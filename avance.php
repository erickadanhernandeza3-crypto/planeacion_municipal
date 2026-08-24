<?php
include('Php/conexion.php');

$clave       = $_GET['clave'] ?? '';
$autorizado  = ($clave === CLAVE_AVANCE_PROYECTO);
$claveEnviada = isset($_GET['clave']); // hubo un intento, aunque haya fallado
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Avance del Proyecto — Sistema de Planeación Municipal</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <?php if ($autorizado): ?>
  <link rel="stylesheet" href="https://cdn.datatables.net/2.3.4/css/dataTables.dataTables.css" />
  <link rel="stylesheet" href="https://cdn.datatables.net/responsive/3.0.6/css/responsive.dataTables.css" />
  <?php endif; ?>
  <style>
    body { background: #f0f2f5; font-family: 'Segoe UI', sans-serif; }

    /* ── Pantalla de acceso ── */
    .pantalla-acceso { min-height: 100vh; display: flex; align-items: center; justify-content: center; }
    .card-acceso { max-width: 420px; width: 100%; border-radius: 16px; overflow: hidden; box-shadow: 0 8px 30px rgba(10,38,71,.14); border: none; }
    .acceso-header { background: #0A2647; padding: 2rem; text-align: center; border-bottom: 4px solid #D4AF37; }
    .acceso-header .icono { width: 62px; height: 62px; background: rgba(212,175,55,.18); border: 2px solid #D4AF37; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; }
    .acceso-header .icono i { font-size: 1.9rem; color: #D4AF37; }
    .acceso-header h5 { color: #fff; font-weight: 700; margin: 0; }
    .acceso-header p { color: rgba(255,255,255,.55); font-size: .78rem; margin: .3rem 0 0; }
    .form-control:focus { border-color: #0A2647; box-shadow: 0 0 0 3px rgba(10,38,71,.1); }
    .btn-entrar { background: #0A2647; color: #fff; border: none; border-radius: 10px; width: 100%; padding: .65rem; font-weight: 700; }
    .btn-entrar:hover { background: #14375e; color: #fff; }

    /* ── Dashboard de solo lectura ── */
    .encabezado-avance { background: #0A2647; padding: 1.2rem 1.5rem; border-bottom: 4px solid #D4AF37; color: #fff; }
    .tarjeta-resumen { border: none; border-radius: 12px; box-shadow: 0 2px 10px rgba(10,38,71,.08); border-left: 5px solid #0A2647; }
    .tarjeta-resumen .numero { font-size: 1.8rem; font-weight: 800; color: #0A2647; }
    .tarjeta-resumen .etiqueta { font-size: .75rem; color: #6c757d; text-transform: uppercase; letter-spacing: .04em; }
    .tarjeta-resumen.completos   { border-left-color: #2fa86a; }
    .tarjeta-resumen.completos .numero   { color: #2fa86a; }
    .tarjeta-resumen.en-proceso { border-left-color: #c89b00; }
    .tarjeta-resumen.en-proceso .numero  { color: #c89b00; }
    .tarjeta-resumen.sin-iniciar { border-left-color: #c0392b; }
    .tarjeta-resumen.sin-iniciar .numero { color: #c0392b; }
  </style>
</head>
<body>

<?php if (!$autorizado): ?>

  <!-- ══════════ PANTALLA DE ACCESO ══════════ -->
  <div class="pantalla-acceso">
    <div class="card card-acceso">
      <div class="acceso-header">
        <div class="icono"><i class="bi bi-graph-up-arrow"></i></div>
        <h5>Avance del Proyecto</h5>
        <p>Sistema de Planeación, Seguimiento y Evaluación de Programas Municipales</p>
      </div>
      <div class="card-body p-4">
        <?php if ($claveEnviada): ?>
          <div class="alert alert-danger py-2" style="font-size:.85rem;">
            <i class="bi bi-exclamation-circle me-1"></i> Clave de acceso incorrecta.
          </div>
        <?php endif; ?>
        <form action="avance.php" method="GET">
          <div class="mb-4">
            <label class="form-label fw-semibold" style="color:#0A2647;">Clave de acceso</label>
            <input type="password" class="form-control" name="clave" placeholder="••••••••" required autofocus>
          </div>
          <button type="submit" class="btn-entrar">
            <i class="bi bi-eye me-2"></i>Ver avance
          </button>
        </form>
      </div>
    </div>
  </div>

<?php else: ?>

  <!-- ══════════ DASHBOARD DE SOLO LECTURA ══════════ -->
  <div class="encabezado-avance">
    <div class="container-fluid">
      <h5 class="fw-bold mb-0"><i class="bi bi-graph-up-arrow me-2"></i>Avance del Proyecto</h5>
      <p class="mb-0" style="font-size:.8rem;color:rgba(255,255,255,.65);">
        Sistema de Planeación, Seguimiento y Evaluación de Programas Municipales — H. Ayuntamiento de Tamazunchale, S.L.P.
        <span class="badge bg-light text-dark ms-2"><i class="bi bi-eye me-1"></i>Solo lectura</span>
      </p>
    </div>
  </div>

  <div class="container-fluid py-4 px-4">

    <!-- Tarjetas resumen -->
    <div class="row g-3 mb-4">
      <div class="col-6 col-md-4 col-lg-2">
        <div class="card tarjeta-resumen p-3 h-100">
          <div class="numero" id="resTotalEjes">—</div>
          <div class="etiqueta">Ejes estratégicos</div>
        </div>
      </div>
      <div class="col-6 col-md-4 col-lg-2">
        <div class="card tarjeta-resumen p-3 h-100">
          <div class="numero" id="resTotalProgramas">—</div>
          <div class="etiqueta">Programas</div>
        </div>
      </div>
      <div class="col-6 col-md-4 col-lg-2">
        <div class="card tarjeta-resumen completos p-3 h-100">
          <div class="numero" id="resCompletos">—</div>
          <div class="etiqueta">Completos</div>
        </div>
      </div>
      <div class="col-6 col-md-4 col-lg-2">
        <div class="card tarjeta-resumen en-proceso p-3 h-100">
          <div class="numero" id="resEnProceso">—</div>
          <div class="etiqueta">En proceso</div>
        </div>
      </div>
      <div class="col-6 col-md-4 col-lg-2">
        <div class="card tarjeta-resumen sin-iniciar p-3 h-100">
          <div class="numero" id="resSinIniciar">—</div>
          <div class="etiqueta">Sin iniciar</div>
        </div>
      </div>
    </div>

    <p class="text-muted mb-3" style="font-size:.8rem;">
      <i class="bi bi-clock-history me-1"></i>
      Última captura registrada: <strong id="resUltimaActualizacion">—</strong>
    </p>

    <!-- Tabla de programas -->
    <div class="card border-0 shadow-sm">
      <div class="card-body">
        <h6 class="fw-bold mb-3" style="color:#0A2647;">
          <i class="bi bi-list-check me-2"></i>Estatus de captura por programa
        </h6>
        <div class="table-responsive">
          <table id="TBAvance" class="table table-striped table-bordered" style="width:100%;">
            <thead>
              <tr>
                <th>Eje Estratégico</th>
                <th>Programa</th>
                <th>Estatus</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
    </div>

  </div>

  <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.datatables.net/2.3.4/js/dataTables.js"></script>
  <script src="https://cdn.datatables.net/responsive/3.0.6/js/dataTables.responsive.js"></script>
  <script src="js/funciones.js?v=<?php echo filemtime(__DIR__ . '/js/funciones.js'); ?>"></script>
  <script>
    const claveAvance = <?php echo json_encode($clave); ?>;
    cargarResumenAvance(claveAvance);
    inicializarDataTable('#TBAvance', 'DatosAvanceProgramas', columnasAvance, '&clave=' + encodeURIComponent(claveAvance));
  </script>

<?php endif; ?>

</body>
</html>
