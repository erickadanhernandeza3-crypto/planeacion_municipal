<?php
session_start();
if (isset($_SESSION['rol'])) {
    header('Location: ' . ($_SESSION['rol'] === 'administrador' ? 'administrador.php' : 'planeacion.php'));
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sistema de Planeación Municipal — Tamazunchale</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body { background: #f0f2f5; min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Segoe UI', sans-serif; }
    .card-login { max-width: 420px; width: 100%; border-radius: 16px; overflow: hidden; box-shadow: 0 8px 30px rgba(10,38,71,.14); border: none; }
    .login-header { background: #0A2647; padding: 2rem; text-align: center; border-bottom: 4px solid #D4AF37; }
    .login-header .icono { width: 62px; height: 62px; background: rgba(212,175,55,.18); border: 2px solid #D4AF37; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; }
    .login-header .icono i { font-size: 1.9rem; color: #D4AF37; }
    .login-header h5 { color: #fff; font-weight: 700; margin: 0; }
    .login-header p { color: rgba(255,255,255,.55); font-size: .78rem; margin: .3rem 0 0; }
    .form-control:focus { border-color: #0A2647; box-shadow: 0 0 0 3px rgba(10,38,71,.1); }
    .btn-entrar { background: #0A2647; color: #fff; border: none; border-radius: 10px; width: 100%; padding: .65rem; font-weight: 700; }
    .btn-entrar:hover { background: #14375e; color: #fff; }
  </style>
</head>
<body>
  <div class="card card-login">
    <div class="login-header">
      <div class="icono"><i class="bi bi-building"></i></div>
      <h5>Sistema de Planeación Municipal</h5>
      <p>H. Ayuntamiento de Tamazunchale, S.L.P.</p>
    </div>
    <div class="card-body p-4">
      <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger py-2" style="font-size:.85rem;">
          <i class="bi bi-exclamation-circle me-1"></i>
          <?= $_GET['error'] === 'credenciales' ? 'Usuario o contraseña incorrectos.' : 'Completa todos los campos.' ?>
        </div>
      <?php endif; ?>
      <form action="Php/validarLogin.php" method="POST">
        <div class="mb-3">
          <label class="form-label fw-semibold" style="color:#0A2647;">Usuario</label>
          <input type="text" class="form-control" name="usuario" placeholder="Ingresa tu usuario" required autofocus>
        </div>
        <div class="mb-4">
          <label class="form-label fw-semibold" style="color:#0A2647;">Contraseña</label>
          <input type="password" class="form-control" name="password" placeholder="••••••••" required>
        </div>
        <button type="submit" class="btn-entrar">
          <i class="bi bi-box-arrow-in-right me-2"></i>Iniciar sesión
        </button>
      </form>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
