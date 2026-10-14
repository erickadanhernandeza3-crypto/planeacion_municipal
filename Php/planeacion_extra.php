<?php
/*
 * MÓDULOS NUEVOS DEL PANEL DE PLANEACIÓN
 * ---------------------------------------------------------------
 * 1. Alertas de metas vencidas y por vencer
 * 2. Registro de avances con historial (tabla avances_meta)
 * 3. Semáforo de cumplimiento (real vs esperado)
 * 4. Seguimiento con filtros por eje, programa, estado y semáforo
 * 5. Exportar a Excel / PDF / Imprimir (se hace en js/planeacion_extra.js)
 * 6. Ficha completa de la meta
 * 7. Reporte imprimible por eje
 * 8. Cambiar mi contraseña
 * 9. Comparativo trimestral
 *
 * Este archivo lo incluye controlador.php solo para los idopc que empiezan
 * con "Plan". No modifica nada de operaciones.php ni de vistas.php.
 * Las funciones de ayuda (semáforo, bitácora, formatos) están en comunes_extra.php.
 * Todas las consultas usan sentencias preparadas (prepare / bind_param).
 */

require_once __DIR__ . '/comunes_extra.php';

$idopc = $_GET['idopc'] ?? '';

// Ninguna opción de este archivo se ejecuta si no hay sesión de planeación.
verificarRol('planeacion');

switch ($idopc) {

    case 'PlanAlertas':            VistaAlertas();            break;
    case 'PlanResumenAlertas':     ResumenAlertas();          break;
    case 'PlanSeguimiento':        VistaSeguimiento();        break;
    case 'PlanDatosSeguimiento':   DatosSeguimiento();        break;
    case 'PlanFrmAvance':          FormularioAvance();        break;
    case 'PlanGuardarAvance':      GuardarAvance();           break;
    case 'PlanHistorialAvances':   HistorialAvances();        break;
    case 'PlanFichaMeta':          FichaMeta();               break;
    case 'PlanFrmReporte':         VistaReporte();            break;
    case 'PlanReporteEje':         ReporteEje();              break;
    case 'PlanFrmContrasena':      FormularioContrasena();    break;
    case 'PlanGuardarContrasena':  GuardarContrasena();       break;
    case 'PlanTrimestral':         VistaTrimestral('PlanDatosTrimestral'); break;
    case 'PlanDatosTrimestral':    responderJson(datosTrimestrales((int)($_GET['anio'] ?? date('Y')))); break;
}


// Busca una meta con su programa y eje.
function obtenerMeta($id_meta)
{
    $filas = consultarPreparado(
        "SELECT m.*, p.nombre_programa, e.id_eje, e.nombre_eje, u.nombre AS nombre_creador, " . columnasDias() . "
         FROM metas m
         INNER JOIN programas p ON m.id_programa = p.id_programa
         INNER JOIN ejes e      ON p.id_eje = e.id_eje
         LEFT JOIN usuarios u   ON m.creado_por = u.id_usuario
         WHERE m.id_meta = ?",
        'i', [$id_meta]
    );
    return $filas[0] ?? null;
}



////////////////////// 1. ALERTAS DE VENCIMIENTO //////////////////////

// Conteos para el aviso del inicio del panel.
function ResumenAlertas()
{
    $filas = consultarPreparado(
        "SELECT
            SUM(fecha_limite < CURDATE()) AS vencidas,
            SUM(fecha_limite BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 15 DAY)) AS por_vencer
         FROM metas
         WHERE estado <> 'Terminado' AND fecha_limite IS NOT NULL"
    );
    responderJson([
        'vencidas'   => (int)($filas[0]['vencidas'] ?? 0),
        'por_vencer' => (int)($filas[0]['por_vencer'] ?? 0),
    ]);
}

function VistaAlertas()
{
    $sqlBase = "SELECT m.id_meta, m.actividad, m.meta_programada, m.meta_alcanzada, m.unidad_medida,
                       m.fecha_limite, m.estado, p.nombre_programa, e.nombre_eje,
                       DATEDIFF(m.fecha_limite, CURDATE()) AS dias
                FROM metas m
                INNER JOIN programas p ON m.id_programa = p.id_programa
                INNER JOIN ejes e      ON p.id_eje = e.id_eje
                WHERE m.estado <> 'Terminado' AND m.fecha_limite IS NOT NULL ";

    $vencidas  = consultarPreparado($sqlBase . "AND m.fecha_limite < CURDATE() ORDER BY m.fecha_limite ASC");
    $porVencer = consultarPreparado($sqlBase . "AND m.fecha_limite BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 15 DAY) ORDER BY m.fecha_limite ASC");
?>
    <h5 class="fw-bold mb-1" style="color:#0A2647;" id="tituloAlertas">
        <i class="bi bi-alarm me-2"></i>Alertas de Vencimiento
    </h5>
    <p class="text-muted mb-4" style="font-size:.85rem;">Metas que no están terminadas y ya vencieron o vencen en los próximos 15 días.</p>

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="p-3 rounded-3 text-white" style="background:linear-gradient(135deg,#7b1f1f,#c0392b);">
                <i class="bi bi-exclamation-octagon d-block fs-3 mb-1"></i>
                <div style="font-size:1.6rem;font-weight:700;"><?= count($vencidas) ?></div>
                <div style="font-size:.78rem;opacity:.85;">Vencidas</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="p-3 rounded-3 text-white" style="background:linear-gradient(135deg,#7d5a00,#c89b00);">
                <i class="bi bi-hourglass-split d-block fs-3 mb-1"></i>
                <div style="font-size:1.6rem;font-weight:700;"><?= count($porVencer) ?></div>
                <div style="font-size:.78rem;opacity:.85;">Por vencer (15 días)</div>
            </div>
        </div>
    </div>

    <?php
    TablaAlertas('Vencidas', 'bi-exclamation-octagon', '#c0392b', $vencidas, true);
    TablaAlertas('Por vencer', 'bi-hourglass-split', '#c89b00', $porVencer, false);
}

function TablaAlertas($titulo, $icono, $color, $metas, $esVencida)
{
?>
    <div class="card mb-4" style="border-left:5px solid <?= $color ?>;">
        <div class="card-body">
            <h6 class="fw-bold mb-3" style="color:<?= $color ?>;"><i class="bi <?= $icono ?> me-2"></i><?= e($titulo) ?></h6>
            <?php if (empty($metas)): ?>
                <p class="text-muted mb-0" style="font-size:.85rem;"><i class="bi bi-check-circle me-1" style="color:#2fa86a;"></i>No hay metas en esta lista.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle mb-0" style="font-size:.84rem;">
                        <thead>
                            <tr style="background:#0A2647;color:#fff;">
                                <th style="background:#0A2647;color:#fff;">Eje</th>
                                <th style="background:#0A2647;color:#fff;">Programa</th>
                                <th style="background:#0A2647;color:#fff;">Actividad</th>
                                <th style="background:#0A2647;color:#fff;">Avance</th>
                                <th style="background:#0A2647;color:#fff;">Fecha límite</th>
                                <th style="background:#0A2647;color:#fff;"><?= $esVencida ? 'Días de atraso' : 'Días restantes' ?></th>
                                <th style="background:#0A2647;color:#fff;text-align:center;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($metas as $m):
                            $programada = (float)$m['meta_programada'];
                            $porcentaje = $programada > 0 ? round(min($m['meta_alcanzada'] / $programada, 1) * 100, 1) : 0;
                            $dias       = abs((int)$m['dias']);
                        ?>
                            <tr>
                                <td><?= e($m['nombre_eje']) ?></td>
                                <td><?= e($m['nombre_programa']) ?></td>
                                <td><?= e($m['actividad']) ?></td>
                                <td><?= formatoNumero($m['meta_alcanzada']) ?> / <?= formatoNumero($m['meta_programada']) ?> <?= e($m['unidad_medida']) ?> (<?= $porcentaje ?>%)</td>
                                <td><?= formatoFecha($m['fecha_limite']) ?></td>
                                <td><strong style="color:<?= $color ?>;"><?= $dias ?> día<?= $dias == 1 ? '' : 's' ?></strong></td>
                                <td class="text-center text-nowrap"><?= botonesAccionMeta($m['id_meta']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php
}

// Botones Registrar avance / Historial / Ficha (se usan en alertas).
function botonesAccionMeta($id_meta)
{
    $id = (int)$id_meta;
    return '<a href="#" onclick="abrirFormAvance(' . $id . ');return false;" class="fs-5 me-2" title="Registrar avance"><i class="bi bi-plus-circle-fill" style="color:#2fa86a;"></i></a>'
         . '<a href="#" onclick="abrirHistorial(' . $id . ');return false;" class="fs-5 me-2" title="Historial de avances"><i class="bi bi-clock-history" style="color:#0A2647;"></i></a>'
         . '<a href="#" onclick="abrirFicha(' . $id . ');return false;" class="fs-5" title="Ficha de la meta"><i class="bi bi-eye-fill" style="color:#c89b00;"></i></a>';
}


////////////////////// 3, 4 y 5. SEGUIMIENTO (semáforo + filtros + exportar) //////////////////////

function VistaSeguimiento()
{
    $ejes = consultarPreparado("SELECT id_eje, nombre_eje FROM ejes ORDER BY nombre_eje ASC");
?>
    <h5 class="fw-bold mb-1" style="color:#0A2647;">
        <i class="bi bi-speedometer2 me-2"></i>Seguimiento de Metas
    </h5>
    <p class="text-muted mb-3" style="font-size:.85rem;">Filtra las metas, revisa su semáforo de cumplimiento, registra avances y exporta el reporte.</p>

    <!-- Filtros -->
    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-3">
                    <label class="form-label fw-semibold" style="font-size:.8rem;">Eje</label>
                    <select class="form-select form-select-sm" id="filtroEje" onchange="cargarFiltroProgramas(this.value)">
                        <option value="">Todos los ejes</option>
                        <?php foreach ($ejes as $eje): ?>
                            <option value="<?= (int)$eje['id_eje'] ?>"><?= e($eje['nombre_eje']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label fw-semibold" style="font-size:.8rem;">Programa</label>
                    <select class="form-select form-select-sm" id="filtroPrograma">
                        <option value="">Todos los programas</option>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label fw-semibold" style="font-size:.8rem;">Estado</label>
                    <select class="form-select form-select-sm" id="filtroEstado">
                        <option value="">Todos</option>
                        <option value="Pendiente">Pendiente</option>
                        <option value="En Proceso">En Proceso</option>
                        <option value="Terminado">Terminado</option>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label fw-semibold" style="font-size:.8rem;">Semáforo</label>
                    <select class="form-select form-select-sm" id="filtroSemaforo">
                        <option value="">Todos</option>
                        <option value="Verde">Verde</option>
                        <option value="Amarillo">Amarillo</option>
                        <option value="Rojo">Rojo</option>
                        <option value="Sin fecha">Sin fecha</option>
                    </select>
                </div>
                <div class="col-12 col-md-2 d-flex gap-1">
                    <button class="btn btn-primary btn-sm flex-fill" onclick="cargarTablaSeguimiento()"><i class="bi bi-funnel me-1"></i>Filtrar</button>
                    <button class="btn btn-outline-secondary btn-sm" onclick="limpiarFiltrosSeguimiento()" title="Limpiar filtros"><i class="bi bi-x-lg"></i></button>
                </div>
            </div>
        </div>
    </div>

    <!-- Simbología del semáforo -->
    <div class="d-flex flex-wrap gap-3 mb-3" style="font-size:.78rem;">
        <span><?= circuloSemaforo('Verde') ?> <span class="text-muted">va al corriente</span></span>
        <span><?= circuloSemaforo('Amarillo') ?> <span class="text-muted">10 a 25 puntos atrasada</span></span>
        <span><?= circuloSemaforo('Rojo') ?> <span class="text-muted">más de 25 puntos atrasada</span></span>
        <span><?= circuloSemaforo('Sin fecha') ?> <span class="text-muted">no tiene fecha límite</span></span>
    </div>

    <div class="table-responsive">
        <table id="TBSeguimiento" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
            <thead>
                <tr>
                    <th>Eje</th>
                    <th>Programa</th>
                    <th>Actividad</th>
                    <th>Unidad</th>
                    <th>Programada</th>
                    <th>Alcanzada</th>
                    <th>% Real</th>
                    <th>% Esperado</th>
                    <th>Semáforo</th>
                    <th>Estado</th>
                    <th>Fecha límite</th>
                    <th style="text-align:center;">Acciones</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
<?php
}

// JSON para la tabla de seguimiento, con filtros opcionales.
function DatosSeguimiento()
{
    $id_eje      = (int)($_GET['eje'] ?? 0);
    $id_programa = (int)($_GET['programa'] ?? 0);
    $estado      = $_GET['estado'] ?? '';
    $semaforo    = $_GET['semaforo'] ?? '';

    $sql = "SELECT m.id_meta, m.actividad, m.unidad_medida, m.meta_programada, m.meta_alcanzada,
                   m.fecha_inicio, m.fecha_limite, m.estado, p.nombre_programa, e.nombre_eje, " . columnasDias() . "
            FROM metas m
            INNER JOIN programas p ON m.id_programa = p.id_programa
            INNER JOIN ejes e      ON p.id_eje = e.id_eje
            WHERE 1 = 1";
    $tipos      = '';
    $parametros = [];

    if ($id_eje > 0)      { $sql .= " AND e.id_eje = ?";      $tipos .= 'i'; $parametros[] = $id_eje; }
    if ($id_programa > 0) { $sql .= " AND p.id_programa = ?"; $tipos .= 'i'; $parametros[] = $id_programa; }
    if (in_array($estado, ['Pendiente', 'En Proceso', 'Terminado'], true)) {
        $sql .= " AND m.estado = ?"; $tipos .= 's'; $parametros[] = $estado;
    }
    $sql .= " ORDER BY e.nombre_eje, p.nombre_programa, m.id_meta";

    $metas = consultarPreparado($sql, $tipos, $parametros);
    $lista = [];
    foreach ($metas as $m) {
        $s = calcularSemaforo($m);
        // El semáforo se calcula en PHP, por eso este filtro se aplica aquí.
        if ($semaforo !== '' && $s['color'] !== $semaforo) continue;

        $lista[] = [
            'id_meta'         => (int)$m['id_meta'],
            'nombre_eje'      => $m['nombre_eje'],
            'nombre_programa' => $m['nombre_programa'],
            'actividad'       => $m['actividad'],
            'unidad_medida'   => $m['unidad_medida'],
            'meta_programada' => formatoNumero($m['meta_programada']),
            'meta_alcanzada'  => formatoNumero($m['meta_alcanzada']),
            'real'            => $s['real'],
            'esperado'        => $s['esperado'],
            'semaforo'        => $s['color'],
            'estado'          => $m['estado'],
            'fecha_limite'    => $m['fecha_limite'] ? formatoFecha($m['fecha_limite']) : 'Sin fecha',
        ];
    }
    responderJson(['data' => $lista]);
}


////////////////////// 2. REGISTRO DE AVANCES //////////////////////

function FormularioAvance()
{
    $id_meta = (int)($_GET['idr'] ?? 0);
    $meta    = obtenerMeta($id_meta);
    if (!$meta) { echo '<div class="alert alert-warning">La meta no existe.</div>'; return; }

    $faltante = max(0, (float)$meta['meta_programada'] - (float)$meta['meta_alcanzada']);
    $hoy      = fechaHoy();
?>
    <h6 class="fw-bold mb-1" style="color:#0A2647;"><i class="bi bi-plus-circle me-2"></i>Registrar avance</h6>
    <p class="mb-3" style="font-size:.85rem;">
        <strong><?= e($meta['actividad']) ?></strong><br>
        <span class="text-muted"><?= e($meta['nombre_eje']) ?> › <?= e($meta['nombre_programa']) ?></span>
    </p>

    <div class="row g-2 mb-3" style="font-size:.82rem;">
        <div class="col-4"><div class="p-2 rounded" style="background:#e8eef7;">Programada<br><strong><?= formatoNumero($meta['meta_programada']) ?> <?= e($meta['unidad_medida']) ?></strong></div></div>
        <div class="col-4"><div class="p-2 rounded" style="background:#d4f4e2;">Alcanzada<br><strong><?= formatoNumero($meta['meta_alcanzada']) ?></strong></div></div>
        <div class="col-4"><div class="p-2 rounded" style="background:#fff3cd;">Falta<br><strong><?= formatoNumero($faltante) ?></strong></div></div>
    </div>

    <form id="frmAvance" onsubmit="guardarAvance(event)">
        <input type="hidden" name="id_meta" value="<?= (int)$meta['id_meta'] ?>">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label fw-semibold">Cantidad avanzada (<?= e($meta['unidad_medida']) ?>) <span class="text-danger">*</span></label>
                <input type="number" step="0.01" min="0.01" class="form-control" id="valor_avance" name="valor_avance" required>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">Fecha del avance <span class="text-danger">*</span></label>
                <input type="date" class="form-control" id="fecha_avance" name="fecha_avance" value="<?= $hoy ?>" max="<?= $hoy ?>" required>
            </div>
            <div class="col-12">
                <label class="form-label fw-semibold">Descripción</label>
                <textarea class="form-control" id="descripcion_avance" name="descripcion" rows="2" maxlength="255" placeholder="¿Qué se realizó? Ej. Se entregaron 20 despensas en la comunidad X"></textarea>
            </div>
        </div>
        <div class="mt-3 d-flex gap-2">
            <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Guardar avance</button>
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        </div>
    </form>
<?php
}

/*
 * Guarda el avance y actualiza la meta en UNA TRANSACCIÓN:
 *  1. Inserta el avance en avances_meta.
 *  2. Suma el valor a metas.meta_alcanzada (se suma, así no se pierde lo capturado antes).
 *  3. Recalcula metas.estado con la misma regla de siempre.
 *  4. Lo registra en la bitácora.
 * Si cualquier paso falla, se revierte todo (rollback).
 */
function GuardarAvance()
{
    global $obj;
    $conexion = $obj->conexion();

    $id_meta     = (int)($_POST['id_meta'] ?? 0);
    $valor       = (float)($_POST['valor_avance'] ?? 0);
    $fecha       = trim($_POST['fecha_avance'] ?? '');
    $descripcion = mb_substr(trim($_POST['descripcion'] ?? ''), 0, 255);
    $id_usuario  = (int)$_SESSION['id_usuario'];

    // Validaciones del servidor (aunque el navegador ya valide, aquí es obligatorio)
    if ($valor <= 0) {
        responderJson(['ok' => false, 'mensaje' => 'La cantidad debe ser mayor a 0.']);
    }
    $fechaObj = DateTime::createFromFormat('Y-m-d', $fecha);
    if (!$fechaObj || $fechaObj->format('Y-m-d') !== $fecha) {
        responderJson(['ok' => false, 'mensaje' => 'La fecha no es válida.']);
    }
    if ($fecha > fechaHoy()) {
        responderJson(['ok' => false, 'mensaje' => 'La fecha del avance no puede ser futura.']);
    }

    mysqli_begin_transaction($conexion);
    try {
        // Bloquea la fila de la meta mientras se actualiza (evita que dos capturas se pisen)
        $stmt = mysqli_prepare($conexion, "SELECT actividad, meta_programada, meta_alcanzada FROM metas WHERE id_meta = ? FOR UPDATE");
        mysqli_stmt_bind_param($stmt, 'i', $id_meta);
        mysqli_stmt_execute($stmt);
        $meta = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);
        if (!$meta) {
            throw new Exception('La meta no existe.');
        }

        // 1. Insertar el avance
        $stmt = mysqli_prepare($conexion, "INSERT INTO avances_meta (id_meta, valor_avance, descripcion, fecha_avance, registrado_por) VALUES (?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'idssi', $id_meta, $valor, $descripcion, $fecha, $id_usuario);
        if (!mysqli_stmt_execute($stmt)) throw new Exception('No se pudo guardar el avance.');
        mysqli_stmt_close($stmt);

        // 2 y 3. Nuevo alcanzado y nuevo estado
        $nuevaAlcanzada = (float)$meta['meta_alcanzada'] + $valor;
        $nuevoEstado    = calcularEstado((float)$meta['meta_programada'], $nuevaAlcanzada);

        $stmt = mysqli_prepare($conexion, "UPDATE metas SET meta_alcanzada = ?, estado = ? WHERE id_meta = ?");
        mysqli_stmt_bind_param($stmt, 'dsi', $nuevaAlcanzada, $nuevoEstado, $id_meta);
        if (!mysqli_stmt_execute($stmt)) throw new Exception('No se pudo actualizar la meta.');
        mysqli_stmt_close($stmt);

        // 4. Bitácora
        registrarBitacora('Registrar avance', 'Meta #' . $id_meta . ' (' . $meta['actividad'] . '): +' . formatoNumero($valor) . ', nuevo total ' . formatoNumero($nuevaAlcanzada) . ', estado ' . $nuevoEstado);

        mysqli_commit($conexion);
        responderJson(['ok' => true, 'mensaje' => 'Avance registrado. Total alcanzado: ' . formatoNumero($nuevaAlcanzada) . ' — Estado: ' . $nuevoEstado]);

    } catch (Throwable $error) {
        mysqli_rollback($conexion);
        responderJson(['ok' => false, 'mensaje' => 'No se guardó nada. ' . $error->getMessage()]);
    }
}

// Línea de tiempo de avances de una meta (HTML). Se usa sola y dentro de la ficha.
function LineaTiempoAvances($id_meta)
{
    $avances = consultarPreparado(
        "SELECT a.valor_avance, a.descripcion, a.fecha_avance, a.creado_en, u.nombre
         FROM avances_meta a
         LEFT JOIN usuarios u ON a.registrado_por = u.id_usuario
         WHERE a.id_meta = ?
         ORDER BY a.fecha_avance DESC, a.id_avance DESC",
        'i', [$id_meta]
    );

    if (empty($avances)) {
        echo '<p class="text-muted" style="font-size:.85rem;"><i class="bi bi-info-circle me-1"></i>Esta meta todavía no tiene avances registrados.</p>';
        return;
    }

    echo '<div style="border-left:3px solid #D4AF37;margin-left:8px;padding-left:18px;">';
    foreach ($avances as $a) {
        echo '<div style="position:relative;margin-bottom:1rem;">'
           . '<span style="position:absolute;left:-27px;top:3px;width:15px;height:15px;border-radius:50%;background:#0A2647;border:3px solid #D4AF37;"></span>'
           . '<div style="font-size:.78rem;color:#6c757d;">' . formatoFecha($a['fecha_avance'])
           . ' · registrado por <strong>' . e($a['nombre'] ?? 'Usuario eliminado') . '</strong></div>'
           . '<div style="font-weight:700;color:#1a6b3c;">+' . formatoNumero($a['valor_avance']) . '</div>'
           . ($a['descripcion'] !== null && $a['descripcion'] !== '' ? '<div style="font-size:.85rem;">' . e($a['descripcion']) . '</div>' : '')
           . '</div>';
    }
    echo '</div>';
}

function HistorialAvances()
{
    $id_meta = (int)($_GET['idr'] ?? 0);
    $meta    = obtenerMeta($id_meta);
    if (!$meta) { echo '<div class="alert alert-warning">La meta no existe.</div>'; return; }
?>
    <h6 class="fw-bold mb-1" style="color:#0A2647;"><i class="bi bi-clock-history me-2"></i>Historial de avances</h6>
    <p class="mb-3" style="font-size:.85rem;">
        <strong><?= e($meta['actividad']) ?></strong> — <?= formatoNumero($meta['meta_alcanzada']) ?> de <?= formatoNumero($meta['meta_programada']) ?> <?= e($meta['unidad_medida']) ?>
    </p>
    <?php LineaTiempoAvances($id_meta); ?>
    <button class="btn btn-primary btn-sm mt-2" onclick="abrirFormAvance(<?= (int)$id_meta ?>)"><i class="bi bi-plus-circle me-1"></i>Registrar avance</button>
<?php
}


////////////////////// 6. FICHA DE LA META //////////////////////

function FichaMeta()
{
    $id_meta = (int)($_GET['idr'] ?? 0);
    $meta    = obtenerMeta($id_meta);
    if (!$meta) { echo '<div class="alert alert-warning">La meta no existe.</div>'; return; }

    $s          = calcularSemaforo($meta);
    $asignado   = (float)$meta['presupuesto_asignado'];
    $ejercido   = (float)$meta['presupuesto_ejercido'];
    $pctPresup  = $asignado > 0 ? round($ejercido / $asignado * 100, 1) : 0;
    $colorBarra = $s['real'] >= 100 ? '#2fa86a' : ($s['real'] > 0 ? '#c89b00' : '#c0392b');
?>
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
        <div>
            <div style="font-size:.75rem;color:#6c757d;text-transform:uppercase;letter-spacing:.04em;"><?= e($meta['nombre_eje']) ?> › <?= e($meta['nombre_programa']) ?></div>
            <h5 class="fw-bold mb-0" style="color:#0A2647;"><?= e($meta['actividad']) ?></h5>
        </div>
        <div class="d-flex gap-2 align-items-center"><?= etiquetaEstado($meta['estado']) ?> <?= circuloSemaforo($s['color']) ?></div>
    </div>

    <!-- Barra de avance -->
    <div class="mb-1 d-flex justify-content-between" style="font-size:.82rem;">
        <span><strong><?= formatoNumero($meta['meta_alcanzada']) ?></strong> de <?= formatoNumero($meta['meta_programada']) ?> <?= e($meta['unidad_medida']) ?></span>
        <span style="font-weight:700;color:<?= $colorBarra ?>;"><?= $s['real'] ?>%</span>
    </div>
    <div style="background:#e9ecef;border-radius:10px;height:12px;position:relative;" class="mb-1">
        <div style="width:<?= $s['real'] ?>%;background:<?= $colorBarra ?>;height:12px;border-radius:10px;"></div>
        <?php if ($s['esperado'] !== null): ?>
            <div title="Avance esperado a hoy" style="position:absolute;top:-4px;left:<?= $s['esperado'] ?>%;width:3px;height:20px;background:#0A2647;"></div>
        <?php endif; ?>
    </div>
    <p class="text-muted mb-3" style="font-size:.75rem;">
        <?= $s['esperado'] !== null ? 'La línea azul marca el avance que se esperaba a hoy: ' . $s['esperado'] . '%.' : 'Sin fecha límite: no se calcula el avance esperado.' ?>
    </p>

    <div class="row g-2 mb-3" style="font-size:.84rem;">
        <div class="col-6 col-md-3"><div class="p-2 rounded h-100" style="background:#f4f6fa;">Fecha de inicio<br><strong><?= formatoFecha($meta['fecha_inicio']) ?></strong></div></div>
        <div class="col-6 col-md-3"><div class="p-2 rounded h-100" style="background:#f4f6fa;">Fecha límite<br><strong><?= formatoFecha($meta['fecha_limite']) ?></strong></div></div>
        <div class="col-6 col-md-3"><div class="p-2 rounded h-100" style="background:#f4f6fa;">Presupuesto asignado<br><strong>$<?= formatoNumero($asignado) ?></strong></div></div>
        <div class="col-6 col-md-3"><div class="p-2 rounded h-100" style="background:#f4f6fa;">Presupuesto ejercido<br><strong>$<?= formatoNumero($ejercido) ?></strong> <span class="text-muted">(<?= $pctPresup ?>%)</span></div></div>
    </div>

    <?php if (!empty($meta['observaciones'])): ?>
        <div class="p-2 rounded mb-3" style="background:#fff8e1;border-left:4px solid #D4AF37;font-size:.84rem;">
            <strong>Observaciones:</strong> <?= nl2br(e($meta['observaciones'])) ?>
        </div>
    <?php endif; ?>

    <p class="text-muted" style="font-size:.75rem;">
        Registrada por <?= e($meta['nombre_creador'] ?? '—') ?> el <?= formatoFecha($meta['creado_en']) ?> · Última actualización: <?= formatoFecha($meta['actualizado_en']) ?>
    </p>

    <hr>
    <h6 class="fw-bold mb-3" style="color:#0A2647;"><i class="bi bi-clock-history me-2"></i>Historial de avances</h6>
    <?php LineaTiempoAvances($id_meta); ?>
    <button class="btn btn-primary btn-sm mt-2" onclick="abrirFormAvance(<?= (int)$id_meta ?>)"><i class="bi bi-plus-circle me-1"></i>Registrar avance</button>
<?php
}


////////////////////// 7. REPORTE IMPRIMIBLE POR EJE //////////////////////

function VistaReporte()
{
    $ejes = consultarPreparado("SELECT id_eje, nombre_eje FROM ejes ORDER BY nombre_eje ASC");
?>
    <h5 class="fw-bold mb-1" style="color:#0A2647;">
        <i class="bi bi-printer me-2"></i>Reporte Imprimible por Eje
    </h5>
    <p class="text-muted mb-3" style="font-size:.85rem;">Genera una hoja con formato oficial para imprimir o guardar como PDF desde el navegador.</p>
    <div class="card" style="max-width:560px;">
        <div class="card-body">
            <label class="form-label fw-semibold">Eje estratégico</label>
            <select class="form-select mb-3" id="reporteEje">
                <option value="0">Todos los ejes</option>
                <?php foreach ($ejes as $eje): ?>
                    <option value="<?= (int)$eje['id_eje'] ?>"><?= e($eje['nombre_eje']) ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-primary" onclick="abrirReporteEje()"><i class="bi bi-file-earmark-text me-1"></i>Generar reporte</button>
        </div>
    </div>
<?php
}

// Página completa (se abre en otra pestaña).
function ReporteEje()
{
    $id_eje = (int)($_GET['idr'] ?? 0);

    $sql = "SELECT m.*, p.nombre_programa, e.id_eje, e.nombre_eje, " . columnasDias() . "
            FROM metas m
            INNER JOIN programas p ON m.id_programa = p.id_programa
            INNER JOIN ejes e      ON p.id_eje = e.id_eje";
    if ($id_eje > 0) {
        $metas = consultarPreparado($sql . " WHERE e.id_eje = ? ORDER BY e.nombre_eje, p.nombre_programa, m.id_meta", 'i', [$id_eje]);
    } else {
        $metas = consultarPreparado($sql . " ORDER BY e.nombre_eje, p.nombre_programa, m.id_meta");
    }

    // Agrupar metas por eje
    $porEje = [];
    foreach ($metas as $m) {
        $porEje[$m['nombre_eje']][] = $m;
    }
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Reporte de Metas por Eje — Tamazunchale</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { font-family: 'Segoe UI', sans-serif; font-size: 12px; color: #222; background: #fff; }
    .encabezado { border-bottom: 4px solid #D4AF37; padding-bottom: 10px; margin-bottom: 18px; }
    .encabezado h4 { color: #0A2647; font-weight: 800; margin: 0; }
    .eje-titulo { background: #0A2647; color: #fff; padding: 6px 10px; font-weight: 700; margin-top: 18px; }
    table { width: 100%; border-collapse: collapse; }
    th { background: #e8eef7; color: #0A2647; }
    th, td { border: 1px solid #ccc; padding: 4px 6px; vertical-align: top; }
    .firma { margin-top: 70px; text-align: center; }
    .firma div { border-top: 1px solid #333; width: 260px; margin: 0 auto; padding-top: 4px; }
    @media print { .no-imprimir { display: none !important; } @page { size: landscape; margin: 1.2cm; } }
  </style>
</head>
<body class="p-4">
  <div class="no-imprimir mb-3 d-flex gap-2">
    <button class="btn btn-sm text-white" style="background:#0A2647;" onclick="window.print()">🖨️ Imprimir / Guardar PDF</button>
    <button class="btn btn-sm btn-outline-secondary" onclick="window.close()">Cerrar</button>
  </div>

  <div class="encabezado d-flex justify-content-between align-items-end">
    <div class="d-flex align-items-center gap-3">
      <?php if (file_exists(__DIR__ . '/../img/escudo.png')): ?>
        <img src="../img/escudo.png" alt="Escudo" style="height:64px;">
      <?php endif; ?>
      <div>
      <h4>H. Ayuntamiento de Tamazunchale, S.L.P.</h4>
      <div style="color:#555;">Departamento de Planeación — Reporte de avance de metas por eje estratégico</div>
      </div>
    </div>
    <div style="text-align:right;color:#555;">
      Fecha de emisión: <strong><?= formatoFecha(fechaHoy()) ?></strong><br>
      Elaboró: <strong><?= e($_SESSION['nombre'] ?? '') ?></strong>
    </div>
  </div>

  <?php if (empty($porEje)): ?>
    <p>No hay metas registradas para este eje.</p>
  <?php endif; ?>

  <?php foreach ($porEje as $nombreEje => $lista):
      $sumaPorcentajes = 0;
      $terminadas      = 0;
  ?>
    <div class="eje-titulo"><?= e($nombreEje) ?></div>
    <table>
      <thead>
        <tr>
          <th>Programa</th><th>Actividad</th><th>Unidad</th><th>Programada</th><th>Alcanzada</th>
          <th>% Avance</th><th>% Esperado</th><th>Semáforo</th><th>Estado</th><th>Fecha límite</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($lista as $m):
          $s = calcularSemaforo($m);
          $sumaPorcentajes += $s['real'];
          if ($m['estado'] === 'Terminado') $terminadas++;
      ?>
        <tr>
          <td><?= e($m['nombre_programa']) ?></td>
          <td><?= e($m['actividad']) ?></td>
          <td><?= e($m['unidad_medida']) ?></td>
          <td><?= formatoNumero($m['meta_programada']) ?></td>
          <td><?= formatoNumero($m['meta_alcanzada']) ?></td>
          <td><?= $s['real'] ?>%</td>
          <td><?= $s['esperado'] !== null ? $s['esperado'] . '%' : '—' ?></td>
          <td><?= circuloSemaforo($s['color']) ?></td>
          <td><?= e($m['estado']) ?></td>
          <td><?= formatoFecha($m['fecha_limite']) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <div style="margin-top:4px;font-weight:600;color:#0A2647;">
      Metas: <?= count($lista) ?> · Terminadas: <?= $terminadas ?> ·
      Avance promedio del eje: <?= round($sumaPorcentajes / count($lista), 1) ?>%
    </div>
  <?php endforeach; ?>

  <div class="firma"><div>Responsable del Departamento de Planeación</div></div>
</body>
</html>
<?php
    exit;
}


////////////////////// 8. CAMBIAR MI CONTRASEÑA //////////////////////

function FormularioContrasena()
{
?>
    <h5 class="fw-bold mb-1" style="color:#0A2647;">
        <i class="bi bi-key me-2"></i>Cambiar mi Contraseña
    </h5>
    <p class="text-muted mb-3" style="font-size:.85rem;">Usuario: <strong><?= e($_SESSION['usuario'] ?? '') ?></strong></p>
    <div class="card" style="max-width:480px;">
        <div class="card-body">
            <form id="frmContrasena" onsubmit="guardarContrasena(event)">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Contraseña actual <span class="text-danger">*</span></label>
                    <input type="password" class="form-control" name="actual" id="pass_actual" required autocomplete="current-password">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Nueva contraseña <span class="text-danger">*</span></label>
                    <input type="password" class="form-control" name="nueva" id="pass_nueva" minlength="6" required autocomplete="new-password">
                    <div class="form-text">Mínimo 6 caracteres.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Confirmar nueva contraseña <span class="text-danger">*</span></label>
                    <input type="password" class="form-control" name="confirmar" id="pass_confirmar" minlength="6" required autocomplete="new-password">
                </div>
                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Guardar contraseña</button>
            </form>
        </div>
    </div>
<?php
}

function GuardarContrasena()
{
    global $obj;
    $conexion   = $obj->conexion();
    $id_usuario = (int)$_SESSION['id_usuario'];
    $actual     = $_POST['actual'] ?? '';
    $nueva      = trim($_POST['nueva'] ?? '');
    $confirmar  = trim($_POST['confirmar'] ?? '');

    if (strlen($nueva) < 6)   responderJson(['ok' => false, 'mensaje' => 'La nueva contraseña debe tener al menos 6 caracteres.']);
    if ($nueva !== $confirmar) responderJson(['ok' => false, 'mensaje' => 'La confirmación no coincide.']);

    $filas = consultarPreparado("SELECT contrasena FROM usuarios WHERE id_usuario = ?", 'i', [$id_usuario]);
    if (!$filas || !password_verify(trim($actual), $filas[0]['contrasena'])) {
        responderJson(['ok' => false, 'mensaje' => 'La contraseña actual es incorrecta.']);
    }

    $hash = password_hash($nueva, PASSWORD_BCRYPT);
    $stmt = mysqli_prepare($conexion, "UPDATE usuarios SET contrasena = ? WHERE id_usuario = ?");
    mysqli_stmt_bind_param($stmt, 'si', $hash, $id_usuario);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    try {
        registrarBitacora('Cambiar contraseña', 'El usuario cambió su propia contraseña.');
    } catch (Throwable $error) {
        // La contraseña ya se cambió; si la bitácora falla no se avisa como error.
    }
    responderJson(['ok' => true, 'mensaje' => 'Contraseña actualizada. Úsala la próxima vez que inicies sesión.']);
}
