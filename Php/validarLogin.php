<?php
session_start();
include("conexion.php");
require_once("comunes_extra.php");
$obj = new OperacionesBd;

$usuario  = trim($_POST['usuario']  ?? '');
$password = trim($_POST['password'] ?? '');

if ($usuario === '' || $password === '') {
    header('Location: ../index.php?error=vacio');
    exit;
}

// Bloqueo temporal: 5 intentos fallidos con el mismo usuario desde la misma IP en 15 minutos.
// (Por usuario + IP, para que los errores de una persona no bloqueen a las demás.)
// Si la tabla bitacora no existe, el login sigue funcionando sin bloqueo.
$descripcionFallo = 'Intento con el usuario "' . mb_substr($usuario, 0, 50) . '".';
try {
    $intentos = consultarPreparado(
        "SELECT COUNT(*) AS total FROM bitacora
         WHERE accion = 'Login fallido' AND descripcion = ? AND ip = ?
           AND fecha >= NOW() - INTERVAL 15 MINUTE",
        'ss', [$descripcionFallo, ipCliente()]
    );
    if ((int)$intentos[0]['total'] >= 5) {
        header('Location: ../index.php?error=bloqueado');
        exit;
    }
} catch (Throwable $error) {
}

// Sentencia preparada: el usuario escrito ya no puede alterar la consulta (inyección SQL).
$filas = consultarPreparado("SELECT * FROM usuarios WHERE usuario = ? AND activo = 1 LIMIT 1", 's', [$usuario]);
$user  = $filas[0] ?? null;

if ($user && password_verify($password, $user['contrasena'])) {
    session_regenerate_id(true);
    $_SESSION['id_usuario']    = $user['id_usuario'];
    $_SESSION['nombre']        = $user['nombre'];
    $_SESSION['usuario']       = $user['usuario'];
    $_SESSION['rol']           = $user['rol'];
    $_SESSION['ultimo_acceso'] = time();

    try {
        registrarBitacora('Inicio de sesión', 'Entró al panel de ' . $user['rol'] . '.');
    } catch (Throwable $error) {
    }

    if ($user['rol'] === 'administrador') {
        header('Location: ../administrador.php');
    } else {
        header('Location: ../planeacion.php');
    }
} else {
    try {
        registrarBitacora('Login fallido', $descripcionFallo);
    } catch (Throwable $error) {
    }
    header('Location: ../index.php?error=credenciales');
}
exit;
