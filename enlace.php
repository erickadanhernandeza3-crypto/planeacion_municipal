<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'enlace') {
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
  <title>Enlace de dependencia — Sistema Municipal</title>

  <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <link rel="stylesheet" href="https://cdn.datatables.net/2.3.4/css/dataTables.dataTables.css" />
  <script src="https://cdn.datatables.net/2.3.4/js/dataTables.js"></script>
  <link rel="stylesheet" href="https://cdn.datatables.net/responsive/3.0.6/css/responsive.dataTables.css" />
  <script src="https://cdn.datatables.net/responsive/3.0.6/js/dataTables.responsive.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.22.0/dist/sweetalert2.all.min.js"></script>
  <!-- Exportar la tabla de Mis metas a Excel / PDF / Imprimir -->
  <link rel="stylesheet" href="https://cdn.datatables.net/buttons/3.2.0/css/buttons.dataTables.min.css" />
  <script src="https://cdn.datatables.net/buttons/3.2.0/js/dataTables.buttons.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/3.2.0/js/buttons.html5.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/3.2.0/js/buttons.print.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
  <script>const ROL_USUARIO = 'enlace';</script>
  <script src="js/funciones.js?v=<?php echo filemtime(__DIR__ . '/js/funciones.js'); ?>"></script>
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
          <div style="width:32px;height:32px;border-radius:50%;background:#D4AF37;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.85rem;color:#0A2647;flex-shrink:0;">
            <?= htmlspecialchars(mb_strtoupper(mb_substr($_SESSION['nombre'], 0, 1))) ?>
          </div>
          <div style="min-width:0;">
            <div style="font-size:.78rem;font-weight:600;"><?= htmlspecialchars($_SESSION['nombre']) ?></div>
            <span style="font-size:.62rem;background:rgba(212,175,55,.25);color:#D4AF37;border:1px solid rgba(212,175,55,.4);padding:1px 8px;border-radius:20px;text-transform:uppercase;letter-spacing:.04em;">Enlace</span>
          </div>
        </div>

        <!-- Menú -->
        <ul class="nav nav-pills flex-column mb-sm-auto mb-0 align-items-center align-items-sm-start w-100" id="menu">
          <li class="nav-item w-100">
            <a href="enlace.php" class="nav-link px-0 align-middle text-white d-flex align-items-center gap-2" style="font-size:.84rem;">
              <i class="bi bi-house-door fs-5"></i><span class="d-none d-sm-inline">Inicio</span>
            </a>
          </li>
          <li class="nav-item w-100">
            <small class="text-secondary d-none d-sm-block" style="font-size:.6rem;text-transform:uppercase;letter-spacing:.1em;padding:.5rem 0 .2rem;">Mi dependencia</small>
          </li>
          <li class="nav-item w-100">
            <a href="javascript:abrirSeguimiento()" class="nav-link px-0 align-middle text-white d-flex align-items-center gap-2" style="font-size:.84rem;">
              <i class="bi bi-list-check fs-5"></i><span class="d-none d-sm-inline">Mis metas</span>
            </a>
          </li>
          <li class="nav-item w-100">
            <a href="javascript:abrirMisAvances()" class="nav-link px-0 align-middle text-white d-flex align-items-center gap-2" style="font-size:.84rem;">
              <i class="bi bi-send-check fs-5"></i><span class="d-none d-sm-inline">Mis avances enviados</span>
              <span class="badge rounded-pill bg-danger ms-auto" id="badgeDevueltos" hidden></span>
            </a>
          </li>
          <li class="nav-item w-100">
            <a href="javascript:abrirAlertas()" class="nav-link px-0 align-middle text-white d-flex align-items-center gap-2" style="font-size:.84rem;">
              <i class="bi bi-alarm fs-5"></i><span class="d-none d-sm-inline">Alertas de vencimiento</span>
            </a>
          </li>
          <li class="nav-item w-100 mt-2">
            <small class="text-secondary d-none d-sm-block" style="font-size:.6rem;text-transform:uppercase;letter-spacing:.1em;padding:.5rem 0 .2rem;">Mi cuenta</small>
          </li>
          <li class="nav-item w-100">
            <a href="javascript:abrirCambiarContrasena()" class="nav-link px-0 align-middle text-white d-flex align-items-center gap-2" style="font-size:.84rem;">
              <i class="bi bi-key fs-5"></i><span class="d-none d-sm-inline">Cambiar contraseña</span>
            </a>
          </li>
        </ul>

        <hr class="w-100 border-secondary">
        <a href="Php/cerrar_sesion.php" class="d-flex align-items-center gap-2 text-decoration-none mb-3" style="color:#f87171;font-size:.83rem;">
          <i class="bi bi-box-arrow-left fs-5"></i><span class="d-none d-sm-inline">Cerrar sesión</span>
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
              <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Detalle</h5>
              <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="contenedorModal"></div>
          </div>
        </div>
      </div>

      <div id="contenedor">
        <div class="dash-encabezado">
          <h4 class="dash-titulo">Panel del enlace</h4>
          <p class="dash-sub"><span id="nombreDependencia">Tu dependencia</span> &middot; <?= date('d/m/Y') ?></p>
        </div>

        <div class="row g-3 mb-4">
          <div class="col-6 col-xl-3"><div class="kpi" style="--color:#0A2647;"><div class="kpi-etiqueta">Metas de mi dependencia</div><div class="kpi-numero" id="kpiTotal">—</div></div></div>
          <div class="col-6 col-xl-3"><div class="kpi" style="--color:#1a7f4b;"><div class="kpi-etiqueta">Cumplidas</div><div class="kpi-numero" id="kpiCumplidas">—</div></div></div>
          <div class="col-6 col-xl-3"><div class="kpi" style="--color:#b7791f;"><div class="kpi-etiqueta">En proceso</div><div class="kpi-numero" id="kpiEnProceso">—</div></div></div>
          <div class="col-6 col-xl-3"><div class="kpi" style="--color:#b42318;"><div class="kpi-etiqueta">Pendientes</div><div class="kpi-numero" id="kpiPendientes">—</div></div></div>
        </div>

        <!-- Avisos: avances devueltos o en revisión y metas por vencer -->
        <div id="avisoAvances"></div>
        <div id="avisoAlertas"></div>

        <div class="card">
          <div class="card-body">
            <h6 class="dash-seccion">Cómo reportar un avance</h6>
            <ol class="mb-0" style="font-size:.9rem;">
              <li>Entra a <a href="javascript:abrirSeguimiento()">Mis metas</a> y en la meta presiona <i class="bi bi-plus-circle-fill" style="color:#2fa86a;"></i>.</li>
              <li>Escribe la cantidad, la fecha y qué se hizo. Adjunta fotos o PDF como evidencia.</li>
              <li>Tu avance queda <strong>En revisión</strong>. Cuando Planeación lo apruebe se suma a la meta.</li>
              <li>Si te lo devuelven, verás el motivo en <a href="javascript:abrirMisAvances()">Mis avances enviados</a>.</li>
            </ol>
          </div>
        </div>
      </div>

    </div>
    <!-- FIN CONTENIDO -->

  </div>
</div>

<script>
cargarInicioEnlace();
cargarAvisoAlertas();
</script>

</body>
</html>
