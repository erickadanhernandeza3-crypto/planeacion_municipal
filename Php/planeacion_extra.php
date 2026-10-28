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

// Ninguna opción de este archivo se ejecuta sin sesión de planeación o de enlace.
// Un enlace solo llega a las opciones de OPCIONES_ENLACE (lo revisa controlador.php)
// y cada una filtra por su dependencia.
verificarRol(['planeacion', 'enlace']);

// Opciones que solo usa Planeación aunque estén en este archivo.
if (in_array($idopc, ['PlanRevision', 'PlanAprobarAvance', 'PlanDevolverAvance'], true)) {
    verificarRol('planeacion');
}

switch ($idopc) {

    // Revisión de avances de los enlaces y evidencias
    case 'PlanRevision':           VistaRevision();           break;
    case 'PlanAprobarAvance':      AprobarAvance();           break;
    case 'PlanDevolverAvance':     DevolverAvance();          break;
    case 'PlanContarPendientes':   ContarPendientes();        break;
    case 'PlanMisAvances':         VistaMisAvances();         break;
    case 'PlanResumenEnlace':      ResumenEnlace();           break;
    case 'PlanSubirEvidencia':     SubirEvidencia();          break;
    case 'PlanBorrarEvidencia':    BorrarEvidencia();         break;
    case 'PlanEvidenciasMeta':     EvidenciasMeta();          break;

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
    case 'PlanFisicoFinanciero':      VistaFisicoFinanciero();      break;
    case 'PlanDatosFisicoFinanciero': DatosFisicoFinanciero();      break;
    case 'PlanDependencias':          VistaDependencias();          break;
    case 'PlanDatosDependencias':     DatosDependencias();          break;
    case 'PlanMetasDependencia':      MetasDependencia();           break;
    case 'PlanCalendario':            VistaCalendario();            break;
    case 'PlanDatosCalendario':       DatosCalendario();            break;
    case 'PlanObservaciones':         ModalObservaciones();         break;
    case 'PlanGuardarObservacion':    GuardarObservacion();         break;
    case 'PlanCumplirAcuerdo':        CumplirAcuerdo();             break;
    case 'PlanEliminarObservacion':   EliminarObservacion();        break;
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
    [$filtro, $tipos, $parametros] = filtroDependencia('p');
    $filas = consultarPreparado(
        "SELECT
            SUM(m.fecha_limite < CURDATE()) AS vencidas,
            SUM(m.fecha_limite BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 15 DAY)) AS por_vencer
         FROM metas m
         INNER JOIN programas p ON m.id_programa = p.id_programa
         WHERE m.estado <> 'Terminado' AND m.fecha_limite IS NOT NULL" . $filtro,
        $tipos, $parametros
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
    [$filtro, $tipos, $parametros] = filtroDependencia('p');
    $sqlBase .= $filtro . ' ';

    $vencidas  = consultarPreparado($sqlBase . "AND m.fecha_limite < CURDATE() ORDER BY m.fecha_limite ASC", $tipos, $parametros);
    $porVencer = consultarPreparado($sqlBase . "AND m.fecha_limite BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 15 DAY) ORDER BY m.fecha_limite ASC", $tipos, $parametros);
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
    if (!esEnlace()) TablaAcuerdosPendientes();
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
         . '<a href="#" onclick="abrirFicha(' . $id . ');return false;" class="fs-5 me-2" title="Ficha de la meta"><i class="bi bi-eye-fill" style="color:#c89b00;"></i></a>'
         . (esEnlace() ? '' : '<a href="#" onclick="abrirObservaciones(' . $id . ');return false;" class="fs-5" title="Observaciones y acuerdos"><i class="bi bi-chat-left-text-fill" style="color:#5b7db1;"></i></a>');
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
    [$filtro, $tipos, $parametros] = filtroDependencia('p');
    $sql .= $filtro;

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
    verificarMetaDeMiDependencia($id_meta);
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

    <?php if (esEnlace()): ?>
        <div class="alert alert-info py-2" style="font-size:.82rem;">
            <i class="bi bi-info-circle me-1"></i>Tu avance quedará <strong>En revisión</strong>. Se suma a la meta cuando Planeación lo apruebe.
        </div>
    <?php endif; ?>

    <form id="frmAvance" onsubmit="guardarAvance(event)">
        <input type="hidden" name="id_meta" value="<?= (int)$meta['id_meta'] ?>">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label fw-semibold" for="valor_avance">Cantidad avanzada (<?= e($meta['unidad_medida']) ?>) <span class="text-danger">*</span></label>
                <input type="number" step="0.01" min="0.01" class="form-control" id="valor_avance" name="valor_avance" required>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold" for="fecha_avance">Fecha del avance <span class="text-danger">*</span></label>
                <input type="date" class="form-control" id="fecha_avance" name="fecha_avance" value="<?= $hoy ?>" max="<?= $hoy ?>" required>
            </div>
            <div class="col-12">
                <label class="form-label fw-semibold" for="descripcion_avance">Descripción</label>
                <textarea class="form-control" id="descripcion_avance" name="descripcion" rows="2" maxlength="255" placeholder="¿Qué se realizó? Ej. Se entregaron 20 despensas en la comunidad X"></textarea>
            </div>
            <div class="col-12">
                <label class="form-label fw-semibold" for="evidencias_avance">Evidencias <span class="text-muted fw-normal">(fotos o PDF, opcional)</span></label>
                <input type="file" class="form-control" id="evidencias_avance" accept="image/jpeg,image/png,image/webp,application/pdf" multiple>
                <div class="form-text">Hasta <?= EVIDENCIA_MAX_POR_AVANCE ?> archivos de máximo 5 MB. Las fotos se reducen solas antes de subirse.</div>
            </div>
        </div>
        <div class="mt-3 d-flex gap-2">
            <button type="submit" class="btn btn-primary" id="btnGuardarAvance"><i class="bi bi-save me-1"></i><?= esEnlace() ? 'Enviar a revisión' : 'Guardar avance' ?></button>
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        </div>
    </form>
<?php
}

/*
 * Guarda un avance.
 *  - Planeación: el avance queda Aprobado y en la MISMA TRANSACCIÓN se suma a
 *    metas.meta_alcanzada y se recalcula metas.estado (como siempre).
 *  - Enlace: el avance queda "En revisión" y la meta no cambia hasta que
 *    Planeación lo apruebe (AprobarAvance).
 * Responde el id del avance para que el navegador suba las evidencias.
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
    $enRevision  = esEnlace();

    verificarMetaDeMiDependencia($id_meta);

    // Validaciones del servidor (aunque el navegador ya valide, aquí es obligatorio)
    if ($valor <= 0) {
        responderJson(['ok' => false, 'mensaje' => 'La cantidad debe ser mayor a 0.']);
    }
    $fechaObj = DateTime::createFromFormat('!Y-m-d', $fecha);
    if (!$fechaObj || $fechaObj->format('Y-m-d') !== $fecha) {
        responderJson(['ok' => false, 'mensaje' => 'La fecha no es válida.']);
    }
    if ($fecha > fechaHoy()) {
        responderJson(['ok' => false, 'mensaje' => 'La fecha del avance no puede ser futura.']);
    }

    mysqli_begin_transaction($conexion);
    try {
        // Bloquea la fila de la meta mientras se actualiza (evita que dos capturas se pisen)
        $filas = consultarPreparado("SELECT actividad, meta_programada, meta_alcanzada FROM metas WHERE id_meta = ? FOR UPDATE", 'i', [$id_meta]);
        if (!$filas) throw new Exception('La meta no existe.');
        $meta = $filas[0];

        $estadoRevision = $enRevision ? 'En revisión' : 'Aprobado';
        ejecutarPreparado(
            "INSERT INTO avances_meta (id_meta, valor_avance, descripcion, fecha_avance, registrado_por, estado_revision, revisado_por, revisado_en)
             VALUES (?, ?, ?, ?, ?, ?, ?, " . ($enRevision ? 'NULL' : 'NOW()') . ")",
            'idssisi', [$id_meta, $valor, $descripcion, $fecha, $id_usuario, $estadoRevision, $enRevision ? null : $id_usuario]
        );
        $id_avance = (int)mysqli_insert_id($conexion);

        if ($enRevision) {
            registrarBitacora('Enviar avance a revisión', 'Meta #' . $id_meta . ' (' . $meta['actividad'] . '): +' . formatoNumero($valor));
            $mensaje = 'Avance enviado a revisión. Se sumará a la meta cuando Planeación lo apruebe.';
        } else {
            [$nuevaAlcanzada, $nuevoEstado] = sumarAvanceAMeta($id_meta, $meta, $valor);
            registrarBitacora('Registrar avance', 'Meta #' . $id_meta . ' (' . $meta['actividad'] . '): +' . formatoNumero($valor) . ', nuevo total ' . formatoNumero($nuevaAlcanzada) . ', estado ' . $nuevoEstado);
            $mensaje = 'Avance registrado. Total alcanzado: ' . formatoNumero($nuevaAlcanzada) . ' — Estado: ' . $nuevoEstado;
        }

        mysqli_commit($conexion);
        responderJson(['ok' => true, 'id_avance' => $id_avance, 'en_revision' => $enRevision, 'mensaje' => $mensaje]);

    } catch (Throwable $error) {
        mysqli_rollback($conexion);
        error_log('GuardarAvance: ' . $error);
        responderJson(['ok' => false, 'mensaje' => 'No se guardó nada. Inténtalo de nuevo.']);
    }
}

// Suma un avance aprobado a la meta y recalcula su estado (la meta ya debe estar bloqueada con FOR UPDATE).
function sumarAvanceAMeta($id_meta, array $meta, $valor)
{
    $nuevaAlcanzada = (float)$meta['meta_alcanzada'] + (float)$valor;
    $nuevoEstado    = calcularEstado((float)$meta['meta_programada'], $nuevaAlcanzada);
    ejecutarPreparado("UPDATE metas SET meta_alcanzada = ?, estado = ? WHERE id_meta = ?", 'dsi', [$nuevaAlcanzada, $nuevoEstado, $id_meta]);
    return [$nuevaAlcanzada, $nuevoEstado];
}

// Etiqueta del estado de revisión de un avance.
function etiquetaRevision($estado)
{
    $c = ['En revisión' => ['#7d5a00', '#fff3cd', 'bi-hourglass-split'], 'Devuelto' => ['#7b1f1f', '#fde8e8', 'bi-arrow-return-left'], 'Aprobado' => ['#1a6b3c', '#d4f4e2', 'bi-check-circle']];
    [$texto, $fondo, $icono] = $c[$estado] ?? ['#333', '#eee', 'bi-circle'];
    return '<span style="background:' . $fondo . ';color:' . $texto . ';font-size:.7rem;font-weight:700;padding:2px 9px;border-radius:20px;white-space:nowrap;">'
         . '<i class="bi ' . $icono . ' me-1"></i>' . e($estado) . '</span>';
}

// Evidencias agrupadas por avance (para no consultar una vez por avance).
function evidenciasPorAvance(array $evidencias)
{
    $porAvance = [];
    foreach ($evidencias as $ev) {
        if ($ev['id_avance']) $porAvance[(int)$ev['id_avance']][] = $ev;
    }
    return $porAvance;
}

// Línea de tiempo de avances de una meta (HTML). Se usa sola y dentro de la ficha.
function LineaTiempoAvances($id_meta)
{
    $avances = consultarPreparado(
        "SELECT a.*, u.nombre, r.nombre AS nombre_revisor
         FROM avances_meta a
         LEFT JOIN usuarios u ON a.registrado_por = u.id_usuario
         LEFT JOIN usuarios r ON a.revisado_por = r.id_usuario
         WHERE a.id_meta = ?
         ORDER BY a.fecha_avance DESC, a.id_avance DESC",
        'i', [$id_meta]
    );

    if (empty($avances)) {
        echo '<p class="text-muted" style="font-size:.85rem;"><i class="bi bi-info-circle me-1"></i>Esta meta todavía no tiene avances registrados.</p>';
        return;
    }
    $evidencias = evidenciasPorAvance(evidenciasDeMeta($id_meta));

    echo '<div style="border-left:3px solid #D4AF37;margin-left:8px;padding-left:18px;">';
    foreach ($avances as $a) {
        $aprobado = $a['estado_revision'] === 'Aprobado';
        echo '<div style="position:relative;margin-bottom:1rem;">'
           . '<span style="position:absolute;left:-27px;top:3px;width:15px;height:15px;border-radius:50%;background:' . ($aprobado ? '#0A2647' : '#adb5bd') . ';border:3px solid #D4AF37;"></span>'
           . '<div style="font-size:.78rem;color:#6c757d;">' . formatoFecha($a['fecha_avance'])
           . ' · registrado por <strong>' . e($a['nombre'] ?? 'Usuario eliminado') . '</strong>'
           . ($aprobado ? '' : ' ' . etiquetaRevision($a['estado_revision'])) . '</div>'
           . '<div style="font-weight:700;color:' . ($aprobado ? '#1a6b3c' : '#6c757d') . ';">+' . formatoNumero($a['valor_avance'])
           . ($aprobado ? '' : ' <span style="font-weight:400;font-size:.75rem;">(no se suma hasta aprobarse)</span>') . '</div>'
           . ($a['descripcion'] !== null && $a['descripcion'] !== '' ? '<div style="font-size:.85rem;">' . e($a['descripcion']) . '</div>' : '');
        if ($a['estado_revision'] === 'Devuelto' && $a['comentario_revision']) {
            echo '<div class="mt-1 p-2 rounded" style="background:#fde8e8;font-size:.8rem;"><strong>Devuelto por ' . e($a['nombre_revisor'] ?? 'Planeación') . ':</strong> ' . e($a['comentario_revision']) . '</div>';
        }
        if (!empty($evidencias[(int)$a['id_avance']])) {
            echo '<div class="mt-2">' . htmlEvidencias($evidencias[(int)$a['id_avance']]) . '</div>';
        }
        echo '</div>';
    }
    echo '</div>';
}

function HistorialAvances()
{
    $id_meta = (int)($_GET['idr'] ?? 0);
    verificarMetaDeMiDependencia($id_meta);
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
    verificarMetaDeMiDependencia($id_meta);
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
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
        <h6 class="fw-bold mb-0" style="color:#0A2647;"><i class="bi bi-images me-2"></i>Evidencias</h6>
        <label class="btn btn-outline-primary btn-sm mb-0">
            <i class="bi bi-upload me-1"></i>Agregar fotos o PDF
            <input type="file" hidden accept="image/jpeg,image/png,image/webp,application/pdf" multiple
                   onchange="subirEvidenciasMeta(<?= (int)$id_meta ?>, this)">
        </label>
    </div>
    <div id="evidenciasMeta"><?= htmlEvidencias(evidenciasDeMeta($id_meta), 'Esta meta todavía no tiene evidencias.') ?></div>

    <hr>
    <h6 class="fw-bold mb-3" style="color:#0A2647;"><i class="bi bi-clock-history me-2"></i>Historial de avances</h6>
    <?php LineaTiempoAvances($id_meta); ?>
    <button class="btn btn-primary btn-sm mt-2" onclick="abrirFormAvance(<?= (int)$id_meta ?>)"><i class="bi bi-plus-circle me-1"></i>Registrar avance</button>

    <hr>
    <?php SeccionObservaciones($id_meta, esEnlace()); ?>
<?php
}


////////////////////// REVISIÓN DE AVANCES (Planeación aprueba lo que mandan los enlaces) //////////////////////

function VistaRevision()
{
    $pendientes = consultarPreparado(
        "SELECT a.*, u.nombre AS nombre_enlace, u.dependencia, m.actividad, m.unidad_medida,
                m.meta_programada, m.meta_alcanzada, p.nombre_programa, e.nombre_eje
         FROM avances_meta a
         INNER JOIN metas m     ON a.id_meta = m.id_meta
         INNER JOIN programas p ON m.id_programa = p.id_programa
         INNER JOIN ejes e      ON p.id_eje = e.id_eje
         LEFT JOIN usuarios u   ON a.registrado_por = u.id_usuario
         WHERE a.estado_revision = 'En revisión'
         ORDER BY a.creado_en ASC"
    );
    $recientes = consultarPreparado(
        "SELECT a.valor_avance, a.estado_revision, a.revisado_en, a.comentario_revision,
                m.actividad, m.id_meta, u.nombre AS nombre_enlace, r.nombre AS nombre_revisor
         FROM avances_meta a
         INNER JOIN metas m   ON a.id_meta = m.id_meta
         LEFT JOIN usuarios u ON a.registrado_por = u.id_usuario
         LEFT JOIN usuarios r ON a.revisado_por = r.id_usuario
         WHERE a.estado_revision IN ('Aprobado', 'Devuelto') AND a.revisado_en IS NOT NULL
           AND u.rol = 'enlace'
         ORDER BY a.revisado_en DESC LIMIT 15"
    );
    // Evidencias de todos los avances pendientes en una sola consulta
    $evidencias = [];
    if ($pendientes) {
        $ids = array_map(fn($a) => (int)$a['id_avance'], $pendientes);
        $evidencias = evidenciasPorAvance(consultarPreparado(
            "SELECT ev.*, 'En revisión' AS estado_revision, u.nombre AS nombre_usuario FROM evidencias ev
             LEFT JOIN usuarios u ON ev.subido_por = u.id_usuario
             WHERE ev.id_avance IN (" . implode(',', array_fill(0, count($ids), '?')) . ")
             ORDER BY ev.id_evidencia",
            str_repeat('i', count($ids)), $ids
        ));
    }
?>
    <h5 class="fw-bold mb-1" style="color:#0A2647;" id="tituloRevision"><i class="bi bi-clipboard-check me-2"></i>Revisión de avances</h5>
    <p class="text-muted mb-4" style="font-size:.85rem;">
        Avances que capturaron los enlaces de las dependencias. Al aprobarlos se suman a la meta; si los devuelves, el enlace ve tu comentario.
    </p>

    <?php if (!$pendientes): ?>
        <div class="aviso-alerta" style="--color:#1a7f4b;"><i class="bi bi-check-circle"></i><span>No hay avances pendientes de revisión.</span></div>
    <?php endif; ?>

    <?php foreach ($pendientes as $a):
        $programada = (float)$a['meta_programada'];
        $antes      = $programada > 0 ? round(min($a['meta_alcanzada'] / $programada, 1) * 100, 1) : 0;
        $despues    = $programada > 0 ? round(min(($a['meta_alcanzada'] + $a['valor_avance']) / $programada, 1) * 100, 1) : 0;
    ?>
        <div class="card mb-3" id="revision-<?= (int)$a['id_avance'] ?>">
            <div class="card-body">
                <div class="d-flex justify-content-between flex-wrap gap-2">
                    <div>
                        <div style="font-size:.72rem;color:#6c757d;text-transform:uppercase;letter-spacing:.04em;"><?= e($a['nombre_eje']) ?> › <?= e($a['nombre_programa']) ?></div>
                        <div class="fw-bold" style="color:#0A2647;"><?= e($a['actividad']) ?></div>
                        <div style="font-size:.8rem;" class="text-muted">
                            <i class="bi bi-person me-1"></i><?= e($a['nombre_enlace'] ?? 'Usuario eliminado') ?>
                            <?= $a['dependencia'] ? ' · ' . e($a['dependencia']) : '' ?> · enviado el <?= date('d/m/Y H:i', strtotime($a['creado_en'])) ?>
                        </div>
                    </div>
                    <div class="text-end">
                        <div class="fw-bold" style="font-size:1.3rem;color:#1a6b3c;">+<?= formatoNumero($a['valor_avance']) ?> <span style="font-size:.8rem;font-weight:400;"><?= e($a['unidad_medida']) ?></span></div>
                        <div style="font-size:.78rem;" class="text-muted">Avance de la meta: <?= $antes ?>% → <strong><?= $despues ?>%</strong></div>
                    </div>
                </div>
                <div class="mt-2" style="font-size:.85rem;">
                    <strong>Fecha del avance:</strong> <?= formatoFecha($a['fecha_avance']) ?>
                    <?php if ($a['descripcion']): ?><br><strong>Descripción:</strong> <?= e($a['descripcion']) ?><?php endif; ?>
                </div>
                <div class="mt-2"><?= htmlEvidencias($evidencias[(int)$a['id_avance']] ?? [], 'El enlace no adjuntó evidencias.') ?></div>
                <div class="mt-3 d-flex gap-2 flex-wrap">
                    <button class="btn btn-success btn-sm" onclick="aprobarAvance(<?= (int)$a['id_avance'] ?>)"><i class="bi bi-check-lg me-1"></i>Aprobar</button>
                    <button class="btn btn-outline-danger btn-sm" onclick="devolverAvance(<?= (int)$a['id_avance'] ?>)"><i class="bi bi-arrow-return-left me-1"></i>Devolver con comentario</button>
                    <button class="btn btn-link btn-sm" onclick="abrirFicha(<?= (int)$a['id_meta'] ?>)">Ver ficha de la meta</button>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <?php if ($recientes): ?>
        <h6 class="fw-bold mt-4 mb-2" style="color:#0A2647;">Revisados recientemente</h6>
        <div class="table-responsive">
            <table class="table table-sm table-bordered align-middle" style="font-size:.82rem;">
                <thead class="table-light"><tr><th>Fecha</th><th>Enlace</th><th>Meta</th><th>Cantidad</th><th>Resultado</th><th>Revisó</th></tr></thead>
                <tbody>
                <?php foreach ($recientes as $r): ?>
                    <tr>
                        <td class="text-nowrap"><?= date('d/m/Y H:i', strtotime($r['revisado_en'])) ?></td>
                        <td><?= e($r['nombre_enlace'] ?? '—') ?></td>
                        <td><a href="#" onclick="abrirFicha(<?= (int)$r['id_meta'] ?>);return false;"><?= e($r['actividad']) ?></a></td>
                        <td>+<?= formatoNumero($r['valor_avance']) ?></td>
                        <td><?= etiquetaRevision($r['estado_revision']) ?><?= $r['comentario_revision'] ? '<div class="text-muted" style="font-size:.75rem;">' . e($r['comentario_revision']) . '</div>' : '' ?></td>
                        <td><?= e($r['nombre_revisor'] ?? '—') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
<?php
}

// Aprueba un avance "En revisión": lo suma a la meta en una transacción.
function AprobarAvance()
{
    global $obj;
    $conexion  = $obj->conexion();
    $id_avance = (int)($_POST['id_avance'] ?? 0);

    mysqli_begin_transaction($conexion);
    try {
        $avance = consultarPreparado("SELECT id_meta, valor_avance, estado_revision FROM avances_meta WHERE id_avance = ? FOR UPDATE", 'i', [$id_avance]);
        if (!$avance || $avance[0]['estado_revision'] !== 'En revisión') {
            mysqli_rollback($conexion);
            responderJson(['ok' => false, 'mensaje' => 'Este avance ya fue revisado por alguien más. Recarga la lista.']);
        }
        $id_meta = (int)$avance[0]['id_meta'];
        $meta    = consultarPreparado("SELECT actividad, meta_programada, meta_alcanzada FROM metas WHERE id_meta = ? FOR UPDATE", 'i', [$id_meta])[0];

        [$nuevaAlcanzada, $nuevoEstado] = sumarAvanceAMeta($id_meta, $meta, $avance[0]['valor_avance']);
        ejecutarPreparado(
            "UPDATE avances_meta SET estado_revision = 'Aprobado', revisado_por = ?, revisado_en = NOW(), comentario_revision = NULL WHERE id_avance = ?",
            'ii', [(int)$_SESSION['id_usuario'], $id_avance]
        );
        registrarBitacora('Aprobar avance', 'Avance #' . $id_avance . ' de la meta #' . $id_meta . ' (' . $meta['actividad'] . '): +'
            . formatoNumero($avance[0]['valor_avance']) . ', nuevo total ' . formatoNumero($nuevaAlcanzada) . ', estado ' . $nuevoEstado);
        mysqli_commit($conexion);
        responderJson(['ok' => true, 'mensaje' => 'Avance aprobado. La meta lleva ' . formatoNumero($nuevaAlcanzada) . ' (' . $nuevoEstado . ').']);
    } catch (Throwable $error) {
        mysqli_rollback($conexion);
        error_log('AprobarAvance: ' . $error);
        responderJson(['ok' => false, 'mensaje' => 'No se pudo aprobar. Inténtalo de nuevo.']);
    }
}

function DevolverAvance()
{
    $id_avance  = (int)($_POST['id_avance'] ?? 0);
    $comentario = trim($_POST['comentario'] ?? '');
    if (mb_strlen($comentario) < 5)   responderJson(['ok' => false, 'mensaje' => 'Explica al enlace por qué se devuelve (mínimo 5 caracteres).']);
    if (mb_strlen($comentario) > 500) responderJson(['ok' => false, 'mensaje' => 'El comentario admite máximo 500 caracteres.']);

    $cambiadas = ejecutarPreparado(
        "UPDATE avances_meta SET estado_revision = 'Devuelto', revisado_por = ?, revisado_en = NOW(), comentario_revision = ?
         WHERE id_avance = ? AND estado_revision = 'En revisión'",
        'isi', [(int)$_SESSION['id_usuario'], $comentario, $id_avance]
    );
    if ($cambiadas !== 1) responderJson(['ok' => false, 'mensaje' => 'Este avance ya fue revisado por alguien más. Recarga la lista.']);
    try {
        registrarBitacora('Devolver avance', 'Avance #' . $id_avance . ': ' . $comentario);
    } catch (Throwable $error) {
    }
    responderJson(['ok' => true, 'mensaje' => 'Avance devuelto al enlace con tu comentario.']);
}

// Números para los avisos del menú: pendientes de revisar (Planeación) o devueltos (enlace).
function ContarPendientes()
{
    if (esEnlace()) {
        $filas = consultarPreparado(
            "SELECT SUM(estado_revision = 'En revisión') AS en_revision, SUM(estado_revision = 'Devuelto') AS devueltos
             FROM avances_meta WHERE registrado_por = ?",
            'i', [(int)$_SESSION['id_usuario']]
        );
        responderJson(['en_revision' => (int)($filas[0]['en_revision'] ?? 0), 'devueltos' => (int)($filas[0]['devueltos'] ?? 0)]);
    }
    $filas = consultarPreparado("SELECT COUNT(*) AS n FROM avances_meta WHERE estado_revision = 'En revisión'");
    responderJson(['pendientes' => (int)$filas[0]['n']]);
}


////////////////////// PANEL DEL ENLACE //////////////////////

// Números del inicio del enlace: sus metas por estado y sus avances.
function ResumenEnlace()
{
    [$filtro, $tipos, $parametros] = filtroDependencia('p');
    $metas = consultarPreparado(
        "SELECT COUNT(*) AS total, SUM(m.estado = 'Terminado') AS terminadas,
                SUM(m.estado = 'En Proceso') AS en_proceso, SUM(m.estado = 'Pendiente') AS pendientes
         FROM metas m INNER JOIN programas p ON m.id_programa = p.id_programa WHERE 1 = 1" . $filtro,
        $tipos, $parametros
    )[0];
    responderJson([
        'dependencia' => dependenciaDelEnlace(),
        'total'       => (int)$metas['total'],
        'terminadas'  => (int)$metas['terminadas'],
        'en_proceso'  => (int)$metas['en_proceso'],
        'pendientes'  => (int)$metas['pendientes'],
    ]);
}

// Avances que mandó el enlace, con su resultado.
function VistaMisAvances()
{
    $avances = consultarPreparado(
        "SELECT a.*, m.actividad, m.unidad_medida, r.nombre AS nombre_revisor,
                (SELECT COUNT(*) FROM evidencias ev WHERE ev.id_avance = a.id_avance) AS num_evidencias
         FROM avances_meta a
         INNER JOIN metas m   ON a.id_meta = m.id_meta
         LEFT JOIN usuarios r ON a.revisado_por = r.id_usuario
         WHERE a.registrado_por = ?
         ORDER BY FIELD(a.estado_revision, 'Devuelto', 'En revisión', 'Aprobado'), a.creado_en DESC
         LIMIT 200",
        'i', [(int)$_SESSION['id_usuario']]
    );
?>
    <h5 class="fw-bold mb-1" style="color:#0A2647;"><i class="bi bi-send-check me-2"></i>Mis avances enviados</h5>
    <p class="text-muted mb-3" style="font-size:.85rem;">Primero aparecen los devueltos (revisa el comentario y registra el avance corregido), luego los que esperan revisión.</p>
    <?php if (!$avances): ?>
        <p class="text-muted">Todavía no has registrado avances. Entra a <strong>Mis metas</strong> y usa el botón <i class="bi bi-plus-circle-fill" style="color:#2fa86a;"></i>.</p>
        <?php return; ?>
    <?php endif; ?>
    <div class="table-responsive">
        <table class="table table-sm table-bordered align-middle" style="font-size:.84rem;">
            <thead class="table-light"><tr><th>Enviado</th><th>Meta</th><th>Cantidad</th><th>Evidencias</th><th>Estado</th><th>Comentario de Planeación</th></tr></thead>
            <tbody>
            <?php foreach ($avances as $a): ?>
                <tr>
                    <td class="text-nowrap"><?= date('d/m/Y', strtotime($a['creado_en'])) ?></td>
                    <td><a href="#" onclick="abrirFicha(<?= (int)$a['id_meta'] ?>);return false;"><?= e($a['actividad']) ?></a></td>
                    <td class="text-nowrap">+<?= formatoNumero($a['valor_avance']) ?> <?= e($a['unidad_medida']) ?></td>
                    <td class="text-center"><?= (int)$a['num_evidencias'] ?></td>
                    <td><?= etiquetaRevision($a['estado_revision']) ?></td>
                    <td><?= $a['comentario_revision'] ? e($a['comentario_revision']) . '<div class="text-muted" style="font-size:.72rem;">' . e($a['nombre_revisor'] ?? '') . '</div>' : '<span class="text-muted">—</span>' ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
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
                    <input type="password" class="form-control" name="nueva" id="pass_nueva" minlength="8" required autocomplete="new-password">
                    <div class="form-text">Mínimo 8 caracteres.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Confirmar nueva contraseña <span class="text-danger">*</span></label>
                    <input type="password" class="form-control" name="confirmar" id="pass_confirmar" minlength="8" required autocomplete="new-password">
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

    if (mb_strlen($nueva) < MINIMO_CONTRASENA) responderJson(['ok' => false, 'mensaje' => 'La nueva contraseña debe tener al menos ' . MINIMO_CONTRASENA . ' caracteres.']);
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



////////////////////// 10. AVANCE FÍSICO VS FINANCIERO //////////////////////

/*
 * Físico     = lo realizado:  alcanzada / programada (máximo 100%)
 * Financiero = lo gastado:    presupuesto ejercido / asignado (puede pasar de 100% = sobreejercicio)
 * Si se gastó bastante más de lo que se avanzó, es una señal de alerta para Contraloría.
 */
function clasificarFisicoFinanciero($fisico, $financiero)
{
    if ($financiero === null)        return 'Sin presupuesto';
    if ($financiero > 100)           return 'Sobreejercicio';
    if ($financiero - $fisico > 25)  return 'Gasto adelantado';
    if ($fisico - $financiero > 25)  return 'Gasto rezagado';
    return 'Equilibrado';
}

function VistaFisicoFinanciero()
{
    $ejes = consultarPreparado("SELECT id_eje, nombre_eje FROM ejes ORDER BY nombre_eje ASC");
?>
    <h5 class="fw-bold mb-1" style="color:#0A2647;">
        <i class="bi bi-cash-coin me-2"></i>Avance Físico vs Financiero
    </h5>
    <p class="text-muted mb-3" style="font-size:.85rem;">
        Compara lo que se ha <strong>realizado</strong> (avance físico) contra lo que se ha <strong>gastado</strong> (avance financiero) en cada meta.
    </p>

    <div class="d-flex align-items-end gap-2 mb-3">
        <div>
            <label class="form-label fw-semibold" style="font-size:.8rem;">Eje</label>
            <select class="form-select form-select-sm" id="ffEje" onchange="cargarFisicoFinanciero()">
                <option value="0">Todos los ejes</option>
                <?php foreach ($ejes as $eje): ?>
                    <option value="<?= (int)$eje['id_eje'] ?>"><?= e($eje['nombre_eje']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="row g-3 mb-3" id="ffTarjetas"></div>

    <div class="row g-3 mb-3">
        <div class="col-lg-7">
            <div class="card h-100"><div class="card-body">
                <h6 class="fw-bold mb-1" style="color:#0A2647;">Mapa de metas</h6>
                <p class="text-muted mb-2" style="font-size:.75rem;">Cada punto es una meta. <strong>Arriba de la línea</strong> = se gastó más de lo que se avanzó.</p>
                <canvas id="graficaFisicoFinanciero"></canvas>
            </div></div>
        </div>
        <div class="col-lg-5">
            <div class="card h-100"><div class="card-body" style="font-size:.82rem;">
                <h6 class="fw-bold mb-2" style="color:#0A2647;">¿Cómo se lee?</h6>
                <p class="mb-2"><span class="badge" style="background:#2fa86a;">Equilibrado</span> el gasto va de acuerdo con lo realizado (diferencia de 25 puntos o menos).</p>
                <p class="mb-2"><span class="badge" style="background:#e0a800;">Gasto adelantado</span> se gastó más de 25 puntos por encima de lo realizado. <strong>Revisar.</strong></p>
                <p class="mb-2"><span class="badge" style="background:#c0392b;">Sobreejercicio</span> se gastó más del 100% del presupuesto asignado.</p>
                <p class="mb-2"><span class="badge" style="background:#5b7db1;">Gasto rezagado</span> se avanzó más de lo que se ha pagado (puede haber pagos pendientes).</p>
                <p class="mb-0"><span class="badge" style="background:#adb5bd;">Sin presupuesto</span> la meta no tiene presupuesto asignado capturado.</p>
            </div></div>
        </div>
    </div>

    <div class="table-responsive">
        <table id="TBFisicoFinanciero" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
            <thead>
                <tr>
                    <th>Eje</th><th>Programa</th><th>Actividad</th><th>Asignado</th><th>Ejercido</th>
                    <th>% Físico</th><th>% Financiero</th><th>Diferencia</th><th>Situación</th><th style="text-align:center;">Acciones</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
<?php
}

function DatosFisicoFinanciero()
{
    $id_eje = (int)($_GET['eje'] ?? 0);
    $sql = "SELECT m.id_meta, m.actividad, m.meta_programada, m.meta_alcanzada,
                   m.presupuesto_asignado, m.presupuesto_ejercido, p.nombre_programa, e.nombre_eje
            FROM metas m
            INNER JOIN programas p ON m.id_programa = p.id_programa
            INNER JOIN ejes e      ON p.id_eje = e.id_eje";
    $metas = $id_eje > 0
        ? consultarPreparado($sql . " WHERE e.id_eje = ? ORDER BY e.nombre_eje, p.nombre_programa", 'i', [$id_eje])
        : consultarPreparado($sql . " ORDER BY e.nombre_eje, p.nombre_programa");

    $lista = [];
    $totalAsignado = 0;
    $totalEjercido = 0;
    $conteo = ['Equilibrado' => 0, 'Gasto adelantado' => 0, 'Sobreejercicio' => 0, 'Gasto rezagado' => 0, 'Sin presupuesto' => 0];

    foreach ($metas as $m) {
        $programada = (float)$m['meta_programada'];
        $asignado   = (float)$m['presupuesto_asignado'];
        $ejercido   = (float)$m['presupuesto_ejercido'];
        $fisico     = $programada > 0 ? round(min((float)$m['meta_alcanzada'] / $programada, 1) * 100, 1) : 0;
        $financiero = $asignado > 0 ? round($ejercido / $asignado * 100, 1) : null;
        $situacion  = clasificarFisicoFinanciero($fisico, $financiero);

        $totalAsignado += $asignado;
        $totalEjercido += $ejercido;
        $conteo[$situacion]++;

        $lista[] = [
            'id_meta'         => (int)$m['id_meta'],
            'nombre_eje'      => $m['nombre_eje'],
            'nombre_programa' => $m['nombre_programa'],
            'actividad'       => $m['actividad'],
            'asignado'        => $asignado,
            'ejercido'        => $ejercido,
            'fisico'          => $fisico,
            'financiero'      => $financiero,
            'diferencia'      => $financiero === null ? null : round($financiero - $fisico, 1),
            'situacion'       => $situacion,
        ];
    }

    responderJson([
        'data'    => $lista,
        'resumen' => [
            'asignado'   => $totalAsignado,
            'ejercido'   => $totalEjercido,
            'porcentaje' => $totalAsignado > 0 ? round($totalEjercido / $totalAsignado * 100, 1) : 0,
            'conteo'     => $conteo,
        ],
    ]);
}


////////////////////// 11. SEGUIMIENTO POR DEPENDENCIA //////////////////////

// Nombre de la dependencia: la columna "responsable" del programa.
function sqlDependencia()
{
    return "COALESCE(NULLIF(TRIM(p.responsable), ''), 'Sin responsable asignado')";
}

function VistaDependencias()
{
?>
    <h5 class="fw-bold mb-1" style="color:#0A2647;">
        <i class="bi bi-building me-2"></i>Seguimiento por Dependencia
    </h5>
    <p class="text-muted mb-3" style="font-size:.85rem;">
        Situación de las metas de cada área responsable. Las dependencias con más metas en rojo aparecen primero.
    </p>
    <div class="row g-3 mb-3" id="depTarjetas"></div>
    <div class="card mb-3"><div class="card-body">
        <h6 class="fw-bold mb-2" style="color:#0A2647;">Semáforo por dependencia <span class="text-muted fw-normal" style="font-size:.75rem;">(las 12 con más metas)</span></h6>
        <div style="position:relative;height:380px;"><canvas id="graficaDependencias"></canvas></div>
    </div></div>
    <div class="table-responsive">
        <table id="TBDependencias" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
            <thead>
                <tr>
                    <th>Dependencia</th><th>Programas</th><th>Metas</th><th>Terminadas</th><th>Vencidas</th>
                    <th>Semáforo</th><th>Avance promedio</th><th>% Ejercido</th><th style="text-align:center;">Ver</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
<?php
}

function DatosDependencias()
{
    $dep = sqlDependencia();

    // Programas por dependencia (incluye las que todavía no tienen metas)
    $programas = consultarPreparado("SELECT $dep AS dependencia, COUNT(*) AS total FROM programas p GROUP BY dependencia");

    $metas = consultarPreparado(
        "SELECT $dep AS dependencia, m.meta_programada, m.meta_alcanzada, m.estado, m.fecha_inicio, m.fecha_limite,
                m.presupuesto_asignado, m.presupuesto_ejercido,
                (m.fecha_limite < CURDATE() AND m.estado <> 'Terminado') AS vencida, " . columnasDias() . "
         FROM metas m
         INNER JOIN programas p ON m.id_programa = p.id_programa"
    );

    $porDep = [];
    foreach ($programas as $p) {
        $porDep[$p['dependencia']] = [
            'dependencia' => $p['dependencia'], 'programas' => (int)$p['total'], 'metas' => 0, 'terminadas' => 0,
            'vencidas' => 0, 'verde' => 0, 'amarillo' => 0, 'rojo' => 0, 'sin_fecha' => 0,
            'suma_avance' => 0, 'asignado' => 0, 'ejercido' => 0,
        ];
    }
    $claves = ['Verde' => 'verde', 'Amarillo' => 'amarillo', 'Rojo' => 'rojo', 'Sin fecha' => 'sin_fecha'];
    foreach ($metas as $m) {
        $dep = $m['dependencia'];
        $s   = calcularSemaforo($m);
        $porDep[$dep]['metas']++;
        $porDep[$dep]['suma_avance'] += $s['real'];
        $porDep[$dep]['asignado']    += (float)$m['presupuesto_asignado'];
        $porDep[$dep]['ejercido']    += (float)$m['presupuesto_ejercido'];
        if ($m['estado'] === 'Terminado') $porDep[$dep]['terminadas']++;
        if ((int)$m['vencida'] === 1)     $porDep[$dep]['vencidas']++;
        $porDep[$dep][$claves[$s['color']]]++;
    }

    $lista = [];
    foreach ($porDep as $d) {
        $d['avance']     = $d['metas'] > 0 ? round($d['suma_avance'] / $d['metas'], 1) : null;
        $d['financiero'] = $d['asignado'] > 0 ? round($d['ejercido'] / $d['asignado'] * 100, 1) : null;
        unset($d['suma_avance']);
        $lista[] = $d;
    }
    // Primero las que tienen más metas en rojo, luego más vencidas, luego menor avance
    usort($lista, fn($a, $b) => [$b['rojo'], $b['vencidas'], $a['avance'] ?? 101] <=> [$a['rojo'], $a['vencidas'], $b['avance'] ?? 101]);

    responderJson(['data' => $lista]);
}

// Metas de una dependencia (modal)
function MetasDependencia()
{
    $dependencia = trim($_GET['dep'] ?? '');
    $metas = consultarPreparado(
        "SELECT m.id_meta, m.actividad, m.unidad_medida, m.meta_programada, m.meta_alcanzada, m.estado,
                m.fecha_inicio, m.fecha_limite, p.nombre_programa, " . columnasDias() . "
         FROM metas m
         INNER JOIN programas p ON m.id_programa = p.id_programa
         WHERE " . sqlDependencia() . " = ?
         ORDER BY p.nombre_programa, m.id_meta",
        's', [$dependencia]
    );
?>
    <h6 class="fw-bold mb-3" style="color:#0A2647;"><i class="bi bi-building me-2"></i><?= e($dependencia) ?></h6>
    <?php if (empty($metas)): ?>
        <p class="text-muted">Esta dependencia todavía no tiene metas registradas.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sm table-bordered align-middle" style="font-size:.84rem;">
                <thead><tr>
                    <th style="background:#0A2647;color:#fff;">Programa</th>
                    <th style="background:#0A2647;color:#fff;">Actividad</th>
                    <th style="background:#0A2647;color:#fff;">Avance</th>
                    <th style="background:#0A2647;color:#fff;">Semáforo</th>
                    <th style="background:#0A2647;color:#fff;">Fecha límite</th>
                    <th style="background:#0A2647;color:#fff;text-align:center;">Acciones</th>
                </tr></thead>
                <tbody>
                <?php foreach ($metas as $m): $s = calcularSemaforo($m); ?>
                    <tr>
                        <td><?= e($m['nombre_programa']) ?></td>
                        <td><?= e($m['actividad']) ?></td>
                        <td class="text-nowrap"><?= formatoNumero($m['meta_alcanzada']) ?> / <?= formatoNumero($m['meta_programada']) ?> (<?= $s['real'] ?>%)</td>
                        <td><?= circuloSemaforo($s['color']) ?></td>
                        <td><?= formatoFecha($m['fecha_limite']) ?></td>
                        <td class="text-center text-nowrap"><?= botonesAccionMeta($m['id_meta']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif;
}


////////////////////// 12. CALENDARIO DE VENCIMIENTOS //////////////////////

function VistaCalendario()
{
?>
    <h5 class="fw-bold mb-1" style="color:#0A2647;">
        <i class="bi bi-calendar-event me-2"></i>Calendario de Vencimientos
    </h5>
    <p class="text-muted mb-3" style="font-size:.85rem;">Fechas límite de las metas, con el color de su semáforo. Haz clic en una meta para ver su ficha.</p>

    <div class="card mb-3"><div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <button class="btn btn-outline-secondary btn-sm" onclick="moverCalendario(-1)" title="Mes anterior"><i class="bi bi-chevron-left"></i></button>
            <h5 class="fw-bold mb-0" style="color:#0A2647;" id="calTitulo">—</h5>
            <button class="btn btn-outline-secondary btn-sm" onclick="moverCalendario(1)" title="Mes siguiente"><i class="bi bi-chevron-right"></i></button>
        </div>
        <div class="calendario-metas" id="calCuadricula"></div>
        <div class="d-flex flex-wrap gap-3 mt-3" style="font-size:.75rem;">
            <span><?= circuloSemaforo('Verde') ?></span><span><?= circuloSemaforo('Amarillo') ?></span>
            <span><?= circuloSemaforo('Rojo') ?></span><span><?= circuloSemaforo('Sin fecha') ?></span>
            <span><i class="bi bi-check-circle-fill" style="color:#1a6b3c;"></i> Terminada</span>
        </div>
    </div></div>

    <div class="card"><div class="card-body">
        <h6 class="fw-bold mb-2" style="color:#0A2647;"><i class="bi bi-list-ul me-2"></i>Metas que vencen en el mes</h6>
        <div id="calLista"></div>
    </div></div>
<?php
}

function DatosCalendario()
{
    $mes = $_GET['mes'] ?? '';
    if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $mes)) {
        $mes = substr(fechaHoy(), 0, 7);
    }
    $inicio = $mes . '-01';
    $fin    = date('Y-m-t', strtotime($inicio));

    $metas = consultarPreparado(
        "SELECT m.id_meta, m.actividad, m.meta_programada, m.meta_alcanzada, m.estado, m.fecha_inicio, m.fecha_limite,
                p.nombre_programa, " . columnasDias() . "
         FROM metas m
         INNER JOIN programas p ON m.id_programa = p.id_programa
         WHERE m.fecha_limite BETWEEN ? AND ?
         ORDER BY m.fecha_limite, m.id_meta",
        'ss', [$inicio, $fin]
    );

    $lista = [];
    foreach ($metas as $m) {
        $s = calcularSemaforo($m);
        $lista[] = [
            'id_meta'   => (int)$m['id_meta'],
            'actividad' => $m['actividad'],
            'programa'  => $m['nombre_programa'],
            'fecha'     => $m['fecha_limite'],
            'estado'    => $m['estado'],
            'semaforo'  => $s['color'],
            'real'      => $s['real'],
        ];
    }
    responderJson(['mes' => $mes, 'hoy' => fechaHoy(), 'metas' => $lista]);
}


////////////////////// 13. OBSERVACIONES Y ACUERDOS POR META //////////////////////
// Usa la tabla observaciones_meta (sql/planeacion_extra.sql).


// $soloLectura: el enlace ve las observaciones de Planeación pero no agrega ni marca nada.
function SeccionObservaciones($id_meta, $soloLectura = false)
{
    try {
        $observaciones = consultarPreparado(
            "SELECT o.*, u.nombre FROM observaciones_meta o
             LEFT JOIN usuarios u ON o.registrado_por = u.id_usuario
             WHERE o.id_meta = ? ORDER BY o.id_observacion DESC",
            'i', [$id_meta]
        );
    } catch (Throwable $error) {
        echo '<div class="alert alert-warning" style="font-size:.85rem;"><i class="bi bi-database-exclamation me-1"></i>'
           . 'Para usar las observaciones hay que ejecutar <strong>sql/planeacion_extra.sql</strong> en la base de datos.</div>';
        return;
    }
    $hoy     = fechaHoy();
    $colores = ['Observación' => ['#0A2647', '#e8eef7'], 'Acuerdo' => ['#1a6b3c', '#d4f4e2'], 'Problema' => ['#7b1f1f', '#fde8e8']];
?>
    <h6 class="fw-bold mb-3" style="color:#0A2647;"><i class="bi bi-chat-left-text me-2"></i>Observaciones y acuerdos</h6>

    <?php if (!$soloLectura): ?>
    <form id="frmObservacion" onsubmit="guardarObservacion(event)" class="p-3 rounded mb-3" style="background:#f4f6fa;">
        <input type="hidden" name="id_meta" value="<?= (int)$id_meta ?>">
        <div class="row g-2">
            <div class="col-md-4">
                <select class="form-select form-select-sm" name="tipo" id="obsTipo" onchange="document.getElementById('obsFechaCaja').hidden = this.value !== 'Acuerdo'">
                    <?php foreach (TIPOS_OBSERVACION as $t): ?><option value="<?= e($t) ?>"><?= e($t) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-8" id="obsFechaCaja" hidden>
                <div class="input-group input-group-sm">
                    <span class="input-group-text">Fecha compromiso</span>
                    <input type="date" class="form-control" name="fecha_compromiso" id="obsFecha" min="<?= $hoy ?>">
                </div>
            </div>
            <div class="col-12">
                <textarea class="form-control form-control-sm" name="texto" id="obsTexto" rows="2" maxlength="2000" required
                          placeholder="Ej. Reunión con Obras Públicas: el avance se retrasa por lluvias; se reprograma para el 30/11."></textarea>
            </div>
            <div class="col-12"><button class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Agregar</button></div>
        </div>
    </form>
    <?php endif; ?>

    <?php if (empty($observaciones)): ?>
        <p class="text-muted" style="font-size:.85rem;">Todavía no hay observaciones para esta meta.</p>
    <?php endif; ?>
    <?php foreach ($observaciones as $o):
        [$texto, $fondo] = $colores[$o['tipo']] ?? $colores['Observación'];
        $esAcuerdo = $o['tipo'] === 'Acuerdo';
        $vencido   = $esAcuerdo && !$o['cumplido'] && $o['fecha_compromiso'] && $o['fecha_compromiso'] < $hoy;
    ?>
        <div class="p-2 mb-2 rounded" style="border:1px solid #dee2e6;border-left:4px solid <?= $texto ?>;font-size:.85rem;">
            <div class="d-flex justify-content-between flex-wrap gap-1">
                <div>
                    <span style="background:<?= $fondo ?>;color:<?= $texto ?>;font-size:.7rem;font-weight:700;padding:2px 9px;border-radius:20px;"><?= e($o['tipo']) ?></span>
                    <?php if ($esAcuerdo): ?>
                        <?php if ($o['cumplido']): ?>
                            <span class="badge bg-success">Cumplido</span>
                        <?php elseif ($vencido): ?>
                            <span class="badge bg-danger">Vencido el <?= formatoFecha($o['fecha_compromiso']) ?></span>
                        <?php elseif ($o['fecha_compromiso']): ?>
                            <span class="badge bg-warning text-dark">Compromiso: <?= formatoFecha($o['fecha_compromiso']) ?></span>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                <div class="text-muted" style="font-size:.75rem;">
                    <?= e($o['nombre'] ?? 'Usuario eliminado') ?> · <?= date('d/m/Y H:i', strtotime($o['creado_en'])) ?>
                    <?php if (!$soloLectura && $esAcuerdo && !$o['cumplido']): ?>
                        <a href="#" onclick="cumplirAcuerdo(<?= (int)$o['id_observacion'] ?>);return false;" class="ms-2" title="Marcar como cumplido"><i class="bi bi-check2-square" style="color:#1a6b3c;"></i></a>
                    <?php endif; ?>
                    <?php if (!$soloLectura && (int)$o['registrado_por'] === (int)$_SESSION['id_usuario']): ?>
                        <a href="#" onclick="eliminarObservacion(<?= (int)$o['id_observacion'] ?>);return false;" class="ms-2" title="Eliminar"><i class="bi bi-trash" style="color:#c0392b;"></i></a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="mt-1"><?= nl2br(e($o['texto'])) ?></div>
        </div>
    <?php endforeach;
}

function ModalObservaciones()
{
    $id_meta = (int)($_GET['idr'] ?? 0);
    $meta    = obtenerMeta($id_meta);
    if (!$meta) { echo '<div class="alert alert-warning">La meta no existe.</div>'; return; }
?>
    <p class="mb-3" style="font-size:.85rem;">
        <strong><?= e($meta['actividad']) ?></strong><br>
        <span class="text-muted"><?= e($meta['nombre_eje']) ?> › <?= e($meta['nombre_programa']) ?></span>
    </p>
<?php
    SeccionObservaciones($id_meta);
}

function GuardarObservacion()
{
    global $obj;
    $id_meta = (int)($_POST['id_meta'] ?? 0);
    $tipo    = $_POST['tipo'] ?? '';
    $texto   = trim($_POST['texto'] ?? '');
    $fecha   = trim($_POST['fecha_compromiso'] ?? '');

    if (!in_array($tipo, TIPOS_OBSERVACION, true)) responderJson(['ok' => false, 'mensaje' => 'Tipo no válido.']);
    if (mb_strlen($texto) < 3)                     responderJson(['ok' => false, 'mensaje' => 'Escribe la observación (mínimo 3 caracteres).']);
    if (mb_strlen($texto) > 2000)                  responderJson(['ok' => false, 'mensaje' => 'Máximo 2000 caracteres.']);
    $meta = obtenerMeta($id_meta);
    if (!$meta)                                    responderJson(['ok' => false, 'mensaje' => 'La meta no existe.']);

    $fechaCompromiso = null;
    if ($tipo === 'Acuerdo') {
        $d = DateTime::createFromFormat('Y-m-d', $fecha);
        if (!$d || $d->format('Y-m-d') !== $fecha) responderJson(['ok' => false, 'mensaje' => 'Un acuerdo necesita una fecha compromiso válida.']);
        $fechaCompromiso = $fecha;
    }

    try {
        $conexion   = $obj->conexion();
        $id_usuario = (int)$_SESSION['id_usuario'];
        $stmt = mysqli_prepare($conexion, "INSERT INTO observaciones_meta (id_meta, tipo, texto, fecha_compromiso, registrado_por) VALUES (?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'isssi', $id_meta, $tipo, $texto, $fechaCompromiso, $id_usuario);
        if (!mysqli_stmt_execute($stmt)) throw new Exception('No se pudo guardar.');
        mysqli_stmt_close($stmt);
    } catch (Throwable $error) {
        responderJson(['ok' => false, 'mensaje' => 'No se pudo guardar. ¿Ya se ejecutó sql/planeacion_extra.sql?']);
    }
    try {
        registrarBitacora('Agregar ' . mb_strtolower($tipo), 'Meta #' . $id_meta . ' (' . $meta['actividad'] . '): ' . mb_substr($texto, 0, 150));
    } catch (Throwable $error) {
    }
    responderJson(['ok' => true]);
}

function CumplirAcuerdo()
{
    global $obj;
    $id   = (int)($_GET['idr'] ?? 0);
    $stmt = mysqli_prepare($obj->conexion(), "UPDATE observaciones_meta SET cumplido = 1 WHERE id_observacion = ? AND tipo = 'Acuerdo'");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $cambio = mysqli_stmt_affected_rows($stmt) > 0;
    mysqli_stmt_close($stmt);
    if ($cambio) {
        try { registrarBitacora('Cumplir acuerdo', 'Acuerdo #' . $id . ' marcado como cumplido.'); } catch (Throwable $error) {}
    }
    responderJson(['ok' => $cambio]);
}

// Solo quien escribió la observación puede borrarla.
function EliminarObservacion()
{
    global $obj;
    $id         = (int)($_GET['idr'] ?? 0);
    $id_usuario = (int)$_SESSION['id_usuario'];
    $stmt = mysqli_prepare($obj->conexion(), "DELETE FROM observaciones_meta WHERE id_observacion = ? AND registrado_por = ?");
    mysqli_stmt_bind_param($stmt, 'ii', $id, $id_usuario);
    mysqli_stmt_execute($stmt);
    $borrado = mysqli_stmt_affected_rows($stmt) > 0;
    mysqli_stmt_close($stmt);
    if ($borrado) {
        try { registrarBitacora('Eliminar observación', 'Observación #' . $id . ' eliminada.'); } catch (Throwable $error) {}
    }
    responderJson(['ok' => $borrado]);
}

// Acuerdos no cumplidos con fecha compromiso vencida o en los próximos 15 días (pantalla de Alertas).
function TablaAcuerdosPendientes()
{
    try {
        $acuerdos = consultarPreparado(
            "SELECT o.id_meta, o.texto, o.fecha_compromiso, m.actividad, u.nombre,
                    DATEDIFF(o.fecha_compromiso, CURDATE()) AS dias
             FROM observaciones_meta o
             INNER JOIN metas m ON o.id_meta = m.id_meta
             LEFT JOIN usuarios u ON o.registrado_por = u.id_usuario
             WHERE o.tipo = 'Acuerdo' AND o.cumplido = 0
               AND o.fecha_compromiso <= DATE_ADD(CURDATE(), INTERVAL 15 DAY)
             ORDER BY o.fecha_compromiso"
        );
    } catch (Throwable $error) {
        return;   // la tabla todavía no existe
    }
?>
    <div class="card mb-4" style="border-left:5px solid #5b7db1;">
        <div class="card-body">
            <h6 class="fw-bold mb-3" style="color:#5b7db1;"><i class="bi bi-people me-2"></i>Acuerdos pendientes (vencidos o en los próximos 15 días)</h6>
            <?php if (empty($acuerdos)): ?>
                <p class="text-muted mb-0" style="font-size:.85rem;"><i class="bi bi-check-circle me-1" style="color:#2fa86a;"></i>No hay acuerdos pendientes.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle mb-0" style="font-size:.84rem;">
                        <thead><tr>
                            <th style="background:#0A2647;color:#fff;">Meta</th>
                            <th style="background:#0A2647;color:#fff;">Acuerdo</th>
                            <th style="background:#0A2647;color:#fff;">Compromiso</th>
                            <th style="background:#0A2647;color:#fff;">Registró</th>
                            <th style="background:#0A2647;color:#fff;text-align:center;">Ver</th>
                        </tr></thead>
                        <tbody>
                        <?php foreach ($acuerdos as $a): $dias = (int)$a['dias']; ?>
                            <tr>
                                <td><?= e($a['actividad']) ?></td>
                                <td><?= e(mb_strimwidth($a['texto'], 0, 120, '…')) ?></td>
                                <td class="text-nowrap">
                                    <?= formatoFecha($a['fecha_compromiso']) ?><br>
                                    <strong style="color:<?= $dias < 0 ? '#c0392b' : '#c89b00' ?>;font-size:.75rem;">
                                        <?= $dias < 0 ? 'Vencido hace ' . abs($dias) . ' día(s)' : ($dias === 0 ? 'Vence hoy' : 'Faltan ' . $dias . ' día(s)') ?>
                                    </strong>
                                </td>
                                <td><?= e($a['nombre'] ?? '—') ?></td>
                                <td class="text-center"><a href="#" onclick="abrirObservaciones(<?= (int)$a['id_meta'] ?>);return false;" class="fs-5" title="Ver observaciones"><i class="bi bi-chat-left-text-fill" style="color:#5b7db1;"></i></a></td>
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
