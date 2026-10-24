<?php

class OperacionesBd {
    private $servidor;
    private $bd;
    private $usuario;
    private $password;
    private $puerto;

    // Una sola conexión compartida por petición. Antes cada método abría su
    // propia conexión y no la cerraba: guardar un calendario llegaba a abrir
    // ~24 conexiones a la vez y el servidor de base de datos las rechazaba.
    private static $conexionCompartida = null;

    public function __construct() {
        $this->servidor = configuracion('DB_HOST', 'localhost');
        $this->bd       = configuracion('DB_NAME', 'planeacion_municipal_adan');
        $this->usuario  = configuracion('DB_USER', 'root');
        $this->password = configuracion('DB_PASS', '');
        $this->puerto   = configuracion('DB_PORT', 3306);
    }

    public function conexion() {
        if (self::$conexionCompartida instanceof mysqli) {
            return self::$conexionCompartida;
        }

        // Cualquier error de MySQL lanza excepción (así ya es por defecto desde PHP 8.1;
        // se deja explícito para que no dependa de la versión del servidor).
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        try {
            $conexion = mysqli_connect($this->servidor, $this->usuario, $this->password, $this->bd, (int)$this->puerto);
        } catch (mysqli_sql_exception $e) {
            // El detalle técnico va al log del servidor, nunca a la pantalla del usuario.
            error_log('Error de conexión a MySQL: ' . $e->getMessage());
            http_response_code(503);
            die('No se pudo conectar con la base de datos. Intenta de nuevo en unos minutos.');
        }

        mysqli_set_charset($conexion, 'utf8mb4');
        self::$conexionCompartida = $conexion;
        return $conexion;
    }

    // Solo para consultas fijas, sin datos escritos por el usuario.
    // Para todo lo que lleve datos del usuario se usan consultarPreparado() y
    // ejecutarPreparado() de comunes_extra.php.
    public function mostrardatos($sql) {
        $resultado = mysqli_query($this->conexion(), $sql);
        return $resultado ? mysqli_fetch_all($resultado, MYSQLI_ASSOC) : [];
    }

    public function mostrarunregistro($sql) {
        $resultado = mysqli_query($this->conexion(), $sql);
        return $resultado ? mysqli_fetch_assoc($resultado) : null;
    }

    public function vistas() {
        include('vistas.php');
    }

    public function operaciones_bd() {
        include('operaciones.php');
    }
}


/*
 * Lee un valor de configuración:
 *   1. Variable de entorno (producción en Render, se configura en el Dashboard).
 *   2. Php/config.local.php (solo en la computadora local; no se sube a git ni a Render).
 *   3. El valor por defecto.
 * trim() por si al pegar el valor en Render se coló un espacio o salto de línea.
 */
function configuracion($clave, $defecto = '')
{
    static $local = null;
    if ($local === null) {
        $archivo = __DIR__ . '/config.local.php';
        $local   = is_file($archivo) ? (include $archivo) : [];
    }
    $valor = getenv($clave);
    if ($valor === false || $valor === '') {
        $valor = $local[$clave] ?? $defecto;
    }
    return trim((string)$valor);
}

// Palabra de acceso para la vista pública de solo lectura "Avance del Proyecto"
// (avance.php). Se configura en la variable de entorno CLAVE_AVANCE o en
// config.local.php. Si no está configurada, la vista pública queda cerrada.
define('CLAVE_AVANCE_PROYECTO', configuracion('CLAVE_AVANCE', ''));

function claveAvanceCorrecta($clave)
{
    return CLAVE_AVANCE_PROYECTO !== '' && is_string($clave) && hash_equals(CLAVE_AVANCE_PROYECTO, $clave);
}
