<?php
session_start();
include("conexion.php");
$obj = new OperacionesBd;

$usuario  = trim($_POST['usuario']  ?? '');
$password = trim($_POST['password'] ?? '');

if ($usuario === '' || $password === '') {
    header('Location: ../index.php?error=vacio');
    exit;
}

$sql  = "SELECT * FROM usuarios WHERE usuario = '$usuario' AND activo = 1 LIMIT 1";
$user = $obj->mostrarunregistro($sql);

if ($user && password_verify($password, $user['contrasena'])) {
    session_regenerate_id(true);
    $_SESSION['id_usuario'] = $user['id_usuario'];
    $_SESSION['nombre']     = $user['nombre'];
    $_SESSION['usuario']    = $user['usuario'];
    $_SESSION['rol']        = $user['rol'];

    if ($user['rol'] === 'administrador') {
        header('Location: ../administrador.php');
    } else {
        header('Location: ../planeacion.php');
    }
} else {
    header('Location: ../index.php?error=credenciales');
}
exit;
