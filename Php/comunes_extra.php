<?php
/*
 * FUNCIONES COMUNES DE LOS MÓDULOS NUEVOS (Planeación y Administrador)
 * ---------------------------------------------------------------
 * Este archivo SOLO declara funciones y constantes; no ejecuta nada al incluirse.
 * Lo usan: controlador.php, validarLogin.php, planeacion_extra.php, admin_extra.php
 * y los dos paneles (para revisar la inactividad).
 * Todas las consultas usan sentencias preparadas (prepare / bind_param).
 */

// Minutos sin actividad antes de cerrar la sesión automáticamente.
const MINUTOS_INACTIVIDAD = 30;

// Opciones del controlador que SOLO puede usar el administrador.
const OPCIONES_SOLO_ADMIN = [
    'frmUsuarios', 'EstructuraTbUsuarios', 'OpsUsuarios', 'DatosUsuarios',
    'frmEjes', 'EstructuraTbEjes', 'OpsEjes',
    'frmProgramas', 'EstructuraTbProgramas', 'OpsProgramas',
];

// Opciones públicas: las usa avance.php, que ya valida su propia clave.
const OPCIONES_PUBLICAS = ['ResumenAvanceProyecto', 'DatosAvanceProgramas'];

// Tipos de observación por meta (Php/planeacion_extra.php, tabla observaciones_meta).
const TIPOS_OBSERVACION = ['Observación', 'Acuerdo', 'Problema'];


////////////////////// SEGURIDAD //////////////////////

/*
 * Se llama al inicio de controlador.php. Antes cualquiera podía ejecutar
 * cualquier opción (crear usuarios, borrar metas...) sin iniciar sesión.
 *  - Opciones públicas: pasan (tienen su propia clave).
 *  - Sin sesión o con sesión vencida por inactividad: 401.
 *  - Opción de administrador pedida por otro rol: 403.
 */
function protegerControlador($idopc)
{
    if (in_array($idopc, OPCIONES_PUBLICAS, true)) {
        return;
    }
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['rol'])) {
        http_response_code(401);
        echo mensajeSesion('Tu sesión no está activa.');
        exit;
    }
    if (!revisarInactividad()) {
        http_response_code(401);
        echo mensajeSesion('Tu sesión se cerró por inactividad.');
        exit;
    }
    if (in_array($idopc, OPCIONES_SOLO_ADMIN, true) && $_SESSION['rol'] !== 'administrador') {
        http_response_code(403);
        echo '<div class="alert alert-danger"><i class="bi bi-shield-lock me-1"></i>No tienes permiso para esta opción.</div>';
        exit;
    }
    // La usa js/extras_ui.js para mantener viva la sesión mientras el usuario trabaja.
    if ($idopc === 'MantenerSesion') {
        responderJson(['ok' => true]);
    }
}

function mensajeSesion($texto)
{
    return '<div class="alert alert-warning"><i class="bi bi-clock-history me-1"></i>' . e($texto)
         . ' <a href="index.php" class="fw-bold">Iniciar sesión de nuevo</a></div>';
}

// Revisa en el servidor que quien llama tenga sesión con el rol indicado.
// Es la misma regla que usan administrador.php y planeacion.php al inicio.
function verificarRol($rol)
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== $rol) {
        http_response_code(403);
        echo 'Acceso denegado. Esta opción es solo para el rol: ' . e($rol) . '.';
        exit;
    }
}

// Devuelve false (y cierra la sesión) si pasaron más de MINUTOS_INACTIVIDAD
// desde la última petición. Si no, anota la hora de esta petición.
function revisarInactividad()
{
    $ahora = time();
    if (isset($_SESSION['ultimo_acceso']) && ($ahora - $_SESSION['ultimo_acceso']) > MINUTOS_INACTIVIDAD * 60) {
        $_SESSION = [];
        session_destroy();
        return false;
    }
    $_SESSION['ultimo_acceso'] = $ahora;
    return true;
}


////////////////////// BITÁCORA DE OPERACIONES EXISTENTES //////////////////////

/*
 * controlador.php llama a esta función ANTES de ejecutar la operación
 * (para leer el nombre del registro antes de que se edite o se borre) y a
 * guardarBitacoraOperacion() DESPUÉS. Así no se toca operaciones.php.
 * Devuelve null si la operación no se registra.
 */
function prepararBitacoraOperacion($idopc)
{
    $catalogo = [
        'OpsEjes'      => ['tabla' => 'ejes',      'id' => 'id_eje',      'campo' => 'nombre_eje',      'nombre' => 'eje',
                           'ops' => ['Insert' => 'Crear', 'UpdateEje' => 'Editar', 'DeleteEje' => 'Eliminar']],
        'OpsProgramas' => ['tabla' => 'programas', 'id' => 'id_programa', 'campo' => 'nombre_programa', 'nombre' => 'programa',
                           'ops' => ['Insert' => 'Crear', 'UpdatePrograma' => 'Editar', 'DeletePrograma' => 'Eliminar']],
        'OpsMetas'     => ['tabla' => 'metas',     'id' => 'id_meta',     'campo' => 'actividad',       'nombre' => 'meta',
                           'ops' => ['Insert' => 'Crear', 'UpdateMeta' => 'Editar', 'DeleteMeta' => 'Eliminar']],
        'OpsUsuarios'  => ['tabla' => 'usuarios',  'id' => 'id_usuario',  'campo' => 'usuario',         'nombre' => 'usuario',
                           'ops' => ['Insert' => 'Crear', 'UpdateUsuario' => 'Editar', 'DeleteUsuario' => 'Eliminar']],
    ];
    $ops = $_GET['ops'] ?? '';
    if (!isset($catalogo[$idopc]['ops'][$ops])) {
        return null;
    }

    try {
        $c     = $catalogo[$idopc];
        $verbo = $c['ops'][$ops];
        $idr   = (int)($_GET['idr'] ?? 0);
        $nuevo = trim($_POST[$c['campo']] ?? '');

        if ($verbo === 'Crear') {
            $texto = $nuevo;
        } else {
            // Nombre actual en la base, antes de editarlo o eliminarlo
            $filas  = consultarPreparado("SELECT {$c['campo']} AS nombre FROM {$c['tabla']} WHERE {$c['id']} = ?", 'i', [$idr]);
            $actual = $filas[0]['nombre'] ?? '';
            $texto  = '#' . $idr . ' ' . $actual;
            if ($verbo === 'Editar' && $nuevo !== '' && mb_strtoupper($nuevo) !== mb_strtoupper($actual)) {
                $texto .= ' → ' . $nuevo;
            }
        }
        if ($idopc === 'OpsUsuarios' && $verbo === 'Editar' && trim($_POST['contrasena'] ?? '') !== '') {
            $texto .= ' (se cambió su contraseña)';
        }
        return ['accion' => $verbo . ' ' . $c['nombre'], 'descripcion' => ucfirst($c['nombre']) . ': ' . $texto];
    } catch (Throwable $error) {
        return null;
    }
}

// Guarda en la bitácora solo si la última consulta de la operación no falló.
// Nunca interrumpe la respuesta de la operación original.
function guardarBitacoraOperacion($datos)
{
    if (!$datos) return;
    global $obj;
    try {
        if (mysqli_affected_rows($obj->conexion()) < 0) return;
        registrarBitacora($datos['accion'], $datos['descripcion']);
    } catch (Throwable $error) {
        // Si la bitácora falla, la operación ya se hizo; no se muestra error.
    }
}


////////////////////// AUXILIARES //////////////////////

// Ejecuta un SELECT preparado y devuelve todas las filas.
function consultarPreparado($sql, $tipos = '', $parametros = [])
{
    global $obj;
    $conexion = $obj->conexion();
    $stmt = mysqli_prepare($conexion, $sql);
    if ($tipos !== '') {
        mysqli_stmt_bind_param($stmt, $tipos, ...$parametros);
    }
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    $filas = mysqli_fetch_all($resultado, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    return $filas;
}

// Guarda un movimiento en la tabla bitacora. Si falla, lanza excepción
// para que GuardarAvance() pueda revertir la transacción.
function registrarBitacora($accion, $descripcion)
{
    global $obj;
    $conexion   = $obj->conexion();
    $id_usuario = $_SESSION['id_usuario'] ?? null;
    $ip         = ipCliente();
    $stmt = mysqli_prepare($conexion, "INSERT INTO bitacora (id_usuario, accion, descripcion, ip) VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, 'isss', $id_usuario, $accion, $descripcion, $ip);
    if (!mysqli_stmt_execute($stmt)) throw new Exception('No se pudo registrar en la bitácora.');
    mysqli_stmt_close($stmt);
}

// IP del visitante. En Render todas las peticiones llegan por un proxy, así que
// la IP real viene en la cabecera X-Forwarded-For (la primera de la lista).
function ipCliente()
{
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $primera = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
        if (filter_var($primera, FILTER_VALIDATE_IP)) return $primera;
    }
    return $_SERVER['REMOTE_ADDR'] ?? '';
}

// Escapa texto para mostrarlo en HTML.
function e($texto)
{
    return htmlspecialchars((string)$texto, ENT_QUOTES, 'UTF-8');
}

// 1500.50 -> "1,500.5"   10.00 -> "10"
function formatoNumero($numero)
{
    $texto = number_format((float)$numero, 2, '.', ',');
    return rtrim(rtrim($texto, '0'), '.');
}

// 2026-07-21 -> 21/07/2026
function formatoFecha($fecha)
{
    if (!$fecha) return 'Sin fecha';
    return date('d/m/Y', strtotime($fecha));
}

// Calcula el estado según lo alcanzado. Es la misma regla que usa crudMetas().
function calcularEstado($programada, $alcanzada)
{
    if ($programada > 0) {
        $avance = ($alcanzada / $programada) * 100;
        if ($avance >= 100)  return 'Terminado';
        if ($avance > 0)     return 'En Proceso';
    }
    return 'Pendiente';
}

/*
 * SEMÁFORO DE CUMPLIMIENTO
 * real%     = alcanzada / programada (máximo 100)
 * esperado% = días transcurridos desde fecha_inicio / días totales hasta fecha_limite (entre 0 y 100)
 * Verde:    real está a 10 puntos o menos debajo de lo esperado
 * Amarillo: entre 10 y 25 puntos debajo
 * Rojo:     más de 25 puntos debajo
 * No cambia la columna estado; solo se calcula al mostrar.
 */
function calcularSemaforo($meta)
{
    $programada = (float)$meta['meta_programada'];
    $alcanzada  = (float)$meta['meta_alcanzada'];
    $real       = ($programada > 0) ? min($alcanzada / $programada, 1) * 100 : 0;

    if ($real >= 100) {
        return ['real' => 100, 'esperado' => 100, 'color' => 'Verde'];
    }
    if (empty($meta['fecha_limite'])) {
        return ['real' => round($real, 1), 'esperado' => null, 'color' => 'Sin fecha'];
    }

    // Los días vienen calculados por MySQL (columnasDias()), así "hoy" es el mismo que usan las alertas.
    $diasTotales       = (int)$meta['dias_totales'];
    $diasTranscurridos = (int)$meta['dias_transcurridos'];

    if ($diasTotales <= 0) {
        $esperado = ($diasTranscurridos >= $diasTotales) ? 100 : 0;
    } else {
        $esperado = ($diasTranscurridos / $diasTotales) * 100;
    }
    $esperado = max(0, min(100, $esperado));

    $diferencia = $esperado - $real;
    if ($diferencia <= 10)      $color = 'Verde';
    elseif ($diferencia <= 25)  $color = 'Amarillo';
    else                        $color = 'Rojo';

    return ['real' => round($real, 1), 'esperado' => round($esperado, 1), 'color' => $color];
}

// Columnas que necesita calcularSemaforo(); se agregan al SELECT de metas (alias m).
function columnasDias()
{
    return "DATEDIFF(m.fecha_limite, m.fecha_inicio) AS dias_totales,
            DATEDIFF(CURDATE(), m.fecha_inicio)       AS dias_transcurridos";
}

// Fecha de hoy según la base de datos (formato Y-m-d).
function fechaHoy()
{
    $filas = consultarPreparado("SELECT CURDATE() AS hoy");
    return $filas[0]['hoy'];
}

// Círculo de color para el semáforo (HTML).
function circuloSemaforo($color)
{
    $colores = ['Verde' => '#2fa86a', 'Amarillo' => '#e0a800', 'Rojo' => '#c0392b', 'Sin fecha' => '#adb5bd'];
    $c = $colores[$color] ?? '#adb5bd';
    return '<span style="display:inline-flex;align-items:center;gap:6px;font-size:.78rem;font-weight:600;">'
         . '<span style="width:14px;height:14px;border-radius:50%;background:' . $c . ';display:inline-block;"></span>'
         . e($color) . '</span>';
}

// Etiqueta de estado con los mismos colores que la tabla de metas.
function etiquetaEstado($estado)
{
    $c = ['Terminado' => ['#1a6b3c', '#d4f4e2'], 'En Proceso' => ['#7d5a00', '#fff3cd'], 'Pendiente' => ['#7b1f1f', '#fde8e8']];
    [$texto, $fondo] = $c[$estado] ?? ['#333', '#eee'];
    return '<span style="background:' . $fondo . ';color:' . $texto . ';font-size:.72rem;font-weight:700;padding:3px 10px;border-radius:20px;">' . e($estado) . '</span>';
}

function responderJson($datos)
{
    header('Content-Type: application/json');
    echo json_encode($datos);
    exit;
}



////////////////////// COMPARATIVO TRIMESTRAL //////////////////////

// Años que tienen metas (más el año actual), del más reciente al más antiguo.
function aniosDisponibles()
{
    $filas = consultarPreparado(
        "SELECT YEAR(fecha_inicio) AS anio FROM metas
         UNION SELECT YEAR(fecha_limite) FROM metas WHERE fecha_limite IS NOT NULL
         UNION SELECT YEAR(CURDATE())
         ORDER BY anio DESC"
    );
    return array_map(fn($f) => (int)$f['anio'], $filas);
}

/*
 * % de avance acumulado al cierre de cada trimestre, promedio por eje.
 *  - Por cada meta: (avance inicial + avances registrados hasta el fin del trimestre) / programada, máximo 100%.
 *  - "Avance inicial" = lo que se capturó a mano en la meta antes de usar el registro de avances
 *    (meta_alcanzada - suma de avances). Se cuenta desde la fecha de inicio de la meta.
 *  - Se promedian porcentajes (no unidades), así no se mezclan jornadas con becas o kilómetros.
 *  - Trimestres que todavía no empiezan quedan vacíos (null).
 */
function datosTrimestrales($anio)
{
    $inicio = [1 => "$anio-01-01", 2 => "$anio-04-01", 3 => "$anio-07-01", 4 => "$anio-10-01"];
    $fin    = [1 => "$anio-03-31", 2 => "$anio-06-30", 3 => "$anio-09-30", 4 => "$anio-12-31"];

    $metas = consultarPreparado(
        "SELECT m.id_meta, e.nombre_eje, m.meta_programada, m.meta_alcanzada, m.fecha_inicio,
                COALESCE(SUM(a.valor_avance), 0) AS total_avances,
                COALESCE(SUM(CASE WHEN a.fecha_avance <= ? THEN a.valor_avance END), 0) AS hasta_t1,
                COALESCE(SUM(CASE WHEN a.fecha_avance <= ? THEN a.valor_avance END), 0) AS hasta_t2,
                COALESCE(SUM(CASE WHEN a.fecha_avance <= ? THEN a.valor_avance END), 0) AS hasta_t3,
                COALESCE(SUM(CASE WHEN a.fecha_avance <= ? THEN a.valor_avance END), 0) AS hasta_t4
         FROM metas m
         INNER JOIN programas p ON m.id_programa = p.id_programa
         INNER JOIN ejes e      ON p.id_eje = e.id_eje
         LEFT JOIN avances_meta a ON a.id_meta = m.id_meta
         GROUP BY m.id_meta, e.nombre_eje, m.meta_programada, m.meta_alcanzada, m.fecha_inicio
         ORDER BY e.nombre_eje",
        'ssss', [$fin[1], $fin[2], $fin[3], $fin[4]]
    );
    $hoy = fechaHoy();

    $vacio   = [1 => [0, 0], 2 => [0, 0], 3 => [0, 0], 4 => [0, 0]];   // [suma de %, cuántas metas]
    $porEje  = [];
    $general = $vacio;

    foreach ($metas as $m) {
        $programada = (float)$m['meta_programada'];
        if ($programada <= 0) continue;
        $base = max(0, (float)$m['meta_alcanzada'] - (float)$m['total_avances']);
        $eje  = $m['nombre_eje'];
        if (!isset($porEje[$eje])) $porEje[$eje] = $vacio;

        for ($t = 1; $t <= 4; $t++) {
            if ($inicio[$t] > $hoy) continue;              // el trimestre aún no empieza
            if ($m['fecha_inicio'] > $fin[$t]) continue;   // la meta aún no empezaba
            $porcentaje = min(($base + (float)$m['hasta_t' . $t]) / $programada, 1) * 100;
            $porEje[$eje][$t][0] += $porcentaje;  $porEje[$eje][$t][1]++;
            $general[$t][0]      += $porcentaje;  $general[$t][1]++;
        }
    }

    $promedios = function ($trimestres) {
        $valores = [];
        foreach ($trimestres as $t) {
            $valores[] = $t[1] > 0 ? round($t[0] / $t[1], 1) : null;
        }
        return $valores;
    };

    $ejes = [];
    foreach ($porEje as $nombre => $trimestres) {
        $ejes[] = ['nombre' => $nombre, 'valores' => $promedios($trimestres)];
    }
    return [
        'anio'       => (int)$anio,
        'trimestres' => ['T1 (Ene–Mar)', 'T2 (Abr–Jun)', 'T3 (Jul–Sep)', 'T4 (Oct–Dic)'],
        'ejes'       => $ejes,
        'general'    => $promedios($general),
    ];
}

// Pantalla del comparativo (la usan los dos paneles).
function VistaTrimestral($idopcDatos)
{
    $anios  = aniosDisponibles();
    $actual = (int)date('Y', strtotime(fechaHoy()));
?>
    <h5 class="fw-bold mb-1" style="color:#0A2647;">
        <i class="bi bi-calendar4-week me-2"></i>Comparativo Trimestral
    </h5>
    <p class="text-muted mb-3" style="font-size:.85rem;">
        Avance acumulado al cierre de cada trimestre, promedio de las metas de cada eje (en porcentaje, para no mezclar unidades distintas).
    </p>
    <div class="d-flex align-items-end gap-2 mb-3">
        <div>
            <label class="form-label fw-semibold" style="font-size:.8rem;">Año</label>
            <select class="form-select form-select-sm" id="anioTrimestral" onchange="graficaTrimestral('<?= e($idopcDatos) ?>')">
                <?php foreach ($anios as $anio): ?>
                    <option value="<?= $anio ?>" <?= $anio === $actual ? 'selected' : '' ?>><?= $anio ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="card mb-3"><div class="card-body"><canvas id="graficaTrimestral" height="110"></canvas></div></div>
    <div class="card"><div class="card-body"><div class="table-responsive" id="tablaTrimestral"></div></div></div>
<?php
}
