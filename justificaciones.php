<?php

$respuesta ["correcto"] = false;
$respuesta ["datos"] = [];
$respuesta ["error"] = "";

try {
    // Conexion manual
    $conn = new PDO("mysql:host=localhost; dbname=metis", "root", "ea6uw1001",
        array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    // Conexion Manual

    $stmt = $conn->prepare("SELECT * FROM Justificaciones;");
    $stmt->execute();

    $tuples = $stmt->fetchAll();
    foreach ($tuples as $clave => $valor) {
            array_push($respuesta["datos"], $tuples[$clave]["justificacion"]);
    }
    $respuesta ["correcto"] = true;
}
catch(PDOException $e) {
    $respuesta ["correcto"] = true;
    $respuesta ["error"] = $e->getMessage();
}
echo json_encode($respuesta);
$conn = null;

/*
    // Conexion manual
    $conn = new PDO("mysql:host=192.168.244.151; dbname=metis", "usuario1","EA6-uwmil1@",
        array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    // Conexion Manual
  */

?>
