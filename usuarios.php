<?php

$respuesta ["correcto"] = false;
$respuesta ["datos"] = [];
$respuesta ["error"] = "";
try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $user = isset($_POST['admin']) ? $_POST['admin'] : 0;

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

        if (array_key_exists(0, $tuples)) { // Comprovamos que sea un usuario con permisos
            if ($tuples[0]["permiso"] == 1 || $tuples[0]["permiso"] == 2) {

                $stmt = $conn->prepare("SELECT codigo, nombre FROM `Usuarios` where permiso = 0;");
                $stmt->execute();

                $tuples2 = $stmt->fetchAll();

                foreach ($tuples2 as $clave => $valor) { //Devolvemos un string para cada opcion que el movil separara para obtener el codigo
                    array_push($respuesta["datos"], $valor["codigo"] . ' - ' . $valor["nombre"]);
                }

                $respuesta ["correcto"] = true;
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