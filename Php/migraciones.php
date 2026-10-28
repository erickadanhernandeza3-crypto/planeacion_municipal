<?php
/*
 * MIGRACIONES AUTOMÁTICAS DE LA BASE DE DATOS
 * ---------------------------------------------------------------
 * En Render no hay acceso directo a la base de datos, así que el sistema se
 * actualiza solo: la primera petición después de un despliegue aplica las
 * migraciones pendientes. La llama OperacionesBd::conexion() (conexion.php).
 *
 *  - Cada migración se aplica una sola vez (tabla esquema_version).
 *  - Se pueden repetir sin daño: revisan si la tabla o columna ya existe.
 *  - GET_LOCK evita que dos peticiones al mismo tiempo apliquen lo mismo.
 *  - Si una falla, se escribe en el log y se reintenta en la siguiente petición.
 *
 * Para agregar una migración: súmale 1 a VERSION_ESQUEMA y agrega su función
 * en listaMigraciones().
 */

const VERSION_ESQUEMA = 3;

function aplicarMigraciones(mysqli $conexion)
{
    static $revisado = false;
    if ($revisado) return;
    $revisado = true;

    // Cuando ya se confirmó que la base está al día, se deja una marca en la carpeta
    // temporal del servidor: así no se gasta una consulta en cada petición (la base
    // puede estar lejos y cada consulta cuenta). Si Render reinicia, la marca se
    // borra y se vuelve a revisar una sola vez.
    $marca = sys_get_temp_dir() . '/planeacion_esquema_' . VERSION_ESQUEMA . '_'
           . md5(($conexion->host_info ?? '') . '|' . configuracion('DB_NAME', 'planeacion_municipal_adan'));
    if (is_file($marca)) return;

    if (versionEsquema($conexion) >= VERSION_ESQUEMA) {
        @touch($marca);
        return;
    }

    $bloqueo = $conexion->query("SELECT GET_LOCK('planeacion_migraciones', 30)")->fetch_row()[0];
    if ((int)$bloqueo !== 1) return;
    try {
        $actual = versionEsquema($conexion);   // otra petición pudo aplicarlas mientras se esperaba
        foreach (listaMigraciones() as $version => $migrar) {
            if ($version <= $actual) continue;
            $migrar($conexion);
            $stmt = $conexion->prepare("INSERT INTO esquema_version (version) VALUES (?)");
            $stmt->bind_param('i', $version);
            $stmt->execute();
            $stmt->close();
            error_log("Migración $version aplicada.");
        }
        @touch($marca);
    } catch (Throwable $error) {
        error_log('Error al aplicar migraciones: ' . $error);
    } finally {
        $conexion->query("SELECT RELEASE_LOCK('planeacion_migraciones')");
    }
}

function versionEsquema(mysqli $conexion)
{
    try {
        return (int)$conexion->query("SELECT COALESCE(MAX(version), 0) FROM esquema_version")->fetch_row()[0];
    } catch (mysqli_sql_exception $error) {
        // Primera vez: la tabla todavía no existe
        $conexion->query("CREATE TABLE IF NOT EXISTS esquema_version (
                            version INT NOT NULL PRIMARY KEY,
                            aplicada_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                          ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        return 0;
    }
}

function columnaExiste(mysqli $conexion, $tabla, $columna)
{
    $stmt = $conexion->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS
                                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
    $stmt->bind_param('ss', $tabla, $columna);
    $stmt->execute();
    $existe = (int)$stmt->get_result()->fetch_row()[0] > 0;
    $stmt->close();
    return $existe;
}

function agregarColumna(mysqli $conexion, $tabla, $columna, $definicion)
{
    if (!columnaExiste($conexion, $tabla, $columna)) {
        $conexion->query("ALTER TABLE `$tabla` ADD COLUMN `$columna` $definicion");
    }
}

function listaMigraciones()
{
    return [
        // 1. Rol "enlace" por dependencia y revisión de avances
        1 => function (mysqli $c) {
            $c->query("ALTER TABLE usuarios MODIFY rol ENUM('administrador','planeacion','enlace')
                       COLLATE utf8mb4_unicode_ci NOT NULL");
            agregarColumna($c, 'usuarios', 'dependencia', "VARCHAR(100) COLLATE utf8mb4_unicode_ci NULL AFTER rol");

            // Los avances que ya existían quedan como aprobados.
            agregarColumna($c, 'avances_meta', 'estado_revision',
                "ENUM('En revisión','Aprobado','Devuelto') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Aprobado' AFTER registrado_por");
            agregarColumna($c, 'avances_meta', 'revisado_por', "INT NULL AFTER estado_revision");
            agregarColumna($c, 'avances_meta', 'revisado_en', "DATETIME NULL AFTER revisado_por");
            agregarColumna($c, 'avances_meta', 'comentario_revision', "VARCHAR(500) COLLATE utf8mb4_unicode_ci NULL AFTER revisado_en");

            $fk = $c->query("SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
                             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'avances_meta'
                               AND CONSTRAINT_NAME = 'fk_av_revisor'")->fetch_row()[0];
            if ((int)$fk === 0) {
                $c->query("ALTER TABLE avances_meta
                           ADD KEY idx_av_revision (estado_revision),
                           ADD CONSTRAINT fk_av_revisor FOREIGN KEY (revisado_por)
                               REFERENCES usuarios (id_usuario) ON DELETE SET NULL ON UPDATE CASCADE");
            }
        },

        // 2. Evidencias (fotos y PDF guardados en Cloudinary)
        2 => function (mysqli $c) {
            $c->query("CREATE TABLE IF NOT EXISTS evidencias (
                id_evidencia    INT NOT NULL AUTO_INCREMENT,
                id_meta         INT NOT NULL,
                id_avance       INT NULL,
                public_id       VARCHAR(255) NOT NULL,
                tipo_recurso    ENUM('image','raw') NOT NULL,
                url             VARCHAR(500) NOT NULL,
                formato         VARCHAR(10) NULL,
                bytes           INT NULL,
                nombre_original VARCHAR(255) NULL,
                subido_por      INT NULL,
                creado_en       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id_evidencia),
                KEY idx_ev_meta (id_meta),
                KEY idx_ev_avance (id_avance),
                CONSTRAINT fk_ev_meta   FOREIGN KEY (id_meta)    REFERENCES metas (id_meta) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT fk_ev_avance FOREIGN KEY (id_avance)  REFERENCES avances_meta (id_avance) ON DELETE SET NULL ON UPDATE CASCADE,
                CONSTRAINT fk_ev_usr    FOREIGN KEY (subido_por) REFERENCES usuarios (id_usuario) ON DELETE SET NULL ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        },

        // 3. Ficha técnica de los indicadores MIR
        3 => function (mysqli $c) {
            agregarColumna($c, 'indicadores', 'nombre_indicador',    "VARCHAR(255) NULL AFTER descripcion");
            agregarColumna($c, 'indicadores', 'formula',             "TEXT NULL AFTER nombre_indicador");
            agregarColumna($c, 'indicadores', 'dimension',           "ENUM('Eficacia','Eficiencia','Calidad','Economía') NULL AFTER formula");
            agregarColumna($c, 'indicadores', 'sentido',             "ENUM('Ascendente','Descendente') NULL AFTER dimension");
            agregarColumna($c, 'indicadores', 'frecuencia',          "ENUM('Mensual','Trimestral','Semestral','Anual') NULL AFTER sentido");
            agregarColumna($c, 'indicadores', 'linea_base',          "DECIMAL(14,2) NULL AFTER unidad_medida");
            agregarColumna($c, 'indicadores', 'anio_linea_base',     "SMALLINT NULL AFTER linea_base");
            agregarColumna($c, 'indicadores', 'meta_anual',          "DECIMAL(14,2) NULL AFTER anio_linea_base");
            agregarColumna($c, 'indicadores', 'medios_verificacion', "TEXT NULL AFTER meta_anual");
            agregarColumna($c, 'indicadores', 'supuestos',           "TEXT NULL AFTER medios_verificacion");
        },
    ];
}

// Si una consulta falla porque falta una tabla (1146) o una columna (1054) -por ejemplo,
// se restauró un respaldo viejo sin reiniciar el servidor-, se borra la marca para que
// la siguiente petición vuelva a revisar la base y aplique lo que falte.
function revisarEsquemaTrasError(Throwable $error)
{
    if ($error instanceof mysqli_sql_exception && in_array($error->getCode(), [1054, 1146], true)) {
        foreach (glob(sys_get_temp_dir() . '/planeacion_esquema_*') ?: [] as $marca) {
            @unlink($marca);
        }
    }
}
