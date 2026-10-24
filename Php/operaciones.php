<?php

include('vistas.php');
$idopc = $_GET['idopc'] ?? '';

switch ($idopc) {

    case 'OpsEjes':
        crudEjes();
        break;

    case 'OpsProgramas':
        crudProgramas();
        break;

    case 'OpsMetas':
        crudMetas();
        break;

    case 'OpsUsuarios':
        crudUsuarios();
        break;

    case 'DatosEjes':
        InfoEjes();
        break;

    case 'DatosProgramas':
        InfoProgramas();
        break;

    case 'DatosMetas':
        InfoMetas();
        break;

    case 'DatosUsuarios':
        InfoUsuarios();
        break;

    case 'DatosGraficaEstados':
        DatosGraficaEstados();
        break;

    case 'DatosGraficaEjesBar':
        DatosGraficaEjesBar();
        break;

    case 'DatosGraficaPresupuesto':
        DatosGraficaPresupuesto();
        break;

    case 'DatosGraficaAvanceProgramas':
        DatosGraficaAvanceProgramas();
        break;

    case 'DatosGraficaCalendario':
        DatosGraficaCalendario();
        break;

    case 'DatosProgramasPorEje':
        ProgramasPorEje();
        break;

    case 'ResumenAvanceProyecto':
        ResumenAvanceProyecto();
        break;

    case 'DatosAvanceProgramas':
        DatosAvanceProgramas();
        break;

    case 'OpsIndicadores':
        crudIndicadores();
        break;

    case 'IndicadoresPrograma':
        IndicadoresPrograma();
        break;

    case 'FormCalendario':
        formCalendario();
        break;

    case 'GuardarCalendario':
        guardarCalendario();
        break;
}


////////////////////// CRUD EJES //////////////////////

function crudEjes()
{
    $ops = $_GET['ops'] ?? '';
    $idr = (int)($_GET['idr'] ?? 0);

    if ($ops == "Insert") {
        $nombre_eje = mb_strtoupper(textoRequerido($_POST['nombre_eje'] ?? '', 'Nombre del eje', 150));
        try {
            ejecutarPreparado("INSERT INTO ejes (nombre_eje) VALUES (?)", 's', [$nombre_eje]);
        } catch (mysqli_sql_exception $error) {
            if (esDuplicado($error)) rechazar('Ya existe un eje con ese nombre.');
            throw $error;
        }
        echo "Eje registrado.";
    }

    if ($ops == "Edit") {
        $resultado = consultarPreparado("SELECT * FROM ejes WHERE id_eje = ?", 'i', [$idr]);
        if (!$resultado) rechazar('El eje ya no existe.', 404);
        FormularioEjesActualizacion($resultado);
    }

    if ($ops == "UpdateEje") {
        $nombre_eje = mb_strtoupper(textoRequerido($_POST['nombre_eje'] ?? '', 'Nombre del eje', 150));
        try {
            ejecutarPreparado("UPDATE ejes SET nombre_eje = ? WHERE id_eje = ?", 'si', [$nombre_eje, $idr]);
        } catch (mysqli_sql_exception $error) {
            if (esDuplicado($error)) rechazar('Ya existe un eje con ese nombre.');
            throw $error;
        }
        echo "Eje actualizado.";
    }

    if ($ops == "DeleteEje") {
        // La base de datos borra en cascada sus programas, metas, avances,
        // observaciones e indicadores (llaves foráneas ON DELETE CASCADE).
        ejecutarPreparado("DELETE FROM ejes WHERE id_eje = ?", 'i', [$idr]);
        MostrarEjes();
    }
}


////////////////////// CRUD PROGRAMAS //////////////////////

// Valida y devuelve los campos del formulario de programas.
function datosFormularioPrograma()
{
    $id_eje = (int)($_POST['id_eje'] ?? 0);
    if (!consultarPreparado("SELECT id_eje FROM ejes WHERE id_eje = ?", 'i', [$id_eje])) {
        rechazar('Selecciona un eje válido.');
    }
    return [
        $id_eje,
        textoRequerido($_POST['nombre_programa'] ?? '', 'Nombre del programa', 150),
        textoOpcional($_POST['responsable'] ?? '', 'Área responsable', 100),
        textoOpcional($_POST['descripcion'] ?? '', 'Descripción', 2000),
    ];
}

function crudProgramas()
{
    $ops = $_GET['ops'] ?? '';
    $idr = (int)($_GET['idr'] ?? 0);

    if ($ops == "Insert") {
        [$id_eje, $nombre, $responsable, $descripcion] = datosFormularioPrograma();
        ejecutarPreparado(
            "INSERT INTO programas (id_eje, nombre_programa, responsable, descripcion) VALUES (?, ?, ?, ?)",
            'isss', [$id_eje, $nombre, $responsable, $descripcion]
        );
        echo "Programa registrado.";
    }

    if ($ops == "Edit") {
        $resultado = consultarPreparado("SELECT * FROM programas WHERE id_programa = ?", 'i', [$idr]);
        if (!$resultado) rechazar('El programa ya no existe.', 404);
        FormularioProgramasActualizacion($resultado);
    }

    if ($ops == "UpdatePrograma") {
        [$id_eje, $nombre, $responsable, $descripcion] = datosFormularioPrograma();
        ejecutarPreparado(
            "UPDATE programas SET id_eje = ?, nombre_programa = ?, responsable = ?, descripcion = ? WHERE id_programa = ?",
            'isssi', [$id_eje, $nombre, $responsable, $descripcion, $idr]
        );
        echo "Programa actualizado.";
    }

    if ($ops == "DeletePrograma") {
        // En cascada: metas, avances, observaciones e indicadores del programa.
        ejecutarPreparado("DELETE FROM programas WHERE id_programa = ?", 'i', [$idr]);
        MostrarProgramas();
    }
}


////////////////////// CRUD METAS //////////////////////

// Valida y devuelve los campos del formulario de metas (el estado se calcula solo).
function datosFormularioMeta()
{
    $id_programa = (int)($_POST['id_programa'] ?? 0);
    if (!consultarPreparado("SELECT id_programa FROM programas WHERE id_programa = ?", 'i', [$id_programa])) {
        rechazar('Selecciona un programa válido.');
    }
    $actividad       = textoRequerido($_POST['actividad'] ?? '', 'Actividad / Meta', 255);
    $unidad_medida   = textoRequerido($_POST['unidad_medida'] ?? '', 'Unidad de medida', 80);
    $meta_programada = numeroNoNegativo($_POST['meta_programada'] ?? '', 'Meta programada');
    if ($meta_programada <= 0) rechazar('La meta programada debe ser mayor a cero.');
    $meta_alcanzada  = numeroNoNegativo($_POST['meta_alcanzada'] ?? '0', 'Meta alcanzada');
    $fecha_inicio    = fechaValida($_POST['fecha_inicio'] ?? '', 'Fecha de inicio', true);
    $fecha_limite    = fechaValida($_POST['fecha_limite'] ?? '', 'Fecha límite');
    if ($fecha_limite !== null && $fecha_limite < $fecha_inicio) {
        rechazar('La fecha límite no puede ser anterior a la fecha de inicio.');
    }
    $asignado      = numeroNoNegativo($_POST['presupuesto_asignado'] ?? '', 'Presupuesto asignado', true);
    $ejercido      = numeroNoNegativo($_POST['presupuesto_ejercido'] ?? '', 'Presupuesto ejercido', true);
    $observaciones = textoOpcional($_POST['observaciones'] ?? '', 'Observaciones', 5000);
    $estado        = calcularEstado($meta_programada, $meta_alcanzada);

    return [$id_programa, $actividad, $unidad_medida, $meta_programada, $meta_alcanzada,
            $asignado, $ejercido, $observaciones, $fecha_inicio, $fecha_limite, $estado];
}

function crudMetas()
{
    $ops = $_GET['ops'] ?? '';
    $idr = (int)($_GET['idr'] ?? 0);

    if ($ops == "Insert") {
        $datos   = datosFormularioMeta();
        $datos[] = $_SESSION['id_usuario'] ?? null;
        ejecutarPreparado(
            "INSERT INTO metas
                (id_programa, actividad, unidad_medida, meta_programada, meta_alcanzada,
                 presupuesto_asignado, presupuesto_ejercido, observaciones,
                 fecha_inicio, fecha_limite, estado, creado_por)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            'issddddssssi', $datos
        );
        echo "Meta registrada.";
    }

    if ($ops == "Edit") {
        $resultado = consultarPreparado("SELECT * FROM metas WHERE id_meta = ?", 'i', [$idr]);
        if (!$resultado) rechazar('La meta ya no existe.', 404);
        FormularioMetasActualizacion($resultado);
    }

    if ($ops == "UpdateMeta") {
        $datos   = datosFormularioMeta();
        $datos[] = $idr;
        ejecutarPreparado(
            "UPDATE metas SET
                id_programa = ?, actividad = ?, unidad_medida = ?, meta_programada = ?, meta_alcanzada = ?,
                presupuesto_asignado = ?, presupuesto_ejercido = ?, observaciones = ?,
                fecha_inicio = ?, fecha_limite = ?, estado = ?
             WHERE id_meta = ?",
            'issddddssssi', $datos
        );
        echo "Meta actualizada.";
    }

    if ($ops == "DeleteMeta") {
        // En cascada: sus avances y observaciones.
        ejecutarPreparado("DELETE FROM metas WHERE id_meta = ?", 'i', [$idr]);
        MostrarMetas();
    }
}


////////////////////// CRUD USUARIOS //////////////////////

// Valida y devuelve nombre, usuario y rol del formulario de usuarios.
function datosFormularioUsuario()
{
    $nombre  = textoRequerido($_POST['nombre'] ?? '', 'Nombre completo', 100);
    $usuario = textoRequerido($_POST['usuario'] ?? '', 'Usuario', 50);
    if (!preg_match('/^[A-Za-z0-9._-]{3,50}$/', $usuario)) {
        rechazar('El usuario debe tener de 3 a 50 caracteres: letras sin acento, números, punto, guion o guion bajo.');
    }
    $rol = $_POST['rol'] ?? '';
    if (!in_array($rol, ROLES_VALIDOS, true)) rechazar('Selecciona un rol válido.');
    return [$nombre, $usuario, $rol];
}

function validarContrasenaNueva($password)
{
    if (mb_strlen($password) < MINIMO_CONTRASENA) {
        rechazar('La contraseña debe tener al menos ' . MINIMO_CONTRASENA . ' caracteres.');
    }
}

// Cuántos administradores activos hay, sin contar al usuario indicado.
function otrosAdministradoresActivos($id_usuario)
{
    $filas = consultarPreparado(
        "SELECT COUNT(*) AS total FROM usuarios WHERE rol = 'administrador' AND activo = 1 AND id_usuario <> ?",
        'i', [$id_usuario]
    );
    return (int)$filas[0]['total'];
}

// true si el usuario indicado es hoy un administrador activo.
function esAdministradorActivo($id_usuario)
{
    $filas = consultarPreparado("SELECT rol, activo FROM usuarios WHERE id_usuario = ?", 'i', [$id_usuario]);
    return $filas && $filas[0]['rol'] === 'administrador' && (int)$filas[0]['activo'] === 1;
}

function crudUsuarios()
{
    $ops = $_GET['ops'] ?? '';
    $idr = (int)($_GET['idr'] ?? 0);

    if ($ops == "Insert") {
        [$nombre, $usuario, $rol] = datosFormularioUsuario();
        $password = trim((string)($_POST['contrasena'] ?? ''));   // igual que el login, que también recorta espacios
        validarContrasenaNueva($password);
        try {
            ejecutarPreparado(
                "INSERT INTO usuarios (nombre, usuario, contrasena, rol) VALUES (?, ?, ?, ?)",
                'ssss', [$nombre, $usuario, password_hash($password, PASSWORD_BCRYPT), $rol]
            );
        } catch (mysqli_sql_exception $error) {
            if (esDuplicado($error)) rechazar('Ese nombre de usuario ya está en uso. Elige otro.');
            throw $error;
        }
        echo "Usuario registrado.";
    }

    if ($ops == "Edit") {
        $resultado = consultarPreparado("SELECT id_usuario, nombre, usuario, rol, activo FROM usuarios WHERE id_usuario = ?", 'i', [$idr]);
        if (!$resultado) rechazar('El usuario ya no existe.', 404);
        FormularioUsuariosActualizacion($resultado);
    }

    if ($ops == "UpdateUsuario") {
        [$nombre, $usuario, $rol] = datosFormularioUsuario();
        $activo   = ((int)($_POST['activo'] ?? 1)) === 1 ? 1 : 0;
        $password = trim((string)($_POST['contrasena'] ?? ''));   // igual que el login, que también recorta espacios

        // Que nadie se quite a sí mismo el acceso, y que siempre quede un administrador activo.
        $pierdeAdmin = ($activo === 0 || $rol !== 'administrador');
        if ($pierdeAdmin && $idr === (int)$_SESSION['id_usuario']) {
            rechazar('No puedes desactivarte ni quitarte el rol de administrador a ti mismo.');
        }
        if ($pierdeAdmin && esAdministradorActivo($idr) && otrosAdministradoresActivos($idr) === 0) {
            rechazar('Debe quedar al menos un administrador activo.');
        }

        try {
            if ($password !== '') {
                validarContrasenaNueva($password);
                ejecutarPreparado(
                    "UPDATE usuarios SET nombre = ?, usuario = ?, contrasena = ?, rol = ?, activo = ? WHERE id_usuario = ?",
                    'ssssii', [$nombre, $usuario, password_hash($password, PASSWORD_BCRYPT), $rol, $activo, $idr]
                );
            } else {
                ejecutarPreparado(
                    "UPDATE usuarios SET nombre = ?, usuario = ?, rol = ?, activo = ? WHERE id_usuario = ?",
                    'sssii', [$nombre, $usuario, $rol, $activo, $idr]
                );
            }
        } catch (mysqli_sql_exception $error) {
            if (esDuplicado($error)) rechazar('Ese nombre de usuario ya está en uso. Elige otro.');
            throw $error;
        }
        echo "Usuario actualizado.";
    }

    if ($ops == "DeleteUsuario") {
        if ($idr === (int)$_SESSION['id_usuario']) {
            rechazar('No puedes eliminar tu propia cuenta.');
        }
        if (esAdministradorActivo($idr) && otrosAdministradoresActivos($idr) === 0) {
            rechazar('Debe quedar al menos un administrador activo.');
        }
        // Sus metas, avances y registros de bitácora se conservan (quedan sin autor).
        ejecutarPreparado("DELETE FROM usuarios WHERE id_usuario = ?", 'i', [$idr]);
        MostrarUsuarios();
    }
}


////////////////////// INFO PARA DATATABLES (JSON) //////////////////////

function InfoEjes()
{
    global $obj;
    $sql   = "SELECT * FROM ejes ORDER BY id_eje ASC";
    $datos = $obj->mostrardatos($sql);
    $lista = [];
    foreach ($datos as $columna) {
        $lista[] = [
            'id_eje'     => $columna['id_eje'],
            'nombre_eje' => $columna['nombre_eje'],
        ];
    }
    header('Content-Type: application/json');
    echo json_encode(["data" => $lista]);
    exit;
}


function InfoProgramas()
{
    global $obj;
    $sql = "SELECT p.*, e.nombre_eje FROM programas p
            INNER JOIN ejes e ON p.id_eje = e.id_eje
            ORDER BY e.id_eje, p.id_programa";
    $datos = $obj->mostrardatos($sql);
    $lista = [];
    foreach ($datos as $columna) {
        $lista[] = [
            'id_programa'     => $columna['id_programa'],
            'nombre_programa' => $columna['nombre_programa'],
            'nombre_eje'      => $columna['nombre_eje'],
            'responsable'     => $columna['responsable'] ?? '',
        ];
    }
    header('Content-Type: application/json');
    echo json_encode(["data" => $lista]);
    exit;
}


function InfoMetas()
{
    global $obj;
    $sql = "SELECT m.*, p.nombre_programa, e.nombre_eje
            FROM metas m
            INNER JOIN programas p ON m.id_programa = p.id_programa
            INNER JOIN ejes e ON p.id_eje = e.id_eje
            ORDER BY m.id_meta DESC";
    $datos = $obj->mostrardatos($sql);
    $lista = [];
    foreach ($datos as $columna) {
        $programada = (float)$columna['meta_programada'];
        $alcanzada  = (float)$columna['meta_alcanzada'];
        $avance     = ($programada > 0) ? round(($alcanzada / $programada) * 100, 1) : 0;
        $lista[] = [
            'id_meta'         => $columna['id_meta'],
            'nombre_eje'      => $columna['nombre_eje'],
            'nombre_programa' => $columna['nombre_programa'],
            'actividad'       => $columna['actividad'],
            'unidad_medida'   => $columna['unidad_medida'],
            'meta_programada' => $columna['meta_programada'],
            'meta_alcanzada'  => $columna['meta_alcanzada'],
            'avance'          => $avance . '%',
            'estado'          => $columna['estado'],
            'fecha_inicio'    => $columna['fecha_inicio'],
        ];
    }
    header('Content-Type: application/json');
    echo json_encode(["data" => $lista]);
    exit;
}


function InfoUsuarios()
{
    global $obj;
    $sql   = "SELECT * FROM usuarios ORDER BY id_usuario ASC";
    $datos = $obj->mostrardatos($sql);
    $lista = [];
    foreach ($datos as $columna) {
        $lista[] = [
            'id_usuario' => $columna['id_usuario'],
            'nombre'     => $columna['nombre'],
            'usuario'    => $columna['usuario'],
            'rol'        => $columna['rol'],
            'activo'     => ($columna['activo'] == 1) ? 'Activo' : 'Inactivo',
        ];
    }
    header('Content-Type: application/json');
    echo json_encode(["data" => $lista]);
    exit;
}


////////////////////// DATOS GRÁFICAS //////////////////////

function DatosGraficaEstados()
{
    global $obj;
    $sql = "SELECT estado, COUNT(*) AS total FROM metas GROUP BY estado";
    $datos = $obj->mostrardatos($sql);
    $estados = [];
    $totales = [];
    foreach ($datos as $fila) {
        $estados[] = $fila['estado'];
        $totales[] = $fila['total'];
    }
    echo json_encode(['estados' => $estados, 'totales' => $totales]);
    exit;
}


function DatosGraficaEjesBar()
{
    global $obj;
    $sql = "SELECT e.nombre_eje,
                   COUNT(m.id_meta) AS total,
                   SUM(CASE WHEN m.estado = 'Terminado'  THEN 1 ELSE 0 END) AS cumplidas,
                   SUM(CASE WHEN m.estado = 'En Proceso' THEN 1 ELSE 0 END) AS enproceso,
                   SUM(CASE WHEN m.estado = 'Pendiente'  THEN 1 ELSE 0 END) AS pendientes
            FROM ejes e
            LEFT JOIN programas p ON e.id_eje = p.id_eje
            LEFT JOIN metas m ON p.id_programa = m.id_programa
            GROUP BY e.id_eje, e.nombre_eje
            ORDER BY e.id_eje";
    $datos = $obj->mostrardatos($sql);
    $ejes       = [];
    $cumplidas  = [];
    $enproceso  = [];
    $pendientes = [];
    foreach ($datos as $fila) {
        $ejes[]       = $fila['nombre_eje'];
        $cumplidas[]  = (int)$fila['cumplidas'];
        $enproceso[]  = (int)$fila['enproceso'];
        $pendientes[] = (int)$fila['pendientes'];
    }
    header('Content-Type: application/json');
    echo json_encode([
        'ejes'       => $ejes,
        'cumplidas'  => $cumplidas,
        'enproceso'  => $enproceso,
        'pendientes' => $pendientes
    ]);
    exit;
}


/**
 * Presupuesto asignado vs ejercido por eje estratégico.
 */
function DatosGraficaPresupuesto()
{
    global $obj;
    $sql = "SELECT e.nombre_eje,
                   COALESCE(SUM(m.presupuesto_asignado), 0) AS asignado,
                   COALESCE(SUM(m.presupuesto_ejercido), 0) AS ejercido
            FROM ejes e
            LEFT JOIN programas p ON e.id_eje = p.id_eje
            LEFT JOIN metas m ON p.id_programa = m.id_programa
            GROUP BY e.id_eje, e.nombre_eje
            ORDER BY e.id_eje";
    $datos = $obj->mostrardatos($sql);
    $ejes     = [];
    $asignado = [];
    $ejercido = [];
    foreach ($datos as $fila) {
        $ejes[]     = $fila['nombre_eje'];
        $asignado[] = (float)$fila['asignado'];
        $ejercido[] = (float)$fila['ejercido'];
    }
    header('Content-Type: application/json');
    echo json_encode(['ejes' => $ejes, 'asignado' => $asignado, 'ejercido' => $ejercido]);
    exit;
}


/**
 * % de avance promedio por programa (barras horizontales).
 * El avance de cada meta se limita a 100% para que una meta rebasada
 * no distorsione el promedio del programa.
 */
function DatosGraficaAvanceProgramas()
{
    global $obj;
    $sql = "SELECT p.nombre_programa,
                   AVG(LEAST(m.meta_alcanzada / NULLIF(m.meta_programada, 0) * 100, 100)) AS avance
            FROM programas p
            INNER JOIN metas m ON p.id_programa = m.id_programa
            GROUP BY p.id_programa, p.nombre_programa
            HAVING avance IS NOT NULL
            ORDER BY avance DESC";
    $datos = $obj->mostrardatos($sql);
    $programas = [];
    $avances   = [];
    foreach ($datos as $fila) {
        $programas[] = $fila['nombre_programa'];
        $avances[]   = round((float)$fila['avance'], 1);
    }
    header('Content-Type: application/json');
    echo json_encode(['programas' => $programas, 'avances' => $avances]);
    exit;
}


/**
 * Calendario mensual del año en curso: programado vs realizado
 * (suma de todos los indicadores MIR).
 */
function DatosGraficaCalendario()
{
    global $obj;
    $anio = (int)date('Y');
    $sql  = "SELECT mes,
                    SUM(programado) AS programado,
                    SUM(realizado)  AS realizado
             FROM indicador_calendario
             WHERE anio = $anio
             GROUP BY mes
             ORDER BY mes";
    $datos = $obj->mostrardatos($sql);

    // Se rellenan los 12 meses para que la línea no se corte donde no hay captura.
    $programado = array_fill(1, 12, 0);
    $realizado  = array_fill(1, 12, 0);
    foreach ($datos as $fila) {
        $mes = (int)$fila['mes'];
        if ($mes >= 1 && $mes <= 12) {
            $programado[$mes] = (float)$fila['programado'];
            $realizado[$mes]  = (float)$fila['realizado'];
        }
    }
    header('Content-Type: application/json');
    echo json_encode([
        'anio'       => $anio,
        'programado' => array_values($programado),
        'realizado'  => array_values($realizado)
    ]);
    exit;
}


function ProgramasPorEje()
{
    // Devuelve en JSON los programas del eje seleccionado
    $id_eje = (int)($_GET['idr'] ?? 0);
    responderJson(consultarPreparado(
        "SELECT id_programa, nombre_programa FROM programas WHERE id_eje = ? ORDER BY id_programa ASC",
        'i', [$id_eje]
    ));
}


////////////////////// INDICADORES (MIR: Fin / Propósito / Componente / Actividad) //////////////////////

function IndicadoresPrograma()
{
    // Devuelve en JSON el árbol de indicadores de un programa: fin, propósito y componentes con sus actividades
    $id_programa = (int)($_GET['idr'] ?? 0);
    $anio        = anioValido($_GET['anio'] ?? null);

    $indicadores = consultarPreparado(
        "SELECT * FROM indicadores WHERE id_programa = ? AND activo = 1 ORDER BY orden, id_indicador",
        'i', [$id_programa]
    );

    $resultado   = ['fin' => null, 'proposito' => null, 'componentes' => []];
    $actividades = [];   // id_padre => [actividades]
    foreach ($indicadores as $ind) {
        if ($ind['tipo'] === 'fin' && !$resultado['fin'])             $resultado['fin'] = $ind;
        if ($ind['tipo'] === 'proposito' && !$resultado['proposito']) $resultado['proposito'] = $ind;
        if ($ind['tipo'] === 'actividad')                             $actividades[$ind['id_padre']][] = $ind;
    }
    foreach ($indicadores as $ind) {
        if ($ind['tipo'] === 'componente') {
            $ind['actividades'] = $actividades[$ind['id_indicador']] ?? [];
            $resultado['componentes'][] = $ind;
        }
    }

    // Calendario del año de todos los indicadores del programa (una sola consulta),
    // para capturar la evaluación mensual directo en la lista.
    $cal = consultarPreparado(
        "SELECT ic.id_indicador, ic.mes, ic.programado, ic.realizado
         FROM indicador_calendario ic
         INNER JOIN indicadores i ON i.id_indicador = ic.id_indicador
         WHERE i.id_programa = ? AND ic.anio = ?",
        'ii', [$id_programa, $anio]
    );
    $resultado['anio']       = $anio;
    $resultado['calendario'] = [];   // id_indicador => mes => {programado, realizado}
    foreach ($cal as $fila) {
        $resultado['calendario'][$fila['id_indicador']][(int)$fila['mes']] = [
            'programado' => (float)$fila['programado'],
            'realizado'  => (float)$fila['realizado'],
        ];
    }

    responderJson($resultado);
}

// Año entre 2000 y 2100; si no viene o no es válido, el año actual.
function anioValido($valor)
{
    $anio = (int)$valor;
    return ($anio >= 2000 && $anio <= 2100) ? $anio : (int)date('Y');
}


function crudIndicadores()
{
    $ops = $_GET['ops'] ?? '';
    $falla = function ($mensaje) { responderJson(['ok' => false, 'msg' => $mensaje]); };

    if ($ops == 'InsertIndicador') {
        $id_programa   = (int)($_POST['id_programa'] ?? 0);
        $tipo          = $_POST['tipo'] ?? '';
        $id_padre      = !empty($_POST['id_padre']) ? (int)$_POST['id_padre'] : null;
        $descripcion   = trim($_POST['descripcion'] ?? '');
        $unidad_medida = trim($_POST['unidad_medida'] ?? '');

        if (!in_array($tipo, ['fin', 'proposito', 'componente', 'actividad'], true)) $falla('Tipo de indicador no válido.');
        if ($descripcion === '' || $unidad_medida === '') $falla('Descripción y unidad de medida son obligatorias.');
        if (mb_strlen($descripcion) > 2000)   $falla('La descripción admite máximo 2000 caracteres.');
        if (mb_strlen($unidad_medida) > 100)  $falla('La unidad de medida admite máximo 100 caracteres.');
        if (!consultarPreparado("SELECT id_programa FROM programas WHERE id_programa = ?", 'i', [$id_programa])) {
            $falla('El programa ya no existe.');
        }

        if ($tipo == 'fin' || $tipo == 'proposito') {
            $existe = consultarPreparado(
                "SELECT id_indicador FROM indicadores WHERE id_programa = ? AND tipo = ? AND activo = 1",
                'is', [$id_programa, $tipo]
            );
            if ($existe) $falla('Este programa ya tiene un indicador de ese tipo.');
            $id_padre = null;
        }
        if ($tipo == 'componente') {
            $id_padre = null;
        }
        if ($tipo == 'actividad') {
            // La actividad debe colgar de un componente del mismo programa.
            $padre = consultarPreparado(
                "SELECT id_indicador FROM indicadores WHERE id_indicador = ? AND id_programa = ? AND tipo = 'componente'",
                'ii', [(int)$id_padre, $id_programa]
            );
            if (!$padre) $falla('La actividad debe pertenecer a un componente de este programa.');
        }

        ejecutarPreparado(
            "INSERT INTO indicadores (id_programa, tipo, id_padre, descripcion, unidad_medida) VALUES (?, ?, ?, ?, ?)",
            'isiss', [$id_programa, $tipo, $id_padre, $descripcion, $unidad_medida]
        );
        responderJson(['ok' => true]);
    }

    if ($ops == 'DeleteIndicador') {
        // En cascada: su calendario y, si es componente, sus actividades.
        ejecutarPreparado("DELETE FROM indicadores WHERE id_indicador = ?", 'i', [(int)($_GET['idr'] ?? 0)]);
        responderJson(['ok' => true]);
    }
}


function formCalendario()
{
    // Devuelve en JSON los 12 meses (programado/realizado) de un indicador para un año dado
    $id_indicador = (int)($_GET['idr'] ?? 0);
    $anio         = anioValido($_GET['anio'] ?? null);

    $datos = consultarPreparado(
        "SELECT mes, programado, realizado FROM indicador_calendario WHERE id_indicador = ? AND anio = ?",
        'ii', [$id_indicador, $anio]
    );
    $porMes = [];
    foreach ($datos as $d) {
        $porMes[(int)$d['mes']] = ['programado' => $d['programado'], 'realizado' => $d['realizado']];
    }

    $meses = [];
    for ($m = 1; $m <= 12; $m++) {
        $meses[] = [
            'mes'        => $m,
            'programado' => $porMes[$m]['programado'] ?? 0,
            'realizado'  => $porMes[$m]['realizado'] ?? 0,
        ];
    }

    responderJson(['id_indicador' => $id_indicador, 'anio' => $anio, 'meses' => $meses]);
}


function guardarCalendario()
{
    global $obj;
    $id_indicador = (int)($_POST['id_indicador'] ?? 0);
    $anio         = anioValido($_POST['anio'] ?? null);

    if (!consultarPreparado("SELECT id_indicador FROM indicadores WHERE id_indicador = ?", 'i', [$id_indicador])) {
        responderJson(['ok' => false, 'msg' => 'El indicador ya no existe.']);
    }

    // Se validan los 24 valores antes de guardar nada.
    $valores = [];
    for ($m = 1; $m <= 12; $m++) {
        $programado = $_POST['programado_' . $m] ?? 0;
        $realizado  = $_POST['realizado_' . $m] ?? 0;
        if (!is_numeric($programado) || !is_numeric($realizado) || $programado < 0 || $realizado < 0) {
            responderJson(['ok' => false, 'msg' => 'Los valores deben ser números mayores o iguales a cero.']);
        }
        $valores[$m] = [(float)$programado, (float)$realizado];
    }

    // Los 12 meses se guardan juntos o no se guarda ninguno.
    // ON DUPLICATE KEY usa el índice único (id_indicador, anio, mes).
    $conexion = $obj->conexion();
    mysqli_begin_transaction($conexion);
    try {
        foreach ($valores as $m => [$programado, $realizado]) {
            ejecutarPreparado(
                "INSERT INTO indicador_calendario (id_indicador, anio, mes, programado, realizado)
                 VALUES (?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE programado = VALUES(programado), realizado = VALUES(realizado)",
                'iiidd', [$id_indicador, $anio, $m, $programado, $realizado]
            );
        }
        mysqli_commit($conexion);
    } catch (Throwable $error) {
        mysqli_rollback($conexion);
        throw $error;
    }

    responderJson(['ok' => true]);
}


////////////////////// AVANCE DEL PROYECTO (vista pública de solo lectura) //////////////////////

/**
 * Corta la ejecución con un 403 si la clave enviada no coincide con la
 * clave de acceso de avance.php. La usan los dos endpoints de abajo para
 * que nadie pueda leer los datos llamando directo a controlador.php sin
 * conocer la clave.
 */
function validarClaveAvance()
{
    if (!claveAvanceCorrecta($_GET['clave'] ?? '')) {
        http_response_code(403);
        responderJson(['error' => 'Clave de acceso incorrecta.']);
    }
}

function ResumenAvanceProyecto()
{
    validarClaveAvance();
    global $obj;

    $totalEjes = (int)($obj->mostrarunregistro("SELECT COUNT(*) AS total FROM ejes")['total'] ?? 0);

    // Un programa se considera:
    //   Completo    -> tiene al menos una meta capturada y todas están 'Terminado'
    //   En proceso  -> tiene al menos una meta capturada, pero no todas 'Terminado'
    //   Sin iniciar -> todavía no se le ha capturado ninguna meta
    $sql = "SELECT
                COUNT(*) AS total_programas,
                SUM(CASE WHEN metas_totales = 0 THEN 1 ELSE 0 END) AS sin_iniciar,
                SUM(CASE WHEN metas_totales > 0 AND metas_totales = metas_terminadas THEN 1 ELSE 0 END) AS completos,
                SUM(CASE WHEN metas_totales > 0 AND metas_totales > metas_terminadas THEN 1 ELSE 0 END) AS en_proceso
            FROM (
                SELECT p.id_programa,
                       COUNT(m.id_meta) AS metas_totales,
                       SUM(CASE WHEN m.estado = 'Terminado' THEN 1 ELSE 0 END) AS metas_terminadas
                FROM programas p
                LEFT JOIN metas m ON m.id_programa = p.id_programa
                GROUP BY p.id_programa
            ) AS resumen_por_programa";
    $resumen = $obj->mostrarunregistro($sql);

    $ultima = $obj->mostrarunregistro("SELECT MAX(actualizado_en) AS fecha FROM metas")['fecha'] ?? null;

    header('Content-Type: application/json');
    echo json_encode([
        'total_ejes'           => $totalEjes,
        'total_programas'      => (int)($resumen['total_programas'] ?? 0),
        'completos'            => (int)($resumen['completos'] ?? 0),
        'en_proceso'           => (int)($resumen['en_proceso'] ?? 0),
        'sin_iniciar'          => (int)($resumen['sin_iniciar'] ?? 0),
        'ultima_actualizacion' => $ultima,
    ]);
    exit;
}

function DatosAvanceProgramas()
{
    validarClaveAvance();
    global $obj;

    $sql = "SELECT e.nombre_eje, p.nombre_programa,
                   COUNT(m.id_meta) AS metas_totales,
                   SUM(CASE WHEN m.estado = 'Terminado' THEN 1 ELSE 0 END) AS metas_terminadas
            FROM programas p
            INNER JOIN ejes e ON e.id_eje = p.id_eje
            LEFT JOIN metas m ON m.id_programa = p.id_programa
            GROUP BY p.id_programa, e.nombre_eje, p.nombre_programa
            ORDER BY e.id_eje, p.nombre_programa";
    $datos = $obj->mostrardatos($sql);

    $lista = [];
    foreach ($datos as $fila) {
        $totales    = (int)$fila['metas_totales'];
        $terminadas = (int)$fila['metas_terminadas'];

        if ($totales === 0) {
            $estatus = 'Sin iniciar';
        } elseif ($terminadas === $totales) {
            $estatus = 'Completo';
        } else {
            $estatus = 'En proceso';
        }

        $lista[] = [
            'nombre_eje'      => $fila['nombre_eje'],
            'nombre_programa' => $fila['nombre_programa'],
            'estatus'         => $estatus,
        ];
    }

    header('Content-Type: application/json');
    echo json_encode(['data' => $lista]);
    exit;
}
