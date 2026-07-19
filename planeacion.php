<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Planeación — Sistema Municipal</title>

  <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <link rel="stylesheet" href="https://cdn.datatables.net/2.3.4/css/dataTables.dataTables.css" />
  <script src="https://cdn.datatables.net/2.3.4/js/dataTables.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="js/funciones.js?v=<?php echo filemtime(__DIR__ . '/js/funciones.js'); ?>"></script>
  <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'planeacion') {
    header('Location: index.php');
    exit;
}
?>

<div class="container-fluid">
  <div class="row flex-nowrap">

    <!-- ══════════ SIDEBAR ══════════ -->
    <div class="col-auto col-md-3 col-xl-2 px-sm-2 px-0" style="background:#0A2647; min-height:100vh;">
      <div class="d-flex flex-column align-items-center align-items-sm-start px-3 pt-3 text-white min-vh-100">

        <!-- Logo -->
        <div class="d-flex align-items-center gap-2 pb-3 mb-2 border-bottom border-secondary w-100">
          <div style="width:36px;height:36px;background:#D4AF37;border-radius:8px;display:flex;align-items:center;justify-content:center;">
            <i class="bi bi-building" style="color:#0A2647;font-size:1.1rem;"></i>
          </div>
          <span class="d-none d-sm-inline fw-bold" style="font-size:.75rem;color:#D4AF37;text-transform:uppercase;letter-spacing:.04em;line-height:1.3;">
            Sistema de<br>Planeación Mpal.
          </span>
        </div>

        <!-- Usuario -->
        <div class="d-none d-sm-flex align-items-center gap-2 mb-3 p-2 rounded w-100" style="background:rgba(255,255,255,.08);">
          <div style="width:32px;height:32px;border-radius:50%;background:#D4AF37;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.85rem;color:#0A2647;">
            <?= strtoupper(substr($_SESSION['nombre'], 0, 1)) ?>
          </div>
          <div>
            <div style="font-size:.78rem;font-weight:600;"><?= htmlspecialchars($_SESSION['nombre']) ?></div>
            <span style="font-size:.62rem;background:rgba(212,175,55,.25);color:#D4AF37;border:1px solid rgba(212,175,55,.4);padding:1px 8px;border-radius:20px;text-transform:uppercase;letter-spacing:.04em;">Planeación</span>
          </div>
        </div>

        <!-- Menú -->
        <ul class="nav nav-pills flex-column mb-sm-auto mb-0 align-items-center align-items-sm-start w-100" id="menu">

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
        <!-- Bienvenida y KPIs -->
        <h4 class="fw-bold mb-1" style="color:#0A2647;">Panel de Planeación</h4>
        <p class="text-muted mb-4">H. Ayuntamiento de Tamazunchale — <?= date('d/m/Y') ?></p>

        <!-- KPIs -->
        <div class="row g-3 mb-4">
          <div class="col-6 col-md-3">
            <div class="p-3 rounded-3 text-white" style="background:linear-gradient(135deg,#0A2647,#14375e);">
              <i class="bi bi-list-check d-block fs-3 mb-1"></i>
              <div id="kpiTotal" style="font-size:1.6rem;font-weight:700;">—</div>
              <div style="font-size:.78rem;opacity:.85;">Total de Metas</div>
            </div>
          </div>
          <div class="col-6 col-md-3">
            <div class="p-3 rounded-3 text-white" style="background:linear-gradient(135deg,#1a6b3c,#2fa86a);">
              <i class="bi bi-check-circle d-block fs-3 mb-1"></i>
              <div id="kpiCumplidas" style="font-size:1.6rem;font-weight:700;">—</div>
              <div style="font-size:.78rem;opacity:.85;">Cumplidas</div>
            </div>
          </div>
          <div class="col-6 col-md-3">
            <div class="p-3 rounded-3 text-white" style="background:linear-gradient(135deg,#7d5a00,#c89b00);">
              <i class="bi bi-arrow-repeat d-block fs-3 mb-1"></i>
              <div id="kpiEnProceso" style="font-size:1.6rem;font-weight:700;">—</div>
              <div style="font-size:.78rem;opacity:.85;">En Proceso</div>
            </div>
          </div>
          <div class="col-6 col-md-3">
            <div class="p-3 rounded-3 text-white" style="background:linear-gradient(135deg,#7b1f1f,#c0392b);">
              <i class="bi bi-hourglass-split d-block fs-3 mb-1"></i>
              <div id="kpiPendientes" style="font-size:1.6rem;font-weight:700;">—</div>
              <div style="font-size:.78rem;opacity:.85;">Pendientes</div>
            </div>
          </div>
        </div>

        <p class="text-muted" style="font-size:.85rem;">
          <i class="bi bi-arrow-left-circle me-1"></i>
          Usa el menú lateral para registrar metas, ver el histórico o la evaluación visual.
        </p>
      </div>
      <!-- Fin contenedor -->

    </div>
    <!-- FIN CONTENIDO -->

  </div>
</div>

<script>
// Cargar KPIs al abrir la página
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
    document.getElementById('kpiTotal').textContent     = total;
    document.getElementById('kpiCumplidas').textContent = cumplidas;
    document.getElementById('kpiEnProceso').textContent = proceso;
    document.getElementById('kpiPendientes').textContent = pendientes;
  }).catch(() => {});
</script>

</body>
</html>
