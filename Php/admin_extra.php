<?php
/*
 * MÓDULOS NUEVOS DEL PANEL DE ADMINISTRADOR
 * ---------------------------------------------------------------
 * 1. Dashboard: conteos reales, metas por estado, avance por eje,
 *    semáforo general y últimas 10 actividades.
 * 2. Visor de bitácora con filtros (usuario, acción, fechas).
 * 3. Comparativo trimestral.
 *
 * Lo incluye controlador.php solo para los idopc que empiezan con "Admin".
 * Todo es de solo lectura. Consultas con sentencias preparadas.
 */

require_once __DIR__ . '/comunes_extra.php';

$idopc = $_GET['idopc'] ?? '';

// Ninguna opción de este archivo se ejecuta si no hay sesión de administrador.
verificarRol('administrador');

switch ($idopc) {
    case 'AdminResumen':        ResumenDashboardAdmin();  break;
    case 'AdminBitacora':       VistaBitacora();          break;
    case 'AdminDatosBitacora':  DatosBitacora();          break;
    case 'AdminTrimestral':     VistaTrimestral('AdminDatosTrimestral'); break;
    case 'AdminDatosTrimestral': responderJson(datosTrimestrales((int)($_GET['anio'] ?? date('Y')))); break;
}


////////////////////// 1. DASHBOARD //////////////////////

function ResumenDashboardAdmin()
{
    // Conteos de las tarjetas
    $conteos = consultarPreparado(
        "SELECT (SELECT COUNT(*) FROM usuarios WHERE activo = 1) AS usuarios,
                (SELECT COUNT(*) FROM ejes)      AS ejes,
                (SELECT COUNT(*) FROM programas) AS programas,
                (SELECT COUNT(*) FROM metas)     AS metas"
    )[0];

    // Metas por estado y vencidas
    $estados = consultarPreparado(
        "SELECT COUNT(*) AS total,
                COALESCE(SUM(estado = 'Terminado'), 0)  AS terminadas,
                COALESCE(SUM(estado = 'En Proceso'), 0) AS en_proceso,
                COALESCE(SUM(estado = 'Pendiente'), 0)  AS pendientes,
                COALESCE(SUM(fecha_limite < CURDATE() AND estado <> 'Terminado'), 0) AS vencidas
         FROM metas"
    )[0];

    // % de avance por eje: promedio por meta de LEAST(alcanzada / programada, 1) * 100.
    // Se promedian porcentajes; nunca se suman unidades distintas.
    $avanceEjes = consultarPreparado(
        "SELECT e.nombre_eje,
                COUNT(m.id_meta) AS metas,
                ROUND(COALESCE(AVG(LEAST(m.meta_alcanzada / NULLIF(m.meta_programada, 0), 1) * 100), 0), 1) AS avance
         FROM ejes e
         LEFT JOIN programas p ON p.id_eje = e.id_eje
         LEFT JOIN metas m     ON m.id_programa = p.id_programa
         GROUP BY e.id_eje, e.nombre_eje
         ORDER BY e.id_eje"
    );

    // Semáforo general (misma regla que el panel de planeación)
    $metas = consultarPreparado(
        "SELECT m.meta_programada, m.meta_alcanzada, m.fecha_inicio, m.fecha_limite, " . columnasDias() . " FROM metas m"
    );
    $semaforo = ['Verde' => 0, 'Amarillo' => 0, 'Rojo' => 0, 'Sin fecha' => 0];
    foreach ($metas as $m) {
        $semaforo[calcularSemaforo($m)['color']]++;
    }

    // Últimas 10 actividades (si la tabla bitacora no existe, la lista queda vacía)
    try {
        $actividades = consultarPreparado(
            "SELECT b.fecha, b.accion, b.descripcion, COALESCE(u.nombre, 'Sistema / desconocido') AS nombre
             FROM bitacora b
             LEFT JOIN usuarios u ON b.id_usuario = u.id_usuario
             ORDER BY b.id_bitacora DESC
             LIMIT 10"
        );
    } catch (Throwable $error) {
        $actividades = [];
    }
    foreach ($actividades as &$a) {
        $a['fecha'] = date('d/m/Y H:i', strtotime($a['fecha']));
    }
    unset($a);

    responderJson([
        'conteos'     => array_map('intval', $conteos),
        'estados'     => array_map('intval', $estados),
        'avance_ejes' => [
            'nombres' => array_column($avanceEjes, 'nombre_eje'),
            'avance'  => array_map('floatval', array_column($avanceEjes, 'avance')),
            'metas'   => array_map('intval', array_column($avanceEjes, 'metas')),
        ],
        'semaforo'    => $semaforo,
        'actividades' => $actividades,
    ]);
}


////////////////////// 2. VISOR DE BITÁCORA //////////////////////

function VistaBitacora()
{
    $usuarios = consultarPreparado("SELECT id_usuario, nombre FROM usuarios ORDER BY nombre");
    try {
        $acciones = consultarPreparado("SELECT DISTINCT accion FROM bitacora ORDER BY accion");
    } catch (Throwable $error) {
        $acciones = [];
    }
?>
    <h5 class="fw-bold mb-1" style="color:#0A2647;">
        <i class="bi bi-journal-text me-2"></i>Bitácora del Sistema
    </h5>
    <p class="text-muted mb-3" style="font-size:.85rem;">Registro de quién hizo qué y cuándo: inicios de sesión, altas, cambios, eliminaciones y avances.</p>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-3">
                    <label class="form-label fw-semibold" style="font-size:.8rem;">Usuario</label>
                    <select class="form-select form-select-sm" id="bitUsuario">
                        <option value="">Todos</option>
                        <?php foreach ($usuarios as $u): ?>
                            <option value="<?= (int)$u['id_usuario'] ?>"><?= e($u['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label fw-semibold" style="font-size:.8rem;">Acción</label>
                    <select class="form-select form-select-sm" id="bitAccion">
                        <option value="">Todas</option>
                        <?php foreach ($acciones as $a): ?>
                            <option value="<?= e($a['accion']) ?>"><?= e($a['accion']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label fw-semibold" style="font-size:.8rem;">Desde</label>
                    <input type="date" class="form-control form-control-sm" id="bitDesde">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label fw-semibold" style="font-size:.8rem;">Hasta</label>
                    <input type="date" class="form-control form-control-sm" id="bitHasta">
                </div>
                <div class="col-12 col-md-2 d-flex gap-1">
                    <button class="btn btn-primary btn-sm flex-fill" onclick="cargarTablaBitacora()"><i class="bi bi-funnel me-1"></i>Filtrar</button>
                    <button class="btn btn-outline-secondary btn-sm" onclick="limpiarFiltrosBitacora()" title="Limpiar filtros"><i class="bi bi-x-lg"></i></button>
                </div>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table id="TBBitacora" class="table table-striped table-bordered dt-responsive" style="width:100%">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Fecha y hora</th>
                    <th>Usuario</th>
                    <th>Acción</th>
                    <th>Descripción</th>
                    <th>IP</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
<?php
}

// JSON de la bitácora con filtros, de la más reciente a la más antigua.
function DatosBitacora()
{
    $id_usuario = (int)($_GET['usuario'] ?? 0);
    $accion     = trim($_GET['accion'] ?? '');
    $desde      = $_GET['desde'] ?? '';
    $hasta      = $_GET['hasta'] ?? '';

    $sql = "SELECT b.id_bitacora, b.fecha, b.accion, b.descripcion, b.ip,
                   COALESCE(u.nombre, 'Sistema / desconocido') AS nombre
            FROM bitacora b
            LEFT JOIN usuarios u ON b.id_usuario = u.id_usuario
            WHERE 1 = 1";
    $tipos      = '';
    $parametros = [];

    if ($id_usuario > 0) { $sql .= " AND b.id_usuario = ?"; $tipos .= 'i'; $parametros[] = $id_usuario; }
    if ($accion !== '')  { $sql .= " AND b.accion = ?";     $tipos .= 's'; $parametros[] = $accion; }
    if (esFechaValida($desde)) { $sql .= " AND b.fecha >= ?"; $tipos .= 's'; $parametros[] = $desde . ' 00:00:00'; }
    if (esFechaValida($hasta)) { $sql .= " AND b.fecha <= ?"; $tipos .= 's'; $parametros[] = $hasta . ' 23:59:59'; }
    $sql .= " ORDER BY b.id_bitacora DESC LIMIT 5000";

    try {
        $filas = consultarPreparado($sql, $tipos, $parametros);
    } catch (Throwable $error) {
        $filas = [];
    }

    $lista = [];
    foreach ($filas as $f) {
        $lista[] = [
            'orden'       => (int)$f['id_bitacora'],
            'fecha'       => date('d/m/Y H:i:s', strtotime($f['fecha'])),
            'nombre'      => $f['nombre'],
            'accion'      => $f['accion'],
            'descripcion' => $f['descripcion'],
            'ip'          => $f['ip'],
        ];
    }
    responderJson(['data' => $lista]);
}

// true si el texto es una fecha AAAA-MM-DD real (para los filtros opcionales de la bitácora).
function esFechaValida($fecha)
{
    $d = DateTime::createFromFormat('Y-m-d', $fecha);
    return $d && $d->format('Y-m-d') === $fecha;
}
