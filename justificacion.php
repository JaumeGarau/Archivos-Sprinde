<?php

$respuesta ["correcto"] = false;
$respuesta ["datos"] = null;
$respuesta ["error"] = "";

function insertRegistro($conn, $user, $ahora, $comentario, $ubicacion, $jornada, $tipo, $extra, $prioridad)
{   // Inserta el registro
    $stm = $conn->prepare("INSERT INTO `Registro` (`usuario`, `fecha`, `comentario`, `ubicacion`, `jornada`, `tipo`, `extra`) VALUES (:user, :ahora, :comentario, :ubicacion, :jornada, :tipo, :extra);");
    $stm->bindValue(':user', !empty($user) ? $user : NULL, PDO::PARAM_STR);
    $stm->bindValue(':ahora', !empty($ahora) ? $ahora : NULL, PDO::PARAM_STR);
    $stm->bindValue(':comentario', !empty($comentario) ? $comentario : NULL, PDO::PARAM_STR);
    $stm->bindValue(':ubicacion', !empty($ubicacion) ? $ubicacion : NULL, PDO::PARAM_STR);
    $stm->bindValue(':jornada', !empty($jornada) ? $jornada : NULL, PDO::PARAM_STR);
    $stm->bindValue(':tipo', !empty($tipo) ? $tipo : NULL, PDO::PARAM_STR);
    $stm->bindValue(':extra', !empty($extra) ? $extra : 0, PDO::PARAM_INT);
    $stm->execute();

    // Insertar notificacion siempre que tenga prioridad
    if ($prioridad != null) {
        $stm = $conn->prepare("INSERT INTO `Avisos` (`usuario`, `fecha`, `comentario`, `ubicacion`, `jornada`, `tipo`, `extra`, `prioridad`) VALUES (:user, :ahora, :comentario, :ubicacion, :jornada, :tipo, :extra, :prioridad);");
        $stm->bindValue(':user', !empty($user) ? $user : NULL, PDO::PARAM_STR);
        $stm->bindValue(':ahora', !empty($ahora) ? $ahora : NULL, PDO::PARAM_STR);
        $stm->bindValue(':comentario', !empty($comentario) ? $comentario : NULL, PDO::PARAM_STR);
        $stm->bindValue(':ubicacion', !empty($ubicacion) ? $ubicacion : NULL, PDO::PARAM_STR);
        $stm->bindValue(':jornada', !empty($jornada) ? $jornada : NULL, PDO::PARAM_STR);
        $stm->bindValue(':tipo', !empty($tipo) ? $tipo : NULL, PDO::PARAM_STR);
        $stm->bindValue(':extra', !empty($extra) ? $extra : 0, PDO::PARAM_INT);
        $stm->bindValue(':prioridad', !empty($prioridad) ? $prioridad : 0, PDO::PARAM_INT);
        $stm->execute();
    }
}

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
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        //variables
        date_default_timezone_set("Europe/Madrid");// "Europe/Madrid" "Etc/GMT+1"
        $datos ["correcto"] = null;
        $datos ["diferencia"] = 0;
        $HOY = getdate();
        $DiasSemana = ["D", "L", "M", "X", "J", "V", "S", "D"];
        $user = isset($_POST['codigo']) ? $_POST['codigo'] : 0;
        $ubicacion = isset($_POST['ubicacion']) ? $_POST['ubicacion'] : "";
        $comentario = isset($_POST['comentario']) ? $_POST['comentario'] : "";
        $claveG = isset($_POST['clave']) ? $_POST['clave'] : "diferencia"; //Estos siguentes strings se puede editar por si fuera necessario tener diferencias entre departamentos
        $mailsG = isset($_POST['mails']) ? $_POST['mails'] : "mails";
        $rutaMail = isset($_POST['ruta']) ? $_POST['ruta'] : "http://localhost/rest-metis/sendmail.php"; // http://2.136.31.62:8070/SendMailMetis/rest/user/email //http://192.168.244.151:8070/SendMailMetis/rest/user/email ## Es diferente el host de producion a local

        if ($ubicacion == null) {
            $ubicacion = "";
        }
        if ($comentario == null) {
            $comentario = "";
        }

        //Obtendremos la fecha para hacer busquedas
        $fechaHoy = $HOY["year"] . "-";
        if ($HOY["mon"] < 10) {
            $fechaHoy .= "0";
        }
        $fechaHoy .= $HOY["mon"] . "-";
        if ($HOY["mday"] < 10) {
            $fechaHoy .= "0";
        }
        $fechaHoy .= $HOY["mday"];

        $stringfecha = $fechaHoy . " 00:00:00.000"; //Ponemos 0 H para filtrar tod0 el dia
        $diaHoy = $HOY["wday"]; //date('w');
        $DiaSemana = $DiasSemana[$diaHoy]; //Así tendremos el dia de hoy

        $ahora = $fechaHoy . " " . $HOY["hours"] . ':' . $HOY["minutes"] . ':' . $HOY["seconds"]; //Pondremos el momento actual para nuestro registro

        // Conexion manual
        $conn = new PDO("mysql:host=localhost; dbname=metis", "root", "ea6uw1001",
            array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        // Conexion Manual

        //comprobamos que el usuario exista
        $stm = $conn->prepare("SELECT * FROM Usuarios WHERE codigo=:user ;");
        $stm->bindValue(':user', !empty($user) ? $user : NULL, PDO::PARAM_STR);
        $stm->execute();

        $tuples = $stm->fetchAll();

        if (array_key_exists(0, $tuples)) { // Existe el usuario

            if ($tuples[0]["permiso"] == 0) { // Solo entrara si el usuario puede fitchar
                // Cogemos los registros
                $stm = $conn->prepare("SELECT * FROM Registro WHERE usuario=:user ORDER BY fecha DESC LIMIT 50;");
                $stm->bindValue(':user', !empty($user) ? $user : NULL, PDO::PARAM_STR);
                $stm->execute();
                $tuples2 = $stm->fetchAll();

                //Cogemos como deberia ser la jornada
                $stm = $conn->prepare("SELECT * FROM Jornada WHERE nombreJornada=:user;");
                $stm->bindValue(':user', !empty($tuples[0]["jornada"]) ? $tuples[0]["jornada"] : NULL, PDO::PARAM_STR);
                $stm->execute();
                $tuples3 = $stm->fetchAll();

                if (array_key_exists(0, $tuples3)) { // Miramos si tiene globales
                    //Cogemos la constante de la diferencia, podria estar arriba
                    $stm = $conn->prepare("SELECT * FROM Globales;");
                    $stm->execute();
                    $tuples4 = $stm->fetchAll();
                    $GlobalesEmpressa = [];

                    foreach ($tuples4 as $clave => $valor) {
                        $GlobalesEmpressa[$valor["clave"]] = $valor["valor"];
                    }

                    if (array_key_exists($claveG, $GlobalesEmpressa)) {
                        $diferecia = "";

                        if ($GlobalesEmpressa[$claveG] < 10) {
                            $diferecia .= "0";
                        }
                        $diferecia .= $GlobalesEmpressa[$claveG]; //diferencia de tu empressa aceptada

                        if (!array_key_exists($mailsG, $GlobalesEmpressa)) {
                            $GlobalesEmpressa[$mailsG] = "";
                        }

                        $mails = str_replace(";", ", ", $GlobalesEmpressa[$mailsG]);

                        if (array_key_exists(0, $tuples2)) {
                            $ultimoDia = date($tuples2[0]["fecha"]);
                            $filtroDia = date('Y-m-d', strtotime($ultimoDia)) . " 00:00:00";

                            if ($ultimoDia < $stringfecha) { // Comprobamos si su jornada de hoy esta empezada
                                // Buscaremos el dia anterior para comprobar la jornada
                                if ($tuples2[0]["tipo"] != "Fin" && $tuples2[0]["tipo"] != "Salida") { //!!! Aqui hay que quitar el filtro de salida

                                    if ($tuples2[0]["tipo"] == "Salida") {// Si tiene un registro de ayer en salida le cerraremos ahora su jornada
                                        $tipo = "Entrada";
                                        $comentario2 = "Entrada autocreada por el sistema";

                                        insertRegistro($conn, $user, $ahora, $comentario, $ubicacion, $tuples2[0]["jornada"], $tipo, $tuples2[0]["extra"], null);
                                    }

                                    // Le registraremos una salida para su jornada no finalizada
                                    $tipo = "Fin";
                                    $comentario = "Finalizacion fuera del horario laboral: " . $comentario;

                                    // separamos nuestra semana laboral en un array
                                    $numUltimoDia = $DiasSemana[date('w', strtotime($ultimoDia))];

                                    $jornadaHoy = $tuples3[0][$numUltimoDia];
                                    $jornadaHoy = preg_split("[,]", $jornadaHoy, null);
                                    foreach ($jornadaHoy as $clave => $valor) {
                                        $jornadaHoy[$clave] = preg_split("[-]", $valor, null);
                                    }

                                    $maxJornada = count($jornadaHoy) - 1; //obtienes el maximo

                                    $tipoEntrada = ($maxJornada % 2);
                                    $numEntrada = ($maxJornada - $tipoEntrada) / 2;
                                    if ($tipoEntrada > 1) {
                                        $tipoEntrada = 0;
                                        $numEntrada++;
                                    }
                                    $valorJornada = preg_split("[:]", $jornadaHoy[$numEntrada][$tipoEntrada], null); // Cogemos el ultimo registro
                                    if (!array_key_exists(0, $valorJornada) || is_string($valorJornada[0])) {
                                        $valorJornada[0] = 0;
                                    }
                                    if (!array_key_exists(1, $valorJornada) || is_string($valorJornada[0])) {
                                        $valorJornada[1] = 0;
                                    }

                                    $curDif = $HOY["minutes"] + $HOY["hours"] * 60 - $valorJornada[1] - $valorJornada[0] * 60; //Calculamos la diferencia

                                    insertRegistro($conn, $user, $ahora, $comentario, $ubicacion, $tuples2[0]["jornada"], $tipo, 1, 2);
                                    $mensajeError = "La entrada del " . $ultimoDia . ", del usuario " . $tuples[0]["nombre"] . " se cerro en " . $ahora . " . \n Comentario: " . $comentario . " \n Este mensaje es autogenerado por Metis ";
                                    crearIncidencia($rutaMail, $mails, $mensajeError, "Inicidencia grave");
                                    $datos["correcto"] = true;
                                    $datos["diferencia"] = $curDif;

                                    if ($tuples2[0]["extra"] == 1) {
                                        $i = 0;
                                        $maxEach = count($tuples2);
                                        while ($i < $maxEach && $tuples2[$i]["extra"] == 0) {
                                            $i++;
                                        }
                                        if ($tuples2[$i]["extra"] == 0 && $tuples2[$i]["tipo"] != "Fin" && $tuples2[$i]["tipo"] != "Salida") { // !!!! Cambiar cuando se necessario que finalizen en fin
                                            insertRegistro($conn, $user, $ahora, $comentario, $ubicacion, $tuples2[$i]["jornada"], $tipo, 1, 2);
                                            $mensajeError = "La entrada del " . $ultimoDia . ", del usuario " . $tuples[0]["nombre"] . " se cerro en " . $ahora . " . \n Comentario: " . $comentario . " \n Este mensaje es autogenerado por Metis ";
                                            crearIncidencia($rutaMail, $mails, $mensajeError, "Inicidencia grave");
                                        }
                                    }

                                } else if ($tuples3[0][$DiaSemana] != null && strlen($tuples3[0][$DiaSemana]) > 4) {
                                    //No tiene registros de la jornada de hoy, así que registraremos sin comprobar los demas registros, solo registrando a partir del horario
                                    $tipo = "Inicio";
                                    // separamos nuestra semana laboral en un array
                                    $jornadaHoy = $tuples3[0][$DiaSemana];
                                    $jornadaHoy = preg_split("[,]", $jornadaHoy, null);
                                    foreach ($jornadaHoy as $clave => $valor) {
                                        $jornadaHoy[$clave] = preg_split("[-]", $valor, null);
                                    }
                                    $valorJornada = preg_split("[:]", $jornadaHoy[0][0], null); // Cogemos el primer registro

                                    if (!array_key_exists(1, $valorJornada)) { // !!!Por si la jornada tiene solo una entrada, comprovador de jornadas
                                        $valorJornada[1] = 0;
                                    }
                                    $curDif = $HOY["minutes"] + $HOY["hours"] * 60 - $valorJornada[1] - $valorJornada[0] * 60; //Calculamos la diferencia

                                    $comentario = "Inicio justificado: " . $comentario;

                                    insertRegistro($conn, $user, $ahora, $comentario, $ubicacion, $tuples[0]["jornada"], $tipo, 0, 1);
                                    $datos["correcto"] = true;
                                    $datos["diferencia"] = $curDif;
                                } else {
                                    $tipo = "Inicio";
                                    $comentario = "Inicio fuera de su jornada laboral: " . $comentario;

                                    insertRegistro($conn, $user, $ahora, $comentario, $ubicacion, $tuples[0]["jornada"], $tipo, 1, 2);
                                    $mensajeError = "La entrada del usuario " . $tuples[0]["nombre"] . " se genero fuera de la jornada asignada, en " . $ahora . " . \n Comentario: " . $comentario . " \n Este mensaje es autogenerado por Metis ";
                                    crearIncidencia($rutaMail, $mails, $mensajeError, "Inicidencia grave");
                                    $datos["correcto"] = true;
                                    $datos["diferencia"] = null;
                                }

                            } else { // Seguiremos nuestra jornada
                                $registroHoy = [];
                                $jornadaHoy = [];

                                if ($tuples3[0][$DiaSemana] != null && strlen($tuples3[0][$DiaSemana]) > 4) { // separamos nuestra semana laboral en un array
                                    $jornadaHoy = $tuples3[0][$DiaSemana];
                                    $jornadaHoy = preg_split("[,]", $jornadaHoy, null);
                                    foreach ($jornadaHoy as $clave => $valor) {
                                        $jornadaHoy[$clave] = preg_split("[-]", $valor, null);
                                    }

                                    foreach ($tuples2 as $clave => $valor) {
                                        if ($valor["fecha"] >= $filtroDia && $valor["extra"] == 0) {
                                            array_push($registroHoy, $valor["fecha"]);
                                        }
                                    }
                                    $numJornadaHoy = count($registroHoy); //Obtenemos cuantos tienes + 1

                                    $tipoEntrada = ($numJornadaHoy % 2);
                                    $numEntrada = ($numJornadaHoy - $tipoEntrada) / 2;
                                    if ($tipoEntrada > 1) {
                                        $tipoEntrada = 0;
                                        $numEntrada++;
                                    }

                                    if (array_key_exists($numEntrada, $jornadaHoy)) {
                                        if (array_key_exists($tipoEntrada, $jornadaHoy[$numEntrada])) {
                                            $valorJornada = preg_split("[:]", $jornadaHoy[$numEntrada][$tipoEntrada], null); // Cogemos el primer registro
                                            $curDif = $HOY["minutes"] + $HOY["hours"] * 60 - $valorJornada[1] - $valorJornada[0] * 60; //Calculamos la diferencia
                                            $tipo = "Entrada";

                                            if ($tipoEntrada == 0 && $numEntrada == 0) {
                                                $comentario = "Inicio justificado: " . $comentario;
                                                $tipo = "Inicio";
                                            } else if ($tipoEntrada == 0) {
                                                $comentario = "Entrada justificado: " . $comentario;
                                                $tipo = "Entrada";
                                            } else if (!array_key_exists($numEntrada + 1, $jornadaHoy)) {
                                                $comentario = "Fin justificado: " . $comentario;
                                                $tipo = "Fin";
                                            } else {
                                                $comentario = "Salida justificado: " . $comentario;
                                                $tipo = "Salida";
                                            }


                                            insertRegistro($conn, $user, $ahora, $comentario, $ubicacion, $tuples2[0]["jornada"], $tipo, 0, 1);
                                            $datos["correcto"] = true;
                                            $datos["diferencia"] = $curDif;
                                        } else {// No es su horario de Trabajo
                                            if ($tuples2[0]["tipo"] == "Entrada" || $tuples2[0]["tipo"] == "Inicio") { // !!! Cuando sea possible hacer descansos hay que quitar la opcion de cerrarlo, ya que los inicios de descansos los iniciara otro php
                                                $comentario = "Finalizacion fuera de su jornada laboral: " . $comentario;
                                                $tipo = "Fin";
                                            } else {
                                                $comentario = "Inicio fuera de su jornada laboral: " . $comentario;
                                                $tipo = "Inicio";
                                            }
                                            insertRegistro($conn, $user, $ahora, $comentario, $ubicacion, $tuples[0]["jornada"], $tipo, 1, 1);
                                            $datos["correcto"] = true;
                                            $datos["diferencia"] = null;
                                        }
                                    } else { // No es su horario de Trabajo
                                        if ($tuples2[0]["tipo"] == "Entrada" || $tuples2[0]["tipo"] == "Inicio") {
                                            $comentario = "Finalizacion fuera de su jornada laboral: " . $comentario;
                                            $tipo = "Fin";
                                        } else {
                                            $comentario = "Inicio fuera de su jornada laboral: " . $comentario;
                                            $tipo = "Inicio";
                                        }
                                        insertRegistro($conn, $user, $ahora, $comentario, $ubicacion, $tuples[0]["jornada"], $tipo, 1, 2);
                                        $mensajeError = "La entrada del usuario " . $tuples[0]["nombre"] . " se genero fuera de la jornada asignada, en " . $ahora . " . \n Comentario: " . $comentario . " \n Este mensaje es autogenerado por Metis ";
                                        crearIncidencia($rutaMail, $mails, $mensajeError, "Inicidencia grave");
                                        $datos["correcto"] = true;
                                        $datos["diferencia"] = null;
                                    }
                                } else { //!!!Aqui me falta es registro millorat
                                    if ($tuples2[0]["tipo"] == "Entrada" || $tuples2[0]["tipo"] == "Inicio") {
                                        $comentario = "Finalizacion fuera de su jornada laboral: " . $comentario;
                                        $tipo = "Fin";
                                    } else {
                                        $comentario = "Inicio fuera de su jornada laboral: " . $comentario;
                                        $tipo = "Inicio";
                                    }

                                    insertRegistro($conn, $user, $ahora, $comentario, $ubicacion, $tuples2[0]["jornada"], $tipo, 1, 2);
                                    $mensajeError = "La entrada del usuario " . $tuples[0]["nombre"] . " se genero fuera de la jornada asignada, en " . $ahora . " . \n Comentario: " . $comentario . " \n Este mensaje es autogenerado por Metis ";
                                    crearIncidencia($rutaMail, $mails, $mensajeError, "Inicidencia grave");
                                    $datos["correcto"] = true;
                                    $datos["diferencia"] = null;
                                }
                            }

                        } else {
                            //No tiene registros anteriores así que registraremos sin comprobar los demas registros, solo registrando a partir del horario
                            $tipo = "Inicio";
                            if ($tuples3[0][$DiaSemana] != null && strlen($tuples3[0][$DiaSemana]) > 4) {//Comprovamos que hoy nos toca trabajar
                                $comentario = "Inicio: " . $comentario;
                                // separamos nuestra semana laboral en un array
                                $jornadaHoy = $tuples3[0][$DiaSemana];
                                $jornadaHoy = preg_split("[,]", $jornadaHoy, null);
                                foreach ($jornadaHoy as $clave => $valor) {
                                    $jornadaHoy[$clave] = preg_split("[-]", $valor, null);
                                }
                                $valorJornada = preg_split("[:]", $jornadaHoy[0][0], null); // Cogemos el primer registro

                                $curDif = $HOY["minutes"] + $HOY["hours"] * 60 - $valorJornada[1] - $valorJornada[0] * 60; //Calculamos la diferencia

                                insertRegistro($conn, $user, $ahora, $comentario, $ubicacion, $tuples[0]["jornada"], $tipo, 0, 1);
                                $datos["correcto"] = true;
                                $datos["diferencia"] = $curDif;
                            } else {
                                $tipo = "Inicio";
                                $comentario = "Inicio fuera de su jornada laboral: " . $comentario;

                                insertRegistro($conn, $user, $ahora, $comentario, $ubicacion, $tuples[0]["jornada"], $tipo, 1, 2);
                                $mensajeError = "La entrada del usuario " . $tuples[0]["nombre"] . " se genero fuera de la jornada asignada, en " . $ahora . " . \n Comentario: " . $comentario . " \n Este mensaje es autogenerado por Metis ";
                                crearIncidencia($rutaMail, $mails, $mensajeError, "Inicidencia grave");
                                $datos["correcto"] = true;
                                $datos["diferencia"] = null;
                            }
                        }
                        $respuesta ["datos"] = $datos;
                        $respuesta ["correcto"] = true;
                    } else {
                        $respuesta ["correcto"] = false;
                        $respuesta ["error"] = "La constante no Existe";
                    }
                } else {
                    $respuesta ["correcto"] = false;
                    $respuesta ["error"] = "El usuario no tiene jornada asignada";
                }
            } else {
                $respuesta ["correcto"] = false;
                $respuesta ["error"] = "No tiene permisos para registrarse";
            }
        } else { // No existe el usuario
            $respuesta ["correcto"] = false;
            $respuesta ["error"] = "Login Incorrecto";
        }
    } else {
        $respuesta ["correcto"] = false;
        $respuesta ["error"] = "Request no valido";
    }
} catch (PDOException $e) { //Error durante el codigo
    $respuesta ["correcto"] = false;
    $respuesta ["error"] = $e->getMessage();
}
$conn = null;
echo json_encode($respuesta);
