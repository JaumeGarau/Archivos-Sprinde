<?php

$respuesta ["correcto"] = false;
$respuesta ["datos"] = [];
$respuesta ["error"] = "";

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        $motivo = isset($_POST['motivo']) ? $_POST['motivo'] : "";
        $comentario = isset($_POST['mensaje']) ? $_POST['mensaje'] : "";
        $destinatarios = isset($_POST['destinatarios']) ? $_POST['destinatarios'] : "";

        $headers = 'From: servicios@tsigo.es' . "\r\n" .
            'Reply-To: servicios@tsigo.es' . "\r\n" .
            'X-Mailer: PHP/' . phpversion();

        if(mail($destinatarios, $motivo, $comentario, $headers)){
            $respuesta ["correcto"] = true;
            $respuesta ["error"] = "";
        } else {
            $respuesta ["correcto"] = false;
            $respuesta ["error"] = "Mensaje no enviado";
        }
    } else {
        $respuesta["correcto"] = false;
        $respuesta["error"] = "Request no valido";
    }
} catch (Exception $e) {
    $respuesta ["correcto"] = false;
    $respuesta ["datos"] = "error inesperado";
    $respuesta ["error"] .= $e->getMessage();
}
echo json_encode($respuesta);
$conn = null;
?>