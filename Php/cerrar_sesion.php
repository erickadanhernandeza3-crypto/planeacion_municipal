<?php
session_start();

// Registrar la salida en la bitácora (si falla, se cierra la sesión de todos modos)
if (isset($_SESSION['id_usuario'])) {
    try {
        include("conexion.php");
        require_once("comunes_extra.php");
        $obj = new OperacionesBd;
        $expirada = ($_GET['motivo'] ?? '') === 'expirada';
        registrarBitacora('Cierre de sesión', $expirada ? 'Sesión cerrada por inactividad.' : 'Salió del sistema.');
    } catch (Throwable $error) {
    }
}

session_destroy();

// Este archivo está dentro de Php/, por eso el login está en ../index.php
// (antes redirigía a Php/index.php, que no existe).
$motivo = ($_GET['motivo'] ?? '') === 'expirada' ? '?error=expirada' : '';
header('Location: ../index.php' . $motivo);
exit;
