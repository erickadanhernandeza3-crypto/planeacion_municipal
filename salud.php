<?php
/*
 * Verificación de salud del sistema.
 *  - La usa .github/workflows/mantener-activo.yml cada 10 minutos para que Render
 *    (plan gratis) no "se duerma" en horario de oficina.
 *  - Sirve para medir qué tan lejos está la base de datos.
 * No muestra ningún dato del sistema: solo si la base responde y cuánto tarda.
 */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$inicio = microtime(true);
require __DIR__ . '/Php/conexion.php';
$conexion = (new OperacionesBd)->conexion();   // si la base no responde, conexion() termina con 503
$conectado = microtime(true);
mysqli_query($conexion, 'SELECT 1');
$fin = microtime(true);

echo json_encode([
    'ok'          => true,
    'conexion_ms' => (int)round(($conectado - $inicio) * 1000),
    'consulta_ms' => (int)round(($fin - $conectado) * 1000),
]);
