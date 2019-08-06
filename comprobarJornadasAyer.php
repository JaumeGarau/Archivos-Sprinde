<?php

$respuesta ["correcto"] = false;
$respuesta ["datos"] = [];
$respuesta ["error"] = "";

function crearIncidencia($rutaMail, $mails, $mensaje, $gravedad)
{ // crearIncidencia ($rutaMail, $mails, $respuesta["error"], "Inicidencia grave"); // Recuerda que las leves no se envian
    // Puedes enviarlo a el usuario así: crearIncidencia ($tuples[0]["email"], $mails, $respuesta["error"], "Inicidencia grave");
    /*try {
        $ch = curl_init($rutaMail);
        $postData = "motivo=" . $gravedad . "&mensaje=" . $mensaje . "&destinatarios=" . $mails . "";
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
        $result = curl_exec($ch);
    } catch (Exception $e) {
        throw new Exception($e);
    }*/
}

try {
    date_default_timezone_set("Europe/Madrid");// "Europe/Madrid" "Etc/GMT+1"
    $datos ["correcto"] = null;
    $datos ["diferencia"] = 0;
    $rutaMail = isset($_POST['ruta']) ? $_POST['ruta'] : "http://localhost/rest-metis/sendmail.php";
    $mailsG = isset($_POST['mails']) ? $_POST['mails'] : "mails";
    $HOY = getdate();
    $DiasSemana = ["D", "L", "M", "X", "J", "V", "S", "D"];
    $DiaHoy = $HOY["wday"] - 1;
    $DiaSemana = $DiasSemana[$DiaHoy];

    $fechaHoy = $HOY["year"] . "-";
    if ($HOY["mon"] < 10) {
        $fechaHoy .= "0";
    }
    $fechaHoy .= $HOY["mon"] . "-";
    if ($HOY["mday"] < 10) {
        $fechaHoy .= "0";
    }
    $fechaHoy .= $HOY["mday"];
    $fechaAyer2 = date("Y-m-d", strtotime($fechaHoy . "- 1 days"));
    $fechaHoy .= " 00:00:00.000";
    $fechaAyer = $fechaAyer2 . " 00:00:00.000";

    // Conexion manual
    $conn = new PDO("mysql:host=localhost; dbname=metis", "root", "ea6uw1001",
        array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    // Conexion Manual

    $stm = $conn->prepare("SELECT * FROM Usuarios;");
    $stm->execute();
    $tuples = $stm->fetchAll();

    $stm = $conn->prepare("SELECT * FROM Registro WHERE fecha >= :fechaAyer AND fecha <= :fechaHoy AND extra = 0 ORDER BY `Registro`.`fecha` ASC;");//SELECT * FROM Registro WHERE fecha >= "2019-07-30 00:00:00" AND fecha <= "2019-07-31 00:00:00" AND tipo = 'Inicio' ORDER BY `Registro`.`fecha`  DESC
    $stm->bindValue(':fechaAyer', !empty($fechaAyer) ? $fechaAyer : "2010-01-01 00:00:00.000", PDO::PARAM_STR);
    $stm->bindValue(':fechaHoy', !empty($fechaHoy) ? $fechaHoy : "2010-01-01 00:00:00.000", PDO::PARAM_STR);
    $stm->execute();
    $tuples2 = $stm->fetchAll();

    //Cogemos como deberia ser la jornada
    $stm = $conn->prepare("SELECT * FROM Jornada;");
    $stm->execute();
    $tuples3 = $stm->fetchAll();

    //Cogemos los mails a los que enviaremos los correos de los incidentes
    $stm = $conn->prepare("SELECT * FROM Globales WHERE clave=:mailsG;");//$mailsG
    $stm->bindValue(':mailsG', !empty($mailsG) ? $mailsG : NULL, PDO::PARAM_STR);
    $stm->execute();
    $tuples4 = $stm->fetchAll();

    $mails = str_replace(";", ", ", $tuples4[0]["valor"]);

    foreach ($tuples as $clave => $valor) {
        $usuario = [];
        $jornadaUsuario = [];

        foreach ($tuples2 as $clave2 => $valor2) {
            if ($valor["codigo"] == $valor2["usuario"]) {
                array_push($usuario, $valor2);
                unset($tuples2[$clave2]);
            }
        }

        foreach ($tuples3 as $clave3 => $valor3) {
            if ($valor["jornada"] == $valor3["nombreJornada"]) {
                $jornadaUsuario = $valor3; // !! Tots buits?
                break;
            }
        }

        if (array_key_exists($DiaSemana, $jornadaUsuario)) {
            $jornadaAyer = $jornadaUsuario[$DiaSemana];
            $jornadaAyer = preg_split("[,]", $jornadaAyer, null);

            $entradasUsuario = count($usuario);
            $entradasJornada = count($jornadaAyer) * 2;
            if ($entradasUsuario < $entradasJornada) {
                $mensaje = "El usuario " . $valor["nombre"] . " (" . $valor["codigo"] . ") tiene " . $entradasUsuario . " registros para los " . $entradasJornada . ", asignados dia " . $fechaAyer2 . ". \n Este mensaje es autogenerado por Metis";
                crearIncidencia($rutaMail, $mails, $mensaje, "Incidencia Grave");
                // Insertar notificacion
                    $stm = $conn->prepare("INSERT INTO `Avisos` (`usuario`, `fecha`, `comentario`,  `jornada`, `extra`, `prioridad`) VALUES (:user, :ahora, :comentario, :jornada, :extra, :prioridad);");
                    $stm->bindValue(':user', !empty($valor["codigo"]) ? $valor["codigo"] : NULL, PDO::PARAM_STR);
                    $stm->bindValue(':ahora', !empty($fechaAyer) ? $fechaAyer : NULL, PDO::PARAM_STR);
                    $stm->bindValue(':comentario', !empty($mensaje) ? $mensaje : NULL, PDO::PARAM_STR);
                    $stm->bindValue(':jornada', !empty($valor["jornada"]) ? $valor["jornada"] : NULL, PDO::PARAM_STR);
                    $stm->bindValue(':extra', 0, PDO::PARAM_INT);
                    $stm->bindValue(':prioridad', 2, PDO::PARAM_INT);
                    $stm->execute();
            }
        }
        array_push($respuesta["datos"], $usuario);
        $respuesta ["correcto"] = true;

    }

} catch (PDOException $e) { //Error durante el codigo
    $respuesta ["correcto"] = false;
    $respuesta ["error"] = $e->getMessage();
}
$conn = null;
echo json_encode($respuesta);
