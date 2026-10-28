<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'planeacion') {
    header('Location: index.php');
    exit;
}
// Cierre automático de la sesión después de 30 minutos sin actividad
require_once __DIR__ . '/Php/comunes_extra.php';
if (!revisarInactividad()) {
    header('Location: Php/cerrar_sesion.php?motivo=expirada');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?= htmlspecialchars(tokenCsrf()) ?>">
  <title>Planeación — Sistema Municipal</title>

  <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <link rel="stylesheet" href="https://cdn.datatables.net/2.3.4/css/dataTables.dataTables.css" />
  <script src="https://cdn.datatables.net/2.3.4/js/dataTables.js"></script>
  <link rel="stylesheet" href="https://cdn.datatables.net/responsive/3.0.6/css/responsive.dataTables.css" />
  <script src="https://cdn.datatables.net/responsive/3.0.6/js/dataTables.responsive.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.22.0/dist/sweetalert2.all.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
  <script>const ROL_USUARIO = 'planeacion';</script>
  <script src="js/funciones.js?v=<?php echo filemtime(__DIR__ . '/js/funciones.js'); ?>"></script>
  <!-- Exportar tablas a Excel / PDF / Imprimir (módulo Seguimiento) -->
  <link rel="stylesheet" href="https://cdn.datatables.net/buttons/3.2.0/css/buttons.dataTables.min.css" />
  <script src="https://cdn.datatables.net/buttons/3.2.0/js/dataTables.buttons.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/3.2.0/js/buttons.html5.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/3.2.0/js/buttons.print.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
  <script src="js/extras_ui.js?v=<?php echo filemtime(__DIR__ . '/js/extras_ui.js'); ?>"></script>
  <script src="js/planeacion_extra.js?v=<?php echo filemtime(__DIR__ . '/js/planeacion_extra.js'); ?>"></script>
  <link rel="stylesheet" href="css/estilos.css?v=<?php echo filemtime(__DIR__ . '/css/estilos.css'); ?>">
</head>
<body>

<div class="container-fluid">
  <div class="row flex-nowrap">

    <!-- ══════════ SIDEBAR ══════════ -->
    <div class="col-auto col-md-3 col-xl-2 px-sm-2 px-0" style="background:#0A2647; min-height:100vh;">
      <div class="d-flex flex-column align-items-center align-items-sm-start px-3 pt-3 text-white min-vh-100">

        <!-- Logo -->
        <div class="d-flex align-items-center gap-2 pb-3 mb-2 border-bottom border-secondary w-100">
          <?php if (file_exists(__DIR__ . '/img/escudo.png')): ?>
          <img src="img/escudo.png" alt="Escudo" style="width:36px;height:36px;object-fit:contain;">
          <?php else: ?>
          <div style="width:36px;height:36px;background:#D4AF37;border-radius:8px;display:flex;align-items:center;justify-content:center;">
            <i class="bi bi-building" style="color:#0A2647;font-size:1.1rem;"></i>
          </div>
          <?php endif; ?>
          <span class="d-none d-sm-inline fw-bold" style="font-size:.75rem;color:#D4AF37;text-transform:uppercase;letter-spacing:.04em;line-height:1.3;">
            Sistema de<br>Planeación Mpal.
          </span>
        </div>

        <!-- Usuario -->
        <div class="d-none d-sm-flex align-items-center gap-2 mb-3 p-2 rounded w-100" style="background:rgba(255,255,255,.08);">
          <div style="width:32px;height:32px;border-radius:50%;background:#D4AF37;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.85rem;color:#0A2647;">
            <?= htmlspecialchars(mb_strtoupper(mb_substr($_SESSION['nombre'], 0, 1))) ?>
          </div>
          <div>
            <div style="font-size:.78rem;font-weight:600;"><?= htmlspecialchars($_SESSION['nombre']) ?></div>
            <span style="font-size:.62rem;background:rgba(212,175,55,.25);color:#D4AF37;border:1px solid rgba(212,175,55,.4);padding:1px 8px;border-radius:20px;text-transform:uppercase;letter-spacing:.04em;">Planeación</span>
          </div>
        </div>

        <!-- Menú -->
        <ul class="nav nav-pills flex-column mb-sm-auto mb-0 align-items-center align-items-sm-start w-100" id="menu">

          <li class="nav-item w-100">
            <a href="planeacion.php"
               class="nav-link px-0 align-middle text-white d-flex align-items-center gap-2" style="font-size:.84rem;">
              <i class="bi bi-house-door fs-5"></i>
              <span class="d-none d-sm-inline">Inicio</span>
            </a>
          </li>

          <li class="nav-item w-100">
            <small class="text-secondary d-none d-sm-block" style="font-size:.6rem;text-transform:uppercase;letter-spacing:.1em;padding:.5rem 0 .2rem;">Módulos</small>
          </li>

          <li class="nav-item w-100">
            <a href="javascript:buscar('frmMetas',1,'',0,0)"
               class="nav-link px-0 align-middle text-white d-flex align-items-center gap-2" style="font-size:.84rem;">
              <i class="bi bi-pencil-square fs-5"></i>
              <span class="d-none d-sm-inline">Registrar Meta</span>
            </a>
          </li>

          <li class="nav-item w-100">
            <a href="javascript:buscar('EstructuraTbMetas',1,'',0,0)"
               class="nav-link px-0 align-middle text-white d-flex align-items-center gap-2" style="font-size:.84rem;">
              <i class="bi bi-list-check fs-5"></i>
              <span class="d-none d-sm-inline">Histórico de Metas</span>
            </a>
          </li>

          <li class="nav-item w-100">
            <a href="javascript:buscar('DatosGraficaEjes',1,'',0,0)"
               class="nav-link px-0 align-middle text-white d-flex align-items-center gap-2" style="font-size:.84rem;">
              <i class="bi bi-bar-chart fs-5"></i>
              <span class="d-none d-sm-inline">Evaluación Visual</span>
            </a>
          </li>

          <li class="nav-item w-100 mt-2">
            <small class="text-secondary d-none d-sm-block" style="font-size:.6rem;text-transform:uppercase;letter-spacing:.1em;padding:.5rem 0 .2rem;">Indicadores</small>
          </li>
          <li class="nav-item w-100">
            <a href="javascript:buscar('PanelIndicadores',1,'',0,0)"
               class="nav-link px-0 align-middle text-white d-flex align-items-center gap-2" style="font-size:.84rem;">
              <i class="bi bi-bullseye fs-5"></i>
              <span class="d-none d-sm-inline">Indicadores (MIR)</span>
            </a>
          </li>
          <li class="nav-item w-100">
            <a href="javascript:buscar('ReporteConcentracion',1,'',0,0)"
               class="nav-link px-0 align-middle text-white d-flex align-items-center gap-2" style="font-size:.84rem;">
              <i class="bi bi-calendar3-range fs-5"></i>
              <span class="d-none d-sm-inline">Concentración de Calendarios</span>
            </a>
          </li>

          <li class="nav-item w-100 mt-2">
            <small class="text-secondary d-none d-sm-block" style="font-size:.6rem;text-transform:uppercase;letter-spacing:.1em;padding:.5rem 0 .2rem;">Seguimiento</small>
          </li>
          <li class="nav-item w-100">
            <a href="javascript:abrirSeguimiento()"
               class="nav-link px-0 align-middle text-white d-flex align-items-center gap-2" style="font-size:.84rem;">
              <i class="bi bi-speedometer2 fs-5"></i>
              <span class="d-none d-sm-inline">Seguimiento y Avances</span>
            </a>
          </li>
          <li class="nav-item w-100">
            <a href="javascript:abrirRevision()"
               class="nav-link px-0 align-middle text-white d-flex align-items-center gap-2" style="font-size:.84rem;">
              <i class="bi bi-clipboard-check fs-5"></i>
              <span class="d-none d-sm-inline">Revisión de Avances</span>
              <span class="badge rounded-pill bg-warning text-dark ms-auto" id="badgeRevision" hidden></span>
            </a>
          </li>
          <li class="nav-item w-100">
            <a href="javascript:abrirAlertas()"
               class="nav-link px-0 align-middle text-white d-flex align-items-center gap-2" style="font-size:.84rem;">
              <i class="bi bi-alarm fs-5"></i>
              <span class="d-none d-sm-inline">Alertas de Vencimiento</span>
            </a>
          </li>
          <li class="nav-item w-100">
            <a href="javascript:abrirTrimestralPlan()"
               class="nav-link px-0 align-middle text-white d-flex align-items-center gap-2" style="font-size:.84rem;">
              <i class="bi bi-calendar4-week fs-5"></i>
              <span class="d-none d-sm-inline">Comparativo Trimestral</span>
            </a>
          </li>
          <li class="nav-item w-100">
            <a href="javascript:abrirCalendario()"
               class="nav-link px-0 align-middle text-white d-flex align-items-center gap-2" style="font-size:.84rem;">
              <i class="bi bi-calendar-event fs-5"></i>
              <span class="d-none d-sm-inline">Calendario de Vencimientos</span>
            </a>
          </li>

          <li class="nav-item w-100 mt-2">
            <small class="text-secondary d-none d-sm-block" style="font-size:.6rem;text-transform:uppercase;letter-spacing:.1em;padding:.5rem 0 .2rem;">Control y Evaluación</small>
          </li>
          <li class="nav-item w-100">
            <a href="javascript:abrirFisicoFinanciero()"
               class="nav-link px-0 align-middle text-white d-flex align-items-center gap-2" style="font-size:.84rem;">
              <i class="bi bi-cash-coin fs-5"></i>
              <span class="d-none d-sm-inline">Físico vs Financiero</span>
            </a>
          </li>
          <li class="nav-item w-100">
            <a href="javascript:abrirDependencias()"
               class="nav-link px-0 align-middle text-white d-flex align-items-center gap-2" style="font-size:.84rem;">
              <i class="bi bi-building fs-5"></i>
              <span class="d-none d-sm-inline">Por Dependencia</span>
            </a>
          </li>
          <li class="nav-item w-100">
            <a href="javascript:abrirReporte()"
               class="nav-link px-0 align-middle text-white d-flex align-items-center gap-2" style="font-size:.84rem;">
              <i class="bi bi-printer fs-5"></i>
              <span class="d-none d-sm-inline">Reporte por Eje</span>
            </a>
          </li>

          <li class="nav-item w-100 mt-2">
            <small class="text-secondary d-none d-sm-block" style="font-size:.6rem;text-transform:uppercase;letter-spacing:.1em;padding:.5rem 0 .2rem;">Mi cuenta</small>
          </li>
          <li class="nav-item w-100">
            <a href="javascript:abrirCambiarContrasena()"
               class="nav-link px-0 align-middle text-white d-flex align-items-center gap-2" style="font-size:.84rem;">
              <i class="bi bi-key fs-5"></i>
              <span class="d-none d-sm-inline">Cambiar Contraseña</span>
            </a>
          </li>

        </ul>

        <hr class="w-100 border-secondary">
        <a href="Php/cerrar_sesion.php" class="d-flex align-items-center gap-2 text-decoration-none mb-3"
           style="color:#f87171;font-size:.83rem;">
          <i class="bi bi-box-arrow-left fs-5"></i>
          <span class="d-none d-sm-inline">Cerrar sesión</span>
        </a>

      </div>
    </div>
    <!-- FIN SIDEBAR -->

    <!-- ══════════ CONTENIDO PRINCIPAL ══════════ -->
    <div class="col py-3 px-4">

      <!-- Modal unificado -->
      <div class="modal fade" id="ModalUnificado" tabindex="-1">
        <div class="modal-dialog modal-xl">
          <div class="modal-content">
            <div class="modal-header" style="background:#0A2647;color:#fff;">
              <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Detalle / Edición</h5>
              <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="contenedorModal"></div>
          </div>
        </div>
      </div>

      <!-- Área de contenido -->
      <div id="contenedor">
        <!-- Encabezado -->
        <div class="dash-encabezado">
          <h4 class="dash-titulo">Panel de Planeación</h4>
          <p class="dash-sub">H. Ayuntamiento de Tamazunchale &middot; <?= date('d/m/Y') ?></p>
        </div>

        <!-- KPIs -->
        <div class="row g-3 mb-4">
          <div class="col-6 col-xl-3">
            <div class="kpi" style="--color:#0A2647;">
              <div class="kpi-etiqueta">Total de metas</div>
              <div class="kpi-numero" id="kpiTotal">—</div>
            </div>
          </div>
          <div class="col-6 col-xl-3">
            <div class="kpi" style="--color:#1a7f4b;">
              <div class="kpi-etiqueta">Cumplidas</div>
              <div class="kpi-numero" id="kpiCumplidas">—</div>
            </div>
          </div>
          <div class="col-6 col-xl-3">
            <div class="kpi" style="--color:#b7791f;">
              <div class="kpi-etiqueta">En proceso</div>
              <div class="kpi-numero" id="kpiEnProceso">—</div>
            </div>
          </div>
          <div class="col-6 col-xl-3">
            <div class="kpi" style="--color:#b42318;">
              <div class="kpi-etiqueta">Pendientes</div>
              <div class="kpi-numero" id="kpiPendientes">—</div>
            </div>
          </div>
        </div>

        <!-- Aviso de metas vencidas / por vencer (lo llena cargarAvisoAlertas) -->
        <div id="avisoAvances"></div>
        <div id="avisoAlertas"></div>

        <!-- Resumen por estado -->
        <div class="card">
          <div class="card-body">
            <h6 class="dash-seccion">Estado de las metas</h6>
            <table class="tabla-resumen">
              <thead>
                <tr><th>Estado</th><th class="text-end">Metas</th><th class="text-end">Porcentaje</th><th class="col-barra"></th></tr>
              </thead>
              <tbody>
                <tr>
                  <td>Cumplidas</td>
                  <td class="text-end" id="resCumplidas">0</td>
                  <td class="text-end" id="pctCumplidas">0%</td>
                  <td class="col-barra"><div class="barra"><span id="barCumplidas" style="background:#1a7f4b;"></span></div></td>
                </tr>
                <tr>
                  <td>En proceso</td>
                  <td class="text-end" id="resEnProceso">0</td>
                  <td class="text-end" id="pctEnProceso">0%</td>
                  <td class="col-barra"><div class="barra"><span id="barEnProceso" style="background:#b7791f;"></span></div></td>
                </tr>
                <tr>
                  <td>Pendientes</td>
                  <td class="text-end" id="resPendientes">0</td>
                  <td class="text-end" id="pctPendientes">0%</td>
                  <td class="col-barra"><div class="barra"><span id="barPendientes" style="background:#b42318;"></span></div></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
      <!-- Fin contenedor -->

    </div>
    <!-- FIN CONTENIDO -->

  </div>
</div>

<script>
// Cargar KPIs y resumen por estado al abrir la página
fetch('Php/controlador.php?idopc=DatosGraficaEstados&ops=&idr=0&msg=0')
  .then(r => r.json())
  .then(data => {
    let total = 0, cumplidas = 0, proceso = 0, pendientes = 0;
    data.estados.forEach((e, i) => {
      total += parseInt(data.totales[i]);
      if (e === 'Terminado')  cumplidas  = parseInt(data.totales[i]);
      if (e === 'En Proceso') proceso    = parseInt(data.totales[i]);
      if (e === 'Pendiente')  pendientes = parseInt(data.totales[i]);
    });
    const pct = n => total ? Math.round(n * 100 / total) : 0;
    const poner = (id, valor) => document.getElementById(id).textContent = valor;

    poner('kpiTotal', total);
    poner('kpiCumplidas', cumplidas);
    poner('kpiEnProceso', proceso);
    poner('kpiPendientes', pendientes);

    [['Cumplidas', cumplidas], ['EnProceso', proceso], ['Pendientes', pendientes]].forEach(([clave, n]) => {
      poner('res' + clave, n);
      poner('pct' + clave, pct(n) + '%');
      document.getElementById('bar' + clave).style.width = pct(n) + '%';
    });
  }).catch(() => {});

// Avisos: avances por revisar y alertas de vencimiento
actualizarAvisosRevision();
cargarAvisoAlertas();
</script>

</body>
</html>
