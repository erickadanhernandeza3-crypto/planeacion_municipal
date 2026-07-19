<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'administrador') {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Administrador — Sistema de Planeación Municipal</title>

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

<div class="container-fluid">
  <div class="row flex-nowrap">

    <!-- ══════════ SIDEBAR ══════════ -->
    <div class="col-auto col-md-3 col-xl-2 px-sm-2 px-0" style="background:#0A2647; min-height:100vh;">
      <div class="d-flex flex-column align-items-center align-items-sm-start px-3 pt-3 text-white min-vh-100">

        <div class="d-flex align-items-center gap-2 pb-3 mb-2 border-bottom border-secondary w-100">
          <div style="width:36px;height:36px;background:#D4AF37;border-radius:8px;display:flex;align-items:center;justify-content:center;">
            <i class="bi bi-building" style="color:#0A2647;font-size:1.1rem;"></i>
          </div>
          <span class="d-none d-sm-inline fw-bold" style="font-size:.75rem;color:#D4AF37;text-transform:uppercase;letter-spacing:.04em;line-height:1.3;">
            Sistema de<br>Planeación Mpal.
          </span>
        </div>

        <div class="d-none d-sm-flex align-items-center gap-2 mb-3 p-2 rounded w-100" style="background:rgba(255,255,255,.08);">
          <div style="width:32px;height:32px;border-radius:50%;background:rgba(255,255,255,.25);border:2px solid rgba(255,255,255,.4);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.85rem;">
            <?= strtoupper(substr($_SESSION['nombre'], 0, 1)) ?>
          </div>
          <div>
            <div style="font-size:.78rem;font-weight:600;"><?= htmlspecialchars($_SESSION['nombre']) ?></div>
            <span style="font-size:.62rem;background:rgba(255,255,255,.2);padding:1px 8px;border-radius:20px;text-transform:uppercase;letter-spacing:.04em;">Admin</span>
          </div>
        </div>

        <ul class="nav nav-pills flex-column mb-sm-auto mb-0 align-items-center align-items-sm-start w-100" id="menu">

          <li class="nav-item w-100">
            <small class="text-secondary d-none d-sm-block" style="font-size:.6rem;text-transform:uppercase;letter-spacing:.1em;padding:.5rem 0 .2rem;">Usuarios</small>
          </li>
          <li class="nav-item w-100">
            <a href="javascript:buscar('frmUsuarios',1,'',0,0)"
               class="nav-link px-0 align-middle text-white d-flex align-items-center gap-2" style="font-size:.84rem;">
              <i class="bi bi-person-plus fs-5"></i>
              <span class="d-none d-sm-inline">Registrar Usuario</span>
            </a>
          </li>
          <li class="nav-item w-100">
            <a href="javascript:buscar('EstructuraTbUsuarios',1,'',0,0)"
               class="nav-link px-0 align-middle text-white d-flex align-items-center gap-2" style="font-size:.84rem;">
              <i class="bi bi-people fs-5"></i>
              <span class="d-none d-sm-inline">Gestión de Usuarios</span>
            </a>
          </li>

          <li class="nav-item w-100 mt-2">
            <small class="text-secondary d-none d-sm-block" style="font-size:.6rem;text-transform:uppercase;letter-spacing:.1em;padding:.5rem 0 .2rem;">Catálogos</small>
          </li>

          <li class="nav-item w-100">
            <a href="#submenuEjes" data-bs-toggle="collapse"
               class="nav-link px-0 align-middle text-white d-flex align-items-center gap-2" style="font-size:.84rem;">
              <i class="bi bi-bullseye fs-5"></i>
              <span class="d-none d-sm-inline">Ejes Estratégicos <i class="bi bi-chevron-down" style="font-size:.7rem;"></i></span>
            </a>
            <ul class="collapse nav flex-column ms-3" id="submenuEjes" data-bs-parent="#menu">
              <li><a href="javascript:buscar('frmEjes',1,'',0,0)" class="nav-link px-0 text-white" style="font-size:.8rem;"><span class="d-none d-sm-inline">Registrar Eje</span></a></li>
              <li><a href="javascript:buscar('EstructuraTbEjes',1,'',0,0)" class="nav-link px-0 text-white" style="font-size:.8rem;"><span class="d-none d-sm-inline">Ver Ejes</span></a></li>
            </ul>
          </li>

          <li class="nav-item w-100">
            <a href="#submenuProg" data-bs-toggle="collapse"
               class="nav-link px-0 align-middle text-white d-flex align-items-center gap-2" style="font-size:.84rem;">
              <i class="bi bi-diagram-3 fs-5"></i>
              <span class="d-none d-sm-inline">Programas <i class="bi bi-chevron-down" style="font-size:.7rem;"></i></span>
            </a>
            <ul class="collapse nav flex-column ms-3" id="submenuProg" data-bs-parent="#menu">
              <li><a href="javascript:buscar('frmProgramas',1,'',0,0)" class="nav-link px-0 text-white" style="font-size:.8rem;"><span class="d-none d-sm-inline">Registrar Programa</span></a></li>
              <li><a href="javascript:buscar('EstructuraTbProgramas',1,'',0,0)" class="nav-link px-0 text-white" style="font-size:.8rem;"><span class="d-none d-sm-inline">Ver Programas</span></a></li>
            </ul>
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

    <!-- ══════════ CONTENIDO ══════════ -->
    <div class="col py-3 px-4">

      <!-- Encabezado fijo -->
      <h4 class="fw-bold mb-1" style="color:#0A2647;">Panel de Administración</h4>
      <p class="text-muted mb-3">H. Ayuntamiento de Tamazunchale — <?= date('d/m/Y') ?></p>

      <!-- ── AQUÍ CARGA TODO EL CONTENIDO DINÁMICO ── -->
      <div id="contenedor">
        <!-- Bienvenida inicial -->
        <div class="row g-3">
          <div class="col-6 col-md-3">
            <a href="javascript:buscar('EstructuraTbUsuarios',1,'',0,0)" class="text-decoration-none">
              <div class="p-4 rounded-3 text-white text-center" style="background:linear-gradient(135deg,#0A2647,#14375e);">
                <i class="bi bi-people fs-2 d-block mb-2"></i>
                <div style="font-size:.9rem;font-weight:600;">Usuarios</div>
              </div>
            </a>
          </div>
          <div class="col-6 col-md-3">
            <a href="javascript:buscar('EstructuraTbEjes',1,'',0,0)" class="text-decoration-none">
              <div class="p-4 rounded-3 text-white text-center" style="background:linear-gradient(135deg,#1a6b3c,#2fa86a);">
                <i class="bi bi-bullseye fs-2 d-block mb-2"></i>
                <div style="font-size:.9rem;font-weight:600;">Ejes</div>
              </div>
            </a>
          </div>
          <div class="col-6 col-md-3">
            <a href="javascript:buscar('EstructuraTbProgramas',1,'',0,0)" class="text-decoration-none">
              <div class="p-4 rounded-3 text-white text-center" style="background:linear-gradient(135deg,#7d5a00,#c89b00);">
                <i class="bi bi-diagram-3 fs-2 d-block mb-2"></i>
                <div style="font-size:.9rem;font-weight:600;">Programas</div>
              </div>
            </a>
          </div>
          <div class="col-6 col-md-3">
            <a href="javascript:buscar('EstructuraTbMetas',1,'',0,0)" class="text-decoration-none">
              <div class="p-4 rounded-3 text-white text-center" style="background:linear-gradient(135deg,#7b1f1f,#c0392b);">
                <i class="bi bi-list-check fs-2 d-block mb-2"></i>
                <div style="font-size:.9rem;font-weight:600;">Metas</div>
              </div>
            </a>
          </div>
        </div>
      </div>

      <!-- Div secundario para selects dependientes -->
      <div id="contenedor2"></div>

      <!-- Modal para ediciones -->
      <div class="modal fade" id="ModalUnificado" tabindex="-1">
        <div class="modal-dialog modal-lg">
          <div class="modal-content">
            <div class="modal-header" style="background:#0A2647;color:#fff;">
              <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Edición</h5>
              <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="contenedorModal"></div>
          </div>
        </div>
      </div>

    </div>
    <!-- FIN CONTENIDO -->

  </div>
</div>

</body>
</html>
