<?

class Calendario
{


    public static $arrDiasBBDD = array('1' => 'LUNES', '2' => 'MARTES', '3' => 'MIERCOLES', '4' => 'JUEVES', '5' => 'VIERNES', '6' => 'SABADOS', '0' => 'DOMINGOS');
    public static $arrDiasIngles = array('1' => 'Monday', '2' => 'Tuesday', '3' => 'Wednesday', '4' => 'Thursday', '5' => 'Friday', '6' => 'Saturday', '0' => 'Sunday');
    public static $arrMesesIngles = array('1' => 'january', '2' => 'february', '3' => 'march', '4' => 'april', '5' => 'may', '6' => 'june', '7' => 'july', '8' => 'august',
                                          '9' => 'september', '10' => 'october', '11' => 'november', '12' => 'december');
    public static $arrDiasSemana = array('1' => 'Lunes', '2' => 'Martes', '3' => 'Miercoles', '4' => 'Jueves', '5' => 'Viernes', '6' => 'Sabado', '0' => 'Domingo');

    /**
     * FUNCION PARA INSERTAR HORARIOS TANTO EN CALENDARIOS FESTIVOS COMO EN CALENDARIO FESTIVOS PLANTILLA
     * @param $idCalendario ID_CALENDARIO_FESTIVO O ID_CALENDARIO_PLANTILLA
     * @param $fechaInicio fecha inicio del rango a aplicar
     * @param $fechaFin fecha fin del rango a aplicar
     * @param $temporada temporada a aplicar (Invierno o Verano)
     * @param $tabla CALENDARIOS FESTIVOS COMO EN CALENDARIO FESTIVOS PLANTILLA
     */

    static function insertarHorariosRangoFechas($idCalendario, $fechaInicio, $fechaFin, $temporada, $tabla = "CALENDARIO_FESTIVOS")
    {
        global $bd, $html;
        //RECORREMOS LAS FECHAS SELECCIONADAS

        $arrDiasInsert = array('1' => '', '2' => '', '3' => '', '4' => '', '5' => '', '6' => '', '0' => '');
        //COMPROBAMOS SI ES HORARIO DE VERANO
        $esVerano = ($temporada == "Verano" ? 1 : 0);

        //FORMAMOS SQL PARA GUARDAR RANGO HORARIOS
        $sqlRangoHorarios = " INSERT INTO " . $tabla . "_HORARIO_RANGO_FECHAS SET
                              ID_" . $tabla . " = $idCalendario
                              , HORARIO_VERANO = $esVerano
                              , FECHA_INICIO = '$fechaInicio'
                              , FECHA_FIN = '$fechaFin' ";
        $insertarRango    = false;

        //GUARDAMOS HORARIO POR DIA EN EL CALENDARIO
        for ($fechaInsertar = $fechaInicio; $fechaInsertar <= $fechaFin; $fechaInsertar = date("Y-m-d", strtotime( (string)$fechaInsertar . "+ 1 days"))):

            //OBTENEMOS EL DIA DE LA SEMANA DE LA FECHA SELECCIONADA AL RECORRER (0-6)(domingo-lunes)
            $numDia = date('w', strtotime( (string)$fechaInsertar));
            //COMPROBAMOS SI SE HA RELLENADO EL HORARIO PARA ESO DIA EN ESA TEMPORADA
            $horaInicio  = $_POST["txHoraInicio" . $temporada . "_" . $numDia];
            $horaFin     = $_POST["txHoraFin" . $temporada . "_" . $numDia];
            $horaInicio2 = $_POST["txHoraInicio" . $temporada . "2_" . $numDia];
            $horaFin2    = $_POST["txHoraFin" . $temporada . "2_" . $numDia];
            if ($horaInicio != ""):
                $html->PagErrorCondicionado($horaFin, "==", "", "RellenarHoraFin");
//                $horaInicio = $_POST["txHoraInicio" . $temporada . "_" . $numDia];
//                $horaFin = $_POST["txHoraFin" . $temporada . "_" . $numDia];
//                $horaInicio2 = $_POST["txHoraInicio" . $temporada . "2_" . $numDia];
//                $horaFin2 = $_POST["txHoraFin" . $temporada . "2_" . $numDia];
                $sqlHorarioDia    = "SELECT * FROM " . $tabla . "_HORARIO
                                WHERE ID_" . $tabla . " = $idCalendario AND FECHA  = '" . $fechaInsertar . "'";
                $resultHorarioDia = $bd->ExecSQL($sqlHorarioDia);
                if ($bd->NumRegs($resultHorarioDia) > 0):
                    //GRABO EL DIA EN LA BASE DE DATOS
                    $sqlInsert = "UPDATE  " . $tabla . "_HORARIO SET
                          ANOTACIONES = '" . (($esVerano == 1) ? "Horario Especial" : "Horario") . "'
                          , HORA_INICIO = '" . $horaInicio . "'
                          , HORA_FIN = '" . $horaFin . "'
                          , HORA_INICIO2 = " . ($horaInicio2 != "" ? "'" . $horaInicio2 . "'" : "NULL") . "
                          , HORA_FIN2 = " . ($horaFin2 != "" ? "'" . $horaFin2 . "'" : "NULL") . "
                          , HORARIO_VERANO = $esVerano
                          , HORARIO_ESPECIAL = 0
                    WHERE ID_" . $tabla . " = $idCalendario  AND FECHA  = '" . $fechaInsertar . "'";

                else:
                    //GRABO EL DIA EN LA BASE DE DATOS
                    $sqlInsert = "INSERT INTO " . $tabla . "_HORARIO SET
                              ID_" . $tabla . " = $idCalendario
                              ,ANOTACIONES = '" . (($esVerano == 1) ? "Horario Especial" : "Horario") . "'
                              , FECHA = '" . $fechaInsertar . "'
                              , HORA_INICIO = '" . $horaInicio . "'
                              , HORA_FIN = '" . $horaFin . "'
                              , HORA_INICIO2 = " . ($horaInicio2 != "" ? "'" . $horaInicio2 . "'" : "NULL") . "
                              , HORA_FIN2 = " . ($horaFin2 != "" ? "'" . $horaFin2 . "'" : "NULL") . "
                              , HORARIO_VERANO = $esVerano";
                endif;
                $bd->ExecSQL($sqlInsert);
                if ($arrDiasInsert[$numDia] == ''):
                    $sqlRangoHorarios .= ", " . self::$arrDiasBBDD[$numDia] . "_HORA_INICIO = " . ($horaInicio != "" ? "'" . $horaInicio . "'" : "NULL") . "
                                        , " . self::$arrDiasBBDD[$numDia] . "_HORA_FIN = " . ($horaFin != "" ? "'" . $horaFin . "'" : "NULL") . "
                                        , " . self::$arrDiasBBDD[$numDia] . "_HORA_INICIO2 = " . ($horaInicio2 != "" ? "'" . $horaInicio2 . "'" : "NULL") . "
                                        , " . self::$arrDiasBBDD[$numDia] . "_HORA_FIN2 = " . ($horaFin2 != "" ? "'" . $horaFin2 . "'" : "NULL");
                    $arrDiasInsert[$numDia] = 1;
                    $insertarRango          = true;
                endif;
            endif;
        endfor; //END RECORRER FECHAS
        if ($insertarRango):
            $bd->ExecSQL($sqlRangoHorarios);
        endif;

        //GUARDAMOS HORARIO POR RANGO DE FECHAS
    }


    static function setNoLaborablePorDiaPorRangoFechas($idCalendario, $fechaInico, $fechaFin, $numDia, $esNoLaborable, $tabla = "CALENDARIO_FESTIVOS")
    {
        global $bd;
        $fechaFin = strtotime( (string)$fechaFin);

        //RECORREMOS LAS FECHAS DEL DIA DEL AÑO SELECCIONADO
        for ($i = strtotime( (string)self::$arrDiasIngles[$numDia], strtotime( (string)$fechaInico)); $i <= $fechaFin; $i = strtotime( (string)'+1 week', $i)):
            $fecha                 = date('Y-m-d', $i);
            $sqlCalendarioLinea    = "SELECT * FROM " . $tabla . "_LINEA
                                    WHERE ID_" . $tabla . " = $idCalendario  AND FECHA_INICIO  = '" . $fecha . "' AND FECHA_FIN  = '" . $fecha . "'";
            $resultCalendarioLinea = $bd->ExecSQL($sqlCalendarioLinea);

            //SI NO ESTA YA COMO NO LABORABLE, LO GUARDAMOS
            if ($esNoLaborable == 1):
                if ($bd->NumRegs($resultCalendarioLinea) == 0):
                    $sqlInsert = "INSERT INTO " . $tabla . "_LINEA SET
                                  ID_" . $tabla . " = $idCalendario
                                  , FECHA_INICIO = '" . $fecha . "'
                                  , FECHA_FIN = '" . $fecha . "'
                                  , FESTIVO = 0
                                  , TIPO_FESTIVO = 'No Laborable'
                                  , ANOTACIONES = 'No Laborable'";
                else:
                    $rowCalendarioLinea = $bd->SigReg($resultCalendarioLinea);
                    $sqlInsert          = "UPDATE " . $tabla . "_LINEA SET

                                   FESTIVO = 0
                                  , TIPO_FESTIVO = 'No Laborable'
                                  , ANOTACIONES = 'No Laborable'
                                  WHERE ID_" . $tabla . "_LINEA = " . $rowCalendarioLinea->{"ID_" . $tabla . "_LINEA"};
                endif;
                $bd->ExecSQL($sqlInsert);
            endif;
            //SI CAMBIA A LABORABLE
            if ($esNoLaborable == 0):
                if ($bd->NumRegs($resultCalendarioLinea) > 0):
                    $sqlInsert = "DELETE FROM " . $tabla . "_LINEA WHERE
                                  ID_" . $tabla . " = $idCalendario
                                  AND FECHA_INICIO = '" . $fecha . "'
                                  AND FECHA_FIN = '" . $fecha . "'
                                  AND FESTIVO = 0
                                  AND TIPO_FESTIVO = 'No Laborable'";
                    $bd->ExecSQL($sqlInsert);
                endif;
            endif;
        endfor;
        $sqlUpdate = "UPDATE $tabla SET
                      NO_LABORABLE_" . self::$arrDiasBBDD[$numDia] . " = $esNoLaborable
                      WHERE ID_" . $tabla . " = $idCalendario";
        $bd->ExecSQL($sqlUpdate);


    }


    /**
     * FUNCION QUE DEVUELVE UN ARRAY DE TOTALES
     * @param $idCalendario
     * @param $tabla DISTINGUIR ENTRE CALENDARIO Y CALENDARIO PLANTILLA (CALENDARIO_FESTIVOS) (CALENDARIO_PLANTILLA_FESTIVOS)
     */
    static function getTotales($idCalendario, $tabla = "CALENDARIO_FESTIVOS")
    {
        $arrDevolver                         = array();
        $arrDevolver["TOTAL_LABORABLES"]     = self::getTotalLaborables($idCalendario, $tabla);
        $arrDevolver["TOTAL_HORAS_APERTURA"] = self::getNumHorasApertura($idCalendario, $tabla);
        $arrDevolver["TOTAL_NO_LABORABLES"]  = self::getNumTotalNoLaborables($idCalendario, $tabla);
        $arrDevolver["TOTAL_FESTIVOS"]       = self::getNumTotalFestivos($idCalendario, $tabla);

        return $arrDevolver;
    }

    /**
     * FUNCION QUE DEVUELVE EL NUMERO TOTAL DE DIAS FESTIVOS DE UN CALENDARIO
     * @param $idCalendario
     * @param $tabla DISTINGUIR ENTRE CALENDARIO Y CALENDARIO PLANTILLA (CALENDARIO_FESTIVOS) (CALENDARIO_PLANTILLA_FESTIVOS)
     */
    static function getNumTotalFestivos($idCalendario, $tabla = "CALENDARIO_FESTIVOS")
    {
        global $bd;
        $sqlTotalFetivos    = "SELECT SUM((DATEDIFF( FECHA_FIN, FECHA_INICIO)+1) ) AS TOTAL FROM " . $tabla . "_LINEA WHERE FESTIVO = 1 AND ID_" . $tabla . " = $idCalendario";
        $resulTotalFestivos = $bd->ExecSQL($sqlTotalFetivos);
        $total              = $bd->SigReg($resulTotalFestivos)->TOTAL;

        return ($total > 0 ? $total : 0);
    }

    /**
     * FUNCION QUE DEVUELVE EL NUMERO TOTAL DE DIAS NO LABORABLES DE UN CALENDARIO
     * @param $idCalendario
     * @param $tabla DISTINGUIR ENTRE CALENDARIO Y CALENDARIO PLANTILLA (CALENDARIO_FESTIVOS) (CALENDARIO_PLANTILLA_FESTIVOS)
     */
    static function getNumTotalNoLaborables($idCalendario, $tabla = "CALENDARIO_FESTIVOS")
    {
        global $bd;
        $sqlTotalNoLaborables   = "SELECT SUM((DATEDIFF( FECHA_FIN, FECHA_INICIO)+1) ) AS TOTAL FROM " . $tabla . "_LINEA WHERE TIPO_FESTIVO = 'No Laborable' AND ID_" . $tabla . " = $idCalendario";
        $resulTotalNoLaborables = $bd->ExecSQL($sqlTotalNoLaborables);
        $total                  = $bd->SigReg($resulTotalNoLaborables)->TOTAL;

        return ($total > 0 ? $total : 0);
    }

    /**
     * FUNCION QUE DEVUELVE EL NUMERO TOTAL DE DIAS LABORABLES DE UN CALENDARIO
     * @param $idCalendario
     * @param $tabla DISTINGUIR ENTRE CALENDARIO Y CALENDARIO PLANTILLA (CALENDARIO_FESTIVOS) (CALENDARIO_PLANTILLA_FESTIVOS)
     */
    static function getTotalLaborables($idCalendario, $tabla = "CALENDARIO_FESTIVOS")
    {
        global $bd, $auxiliar;

        //OBTENGO EL CALENDARIO
        $rowCalendario = $bd->VerReg($tabla, "ID_" . $tabla, $idCalendario, "No");

        return ($auxiliar->getDiasAno($rowCalendario->YEAR) - (self::getNumTotalFestivos($idCalendario, $tabla) + self::getNumTotalNoLaborables($idCalendario, $tabla)));
    }

    /**
     * DEVUELVE EL NUMERO DE HORAS DE APERTURA DE UN CALENDARIO
     * @param $idCalendario
     * @param $tabla DISTINGUIR ENTRE CALENDARIO Y CALENDARIO PLANTILLA (CALENDARIO_FESTIVOS) (CALENDARIO_PLANTILLA_FESTIVOS)
     */
    static function getNumHorasApertura($idCalendario, $tabla = "CALENDARIO_FESTIVOS")
    {
        global $bd, $auxiliar;

        //OBTENGO EL CALENDARIO
        $rowCalendario = $bd->VerReg($tabla, "ID_" . $tabla, $idCalendario, "No");

        //RECORRO TODOS LOS DIAS DEL AÑO, 1º COMPRUEBO QUE NO TIENE DIA FESTIVO, 2 SUMO HORAS DEL DIA EN LA TABLA DE HORARIOS
        //GUARDAMOS HORARIO POR DIA EN EL CALENDARIO
        $fechaInicio = date( (string)$rowCalendario->YEAR . "-01-01"); //FORMAYO Y-m-d
        $fechaFin    = date( (string)$rowCalendario->YEAR . "-12-31"); //FORMAYO Y-m-d
        $totalHoras  = "00:00:00";
        for ($fechaRecorrer = $fechaInicio; $fechaRecorrer <= $fechaFin; $fechaRecorrer = date("Y-m-d", strtotime( (string)$fechaRecorrer . "+ 1 days"))):

            $sqlCalendarioLinea = "SELECT * FROM " . $tabla . "_LINEA
                                    WHERE '$fechaRecorrer' BETWEEN FECHA_INICIO AND FECHA_FIN AND ID_" . $tabla . " = $idCalendario ";

            //SI NO TIENE ASOCIADO FESTIVO O NO LABORABLE CONTAMOS HORAS
            if ($bd->NumRegs($bd->ExecSQL($sqlCalendarioLinea)) == 0):
                $sqlHorarioCalendario    = "SELECT TIMEDIFF(HORA_FIN, HORA_INICIO) AS NUM_HORAS_RANGO1, IF(TIMEDIFF(HORA_FIN2, HORA_INICIO2) IS NULL, '00:00:00',TIMEDIFF(HORA_FIN2, HORA_INICIO2)) AS NUM_HORAS_RANGO2 FROM " . $tabla . "_HORARIO WHERE ID_" . $tabla . " = $idCalendario AND FECHA = '$fechaRecorrer' ";
                $resultHorarioCalendario = $bd->ExecSQL($sqlHorarioCalendario, "No");
                if ($bd->NumRegs($resultHorarioCalendario) > 0):

                    $rowHorarioCalendario = $bd->SigReg($resultHorarioCalendario);
                    $totalHoras           = $auxiliar->suma_horas($totalHoras, $rowHorarioCalendario->NUM_HORAS_RANGO1);
                    $totalHoras           = $auxiliar->suma_horas($totalHoras, $rowHorarioCalendario->NUM_HORAS_RANGO2);
                endif;
            endif;
        endfor;

        return $totalHoras;
    }

    /**
     * DEVUELVE EL ID DEL CALENDARIO CLONADO
     * @param $idCalendario
     * @param $idCentroFisico
     * @param $tabla DISTINGUIR ENTRE CALENDARIO Y CALENDARIO PLANTILLA (CALENDARIO_FESTIVOS) (CALENDARIO_PLANTILLA_FESTIVOS)
     */
    static function clonarCalendario($idCalendario, $idCentroFisico, $tabla = "CALENDARIO_FESTIVOS", $nombre = "")
    {
        global $bd, $administrador;
        $rowCalendario = $bd->VerReg($tabla, "ID_" . $tabla, $idCalendario, "No");

        //GRABO EL CALENDARIO EN BASE DE DATOS
        if ($tabla == "CALENDARIO_FESTIVOS"):
            $descripcion = "Calendario";
            $sqlInsert   = "INSERT INTO " . $tabla . " SET
                        YEAR = '$rowCalendario->YEAR'
                       , TIPO_CALENDARIO = 'Centro Fisico'
                       , ID_CENTRO_FISICO = $idCentroFisico
                       , NO_LABORABLE_LUNES = $rowCalendario->NO_LABORABLE_LUNES
                       , NO_LABORABLE_MARTES = $rowCalendario->NO_LABORABLE_MARTES
                       , NO_LABORABLE_MIERCOLES = $rowCalendario->NO_LABORABLE_MIERCOLES
                       , NO_LABORABLE_JUEVES = $rowCalendario->NO_LABORABLE_JUEVES
                       , NO_LABORABLE_VIERNES = $rowCalendario->NO_LABORABLE_VIERNES
                       , NO_LABORABLE_SABADOS = $rowCalendario->NO_LABORABLE_SABADOS
                       , NO_LABORABLE_DOMINGOS = $rowCalendario->NO_LABORABLE_DOMINGOS";
        elseif ($tabla == "CALENDARIO_PLANTILLA_FESTIVOS"):
            $descripcion = "Plantilla Calendario";
            $sqlInsert   = "INSERT INTO " . $tabla . " SET
                    YEAR = '$rowCalendario->YEAR'
                   , NOMBRE = '" . (($nombre == "") ? $rowCalendario->NOMBRE . "_copia" : $nombre) . "'
                   , NO_LABORABLE_LUNES = $rowCalendario->NO_LABORABLE_LUNES
                   , NO_LABORABLE_MARTES = $rowCalendario->NO_LABORABLE_MARTES
                   , NO_LABORABLE_MIERCOLES = $rowCalendario->NO_LABORABLE_MIERCOLES
                   , NO_LABORABLE_JUEVES = $rowCalendario->NO_LABORABLE_JUEVES
                   , NO_LABORABLE_VIERNES = $rowCalendario->NO_LABORABLE_VIERNES
                   , NO_LABORABLE_SABADOS = $rowCalendario->NO_LABORABLE_SABADOS
                   , NO_LABORABLE_DOMINGOS = $rowCalendario->NO_LABORABLE_DOMINGOS";
        endif;
        $bd->ExecSQL($sqlInsert);
        //RECUPERO EL ID DEL CALENDARIO RECIEN CREADA
        $idCalendarioNuevo = $bd->IdAsignado();

        // LOG MOVIMIENTOS
        $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, 'Creacion', "Maestro", $idCalendarioNuevo, $descripcion, $tabla);


        //GRABO RANGO DE FECHAS
        $sqlCalendarioRangos = "SELECT CFR.*
                    FROM " . $tabla . "_HORARIO_RANGO_FECHAS CFR
                    WHERE CFR.ID_" . $tabla . " = $idCalendario ";

        $resultCalendarioRangos = $bd->ExecSQL($sqlCalendarioRangos, "No");
        while ($rowCalendarioRango = $bd->SigReg($resultCalendarioRangos)):
            //FORMAMOS SQL PARA GUARDAR RANGO HORARIOS
            $sqlRangoHorarios = " INSERT INTO " . $tabla . "_HORARIO_RANGO_FECHAS SET
                              ID_" . $tabla . " = $idCalendarioNuevo
                              , HORARIO_VERANO = $rowCalendarioRango->HORARIO_VERANO
                              , FECHA_INICIO = '$rowCalendarioRango->FECHA_INICIO'
                              , FECHA_FIN = '$rowCalendarioRango->FECHA_FIN' ";

            //RECORREMOS DIAS PARA CONSTRUIR CONSULTA
            for ($i = 0; $i <= 6; $i++):
                $nombreDiaInicio  = self::$arrDiasBBDD[$i] . "_HORA_INICIO";
                $nombreDiaInicio2 = self::$arrDiasBBDD[$i] . "_HORA_INICIO2";
                $nombreDiaFin     = self::$arrDiasBBDD[$i] . "_HORA_FIN";
                $nombreDiaFin2    = self::$arrDiasBBDD[$i] . "_HORA_FIN2";
                $sqlRangoHorarios .= ", " . $nombreDiaInicio . " = " . ($rowCalendarioRango->$nombreDiaInicio != "" ? "'" . $rowCalendarioRango->$nombreDiaInicio . "'" : "NULL") . "
                                    , " . $nombreDiaFin . " = " . ($rowCalendarioRango->$nombreDiaFin != "" ? "'" . $rowCalendarioRango->$nombreDiaFin . "'" : "NULL") . "
                                    , " . $nombreDiaInicio2 . " = " . ($rowCalendarioRango->$nombreDiaInicio2 != "" ? "'" . $rowCalendarioRango->$nombreDiaInicio2 . "'" : "NULL") . "
                                    , " . $nombreDiaFin2 . " = " . ($rowCalendarioRango->$nombreDiaFin2 != "" ? "'" . $rowCalendarioRango->$nombreDiaFin2 . "'" : "NULL");
            endfor;
            $bd->ExecSQL($sqlRangoHorarios);

        endwhile;

        //GRABO EVENTOS

        $sqlCalendarioEventos = "SELECT CFL.*
                    FROM " . $tabla . "_LINEA CFL
                    WHERE CFL.ID_" . $tabla . " = $idCalendario ";

        $resultCalendarioEventos = $bd->ExecSQL($sqlCalendarioEventos, "No");
        while ($rowCalendarioEvento = $bd->SigReg($resultCalendarioEventos)):
            //FORMAMOS SQL PARA GUARDAR RANGO HORARIOS
            $sqlInsert = "INSERT INTO " . $tabla . "_LINEA SET
                                      ID_" . $tabla . " = $idCalendarioNuevo
                                  , FECHA_INICIO = '" . $rowCalendarioEvento->FECHA_INICIO . "'
                                  , FECHA_FIN = '" . $rowCalendarioEvento->FECHA_FIN . "'
                                  , FESTIVO = $rowCalendarioEvento->FESTIVO
                                  , TIPO_FESTIVO = '" . $rowCalendarioEvento->TIPO_FESTIVO . "'
                                  , ANOTACIONES = '" . $bd->escapeCondicional($rowCalendarioEvento->ANOTACIONES) . "'";
            $bd->ExecSQL($sqlInsert);

        endwhile;


        //GRABO HORARIOS
        $sqlCalendarioHorarios = "SELECT CFH.*
                    FROM " . $tabla . "_HORARIO CFH
                    WHERE CFH.ID_" . $tabla . " = $idCalendario ";

        $resultCalendarioHorarios = $bd->ExecSQL($sqlCalendarioHorarios, "No");
        while ($rowCalendarioHorario = $bd->SigReg($resultCalendarioHorarios)):
            //FORMAMOS SQL PARA GUARDAR RANGO HORARIOS
            //GRABO EL DIA EN LA BASE DE DATOS
            $sqlInsert = "INSERT INTO " . $tabla . "_HORARIO SET
                              ID_" . $tabla . " = $idCalendarioNuevo
                              ,ANOTACIONES = '" . $rowCalendarioHorario->ANOTACIONES . "'
                              , FECHA = '" . $rowCalendarioHorario->FECHA . "'
                              , HORA_INICIO = '" . $rowCalendarioHorario->HORA_INICIO . "'
                              , HORA_FIN = '" . $rowCalendarioHorario->HORA_FIN . "'
                              , HORA_INICIO2 = " . ($rowCalendarioHorario->HORA_INICIO2 != "" ? "'" . $rowCalendarioHorario->HORA_INICIO2 . "'" : "NULL") . "
                              , HORA_FIN2 = " . ($rowCalendarioHorario->HORA_FIN2 != "" ? "'" . $rowCalendarioHorario->HORA_FIN2 . "'" : "NULL") . "
                              , HORARIO_ESPECIAL = $rowCalendarioHorario->HORARIO_ESPECIAL
                              , HORARIO_VERANO = $rowCalendarioHorario->HORARIO_VERANO";
            $bd->ExecSQL($sqlInsert);

        endwhile;

        return $idCalendarioNuevo;

    }

    /**
     * DEVUELVE EL ID DEL CALENDARIO MODIFICADO
     * CLONAR MODIFICAR UN CALENDARIO POR OTRO MCALENDARIO
     * @param $idCalendario
     * @param $idCalendarioDestino
     * @param $tabla DISTINGUIR ENTRE CALENDARIO Y CALENDARIO PLANTILLA (CALENDARIO_FESTIVOS) (CALENDARIO_PLANTILLA_FESTIVOS)
     *
     */
    static function clonarCalendarioEnCalendario($idCalendario, $idCalendarioDestino, $tabla = "CALENDARIO_FESTIVOS")
    {
        global $bd, $administrador;
        $rowCalendario        = $bd->VerReg($tabla, "ID_" . $tabla, $idCalendario, "No");
        $rowCalendarioDestino = $bd->VerReg("CALENDARIO_FESTIVOS", "ID_CALENDARIO_FESTIVOS", $idCalendarioDestino, "No");


        //GRABO EL CALENDARIO EN BASE DE DATOS
        $sqlInsert = "UPDATE CALENDARIO_FESTIVOS SET
                    YEAR = '$rowCalendario->YEAR'
                   , NO_LABORABLE_LUNES = $rowCalendario->NO_LABORABLE_LUNES
                   , NO_LABORABLE_MARTES = $rowCalendario->NO_LABORABLE_MARTES
                   , NO_LABORABLE_MIERCOLES = $rowCalendario->NO_LABORABLE_MIERCOLES
                   , NO_LABORABLE_JUEVES = $rowCalendario->NO_LABORABLE_JUEVES
                   , NO_LABORABLE_VIERNES = $rowCalendario->NO_LABORABLE_VIERNES
                   , NO_LABORABLE_SABADOS = $rowCalendario->NO_LABORABLE_SABADOS
                   , NO_LABORABLE_DOMINGOS = $rowCalendario->NO_LABORABLE_DOMINGOS
                   WHERE ID_CALENDARIO_FESTIVOS = $rowCalendarioDestino->ID_CALENDARIO_FESTIVOS";

        $bd->ExecSQL($sqlInsert);
        //RECUPERO EL ID DEL CALENDARIO RECIEN CREADA
        $rowCalendarioActualizado = $bd->VerReg("CALENDARIO_FESTIVOS", "ID_CALENDARIO_FESTIVOS", $rowCalendarioDestino->ID_CALENDARIO_FESTIVOS);

        // LOG MOVIMIENTOS
        $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, 'Modificación', "Maestro", $rowCalendarioDestino->ID_CALENDARIO_FESTIVOS, "Modificacion Calendario al clonar", "CALENDARIO_FESTIVOS", $rowCalendarioDestino, $rowCalendarioActualizado);


        //BORRO POSIBLES REGISTROS ANTERIORES
        $sqlDelete = "DELETE FROM CALENDARIO_FESTIVOS_HORARIO_RANGO_FECHAS
                          WHERE ID_CALENDARIO_FESTIVOS = $rowCalendarioDestino->ID_CALENDARIO_FESTIVOS";
        $bd->ExecSQL($sqlDelete);

        //GRABO RANGO DE FECHAS
        $sqlCalendarioRangos = "SELECT CFR.*
                    FROM " . $tabla . "_HORARIO_RANGO_FECHAS CFR
                    WHERE CFR.ID_" . $tabla . " = $idCalendario ";

        $resultCalendarioRangos = $bd->ExecSQL($sqlCalendarioRangos, "No");
        while ($rowCalendarioRango = $bd->SigReg($resultCalendarioRangos)):
            //FORMAMOS SQL PARA GUARDAR RANGO HORARIOS
            $sqlRangoHorarios = " INSERT INTO CALENDARIO_FESTIVOS_HORARIO_RANGO_FECHAS SET
                              ID_CALENDARIO_FESTIVOS = $rowCalendarioDestino->ID_CALENDARIO_FESTIVOS
                              , HORARIO_VERANO = $rowCalendarioRango->HORARIO_VERANO
                              , FECHA_INICIO = '$rowCalendarioRango->FECHA_INICIO'
                              , FECHA_FIN = '$rowCalendarioRango->FECHA_FIN' ";

            //RECORREMOS DIAS PARA CONSTRUIR CONSULTA
            for ($i = 0; $i <= 6; $i++):
                $nombreDiaInicio  = self::$arrDiasBBDD[$i] . "_HORA_INICIO";
                $nombreDiaInicio2 = self::$arrDiasBBDD[$i] . "_HORA_INICIO2";
                $nombreDiaFin     = self::$arrDiasBBDD[$i] . "_HORA_FIN";
                $nombreDiaFin2    = self::$arrDiasBBDD[$i] . "_HORA_FIN2";
                $sqlRangoHorarios .= ", " . $nombreDiaInicio . " = " . ($rowCalendarioRango->$nombreDiaInicio != "" ? "'" . $rowCalendarioRango->$nombreDiaInicio . "'" : "NULL") . "
                                    , " . $nombreDiaFin . " = " . ($rowCalendarioRango->$nombreDiaFin != "" ? "'" . $rowCalendarioRango->$nombreDiaFin . "'" : "NULL") . "
                                    , " . $nombreDiaInicio2 . " = " . ($rowCalendarioRango->$nombreDiaInicio2 != "" ? "'" . $rowCalendarioRango->$nombreDiaInicio2 . "'" : "NULL") . "
                                    , " . $nombreDiaFin2 . " = " . ($rowCalendarioRango->$nombreDiaFin2 != "" ? "'" . $rowCalendarioRango->$nombreDiaFin2 . "'" : "NULL");
            endfor;
            $bd->ExecSQL($sqlRangoHorarios);

        endwhile;

        //BORRO POSIBLES REGISTROS ANTERIORES
        $sqlDelete = "DELETE FROM CALENDARIO_FESTIVOS_LINEA
                          WHERE ID_CALENDARIO_FESTIVOS = $rowCalendarioDestino->ID_CALENDARIO_FESTIVOS";
        $bd->ExecSQL($sqlDelete);

        //GRABO EVENTOS
        $sqlCalendarioEventos = "SELECT CFL.*
                    FROM " . $tabla . "_LINEA CFL
                    WHERE CFL.ID_" . $tabla . " = $idCalendario ";

        $resultCalendarioEventos = $bd->ExecSQL($sqlCalendarioEventos, "No");
        while ($rowCalendarioEvento = $bd->SigReg($resultCalendarioEventos)):
            //FORMAMOS SQL PARA GUARDAR RANGO HORARIOS
            $sqlInsert = "INSERT INTO CALENDARIO_FESTIVOS_LINEA SET
                                  ID_CALENDARIO_FESTIVOS = $rowCalendarioDestino->ID_CALENDARIO_FESTIVOS
                                  , FECHA_INICIO = '" . $rowCalendarioEvento->FECHA_INICIO . "'
                                  , FECHA_FIN = '" . $rowCalendarioEvento->FECHA_FIN . "'
                                  , FESTIVO = $rowCalendarioEvento->FESTIVO
                                  , TIPO_FESTIVO = '" . $rowCalendarioEvento->TIPO_FESTIVO . "'
                                  , ANOTACIONES = '" . $rowCalendarioEvento->ANOTACIONES . "'";
            $bd->ExecSQL($sqlInsert);

        endwhile;

        //BORRO POSIBLES REGISTROS ANTERIORES
        $sqlDelete = "DELETE FROM CALENDARIO_FESTIVOS_HORARIO
                          WHERE ID_CALENDARIO_FESTIVOS = $rowCalendarioDestino->ID_CALENDARIO_FESTIVOS";
        $bd->ExecSQL($sqlDelete);

        //GRABO HORARIOS
        $sqlCalendarioHorarios = "SELECT CFH.*
                    FROM " . $tabla . "_HORARIO CFH
                    WHERE CFH.ID_" . $tabla . " = $idCalendario ";

        $resultCalendarioHorarios = $bd->ExecSQL($sqlCalendarioHorarios, "No");
        while ($rowCalendarioHorario = $bd->SigReg($resultCalendarioHorarios)):
            //FORMAMOS SQL PARA GUARDAR RANGO HORARIOS
            //GRABO EL DIA EN LA BASE DE DATOS
            $sqlInsert = "INSERT INTO CALENDARIO_FESTIVOS_HORARIO SET
                              ID_CALENDARIO_FESTIVOS = $rowCalendarioDestino->ID_CALENDARIO_FESTIVOS
                              ,ANOTACIONES = '" . $rowCalendarioHorario->ANOTACIONES . "'
                              , FECHA = '" . $rowCalendarioHorario->FECHA . "'
                              , HORA_INICIO = '" . $rowCalendarioHorario->HORA_INICIO . "'
                              , HORA_FIN = '" . $rowCalendarioHorario->HORA_FIN . "'
                              , HORA_INICIO2 = " . ($rowCalendarioHorario->HORA_INICIO2 != "" ? "'" . $rowCalendarioHorario->HORA_INICIO2 . "'" : "NULL") . "
                              , HORA_FIN2 = " . ($rowCalendarioHorario->HORA_FIN2 != "" ? "'" . $rowCalendarioHorario->HORA_FIN2 . "'" : "NULL") . "
                              , HORARIO_ESPECIAL = $rowCalendarioHorario->HORARIO_ESPECIAL
                              , HORARIO_VERANO = $rowCalendarioHorario->HORARIO_VERANO";
            $bd->ExecSQL($sqlInsert);

        endwhile;

        return $rowCalendarioDestino->ID_CALENDARIO_FESTIVOS;

    }


    /**
     * DEVUELVE EL ID DEL CALENDARIO CREADO
     * @param $idCalendario
     * @param $idCentroFisico
     * @param $tabla DISTINGUIR ENTRE CALENDARIO Y CALENDARIO PLANTILLA (CALENDARIO_FESTIVOS) (CALENDARIO_PLANTILLA_FESTIVOS)
     */
    static function crearCalendarioAPartirDePlantilla($idPlantillaCalendario, $idElemento, $tipo)
    {
        global $bd, $administrador;

        //DEPENDIENDO DEL TIPO GUARDAREMOS CF O PAIS
        if ($tipo == "Centro Fisico"):
            $sqlElemento = ", ID_CENTRO_FISICO = $idElemento ";
        elseif ($tipo == "Nacional"):
            $sqlElemento = ", ID_PAIS = $idElemento ";
        endif;
        //OBTENGO LA PLANTILLA
        $rowPlantillaCalendario = $bd->VerReg("CALENDARIO_PLANTILLA_FESTIVOS", "ID_CALENDARIO_PLANTILLA_FESTIVOS", $idPlantillaCalendario, "No");

        //CREO CALENDARIO A PARTIR DE PLANTILLA
        $sqlInsert = "INSERT INTO CALENDARIO_FESTIVOS SET
                    YEAR = '$rowPlantillaCalendario->YEAR'
                   , TIPO_CALENDARIO = '$tipo'
                   $sqlElemento
                   , NO_LABORABLE_LUNES = $rowPlantillaCalendario->NO_LABORABLE_LUNES
                   , NO_LABORABLE_MARTES = $rowPlantillaCalendario->NO_LABORABLE_MARTES
                   , NO_LABORABLE_MIERCOLES = $rowPlantillaCalendario->NO_LABORABLE_MIERCOLES
                   , NO_LABORABLE_JUEVES = $rowPlantillaCalendario->NO_LABORABLE_JUEVES
                   , NO_LABORABLE_VIERNES = $rowPlantillaCalendario->NO_LABORABLE_VIERNES
                   , NO_LABORABLE_SABADOS = $rowPlantillaCalendario->NO_LABORABLE_SABADOS
                   , NO_LABORABLE_DOMINGOS = $rowPlantillaCalendario->NO_LABORABLE_DOMINGOS";

        $bd->ExecSQL($sqlInsert);
        //RECUPERO EL ID DEL CALENDARIO RECIEN CREADA
        $idCalendarioNuevo = $bd->IdAsignado();

        // LOG MOVIMIENTOS
        $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, 'Creacion', "Maestro", $idCalendarioNuevo, "Calendario", "CALENDARIO_FESTIVOS");


        //OBTENGO EL RANGO DE FECHAS DE LA PLANTILLA
        $sqlCalendarioRangos = "SELECT CFR.*
                    FROM CALENDARIO_PLANTILLA_FESTIVOS_HORARIO_RANGO_FECHAS CFR
                    WHERE CFR.ID_CALENDARIO_PLANTILLA_FESTIVOS = $idPlantillaCalendario ";

        $resultCalendarioRangos = $bd->ExecSQL($sqlCalendarioRangos, "No");
        //COPIO RANGOS AL NUEVO  CALENDARIO
        while ($rowCalendarioRango = $bd->SigReg($resultCalendarioRangos)):
            //FORMAMOS SQL PARA GUARDAR RANGO HORARIOS
            $sqlRangoHorarios = " INSERT INTO CALENDARIO_FESTIVOS_HORARIO_RANGO_FECHAS SET
                              ID_CALENDARIO_FESTIVOS = $idCalendarioNuevo
                              , HORARIO_VERANO = $rowCalendarioRango->HORARIO_VERANO
                              , FECHA_INICIO = '$rowCalendarioRango->FECHA_INICIO'
                              , FECHA_FIN = '$rowCalendarioRango->FECHA_FIN' ";

            //RECORREMOS DIAS PARA CONSTRUIR CONSULTA
            for ($i = 0; $i <= 6; $i++):
                $nombreDiaInicio  = self::$arrDiasBBDD[$i] . "_HORA_INICIO";
                $nombreDiaInicio2 = self::$arrDiasBBDD[$i] . "_HORA_INICIO2";
                $nombreDiaFin     = self::$arrDiasBBDD[$i] . "_HORA_FIN";
                $nombreDiaFin2    = self::$arrDiasBBDD[$i] . "_HORA_FIN2";
                $sqlRangoHorarios .= ", " . $nombreDiaInicio . " = " . ($rowCalendarioRango->$nombreDiaInicio != "" ? "'" . $rowCalendarioRango->$nombreDiaInicio . "'" : "NULL") . "
                                    , " . $nombreDiaFin . " = " . ($rowCalendarioRango->$nombreDiaFin != "" ? "'" . $rowCalendarioRango->$nombreDiaFin . "'" : "NULL") . "
                                    , " . $nombreDiaInicio2 . " = " . ($rowCalendarioRango->$nombreDiaInicio2 != "" ? "'" . $rowCalendarioRango->$nombreDiaInicio2 . "'" : "NULL") . "
                                    , " . $nombreDiaFin2 . " = " . ($rowCalendarioRango->$nombreDiaFin2 != "" ? "'" . $rowCalendarioRango->$nombreDiaFin2 . "'" : "NULL");
            endfor;
            $bd->ExecSQL($sqlRangoHorarios);

        endwhile;

        //OBTENGO EVENTOS DE LA PLANTILLA
        $sqlCalendarioEventos    = "SELECT CFL.*
                    FROM CALENDARIO_PLANTILLA_FESTIVOS_LINEA CFL
                    WHERE CFL.ID_CALENDARIO_PLANTILLA_FESTIVOS  = $idPlantillaCalendario ";
        $resultCalendarioEventos = $bd->ExecSQL($sqlCalendarioEventos, "No");

        //COPIO EVENTOS AL NUEVO CALENDARIO
        while ($rowCalendarioEvento = $bd->SigReg($resultCalendarioEventos)):
            //FORMAMOS SQL PARA GUARDAR RANGO HORARIOS
            $sqlInsert = "INSERT INTO CALENDARIO_FESTIVOS_LINEA SET
                                  ID_CALENDARIO_FESTIVOS = $idCalendarioNuevo
                                  , FECHA_INICIO = '" . $rowCalendarioEvento->FECHA_INICIO . "'
                                  , FECHA_FIN = '" . $rowCalendarioEvento->FECHA_FIN . "'
                                  , FESTIVO = $rowCalendarioEvento->FESTIVO
                                  , TIPO_FESTIVO = '" . $rowCalendarioEvento->TIPO_FESTIVO . "'
                                  , ANOTACIONES = '" . $rowCalendarioEvento->ANOTACIONES . "'";
            $bd->ExecSQL($sqlInsert);

        endwhile;


        //OBTENGO HORARIOS DE LA PLANTILLA
        $sqlCalendarioHorarios = "SELECT CFH.*
                    FROM CALENDARIO_PLANTILLA_FESTIVOS_HORARIO CFH
                    WHERE CFH.ID_CALENDARIO_PLANTILLA_FESTIVOS = $idPlantillaCalendario ";

        $resultCalendarioHorarios = $bd->ExecSQL($sqlCalendarioHorarios, "No");

        //COPIO HORARIOS AL NUEVO CALENDARIO
        while ($rowCalendarioHorario = $bd->SigReg($resultCalendarioHorarios)):
            //FORMAMOS SQL PARA GUARDAR RANGO HORARIOS
            //GRABO EL DIA EN LA BASE DE DATOS
            $sqlInsert = "INSERT INTO CALENDARIO_FESTIVOS_HORARIO SET
                              ID_CALENDARIO_FESTIVOS = $idCalendarioNuevo
                              ,ANOTACIONES = '" . $rowCalendarioHorario->ANOTACIONES . "'
                              , FECHA = '" . $rowCalendarioHorario->FECHA . "'
                              , HORA_INICIO = '" . $rowCalendarioHorario->HORA_INICIO . "'
                              , HORA_FIN = '" . $rowCalendarioHorario->HORA_FIN . "'
                              , HORA_INICIO2 = " . ($rowCalendarioHorario->HORA_INICIO2 != "" ? "'" . $rowCalendarioHorario->HORA_INICIO2 . "'" : "NULL") . "
                              , HORA_FIN2 = " . ($rowCalendarioHorario->HORA_FIN2 != "" ? "'" . $rowCalendarioHorario->HORA_FIN2 . "'" : "NULL") . "
                              , HORARIO_ESPECIAL = $rowCalendarioHorario->HORARIO_ESPECIAL
                              , HORARIO_VERANO = $rowCalendarioHorario->HORARIO_VERANO";
            $bd->ExecSQL($sqlInsert);

        endwhile;

        return $idCalendarioNuevo;

    }


    /** DEVUELVE EL ID DEL CALENDARIO DEL ALMACEN, SI NO TIENE DEVUELVE NULL
     * @param $idAlmacen
     * @param $ano
     */
    static function obtenerCalendarioAlmacen($idAlmacen, $ano)
    {
        global $bd;

        $sqlCalendario    = "SELECT DISTINCT CAF.* FROM CALENDARIO_FESTIVOS CAF
                            INNER JOIN CENTRO_FISICO CF ON CF.ID_CENTRO_FISICO = CAF.ID_CENTRO_FISICO
                            INNER JOIN ALMACEN A ON A.ID_CENTRO_FISICO = CF.ID_CENTRO_FISICO
                            WHERE A.ID_ALMACEN = $idAlmacen AND YEAR = '$ano' AND CAF.BAJA = 0";
        $resultCalendario = $bd->ExecSQL($sqlCalendario, "No");

        if ($bd->NumRegs($resultCalendario) > 0):
            $rowCalendario = $bd->SigReg($resultCalendario);

            return $rowCalendario->ID_CALENDARIO_FESTIVOS;
        endif;

        return null;


    }

    /** DEVUELVE ARRAY CON ID DEL CALENDARIO DEL CENTRO FISICO, SI NO TIENE ARRAY VACIO
     * @param $idCentroFisico
     */
    static function obtenerCalendarioCF($idCentroFisico, $aPartirAnoActual = "Si")
    {
        global $bd;
        $arrCalendarios = array();

        //BUSCAMOS CALENDARIOS A PARTIR DEL AÑO ACTUAL
        $sqlWhere = "";
        if ($aPartirAnoActual == "Si"):
            $sqlWhere = " AND YEAR >= '" . date('Y') . "' ";
        endif;

        $sqlCalendario    = "SELECT DISTINCT CAF.* FROM CALENDARIO_FESTIVOS CAF
                            WHERE CAF.ID_CENTRO_FISICO = $idCentroFisico $sqlWhere AND CAF.BAJA = 0 ORDER BY YEAR";
        $resultCalendario = $bd->ExecSQL($sqlCalendario, "No");

        if ($bd->NumRegs($resultCalendario) > 0):
            while ($rowCalendario = $bd->SigReg($resultCalendario)):
                $arrCalendarios[] = $rowCalendario->ID_CALENDARIO_FESTIVOS;
            endwhile;
        endif;

        return $arrCalendarios;
    }

    /** DEVUELVE ARRAY CON ID DEL CALENDARIO DEL PAIS, SI NO TIENE ARRAY VACIO
     * @param $idCentroFisico
     */
    static function obtenerCalendarioPais($idPais, $aPartirAnoActual = "Si")
    {
        global $bd;
        $arrCalendarios = array();

        //BUSCAMOS CALENDARIOS A PARTIR DEL AÑO ACTUAL
        $sqlWhere = "";
        if ($aPartirAnoActual == "Si"):
            $sqlWhere = " AND YEAR >= '" . date('Y') . "' ";
        endif;

        $sqlCalendario    = "SELECT DISTINCT CAF.* FROM CALENDARIO_FESTIVOS CAF
                            WHERE CAF.ID_PAIS = $idPais $sqlWhere AND CAF.BAJA = 0 ORDER BY YEAR";
        $resultCalendario = $bd->ExecSQL($sqlCalendario, "No");

        if ($bd->NumRegs($resultCalendario) > 0):
            while ($rowCalendario = $bd->SigReg($resultCalendario)):
                $arrCalendarios[] = $rowCalendario->ID_CALENDARIO_FESTIVOS;
            endwhile;
        endif;

        return $arrCalendarios;
    }

    /** DEVUELVE EL ID DEL CALENDARIO DEL CENTRO FISICO, SI NO TIENE DEVUELVE NULL
     * @param $idCentroFisico
     * @param $ano
     * @return rowCalendario or null
     */
    static function obtenerCalendarioCFPorAno($idCentroFisico, $ano)
    {
        global $bd;

        $sqlCalendario    = "SELECT DISTINCT CAF.* FROM CALENDARIO_FESTIVOS CAF
                            WHERE CAF.ID_CENTRO_FISICO = $idCentroFisico AND YEAR = '$ano' AND CAF.BAJA = 0";
        $resultCalendario = $bd->ExecSQL($sqlCalendario, "No");

        if ($bd->NumRegs($resultCalendario) > 0):
            $rowCalendario = $bd->SigReg($resultCalendario);

            return $rowCalendario;
        endif;

        return null;
    }

    /** DEVUELVE EL ID DEL CALENDARIO DEL CENTRO FISICO, SI NO TIENE DEVUELVE NULL
     * @param $idProveedor
     * @param $ano
     * @return rowCalendario or null
     */
    static function obtenerCalendarioProveedorPorAno($idProveedor, $ano)
    {
        global $bd;

        $sqlCalendario    = "SELECT DISTINCT CAF.* FROM CALENDARIO_FESTIVOS CAF
                            WHERE CAF.ID_PROVEEDOR = $idProveedor AND YEAR = '$ano' AND CAF.BAJA = 0";
        $resultCalendario = $bd->ExecSQL($sqlCalendario, "No");

        if ($bd->NumRegs($resultCalendario) > 0):
            $rowCalendario = $bd->SigReg($resultCalendario);

            return $rowCalendario;
        endif;

        return null;
    }


    /** DEVUELVE EL ID DEL CALENDARIO DEL PAIS, SI NO TIENE DEVUELVE NULL
     * @param $idCentroFisico
     * @param $ano
     */
    static function obtenerCalendarioPaisPorAno($idPais, $ano)
    {
        global $bd;

        $sqlCalendario    = "SELECT DISTINCT CAF.* FROM CALENDARIO_FESTIVOS CAF
                            WHERE CAF.ID_PAIS = $idPais AND YEAR = '$ano' AND CAF.BAJA = 0";
        $resultCalendario = $bd->ExecSQL($sqlCalendario, "No");

        if ($bd->NumRegs($resultCalendario) > 0):
            $rowCalendario = $bd->SigReg($resultCalendario);

            return $rowCalendario->ID_CALENDARIO_FESTIVOS;
        endif;

        return null;
    }

    /** DEVUELVE EL ID DEL CALENDARIO DEL OBJETO, SI NO TIENE DEVUELVE NULL
     * @param $idCentroFisico
     * @param $ano
     */
    static function obtenerCalendarioObjetoPorAno($idObjeto, $tipoObjeto, $ano)
    {
        global $bd;
        $sqlCalendario    = "SELECT DISTINCT CAF.* FROM CALENDARIO_FESTIVOS CAF
                            WHERE CAF.ID_" . $tipoObjeto . " = $idObjeto AND YEAR = '$ano' AND CAF.BAJA = 0";
        $resultCalendario = $bd->ExecSQL($sqlCalendario, "No");
        if ($bd->NumRegs($resultCalendario) > 0):
            $rowCalendario = $bd->SigReg($resultCalendario);

            return $rowCalendario;
        endif;

        return null;
    }

    /**
     * FUNCION PARA GUARDAR CALENDARIOS FAVORITOS DEL USUARIO ACTUAL
     * @param $tipoObjeto DIFERENCIAR CALENDARIO POR PAIS O CENTRO FISICO
     * @param $idObjeto
     *
     * return false/true  si se ha guardado o no el registro
     */
    static function guardarCalendarioFavoritoAdministrador($tipoObjeto, $idObjeto)
    {
        global $administrador, $bd;

        //COMPROBAMOS SI EL USUARIO TIENE ASGINADO YA EL CALENDARIO
        $sqlComprobarCalendario    = "SELECT * FROM CALENDARIO_FESTIVOS_ADMINISTRADOR WHERE ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR AND TIPO_OBJETO = '$tipoObjeto' AND ID_OBJETO = $idObjeto ";
        $resultComprobarCalendario = $bd->ExecSQL($sqlComprobarCalendario, "No");

        //SI NO ASIGNAMOS Y DEVOLVEMOS TRUE
        if ($bd->NumRegs($resultComprobarCalendario) == 0):
            $sqlInsertCalendarioAdministrador = "INSERT INTO CALENDARIO_FESTIVOS_ADMINISTRADOR SET
                                                    TIPO_OBJETO = '$tipoObjeto'
                                                    , ID_OBJETO = $idObjeto
                                                    , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR";
            $bd->ExecSQL($sqlInsertCalendarioAdministrador);

            //RECUPERO EL ID DEL CALENDARIO RECIEN CREADA
            $idCalendarioAdministrador = $bd->IdAsignado();

            // LOG MOVIMIENTOS
            //º$administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, 'Creacion', "Maestro", $idCalendarioAdministrador, "Calendario Administrador, Calendario: $idCalendario", "CALENDARIO_FESTIVOS_ADMINISTRADOR");

            return true;
        //CASO CONTRARIO DEVOLVEMOS FALSE
        else:
            return false;
        endif;
    }

    /**
     * return array de calendarios favoritos del tipo arr[ID_CENTRO_FISICO/ID_PAIS] = TIPO_CALENDARIO (CENTRO_FISICO O PAIS)
     *
     * al añadir un calendario favorito no selecciona uno en cuestión si no un cf o un pais, todos los calendarios de ese cf o pais serán sus favoritos.
     */
    static function obtenerCalendariosFavoritosAdministrador()
    {
        global $bd, $administrador;

        $arrCalendarios             = array();
        $sqlCalendariosFavoritos    = "SELECT * FROM CALENDARIO_FESTIVOS_ADMINISTRADOR WHERE ID_ADMINISTRADOR = " . $administrador->ID_ADMINISTRADOR;
        $resultCalendariosFavoritos = $bd->ExecSQL($sqlCalendariosFavoritos, "No");

        while ($rowCalendarioFavoritos = $bd->SigReg($resultCalendariosFavoritos)):
            $arrCalendarios[] = $rowCalendarioFavoritos->ID_CALENDARIO_FESTIVOS_ADMINISTRADOR;
        endwhile;

        return $arrCalendarios;

    }

    static function eliminarCalendarioFavoritoAdministrador($idCalendarioAdministrador)
    {
        global $bd, $administrador;

        $sqlDelete = "DELETE FROM CALENDARIO_FESTIVOS_ADMINISTRADOR WHERE
                              ID_CALENDARIO_FESTIVOS_ADMINISTRADOR = $idCalendarioAdministrador";

        $bd->ExecSQL($sqlDelete);

    }

    /**
     * DEVUELVE LA FECHA BUSCADA A PARTIR DE UNA DADA, EJEMPLO PRIMER JUEVES, getFechaEspefificaPorFecha("01-08-2018", "miercoles", 1)
     * @param $fecha fecha de inicio
     * @param $dia (lunes, martes, miercoles, jueves, viernes, sabado, domingo)
     * return fecha "d-m-Y"
     */
    static function getFechaEspecificaOrdenSemana($fecha, $dia, $orden = 1, $fechaFin = "")
    {
        $dia = ucfirst(strtolower((string)$dia));
        if ($orden == "") $orden = 1;

        //COMPRUEBO EL DIA INGLES Y ESPAÑOL
        if (in_array($dia, (array) self::$arrDiasIngles)) $dia = array_search($dia, self::$arrDiasIngles);
        elseif (in_array($dia, (array) self::$arrDiasSemana)) $dia = array_search($dia, self::$arrDiasSemana);
        else return null; //DEVOLVEMOS PRIMERA FECHA PHP - ERROR
        if ($fechaFin == "") $fechaFin = date("Y-m-d", strtotime( (string)$fecha . " +1 month"));

        $fecha       = date("Y-m-d", strtotime( (string)$fecha));
        $ordenActual = 1;
        //BUSCAMOS DIA
        for ($fechaRecorrer = $fecha; $fechaRecorrer <= $fechaFin; $fechaRecorrer = date("Y-m-d", strtotime( (string)$fechaRecorrer . "+ 1 days"))):
            if ($ordenActual == $orden && $dia == date("w", strtotime( (string)$fechaRecorrer))):
                return date("Y-m-d", strtotime( (string)$fechaRecorrer));
            elseif ($dia == date("w", strtotime( (string)$fechaRecorrer))):
                $ordenActual++;
            endif;
        endfor;

        // SI NO ENCONTRAMOS
        return null;
    }

    static function getFechaDiaEspecificoOrdenSemanaMes($fecha, $dia, $orden = 1)
    {
        $dia = ucfirst(strtolower((string)$dia));

        //COMPRUEBO EL DIA INGLES Y ESPAÑOL
        if (in_array($dia, (array) self::$arrDiasIngles)) $dia = array_search($dia, self::$arrDiasIngles);
        elseif (in_array($dia, (array) self::$arrDiasSemana)) $dia = array_search($dia, self::$arrDiasSemana);
        else return null; //DEVOLVEMOS PRIMERA FECHA PHP - ERROR

        //FECHA INICIO ESTABLEZCO EL DIA AL PRIMERO DEL MES
        $fechaInicio = date("Y", strtotime( (string)$fecha)) . "-" . date("m", strtotime( (string)$fecha)) . "-01";
        $fechaFin    = date("Y-m-d", strtotime( (string)$fechaInicio . " +1 month"));

        $ordenActual   = 1;
        $fechaDevolver = "";
        //BUSCAMOS DIA
        for ($fechaRecorrer = $fechaInicio; $fechaRecorrer < $fechaFin; $fechaRecorrer = date("Y-m-d", strtotime( (string)$fechaRecorrer . "+ 1 days"))):
            if ($ordenActual == $orden && $dia == date("w", strtotime( (string)$fechaRecorrer))):
                $fechaDevolver = date("Y-m-d", strtotime( (string)$fechaRecorrer));
                break;
            elseif ($dia == date("w", strtotime( (string)$fechaRecorrer))):
                $ordenActual++;
                //SI EL USUARIO ESCCOGE ULTIMA SEMANA DEL MES EL ORDEN ES 5, SI EL MES SOLO TIENE 4 SEMANAS (ORDEN 4) DEVOLVERA LA DE LA ULTIMA SEMANA ORDEN 4
                $fechaDevolver = date("Y-m-d", strtotime( (string)$fechaRecorrer));
            endif;
        endfor;

        return $fechaDevolver;
    }

    static function getEsFechaFestivaONoLaborableCF($idCentroFisico, $fecha)
    {
        global $bd;

        //OBTENGO EL CALENDARIO
        $rowCalendario = self::obtenerCalendarioCFPorAno($idCentroFisico, date("Y", strtotime( (string)$fecha)));

        //SI EL CF TIENE CALENDARIO PARA ESE AÑO
        if ($rowCalendario != null):

            //CONSULTO SI LA FECHA ES FESTIVA O NO LABORABLE
            $sqlConsultarFestivo    = "SELECT * FROM CALENDARIO_FESTIVOS_LINEA
                                        WHERE ID_CALENDARIO_FESTIVOS = $rowCalendario->ID_CALENDARIO_FESTIVOS
                                        AND FECHA_INICIO = '" . date("Y-m-d", strtotime( (string)$fecha)) . "'";
            $resultConsultarFestivo = $bd->ExecSQL($sqlConsultarFestivo, "No");
            if ($bd->NumRegs($resultConsultarFestivo) > 0):
                $rowCalendarioLinea = $bd->SigReg($resultConsultarFestivo);

                //SI ES FESTIVA O NO LABORABLE DEVUELVO LA LINEA DEL CALENDARIO
                return $rowCalendarioLinea->ID_CALENDARIO_FESTIVOS_LINEA;
            endif;
        endif;

        //SI NO FESTIVA O NO LABORABLE
        return false;
    }
    static function getEsFechaFestivaONoLaborableProveedor($idProveedor, $fecha)
    {
        global $bd;

        //OBTENGO EL CALENDARIO
        $rowCalendario = self::obtenerCalendarioProveedorPorAno($idProveedor, date("Y", strtotime( (string)$fecha)));

        //SI EL PROVEEDOR TIENE CALENDARIO PARA ESE AÑO
        if ($rowCalendario != null):

            //CONSULTO SI LA FECHA ES FESTIVA O NO LABORABLE
            $sqlConsultarFestivo    = "SELECT * FROM CALENDARIO_FESTIVOS_LINEA
                                        WHERE ID_CALENDARIO_FESTIVOS = $rowCalendario->ID_CALENDARIO_FESTIVOS
                                        AND FECHA_INICIO = '" . date("Y-m-d", strtotime( (string)$fecha)) . "'";
            $resultConsultarFestivo = $bd->ExecSQL($sqlConsultarFestivo, "No");
            if ($bd->NumRegs($resultConsultarFestivo) > 0):
                $rowCalendarioLinea = $bd->SigReg($resultConsultarFestivo);

                //SI ES FESTIVA O NO LABORABLE DEVUELVO LA LINEA DEL CALENDARIO
                return $rowCalendarioLinea->ID_CALENDARIO_FESTIVOS_LINEA;
            endif;
        endif;

        //SI NO FESTIVA O NO LABORABLE
        return false;
    }
}