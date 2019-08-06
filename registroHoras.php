<?php

$respuesta ["correcto"] = false;
$respuesta ["datos"] = [];
$respuesta ["error"] = "";

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $admin = isset($_POST['admin']) ? $_POST['admin'] : 0;
        $user = isset($_POST['codigo']) ? $_POST['codigo'] : "";
        $fechaInicio = isset($_POST['fechaInicio']) ? $_POST['fechaInicio'] : "2000-01-01";
        $fechaFin = isset($_POST['fechaFin']) ? $_POST['fechaFin'] : null;

        if ($fechaFin == null) { // Obtenemos el momento de hoy
            $HOY = getdate();
            $fechaFin = $HOY["year"] . "-";
            if ($HOY["mon"] < 10) {
                $fechaFin .= "0";
            }
            $fechaFin .= $HOY["mon"] . "-";
            if ($HOY["mday"] < 10) {
                $fechaFin .= "0";
            }
            $fechaFin .= $HOY["mday"];
        }
        if($user!="") {
            $fechaInicio .= " 00:00:00.000";
            $fechaFin .= " 23:59:59.999";
        } else { // Así podremos buscar los registros de hoy
            $fechaInicio =  $fechaFin." 00:00:00.000";
        }



        // Conexion manual
        $conn = new PDO("mysql:host=localhost; dbname=metis", "root", "ea6uw1001",
            array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        // Conexion Manual

        $stm = $conn->prepare("SELECT * FROM Usuarios WHERE codigo=:user ;");
        $stm->bindValue(':user', !empty($admin) ? $admin : NULL, PDO::PARAM_STR);
        $stm->execute();
        $tuples2 = $stm->fetchAll();

        if (array_key_exists(0, $tuples2)) {
            if ($tuples2[0]["permiso"] == 1 || $tuples2[0]["permiso"] == 2) {

                if($user!="") {// Muestra los registros entre esas fechas

                    $stm = $conn->prepare("SELECT * FROM Registro WHERE fecha <= :fechaFin AND fecha >= :fechaInicio AND usuario=:user ORDER BY `Registro`.`fecha` ASC;");
                    $stm->bindValue(':fechaInicio', !empty($fechaInicio) ? $fechaInicio : "2000-01-01 00:00:00.000", PDO::PARAM_STR);
                    $stm->bindValue(':fechaFin', !empty($fechaFin) ? $fechaFin : "2000-01-01 00:00:00.000", PDO::PARAM_STR);
                    $stm->bindValue(':user', !empty($user) ? $user : NULL, PDO::PARAM_STR);
                    $stm->execute();
                    $tuples = $stm->fetchAll();

                    $respuesta["datos"] = $tuples;
                    $respuesta ["correcto"] = true;
                } else { //Muestra los registros de hoy
                    $stm = $conn->prepare("SELECT * FROM Registro WHERE fecha >= :fechaInicio ORDER BY `Registro`.`fecha` ASC;");
                    $stm->bindValue(':fechaInicio', !empty($fechaInicio) ? $fechaInicio : "2000-01-01 00:00:00.000", PDO::PARAM_STR);
                    $stm->execute();
                    $tuples = $stm->fetchAll();

                    $respuesta["datos"] = $tuples;
                    $respuesta ["correcto"] = true;
                }

            } else {
                $respuesta["correcto"] = false;
                $respuesta["error"] = "No tienes permisos para aceder a esta opcion";
            }
        } else {
            $respuesta["correcto"] = false;
            $respuesta["error"] = "Login incorrecto";
        }
    } else {
        $respuesta["correcto"] = false;
        $respuesta["error"] = "Request no valido";
    }
} catch (PDOException $e) {
    $respuesta ["correcto"] = true;
    $respuesta ["error"] = $e->getMessage();
}
echo json_encode($respuesta);
$conn = null;

?>