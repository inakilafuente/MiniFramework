<?php

# ubicacion
# Clase ubicacion contiene todas las funciones necesarias para
# la interaccion con la clase ubicacion
# Se incluira en las sesiones
# Agosto 2007 Fabián Bueno - Dinámica

class ubicacion
{

    function __construct()
    {
    } // Fin ubicacion

    function Existe_Ubicacion($txUbicacion)
    {
        global $bd;

        $sql    = "SELECT ID_UBICACION FROM UBICACION WHERE UBICACION='$txUbicacion'";
        $result = $bd->ExecSQL($sql, "No");
        if ($result == false) return "Err.";
        if ($bd->NumRegs($result) > 0):
            return true;
        else:
            return false;
        endif;
    } // FIN Existe_Ubicacion

    function Existe_Ubicacion_Almacen($idAlmacen, $txUbicacion)
    {
        global $bd;

        $sql    = "SELECT ID_UBICACION FROM UBICACION WHERE ID_ALMACEN=$idAlmacen AND UBICACION='$txUbicacion'";
        $result = $bd->ExecSQL($sql, "No");
        if ($result == false) return "Err.";
        if ($bd->NumRegs($result) > 0):
            return true;
        else:
            return false;
        endif;
    } // FIN Existe_Ubicacion_Almacen

    function Obtener_Id($txUbicacion)
    {
        global $bd;

        $sql    = "SELECT * FROM UBICACION WHERE UBICACION='$txUbicacion'";
        $result = $bd->ExecSQL($sql, "No");
        if ($result == false) return "Err.";
        if ($bd->NumRegs($result) > 0):
            $row = $bd->SigReg($result);

            return $row;
        else:
            return false;
        endif;
    } // FIN obtenerId

    // COMPRUEBA QUE EXISTA UN REGISTRO EN LA TABLA MATERIAL_UBICACION
    function Existe_Registro_Ubicacion_Material($idUbicacion, $idMaterial, $idMaterialFisico = NULL, $idTipoBloqueo = NULL, $idOrdenTrabajoMovimiento = NULL, $idIncidenciaCalidad = NULL)
    {
        global $bd;

        $sql    = "SELECT ID_MATERIAL_UBICACION FROM MATERIAL_UBICACION WHERE ID_UBICACION=$idUbicacion AND ID_MATERIAL=$idMaterial AND ID_MATERIAL_FISICO" . ($idMaterialFisico == NULL ? " IS NULL" : " = $idMaterialFisico") . " AND ID_TIPO_BLOQUEO" . ($idTipoBloqueo == NULL ? " IS NULL" : " = $idTipoBloqueo") . " AND ID_ORDEN_TRABAJO_MOVIMIENTO" . ($idOrdenTrabajoMovimiento == NULL ? " IS NULL" : " = $idOrdenTrabajoMovimiento") . " AND ID_INCIDENCIA_CALIDAD" . ($idIncidenciaCalidad == NULL ? " IS NULL" : " = $idIncidenciaCalidad");//exit($sql);
        $result = $bd->ExecSQL($sql, "No");
        if ($result == false) return "Err.";
        if ($bd->NumRegs($result) > 0):
            $row = $bd->SigReg($result);

            return true;
        else:
            return false;
        endif;
    } // FIN Existe_Registro_Ubicacion_Material

    // COMPRUEBA QUE EL STOCK TOTAL ES MAYOR O IGUAL A LA CANTIDAD A TRASPASAR
    function Comprobar_Stock_Total($idUbicacion, $idMaterial, $cantidad, $idMaterialFisico = "")
    {
        global $bd;

        $sqlMF = "";
        if ($idMaterialFisico != ""):
            $sqlMF = " AND ID_MATERIAL_FISICO = $idMaterialFisico ";
        endif;

        $sql    = "SELECT SUM(STOCK_TOTAL) AS STOCK 
                        FROM MATERIAL_UBICACION 
                        WHERE ID_UBICACION=$idUbicacion AND ID_MATERIAL=$idMaterial AND ACTIVO = 1 $sqlMF";
        $result = $bd->ExecSQL($sql);
        if ($bd->NumRegs($result) > 0):
            $row = $bd->SigReg($result);

            if ($cantidad - $row->STOCK > EPSILON_SISTEMA):
                return false;
            else:
                return true;
            endif;
        else:
            return false;
        endif;
    } // FIN Comprobar_Stock_Total

    // COMPRUEBA QUE EL STOCK TOTAL ES MAYOR O IGUAL A LA CANTIDAD A TRASPASAR
    function Obtener_Stock_Ubicacion($idUbicacion, $idMaterial)
    {
        global $bd;

        $sql    = "SELECT STOCK_TOTAL FROM MATERIAL_UBICACION WHERE ID_UBICACION=$idUbicacion AND ID_MATERIAL=$idMaterial";
        $result = $bd->ExecSQL($sql, "No");
        if ($result == false) return "Err.";
        if ($bd->NumRegs($result) > 0):
            $row = $bd->SigReg($result);

            return $row->STOCK_TOTAL;
        else:
            return 0;
        endif;
    } // FIN Comprobar_Stock_Total

    function Es_Mayor_Stock_Total($idUbicacion, $idMaterial, $cantidad, $signo = "sumando")
    {
        global $bd;

        $sql    = "SELECT STOCK_TOTAL FROM MATERIAL_UBICACION WHERE ID_UBICACION=$idUbicacion AND ID_MATERIAL=$idMaterial";
        $result = $bd->ExecSQL($sql, "No");
        if ($result == false) return "Err.";
        if ($bd->NumRegs($result) > 0):
            $row = $bd->SigReg($result);
            if ($signo == "sumando"):
                if (($row->STOCK_TOTAL + $cantidad) < 0):
                    return true;
                else:
                    return false;
                endif;
            elseif ($signo == "restando"):
                if (($row->STOCK_TOTAL - $cantidad) < 0):
                    return true;
                else:
                    return false;
                endif;
            endif;
        endif;
    } // Es_Mayor_Stock_Total

    function Restar_A_Stock_Total($idUbicacion, $idMaterial, $cantidad)
    {
        global $bd;

        $sql = "UPDATE MATERIAL_UBICACION SET STOCK_TOTAL = (STOCK_TOTAL - $cantidad) WHERE ID_UBICACION=$idUbicacion AND ID_MATERIAL=$idMaterial";
        $bd->ExecSQL($sql);
    } // FIN Restar_A_Stock_Total

    function Sumar_A_Stock_Total($idUbicacion, $idMaterial, $cantidad)
    {
        global $bd;

        $sql = "UPDATE MATERIAL_UBICACION SET STOCK_TOTAL = (STOCK_TOTAL + $cantidad) WHERE ID_UBICACION=$idUbicacion AND ID_MATERIAL=$idMaterial";
        $bd->ExecSQL($sql);
    } // FIN Sumar_A_Stock_Total

    function UbicarMaterial($idMaterial, $idAlmacen, $idTipoBloqueo, $especiales = "No", $listaUbicacionNoValidas = "0")
    {
        global $bd;
        global $html;

        //BUSCO EL TIPO DE BLOQUEO PREVENTIVO
        $rowTipoBloqueoPreventivo = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "SP");
        //BUSCO EL TIPO DE BLOQUEO RESERVADO
        $rowTipoBloqueoReservado = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "RV");
        //BUSCO EL TIPO DE BLOQUEO RESERVADO PLANIFICADO
        $rowTipoBloqueoReservadoPlanificado = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "RVP");

        //VARIABLE PARA SABER SI ES UNA UBICACION DE CALIDAD
        $calidad = false;

        //VARIABLE PARA ACOTAR BUSQUEDAS
        $sqlwhere       = "";
        $sqlTipoBloqueo = "";
        $sqlAPQ         = "";

        //DATOS NECESARIOS PARA LAS COMPROBACIONES
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowAlmacen                       = $bd->VerReg("ALMACEN", "ID_ALMACEN", $idAlmacen, "No");
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowCentroFisico                  = $bd->VerReg("CENTRO_FISICO", "ID_CENTRO_FISICO", $rowAlmacen->ID_CENTRO_FISICO, "No");
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowPais                          = $bd->VerReg("PAIS", "ID_PAIS", $rowCentroFisico->ID_PAIS, "No");
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowMat                           = $bd->VerReg("MATERIAL", "ID_MATERIAL", $idMaterial, "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);

        //VARIABLE PARA SABER SI TENGO QUE AGRUPAR POR TIPO BLOQUEO
        $agruparPorTipoBloqueo = true;

        //SI EL CENTRO FISICO ESTÁ MARCADO COMO PRINCIPAL REALIZAMOS LA BUSQUEDA DE MATERIAL DE CORRECTIVO Y PREVENTIVO DE FORMA CONJUNTA
        if ($rowCentroFisico->AGRUPACION_STOCK_RECEPCION == 1):
            if (($idTipoBloqueo == NULL) || ($idTipoBloqueo == $rowTipoBloqueoPreventivo->ID_TIPO_BLOQUEO)): //OK O P

                //ACTUALIZO LA VARIABLE PARA SABER SI TENGO QUE AGRUPAR POR TIPO BLOQUEO
                $agruparPorTipoBloqueo = false;

                //TIPO BLOQUEO
                $sqlTipoBloqueo = $sqlTipoBloqueo . " AND (MU.ID_TIPO_BLOQUEO IS NULL OR MU.ID_TIPO_BLOQUEO = $rowTipoBloqueoPreventivo->ID_TIPO_BLOQUEO OR MU.ID_TIPO_BLOQUEO = $rowTipoBloqueoReservado->ID_TIPO_BLOQUEO OR MU.ID_TIPO_BLOQUEO = $rowTipoBloqueoReservadoPlanificado->ID_TIPO_BLOQUEO)";
            else:
                //TIPO BLOQUEO
                $rowTipoBloqueo = $bd->VerReg("TIPO_BLOQUEO", "ID_TIPO_BLOQUEO", $idTipoBloqueo);
                $sqlTipoBloqueo = $sqlTipoBloqueo . " AND MU.ID_TIPO_BLOQUEO = $idTipoBloqueo";
            endif;
        else: //NO AGRUPACION DE STOCKS
            //TIPO BLOQUEO
            if ($idTipoBloqueo == NULL):
                $sqlTipoBloqueo = $sqlTipoBloqueo . " AND MU.ID_TIPO_BLOQUEO IS NULL";
            else:
                $rowTipoBloqueo = $bd->VerReg("TIPO_BLOQUEO", "ID_TIPO_BLOQUEO", $idTipoBloqueo);
                $sqlTipoBloqueo = $sqlTipoBloqueo . " AND MU.ID_TIPO_BLOQUEO = $idTipoBloqueo";
            endif;
        endif;
        //FIN SI EL CENTRO FISICO ESTÁ MARCADO COMO PRINCIPAL REALIZAMOS LA BUSQUEDA DE MATERIAL DE CORRECTIVO Y PREVENTIVO DE FORMA CONJUNTA

        //SI NO ES OK EL MATERIAL RECEPCIONADO Y TIENE QUE PASAR CONTRL DE CALIDAD
        if ($idTipoBloqueo != NULL):
            // BUSCO LA FDS CORRESPONDIENTE AL MATERIAL
            $GLOBALS["NotificaErrorPorEmail"] = "No";
            $rowFDS                           = $bd->VerRegRest("FICHA_SEGURIDAD_MATERIAL_IDIOMA", "ID_MATERIAL = $rowMat->ID_MATERIAL AND ID_IDIOMA = $rowPais->ID_IDIOMA_PRINCIPAL AND ESTADO ='Valida'", "No");
            unset($GLOBALS["NotificaErrorPorEmail"]);

            // SI TIENE MARCADO EL CONTROL DE CALIDAD
            if ($rowTipoBloqueo->CONTROL_CALIDAD == 1):
                $sqlwhere = $sqlwhere . " AND U.TIPO_UBICACION = 'Calidad'";
                $calidad  = true;

            // SI REQUIERE FDS, EL CENTRO FÍSICO TIENE CONTROL DE FDS Y NO EXISTE FDS VÁLIDA CARGADA EN EL SISTEMA
            elseif ($rowMat->DEBE_TENER_FICHA_SEGURIDAD == 1 && $rowCentroFisico->CONTROL_FDS == 1 && !$rowFDS):
                $sqlwhere = $sqlwhere . " AND U.TIPO_UBICACION = 'Calidad'";
                $calidad  = true;
            endif;
        endif;
        //FIN SI NO ES OK EL MATERIAL RECEPCIONADO Y TIENE QUE PASAR CONTRL DE CALIDAD

        //UBICACIONES ESPECIALES
        if (
            ($rowCentroFisico->AGRUPACION_STOCK_RECEPCION == 1) && //AGRUPACION DE STOCKS
            (($idTipoBloqueo == NULL) || ($idTipoBloqueo == $rowTipoBloqueoPreventivo->ID_TIPO_BLOQUEO)) //OK O P
        ):
            $sqlwhere = $sqlwhere . " AND (U.TIPO_UBICACION_ESTANDAR = 1 OR U.TIPO_UBICACION = 'Preventivo')";
        elseif ($especiales == "No"):
            $sqlwhere = $sqlwhere . " AND U.TIPO_UBICACION IS NULL";
        elseif ($especiales == "Si"):
            $sqlwhere = $sqlwhere . " AND U.TIPO_UBICACION IS NOT NULL";
        endif;

        //LISTA UBICACIONES A QUITAR
        if ($listaUbicacionNoValidas != ""):
            $sqlwhere = $sqlwhere . " AND U.ID_UBICACION NOT IN ($listaUbicacionNoValidas)";
        endif;

        //SI EL CF DEL ALMACEN TIENE GESTION APQ, Y EL MATERIAL ES APQ, TENDREMOS QUE BUSCAR UNA UBICACION APQ
        if (($rowCentroFisico->GESTION_APQ == 1)):
            if (($rowMat->APQ == "1") || ($rowMat->APQ == "5") || ($rowMat->APQ == "6") || ($rowMat->APQ == "7") || ($rowMat->APQ == "9") || ($rowMat->APQ == "RG")):
                $sqlAPQ = " AND U.CLASE_APQ = '$rowMat->APQ'";
            endif;
        endif;
        unset($GLOBALS["NotificaErrorPorEmail"]);

        //SQL PARA EXTRAER LA UBICACION
        if ($calidad == false):
            if ($sqlAPQ != ""):
                $sql = "SELECT ID_UBICACION
                        FROM UBICACION U
                        WHERE U.ID_ALMACEN = $idAlmacen AND U.BAJA = 0 $sqlwhere $sqlAPQ";
            else:
                if (($idTipoBloqueo == NULL) || ($idTipoBloqueo == $rowTipoBloqueoPreventivo->ID_TIPO_BLOQUEO)):
                    $sql = "SELECT U.ID_UBICACION, U.UBICACION, U.NOMBRE_MAQUINA
                            FROM MATERIAL_UBICACION MU
                            INNER JOIN UBICACION UCF ON UCF.ID_UBICACION = MU.ID_UBICACION
                            INNER JOIN ALMACEN ACF ON ACF.ID_ALMACEN = UCF.ID_ALMACEN
                            INNER JOIN UBICACION U ON U.UBICACION = UCF.UBICACION
                            WHERE ACF.ID_CENTRO_FISICO = $rowAlmacen->ID_CENTRO_FISICO AND U.ID_ALMACEN = $rowAlmacen->ID_ALMACEN AND MU.ID_MATERIAL = $idMaterial AND U.BAJA = 0 $sqlTipoBloqueo $sqlwhere AND MU.ACTIVO = 1 AND UCF.BAJA = 0
                            ORDER BY MU.STOCK_TOTAL DESC, U.UBICACION ASC
                            LIMIT 1";
                else:
                    $sql = "SELECT MU.ID_UBICACION, SUM(MU.STOCK_TOTAL) AS TOTAL_STOCK
                            FROM MATERIAL_UBICACION MU
                            INNER JOIN UBICACION U ON U.ID_UBICACION = MU.ID_UBICACION
                            WHERE MU.ID_MATERIAL = $idMaterial AND U.ID_ALMACEN = $idAlmacen AND U.BAJA = 0 $sqlTipoBloqueo $sqlwhere 
                            GROUP BY MU.ID_UBICACION " . ($agruparPorTipoBloqueo == true ? ", MU.ID_TIPO_BLOQUEO" : "") . "
                            ORDER BY TOTAL_STOCK DESC, UBICACION";
                endif;
            endif;
        else:
            $sql = "SELECT ID_UBICACION
                    FROM UBICACION 
                    WHERE ID_ALMACEN = $idAlmacen AND TIPO_UBICACION = 'Calidad' AND BAJA = 0";
        endif;

        //EJECUTO LA SQL CORRESPONDIENTE
        $result = $bd->ExecSQL($sql);

        if ($bd->NumRegs($result) > 0): //HAY UBICACIONES EXISTENTES CON ESTE MATERIAL EN ESTE ALMACEN
            $row = $bd->SigReg($result);
        else: //NO HAY UBICACIONES CON ESTE MATERIAL EN ESTE ALMACEN, BUSCO LA UBICACION DONDE HAYA ESTADO EL MATERIAL DENTRO DEL ALMACEN
            if (($idTipoBloqueo == NULL) || ($idTipoBloqueo == $rowTipoBloqueoPreventivo->ID_TIPO_BLOQUEO)): //OK O P
                if ($rowCentroFisico->AGRUPACION_STOCK_RECEPCION == 1): //AGRUPACION DE STOCKS
                    $sqlBuscaUbicaciones = "SELECT U.ID_UBICACION, U.UBICACION, MEL.FECHA_PROCESADO AS FECHA
                                            FROM MOVIMIENTO_ENTRADA_LINEA MEL
                                            INNER JOIN UBICACION UCF ON UCF.ID_UBICACION = MEL.ID_UBICACION
                                            INNER JOIN ALMACEN ACF ON ACF.ID_ALMACEN = UCF.ID_ALMACEN
                                            INNER JOIN UBICACION U ON U.UBICACION = UCF.UBICACION
                                            WHERE ACF.ID_CENTRO_FISICO = $rowAlmacen->ID_CENTRO_FISICO AND U.ID_ALMACEN = $idAlmacen AND (U.TIPO_UBICACION_ESTANDAR = 1 OR U.TIPO_UBICACION = 'Preventivo') AND MEL.ID_MATERIAL = $idMaterial AND U.BAJA = 0
                                            UNION ALL
                                            SELECT U.ID_UBICACION, U.UBICACION, MT.FECHA AS FECHA
                                            FROM MOVIMIENTO_TRANSFERENCIA MT
                                            INNER JOIN UBICACION UCF ON UCF.ID_UBICACION = MT.ID_UBICACION_DESTINO
                                            INNER JOIN ALMACEN ACF ON ACF.ID_ALMACEN = UCF.ID_ALMACEN
                                            INNER JOIN UBICACION U ON U.UBICACION = UCF.UBICACION
                                            WHERE MT.TIPO IN ('Manual', 'Recepcion', 'ReubicacionMaterial') AND ACF.ID_CENTRO_FISICO = $rowAlmacen->ID_CENTRO_FISICO AND U.ID_ALMACEN = $idAlmacen AND (U.TIPO_UBICACION_ESTANDAR = 1 OR U.TIPO_UBICACION = 'Preventivo') AND MT.ID_MATERIAL = $idMaterial AND U.BAJA = 0
                                            ORDER BY FECHA DESC";
                else: //SIN AGRUPACION DE STOCKS
                    $sqlBuscaUbicaciones = "SELECT U.ID_UBICACION, U.UBICACION, MEL.FECHA_PROCESADO AS FECHA
                                            FROM MOVIMIENTO_ENTRADA_LINEA MEL
                                            INNER JOIN UBICACION U ON U.ID_UBICACION = MEL.ID_UBICACION
                                            WHERE U.ID_ALMACEN = $idAlmacen AND U.TIPO_UBICACION_ESTANDAR = 1 AND MEL.ID_MATERIAL = $idMaterial AND U.BAJA = 0
                                            UNION ALL
                                            SELECT U.ID_UBICACION, U.UBICACION, MT.FECHA AS FECHA
                                            FROM MOVIMIENTO_TRANSFERENCIA MT
                                            INNER JOIN UBICACION U ON U.ID_UBICACION = MT.ID_UBICACION_DESTINO
                                            WHERE MT.TIPO IN ('Manual', 'Recepcion', 'ReubicacionMaterial') AND U.ID_ALMACEN = $idAlmacen AND U.TIPO_UBICACION_ESTANDAR = 1 AND MT.ID_MATERIAL = $idMaterial AND U.BAJA = 0
                                            ORDER BY FECHA DESC";
                endif;
                $resultBuscaUbicaciones = $bd->ExecSQL($sqlBuscaUbicaciones);
                if ($bd->NumRegs($resultBuscaUbicaciones) > 0):
                    $row = $bd->SigReg($resultBuscaUbicaciones);
                endif;
            endif;
            //FIN OK/P

            if (!isset($row)): //SE OBTIENE LA PRIMERA UBICACION DEL ALMACEN
                $row = $bd->VerRegRest("UBICACION", "ID_ALMACEN = $idAlmacen AND BAJA = 0");
            endif;

        endif;

        return $row->ID_UBICACION;
    }

    function UbicarMaterialPDA($idMaterial, $idAlmacen, $idTipoBloqueo, $especiales = "No", $listaUbicacionNoValidas = "0")
    {
        global $bd;
        global $html;

        //BUSCO EL TIPO DE BLOQUEO PREVENTIVO
        $rowTipoBloqueoPreventivo = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "SP");
        //BUSCO EL TIPO DE BLOQUEO RESERVADO
        $rowTipoBloqueoReservado = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "RV");
        //BUSCO EL TIPO DE BLOQUEO RESERVADO PLANIFICADO
        $rowTipoBloqueoReservadoPlanificado = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "RVP");
        //BUSCO EL TIPO DE BLOQUEO RESERVADO PARA PREPARACION
        $rowTipoBloqueoReservadoPreparacion = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "RP");
        //BUSCO EL TIPO DE BLOQUEO RESERVADO PARA PREPARACION PREVENTIVO
        $rowTipoBloqueoReservadoPreparacionPreventivo = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "RPP");

        //VARIABLE PARA SABER SI ES UNA UBICACION DE CALIDAD
        $calidad = false;

        //VARIABLE PARA ACOTAR BUSQUEDAS
        $sqlwhere       = "";
        $sqlTipoBloqueo = "";
        $sqlAPQ         = "";

        //DATOS NECESARIOS PARA LAS COMPROBACIONES
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowAlmacen                       = $bd->VerReg("ALMACEN", "ID_ALMACEN", $idAlmacen, "No");
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowCentroFisico                  = $bd->VerReg("CENTRO_FISICO", "ID_CENTRO_FISICO", $rowAlmacen->ID_CENTRO_FISICO, "No");
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowPais                          = $bd->VerReg("PAIS", "ID_PAIS", $rowCentroFisico->ID_PAIS, "No");
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowMat                           = $bd->VerReg("MATERIAL", "ID_MATERIAL", $idMaterial, "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);

        //VARIABLE PARA SABER SI TENGO QUE AGRUPAR POR TIPO BLOQUEO
        $agruparPorTipoBloqueo = true;

        //SI EL CENTRO FISICO ESTÁ MARCADO COMO PRINCIPAL REALIZAMOS LA BUSQUEDA DE MATERIAL DE CORRECTIVO Y PREVENTIVO DE FORMA CONJUNTA
        if ($rowCentroFisico->AGRUPACION_STOCK_RECEPCION == 1):
            if (($idTipoBloqueo == NULL) || ($idTipoBloqueo == $rowTipoBloqueoPreventivo->ID_TIPO_BLOQUEO)): //OK O P

                //ACTUALIZO LA VARIABLE PARA SABER SI TENGO QUE AGRUPAR POR TIPO BLOQUEO
                $agruparPorTipoBloqueo = false;

                //TIPO BLOQUEO
                $sqlTipoBloqueo = $sqlTipoBloqueo . " AND (MU.ID_TIPO_BLOQUEO IS NULL OR MU.ID_TIPO_BLOQUEO = $rowTipoBloqueoPreventivo->ID_TIPO_BLOQUEO OR MU.ID_TIPO_BLOQUEO = $rowTipoBloqueoReservado->ID_TIPO_BLOQUEO OR MU.ID_TIPO_BLOQUEO = $rowTipoBloqueoReservadoPlanificado->ID_TIPO_BLOQUEO)";
            else:
                //TIPO BLOQUEO
                $rowTipoBloqueo = $bd->VerReg("TIPO_BLOQUEO", "ID_TIPO_BLOQUEO", $idTipoBloqueo);
                $sqlTipoBloqueo = $sqlTipoBloqueo . " AND MU.ID_TIPO_BLOQUEO = $idTipoBloqueo";
            endif;
        else: //NO AGRUPACION DE STOCKS
            //TIPO BLOQUEO
            if ($idTipoBloqueo == NULL):
                $sqlTipoBloqueo = $sqlTipoBloqueo . " AND MU.ID_TIPO_BLOQUEO IS NULL";
            else:
                $rowTipoBloqueo = $bd->VerReg("TIPO_BLOQUEO", "ID_TIPO_BLOQUEO", $idTipoBloqueo);
                $sqlTipoBloqueo = $sqlTipoBloqueo . " AND MU.ID_TIPO_BLOQUEO = $idTipoBloqueo";
            endif;
        endif;
        //FIN SI EL CENTRO FISICO ESTÁ MARCADO COMO PRINCIPAL REALIZAMOS LA BUSQUEDA DE MATERIAL DE CORRECTIVO Y PREVENTIVO DE FORMA CONJUNTA

        //SI NO ES OK EL MATERIAL RECEPCIONADO Y TIENE QUE PASAR CONTRL DE CALIDAD
        if ($idTipoBloqueo != NULL):
            // BUSCO LA FDS CORRESPONDIENTE AL MATERIAL
            $GLOBALS["NotificaErrorPorEmail"] = "No";
            $rowFDS                           = $bd->VerRegRest("FICHA_SEGURIDAD_MATERIAL_IDIOMA", "ID_MATERIAL = $rowMat->ID_MATERIAL AND ID_IDIOMA = $rowPais->ID_IDIOMA_PRINCIPAL AND ESTADO ='Valida'", "No");
            unset($GLOBALS["NotificaErrorPorEmail"]);

            // SI TIENE MARCADO EL CONTROL DE CALIDAD
            if ($rowTipoBloqueo->CONTROL_CALIDAD == 1):
                $sqlwhere = $sqlwhere . " AND U.TIPO_UBICACION = 'Calidad'";
                $calidad  = true;

            // SI REQUIERE FDS, EL CENTRO FÍSICO TIENE CONTROL DE FDS Y NO EXISTE FDS VÁLIDA CARGADA EN EL SISTEMA
            elseif ($rowMat->DEBE_TENER_FICHA_SEGURIDAD == 1 && $rowCentroFisico->CONTROL_FDS == 1 && !$rowFDS):
                $sqlwhere = $sqlwhere . " AND U.TIPO_UBICACION = 'Calidad'";
                $calidad  = true;
            endif;
        endif;
        //FIN SI NO ES OK EL MATERIAL RECEPCIONADO Y TIENE QUE PASAR CONTRL DE CALIDAD

        //UBICACIONES ESPECIALES
        if (
            ($rowCentroFisico->AGRUPACION_STOCK_RECEPCION == 1) && //AGRUPACION DE STOCKS
            (($idTipoBloqueo == NULL) || ($idTipoBloqueo == $rowTipoBloqueoPreventivo->ID_TIPO_BLOQUEO)) //OK O P
        ):
            $sqlwhere = $sqlwhere . " AND (U.TIPO_UBICACION_ESTANDAR = 1 OR U.TIPO_UBICACION = 'Preventivo')";
        elseif ($especiales == "No"):
            $sqlwhere = $sqlwhere . " AND U.TIPO_UBICACION IS NULL";
        elseif ($especiales == "Si"):
            $sqlwhere = $sqlwhere . " AND U.TIPO_UBICACION IS NOT NULL";
        endif;

        //LISTA UBICACIONES A QUITAR
        if ($listaUbicacionNoValidas != ""):
            $sqlwhere = $sqlwhere . " AND U.ID_UBICACION NOT IN ($listaUbicacionNoValidas)";
        endif;

        //SI EL CF DEL ALMACEN TIENE GESTION APQ, Y EL MATERIAL ES APQ, TENDREMOS QUE BUSCAR UNA UBICACION APQ
        if (($rowCentroFisico->GESTION_APQ == 1)):
            if (($rowMat->APQ == "1") || ($rowMat->APQ == "5") || ($rowMat->APQ == "6") || ($rowMat->APQ == "7") || ($rowMat->APQ == "9") || ($rowMat->APQ == "RG")):
                $sqlAPQ = " AND U.CLASE_APQ = '$rowMat->APQ'";
            endif;
        endif;
        unset($GLOBALS["NotificaErrorPorEmail"]);

        //SQL PARA EXTRAER LA UBICACION
        if ($calidad == false):
            if ($sqlAPQ != ""):
                $sql = "SELECT ID_UBICACION
                        FROM UBICACION U
                        WHERE U.ID_ALMACEN = $idAlmacen AND U.BAJA = 0 $sqlwhere $sqlAPQ";
            else:
                if (($idTipoBloqueo == NULL) || ($idTipoBloqueo == $rowTipoBloqueoPreventivo->ID_TIPO_BLOQUEO)):

                    //SI EL MATERIAL CENTRO_FISICO EXISTE Y TIENE UNA UBICACION FIJA LA BUSCO
                    $rowMatCF = $bd->VerRegRest("MATERIAL_CENTRO_FISICO", " ID_MATERIAL = $idMaterial AND ID_CENTRO_FISICO = " . $rowAlmacen->ID_CENTRO_FISICO . " AND BAJA = 0", "No");
                    if ($rowMatCF && $rowMatCF->ID_UBICACION_FIJA != ""):
                        return $rowMatCF->ID_UBICACION_FIJA;
                    endif;

                    //SI EL MATERIAL CENTRO FISICO PUEDE ENTRAR EN AUTOSTORE SE BUSCARA LA UBICACION DE AUTOSTORE
                    if ($rowMatCF && $rowMatCF->PUEDE_ENTRAR_EN_AUTOSTORE != ""):
                        $sqlwhere = $sqlwhere . " AND U.AUTOSTORE = 1";
                    endif;

                    $sql = "SELECT U.ID_UBICACION, U.UBICACION, U.NOMBRE_MAQUINA
                            FROM MATERIAL_UBICACION MU
                            INNER JOIN UBICACION UCF ON UCF.ID_UBICACION = MU.ID_UBICACION
                            INNER JOIN ALMACEN ACF ON ACF.ID_ALMACEN = UCF.ID_ALMACEN
                            INNER JOIN UBICACION U ON U.UBICACION = UCF.UBICACION
                            WHERE ACF.ID_CENTRO_FISICO = $rowAlmacen->ID_CENTRO_FISICO AND U.ID_ALMACEN = $rowAlmacen->ID_ALMACEN AND MU.ID_MATERIAL = $idMaterial AND U.BAJA = 0 $sqlTipoBloqueo $sqlwhere AND MU.ACTIVO = 1 AND UCF.BAJA = 0
                            ORDER BY MU.STOCK_TOTAL DESC, U.UBICACION ASC
                            LIMIT 1";
                else:
                    $sql = "SELECT MU.ID_UBICACION, SUM(MU.STOCK_TOTAL) AS TOTAL_STOCK
                            FROM MATERIAL_UBICACION MU
                            INNER JOIN UBICACION U ON U.ID_UBICACION = MU.ID_UBICACION
                            WHERE MU.ID_MATERIAL = $idMaterial AND U.ID_ALMACEN = $idAlmacen AND U.BAJA = 0 $sqlTipoBloqueo $sqlwhere 
                            GROUP BY MU.ID_UBICACION " . ($agruparPorTipoBloqueo == true ? ", MU.ID_TIPO_BLOQUEO" : "") . "
                            ORDER BY TOTAL_STOCK DESC, UBICACION";
                endif;
            endif;
        else:
            $sql = "SELECT ID_UBICACION
                    FROM UBICACION 
                    WHERE ID_ALMACEN = $idAlmacen AND TIPO_UBICACION = 'Calidad' AND BAJA = 0";
        endif;

        //EJECUTO LA SQL CORRESPONDIENTE
        $result = $bd->ExecSQL($sql);

        if ($bd->NumRegs($result) > 0): //HAY UBICACIONES EXISTENTES CON ESTE MATERIAL EN ESTE ALMACEN
            $row = $bd->SigReg($result);
        else: //NO HAY UBICACIONES CON ESTE MATERIAL EN ESTE ALMACEN, BUSCO LA UBICACION DONDE HAYA ESTADO EL MATERIAL DENTRO DEL ALMACEN
            if (($idTipoBloqueo == NULL) || ($idTipoBloqueo == $rowTipoBloqueoPreventivo->ID_TIPO_BLOQUEO)): //OK O P
                //PARA LA PDA AÑADE UN FILTRO POR TIPOS DE BLOQUEO (OK, P, RV, RVP, RP y RPP) EN MEL y MT
                if ($rowCentroFisico->AGRUPACION_STOCK_RECEPCION == 1): //AGRUPACION DE STOCKS
                    $sqlBuscaUbicaciones = "SELECT U.ID_UBICACION, U.UBICACION, MEL.FECHA_PROCESADO AS FECHA
                                            FROM MOVIMIENTO_ENTRADA_LINEA MEL
                                            INNER JOIN UBICACION UCF ON UCF.ID_UBICACION = MEL.ID_UBICACION
                                            INNER JOIN ALMACEN ACF ON ACF.ID_ALMACEN = UCF.ID_ALMACEN
                                            INNER JOIN UBICACION U ON U.UBICACION = UCF.UBICACION
                                            WHERE ACF.ID_CENTRO_FISICO = $rowAlmacen->ID_CENTRO_FISICO AND U.ID_ALMACEN = $idAlmacen AND (U.TIPO_UBICACION_ESTANDAR = 1 OR U.TIPO_UBICACION = 'Preventivo') AND MEL.ID_MATERIAL = $idMaterial AND (MEL.ID_TIPO_BLOQUEO IS NULL OR MEL.ID_TIPO_BLOQUEO IN ($rowTipoBloqueoPreventivo->ID_TIPO_BLOQUEO, $rowTipoBloqueoReservado->ID_TIPO_BLOQUEO, $rowTipoBloqueoReservadoPlanificado->ID_TIPO_BLOQUEO, $rowTipoBloqueoReservadoPreparacion->ID_TIPO_BLOQUEO, $rowTipoBloqueoReservadoPreparacionPreventivo->ID_TIPO_BLOQUEO)) AND U.BAJA = 0
                                            UNION ALL
                                            SELECT U.ID_UBICACION, U.UBICACION, MT.FECHA AS FECHA
                                            FROM MOVIMIENTO_TRANSFERENCIA MT
                                            INNER JOIN UBICACION UCF ON UCF.ID_UBICACION = MT.ID_UBICACION_DESTINO
                                            INNER JOIN ALMACEN ACF ON ACF.ID_ALMACEN = UCF.ID_ALMACEN
                                            INNER JOIN UBICACION U ON U.UBICACION = UCF.UBICACION
                                            WHERE MT.TIPO IN ('Manual', 'Recepcion', 'ReubicacionMaterial') AND ACF.ID_CENTRO_FISICO = $rowAlmacen->ID_CENTRO_FISICO AND U.ID_ALMACEN = $idAlmacen AND (U.TIPO_UBICACION_ESTANDAR = 1 OR U.TIPO_UBICACION = 'Preventivo') AND MT.ID_MATERIAL = $idMaterial AND (MT.ID_TIPO_BLOQUEO IS NULL OR MT.ID_TIPO_BLOQUEO IN ($rowTipoBloqueoPreventivo->ID_TIPO_BLOQUEO, $rowTipoBloqueoReservado->ID_TIPO_BLOQUEO, $rowTipoBloqueoReservadoPlanificado->ID_TIPO_BLOQUEO, $rowTipoBloqueoReservadoPreparacion->ID_TIPO_BLOQUEO, $rowTipoBloqueoReservadoPreparacionPreventivo->ID_TIPO_BLOQUEO)) AND U.BAJA = 0
                                            ORDER BY FECHA DESC";
                else: //SIN AGRUPACION DE STOCKS
                    $sqlBuscaUbicaciones = "SELECT U.ID_UBICACION, U.UBICACION, MEL.FECHA_PROCESADO AS FECHA
                                            FROM MOVIMIENTO_ENTRADA_LINEA MEL
                                            INNER JOIN UBICACION U ON U.ID_UBICACION = MEL.ID_UBICACION
                                            WHERE U.ID_ALMACEN = $idAlmacen AND U.TIPO_UBICACION_ESTANDAR = 1 AND MEL.ID_MATERIAL = $idMaterial AND (MEL.ID_TIPO_BLOQUEO IS NULL OR MEL.ID_TIPO_BLOQUEO IN ($rowTipoBloqueoPreventivo->ID_TIPO_BLOQUEO, $rowTipoBloqueoReservado->ID_TIPO_BLOQUEO, $rowTipoBloqueoReservadoPlanificado->ID_TIPO_BLOQUEO, $rowTipoBloqueoReservadoPreparacion->ID_TIPO_BLOQUEO, $rowTipoBloqueoReservadoPreparacionPreventivo->ID_TIPO_BLOQUEO)) AND U.BAJA = 0
                                            UNION ALL
                                            SELECT U.ID_UBICACION, U.UBICACION, MT.FECHA AS FECHA
                                            FROM MOVIMIENTO_TRANSFERENCIA MT
                                            INNER JOIN UBICACION U ON U.ID_UBICACION = MT.ID_UBICACION_DESTINO
                                            WHERE MT.TIPO IN ('Manual', 'Recepcion', 'ReubicacionMaterial') AND U.ID_ALMACEN = $idAlmacen AND U.TIPO_UBICACION_ESTANDAR = 1 AND MT.ID_MATERIAL = $idMaterial AND (MT.ID_TIPO_BLOQUEO IS NULL OR MT.ID_TIPO_BLOQUEO IN ($rowTipoBloqueoPreventivo->ID_TIPO_BLOQUEO, $rowTipoBloqueoReservado->ID_TIPO_BLOQUEO, $rowTipoBloqueoReservadoPlanificado->ID_TIPO_BLOQUEO, $rowTipoBloqueoReservadoPreparacion->ID_TIPO_BLOQUEO, $rowTipoBloqueoReservadoPreparacionPreventivo->ID_TIPO_BLOQUEO)) AND U.BAJA = 0
                                            ORDER BY FECHA DESC";
                endif;
                $resultBuscaUbicaciones = $bd->ExecSQL($sqlBuscaUbicaciones);
                if ($bd->NumRegs($resultBuscaUbicaciones) > 0):
                    $row = $bd->SigReg($resultBuscaUbicaciones);
                endif;
            endif;
            //FIN OK/P

            if (!isset($row)): //SE OBTIENE LA PRIMERA UBICACION DEL ALMACEN
                $row = $bd->VerRegRest("UBICACION", "ID_ALMACEN = $idAlmacen AND BAJA = 0");
            endif;

        endif;

        return $row->ID_UBICACION;
    }

    function DesubicarMaterial($idAlmacen, $idMaterial, $idMaterialFisico = NULL, $idTipoBloqueo = NULL)
    {
        //COMPRUEBO EL TIPO LOTE DE UN MATERIAL EN UN ALMACEN, SI NO ESTA LO COPIO DE OTRO CENTRO, Y SI NO ESTA DEFINIDO ERROR
        global $bd;
        global $NotificaErrorPorEmail;

        //BUSCO MATERIAL ALMACEN PARA SABER EL TIPO LOTE DEL MATERIAL
        $rowMatAlm = $bd->VerRegRest("MATERIAL_ALMACEN", "ID_MATERIAL = $idMaterial AND ID_ALMACEN = $idAlmacen", "No");

        //BUSCO LA UBICACION POR DEFECTO
        if ($rowMatAlm->TIPO_LOTE != "ninguno"):
            $sqlBuscaUbicaciones = "SELECT *
															FROM MATERIAL_UBICACION MU 
															INNER JOIN UBICACION U ON U.ID_UBICACION = MU.ID_UBICACION 
															INNER JOIN MATERIAL_FISICO MF ON MF.ID_MATERIAL_FISICO = MU.ID_MATERIAL_FISICO 
															WHERE MU.ID_MATERIAL = $idMaterial AND U.TIPO_UBICACION IS NULL AND U.ID_ALMACEN = $idAlmacen AND MU.STOCK_TOTAL > 0 AND MU.ID_MATERIAL_FISICO IS NOT NULL
															AND ID_TIPO_BLOQUEO " . ($idTipoBloqueo == NULL ? 'IS NULL' : "= $idTipoBloqueo") . ($idMaterialFisico == NULL ? '' : " AND MU.ID_MATERIAL_FISICO = $idMaterialFisico") . "
															AND MF.TIPO_LOTE = '" . $rowMatAlm->TIPO_LOTE . "' 															 																														
															ORDER BY MF.FECHA_CADUCIDAD ASC, STOCK_TOTAL ASC, NUMERO_SERIE_LOTE ASC";
        else:
            $sqlBuscaUbicaciones = "SELECT *
															FROM MATERIAL_UBICACION MU 
															INNER JOIN UBICACION U ON U.ID_UBICACION = MU.ID_UBICACION 
															WHERE MU.ID_MATERIAL = $idMaterial AND U.TIPO_UBICACION IS NULL AND U.ID_ALMACEN = $idAlmacen AND MU.STOCK_TOTAL > 0
															AND ID_TIPO_BLOQUEO " . ($idTipoBloqueo == NULL ? 'IS NULL' : "= $idTipoBloqueo") . "
															AND MU.ID_MATERIAL_FISICO IS NULL 
															ORDER BY STOCK_TOTAL ASC";
        endif;
        $resultBuscaUbicaciones = $bd->ExecSQL($sqlBuscaUbicaciones);//echo($sqlBuscaUbicaciones);
        if ($bd->NumRegs($resultBuscaUbicaciones) == 0):
            $rowUbiDefecto = NULL;
            $txUbicacion   = NULL;
        else:
            $rowUbiDefecto = $bd->SigReg($resultBuscaUbicaciones);
            $txUbicacion   = $rowUbiDefecto->UBICACION;
        endif;

        return $rowUbiDefecto;
    }

    /**
     * @param $idAlmacen
     * @param $idMaterial
     * @param $cantidadConsulta
     * @param null $idMaterialFisico
     * @param null $idTipoBloqueo
     * La funcion DesubicacionMaterial no se usa en ningun lado, pero la dejamos por si acaso. A diferencia de esa funcion, ésta devuelve un array con las ubicaciones disponibles en vez de solo una row.
     * Devuelve un array con las ubicaciones necesarias para cubrir la cantidad de la peticion
     */
    function UbicacionMaterialDefecto($idAlmacen, $idMaterial, $cantidadConsulta, $idTipoBloqueo = NULL, $idMaterialFisico = NULL)
    {
        //COMPRUEBO EL TIPO LOTE DE UN MATERIAL EN UN ALMACEN, SI NO ESTA LO COPIO DE OTRO CENTRO, Y SI NO ESTA DEFINIDO ERROR
        global $bd;
        global $NotificaErrorPorEmail;

        //VARIABLE PARA DEVOLVER LOS DATOS POR DEFECTO DE DONDE DESUBICAR EL MATERIAL
        $arrDesubicacionDefecto = array();

        //BUSCO MATERIAL ALMACEN PARA SABER EL TIPO LOTE DEL MATERIAL
        $rowMatAlm = $bd->VerRegRest("MATERIAL_ALMACEN", "ID_MATERIAL = $idMaterial AND ID_ALMACEN = $idAlmacen", "No");

        //BUSCO LA UBICACION POR DEFECTO
        if ($rowMatAlm->TIPO_LOTE != "ninguno"):
            $sqlBuscaUbicaciones = "SELECT MU.ID_MATERIAL_UBICACION, U.ID_UBICACION, U.UBICACION, MU.ID_MATERIAL_FISICO, MU.ID_ORDEN_TRABAJO_MOVIMIENTO, MU.ID_INCIDENCIA_CALIDAD, MU.STOCK_TOTAL
															FROM MATERIAL_UBICACION MU
															INNER JOIN UBICACION U ON U.ID_UBICACION = MU.ID_UBICACION
															INNER JOIN MATERIAL_FISICO MF ON MF.ID_MATERIAL_FISICO = MU.ID_MATERIAL_FISICO
															WHERE MU.ACTIVO = 1 AND U.ID_ALMACEN = $idAlmacen AND MU.ID_MATERIAL = $idMaterial AND U.VALIDA_STOCK_DISPONIBLE = 1 AND MU.ID_MATERIAL_FISICO IS NOT NULL
															AND MU.ID_TIPO_BLOQUEO " . ($idTipoBloqueo == NULL ? 'IS NULL' : "= $idTipoBloqueo") . ($idMaterialFisico == NULL ? '' : " AND MU.ID_MATERIAL_FISICO = $idMaterialFisico") . "
															AND MF.TIPO_LOTE = '" . $rowMatAlm->TIPO_LOTE . "'
															ORDER BY MF.FECHA_CADUCIDAD ASC, U.TIPO_UBICACION ASC, STOCK_TOTAL ASC, NUMERO_SERIE_LOTE ASC";
        else:
            $sqlBuscaUbicaciones = "SELECT MU.ID_MATERIAL_UBICACION, U.ID_UBICACION, U.UBICACION, MU.ID_MATERIAL_FISICO, MU.ID_ORDEN_TRABAJO_MOVIMIENTO, MU.ID_INCIDENCIA_CALIDAD, MU.STOCK_TOTAL
															FROM MATERIAL_UBICACION MU
															INNER JOIN UBICACION U ON U.ID_UBICACION = MU.ID_UBICACION
															WHERE MU.ACTIVO = 1 AND U.ID_ALMACEN = $idAlmacen AND MU.ID_MATERIAL = $idMaterial AND U.VALIDA_STOCK_DISPONIBLE = 1
															AND MU.ID_TIPO_BLOQUEO " . ($idTipoBloqueo == NULL ? 'IS NULL' : "= $idTipoBloqueo") . "
															AND MU.ID_MATERIAL_FISICO IS NULL
															ORDER BY U.TIPO_UBICACION ASC,STOCK_TOTAL ASC";
        endif;
        //EJECUTO LA CONSULTA DE LAS UBICACIONES
        $resultBuscaUbicaciones = $bd->ExecSQL($sqlBuscaUbicaciones);

        //VARIABLE PARA CONTROLAR EL INDICE
        $indice = 0;

        //RECORRO LAS UBICACIONES PARA RESERVAR EL MATERIAL NECESARIO
        while (($rowBuscaUbicacion = $bd->SigReg($resultBuscaUbicaciones)) && ($cantidadConsulta > EPSILON_SISTEMA)):

            //PRIMERO COMPRUEBO QUE EL MATERIAL Y UBICACION NO ESTEN INCLUIDOS EN UNA ORDEN DE CONTEO ACTIVA
            $sqlDatosIncluidosEnOrdenesConteoActivas    = "SELECT COUNT(*) AS NUM
                                                        FROM INVENTARIO_ORDEN_CONTEO_LINEA IOCL
                                                        INNER JOIN INVENTARIO_ORDEN_CONTEO IOC ON IOC.ID_INVENTARIO_ORDEN_CONTEO = IOCL.ID_INVENTARIO_ORDEN_CONTEO
                                                        WHERE IOCL.ID_MATERIAL = $idMaterial AND IOCL.ID_UBICACION = $rowBuscaUbicacion->ID_UBICACION AND IOCL.BAJA = 0 AND IOC.TIPO = 'Inventario' AND IOC.ESTADO <> 'Finalizado' AND IOC.BAJA = 0";
            $resultDatosIncluidosEnOrdenesConteoActivas = $bd->ExecSQL($sqlDatosIncluidosEnOrdenesConteoActivas);   //echo($sqlDatosIncluidosEnOrdenesConteoActivas);exit;
            $rowDatosIncluidosEnOrdenesConteoActivas    = $bd->SigReg($resultDatosIncluidosEnOrdenesConteoActivas);
            if ($rowDatosIncluidosEnOrdenesConteoActivas->NUM > 0): //SI LOS DATOS ESTAN INCLUIDOS EN UNA ORDEN DE CONTEO ACTIVA ME SALTO LA UBICACION
                continue;
            endif;

            //CALCULO LA CANTIDAD A PREPARAR DE LA UBICACION CORRESPONDIENTE
            if ($rowBuscaUbicacion->STOCK_TOTAL > $cantidadConsulta):
                $cantidadLinea = $cantidadConsulta;
            else:
                $cantidadLinea = $rowBuscaUbicacion->STOCK_TOTAL;
            endif;

            //RELLENO EL ARRAY
            $arrDesubicacionDefecto[$indice]["ID_MATERIAL_UBICACION"] = $rowBuscaUbicacion->ID_MATERIAL_UBICACION;
            $arrDesubicacionDefecto[$indice]["CANTIDAD"]              = $cantidadLinea;

            //ACTUALIZO EL INDICE
            $indice = $indice + 1;

            //ACTUALIZO LA CANTIDAD PENDIENTE DE PREPARAR
            $cantidadConsulta = $cantidadConsulta - $cantidadLinea;

        endwhile;

        //SI LA CANTIDAD PENDIENTE NO ES CERO DEVOLVEMOS NULL (NO SE PUEDE RESERVAR EL MATERIAL NECESARIO PARA LA LINEA)
        if ($cantidadConsulta > EPSILON_SISTEMA):
            $arrDesubicacionDefecto = NULL;
        endif;

        return $arrDesubicacionDefecto;
    }

    function UbicacionDefectoRecepcionPedidosTraslado($idAlmacen, $idAlbaranLinea, $listaUbicacionesUtilizadas = 0, $idTipoBloqueo = NULL, $tipoPedido = NULL, $idPedidoSalida = NULL)
    {
        global $bd;
        global $html;
        global $mat;
        global $administrador;//exit($idAlmacen);

        //VARIABLE PARA SABER SI LA PIEZA ES ACHATARRAR
        $materialAchatarrar = 0;

        //VARIABLE PARA SABER SI LA PIEZA ES DE CALIDAD
        $materialCalidad = 0;

        //VARIABLE PARA SABER SI LA PIEZA TIENE ASIGNADA UNA DIRECCION DE PROVEEDOR
        $idProveedorEnvio = NULL;

        //BUSCO EL ALMACEN
        $rowAlmacen = $bd->VerReg("ALMACEN", "ID_ALMACEN", $idAlmacen);

        //BUSCO EL CENTRO FISICO
        $rowCentroFisico = $bd->VerReg("CENTRO_FISICO", "ID_CENTRO_FISICO", $rowAlmacen->ID_CENTRO_FISICO);

        //BUSCO LA LINEA DE ALBARAN
        $rowLineaAlbaran = $bd->VerReg("ALBARAN_LINEA", "ID_ALBARAN_LINEA", $idAlbaranLinea);

        //BUSCO EL ALBARAN
        $rowAlbaran = $bd->VerReg("ALBARAN", "ID_ALBARAN", $rowLineaAlbaran->ID_ALBARAN);

        //BUSCO LA ORDEN DE RECOGIDA
        $rowOrdenRecogida = $bd->VerReg("EXPEDICION", "ID_EXPEDICION", $rowAlbaran->ID_EXPEDICION);

        //BUSCO EL TIPO Y SUBTIPO DE LA ORDEN DE RECOGIDA
        $tipoOrdenRecogida    = $rowOrdenRecogida->TIPO_ORDEN_RECOGIDA;
        $subtipoOrdenRecogida = $rowOrdenRecogida->SUBTIPO_ORDEN_RECOGIDA;

        //BUSCO EL MATERIAL
        $rowMaterial = $bd->VerReg("MATERIAL", "ID_MATERIAL", $rowLineaAlbaran->ID_MATERIAL);

        //BUSCO EL MATERIAL ALMACEN
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowMaterialAlmacen               = $bd->VerRegRest("MATERIAL_ALMACEN", "ID_MATERIAL = $rowMaterial->ID_MATERIAL AND ID_ALMACEN = $rowAlmacen->ID_ALMACEN", "No");
        if ($rowMaterialAlmacen == false):
            //GENERAMOS UNA COPIA DEL MATERIAL_ALMACEN ENCONTRADO
            $idMaterialAlmacen = $mat->ClonarMaterialAlmacen($rowMaterial->ID_MATERIAL, $rowAlmacen->ID_ALMACEN, "Recepcion Pedidos Traslado");

            if ($idMaterialAlmacen != false):
                //AÑADO EL MATERIAL ALMACEN AL ARRAY PARA NOTIFICAR A LAS PERSONA CORRESPONDIENTES
                $arrMaterialAlmacen[$rowMaterial->ID_MATERIAL . "_" . $rowAlmacen->ID_ALMACEN] = 1;

                //RECUPERO EL OBJETO CREADO
                $rowMaterialAlmacen = $bd->VerReg("MATERIAL_ALMACEN", "ID_MATERIAL_ALMACEN", $idMaterialAlmacen);
            endif;
        endif;
        unset($GLOBALS["NotificaErrorPorEmail"]);

        //COMPRUEBO QUE EXISTA MATERIAL ALMACEN ORIGEN
        if ($rowMaterialAlmacen == false):
            global $strError;
            $strError = $strError . $auxiliar->traduce("El material", $administrador->ID_IDIOMA) . " $rowMaterial->REFERENCIA_SGA - " . ($administrador->ID_IDIOMA == "ESP" ? $rowMaterial->DESCRIPCION : $rowMaterial->DESCRIPCION_EN) . " " . $auxiliar->traduce("no está definido para el almacén", $administrador->ID_IDIOMA) . " $rowAlmacen->REFERENCIA - $rowAlmacen->NOMBRE.<br>";
            $html->PagError("MaterialAlmacenNoDefinido");
        endif;

        //BUSCO EL MATERIAL FISICO EN CASO DE NO SER NULO
        if ($rowLineaAlbaran->ID_MATERIAL_FISICO != NULL):
            //BUSCO EL MATERIAL FISICO
            $rowMaterialFisico = $bd->VerReg("MATERIAL_FISICO", "ID_MATERIAL_FISICO", $rowLineaAlbaran->ID_MATERIAL_FISICO);
        endif;

        //BUSCO EL TIPO DE BLOQUEO PREVENTIVO
        $rowTipoBloqueoPreventivo = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "SP");
        //BUSCO EL TIPO DE BLOQUEO NO REPARABLE NO GARANTIA
        $rowTipoBloqueoNoReparablaNoGarantia = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "QNRNG");
        //BUSCO EL TIPO DE BLOQUEO RESERVADO
        $rowTipoBloqueoReservado = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "RV");
        //BUSCO EL TIPO DE BLOQUEO RESERVADO PLANIFICADO
        $rowTipoBloqueoReservadoPlanificado = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "RVP");

        //BUSCO EL TIPO DE BLOQUEO EN CASO DE NO SER NULO
        if ($rowLineaAlbaran->ID_TIPO_BLOQUEO != NULL):
            //BUSCO EL TIPO DE BLOQUEO
            $rowTipoBloqueo = $bd->VerReg("TIPO_BLOQUEO", "ID_TIPO_BLOQUEO", $rowLineaAlbaran->ID_TIPO_BLOQUEO);

            //SI EL TIPO DE BLOQUEO ES NO REPARABLE: QNRNG (NR) EL TIPO DE UBICACION ES ACHATARRAR
            if ($rowTipoBloqueo->ID_TIPO_BLOQUEO == $rowTipoBloqueoNoReparablaNoGarantia->ID_TIPO_BLOQUEO):
                $materialAchatarrar = 1;
            endif;

            //SI EL TIPO DE BLOQUEO ES DE CALIDAD (EMPIEZA POR X) EL MATERIAL SE RECEPCIONARA EN LA UBICACION DE TIPO 'Traslado Calidad'
            if (substr((string)$rowTipoBloqueo->TIPO_BLOQUEO_INTERNO, 0, 1) == 'X'):
                $materialCalidad = 1;
            endif;
        endif;

        //BUSCO EL MOVIMIENTO DE LA ORDEN DE TRABAJO EN CASO DE NO SER NULO
        if ($rowLineaAlbaran->ID_ORDEN_TRABAJO_MOVIMIENTO != NULL):
            //BUSCO EL MOVIMIENTO DE LA ORDEN DE TRABAJO
            $rowOrdenTrabajoMovimiento = $bd->VerReg("ORDEN_TRABAJO_MOVIMIENTO", "ID_ORDEN_TRABAJO_MOVIMIENTO", $rowLineaAlbaran->ID_ORDEN_TRABAJO_MOVIMIENTO);

            //RECUPERO LA DIRECCION DE ENVIO DEL MATERIAL
            $idProveedorEnvio = $rowOrdenTrabajoMovimiento->ID_PROVEEDOR_GARANTIA;
        endif;

        //BUSCO LA INCIDENCIA DE CALIDAD EN CASO DE NO SER NULO
        if ($rowLineaAlbaran->ID_INCIDENCIA_CALIDAD != NULL):
            //BUSCO LA INCIDENCIA DE CALIDAD
            $rowIncidenciaCalidad = $bd->VerReg("INCIDENCIA_CALIDAD", "ID_INCIDENCIA_CALIDAD", $rowLineaAlbaran->ID_INCIDENCIA_CALIDAD);
        endif;

        //SI SE RETORNA EL MATERIAL ESTROPEADO DESDE PROVEEDOR, LA UBICACION PROPUESTA DEPENDE DE LA DECISION TOMADA POR EL COMPRADOR
        if (($tipoOrdenRecogida == 'Recogida en Proveedor') && ($subtipoOrdenRecogida == 'Retorno Material Estropeado desde Proveedor')):
            //BUSCO LA LINEA DEL MOVIMIETO DE SALIDA DEL PROVEEDOR AL ALMACEN DE DESTINO
            $rowLineaMovimientoRetornoMaterialEstropeado = $bd->VerRegRest("MOVIMIENTO_SALIDA_LINEA", "ID_ALBARAN_LINEA = $rowLineaAlbaran->ID_ALBARAN_LINEA AND BAJA = 0", "No");

            //BUSCO LA LINEA ORIGINAL DEL ALMACEN AL PROVEEDOR
            $rowLineaMovimientoEnvioAlmacenProveedor = $bd->VerRegRest("MOVIMIENTO_SALIDA_LINEA", "ID_MOVIMIENTO_SALIDA_LINEA = $rowLineaMovimientoRetornoMaterialEstropeado->ID_MOVIMIENTO_SALIDA_LINEA_MATERIAL_ESTROPEADO AND BAJA = 0", "No");

            //SI LA DECISION FUE ACHATARRAR EN EL ALMACEN PRINCIPAL, LA UBICACION PROPUESTA ES LA DE ACHATARRAMIENTO
            if ($rowLineaMovimientoEnvioAlmacenProveedor->DECISION_COMPRADOR == 'Achatarrar en Almacén Principal'):
                $materialAchatarrar = 1;
            elseif ($rowLineaMovimientoEnvioAlmacenProveedor->DECISION_COMPRADOR == 'Otro Proveedor'):
                $idProveedorEnvio = $rowLineaMovimientoEnvioAlmacenProveedor->ID_OTRO_PROVEEDOR_GARANTIA;
            endif;
        endif;

        //SI EL PEDIDO ES PENDIENTES DE ORDENES DE TRABAJO, INTENTAREMOS UBICAR TODOS LOS MATERIALES DE LA ORDEN DE TRABAJO JUNTOS
        $tipoPedidoPendientes = false;
        $idOrdenTrabajo       = NULL;
        if ($tipoPedido == 'Pendientes'):
            $sqlWhereOT = "";
            if ($idPedidoSalida != NULL):
                $sqlWhereOT = "AND OTL.ID_PEDIDO_SALIDA = $idPedidoSalida";
            endif;

            $sqlTipoPedido    = "SELECT DISTINCT PS.TIPO_PEDIDO, OTL.ID_ORDEN_TRABAJO
                                  FROM ALBARAN_LINEA AL
                                  INNER JOIN MOVIMIENTO_SALIDA_LINEA MSL ON MSL.ID_ALBARAN_LINEA = AL.ID_ALBARAN_LINEA
                                  INNER JOIN PEDIDO_SALIDA_LINEA PSL ON PSL.ID_PEDIDO_SALIDA_LINEA = MSL.ID_PEDIDO_SALIDA_LINEA
                                  INNER JOIN PEDIDO_SALIDA PS ON PS.ID_PEDIDO_SALIDA = PSL.ID_PEDIDO_SALIDA
                                  INNER JOIN ORDEN_TRABAJO_LINEA OTL ON OTL.ID_ORDEN_TRABAJO_LINEA = PSL.ID_ORDEN_TRABAJO_LINEA
                                  WHERE AL.ID_ALBARAN_LINEA = $rowLineaAlbaran->ID_ALBARAN_LINEA $sqlWhereOT AND OTL.ID_ALMACEN = $rowAlmacen->ID_ALMACEN";
            $resultTipoPedido = $bd->ExecSQL($sqlTipoPedido);
            while ($rowTipoPedido = $bd->SigReg($resultTipoPedido)):
                if ($rowTipoPedido->TIPO_PEDIDO == 'Pendientes de Ordenes Trabajo'):
                    $tipoPedidoPendientes = true;
                    $idOrdenTrabajo       = $rowTipoPedido->ID_ORDEN_TRABAJO;
                    break;
                endif;
            endwhile;
        endif;

        //SI EL CENTRO FISICO ESTÁ MARCADO COMO PRINCIPAL REALIZAMOS LA BUSQUEDA DE MATERIAL DE CORRECTIVO Y PREVENTIVO DE FORMA CONJUNTA
        if ($rowCentroFisico->AGRUPACION_STOCK_RECEPCION == 1):
            //VARIABLE PARA SABER SI HA ENTRADO EN LA CONUSLTA DE LOS TIPOS DE BLOQUEO OK (CORRCETIVOS)
            $tipoCorrectivoPreventivo = false;

            //DETERMINO LOS TIPOS DE UBICACION ESPECIAL VALIDOS
            $tiposUbicacionValidos             = "'Preventivo', 'Achatarrar', 'Traslado Calidad', 'Construcción', 'Material Estropeado'";
            $tiposUbicacionValidosNoAchatarrar = "'Preventivo', 'Traslado Calidad', 'Construcción', 'Material Estropeado'";

            $whereUbicacionesUtilizadas = "";
            if ($listaUbicacionesUtilizadas != 0):
                $whereUbicacionesUtilizadas = "AND U.ID_UBICACION NOT IN ($listaUbicacionesUtilizadas)";
            endif;

            //GENERO LA CONSULTA DE BUSQUEDA DE UBICACIONES
            if ($rowAlmacen->CATEGORIA_ALMACEN == 'Construccion: Instalacion'): //RECEPCION EN ALMACEN DE CONTRUCCION
                $sqlBuscaUbicaciones = "SELECT U.ID_UBICACION, U.UBICACION
                                            FROM UBICACION U
                                            WHERE U.ID_ALMACEN = $rowAlmacen->ID_ALMACEN AND U.TIPO_UBICACION = 'Construcción' $whereUbicacionesUtilizadas AND U.BAJA = 0";

            elseif ($tipoPedidoPendientes == true): //BUSCAMOS EL REGISTRO EN LA TABLA QUE RELACIONA OTs Y UBICACIONES
                $sqlBuscaUbicaciones    = "SELECT DISTINCT U.ID_UBICACION, U.UBICACION
                                               FROM ORDEN_TRABAJO_UBICACION_TRANSFERENCIA OTUT
                                               INNER JOIN UBICACION U ON U.ID_UBICACION = OTUT.ID_UBICACION
                                               WHERE OTUT.ID_ORDEN_TRABAJO = $idOrdenTrabajo AND OTUT.BAJA = 0 AND U.ID_ALMACEN = $rowAlmacen->ID_ALMACEN $whereUbicacionesUtilizadas AND U.BAJA = 0
                                               ORDER BY OTUT.ULTIMA_UBICACION_UTILIZADA DESC";
                $resultBuscaUbicaciones = $bd->ExecSQL($sqlBuscaUbicaciones);

                if ($bd->NumRegs($resultBuscaUbicaciones) == 0): //SI NO HAY NADA TRANSFERIDO DE ESA OT, NO DEVOLVEMOS NINGUNA UBICACION
                    return NULL;
                endif;

            //BLOQUEO OK Y PREVENTIVO BUSCAN EN LAS MISMAS UBICACIONEs (AccTASK000006)
            elseif (($rowLineaAlbaran->ID_TIPO_BLOQUEO == NULL || $rowLineaAlbaran->ID_TIPO_BLOQUEO == $rowTipoBloqueoPreventivo->ID_TIPO_BLOQUEO)): //TIPO BLOQUEO OK
                //BUSCAMOS LA UBICACION CON MAS STOCK EN EL CENTRO FISICO Y CUYA REFERENCIA DE UBICACION EXISTA EN EL ALMACEN DE DESTINO
                $sqlBuscaUbicaciones      = "SELECT U.ID_UBICACION, U.UBICACION, U.NOMBRE_MAQUINA
                                            FROM MATERIAL_UBICACION MU
                                            INNER JOIN UBICACION UCF ON UCF.ID_UBICACION = MU.ID_UBICACION
                                            INNER JOIN ALMACEN ACF ON ACF.ID_ALMACEN = UCF.ID_ALMACEN
                                            INNER JOIN UBICACION U ON U.UBICACION = UCF.UBICACION
                                            WHERE ACF.ID_CENTRO_FISICO = $rowAlmacen->ID_CENTRO_FISICO AND U.ID_ALMACEN = $rowAlmacen->ID_ALMACEN AND MU.ID_MATERIAL = $rowMaterial->ID_MATERIAL AND (U.TIPO_UBICACION_ESTANDAR = 1 OR U.TIPO_UBICACION = 'Preventivo') AND (MU.ID_TIPO_BLOQUEO IS NULL OR MU.ID_TIPO_BLOQUEO = $rowTipoBloqueoPreventivo->ID_TIPO_BLOQUEO OR MU.ID_TIPO_BLOQUEO = $rowTipoBloqueoReservado->ID_TIPO_BLOQUEO OR MU.ID_TIPO_BLOQUEO = $rowTipoBloqueoReservadoPlanificado->ID_TIPO_BLOQUEO) AND MU.ACTIVO = 1 $whereUbicacionesUtilizadas AND U.BAJA = 0 AND UCF.BAJA = 0
                                            ORDER BY MU.STOCK_TOTAL DESC, U.UBICACION ASC
                                            LIMIT 1";
                $tipoCorrectivoPreventivo = true;

            elseif ($materialAchatarrar == 1): //TIPO BLOQUEO ACHATARRAR
                //VARIABLE PARA SABER SI EXISTEN UBICACIONES PARA PROPONER
                $existenUbicacionesPropuestas = false;

                //SI NO EXISTEN UBICACIONES PARA PROPONER, BUSCO UBICACIONES DONDE EXISTA MATERIAL ESTROPEADO DE LA MISMA REFERENCIA Y TIPO DE BLOQUEO
                if ($existenUbicacionesPropuestas == false):
                    //BUSCO UBICACIONES DONDE EXISTA MATERIAL ESTROPEADO DE LA MISMA REFERENCIA Y TIPO DE BLOQUEO
                    $sqlBuscaUbicaciones    = "SELECT U.ID_UBICACION, U.UBICACION
                                                FROM MATERIAL_UBICACION MU
                                                INNER JOIN UBICACION U ON U.ID_UBICACION = MU.ID_UBICACION
                                                WHERE U.ID_ALMACEN = $rowAlmacen->ID_ALMACEN AND (U.TIPO_UBICACION IS NULL OR U.TIPO_UBICACION IN ($tiposUbicacionValidos)) AND MU.ID_MATERIAL = $rowMaterial->ID_MATERIAL AND MU.ID_TIPO_BLOQUEO = $rowTipoBloqueo->ID_TIPO_BLOQUEO AND MU.ACTIVO = 1 $whereUbicacionesUtilizadas AND U.BAJA = 0
                                                ORDER BY MU.STOCK_TOTAL DESC, U.UBICACION ASC";
                    $resultBuscaUbicaciones = $bd->ExecSQL($sqlBuscaUbicaciones);
                    if ($bd->NumRegs($resultBuscaUbicaciones) > 0):
                        //ACTUALIZO LA VARIABLE PARA SABER SI EXISTEN UBICACIONES PARA PROPONER
                        $existenUbicacionesPropuestas = true;
                    endif;
                endif;

                //SI NO EXISTEN UBICACIONES PARA PROPONER, BUSCO UBICACIONES DONDE HAYA EXISTIDO MATERIAL ESTROPEADO DE LA MISMA REFERENCIA Y TIPO DE BLOQUEO
                if ($existenUbicacionesPropuestas == false):
                    //BUSCO UBICACIONES DONDE HAYA EXISTIDO MATERIAL ESTROPEADO DE LA MISMA REFERENCIA Y TIPO DE BLOQUEO
                    $sqlBuscaUbicaciones    = "SELECT U.ID_UBICACION, U.UBICACION
                                                FROM MATERIAL_UBICACION MU
                                                INNER JOIN UBICACION U ON U.ID_UBICACION = MU.ID_UBICACION
                                                WHERE U.ID_ALMACEN = $rowAlmacen->ID_ALMACEN AND (U.TIPO_UBICACION IS NULL OR U.TIPO_UBICACION IN ($tiposUbicacionValidos)) AND MU.ID_MATERIAL = $rowMaterial->ID_MATERIAL AND MU.ID_TIPO_BLOQUEO = $rowTipoBloqueo->ID_TIPO_BLOQUEO AND MU.ACTIVO = 0 $whereUbicacionesUtilizadas AND U.BAJA = 0
                                                ORDER BY U.UBICACION ASC";
                    $resultBuscaUbicaciones = $bd->ExecSQL($sqlBuscaUbicaciones);
                    if ($bd->NumRegs($resultBuscaUbicaciones) > 0):
                        //ACTUALIZO LA VARIABLE PARA SABER SI EXISTEN UBICACIONES PARA PROPONER
                        $existenUbicacionesPropuestas = true;
                    endif;
                endif;
            elseif ($materialCalidad == 1): //TIPO BLOQUEO CALIDAD
                $sqlBuscaUbicaciones = "SELECT U.ID_UBICACION, U.UBICACION
                                            FROM UBICACION U
                                            WHERE U.ID_ALMACEN = $rowAlmacen->ID_ALMACEN AND U.TIPO_UBICACION = 'Traslado Calidad' $whereUbicacionesUtilizadas AND U.BAJA = 0";
            elseif ($idProveedorEnvio != NULL): //MATERIAL CON DIRECCION DE ENVIO, EL MATERIAL SE AGRUPA EN UBICACIONES PARA ENVIAR A ESE PROVEEDOR
                //BUSCO EL TIPO DE BLOQUEO NO REPARABLE NO GARANTIA
                $rowTipoBloqueoNoReparableNoGarantia = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "QNRNG");
                //BUSCO EL TIPO DE BLOQUEO RETENIDO POR CALIDAD NO PREVENTIVO NO REPARABLE NO GARANTIA
                $rowTipoBloqueoRetenidoCalidadNoPreventivoNoReparableNoEnGarantia = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "XRCNRNG");

                //VARIABLE PARA SABER SI EXISTEN UBICACIONES PARA PROPONER
                $existenUbicacionesPropuestas = false;

                //SI NO EXISTEN UBICACIONES PARA PROPONER, BUSCO UBICACIONES DONDE EXISTA MATERIAL ESTROPEADO CON DIRECCION EL PROVEEDOR GARANTIA
                if ($existenUbicacionesPropuestas == false):
                    //BUSCO UBICACIONES DONDE EXISTA MATERIAL ESTROPEADO CON DIRECCION EL PROVEEDOR GARANTIA Y EL TIPO DE BLOQUEO SE DIFERENTE DE NO REPARABLE
                    $sqlBuscaUbicaciones    = "SELECT U.ID_UBICACION, U.UBICACION
                                                FROM MATERIAL_UBICACION MU
                                                INNER JOIN UBICACION U ON U.ID_UBICACION = MU.ID_UBICACION
                                                INNER JOIN ORDEN_TRABAJO_MOVIMIENTO OTM ON OTM.ID_ORDEN_TRABAJO_MOVIMIENTO = MU.ID_ORDEN_TRABAJO_MOVIMIENTO
                                                WHERE U.ID_ALMACEN = $rowAlmacen->ID_ALMACEN AND (U.TIPO_UBICACION_ESTANDAR = 1 OR U.TIPO_UBICACION IN ($tiposUbicacionValidosNoAchatarrar)) AND OTM.ID_PROVEEDOR_GARANTIA = $idProveedorEnvio AND MU.ACTIVO = 1 $whereUbicacionesUtilizadas AND U.BAJA = 0 AND MU.ID_TIPO_BLOQUEO <> $rowTipoBloqueoNoReparableNoGarantia->ID_TIPO_BLOQUEO AND MU.ID_TIPO_BLOQUEO <> $rowTipoBloqueoRetenidoCalidadNoPreventivoNoReparableNoEnGarantia->ID_TIPO_BLOQUEO
                                                GROUP BY MU.ID_UBICACION
                                                ORDER BY SUM(MU.STOCK_TOTAL) DESC, U.UBICACION ASC";
                    $resultBuscaUbicaciones = $bd->ExecSQL($sqlBuscaUbicaciones);
                    if ($bd->NumRegs($resultBuscaUbicaciones) > 0):
                        //ACTUALIZO LA VARIABLE PARA SABER SI EXISTEN UBICACIONES PARA PROPONER
                        $existenUbicacionesPropuestas = true;
                    endif;
                endif;

                //SI NO EXISTEN UBICACIONES PARA PROPONER, BUSCO UBICACIONES DONDE EXISTA MATERIAL ESTROPEADO DE LA MISMA REFERENCIA Y TIPO DE BLOQUEO
                if ($existenUbicacionesPropuestas == false):
                    //BUSCO UBICACIONES DONDE EXISTA MATERIAL ESTROPEADO DE LA MISMA REFERENCIA Y TIPO DE BLOQUEO
                    $sqlBuscaUbicaciones    = "SELECT U.ID_UBICACION, U.UBICACION
                                                FROM MATERIAL_UBICACION MU
                                                INNER JOIN UBICACION U ON U.ID_UBICACION = MU.ID_UBICACION
                                                WHERE U.ID_ALMACEN = $rowAlmacen->ID_ALMACEN AND (U.TIPO_UBICACION_ESTANDAR = 1 OR U.TIPO_UBICACION IN ($tiposUbicacionValidosNoAchatarrar)) AND MU.ID_MATERIAL = $rowMaterial->ID_MATERIAL AND MU.ID_TIPO_BLOQUEO = $rowTipoBloqueo->ID_TIPO_BLOQUEO AND MU.ACTIVO = 1 $whereUbicacionesUtilizadas AND U.BAJA = 0 AND MU.ID_TIPO_BLOQUEO <> $rowTipoBloqueoNoReparableNoGarantia->ID_TIPO_BLOQUEO AND MU.ID_TIPO_BLOQUEO <> $rowTipoBloqueoRetenidoCalidadNoPreventivoNoReparableNoEnGarantia->ID_TIPO_BLOQUEO
                                                ORDER BY MU.STOCK_TOTAL DESC, U.UBICACION ASC";
                    $resultBuscaUbicaciones = $bd->ExecSQL($sqlBuscaUbicaciones);
                    if ($bd->NumRegs($resultBuscaUbicaciones) > 0):
                        //ACTUALIZO LA VARIABLE PARA SABER SI EXISTEN UBICACIONES PARA PROPONER
                        $existenUbicacionesPropuestas = true;
                    endif;
                endif;

                //SI NO EXISTEN UBICACIONES PARA PROPONER, BUSCO UBICACIONES DONDE HAYA EXISTIDO MATERIAL ESTROPEADO CON DIRECCION EL PROVEEDOR GARANTIA
                if ($existenUbicacionesPropuestas == false):
                    //BUSCO UBICACIONES DONDE HAYA EXISTIDO MATERIAL ESTROPEADO CON DIRECCION EL PROVEEDOR GARANTIA
                    $sqlBuscaUbicaciones    = "SELECT U.ID_UBICACION, U.UBICACION
                                                FROM MATERIAL_UBICACION MU
                                                INNER JOIN UBICACION U ON U.ID_UBICACION = MU.ID_UBICACION
                                                INNER JOIN ORDEN_TRABAJO_MOVIMIENTO OTM ON OTM.ID_ORDEN_TRABAJO_MOVIMIENTO = MU.ID_ORDEN_TRABAJO_MOVIMIENTO
                                                WHERE U.ID_ALMACEN = $rowAlmacen->ID_ALMACEN AND (U.TIPO_UBICACION_ESTANDAR = 1 OR U.TIPO_UBICACION IN ($tiposUbicacionValidosNoAchatarrar)) AND OTM.ID_PROVEEDOR_GARANTIA = $idProveedorEnvio AND MU.ACTIVO = 0 $whereUbicacionesUtilizadas AND U.BAJA = 0 AND MU.ID_TIPO_BLOQUEO <> $rowTipoBloqueoNoReparableNoGarantia->ID_TIPO_BLOQUEO AND MU.ID_TIPO_BLOQUEO <> $rowTipoBloqueoRetenidoCalidadNoPreventivoNoReparableNoEnGarantia->ID_TIPO_BLOQUEO
                                                ORDER BY U.UBICACION ASC";
                    $resultBuscaUbicaciones = $bd->ExecSQL($sqlBuscaUbicaciones);
                    if ($bd->NumRegs($resultBuscaUbicaciones) > 0):
                        //ACTUALIZO LA VARIABLE PARA SABER SI EXISTEN UBICACIONES PARA PROPONER
                        $existenUbicacionesPropuestas = true;
                    endif;
                endif;

                //SI NO EXISTEN UBICACIONES PARA PROPONER, BUSCO UBICACIONES DONDE HAYA EXISTIDO MATERIAL ESTROPEADO DE LA MISMA REFERENCIA Y TIPO DE BLOQUEO
                if ($existenUbicacionesPropuestas == false):
                    //BUSCO UBICACIONES DONDE HAYA EXISTIDO MATERIAL ESTROPEADO DE LA MISMA REFERENCIA Y TIPO DE BLOQUEO
                    $sqlBuscaUbicaciones    = "SELECT U.ID_UBICACION, U.UBICACION
                                                FROM MATERIAL_UBICACION MU
                                                INNER JOIN UBICACION U ON U.ID_UBICACION = MU.ID_UBICACION
                                                WHERE U.ID_ALMACEN = $rowAlmacen->ID_ALMACEN AND (U.TIPO_UBICACION_ESTANDAR = 1 OR U.TIPO_UBICACION IN ($tiposUbicacionValidosNoAchatarrar)) AND MU.ID_MATERIAL = $rowMaterial->ID_MATERIAL AND MU.ID_TIPO_BLOQUEO = $rowTipoBloqueo->ID_TIPO_BLOQUEO AND MU.ACTIVO = 0 $whereUbicacionesUtilizadas AND U.BAJA = 0 AND MU.ID_TIPO_BLOQUEO <> $rowTipoBloqueoNoReparableNoGarantia->ID_TIPO_BLOQUEO AND MU.ID_TIPO_BLOQUEO <> $rowTipoBloqueoRetenidoCalidadNoPreventivoNoReparableNoEnGarantia->ID_TIPO_BLOQUEO
                                                ORDER BY U.UBICACION ASC";
                    $resultBuscaUbicaciones = $bd->ExecSQL($sqlBuscaUbicaciones);
                    if ($bd->NumRegs($resultBuscaUbicaciones) > 0):
                        //ACTUALIZO LA VARIABLE PARA SABER SI EXISTEN UBICACIONES PARA PROPONER
                        $existenUbicacionesPropuestas = true;
                    endif;
                endif;

            else: //POR DEFECTO QUE COINCIDA EL TIPO DE BLOQUEO
                $sqlBuscaUbicaciones = "SELECT U.ID_UBICACION, U.UBICACION
                                            FROM MATERIAL_UBICACION MU
                                            INNER JOIN UBICACION U ON U.ID_UBICACION = MU.ID_UBICACION
                                            WHERE U.ID_ALMACEN = $rowAlmacen->ID_ALMACEN AND (U.TIPO_UBICACION_ESTANDAR = 1 OR U.TIPO_UBICACION IN ($tiposUbicacionValidos)) AND MU.ID_MATERIAL = $rowMaterial->ID_MATERIAL AND MU.ID_TIPO_BLOQUEO = $rowTipoBloqueo->ID_TIPO_BLOQUEO $whereUbicacionesUtilizadas AND U.BAJA = 0
                                            ORDER BY MU.STOCK_TOTAL DESC, U.UBICACION ASC";
            endif;

            //SI NO ESTA DEFINIDA LA CONSULTA, RETORNO NULO
            if (!(isset($sqlBuscaUbicaciones))):
                return NULL;
            else:
                //echo($sqlBuscaUbicaciones);
            endif;

            if ($resultBuscaUbicaciones != NULL):
                //SI SE EJECUTÓ PREVIAMENTE LA CONSULTA, HACEMOS QUE EL PUNTERO APUNTE AL PRIMER REGISTRO
                $bd->Mover($resultBuscaUbicaciones, 0);
            else:
                //SI NO SE HA EJECUTADO PREVIAMENTE, REALIZO LA BUSQUEDA DE LA UBICACION POR DEFECTO
                $resultBuscaUbicaciones = $bd->ExecSQL($sqlBuscaUbicaciones);
            endif;

            //SI SE OBTIENEN REGISTROS RETORNO EL REGISTRO, SINO NULO
            if ($bd->NumRegs($resultBuscaUbicaciones) > 0):
                $rowUbicacionPorDefecto = $bd->SigReg($resultBuscaUbicaciones);

                return $rowUbicacionPorDefecto;
            else:
                if (($rowLineaAlbaran->ID_TIPO_BLOQUEO == NULL || $rowLineaAlbaran->ID_TIPO_BLOQUEO == $rowTipoBloqueoPreventivo->ID_TIPO_BLOQUEO) && ($tipoCorrectivoPreventivo)): //TIPO BLOQUEO OK (CORRECTIVO)
                    //BUSCO UBICACIONES DONDE HAYA EXISTIDO MATERIAL ESTROPEADO DE LA MISMA REFERENCIA Y TIPO DE BLOQUEO
                    //BUSCAMOS LA UBICACIONES EN EL CENTRO FISICO Y CUYA REFERENCIA DE UBICACION EXISTA EN EL ALMACEN DE DESTINO
                    $sqlBuscaUbicaciones    = "SELECT U.ID_UBICACION, U.UBICACION, MEL.FECHA_PROCESADO AS FECHA
                                                    FROM MOVIMIENTO_ENTRADA_LINEA MEL
                                                    INNER JOIN UBICACION UCF ON UCF.ID_UBICACION = MEL.ID_UBICACION
                                                    INNER JOIN ALMACEN ACF ON ACF.ID_ALMACEN = UCF.ID_ALMACEN
                                                    INNER JOIN UBICACION U ON U.UBICACION = UCF.UBICACION
                                                    WHERE ACF.ID_CENTRO_FISICO = $rowAlmacen->ID_CENTRO_FISICO AND U.ID_ALMACEN = $rowAlmacen->ID_ALMACEN AND (U.TIPO_UBICACION_ESTANDAR = 1 OR U.TIPO_UBICACION = 'Preventivo') AND MEL.ID_MATERIAL = $rowMaterial->ID_MATERIAL $whereUbicacionesUtilizadas AND U.BAJA = 0
                                                UNION ALL
                                                SELECT U.ID_UBICACION, U.UBICACION, MT.FECHA AS FECHA
                                                    FROM MOVIMIENTO_TRANSFERENCIA MT
                                                    INNER JOIN UBICACION UCF ON UCF.ID_UBICACION = MT.ID_UBICACION_DESTINO
                                                    INNER JOIN ALMACEN ACF ON ACF.ID_ALMACEN = UCF.ID_ALMACEN
                                                    INNER JOIN UBICACION U ON U.UBICACION = UCF.UBICACION
                                                    WHERE MT.TIPO IN ('Manual', 'Recepcion', 'ReubicacionMaterial') AND ACF.ID_CENTRO_FISICO = $rowAlmacen->ID_CENTRO_FISICO AND U.ID_ALMACEN = $rowAlmacen->ID_ALMACEN AND (U.TIPO_UBICACION_ESTANDAR = 1 OR U.TIPO_UBICACION = 'Preventivo') AND MT.ID_MATERIAL = $rowMaterial->ID_MATERIAL $whereUbicacionesUtilizadas AND U.BAJA = 0
                                                ORDER BY FECHA DESC
                                                LIMIT 1";
                    $resultBuscaUbicaciones = $bd->ExecSQL($sqlBuscaUbicaciones);

                endif;

                if ($bd->NumRegs($resultBuscaUbicaciones) > 0):
                    $rowUbicacionPorDefecto = $bd->SigReg($resultBuscaUbicaciones);

                    return $rowUbicacionPorDefecto;
                else:
                    return NULL;
                endif;
            endif;
        else:
            //VARIABLE PARA SABER SI HA ENTRADO EN LA CONSULTA DE LOS TIPOS DE BLOQUEO OK (CORRCETIVOS)
            $tipoCorrectivo = false;

            //DETERMINO LOS TIPOS DE UBICACION ESPECIAL VALIDOS
            $tiposUbicacionValidos             = "'Preventivo', 'Achatarrar', 'Traslado Calidad', 'Construcción', 'Material Estropeado'";
            $tiposUbicacionValidosNoAchatarrar = "'Preventivo', 'Traslado Calidad', 'Construcción', 'Material Estropeado'";

            $whereUbicacionesUtilizadas = "";
            if ($listaUbicacionesUtilizadas != 0):
                $whereUbicacionesUtilizadas = "AND U.ID_UBICACION NOT IN ($listaUbicacionesUtilizadas)";
            endif;

            //GENERO LA CONSULTA DE BUSQUEDA DE UBICACIONES
            if ($rowAlmacen->CATEGORIA_ALMACEN == 'Construccion: Instalacion'): //RECEPCION EN ALMACEN DE CONTRUCCION
                $sqlBuscaUbicaciones = "SELECT U.ID_UBICACION, U.UBICACION
                                            FROM UBICACION U
                                            WHERE U.ID_ALMACEN = $rowAlmacen->ID_ALMACEN AND U.TIPO_UBICACION = 'Construcción' $whereUbicacionesUtilizadas AND U.BAJA = 0";
            elseif ($tipoPedidoPendientes == true): //BUSCAMOS EL REGISTRO EN LA TABLA QUE RELACIONA OTs Y UBICACIONES
                $sqlBuscaUbicaciones    = "SELECT DISTINCT U.ID_UBICACION, U.UBICACION
                                               FROM ORDEN_TRABAJO_UBICACION_TRANSFERENCIA OTUT
                                               INNER JOIN UBICACION U ON U.ID_UBICACION = OTUT.ID_UBICACION
                                               WHERE OTUT.ID_ORDEN_TRABAJO = $idOrdenTrabajo AND OTUT.BAJA = 0 AND U.ID_ALMACEN = $rowAlmacen->ID_ALMACEN $whereUbicacionesUtilizadas AND U.BAJA = 0
                                               ORDER BY OTUT.ULTIMA_UBICACION_UTILIZADA DESC";
                $resultBuscaUbicaciones = $bd->ExecSQL($sqlBuscaUbicaciones);

                if ($bd->NumRegs($resultBuscaUbicaciones) == 0): //SI NO HAY NADA TRANSFERIDO DE ESA OT, NO DEVOLVEMOS NINGUNA UBICACION
                    return NULL;
                endif;

            elseif (($rowLineaAlbaran->ID_TIPO_BLOQUEO == NULL) && ($idTipoBloqueo == NULL)): //TIPO BLOQUEO OK
                $sqlBuscaUbicaciones = "SELECT U.ID_UBICACION, U.UBICACION, U.NOMBRE_MAQUINA
                                            FROM MATERIAL_UBICACION MU
                                            INNER JOIN UBICACION U ON U.ID_UBICACION = MU.ID_UBICACION
                                            WHERE U.ID_ALMACEN = $rowAlmacen->ID_ALMACEN AND U.TIPO_UBICACION_ESTANDAR = 1 AND MU.ID_MATERIAL = $rowMaterial->ID_MATERIAL AND MU.ID_TIPO_BLOQUEO IS NULL AND MU.ACTIVO = 1 $whereUbicacionesUtilizadas AND U.BAJA = 0
                                            ORDER BY MU.STOCK_TOTAL DESC, U.UBICACION ASC";
                $tipoCorrectivo      = true;
            elseif (($rowLineaAlbaran->ID_TIPO_BLOQUEO == NULL) && ($idTipoBloqueo == $rowTipoBloqueoPreventivo->ID_TIPO_BLOQUEO)): //TIPO BLOQUEO PREVENTIVO SEGUN LA NUEVA FORMA DE GENERAR ALBARANES
                $sqlBuscaUbicaciones = "SELECT U.ID_UBICACION, U.UBICACION
                                            FROM UBICACION U
                                            WHERE U.ID_ALMACEN = $rowAlmacen->ID_ALMACEN AND U.TIPO_UBICACION = 'Preventivo' AND U.TIPO_PREVENTIVO IS NULL $whereUbicacionesUtilizadas AND U.BAJA = 0";
            elseif ($rowTipoBloqueo->ID_TIPO_BLOQUEO == $rowTipoBloqueoPreventivo->ID_TIPO_BLOQUEO): //TIPO BLOQUEO PREVENTIVO
                $sqlBuscaUbicaciones = "SELECT U.ID_UBICACION, U.UBICACION
                                            FROM UBICACION U
                                            WHERE U.ID_ALMACEN = $rowAlmacen->ID_ALMACEN AND U.TIPO_UBICACION = 'Preventivo' $whereUbicacionesUtilizadas AND U.BAJA = 0";
            elseif ($materialAchatarrar == 1): //TIPO BLOQUEO ACHATARRAR
                //VARIABLE PARA SABER SI EXISTEN UBICACIONES PARA PROPONER
                $existenUbicacionesPropuestas = false;

                //SI NO EXISTEN UBICACIONES PARA PROPONER, BUSCO UBICACIONES DONDE EXISTA MATERIAL ESTROPEADO DE LA MISMA REFERENCIA Y TIPO DE BLOQUEO
                if ($existenUbicacionesPropuestas == false):
                    //BUSCO UBICACIONES DONDE EXISTA MATERIAL ESTROPEADO DE LA MISMA REFERENCIA Y TIPO DE BLOQUEO
                    $sqlBuscaUbicaciones    = "SELECT U.ID_UBICACION, U.UBICACION
                                                FROM MATERIAL_UBICACION MU
                                                INNER JOIN UBICACION U ON U.ID_UBICACION = MU.ID_UBICACION
                                                WHERE U.ID_ALMACEN = $rowAlmacen->ID_ALMACEN AND (U.TIPO_UBICACION IS NULL OR U.TIPO_UBICACION IN ($tiposUbicacionValidos)) AND MU.ID_MATERIAL = $rowMaterial->ID_MATERIAL AND MU.ID_TIPO_BLOQUEO = $rowTipoBloqueo->ID_TIPO_BLOQUEO AND MU.ACTIVO = 1 $whereUbicacionesUtilizadas AND U.BAJA = 0
                                                ORDER BY MU.STOCK_TOTAL DESC, U.UBICACION ASC";
                    $resultBuscaUbicaciones = $bd->ExecSQL($sqlBuscaUbicaciones);
                    if ($bd->NumRegs($resultBuscaUbicaciones) > 0):
                        //ACTUALIZO LA VARIABLE PARA SABER SI EXISTEN UBICACIONES PARA PROPONER
                        $existenUbicacionesPropuestas = true;
                    endif;
                endif;

                //SI NO EXISTEN UBICACIONES PARA PROPONER, BUSCO UBICACIONES DONDE HAYA EXISTIDO MATERIAL ESTROPEADO DE LA MISMA REFERENCIA Y TIPO DE BLOQUEO
                if ($existenUbicacionesPropuestas == false):
                    //BUSCO UBICACIONES DONDE HAYA EXISTIDO MATERIAL ESTROPEADO DE LA MISMA REFERENCIA Y TIPO DE BLOQUEO
                    $sqlBuscaUbicaciones    = "SELECT U.ID_UBICACION, U.UBICACION
                                                FROM MATERIAL_UBICACION MU
                                                INNER JOIN UBICACION U ON U.ID_UBICACION = MU.ID_UBICACION
                                                WHERE U.ID_ALMACEN = $rowAlmacen->ID_ALMACEN AND (U.TIPO_UBICACION IS NULL OR U.TIPO_UBICACION IN ($tiposUbicacionValidos)) AND MU.ID_MATERIAL = $rowMaterial->ID_MATERIAL AND MU.ID_TIPO_BLOQUEO = $rowTipoBloqueo->ID_TIPO_BLOQUEO AND MU.ACTIVO = 0 $whereUbicacionesUtilizadas AND U.BAJA = 0
                                                ORDER BY U.UBICACION ASC";
                    $resultBuscaUbicaciones = $bd->ExecSQL($sqlBuscaUbicaciones);
                    if ($bd->NumRegs($resultBuscaUbicaciones) > 0):
                        //ACTUALIZO LA VARIABLE PARA SABER SI EXISTEN UBICACIONES PARA PROPONER
                        $existenUbicacionesPropuestas = true;
                    endif;
                endif;
            elseif ($materialCalidad == 1): //TIPO BLOQUEO CALIDAD
                $sqlBuscaUbicaciones = "SELECT U.ID_UBICACION, U.UBICACION
                                            FROM UBICACION U
                                            WHERE U.ID_ALMACEN = $rowAlmacen->ID_ALMACEN AND U.TIPO_UBICACION = 'Traslado Calidad' $whereUbicacionesUtilizadas AND U.BAJA = 0";
            elseif ($idProveedorEnvio != NULL): //MATERIAL CON DIRECCION DE ENVIO, EL MATERIAL SE AGRUPA EN UBICACIONES PARA ENVIAR A ESE PROVEEDOR
                //BUSCO EL TIPO DE BLOQUEO NO REPARABLE NO GARANTIA
                $rowTipoBloqueoNoReparableNoGarantia = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "QNRNG");
                //BUSCO EL TIPO DE BLOQUEO RETENIDO POR CALIDAD NO PREVENTIVO NO REPARABLE NO GARANTIA
                $rowTipoBloqueoRetenidoCalidadNoPreventivoNoReparableNoEnGarantia = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "XRCNRNG");

                //VARIABLE PARA SABER SI EXISTEN UBICACIONES PARA PROPONER
                $existenUbicacionesPropuestas = false;

                //SI NO EXISTEN UBICACIONES PARA PROPONER, BUSCO UBICACIONES DONDE EXISTA MATERIAL ESTROPEADO CON DIRECCION EL PROVEEDOR GARANTIA
                if ($existenUbicacionesPropuestas == false):
                    //BUSCO UBICACIONES DONDE EXISTA MATERIAL ESTROPEADO CON DIRECCION EL PROVEEDOR GARANTIA Y EL TIPO DE BLOQUEO SE DIFERENTE DE NO REPARABLE
                    $sqlBuscaUbicaciones    = "SELECT U.ID_UBICACION, U.UBICACION
                                                FROM MATERIAL_UBICACION MU
                                                INNER JOIN UBICACION U ON U.ID_UBICACION = MU.ID_UBICACION
                                                INNER JOIN ORDEN_TRABAJO_MOVIMIENTO OTM ON OTM.ID_ORDEN_TRABAJO_MOVIMIENTO = MU.ID_ORDEN_TRABAJO_MOVIMIENTO
                                                WHERE U.ID_ALMACEN = $rowAlmacen->ID_ALMACEN AND (U.TIPO_UBICACION_ESTANDAR = 1 OR U.TIPO_UBICACION IN ($tiposUbicacionValidosNoAchatarrar)) AND OTM.ID_PROVEEDOR_GARANTIA = $idProveedorEnvio AND MU.ACTIVO = 1 $whereUbicacionesUtilizadas AND U.BAJA = 0 AND MU.ID_TIPO_BLOQUEO <> $rowTipoBloqueoNoReparableNoGarantia->ID_TIPO_BLOQUEO AND MU.ID_TIPO_BLOQUEO <> $rowTipoBloqueoRetenidoCalidadNoPreventivoNoReparableNoEnGarantia->ID_TIPO_BLOQUEO
                                                GROUP BY MU.ID_UBICACION
                                                ORDER BY SUM(MU.STOCK_TOTAL) DESC, U.UBICACION ASC";
                    $resultBuscaUbicaciones = $bd->ExecSQL($sqlBuscaUbicaciones);
                    if ($bd->NumRegs($resultBuscaUbicaciones) > 0):
                        //ACTUALIZO LA VARIABLE PARA SABER SI EXISTEN UBICACIONES PARA PROPONER
                        $existenUbicacionesPropuestas = true;
                    endif;
                endif;

                //SI NO EXISTEN UBICACIONES PARA PROPONER, BUSCO UBICACIONES DONDE EXISTA MATERIAL ESTROPEADO DE LA MISMA REFERENCIA Y TIPO DE BLOQUEO
                if ($existenUbicacionesPropuestas == false):
                    //BUSCO UBICACIONES DONDE EXISTA MATERIAL ESTROPEADO DE LA MISMA REFERENCIA Y TIPO DE BLOQUEO
                    $sqlBuscaUbicaciones    = "SELECT U.ID_UBICACION, U.UBICACION
                                                FROM MATERIAL_UBICACION MU
                                                INNER JOIN UBICACION U ON U.ID_UBICACION = MU.ID_UBICACION
                                                WHERE U.ID_ALMACEN = $rowAlmacen->ID_ALMACEN AND (U.TIPO_UBICACION_ESTANDAR = 1 OR U.TIPO_UBICACION IN ($tiposUbicacionValidosNoAchatarrar)) AND MU.ID_MATERIAL = $rowMaterial->ID_MATERIAL AND MU.ID_TIPO_BLOQUEO = $rowTipoBloqueo->ID_TIPO_BLOQUEO AND MU.ACTIVO = 1 $whereUbicacionesUtilizadas AND U.BAJA = 0 AND MU.ID_TIPO_BLOQUEO <> $rowTipoBloqueoNoReparableNoGarantia->ID_TIPO_BLOQUEO AND MU.ID_TIPO_BLOQUEO <> $rowTipoBloqueoRetenidoCalidadNoPreventivoNoReparableNoEnGarantia->ID_TIPO_BLOQUEO
                                                ORDER BY MU.STOCK_TOTAL DESC, U.UBICACION ASC";
                    $resultBuscaUbicaciones = $bd->ExecSQL($sqlBuscaUbicaciones);
                    if ($bd->NumRegs($resultBuscaUbicaciones) > 0):
                        //ACTUALIZO LA VARIABLE PARA SABER SI EXISTEN UBICACIONES PARA PROPONER
                        $existenUbicacionesPropuestas = true;
                    endif;
                endif;

                //SI NO EXISTEN UBICACIONES PARA PROPONER, BUSCO UBICACIONES DONDE HAYA EXISTIDO MATERIAL ESTROPEADO CON DIRECCION EL PROVEEDOR GARANTIA
                if ($existenUbicacionesPropuestas == false):
                    //BUSCO UBICACIONES DONDE HAYA EXISTIDO MATERIAL ESTROPEADO CON DIRECCION EL PROVEEDOR GARANTIA
                    $sqlBuscaUbicaciones    = "SELECT U.ID_UBICACION, U.UBICACION
                                                FROM MATERIAL_UBICACION MU
                                                INNER JOIN UBICACION U ON U.ID_UBICACION = MU.ID_UBICACION
                                                INNER JOIN ORDEN_TRABAJO_MOVIMIENTO OTM ON OTM.ID_ORDEN_TRABAJO_MOVIMIENTO = MU.ID_ORDEN_TRABAJO_MOVIMIENTO
                                                WHERE U.ID_ALMACEN = $rowAlmacen->ID_ALMACEN AND (U.TIPO_UBICACION_ESTANDAR = 1 OR U.TIPO_UBICACION IN ($tiposUbicacionValidosNoAchatarrar)) AND OTM.ID_PROVEEDOR_GARANTIA = $idProveedorEnvio AND MU.ACTIVO = 0 $whereUbicacionesUtilizadas AND U.BAJA = 0 AND MU.ID_TIPO_BLOQUEO <> $rowTipoBloqueoNoReparableNoGarantia->ID_TIPO_BLOQUEO AND MU.ID_TIPO_BLOQUEO <> $rowTipoBloqueoRetenidoCalidadNoPreventivoNoReparableNoEnGarantia->ID_TIPO_BLOQUEO
                                                ORDER BY U.UBICACION ASC";
                    $resultBuscaUbicaciones = $bd->ExecSQL($sqlBuscaUbicaciones);
                    if ($bd->NumRegs($resultBuscaUbicaciones) > 0):
                        //ACTUALIZO LA VARIABLE PARA SABER SI EXISTEN UBICACIONES PARA PROPONER
                        $existenUbicacionesPropuestas = true;
                    endif;
                endif;

                //SI NO EXISTEN UBICACIONES PARA PROPONER, BUSCO UBICACIONES DONDE HAYA EXISTIDO MATERIAL ESTROPEADO DE LA MISMA REFERENCIA Y TIPO DE BLOQUEO
                if ($existenUbicacionesPropuestas == false):
                    //BUSCO UBICACIONES DONDE HAYA EXISTIDO MATERIAL ESTROPEADO DE LA MISMA REFERENCIA Y TIPO DE BLOQUEO
                    $sqlBuscaUbicaciones    = "SELECT U.ID_UBICACION, U.UBICACION
                                                FROM MATERIAL_UBICACION MU
                                                INNER JOIN UBICACION U ON U.ID_UBICACION = MU.ID_UBICACION
                                                WHERE U.ID_ALMACEN = $rowAlmacen->ID_ALMACEN AND (U.TIPO_UBICACION_ESTANDAR = 1 OR U.TIPO_UBICACION IN ($tiposUbicacionValidosNoAchatarrar)) AND MU.ID_MATERIAL = $rowMaterial->ID_MATERIAL AND MU.ID_TIPO_BLOQUEO = $rowTipoBloqueo->ID_TIPO_BLOQUEO AND MU.ACTIVO = 0 $whereUbicacionesUtilizadas AND U.BAJA = 0 AND MU.ID_TIPO_BLOQUEO <> $rowTipoBloqueoNoReparableNoGarantia->ID_TIPO_BLOQUEO AND MU.ID_TIPO_BLOQUEO <> $rowTipoBloqueoRetenidoCalidadNoPreventivoNoReparableNoEnGarantia->ID_TIPO_BLOQUEO
                                                ORDER BY U.UBICACION ASC";
                    $resultBuscaUbicaciones = $bd->ExecSQL($sqlBuscaUbicaciones);
                    if ($bd->NumRegs($resultBuscaUbicaciones) > 0):
                        //ACTUALIZO LA VARIABLE PARA SABER SI EXISTEN UBICACIONES PARA PROPONER
                        $existenUbicacionesPropuestas = true;
                    endif;
                endif;

            else: //POR DEFECTO QUE COINCIDA EL TIPO DE BLOQUEO
                $sqlBuscaUbicaciones = "SELECT U.ID_UBICACION, U.UBICACION
                                            FROM MATERIAL_UBICACION MU
                                            INNER JOIN UBICACION U ON U.ID_UBICACION = MU.ID_UBICACION
                                            WHERE U.ID_ALMACEN = $rowAlmacen->ID_ALMACEN AND (U.TIPO_UBICACION_ESTANDAR = 1 OR U.TIPO_UBICACION IN ($tiposUbicacionValidos)) AND MU.ID_MATERIAL = $rowMaterial->ID_MATERIAL AND MU.ID_TIPO_BLOQUEO = $rowTipoBloqueo->ID_TIPO_BLOQUEO $whereUbicacionesUtilizadas AND U.BAJA = 0
                                            ORDER BY MU.STOCK_TOTAL DESC, U.UBICACION ASC";
            endif;

            //SI NO ESTA DEFINIDA LA CONSULTA, RETORNO NULO
            if (!(isset($sqlBuscaUbicaciones))):
                return NULL;
            else:
                //echo($sqlBuscaUbicaciones);
            endif;

            if ($resultBuscaUbicaciones != NULL):
                //SI SE EJECUTÓ PREVIAMENTE LA CONSULTA, HACEMOS QUE EL PUNTERO APUNTE AL PRIMER REGISTRO
                $bd->Mover($resultBuscaUbicaciones, 0);
            else:
                //SI NO SE HA EJECUTADO PREVIAMENTE, REALIZO LA BUSQUEDA DE LA UBICACION POR DEFECTO
                $resultBuscaUbicaciones = $bd->ExecSQL($sqlBuscaUbicaciones);
            endif;

            //SI SE OBTIENEN REGISTROS RETORNO EL REGISTRO, SINO NULO
            if ($bd->NumRegs($resultBuscaUbicaciones) > 0):
                $rowUbicacionPorDefecto = $bd->SigReg($resultBuscaUbicaciones);

                return $rowUbicacionPorDefecto;
            else:
                if (($rowLineaAlbaran->ID_TIPO_BLOQUEO == NULL) && ($idTipoBloqueo == NULL) && ($tipoCorrectivo)): //TIPO BLOQUEO OK (CORRECTIVO)
                    //BUSCO UBICACIONES DONDE HAYA EXISTIDO MATERIAL ESTROPEADO DE LA MISMA REFERENCIA Y TIPO DE BLOQUEO
                    $sqlBuscaUbicaciones    = "SELECT U.ID_UBICACION, U.UBICACION, MEL.FECHA_PROCESADO AS FECHA
                                                    FROM MOVIMIENTO_ENTRADA_LINEA MEL
                                                    INNER JOIN UBICACION U ON U.ID_UBICACION = MEL.ID_UBICACION
                                                    WHERE U.ID_ALMACEN = $rowAlmacen->ID_ALMACEN AND U.TIPO_UBICACION_ESTANDAR = 1 AND MEL.ID_MATERIAL = $rowMaterial->ID_MATERIAL $whereUbicacionesUtilizadas AND U.BAJA = 0
                                                UNION ALL
                                                SELECT U.ID_UBICACION, U.UBICACION, MT.FECHA AS FECHA
                                                    FROM MOVIMIENTO_TRANSFERENCIA MT
                                                    INNER JOIN UBICACION U ON U.ID_UBICACION = MT.ID_UBICACION_DESTINO
                                                    WHERE MT.TIPO IN ('Manual', 'Recepcion', 'ReubicacionMaterial') AND U.ID_ALMACEN = $rowAlmacen->ID_ALMACEN AND U.TIPO_UBICACION_ESTANDAR = 1 AND MT.ID_MATERIAL = $rowMaterial->ID_MATERIAL $whereUbicacionesUtilizadas AND U.BAJA = 0
                                                ORDER BY FECHA DESC";
                    $resultBuscaUbicaciones = $bd->ExecSQL($sqlBuscaUbicaciones);
                endif;

                if ($bd->NumRegs($resultBuscaUbicaciones) > 0):
                    $rowUbicacionPorDefecto = $bd->SigReg($resultBuscaUbicaciones);

                    return $rowUbicacionPorDefecto;
                else:
                    return NULL;
                endif;
            endif;
        endif;
    }

    function ComprobarUbicacionSeleccionadaRecepcionPedidosTraslado($idAlmacen, $idUbicacion, $idAlbaranLinea, $idMovimientoLinea)
    {
        global $bd;
        global $html;
        global $mat;
        global $administrador;

        //ARRAY A DEVOLVER
        $arrDevolver              = array();
        $arrDevolver['RESULTADO'] = 'Ok';
        $arrDevolver['ERROR']     = '';

        //VARIABLE PARA SABER SI LA PIEZA ES ACHATARRAR
        $materialAchatarrar = 0;

        //VARIABLE PARA SABER SI LA PIEZA ES DE CALIDAD
        $materialCalidad = 0;

        //VARIABLE PARA SABER SI LA PIEZA TIENE ASIGNADA UNA DIRECCION DE PROVEEDOR
        $idProveedorEnvio = NULL;

        //BUSCO EL ALMACEN
        $rowAlmacen = $bd->VerReg("ALMACEN", "ID_ALMACEN", $idAlmacen);

        //BUSCO LA UBICACION
        $rowUbicacion = $bd->VerReg("UBICACION", "ID_UBICACION", $idUbicacion);

        //BUSCO LA LINEA DE ALBARAN
        $rowLineaAlbaran = $bd->VerReg("ALBARAN_LINEA", "ID_ALBARAN_LINEA", $idAlbaranLinea);

        //BUSCO EL ALBARAN
        $rowAlbaran = $bd->VerReg("ALBARAN", "ID_ALBARAN", $rowLineaAlbaran->ID_ALBARAN);

        //BUSCO LA LINEA DEL MOVIMIENTO
        $rowMovSalLin = $bd->VerReg("MOVIMIENTO_SALIDA_LINEA", "ID_MOVIMIENTO_SALIDA_LINEA", $idMovimientoLinea);

        //BUSCO EL PEDIDO
        if ($rowMovSalLin->ID_PEDIDO_SALIDA != NULL):
            $rowPedSal = $bd->VerReg("PEDIDO_SALIDA", "ID_PEDIDO_SALIDA", $rowMovSalLin->ID_PEDIDO_SALIDA);
        else:
            $rowPedSal = NULL;
        endif;

        //BUSCO LA ORDEN DE RECOGIDA
        $rowOrdenRecogida = $bd->VerReg("EXPEDICION", "ID_EXPEDICION", $rowAlbaran->ID_EXPEDICION);

        //BUSCO EL TIPO Y SUBTIPO DE LA ORDEN DE RECOGIDA
        $tipoOrdenRecogida    = $rowOrdenRecogida->TIPO_ORDEN_RECOGIDA;
        $subtipoOrdenRecogida = $rowOrdenRecogida->SUBTIPO_ORDEN_RECOGIDA;

        //BUSCO EL MATERIAL
        $rowMaterial = $bd->VerReg("MATERIAL", "ID_MATERIAL", $rowLineaAlbaran->ID_MATERIAL);

        //BUSCO EL MATERIAL ALMACEN
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowMaterialAlmacen               = $bd->VerRegRest("MATERIAL_ALMACEN", "ID_MATERIAL = $rowMaterial->ID_MATERIAL AND ID_ALMACEN = $rowAlmacen->ID_ALMACEN", "No");
        if ($rowMaterialAlmacen == false):
            //GENERAMOS UNA COPIA DEL MATERIAL_ALMACEN ENCONTRADO
            $idMaterialAlmacen = $mat->ClonarMaterialAlmacen($rowMaterial->ID_MATERIAL, $rowAlmacen->ID_ALMACEN, "Recepcion Pedidos Traslado");

            if ($idMaterialAlmacen != false):
                //AÑADO EL MATERIAL ALMACEN AL ARRAY PARA NOTIFICAR A LAS PERSONA CORRESPONDIENTES
                $arrMaterialAlmacen[$rowMaterial->ID_MATERIAL . "_" . $rowAlmacen->ID_ALMACEN] = 1;

                //RECUPERO EL OBJETO CREADO
                $rowMaterialAlmacen = $bd->VerReg("MATERIAL_ALMACEN", "ID_MATERIAL_ALMACEN", $idMaterialAlmacen);
            endif;
        endif;
        unset($GLOBALS["NotificaErrorPorEmail"]);

        //COMPRUEBO QUE EXISTA MATERIAL ALMACEN ORIGEN
        if ($rowMaterialAlmacen == false) :
            global $strError;
            $strError = $strError . $auxiliar->traduce("El material", $administrador->ID_IDIOMA) . " $rowMaterial->REFERENCIA_SGA - " . ($administrador->ID_IDIOMA == "ESP" ? $rowMaterial->DESCRIPCION : $rowMaterial->DESCRIPCION_EN) . " " . $auxiliar->traduce("no está definido para el almacén", $administrador->ID_IDIOMA) . " $rowAlmacen->REFERENCIA - $rowAlmacen->NOMBRE.<br>";
            $html->PagError("MaterialAlmacenNoDefinido");
        endif;

        //BUSCO EL MATERIAL FISICO EN CASO DE NO SER NULO
        if ($rowLineaAlbaran->ID_MATERIAL_FISICO != NULL):
            //BUSCO EL MATERIAL FISICO
            $rowMaterialFisico = $bd->VerReg("MATERIAL_FISICO", "ID_MATERIAL_FISICO", $rowLineaAlbaran->ID_MATERIAL_FISICO);
        endif;

        //BUSCO EL TIPO DE BLOQUEO PREVENTIVO
        $rowTipoBloqueoPreventivo = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "SP");
        //BUSCO EL TIPO DE BLOQUEO NO REPARABLE NO GARANTIA
        $rowTipoBloqueoNoReparablaNoGarantia = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "QNRNG");

        //BUSCO EL TIPO DE BLOQUEO EN CASO DE NO SER NULO
        if ($rowMovSalLin->ID_TIPO_BLOQUEO != NULL):
            //BUSCO EL TIPO DE BLOQUEO
            $rowTipoBloqueo = $bd->VerReg("TIPO_BLOQUEO", "ID_TIPO_BLOQUEO", $rowMovSalLin->ID_TIPO_BLOQUEO);

            //SI EL TIPO DE BLOQUEO ES NO REPARABLE: QNRNG (NR) EL TIPO DE UBICACION ES ACHATARRAR
            if ($rowTipoBloqueo->ID_TIPO_BLOQUEO == $rowTipoBloqueoNoReparablaNoGarantia->ID_TIPO_BLOQUEO):
                $materialAchatarrar = 1;
            endif;

            //SI EL TIPO DE BLOQUEO ES DE CALIDAD (EMPIEZA POR X) EL MATERIAL SE RECEPCIONARA EN LA UBICACION DE TIPO 'Traslado Calidad'
            if (substr((string)$rowTipoBloqueo->TIPO_BLOQUEO_INTERNO, 0, 1) == 'X'):
                $materialCalidad = 1;
            endif;
        endif;

        //BUSCO EL MOVIMIENTO DE LA ORDEN DE TRABAJO EN CASO DE NO SER NULO
        if ($rowLineaAlbaran->ID_ORDEN_TRABAJO_MOVIMIENTO != NULL):
            //BUSCO EL MOVIMIENTO DE LA ORDEN DE TRABAJO
            $rowOrdenTrabajoMovimiento = $bd->VerReg("ORDEN_TRABAJO_MOVIMIENTO", "ID_ORDEN_TRABAJO_MOVIMIENTO", $rowLineaAlbaran->ID_ORDEN_TRABAJO_MOVIMIENTO);

            //RECUPERO LA DIRECCION DE ENVIO DEL MATERIAL
            $idProveedorEnvio = $rowOrdenTrabajoMovimiento->ID_PROVEEDOR_GARANTIA;
        endif;

        //BUSCO LA INCIDENCIA DE CALIDAD EN CASO DE NO SER NULO
        if ($rowLineaAlbaran->ID_INCIDENCIA_CALIDAD != NULL):
            //BUSCO LA INCIDENCIA DE CALIDAD
            $rowIncidenciaCalidad = $bd->VerReg("INCIDENCIA_CALIDAD", "ID_INCIDENCIA_CALIDAD", $rowLineaAlbaran->ID_INCIDENCIA_CALIDAD);
        endif;

        //SI SE RETORNA EL MATERIAL ESTROPEADO DESDE PROVEEDOR, LA UBICACION PROPUESTA DEPENDE DE LA DECISION TOMADA POR EL COMPRADOR
        if (($tipoOrdenRecogida == 'Recogida en Proveedor') && ($subtipoOrdenRecogida == 'Retorno Material Estropeado desde Proveedor')):
            //BUSCO LA LINEA DEL MOVIMIETO DE SALIDA DEL PROVEEDOR AL ALMACEN DE DESTINO
            $rowLineaMovimientoRetornoMaterialEstropeado = $bd->VerRegRest("MOVIMIENTO_SALIDA_LINEA", "ID_ALBARAN_LINEA = $rowLineaAlbaran->ID_ALBARAN_LINEA AND BAJA = 0", "No");

            //BUSCO LA LINEA ORIGINAL DEL ALMACEN AL PROVEEDOR
            $rowLineaMovimientoEnvioAlmacenProveedor = $bd->VerRegRest("MOVIMIENTO_SALIDA_LINEA", "ID_MOVIMIENTO_SALIDA_LINEA = $rowLineaMovimientoRetornoMaterialEstropeado->ID_MOVIMIENTO_SALIDA_LINEA_MATERIAL_ESTROPEADO AND BAJA = 0", "No");

            //SI LA DECISION FUE ACHATARRAR EN EL ALMACEN PRINCIPAL, LA UBICACION PROPUESTA ES LA DE ACHATARRAMIENTO
            if ($rowLineaMovimientoEnvioAlmacenProveedor->DECISION_COMPRADOR == 'Achatarrar en Almacén Principal'):
                $materialAchatarrar = 1;
            elseif ($rowLineaMovimientoEnvioAlmacenProveedor->DECISION_COMPRADOR == 'Otro Proveedor'):
                $idProveedorEnvio = $rowLineaMovimientoEnvioAlmacenProveedor->ID_OTRO_PROVEEDOR_GARANTIA;
            endif;
        endif;

        //DETERMINO LOS TIPOS DE UBICACION ESPECIAL VALIDOS
        $tiposUbicacionValidos = "Preventivo, Achatarrar, Traslado Calidad, Construcción, Material Estropeado";

        //COMPRUEBO SI LA UBICACION ES VALIDA
        if (($rowUbicacion->TIPO_UBICACION != NULL) && (!(in_array($rowUbicacion->TIPO_UBICACION, (array)explode(", ", (string)$tiposUbicacionValidos))))): //COMPROBACION DE TIPOS DE UBICACION
            $arrDevolver['RESULTADO'] = 'Error';
            $arrDevolver['ERROR']     = 'UbicacionEspecialNoValidaRecepcion';
        elseif (($rowAlmacen->CATEGORIA_ALMACEN == 'Construccion: Instalacion') && ($rowUbicacion->TIPO_UBICACION == 'Construcción')): //RECEPCION EN ALMACEN DE CONTRUCCION
            $arrDevolver['RESULTADO'] = 'Error';
            $arrDevolver['ERROR']     = 'UbicacionNoConstruccion';
        elseif ($rowPedSal->TIPO_PEDIDO == 'Traslado'):
            if (($rowTipoBloqueo->ID_TIPO_BLOQUEO == NULL) && ($rowUbicacion->TIPO_UBICACION != NULL)): //TIPO BLOQUEO OK
                $arrDevolver['RESULTADO'] = 'Error';
                $arrDevolver['ERROR']     = 'UbicacionEspecial';
            elseif (($rowTipoBloqueo->ID_TIPO_BLOQUEO == $rowTipoBloqueoPreventivo->ID_TIPO_BLOQUEO) && ($rowUbicacion->TIPO_UBICACION != 'Preventivo') && ($rowUbicacion->TIPO_UBICACION != NULL)): //TIPO BLOQUEO PREVENTIVO
                $arrDevolver['RESULTADO'] = 'Error';
                $arrDevolver['ERROR']     = 'UbicacionNoPreventivo';
            endif;
            if ($rowUbicacion->TIPO_PREVENTIVO == 'Pendientes'):
                $arrDevolver['RESULTADO'] = 'Error';
                $arrDevolver['ERROR']     = 'UbicacionPendientes';
            endif;
        elseif ($rowPedSal->TIPO_PEDIDO == 'Pendientes de Ordenes Trabajo'):
            if (($rowTipoBloqueo->ID_TIPO_BLOQUEO == $rowTipoBloqueoPreventivo->ID_TIPO_BLOQUEO) && ($rowUbicacion->TIPO_UBICACION != 'Preventivo') && ($rowUbicacion->TIPO_UBICACION != NULL)): //TIPO BLOQUEO PREVENTIVO
                $arrDevolver['RESULTADO'] = 'Error';
                $arrDevolver['ERROR']     = 'UbicacionNoPreventivo';
            endif;
            if (($rowTipoBloqueo->ID_TIPO_BLOQUEO == $rowTipoBloqueoPreventivo->ID_TIPO_BLOQUEO) && ($rowUbicacion->TIPO_UBICACION != 'Preventivo') && ($rowUbicacion->TIPO_UBICACION != NULL) && ($rowUbicacion->TIPO_PREVENTIVO != 'Pendientes')): //TIPO BLOQUEO PREVENTIVO-PENDIENTES
                $arrDevolver['RESULTADO'] = 'Error';
                $arrDevolver['ERROR']     = 'UbicacionNoPendientes';
            endif;
        elseif (($materialCalidad == 1) && ($rowUbicacion->TIPO_UBICACION != 'Traslado Calidad')): //TIPO BLOQUEO CALIDAD
            $arrDevolver['RESULTADO'] = 'Error';
            $arrDevolver['ERROR']     = 'UbicacionNoCalidad';
        endif;

        return $arrDevolver;
    }

    // idUbicacion ES EL IDENTIFICADOR (ID) DE UNA UBICACIÓN DE LA BASE DE DATOS
    // DEVUELVE EL PESO BRUTO TOTAL EN KILOGRAMOS DE TODOS LOS MATERIALES DE LA UBICACIÓN CON ID DADO Y QUE EXISTEN EN LA UBICACIÓN
    public function pesoBrutoUbicacion($idUbicacion)
    {
        global $bd;
        global $auxiliar;
        global $mat;

        $pesoBruto = 0;

        $sql_materiales        = "SELECT M.ID_MATERIAL, SUM(STOCK_TOTAL) AS STOCK, M.PESO_BRUTO, M.ID_UNIDAD_PESO, M.ID_UNIDAD_MEDIDA, M.ID_UNIDAD_COMPRA
                                    FROM MATERIAL_UBICACION MU
                                    INNER JOIN MATERIAL M ON M.ID_MATERIAL = MU.ID_MATERIAL
                                    WHERE MU.ID_UBICACION = $idUbicacion AND MU.ACTIVO = '1' AND M.ID_UNIDAD_PESO IS NOT NULL AND M.PESO_BRUTO > 0
                                    GROUP BY M.ID_MATERIAL";
        $result_sql_materiales = $bd->ExecSQL($sql_materiales);

        if ($bd->NumRegs($result_sql_materiales) > 0):
            $idUnidadKG      = $bd->VerReg("UNIDAD", "UNIDAD", "KG")->ID_UNIDAD;
            $arrConversionKg = array($idUnidadKG => 1);
            while ($rowMateriales = $bd->SigReg($result_sql_materiales)):
                if ($rowMateriales->ID_UNIDAD_MEDIDA <> $rowMateriales->ID_UNIDAD_COMPRA):
                    $cantidadCompra = $mat->cantUnidadCompra($rowMateriales->ID_MATERIAL, $rowMateriales->STOCK);
                else:
                    $cantidadCompra = $rowMateriales->STOCK;
                endif;

                if (!isset($arrConversionKg[$rowMateriales->ID_UNIDAD_PESO])):
                    $arrConversionKg[$rowMateriales->ID_UNIDAD_PESO] = $auxiliar->convertirUnidades(1, $rowMateriales->ID_UNIDAD_PESO)[$idUnidadKG];
                endif;
                $pesoBruto += $cantidadCompra * $rowMateriales->PESO_BRUTO * $arrConversionKg[$rowMateriales->ID_UNIDAD_PESO];
            endwhile;
        endif;

        return number_format($pesoBruto, 3);
    }

    // DEVUELVE EL PESO BRUTO TOTAL EN KILOGRAMOS DE TODOS LOS MATERIALES DE LAS UBICACIONES DEL CF
    public function pesoBrutoUbicacionCentroFisico($idUbicacionCentroFisico)
    {
        global $bd;
        global $auxiliar;
        global $mat;

        $pesoBruto = 0;

        $sql_ubicaciones        = "SELECT DISTINCT ID_UBICACION FROM UBICACION WHERE ID_UBICACION_CENTRO_FISICO = '$idUbicacionCentroFisico' AND BAJA = '0'";
        $result_sql_ubicaciones = $bd->ExecSQL($sql_ubicaciones);

        while ($rowUbicacion = $bd->SigReg($result_sql_ubicaciones)):
            $pesoBruto += $this->pesoBrutoUbicacion($rowUbicacion->ID_UBICACION);
        endwhile;

        return $pesoBruto;
    }

    // idUbicacion ES EL IDENTIFICADOR DE UNA UBICACIÓN DE LA BASE DE DATOS
    // DEVUELVE EL VOLUMEN TOTAL EN METROS CÚBICOS DE TODOS LOS MATERIALES DE LA UBICACIÓN CON ID DADO
    public function volumenTotalMaterialesUbicacion($idUbicacion)
    {
        global $bd;
        global $auxiliar;
        global $mat;

        // volumen total de los materiales de la ubicacion en mretros cúbicos
        $volumen_total = 0;

        $sql_materiales        = "SELECT M.ID_MATERIAL, SUM(STOCK_TOTAL) AS STOCK, M.VOLUMEN, M.ID_UNIDAD_VOLUMEN, M.ID_UNIDAD_MEDIDA, M.ID_UNIDAD_COMPRA
                                    FROM MATERIAL_UBICACION MU
                                    INNER JOIN MATERIAL M ON M.ID_MATERIAL = MU.ID_MATERIAL
                                    WHERE MU.ID_UBICACION = $idUbicacion AND MU.ACTIVO = '1' AND M.ID_UNIDAD_VOLUMEN IS NOT NULL AND M.VOLUMEN > 0
                                    GROUP BY M.ID_MATERIAL";
        $result_sql_materiales = $bd->ExecSQL($sql_materiales);

        if ($bd->NumRegs($result_sql_materiales) > 0):
            $idUnidad_M3     = $bd->VerReg("UNIDAD", "UNIDAD", "M3")->ID_UNIDAD;
            $arrConversionM3 = array($idUnidad_M3 => 1);
            while ($rowMateriales = $bd->SigReg($result_sql_materiales)):
                if ($rowMateriales->ID_UNIDAD_MEDIDA <> $rowMateriales->ID_UNIDAD_COMPRA):
                    $cantidadCompra = $mat->cantUnidadCompra($rowMateriales->ID_MATERIAL, $rowMateriales->STOCK);
                else:
                    $cantidadCompra = $rowMateriales->STOCK;
                endif;

                if (!isset($arrConversionM3[$rowMateriales->ID_UNIDAD_VOLUMEN])):
                    $arrConversionM3[$rowMateriales->ID_UNIDAD_VOLUMEN] = $auxiliar->convertirUnidades(1, $rowMateriales->ID_UNIDAD_VOLUMEN)[$idUnidad_M3];
                endif;
                $volumen_total += $cantidadCompra * $rowMateriales->VOLUMEN * $arrConversionM3[$rowMateriales->ID_UNIDAD_VOLUMEN];

            endwhile;
        endif;

        return number_format($volumen_total, 3);
    }

    // DEVUELVE EL VOLUMEN DE TODOS LOS MATERIALES DE LAS UBICACIONES DEL CF
    public function volumenTotalMaterialesUbicacionCentroFisico($idUbicacionCentroFisico)
    {
        global $bd;
        global $auxiliar;
        global $mat;

        $volumen = 0;

        $sql_ubicaciones        = "SELECT DISTINCT ID_UBICACION FROM UBICACION WHERE ID_UBICACION_CENTRO_FISICO = '$idUbicacionCentroFisico' AND BAJA = '0'";
        $result_sql_ubicaciones = $bd->ExecSQL($sql_ubicaciones);

        while ($rowUbicacion = $bd->SigReg($result_sql_ubicaciones)):
            $volumen += $this->volumenTotalMaterialesUbicacion($rowUbicacion->ID_UBICACION);
        endwhile;

        return $volumen;
    }

    // ACTUALIZA LAS UBICACIONES CON LOS DATOS DE LA UBICACION CENTRO FISICO
    public function actualizarUbicacionesAlmacenesCentroFisico($idUbicacionCentroFisico)
    {
        global $bd;
        global $auxiliar;
        global $administrador;

        //BUSCAMOS REGISTRO
        $rowUbicacionCentroFisico = $bd->VerReg("UBICACION_CENTRO_FISICO", "ID_UBICACION_CENTRO_FISICO", $idUbicacionCentroFisico);

        //BUSCAMOS LAS UBICACIONES ASIGNADAS A ESA UCF
        $ubicacionesActualizar = array();
        $sqlUbicaciones        = "SELECT * FROM UBICACION WHERE ID_UBICACION_CENTRO_FISICO = $rowUbicacionCentroFisico->ID_UBICACION_CENTRO_FISICO AND BAJA = 0";
        $resultUbicaciones     = $bd->ExecSQL($sqlUbicaciones);
        while ($rowUbicacion = $bd->SigReg($resultUbicaciones)):
            $ubicacionesActualizar[$rowUbicacion->ID_UBICACION] = $rowUbicacion;
        endwhile;

        //BUSCAMOS LOS ALMACENES DE ESE CENTRO FISICO
        $sqlAlmacenes    = "SELECT ID_ALMACEN FROM ALMACEN WHERE ID_CENTRO_FISICO = $rowUbicacionCentroFisico->ID_CENTRO_FISICO AND TIPO_ALMACEN = 'acciona' AND BAJA = 0";
        $resultAlmacenes = $bd->ExecSQL($sqlAlmacenes);


        while ($rowAlmacen = $bd->SigReg($resultAlmacenes)):

            //SI ES UBICACION ESPECIAL UNICA, BUSCAMOS SI EXISTE LA UBICACION
            if ($rowUbicacionCentroFisico->TIPO_UBICACION_CF == 'Entrada' || $rowUbicacionCentroFisico->TIPO_UBICACION_CF == 'Salida' || $rowUbicacionCentroFisico->TIPO_UBICACION_CF == 'Embarque' || $rowUbicacionCentroFisico->TIPO_UBICACION_CF == 'Consumos Masivos' || $rowUbicacionCentroFisico->TIPO_UBICACION_CF == 'Retornos Masivos' || $rowUbicacionCentroFisico->TIPO_UBICACION_CF == 'Consolidacion'):
                //BUSCAMOS SI YA EXISTE UNA UBICACION CON ESA REFERENCIA Y TIPO
                $GLOBALS["NotificaErrorPorEmail"] = "No";
                $rowUbicacion                     = $bd->VerRegRest("UBICACION", "ID_ALMACEN=" . $rowAlmacen->ID_ALMACEN . " AND TIPO_UBICACION ='" . $rowUbicacionCentroFisico->TIPO_UBICACION_CF . "'", "No");

            else:
                //BUSCAMOS SI YA EXISTE UNA UBICACION CON ESA REFERENCIA Y TIPO
                $GLOBALS["NotificaErrorPorEmail"] = "No";
                $rowUbicacion                     = $bd->VerRegRest("UBICACION", "ID_ALMACEN=" . $rowAlmacen->ID_ALMACEN . " AND TIPO_UBICACION " . ($rowUbicacionCentroFisico->TIPO_UBICACION_CF != NULL ? "='" . $rowUbicacionCentroFisico->TIPO_UBICACION_CF . "'" : "IS NULL") . " AND UBICACION = '" . $rowUbicacionCentroFisico->REFERENCIA_UBICACION . "'", "No");
            endif;
            if ($rowUbicacion != false):
                $ubicacionesActualizar[$rowUbicacion->ID_UBICACION] = $rowUbicacion;
            else:
                //GENERO EL ARRAY DE UBICACIONES QUE NO CONTABILIZAN PARA EL STOCK DISPONIBLE EN EL ALMACEN
                $arrUbicacionNoContabilizaStockDisponible = explode(",", LISTA_UBICACIONES_NO_CONTABILIZAN_STOCK_DISPONIBLE);

                //CREAMOS LA UBICACION
                $sqlUbicacion = "INSERT INTO UBICACION SET
                                UBICACION = '" . $rowUbicacionCentroFisico->REFERENCIA_UBICACION . "'
                                , ID_UBICACION_CENTRO_FISICO = $rowUbicacionCentroFisico->ID_UBICACION_CENTRO_FISICO
                                , ID_ALMACEN = $rowAlmacen->ID_ALMACEN
                                , VALIDA_STOCK_DISPONIBLE = " . ((($rowUbicacionCentroFisico->TIPO_UBICACION_CF != NULL) && (in_array((string)$rowUbicacionCentroFisico->TIPO_UBICACION_CF, (array)$arrUbicacionNoContabilizaStockDisponible))) ? 0 : 1) . "
                                , TIPO_UBICACION = " . ($rowUbicacionCentroFisico->TIPO_UBICACION_CF == NULL ? 'NULL' : "'" . $rowUbicacionCentroFisico->TIPO_UBICACION_CF . "'") . "
                                , ID_UBICACION_CATEGORIA = " . ($rowUbicacionCentroFisico->ID_UBICACION_CATEGORIA == NULL ? 'NULL' : $rowUbicacionCentroFisico->ID_UBICACION_CATEGORIA) . "
                                , CLASE_APQ = " . ($rowUbicacionCentroFisico->CLASE_APQ == NULL ? 'NULL' : "'" . $rowUbicacionCentroFisico->CLASE_APQ . "'") . "
                                , AUTOSTORE = '" . $rowUbicacionCentroFisico->UBICACION_AUTOSTORE . "'
                                , PRECIO_FIJO = '" . $rowUbicacionCentroFisico->PRECIO_FIJO . "'";
                $bd->ExecSQL($sqlUbicacion);
                $idUbicacion = $bd->IdAsignado();

                // LOG MOVIMIENTOS
                $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Creación", "Maestro", $idUbicacion, "Ubicación desde CF");
            endif;
        endwhile;

        //ACTUALIZAMOS LAS UBICACIONES
        if (count($ubicacionesActualizar) > 0):
            foreach ($ubicacionesActualizar as $rowUbicacion):
                //ACTUALIZAMOS CAMPOS COMUNES
                $sqlUbicacion = "UPDATE UBICACION SET
                               ID_UBICACION_CATEGORIA = " . ($rowUbicacionCentroFisico->ID_UBICACION_CATEGORIA == NULL ? 'NULL' : $rowUbicacionCentroFisico->ID_UBICACION_CATEGORIA) . "
                             , ID_UBICACION_CENTRO_FISICO = $rowUbicacionCentroFisico->ID_UBICACION_CENTRO_FISICO
                             , CLASE_APQ = " . ($rowUbicacionCentroFisico->CLASE_APQ == NULL ? 'NULL' : "'" . $rowUbicacionCentroFisico->CLASE_APQ . "'") . "
                             , AUTOSTORE = '" . $rowUbicacionCentroFisico->UBICACION_AUTOSTORE . "'
                             , PRECIO_FIJO = '" . $rowUbicacionCentroFisico->PRECIO_FIJO . "'
                             WHERE ID_UBICACION = $rowUbicacion->ID_UBICACION";
                $bd->ExecSQL($sqlUbicacion);

                //BUSCO LA UBICACION ACTUALIZADA
                $rowUbicacionActualizada = $bd->VerReg("UBICACION", "ID_UBICACION", $rowUbicacion->ID_UBICACION);

                // LOG MOVIMIENTOS
                $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Maestro", $rowUbicacion->ID_UBICACION, "Ubicación desde CF", "UBICACION", $rowUbicacion, $rowUbicacionActualizada);
            endforeach;
        endif;

        return true;
    }


    /**
     *
     * @param $idAlmacen
     * @param $idMaterial
     * @param $idUbicacion
     * @return idUbicacion
     */
    function reubicarMaterialStockPorUbicacion($idAlmacen, $idMaterial, $idUbicacion, $listaUbicacionesUtilizadas = "")
    {

        global $bd;
        //BUSCAMOS ALMACEN
        $rowAlmacen = $bd->VerReg("ALMACEN", "ID_ALMACEN", $idAlmacen);

        //BUSCAMOS EL MATERIAL
        $rowMaterial = $bd->VerReg("MATERIAL", "ID_MATERIAL", $idMaterial);

        //BUSCO LA UBICACION
        $rowUbicacion = $bd->VerReg("UBICACION", "ID_UBICACION", $idUbicacion);

        //BUSCO EL TIPO DE BLOQUEO PREVENTIVO
        $rowTipoBloqueoPreventivo = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "SP");
        //BUSCO EL TIPO DE BLOQUEO NO REPARABLE NO GARANTIA
        $rowTipoBloqueoNoReparablaNoGarantia = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "QNRNG");

        $rowMaterialUbicacion = $bd->VerRegRest("MATERIAL_UBICACION", "ID_MATERIAL = $rowMaterial->ID_MATERIAL AND ID_UBICACION = $rowUbicacion->ID_UBICACION", "No");


        //BUSCO EL TIPO DE BLOQUEO EN CASO DE NO SER NULO
        if ($rowMaterialUbicacion->ID_TIPO_BLOQUEO != NULL):
            //BUSCO EL TIPO DE BLOQUEO
            $rowTipoBloqueo = $bd->VerReg("TIPO_BLOQUEO", "ID_TIPO_BLOQUEO", $rowMaterialUbicacion->ID_TIPO_BLOQUEO);

            //SI EL TIPO DE BLOQUEO ES NO REPARABLE: QNRNG (NR) EL TIPO DE UBICACION ES ACHATARRAR
            if ($rowTipoBloqueo->ID_TIPO_BLOQUEO == $rowTipoBloqueoNoReparablaNoGarantia->ID_TIPO_BLOQUEO):
                $materialAchatarrar = 1;
            endif;

            //SI EL TIPO DE BLOQUEO ES DE CALIDAD (EMPIEZA POR X) EL MATERIAL SE RECEPCIONARA EN LA UBICACION DE TIPO 'Traslado Calidad'
            if (substr((string)$rowTipoBloqueo->TIPO_BLOQUEO_INTERNO, 0, 1) == 'X'):
                $materialCalidad = 1;
            endif;
        endif;
        //BUSCO EL MOVIMIENTO DE LA ORDEN DE TRABAJO EN CASO DE NO SER NULO
        if ($rowMaterialUbicacion->ID_ORDEN_TRABAJO_MOVIMIENTO != NULL):
            //BUSCO EL MOVIMIENTO DE LA ORDEN DE TRABAJO
            $rowOrdenTrabajoMovimiento = $bd->VerReg("ORDEN_TRABAJO_MOVIMIENTO", "ID_ORDEN_TRABAJO_MOVIMIENTO", $rowMaterialUbicacion->ID_ORDEN_TRABAJO_MOVIMIENTO);

            //RECUPERO LA DIRECCION DE ENVIO DEL MATERIAL
            $idProveedorEnvio = $rowOrdenTrabajoMovimiento->ID_PROVEEDOR_GARANTIA;
        endif;

        //DETERMINO LOS TIPOS DE UBICACION ESPECIAL VALIDOS
        $tiposUbicacionValidos = "'Preventivo', 'Achatarrar', 'Traslado Calidad', 'Construcción', 'Material Estropeado','Picking','Reubicacion','Calidad','Pendiente Ubicar','Reparación','Pendiente Asignación','Sector','Consumos Masivos','Retornos Masivos','Componentes AGM','AGM'";

        //GENERO LA CONSULTA DE BUSQUEDA DE UBICACIONES
        if ($rowAlmacen->CATEGORIA_ALMACEN == 'Construccion: Instalacion'): //RECEPCION EN ALMACEN DE CONTRUCCION
            $sqlBuscaUbicaciones = "SELECT U.ID_UBICACION, U.UBICACION
                                    FROM UBICACION U
                                    WHERE U.ID_ALMACEN = $rowAlmacen->ID_ALMACEN AND U.TIPO_UBICACION = 'Construcción' AND U.BAJA = 0";
        elseif ($rowTipoBloqueo->ID_TIPO_BLOQUEO == NULL): //TIPO BLOQUEO OK
            $sqlBuscaUbicaciones = "SELECT U.ID_UBICACION, U.UBICACION
                                    FROM MATERIAL_UBICACION MU
                                    INNER JOIN UBICACION U ON U.ID_UBICACION = MU.ID_UBICACION
                                    WHERE U.ID_ALMACEN = $rowAlmacen->ID_ALMACEN AND U.TIPO_UBICACION IS NULL AND MU.ID_MATERIAL = $rowMaterial->ID_MATERIAL AND MU.ID_TIPO_BLOQUEO IS NULL AND U.BAJA = 0
                                    ORDER BY MU.STOCK_TOTAL DESC, U.UBICACION ASC";
        elseif ($rowTipoBloqueo->ID_TIPO_BLOQUEO == $rowTipoBloqueoPreventivo->ID_TIPO_BLOQUEO): //TIPO BLOQUEO PREVENTIVO
            $sqlBuscaUbicaciones = "SELECT U.ID_UBICACION, U.UBICACION
                                    FROM UBICACION U
                                    WHERE U.ID_ALMACEN = $rowAlmacen->ID_ALMACEN AND U.TIPO_UBICACION = 'Preventivo' AND U.BAJA = 0";
        elseif ($materialAchatarrar == 1): //TIPO BLOQUEO ACHATARRAR
            //VARIABLE PARA SABER SI EXISTEN UBICACIONES PARA PROPONER
            $existenUbicacionesPropuestas = false;

            //SI NO EXISTEN UBICACIONES PARA PROPONER, BUSCO UBICACIONES DONDE EXISTA MATERIAL ESTROPEADO DE LA MISMA REFERENCIA Y TIPO DE BLOQUEO
            if ($existenUbicacionesPropuestas == false):
                //BUSCO UBICACIONES DONDE EXISTA MATERIAL ESTROPEADO DE LA MISMA REFERENCIA Y TIPO DE BLOQUEO
                $sqlBuscaUbicaciones    = "SELECT U.ID_UBICACION, U.UBICACION
                                            FROM MATERIAL_UBICACION MU
                                            INNER JOIN UBICACION U ON U.ID_UBICACION = MU.ID_UBICACION
                                            WHERE U.ID_ALMACEN = $rowAlmacen->ID_ALMACEN AND (U.TIPO_UBICACION IS NULL OR U.TIPO_UBICACION IN ($tiposUbicacionValidos)) AND MU.ID_MATERIAL = $rowMaterial->ID_MATERIAL AND MU.ID_TIPO_BLOQUEO = $rowTipoBloqueo->ID_TIPO_BLOQUEO AND MU.ACTIVO = 1 AND U.BAJA = 0
                                            ORDER BY MU.STOCK_TOTAL DESC, U.UBICACION ASC";
                $resultBuscaUbicaciones = $bd->ExecSQL($sqlBuscaUbicaciones);
                if ($bd->NumRegs($resultBuscaUbicaciones) > 0):
                    //ACTUALIZO LA VARIABLE PARA SABER SI EXISTEN UBICACIONES PARA PROPONER
                    $existenUbicacionesPropuestas = true;
                endif;
            endif;

            //SI NO EXISTEN UBICACIONES PARA PROPONER, BUSCO UBICACIONES DONDE HAYA EXISTIDO MATERIAL ESTROPEADO DE LA MISMA REFERENCIA Y TIPO DE BLOQUEO
            if ($existenUbicacionesPropuestas == false):
                //BUSCO UBICACIONES DONDE HAYA EXISTIDO MATERIAL ESTROPEADO DE LA MISMA REFERENCIA Y TIPO DE BLOQUEO
                $sqlBuscaUbicaciones    = "SELECT U.ID_UBICACION, U.UBICACION
                                            FROM MATERIAL_UBICACION MU
                                            INNER JOIN UBICACION U ON U.ID_UBICACION = MU.ID_UBICACION
                                            WHERE U.ID_ALMACEN = $rowAlmacen->ID_ALMACEN AND (U.TIPO_UBICACION IS NULL OR U.TIPO_UBICACION IN ($tiposUbicacionValidos)) AND MU.ID_MATERIAL = $rowMaterial->ID_MATERIAL AND MU.ID_TIPO_BLOQUEO = $rowTipoBloqueo->ID_TIPO_BLOQUEO AND MU.ACTIVO = 0 AND U.BAJA = 0
                                            ORDER BY U.UBICACION ASC";
                $resultBuscaUbicaciones = $bd->ExecSQL($sqlBuscaUbicaciones);
                if ($bd->NumRegs($resultBuscaUbicaciones) > 0):
                    //ACTUALIZO LA VARIABLE PARA SABER SI EXISTEN UBICACIONES PARA PROPONER
                    $existenUbicacionesPropuestas = true;
                endif;
            endif;
        elseif ($materialCalidad == 1): //TIPO BLOQUEO CALIDAD
            $sqlBuscaUbicaciones = "SELECT U.ID_UBICACION, U.UBICACION
                                    FROM UBICACION U
                                    WHERE U.ID_ALMACEN = $rowAlmacen->ID_ALMACEN AND U.TIPO_UBICACION = 'Traslado Calidad' AND U.ID_UBICACION AND U.BAJA = 0";
        elseif ($idProveedorEnvio != NULL): //MATERIAL CON DIRECCION DE ENVIO, EL MATERIAL SE AGRUPA EN UBICACIONES PARA ENVIAR A ESE PROVEEDOR
            //BUSCO EL TIPO DE BLOQUEO NO REPARABLE NO GARANTIA
            $rowTipoBloqueoNoReparableNoGarantia = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "QNRNG");
            //BUSCO EL TIPO DE BLOQUEO RETENIDO POR CALIDAD NO PREVENTIVO NO REPARABLE NO GARANTIA
            $rowTipoBloqueoRetenidoCalidadNoPreventivoNoReparableNoEnGarantia = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "XRCNRNG");

            //VARIABLE PARA SABER SI EXISTEN UBICACIONES PARA PROPONER
            $existenUbicacionesPropuestas = false;

            //SI NO EXISTEN UBICACIONES PARA PROPONER, BUSCO UBICACIONES DONDE EXISTA MATERIAL ESTROPEADO CON DIRECCION EL PROVEEDOR GARANTIA
            if ($existenUbicacionesPropuestas == false):
                //BUSCO UBICACIONES DONDE EXISTA MATERIAL ESTROPEADO CON DIRECCION EL PROVEEDOR GARANTIA Y EL TIPO DE BLOQUEO SE DIFERENTE DE NO REPARABLE
                $sqlBuscaUbicaciones    = "SELECT U.ID_UBICACION, U.UBICACION
                                        FROM MATERIAL_UBICACION MU
                                        INNER JOIN UBICACION U ON U.ID_UBICACION = MU.ID_UBICACION
                                        INNER JOIN ORDEN_TRABAJO_MOVIMIENTO OTM ON OTM.ID_ORDEN_TRABAJO_MOVIMIENTO = MU.ID_ORDEN_TRABAJO_MOVIMIENTO
                                        WHERE U.ID_ALMACEN = $rowAlmacen->ID_ALMACEN AND (U.TIPO_UBICACION IS NULL OR U.TIPO_UBICACION IN ($tiposUbicacionValidos)) AND OTM.ID_PROVEEDOR_GARANTIA = $idProveedorEnvio AND MU.ACTIVO = 1 AND U.BAJA = 0 AND MU.ID_TIPO_BLOQUEO <> $rowTipoBloqueoNoReparableNoGarantia->ID_TIPO_BLOQUEO AND MU.ID_TIPO_BLOQUEO <> $rowTipoBloqueoRetenidoCalidadNoPreventivoNoReparableNoEnGarantia->ID_TIPO_BLOQUEO
                                        ORDER BY MU.STOCK_TOTAL DESC, U.UBICACION ASC";
                $resultBuscaUbicaciones = $bd->ExecSQL($sqlBuscaUbicaciones);
                if ($bd->NumRegs($resultBuscaUbicaciones) > 0):
                    //ACTUALIZO LA VARIABLE PARA SABER SI EXISTEN UBICACIONES PARA PROPONER
                    $existenUbicacionesPropuestas = true;
                endif;
            endif;

            //SI NO EXISTEN UBICACIONES PARA PROPONER, BUSCO UBICACIONES DONDE EXISTA MATERIAL ESTROPEADO DE LA MISMA REFERENCIA Y TIPO DE BLOQUEO
            if ($existenUbicacionesPropuestas == false):
                //BUSCO UBICACIONES DONDE EXISTA MATERIAL ESTROPEADO DE LA MISMA REFERENCIA Y TIPO DE BLOQUEO
                $sqlBuscaUbicaciones    = "SELECT U.ID_UBICACION, U.UBICACION
                                        FROM MATERIAL_UBICACION MU
                                        INNER JOIN UBICACION U ON U.ID_UBICACION = MU.ID_UBICACION
                                        WHERE U.ID_ALMACEN = $rowAlmacen->ID_ALMACEN AND (U.TIPO_UBICACION IS NULL OR U.TIPO_UBICACION IN ($tiposUbicacionValidos)) AND MU.ID_MATERIAL = $rowMaterial->ID_MATERIAL AND MU.ID_TIPO_BLOQUEO = $rowTipoBloqueo->ID_TIPO_BLOQUEO AND MU.ACTIVO = 1 AND U.BAJA = 0 AND MU.ID_TIPO_BLOQUEO <> $rowTipoBloqueoNoReparableNoGarantia->ID_TIPO_BLOQUEO AND MU.ID_TIPO_BLOQUEO <> $rowTipoBloqueoRetenidoCalidadNoPreventivoNoReparableNoEnGarantia->ID_TIPO_BLOQUEO
                                        ORDER BY MU.STOCK_TOTAL DESC, U.UBICACION ASC";
                $resultBuscaUbicaciones = $bd->ExecSQL($sqlBuscaUbicaciones);
                if ($bd->NumRegs($resultBuscaUbicaciones) > 0):
                    //ACTUALIZO LA VARIABLE PARA SABER SI EXISTEN UBICACIONES PARA PROPONER
                    $existenUbicacionesPropuestas = true;
                endif;
            endif;

            //SI NO EXISTEN UBICACIONES PARA PROPONER, BUSCO UBICACIONES DONDE HAYA EXISTIDO MATERIAL ESTROPEADO CON DIRECCION EL PROVEEDOR GARANTIA
            if ($existenUbicacionesPropuestas == false):
                //BUSCO UBICACIONES DONDE HAYA EXISTIDO MATERIAL ESTROPEADO CON DIRECCION EL PROVEEDOR GARANTIA
                $sqlBuscaUbicaciones    = "SELECT U.ID_UBICACION, U.UBICACION
                                        FROM MATERIAL_UBICACION MU
                                        INNER JOIN UBICACION U ON U.ID_UBICACION = MU.ID_UBICACION
                                        INNER JOIN ORDEN_TRABAJO_MOVIMIENTO OTM ON OTM.ID_ORDEN_TRABAJO_MOVIMIENTO = MU.ID_ORDEN_TRABAJO_MOVIMIENTO
                                        WHERE U.ID_ALMACEN = $rowAlmacen->ID_ALMACEN AND (U.TIPO_UBICACION IS NULL OR U.TIPO_UBICACION IN ($tiposUbicacionValidos)) AND OTM.ID_PROVEEDOR_GARANTIA = $idProveedorEnvio AND MU.ACTIVO = 0 AND U.BAJA = 0 AND MU.ID_TIPO_BLOQUEO <> $rowTipoBloqueoNoReparableNoGarantia->ID_TIPO_BLOQUEO AND MU.ID_TIPO_BLOQUEO <> $rowTipoBloqueoRetenidoCalidadNoPreventivoNoReparableNoEnGarantia->ID_TIPO_BLOQUEO
                                        ORDER BY U.UBICACION ASC";
                $resultBuscaUbicaciones = $bd->ExecSQL($sqlBuscaUbicaciones);
                if ($bd->NumRegs($resultBuscaUbicaciones) > 0):
                    //ACTUALIZO LA VARIABLE PARA SABER SI EXISTEN UBICACIONES PARA PROPONER
                    $existenUbicacionesPropuestas = true;
                endif;
            endif;

            //SI NO EXISTEN UBICACIONES PARA PROPONER, BUSCO UBICACIONES DONDE HAYA EXISTIDO MATERIAL ESTROPEADO DE LA MISMA REFERENCIA Y TIPO DE BLOQUEO
            if ($existenUbicacionesPropuestas == false):
                //BUSCO UBICACIONES DONDE HAYA EXISTIDO MATERIAL ESTROPEADO DE LA MISMA REFERENCIA Y TIPO DE BLOQUEO
                $sqlBuscaUbicaciones    = "SELECT U.ID_UBICACION, U.UBICACION
                                        FROM MATERIAL_UBICACION MU
                                        INNER JOIN UBICACION U ON U.ID_UBICACION = MU.ID_UBICACION
                                        WHERE U.ID_ALMACEN = $rowAlmacen->ID_ALMACEN AND (U.TIPO_UBICACION IS NULL OR U.TIPO_UBICACION IN ($tiposUbicacionValidos)) AND MU.ID_MATERIAL = $rowMaterial->ID_MATERIAL AND MU.ID_TIPO_BLOQUEO = $rowTipoBloqueo->ID_TIPO_BLOQUEO AND MU.ACTIVO = 0 AND U.BAJA = 0 AND MU.ID_TIPO_BLOQUEO <> $rowTipoBloqueoNoReparableNoGarantia->ID_TIPO_BLOQUEO AND MU.ID_TIPO_BLOQUEO <> $rowTipoBloqueoRetenidoCalidadNoPreventivoNoReparableNoEnGarantia->ID_TIPO_BLOQUEO
                                        ORDER BY U.UBICACION ASC";
                $resultBuscaUbicaciones = $bd->ExecSQL($sqlBuscaUbicaciones);
                if ($bd->NumRegs($resultBuscaUbicaciones) > 0):
                    //ACTUALIZO LA VARIABLE PARA SABER SI EXISTEN UBICACIONES PARA PROPONER
                    $existenUbicacionesPropuestas = true;
                endif;
            endif;

        else: //POR DEFECTO QUE COINCIDA EL TIPO DE BLOQUEO
            $sqlBuscaUbicaciones = "SELECT U.ID_UBICACION, U.UBICACION
                                    FROM MATERIAL_UBICACION MU
                                    INNER JOIN UBICACION U ON U.ID_UBICACION = MU.ID_UBICACION
                                    WHERE U.ID_ALMACEN = $rowAlmacen->ID_ALMACEN AND (U.TIPO_UBICACION IS NULL OR U.TIPO_UBICACION IN ($tiposUbicacionValidos)) AND MU.ID_MATERIAL = $rowMaterial->ID_MATERIAL AND MU.ID_TIPO_BLOQUEO = $rowTipoBloqueo->ID_TIPO_BLOQUEO AND U.BAJA = 0
                                    ORDER BY MU.STOCK_TOTAL DESC, U.UBICACION ASC";
        endif;

        //SI NO ESTA DEFINIDA LA CONSULTA, RETORNO NULO
        if (!(isset($sqlBuscaUbicaciones))):
            return NULL;
        else:
            //echo($sqlBuscaUbicaciones);
        endif;

        //REALIZO LA BUSQUEDA DE LA UBICACION POR DEFECTO
        $resultBuscaUbicaciones = $bd->ExecSQL($sqlBuscaUbicaciones);

        //SI SE OBTIENEN REGISTROS RETORNO EL REGISTRO, SINO NULO
        if ($bd->NumRegs($resultBuscaUbicaciones) > 0):
            $rowUbicacionPorDefecto = $bd->SigReg($resultBuscaUbicaciones);

            return $rowUbicacionPorDefecto;
        else:
            return NULL;
        endif;
    }

    function ObtenerCodigoUbicacionQR($txUbicacion, $txTipoSector = "")
    {

        global $bd, $administrador, $html;

        //COMPROBAMOS QUE NI LA UBICACION NI EL TIPO_SECTOR TENGAN EL SEPARADOR
        if ((strpos((string)$txUbicacion, SEPARADOR_UBICACION_TIPO_SECTOR) !== false) || (strpos((string)$txTipoSector, SEPARADOR_UBICACION_TIPO_SECTOR) !== false)):
            $html->PagError("CodigoIncorrectoParaQR");
        endif;

        //AÑADIMOS LA UBICACION
        $codigoQR = $txUbicacion;

        //SI TIENE TIPO_SECTOR, LO AÑADIMOS CON EL SEPARADOR
        if ($txTipoSector != ""):
            $codigoQR .= SEPARADOR_UBICACION_TIPO_SECTOR . $txTipoSector;
        endif;

        return $codigoQR;
    }

} // FIN CLASE