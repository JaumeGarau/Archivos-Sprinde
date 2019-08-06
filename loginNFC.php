<?php

$respuesta ["correcto"] = false;
$respuesta ["datos"] = null;
$respuesta ["error"] = "";

function insertRegistro($conn, $user, $ahora, $comentario, $ubicacion, $jornada, $tipo, $extra)
{
    $stm = $conn->prepare("INSERT INTO `Registro` (`usuario`, `fecha`, `comentario`, `ubicacion`, `jornada`, `tipo`, `extra`) VALUES (:user, :ahora, :comentario, :ubicacion, :jornada, :tipo, :extra);");
    $stm->bindValue(':user', !empty($user) ? $user : NULL, PDO::PARAM_STR);
    $stm->bindValue(':ahora', !empty($ahora) ? $ahora : NULL, PDO::PARAM_STR);
    $stm->bindValue(':comentario', !empty($comentario) ? $comentario : NULL, PDO::PARAM_STR);
    $stm->bindValue(':ubicacion', !empty($ubicacion) ? $ubicacion : NULL, PDO::PARAM_STR);
    $stm->bindValue(':jornada', !empty($jornada) ? $jornada : NULL, PDO::PARAM_STR);
    $stm->bindValue(':tipo', !empty($tipo) ? $tipo : NULL, PDO::PARAM_STR);
    $stm->bindValue(':extra', !empty($extra) ? $extra : 0, PDO::PARAM_INT);
    $stm->execute();
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        //variables
        date_default_timezone_set("Europe/Madrid");// "Europe/Madrid" "Etc/GMT+1"
        $datos ["correcto"] = null;
        $datos ["diferencia"] = 0;
        $HOY = getdate();
        $DiasSemana = ["D", "L", "M", "X", "J", "V", "S", "D"];
        $nfc = isset($_POST['NFC']) ? $_POST['NFC'] : "";
        $ubicacion = isset($_POST['ubicacion']) ? $_POST['ubicacion'] : "";
        $comentario = isset($_POST['comentario']) ? $_POST['comentario'] : "";
        $claveG = isset($_POST['clave']) ? $_POST['clave'] : "diferencia";

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

        $stringfecha = $fechaHoy . " 00:00:00.000"; //Ponemos 0 H para filtrar todo el dia
        $diaHoy = $HOY["wday"]; //date('w');
        $DiaSemana = $DiasSemana[$diaHoy]; //Así tendremos el dia de hoy

        $horaActual = $HOY["hours"] . ':' . $HOY["minutes"];
        $ahora = $fechaHoy . " " . $HOY["hours"] . ':' . $HOY["minutes"] . ':' . $HOY["seconds"]; //Pondremos el momento actual para nuestro registro


        // Conexion manual
        $conn = new PDO("mysql:host=localhost; dbname=metis", "root", "ea6uw1001",
            array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        // Conexion Manual

        if ($nfc != null && $nfc != "") {

            $stm = $conn->prepare("SELECT * FROM Usuarios WHERE NFC=:NFC;");
            $stm->bindValue(':NFC', !empty($nfc) ? $nfc : NULL, PDO::PARAM_STR);
            $stm->execute();

            $tuples = $stm->fetchAll();

            $user = $tuples[0]["codigo"]; //Cogemos su id
            $datos["usuario"] = $user;

            // Copia de registro
            if (array_key_exists(0, $tuples)) { // Existe el usuario

                if ($tuples[0]["permiso"] == 0) {
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

                    if (array_key_exists(0, $tuples3)) {

                        //Cogemos la constante de la diferencia, podria estar arriba
                        $stm = $conn->prepare("SELECT valor FROM Globales WHERE clave=:clave;");
                        $stm->bindValue(':clave', !empty($claveG) ? $claveG : NULL, PDO::PARAM_STR);
                        $stm->execute();
                        $tuples4 = $stm->fetchAll();

                        if (array_key_exists(0, $tuples4)) {

                            $diferecia = "";

                            if ($tuples4[0]["valor"] < 10) {
                                $diferecia .= "0";
                            }
                            $diferecia .= $tuples4[0]["valor"]; //diferencia de tu empressa aceptada

                            if (array_key_exists(0, $tuples2)) {

                                $ultimoDia = date($tuples2[0]["fecha"]);
                                $filtroDia = date('Y-m-d', strtotime($ultimoDia)) . " 00:00:00";

                                if ($ultimoDia < $stringfecha) {// Comprobamos si su jornada de hoy esta empezada
                                    // Buscaremos el dia anterior para comprobar la jornada
                                    if ($tuples2[0]["tipo"] != "Fin" && $tuples2[0]["tipo"] != "Salida") {
                                        $datos["correcto"] = false; //para poder justificarlo
                                        $respuesta ["correcto"] = true;
                                        $respuesta ["error"] = "La ultima entrada de ayer no se cerro correctamente";
                                    } /*else if ($tuples2[0]["tipo"] == "Salida") { // Aqui comprueba que el ultimo registro sea un Fin
                                    $datos["correcto"] = false; //para poder justificarlo
                                    $respuesta ["correcto"] = true;
                                    $respuesta ["error"] = "No se completaron correctamente las entradas";
                                }*/ else if ($tuples3[0][$DiaSemana] != null && strlen($tuples3[0][$DiaSemana]) > 4) {
                                        //No tiene registros de este dia, así que registraremos sin comprobar los demas registros, solo registrando a partir del horario
                                        $tipo = "Inicio";
                                        $comentario = "Inicio: " . $comentario;
                                        // separamos nuestra semana laboral en un array
                                        $jornadaHoy = $tuples3[0][$DiaSemana];
                                        $jornadaHoy = preg_split("[,]", $jornadaHoy, null);
                                        foreach ($jornadaHoy as $clave => $valor) {
                                            $jornadaHoy[$clave] = preg_split("[-]", $valor, null);
                                        }
                                        $valorJornada = preg_split("[:]", $jornadaHoy[0][0], null); // Cogemos el primer registro

                                        $curDif = $HOY["minutes"] + $HOY["hours"] * 60 - $valorJornada[1] - $valorJornada[0] * 60; //Calculamos la diferencia // !!!! Calcula mal los minutos de otro dia

                                        if ($curDif <= $diferecia && $curDif >= -$diferecia) {
                                            insertRegistro($conn, $user, $ahora, $comentario, $ubicacion, $tuples[0]["jornada"], $tipo, 0);
                                            $respuesta ["correcto"] = true;
                                            $datos["correcto"] = true;
                                            $datos["diferencia"] = $curDif;
                                        } else {
                                            $respuesta ["correcto"] = true;
                                            $datos["correcto"] = false;
                                            $datos["diferencia"] = $curDif;
                                            $difMensaje = abs($curDif);
                                            $minutosMensaje = $difMensaje % 60;
                                            $horasMensaje = ($difMensaje - $minutosMensaje) / 60;

                                            if ($curDif < 0) {
                                                $respuesta["error"] = "Has llegado " . $horasMensaje . " horas y " . $minutosMensaje . " minutos temprano";
                                            } else {
                                                $respuesta["error"] = "Has llegado " . $horasMensaje . " horas y " . $minutosMensaje . " minutos tarde";
                                            }
                                        }
                                    } else {
                                        $datos["correcto"] = false; //para poder justificarlo
                                        $respuesta ["correcto"] = true;
                                        $respuesta ["error"] = "Fuera del horario laboral";
                                    }

                                } else { // Seguiremos nuestra jornada
                                    $registroHoy = [];
                                    $jornadaHoy = [];

                                    if ($tuples3[0][$DiaSemana] != null && strlen($tuples3[0][$DiaSemana]) > 4) { // separamos nuestra semana laboral en un array
                                        $jornadaHoy = $tuples3[0][$DiaSemana]; // !!! Hay que comprovar que la jornada no es un string con un espacio
                                        $jornadaHoy = preg_split("[,]", $jornadaHoy, null);

                                        foreach ($jornadaHoy as $clave => $valor) {
                                            $jornadaHoy[$clave] = preg_split("[-]", $valor, null);
                                        }

                                        foreach ($tuples2 as $clave => $valor) {
                                            if ($valor["fecha"] >= $filtroDia && $valor["extra"] == 0) {
                                                array_push($registroHoy, $valor["fecha"]);
                                            }
                                        }
                                        $numJornadaHoy = count($registroHoy); //Obtenemos cuantos tienes +1

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
                                                    $comentario = "Inicio: " . $comentario;
                                                    $tipo = "Inicio";
                                                } else if ($tipoEntrada == 0) {
                                                    $comentario = "Entrada: " . $comentario;
                                                    $tipo = "Entrada";
                                                } else if (!array_key_exists($numEntrada + 1, $jornadaHoy)) {
                                                    $comentario = "Fin: " . $comentario;
                                                    $tipo = "Fin";
                                                } else {
                                                    $comentario = "Salida: " . $comentario;
                                                    $tipo = "Salida";
                                                }
                                                if ($curDif <= $diferecia && $curDif >= -$diferecia) {
                                                    insertRegistro($conn, $user, $ahora, $comentario, $ubicacion, $tuples[0]["jornada"], $tipo, 0);
                                                    $respuesta ["correcto"] = true;
                                                    $datos["correcto"] = true;
                                                    $datos["diferencia"] = $curDif;
                                                } else {
                                                    $respuesta ["correcto"] = true;
                                                    $datos["correcto"] = false;
                                                    $datos["diferencia"] = $curDif;
                                                    $difMensaje = abs($curDif);
                                                    $minutosMensaje = $difMensaje % 60;
                                                    $horasMensaje = ($difMensaje - $minutosMensaje) / 60;

                                                    if ($curDif < 0) {
                                                        $respuesta["error"] = "Has llegado " . $horasMensaje . " horas y " . $minutosMensaje . " minutos temprano";
                                                    } else {
                                                        $respuesta["error"] = "Has llegado " . $horasMensaje . " horas y " . $minutosMensaje . " minutos tarde";
                                                    }
                                                }
                                            } else {
                                                $datos["correcto"] = false;
                                                $respuesta ["correcto"] = true;
                                                $respuesta ["error"] = "Tu horario te permite cerrar mañana";
                                            }
                                        } else { // No es su horario de Trabajo
                                            $datos["correcto"] = false; //para poder justificarlo
                                            $respuesta ["correcto"] = true;
                                            $respuesta ["error"] = "Completaste tu jornada de hoy";
                                        }
                                    } else { // No es su horario de Trabajo
                                        $datos["correcto"] = false; //para poder justificarlo
                                        $respuesta ["correcto"] = true;
                                        $respuesta ["error"] = "Hoy no tienes jornada asignada";
                                    }

                                }

                            } else {
                                //No tiene registros anteriores así que registraremos sin comprobar los demas registros, solo registrando a partir del horario
                                $tipo = "Inicio";
                                $comentario = "Inicio: " . $comentario;
                                if ($tuples3[0][$DiaSemana] != null && strlen($tuples3[0][$DiaSemana]) > 4) { //Comprovamos que hoy nos toca trabajar
                                    // separamos nuestra semana laboral en un array
                                    $jornadaHoy = $tuples3[0][$DiaSemana];
                                    $jornadaHoy = preg_split("[,]", $jornadaHoy, null);
                                    foreach ($jornadaHoy as $clave => $valor) {
                                        $jornadaHoy[$clave] = preg_split("[-]", $valor, null);
                                    }
                                    $valorJornada = preg_split("[:]", $jornadaHoy[0][0], null); // Cogemos el primer registro

                                    $curDif = $HOY["minutes"] + $HOY["hours"] * 60 - $valorJornada[1] - $valorJornada[0] * 60; //Calculamos la diferencia

                                    if ($curDif <= $diferecia && $curDif >= -$diferecia) {
                                        insertRegistro($conn, $user, $ahora, $comentario, $ubicacion, $tuples[0]["jornada"], $tipo, 0);
                                        $respuesta ["correcto"] = true;
                                        $datos["correcto"] = true;
                                        $datos["diferencia"] = $curDif;
                                    } else {
                                        $respuesta ["correcto"] = true;
                                        $datos["correcto"] = false;
                                        $datos["diferencia"] = $curDif;
                                        $difMensaje = abs($curDif);
                                        $minutosMensaje = $difMensaje % 60;
                                        $horasMensaje = ($difMensaje - $minutosMensaje) / 60;

                                        $difMensaje = abs($curDif);
                                        $minutosMensaje = $difMensaje % 60;
                                        $horasMensaje = ($difMensaje - $minutosMensaje) / 60;

                                        if ($curDif < 0) {
                                            $respuesta["error"] = "Has llegado " . $horasMensaje . " horas y " . $minutosMensaje . " minutos temprano";
                                        } else {
                                            $respuesta["error"] = "Has llegado " . $horasMensaje . " horas y " . $minutosMensaje . " minutos tarde";
                                        }
                                    }
                                } else {
                                    $datos["correcto"] = false; //para poder justificarlo
                                    $respuesta ["correcto"] = true;
                                    $respuesta ["error"] = "Fuera del horario laboral";
                                }
                            }
                            $respuesta ["datos"] = $datos;
                            //$respuesta ["correcto"] = true;
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
                $respuesta ["error"] = " Login Incorrecto";
            }
            // Copia de registro
        } else {
            $respuesta ["correcto"] = false;
            $respuesta ["error"] = "Lectura incorrecta NFC";
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
