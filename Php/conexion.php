<?php
class OperacionesBd {
    private $servidor;
    private $bd;
    private $usuario;
    private $password;
    private $puerto;

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
        $conexion = mysqli_connect($this->servidor, $this->usuario, $this->password, $this->bd, (int)$this->puerto);
        if (!$conexion) {
            die("Error en la conexión: " . mysqli_connect_error());
        }
        mysqli_set_charset($conexion, 'utf8mb4');
        return $conexion;
    }

    public function guardardatos($sql) {
        $obj     = new OperacionesBd;
        $conexion = $obj->conexion();
        mysqli_query($conexion, $sql);
    }

    public function mostrardatos($sql) {
        $obj      = new OperacionesBd;
        $conexion = $obj->conexion();
        $resultado = mysqli_query($conexion, $sql);
        return mysqli_fetch_all($resultado, MYSQLI_ASSOC);
    }

    public function mostrarunregistro($sql) {
        $obj       = new OperacionesBd;
        $conexion  = $obj->conexion();
        $resultado = mysqli_query($conexion, $sql);
        return mysqli_fetch_assoc($resultado);
    }

    public function eliminardatos($sql) {
        $obj      = new OperacionesBd;
        $conexion = $obj->conexion();
        mysqli_query($conexion, $sql);
    }

    public function actualizadatos($sql) {
        $obj      = new OperacionesBd;
        $conexion = $obj->conexion();
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
