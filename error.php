<?php
// Página de error amigable. Apache la muestra en lugar de su página genérica
// (configurado en docker/apache-seguridad.conf).
$codigo = (int)($_SERVER['REDIRECT_STATUS'] ?? ($_GET['c'] ?? 404));
$mensajes = [
    403 => ['Acceso restringido', 'No tienes permiso para ver esta sección.', 'bi-shield-lock'],
    404 => ['Página no encontrada', 'La dirección que buscas no existe o fue movida.', 'bi-signpost-split'],
    500 => ['Error del servidor', 'Ocurrió un problema inesperado. Intenta de nuevo en unos minutos.', 'bi-tools'],
];
[$titulo, $texto, $icono] = $mensajes[$codigo] ?? $mensajes[404];
http_response_code(isset($mensajes[$codigo]) ? $codigo : 404);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($titulo) ?> — Sistema de Planeación Municipal</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body { background: #f0f2f5; min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Segoe UI', sans-serif; padding: 16px; }
    .tarjeta { max-width: 460px; width: 100%; border-radius: 16px; overflow: hidden; box-shadow: 0 8px 30px rgba(10,38,71,.14); background: #fff; text-align: center; }
    .cabecera { background: #0A2647; border-bottom: 4px solid #D4AF37; padding: 2rem; }
    .cabecera i { font-size: 2.6rem; color: #D4AF37; }
    .codigo { color: rgba(255,255,255,.5); font-weight: 800; letter-spacing: .1em; margin-top: .5rem; }
    .btn-volver { background: #0A2647; color: #fff; border-radius: 10px; padding: .6rem 1.4rem; font-weight: 700; text-decoration: none; display: inline-block; }
    .btn-volver:hover { background: #14375e; color: #fff; }
  </style>
</head>
<body>
  <div class="tarjeta">
    <div class="cabecera">
      <i class="bi <?= $icono ?>"></i>
      <div class="codigo">ERROR <?= $codigo ?></div>
    </div>
    <div class="p-4">
      <h5 class="fw-bold" style="color:#0A2647;"><?= htmlspecialchars($titulo) ?></h5>
      <p class="text-muted"><?= htmlspecialchars($texto) ?></p>
      <a href="/index.php" class="btn-volver"><i class="bi bi-house-door me-1"></i>Ir al inicio</a>
      <p class="text-muted mt-4 mb-0" style="font-size:.75rem;">Sistema de Planeación Municipal — H. Ayuntamiento de Tamazunchale, S.L.P.</p>
    </div>
  </div>
</body>
</html>
