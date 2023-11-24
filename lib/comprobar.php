<?php

# comprobar
# Clase comprobar contiene todas las funciones necesarias para
# la comprobacion de campos en el servidor 
# Se incluira en las sesiones
# Diciembre 2005 Ruben Alutiz Duarte

class comprobar
{

    function __construct()
    {
    }

// FUNCION QUE COMPRUEBA QUE EL CONTENIDO DE LOS CAMPOS DE TEXTO SEA CORRECTO
    function ComprobarExacto($Array_Campos, $Tipo_Error)
    {
        // NO DEVUELVE NADA
        global $prov;
        global $Pagina_Error;
        global $admin;
        global $bd;

        if (isset($Pagina_Error)) $Pagina = "$Pagina_Error";
        else                      $Pagina = "error.php";

        $error      = "N";
        $CampoError = "";
        $i          = 0;
        while ($i < count( (array)$Array_Campos)):
            if (strlen( (string)$Array_Campos[$i]["valor"]) != $Array_Campos[$i]["caracteres"]):
                $error = "S";
                // TENGO AQUI CampoError PARA LA PAGINA DE ERROR
                $CampoError = $Array_Campos[$i]["err"];
                break;
            endif;
            $i++;
        endwhile;
        //MUESTRO EL ERROR AL USUARIO
        if ($error == "S"):
            $TipoError = $Tipo_Error;
            include $Pagina;
            exit;
        endif;
    } // FIN ComprobarTexto


// COMPRUEBA QUE EL CONTENIDO DE LOS CAMPOS DE TEXTO SEA CORRECTO, $devolverCadenaError para devolver el error en vez de incluir la pagina de error
    function ComprobarTexto($Array_Campos, $TipoError, $devolverCadenaError = "")
    {
        global $Pagina_Error;
        global $prov;

        if (!isset($Pagina_Error)) $Pagina_Error = "error.php";

        $error      = "N";
        $CampoError = "";
        $i          = 0;
        while ($i < count( (array)$Array_Campos)):
            if (trim( (string)$Array_Campos[$i]["valor"]) == ""):
                $error = "S";

                // TENGO AQUI CampoError PARA LA PAGINA DE ERROR
                $CampoError = $Array_Campos[$i]["err"];
                break;
            endif;
            $i++;
        endwhile;
        //MUESTRO EL ERROR AL USUARIO
        if ($error == "S"):
            if ($devolverCadenaError == "Si"):
                return $CampoError;
            else:
                include $Pagina_Error;
                exit;
            endif;
        endif;
    } // FIN ComprobarTexto

// FUNCION QUE VALIDA QUE LOS CAMPOS SEAN NUMERICOS, CON O SIN DECIMALES. $devolverCadenaError para devolver el error en vez de incluir la pagina de error
    function ComprobarDec($Array_Campos, $TipoError, $devolverCadenaError = "")
    {
        global $Pagina_Error;
        global $prov;

        if (!isset($Pagina_Error)) $Pagina_Error = "error.php";

        $error      = "N";
        $CampoError = "";
        $i          = 0;
        while ($i < count( (array)$Array_Campos)):
            if (!is_numeric(trim( (string)$Array_Campos[$i]["valor"]))):
                $error = "S";
                // TENGA AQUI CampoError PARA LA PAGINA DE ERROR
                $CampoError = $Array_Campos[$i]["err"];
                break;
            endif;
            $i++;
        endwhile;
        // MUESTRA EL ERROR AL USUARIO
        if ($error == "S"):
            if ($devolverCadenaError == "Si"):
                return $CampoError;
            else:
                include $Pagina_Error;
                exit;
            endif;
        endif;
    } // FIN ComprobarDec

    // FUNCIÓN QUE COMPRUEBA QUE EL NÚMERO DE DECIMALES NO EXCEDA UN VALOR PREDEFINIDO (USAR DESPUÉS DE ComprobarDec()) - Christian Téllez - 19/02/2019
    function ComprobarNumeroDecimales($Array_Campos, $TipoError, $devolverCadenaError = "")
    {
        global $Pagina_Error;

        if (!isset($Pagina_Error)) $Pagina_Error = "error.php";

        $error      = "N";
        $CampoError = "";
        $i          = 0;

        while ($i < count( (array)$Array_Campos)):
            if (strlen( (string)substr(strrchr((string)$Array_Campos[$i]["valor"], "."), 1)) > $Array_Campos[$i]["decimales"]):
                $error = "S";
                // TENGA AQUI CampoError PARA LA PAGINA DE ERROR
                $CampoError = $Array_Campos[$i]["err"];
                $valorError = $Array_Campos[$i]["decimales"];
                break;
            endif;
            $i++;
        endwhile;
        // MUESTRA EL ERROR AL USUARIO
        if ($error == "S"):
            if ($devolverCadenaError == "Si"):
                return $CampoError;
            else:
                include $Pagina_Error;
                exit;
            endif;
        endif;
    }

// FUNCION QUE VALIDA QUE LOS CAMPOS SEAN NUMERICOS ENTEROS SIN DECIMALES
    function ComprobarEnt($Array_Campos, $TipoError)
    {
        global $Pagina_Error;
        global $prov;
        if (!isset($Pagina_Error)) $Pagina_Error = "error.php";

        $error      = "N";
        $CampoError = "";
        $i          = 0;
        while ($i < count( (array)$Array_Campos)):
            $campo  = $Array_Campos[$i]["valor"];
            $result = is_numeric($campo) ? intval(0 + $campo) == $campo : false;
            if ($result == false):
                $error = "S";
                // TENGO AQUI CampoError PARA LA PAGINA DE ERROR
                $CampoError = $Array_Campos[$i]["err"];
                break;
            endif;
            $i++;
        endwhile;
        // MUESTRA EL ERROR AL USUARIO
        if ($error == "S"):
            include $Pagina_Error;
            exit;
        endif;
    } // FIN ComprobarEnt


// FUNCION QUE CONTROLA QUE UNA FECHA SEA VALIDA. SE USA PARA ELLO CHECKDATE. $devolverCadenaError para devolver el error en vez de incluir la pagina de error
    function ComprobarFecha($Array_Campos, $TipoError, $devolverCadenaError = "")
    {
        global $Pagina_Error;
        global $prov;
        global $administrador;
        if (!isset($Pagina_Error)) $Pagina_Error = "error.php";

        $error      = "N";
        $CampoError = "";
        for ($i = 0; $i < count( (array)$Array_Campos); $i++):
            if (strlen( (string)$Array_Campos[$i]["valor"]) != 10):
                // TENGO AQUI CampoError PARA LA PAGINA DE ERROR
                $CampoError = $Array_Campos[$i]["err"];
                $error      = "S";
                break;
            endif;

            //depende del formato de fecha del usuario
            switch ($administrador->FMTO_FECHA) {

                case 'yyyy-mm-dd':
                    $dia = substr( (string) $Array_Campos[$i]["valor"], -2);
                    $mes = substr( (string) $Array_Campos[$i]["valor"], 5, 2);
                    $ano = substr( (string) $Array_Campos[$i]["valor"], 0, 4);
                    break;

                case 'mm-dd-yyyy':
                    $dia = substr( (string) $Array_Campos[$i]["valor"], 3, 2);
                    $mes = substr( (string) $Array_Campos[$i]["valor"], 0, 2);
                    $ano = substr( (string) $Array_Campos[$i]["valor"], -4);
                    break;

                case 'dd-mm-yyyy':
                default:
                    $dia = substr( (string) $Array_Campos[$i]["valor"], 0, 2);
                    $mes = substr( (string) $Array_Campos[$i]["valor"], 3, 2);
                    $ano = substr( (string) $Array_Campos[$i]["valor"], -4);
                    break;
            }

            if (!is_numeric($dia) || !is_numeric($mes) || !is_numeric($ano)):
                $CampoError = $Array_Campos[$i]["err"];
                $error      = "S";
                break;
            endif;
            if (!checkdate((int)$mes, (int)$dia, (int)$ano) || $dia == "" || $mes == "" || $ano == ""):
                // TENGO AQUI CampoError PARA LA PAGINA DE ERROR
                $CampoError = $Array_Campos[$i]["err"];
                $error      = "S";
                break;
            endif;
        endfor;
        // MUESTRA EL ERROR AL USUSARIO
        if ($error == "S"):
            if ($devolverCadenaError == "Si"):
                return $CampoError;
            else:
                include $Pagina_Error;
                exit;
            endif;
        endif;
    } // FIN ComprobarFecha

// FUNCION QUE CONTROLA QUE 1 FECHA O UN RANGO DE FECHAS, DEVUELVE EL SQL PARA LA SELECT
    function ComprobarRangoFechas($txFecha, $campo_fecha)
    {
        global $Pagina_Error;
        global $auxiliar;
        if (!isset($Pagina_Error)) $Pagina_Error = "error.php";

        //SI HAY MAS DE UN GUION SALIMOS
        if (substr_count((string)$txFecha, "-") > 1):
            $TipoError = "FechaIncorrecta";
            include $Pagina_Error;
            exit;
        endif;

        $arr_fechas = explode("-", (string)$txFecha);
        $fecha1     = $arr_fechas[0];
        $fecha2     = $arr_fechas[1];
        if (strpos( (string)$txFecha, "-") === false): // BUSCAN SOLO UNA FECHA
            $arr_tx[0]["err"]   = "Fecha";
            $arr_tx[0]["valor"] = $fecha1;
            $this->ComprobarFecha($arr_tx, "FechaIncorrecta");
            $fechaSQL = $auxiliar->fechaFmtoSQL($fecha1);
            $sqlFecha = " AND $campo_fecha like '%$fechaSQL%' ";
        else:
            $arr_tx[0]["err"]   = "Fecha Inicial";
            $arr_tx[0]["valor"] = $fecha1;
            $arr_tx[1]["err"]   = "Fecha Final";
            $arr_tx[1]["valor"] = $fecha2;
            $this->ComprobarFecha($arr_tx, "FechaIncorrecta");
            $fechaSQL_ini = $auxiliar->fechaFmtoSQL($fecha1);
            $fechaSQL_fin = $auxiliar->fechaFmtoSQL($fecha2);
            $sqlFecha     = " AND $campo_fecha >= '$fechaSQL_ini 00:00:00' AND $campo_fecha <= '$fechaSQL_fin 23:59:59'";
        endif;

        return $sqlFecha;

    } // FIN ComprobarRangoFechas

// FUNCION QUE CONTROLA QUE 1 HORA O UN RANGO DE HORA, DEVUELVE EL SQL PARA LA SELECT
    function ComprobarRangoHoras($txHora, $campo_hora)
    {
        global $Pagina_Error;
        global $auxiliar;
        global $auxiliar;

        if (!isset($Pagina_Error)) $Pagina_Error = "error.php";

        //SI HAY MAS DE UN GUION SALIMOS
        if (substr_count((string)$txHora, "-") > 1):
            $TipoError = "HoraIncorrecta";
            include $Pagina_Error;
            exit;
        endif;

        $arr_horas = explode("-", (string)$txHora);
        $hora1     = $arr_horas[0];
        $hora2     = $arr_horas[1];
        if (strpos( (string)$txHora, "-") === false): // BUSCAN SOLO UNA HORA
            $arr_tx[0]["err"]   = $auxiliar->traduce("Hora", $administrador->ID_IDIOMA);
            $arr_tx[0]["valor"] = $hora1;
            $this->ComprobarHora($arr_tx, "HoraIncorrecta");
            if (strlen( (string)$hora1) == 5) $hora1 = $hora1 . ":00"; //Por si solo vienen horas y minutos
            $horaSQL = $hora1;//$auxiliar->fechaFmtoSQL($fecha1);
            $sqlHora = " AND $campo_hora like '%$horaSQL%' ";
        else:
            $arr_tx[0]["err"]   = $auxiliar->traduce("Hora Inicial", $administrador->ID_IDIOMA);
            $arr_tx[0]["valor"] = $hora1;
            $arr_tx[1]["err"]   = $auxiliar->traduce("Hora Final", $administrador->ID_IDIOMA);
            $arr_tx[1]["valor"] = $hora2;
            $this->ComprobarHora($arr_tx, "HoraIncorrecta");
            if (strlen( (string)$hora1) == 5) $hora1 = $hora1 . ":00"; //Por si solo vienen horas y minutos
            if (strlen( (string)$hora2) == 5) $hora2 = $hora2 . ":00"; //Por si solo vienen horas y minutos
            $horaSQL_ini = $hora1;//$auxiliar->fechaFmtoSQL($fecha1);
            $horaSQL_fin = $hora2;//$auxiliar->fechaFmtoSQL($fecha2);
            $sqlHora     = " AND $campo_hora >= '$horaSQL_ini' AND $campo_hora <= '$horaSQL_fin'";
        endif;

        return $sqlHora;

    } // FIN ComprobarRangoFechas

// FUNCION QUE COMPRUEBA QUE LOS VALORES DE HORA SEAN CORRECTOS
    function ComprobarHora($Array_Campos, $TipoError, $devolverCadenaError = "")
    {
        global $Pagina_Error;
        global $prov;
        if (!isset($Pagina_Error)) $Pagina_Error = "error.php";

        $error      = "N";
        $CampoError = "";
        for ($i = 0; $i < count( (array)$Array_Campos); $i++):
            if (strlen( (string)$Array_Campos[$i]["valor"]) != 5):
                // TENGO AQUI CampoError PARA LA PAGINA DE ERROR
                $CampoError = $Array_Campos[$i]["err"];
                $error      = "S";
                break;
            endif;
            // DIVIDE FECHA EN PARTES Y COMPRUEBA SI ESTAN EN EL RANGO PERMITIDO
            $partesHora = explode(":", (string)$Array_Campos[$i]["valor"]);
            if ($partesHora[0] < 0 || $partesHora[0] > 23 || $partesHora[1] < 0 || $partesHora[1] > 59):
                // TENGO AQUI CampoError PARA LA PAGINA DE ERROR
                $CampoError = $Array_Campos[$i]["err"];
                $error      = "S";
                break;
            endif;
        endfor;
        // MUESTRA EL ERROR AL USUARIO
        if ($error == "S"):
            if ($devolverCadenaError == "Si"):
                return $CampoError;
            else:
                include $Pagina_Error;
                exit;
            endif;
        endif;
    } // FIN ComprobarHora


    function comprobarEmail($Array_Campos, $TipoError)
    {
        global $Pagina_Error;

        if (!isset($Pagina_Error)) $Pagina_Error = "error.php";

        $error      = "N";
        $CampoError = "";
        for ($i = 0; $i < count((array) $Array_Campos); $i++):
            if (!preg_match("#^[_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-]+)*@+([_a-zA-Z0-9-]+\.)*[a-zA-Z0-9-]{2,200}\.[a-zA-Z]{2,6}#i", (string) $Array_Campos[$i]["valor"])):
                //if(!preg_match("#^[a-z0-9.]+@[a-z0-9.]+\.([a-z0-9]{2,})#i", $campo["valor"])):
                $CampoError = $Array_Campos[$i]["err"];
                $error      = "S";
                break;
            endif;
        endfor;
        if ($error == "S"):
            include $Pagina_Error;
            exit;
        endif;
    }


    function EsFechaCorrecta($fecha, $fmto = "Esp")
    {

        // COJO LOS DIA/MES/ANO SEGUN EL FMTO
        if ($fmto == "Esp"):
            $dia = substr( (string) $fecha, 0, 2);
            $mes = substr( (string) $fecha, 3, 2);
            $ano = substr( (string) $fecha, -4);
        else: // Sera SQL
            $dia = substr( (string) $fecha, -2);
            $mes = substr( (string) $fecha, 5, 2);
            $ano = substr( (string) $fecha, 0, 4);
        endif;

        // COMPROBACIONES
        if (strlen( (string)$fecha) != 10) return false;
        if (!is_numeric($dia) || !is_numeric($mes) || !is_numeric($ano)) return false;
        if (!checkdate((int)$mes, (int)$dia, (int)$ano) || $dia == "" || $mes == "" || $ano == "") return false;

        return true;

    } // FIN EsFechaCorrecta


    //COMPRUEBA SI $numero ES UN VALOR ENTERO. 2 DEVUELVE TRUE Y 2.000 DEVUELVE FALSE
    function EsEnteroCorrecto($numero)
    {

        $result = ctype_digit($numero);
        if ($result == false) return false;

        return true;

    } // FIN EsEnteroCorrecto


    //COMPRUEBA SI $numero PUEDE SER UN VALOR ENTERO. 2 DEVUELVE TRUE Y 2.000 DEVUELVE TRUE
    function EsEntero($numero)
    {
        $result = is_numeric($numero) ? intval(0 + $numero) == $numero : false;

        return $result;

    } // FIN EsEntero


    function ValidarEmail($email)
    {
        // Primero, checamos que solo haya un símbolo @, y que los largos sean correctos
        if (!preg_match("/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,})$/i", (string) $email)) {
            // correo inválido por número incorrecto de caracteres en una parte, o número incorrecto de símbolos @
            return false;
        }
        // se divide en partes para hacerlo más sencillo
       /* $email_array = explode("@", $email);
        $local_array = explode(".", $email_array[0]);
        for ($i = 0; $i < sizeof($local_array); $i++) {
            if (!ereg("^(([A-Za-z0-9!#$%&'*+/=?^_`{|}~-][A-Za-z0-9!#$%&'*+/=?^_`{|}~\.-]{0,63})|(\"[^(\\|\")]{0,62}\"))$", $local_array[$i])) {
                return false;
            }
        }
        // se revisa si el dominio es una IP. Si no, debe ser un nombre de dominio válido
        if (!ereg("^\[?[0-9\.]+\]?$", $email_array[1])) {
            $domain_array = explode(".", $email_array[1]);
            if (sizeof($domain_array) < 2) {
                return false; // No son suficientes partes o secciones para se un dominio
            }
            for ($i = 0; $i < sizeof($domain_array); $i++) {
                if (!ereg("^(([A-Za-z0-9][A-Za-z0-9-]{0,61}[A-Za-z0-9])|([A-Za-z0-9]+))$", $domain_array[$i])) {
                    return false;
                }
            }
        }*/

        return true;
    }

// COMPRUEBA QUE LA LONGITUD DE LOS CAMPOS DE TEXTO SEA LA CORRECTA
    function ComprobarLongitud($Array_Campos, $TipoError)
    {
        global $Pagina_Error;
        global $prov;

        if (!isset($Pagina_Error)) $Pagina_Error = "error.php";

        $error         = "N";
        $CampoError    = "";
        $LongitudCampo = "";
        $i             = 0;
        while ($i < count( (array)$Array_Campos)):
            if (strlen( (string)trim( (string)$Array_Campos[$i]["valor"])) > trim( (string)$Array_Campos[$i]["longitud"])):
                $error = "S";

                // TENGO AQUI CampoError PARA LA PAGINA DE ERROR
                $CampoError = $Array_Campos[$i]["err"];
                // TENGO AQUI LongitudCampo PARA LA PAGINA DE ERROR
                $LongitudCampo = $Array_Campos[$i]["longitud"];
                break;
            endif;
            $i++;
        endwhile;
        //MUESTRO EL ERROR AL USUARIO
        if ($error == "S"):
            include $Pagina_Error;
            exit;
        endif;
    } // FIN ComprobarTexto


    function ComprobarAlgunFiltroRellenado($Array_Campos, $Array_Nombres)
    {
        global $Pagina_Error;
        global $auxiliar;
        global $administrador;

        if (!isset($Pagina_Error)) $Pagina_Error = "error.php";

        $algunFiltroRellenado = false;
        $array_nombres        = array();

        foreach ($Array_Campos as $filtro) {
            if ((trim( (string)$filtro) != "")) {
                $algunFiltroRellenado = true;
                break;
            }
        }

        //MUESTRO EL ERROR AL USUARIO
        if (!$algunFiltroRellenado):

            $coma                = '';
            $ListadoNombresError = "";
            foreach ($Array_Nombres as $nombre) {
                $ListadoNombresError .= $coma . ' ' . $auxiliar->traduce($nombre, $administrador->ID_IDIOMA);;
                $coma = ',';
            }
            $ListadoNombresError .= '.';

            $TipoError = "FiltrosSinRellenarNombres";
            if (count( (array)$Array_Campos) > 5) {
                $TipoError = "FiltrosSinRellenar";
            }

            include $Pagina_Error;
            exit;
        endif;
    }

    function comprobarCentro($txCentro, $idCentro)
    {
        global $html, $bd;
        //PROVEEDOR
        //COMPRUEBO SI EXISTE EL CENTRO EN CASO CONTRARIO SACO UN ERROR
        if ($idCentro != ""): //VIENE DEL DESPLEGABLE O DEL AJAX
            $NotificaErrorPorEmail = "No";

            $GLOBALS["NotificaErrorPorEmail"] = "No";
            $rowCentro                        = $bd->VerReg("CENTRO", "ID_CENTRO", trim( (string)$bd->escapeCondicional($idCentro)), "No");
            $html->PagErrorCondicionado($rowCentro->REFERENCIA . " - " . $rowCentro->CENTRO, "!=", trim( (string)$bd->escapeCondicional($txCentro)), "ErrorDatosCentro");
        elseif ($idCentro == ""): //NOMBRE INTRODUICIDO MANUALMENTE
            $sqlCentro    = "SELECT * FROM CENTRO WHERE TRIM(REFERENCIA) = TRIM('" . $bd->escapeCondicional($txCentro) . "')";
            $resultCentro = $bd->ExecSQL($sqlCentro);
            if ($rowCentro = $bd->SigReg($resultCentro)):
                $html->PagErrorCondicionado($rowCentro->BAJA, "==", 1, "CentroBaja");
            else: //NO EXISTE. SACO UN ERROR
                $html->PagError("CentroNoEncontrado");
            endif;
        endif;

        return $rowCentro;
    }

} // FIN DE LA CLASE comprobar
?>
