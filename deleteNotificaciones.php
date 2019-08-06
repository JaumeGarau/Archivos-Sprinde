<?php

header("Allow: POST, DELETE");

$respuesta ["correcto"] = false;
$respuesta ["datos"] = [];
$respuesta ["error"] = "";


try {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {// opcion para obtener las notificaciones
        $admin = isset($_POST['admin']) ? $_POST['admin'] : 0;

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
                $avisos = isset($_POST['avisos']) ? $_POST['avisos'] : ""; // deben pasar un string asi: "0,2,1" no hace falta ordenar
                $avisos = explode(",", $avisos);
                $correcto = true;
                $respuesta["datos"]["id"] = "";
                if (is_array ($avisos)) {
                    for ($i = 0; $i < count($avisos); $i++) {
                        $idAvisos = (int)$avisos[$i];
                        if (is_int($idAvisos)){ //Borraremos uno en uno para poder usar adecuadamente el bindParam y evitar inserciones faciles de codigo
                            $stmt = $conn->prepare("DELETE FROM Avisos WHERE id = :identificador ;");
                            $stmt->bindParam(':identificador', $idAvisos, PDO::PARAM_INT);
                            $stmt->execute();
                        } else {
                            $respuesta["datos"]["id"] .= $idAvisos.",";
                            $correcto = false;
                            $respuesta["error"] = "Algunos ID no han funcionado adecuadamente";
                        }
                    }
                    $respuesta["correcto"] = $correcto;
                } else {
                    $respuesta["correcto"] = false;
                    $respuesta["error"] = "Se requiere un array de los ID de Avisos";
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