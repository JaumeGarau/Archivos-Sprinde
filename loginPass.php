<?php

$respuesta ["correcto"] = false;
$respuesta ["datos"] = null;
$respuesta ["error"] = "";

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $user = isset($_POST['user']) ? $_POST['user'] : "";
        $pass = isset($_POST['pass']) ? $_POST['pass'] : "";
        $imei = isset($_POST['imei']) ? $_POST['imei'] : "";

        // Conexion manual
        $conn = new PDO("mysql:host=192.168.244.151; dbname=metis", "usuario1", "EA6-uwmil1@",
            array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        // Conexion Manual

        $stm = $conn->prepare("SELECT * FROM Usuarios WHERE email=:user AND contrasenya=:pass;");
        $stm->bindValue(':user', !empty($user) ? $user : NULL, PDO::PARAM_STR);
        $stm->bindValue(':pass', !empty($pass) ? $pass : NULL, PDO::PARAM_STR);
        $stm->execute();

        $tuples = $stm->fetchAll();

        if (array_key_exists(0, $tuples)) {
            $datos["usuario"] = $tuples[0];
            $user2 = $datos["usuario"]["codigo"];
            $stm = $conn->prepare("SELECT * FROM Registro WHERE usuario=:user ORDER BY fecha DESC;");
            $stm->bindValue(':user', !empty($user2) ? $user2 : NULL, PDO::PARAM_STR);
            $stm->execute();
            $tuples2 = $stm->fetchAll();
            if ($tuples2 != null) {
                $datos["hora"] = $tuples2[0]["fecha"];
                $datos["tipo"] = $tuples2[0]["tipo"];
            } else {
                $datos["hora"] = "";
                $datos["tipo"] = "";
            }
            if (array_key_exists("imei", $datos["usuario"])) {
                if ($datos["usuario"]["imei"] == null || $datos["usuario"]["imei"] == "" AND $imei != "" || $imei != null) {
                    $stm = $conn->prepare("UPDATE Usuarios SET imei= :imei WHERE codigo= :user;");
                    $stm->bindValue(':imei', !empty($imei) ? $imei : NULL, PDO::PARAM_STR);
                    $stm->bindValue(':user', !empty($user2) ? $user2 : NULL, PDO::PARAM_STR);
                    $stm->execute();
                    $datos["usuario"]["imei"] = $imei;
                }
            }
            $respuesta["datos"] = $datos;
            $respuesta["correcto"] = true;
        } else {
            $respuesta["correcto"] = false;
            $respuesta["error"] = "Login Incorrecto, " . $user . ', ' . $pass . ', ' . $imei . ', <br>' . json_encode($_POST);
        }
    } else {
        $respuesta["correcto"] = false;
        $respuesta["error"] = "Request no valido";
    }
} catch (PDOException $e) {
    $respuesta["correcto"] = false;
    $respuesta["error"] = $e->getMessage();
}
$conn = null;
echo json_encode($respuesta);
?>