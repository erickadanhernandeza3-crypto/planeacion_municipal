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

    case 'FichaIndicador':
        FichaIndicador();
        break;

    case 'GuardarFichaIndicador':
        GuardarFichaIndicador();
        break;

    case 'FichaTecnicaIndicador':
        FichaTecnicaIndicador();
        break;

    case 'MatrizMIR':
        MatrizMIR();
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
        $archivos = evidenciasPorBorrar('p.id_eje = ?', 'i', [$idr]);
        ejecutarPreparado("DELETE FROM ejes WHERE id_eje = ?", 'i', [$idr]);
        borrarArchivosEvidencias($archivos);   // fotos y PDF en Cloudinary
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
        $archivos = evidenciasPorBorrar('p.id_programa = ?', 'i', [$idr]);
        ejecutarPreparado("DELETE FROM programas WHERE id_programa = ?", 'i', [$idr]);
        borrarArchivosEvidencias($archivos);
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
        // En cascada: sus avances, observaciones y evidencias.
        $archivos = evidenciasPorBorrar('m.id_meta = ?', 'i', [$idr]);
        ejecutarPreparado("DELETE FROM metas WHERE id_meta = ?", 'i', [$idr]);
        borrarArchivosEvidencias($archivos);
        MostrarMetas();
    }
}


////////////////////// CRUD USUARIOS //////////////////////

// Valida y devuelve nombre, usuario, rol y dependencia del formulario de usuarios.
// La dependencia solo se guarda para los enlaces (para los demás roles queda vacía).
function datosFormularioUsuario()
{
    $nombre  = textoRequerido($_POST['nombre'] ?? '', 'Nombre completo', 100);
    $usuario = textoRequerido($_POST['usuario'] ?? '', 'Usuario', 50);
    if (!preg_match('/^[A-Za-z0-9._-]{3,50}$/', $usuario)) {
        rechazar('El usuario debe tener de 3 a 50 caracteres: letras sin acento, números, punto, guion o guion bajo.');
    }
    $rol = $_POST['rol'] ?? '';
    if (!in_array($rol, ROLES_VALIDOS, true)) rechazar('Selecciona un rol válido.');

    $dependencia = null;
    if ($rol === 'enlace') {
        $dependencia = trim($_POST['dependencia'] ?? '');
        if (!in_array($dependencia, listaDependencias(), true)) {
            rechazar('Selecciona la dependencia del enlace (debe ser el Área responsable de algún programa).');
        }
    }
    return [$nombre, $usuario, $rol, $dependencia];
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
        [$nombre, $usuario, $rol, $dependencia] = datosFormularioUsuario();
        $password = trim((string)($_POST['contrasena'] ?? ''));   // igual que el login, que también recorta espacios
        validarContrasenaNueva($password);
        try {
            ejecutarPreparado(
                "INSERT INTO usuarios (nombre, usuario, contrasena, rol, dependencia) VALUES (?, ?, ?, ?, ?)",
                'sssss', [$nombre, $usuario, password_hash($password, PASSWORD_BCRYPT), $rol, $dependencia]
            );
        } catch (mysqli_sql_exception $error) {
            if (esDuplicado($error)) rechazar('Ese nombre de usuario ya está en uso. Elige otro.');
            throw $error;
        }
        echo "Usuario registrado.";
    }

    if ($ops == "Edit") {
        $resultado = consultarPreparado("SELECT id_usuario, nombre, usuario, rol, dependencia, activo FROM usuarios WHERE id_usuario = ?", 'i', [$idr]);
        if (!$resultado) rechazar('El usuario ya no existe.', 404);
        FormularioUsuariosActualizacion($resultado);
    }

    if ($ops == "UpdateUsuario") {
        [$nombre, $usuario, $rol, $dependencia] = datosFormularioUsuario();
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
                    "UPDATE usuarios SET nombre = ?, usuario = ?, contrasena = ?, rol = ?, dependencia = ?, activo = ? WHERE id_usuario = ?",
                    'sssssii', [$nombre, $usuario, password_hash($password, PASSWORD_BCRYPT), $rol, $dependencia, $activo, $idr]
                );
            } else {
                ejecutarPreparado(
                    "UPDATE usuarios SET nombre = ?, usuario = ?, rol = ?, dependencia = ?, activo = ? WHERE id_usuario = ?",
                    'ssssii', [$nombre, $usuario, $rol, $dependencia, $activo, $idr]
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
            'dependencia' => $columna['dependencia'] ?? '',
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


////////////////////// FICHA TÉCNICA DE INDICADORES MIR //////////////////////
// Campos de la Metodología de Marco Lógico que pide el Presupuesto basado en
// Resultados (PbR). Las columnas las crea Php/migraciones.php (migración 3).

function indicadorConPrograma($id_indicador)
{
    $filas = consultarPreparado(
        "SELECT i.*, p.nombre_programa, p.responsable, e.nombre_eje
         FROM indicadores i
         INNER JOIN programas p ON i.id_programa = p.id_programa
         INNER JOIN ejes e      ON p.id_eje = e.id_eje
         WHERE i.id_indicador = ?",
        'i', [(int)$id_indicador]
    );
    return $filas[0] ?? null;
}

// Datos de la ficha para el formulario de edición (JSON).
function FichaIndicador()
{
    $ind = indicadorConPrograma($_GET['idr'] ?? 0);
    if (!$ind) responderJson(['ok' => false, 'msg' => 'El indicador ya no existe.']);
    $ind['nivel'] = MIR_NIVELES[$ind['tipo']] ?? $ind['tipo'];
    responderJson(['ok' => true, 'indicador' => $ind,
                   'opciones' => ['dimension' => MIR_DIMENSIONES, 'sentido' => MIR_SENTIDOS, 'frecuencia' => MIR_FRECUENCIAS]]);
}

function GuardarFichaIndicador()
{
    $id    = (int)($_POST['id_indicador'] ?? 0);
    $falla = function ($mensaje) { responderJson(['ok' => false, 'msg' => $mensaje]); };
    if (!indicadorConPrograma($id)) $falla('El indicador ya no existe.');

    $texto = function ($campo, $nombre, $maximo, $obligatorio = false) use ($falla) {
        $valor = trim((string)($_POST[$campo] ?? ''));
        if ($valor === '' && $obligatorio) $falla("El campo \"$nombre\" es obligatorio.");
        if (mb_strlen($valor) > $maximo)   $falla("El campo \"$nombre\" admite máximo $maximo caracteres.");
        return $valor === '' ? null : $valor;
    };
    $opcion = function ($campo, $nombre, array $validas) use ($falla) {
        $valor = trim((string)($_POST[$campo] ?? ''));
        if ($valor === '') return null;
        if (!in_array($valor, $validas, true)) $falla("Selecciona un valor válido en \"$nombre\".");
        return $valor;
    };
    $numero = function ($campo, $nombre) use ($falla) {
        $valor = trim((string)($_POST[$campo] ?? ''));
        if ($valor === '') return null;
        if (!is_numeric($valor)) $falla("\"$nombre\" debe ser un número.");
        return (float)$valor;
    };

    $descripcion   = $texto('descripcion', 'Resumen narrativo', 2000, true);
    $unidad        = $texto('unidad_medida', 'Unidad de medida', 100, true);
    $nombre        = $texto('nombre_indicador', 'Nombre del indicador', 255);
    $formula       = $texto('formula', 'Fórmula de cálculo', 2000);
    $dimension     = $opcion('dimension', 'Dimensión', MIR_DIMENSIONES);
    $sentido       = $opcion('sentido', 'Sentido', MIR_SENTIDOS);
    $frecuencia    = $opcion('frecuencia', 'Frecuencia de medición', MIR_FRECUENCIAS);
    $lineaBase     = $numero('linea_base', 'Línea base');
    $metaAnual     = $numero('meta_anual', 'Meta anual');
    $medios        = $texto('medios_verificacion', 'Medios de verificación', 2000);
    $supuestos     = $texto('supuestos', 'Supuestos', 2000);
    $anioBase      = trim((string)($_POST['anio_linea_base'] ?? ''));
    if ($anioBase !== '' && (!ctype_digit($anioBase) || (int)$anioBase < 2000 || (int)$anioBase > 2100)) {
        $falla('El año de la línea base debe estar entre 2000 y 2100.');
    }
    $anioBase = $anioBase === '' ? null : (int)$anioBase;

    ejecutarPreparado(
        "UPDATE indicadores SET descripcion = ?, unidad_medida = ?, nombre_indicador = ?, formula = ?, dimension = ?,
                sentido = ?, frecuencia = ?, linea_base = ?, anio_linea_base = ?, meta_anual = ?,
                medios_verificacion = ?, supuestos = ?
         WHERE id_indicador = ?",
        'sssssssdidssi',
        [$descripcion, $unidad, $nombre, $formula, $dimension, $sentido, $frecuencia, $lineaBase, $anioBase,
         $metaAnual, $medios, $supuestos, $id]
    );
    try {
        registrarBitacora('Editar ficha técnica', 'Indicador #' . $id . ': ' . mb_substr($nombre ?: $descripcion, 0, 120));
    } catch (Throwable $error) {
    }
    responderJson(['ok' => true]);
}

/*
 * % de cumplimiento del calendario según el sentido del indicador:
 *  - Ascendente  (más es mejor, ej. personas atendidas): realizado / programado.
 *  - Descendente (menos es mejor, ej. accidentes):       programado / realizado.
 * Semáforo usual de las evaluaciones PbR: verde >= 90%, amarillo 70-89%, rojo < 70%.
 */
function cumplimientoIndicador($programado, $realizado, $sentido)
{
    if ($programado <= 0) return [null, '#adb5bd', 'Sin programación'];
    if ($sentido === 'Descendente') {
        $pct = $realizado > 0 ? $programado / $realizado * 100 : 100;
    } else {
        $pct = $realizado / $programado * 100;
    }
    $pct = round($pct, 1);
    if ($pct >= 90) return [$pct, '#1a7f4b', 'Aceptable'];
    if ($pct >= 70) return [$pct, '#b7791f', 'Con riesgo'];
    return [$pct, '#b42318', 'Crítico'];
}

function calendarioIndicador($id_indicador, $anio)
{
    $porMes = array_fill(1, 12, ['programado' => 0.0, 'realizado' => 0.0]);
    $filas  = consultarPreparado(
        "SELECT mes, programado, realizado FROM indicador_calendario WHERE id_indicador = ? AND anio = ?",
        'ii', [(int)$id_indicador, (int)$anio]
    );
    foreach ($filas as $f) {
        $porMes[(int)$f['mes']] = ['programado' => (float)$f['programado'], 'realizado' => (float)$f['realizado']];
    }
    return $porMes;
}

// Encabezado y estilos comunes de las hojas imprimibles de la MIR.
function inicioHojaMir($titulo)
{
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($titulo) ?> — Tamazunchale</title>
  <style>
    body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 12px; color: #1f2937; background: #fff; margin: 0; padding: 24px; }
    .hoja { max-width: 1000px; margin: 0 auto; }
    .encabezado { display: flex; justify-content: space-between; align-items: flex-end; gap: 12px; border-bottom: 4px solid #D4AF37; padding-bottom: 10px; margin-bottom: 16px; }
    .encabezado h1 { color: #0A2647; font-size: 18px; margin: 0; }
    .encabezado p { margin: 2px 0 0; color: #4b5563; }
    .acciones { text-align: right; }
    .acciones button { background: #0A2647; color: #fff; border: 0; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-size: 13px; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
    th, td { border: 1px solid #c9d1dc; padding: 6px 8px; vertical-align: top; text-align: left; }
    th { background: #e8eef7; color: #0A2647; font-weight: 600; }
    .seccion { background: #0A2647; color: #fff; font-weight: 700; padding: 6px 8px; margin: 18px 0 0; }
    .etiqueta { width: 28%; background: #f4f6fa; font-weight: 600; color: #374151; }
    .num { text-align: center; font-variant-numeric: tabular-nums; }
    .vacio { color: #9ca3af; font-style: italic; }
    .semaforo { display: inline-block; width: 12px; height: 12px; border-radius: 50%; vertical-align: -1px; margin-right: 6px; }
    .firmas { display: grid; grid-template-columns: repeat(3, 1fr); gap: 30px; margin-top: 70px; text-align: center; }
    .firmas div { border-top: 1px solid #1f2937; padding-top: 6px; }
    @media print { body { padding: 0; } .acciones { display: none; } .seccion, th { -webkit-print-color-adjust: exact; print-color-adjust: exact; } }
  </style>
</head>
<body>
<div class="hoja">
<?php
}

// Muestra un valor o "Sin capturar".
function valorMir($valor)
{
    return ($valor === null || $valor === '') ? '<span class="vacio">Sin capturar</span>' : nl2br(e($valor));
}

// Hoja imprimible con la ficha técnica de un indicador (se abre en otra pestaña).
function FichaTecnicaIndicador()
{
    $ind = indicadorConPrograma($_GET['idr'] ?? 0);
    if (!$ind) { echo 'El indicador ya no existe.'; return; }
    $anio   = anioValido($_GET['anio'] ?? null);
    $meses  = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
    $cal    = calendarioIndicador($ind['id_indicador'], $anio);
    $totalP = array_sum(array_column($cal, 'programado'));
    $totalR = array_sum(array_column($cal, 'realizado'));
    [$pct, $color, $leyenda] = cumplimientoIndicador($totalP, $totalR, $ind['sentido']);
    $avanceMeta = ($ind['meta_anual'] !== null && (float)$ind['meta_anual'] > 0) ? round($totalR / (float)$ind['meta_anual'] * 100, 1) : null;

    inicioHojaMir('Ficha técnica del indicador');
?>
    <div class="encabezado">
        <div>
            <h1>Ficha Técnica del Indicador</h1>
            <p>H. Ayuntamiento de Tamazunchale, S.L.P. · Ejercicio <?= $anio ?></p>
        </div>
        <div class="acciones"><button onclick="window.print()">Imprimir / Guardar PDF</button></div>
    </div>

    <div class="seccion">1. Datos de identificación</div>
    <table>
        <tr><td class="etiqueta">Eje estratégico</td><td><?= e($ind['nombre_eje']) ?></td></tr>
        <tr><td class="etiqueta">Programa</td><td><?= e($ind['nombre_programa']) ?></td></tr>
        <tr><td class="etiqueta">Área responsable</td><td><?= valorMir($ind['responsable']) ?></td></tr>
        <tr><td class="etiqueta">Nivel de la MIR</td><td><?= e(MIR_NIVELES[$ind['tipo']] ?? $ind['tipo']) ?></td></tr>
        <tr><td class="etiqueta">Resumen narrativo (objetivo)</td><td><?= valorMir($ind['descripcion']) ?></td></tr>
    </table>

    <div class="seccion">2. Características del indicador</div>
    <table>
        <tr><td class="etiqueta">Nombre del indicador</td><td><?= valorMir($ind['nombre_indicador']) ?></td></tr>
        <tr><td class="etiqueta">Fórmula de cálculo</td><td><?= valorMir($ind['formula']) ?></td></tr>
        <tr><td class="etiqueta">Unidad de medida</td><td><?= valorMir($ind['unidad_medida']) ?></td></tr>
        <tr><td class="etiqueta">Dimensión</td><td><?= valorMir($ind['dimension']) ?></td></tr>
        <tr><td class="etiqueta">Sentido</td><td><?= valorMir($ind['sentido']) ?></td></tr>
        <tr><td class="etiqueta">Frecuencia de medición</td><td><?= valorMir($ind['frecuencia']) ?></td></tr>
    </table>

    <div class="seccion">3. Línea base y meta</div>
    <table>
        <tr><th>Línea base</th><th>Año de la línea base</th><th>Meta anual <?= $anio ?></th><th>Realizado acumulado</th><th>Avance de la meta</th></tr>
        <tr>
            <td class="num"><?= $ind['linea_base'] !== null ? formatoNumero($ind['linea_base']) : '<span class="vacio">—</span>' ?></td>
            <td class="num"><?= $ind['anio_linea_base'] ?: '<span class="vacio">—</span>' ?></td>
            <td class="num"><?= $ind['meta_anual'] !== null ? formatoNumero($ind['meta_anual']) : '<span class="vacio">—</span>' ?></td>
            <td class="num"><?= formatoNumero($totalR) ?></td>
            <td class="num"><?= $avanceMeta !== null ? $avanceMeta . '%' : '<span class="vacio">—</span>' ?></td>
        </tr>
    </table>

    <div class="seccion">4. Calendario <?= $anio ?> y cumplimiento</div>
    <table>
        <tr><th></th><?php foreach ($meses as $m): ?><th class="num"><?= $m ?></th><?php endforeach; ?><th class="num">Total</th></tr>
        <tr><td class="etiqueta">Programado</td><?php foreach ($cal as $c): ?><td class="num"><?= formatoNumero($c['programado']) ?></td><?php endforeach; ?><td class="num"><strong><?= formatoNumero($totalP) ?></strong></td></tr>
        <tr><td class="etiqueta">Realizado</td><?php foreach ($cal as $c): ?><td class="num"><?= formatoNumero($c['realizado']) ?></td><?php endforeach; ?><td class="num"><strong><?= formatoNumero($totalR) ?></strong></td></tr>
    </table>
    <p>
        <span class="semaforo" style="background:<?= $color ?>;"></span>
        <strong>Cumplimiento del calendario: <?= $pct !== null ? $pct . '%' : '—' ?></strong> (<?= e($leyenda) ?>).
        Parámetros: verde 90% o más, amarillo de 70% a 89%, rojo menos de 70%<?= $ind['sentido'] === 'Descendente' ? '; al ser descendente se calcula programado ÷ realizado' : '' ?>.
    </p>

    <div class="seccion">5. Verificación</div>
    <table>
        <tr><td class="etiqueta">Medios de verificación</td><td><?= valorMir($ind['medios_verificacion']) ?></td></tr>
        <tr><td class="etiqueta">Supuestos</td><td><?= valorMir($ind['supuestos']) ?></td></tr>
    </table>

    <div class="firmas"><div>Elaboró<br><small>Área responsable</small></div><div>Revisó<br><small>Dirección de Planeación</small></div><div>Autorizó<br><small>Presidencia Municipal</small></div></div>
</div>
</body>
</html>
<?php
}

// Hoja imprimible con la Matriz de Indicadores para Resultados de un programa.
function MatrizMIR()
{
    $id_programa = (int)($_GET['idr'] ?? 0);
    $programa = consultarPreparado(
        "SELECT p.*, e.nombre_eje FROM programas p INNER JOIN ejes e ON p.id_eje = e.id_eje WHERE p.id_programa = ?",
        'i', [$id_programa]
    );
    if (!$programa) { echo 'El programa ya no existe.'; return; }
    $programa = $programa[0];

    $indicadores = consultarPreparado(
        "SELECT * FROM indicadores WHERE id_programa = ? AND activo = 1 ORDER BY orden, id_indicador",
        'i', [$id_programa]
    );
    // Orden oficial: Fin, Propósito y cada Componente seguido de sus Actividades.
    $filas = [];
    foreach (['fin', 'proposito'] as $nivel) {
        foreach ($indicadores as $i) if ($i['tipo'] === $nivel) $filas[] = [MIR_NIVELES[$nivel], $i];
    }
    $nC = 0;
    foreach ($indicadores as $c) {
        if ($c['tipo'] !== 'componente') continue;
        $nC++;
        $filas[] = ['Componente ' . $nC, $c];
        $nA = 0;
        foreach ($indicadores as $a) {
            if ($a['tipo'] === 'actividad' && (int)$a['id_padre'] === (int)$c['id_indicador']) {
                $nA++;
                $filas[] = ['Actividad ' . $nC . '.' . $nA, $a];
            }
        }
    }

    inicioHojaMir('Matriz de Indicadores para Resultados');
?>
    <div class="encabezado">
        <div>
            <h1>Matriz de Indicadores para Resultados (MIR)</h1>
            <p><?= e($programa['nombre_eje']) ?> · Programa: <strong><?= e($programa['nombre_programa']) ?></strong>
               <?= $programa['responsable'] ? ' · ' . e($programa['responsable']) : '' ?></p>
        </div>
        <div class="acciones"><button onclick="window.print()">Imprimir / Guardar PDF</button></div>
    </div>
    <?php if (!$filas): ?>
        <p class="vacio">Este programa todavía no tiene indicadores. Agrégalos en "Indicadores (MIR)".</p>
    <?php else: ?>
    <table>
        <tr>
            <th style="width:11%;">Nivel</th><th style="width:22%;">Resumen narrativo</th><th style="width:18%;">Indicador</th>
            <th style="width:17%;">Fórmula</th><th style="width:8%;">Frecuencia</th><th style="width:12%;">Medios de verificación</th><th style="width:12%;">Supuestos</th>
        </tr>
        <?php foreach ($filas as [$nivel, $i]): ?>
            <tr>
                <td><strong><?= e($nivel) ?></strong></td>
                <td><?= valorMir($i['descripcion']) ?></td>
                <td><?= valorMir($i['nombre_indicador']) ?><?= $i['unidad_medida'] ? '<br><small>Unidad: ' . e($i['unidad_medida']) . '</small>' : '' ?></td>
                <td><?= valorMir($i['formula']) ?></td>
                <td><?= valorMir($i['frecuencia']) ?></td>
                <td><?= valorMir($i['medios_verificacion']) ?></td>
                <td><?= valorMir($i['supuestos']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>
    <div class="firmas"><div>Elaboró<br><small>Área responsable</small></div><div>Revisó<br><small>Dirección de Planeación</small></div><div>Autorizó<br><small>Presidencia Municipal</small></div></div>
</div>
</body>
</html>
<?php
}
