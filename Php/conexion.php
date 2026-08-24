<?php

// Palabra de acceso para la vista pública de solo lectura "Avance del Proyecto"
// (avance.php). No es una cuenta de usuario ni reemplaza el login: solo evita
// que cualquiera que adivine la URL entre sin conocer la clave.
define('CLAVE_AVANCE_PROYECTO', 'Tamazunchale2026');

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
        // En producción (Render) estas variables se configuran como variables de entorno.
        // En local (XAMPP), si no existen, cae en los valores de siempre.
        // trim() por si al pegar el valor en Render se coló un espacio o salto de línea.
        $this->servidor = trim(getenv('DB_HOST') ?: 'localhost');
        $this->bd       = trim(getenv('DB_NAME') ?: 'planeacion_municipal_adan');
        $this->usuario  = trim(getenv('DB_USER') ?: 'root');
        $this->password = trim(getenv('DB_PASS') ?: '');
        $this->puerto   = trim(getenv('DB_PORT') ?: 3306);
    }

    public function conexion() {
        if (self::$conexionCompartida instanceof mysqli) {
            return self::$conexionCompartida;
        }

        // Desde PHP 8 mysqli lanza excepción en vez de devolver false, y sin
        // capturarla la respuesta era un 500 con el cuerpo vacío.
        try {
            $conexion = mysqli_connect($this->servidor, $this->usuario, $this->password, $this->bd, (int)$this->puerto);
        } catch (mysqli_sql_exception $e) {
            $conexion = false;
            $error    = $e->getMessage();
        }

        if (!$conexion) {
            http_response_code(500);
            die("Error en la conexión: " . ($error ?? mysqli_connect_error()));
        }

        mysqli_set_charset($conexion, 'utf8mb4');
        self::$conexionCompartida = $conexion;
        return $conexion;
    }

    public function guardardatos($sql) {
        $conexion = $this->conexion();
        mysqli_query($conexion, $sql);
    }

    public function mostrardatos($sql) {
        $conexion  = $this->conexion();
        $resultado = mysqli_query($conexion, $sql);
        // Si la consulta falla, mysqli_query devuelve false y mysqli_fetch_all
        // lanzaría un TypeError que tumba toda la petición.
        if (!$resultado) {
            return [];
        }
        return mysqli_fetch_all($resultado, MYSQLI_ASSOC);
    }

    public function mostrarunregistro($sql) {
        $conexion  = $this->conexion();
        $resultado = mysqli_query($conexion, $sql);
        if (!$resultado) {
            return null;
        }
        return mysqli_fetch_assoc($resultado);
    }

    public function eliminardatos($sql) {
        $conexion = $this->conexion();
        mysqli_query($conexion, $sql);
    }

    public function actualizadatos($sql) {
        $conexion = $this->conexion();
        mysqli_query($conexion, $sql);
    }

    public function consultardatos($sql) {
        $conexion  = $this->conexion();
        $resultado = mysqli_query($conexion, $sql);
        if ($resultado) {
            return $resultado;
        } else {
            echo "Error: " . $sql . "<br>" . mysqli_error($conexion);
            return false;
        }
    }

    public function vistas() {
        include('vistas.php');
    }

    public function operaciones_bd() {
        include('operaciones.php');
    }
}
