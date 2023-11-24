<?php

# auxiliar
# Clase auxiliar contiene todas las funciones necesarias para
# contener todo tipo de funciones sin clasificar 
# Se incluira en las sesiones
# Diciembre 2005 Ruben Alutiz Duarte

class auxiliar
{

    function __construct()
    {
    }

    /**
     * Devuelve la fecha en el formato de fecha del servidor y en el huso horario del servidor
     * @param $fecha
     * @return string
     */
    function fechaFmtoSQL($fecha, $convertir_desde_huso_de_usuario = false)
    {
// SE LE PASA UNA FECHA DEL TIPO ESPAÑOL  17-11-2001 (o formato de usuario)
// DEVUELVE OTRA DEL TIPO SQL 2001-11-17

        global $administrador;

        //pasamos la fecha a formato sql
        //depende del formato de fecha del usuario
        switch ($administrador->FMTO_FECHA) {
            case 'yyyy-mm-dd':
                $dia = substr( (string) $fecha, 8, 2);
                $mes = substr( (string) $fecha, 5, 2);
                $ano = substr( (string) $fecha, 0, 4);
                break;
            case 'mm-dd-yyyy':
                $dia = substr( (string) $fecha, 3, 2);
                $mes = substr( (string) $fecha, 0, 2);
                $ano = substr( (string) $fecha, 6, 4);
                break;
            case 'dd-mm-yyyy':
            default:
                $dia = substr( (string) $fecha, 0, 2);
                $mes = substr( (string) $fecha, 3, 2);
                $ano = substr( (string) $fecha, 6, 4);
                break;
        }
        $fechabien = $ano . "-" . $mes . "-" . $dia;

        //paso la fecha a la timezone del servidor si tiene hora y esta no es '00:00:00'
        if (strlen( (string)$fecha) > 10 && strpos( (string)$fecha, '00:00:00') === FALSE) {

            $hora     = substr( (string) $fecha, 11, 2);
            $minutos  = substr( (string) $fecha, 14, 2);
            $segundos = substr( (string) $fecha, 17, 4);
            if (!$segundos) {
                $segundos = '00';
            }

            $fechabien_tz = $this->fechaToTimezoneServer($fechabien . ' ' . $hora . ':' . $minutos . ':' . $segundos, $convertir_desde_huso_de_usuario);

            $dia_tz    = substr( (string) $fechabien_tz, 8, 2);
            $mes_tz    = substr( (string) $fechabien_tz, 5, 2);
            $ano_tz    = substr( (string) $fechabien_tz, 0, 4);
            $fechabien = $ano_tz . "-" . $mes_tz . "-" . $dia_tz;
        }

        return ($fechabien);
    }

    /**
     * Devuelve la fecha con hora en el formato de fecha del servidor y en el huso horario del servidor
     * @param $fecha
     * @return string
     */
    function fechaFmtoSQLHora($fecha, $return_solo_hora = false, $convertir_desde_huso_de_usuario = false)
    {
        /*ereg( "([0-9]{1,2})-([0-9]{1,2})-([0-9]{2,4}) ([0-9]{1,2}):([0-9]{1,2}):([0-9]{1,2})", $fecha, $mifecha);
        $lafecha=$mifecha[3]."-".$mifecha[2]."-".$mifecha[1]." ".$mifecha[4].":".$mifecha[5].":".$mifecha[6] ;
        return $lafecha;*/

        global $administrador;

        //pasamos la fecha a formato sql
        //depende del formato de fecha del usuario
        switch ($administrador->FMTO_FECHA) {
            case 'yyyy-mm-dd':
                $dia      = substr( (string) $fecha, 8, 2);
                $mes      = substr( (string) $fecha, 5, 2);
                $ano      = substr( (string) $fecha, 0, 4);
                $hora     = substr( (string) $fecha, 11, 2);
                $minutos  = substr( (string) $fecha, 14, 2);
                $segundos = substr( (string) $fecha, 17, 4);
                break;
            case 'mm-dd-yyyy':
                $dia      = substr( (string) $fecha, 3, 2);
                $mes      = substr( (string) $fecha, 0, 2);
                $ano      = substr( (string) $fecha, 6, 4);
                $hora     = substr( (string) $fecha, 11, 2);
                $minutos  = substr( (string) $fecha, 14, 2);
                $segundos = substr( (string) $fecha, 17, 4);
                break;
            case 'dd-mm-yyyy':
            default:
                $dia      = substr( (string) $fecha, 0, 2);
                $mes      = substr( (string) $fecha, 3, 2);
                $ano      = substr( (string) $fecha, 6, 4);
                $hora     = substr( (string) $fecha, 11, 2);
                $minutos  = substr( (string) $fecha, 14, 2);
                $segundos = substr( (string) $fecha, 17, 4);
                break;
        }
        if (!$segundos) {
            $segundos = '00';
        }
        $fechabien = $ano . "-" . $mes . "-" . $dia . " " . $hora . ":" . $minutos . ":" . $segundos;

        //paso la fecha a la timezone del servidor
        $fechabien_tz = $this->fechaToTimezoneServer($fechabien, $convertir_desde_huso_de_usuario);
        $dia_tz       = substr( (string) $fechabien_tz, 8, 2);
        $mes_tz       = substr( (string) $fechabien_tz, 5, 2);
        $ano_tz       = substr( (string) $fechabien_tz, 0, 4);
        $hora_tz      = substr( (string) $fechabien_tz, 11, 2);
        $minutos_tz   = substr( (string) $fechabien_tz, 14, 2);
        $segundos_tz  = substr( (string) $fechabien_tz, 17, 2);
        $fechabien    = $ano_tz . "-" . $mes_tz . "-" . $dia_tz . " " . $hora_tz . ":" . $minutos_tz . ":" . $segundos_tz;

        if ($return_solo_hora) {
            $fechabien = $hora_tz . ":" . $minutos_tz;
        }

        return ($fechabien);
    }

    /**
     * Devuelve la fecha en el formato de fecha del usuario y (si se desea) en el huso horario del usuario
     * @param $fecha
     * @param string $caracter
     * @param boolean $convertir_a_huso_de_usuario
     * @param boolean $convertir_a_formato_usuario
     * @return string
     */
    function fechaFmtoEsp($fecha, $caracter = "-", $convertir_a_huso_de_usuario = true, $convertir_a_formato_usuario = true)
    {

        global $administrador;

        //paso la fecha a la timezone del usuario si tiene hora y esta no es '00:00:00'
        if (strlen( (string)$fecha) > 10 && strpos( (string)$fecha, '00:00:00') === FALSE && $convertir_a_huso_de_usuario) {
            $fecha = $this->fechaToTimezoneUser($fecha);
        }

        preg_match("/([0-9]{2,4})-([0-9]{1,2})-([0-9]{1,2})/", (string) $fecha, $mifecha);
        $ano = $mifecha[1];
        $mes = $mifecha[2];
        $dia = $mifecha[3];

        //depende del formato de fecha del usuario
        if ($convertir_a_formato_usuario) {
            switch ($administrador->FMTO_FECHA) {

                case 'yyyy-mm-dd':
                    $lafecha = $ano . $caracter . $mes . $caracter . $dia;
                    break;

                case 'mm-dd-yyyy':
                    $lafecha = $mes . $caracter . $dia . $caracter . $ano;
                    break;

                case 'dd-mm-yyyy':
                default:
                    $lafecha = $dia . $caracter . $mes . $caracter . $ano;
                    break;
            }
        } else {
            $lafecha = $dia . $caracter . $mes . $caracter . $ano;
        }

        if ($lafecha == '00-00-0000') {
            $lafecha = '-';
        }

        return $lafecha;
    }

    /**
     * Devuelve la fecha en el formato INDICADO
     * @param $fecha
     * @param string $caracter
     * @param boolean $convertir_a_huso_de_usuario
     * @return string
     */
    function fechaFmto($fecha, $fmto, $caracter = "-", $convertir_a_huso_de_usuario = true)
    {
        global $administrador;

        //paso la fecha a la timezone del usuario si tiene hora y esta no es '00:00:00'
        if (strlen( (string)$fecha) > 10 && strpos( (string)$fecha, '00:00:00') === FALSE && $convertir_a_huso_de_usuario) {
            $fecha = $this->fechaToTimezoneUser($fecha);
        }

        preg_match("/([0-9]{2,4})-([0-9]{1,2})-([0-9]{1,2})/", (string) $fecha, $mifecha);
        $ano = $mifecha[1];
        $mes = $mifecha[2];
        $dia = $mifecha[3];

        //depende del formato de fecha del usuario
        switch ($fmto) {

            case 'yyyy-mm-dd':
                $lafecha = $ano . $caracter . $mes . $caracter . $dia;
                break;

            case 'mm-dd-yyyy':
                $lafecha = $mes . $caracter . $dia . $caracter . $ano;
                break;

            case 'dd-mm-yyyy':
            default:
                $lafecha = $dia . $caracter . $mes . $caracter . $ano;
                break;
        }

        if ($lafecha == '00-00-0000') {
            $lafecha = '-';
        }

        return $lafecha;
    }

    /**
     * Devuelve la fecha en el formato de fecha del usuario y (si se desea) en el huso horario del usuario
     * @param $fecha
     * @param boolean $convertir_a_huso_de
     * @return string
     */
    function fechaFmtoEspHora($fecha, $convertir_a_huso_de_usuario = true, $return_solo_hora = false, $incluir_segundos = true, $convertir_a_formato_usuario = true)
    {

        global $administrador;

        //paso la fecha a la timezone del usuario
        if ($convertir_a_huso_de_usuario) {
            $fecha = $this->fechaToTimezoneUser($fecha);
        }

        preg_match("/([0-9]{2,4})-([0-9]{1,2})-([0-9]{1,2}) ([0-9]{1,2}):([0-9]{1,2}):([0-9]{1,2})/", (string) $fecha, $mifecha);
        $ano      = $mifecha[1];
        $mes      = $mifecha[2];
        $dia      = $mifecha[3];
        $hora     = $mifecha[4];
        $minutos  = $mifecha[5];
        $segundos = $mifecha[6];

        if ($incluir_segundos) {
            $segundos = ':' . $segundos;
        } else {
            $segundos = '';
        }

        //depende del formato de fecha del usuario
        if ($convertir_a_formato_usuario) {
            switch ($administrador->FMTO_FECHA) {

                case 'yyyy-mm-dd':
                    $lafecha = $ano . "-" . $mes . "-" . $dia . " " . $hora . ":" . $minutos . $segundos;
                    break;

                case 'mm-dd-yyyy':
                    $lafecha = $mes . "-" . $dia . "-" . $ano . " " . $hora . ":" . $minutos . $segundos;
                    break;

                case 'dd-mm-yyyy':
                default:
                    $lafecha = $dia . "-" . $mes . "-" . $ano . " " . $hora . ":" . $minutos . $segundos;
                    break;
            }
        } else {
            $lafecha = $dia . "-" . $mes . "-" . $ano . " " . $hora . ":" . $minutos . $segundos;
        }

        if ($return_solo_hora) {
            $lafecha = $hora . ":" . $minutos;
        }

        return $lafecha;
    }

    /**
     * @param $fecha
     * DEVUELVE LA FECHA Y HORA, SIEMPRE QUE HAYAN INTRODUCIDO HORAS. UTILIZADO PARA FECHAS EN LAS QUE LAS HORAS SON OPCIONALES
     */
    function fechaFmtoEspHoraOpcional($fecha)
    {

        //OBTENEMOS LA FECHA
        $campo_fecha = (($fecha != NULL && $fecha != "") ? $this->fechaFmtoEsp($fecha) : "");
        //CONVERTIR, RETORNAR SOLO HORA Y SIN SEGUNDOS, SI HAY GUARDADO 00:00:00 es que no introdujeron hora
        $campo_hora = (($fecha != NULL && strpos( (string)$fecha, '00:00:00') === FALSE) ? $this->fechaFmtoEspHora($fecha, true, true, false) : "");

        return $campo_fecha . ($campo_hora != "" ? " " . $campo_hora : "");
    }

    /**
     * Pasa una fecha a la timezone del servidor
     * @param $timestamp
     * @return string
     */
    function fechaToTimezoneServer($timestamp, $convertir_desde_huso_de_usuario = false)
    {
        global $administrador;

        $from_tz = '';
        if ($convertir_desde_huso_de_usuario) {
            $from_tz = $administrador->ID_HUSO_HORARIO_PHP;
        }

        return $this->fecha_tz_to_tz($timestamp, $from_tz, ini_get('date.timezone'));
    }

    /**
     * Pasa una fecha a la timezone del usuario
     * @param $timestamp
     * @return string
     */
    function fechaToTimezoneUser($timestamp)
    {
        global $administrador;

        return $this->fecha_tz_to_tz($timestamp, '', $administrador->ID_HUSO_HORARIO_PHP);
    }

    /**Pasa fecha a la timezone pasada por parametro
     * @param $husoHorario
     * @param $timestamp
     * @return string
     */
    function fechaToTimezoneUserParam($husoHorario, $timestamp)
    {
        global $administrador;

        return $this->fecha_tz_to_tz($timestamp, '', $husoHorario);
    }

    /**Pasa fecha a la timezone pasada por parametro
     * @param $husoHorario
     * @param $timestamp
     * @return string
     */
    function fechaToTimezoneJWParam($husoHorario, $timestamp)
    {
        global $administrador;

        return $this->fecha_tz_to_tz($timestamp, $husoHorario, ini_get('date.timezone'));
    }

    /**
     * Converts a timestamp between arbitrary timezones.
     * @param $timestamp
     * @param $from_tz
     * @param $to_tz
     * @return string
     */
    function fecha_tz_to_tz($timestamp, $from_tz, $to_tz)
    {
        $old_tz = date_default_timezone_get();
        // Parse $timestamp and extract the Unix time.
        if (!empty($from_tz)) {
            date_default_timezone_set(trim( (string)$from_tz, "'"));
        } else {
            $from_tz = $old_tz;
        }

        $unix_time = strtotime( (string)$timestamp); // Unix time is seconds since the epoch (in UTC).

        // Express the Unix time as a string for timezone $tz.
        date_default_timezone_set(trim( (string)$to_tz, "'"));

        $result = date("Y-m-d H:i:s", $unix_time);
        date_default_timezone_set(trim( (string)$old_tz, "'"));

        return $result;
    }

// COMPARA 2 FECHAS
// DEVUELVE <0, 0, >0 SI; FECHA A < FECHA B, FECHA A == FECHA B, FECHA A > FECHA B
    function CompararFecha($I_SFirstDate, $I_SSecondDate)
    {

        // DIVIDE LAS FECHAS EN PARTES PARA PODER COMPARARLAS POSTERIORMENTE
        $arrFirstDate  = explode("/", (string)$I_SFirstDate);
        $arrSecondDate = explode("/", (string)$I_SSecondDate);

        $intFirstDay   = $arrFirstDate[0];
        $intFirstMonth = $arrFirstDate[1];
        $intFirstYear  = $arrFirstDate[2];

        $intSecondDay   = $arrSecondDate[0];
        $intSecondMonth = $arrSecondDate[1];
        $intSecondYear  = $arrSecondDate[2];

        // CALCULA LA DIFERENCIA ENTRE DOS FECHAS Y DEVUELVE EL NUMERO DE DIAS.
        $intDate1Jul = gregoriantojd($intFirstMonth, $intFirstDay, $intFirstYear);
        $intDate2Jul = gregoriantojd($intSecondMonth, $intSecondDay, $intSecondYear);

        return $intDate1Jul - $intDate2Jul;
    } // FIN CompararFecha


    //RESTA DOS FECHAS(datetime) Y DEVUELVE LA DIFERENCIA EN HH:MM:SS 
    function RestarFechas($fecha1, $fecha2)
    {
        //DIVIDIMOS LAS FECHAS EN DATE Y TIME
        $arr1 = explode(" ", (string)$fecha1);
        $arr2 = explode(" ", (string)$fecha2);

        $arrFirstDate  = explode("-", (string)$arr1[0]);
        $arrSecondDate = explode("-", (string)$arr2[0]);

        // CALCULA LA DIFERENCIA DE DIAS.
        $intDate1Jul = gregoriantojd($arrFirstDate[1], $arrFirstDate[2], $arrFirstDate[0]);
        $intDate2Jul = gregoriantojd($arrSecondDate[1], $arrSecondDate[2], $arrSecondDate[0]);

        //CONVERTIR A HORAS
        $horas = ($intDate2Jul - $intDate1Jul) * 24;


        //RESTAMOS LOS FIN DE SEMANA, SI SE HA CREADO EN SABADO ESE SABADO NO LO RESTAMOS
        $start = strtotime( (string)$arr1[0]);
        $end   = strtotime( (string)$arr2[0]);

        if (date('N', $start) == 6):
            $count = -1;
        else:
            $count = 0;
        endif;

        while (date('Y-m-d', $start) < date('Y-m-d', $end)):
            $count += date('N', $start) < 6 ? 0 : 1;
            $start = strtotime( (string)"+1 day", $start);
        endwhile;
        $horas = $horas - 24 * $count;


        //CALCULAMOS LA DIFERENCIA EN HORAS Y MINUTOS
        $arrFirstTime  = explode(":", (string)$arr1[1]);
        $arrSecondTime = explode(":", (string)$arr2[1]);

        $horas    = $horas + ($arrSecondTime[0] - $arrFirstTime[0]);
        $minutos  = $arrSecondTime[1] - $arrFirstTime[1];
        $segundos = $arrSecondTime[2] - $arrFirstTime[2];

        //SI SON NEGATIVOS, ME LLEVO UNA
        if ($minutos < 0):
            $minutos = 60 + $minutos;
            $horas--;
        endif;
        if ($segundos < 0):
            $segundos = 60 + $segundos;
            $minutos--;
        endif;

        return $horas . ":" . ($minutos < 10 ? '0' : '') . $minutos . ":" . ($segundos < 10 ? '0' : '') . $segundos;
    }

    //RESTA DOS FECHAS(datetime) Y DEVUELVE LA DIFERENCIA EN HH:MM:SS SIN TENER EN CUENTA LOS FESTIVOS
    function RestarFechasSinFestivos($fecha1, $fecha2)
    {

        if ($fecha1 < $fecha2):
            $fechaInicio = strtotime( (string)$fecha1);
            $fechaFin    = strtotime( (string)$fecha2);

            // CALCULAMOS LA DIFERENCIA EN SEGUNDOS
            $diferencia         = $fechaFin - $fechaInicio;
            $numDiasFinDeSemana = 0;

            //CALCULAMOS SIN EN EL INTERVALO DE FECHAS CAE EN FIN DE SEMANA
            while (date('Y-m-d', $fechaInicio) < date('Y-m-d', $fechaFin)):
                $numDiasFinDeSemana += date('N', $fechaInicio) < 6 ? 0 : 1;
                $fechaInicio        = strtotime( (string)"+1 day", $fechaInicio);
            endwhile;
            $horas = $diferencia / 3600; // 3600 SEGUNDOS POR HORA
            $horas = $horas - ($numDiasFinDeSemana * 24); // RESTAMOS LOS DIAS DE FIN DE SEMANA
            if($horas < EPSILON_SISTEMA): //SI LAS HORAS SON NEGATIVAS, SE DEVUELVE 0
                return "00:00:00";
            else:
                $minutos       = ($horas - floor((float)$horas)) * 60;   //60 MINUTOS POR HORA
                $segundos      = ($minutos - floor((float)$minutos)) * 60; // 60 SEGUNDOS POR MINUTO
                $horasFinal    = intval($horas);   // HORAS SIN REDONDEAR, EL RESTO SON LOS MINUTOS
                $minutosFinal  = intval($minutos);   // MINUTOS SIN REDONDEAR
                $segundosFinal = round( (float)$segundos);  // SEGNDOS

                return $horasFinal . ":" . ($minutosFinal < 10 ? '0' : '') . $minutosFinal . ":" . ($segundosFinal < 10 ? '0' : '') . $segundosFinal;
            endif;

        else:
            return "00:00:00";
        endif;
    }


// FUNCION QUE DEVUELVE UN VALOR TIMESTAMP A PARTIR DE LOS ARGUMENTOS DADOS.
// $TimeTamp : CONTIENE UN VALOR DE FECHA
// $Seconds : CONTIENE UN VALOR PARA LOS SEGUNDOS DE LA FECHA
// $Minutes : CONTIENE UN VALOR PARA LOS MINUTOS DE LA FECHA
// $Hours : CONTIENE UN VALOR PARA LA HORA DE LA FECHA
// $Days : CONTIENE UN VALOR PARA LOS DIAS DE LA FECHA
// $Months : CONTIENE UN VALOR PARA LOS MESES DE LA FECHA
// $Years : CONTIENE UN VALOR PARA LOS AÑOS DE LA FECHA
    function SumarFecha($TimesTamp, $Seconds, $Minutes, $Hours, $Days, $Months, $Years)
    {
        $timePieces = getdate($TimesTamp);

        return mktime($timePieces["Hours"] + $Hours,
            $timePieces["Minutes"] + $Minutes,
            $timePieces["Seconds"] + $Seconds,
            $timePieces["Mon"] + $Months,
            $timePieces["Mday"] + $Days,
            $timePieces["Year"] + $Years);
    } // FIN SumarFecha

    /**
     * FUNCION QUE CONVIERTE LOS SEGUNDOS PASADOS POR PARAMETRO EN FORMATO TIME
     * @param $total_seconds
     * @return string
     */
    function makeTimeFromSeconds($total_seconds)
    {
        $horas   = floor((float)$total_seconds / 3600);
        $minutes = ($total_seconds / 60) % 60;
        $seconds = $total_seconds % 60;

        $time['horas']   = str_pad( (string)$horas, 2, "0", STR_PAD_LEFT);
        $time['minutes'] = str_pad( (string)$minutes, 2, "0", STR_PAD_LEFT);
        $time['seconds'] = str_pad( (string)$seconds, 2, "0", STR_PAD_LEFT);

        $time = implode(':', (array) $time);

        return $time;
    }

    /**
     * FUNCION PARA COMPRAR CANTIDADES
     * Si tomoamos como cantidad1 es 0.9 y cantidad2 es 0.900, esta funcion devulve true, si se comparan con '!=' devuelve false
     */
    function sonCantidadesIguales($cantidad1, $cantidad2)
    {
        if (abs( (float)$cantidad1 - $cantidad2) < EPSILON_SISTEMA):
            return true;
        else:
            return false;
        endif;
    }

    // FUNCIONES RELACIONADAS CON LA CONVERSION DE UNIDADES (PESO, LONGITUD, VOLUMEN, ETC.) DEL OBJETO CORRESPONDIENTE

    //OBTIENE EL VALOR EN LAS DISTINTAS UNIDADES A LAS QUE SE PUEDE CONVERTIR
    function convertirUnidades($valor, $idUnidad)
    {
        //DECLARO LA VARIABLE GLOBAL
        global $bd;

        //ARRAY A DEVOLVER
        $arrDevolver = array();

        //SI ESTA RELLENA LA UNIDAD DE ENTRADA CONTINUO
        if (($idUnidad != "") && ($idUnidad != NULL)):

            //SI VALOR NO TRAE DATO, SE INTERPRETARA COMO CERO
            if (($valor == "") || ($valor == NULL)):
                $valor = 0;
            endif;

            //LO INICIALIZO CON LOS DATOS ENVIADOS
            $arrDevolver[$idUnidad] = $valor;

            //BUSCO TODOS LOS TIPOS DE CONVERSION DE ESTA UNIDAD COMO ORIGEN
            $sqlConversionOrigen    = "SELECT *
                                        FROM CONVERSION_UNIDADES CU
                                        WHERE CU.ID_UNIDAD_ORIGEN = $idUnidad";
            $resultConsersionOrigen = $bd->ExecSQL($sqlConversionOrigen);
            while ($rowConversionOrigen = $bd->SigReg($resultConsersionOrigen)):
                $valorNuevaUnidad                                     = $valor * ($rowConversionOrigen->NUMERADOR / $rowConversionOrigen->DENOMINADOR);
                $arrDevolver[$rowConversionOrigen->ID_UNIDAD_DESTINO] = $valorNuevaUnidad;
            endwhile;

            //BUSCO TODOS LOS TIPOS DE CONVERSION DE ESTA UNIDAD COMO DESTINO
            $sqlConversionDestino    = "SELECT *
                                          FROM CONVERSION_UNIDADES CU
                                          WHERE CU.ID_UNIDAD_DESTINO = $idUnidad";
            $resultConsersionDestino = $bd->ExecSQL($sqlConversionDestino);
            while ($rowConversionDestino = $bd->SigReg($resultConsersionDestino)):
                $valorNuevaUnidad                                     = $valor * ($rowConversionDestino->DENOMINADOR / $rowConversionDestino->NUMERADOR);
                $arrDevolver[$rowConversionDestino->ID_UNIDAD_ORIGEN] = $valorNuevaUnidad;
            endwhile;

        endif;
        //FIN SI ESTAN RELLENOS LOS DATOS DE ENTRADA CONTINUO

        //DEVULVO EL ARRAY CON LAS CONVERSIONES
        return $arrDevolver;
    }

    //OBTIENE EL FEACTOR DE CONVERSION ENTRE 2 UNIDADES
    function getFactorConversion($idUnidadPrimaria, $idUnidadSecundaria)
    {
        //DECLARO LA VARIABLE GLOBAL
        global $bd;

        //VALOR A DEVOLVER
        $valor = 1;

        //SI ESTAN RELLENOS LOS DATOS DE ENTRADA CONTINUO
        if (($idUnidadPrimaria != NULL) && ($idUnidadSecundaria != NULL) && ($idUnidadPrimaria != $idUnidadSecundaria)):
            //BUSCO TODOS LOS TIPOS DE CONVERSION DE UNIDAD PRIMARIA COMO ORIGEN Y LA SECUNDARIA COMO DESTINO
            $sqlFactorConversion    = "SELECT *
                                    FROM CONVERSION_UNIDADES CU
                                    WHERE CU.ID_UNIDAD_ORIGEN = $idUnidadPrimaria AND CU.ID_UNIDAD_DESTINO = $idUnidadSecundaria";
            $resultFactorConversion = $bd->ExecSQL($sqlFactorConversion);
            if (($resultFactorConversion != false) && ($bd->NumRegs($resultFactorConversion) > 0)):
                $rowFactorConversion = $bd->SigReg($resultFactorConversion);
                $valor               = $rowFactorConversion->NUMERADOR / $rowFactorConversion->DENOMINADOR;
            endif;

            //BUSCO TODOS LOS TIPOS DE CONVERSION DE UNIDAD PRIMARIA COMO DESTINO Y LA SECUNDARIA COMO ORIGEN
            $sqlFactorConversion    = "SELECT *
                                    FROM CONVERSION_UNIDADES CU
                                    WHERE CU.ID_UNIDAD_DESTINO = $idUnidadPrimaria AND CU.ID_UNIDAD_ORIGEN = $idUnidadSecundaria";
            $resultFactorConversion = $bd->ExecSQL($sqlFactorConversion);
            if (($resultFactorConversion != false) && ($bd->NumRegs($resultFactorConversion) > 0)):
                $rowFactorConversion = $bd->SigReg($resultFactorConversion);
                $valor               = $rowFactorConversion->DENOMINADOR / $rowFactorConversion->NUMERADOR;
            endif;

        endif;
        //FIN SI ESTAN RELLENOS LOS DATOS DE ENTRADA CONTINUO

        //DEVUELVO EL FACTOR DE CONVERSION
        return $valor;
    }

    // FIN FUNCIONES RELACIONADAS CON LA CONVERSION DE UNIDADES (PESO, LONGITUD, VOLUMEN, ETC.) DEL OBJETO CORRESPONDIENTE

// DEVUELVE $frase AÑADIENDO LOS TAGS DELANTE Y DETRAS DE LAS PALABRAS DEL ARRAY
    function ColorearFrase($frase, $arrPalabras)
    {
        // TAGS PARA COLOREAR LAS PALABRAS
        $delante = "<font color=#FCAD56>";
        $detras  = "</font>";

        for ($i = 0; $i < count( (array)$arrPalabras); $i++):

            $CadBuscar     = $arrPalabras[$i];
            $LongCadBuscar = strlen( (string)$CadBuscar);
            if (trim( (string)$CadBuscar) == "") continue;

            $CadNueva   = "";
            $PosPalabra = strpos(strtr(strtolower((string)$frase), "áéíóú", "aeiou"), strtr(strtolower((string)$CadBuscar), "áéíóú", "aeiou"));
            if (!($PosPalabra === false)):
                while (!($PosPalabra === false)):
                    $inicio     = substr( (string) $frase, 0, $PosPalabra);
                    $resto      = substr( (string) $frase, $PosPalabra);
                    $CadNueva   = $CadNueva . $inicio . "#@#";
                    $cadTalCual = substr( (string) $resto, 0, $LongCadBuscar);

                    $CadNueva = $CadNueva . $cadTalCual . "$|$";

                    $resto      = substr((string)$frase, $PosPalabra + $LongCadBuscar);
                    $PosPalabra = strpos(strtr(strtolower((string)$resto), "áéíóú", "aeiou"), strtr(strtolower((string)$CadBuscar), "áéíóú", "aeiou"));
                    $frase      = $resto;

                endwhile;

                $CadNueva = $CadNueva . $resto;
                $frase    = $CadNueva;

            endif;

        endfor;

        $frase = str_replace( "#@#",(string) $delante,(string) $frase);
        $frase = str_replace( "$|$",(string) $detras,(string) $frase);

        return $frase;

    } // Fin ColorearFrase

// HAYA LA IP DE ACCESO DEL CLIENTE
    function Hayar_IP()
    {
        if (getenv("HTTP_CLIENT_IP")):
            $IpEntrada = getenv("HTTP_CLIENT_IP");
        elseif (getenv("HTTP_X_FORWARDED_FOR")):
            $IpEntrada = getenv("HTTP_X_FORWARDED_FOR");
        else:
            $IpEntrada = getenv("REMOTE_ADDR");
        endif;

        return $IpEntrada;

    } // Fin Hayar_IP

// GENERA UNA CLAVE ALEATORIA DE LA LONGITUD ESPECIFICA
    function Generar_Clave($MinCaracts, $MaxCaracts)
    {
        // Creamos la semilla para la función rand()
        list($usec, $sec) = explode(' ', microtime());
        $semilla = (float)$sec + ((float)$usec * 100000);
        srand($semilla);

        // Generamos la clave
        $clave     = "";
        $max_chars = round( (float)rand($MinCaracts, $MaxCaracts)); // Entre min y max caracts
        $chars     = array();
        for ($i = "a"; $i < "z"; $i++) $chars[] = $i; // creamos vector de letras
        $chars[] = "z";
        for ($i = 0; $i < $max_chars; $i++):
            if ($i == 0)
                $letra = 0; // PARA QUE LA 1ª SIEMPRE LETRA
            else
                $letra = round( (float)rand(0, 4)); // primero escogemos entre letra y número
            if ($letra < 4) // es letra
                $clave .= $chars[round( (float)rand(0, count( (array)$chars) - 1))];
            else // es numero
                $clave .= round( (float)rand(0, 9));
        endfor;

        return $clave;
    } // Fin Generar_Clave

    /*
     *Extrae los valores numéricos de una expresión y los
     *devuelve en el formato en que fueron pasados.
     *Si no encuentra números devuelve false.
     */
    function getnum($numString = 0, $regexp = '/\d+\.?\d*/')
    {
        preg_match_all($regexp, $numString, $matches);

        return (is_numeric($matches[0][0])) ? $matches[0][0] : false;
    }

    function verifRealConDosDecimales($valor, $signo = 3)
    {
        if ($signo == 1)
            $patron = "/^[0-9]+(.[0-9]{1,2}|[0-9]*)$/";
        elseif ($signo == 2)
            $patron = "/^-[0-9]+(.[0-9]{1,2}|[0-9]*)$/";
        else
            $patron = "/^-?[0-9]+(.[0-9]{1,2}|[0-9]*)$/";

        if (!preg_match($patron, (string) $valor))
            return true;
        else
            return false;
    }

    function verifCantidad($valor)
    {
        for ($i = 0; $i < strlen( (string)$valor); $i++) {
            $ascii = ord((string)$valor[$i]);
            if (intval($ascii) >= 49 && intval($ascii) <= 57)
                continue;
            else
                return false;
        }

        return true;
    }


#######################################
#LAURA PARA LAS IMAGENES
//Fija la altura de una foto y retorna la anchura proporcional
    function fijarAltura($pfoto, $pfixaltura, &$altura, &$anchura)
    {
        if (file_exists($pfoto) == 1) { // LA IMAGEN EXISTE
            $size = getimagesize("$pfoto");
            // TAMAÑO ORIGINAL
            $altura  = $size[1];
            $anchura = $size[0];
            // FIJA ALTURA A $pfixaltura
            $anchura = ($anchura * $pfixaltura) / $altura;
            $altura  = $pfixaltura;
        }
    }

//Fija la anchura de una foto y retorna la altura proporcional
    function fijarAnchura($pfoto, $pfixanchura, &$altura, &$anchura)
    {
        if (file_exists($pfoto) == 1) { // LA IMAGEN EXISTE
            $size = getimagesize("$pfoto");
            // TAMAÑO ORIGINAL
            $altura  = $size[1];
            $anchura = $size[0];
            // FIJA ALTURA A $pfixaltura
            $altura  = ($altura * $pfixanchura) / $anchura;
            $anchura = $pfixanchura;
        }
    }


    function CaracteresEspecialesWSDL($cadena)
    {

        $resultado = str_replace( '<(>&<)>', '&',(string) $cadena);
        $resultado = str_replace( '<(><<)>', '<',(string) $resultado);

        return ($resultado);
    }

    //FORMATO % CON UN DECIMAL PARA GRAFICOS
    function formatoPorcentaje($numero, $numeroDecimales = "")
    {

        $parteEntera = intval($numero);
        if ($numero - $parteEntera == 0):
            //$numeroDevolver=number_format($parteEntera,0,",",".");
            $numeroDevolver = number_format((float)$parteEntera, 0, ".", "");
        else:
            if ($numeroDecimales != ""):
                $numeroDevolver = number_format((float)$numero, $numeroDecimales, ".", "");
            else:
                $numeroDevolver = number_format((float)$numero, 1, ".", "");
            endif;
        endif;

        return $numeroDevolver;

    }

// FORMATO NUMERICO PARA MOSTRAR
    function formatoNumero($numero)
    {
        if (($numero == "-") || ($numero == "")):
            return $numero;
        endif;

        $parteEntera = intval($numero);
        if ($numero - $parteEntera == 0):
            //$numeroDevolver=number_format($parteEntera,0,",",".");
            $numeroDevolver = number_format((float)$parteEntera, 0, ".", "");
        else:
            //$numeroDevolver=number_format($numero, 3, ',', '.');
            $numeroDevolver = number_format((float)$numero, 3, ".", "");
        endif;

        return $numeroDevolver;
    }

    // FORMATO NUMERICO PARA MOSTRAR MONEDAS REDONDEANDO AL NUMERO DE DECIMALES DE LA MONEDA, POR DEFECTO DOS DECIMALES
    function formatoMoneda($numero, $idMoneda = NULL)
    {
        global $bd;

        //VARIABLE PARA CALCULAR EL NUMERO DE DECIMALES A MOSTRAR
        $numeroDecimales = 2;

        //SI VIENE MONEDA CALCULAMOS EL NUMERO DE DECIMALES PARA ESTA MONEDA
        if ($idMoneda != NULL):
            //BUSCO LA MONEDA
            $rowMoneda = $bd->VerReg("MONEDA", "ID_MONEDA", $idMoneda);

            //ACTUALIZO LA VARIABLE NUMERO DE DECIMALES
            $numeroDecimales = $rowMoneda->NUMERO_DECIMALES;
        endif;

        //CALCULO EL IMPORTE REDONDEADO AL NUMERO DE DECIMALES DE LA MONEDA
        $numeroDevolver = round( (float)$numero, $numeroDecimales);

        //MUESTRO EL NUMERO SIEMPRE CON UN NUMERO DE DECIMALES ESPECIFICADO
        $numeroDevolver = number_format((float)$numeroDevolver, $numeroDecimales, ".", "");

        //DEVUELVO EL VALOR REDONDEADO
        return $numeroDevolver;
    }


    // FUNCION DE TRADUCCION DE UNA PALABRA O FRASE
    function traduce_old($frase, $idIdioma = "ESP")
    {
        global $bd;
        global $administrador;

        // SE LE PASA UNA LITERAL QUE IDENTIFICA UNA FRASE Y UN IDIOMA. DEVUELVE LA FRASE EN EL IDIOMA SOLICITADO
        $fraseTraducida = $frase;
        if ($idIdioma == "ESP" || $idIdioma == "ENG"):
            $sqlDic    = "SELECT " . $idIdioma . " AS FRASE_TRADUCIDA FROM DICCIONARIO WHERE CLAVE = '" . trim( (string)$bd->escapeCondicional($frase)) . "' AND " . $idIdioma . " <> '' ";
            $resultDic = $bd->ExecSQL($sqlDic, "No");
            if ($rowDic = $bd->SigReg($resultDic)):
                $fraseTraducida = $rowDic->FRASE_TRADUCIDA;
            elseif ((ENTORNO != 'PRODUCCION') && (trim( (string)$fraseTraducida) != '-') && (trim( (string)$fraseTraducida) != '')):
                $fraseTraducida = "CLAVE SIN TRADUCIR - " . $fraseTraducida;
            endif;
        endif;

        //ESCAPAMOS COMILLAS
        //$fraseTraducida=str_replace("'","&#39;",$fraseTraducida);
        //$fraseTraducida=str_replace('"','&#34;',$fraseTraducida);
        $fraseTraducida = str_replace( "'", "·",(string) $fraseTraducida);
        $fraseTraducida = str_replace( '"', '·',(string) $fraseTraducida);

        return $fraseTraducida;
    }

    // FUNCION DE TRADUCCION DE UNA PALABRA O FRASE
    function traduce($frase, $idIdioma = "ESP")
    {
        global $bd;
        global $administrador;

        // SE LE PASA UNA LITERAL QUE IDENTIFICA UNA FRASE Y UN IDIOMA. DEVUELVE LA FRASE EN EL IDIOMA SOLICITADO
        $fraseTraducida = $frase;
        if (($idIdioma == "ESP" || $idIdioma == "ENG") && (trim( (string)$fraseTraducida) != '-')):
            $sqlDic    = "SELECT * FROM DICCIONARIO WHERE CLAVE = '" . trim( (string)$bd->escapeCondicional($frase)) . "'";
            $resultDic = $bd->ExecSQL($sqlDic, "No");
            if ($bd->NumRegs($resultDic) == 0):
                $fraseTraducida = (ENTORNO != 'PRODUCCION' ? "CLAVE NO INCLUIDA EN EL DICCIONARIO - " : "") . $frase;
            else:
                $rowDic = $bd->SigReg($resultDic);
                if ($idIdioma == "ESP"):
                    if (trim( (string)$rowDic->ESP) == ''):
                        $fraseTraducida = (ENTORNO != 'PRODUCCION' ? "CLAVE SIN TRADUCIR AL CASTELLANO - " : "") . $frase;
                    else:
                        $fraseTraducida = $rowDic->ESP;
                    endif;
                elseif ($idIdioma == "ENG"):
                    if (trim( (string)$rowDic->ENG) == ''):
                        $fraseTraducida = (ENTORNO != 'PRODUCCION' ? "CLAVE SIN TRADUCIR AL INGLES - " : "") . $frase;
                    else:
                        $fraseTraducida = $rowDic->ENG;
                    endif;
                endif;
            endif;
        endif;

        //ESCAPAMOS COMILLAS
        //$fraseTraducida=str_replace("'","&#39;",$fraseTraducida);
        //$fraseTraducida=str_replace('"','&#34;',$fraseTraducida);
        $fraseTraducida = str_replace( "'", "·",(string) $fraseTraducida);
        $fraseTraducida = str_replace( '"', '·',(string) $fraseTraducida);

        return $fraseTraducida;
    }

//NOS FACILITA LA URL ACTUAL DEL USUARIO
    function dameURL()
    {
        //$url="http://".$_SERVER['HTTP_HOST'].":".$_SERVER['SERVER_PORT'].$_SERVER['REQUEST_URI'];
        $url = "https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

        return $url;
    }

    //FUNCION PARA OBTENER EL NOMBRE DE UN CLIENTE A PARTIR DE UN ID_CLIENTE
    function obtenerNombreCliente($idCliente)
    {
        global $bd;

        // SE LE PASA UN ID QUE IDENTIFICA AL CLIENTE
        $nombreCliente = "";
        $sql           = "SELECT NOMBRE FROM CLIENTE WHERE ID_CLIENTE = '" . trim( (string)$bd->escapeCondicional($idCliente)) . "'";
        $result        = $bd->ExecSQL($sql);
        if ($row = $bd->SigReg($result)):
            $nombreCliente = $row->NOMBRE;
        endif;

        if ($nombreCliente == "" || $nombreCliente == NULL):
            return NULL;
        endif;

        return $nombreCliente;
    }

    //FUNCION PARA OBTENER LA REFERENCIA DE UN CLIENTE A PARTIR DE UN ID_CLIENTE
    function obtenerRefCliente($idCliente)
    {
        global $bd;

        // SE LE PASA UN ID QUE IDENTIFICA AL CLIENTE
        $refCliente = "";
        $sql        = "SELECT REFERENCIA FROM CLIENTE WHERE ID_CLIENTE = '" . trim( (string)$bd->escapeCondicional($idCliente)) . "'";
        $result     = $bd->ExecSQL($sql);
        if ($row = $bd->SigReg($result)):
            $refCliente = $row->REFERENCIA;
        endif;

        if ($refCliente == "" || $refCliente == NULL):
            return NULL;
        endif;

        return $refCliente;
    }

// FUNCION DE OBTENCION DE LA DESCRIPCION (EN EL IDIOMA CORRESPONDIENTE) DE UN PAIS DADO POR ID
    function obtenerDescripcionPais($idPais, $idIdioma = "ESP")
    {

        global $bd;

        // SE LE PASA UN ID QUE IDENTIFICA AL PAIS
        $descripcionPais = "";
        if ($idIdioma == "ESP" || $idIdioma == "ENG"):
            $sql    = "SELECT DESCRIPCION_" . $idIdioma . " AS DESCRIPCION FROM PAIS WHERE ID_PAIS = '" . trim( (string)$bd->escapeCondicional($idPais)) . "'";
            $result = $bd->ExecSQL($sql);
            if ($row = $bd->SigReg($result)):
                $descripcionPais = $row->DESCRIPCION;
            endif;
        endif;

        //SI NO SE HA ENCONTRADO LA DESCRIPCION INFORMAMOS DEL ERROR
        /*if($descripcionPais=="" || $descripcionPais==NULL):
            $bd->EnviarEmailErr('Descripción de pais no encontrada (libreria auxiliar.php)',
                'Descripción de pais no encontrada (libreria auxiliar.php); Idioma:'.$idIdioma.' ; idPais:'.$idPais.' ; sql:'.$sql);
        endif;*/
        if ($descripcionPais == "" || $descripcionPais == NULL):
            return NULL;
        endif;

        return $descripcionPais;

    }

// FUNCION DE OBTENCION DEL CODIGO DE UN PAIS DADO POR ID
    function obtenerCodigoPais($idPais)
    {

        global $bd;

        // SE LE PASA UN ID QUE IDENTIFICA AL PAIS
        $codigoPais = "";
        $sql        = "SELECT PAIS FROM PAIS WHERE ID_PAIS = '" . trim( (string)$bd->escapeCondicional($idPais)) . "'";
        $result     = $bd->ExecSQL($sql);
        if ($row = $bd->SigReg($result)):
            $codigoPais = $row->PAIS;
        endif;

        //SI NO SE HA ENCONTRADO LA DESCRIPCION INFORMAMOS DEL ERROR
        /*if($codigoPais=="" || $codigoPais==NULL):
            $bd->EnviarEmailErr('Código de pais no encontrado (libreria auxiliar.php)',
                'Código del pais no encontrado (libreria auxiliar.php); idPais:'.$idPais.' ; sql:'.$sql);
        endif;	*/
        if ($codigoPais == "" || $codigoPais == NULL):
            return NULL;
        endif;

        return $codigoPais;

    }

// FUNCION DE OBTENCION DEL ID INTERNO DE UN PAIS DADO POR CODIGO
    function obtenerIdPais($codigoPais)
    {

        global $bd;

        // SE LE PASA UN CODIGO QUE IDENTIFICA AL PAIS
        $idPais = "";
        $sql    = "SELECT ID_PAIS FROM PAIS WHERE PAIS = '" . trim( (string)$bd->escapeCondicional($codigoPais)) . "'";
        $result = $bd->ExecSQL($sql);
        if ($row = $bd->SigReg($result)):
            $idPais = $row->ID_PAIS;
        endif;

        //SI NO SE HA ENCONTRADO LA DESCRIPCION INFORMAMOS DEL ERROR
        /*if($codigoPais=="" || $codigoPais==NULL):
            $bd->EnviarEmailErr('Código de pais no encontrado (libreria auxiliar.php)',
                'Código del pais no encontrado (libreria auxiliar.php); idPais:'.$idPais.' ; sql:'.$sql);
        endif;	*/
        if ($idPais == "" || $idPais == NULL):
            return NULL;
        endif;

        return $idPais;

    }

// FUNCION DE OBTENCION DEL NIF DE UNA DIRECCION
    function ObtenerNIFDireccion($idDireccion)
    {
        global $bd;

        if ($idDireccion == ""):
            return NULL;
        endif;

        // SE LE PASA UN ID QUE IDENTIFICA AL PAIS
        $nifDireccion = "";
        $sql          = "SELECT * FROM DIRECCION WHERE ID_DIRECCION = '" . trim( (string)$bd->escapeCondicional($idDireccion)) . "'";
        $result       = $bd->ExecSQL($sql);
        if ($row = $bd->SigReg($result)):

            switch ($row->TIPO_DIRECCION):
                case 'Proveedor':
                    $rowProveedorDireccion = $bd->VerReg("PROVEEDOR", "ID_PROVEEDOR", $row->ID_PROVEEDOR, "No");
                    $nifDireccion          = $rowProveedorDireccion->NIF;
                    break;

                case 'Centro Fisico':
                    $rowCentroFisico = $bd->VerReg("CENTRO_FISICO", "ID_CENTRO_FISICO", $row->ID_CENTRO_FISICO, "No");
                    if ($rowCentroFisico->ID_ENTIDAD_EXPEDIDORA != ""):
                        $rowEntidadExpedidora = $bd->VerReg("ENTIDAD_EXPEDIDORA", "ID_ENTIDAD_EXPEDIDORA", $rowCentroFisico->ID_ENTIDAD_EXPEDIDORA, "No");
                        $nifDireccion         = $rowEntidadExpedidora->CIF;
                    endif;
                    break;
                case 'Cliente':
                    $rowCliente   = $bd->VerReg("CLIENTE", "ID_CLIENTE", $row->ID_CLIENTE, "No");
                    $nifDireccion = $rowCliente->NIF;
                    break;
                case 'Sociedad':
                    $rowSociedad  = $bd->VerReg("SOCIEDAD", "ID_SOCIEDAD", $row->ID_SOCIEDAD, "No");
                    $nifDireccion = $rowSociedad->CIF;
                    break;
                default:
                    break;

            endswitch;

        endif;


        if ($nifDireccion == "" || $nifDireccion == NULL):
            return NULL;
        endif;

        return $nifDireccion;

    }

//FUNCION QUE DEVUELVE LA URL ACTUAL
    function currrentPageURL()
    {
        $pageURL = 'http';
        if ($_SERVER["HTTPS"] == "on") {
            $pageURL .= "s";
        }
        $pageURL .= "://";
        $pageURL .= $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];

        return $pageURL;
    }

//FUNCION QUE DEVUELVE EL STRING CONVERTIDO A UTF8
    function to_utf8($str)
    {
        return mb_convert_encoding((string)$str, 'UTF-8', 'ISO-8859-1');
    }

//FUNCION QUE DEVUELVE EL STRING CONVERTIDO A ISO-8859-1
    function to_iso88591($str)
    {
        return mb_convert_encoding((string)$str, 'ISO-8859-1', 'UTF-8');
    }

    //SEPARA LOS GRADOS MINUTOS SEGUNDOS Y LETRA DE UNA LONGITUD O LATITUD. Y LA DEVUELVE EN UN ARRAY
    //EN EL ORDEN
    // 1- GRADOS
    // 2- MINUTOS
    // 3- SEGUNDOS
    // 4- LETRA
    function separarGradosMinutosSegundosLatitudLongitud($valor)
    {
        $valor = trim( (string)$valor);
        //RECOGE POSIBLE PRIMERA LETRA
        $ultimaLetra  = substr( (string) $valor, (strlen( (string)$valor) - 1));
        $primeraLetra = substr( (string) $valor, 0, 1);

        $letra               = "";
        $arrGradoMinSegLetra = array();
        //COMPROBAR SI LA LETRA ESTA AL PRINCIPIO O AL FINAL
        if (($ultimaLetra == "N") || ($ultimaLetra == "S") || ($ultimaLetra == "O") || ($ultimaLetra == "W") || ($ultimaLetra == "E")) {
            $valor = substr( (string) $valor, 0, strlen( (string)$valor) - 1);
            $letra = $ultimaLetra;
        } elseif (($primeraLetra == "N") || ($primeraLetra == "S") || ($primeraLetra == "O") || ($primeraLetra == "W") || ($primeraLetra == "E")) {
            $valor = substr( (string) $valor, strpos( (string)$valor, $primeraLetra) + 1);
            $letra = $primeraLetra;
        }

        $posicionSegundo = "";
        $posicionMinuto  = "";

        //COMPROBAMOS GRADOS Y RECOGEMOS SU VALOR
        if ((strpos( (string)$valor, 'º') !== false) || (strpos( (string)$valor, '°') !== false)) {
            if ((strpos( (string)$valor, 'º') !== false)):
                $posicionGrados = strpos( (string)$valor, 'º');
            elseif (strpos( (string)$valor, '°') !== false):
                $posicionGrados = strpos( (string)$valor, '°');
            endif;
            $grados = substr( (string) $valor, 0, $posicionGrados);

            //COMPROBAMOS EL SIGNO QUE SE USA PARA LOS MINUTOS

            if (substr_count((string)$valor, "\"") > 1):
                $posicionMinuto = strpos( (string)$valor, "\"");
            endif;
            if (strpos( (string)$valor, "'")):
                $posicionMinuto = strpos( (string)$valor, "'");
            elseif (strpos( (string)$valor, "´")):
                $posicionMinuto = strpos( (string)$valor, "´");
            elseif (strpos( (string)$valor, "’")):
                $posicionMinuto = strpos( (string)$valor, "’");
            elseif (strpos( (string)$valor, ",")):
                $posicionMinuto = strpos( (string)$valor, ",");
            endif;
            $minutos = substr( (string) $valor, $posicionGrados + 1, ($posicionMinuto - 1 - $posicionGrados));
            $minutos = str_replace(",", ".", (string) $minutos);

            //COMPROBAMOS SIGNOS SEGUNDOS
            if (strpos((string) $valor, "''")):
                $posicionSegundo = strpos((string) $valor, "''");
            elseif (strrpos((string) $valor, "\"")):
                if (substr_count((string) $valor, "\"") > 1):
                    $posicionSegundo = strrpos((string) $valor, "\"") - 1;
                else:
                    $posicionSegundo = strrpos((string) $valor, "\"");
                endif;
            elseif (strpos( (string)$valor, "´´")):
                $posicionSegundo = strpos( (string)$valor, "´´");
            elseif (strpos( (string)$valor, "’’")):
                $posicionSegundo = strpos( (string)$valor, "’’");
            endif;

            //COMPROBAMOS SI EL USUARIO NO HA METIDO SIMBOLO DE SEGUNDOS
            if ($posicionSegundo != ""):
                $segundos = substr( (string) $valor, $posicionMinuto + 1, ($posicionSegundo - 1 - $posicionMinuto));
            else:
                $segundos = substr( (string) $valor, $posicionMinuto + 1, strlen( (string)$valor) - $posicionMinuto);
            endif;
            $segundos = round( (float)str_replace(",", ".", (string) $segundos), 4);

            //GUARDAMOS TODO EN UN ARRAY
            $arrGradoMinSegLetra[0] = trim( (string)($grados != 0 ? $grados : 0));
            $arrGradoMinSegLetra[1] = trim( (string)($minutos != 0 ? $minutos : 0));
            $arrGradoMinSegLetra[2] = trim( (string)($segundos != 0 ? $segundos : 0));
            $arrGradoMinSegLetra[3] = trim( (string)($letra));
        }

        return $arrGradoMinSegLetra;

    }

    //FUNCION TRANSFORMA UNIDADES DE MEDIDA LATITUD LONGITUD

    function cambioFormatoLatitudLongitud($valor, $tipo)
    {
        $valor = trim( (string)$valor);

        //COMPROBAMOS SI LETRA EL PRINCIPIO O AL FINAL Y LA GUARDAMOS
        $ultimaLetra  = strtoupper( (string)substr( (string) $valor, (strlen( (string)$valor) - 1)));
        $primeraLetra = strtoupper( (string)substr( (string) $valor, 0, 1));
        $letra        = "";
        if (($ultimaLetra == "N") || ($ultimaLetra == "S") || ($ultimaLetra == "O") || ($ultimaLetra == "W") || ($ultimaLetra == "E")) {
            $valor = substr( (string) $valor, 0, strlen( (string)$valor) - 1);
            $letra = $ultimaLetra;
        } elseif (($primeraLetra == "N") || ($primeraLetra == "S") || ($primeraLetra == "O") || ($primeraLetra == "W") || ($primeraLetra == "E")) {
            $valor = substr( (string) $valor, strpos((string)$valor, (string)$primeraLetra) + 1);
            $letra = $primeraLetra;
        }


        $posicionSegundo = "";
        $posicionMinuto  = "";
        //COMPROBAMOS SI ESTA EN FORMATO DE GRADOS MINUTOS SEGUNDOS
        //  RECOGEMOS GRADOS
        if ((strpos( (string)$valor, 'º') !== false) || (strpos( (string)$valor, '°') !== false)) {
            if ((strpos( (string)$valor, 'º') !== false)):
                $posicionGrados = strpos( (string)$valor, 'º');
            elseif (strpos( (string)$valor, '°') !== false):
                $posicionGrados = strpos( (string)$valor, '°');
            endif;
            $grados = substr( (string) $valor, 0, $posicionGrados);

            //POSICION Y VALOR DE  MINUTOS
            if (substr_count((string)$valor, "\"") > 1):
                $posicionMinuto = strpos( (string)$valor, "\"");
            endif;
            if (strpos( (string)$valor, "'")):
                $posicionMinuto = strpos( (string)$valor, "'");
            elseif (strpos( (string)$valor, "´")):
                $posicionMinuto = strpos( (string)$valor, "´");
            elseif (strpos( (string)$valor, "’")):
                $posicionMinuto = strpos( (string)$valor, "’");
            elseif (strpos( (string)$valor, ",")):
                $posicionMinuto = strpos( (string)$valor, ",");
            endif;
            $minutos = substr( (string) $valor, $posicionGrados + 1, ($posicionMinuto - 1 - $posicionGrados));
            $minutos = str_replace(",", ".", (string) $minutos);

            //POSICION Y VALOR DE MINUTOS
            if (strpos((string)$valor, "''")):
                $posicionSegundo = strpos((string)$valor, "''");
            elseif (strrpos((string) $valor, "\"")):
                $posicionSegundo = strpos((string)$valor, "\"");
            elseif (strpos((string)$valor, "´´")):
                $posicionSegundo = strpos((string)$valor, "´´");
            elseif (strpos((string)$valor, "’’")):
                $posicionSegundo = strpos((string)$valor, "’’");
            endif;

            //SI EL USUARIO NO HA GUARDADO SIGNO SEGUNDOS
            if ($posicionSegundo != ""):
                $segundos = substr( (string) $valor, $posicionMinuto + 1, ($posicionSegundo - 1 - $posicionMinuto));
            else:
                $segundos = substr( (string) $valor, $posicionMinuto + 1, strlen( (string)$valor) - $posicionMinuto);
            endif;
            $segundos = round( (float)str_replace(",", ".", (string) $segundos), 4);
            //SI LA LETRA ES S o W SE DEVUELVE VALOR NEGATIVO
            $polo = ((($letra == "S") || ($letra == "O") || ($letra == "W")) ? -1.0 : 1.0);

            //RESULTADO EN FORMATO DECIMAL
            $resultado = $polo * ($grados + $minutos / 60 + $segundos / 3600);

            return $resultado;
        } else {
            //SUSTITUIR COMAS POR PUNTOS EN EL CASI DE EXISTIR
            $valor = str_replace(",", ".", (string) $valor);
            $letra = ($tipo == "latitud" ? ($valor >= 0 ? "N" : "S") : ($valor >= 0 ? "E" : "O"));

            //CALCULAMOS VALORES
            $valor    = abs( (float)$valor);
            $grados   = intval($valor);
            $minutos  = intval(($valor - $grados) * 60);
            $segundos = ((($valor - $grados) * 60) - $minutos) * 60;

            //DEVOLVEMOS EN FORMATO GRADOS, MINUTOS, SEGUNDOS
            $resultado = $grados . "º " . $minutos . "' " . $segundos . "''";

            return $resultado . " $letra";
        }

    }

    function cambioFormatoLatitud($valor)
    {
        return $this->cambioFormatoLatitudLongitud($valor, "latitud");
    }

    function cambioFormatoLongitud($valor)
    {
        return $this->cambioFormatoLatitudLongitud($valor, "longitud");
    }

    //TRADUCE ELEMENTOS SELECCIONADOS (QUE ESTEN EN EL DICCIONARIO) EN EL DESPLEGABLE PARA MOSTRARLOS EN "TEXTOLISTA".
    //SELELEMENTO EN FORMATO "element1|element2|....|elementN"
    function traducirElementosSeleccionadosDesplegable($selElemento)
    {
        global $administrador;

        //ARRAY PARA RECORRER ELEMENTOS
        $arrTraducir         = explode(SEPARADOR_BUSQUEDA_MULTIPLE, (string)$selElemento);
        $textoListaAtraducir = "";
        if (count( (array)$arrTraducir) == 1):
            foreach ($arrTraducir as $elemento):
                if ($textoListaAtraducir != ""):
                    $textoListaAtraducir .= SEPARADOR_BUSQUEDA_MULTIPLE;
                endif;
                $textoListaAtraducir .= $this->traduce($elemento, $administrador->ID_IDIOMA);
            endforeach;
        else:
            $textoListaAtraducir = $this->traduce("Seleccion Multiple", $administrador->ID_ADMINISTRADOR);
        endif;

        return $textoListaAtraducir;
    }

//FUNCION QUE DEVUELVE UN TEXTO ADECUADO A CSV DE SU REGION
    function to_csv($str, $number_to_text = false)
    {

        global $administrador;
        global $csv_sep;
        $cadena = "";

        if (preg_match('/"/', (string) $str)):
            $cadena = str_replace('"', '""', (string) $str);
        else:
            $cadena = $str;
        endif;

        //SI EL VALOR TIENE COMILLAS O EL SEPARADOR DE LA REGION, DELIMITAMOS CON COMILLAS
        if (preg_match('/"/', (string) $str) || preg_match("/" . $csv_sep . "/", (string) $str)):
            $cadena = '"' . $cadena . '"';
        endif;

        //SUSTITUIMOS SALTOS DE LINEA POR ESPACIO
        $cadena = preg_replace("[(\n)|(\r)|(\n\r)]", " ", $cadena);

        //SI ESTAMOS EN FORMATO EUROPEO, Y ES UN NUMERO, SUSTITUIMOS PUNTOS POR COMAS
        if ($administrador->FMTO_CSV == "EUROPEO" && is_numeric($str) && !$number_to_text):
            $cadena = str_replace( '.', ',',(string) $str);
        endif;

        //SI HAY QUE CONVERTIR UN NUMERO A TEXTO
        if ($number_to_text && is_numeric($str)):
            $cadena = '"=""' . $cadena . '"""';
        endif;

        return $cadena;
    }

    /**
     *
     * GENERAR KEY PARA FICHAS DE SEGURIDAD. ENLACE A PROVEEDOR
     *
     * @return string
     */
    function generarKey($tipoObjeto = "")
    {

        //SI NO VIENE OBJETO ASIGNAMOS A FICHAS DE SEGURIDAD
        if ($tipoObjeto == ""):
            $tipoObjeto = "FichaSeguridad";
        endif;

        do {
            $key = md5(uniqid(mt_rand(), false));
        } while ($this->validarKey($key, $tipoObjeto));

        return $key;
    }

    /**
     * VALIDAR KEY UNICA PARA FICHAS DE SEGURIDAD
     *
     * @param $key
     * @return bool
     */
    function validarKey($key, $tipoObjeto)
    {
        global $bd;

        if ($tipoObjeto == "FichaSeguridad"):
            $query  = "SELECT KEY_CORREO FROM INCIDENCIA_FICHA_SEGURIDAD_MATERIAL_IDIOMA WHERE KEY_CORREO ='" . trim( (string)$key) . "' LIMIT 1";
            $result = $bd->ExecSQL($query);

        elseif ($tipoObjeto == "OrdenContratacion"):
            $query  = "SELECT KEY_CORREO FROM ORDEN_CONTRATACION WHERE KEY_CORREO ='" . trim( (string)$key) . "' LIMIT 1";
            $result = $bd->ExecSQL($query);
        elseif ($tipoObjeto == "SolicitudProveedor"):
            $query  = "SELECT KEY_CORREO FROM SOLICITUD_TRANSPORTE_PROVEEDOR WHERE KEY_CORREO ='" . trim( (string)$key) . "' LIMIT 1";
            $result = $bd->ExecSQL($query);
        elseif ($tipoObjeto == "SolicitudTransporte"):
            $query  = "SELECT KEY_CORREO FROM SOLICITUD_TRANSPORTE WHERE KEY_CORREO ='" . trim( (string)$key) . "' LIMIT 1";
            $result = $bd->ExecSQL($query);
        elseif ($tipoObjeto == "SolicitudProveedorEmailFDS"):
            $query  = "SELECT KEY_CORREO_FDS FROM PROVEEDOR WHERE KEY_CORREO_FDS ='" . trim( (string)$key) . "' LIMIT 1";
            $result = $bd->ExecSQL($query);
        endif;

        if ($bd->NumRegs($result)) return true;
        else return false;
    }

    /**
     * @param $codigoCorreoMatriz codigo del correo
     * @return string
     */
    function correosMatrizPorID($codigoCorreoMatriz)
    {
        global $bd;
        global $administrador;

        //BUSCAMOS CORREO DESTINATARIO EN LA MATRIZ DE CORREOS
        $sqlDestinatarioCorreo   = "SELECT CPC.EMAIL
                                  FROM CORREO_PERSONA_CONTACTO CPC
                                  INNER JOIN CORREO C ON C.ID_CORREO = CPC.ID_CORREO
                                  WHERE CPC.BAJA = 0 AND CODIGO_CORREO = '" . $codigoCorreoMatriz . "'";
        $resulDestinatarioCorreo = $bd->ExecSQL($sqlDestinatarioCorreo);
        $destinatariosCorreo     = "";
        if (($resulDestinatarioCorreo != false) && ($bd->NumRegs($resulDestinatarioCorreo) > 0)):
            while ($rowDestinatarioCorreo = $bd->SigReg($resulDestinatarioCorreo)):
                if ($destinatariosCorreo != ""):
                    $destinatariosCorreo .= ",";
                endif;
                if ($rowDestinatarioCorreo->EMAIL == "EQUIPO IR"):
                    $destinatariosCorreo .= EQUIPO_IR_CORREO;
                else:
                    $destinatariosCorreo .= $rowDestinatarioCorreo->EMAIL;
                endif;

            endwhile;
        //SI NO ENCUENTRA DESTINATARIOS ENVIA ERROR AL EQUIPO
        else:
            $Asunto = "Fallo Envio Correo - Matriz Correos";
            $cuerpoMensaje = "El correo con clave: $codigoCorreoMatriz no se ha podido enviar por no tener destinatarios";
            $this->enviarCorreoSistema($Asunto, $cuerpoMensaje, OUTLOOK_USER, SENDER_EMAIL,EQUIPO_IR_CORREO); //CORREO INTERNO
        endif;

        return $destinatariosCorreo;
    }


    /**
     * @param $idCorreo id del correo
     * @return string
     */
    function correosMatrizPoridCorreo($idCorreo)
    {
        global $bd;
        global $administrador;

        //DEFINIMOS LA RESPUESTA
        $destinatariosCorreo = "";

        //BUSCAMOS CORREO DESTINATARIO EN LA MATRIZ DE CORREOS
        if ($idCorreo != ""):

            //BUSCAMOS LOS DESTINATARIOS DEL CORREO
            $sqlDestinatarioCorreo   = "SELECT DISTINCT CPC.EMAIL
                                      FROM CORREO_PERSONA_CONTACTO CPC
                                      WHERE CPC.BAJA = 0 AND CPC.ID_CORREO = '" . $idCorreo . "'";
            $resulDestinatarioCorreo = $bd->ExecSQL($sqlDestinatarioCorreo);
            if (($resulDestinatarioCorreo != false) && ($bd->NumRegs($resulDestinatarioCorreo) > 0)):
                while ($rowDestinatarioCorreo = $bd->SigReg($resulDestinatarioCorreo)):
                    if ($destinatariosCorreo != ""):
                        $destinatariosCorreo .= ",";
                    endif;
                    if ($rowDestinatarioCorreo->EMAIL == "EQUIPO IR"):
                        $destinatariosCorreo .= EQUIPO_IR_CORREO;
                    else:
                        $destinatariosCorreo .= $rowDestinatarioCorreo->EMAIL;
                    endif;

                endwhile;
            endif;
        endif;

        return $destinatariosCorreo;
    }

    /**
     * @param $rowAntigua
     * @param $rowActualizada
     * @return string
     * SE LE PASAN DOS $rows CON LOS MISMO CAMPOS, Y LA FUNCION LAS COMPARA
     */
    function obtenerCambiosRegistro($rowAntigua, $rowActualizada, $objeto)
    {

        global $administrador;
        global $bd;
        global $auxiliar;
        //VARIABLE PARA GUARDAR LOS CAMBIOS
        $cambiosDevolverESP = "";
        $cambiosDevolverENG = "";
        $cambiosDevolver    = "";

        //RECORREMOS LOS CAMPOS, PARA ENCONTRAR DIFERENCIAS
        foreach ($rowActualizada as $nombreCampo => $valorCampo):
            //VARIABLE PARA MOSTRAR EL CAMPO EN EL LOG DE MODIFICACION (RECOGEMOS VALOR BBDD)
            $motrarCampo = true;
            if ($rowAntigua->$nombreCampo != $valorCampo):

                $valorCampoAntiguo     = $rowAntigua->$nombreCampo;
                $valorCampoActualizado = $valorCampo;

                //EN FUNCIÓN DLE TIPO DE OBJETO, REGISTRAMOS LA INFORMACIÓN DE UNA MANERA U OTRA
                if (($objeto == "Orden de Transporte") || ($objeto == "EmbarqueGC")):

                    //OBTENEMOS EL NOMBRE DEL CAMPO EN ESPAÑOL Y EN INGLÉS
                    $GLOBALS["NotificaErrorPorEmail"] = "No";
                    $rowCampodiccionario              = $bd->VerRegRest("DICCIONARIO_CAMPOS_LOG_MOVIMIENTOS", "CLAVE = '$nombreCampo' AND MOSTRAR = 1 AND BAJA = 0", "No");
                    $nombreCampoEsp                   = $auxiliar->traduce($rowCampodiccionario->CLAVE_DICCIONARIO, "ESP");
                    $nombreCampoEng                   = $auxiliar->traduce($rowCampodiccionario->CLAVE_DICCIONARIO, "ENG");

                    //OBTENEMOS EL VALOR DEL CAMPO ANTIGUO Y NUEVO. SI SE TRATA DE UN ID, OBTENDREMOS EL VALOR ADECUADO
                    $valorAntiguoAux = $this->obtenerValorCampoBBDD($rowAntigua, $nombreCampo);
                    $valorNuevoAux   = $this->obtenerValorCampoBBDD($rowActualizada, $nombreCampo);

                    //PARA CAMPOS COMPUESTOS (CON MONEDA AL FINAL)
                    if (strpos( (string)$nombreCampoEsp, " | Moneda") !== false):
                        $nombreCampoEsp = str_replace( " | Moneda", "", (string)$nombreCampoEsp);
                    elseif (strpos( (string)$nombreCampoEsp, "| Moneda") !== false):
                        $nombreCampoEsp = str_replace( "| Moneda", "", (string)$nombreCampoEsp);
                    elseif (strpos( (string)$nombreCampoEsp, " |Moneda") !== false):
                        $nombreCampoEsp = str_replace( " |Moneda", "", (string)$nombreCampoEsp);
                    elseif (strpos( (string)$nombreCampoEsp, "|Moneda") !== false):
                        $nombreCampoEsp = str_replace( "|Moneda", "", (string)$nombreCampoEsp);
                    endif;
                    if (strpos( (string)$nombreCampoEng, " | Currency") !== false):
                        $nombreCampoEng = str_replace( " | Currency", "", (string)$nombreCampoEng);
                    elseif (strpos( (string)$nombreCampoEng, "| Currency") !== false):
                        $nombreCampoEng = str_replace( "| Currency", "", (string)$nombreCampoEng);
                    elseif (strpos( (string)$nombreCampoEng, " |Currency") !== false):
                        $nombreCampoEng = str_replace( " |Currency", "", (string)$nombreCampoEng);
                    elseif (strpos( (string)$nombreCampoEng, "|Currency") !== false):
                        $nombreCampoEng = str_replace( "|Currency", "", (string)$nombreCampoEng);
                    endif;

                    //MOSTRAMOS LOS CAMPOS TRADUCIDOS
                    $cambiosDevolver .= ($cambiosDevolver != "" ? ', \n' : "") . '[Campo]: {' . ucfirst( (string)$nombreCampoEsp) . '/' . ucfirst( (string)$nombreCampoEng) . '}. ' . '[Valor Antiguo Log]: $' . ($valorAntiguoAux == "" ? $valorCampoAntiguo : $valorAntiguoAux) . '$ - ' . '[Valor Nuevo Log]: %' . ($valorNuevoAux == "" ? $valorCampoActualizado : $valorNuevoAux) . '%|';
                else:
                    $cambiosDevolver .= ($cambiosDevolver != "" ? ', \n' : "") . '[Campo]: {' . ucfirst( (string)$nombreCampo) . '}. ' . '[Valor Antiguo Log]: $' . $valorCampoAntiguo . '$ - ' . '[Valor Nuevo Log]: %' . $valorCampoActualizado . '%|';
                endif;
            endif;
        endforeach;

        return $cambiosDevolver;
    }

    /**
     * @param $row
     * @param $nombreCampo
     * @return string
     * SE LE PASA LA $row CON LA INFORMACIÓN DEL OBJETO, Y EL NOMBRE DEL CAMPO. DE ESTA MANERA, OBTENDREMOS EL CAMPO DESEADO Y NO UN ID
     */
    function obtenerValorCampoBBDD($row, $nombreCampo)
    {
        global $bd;
        global $administrador;
        global $auxiliar;

        $valorCampo = "";

        //OBTENEMOS LA TABLA Y EL CAMPO DEL QUE OBTENER EL VALOR
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowCampoDiccionario              = $bd->VerReg("DICCIONARIO_CAMPOS_LOG_MOVIMIENTOS", "CLAVE", $nombreCampo, "No");

        if ($rowCampoDiccionario->TIPO == "foreign key"):

            //AHORA OBTENEMOS LA INFORMACIÓN DE LA TABLA AFECTADA SI SE TRATA D EUN CAMPO DE TIPO 'CLAVE FORÁNEA'
            $GLOBALS["NotificaErrorPorEmail"] = "No";
            $rowCampoTabla                    = $bd->VerReg($rowCampoDiccionario->TABLA, $rowCampoDiccionario->ID_REAL_TABLA, $row->$nombreCampo, "No");

            //ASIGNAMOS EL VALOR AL CAMPO
            $valorCampo = $rowCampoTabla->{$rowCampoDiccionario->CAMPO_TABLA_MOSTRAR};

        elseif ($rowCampoDiccionario->TIPO == "bool"):

            //ASIGNAMOS EL VALOR AL CAMPO, PERO TRADUCIDO
            $valorCampo = ($row->$nombreCampo == 0 ? $auxiliar->traduce("No", "ESP") . "/" . $auxiliar->traduce("No", "ENG") : $auxiliar->traduce("Si", "ESP") . "/" . $auxiliar->traduce("Si", "ENG"));

        elseif ($rowCampoDiccionario->TIPO == "enum"):

            //ASIGNAMOS EL VALOR AL CAMPO, PERO TRADUCIDO
            if (($row->$nombreCampo == NULL) || is_numeric($row->$nombreCampo)):
                $valorCampo = $row->$nombreCampo;
            else:
                //COMPRUEBO SI VIENE MÁS DE UN VALOR
                $arrCampos = explode(",", (string)$row->$nombreCampo);

                $valorCampoEsp = "";
                $valorCampoEng = "";
                $coma          = "";

                if (count( (array)$arrCampos) > 1):
                    //SI SE SELECCIONA MÁS DE UN VALOR, TRADUCIMOS TODOS
                    foreach ($arrCampos as $valor):
                        $valorCampoEsp .= $coma . $auxiliar->traduce($valor, "ESP");
                        $valorCampoEng .= $coma . $auxiliar->traduce($valor, "ENG");
                        $coma          = ",";
                    endforeach;

                    $valorCampo = $valorCampoEsp . "/" . $valorCampoEng;
                else:
                    $valorCampo = $auxiliar->traduce($row->$nombreCampo, "ESP") . "/" . $auxiliar->traduce($row->$nombreCampo, "ENG");
                endif;
            endif;

        endif;

        return $valorCampo;
    }

    function traduceLogMovimientosBBDD($cadena, $idIdioma = "ESP")
    {
        global $bd;
        global $administrador;
        global $auxiliar;

        $cadenaDevolver = "";
        $arrCambios     = explode("|", (string)$cadena);
        foreach ($arrCambios as $cambio):
            if (strpos( (string)$cambio, "{") === false): //PUNTO DE CORTE
                $cadenaDevolver .= $cambio;
            else:
                $nombreCampoCambio = substr( (string) $cambio, strpos( (string)$cambio, "{") + 1, (strpos( (string)$cambio, "}") - strpos( (string)$cambio, "{")) - 1);
                $sqlDic            = "SELECT * FROM DICCIONARIO_CAMPOS_LOG_MOVIMIENTOS WHERE CLAVE = '" . trim( (string)$bd->escapeCondicional($nombreCampoCambio)) . "' ";
                $resultDic         = $bd->ExecSQL($sqlDic, "No");
                //SI CAMPOS ESTAN EN DICCIONARIO RECOGEMOS TRADUCCIONES
                if ($bd->NumRegs($resultDic) > 0):

                    $rowDic                       = $bd->SigReg($resultDic);
                    $motrarCampo                  = $rowDic->MOSTRAR;
                    $nombreCampo                  = $rowDic->CLAVE_DICCIONARIO;
                    $valorCampoAntiguoInicial     = substr((string) $cambio, strpos((string) $cambio, "$") + 1, (strrpos((string) $cambio, "$") - strpos((string) $cambio, "$")) - 1);
                    $valorCampoActualizadoInicial = substr((string) $cambio, strpos((string) $cambio, "%") + 1, (strrpos((string) $cambio, "%") - strpos((string) $cambio, "%")) - 1);
                    $valorCampoActualizado        = $valorCampoActualizadoInicial;
                    $valorCampoAntiguo            = $valorCampoAntiguoInicial;

                    //SEGUN EL TIPO
                    switch ($rowDic->TIPO):
                        case 'enum':
                            $valorCampoAntiguo     = "[" . $valorCampoAntiguo . "]";
                            $valorCampoActualizado = "[" . $valorCampoActualizado . "]";
                            break;
                        case 'fecha':
                            $valorCampoAntiguo     = $auxiliar->fechaFmtoEsp($valorCampoAntiguo);
                            $valorCampoActualizado = $auxiliar->fechaFmtoEsp($valorCampoActualizado);
//                            $valorCampoAntiguoENG =  $auxiliar->fechaFmtoEsp($rowAntigua->$nombreCampo);
//                            $valorCampoActualizadoENG =  $auxiliar->fechaFmtoEsp($valorCampo);
                            break;
                        case 'foreign key':
                            //SI TIENE COLUMNA TABLA ES QUE ES UN ID, RECOGEMOS VALOR DE CORRESPONDIENTE TABLA
                            if ($rowDic->TABLA != ""):
                                //REGISTRO A RECOGER
                                $GLOBALS["NotificaErrorPorEmail"] = "No";
                                $NotificaErrorPorEmail            = "No";
                                $rowValorAntiguo                  = $bd->VerReg($rowDic->TABLA, $rowDic->ID_REAL_TABLA, $valorCampoAntiguo, "No");
                                $GLOBALS["NotificaErrorPorEmail"] = "No";
                                $NotificaErrorPorEmail            = "No";
                                $rowValorActualizado              = $bd->VerReg($rowDic->TABLA, $rowDic->ID_REAL_TABLA, $valorCampoActualizado, "No");

                                $valorCampoAntiguo     = $rowValorAntiguo->{$rowDic->CAMPO_TABLA_MOSTRAR};
                                $valorCampoActualizado = $rowValorActualizado->{$rowDic->CAMPO_TABLA_MOSTRAR};
                                if ($rowDic->CAMPO_TABLA_MOSTRAR2 != ""):
                                    $valorCampoAntiguo     .= " - " . $rowValorAntiguo->{$rowDic->CAMPO_TABLA_MOSTRAR2};
                                    $valorCampoActualizado .= " - " . $rowValorActualizado->{$rowDic->CAMPO_TABLA_MOSTRAR2};
                                endif;
                            endif;
                            break;
                        case 'bool':
                            $valorCampoAntiguo     = ($valorCampoAntiguo == '0') ? $auxiliar->traduce("No", "ESP") : $auxiliar->traduce("Si", "ESP");
                            $valorCampoActualizado = ($valorCampoActualizado == '0') ? $auxiliar->traduce("No", "ESP") : $auxiliar->traduce("Si", "ESP");
                            break;
                        case 'texto':
                            //YA ESTAN RECOGIDOS ARRIBA
                            break;
                    endswitch;

                    $cambio         = str_replace( "{" . $nombreCampoCambio . "}",(string) ucfirst( (string)$auxiliar->traduce($nombreCampo, $administrador->ID_IDIOMA)),(string) $cambio);
                    $cambio         = str_replace( "$" . $valorCampoAntiguoInicial . "$",(string) $valorCampoAntiguo, (string)$cambio);
                    $cambio         = str_replace( "%" . $valorCampoActualizadoInicial . "%",(string) $valorCampoActualizado,(string) $cambio);
                    $cadenaDevolver .= $cambio;
                else:
                    $cambio         = str_replace( "$", "",(string) $cambio);
                    $cambio         = str_replace( "%", "",(string) $cambio);
                    $cambio         = str_replace( "{", "",(string) $cambio);
                    $cambio         = str_replace( "}", "",(string) $cambio);
                    $cadenaDevolver .= $cambio;
                endif;
            endif;
        endforeach;

        return $this->traduceLogMovimientos($cadenaDevolver, $administrador->ID_IDIOMA);


    }

    function traduceLogMovimientosAccionesBlockchain($cadena, $objeto, $idIdioma = "ESP")
    {
        global $bd;
        global $administrador;
        global $auxiliar;

        $cadenaDevolver = "";
        $arrCambios     = explode("|", (string)$cadena);
        foreach ($arrCambios as $cambio):
            if (strpos( (string)$cambio, "{") === false): //PUNTO DE CORTE
                $cadenaDevolver .= $cambio;
            else:
                $nombreCampoCambio = substr( (string) $cambio, strpos( (string)$cambio, "{") + 1, (strpos( (string)$cambio, "}") - strpos( (string)$cambio, "{")) - 1);

                $sqlCampoTraduccion    = "SELECT NOMBRE_DICCIONARIO FROM CAMPO_OBJETO WHERE NOMBRE_CAMPO = '" . trim( (string)$bd->escapeCondicional($nombreCampoCambio)) . "' AND OBJETO = '" . $objeto . "'";
                $resultCampoTraduccion = $bd->ExecSQL($sqlCampoTraduccion, "No");
                if ($bd->NumRegs($resultCampoTraduccion) > 0):
                    $rowCampoTraduccion = $bd->SigReg($resultCampoTraduccion);
                    $nombreCampo        = $auxiliar->traduce($rowCampoTraduccion->NOMBRE_DICCIONARIO, $administrador->ID_IDIOMA);
                    $cambio             = str_replace( "{" . $nombreCampoCambio . "}",(string) $nombreCampo,(string) $cambio);
                endif;

                $cambio         = str_replace( "$", "",(string) $cambio);
                $cambio         = str_replace( "%", "",(string) $cambio);
                $cambio         = str_replace( "{", "",(string) $cambio);
                $cambio         = str_replace( "}", "",(string) $cambio);
                $cadenaDevolver .= $cambio;
            endif;
        endforeach;

        return $this->traduceLogMovimientos($cadenaDevolver, $administrador->ID_IDIOMA);


    }

//    function valorCampoLogMovimientos($nombreCampo, $valorCampo)
//    {
//        global $bd;
//        $valor = "";
//        $sqlDic = "SELECT * FROM DICCIONARIO_CAMPOS_LOG_MOVIMIENTOS WHERE CLAVE = '" . trim($bd->escapeCondicional($nombreCampo)) . "' ";
//        $resultDic = $bd->ExecSQL($sqlDic, "No");
//        if ($bd->NumRegs($resultDic) == 0):
//            $valor = $valorCampo;
//        else:
//            $rowDic = $bd->SigReg($resultDic);
//            if ($rowDic->TABLA != ""):
//                //REGISTRO A RECOGER
//                $row = $bd->VerReg($rowDic->TABLA, $nombreCampo, $valorCampo, "No");
//
//                $valor = $row->{$rowDic->CAMPO_TABLA_MOSTRAR};
//                if ($rowDic->CAMPO_TABLA_MOSTRAR2 != ""):
//                    $valor .= " - " . $row->{$rowDic->CAMPO_TABLA_MOSTRAR2};
//                endif;
//            else:
//                $valor = $valorCampo;
//            endif;
//        endif;
//
//        return $valor;
//    }

    /**
     * FUNCION RECURSIVA PARA TRADUCIR LOG MOVIMIENTOS PARSEADO (EVITAR GUARDAR TRADUCCION INGLES Y ESPAÑOL
     *
     * EL CONTENIDO DE LA FRASE QUE SE QUERRA TRADUCIR IRA ENTRE CORCHETES.Ej. "[campo] fecha_columna. [Valor Antiguo]: 15/12/2015 [Campo Viejo]: 12/12/1212
     *
     * @param $cadena
     * @param string $idIdioma
     * @return mixed
     */
    function traduceLogMovimientos($cadena, $idIdioma = "ESP")
    {
        global $bd;
        global $administrador;
        global $auxiliar;
        if (strpos( (string)$cadena, "[") === false): //PUNTO DE CORTE
            return $cadena;
        else:
            $traduce = substr( (string) $cadena, strpos( (string)$cadena, "[") + 1, (strpos( (string)$cadena, "]") - strpos( (string)$cadena, "[")) - 1);
            $cadena  = str_replace( "[$traduce]",(string) ucfirst( (string)$auxiliar->traduce($traduce, $administrador->ID_IDIOMA)),(string) $cadena);

            return $this->traduceLogMovimientos($cadena, $idIdioma);
        endif;

    }

    function traduceLogMovimientosOE($campoModificado, $cadena, $idIdioma = "ESP")
    {
        global $bd;
        global $administrador;
        global $auxiliar;

        $cadenaDevolver = "";

        //BUSCAMOS SI EL CAMPO TIENE TRADUCCION
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowCampoDiccionario = $bd->VerReg("DICCIONARIO_CAMPOS_LOG_MOVIMIENTOS", "CLAVE", $campoModificado, "No");
        if ($rowCampoDiccionario != false):
            //SEGUN EL TIPO
            switch ($rowCampoDiccionario->TIPO):
                case 'fecha':
                    if (strpos( (string)$cadena, ":") !== false):
                        if ($cadena != "0000-00-00 00:00:00"):
                            $cadenaDevolver = $auxiliar->fechaFmtoEspHora($cadena);
                        else:
                            $cadenaDevolver = "-";
                        endif;
                    else:
                        if ($cadena != "0000-00-00"):
                            $cadenaDevolver = $auxiliar->fechaFmtoEsp($cadena);
                        else:
                            $cadenaDevolver = "-";
                        endif;
                    endif;
                    break;

                case 'foreign key':
                    //SI TIENE COLUMNA TABLA ES QUE ES UN ID, RECOGEMOS VALOR DE CORRESPONDIENTE TABLA
                    if ($rowCampoDiccionario->TABLA != ""):
                        //REGISTRO A RECOGER
                        $GLOBALS["NotificaErrorPorEmail"] = "No";
                        $rowTablaCampo = $bd->VerReg($rowCampoDiccionario->TABLA, $rowCampoDiccionario->ID_REAL_TABLA, $cadena, "No");

                        if ($rowCampoDiccionario->CAMPO_TABLA_MOSTRAR != ""):
                            $cadenaDevolver = $rowTablaCampo->{$rowCampoDiccionario->CAMPO_TABLA_MOSTRAR};
                        endif;

                        if (($rowCampoDiccionario->CAMPO_TABLA_MOSTRAR != "") && (($rowCampoDiccionario->CAMPO_TABLA_MOSTRAR2 != "") || ($rowCampoDiccionario->CAMPO_TABLA_MOSTRAR3 != ""))):
                            $cadenaDevolver .= " - ";
                        endif;

                        if ($rowCampoDiccionario->CAMPO_TABLA_MOSTRAR3 != ""):
                            if ($idIdioma == "ESP"):
                                if ($rowCampoDiccionario->CAMPO_TABLA_MOSTRAR2 != ""):
                                    $cadenaDevolver .= $rowTablaCampo->{$rowCampoDiccionario->CAMPO_TABLA_MOSTRAR2};
                                else:
                                    $cadenaDevolver .= $rowTablaCampo->{$rowCampoDiccionario->CAMPO_TABLA_MOSTRAR3};
                                endif;
                            elseif ($idIdioma == "ENG"):
                                $cadenaDevolver .= $rowTablaCampo->{$rowCampoDiccionario->CAMPO_TABLA_MOSTRAR3};
                            endif;
                        elseif ($rowCampoDiccionario->CAMPO_TABLA_MOSTRAR2 != ""):
                            $cadenaDevolver .= $rowTablaCampo->{$rowCampoDiccionario->CAMPO_TABLA_MOSTRAR2};
                        endif;
                    endif;
                    break;

                case 'bool':
                    $cadenaDevolver = ($cadena == 0) ? $auxiliar->traduce("No", $idIdioma) : $auxiliar->traduce("Si", $idIdioma);
                    break;

                default:
                    $cadenaDevolver = $cadena;
                    break;
            endswitch;
        else:
            $cadenaDevolver = $cadena;
        endif;

        return $cadenaDevolver;
    }

    function descargarFdSIdiomaCentroFisico($arrMateriales, $idCentroFisico)
    {
        global $bd;
        global $pathClases;
        $rutaFichas = $pathClases . RUTA_FICHAS_SEGURIDAD_MATERIAL;
        //COMPRIMIMOS EL ZIP
        $nombreFichero = "FdS_" . time() . ".zip";
        $filename      = $rutaFichas . $nombreFichero;
        $hayFicheros   = false;

        //COMPROBAR SI TIENE FICHA DE SEGURIDAD
        foreach ($arrMateriales as $idMaterial):
            $rowMaterial = $bd->VerReg("MATERIAL", "ID_MATERIAL", $idMaterial, "No");
            $rutaFichero = "";
            // SI DEBE TENER FICHA DE SEGURIDAD COMPROBAMOS EL IDIOMA DEL ALMACEN PARA MOSTRAR LA FICHA EN SU IDIOMA
            // SI NO BUSCAMOS LA FICHA EN INGLES
            // Y SI TMP EXISTE COMPROBAMOS SI EXISTE ALGUNA FICHA DE SEGURIDAD PARA MOSTRAR DE ESE MATERIAL
            $rowCentroFisicoAlmacen = $bd->VerReg("CENTRO_FISICO", "ID_CENTRO_FISICO", $idCentroFisico, "No");
            if ($rowCentroFisicoAlmacen->ID_PAIS != ""):
                $rowPais                          = $bd->VerReg("PAIS", "ID_PAIS", $rowCentroFisicoAlmacen->ID_PAIS, "No");
                $NotificaErrorPorEmail            = "No";
                $GLOBALS["NotificaErrorPorEmail"] = "No";
                $rowFichaSeguridad                = $bd->VerRegRest("FICHA_SEGURIDAD_MATERIAL_IDIOMA", "ID_IDIOMA = $rowPais->ID_IDIOMA_PRINCIPAL AND ID_MATERIAL = $rowMaterial->ID_MATERIAL ", "No");
                unset($NotificaErrorPorEmail);
                unset($GLOBALS["NotificaErrorPorEmail"]);
                if ($rowFichaSeguridad != false):
                    $rutaFichero  = $rutaFichas . $rowFichaSeguridad->FICHERO;
                    $siglasIdioma = $bd->VerReg("IDIOMA", "ID_IDIOMA", $rowFichaSeguridad->ID_IDIOMA, "No")->SIGLAS;
                //SI NO NO ESTAN EN EL IDIOMA DEL PAIS DEL ALMACEN, MOSTRAMOS IDIOMA POR DEFECTO INGLES
                else:
                    $rowIdiomaIngles                  = $bd->VerReg("IDIOMA", "IDIOMA_ESP", "Inglés", "No");
                    $NotificaErrorPorEmail            = "No";
                    $GLOBALS["NotificaErrorPorEmail"] = "No";
                    $rowFichaSeguridad                = $bd->VerRegRest("FICHA_SEGURIDAD_MATERIAL_IDIOMA", "ID_IDIOMA = $rowIdiomaIngles->ID_IDIOMA AND ID_MATERIAL = $rowMaterial->ID_MATERIAL ", "No");
                    unset($NotificaErrorPorEmail);
                    unset($GLOBALS["NotificaErrorPorEmail"]);
                    if ($rowFichaSeguridad != false):
                        $rutaFichero  = $rutaFichas . $rowFichaSeguridad->FICHERO;
                        $siglasIdioma = $bd->VerReg("IDIOMA", "ID_IDIOMA", $rowFichaSeguridad->ID_IDIOMA, "No")->SIGLAS;
                    //SI TMP ESTA EN INGLES, MOSTRAMOS LA QUE HAYA SI HAY...
                    else:
                        $NotificaErrorPorEmail            = "No";
                        $GLOBALS["NotificaErrorPorEmail"] = "No";
                        $rowFichaSeguridad                = $bd->VerReg("FICHA_SEGURIDAD_MATERIAL_IDIOMA", "ID_MATERIAL", $rowMaterial->ID_MATERIAL, "No");
                        unset($NotificaErrorPorEmail);
                        unset($GLOBALS["NotificaErrorPorEmail"]);
                        if ($rowFichaSeguridad != false):
                            $rutaFichero  = $rutaFichas . $rowFichaSeguridad->FICHERO;
                            $siglasIdioma = $bd->VerReg("IDIOMA", "ID_IDIOMA", $rowFichaSeguridad->ID_IDIOMA, "No")->SIGLAS;
                        endif;
                    endif;
                endif;
            endif;
            if ($rutaFichero != ""):

                //COMPRUEBO QUE EXISTA Y SEA FICHERO
                //echo 'zip -r -j ' . $filename . ' ' . $rutaFichero;
                $salida      = @shell_exec("zip -r -j  $filename $rutaFichero");
                $hayFicheros = true;
            endif;

        endforeach;

        if ($hayFicheros):
            //CABECERAS
            //LO MUESTRO PARA DESCARGAR
            header("Content-Type: application/octet-stream");
            header("Content-Type: application/force-download");
            header("Content-Disposition: attachment; filename=" . $nombreFichero . "\n\n");
            header("Content-Transfer-Encoding: binary");
            //ESCRIBIMOS FICHERO (DESCARGAR)
            readfile($filename);
            //BORRAMOS FICHERO DEL SISTEMA
            unlink($filename);

            return true;
        else:
            return false;
        endif;
    }

    function descargarFdSIdiomaAlmacen($arrMateriales, $idAlmacen)
    {
        global $bd;
        global $pathClases;
        $rutaFichas = $pathClases . RUTA_FICHAS_SEGURIDAD_MATERIAL;
        //COMPRIMIMOS EL ZIP
        $nombreFichero = "FdS_" . time() . ".zip";
        $filename      = $rutaFichas . $nombreFichero;
        $hayFicheros   = false;

        //COMPROBAR SI TIENE FICHA DE SEGURIDAD
        foreach ($arrMateriales as $idMaterial):
            $rowMaterial = $bd->VerReg("MATERIAL", "ID_MATERIAL", $idMaterial, "No");
            $rutaFichero = "";
            // SI DEBE TENER FICHA DE SEGURIDAD COMPROBAMOS EL IDIOMA DEL ALMACEN PARA MOSTRAR LA FICHA EN SU IDIOMA
            // SI NO BUSCAMOS LA FICHA EN INGLES
            // Y SI TMP EXISTE COMPROBAMOS SI EXISTE ALGUNA FICHA DE SEGURIDAD PARA MOSTRAR DE ESE MATERIAL
            $rowAlmacen             = $bd->VerReg("ALMACEN", "ID_ALMACEN", $idAlmacen, "No");
            $rowCentroFisicoAlmacen = $bd->VerReg("CENTRO_FISICO", "ID_CENTRO_FISICO", $rowAlmacen->ID_CENTRO_FISICO, "No");
            if ($rowCentroFisicoAlmacen->ID_PAIS != ""):
                $rowPais                          = $bd->VerReg("PAIS", "ID_PAIS", $rowCentroFisicoAlmacen->ID_PAIS, "No");
                $NotificaErrorPorEmail            = "No";
                $GLOBALS["NotificaErrorPorEmail"] = "No";
                $rowFichaSeguridad                = $bd->VerRegRest("FICHA_SEGURIDAD_MATERIAL_IDIOMA", "ID_IDIOMA = $rowPais->ID_IDIOMA_PRINCIPAL AND ID_MATERIAL = $rowMaterial->ID_MATERIAL ", "No");
                unset($NotificaErrorPorEmail);
                unset($GLOBALS["NotificaErrorPorEmail"]);
                if ($rowFichaSeguridad != false):
                    $rutaFichero  = $rutaFichas . $rowFichaSeguridad->FICHERO;
                    $siglasIdioma = $bd->VerReg("IDIOMA", "ID_IDIOMA", $rowFichaSeguridad->ID_IDIOMA, "No")->SIGLAS;
                //SI NO NO ESTAN EN EL IDIOMA DEL PAIS DEL ALMACEN, MOSTRAMOS IDIOMA POR DEFECTO INGLES
                else:
                    $rowIdiomaIngles                  = $bd->VerReg("IDIOMA", "IDIOMA_ESP", "Inglés", "No");
                    $NotificaErrorPorEmail            = "No";
                    $GLOBALS["NotificaErrorPorEmail"] = "No";
                    $rowFichaSeguridad                = $bd->VerRegRest("FICHA_SEGURIDAD_MATERIAL_IDIOMA", "ID_IDIOMA = $rowIdiomaIngles->ID_IDIOMA AND ID_MATERIAL = $rowMaterial->ID_MATERIAL ", "No");
                    unset($NotificaErrorPorEmail);
                    unset($GLOBALS["NotificaErrorPorEmail"]);
                    if ($rowFichaSeguridad != false):
                        $rutaFichero  = $rutaFichas . $rowFichaSeguridad->FICHERO;
                        $siglasIdioma = $bd->VerReg("IDIOMA", "ID_IDIOMA", $rowFichaSeguridad->ID_IDIOMA, "No")->SIGLAS;
                    //SI TMP ESTA EN INGLES, MOSTRAMOS LA QUE HAYA SI HAY...
                    else:
                        $NotificaErrorPorEmail            = "No";
                        $GLOBALS["NotificaErrorPorEmail"] = "No";
                        $rowFichaSeguridad                = $bd->VerReg("FICHA_SEGURIDAD_MATERIAL_IDIOMA", "ID_MATERIAL", $rowMaterial->ID_MATERIAL, "No");
                        unset($NotificaErrorPorEmail);
                        unset($GLOBALS["NotificaErrorPorEmail"]);
                        if ($rowFichaSeguridad != false):
                            $rutaFichero  = $rutaFichas . $rowFichaSeguridad->FICHERO;
                            $siglasIdioma = $bd->VerReg("IDIOMA", "ID_IDIOMA", $rowFichaSeguridad->ID_IDIOMA, "No")->SIGLAS;
                        endif;
                    endif;
                endif;
            endif;
            if ($rutaFichero != ""):

                //COMPRUEBO QUE EXISTA Y SEA FICHERO
                //echo 'zip -r -j ' . $filename . ' ' . $rutaFichero;
                $salida      = @shell_exec("zip -r -j  $filename $rutaFichero");
                $hayFicheros = true;
            endif;

        endforeach;

        if ($hayFicheros):
            //CABECERAS
            //LO MUESTRO PARA DESCARGAR
            header("Content-Type: application/octet-stream");
            header("Content-Type: application/force-download");
            header("Content-Disposition: attachment; filename=" . $nombreFichero . "\n\n");
            header("Content-Transfer-Encoding: binary");
            //ESCRIBIMOS FICHERO (DESCARGAR)
            readfile($filename);
            //BORRAMOS FICHERO DEL SISTEMA
            unlink($filename);

            return true;
        else:
            return false;
        endif;
    }

    /***
     * @param $idAlmacen ID DEL ALMACEN A CONSULTAR
     * @return RESULTSET DE LA SQL QUE OBTIENE LAS RUTAS
     */
    function getRutasDeAlmacen($idAlmacen)
    {
        global $bd;

        $sqlRutasDeAlmacen    = "SELECT DISTINCT R.ID_RUTA,R.RUTA FROM RUTA R 
                                    INNER JOIN RUTA_DESTINO RD ON RD.ID_RUTA = R.ID_RUTA
                                    INNER JOIN RUTA_DESTINO_ALMACEN RDA ON RDA.ID_RUTA_DESTINO = RD.ID_RUTA_DESTINO 
                                    WHERE RDA.ID_ALMACEN = $idAlmacen AND R.BAJA = 0 AND RD.BAJA = 0 AND RDA.BAJA = 0";
        $resultRutasDeAlmacen = $bd->ExecSQL($sqlRutasDeAlmacen, "No");

        return $resultRutasDeAlmacen;
    }

    function getRutasDeCentroFisico($idCentroFisico)
    {
        global $bd;

        $sqlRutasDeAlmacen = "SELECT DISTINCT R.ID_RUTA,R.RUTA FROM RUTA R 
                                    INNER JOIN RUTA_DESTINO RD ON RD.ID_RUTA = R.ID_RUTA
                                    INNER JOIN RUTA_DESTINO_ALMACEN RDA ON RDA.ID_RUTA_DESTINO = RD.ID_RUTA_DESTINO 
                                    INNER JOIN ALMACEN A ON A.ID_ALMACEN = RDA.ID_ALMACEN 
                                    INNER JOIN CENTRO_FISICO CF ON CF.ID_CENTRO_FISICO = A.ID_CENTRO_FISICO 
                                    WHERE CF.ID_CENTRO_FISICO = $idCentroFisico AND R.BAJA = 0 AND RD.BAJA = 0 AND RDA.BAJA = 0 AND CF.BAJA = 0 AND A.BAJA = 0";

        $resultRutasDeAlmacen = $bd->ExecSQL($sqlRutasDeAlmacen, "No");

        return $resultRutasDeAlmacen;
    }

    //FUNCION QUE DEVUELVE EL NUMERO DE RUTAS A LAS QUE PERTENECE EL ALMACEN
    function getNumRutasAlmacen($idAlmacen)
    {
        global $bd;

        $resultRutasDeAlmacen = self::getRutasDeAlmacen($idAlmacen);

        return $bd->NumRegs($resultRutasDeAlmacen);
    }

    /***
     * @param $idAlmacen ID DEL ALMACEN A CONSULTAR
     * @return RESULTSET DE LA SQL QUE OBTIENE LAS RUTAS
     */
    function getListaRutasDeAlmacen($idAlmacen)
    {
        global $bd;

        $resultRutasDeAlmacen = self::getRutasDeAlmacen($idAlmacen);

        $listaRutas = "";
        while ($rowRuta = $bd->SigReg($resultRutasDeAlmacen)):
            $listaRutas .= $rowRuta->RUTA . ", ";
        endwhile;

        if (strlen( (string)$listaRutas) > 0):
            $listaRutas = substr( (string) $listaRutas, 0, -2);
        endif;

        return $listaRutas;
    }

    /***
     * @param $idAlmacen ID DEL ALMACEN A CONSULTAR
     * @return RESULTSET DE LA SQL QUE OBTIENE LAS RUTAS
     */
    function getIdsRutasDeAlmacen($idAlmacen)
    {
        global $bd;

        $resultRutasDeAlmacen = self::getRutasDeAlmacen($idAlmacen);

        $idsRutasAlmacen = array();
        while ($rowRuta = $bd->SigReg($resultRutasDeAlmacen)):
            $idsRutasAlmacen[] = $rowRuta->ID_RUTA;
        endwhile;

        return $idsRutasAlmacen;
    }

    /***
     * @param $idAlmacenOrigen ID DEL ALMACEN ORIGEN
     * @param $idAlmacenDestino ID DEL ALMACEN DESITNO
     * @return ID_RUTA QUE VA de origen a destino
     */
    function getIdRutaDeAlmacenes($idAlmacenOrigen, $idAlmacenDestino)
    {
        global $bd;

        $idsRutasDeAlmacenOrigen  = self::getIdsRutasDeAlmacen($idAlmacenOrigen);
        $idsRutasDeAlmacenDestino = self::getIdsRutasDeAlmacen($idAlmacenDestino);

        foreach ($idsRutasDeAlmacenOrigen as $idRutaAlmacenOrigen):
            $idRuta = array_search($idRutaAlmacenOrigen, $idsRutasDeAlmacenDestino);
            if ($idRuta !== false):
                return $idsRutasDeAlmacenDestino[$idRuta];
            endif;
        endforeach;

        return NULL;
    }

    /***
     * FUNCION QEU BUSCA UNA RUTA COMUN ENTRE ORIGEN Y DESTINO. SI NO LA ENCUENTRA DEVUELVE UNA RUTA DEL ALMACEN DE DESTINO.
     * @param $idAlmacenOrigen ID DEL ALMACEN ORIGEN
     * @param $idAlmacenDestino ID DEL ALMACEN DESITNO
     * @return ID_RUTA
     */
    function getIdRutaDeAlmacenDestino($idAlmacenOrigen, $idAlmacenDestino)
    {
        global $bd;

        $idRuta = false;

        $numRutas = self::getNumRutasAlmacen($idAlmacenDestino);
        if ($numRutas > 0):
            //SI HAY MAS DE UNA RUTA AL ALMACEN DE DESTINO COJO LA QUE TIENEN EN COMUN ORIGEN Y DESTINO
            $idRuta = self::getIdRutaDeAlmacenes($idAlmacenOrigen, $idAlmacenDestino);
            //SI NO HAY UNA RUTA COMÚN PONGO UNA DE LAS RUTAS DEL ALMACEN DE DESTINO
            if ($idRuta == NULL):
                $arrayRutas = self::getIdsRutasDeAlmacen($idAlmacenDestino);

                return $arrayRutas[0];
            endif;
        endif;

        return $idRuta;
    }

    /***
     * FUNCION QEU BUSCA TODAS LAS RUTAS COMUNES ENTRE ORIGEN Y DESTINO. SI NO ENCUENTRA DEVUELVE TODAS LAS RUTA DEL ALMACEN DE DESTINO.
     * @param $idAlmacenOrigen ID DEL ALMACEN ORIGEN
     * @param $idAlmacenDestino ID DEL ALMACEN DESITNO
     * @return array RUTAS
     */
    function getIdsRutasDeAlmacenDestino($idAlmacenOrigen, $idAlmacenDestino)
    {
        global $bd;

        $arrayRutas = NULL;

        $numRutas = self::getNumRutasAlmacen($idAlmacenDestino);
        if ($numRutas > 0):
            //SI HAY MAS DE UNA RUTA AL ALMACEN DE DESTINO COJO LAS QUE TIENEN EN COMUN ORIGEN Y DESTINO
            $idsRutasDeAlmacenOrigen  = self::getIdsRutasDeAlmacen($idAlmacenOrigen);
            $idsRutasDeAlmacenDestino = self::getIdsRutasDeAlmacen($idAlmacenDestino);

            foreach ($idsRutasDeAlmacenOrigen as $idRutaAlmacenOrigen):
                $posIdRuta = array_search($idRutaAlmacenOrigen, $idsRutasDeAlmacenDestino);
                if ($posIdRuta !== false):
                    $arrayRutas[] = $idsRutasDeAlmacenDestino[$posIdRuta];
                endif;
            endforeach;

            if ($arrayRutas != NULL):
                return $arrayRutas;
            endif;

            //SI NO HAY UNA RUTA COMÚN PONGO TODAS DE LAS RUTAS DEL ALMACEN DE DESTINO
            if ($arrayRutas == NULL):
                foreach ($idsRutasDeAlmacenDestino as $idRuta):
                    $arrayRutas[] = $idRuta;
                endforeach;
            endif;
        endif;

        return $arrayRutas;
    }


    /***
     * @param $row ROW DE LA TABLA A CONSULTAR
     * @param $campoESP NOMBRE DEL CAMPO EN ESPAÑOL
     * @param $campoENG NOMBRE DEL CAMPO EN INGLES
     * @return mixed VALOR DEL CAMPO A DEVOLVER, SI EL VALOR EN INGLES ESTÁ VACIO, DEVUELVE EN ESPAÑOL
     */
    function devolverCampoIdiomaAdmin($row, $nombreCampoESP, $nombreCampoENG)
    {
        global $administrador;
        if ($administrador->ID_IDIOMA == "ESP"):
            return $row->$nombreCampoESP;
        else:
            if ($row->$nombreCampoENG != ""):
                return $row->$nombreCampoENG;
            else:
                return $row->$nombreCampoESP;
            endif;
        endif;

    }


    /**
     * MAYUSCULA LA PRIMERA LETRA DE CADA PALABRA
     * @param $string CADENA DE ENTRADA
     * @param array $delimiters
     * @param array $exceptions
     * @return string
     */
    function titleCase($stringTitleCase, $delimitadoresTC = array(" ", "-", "."), $excepcionesTC = array("OC", "de", "OM", "por", "AGM", "en", "OTS", "y", "SPV", "APQ"))
    {
        /*
         * Exceptions in lower case are words you don't want converted
         * Exceptions all in upper case are any words you don't want converted to title case
         *   but should be converted to upper case, e.g.:
         *   king henry viii or king henry Viii should be King Henry VIII
         */
        // $string = mb_convert_case($string, MB_CASE_TITLE, "UTF-8");
        $i = 0;

        foreach ($delimitadoresTC as $dlnr => $delimitadorTC) {
            $palabrasTitleCase = explode((string)$delimitadorTC, (string)$stringTitleCase);
            if (count( (array)$palabrasTitleCase) > 1) {
                $fraseConvertir = array();
                foreach ($palabrasTitleCase as $wordnr => $palabraTC) {
                    if (in_array(mb_strtoupper($palabraTC, "UTF-8"), (array) $excepcionesTC)) {
                        // check exceptions list for any words that should be in upper case
                        $palabraTC = mb_strtoupper($palabraTC, "UTF-8");
                    } elseif (in_array(mb_strtolower((string) $palabraTC, "UTF-8"), (array) $excepcionesTC)) {
                        // check exceptions list for any words that should be in upper case
                        $palabraTC = mb_strtolower((string) $palabraTC, "UTF-8");
                    } elseif (!in_array($palabraTC, (array) $excepcionesTC)) {
                        // convert to uppercase (non-utf8 only)
                        $palabraTC = ucfirst( (string)$palabraTC);
                    }
                    echo "indice: $i";
                    array_push($fraseConvertir, $palabraTC);
                    $i++;
                }

                $stringTitleCase = join($delimitadorTC, $fraseConvertir);
            }
        }//foreach
        return $stringTitleCase;
    }


    /**
     * DEVUELVE LAS FECHAS DE UN DIA ESPECIFICO EN UN RANGO DE FECHAS (POR EJEMPLO TODOS LOS LUNES DE 2018
     * @param $fechaInicio FECHA DE INICIO, DEBE ESTAR EN FORMATO (Y-m-d)
     * @param $fechaFin FECHA DE FIN, DEBE ESTAR EN FORMATO (Y-m-d)
     * @param $numDia NUMERO DEL DIA A BUSCAR DE 0 A 6 (DOMINGO A SABADO)
     */
    function getFechaPorDiaPorRangoFechas($fechaInicio, $fechaFin, $numDia)
    {
        $fechaFin = strtotime( (string)$fechaFin);
        $arrDias  = array('1' => 'Monday', '2' => 'Tuesday', '3' => 'Wednesday', '4' => 'Thursday', '5' => 'Friday', '6' => 'Saturday', '0' => 'Sunday');
        for ($i = strtotime( (string)$arrDias[$numDia], strtotime( (string)$fechaInicio)); $i <= $fechaFin; $i = strtotime( (string)'+1 week', $i))
            $arrFechas[] = date('Y-m-d', $i);

        return $arrFechas;
    }

    /**
     * DEVUELVE LA SUMA DE HORAS
     * @param $hora1 en formato 00:00:00
     * @param $hora2 en formato 00:00:00
     * return la suma de las horas
     */
    function suma_horas($hora1, $hora2)
    {

        $hora1 = explode(":", (string)$hora1);
        $hora2 = explode(":", (string)$hora2);
        $temp  = 0;

        //sumo segundos
        $segundos = (int)$hora1[2] + (int)$hora2[2];
        while ($segundos >= 60) {
            $segundos = $segundos - 60;
            $temp++;
        }

        //sumo minutos
        $minutos = (int)$hora1[1] + (int)$hora2[1] + $temp;
        $temp    = 0;
        while ($minutos >= 60) {
            $minutos = $minutos - 60;
            $temp++;
        }

        //sumo horas
        $horas = (int)$hora1[0] + (int)$hora2[0] + $temp;

        if ($horas < 10)
            $horas = '0' . $horas;

        if ($minutos < 10)
            $minutos = '0' . $minutos;

        if ($segundos < 10)
            $segundos = '0' . $segundos;

        $sum_hrs = $horas . ':' . $minutos . ':' . $segundos;

        return ($sum_hrs);

    }

    /**
     * DEVUELVE EL NUMERO DE DIAS DE UN ANO ESPECIFICO
     * @param $ano
     */
    function getDiasAno($ano)
    {
        $diasAno = 365;
        if (($ano % 4 == 0) && (($ano % 100 != 0) || ($ano % 400 == 0))) $diasAno = 366;

        return $diasAno;
    }


    /**
     * DEVUELVE DIFERENCIA DE DIAS ENTRE DOS FECHAS
     * @param string $fechaInicio
     * @param string $fechaFin
     * @return int
     */
    function getDiasRestaFechas($fechaInicio, $fechaFin)
    {
        return round((strtotime( (string)$fechaFin) - strtotime( (string)$fechaInicio)) / 86400);
    }

    /**
     * DEVUELVE DIFERENCIA DE DIAS ENTRE DOS FECHAS
     * @param string $fechaInicio en formato SQL
     * @param string $txDias con los dias a restar
     * @param string $txhoras con las horas a restar
     * @return $txFechaReturn restada en formato sql
     */
    function restarDiasFecha($fechaInicio, $txDias, $txHoras = 0)
    {
        //CONVERTIMOS LA FECHA A TIMESTAMP
        $nuevafecha = strtotime( (string)$fechaInicio);

        //VAMOS SUMANDO LOS DIAS, TENIENDO EN CUENTA FIN DE SEMANAS
        for ($numDias = 0; $numDias < $txDias; $numDias++):

            //AVANZAMOS LOS DIAS
            $nuevafecha = strtotime( (string)'-1 day', $nuevafecha);

            //SI  HABRIA QUE QUITAR SABADO DOMINGO...
            /*if ((date('w', $nuevafecha) == 0) || (date('w', $nuevafecha) == 6)):
                $nuevafecha = strtotime('+1 day', $nuevafecha);
            endif;*/
        endfor;

        //SUMAMOS LAS HORAS
        if ($txHoras > 0):
            $nuevafecha = strtotime( (string)'-' . $txHoras . ' hour', $nuevafecha);
        endif;

        //LO VOLVEMOS A CONVERTIR A SQL
        $txFechaReturn = date('Y-m-d H:i:s', $nuevafecha);

        return $txFechaReturn;
    }

    /**
     * DEVUELVE LA SUMA DE UNOS DÍAS A UNA FECHA
     * @param string $fechaInicio en formato SQL
     * @param string $txDias con los dias a restar
     * @param string $txhoras con las horas a restar
     * @return $txFechaReturn restada en formato sql
     */
    function sumarDiasFecha($fechaInicio, $txDias, $txHoras = 0)
    {
        //CONVERTIMOS LA FECHA A TIMESTAMP
        $nuevafecha = strtotime( (string)$fechaInicio);

        //VAMOS SUMANDO LOS DIAS, TENIENDO EN CUENTA FIN DE SEMANAS
        for ($numDias = 0; $numDias < $txDias; $numDias++):

            //AVANZAMOS LOS DIAS
            $nuevafecha = strtotime( (string)'+1 day', $nuevafecha);

        endfor;

        //SUMAMOS LAS HORAS
        if ($txHoras > 0):
            $nuevafecha = strtotime( (string)'+' . $txHoras . ' hour', $nuevafecha);
        endif;

        //LO VOLVEMOS A CONVERTIR A SQL
        $txFechaReturn = date('Y-m-d H:i:s', $nuevafecha);

        return $txFechaReturn;
    }

    /**
     * FUNCIÓN PARA COMPARAR ARRAYS MULTIDIMENSIONALES (array_diff() SOLO FUNCIONA CON ARRAYS UNIDIMENSIONALES) Y DEVOLVER EL ARRAY DE DIFERENCIAS
     * @param array $arrayInicial
     * @param array $arrayComparar
     * @return $diff array formado con las diferencias entre $arrayInicial y $arrayComparar
     */
    function array_diff_multidimensional($arrayInicial, $arrayComparar)
    {
        $arrayInicial = (array) $arrayInicial;
        $arrayComparar = (array) $arrayComparar;
        foreach ($arrayInicial as $clave => $valor):
            // VARIABLE PARA GUARDAR EL ARRAY FINAL CON LAS DIFERENCIAS ENTRE $arrayInicial Y $arrayComparar
            unset($valoresDiferentes);

            if (is_int($clave)):
                // COMPARA VALORES ENTEROS
                if (array_search($valor, $arrayComparar) === false):
                    $valoresDiferentes = $valor;
                elseif (is_array($valor)):
                    $valoresDiferentes = $this->array_diff_multidimensional($valor, $arrayComparar[$clave]);
                endif;

                if ($valoresDiferentes):
                    $diff[] = $valoresDiferentes;
                endif;
            else:
                // COMPARA CLAVES NO ENTERAS
                if (!$arrayComparar[$clave]):
                    $valoresDiferentes = $valor;
                elseif (is_array($valor)):
                    $valoresDiferentes = $this->array_diff_multidimensional($valor, $arrayComparar[$clave]);
                endif;

                if ($valoresDiferentes):
                    $diff[$clave] = $valoresDiferentes;
                endif;
            endif;
        endforeach;

        return $diff;
    }

    /**
     * SI CROSS_DOCKING == 1, SE OBTIENEN TODOS LOS ALMACENES SECUNDARIOS QUE CUELGAN DEL ALMACEN PROPORCIONADO
     * @param $idAlmacen : idAlmacen de la línea
     * @param $crossDocking : 1 o 0 dependiendo de si el bulto está definido como crossDocking
     * @return array
     */
    function comprobarAlmacenDestino($idAlmacen, $tipoBulto)
    {
        global $bd;

        //SE COMPRUEBA SI EL BULTO ES DE CROSS_DOCKING
        $arrAlmacenes = array();

        if ($tipoBulto == 'CrossDocking'):
            //SE OBTIENE EL ALMACEN AL QUE PERTENETE LA LINEA, y LOS ALMACENES A LOS QUE PERTENECEN LOS CENTROS FISICOS CROSS DOCKING RELACIONADOS CON ESTE
            if ($idAlmacen != NULL):
                $GLOBALS["NotificaErrorPorEmail"] = "No";
                $rowAlmacenLinea                  = $bd->VerReg("ALMACEN", "ID_ALMACEN", $idAlmacen, "No");

                //SE OBTIENE EL CENTRO_FISICO RELACIONADO CON ESE ALMACEN
                $rowCentroFisico = $bd->VerReg("CENTRO_FISICO", "ID_CENTRO_FISICO", $rowAlmacenLinea->ID_CENTRO_FISICO);

                //CALCULO EL ALMACEN EN FUNCION DE SI EL CENTRO FISICO DEL ALMACEN ES CROSSDOCKING O NO
                if ($rowCentroFisico->CROSS_DOCKING == 1): //CENTRO FISICO ES CROSSDOCKING, SACO LO ALMACENES DE LA RUTA DEL CENTRO FISICO
                    //BUSCO LA DIRECCION DEL CENTRO FISICO
                    $rowDireccionCentroFisico = $bd->VerRegRest("DIRECCION", "TIPO_DIRECCION = 'Centro Fisico' AND ID_CENTRO_FISICO = $rowCentroFisico->ID_CENTRO_FISICO AND BAJA = 0");

                    //BUSCO LOS ALMACENES ACTIVOS RELACIONADOS CON LA DIRECCION
                    $sqlAlmacenesSecundarios    = "SELECT DISTINCT(ID_ALMACEN) 
                                                FROM RUTA_DESTINO_ALMACEN 
                                                WHERE ID_DIRECCION = $rowDireccionCentroFisico->ID_DIRECCION AND BAJA = 0";
                    $resultAlmacenesSecundarios = $bd->ExecSQL($sqlAlmacenesSecundarios);
                    while ($rowAlmacenCentroFisicoSecundario = $bd->SigReg($resultAlmacenesSecundarios)):
                        array_push($arrAlmacenes, $rowAlmacenCentroFisicoSecundario->ID_ALMACEN);
                    endwhile;
                else: //CENTRO FISICO ES CROSSDOCKING, SACO LO ALMACENES DE LA RUTA DEL CENTRO FISICO PADRE
                    //BUSCO EL CENTRO FISICO PADRE
                    $rowCentroFisicoSecundario = $bd->VerRegRest("CENTRO_FISICO_SECUNDARIO", "ID_CENTRO_FISICO_SECUNDARIO = $rowCentroFisico->ID_CENTRO_FISICO AND BAJA = 0");

                    if ($rowCentroFisicoSecundario != false):
                        //SE OBTIENE EL CENTRO_FISICO CROSSDOCKING PADRE
                        $rowCentroFisicoCrossDockingPadre = $bd->VerReg("CENTRO_FISICO", "ID_CENTRO_FISICO", $rowCentroFisicoSecundario->ID_CENTRO_FISICO);

                        //BUSCO LA DIRECCION DEL CENTRO FISICO
                        $rowDireccionCentroFisico = $bd->VerRegRest("DIRECCION", "TIPO_DIRECCION = 'Centro Fisico' AND ID_CENTRO_FISICO = $rowCentroFisicoCrossDockingPadre->ID_CENTRO_FISICO AND BAJA = 0");

                        //BUSCO LOS ALMACENES ACTIVOS RELACIONADOS CON LA DIRECCION
                        $sqlAlmacenesSecundarios    = "SELECT DISTINCT(ID_ALMACEN) 
                                                    FROM RUTA_DESTINO_ALMACEN 
                                                    WHERE ID_DIRECCION = $rowDireccionCentroFisico->ID_DIRECCION AND BAJA = 0";
                        $resultAlmacenesSecundarios = $bd->ExecSQL($sqlAlmacenesSecundarios);
                        while ($rowAlmacenCentroFisicoSecundario = $bd->SigReg($resultAlmacenesSecundarios)):
                            array_push($arrAlmacenes, $rowAlmacenCentroFisicoSecundario->ID_ALMACEN);
                        endwhile;
                    endif;
                endif;
                //FIN CALCULO EL ALMACEN EN FUNCION DE SI EL CENTRO FISICO DEL ALMACEN ES CROSSDOCKING O NO
            endif;
        else:
            array_push($arrAlmacenes, $idAlmacen);
        endif;

        return $arrAlmacenes;
    }

    /**
     * Convierto una fecha en formato de fecha del usuario al formato deseado y la devuelvo. Solo válido para PHP > 5.3
     * Mejora a fechaFmtoSQL ya que se le puede pasar como parametro el formato al que queremos convertir la fecha
     * @param $fecha : La fecha a convertir
     * @param $nuevoFormato : Formato al que queremos convertir la fecha
     * @return string
     */
    function CambiarFormatoFecha($fecha, $nuevoFormato)
    {
        global $administrador;
        switch ($administrador->FMTO_FECHA) {
            case 'yyyy-mm-dd':
                $formatoFecha = 'Y-m-d';
                break;
            case 'mm-dd-yyyy':
                $formatoFecha = 'm-d-Y';
                break;
            case 'dd-mm-yyyy':
                $formatoFecha = 'd-m-Y';
                break;
            default:
                $formatoFecha = 'd-m-Y';
                break;
        }

        $objFecha             = DateTime::createFromFormat($formatoFecha, $fecha);
        $fechaFormatoCambiado = date( (string)$nuevoFormato, $objFecha->getTimestamp());

        return $fechaFormatoCambiado;
    }

    /**
     * Convierto una fecha en formato de fecha del usuario al formato deseado y la devuelvo. Válido para PHP < 5.3
     * @param $fecha : La fecha a convertir
     * @param $nuevoFormato : Formato al que queremos convertir la fecha
     * @param $separador : Caracter que separa el dia,mes y año de la fecha que pasamos
     * @return string
     */
    function CambiarFormatoFechaAnt($fecha, $nuevoFormato, $separador)
    {
        global $administrador;
        $array_datos_fecha = explode((string)$separador, (string)$fecha);

        switch ($administrador->FMTO_FECHA) {
            case 'yyyy-mm-dd':
                $año = $array_datos_fecha[0];
                $mes = $array_datos_fecha[1];
                $dia = $array_datos_fecha[2];
                break;
            case 'mm-dd-yyyy':
                $año = $array_datos_fecha[2];
                $mes = $array_datos_fecha[0];
                $dia = $array_datos_fecha[1];
                break;
            case 'dd-mm-yyyy':
                $año = $array_datos_fecha[2];
                $mes = $array_datos_fecha[1];
                $dia = $array_datos_fecha[0];
                break;
            default:
                $año = $array_datos_fecha[2];
                $mes = $array_datos_fecha[1];
                $dia = $array_datos_fecha[0];
                break;
        }
        $fechaOrdenada        = $dia . "-" . $mes . "-" . $año;
        $fechaFormatoCambiado = date( (string)$nuevoFormato, strtotime( (string)$fechaOrdenada));

        return $fechaFormatoCambiado;
    }


    //FUNCION PARA REGISTRAR LAS ACCIONES DEL LOG DE MOVIMIENTOS
    function crearEventosMovimientos($idLogMov)
    {
        global $administrador;
        global $bd;
        global $pathClases;

        $rowLog = $bd->VerReg("LOG_MOVIMIENTOS", "ID_LOG_MOVIMIENTOS", $idLogMov, "No");

        // CREACION DE EVENTO PARA REGISTRAR EN BLOCKCHAIN
        $fecha_actual = date("Y-m-d H:i:s");
        $sqlInsert    = "INSERT INTO EVENTO SET
                          TIPO_EVENTO = 'Registrar accion'
                          , ID_ADMINISTRADOR = '" . $rowLog->ID_ADMINISTRADOR . "'
                          , TIPO_OBJETO = 'Accion'
                          , ID_OBJETO = '" . $idLogMov . "'
                          , FECHA_CREACION = '" . $fecha_actual . "'
                          , FECHA_ULTIMA_MODIFICACION = '" . $fecha_actual . "'
                          , BAJA = 0";
        $bd->ExecSQL($sqlInsert);
        $idEvento = $bd->IdAsignado();

        $rowEvento = $bd->VerReg("EVENTO", "ID_EVENTO", $idEvento);

        //VAMOS INSERTANDO LOS CAMPOS Y SUS VALORES EN LA TABLA EVENTO_DATOS_PARAMETRIZADOS
        $arrDatosLog = array("Usuario" => "ID_ADMINISTRADOR", "Fecha" => "FECHA", "Objeto" => "OBJETO", "Id Objeto" => "ID_OBJETO", "Tipo Movimiento" => "TIPO_MOVIMIENTO", "Descripcion" => "DESCRIPCION", "Datos Modificados" => "DATOS");
        $fieldName   = array();
        $fieldValue  = array();

        foreach ($arrDatosLog as $nombreMostrar => $nombreCampo):
            $sqlInsert = "INSERT INTO EVENTO_DATOS_PARAMETRIZADOS SET
                      ID_EVENTO = '" . $idEvento . "'
                      , NOMBRE_MOSTRAR = '" . $bd->escapeCondicional($nombreMostrar) . "'
                      , NOMBRE_CAMPO = '" . $bd->escapeCondicional($nombreCampo) . "'
                      , VALOR_CAMPO = '" . $bd->escapeCondicional($rowLog->{$nombreCampo}) . "'";
            $bd->ExecSQL($sqlInsert);
            $idEventoDatos = $bd->IdAsignado();

            $fieldName[]  = $nombreCampo;
            $fieldValue[] = $rowLog->{$nombreCampo};
        endforeach;

        //AÑADIMOS TAMBIEN EL PROYECTO Y PROVEEDOR EN CASO DE QUE LOS TENGA
        $rowLogPP = $bd->VerRegRest("LOG_MOVIMIENTOS_PROY_PROVEEDOR", "ID_LOG_MOVIMIENTOS = $idLogMov", "No");
        if ($rowLogPP != false):
            $arrDatosLog = array("Proyecto" => "ID_PROYECTO", "Proveedor" => "ID_PROVEEDOR");
            foreach ($arrDatosLog as $nombreMostrar => $nombreCampo):
                $sqlInsert = "INSERT INTO EVENTO_DATOS_PARAMETRIZADOS SET
                          ID_EVENTO = '" . $idEvento . "'
                          , NOMBRE_MOSTRAR = '" . $bd->escapeCondicional($nombreMostrar) . "'
                          , NOMBRE_CAMPO = '" . $bd->escapeCondicional($nombreCampo) . "'
                          , VALOR_CAMPO = '" . $bd->escapeCondicional($rowLogPP->{$nombreCampo}) . "'";
                $bd->ExecSQL($sqlInsert);
                $idEventoDatos = $bd->IdAsignado();

                $fieldName[]  = $nombreCampo;
                $fieldValue[] = $bd->escapeCondicional($rowLogPP->{$nombreCampo});
            endforeach;
        endif;

        if ($idEvento != '' && $idEventoDatos != ''):
            //CREAMOS EL EVENTO
            $idEventoPendienteTransmitir = $this->crear_evento_pendiente_transmitir($rowEvento->ID_EVENTO, $fieldName, $fieldValue);
        endif;

        //COMPROBAMOS SI EN EL MOVIMIENTO SE HA GUARDADO ALGUN DOCUMENTO
        $sqlCamposModificados    = "SELECT NOMBRE_CAMPO, VALOR_NUEVO FROM LOG_MOVIMIENTOS_CAMPO WHERE ID_LOG_MOVIMIENTOS = '" . $idLogMov . "'";
        $resultCamposModificados = $bd->ExecSQL($sqlCamposModificados);
        while ($rowCamposModificados = $bd->SigReg($resultCamposModificados)):
            $sqlDocumento    = "SELECT * FROM DOCUMENTO_OBJETO WHERE NOMBRE_CAMPO = '" . $rowCamposModificados->NOMBRE_CAMPO . "' AND OBJETO = '" . $rowLog->OBJETO . "'";
            $resultDocumento = $bd->ExecSQL($sqlDocumento, "No");
            //COMPROBAMOS SI EL CAMPO ES UN DOCUMENTO
            if ($bd->NumRegs($resultDocumento) > 0):
                $rowDocumento = $bd->SigReg($resultDocumento);
                $carpetaExtra = "";
                if ($rowDocumento->RUTA == "embarques/"):
                    $nombreFichero = "ADJUNTO_" . $rowLog->ID_OBJETO . "_" . rawurlencode($rowCamposModificados->VALOR_NUEVO);
                else:
                    if ($rowDocumento->NOMBRE_CAMPO == "ADJUNTO_JUSTIFICANTE_EXTRACOSTE" && $rowDocumento->OBJETO = "EmbarqueGC"):
                        $rowExtracosteEmbarque = $bd->VerRegRest("EXTRACOSTE_EMBARQUE", "ID_EMBARQUE = " . $rowLog->ID_OBJETO . " AND BAJA = '0'", "No");
                        $carpetaExtra          = ($rowExtracosteEmbarque->SECCION == 'Carga' ? "justificante_extracoste_carga/" : "justificante_extracoste_descarga/");
                    else:
                        $nombreFichero = $rowCamposModificados->VALOR_NUEVO;
                    endif;
                endif;

                $doc = $pathClases . 'documentos/' . $rowDocumento->RUTA . $carpetaExtra . $nombreFichero;
                if (file_exists($doc) == 1): // HAY DOCUMENTO
                    //CREAMOS EL EVENTO DEL DOCUMENTO
                    $idEventoPendienteTransmitir = $this->crear_evento_documento_pendiente_transmitir($rowEvento->ID_EVENTO, $rowDocumento->TIPO_DOCUMENTO, $nombreFichero);
                endif;
            endif;
            //COMPROBAMOS SI EL DOCUMENTO ES LA FACTURA DE LA ORDEN DE TRANSPORTE
            if (strpos( (string)$rowCamposModificados->NOMBRE_CAMPO, "ADJUNTO_FACTURA") !== false && $rowLog->OBJETO = "Orden de Transporte"):
                $sqlDocumento    = "SELECT * FROM DOCUMENTO_OBJETO WHERE TIPO_DOCUMENTO = 'Factura' AND OBJETO = '" . $rowLog->OBJETO . "'";
                $resultDocumento = $bd->ExecSQL($sqlDocumento, "No");
                if ($bd->NumRegs($resultDocumento) > 0):
                    $rowDocumento = $bd->SigReg($resultDocumento);
                    $doc          = $pathClases . "documentos/otc_documento_proveedor/" . $rowCamposModificados->VALOR_NUEVO;
                    if (file_exists($doc) == 1): // HAY DOCUMENTO
                        //CREAMOS EL EVENTO DEL DOCUMENTO
                        $idEventoPendienteTransmitir = $this->crear_evento_documento_pendiente_transmitir($rowEvento->ID_EVENTO, $rowDocumento->TIPO_DOCUMENTO, $rowCamposModificados->VALOR_NUEVO);
                    endif;
                endif;
            endif;
            //COMPROBAMOS SI EL DOCUMENTO SE GUARDA EN LA TABLA FICHERO
        endwhile;
    }

    function crear_evento_pendiente_transmitir($idEvento, $fieldName, $fieldValue, $fechaEjecucion = "")
    {
        global $administrador;
        global $bd;


        //BUSCAMOS EL EVENTO
        $rowEvento = $bd->VerReg("EVENTO", "ID_EVENTO", $idEvento);

        //GENERAMOS EL ARRAY DE DATOS
        $data = array(
            'eventId'      => $rowEvento->ID_EVENTO,
            'eventType'    => $rowEvento->TIPO_EVENTO,
            'userID'       => $administrador->ID_ADMINISTRADOR,
            'companyCode'  => $rowEvento->ROL_ADMINISTRADOR,
            'orderType'    => $rowEvento->TIPO_OBJETO,
            'orderCode'    => $rowEvento->ID_OBJETO,
            'creationDate' => $rowEvento->FECHA_CREACION,
            'fieldName'    => $fieldName,
            'fieldValue'   => str_replace( "'", "''", $fieldValue)
        );

        //CONVERTIMOS A JSON
        $data_json = $this->convertir_json($data);

        $sqlInsert = "INSERT INTO EVENTO_PENDIENTE_TRANSMITIR SET
                          ID_EVENTO = " . $rowEvento->ID_EVENTO . "
                          , ESTADO_LLAMADA  = 'Pendiente realizar'
                          , TIPO_LLAMADA  = 'Crear evento'
                          , FECHA_CREACION  = '" . $rowEvento->FECHA_CREACION . "'
                          " . ($fechaEjecucion != "" ? ", FECHA_EJECUCION  = '" . $fechaEjecucion . "'" : "") . "
                          , PARAMETROS  = '" . $bd->escapeCondicional($data_json) . "'
                          , BAJA = 0";
        $bd->ExecSQL($sqlInsert);
        $idEventoPendienteTransmitir = $bd->IdAsignado();

        return $idEventoPendienteTransmitir;
    }

    function crear_evento_documento_pendiente_transmitir($idEvento, $tipoDocumento, $nombreDocumento)
    {
        global $administrador;
        global $bd;

        //BUSCAMOS EL EVENTO
        $rowEvento = $bd->VerReg("EVENTO", "ID_EVENTO", $idEvento);

        //LA FECHA DE LOS DOCUMENTOS , POSTERIOR SIEMPRE A LA CREACION DE EVENTO
        $fecha_actual_documentos = date("Y-m-d H:i:s", strtotime( (string)$rowEvento->FECHA_CREACION . "+ 1 seconds"));

        //OBTENEMOS LA EXTENSION
        $arrDoc           = explode('.', (string)$nombreDocumento);
        $extensionArchivo = $arrDoc[count( (array)$arrDoc) - 1];

        //INSERTAMOS EL DOCUMENTO
        $sqlInsert = "INSERT INTO EVENTO_DOCUMENTO SET
                                          ID_EVENTO = " . $rowEvento->ID_EVENTO . "
                                          , TIPO_DOCUMENTO  = '" . $bd->escapeCondicional($tipoDocumento) . "'
                                          , NOMBRE_DOCUMENTO  = '" . $bd->escapeCondicional($nombreDocumento) . "'
                                          , EXTENSION_DOCUMENTO  = '" . $bd->escapeCondicional($extensionArchivo) . "'
                                          , FECHA_CREACION  = '" . $fecha_actual_documentos . "'
                                          , BAJA = 0";
        $bd->ExecSQL($sqlInsert);
        $idEventoDocumento = $bd->IdAsignado();

        //GENERAMOS EL ARRAY DE DATOS
        //SE AÑADEN TODOS LOS DATOS MENOS EL DOCUMENTO
        $data = array(
            'documentId'   => "" . $idEventoDocumento . "",
            'eventId'      => $rowEvento->ID_EVENTO,
            'eventType'    => $rowEvento->TIPO_EVENTO,
            'userID'       => $administrador->ID_ADMINISTRADOR,
            'companyCode'  => $rowEvento->ROL_ADMINISTRADOR,
            'orderType'    => $rowEvento->TIPO_OBJETO,
            'orderCode'    => $rowEvento->ID_OBJETO,
            'creationDate' => $fecha_actual_documentos,
            'documentType' => $tipoDocumento,
            'DocumentName' => $nombreDocumento
        );

        //CONVERTIMOS A JSON
        $data_json = $this->convertir_json($data);

        //INSERTAMOS EL REGISTRO
        $sqlInsert = "INSERT INTO EVENTO_PENDIENTE_TRANSMITIR SET
                          ID_EVENTO = " . $rowEvento->ID_EVENTO . "
                          , ID_EVENTO_DOCUMENTO = '" . $idEventoDocumento . "'
                          , ESTADO_LLAMADA  = 'Pendiente realizar'
                          , TIPO_LLAMADA  = 'Crear documento'
                          , FECHA_CREACION  = '" . $fecha_actual_documentos . "'
                          , PARAMETROS  = '" . $bd->escapeCondicional($data_json) . "'
                          , BAJA = 0";
        $bd->ExecSQL($sqlInsert);
        $idEventoPendienteTransmitir = $bd->IdAsignado();

        return $idEventoPendienteTransmitir;
    }

    function eliminar_evento_pendiente_transmitir($idEvento)
    {
        global $bd;


        $fecha_actual = date("Y-m-d H:i:s");

        $sqlInsert = "INSERT INTO EVENTO_PENDIENTE_TRANSMITIR SET
                          ID_EVENTO = " . $idEvento . "
                          , ESTADO_LLAMADA  = 'Pendiente realizar'
                          , TIPO_LLAMADA  = 'Eliminar evento'
                          , FECHA_CREACION  = '" . $fecha_actual . "'
                          , BAJA = 0";
        $bd->ExecSQL($sqlInsert);
        $idEventoPendienteTransmitir = $bd->IdAsignado();

        return $idEventoPendienteTransmitir;
    }

    function eliminar_evento_documento_pendiente_transmitir($idEvento, $idEventoDocumento)
    {
        global $bd;

        $fecha_actual = date("Y-m-d H:i:s");

        $sqlInsert = "INSERT INTO EVENTO_PENDIENTE_TRANSMITIR SET
                          ID_EVENTO = " . $idEvento . "
                          , ID_EVENTO_DOCUMENTO = '" . $idEventoDocumento . "'
                          , ESTADO_LLAMADA  = 'Pendiente realizar'
                          , TIPO_LLAMADA  = 'Eliminar documento'
                          , FECHA_CREACION  = '" . $fecha_actual . "'
                          , BAJA = 0";
        $bd->ExecSQL($sqlInsert);
        $idEventoPendienteTransmitir = $bd->IdAsignado();

        return $idEventoPendienteTransmitir;
    }

    //FUNCION PARA COMPROBAR SI UN EVENTO BLOCKCHAIN CONTIENE DOCUMENTOS
    //DEVUELVE TRUE SI CONTIENE ALGUN DOCUMENTO Y FALSE EN CASO CONTRARIO

    function comprobar_evento_contiene_documentos($idEvento)
    {
        global $bd;

        //BUSCO LOS DOCUMENTOS PARA EL EVENTO
        $sqlEventoDocumento    = "SELECT *
                                        FROM EVENTO_DOCUMENTO
                                        WHERE ID_EVENTO = $idEvento AND BAJA=0";
        $resultEventoDocumento = $bd->ExecSQL($sqlEventoDocumento);

        if ($bd->NumRegs($resultEventoDocumento) > 0):
            return true;
        else:
            return false;
        endif;

    }

    //FUNCION PARA COMPROBAR SI UN EVENTO BLOCKCHAIN CONTIENE DOCUMENTO DE UN DETERMINADO TIPO
    //DEVUELVE TRUE SI CONTIENE ALGUN DOCUMENTO PARA ESE TIPO Y FALSE EN CASO CONTRARIO

    function comprobar_evento_contiene_tipo_documento($idEvento, $tipoDocumento)
    {
        global $bd;

        //BUSCO LOS DOCUMENTOS PARA EL EVENTO
        $sqlEventoDocumento    = "SELECT *
                                        FROM EVENTO_DOCUMENTO ED
                                        WHERE ID_EVENTO = $idEvento AND TIPO_DOCUMENTO = '$tipoDocumento' AND BAJA=0";
        $resultEventoDocumento = $bd->ExecSQL($sqlEventoDocumento);

        if ($bd->NumRegs($resultEventoDocumento) > 0):
            return true;
        else:
            return false;
        endif;

    }

    //FUNCION PARA CONVETIR JSON
    function convertir_json($data)
    {
        //PRIMERO, LE QUITAMOS LOS NULLS Y LOS CARACTERES ESPECIALES
        array_walk_recursive($data, "auxiliar::encode_before_json");

        //GENERAMOS EL JSON SIN ESCAPAR UTF8 PARA LUEGO PODER HACER DECODE
        $data_json = json_encode($data, JSON_UNESCAPED_UNICODE);

        //SE HACE DECODE (CHAIN GO ADMITE CARACTERES ESPECIALES)
        $data_json = mb_convert_encoding($data_json, 'ISO-8859-1','UTF-8');

        return $data_json;
    }

    public static function encode_before_json(&$item, $key)
    {
        $item = mb_convert_encoding((string)$item, 'UTF-8','ISO-8859-1');
    }

    public static function encode_before_json_formato_numerico(&$item, $key)
    {
        if (!(is_numeric($item))):
            $item = mb_convert_encoding((string)$item, 'UTF-8','ISO-8859-1');
        endif;
    }

    //FUNCION PARA HACER DECODE DEL JSON
    function deconvertir_json($data_json)
    {
        //DECODIFICAMOS EL JSON
        $data = json_decode((string) mb_convert_encoding( (string)$data_json,'UTF-8','ISO-8859-1'), JSON_UNESCAPED_UNICODE);

        //LE HACEMOS DECODE
        array_walk_recursive($data, "auxiliar::decode_post_json");

        return $data;
    }

    public static function decode_post_json(&$item, $key)
    {
        $item = mb_convert_encoding((string)$item, 'ISO-8859-1','UTF-8');
    }

    public function comprobarFecha0SQL($fecha)
    {
        if ($fecha == '0000-00-00'):
            return NULL;
        endif;
        return $fecha;
    }

    /**
     * @param $asuntoCorreo
     * @param $cuerpoCorreo
     * @param $fromEmail
     * @param $fromNombre
     * @param $emailUsuarios
     * @param int $prioridadMail
     * @param array $arrayAdjuntos[] =array('ruta_adjunto' => 'ruta/', 'nombre_adjunto.ext' 'nombre_adjunto' => 'nombre_adjunto.ext')
     * @param array $arrayFotos[0] = array('ruta_adjunto' => $path_raiz . "administrador/imagenes/" , 'nombre_adjunto' => 'acciona_impresos.jpg', 'nombre_imagen' => 'logo_acciona', 'encoding_imagen' => 'base64', 'tipo_imagen' => 'image/jpeg');
     */
    function enviarCorreoSistema($asuntoCorreo, $cuerpoCorreo, $fromEmail, $fromNombre, $emailUsuarios, $emailUsuariosOcultos = "", $prioridadMail = 0, $esHtml = true, $arrayAdjuntos = array(), $arrayImagenes = array())
    {
        //ARMAMOS EL MAIL
        $mail           = new PHPMailer();
        $mail->From     = $fromEmail;
        $mail->FromName = $fromNombre;
        $mail->Mailer   = "mail";

        //CONFIGURAMOS EL SMTP
        $mail->isSMTP();
        $mail->SMTPDebug = false; //3 si queremos ver el debug completo
        //$mail->Debugoutput = function($str, $level) { //file_put_contents('/var/www/html/mail/smtp.log', gmdate('Y-m-d H:i:s'). "\t$level\t$str\n", FILE_APPEND | LOCK_EX); };
        $mail->Host       = CORREO_HOST; //Set the SMTP server to send through
        $mail->SMTPAuth   = false; //Enable SMTP authentication
        $mail->Username   = CORREO_USER;
        $mail->Password   = CORREO_PASS;
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 25;

        $mail->Body    = $cuerpoCorreo;
        $mail->Subject = $asuntoCorreo;
        if ($esHtml == true):
            $mail->IsHTML(true);
        endif;

        //ADJUNTOS
        if (count( (array)$arrayAdjuntos) > 0):
            foreach ($arrayAdjuntos as $datosAdjunto):
                $mail->AddAttachment($datosAdjunto['ruta_adjunto'], $datosAdjunto['nombre_adjunto']);
            endforeach;
        endif;

        //IMAGENES
        if (count( (array)$arrayImagenes) > 0):
            foreach ($arrayImagenes as $datosAdjunto):
                $mail->AddEmbeddedImage($datosAdjunto['ruta_adjunto'], $datosAdjunto['nombre_imagen'], $datosAdjunto['nombre_adjunto'], $datosAdjunto['encoding_imagen'], $datosAdjunto['tipo_imagen']);
            endforeach;
        endif;

        //PRIORIDAD
        if ($prioridadMail == 1):
            $mail->Priority = 1;
        endif;

        //DESTINATARIOS
        $mail->ClearAllRecipients();

        //SMPT NO ADMITE MAILS SEPARADOS POR COMAS, ASI QUE LO DESGLOSAMOS
        if ($emailUsuarios != ""):
            $addresses = explode(',', (string)$emailUsuarios);
            foreach ($addresses as $address):
                $mail->AddAddress(trim( (string)$address));
            endforeach;
        endif;

        if ($emailUsuariosOcultos != ""):
            $addresses = explode(',', (string)$emailUsuariosOcultos);
            foreach ($addresses as $address):
                $mail->AddBCC(trim( (string)$address));
            endforeach;
        endif;

        $mail->Sender = $fromEmail;
        $mail->Send();
    }

    function eliminarFacturasNoComerciales($idOrdenTransporte)
    {
        global $bd;

        //SE DAN DE BAJA LAS FACTURAS PREVIAS
        $sqlFacturasNoComercialesOT = "SELECT DISTINCT ID_FACTURA_NO_COMERCIAL
                                       FROM FACTURA_NO_COMERCIAL
                                       WHERE ID_ORDEN_TRANSPORTE = $idOrdenTransporte AND BAJA = 0";
        $resultFacturasNoComercialesOT = $bd->ExecSQL($sqlFacturasNoComercialesOT);
        while ($rowFacturaNoComercialOT = $bd->SigReg($resultFacturasNoComercialesOT)):
            $sqlUpdateFNC = "UPDATE FACTURA_NO_COMERCIAL
                             SET BAJA = 1
                             WHERE ID_FACTURA_NO_COMERCIAL = $rowFacturaNoComercialOT->ID_FACTURA_NO_COMERCIAL";
            $bd->ExecSQL($sqlUpdateFNC);
            $sqlFNCM = "SELECT ID_FACTURA_NO_COMERCIAL_MOVIMIENTO
                        FROM FACTURA_NO_COMERCIAL_MOVIMIENTO
                        WHERE ID_FACTURA_NO_COMERCIAL = $rowFacturaNoComercialOT->ID_FACTURA_NO_COMERCIAL";
            $resultFNCM = $bd->ExecSQL($sqlFNCM);
            while($rowFNCM = $bd->SigReg($resultFNCM)):
                $sqlUpdateFNCM = "UPDATE FACTURA_NO_COMERCIAL_MOVIMIENTO
                                  SET BAJA = 1
                                  WHERE ID_FACTURA_NO_COMERCIAL_MOVIMIENTO = $rowFNCM->ID_FACTURA_NO_COMERCIAL_MOVIMIENTO";
                $bd->ExecSQL($sqlUpdateFNCM);
            endwhile;
        endwhile;
    }

    public function obtenerTokenAzure(){

        $url = "https://login.microsoftonline.com/" . AAD_ID_TENANT . "/oauth2/v2.0/token";

        $data = array(
            'client_id' => AAD_ID_CLIENT,
            'client_secret' => AAD_SECRET,
            'grant_type' => 'client_credentials',
            'scope' => 'https://graph.microsoft.com/.default'
        );

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL            => $url,
            CURLOPT_POST           => 1,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => "",
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => "POST",
            CURLOPT_POSTFIELDS     => http_build_query($data),
            CURLOPT_HTTPHEADER     => array(
                "Content-Type: application/x-www-form-urlencoded"
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        return json_decode((string) $response);

    }
    //MINIMO COMUN MULTIPLO
    function mcm($m,$n) {
        if ($m == 0 || $n == 0) return 0;

        $r = ($m * $n) / $this->gcd($m, $n);
        return abs( (float)$r);
    }

    //MAXIMO COMUN DIVISOR
    function gcd($a, $b) {
        while ($b != 0) {
            $t = $b;
            $b = $a % $b;
            $a = $t;
        }
        return $a;
    }

    //BUSCAR EL ARRAY QUE CONTIENE EL VALOR BUSCADO DE UN CAMPO
    function buscar_array_multi($nombre_campo,$valor_buscar,$array){
        foreach ($array as $key => $val){
            if($val["$nombre_campo"]=== $valor_buscar){
                return $key;
            }
        }
        return null;
    }

    function devuelveArrayLetrasColumnasExcel()
    {
        $arrayColumnasExcel = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ');
        return $arrayColumnasExcel;
    }


} // FIN DE LA CLASE auxiliar

?>