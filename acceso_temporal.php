<?php
session_start();

// Candado: solo entra quien conozca la clave en la URL (?clave=...)
$claveSecreta = getenv('ACCESO_TEMPORAL_CLAVE');
if (!$claveSecreta || ($_GET['clave'] ?? '') !== $claveSecreta) {
    http_response_code(404);
    die('404 Not Found');
}

// Conexión usando las mismas credenciales que el resto del sistema (variables de entorno en producción)
include(__DIR__ . '/Php/conexion.php');
$obj      = new OperacionesBd;
$conexion = $obj->conexion();

// Mostrar todos los usuarios que existen
$resultado = mysqli_query($conexion, "SELECT id_usuario, nombre, usuario, rol, activo FROM usuarios");
$usuarios  = mysqli_fetch_all($resultado, MYSQLI_ASSOC);

// Si presionó un botón de entrar
if (isset($_GET['entrar'])) {
    $id  = (int)$_GET['entrar'];
    $sql = "SELECT * FROM usuarios WHERE id_usuario = $id LIMIT 1";
    $r   = mysqli_query($conexion, $sql);
    $u   = mysqli_fetch_assoc($r);

    if ($u) {
        $_SESSION['id_usuario'] = $u['id_usuario'];
        $_SESSION['nombre']     = $u['nombre'];
        $_SESSION['usuario']    = $u['usuario'];
        $_SESSION['rol']        = $u['rol'];

        if ($u['rol'] === 'administrador') {
            header('Location: administrador.php');
        } else {
            header('Location: planeacion.php');
        }
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Acceso Temporal</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4 bg-light">

<div class="card shadow-sm p-4" style="max-width:600px;margin:auto;">
    <h5 class="text-primary mb-1">🔑 Acceso temporal</h5>
    <p class="text-muted small mb-3">Elige con qué usuario quieres entrar:</p>

    <?php if (empty($usuarios)): ?>
        <div class="alert alert-warning">No hay usuarios en la tabla <code>usuarios</code>.</div>
    <?php else: ?>
        <table class="table table-bordered align-middle">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Usuario</th>
                    <th>Rol</th>
                    <th>Activo</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios as $u): ?>
                <tr>
                    <td><?= $u['id_usuario'] ?></td>
                    <td><?= $u['nombre'] ?></td>
                    <td><code><?= $u['usuario'] ?></code></td>
                    <td><span class="badge bg-dark"><?= $u['rol'] ?></span></td>
                    <td><?= $u['activo'] ? '✅' : '❌' ?></td>
                    <td>
                        <a href="?entrar=<?= $u['id_usuario'] ?>"
                           class="btn btn-sm btn-success">
                           Entrar como este
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <div class="alert alert-danger mb-0 mt-2">
        ⚠️ <strong>Borra este archivo</strong> después de usarlo. Es solo para emergencias.
    </div>
</div>

</body>
</html>
