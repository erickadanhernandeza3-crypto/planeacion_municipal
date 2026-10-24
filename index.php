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
    :root {
      --azul-oscuro: #0A2647;
      --azul-medio: #144272;
      --azul-vivo: #2563EB;
      --azul-claro: #38BDF8;
      --dorado: #D4AF37;
    }
    * { box-sizing: border-box; }
    body { margin: 0; min-height: 100vh; font-family: 'Segoe UI', sans-serif; background: #f0f2f5; }

    .login-wrap { display: flex; min-height: 100vh; }

    /* ---------- Panel azul (lado izquierdo) ---------- */
    .panel-azul {
      flex: 1 1 55%;
      position: relative;
      overflow: hidden;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 3rem;
      color: #fff;
      background: linear-gradient(135deg, var(--azul-oscuro) 0%, var(--azul-medio) 45%, var(--azul-vivo) 100%);
      background-size: 200% 200%;
      animation: degradado 14s ease infinite;
    }
    @keyframes degradado {
      0%, 100% { background-position: 0% 50%; }
      50%      { background-position: 100% 50%; }
    }
    .burbuja {
      position: absolute;
      border-radius: 50%;
      filter: blur(2px);
      background: radial-gradient(circle at 30% 30%, rgba(56,189,248,.55), rgba(37,99,235,0) 70%);
      animation: flotar 12s ease-in-out infinite;
    }
    .burbuja.b1 { width: 420px; height: 420px; top: -120px; left: -120px; }
    .burbuja.b2 { width: 300px; height: 300px; bottom: -80px; right: -60px; animation-delay: -4s; }
    .burbuja.b3 { width: 180px; height: 180px; top: 55%; left: 12%; animation-delay: -8s;
                  background: radial-gradient(circle at 30% 30%, rgba(212,175,55,.35), rgba(212,175,55,0) 70%); }
    @keyframes flotar {
      0%, 100% { transform: translate(0, 0) scale(1); }
      50%      { transform: translate(30px, -25px) scale(1.08); }
    }
    .panel-contenido { position: relative; z-index: 1; max-width: 460px; text-align: center; }
    .panel-contenido .icono {
      width: 92px; height: 92px; margin: 0 auto 1.5rem;
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      background: rgba(255,255,255,.1);
      border: 2px solid var(--dorado);
      backdrop-filter: blur(6px);
    }
    .panel-contenido .icono i { font-size: 2.6rem; color: var(--dorado); }
    .panel-contenido img { height: 120px; margin: 0 auto 1.5rem; display: block; }
    .panel-contenido h1 { font-weight: 800; font-size: 2.1rem; line-height: 1.2; margin-bottom: .75rem; }
    .panel-contenido .linea { width: 70px; height: 4px; border-radius: 4px; background: var(--dorado); margin: 0 auto 1rem; }
    .panel-contenido p { color: rgba(255,255,255,.75); font-size: 1rem; margin: 0; }

    /* ---------- Formulario (lado derecho) ---------- */
    .panel-form {
      flex: 1 1 45%;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 2rem 1rem;
      background: #f0f2f5;
    }
    .card-login {
      width: 100%; max-width: 420px;
      border: none; border-radius: 16px;
      box-shadow: 0 8px 30px rgba(10,38,71,.14);
      animation: aparecer .6s ease-out both;
    }
    @keyframes aparecer {
      from { opacity: 0; transform: translateY(18px); }
      to   { opacity: 1; transform: translateY(0); }
    }
    .card-login h4 { color: var(--azul-oscuro); font-weight: 800; margin-bottom: .25rem; }
    .card-login .sub { color: #6b7280; font-size: .9rem; margin-bottom: 1.5rem; }
    .form-label { color: var(--azul-oscuro); font-weight: 600; }
    .input-group-text { background: #fff; color: var(--azul-medio); border-right: none; }
    .input-group .form-control { border-left: none; }
    .input-group:focus-within .input-group-text,
    .input-group:focus-within .form-control { border-color: var(--azul-vivo); }
    .input-group:focus-within { box-shadow: 0 0 0 3px rgba(37,99,235,.15); border-radius: .375rem; }
    .form-control:focus { box-shadow: none; }

    /* ---------- Botón animado ---------- */
    .btn-entrar {
      position: relative;
      overflow: hidden;
      width: 100%;
      padding: .8rem;
      border: none;
      border-radius: 12px;
      color: #fff;
      font-weight: 700;
      letter-spacing: .3px;
      background: linear-gradient(90deg, var(--azul-oscuro), var(--azul-vivo), var(--azul-claro), var(--azul-vivo), var(--azul-oscuro));
      background-size: 300% 100%;
      box-shadow: 0 6px 18px rgba(37,99,235,.35);
      transition: background-position .6s ease, transform .2s ease, box-shadow .2s ease;
    }
    .btn-entrar:hover {
      background-position: 100% 0;
      transform: translateY(-2px);
      box-shadow: 0 10px 24px rgba(37,99,235,.45);
    }
    .btn-entrar:active { transform: translateY(0) scale(.98); }
    .btn-entrar:focus-visible { outline: 3px solid rgba(56,189,248,.6); outline-offset: 2px; }
    /* Destello que cruza el botón */
    .btn-entrar::before {
      content: '';
      position: absolute;
      top: 0; left: -75%;
      width: 50%; height: 100%;
      background: linear-gradient(120deg, transparent, rgba(255,255,255,.45), transparent);
      transform: skewX(-20deg);
    }
    .btn-entrar:hover::before { animation: destello .9s ease; }
    @keyframes destello { to { left: 125%; } }
    .btn-entrar .flecha { display: inline-block; transition: transform .25s ease; }
    .btn-entrar:hover .flecha { transform: translateX(5px); }
    /* Onda al hacer clic */
    .btn-entrar .onda {
      position: absolute;
      border-radius: 50%;
      background: rgba(255,255,255,.5);
      transform: scale(0);
      animation: onda .6s linear;
      pointer-events: none;
    }
    @keyframes onda { to { transform: scale(4); opacity: 0; } }
    /* Estado cargando */
    .btn-entrar .cargando { display: none; }
    .btn-entrar.enviando .texto { display: none; }
    .btn-entrar.enviando .cargando { display: inline-flex; align-items: center; gap: .5rem; }
    .btn-entrar.enviando { pointer-events: none; opacity: .9; }

    /* ---------- Celular: el azul pasa arriba ---------- */
    @media (max-width: 767.98px) {
      .login-wrap { flex-direction: column; }
      .panel-azul { flex: none; padding: 2.5rem 1.25rem; }
      .panel-contenido h1 { font-size: 1.5rem; }
      .panel-contenido .icono { width: 70px; height: 70px; margin-bottom: 1rem; }
      .panel-contenido .icono i { font-size: 2rem; }
      .panel-contenido img { height: 80px; margin-bottom: 1rem; }
      .panel-form { flex: 1; padding: 1.5rem 1rem 2rem; }
    }

    @media (prefers-reduced-motion: reduce) {
      *, *::before, *::after { animation: none !important; transition: none !important; }
    }
  </style>
</head>
<body>
  <div class="login-wrap">

    <section class="panel-azul">
      <span class="burbuja b1"></span>
      <span class="burbuja b2"></span>
      <span class="burbuja b3"></span>
      <div class="panel-contenido">
        <?php if (file_exists(__DIR__ . '/img/escudo.png')): ?>
          <img src="img/escudo.png" alt="Escudo de Tamazunchale">
        <?php else: ?>
          <div class="icono"><i class="bi bi-building"></i></div>
        <?php endif; ?>
        <h1>Sistema de Planeación Municipal</h1>
        <div class="linea"></div>
        <p>H. Ayuntamiento de Tamazunchale, S.L.P.</p>
      </div>
    </section>

    <section class="panel-form">
      <div class="card card-login">
        <div class="card-body p-4 p-md-5">
          <h4>Bienvenido</h4>
          <p class="sub">Ingresa tus datos para continuar</p>

          <?php if (isset($_GET['error'])):
            $mensajesError = [
              'credenciales' => 'Usuario o contraseña incorrectos.',
              'vacio'        => 'Completa todos los campos.',
              'expirada'     => 'Tu sesión se cerró por inactividad. Vuelve a iniciar sesión.',
              'bloqueado'    => 'Demasiados intentos fallidos. Espera 15 minutos e inténtalo de nuevo.',
            ];
          ?>
            <div class="alert <?= $_GET['error'] === 'expirada' ? 'alert-warning' : 'alert-danger' ?> py-2" style="font-size:.85rem;">
              <i class="bi bi-exclamation-circle me-1"></i>
              <?= $mensajesError[$_GET['error']] ?? 'Completa todos los campos.' ?>
            </div>
          <?php endif; ?>

          <form action="Php/validarLogin.php" method="POST" id="formLogin">
            <div class="mb-3">
              <label class="form-label" for="usuario">Usuario</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-person"></i></span>
                <input type="text" class="form-control" id="usuario" name="usuario" placeholder="Ingresa tu usuario" required autofocus>
              </div>
            </div>
            <div class="mb-4">
              <label class="form-label" for="password">Contraseña</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                <input type="password" class="form-control" id="password" name="password" placeholder="••••••••" required>
              </div>
            </div>
            <button type="submit" class="btn-entrar" id="btnEntrar">
              <span class="texto">Iniciar sesión <i class="bi bi-arrow-right flecha ms-1"></i></span>
              <span class="cargando"><span class="spinner-border spinner-border-sm"></span> Entrando...</span>
            </button>
          </form>
        </div>
      </div>
    </section>

  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    const btnEntrar = document.getElementById('btnEntrar');

    // Onda que sale desde donde se hizo clic
    btnEntrar.addEventListener('click', (e) => {
      const rect = btnEntrar.getBoundingClientRect();
      const tam = Math.max(rect.width, rect.height);
      const onda = document.createElement('span');
      onda.className = 'onda';
      onda.style.width = onda.style.height = tam + 'px';
      onda.style.left = (e.clientX - rect.left - tam / 2) + 'px';
      onda.style.top = (e.clientY - rect.top - tam / 2) + 'px';
      btnEntrar.appendChild(onda);
      setTimeout(() => onda.remove(), 600);
    });

    // Spinner mientras se valida el login
    document.getElementById('formLogin').addEventListener('submit', () => {
      btnEntrar.classList.add('enviando');
    });
  </script>
</body>
</html>
