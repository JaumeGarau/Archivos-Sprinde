<?php

$respuesta ["correcto"] = false;
$respuesta ["datos"] = null;
$respuesta ["error"] = "";

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $user = isset($_POST['codigo']) ? $_POST['codigo'] : 0;

        // Conexion manual
        $conn = new PDO("mysql:host=localhost; dbname=metis", "root", "ea6uw1001",
            array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        // Conexion Manual

        $stm = $conn->prepare("SELECT * FROM Usuarios WHERE codigo=:user ;");
        $stm->bindValue(':user', !empty($user) ? $user : NULL, PDO::PARAM_STR);
        $stm->execute();

        $tuples = $stm->fetchAll();

        if (array_key_exists(0, $tuples)) {
            $datos["usuario"] = $tuples[0];
            $stm = $conn->prepare("SELECT * FROM Registro WHERE usuario=:user ORDER BY fecha DESC;");
            $stm->bindValue(':user', !empty($user) ? $user : NULL, PDO::PARAM_STR);
            $stm->execute();
            $tuples2 = $stm->fetchAll();
            if ($tuples2 != null) {
                $datos["hora"] = $tuples2[0]["fecha"];
                $datos["tipo"] = $tuples2[0]["tipo"];
            } else {
                $datos["hora"] = "";
                $datos["tipo"] = "";
            }
            $respuesta ["datos"] = $datos;
            $respuesta ["correcto"] = true;
        } else {
            $respuesta ["correcto"] = false;
            $respuesta ["error"] = "Login Incorrecto";
        }
    } else {
        $respuesta ["correcto"] = false;
        $respuesta ["error"] = "Request no valido";
    }
} catch (PDOException $e) {
    $respuesta ["correcto"] = false;
    $respuesta ["error"] = $e->getMessage();
}
$conn = null;


echo json_encode($respuesta);
?>
