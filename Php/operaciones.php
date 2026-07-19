<?php

include('vistas.php');
$idopc = $_GET['idopc'];

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

    case 'DatosProgramasPorEje':
        ProgramasPorEje();
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
    $ops = $_GET['ops'];
    $idr = $_GET['idr'];

    if ($ops == "Insert") {
        global $obj;
        $nombre_eje = strtoupper(trim($_POST['nombre_eje']));
        $sql = "INSERT INTO ejes (nombre_eje) VALUES ('$nombre_eje')";
        $obj->guardardatos($sql);
        echo "Eje registrado.";
    }

    if ($ops == "Edit") {
        global $obj;
        $sql       = "SELECT * FROM ejes WHERE id_eje = $idr";
        $resultado = $obj->mostrardatos($sql);
        FormularioEjesActualizacion($resultado);
    }

    if ($ops == "UpdateEje") {
        global $obj;
        $nombre_eje = strtoupper(trim($_POST['nombre_eje']));
        $sql = "UPDATE ejes SET nombre_eje = '$nombre_eje' WHERE id_eje = $idr";
        $obj->actualizadatos($sql);
        echo "Eje actualizado.";
    }

    if ($ops == "DeleteEje") {
        global $obj;
        // Eliminación en cascada: metas de cada programa del eje, luego los programas, luego el eje
        $programas = $obj->mostrardatos("SELECT id_programa FROM programas WHERE id_eje = $idr");
        foreach ($programas as $p) {
            $obj->eliminardatos("DELETE FROM metas WHERE id_programa = " . (int)$p['id_programa']);
        }
        $obj->eliminardatos("DELETE FROM programas WHERE id_eje = $idr");
        $obj->eliminardatos("DELETE FROM ejes WHERE id_eje = $idr");
        MostrarEjes();
    }
}


////////////////////// CRUD PROGRAMAS //////////////////////

function crudProgramas()
{
    $ops = $_GET['ops'];
    $idr = $_GET['idr'];

    if ($ops == "Insert") {
        global $obj;
        $id_eje          = $_POST['id_eje'];
        $nombre_programa = trim($_POST['nombre_programa']);
        $responsable     = trim($_POST['responsable']);
        $descripcion     = trim($_POST['descripcion']);
        $sql = "INSERT INTO programas (id_eje, nombre_programa, responsable, descripcion)
                VALUES ($id_eje, '$nombre_programa', '$responsable', '$descripcion')";
        $obj->guardardatos($sql);
        echo "Programa registrado.";
    }

    if ($ops == "Edit") {
        global $obj;
        $sql       = "SELECT * FROM programas WHERE id_programa = $idr";
        $resultado = $obj->mostrardatos($sql);
        FormularioProgramasActualizacion($resultado);
    }

    if ($ops == "UpdatePrograma") {
        global $obj;
        $id_eje          = $_POST['id_eje'];
        $nombre_programa = trim($_POST['nombre_programa']);
        $responsable     = trim($_POST['responsable']);
        $descripcion     = trim($_POST['descripcion']);
        $sql = "UPDATE programas SET id_eje = $id_eje, nombre_programa = '$nombre_programa',
                responsable = '$responsable', descripcion = '$descripcion'
                WHERE id_programa = $idr";
        $obj->actualizadatos($sql);
        echo "Programa actualizado.";
    }

    if ($ops == "DeletePrograma") {
        global $obj;
        // Eliminación en cascada: primero las metas del programa, luego el programa
        $obj->eliminardatos("DELETE FROM metas WHERE id_programa = $idr");
        $obj->eliminardatos("DELETE FROM programas WHERE id_programa = $idr");
        MostrarProgramas();
    }
}


////////////////////// CRUD METAS //////////////////////

function crudMetas()
{
    $ops = $_GET['ops'];
    $idr = $_GET['idr'];

    if ($ops == "Insert") {
        global $obj;
        $id_programa          = (int)$_POST['id_programa'];
        $actividad            = trim($_POST['actividad']);
        $unidad_medida        = trim($_POST['unidad_medida']);
        $meta_programada      = (float)$_POST['meta_programada'];
        $meta_alcanzada       = (float)$_POST['meta_alcanzada'];
        $fecha_inicio         = $_POST['fecha_inicio'];
        $fecha_limite         = $_POST['fecha_limite'] ?: 'NULL';
        $presupuesto_asignado = (float)($_POST['presupuesto_asignado'] ?? 0);
        $presupuesto_ejercido = (float)($_POST['presupuesto_ejercido'] ?? 0);
        $observaciones        = trim($_POST['observaciones']);
        $id_usuario           = $_SESSION['id_usuario'] ?? 1;

        // Calcular estado automáticamente
        if ($meta_programada > 0) {
            $avance = ($meta_alcanzada / $meta_programada) * 100;
            if ($avance >= 100)  $estado = 'Terminado';
            elseif ($avance > 0) $estado = 'En Proceso';
            else                 $estado = 'Pendiente';
        } else {
            $estado = 'Pendiente';
        }

        $fecha_limite_sql = ($fecha_limite === 'NULL') ? 'NULL' : "'$fecha_limite'";

        $sql = "INSERT INTO metas
                (id_programa, actividad, unidad_medida, meta_programada, meta_alcanzada,
                 presupuesto_asignado, presupuesto_ejercido, observaciones,
                 fecha_inicio, fecha_limite, estado, creado_por)
                VALUES
                ($id_programa, '$actividad', '$unidad_medida', $meta_programada, $meta_alcanzada,
                 $presupuesto_asignado, $presupuesto_ejercido, '$observaciones',
                 '$fecha_inicio', $fecha_limite_sql, '$estado', $id_usuario)";

        $obj->guardardatos($sql);
        echo "Meta registrada.";
    }

    if ($ops == "Edit") {
        global $obj;
        $sql       = "SELECT * FROM metas WHERE id_meta = $idr";
        $resultado = $obj->mostrardatos($sql);
        FormularioMetasActualizacion($resultado);
    }

    if ($ops == "UpdateMeta") {
        global $obj;
        $id_programa          = (int)$_POST['id_programa'];
        $actividad            = trim($_POST['actividad']);
        $unidad_medida        = trim($_POST['unidad_medida']);
        $meta_programada      = (float)$_POST['meta_programada'];
        $meta_alcanzada       = (float)$_POST['meta_alcanzada'];
        $fecha_inicio         = $_POST['fecha_inicio'];
        $fecha_limite         = $_POST['fecha_limite'] ?: null;
        $presupuesto_asignado = (float)($_POST['presupuesto_asignado'] ?? 0);
        $presupuesto_ejercido = (float)($_POST['presupuesto_ejercido'] ?? 0);
        $observaciones        = trim($_POST['observaciones']);

        if ($meta_programada > 0) {
            $avance = ($meta_alcanzada / $meta_programada) * 100;
            if ($avance >= 100)  $estado = 'Terminado';
            elseif ($avance > 0) $estado = 'En Proceso';
            else                 $estado = 'Pendiente';
        } else {
            $estado = 'Pendiente';
        }

        $fecha_limite_sql = $fecha_limite ? "'$fecha_limite'" : 'NULL';

        $sql = "UPDATE metas SET
                id_programa = $id_programa, actividad = '$actividad',
                unidad_medida = '$unidad_medida', meta_programada = $meta_programada,
                meta_alcanzada = $meta_alcanzada, presupuesto_asignado = $presupuesto_asignado,
                presupuesto_ejercido = $presupuesto_ejercido, observaciones = '$observaciones',
                fecha_inicio = '$fecha_inicio', fecha_limite = $fecha_limite_sql, estado = '$estado'
                WHERE id_meta = $idr";

        $obj->actualizadatos($sql);
        echo "Meta actualizada.";
    }

    if ($ops == "DeleteMeta") {
        global $obj;
        $sql = "DELETE FROM metas WHERE id_meta = $idr";
        $obj->eliminardatos($sql);
        MostrarMetas();
    }
}


////////////////////// CRUD USUARIOS //////////////////////

function crudUsuarios()
{
    $ops = $_GET['ops'];
    $idr = $_GET['idr'];

    if ($ops == "Insert") {
        global $obj;
        $nombre    = trim($_POST['nombre']);
        $usuario   = trim($_POST['usuario']);
        $password  = password_hash(trim($_POST['contrasena']), PASSWORD_BCRYPT);
        $rol       = $_POST['rol'];
        $sql = "INSERT INTO usuarios (nombre, usuario, contrasena, rol)
                VALUES ('$nombre', '$usuario', '$password', '$rol')";
        $obj->guardardatos($sql);
        echo "Usuario registrado.";
    }

    if ($ops == "Edit") {
        global $obj;
        $sql       = "SELECT * FROM usuarios WHERE id_usuario = $idr";
        $resultado = $obj->mostrardatos($sql);
        FormularioUsuariosActualizacion($resultado);
    }

    if ($ops == "UpdateUsuario") {
        global $obj;
        $nombre   = trim($_POST['nombre']);
        $usuario  = trim($_POST['usuario']);
        $rol      = $_POST['rol'];
        $activo   = (int)$_POST['activo'];
        $password = trim($_POST['contrasena']);

        if ($password !== '') {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $sql  = "UPDATE usuarios SET nombre = '$nombre', usuario = '$usuario',
                     contrasena = '$hash', rol = '$rol', activo = $activo
                     WHERE id_usuario = $idr";
        } else {
            $sql  = "UPDATE usuarios SET nombre = '$nombre', usuario = '$usuario',
                     rol = '$rol', activo = $activo WHERE id_usuario = $idr";
        }
        $obj->actualizadatos($sql);
        echo "Usuario actualizado.";
    }

    if ($ops == "DeleteUsuario") {
        global $obj;
        $sql = "DELETE FROM usuarios WHERE id_usuario = $idr";
        $obj->eliminardatos($sql);
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
    ob_clean();
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
                   SUM(CASE WHEN m.estado = 'Terminado' THEN 1 ELSE 0 END) AS cumplidas,
                   SUM(CASE WHEN m.estado = 'Pendiente' THEN 1 ELSE 0 END) AS pendientes
            FROM ejes e
            LEFT JOIN programas p ON e.id_eje = p.id_eje
            LEFT JOIN metas m ON p.id_programa = m.id_programa
            GROUP BY e.id_eje, e.nombre_eje
            ORDER BY e.id_eje";
    $datos = $obj->mostrardatos($sql);
    $ejes      = [];
    $cumplidas = [];
    $pendientes = [];
    foreach ($datos as $fila) {
        $ejes[]       = $fila['nombre_eje'];
        $cumplidas[]  = (int)$fila['cumplidas'];
        $pendientes[] = (int)$fila['pendientes'];
    }
    echo json_encode(['ejes' => $ejes, 'cumplidas' => $cumplidas, 'pendientes' => $pendientes]);
    exit;
}


function ProgramasPorEje()
{
    // Devuelve en JSON los programas del eje seleccionado
    global $obj;
    $id_eje = (int)$_GET['idr'];
    $sql    = "SELECT id_programa, nombre_programa FROM programas WHERE id_eje = $id_eje ORDER BY id_programa ASC";
    $datos  = $obj->mostrardatos($sql);
    header('Content-Type: application/json');
    echo json_encode($datos);
    exit;
}


////////////////////// INDICADORES (MIR: Fin / Propósito / Componente / Actividad) //////////////////////

function IndicadoresPrograma()
{
    // Devuelve en JSON el árbol de indicadores de un programa: fin, propósito y componentes con sus actividades
    global $obj;
    $id_programa = (int)$_GET['idr'];

    $resultado = ['fin' => null, 'proposito' => null, 'componentes' => []];

    $fin = $obj->mostrardatos("SELECT * FROM indicadores WHERE id_programa = $id_programa AND tipo = 'fin' AND activo = 1 LIMIT 1");
    if (!empty($fin)) $resultado['fin'] = $fin[0];

    $proposito = $obj->mostrardatos("SELECT * FROM indicadores WHERE id_programa = $id_programa AND tipo = 'proposito' AND activo = 1 LIMIT 1");
    if (!empty($proposito)) $resultado['proposito'] = $proposito[0];

    $componentes = $obj->mostrardatos("SELECT * FROM indicadores WHERE id_programa = $id_programa AND tipo = 'componente' AND activo = 1 ORDER BY orden, id_indicador");
    foreach ($componentes as $c) {
        $c['actividades'] = $obj->mostrardatos("SELECT * FROM indicadores WHERE id_padre = {$c['id_indicador']} AND tipo = 'actividad' AND activo = 1 ORDER BY orden, id_indicador");
        $resultado['componentes'][] = $c;
    }

    header('Content-Type: application/json');
    echo json_encode($resultado);
    exit;
}


function crudIndicadores()
{
    global $obj;
    $ops = $_GET['ops'];
    header('Content-Type: application/json');

    if ($ops == 'InsertIndicador') {
        $id_programa   = (int)$_POST['id_programa'];
        $tipo          = $_POST['tipo'];
        $id_padre      = !empty($_POST['id_padre']) ? (int)$_POST['id_padre'] : null;
        $descripcion   = trim($_POST['descripcion']);
        $unidad_medida = trim($_POST['unidad_medida']);

        $tipos_validos = ['fin', 'proposito', 'componente', 'actividad'];
        if (!in_array($tipo, $tipos_validos) || $descripcion === '' || $unidad_medida === '') {
            echo json_encode(['ok' => false, 'msg' => 'Datos inválidos.']);
            exit;
        }

        if ($tipo == 'fin' || $tipo == 'proposito') {
            $existe = $obj->mostrardatos("SELECT id_indicador FROM indicadores WHERE id_programa = $id_programa AND tipo = '$tipo' AND activo = 1");
            if (!empty($existe)) {
                echo json_encode(['ok' => false, 'msg' => 'Este programa ya tiene un indicador de ese tipo.']);
                exit;
            }
        }

        $padreSql = $id_padre ? $id_padre : 'NULL';
        $sql = "INSERT INTO indicadores (id_programa, tipo, id_padre, descripcion, unidad_medida)
                VALUES ($id_programa, '$tipo', $padreSql, '$descripcion', '$unidad_medida')";
        $obj->guardardatos($sql);
        echo json_encode(['ok' => true]);
        exit;
    }

    if ($ops == 'DeleteIndicador') {
        $idr = (int)$_GET['idr'];
        $obj->eliminardatos("DELETE FROM indicadores WHERE id_indicador = $idr");
        echo json_encode(['ok' => true]);
        exit;
    }
}


function formCalendario()
{
    // Devuelve en JSON los 12 meses (programado/realizado) de un indicador para un año dado
    global $obj;
    $id_indicador = (int)$_GET['idr'];
    $anio = isset($_GET['anio']) ? (int)$_GET['anio'] : (int)date('Y');

    $datos = $obj->mostrardatos("SELECT mes, programado, realizado FROM indicador_calendario WHERE id_indicador = $id_indicador AND anio = $anio");
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

    header('Content-Type: application/json');
    echo json_encode(['id_indicador' => $id_indicador, 'anio' => $anio, 'meses' => $meses]);
    exit;
}


function guardarCalendario()
{
    global $obj;
    $id_indicador = (int)$_POST['id_indicador'];
    $anio         = (int)$_POST['anio'];

    for ($m = 1; $m <= 12; $m++) {
        $programado = isset($_POST['programado_' . $m]) ? (float)$_POST['programado_' . $m] : 0;
        $realizado  = isset($_POST['realizado_' . $m])  ? (float)$_POST['realizado_' . $m]  : 0;

        $existe = $obj->mostrardatos("SELECT id_calendario FROM indicador_calendario WHERE id_indicador = $id_indicador AND anio = $anio AND mes = $m");
        if (!empty($existe)) {
            $obj->actualizadatos("UPDATE indicador_calendario SET programado = $programado, realizado = $realizado
                                   WHERE id_indicador = $id_indicador AND anio = $anio AND mes = $m");
        } else {
            $obj->guardardatos("INSERT INTO indicador_calendario (id_indicador, anio, mes, programado, realizado)
                                 VALUES ($id_indicador, $anio, $m, $programado, $realizado)");
        }
    }

    header('Content-Type: application/json');
    echo json_encode(['ok' => true]);
    exit;
}
