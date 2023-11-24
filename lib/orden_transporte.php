<?php

# orden_transporte
# Clase orden_transporte contiene todas las funciones necesarias para la interaccion con la clase orden de transporte
# Se incluira en las sesiones

class orden_transporte
{

    function __construct()
    {
    } // Fin orden_transporte

    /**
     * @param $idOrdenTransporte ORDEN DE TRANSPORTE SOBRE LA QUE ACTUALIZAR EL PESO
     * FUNCION UTILIZADA PARA RECALCULAR EL PESO DE LA ORDEN DE TRANSPORTE
     */
    function ActualizarPeso($idOrdenTransporte)
    {
        global $bd;
        global $html;
        global $administrador;

        //BUSCO LA ORDEN DE TRANSPORTE
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowOrdenTransporte               = $bd->VerReg("ORDEN_TRANSPORTE", "ID_ORDEN_TRANSPORTE", $idOrdenTransporte, "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);

        //COMPRUEBO QUE EXISTA LA ORDEN DE TRANSPORTE
        $html->PagErrorCondicionado($rowOrdenTransporte, "==", false, "OrdenTransporteNoEncontrada");

        //COMPRUEBO QUE LA ORDEN DE TRANSPORTE NO ESTE DADA DE BAJA
        $html->PagErrorCondicionado($rowOrdenTransporte->BAJA, "!=", 0, "OrdenTransporteBaja");

        //VARIABLE PARA ALMACENAR EL PESO DE LA ORDEN DE TRANSPORTE
        $pesoOrdenTransporte = 0;

        //BUSCO LAS ORDENES DE RECOGIDA PARA SUMAR SUS PESOS EXCEPTO LAS RECOGIDAS EN PROVEEDOR
        $sqlOrdenesRecogida    = "SELECT PESO
                                  FROM EXPEDICION 
                                  WHERE ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND BAJA = 0 AND TIPO_ORDEN_RECOGIDA <> 'Recogida en Proveedor'";
        $resultOrdenesRecogida = $bd->ExecSQL($sqlOrdenesRecogida);
        while ($rowOrdenRecogida = $bd->SigReg($resultOrdenesRecogida)):
            $pesoOrdenTransporte = $pesoOrdenTransporte + $rowOrdenRecogida->PESO;
        endwhile;

        //SE REVISA SI LA ORDEN DE TRANSPORTE TIENE RECEPCIONES ASOCIADAS
        $num = $bd->NumRegsTabla("MOVIMIENTO_RECEPCION", "ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND BAJA = 0");

        //SE SUMAN LOS PESOS DE LAS RECEPCIONES O DE LAS RECOGIDAS EN PROVEEDOR EN FUNCION DE SI LA ORDEN DE TRANSPORTE TIENE RECEPCIONES O NO
        if ($num > 0): //SI TIENE RECEPCIONES, SE TIENE EN CUENTA EL PESO DE ESTAS
            //SE OBTIENE EL PESO DE LAS RECEPCIONES
            $sqlRecepciones    = "SELECT PESO
                                  FROM MOVIMIENTO_RECEPCION 
                                  WHERE ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND TIPO_RECEPCION = 'PedidoProveedor' AND BAJA = 0";
            $resultRecepciones = $bd->ExecSQL($sqlRecepciones);
            while ($rowRecepcion = $bd->SigReg($resultRecepciones)):
                $pesoOrdenTransporte = $pesoOrdenTransporte + $rowRecepcion->PESO;
            endwhile;
        else: //SI NO TIENE RECEPCIONES, SE TIENE EN CUENTA EL PESO DE LAS RECOGIDAS EN PROVEEDOR
            //SE OBTIENE EL PESO DE LAS RECOGIDAS EN PROVEEDOR
            $sqlOrdenesRecogida    = "SELECT PESO
                                      FROM EXPEDICION 
                                      WHERE ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND BAJA = 0 AND TIPO_ORDEN_RECOGIDA = 'Recogida en Proveedor'";
            $resultOrdenesRecogida = $bd->ExecSQL($sqlOrdenesRecogida);
            while ($rowOrdenRecogida = $bd->SigReg($resultOrdenesRecogida)):
                $pesoOrdenTransporte = $pesoOrdenTransporte + $rowOrdenRecogida->PESO;
            endwhile;
        endif;

        //BUSCAMOS LA UNIDAD DEL PESO DE LA ORDEN DE TRANSPORTE
        $rowUnidadPeso = $bd->VerReg("UNIDAD", "UNIDAD", 'KG');

        //ACTUALIZO EL PESO DE LA ORDEN DE TRANSPORTE
        $sqlUpdate = "UPDATE ORDEN_TRANSPORTE SET
                        ID_ADMINISTRADOR_ULTIMA_MODIFICACION = $administrador->ID_ADMINISTRADOR
                        , FECHA_ULTIMA_MODIFICACION = '" . date("Y-m-d H:i:s") . "'
                        , PESO = $pesoOrdenTransporte
                        , ID_UNIDAD_PESO = $rowUnidadPeso->ID_UNIDAD
                        WHERE ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE";
        $bd->ExecSQL($sqlUpdate);
    }

    /**
     * @param $idOrdenTransporte ORDEN DE TRANSPORTE SOBRE LA QUE ACTUALIZAR EL IMPORTE
     * FUNCION UTILIZADA PARA RECALCULAR EL IMPORTE DE LA ORDEN DE TRANSPORTE
     */
    function ActualizarImporte($idOrdenTransporte)
    {
        global $bd;
        global $html;
        global $administrador;

        //BUSCO LA ORDEN DE TRANSPORTE
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowOrdenTransporte               = $bd->VerReg("ORDEN_TRANSPORTE", "ID_ORDEN_TRANSPORTE", $idOrdenTransporte, "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);

        //COMPRUEBO QUE EXISTA LA ORDEN DE TRANSPORTE
        $html->PagErrorCondicionado($rowOrdenTransporte, "==", false, "OrdenTransporteNoEncontrada");

        //COMPRUEBO QUE LA ORDEN DE TRANSPORTE NO ESTE DADA DE BAJA
        $html->PagErrorCondicionado($rowOrdenTransporte->BAJA, "!=", 0, "OrdenTransporteBaja");

        //VARIABLE PARA ALMACENAR EL IMPORTE DE LA ORDEN DE TRANSPORTE
        $importeOrdenTransporte = 0;

        //BUSCO LAS ORDENES DE CONTRATACION NO RECHAZADAS O CANCELADAS PARA SUMAR SUS IMPORTES
        $sqlOrdenesContratacion    = "SELECT *
                                    FROM ORDEN_CONTRATACION OC
                                    WHERE OC.ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND OC.BAJA = 0 AND OC.ESTADO<>'Rechazada' AND OC.ESTADO<>'Cancelada'";
        $resultOrdenesContratacion = $bd->ExecSQL($sqlOrdenesContratacion);
        while ($rowOrdenContratacion = $bd->SigReg($resultOrdenesContratacion)):
            $importeOrdenTransporte = $importeOrdenTransporte + $rowOrdenContratacion->IMPORTE;
        endwhile;

        //ACTUALIZO EL PESO DE LA ORDEN DE TRANSPORTE
        $sqlUpdate = "UPDATE ORDEN_TRANSPORTE SET
                        ID_ADMINISTRADOR_ULTIMA_MODIFICACION = $administrador->ID_ADMINISTRADOR
                        , FECHA_ULTIMA_MODIFICACION = '" . date("Y-m-d H:i:s") . "'
                        , IMPORTE = $importeOrdenTransporte
                        WHERE ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE";
        //$bd->ExecSQL($sqlUpdate);//NO ACTUALIZAREMOS IMPORTE PORQUE SE VAN A MEZCLAR CONTRATACIONES EN DIFERENTES MONEDAS
    }

    /**
     * @param $idOrdenTransporte ORDEN DE TRANSPORTE SOBRE LA QUE ACTUALIZAR EL IMPORTE
     * FUNCION UTILIZADA PARA ACTUALIZAR EL NUMERO DE CONTRATACIONES DE LA ORDEN DE TRANSPORTE
     */
    function ActualizarNumeroContrataciones($idOrdenTransporte)
    {
        global $bd;
        global $html;
        global $administrador;

        //BUSCO LA ORDEN DE TRANSPORTE
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowOrdenTransporte               = $bd->VerReg("ORDEN_TRANSPORTE", "ID_ORDEN_TRANSPORTE", $idOrdenTransporte, "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);

        //COMPRUEBO QUE EXISTA LA ORDEN DE TRANSPORTE
        $html->PagErrorCondicionado($rowOrdenTransporte, "==", false, "OrdenTransporteNoEncontrada");

        //COMPRUEBO QUE LA ORDEN DE TRANSPORTE NO ESTE DADA DE BAJA
        $html->PagErrorCondicionado($rowOrdenTransporte->BAJA, "!=", 0, "OrdenTransporteBaja");

        //BUSCO LAS ORDENES DE CONTRATACION NO RECHAZADAS O CANCELADAS PARA SUMAR SUS IMPORTES
        $sqlOrdenesContratacion    = "SELECT COUNT(*) AS NUM 
                                        FROM ORDEN_CONTRATACION OC
                                        WHERE OC.ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND OC.BAJA = 0 AND OC.ESTADO <> 'Rechazada' AND OC.ESTADO <> 'Cancelada'";
        $resultOrdenesContratacion = $bd->ExecSQL($sqlOrdenesContratacion);
        $rowOrdenContratacion      = $bd->SigReg($resultOrdenesContratacion);

        //ACTUALIZO EL NUMERO DE CONTRATACIONES DE LA ORDEN DE TRANSPORTE
        $sqlUpdate = "UPDATE ORDEN_TRANSPORTE SET
                        NUMERO_CONTRATACIONES = $rowOrdenContratacion->NUM 
                        WHERE ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE";
        $bd->ExecSQL($sqlUpdate);//NO ACTUALIZAREMOS IMPORTE PORQUE SE VAN A MEZCLAR CONTRATACIONES EN DIFERENTES MONEDAS
    }

    /**
     * @param $idOrdenContratacion ORDEN DE CONTRATACION SOBRE LA QUE CALCULAR LA REFERENCIA FACTURACION
     * FUNCION UTILIZADA PARA CALCULAR Y DEVOLVER LA REFENCIA FACUTRACION DE UN PROVEEDOR
     */
    function ObtenerReferenciaFacturacion($idOrdenContratacion, $tipoFacturacion = "", $fecha_contabilizacion = "", $primeraContratacion = true)
    {

        global $bd;
        global $html;
        global $auxiliar;
        global $administrador;

        //BUSCO LA ORDEN DE CONTRATACION
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowOrdenContratacion             = $bd->VerReg("ORDEN_CONTRATACION", "ID_ORDEN_CONTRATACION", $idOrdenContratacion, "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);

        //COMPRUEBO QUE EXISTA LA ORDEN DE TRANSPORTE
        if ($rowOrdenContratacion == false):
            return '';
        endif;
        //$html->PagErrorCondicionado($rowOrdenContratacion, "==", false, "OrdenContratacionNoEncontrada");

        //COMPRUEBO QUE TENGA ASIGNADO PROVEEDOR
        if ($rowOrdenContratacion->ID_PROVEEDOR == false):
            return '';
        endif;
        //$html->PagErrorCondicionado($rowOrdenContratacion->ID_PROVEEDOR, "==", false, "FaltaProveedor");

        //COMPRUEBO QUE TENGA ASIGNADO PROVEEDOR
        if ($rowOrdenContratacion->FECHA_EJECUCION == false):
            return '';
        endif;
        //$html->PagErrorCondicionado($rowOrdenContratacion->FECHA_EJECUCION, "==", false, "FaltanDestinos");

        //BUSCO LA TARIFA PARA OBTENER EL NUMERO DE CONTRATO
        $rowTarifa = "";
        if ($rowOrdenContratacion->ID_TARIFA != NULL):
            $rowTarifa = $bd->VerReg("TARIFA", "ID_TARIFA", $rowOrdenContratacion->ID_TARIFA);
        endif;

        //BUSCO EL PROVEEDOR
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowProveedor                     = $bd->VerReg("PROVEEDOR", "ID_PROVEEDOR", $rowOrdenContratacion->ID_PROVEEDOR);
        unset($GLOBALS["NotificaErrorPorEmail"]);

        //BUSCO LA ORDEN DE TRANSPORTE
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowOrdenTransporte               = $bd->VerReg("ORDEN_TRANSPORTE", "ID_ORDEN_TRANSPORTE", $rowOrdenContratacion->ID_ORDEN_TRANSPORTE);
        unset($GLOBALS["NotificaErrorPorEmail"]);

        //BUSCO EL CENTRO CONTRATANTE
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowCentroContratante             = $bd->VerReg("CENTRO", "ID_CENTRO", $rowOrdenTransporte->ID_CENTRO_CONTRATANTE);
        unset($GLOBALS["NotificaErrorPorEmail"]);

        //BUSCO LA SOCIEDAD CONTRATANTE
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowSociedadContratante           = $bd->VerReg("SOCIEDAD", "ID_SOCIEDAD", $rowCentroContratante->ID_SOCIEDAD);
        unset($GLOBALS["NotificaErrorPorEmail"]);

        //SI ESTA INDICADO EN EL PROVEEDOR O SOCIEDAD QUE EL PERIODO MENSUAL ACABE ANTES
        $diaCierreMensual = 0;
        if (($rowProveedor->PERIODO_FACTURACION == "Mensual") && ($rowProveedor->DIA_CIERRE_PERIODO_FACTURACION > 0 || $rowSociedadContratante->DIA_CIERRE_PERIODO_FACTURACION > 0)):
            //SI EL DIA DE CIERRE ESTA INDICADO EN EL PROVEEDOR; PRIMA RESPECTO AL DE LA SOCIEDAD
            if ($rowProveedor->DIA_CIERRE_PERIODO_FACTURACION > 0):
                $diaCierreMensual = $rowProveedor->DIA_CIERRE_PERIODO_FACTURACION;
            else:
                $diaCierreMensual = $rowSociedadContratante->DIA_CIERRE_PERIODO_FACTURACION;
            endif;
        endif;

        //CALCULAMOS EL AÑO Y EL PERIODO ACTUAL
        $anioActual = date('Y');
        if ($rowProveedor->PERIODO_FACTURACION == "Mensual"):
            $num_periodo_actual      = date('m', mktime(0, 0, 0, date('m'), date('d'), date('Y')));
            $num_ultimo_periodo_anio = 12; //EL ULTIMO PERIODO MENUAL ES EL 12

            //SI ES MENSUAL, Y HAY INDICADO UN DIA DE CIERRE, Y EL DIA DE EJECUCION ES MAYOR QUE ESE DIA, AVANZAMOS EL PERIODO
            if ($diaCierreMensual > 0):
                if (date('d') > $diaCierreMensual):
                    $num_periodo_actual = $num_periodo_actual + 1;

                    //SI HEMOS AVANZADO DE AÑO, LO CONTROLAMOS
                    if ($num_periodo_actual == 13):
                        $num_periodo_actual = 1;
                        $anioActual         = $anioActual + 1;
                    endif;
                endif;
            endif;

        elseif ($rowProveedor->PERIODO_FACTURACION == "Semanal"):
            $num_periodo_actual      = date('W', mktime(0, 0, 0, date('m'), date('d'), date('Y')));
            $num_ultimo_periodo_anio = date('W', mktime(0, 0, 0, 12, 31, (int)$anioActual)); //CALCULAMOS LA ULTIMA SEMANA DEL AÑO

            //SI EL MES ES ENERO, LA PRIMERA SEMANA PUEDE QUE SE TOME COMO LA SEMANA 52 o 53 DEL ANTERIOR
            if (date('m') == 1 && ($num_periodo_actual == 52 || $num_periodo_actual == 53)):
                $anioActual = $anioActual - 1;
            endif;

        elseif ($rowProveedor->PERIODO_FACTURACION == "Quincenal"):
            //OBTENEMOS EL PERIODO ACTUAL
            $num_periodo_actual = date('z', mktime(0, 0, 0, date('m'), date('d'), date('Y')));
            $num_periodo_actual = intdiv($num_periodo_actual, 15) + 1;

            $num_ultimo_periodo_anio = date('z', mktime(0, 0, 0, 12, 31, (int)$anioActual)); //CALCULAMOS LA ULTIMA QUINCENA DEL AÑO
            $num_ultimo_periodo_anio = intdiv($num_ultimo_periodo_anio, 15) + 1;

            //SI EL MES ES ENERO, LA PRIMERA SEMANA PUEDE QUE SE TOME COMO LA QUINCENA 25 o 26 DEL ANTERIOR
            if ($num_periodo_actual == 1 && ($num_ultimo_periodo_anio == 25 || $num_ultimo_periodo_anio == 26)):
                $anioActual = $anioActual - 1;
            endif;
        endif;

        //CALCULO EL NUMERO DE PERIODO SEGUN EL TIPO DE PERIODO (1-12 para Mensual, 1-56 para Anual)
        $diaEjecucion  = substr((($fecha_contabilizacion != "") ? (string)$fecha_contabilizacion : (string)$rowOrdenContratacion->FECHA_EJECUCION), 8, 2);
        $mesEjecucion  = substr((($fecha_contabilizacion != "") ? (string)$fecha_contabilizacion : (string)$rowOrdenContratacion->FECHA_EJECUCION), 5, 2);
        $anioEjecucion = substr((($fecha_contabilizacion != "") ? (string)$fecha_contabilizacion : (string)$rowOrdenContratacion->FECHA_EJECUCION), 0, 4);
        if ($rowProveedor->PERIODO_FACTURACION == "Mensual"):
            $num_periodo = date('m', mktime(0, 0, 0, (int)$mesEjecucion, (int)$diaEjecucion, (int)$anioEjecucion));
        elseif ($rowProveedor->PERIODO_FACTURACION == "Semanal"):
            $num_periodo = date('W', mktime(0, 0, 0, (int)$mesEjecucion, (int)$diaEjecucion, (int)$anioEjecucion));

            //SI EL MES ES ENERO, LA PRIMERA SEMANA PUEDE QUE SE TOME COMO LA SEMANA 52,53 DEL ANTERIOR
            if ($mesEjecucion == 1 && ($num_periodo == 52 || $num_periodo == 53)):
                $anioEjecucion = $anioEjecucion - 1;
            endif;
        elseif ($rowProveedor->PERIODO_FACTURACION == "Quincenal"):
            $num_periodo = date('z', mktime(0, 0, 0, (int)$mesEjecucion, (int)$diaEjecucion, (int)$anioEjecucion));
            $num_periodo = intdiv($num_periodo, 15) + 1;

            //SI EL MES ES ENERO, LA PRIMERA SEMANA PUEDE QUE SE TOME COMO LA QUINCENA 25,26 DEL ANTERIOR
            if ($num_periodo == 1 && ($num_periodo == 25 || $num_periodo == 26)):
                $anioEjecucion = $anioEjecucion - 1;
            endif;
        else:
            $html->PagError("ProveedorSinPeriodoDefinido");
        endif;

        //SI ES MENSUAL, Y HAY INDICADO UN DIA DE CIERRE, Y EL DIA DE EJECUCION ES MAYOR QUE ESE DIA, AVANZAMOS EL PERIODO
        if ($diaCierreMensual > 0):
            if ($diaEjecucion > $diaCierreMensual):
                $num_periodo = $num_periodo + 1;

                //SI HEMOS AVANZADO DE AÑO, LO CONTROLAMOS
                if ($num_periodo == 13):
                    $num_periodo   = 1;
                    $anioEjecucion = $anioEjecucion + 1;
                endif;
            endif;
        endif;

        //SI EL PERIODO DE EJECUCION YA HA PASADO (EL NUMERO DE PERIODO DE EJECUCION DE ESTE AÑO ES MENOR QUE EL PERIODO ACTUAL O EL AÑO DE EJECUCION ES MENOR QUE EL ACTUAL), TOMAREMOS COMO PERIODO EL ACTUAL
        if ((($num_periodo < $num_periodo_actual) && ($anioEjecucion == $anioActual)) ||
            ($anioEjecucion < $anioActual)
        ):
            $diaEjecucion  = date('d');
            $mesEjecucion  = date('m');
            $anioEjecucion = $anioActual;
            $num_periodo   = $num_periodo_actual;
        endif;

        //SOLO DEJAMOS AÑO DE EJECUCION PARA ESTE AÑO Y EL SIGUIENTE
        if ($anioEjecucion < date('Y') || $anioEjecucion > (date('Y') + 1)):
            $html->PagError("AñoEjecucionFueraDeRango");
        endif;

        //BUSCO SI PARA ESE PROVEEDOR, CON ESA PERIODICIDAD Y ESA SOCIEDAD CONTRATANTE, HAY DEFINIDO UNA REFERENCIA PARA ESA FECHA DE EJECUCION
        if (($rowOrdenTransporte->MODELO_TRANSPORTE == 'Segundo') && ($rowOrdenContratacion->TIPO_ORDEN_TRANSPORTE != "OTC")):
            $sqlReferenciaFacturacion = "SELECT REFERENCIA_FACTURACION_NUEVO_MODELO AS REFERENCIA_FACTURACION
                                         FROM PROVEEDOR_REFERENCIA_FACTURACION
                                         WHERE ID_SOCIEDAD_CONTRATANTE = $rowCentroContratante->ID_SOCIEDAD AND ID_PROVEEDOR = $rowOrdenContratacion->ID_PROVEEDOR AND TIPO_TRANSPORTE = 'OT' AND PERIODO_FACTURACION = '$rowProveedor->PERIODO_FACTURACION' AND NUMERO_PERIODO = $num_periodo AND AÑO = $anioEjecucion AND ID_PROVEEDOR_CONTRATO " . ($rowTarifa->ID_PROVEEDOR_CONTRATO != "" ? " = $rowTarifa->ID_PROVEEDOR_CONTRATO " : " IS NULL ");
        else:
            $sqlReferenciaFacturacion = "SELECT REFERENCIA_FACTURACION
                                         FROM PROVEEDOR_REFERENCIA_FACTURACION
                                         WHERE ID_SOCIEDAD_CONTRATANTE = $rowCentroContratante->ID_SOCIEDAD AND ID_PROVEEDOR = $rowOrdenContratacion->ID_PROVEEDOR AND TIPO_TRANSPORTE = 'OT' AND PERIODO_FACTURACION = '$rowProveedor->PERIODO_FACTURACION' AND NUMERO_PERIODO = $num_periodo AND AÑO = $anioEjecucion AND ID_PROVEEDOR_CONTRATO " . ($rowTarifa->ID_PROVEEDOR_CONTRATO != "" ? " = $rowTarifa->ID_PROVEEDOR_CONTRATO " : " IS NULL ");
        endif;
        $resultReferenciaFacturacion = $bd->ExecSQL($sqlReferenciaFacturacion);
        $rowReferenciaFacturacion    = $bd->SigReg($resultReferenciaFacturacion);


        //SI YA EXISTE UNA REFERENCIA FACTURACION, USAMOS ESA
        if ((($rowReferenciaFacturacion != NULL) && ($rowReferenciaFacturacion->REFERENCIA_FACTURACION != '')) || ($tipoFacturacion != "")):
            $nuevaReferenciaFacturacion = $rowReferenciaFacturacion->REFERENCIA_FACTURACION;
            if ($tipoFacturacion != ""):
                if ($primeraContratacion):
                    //SI EL TIPO DE FACTURACION ES INDIVIDUAL O MANUAL, OBTENEMOS LA SIGUIENTE REFERENCIA DE FACTURACION
                    //BUSCO EL NUMERO DE FACTURACION QUE LE CORRESPONDA
                    $sqlConfigGral    = "SELECT ULTIMA_REFERENCIA_FACTURACION_PROVEEDOR AS ULTIMA_REFERENCIA FROM CONFIG_GRAL FOR UPDATE";
                    $resultConfigGral = $bd->ExecSQL($sqlConfigGral);
                    $rowRef           = $bd->SigReg($resultConfigGral);

                    //COMPROBACION POR SI YA EXISTE
                    $referencia_encontrada = false;
                    $numRefAutomatico      = $rowRef->ULTIMA_REFERENCIA;
                    while ($referencia_encontrada == false):
                        $numRefAutomatico = $numRefAutomatico + 1;

                        //LE DOY FORMATO A LA REFERENCIA DE FACTURACION
                        if ($rowOrdenTransporte->MODELO_TRANSPORTE == 'Segundo' && $rowOrdenContratacion->TIPO_ORDEN_TRANSPORTE != "OTC"):
                            $nuevaReferenciaFacturacion = "TT" . date('y', mktime(0, 0, 0, (int)$mesEjecucion, (int)$diaEjecucion, (int)$anioEjecucion)) . str_pad((string)$numRefAutomatico, 6, "0", STR_PAD_LEFT);
                        else:
                            $nuevaReferenciaFacturacion = "T" . date('y', mktime(0, 0, 0, (int)$mesEjecucion, (int)$diaEjecucion, (int)$anioEjecucion)) . str_pad((string)$numRefAutomatico, 7, "0", STR_PAD_LEFT);
                        endif;

                        //BUSCAMOS SI LA REFERENCIA DE FACTURACION YA ESTA USADA
                        $GLOBALS["NotificaErrorPorEmail"] = "No";
                        if ($rowOrdenTransporte->MODELO_TRANSPORTE == 'Segundo' && $rowOrdenContratacion->TIPO_ORDEN_TRANSPORTE != "OTC"):
                            $rowRefFacturacionUsada = $bd->VerReg("PROVEEDOR_REFERENCIA_FACTURACION", "REFERENCIA_FACTURACION_NUEVO_MODELO", $nuevaReferenciaFacturacion, "No");
                        else:
                            $rowRefFacturacionUsada = $bd->VerReg("PROVEEDOR_REFERENCIA_FACTURACION", "REFERENCIA_FACTURACION", $nuevaReferenciaFacturacion, "No");
                        endif;
                        unset($GLOBALS["NotificaErrorPorEmail"]);
                        //SI NO EXISTE
                        if ($rowRefFacturacionUsada == false):
                            $referencia_encontrada = true;
                        endif;
                    endwhile;

                    //ACTUALIZO EL ULTIMO NUMERO UTILIZADO
                    $sqlUpdate = "UPDATE CONFIG_GRAL SET ULTIMA_REFERENCIA_FACTURACION_PROVEEDOR = $numRefAutomatico";
                    $bd->ExecSQL($sqlUpdate);

                    //CALCULO LA FECHA DE INICIO Y FIN DEL PERIODO
                    if ($rowProveedor->PERIODO_FACTURACION == "Mensual"):
                        //CALCULO EL DIA 1 DEL MES DEL PERIODO
                        $fechaInicioPeriodo = date("Y-m-d", (mktime(0, 0, 0, (int)$num_periodo, 1, (int)$anioEjecucion)));
                        //CALCULO EL ULTIMO DIA DE ESE MES
                        $fechaFinPeriodo = date('Y-m-t', strtotime((string)$fechaInicioPeriodo));

                    elseif ($rowProveedor->PERIODO_FACTURACION == "Semanal"):
                        $string_fecha       = $anioEjecucion . 'W' . str_pad((string)$num_periodo, 2, '0', STR_PAD_LEFT);
                        $fechaInicioPeriodo = date('Y-m-d', strtotime((string)$string_fecha . '1'));
                        $fechaFinPeriodo    = date('Y-m-d', strtotime((string)$string_fecha . '7'));

                    elseif ($rowProveedor->PERIODO_FACTURACION == "Quincenal"):
                        //OBTENEMOS EL NUMERO DE DIAS DEL ACTUAL PERIODO
                        $dias_periodo = ($num_periodo - 1) * 15;

                        //AHORA OBTENEMOS EL NUMERO DE DIAS DE LA FECHA DEL HITO
                        $dias_hito = date('z', mktime(0, 0, 0, (int)$mesEjecucion, (int)$diaEjecucion, (int)$anioEjecucion));

                        //A LA FECHA DEL HITO LE RESTAMOS LOS DIAS DE DIFERENCIA ENTRE LA FECHA DEL HITO Y LA DE INICIO DEL PERIODO
                        $fechaInicioPeriodo = date('Y-m-d', strtotime((string)($fecha_contabilizacion != "" ? $fecha_contabilizacion : $rowOrdenContratacion->FECHA_EJECUCION) . "- " . ($dias_hito - $dias_periodo) . " days"));
                        $fechaFinPeriodo    = date('Y-m-d', strtotime((string)$fechaInicioPeriodo . '+ 14 days'));
                    endif;

                    $whereRefFacturacion = " ,REFERENCIA_FACTURACION = '$nuevaReferenciaFacturacion'";
                    if (($rowOrdenTransporte->MODELO_TRANSPORTE == 'Segundo') && ($rowOrdenContratacion->TIPO_ORDEN_TRANSPORTE != "OTC")):
                        $whereRefFacturacion = " ,REFERENCIA_FACTURACION_NUEVO_MODELO = '$nuevaReferenciaFacturacion'";
                    endif;

                    //ACTUALIZAMOS LA TABLA PROVEEDOR
                    $sqlInsert = "INSERT INTO PROVEEDOR_REFERENCIA_FACTURACION SET
                                    ID_PROVEEDOR = $rowOrdenContratacion->ID_PROVEEDOR
                                    ,TIPO_TRANSPORTE = 'OT'
                                    ,ID_SOCIEDAD_CONTRATANTE = $rowCentroContratante->ID_SOCIEDAD
                                    ,ID_PROVEEDOR_CONTRATO = " . ($rowTarifa->ID_PROVEEDOR_CONTRATO != "" ? " $rowTarifa->ID_PROVEEDOR_CONTRATO " : "NULL") . "
                                    ,AÑO = '$anioEjecucion'
                                    ,PERIODO_FACTURACION = '" . (($tipoFacturacion != "") ? $tipoFacturacion : $rowProveedor->PERIODO_FACTURACION) . "'
                                    ,NUMERO_PERIODO = '$num_periodo'
                                    ,FECHA_INICIO_PERIODO = '$fechaInicioPeriodo'
                                    ,FECHA_FIN_PERIODO = '$fechaFinPeriodo'
                                    $whereRefFacturacion";
                    $bd->ExecSQL($sqlInsert);
                else:
                    //OBTENEMOS LA ULTIMA REFERENCIA DE FACTURACION MANUAL DISPONIBLE
                    if (($rowOrdenTransporte->MODELO_TRANSPORTE == 'Segundo') && ($rowOrdenContratacion->TIPO_ORDEN_TRANSPORTE != "OTC")):
                        $sqlReferenciaFacturacion = "SELECT MAX(REFERENCIA_FACTURACION_NUEVO_MODELO) AS REFERENCIA_FACTURACION
                                         FROM PROVEEDOR_REFERENCIA_FACTURACION
                                         WHERE PERIODO_FACTURACION = 'Manual' AND ID_SOCIEDAD_CONTRATANTE = $rowCentroContratante->ID_SOCIEDAD AND ID_PROVEEDOR = $rowOrdenContratacion->ID_PROVEEDOR AND TIPO_TRANSPORTE = 'OT' AND NUMERO_PERIODO = $num_periodo AND AÑO = $anioEjecucion AND ID_PROVEEDOR_CONTRATO " . ($rowTarifa->ID_PROVEEDOR_CONTRATO != "" ? " = $rowTarifa->ID_PROVEEDOR_CONTRATO " : " IS NULL ");
                    else:
                        $sqlReferenciaFacturacion = "SELECT MAX(REFERENCIA_FACTURACION) AS REFERENCIA_FACTURACION
                                         FROM PROVEEDOR_REFERENCIA_FACTURACION
                                         WHERE PERIODO_FACTURACION = 'Manual' AND ID_SOCIEDAD_CONTRATANTE = $rowCentroContratante->ID_SOCIEDAD AND ID_PROVEEDOR = $rowOrdenContratacion->ID_PROVEEDOR AND TIPO_TRANSPORTE = 'OT' AND NUMERO_PERIODO = $num_periodo AND AÑO = $anioEjecucion AND ID_PROVEEDOR_CONTRATO " . ($rowTarifa->ID_PROVEEDOR_CONTRATO != "" ? " = $rowTarifa->ID_PROVEEDOR_CONTRATO " : " IS NULL ");
                    endif;
                    $resultReferenciaFacturacion = $bd->ExecSQL($sqlReferenciaFacturacion);
                    $rowReferenciaFacturacion    = $bd->SigReg($resultReferenciaFacturacion);

                    $nuevaReferenciaFacturacion = $rowReferenciaFacturacion->REFERENCIA_FACTURACION;
                endif;
            endif;

            return $nuevaReferenciaFacturacion;
        else://CALCULAMOS LA NUEVA REFERENCIA DE CONTRATACION Y RELLENAMOS POSIBLES REFERENCIAS INTERMEDIAS

            //SI EL AÑO ACTUAL Y EL DE EJECUCION ES EL MISMO
            if ($anioActual == $anioEjecucion):
                //SI SE INTENTA ACCEDER A UN PERIODO PASADO, LO CREAMOS PASA ESE PERIODO
                if ($num_periodo < $num_periodo_actual):

                    //BUSCO EL NUMERO DE FACTURACION QUE LE CORRESPONDA
                    $sqlConfigGral    = "SELECT ULTIMA_REFERENCIA_FACTURACION_PROVEEDOR AS ULTIMA_REFERENCIA FROM CONFIG_GRAL FOR UPDATE";
                    $resultConfigGral = $bd->ExecSQL($sqlConfigGral);
                    $rowRef           = $bd->SigReg($resultConfigGral);

                    //COMPROBACION POR SI YA EXISTE
                    $referencia_encontrada = false;
                    $numRefAutomatico      = $rowRef->ULTIMA_REFERENCIA;
                    while ($referencia_encontrada == false):
                        $numRefAutomatico = $numRefAutomatico + 1;

                        //LE DOY FORMATO A LA REFERENCIA DE FACTURACION
                        if ($rowOrdenTransporte->MODELO_TRANSPORTE == 'Segundo' && $rowOrdenContratacion->TIPO_ORDEN_TRANSPORTE != "OTC"):
                            $nuevaReferenciaFacturacion = "TT" . date('y', mktime(0, 0, 0, (int)$mesEjecucion, (int)$diaEjecucion, (int)$anioEjecucion)) . str_pad((string)$numRefAutomatico, 6, "0", STR_PAD_LEFT);
                        else:
                            $nuevaReferenciaFacturacion = "T" . date('y', mktime(0, 0, 0, (int)$mesEjecucion, (int)$diaEjecucion, (int)$anioEjecucion)) . str_pad((string)$numRefAutomatico, 7, "0", STR_PAD_LEFT);
                        endif;

                        //BUSCAMOS SI LA REFERENCIA DE FACTURACION YA ESTA USADA
                        $GLOBALS["NotificaErrorPorEmail"] = "No";
                        if ($rowOrdenTransporte->MODELO_TRANSPORTE == 'Segundo' && $rowOrdenContratacion->TIPO_ORDEN_TRANSPORTE != "OTC"):
                            $rowRefFacturacionUsada = $bd->VerReg("PROVEEDOR_REFERENCIA_FACTURACION", "REFERENCIA_FACTURACION_NUEVO_MODELO", $nuevaReferenciaFacturacion, "No");
                        else:
                            $rowRefFacturacionUsada = $bd->VerReg("PROVEEDOR_REFERENCIA_FACTURACION", "REFERENCIA_FACTURACION", $nuevaReferenciaFacturacion, "No");
                        endif;
                        unset($GLOBALS["NotificaErrorPorEmail"]);
                        //SI NO EXISTE
                        if ($rowRefFacturacionUsada == false):
                            $referencia_encontrada = true;
                        endif;
                    endwhile;

                    //ACTUALIZO EL ULTIMO NUMERO UTILIZADO
                    $sqlUpdate = "UPDATE CONFIG_GRAL SET ULTIMA_REFERENCIA_FACTURACION_PROVEEDOR = $numRefAutomatico";
                    $bd->ExecSQL($sqlUpdate);

                    //CALCULO LA FECHA DE INICIO Y FIN DEL PERIODO
                    if ($rowProveedor->PERIODO_FACTURACION == "Mensual"):
                        //CALCULO EL DIA 1 DEL MES DEL PERIODO
                        $fechaInicioPeriodo = date("Y-m-d", (mktime(0, 0, 0, (int)$num_periodo, 1, (int)$anioEjecucion)));
                        //CALCULO EL ULTIMO DIA DE ESE MES
                        $fechaFinPeriodo = date('Y-m-t', strtotime((string)$fechaInicioPeriodo));

                    elseif ($rowProveedor->PERIODO_FACTURACION == "Semanal"):
                        $string_fecha       = $anioEjecucion . 'W' . str_pad((string)$num_periodo, 2, '0', STR_PAD_LEFT);
                        $fechaInicioPeriodo = date('Y-m-d', strtotime((string)$string_fecha . '1'));
                        $fechaFinPeriodo    = date('Y-m-d', strtotime((string)$string_fecha . '7'));

                    elseif ($rowProveedor->PERIODO_FACTURACION == "Quincenal"):
                        //OBTENEMOS EL NUMERO DE DIAS DEL ACTUAL PERIODO
                        $dias_periodo = ($num_periodo - 1) * 15;

                        //AHORA OBTENEMOS EL NUMERO DE DIAS DE LA FECHA DEL HITO
                        $dias_hito = date('z', mktime(0, 0, 0, (int)$mesEjecucion, (int)$diaEjecucion, (int)$anioEjecucion));

                        //A LA FECHA DEL HITO LE RESTAMOS LOS DIAS DE DIFERENCIA ENTRE LA FECHA DEL HITO Y LA DE INICIO DEL PERIODO
                        $fechaInicioPeriodo = date('Y-m-d', strtotime((string)($fecha_contabilizacion != "" ? $fecha_contabilizacion : $rowOrdenContratacion->FECHA_EJECUCION) . "- " . ($dias_hito - $dias_periodo) . " days"));
                        $fechaFinPeriodo    = date('Y-m-d', strtotime((string)$fechaInicioPeriodo . '+ 14 days'));
                    endif;

                    $whereRefFacturacion = " ,REFERENCIA_FACTURACION = '$nuevaReferenciaFacturacion'";
                    if (($rowOrdenTransporte->MODELO_TRANSPORTE == 'Segundo') && ($rowOrdenContratacion->TIPO_ORDEN_TRANSPORTE != "OTC")):
                        $whereRefFacturacion = " ,REFERENCIA_FACTURACION_NUEVO_MODELO = '$nuevaReferenciaFacturacion'";
                    endif;

                    //ACTUALIZAMOS LA TABLA PROVEEDOR
                    $sqlInsert = "INSERT INTO PROVEEDOR_REFERENCIA_FACTURACION SET
                                    ID_PROVEEDOR = $rowOrdenContratacion->ID_PROVEEDOR
                                    ,TIPO_TRANSPORTE = 'OT'
                                    ,ID_SOCIEDAD_CONTRATANTE = $rowCentroContratante->ID_SOCIEDAD
                                    ,ID_PROVEEDOR_CONTRATO = " . ($rowTarifa->ID_PROVEEDOR_CONTRATO != "" ? " $rowTarifa->ID_PROVEEDOR_CONTRATO " : "NULL") . "
                                    ,AÑO = '$anioEjecucion'
                                    ,PERIODO_FACTURACION = '$rowProveedor->PERIODO_FACTURACION'
                                    ,NUMERO_PERIODO = '$num_periodo'
                                    ,FECHA_INICIO_PERIODO = '$fechaInicioPeriodo'
                                    ,FECHA_FIN_PERIODO = '$fechaFinPeriodo'
                                    $whereRefFacturacion";
                    $bd->ExecSQL($sqlInsert);

                else://SI SE INTENTA CREAR PARA UN PERIODO POSTERIOR, BUSCAMOS LOS PERIODOS INTERMEDIOS

                    $numPeriodosIntermedios = $num_periodo - $num_periodo_actual;

                    //BUSCO EL NUMERO DE FACTURACION QUE LE CORRESPONDA
                    $sqlConfigGral    = "SELECT ULTIMA_REFERENCIA_FACTURACION_PROVEEDOR AS ULTIMA_REFERENCIA FROM CONFIG_GRAL FOR UPDATE";
                    $resultConfigGral = $bd->ExecSQL($sqlConfigGral);
                    $rowRef           = $bd->SigReg($resultConfigGral);

                    $numRefAutomatico = $rowRef->ULTIMA_REFERENCIA;

                    //RECORREMOS LOS PERIODOS INTERMEDIOS
                    $i = 0;
                    while ($i <= $numPeriodosIntermedios):

                        $numPeriodoIntermedio = $num_periodo_actual + $i;

                        //BUSCO SI PARA ESE PROVEEDOR, CON ESA PERIODICIDAD, HAY DEFINIDO UNA REFERENCIA PARA ESA FECHA DE EJECUCION
                        if (($rowOrdenTransporte->MODELO_TRANSPORTE == 'Segundo') && ($rowOrdenContratacion->TIPO_ORDEN_TRANSPORTE != "OTC")):
                            $sqlReferenciaFacturacionIntermedio = "SELECT REFERENCIA_FACTURACION_NUEVO_MODELO AS REFERENCIA_FACTURACION
                                                                   FROM PROVEEDOR_REFERENCIA_FACTURACION
                                                                   WHERE ID_SOCIEDAD_CONTRATANTE = $rowCentroContratante->ID_SOCIEDAD AND ID_PROVEEDOR = $rowOrdenContratacion->ID_PROVEEDOR AND TIPO_TRANSPORTE = 'OT' AND PERIODO_FACTURACION = '$rowProveedor->PERIODO_FACTURACION' AND NUMERO_PERIODO = $numPeriodoIntermedio AND AÑO = $anioActual AND ID_PROVEEDOR_CONTRATO " . ($rowTarifa->ID_PROVEEDOR_CONTRATO != "" ? " = $rowTarifa->ID_PROVEEDOR_CONTRATO " : " IS NULL ");
                        else:
                            $sqlReferenciaFacturacionIntermedio = "SELECT REFERENCIA_FACTURACION
                                                                   FROM PROVEEDOR_REFERENCIA_FACTURACION
                                                                   WHERE ID_SOCIEDAD_CONTRATANTE = $rowCentroContratante->ID_SOCIEDAD AND ID_PROVEEDOR = $rowOrdenContratacion->ID_PROVEEDOR AND TIPO_TRANSPORTE = 'OT' AND PERIODO_FACTURACION = '$rowProveedor->PERIODO_FACTURACION' AND NUMERO_PERIODO = $numPeriodoIntermedio AND AÑO = $anioActual AND ID_PROVEEDOR_CONTRATO " . ($rowTarifa->ID_PROVEEDOR_CONTRATO != "" ? " = $rowTarifa->ID_PROVEEDOR_CONTRATO " : " IS NULL ");
                        endif;
                        $resultReferenciaFacturacionIntermedio = $bd->ExecSQL($sqlReferenciaFacturacionIntermedio);

                        //SI NO EXISTE , LO CREAMOS
                        if ($bd->NumRegs($resultReferenciaFacturacionIntermedio) == 0):

                            //COMPROBACION POR SI YA EXISTE
                            $referencia_encontrada = false;
                            while ($referencia_encontrada == false):
                                //AUMENTO EL NUMERO DE REFERENCIA
                                $numRefAutomatico = $numRefAutomatico + 1;

                                //LE DOY FORMATO A LA REFERENCIA DE FACTURACION
                                if ($rowOrdenTransporte->MODELO_TRANSPORTE == 'Segundo' && $rowOrdenContratacion->TIPO_ORDEN_TRANSPORTE != "OTC"):
                                    $nuevaReferenciaFacturacion = "TT" . date('y', mktime(0, 0, 0, (int)$mesEjecucion, (int)$diaEjecucion, (int)$anioEjecucion)) . str_pad((string)$numRefAutomatico, 6, "0", STR_PAD_LEFT);
                                else:
                                    $nuevaReferenciaFacturacion = "T" . date('y', mktime(0, 0, 0, (int)$mesEjecucion, (int)$diaEjecucion, (int)$anioEjecucion)) . str_pad((string)$numRefAutomatico, 7, "0", STR_PAD_LEFT);
                                endif;
                                //BUSCAMOS SI LA REFERENCIA DE FACTURACION YA ESTA USADA
                                $GLOBALS["NotificaErrorPorEmail"] = "No";
                                if ($rowOrdenTransporte->MODELO_TRANSPORTE == 'Segundo' && $rowOrdenContratacion->TIPO_ORDEN_TRANSPORTE != "OTC"):
                                    $rowRefFacturacionUsada = $bd->VerReg("PROVEEDOR_REFERENCIA_FACTURACION", "REFERENCIA_FACTURACION_NUEVO_MODELO", $nuevaReferenciaFacturacion, "No");
                                else:
                                    $rowRefFacturacionUsada = $bd->VerReg("PROVEEDOR_REFERENCIA_FACTURACION", "REFERENCIA_FACTURACION", $nuevaReferenciaFacturacion, "No");
                                endif;
                                unset($GLOBALS["NotificaErrorPorEmail"]);
                                //SI NO EXISTE
                                if ($rowRefFacturacionUsada == false):
                                    $referencia_encontrada = true;
                                endif;
                            endwhile;

                            //CALCULO LA FECHA DE INICIO Y FIN DEL PERIODO
                            if ($rowProveedor->PERIODO_FACTURACION == "Mensual"):

                                //SI ES MENSUAL, Y HAY INDICADO UN DIA DE CIERRE,PONEMOS LA FECHA RESPECTO A ESE PERIODO
                                if ($diaCierreMensual > 0):
                                    //CALCULAMOS LA FECHA INICO UN DIA DESPUES DE LA FECHA DE CIERRE DEL MES PASADO
                                    $fechaInicioPeriodo = date("Y-m-d", (mktime(0, 0, 0, (int)$numPeriodoIntermedio - 1, (int)$diaCierreMensual + 1, (int)$anioActual)));

                                    //CALCULAMOS LA FECHA DE FIN CON EL DIA DE CIERRE
                                    $fechaFinPeriodo = date("Y-m-d", (mktime(0, 0, 0, (int)$numPeriodoIntermedio, (int)$diaCierreMensual, (int)$anioActual)));
                                else:
                                    //CALCULO EL DIA 1 DEL MES DEL PERIODO
                                    $fechaInicioPeriodo = date("Y-m-d", (mktime(0, 0, 0, (int)$numPeriodoIntermedio, 1, (int)$anioActual)));
                                    //CALCULO EL ULTIMO DIA DE ESE MES
                                    $fechaFinPeriodo = date('Y-m-t', strtotime((string)$fechaInicioPeriodo));
                                endif;

                            elseif ($rowProveedor->PERIODO_FACTURACION == "Semanal"):
                                $string_fecha       = $anioActual . 'W' . str_pad((string)$numPeriodoIntermedio, 2, '0', STR_PAD_LEFT);
                                $fechaInicioPeriodo = date('Y-m-d', strtotime((string)$string_fecha . '1'));
                                $fechaFinPeriodo    = date('Y-m-d', strtotime((string)$string_fecha . '7'));

                            elseif ($rowProveedor->PERIODO_FACTURACION == "Quincenal"):
                                //OBTENEMOS EL NUMERO DE DIAS DEL ACTUAL PERIODO
                                $dias_periodo = ($num_periodo - 1) * 15;

                                //AHORA OBTENEMOS EL NUMERO DE DIAS DE LA FECHA DEL HITO
                                $dias_hito = date('z', mktime(0, 0, 0, (int)$mesEjecucion, (int)$diaEjecucion, (int)$anioEjecucion));

                                //A LA FECHA DEL HITO LE RESTAMOS LOS DIAS DE DIFERENCIA ENTRE LA FECHA DEL HITO Y LA DE INICIO DEL PERIODO
                                $fechaInicioPeriodo = date('Y-m-d', strtotime((string)($fecha_contabilizacion != "" ? $fecha_contabilizacion : $rowOrdenContratacion->FECHA_EJECUCION) . "- " . ($dias_hito - $dias_periodo) . " days"));
                                $fechaFinPeriodo    = date('Y-m-d', strtotime((string)$fechaInicioPeriodo . '+ 14 days'));
                            endif;

                            $whereRefFacturacion = " ,REFERENCIA_FACTURACION = '$nuevaReferenciaFacturacion'";
                            if (($rowOrdenTransporte->MODELO_TRANSPORTE == 'Segundo') && ($rowOrdenContratacion->TIPO_ORDEN_TRANSPORTE != "OTC")):
                                $whereRefFacturacion = " ,REFERENCIA_FACTURACION_NUEVO_MODELO = '$nuevaReferenciaFacturacion'";
                            endif;

                            //ACTUALIZAMOS LA TABLA PROVEEDOR
                            $sqlInsert = "INSERT INTO PROVEEDOR_REFERENCIA_FACTURACION SET
                                            ID_PROVEEDOR = $rowOrdenContratacion->ID_PROVEEDOR
                                            ,TIPO_TRANSPORTE = 'OT'
                                            ,ID_SOCIEDAD_CONTRATANTE = $rowCentroContratante->ID_SOCIEDAD
                                            ,ID_PROVEEDOR_CONTRATO = " . ($rowTarifa->ID_PROVEEDOR_CONTRATO != "" ? " $rowTarifa->ID_PROVEEDOR_CONTRATO " : "NULL") . "
                                            ,AÑO = '$anioActual'
                                            ,PERIODO_FACTURACION = '$rowProveedor->PERIODO_FACTURACION'
                                            ,NUMERO_PERIODO = '$numPeriodoIntermedio'
                                            ,FECHA_INICIO_PERIODO = '$fechaInicioPeriodo'
                                            ,FECHA_FIN_PERIODO = '$fechaFinPeriodo'
                                            $whereRefFacturacion";
                            $bd->ExecSQL($sqlInsert);

                        endif;

                        $i = $i + 1;
                    endwhile;

                    //ACTUALIZO EL ULTIMO NUMERO UTILIZADO
                    $sqlUpdate = "UPDATE CONFIG_GRAL SET ULTIMA_REFERENCIA_FACTURACION_PROVEEDOR = $numRefAutomatico";
                    $bd->ExecSQL($sqlUpdate);


                endif;

            else:// SI EL AÑO ES EL SIGUIENTE

                //BUSCO EL NUMERO DE FACTURACION QUE LE CORRESPONDA
                $sqlConfigGral    = "SELECT ULTIMA_REFERENCIA_FACTURACION_PROVEEDOR AS ULTIMA_REFERENCIA FROM CONFIG_GRAL FOR UPDATE";
                $resultConfigGral = $bd->ExecSQL($sqlConfigGral);
                $rowRef           = $bd->SigReg($resultConfigGral);

                $numRefAutomatico = $rowRef->ULTIMA_REFERENCIA;

                //RECORREMOS LOS PERIODOS RESTANTES DE EL AÑO ACTUAL
                $numPeriodosIntermedios = $num_ultimo_periodo_anio - $num_periodo_actual;


                // RECORREMOS LOS PERIODOS INTERMEDIOS DE ESTE AÑO
                $i = 0;
                while ($i <= $numPeriodosIntermedios):

                    $numPeriodoIntermedio = $num_periodo_actual + $i;

                    // BUSCO SI PARA ESE PROVEEDOR, CON ESA PERIODICIDAD, HAY DEFINIDO UNA REFERENCIA PARA ESA FECHA DE EJECUCION
                    if (($rowOrdenTransporte->MODELO_TRANSPORTE == 'Segundo') && ($rowOrdenContratacion->TIPO_ORDEN_TRANSPORTE != "OTC")):
                        $sqlReferenciaFacturacionIntermedio = "SELECT REFERENCIA_FACTURACION_NUEVO_MODELO AS REFERENCIA_FACTURACION
                                                               FROM PROVEEDOR_REFERENCIA_FACTURACION
                                                               WHERE ID_SOCIEDAD_CONTRATANTE = $rowCentroContratante->ID_SOCIEDAD AND ID_PROVEEDOR = $rowOrdenContratacion->ID_PROVEEDOR AND TIPO_TRANSPORTE = 'OT' AND PERIODO_FACTURACION = '$rowProveedor->PERIODO_FACTURACION' AND NUMERO_PERIODO = $numPeriodoIntermedio AND AÑO = $anioActual AND ID_PROVEEDOR_CONTRATO " . ($rowTarifa->ID_PROVEEDOR_CONTRATO != "" ? " = $rowTarifa->ID_PROVEEDOR_CONTRATO " : " IS NULL ");
                    else:
                        $sqlReferenciaFacturacionIntermedio = "SELECT REFERENCIA_FACTURACION
                                                               FROM PROVEEDOR_REFERENCIA_FACTURACION
                                                               WHERE ID_SOCIEDAD_CONTRATANTE = $rowCentroContratante->ID_SOCIEDAD AND ID_PROVEEDOR = $rowOrdenContratacion->ID_PROVEEDOR AND TIPO_TRANSPORTE = 'OT' AND PERIODO_FACTURACION = '$rowProveedor->PERIODO_FACTURACION' AND NUMERO_PERIODO = $numPeriodoIntermedio AND AÑO = $anioActual AND ID_PROVEEDOR_CONTRATO " . ($rowTarifa->ID_PROVEEDOR_CONTRATO != "" ? " = $rowTarifa->ID_PROVEEDOR_CONTRATO " : " IS NULL ");
                    endif;

                    $resultReferenciaFacturacionIntermedio = $bd->ExecSQL($sqlReferenciaFacturacionIntermedio);

                    //SI NO EXISTE , LO CREAMOS
                    if ($bd->NumRegs($resultReferenciaFacturacionIntermedio) == 0):

                        //COMPROBACION POR SI YA EXISTE
                        $referencia_encontrada = false;
                        while ($referencia_encontrada == false):
                            //AUMENTO EL NUMERO DE REFERENCIA
                            $numRefAutomatico = $numRefAutomatico + 1;

                            //LE DOY FORMATO A LA REFERENCIA DE FACTURACION
                            if ($rowOrdenTransporte->MODELO_TRANSPORTE == 'Segundo' && $rowOrdenContratacion->TIPO_ORDEN_TRANSPORTE != "OTC"):
                                $nuevaReferenciaFacturacion = "TT" . date('y', mktime(0, 0, 0, (int)$mesEjecucion, (int)$diaEjecucion, (int)$anioActual)) . str_pad((string)$numRefAutomatico, 6, "0", STR_PAD_LEFT);
                            else:
                                $nuevaReferenciaFacturacion = "T" . date('y', mktime(0, 0, 0, (int)$mesEjecucion, (int)$diaEjecucion, (int)$anioActual)) . str_pad((string)$numRefAutomatico, 7, "0", STR_PAD_LEFT);
                            endif;


                            //BUSCAMOS SI LA REFERENCIA DE FACTURACION YA ESTA USADA
                            $GLOBALS["NotificaErrorPorEmail"] = "No";
                            if ($rowOrdenTransporte->MODELO_TRANSPORTE == 'Segundo' && $rowOrdenContratacion->TIPO_ORDEN_TRANSPORTE != "OTC"):
                                $rowRefFacturacionUsada = $bd->VerReg("PROVEEDOR_REFERENCIA_FACTURACION", "REFERENCIA_FACTURACION_NUEVO_MODELO", $nuevaReferenciaFacturacion, "No");
                            else:
                                $rowRefFacturacionUsada = $bd->VerReg("PROVEEDOR_REFERENCIA_FACTURACION", "REFERENCIA_FACTURACION", $nuevaReferenciaFacturacion, "No");
                            endif;
                            unset($GLOBALS["NotificaErrorPorEmail"]);
                            //SI NO EXISTE
                            if ($rowRefFacturacionUsada == false):
                                $referencia_encontrada = true;
                            endif;
                        endwhile;

                        //CALCULO LA FECHA DE INICIO Y FIN DEL PERIODO
                        if ($rowProveedor->PERIODO_FACTURACION == "Mensual"):

                            //SI ES MENSUAL, Y HAY INDICADO UN DIA DE CIERRE,PONEMOS LA FECHA RESPECTO A ESE PERIODO
                            if ($diaCierreMensual > 0):
                                //CALCULAMOS LA FECHA INICO UN DIA DESPUES DE LA FECHA DE CIERRE DEL MES PASADO
                                $fechaInicioPeriodo = date("Y-m-d", (mktime(0, 0, 0, (int)$numPeriodoIntermedio - 1, (int)$diaCierreMensual + 1, (int)$anioActual)));

                                //CALCULAMOS LA FECHA DE FIN CON EL DIA DE CIERRE
                                $fechaFinPeriodo = date("Y-m-d", (mktime(0, 0, 0, (int)$numPeriodoIntermedio, (int)$diaCierreMensual, (int)$anioActual)));
                            else:
                                //CALCULO EL DIA 1 DEL MES DEL PERIODO
                                $fechaInicioPeriodo = date("Y-m-d", (mktime(0, 0, 0, (int)$numPeriodoIntermedio, 1, (int)$anioActual)));
                                //CALCULO EL ULTIMO DIA DE ESE MES
                                $fechaFinPeriodo = date('Y-m-t', strtotime((string)$fechaInicioPeriodo));
                            endif;

                        elseif ($rowProveedor->PERIODO_FACTURACION == "Semanal"):

                            $string_fecha       = $anioActual . 'W' . str_pad((string)$numPeriodoIntermedio, 2, '0', STR_PAD_LEFT);
                            $fechaInicioPeriodo = date('Y-m-d', strtotime((string)$string_fecha . '1'));
                            $fechaFinPeriodo    = date('Y-m-d', strtotime((string)$string_fecha . '7'));

                        elseif ($rowProveedor->PERIODO_FACTURACION == "Quincenal"):

                            //OBTENEMOS EL NUMERO DE DIAS DEL ACTUAL PERIODO
                            $dias_periodo = ($num_periodo - 1) * 15;

                            //AHORA OBTENEMOS EL NUMERO DE DIAS DE LA FECHA DEL HITO
                            $dias_hito = date('z', mktime(0, 0, 0, (int)$mesEjecucion, (int)$diaEjecucion, (int)$anioEjecucion));

                            //A LA FECHA DEL HITO LE RESTAMOS LOS DIAS DE DIFERENCIA ENTRE LA FECHA DEL HITO Y LA DE INICIO DEL PERIODO
                            $fechaInicioPeriodo = date('Y-m-d', strtotime((string)($fecha_contabilizacion != "" ? $fecha_contabilizacion : $rowOrdenContratacion['FECHA_EJECUCION']) . "- " . ($dias_hito - $dias_periodo) . " days"));
                            $fechaFinPeriodo    = date('Y-m-d', strtotime((string)$fechaInicioPeriodo . '+ 14 days'));

                        endif;

                        $whereRefFacturacion = " ,REFERENCIA_FACTURACION = '$nuevaReferenciaFacturacion'";
                        if (($rowOrdenTransporte->MODELO_TRANSPORTE == 'Segundo') && ($rowOrdenContratacion->TIPO_ORDEN_TRANSPORTE != "OTC")):
                            $whereRefFacturacion = " ,REFERENCIA_FACTURACION_NUEVO_MODELO = '$nuevaReferenciaFacturacion'";
                        endif;

                        //ACTUALIZAMOS LA TABLA PROVEEDOR
                        $sqlInsert = "INSERT INTO PROVEEDOR_REFERENCIA_FACTURACION SET
                                            ID_PROVEEDOR = $rowOrdenContratacion->ID_PROVEEDOR
                                            ,TIPO_TRANSPORTE = 'OT'
                                            ,ID_SOCIEDAD_CONTRATANTE = $rowCentroContratante->ID_SOCIEDAD
                                            ,ID_PROVEEDOR_CONTRATO = " . ($rowTarifa->ID_PROVEEDOR_CONTRATO != "" ? " $rowTarifa->ID_PROVEEDOR_CONTRATO " : "NULL") . "
                                            ,AÑO = '$anioActual'
                                            ,PERIODO_FACTURACION = '$rowProveedor->PERIODO_FACTURACION'
                                            ,NUMERO_PERIODO = '$numPeriodoIntermedio'
                                            ,FECHA_INICIO_PERIODO = '$fechaInicioPeriodo'
                                            ,FECHA_FIN_PERIODO = '$fechaFinPeriodo'
                                            $whereRefFacturacion";
                        $bd->ExecSQL($sqlInsert);
                    endif;

                    $i = $i + 1;
                endwhile;
                // FIN RECORREMOS LOS PERIODOS INTERMEDIOS DE ESTE AÑO

                //ACTUALIZO EL ULTIMO NUMERO  UTILIZADO
                $sqlUpdate = "UPDATE CONFIG_GRAL SET ULTIMA_REFERENCIA_FACTURACION_PROVEEDOR = $numRefAutomatico";
                $bd->ExecSQL($sqlUpdate);

                //BUSCO EL NUMERO DE FACTURACION QUE LE CORRESPONDA AL AÑO SIGIENTE
                $sqlConfigGral    = "SELECT ULTIMA_REFERENCIA_FACTURACION_PROVEEDOR_AÑO_SIGUIENTE AS ULTIMA_REFERENCIA FROM CONFIG_GRAL FOR UPDATE";
                $resultConfigGral = $bd->ExecSQL($sqlConfigGral);
                $rowRef           = $bd->SigReg($resultConfigGral);

                $numRefAutomatico = $rowRef->ULTIMA_REFERENCIA;

                //RECORREMOS LOS PERIODOS DEL AÑO SIGUIENTE
                $j = 1;

                while ($j <= $num_periodo):

                    $numPeriodoIntermedio = $j;

                    //BUSCO SI PARA ESE PROVEEDOR, CON ESA PERIODICIDAD, HAY DEFINIDO UNA REFERENCIA PARA ESA FECHA DE EJECUCION
                    if (($rowOrdenTransporte->MODELO_TRANSPORTE == 'Segundo') && ($rowOrdenContratacion->TIPO_ORDEN_TRANSPORTE != "OTC")):
                        $sqlReferenciaFacturacionIntermedio = "SELECT REFERENCIA_FACTURACION_NUEVO_MODELO AS REFERENCIA_FACTURACION
                                                               FROM PROVEEDOR_REFERENCIA_FACTURACION
                                                               WHERE ID_SOCIEDAD_CONTRATANTE = $rowCentroContratante->ID_SOCIEDAD AND ID_PROVEEDOR = $rowOrdenContratacion->ID_PROVEEDOR AND TIPO_TRANSPORTE = 'OT' AND PERIODO_FACTURACION = '$rowProveedor->PERIODO_FACTURACION' AND NUMERO_PERIODO = $numPeriodoIntermedio AND AÑO = $anioEjecucion AND ID_PROVEEDOR_CONTRATO " . ($rowTarifa->ID_PROVEEDOR_CONTRATO != "" ? " = $rowTarifa->ID_PROVEEDOR_CONTRATO " : " IS NULL ");
                    else:
                        $sqlReferenciaFacturacionIntermedio = "SELECT REFERENCIA_FACTURACION
                                                               FROM PROVEEDOR_REFERENCIA_FACTURACION
                                                               WHERE ID_SOCIEDAD_CONTRATANTE = $rowCentroContratante->ID_SOCIEDAD AND ID_PROVEEDOR = $rowOrdenContratacion->ID_PROVEEDOR AND TIPO_TRANSPORTE = 'OT' AND PERIODO_FACTURACION = '$rowProveedor->PERIODO_FACTURACION' AND NUMERO_PERIODO = $numPeriodoIntermedio AND AÑO = $anioEjecucion AND ID_PROVEEDOR_CONTRATO " . ($rowTarifa->ID_PROVEEDOR_CONTRATO != "" ? " = $rowTarifa->ID_PROVEEDOR_CONTRATO " : " IS NULL ");
                    endif;
                    $resultReferenciaFacturacionIntermedio = $bd->ExecSQL($sqlReferenciaFacturacionIntermedio);

                    //SI NO EXISTE , LO CREAMOS
                    if ($bd->NumRegs($resultReferenciaFacturacionIntermedio) == 0):

                        //COMPROBACION POR SI YA EXISTE
                        $referencia_encontrada = false;
                        while ($referencia_encontrada == false):
                            //AUMENTO EL NUMERO DE REFERENCIA
                            $numRefAutomatico = $numRefAutomatico + 1;

                            //LE DOY FORMATO A LA REFERENCIA DE FACTURACION
                            if ($rowOrdenTransporte->MODELO_TRANSPORTE == 'Segundo' && $rowOrdenContratacion->TIPO_ORDEN_TRANSPORTE != "OTC"):
                                $nuevaReferenciaFacturacion = "TT" . date('y', mktime(0, 0, 0, (int)$mesEjecucion, (int)$diaEjecucion, (int)$anioEjecucion)) . str_pad((string)$numRefAutomatico, 6, "0", STR_PAD_LEFT);
                            else:
                                $nuevaReferenciaFacturacion = "T" . date('y', mktime(0, 0, 0, (int)$mesEjecucion, (int)$diaEjecucion, (int)$anioEjecucion)) . str_pad((string)$numRefAutomatico, 7, "0", STR_PAD_LEFT);
                            endif;


                            //BUSCAMOS SI LA REFERENCIA DE FACTURACION YA ESTA USADA
                            $GLOBALS["NotificaErrorPorEmail"] = "No";
                            if ($rowOrdenTransporte->MODELO_TRANSPORTE == 'Segundo' && $rowOrdenContratacion->TIPO_ORDEN_TRANSPORTE != "OTC"):
                                $rowRefFacturacionUsada = $bd->VerReg("PROVEEDOR_REFERENCIA_FACTURACION", "REFERENCIA_FACTURACION_NUEVO_MODELO", $nuevaReferenciaFacturacion, "No");
                            else:
                                $rowRefFacturacionUsada = $bd->VerReg("PROVEEDOR_REFERENCIA_FACTURACION", "REFERENCIA_FACTURACION", $nuevaReferenciaFacturacion, "No");
                            endif;
                            unset($GLOBALS["NotificaErrorPorEmail"]);
                            //SI NO EXISTE
                            if ($rowRefFacturacionUsada == false):
                                $referencia_encontrada = true;
                            endif;
                        endwhile;

                        //CALCULO LA FECHA DE INICIO Y FIN DEL PERIODO
                        if ($rowProveedor->PERIODO_FACTURACION == "Mensual"):

                            //SI ES MENSUAL, Y HAY INDICADO UN DIA DE CIERRE,PONEMOS LA FECHA RESPECTO A ESE PERIODO
                            if ($diaCierreMensual > 0):
                                //CALCULAMOS LA FECHA INICO UN DIA DESPUES DE LA FECHA DE CIERRE DEL MES PASADO
                                $fechaInicioPeriodo = date("Y-m-d", (mktime(0, 0, 0, (int)$numPeriodoIntermedio - 1, (int)$diaCierreMensual + 1, (int)$anioEjecucion)));

                                //CALCULAMOS LA FECHA DE FIN CON EL DIA DE CIERRE
                                $fechaFinPeriodo = date("Y-m-d", (mktime(0, 0, 0, (int)$numPeriodoIntermedio, (int)$diaCierreMensual, (int)$anioEjecucion)));
                            else:

                                //CALCULO EL DIA 1 DEL MES DEL PERIODO
                                $fechaInicioPeriodo = date("Y-m-d", (mktime(0, 0, 0, (int)$numPeriodoIntermedio, 1, (int)$anioEjecucion)));
                                //CALCULO EL ULTIMO DIA DE ESE MES
                                $fechaFinPeriodo = date('Y-m-t', strtotime((string)$fechaInicioPeriodo));
                            endif;

                        elseif ($rowProveedor->PERIODO_FACTURACION == "Semanal"):
                            $string_fecha       = $anioEjecucion . 'W' . str_pad((string)$numPeriodoIntermedio, 2, '0', STR_PAD_LEFT);
                            $fechaInicioPeriodo = date('Y-m-d', strtotime((string)$string_fecha . '1'));
                            $fechaFinPeriodo    = date('Y-m-d', strtotime((string)$string_fecha . '7'));

                        elseif ($rowProveedor->PERIODO_FACTURACION == "Quincenal"):
                            //OBTENEMOS EL NUMERO DE DIAS DEL ACTUAL PERIODO
                            $dias_periodo = ($num_periodo - 1) * 15;

                            //AHORA OBTENEMOS EL NUMERO DE DIAS DE LA FECHA DEL HITO
                            $dias_hito = date('z', mktime(0, 0, 0, (int)$mesEjecucion, (int)$diaEjecucion, (int)$anioEjecucion));

                            //A LA FECHA DEL HITO LE RESTAMOS LOS DIAS DE DIFERENCIA ENTRE LA FECHA DEL HITO Y LA DE INICIO DEL PERIODO
                            $fechaInicioPeriodo = date('Y-m-d', strtotime((string)($fecha_contabilizacion != "" ? $fecha_contabilizacion : $rowOrdenContratacion['FECHA_EJECUCION']) . "- " . ($dias_hito - $dias_periodo) . " days"));
                            $fechaFinPeriodo    = date('Y-m-d', strtotime((string)$fechaInicioPeriodo . '+ 14 days'));
                        endif;

                        $whereRefFacturacion = " ,REFERENCIA_FACTURACION = '$nuevaReferenciaFacturacion'";
                        if (($rowOrdenTransporte->MODELO_TRANSPORTE == 'Segundo') && ($rowOrdenContratacion->TIPO_ORDEN_TRANSPORTE != "OTC")):
                            $whereRefFacturacion = " ,REFERENCIA_FACTURACION_NUEVO_MODELO = '$nuevaReferenciaFacturacion'";
                        endif;

                        //ACTUALIZAMOS LA TABLA PROVEEDOR
                        $sqlInsert = "INSERT INTO PROVEEDOR_REFERENCIA_FACTURACION SET
                                            ID_PROVEEDOR = $rowOrdenContratacion->ID_PROVEEDOR
                                            ,TIPO_TRANSPORTE = 'OT'
                                            ,ID_SOCIEDAD_CONTRATANTE = $rowCentroContratante->ID_SOCIEDAD
                                            ,ID_PROVEEDOR_CONTRATO = " . ($rowTarifa->ID_PROVEEDOR_CONTRATO != "" ? " $rowTarifa->ID_PROVEEDOR_CONTRATO " : "NULL") . "
                                            ,AÑO = '$anioEjecucion'
                                            ,PERIODO_FACTURACION = '$rowProveedor->PERIODO_FACTURACION'
                                            ,NUMERO_PERIODO = '$numPeriodoIntermedio'
                                            ,FECHA_INICIO_PERIODO = '$fechaInicioPeriodo'
                                            ,FECHA_FIN_PERIODO = '$fechaFinPeriodo'
                                            $whereRefFacturacion";
                        $bd->ExecSQL($sqlInsert);
                    endif;

                    $j = $j + 1;
                endwhile;
                //FIN RECORREMOS LOS PERIODOS DEL AÑO SIGUIENTE

                //ACTUALIZO EL ULTIMO NUMERO UTILIZADO
                $sqlUpdate = "UPDATE CONFIG_GRAL SET ULTIMA_REFERENCIA_FACTURACION_PROVEEDOR_AÑO_SIGUIENTE = $numRefAutomatico";
                $bd->ExecSQL($sqlUpdate);


            endif;

            //BUSCO LA REFERENCIA PARA EL PERIODO DE LA CONTRATACION
            if (($rowOrdenTransporte->MODELO_TRANSPORTE == 'Segundo') && ($rowOrdenContratacion->TIPO_ORDEN_TRANSPORTE != "OTC")):
                $sqlReferenciaFacturacion = "SELECT REFERENCIA_FACTURACION_NUEVO_MODELO AS REFERENCIA_FACTURACION
                                             FROM PROVEEDOR_REFERENCIA_FACTURACION
                                             WHERE ID_SOCIEDAD_CONTRATANTE = $rowCentroContratante->ID_SOCIEDAD AND ID_PROVEEDOR = $rowOrdenContratacion->ID_PROVEEDOR AND TIPO_TRANSPORTE = 'OT' AND PERIODO_FACTURACION = '$rowProveedor->PERIODO_FACTURACION' AND NUMERO_PERIODO = $num_periodo AND AÑO = $anioEjecucion AND ID_PROVEEDOR_CONTRATO " . ($rowTarifa->ID_PROVEEDOR_CONTRATO != "" ? " = $rowTarifa->ID_PROVEEDOR_CONTRATO " : " IS NULL ");
            else:
                $sqlReferenciaFacturacion = "SELECT REFERENCIA_FACTURACION
                                             FROM PROVEEDOR_REFERENCIA_FACTURACION
                                             WHERE ID_SOCIEDAD_CONTRATANTE = $rowCentroContratante->ID_SOCIEDAD AND ID_PROVEEDOR = $rowOrdenContratacion->ID_PROVEEDOR AND TIPO_TRANSPORTE = 'OT' AND PERIODO_FACTURACION = '$rowProveedor->PERIODO_FACTURACION' AND NUMERO_PERIODO = $num_periodo AND AÑO = $anioEjecucion AND ID_PROVEEDOR_CONTRATO " . ($rowTarifa->ID_PROVEEDOR_CONTRATO != "" ? " = $rowTarifa->ID_PROVEEDOR_CONTRATO " : " IS NULL ");
            endif;
            $resultReferenciaFacturacion = $bd->ExecSQL($sqlReferenciaFacturacion);

            $rowReferenciaFacturacion = $bd->SigReg($resultReferenciaFacturacion);

            return $rowReferenciaFacturacion->REFERENCIA_FACTURACION;

        endif;//FIN EXISTE/NO EXISTE REFERENCIA FACTURACION
    }

    /** Migrada a CI obtener_referencia_facturacion_construccion
     * @param $idOrdenContratacion ORDEN DE CONTRATACION SOBRE LA QUE CALCULAR LA REFERENCIA FACTURACION
     * FUNCION UTILIZADA PARA CALCULAR Y DEVOLVER LA REFENCIA FACUTRACION DE UN PROVEEDOR
     */
    function ObtenerReferenciaFacturacionConstruccion($idOrdenContratacion)
    {

        global $bd;
        global $html;
        global $auxiliar;
        global $administrador;

        //BUSCO LA ORDEN DE CONTRATACION
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowOrdenContratacion             = $bd->VerReg("ORDEN_CONTRATACION", "ID_ORDEN_CONTRATACION", $idOrdenContratacion, "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);

        //ARRAY RESPUESTA
        $arr_respuesta                           = array();
        $arr_respuesta['REFERENCIA_FACTURACION'] = '';
        $arr_respuesta['FECHA_CONTABILIZACION']  = '0000-00-00';

        //COMPRUEBO QUE EXISTA LA ORDEN DE TRANSPORTE
        if ($rowOrdenContratacion == false):
            return $arr_respuesta;
        endif;
        //$html->PagErrorCondicionado($rowOrdenContratacion, "==", false, "OrdenContratacionNoEncontrada");

        //COMPRUEBO QUE TENGA ASIGNADO PROVEEDOR
        if ($rowOrdenContratacion->ID_PROVEEDOR == NULL):
            return $arr_respuesta;
        endif;
        //$html->PagErrorCondicionado($rowOrdenContratacion->ID_PROVEEDOR, "==", false, "FaltaProveedor");

        //SI LA FECHA DE CONTABILIZACION ES MANUAL, TOMAMOS ESA FECHA COMO LA DE EJECUCION
        if ($rowOrdenContratacion->CONTABILIZACION_MANUAL == 1):
            $rowOrdenContratacion->FECHA_EJECUCION = $rowOrdenContratacion->FECHA_CONTABILIZACION;
        endif;

        //COMPRUEBO QUE TENGA ASIGNADA FECHA EJECUCION
        if ($rowOrdenContratacion->FECHA_EJECUCION == '0000-00-00'):
            return $arr_respuesta;
        endif;
        //$html->PagErrorCondicionado($rowOrdenContratacion->FECHA_EJECUCION, "==", false, "FaltanDestinos");

        //BUSCO EL PROVEEDOR
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowProveedor                     = $bd->VerReg("PROVEEDOR", "ID_PROVEEDOR", $rowOrdenContratacion->ID_PROVEEDOR);
        unset($GLOBALS["NotificaErrorPorEmail"]);

        //BUSCO LA ORDEN DE TRANSPORTE SI LA CONTIENE
        if ($rowOrdenContratacion->ID_ORDEN_TRANSPORTE != NULL):
            $rowOrdenTransporte = $this->getOrdenTransporteConstruccion($rowOrdenContratacion->ID_ORDEN_TRANSPORTE);
        endif;

        //BUSCO LA SOCIEDAD CONTRATANTE
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowSociedadContratante           = $bd->VerReg("SOCIEDAD", "ID_SOCIEDAD", ($rowOrdenContratacion->ID_ORDEN_TRANSPORTE != NULL ? $rowOrdenTransporte->ID_SOCIEDAD_CONTRATANTE : $rowOrdenContratacion->ID_SOCIEDAD_CONTRATANTE));
        unset($GLOBALS["NotificaErrorPorEmail"]);

        //SI ESTA INDICADO EN EL PROVEEDOR O SOCIEDAD QUE EL PERIODO MENSUAL ACABE ANTES
        $diaCierreMensual = 0;
        if (($rowProveedor->PERIODO_FACTURACION == "Mensual") && ($rowProveedor->DIA_CIERRE_PERIODO_FACTURACION > 0 || $rowSociedadContratante->DIA_CIERRE_PERIODO_FACTURACION > 0)):
            //SI EL DIA DE CIERRE ESTA INDICADO EN EL PROVEEDOR; PRIMA RESPECTO AL DE LA SOCIEDAD
            if ($rowProveedor->DIA_CIERRE_PERIODO_FACTURACION > 0):
                $diaCierreMensual = $rowProveedor->DIA_CIERRE_PERIODO_FACTURACION;
            else:
                $diaCierreMensual = $rowSociedadContratante->DIA_CIERRE_PERIODO_FACTURACION;
            endif;
        endif;

        //CALCULAMOS EL AÑO Y EL PERIODO ACTUAL
        $anioActual = date('Y');
        if ($rowProveedor->PERIODO_FACTURACION == "Mensual"):
            $num_periodo_actual      = date('m', mktime(0, 0, 0, date('m'), date('d'), date('Y')));
            $num_ultimo_periodo_anio = 12; //EL ULTIMO PERIODO MENUAL ES EL 12

            //SI ES MENSUAL, Y HAY INDICADO UN DIA DE CIERRE, Y EL DIA DE EJECUCION ES MAYOR QUE ESE DIA, AVANZAMOS EL PERIODO
            if ($diaCierreMensual > 0):
                if (date('d') > $diaCierreMensual):
                    $num_periodo_actual = $num_periodo_actual + 1;

                    //SI HEMOS AVANZADO DE AÑO, LO CONTROLAMOS
                    if ($num_periodo_actual == 13):
                        $num_periodo_actual = 1;
                        $anioActual         = $anioActual + 1;
                    endif;
                endif;
            endif;

        elseif ($rowProveedor->PERIODO_FACTURACION == "Semanal"):
            $num_periodo_actual      = date('W', mktime(0, 0, 0, date('m'), date('d'), date('Y')));
            $num_ultimo_periodo_anio = date('W', mktime(0, 0, 0, 12, 31, (int)$anioActual)); //CALCULAMOS LA ULTIMA SEMANA DEL AÑO

            //SI EL MES ES ENERO, LA PRIMERA SEMANA PUEDE QUE SE TOME COMO LA SEMANA 52 o 53 DEL ANTERIOR
            if (date('m') == 1 && ($num_periodo_actual == 52 || $num_periodo_actual == 53)):
                $anioActual = $anioActual - 1;
            endif;

        elseif ($rowProveedor->PERIODO_FACTURACION == "Quincenal"):
            //OBTENEMOS EL PERIODO ACTUAL
            $num_periodo_actual = date('z', mktime(0, 0, 0, date('m'), date('d'), date('Y')));
            $num_periodo_actual = intdiv($num_periodo_actual, 15) + 1;

            $num_ultimo_periodo_anio = date('z', mktime(0, 0, 0, 12, 31, (int)$anioActual)); //CALCULAMOS LA ULTIMA QUINCENA DEL AÑO
            $num_ultimo_periodo_anio = intdiv($num_ultimo_periodo_anio, 15) + 1;

            //SI EL MES ES ENERO, LA PRIMERA SEMANA PUEDE QUE SE TOME COMO LA QUINCENA 25 o 26 DEL ANTERIOR
            if ($num_periodo_actual == 1 && ($num_ultimo_periodo_anio == 25 || $num_ultimo_periodo_anio == 26)):
                $anioActual = $anioActual - 1;
            endif;

        endif;

        //CALCULO EL NUMERO DE PERIODO SEGUN EL TIPO DE PERIODO (1-12 para Mensual, 1-56 para Anual)
        $diaEjecucion  = substr(($rowOrdenContratacion->FECHA_CONTABILIZACION != '0000-00-00' ? $rowOrdenContratacion->FECHA_CONTABILIZACION : $rowOrdenContratacion->FECHA_EJECUCION), 8, 2);
        $mesEjecucion  = substr(($rowOrdenContratacion->FECHA_CONTABILIZACION != '0000-00-00' ? $rowOrdenContratacion->FECHA_CONTABILIZACION : $rowOrdenContratacion->FECHA_EJECUCION), 5, 2);
        $anioEjecucion = substr(($rowOrdenContratacion->FECHA_CONTABILIZACION != '0000-00-00' ? $rowOrdenContratacion->FECHA_CONTABILIZACION : $rowOrdenContratacion->FECHA_EJECUCION), 0, 4);
        if ($rowProveedor->PERIODO_FACTURACION == "Mensual"):
            $num_periodo = date('m', mktime(0, 0, 0, (int)$mesEjecucion, (int)$diaEjecucion, (int)$anioEjecucion));
        elseif ($rowProveedor->PERIODO_FACTURACION == "Semanal"):
            $num_periodo = date('W', mktime(0, 0, 0, (int)$mesEjecucion, (int)$diaEjecucion, (int)$anioEjecucion));

            //SI EL MES ES ENERO, LA PRIMERA SEMANA PUEDE QUE SE TOME COMO LA SEMANA 52,53 DEL ANTERIOR
            if ($mesEjecucion == 1 && ($num_periodo == 52 || $num_periodo == 53)):
                $anioEjecucion = $anioEjecucion - 1;
            endif;
        elseif ($rowProveedor->PERIODO_FACTURACION == "Quincenal"):
            $num_periodo = date('z', mktime(0, 0, 0, (int)$mesEjecucion, (int)$diaEjecucion, (int)$anioEjecucion));
            $num_periodo = intdiv($num_periodo, 15) + 1;

            //SI EL MES ES ENERO, LA PRIMERA SEMANA PUEDE QUE SE TOME COMO LA QUINCENA 25,26 DEL ANTERIOR
            if ($num_periodo == 1 && ($num_periodo == 25 || $num_periodo == 26)):
                $anioEjecucion = $anioEjecucion - 1;
            endif;
        else:
            $html->PagError("ProveedorSinPeriodoDefinido");
        endif;

        //SI ES MENSUAL, Y HAY INDICADO UN DIA DE CIERRE, Y EL DIA DE EJECUCION ES MAYOR QUE ESE DIA, AVANZAMOS EL PERIODO
        if ($diaCierreMensual > 0):
            if ($diaEjecucion > $diaCierreMensual):
                $num_periodo = $num_periodo + 1;

                //SI HEMOS AVANZADO DE AÑO, LO CONTROLAMOS
                if ($num_periodo == 13):
                    $num_periodo   = 1;
                    $anioEjecucion = $anioEjecucion + 1;
                endif;
            endif;
        endif;

        //GUARDAMOS EL PERIODO DE EJECUCION
        $num_periodo_ejecucion = $num_periodo;
        $anio_ejecucion        = $anioEjecucion;

        //SI EL PERIODO DE EJECUCION YA HA PASADO (EL NUMERO DE PERIODO DE EJECUCION DE ESTE AÑO ES MENOR QUE EL PERIODO ACTUAL O EL AÑO DE EJECUCION ES MENOR QUE EL ACTUAL), TOMAREMOS COMO PERIODO EL ACTUAL
        //SE COGERA EL PRIMER PERIODO CUYA CERTIFICACION NO SE ESTE TRATANDO
        /*if ((($num_periodo < $num_periodo_actual) && ($anioEjecucion == $anioActual)) ||
            ($anioEjecucion < $anioActual)
        ):
            $diaEjecucion  = date('d');
            $mesEjecucion  = date('m');
            $anioEjecucion = $anioActual;
            $num_periodo   = $num_periodo_actual;
        endif;*/

        //BUSCAMOS EL PERIODO PARA ASIGNAR, SIENDO EL PRIMERO CON UNA AUTOFACTURA SIN TRATAR
        //SOLO DEJAMOS AÑO DE EJECUCION PARA ESTE AÑO, EL SIGUIENTE Y EL SIGUIENTE
        if (/*$anioEjecucion < date('Y') || */ $anioEjecucion > (date('Y') + 2)):
            $html->PagError("AñoEjecucionFueraDeRango");
        endif;

        //SI NO VIENE PREFIJADA LA FECHA CONTABILIZACION, BUSCAMOS EL PRIMER PERIODO DE FACTURACION QUE NO ESTE FACTURADO
        if ($rowOrdenContratacion->CONTABILIZACION_MANUAL == 0):
            $periodo_valido = false;
            while ($periodo_valido == false):
                //BUSCO SI PARA ESE PROVEEDOR, CON ESA PERIODICIDAD Y ESA SOCIEDAD CONTRATANTE, HAY DEFINIDO UNA REFERENCIA PARA ESA FECHA DE EJECUCION
                $sqlReferenciaFacturacion    = "SELECT REFERENCIA_FACTURACION, FECHA_INICIO_PERIODO
                                     FROM PROVEEDOR_REFERENCIA_FACTURACION
                                     WHERE ID_SOCIEDAD_CONTRATANTE = $rowSociedadContratante->ID_SOCIEDAD AND ID_PROVEEDOR = $rowOrdenContratacion->ID_PROVEEDOR AND TIPO_TRANSPORTE = 'OTC' AND PERIODO_FACTURACION = '$rowProveedor->PERIODO_FACTURACION' AND NUMERO_PERIODO = $num_periodo AND AÑO = $anioEjecucion AND ID_PROVEEDOR_CONTRATO IS NULL";
                $resultReferenciaFacturacion = $bd->ExecSQL($sqlReferenciaFacturacion);

                //SI YA EXISTE UNA REFERENCIA FACTURACION, USAMOS ESA
                if ($rowReferenciaFacturacion = $bd->SigReg($resultReferenciaFacturacion)):

                    //BUSCAMOS SI LA AUTOFACUTURA EXISTE Y SE ESTA TRATANDO
                    $GLOBALS["NotificaErrorPorEmail"] = "No";
                    $rowAutofactura                   = $bd->VerRegRest("AUTOFACTURA", "REFERENCIA_FACTURACION = '" . $rowReferenciaFacturacion->REFERENCIA_FACTURACION . "' AND BAJA = 0", "No");
                    unset($GLOBALS["NotificaErrorPorEmail"]);
                    if (($rowAutofactura == false) || ($rowAutofactura != false && $rowAutofactura->ESTADO_CERTIFICACION == 'Pdte. Tratar')):
                        $periodo_valido = true;
                    else://AVANZAMOS DE PERIODO
                        //SI ES EL ULTIMO PERIODO DEL AÑO, AVANZAMOS DEL AÑO SI ES POSIBLE
                        if ($num_periodo == $num_ultimo_periodo_anio):
                            if ($anioEjecucion <= ($anioActual + 1)):
                                $anioEjecucion = $anioEjecucion + 1;
                                $num_periodo   = 1;
                            else:
                                $html->PagError("AñoEjecucionFueraDeRango");
                            endif;
                        else:
                            $num_periodo = $num_periodo + 1;
                        endif;
                    endif;

                else://SI NO EXISTE LA REFERENCIA, NO TIENE UNA AUTOFACTURA
                    $periodo_valido = true;
                endif;
            endwhile;
        endif;

        //BUSCO SI PARA ESE PROVEEDOR, CON ESA PERIODICIDAD Y ESA SOCIEDAD CONTRATANTE, HAY DEFINIDO UNA REFERENCIA PARA ESA FECHA DE EJECUCION
        $sqlReferenciaFacturacion    = "SELECT REFERENCIA_FACTURACION, FECHA_INICIO_PERIODO
                                     FROM PROVEEDOR_REFERENCIA_FACTURACION
                                     WHERE ID_SOCIEDAD_CONTRATANTE = $rowSociedadContratante->ID_SOCIEDAD AND ID_PROVEEDOR = $rowOrdenContratacion->ID_PROVEEDOR AND TIPO_TRANSPORTE = 'OTC' AND PERIODO_FACTURACION = '$rowProveedor->PERIODO_FACTURACION' AND NUMERO_PERIODO = $num_periodo AND AÑO = $anioEjecucion AND ID_PROVEEDOR_CONTRATO IS NULL";
        $resultReferenciaFacturacion = $bd->ExecSQL($sqlReferenciaFacturacion);

        //SI YA EXISTE UNA REFERENCIA FACTURACION, USAMOS ESA
        if ($rowReferenciaFacturacion = $bd->SigReg($resultReferenciaFacturacion)):
            $arr_respuesta['REFERENCIA_FACTURACION'] = $rowReferenciaFacturacion->REFERENCIA_FACTURACION;

            //EN LA CONTABILIZACION MANUAL, SE MANTIENE
            if ($rowOrdenContratacion->CONTABILIZACION_MANUAL == 1):
                $arr_respuesta['FECHA_CONTABILIZACION'] = $rowOrdenContratacion->FECHA_CONTABILIZACION;
            else:
                $arr_respuesta['FECHA_CONTABILIZACION'] = (($num_periodo_ejecucion == $num_periodo) && ($anio_ejecucion == $anioEjecucion) ? $rowOrdenContratacion->FECHA_EJECUCION : $rowReferenciaFacturacion->FECHA_INICIO_PERIODO); //SI EL PERIODO ES EL DE LA FECHA EJECUCION, TOMAMOS LA FECHA EJECUCION COMO FECHA CONTABILIZACION
            endif;

            return $arr_respuesta;

        else://CALCULAMOS LA NUEVA REFERENCIA DE CONTRATACION Y RELLENAMOS POSIBLES REFERENCIAS INTERMEDIAS

            //SI EL AÑO ACTUAL Y EL DE EJECUCION ES EL MISMO
            if ($anioActual == $anioEjecucion):
                //SI SE INTENTA ACCEDER A UN PERIODO PASADO, LO CREAMOS PARA ESE PERIODO
                if ($num_periodo < $num_periodo_actual):

                    //BUSCO EL NUMERO DE FACTURACION QUE LE CORRESPONDA
                    $sqlConfigGral    = "SELECT ULTIMA_REFERENCIA_FACTURACION_PROVEEDOR AS ULTIMA_REFERENCIA FROM CONFIG_GRAL FOR UPDATE";
                    $resultConfigGral = $bd->ExecSQL($sqlConfigGral);
                    $rowRef           = $bd->SigReg($resultConfigGral);

                    //COMPROBACION POR SI YA EXISTE
                    $referencia_encontrada = false;
                    $numRefAutomatico      = $rowRef->ULTIMA_REFERENCIA;
                    while ($referencia_encontrada == false):
                        $numRefAutomatico = $numRefAutomatico + 1;

                        //LE DOY FORMATO A LA REFERENCIA DE FACTURACION
                        $nuevaReferenciaFacturacion = "T" . date('y', mktime(0, 0, 0, (int)$mesEjecucion, (int)$diaEjecucion, (int)$anioEjecucion)) . str_pad((string)$numRefAutomatico, 7, "0", STR_PAD_LEFT);

                        //BUSCAMOS SI LA REFERENCIA DE FACTURACION YA ESTA USADA
                        $GLOBALS["NotificaErrorPorEmail"] = "No";
                        $rowRefFacturacionUsada           = $bd->VerReg("PROVEEDOR_REFERENCIA_FACTURACION", "REFERENCIA_FACTURACION", $nuevaReferenciaFacturacion, "No");
                        unset($GLOBALS["NotificaErrorPorEmail"]);
                        //SI NO EXISTE
                        if ($rowRefFacturacionUsada == false):
                            $referencia_encontrada = true;
                        endif;
                    endwhile;

                    //ACTUALIZO EL ULTIMO NUMERO UTILIZADO
                    $sqlUpdate = "UPDATE CONFIG_GRAL SET ULTIMA_REFERENCIA_FACTURACION_PROVEEDOR = $numRefAutomatico";
                    $bd->ExecSQL($sqlUpdate);

                    //CALCULO LA FECHA DE INICIO Y FIN DEL PERIODO
                    if ($rowProveedor->PERIODO_FACTURACION == "Mensual"):
                        //CALCULO EL DIA 1 DEL MES DEL PERIODO
                        $fechaInicioPeriodo = date("Y-m-d", (mktime(0, 0, 0, (int)$num_periodo, 1, (int)$anioEjecucion)));
                        //CALCULO EL ULTIMO DIA DE ESE MES
                        $fechaFinPeriodo = date('Y-m-t', strtotime((string)$fechaInicioPeriodo));

                    elseif ($rowProveedor->PERIODO_FACTURACION == "Semanal"):
                        $string_fecha       = $anioEjecucion . 'W' . str_pad((string)$num_periodo, 2, '0', STR_PAD_LEFT);
                        $fechaInicioPeriodo = date('Y-m-d', strtotime((string)$string_fecha . '1'));
                        $fechaFinPeriodo    = date('Y-m-d', strtotime((string)$string_fecha . '7'));

                    elseif ($rowProveedor->PERIODO_FACTURACION == "Quincenal"):
                        //OBTENEMOS EL NUMERO DE DIAS DEL ACTUAL PERIODO
                        $dias_periodo = ($num_periodo - 1) * 15;

                        //AHORA OBTENEMOS EL NUMERO DE DIAS DE LA FECHA DEL HITO
                        $dias_hito = date('z', mktime(0, 0, 0, (int)$mesEjecucion, (int)$diaEjecucion, (int)$anioEjecucion));

                        //A LA FECHA DEL HITO LE RESTAMOS LOS DIAS DE DIFERENCIA ENTRE LA FECHA DEL HITO Y LA DE INICIO DEL PERIODO
                        $fechaInicioPeriodo = date('Y-m-d', strtotime((string)($fecha_contabilizacion != "" ? $fecha_contabilizacion : $rowOrdenContratacion['FECHA_EJECUCION']) . "- " . ($dias_hito - $dias_periodo) . " days"));
                        $fechaFinPeriodo    = date('Y-m-d', strtotime((string)$fechaInicioPeriodo . '+ 14 days'));

                    endif;


                    //ACTUALIZAMOS LA TABLA PROVEEDOR
                    $sqlInsert = "INSERT INTO PROVEEDOR_REFERENCIA_FACTURACION SET
                                    ID_PROVEEDOR = $rowOrdenContratacion->ID_PROVEEDOR
                                    ,TIPO_TRANSPORTE = 'OTC'
                                    ,ID_SOCIEDAD_CONTRATANTE = $rowSociedadContratante->ID_SOCIEDAD
                                    ,ID_PROVEEDOR_CONTRATO = NULL
                                    ,AÑO = '$anioEjecucion'
                                    ,PERIODO_FACTURACION = '$rowProveedor->PERIODO_FACTURACION'
                                    ,NUMERO_PERIODO = '$num_periodo'
                                    ,FECHA_INICIO_PERIODO = '$fechaInicioPeriodo'
                                    ,FECHA_FIN_PERIODO = '$fechaFinPeriodo'
                                    ,REFERENCIA_FACTURACION = '$nuevaReferenciaFacturacion'";
                    $bd->ExecSQL($sqlInsert);

                else://SI SE INTENTA CREAR PARA UN PERIODO POSTERIOR, BUSCAMOS LOS PERIODOS INTERMEDIOS

                    $numPeriodosIntermedios = $num_periodo - $num_periodo_actual;

                    //BUSCO EL NUMERO DE FACTURACION QUE LE CORRESPONDA
                    $sqlConfigGral    = "SELECT ULTIMA_REFERENCIA_FACTURACION_PROVEEDOR AS ULTIMA_REFERENCIA FROM CONFIG_GRAL FOR UPDATE";
                    $resultConfigGral = $bd->ExecSQL($sqlConfigGral);
                    $rowRef           = $bd->SigReg($resultConfigGral);

                    $numRefAutomatico = $rowRef->ULTIMA_REFERENCIA;

                    //RECORREMOS LOS PERIODOS INTERMEDIOS
                    $i = 0;
                    while ($i <= $numPeriodosIntermedios):

                        $numPeriodoIntermedio = $num_periodo_actual + $i;

                        //BUSCO SI PARA ESE PROVEEDOR, CON ESA PERIODICIDAD, HAY DEFINIDO UNA REFERENCIA PARA ESA FECHA DE EJECUCION
                        $sqlReferenciaFacturacionIntermedio    = "SELECT REFERENCIA_FACTURACION
                                     FROM PROVEEDOR_REFERENCIA_FACTURACION
                                     WHERE ID_SOCIEDAD_CONTRATANTE = $rowSociedadContratante->ID_SOCIEDAD AND ID_PROVEEDOR = $rowOrdenContratacion->ID_PROVEEDOR AND TIPO_TRANSPORTE = 'OTC' AND PERIODO_FACTURACION = '$rowProveedor->PERIODO_FACTURACION' AND NUMERO_PERIODO = $numPeriodoIntermedio AND AÑO = $anioActual AND ID_PROVEEDOR_CONTRATO IS NULL ";
                        $resultReferenciaFacturacionIntermedio = $bd->ExecSQL($sqlReferenciaFacturacionIntermedio);

                        //SI NO EXISTE , LO CREAMOS
                        if ($bd->NumRegs($resultReferenciaFacturacionIntermedio) == 0):

                            //COMPROBACION POR SI YA EXISTE
                            $referencia_encontrada = false;
                            while ($referencia_encontrada == false):
                                //AUMENTO EL NUMERO DE REFERENCIA
                                $numRefAutomatico = $numRefAutomatico + 1;

                                //LE DOY FORMATO A LA REFERENCIA DE FACTURACION (T + DOS CIFRAS DEL AÑO + 7 cifras automaticas)
                                $nuevaReferenciaFacturacion = "T" . date('y', mktime(0, 0, 0, (int)$mesEjecucion, (int)$diaEjecucion, (int)$anioEjecucion)) . str_pad((string)$numRefAutomatico, 7, "0", STR_PAD_LEFT);

                                //BUSCAMOS SI LA REFERENCIA DE FACTURACION YA ESTA USADA
                                $GLOBALS["NotificaErrorPorEmail"] = "No";
                                $rowRefFacturacionUsada           = $bd->VerReg("PROVEEDOR_REFERENCIA_FACTURACION", "REFERENCIA_FACTURACION", $nuevaReferenciaFacturacion, "No");
                                unset($GLOBALS["NotificaErrorPorEmail"]);
                                //SI NO EXISTE
                                if ($rowRefFacturacionUsada == false):
                                    $referencia_encontrada = true;
                                endif;
                            endwhile;

                            //CALCULO LA FECHA DE INICIO Y FIN DEL PERIODO
                            if ($rowProveedor->PERIODO_FACTURACION == "Mensual"):

                                //SI ES MENSUAL, Y HAY INDICADO UN DIA DE CIERRE,PONEMOS LA FECHA RESPECTO A ESE PERIODO
                                if ($diaCierreMensual > 0):
                                    //CALCULAMOS LA FECHA INICO UN DIA DESPUES DE LA FECHA DE CIERRE DEL MES PASADO
                                    $fechaInicioPeriodo = date("Y-m-d", (mktime(0, 0, 0, (int)$numPeriodoIntermedio - 1, (int)$diaCierreMensual + 1, (int)$anioActual)));

                                    //CALCULAMOS LA FECHA DE FIN CON EL DIA DE CIERRE
                                    $fechaFinPeriodo = date("Y-m-d", (mktime(0, 0, 0, (int)$numPeriodoIntermedio, (int)$diaCierreMensual, (int)$anioActual)));
                                else:
                                    //CALCULO EL DIA 1 DEL MES DEL PERIODO
                                    $fechaInicioPeriodo = date("Y-m-d", (mktime(0, 0, 0, (int)$numPeriodoIntermedio, 1, (int)$anioActual)));
                                    //CALCULO EL ULTIMO DIA DE ESE MES
                                    $fechaFinPeriodo = date('Y-m-t', strtotime((string)$fechaInicioPeriodo));
                                endif;

                            elseif ($rowProveedor->PERIODO_FACTURACION == "Semanal"):
                                $string_fecha       = $anioActual . 'W' . str_pad((string)$numPeriodoIntermedio, 2, '0', STR_PAD_LEFT);
                                $fechaInicioPeriodo = date('Y-m-d', strtotime((string)$string_fecha . '1'));
                                $fechaFinPeriodo    = date('Y-m-d', strtotime((string)$string_fecha . '7'));

                            elseif ($rowProveedor->PERIODO_FACTURACION == "Quincenal"):
                                //OBTENEMOS EL NUMERO DE DIAS DEL ACTUAL PERIODO
                                $dias_periodo = ($num_periodo - 1) * 15;

                                //AHORA OBTENEMOS EL NUMERO DE DIAS DE LA FECHA DEL HITO
                                $dias_hito = date('z', mktime(0, 0, 0, (int)$mesEjecucion, (int)$diaEjecucion, (int)$anioEjecucion));

                                //A LA FECHA DEL HITO LE RESTAMOS LOS DIAS DE DIFERENCIA ENTRE LA FECHA DEL HITO Y LA DE INICIO DEL PERIODO
                                $fechaInicioPeriodo = date('Y-m-d', strtotime((string)($fecha_contabilizacion != "" ? $fecha_contabilizacion : $rowOrdenContratacion['FECHA_EJECUCION']) . "- " . ($dias_hito - $dias_periodo) . " days"));
                                $fechaFinPeriodo    = date('Y-m-d', strtotime((string)$fechaInicioPeriodo . '+ 14 days'));

                            endif;

                            //ACTUALIZAMOS LA TABLA PROVEEDOR
                            $sqlInsert = "INSERT INTO PROVEEDOR_REFERENCIA_FACTURACION SET
                                            ID_PROVEEDOR = $rowOrdenContratacion->ID_PROVEEDOR
                                            ,TIPO_TRANSPORTE = 'OTC'
                                            ,ID_SOCIEDAD_CONTRATANTE = $rowSociedadContratante->ID_SOCIEDAD
                                            ,ID_PROVEEDOR_CONTRATO = NULL
                                            ,AÑO = '$anioActual'
                                            ,PERIODO_FACTURACION = '$rowProveedor->PERIODO_FACTURACION'
                                            ,NUMERO_PERIODO = '$numPeriodoIntermedio'
                                            ,FECHA_INICIO_PERIODO = '$fechaInicioPeriodo'
                                            ,FECHA_FIN_PERIODO = '$fechaFinPeriodo'
                                            ,REFERENCIA_FACTURACION = '$nuevaReferenciaFacturacion'";
                            $bd->ExecSQL($sqlInsert);

                        endif;

                        $i = $i + 1;
                    endwhile;

                    //ACTUALIZO EL ULTIMO NUMERO UTILIZADO
                    $sqlUpdate = "UPDATE CONFIG_GRAL SET ULTIMA_REFERENCIA_FACTURACION_PROVEEDOR = $numRefAutomatico";
                    $bd->ExecSQL($sqlUpdate);


                endif;

            elseif ($anioActual > $anioEjecucion): //SI LA FECHA DE EJECUCIÓN ES PASADA

                //BUSCO EL ÚLTIMO NUMERO DE FACTURACION INTRODUCIDO EN EL AÑO DE LA FACTURACIÓN SELECCIONADO
                $sqlUltimaReferencia    = "SELECT MAX(REFERENCIA_FACTURACION) AS REFERENCIA_FACTURACION FROM PROVEEDOR_REFERENCIA_FACTURACION WHERE AÑO = $anioEjecucion";
                $resultUltimaReferencia = $bd->ExecSQL($sqlUltimaReferencia);
                $rowRef                 = $bd->SigReg($resultUltimaReferencia);


                //COMPROBACION POR SI YA EXISTE
                $referencia_encontrada = false;
                $numRefAutomatico      = $rowRef->REFERENCIA_FACTURACION;
                $numRefAutomatico      = intval(substr((string)$numRefAutomatico, 3, 7));
                while ($referencia_encontrada == false):
                    //OBTENEMOS EL NÚMERO DE LA REFERENCIA
                    $numRefAutomatico = $numRefAutomatico + 1;

                    //LE DOY FORMATO A LA REFERENCIA DE FACTURACION
                    $nuevaReferenciaFacturacion = "T" . date('y', mktime(0, 0, 0, (int)$mesEjecucion, (int)$diaEjecucion, (int)$anioEjecucion)) . str_pad((string)$numRefAutomatico, 7, "0", STR_PAD_LEFT);

                    //BUSCAMOS SI LA REFERENCIA DE FACTURACION YA ESTA USADA
                    $GLOBALS["NotificaErrorPorEmail"] = "No";
                    $rowRefFacturacionUsada           = $bd->VerReg("PROVEEDOR_REFERENCIA_FACTURACION", "REFERENCIA_FACTURACION", $nuevaReferenciaFacturacion, "No");
                    unset($GLOBALS["NotificaErrorPorEmail"]);
                    //SI NO EXISTE
                    if ($rowRefFacturacionUsada == false):
                        $referencia_encontrada = true;
                    endif;
                endwhile;


                //CALCULO LA FECHA DE INICIO Y FIN DEL PERIODO
                if ($rowProveedor->PERIODO_FACTURACION == "Mensual"):
                    //CALCULO EL DIA 1 DEL MES DEL PERIODO
                    $fechaInicioPeriodo = date("Y-m-d", (mktime(0, 0, 0, (int)$num_periodo, 1, (int)$anioEjecucion)));
                    //CALCULO EL ULTIMO DIA DE ESE MES
                    $fechaFinPeriodo = date('Y-m-t', strtotime((string)$fechaInicioPeriodo));

                elseif ($rowProveedor->PERIODO_FACTURACION == "Semanal"):
                    $string_fecha       = $anioEjecucion . 'W' . str_pad((string)$num_periodo, 2, '0', STR_PAD_LEFT);
                    $fechaInicioPeriodo = date('Y-m-d', strtotime((string)$string_fecha . '1'));
                    $fechaFinPeriodo    = date('Y-m-d', strtotime((string)$string_fecha . '7'));

                elseif ($rowProveedor->PERIODO_FACTURACION == "Quincenal"):
                    //OBTENEMOS EL NUMERO DE DIAS DEL ACTUAL PERIODO
                    $dias_periodo = ($num_periodo - 1) * 15;

                    //AHORA OBTENEMOS EL NUMERO DE DIAS DE LA FECHA DEL HITO
                    $dias_hito = date('z', mktime(0, 0, 0, (int)$mesEjecucion, (int)$diaEjecucion, (int)$anioEjecucion));

                    //A LA FECHA DEL HITO LE RESTAMOS LOS DIAS DE DIFERENCIA ENTRE LA FECHA DEL HITO Y LA DE INICIO DEL PERIODO
                    $fechaInicioPeriodo = date('Y-m-d', strtotime((string)($fecha_contabilizacion != "" ? $fecha_contabilizacion : $rowOrdenContratacion['FECHA_EJECUCION']) . "- " . ($dias_hito - $dias_periodo) . " days"));
                    $fechaFinPeriodo    = date('Y-m-d', strtotime((string)$fechaInicioPeriodo . '+ 14 days'));

                endif;


                //ACTUALIZAMOS LA TABLA PROVEEDOR
                $sqlInsert = "INSERT INTO PROVEEDOR_REFERENCIA_FACTURACION SET
                                    ID_PROVEEDOR = $rowOrdenContratacion->ID_PROVEEDOR
                                    ,TIPO_TRANSPORTE = 'OTC'
                                    ,ID_SOCIEDAD_CONTRATANTE = $rowSociedadContratante->ID_SOCIEDAD
                                    ,ID_PROVEEDOR_CONTRATO = NULL
                                    ,AÑO = '$anioEjecucion'
                                    ,PERIODO_FACTURACION = '$rowProveedor->PERIODO_FACTURACION'
                                    ,NUMERO_PERIODO = '$num_periodo'
                                    ,FECHA_INICIO_PERIODO = '$fechaInicioPeriodo'
                                    ,FECHA_FIN_PERIODO = '$fechaFinPeriodo'
                                    ,REFERENCIA_FACTURACION = '$nuevaReferenciaFacturacion'";
                $bd->ExecSQL($sqlInsert);

            else:// SI EL AÑO ES EL SIGUIENTE

                //BUSCO EL NUMERO DE FACTURACION QUE LE CORRESPONDA
                $sqlConfigGral    = "SELECT ULTIMA_REFERENCIA_FACTURACION_PROVEEDOR AS ULTIMA_REFERENCIA FROM CONFIG_GRAL FOR UPDATE";
                $resultConfigGral = $bd->ExecSQL($sqlConfigGral);
                $rowRef           = $bd->SigReg($resultConfigGral);

                $numRefAutomatico = $rowRef->ULTIMA_REFERENCIA;

                //RECORREMOS LOS PERIODOS RESTANTES DE EL AÑO ACTUAL
                $numPeriodosIntermedios = $num_ultimo_periodo_anio - $num_periodo_actual;


                //RECORREMOS LOS PERIODOS INTERMEDIOS DE ESTE AÑO
                $i = 0;
                while ($i <= $numPeriodosIntermedios):

                    $numPeriodoIntermedio = $num_periodo_actual + $i;

                    //BUSCO SI PARA ESE PROVEEDOR, CON ESA PERIODICIDAD, HAY DEFINIDO UNA REFERENCIA PARA ESA FECHA DE EJECUCION
                    $sqlReferenciaFacturacionIntermedio    = "SELECT REFERENCIA_FACTURACION
                                     FROM PROVEEDOR_REFERENCIA_FACTURACION
                                     WHERE ID_SOCIEDAD_CONTRATANTE = $rowSociedadContratante->ID_SOCIEDAD AND ID_PROVEEDOR = $rowOrdenContratacion->ID_PROVEEDOR AND TIPO_TRANSPORTE = 'OTC' AND PERIODO_FACTURACION = '$rowProveedor->PERIODO_FACTURACION' AND NUMERO_PERIODO = $numPeriodoIntermedio AND AÑO = $anioActual AND ID_PROVEEDOR_CONTRATO IS NULL ";
                    $resultReferenciaFacturacionIntermedio = $bd->ExecSQL($sqlReferenciaFacturacionIntermedio);

                    //SI NO EXISTE , LO CREAMOS
                    if ($bd->NumRegs($resultReferenciaFacturacionIntermedio) == 0):

                        //COMPROBACION POR SI YA EXISTE
                        $referencia_encontrada = false;
                        while ($referencia_encontrada == false):
                            //AUMENTO EL NUMERO DE REFERENCIA
                            $numRefAutomatico = $numRefAutomatico + 1;

                            //LE DOY FORMATO A LA REFERENCIA DE FACTURACION (T + DOS CIFRAS DEL AÑO + 7 cifras automaticas)
                            $nuevaReferenciaFacturacion = "T" . date('y', mktime(0, 0, 0, (int)$mesEjecucion, (int)$diaEjecucion, (int)$anioActual)) . str_pad((string)$numRefAutomatico, 7, "0", STR_PAD_LEFT);

                            //BUSCAMOS SI LA REFERENCIA DE FACTURACION YA ESTA USADA
                            $GLOBALS["NotificaErrorPorEmail"] = "No";
                            $rowRefFacturacionUsada           = $bd->VerReg("PROVEEDOR_REFERENCIA_FACTURACION", "REFERENCIA_FACTURACION", $nuevaReferenciaFacturacion, "No");
                            unset($GLOBALS["NotificaErrorPorEmail"]);
                            //SI NO EXISTE
                            if ($rowRefFacturacionUsada == false):
                                $referencia_encontrada = true;
                            endif;
                        endwhile;

                        //CALCULO LA FECHA DE INICIO Y FIN DEL PERIODO
                        if ($rowProveedor->PERIODO_FACTURACION == "Mensual"):

                            //SI ES MENSUAL, Y HAY INDICADO UN DIA DE CIERRE,PONEMOS LA FECHA RESPECTO A ESE PERIODO
                            if ($diaCierreMensual > 0):
                                //CALCULAMOS LA FECHA INICO UN DIA DESPUES DE LA FECHA DE CIERRE DEL MES PASADO
                                $fechaInicioPeriodo = date("Y-m-d", (mktime(0, 0, 0, (int)$numPeriodoIntermedio - 1, (int)$diaCierreMensual + 1, (int)$anioActual)));

                                //CALCULAMOS LA FECHA DE FIN CON EL DIA DE CIERRE
                                $fechaFinPeriodo = date("Y-m-d", (mktime(0, 0, 0, (int)$numPeriodoIntermedio, (int)$diaCierreMensual, (int)$anioActual)));
                            else:
                                //CALCULO EL DIA 1 DEL MES DEL PERIODO
                                $fechaInicioPeriodo = date("Y-m-d", (mktime(0, 0, 0, (int)$numPeriodoIntermedio, 1, (int)$anioActual)));
                                //CALCULO EL ULTIMO DIA DE ESE MES
                                $fechaFinPeriodo = date('Y-m-t', strtotime((string)$fechaInicioPeriodo));
                            endif;

                        elseif ($rowProveedor->PERIODO_FACTURACION == "Semanal"):

                            $string_fecha       = $anioActual . 'W' . str_pad((string)$numPeriodoIntermedio, 2, '0', STR_PAD_LEFT);
                            $fechaInicioPeriodo = date('Y-m-d', strtotime((string)$string_fecha . '1'));
                            $fechaFinPeriodo    = date('Y-m-d', strtotime((string)$string_fecha . '7'));

                        elseif ($rowProveedor->PERIODO_FACTURACION == "Quincenal"):

                            //OBTENEMOS EL NUMERO DE DIAS DEL ACTUAL PERIODO
                            $dias_periodo = ($num_periodo - 1) * 15;

                            //AHORA OBTENEMOS EL NUMERO DE DIAS DE LA FECHA DEL HITO
                            $dias_hito = date('z', mktime(0, 0, 0, (int)$mesEjecucion, (int)$diaEjecucion, (int)$anioEjecucion));

                            //A LA FECHA DEL HITO LE RESTAMOS LOS DIAS DE DIFERENCIA ENTRE LA FECHA DEL HITO Y LA DE INICIO DEL PERIODO
                            $fechaInicioPeriodo = date('Y-m-d', strtotime((string)($fecha_contabilizacion != "" ? $fecha_contabilizacion : $rowOrdenContratacion['FECHA_EJECUCION']) . "- " . ($dias_hito - $dias_periodo) . " days"));
                            $fechaFinPeriodo    = date('Y-m-d', strtotime((string)$fechaInicioPeriodo . '+ 14 days'));

                        endif;

                        //ACTUALIZAMOS LA TABLA PROVEEDOR
                        $sqlInsert = "INSERT INTO PROVEEDOR_REFERENCIA_FACTURACION SET
                                            ID_PROVEEDOR = $rowOrdenContratacion->ID_PROVEEDOR
                                            ,TIPO_TRANSPORTE = 'OTC'
                                            ,ID_SOCIEDAD_CONTRATANTE = $rowSociedadContratante->ID_SOCIEDAD
                                            ,ID_PROVEEDOR_CONTRATO = NULL
                                            ,AÑO = '$anioActual'
                                            ,PERIODO_FACTURACION = '$rowProveedor->PERIODO_FACTURACION'
                                            ,NUMERO_PERIODO = '$numPeriodoIntermedio'
                                            ,FECHA_INICIO_PERIODO = '$fechaInicioPeriodo'
                                            ,FECHA_FIN_PERIODO = '$fechaFinPeriodo'
                                            ,REFERENCIA_FACTURACION = '$nuevaReferenciaFacturacion'";
                        $bd->ExecSQL($sqlInsert);
                    endif;

                    $i = $i + 1;
                endwhile;
                //FIN RECORREMOS LOS PERIODOS INTERMEDIOS DE ESTE AÑO

                //ACTUALIZO EL ULTIMO NUMERO  UTILIZADO
                $sqlUpdate = "UPDATE CONFIG_GRAL SET ULTIMA_REFERENCIA_FACTURACION_PROVEEDOR = $numRefAutomatico";
                $bd->ExecSQL($sqlUpdate);

                //BUSCO EL NUMERO DE FACTURACION QUE LE CORRESPONDA AL AÑO SIGIENTE
                $sqlConfigGral    = "SELECT ULTIMA_REFERENCIA_FACTURACION_PROVEEDOR_AÑO_SIGUIENTE AS ULTIMA_REFERENCIA FROM CONFIG_GRAL FOR UPDATE";
                $resultConfigGral = $bd->ExecSQL($sqlConfigGral);
                $rowRef           = $bd->SigReg($resultConfigGral);

                $numRefAutomatico = $rowRef->ULTIMA_REFERENCIA;

                //RECORREMOS LOS PERIODOS DEL AÑO SIGUIENTE
                $j = 1;

                while ($j <= $num_periodo):

                    $numPeriodoIntermedio = $j;

                    //BUSCO SI PARA ESE PROVEEDOR, CON ESA PERIODICIDAD, HAY DEFINIDO UNA REFERENCIA PARA ESA FECHA DE EJECUCION
                    $sqlReferenciaFacturacionIntermedio    = "SELECT REFERENCIA_FACTURACION
                                     FROM PROVEEDOR_REFERENCIA_FACTURACION
                                     WHERE ID_SOCIEDAD_CONTRATANTE = $rowSociedadContratante->ID_SOCIEDAD AND ID_PROVEEDOR = $rowOrdenContratacion->ID_PROVEEDOR AND TIPO_TRANSPORTE = 'OTC' AND PERIODO_FACTURACION = '$rowProveedor->PERIODO_FACTURACION' AND NUMERO_PERIODO = $numPeriodoIntermedio AND AÑO = $anioEjecucion AND ID_PROVEEDOR_CONTRATO IS NULL ";
                    $resultReferenciaFacturacionIntermedio = $bd->ExecSQL($sqlReferenciaFacturacionIntermedio);

                    //SI NO EXISTE , LO CREAMOS
                    if ($bd->NumRegs($resultReferenciaFacturacionIntermedio) == 0):

                        //COMPROBACION POR SI YA EXISTE
                        $referencia_encontrada = false;
                        while ($referencia_encontrada == false):
                            //AUMENTO EL NUMERO DE REFERENCIA
                            $numRefAutomatico = $numRefAutomatico + 1;

                            //LE DOY FORMATO A LA REFERENCIA DE FACTURACION (T + DOS CIFRAS DEL AÑO + 7 cifras automaticas)
                            $nuevaReferenciaFacturacion = "T" . date('y', mktime(0, 0, 0, (int)$mesEjecucion, (int)$diaEjecucion, (int)$anioEjecucion)) . str_pad((string)$numRefAutomatico, 7, "0", STR_PAD_LEFT);

                            //BUSCAMOS SI LA REFERENCIA DE FACTURACION YA ESTA USADA
                            $GLOBALS["NotificaErrorPorEmail"] = "No";
                            $rowRefFacturacionUsada           = $bd->VerReg("PROVEEDOR_REFERENCIA_FACTURACION", "REFERENCIA_FACTURACION", $nuevaReferenciaFacturacion, "No");
                            unset($GLOBALS["NotificaErrorPorEmail"]);
                            //SI NO EXISTE
                            if ($rowRefFacturacionUsada == false):
                                $referencia_encontrada = true;
                            endif;
                        endwhile;

                        //CALCULO LA FECHA DE INICIO Y FIN DEL PERIODO
                        if ($rowProveedor->PERIODO_FACTURACION == "Mensual"):

                            //SI ES MENSUAL, Y HAY INDICADO UN DIA DE CIERRE,PONEMOS LA FECHA RESPECTO A ESE PERIODO
                            if ($diaCierreMensual > 0):
                                //CALCULAMOS LA FECHA INICO UN DIA DESPUES DE LA FECHA DE CIERRE DEL MES PASADO
                                $fechaInicioPeriodo = date("Y-m-d", (mktime(0, 0, 0, (int)$numPeriodoIntermedio - 1, (int)$diaCierreMensual + 1, (int)$anioEjecucion)));

                                //CALCULAMOS LA FECHA DE FIN CON EL DIA DE CIERRE
                                $fechaFinPeriodo = date("Y-m-d", (mktime(0, 0, 0, (int)$numPeriodoIntermedio, (int)$diaCierreMensual, (int)$anioEjecucion)));
                            else:

                                //CALCULO EL DIA 1 DEL MES DEL PERIODO
                                $fechaInicioPeriodo = date("Y-m-d", (mktime(0, 0, 0, (int)$numPeriodoIntermedio, 1, (int)$anioEjecucion)));
                                //CALCULO EL ULTIMO DIA DE ESE MES
                                $fechaFinPeriodo = date('Y-m-t', strtotime((string)$fechaInicioPeriodo));
                            endif;

                        elseif ($rowProveedor->PERIODO_FACTURACION == "Semanal"):
                            $string_fecha       = $anioEjecucion . 'W' . str_pad((string)$numPeriodoIntermedio, 2, '0', STR_PAD_LEFT);
                            $fechaInicioPeriodo = date('Y-m-d', strtotime((string)$string_fecha . '1'));
                            $fechaFinPeriodo    = date('Y-m-d', strtotime((string)$string_fecha . '7'));

                        elseif ($rowProveedor->PERIODO_FACTURACION == "Quincenal"):
                            //OBTENEMOS EL NUMERO DE DIAS DEL ACTUAL PERIODO
                            $dias_periodo = ($num_periodo - 1) * 15;

                            //AHORA OBTENEMOS EL NUMERO DE DIAS DE LA FECHA DEL HITO
                            $dias_hito = date('z', mktime(0, 0, 0, (int)$mesEjecucion, (int)$diaEjecucion, (int)$anioEjecucion));

                            //A LA FECHA DEL HITO LE RESTAMOS LOS DIAS DE DIFERENCIA ENTRE LA FECHA DEL HITO Y LA DE INICIO DEL PERIODO
                            $fechaInicioPeriodo = date('Y-m-d', strtotime((string)($fecha_contabilizacion != "" ? $fecha_contabilizacion : $rowOrdenContratacion['FECHA_EJECUCION']) . "- " . ($dias_hito - $dias_periodo) . " days"));
                            $fechaFinPeriodo    = date('Y-m-d', strtotime((string)$fechaInicioPeriodo . '+ 14 days'));

                        endif;

                        //ACTUALIZAMOS LA TABLA PROVEEDOR
                        $sqlInsert = "INSERT INTO PROVEEDOR_REFERENCIA_FACTURACION SET
                                            ID_PROVEEDOR = $rowOrdenContratacion->ID_PROVEEDOR
                                            ,TIPO_TRANSPORTE = 'OTC'
                                            ,ID_SOCIEDAD_CONTRATANTE = $rowSociedadContratante->ID_SOCIEDAD
                                            ,ID_PROVEEDOR_CONTRATO = NULL
                                            ,AÑO = '$anioEjecucion'
                                            ,PERIODO_FACTURACION = '$rowProveedor->PERIODO_FACTURACION'
                                            ,NUMERO_PERIODO = '$numPeriodoIntermedio'
                                            ,FECHA_INICIO_PERIODO = '$fechaInicioPeriodo'
                                            ,FECHA_FIN_PERIODO = '$fechaFinPeriodo'
                                            ,REFERENCIA_FACTURACION = '$nuevaReferenciaFacturacion'";
                        $bd->ExecSQL($sqlInsert);
                    endif;

                    $j = $j + 1;
                endwhile;
                //FIN RECORREMOS LOS PERIODOS DEL AÑO SIGUIENTE

                //ACTUALIZO EL ULTIMO NUMERO UTILIZADO
                $sqlUpdate = "UPDATE CONFIG_GRAL SET ULTIMA_REFERENCIA_FACTURACION_PROVEEDOR_AÑO_SIGUIENTE = $numRefAutomatico";
                $bd->ExecSQL($sqlUpdate);


            endif;

            //BUSCO LA REFERENCIA PARA EL PERIODO DE LA CONTRATACION
            $sqlReferenciaFacturacion    = "SELECT REFERENCIA_FACTURACION,FECHA_INICIO_PERIODO
                                     FROM PROVEEDOR_REFERENCIA_FACTURACION
                                     WHERE ID_SOCIEDAD_CONTRATANTE = $rowSociedadContratante->ID_SOCIEDAD AND ID_PROVEEDOR = $rowOrdenContratacion->ID_PROVEEDOR AND TIPO_TRANSPORTE = 'OTC' AND PERIODO_FACTURACION = '$rowProveedor->PERIODO_FACTURACION' AND NUMERO_PERIODO = $num_periodo AND AÑO = $anioEjecucion AND ID_PROVEEDOR_CONTRATO IS NULL ";
            $resultReferenciaFacturacion = $bd->ExecSQL($sqlReferenciaFacturacion);
            $rowReferenciaFacturacion    = $bd->SigReg($resultReferenciaFacturacion);

            $arr_respuesta['REFERENCIA_FACTURACION'] = $rowReferenciaFacturacion->REFERENCIA_FACTURACION;
            //EN LA CONTABILIZACION MANUAL, SE MANTIENE
            if ($rowOrdenContratacion->CONTABILIZACION_MANUAL == 1):
                $arr_respuesta['FECHA_CONTABILIZACION'] = $rowOrdenContratacion->FECHA_CONTABILIZACION;
            else:
                $arr_respuesta['FECHA_CONTABILIZACION'] = (($num_periodo_ejecucion == $num_periodo) && ($anio_ejecucion == $anioEjecucion) ? $rowOrdenContratacion->FECHA_EJECUCION : $rowReferenciaFacturacion->FECHA_INICIO_PERIODO); //SI EL PERIODO ES EL DE LA FECHA EJECUCION, TOMAMOS LA FECHA EJECUCION COMO FECHA CONTABILIZACION
            endif;

            return $arr_respuesta;

        endif;//FIN EXISTE/NO EXISTE REFERENCIA FACTURACION
    }

    /**
     * @param $idOrdenContratacion ORDEN DE CONTRATACION A LA QUE SE DESASIGNA LA REFERENCIA DE FACTURACION
     * FUNCION UTILIZADA PARA DESASIGNAR LA REFERENCIA DE FACTURACION DE UNA CONTRATACION
     */
    function desasignarReferenciaFacturacion($idOrdenContratacion)
    {

        global $bd;
        global $html;
        global $auxiliar;
        global $administrador;

        //BUSCO LA ORDEN DE CONTRATACION
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowOrdenContratacion             = $bd->VerReg("ORDEN_CONTRATACION", "ID_ORDEN_CONTRATACION", $idOrdenContratacion, "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);

        //BUSCO LA AUTOFACTURA ASOCIADA A LA CONTRATACION
        $sqlReferenciaFacturacion    = "SELECT AF.ID_AUTOFACTURA
                                    FROM AUTOFACTURA_LINEA AFL
                                        INNER JOIN AUTOFACTURA AF ON AF.ID_AUTOFACTURA = AFL.ID_AUTOFACTURA
                                    WHERE AFL.ID_ORDEN_CONTRATACION = " . $idOrdenContratacion . " AND AFL.BAJA = 0 AND AF.BAJA = 0";
        $resultReferenciaFacturacion = $bd->ExecSQL($sqlReferenciaFacturacion);

        //PRIMERO DESASIGNAMOS LA REFERENCIA DE FACTURACION DE LA OC
        $sqlUpdateContratacion = "UPDATE ORDEN_CONTRATACION SET 
                                        REFERENCIA_FACTURACION = '' 
                                    WHERE ID_ORDEN_CONTRATACION = $idOrdenContratacion";
        $bd->ExecSQL($sqlUpdateContratacion);

        if ($bd->NumRegs($resultReferenciaFacturacion) > 0):
            $rowReferenciaFacturacion = $bd->SigReg($resultReferenciaFacturacion);
            $idAutofactura            = $rowReferenciaFacturacion->ID_AUTOFACTURA;

            //AHORA DAMOS DE BAJA LA LINEA DE LA AUTOFACTURA ASOCIADA A LA OC
            $sqlUpdateLineaAutofactura = "UPDATE AUTOFACTURA_LINEA SET 
                                        BAJA = 1 
                                    WHERE ID_ORDEN_CONTRATACION = $idOrdenContratacion";
            $bd->ExecSQL($sqlUpdateLineaAutofactura);


            //AHORA OBTENEMOS EL NUMERO DE LINEAS ACTIVAS DEL INFORME DE CERTIFICACION
            $sqlLineasActivasInforme    = "SELECT ID_AUTOFACTURA_LINEA
                                    FROM AUTOFACTURA_LINEA
                                    WHERE ID_AUTOFACTURA = $idAutofactura AND BAJA = 0 ";
            $resultLineasActivasInforme = $bd->ExecSQL($sqlLineasActivasInforme);

            if ($bd->NumRegs($resultLineasActivasInforme) == 0):
                //SI NO TIENE NINGUNA LINEA MAS ACTIVA, SE DA DE BAJA EL INFORME DE CERTIFICACION
                $sqlUpdateAutofactura = "UPDATE AUTOFACTURA SET 
                                        BAJA = 1 
                                    WHERE ID_AUTOFACTURA = $idAutofactura";
                $bd->ExecSQL($sqlUpdateAutofactura);
            endif;
        endif;


        //PRIMERO DESASIGNAMOS LA REFERENCIA DE FACTURACION DE LA OC
        $sqlUpdateContratacion = "UPDATE ORDEN_CONTRATACION SET 
                                        REFERENCIA_FACTURACION = '',
                                        FECHA_CONTABILIZACION = '0000-00-00'
                                    WHERE ID_ORDEN_CONTRATACION = $idOrdenContratacion";
        $bd->ExecSQL($sqlUpdateContratacion);


        //AHORA DAMOS DE BAJA LA LINEA DE LA AUTOFACTURA ASOCIADA A LA OC
        $sqlUpdateLineaAutofactura = "UPDATE AUTOFACTURA_LINEA SET 
                                        BAJA = 1 
                                    WHERE ID_ORDEN_CONTRATACION = $idOrdenContratacion";
        $bd->ExecSQL($sqlUpdateLineaAutofactura);

        return true;
    }

    /**
     * @param $idOrdenContratacion ORDEN DE CONTRATACION SOBRE LA QUE CALCULAR EL ORDEN
     * FUNCION UTILIZADA PARA ACTUALIZAR EL ORDEN DE LOS DESTINOS DE UNA CONTRATACION
     */
    function actualizarFechaEjecucion($idOrdenContratacion)
    {

        global $bd;
        global $html;
        global $auxiliar;
        global $administrador;

        //BUSCO LA ORDEN DE CONTRATACION
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowOrdenContratacion             = $bd->VerReg("ORDEN_CONTRATACION", "ID_ORDEN_CONTRATACION", $idOrdenContratacion, "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);

        //COMPRUEBO QUE EXISTA LA ORDEN DE TRANSPORTE
        $html->PagErrorCondicionado($rowOrdenContratacion, "==", false, "OrdenContratacionNoEncontrada");

        //OBTENGO LAS CONTRATACIONES ORDENADAS POR FECHA Y HORA
        $sqlFechaEjecucion    = "SELECT MIN(FECHA_SERVICIO) AS FECHA_EJECUCION FROM ORDEN_CONTRATACION_DESTINO WHERE ID_ORDEN_CONTRATACION=$idOrdenContratacion AND BAJA=0";
        $resultFechaEjecucion = $bd->ExecSQL($sqlFechaEjecucion);
        $rowFechaEjecucion    = $bd->SigReg($resultFechaEjecucion);

        //SI HAY FECHAS COMPROBAMOS QUE SON VALIDAS
        if ($rowFechaEjecucion->FECHA_EJECUCION != ""):
            //CALCULO EL AÑO DE EJECUCION
            $anioEjecucion = substr((string)$rowFechaEjecucion->FECHA_EJECUCION, 0, 4);

            //SOLO DEJAMOS AÑO DE EJECUCION PARA ESTE AÑO Y EL SIGUIENTE
            if (($anioEjecucion < date('Y')) || ($anioEjecucion > (date('Y') + 1))):
                $html->PagError("AñoEjecucionFueraDeRango");
            endif;
        endif;

        //ACTUALIZO LA FECHA DE EJECUCION DE LA ORDEN DE CONTRATACION
        $sqlUpdate = "UPDATE ORDEN_CONTRATACION SET
                      FECHA_EJECUCION = " . ($rowFechaEjecucion->FECHA_EJECUCION != "" ? "'$rowFechaEjecucion->FECHA_EJECUCION'" : "NULL") . "
                      WHERE ID_ORDEN_CONTRATACION=" . $idOrdenContratacion;
        $bd->ExecSQL($sqlUpdate);
    }


    /**
     * @param $idOrdenContratacion ORDEN DE CONTRATACION
     * FUNCION UTILIZADA PARA ACTUALIZAR EL NUMERO DE DESTINOS DE UNA ORDEN DE TRANSPORTE
     */
    function actualizarNumeroDestinos($idOrdenContratacion)
    {

        global $bd;
        global $html;
        global $auxiliar;
        global $administrador;

        //BUSCO LA ORDEN DE CONTRATACION
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowOrdenContratacion             = $bd->VerReg("ORDEN_CONTRATACION", "ID_ORDEN_CONTRATACION", $idOrdenContratacion, "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);

        //COMPRUEBO QUE EXISTA LA ORDEN DE TRANSPORTE
        $html->PagErrorCondicionado($rowOrdenContratacion, "==", false, "OrdenContratacionNoEncontrada");

        //SI LA ORDEN DE CONTRATACION ES TRANSPORTE PRINCIPAL, ACTUALIZAMOS EL NUMERO DE DESTINOS DE LA ORDEN DE TRANSPORTE
        if ($rowOrdenContratacion->CONTRATACION_TRANSPORTE_PRINCIPAL == 1):

            //BUSCO LOS DESTINOS
            $sqlDestinos    = "SELECT COUNT(*) AS DESTINOS FROM ORDEN_CONTRATACION_DESTINO WHERE ID_ORDEN_CONTRATACION=$idOrdenContratacion AND BAJA=0";
            $resultDestinos = $bd->ExecSQL($sqlDestinos);

            $rowDestinos = $bd->SigReg($resultDestinos);
            $numDestinos = $rowDestinos->DESTINOS;

            $sqlUpdate = "UPDATE ORDEN_TRANSPORTE SET NUMERO_DESTINOS=$numDestinos WHERE ID_ORDEN_TRANSPORTE=$rowOrdenContratacion->ID_ORDEN_TRANSPORTE";
            $bd->ExecSQL($sqlUpdate);

        endif;

    }

    /**
     * @param $idOrdenTransporte ORDEN DE TRANSPORTE
     * @param $idSolicitudTransporte SOLICITUD DE TRANSPORTE PARA SOLO OBTENER LO RELACIONADO CON ELLA
     * @return array CON LOS IDS DE CF DESTINO DE LOS TRASLADOS
     * FUNCION PARA OBTENER LOS CENTROS FISICOS DE TRASLADOS
     */
    function getCentrosFisicosDestinoTraslado($idOrdenTransporte, $idSolicitudTransporte = "")
    {

        global $bd;
        global $html;
        global $auxiliar;
        global $administrador;

        //CREAMOS EL ARRAY A DEVOLVER
        $arrIdCentroFisicoDestino = array();

        //BUSCO LA ORDEN DE TRANSPORTE
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowOrdenTransporte               = $bd->VerReg("ORDEN_TRANSPORTE", "ID_ORDEN_TRANSPORTE", $idOrdenTransporte, "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);

        if ($rowOrdenTransporte == false): //SI NO EXISTE , SALGO DE LA FUNCION
            return $arrIdCentroFisicoDestino;
        endif;

        //SI VIENE SOLICITUD, AÑADIREMOS EL FILTRO OBTENER SOLO ESAS RECOGIDAS
        $sqlWhereSolicitud = "";
        if ($idSolicitudTransporte != ""):
            $sqlWhereSolicitud = " AND E.ID_SOLICITUD_TRANSPORTE = $idSolicitudTransporte";
        endif;


        //BUSCAMOS LOS CF DESTINO DE TODAS LAS RECOGIDAS
        $sqlRecogidas    = "SELECT DISTINCT CFD.ID_CENTRO_FISICO
                           FROM MOVIMIENTO_SALIDA_LINEA MSL
                            INNER JOIN EXPEDICION E ON E.ID_EXPEDICION = MSL.ID_EXPEDICION
                            INNER JOIN PEDIDO_SALIDA_LINEA PSL ON PSL.ID_PEDIDO_SALIDA_LINEA = MSL.ID_PEDIDO_SALIDA_LINEA
                            INNER JOIN ALMACEN AD ON PSL.ID_ALMACEN_DESTINO = AD.ID_ALMACEN
                            INNER JOIN CENTRO_FISICO CFD ON CFD.ID_CENTRO_FISICO = AD.ID_CENTRO_FISICO
                            WHERE E.ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND MSL.LINEA_ANULADA = 0 AND MSL.BAJA = 0 AND E.BAJA = 0 $sqlWhereSolicitud";
        $resultRecogidas = $bd->ExecSQL($sqlRecogidas);

        //RECORRO LOS CF DE RECOGIDAS EXPEDIDAS
        while ($rowRecogidas = $bd->SigReg($resultRecogidas)):
            $arrIdCentroFisicoDestino[] = $rowRecogidas->ID_CENTRO_FISICO;
        endwhile;


        //BUSCAMOS LOS CF DE MATERIAL ESTROPEADO DSDE PROVEEDOR
        $sqlRecogidasDesdeProveedor    = "SELECT DISTINCT CFD.ID_CENTRO_FISICO
                                        FROM  MOVIMIENTO_SALIDA_LINEA MSL
                                        INNER JOIN MOVIMIENTO_SALIDA MS_REL ON MSL.ID_MOVIMIENTO_SALIDA_MATERIAL_ESTROPEADO = MS_REL.ID_MOVIMIENTO_SALIDA
                                        INNER JOIN EXPEDICION E ON E.ID_EXPEDICION = MSL.ID_EXPEDICION
                                        INNER JOIN ALMACEN AD ON AD.ID_ALMACEN = MSL.ID_ALMACEN_DESTINO
                                        INNER JOIN CENTRO_FISICO CFD ON CFD.ID_CENTRO_FISICO = AD.ID_CENTRO_FISICO
                                        WHERE E.ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND MSL.BAJA = 0 $sqlWhereSolicitud";
        $resultRecogidasDesdeProveedor = $bd->ExecSQL($sqlRecogidasDesdeProveedor);

        //RECORRO LOS CF DE RECOGIDAS EN PROVEEDOR
        while ($rowRecogidasDesdeProveedor = $bd->SigReg($resultRecogidasDesdeProveedor)):
            $arrIdCentroFisicoDestino[] = $rowRecogidasDesdeProveedor->ID_CENTRO_FISICO;
        endwhile;


        return $arrIdCentroFisicoDestino;
    }

    /**
     * @param $idOrdenTransporte ORDEN DE TRANSPORTE
     * @param $idSolicitudTransporte SOLICITUD DE TRANSPORTE PARA SOLO OBTENER LO RELACIONADO CON ELLA
     * @return array CON LOS IDS DE CF DESTINO DE LAS RECOGIDAS EN PROVEEDOR SIN RECEPCION
     * FUNCION PARA OBTENER LOS CENTROS FISICOS DE LAS RECOGIDAS EN PROVEEDOR SIN RECEPCION
     */
    function getCentrosFisicosDestinoRecogidasProveedorSinRecepcion($idOrdenTransporte, $idSolicitudTransporte = "")
    {

        global $bd;
        global $html;
        global $auxiliar;
        global $administrador;

        //CREAMOS EL ARRAY A DEVOLVER
        $arrIdCentroFisicoDestino = array();

        //BUSCO LA ORDEN DE TRANSPORTE
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowOrdenTransporte               = $bd->VerReg("ORDEN_TRANSPORTE", "ID_ORDEN_TRANSPORTE", $idOrdenTransporte, "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);

        if ($rowOrdenTransporte == false): //SI NO EXISTE , SALGO DE LA FUNCION
            return $arrIdCentroFisicoDestino;
        endif;

        //SI VIENE SOLICITUD, AÑADIREMOS EL FILTRO OBTENER SOLO ESAS RECOGIDAS
        $sqlWhereSolicitud = "";
        if ($idSolicitudTransporte != ""):
            $sqlWhereSolicitud = " AND E.ID_SOLICITUD_TRANSPORTE = $idSolicitudTransporte";
        endif;

        //BUSCAMOS LOS CF DESTINOS DE RECOGIDAS EN PROVEEDOR CON PEDIDO CONOCIDO NO RECEPCIONADAS
        $sqlRecogidasProveedorConPedido    = "SELECT DISTINCT CFD.ID_CENTRO_FISICO
                                        FROM EXPEDICION_PEDIDO_CONOCIDO EPC
                                        INNER JOIN EXPEDICION E ON E.ID_EXPEDICION = EPC.ID_EXPEDICION
                                        INNER JOIN ORDEN_TRANSPORTE OT ON OT.ID_ORDEN_TRANSPORTE = E.ID_ORDEN_TRANSPORTE
                                        INNER JOIN PEDIDO_ENTRADA_LINEA PEL ON PEL.ID_PEDIDO_ENTRADA_LINEA = EPC.ID_PEDIDO_ENTRADA_LINEA
                                        INNER JOIN ALMACEN AD ON AD.ID_ALMACEN = PEL.ID_ALMACEN
                                        INNER JOIN CENTRO_FISICO CFD ON CFD.ID_CENTRO_FISICO = AD.ID_CENTRO_FISICO
                                        WHERE OT.ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND EPC.BAJA=0 AND PEL.ESTADO<>'Recepcionada' $sqlWhereSolicitud";
        $resultRecogidasProveedorConPedido = $bd->ExecSQL($sqlRecogidasProveedorConPedido);


        //RECORRO LOS CF DE RECOGIDAS EN PROVEEDOR
        while ($rowRecogidasProveedorConPedido = $bd->SigReg($resultRecogidasProveedorConPedido)):
            $sqlMovimientosRecepcion    = "SELECT * FROM MOVIMIENTO_RECEPCION MR WHERE MR.ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND MR.ID_CENTRO_FISICO = $rowRecogidasProveedorConPedido->ID_CENTRO_FISICO AND BAJA=0";
            $resultMovimientosRecepcion = $bd->ExecSQL($sqlMovimientosRecepcion);
            //SI NO TIENE RECEPCION CREADA , LO AÑADO AL ARRAY DE RESPUESTA
            if ($bd->NumRegs($resultMovimientosRecepcion) == 0):
                $arrIdCentroFisicoDestino[] = $rowRecogidasProveedorConPedido->ID_CENTRO_FISICO;
            endif;
        endwhile;
        //FIN RECORRO LOS CF DE RECOGIDAS EN PROVEEDOR

        //RECORROR LOS CF
        $sqlRecogidasSinPedido    = "SELECT DISTINCT E.ID_CENTRO_FISICO
                                        FROM EXPEDICION E
                                        WHERE E.BAJA = 0 AND E.SUBTIPO_ORDEN_RECOGIDA='Sin Pedido Conocido' AND E.ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE $sqlWhereSolicitud";
        $resultRecogidasSinPedido = $bd->ExecSQL($sqlRecogidasSinPedido);

        while ($rowRecogidaSinPedido = $bd->SigReg($resultRecogidasSinPedido)):
            if ($rowRecogidaSinPedido->ID_CENTRO_FISICO != NULL):
                $sqlMovimientosRecepcion    = "SELECT * FROM MOVIMIENTO_RECEPCION MR WHERE MR.ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND MR.ID_CENTRO_FISICO = $rowRecogidaSinPedido->ID_CENTRO_FISICO AND BAJA=0";
                $resultMovimientosRecepcion = $bd->ExecSQL($sqlMovimientosRecepcion);
                //SI NO TIENE RECEPCION CREADA , LO AÑADO AL ARRAY DE RESPUESTA
                if ($bd->NumRegs($resultMovimientosRecepcion) == 0):
                    $arrIdCentroFisicoDestino[] = $rowRecogidaSinPedido->ID_CENTRO_FISICO;
                endif;
            endif;
        endwhile;

        //FIN RECORROR LOS CF


        return $arrIdCentroFisicoDestino;
    }

    /**
     * @param $idExpedicion ORDEN DE RECOGIDA
     * @return bool
     * Busca las sociedades de los Almacenes de origen para NO traslados que participan en la Recogida y si alguno tiene Gestion de Transporte devuelve true, si no, false
     */
    function RecogidaEnAlmacenOrigenConGestionTransporte($idExpedicion)
    {

        global $bd;
        global $html;
        global $auxiliar;
        global $administrador;

        //BUSCO LA ORDEN DE RECOGIDA
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowOrdenRecogida                 = $bd->VerReg("EXPEDICION", "ID_EXPEDICION", $idExpedicion, "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);

        //BUSCAMOS LOS ALMACENES DE ORIGEN
        $sqlAlmacenesOrigen    = "SELECT DISTINCT MSL.ID_ALMACEN
                              FROM MOVIMIENTO_SALIDA_LINEA MSL
                              INNER JOIN PEDIDO_SALIDA PS ON PS.ID_PEDIDO_SALIDA = MSL.ID_PEDIDO_SALIDA
                              WHERE MSL.ID_EXPEDICION = $rowOrdenRecogida->ID_EXPEDICION AND MSL.LINEA_ANULADA = 0 AND MSL.BAJA = 0";
        $resultAlmacenesOrigen = $bd->ExecSQL($sqlAlmacenesOrigen);

        while ($rowAlmacenesOrigen = $bd->SigReg($resultAlmacenesOrigen)):
            if ($rowAlmacenesOrigen->ID_ALMACEN != ""):
                //BUSCAMOS SI LA SOCIEDAD TIENE GESTION DE TRANSPORTE
                $sqlGestionTransporte    = "SELECT S.ID_SOCIEDAD
                                          FROM ALMACEN A
                                          INNER JOIN CENTRO C ON C.ID_CENTRO = A.ID_CENTRO
                                          INNER JOIN SOCIEDAD S ON S.ID_SOCIEDAD = C.ID_SOCIEDAD
                                          WHERE A.ID_ALMACEN = $rowAlmacenesOrigen->ID_ALMACEN AND S.GESTION_TRANSPORTE = 1";
                $resultGestionTransporte = $bd->ExecSQL($sqlGestionTransporte);

                //SI LA SOCIEDAD TIENE GESTION DE TRANSPORTE, DEVOLVEMOS TRUE
                if ($rowGestionTransporte = $bd->SigReg($resultGestionTransporte)):
                    return true;
                endif;

            endif;
        endwhile;


        //BUSCAMOS LOS ALMACENES DE DESTINO PARA TRASLADOS
        $sqlAlmacenesDestino    = "SELECT DISTINCT MSL.ID_ALMACEN_DESTINO
                              FROM MOVIMIENTO_SALIDA_LINEA MSL
                              INNER JOIN PEDIDO_SALIDA PS ON PS.ID_PEDIDO_SALIDA = MSL.ID_PEDIDO_SALIDA
                              WHERE PS.TIPO_PEDIDO = 'Traslado' AND MSL.ID_EXPEDICION = $rowOrdenRecogida->ID_EXPEDICION AND MSL.LINEA_ANULADA = 0 AND MSL.BAJA = 0";
        $resultAlmacenesDestino = $bd->ExecSQL($sqlAlmacenesDestino);

        while ($rowAlmacenesDestino = $bd->SigReg($resultAlmacenesDestino)):
            if ($rowAlmacenesDestino->ID_ALMACEN_DESTINO != ""):
                //BUSCAMOS SI LA SOCIEDAD TIENE GESTION DE TRANSPORTE
                $sqlGestionTransporte    = "SELECT S.ID_SOCIEDAD
                                          FROM ALMACEN A
                                          INNER JOIN CENTRO C ON C.ID_CENTRO = A.ID_CENTRO
                                          INNER JOIN SOCIEDAD S ON S.ID_SOCIEDAD = C.ID_SOCIEDAD
                                          WHERE A.ID_ALMACEN = $rowAlmacenesDestino->ID_ALMACEN_DESTINO AND S.GESTION_TRANSPORTE = 1";
                $resultGestionTransporte = $bd->ExecSQL($sqlGestionTransporte);

                //SI LA SOCIEDAD TIENE GESTION DE TRANSPORTE, DEVOLVEMOS TRUE
                if ($rowGestionTransporte = $bd->SigReg($resultGestionTransporte)):
                    return true;
                endif;

            endif;
        endwhile;

        //SI NO HEMOS SALIDO AUN DE LA FUNCION, ES QUE NINGUNA SOCIEDAD IMPLICADA TIENE GESTION DE TRANSPORTE Y DEVOLVEMOS FALSE
        return false;

    }


    /**
     * @param $idExpedicion ORDEN DE RECOGIDA
     * @return bool
     * Busca las sociedades de los Almacenes de destino para TRASLADOS que participan en la Recogida y si alguno tiene Gestion de Transporte devuelve true, si no, false
     */
    function RecogidaEnAlmacenDestinoConGestionTransporte($idExpedicion)
    {

        global $bd;
        global $html;
        global $auxiliar;
        global $administrador;

        //BUSCO LA ORDEN DE RECOGIDA
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowOrdenRecogida                 = $bd->VerReg("EXPEDICION", "ID_EXPEDICION", $idExpedicion, "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);


//        //BUSCAMOS EL ALMACEN DE MOURA Y EL ALMACEN 1082 DEL CENTRO 1132, ESTE ALMACEN NO CONTARA COMO DESTINO CON GESTION TRANSPORTE
//        $rowMoura  = $bd->VerReg("ALMACEN", "REFERENCIA", "1002","No");
//        $row1082 = $bd->VerReg("ALMACEN", "REFERENCIA", "1082","No");


        //BUSCAMOS LOS ALMACENES DE DESTINO PARA TRASLADOS
//        $sqlAlmacenesDestino    = "SELECT DISTINCT MSL.ID_ALMACEN_DESTINO
//                                      FROM MOVIMIENTO_SALIDA_LINEA MSL
//                                      INNER JOIN PEDIDO_SALIDA PS ON PS.ID_PEDIDO_SALIDA = MSL.ID_PEDIDO_SALIDA
//                                      WHERE PS.TIPO_PEDIDO = 'Traslado' AND MSL.ID_EXPEDICION = $rowOrdenRecogida->ID_EXPEDICION AND MSL.LINEA_ANULADA = 0 AND MSL.BAJA = 0 AND MSL.ID_ALMACEN_DESTINO <> '$rowMoura->ID_ALMACEN' AND MSL.ID_ALMACEN_DESTINO <> '$row1082->ID_ALMACEN' ";
        $sqlAlmacenesDestino    = "SELECT DISTINCT MSL.ID_ALMACEN_DESTINO
                                      FROM MOVIMIENTO_SALIDA_LINEA MSL
                                      INNER JOIN PEDIDO_SALIDA PS ON PS.ID_PEDIDO_SALIDA = MSL.ID_PEDIDO_SALIDA
                                      INNER JOIN ALMACEN A ON A.ID_ALMACEN = MSL.ID_ALMACEN_DESTINO
                                      INNER JOIN CENTRO C ON C.ID_CENTRO = A.ID_CENTRO
                                      WHERE PS.TIPO_PEDIDO = 'Traslado' AND MSL.ID_EXPEDICION = $rowOrdenRecogida->ID_EXPEDICION AND MSL.LINEA_ANULADA = 0 AND MSL.BAJA = 0 AND C.LOGISTICA_INVERSA_DELEGADA = 0 ";
        $resultAlmacenesDestino = $bd->ExecSQL($sqlAlmacenesDestino);

        while ($rowAlmacenesDestino = $bd->SigReg($resultAlmacenesDestino)):
            if ($rowAlmacenesDestino->ID_ALMACEN_DESTINO != ""):
                //BUSCAMOS SI LA SOCIEDAD TIENE GESTION DE TRANSPORTE
                $sqlGestionTransporte    = "SELECT S.ID_SOCIEDAD
                                              FROM ALMACEN A
                                              INNER JOIN CENTRO C ON C.ID_CENTRO = A.ID_CENTRO
                                              INNER JOIN SOCIEDAD S ON S.ID_SOCIEDAD = C.ID_SOCIEDAD
                                              WHERE A.ID_ALMACEN = $rowAlmacenesDestino->ID_ALMACEN_DESTINO AND S.GESTION_TRANSPORTE = 1";
                $resultGestionTransporte = $bd->ExecSQL($sqlGestionTransporte);

                //SI LA SOCIEDAD TIENE GESTION DE TRANSPORTE, DEVOLVEMOS TRUE
                if ($rowGestionTransporte = $bd->SigReg($resultGestionTransporte)):
                    return true;
                endif;
            endif;
        endwhile;

        //SI NO HEMOS SALIDO AUN DE LA FUNCION, ES QUE NINGUNA SOCIEDAD IMPLICADA TIENE GESTION DE TRANSPORTE Y DEVOLVEMOS FALSE
        return false;

    }

    /**
     * @param $idOrdenTransporte ORDEN DE TRANSPORTE
     * @return bool
     * Busca en las recogidas y si hay Operaciones en parque, Operaciones fuera de sistema o Envios y componentes a proveedor, devuelve true, si no, false
     */
    function TransporteConZTL($idOrdenTransporte)
    {


        global $bd;
        global $html;
        global $auxiliar;
        global $administrador;

        //BUSCO LA ORDEN DE RECOGIDA
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowOrdenTransporte               = $bd->VerReg("ORDEN_TRANSPORTE", "ID_ORDEN_TRANSPORTE", $idOrdenTransporte, "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);

        //BUSCO SI TIENE RECOGIDAS CON ENVIOS Y COMPONENTES A PROVEEDOR (ZTLI)
        $sqlPedidoZTLI = "SELECT E.ID_EXPEDICION
                           FROM MOVIMIENTO_SALIDA_LINEA MSL
                           INNER JOIN EXPEDICION E ON E.ID_EXPEDICION = MSL.ID_EXPEDICION
                           INNER JOIN PEDIDO_SALIDA PS ON PS.ID_PEDIDO_SALIDA = MSL.ID_PEDIDO_SALIDA
                           INNER JOIN PEDIDO_SALIDA_LINEA PSL ON PSL.ID_PEDIDO_SALIDA_LINEA = MSL.ID_PEDIDO_SALIDA_LINEA
                           WHERE E.ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND MSL.LINEA_ANULADA = 0 AND MSL.BAJA = 0 AND E.BAJA = 0
                           AND (PS.TIPO_PEDIDO = 'Componentes a Proveedor' OR PS.TIPO_PEDIDO = 'Material Estropeado a Proveedor' OR PS.TIPO_PEDIDO = 'Material Estropeado Entre Proveedores' OR PS.TIPO_PEDIDO = 'Devolución a Proveedor')";

        $resultPedidoZTLI = $bd->ExecSQL($sqlPedidoZTLI);

        //BUSCO LAS RECOGIDAS FUERA DE SISTEMA Y OPERACIONES EN PARQUE
        $sqlPedidosZTLG = "SELECT E.ID_EXPEDICION
                            FROM EXPEDICION E
                            WHERE E.ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND (E.TIPO_ORDEN_RECOGIDA = 'Operaciones en Parque' OR E.TIPO_ORDEN_RECOGIDA = 'Operaciones fuera de Sistema') AND E.BAJA=0";

        $resultPedidoZTLG = $bd->ExecSQL($sqlPedidosZTLG);

        //BUSCO SI EXISTEN PEDIDOS ZTL
        $sqlPedidosCreados = "SELECT PE.ID_PEDIDO_ENTRADA
                                   FROM PEDIDO_ENTRADA PE
                                   INNER JOIN ORDEN_TRANSPORTE OT ON OT.ID_ORDEN_TRANSPORTE = PE.ID_ORDEN_TRANSPORTE
                                   WHERE PE.ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND PE.BAJA = 0";

        $resultPedidosCreados = $bd->ExecSQL($sqlPedidosCreados);

        //SI TIENE RECOGIDAS DE ALGUNO DE LOS TIPOS QUE NECESITAN ZTL DEVUELVO TRUE , SI NO,  FALSE
        if (($bd->NumRegs($resultPedidoZTLI) > 0) || ($bd->NumRegs($resultPedidoZTLG) > 0) || ($bd->NumRegs($resultPedidosCreados) > 0) || ($rowOrdenTransporte->MODELO_TRANSPORTE == 'Segundo')):
            return true;
        else:
            return false;
        endif;
    }

    /**
     * @param $idOrdenTransporte ORDEN DE TRANSPORTE
     * @return bool
     * Busca en las recogidas y si SOLO hay Operaciones en parque, Operaciones fuera de sistema o Envios y componentes a proveedor, devuelve true, si no, false
     * Las recogidas cuyas todas sus lineas sean de no transmision a SAP o gratuitas no contarán
     */
    function NumeroRecogidasSinZTL($idOrdenTransporte)
    {

        global $bd;
        global $html;
        global $auxiliar;
        global $administrador;

        //BUSCO LA ORDEN DE RECOGIDA
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowOrdenTransporte               = $bd->VerReg("ORDEN_TRANSPORTE", "ID_ORDEN_TRANSPORTE", $idOrdenTransporte, "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);

        //BUSCO LAS RECOGIDAS FUERA DE SISTEMA Y OPERACIONES EN PARQUE
        $sqlPedidosSinZTL = "SELECT E.ID_EXPEDICION
                            FROM EXPEDICION E
                            WHERE E.ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND
                            (E.TIPO_ORDEN_RECOGIDA <> 'Operaciones en Parque' AND E.TIPO_ORDEN_RECOGIDA <> 'Operaciones fuera de Sistema' AND E.SUBTIPO_ORDEN_RECOGIDA <> 'Material Estropeado a Proveedor' AND E.SUBTIPO_ORDEN_RECOGIDA <> 'Material Estropeado Entre Proveedores' AND E.SUBTIPO_ORDEN_RECOGIDA <> 'Componentes a Proveedor' AND E.SUBTIPO_ORDEN_RECOGIDA <> 'Devoluciones a Proveedor' ) AND E.BAJA=0";

        $resultPedidosSinZTL = $bd->ExecSQL($sqlPedidosSinZTL);

        //VARIABLE PARA CONTROLAR EL NUMERO DE RECOGIDAS EN LAS QUE TODAS SUS LINEAS SON DE NO TRANSMITIR A SAP
        $numRecogidasNoTransmitir = 0;

        //RECORRO LAS RECOGIDAS POR SI ALGUNA TODAS SUS LINEAS SON LINEAS QUE NO SE TRANSMITEN A SAP
        while ($rowExpedicion = $bd->SigReg($resultPedidosSinZTL)):
            //CALCULO EL NUMERO DE LINEAS DE LA RECOGIDA
            $sqlNumLineas    = "SELECT *
                                FROM MOVIMIENTO_SALIDA_LINEA MSL
                                INNER JOIN MATERIAL M ON M.ID_MATERIAL = MSL.ID_MATERIAL
                                INNER JOIN PEDIDO_SALIDA PS ON PS.ID_PEDIDO_SALIDA = MSL.ID_PEDIDO_SALIDA
                                INNER JOIN PEDIDO_SALIDA_LINEA PSL ON PSL.ID_PEDIDO_SALIDA_LINEA = MSL.ID_PEDIDO_SALIDA_LINEA
                                WHERE MSL.ID_EXPEDICION = $rowExpedicion->ID_EXPEDICION AND MSL.LINEA_ANULADA = 0 AND MSL.BAJA = 0";
            $resultNumLineas = $bd->ExecSQL($sqlNumLineas);

            //CALCULO EL NUMERO DE LINEAS DE LA RECOGIDA A NO TRANSMITIR
            $sqlNumLineasNoTransmitir    = "SELECT *
                                            FROM MOVIMIENTO_SALIDA_LINEA MSL
                                            INNER JOIN MATERIAL M ON M.ID_MATERIAL = MSL.ID_MATERIAL
                                            INNER JOIN PEDIDO_SALIDA PS ON PS.ID_PEDIDO_SALIDA = MSL.ID_PEDIDO_SALIDA
                                            INNER JOIN PEDIDO_SALIDA_LINEA PSL ON PSL.ID_PEDIDO_SALIDA_LINEA = MSL.ID_PEDIDO_SALIDA_LINEA
                                            WHERE MSL.ID_EXPEDICION = $rowExpedicion->ID_EXPEDICION AND MSL.LINEA_ANULADA = 0 AND MSL.BAJA = 0 AND (PSL.LINEA_NO_TRANSMITIR_SAP = 1 OR PSL.POSICION_GRATUITA = 1)";
            $resultNumLineasNoTransmitir = $bd->ExecSQL($sqlNumLineasNoTransmitir);

            //SI TODAS SUS LINEAS NO HAY QUE TRANSMITIRLAS ESTA RECOGIDA NO CONTARA
            if (($bd->NumRegs($resultNumLineas) == $bd->NumRegs($resultNumLineasNoTransmitir)) && ($bd->NumRegs($resultNumLineas) > 0)):
                $numRecogidasNoTransmitir = $numRecogidasNoTransmitir + 1;
            endif;
        endwhile;

        $numPedidosSinZTL = $bd->NumRegs($resultPedidosSinZTL) - $numRecogidasNoTransmitir;


        //ADEMAS, COMPROBAMOS QUE NO HAY NINGUNA DE ESAS RECOGIDAS YA ANULADA UNA VEZ QUE SE HA ENVIADO EL TRANSPORTE (SE GUARDAN ESAS LINEAS EN ORDEN_TRANSPORTE_LINEA_ANULADA HASTA QUE SE ANULAN GASTOS DE NUEVO)
        $sqlBajaPedidosSinZTL    = "SELECT E.ID_EXPEDICION
                                    FROM EXPEDICION E
                                    INNER JOIN ORDEN_TRANSPORTE_LINEA_ANULADA OTA ON OTA.ID_EXPEDICION = E.ID_EXPEDICION
                                    WHERE OTA.ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND
                                    (E.TIPO_ORDEN_RECOGIDA <> 'Operaciones en Parque' AND E.TIPO_ORDEN_RECOGIDA <> 'Operaciones fuera de Sistema' AND E.SUBTIPO_ORDEN_RECOGIDA <> 'Material Estropeado a Proveedor' AND E.SUBTIPO_ORDEN_RECOGIDA <> 'Material Estropeado Entre Proveedores' AND E.SUBTIPO_ORDEN_RECOGIDA <> 'Componentes a Proveedor' AND E.SUBTIPO_ORDEN_RECOGIDA <> 'Devoluciones a Proveedor' ) AND OTA.BAJA=0 AND E.BAJA=1";
        $resultBajaPedidosSinZTL = $bd->ExecSQL($sqlBajaPedidosSinZTL);
        $numBajaPedidoSinZTL     = $bd->NumRegs($resultBajaPedidosSinZTL);

        $numTotal = ($numPedidosSinZTL + $numBajaPedidoSinZTL);


        return $numTotal;
    }

    /**
     * @param $idOrdenTransporte
     * DEVUELVE SI EL TRANSPORTE SOLO ESTA COMPUESTO POR LINEAS GRATUITAS
     */
    function TransporteGratuito($idOrdenTransporte)
    {

        global $bd;
        global $html;
        global $auxiliar;
        global $administrador;

        //BUSCO LA ORDEN DE RECOGIDA
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowOrdenTransporte               = $bd->VerReg("ORDEN_TRANSPORTE", "ID_ORDEN_TRANSPORTE", $idOrdenTransporte, "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);

        $transporteGratuito = 0;

        //COMPROBAMOS QUE EL TRANSPORTE SOLO TIENE RECOGIDAS EN PROVEEDOR ( SOLO LOS PEDIDOS DE COMPRA PUEDEN SER GRATUITOS)
        $numRecogidasNoProveedor = $bd->NumRegsTabla("EXPEDICION", "ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE  AND BAJA = 0 AND ((SUBTIPO_ORDEN_RECOGIDA <> 'Con Pedido Conocido' AND SUBTIPO_ORDEN_RECOGIDA <> 'Sin Pedido Conocido') OR (SUBTIPO_ORDEN_RECOGIDA IS NULL))");

        //BUSCAMOS LAS RECOGIDAS CON Y SIN PEDIDO CONOCIDO
        $sqlRecogidasProveedor    = "SELECT E.ID_EXPEDICION, E.SUBTIPO_ORDEN_RECOGIDA
                                        FROM EXPEDICION E
                                        WHERE E.ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND
                                        (E.SUBTIPO_ORDEN_RECOGIDA = 'Con Pedido Conocido' OR  E.SUBTIPO_ORDEN_RECOGIDA = 'Sin Pedido Conocido') AND E.BAJA=0";
        $resultRecogidasProveedor = $bd->ExecSQL($sqlRecogidasProveedor);

        //SI SOLO HAY RECOGIDAS EN PROVEEDOR
        if (($numRecogidasNoProveedor == 0) && ($bd->NumRegs($resultRecogidasProveedor) > 0)):

            //RECORREMOS LAS RECOGIDAS EN PROVEEDOR
            while ($rowRecogidaProveedor = $bd->SigReg($resultRecogidasProveedor)):
                if ($rowRecogidaProveedor->SUBTIPO_ORDEN_RECOGIDA == "Con Pedido Conocido"):
                    //PEDIDO CONOCIDO CON POSICIONES NO GRATUITAS
                    $sqlPedidoEntrada = "SELECT DISTINCT  PEL.ID_PEDIDO_ENTRADA_LINEA
                            FROM EXPEDICION_PEDIDO_CONOCIDO EPC
                            INNER JOIN PEDIDO_ENTRADA PE ON PE.ID_PEDIDO_ENTRADA = EPC.ID_PEDIDO_ENTRADA
                            INNER JOIN PEDIDO_ENTRADA_LINEA PEL ON PEL.ID_PEDIDO_ENTRADA_LINEA = EPC.ID_PEDIDO_ENTRADA_LINEA
                            WHERE EPC.ID_EXPEDICION = $rowRecogidaProveedor->ID_EXPEDICION AND EPC.BAJA = 0 AND PEL.POSICION_GRATUITA = 0";

                elseif ($rowRecogidaProveedor->SUBTIPO_ORDEN_RECOGIDA == "Sin Pedido Conocido"):
                    //MOVIMIENTO ENTRADA DE PEDIDOS CON POSICIONES NO GRATUITAS
                    $sqlPedidoEntrada = "SELECT DISTINCT PEL.ID_PEDIDO_ENTRADA_LINEA
                            FROM MOVIMIENTO_ENTRADA ME
                            INNER JOIN MOVIMIENTO_ENTRADA_LINEA MEL ON MEL.ID_MOVIMIENTO_ENTRADA=ME.ID_MOVIMIENTO_ENTRADA
                            INNER JOIN PEDIDO_ENTRADA PE ON PE.ID_PEDIDO_ENTRADA=MEL.ID_PEDIDO
                            INNER JOIN PEDIDO_ENTRADA_LINEA PEL ON PEL.ID_PEDIDO_ENTRADA_LINEA=MEL.ID_PEDIDO_LINEA
                            WHERE MEL.ID_EXPEDICION_ENTREGA = $rowRecogidaProveedor->ID_EXPEDICION AND ME.BAJA = 0 AND PEL.POSICION_GRATUITA = 0";
                endif;

                $resultPedidoEntrada = $bd->ExecSQL($sqlPedidoEntrada);
                //SALIMOS DE LA FUNCION SI HEMOS ENCONTRADO UNA POSICION NO GRATUITA
                if ($bd->NumRegs($resultPedidoEntrada) > 0):
                    return 0;
                endif;
            endwhile;

            //SI HEMOS LLEGADO A ESTE PUNTO ES QUE TODAS SON GRATUITAS
            $transporteGratuito = 1;

        endif;//FIN SOLO HAY RECOGIDAS EN PROVEEDOR


        return $transporteGratuito;
    }

    /**
     * @param $idOrdenTransporte
     * DEVUELVE SI EL TRANSPORTE SOLO ESTA COMPUESTO POR LINEAS GRATUITAS Y LINEAS ZTL
     */
    function TransporteGratuitoZTL($idOrdenTransporte)
    {

        global $bd;
        global $html;
        global $auxiliar;
        global $administrador;

        //BUSCO LA ORDEN DE RECOGIDA
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowOrdenTransporte               = $bd->VerReg("ORDEN_TRANSPORTE", "ID_ORDEN_TRANSPORTE", $idOrdenTransporte, "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);

        $transporteGratuito = 0;


        //BUSCO LAS RECOGIDAS DISTINTAS DE ZTL Y DE PROVEEDOR
        $sqlPedidosSinZTLNiProv    = "SELECT E.ID_EXPEDICION
                            FROM EXPEDICION E
                            WHERE E.ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND
                            E.BAJA = 0 AND E.TIPO_ORDEN_RECOGIDA <> 'Operaciones en Parque' AND E.TIPO_ORDEN_RECOGIDA <> 'Operaciones fuera de Sistema' AND
                             ((E.SUBTIPO_ORDEN_RECOGIDA <> 'Material Estropeado a Proveedor' AND E.SUBTIPO_ORDEN_RECOGIDA <> 'Material Estropeado Entre Proveedores' AND E.SUBTIPO_ORDEN_RECOGIDA <> 'Componentes a Proveedor' AND E.SUBTIPO_ORDEN_RECOGIDA <> 'Devoluciones a Proveedor' AND E.SUBTIPO_ORDEN_RECOGIDA <> 'Con Pedido Conocido' AND E.SUBTIPO_ORDEN_RECOGIDA <> 'Sin Pedido Conocido') OR (E.SUBTIPO_ORDEN_RECOGIDA IS NULL))";
        $resultPedidosSinZTLNiProv = $bd->ExecSQL($sqlPedidosSinZTLNiProv);

        //BUSCAMOS LAS RECOGIDAS CON Y SIN PEDIDO CONOCIDO
        $sqlRecogidasProveedor    = "SELECT E.ID_EXPEDICION, E.SUBTIPO_ORDEN_RECOGIDA
                                        FROM EXPEDICION E
                                        WHERE E.ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND
                                        (E.SUBTIPO_ORDEN_RECOGIDA = 'Con Pedido Conocido' OR  E.SUBTIPO_ORDEN_RECOGIDA = 'Sin Pedido Conocido') AND E.BAJA=0";
        $resultRecogidasProveedor = $bd->ExecSQL($sqlRecogidasProveedor);

        //SI SOLO HAY RECOGIDAS EN PROVEEDOR Y DE ZTL
        if (($bd->NumRegs($resultPedidosSinZTLNiProv) == 0) && ($bd->NumRegs($resultRecogidasProveedor) > 0)):

            //RECORREMOS LAS RECOGIDAS EN PROVEEDOR
            while ($rowRecogidaProveedor = $bd->SigReg($resultRecogidasProveedor)):
                if ($rowRecogidaProveedor->SUBTIPO_ORDEN_RECOGIDA == "Con Pedido Conocido"):
                    //PEDIDO CONOCIDO CON POSICIONES NO GRATUITAS
                    $sqlPedidoEntrada = "SELECT DISTINCT  PEL.ID_PEDIDO_ENTRADA_LINEA
                            FROM EXPEDICION_PEDIDO_CONOCIDO EPC
                            INNER JOIN PEDIDO_ENTRADA PE ON PE.ID_PEDIDO_ENTRADA = EPC.ID_PEDIDO_ENTRADA
                            INNER JOIN PEDIDO_ENTRADA_LINEA PEL ON PEL.ID_PEDIDO_ENTRADA_LINEA = EPC.ID_PEDIDO_ENTRADA_LINEA
                            WHERE EPC.ID_EXPEDICION = $rowRecogidaProveedor->ID_EXPEDICION AND EPC.BAJA = 0 AND PEL.POSICION_GRATUITA = 0";

                elseif ($rowRecogidaProveedor->SUBTIPO_ORDEN_RECOGIDA == "Sin Pedido Conocido"):
                    //MOVIMIENTO ENTRADA DE PEDIDOS CON POSICIONES NO GRATUITAS
                    $sqlPedidoEntrada = "SELECT DISTINCT PEL.ID_PEDIDO_ENTRADA_LINEA
                            FROM MOVIMIENTO_ENTRADA ME
                            INNER JOIN MOVIMIENTO_ENTRADA_LINEA MEL ON MEL.ID_MOVIMIENTO_ENTRADA=ME.ID_MOVIMIENTO_ENTRADA
                            INNER JOIN PEDIDO_ENTRADA PE ON PE.ID_PEDIDO_ENTRADA=MEL.ID_PEDIDO
                            INNER JOIN PEDIDO_ENTRADA_LINEA PEL ON PEL.ID_PEDIDO_ENTRADA_LINEA=MEL.ID_PEDIDO_LINEA
                            WHERE MEL.ID_EXPEDICION_ENTREGA = $rowRecogidaProveedor->ID_EXPEDICION AND ME.BAJA = 0 AND PEL.POSICION_GRATUITA = 0";
                endif;

                $resultPedidoEntrada = $bd->ExecSQL($sqlPedidoEntrada);
                //SALIMOS DE LA FUNCION SI HEMOS ENCONTRADO UNA POSICION NO GRATUITA
                if ($bd->NumRegs($resultPedidoEntrada) > 0):
                    return 0;
                endif;
            endwhile;

            //SI HEMOS LLEGADO A ESTE PUNTO ES QUE TODAS SON GRATUITAS
            $transporteGratuito = 1;

        endif;//FIN SOLO HAY RECOGIDAS EN PROVEEDOR


        return $transporteGratuito;
    }


    /**
     * @param $idOrdenTransporte ORDEN DE TRANSPORTE
     * @return ESTADO_INTERFACES de la Orden de Transporte(Creada,Recogidas en Transmision,Recogidas Transmitidas)
     * Segun el estado de las recogidas en almacén, devuelve cual es el estado de las interfaces de la Orden de Transporte
     */
    function EstadoTransporteSegunRecogidas($idOrdenTransporte)
    {

        global $bd;
        global $html;
        global $auxiliar;
        global $administrador;

        //BUSCO LA ORDEN DE RECOGIDA
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowOrdenTransporte               = $bd->VerReg("ORDEN_TRANSPORTE", "ID_ORDEN_TRANSPORTE", $idOrdenTransporte, "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);

        $numRecogidasAlmacen              = $bd->NumRegsTabla("EXPEDICION", "ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND TIPO_ORDEN_RECOGIDA = 'Recogida en Almacen' AND BAJA = 0");
        $numRecogidasAlmacenCreadas       = $bd->NumRegsTabla("EXPEDICION", "ESTADO='Creada' AND ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND TIPO_ORDEN_RECOGIDA = 'Recogida en Almacen' AND BAJA = 0");
        $numRecogidasAlmacenEnTransmision = $bd->NumRegsTabla("EXPEDICION", "ESTADO='En Transmision' AND ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND TIPO_ORDEN_RECOGIDA = 'Recogida en Almacen' AND BAJA = 0");

        if ($numRecogidasAlmacen == $numRecogidasAlmacenCreadas)://SI TODAS ESTAN CREADAS, EL ESTADO ES CREADA
            return 'Creada';

        elseif ($numRecogidasAlmacenCreadas == 0 && $numRecogidasAlmacenEnTransmision == 0): //SI NO HAY CREADAS NI EN TRANSMISION, ESTAN TRANSMITIDAS
            return 'Recogidas Transmitidas';

        else://SI NO, EN TRANSMISION
            return 'Recogidas en Transmision';

        endif;
    }

    /**
     * @param $idOrdenTransporte
     * @return TRUE si el Transporte tiene que tener Entrega Entrante, FALSE si no
     * Busca si las Recogidas del Transporte tienen pedidos que sean Relevantes para Entrega entrate, y si encuentra alguno devuelve true, si no, false
     */
    function TransporteConEntregaEntrante($idOrdenTransporte)
    {
        global $bd;
        global $html;
        global $auxiliar;
        global $administrador;

        //BUSCO LA ORDEN DE RECOGIDA
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowOrdenTransporte               = $bd->VerReg("ORDEN_TRANSPORTE", "ID_ORDEN_TRANSPORTE", $idOrdenTransporte, "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);

        //SI YA TIENE LA FOTO ENVIADA, CONFIRMAMOS QUE TIENE ENTREGA ENTRANTE
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowEntregaEntrante               = $bd->VerReg("EXPEDICION_ENTREGA_ENTRANTE", "ID_ORDEN_TRANSPORTE", $rowOrdenTransporte->ID_ORDEN_TRANSPORTE, "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);
        if ($rowEntregaEntrante != false):
            return true;
        endif;

        //BUSCO LAS RECOGIDAS  DE LA ORDEN DE TRANSPORTE
        $sqlRecogidas    = "SELECT E.ID_ORDEN_TRANSPORTE, E.ID_EXPEDICION, E.TIPO_ORDEN_RECOGIDA, E.SUBTIPO_ORDEN_RECOGIDA, E.DESCRIPCION_ORDEN_TRANSPORTE, E.ESTADO, E.FECHA, E.HORA
                            FROM EXPEDICION E
                            INNER JOIN ORDEN_TRANSPORTE OT ON E.ID_ORDEN_TRANSPORTE = OT.ID_ORDEN_TRANSPORTE
                            WHERE E.ID_ORDEN_TRANSPORTE =  $rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND E.BAJA = 0";
        $resultRecogidas = $bd->ExecSQL($sqlRecogidas);

        //SI TIENE RECOGIDAS
        if ($bd->NumRegs($resultRecogidas) > 0):
            while ($rowOrdenRecogida = $bd->SigReg($resultRecogidas)):

                //DEPENDIENDO DEL TIPO Y SUBTIPO BUSCAMOS SUS PEDIDOS
                //RECOGIDAS EN ALMACEN DEBEN ESTAR TRANSMITIDAS A SAP
                if (($rowOrdenRecogida->TIPO_ORDEN_RECOGIDA == "Recogida en Almacen")):

                    //PEDIDO DE SALIDA
                    $sqlPedidos    = "SELECT PS.ID_PEDIDO_SALIDA AS ID_PEDIDO_AGRUPADO, PS.PEDIDO_SAP
                                      FROM MOVIMIENTO_SALIDA_LINEA MSL
                                      INNER JOIN PEDIDO_SALIDA PS ON MSL.ID_PEDIDO_SALIDA = PS.ID_PEDIDO_SALIDA
                                      INNER JOIN PEDIDO_SALIDA_LINEA PSL ON MSL.ID_PEDIDO_SALIDA_LINEA = PSL.ID_PEDIDO_SALIDA_LINEA
                                      WHERE MSL.ID_EXPEDICION = $rowOrdenRecogida->ID_EXPEDICION AND MSL.BAJA = 0 AND MSL.LINEA_ANULADA = 0 AND PSL.RELEVANTE_ENTREGA_ENTRANTE = 1
                                      GROUP BY PS.ID_PEDIDO_SALIDA";
                    $resultPedidos = $bd->ExecSQL($sqlPedidos);

                elseif ($rowOrdenRecogida->TIPO_ORDEN_RECOGIDA == "Recogida en Proveedor"):
                    if ($rowOrdenRecogida->SUBTIPO_ORDEN_RECOGIDA == "Sin Pedido Conocido")://BUSCAMOS LOS PEDIDOS DE LOS MOVIMIENTOS
                        //PEDIDO DE ENTRADA
                        $sqlPedidos    = "SELECT PE.ID_PEDIDO_ENTRADA AS ID_PEDIDO_AGRUPADO, PE.PEDIDO_SAP
                                          FROM MOVIMIENTO_ENTRADA ME
                                          INNER JOIN MOVIMIENTO_ENTRADA_LINEA MEL ON MEL.ID_MOVIMIENTO_ENTRADA = ME.ID_MOVIMIENTO_ENTRADA
                                          INNER JOIN PEDIDO_ENTRADA PE ON MEL.ID_PEDIDO = PE.ID_PEDIDO_ENTRADA
                                          INNER JOIN PEDIDO_ENTRADA_LINEA PEL ON MEL.ID_PEDIDO_LINEA = PEL.ID_PEDIDO_ENTRADA_LINEA
                                          WHERE MEL.ID_EXPEDICION_ENTREGA = $rowOrdenRecogida->ID_EXPEDICION AND MEL.BAJA = 0 AND MEL.LINEA_ANULADA = 0 AND PEL.RELEVANTE_ENTREGA_ENTRANTE = 1
                                          GROUP BY PE.ID_PEDIDO_ENTRADA";
                        $resultPedidos = $bd->ExecSQL($sqlPedidos);
                    elseif ($rowOrdenRecogida->SUBTIPO_ORDEN_RECOGIDA == "Con Pedido Conocido")://BUSCAMOS LOS PEDIDOS CONOCIDOS CREADOS

                        $sqlPedidos    = "SELECT PE.ID_PEDIDO_ENTRADA AS ID_PEDIDO_AGRUPADO, PE.PEDIDO_SAP
                                          FROM EXPEDICION_PEDIDO_CONOCIDO EPC
                                          INNER JOIN PEDIDO_ENTRADA PE ON EPC.ID_PEDIDO_ENTRADA = PE.ID_PEDIDO_ENTRADA
                                          INNER JOIN PEDIDO_ENTRADA_LINEA PEL ON EPC.ID_PEDIDO_ENTRADA_LINEA = PEL.ID_PEDIDO_ENTRADA_LINEA
                                          WHERE EPC.ID_EXPEDICION = $rowOrdenRecogida->ID_EXPEDICION AND PEL.RELEVANTE_ENTREGA_ENTRANTE = 1 AND EPC.BAJA=0
                                          GROUP BY PE.ID_PEDIDO_ENTRADA";
                        $resultPedidos = $bd->ExecSQL($sqlPedidos);
                    elseif ($rowOrdenRecogida->SUBTIPO_ORDEN_RECOGIDA == "Retorno Material Estropeado desde Proveedor")://NO TIENEN PEDIDOS
                        continue;

                    endif;

                elseif ($rowOrdenRecogida->TIPO_ORDEN_RECOGIDA == "Operaciones en Parque"):
                    continue;//PEDIDOS ZTLG , NO SON RELEVANTES
                elseif ($rowOrdenRecogida->TIPO_ORDEN_RECOGIDA == "Operaciones fuera de Sistema"):
                    continue;//PEDIDOS ZTLG , NO SON RELEVANTES
                else:
                    continue;
                endif;

                if ($bd->NumRegs($resultPedidos) > 0)://SI TIENE PEDIDOS RELEVANTES
                    //DEVOLVEMOS QUE SI TIENE ENTREGA ENTRANTE
                    return true;
                endif;

            endwhile;

        else://SI NO TIENE RECOGIDAS
            //BUSCAMOS SI ESTA ASOCIADA A UNA RECEPCION(GENERARÁ RECOGIDAS SIN PEDIDO CONOCIDO), Y SI LO ESTÁ DEVOLVEMOS TRUE
            $sqlRecepcion    = "SELECT ID_MOVIMIENTO_RECEPCION, ID_CENTRO_FISICO, PESO
                                FROM MOVIMIENTO_RECEPCION
                                WHERE ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND BAJA = 0";
            $resultRecepcion = $bd->ExecSQL($sqlRecepcion);

            if ($bd->NumRegs($resultRecepcion) > 0):
                //DEVOLVEMOS QUE SI TIENE ENTREGA ENTRANTE
                return true;
            endif;

        endif;

        //SI NO HEMOS ENCONTRADO PEDIDOS RELEVANTES, DEVOLVEMOS FALSE
        return false;
    }

    /**
     ** @param $idExpedicion -> ORDEN DE RECOGIDA
     * @param $idPedidoSalidaLinea -> LINEA DE PEDIDO DE SALIDA
     * @return array -> ARRAY QUE CONTIENE LA ENTREGA SALIENTE Y LA POSICION O GUIONES SI NO SE ENCUENTRA
     * @throws Exception
     */
    function getEntregaSaliente($idExpedicion, $idPedidoSalidaLinea, $expSAP = '')
    {
        global $bd;

        //VALOR A DEVOLVER
        $entregaSaliente                            = array();
        $entregaSaliente["NumeroEntregaSaliente"]   = "-";
        $entregaSaliente["PosicionEntregaSaliente"] = "-";

        //BUSCO SOLO SI VIENEN LOS 2 PARAMETROS DE ENTRADA VIENEN RELLENOS
        if (
            ($idExpedicion != NULL) &&
            ($idExpedicion != "") &&
            ($idPedidoSalidaLinea != NULL) &&
            ($idPedidoSalidaLinea != "")
        ):
            $whereExpSAP = '';
            if ($expSAP != '' && $expSAP != NULL):
                $whereExpSAP = " AND EES.EXPEDICION_SAP = '$expSAP' ";
            endif;

            //BUSCO LA ENTREGA SALIENTE EN BBDD
            $GLOBALS["NotificaErrorPorEmail"] = "No";
            $sqlEntregaSaliente               = "SELECT EES.NUMERO_ENTREGA_SALIENTE, EESP.LINEA_ENTREGA_SALIENTE 
                                                 FROM EXPEDICION_ENTREGA_SALIENTE_POSICION EESP 
                                                 INNER JOIN EXPEDICION_ENTREGA_SALIENTE EES ON EES.ID_EXPEDICION_ENTREGA_SALIENTE = EESP.ID_EXPEDICION_ENTREGA_SALIENTE 
                                                 WHERE EESP.ID_PEDIDO_SALIDA_LINEA = $idPedidoSalidaLinea AND EES.ID_EXPEDICION = $idExpedicion $whereExpSAP";
            $resultEntregaSaliente            = $bd->ExecSQL($sqlEntregaSaliente);
            unset($GLOBALS["NotificaErrorPorEmail"]);

            //RECUPERO EL REGISTRO SI LO ENCUENTRO
            if (($resultEntregaSaliente != false) && ($bd->NumRegs($resultEntregaSaliente) > 0)):
                $rowEntregaSeliente                         = $bd->SigReg($resultEntregaSaliente);
                $entregaSaliente["NumeroEntregaSaliente"]   = $rowEntregaSeliente->NUMERO_ENTREGA_SALIENTE;
                $entregaSaliente["PosicionEntregaSaliente"] = $rowEntregaSeliente->LINEA_ENTREGA_SALIENTE;
            endif;
        endif;
        //FIN BUSCO SOLO SI VIENEN LOS 2 PARAMETROS DE ENTRADA VIENEN RELLENOS

        //DEVOLVEMOS EL VALOR ALMACENADO EN BBDD
        return $entregaSaliente;
    }

    /**
     * @param $idOrdenTransporte ORDEN DE TRANSPORTE
     * GENERA LOS PEDIDOS ZTL DE LA ORDEN DE TRANSPORTE
     */
    function generarPedidosZTL($idOrdenTransporte)
    {
        global $bd;
        global $html;
        global $administrador;
        global $mat;
        global $stock_compartido;

        //BUSCO LA ORDEN DE RECOGIDA
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowOrdenTransporte               = $bd->VerReg("ORDEN_TRANSPORTE", "ID_ORDEN_TRANSPORTE", $idOrdenTransporte, "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);

        //BUSCO SI LA ORDEN DE TRANSPORTE YA TIENE PEDIDOS
        $sqlPedidoServicios    = "SELECT PE.ID_PEDIDO_ENTRADA
                                   FROM PEDIDO_ENTRADA PE
                                   INNER JOIN ORDEN_TRANSPORTE OT ON OT.ID_ORDEN_TRANSPORTE = PE.ID_ORDEN_TRANSPORTE
                                   WHERE PE.ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND PE.TIPO_PEDIDO_SAP <> 'ZTLJ' AND PE.BAJA = 0";
        $resultPedidoServicios = $bd->ExecSQL($sqlPedidoServicios);

        //SI NO TIENE PEDIDOS, CREAMOS LOS PEDIDOS DE SERVICIOS
        if ($bd->NumRegs($resultPedidoServicios) == 0):

            //BUSCO LAS DIFERENTES ORDENES DE CONTRATACION ACEPTADAS DE LA ORDEN DE TRANSPORTE
            $sqlOrdenesContratacion    = "SELECT OC.ID_ORDEN_CONTRATACION, OC.ID_PROVEEDOR, OC.REFERENCIA_FACTURACION, OC.ID_SERVICIO, OC.NUMERO_CONTRATO, OC.NUMERO_CONTRATO_LINEA, OC.ID_MONEDA
                                           FROM ORDEN_CONTRATACION OC
                                           INNER JOIN PROVEEDOR P ON P.ID_PROVEEDOR = OC.ID_PROVEEDOR
                                           WHERE OC.ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND OC.ESTADO = 'Aceptada' AND OC.BAJA = 0";
            $resultOrdenesContratacion = $bd->ExecSQL($sqlOrdenesContratacion);//exit($sqlOrdenesContratacion);

            //COMPRUEBO QUE EXISTEN CONTRATACIONES CON GESTION TRANSPORTE
            if ($bd->NumRegs($resultOrdenesContratacion) == 0):
                return '';
            endif;

            //CREO PEDIDOS POR CADA CONTRATACION CON PROVEEDORES CON GESTION TRANSPORTE
            while ($rowOrdenContratacion = $bd->SigReg($resultOrdenesContratacion)):

                //BUSCO EL SERVICIO
                $rowServicio = $bd->VerReg("SERVICIO", "ID_SERVICIO", $rowOrdenContratacion->ID_SERVICIO);

                //BUSCO EL MATERIAL SERVICIOS
                $rowMaterialServicios = $bd->VerReg("MATERIAL", "ID_MATERIAL", $rowServicio->ID_MATERIAL, "No");
                $rowUnidadCompra      = $bd->VerReg("UNIDAD", "ID_UNIDAD", $rowMaterialServicios->ID_UNIDAD_COMPRA);

                //BUSCO LAS RECOGIDAS FUERA DE SISTEMA, AGRUPADAS POR ELEMENTO DE IMPUTACION
                $sqlRecogidasFueraSistema    = "SELECT ID_ELEMENTO_IMPUTACION, CODIGO_SELECCIONADO_ELEMENTO_IMPUTACION
                                                 FROM EXPEDICION
                                                 WHERE TIPO_ORDEN_RECOGIDA = 'Operaciones fuera de Sistema' AND ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND BAJA = 0
                                                 GROUP BY ID_ELEMENTO_IMPUTACION, CODIGO_SELECCIONADO_ELEMENTO_IMPUTACION";
                $resultRecogidasFueraSistema = $bd->ExecSQL($sqlRecogidasFueraSistema);//echo($sqlRecogidasFueraSistema . "<hr>");

                //BUSCO LAS RECOGIDAS DE OPERACIONES EN PARQUE, AGRUPADAS POR ORDEN DE TRABAJO
                $sqlRecogidasParque    = "SELECT OT.ID_ORDEN_TRABAJO, OT.ID_CENTRO
                                           FROM EXPEDICION E
                                           INNER JOIN ORDEN_TRABAJO_MOVIMIENTO OTM ON OTM.ID_EXPEDICION = E.ID_EXPEDICION
                                           INNER JOIN ORDEN_TRABAJO OT ON OT.ID_ORDEN_TRABAJO = OTM.ID_ORDEN_TRABAJO
                                           WHERE E.ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND E.BAJA = 0
                                           GROUP BY OTM.ID_ORDEN_TRABAJO";
                $resultRecogidasParque = $bd->ExecSQL($sqlRecogidasParque);//echo($sqlRecogidasParque . "<hr>");

                //SI TIENE RECOGIDAS FUERA DE SISTEMA O EN PARQUE, CREO EL PEDIDO DE SERVICIOS PARA ESA CONTRATACION
                if (($bd->NumRegs($resultRecogidasFueraSistema) > 0) || ($bd->NumRegs($resultRecogidasParque) > 0)):

                    //CREO LA CABECERA DEL PEDIDO, ESTARA EN LIBERACION HASTA QUE NOS CAIGA DESDE SAP PARA PODER CONFIRMARLO
                    $sqlInsert = "INSERT INTO PEDIDO_ENTRADA SET
                                    PEDIDO_SAP = ''
                                    , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                    , TIPO_PEDIDO = 'Servicios'";
                    if ($rowOrdenTransporte->MODELO_TRANSPORTE == 'Primero'):
                        $sqlInsert .= " , TIPO_PEDIDO_SAP = 'ZTLG'
                                        , TIPO_ZTLG = 'Trasmitir ZTL Orden Transporte'";
                    elseif ($rowOrdenTransporte->MODELO_TRANSPORTE == 'Segundo'):
                        $sqlInsert .= " , TIPO_PEDIDO_SAP = 'ZTLI'
                                        , TIPO_ZTLI = 'Importes en Posicion'";
                    endif;
                    $sqlInsert .= " , INDICADOR_LIBERACION = 'En Liberación'
                                    , ID_PROVEEDOR = $rowOrdenContratacion->ID_PROVEEDOR
                                    , ESTADO = 'Creado'
                                    , ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE
                                    , FECHA_CREACION = '" . date("Y-m-d") . "'
                                    , ID_ORDEN_CONTRATACION = $rowOrdenContratacion->ID_ORDEN_CONTRATACION";
                    $bd->ExecSQL($sqlInsert);
                    $idPedidoZTL = $bd->IdAsignado();

                    //DAMOS VALOR A PEDIDO_SAP YA QUE ES CLAVE UNICA
                    $sqlUpdate = "UPDATE PEDIDO_ENTRADA SET
                                    PEDIDO_SAP = 'SGA" . str_pad((string)$idPedidoZTL, 7, "0", STR_PAD_LEFT) . "'
                                    WHERE ID_PEDIDO_ENTRADA = $idPedidoZTL";
                    $bd->ExecSQL($sqlUpdate);

                    // LOG MOVIMIENTOS
                    $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Creación", "Pedido entrada", $idPedidoZTL, "");

                    //INICIAMOS EL ULTIMO NUMERO DE LINEA
                    $UltimoNumeroLinea = 10;

                    //RECOGIDAS FUERA DE SISTEMA
                    while ($rowRecogidasFueraSistema = $bd->SigReg($resultRecogidasFueraSistema)):

                        //COMPROBAMOS QUE TENGA RELLENO EL ELEMENTO DE IMPUTACION
                        $html->PagErrorCondicionado($rowRecogidasFueraSistema->ID_ELEMENTO_IMPUTACION, "==", "", "RecogidaSinElementoImputacion");

                        $tipoImputacion = $this->getTipoImputacionSAP("", "",
                            "", $rowRecogidasFueraSistema->ID_ELEMENTO_IMPUTACION, "", 0);

                        //CREO LA LINEA DEL PEDIDO DE ENTRADA
                        $sqlInsert = "INSERT INTO PEDIDO_ENTRADA_LINEA SET
                                        ID_PEDIDO_ENTRADA = $idPedidoZTL
                                        , ID_ELEMENTO_IMPUTACION = $rowRecogidasFueraSistema->ID_ELEMENTO_IMPUTACION
                                        , CODIGO_SELECCIONADO_ELEMENTO_IMPUTACION = '" . $bd->escapeCondicional($rowRecogidasFueraSistema->CODIGO_SELECCIONADO_ELEMENTO_IMPUTACION) . "'
                                        , MATERIAL_REAL_ZTL_RECEPCIONADO = 0 
                                        , ID_ALMACEN = NULL
                                        , ID_CENTRO = $rowOrdenTransporte->ID_CENTRO_CONTRATANTE
                                        , ID_MATERIAL = $rowMaterialServicios->ID_MATERIAL
                                        , ID_TIPO_BLOQUEO = NULL
                                        , UNIDAD_SAP = $rowMaterialServicios->ID_UNIDAD_COMPRA
                                        , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                        , LINEA_PEDIDO_SAP = '" . str_pad((string)$UltimoNumeroLinea, 5, "0", STR_PAD_LEFT) . "'
                                        , CANTIDAD = 1
                                        , CANTIDAD_PDTE = 1
                                        , NUMERO_CONTRATO =  '" . $bd->escapeCondicional($rowOrdenContratacion->NUMERO_CONTRATO) . "'
                                        , NUMERO_CONTRATO_LINEA = '" . $bd->escapeCondicional($rowOrdenContratacion->NUMERO_CONTRATO_LINEA) . "'
                                        , FECHA_ENTREGA = '" . date("Y-m-d") . "'
                                        , ESTADO = 'Sin Recepcionar'
                                        , TIPO_IMPUTACION = '" . $tipoImputacion . "'
                                        , ID_MONEDA_TRANSMITIDA_A_SAP = " . ($rowOrdenContratacion->ID_MONEDA == NULL ? 'NULL' : $rowOrdenContratacion->ID_MONEDA) . "
                                        , ACTIVA = 1";
                        $bd->ExecSQL($sqlInsert);

                        $idPedidoEntradaLinea = $bd->IdAsignado();

                        //INCREMENTO EN 10 EL NUMERO DE LINEA
                        $UltimoNumeroLinea = $UltimoNumeroLinea + 10;

                        //BUSCAMOS LAS RECOGIDAS A LAS QUE HACE REFERENCIA LA AGRUPACION Y LAS GUARDAMOS COMO LINEAS DE SERVICIOS
                        $sqlRecogidasFueraSistemaDesglose    = "SELECT ID_EXPEDICION
                                                                 FROM EXPEDICION
                                                                 WHERE TIPO_ORDEN_RECOGIDA = 'Operaciones fuera de Sistema' AND ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND BAJA = 0 AND ID_ELEMENTO_IMPUTACION = $rowRecogidasFueraSistema->ID_ELEMENTO_IMPUTACION AND CODIGO_SELECCIONADO_ELEMENTO_IMPUTACION = '" . $bd->escapeCondicional($rowRecogidasFueraSistema->CODIGO_SELECCIONADO_ELEMENTO_IMPUTACION) . "'";
                        $resultRecogidasFueraSistemaDesglose = $bd->ExecSQL($sqlRecogidasFueraSistemaDesglose);
                        while ($rowRecogidasFueraSistemaDesglose = $bd->SigReg($resultRecogidasFueraSistemaDesglose)):
                            //CREAMOS LAS LINEAS DE SERVICIOS, AL SER SIN MATERIAL , LO PONEMOS A NULL y LA CANTIDAD a 0
                            $sqlInsert = "INSERT INTO PEDIDO_SERVICIO_LINEA SET
                                            ID_PEDIDO_ENTRADA = $idPedidoZTL
                                            , ID_PEDIDO_ENTRADA_LINEA = $idPedidoEntradaLinea
                                            , ID_EXPEDICION = $rowRecogidasFueraSistemaDesglose->ID_EXPEDICION
                                            , ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE
                                            , ID_MATERIAL = NULL
                                            , CANTIDAD = 0";
                            $bd->ExecSQL($sqlInsert);
                        endwhile;
                    endwhile;// FIN RECOGIDAS FUERA DE SISTEMA, DESGLOSE DE LINEAS A LA TABLA PEDIDO_SERVICIO_LINEA

                    //RECOGIDAS EN PARQUE
                    while ($rowRecogidasParque = $bd->SigReg($resultRecogidasParque)):

                        $tipoImputacion = $this->getTipoImputacionSAP("", "",
                            "", "", $rowRecogidasParque->ID_ORDEN_TRABAJO, 0);

                        //CREO LA LINEA DEL PEDIDO DE ENTRADA, AGRUPADA POR ORDEN DE TRABAJO Y SE ENVIA EL CENTRO CONTRATANTE
                        $sqlInsert = "INSERT INTO PEDIDO_ENTRADA_LINEA SET
                                        ID_PEDIDO_ENTRADA = $idPedidoZTL
                                        , ID_ORDEN_TRABAJO_RELACIONADO = $rowRecogidasParque->ID_ORDEN_TRABAJO
                                        , MATERIAL_REAL_ZTL_RECEPCIONADO = 0 
                                        , ID_ALMACEN = NULL
                                        , ID_CENTRO = $rowRecogidasParque->ID_CENTRO
                                        , ID_MATERIAL = $rowMaterialServicios->ID_MATERIAL
                                        , ID_TIPO_BLOQUEO = NULL
                                        , UNIDAD_SAP = $rowMaterialServicios->ID_UNIDAD_COMPRA
                                        , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                        , LINEA_PEDIDO_SAP = '" . str_pad((string)$UltimoNumeroLinea, 5, "0", STR_PAD_LEFT) . "'
                                        , CANTIDAD = 1
                                        , CANTIDAD_PDTE = 1
                                        , NUMERO_CONTRATO =  '" . $bd->escapeCondicional($rowOrdenContratacion->NUMERO_CONTRATO) . "'
                                        , NUMERO_CONTRATO_LINEA = '" . $bd->escapeCondicional($rowOrdenContratacion->NUMERO_CONTRATO_LINEA) . "'
                                        , FECHA_ENTREGA = '" . date("Y-m-d") . "'
                                        , ESTADO = 'Sin Recepcionar'
                                        , ID_MONEDA_TRANSMITIDA_A_SAP = " . ($rowOrdenContratacion->ID_MONEDA == NULL ? 'NULL' : $rowOrdenContratacion->ID_MONEDA) . "
                                        , TIPO_IMPUTACION = '" . $tipoImputacion . "'";
                        $bd->ExecSQL($sqlInsert);

                        $idPedidoEntradaLinea = $bd->IdAsignado();

                        //INCREMENTO EN 10 EL NUMERO DE LINEA
                        $UltimoNumeroLinea = $UltimoNumeroLinea + 10;

                        //BUSCO LAS ORDEN TRABAJO MOVIMIENTO DE ESA ORDEN DE TRABAJO
                        $sqlOTMRecogida    = "SELECT OTM.ID_ORDEN_TRABAJO_MOVIMIENTO, OTM.ID_MATERIAL, OTM.CANTIDAD, OTM.ID_EXPEDICION
                                           FROM EXPEDICION E
                                           INNER JOIN ORDEN_TRABAJO_MOVIMIENTO OTM ON OTM.ID_EXPEDICION = E.ID_EXPEDICION
                                           WHERE E.ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND E.BAJA = 0 AND OTM.ID_ORDEN_TRABAJO = $rowRecogidasParque->ID_ORDEN_TRABAJO";
                        $resultOTMRecogida = $bd->ExecSQL($sqlOTMRecogida);

                        //LINEAS OTM RELACIONADAS CON LA ORDEN TRABAJO, CREO UNA LINEA POR OTM
                        while ($rowOTMRecogida = $bd->SigReg($resultOTMRecogida)):

                            //CREAMOS LA LINEA DE SERVICIOS
                            $sqlInsert = "INSERT INTO PEDIDO_SERVICIO_LINEA SET
                                            ID_PEDIDO_ENTRADA = $idPedidoZTL
                                            , ID_PEDIDO_ENTRADA_LINEA = $idPedidoEntradaLinea
                                            , ID_ORDEN_TRABAJO_MOVIMIENTO = $rowOTMRecogida->ID_ORDEN_TRABAJO_MOVIMIENTO
                                            , ID_EXPEDICION = $rowOTMRecogida->ID_EXPEDICION
                                            , ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE
                                            , ID_MATERIAL = $rowOTMRecogida->ID_MATERIAL
                                            , CANTIDAD = $rowOTMRecogida->CANTIDAD";
                            $bd->ExecSQL($sqlInsert);
                        endwhile;//FIN LINEAS OTM RELACIONADAS CON LA ORDEN TRABAJO
                    endwhile;// FIN RECOGIDAS EN PARQUE
                endif;//FIN TIENE RECOGIDAS FUERA DE SISTEMA O EN PARQUE

                //CONFIGURACION DE LAS LINEAS AFECTADAS EN FUNCION DEL MODELO DE TRANSPORTE DE LA ORDEN DE TRANSPORTE
                if ($rowOrdenTransporte->MODELO_TRANSPORTE == 'Primero'): //RECOGIDAS CON ENVIOS Y COMPONENTES A PROVEEDOR (ZTLI)
                    $sqlWhere = "E.ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND MSL.LINEA_ANULADA = 0 AND MSL.BAJA = 0 AND (PS.TIPO_PEDIDO = 'Componentes a Proveedor' OR PS.TIPO_PEDIDO = 'Material Estropeado a Proveedor' OR PS.TIPO_PEDIDO = 'Material Estropeado Entre Proveedores' OR PS.TIPO_PEDIDO = 'Devolución a Proveedor')";
                elseif ($rowOrdenTransporte->MODELO_TRANSPORTE == 'Segundo'): //RECOGIDAS EN PROVEEDOR
                    $sqlWhere = "E.ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND MSL.LINEA_ANULADA = 0 AND MSL.BAJA = 0 AND E.TIPO_ORDEN_RECOGIDA = 'Recogida en Almacen'";
                endif;

                //BUSCO LINEAS DE MOVIMIENTOS DE SALIDA EN FUNCION DE LO FILTRADO
                $sqlPedidoZTLI    = "SELECT MSL.ID_MOVIMIENTO_SALIDA_LINEA, MSL.ID_ALMACEN, MSL.ID_MATERIAL, MSL.ID_TIPO_BLOQUEO, PSL.ID_UNIDAD, MSL.CANTIDAD, MSL.ESTADO, MSL.ID_ALMACEN_DESTINO, PS.TIPO_PEDIDO
                                      FROM MOVIMIENTO_SALIDA_LINEA MSL
                                      INNER JOIN EXPEDICION E ON E.ID_EXPEDICION = MSL.ID_EXPEDICION
                                      INNER JOIN PEDIDO_SALIDA PS ON PS.ID_PEDIDO_SALIDA = MSL.ID_PEDIDO_SALIDA
                                      INNER JOIN PEDIDO_SALIDA_LINEA PSL ON PSL.ID_PEDIDO_SALIDA_LINEA = MSL.ID_PEDIDO_SALIDA_LINEA
                                      WHERE " . $sqlWhere;
                $resultPedidoZTLI = $bd->ExecSQL($sqlPedidoZTLI);
                if ($bd->NumRegs($resultPedidoZTLI) > 0):

                    //CREO EL PEDIDO ZTLI
                    $sqlInsert = "INSERT INTO PEDIDO_ENTRADA SET
                                    PEDIDO_SAP = ''
                                    , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                    , TIPO_PEDIDO = 'Servicios'
                                    , TIPO_PEDIDO_SAP = 'ZTLI'";
                    if ($rowOrdenTransporte->MODELO_TRANSPORTE == 'Primero'):
                        $sqlInsert .= " , TIPO_ZTLI = 'Envio a Proveedor'";
                    elseif ($rowOrdenTransporte->MODELO_TRANSPORTE == 'Segundo'):
                        $sqlInsert .= " , TIPO_ZTLI = 'Importes en Posicion'";
                    endif;
                    $sqlInsert .= " , INDICADOR_LIBERACION = 'En Liberación'
                                    , ID_PROVEEDOR = $rowOrdenContratacion->ID_PROVEEDOR
                                    , ESTADO = 'Creado'
                                    , ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE
                                    , FECHA_CREACION = '" . date("Y-m-d") . "'
                                    , ID_ORDEN_CONTRATACION = $rowOrdenContratacion->ID_ORDEN_CONTRATACION";
                    $bd->ExecSQL($sqlInsert);
                    $idPedidoZTLI = $bd->IdAsignado();

                    //DAMOS VALOR A PEDIDO_SAP YA QUE ES CLAVE UNICA
                    $sqlUpdate = "UPDATE PEDIDO_ENTRADA SET
                                    PEDIDO_SAP = 'SGA" . str_pad((string)$idPedidoZTLI, 7, "0", STR_PAD_LEFT) . "'
                                    WHERE ID_PEDIDO_ENTRADA = $idPedidoZTLI";
                    $bd->ExecSQL($sqlUpdate);

                    // LOG MOVIMIENTOS
                    $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Creación", "Pedido entrada", $idPedidoZTLI, "");

                    //INICIAMOS EL ULTIMO NUMERO DE LINEA
                    $UltimoNumeroLinea = 10;

                    //CREAMOS UNA LINEA POR MOVIMIENTO
                    while ($rowPedidoZTLI = $bd->SigReg($resultPedidoZTLI)):

                        $sqlInsertZTLIOrig                = "";
                        $GLOBALS["NotificaErrorPorEmail"] = "No";
                        $rowPedEntLinOrig                 = $bd->VerReg("PEDIDO_ENTRADA_LINEA", "ID_MOVIMIENTO_SALIDA_LINEA_RELACIONADA_MUEVE_MATERIAL", $rowPedidoZTLI->ID_MOVIMIENTO_SALIDA_LINEA, "No");
                        if ($rowPedEntLinOrig != false):
                            $rowPedEntOrig = $bd->VerReg("PEDIDO_ENTRADA", "ID_PEDIDO_ENTRADA", $rowPedEntLinOrig->ID_PEDIDO_ENTRADA);
                            if (substr((string)$rowPedEntOrig->PEDIDO_SAP, 0, 3) != 'SGA'):
                                $sqlInsertZTLIOrig = " , ID_LINEA_ZTLI_ORIGINAL = $rowPedEntLinOrig->ID_PEDIDO_ENTRADA_LINEA ";
                            endif;
                        endif;

                        //DETERMINO SI ESTA RECEPCIONADO EN FUNCION DEL ESTADO DE LA LINEA DEL MOVIMIENTO DE SALIDA
                        if (($rowPedidoZTLI->ESTADO == 'Expedido') || ($rowPedidoZTLI->ESTADO == 'Recepcionado')):
                            $posicionRecepcionada = 1;
                        else:
                            $posicionRecepcionada = 0;
                        endif;

                        //BUSCO EL ALMACEN DE ORIGEN
                        $rowAlmacen   = $bd->VerReg("ALMACEN", "ID_ALMACEN", $rowPedidoZTLI->ID_ALMACEN);
                        $idCentroZTLI = $rowOrdenTransporte->ID_CENTRO_CONTRATANTE;

                        //SI EL ALMACEN ES SPV, MANDAREMOS EL CENTRO DEL ALMACEN MANTENEDOR
                        if (($rowAlmacen->STOCK_COMPARTIDO == 1) && ($rowAlmacen->TIPO_STOCK == 'SPV') && ($stock_compartido->Obtener_Almacen_Mantenedor($rowAlmacen->ID_CENTRO_FISICO) != NULL)):
                            $rowAlmacenMantenedor = $bd->VerReg("ALMACEN", "ID_ALMACEN", $stock_compartido->Obtener_Almacen_Mantenedor($rowAlmacen->ID_CENTRO_FISICO));
                            $idCentroZTLI         = $rowAlmacenMantenedor->ID_CENTRO;
                        endif;

                        //ALMACEN DESTINO
                        if ($rowPedidoZTLI->TIPO_PEDIDO == 'Venta'):
                            $rowMaterial = $bd->VerReg("MATERIAL", "ID_MATERIAL", $rowMaterialServicios->ID_MATERIAL);
                        else:
                            $rowAlmacenDestino = $bd->VerReg("ALMACEN", "ID_ALMACEN", $rowPedidoZTLI->ID_ALMACEN_DESTINO);
                            if ($rowAlmacenDestino->INFORMAR_MATERIAL_TRANSPORTE == 1):
                                $rowMaterial = $bd->VerReg("MATERIAL", "ID_MATERIAL", $rowMaterialServicios->ID_MATERIAL);
                            else:
                                $rowMaterial = $bd->VerReg("MATERIAL", "ID_MATERIAL", $rowPedidoZTLI->ID_MATERIAL);
                            endif;
                        endif;

                        //NOS GUARDAMOS LA CANTIDAD SAP
                        $cantidad_sap = $mat->cantUnidadCompra($rowPedidoZTLI->ID_MATERIAL, $rowPedidoZTLI->CANTIDAD);

                        $tipoImputacion = $this->getTipoImputacionSAP($rowPedidoZTLI->ID_MOVIMIENTO_SALIDA_LINEA, "", "", "", "", $posicionRecepcionada);

                        //CREO LA LINEA DEL PEDIDO DE ENTRADA
                        $sqlInsert = "INSERT INTO PEDIDO_ENTRADA_LINEA SET
                                        ID_PEDIDO_ENTRADA = $idPedidoZTLI
                                        , ID_MOVIMIENTO_SALIDA_LINEA_RELACIONADA_MUEVE_MATERIAL = $rowPedidoZTLI->ID_MOVIMIENTO_SALIDA_LINEA
                                        , MATERIAL_REAL_ZTL_RECEPCIONADO = $posicionRecepcionada 
                                        , ID_ALMACEN = NULL
                                        , ID_CENTRO = $idCentroZTLI
                                        , ID_MATERIAL = $rowMaterial->ID_MATERIAL
                                        , ID_TIPO_BLOQUEO = " . ($rowPedidoZTLI->ID_TIPO_BLOQUEO == NULL ? 'NULL' : $rowPedidoZTLI->ID_TIPO_BLOQUEO) . "
                                        , UNIDAD_SAP = $rowMaterial->ID_UNIDAD_COMPRA
                                        , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                        , LINEA_PEDIDO_SAP = '" . str_pad((string)$UltimoNumeroLinea, 5, "0", STR_PAD_LEFT) . "'
                                        , CANTIDAD_SAP = $cantidad_sap
                                        , CANTIDAD = $rowPedidoZTLI->CANTIDAD
                                        , CANTIDAD_PDTE = $rowPedidoZTLI->CANTIDAD
                                        , NUMERO_CONTRATO =  '" . $bd->escapeCondicional($rowOrdenContratacion->NUMERO_CONTRATO) . "'
                                        , NUMERO_CONTRATO_LINEA = '" . $bd->escapeCondicional($rowOrdenContratacion->NUMERO_CONTRATO_LINEA) . "'
                                        , FECHA_ENTREGA = '" . date("Y-m-d") . "'
                                        , ESTADO = 'Sin Recepcionar'
                                        , TIPO_IMPUTACION = '" . $tipoImputacion . "'
                                        , ACTIVA = 1
                                        , ID_MONEDA_TRANSMITIDA_A_SAP = " . ($rowOrdenContratacion->ID_MONEDA == NULL ? 'NULL' : $rowOrdenContratacion->ID_MONEDA) . " 
                                        $sqlInsertZTLIOrig";
                        $bd->ExecSQL($sqlInsert);

                        //INCREMENTO EN 10 EL NUMERO DE LINEA
                        $UltimoNumeroLinea = $UltimoNumeroLinea + 10;
                    endwhile;//FIN CREAMOS UNA LINEA POR MOVIMIENTO
                endif;// FIN BUSCO LINEAS DE MOVIMIENTOS DE SALIDA EN FUNCION DE LO FILTRADO

                if ($rowOrdenTransporte->MODELO_TRANSPORTE == 'Segundo'): //ORDEN TRANSPORTE DE MODELO TRANSPORTE Segundo
                    //BUSCO LINEAS DE TIPO RECOGIDAS EN PROVEEDOR CON PEDIDO CONOCIDO
                    $sqlPedidoZTLI    = "SELECT EPC.ID_EXPEDICION_PEDIDO_CONOCIDO, EPC.ID_PEDIDO_ENTRADA_LINEA, E.ID_EXPEDICION, PEL.ID_ALMACEN, PEL.ID_MATERIAL, PEL.ID_TIPO_BLOQUEO, (EPC.CANTIDAD - EPC.CANTIDAD_NO_SERVIDA) AS CANTIDAD_TOTAL 
                                          FROM EXPEDICION_PEDIDO_CONOCIDO EPC 
                                          INNER JOIN EXPEDICION E ON E.ID_EXPEDICION = EPC.ID_EXPEDICION 
                                          INNER JOIN PEDIDO_ENTRADA_LINEA PEL ON PEL.ID_PEDIDO_ENTRADA_LINEA = EPC.ID_PEDIDO_ENTRADA_LINEA 
                                          WHERE E.ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND E.TIPO_ORDEN_RECOGIDA = 'Recogida en Proveedor' AND E.SUBTIPO_ORDEN_RECOGIDA = 'Con Pedido Conocido' AND EPC.BAJA = 0";
                    $resultPedidoZTLI = $bd->ExecSQL($sqlPedidoZTLI);
                    if ($bd->NumRegs($resultPedidoZTLI) > 0):

                        //CREO EL PEDIDO ZTLI
                        $sqlInsert = "INSERT INTO PEDIDO_ENTRADA SET
                                        PEDIDO_SAP = ''
                                        , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                        , TIPO_PEDIDO = 'Servicios'
                                        , TIPO_PEDIDO_SAP = 'ZTLI'
                                        , TIPO_ZTLI = 'Importes en Posicion' 
                                        , INDICADOR_LIBERACION = 'En Liberación'
                                        , ID_PROVEEDOR = $rowOrdenContratacion->ID_PROVEEDOR
                                        , ESTADO = 'Creado'
                                        , ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE
                                        , FECHA_CREACION = '" . date("Y-m-d") . "'
                                        , ID_ORDEN_CONTRATACION = $rowOrdenContratacion->ID_ORDEN_CONTRATACION";
                        $bd->ExecSQL($sqlInsert);
                        $idPedidoZTLI = $bd->IdAsignado();

                        //DAMOS VALOR A PEDIDO_SAP YA QUE ES CLAVE UNICA
                        $sqlUpdate = "UPDATE PEDIDO_ENTRADA SET
                                        PEDIDO_SAP = 'SGA" . str_pad((string)$idPedidoZTLI, 7, "0", STR_PAD_LEFT) . "'
                                        WHERE ID_PEDIDO_ENTRADA = $idPedidoZTLI";
                        $bd->ExecSQL($sqlUpdate);

                        // LOG MOVIMIENTOS
                        $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Creación", "Pedido entrada", $idPedidoZTLI, "");

                        //INICIAMOS EL ULTIMO NUMERO DE LINEA
                        $UltimoNumeroLinea = 10;

                        //CREAMOS UNA LINEA POR MOVIMIENTO
                        while ($rowPedidoZTLI = $bd->SigReg($resultPedidoZTLI)):

                            $sqlInsertZTLIOrig                = "";
                            $GLOBALS["NotificaErrorPorEmail"] = "No";
                            $rowPedEntLinOrig                 = $bd->VerReg("PEDIDO_ENTRADA_LINEA", "ID_PEDIDO_ENTRADA_LINEA_RELACIONADA_RECEPCIONA_MATERIAL", $rowPedidoZTLI->ID_PEDIDO_ENTRADA_LINEA, "No");
                            if ($rowPedEntLinOrig != false):
                                $rowPedEntOrig = $bd->VerReg("PEDIDO_ENTRADA", "ID_PEDIDO_ENTRADA", $rowPedEntLinOrig->ID_PEDIDO_ENTRADA);
                                if (substr((string)$rowPedEntOrig->PEDIDO_SAP, 0, 3) != 'SGA'):
                                    $sqlInsertZTLIOrig = " , ID_LINEA_ZTLI_ORIGINAL = $rowPedEntLinOrig->ID_PEDIDO_ENTRADA_LINEA ";
                                endif;
                            endif;

                            //POSICION RECEPCIONADA POR DEFECTO NO
                            $posicionRecepcionada = 0;

                            //BUSCO LA LINEA DEL MOVIMIENTO DE ENTRADA
                            $GLOBALS["NotificaErrorPorEmail"] = "No";
                            $rowMovimientoEntradaLinea        = $bd->VerRegRest("MOVIMIENTO_ENTRADA_LINEA", "ID_PEDIDO_LINEA = $rowPedidoZTLI->ID_PEDIDO_ENTRADA_LINEA AND ID_EXPEDICION_ENTREGA = $rowPedidoZTLI->ID_EXPEDICION AND LINEA_ANULADA = 0 AND BAJA = 0", "No");
                            unset($GLOBALS["NotificaErrorPorEmail"]);

                            //SI EXISTE LA LINEA DEL MOVIMIENTO DE ENTRADA
                            if ($rowMovimientoEntradaLinea != false):
                                //BUSCO EL MOVIMIENTO DE ENTRADA
                                $rowMovimientoEntrada = $bd->VerReg("MOVIMIENTO_ENTRADA", "ID_MOVIMIENTO_ENTRADA", $rowMovimientoEntradaLinea->ID_MOVIMIENTO_ENTRADA);
                                if (($rowMovimientoEntrada->ESTADO != 'En Proceso') && ($rowMovimientoEntrada->PENDIENTE_CONFIRMACION_SAP == 0)):
                                    $posicionRecepcionada = 1;
                                endif;
                            endif;

                            //BUSCO EL ALMACEN DE ORIGEN
                            $rowAlmacen   = $bd->VerReg("ALMACEN", "ID_ALMACEN", $rowPedidoZTLI->ID_ALMACEN);
                            $idCentroZTLI = $rowOrdenTransporte->ID_CENTRO_CONTRATANTE;

                            //ALMACEN DESTINO
                            if ($rowAlmacen->INFORMAR_MATERIAL_TRANSPORTE == 1):
                                if ($stock_compartido->Obtener_Almacen_Mantenedor($rowAlmacen->ID_CENTRO_FISICO) != NULL):
                                    $rowAlmacenMantenedor = $bd->VerReg("ALMACEN", "ID_ALMACEN", $stock_compartido->Obtener_Almacen_Mantenedor($rowAlmacen->ID_CENTRO_FISICO));
                                    $idCentroZTLI         = $rowAlmacenMantenedor->ID_CENTRO;
                                endif;
                                $rowMaterial = $bd->VerReg("MATERIAL", "ID_MATERIAL", $rowMaterialServicios->ID_MATERIAL);
                            else:
                                $rowMaterial = $bd->VerReg("MATERIAL", "ID_MATERIAL", $rowPedidoZTLI->ID_MATERIAL);
                            endif;

                            //NOS GUARDAMOS LA CANTIDAD SAP
                            $cantidad_sap = $mat->cantUnidadCompra($rowPedidoZTLI->ID_MATERIAL, $rowPedidoZTLI->CANTIDAD_TOTAL);

                            $tipoImputacion = $this->getTipoImputacionSAP("", $rowPedidoZTLI->ID_PEDIDO_ENTRADA_LINEA,
                                "", "", "", $posicionRecepcionada);

                            //CREO LA LINEA DEL PEDIDO DE ENTRADA
                            $sqlInsert = "INSERT INTO PEDIDO_ENTRADA_LINEA SET
                                            ID_PEDIDO_ENTRADA = $idPedidoZTLI
                                            , ID_PEDIDO_ENTRADA_LINEA_RELACIONADA_RECEPCIONA_MATERIAL = $rowPedidoZTLI->ID_PEDIDO_ENTRADA_LINEA 
                                            , MATERIAL_REAL_ZTL_RECEPCIONADO = $posicionRecepcionada 
                                            , ID_ALMACEN = NULL
                                            , ID_CENTRO = $idCentroZTLI
                                            , ID_MATERIAL = $rowMaterial->ID_MATERIAL
                                            , ID_TIPO_BLOQUEO = " . ($rowPedidoZTLI->ID_TIPO_BLOQUEO == NULL ? 'NULL' : $rowPedidoZTLI->ID_TIPO_BLOQUEO) . "
                                            , UNIDAD_SAP = $rowMaterial->ID_UNIDAD_COMPRA
                                            , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                            , LINEA_PEDIDO_SAP = '" . str_pad((string)$UltimoNumeroLinea, 5, "0", STR_PAD_LEFT) . "'
                                            , CANTIDAD_SAP = $cantidad_sap
                                            , CANTIDAD = $rowPedidoZTLI->CANTIDAD_TOTAL
                                            , CANTIDAD_PDTE = $rowPedidoZTLI->CANTIDAD_TOTAL
                                            , NUMERO_CONTRATO =  '" . $bd->escapeCondicional($rowOrdenContratacion->NUMERO_CONTRATO) . "'
                                            , NUMERO_CONTRATO_LINEA = '" . $bd->escapeCondicional($rowOrdenContratacion->NUMERO_CONTRATO_LINEA) . "'
                                            , FECHA_ENTREGA = '" . date("Y-m-d") . "'
                                            , ESTADO = 'Sin Recepcionar'
                                            , ACTIVA = 1
                                            , ID_MONEDA_TRANSMITIDA_A_SAP = " . ($rowOrdenContratacion->ID_MONEDA == NULL ? 'NULL' : $rowOrdenContratacion->ID_MONEDA) . "
                                            , TIPO_IMPUTACION = '" . $tipoImputacion . "'
                                            $sqlInsertZTLIOrig";
                            $bd->ExecSQL($sqlInsert);

                            //INCREMENTO EN 10 EL NUMERO DE LINEA
                            $UltimoNumeroLinea = $UltimoNumeroLinea + 10;
                        endwhile;//FIN CREAMOS UNA LINEA POR MOVIMIENTO
                    endif;// FIN BUSCO LINEAS DE TIPO RECOGIDAS EN PROVEEDOR CON PEDIDO CONOCIDO

                    //BUSCO LINEAS DE TIPO RECOGIDAS EN PROVEEDOR SIN PEDIDO CONOCIDO
                    $sqlPedidoZTLI    = "SELECT MEL.ID_MOVIMIENTO_ENTRADA_LINEA, MEL.ID_MOVIMIENTO_ENTRADA, MEL.ID_UBICACION, MEL.ID_MATERIAL, MEL.ID_TIPO_BLOQUEO, MEL.CANTIDAD 
                                          FROM MOVIMIENTO_ENTRADA_LINEA MEL 
                                          INNER JOIN EXPEDICION E ON E.ID_EXPEDICION = MEL.ID_EXPEDICION_ENTREGA 
                                          WHERE E.ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND E.TIPO_ORDEN_RECOGIDA = 'Recogida en Proveedor' AND E.SUBTIPO_ORDEN_RECOGIDA = 'Sin Pedido Conocido' AND MEL.BAJA = 0 AND MEL.LINEA_ANULADA = 0";
                    $resultPedidoZTLI = $bd->ExecSQL($sqlPedidoZTLI);
                    if ($bd->NumRegs($resultPedidoZTLI) > 0):

                        //CREO EL PEDIDO ZTLI
                        $sqlInsert = "INSERT INTO PEDIDO_ENTRADA SET
                                        PEDIDO_SAP = ''
                                        , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                        , TIPO_PEDIDO = 'Servicios'
                                        , TIPO_PEDIDO_SAP = 'ZTLI'
                                        , TIPO_ZTLI = 'Importes en Posicion' 
                                        , INDICADOR_LIBERACION = 'En Liberación'
                                        , ID_PROVEEDOR = $rowOrdenContratacion->ID_PROVEEDOR
                                        , ESTADO = 'Creado'
                                        , ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE
                                        , FECHA_CREACION = '" . date("Y-m-d") . "'
                                        , ID_ORDEN_CONTRATACION = $rowOrdenContratacion->ID_ORDEN_CONTRATACION";
                        $bd->ExecSQL($sqlInsert);
                        $idPedidoZTLI = $bd->IdAsignado();

                        //DAMOS VALOR A PEDIDO_SAP YA QUE ES CLAVE UNICA
                        $sqlUpdate = "UPDATE PEDIDO_ENTRADA SET
                                        PEDIDO_SAP = 'SGA" . str_pad((string)$idPedidoZTLI, 7, "0", STR_PAD_LEFT) . "'
                                        WHERE ID_PEDIDO_ENTRADA = $idPedidoZTLI";
                        $bd->ExecSQL($sqlUpdate);

                        // LOG MOVIMIENTOS
                        $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Creación", "Pedido entrada", $idPedidoZTLI, "");

                        //INICIAMOS EL ULTIMO NUMERO DE LINEA
                        $UltimoNumeroLinea = 10;

                        //CREAMOS UNA LINEA POR MOVIMIENTO
                        while ($rowPedidoZTLI = $bd->SigReg($resultPedidoZTLI)):

                            $sqlInsertZTLIOrig                = "";
                            $GLOBALS["NotificaErrorPorEmail"] = "No";
                            $rowPedEntLinOrig                 = $bd->VerReg("PEDIDO_ENTRADA_LINEA", "ID_MOVIMIENTO_ENTRADA_LINEA_RELACIONADA_RECEPCIONA_MATERIAL", $rowPedidoZTLI->ID_MOVIMIENTO_ENTRADA_LINEA, "No");
                            if ($rowPedEntLinOrig != false):
                                $sqlInsertZTLIOrig = " , ID_LINEA_ZTLI_ORIGINAL = $rowPedEntLinOrig->ID_PEDIDO_ENTRADA_LINEA ";
                            endif;

                            //BUSCO EL MOVIMIENTO DE ENTRADA
                            $rowMovimientoEntrada = $bd->VerReg("MOVIMIENTO_ENTRADA", "ID_MOVIMIENTO_ENTRADA", $rowPedidoZTLI->ID_MOVIMIENTO_ENTRADA);
                            if (($rowMovimientoEntrada->ESTADO != 'En Proceso') && ($rowMovimientoEntrada->PENDIENTE_CONFIRMACION_SAP == 0)):
                                $posicionRecepcionada = 1;
                            else:
                                $posicionRecepcionada = 0;
                            endif;

                            //BUSCO LA UBICACION DE DESTINO
                            $rowUbicacion = $bd->VerReg("UBICACION", "ID_UBICACION", $rowPedidoZTLI->ID_UBICACION);

                            //BUSCO EL ALMACEN DE DESTINO
                            $rowAlmacen   = $bd->VerReg("ALMACEN", "ID_ALMACEN", $rowUbicacion->ID_ALMACEN);
                            $idCentroZTLI = $rowOrdenTransporte->ID_CENTRO_CONTRATANTE;

                            if ($rowAlmacen->INFORMAR_MATERIAL_TRANSPORTE == 1):
                                if ($stock_compartido->Obtener_Almacen_Mantenedor($rowAlmacen->ID_CENTRO_FISICO) != NULL):
                                    $rowAlmacenMantenedor = $bd->VerReg("ALMACEN", "ID_ALMACEN", $stock_compartido->Obtener_Almacen_Mantenedor($rowAlmacen->ID_CENTRO_FISICO));
                                    $idCentroZTLI         = $rowAlmacenMantenedor->ID_CENTRO;
                                endif;
                                $rowMaterial = $bd->VerReg("MATERIAL", "ID_MATERIAL", $rowMaterialServicios->ID_MATERIAL);
                            else:
                                $rowMaterial = $bd->VerReg("MATERIAL", "ID_MATERIAL", $rowPedidoZTLI->ID_MATERIAL);
                            endif;

                            //NOS GUARDAMOS LA CANTIDAD SAP
                            $cantidad_sap = $mat->cantUnidadCompra($rowPedidoZTLI->ID_MATERIAL, $rowPedidoZTLI->CANTIDAD);

                            $tipoImputacion = $this->getTipoImputacionSAP("", "",
                                $rowPedidoZTLI->ID_MOVIMIENTO_ENTRADA_LINEA, "", "", $posicionRecepcionada);

                            //CREO LA LINEA DEL PEDIDO DE ENTRADA
                            $sqlInsert = "INSERT INTO PEDIDO_ENTRADA_LINEA SET
                                            ID_PEDIDO_ENTRADA = $idPedidoZTLI
                                            , ID_MOVIMIENTO_ENTRADA_LINEA_RELACIONADA_RECEPCIONA_MATERIAL = $rowPedidoZTLI->ID_MOVIMIENTO_ENTRADA_LINEA  
                                            , MATERIAL_REAL_ZTL_RECEPCIONADO = $posicionRecepcionada 
                                            , ID_ALMACEN = NULL
                                            , ID_CENTRO = $idCentroZTLI
                                            , ID_MATERIAL = $rowMaterial->ID_MATERIAL
                                            , ID_TIPO_BLOQUEO = " . ($rowPedidoZTLI->ID_TIPO_BLOQUEO == NULL ? 'NULL' : $rowPedidoZTLI->ID_TIPO_BLOQUEO) . "
                                            , UNIDAD_SAP = $rowMaterial->ID_UNIDAD_COMPRA
                                            , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                            , LINEA_PEDIDO_SAP = '" . str_pad((string)$UltimoNumeroLinea, 5, "0", STR_PAD_LEFT) . "'
                                            , CANTIDAD_SAP = $cantidad_sap
                                            , CANTIDAD = $rowPedidoZTLI->CANTIDAD
                                            , CANTIDAD_PDTE = $rowPedidoZTLI->CANTIDAD
                                            , NUMERO_CONTRATO =  '" . $bd->escapeCondicional($rowOrdenContratacion->NUMERO_CONTRATO) . "'
                                            , NUMERO_CONTRATO_LINEA = '" . $bd->escapeCondicional($rowOrdenContratacion->NUMERO_CONTRATO_LINEA) . "'
                                            , FECHA_ENTREGA = '" . date("Y-m-d") . "'
                                            , ESTADO = 'Sin Recepcionar'
                                            , ACTIVA = 1
                                            , ID_MONEDA_TRANSMITIDA_A_SAP = " . ($rowOrdenContratacion->ID_MONEDA == NULL ? 'NULL' : $rowOrdenContratacion->ID_MONEDA) . "
                                            , TIPO_IMPUTACION = '" . $tipoImputacion . "'
                                            $sqlInsertZTLIOrig";
                            $bd->ExecSQL($sqlInsert);

                            //INCREMENTO EN 10 EL NUMERO DE LINEA
                            $UltimoNumeroLinea = $UltimoNumeroLinea + 10;
                        endwhile;//FIN CREAMOS UNA LINEA POR MOVIMIENTO
                    endif;// FIN BUSCO LINEAS DE TIPO RECOGIDAS EN PROVEEDOR SIN PEDIDO CONOCIDO

                endif; //FIN ORDEN TRANSPORTE DE MODELO TRANSPORTE Segundo

            endwhile;//FIN CONTRATACIONES CON PROVEEDORES CON GESTION TRANSPORTE

        endif;//LA OT YA TIENE PEDIDOS DE SERVICIOS
    }

    function getTipoImputacionSAP($idMovimientoSalidaLineaRelacionadaMueveMaterial = "", $idPedidoEntradaLineaRelacionadaRecepcionaMaterial = "",
                                  $idMovimientoEntradaLineaRelacionadaRecepcionaMaterial = "", $idElementoImputacion = "", $idOrdenTrabajo = "",
                                  $posicionRecepcionada = "")
    {
        global $bd;

        $rowBloqueoG  = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO", "G");
        $rowBloqueoXG = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO", "XG");

        if ($idMovimientoSalidaLineaRelacionadaMueveMaterial != ""):
            $rowMovSalLin = $bd->VerReg("MOVIMIENTO_SALIDA_LINEA", "ID_MOVIMIENTO_SALIDA_LINEA", $idMovimientoSalidaLineaRelacionadaMueveMaterial);
            $rowPedSal    = $bd->VerReg("PEDIDO_SALIDA", "ID_PEDIDO_SALIDA", $rowMovSalLin->ID_PEDIDO_SALIDA);

            $rowAlmacen = $bd->VerReg("ALMACEN", "ID_ALMACEN", $rowMovSalLin->ID_ALMACEN);
            $rowCentro  = $bd->VerReg("CENTRO", "ID_CENTRO", $rowAlmacen->ID_CENTRO);

        elseif ($idPedidoEntradaLineaRelacionadaRecepcionaMaterial != ""):
            $rowPedEntLin = $bd->VerReg("PEDIDO_ENTRADA_LINEA", "ID_PEDIDO_ENTRADA_LINEA", $idPedidoEntradaLineaRelacionadaRecepcionaMaterial);
            $rowPedEnt    = $bd->VerReg("PEDIDO_ENTRADA", "ID_PEDIDO_ENTRADA", $rowPedEntLin->ID_PEDIDO_ENTRADA);

            if ($rowPedEntLin->ID_CENTRO != NULL):
                $rowCentro = $bd->VerReg("CENTRO", "ID_CENTRO", $rowPedEntLin->ID_CENTRO);
            else:
                $rowAlmacen = $bd->VerReg("ALMACEN", "ID_ALMACEN", $rowPedEntLin->ID_ALMACEN);
                $rowCentro  = $bd->VerReg("CENTRO", "ID_CENTRO", $rowAlmacen->ID_CENTRO);
            endif;


        elseif ($idMovimientoEntradaLineaRelacionadaRecepcionaMaterial != ""):
            $rowMovEntLin = $bd->VerReg("MOVIMIENTO_ENTRADA_LINEA", "ID_MOVIMIENTO_ENTRADA_LINEA", $idMovimientoEntradaLineaRelacionadaRecepcionaMaterial);
            $rowPedEntLin = $bd->VerReg("PEDIDO_ENTRADA_LINEA", "ID_PEDIDO_ENTRADA_LINEA", $rowMovEntLin->ID_PEDIDO_LINEA);
            $rowPedEnt    = $bd->VerReg("PEDIDO_ENTRADA", "ID_PEDIDO_ENTRADA", $rowPedEntLin->ID_PEDIDO_ENTRADA);

        endif;

        $tipoImputacion = '';

        if ($posicionRecepcionada == 1):
            $tipoImputacion = "O";
        else:
            if ($idMovimientoSalidaLineaRelacionadaMueveMaterial != ""):
                if (($rowCentro->SPV == 1)
                    || ($posicionRecepcionada == 1)
                    || ($rowPedSal->TIPO_PEDIDO == 'Venta')
                    || (($rowPedSal->TIPO_PEDIDO == 'Material Estropeado a Proveedor' || $rowPedSal->TIPO_PEDIDO == 'Material Estropeado Entre Proveedores' || $rowPedSal->TIPO_PEDIDO == 'Componentes a Proveedor') && ($rowMovSalLin->ID_TIPO_BLOQUEO == $rowBloqueoG->ID_TIPO_BLOQUEO || $rowMovSalLin->ID_TIPO_BLOQUEO == $rowBloqueoXG->ID_TIPO_BLOQUEO))
                    || ($rowPedSal->TIPO_PEDIDO_SAP == 'ZTRA' || $rowPedSal->TIPO_PEDIDO_SAP == 'ZTRB' || $rowPedSal->TIPO_PEDIDO_SAP == 'ZTRH')):
                    $tipoImputacion = "O";
                elseif (($rowPedSal->TIPO_PEDIDO_SAP == 'ZTRC' || $rowPedSal->TIPO_PEDIDO_SAP == 'ZTRD' || $rowPedSal->TIPO_PEDIDO_SAP == 'ZTRE' || $rowPedSal->TIPO_PEDIDO_SAP == 'ZTRG')
                    || (($rowPedSal->TIPO_PEDIDO == 'Material Estropeado a Proveedor' || $rowPedSal->TIPO_PEDIDO == 'Material Estropeado Entre Proveedores' || $rowPedSal->TIPO_PEDIDO == 'Componentes a Proveedor') && ($rowMovSalLin->ID_TIPO_BLOQUEO != $rowBloqueoG->ID_TIPO_BLOQUEO && $rowMovSalLin->ID_TIPO_BLOQUEO != $rowBloqueoXG->ID_TIPO_BLOQUEO))):
                    $tipoImputacion = "C";
                endif;
            elseif ($idPedidoEntradaLineaRelacionadaRecepcionaMaterial != "" || $idMovimientoEntradaLineaRelacionadaRecepcionaMaterial != ""):
                if (($rowPedEnt->TIPO_PEDIDO == 'Garantía')
                    || ($rowCentro->SPV == 1)
                    || ($posicionRecepcionada == 1)):
                    $tipoImputacion = "O";
                elseif ($rowPedEnt->TIPO_PEDIDO == 'Compra' || $rowPedEnt->TIPO_PEDIDO == 'Reparación' || $rowPedEnt->TIPO_PEDIDO == 'Devolución a Proveedor'):
                    $tipoImputacion = "C";
                endif;
            elseif ($idElementoImputacion != ""):
                $rowElementoImputacion = $bd->VerReg("ELEMENTO_IMPUTACION", "ID_ELEMENTO_IMPUTACION", $idElementoImputacion);
                $tipoImputacion        = $rowElementoImputacion->INDICADOR_IMPUTACION;
            elseif ($idOrdenTrabajo != ""):
                $tipoImputacion = "P";
            endif;
        endif;

        //SI FORMA PARTE DE UNA REPARACION MULTIPROVEEDOR
        if ($idMovimientoSalidaLineaRelacionadaMueveMaterial != ""):
            //BUSCO LA LINEA DE LA REPARACION MULTIPROVEEDOR
            $rowRepMulLin = $bd->VerRegRest("REPARACION_MULTIPROVEEDOR_LINEA", "ID_MOVIMIENTO_SALIDA_LINEA = $rowMovSalLin->ID_MOVIMIENTO_SALIDA_LINEA AND PEDIDO_SAP IS NULL AND BAJA = 0", "No");

            //BUSCO LA REPARACION MULTIPROVEEDOR
            if ($rowRepMulLin != false):
                $rowRepMul = $bd->VerReg("REPARACION_MULTIPROVEEDOR", "ID_REPARACION_MULTIPROVEEDOR", $rowRepMulLin->ID_REPARACION_MULTIPROVEEDOR);

                //BUSCO EL TIPO DE GARANTIA SAP NUMERO 3 - Garantia de Proveedor
                $rowTipoGarantiaSAP = $bd->VerReg("TIPO_GARANTIA_SAP", "TIPO_GARANTIA_SAP", "3");

                //BUSCO LAS CONSULTAS DE GARANTIAS
                $rowConsultaGarantia3 = $bd->VerRegRest("CONSULTA_GARANTIAS_SAP", "ID_ORDEN_TRABAJO_MOVIMIENTO = $rowRepMul->ID_ORDEN_TRABAJO_MOVIMIENTO AND ASUME_GARANTIA = 1 AND GARANTIA = " . REPARABLE_GARANTIA . " AND ID_TIPO_GARANTIA_SAP = " . $rowTipoGarantiaSAP->ID_TIPO_GARANTIA_SAP, "No");

                //SI EXISTE EL REGISTRO
                if ($rowConsultaGarantia3 != false):
                    //BUSCO EL ALMACEN DE DESTINO DE LA LINEA DEL MOVIMIENTO DE SALIDA
                    $rowAlmacenDestino = $bd->VerReg("ALMACEN", "ID_ALMACEN", $rowMovSalLin->ID_ALMACEN_DESTINO);

                    //SI COINCIDE EL PROVEEDOR CON EL DE GARANTIA, LA IMOUTACION IRA A CECO
                    if ($rowAlmacenDestino->ID_PROVEEDOR == $rowConsultaGarantia3->ID_PROVEEDOR_GARANTIA):
                        $tipoImputacion = "C";
                    endif;
                endif;
            endif;
        endif;

        //FIN SI FORMA PARTE DE UNA REPARACION MULTIPROVEEDOR

        return $tipoImputacion;
    }

    function actualizarTipoImputacionSAP($idPedidoEntradaLinea)
    {
        global $bd;

        $rowPedEntLin        = $bd->VerReg("PEDIDO_ENTRADA_LINEA", "ID_PEDIDO_ENTRADA_LINEA", $idPedidoEntradaLinea);
        $sqlImporteMinimo    = "SELECT S.IMPORTE_MINIMO
                            FROM PEDIDO_ENTRADA PE 
                            INNER JOIN ORDEN_CONTRATACION OC ON OC.ID_ORDEN_CONTRATACION = PE.ID_ORDEN_CONTRATACION 
                            INNER JOIN ORDEN_TRANSPORTE OT ON OT.ID_ORDEN_TRANSPORTE = OC.ID_ORDEN_TRANSPORTE
                            INNER JOIN CENTRO C ON C.ID_CENTRO = OT.ID_CENTRO_CONTRATANTE
                            INNER JOIN SOCIEDAD S ON S.ID_SOCIEDAD = C.ID_SOCIEDAD
                            WHERE PE.ID_PEDIDO_ENTRADA = $rowPedEntLin->ID_PEDIDO_ENTRADA AND PE.BAJA = 0";
        $resultImporteMinimo = $bd->ExecSQL($sqlImporteMinimo);
        $rowImporteMinimo    = $bd->SigReg($resultImporteMinimo);

        if ($rowPedEntLin->ESTADO == 'Recepcionada'):
            return $rowPedEntLin->TIPO_IMPUTACION;
        else:
            if ($rowPedEntLin->IMPORTE_TRANSMITIDO_A_SAP_SOCIEDAD_CONTRATANTE < $rowImporteMinimo->IMPORTE_MINIMO):
                return 'O';
            else:
                return $rowPedEntLin->TIPO_IMPUTACION;
            endif;
        endif;
    }

    /**
     * @param $idOrdenContratacion ORDEN DE CONTRATACION
     * @param $importeModificado IMPORTE MOFIDICADO DE LA ORDEN DE CONTRATACION O IMPORTE DE LA NUEVA CONTRATACION
     * GENERA LOS PEDIDOS ZTLG, ZTLI Y ZTLF NECESARIOS EN FUNCION DEL IMPORTE Y DE LAS ORDENES DE RECOGIDA
     * DEVUELVE $arrPedidosServicios, UN ARRAY CON LOS PEDIDOS GENERADOS
     */
    function generarZTLFicticiosModificacionImporte($idOrdenContratacion, $importeModificado)
    {
        global $bd;

        //ARRAY A DEVOLVER
        $arrPedidosServicios = array();

        //BUSCO LA ORDEN DE CONTRATACION
        $rowOrdenContratacion = $bd->VerReg("ORDEN_CONTRATACION", "ID_ORDEN_CONTRATACION", $idOrdenContratacion);

        //BUSCO LA ORDEN DE TRANSPORTE
        $rowOrdenTransporte = $bd->VerReg("ORDEN_TRANSPORTE", "ID_ORDEN_TRANSPORTE", $rowOrdenContratacion->ID_ORDEN_TRANSPORTE);

        //LLAMO A UNA FUNCION U OTRA EN FUNCION DEL MODELO DE TRANSPORTE DE LA ORDEN DE TRANSPORTE
        if ($rowOrdenTransporte->MODELO_TRANSPORTE == 'Primero'): //SI LA ORDEN DE TRANSPORTE ES DE MODELO DE TRANSPORTE 'Primero'
            $arrPedidosServicios = $this->generarZTLFicticiosModificacionImporteConMaterialServicios($idOrdenContratacion, $importeModificado);
        elseif ($rowOrdenTransporte->MODELO_TRANSPORTE == 'Segundo'): //SI LA ORDEN DE TRANSPORTE ES DE MODELO DE TRANSPORTE 'Segundo'
            $arrPedidosServicios = $this->generarZTLFicticiosModificacionImporteConMaterialReal($idOrdenContratacion, $importeModificado);
        endif;

        //DEVULEVO EL ARRAY CON LOS PEDIDOS DE SERVICIOS DEVUELTOS
        return $arrPedidosServicios;
    }

    /**
     * @param $idOrdenContratacion ORDEN DE CONTRATACION
     * @param $importeModificado IMPORTE MOFIDICADO DE LA ORDEN DE CONTRATACION
     * PARA EL MODELO DE TRANSPORTE 'Primero' GENERA LOS PEDIDOS ZTLG, ZTLI Y ZTLF NECESARIOS EN FUNCION DEL IMPORTE Y DE LAS ORDENES DE RECOGIDA
     * DEVUELVE $arrPedidosServicios, UN ARRAY CON LOS PEDIDOS GENERADOS
     */
    function generarZTLFicticiosModificacionImporteConMaterialServicios($idOrdenContratacion, $importeModificado)
    {
        global $bd;
        global $auxiliar;
        global $importe;
        global $html;
        global $administrador;

        //ARRAY A DEVOLVER
        $arrPedidosServicios = array();

        //BUSCO LA ORDEN DE CONTRATACION
        $rowOrdenContratacion = $bd->VerReg("ORDEN_CONTRATACION", "ID_ORDEN_CONTRATACION", $idOrdenContratacion);

        //REALIZO OPERACIONES CON EL IMPORTE
        if ($importeModificado > 0):
            $importePositivo = true;
        else:
            $importePositivo = false;
        endif;

        //PASO EL IMPORTE A VALOR ABSOLUTO
        $importeModificado = abs((float)$importeModificado);

        //ME GUARDO EL IMPORTE ORIGINAL EN VALOR ABSOLUTO
        $importeModificadoOriginal = $importeModificado;

        //VARIABLE PARA ACUMULAR EL IMPORTE ASIGNADO A LOS DIFERENTES PEDIDOS
        $importeModificadoAcumulado = 0;

        //EXTRAIGO LA UNIDAD DE GRAMO (PESO)
        $rowUnidadPesoGramo = $bd->VerReg("UNIDAD", "UNIDAD", 'G');

        //VARIABLE PARA ACUMULAR EL PESO ASIGNADO A LOS DIFERENTES PEDIDOS EN GRAMOS
        $pesoPedidosEnGramos = 0;

        //BUSCO LA ORDEN DE TRANSPORTE
        $rowOrdenTransporte = $bd->VerReg("ORDEN_TRANSPORTE", "ID_ORDEN_TRANSPORTE", $rowOrdenContratacion->ID_ORDEN_TRANSPORTE);

        //BUSCO LOS PEDIDOS ZTLG DE TIPO ZTLG 'Trasmitir ZTL Orden Transporte' DE LA ORDEN DE TRANSPORTE
        $sqlBuscaZTLG    = "SELECT *
                             FROM PEDIDO_ENTRADA PE
                             WHERE PE.TIPO_PEDIDO = 'Servicios' AND PE.TIPO_PEDIDO_SAP = 'ZTLG' AND PE.TIPO_ZTLG = 'Trasmitir ZTL Orden Transporte' AND PE.ID_ORDEN_TRANSPORTE = $rowOrdenContratacion->ID_ORDEN_TRANSPORTE AND PE.ID_ORDEN_CONTRATACION = $rowOrdenContratacion->ID_ORDEN_CONTRATACION AND BAJA = 0";
        $resultBuscaZTLG = $bd->ExecSQL($sqlBuscaZTLG);
        while ($rowZTLG = $bd->SigReg($resultBuscaZTLG)):
            //CALCULO EL PESO EN PORCENTAJE DEL PEDIDO DENTRO DE LA ORDEN DE TRANSPORTE
            $porcentajePeso = $importe->getPorcentajePesoPedidoEntradaRespectoOrdenTransporte($rowZTLG->ID_PEDIDO_ENTRADA, $rowOrdenTransporte->ID_ORDEN_TRANSPORTE);

            //CALCULO EL IMPORTE A LLEVARSE ESTE PEDIDO
            $importeModificadoPedido = $auxiliar->formatoMoneda(($importeModificadoOriginal * $porcentajePeso), $rowOrdenContratacion->ID_MONEDA);

            //CREO LA CABECERA DEL PEDIDO, ESTARA EN LIBERACION HASTA QUE NOS CAIGA DESDE SAP PARA PODER CONFIRMARLO
            $sqlInsert = "INSERT INTO PEDIDO_ENTRADA SET
                            PEDIDO_SAP = ''
                            , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                            , TIPO_PEDIDO = 'Servicios'
                            , TIPO_PEDIDO_SAP = '" . ($importePositivo == true ? "ZTLG" : "ZTLF") . "'
                            , INDICADOR_LIBERACION = 'En Liberación'
                            , ID_PROVEEDOR = $rowOrdenContratacion->ID_PROVEEDOR
                            , ESTADO = 'Creado'
                            , ID_ORDEN_TRANSPORTE = $rowOrdenContratacion->ID_ORDEN_TRANSPORTE
                            , FECHA_CREACION = '" . date("Y-m-d") . "'
                            , ID_ORDEN_CONTRATACION = $rowOrdenContratacion->ID_ORDEN_CONTRATACION
                            , PESO = $rowZTLG->PESO
                            , ID_UNIDAD_PESO = $rowZTLG->ID_UNIDAD_PESO";
            if ($importePositivo == true):
                $sqlInsert = $sqlInsert . ", TIPO_ZTLG = 'Modificacion Importes'";
            elseif ($importePositivo == false):
                $sqlInsert = $sqlInsert . ", TIPO_ZTLI = 'Modificacion Importes'";
            endif;
            $bd->ExecSQL($sqlInsert);
            $idPedido = $bd->IdAsignado();

            //AÑADO EL PEDIDO CREADO AL ARRAY DE PEDIDOS
            $arrPedidosServicios[] = $idPedido;

            //DAMOS VALOR A PEDIDO_SAP YA QUE ES CLAVE UNICA
            $sqlUpdate = "UPDATE PEDIDO_ENTRADA SET
                            PEDIDO_SAP = 'SGA" . str_pad((string)$idPedido, 7, "0", STR_PAD_LEFT) . "'
                            WHERE ID_PEDIDO_ENTRADA = $idPedido";
            $bd->ExecSQL($sqlUpdate);

            //LOG MOVIMIENTOS
            $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Creación", "Pedido entrada", $idPedido, "");

            //CALCULAMOS CUANTO IMPORTE MANDAMOS ENTRE TODAS LAS LINEAS DEL PEDIDO
            $importeOriginalPedido = 0;
            $sqlImporteOriginal    = "SELECT SUM(IMPORTE_TRANSMITIDO_A_SAP) AS IMPORTE_ORIGINAL
                                       FROM PEDIDO_ENTRADA_LINEA PEL
                                       WHERE PEL.ID_PEDIDO_ENTRADA = $rowZTLG->ID_PEDIDO_ENTRADA AND PEL.BAJA = 0 AND PEL.INDICADOR_BORRADO IS NULL";
            $resultImporteOriginal = $bd->ExecSQL($sqlImporteOriginal);
            if (($resultImporteOriginal != false) && ($bd->numRegs($resultImporteOriginal) == 1)):
                $rowImporteOriginal    = $bd->SigReg($resultImporteOriginal);
                $importeOriginalPedido = $rowImporteOriginal->IMPORTE_ORIGINAL;
            endif;

            //SI EL IMPORTE DEL PEDIDO ES CERO DAREMOS ERROR
            if ($importeOriginalPedido == 0):
                $html->PagError("ErrorImportePedidoZTLG");
            endif;

            //ARRAY CON EL PESO EN LAS DIFERENTES UNIDADES
            $arrPesos = $auxiliar->convertirUnidades($rowZTLG->PESO, $rowZTLG->ID_UNIDAD_PESO);

            //ME GUARDO EL PESO DE LOS PEDIDOS ZTLG
            $pesoPedidosEnGramos = $pesoPedidosEnGramos + round((float)$arrPesos[$rowUnidadPesoGramo->ID_UNIDAD], 3);

            //VARIABLE PARA ACUMULAR EL IMPORTE ASIGNADO AL PEDIDO NUEVO
            $importePedidoAcumulado = 0;

            //INICIAMOS EL ULTIMO NUMERO DE LINEA
            $UltimoNumeroLinea = 10;

            //BUSCO LAS LINEAS DEL PEDIDO DE ENTRADA PARA CLONARLAS
            $sqlLineasPedido    = "SELECT *
                                    FROM PEDIDO_ENTRADA_LINEA PEL
                                    WHERE PEL.ID_PEDIDO_ENTRADA = $rowZTLG->ID_PEDIDO_ENTRADA AND PEL.BAJA = 0 AND PEL.INDICADOR_BORRADO IS NULL";
            $resultLineasPedido = $bd->ExecSQL($sqlLineasPedido);
            while ($rowLineaPedido = $bd->SigReg($resultLineasPedido)):
                //CALCULO EL PORCENTAJE DEL IMPORTE DE LA LINEA ORIGINAL
                $porcentajeLinea = $rowLineaPedido->IMPORTE_TRANSMITIDO_A_SAP / $importeOriginalPedido;

                //CALCULO EL IMPORTE DE LA LINEA
                $importeModificadoLinea = $auxiliar->formatoMoneda(($importeModificadoPedido * $porcentajeLinea), $rowOrdenContratacion->ID_MONEDA);
                if (($bd->NumRegs($resultLineasPedido) * 10) == $UltimoNumeroLinea)://SI ES LA ULTIMA LINEA, ASIGNO EL IMPORTE PENDIENTE DEL PEDIDO A ESTA LINEA
                    $importeModificadoLinea = $importeModificadoPedido - $importePedidoAcumulado;
                elseif (($importeModificadoLinea + $importePedidoAcumulado) > $importeModificadoPedido)://SI EL IMPOTE VA A SUPERAR LO CORRESPONDIENTE AL PEDIDO, ASIGNO LA DIFERENCIA
                    $importeModificadoLinea = $importeModificadoPedido - $importePedidoAcumulado;
                endif;

                //CREO LA LINEA DE PEDIDO ENTRADA CLON DE LA ORIGINAL, CON LA SALVEDAD DEL IMPORTE MODIFICADO POR EL QUE LE CORRESPONDE
                $sqlInsert = "INSERT INTO PEDIDO_ENTRADA_LINEA SET
                                ID_PEDIDO_ENTRADA = $idPedido
                                , ID_ELEMENTO_IMPUTACION = " . ($rowLineaPedido->ID_ELEMENTO_IMPUTACION == NULL ? 'NULL' : $rowLineaPedido->ID_ELEMENTO_IMPUTACION) . "
                                , CODIGO_SELECCIONADO_ELEMENTO_IMPUTACION = '" . $rowLineaPedido->CODIGO_SELECCIONADO_ELEMENTO_IMPUTACION . "'
                                , ID_ORDEN_TRABAJO_RELACIONADO = " . ($rowLineaPedido->ID_ORDEN_TRABAJO_RELACIONADO == NULL ? 'NULL' : $rowLineaPedido->ID_ORDEN_TRABAJO_RELACIONADO) . "
                                , ID_ALMACEN = NULL
                                , ID_CENTRO = $rowLineaPedido->ID_CENTRO
                                , ID_MATERIAL = $rowLineaPedido->ID_MATERIAL
                                , ID_TIPO_BLOQUEO = NULL
                                , UNIDAD_SAP = $rowLineaPedido->UNIDAD_SAP
                                , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                , LINEA_PEDIDO_SAP = '" . str_pad((string)$UltimoNumeroLinea, 5, "0", STR_PAD_LEFT) . "'
                                , CANTIDAD = 1
                                , CANTIDAD_PDTE = 1
                                , NUMERO_CONTRATO =  '" . $rowLineaPedido->NUMERO_CONTRATO . "'
                                , NUMERO_CONTRATO_LINEA = '" . $rowLineaPedido->NUMERO_CONTRATO_LINEA . "'
                                , FECHA_ENTREGA = '" . date("Y-m-d") . "'
                                , IMPORTE_TRANSMITIDO_A_SAP = $importeModificadoLinea
                                , ID_MONEDA_TRANSMITIDA_A_SAP = $rowLineaPedido->ID_MONEDA_TRANSMITIDA_A_SAP
                                , ESTADO = 'Sin Recepcionar'";
                $bd->ExecSQL($sqlInsert);
                $idPedidoLinea = $bd->IdAsignado();

                //BUSCO LA LINEA DEL PEDIDO DE SERVICIOS PARA CLONARLA
                $rowLineaServicio = $bd->VerReg("PEDIDO_SERVICIO_LINEA", "ID_PEDIDO_ENTRADA_LINEA", $rowLineaPedido->ID_PEDIDO_ENTRADA_LINEA);

                //CREO LA LINEA DEL PEDIDO DE SERVICIOS
                $sqlInsert = "INSERT INTO PEDIDO_SERVICIO_LINEA SET
                                ID_PEDIDO_ENTRADA = $idPedido
                                , ID_PEDIDO_ENTRADA_LINEA = $idPedidoLinea
                                , ID_ORDEN_TRABAJO_MOVIMIENTO = " . ($rowLineaServicio->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? "NULL" : $rowLineaServicio->ID_ORDEN_TRABAJO_MOVIMIENTO) . "
                                , ID_EXPEDICION = $rowLineaServicio->ID_EXPEDICION
                                , ID_ORDEN_TRANSPORTE = $rowLineaServicio->ID_ORDEN_TRANSPORTE
                                , ID_MATERIAL = " . ($rowLineaServicio->ID_MATERIAL == NULL ? "NULL" : $rowLineaServicio->ID_MATERIAL) . "
                                , CANTIDAD = $rowLineaServicio->CANTIDAD";
                $bd->ExecSQL($sqlInsert);

                //DECREMENTO EL IMPORTE A ASIGNAR
                $importePedidoAcumulado = $importePedidoAcumulado + $importeModificadoLinea;

                //INCREMENTO EN 10 EL NUMERO DE LINEA
                $UltimoNumeroLinea = $UltimoNumeroLinea + 10;
            endwhile;

            //ACTUALIZO EL IMPORTE MODIFICADO
            $importeModificadoAcumulado = $importeModificadoAcumulado + $importePedidoAcumulado;
        endwhile;
        //FIN BUSCO LOS PEDIDOS ZTLG DE TIPO ZTLG 'Trasmitir ZTL Orden Transporte' DE LA ORDEN DE TRANSPORTE

        //ACTUALIZO EL IMPORTE MODIFICADO
        $importeModificado = $importeModificado - $importeModificadoAcumulado;

        //VUELVO A PONER EL IMPORTE MODIFICADO A CERO
        $importeModificadoAcumulado = 0;

        //SI EL IMPORTE MODIFICADO PENDIENTE ES MAYOR QUE CERO, COMPRUEBO SI ES NECESARIO CREAR UN PEDIDO ZTLI O ZTLF PARA LOGISITICA INVERSA
        if ($importeModificado > EPSILON_SISTEMA):
            //BUSCO LOS PEDIDOS ZTLI DE TIPO ZTLI 'Envio a Proveedor' DE LA ORDEN DE TRANSPORTE
            $sqlBuscaZTLI    = "SELECT *
                                 FROM PEDIDO_ENTRADA PE
                                 WHERE PE.TIPO_PEDIDO = 'Servicios' AND PE.TIPO_PEDIDO_SAP = 'ZTLI' AND PE.TIPO_ZTLI = 'Envio a Proveedor' AND PE.ID_ORDEN_TRANSPORTE = $rowOrdenContratacion->ID_ORDEN_TRANSPORTE AND PE.ID_ORDEN_CONTRATACION = $rowOrdenContratacion->ID_ORDEN_CONTRATACION AND BAJA = 0";
            $resultBuscaZTLI = $bd->ExecSQL($sqlBuscaZTLI);

            //DECLARO EL PEDIDO Y LINEA ZTLI O ZTLF A GENERAR
            $idPedidoZTLI      = NULL;
            $idLineaPedidoZTLI = NULL;

            while ($rowZTLI = $bd->SigReg($resultBuscaZTLI)):
                //CALCULO EL PESO EN PORCENTAJE DEL PEDIDO DENTRO DE LA ORDEN DE TRANSPORTE
                $porcentajePeso = $importe->getPorcentajePesoPedidoEntradaRespectoOrdenTransporte($rowZTLI->ID_PEDIDO_ENTRADA, $rowOrdenTransporte->ID_ORDEN_TRANSPORTE);

                //CALCULO EL IMPORTE A LLEVARSE ESTE PEDIDO
                $importeModificadoPedido = $auxiliar->formatoMoneda(($importeModificadoOriginal * $porcentajePeso), $rowOrdenContratacion->ID_MONEDA);

                //SI EL PEDIDO NO ESTA GENERADO, LO CREO
                if ($idPedidoZTLI == NULL):
                    //CREO LA CABECERA DEL PEDIDO, ESTARA EN LIBERACION HASTA QUE NOS CAIGA DESDE SAP PARA PODER CONFIRMARLO
                    $sqlInsert = "INSERT INTO PEDIDO_ENTRADA SET
                                PEDIDO_SAP = ''
                                , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                , TIPO_PEDIDO = 'Servicios'
                                , TIPO_PEDIDO_SAP = '" . ($importePositivo == true ? "ZTLI" : "ZTLE") . "'
                                , INDICADOR_LIBERACION = 'En Liberación'
                                , ID_PROVEEDOR = $rowOrdenContratacion->ID_PROVEEDOR
                                , ESTADO = 'Creado'
                                , ID_ORDEN_TRANSPORTE = $rowOrdenContratacion->ID_ORDEN_TRANSPORTE
                                , FECHA_CREACION = '" . date("Y-m-d") . "'
                                , ID_ORDEN_CONTRATACION = $rowOrdenContratacion->ID_ORDEN_CONTRATACION
                                , PESO = $rowZTLI->PESO
                                , ID_UNIDAD_PESO = $rowZTLI->ID_UNIDAD_PESO";
                    if ($importePositivo == true):
                        $sqlInsert = $sqlInsert . ", TIPO_ZTLI = 'Modificacion Importes'";
                    elseif ($importePositivo == false):
                        $sqlInsert = $sqlInsert . ", TIPO_ZTLE = 'Modificacion Importes'";
                    endif;
                    $bd->ExecSQL($sqlInsert);
                    $idPedido     = $bd->IdAsignado();
                    $idPedidoZTLI = $idPedido;

                    //AÑADO EL PEDIDO CREADO AL ARRAY DE PEDIDOS
                    $arrPedidosServicios[] = $idPedido;
                    //DAMOS VALOR A PEDIDO_SAP YA QUE ES CLAVE UNICA
                    $sqlUpdate = "UPDATE PEDIDO_ENTRADA SET
                                PEDIDO_SAP = 'SGA" . str_pad((string)$idPedido, 7, "0", STR_PAD_LEFT) . "'
                                WHERE ID_PEDIDO_ENTRADA = $idPedido";
                    $bd->ExecSQL($sqlUpdate);

                    //LOG MOVIMIENTOS
                    $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Creación", "Pedido entrada", $idPedido, "");
                else:
                    $sqlUpdate = "UPDATE PEDIDO_ENTRADA SET
                                  PESO = PESO + $rowZTLI->PESO
                                  WHERE ID_PEDIDO_ENTRADA = $idPedidoZTLI";
                    $bd->ExecSQL($sqlUpdate);
                endif;

                //ARRAY CON EL PESO EN LAS DIFERENTES UNIDADES
                $arrPesos = $auxiliar->convertirUnidades($rowZTLI->PESO, $rowZTLI->ID_UNIDAD_PESO);

                //ME GUARDO EL PESO DE LOS PEDIDOS ZTLI
                $pesoPedidosEnGramos = $pesoPedidosEnGramos + round((float)$arrPesos[$rowUnidadPesoGramo->ID_UNIDAD], 3);

                //CALCULAMOS CUANTO IMPORTE MANDAMOS ENTRE TODAS LAS LINEAS DEL PEDIDO
                $importeOriginalPedido = 0;
                $sqlImporteOriginal    = "SELECT SUM(IMPORTE_TRANSMITIDO_A_SAP) AS IMPORTE_ORIGINAL
                                           FROM PEDIDO_ENTRADA_LINEA PEL
                                           WHERE PEL.ID_PEDIDO_ENTRADA = $rowZTLI->ID_PEDIDO_ENTRADA AND PEL.BAJA = 0 AND PEL.INDICADOR_BORRADO IS NULL";
                $resultImporteOriginal = $bd->ExecSQL($sqlImporteOriginal);
                if (($resultImporteOriginal != false) && ($bd->numRegs($resultImporteOriginal) == 1)):
                    $rowImporteOriginal    = $bd->SigReg($resultImporteOriginal);
                    $importeOriginalPedido = $rowImporteOriginal->IMPORTE_ORIGINAL;
                endif;

                //SI EL IMPORTE DEL PEDIDO ES CERO DAREMOS ERROR
                if ($importeOriginalPedido == 0):
                    $html->PagError("ErrorImportePedidoZTLI");
                endif;

                //VARIABLE PARA ACUMULAR EL IMPORTE ASIGNADO AL PEDIDO NUEVO
                $importePedidoAcumulado = 0;

                //INICIAMOS EL ULTIMO NUMERO DE LINEA
                $UltimoNumeroLinea = 10;

                //BUSCO LAS LINEAS DEL PEDIDO DE ENTRADA PARA CLONARLAS
                $sqlLineasPedido    = "SELECT *
                                        FROM PEDIDO_ENTRADA_LINEA PEL
                                        WHERE PEL.ID_PEDIDO_ENTRADA = $rowZTLI->ID_PEDIDO_ENTRADA AND PEL.BAJA = 0 AND PEL.INDICADOR_BORRADO IS NULL";
                $resultLineasPedido = $bd->ExecSQL($sqlLineasPedido);
                while ($rowLineaPedido = $bd->SigReg($resultLineasPedido)):
                    //CALCULO EL PORCENTAJE DEL IMPORTE DE LA LINEA ORIGINAL
                    $porcentajeLinea = $rowLineaPedido->IMPORTE_TRANSMITIDO_A_SAP / $importeOriginalPedido;

                    //CALCULO EL IMPORTE DE LA LINEA
                    $importeModificadoLinea = $auxiliar->formatoMoneda(($importeModificadoPedido * $porcentajeLinea), $rowOrdenContratacion->ID_MONEDA);
                    if (($bd->NumRegs($resultLineasPedido) * 10) == $UltimoNumeroLinea)://SI ES LA ULTIMA LINEA, ASIGNO EL IMPORTE PENDIENTE DEL PEDIDO A ESTA LINEA
                        $importeModificadoLinea = $importeModificadoPedido - $importePedidoAcumulado;
                    elseif (($importeModificadoLinea + $importePedidoAcumulado) > $importeModificadoPedido)://SI EL IMPOTE VA A SUPERAR LO CORRESPONDIENTE AL PEDIDO, ASIGNO LA DIFERENCIA
                        $importeModificadoLinea = $importeModificadoPedido - $importePedidoAcumulado;
                    endif;

                    //A NIVEL DE LINEA ENVIAMOS EL MATERIAL DE SERVICIOS

                    //BUSCAMOS EL SERVICIO
                    $rowServicio = $bd->VerReg("SERVICIO", "ID_SERVICIO", $rowOrdenContratacion->ID_SERVICIO);

                    //BUSCAMOS EL MATERIAL
                    $rowMaterial = $bd->VerReg("MATERIAL", "ID_MATERIAL", $rowServicio->ID_MATERIAL);

                    //SI LA LINEA DEL PEDIDO NO ESTA GENERADA, LA CREO
                    if ($idLineaPedidoZTLI == NULL):
                        //CREO LA LINEA DE PEDIDO ENTRADA CLON DE LA ORIGINAL, CON LA SALVEDAD DEL IMPORTE MODIFICADO POR EL QUE LE CORRESPONDE
                        $sqlInsert = "INSERT INTO PEDIDO_ENTRADA_LINEA SET
                                        ID_PEDIDO_ENTRADA = $idPedido
                                        , ID_ALMACEN = NULL
                                        , ID_CENTRO = $rowOrdenTransporte->ID_CENTRO_CONTRATANTE
                                        , ID_MATERIAL = $rowMaterial->ID_MATERIAL
                                        , ID_TIPO_BLOQUEO = NULL
                                        , UNIDAD_SAP = $rowMaterial->ID_UNIDAD_COMPRA
                                        , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                        , LINEA_PEDIDO_SAP = '" . str_pad((string)$UltimoNumeroLinea, 5, "0", STR_PAD_LEFT) . "'
                                        , CANTIDAD_SAP = 1
                                        , CANTIDAD = 1
                                        , CANTIDAD_PDTE = 1
                                        , NUMERO_CONTRATO =  '" . $bd->escapeCondicional($rowOrdenContratacion->NUMERO_CONTRATO) . "'
                                        , NUMERO_CONTRATO_LINEA = '" . $bd->escapeCondicional($rowOrdenContratacion->NUMERO_CONTRATO_LINEA) . "'
                                        , FECHA_ENTREGA = '" . date("Y-m-d") . "'
                                        , IMPORTE_TRANSMITIDO_A_SAP = $importeModificadoLinea
                                        , ID_MONEDA_TRANSMITIDA_A_SAP = $rowLineaPedido->ID_MONEDA_TRANSMITIDA_A_SAP
                                        , ESTADO = 'Sin Recepcionar'";
                        $bd->ExecSQL($sqlInsert);
                        $idPedidoLinea     = $bd->IdAsignado();
                        $idLineaPedidoZTLI = $idPedidoLinea;
                    else:
                        $sqlUpdate = "UPDATE PEDIDO_ENTRADA_LINEA SET
                                      IMPORTE_TRANSMITIDO_A_SAP = IMPORTE_TRANSMITIDO_A_SAP + $importeModificadoLinea
                                      WHERE ID_PEDIDO_ENTRADA_LINEA = $idLineaPedidoZTLI";
                        $bd->ExecSQL($sqlUpdate);
                    endif;

                    //DECREMENTO EL IMPORTE A ASIGNAR
                    $importePedidoAcumulado = $importePedidoAcumulado + $importeModificadoLinea;

                    //INCREMENTO EN 10 EL NUMERO DE LINEA
                    $UltimoNumeroLinea = $UltimoNumeroLinea + 10;
                endwhile;

                //ACTUALIZO EL IMPORTE MODIFICADO
                $importeModificadoAcumulado = $importeModificadoAcumulado + $importePedidoAcumulado;
            endwhile;
            //FIN BUSCO LOS PEDIDOS ZTLI DE TIPO ZTLI 'Envio a Proveedor' DE LA ORDEN DE TRANSPORTE
        endif;
        //FIN SI EL IMPORTE MODIFICADO PENDIENTE ES MAYOR QUE CERO, COMPRUEBO SI ES NECESARIO CREAR UN PEDIDO ZTLI O ZTLF PARA LOGISITICA INVERSA

        //ACTUALIZO EL IMPORTE MODIFICADO A ASIGNAR AL PEDIDO ZTLG O ZTLF
        $importeModificado = $importeModificado - $importeModificadoAcumulado;

        //SI EL IMPORTE MODIFICADO PENDIENTE ES MAYOR QUE CERO, CREO UN PEDIDO ZTLG O ZTLF SEGUN CORRESPONDA PARA LOGISTICA DIRECTA
        if ($importeModificado > EPSILON_SISTEMA):
            //ARRAY CON EL PESO EN LAS DIFERENTES UNIDADES
            $arrPesos = $auxiliar->convertirUnidades($rowOrdenTransporte->PESO, $rowOrdenTransporte->ID_UNIDAD_PESO);

            //CALCULO EL PESO EN GRAMOS DE ESTE PEDIDO
            $pesoPedido = $arrPesos[$rowUnidadPesoGramo->ID_UNIDAD] - $pesoPedidosEnGramos;

            //CREO LA CABECERA DEL PEDIDO, ESTARA EN LIBERACION HASTA QUE NOS CAIGA DESDE SAP PARA PODER CONFIRMARLO
            $sqlInsert = "INSERT INTO PEDIDO_ENTRADA SET
                            PEDIDO_SAP = ''
                            , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                            , TIPO_PEDIDO = 'Servicios'
                            , TIPO_PEDIDO_SAP = '" . ($importePositivo == true ? "ZTLG" : "ZTLF") . "'
                            , INDICADOR_LIBERACION = 'En Liberación'
                            , ID_PROVEEDOR = $rowOrdenContratacion->ID_PROVEEDOR
                            , ESTADO = 'Creado'
                            , ID_ORDEN_TRANSPORTE = $rowOrdenContratacion->ID_ORDEN_TRANSPORTE
                            , FECHA_CREACION = '" . date("Y-m-d") . "'
                            , ID_ORDEN_CONTRATACION = $rowOrdenContratacion->ID_ORDEN_CONTRATACION
                            , PESO = $pesoPedido
                            , ID_UNIDAD_PESO = $rowUnidadPesoGramo->ID_UNIDAD";
            if ($importePositivo == true):
                $sqlInsert = $sqlInsert . ", TIPO_ZTLG = 'Modificacion Importes'";
            elseif ($importePositivo == false):
                $sqlInsert = $sqlInsert . ", TIPO_ZTLI = 'Modificacion Importes'";
            endif;
            $bd->ExecSQL($sqlInsert);
            $idPedido = $bd->IdAsignado();

            //AÑADO EL PEDIDO CREADO AL ARRAY DE PEDIDOS
            $arrPedidosServicios[] = $idPedido;

            //DAMOS VALOR A PEDIDO_SAP YA QUE ES CLAVE UNICA
            $sqlUpdate = "UPDATE PEDIDO_ENTRADA SET
                            PEDIDO_SAP = 'SGA" . str_pad((string)$idPedido, 7, "0", STR_PAD_LEFT) . "'
                            WHERE ID_PEDIDO_ENTRADA = $idPedido";
            $bd->ExecSQL($sqlUpdate);

            // LOG MOVIMIENTOS
            $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Creación", "Pedido entrada", $idPedido, "");

            //A NIVEL DE LINEA ENVIAMOS EL MATERIAL DE SERVICIOS

            //BUSCAMOS EL SERVICIO
            $rowServicio = $bd->VerReg("SERVICIO", "ID_SERVICIO", $rowOrdenContratacion->ID_SERVICIO);

            //BUSCAMOS EL MATERIAL
            $rowMaterial = $bd->VerReg("MATERIAL", "ID_MATERIAL", $rowServicio->ID_MATERIAL);

            //CREO LA LINEA DEL PEDIDO DE ENTRADA
            $sqlInsert = "INSERT INTO PEDIDO_ENTRADA_LINEA SET
                            ID_PEDIDO_ENTRADA = $idPedido
                            , ID_ALMACEN = NULL
                            , ID_CENTRO = $rowOrdenTransporte->ID_CENTRO_CONTRATANTE
                            , ID_MATERIAL = $rowMaterial->ID_MATERIAL
                            , ID_TIPO_BLOQUEO = NULL
                            , UNIDAD_SAP = $rowMaterial->ID_UNIDAD_COMPRA
                            , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                            , LINEA_PEDIDO_SAP = '" . str_pad((string)"10", 5, "0", STR_PAD_LEFT) . "'
                            , CANTIDAD_SAP = 1
                            , CANTIDAD = 1
                            , CANTIDAD_PDTE = 1
                            , NUMERO_CONTRATO =  '" . $bd->escapeCondicional($rowOrdenContratacion->NUMERO_CONTRATO) . "'
                            , NUMERO_CONTRATO_LINEA = '" . $bd->escapeCondicional($rowOrdenContratacion->NUMERO_CONTRATO_LINEA) . "'
                            , FECHA_ENTREGA = '" . date("Y-m-d") . "'
                            , IMPORTE_TRANSMITIDO_A_SAP = $importeModificado
                            , ID_MONEDA_TRANSMITIDA_A_SAP = $rowOrdenContratacion->ID_MONEDA
                            , ESTADO = 'Sin Recepcionar'";
            $bd->ExecSQL($sqlInsert);
        endif;
        //FIN SI EL IMPORTE MODIFICADO PENDIENTE ES MAYOR QUE CERO, CREO UN PEDIDO ZTLG O ZTLF SEGUN CORRESPONDA PARA LOGISTICA DIRECTA

        //DEVULEVO EL ARRAY CON LOS PEDIDOS DE SERVICIOS DEVUELTOS
        return $arrPedidosServicios;
    }

    /**
     * @param $idOrdenContratacion ORDEN DE CONTRATACION
     * @param $importeModificado IMPORTE MOFIDICADO DE LA ORDEN DE CONTRATACION
     * PARA EL MODELO DE TRANSPORTE 'Segundo' GENERA LOS PEDIDOS ZTLI Y ZTLE NECESARIOS EN FUNCION DEL IMPORTE Y DE LAS ORDENES DE RECOGIDA
     * DEVUELVE $arrPedidosServicios, UN ARRAY CON LOS PEDIDOS GENERADOS
     */
    function generarZTLFicticiosModificacionImporteConMaterialReal($idOrdenContratacion, $importeModificado)
    {
        global $bd;
        global $html;
        global $administrador;
        global $mat;
        global $stock_compartido;

        //ARRAY A DEVOLVER
        $arrPedidosServicios = array();

        //BUSCO LA ORDEN DE CONTRATACION
        $rowOrdenContratacion = $bd->VerReg("ORDEN_CONTRATACION", "ID_ORDEN_CONTRATACION", $idOrdenContratacion);

        //SI HAY PEDIDOS DE SERVICIOS PENDIENTES DE TRANSMITIR NO ES NECESARIO CREAR NUEVO PEDIDOS, VALE CON DEVOLVER LOS ENCONTRADOS
        $sqlPedidosServiciosNoEnviados    = "SELECT PE.ID_PEDIDO_ENTRADA 
                                          FROM PEDIDO_ENTRADA_LINEA PEL 
                                          INNER JOIN PEDIDO_ENTRADA PE ON PE.ID_PEDIDO_ENTRADA = PEL.ID_PEDIDO_ENTRADA 
                                          WHERE PE.ID_ORDEN_CONTRATACION = $rowOrdenContratacion->ID_ORDEN_CONTRATACION AND PE.TIPO_PEDIDO_SAP IN ('ZTLI', 'ZTLF') AND ((PE.TIPO_ZTLI = 'Importes en Posicion') OR (PE.TIPO_ZTLF = 'Importes en Posicion')) AND PE.BAJA = 0 AND PEL.ENVIADO_SAP = 0 AND PEL.BAJA = 0";
        $resultPedidosServiciosNoEnviados = $bd->ExecSQL($sqlPedidosServiciosNoEnviados);
        if (($resultPedidosServiciosNoEnviados != false) && ($bd->NumRegs($resultPedidosServiciosNoEnviados) > 0)):
            while ($rowPedidoServicioNoEnviado = $bd->SigReg($resultPedidosServiciosNoEnviados)):
                $arrPedidosServicios[] = $rowPedidoServicioNoEnviado->ID_PEDIDO_ENTRADA;
            endwhile;

            return $arrPedidosServicios;
        endif;

        //REALIZO OPERACIONES CON EL IMPORTE
        if ($importeModificado > 0):
            $importePositivo = true;
        else:
            $importePositivo = false;
        endif;

        //SI EL IMPORTE ES NEGATIVO, ES UNA DISMINUCIÓN DEL IMPORTE DE LA CONTRATACIÓN (ZTLF), CON LO QUE DEBE IMPUTAR A GASTO (ORDEN)
        $tipoImputacion = "";
        if (!$importePositivo):
            $tipoImputacion = "O";
        endif;

        //BUSCO LA ORDEN DE TRANSPORTE
        $rowOrdenTransporte = $bd->VerReg("ORDEN_TRANSPORTE", "ID_ORDEN_TRANSPORTE", $rowOrdenContratacion->ID_ORDEN_TRANSPORTE);

        //BUSCO EL SERVICIO
        $rowServicio = $bd->VerReg("SERVICIO", "ID_SERVICIO", $rowOrdenContratacion->ID_SERVICIO);

        //BUSCO EL MATERIAL SERVICIOS
        $rowMaterialServicios = $bd->VerReg("MATERIAL", "ID_MATERIAL", $rowServicio->ID_MATERIAL, "No");
        $rowUnidadCompra      = $bd->VerReg("UNIDAD", "ID_UNIDAD", $rowMaterialServicios->ID_UNIDAD_COMPRA);

        //BUSCO LAS RECOGIDAS FUERA DE SISTEMA, AGRUPADAS POR ELEMENTO DE IMPUTACION
        $sqlRecogidasFueraSistema    = "SELECT ID_ELEMENTO_IMPUTACION, CODIGO_SELECCIONADO_ELEMENTO_IMPUTACION
                                         FROM EXPEDICION
                                         WHERE TIPO_ORDEN_RECOGIDA = 'Operaciones fuera de Sistema' AND ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND BAJA = 0
                                         GROUP BY ID_ELEMENTO_IMPUTACION, CODIGO_SELECCIONADO_ELEMENTO_IMPUTACION";
        $resultRecogidasFueraSistema = $bd->ExecSQL($sqlRecogidasFueraSistema);//echo($sqlRecogidasFueraSistema . "<hr>");

        //BUSCO LAS RECOGIDAS DE OPERACIONES EN PARQUE, AGRUPADAS POR ORDEN DE TRABAJO
        $sqlRecogidasParque    = "SELECT OT.ID_ORDEN_TRABAJO, OT.ID_CENTRO
                                   FROM EXPEDICION E
                                   INNER JOIN ORDEN_TRABAJO_MOVIMIENTO OTM ON OTM.ID_EXPEDICION = E.ID_EXPEDICION
                                   INNER JOIN ORDEN_TRABAJO OT ON OT.ID_ORDEN_TRABAJO = OTM.ID_ORDEN_TRABAJO
                                   WHERE E.ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND E.BAJA = 0
                                   GROUP BY OTM.ID_ORDEN_TRABAJO";
        $resultRecogidasParque = $bd->ExecSQL($sqlRecogidasParque);//echo($sqlRecogidasParque . "<hr>");

        //SI TIENE RECOGIDAS FUERA DE SISTEMA O EN PARQUE, CREO EL PEDIDO DE SERVICIOS PARA ESA CONTRATACION
        if (($bd->NumRegs($resultRecogidasFueraSistema) > 0) || ($bd->NumRegs($resultRecogidasParque) > 0)):

            //CREO LA CABECERA DEL PEDIDO, ESTARA EN LIBERACION HASTA QUE NOS CAIGA DESDE SAP PARA PODER CONFIRMARLO
            $sqlInsert = "INSERT INTO PEDIDO_ENTRADA SET
                            PEDIDO_SAP = ''
                            , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                            , TIPO_PEDIDO = 'Servicios' 
                            , TIPO_PEDIDO_SAP = '" . ($importePositivo == true ? 'ZTLI' : 'ZTLF') . "'";
            if ($importePositivo == true):
                $sqlInsert = $sqlInsert . ", TIPO_ZTLI = 'Importes en Posicion'";
            elseif ($importePositivo == false):
                $sqlInsert = $sqlInsert . ", TIPO_ZTLF = 'Importes en Posicion'";
            endif;
            $sqlInsert = $sqlInsert . ", INDICADOR_LIBERACION = 'En Liberación'
                                       , ID_PROVEEDOR = $rowOrdenContratacion->ID_PROVEEDOR
                                       , ESTADO = 'Creado'
                                       , ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE
                                       , FECHA_CREACION = '" . date("Y-m-d") . "'
                                       , ID_ORDEN_CONTRATACION = $rowOrdenContratacion->ID_ORDEN_CONTRATACION";
            $bd->ExecSQL($sqlInsert);
            $idPedidoZTL = $bd->IdAsignado();

            //AÑADO EL PEDIDO CREADO AL ARRAY DE PEDIDOS
            $arrPedidosServicios[] = $idPedidoZTL;

            //DAMOS VALOR A PEDIDO_SAP YA QUE ES CLAVE UNICA
            $sqlUpdate = "UPDATE PEDIDO_ENTRADA SET
                            PEDIDO_SAP = 'SGA" . str_pad((string)$idPedidoZTL, 7, "0", STR_PAD_LEFT) . "'
                            WHERE ID_PEDIDO_ENTRADA = $idPedidoZTL";
            $bd->ExecSQL($sqlUpdate);

            // LOG MOVIMIENTOS
            $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Creación", "Pedido entrada", $idPedidoZTL, "");

            //INICIAMOS EL ULTIMO NUMERO DE LINEA
            $UltimoNumeroLinea = 10;

            //RECOGIDAS FUERA DE SISTEMA
            while ($rowRecogidasFueraSistema = $bd->SigReg($resultRecogidasFueraSistema)):

                //COMPROBAMOS QUE TENGA RELLENO EL ELEMENTO DE IMPUTACION
                $html->PagErrorCondicionado($rowRecogidasFueraSistema->ID_ELEMENTO_IMPUTACION, "==", "", "RecogidaSinElementoImputacion");

                //TIPO IMPUTACION
                if ($importePositivo == true): //SI EL IMPORTE ES POSITIVO MIRO EL TIPO DE IMPUTACION. SI ES NEGATIVO VA SIEMPRE A ORDEN.
                    $tipoImputacion = $this->getTipoImputacionSAP("", "", "", $rowRecogidasFueraSistema->ID_ELEMENTO_IMPUTACION, "", 0);
                endif;

                //CREO LA LINEA DEL PEDIDO DE ENTRADA
                $sqlInsert = "INSERT INTO PEDIDO_ENTRADA_LINEA SET
                                ID_PEDIDO_ENTRADA = $idPedidoZTL
                                , ID_ELEMENTO_IMPUTACION = $rowRecogidasFueraSistema->ID_ELEMENTO_IMPUTACION
                                , CODIGO_SELECCIONADO_ELEMENTO_IMPUTACION = '" . $bd->escapeCondicional($rowRecogidasFueraSistema->CODIGO_SELECCIONADO_ELEMENTO_IMPUTACION) . "'
                                , MATERIAL_REAL_ZTL_RECEPCIONADO = 0 
                                , ID_ALMACEN = NULL
                                , ID_CENTRO = $rowOrdenTransporte->ID_CENTRO_CONTRATANTE
                                , ID_MATERIAL = $rowMaterialServicios->ID_MATERIAL
                                , ID_TIPO_BLOQUEO = NULL
                                , UNIDAD_SAP = $rowMaterialServicios->ID_UNIDAD_COMPRA
                                , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                , LINEA_PEDIDO_SAP = '" . str_pad((string)$UltimoNumeroLinea, 5, "0", STR_PAD_LEFT) . "'
                                , CANTIDAD = 1
                                , CANTIDAD_PDTE = 1
                                , NUMERO_CONTRATO =  '" . $bd->escapeCondicional($rowOrdenContratacion->NUMERO_CONTRATO) . "'
                                , NUMERO_CONTRATO_LINEA = '" . $bd->escapeCondicional($rowOrdenContratacion->NUMERO_CONTRATO_LINEA) . "'
                                , FECHA_ENTREGA = '" . date("Y-m-d") . "'
                                , ESTADO = 'Sin Recepcionar'
                                , TIPO_IMPUTACION = '" . $tipoImputacion . "'
                                , ACTIVA = 1";
                $bd->ExecSQL($sqlInsert);

                $idPedidoEntradaLinea = $bd->IdAsignado();

                //INCREMENTO EN 10 EL NUMERO DE LINEA
                $UltimoNumeroLinea = $UltimoNumeroLinea + 10;

                //BUSCAMOS LAS RECOGIDAS A LAS QUE HACE REFERENCIA LA AGRUPACION Y LAS GUARDAMOS COMO LINEAS DE SERVICIOS
                $sqlRecogidasFueraSistemaDesglose    = "SELECT ID_EXPEDICION
                                                         FROM EXPEDICION
                                                         WHERE TIPO_ORDEN_RECOGIDA = 'Operaciones fuera de Sistema' AND ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND BAJA = 0 AND ID_ELEMENTO_IMPUTACION = $rowRecogidasFueraSistema->ID_ELEMENTO_IMPUTACION AND CODIGO_SELECCIONADO_ELEMENTO_IMPUTACION = '" . $bd->escapeCondicional($rowRecogidasFueraSistema->CODIGO_SELECCIONADO_ELEMENTO_IMPUTACION) . "'";
                $resultRecogidasFueraSistemaDesglose = $bd->ExecSQL($sqlRecogidasFueraSistemaDesglose);
                while ($rowRecogidasFueraSistemaDesglose = $bd->SigReg($resultRecogidasFueraSistemaDesglose)):
                    //CREAMOS LAS LINEAS DE SERVICIOS, AL SER SIN MATERIAL , LO PONEMOS A NULL y LA CANTIDAD a 0
                    $sqlInsert = "INSERT INTO PEDIDO_SERVICIO_LINEA SET
                                    ID_PEDIDO_ENTRADA = $idPedidoZTL
                                    , ID_PEDIDO_ENTRADA_LINEA = $idPedidoEntradaLinea
                                    , ID_EXPEDICION = $rowRecogidasFueraSistemaDesglose->ID_EXPEDICION
                                    , ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE
                                    , ID_MATERIAL = NULL
                                    , CANTIDAD = 0";
                    $bd->ExecSQL($sqlInsert);
                endwhile;
            endwhile;// FIN RECOGIDAS FUERA DE SISTEMA, DESGLOSE DE LINEAS A LA TABLA PEDIDO_SERVICIO_LINEA

            //RECOGIDAS EN PARQUE
            while ($rowRecogidasParque = $bd->SigReg($resultRecogidasParque)):

                //TIPO IMPUTACION
                if ($importePositivo == true): //SI EL IMPORTE ES POSITIVO MIRO EL TIPO DE IMPUTACION. SI ES NEGATIVO VA SIEMPRE A ORDEN.
                    $tipoImputacion = $this->getTipoImputacionSAP("", "", "", "", $rowRecogidasParque->ID_ORDEN_TRABAJO, 0);
                endif;

                //CREO LA LINEA DEL PEDIDO DE ENTRADA, AGRUPADA POR ORDEN DE TRABAJO Y SE ENVIA EL CENTRO CONTRATANTE
                $sqlInsert = "INSERT INTO PEDIDO_ENTRADA_LINEA SET
                                ID_PEDIDO_ENTRADA = $idPedidoZTL
                                , ID_ORDEN_TRABAJO_RELACIONADO = $rowRecogidasParque->ID_ORDEN_TRABAJO
                                , MATERIAL_REAL_ZTL_RECEPCIONADO = 0 
                                , ID_ALMACEN = NULL
                                , ID_CENTRO = $rowRecogidasParque->ID_CENTRO
                                , ID_MATERIAL = $rowMaterialServicios->ID_MATERIAL
                                , ID_TIPO_BLOQUEO = NULL
                                , UNIDAD_SAP = $rowMaterialServicios->ID_UNIDAD_COMPRA
                                , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                , LINEA_PEDIDO_SAP = '" . str_pad((string)$UltimoNumeroLinea, 5, "0", STR_PAD_LEFT) . "'
                                , CANTIDAD = 1
                                , CANTIDAD_PDTE = 1
                                , NUMERO_CONTRATO =  '" . $bd->escapeCondicional($rowOrdenContratacion->NUMERO_CONTRATO) . "'
                                , NUMERO_CONTRATO_LINEA = '" . $bd->escapeCondicional($rowOrdenContratacion->NUMERO_CONTRATO_LINEA) . "'
                                , FECHA_ENTREGA = '" . date("Y-m-d") . "'
                                , ESTADO = 'Sin Recepcionar'
                                , TIPO_IMPUTACION = '" . $tipoImputacion . "'
                                , ACTIVA = 1";
                $bd->ExecSQL($sqlInsert);

                $idPedidoEntradaLinea = $bd->IdAsignado();

                //INCREMENTO EN 10 EL NUMERO DE LINEA
                $UltimoNumeroLinea = $UltimoNumeroLinea + 10;

                //BUSCO LAS ORDEN TRABAJO MOVIMIENTO DE ESA ORDEN DE TRABAJO
                $sqlOTMRecogida    = "SELECT OTM.ID_ORDEN_TRABAJO_MOVIMIENTO, OTM.ID_MATERIAL, OTM.CANTIDAD, OTM.ID_EXPEDICION
                                       FROM EXPEDICION E
                                       INNER JOIN ORDEN_TRABAJO_MOVIMIENTO OTM ON OTM.ID_EXPEDICION = E.ID_EXPEDICION
                                       WHERE E.ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND E.BAJA = 0 AND OTM.ID_ORDEN_TRABAJO = $rowRecogidasParque->ID_ORDEN_TRABAJO";
                $resultOTMRecogida = $bd->ExecSQL($sqlOTMRecogida);

                //LINEAS OTM RELACIONADAS CON LA ORDEN TRABAJO, CREO UNA LINEA POR OTM
                while ($rowOTMRecogida = $bd->SigReg($resultOTMRecogida)):

                    //CREAMOS LA LINEA DE SERVICIOS
                    $sqlInsert = "INSERT INTO PEDIDO_SERVICIO_LINEA SET
                                    ID_PEDIDO_ENTRADA = $idPedidoZTL
                                    , ID_PEDIDO_ENTRADA_LINEA = $idPedidoEntradaLinea
                                    , ID_ORDEN_TRABAJO_MOVIMIENTO = $rowOTMRecogida->ID_ORDEN_TRABAJO_MOVIMIENTO
                                    , ID_EXPEDICION = $rowOTMRecogida->ID_EXPEDICION
                                    , ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE
                                    , ID_MATERIAL = $rowOTMRecogida->ID_MATERIAL
                                    , CANTIDAD = $rowOTMRecogida->CANTIDAD";
                    $bd->ExecSQL($sqlInsert);
                endwhile;//FIN LINEAS OTM RELACIONADAS CON LA ORDEN TRABAJO
            endwhile;// FIN RECOGIDAS EN PARQUE
        endif;//FIN TIENE RECOGIDAS FUERA DE SISTEMA O EN PARQUE

        //BUSCO LINEAS DE MOVIMIENTOS DE SALIDA EN FUNCION DE LO FILTRADO
        $sqlPedidoZTLI    = "SELECT MSL.ID_MOVIMIENTO_SALIDA_LINEA, MSL.ID_ALMACEN, MSL.ID_MATERIAL, MSL.ID_TIPO_BLOQUEO, PSL.ID_UNIDAD, MSL.CANTIDAD, MSL.ESTADO 
                              FROM MOVIMIENTO_SALIDA_LINEA MSL
                              INNER JOIN EXPEDICION E ON E.ID_EXPEDICION = MSL.ID_EXPEDICION
                              INNER JOIN PEDIDO_SALIDA PS ON PS.ID_PEDIDO_SALIDA = MSL.ID_PEDIDO_SALIDA
                              INNER JOIN PEDIDO_SALIDA_LINEA PSL ON PSL.ID_PEDIDO_SALIDA_LINEA = MSL.ID_PEDIDO_SALIDA_LINEA
                              WHERE E.ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND MSL.LINEA_ANULADA = 0 AND MSL.BAJA = 0 AND E.TIPO_ORDEN_RECOGIDA = 'Recogida en Almacen'";
        $resultPedidoZTLI = $bd->ExecSQL($sqlPedidoZTLI);
        if ($bd->NumRegs($resultPedidoZTLI) > 0):

            //CREO EL PEDIDO ZTLI
            $sqlInsert = "INSERT INTO PEDIDO_ENTRADA SET
                            PEDIDO_SAP = ''
                            , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                            , TIPO_PEDIDO = 'Servicios' 
                            , TIPO_PEDIDO_SAP = '" . ($importePositivo == true ? 'ZTLI' : 'ZTLF') . "'";
            if ($importePositivo == true):
                $sqlInsert = $sqlInsert . ", TIPO_ZTLI = 'Importes en Posicion'";
            elseif ($importePositivo == false):
                $sqlInsert = $sqlInsert . ", TIPO_ZTLF = 'Importes en Posicion'";
            endif;
            $sqlInsert = $sqlInsert . ", INDICADOR_LIBERACION = 'En Liberación'
                                       , ID_PROVEEDOR = $rowOrdenContratacion->ID_PROVEEDOR
                                       , ESTADO = 'Creado'
                                       , ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE
                                       , FECHA_CREACION = '" . date("Y-m-d") . "'
                                       , ID_ORDEN_CONTRATACION = $rowOrdenContratacion->ID_ORDEN_CONTRATACION";
            $bd->ExecSQL($sqlInsert);
            $idPedidoZTL = $bd->IdAsignado();

            //AÑADO EL PEDIDO CREADO AL ARRAY DE PEDIDOS
            $arrPedidosServicios[] = $idPedidoZTL;

            //DAMOS VALOR A PEDIDO_SAP YA QUE ES CLAVE UNICA
            $sqlUpdate = "UPDATE PEDIDO_ENTRADA SET
                            PEDIDO_SAP = 'SGA" . str_pad((string)$idPedidoZTL, 7, "0", STR_PAD_LEFT) . "'
                            WHERE ID_PEDIDO_ENTRADA = $idPedidoZTL";
            $bd->ExecSQL($sqlUpdate);

            // LOG MOVIMIENTOS
            $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Creación", "Pedido entrada", $idPedidoZTL, "");

            //INICIAMOS EL ULTIMO NUMERO DE LINEA
            $UltimoNumeroLinea = 10;

            //CREAMOS UNA LINEA POR MOVIMIENTO
            while ($rowPedidoZTLI = $bd->SigReg($resultPedidoZTLI)):

                //SE OBTIENE EL PEL ORIGINAL
                $sqlInsertZTLIOrig = "";

                $sqlPedEntLinOrig    = "SELECT PEL.ID_PEDIDO_ENTRADA_LINEA, PE.PEDIDO_SAP
                                     FROM PEDIDO_ENTRADA_LINEA PEL
                                     INNER JOIN PEDIDO_ENTRADA PE ON PE.ID_PEDIDO_ENTRADA = PEL.ID_PEDIDO_ENTRADA
                                     WHERE PEL.ID_MOVIMIENTO_SALIDA_LINEA_RELACIONADA_MUEVE_MATERIAL = $rowPedidoZTLI->ID_MOVIMIENTO_SALIDA_LINEA AND PE.ID_ORDEN_CONTRATACION = $rowOrdenContratacion->ID_ORDEN_CONTRATACION AND PEL.ID_LINEA_ZTLI_ORIGINAL IS NULL AND PEL.BAJA = 0";
                $resultPedEntLinOrig = $bd->ExecSQL($sqlPedEntLinOrig);
                if ($bd->NumRegs($resultPedEntLinOrig) > 0):
                    $rowPedEntLinOrig = $bd->SigReg($resultPedEntLinOrig);
                    if (substr((string)$rowPedEntLinOrig->PEDIDO_SAP, 0, 3) != 'SGA'):
                        $sqlInsertZTLIOrig = " , ID_LINEA_ZTLI_ORIGINAL = $rowPedEntLinOrig->ID_PEDIDO_ENTRADA_LINEA ";
                    endif;
                endif;

                //DETERMINO SI ESTA RECEPCIONADO EN FUNCION DEL ESTADO DE LA LINEA DEL MOVIMIENTO DE SALIDA
                if (($rowPedidoZTLI->ESTADO == 'Expedido') || ($rowPedidoZTLI->ESTADO == 'Recepcionado')):
                    $posicionRecepcionada = 1;
                else:
                    $posicionRecepcionada = 0;
                endif;

                //BUSCO EL ALMACEN DE ORIGEN
                $rowAlmacen   = $bd->VerReg("ALMACEN", "ID_ALMACEN", $rowPedidoZTLI->ID_ALMACEN);
                $idCentroZTLI = $rowOrdenTransporte->ID_CENTRO_CONTRATANTE;

                //ALMACEN DESTINO
                if ($rowAlmacen->INFORMAR_MATERIAL_TRANSPORTE == 1):
                    if ($stock_compartido->Obtener_Almacen_Mantenedor($rowAlmacen->ID_CENTRO_FISICO) != NULL):
                        $rowAlmacenMantenedor = $bd->VerReg("ALMACEN", "ID_ALMACEN", $stock_compartido->Obtener_Almacen_Mantenedor($rowAlmacen->ID_CENTRO_FISICO));
                        $idCentroZTLI         = $rowAlmacenMantenedor->ID_CENTRO;
                    endif;
                    $rowMaterial = $bd->VerReg("MATERIAL", "ID_MATERIAL", $rowMaterialServicios->ID_MATERIAL);
                else:
                    $rowMaterial = $bd->VerReg("MATERIAL", "ID_MATERIAL", $rowPedidoZTLI->ID_MATERIAL);
                endif;

                //NOS GUARDAMOS LA CANTIDAD SAP
                $cantidad_sap = $mat->cantUnidadCompra($rowPedidoZTLI->ID_MATERIAL, $rowPedidoZTLI->CANTIDAD);

                //TIPO IMPUTACION
                if ($importePositivo == true): //SI EL IMPORTE ES POSITIVO MIRO EL TIPO DE IMPUTACION. SI ES NEGATIVO VA SIEMPRE A ORDEN.
                    $tipoImputacion = $this->getTipoImputacionSAP($rowPedidoZTLI->ID_MOVIMIENTO_SALIDA_LINEA, "", "", "", "", $posicionRecepcionada);
                endif;

                //CREO LA LINEA DEL PEDIDO DE ENTRADA
                $sqlInsert = "INSERT INTO PEDIDO_ENTRADA_LINEA SET
                                ID_PEDIDO_ENTRADA = $idPedidoZTL
                                , ID_MOVIMIENTO_SALIDA_LINEA_RELACIONADA_MUEVE_MATERIAL = $rowPedidoZTLI->ID_MOVIMIENTO_SALIDA_LINEA
                                , MATERIAL_REAL_ZTL_RECEPCIONADO = $posicionRecepcionada 
                                , ID_ALMACEN = NULL
                                , ID_CENTRO = $idCentroZTLI
                                , ID_MATERIAL = $rowMaterial->ID_MATERIAL
                                , ID_TIPO_BLOQUEO = " . ($rowPedidoZTLI->ID_TIPO_BLOQUEO == NULL ? 'NULL' : $rowPedidoZTLI->ID_TIPO_BLOQUEO) . "
                                , UNIDAD_SAP = $rowMaterial->ID_UNIDAD_COMPRA
                                , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                , LINEA_PEDIDO_SAP = '" . str_pad((string)$UltimoNumeroLinea, 5, "0", STR_PAD_LEFT) . "'
                                , CANTIDAD_SAP = $cantidad_sap
                                , CANTIDAD = $rowPedidoZTLI->CANTIDAD
                                , CANTIDAD_PDTE = $rowPedidoZTLI->CANTIDAD
                                , NUMERO_CONTRATO =  '" . $bd->escapeCondicional($rowOrdenContratacion->NUMERO_CONTRATO) . "'
                                , NUMERO_CONTRATO_LINEA = '" . $bd->escapeCondicional($rowOrdenContratacion->NUMERO_CONTRATO_LINEA) . "'
                                , FECHA_ENTREGA = '" . date("Y-m-d") . "'
                                , ESTADO = 'Sin Recepcionar'
                                , TIPO_IMPUTACION = '" . $tipoImputacion . "'
                                , ACTIVA = 1
                                $sqlInsertZTLIOrig";
                $bd->ExecSQL($sqlInsert);

                //INCREMENTO EN 10 EL NUMERO DE LINEA
                $UltimoNumeroLinea = $UltimoNumeroLinea + 10;
            endwhile;//FIN CREAMOS UNA LINEA POR MOVIMIENTO
        endif;// FIN BUSCO LINEAS DE MOVIMIENTOS DE SALIDA EN FUNCION DE LO FILTRADO

        if ($rowOrdenTransporte->MODELO_TRANSPORTE == 'Segundo'): //ORDEN TRANSPORTE DE MODELO TRANSPORTE Segundo
            //BUSCO LINEAS DE TIPO RECOGIDAS EN PROVEEDOR CON PEDIDO CONOCIDO
            $sqlPedidoZTLI    = "SELECT EPC.ID_PEDIDO_ENTRADA_LINEA, E.ID_EXPEDICION, PEL.ID_ALMACEN, PEL.ID_MATERIAL, PEL.ID_TIPO_BLOQUEO, (EPC.CANTIDAD - EPC.CANTIDAD_NO_SERVIDA) AS CANTIDAD_TOTAL 
                                  FROM EXPEDICION_PEDIDO_CONOCIDO EPC 
                                  INNER JOIN EXPEDICION E ON E.ID_EXPEDICION = EPC.ID_EXPEDICION 
                                  INNER JOIN PEDIDO_ENTRADA_LINEA PEL ON PEL.ID_PEDIDO_ENTRADA_LINEA = EPC.ID_PEDIDO_ENTRADA_LINEA 
                                  WHERE E.ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND E.TIPO_ORDEN_RECOGIDA = 'Recogida en Proveedor' AND E.SUBTIPO_ORDEN_RECOGIDA = 'Con Pedido Conocido' AND EPC.BAJA = 0";
            $resultPedidoZTLI = $bd->ExecSQL($sqlPedidoZTLI);
            if ($bd->NumRegs($resultPedidoZTLI) > 0):

                //CREO EL PEDIDO ZTLI
                $sqlInsert = "INSERT INTO PEDIDO_ENTRADA SET
                                PEDIDO_SAP = ''
                                , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                , TIPO_PEDIDO = 'Servicios' 
                                , TIPO_PEDIDO_SAP = '" . ($importePositivo == true ? 'ZTLI' : 'ZTLF') . "'";
                if ($importePositivo == true):
                    $sqlInsert = $sqlInsert . ", TIPO_ZTLI = 'Importes en Posicion'";
                elseif ($importePositivo == false):
                    $sqlInsert = $sqlInsert . ", TIPO_ZTLF = 'Importes en Posicion'";
                endif;
                $sqlInsert = $sqlInsert . ", INDICADOR_LIBERACION = 'En Liberación'
                                           , ID_PROVEEDOR = $rowOrdenContratacion->ID_PROVEEDOR
                                           , ESTADO = 'Creado'
                                           , ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE
                                           , FECHA_CREACION = '" . date("Y-m-d") . "'
                                           , ID_ORDEN_CONTRATACION = $rowOrdenContratacion->ID_ORDEN_CONTRATACION";
                $bd->ExecSQL($sqlInsert);
                $idPedidoZTL = $bd->IdAsignado();

                //AÑADO EL PEDIDO CREADO AL ARRAY DE PEDIDOS
                $arrPedidosServicios[] = $idPedidoZTL;

                //DAMOS VALOR A PEDIDO_SAP YA QUE ES CLAVE UNICA
                $sqlUpdate = "UPDATE PEDIDO_ENTRADA SET
                                PEDIDO_SAP = 'SGA" . str_pad((string)$idPedidoZTL, 7, "0", STR_PAD_LEFT) . "'
                                WHERE ID_PEDIDO_ENTRADA = $idPedidoZTL";
                $bd->ExecSQL($sqlUpdate);

                // LOG MOVIMIENTOS
                $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Creación", "Pedido entrada", $idPedidoZTL, "");

                //INICIAMOS EL ULTIMO NUMERO DE LINEA
                $UltimoNumeroLinea = 10;

                //CREAMOS UNA LINEA POR MOVIMIENTO
                while ($rowPedidoZTLI = $bd->SigReg($resultPedidoZTLI)):

                    //SE OBTIENE EL PEL ORIGINAL
                    $sqlInsertZTLIOrig = "";

                    $sqlPedEntLinOrig    = "SELECT PEL.ID_PEDIDO_ENTRADA_LINEA, PE.PEDIDO_SAP
                                     FROM PEDIDO_ENTRADA_LINEA PEL
                                     INNER JOIN PEDIDO_ENTRADA PE ON PE.ID_PEDIDO_ENTRADA = PEL.ID_PEDIDO_ENTRADA
                                     WHERE PEL.ID_PEDIDO_ENTRADA_LINEA_RELACIONADA_RECEPCIONA_MATERIAL = $rowPedidoZTLI->ID_PEDIDO_ENTRADA_LINEA AND PE.ID_ORDEN_CONTRATACION = $rowOrdenContratacion->ID_ORDEN_CONTRATACION AND PEL.ID_LINEA_ZTLI_ORIGINAL IS NULL AND PEL.BAJA = 0";
                    $resultPedEntLinOrig = $bd->ExecSQL($sqlPedEntLinOrig);
                    if ($bd->NumRegs($resultPedEntLinOrig) > 0):
                        $rowPedEntLinOrig = $bd->SigReg($resultPedEntLinOrig);
                        if (substr((string)$rowPedEntLinOrig->PEDIDO_SAP, 0, 3) != 'SGA'):
                            $sqlInsertZTLIOrig = " , ID_LINEA_ZTLI_ORIGINAL = $rowPedEntLinOrig->ID_PEDIDO_ENTRADA_LINEA ";
                        endif;
                    endif;

                    //POSICION RECEPCIONADA POR DEFECTO NO
                    $posicionRecepcionada = 0;

                    //BUSCO LA LINEA DEL MOVIMIENTO DE ENTRADA
                    $GLOBALS["NotificaErrorPorEmail"] = "No";
                    $rowMovimientoEntradaLinea        = $bd->VerRegRest("MOVIMIENTO_ENTRADA_LINEA", "ID_PEDIDO_LINEA = $rowPedidoZTLI->ID_PEDIDO_ENTRADA_LINEA AND ID_EXPEDICION_ENTREGA = $rowPedidoZTLI->ID_EXPEDICION AND LINEA_ANULADA = 0 AND BAJA = 0", "No");
                    unset($GLOBALS["NotificaErrorPorEmail"]);

                    //SI EXISTE LA LINEA DEL MOVIMIENTO DE ENTRADA
                    if ($rowMovimientoEntradaLinea != false):
                        //BUSCO EL MOVIMIENTO DE ENTRADA
                        $rowMovimientoEntrada = $bd->VerReg("MOVIMIENTO_ENTRADA", "ID_MOVIMIENTO_ENTRADA", $rowMovimientoEntradaLinea->ID_MOVIMIENTO_ENTRADA);
                        if (($rowMovimientoEntrada->ESTADO != 'En Proceso') && ($rowMovimientoEntrada->PENDIENTE_CONFIRMACION_SAP == 0)):
                            $posicionRecepcionada = 1;
                        endif;
                    endif;

                    //BUSCO EL ALMACEN DE ORIGEN
                    $rowAlmacen   = $bd->VerReg("ALMACEN", "ID_ALMACEN", $rowPedidoZTLI->ID_ALMACEN);
                    $idCentroZTLI = $rowOrdenTransporte->ID_CENTRO_CONTRATANTE;

                    //ALMACEN DESTINO
                    if ($rowAlmacen->INFORMAR_MATERIAL_TRANSPORTE == 1):
                        if ($stock_compartido->Obtener_Almacen_Mantenedor($rowAlmacen->ID_CENTRO_FISICO) != NULL):
                            $rowAlmacenMantenedor = $bd->VerReg("ALMACEN", "ID_ALMACEN", $stock_compartido->Obtener_Almacen_Mantenedor($rowAlmacen->ID_CENTRO_FISICO));
                            $idCentroZTLI         = $rowAlmacenMantenedor->ID_CENTRO;
                        endif;
                        $rowMaterial = $bd->VerReg("MATERIAL", "ID_MATERIAL", $rowMaterialServicios->ID_MATERIAL);
                    else:
                        $rowMaterial = $bd->VerReg("MATERIAL", "ID_MATERIAL", $rowPedidoZTLI->ID_MATERIAL);
                    endif;

                    //NOS GUARDAMOS LA CANTIDAD SAP
                    $cantidad_sap = $mat->cantUnidadCompra($rowPedidoZTLI->ID_MATERIAL, $rowPedidoZTLI->CANTIDAD_TOTAL);

                    //TIPO IMPUTACION
                    if ($importePositivo == true): //SI EL IMPORTE ES POSITIVO MIRO EL TIPO DE IMPUTACION. SI ES NEGATIVO VA SIEMPRE A ORDEN.
                        $tipoImputacion = $this->getTipoImputacionSAP("", $rowPedidoZTLI->ID_PEDIDO_ENTRADA_LINEA, "", "", "", $posicionRecepcionada);
                    endif;

                    //CREO LA LINEA DEL PEDIDO DE ENTRADA
                    $sqlInsert = "INSERT INTO PEDIDO_ENTRADA_LINEA SET
                                    ID_PEDIDO_ENTRADA = $idPedidoZTL
                                    , ID_PEDIDO_ENTRADA_LINEA_RELACIONADA_RECEPCIONA_MATERIAL = $rowPedidoZTLI->ID_PEDIDO_ENTRADA_LINEA 
                                    , MATERIAL_REAL_ZTL_RECEPCIONADO = $posicionRecepcionada 
                                    , ID_ALMACEN = NULL
                                    , ID_CENTRO = $idCentroZTLI
                                    , ID_MATERIAL = $rowMaterial->ID_MATERIAL
                                    , ID_TIPO_BLOQUEO = " . ($rowPedidoZTLI->ID_TIPO_BLOQUEO == NULL ? 'NULL' : $rowPedidoZTLI->ID_TIPO_BLOQUEO) . "
                                    , UNIDAD_SAP = $rowMaterial->ID_UNIDAD_COMPRA
                                    , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                    , LINEA_PEDIDO_SAP = '" . str_pad((string)$UltimoNumeroLinea, 5, "0", STR_PAD_LEFT) . "'
                                    , CANTIDAD_SAP = $cantidad_sap
                                    , CANTIDAD = $rowPedidoZTLI->CANTIDAD_TOTAL
                                    , CANTIDAD_PDTE = $rowPedidoZTLI->CANTIDAD_TOTAL
                                    , NUMERO_CONTRATO =  '" . $bd->escapeCondicional($rowOrdenContratacion->NUMERO_CONTRATO) . "'
                                    , NUMERO_CONTRATO_LINEA = '" . $bd->escapeCondicional($rowOrdenContratacion->NUMERO_CONTRATO_LINEA) . "'
                                    , FECHA_ENTREGA = '" . date("Y-m-d") . "'
                                    , ESTADO = 'Sin Recepcionar'
                                    , TIPO_IMPUTACION = '" . $tipoImputacion . "'
                                    , ACTIVA = 1
                                    $sqlInsertZTLIOrig";
                    $bd->ExecSQL($sqlInsert);

                    //INCREMENTO EN 10 EL NUMERO DE LINEA
                    $UltimoNumeroLinea = $UltimoNumeroLinea + 10;
                endwhile;//FIN CREAMOS UNA LINEA POR MOVIMIENTO
            endif;// FIN BUSCO LINEAS DE TIPO RECOGIDAS EN PROVEEDOR CON PEDIDO CONOCIDO

            //BUSCO LINEAS DE TIPO RECOGIDAS EN PROVEEDOR SIN PEDIDO CONOCIDO
            $sqlPedidoZTLI    = "SELECT MEL.ID_MOVIMIENTO_ENTRADA, MEL.ID_MOVIMIENTO_ENTRADA_LINEA, MEL.ID_PEDIDO_LINEA, MEL.ID_UBICACION, MEL.ID_MATERIAL, MEL.ID_TIPO_BLOQUEO, MEL.CANTIDAD 
                                  FROM MOVIMIENTO_ENTRADA_LINEA MEL 
                                  INNER JOIN EXPEDICION E ON E.ID_EXPEDICION = MEL.ID_EXPEDICION_ENTREGA 
                                  WHERE E.ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND E.TIPO_ORDEN_RECOGIDA = 'Recogida en Proveedor' AND E.SUBTIPO_ORDEN_RECOGIDA = 'Sin Pedido Conocido' AND MEL.BAJA = 0 AND MEL.LINEA_ANULADA = 0 AND MEL.CANTIDAD > 0";
            $resultPedidoZTLI = $bd->ExecSQL($sqlPedidoZTLI);
            if ($bd->NumRegs($resultPedidoZTLI) > 0):

                //CREO EL PEDIDO ZTLI
                $sqlInsert = "INSERT INTO PEDIDO_ENTRADA SET
                                PEDIDO_SAP = ''
                                , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                , TIPO_PEDIDO = 'Servicios'
                                , TIPO_PEDIDO_SAP = '" . ($importePositivo == true ? 'ZTLI' : 'ZTLF') . "'";
                if ($importePositivo == true):
                    $sqlInsert = $sqlInsert . ", TIPO_ZTLI = 'Importes en Posicion'";
                elseif ($importePositivo == false):
                    $sqlInsert = $sqlInsert . ", TIPO_ZTLF = 'Importes en Posicion'";
                endif;
                $sqlInsert = $sqlInsert . ", INDICADOR_LIBERACION = 'En Liberación'
                                           , ID_PROVEEDOR = $rowOrdenContratacion->ID_PROVEEDOR
                                           , ESTADO = 'Creado'
                                           , ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE
                                           , FECHA_CREACION = '" . date("Y-m-d") . "'
                                           , ID_ORDEN_CONTRATACION = $rowOrdenContratacion->ID_ORDEN_CONTRATACION";
                $bd->ExecSQL($sqlInsert);
                $idPedidoZTL = $bd->IdAsignado();

                //AÑADO EL PEDIDO CREADO AL ARRAY DE PEDIDOS
                $arrPedidosServicios[] = $idPedidoZTL;

                //DAMOS VALOR A PEDIDO_SAP YA QUE ES CLAVE UNICA
                $sqlUpdate = "UPDATE PEDIDO_ENTRADA SET
                                        PEDIDO_SAP = 'SGA" . str_pad((string)$idPedidoZTL, 7, "0", STR_PAD_LEFT) . "'
                                        WHERE ID_PEDIDO_ENTRADA = $idPedidoZTL";
                $bd->ExecSQL($sqlUpdate);

                // LOG MOVIMIENTOS
                $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Creación", "Pedido entrada", $idPedidoZTLI, "");

                //INICIAMOS EL ULTIMO NUMERO DE LINEA
                $UltimoNumeroLinea = 10;

                //CREAMOS UNA LINEA POR MOVIMIENTO
                while ($rowPedidoZTLI = $bd->SigReg($resultPedidoZTLI)):

                    //SE OBTIENE EL PEL ORIGINAL
                    $sqlInsertZTLIOrig = "";

                    $sqlPedEntLinOrig    = "SELECT PEL.ID_PEDIDO_ENTRADA_LINEA, PE.PEDIDO_SAP
                                     FROM PEDIDO_ENTRADA_LINEA PEL
                                     INNER JOIN PEDIDO_ENTRADA PE ON PE.ID_PEDIDO_ENTRADA = PEL.ID_PEDIDO_ENTRADA
                                     WHERE PEL.ID_MOVIMIENTO_ENTRADA_LINEA_RELACIONADA_RECEPCIONA_MATERIAL = $rowPedidoZTLI->ID_MOVIMIENTO_ENTRADA_LINEA AND PE.ID_ORDEN_CONTRATACION = $rowOrdenContratacion->ID_ORDEN_CONTRATACION AND PEL.ID_LINEA_ZTLI_ORIGINAL IS NULL AND PEL.BAJA = 0";
                    $resultPedEntLinOrig = $bd->ExecSQL($sqlPedEntLinOrig);
                    if ($bd->NumRegs($resultPedEntLinOrig) > 0):
                        $rowPedEntLinOrig = $bd->SigReg($resultPedEntLinOrig);
                        if (substr((string)$rowPedEntLinOrig->PEDIDO_SAP, 0, 3) != 'SGA'):
                            $sqlInsertZTLIOrig = " , ID_LINEA_ZTLI_ORIGINAL = $rowPedEntLinOrig->ID_PEDIDO_ENTRADA_LINEA ";
                        endif;
                    endif;

                    //BUSCO EL MOVIMIENTO DE ENTRADA
                    $rowMovimientoEntrada = $bd->VerReg("MOVIMIENTO_ENTRADA", "ID_MOVIMIENTO_ENTRADA", $rowPedidoZTLI->ID_MOVIMIENTO_ENTRADA);
                    if (($rowMovimientoEntrada->ESTADO != 'En Proceso') && ($rowMovimientoEntrada->PENDIENTE_CONFIRMACION_SAP == 0)):
                        $posicionRecepcionada = 1;
                    else:
                        $posicionRecepcionada = 0;
                    endif;

                    //BUSCO LA UBICACION DE DESTINO
                    $rowUbicacion = $bd->VerReg("UBICACION", "ID_UBICACION", $rowPedidoZTLI->ID_UBICACION);

                    //BUSCO EL ALMACEN DE DESTINO
                    $rowAlmacen   = $bd->VerReg("ALMACEN", "ID_ALMACEN", $rowUbicacion->ID_ALMACEN);
                    $idCentroZTLI = $rowOrdenTransporte->ID_CENTRO_CONTRATANTE;

                    //ALMACEN DESTINO
                    if ($rowAlmacen->INFORMAR_MATERIAL_TRANSPORTE == 1):
                        if ($stock_compartido->Obtener_Almacen_Mantenedor($rowAlmacen->ID_CENTRO_FISICO) != NULL):
                            $rowAlmacenMantenedor = $bd->VerReg("ALMACEN", "ID_ALMACEN", $stock_compartido->Obtener_Almacen_Mantenedor($rowAlmacen->ID_CENTRO_FISICO));
                            $idCentroZTLI         = $rowAlmacenMantenedor->ID_CENTRO;
                        endif;
                        $rowMaterial = $bd->VerReg("MATERIAL", "ID_MATERIAL", $rowMaterialServicios->ID_MATERIAL);
                    else:
                        $rowMaterial = $bd->VerReg("MATERIAL", "ID_MATERIAL", $rowPedidoZTLI->ID_MATERIAL);
                    endif;

                    //NOS GUARDAMOS LA CANTIDAD SAP
                    $cantidad_sap = $mat->cantUnidadCompra($rowPedidoZTLI->ID_MATERIAL, $rowPedidoZTLI->CANTIDAD);

                    //TIPO IMPUTACION
                    if ($importePositivo == true): //SI EL IMPORTE ES POSITIVO MIRO EL TIPO DE IMPUTACION. SI ES NEGATIVO VA SIEMPRE A ORDEN.
                        $tipoImputacion = $this->getTipoImputacionSAP("", "", $rowPedidoZTLI->ID_MOVIMIENTO_ENTRADA_LINEA, "", "", $posicionRecepcionada);
                    endif;

                    //CREO LA LINEA DEL PEDIDO DE ENTRADA
                    $sqlInsert = "INSERT INTO PEDIDO_ENTRADA_LINEA SET
                                    ID_PEDIDO_ENTRADA = $idPedidoZTL
                                    , ID_MOVIMIENTO_ENTRADA_LINEA_RELACIONADA_RECEPCIONA_MATERIAL = $rowPedidoZTLI->ID_MOVIMIENTO_ENTRADA_LINEA 
                                    , MATERIAL_REAL_ZTL_RECEPCIONADO = $posicionRecepcionada 
                                    , ID_ALMACEN = NULL
                                    , ID_CENTRO = $idCentroZTLI
                                    , ID_MATERIAL = $rowMaterial->ID_MATERIAL
                                    , ID_TIPO_BLOQUEO = " . ($rowPedidoZTLI->ID_TIPO_BLOQUEO == NULL ? 'NULL' : $rowPedidoZTLI->ID_TIPO_BLOQUEO) . "
                                    , UNIDAD_SAP = $rowMaterial->ID_UNIDAD_COMPRA
                                    , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                    , LINEA_PEDIDO_SAP = '" . str_pad((string)$UltimoNumeroLinea, 5, "0", STR_PAD_LEFT) . "'
                                    , CANTIDAD_SAP = $cantidad_sap
                                    , CANTIDAD = $rowPedidoZTLI->CANTIDAD
                                    , CANTIDAD_PDTE = $rowPedidoZTLI->CANTIDAD
                                    , NUMERO_CONTRATO =  '" . $bd->escapeCondicional($rowOrdenContratacion->NUMERO_CONTRATO) . "'
                                    , NUMERO_CONTRATO_LINEA = '" . $bd->escapeCondicional($rowOrdenContratacion->NUMERO_CONTRATO_LINEA) . "'
                                    , FECHA_ENTREGA = '" . date("Y-m-d") . "'
                                    , ESTADO = 'Sin Recepcionar'
                                    , TIPO_IMPUTACION = '" . $tipoImputacion . "'
                                    , ACTIVA = 1
                                    $sqlInsertZTLIOrig";
                    $bd->ExecSQL($sqlInsert);

                    //INCREMENTO EN 10 EL NUMERO DE LINEA
                    $UltimoNumeroLinea = $UltimoNumeroLinea + 10;
                endwhile;//FIN CREAMOS UNA LINEA POR MOVIMIENTO
            endif;// FIN BUSCO LINEAS DE TIPO RECOGIDAS EN PROVEEDOR SIN PEDIDO CONOCIDO

        endif; //FIN ORDEN TRANSPORTE DE MODELO TRANSPORTE Segundo

        //SI NO SE HA GENERADO NINGUN PEDIDO POR NO TENER LINEAS ACTIVAS, GENERO UN PEDIDO CON EL MATERIAL DEL SERVICIO
        if (count((array)$arrPedidosServicios) == 0):
            //CREO EL PEDIDO ZTLI
            $sqlInsert = "INSERT INTO PEDIDO_ENTRADA SET
                                PEDIDO_SAP = ''
                                , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                , TIPO_PEDIDO = 'Servicios' 
                                , TIPO_PEDIDO_SAP = '" . ($importePositivo == true ? 'ZTLI' : 'ZTLF') . "'";
            if ($importePositivo == true):
                $sqlInsert = $sqlInsert . ", TIPO_ZTLI = 'Importes en Posicion'";
            elseif ($importePositivo == false):
                $sqlInsert = $sqlInsert . ", TIPO_ZTLF = 'Importes en Posicion'";
            endif;
            $sqlInsert = $sqlInsert . ", INDICADOR_LIBERACION = 'En Liberación'
                                       , ID_PROVEEDOR = $rowOrdenContratacion->ID_PROVEEDOR
                                       , ESTADO = 'Creado'
                                       , ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE
                                       , FECHA_CREACION = '" . date("Y-m-d") . "'
                                       , ID_ORDEN_CONTRATACION = $rowOrdenContratacion->ID_ORDEN_CONTRATACION";
            $bd->ExecSQL($sqlInsert);
            $idPedidoZTL = $bd->IdAsignado();

            //AÑADO EL PEDIDO CREADO AL ARRAY DE PEDIDOS
            $arrPedidosServicios[] = $idPedidoZTL;

            //DAMOS VALOR A PEDIDO_SAP YA QUE ES CLAVE UNICA
            $sqlUpdate = "UPDATE PEDIDO_ENTRADA SET
                            PEDIDO_SAP = 'SGA" . str_pad((string)$idPedidoZTL, 7, "0", STR_PAD_LEFT) . "'
                            WHERE ID_PEDIDO_ENTRADA = $idPedidoZTL";
            $bd->ExecSQL($sqlUpdate);

            // LOG MOVIMIENTOS
            $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Creación", "Pedido entrada", $idPedidoZTL, "");

            //INICIAMOS EL ULTIMO NUMERO DE LINEA
            $UltimoNumeroLinea = 10;

            //TIPO IMPUTACION FIJADO A ORDEN -> O
            $tipoImputacion = "O";

            //CREO LA LINEA DEL PEDIDO DE ENTRADA
            $sqlInsert = "INSERT INTO PEDIDO_ENTRADA_LINEA SET
                            ID_PEDIDO_ENTRADA = $idPedidoZTL
                            , MATERIAL_REAL_ZTL_RECEPCIONADO = 0 
                            , ID_ALMACEN = NULL
                            , ID_CENTRO = $rowOrdenTransporte->ID_CENTRO_CONTRATANTE
                            , ID_MATERIAL = $rowMaterialServicios->ID_MATERIAL
                            , ID_TIPO_BLOQUEO = NULL
                            , UNIDAD_SAP = $rowMaterialServicios->ID_UNIDAD_COMPRA
                            , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                            , LINEA_PEDIDO_SAP = '" . str_pad((string)$UltimoNumeroLinea, 5, "0", STR_PAD_LEFT) . "'
                            , CANTIDAD = 1
                            , CANTIDAD_PDTE = 1
                            , NUMERO_CONTRATO =  '" . $bd->escapeCondicional($rowOrdenContratacion->NUMERO_CONTRATO) . "'
                            , NUMERO_CONTRATO_LINEA = '" . $bd->escapeCondicional($rowOrdenContratacion->NUMERO_CONTRATO_LINEA) . "'
                            , FECHA_ENTREGA = '" . date("Y-m-d") . "'
                            , ESTADO = 'Sin Recepcionar'
                            , TIPO_IMPUTACION = '" . $tipoImputacion . "'
                            , ID_MONEDA_TRANSMITIDA_A_SAP = " . ($rowOrdenContratacion->ID_MONEDA == NULL ? 'NULL' : $rowOrdenContratacion->ID_MONEDA) . "
                            , ACTIVA = 1";
            $bd->ExecSQL($sqlInsert);
        endif;
        //FIN SI NO SE HA GENERADO NINGUN PEDIDO POR NO TENER LINEAS ACTIVAS, GENERO UN PEDIDO CON EL MATERIAL DEL SERVICIO

        //DEVULEVO EL ARRAY CON LOS PEDIDOS DE SERVICIOS DEVUELTOS
        return $arrPedidosServicios;
    }

    /**
     * @param $idOrdenTransporte ORDEN DE TRANSPORTE
     * GENERA LOS PEDIDOS ZTLJ PARA TRANSPORTE INTERNACIONALES
     * Devuelve el ID_PEDIDO_ENTRADA del ZTLJ ficticio
     */
    function generarZTLJFicticio($idOrdenTransporte)
    {
        global $bd;
        global $html;
        global $auxiliar;
        global $administrador;
        global $mat;

        //BUSCO LA ORDEN DE RECOGIDA
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowOrdenTransporte               = $bd->VerReg("ORDEN_TRANSPORTE", "ID_ORDEN_TRANSPORTE", $idOrdenTransporte, "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);

        //BUSCO SI LA ORDEN DE TRANSPORTE YA TIENE PEDIDOS
        $sqlPedidoServicios    = "SELECT PE.ID_PEDIDO_ENTRADA
                                   FROM PEDIDO_ENTRADA PE
                                   INNER JOIN ORDEN_TRANSPORTE OT ON OT.ID_ORDEN_TRANSPORTE = PE.ID_ORDEN_TRANSPORTE
                                   WHERE PE.ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND PE.TIPO_PEDIDO_SAP = 'ZTLJ' AND PE.BAJA = 0";
        $resultPedidoServicios = $bd->ExecSQL($sqlPedidoServicios);

        //SI NO TIENE PEDIDOS, CREAMOS LOS PEDIDOS DE SERVICIOS
        if ($bd->NumRegs($resultPedidoServicios) == 0):

            //BUSCO SI TIENE RECOGIDAS CON ENVIOS Y COMPONENTES A PROVEEDOR (ZTLI)
            $sqlPedidoZTLI    = "SELECT MSL.ID_MOVIMIENTO_SALIDA_LINEA, MSL.ID_MOVIMIENTO_SALIDA, MSL.ID_ALMACEN, MSL.ID_MATERIAL, MSL.ID_TIPO_BLOQUEO, PSL.ID_UNIDAD, MSL.CANTIDAD, PS.ID_PROVEEDOR
                                  FROM MOVIMIENTO_SALIDA_LINEA MSL
                                  INNER JOIN EXPEDICION E ON E.ID_EXPEDICION = MSL.ID_EXPEDICION
                                  INNER JOIN PEDIDO_SALIDA PS ON PS.ID_PEDIDO_SALIDA = MSL.ID_PEDIDO_SALIDA
                                  INNER JOIN PEDIDO_SALIDA_LINEA PSL ON PSL.ID_PEDIDO_SALIDA_LINEA = MSL.ID_PEDIDO_SALIDA_LINEA
                                  WHERE E.ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND E.NACIONAL = 'Internacional' AND MSL.LINEA_ANULADA = 0 AND MSL.BAJA = 0 AND (PS.TIPO_PEDIDO = 'Componentes a Proveedor' OR PS.TIPO_PEDIDO = 'Material Estropeado a Proveedor' OR PS.TIPO_PEDIDO = 'Material Estropeado Entre Proveedores')";
            $resultPedidoZTLI = $bd->ExecSQL($sqlPedidoZTLI);

            if ($bd->NumRegs($resultPedidoZTLI) > 0):

                //CREO EL PEDIDO ZTLJ
                $sqlInsert = "INSERT INTO PEDIDO_ENTRADA SET
                                PEDIDO_SAP = ''
                                , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                , TIPO_PEDIDO = 'Servicios'
                                , TIPO_PEDIDO_SAP = 'ZTLJ'
                                , TIPO_ZTLJ = 'Envio a Proveedor Internacional'
                                , INDICADOR_LIBERACION = 'En Liberación'
                                , ID_PROVEEDOR = $rowOrdenTransporte->ID_AGENCIA
                                , ESTADO = 'Creado'
                                , ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE
                                , FECHA_CREACION = '" . date("Y-m-d") . "'";
                $bd->ExecSQL($sqlInsert);
                $idPedidoZTLJ = $bd->IdAsignado();

                //DAMOS VALOR A PEDIDO_SAP YA QUE ES CLAVE UNICA
                $sqlUpdate = "UPDATE PEDIDO_ENTRADA SET
                                PEDIDO_SAP = 'SGA" . str_pad((string)$idPedidoZTLJ, 7, "0", STR_PAD_LEFT) . "'
                                WHERE ID_PEDIDO_ENTRADA = $idPedidoZTLJ";
                $bd->ExecSQL($sqlUpdate);

                // LOG MOVIMIENTOS
                $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Creación", "Pedido entrada", $idPedidoZTLJ, "");

                //INICIAMOS EL ULTIMO NUMERO DE LINEA
                $UltimoNumeroLinea = 10;

                //CREAMOS UNA LINEA POR MOVIMIENTO
                while ($rowPedidoZTLI = $bd->SigReg($resultPedidoZTLI)):
                    //SE OBTIENE EL MOVIMIENTO DE SALIDA
                    $rowMovSal = $bd->VerReg("MOVIMIENTO_SALIDA", "ID_MOVIMIENTO_SALIDA", $rowPedidoZTLI->ID_MOVIMIENTO_SALIDA);

                    //BUSCO EL ALMACEN DE ORIGEN
                    $rowAlmacen = $bd->VerReg("ALMACEN", "ID_ALMACEN", $rowPedidoZTLI->ID_ALMACEN);

                    //BUSCO EL MATERIAL DE ORIGEN
                    $rowMaterial = $bd->VerReg("MATERIAL", "ID_MATERIAL", $rowPedidoZTLI->ID_MATERIAL);

                    //NOS GUARDAMOS LA CANTIDAD SAP
                    $cantidad_sap = $mat->cantUnidadCompra($rowPedidoZTLI->ID_MATERIAL, $rowPedidoZTLI->CANTIDAD);

                    //CREO LA LINEA DEL PEDIDO DE ENTRADA
                    $sqlInsert = "INSERT INTO PEDIDO_ENTRADA_LINEA SET
                                    ID_PEDIDO_ENTRADA = $idPedidoZTLJ
                                    , ID_MOVIMIENTO_SALIDA_LINEA_RELACIONADA_MUEVE_MATERIAL = $rowPedidoZTLI->ID_MOVIMIENTO_SALIDA_LINEA
                                    , ID_ALMACEN = NULL
                                    , ID_CENTRO = " . ($rowOrdenTransporte->ID_CENTRO_CONTRATANTE != NULL ? $rowOrdenTransporte->ID_CENTRO_CONTRATANTE : $rowAlmacen->ID_CENTRO) . "
                                    , ID_MATERIAL = $rowPedidoZTLI->ID_MATERIAL
                                    , ID_TIPO_BLOQUEO = " . ($rowPedidoZTLI->ID_TIPO_BLOQUEO == NULL ? 'NULL' : $rowPedidoZTLI->ID_TIPO_BLOQUEO) . "
                                    , UNIDAD_SAP = $rowMaterial->ID_UNIDAD_COMPRA
                                    , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                    , LINEA_PEDIDO_SAP = '" . str_pad((string)$UltimoNumeroLinea, 5, "0", STR_PAD_LEFT) . "'
                                    , CANTIDAD_SAP = $cantidad_sap
                                    , CANTIDAD = $rowPedidoZTLI->CANTIDAD
                                    , CANTIDAD_PDTE = $rowPedidoZTLI->CANTIDAD
                                    , FECHA_ENTREGA = '" . date("Y-m-d") . "'
                                    , ESTADO = 'Sin Recepcionar'";
                    $bd->ExecSQL($sqlInsert);

                    //INCREMENTO EN 10 EL NUMERO DE LINEA
                    $UltimoNumeroLinea = $UltimoNumeroLinea + 10;

                    //ACTUALIZO EL PROVEEDOR DEL PEDIDO ZTLJ
                    if ($rowMovSal->TIPO_MOVIMIENTO == 'MaterialEstropeadoEntreProveedores'):
                        $idProveedor = $rowMovSal->ID_PROVEEDOR;
                    else:
                        $idProveedor = $rowPedidoZTLI->ID_PROVEEDOR;
                    endif;
                    $sqlUpdate = "UPDATE PEDIDO_ENTRADA SET ID_PROVEEDOR = $idProveedor WHERE ID_PEDIDO_ENTRADA = $idPedidoZTLJ";
                    $bd->ExecSQL($sqlUpdate);
                endwhile;//FIN CREAMOS UNA LINEA POR MOVIMIENTO

                return $idPedidoZTLJ;
            endif;// FIN BUSCO SI TIENE RECOGIDAS CON ENVIOS Y COMPONENTES A PROVEEDOR (ZTLI)

        else:
            $rowPedidoZTL = $bd->SigReg($resultPedidoServicios);

            return $rowPedidoZTL->ID_PEDIDO_ENTRADA;

        endif;//LA OT YA TIENE PEDIDOS DE SERVICIOS


    }

    /**
     * @param $idOrdenTransporte ORDEN DE TRANSPORTE , $cantidadModificado CANTIDAD MODIFICADA EN EL ULTIMO CAMBIO
     * GENERA LOS PEDIDOS ZTLI PARA INFORMAR DE CAMBIOS EN EL IMPORTE DE CONTRATACIONES ACEPTADAS
     * Devuelve el ID_PEDIDO_ENTRADA del ZTLI ficticio
     */
    function generarZTLIFicticioModificacionImporte($idOrdenContratacion, $cantidadModificado)
    {
        exit("FUNCION generarZTLIFicticioModificacionImporte NO UTILIZADA EN SISTEMA");
        global $bd;
        global $html;
        global $auxiliar;
        global $administrador;
        global $mat;

        //BUSCO LA ORDEN DE CONTRATACION
        $rowOrdenContratacion = $bd->VerReg("ORDEN_CONTRATACION", "ID_ORDEN_CONTRATACION", $idOrdenContratacion);

        //BUSCO LA ORDEN DE TRANSPORTE
        $rowOrdenTransporte = $bd->VerReg("ORDEN_TRANSPORTE", "ID_ORDEN_TRANSPORTE", $rowOrdenContratacion->ID_ORDEN_TRANSPORTE);

        //OBTENEMOS EL IMPORTE MODIFICADO
        //$cantidadModificado = $rowOrdenContratacion->IMPORTE_MODIFICADO - $rowOrdenContratacion->IMPORTE;

        //CREO LA CABECERA DEL PEDIDO, ESTARA EN LIBERACION HASTA QUE NOS CAIGA DESDE SAP PARA PODER CONFIRMARLO
        $sqlInsert = "INSERT INTO PEDIDO_ENTRADA SET
                                    PEDIDO_SAP = ''
                                    , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                    , TIPO_PEDIDO = 'Servicios'
                                    , TIPO_PEDIDO_SAP = 'ZTLI'
                                    , TIPO_ZTLI = 'Modificacion Importes'
                                    , INDICADOR_LIBERACION = 'En Liberación'
                                    , ID_PROVEEDOR = $rowOrdenContratacion->ID_PROVEEDOR
                                    , ESTADO = 'Creado'
                                    , ID_ORDEN_TRANSPORTE = $rowOrdenContratacion->ID_ORDEN_TRANSPORTE
                                    , FECHA_CREACION = '" . date("Y-m-d") . "'
                                    , ID_ORDEN_CONTRATACION = $rowOrdenContratacion->ID_ORDEN_CONTRATACION";
        $bd->ExecSQL($sqlInsert);
        $idPedidoZTLI = $bd->IdAsignado();

        //DAMOS VALOR A PEDIDO_SAP YA QUE ES CLAVE UNICA
        $sqlUpdate = "UPDATE PEDIDO_ENTRADA SET
                                    PEDIDO_SAP = 'SGA" . str_pad((string)$idPedidoZTLI, 7, "0", STR_PAD_LEFT) . "'
                                    WHERE ID_PEDIDO_ENTRADA = $idPedidoZTLI";
        $bd->ExecSQL($sqlUpdate);

        // LOG MOVIMIENTOS
        $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Creación", "Pedido entrada", $idPedidoZTLI, "");

        //A NIVEL DE LINEA ENVIAMOS EL MATERIAL DE SERVICIOS

        //BUSCAMOS EL SERVICIO
        $rowServicio = $bd->VerReg("SERVICIO", "ID_SERVICIO", $rowOrdenContratacion->ID_SERVICIO);

        //BUSCAMOS EL MATERIAL
        $rowMaterial = $bd->VerReg("MATERIAL", "ID_MATERIAL", $rowServicio->ID_MATERIAL);

        //CREO LA LINEA DEL PEDIDO DE ENTRADA
        $sqlInsert = "INSERT INTO PEDIDO_ENTRADA_LINEA SET
                                    ID_PEDIDO_ENTRADA = $idPedidoZTLI
                                    , ID_ALMACEN = NULL
                                    , ID_CENTRO = $rowOrdenTransporte->ID_CENTRO_CONTRATANTE
                                    , ID_MATERIAL = $rowMaterial->ID_MATERIAL
                                    , ID_TIPO_BLOQUEO = NULL
                                    , UNIDAD_SAP = $rowMaterial->ID_UNIDAD_COMPRA
                                    , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                    , LINEA_PEDIDO_SAP = '" . str_pad((string)"10", 5, "0", STR_PAD_LEFT) . "'
                                    , CANTIDAD_SAP = 1
                                    , CANTIDAD = 1
                                    , CANTIDAD_PDTE = 1
                                    , NUMERO_CONTRATO =  '" . $bd->escapeCondicional($rowOrdenContratacion->NUMERO_CONTRATO) . "'
                                    , NUMERO_CONTRATO_LINEA = '" . $bd->escapeCondicional($rowOrdenContratacion->NUMERO_CONTRATO_LINEA) . "'
                                    , FECHA_ENTREGA = '" . date("Y-m-d") . "'
                                    , IMPORTE_TRANSMITIDO_A_SAP = $cantidadModificado
                                    , ID_MONEDA_TRANSMITIDA_A_SAP = $rowOrdenTransporte->ID_MONEDA
                                    , ESTADO = 'Sin Recepcionar'";
        $bd->ExecSQL($sqlInsert);

        return $idPedidoZTLI;
    }

    /**
     * @param $idPedidoEntradaLinea LINEA DE PEDIDO ANULADA RECEPCION, $idOrdenContratacion ORDEN DE CONTRATACION , $importeModificado IMPORTE CORRESPONDIENTE, $cantidad CANTIDAD SPLIT
     * GENERA LOS PEDIDOS ZTLI PARA INFORMAR DE CAMBIOS EN EL IMPORTE DE CONTRATACIONES ACEPTADAS
     * Devuelve el ID_PEDIDO_ENTRADA del ZTLI ficticio
     */
    function generarZTLIFicticioAnulacionLineaPedidoProveedor($idPedidoEntradaLinea, $idOrdenContratacion, $importeModificado, $cantidad)
    {

        global $bd;
        global $html;
        global $auxiliar;
        global $administrador;
        global $mat;

        //BUSCO LA LINEA DEL PEDIDO DE ENTRADA DE LA QUE SE HA ANULADO LA RECEPCION
        $rowPedidoEntradaLinea = $bd->VerReg("PEDIDO_ENTRADA_LINEA", "ID_PEDIDO_ENTRADA_LINEA", $idPedidoEntradaLinea);

        //BUSCO EL PEDIDO DE ENTRADA DE LA QUE SE HA ANULADO LA RECEPCION
        $rowPedidoEntrada = $bd->VerReg("PEDIDO_ENTRADA", "ID_PEDIDO_ENTRADA", $rowPedidoEntradaLinea->ID_PEDIDO_ENTRADA);

        //BUSCO LA ORDEN DE CONTRATACION
        $rowOrdenContratacion = $bd->VerReg("ORDEN_CONTRATACION", "ID_ORDEN_CONTRATACION", $idOrdenContratacion);

        //BUSCO LA ORDEN DE TRANSPORTE
        $rowOrdenTransporte = $bd->VerReg("ORDEN_TRANSPORTE", "ID_ORDEN_TRANSPORTE", $rowOrdenContratacion->ID_ORDEN_TRANSPORTE);

        //BUSCO EL MATERIAL
        $rowMaterial = $bd->VerReg("MATERIAL", "ID_MATERIAL", $rowPedidoEntradaLinea->ID_MATERIAL);

        //CALCULO EL PESO DE LA LINEA
        $pesoLinea = $rowMaterial->PESO_BRUTO * $cantidad;

        //DEPENDIENDO DEL ESTADO DE LA REFERENCIA DE FACTURACIÓN ASOCIADA A LA CONTRATACIÓN, SE CREA UN ZTLI O ZTLK
        $sqlEstadoRefFacturacion    = "SELECT A.ESTADO_CERTIFICACION
                                       FROM AUTOFACTURA A
                                       INNER JOIN AUTOFACTURA_LINEA AL ON AL.ID_AUTOFACTURA = A.ID_AUTOFACTURA
                                       WHERE AL.ID_ORDEN_CONTRATACION = $rowOrdenContratacion->ID_ORDEN_CONTRATACION AND AL.BAJA = 0 AND A.BAJA = 0";
        $resultEstadoRefFacturacion = $bd->ExecSQL($sqlEstadoRefFacturacion);
        $rowEstadoRefFacturacion    = $bd->SigReg($resultEstadoRefFacturacion);

        $tipoZTL = " , TIPO_PEDIDO_SAP = '" . $rowPedidoEntrada->TIPO_PEDIDO_SAP . "' ";

        //SE OBTIENE LA SUMA DE LOS IMPORTES DE ZTLI IMPUTADAS A CECO
        $sqlCantidadCEcoZTLI    = "SELECT SUM(IMPORTE) AS CANTIDAD
                                    FROM PEDIDO_ENTRADA
                                    WHERE ID_ORDEN_CONTRATACION = $rowOrdenContratacion->ID_ORDEN_CONTRATACION AND TIPO_PEDIDO_SAP = 'ZTLI' AND BAJA = 0";
        $resultCantidadCEcoZTLI = $bd->ExecSQL($sqlCantidadCEcoZTLI);
        $rowCantidadCEcoZTLI    = $bd->SigReg($resultCantidadCEcoZTLI);

        //SE OBTIENE LA SUMA DE LOS IMPORTES DE ZTLF IMPUTADAS A CECO
        $sqlCantidadCEcoZTLF    = "SELECT SUM(IMPORTE) AS CANTIDAD
                                    FROM PEDIDO_ENTRADA
                                    WHERE ID_ORDEN_CONTRATACION = $rowOrdenContratacion->ID_ORDEN_CONTRATACION AND TIPO_PEDIDO_SAP = 'ZTLF' AND BAJA = 0";
        $resultCantidadCEcoZTLF = $bd->ExecSQL($sqlCantidadCEcoZTLF);
        $rowCantidadCEcoZTLF    = $bd->SigReg($resultCantidadCEcoZTLF);

        $cantidadCEco = $rowCantidadCEcoZTLI->CANTIDAD - $rowCantidadCEcoZTLF->CANTIDAD;

        if ($rowEstadoRefFacturacion->ESTADO_CERTIFICACION == 'Contabilizado' && $cantidadCEco > EPSILON_SISTEMA):
            $tipoZTL = " , TIPO_PEDIDO_SAP = 'ZTLK' ";
        endif;

        //CREO LA CABECERA DEL PEDIDO, ESTARA EN LIBERACION HASTA QUE NOS CAIGA DESDE SAP PARA PODER CONFIRMARLO
        $sqlInsert = "INSERT INTO PEDIDO_ENTRADA SET
                        PEDIDO_SAP = ''
                        , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                        , TIPO_PEDIDO = 'Servicios'
                        $tipoZTL
                        , TIPO_ZTLI = 'Anulacion Linea Pedido Proveedor'
                        , INDICADOR_LIBERACION = 'En Liberación'
                        , ID_PROVEEDOR = $rowOrdenContratacion->ID_PROVEEDOR
                        , ESTADO = 'Creado'
                        , ID_ORDEN_TRANSPORTE = $rowOrdenContratacion->ID_ORDEN_TRANSPORTE
                        , FECHA_CREACION = '" . date("Y-m-d") . "'
                        , ID_ORDEN_CONTRATACION = $rowOrdenContratacion->ID_ORDEN_CONTRATACION
                        , PESO = $pesoLinea
                        , ID_UNIDAD_PESO = $rowMaterial->ID_UNIDAD_PESO";
        $bd->ExecSQL($sqlInsert);
        $idPedidoZTL = $bd->IdAsignado();

        //DAMOS VALOR A PEDIDO_SAP YA QUE ES CLAVE UNICA
        $sqlUpdate = "UPDATE PEDIDO_ENTRADA SET
                        PEDIDO_SAP = 'SGA" . str_pad((string)$idPedidoZTL, 7, "0", STR_PAD_LEFT) . "'
                        WHERE ID_PEDIDO_ENTRADA = $idPedidoZTL";
        $bd->ExecSQL($sqlUpdate);

        // LOG MOVIMIENTOS
        $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Creación", "Pedido entrada", $idPedidoZTL, "");

        $whereLineaRelacionadaMaterial = "";
        if ($rowPedidoEntradaLinea->ID_MOVIMIENTO_SALIDA_LINEA_RELACIONADA_MUEVE_MATERIAL != NULL):
            $whereLineaRelacionadaMaterial = ", ID_MOVIMIENTO_SALIDA_LINEA_RELACIONADA_MUEVE_MATERIAL = $rowPedidoEntradaLinea->ID_MOVIMIENTO_SALIDA_LINEA_RELACIONADA_MUEVE_MATERIAL ";
        endif;

        if ($rowEstadoRefFacturacion->ESTADO_CERTIFICACION == 'Contabilizado' && $cantidadCEco > EPSILON_SISTEMA):

            $insertZTL = " , LINEA_PEDIDO_SAP = '" . $rowPedidoEntradaLinea->LINEA_PEDIDO_SAP . "'
                           , CANTIDAD_SAP = $rowPedidoEntradaLinea->CANTIDAD_SAP
                           , CANTIDAD = $rowPedidoEntradaLinea->CANTIDAD
                           , CANTIDAD_PDTE = $rowPedidoEntradaLinea->CANTIDAD_PDTE
                           $whereLineaRelacionadaMaterial ";
        else:
            //A NIVEL DE LINEA ENVIAMOS EL MATERIAL DE SERVICIOS
            //BUSCAMOS EL SERVICIO
            $rowServicio = $bd->VerReg("SERVICIO", "ID_SERVICIO", $rowOrdenContratacion->ID_SERVICIO);

            //BUSCAMOS EL MATERIAL
            $rowMaterial = $bd->VerReg("MATERIAL", "ID_MATERIAL", $rowServicio->ID_MATERIAL);

            $insertZTL = " , LINEA_PEDIDO_SAP = '" . str_pad("10", 5, "0", STR_PAD_LEFT) . "'
                           , CANTIDAD_SAP = 1
                           , CANTIDAD = 1
                           , CANTIDAD_PDTE = 1";
        endif;

        //CREO LA LINEA DEL PEDIDO DE ENTRADA
        $sqlInsert = "INSERT INTO PEDIDO_ENTRADA_LINEA SET
                        ID_PEDIDO_ENTRADA = $idPedidoZTL
                        , ID_ALMACEN = NULL
                        , ID_CENTRO = $rowOrdenTransporte->ID_CENTRO_CONTRATANTE
                        , ID_MATERIAL = $rowMaterial->ID_MATERIAL
                        , ID_TIPO_BLOQUEO = NULL
                        , UNIDAD_SAP = $rowMaterial->ID_UNIDAD_COMPRA
                        , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                        $insertZTL
                        , NUMERO_CONTRATO =  '" . $bd->escapeCondicional($rowPedidoEntrada->PEDIDO_SAP) . "'
                        , NUMERO_CONTRATO_LINEA = '" . $bd->escapeCondicional($rowPedidoEntradaLinea->LINEA_PEDIDO_SAP) . "'
                        , FECHA_ENTREGA = '" . date("Y-m-d") . "'
                        , IMPORTE_TRANSMITIDO_A_SAP = $importeModificado
                        , ID_MONEDA_TRANSMITIDA_A_SAP = $rowOrdenContratacion->ID_MONEDA
                        , ESTADO = 'Sin Recepcionar'
                        , ID_LINEA_ZTLI_ORIGINAL = $rowPedidoEntradaLinea->ID_PEDIDO_ENTRADA_LINEA
                        , ACTIVA = 1
                        , TIPO_IMPUTACION = 'O'";
        $bd->ExecSQL($sqlInsert);

        //SE ACTUALIZA LA LINEA DEL ZTLI ORIGINA A NO ACTIVA
        $sqlUpdateNoActiva = "UPDATE PEDIDO_ENTRADA_LINEA
                              SET ACTIVA = 0
                              WHERE ID_PEDIDO_ENTRADA_LINEA = $rowPedidoEntradaLinea->ID_PEDIDO_ENTRADA_LINEA";
        $bd->ExecSQL($sqlUpdateNoActiva);

        return $idPedidoZTL;
    }

    /**
     * @param $idPedidoEntradaLineaMaterialReal -> ID de la linea del material real del transporte
     * @param $idPedidoEntradaLineaServicios -> ID de la linea del pedido de servicios anulada
     * @return int -> ID del nuevo pedido ZTLI generado
     */
    function generarZTLIAnulacionLineaPedidoZTLI($idPedidoEntradaLineaMaterialReal, $idPedidoEntradaLineaServicios)
    {

        global $bd;
        global $administrador;

        //BUSCO LA LINEA DEL PEDIDO DE ENTRADA DEL MATERIAL DEL TRANSPORTE
        $rowPedidoEntradaLineaMaterialReal = $bd->VerReg("PEDIDO_ENTRADA_LINEA", "ID_PEDIDO_ENTRADA_LINEA", $idPedidoEntradaLineaMaterialReal);

        //BUSCO EL PEDIDO DE ENTRADA DEL MATERIAL DEL TRANSPORTE
        $rowPedidoEntradaMaterialReal = $bd->VerReg("PEDIDO_ENTRADA", "ID_PEDIDO_ENTRADA", $rowPedidoEntradaLineaMaterialReal->ID_PEDIDO_ENTRADA);

        //BUSCO LA LINEA DEL PEDIDO DE SERVICIOS
        $rowPedidoEntradaLineaServicios = $bd->VerReg("PEDIDO_ENTRADA_LINEA", "ID_PEDIDO_ENTRADA_LINEA", $idPedidoEntradaLineaServicios);

        //BUSCO EL PEDIDO DE ENTRADA DEL MATERIAL DEL TRANSPORTE
        $rowPedidoEntradaServicios = $bd->VerReg("PEDIDO_ENTRADA", "ID_PEDIDO_ENTRADA", $rowPedidoEntradaLineaServicios->ID_PEDIDO_ENTRADA);

        //BUSCO LA ORDEN DE CONTRATACION
        $rowOrdenContratacion = $bd->VerReg("ORDEN_CONTRATACION", "ID_ORDEN_CONTRATACION", $rowPedidoEntradaServicios->ID_ORDEN_CONTRATACION);

        //BUSCO LA ORDEN DE TRANSPORTE
        $rowOrdenTransporte = $bd->VerReg("ORDEN_TRANSPORTE", "ID_ORDEN_TRANSPORTE", $rowOrdenContratacion->ID_ORDEN_TRANSPORTE);

        //BUSCO EL MATERIAL
        $rowMaterial = $bd->VerReg("MATERIAL", "ID_MATERIAL", $rowPedidoEntradaLineaMaterialReal->ID_MATERIAL);

        $tipoZTL = " , TIPO_PEDIDO_SAP = '" . $rowPedidoEntradaServicios->TIPO_PEDIDO_SAP . "' ";

        //DEPENDIENDO DEL ESTADO DE LA REFERENCIA DE FACTURACIÓN ASOCIADA A LA CONTRATACIÓN, SE CREA UN ZTLI O ZTLK
        $rowEstadoRefFacturacion = $bd->VerReg("AUTOFACTURA", "REFERENCIA_FACTURACION", $rowOrdenContratacion->REFERENCIA_FACTURACION);
        if ($rowEstadoRefFacturacion->ESTADO_CERTIFICACION == 'Contabilizado'):
            $tipoZTL = " , TIPO_PEDIDO_SAP = 'ZTLK' ";
        endif;

        //CREO LA CABECERA DEL PEDIDO, ESTARA EN LIBERACION HASTA QUE NOS CAIGA DESDE SAP PARA PODER CONFIRMARLO
        $sqlInsert = "INSERT INTO PEDIDO_ENTRADA SET
                        PEDIDO_SAP = ''
                        , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                        , TIPO_PEDIDO = 'Servicios'
                        $tipoZTL
                        , TIPO_ZTLI = 'Anulacion Linea Pedido Proveedor'
                        , INDICADOR_LIBERACION = 'En Liberación'
                        , ID_PROVEEDOR = $rowOrdenContratacion->ID_PROVEEDOR
                        , ESTADO = 'Creado'
                        , ID_ORDEN_TRANSPORTE = $rowOrdenContratacion->ID_ORDEN_TRANSPORTE
                        , FECHA_CREACION = '" . date("Y-m-d") . "'
                        , ID_ORDEN_CONTRATACION = $rowOrdenContratacion->ID_ORDEN_CONTRATACION
                        , PESO = $rowPedidoEntradaServicios->PESO
                        , ID_UNIDAD_PESO = $rowPedidoEntradaServicios->ID_UNIDAD_PESO";
        $bd->ExecSQL($sqlInsert);
        $idPedidoZTL = $bd->IdAsignado();

        //DAMOS VALOR A PEDIDO_SAP YA QUE ES CLAVE UNICA
        $sqlUpdate = "UPDATE PEDIDO_ENTRADA SET
                        PEDIDO_SAP = 'SGA" . str_pad((string)$idPedidoZTL, 7, "0", STR_PAD_LEFT) . "'
                        WHERE ID_PEDIDO_ENTRADA = $idPedidoZTL";
        $bd->ExecSQL($sqlUpdate);

        // LOG MOVIMIENTOS
        $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Creación", "Pedido entrada", $idPedidoZTL, "");

        //AÑADO EL ELEMENTO RELACIONADO CON EL MATERIAL DE TRANSPORTE
        $whereLineaRelacionadaMaterial = "";
        if ($rowPedidoEntradaLineaServicios->ID_MOVIMIENTO_SALIDA_LINEA_RELACIONADA_MUEVE_MATERIAL != NULL):
            $whereLineaRelacionadaMaterial = ", ID_MOVIMIENTO_SALIDA_LINEA_RELACIONADA_MUEVE_MATERIAL = $rowPedidoEntradaLineaServicios->ID_MOVIMIENTO_SALIDA_LINEA_RELACIONADA_MUEVE_MATERIAL ";
        elseif ($rowPedidoEntradaLineaServicios->ID_PEDIDO_ENTRADA_LINEA_RELACIONADA_RECEPCIONA_MATERIAL != NULL):
            $whereLineaRelacionadaMaterial = ", ID_PEDIDO_ENTRADA_LINEA_RELACIONADA_RECEPCIONA_MATERIAL = $rowPedidoEntradaLineaServicios->ID_PEDIDO_ENTRADA_LINEA_RELACIONADA_RECEPCIONA_MATERIAL ";
        elseif ($rowPedidoEntradaLineaServicios->ID_MOVIMIENTO_ENTRADA_LINEA_RELACIONADA_RECEPCIONA_MATERIAL != NULL):
            $whereLineaRelacionadaMaterial = ", ID_MOVIMIENTO_ENTRADA_LINEA_RELACIONADA_RECEPCIONA_MATERIAL = $rowPedidoEntradaLineaServicios->ID_MOVIMIENTO_ENTRADA_LINEA_RELACIONADA_RECEPCIONA_MATERIAL ";
        elseif ($rowPedidoEntradaLineaServicios->ID_ELEMENTO_IMPUTACION != NULL):
            $whereLineaRelacionadaMaterial = ", ID_ELEMENTO_IMPUTACION = $rowPedidoEntradaLineaServicios->ID_ELEMENTO_IMPUTACION 
                                              , CODIGO_SELECCIONADO_ELEMENTO_IMPUTACION = '" . $rowPedidoEntradaLineaServicios->CODIGO_SELECCIONADO_ELEMENTO_IMPUTACION . "'";
        elseif ($rowPedidoEntradaLineaServicios->ID_ORDEN_TRABAJO_RELACIONADO != NULL):
            $whereLineaRelacionadaMaterial = ", ID_ORDEN_TRABAJO_RELACIONADO = $rowPedidoEntradaLineaServicios->ID_ORDEN_TRABAJO_RELACIONADO ";
        endif;

        //GENERO EL NUMERO DE LINEA DE PEDIDO
        $lineaPedidoSAP = '00010';

        //CREO LA LINEA DEL PEDIDO DE ENTRADA
        $sqlInsert = "INSERT INTO PEDIDO_ENTRADA_LINEA SET
                        ID_PEDIDO_ENTRADA = $idPedidoZTL
                        , ID_ALMACEN = NULL
                        , ID_CENTRO = $rowOrdenTransporte->ID_CENTRO_CONTRATANTE
                        , ID_MATERIAL = $rowMaterial->ID_MATERIAL
                        , ID_TIPO_BLOQUEO = NULL
                        , UNIDAD_SAP = $rowMaterial->ID_UNIDAD_COMPRA
                        , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                        , LINEA_PEDIDO_SAP = '" . $lineaPedidoSAP . "'
                        , CANTIDAD_SAP = $rowPedidoEntradaLineaServicios->CANTIDAD_SAP
                        , CANTIDAD = $rowPedidoEntradaLineaServicios->CANTIDAD
                        , CANTIDAD_PDTE = $rowPedidoEntradaLineaServicios->CANTIDAD_PDTE
                          $whereLineaRelacionadaMaterial 
                        , NUMERO_CONTRATO =  '" . $bd->escapeCondicional($rowPedidoEntradaMaterialReal->PEDIDO_SAP) . "'
                        , NUMERO_CONTRATO_LINEA = '" . $bd->escapeCondicional($rowPedidoEntradaLineaMaterialReal->LINEA_PEDIDO_SAP) . "'
                        , FECHA_ENTREGA = '" . date("Y-m-d") . "'
                        , IMPORTE_TRANSMITIDO_A_SAP = $rowPedidoEntradaLineaServicios->IMPORTE_TRANSMITIDO_A_SAP
                        , ID_MONEDA_TRANSMITIDA_A_SAP = $rowPedidoEntradaLineaServicios->ID_MONEDA_TRANSMITIDA_A_SAP
                        , ESTADO = 'Sin Recepcionar'
                        , ID_LINEA_ZTLI_ORIGINAL = $rowPedidoEntradaLineaServicios->ID_PEDIDO_ENTRADA_LINEA
                        , ACTIVA = 1
                        , TIPO_IMPUTACION = 'O'";
        $bd->ExecSQL($sqlInsert);

        //SE ACTUALIZA LA LINEA DEL ZTLI ORIGINAL A NO ACTIVA
        $sqlUpdateNoActiva = "UPDATE PEDIDO_ENTRADA_LINEA
                              SET ACTIVA = 0
                              WHERE ID_PEDIDO_ENTRADA_LINEA = $rowPedidoEntradaLineaServicios->ID_PEDIDO_ENTRADA_LINEA";
        $bd->ExecSQL($sqlUpdateNoActiva);

        return $idPedidoZTL;
    }

    /**
     * @param $idOrdenTransporte ORDEN DE TRANSPORTE , $cantidadModificado CANTIDAD MODIFICADA EN EL ULTIMO CAMBIO
     * GENERA LOS PEDIDOS ZTLF PARA INFORMAR DE CAMBIOS EN EL IMPORTE DE CONTRATACIONES ACEPTADAS
     * Devuelve el ID_PEDIDO_ENTRADA del ZTLF ficticio
     */
    function generarZTLFFicticioModificacionImporte($idOrdenContratacion, $cantidadModificado)
    {
        exit("FUNCION generarZTLFFicticioModificacionImporte NO UTILIZADA EN SISTEMA");
        global $bd;
        global $html;
        global $auxiliar;
        global $administrador;
        global $mat;

        //BUSCO LA ORDEN DE CONTRATACION
        $rowOrdenContratacion = $bd->VerReg("ORDEN_CONTRATACION", "ID_ORDEN_CONTRATACION", $idOrdenContratacion);

        //BUSCO LA ORDEN DE TRANSPORTE
        $rowOrdenTransporte = $bd->VerReg("ORDEN_TRANSPORTE", "ID_ORDEN_TRANSPORTE", $rowOrdenContratacion->ID_ORDEN_TRANSPORTE);

        //OBTENEMOS EL IMPORTE MODIFICADO
        //$cantidadModificado = $rowOrdenContratacion->IMPORTE_MODIFICADO - $rowOrdenContratacion->IMPORTE;

        //CREO LA CABECERA DEL PEDIDO, ESTARA EN LIBERACION HASTA QUE NOS CAIGA DESDE SAP PARA PODER CONFIRMARLO
        $sqlInsert = "INSERT INTO PEDIDO_ENTRADA SET
                                    PEDIDO_SAP = ''
                                    , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                    , TIPO_PEDIDO = 'Servicios'
                                    , TIPO_PEDIDO_SAP = 'ZTLF'
                                    , TIPO_ZTLI = 'Modificacion Importes'
                                    , INDICADOR_LIBERACION = 'En Liberación'
                                    , ID_PROVEEDOR = $rowOrdenContratacion->ID_PROVEEDOR
                                    , ESTADO = 'Creado'
                                    , ID_ORDEN_TRANSPORTE = $rowOrdenContratacion->ID_ORDEN_TRANSPORTE
                                    , FECHA_CREACION = '" . date("Y-m-d") . "'
                                    , ID_ORDEN_CONTRATACION = $rowOrdenContratacion->ID_ORDEN_CONTRATACION";
        $bd->ExecSQL($sqlInsert);
        $idPedidoZTLF = $bd->IdAsignado();

        //DAMOS VALOR A PEDIDO_SAP YA QUE ES CLAVE UNICA
        $sqlUpdate = "UPDATE PEDIDO_ENTRADA SET
                                    PEDIDO_SAP = 'SGA" . str_pad((string)$idPedidoZTLF, 7, "0", STR_PAD_LEFT) . "'
                                    WHERE ID_PEDIDO_ENTRADA = $idPedidoZTLF";
        $bd->ExecSQL($sqlUpdate);

        // LOG MOVIMIENTOS
        $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Creación", "Pedido entrada", $idPedidoZTLF, "");

        //A NIVEL DE LINEA ENVIAMOS EL MATERIAL DE SERVICIOS

        //BUSCAMOS EL SERVICIO
        $rowServicio = $bd->VerReg("SERVICIO", "ID_SERVICIO", $rowOrdenContratacion->ID_SERVICIO);

        //BUSCAMOS EL MATERIAL
        $rowMaterial = $bd->VerReg("MATERIAL", "ID_MATERIAL", $rowServicio->ID_MATERIAL);

        //CREO LA LINEA DEL PEDIDO DE ENTRADA
        $sqlInsert = "INSERT INTO PEDIDO_ENTRADA_LINEA SET
                                    ID_PEDIDO_ENTRADA = $idPedidoZTLF
                                    , ID_ALMACEN = NULL
                                    , ID_CENTRO = $rowOrdenTransporte->ID_CENTRO_CONTRATANTE
                                    , ID_MATERIAL = $rowMaterial->ID_MATERIAL
                                    , ID_TIPO_BLOQUEO = NULL
                                    , UNIDAD_SAP = $rowMaterial->ID_UNIDAD_COMPRA
                                    , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                    , LINEA_PEDIDO_SAP = '" . str_pad((string)"10", 5, "0", STR_PAD_LEFT) . "'
                                    , CANTIDAD_SAP = 1
                                    , CANTIDAD = 1
                                    , CANTIDAD_PDTE = 1
                                    , NUMERO_CONTRATO =  '" . $bd->escapeCondicional($rowOrdenContratacion->NUMERO_CONTRATO) . "'
                                    , NUMERO_CONTRATO_LINEA = '" . $bd->escapeCondicional($rowOrdenContratacion->NUMERO_CONTRATO_LINEA) . "'
                                    , FECHA_ENTREGA = '" . date("Y-m-d") . "'
                                    , IMPORTE_TRANSMITIDO_A_SAP = $cantidadModificado
                                    , ID_MONEDA_TRANSMITIDA_A_SAP = $rowOrdenTransporte->ID_MONEDA
                                    , ESTADO = 'Sin Recepcionar'";
        $bd->ExecSQL($sqlInsert);

        return $idPedidoZTLF;

    }


    /**
     * @param $idOrdenTransporte ORDEN DE TRANSPORTE
     * GENERA LOS PEDIDOS ZTLI PARA INFORMAR DE LOS GATOS CUANTO TODAS LAS LINEAS SON GRATUITAS
     * Devuelve el ID_PEDIDO_ENTRADA del ZTLI ficticio
     */
    function generarZTLIFicticioTransporteGratuito($idOrdenContratacion)
    {

        global $bd;
        global $html;
        global $auxiliar;
        global $administrador;
        global $mat;

        //BUSCO LA ORDEN DE CONTRATACION
        $rowOrdenContratacion = $bd->VerReg("ORDEN_CONTRATACION", "ID_ORDEN_CONTRATACION", $idOrdenContratacion);

        //BUSCO LA ORDEN DE TRANSPORTE
        $rowOrdenTransporte = $bd->VerReg("ORDEN_TRANSPORTE", "ID_ORDEN_TRANSPORTE", $rowOrdenContratacion->ID_ORDEN_TRANSPORTE);

        //CREO LA CABECERA DEL PEDIDO, ESTARA EN LIBERACION HASTA QUE NOS CAIGA DESDE SAP PARA PODER CONFIRMARLO
        $sqlInsert = "INSERT INTO PEDIDO_ENTRADA SET
                                    PEDIDO_SAP = ''
                                    , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                    , TIPO_PEDIDO = 'Servicios'
                                    , TIPO_PEDIDO_SAP = 'ZTLI'
                                    , TIPO_ZTLI = 'Transporte Gratuito'
                                    , INDICADOR_LIBERACION = 'En Liberación'
                                    , ID_PROVEEDOR = $rowOrdenContratacion->ID_PROVEEDOR
                                    , ESTADO = 'Creado'
                                    , ID_ORDEN_TRANSPORTE = $rowOrdenContratacion->ID_ORDEN_TRANSPORTE
                                    , FECHA_CREACION = '" . date("Y-m-d") . "'
                                    , ID_ORDEN_CONTRATACION = $rowOrdenContratacion->ID_ORDEN_CONTRATACION";
        $bd->ExecSQL($sqlInsert);
        $idPedidoZTLI = $bd->IdAsignado();

        //DAMOS VALOR A PEDIDO_SAP YA QUE ES CLAVE UNICA
        $sqlUpdate = "UPDATE PEDIDO_ENTRADA SET
                                    PEDIDO_SAP = 'SGA" . str_pad((string)$idPedidoZTLI, 7, "0", STR_PAD_LEFT) . "'
                                    WHERE ID_PEDIDO_ENTRADA = $idPedidoZTLI";
        $bd->ExecSQL($sqlUpdate);

        // LOG MOVIMIENTOS
        $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Creación", "Pedido entrada", $idPedidoZTLI, "");

        //A NIVEL DE LINEA ENVIAMOS EL MATERIAL DE SERVICIOS

        //BUSCAMOS EL SERVICIO
        $rowServicio = $bd->VerReg("SERVICIO", "ID_SERVICIO", $rowOrdenContratacion->ID_SERVICIO);

        //BUSCAMOS EL MATERIAL
        $rowMaterial = $bd->VerReg("MATERIAL", "ID_MATERIAL", $rowServicio->ID_MATERIAL);

        //OBTENEMOS EL IMPORTE
//        $importeContratacion = $auxiliar->formatoMoneda($rowOrdenContratacion->IMPORTE_MONEDA_SOCIEDAD, $rowOrdenTransporte->ID_MONEDA);
        $importeContratacion = $auxiliar->formatoMoneda($rowOrdenContratacion->IMPORTE, $rowOrdenContratacion->ID_MONEDA);

        //CREO LA LINEA DEL PEDIDO DE ENTRADA
        $sqlInsert = "INSERT INTO PEDIDO_ENTRADA_LINEA SET
                                    ID_PEDIDO_ENTRADA = $idPedidoZTLI
                                    , ID_ALMACEN = NULL
                                    , ID_CENTRO = $rowOrdenTransporte->ID_CENTRO_CONTRATANTE
                                    , ID_MATERIAL = $rowMaterial->ID_MATERIAL
                                    , ID_TIPO_BLOQUEO = NULL
                                    , UNIDAD_SAP = $rowMaterial->ID_UNIDAD_COMPRA
                                    , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                    , LINEA_PEDIDO_SAP = '" . str_pad((string)"10", 5, "0", STR_PAD_LEFT) . "'
                                    , CANTIDAD_SAP = 1
                                    , CANTIDAD = 1
                                    , CANTIDAD_PDTE = 1
                                    , NUMERO_CONTRATO =  '" . $bd->escapeCondicional($rowOrdenContratacion->NUMERO_CONTRATO) . "'
                                    , NUMERO_CONTRATO_LINEA = '" . $bd->escapeCondicional($rowOrdenContratacion->NUMERO_CONTRATO_LINEA) . "'
                                    , FECHA_ENTREGA = '" . date("Y-m-d") . "'
                                    , IMPORTE_TRANSMITIDO_A_SAP = $importeContratacion
                                    , ID_MONEDA_TRANSMITIDA_A_SAP = $rowOrdenContratacion->ID_MONEDA
                                    , ESTADO = 'Sin Recepcionar'";
        $bd->ExecSQL($sqlInsert);

        return $idPedidoZTLI;

    }

    /**
     * @param $idOrdenTransporte ORDEN DE TRANSPORTE
     * ANULA LOS PEDIDOS ZTL DE LA ORDEN DE TRANSPORTE
     */
    function anularPedidosZTLNoTransmitidos($idOrdenTransporte)
    {

        global $bd;
        global $html;
        global $auxiliar;
        global $administrador;
        global $mat;

        //BUSCO LA ORDEN DE RECOGIDA
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowOrdenTransporte               = $bd->VerReg("ORDEN_TRANSPORTE", "ID_ORDEN_TRANSPORTE", $idOrdenTransporte, "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);


        //EN CASO DE HABER PEDIDOS ZTL, LOS ANULAMOS
        //BUSCO SI LA ORDEN DE TRANSPORTE YA TIENE PEDIDOS
        $sqlPedidoServicios    = "SELECT PE.ID_PEDIDO_ENTRADA
                               FROM PEDIDO_ENTRADA PE
                               INNER JOIN ORDEN_TRANSPORTE OT ON OT.ID_ORDEN_TRANSPORTE = PE.ID_ORDEN_TRANSPORTE
                               WHERE PE.ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND PE.BAJA = 0";
        $resultPedidoServicios = $bd->ExecSQL($sqlPedidoServicios);


        //RECORRO LOS PEDIDOS PARA TRANSMITIRLOS A SAP
        while ($rowPedido = $bd->SigReg($resultPedidoServicios)):

            //BUSCO LAS LINEAS DEL PEDIDO PARA MARCARLAS DE BAJA
            $sqlLineasPedido    = "SELECT *
                                FROM PEDIDO_ENTRADA_LINEA
                                WHERE ID_PEDIDO_ENTRADA = $rowPedido->ID_PEDIDO_ENTRADA AND BAJA = 0";
            $resultLineasPedido = $bd->ExecSQL($sqlLineasPedido);

            //VARIABLE PARA SABER SI ESTABA ENVIADO A SAP
            $pedidoEnviadoSap = 0;

            while ($rowLineaPedido = $bd->SigReg($resultLineasPedido)):
                //DOY DE BAJA LAS LINEAS DEL PEDIDO DE SERVICIOS
                $sqlUpdate = "UPDATE PEDIDO_ENTRADA_LINEA SET
                              ID_ORDEN_TRABAJO_RELACIONADO = NULL
                              , ID_MOVIMIENTO_SALIDA_LINEA_RELACIONADA_MUEVE_MATERIAL = NULL 
                              , ID_PEDIDO_ENTRADA_LINEA_RELACIONADA_RECEPCIONA_MATERIAL = NULL 
                              , ID_ELEMENTO_IMPUTACION = NULL
                              , CODIGO_SELECCIONADO_ELEMENTO_IMPUTACION = ''
                              , INDICADOR_BORRADO = 'L'
                              , BAJA = 1
                              WHERE ID_PEDIDO_ENTRADA_LINEA = $rowLineaPedido->ID_PEDIDO_ENTRADA_LINEA";
                $bd->ExecSQL($sqlUpdate);

                //GUARDO SI ESTABA ENVIADO A SAP
                $pedidoEnviadoSap = $rowLineaPedido->ENVIADO_SAP;
            endwhile;
            //FIN BUSCO LAS LINEAS DEL PEDIDO PARA MARCARLAS DE BAJA

            //DOY DE BAJA EL PEDIDO DE SERVICIOS
            $sqlUpdate = "UPDATE PEDIDO_ENTRADA SET
                          BAJA = 1
                          WHERE ID_PEDIDO_ENTRADA = $rowPedido->ID_PEDIDO_ENTRADA";
            $bd->ExecSQL($sqlUpdate);

            //DOY DE BAJA LAS LINEAS DE LA TABLA PEDIDO SERVICIO LINEA
            $sqlUpdate = "UPDATE PEDIDO_SERVICIO_LINEA SET
                          BAJA = 1
                          WHERE ID_PEDIDO_ENTRADA = $rowPedido->ID_PEDIDO_ENTRADA";
            $bd->ExecSQL($sqlUpdate);
        endwhile;
        //FIN EN CASO DE HABER PEDIDOS ZTL, LOS ANULAMOS


    }

    /**
     * @param $idOrdenTransporte ORDEN DE TRANSPORTE SOBRE LA QUE CALCULAR EL ADR
     * FUNCION UTILIZADA PARA CALCULAR EL ADR DE LA ORDEN DE TRANSPORTE
     */
    function calcularADROrdenTransporte($idOrdenTransporte)
    {
        global $bd;
        global $html;
        global $administrador;

        //BUSCO LA ORDEN DE TRANSPORTE
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowOrdenTransporte               = $bd->VerReg("ORDEN_TRANSPORTE", "ID_ORDEN_TRANSPORTE", $idOrdenTransporte, "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);

        //COMPRUEBO QUE EXISTA LA ORDEN DE TRANSPORTE
        $html->PagErrorCondicionado($rowOrdenTransporte, "==", false, "OrdenTransporteNoEncontrada");

        //COMPRUEBO QUE LA ORDEN DE TRANSPORTE NO ESTE DADA DE BAJA
        $html->PagErrorCondicionado($rowOrdenTransporte->BAJA, "!=", 0, "OrdenTransporteBaja");

        //INICIALIZAMOS EL VALOR
        $adrRecogidas = 'No ADR';

        //BUSCO LAS ORDENES DE RECOGIDA
        $sqlOrdenesRecogida    = "SELECT ADR
                                FROM EXPEDICION
                                WHERE ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND BAJA = 0";
        $resultOrdenesRecogida = $bd->ExecSQL($sqlOrdenesRecogida);
        while (($rowOrdenRecogida = $bd->SigReg($resultOrdenesRecogida)) &&
            ($adrRecogidas != "ADR")
        ):

            if ($rowOrdenRecogida->ADR == "ADR"):
                $adrRecogidas = "ADR";
            elseif ($rowOrdenRecogida->ADR == "Exento"):
                $adrRecogidas = "Exento";
            endif;

        endwhile;


        //ACTUALIZO EL PESO DE LA ORDEN DE TRANSPORTE
        $sqlUpdate = "UPDATE ORDEN_TRANSPORTE SET
                        ID_ADMINISTRADOR_ULTIMA_MODIFICACION = $administrador->ID_ADMINISTRADOR
                        , FECHA_ULTIMA_MODIFICACION = '" . date("Y-m-d H:i:s") . "'
                        , ADR = '$adrRecogidas'
                        WHERE ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE";
        $bd->ExecSQL($sqlUpdate);
    }

    /**
     * @param $idOrdenTransporte ORDEN DE TRANSPORTE SOBRE LA QUE CALCULAR EL AMBITO Y COMUNIDAD
     * FUNCION UTILIZADA PARA CALCULAR EL AMBITO Y COMUNIDAD DE LA ORDEN DE TRANSPORTE
     */
    function calcularAmbitoYComunidadOrdenTransporte($idOrdenTransporte)
    {
        global $bd;
        global $html;
        global $administrador;

        //BUSCO LA ORDEN DE TRANSPORTE
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowOrdenTransporte               = $bd->VerReg("ORDEN_TRANSPORTE", "ID_ORDEN_TRANSPORTE", $idOrdenTransporte, "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);

        //COMPRUEBO QUE EXISTA LA ORDEN DE TRANSPORTE
        $html->PagErrorCondicionado($rowOrdenTransporte, "==", false, "OrdenTransporteNoEncontrada");

        //COMPRUEBO QUE LA ORDEN DE TRANSPORTE NO ESTE DADA DE BAJA
        $html->PagErrorCondicionado($rowOrdenTransporte->BAJA, "!=", 0, "OrdenTransporteBaja");

        //INICIALIZAMOS EL VALOR
        $nacionalRecogidas    = 'Nacional';
        $comunitarioRecogidas = "Comunitario";

        //SI ES TRANSPORTE CONSTRUCCION
        if ($rowOrdenTransporte->TIPO_ORDEN_TRANSPORTE == 'OTC'):

            //OBTENEMOS TODOS LOS CAMPOS DE OTC
            $rowOrdenTransporte = $this->getOrdenTransporteConstruccion($rowOrdenTransporte->ID_ORDEN_TRANSPORTE);

            //PUNTO ENTREGA SEGUN INCOTERM
            $rowDireccionOrigen = false;
            if ($rowOrdenTransporte->ID_DIRECCION_ENTREGA_INCOTERM != NULL):
                $rowDireccionOrigen = $bd->VerReg("DIRECCION", "ID_DIRECCION", $rowOrdenTransporte->ID_DIRECCION_ENTREGA_INCOTERM);
            endif;
            $rowDireccionDestino = false;
            //LUGAR ENTREGA FINAL
            if ($rowOrdenTransporte->ID_DIRECCION_ENTREGA_FINAL != NULL):
                $rowDireccionDestino = $bd->VerReg("DIRECCION", "ID_DIRECCION", $rowOrdenTransporte->ID_DIRECCION_ENTREGA_FINAL);
            endif;

            //BUSCAMOS LOS PAISES
            if (($rowDireccionOrigen->ID_PAIS != "") && ($rowDireccionDestino->ID_PAIS != "")):
                $rowPaisOrigen  = $bd->VerReg("PAIS", "ID_PAIS", $rowDireccionOrigen->ID_PAIS, "No");
                $rowPaisDestino = $bd->VerReg("PAIS", "ID_PAIS", $rowDireccionDestino->ID_PAIS, "No");

                //SI SON DISTINTOS MARCAMOS COMO INTERNACIONAL
                if ($rowPaisOrigen->ID_PAIS != $rowPaisDestino->ID_PAIS):
                    $nacionalRecogidas = 'Internacional';
                endif;

                //SI SON DISTINTAS COMUNIDADES MARCAMOS COMO EXTRACOMUNITARIO
                if ($rowPaisOrigen->COMUNIDAD != $rowPaisDestino->COMUNIDAD):
                    $comunitarioRecogidas = 'Extracomunitario';
                endif;
            endif;

        else://TRANSPORTES NORMALES
            //BUSCO LAS ORDENES DE RECOGIDA INTERNACIONALES
            $sqlOrdenesRecogida    = "SELECT NACIONAL
                                    FROM EXPEDICION
                                    WHERE ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND BAJA = 0 AND NACIONAL='Internacional'";
            $resultOrdenesRecogida = $bd->ExecSQL($sqlOrdenesRecogida);

            if ($rowOrdenRecogida = $bd->SigReg($resultOrdenesRecogida)):

                $nacionalRecogidas = 'Internacional';

            endif;

            //BUSCO LAS ORDENES DE RECOGIDA EXTRACOMUNITARIAS
            $sqlOrdenesRecogida    = "SELECT COMUNITARIO
                                    FROM EXPEDICION
                                    WHERE ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND BAJA = 0 AND COMUNITARIO='Extracomunitario'";
            $resultOrdenesRecogida = $bd->ExecSQL($sqlOrdenesRecogida);

            if ($rowOrdenRecogida = $bd->SigReg($resultOrdenesRecogida)):

                $comunitarioRecogidas = 'Extracomunitario';

            endif;
        endif;


        //ACTUALIZO EL PESO DE LA ORDEN DE TRANSPORTE
        $sqlUpdate = "UPDATE ORDEN_TRANSPORTE SET
                        ID_ADMINISTRADOR_ULTIMA_MODIFICACION = $administrador->ID_ADMINISTRADOR
                        , FECHA_ULTIMA_MODIFICACION = '" . date("Y-m-d H:i:s") . "'
                        , NACIONAL = '$nacionalRecogidas'
                        , COMUNITARIO = '$comunitarioRecogidas'
                        WHERE ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE";
        $bd->ExecSQL($sqlUpdate);
    }

    /**
     * @param $idOrdenTransporte ORDEN DE TRANSPORTE SOBRE LA QUE CALCULAR
     * FUNCION UTILIZADA PARA CALCULAR SI UNA ORDEN DE TRANSPORTE TIENE RECOGIDAS TRASLADO Y VENTAS DE TIPO INTERNACIONAL CON DESTINO SIN GESTION DE TRANSPORTE
     * DEVUELVE EL ID DE LA SOCIEDAD DE DESTINO SIN GESTION DE TRANSPORTE SI ES EL DESTINO INTERNACIONAL NO TIENE GESTION DE TRANSPROTE , false EN OTRO CASO
     */
    function DestinoInternacionalSinGestionTransporte($idOrdenTransporte)
    {

        global $bd;
        global $html;
        global $administrador;

        //BUSCO LA ORDEN DE TRANSPORTE
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowOrdenTransporte               = $bd->VerReg("ORDEN_TRANSPORTE", "ID_ORDEN_TRANSPORTE", $idOrdenTransporte, "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);

        //BUSCAMOS LOS TRASLADOS Y VENTAS INTERNACIONALES
        $sqlExpedicion = "SELECT *
                            FROM EXPEDICION
                            WHERE ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND NACIONAL = 'Internacional' AND (SUBTIPO_ORDEN_RECOGIDA IN ('Traslados y Ventas', 'Material Estropeado entre Almacenes Intercompany', 'Traslado Multi-Estado')) AND BAJA = 0";

        $resultExpedicion = $bd->ExecSQL($sqlExpedicion);

        //RECCORER TRASLADOS Y VENTAS INTERNACIONALES (EXCEPTO PEDIDOS ZTRA Y ZTRB)
        while ($rowExpedicion = $bd->SigReg($resultExpedicion)):

            //SE DIVIDEN ENTRE CON BULTOS O SIN BULTOS
            if ($rowExpedicion->CON_BULTOS == 1):
                //BULTOS
                $sqlMSL = "SELECT DISTINCT MSL.ID_PEDIDO_SALIDA_LINEA, MSL.ID_PEDIDO_SALIDA, MSL.ID_ALMACEN
                    FROM MOVIMIENTO_SALIDA_LINEA MSL
                    INNER JOIN BULTO B ON B.ID_BULTO = MSL.ID_BULTO
                    INNER JOIN PEDIDO_SALIDA PS ON PS.ID_PEDIDO_SALIDA = MSL.ID_PEDIDO_SALIDA
                    WHERE MSL.ID_EXPEDICION = $rowExpedicion->ID_EXPEDICION AND (PS.TIPO_PEDIDO_SAP <> 'ZTRA' AND PS.TIPO_PEDIDO_SAP <> 'ZTRB') AND MSL.LINEA_ANULADA = 0 AND MSL.BAJA = 0";

            elseif ($rowExpedicion->CON_BULTOS == 0):
                //MOVIMIENTO_SALIDA
                $sqlMSL = "SELECT DISTINCT MSL.ID_PEDIDO_SALIDA_LINEA, MSL.ID_PEDIDO_SALIDA, MSL.ID_ALMACEN
                    FROM MOVIMIENTO_SALIDA_LINEA MSL
                    INNER JOIN MOVIMIENTO_SALIDA MS ON MS.ID_MOVIMIENTO_SALIDA = MSL.ID_MOVIMIENTO_SALIDA
                    INNER JOIN PEDIDO_SALIDA PS ON PS.ID_PEDIDO_SALIDA = MSL.ID_PEDIDO_SALIDA
                    WHERE MSL.ID_EXPEDICION = $rowExpedicion->ID_EXPEDICION AND (PS.TIPO_PEDIDO_SAP <> 'ZTRA' AND PS.TIPO_PEDIDO_SAP <> 'ZTRB') AND MSL.LINEA_ANULADA = 0 AND MSL.BAJA = 0";
            endif;

            $resultMSL = $bd->ExecSQL($sqlMSL);

            //RECORREMOS LOS MOVIMIENTOS DE LA RECOGIDA
            while ($rowMSL = $bd->SigReg($resultMSL)):

                //BUSCAMOS EL PAIS ORIGEN DEL ALMACEN
                unset($rowPaisOrigen);
                unset($rowPaisDestino);

                if ($rowMSL->ID_ALMACEN != ""):
                    //BUSCAMOS EL PAIS DEL ALMACEN ORIGEN(CENTRO FISICO)
                    $sqlPaisOrigen    = "SELECT DISTINCT P.ID_PAIS, P.COMUNIDAD
                                                        FROM ALMACEN A
                                                        INNER JOIN DIRECCION D ON A.ID_CENTRO_FISICO = D.ID_CENTRO_FISICO
                                                        INNER JOIN PAIS P ON P.ID_PAIS = D.ID_PAIS
                                                        WHERE D.TIPO_DIRECCION = 'Centro Fisico' AND A.ID_ALMACEN = $rowMSL->ID_ALMACEN AND D.BAJA = 0";
                    $resultPaisOrigen = $bd->ExecSQL($sqlPaisOrigen);
                    $rowPaisOrigen    = $bd->SigReg($resultPaisOrigen);
                endif;

                //BUSCAMOS EL TIPO DE PEDIDO PARA VER EL DESTINO
                $GLOBALS["NotificaErrorPorEmail"] = "No";
                $rowPedidoSalida                  = $bd->VerReg("PEDIDO_SALIDA", "ID_PEDIDO_SALIDA", $rowMSL->ID_PEDIDO_SALIDA, "No");

                //SI ES TRASLADO
                if (
                    ($rowPedidoSalida->TIPO_PEDIDO == 'Traslado' && $rowPedidoSalida->TIPO_TRASLADO != 'Desconectado') ||
                    ($rowPedidoSalida->TIPO_PEDIDO == 'Traspaso Entre Almacenes Material Estropeado') ||
                    ($rowPedidoSalida->TIPO_PEDIDO == 'Intra Centro Fisico')
                ):
                    //BUSCAMOS EL PSL
                    $rowPedidoSalidaLinea = $bd->VerReg("PEDIDO_SALIDA_LINEA", "ID_PEDIDO_SALIDA_LINEA", $rowMSL->ID_PEDIDO_SALIDA_LINEA, "No");

                    //BUSCAMOS EL PAIS DEL ALMACEN DESTINO(CENTRO FISICO)
                    $sqlPaisDestino    = "SELECT DISTINCT P.ID_PAIS, P.COMUNIDAD
                                                        FROM ALMACEN A
                                                        INNER JOIN DIRECCION D ON A.ID_CENTRO_FISICO = D.ID_CENTRO_FISICO
                                                        INNER JOIN PAIS P ON P.ID_PAIS = D.ID_PAIS
                                                        WHERE D.TIPO_DIRECCION = 'Centro Fisico' AND A.ID_ALMACEN = $rowPedidoSalidaLinea->ID_ALMACEN_DESTINO AND D.BAJA = 0";
                    $resultPaisDestino = $bd->ExecSQL($sqlPaisDestino);
                    $rowPaisDestino    = $bd->SigReg($resultPaisDestino);

                elseif ($rowPedidoSalida->TIPO_PEDIDO == 'Venta'):

                    //BUSCAMOS LA DIRECCION DEL CLIENTE
                    $sqlPaisDestino    = "SELECT DISTINCT P.ID_PAIS, P.COMUNIDAD
                                                FROM DIRECCION D
                                                INNER JOIN PAIS P ON P.ID_PAIS = D.ID_PAIS
                                                WHERE D.TIPO_DIRECCION = 'Cliente' AND D.ID_CLIENTE = $rowPedidoSalida->ID_CLIENTE AND D.BAJA = 0";
                    $resultPaisDestino = $bd->ExecSQL($sqlPaisDestino);
                    $rowPaisDestino    = $bd->SigReg($resultPaisDestino);


                else://SI NO SON TRASLADOS NI VENTAS, CONTINUAMOS

                    continue;

                endif;


                if ($rowPaisDestino && $rowPaisOrigen):
                    //SI SON DISTINTOS ES INTERNACIONAL, COMPROBAMOS QUE EL CENTRO DESTINO TIENE GESTION TRANSPORTE
                    if ($rowPaisDestino->ID_PAIS != $rowPaisOrigen->ID_PAIS):

                        //SI ES VENTA, BUSCAMOS LA SOCIEDAD DE ORIGEN
                        if ($rowPedidoSalida->TIPO_PEDIDO == 'Venta'):
                            $rowAlmacenDestino  = $bd->VerReg("ALMACEN", "ID_ALMACEN", $rowMSL->ID_ALMACEN, "No");
                            $rowCentroDestino   = $bd->VerReg("CENTRO", "ID_CENTRO", $rowAlmacenDestino->ID_CENTRO, "No");
                            $rowSociedadDestino = $bd->VerReg("SOCIEDAD", "ID_SOCIEDAD", $rowCentroDestino->ID_SOCIEDAD, "No");

                            //DEVOLVEMOS LA SOCIEDAD ORIGEN DE LA VENTA SI NO TIENE GESTION DE TRANSPORTE
                            if ($rowSociedadDestino->GESTION_TRANSPORTE == 0):
                                return $rowSociedadDestino->ID_SOCIEDAD;
                            endif;

                        //SI ES TRASLADO, LA DE DESTINO
                        else:
                            $rowAlmacenDestino  = $bd->VerReg("ALMACEN", "ID_ALMACEN", $rowPedidoSalidaLinea->ID_ALMACEN_DESTINO, "No");
                            $rowCentroDestino   = $bd->VerReg("CENTRO", "ID_CENTRO", $rowAlmacenDestino->ID_CENTRO, "No");
                            $rowSociedadDestino = $bd->VerReg("SOCIEDAD", "ID_SOCIEDAD", $rowCentroDestino->ID_SOCIEDAD, "No");

                            if ($rowSociedadDestino->GESTION_TRANSPORTE == 0):
                                return $rowSociedadDestino->ID_SOCIEDAD;
                            endif;
                        endif;

                    endif;
                    //FIN SI SON DISTINTOS ES INTERNACIONAL, COMPROBAMOS QUE EL CENTRO DESTINO TIENE GESTION TRANSPORTE
                endif;

            endwhile;//FIN RECORRER MOVIMIENTOS DE LA RECOGIDA

        endwhile;//FIN RECCORER TRASLADOS Y VENTAS INTERNACIONALES

        return false;

    }

    /**
     * @param $idOrdenTransporte ORDEN DE TRANSPORTE SOBRE LA QUE CALCULAR
     * FUNCION UTILIZADA PARA CALCULAR SI UNA ORDEN DE TRANSPORTE ES INTERNACIONAL
     ******************************************************************************** IMPORTANTE ********************************************************************************
     * SOLO COMPRUEBA SI ES INTERNACIONAL AL MOVER MATERIAL ENTRE ALMACENES DE ACCIONA O VENTAS, LOS ENVIOS A PROVEEDOR SIEMPRE DEVUELVE false
     ****************************************************************************** FIN IMPORTANTE ******************************************************************************
     * DEVUELVE EL ID DE LA SOCIEDAD DE DESTINO SI ES EL DESTINO INTERNACIONAL , false EN OTRO CASO
     */
    function DestinoInternacionalTransporte($idOrdenTransporte)
    {

        global $bd;
        global $html;
        global $administrador;

        //BUSCO LA ORDEN DE TRANSPORTE
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowOrdenTransporte               = $bd->VerReg("ORDEN_TRANSPORTE", "ID_ORDEN_TRANSPORTE", $idOrdenTransporte, "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);

        //BUSCAMOS LOS TRASLADOS Y VENTAS INTERNACIONALES
        $sqlExpedicion = "SELECT *
                            FROM EXPEDICION
                            WHERE ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND NACIONAL = 'Internacional' AND (SUBTIPO_ORDEN_RECOGIDA IN ('Traslados y Ventas', 'Material Estropeado entre Almacenes Intercompany', 'Traslado Multi-Estado')) AND BAJA = 0";

        $resultExpedicion = $bd->ExecSQL($sqlExpedicion);

        //RECCORER TRASLADOS Y VENTAS INTERNACIONALES (EXCEPTO PEDIDOS ZTRA Y ZTRB)
        while ($rowExpedicion = $bd->SigReg($resultExpedicion)):

            //SE DIVIDEN ENTRE CON BULTOS O SIN BULTOS
            if ($rowExpedicion->CON_BULTOS == 1):
                //BULTOS
                $sqlMSL = "SELECT DISTINCT MSL.ID_PEDIDO_SALIDA_LINEA, MSL.ID_PEDIDO_SALIDA, MSL.ID_ALMACEN
                    FROM MOVIMIENTO_SALIDA_LINEA MSL
                    INNER JOIN BULTO B ON B.ID_BULTO = MSL.ID_BULTO
                    INNER JOIN PEDIDO_SALIDA PS ON PS.ID_PEDIDO_SALIDA = MSL.ID_PEDIDO_SALIDA
                    WHERE MSL.ID_EXPEDICION = $rowExpedicion->ID_EXPEDICION AND (PS.TIPO_PEDIDO_SAP <> 'ZTRA' AND PS.TIPO_PEDIDO_SAP <> 'ZTRB') AND MSL.LINEA_ANULADA = 0 AND MSL.BAJA = 0";

            elseif ($rowExpedicion->CON_BULTOS == 0):
                //MOVIMIENTO_SALIDA
                $sqlMSL = "SELECT DISTINCT MSL.ID_PEDIDO_SALIDA_LINEA, MSL.ID_PEDIDO_SALIDA, MSL.ID_ALMACEN
                    FROM MOVIMIENTO_SALIDA_LINEA MSL
                    INNER JOIN MOVIMIENTO_SALIDA MS ON MS.ID_MOVIMIENTO_SALIDA = MSL.ID_MOVIMIENTO_SALIDA
                    INNER JOIN PEDIDO_SALIDA PS ON PS.ID_PEDIDO_SALIDA = MSL.ID_PEDIDO_SALIDA
                    WHERE MSL.ID_EXPEDICION = $rowExpedicion->ID_EXPEDICION AND (PS.TIPO_PEDIDO_SAP <> 'ZTRA' AND PS.TIPO_PEDIDO_SAP <> 'ZTRB') AND MSL.LINEA_ANULADA = 0 AND MSL.BAJA = 0";
            endif;

            $resultMSL = $bd->ExecSQL($sqlMSL);

            //RECORREMOS LOS MOVIMIENTOS DE LA RECOGIDA
            while ($rowMSL = $bd->SigReg($resultMSL)):

                //BUSCAMOS EL PAIS ORIGEN DEL ALMACEN
                unset($rowPaisOrigen);
                unset($rowPaisDestino);

                if ($rowMSL->ID_ALMACEN != ""):
                    //BUSCAMOS EL PAIS DEL ALMACEN ORIGEN(CENTRO FISICO)
                    $sqlPaisOrigen    = "SELECT DISTINCT P.ID_PAIS, P.COMUNIDAD
                                                        FROM ALMACEN A
                                                        INNER JOIN DIRECCION D ON A.ID_CENTRO_FISICO = D.ID_CENTRO_FISICO
                                                        INNER JOIN PAIS P ON P.ID_PAIS = D.ID_PAIS
                                                        WHERE D.TIPO_DIRECCION = 'Centro Fisico' AND A.ID_ALMACEN = $rowMSL->ID_ALMACEN AND D.BAJA = 0";
                    $resultPaisOrigen = $bd->ExecSQL($sqlPaisOrigen);
                    $rowPaisOrigen    = $bd->SigReg($resultPaisOrigen);
                endif;

                //BUSCAMOS EL TIPO DE PEDIDO PARA VER EL DESTINO
                $GLOBALS["NotificaErrorPorEmail"] = "No";
                $rowPedidoSalida                  = $bd->VerReg("PEDIDO_SALIDA", "ID_PEDIDO_SALIDA", $rowMSL->ID_PEDIDO_SALIDA, "No");

                //SI ES TRASLADO
                if (
                    ($rowPedidoSalida->TIPO_PEDIDO == 'Traslado' && $rowPedidoSalida->TIPO_TRASLADO != 'Desconectado') ||
                    ($rowPedidoSalida->TIPO_PEDIDO == 'Traspaso Entre Almacenes Material Estropeado') ||
                    ($rowPedidoSalida->TIPO_PEDIDO == 'Intra Centro Fisico')
                ):
                    //BUSCAMOS EL PSL
                    $rowPedidoSalidaLinea = $bd->VerReg("PEDIDO_SALIDA_LINEA", "ID_PEDIDO_SALIDA_LINEA", $rowMSL->ID_PEDIDO_SALIDA_LINEA, "No");

                    //BUSCAMOS EL PAIS DEL ALMACEN DESTINO(CENTRO FISICO)
                    $sqlPaisDestino    = "SELECT DISTINCT P.ID_PAIS, P.COMUNIDAD
                                                        FROM ALMACEN A
                                                        INNER JOIN DIRECCION D ON A.ID_CENTRO_FISICO = D.ID_CENTRO_FISICO
                                                        INNER JOIN PAIS P ON P.ID_PAIS = D.ID_PAIS
                                                        WHERE D.TIPO_DIRECCION = 'Centro Fisico' AND A.ID_ALMACEN = $rowPedidoSalidaLinea->ID_ALMACEN_DESTINO AND D.BAJA = 0";
                    $resultPaisDestino = $bd->ExecSQL($sqlPaisDestino);
                    $rowPaisDestino    = $bd->SigReg($resultPaisDestino);

                elseif ($rowPedidoSalida->TIPO_PEDIDO == 'Venta'):

                    //BUSCAMOS LA DIRECCION DEL CLIENTE
                    $sqlPaisDestino    = "SELECT DISTINCT P.ID_PAIS, P.COMUNIDAD
                                                FROM DIRECCION D
                                                INNER JOIN PAIS P ON P.ID_PAIS = D.ID_PAIS
                                                WHERE D.TIPO_DIRECCION = 'Cliente' AND D.ID_CLIENTE = $rowPedidoSalida->ID_CLIENTE AND D.BAJA = 0";
                    $resultPaisDestino = $bd->ExecSQL($sqlPaisDestino);
                    $rowPaisDestino    = $bd->SigReg($resultPaisDestino);


                else://SI NO SON TRASLADOS NI VENTAS, CONTINUAMOS

                    continue;

                endif;


                if ($rowPaisDestino && $rowPaisOrigen):
                    //SI SON DISTINTOS ES INTERNACIONAL
                    if ($rowPaisDestino->ID_PAIS != $rowPaisOrigen->ID_PAIS):

                        //SI ES VENTA, BUSCAMOS LA SOCIEDAD DE ORIGEN
                        if ($rowPedidoSalida->TIPO_PEDIDO == 'Venta'):
                            $rowAlmacenDestino  = $bd->VerReg("ALMACEN", "ID_ALMACEN", $rowMSL->ID_ALMACEN, "No");
                            $rowCentroDestino   = $bd->VerReg("CENTRO", "ID_CENTRO", $rowAlmacenDestino->ID_CENTRO, "No");
                            $rowSociedadDestino = $bd->VerReg("SOCIEDAD", "ID_SOCIEDAD", $rowCentroDestino->ID_SOCIEDAD, "No");

                            //DEVOLVEMOS LA SOCIEDAD ORIGEN DE LA VENTA
                            return $rowSociedadDestino->ID_SOCIEDAD;

                        //SI ES TRASLADO, LA DE DESTINO
                        else:
                            $rowAlmacenDestino  = $bd->VerReg("ALMACEN", "ID_ALMACEN", $rowPedidoSalidaLinea->ID_ALMACEN_DESTINO, "No");
                            $rowCentroDestino   = $bd->VerReg("CENTRO", "ID_CENTRO", $rowAlmacenDestino->ID_CENTRO, "No");
                            $rowSociedadDestino = $bd->VerReg("SOCIEDAD", "ID_SOCIEDAD", $rowCentroDestino->ID_SOCIEDAD, "No");

                            return $rowSociedadDestino->ID_SOCIEDAD;
                        endif;

                    endif;
                    //FIN SI SON DISTINTOS ES INTERNACIONAL
                endif;

            endwhile;//FIN RECORRER MOVIMIENTOS DE LA RECOGIDA

        endwhile;//FIN RECCORER TRASLADOS Y VENTAS INTERNACIONALES

        return false;

    }

    function getTipoFactura($idMovSalLin = '')
    {
        global $bd;

        if ($idMovSalLin != ""):
            $numC = $bd->NumRegsTabla("FACTURA_COMERCIAL_MOVIMIENTO", "ID_MOVIMIENTO_SALIDA_LINEA = $idMovSalLin AND BAJA = 0");
            if ($numC > 0):
                return "Comercial";
            else:
                $numNC = $bd->NumRegsTabla("FACTURA_NO_COMERCIAL_MOVIMIENTO", "ID_MOVIMIENTO_SALIDA_LINEA = $idMovSalLin AND BAJA = 0");
                if ($numNC > 0):
                    return "No Comercial";
                endif;
            endif;
        endif;

        return NULL;

    }

    function getTipoFacturaOT($idOT)
    {
        global $bd;

        if ($idOT != ""):
            $numC = $bd->NumRegsTabla("FACTURA_COMERCIAL", "ID_ORDEN_TRANSPORTE = $idOT AND BAJA = 0");
            if ($numC > 0):
                return "Comercial";
            else:
                $numNC = $bd->NumRegsTabla("FACTURA_NO_COMERCIAL", "ID_ORDEN_TRANSPORTE = $idOT AND BAJA = 0");
                if ($numNC > 0):
                    return "No Comercial";
                endif;
            endif;
        endif;

        return "No Comercial";

    }

    function getNumeroFacturaOT($idOT)
    {
        global $bd;

        if ($idOT != ""):

            //BUSCAMOS SI TIENE NUMERO DE FACTURA COMERCIAL O NO COMERCIAL
            $sqlFacturaC    = "SELECT NUMERO_FACTURA
                                   FROM FACTURA_COMERCIAL
                                   WHERE ID_ORDEN_TRANSPORTE = $idOT AND BAJA = 0";
            $resultFacturaC = $bd->ExecSQL($sqlFacturaC);

            //SI TIENE NUMERO DE FACTURA, SERA LO QUE DEVOLVAMOS
            if ($rowFacturaC = $bd->SigReg($resultFacturaC)):
                return $rowFacturaC->NUMERO_FACTURA;
            else:
                $sqlFacturaNC    = "SELECT NUMERO_FACTURA
                                FROM FACTURA_NO_COMERCIAL 
                                WHERE ID_ORDEN_TRANSPORTE = $idOT AND BAJA = 0";
                $resultFacturaNC = $bd->ExecSQL($sqlFacturaNC);
                if ($rowFacturaNC = $bd->SigReg($resultFacturaNC)):
                    return $rowFacturaNC->NUMERO_FACTURA;
                endif;
            endif;

        endif;

        return NULL;
    }

    /**
     * @param $idMovimientoSalida MOVIMIENTO DE SALIDA QUE TIENE LA FACTURA
     * @return string DEVUELVE NUMERO DE FACTURA SI EXISTE
     */
    function getNumeroFacturaMovimiento($idMovSalLin = '')
    {
        global $bd;

        if ($idMovSalLin != ""):
            //BUSCAMOS SI TIENE NUMERO DE FACTURA COMERCIAL O NO COMERCIAL
            $sqlFacturaC    = "SELECT NUMERO_FACTURA
                                   FROM FACTURA_COMERCIAL FC
                                   INNER JOIN FACTURA_COMERCIAL_MOVIMIENTO FCM ON FCM.ID_FACTURA_COMERCIAL = FC.ID_FACTURA_COMERCIAL
                                   WHERE FCM.ID_MOVIMIENTO_SALIDA_LINEA = $idMovSalLin AND FC.BAJA = 0 AND FCM.BAJA = 0";
            $resultFacturaC = $bd->ExecSQL($sqlFacturaC);

            //SI TIENE NUMERO DE FACTURA, SERA LO QUE DEVOLVAMOS
            if ($rowFacturaC = $bd->SigReg($resultFacturaC)):
                return $rowFacturaC->NUMERO_FACTURA;
            else:
                $sqlFacturaNC    = "SELECT NUMERO_FACTURA
                                FROM FACTURA_NO_COMERCIAL FNC
                                INNER JOIN FACTURA_NO_COMERCIAL_MOVIMIENTO FNCM ON FNCM.ID_FACTURA_NO_COMERCIAL = FNC.ID_FACTURA_NO_COMERCIAL
                                WHERE FNCM.ID_MOVIMIENTO_SALIDA_LINEA = $idMovSalLin AND FNC.BAJA = 0 AND FNCM.BAJA = 0";
                $resultFacturaNC = $bd->ExecSQL($sqlFacturaNC);
                if ($rowFacturaNC = $bd->SigReg($resultFacturaNC)):
                    return $rowFacturaNC->NUMERO_FACTURA;
                endif;
            endif;

        endif;

        return NULL;
    }

    function getNumFacturaNoComercial($idMovSalLin)
    {
        global $bd;

        $sqlFacturaNC    = "SELECT NUMERO_FACTURA
                                FROM FACTURA_NO_COMERCIAL FNC
                                INNER JOIN FACTURA_NO_COMERCIAL_MOVIMIENTO FNCM ON FNCM.ID_FACTURA_NO_COMERCIAL = FNC.ID_FACTURA_NO_COMERCIAL
                                WHERE FNCM.ID_MOVIMIENTO_SALIDA_LINEA = $idMovSalLin AND FNC.BAJA = 0 AND FNCM.BAJA = 0";
        $resultFacturaNC = $bd->ExecSQL($sqlFacturaNC);
        $rowFacturaNC    = $bd->SigReg($resultFacturaNC);

        return $rowFacturaNC->NUMERO_FACTURA;
    }

    function getNumFacturaComercialAnulada($idMovSalLin)
    {
        global $bd;

        $listaFacturas = "";
        $coma          = "";

        $sqlFacturaCA    = "SELECT NUMERO_FACTURA
                            FROM FACTURA_COMERCIAL FC
                            INNER JOIN FACTURA_COMERCIAL_MOVIMIENTO FCM ON FCM.ID_FACTURA_COMERCIAL = FC.ID_FACTURA_COMERCIAL
                            WHERE FCM.ID_MOVIMIENTO_SALIDA_LINEA = $idMovSalLin AND FC.BAJA = 1 AND FCM.BAJA = 1";
        $resultFacturaCA = $bd->ExecSQL($sqlFacturaCA);
        while ($rowFacturaCA = $bd->SigReg($resultFacturaCA)):
            $listaFacturas .= $coma . $rowFacturaCA->NUMERO_FACTURA;
            $coma          = ",";
        endwhile;

        return $listaFacturas;
    }

    /**
     * @param $idMovimientoSalida MOVIMIENTO DE SALIDA QUE TIENE LA FACTURA
     * @return string DEVUELVE NUMERO DE POSICION FACTURA SI EXISTE
     */
    function getNumeroPosicionFacturaMovimiento($idMovSalLin = '')
    {
        global $bd;

        if ($idMovSalLin != ""):

            //BUSCAMOS SI TIENE NUMERO DE FACTURA COMERCIAL O NO COMERCIAL
            $sqlFacturaC    = "SELECT NUMERO_POSICION_FACTURA
                                   FROM FACTURA_COMERCIAL_MOVIMIENTO
                                   WHERE ID_MOVIMIENTO_SALIDA_LINEA = $idMovSalLin AND BAJA = 0";
            $resultFacturaC = $bd->ExecSQL($sqlFacturaC);

            //SI TIENE NUMERO DE FACTURA, SERA LO QUE DEVOLVAMOS
            if ($rowFacturaC = $bd->SigReg($resultFacturaC)):
                return $rowFacturaC->NUMERO_POSICION_FACTURA;
            else:
                $sqlFacturaNC    = "SELECT NUMERO_POSICION_FACTURA
                                FROM FACTURA_NO_COMERCIAL_MOVIMIENTO
                                WHERE ID_MOVIMIENTO_SALIDA_LINEA = $idMovSalLin AND BAJA = 0";
                $resultFacturaNC = $bd->ExecSQL($sqlFacturaNC);
                if ($rowFacturaNC = $bd->SigReg($resultFacturaNC)):
                    return $rowFacturaNC->NUMERO_POSICION_FACTURA;
                endif;
            endif;

        endif;

        return NULL;
    }

    function getNumPosFacturaNoComercial($idMovSalLin)
    {
        global $bd;

        $sqlFacturaNC    = "SELECT NUMERO_POSICION_FACTURA
                                FROM FACTURA_NO_COMERCIAL_MOVIMIENTO
                                WHERE ID_MOVIMIENTO_SALIDA_LINEA = $idMovSalLin AND BAJA = 0";
        $resultFacturaNC = $bd->ExecSQL($sqlFacturaNC);
        $rowFacturaNC    = $bd->SigReg($resultFacturaNC);

        return $rowFacturaNC->NUMERO_POSICION_FACTURA;

    }

    function getNumPosFacturaComercialAnulada($idMovSalLin)
    {
        global $bd;

        $listaPosFacturas = "";
        $coma             = "";

        $sqlFacturaCA    = "SELECT NUMERO_POSICION_FACTURA
                                FROM FACTURA_COMERCIAL_MOVIMIENTO
                                WHERE ID_MOVIMIENTO_SALIDA_LINEA = $idMovSalLin AND BAJA = 1";
        $resultFacturaCA = $bd->ExecSQL($sqlFacturaCA);
        while ($rowFacturaCA = $bd->SigReg($resultFacturaCA)):
            $listaPosFacturas .= $coma . $rowFacturaCA->NUMERO_POSICION_FACTURA;
            $coma             = ",";
        endwhile;

        return $listaPosFacturas;

    }

    /**
     * @param $idOrdenTransporte ORDEN DE TRANSPORTE
     * Si todas sus recogidas cumplen las condiciones, se finaliza automaticamente el transporte. Ademas comprueba si han finalizado las recogidas de las solicitudes para actualziar sus estados
     */
    function actualizarEstadoOrdenTransporte($idOrdenTransporte)
    {

        global $bd;
        global $html;
        global $auxiliar;
        global $administrador;


        //BUSCO LA ORDEN DE RECOGIDA
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowOrdenTransporte               = $bd->VerReg("ORDEN_TRANSPORTE", "ID_ORDEN_TRANSPORTE", $idOrdenTransporte, "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);

        //LOS TRANSPORTES DE CONSTRUCCION NO SE ACTUALIZA EL ESTADO AUTOMATICAMENTE
        if ($rowOrdenTransporte->TIPO_ORDEN_TRANSPORTE == "OTC"):
            return false;
        endif;

        //VALOR POR DEFECTO DEL ESTADO DE LA ORDEN DE TRANSPORTE
        $ordenFinalizada = true;

        //BUSCAMOS QUE TENGA RECOGIDAS
        $numRecogidas = $bd->NumRegsTabla("EXPEDICION", "BAJA = 0 AND ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE");
        if ($numRecogidas == 0):
            //SI TIENE GASTOS Y LAS INTERFACES ESTAN TRANSMITIDAS Y TODAS LAS RECOGIDAS SE HAN DADO DE BAJA, DEJAMOS LA ORDEN COMO FINALIZADA
            if (($rowOrdenTransporte->TIENE_GASTOS_TRANSPORTE == 0) ||
                (($rowOrdenTransporte->TIENE_GASTOS_TRANSPORTE == 1) && ($rowOrdenTransporte->ESTADO_INTERFACES != "Finalizada"))
            ):
                $ordenFinalizada = false;
            endif;
        endif;

        //BUSCAMOS LAS RECOGIDAS OPERACIONES EN PARQUE Y FUERA DE SISTEMA QUE NO ESTEN RECEPCIONADAS
        $sqlOperacionesNoRecepcionadas    = "SELECT ID_EXPEDICION
                                            FROM EXPEDICION
                                            WHERE ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND (TIPO_ORDEN_RECOGIDA = 'Operaciones fuera de Sistema' OR TIPO_ORDEN_RECOGIDA = 'Operaciones en Parque') AND ESTADO <> 'Recepcionada' AND BAJA = 0";
        $resultOperacionesNoRecepcionadas = $bd->ExecSQL($sqlOperacionesNoRecepcionadas);

        $numOperacionesNoRecepcionadas = $bd->NumRegs($resultOperacionesNoRecepcionadas);
        //SI QUEDA ALGUNA POR RECEPCIONAR , NO FINALIZAR
        if ($numOperacionesNoRecepcionadas > 0):
            $ordenFinalizada = false;
        endif;

        //SI ES SIN PEDIDO CONOCIDO, COMPROBAMOS QUE ESTA ASOCIADO A SU RECEPCION
        $numRecogidasSinPedidoConocido = $bd->NumRegsTabla("EXPEDICION", "BAJA=0 AND ID_ORDEN_TRANSPORTE=$rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND SUBTIPO_ORDEN_RECOGIDA='Sin Pedido Conocido'");
        if ($numRecogidasSinPedidoConocido > 0):
            $numRecepcionesOT = $bd->NumRegsTabla("MOVIMIENTO_RECEPCION", "BAJA=0 AND ID_ORDEN_TRANSPORTE=$rowOrdenTransporte->ID_ORDEN_TRANSPORTE");
            if ($numRecepcionesOT == 0):
                $ordenFinalizada = false;
            endif;
        endif;

        //SI TIENE RECOGIDAS CON PEDIDO CONOCIDO, COMPROBAMOS SI TIENE LINEAS
        $sqlRecogidasProveedorConPedido    = "SELECT DISTINCT E.ID_EXPEDICION
                                                FROM EXPEDICION E
                                                INNER JOIN ORDEN_TRANSPORTE OT ON OT.ID_ORDEN_TRANSPORTE = E.ID_ORDEN_TRANSPORTE
                                                WHERE OT.ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND E.BAJA = 0 AND E.SUBTIPO_ORDEN_RECOGIDA = 'Con Pedido Conocido'";
        $resultRecogidasProveedorConPedido = $bd->ExecSQL($sqlRecogidasProveedorConPedido);
        while ($rowRecogidasConPedido = $bd->SigReg($resultRecogidasProveedorConPedido)):
            //BUSCAMOS SI TIENE LINEAS ACTIVAS
            $sqlLineas    = "SELECT * 
                          FROM EXPEDICION_PEDIDO_CONOCIDO 
                          WHERE BAJA = 0 AND ID_EXPEDICION = $rowRecogidasConPedido->ID_EXPEDICION";
            $resultLineas = $bd->ExecSQL($sqlLineas);
            while ($rowLinea = $bd->SigReg($resultLineas)):
                //CALCULO LA CANTIDAD ASIGNADA A LA ORDEN DE TRANSPORTE
                $cantidadAsignadaOrdenTransporte = $rowLinea->CANTIDAD - $rowLinea->CANTIDAD_NO_SERVIDA;

                //SI LA CANTIDAD ASIGNADA A LA ORDEN DE TRANSPORTE ES SUPERIOR A EPSILON
                if ($cantidadAsignadaOrdenTransporte > EPSILON_SISTEMA):
                    $sqlCantidadAsociadaOrdenTransporteRecepcionada    = "SELECT IF(SUM(CANTIDAD) IS NULL, 0, SUM(CANTIDAD)) AS CANTIDAD_RECEPCIONADA 
                                                                       FROM MOVIMIENTO_ENTRADA_LINEA MEL 
                                                                       WHERE MEL.ID_EXPEDICION_ENTREGA = $rowRecogidasConPedido->ID_EXPEDICION AND MEL.ID_PEDIDO_LINEA = $rowLinea->ID_PEDIDO_ENTRADA_LINEA AND MEL.LINEA_ANULADA = 0 AND MEL.BAJA = 0";
                    $resultCantidadAsociadaOrdenTransporteRecepcionada = $bd->ExecSQL($sqlCantidadAsociadaOrdenTransporteRecepcionada);
                    $rowCantidadAsociadaOrdenTransporteRecepcionada    = $bd->SigReg($resultCantidadAsociadaOrdenTransporteRecepcionada);
                    if ($rowCantidadAsociadaOrdenTransporteRecepcionada->CANTIDAD_RECEPCIONADA < $cantidadAsignadaOrdenTransporte):
                        $ordenFinalizada = false;
                    endif;
                endif;
            endwhile;
        endwhile;

        //BUSCAMOS LOS CF DE LAS RECOGIDAS EN PROVEEDOR SIN RECEPCION
        $arrCentroFisicoRecogidasProveedor = $this->getCentrosFisicosDestinoRecogidasProveedorSinRecepcion($rowOrdenTransporte->ID_ORDEN_TRANSPORTE);
        //SI HAY ALGUN CF SIN RECEPCION, NO FINALIZAR
        if (count((array)$arrCentroFisicoRecogidasProveedor) > 0):
            $ordenFinalizada = false;
        endif;

        //BUSCAMOS LOS DESTINOS DE LOS TRASLADOS
        $arrCentroFisicoTraslados = $this->getCentrosFisicosDestinoTraslado($rowOrdenTransporte->ID_ORDEN_TRANSPORTE);

        //BUSCAMOS SI TIENE RECOGIDAS QUE TENGAN CF DE DESTINO (NO INCLUIMOS EL ESTADO EXPEDIDA QUE ES DE PEDIDOS DE VENTA)
        $numRecogidasConCFDestino = $bd->NumRegsTabla("EXPEDICION", "BAJA=0 AND ID_ORDEN_TRANSPORTE=$rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND ESTADO<>'Expedida' AND (SUBTIPO_ORDEN_RECOGIDA='Traslados y Ventas' OR SUBTIPO_ORDEN_RECOGIDA='Material Estropeado entre Almacenes Intracompany' OR SUBTIPO_ORDEN_RECOGIDA='Material Estropeado entre Almacenes Intercompany' OR SUBTIPO_ORDEN_RECOGIDA='Retorno Material Estropeado desde Proveedor')");

        //SI NO HA ENCONTRADO LINEAS Y ES UN TRASLADO, LA OT NO ESTARA FINALIZADA
        if ((count((array)$arrCentroFisicoTraslados) == 0) && ($numRecogidasConCFDestino > 0)):
            $ordenFinalizada = false;
        endif;

        //RECORREMOS LOS CF DESTINO PARA COMPROBAR SI TIENEN RECEPCION ASOCIADA
        foreach ($arrCentroFisicoTraslados as $idCentroFisicoDestino):

            $sqlMovimientosRecepcion    = "SELECT * FROM MOVIMIENTO_RECEPCION MR WHERE MR.ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND MR.ID_CENTRO_FISICO = $idCentroFisicoDestino AND BAJA=0";
            $resultMovimientosRecepcion = $bd->ExecSQL($sqlMovimientosRecepcion);

            //SI NO TIENE RECEPCION, NO FINALIZAR
            if ($bd->NumRegs($resultMovimientosRecepcion) == 0):
                $ordenFinalizada = false;
            endif;
        endforeach;
        //FIN RECORREMOS LOS CF DESTINO PARA COMPRABAR SI TIENEN RECEPCION ASOCIADA


        //COMPROBAMOS QUE LAS RECOGIDAS QUE VAN A PROVEEDOR ESTAN EXPEDIDAS
        $sqlRecogidasAProveedor    = "SELECT ID_EXPEDICION
                                            FROM EXPEDICION
                                            WHERE ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND (SUBTIPO_ORDEN_RECOGIDA = 'Componentes a Proveedor' OR SUBTIPO_ORDEN_RECOGIDA = 'Devoluciones a Proveedor' OR SUBTIPO_ORDEN_RECOGIDA = 'Rechazos Anulaciones Proveedor') AND ESTADO <> 'Expedida' AND BAJA = 0";
        $resultRecogidasAProveedor = $bd->ExecSQL($sqlRecogidasAProveedor);

        $numRecogidasAProveedor = $bd->NumRegs($resultRecogidasAProveedor);
        //SI QUEDA ALGUNA POR RECEPCIONAR , NO FINALIZAR
        if ($numRecogidasAProveedor > 0):
            $ordenFinalizada = false;
        endif;


        //COMPROBAMOS QUE LAS RECOGIDAS DE MATERIAL ESTROPEADO ESTAN RECEPCIONADAS
        $sqlRecogidasEstropeadoAProveedor    = "SELECT ID_EXPEDICION
                                            FROM EXPEDICION
                                            WHERE ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND SUBTIPO_ORDEN_RECOGIDA IN ('Material Estropeado a Proveedor','Material Estropeado Entre Proveedores') AND ESTADO <> 'Recepcionada' AND BAJA = 0";
        $resultRecogidasEstropeadoAProveedor = $bd->ExecSQL($sqlRecogidasEstropeadoAProveedor);

        $numRecogidasEstropeadoAProveedor = $bd->NumRegs($resultRecogidasEstropeadoAProveedor);
        //SI QUEDA ALGUNA POR RECEPCIONAR , NO FINALIZAR
        if ($numRecogidasEstropeadoAProveedor > 0):
            $ordenFinalizada = false;
        endif;


        if ($ordenFinalizada):
            //SI NO HEMOS SALIDO DE LA FUNCION,
            $sqlUpdate = "UPDATE ORDEN_TRANSPORTE SET ESTADO = 'Finalizada' WHERE ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE ";
            $bd->ExecSQL($sqlUpdate);

            if ($rowOrdenTransporte->ESTADO != "Finalizada"):
                // LOG MOVIMIENTOS
                $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Orden de Transporte", $rowOrdenTransporte->ID_ORDEN_TRANSPORTE, "Finalizacion Automatica");
            endif;
        else:

            //COMPRUEBO QUE AL MENOS UNA DE SUS RECOGIDAS ESTAN Expedidas(o En tránsito o Recepcionada)
            $sqlRecogidasExpedidas    = "SELECT * 
                                         FROM EXPEDICION
                                         WHERE ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND (ESTADO = 'Expedida' OR ESTADO = 'En Tránsito' OR ESTADO = 'Recepcionada') AND BAJA = 0";
            $resultRecogidasExpedidas = $bd->ExecSQL($sqlRecogidasExpedidas);

            //SI NO QUEDA NINGUNA RECOGIDA EXPEDIDA, PASAMOS EL TRANSPORTE A ESTADO CREADA
            if ($bd->NumRegs($resultRecogidasExpedidas) == 0):
                //ESTABLEZCO EL ESTADO DE LA ORDEN DE TRANSPORTE
                $sqlUpdate = "UPDATE ORDEN_TRANSPORTE SET
                  ESTADO = 'Creada'
                  WHERE ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE";
                $bd->ExecSQL($sqlUpdate);
                if ($rowOrdenTransporte->ESTADO != "Creada"):
                    // LOG MOVIMIENTOS
                    $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Orden de Transporte", $rowOrdenTransporte->ID_ORDEN_TRANSPORTE, "Pasa a Estado Creada");
                endif;

            elseif ($bd->NumRegs($resultRecogidasExpedidas) > 0): // SI HAY ALGUNA RECOGIDA EXPEDIDA, EL TRANSPORTE LO PONEMOS 'En Transito'
                //ESTABLEZCO EL ESTADO DE LA ORDEN DE TRANSPORTE
                $sqlUpdate = "UPDATE ORDEN_TRANSPORTE SET
                  ESTADO = 'En Transito'
                  WHERE ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE";
                $bd->ExecSQL($sqlUpdate);

                if ($rowOrdenTransporte->ESTADO != "En Transito"):
                    // LOG MOVIMIENTOS
                    $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Orden de Transporte", $rowOrdenTransporte->ID_ORDEN_TRANSPORTE, "Pasa a En Transito");
                endif;

            endif;

        endif;

        //ADICIONALMENTE, ACTUALIZAMOS LAS SOLICITUDES ASIGNADAS A ESE TRANSPORTE
        $sqlSolicitudes    = "SELECT DISTINCT ID_SOLICITUD_TRANSPORTE FROM SOLICITUD_TRANSPORTE WHERE BAJA = 0 AND ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE";
        $resultSolicitudes = $bd->ExecSQL($sqlSolicitudes);
        while ($rowSolicitudes = $bd->SigReg($resultSolicitudes)):
            $this->actualizarEstadoSolicitudTransporte($rowSolicitudes->ID_SOLICITUD_TRANSPORTE);
        endwhile;
    }

    /**
     * @param $idOrdenTransporte ORDEN DE TRANSPORTE
     * ACTUALIZAMOS EL ESTADO DE LAS INTERFACES DE LA ORDEN DE TRANSPORTE
     */
    function actualizarEstadoInterfacesOrdenTransporte($idOrdenTransporte)
    {

        global $bd;
        global $html;
        global $auxiliar;
        global $administrador;


        //BUSCO LA ORDEN DE RECOGIDA
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowOrdenTransporte               = $bd->VerReg("ORDEN_TRANSPORTE", "ID_ORDEN_TRANSPORTE", $idOrdenTransporte, "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);

        //CALCULO EL ESTADO SEGUN EL NUMERO DE PEDIDOS TRANSMITIDOS
        $numPedidos = $bd->NumRegsTabla("PEDIDO_ENTRADA", "ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND BAJA = 0");

        //CALCULOS EL NUMERO DE PEDIDOS TRANSMITIDOS A SAP
        $sqlPedidosTransmitidos    = "SELECT COUNT(DISTINCT PEL.ID_PEDIDO_ENTRADA) AS NUM_PEDIDOS_TRANSMITIDOS
                                       FROM PEDIDO_ENTRADA_LINEA PEL
                                       INNER JOIN PEDIDO_ENTRADA PE ON PE.ID_PEDIDO_ENTRADA = PEL.ID_PEDIDO_ENTRADA
                                       WHERE PEL.ENVIADO_SAP = 1 AND PE.ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND PE.BAJA = 0";
        $resultPedidosTransmitidos = $bd->ExecSQL($sqlPedidosTransmitidos);
        $rowPedidosTransmitidos    = $bd->SigReg($resultPedidosTransmitidos);
        $numPedidosTransmitidos    = $rowPedidosTransmitidos->NUM_PEDIDOS_TRANSMITIDOS;

        //SI TODOS ESTAN TRANSMITIDOS, EL ESTADO ES ZTL TRANSMITIDOS O FINALIZADA
        if ($numPedidos == $numPedidosTransmitidos):

            //CALCULAMOS SI HAY RECOGIDAS QUE NO TENGAN ZTL
            $numRecogidasSinZTL = $this->NumeroRecogidasSinZTL($rowOrdenTransporte->ID_ORDEN_TRANSPORTE);
            if (($numRecogidasSinZTL == 0) && ($rowOrdenTransporte->MODELO_TRANSPORTE == 'Primero')):
                $sqlUpdate = "UPDATE ORDEN_TRANSPORTE SET ESTADO_INTERFACES = 'Finalizada' WHERE ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE";
                $bd->ExecSQL($sqlUpdate);
                $sqlUpdate = "UPDATE ORDEN_CONTRATACION SET ENVIADO_SAP = 1 WHERE ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND BAJA = 0";
                $bd->ExecSQL($sqlUpdate);
            else:
                $sqlUpdate = "UPDATE ORDEN_TRANSPORTE SET ESTADO_INTERFACES = 'ZTL Transmitidos' WHERE ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE";
                $bd->ExecSQL($sqlUpdate);
            endif;

        elseif ($numPedidosTransmitidos > 0): //SI NO ESTAN TODOS TRANSMITIDOS PERO ALGUNO SI

            $sqlUpdate = "UPDATE ORDEN_TRANSPORTE SET ESTADO_INTERFACES = 'ZTL en Transmision' WHERE ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE";
            $bd->ExecSQL($sqlUpdate);

        else://SI NO SE HA TRANSMITIDO NINGUNO, DAMOS DE BAJA LOS PEDIDOS CREADOS

            //NO HACER NADA

        endif;//SI NO HAY NINGUNO TRANSMITIDO EL ESTADO NO SE ALTERA
    }

    /**
     * @param $idOrdenTransporte ID ORDEN TRANSPORTE
     * CALCULAMOS EL ESTADO DE ENTREGA EN OBRA EN FUNCIÓN DE SUS CAMPOS
     */
    function actualizarEstadoCicloEntregaObra($idOrdenTransporte)
    {

        global $bd;
        global $administrador;

        if ($idOrdenTransporte != ''):

            //Cargo la orden transporte
            $row = $this->getOrdenTransporteConstruccion($idOrdenTransporte);


            //ESTADO INICIAL
            $estado            = "En Terminal";
            $estado_encontrado = false;

            //SI HA SALIDO DE PUERTO
            if ($row->FECHA_RECOGIDA_PUERTO != NULL):
                $estado = "Transito Terrestre";

            else: //SI NO ESTA RELLENO, NOS QUEDAMOS EN ESE ESTADO
                $estado_encontrado = true;
            endif;

            //SI TIENE ALMACEN INTERMEDIO, SI NO ESTA RELLENO, OBVIAMOS EL ESTADO
            if ($row->FECHA_LLEGADA_ALMACEN_INTERMEDIO != NULL && $estado_encontrado == false):
                $estado = "Almacen Intermedio";

                //SI HA SALIDO DE ALMACEN INTERMEDIO
                if ($row->FECHA_SALIDA_ALMACEN_INTERMEDIO != NULL):
                    $estado = "Transito Terrestre";
                else: //SI NO ESTA RELLENO, NOS QUEDAMOS EN ESE ESTADO
                    $estado_encontrado = true;
                endif;
            endif;

            //SI HA LLEGADO A OBRA
            if ($row->FECHA_LLEGADA_PROYECTO != NULL && $estado_encontrado == false):
                $estado = "En Obra";
            else: //SI NO ESTA RELLENO, NOS QUEDAMOS EN ESE ESTADO
                $estado_encontrado = true;
            endif;

            //SI HA SALIDO DE OBRA
            if ($row->FECHA_ENTREGA_REAL != NULL && $estado_encontrado == false):
                $estado = "En Devolucion";
            else: //SI NO ESTA RELLENO, NOS QUEDAMOS EN ESE ESTADO
                $estado_encontrado = true;
            endif;

            //SI HA FINALIZADO EL RETORNO DE LA CARGA
            if ($row->FECHA_REAL_RETORNO_TERMINAL_CARGA != NULL && $estado_encontrado == false):
                $estado = "Finalizado";
            else: //SI NO ESTA RELLENO, NOS QUEDAMOS EN ESE ESTADO
                $estado_encontrado = true;
            endif;

            //ACTUALIZAMOS EL ESTADO
            $sqlUpdate = "UPDATE ORDEN_TRANSPORTE SET
                                ESTADO_ENTREGA_OBRA = '$estado'
                                WHERE ID_ORDEN_TRANSPORTE = $idOrdenTransporte";
            $bd->ExecSQL($sqlUpdate);

            //OBTENEMOS LA OT ACTUALIZADA
            $rowActualizada = $this->getOrdenTransporteConstruccion($idOrdenTransporte);

            // LOG MOVIMIENTOS
            $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Orden de Transporte", $idOrdenTransporte, "Unidad de transporte", "ORDEN_TRANSPORTE", $row, $rowActualizada);

        endif;

    }


    /**
     * @param $idSolicitudTransporte SOLICITUDES DE TRANSPORTE
     * Si todas sus recogidas cumplen las condiciones, se finaliza automaticamente
     */
    function actualizarEstadoSolicitudTransporte($idSolicitudTransporte)
    {

        global $bd;
        global $html;
        global $auxiliar;
        global $administrador;


        //BUSCO LA SOLICITUD
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowSolicitud                     = $bd->VerReg("SOLICITUD_TRANSPORTE", "ID_SOLICITUD_TRANSPORTE", $idSolicitudTransporte, "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);

        //BUSCO LA ORDEN DE TRANSPORTE
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowOrdenTransporte               = $bd->VerReg("ORDEN_TRANSPORTE", "ID_ORDEN_TRANSPORTE", $rowSolicitud->ID_ORDEN_TRANSPORTE, "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);


        if ($rowSolicitud->ID_ORDEN_TRANSPORTE != NULL):
            $ordenFinalizada = true;

            //BUSCAMOS QUE TENGA RECOGIDAS
            $numRecogidas = $bd->NumRegsTabla("EXPEDICION", "BAJA=0 AND ID_SOLICITUD_TRANSPORTE=$rowSolicitud->ID_SOLICITUD_TRANSPORTE");
            if ($numRecogidas == 0):
                //SI TIENE GASTOS Y LAS INTERFACES ESTAN TRANSMITIDAS Y TODAS LAS RECOGIDAS SE HAN DADO DE BAJA, DEJAMOS LA ORDEN COMO FINALIZADA
                if (($rowOrdenTransporte->TIENE_GASTOS_TRANSPORTE == 0) ||
                    (($rowOrdenTransporte->TIENE_GASTOS_TRANSPORTE == 1) && ($rowOrdenTransporte->ESTADO_INTERFACES != "Finalizada"))
                ):
                    $ordenFinalizada = false;
                endif;
            endif;

            //BUSCAMOS LAS RECOGIDAS OPERACIONES EN PARQUE Y FUERA DE SISTEMA QUE NO ESTEN RECEPCIONADAS
            $sqlOperacionesNoRecepcionadas    = "SELECT ID_EXPEDICION
                                                FROM EXPEDICION
                                                WHERE ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND ID_SOLICITUD_TRANSPORTE = $rowSolicitud->ID_SOLICITUD_TRANSPORTE AND (TIPO_ORDEN_RECOGIDA = 'Operaciones fuera de Sistema' OR TIPO_ORDEN_RECOGIDA = 'Operaciones en Parque') AND ESTADO <> 'Recepcionada' AND BAJA = 0";
            $resultOperacionesNoRecepcionadas = $bd->ExecSQL($sqlOperacionesNoRecepcionadas);

            $numOperacionesNoRecepcionadas = $bd->NumRegs($resultOperacionesNoRecepcionadas);
            //SI QUEDA ALGUNA POR RECEPCIONAR , NO FINALIZAR
            if ($numOperacionesNoRecepcionadas > 0):
                $ordenFinalizada = false;
            endif;

            //SI ES SIN PEDIDO CONOCIDO, COMPROBAMOS QUE ESTA ASOCIADO A SU RECEPCION
            $numRecogidasSinPedidoConocido = $bd->NumRegsTabla("EXPEDICION", "BAJA=0 AND ID_ORDEN_TRANSPORTE=$rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND SUBTIPO_ORDEN_RECOGIDA='Sin Pedido Conocido' AND ID_SOLICITUD_TRANSPORTE = $rowSolicitud->ID_SOLICITUD_TRANSPORTE");
            if ($numRecogidasSinPedidoConocido > 0):
                $numRecepcionesOT = $bd->NumRegsTabla("MOVIMIENTO_RECEPCION", "BAJA=0 AND ID_ORDEN_TRANSPORTE=$rowOrdenTransporte->ID_ORDEN_TRANSPORTE");
                if ($numRecepcionesOT == 0):
                    $ordenFinalizada = false;
                endif;
            endif;

            //SI TIENE RECOGIDAS CON PEDIDO CONOCIDO, COMPROBAMOS SI TIENE LINEAS
            $sqlRecogidasProveedorConPedido    = "SELECT DISTINCT E.ID_EXPEDICION
                                                    FROM EXPEDICION E
                                                    INNER JOIN ORDEN_TRANSPORTE OT ON OT.ID_ORDEN_TRANSPORTE = E.ID_ORDEN_TRANSPORTE
                                                    WHERE OT.ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND E.BAJA = 0 AND E.SUBTIPO_ORDEN_RECOGIDA = 'Con Pedido Conocido' AND E.ID_SOLICITUD_TRANSPORTE = $rowSolicitud->ID_SOLICITUD_TRANSPORTE";
            $resultRecogidasProveedorConPedido = $bd->ExecSQL($sqlRecogidasProveedorConPedido);
            while ($rowRecogidasConPedido = $bd->SigReg($resultRecogidasProveedorConPedido)):
                //BUSCAMOS SI TIENE LINEAS ANULADAS ( NO CUENTAN LAS ANULADAS POR NO HABER SIDO SERVIDAS)
                $numLineasConocido = $bd->NumRegsTabla("EXPEDICION_PEDIDO_CONOCIDO", "(BAJA=0 OR CANTIDAD_NO_SERVIDA = CANTIDAD) AND ID_EXPEDICION=$rowRecogidasConPedido->ID_EXPEDICION");
                if ($numLineasConocido == 0):
                    $ordenFinalizada = false;
                endif;
            endwhile;

            //BUSCAMOS LOS CF DE LAS RECOGIDAS EN PROVEEDOR SIN RECEPCION
            $arrCentroFisicoRecogidasProveedor = $this->getCentrosFisicosDestinoRecogidasProveedorSinRecepcion($rowOrdenTransporte->ID_ORDEN_TRANSPORTE, $rowSolicitud->ID_SOLICITUD_TRANSPORTE);
            //SI HAY ALGUN CF SIN RECEPCION, NO FINALIZAR
            if (count((array)$arrCentroFisicoRecogidasProveedor) > 0):
                $ordenFinalizada = false;
            endif;

            //BUSCAMOS LOS DESTINOS DE LOS TRASLADOS
            $arrCentroFisicoTraslados = $this->getCentrosFisicosDestinoTraslado($rowOrdenTransporte->ID_ORDEN_TRANSPORTE, $rowSolicitud->ID_SOLICITUD_TRANSPORTE);

            //BUSCAMOS SI TIENE RECOGIDAS QUE TENGAN CF DE DESTINO (NO INCLUIMOS EL ESTADO EXPEDIDA QUE ES DE PEDIDOS DE VENTA)
            $numRecogidasConCFDestino = $bd->NumRegsTabla("EXPEDICION", "BAJA=0 AND ID_SOLICITUD_TRANSPORTE = $rowSolicitud->ID_SOLICITUD_TRANSPORTE AND ID_ORDEN_TRANSPORTE=$rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND ESTADO<>'Expedida' AND (SUBTIPO_ORDEN_RECOGIDA='Traslados y Ventas' OR SUBTIPO_ORDEN_RECOGIDA='Material Estropeado entre Almacenes Intracompany' OR SUBTIPO_ORDEN_RECOGIDA='Material Estropeado entre Almacenes Intercompany' OR SUBTIPO_ORDEN_RECOGIDA='Retorno Material Estropeado desde Proveedor')");

            //SI NO HA ENCONTRADO LINEAS Y ES UN TRASLADO, LA OT NO ESTARA FINALIZADA
            if ((count((array)$arrCentroFisicoTraslados) == 0) && ($numRecogidasConCFDestino > 0)):
                $ordenFinalizada = false;
            endif;

            //RECORREMOS LOS CF DESTINO PARA COMPROBAR SI TIENEN RECEPCION ASOCIADA
            foreach ($arrCentroFisicoTraslados as $idCentroFisicoDestino):

                $sqlMovimientosRecepcion    = "SELECT * FROM MOVIMIENTO_RECEPCION MR WHERE MR.ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND MR.ID_CENTRO_FISICO = $idCentroFisicoDestino AND BAJA=0";
                $resultMovimientosRecepcion = $bd->ExecSQL($sqlMovimientosRecepcion);

                //SI NO TIENE RECEPCION, NO FINALIZAR
                if ($bd->NumRegs($resultMovimientosRecepcion) == 0):
                    $ordenFinalizada = false;
                endif;
            endforeach;
            //FIN RECORREMOS LOS CF DESTINO PARA COMPRABAR SI TIENEN RECEPCION ASOCIADA


            //COMPROBAMOS QUE LAS RECOGIDAS QUE VAN A PROVEEDOR ESTAN EXPEDIDAS
            $sqlRecogidasAProveedor    = "SELECT ID_EXPEDICION
                                                FROM EXPEDICION
                                                WHERE ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND ID_SOLICITUD_TRANSPORTE = $rowSolicitud->ID_SOLICITUD_TRANSPORTE AND (SUBTIPO_ORDEN_RECOGIDA = 'Componentes a Proveedor' OR SUBTIPO_ORDEN_RECOGIDA = 'Devoluciones a Proveedor' OR SUBTIPO_ORDEN_RECOGIDA = 'Rechazos Anulaciones Proveedor') AND ESTADO <> 'Expedida' AND BAJA = 0";
            $resultRecogidasAProveedor = $bd->ExecSQL($sqlRecogidasAProveedor);

            $numRecogidasAProveedor = $bd->NumRegs($resultRecogidasAProveedor);
            //SI QUEDA ALGUNA POR RECEPCIONAR , NO FINALIZAR
            if ($numRecogidasAProveedor > 0):
                $ordenFinalizada = false;
            endif;


            //COMPROBAMOS QUE LAS RECOGIDAS DE MATERIAL ESTROPEADO ESTAN RECEPCIONADAS
            $sqlRecogidasEstropeadoAProveedor    = "SELECT ID_EXPEDICION
                                                        FROM EXPEDICION
                                                        WHERE ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND ID_SOLICITUD_TRANSPORTE = $rowSolicitud->ID_SOLICITUD_TRANSPORTE AND SUBTIPO_ORDEN_RECOGIDA IN ('Material Estropeado a Proveedor','Material Estropeado Entre Proveedores') AND ESTADO <> 'Recepcionada' AND BAJA = 0";
            $resultRecogidasEstropeadoAProveedor = $bd->ExecSQL($sqlRecogidasEstropeadoAProveedor);

            $numRecogidasEstropeadoAProveedor = $bd->NumRegs($resultRecogidasEstropeadoAProveedor);
            //SI QUEDA ALGUNA POR RECEPCIONAR , NO FINALIZAR
            if ($numRecogidasEstropeadoAProveedor > 0):
                $ordenFinalizada = false;
            endif;


            if ($ordenFinalizada):
                //SI NO HEMOS SALIDO DE LA FUNCION,
                $sqlUpdate = "UPDATE SOLICITUD_TRANSPORTE SET
                                  ESTADO = 'Finalizada'
                                , FECHA_FINALIZACION = '" . date("Y-m-d H:i:s") . "'
                                WHERE ID_SOLICITUD_TRANSPORTE = $rowSolicitud->ID_SOLICITUD_TRANSPORTE ";
                $bd->ExecSQL($sqlUpdate);

                // LOG MOVIMIENTOS
                if ($rowSolicitud->ESTADO != "Finalizada"):
                    $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Solicitud Transporte", $rowSolicitud->ID_SOLICITUD_TRANSPORTE, "Finalizacion Automatica");
                endif;
            else:

                //COMPRUEBO QUE AL MENOS UNA DE SUS RECOGIDAS ESTAN Expedidas(o En tránsito o Recepcionada)
                $sqlRecogidasExpedidas    = "SELECT * FROM EXPEDICION
                                        WHERE ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND ID_SOLICITUD_TRANSPORTE = $rowSolicitud->ID_SOLICITUD_TRANSPORTE  AND (ESTADO = 'Expedida' OR ESTADO = 'En Tránsito' OR ESTADO = 'Recepcionada') AND BAJA = 0";
                $resultRecogidasExpedidas = $bd->ExecSQL($sqlRecogidasExpedidas);

                //SI NO QUEDA NINGUNA RECOGIDA EXPEDIDA, PASAMOS EL TRANSPORTE A ESTADO CREADA
                if ($bd->NumRegs($resultRecogidasExpedidas) == 0):
                    //ESTABLEZCO EL ESTADO DE LA ORDEN DE TRANSPORTE
                    $sqlUpdate = "UPDATE SOLICITUD_TRANSPORTE SET
                                      ESTADO = 'Asignada a OT'
                                      , FECHA_FINALIZACION = NULL
                                      WHERE ID_SOLICITUD_TRANSPORTE = $rowSolicitud->ID_SOLICITUD_TRANSPORTE";
                    $bd->ExecSQL($sqlUpdate);
                    if ($rowSolicitud->ESTADO != "Asignada a OT"):
                        // LOG MOVIMIENTOS
                        $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Solicitud Transporte", $rowSolicitud->ID_SOLICITUD_TRANSPORTE, "Pasa a Estado Asignada a OT");
                    endif;

                elseif ($bd->NumRegs($resultRecogidasExpedidas) > 0): // SI HAY ALGUNA RECOGIDA EXPEDIDA, EL TRANSPORTE LO PONEMOS 'En Transito'
                    //ESTABLEZCO EL ESTADO DE LA ORDEN DE TRANSPORTE
                    $sqlUpdate = "UPDATE SOLICITUD_TRANSPORTE SET
                                      ESTADO = 'En Transito'
                                      , FECHA_FINALIZACION = NULL
                                      WHERE ID_SOLICITUD_TRANSPORTE = $rowSolicitud->ID_SOLICITUD_TRANSPORTE";
                    $bd->ExecSQL($sqlUpdate);

                    if ($rowSolicitud->ESTADO != "En Transito"):
                        // LOG MOVIMIENTOS
                        $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Solicitud Transporte", $rowSolicitud->ID_SOLICITUD_TRANSPORTE, "Pasa a En Transito");
                    endif;
                endif;

            endif;
        endif;//TIENE ASOCIADA TRANSPORTE


    }

    /**
     * @param $idSolicitudTransporteProveedor Aviso al usuario que corresponda
     */
    function enviarReclamacionSolicitudProveedor($idSolicitudTransporteProveedor)
    {

        global $html;
        global $bd;
        global $administrador;
        global $auxiliar;
        global $aviso;

        //BUSCAMOS LA SOLICITUD
        $rowSolicitudProveedor = $bd->VerReg("SOLICITUD_TRANSPORTE_PROVEEDOR", "ID_SOLICITUD_TRANSPORTE_PROVEEDOR", $idSolicitudTransporteProveedor);

        //BUSCAMOS LA ACCION
        $rowSolicitud = $bd->VerReg("SOLICITUD_TRANSPORTE", "ID_SOLICITUD_TRANSPORTE", $rowSolicitudProveedor->ID_SOLICITUD_TRANSPORTE);

        //BUSCAMOS EL PROVEEDOR
        $rowProveedor = $bd->VerReg("PROVEEDOR", "ID_PROVEEDOR", $rowSolicitudProveedor->ID_PROVEEDOR);


        // LOG MOVIMIENTOS
        $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Solicitud Transporte", $rowSolicitud->ID_SOLICITUD_TRANSPORTE, "Reclamacion Automatica Solicitud Proveedor $rowSolicitudProveedor->ID_SOLICITUD_TRANSPORTE_PROVEEDOR", "SOLICITUD_TRANSPORTE");


        //VARIABLES PARA GUARDAR LOS USUARIOS
        $arrAdminEsp = array();
        $arrAdminEng = array();
        $arrDestEsp  = array();
        $arrDestEng  = array();


        //SE ENVIA AL USUARIO QUE GENERO LA SOLICITUD
        $rowUsuarioCreacion = $bd->VerReg("ADMINISTRADOR", "ID_ADMINISTRADOR", $rowSolicitud->ID_ADMINISTRADOR_CREACION, "No");

        //GUARDAMOS EL USUARIO
        ($rowUsuarioCreacion->IDIOMA_NOTIFICACIONES == "ESP" ? $arrAdminEsp[] = $rowUsuarioCreacion->ID_ADMINISTRADOR : $arrAdminEng[] = $rowUsuarioCreacion->ID_ADMINISTRADOR);

        //BUSCAMOS EL GESTOR DE LA SOCIEDAD
        if ($rowSolicitud->ID_SOCIEDAD != NULL):

            //BUSCAMOS EL GESTOR
            if ($rowSolicitud->NACIONAL == "Nacional"):
                $tipoGestor = "Gestor de transporte nacional envíos proveedor";
            else:
                $tipoGestor = "Gestor de transporte internacional envíos proveedor";
            endif;

            //BUSCAMOS EL GESTOR DE LA SOCIEDAD
            $sqlGestor    = "SELECT A.ID_ADMINISTRADOR, A.NOMBRE, A.IDIOMA_NOTIFICACIONES
                                FROM SOCIEDAD_RESPONSABLE SR
                                INNER JOIN ADMINISTRADOR A ON SR.ID_ADMINISTRADOR_RESPONSABLE = A.ID_ADMINISTRADOR
                                WHERE SR.BAJA = 0 AND SR.ID_SOCIEDAD = $rowSolicitud->ID_SOCIEDAD AND SR.TIPO = '$tipoGestor'";
            $resultGestor = $bd->ExecSQL($sqlGestor);
            //COMPROBAMOS QUE ESTA DEFINIDIDO EL GESTOR
            if ($bd->NumRegs($resultGestor) > 0):
                //RECORREMOS GESTORES
                while ($rowGestor = $bd->SigReg($resultGestor)):
                    //GUARDAMOS EL USUARIO
                    ($rowGestor->IDIOMA_NOTIFICACIONES == "ESP" ? $arrAdminEsp[] = $rowGestor->ID_ADMINISTRADOR : $arrAdminEng[] = $rowGestor->ID_ADMINISTRADOR);
                endwhile;
            endif;
        endif;


        //UNIFICO DESTINATARIOS
        $arrAdminEsp = array_unique((array)$arrAdminEsp);
        $arrAdminEng = array_unique((array)$arrAdminEng);

        //GENERAMOS CUERPO Y ASUNTO
        $arrIdiomas = array();
        if (count((array)$arrAdminEsp) > 0):
            $arrIdiomas[] = "ESP";
        endif;
        if (count((array)$arrAdminEng) > 0):
            $arrIdiomas[] = "ENG";
        endif;

        //CREAMOS EL AVISO DEPENDIENDO DEL IDIOMA
        $AsuntoESP = "";
        $AsuntoENG = "";
        $CuerpoESP = "";
        $CuerpoENG = "";
        foreach ($arrIdiomas as $idIdiomaImpresion):

            ${"Asunto" . $idIdiomaImpresion} = $auxiliar->traduce("Solicitud Transporte", $idIdiomaImpresion) . " $rowSolicitud->ID_SOLICITUD_TRANSPORTE ";

            ${"Cuerpo" . $idIdiomaImpresion} = $auxiliar->traduce("Buenos días", $idIdiomaImpresion) . ' <br><br><br>' . $auxiliar->traduce("Una Solicitud de Proveedor en la que usted esta involucrado no ha sido aun tratada por el Proveedor", $idIdiomaImpresion) . '. ';
            ${"Cuerpo" . $idIdiomaImpresion} .= '<br><br> -' . $auxiliar->traduce("Solicitud Transporte", $idIdiomaImpresion) . ': <strong> <a href="' . SOLICITUDES_TRANSPORTE_URL_ENLACE . 'ficha.php?idSolicitudTransporte=' . $rowSolicitud->ID_SOLICITUD_TRANSPORTE . '">' . $rowSolicitud->ID_SOLICITUD_TRANSPORTE . '</a></strong>';
            ${"Cuerpo" . $idIdiomaImpresion} .= '<br> -' . ucfirst((string)$auxiliar->traduce("Proveedor", $idIdiomaImpresion)) . ': <strong>' . $rowProveedor->REFERENCIA . ' - ' . $rowProveedor->NOMBRE . '</strong>';

            ${"Cuerpo" . $idIdiomaImpresion} .= '.<br><br><br>' . $auxiliar->traduce("Muchas gracias. Saludos.", $idIdiomaImpresion) . '<br>';
        endforeach;

        //LE INDICAMOS A LA FUNCION QUE SON ADMINISTRADORES
        if (count((array)$arrAdminEsp) > 0):
            $arrDestEsp['ADMINISTRADOR'] = $arrAdminEsp;
        endif;
        if (count((array)$arrAdminEng) > 0):
            $arrDestEng['ADMINISTRADOR'] = $arrAdminEng;
        endif;

        //GUARDAMOS EL AVISO EN ESPAÑOL (NO SE GUARDARA NADA SI NO HAY DESTINATARIOS CON NOTIFICACIONES EN ESPAÑOL)
        $idAvisoEsp = $aviso->GuardarAviso($rowSolicitud->ID_SOLICITUD_TRANSPORTE, "SOLICITUD_TRANSPORTE", $AsuntoESP, $CuerpoESP, $arrDestEsp);

        //GUARDAMOS EL AVISO EN INGLES (NO SE GUARDARA NADA SI NO HAY DESTINATARIOS CON NOTIFICACIONES EN INGLES)
        $idAvisoEng = $aviso->GuardarAviso($rowSolicitud->ID_SOLICITUD_TRANSPORTE, "SOLICITUD_TRANSPORTE", $AsuntoENG, $CuerpoENG, $arrDestEng);

        //ENVIAMOS LOS AVISOS
        if ($idAvisoEsp != ""):
            $aviso->enviarAviso($idAvisoEsp);
        endif;
        if ($idAvisoEng != ""):
            $aviso->enviarAviso($idAvisoEng);
        endif;

    }

    /**
     * @param $rowOrdenContratacionPrevia ROW DE LA ORDEN DE CONTRATACION ANTES DE SER ACTUALIZADA
     * @param DEVUELVE UN TEXTO CON LOS PARAMETROS QUE HAN VARIADO EN EL IDIOMA $idIdioma
     */
    function obtenerTextoCambioParametros($rowOrdenContratacionPrevia, $idIdioma = "ESP", $saltoLinea = "")
    {

        global $bd;
        global $html;
        global $auxiliar;
        global $administrador;

        //STRING CON LOS CAMBIOS
        $strCambios = "";

        //COMPROBAMOS QUE VENGAN LOS DATOS
        if ($rowOrdenContratacionPrevia == false):
            return $strCambios;
        endif;

        //POR DEFECTO USAMOS \N COMO SALTO DE LINEA
        if ($saltoLinea == ""):
            $saltoLinea = "\n";
        endif;


        $strCambios = $auxiliar->traduce("Parametros Modificados", $idIdioma) . ":" . $saltoLinea;

        //BUSCAMOS LA ORDEN DE CONTRATACION ACTUALIZADA
        $rowOrdenContratacionActualizada = $bd->VerReg("ORDEN_CONTRATACION", "ID_ORDEN_CONTRATACION", $rowOrdenContratacionPrevia->ID_ORDEN_CONTRATACION);

        if ($rowOrdenContratacionActualizada->NUMERO_KM != $rowOrdenContratacionPrevia->NUMERO_KM):
            $strCambios .= $auxiliar->traduce("Tramo", $idIdioma) . ": $rowOrdenContratacionPrevia->NUMERO_KM " . $auxiliar->traduce("A_TO", $idIdioma) . " $rowOrdenContratacionActualizada->NUMERO_KM" . $saltoLinea;
        endif;
        if ($rowOrdenContratacionActualizada->NUMERO_KM_PRECIO_SUPERIOR_RADIO_KM != $rowOrdenContratacionPrevia->NUMERO_KM_PRECIO_SUPERIOR_RADIO_KM):
            $strCambios .= $auxiliar->traduce("Radio de desplazamiento en KM", $idIdioma) . ": $rowOrdenContratacionPrevia->NUMERO_KM_PRECIO_SUPERIOR_RADIO_KM " . $auxiliar->traduce("A_TO", $idIdioma) . " $rowOrdenContratacionActualizada->NUMERO_KM_PRECIO_SUPERIOR_RADIO_KM" . $saltoLinea;
        endif;
        if ($rowOrdenContratacionActualizada->NUMERO_KM_IRUÑA != $rowOrdenContratacionPrevia->NUMERO_KM_IRUÑA):
            $strCambios .= $auxiliar->traduce("Km Ida y Vuelta desde Sede Iruña Express", $idIdioma) . ": $rowOrdenContratacionPrevia->NUMERO_KM_IRUÑA " . $auxiliar->traduce("A_TO", $idIdioma) . " $rowOrdenContratacionActualizada->NUMERO_KM_IRUÑA" . $saltoLinea;
        endif;
        if ($rowOrdenContratacionActualizada->NUMERO_KM_ADER != $rowOrdenContratacionPrevia->NUMERO_KM_ADER):
            $strCambios .= $auxiliar->traduce("Km Ida y Vuelta desde Sede Ader", $idIdioma) . ": $rowOrdenContratacionPrevia->NUMERO_KM_ADER " . $auxiliar->traduce("A_TO", $idIdioma) . " $rowOrdenContratacionActualizada->NUMERO_KM_ADER" . $saltoLinea;
        endif;
        if ($rowOrdenContratacionActualizada->NUMERO_KM_MRW != $rowOrdenContratacionPrevia->NUMERO_KM_MRW):
            $strCambios .= $auxiliar->traduce("Km distancia desde Sede MRW", $idIdioma) . ": $rowOrdenContratacionPrevia->NUMERO_KM_MRW " . $auxiliar->traduce("A_TO", $idIdioma) . " $rowOrdenContratacionActualizada->NUMERO_KM_MRW" . $saltoLinea;
        endif;

        if ($rowOrdenContratacionActualizada->PARADA_ADICIONAL != $rowOrdenContratacionPrevia->PARADA_ADICIONAL):
            $strCambios .= $auxiliar->traduce("Parada Adicional", $idIdioma) . ": " . ($rowOrdenContratacionPrevia->PARADA_ADICIONAL != 0 ? $auxiliar->traduce("Si", $administrador->ID_IDIOMA) : $auxiliar->traduce("No", $administrador->ID_IDIOMA)) . " " . $auxiliar->traduce("A_TO", $idIdioma) . " " . ($rowOrdenContratacionActualizada->PARADA_ADICIONAL != 0 ? $auxiliar->traduce("Si", $administrador->ID_IDIOMA) : $auxiliar->traduce("No", $administrador->ID_IDIOMA)) . " " . $saltoLinea;
        endif;

        if ($rowOrdenContratacionActualizada->NUMERO_KM_PARADA_ADICIONAL != $rowOrdenContratacionPrevia->NUMERO_KM_PARADA_ADICIONAL):
            $strCambios .= $auxiliar->traduce("Nº Km Parada Adicional", $idIdioma) . ": $rowOrdenContratacionPrevia->NUMERO_KM_PARADA_ADICIONAL " . $auxiliar->traduce("A_TO", $idIdioma) . " $rowOrdenContratacionActualizada->NUMERO_KM_PARADA_ADICIONAL" . $saltoLinea;
        endif;

        if ($rowOrdenContratacionActualizada->NUMERO_PARADAS_ADICIONALES != $rowOrdenContratacionPrevia->NUMERO_PARADAS_ADICIONALES):
            $strCambios .= $auxiliar->traduce("Nº Paradas Adicionales", $idIdioma) . ": $rowOrdenContratacionPrevia->NUMERO_PARADAS_ADICIONALES " . $auxiliar->traduce("A_TO", $idIdioma) . " $rowOrdenContratacionActualizada->NUMERO_PARADAS_ADICIONALES" . $saltoLinea;
        endif;

        if ($rowOrdenContratacionActualizada->NUMERO_HORAS_PARADA_ADICIONAL != $rowOrdenContratacionPrevia->NUMERO_HORAS_PARADA_ADICIONAL):
            $strCambios .= $auxiliar->traduce("Nº horas parada adicional", $idIdioma) . ": $rowOrdenContratacionPrevia->NUMERO_HORAS_PARADA_ADICIONAL " . $auxiliar->traduce("A_TO", $idIdioma) . " $rowOrdenContratacionActualizada->NUMERO_HORAS_PARADA_ADICIONAL" . $saltoLinea;
        endif;

        if ($rowOrdenContratacionActualizada->TARA_VEHICULO != $rowOrdenContratacionPrevia->TARA_VEHICULO):
            $strCambios .= $auxiliar->traduce("Tara", $idIdioma) . "(Kg): $rowOrdenContratacionPrevia->TARA_VEHICULO " . $auxiliar->traduce("A_TO", $idIdioma) . " $rowOrdenContratacionActualizada->TARA_VEHICULO" . $saltoLinea;
        endif;
        if ($rowOrdenContratacionActualizada->NUMERO_DIAS_ADICIONALES != $rowOrdenContratacionPrevia->NUMERO_DIAS_ADICIONALES):
            $strCambios .= $auxiliar->traduce("Dias Adicionales", $idIdioma) . ": $rowOrdenContratacionPrevia->NUMERO_DIAS_ADICIONALES " . $auxiliar->traduce("A_TO", $idIdioma) . " $rowOrdenContratacionActualizada->NUMERO_DIAS_ADICIONALES" . $saltoLinea;
        endif;
        if ($rowOrdenContratacionActualizada->NUMERO_DIAS_ADICIONALES_SOLO_PLATAFORMA != $rowOrdenContratacionPrevia->NUMERO_DIAS_ADICIONALES_SOLO_PLATAFORMA):
            $strCambios .= $auxiliar->traduce("Dias Adicionales Solo Plataforma", $idIdioma) . ": $rowOrdenContratacionPrevia->NUMERO_DIAS_ADICIONALES_SOLO_PLATAFORMA " . $auxiliar->traduce("A_TO", $idIdioma) . " $rowOrdenContratacionActualizada->NUMERO_DIAS_ADICIONALES_SOLO_PLATAFORMA" . $saltoLinea;
        endif;
        if ($rowOrdenContratacionActualizada->NUMERO_AYUDANTES != $rowOrdenContratacionPrevia->NUMERO_AYUDANTES):
            $strCambios .= $auxiliar->traduce("Numero Ayudantes", $idIdioma) . ": $rowOrdenContratacionPrevia->NUMERO_AYUDANTES " . $auxiliar->traduce("A_TO", $idIdioma) . " $rowOrdenContratacionActualizada->NUMERO_AYUDANTES" . $saltoLinea;
        endif;
        if ($rowOrdenContratacionActualizada->NUMERO_AYUDANTES_DIAS != $rowOrdenContratacionPrevia->NUMERO_AYUDANTES_DIAS):
            $strCambios .= $auxiliar->traduce("Numero Dias Ayudantes", $idIdioma) . ": $rowOrdenContratacionPrevia->NUMERO_AYUDANTES_DIAS " . $auxiliar->traduce("A_TO", $idIdioma) . " $rowOrdenContratacionActualizada->NUMERO_AYUDANTES_DIAS" . $saltoLinea;
        endif;

        if ($rowOrdenContratacionActualizada->NUMERO_HORAS_EN_DESTINO != $rowOrdenContratacionPrevia->NUMERO_HORAS_EN_DESTINO):
            $strCambios .= $auxiliar->traduce("Horas de Espera en Destino a partir de la 2º hora", $idIdioma) . ": $rowOrdenContratacionPrevia->NUMERO_HORAS_EN_DESTINO " . $auxiliar->traduce("A_TO", $idIdioma) . " $rowOrdenContratacionActualizada->NUMERO_HORAS_EN_DESTINO" . $saltoLinea;
        endif;

        if ($rowOrdenContratacionActualizada->NUMERO_HORAS_GRUA != $rowOrdenContratacionPrevia->NUMERO_HORAS_GRUA):
            $strCambios .= $auxiliar->traduce("Horas de uso", $idIdioma) . ": $rowOrdenContratacionPrevia->NUMERO_HORAS_GRUA " . $auxiliar->traduce("A_TO", $idIdioma) . " $rowOrdenContratacionActualizada->NUMERO_HORAS_GRUA" . $saltoLinea;
        endif;

        if ($rowOrdenContratacionActualizada->NUMERO_HORAS_PRECIO_ESPECIAL != $rowOrdenContratacionPrevia->NUMERO_HORAS_PRECIO_ESPECIAL):
            $strCambios .= $auxiliar->traduce("de las cuales, se realizaran en horario especial", $idIdioma) . ": $rowOrdenContratacionPrevia->NUMERO_HORAS_PRECIO_ESPECIAL " . $auxiliar->traduce("A_TO", $idIdioma) . " $rowOrdenContratacionActualizada->NUMERO_HORAS_PRECIO_ESPECIAL" . $saltoLinea;
        endif;

        if ($rowOrdenContratacionActualizada->CAMINO_RURAL != $rowOrdenContratacionPrevia->CAMINO_RURAL):
            $strCambios .= $auxiliar->traduce("Camino Rural", $idIdioma) . ": " . ($rowOrdenContratacionPrevia->CAMINO_RURAL != 0 ? $auxiliar->traduce("Si", $administrador->ID_IDIOMA) : $auxiliar->traduce("No", $administrador->ID_IDIOMA)) . " " . $auxiliar->traduce("A_TO", $idIdioma) . " " . ($rowOrdenContratacionActualizada->CAMINO_RURAL != 0 ? $auxiliar->traduce("Si", $administrador->ID_IDIOMA) : $auxiliar->traduce("No", $administrador->ID_IDIOMA)) . " " . $saltoLinea;
        endif;

        if ($rowOrdenContratacionActualizada->TONELAJE_GRUA != $rowOrdenContratacionPrevia->TONELAJE_GRUA):
            $strCambios .= $auxiliar->traduce("Tonelaje Grua", $idIdioma) . ": $rowOrdenContratacionPrevia->TONELAJE_GRUA " . $auxiliar->traduce("A_TO", $idIdioma) . " $rowOrdenContratacionActualizada->TONELAJE_GRUA" . $saltoLinea;
        endif;

        if ($rowOrdenContratacionActualizada->HORA_ENTREGA != $rowOrdenContratacionPrevia->HORA_ENTREGA):
            $strCambios .= $auxiliar->traduce("Hora Limite Entrega", $idIdioma) . ": $rowOrdenContratacionPrevia->HORA_ENTREGA " . $auxiliar->traduce("A_TO", $idIdioma) . " $rowOrdenContratacionActualizada->HORA_ENTREGA" . $saltoLinea;
        endif;

        if ($rowOrdenContratacionActualizada->SOLICITUD_ASM_POSTERIOR_10 != $rowOrdenContratacionPrevia->SOLICITUD_ASM_POSTERIOR_10):
            $strCambios .= $auxiliar->traduce("¿Contratado mediante delegación ASM-Imarcoain?", $idIdioma) . ": " . ($rowOrdenContratacionPrevia->SOLICITUD_ASM_POSTERIOR_10 != 0 ? $auxiliar->traduce("Si", $administrador->ID_IDIOMA) : $auxiliar->traduce("No", $administrador->ID_IDIOMA)) . " " . $auxiliar->traduce("A_TO", $idIdioma) . " " . ($rowOrdenContratacionActualizada->SOLICITUD_ASM_POSTERIOR_10 != 0 ? $auxiliar->traduce("Si", $administrador->ID_IDIOMA) : $auxiliar->traduce("No", $administrador->ID_IDIOMA)) . " " . $saltoLinea;
        endif;

        if ($rowOrdenContratacionActualizada->NUMERO_KM_ASM_ORIGEN != $rowOrdenContratacionPrevia->NUMERO_KM_ASM_ORIGEN):
            $strCambios .= $auxiliar->traduce("Km ida y vuelta sede ASM Origen-Punto Recogida", $idIdioma) . ": $rowOrdenContratacionPrevia->NUMERO_KM_ASM_ORIGEN " . $auxiliar->traduce("A_TO", $idIdioma) . " $rowOrdenContratacionActualizada->NUMERO_KM_ASM_ORIGEN" . $saltoLinea;
        endif;

        if ($rowOrdenContratacionActualizada->NUMERO_KM_ASM_DESTINO != $rowOrdenContratacionPrevia->NUMERO_KM_ASM_DESTINO):
            $strCambios .= $auxiliar->traduce("Km ida y vuelta sede ASM Destino-Punto Entrega", $idIdioma) . ": $rowOrdenContratacionPrevia->NUMERO_KM_ASM_DESTINO " . $auxiliar->traduce("A_TO", $idIdioma) . " $rowOrdenContratacionActualizada->NUMERO_KM_ASM_DESTINO" . $saltoLinea;
        endif;

        if ($rowOrdenContratacionActualizada->ZONA_DESTINO != $rowOrdenContratacionPrevia->ZONA_DESTINO):
            $strCambios .= $auxiliar->traduce("Zona Destino", $idIdioma) . ": $rowOrdenContratacionPrevia->ZONA_DESTINO " . $auxiliar->traduce("A_TO", $idIdioma) . " $rowOrdenContratacionActualizada->ZONA_DESTINO" . $saltoLinea;
        endif;

        if ($rowOrdenContratacionActualizada->VEHICULO_CARROZADO != $rowOrdenContratacionPrevia->VEHICULO_CARROZADO):
            $strCambios .= $auxiliar->traduce("Vehiculo Carrozado", $idIdioma) . ": " . ($rowOrdenContratacionPrevia->VEHICULO_CARROZADO != 0 ? $auxiliar->traduce("Si", $administrador->ID_IDIOMA) : $auxiliar->traduce("No", $administrador->ID_IDIOMA)) . " " . $auxiliar->traduce("A_TO", $idIdioma) . " " . ($rowOrdenContratacionActualizada->VEHICULO_CARROZADO != 0 ? $auxiliar->traduce("Si", $administrador->ID_IDIOMA) : $auxiliar->traduce("No", $administrador->ID_IDIOMA)) . " " . $saltoLinea;
        endif;
        if ($rowOrdenContratacionActualizada->SERVICIO_COMARCAL != $rowOrdenContratacionPrevia->SERVICIO_COMARCAL):
            $strCambios .= $auxiliar->traduce("Comarcal", $idIdioma) . ": " . ($rowOrdenContratacionPrevia->SERVICIO_COMARCAL != 0 ? $auxiliar->traduce("Si", $administrador->ID_IDIOMA) : $auxiliar->traduce("No", $administrador->ID_IDIOMA)) . " " . $auxiliar->traduce("A_TO", $idIdioma) . " " . ($rowOrdenContratacionActualizada->SERVICIO_COMARCAL != 0 ? $auxiliar->traduce("Si", $administrador->ID_IDIOMA) : $auxiliar->traduce("No", $administrador->ID_IDIOMA)) . " " . $saltoLinea;
        endif;

        if ($rowOrdenContratacionActualizada->SERVICIO_NOCTURNO_FESTIVO != $rowOrdenContratacionPrevia->SERVICIO_NOCTURNO_FESTIVO):
            $strCambios .= $auxiliar->traduce("Servicio Nocturno o Festivo", $idIdioma) . ": " . ($rowOrdenContratacionPrevia->SERVICIO_NOCTURNO_FESTIVO != 0 ? $auxiliar->traduce("Si", $administrador->ID_IDIOMA) : $auxiliar->traduce("No", $administrador->ID_IDIOMA)) . " " . $auxiliar->traduce("A_TO", $idIdioma) . " " . ($rowOrdenContratacionActualizada->SERVICIO_NOCTURNO_FESTIVO != 0 ? $auxiliar->traduce("Si", $administrador->ID_IDIOMA) : $auxiliar->traduce("No", $administrador->ID_IDIOMA)) . " " . $saltoLinea;
        endif;

        if ($rowOrdenContratacionActualizada->SERVICIO_SABADO_MAÑANA != $rowOrdenContratacionPrevia->SERVICIO_SABADO_MAÑANA):
            $strCambios .= $auxiliar->traduce("Servicio Sabado Mañana", $idIdioma) . ": " . ($rowOrdenContratacionPrevia->SERVICIO_SABADO_MAÑANA != 0 ? $auxiliar->traduce("Si", $administrador->ID_IDIOMA) : $auxiliar->traduce("No", $administrador->ID_IDIOMA)) . " " . $auxiliar->traduce("A_TO", $idIdioma) . " " . ($rowOrdenContratacionActualizada->SERVICIO_SABADO_MAÑANA != 0 ? $auxiliar->traduce("Si", $administrador->ID_IDIOMA) : $auxiliar->traduce("No", $administrador->ID_IDIOMA)) . " " . $saltoLinea;
        endif;

        if ($rowOrdenContratacionActualizada->TIENE_RETORNO_CARGADO != $rowOrdenContratacionPrevia->TIENE_RETORNO_CARGADO):
            $strCambios .= $auxiliar->traduce("Retorno Cargado", $idIdioma) . ": " . ($rowOrdenContratacionPrevia->TIENE_RETORNO_CARGADO != 0 ? $auxiliar->traduce("Si", $administrador->ID_IDIOMA) : $auxiliar->traduce("No", $administrador->ID_IDIOMA)) . " " . $auxiliar->traduce("A_TO", $idIdioma) . " " . ($rowOrdenContratacionActualizada->TIENE_RETORNO_CARGADO != 0 ? $auxiliar->traduce("Si", $administrador->ID_IDIOMA) : $auxiliar->traduce("No", $administrador->ID_IDIOMA)) . " " . $saltoLinea;
        endif;

        if ($rowOrdenContratacionActualizada->SUPLEMENTO_RECOGIDAS_CRUZADAS != $rowOrdenContratacionPrevia->SUPLEMENTO_RECOGIDAS_CRUZADAS):
            $strCambios .= $auxiliar->traduce("Recogida Cruzada", $idIdioma) . ": " . ($rowOrdenContratacionPrevia->SUPLEMENTO_RECOGIDAS_CRUZADAS != 0 ? $auxiliar->traduce("Si", $administrador->ID_IDIOMA) : $auxiliar->traduce("No", $administrador->ID_IDIOMA)) . " " . $auxiliar->traduce("A_TO", $idIdioma) . " " . ($rowOrdenContratacionActualizada->SUPLEMENTO_RECOGIDAS_CRUZADAS != 0 ? $auxiliar->traduce("Si", $administrador->ID_IDIOMA) : $auxiliar->traduce("No", $administrador->ID_IDIOMA)) . " " . $saltoLinea;
        endif;

        if ($rowOrdenContratacionActualizada->SERVICIO_DELEGACION_ASM != $rowOrdenContratacionPrevia->SERVICIO_DELEGACION_ASM):
            $strCambios .= $auxiliar->traduce("¿Contratado mediante delegación ASM-Imarcoain?", $idIdioma) . ": " . ($rowOrdenContratacionPrevia->SERVICIO_DELEGACION_ASM != 0 ? $auxiliar->traduce("Si", $administrador->ID_IDIOMA) : $auxiliar->traduce("No", $administrador->ID_IDIOMA)) . " " . $auxiliar->traduce("A_TO", $idIdioma) . " " . ($rowOrdenContratacionActualizada->SERVICIO_DELEGACION_ASM != 0 ? $auxiliar->traduce("Si", $administrador->ID_IDIOMA) : $auxiliar->traduce("No", $administrador->ID_IDIOMA)) . " " . $saltoLinea;
        endif;


        return $strCambios;

    }


    /**
     * @param $idOrdenContratacion ORDEN DE CONTRATACION
     * Si Existe ya informe de certificacion para una contratacion, regeneramos para contemplar los cambios.
     */
    function RegenerarInformeCertificacion($idOrdenContratacion, $regenerar_informe_certificacion = true)
    {

        global $bd;
        global $html;
        global $auxiliar;
        global $administrador;
        global $orden_transporte;
        global $sap;


        //BUSCO LA ORDEN DE RECOGIDA
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowOrdenContratacion             = $bd->VerReg("ORDEN_CONTRATACION", "ID_ORDEN_CONTRATACION", $idOrdenContratacion, "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);

        //BUSCO SI HAY UNA AUTOFACTURA A LA QUE PERTENECE LA ORDEN DE CONTRATACION
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowAutofacturaLinea              = $bd->VerRegRest("AUTOFACTURA_LINEA", "ID_ORDEN_CONTRATACION = $rowOrdenContratacion->ID_ORDEN_CONTRATACION AND BAJA = 0", "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);

        //SI SE HA EJECUTADO LA CONTRATACIÓN Y ES DE CONSTRUCCIÓN, CREAMOS Y GUARDAMOS EL TIPO DE CAMBIO DE MONEDA
        if (($rowOrdenContratacion->ESTADO == "Ejecutada") && ($rowOrdenContratacion->TIPO_ORDEN_TRANSPORTE == 'OTC')):

            //OBTENGO LA ORDEN DE TRANSPORTE ASOCIADA A LA CONTRATACIÓN
            if ($rowOrdenContratacion->ID_ORDEN_TRANSPORTE != NULL):

                $rowOrdenTransporteConstruccion = $orden_transporte->getOrdenTransporteConstruccion($rowOrdenContratacion->ID_ORDEN_TRANSPORTE);

            elseif ($rowOrdenContratacion->ID_ORDEN_TRANSPORTE_SERVICIO_PROVEEDOR != NULL):

                $GLOBALS["NotificaErrorPorEmail"] = "No";
                $rowOTSP                          = $bd->VerReg("ORDEN_TRANSPORTE_SERVICIO_PROVEEDOR", "ID_ORDEN_TRANSPORTE_SERVICIO_PROVEEDOR", $rowOrdenContratacion->ID_ORDEN_TRANSPORTE_SERVICIO_PROVEEDOR, "No");
                unset($GLOBALS["NotificaErrorPorEmail"]);

                $rowOrdenTransporteConstruccion = $orden_transporte->getOrdenTransporteConstruccion($rowOTSP->ID_ORDEN_TRANSPORTE);

            endif;

            //OBTENGO EL ID DE LA SOCIEDAD CONTRATANTE
            $idSociedad = NULL;

            if ($rowOrdenContratacion->ID_SOCIEDAD_CONTRATANTE != NULL):

                //SI VIENE EN LA PROPIA CONTRATACIÓN, LA ASIGNO
                $idSociedad = $rowOrdenContratacion->ID_SOCIEDAD_CONTRATANTE;

            else:

                //OBTENGO LA SOCIEDAD DE LA CONTRATACIÓN
                if ($rowOrdenTransporteConstruccion->ID_ORDEN_TRANSPORTE != NULL):

                    $GLOBALS["NotificaErrorPorEmail"] = "No";
                    $rowSociedad                      = $bd->VerReg("SOCIEDAD", "ID_SOCIEDAD", $rowOrdenTransporteConstruccion->ID_SOCIEDAD_CONTRATANTE, "No");
                    unset($GLOBALS["NotificaErrorPorEmail"]);

                endif;

                $idSociedad = $rowSociedad->ID_SOCIEDAD;

            endif;

            //OBTENEMOS EL PROYECTO
            if ($rowOrdenTransporteConstruccion->ID_ORDEN_TRANSPORTE != NULL):

                //SI LA CONTRATACIÓN TIENE ASIGNADA OT, LO OBTENGO DE LA OT
                $GLOBALS["NotificaErrorPorEmail"] = "No";
                $rowCentroFisico                  = $bd->VerReg("CENTRO_FISICO", "ID_CENTRO_FISICO", $rowOrdenTransporteConstruccion->ID_CENTRO_FISICO_PROYECTO, "No");
                unset($GLOBALS["NotificaErrorPorEmail"]);

            else:

                //SI NO, LO OBTENGO DEL PEDIDO
                //OBTENGO EN PRIMER LUGAR LA LINEA DEL PEDIDO
                $GLOBALS["NotificaErrorPorEmail"] = "No";
                $rowPedidoContratoLinea           = $bd->VerReg("PEDIDO_CONTRATO_LINEA", "ID_PEDIDO_CONTRATO_LINEA", $rowOrdenContratacion->ID_PEDIDO_CONTRATO_LINEA, "No");
                unset($GLOBALS["NotificaErrorPorEmail"]);

                //OBTENGO EL PEDIDO
                $GLOBALS["NotificaErrorPorEmail"] = "No";
                $rowPedidoContrato                = $bd->VerReg("PEDIDO_CONTRATO", "ID_PEDIDO_CONTRATO", $rowPedidoContratoLinea->ID_PEDIDO_CONTRATO, "No");
                unset($GLOBALS["NotificaErrorPorEmail"]);

                //OBTENEMOS EL PROYECTO
                $GLOBALS["NotificaErrorPorEmail"] = "No";
                $rowCentroFisico                  = $bd->VerReg("CENTRO_FISICO", "ID_CENTRO_FISICO", $rowPedidoContrato->ID_CENTRO_FISICO, "No");
                unset($GLOBALS["NotificaErrorPorEmail"]);

            endif;

//            if ($rowOrdenContratacion->TIPO_CAMBIO_MANUAL == 0):
//
//                //SI NO SE HA MODIFICADO MANUALMENTE EL TIPO DE CAMBIO, OBTENEMOS EL TIPO DE CAMBIO
//                $tipoCambio = 0;
//
//                //COMPROBAMOS SI EL PROYECTO TIENE LA MCP ASIGNADA
//                if ($rowCentroFisico->ID_MONEDA_CONTROL != NULL):
//                    if ($rowOrdenContratacion->ID_MONEDA == $rowCentroFisico->ID_MONEDA_CONTROL):
//
//                        //SI LA MONEDA DE ORIGEN Y DESTINO SON IGUALES, LO PONGO A 1
//                        $tipoCambio = 1;
//
//                    else:
//                        //REALIZAMOS LA LLAMADA A SAP PARA OBTENER EL IMPORTE CONVERTIDO
//                        $resultado = $sap->ConvertirImporteMonedaOrigenMonedaDestinoConstruccion($rowOrdenContratacion->IMPORTE_MODIFICADO, $rowOrdenContratacion->ID_MONEDA, $rowCentroFisico->ID_MONEDA_CONTROL, ($rowOrdenContratacion->FECHA_CONTABILIZACION != '0000-00-00' ? $rowOrdenContratacion->FECHA_CONTABILIZACION : $rowOrdenContratacion->FECHA_EJECUCION));
//
//                        if ($resultado['RESULTADO'] == 'OK'):
//
//                            //OBTENGO EL TIPO DE CAMBIO (IMPORTE CALCULADO ENTRE EL ORIGINAL)
//                            $tipoCambio = $resultado['IMPORTE_DEVUELTO'] / $rowOrdenContratacion->IMPORTE_MODIFICADO;
//
//                        else:
//                            //SI EL RESULTADO NO HA SIDO CORRECTO MUESTRO UN MENSAJE DE ERROR
//                            $html->PagError($auxiliar->traduce("Error al obtener el tipo de cambio", $administrador->ID_IDIOMA));
//                        endif;
//                    endif;
//                else:
//                    //SI NO LA TIENE MUESTRO UN MENSAJE DE ERROR
//                    $html->PagError($auxiliar->traduce("El proyecto seleccionado no tiene asignada la Moneda Control de Proyecto", $administrador->ID_IDIOMA));
//                endif;
//
//                //ACTUALIZAMOS LA CONTRATACION
//                $sqlUpdate = "UPDATE ORDEN_CONTRATACION SET
//                                  TIPO_CAMBIO = $tipoCambio
//                                  , ID_MONEDA_CONTROL_PROYECTO = " . ($rowCentroFisico->ID_MONEDA_CONTROL != NULL ? $rowCentroFisico->ID_MONEDA_CONTROL : "NULL") . "
//                                  WHERE ID_ORDEN_CONTRATACION = $rowOrdenContratacion->ID_ORDEN_CONTRATACION";
//                $bd->ExecSQL($sqlUpdate);
//
//            endif;
        endif;

        //SI LA CONTRATACION YA ESTA RECOGIDA EN UN INFORME, REGENERAMOS
        if ($rowAutofacturaLinea):
            //SI LA CONTRATACION DE OT NO ESTA ACEPTADA O DADA DE BAJA, DAMOS DE BAJA EL REGISTRO
            if (($rowOrdenContratacion->BAJA > 0) ||
                (($rowOrdenContratacion->TIPO_ORDEN_TRANSPORTE == 'OT') && ($rowOrdenContratacion->ESTADO <> 'Aceptada'))
            ):
                $sqlUpdate = "UPDATE AUTOFACTURA_LINEA SET BAJA = 1 WHERE ID_AUTOFACTURA_LINEA = $rowAutofacturaLinea->ID_AUTOFACTURA_LINEA";
                $bd->ExecSQL($sqlUpdate);

                //SI NO QUEDA NINGUNA ACTIVA, DAMOS DE BAJA LA AUTOFACTURA
                $numLineasAutofactura = $bd->NumRegsTabla("AUTOFACTURA_LINEA", "ID_AUTOFACTURA = $rowAutofacturaLinea->ID_AUTOFACTURA AND BAJA = 0");
                if ($numLineasAutofactura == 0):
                    //DAMOS DE BAJA
                    $sqlUpdate = "UPDATE AUTOFACTURA SET BAJA = 1 WHERE ID_AUTOFACTURA = $rowAutofacturaLinea->ID_AUTOFACTURA";
                    $bd->ExecSQL($sqlUpdate);
                endif;

            else: //SI NO , REGENERAMOS
                //EJECUTO EL SCRIPT PARA REGENERAR LOS INFORMES DE CERTIFICACION
                $orden_transporte->RegenerarLineaInformeCertificacion($rowOrdenContratacion->ID_ORDEN_CONTRATACION, $regenerar_informe_certificacion);
            endif;
        else: //SI NO , REGENERAMOS
            //EJECUTO EL SCRIPT PARA REGENERAR LOS INFORMES DE CERTIFICACION
            $orden_transporte->RegenerarLineaInformeCertificacion($rowOrdenContratacion->ID_ORDEN_CONTRATACION, $regenerar_informe_certificacion);
        endif;

    }

    /**
     * @param $idOrdenContratacion
     * SI ES NECESARIO, REGENERA UNA LINEA (ORDEN DE CONTRATACION) DE UN INFORME DE CERTIFICACION
     */
    function RegenerarLineaInformeCertificacion($idOrdenContratacion, $regenerar_informe_certificacion = true, $filtrosAplicados = "", $tipoSeleccion = "")
    {


        global $bd;
        global $html;
        global $auxiliar;
        global $administrador;

        //BUSCO LA ORDEN DE RECOGIDA
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowOrdenContratacion             = $bd->VerReg("ORDEN_CONTRATACION", "ID_ORDEN_CONTRATACION", $idOrdenContratacion, "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);

        //BUSCO LA ORDEN DE TRANSPORTE
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowOrdenTransporte               = $bd->VerReg("ORDEN_TRANSPORTE", "ID_ORDEN_TRANSPORTE", $rowOrdenContratacion->ID_ORDEN_TRANSPORTE, "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);

        //BUSCO LA TARIFA SI TIENE
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowTarifa                        = $bd->VerReg("TARIFA", "ID_TARIFA", $rowOrdenContratacion->ID_TARIFA, "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);

        //VARIABLE PARA SABER SI HAY QUE ACTUALIZAR LA LINEA DE LA AUTOFACTURA CORRESPONDIENTE A LA ORDEN DE CONTRATACION
        $actualizarAutofacturaLineaOrdenContratacion = false;

        //BUSCO SI HAY UNA AUTOFACTURA A LA QUE PERTENECE LA ORDEN DE CONTRATACION
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowAutofacturaLinea              = $bd->VerRegRest("AUTOFACTURA_LINEA", "ID_ORDEN_CONTRATACION = $rowOrdenContratacion->ID_ORDEN_CONTRATACION AND BAJA = 0", "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);

        //ACCIONES EN FUNCION DE SI EXISTE O NO LA LINEA DE AUTOFACTURA
        if ($rowAutofacturaLinea == false): //NO EXISTE
            //MARCO LA ORDEN DE CONTRATACION PARA ACTUALIZAR AUTOFACTURA
            $actualizarAutofacturaLineaOrdenContratacion = true;
        elseif ($rowAutofacturaLinea != false): //EXISTE
            //BUSCO LA AUTOFACTURA
            $rowAutofactura = $bd->VerReg("AUTOFACTURA", "ID_AUTOFACTURA", $rowAutofacturaLinea->ID_AUTOFACTURA);

            //COMPRUEBO QUE COINCIDA LA REFERENCIA DE FACTURACION
            if ($rowAutofactura->REFERENCIA_FACTURACION != $rowOrdenContratacion->REFERENCIA_FACTURACION):
                //DOY DE BAJA LA AUTOFACTURA LINEA
                $sqlUpdate = "UPDATE AUTOFACTURA_LINEA SET BAJA = 1 WHERE ID_AUTOFACTURA_LINEA = $rowAutofacturaLinea->ID_AUTOFACTURA_LINEA";
                $bd->ExecSQL($sqlUpdate);

                //MARCO LA ORDEN DE CONTRATACION PARA ACTUALIZAR AUTOFACTURA
                $actualizarAutofacturaLineaOrdenContratacion = true;
            endif;

            //COMPRUEBO QUE COINCIDA EL PROVEEDOR
            if ($rowAutofactura->ID_PROVEEDOR != $rowOrdenContratacion->ID_PROVEEDOR):
                //DOY DE BAJA LA AUTOFACTURA LINEA
                $sqlUpdate = "UPDATE AUTOFACTURA_LINEA SET BAJA = 1 WHERE ID_AUTOFACTURA_LINEA = $rowAutofacturaLinea->ID_AUTOFACTURA_LINEA";
                $bd->ExecSQL($sqlUpdate);

                //MARCO LA ORDEN DE CONTRATACION PARA ACTUALIZAR AUTOFACTURA
                $actualizarAutofacturaLineaOrdenContratacion = true;
            endif;

            //COMPRUEBO QUE EL IMPORTE NO HAYA SIDO MODIFICADO
            if ($rowAutofacturaLinea->IMPORTE != $rowOrdenContratacion->IMPORTE_MODIFICADO):
                //DOY DE BAJA LA AUTOFACTURA LINEA
                $sqlUpdate = "UPDATE AUTOFACTURA_LINEA SET BAJA = 1 WHERE ID_AUTOFACTURA_LINEA = $rowAutofacturaLinea->ID_AUTOFACTURA_LINEA";
                $bd->ExecSQL($sqlUpdate);

                //MARCO LA ORDEN DE CONTRATACION PARA ACTUALIZAR AUTOFACTURA
                $actualizarAutofacturaLineaOrdenContratacion = true;
            endif;

            //SI LA CONTRATACION ES DE CONSTRUCCION Y CAMBIA SOLAMENTE LA FECHA EJECUCION, SIMPLEMENTE SE ACTUALIZA
            if (($actualizarAutofacturaLineaOrdenContratacion == false) && ($rowOrdenContratacion->TIPO_ORDEN_TRANSPORTE == 'OTC') && ($rowAutofacturaLinea->FECHA_EJECUCION != $rowOrdenContratacion->FECHA_EJECUCION)):
                //DOY DE BAJA LA AUTOFACTURA LINEA
                $sqlUpdate = "UPDATE AUTOFACTURA_LINEA SET FECHA_EJECUCION = '" . $rowOrdenContratacion->FECHA_EJECUCION . "' WHERE ID_AUTOFACTURA_LINEA = $rowAutofacturaLinea->ID_AUTOFACTURA_LINEA";
                $bd->ExecSQL($sqlUpdate);
            endif;
            //FIN COMPRUEBO QUE EL IMPORTE NO HAYA SIDO MODIFICADO
        endif;
        //FIN ACCIONES EN FUNCION DE SI EXISTE O NO LA LINEA DE AUTOFACTURA

        //SI LA VARIABLE ACTUALIZAR AUTOFACTURA LINEA ESTA A TRUE GENERO LA AUTOFACTURA DE LA ORDEN DE CONTRATACION, SIEMPRE QUE LA MONEDA ESTE ASIGNADA YA
        if ($actualizarAutofacturaLineaOrdenContratacion == true && $rowOrdenContratacion->ID_MONEDA != NULL):

            //BUSCO SI EXISTE UNA AUTOFACTURA ACTIVA A LA QUE AÑADIR ESTA ORDEN DE CONTRATACION
            if (($rowOrdenContratacion->ID_ORDEN_TRANSPORTE != NULL) && ($rowOrdenTransporte->MODELO_TRANSPORTE == 'Segundo') && ($rowOrdenContratacion->TIPO_ORDEN_TRANSPORTE != "OTC")):
                $sqlAutofactura = "SELECT AF.ID_AUTOFACTURA
                                   FROM AUTOFACTURA AF
                                   INNER JOIN PROVEEDOR_REFERENCIA_FACTURACION PRF ON PRF.ID_PROVEEDOR = AF.ID_PROVEEDOR AND PRF.REFERENCIA_FACTURACION_NUEVO_MODELO = AF.REFERENCIA_FACTURACION
                                   WHERE AF.BAJA = 0 AND PRF.REFERENCIA_FACTURACION_NUEVO_MODELO = '" . $rowOrdenContratacion->REFERENCIA_FACTURACION . "'";
            else:
                $sqlAutofactura = "SELECT AF.ID_AUTOFACTURA
                                   FROM AUTOFACTURA AF
                                   INNER JOIN PROVEEDOR_REFERENCIA_FACTURACION PRF ON PRF.ID_PROVEEDOR = AF.ID_PROVEEDOR AND PRF.REFERENCIA_FACTURACION = AF.REFERENCIA_FACTURACION
                                   WHERE AF.BAJA = 0 AND PRF.REFERENCIA_FACTURACION = '" . $rowOrdenContratacion->REFERENCIA_FACTURACION . "'";
            endif;
            $resultAutofactura = $bd->ExecSQL($sqlAutofactura);
            if ($bd->NumRegs($resultAutofactura) == 0): //NO EXISTE UNA AUTOFACTURA A LA QUE ASIGNAR ESTA ORDEN DE CONTRATACION, SERA NECESARIO CREAR UNA AUTOFACTURA
                if ($regenerar_informe_certificacion):
                    //FECHA DE CREACION DE INF. CERTIFIACION DEBE CORRESPONDERSE CON EL ULTIMO DIA DEL PERIODO DE FACT.
                    //GUARDAR PERIODO A LA TABLA AUTOFACTURA
                    //BUSCO LA REFERENCIA FACTURACION DEL PROVEEDOR POR PROVEEDOR
                    if (($rowOrdenContratacion->ID_ORDEN_TRANSPORTE != NULL) && ($rowOrdenTransporte->MODELO_TRANSPORTE == 'Segundo') && ($rowOrdenContratacion->TIPO_ORDEN_TRANSPORTE != "OTC")):
                        $GLOBALS["NotificaErrorPorEmail"]  = "No";
                        $rowProveedorReferenciaFacturacion = $bd->VerRegRest("PROVEEDOR_REFERENCIA_FACTURACION", "ID_PROVEEDOR = $rowOrdenContratacion->ID_PROVEEDOR AND REFERENCIA_FACTURACION_NUEVO_MODELO = '" . $rowOrdenContratacion->REFERENCIA_FACTURACION . "'", "No");
                        unset($GLOBALS["NotificaErrorPorEmail"]);
                    else:
                        $GLOBALS["NotificaErrorPorEmail"]  = "No";
                        $rowProveedorReferenciaFacturacion = $bd->VerRegRest("PROVEEDOR_REFERENCIA_FACTURACION", "ID_PROVEEDOR = $rowOrdenContratacion->ID_PROVEEDOR AND REFERENCIA_FACTURACION = '" . $rowOrdenContratacion->REFERENCIA_FACTURACION . "'", "No");
                        unset($GLOBALS["NotificaErrorPorEmail"]);
                    endif;

                    //SI NO EXISTE, BUSCAMOS SI AL MENOS EXISTE LA REFERENCIA FACTURACION (Se ha podido cambiar a mano)
                    if ($rowProveedorReferenciaFacturacion == false):
                        if (($rowOrdenContratacion->ID_ORDEN_TRANSPORTE != NULL) && ($rowOrdenTransporte->MODELO_TRANSPORTE == 'Segundo') && ($rowOrdenContratacion->TIPO_ORDEN_TRANSPORTE != "OTC")):
                            $GLOBALS["NotificaErrorPorEmail"]  = "No";
                            $rowProveedorReferenciaFacturacion = $bd->VerRegRest("PROVEEDOR_REFERENCIA_FACTURACION", "REFERENCIA_FACTURACION_NUEVO_MODELO = '" . $rowOrdenContratacion->REFERENCIA_FACTURACION . "'", "No");
                            unset($GLOBALS["NotificaErrorPorEmail"]);
                        else:
                            $GLOBALS["NotificaErrorPorEmail"]  = "No";
                            $rowProveedorReferenciaFacturacion = $bd->VerRegRest("PROVEEDOR_REFERENCIA_FACTURACION", "REFERENCIA_FACTURACION = '" . $rowOrdenContratacion->REFERENCIA_FACTURACION . "'", "No");
                            unset($GLOBALS["NotificaErrorPorEmail"]);
                        endif;
                    endif;

                    $fechaCreacionFinPeriodo    = $rowProveedorReferenciaFacturacion->FECHA_FIN_PERIODO;
                    $fechaCreacionInicioPeriodo = $rowProveedorReferenciaFacturacion->FECHA_INICIO_PERIODO;

                    //BUSCO EL ESTADO DE LA AUTOFACTURA
                    if ((isset($rowTarifa)) && ($rowTarifa->PRECIOS_INTRODUCIDOS_POR == 'Gestor')):
                        $estadoAutofactura = 'Pdte. Verificar Imp. Proveedor';
                    else:
                        $estadoAutofactura = 'Pdte. Tratar';
                    endif;

                    //CREO LA AUTOFACTURA
                    $sqlInsert = "INSERT INTO AUTOFACTURA SET
                              ID_PROVEEDOR = $rowOrdenContratacion->ID_PROVEEDOR
                              , REFERENCIA_FACTURACION = '" . $rowOrdenContratacion->REFERENCIA_FACTURACION . "'
                              , TIPO_TRANSPORTE_FACTURACION = '" . $rowOrdenContratacion->TIPO_ORDEN_TRANSPORTE . "'
                              , ESTADO_CERTIFICACION = '" . $estadoAutofactura . "'
                              , TIPO_FACTURACION = '" . $rowProveedorReferenciaFacturacion->PERIODO_FACTURACION . "'
                              , OBSERVACIONES_FILTROS_APLICADOS = '" . trim($bd->escapeCondicional($filtrosAplicados)) . "'
                              , TIPO_SELECCION = '" . trim($bd->escapeCondicional($tipoSeleccion)) . "'
                              , FECHA_CREACION = '" . date('Y-m-d H:i:s') . "'
                              , FECHA_INICIO_INTERVALO = '" . $fechaCreacionInicioPeriodo . "'
                              , FECHA_FIN_INTERVALO = '" . $fechaCreacionFinPeriodo . "'";

                    $bd->ExecSQL($sqlInsert); //echo($sqlInsert . "<hr>");
                    $idAutofactura  = $bd->IdAsignado();
                    $rowAutofactura = $bd->VerReg("AUTOFACTURA", "ID_AUTOFACTURA", $idAutofactura);
                endif;
            else:
                $rowAutofactura = $bd->SigReg($resultAutofactura);

            endif;
            //FIN BUSCO SI EXISTE UNA AUTOFACTURA ACTIVA A LA QUE AÑADIR ESTA ORDEN DE CONTRATACION

            //BUSCO EL ESTADO DE LA AUTOFACTURA
            if ($regenerar_informe_certificacion):
                if ((isset($rowTarifa)) && ($rowTarifa->PRECIOS_INTRODUCIDOS_POR == 'Gestor')):
                    $estadoAutofacturaLinea = 'Pdte. Verificar Imp. Proveedor';
                else:
                    $estadoAutofacturaLinea = 'Pdte. Tratar';
                endif;

                //AÑADO LA LINEA DE LA CONTRATACION (SI EXISTIA LINEA PREVIA Y ESTABA TRATADA O FUE CREADA POR UNA TRATADA, MARCAMOS EL CHECK MODIFICADO_TRAS_CERTIFICADO)
                $sqlInsert = "INSERT INTO AUTOFACTURA_LINEA SET
                              ID_AUTOFACTURA = $rowAutofactura->ID_AUTOFACTURA
                              , ESTADO = '" . $estadoAutofacturaLinea . "'
                              , ID_ORDEN_CONTRATACION = $rowOrdenContratacion->ID_ORDEN_CONTRATACION
                              , IMPORTE = $rowOrdenContratacion->IMPORTE_MODIFICADO
                              , ID_MONEDA = $rowOrdenContratacion->ID_MONEDA
                              , MODIFICADO_TRAS_CERTIFICADO = " . (($rowAutofacturaLinea->ESTADO == "Tratada" || $rowAutofacturaLinea->MODIFICADO_TRAS_CERTIFICADO == 1) ? "1" : "0") . "
                              , FECHA_EJECUCION = '" . $rowOrdenContratacion->FECHA_EJECUCION . "'";
                $bd->ExecSQL($sqlInsert); //echo($sqlInsert . "<hr>");
                //FIN AÑADO LAS LINEAS DE CONTRATACION
            endif;

        endif;
        //FIN SI LA VARIABLE ACTUALIZAR AUTOFACTURA LINEA ESTA A TRUE GENERO LA AUTOFACTURA DE LA ORDEN DE CONTRATACION

        //SI EXISTE AUTOFACTURA LA ACTUALIZAMOS
        if (isset($rowAutofactura)):
            $estadoAutofactura  = $this->getEstadoAutofactura($rowAutofactura->ID_AUTOFACTURA);
            $estado_actualizado = false;

            if ($rowAutofactura->ESTADO_CERTIFICACION != $estadoAutofactura):
                $sqlUpdate = "UPDATE AUTOFACTURA SET ESTADO_CERTIFICACION = '" . $bd->escapeCondicional($estadoAutofactura) . "' WHERE ID_AUTOFACTURA = $rowAutofactura->ID_AUTOFACTURA ";
                $bd->ExecSQL($sqlUpdate);
                $estado_actualizado = true;
            endif;

            //SI SE ACTUALIZA EL ESTADO, Y EL ESTADO INICIAL ERA CERTIFICADO Y ES DE TIPO OTC, ACTUALIZAMOS SUS CONTRATACIONES
            if (($estado_actualizado == true) && ($rowAutofactura->ESTADO_CERTIFICACION == 'Certificado') && ($rowAutofactura->TIPO_TRANSPORTE_FACTURACION == 'OTC')):
                //BUSCAMOS CONTRATACIONES
                $sqlContrataciones    = "SELECT DISTINCT AL.ID_ORDEN_CONTRATACION, OC.FECHA_EJECUCION
                                        FROM AUTOFACTURA_LINEA AL 
                                        INNER JOIN ORDEN_CONTRATACION OC ON OC.ID_ORDEN_CONTRATACION = AL.ID_ORDEN_CONTRATACION
                                        WHERE AL.ID_AUTOFACTURA = $rowAutofactura->ID_AUTOFACTURA AND AL.BAJA = 0 AND OC.TIPO_ORDEN_TRANSPORTE = 'OTC'";
                $resultContrataciones = $bd->ExecSQL($sqlContrataciones);
                while ($rowContrataciones = $bd->SigReg($resultContrataciones)):

                    //ACTUALIZAMOS ESTADO DE LA CONTRATACION
                    if ($rowContrataciones->FECHA_EJECUCION == NULL):
                        $estado_contratacion = "En Ejecucion";
                    else:
                        if ($rowContrataciones->FECHA_EJECUCION <= date('Y-m-d')):
                            $estado_contratacion = "Ejecutada";
                        else:
                            $estado_contratacion = "En Ejecucion";
                        endif;
                    endif;

                    //ACTUALIZAMOS
                    $sqlUpdate = "UPDATE ORDEN_CONTRATACION SET 
                                ESTADO = '" . $estado_contratacion . "' 
                                WHERE ID_ORDEN_CONTRATACION = $rowContrataciones->ID_ORDEN_CONTRATACION";
                    $bd->ExecSQL($sqlUpdate);

                    //LOG MOVIMIENTOS
                    $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Orden de Contratacion", $rowContrataciones->ID_ORDEN_CONTRATACION, "Cancelar Certificacion");
                endwhile;

            endif;

        endif;
    }

    /**DEVUELVE EL ESTADO DE LA AUTOFACTURA*/
    function getEstadoAutofactura($idAutofactura)
    {
        global $bd;
        global $html;
        global $auxiliar;
        global $administrador;

        //BUSCO LA AUTOFACTURA
        $rowAutofactura = $bd->VerReg("AUTOFACTURA", "ID_AUTOFACTURA", $idAutofactura);

        //CALCULO EL NUEVO ESTADO DE LA AUTOFACTURA
        $numLineas                              = $bd->NumRegsTabla("AUTOFACTURA_LINEA", "ID_AUTOFACTURA = $rowAutofactura->ID_AUTOFACTURA AND BAJA = 0");
        $numLineasPdteVerificarImporteProveedor = $bd->NumRegsTabla("AUTOFACTURA_LINEA", "ID_AUTOFACTURA = $rowAutofactura->ID_AUTOFACTURA AND BAJA = 0 AND ESTADO = 'Pdte. Verificar Imp. Proveedor'");
        $numLineasVerificadoImporteProveedor    = $bd->NumRegsTabla("AUTOFACTURA_LINEA", "ID_AUTOFACTURA = $rowAutofactura->ID_AUTOFACTURA AND BAJA = 0 AND ESTADO = 'Verificado Importe Proveedor'");
        $numLineasEnTransmisionASAP             = $bd->NumRegsTabla("AUTOFACTURA_LINEA", "ID_AUTOFACTURA = $rowAutofactura->ID_AUTOFACTURA AND BAJA = 0 AND ESTADO = 'En Transmision a SAP'");
        $numLineasPdteTratar                    = $bd->NumRegsTabla("AUTOFACTURA_LINEA", "ID_AUTOFACTURA = $rowAutofactura->ID_AUTOFACTURA AND BAJA = 0 AND ESTADO = 'Pdte. Tratar'");
        $numLineasTratadas                      = $bd->NumRegsTabla("AUTOFACTURA_LINEA", "ID_AUTOFACTURA = $rowAutofactura->ID_AUTOFACTURA AND BAJA = 0 AND ESTADO = 'Tratada'");
        if ($numLineas == $numLineasPdteVerificarImporteProveedor):
            $estadoAutofactura = 'Pdte. Verificar Imp. Proveedor';
        elseif ($numLineas == $numLineasPdteTratar):
            $estadoAutofactura = 'Pdte. Tratar';
        elseif (($numLineas == ($numLineasPdteVerificarImporteProveedor + $numLineasVerificadoImporteProveedor + $numLineasEnTransmisionASAP + $numLineasPdteTratar))
            && ($numLineasPdteVerificarImporteProveedor + $numLineasVerificadoImporteProveedor > 0)
        ):
            $estadoAutofactura = 'En Verificacion Imp. Proveedor';

        elseif ($numLineas == $numLineasTratadas):
            //SI EL ESTADO ES CERTIFICADO, LO DEJAMOS IGUAL, SI NO, PDTE CERTIFICAR
            if ($rowAutofactura->ESTADO_CERTIFICACION != 'Certificado' && $rowAutofactura->ESTADO_CERTIFICACION != 'Contabilizado'):
                $estadoAutofactura = 'Pdte. Certificar';
            else:
                $estadoAutofactura = $rowAutofactura->ESTADO_CERTIFICACION;
            endif;
        else:
            $estadoAutofactura = 'En Tratamiento';
        endif;

        return $estadoAutofactura;
    }


    /**
     * @param $idOrdenTransporte TRANSPORTE A DEVOLVER
     * DEVUELVE LA ROW DE LA ORDEN DE TRANSPORTE CONSTRUCCION CON SUS CAMPOS (LOS DE LA TABLA ORDEN TRANSPORTE Y LOS DE ORDEN TRANSPORTE CONSTRUCCION)
     */
    function getOrdenTransporteConstruccion($idOrdenTransporte)
    {
        global $html;
        global $bd;
        global $administrador;
        global $auxiliar;

        //BUSCAMOS LA ORDEN DE TRANSPORTE
        $sqlOrdenTransporte = "SELECT OTC.*, OTCA.*, OT.*
                                FROM ORDEN_TRANSPORTE OT
                                INNER JOIN ORDEN_TRANSPORTE_CONSTRUCCION OTC ON OTC.ID_ORDEN_TRANSPORTE = OT.ID_ORDEN_TRANSPORTE
                                INNER JOIN ORDEN_TRANSPORTE_CONSTRUCCION_AMPLIACION OTCA ON OTCA.ID_ORDEN_TRANSPORTE = OT.ID_ORDEN_TRANSPORTE
                                WHERE OT.TIPO_ORDEN_TRANSPORTE = 'OTC' AND OT.ID_ORDEN_TRANSPORTE = $idOrdenTransporte";
        $resOrdenTransporte = $bd->ExecSQL($sqlOrdenTransporte);
        $rowOrdenTransporte = $bd->SigReg($resOrdenTransporte);

        return $rowOrdenTransporte;
    }

    /**
     * @param $rowOrdenTransporte
     * @param $rowOrdenTransporteActualizada
     * COMPROBAMOS SI EL VALOR DE LAS FECHAS HA SIDO MODIFICADO, Y EN TAL CASO MODICAR LA FECHA FINAL. LA Fecha entrega final planificada NO SE MODIFICARA SI EL PLANIFICADOR LA HA ACEPTADO
     */
    function ActualizarFechasAutomaticamente($rowOrdenTransporte, $rowOrdenTransporteActualizada)
    {
        global $html;
        global $bd;
        global $administrador;
        global $auxiliar;

        //SI CAMBIA LA FECHA ESTIMADA SALIDA
        if (($rowOrdenTransporte->FECHA_ESTIMADA_LLEGADA != $rowOrdenTransporteActualizada->FECHA_ESTIMADA_LLEGADA) && ($rowOrdenTransporteActualizada->FECHA_ESTIMADA_LLEGADA != NULL) && ($rowOrdenTransporteActualizada->ACEPTACION_PLANIFICACION_POR_PLANIFICADOR != 1)):
            //ACTUALIZAMOS LA FECHA ENTREGA FINAL PLANIFICADA CON ESA FECHA + 7 dias
            $sqlUpdate = "UPDATE ORDEN_TRANSPORTE_CONSTRUCCION_AMPLIACION SET
                          FECHA_ENTREGA_FINAL_PLANIFICADA = DATE_ADD('" . $rowOrdenTransporteActualizada->FECHA_ESTIMADA_LLEGADA . "', INTERVAL 7 DAY)
                          WHERE ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE";
            $bd->ExecSQL($sqlUpdate);

        endif;

        //SI CAMBIA LA FECHA REAL LLEGADA
        if (($rowOrdenTransporte->FECHA_REAL_LLEGADA != $rowOrdenTransporteActualizada->FECHA_REAL_LLEGADA) && ($rowOrdenTransporteActualizada->FECHA_REAL_LLEGADA != NULL) && ($rowOrdenTransporteActualizada->ACEPTACION_PLANIFICACION_POR_PLANIFICADOR != 1)):
            //CALCULAMOS EL ULTIMO DOMINGO DEL MES SIGUIENTE
            $txNuevaFecha = date("Y-m-d", strtotime((string)date("Y-m-01", strtotime((string)$rowOrdenTransporteActualizada->FECHA_REAL_LLEGADA . "+2 months")) . "previous sunday"));
            //ACTUALIZAMOS LA FECHA ENTREGA FINAL PLANIFICADA CON ESA FECHA + 7 dias
            $sqlUpdate = "UPDATE ORDEN_TRANSPORTE_CONSTRUCCION_AMPLIACION SET
                          FECHA_ENTREGA_FINAL_PLANIFICADA = '" . $txNuevaFecha . "'
                          WHERE ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE";
            $bd->ExecSQL($sqlUpdate);

        endif;

        //SI CAMBIA LA FECHA ESTIMADA RESOLUCION
        if (($rowOrdenTransporte->FECHA_ESTIMADA_RESOLUCION_ADUANA != $rowOrdenTransporteActualizada->FECHA_ESTIMADA_RESOLUCION_ADUANA) && ($rowOrdenTransporteActualizada->FECHA_ESTIMADA_RESOLUCION_ADUANA != NULL) && ($rowOrdenTransporteActualizada->ACEPTACION_PLANIFICACION_POR_PLANIFICADOR != 1)):
            //CALCULAMOS EL ULTIMO DOMINGO DEL MES SIGUIENTE
            $txNuevaFecha = date("Y-m-d", strtotime((string)date("Y-m-01", strtotime((string)$rowOrdenTransporteActualizada->FECHA_ESTIMADA_RESOLUCION_ADUANA . "+2 months")) . "previous sunday"));
            //ACTUALIZAMOS LA FECHA ENTREGA FINAL PLANIFICADA CON ESA FECHA + 3 dias
            $sqlUpdate = "UPDATE ORDEN_TRANSPORTE_CONSTRUCCION_AMPLIACION SET
                          FECHA_ENTREGA_FINAL_PLANIFICADA = '" . $txNuevaFecha . "'
                          WHERE ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE";
            $bd->ExecSQL($sqlUpdate);

        endif;

        //SI CAMBIA LA FECHA ESTIMADA LEVANTE MATERIAL
        if (($rowOrdenTransporte->FECHA_ESTIMADA_LEVANTE_MATERIAL != $rowOrdenTransporteActualizada->FECHA_ESTIMADA_LEVANTE_MATERIAL) && ($rowOrdenTransporteActualizada->FECHA_ESTIMADA_LEVANTE_MATERIAL != NULL) && ($rowOrdenTransporteActualizada->ACEPTACION_PLANIFICACION_POR_PLANIFICADOR != 1)):
            //CALCULAMOS EL ULTIMO DOMINGO DEL MES SIGUIENTE
            $txNuevaFecha = date("Y-m-d", strtotime((string)date("Y-m-01", strtotime((string)$rowOrdenTransporteActualizada->FECHA_ESTIMADA_LEVANTE_MATERIAL . "+2 months")) . "previous sunday"));
            //ACTUALIZAMOS LA FECHA ENTREGA FINAL PLANIFICADA CON ESA FECHA + 3 dias
            $sqlUpdate = "UPDATE ORDEN_TRANSPORTE_CONSTRUCCION_AMPLIACION SET
                          FECHA_ENTREGA_FINAL_PLANIFICADA = '" . $txNuevaFecha . "'
                          WHERE ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE";
            $bd->ExecSQL($sqlUpdate);
        endif;

        //SI CAMBIA LA FECHA PLANIFICADA ENTREGA SEGUN INCOTERM , CAMBIAMOS LA MISMA CANTIDAD DE DIAS LA ENTREGA FINAL, ETD Y ETA PLANIFICADA
        if (($rowOrdenTransporte->FECHA_PLANIFICADA_ENTREGA_DE_PROVEEDOR != $rowOrdenTransporteActualizada->FECHA_PLANIFICADA_ENTREGA_DE_PROVEEDOR) && ($rowOrdenTransporteActualizada->FECHA_PLANIFICADA_ENTREGA_DE_PROVEEDOR) && ($rowOrdenTransporte->FECHA_PLANIFICADA_ENTREGA_DE_PROVEEDOR)):

            //CALCULAMOS LA DIFERENCIA DE DIAS
            $sqlDiferencia    = "SELECT DATEDIFF('" . $rowOrdenTransporteActualizada->FECHA_PLANIFICADA_ENTREGA_DE_PROVEEDOR . "', '" . $rowOrdenTransporte->FECHA_PLANIFICADA_ENTREGA_DE_PROVEEDOR . "') AS DIFERENCIA_FECHA";
            $resultDiferencia = $bd->ExecSQL($sqlDiferencia);
            $rowDiferencia    = $bd->SigReg($resultDiferencia);

            //OBTENEMOS LAS HORAS DE DIFERENCIAI
            if ($rowDiferencia->DIFERENCIA_FECHA > 0):
                if ($rowOrdenTransporteActualizada->ACEPTACION_PLANIFICACION_POR_PLANIFICADOR != 1):
                    //ACTUALIZAMOS CON LA DIFERENCIA
                    $sqlUpdate = "UPDATE ORDEN_TRANSPORTE_CONSTRUCCION_AMPLIACION SET
                                FECHA_ENTREGA_FINAL_PLANIFICADA = DATE_ADD(FECHA_ENTREGA_FINAL_PLANIFICADA, INTERVAL " . $rowDiferencia->DIFERENCIA_FECHA . " DAY)
                                WHERE ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE";
                    $bd->ExecSQL($sqlUpdate);
                endif;

                $sqlUpdate = "UPDATE ORDEN_TRANSPORTE_CONSTRUCCION SET
                                  FECHA_ESTIMADA_SALIDA = DATE_ADD(FECHA_ESTIMADA_SALIDA, INTERVAL " . $rowDiferencia->DIFERENCIA_FECHA . " DAY)
                                , FECHA_ESTIMADA_LLEGADA = DATE_ADD(FECHA_ESTIMADA_LLEGADA, INTERVAL " . $rowDiferencia->DIFERENCIA_FECHA . " DAY)
                                WHERE ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE";
                $bd->ExecSQL($sqlUpdate);
            endif;
        endif;

        //SI CAMBIA LA FECHA LEVANTE MATERIAL
        if (($rowOrdenTransporte->FECHA_REAL_LEVANTE_MATERIAL != $rowOrdenTransporteActualizada->FECHA_REAL_LEVANTE_MATERIAL) && ($rowOrdenTransporteActualizada->FECHA_REAL_LEVANTE_MATERIAL != NULL) && ($rowOrdenTransporteActualizada->ACEPTACION_PLANIFICACION_POR_PLANIFICADOR != 1)):
            //CALCULAMOS EL ULTIMO DOMINGO DEL MES SIGUIENTE
            $txNuevaFecha = date("Y-m-d", strtotime((string)date("Y-m-01", strtotime((string)$rowOrdenTransporteActualizada->FECHA_REAL_LEVANTE_MATERIAL . "+2 months")) . "previous sunday"));
            //ACTUALIZAMOS LA FECHA ENTREGA FINAL PLANIFICADA CON ESA FECHA + 3 dias
            $sqlUpdate = "UPDATE ORDEN_TRANSPORTE_CONSTRUCCION_AMPLIACION SET
                          FECHA_ENTREGA_FINAL_PLANIFICADA = '" . $txNuevaFecha . "'
                          WHERE ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE";
            $bd->ExecSQL($sqlUpdate);
        endif;

        //OBTENEMOS LA ROW ACTUALIZADA PARA VER SI HAY QUE ACTUALIZAR LAS ACCIONES
        $rowOrdenTransporteActualizada = $this->getOrdenTransporteConstruccion($rowOrdenTransporte->ID_ORDEN_TRANSPORTE);

        //SI HAN ACTUALIZADO LA FECHA OBJETIVO INCOTERM, ACTUALIZAMOS LA FECHA DE LAS ACCIONES RELACIONADAS
        if (($rowOrdenTransporteActualizada->FECHA_PLANIFICADA_ENTREGA_DE_PROVEEDOR != $rowOrdenTransporte->FECHA_PLANIFICADA_ENTREGA_DE_PROVEEDOR) && ($rowOrdenTransporteActualizada->FECHA_PLANIFICADA_ENTREGA_DE_PROVEEDOR != NULL)):
            //ACTUALIZAMOS LOS AVISOS RELACIONADO CON ACCIONES QUE HACEN REFERENCIA A LA FECHA OBJETIVO
            $sqlUpdate = "UPDATE ORDEN_TRANSPORTE_ACCION_AVISO OTAA
                                INNER JOIN ORDEN_TRANSPORTE_ACCION OTA ON OTA.ID_ORDEN_TRANSPORTE_ACCION = OTAA.ID_ORDEN_TRANSPORTE_ACCION
                                SET OTAA.FECHA_RELACIONADA = '" . $rowOrdenTransporteActualizada->FECHA_PLANIFICADA_ENTREGA_DE_PROVEEDOR . "'
                                WHERE OTAA.ID_ORDEN_TRANSPORTE = $rowOrdenTransporteActualizada->ID_ORDEN_TRANSPORTE AND OTA.CAMPO_FECHA_RELACIONADO = 'FECHA_PLANIFICADA_ENTREGA_DE_PROVEEDOR'";
            $bd->ExecSQL($sqlUpdate);
        endif;

        //SI HAN ACTUALIZADO LA FECHA_ESTIMADA_SALIDA
        if (($rowOrdenTransporteActualizada->FECHA_ESTIMADA_SALIDA != $rowOrdenTransporte->FECHA_ESTIMADA_SALIDA) && ($rowOrdenTransporteActualizada->FECHA_ESTIMADA_SALIDA != NULL)):
            //ACTUALIZAMOS LOS AVISOS RELACIONADO CON ACCIONES QUE HACEN REFERENCIA A LA FECHA OBJETIVO
            $sqlUpdate = "UPDATE ORDEN_TRANSPORTE_ACCION_AVISO OTAA
                                INNER JOIN ORDEN_TRANSPORTE_ACCION OTA ON OTA.ID_ORDEN_TRANSPORTE_ACCION = OTAA.ID_ORDEN_TRANSPORTE_ACCION
                                SET OTAA.FECHA_RELACIONADA = '" . $rowOrdenTransporteActualizada->FECHA_ESTIMADA_SALIDA . "'
                                WHERE OTAA.ID_ORDEN_TRANSPORTE = $rowOrdenTransporteActualizada->ID_ORDEN_TRANSPORTE AND OTA.CAMPO_FECHA_RELACIONADO = 'FECHA_ESTIMADA_SALIDA'";
            $bd->ExecSQL($sqlUpdate);
        endif;

        //SI HAN ACTUALIZADO LA FECHA_ESTIMADA_LLEGADA
        if (($rowOrdenTransporteActualizada->FECHA_ESTIMADA_LLEGADA != $rowOrdenTransporte->FECHA_ESTIMADA_LLEGADA) && ($rowOrdenTransporteActualizada->FECHA_ESTIMADA_LLEGADA != NULL)):
            //ACTUALIZAMOS LOS AVISOS RELACIONADO CON ACCIONES QUE HACEN REFERENCIA A LA FECHA OBJETIVO
            $sqlUpdate = "UPDATE ORDEN_TRANSPORTE_ACCION_AVISO OTAA
                                INNER JOIN ORDEN_TRANSPORTE_ACCION OTA ON OTA.ID_ORDEN_TRANSPORTE_ACCION = OTAA.ID_ORDEN_TRANSPORTE_ACCION
                                SET OTAA.FECHA_RELACIONADA = '" . $rowOrdenTransporteActualizada->FECHA_ESTIMADA_LLEGADA . "'
                                WHERE OTAA.ID_ORDEN_TRANSPORTE = $rowOrdenTransporteActualizada->ID_ORDEN_TRANSPORTE AND OTA.CAMPO_FECHA_RELACIONADO = 'FECHA_ESTIMADA_LLEGADA'";
            $bd->ExecSQL($sqlUpdate);
        endif;

        //SI HAN ACTUALIZADO LA FECHA_ESTIMADA_LEVANTE_MATERIAL
        if (($rowOrdenTransporteActualizada->FECHA_ESTIMADA_LEVANTE_MATERIAL != $rowOrdenTransporte->FECHA_ESTIMADA_LEVANTE_MATERIAL) && ($rowOrdenTransporteActualizada->FECHA_ESTIMADA_LEVANTE_MATERIAL != NULL)):
            //ACTUALIZAMOS LOS AVISOS RELACIONADO CON ACCIONES QUE HACEN REFERENCIA A LA FECHA OBJETIVO
            $sqlUpdate = "UPDATE ORDEN_TRANSPORTE_ACCION_AVISO OTAA
                                INNER JOIN ORDEN_TRANSPORTE_ACCION OTA ON OTA.ID_ORDEN_TRANSPORTE_ACCION = OTAA.ID_ORDEN_TRANSPORTE_ACCION
                                SET OTAA.FECHA_RELACIONADA = '" . $rowOrdenTransporteActualizada->FECHA_ESTIMADA_LEVANTE_MATERIAL . "'
                                WHERE OTAA.ID_ORDEN_TRANSPORTE = $rowOrdenTransporteActualizada->ID_ORDEN_TRANSPORTE AND OTA.CAMPO_FECHA_RELACIONADO = 'FECHA_ESTIMADA_LEVANTE_MATERIAL'";
            $bd->ExecSQL($sqlUpdate);
        endif;

        //SI HAN ACTUALIZADO LA FECHA_ESTIMADA_LEVANTE_MATERIAL
        if (($rowOrdenTransporteActualizada->FECHA_ENTREGA_FINAL_PLANIFICADA != $rowOrdenTransporte->FECHA_ENTREGA_FINAL_PLANIFICADA) && ($rowOrdenTransporteActualizada->FECHA_ENTREGA_FINAL_PLANIFICADA != NULL)):
            //ACTUALIZAMOS LOS AVISOS RELACIONADO CON ACCIONES QUE HACEN REFERENCIA A LA FECHA OBJETIVO
            $sqlUpdate = "UPDATE ORDEN_TRANSPORTE_ACCION_AVISO OTAA
                                INNER JOIN ORDEN_TRANSPORTE_ACCION OTA ON OTA.ID_ORDEN_TRANSPORTE_ACCION = OTAA.ID_ORDEN_TRANSPORTE_ACCION
                                SET OTAA.FECHA_RELACIONADA = '" . $rowOrdenTransporteActualizada->FECHA_ENTREGA_FINAL_PLANIFICADA . "'
                                WHERE OTAA.ID_ORDEN_TRANSPORTE = $rowOrdenTransporteActualizada->ID_ORDEN_TRANSPORTE AND OTA.CAMPO_FECHA_RELACIONADO = 'FECHA_ENTREGA_FINAL_PLANIFICADA'";
            $bd->ExecSQL($sqlUpdate);

            //ACTUALIZAMOS LAS LINEAS DE MATERIAL SI EXISTEN

            $sqlPedidoEntrada    = " SELECT ID_PEDIDO_ENTRADA FROM PEDIDO_ENTRADA WHERE ID_ORDEN_TRANSPORTE = $rowOrdenTransporteActualizada->ID_ORDEN_TRANSPORTE AND BAJA = 0";
            $resultPedidoEntrada = $bd->ExecSQL($sqlPedidoEntrada);

            while ($rowPedidoEntrada = $bd->SigReg($resultPedidoEntrada)):

                $sqlUpdate = "UPDATE PEDIDO_ENTRADA_LINEA
                                SET FECHA_ENTREGA = '" . $rowOrdenTransporteActualizada->FECHA_ENTREGA_FINAL_PLANIFICADA . "'
                                WHERE ID_PEDIDO_ENTRADA = '$rowPedidoEntrada->ID_PEDIDO_ENTRADA' AND BAJA = 0";
                $bd->ExecSQL($sqlUpdate);

            endwhile;
        endif;

    }

    /**
     * MIGRADO A CODEIGNITER (SI SE ACTUALIZA, ACTUALIZAR AMBAS comprobar_orden_fechas_construccion)
     * @param $idOrdenTransporte TRANSPORTE EN EL QUE COMPROBAR SI LAS FECHAS INTRODUCIDAS CUMPLEN UN ORDEN CRONOLOGICO
     * @return string POSIBLES ERRORES
     */
    function comprobarOrdenFechasConstruccion($idOrdenTransporte, $saltoLinea = "")
    {

        global $html;
        global $bd;
        global $administrador;
        global $auxiliar;

        //OBTENEMOS LA ORDEN DE TRANSPORTE CON LOS CAMPOS
        $rowOrdenTransporte = $this->getOrdenTransporteConstruccion($idOrdenTransporte);

        $camposError = "";

        //DECLARAMOS LAS FECHAS EN ORDEN
        $arrCamposFecha = array();

        if ($rowOrdenTransporte->TIPO_TRANSPORTE != "Terrestre"): //SOLAMENTE SI EL MEDIO DE TRANSPORTE NO ES TERRESTRE
            //$arrCamposFecha[] = array('ORDEN_CAMPO' => 1, 'NOMBRE_CAMPO' => 'FECHA_ENTREGA_DISPONIBLE_PROVEEDOR', 'NOMBRE_MOSTRAR' => 'Fecha Entrega Disponible');
            //$arrCamposFecha[] = array('ORDEN_CAMPO'=>  2, 'NOMBRE_CAMPO' => 'FECHA_OBJETIVO', 'NOMBRE_MOSTRAR' => 'Fecha Objetivo Entrega segun Incoterm');
            //$arrCamposFecha[] = array('ORDEN_CAMPO'=>  3, 'NOMBRE_CAMPO' => 'FECHA_OBJETIVO_SALIDA', 'NOMBRE_MOSTRAR' => 'ETD Objetivo');
            $arrCamposFecha[] = array('ORDEN_CAMPO' => 3, 'NOMBRE_CAMPO' => 'FECHA_ESTIMADA_SALIDA', 'NOMBRE_MOSTRAR' => 'ETD Planificada');
            $arrCamposFecha[] = array('ORDEN_CAMPO' => 3, 'NOMBRE_CAMPO' => 'FECHA_REAL_SALIDA', 'NOMBRE_MOSTRAR' => 'ETD Real');
            $arrCamposFecha[] = array('ORDEN_CAMPO' => 4, 'NOMBRE_CAMPO' => 'FECHA_ENVIO_DOCUMENTACION_BL', 'NOMBRE_MOSTRAR' => 'Fecha Envio Documentacion BL');
            $arrCamposFecha[] = array('ORDEN_CAMPO' => 5, 'NOMBRE_CAMPO' => 'FECHA_RECEPCION_DOCUMENTACION_BL', 'NOMBRE_MOSTRAR' => 'Fecha Recepcion Documentacion BL');
            //$arrCamposFecha[] = array('ORDEN_CAMPO'=>  6, 'NOMBRE_CAMPO' => 'FECHA_OBJETIVO_LLEGADA', 'NOMBRE_MOSTRAR' => 'ETA Objetivo');

            $arrCamposFecha[] = array('ORDEN_CAMPO' => 6, 'NOMBRE_CAMPO' => 'FECHA_ESTIMADA_LLEGADA', 'NOMBRE_MOSTRAR' => 'ETA Planificada');
            $arrCamposFecha[] = array('ORDEN_CAMPO' => 6, 'NOMBRE_CAMPO' => 'FECHA_REAL_LLEGADA', 'NOMBRE_MOSTRAR' => 'ETA Real');
            $arrCamposFecha[] = array('ORDEN_CAMPO' => 7, 'NOMBRE_CAMPO' => 'FECHA_ESTIMADA_RESOLUCION_ADUANA', 'NOMBRE_MOSTRAR' => 'Fecha Estimada Resolucion');
            $arrCamposFecha[] = array('ORDEN_CAMPO' => 7, 'NOMBRE_CAMPO' => 'FECHA_ESTIMADA_LEVANTE_MATERIAL', 'NOMBRE_MOSTRAR' => 'Fecha Estimada Levante Material');
            $arrCamposFecha[] = array('ORDEN_CAMPO' => 7, 'NOMBRE_CAMPO' => 'FECHA_REAL_LEVANTE_MATERIAL', 'NOMBRE_MOSTRAR' => 'Fecha Levante Material');
            $arrCamposFecha[] = array('ORDEN_CAMPO' => 7, 'NOMBRE_CAMPO' => 'FECHA_RECOGIDA_PUERTO', 'NOMBRE_MOSTRAR' => 'Fecha Salida Terminal');
        endif;

        if ($rowOrdenTransporte->FECHA_ENTREGA_REAL == NULL):
            $arrCamposFecha[] = array('ORDEN_CAMPO' => 8, 'NOMBRE_CAMPO' => 'FECHA_ENTREGA_FINAL_PLANIFICADA', 'NOMBRE_MOSTRAR' => 'Fecha Entrega Final Planificada');
        endif;
        //$arrCamposFecha[] = array('ORDEN_CAMPO'=>  9, 'NOMBRE_CAMPO' => 'FECHA_ENTREGA_PLANIFICADA', 'NOMBRE_MOSTRAR' => 'Fecha Entrega Final Objetivo');

        foreach ($arrCamposFecha as $arrDatosFechas):

            //OBTENEMOS LOS DATOS
            $ordenCampo       = $arrDatosFechas['ORDEN_CAMPO'];
            $nombreCampoFecha = $arrDatosFechas['NOMBRE_CAMPO'];
            $tituloCampoFecha = $arrDatosFechas['NOMBRE_MOSTRAR'];

            //SI ESTA EL VALOR INTRODUCIDO
            if ($rowOrdenTransporte->$nombreCampoFecha != NULL):

                //OBTENEMOS SOLO LA FECHA
                $fechaInicial = substr((string)$rowOrdenTransporte->$nombreCampoFecha, 0, 10);

                //RECORREMOS EL ARRAY PARA COMPROBAR QUE AQUELLOS CON ORDEN MAYOR, TIENEN FECHA POSTERIOR
                foreach ($arrCamposFecha as $arrDatosComprobar):

                    $ordenCampoComprobar       = $arrDatosComprobar['ORDEN_CAMPO'];
                    $nombreCampoFechaComprobar = $arrDatosComprobar['NOMBRE_CAMPO'];
                    $tituloCampoFechaComprobar = $arrDatosComprobar['NOMBRE_MOSTRAR'];

                    //SI EL ORDEN ES POSTERIOR COMPROBAR FECHA
                    if ($ordenCampoComprobar > $ordenCampo):

                        //SI LA FECHA ESTA INTRODUCIDA
                        if ($rowOrdenTransporte->$nombreCampoFechaComprobar != NULL):

                            //OBTENEMOS SOLO LA FECHA
                            $fechaComprobar = substr((string)$rowOrdenTransporte->$nombreCampoFechaComprobar, 0, 10);

                            //COMPROBAMOS SI LA FECHA ES POSTERIOR PARA GUARDAR EL ERROR
                            if ($fechaInicial > $fechaComprobar):

                                $camposError .= $auxiliar->traduce($tituloCampoFecha, $administrador->ID_IDIOMA) . " " . $auxiliar->traduce("no puede ser posterior a", $administrador->ID_IDIOMA) . " " . $auxiliar->traduce($tituloCampoFechaComprobar, $administrador->ID_IDIOMA);
                                if ($saltoLinea == "texto"):
                                    $camposError .= " \n";
                                elseif ($saltoLinea == "coma"):
                                    $camposError .= " , ";
                                else:
                                    $camposError .= " <br>";
                                endif;

                            endif;

                        endif;

                    endif;//SI EL ORDEN ES POSTERIOR COMPROBAR FECHA

                endforeach;//FIN RECORREMOS EL ARRAY PARA COMPROBAR QUE AQUELLOS CON ORDEN MAYOR, TIENEN FECHA POSTERIOR
            endif;

        endforeach;


        return $camposError;
    }

    /**
     * MIGRADO A CODEIGNITER (SI SE ACTUALIZA, ACTUALIZAR AMBAS get_nivel_estado_construccion)
     * @param $txEstado ESTADO DEL QUE SE QUIERE OBTENER EL NIVEL
     * @return NIVEL DEL ESTADO
     */
    function getNivelEstadoConstruccion($txEstado, $selMedioTransporte = "", $conAduanas = "", $conEmbarque = "")
    {

        $nivelEstado = 1;
        //ASIGNAMOS UN NIVEL A CADA ESTADO POSIBLE (SI SE MODIFICA HABRA QUE MODIFICAR LAS DEMAS FUNCIONES)
        if ($txEstado == "Creada"):
            return $nivelEstado;
        endif;
        $nivelEstado++;

        if ($conEmbarque != 1)://EMBARQUE PASA DIRECTO A PUERTO ORIGEN
            if ($txEstado == "Preparado en Proveedor"):
                return $nivelEstado;
            endif;
            $nivelEstado++;
        endif;

        //ESTADOS MARITIMOS Y AEREOS
        if ($selMedioTransporte == "Maritimo" || $selMedioTransporte == "Multimodal Maritimo" || $selMedioTransporte == "Aereo" || $selMedioTransporte == "Multimodal Aereo"):
            if ($conEmbarque != 1)://EMBARQUE PASA DIRECTO A PUERTO ORIGEN
                if ($txEstado == "Entregado a Forwarder"):
                    return $nivelEstado;
                endif;
                $nivelEstado++;
            endif;
            if ($txEstado == "Puerto Origen"):
                return $nivelEstado;
            endif;
            $nivelEstado++;
            if ($txEstado == "Transito Internacional"):
                return $nivelEstado;
            endif;
            $nivelEstado++;
            if ($txEstado == "Puerto Destino"):
                return $nivelEstado;
            endif;
            $nivelEstado++;
            if ($txEstado == "Liberado Aduana"):
                return $nivelEstado;
            endif;
            $nivelEstado++;
            if ($txEstado == "Transito Local"):
                return $nivelEstado;
            endif;
            $nivelEstado++;
        endif;

        //ESTADOS TERRESTRES
        if ($selMedioTransporte == "Terrestre"):
            if ($txEstado == "Transito Internacional"):
                return $nivelEstado;
            endif;
            $nivelEstado++;

            //SI TIENE ADUANAS
            if ($conAduanas == 1):
                if ($txEstado == "Aduana Destino"):
                    return $nivelEstado;
                endif;
                $nivelEstado++;
                if ($txEstado == "Liberado Aduana"):
                    return $nivelEstado;
                endif;
                $nivelEstado++;
                if ($txEstado == "Transito Local"):
                    return $nivelEstado;
                endif;
                $nivelEstado++;
            endif;
        endif;

        if ($txEstado == "Entregado"):
            return $nivelEstado;
        endif;
        $nivelEstado++;
        if ($txEstado == "Recepcionado"):
            return $nivelEstado;
        endif;

        //SI NO HA ENTRADO DEVOLVEMOS 100 (SERA UN ESTADO QUE NO APLICA PARA ESE TRANSPORTE)
        return 100;
    }


    /**
     * MIGRADO A CODEIGNITER (SI SE ACTUALIZA, ACTUALIZAR AMBAS get_siguiente_estado_construccion)
     * @param $txEstado ESTADO DEL QUE SE QUIERE OBTENER EL SIGUIENTE
     * @return SIGUIENTE ESTADO
     */
    function getSiguienteEstadoConstruccion($txEstado, $selMedioTransporte = "", $conAduanas = "", $conEmbarque = "")
    {

        if ($txEstado == "Creada"):
            if ($conEmbarque == 1):
                return "Puerto Origen";
            else:
                return "Preparado en Proveedor";
            endif;
        endif;

        if ($txEstado == "Preparado en Proveedor"):
            //ESTADOS MARITIMOS Y AEREOS
            if ($selMedioTransporte == "Maritimo" || $selMedioTransporte == "Multimodal Maritimo" || $selMedioTransporte == "Aereo" || $selMedioTransporte == "Multimodal Aereo"):
                return "Entregado a Forwarder";
            else:
                return "Transito Internacional";
            endif;
        endif;

        if ($txEstado == "Aduana Destino")://ESTADO TERRESTRE CON ADUANAS
            return "Liberado Aduana";
        endif;


        if ($txEstado == "Entregado a Forwarder")://ESTADO MARITIMO/AEREO
            return "Puerto Origen";
        endif;

        if ($txEstado == "Puerto Origen")://ESTADO MARITIMO/AEREO
            return "Transito Internacional";
        endif;

        if ($txEstado == "Transito Internacional"):
            if ($selMedioTransporte == "Terrestre"):
                //DEPENDIENDO DE LA ADUANA
                if ($conAduanas == 1):
                    return "Aduana Destino";
                else:
                    return "Entregado";
                endif;

            else://MARITIMO/AEREO
                return "Puerto Destino";
            endif;
        endif;

        if ($txEstado == "Puerto Destino")://ESTADO MARITIMO/AEREO
            return "Liberado Aduana";
        endif;

        if ($txEstado == "Liberado Aduana"):
            return "Transito Local";
        endif;

        if ($txEstado == "Transito Local"):
            return "Entregado";
        endif;

        if ($txEstado == "Entregado"):
            return "Recepcionado";
        endif;

        return false;
    }

    /**
     * MIGRADO A CODEIGNITER (SI SE ACTUALIZA, ACTUALIZAR AMBAS get_array_campos_estado_construccion)
     * @param $idOrdenTransporte
     * @return  ARRAY CON LOS CAMPOS IMPLICADOS
     * Devuelve un array con los campos obligatorios hasta el nivel indicado (O solo los implicados con el estado si viene indicado en $soloEstadoActual)
     */
    function getArrayCamposEstadoConstruccion($idOrdenTransporte, $soloEstadoActual = "")
    {
        global $html;
        global $bd;
        global $administrador;
        global $auxiliar;

        //OBTENEMOS LA ORDEN DE TRANSPORTE CON LOS CAMPOS
        $rowOrdenTransporte = $this->getOrdenTransporteConstruccion($idOrdenTransporte);

        //OBTENEMOS EL NIVEL DEL ESTADO
        $nivelEstado = $this->getNivelEstadoConstruccion($rowOrdenTransporte->ESTADO, $rowOrdenTransporte->TIPO_TRANSPORTE, $rowOrdenTransporte->TRANSPORTE_CON_ADUANAS, $rowOrdenTransporte->CON_EMBARQUE_GC);
        $html->PagErrorCondicionado($nivelEstado, "==", "", "EstadoNoContemplado");


        //DECLARAMOS EL ARRAY
        $arrayCampos = array();

        if ($nivelEstado > $this->getNivelEstadoConstruccion("Creada", $rowOrdenTransporte->TIPO_TRANSPORTE, $rowOrdenTransporte->TRANSPORTE_CON_ADUANAS, $rowOrdenTransporte->CON_EMBARQUE_GC))://CREADA (MIENTRAS ESTE CREADA SI QUE SE PODRAN MODIFICAR, POR ESO PONEMOS SOLO >)

            $arrayCampos['Proyecto']['Campo']                     = 'ID_CENTRO_FISICO_PROYECTO';
            $arrayCampos['Contenido planificado']['Campo']        = 'ID_TIPO_MATERIAL';
            $arrayCampos['Lugar Entrega Final']['Campo']          = 'ID_DIRECCION_ENTREGA_FINAL';
            $arrayCampos['Fecha Entrega Final Objetivo']['Campo'] = 'FECHA_ENTREGA_PLANIFICADA';
            $arrayCampos['Logistica']['Campo']                    = 'TIPO_LOGISTICA';
            $arrayCampos['Medio Transporte']['Campo']             = 'TIPO_TRANSPORTE';
            $arrayCampos['Aduana']['Campo']                       = 'TRANSPORTE_CON_ADUANAS';
            $arrayCampos['Carga']['Campo']                        = 'CARGA';
            $arrayCampos['Tipo Contenedor']['Campo']              = 'ID_CONTENEDOR_EXPORTACION';
            $arrayCampos['Proveedor del Material']['Campo']       = 'ID_PROVEEDOR';
            $arrayCampos['Forwarder']['Campo']                    = 'ID_PROVEEDOR_FORWARDER';
            //CAMPOS PARA NO TERRESTRE
            if ($rowOrdenTransporte->TIPO_TRANSPORTE != "Terrestre"):
                $arrayCampos['Tipo Envio BL']['Campo'] = 'TIPO_BL';
                $arrayCampos['ETD Objetivo']['Campo']  = 'FECHA_OBJETIVO_SALIDA';
                $arrayCampos['ETA Objetivo']['Campo']  = 'FECHA_OBJETIVO_LLEGADA';
            endif;
            $arrayCampos['Incoterm']['Campo'] = 'ID_INCOTERM';
            //PARA OTS CON ADUANAS
            if ($rowOrdenTransporte->TRANSPORTE_CON_ADUANAS == 1):
                $arrayCampos['Empresa Agente Aduanal']['Campo'] = 'ID_PROVEEDOR_AGENTE_ADUANAL';
            endif;
            $arrayCampos['Punto Entrega segun Incoterm']['Campo']          = 'ID_DIRECCION_ENTREGA_INCOTERM';
            $arrayCampos['Fecha Objetivo Entrega segun Incoterm']['Campo'] = 'FECHA_OBJETIVO';


            if ($rowOrdenTransporte->TIPO_TRANSPORTE == "Maritimo" || $rowOrdenTransporte->ESTADO_MATERIAL == "Multimodal Maritimo"):
                $arrayCampos['Puerto Origen']['Campo']  = 'ID_PUERTO_ORIGEN';
                $arrayCampos['Puerto Destino']['Campo'] = 'ID_PUERTO_DESTINO';

            elseif ($rowOrdenTransporte->TIPO_TRANSPORTE == "Aereo" || $rowOrdenTransporte->ESTADO_MATERIAL == "Multimodal Aereo"):
                $arrayCampos['Aeropuerto Origen']['Campo']  = 'ID_PUERTO_ORIGEN';
                $arrayCampos['Aeropuerto Destino']['Campo'] = 'ID_PUERTO_DESTINO';
            endif;
        endif;

        if ($nivelEstado >= $this->getNivelEstadoConstruccion("Preparado en Proveedor", $rowOrdenTransporte->TIPO_TRANSPORTE, $rowOrdenTransporte->TRANSPORTE_CON_ADUANAS, $rowOrdenTransporte->CON_EMBARQUE_GC))://PREPARADO EN PROVEEDOR
            $arrayCampos['Fecha Entrega Disponible']['Campo'] = 'FECHA_ENTREGA_DISPONIBLE_PROVEEDOR';
            $arrayCampos['Fecha Entrega Disponible']['Rol']   = 'Proveedor del Material';

            //NO AFECTAN A EMBARQUES
            if ($rowOrdenTransporte->CON_EMBARQUE_GC != 1):
                $arrayCampos['Descripcion Contenido']['Campo'] = 'ID_TIPO_MATERIAL_PROVEEDOR';
                $arrayCampos['Descripcion Contenido']['Rol']   = 'Proveedor del Material';

                $arrayCampos['Punto Entrega Material']['Campo'] = 'ID_DIRECCION_ENTREGA_PROVEEDOR';
                $arrayCampos['Punto Entrega Material']['Rol']   = ($rowOrdenTransporte->RECOGIDA_EN_PROVEEDOR == 1 ? 'Proveedor del Material' : 'Forwarder');

                $arrayCampos['Persona de Contacto Punto Entrega']['Campo'] = 'ID_DIRECCION_ENTREGA_PROVEEDOR_CONTACTO';
                $arrayCampos['Persona de Contacto Punto Entrega']['Rol']   = ($rowOrdenTransporte->RECOGIDA_EN_PROVEEDOR == 1 ? 'Proveedor del Material' : 'Forwarder');
            endif;

        endif;

        if ($nivelEstado >= $this->getNivelEstadoConstruccion("Entregado a Forwarder", $rowOrdenTransporte->TIPO_TRANSPORTE, $rowOrdenTransporte->TRANSPORTE_CON_ADUANAS, $rowOrdenTransporte->CON_EMBARQUE_GC))://ENTREGADO A FORWARDER
            $arrayCampos['Envio Contenedor Vacio a Recogida']['Campo'] = 'ENVIO_CONTENEDOR_VACIO_RECOGIDA';
            $arrayCampos['Envio Contenedor Vacio a Recogida']['Rol']   = 'Forwarder';

            $arrayCampos['Nº Packing List']['Campo'] = 'NUMERO_PACKING_LIST';
            $arrayCampos['Nº Packing List']['Rol']   = 'Proveedor del Material';

            //BUSCAMOS EL CONTENEDOR
            $rowContenedor = false;
            if ($rowOrdenTransporte->ID_CONTENEDOR_EXPORTACION != NULL):
                $rowContenedor = $bd->VerReg("CONTENEDOR_EXPORTACION", "ID_CONTENEDOR_EXPORTACION", $rowOrdenTransporte->ID_CONTENEDOR_EXPORTACION);
            endif;

            if ($rowContenedor == false || $rowContenedor->REQUIERE_PRECINTO == 1):
                $arrayCampos['Sello Contenedor']['Campo'] = 'SELLO_CONTENEDOR';
                $arrayCampos['Sello Contenedor']['Rol']   = 'Proveedor del Material';

                $arrayCampos['Nº Contenedor']['Campo'] = 'NUMERO_CONTENEDOR';
                $arrayCampos['Nº Contenedor']['Rol']   = 'Proveedor del Material';
            endif;

            $arrayCampos['Packing List']['Campo'] = 'ADJUNTO_PACKING_PROVEEDOR';
            $arrayCampos['Packing List']['Rol']   = 'Proveedor del Material';

            /*$arrayCampos['Certificado Origen']['Campo'] = 'ADJUNTO_CERTIFICADO_ORIGEN';
            $arrayCampos['Certificado Origen']['Rol']   = 'Proveedor del Material';

            $arrayCampos['Certificado Fitosanitario']['Campo'] = 'ADJUNTO_CERTIFICADO_FITOSANITARIO';
            $arrayCampos['Certificado Fitosanitario']['Rol']   = 'Proveedor del Material';*/

            $arrayCampos['Estado Material']['Campo'] = 'ESTADO_MATERIAL';
            $arrayCampos['Estado Material']['Rol']   = 'Proveedor del Material';

            $arrayCampos['Tipo Contenido']['Campo'] = 'TIPO_CONTENIDO';
            $arrayCampos['Tipo Contenido']['Rol']   = 'Proveedor del Material';

            //GESTION DE FACTURAS PROVEEDOR ( LOS CAMPOS SON OBLIGATORIOS DEPENDIENDO DEL VALOR DE ESTADO_MATERIAL Y TIPO_CONTENIDO)
            if (($rowOrdenTransporte->ESTADO_MATERIAL == "Usado" || $rowOrdenTransporte->ESTADO_MATERIAL == "Ambos") && ($rowOrdenTransporte->TIPO_CONTENIDO == "Componente" || $rowOrdenTransporte->TIPO_CONTENIDO == "Ambos")):
                $arrayCampos['Componente Usado']['Campo'] = 'NUMERO_FACTURA_COMPONENTE_USADO';
                $arrayCampos['Componente Usado']['Rol']   = 'Proveedor del Material';

                $arrayCampos['Adjunto Componente Usado']['Campo'] = 'ADJUNTO_FACTURA_COMPONENTE_USADO';
                $arrayCampos['Adjunto Componente Usado']['Rol']   = 'Proveedor del Material';
            endif;
            if (($rowOrdenTransporte->ESTADO_MATERIAL == "Nuevo" || $rowOrdenTransporte->ESTADO_MATERIAL == "Ambos") && ($rowOrdenTransporte->TIPO_CONTENIDO == "Componente" || $rowOrdenTransporte->TIPO_CONTENIDO == "Ambos")):
                $arrayCampos['Componente Nuevo']['Campo'] = 'NUMERO_FACTURA_COMPONENTE_NUEVO';
                $arrayCampos['Componente Nuevo']['Rol']   = 'Proveedor del Material';

                $arrayCampos['Adjunto Componente Nuevo']['Campo'] = 'ADJUNTO_FACTURA_COMPONENTE_NUEVO';
                $arrayCampos['Adjunto Componente Nuevo']['Rol']   = 'Proveedor del Material';
            endif;
            if (($rowOrdenTransporte->ESTADO_MATERIAL == "Usado" || $rowOrdenTransporte->ESTADO_MATERIAL == "Ambos") && ($rowOrdenTransporte->TIPO_CONTENIDO == "Util" || $rowOrdenTransporte->TIPO_CONTENIDO == "Ambos")):
                $arrayCampos['Util Usado']['Campo'] = 'NUMERO_FACTURA_UTIL_USADO';
                $arrayCampos['Util Usado']['Rol']   = 'Proveedor del Material';

                $arrayCampos['Adjunto Util Usado']['Campo'] = 'ADJUNTO_FACTURA_UTIL_USADO';
                $arrayCampos['Adjunto Util Usado']['Rol']   = 'Proveedor del Material';
            endif;
            if (($rowOrdenTransporte->ESTADO_MATERIAL == "Nuevo" || $rowOrdenTransporte->ESTADO_MATERIAL == "Ambos") && ($rowOrdenTransporte->TIPO_CONTENIDO == "Util" || $rowOrdenTransporte->TIPO_CONTENIDO == "Ambos")):
                $arrayCampos['Util Nuevo']['Campo'] = 'NUMERO_FACTURA_UTIL_NUEVO';
                $arrayCampos['Util Nuevo']['Rol']   = 'Proveedor del Material';

                $arrayCampos['Adjunto Util Nuevo']['Campo'] = 'ADJUNTO_FACTURA_UTIL_NUEVO';
                $arrayCampos['Adjunto Util Nuevo']['Rol']   = 'Proveedor del Material';
            endif;
        endif;

        if ($nivelEstado >= $this->getNivelEstadoConstruccion("Puerto Origen", $rowOrdenTransporte->TIPO_TRANSPORTE, $rowOrdenTransporte->TRANSPORTE_CON_ADUANAS, $rowOrdenTransporte->CON_EMBARQUE_GC))://PUERTO ORIGEN (No tiene ninguno especifico)

        endif;

        if ($nivelEstado >= $this->getNivelEstadoConstruccion("Transito Internacional", $rowOrdenTransporte->TIPO_TRANSPORTE, $rowOrdenTransporte->TRANSPORTE_CON_ADUANAS, $rowOrdenTransporte->CON_EMBARQUE_GC))://TRANSITO INTERNACIONAL

            /*$arrayCampos['ETA Objetivo']['Campo'] = 'FECHA_OBJETIVO_LLEGADA';
            $arrayCampos['ETA Objetivo']['Rol']   = 'Forwarder';*/

            //CAMPOS PARA NO TERRESTRE
            if ($rowOrdenTransporte->TIPO_TRANSPORTE != "Terrestre"):
                $arrayCampos['Booking']['Campo'] = 'NUMERO_BOOKING';
                $arrayCampos['Booking']['Rol']   = 'Forwarder';

                $arrayCampos['Nº BL Master']['Campo'] = 'NUMERO_BL';
                $arrayCampos['Nº BL Master']['Rol']   = 'Forwarder';

                $arrayCampos['Documento BL Master']['Campo'] = 'ADJUNTO_BL';
                $arrayCampos['Documento BL Master']['Rol']   = 'Forwarder';

                if ($rowOrdenTransporte->TIPO_DOCUMENTO_BL == "Master & House"):
                    $arrayCampos['Nº BL House']['Campo'] = 'NUMERO_BL_HOUSE';
                    $arrayCampos['Nº BL House']['Rol']   = 'Forwarder';

                    $arrayCampos['Documento BL House']['Campo'] = 'ADJUNTO_BL_HOUSE';
                    $arrayCampos['Documento BL House']['Rol']   = 'Forwarder';
                endif;

                //DATOS TRANSITO, COMPROBAREMOS LA TABLA ORDEN_TRANSPORTE_TRANSBORDO
                if ($rowOrdenTransporte->TIPO_TRANSPORTE == "Maritimo" || $rowOrdenTransporte->TIPO_TRANSPORTE == "Multimodal Maritimo"):
                    $arrayCampos['Datos Transito']['Campo'] = 'ORDEN_TRANSPORTE_TRANSBORDO';
                    $arrayCampos['Datos Transito']['Rol']   = 'Forwarder';
                    $arrayCampos['Datos Transito']['Tipo']  = 'ExcepcionBuques';
                elseif ($rowOrdenTransporte->TIPO_TRANSPORTE == "Aereo" || $rowOrdenTransporte->TIPO_TRANSPORTE == "Multimodal Aereo"):

                    $arrayCampos['Datos Transito']['Campo'] = 'ORDEN_TRANSPORTE_TRANSBORDO';
                    $arrayCampos['Datos Transito']['Rol']   = 'Forwarder';
                    $arrayCampos['Datos Transito']['Tipo']  = 'ExcepcionAviones';
                endif;
            endif;

        endif;

        if ($nivelEstado >= $this->getNivelEstadoConstruccion("Puerto Destino", $rowOrdenTransporte->TIPO_TRANSPORTE, $rowOrdenTransporte->TRANSPORTE_CON_ADUANAS, $rowOrdenTransporte->CON_EMBARQUE_GC))://PUERTO DESTINO

            $arrayCampos['ETA Planificada']['Campo'] = 'FECHA_ESTIMADA_LLEGADA';
            $arrayCampos['ETA Planificada']['Rol']   = 'Forwarder';

            /*$arrayCampos['Importe Flete']['Campo'] = 'IMPORTE_FLETE';
            $arrayCampos['Importe Flete']['Rol']   = 'Forwarder';

            $arrayCampos['Moneda Importe Flete']['Campo'] = 'ID_MONEDA_FLETE';
            $arrayCampos['Moneda Importe Flete']['Rol']   = 'Forwarder';

            $arrayCampos['Importe Seguro']['Campo'] = 'IMPORTE_SEGURO';
            $arrayCampos['Importe Seguro']['Rol']   = 'Forwarder';

            $arrayCampos['Moneda Importe Seguro']['Campo'] = 'ID_MONEDA_SEGURO';
            $arrayCampos['Moneda Importe Seguro']['Rol']   = 'Forwarder';*/
        endif;

        if ($nivelEstado >= $this->getNivelEstadoConstruccion("Liberado Aduana", $rowOrdenTransporte->TIPO_TRANSPORTE, $rowOrdenTransporte->TRANSPORTE_CON_ADUANAS, $rowOrdenTransporte->CON_EMBARQUE_GC))://LIBERADO ADUANA

            $arrayCampos['Aceptacion Documentacion']['Campo'] = 'ACEPTACION_DOCUMENTACION_EN_ADUANA';
            $arrayCampos['Aceptacion Documentacion']['Rol']   = 'Agente Aduanal';

            $arrayCampos['Importe Arancel']['Campo'] = 'IMPORTE_ARANCEL';
            $arrayCampos['Importe Arancel']['Rol']   = 'Agente Aduanal';

            $arrayCampos['Moneda Importe Arancel']['Campo'] = 'ID_MONEDA_ARANCEL';
            $arrayCampos['Moneda Importe Arancel']['Rol']   = 'Agente Aduanal';

            $arrayCampos['Importe IVA Aduana']['Campo'] = 'IMPORTE_IVA_ADUANA';
            $arrayCampos['Importe IVA Aduana']['Rol']   = 'Agente Aduanal';

            $arrayCampos['Moneda Importe IVA Aduana']['Campo'] = 'ID_MONEDA_IVA_ADUANA';
            $arrayCampos['Moneda Importe IVA Aduana']['Rol']   = 'Agente Aduanal';

            $arrayCampos['Importe Pdte Aprovisionar']['Campo'] = 'IMPORTE_APROVISIONADO_TESORERIA';
            $arrayCampos['Importe Pdte Aprovisionar']['Rol']   = 'Tesoreria';

            $arrayCampos['Operador Transporte Inland Destino']['Campo'] = 'ID_AGENCIA';
            $arrayCampos['Operador Transporte Inland Destino']['Rol']   = 'Forwarder';

            $arrayCampos['Semaforo Aduana']['Campo'] = 'SEMAFORO_ADUANA';
            $arrayCampos['Semaforo Aduana']['Rol']   = 'Agente Aduanal';

            //SI EL SEMAFORO ES ROJO O AMARILLO, SE PIDE FECHA_ESTIMADA_RESOLUCION_ADUANA
            if ($rowOrdenTransporte->SEMAFORO_ADUANA == "Rojo" || $rowOrdenTransporte->SEMAFORO_ADUANA == "Naranja"):
                $arrayCampos['Fecha Estimada Resolucion']['Campo'] = 'FECHA_ESTIMADA_RESOLUCION_ADUANA';
                $arrayCampos['Fecha Estimada Resolucion']['Rol']   = 'Agente Aduanal';
            endif;

            $arrayCampos['Fecha Estimada Levante Material']['Campo'] = 'FECHA_ESTIMADA_LEVANTE_MATERIAL';
            $arrayCampos['Fecha Estimada Levante Material']['Rol']   = 'Agente Aduanal';

            $arrayCampos['Lugar Levante Material']['Campo'] = 'ID_DIRECCION_LEVANTE_MATERIAL';
            $arrayCampos['Lugar Levante Material']['Rol']   = 'Agente Aduanal';

            $arrayCampos['Importe Abonado Aduana']['Campo'] = 'IMPORTE_ABONADO_ADUANA';
            $arrayCampos['Importe Abonado Aduana']['Rol']   = 'Agente Aduanal';

            $arrayCampos['Moneda Importe Abonado Aduana']['Campo'] = 'ID_MONEDA_ABONADO_ADUANA';
            $arrayCampos['Moneda Importe Abonado Aduana']['Rol']   = 'Agente Aduanal';

            $arrayCampos['Justificante Abono Aduana']['Campo'] = 'ADJUNTO_ABONACION_ADUANA';
            $arrayCampos['Justificante Abono Aduana']['Rol']   = 'Agente Aduanal';

            $arrayCampos['DUA Importacion']['Campo'] = 'ADJUNTO_DUA_IMPORTACION';
            $arrayCampos['DUA Importacion']['Rol']   = 'Agente Aduanal';
        endif;

        if ($nivelEstado >= $this->getNivelEstadoConstruccion("Transito Local", $rowOrdenTransporte->TIPO_TRANSPORTE, $rowOrdenTransporte->TRANSPORTE_CON_ADUANAS, $rowOrdenTransporte->CON_EMBARQUE_GC))://TRANSITO LOCAL
            $arrayCampos['Entrega a Transportista Inland']['Campo'] = 'ENTREGA_A_TRANSPORTISTA_INLAND';
            $arrayCampos['Entrega a Transportista Inland']['Rol']   = 'Agente Aduanal';
        endif;

        if ($nivelEstado >= $this->getNivelEstadoConstruccion("Entregado", $rowOrdenTransporte->TIPO_TRANSPORTE, $rowOrdenTransporte->TRANSPORTE_CON_ADUANAS, $rowOrdenTransporte->CON_EMBARQUE_GC))://ENTREGADO
            $arrayCampos['Fecha Entrega Final Planificada']['Campo'] = 'FECHA_ENTREGA_FINAL_PLANIFICADA';
            $arrayCampos['Fecha Entrega Final Planificada']['Rol']   = 'Transp. InLand';
            $arrayCampos['Fecha Entrega Final Real']['Campo']        = 'FECHA_ENTREGA_REAL';
            $arrayCampos['Fecha Entrega Final Real']['Rol']          = 'Transp. InLand';
        endif;

        if ($nivelEstado >= $this->getNivelEstadoConstruccion("Recepcionado", $rowOrdenTransporte->TIPO_TRANSPORTE, $rowOrdenTransporte->TRANSPORTE_CON_ADUANAS, $rowOrdenTransporte->CON_EMBARQUE_GC))://RECEPCIONADO
            $arrayCampos['Posicion Entrega Destino']['Campo'] = 'LUGAR_APROVISIONAMIENTO_CONSTRUCCION';
        endif;


        return $arrayCampos;
    }

    /**
     * @param $rowOrdenTransporte
     * @return string
     */
    function ComprobarCamposObligatoriosConstruccion($rowOrdenTransporte)
    {
        global $html;
        global $bd;
        global $administrador;
        global $auxiliar;

        $camposError = "";


        //OBTENEMOS LOS CAMPOS OBLIGATORIOS PARA EL NIVEL ACTUAL
        $arrayCampos = $this->getArrayCamposEstadoConstruccion($rowOrdenTransporte->ID_ORDEN_TRANSPORTE);

        //SI NOS HA DEVUELTO CAMPOS
        if (count((array)$arrayCampos) > 0):

            //RECORREMOS LOS CAMPOS PARA DETECTAR SI NO SE HA INTRODUCIDO VALOR
            foreach ($arrayCampos as $nombreCampo => $arrayDatosCampo):

                $nombreCampoTabla = $arrayDatosCampo['Campo'];
                $rolImplicado     = $arrayDatosCampo['Rol'];

                //region AQUI PONEMOS EXCEPCIONES

                //CAMPOS NO NULOS QUE PUEDEN TRAER VALOR QUE CONSIDERAMOS NULO (EJ importe 0....)
                if (($nombreCampoTabla == "RESERVA_MEDIOS_REALIZADA") || ($nombreCampoTabla == "ACEPTACION_DOCUMENTACION_EN_ADUANA") || ($nombreCampoTabla == "ENVIO_CONTENEDOR_VACIO_RECOGIDA") || ($nombreCampoTabla == "ENTREGA_A_TRANSPORTISTA_INLAND") || ($nombreCampoTabla == "IMPORTE_COMPONENTE_USADO") || ($nombreCampoTabla == "IMPORTE_COMPONENTE_NUEVO") || ($nombreCampoTabla == "IMPORTE_UTIL_USADO") || ($nombreCampoTabla == "IMPORTE_EXPORTACION_TEMPORAL") || ($nombreCampoTabla == "IMPORTE_FLETE") || ($nombreCampoTabla == "IMPORTE_SEGURO") || ($nombreCampoTabla == "IMPORTE_ABONADO_ADUANA") || ($nombreCampoTabla == "IMPORTE_APROVISIONADO_TESORERIA")):

                    if ($rowOrdenTransporte->$nombreCampoTabla == 0):
                        $camposError .= $auxiliar->traduce($nombreCampo, $administrador->ID_IDIOMA) . " (" . $auxiliar->traduce($rolImplicado, $administrador->ID_IDIOMA) . ") <br>";
                    endif;

                    //CONTINUAMOS CON EL SIGUIENTE CAMPO
                    continue;
                endif;

                //EN EL CASO DEL IMPORTE ARANCEL E IMPORTE IVA, SIRVE CON QUE UNO DE ELLOS ESTE RELLENO
                if (($nombreCampoTabla == "IMPORTE_ARANCEL") || ($nombreCampoTabla == "IMPORTE_IVA_ADUANA")):
                    if (($rowOrdenTransporte->IMPORTE_ARANCEL == 0) && ($rowOrdenTransporte->IMPORTE_IVA_ADUANA == 0)):
                        $camposError .= $auxiliar->traduce($nombreCampo, $administrador->ID_IDIOMA) . " (" . $auxiliar->traduce($rolImplicado, $administrador->ID_IDIOMA) . ") <br>";
                    endif;
                    //CONTINUAMOS CON EL SIGUIENTE CAMPO
                    continue;
                endif;

                //PARA ARANCEL E IVA SOLO HARA FALTA QUE ESTE RELLENA LA MONEDA DEL IMPORTE RELLENADO
                if ($nombreCampoTabla == "ID_MONEDA_ARANCEL"):
                    if (($rowOrdenTransporte->IMPORTE_ARANCEL > 0) && ($rowOrdenTransporte->ID_MONEDA_ARANCEL == "")):
                        $camposError .= $auxiliar->traduce($nombreCampo, $administrador->ID_IDIOMA) . " (" . $auxiliar->traduce($rolImplicado, $administrador->ID_IDIOMA) . ") <br>";
                    endif;
                    //CONTINUAMOS CON EL SIGUIENTE CAMPO
                    continue;
                endif;
                if ($nombreCampoTabla == "ID_MONEDA_IVA_ADUANA"):
                    if (($rowOrdenTransporte->IMPORTE_IVA_ADUANA > 0) && ($rowOrdenTransporte->ID_MONEDA_IVA_ADUANA == "")):
                        $camposError .= $auxiliar->traduce($nombreCampo, $administrador->ID_IDIOMA) . " (" . $auxiliar->traduce($rolImplicado, $administrador->ID_IDIOMA) . ") <br>";
                    endif;
                    //CONTINUAMOS CON EL SIGUIENTE CAMPO
                    continue;
                endif;


                //EXCEPCION DE DATOS DE TRANSITO, COMPROBAMOS LA TABLA ORDEN_TRANSPORTE_TRANSBORDO
                if ($arrayDatosCampo['Tipo'] == "ExcepcionBuques"):

                    //COMPROBAMOS QUE AL MENOS TIENE UN BUQUE
                    $numeroTransbordos = $bd->NumRegsTabla("ORDEN_TRANSPORTE_TRANSBORDO", "ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND BAJA = 0");

                    if ($numeroTransbordos == 0):
                        $camposError .= $auxiliar->traduce("Asignar a Buque", $administrador->ID_IDIOMA) . " (" . $auxiliar->traduce($rolImplicado, $administrador->ID_IDIOMA) . ") <br>";
                    endif;

                    //CONTINUAMOS CON EL SIGUIENTE CAMPO
                    continue;

                elseif ($arrayDatosCampo['Tipo'] == "ExcepcionAviones"):

                    //COMPROBAMOS QUE AL MENOS TIENE UN BUQUE
                    $numeroEscalas = $bd->NumRegsTabla("ORDEN_TRANSPORTE_TRANSBORDO", "ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND BAJA = 0");

                    if ($numeroEscalas == 0):
                        $camposError .= $auxiliar->traduce("Asignar a Avion", $administrador->ID_IDIOMA) . " (" . $auxiliar->traduce($rolImplicado, $administrador->ID_IDIOMA) . ") <br>";
                    endif;

                    //CONTINUAMOS CON EL SIGUIENTE CAMPO
                    continue;
                endif;

                //endregion FIN EXCEPCIONES

                //SI NO ESTA RELLENO, GUARDAMOS EL CAMPO
                if ($rowOrdenTransporte->$nombreCampoTabla == ""):
                    $camposError .= $auxiliar->traduce($nombreCampo, $administrador->ID_IDIOMA) . " (" . $auxiliar->traduce($rolImplicado, $administrador->ID_IDIOMA) . ") <br>";
                endif;

            endforeach;
        endif;

        return $camposError;
    }

    /**
     * MIGRADO A CODEIGNITER (SI SE ACTUALIZA, ACTUALIZAR AMBAS comprobar_modificacion_campos_construccion)
     * @param $rowOrdenTransporte
     * @param $rowOrdenTransporteActualizada
     * COMPROBAMOS SI EL VALOR DE LOS CAMPOS OBLIGATORIOS PARA ESTADOS ANTERIORES AL ACTUAL HA CAMBIADO
     */
    function ComprobarModificacionCamposConstruccion($rowOrdenTransporte, $rowOrdenTransporteActualizada, $saltoLinea = "")
    {
        global $html;
        global $bd;
        global $administrador;
        global $auxiliar;

        $camposError = "";

        //OBTENEMOS LOS CAMPOS OBLIGATORIOS PARA EL NIVEL ACTUAL
        $arrayCampos = $this->getArrayCamposEstadoConstruccion($rowOrdenTransporte->ID_ORDEN_TRANSPORTE);

        //SI NOS HA DEVUELTO CAMPOS
        if (count((array)$arrayCampos) > 0):

            //RECORREMOS LOS CAMPOS PARA DETECTAR SI HA CAMBIADO EL VALOR
            foreach ($arrayCampos as $nombreCampo => $arrayDatosCampo):

                $nombreCampoTabla = $arrayDatosCampo['Campo'];

                //AQUI PONEMOS EXCEPCIONES
                //CON LOS ADJUNTOS HACEMOS EXCEPCION
                if (strpos((string)$nombreCampoTabla, 'ADJUNTO_') !== false):
                    continue;
                endif;

                //EXCEPCION DE DATOS DE TRANSITO, COMPROBAMOS LA TABLA ORDEN_TRANSPORTE_TRANSBORDO
                if ($arrayDatosCampo['Tipo'] == "ExcepcionBuques"):

                    //CONTINUAMOS CON EL SIGUIENTE CAMPO
                    continue;

                elseif ($arrayDatosCampo['Tipo'] == "ExcepcionAviones"):

                    //CONTINUAMOS CON EL SIGUIENTE CAMPO
                    continue;
                elseif ($arrayDatosCampo['Tipo'] == "ExcepcionETA"):

                    //CONTINUAMOS CON EL SIGUIENTE CAMPO
                    continue;
                elseif ($arrayDatosCampo['Tipo'] == "ExcepcionTransbordo"):

                    //CONTINUAMOS CON EL SIGUIENTE CAMPO
                    continue;
                endif;

                //SI HA CAMBIADO DE VALOR Y NO ESTABA RELLENO (PUEDE QUE EL CAMPO SE HAYA AÑADIDO DESPUES), GUARDAMOS EL CAMPO
                if (($rowOrdenTransporte->$nombreCampoTabla != "") && ($rowOrdenTransporte->$nombreCampoTabla != $rowOrdenTransporteActualizada->$nombreCampoTabla)):
                    $camposError .= $auxiliar->traduce($nombreCampo, $administrador->ID_IDIOMA);
                    if ($saltoLinea == "texto"):
                        $camposError .= " \n";
                    elseif
                    ($saltoLinea == "coma"
                    ):
                        $camposError .= " , ";
                    else:
                        $camposError .= " <br>";
                    endif;
                endif;

            endforeach;
        endif;

        return $camposError;
    }

    /**
     * MIGRADO A CODEIGNITER (SI SE ACTUALIZA, ACTUALIZAR AMBAS get_array_campos_accion_construccion)
     * @param $nombreAccion
     * @param $mostrarCamposOpcionales PARA MOSTRAR LOS CAMPOS OPCIONALES. SI NO VIENE RELLENO SOLO MUESTRA CUANDO SON OBLIGATORIOS (Por ejemplo dependiendo de ESTADO_MATERIAL se piden unas FACTURAS u otras)
     * @return  ARRAY CON LOS CAMPOS IMPLICADOS
     * Devuelve un array con los campos obligatorios para dar por vencida una accion(Campo: CAMPO EN BBDD, Rol: ROL DEL PROVEEDOR, Tipo: TIPO DE INPUT, Variable: NOMBRE DEL INPUT (comprobar como funciona cada tipo en avisos_construccion/accion.php),  [Tabla] :opcional, si el valor es "A" es que el campo afecta a la tabla ORDEN_TRANSPORTE_AMPLIACION
     */
    function getArrayCamposAccionConstruccion($idOrdenTransporteAccionAviso, $mostrarCamposOpcionales = "")
    {
        global $html;
        global $bd;
        global $administrador;
        global $auxiliar;

        //DECLARAMOS EL ARRAY
        $arrayCampos = array();

        //BUSCAMOS EL AVISO ACCION
        $rowAvisoAccion = $bd->VerReg("ORDEN_TRANSPORTE_ACCION_AVISO", "ID_ORDEN_TRANSPORTE_ACCION_AVISO", $idOrdenTransporteAccionAviso);

        //BUSCAMOS LA ACCION
        $rowAccion    = $bd->VerReg("ORDEN_TRANSPORTE_ACCION", "ID_ORDEN_TRANSPORTE_ACCION", $rowAvisoAccion->ID_ORDEN_TRANSPORTE_ACCION);
        $nombreAccion = $rowAccion->TIPO_ACCION;

        //OBTENEMOS LA ORDEN DE TRANSPORTE CON LOS CAMPOS
        $rowOrdenTransporte = $this->getOrdenTransporteConstruccion($rowAvisoAccion->ID_ORDEN_TRANSPORTE);

        //COGEMOS LOS CAMPOS IMPLICADOS CON CADA ACCION
        if ($nombreAccion == "Cumplimentar Fecha Disponibilidad Entrega"):

            $arrayCampos['Fecha Entrega Disponible'] = array('Campo' => 'FECHA_ENTREGA_DISPONIBLE_PROVEEDOR', 'Rol' => 'Proveedor del Material', 'Tipo' => 'Fecha', 'Variable' => 'EntregaDisponibleProveedor');
        endif;

        if ($nombreAccion == "Confirmar Reserva Medios"):

            //$arrayCampos['ETD Objetivo'] = array('Campo' => 'FECHA_OBJETIVO_SALIDA', 'Rol' => 'Forwarder', 'Tipo' => 'Fecha', 'Variable' => 'ObjetivoSalida'); AHORA LA INTRODUCE EL GESTOR

            $arrayCampos['ETD Planificada'] = array('Campo' => 'FECHA_ESTIMADA_SALIDA', 'Rol' => 'Forwarder', 'Tipo' => 'Fecha', 'Variable' => 'EstimadaSalida');

            $arrayCampos['Dias Salida Linea Regular'] = array('Campo' => 'DIAS_SALIDA_LINEA_REGULAR', 'Rol' => 'Forwarder', 'Tipo' => 'Set', 'Variable' => 'DiaSalidaRegular');

            if ($rowOrdenTransporte->TIPO_TRANSPORTE != "Terrestre"):
                $arrayCampos['Booking'] = array('Campo' => 'NUMERO_BOOKING', 'Rol' => 'Forwarder', 'Tipo' => 'Texto', 'Variable' => 'Booking');
            endif;

            $arrayCampos['Reserva de Medios Realizada'] = array('Campo' => 'RESERVA_MEDIOS_REALIZADA', 'Rol' => 'Forwarder', 'Tipo' => 'Button', 'Variable' => 'ReservaMedios');
        endif;

        if ($nombreAccion == "Confirmar Material Preparado"):
            $arrayCampos['Descripcion Contenido'] = array('Campo' => 'ID_TIPO_MATERIAL_PROVEEDOR', 'Rol' => 'Proveedor del Material', 'Tipo' => 'TipoMaterial', 'Variable' => 'TipoMaterialProveedor');

            //NO AFECTA A EMBARQUES
            if ($rowOrdenTransporte->CON_EMBARQUE_GC != 1):
                $arrayCampos['Punto Entrega Material'] = array('Campo' => 'ID_DIRECCION_ENTREGA_PROVEEDOR', 'Rol' => ($rowOrdenTransporte->RECOGIDA_EN_PROVEEDOR == 1 ? 'Proveedor del Material' : 'Forwarder'), 'Tipo' => 'Direccion', 'Variable' => 'DireccionEntregaProveedor');

                $arrayCampos['Persona de Contacto Punto Entrega'] = array('Campo' => 'ID_DIRECCION_ENTREGA_PROVEEDOR_CONTACTO', 'Rol' => ($rowOrdenTransporte->RECOGIDA_EN_PROVEEDOR == 1 ? 'Proveedor del Material' : 'Forwarder'), 'Tipo' => 'ContactoDireccion', 'Variable' => 'PersonaContactoProveedor');
            endif;
        endif;

        if ($nombreAccion == "Confirmar Envio Contenedor Vacio a Recogida"):

            $arrayCampos['Envio Contenedor Vacio a Recogida'] = array('Campo' => 'ENVIO_CONTENEDOR_VACIO_RECOGIDA', 'Rol' => 'Forwarder', 'Tipo' => 'Button', 'Variable' => 'EnvioContenedorRecogida');
        endif;

        if ($nombreAccion == "Confirmar Entrega a Forwarder"):

            $arrayCampos['Nº Packing List'] = array('Campo' => 'NUMERO_PACKING_LIST', 'Rol' => 'Proveedor del Material', 'Tipo' => 'Texto', 'Variable' => 'NumPackingList');

            //LOS CAMPOS SON OBLIGATORIOS DEPENDIENDO DEL VALOR DEL CONTENEDOR
            //BUSCAMOS EL CONTENEDOR
            $rowContenedor = false;
            if ($rowOrdenTransporte->ID_CONTENEDOR_EXPORTACION != NULL):
                $rowContenedor = $bd->VerReg("CONTENEDOR_EXPORTACION", "ID_CONTENEDOR_EXPORTACION", $rowOrdenTransporte->ID_CONTENEDOR_EXPORTACION);
            endif;

            if (($mostrarCamposOpcionales == "Si") ||
                ($rowContenedor == false || $rowContenedor->REQUIERE_PRECINTO == 1)
            ):
                $arrayCampos['Sello Contenedor'] = array('Campo' => 'SELLO_CONTENEDOR', 'Rol' => 'Proveedor del Material', 'Tipo' => 'Texto', 'Variable' => 'SelloContenedor');

                $arrayCampos['Nº Contenedor'] = array('Campo' => 'NUMERO_CONTENEDOR', 'Rol' => 'Proveedor del Material', 'Tipo' => 'Texto', 'Variable' => 'NumContenedor');
            endif;

            $arrayCampos['Packing List'] = array('Campo' => 'ADJUNTO_PACKING_PROVEEDOR', 'Rol' => 'Proveedor del Material', 'Tipo' => 'Adjunto', 'Variable' => 'packing');

            if ($mostrarCamposOpcionales == "Si"):
                $arrayCampos['Certificado Origen'] = array('Campo' => 'ADJUNTO_CERTIFICADO_ORIGEN', 'Rol' => 'Proveedor del Material', 'Tipo' => 'Adjunto', 'Variable' => 'certificado_origen');

                $arrayCampos['Certificado Fitosanitario'] = array('Campo' => 'ADJUNTO_CERTIFICADO_FITOSANITARIO', 'Rol' => 'Proveedor del Material', 'Tipo' => 'Adjunto', 'Variable' => 'fitosanitario');
            endif;

            $arrayCampos['Estado Material'] = array('Campo' => 'ESTADO_MATERIAL', 'Rol' => 'Proveedor del Material', 'Tipo' => 'Select', 'Variable' => 'EstadoMaterial');

            $arrayCampos['Tipo Contenido'] = array('Campo' => 'TIPO_CONTENIDO', 'Rol' => 'Proveedor del Material', 'Tipo' => 'Select', 'Variable' => 'TipoContenido');

            //GESTION DE FACTURAS PROVEEDOR ( LOS CAMPOS SON OBLIGATORIOS DEPENDIENDO DEL VALOR DE ESTADO_MATERIAL Y TIPO_CONTENIDO)
            if (($mostrarCamposOpcionales == "Si") ||
                (($rowOrdenTransporte->ESTADO_MATERIAL == "Usado" || $rowOrdenTransporte->ESTADO_MATERIAL == "Ambos") && ($rowOrdenTransporte->TIPO_CONTENIDO == "Componente" || $rowOrdenTransporte->TIPO_CONTENIDO == "Ambos"))
            ):
                $arrayCampos['Componente Usado'] = array('Campo' => 'NUMERO_FACTURA_COMPONENTE_USADO', 'Rol' => 'Proveedor del Material', 'Tipo' => 'Texto', 'Variable' => 'FacturaComponenteUsado');

                $arrayCampos['Adjunto Componente Usado'] = array('Campo' => 'ADJUNTO_FACTURA_COMPONENTE_USADO', 'Rol' => 'Proveedor del Material', 'Tipo' => 'Adjunto', 'Variable' => 'componente_usado');
            endif;
            if (($mostrarCamposOpcionales == "Si") ||
                (($rowOrdenTransporte->ESTADO_MATERIAL == "Nuevo" || $rowOrdenTransporte->ESTADO_MATERIAL == "Ambos") && ($rowOrdenTransporte->TIPO_CONTENIDO == "Componente" || $rowOrdenTransporte->TIPO_CONTENIDO == "Ambos"))
            ):
                $arrayCampos['Componente Nuevo'] = array('Campo' => 'NUMERO_FACTURA_COMPONENTE_NUEVO', 'Rol' => 'Proveedor del Material', 'Tipo' => 'Texto', 'Variable' => 'FacturaComponenteNuevo');

                $arrayCampos['Adjunto Componente Nuevo'] = array('Campo' => 'ADJUNTO_FACTURA_COMPONENTE_NUEVO', 'Rol' => 'Proveedor del Material', 'Tipo' => 'Adjunto', 'Variable' => 'componente_nuevo');
            endif;
            if (($mostrarCamposOpcionales == "Si") ||
                (($rowOrdenTransporte->ESTADO_MATERIAL == "Usado" || $rowOrdenTransporte->ESTADO_MATERIAL == "Ambos") && ($rowOrdenTransporte->TIPO_CONTENIDO == "Util" || $rowOrdenTransporte->TIPO_CONTENIDO == "Ambos"))
            ):
                $arrayCampos['Util Usado'] = array('Campo' => 'NUMERO_FACTURA_UTIL_USADO', 'Rol' => 'Proveedor del Material', 'Tipo' => 'Texto', 'Variable' => 'FacturaUtilUsado');

                $arrayCampos['Adjunto Util Usado'] = array('Campo' => 'ADJUNTO_FACTURA_UTIL_USADO', 'Rol' => 'Proveedor del Material', 'Tipo' => 'Adjunto', 'Variable' => 'util_usado');
            endif;
            if (($mostrarCamposOpcionales == "Si") ||
                (($rowOrdenTransporte->ESTADO_MATERIAL == "Nuevo" || $rowOrdenTransporte->ESTADO_MATERIAL == "Ambos") && ($rowOrdenTransporte->TIPO_CONTENIDO == "Util" || $rowOrdenTransporte->TIPO_CONTENIDO == "Ambos"))
            ):
                $arrayCampos['Util Nuevo'] = array('Campo' => 'NUMERO_FACTURA_UTIL_NUEVO', 'Rol' => 'Proveedor del Material', 'Tipo' => 'Texto', 'Variable' => 'FacturaUtilNuevo');

                $arrayCampos['Adjunto Util Nuevo'] = array('Campo' => 'ADJUNTO_FACTURA_UTIL_NUEVO', 'Rol' => 'Proveedor del Material', 'Tipo' => 'Adjunto', 'Variable' => 'util_nuevo');
            endif;
            //SI NOS VIENE PARA MOSTRAR LOS CAMPOS OPCIONALES, MOSTRAMOS LOS IMPORTES DE LAS FACTURAS
            if ($mostrarCamposOpcionales == "Si"):

                //Tipo "ImporteMoneda" SIRVE PARA EL CAMPO IMPORTE_COMPONENTE_USADO e ID_MONEDA_COMPONENTE_USADO
                $arrayCampos['Importe Componente Usado'] = array('Campo' => 'COMPONENTE_USADO', 'Rol' => 'Proveedor del Material', 'Tipo' => 'ImporteMoneda', 'Variable' => 'ComponenteUsado');
                $arrayCampos['Importe Componente Nuevo'] = array('Campo' => 'COMPONENTE_NUEVO', 'Rol' => 'Proveedor del Material', 'Tipo' => 'ImporteMoneda', 'Variable' => 'ComponenteNuevo');
                $arrayCampos['Importe Util Usado']       = array('Campo' => 'UTIL_USADO', 'Rol' => 'Proveedor del Material', 'Tipo' => 'ImporteMoneda', 'Variable' => 'UtilUsado');
                $arrayCampos['Importe Util Nuevo']       = array('Campo' => 'UTIL_NUEVO', 'Rol' => 'Proveedor del Material', 'Tipo' => 'ImporteMoneda', 'Variable' => 'ComponenteNuevo');

            endif;
        endif;

        if ($nombreAccion == "Confirmar Recibido de Proveedor"):
            //SI ES TERRESTRE SE PIDEN CAMPOS
            if ($rowOrdenTransporte->TIPO_TRANSPORTE == "Terrestre"):
                //PEDIMOS DATOS VEHICULO
                if ($mostrarCamposOpcionales == "Si"):
                    $arrayCampos['Datos Vehiculo']          = array('Campo' => 'EXCEPCION_VEHICULO', 'Rol' => 'Transp. InLand', 'Tipo' => 'ExcepcionDatosVehiculo', 'Variable' => '');
                    $arrayCampos['Datos Vehiculo']['Tabla'] = "Ampliada";//INDICAMOS QUE EL CAMPO VA CONTRA LA TABLA AMPLIADA
                endif;

                if ($rowOrdenTransporte->TRANSPORTE_CON_ADUANAS == 1)://SI TIENE ADUANAS
                    $arrayCampos['Fecha Planificada Llegada Aduana']          = array('Campo' => 'FECHA_PLANIFICADA_LLEGADA_ADUANA', 'Rol' => 'Forwarder', 'Tipo' => 'Fecha', 'Variable' => 'PlanificadaLlegadaAduana');
                    $arrayCampos['Fecha Planificada Llegada Aduana']['Tabla'] = "Ampliada";//INDICAMOS QUE EL CAMPO VA CONTRA LA TABLA AMPLIADA

                else://SIN ADUANA
                    $arrayCampos['Nº CMR']          = array('Campo' => 'NUMERO_CMR', 'Rol' => 'Forwarder', 'Tipo' => 'Texto', 'Variable' => 'NumeroCMR');
                    $arrayCampos['Nº CMR']['Tabla'] = "Ampliada";//INDICAMOS QUE EL CAMPO VA CONTRA LA TABLA AMPLIADA

                    $arrayCampos['Adjunto CMR']          = array('Campo' => 'ADJUNTO_CMR', 'Rol' => 'Forwarder', 'Tipo' => 'Adjunto', 'Variable' => 'cmr');
                    $arrayCampos['Adjunto CMR']['Tabla'] = "Ampliada";//INDICAMOS QUE EL CAMPO VA CONTRA LA TABLA AMPLIADA

                    $arrayCampos['Fecha Entrega Final Planificada']          = array('Campo' => 'FECHA_ENTREGA_FINAL_PLANIFICADA', 'Rol' => 'Transp. InLand', 'Tipo' => 'Fecha', 'Variable' => 'EntregaFinalPlanificada');
                    $arrayCampos['Fecha Entrega Final Planificada']['Tabla'] = "Ampliada";//INDICAMOS QUE EL CAMPO VA CONTRA LA TABLA AMPLIADA
                endif;
            endif;

            $arrayCampos['Fecha Recibido Proveedor']          = array('Campo' => 'FECHA_RECIBIDO_PROVEEDOR', 'Rol' => 'Forwarder', 'Tipo' => 'Fecha', 'Variable' => 'RecibidoProveedor');
            $arrayCampos['Fecha Recibido Proveedor']['Tabla'] = "Ampliada";//INDICAMOS QUE EL CAMPO VA CONTRA LA TABLA AMPLIADA
        endif;

        if ($nombreAccion == "Confirmar Salida Buque"):

            //$arrayCampos['ETA Objetivo'] = array('Campo' => 'FECHA_OBJETIVO_LLEGADA', 'Rol' => 'Forwarder', 'Tipo' => 'Fecha', 'Variable' => 'ObjetivoLlegada'); AHORA LA INTRODUCE EL GESTOR

            $arrayCampos['ETA Planificada'] = array('Campo' => 'FECHA_ESTIMADA_LLEGADA', 'Rol' => 'Forwarder', 'Tipo' => 'Fecha', 'Variable' => 'EstimadaLlegada');

            //DATOS TRANSITO, COMPROBAREMOS LA TABLA ORDEN_TRANSPORTE_TRANSBORDO
            if ($rowOrdenTransporte->TIPO_TRANSPORTE == "Maritimo" || $rowOrdenTransporte->TIPO_TRANSPORTE == "Multimodal Maritimo"):
                $arrayCampos['Datos Transito'] = array('Campo' => 'ORDEN_TRANSPORTE_TRANSBORDO', 'Rol' => 'Forwarder', 'Tipo' => 'ExcepcionBuques', 'Variable' => '');
            elseif ($rowOrdenTransporte->TIPO_TRANSPORTE == "Aereo" || $rowOrdenTransporte->TIPO_TRANSPORTE == "Multimodal Aereo"):
                $arrayCampos['Datos Transito'] = array('Campo' => 'ORDEN_TRANSPORTE_TRANSBORDO', 'Rol' => 'Forwarder', 'Tipo' => 'ExcepcionAviones', 'Variable' => '');
            endif;
        endif;

        if ($nombreAccion == "Aceptar/Rechazar Documentacion Proveedor"):
            $arrayCampos['Aceptacion Documentacion Proveedor'] = array('Campo' => 'ACEPTACION_DOCUMENTACION_PROVEEDOR', 'Rol' => 'Agente Aduanal', 'Tipo' => 'AceptarRechazar', 'Variable' => 'AceptacionDocumentacionProveedor');
        endif;

        if ($nombreAccion == "Modificar Documentacion"):

            $arrayCampos['Packing List'] = array('Campo' => 'ADJUNTO_PACKING_PROVEEDOR', 'Rol' => 'Proveedor del Material', 'Tipo' => 'Adjunto', 'Variable' => 'packing');

            if ($mostrarCamposOpcionales == "Si"):
                $arrayCampos['Certificado Origen'] = array('Campo' => 'ADJUNTO_CERTIFICADO_ORIGEN', 'Rol' => 'Proveedor del Material', 'Tipo' => 'Adjunto', 'Variable' => 'certificado_origen');

                $arrayCampos['Certificado Fitosanitario'] = array('Campo' => 'ADJUNTO_CERTIFICADO_FITOSANITARIO', 'Rol' => 'Proveedor del Material', 'Tipo' => 'Adjunto', 'Variable' => 'fitosanitario');
            endif;

            $arrayCampos['Estado Material'] = array('Campo' => 'ESTADO_MATERIAL', 'Rol' => 'Proveedor del Material', 'Tipo' => 'Select', 'Variable' => 'EstadoMaterial');

            $arrayCampos['Tipo Contenido'] = array('Campo' => 'TIPO_CONTENIDO', 'Rol' => 'Proveedor del Material', 'Tipo' => 'Select', 'Variable' => 'TipoContenido');

            //GESTION DE FACTURAS PROVEEDOR ( LOS CAMPOS SON OBLIGATORIOS DEPENDIENDO DEL VALOR DE ESTADO_MATERIAL Y TIPO_CONTENIDO)
            if (($mostrarCamposOpcionales == "Si") ||
                (($rowOrdenTransporte->ESTADO_MATERIAL == "Usado" || $rowOrdenTransporte->ESTADO_MATERIAL == "Ambos") && ($rowOrdenTransporte->TIPO_CONTENIDO == "Componente" || $rowOrdenTransporte->TIPO_CONTENIDO == "Ambos"))
            ):
                $arrayCampos['Componente Usado'] = array('Campo' => 'NUMERO_FACTURA_COMPONENTE_USADO', 'Rol' => 'Proveedor del Material', 'Tipo' => 'Texto', 'Variable' => 'FacturaComponenteUsado');

                $arrayCampos['Adjunto Componente Usado'] = array('Campo' => 'ADJUNTO_FACTURA_COMPONENTE_USADO', 'Rol' => 'Proveedor del Material', 'Tipo' => 'Adjunto', 'Variable' => 'componente_usado');
            endif;
            if (($mostrarCamposOpcionales == "Si") ||
                (($rowOrdenTransporte->ESTADO_MATERIAL == "Nuevo" || $rowOrdenTransporte->ESTADO_MATERIAL == "Ambos") && ($rowOrdenTransporte->TIPO_CONTENIDO == "Componente" || $rowOrdenTransporte->TIPO_CONTENIDO == "Ambos"))
            ):
                $arrayCampos['Componente Nuevo'] = array('Campo' => 'NUMERO_FACTURA_COMPONENTE_NUEVO', 'Rol' => 'Proveedor del Material', 'Tipo' => 'Texto', 'Variable' => 'FacturaComponenteNuevo');

                $arrayCampos['Adjunto Componente Nuevo'] = array('Campo' => 'ADJUNTO_FACTURA_COMPONENTE_NUEVO', 'Rol' => 'Proveedor del Material', 'Tipo' => 'Adjunto', 'Variable' => 'componente_nuevo');
            endif;
            if (($mostrarCamposOpcionales == "Si") ||
                (($rowOrdenTransporte->ESTADO_MATERIAL == "Usado" || $rowOrdenTransporte->ESTADO_MATERIAL == "Ambos") && ($rowOrdenTransporte->TIPO_CONTENIDO == "Util" || $rowOrdenTransporte->TIPO_CONTENIDO == "Ambos"))
            ):
                $arrayCampos['Util Usado'] = array('Campo' => 'NUMERO_FACTURA_UTIL_USADO', 'Rol' => 'Proveedor del Material', 'Tipo' => 'Texto', 'Variable' => 'FacturaUtilUsado');

                $arrayCampos['Adjunto Util Usado'] = array('Campo' => 'ADJUNTO_FACTURA_UTIL_USADO', 'Rol' => 'Proveedor del Material', 'Tipo' => 'Adjunto', 'Variable' => 'util_usado');
            endif;
            if (($mostrarCamposOpcionales == "Si") ||
                (($rowOrdenTransporte->ESTADO_MATERIAL == "Nuevo" || $rowOrdenTransporte->ESTADO_MATERIAL == "Ambos") && ($rowOrdenTransporte->TIPO_CONTENIDO == "Util" || $rowOrdenTransporte->TIPO_CONTENIDO == "Ambos"))
            ):
                $arrayCampos['Util Nuevo'] = array('Campo' => 'NUMERO_FACTURA_UTIL_NUEVO', 'Rol' => 'Proveedor del Material', 'Tipo' => 'Texto', 'Variable' => 'FacturaUtilNuevo');

                $arrayCampos['Adjunto Util Nuevo'] = array('Campo' => 'ADJUNTO_FACTURA_UTIL_NUEVO', 'Rol' => 'Proveedor del Material', 'Tipo' => 'Adjunto', 'Variable' => 'util_nuevo');
            endif;
            //SI NOS VIENE PARA MOSTRAR LOS CAMPOS OPCIONALES, MOSTRAMOS LOS IMPORTES DE LAS FACTURAS
            if ($mostrarCamposOpcionales == "Si"):

                //Tipo "ImporteMoneda" SIRVE PARA EL CAMPO IMPORTE_COMPONENTE_USADO e ID_MONEDA_COMPONENTE_USADO
                $arrayCampos['Importe Componente Usado'] = array('Campo' => 'COMPONENTE_USADO', 'Rol' => 'Proveedor del Material', 'Tipo' => 'ImporteMoneda', 'Variable' => 'ComponenteUsado');
                $arrayCampos['Importe Componente Nuevo'] = array('Campo' => 'COMPONENTE_NUEVO', 'Rol' => 'Proveedor del Material', 'Tipo' => 'ImporteMoneda', 'Variable' => 'ComponenteNuevo');
                $arrayCampos['Importe Util Usado']       = array('Campo' => 'UTIL_USADO', 'Rol' => 'Proveedor del Material', 'Tipo' => 'ImporteMoneda', 'Variable' => 'UtilUsado');
                $arrayCampos['Importe Util Nuevo']       = array('Campo' => 'UTIL_NUEVO', 'Rol' => 'Proveedor del Material', 'Tipo' => 'ImporteMoneda', 'Variable' => 'ComponenteNuevo');

            endif;

        endif;

        if ($nombreAccion == "Añadir CMR"):
            $arrayCampos['Nº CMR']          = array('Campo' => 'NUMERO_CMR', 'Rol' => 'Forwarder', 'Tipo' => 'Texto', 'Variable' => 'NumeroCMR');
            $arrayCampos['Nº CMR']['Tabla'] = "Ampliada";//INDICAMOS QUE EL CAMPO VA CONTRA LA TABLA AMPLIADA

            $arrayCampos['Adjunto CMR']          = array('Campo' => 'ADJUNTO_CMR', 'Rol' => 'Forwarder', 'Tipo' => 'Adjunto', 'Variable' => 'cmr');
            $arrayCampos['Adjunto CMR']['Tabla'] = "Ampliada";//INDICAMOS QUE EL CAMPO VA CONTRA LA TABLA AMPLIADA
        endif;

        if ($nombreAccion == "Revisar CMR"):
            $arrayCampos['Nº CMR']          = array('Campo' => 'NUMERO_CMR', 'Rol' => 'Forwarder', 'Tipo' => 'Texto', 'Variable' => 'NumeroCMR');
            $arrayCampos['Nº CMR']['Tabla'] = "Ampliada";//INDICAMOS QUE EL CAMPO VA CONTRA LA TABLA AMPLIADA

            $arrayCampos['Adjunto CMR']          = array('Campo' => 'ADJUNTO_CMR', 'Rol' => 'Forwarder', 'Tipo' => 'Adjunto', 'Variable' => 'cmr');
            $arrayCampos['Adjunto CMR']['Tabla'] = "Ampliada";//INDICAMOS QUE EL CAMPO VA CONTRA LA TABLA AMPLIADA
        endif;

        if ($nombreAccion == "Aceptar/Rechazar CMR"):

            $arrayCampos['Aceptacion CMR']          = array('Campo' => 'ACEPTACION_CMR', 'Rol' => 'Agente Aduanal', 'Tipo' => 'AceptarRechazar', 'Variable' => 'AceptacionCMR');
            $arrayCampos['Aceptacion CMR']['Tabla'] = "Ampliada";//INDICAMOS QUE EL CAMPO VA CONTRA LA TABLA AMPLIADA
        endif;

        if ($nombreAccion == "Añadir Borrador BL"):

            $arrayCampos['Booking'] = array('Campo' => 'NUMERO_BOOKING', 'Rol' => 'Forwarder', 'Tipo' => 'Texto', 'Variable' => 'Booking');

            $arrayCampos['Borrador BL Master'] = array('Campo' => 'ADJUNTO_BORRADOR_BL', 'Rol' => 'Forwarder', 'Tipo' => 'Adjunto', 'Variable' => 'borrador_master');

            $arrayCampos['Nº BL Master'] = array('Campo' => 'NUMERO_BL', 'Rol' => 'Forwarder', 'Tipo' => 'Texto', 'Variable' => 'NumeroBL');

            if ($rowOrdenTransporte->TIPO_DOCUMENTO_BL == "Master & House"):
                $arrayCampos['Nº BL House'] = array('Campo' => 'NUMERO_BL_HOUSE', 'Rol' => 'Forwarder', 'Tipo' => 'Texto', 'Variable' => 'NumeroBLHouse');

                $arrayCampos['Borrador BL House'] = array('Campo' => 'ADJUNTO_BORRADOR_BL_HOUSE', 'Rol' => 'Forwarder', 'Tipo' => 'Adjunto', 'Variable' => 'borrador_house');
            endif;

        endif;

        if ($nombreAccion == "Revisar Borrador BL"):

            $arrayCampos['Booking'] = array('Campo' => 'NUMERO_BOOKING', 'Rol' => 'Forwarder', 'Tipo' => 'Texto', 'Variable' => 'Booking');

            $arrayCampos['Borrador BL Master'] = array('Campo' => 'ADJUNTO_BORRADOR_BL', 'Rol' => 'Forwarder', 'Tipo' => 'Adjunto', 'Variable' => 'borrador_master');

            $arrayCampos['Nº BL Master'] = array('Campo' => 'NUMERO_BL', 'Rol' => 'Forwarder', 'Tipo' => 'Texto', 'Variable' => 'NumeroBL');

            if ($rowOrdenTransporte->TIPO_DOCUMENTO_BL == "Master & House"):
                $arrayCampos['Nº BL House'] = array('Campo' => 'NUMERO_BL_HOUSE', 'Rol' => 'Forwarder', 'Tipo' => 'Texto', 'Variable' => 'NumeroBLHouse');

                $arrayCampos['Borrador BL House'] = array('Campo' => 'ADJUNTO_BORRADOR_BL_HOUSE', 'Rol' => 'Forwarder', 'Tipo' => 'Adjunto', 'Variable' => 'borrador_house');
            endif;

        endif;

        if ($nombreAccion == "Aceptar/Rechazar Borrador BL"):
            $arrayCampos['Aceptacion Borrador BL'] = array('Campo' => 'ACEPTACION_BORRADOR_BL', 'Rol' => 'Agente Aduanal', 'Tipo' => 'AceptarRechazar', 'Variable' => 'AceptacionBorradorBL');
        endif;

        if ($nombreAccion == "Añadir BL Definitivo"):

            $arrayCampos['Nº BL Master'] = array('Campo' => 'NUMERO_BL', 'Rol' => 'Forwarder', 'Tipo' => 'Texto', 'Variable' => 'NumeroBL');

            $arrayCampos['Documento BL Master'] = array('Campo' => 'ADJUNTO_BL', 'Rol' => 'Forwarder', 'Tipo' => 'Adjunto', 'Variable' => 'bl_master');

            if ($rowOrdenTransporte->TIPO_DOCUMENTO_BL == "Master & House"):

                $arrayCampos['Nº BL House'] = array('Campo' => 'NUMERO_BL_HOUSE', 'Rol' => 'Forwarder', 'Tipo' => 'Texto', 'Variable' => 'NumeroBLHouse');

                $arrayCampos['Documento BL House'] = array('Campo' => 'ADJUNTO_BL_HOUSE', 'Rol' => 'Forwarder', 'Tipo' => 'Adjunto', 'Variable' => 'bl_house');
            endif;
        endif;

        if ($nombreAccion == "Revisar BL Definitivo"):

            $arrayCampos['Nº BL Master'] = array('Campo' => 'NUMERO_BL', 'Rol' => 'Forwarder', 'Tipo' => 'Texto', 'Variable' => 'NumeroBL');

            $arrayCampos['Documento BL Master'] = array('Campo' => 'ADJUNTO_BL', 'Rol' => 'Forwarder', 'Tipo' => 'Adjunto', 'Variable' => 'bl_master');

            if ($rowOrdenTransporte->TIPO_DOCUMENTO_BL == "Master & House"):

                $arrayCampos['Nº BL House'] = array('Campo' => 'NUMERO_BL_HOUSE', 'Rol' => 'Forwarder', 'Tipo' => 'Texto', 'Variable' => 'NumeroBLHouse');

                $arrayCampos['Documento BL House'] = array('Campo' => 'ADJUNTO_BL_HOUSE', 'Rol' => 'Forwarder', 'Tipo' => 'Adjunto', 'Variable' => 'bl_house');
            endif;
        endif;

        if ($nombreAccion == "Aceptar/Rechazar BL Definitivo"):
            $arrayCampos['Aceptacion BL Definitivo'] = array('Campo' => 'ACEPTACION_BL_DEFINITIVO', 'Rol' => 'Agente Aduanal', 'Tipo' => 'AceptarRechazar', 'Variable' => 'AceptacionBLDefinitivo');
        endif;

        if ($nombreAccion == "Aceptar Documentacion"):
            $arrayCampos['Aceptacion Documentacion'] = array('Campo' => 'ACEPTACION_DOCUMENTACION_EN_ADUANA', 'Rol' => 'Agente Aduanal', 'Tipo' => 'AceptarRechazar', 'Variable' => 'AceptacionDocumentacion');
        endif;


        if ($nombreAccion == "Confirmar Peticion Provision Fondos"):

            //Tipo "ImporteMoneda" SIRVE PARA EL CAMPO IMPORTE_COMPONENTE_USADO e ID_MONEDA_COMPONENTE_USADO
            //$arrayCampos['Importe Flete'] = array('Campo' => 'FLETE', 'Rol' => 'Forwarder', 'Tipo' => 'ImporteMoneda', 'Variable' => 'Flete');
            //$arrayCampos['Importe Seguro'] = array('Campo' => 'SEGURO', 'Rol' => 'Forwarder', 'Tipo' => 'ImporteMoneda', 'Variable' => 'Seguro'); QUITAMOS ESTOS CAMPOS PORQUE  LOS DEBE INTRODUCIR FORWARDER Y NO AGENTE ADUANAL

            $arrayCampos['Importe Arancel'] = array('Campo' => 'ARANCEL', 'Rol' => 'Agente Aduanal', 'Tipo' => 'ImporteMoneda', 'Variable' => 'Arancel');

            $arrayCampos['Importe IVA Aduana'] = array('Campo' => 'IVA_ADUANA', 'Rol' => 'Agente Aduanal', 'Tipo' => 'ImporteMoneda', 'Variable' => 'IvaAduana');
        endif;


        if ($nombreAccion == "Confirmar Provision Fondos")://EXCEPCION, EN VEZ DE LA PANTALLA NORMAL DE ACCIONES, SE MOSTRARA LA DE APROVISIONAMIENTO DE TESORERIA
            $arrayCampos['Importe Pdte Aprovisionar'] = array('Campo' => 'APROVISIONADO_TESORERIA', 'Rol' => 'Tesoreria', 'Tipo' => 'ImporteMoneda', 'Variable' => '');
        endif;

        if ($nombreAccion == "Actualizar ETA"):
            //DATOS TRANSITO, COMPROBAREMOS LA TABLA ORDEN_TRANSPORTE_TRANSBORDO
            if ($rowOrdenTransporte->TIPO_TRANSPORTE == "Maritimo" || $rowOrdenTransporte->TIPO_TRANSPORTE == "Multimodal Maritimo"):
                $arrayCampos['Datos Transito'] = array('Campo' => 'ORDEN_TRANSPORTE_TRANSBORDO', 'Rol' => 'Forwarder', 'Tipo' => 'ExcepcionETA', 'Variable' => '', 'Opcional' => 'Si');
            elseif ($rowOrdenTransporte->TIPO_TRANSPORTE == "Aereo" || $rowOrdenTransporte->TIPO_TRANSPORTE == "Multimodal Aereo"):
                $arrayCampos['Datos Transito'] = array('Campo' => 'ORDEN_TRANSPORTE_TRANSBORDO', 'Rol' => 'Forwarder', 'Tipo' => 'ExcepcionETA', 'Variable' => '', 'Opcional' => 'Si');
            endif;
        endif;

        if ($nombreAccion == "Generar Transbordo"):
            //DATOS TRANSITO, COMPROBAREMOS LA TABLA ORDEN_TRANSPORTE_TRANSBORDO
            if ($rowOrdenTransporte->TIPO_TRANSPORTE == "Maritimo" || $rowOrdenTransporte->TIPO_TRANSPORTE == "Multimodal Maritimo"):
                $arrayCampos['Datos Transito'] = array('Campo' => 'ORDEN_TRANSPORTE_TRANSBORDO', 'Rol' => 'Forwarder', 'Tipo' => 'ExcepcionTransbordo', 'Variable' => '', 'Opcional' => 'Si');
            elseif ($rowOrdenTransporte->TIPO_TRANSPORTE == "Aereo" || $rowOrdenTransporte->TIPO_TRANSPORTE == "Multimodal Aereo"):
                $arrayCampos['Datos Transito'] = array('Campo' => 'ORDEN_TRANSPORTE_TRANSBORDO', 'Rol' => 'Forwarder', 'Tipo' => 'ExcepcionTransbordo', 'Variable' => '', 'Opcional' => 'Si');
            endif;
        endif;

        if ($nombreAccion == "Confirmar Llegada Buque"):

            //DATOS TRANSITO, COMPROBAREMOS LA TABLA ORDEN_TRANSPORTE_TRANSBORDO
            if ($rowOrdenTransporte->TIPO_TRANSPORTE == "Maritimo" || $rowOrdenTransporte->TIPO_TRANSPORTE == "Multimodal Maritimo"):
                $arrayCampos['Datos Transito'] = array('Campo' => 'ORDEN_TRANSPORTE_TRANSBORDO', 'Rol' => 'Forwarder', 'Tipo' => 'ExcepcionBuques', 'Variable' => '', 'Opcional' => 'Si');
            elseif ($rowOrdenTransporte->TIPO_TRANSPORTE == "Aereo" || $rowOrdenTransporte->TIPO_TRANSPORTE == "Multimodal Aereo"):
                $arrayCampos['Datos Transito'] = array('Campo' => 'ORDEN_TRANSPORTE_TRANSBORDO', 'Rol' => 'Forwarder', 'Tipo' => 'ExcepcionAviones', 'Variable' => '', 'Opcional' => 'Si');
            endif;
        endif;

        if ($nombreAccion == "Introducir Operador Transporte"):
            $arrayCampos['Operador Transporte Inland Destino']          = array('Campo' => 'ID_AGENCIA', 'Rol' => 'Forwarder', 'Tipo' => 'Proveedor', 'Variable' => 'OperadorTransporte');
            $arrayCampos['Operador Transporte Inland Destino']['Tabla'] = "OT";//INDICAMOS QUE EL CAMPO VA CONTRA LA TABLA ORDEN TRANSPORTE
        endif;

        if ($nombreAccion == "Marcar Situacion Semaforo"):
            $arrayCampos['Semaforo Aduana'] = array('Campo' => 'SEMAFORO_ADUANA', 'Rol' => 'Agente Aduanal', 'Tipo' => 'RadioButton', 'Variable' => 'SemaforoAduana');
        endif;

        if ($nombreAccion == "Rellenar Datos Aduana"):

            //SI EL SEMAFORO ES ROJO O AMARILLO, SE PIDE FECHA_ESTIMADA_RESOLUCION_ADUANA
            if ($rowOrdenTransporte->SEMAFORO_ADUANA == "Rojo" || $rowOrdenTransporte->SEMAFORO_ADUANA == "Naranja"):
                $arrayCampos['Fecha Estimada Resolucion'] = array('Campo' => 'FECHA_ESTIMADA_RESOLUCION_ADUANA', 'Rol' => 'Agente Aduanal', 'Tipo' => 'Fecha', 'Variable' => 'ResolucionAduana');
            endif;

            $arrayCampos['Fecha Estimada Levante Material'] = array('Campo' => 'FECHA_ESTIMADA_LEVANTE_MATERIAL', 'Rol' => 'Agente Aduanal', 'Tipo' => 'Fecha', 'Variable' => 'EstimadaLevante');

            $arrayCampos['Lugar Levante Material'] = array('Campo' => 'ID_DIRECCION_LEVANTE_MATERIAL', 'Rol' => 'Agente Aduanal', 'Tipo' => 'Direccion', 'Variable' => 'DireccionLevante');

            if ($mostrarCamposOpcionales == "Si"):
                $arrayCampos['Numero Expediente']          = array('Campo' => 'NUMERO_EXPEDIENTE', 'Rol' => 'Agente Aduanal', 'Tipo' => 'Texto', 'Variable' => 'NumeroExpediente');
                $arrayCampos['Numero Expediente']['Tabla'] = "Ampliada";

            endif;
        endif;


        if ($nombreAccion == "Confirmar Levante Material"):
            //Tipo "ImporteMoneda" SIRVE PARA EL CAMPO IMPORTE_COMPONENTE_USADO e ID_MONEDA_COMPONENTE_USADO
            $arrayCampos['Importe Final Arancel Abonado Aduana']          = array('Campo' => 'FINAL_ARANCEL_ABONADO_ADUANA', 'Rol' => 'Importe Final Arancel Abonado Aduana', 'Tipo' => 'ImporteMoneda', 'Variable' => 'FinalArancelAbonadoAduana');
            $arrayCampos['Importe Final Arancel Abonado Aduana']['Tabla'] = "Ampliada";

            $arrayCampos['Importe Final IVA Abonado Aduana']          = array('Campo' => 'FINAL_IVA_ABONADO_ADUANA', 'Rol' => 'Importe Final IVA Abonado Aduana', 'Tipo' => 'ImporteMoneda', 'Variable' => 'FinalIvaAbonadoAduana');
            $arrayCampos['Importe Final IVA Abonado Aduana']['Tabla'] = "Ampliada";

            $arrayCampos['Importe Abonado Aduana'] = array('Campo' => 'ABONADO_ADUANA', 'Rol' => 'Agente Aduanal', 'Tipo' => 'ImporteMoneda', 'Variable' => 'AbonadoAduana');

            $arrayCampos['Justificante Abono Aduana'] = array('Campo' => 'ADJUNTO_ABONACION_ADUANA', 'Rol' => 'Agente Aduanal', 'Tipo' => 'Adjunto', 'Variable' => 'abonoAduana');

            $arrayCampos['DUA Importacion'] = array('Campo' => 'ADJUNTO_DUA_IMPORTACION', 'Rol' => 'Agente Aduanal', 'Tipo' => 'Adjunto', 'Variable' => 'DUA');

            $arrayCampos['Fecha Levante Material'] = array('Campo' => 'FECHA_REAL_LEVANTE_MATERIAL', 'Rol' => 'Agente Aduanal', 'Tipo' => 'Fecha', 'Variable' => 'RealLevante');
        endif;


        if ($nombreAccion == "Confirmar Entrega Mercancia a Transportista Inland"):
            $arrayCampos['Entrega a Transportista Inland'] = array('Campo' => 'ENTREGA_A_TRANSPORTISTA_INLAND', 'Rol' => 'Agente Aduanal', 'Tipo' => 'Button', 'Variable' => 'EntregaTransportistaInland');
        endif;

        if ($nombreAccion == "Confirmar Recogida Mercancia"):
            if ($rowOrdenTransporte->TIPO_TRANSPORTE == "Maritimo" || $rowOrdenTransporte->TIPO_TRANSPORTE == "Multimodal Maritimo" || $rowOrdenTransporte->TIPO_TRANSPORTE == "Aereo" || $rowOrdenTransporte->TIPO_TRANSPORTE == "Multimodal Aereo"):
                $arrayCampos['Fecha Recogida en Puerto']          = array('Campo' => 'FECHA_RECOGIDA_PUERTO', 'Rol' => 'Transp. InLand', 'Tipo' => 'Fecha', 'Variable' => 'RecogidaPuerto');
                $arrayCampos['Fecha Recogida en Puerto']['Tabla'] = "Ampliada";//INDICAMOS QUE EL CAMPO VA CONTRA LA TABLA AMPLIADA
            endif;

            if ($mostrarCamposOpcionales == "Si"):
                $arrayCampos['Datos Vehiculo']          = array('Campo' => 'EXCEPCION_VEHICULO', 'Rol' => 'Transp. InLand', 'Tipo' => 'ExcepcionDatosVehiculo', 'Variable' => '');
                $arrayCampos['Datos Vehiculo']['Tabla'] = "Ampliada";//INDICAMOS QUE EL CAMPO VA CONTRA LA TABLA AMPLIADA

            endif;
        endif;

        if ($nombreAccion == "Confirmar Fecha Estimada Entrega"):
            $arrayCampos['Fecha Entrega Final Planificada']          = array('Campo' => 'FECHA_ENTREGA_FINAL_PLANIFICADA', 'Rol' => 'Transp. InLand', 'Tipo' => 'Fecha', 'Variable' => 'EntregaFinalPlanificada');
            $arrayCampos['Fecha Entrega Final Planificada']['Tabla'] = "Ampliada";//INDICAMOS QUE EL CAMPO VA CONTRA LA TABLA AMPLIADA
        endif;

        if ($nombreAccion == "Confirmar Recepcion Almacen Destino"):
            $arrayCampos['Fecha Entrega Final Real']          = array('Campo' => 'FECHA_ENTREGA_REAL', 'Rol' => 'Transp. InLand', 'Tipo' => 'Fecha', 'Variable' => 'RealEntrega');
            $arrayCampos['Fecha Entrega Final Real']['Tabla'] = "Ampliada";//INDICAMOS QUE EL CAMPO VA CONTRA LA TABLA AMPLIADA
        endif;


        //ACCION FUERA DEL FLUJO
        if ($nombreAccion == "Confirmar Extracoste"):
            $arrayCampos['Aceptacion Documentacion'] = array('Campo' => 'EXCEPCION_ORDEN_TRANSPORTE', 'Rol' => 'Gestor Transporte', 'Tipo' => 'ExcepcionExtracoste', 'Variable' => '');
        endif;

        return $arrayCampos;
    }

    /**
     * MIGRADO A CODEIGNITER (SI SE ACTUALIZA, ACTUALIZAR AMBAS get_array_acciones_masivas)
     * @param string $listaOTs lista OTs a prefiltrar
     * @param string $acciones_especificas si viene indicado que es de EmbarqueGC, solo se muestran las acciones que se cumplen en el embarque
     * @return array
     * DEVUELVE UN ARRAY CON LAS DISTINTAS ACCIONES MASIVAS
     */
    function getAccionesMasivas($listaOTs = "", $acciones_especificas = "")
    {
        global $html;
        global $bd;
        global $administrador;
        global $auxiliar;

        //ARRAY PARA DEVOLVER
        $arrayAcciones = array();

        //SI EL USUARIO ES UN PROVEEDOR FILTRAMOS
        $sqlWhereTipoDestinatario = "";
        $coma                     = "";
        if ($administrador->esProveedor()):
            $rowPerfil = $administrador->ObtenerPerfilAdministrador($administrador->ID_ADMINISTRADOR);

            if ($rowPerfil->ES_FORWARDER_CONSTRUCCION == 1):
                $sqlWhereTipoDestinatario .= $coma . "'Forwarder'";
                $coma                     = ",";
            endif;

            if ($rowPerfil->ES_PROVEEDOR_CONSTRUCCION == 1):
                $sqlWhereTipoDestinatario .= $coma . "'Proveedor del Material'";
                $coma                     = ",";
            endif;

            if ($rowPerfil->ES_AGENTE_ADUANAL_CONSTRUCCION == 1):
                $sqlWhereTipoDestinatario .= $coma . "'Agente Aduanal'";
                $coma                     = ",";
            endif;

            if ($rowPerfil->ES_TRANSPORTISTA_INLAND_CONSTRUCCION == 1):
                $sqlWhereTipoDestinatario .= $coma . "'Transportista Inland'";
                $coma                     = ",";
            endif;

            //SI SE HA RELLENADO HACEMNOS EL FILTRO
            if ($sqlWhereTipoDestinatario != ""):
                $sqlWhereTipoDestinatario = " AND OTA.TIPO_DESTINATARIO IN (" . $sqlWhereTipoDestinatario . ") ";
            endif;
        endif;


        //SI VIENEN OTS
        $joinAvisoTransporte = "";
        $sqlWhereOT          = "";
        if ($listaOTs != ""):
            $joinAvisoTransporte = "INNER JOIN ORDEN_TRANSPORTE_ACCION_AVISO OTAA ON OTAA.ID_ORDEN_TRANSPORTE_ACCION = OTA.ID_ORDEN_TRANSPORTE_ACCION";
            $sqlWhereOT          = " AND OTAA.ID_ORDEN_TRANSPORTE IN (" . $listaOTs . ") AND OTAA.ESTADO_ACCION = 'Pendiente' AND OTAA.BAJA = 0 ";
        endif;

        //SI VIENE INDICADO ACCIONES ESPECIFICAS DEL EMBARQUE
        $sqlWhereOTA = "";
        if ($acciones_especificas == "EmbarqueGC"):
            $sqlWhereOTA = " AND (OTA.TIPO_ACCION = 'Cumplimentar Fecha Disponibilidad Entrega' OR OTA.TIPO_ACCION = 'Confirmar Material Preparado')";
        endif;

        //BUSCAMOS LAS ACCIONES
        $sqlAcciones    = "SELECT DISTINCT OTA.TIPO_ACCION
                            FROM ORDEN_TRANSPORTE_ACCION OTA
                            $joinAvisoTransporte
                            WHERE OTA.BAJA = 0 AND OTA.TIENE_ACCION_MASIVA = 1 $sqlWhereTipoDestinatario $sqlWhereOTA $sqlWhereOT
                            ORDER BY OTA.ORDEN_ACCION";
        $resultAcciones = $bd->ExecSQL($sqlAcciones);

        while ($rowAcciones = $bd->SigReg($resultAcciones)):
            if ($rowAcciones->TIPO_ACCION == "Confirmar Llegada Buque"): //SI ESTA PENDIENTE CONFIRMAR LLEGADA BUQUE, TAMBIEN PUEDEN Actualizar ETa y Generar Transbordo (NO APARARECEN POR DEFECTO PORQUE ESTAN RESUELTAS Y SE BUSCAN ACCIONES PENDIENTES)
                $arrayAcciones['Actualizar ETA']     = "Actualizar ETA";
                $arrayAcciones['Generar Transbordo'] = "Generar Transbordo";
            endif;
            $arrayAcciones[$rowAcciones->TIPO_ACCION] = $rowAcciones->TIPO_ACCION;
        endwhile;

        return $arrayAcciones;
    }

    /**
     * @param string $idOrdenTransporte OT a prefiltrar
     * @param string $fichaProveedor Ficha a la cual está tratando de acceder el usuario
     * @return booleano
     * DEVUELVE UN BOOLEANO QUE INDICA SI EL USUARIO TIENE ACCESO O NO A LA INFORMACIÓN DE UNA OT
     */
    function tieneAccesoOT($idOrdenTransporte, $fichaProveedor)
    {
        global $html;
        global $bd;
        global $administrador;
        global $auxiliar;
        global $orden_transporte;

        if ($administrador->esProveedor()):
            //OBTENGO LA INFORMACIÓN DE LA OT
            $rowOrdenTransporte = $orden_transporte->getOrdenTransporteConstruccion($idOrdenTransporte);

            if ($fichaProveedor == "Proveedor del Material"):

                //SI SE ESTÁ TRATANDO DE ACCEDER A LA INFORMACIÓN DEL PROVEEDOR, COMPROBAMOS EN PRIMER LUGAR SI EL USUARIO ES EL PROVEEDOR DE LA OT
                if ($rowOrdenTransporte->ID_PROVEEDOR == $administrador->ID_PROVEEDOR):

                    //SI ES EL PROVEEDOR, ENTONCES TIENE ACCESO A LA OT
                    return true;

                endif;

            elseif ($fichaProveedor == "Forwarder"):

                //SI SE ESTÁ TRATANDO DE ACCEDER A LA INFORMACIÓN DEL FORWARDER, COMPROBAMOS EN PRIMER LUGAR SI EL USUARIO ES EL FORWARDER DE LA OT
                if ($rowOrdenTransporte->ID_PROVEEDOR_FORWARDER == $administrador->ID_PROVEEDOR):

                    //SI ES EL FORWARDER, ENTONCES TIENE ACCESO A LA OT
                    return true;

                else:

                    //SI NO ES EL FORWARDER, COMPROBAMOS SI EL PROVEEDOR DEL MATERIAL PUEDE ASUMIR EL ROL DE FORWARDER
                    $rowRolesAsumidos = $bd->VerRegRest("ORDEN_TRANSPORTE_ROLES_ASUMIDOS", "ROL = 'Proveedor' AND ID_ORDEN_TRANSPORTE = $idOrdenTransporte", "No");

                    if ($rowRolesAsumidos->FORWARDER_ASUMIDO == 1):

                        //SI EL PROVEEDOR PUEDE ASUMIR EL ROL DE FORWARDER, ENTONCES DEVOLVEMOS TRUE
                        return true;

                    endif;

                endif;

            elseif ($fichaProveedor == "Agente Aduanal"):

                //SI SE ESTÁ TRATANDO DE ACCEDER A LA INFORMACIÓN DEL AGENTE ADUANAL, COMPROBAMOS EN PRIMER LUGAR SI EL USUARIO ES EL AGENTE ADUANAL DE LA OT
                if ($rowOrdenTransporte->ID_PROVEEDOR_AGENTE_ADUANAL == $administrador->ID_PROVEEDOR):

                    //SI ES EL AGENTE ADUANAL, ENTONCES TIENE ACCESO A LA OT
                    return true;

                else:

                    //SI NO ES EL AGENTE ADUANAL, COMPROBAMOS SI EL FORWARDER PUEDE ASUMIR EL ROL DE AGENTE ADUANAL
                    $rowRolesAsumidos = $bd->VerRegRest("ORDEN_TRANSPORTE_ROLES_ASUMIDOS", "ROL = 'Forwarder' AND ID_ORDEN_TRANSPORTE = $idOrdenTransporte", "No");

                    if ($rowRolesAsumidos->AGENTE_ADUANAL_ASUMIDO == 1):

                        //SI EL FORWARDER PUEDE ASUMIR EL ROL DE AGENTE ADUANAL, ENTONCES DEVOLVEMOS TRUE
                        return true;

                    endif;

                endif;

            elseif ($fichaProveedor == "Transportista Inland"):

                //SI SE ESTÁ TRATANDO DE ACCEDER A LA INFORMACIÓN DEL TRANSPORTISTA INLAND, COMPROBAMOS EN PRIMER LUGAR SI EL USUARIO ES EL TRANSPORTISTA INLAND DE LA OT
                if ($rowOrdenTransporte->ID_AGENCIA == $administrador->ID_PROVEEDOR):

                    //SI ES EL TRANSPORTISTA_INLAND, ENTONCES TIENE ACCESO A LA OT
                    return true;

                else:

                    //SI NO ES EL TRANSPORTISTA INLAND, COMPROBAMOS SI EL PROVEEDOR DEL MATERIAL PUEDE ASUMIR EL ROL DE TRANSPORTISTA INLAND
                    $rowRolesAsumidos = $bd->VerRegRest("ORDEN_TRANSPORTE_ROLES_ASUMIDOS", "ROL = 'Proveedor' AND ID_ORDEN_TRANSPORTE = $idOrdenTransporte", "No");

                    if ($rowRolesAsumidos->TRANSPORTISTA_INLAND_ASUMIDO == 1):

                        //SI EL PROVEEDOR PUEDE ASUMIR EL ROL DE TRANSPORTISTA INLAND, ENTONCES DEVOLVEMOS TRUE
                        return true;

                    else:

                        //SI EL PROVEEDOR DEL MATERIAL NO PUEDE ASUMIR EL ROL DE TRANSPORTISTA INLAND, COMPROBAMOS SI EL FORWARDER LO PUEDE ASUMIR
                        $rowRolesAsumidos = $bd->VerRegRest("ORDEN_TRANSPORTE_ROLES_ASUMIDOS", "ROL = 'Forwarder' AND ID_ORDEN_TRANSPORTE = $idOrdenTransporte", "No");

                        if ($rowRolesAsumidos->TRANSPORTISTA_INLAND_ASUMIDO == 1):

                            //SI EL FORWARDER PUEDE ASUMIR EL ROL DE TRANSPORTISTA INLAND, ENTONCES DEVOLVEMOS TRUE
                            return true;

                        else:

                            //SI EL FORWARDER TAMPOCO PUEDE ASUMIR EL ROL DE TRANSPORTISTA INLAND, COMPROBAMOS SI EL AGENTE ADUANAL PUEDE ASUMIRLO
                            $rowRolesAsumidos = $bd->VerRegRest("ORDEN_TRANSPORTE_ROLES_ASUMIDOS", "ROL = 'Agente Aduanal' AND ID_ORDEN_TRANSPORTE = $idOrdenTransporte", "No");

                            if ($rowRolesAsumidos->TRANSPORTISTA_INLAND_ASUMIDO == 1):

                                //SI EL AGENTE ADUANAL PUEDE ASUMIR EL ROL DE TRANSPORTISTA INLAND, ENTONCES DEVOLVEMOS TRUE
                                return true;

                            endif;

                        endif;

                    endif;

                endif;

            endif;

        else:

            //SI EL USUARIO NO ES PROVEEDOR, DEVUELVO TRUE DIRECTAMENTE
            return true;

        endif;

        //SI LLEGAMOS HASTA AQUÍ QUIERE DECIR QUE NO SE PUEDE ACCEDER A LA OT
        return false;

    }

    /**
     * @param string $listaOTs lista OTs a prefiltrar
     * @param string $acciones_especificas si viene indicado que es de EmbarqueGC, solo se muestran las acciones que se cumplen en el embarque
     * @return array
     * DEVUELVE UN ARRAY CON LAS DISTINTAS ACCIONES MASIVAS PARA UN PROVEEDOR
     */
    function getAccionesMasivasProveedor($listaOTs = "", $acciones_especificas = "")
    {
        global $html;
        global $bd;
        global $administrador;
        global $auxiliar;
        global $orden_transporte;

        //ARRAY PARA DEVOLVER
        $arrayAcciones = array();

        //SI EL USUARIO ES UN PROVEEDOR FILTRAMOS
        /*$sqlWhereTipoDestinatario = "";
        $coma                     = "";
        if ($administrador->esProveedor()):
            $rowPerfil = $administrador->ObtenerPerfilAdministrador($administrador->ID_ADMINISTRADOR);

            if ($rowPerfil->ES_FORWARDER_CONSTRUCCION == 1):
                $sqlWhereTipoDestinatario .= $coma . "'Forwarder'";
                $coma                     = ",";
            endif;

            if ($rowPerfil->ES_PROVEEDOR_CONSTRUCCION == 1):
                $sqlWhereTipoDestinatario .= $coma . "'Proveedor del Material'";
                $coma                     = ",";
            endif;

            if ($rowPerfil->ES_AGENTE_ADUANAL_CONSTRUCCION == 1):
                $sqlWhereTipoDestinatario .= $coma . "'Agente Aduanal'";
                $coma                     = ",";
            endif;

            if ($rowPerfil->ES_TRANSPORTISTA_INLAND_CONSTRUCCION == 1):
                $sqlWhereTipoDestinatario .= $coma . "'Transportista Inland'";
                $coma                     = ",";
            endif;

            //SI SE HA RELLENADO HACEMOS EL FILTRO
            if ($sqlWhereTipoDestinatario != ""):
                $sqlWhereTipoDestinatario = " AND OTA.TIPO_DESTINATARIO IN (" . $sqlWhereTipoDestinatario . ") ";
            endif;
        endif;


        //SI VIENEN OTS
        $joinAvisoTransporte = "";
        $sqlWhereOT          = "";
        if ($listaOTs != ""):
            $joinAvisoTransporte = "INNER JOIN ORDEN_TRANSPORTE_ACCION_AVISO OTAA ON OTAA.ID_ORDEN_TRANSPORTE_ACCION = OTA.ID_ORDEN_TRANSPORTE_ACCION";
            $sqlWhereOT          = " AND OTAA.ID_ORDEN_TRANSPORTE IN (" . $listaOTs . ") AND OTAA.ESTADO_ACCION = 'Pendiente' AND OTAA.BAJA = 0 ";
        endif;

        //SI VIENE INDICADO ACCIONES ESPECIFICAS DEL EMBARQUE
        $sqlWhereOTA = "";
        if ($acciones_especificas == "EmbarqueGC"):
            $sqlWhereOTA = " AND (OTA.TIPO_ACCION = 'Cumplimentar Fecha Disponibilidad Entrega' OR OTA.TIPO_ACCION = 'Confirmar Material Preparado')";
        endif;

        //BUSCAMOS LAS ACCIONES
        $sqlAcciones    = "SELECT DISTINCT OTA.ID_ORDEN_TRANSPORTE_ACCION, OTA.TIPO_ACCION, OTA.TIPO_DESTINATARIO
                            FROM ORDEN_TRANSPORTE_ACCION OTA
                            $joinAvisoTransporte
                            WHERE OTA.BAJA = 0 AND OTA.TIENE_ACCION_MASIVA = 1 $sqlWhereTipoDestinatario $sqlWhereOTA $sqlWhereOT
                            ORDER BY OTA.ORDEN_ACCION";
        $resultAcciones = $bd->ExecSQL($sqlAcciones);

        while ($rowAcciones = $bd->SigReg($resultAcciones)):
            if ($rowAcciones->TIPO_ACCION == "Confirmar Llegada Buque"): //SI ESTA PENDIENTE CONFIRMAR LLEGADA BUQUE, TAMBIEN PUEDEN Actualizar ETa y Generar Transbordo (NO APARARECEN POR DEFECTO PORQUE ESTAN RESUELTAS Y SE BUSCAN ACCIONES PENDIENTES)
                $arrayAcciones[$rowAcciones->ID_ORDEN_TRANSPORTE_ACCION]['NOMBRE_ACCION'] = "Actualizar ETA";
                $arrayAcciones[$rowAcciones->ID_ORDEN_TRANSPORTE_ACCION]['ROL']           = "Forwarder";
                $arrayAcciones[$rowAcciones->ID_ORDEN_TRANSPORTE_ACCION]['NOMBRE_ACCION'] = "Generar Transbordo";
                $arrayAcciones[$rowAcciones->ID_ORDEN_TRANSPORTE_ACCION]['ROL']           = "Forwarder";
            endif;
            $arrayAcciones[$rowAcciones->ID_ORDEN_TRANSPORTE_ACCION]['NOMBRE_ACCION'] = $rowAcciones->TIPO_ACCION;
            $arrayAcciones[$rowAcciones->ID_ORDEN_TRANSPORTE_ACCION]['ROL']           = $rowAcciones->TIPO_DESTINATARIO;
        endwhile;*/

        //AHORA COMPROBAMOS SI TIENE OTRAS ACCIONES PENDIENTES EN BASE A LOS ROLES QUE PUEDEN ASUMIR LAS OTS
        if ($listaOTs != ""):

            //CREAMOS ARRAY PARA RECORRER LAS OTS
            $arrOTs = explode(",", (string)$listaOTs);

            foreach ($arrOTs as $idOrdenTransporte):

                //OBTENGO LA INFORMACIÓN DE LA OT
                $rowOrdenTransporte = $orden_transporte->getOrdenTransporteConstruccion($idOrdenTransporte);

                if ($administrador->esProveedor()):

                    if ($rowOrdenTransporte->ID_PROVEEDOR == $administrador->ID_PROVEEDOR):

                        //SI EL PROVEEDOR DEL MATERIAL DE LA OT COINCIDE CON EL PROVEEDOR
                        //OBTENGO LOS ROLES QUE PUEDE ASUMIR EL PROVEEDOR EN LA OT (COMO PROVEEDOR DEL MATERIAL)
                        $rowRolesAsumidos = $bd->VerRegRest("ORDEN_TRANSPORTE_ROLES_ASUMIDOS", "ROL = 'Proveedor' AND ID_ORDEN_TRANSPORTE = $idOrdenTransporte", "No");

                        if ($rowRolesAsumidos->PROVEEDOR_ASUMIDO == 1):

                            //OBTENEMOS LAS ACCIONES PENDIENTES DEL PROVEEDOR
                            $sqlAccionesPendientes    = "SELECT OTA.ID_ORDEN_TRANSPORTE_ACCION, OTA.TIPO_ACCION, OTA.TIPO_DESTINATARIO
                                                    FROM ORDEN_TRANSPORTE_ACCION_AVISO OTAA
                                                        INNER JOIN ORDEN_TRANSPORTE_ACCION OTA ON OTA.ID_ORDEN_TRANSPORTE_ACCION = OTAA.ID_ORDEN_TRANSPORTE_ACCION
                                                    WHERE OTAA.ID_ORDEN_TRANSPORTE = $idOrdenTransporte AND OTAA.ESTADO_ACCION = 'Pendiente' AND OTA.TIENE_ACCION_MASIVA = 1 AND OTA.TIPO_DESTINATARIO = 'Proveedor del Material' AND OTAA.BAJA = 0 AND OTA.BAJA = 0";
                            $resultAccionesPendientes = $bd->ExecSQL($sqlAccionesPendientes);

                            while ($rowAccionesPendientes = $bd->SigReg($resultAccionesPendientes)):

                                //SI NO EXISTEN EN EL ARRAY, LAS AÑADIMOS
                                if (!array_key_exists((string)$rowAccionesPendientes->ID_ORDEN_TRANSPORTE_ACCION, (array)$arrayAcciones)):

                                    $arrayAcciones[$rowAccionesPendientes->ID_ORDEN_TRANSPORTE_ACCION]['NOMBRE_ACCION'] = $rowAccionesPendientes->TIPO_ACCION;
                                    $arrayAcciones[$rowAccionesPendientes->ID_ORDEN_TRANSPORTE_ACCION]['ROL']           = $rowAccionesPendientes->TIPO_DESTINATARIO;

                                endif;

                            endwhile;

                        endif;
                        if ($rowRolesAsumidos->FORWARDER_ASUMIDO == 1):

                            //OBTENEMOS LAS ACCIONES PENDIENTES DEL FORWARDER
                            $sqlAccionesPendientes    = "SELECT OTA.ID_ORDEN_TRANSPORTE_ACCION, OTA.TIPO_ACCION, OTA.TIPO_DESTINATARIO
                                                    FROM ORDEN_TRANSPORTE_ACCION_AVISO OTAA
                                                        INNER JOIN ORDEN_TRANSPORTE_ACCION OTA ON OTA.ID_ORDEN_TRANSPORTE_ACCION = OTAA.ID_ORDEN_TRANSPORTE_ACCION
                                                    WHERE OTAA.ID_ORDEN_TRANSPORTE = $idOrdenTransporte AND OTAA.ESTADO_ACCION = 'Pendiente' AND OTA.TIENE_ACCION_MASIVA = 1 AND OTA.TIPO_DESTINATARIO = 'Forwarder' AND OTAA.BAJA = 0 AND OTA.BAJA = 0";
                            $resultAccionesPendientes = $bd->ExecSQL($sqlAccionesPendientes);

                            while ($rowAccionesPendientes = $bd->SigReg($resultAccionesPendientes)):

                                //SI NO EXISTEN EN EL ARRAY, LAS AÑADIMOS
                                if (!array_key_exists((string)$rowAccionesPendientes->ID_ORDEN_TRANSPORTE_ACCION, (array)$arrayAcciones)):

                                    $arrayAcciones[$rowAccionesPendientes->ID_ORDEN_TRANSPORTE_ACCION]['NOMBRE_ACCION'] = $rowAccionesPendientes->TIPO_ACCION;
                                    $arrayAcciones[$rowAccionesPendientes->ID_ORDEN_TRANSPORTE_ACCION]['ROL']           = $rowAccionesPendientes->TIPO_DESTINATARIO;

                                endif;

                                if ($rowAccionesPendientes->TIPO_ACCION == "Confirmar Llegada Buque"):

                                    //SI ES LA ACCIÓN DE CONFIRMAR LLEGADA BUQUE, ENTONCES AÑADIMOS TAMBIÉN LA GENERACIÓN DE TRANSBORDOS Y LA ACTUALIZACIÓN DE LA ETA
                                    //OBTENEMOS LA GENERACIÓN DE TRANSBORDOS
                                    $NotificaErrorPorEmail = "No";
                                    $rowGenerarTransbordo  = $bd->VerReg("ORDEN_TRANSPORTE_ACCION", "TIPO_ACCION", "Generar Transbordo", "No");

                                    //OBTENEMOS LA ACTUALIZACION DE LA ETA
                                    $NotificaErrorPorEmail = "No";
                                    $rowActualizarETA      = $bd->VerReg("ORDEN_TRANSPORTE_ACCION", "TIPO_ACCION", "Actualizar ETA", "No");

                                    //AÑADIMOS LA INFORMACIÓN AL ARRAY
                                    if (!array_key_exists((string)$rowGenerarTransbordo->ID_ORDEN_TRANSPORTE_ACCION, (array)$arrayAcciones)):
                                        $arrayAcciones[$rowGenerarTransbordo->ID_ORDEN_TRANSPORTE_ACCION]['NOMBRE_ACCION'] = $rowGenerarTransbordo->TIPO_ACCION;
                                        $arrayAcciones[$rowGenerarTransbordo->ID_ORDEN_TRANSPORTE_ACCION]['ROL']           = $rowGenerarTransbordo->TIPO_DESTINATARIO;
                                    endif;
                                    if (!array_key_exists((string)$rowGenerarTransbordo->ID_ORDEN_TRANSPORTE_ACCION, (array)$arrayAcciones)):
                                        $arrayAcciones[$rowActualizarETA->ID_ORDEN_TRANSPORTE_ACCION]['NOMBRE_ACCION'] = $rowActualizarETA->TIPO_ACCION;
                                        $arrayAcciones[$rowActualizarETA->ID_ORDEN_TRANSPORTE_ACCION]['ROL']           = $rowActualizarETA->TIPO_DESTINATARIO;
                                    endif;

                                endif;

                            endwhile;

                        endif;
                        if ($rowRolesAsumidos->TRANSPORTISTA_INLAND_ASUMIDO == 1):

                            //OBTENEMOS LAS ACCIONES PENDIENTES DEL TRANSPORTISTA INLAND
                            $sqlAccionesPendientes    = "SELECT OTA.ID_ORDEN_TRANSPORTE_ACCION, OTA.TIPO_ACCION, OTA.TIPO_DESTINATARIO
                                                    FROM ORDEN_TRANSPORTE_ACCION_AVISO OTAA
                                                        INNER JOIN ORDEN_TRANSPORTE_ACCION OTA ON OTA.ID_ORDEN_TRANSPORTE_ACCION = OTAA.ID_ORDEN_TRANSPORTE_ACCION
                                                    WHERE OTAA.ID_ORDEN_TRANSPORTE = $idOrdenTransporte AND OTAA.ESTADO_ACCION = 'Pendiente' AND OTA.TIENE_ACCION_MASIVA = 1 AND OTA.TIPO_DESTINATARIO = 'Transportista Inland' AND OTAA.BAJA = 0 AND OTA.BAJA = 0";
                            $resultAccionesPendientes = $bd->ExecSQL($sqlAccionesPendientes);

                            while ($rowAccionesPendientes = $bd->SigReg($resultAccionesPendientes)):

                                //SI NO EXISTEN EN EL ARRAY, LAS AÑADIMOS
                                if (!array_key_exists((string)$rowAccionesPendientes->ID_ORDEN_TRANSPORTE_ACCION, (array)$arrayAcciones)):

                                    $arrayAcciones[$rowAccionesPendientes->ID_ORDEN_TRANSPORTE_ACCION]['NOMBRE_ACCION'] = $rowAccionesPendientes->TIPO_ACCION;
                                    $arrayAcciones[$rowAccionesPendientes->ID_ORDEN_TRANSPORTE_ACCION]['ROL']           = $rowAccionesPendientes->TIPO_DESTINATARIO;

                                endif;

                            endwhile;

                        endif;

                    endif;
                    if ($rowOrdenTransporte->ID_PROVEEDOR_FORWARDER == $administrador->ID_PROVEEDOR):

                        //SI EL FORWARDER DE LA OT COINCIDE CON EL PROVEEDOR
                        //OBTENGO LOS ROLES QUE PUEDE ASUMIR EL PROVEEDOR EN LA OT (COMO FORWARDER)
                        $rowRolesAsumidos = $bd->VerRegRest("ORDEN_TRANSPORTE_ROLES_ASUMIDOS", "ROL = 'Forwarder' AND ID_ORDEN_TRANSPORTE = $idOrdenTransporte", "No");

                        if ($rowRolesAsumidos->FORWARDER_ASUMIDO == 1):

                            //OBTENEMOS LAS ACCIONES PENDIENTES DEL FORWARDER
                            $sqlAccionesPendientes    = "SELECT OTA.ID_ORDEN_TRANSPORTE_ACCION, OTA.TIPO_ACCION, OTA.TIPO_DESTINATARIO
                                                    FROM ORDEN_TRANSPORTE_ACCION_AVISO OTAA
                                                        INNER JOIN ORDEN_TRANSPORTE_ACCION OTA ON OTA.ID_ORDEN_TRANSPORTE_ACCION = OTAA.ID_ORDEN_TRANSPORTE_ACCION
                                                    WHERE OTAA.ID_ORDEN_TRANSPORTE = $idOrdenTransporte AND OTAA.ESTADO_ACCION = 'Pendiente' AND OTA.TIENE_ACCION_MASIVA = 1 AND OTA.TIPO_DESTINATARIO = 'Forwarder' AND OTAA.BAJA = 0 AND OTA.BAJA = 0";
                            $resultAccionesPendientes = $bd->ExecSQL($sqlAccionesPendientes);

                            while ($rowAccionesPendientes = $bd->SigReg($resultAccionesPendientes)):

                                //SI NO EXISTEN EN EL ARRAY, LAS AÑADIMOS
                                if (!array_key_exists((string)$rowAccionesPendientes->ID_ORDEN_TRANSPORTE_ACCION, (array)$arrayAcciones)):

                                    $arrayAcciones[$rowAccionesPendientes->ID_ORDEN_TRANSPORTE_ACCION]['NOMBRE_ACCION'] = $rowAccionesPendientes->TIPO_ACCION;
                                    $arrayAcciones[$rowAccionesPendientes->ID_ORDEN_TRANSPORTE_ACCION]['ROL']           = $rowAccionesPendientes->TIPO_DESTINATARIO;

                                endif;

                                if ($rowAccionesPendientes->TIPO_ACCION == "Confirmar Llegada Buque"):

                                    //SI ES LA ACCIÓN DE CONFIRMAR LLEGADA BUQUE, ENTONCES AÑADIMOS TAMBIÉN LA GENERACIÓN DE TRANSBORDOS Y LA ACTUALIZACIÓN DE LA ETA
                                    //OBTENEMOS LA GENERACIÓN DE TRANSBORDOS
                                    $NotificaErrorPorEmail = "No";
                                    $rowGenerarTransbordo  = $bd->VerReg("ORDEN_TRANSPORTE_ACCION", "TIPO_ACCION", "Generar Transbordo", "No");

                                    //OBTENEMOS LA ACTUALIZACION DE LA ETA
                                    $NotificaErrorPorEmail = "No";
                                    $rowActualizarETA      = $bd->VerReg("ORDEN_TRANSPORTE_ACCION", "TIPO_ACCION", "Actualizar ETA", "No");

                                    //AÑADIMOS LA INFORMACIÓN AL ARRAY
                                    $arrayAcciones[$rowGenerarTransbordo->ID_ORDEN_TRANSPORTE_ACCION]['NOMBRE_ACCION'] = $rowGenerarTransbordo->TIPO_ACCION;
                                    $arrayAcciones[$rowGenerarTransbordo->ID_ORDEN_TRANSPORTE_ACCION]['ROL']           = $rowGenerarTransbordo->TIPO_DESTINATARIO;
                                    $arrayAcciones[$rowActualizarETA->ID_ORDEN_TRANSPORTE_ACCION]['NOMBRE_ACCION']     = $rowActualizarETA->TIPO_ACCION;
                                    $arrayAcciones[$rowActualizarETA->ID_ORDEN_TRANSPORTE_ACCION]['ROL']               = $rowActualizarETA->TIPO_DESTINATARIO;

                                endif;

                            endwhile;

                        endif;
                        if ($rowRolesAsumidos->AGENTE_ADUANAL_ASUMIDO == 1):

                            //OBTENEMOS LAS ACCIONES PENDIENTES DEL PROVEEDOR
                            $sqlAccionesPendientes    = "SELECT OTA.ID_ORDEN_TRANSPORTE_ACCION, OTA.TIPO_ACCION, OTA.TIPO_DESTINATARIO
                                                    FROM ORDEN_TRANSPORTE_ACCION_AVISO OTAA
                                                        INNER JOIN ORDEN_TRANSPORTE_ACCION OTA ON OTA.ID_ORDEN_TRANSPORTE_ACCION = OTAA.ID_ORDEN_TRANSPORTE_ACCION
                                                    WHERE OTAA.ID_ORDEN_TRANSPORTE = $idOrdenTransporte AND OTAA.ESTADO_ACCION = 'Pendiente' AND OTA.TIENE_ACCION_MASIVA = 1 AND OTA.TIPO_DESTINATARIO = 'Agente Aduanal' AND OTAA.BAJA = 0 AND OTA.BAJA = 0";
                            $resultAccionesPendientes = $bd->ExecSQL($sqlAccionesPendientes);

                            while ($rowAccionesPendientes = $bd->SigReg($resultAccionesPendientes)):

                                //SI NO EXISTEN EN EL ARRAY, LAS AÑADIMOS
                                if (!array_key_exists((string)$rowAccionesPendientes->ID_ORDEN_TRANSPORTE_ACCION, (array)$arrayAcciones)):

                                    $arrayAcciones[$rowAccionesPendientes->ID_ORDEN_TRANSPORTE_ACCION]['NOMBRE_ACCION'] = $rowAccionesPendientes->TIPO_ACCION;
                                    $arrayAcciones[$rowAccionesPendientes->ID_ORDEN_TRANSPORTE_ACCION]['ROL']           = $rowAccionesPendientes->TIPO_DESTINATARIO;

                                endif;

                            endwhile;

                        endif;
                        if ($rowRolesAsumidos->TRANSPORTISTA_INLAND_ASUMIDO == 1):

                            //OBTENEMOS LAS ACCIONES PENDIENTES DEL TRANSPORTISTA INLAND
                            $sqlAccionesPendientes    = "SELECT OTA.ID_ORDEN_TRANSPORTE_ACCION, OTA.TIPO_ACCION, OTA.TIPO_DESTINATARIO
                                                    FROM ORDEN_TRANSPORTE_ACCION_AVISO OTAA
                                                        INNER JOIN ORDEN_TRANSPORTE_ACCION OTA ON OTA.ID_ORDEN_TRANSPORTE_ACCION = OTAA.ID_ORDEN_TRANSPORTE_ACCION
                                                    WHERE OTAA.ID_ORDEN_TRANSPORTE = $idOrdenTransporte AND OTAA.ESTADO_ACCION = 'Pendiente' AND OTA.TIENE_ACCION_MASIVA = 1 AND OTA.TIPO_DESTINATARIO = 'Transportista Inland' AND OTAA.BAJA = 0 AND OTA.BAJA = 0";
                            $resultAccionesPendientes = $bd->ExecSQL($sqlAccionesPendientes);

                            while ($rowAccionesPendientes = $bd->SigReg($resultAccionesPendientes)):

                                //SI NO EXISTEN EN EL ARRAY, LAS AÑADIMOS
                                if (!array_key_exists((string)$rowAccionesPendientes->ID_ORDEN_TRANSPORTE_ACCION, (array)$arrayAcciones)):

                                    $arrayAcciones[$rowAccionesPendientes->ID_ORDEN_TRANSPORTE_ACCION]['NOMBRE_ACCION'] = $rowAccionesPendientes->TIPO_ACCION;
                                    $arrayAcciones[$rowAccionesPendientes->ID_ORDEN_TRANSPORTE_ACCION]['ROL']           = $rowAccionesPendientes->TIPO_DESTINATARIO;

                                endif;

                            endwhile;

                        endif;

                    endif;
                    if ($rowOrdenTransporte->ID_PROVEEDOR_AGENTE_ADUANAL == $administrador->ID_PROVEEDOR):

                        //SI EL AGENTE ADUANAL DE LA OT COINCIDE CON EL PROVEEDOR
                        //OBTENGO LOS ROLES QUE PUEDE ASUMIR EL PROVEEDOR EN LA OT (COMO AGENTE ADUANAL)
                        $rowRolesAsumidos = $bd->VerRegRest("ORDEN_TRANSPORTE_ROLES_ASUMIDOS", "ROL = 'Agente Aduanal' AND ID_ORDEN_TRANSPORTE = $idOrdenTransporte", "No");

                        if ($rowRolesAsumidos->AGENTE_ADUANAL_ASUMIDO == 1):

                            //OBTENEMOS LAS ACCIONES PENDIENTES DEL PROVEEDOR
                            $sqlAccionesPendientes    = "SELECT OTA.ID_ORDEN_TRANSPORTE_ACCION, OTA.TIPO_ACCION, OTA.TIPO_DESTINATARIO
                                                    FROM ORDEN_TRANSPORTE_ACCION_AVISO OTAA
                                                        INNER JOIN ORDEN_TRANSPORTE_ACCION OTA ON OTA.ID_ORDEN_TRANSPORTE_ACCION = OTAA.ID_ORDEN_TRANSPORTE_ACCION
                                                    WHERE OTAA.ID_ORDEN_TRANSPORTE = $idOrdenTransporte AND OTAA.ESTADO_ACCION = 'Pendiente' AND OTA.TIENE_ACCION_MASIVA = 1 AND OTA.TIPO_DESTINATARIO = 'Agente Aduanal' AND OTAA.BAJA = 0 AND OTA.BAJA = 0";
                            $resultAccionesPendientes = $bd->ExecSQL($sqlAccionesPendientes);

                            while ($rowAccionesPendientes = $bd->SigReg($resultAccionesPendientes)):

                                //SI NO EXISTEN EN EL ARRAY, LAS AÑADIMOS
                                if (!array_key_exists((string)$rowAccionesPendientes->ID_ORDEN_TRANSPORTE_ACCION, (array)$arrayAcciones)):

                                    $arrayAcciones[$rowAccionesPendientes->ID_ORDEN_TRANSPORTE_ACCION]['NOMBRE_ACCION'] = $rowAccionesPendientes->TIPO_ACCION;
                                    $arrayAcciones[$rowAccionesPendientes->ID_ORDEN_TRANSPORTE_ACCION]['ROL']           = $rowAccionesPendientes->TIPO_DESTINATARIO;

                                endif;

                            endwhile;

                        endif;
                        if ($rowRolesAsumidos->TRANSPORTISTA_INLAND_ASUMIDO == 1):

                            //OBTENEMOS LAS ACCIONES PENDIENTES DEL TRANSPORTISTA INLAND
                            $sqlAccionesPendientes    = "SELECT OTA.ID_ORDEN_TRANSPORTE_ACCION, OTA.TIPO_ACCION, OTA.TIPO_DESTINATARIO
                                                    FROM ORDEN_TRANSPORTE_ACCION_AVISO OTAA
                                                        INNER JOIN ORDEN_TRANSPORTE_ACCION OTA ON OTA.ID_ORDEN_TRANSPORTE_ACCION = OTAA.ID_ORDEN_TRANSPORTE_ACCION
                                                    WHERE OTAA.ID_ORDEN_TRANSPORTE = $idOrdenTransporte AND OTAA.ESTADO_ACCION = 'Pendiente' AND OTA.TIENE_ACCION_MASIVA = 1 AND OTA.TIPO_DESTINATARIO = 'Transportista Inland' AND OTAA.BAJA = 0 AND OTA.BAJA = 0";
                            $resultAccionesPendientes = $bd->ExecSQL($sqlAccionesPendientes);

                            while ($rowAccionesPendientes = $bd->SigReg($resultAccionesPendientes)):

                                //SI NO EXISTEN EN EL ARRAY, LAS AÑADIMOS
                                if (!array_key_exists((string)$rowAccionesPendientes->ID_ORDEN_TRANSPORTE_ACCION, (array)$arrayAcciones)):

                                    $arrayAcciones[$rowAccionesPendientes->ID_ORDEN_TRANSPORTE_ACCION]['NOMBRE_ACCION'] = $rowAccionesPendientes->TIPO_ACCION;
                                    $arrayAcciones[$rowAccionesPendientes->ID_ORDEN_TRANSPORTE_ACCION]['ROL']           = $rowAccionesPendientes->TIPO_DESTINATARIO;

                                endif;

                            endwhile;

                        endif;

                    endif;
                    if ($rowOrdenTransporte->ID_AGENCIA == $administrador->ID_PROVEEDOR):

                        //OBTENEMOS LAS ACCIONES PENDIENTES DEL TRANSPORTISTA INLAND
                        $sqlAccionesPendientes    = "SELECT OTA.ID_ORDEN_TRANSPORTE_ACCION, OTA.TIPO_ACCION, OTA.TIPO_DESTINATARIO
                                                    FROM ORDEN_TRANSPORTE_ACCION_AVISO OTAA
                                                        INNER JOIN ORDEN_TRANSPORTE_ACCION OTA ON OTA.ID_ORDEN_TRANSPORTE_ACCION = OTAA.ID_ORDEN_TRANSPORTE_ACCION
                                                    WHERE OTAA.ID_ORDEN_TRANSPORTE = $idOrdenTransporte AND OTAA.ESTADO_ACCION = 'Pendiente' AND OTA.TIENE_ACCION_MASIVA = 1 AND OTA.TIPO_DESTINATARIO = 'Transportista Inland' AND OTAA.BAJA = 0 AND OTA.BAJA = 0";
                        $resultAccionesPendientes = $bd->ExecSQL($sqlAccionesPendientes);

                        while ($rowAccionesPendientes = $bd->SigReg($resultAccionesPendientes)):

                            //SI NO EXISTEN EN EL ARRAY, LAS AÑADIMOS
                            if (!array_key_exists((string)$rowAccionesPendientes->ID_ORDEN_TRANSPORTE_ACCION, (array)$arrayAcciones)):

                                $arrayAcciones[$rowAccionesPendientes->ID_ORDEN_TRANSPORTE_ACCION]['NOMBRE_ACCION'] = $rowAccionesPendientes->TIPO_ACCION;
                                $arrayAcciones[$rowAccionesPendientes->ID_ORDEN_TRANSPORTE_ACCION]['ROL']           = $rowAccionesPendientes->TIPO_DESTINATARIO;

                            endif;

                        endwhile;

                    endif;

                else:

                    //OBTENEMOS LAS ACCIONES PENDIENTES DEL USUARIO
                    $sqlAccionesPendientes    = "SELECT OTA.ID_ORDEN_TRANSPORTE_ACCION, OTA.TIPO_ACCION, OTA.TIPO_DESTINATARIO
                                                    FROM ORDEN_TRANSPORTE_ACCION_AVISO OTAA
                                                        INNER JOIN ORDEN_TRANSPORTE_ACCION OTA ON OTA.ID_ORDEN_TRANSPORTE_ACCION = OTAA.ID_ORDEN_TRANSPORTE_ACCION
                                                    WHERE OTAA.ID_ORDEN_TRANSPORTE = $idOrdenTransporte AND OTAA.ESTADO_ACCION = 'Pendiente' AND OTA.TIENE_ACCION_MASIVA = 1 AND OTAA.BAJA = 0 AND OTA.BAJA = 0";
                    $resultAccionesPendientes = $bd->ExecSQL($sqlAccionesPendientes);

                    while ($rowAccionesPendientes = $bd->SigReg($resultAccionesPendientes)):

                        //SI NO EXISTEN EN EL ARRAY, LAS AÑADIMOS
                        if (!array_key_exists((string)$rowAccionesPendientes->ID_ORDEN_TRANSPORTE_ACCION, (array)$arrayAcciones)):

                            $arrayAcciones[$rowAccionesPendientes->ID_ORDEN_TRANSPORTE_ACCION]['NOMBRE_ACCION'] = $rowAccionesPendientes->TIPO_ACCION;
                            $arrayAcciones[$rowAccionesPendientes->ID_ORDEN_TRANSPORTE_ACCION]['ROL']           = $rowAccionesPendientes->TIPO_DESTINATARIO;

                        endif;

                        if ($rowAccionesPendientes->TIPO_ACCION == "Confirmar Llegada Buque"):

                            //SI ES LA ACCIÓN DE CONFIRMAR LLEGADA BUQUE, ENTONCES AÑADIMOS TAMBIÉN LA GENERACIÓN DE TRANSBORDOS Y LA ACTUALIZACIÓN DE LA ETA
                            //OBTENEMOS LA GENERACIÓN DE TRANSBORDOS
                            $NotificaErrorPorEmail = "No";
                            $rowGenerarTransbordo  = $bd->VerReg("ORDEN_TRANSPORTE_ACCION", "TIPO_ACCION", "Generar Transbordo", "No");

                            //OBTENEMOS LA ACTUALIZACION DE LA ETA
                            $NotificaErrorPorEmail = "No";
                            $rowActualizarETA      = $bd->VerReg("ORDEN_TRANSPORTE_ACCION", "TIPO_ACCION", "Actualizar ETA", "No");

                            //AÑADIMOS LA INFORMACIÓN AL ARRAY
                            $arrayAcciones[$rowGenerarTransbordo->ID_ORDEN_TRANSPORTE_ACCION]['NOMBRE_ACCION'] = $rowGenerarTransbordo->TIPO_ACCION;
                            $arrayAcciones[$rowGenerarTransbordo->ID_ORDEN_TRANSPORTE_ACCION]['ROL']           = $rowGenerarTransbordo->TIPO_DESTINATARIO;
                            $arrayAcciones[$rowActualizarETA->ID_ORDEN_TRANSPORTE_ACCION]['NOMBRE_ACCION']     = $rowActualizarETA->TIPO_ACCION;
                            $arrayAcciones[$rowActualizarETA->ID_ORDEN_TRANSPORTE_ACCION]['ROL']               = $rowActualizarETA->TIPO_DESTINATARIO;

                        endif;

                    endwhile;

                endif;

            endforeach;

        endif;

        return $arrayAcciones;
    }

    /**
     * Devuelve un listado con los campos que se pueden modificar masivamente en una OT de construccion
     */
    function getArrayCamposModificacionMasiva()
    {
        //COMPROBAMOS SI TIENEN VALORES COMUNES PARA LOS CAMPOS
        $arrayCampos                                            = array();
        $arrayCampos['Medio Transporte']                        = array('Tabla' => "ORDEN_TRANSPORTE", 'Campo' => "TIPO_TRANSPORTE", 'Variable' => "selMedioTransporte");
        $arrayCampos['Carga']                                   = array('Tabla' => "ORDEN_TRANSPORTE", 'Campo' => "CARGA", 'Variable' => "selCarga");
        $arrayCampos['Puerto Origen']                           = array('Tabla' => "ORDEN_TRANSPORTE", 'Campo' => "ID_PUERTO_ORIGEN", 'Variable' => "PuertoOrigen", 'ObjetoEspecial' => 'Puerto');
        $arrayCampos['Puerto Destino']                          = array('Tabla' => "ORDEN_TRANSPORTE", 'Campo' => "ID_PUERTO_DESTINO", 'Variable' => "PuertoDestino", 'ObjetoEspecial' => 'Puerto');
        $arrayCampos['Aeropuerto Origen']                       = array('Tabla' => "ORDEN_TRANSPORTE", 'Campo' => "ID_PUERTO_ORIGEN", 'Variable' => "AeropuertoOrigen", 'ObjetoEspecial' => 'Aeropuerto');
        $arrayCampos['Aeropuerto Destino']                      = array('Tabla' => "ORDEN_TRANSPORTE", 'Campo' => "ID_PUERTO_DESTINO", 'Variable' => "AeropuertoDestino", 'ObjetoEspecial' => 'Aeropuerto');
        $arrayCampos['Tipo Contenedor']                         = array('Tabla' => "ORDEN_TRANSPORTE", 'Campo' => "ID_CONTENEDOR_EXPORTACION", 'Variable' => "Contenedor", 'ObjetoEspecial' => 'Contenedor');
        $arrayCampos['Posicion Entrega Destino']                = array('Tabla' => "ORDEN_TRANSPORTE", 'Campo' => "LUGAR_APROVISIONAMIENTO_CONSTRUCCION", 'Variable' => "selLugarAprovisionamiento");
        $arrayCampos['Logistica']                               = array('Tabla' => "ORDEN_TRANSPORTE_CONSTRUCCION", 'Campo' => "TIPO_LOGISTICA", 'Variable' => "selTipoLogistica");
        $arrayCampos['Tipo Envio BL']                           = array('Tabla' => "ORDEN_TRANSPORTE_CONSTRUCCION", 'Campo' => "TIPO_BL", 'Variable' => "selTipoBL");
        $arrayCampos['Contenido Planificado']                   = array('Tabla' => "ORDEN_TRANSPORTE_CONSTRUCCION", 'Campo' => "ID_TIPO_MATERIAL", 'Variable' => "TipoMaterial", 'ObjetoEspecial' => 'TipoMaterial');
        $arrayCampos['Lugar Entrega Final']                     = array('Tabla' => "ORDEN_TRANSPORTE_CONSTRUCCION", 'Campo' => "ID_DIRECCION_ENTREGA_FINAL", 'Variable' => "DireccionEntrega", 'ObjetoEspecial' => 'Direccion');
        $arrayCampos['Punto Entrega segun Incoterm']            = array('Tabla' => "ORDEN_TRANSPORTE_CONSTRUCCION", 'Campo' => "ID_DIRECCION_ENTREGA_INCOTERM", 'Variable' => "DireccionIncoterm", 'ObjetoEspecial' => 'Direccion');
        $arrayCampos['Proveedor del Material']                  = array('Tabla' => "ORDEN_TRANSPORTE_CONSTRUCCION", 'Campo' => "ID_PROVEEDOR", 'Variable' => "Proveedor", 'ObjetoEspecial' => 'Proveedor');
        $arrayCampos['Empresa Aduanal']                         = array('Tabla' => "ORDEN_TRANSPORTE_CONSTRUCCION", 'Campo' => "ID_PROVEEDOR_AGENTE_ADUANAL", 'Variable' => "EmpresaAduanal", 'ObjetoEspecial' => 'Proveedor');
        $arrayCampos['Forwarder']                               = array('Tabla' => "ORDEN_TRANSPORTE_CONSTRUCCION", 'Campo' => "ID_PROVEEDOR_FORWARDER", 'Variable' => "Forwarder", 'ObjetoEspecial' => 'Proveedor');
        $arrayCampos['Incoterm']                                = array('Tabla' => "ORDEN_TRANSPORTE_CONSTRUCCION", 'Campo' => "ID_INCOTERM", 'Variable' => "Incoterm", 'ObjetoEspecial' => 'Incoterm');
        $arrayCampos['Provision Fondos Estimada']               = array('Tabla' => "ORDEN_TRANSPORTE_CONSTRUCCION", 'Campo' => "IMPORTE_TOTAL_PLANIFICADO", 'Variable' => "txImporteProvisionEstimado", 'ObjetoEspecial' => 'Importe');
        $arrayCampos['Moneda Provision Fondos Estimada']        = array('Tabla' => "ORDEN_TRANSPORTE_CONSTRUCCION", 'Campo' => "ID_MONEDA_TOTAL_PLANIFICADO", 'Variable' => "MonedaProvisionEstimado", 'ObjetoEspecial' => 'Moneda');
        $arrayCampos['Fecha Objetivo Entrega']                  = array('Tabla' => "ORDEN_TRANSPORTE_CONSTRUCCION", 'Campo' => "FECHA_OBJETIVO", 'Variable' => "Objetivo", 'ObjetoEspecial' => 'Fecha');
        $arrayCampos['Fecha Planificada Entrega']               = array('Tabla' => "ORDEN_TRANSPORTE_CONSTRUCCION", 'Campo' => "FECHA_PLANIFICADA_ENTREGA_DE_PROVEEDOR", 'Variable' => "PlanificadaIncoterm", 'ObjetoEspecial' => 'Fecha');
        $arrayCampos['ETD Planificada']                         = array('Tabla' => "ORDEN_TRANSPORTE_CONSTRUCCION", 'Campo' => "FECHA_ESTIMADA_SALIDA", 'Variable' => "EstimadaSalida", 'ObjetoEspecial' => 'Fecha');
        $arrayCampos['ETA Planificada']                         = array('Tabla' => "ORDEN_TRANSPORTE_CONSTRUCCION", 'Campo' => "FECHA_ESTIMADA_LLEGADA", 'Variable' => "EstimadaLlegada", 'ObjetoEspecial' => 'Fecha');
        $arrayCampos['ETD Objetivo']                            = array('Tabla' => "ORDEN_TRANSPORTE_CONSTRUCCION", 'Campo' => "FECHA_OBJETIVO_SALIDA", 'Variable' => "ObjetivoSalida", 'ObjetoEspecial' => 'Fecha');
        $arrayCampos['ETA Objetivo']                            = array('Tabla' => "ORDEN_TRANSPORTE_CONSTRUCCION", 'Campo' => "FECHA_OBJETIVO_LLEGADA", 'Variable' => "ObjetivoLlegada", 'ObjetoEspecial' => 'Fecha');
        $arrayCampos['Semanas Transito Objetivo']               = array('Tabla' => "ORDEN_TRANSPORTE_CONSTRUCCION", 'Campo' => "FECHA_ENTREGA_PLANIFICADA", 'Variable' => "txSemanasObjetivo", 'ObjetoEspecial' => 'FechaObjetivoCalculada');
        $arrayCampos['Fecha Entrega Final Objetivo']            = array('Tabla' => "ORDEN_TRANSPORTE_CONSTRUCCION", 'Campo' => "FECHA_ENTREGA_PLANIFICADA", 'Variable' => "txFechaEntregaFinalObjetivo", 'ObjetoEspecial' => 'FechaObjetivoCalculada');
        $arrayCampos['Fecha Real Confirmar Material Preparado'] = array('Tabla' => "ORDEN_TRANSPORTE_CONSTRUCCION", 'Campo' => "FECHA_REAL_CONFIRMAR_MATERIAL_PREPARADO", 'Variable' => "RealConfirmarMaterialPreparado", 'ObjetoEspecial' => 'Fecha');
        $arrayCampos['Nº Lote']                                 = array('Tabla' => "ORDEN_TRANSPORTE_CONSTRUCCION_AMPLIACION", 'Campo' => "ID_PROVEEDOR_PROYECTO_LOTE", 'Variable' => "NumeroLote", 'ObjetoEspecial' => 'Lote');
        $arrayCampos['Dias Transito Planificado']               = array('Tabla' => "ORDEN_TRANSPORTE_CONSTRUCCION_AMPLIACION", 'Campo' => "FECHA_ENTREGA_FINAL_PLANIFICADA", 'Variable' => "txDiasPlanificado", 'ObjetoEspecial' => 'FechaPlanificadaCalculada');
        $arrayCampos['Exportador']                              = array('Tabla' => "ORDEN_TRANSPORTE_CONSTRUCCION_AMPLIACION", 'Campo' => "ID_PROVEEDOR_EXPORTADOR_BL", 'Variable' => "ProveedorExportadorBL", 'ObjetoEspecial' => 'Proveedor');
        $arrayCampos['Cargador Master']                         = array('Tabla' => "ORDEN_TRANSPORTE_CONSTRUCCION_AMPLIACION", 'Campo' => "ID_ENTIDAD_BL_MASTER_CARGADOR", 'Variable' => "EntidadMasterCargador", 'ObjetoEspecial' => 'EntidadBl');
        $arrayCampos['Consignatario Master']                    = array('Tabla' => "ORDEN_TRANSPORTE_CONSTRUCCION_AMPLIACION", 'Campo' => "ID_ENTIDAD_BL_MASTER_CONSIGNATARIO", 'Variable' => "EntidadMasterConsignatario", 'ObjetoEspecial' => 'EntidadBl');
        $arrayCampos['Notificar Master']                        = array('Tabla' => "ORDEN_TRANSPORTE_CONSTRUCCION_AMPLIACION", 'Campo' => "ID_ENTIDAD_BL_MASTER_NOTIFICAR", 'Variable' => "EntidadMasterNotificar", 'ObjetoEspecial' => 'EntidadBl');
        $arrayCampos['Cargador House']                          = array('Tabla' => "ORDEN_TRANSPORTE_CONSTRUCCION_AMPLIACION", 'Campo' => "ID_ENTIDAD_BL_HOUSE_CARGADOR", 'Variable' => "EntidadHouseCargador", 'ObjetoEspecial' => 'EntidadBl');
        $arrayCampos['Consignatario House']                     = array('Tabla' => "ORDEN_TRANSPORTE_CONSTRUCCION_AMPLIACION", 'Campo' => "ID_ENTIDAD_BL_HOUSE_CONSIGNATARIO", 'Variable' => "EntidadHouseConsignatario", 'ObjetoEspecial' => 'EntidadBl');
        $arrayCampos['Notificar House']                         = array('Tabla' => "ORDEN_TRANSPORTE_CONSTRUCCION_AMPLIACION", 'Campo' => "ID_ENTIDAD_BL_HOUSE_NOTIFICAR", 'Variable' => "EntidadHouseNotificar", 'ObjetoEspecial' => 'EntidadBl');
        $arrayCampos['Tipo BL']                                 = array('Tabla' => "ORDEN_TRANSPORTE_CONSTRUCCION_AMPLIACION", 'Campo' => "TIPO_DOCUMENTO_BL", 'Variable' => "selTipoDocumentoBL");

        return $arrayCampos;
    }

    function getArrayCamposModificacionMasivaDirecta()
    {
        //COMPROBAMOS SI TIENEN VALORES COMUNES PARA LOS CAMPOS
        $arrayCampos                                       = array();
        $arrayCampos['Carga']                              = array('Tabla' => "ORDEN_TRANSPORTE", 'Campo' => "CARGA", 'Variable' => "selCarga");
        $arrayCampos['Numero Contenedor']                  = array('Tabla' => "ORDEN_TRANSPORTE_CONSTRUCCION", 'Campo' => "NUMERO_CONTENEDOR", 'Variable' => "txNumContenedor");
        $arrayCampos['Nº Packing List']                    = array('Tabla' => "ORDEN_TRANSPORTE_CONSTRUCCION", 'Campo' => "NUMERO_PACKING_LIST", 'Variable' => "txNumPackingList");
        $arrayCampos['Packing List']                       = array('Tabla' => "ORDEN_TRANSPORTE_CONSTRUCCION", 'Campo' => "ADJUNTO_PACKING_PROVEEDOR", 'Variable' => "adjunto_packing");
        $arrayCampos['Contenido Planificado']              = array('Tabla' => "ORDEN_TRANSPORTE_CONSTRUCCION", 'Campo' => "ID_TIPO_MATERIAL", 'Variable' => "TipoMaterial", 'ObjetoEspecial' => 'TipoMaterial');
        $arrayCampos['Descripcion Contenido']              = array('Tabla' => "ORDEN_TRANSPORTE_CONSTRUCCION", 'Campo' => "ID_TIPO_MATERIAL_PROVEEDOR", 'Variable' => "TipoMaterialProveedor", 'ObjetoEspecial' => 'TipoMaterialProveedor');
        $arrayCampos['Sello Contenedor']                   = array('Tabla' => "ORDEN_TRANSPORTE_CONSTRUCCION", 'Campo' => "SELLO_CONTENEDOR", 'Variable' => "txSelloContenedor");
        $arrayCampos['Booking']                            = array('Tabla' => "ORDEN_TRANSPORTE_CONSTRUCCION", 'Campo' => "NUMERO_BOOKING", 'Variable' => "txNumeroBooking");
        $arrayCampos['Nº BL Master']                       = array('Tabla' => "ORDEN_TRANSPORTE_CONSTRUCCION", 'Campo' => "NUMERO_BL", 'Variable' => "txNumeroBL");
        $arrayCampos['Nº BL House']                        = array('Tabla' => "ORDEN_TRANSPORTE_CONSTRUCCION", 'Campo' => "NUMERO_BL_HOUSE", 'Variable' => "txNumeroBLHouse");
        $arrayCampos['Operador Transporte Inland Destino'] = array('Tabla' => "ORDEN_TRANSPORTE", 'Campo' => 'ID_AGENCIA', 'Variable' => 'OperadorTransporte', 'ObjetoEspecial' => 'OperadorTransporte');
        $arrayCampos['Estado Material']                    = array('Tabla' => "ORDEN_TRANSPORTE_CONSTRUCCION", 'Campo' => "ESTADO_MATERIAL", 'Variable' => "selEstadoMaterial");
        $arrayCampos['Tipo Contenido']                     = array('Tabla' => "ORDEN_TRANSPORTE_CONSTRUCCION", 'Campo' => "TIPO_CONTENIDO", 'Variable' => "selTipoContenido");
        $arrayCampos['Tipo Contenedor']                    = array('Tabla' => "ORDEN_TRANSPORTE", 'Campo' => "ID_CONTENEDOR_EXPORTACION", 'Variable' => "Contenedor", 'ObjetoEspecial' => 'TipoContenedor');
        $arrayCampos['Pedido SAP']                         = array('Tabla' => "ORDEN_TRANSPORTE_CONSTRUCCION_AMPLIACION", 'Campo' => "ID_PLAN_MATERIAL_PROYECTO", 'Variable' => "selPlanMaterial", 'ObjetoEspecial' => 'PlanMaterial');

        $arrayCampos['Nº Factura Componente Usado'] = array('Tabla' => "ORDEN_TRANSPORTE_CONSTRUCCION", 'Campo' => "NUMERO_FACTURA_COMPONENTE_USADO", 'Variable' => "txNumeroFacturaComponenteUsado");
        $arrayCampos['Adjunto Componente Usado']    = array('Tabla' => "ORDEN_TRANSPORTE_CONSTRUCCION", 'Campo' => "ADJUNTO_FACTURA_COMPONENTE_USADO", 'Variable' => "adjunto_componente_usado");

        $arrayCampos['Nº Factura Componente Nuevo'] = array('Tabla' => "ORDEN_TRANSPORTE_CONSTRUCCION", 'Campo' => "NUMERO_FACTURA_COMPONENTE_NUEVO", 'Variable' => "txNumeroFacturaComponenteNuevo");
        $arrayCampos['Adjunto Componente Nuevo']    = array('Tabla' => "ORDEN_TRANSPORTE_CONSTRUCCION", 'Campo' => "ADJUNTO_FACTURA_COMPONENTE_NUEVO", 'Variable' => "adjunto_componente_nuevo");

        $arrayCampos['Nº Factura Util Usado'] = array('Tabla' => "ORDEN_TRANSPORTE_CONSTRUCCION", 'Campo' => "NUMERO_FACTURA_UTIL_USADO", 'Variable' => "txNumeroFacturaUtilUsado");
        $arrayCampos['Adjunto Util Usado']    = array('Tabla' => "ORDEN_TRANSPORTE_CONSTRUCCION", 'Campo' => "ADJUNTO_FACTURA_UTIL_USADO", 'Variable' => "adjunto_util_usado");

        $arrayCampos['Nº Factura Util Nuevo'] = array('Tabla' => "ORDEN_TRANSPORTE_CONSTRUCCION", 'Campo' => "NUMERO_FACTURA_UTIL_NUEVO", 'Variable' => "txNumeroFacturaUtilNuevo");
        $arrayCampos['Adjunto Util Nuevo']    = array('Tabla' => "ORDEN_TRANSPORTE_CONSTRUCCION", 'Campo' => "ADJUNTO_FACTURA_UTIL_NUEVO", 'Variable' => "adjunto_util_nuevo");


        $arrayCampos['Moneda Componente Usado']         = array('Tabla' => "ORDEN_TRANSPORTE_CONSTRUCCION", 'Campo' => "ID_MONEDA_COMPONENTE_USADO", 'Variable' => "MonedaComponenteUsado", 'ObjetoEspecial' => 'MonedaComponenteUsado');
        $arrayCampos['Moneda Factura Componente Nuevo'] = array('Tabla' => "ORDEN_TRANSPORTE_CONSTRUCCION", 'Campo' => "ID_MONEDA_COMPONENTE_NUEVO", 'Variable' => "MonedaComponenteNuevo", 'ObjetoEspecial' => 'MonedaComponenteNuevo');
        $arrayCampos['Moneda Factura Util Usado']       = array('Tabla' => "ORDEN_TRANSPORTE_CONSTRUCCION", 'Campo' => "ID_MONEDA_UTIL_USADO", 'Variable' => "MonedaUtilUsado", 'ObjetoEspecial' => 'MonedaUtilUsado');
        $arrayCampos['Moneda Factura Util Nuevo']       = array('Tabla' => "ORDEN_TRANSPORTE_CONSTRUCCION", 'Campo' => "ID_MONEDA_UTIL_NUEVO", 'Variable' => "MonedaUtilNuevo", 'ObjetoEspecial' => 'MonedaUtilNuevo');

        $arrayCampos['Importe Componente Usado'] = array('Tabla' => "ORDEN_TRANSPORTE_CONSTRUCCION", 'Campo' => "IMPORTE_COMPONENTE_USADO", 'Variable' => "txImporteComponenteUsado");
        $arrayCampos['Importe Componente Nuevo'] = array('Tabla' => "ORDEN_TRANSPORTE_CONSTRUCCION", 'Campo' => "IMPORTE_COMPONENTE_NUEVO", 'Variable' => "txImporteComponenteNuevo");
        $arrayCampos['Importe Util Usado']       = array('Tabla' => "ORDEN_TRANSPORTE_CONSTRUCCION", 'Campo' => "IMPORTE_UTIL_USADO", 'Variable' => "txImporteUtilUsado");
        $arrayCampos['Importe Util Nuevo']       = array('Tabla' => "ORDEN_TRANSPORTE_CONSTRUCCION", 'Campo' => "IMPORTE_UTIL_NUEVO", 'Variable' => "txImporteUtilNuevo");

        $arrayCampos['Tipo Envio BL']                   = array('Tabla' => "ORDEN_TRANSPORTE_CONSTRUCCION", 'Campo' => "TIPO_BL", 'Variable' => "selTipoBL");
        $arrayCampos['Tipo BL']                         = array('Tabla' => "ORDEN_TRANSPORTE_CONSTRUCCION_AMPLIACION", 'Campo' => "TIPO_DOCUMENTO_BL", 'Variable' => "selTipoDocumentoBL");
        $arrayCampos['Exportador']                      = array('Tabla' => "ORDEN_TRANSPORTE_CONSTRUCCION_AMPLIACION", 'Campo' => "ID_PROVEEDOR_EXPORTADOR_BL", 'Variable' => "ProveedorExportadorBL", 'ObjetoEspecial' => 'ProveedorExportadorBL');
        $arrayCampos['Entidad Master BL Cargador']      = array('Tabla' => "ORDEN_TRANSPORTE_CONSTRUCCION_AMPLIACION", 'Campo' => "ID_ENTIDAD_BL_MASTER_CARGADOR", 'Variable' => "EntidadMasterCargador", 'ObjetoEspecial' => 'EntidadBL');
        $arrayCampos['Entidad Master BL Consignatario'] = array('Tabla' => "ORDEN_TRANSPORTE_CONSTRUCCION_AMPLIACION", 'Campo' => "ID_ENTIDAD_BL_MASTER_CONSIGNATARIO", 'Variable' => "EntidadMasterConsignatario", 'ObjetoEspecial' => 'EntidadBL');
        $arrayCampos['Entidad Master BL Notificar']     = array('Tabla' => "ORDEN_TRANSPORTE_CONSTRUCCION_AMPLIACION", 'Campo' => "ID_ENTIDAD_BL_MASTER_NOTIFICAR", 'Variable' => "EntidadMasterNotificar", 'ObjetoEspecial' => 'EntidadBL');
        $arrayCampos['Entidad House BL Cargador']       = array('Tabla' => "ORDEN_TRANSPORTE_CONSTRUCCION_AMPLIACION", 'Campo' => "ID_ENTIDAD_BL_HOUSE_CARGADOR", 'Variable' => "EntidadHouseCargador", 'ObjetoEspecial' => 'EntidadBL');
        $arrayCampos['Entidad House BL Consignatario']  = array('Tabla' => "ORDEN_TRANSPORTE_CONSTRUCCION_AMPLIACION", 'Campo' => "ID_ENTIDAD_BL_HOUSE_CONSIGNATARIO", 'Variable' => "EntidadHouseConsignatario", 'ObjetoEspecial' => 'EntidadBL');
        $arrayCampos['Entidad House BL Notificar']      = array('Tabla' => "ORDEN_TRANSPORTE_CONSTRUCCION_AMPLIACION", 'Campo' => "ID_ENTIDAD_BL_HOUSE_NOTIFICAR", 'Variable' => "EntidadHouseNotificar", 'ObjetoEspecial' => 'EntidadBL');
        $arrayCampos['Borrador BL Master']              = array('Tabla' => "ORDEN_TRANSPORTE_CONSTRUCCION", 'Campo' => "ADJUNTO_BORRADOR_BL", 'Variable' => "adjunto_borrador_master");
        $arrayCampos['Borrador BL House']               = array('Tabla' => "ORDEN_TRANSPORTE_CONSTRUCCION", 'Campo' => "ADJUNTO_BORRADOR_BL_HOUSE", 'Variable' => "adjunto_borrador_house");
        $arrayCampos['Documento BL Master']             = array('Tabla' => "ORDEN_TRANSPORTE_CONSTRUCCION", 'Campo' => "ADJUNTO_BL", 'Variable' => "adjunto_bl_master");
        $arrayCampos['Documento BL House']              = array('Tabla' => "ORDEN_TRANSPORTE_CONSTRUCCION", 'Campo' => "ADJUNTO_BL_HOUSE", 'Variable' => "adjunto_bl_house");

        $arrayCampos['Barco']    = array('Tabla' => "ORDEN_TRANSPORTE_TRANSBORDO", 'Campo' => "ID_BARCO", 'Variable' => "Barco", 'ObjetoEspecial' => 'Barco');
        $arrayCampos['Naviera']  = array('Tabla' => "ORDEN_TRANSPORTE_TRANSBORDO", 'Campo' => "ID_NAVIERA", 'Variable' => "Naviera", 'ObjetoEspecial' => 'Naviera');
        $arrayCampos['ETD Real'] = array('Tabla' => "ORDEN_TRANSPORTE_TRANSBORDO", 'Campo' => "FECHA_ESTIMADA_SALIDA", 'Variable' => "ETD", 'ObjetoEspecial' => 'FechaBarco');
        $arrayCampos['ETA Real'] = array('Tabla' => "ORDEN_TRANSPORTE_TRANSBORDO", 'Campo' => "FECHA_ESTIMADA_LLEGADA", 'Variable' => "ETA", 'ObjetoEspecial' => 'FechaBarco');

        $arrayCampos['Empresa Aduanal']        = array('Tabla' => "ORDEN_TRANSPORTE_CONSTRUCCION", 'Campo' => "ID_PROVEEDOR_AGENTE_ADUANAL", 'Variable' => "EmpresaAduanal", 'ObjetoEspecial' => 'Proveedor');
        $arrayCampos['Forwarder']              = array('Tabla' => "ORDEN_TRANSPORTE_CONSTRUCCION", 'Campo' => "ID_PROVEEDOR_FORWARDER", 'Variable' => "Forwarder", 'ObjetoEspecial' => 'Proveedor');
        $arrayCampos['Proveedor del Material'] = array('Tabla' => "ORDEN_TRANSPORTE_CONSTRUCCION", 'Campo' => "ID_PROVEEDOR", 'Variable' => "Proveedor", 'ObjetoEspecial' => 'Proveedor');
        $arrayCampos['Incoterm']               = array('Tabla' => "ORDEN_TRANSPORTE_CONSTRUCCION", 'Campo' => "ID_INCOTERM", 'Variable' => "Incoterm", 'ObjetoEspecial' => 'Incoterm');

        $arrayCampos['Fecha Entrega Final Planificada'] = array('Tabla' => "ORDEN_TRANSPORTE_CONSTRUCCION_AMPLIACION", 'Campo' => "FECHA_ENTREGA_FINAL_PLANIFICADA", 'Variable' => "EntregaFinalPlanificada", 'ObjetoEspecial' => 'FechaHora');
        $arrayCampos['Nº Lote']                         = array('Tabla' => "ORDEN_TRANSPORTE_CONSTRUCCION_AMPLIACION", 'Campo' => "ID_PROVEEDOR_PROYECTO_LOTE", 'Variable' => "NumeroLote", 'ObjetoEspecial' => 'Lote');

        return $arrayCampos;
    }

    /**
     * @param $nombreAccion
     * @param $mostrarCamposOpcionales PARA MOSTRAR LOS CAMPOS OPCIONALES. SI NO VIENE RELLENO SOLO MUESTRA CUANDO SON OBLIGATORIOS (Por ejemplo dependiendo de ESTADO_MATERIAL se piden unas FACTURAS u otras)
     * @return  ARRAY CON LOS CAMPOS IMPLICADOS
     * Devuelve un array con los campos obligatorios a pedir en una accion masiva(Campo: CAMPO EN BBDD)
     */
    function getArrayCamposAccionMasivaConstruccion($idOrdenTransporteAccion, $mostrarCamposOpcionales = "")
    {
        global $html;
        global $bd;
        global $administrador;
        global $auxiliar;

        //DECLARAMOS EL ARRAY
        $arrayCampos = array();

        //BUSCAMOS LA ACCION
        $rowAccion    = $bd->VerReg("ORDEN_TRANSPORTE_ACCION", "ID_ORDEN_TRANSPORTE_ACCION", $idOrdenTransporteAccion);
        $nombreAccion = $rowAccion->TIPO_ACCION;


        //COGEMOS LOS CAMPOS IMPLICADOS CON CADA ACCION
        if ($nombreAccion == "Cumplimentar Fecha Disponibilidad Entrega"):

            $arrayCampos['Fecha Entrega Disponible'] = array('Campo' => 'FECHA_ENTREGA_DISPONIBLE_PROVEEDOR', 'Rol' => 'Proveedor del Material', 'Tipo' => 'Fecha', 'Variable' => 'EntregaDisponibleProveedor');
        endif;

        if ($nombreAccion == "Confirmar Reserva Medios"):

            $arrayCampos['ETD Planificada'] = array('Campo' => 'FECHA_ESTIMADA_SALIDA', 'Rol' => 'Forwarder', 'Tipo' => 'Fecha', 'Variable' => 'EstimadaSalida');

            $arrayCampos['Booking'] = array('Campo' => 'NUMERO_BOOKING', 'Rol' => 'Forwarder', 'Tipo' => 'Texto', 'Variable' => 'Booking');

            $arrayCampos['Dias Salida Linea Regular'] = array('Campo' => 'DIAS_SALIDA_LINEA_REGULAR', 'Rol' => 'Forwarder', 'Tipo' => 'Set', 'Variable' => 'DiaSalidaRegular');

            $arrayCampos['Reserva de Medios Realizada'] = array('Campo' => 'RESERVA_MEDIOS_REALIZADA', 'Rol' => 'Forwarder', 'Tipo' => 'Button', 'Variable' => 'ReservaMedios');
        endif;

        if ($nombreAccion == "Confirmar Material Preparado"):
            $arrayCampos['Descripcion Contenido'] = array('Campo' => 'ID_TIPO_MATERIAL_PROVEEDOR', 'Rol' => 'Proveedor del Material', 'Tipo' => 'TipoMaterial', 'Variable' => 'TipoMaterialProveedor');

            $arrayCampos['Punto Entrega Material'] = array('Campo' => 'ID_DIRECCION_ENTREGA_PROVEEDOR', 'Rol' => 'Proveedor del Material', 'Tipo' => 'Direccion', 'Variable' => 'DireccionEntregaProveedor');

            $arrayCampos['Persona de Contacto Punto Entrega'] = array('Campo' => 'ID_DIRECCION_ENTREGA_PROVEEDOR_CONTACTO', 'Rol' => 'Proveedor del Material', 'Tipo' => 'ContactoDireccion', 'Variable' => 'PersonaContactoProveedor');
        endif;

        if ($nombreAccion == "Confirmar Envio Contenedor Vacio a Recogida"):
            $arrayCampos['Envio Contenedor Vacio a Recogida'] = array('Campo' => 'ENVIO_CONTENEDOR_VACIO_RECOGIDA', 'Rol' => 'Forwarder', 'Tipo' => 'Button', 'Variable' => 'EnvioContenedorRecogida');
        endif;

        if ($nombreAccion == "Confirmar Entrega a Forwarder"):

            $arrayCampos['Nº Packing List'] = array('Campo' => 'NUMERO_PACKING_LIST', 'Rol' => 'Proveedor del Material', 'Tipo' => 'Texto', 'Variable' => 'NumPackingList');

            $arrayCampos['Sello Contenedor'] = array('Campo' => 'SELLO_CONTENEDOR', 'Rol' => 'Proveedor del Material', 'Tipo' => 'Texto', 'Variable' => 'SelloContenedor');
            $arrayCampos['Nº Contenedor']    = array('Campo' => 'NUMERO_CONTENEDOR', 'Rol' => 'Proveedor del Material', 'Tipo' => 'Texto', 'Variable' => 'NumContenedor');

            $arrayCampos['Packing List'] = array('Campo' => 'ADJUNTO_PACKING_PROVEEDOR', 'Rol' => 'Proveedor del Material', 'Tipo' => 'Adjunto', 'Variable' => 'packing');

            $arrayCampos['Certificado Origen']        = array('Campo' => 'ADJUNTO_CERTIFICADO_ORIGEN', 'Rol' => 'Proveedor del Material', 'Tipo' => 'Adjunto', 'Variable' => 'certificado_origen');
            $arrayCampos['Certificado Fitosanitario'] = array('Campo' => 'ADJUNTO_CERTIFICADO_FITOSANITARIO', 'Rol' => 'Proveedor del Material', 'Tipo' => 'Adjunto', 'Variable' => 'fitosanitario');

            $arrayCampos['Estado Material'] = array('Campo' => 'ESTADO_MATERIAL', 'Rol' => 'Proveedor del Material', 'Tipo' => 'Select', 'Variable' => 'EstadoMaterial');
            $arrayCampos['Tipo Contenido']  = array('Campo' => 'TIPO_CONTENIDO', 'Rol' => 'Proveedor del Material', 'Tipo' => 'Select', 'Variable' => 'TipoContenido');

            $arrayCampos['Componente Usado']         = array('Campo' => 'NUMERO_FACTURA_COMPONENTE_USADO', 'Rol' => 'Proveedor del Material', 'Tipo' => 'Texto', 'Variable' => 'FacturaComponenteUsado');
            $arrayCampos['Adjunto Componente Usado'] = array('Campo' => 'ADJUNTO_FACTURA_COMPONENTE_USADO', 'Rol' => 'Proveedor del Material', 'Tipo' => 'Adjunto', 'Variable' => 'componente_usado');

            $arrayCampos['Componente Nuevo']         = array('Campo' => 'NUMERO_FACTURA_COMPONENTE_NUEVO', 'Rol' => 'Proveedor del Material', 'Tipo' => 'Texto', 'Variable' => 'FacturaComponenteNuevo');
            $arrayCampos['Adjunto Componente Nuevo'] = array('Campo' => 'ADJUNTO_FACTURA_COMPONENTE_NUEVO', 'Rol' => 'Proveedor del Material', 'Tipo' => 'Adjunto', 'Variable' => 'componente_nuevo');

            $arrayCampos['Util Usado']         = array('Campo' => 'NUMERO_FACTURA_UTIL_USADO', 'Rol' => 'Proveedor del Material', 'Tipo' => 'Texto', 'Variable' => 'FacturaUtilUsado');
            $arrayCampos['Adjunto Util Usado'] = array('Campo' => 'ADJUNTO_FACTURA_UTIL_USADO', 'Rol' => 'Proveedor del Material', 'Tipo' => 'Adjunto', 'Variable' => 'util_usado');

            $arrayCampos['Util Nuevo']         = array('Campo' => 'NUMERO_FACTURA_UTIL_NUEVO', 'Rol' => 'Proveedor del Material', 'Tipo' => 'Texto', 'Variable' => 'FacturaUtilNuevo');
            $arrayCampos['Adjunto Util Nuevo'] = array('Campo' => 'ADJUNTO_FACTURA_UTIL_NUEVO', 'Rol' => 'Proveedor del Material', 'Tipo' => 'Adjunto', 'Variable' => 'util_nuevo');

            //Tipo "ImporteMoneda" SIRVE PARA EL CAMPO IMPORTE_COMPONENTE_USADO e ID_MONEDA_COMPONENTE_USADO
            $arrayCampos['Importe Componente Usado'] = array('Campo' => 'COMPONENTE_USADO', 'Rol' => 'Proveedor del Material', 'Tipo' => 'ImporteMoneda', 'Variable' => 'ComponenteUsado');
            $arrayCampos['Importe Componente Nuevo'] = array('Campo' => 'COMPONENTE_NUEVO', 'Rol' => 'Proveedor del Material', 'Tipo' => 'ImporteMoneda', 'Variable' => 'ComponenteNuevo');
            $arrayCampos['Importe Util Usado']       = array('Campo' => 'UTIL_USADO', 'Rol' => 'Proveedor del Material', 'Tipo' => 'ImporteMoneda', 'Variable' => 'UtilUsado');
            $arrayCampos['Importe Util Nuevo']       = array('Campo' => 'UTIL_NUEVO', 'Rol' => 'Proveedor del Material', 'Tipo' => 'ImporteMoneda', 'Variable' => 'ComponenteNuevo');

        endif;

        if ($nombreAccion == "Confirmar Recibido de Proveedor"):
            $arrayCampos['Fecha Recibido Proveedor']          = array('Campo' => 'FECHA_RECIBIDO_PROVEEDOR', 'Rol' => 'Forwarder', 'Tipo' => 'Fecha', 'Variable' => 'RecibidoProveedor');
            $arrayCampos['Fecha Recibido Proveedor']['Tabla'] = "Ampliada";//INDICAMOS QUE EL CAMPO VA CONTRA LA TABLA AMPLIADA
        endif;

        if ($nombreAccion == "Confirmar Salida Buque"):

            $arrayCampos['ETA Planificada'] = array('Campo' => 'FECHA_ESTIMADA_LLEGADA', 'Rol' => 'Forwarder', 'Tipo' => 'Fecha', 'Variable' => 'EstimadaLlegada');

            //DATOS TRANSITO, COMPROBAREMOS LA TABLA ORDEN_TRANSPORTE_TRANSBORDO
            $arrayCampos['Datos Transito Buque'] = array('Campo' => 'ORDEN_TRANSPORTE_TRANSBORDO', 'Rol' => 'Forwarder', 'Tipo' => 'ExcepcionBuques', 'Variable' => '');
            $arrayCampos['Datos Transito Avion'] = array('Campo' => 'ORDEN_TRANSPORTE_TRANSBORDO', 'Rol' => 'Forwarder', 'Tipo' => 'ExcepcionAviones', 'Variable' => '');
        endif;

        if ($nombreAccion == "Actualizar ETA"):

            //DATOS TRANSITO, COMPROBAREMOS LA TABLA ORDEN_TRANSPORTE_TRANSBORDO
            $arrayCampos['Datos Transito'] = array('Campo' => 'ORDEN_TRANSPORTE_TRANSBORDO', 'Rol' => 'Forwarder', 'Tipo' => 'ExcepcionETA', 'Variable' => '', 'Opcional' => 'Si');
        endif;

        if ($nombreAccion == "Generar Transbordo"):

            //DATOS TRANSITO, COMPROBAREMOS LA TABLA ORDEN_TRANSPORTE_TRANSBORDO
            $arrayCampos['Datos Transito'] = array('Campo' => 'ORDEN_TRANSPORTE_TRANSBORDO', 'Rol' => 'Forwarder', 'Tipo' => 'ExcepcionTransbordo', 'Variable' => '', 'Opcional' => 'Si');
        endif;

        if ($nombreAccion == "Confirmar Llegada Buque"):

            //DATOS TRANSITO, COMPROBAREMOS LA TABLA ORDEN_TRANSPORTE_TRANSBORDO
            $arrayCampos['Datos Transito Buque'] = array('Campo' => 'ORDEN_TRANSPORTE_TRANSBORDO', 'Rol' => 'Forwarder', 'Tipo' => 'ExcepcionBuques', 'Variable' => '', 'Opcional' => 'Si');
            $arrayCampos['Datos Transito Avion'] = array('Campo' => 'ORDEN_TRANSPORTE_TRANSBORDO', 'Rol' => 'Forwarder', 'Tipo' => 'ExcepcionAviones', 'Variable' => '', 'Opcional' => 'Si');
        endif;

        if ($nombreAccion == "Introducir Operador Transporte"):
            $arrayCampos['Operador Transporte Inland Destino']          = array('Campo' => 'ID_AGENCIA', 'Rol' => 'Forwarder', 'Tipo' => 'Proveedor', 'Variable' => 'OperadorTransporte');
            $arrayCampos['Operador Transporte Inland Destino']['Tabla'] = "OT";//INDICAMOS QUE EL CAMPO VA CONTRA LA TABLA ORDEN TRANSPORTE
        endif;


        if ($nombreAccion == "Modificar Documentacion"):

            $arrayCampos['Packing List'] = array('Campo' => 'ADJUNTO_PACKING_PROVEEDOR', 'Rol' => 'Proveedor del Material', 'Tipo' => 'Adjunto', 'Variable' => 'packing');

            $arrayCampos['Certificado Origen']        = array('Campo' => 'ADJUNTO_CERTIFICADO_ORIGEN', 'Rol' => 'Proveedor del Material', 'Tipo' => 'Adjunto', 'Variable' => 'certificado_origen');
            $arrayCampos['Certificado Fitosanitario'] = array('Campo' => 'ADJUNTO_CERTIFICADO_FITOSANITARIO', 'Rol' => 'Proveedor del Material', 'Tipo' => 'Adjunto', 'Variable' => 'fitosanitario');

            $arrayCampos['Estado Material'] = array('Campo' => 'ESTADO_MATERIAL', 'Rol' => 'Proveedor del Material', 'Tipo' => 'Select', 'Variable' => 'EstadoMaterial');
            $arrayCampos['Tipo Contenido']  = array('Campo' => 'TIPO_CONTENIDO', 'Rol' => 'Proveedor del Material', 'Tipo' => 'Select', 'Variable' => 'TipoContenido');

            $arrayCampos['Componente Usado']         = array('Campo' => 'NUMERO_FACTURA_COMPONENTE_USADO', 'Rol' => 'Proveedor del Material', 'Tipo' => 'Texto', 'Variable' => 'FacturaComponenteUsado');
            $arrayCampos['Adjunto Componente Usado'] = array('Campo' => 'ADJUNTO_FACTURA_COMPONENTE_USADO', 'Rol' => 'Proveedor del Material', 'Tipo' => 'Adjunto', 'Variable' => 'componente_usado');

            $arrayCampos['Componente Nuevo']         = array('Campo' => 'NUMERO_FACTURA_COMPONENTE_NUEVO', 'Rol' => 'Proveedor del Material', 'Tipo' => 'Texto', 'Variable' => 'FacturaComponenteNuevo');
            $arrayCampos['Adjunto Componente Nuevo'] = array('Campo' => 'ADJUNTO_FACTURA_COMPONENTE_NUEVO', 'Rol' => 'Proveedor del Material', 'Tipo' => 'Adjunto', 'Variable' => 'componente_nuevo');

            $arrayCampos['Util Usado']         = array('Campo' => 'NUMERO_FACTURA_UTIL_USADO', 'Rol' => 'Proveedor del Material', 'Tipo' => 'Texto', 'Variable' => 'FacturaUtilUsado');
            $arrayCampos['Adjunto Util Usado'] = array('Campo' => 'ADJUNTO_FACTURA_UTIL_USADO', 'Rol' => 'Proveedor del Material', 'Tipo' => 'Adjunto', 'Variable' => 'util_usado');

            $arrayCampos['Util Nuevo']         = array('Campo' => 'NUMERO_FACTURA_UTIL_NUEVO', 'Rol' => 'Proveedor del Material', 'Tipo' => 'Texto', 'Variable' => 'FacturaUtilNuevo');
            $arrayCampos['Adjunto Util Nuevo'] = array('Campo' => 'ADJUNTO_FACTURA_UTIL_NUEVO', 'Rol' => 'Proveedor del Material', 'Tipo' => 'Adjunto', 'Variable' => 'util_nuevo');

            //Tipo "ImporteMoneda" SIRVE PARA EL CAMPO IMPORTE_COMPONENTE_USADO e ID_MONEDA_COMPONENTE_USADO
            $arrayCampos['Importe Componente Usado'] = array('Campo' => 'COMPONENTE_USADO', 'Rol' => 'Proveedor del Material', 'Tipo' => 'ImporteMoneda', 'Variable' => 'ComponenteUsado');
            $arrayCampos['Importe Componente Nuevo'] = array('Campo' => 'COMPONENTE_NUEVO', 'Rol' => 'Proveedor del Material', 'Tipo' => 'ImporteMoneda', 'Variable' => 'ComponenteNuevo');
            $arrayCampos['Importe Util Usado']       = array('Campo' => 'UTIL_USADO', 'Rol' => 'Proveedor del Material', 'Tipo' => 'ImporteMoneda', 'Variable' => 'UtilUsado');
            $arrayCampos['Importe Util Nuevo']       = array('Campo' => 'UTIL_NUEVO', 'Rol' => 'Proveedor del Material', 'Tipo' => 'ImporteMoneda', 'Variable' => 'ComponenteNuevo');

        endif;

        if ($nombreAccion == "Añadir CMR"):
            $arrayCampos['Nº CMR']          = array('Campo' => 'NUMERO_CMR', 'Rol' => 'Forwarder', 'Tipo' => 'Texto', 'Variable' => 'NumeroCMR');
            $arrayCampos['Nº CMR']['Tabla'] = "Ampliada";//INDICAMOS QUE EL CAMPO VA CONTRA LA TABLA AMPLIADA

            $arrayCampos['Adjunto CMR']          = array('Campo' => 'ADJUNTO_CMR', 'Rol' => 'Forwarder', 'Tipo' => 'Adjunto', 'Variable' => 'cmr');
            $arrayCampos['Adjunto CMR']['Tabla'] = "Ampliada";//INDICAMOS QUE EL CAMPO VA CONTRA LA TABLA AMPLIADA
        endif;

        if ($nombreAccion == "Revisar CMR"):
            $arrayCampos['Nº CMR']          = array('Campo' => 'NUMERO_CMR', 'Rol' => 'Forwarder', 'Tipo' => 'Texto', 'Variable' => 'NumeroCMR');
            $arrayCampos['Nº CMR']['Tabla'] = "Ampliada";//INDICAMOS QUE EL CAMPO VA CONTRA LA TABLA AMPLIADA

            $arrayCampos['Adjunto CMR']          = array('Campo' => 'ADJUNTO_CMR', 'Rol' => 'Forwarder', 'Tipo' => 'Adjunto', 'Variable' => 'cmr');
            $arrayCampos['Adjunto CMR']['Tabla'] = "Ampliada";//INDICAMOS QUE EL CAMPO VA CONTRA LA TABLA AMPLIADA
        endif;

        if ($nombreAccion == "Añadir Borrador BL"):

            $arrayCampos['Booking'] = array('Campo' => 'NUMERO_BOOKING', 'Rol' => 'Forwarder', 'Tipo' => 'Texto', 'Variable' => 'Booking');

            $arrayCampos['Nº BL Master']       = array('Campo' => 'NUMERO_BL', 'Rol' => 'Forwarder', 'Tipo' => 'Texto', 'Variable' => 'NumeroBL');
            $arrayCampos['Borrador BL Master'] = array('Campo' => 'ADJUNTO_BORRADOR_BL', 'Rol' => 'Forwarder', 'Tipo' => 'Adjunto', 'Variable' => 'borrador_master');

            $arrayCampos['Nº BL House']       = array('Campo' => 'NUMERO_BL_HOUSE', 'Rol' => 'Forwarder', 'Tipo' => 'Texto', 'Variable' => 'NumeroBLHouse');
            $arrayCampos['Borrador BL House'] = array('Campo' => 'ADJUNTO_BORRADOR_BL_HOUSE', 'Rol' => 'Forwarder', 'Tipo' => 'Adjunto', 'Variable' => 'borrador_house');

        endif;

        if ($nombreAccion == "Revisar Borrador BL"):

            $arrayCampos['Booking'] = array('Campo' => 'NUMERO_BOOKING', 'Rol' => 'Forwarder', 'Tipo' => 'Texto', 'Variable' => 'Booking');

            $arrayCampos['Nº BL Master']       = array('Campo' => 'NUMERO_BL', 'Rol' => 'Forwarder', 'Tipo' => 'Texto', 'Variable' => 'NumeroBL');
            $arrayCampos['Borrador BL Master'] = array('Campo' => 'ADJUNTO_BORRADOR_BL', 'Rol' => 'Forwarder', 'Tipo' => 'Adjunto', 'Variable' => 'borrador_master');

            $arrayCampos['Nº BL House']       = array('Campo' => 'NUMERO_BL_HOUSE', 'Rol' => 'Forwarder', 'Tipo' => 'Texto', 'Variable' => 'NumeroBLHouse');
            $arrayCampos['Borrador BL House'] = array('Campo' => 'ADJUNTO_BORRADOR_BL_HOUSE', 'Rol' => 'Forwarder', 'Tipo' => 'Adjunto', 'Variable' => 'borrador_house');

        endif;

        if ($nombreAccion == "Añadir BL Definitivo"):

            $arrayCampos['Nº BL Master']        = array('Campo' => 'NUMERO_BL', 'Rol' => 'Forwarder', 'Tipo' => 'Texto', 'Variable' => 'NumeroBL');
            $arrayCampos['Documento BL Master'] = array('Campo' => 'ADJUNTO_BL', 'Rol' => 'Forwarder', 'Tipo' => 'Adjunto', 'Variable' => 'bl_master');

            $arrayCampos['Nº BL House']        = array('Campo' => 'NUMERO_BL_HOUSE', 'Rol' => 'Forwarder', 'Tipo' => 'Texto', 'Variable' => 'NumeroBLHouse');
            $arrayCampos['Documento BL House'] = array('Campo' => 'ADJUNTO_BL_HOUSE', 'Rol' => 'Forwarder', 'Tipo' => 'Adjunto', 'Variable' => 'bl_house');

        endif;

        if ($nombreAccion == "Revisar BL Definitivo"):

            $arrayCampos['Nº BL Master']        = array('Campo' => 'NUMERO_BL', 'Rol' => 'Forwarder', 'Tipo' => 'Texto', 'Variable' => 'NumeroBL');
            $arrayCampos['Documento BL Master'] = array('Campo' => 'ADJUNTO_BL', 'Rol' => 'Forwarder', 'Tipo' => 'Adjunto', 'Variable' => 'bl_master');

            $arrayCampos['Nº BL House']        = array('Campo' => 'NUMERO_BL_HOUSE', 'Rol' => 'Forwarder', 'Tipo' => 'Texto', 'Variable' => 'NumeroBLHouse');
            $arrayCampos['Documento BL House'] = array('Campo' => 'ADJUNTO_BL_HOUSE', 'Rol' => 'Forwarder', 'Tipo' => 'Adjunto', 'Variable' => 'bl_house');

        endif;

        if ($nombreAccion == "Aceptar/Rechazar Documentacion Proveedor"):
            $arrayCampos['Aceptacion Documentacion Proveedor'] = array('Campo' => 'ACEPTACION_DOCUMENTACION_PROVEEDOR', 'Rol' => 'Agente Aduanal', 'Tipo' => 'AceptarRechazar', 'Variable' => 'AceptacionDocumentacionProveedor');
        endif;

        if ($nombreAccion == "Aceptar/Rechazar CMR"):
            $arrayCampos['Aceptacion Borrador BL'] = array('Campo' => 'ACEPTACION_CMR', 'Rol' => 'Agente Aduanal', 'Tipo' => 'AceptarRechazar', 'Variable' => 'AceptacionCMR');
        endif;

        if ($nombreAccion == "Aceptar/Rechazar Borrador BL"):
            $arrayCampos['Aceptacion Borrador BL'] = array('Campo' => 'ACEPTACION_BORRADOR_BL', 'Rol' => 'Agente Aduanal', 'Tipo' => 'AceptarRechazar', 'Variable' => 'AceptacionBorradorBL');
        endif;

        if ($nombreAccion == "Aceptar/Rechazar BL Definitivo"):
            $arrayCampos['Aceptacion BL Definitivo'] = array('Campo' => 'ACEPTACION_BL_DEFINITIVO', 'Rol' => 'Agente Aduanal', 'Tipo' => 'AceptarRechazar', 'Variable' => 'AceptacionBLDefinitivo');
        endif;

        if ($nombreAccion == "Confirmar Peticion Provision Fondos"):

            $arrayCampos['Importe Arancel']    = array('Campo' => 'ARANCEL', 'Rol' => 'Agente Aduanal', 'Tipo' => 'ImporteMoneda', 'Variable' => 'Arancel');
            $arrayCampos['Importe IVA Aduana'] = array('Campo' => 'IVA_ADUANA', 'Rol' => 'Agente Aduanal', 'Tipo' => 'ImporteMoneda', 'Variable' => 'IvaAduana');
        endif;

        if ($nombreAccion == "Marcar Situacion Semaforo"):
            $arrayCampos['Semaforo Aduana'] = array('Campo' => 'SEMAFORO_ADUANA', 'Rol' => 'Agente Aduanal', 'Tipo' => 'RadioButton', 'Variable' => 'SemaforoAduana');
        endif;

        if ($nombreAccion == "Rellenar Datos Aduana"):

            //SI EL SEMAFORO ES ROJO O AMARILLO, SE PIDE FECHA_ESTIMADA_RESOLUCION_ADUANA
            $arrayCampos['Fecha Estimada Resolucion'] = array('Campo' => 'FECHA_ESTIMADA_RESOLUCION_ADUANA', 'Rol' => 'Agente Aduanal', 'Tipo' => 'Fecha', 'Variable' => 'ResolucionAduana');

            $arrayCampos['Fecha Estimada Levante Material'] = array('Campo' => 'FECHA_ESTIMADA_LEVANTE_MATERIAL', 'Rol' => 'Agente Aduanal', 'Tipo' => 'Fecha', 'Variable' => 'EstimadaLevante');

            $arrayCampos['Lugar Levante Material'] = array('Campo' => 'ID_DIRECCION_LEVANTE_MATERIAL', 'Rol' => 'Agente Aduanal', 'Tipo' => 'Direccion', 'Variable' => 'DireccionLevante');

            $arrayCampos['Numero Expediente']          = array('Campo' => 'NUMERO_EXPEDIENTE', 'Rol' => 'Agente Aduanal', 'Tipo' => 'Texto', 'Variable' => 'NumeroExpediente');
            $arrayCampos['Numero Expediente']['Tabla'] = "Ampliada";
        endif;

        if ($nombreAccion == "Confirmar Levante Material"):
            //Tipo "ImporteMoneda" SIRVE PARA EL CAMPO IMPORTE_COMPONENTE_USADO e ID_MONEDA_COMPONENTE_USADO
            $arrayCampos['Importe Final Arancel Abonado Aduana']          = array('Campo' => 'FINAL_ARANCEL_ABONADO_ADUANA', 'Rol' => 'Importe Final Arancel Abonado Aduana', 'Tipo' => 'ImporteMoneda', 'Variable' => 'FinalArancelAbonadoAduana');
            $arrayCampos['Importe Final Arancel Abonado Aduana']['Tabla'] = "Ampliada";

            $arrayCampos['Importe Final IVA Abonado Aduana']          = array('Campo' => 'FINAL_IVA_ABONADO_ADUANA', 'Rol' => 'Importe Final IVA Abonado Aduana', 'Tipo' => 'ImporteMoneda', 'Variable' => 'FinalIvaAbonadoAduana');
            $arrayCampos['Importe Final IVA Abonado Aduana']['Tabla'] = "Ampliada";

            $arrayCampos['Importe Abonado Aduana'] = array('Campo' => 'ABONADO_ADUANA', 'Rol' => 'Agente Aduanal', 'Tipo' => 'ImporteMoneda', 'Variable' => 'AbonadoAduana');

            $arrayCampos['Justificante Abono Aduana'] = array('Campo' => 'ADJUNTO_ABONACION_ADUANA', 'Rol' => 'Agente Aduanal', 'Tipo' => 'Adjunto', 'Variable' => 'abonoAduana');

            $arrayCampos['DUA Importacion'] = array('Campo' => 'ADJUNTO_DUA_IMPORTACION', 'Rol' => 'Agente Aduanal', 'Tipo' => 'Adjunto', 'Variable' => 'DUA');

            $arrayCampos['Fecha Levante Material'] = array('Campo' => 'FECHA_REAL_LEVANTE_MATERIAL', 'Rol' => 'Agente Aduanal', 'Tipo' => 'Fecha', 'Variable' => 'RealLevante');

        endif;

        if ($nombreAccion == "Confirmar Entrega Mercancia a Transportista Inland"):
            $arrayCampos['Entrega a Transportista Inland'] = array('Campo' => 'ENTREGA_A_TRANSPORTISTA_INLAND', 'Rol' => 'Agente Aduanal', 'Tipo' => 'Button', 'Variable' => 'EntregaTransportistaInland');
        endif;


        return $arrayCampos;
    }

    /**
     * @param $nombreAccion
     * @return  ARRAY CON LOS FILTROS NECESARIOS PARA REALIZAR ESA ACCION MASIVA
     * Devuelve un array con los filtros obligatorios para poder realizar esa accion masiva
     */
    function getArrayFiltrosAccionMasiva($idOrdenTransporteAccion)
    {
        global $html;
        global $bd;
        global $administrador;
        global $auxiliar;

        //DECLARAMOS EL ARRAY
        $arrayFiltros = array();

        //BUSCAMOS EL AVISO ACCION
        $rowAccion    = $bd->VerReg("ORDEN_TRANSPORTE_ACCION", "ID_ORDEN_TRANSPORTE_ACCION", $idOrdenTransporteAccion);
        $nombreAccion = $rowAccion->TIPO_ACCION;

        //SIEMPRE PEDIREMOS MEDIO TRANSPORTE Y CON ADUANAS
        $arrayFiltros['MedioTransporte'] = 1;
        $arrayFiltros['ConAduana']       = 1;

        //COGEMOS LOS CAMPOS IMPLICADOS CON CADA ACCION
        if ($nombreAccion == "Cumplimentar Fecha Disponibilidad Entrega"):

            $arrayFiltros['Proveedor']                  = 1;
            $arrayFiltros['FechaDisponibilidadEntrega'] = 1;
        endif;

        if ($nombreAccion == "Confirmar Reserva Medios"):

            $arrayFiltros['Forwarder']     = 1;
            $arrayFiltros['ReservaMedios'] = 1;
        endif;

        if ($nombreAccion == "Confirmar Material Preparado"):
            $arrayFiltros['Proveedor']        = 1;
            $arrayFiltros['EstadoTransporte'] = 1;
        endif;

        if ($nombreAccion == "Confirmar Envio Contenedor Vacio a Recogida"):
            $arrayFiltros['Forwarder']            = 1;
            $arrayFiltros['EnvioContenedorVacio'] = 1;
        endif;

        if ($nombreAccion == "Confirmar Entrega a Forwarder"):
            $arrayFiltros['Proveedor']        = 1;
            $arrayFiltros['EstadoTransporte'] = 1;
        endif;

        if ($nombreAccion == "Confirmar Recibido de Proveedor"):
            $arrayFiltros['Forwarder']        = 1;
            $arrayFiltros['EstadoTransporte'] = 1;
        endif;

        if ($nombreAccion == "Confirmar Salida Buque"):
            $arrayFiltros['Forwarder']        = 1;
            $arrayFiltros['MedioTransporte']  = 1;
            $arrayFiltros['EstadoTransporte'] = 1;
        endif;

        if ($nombreAccion == "Aceptar/Rechazar Documentacion Proveedor"):
            $arrayFiltros['Proveedor']     = 1;
            $arrayFiltros['AgenteAduanal'] = 1;
        endif;

        if ($nombreAccion == "Modificar Documentacion"):
            $arrayFiltros['Proveedor'] = 1;
        endif;

        if ($nombreAccion == "Añadir CMR"):
            $arrayFiltros['Forwarder'] = 1;
        endif;

        if ($nombreAccion == "Revisar CMR"):
            $arrayFiltros['Forwarder'] = 1;
        endif;

        if ($nombreAccion == "Añadir Borrador BL"):
            $arrayFiltros['Forwarder'] = 1;
        endif;

        if ($nombreAccion == "Revisar Borrador BL"):
            $arrayFiltros['Forwarder'] = 1;
        endif;

        if ($nombreAccion == "Aceptar/Rechazar CMR"):
            $arrayFiltros['Forwarder']     = 1;
            $arrayFiltros['AgenteAduanal'] = 1;
        endif;

        if ($nombreAccion == "Aceptar/Rechazar Borrador BL"):
            $arrayFiltros['Forwarder']     = 1;
            $arrayFiltros['AgenteAduanal'] = 1;
        endif;

        if ($nombreAccion == "Añadir BL Definitivo"):
            $arrayFiltros['Forwarder'] = 1;
        endif;

        if ($nombreAccion == "Revisar BL Definitivo"):
            $arrayFiltros['Forwarder'] = 1;
        endif;

        if ($nombreAccion == "Aceptar/Rechazar BL Definitivo"):
            $arrayFiltros['Forwarder']     = 1;
            $arrayFiltros['AgenteAduanal'] = 1;
        endif;

        if ($nombreAccion == "Aceptar Documentacion"):
        endif;

        if ($nombreAccion == "Confirmar Peticion Provision Fondos"):
            $arrayFiltros['AgenteAduanal'] = 1;
        endif;

        if ($nombreAccion == "Confirmar Provision Fondos")://EXCEPCION, EN VEZ DE LA PANTALLA NORMAL DE ACCIONES, SE MOSTRARA LA DE APROVISIONAMIENTO DE TESORERIA
        endif;

        if ($nombreAccion == "Actualizar ETA"):
            $arrayFiltros['Forwarder']          = 1;
            $arrayFiltros['MedioTransporte']    = 1;
            $arrayFiltros['Barco']              = 1;
            $arrayFiltros['TransporteTransito'] = 1;
        endif;

        if ($nombreAccion == "Generar Transbordo"):
            $arrayFiltros['Forwarder']          = 1;
            $arrayFiltros['MedioTransporte']    = 1;
            $arrayFiltros['Barco']              = 1;
            $arrayFiltros['TransporteTransito'] = 1;
        endif;

        if ($nombreAccion == "Confirmar Llegada Buque"):
            $arrayFiltros['Forwarder']          = 1;
            $arrayFiltros['MedioTransporte']    = 1;
            $arrayFiltros['Barco']              = 1;
            $arrayFiltros['TransporteTransito'] = 1;
            $arrayFiltros['EstadoTransporte']   = 1;
        endif;

        if ($nombreAccion == "Introducir Operador Transporte"):
            $arrayFiltros['Forwarder'] = 1;
        endif;

        if ($nombreAccion == "Marcar Situacion Semaforo"):
            $arrayFiltros['AgenteAduanal'] = 1;
        endif;

        if ($nombreAccion == "Rellenar Datos Aduana"):
            $arrayFiltros['AgenteAduanal'] = 1;
        endif;

        if ($nombreAccion == "Confirmar Levante Material"):
            $arrayFiltros['AgenteAduanal'] = 1;
        endif;

        if ($nombreAccion == "Confirmar Entrega Mercancia a Transportista Inland"):
            $arrayFiltros['AgenteAduanal'] = 1;
        endif;

        if ($nombreAccion == "Confirmar Recogida Mercancia"):
        endif;

        if ($nombreAccion == "Confirmar Fecha Estimada Entrega"):
        endif;

        if ($nombreAccion == "Confirmar Recepcion Almacen Destino"):
        endif;

        if ($nombreAccion == "Confirmar Extracoste"):
        endif;

        return $arrayFiltros;
    }

    /**
     * @param $idOrdenTransporte
     * @return  ARRAY CON LOS BULTOS Y LINEAS PENDIENTES DE RECEPCIONAR
     */
    function getMaterialesPendientesTransporteConstruccion($idOrdenTransporte)
    {
        global $html;
        global $bd;
        global $administrador;
        global $auxiliar;

        //DEFINIMOS EL ARRAY
        $arrBultosPdtes = array();

        //BUSCAMOS LOS MATERIALES DE LA OT
        $sqlBultos    = "SELECT DISTINCT B.REFERENCIA_CONSTRUCCION, B.ID_BULTO, BL.ID_BULTO_LINEA, BL.ID_MATERIAL, BL.ID_MATERIAL_FISICO, BL.CANTIDAD
                        FROM BULTO B
                        INNER JOIN BULTO_LINEA BL ON BL.ID_BULTO = B.ID_BULTO
                        INNER JOIN EXPEDICION E ON E.ID_EXPEDICION = B.ID_EXPEDICION
                        WHERE E.ID_ORDEN_TRANSPORTE = $idOrdenTransporte AND B.TIPO = 'Construccion'
                        ORDER BY B.REFERENCIA_CONSTRUCCION";
        $resultBultos = $bd->ExecSQL($sqlBultos);

        while ($rowBulto = $bd->SigReg($resultBultos)):

            //BUSCAMOS SI ESTA YA EN UN MOVIMIENTO
            $sqlCantidadMovimiento    = " SELECT SUM(CANTIDAD) AS CANTIDAD_ASIGNADA
                                 FROM MOVIMIENTO_ENTRADA_LINEA MEL
                                 WHERE MEL.ID_BULTO_LINEA = $rowBulto->ID_BULTO_LINEA AND MEL.LINEA_ANULADA = 0 AND MEL.BAJA = 0";
            $resultCantidadMovimiento = $bd->ExecSQL($sqlCantidadMovimiento);

            $rowCantidadMovimiento = $bd->SigReg($resultCantidadMovimiento);

            if (($rowCantidadMovimiento != false) && ($rowCantidadMovimiento->CANTIDAD_ASIGNADA != NULL)):

                if ($rowBulto->CANTIDAD > $rowCantidadMovimiento->CANTIDAD_ASIGNADA):
                    //GUARDAMOS LO PENDIENTE DEL BULTO BULTO LINEA
                    $arrBultosPdtes[$rowBulto->ID_BULTO][$rowBulto->ID_BULTO_LINEA]['idMaterial'] = $rowBulto->ID_MATERIAL;
                    $arrBultosPdtes[$rowBulto->ID_BULTO][$rowBulto->ID_BULTO_LINEA]['Cantidad']   = $rowBulto->CANTIDAD - $rowCantidadMovimiento->CANTIDAD_ASIGNADA;
                endif;
            else:
                //GUARDAMOS EL BULTO LINEA
                $arrBultosPdtes[$rowBulto->ID_BULTO][$rowBulto->ID_BULTO_LINEA]['idMaterial'] = $rowBulto->ID_MATERIAL;
                $arrBultosPdtes[$rowBulto->ID_BULTO][$rowBulto->ID_BULTO_LINEA]['Cantidad']   = $rowBulto->CANTIDAD;
            endif;
        endwhile;


        return $arrBultosPdtes;
    }

    /**
     * @param $idOrdenTransporte TRANSPORTE A DEVOLVER
     * DEVUELVE LAS ACCIONES DE LA OT INTRODUCIDA
     */
    function getAccionesActivasOrdenTransporte($id = false, $sql_where = '')
    {

        global $bd;

        if ($id == null) {
            return false;
        } else {

            //BUSCAMOS LAS ACCIONES DE LA OT
            $sqlOrdenTransporte = "SELECT OTA.TIPO_ACCION, OTAA.ID_ORDEN_TRANSPORTE_ACCION_AVISO, OTA.ESTADO_TRANSPORTE
                                    FROM ORDEN_TRANSPORTE_ACCION OTA
                                    INNER JOIN ORDEN_TRANSPORTE_ACCION_AVISO OTAA ON OTAA.ID_ORDEN_TRANSPORTE_ACCION = OTA.ID_ORDEN_TRANSPORTE_ACCION
                                    WHERE OTAA.BAJA = 0 AND OTAA.ID_ORDEN_TRANSPORTE = $id " . $sql_where;
            $result             = $bd->ExecSQL($sqlOrdenTransporte);

            return $result;
        }
    }

    /**
     * obtiene los hitos pendientes que tiene el embarque
     * @param array $rowEmbarque
     * @return array
     */
    public function getHitosPendientes($rowEmbarque = "")
    {

        global $auxiliar;
        global $administrador;
        global $bd;

        $arr_hitos_pendientes = array();

        $i = 0;

        //SOLICITAR NOMINACIÓN
        $dias_diferencia_etd = 0;

        if ($rowEmbarque->ETD_PLANIFICADO_PUERTO_ORIGEN != ""):
            //OBTENGO LOS DÍAS DE DIFERENCIA ENTRE LA FECHA ACTUAL Y LA ETD PLANIFICADA PUERTO ORIGEN
            $dias_diferencia_etd = $this->getDiasRestaFechas(date("Y-m-d H:i:s"), $rowEmbarque->ETD_PLANIFICADO_PUERTO_ORIGEN);
        elseif ($rowEmbarque->ETD_OBJETIVO_PUERTO_ORIGEN != ""):
            //OBTENGO LOS DÍAS DE DIFERENCIA ENTRE LA FECHA ACTUAL Y LA ETD OBJETIVO PUERTO ORIGEN
            $dias_diferencia_etd = $this->getDiasRestaFechas(date("Y-m-d H:i:s"), $rowEmbarque->ETD_OBJETIVO_PUERTO_ORIGEN);
        endif;

        //OBTENGO LAS SEMANAS DE DIFERENCIA ENTRE LA FECHA ACTUAL Y LA ETD PLANIFICADA PUERTO ORIGEN
        $semanas_diferencia_etd = intdiv((int)$dias_diferencia_etd, 7) + 1;

        //SI LA DIFERENCIA DE SEMANAS ES INFERIOR A 6 AÑADO EL HITO PENDIENTE
        if ($semanas_diferencia_etd <= 6):

            //INTRODUCIR "FECHA DISPONIBILIDAD ENTREGA"
            //PRIMERO OBTENGO TODAS LAS OTS DEL EMBARQUE
            $arr_ots_embarque = $this->getOtsEmbarque($rowEmbarque->ID_EMBARQUE, '', '', 'OT.BAJA = 0');

            //RECORREMOS LAS OTS PARA COMPROBAR SI SE HA RELLENADO LA FECHA DISPONIBILIDAD ENTREGA EN TODAS LAS OTS DEL EMBARQUE
            foreach ($arr_ots_embarque as $info_ot):
                //COMPROBAMOS QUE TODAS LAS OTS DEL EMBARQUE TIENEN RESUELTA LA ACCIÓN DE INTRODUCIR LA FECHA DE DISPONIBILIDAD DE ENTREGA
                $arr_acciones_activas_fecha_disponibilidad_entrega = $this->getAccionesActivasOrdenTransporte($info_ot, " AND OTAA.ID_ORDEN_TRANSPORTE_ACCION = 1 AND OTAA.ESTADO_ACCION = 'Pendiente' ");

                if ($bd->NumRegs($arr_acciones_activas_fecha_disponibilidad_entrega) > 0):
                    //SI EL ARRAY ESTÁ VACÍO AÑADO LA ALERTA
                    $arr_hitos_pendientes[$i]['ALERTA']           = $auxiliar->traduce('Introducir Fecha disponibilidad entrega', $administrador->ID_IDIOMA);
                    $arr_hitos_pendientes[$i]['CAMPO_ACTIVACION'] = 'FECHA_ENTREGA_DISPONIBLE_PROVEEDOR';
                    $i++;

                    break;
                endif;
            endforeach;

            //CONFIRMAR INDIVIDUALMENTE MATERIAL PREPARADO
            //RECORREMOS LAS OTS PARA COMPROBAR SI SE HA RELLENADO LA FECHA DISPONIBILIDAD ENTREGA EN TODAS LAS OTS DEL EMBARQUE
            foreach ($arr_ots_embarque as $info_ot):
                //COMPROBAMOS QUE TODAS LAS OTS DEL EMBARQUE TIENEN RESUELTA LA ACCIÓN DE INTRODUCIR LA FECHA DE DISPONIBILIDAD DE ENTREGA
                $arr_acciones_activas_confirmar_material_preparado = $this->getAccionesActivasOrdenTransporte($info_ot, " AND OTAA.ID_ORDEN_TRANSPORTE_ACCION = 3 AND OTAA.ESTADO_ACCION = 'Pendiente' ");

                if ($bd->NumRegs($arr_acciones_activas_confirmar_material_preparado) > 0):
                    //SI EL ARRAY ESTÁ VACÍO AÑADO LA ALERTA
                    $arr_hitos_pendientes[$i]['ALERTA']           = $auxiliar->traduce('Confirmar Individualmente material preparado', $administrador->ID_IDIOMA);
                    $arr_hitos_pendientes[$i]['CAMPO_ACTIVACION'] = 'MATERIAL_PREPARADO';
                    $i++;

                    break;
                endif;
            endforeach;

            //CONFIRMAR MATERIAL PREPARADO
            if ($rowEmbarque->PLANIFICACION_ENTREGA_PROVEEDOR != 1):
                //SI NO ESTÁ MARCADO EL CHECK DE PLANIFICACIÓN ENTREGA PROVEEDOR, AÑADIMOS LA ALERTA
                $arr_hitos_pendientes[$i]['ALERTA']           = $auxiliar->traduce('Confirmar Material Preparado', $administrador->ID_IDIOMA);
                $arr_hitos_pendientes[$i]['CAMPO_ACTIVACION'] = 'PLANIFICACION_ENTREGA_PROVEEDOR';
                $i++;
            endif;

            //INTRODUCIR TERMINAL DE CARGA
            if ($rowEmbarque->ID_TERMINAL_PUERTO_ORIGEN == ""):
                //SI NO SE HA INTRODUCIDO LA TERMINAL DE CARGA MUESTRO LA ALERTA
                $arr_hitos_pendientes[$i]['ALERTA']           = $auxiliar->traduce('Introducir terminal de carga', $administrador->ID_IDIOMA);
                $arr_hitos_pendientes[$i]['CAMPO_ACTIVACION'] = 'ID_PUERTO_ORIGEN';
                $i++;
            endif;

            //ADJUNTAR CERTIFICADOS DE ÚTILES
            $sql_where_certificados_utiles = " WHERE F.TIPO_OBJETO='EmbarqueGC' AND F.ID_EMBARQUE_GC=" . $rowEmbarque->ID_EMBARQUE . " AND F.SECCION='CertificadoUtiles' ";

            //OBTENGO EL NÚMERO DE CERTIFICADOS DE ÚTILES DEL EMBARQUE
            $num_certificados_utiles = $this->getNumFicheros($sql_where_certificados_utiles);

            //SI EL NÚMERO DE CERTIFICADOS DE ÚTILES ES CERO MUESTRO EL HITO COMO PENDIENTE
            if ($num_certificados_utiles->NUM_FICHEROS == '0'):
                $arr_hitos_pendientes[$i]['ALERTA']           = $auxiliar->traduce('Adjuntar Certificados de utiles', $administrador->ID_IDIOMA);
                $arr_hitos_pendientes[$i]['CAMPO_ACTIVACION'] = 'CERTIFICADOS_UTILES';
                $i++;
            endif;

            if ($semanas_diferencia_etd <= 5):
                if ($rowEmbarque->NOMINACION_SOLICITADA != 1):
                    $arr_hitos_pendientes[$i]['ALERTA']           = $auxiliar->traduce('Solicitar nominacion', $administrador->ID_IDIOMA);
                    $arr_hitos_pendientes[$i]['CAMPO_ACTIVACION'] = 'ETD';
                    $i++;
                endif;

                if ($rowEmbarque->CONFIRMACION_NOMINACION_TA != 1):
                    $arr_hitos_pendientes[$i]['ALERTA']           = $auxiliar->traduce('Confirmar nominacion', $administrador->ID_IDIOMA);
                    $arr_hitos_pendientes[$i]['CAMPO_ACTIVACION'] = 'NOMINACION_SOLICITADA';
                    $i++;
                endif;

                //INFORMAR EL ETA PLANIFICADO Y CUT OFF DOCUMENTAL
                //SI LA DIFERENCIA DE SEMANAS ES INFERIOR A 2 AÑADO EL HITO PENDIENTE
                if ($semanas_diferencia_etd <= 2):
                    //SI EL ETA PUERTO ORIGEN PLANIFICADO O EL LÍMITE DE DESPACHO EXPORTACIÓN NO SE HAN RELLENADO, MUESTRO EL HITO COMO PENDIENTE
                    if (($rowEmbarque->ETA_PUERTO_ORIGEN_PLANIFICADO == "") || ($rowEmbarque->LIMITE_DESPACHO_EXPORTACION == "")):
                        $arr_hitos_pendientes[$i]['ALERTA']           = $auxiliar->traduce('Informar el ETA planificado y cut off documental', $administrador->ID_IDIOMA);
                        $arr_hitos_pendientes[$i]['CAMPO_ACTIVACION'] = 'ETD';
                        $i++;
                    endif;

                    if ($semanas_diferencia_etd <= 1):
                        //CONFIRMAR DESPACHO DE COMPONENTES
                        if ($rowEmbarque->CONFIRMAR_DESPACHO_COMPONENTES != 1):
                            //SI NO ESTÁ MARCADO EL DESPACHO DE COMPONENTES MUESTRO LA ALERTA
                            $arr_hitos_pendientes[$i]['ALERTA']           = $auxiliar->traduce('Confirmar despacho de componentes', $administrador->ID_IDIOMA);
                            $arr_hitos_pendientes[$i]['CAMPO_ACTIVACION'] = 'CONFIRMAR_DESPACHO_COMPONENTES';
                            $i++;
                        endif;

                        //ADJUNTAR DOCUMENTACIÓN DE DESPACHO DE COMPONENTES
                        $sql_where_exportacion_componentes = " WHERE F.TIPO_OBJETO='EmbarqueGC' AND F.ID_EMBARQUE_GC=" . $rowEmbarque->ID_EMBARQUE . " AND F.SECCION='DUAsExportacionComponentes' ";
                        //OBTENGO EL NÚMERO DE DOCUMENTOS DE EXPORTACIÓN DE COMPONENTES DEL EMBARQUE
                        $num_exportacion_componentes = $this->getNumFicheros($sql_where_exportacion_componentes);

                        //SI EL NÚMERO DE DOCUMENTOS DE EXPORTACIÓN DE COMPONENTES ES CERO MUESTRO EL HITO COMO PENDIENTE
                        if ($num_exportacion_componentes->NUM_FICHEROS == '0'):
                            $arr_hitos_pendientes[$i]['ALERTA']           = $auxiliar->traduce('Adjuntar documentacion de despacho de componentes', $administrador->ID_IDIOMA);
                            $arr_hitos_pendientes[$i]['CAMPO_ACTIVACION'] = 'DUAS_EXPORTACION_COMPONENTES';
                            $i++;
                        endif;

                        //AÑADIR INFORMACIÓN Y DOCUMENTOS DE FACTURAS, PACKING LISTS, CERTIFICADOS, ETC
                        if ($rowEmbarque->ESTADO_DOCUMENTACION_PROVEEDOR == 'Pendiente'):
                            //SI EL ESTADO DE LA DOCUMENTACIÓN DEL PROVEEDOR ESTÁ ENJ ESTADO PENDIENTE MUESRO LA ALERTA
                            $arr_hitos_pendientes[$i]['ALERTA']           = $auxiliar->traduce('Añadir informacion y documentos de facturas, packing lists, certificados, etc', $administrador->ID_IDIOMA);
                            $arr_hitos_pendientes[$i]['CAMPO_ACTIVACION'] = 'DOCUMENTACION_PROVEEDOR';
                            $i++;
                        endif;
                    endif;
                endif;
            endif;
        endif;

        //SI SE SOLICITA LA NOMINACIÓN DEL EMBARQUE, ACTIVAMOS LOS HITOS NECESARIOS
        if ($rowEmbarque->NOMINACION_SOLICITADA == 1):
            //SI EL HITO ANTERIOR NO SE MUESTRA COMPRUEBO SI EL TÉCNICO DE ACCIONA TIENE EL RESTO DE HITOS PENDIENTES
            //EMPIEZO POR LAS INSTRUCCIONES BL
            $sql_where_instrucciones_bl = " WHERE F.TIPO_OBJETO='EmbarqueGC' AND F.ID_EMBARQUE_GC=" . $rowEmbarque->ID_EMBARQUE . " AND F.SECCION='InstruccionesBL' ";

            //OBTENGO EL NÚMERO DE INSTRUCCIONES BL DEL EMBARQUE
            $num_instrucciones_bl = $this->getNumFicheros($sql_where_instrucciones_bl);

            //SI EL NÚMERO DE INSTRUCCIONES BL ES CERO MUESTRO EL HITO COMO PENDIENTE
            if ($num_instrucciones_bl->NUM_FICHEROS == '0'):
                $arr_hitos_pendientes[$i]['ALERTA']           = $auxiliar->traduce('Adjuntar instrucciones BL', $administrador->ID_IDIOMA);
                $arr_hitos_pendientes[$i]['CAMPO_ACTIVACION'] = 'INSTRUCCIONES_BL';
                $i++;
            endif;

            //INSTRUCCIONES DOCUMENTACIÓN DEL PROVEEDOR
            $sql_where_instrucciones_documentacion_proveedor = " WHERE F.TIPO_OBJETO='EmbarqueGC' AND F.ID_EMBARQUE_GC=" . $rowEmbarque->ID_EMBARQUE . " AND F.SECCION='InstruccionesDocumentacionProveedor' ";

            //OBTENGO EL NÚMERO DE INSTRUCCIONES DOCUMENTACIÓN PROVEEDOR DEL EMBARQUE
            $num_instrucciones_documentacion_proveedor = $this->getNumFicheros($sql_where_instrucciones_documentacion_proveedor);

            //SI EL NÚMERO DE INSTRUCCIONES BL ES CERO MUESTRO EL HITO COMO PENDIENTE
            if ($num_instrucciones_documentacion_proveedor->NUM_FICHEROS == '0'):
                $arr_hitos_pendientes[$i]['ALERTA']           = $auxiliar->traduce('Adjuntar instrucciones documentacion proveedor', $administrador->ID_IDIOMA);
                $arr_hitos_pendientes[$i]['CAMPO_ACTIVACION'] = 'INSTRUCCIONES_PROVEEDOR';
                $i++;
            endif;

            //INSTRUCCIONES ITM'S'
            //EMPIEZO OBTENIENDO LAS ITMS DEL EMBARQUE
            $result_itms = $this->getItmsEmbarque($rowEmbarque->ID_EMBARQUE);

            if (count((array)$result_itms) == 0):
                $arr_hitos_pendientes[$i]['ALERTA']           = $auxiliar->traduce('Adjuntar instrucciones ITMs (al menos 1)', $administrador->ID_IDIOMA);
                $arr_hitos_pendientes[$i]['CAMPO_ACTIVACION'] = 'ITMS';
                $i++;
            endif;

            //COMPROBAMOS SI EL EMBARQUE TIENE PLANO DE ESTIBA PLANIFICADO
            $sql_where_plano_estiba_planificado = " WHERE F.TIPO_OBJETO='EmbarqueGC' AND F.ID_EMBARQUE_GC=" . $rowEmbarque->ID_EMBARQUE . " AND F.SECCION='PlanoEstibaPlanificado' ";

            //OBTENGO EL NÚMERO DE INSTRUCCIONES DOCUMENTACIÓN PROVEEDOR DEL EMBARQUE
            $num_planos_estiba_planificados = $this->getNumFicheros($sql_where_plano_estiba_planificado);

            if (($rowEmbarque->ID_BARCO == "") || ($rowEmbarque->ID_NAVIERA == "") || ($rowEmbarque->LAYCAN_PLANIFICADO_INICIO == "") || ($rowEmbarque->LAYCAN_PLANIFICADO_FIN == "") || ($rowEmbarque->USO_MEDIOS_ADICIONALES == "") || ($num_planos_estiba_planificados->NUM_FICHEROS == '0')):
                //SI ALGÚN CAMPO DE LOS MENCIONADOS NO SE RELLENA, SE MUESTRA EL HITO COMO PENDIENTE
                $arr_hitos_pendientes[$i]['ALERTA']           = $auxiliar->traduce('Informacion de nominacion (Buque, naviera, Laycan de inicio y fin planificados, uso medios adicionales, plano estiba planificado)', $administrador->ID_IDIOMA);
                $arr_hitos_pendientes[$i]['CAMPO_ACTIVACION'] = 'INFORMACION_NOMINACION';
                $i++;
            endif;

            //INTRODUCIR LAYCAN OBJETIVO INICIO Y FIN
            if ($rowEmbarque->LAYCAN_OBJETIVO_INICIO == "" || $rowEmbarque->LAYCAN_OBJETIVO_FIN == ""):
                //SI ALGUNO DE ESTOS DOS CAMPOS NO ESTÁ RELLENO, AÑADO LA ALERTA
                $arr_hitos_pendientes[$i]['ALERTA']           = $auxiliar->traduce('Introducir Laycan Objetivo inicio y fin', $administrador->ID_IDIOMA);
                $arr_hitos_pendientes[$i]['CAMPO_ACTIVACION'] = 'LAYCAN_OBJETIVO';
                $i++;
            endif;
        endif;

        //REVISAR DOCUMENTACIÓN (PROVEEDOR)
        if ($rowEmbarque->ESTADO_DOCUMENTACION_PROVEEDOR == 'Rechazada'):
            //SI EL ESTADO DE LA DOCUMENTACIÓN ES "RECHAZADA" ACTIVO LA ALERTA
            $arr_hitos_pendientes[$i]['ALERTA']           = $auxiliar->traduce('Revisar documentacion', $administrador->ID_IDIOMA);
            $arr_hitos_pendientes[$i]['CAMPO_ACTIVACION'] = 'ESTADO_DOCUMENTACION_PROVEEDOR';
            $i++;
        endif;

        //REVISAR DOCUMENTACIÓN (AGENTE ADUANAL)
        if (($rowEmbarque->ESTADO_DOCUMENTACION_PROVEEDOR != 'Pendiente' && $rowEmbarque->ESTADO_DOCUMENTACION_BORRADOR_FORWARDER != 'Pendiente')
            && ($rowEmbarque->ESTADO_DOCUMENTACION_PROVEEDOR == 'En Revision' || $rowEmbarque->ESTADO_DOCUMENTACION_BORRADOR_FORWARDER == 'En Revision' || $rowEmbarque->ESTADO_DOCUMENTACION_DEFINITIVA_FORWARDER == 'En Revision')
        ):
            //SI EL ESTADO DE LA DOCUMENTACIÓN ES "RECHAZADA" ACTIVO LA ALERTA
            $arr_hitos_pendientes[$i]['ALERTA']           = $auxiliar->traduce('Revisar documentacion', $administrador->ID_IDIOMA);
            $arr_hitos_pendientes[$i]['CAMPO_ACTIVACION'] = 'ESTADO_DOCUMENTACION_DEFINITIVA_AGENTE_ADUANAL';
            $i++;
        endif;

        $dias_diferencia_eta = 0;

        if ($rowEmbarque->ETA_PUERTO_ORIGEN_PLANIFICADO != ""):
            //OBTENGO LOS DÍAS DE DIFERENCIA ENTRE LA FECHA ACTUAL Y LA ETD PLANIFICADA PUERTO ORIGEN
            $dias_diferencia_eta = $this->getDiasRestaFechas(date("Y-m-d H:i:s"), $rowEmbarque->ETA_PUERTO_ORIGEN_PLANIFICADO);
        elseif ($rowEmbarque->ETA_PUERTO_ORIGEN_OBJETIVO != ""):
            //OBTENGO LOS DÍAS DE DIFERENCIA ENTRE LA FECHA ACTUAL Y LA ETD OBJETIVO PUERTO ORIGEN
            $dias_diferencia_eta = $this->getDiasRestaFechas(date("Y-m-d H:i:s"), $rowEmbarque->ETA_PUERTO_ORIGEN_OBJETIVO);
        endif;

        if ($dias_diferencia_eta <= 7):
            //SI QUEDA UNA SEMANA PARA LA ETA PLANIFICADO PUERTO ORIGEN ACTIVO LA ALERTA
            //OBTENGO EL PERSONAL DE ASISTENCIA EN LA CARGA DEL EMBARQUE
            $res_personal_asistencia_carga = $this->getPersonalAsistencia($rowEmbarque->ID_EMBARQUE, 'Carga');

            if (count((array)$res_personal_asistencia_carga) == 0):
                //SI EL EMBARQUE NO TIENE PERSONAL DE ASISTENCIA DE CARGA SALTA LA ALARMA
                $arr_hitos_pendientes[$i]['ALERTA']           = $auxiliar->traduce('Añadir personal de asistencia a la carga', $administrador->ID_IDIOMA);
                $arr_hitos_pendientes[$i]['CAMPO_ACTIVACION'] = 'PERSONAL_ASISTENCIA';
                $i++;
            endif;

            if ($dias_diferencia_eta <= 5):
                //SI HAY 5 DÍAS DE DIFERENCIA O MENOS COMPRUEBO SI SE HA RELLENADO LA FECHA COMIENZO DE CARGA PLANIFICADA
                if ($rowEmbarque->FECHA_COMIENZO_CARGA_PLANIFICADA == ""):
                    //SI NO SE HA RELLENADO MUESTRO EL HITO COMO PENDIENTE
                    $arr_hitos_pendientes[$i]['ALERTA']           = $auxiliar->traduce('Informar fecha/hora planificada de comienzo de carga', $administrador->ID_IDIOMA);
                    $arr_hitos_pendientes[$i]['CAMPO_ACTIVACION'] = 'FECHA_COMIENZO_CARGA_PLANIFICADA';
                    $i++;
                endif;

                //SI HAY 5 DÍAS DE DIFERENCIA O MENOS COMPRUEBO SI SE HA INTRODUCIDO AL MENOS UNA ACREDITACIÓN DE ENTRADA EN PUERTO Y DE ACCESO BUQUE
                //OBTENEMOS LAS ACREDITACIONES DEL EMBARQUE
                $sql_where_acreditaciones_entrada_puerto = " WHERE F.TIPO_OBJETO='EmbarqueGC' AND F.ID_EMBARQUE_GC=" . $rowEmbarque->ID_EMBARQUE . " AND F.SECCION='AcreditacionesEntradaPuertoCarga' ";
                $sql_where_acreditaciones_acceso_buque   = " WHERE F.TIPO_OBJETO='EmbarqueGC' AND F.ID_EMBARQUE_GC=" . $rowEmbarque->ID_EMBARQUE . " AND F.SECCION='AcreditacionesAccesoBuqueCarga' ";

                //OBTENGO EL NÚMERO DE ACREDITACIONES DE ENTRADA A PUERTO DEL EMBARQUE
                $num_acreditaciones_entrada_puerto = $this->getNumFicheros($sql_where_acreditaciones_entrada_puerto);
                //OBTENGO EL NÚMERO DE ACREDITACIONES DE ACCESO BUQUE DEL EMBARQUE
                $num_acreditaciones_acceso_buque = $this->getNumFicheros($sql_where_acreditaciones_acceso_buque);

                if (($num_acreditaciones_entrada_puerto->NUM_FICHEROS == '0') || ($num_acreditaciones_acceso_buque->NUM_FICHEROS == '0')):
                    //SI ALGÚN CAMPO DE LOS MENCIONADOS NO SE RELLENA, SE MUESTRA EL HITO COMO PENDIENTE
                    $arr_hitos_pendientes[$i]['ALERTA']           = $auxiliar->traduce('Adjuntar acreditaciones personal en carga', $administrador->ID_IDIOMA);
                    $arr_hitos_pendientes[$i]['CAMPO_ACTIVACION'] = 'ACREDITACIONES';
                    $i++;
                endif;

                if ($dias_diferencia_eta <= 1):
                    //SI SOLAMENTE HAY UN DÍA DE DIFERENCIA COMPRUEBO SI SE HAN RELLENADO EL ATA PUERTO ORIGEN Y EL ETD PLANIFICADO PUERTO ORIGEN
                    if (($rowEmbarque->ETA_PUERTO_ORIGEN_REAL == "") || ($rowEmbarque->ETD_PLANIFICADO_PUERTO_ORIGEN == "")):
                        //SI ALGUNA FECHA ESTÁ VACÍA MOSTRAMOS EL HITO COMO PENDIENTE
                        $arr_hitos_pendientes[$i]['ALERTA']           = $auxiliar->traduce('Confirmar ATA puerto Origen y ETD planificado', $administrador->ID_IDIOMA);
                        $arr_hitos_pendientes[$i]['CAMPO_ACTIVACION'] = 'ETA_ETD';
                        $i++;
                    endif;

                    //SI HAY 1 DÍA DE DIFERENCIA O MENOS COMPRUEBO SI SE HA RELLENADO LA FECHA COMIENZO DE CARGA REAL
                    if ($rowEmbarque->FECHA_COMIENZO_CARGA_REAL == ""):
                        //SI NO SE HA RELLENADO MUESTRO EL HITO COMO PENDIENTE
                        $arr_hitos_pendientes[$i]['ALERTA']           = $auxiliar->traduce('Informar fecha/hora Real de comienzo de carga', $administrador->ID_IDIOMA);
                        $arr_hitos_pendientes[$i]['CAMPO_ACTIVACION'] = 'FECHA_COMIENZO_CARGA_REAL';
                        $i++;
                    endif;
                endif;
            endif;
        endif;

        //AÑADIR INFORMACIÓN Y DOCUMENTACIÓN DE TRANSPORTE
        if ($rowEmbarque->FECHA_COMIENZO_CARGA_REAL != ""):
            //SI SE RELLENA LA FECHA INICIO CARGA REAL SE ACTIVA LA ALERTA
            //COMPROBAMOS SI EL CAMPO "ESTADO_DOCUMENTACION_DEFINITIVA_FORWARDER" ESTÁ EN ESTADO "Aceptada"
            if (($rowEmbarque->ESTADO_DOCUMENTACION_BORRADOR_FORWARDER == "Pendiente") || ($rowEmbarque->ESTADO_DOCUMENTACION_BORRADOR_FORWARDER == "Aceptada" && $rowEmbarque->ESTADO_DOCUMENTACION_DEFINITIVA_FORWARDER == "Pendiente")):
                //SI NO ESTÁ EN ESE ESTADO MOSTRAMOS LA ALERTA
                $arr_hitos_pendientes[$i]['ALERTA']           = $auxiliar->traduce('Añadir informacion y documentos de Transporte', $administrador->ID_IDIOMA);
                $arr_hitos_pendientes[$i]['CAMPO_ACTIVACION'] = 'ESTADO_DOCUMENTACION_DEFINITIVA_FORWARDER';
                $i++;
            endif;

            //ADJUNTAR PLANOS ESTIBA DEFINITIVOS Y ESQUEMA CARGA DE PALAS
            //SI SE INTRODUCE LA FECHA REAL DE CARGA PUEDO MOSTRAR LA ALERTA
            //OBTENEMOS LOS ESQUEMAS DEFINITIVOS Y LOS PLANOS DE ESTIBA DEFINITIVOS DEL EMBARQUE
            $sql_where_esquema_definitivos       = " WHERE F.TIPO_OBJETO='EmbarqueGC' AND F.ID_EMBARQUE_GC=" . $rowEmbarque->ID_EMBARQUE . " AND F.SECCION='EsquemaDefinitivoCargaConSetPalas' ";
            $sql_where_planos_estiba_definitivos = " WHERE F.TIPO_OBJETO='EmbarqueGC' AND F.ID_EMBARQUE_GC=" . $rowEmbarque->ID_EMBARQUE . " AND F.SECCION='PlanoEstibaDefinitivo' ";

            //OBTENGO EL NÚMERO DE ESQUEMAS DEFINITIVOS DEL EMBARQUE
            $num_esquemas_definitivos = $this->getNumFicheros($sql_where_esquema_definitivos);
            //OBTENGO EL NÚMERO DE PLANOS DE ESTIBA DEFINITIVOS DEL EMBARQUE
            $num_planos_estiba_definitivos = $this->getNumFicheros($sql_where_planos_estiba_definitivos);

            if (($num_esquemas_definitivos->NUM_FICHEROS == '0') || ($num_planos_estiba_definitivos->NUM_FICHEROS == '0')):
                //SI ALGÚN CAMPO DE LOS MENCIONADOS NO SE RELLENA, SE MUESTRA EL HITO COMO PENDIENTE
                $arr_hitos_pendientes[$i]['ALERTA']           = $auxiliar->traduce('Adjuntar Planos estiba definitivos y esquema carga de palas', $administrador->ID_IDIOMA);
                $arr_hitos_pendientes[$i]['CAMPO_ACTIVACION'] = 'ESQUEMAS';
                $i++;
            endif;

            //INFORMAR FECHA/HORA REAL DE FINALIZACIÓN DE CARGA
            if ($rowEmbarque->FECHA_FIN_CARGA_REAL == ""):
                //SI NO SE RELLENA LA FECHA FIN DE CARGA SE MUESTRA LA ALERTA
                $arr_hitos_pendientes[$i]['ALERTA']           = $auxiliar->traduce('Informar fecha/hora Real de finalizacion de carga', $administrador->ID_IDIOMA);
                $arr_hitos_pendientes[$i]['CAMPO_ACTIVACION'] = 'FECHA_FIN_CARGA_REAL';
                $i++;
            endif;
        endif;

        //AÑADIR INFORMACIÓN Y DOCUMENTACIÓN DE TRANSPORTE
        if (($rowEmbarque->ESTADO_DOCUMENTACION_DEFINITIVA_FORWARDER == "Rechazada") || ($rowEmbarque->ESTADO_DOCUMENTACION_BORRADOR_FORWARDER == "Rechazada")):
            //SI EL AA RECHAZA LA DOCUMENTACIÓN DEL FWD SE ACTIVA LA ALERTA
            //SI NO ESTÁ EN ESE ESTADO MOSTRAMOS LA ALERTA
            $arr_hitos_pendientes[$i]['ALERTA']           = $auxiliar->traduce('Revisar documentacion', $administrador->ID_IDIOMA);
            $arr_hitos_pendientes[$i]['CAMPO_ACTIVACION'] = 'ESTADO_DOCUMENTACION_DEFINITIVA_FORWARDER';
            $i++;
        endif;

        //ADJUNTAR INFORME SURVEYORFWD EN PUERTO ORIGEN
        //SI RELLENA LA FECHA FIN DE CARGA REAL SE ACTIVA LA ALERTA
        if ($rowEmbarque->FECHA_FIN_CARGA_REAL != ""):
            //OBTENGO EL NÚMERO DE INFORMES DE CARGA SURVEYOR FWD DEL EMBARQUE
            $sql_where_informes_carga_surveyor_fwd = " WHERE F.TIPO_OBJETO='EmbarqueGC' AND F.ID_EMBARQUE_GC=" . $rowEmbarque->ID_EMBARQUE . " AND F.SECCION='InformeCargaSurveyorFWDPuertoOrigen' ";

            //OBTENGO EL NÚMERO DE ESQUEMAS DEFINITIVOS DEL EMBARQUE
            $num_informes_carga_surveyor_fwd = $this->getNumFicheros($sql_where_informes_carga_surveyor_fwd);

            if ($num_informes_carga_surveyor_fwd->NUM_FICHEROS == '0'):
                //SI EL EMBARQUE NO CONTIENE ESTE TIPO DE DOCUMENTOS, SE MUESTRA EL HITO COMO PENDIENTE
                $arr_hitos_pendientes[$i]['ALERTA']           = $auxiliar->traduce('Adjuntar Informe surveryor FWD en puerto origen', $administrador->ID_IDIOMA);
                $arr_hitos_pendientes[$i]['CAMPO_ACTIVACION'] = 'INFORMES_CARGA';
                $i++;
            endif;

            //ACEPTACIÓN/RECHAZO DE LA CARGA
            //OBTENGO EL NÚMERO DE COMENTARIOS DE LA CARGA
            $num_observaciones_carga = count((array)$this->getObservacionesSistemaObjeto("EMBARQUE", $rowEmbarque->ID_EMBARQUE, 'Surveyor', 'CargaSurveyorTransportesGC'));

            if ($rowEmbarque->ACEPTACION_CARGA != 1 && $num_observaciones_carga == 0):
                //SI NO SE HA ACEPTADO LA CARGA Y NO TIENE COMENTARIOS, AÑADO LA ALERTA AL ARRAY
                $arr_hitos_pendientes[$i]['ALERTA']           = $auxiliar->traduce('Aceptacion/Rechazo de la carga ', $administrador->ID_IDIOMA);
                $arr_hitos_pendientes[$i]['CAMPO_ACTIVACION'] = 'ACEPTACION_CARGA';
                $i++;
            endif;

            //ADJUNTAR INFORME DE CARGA SURVEYOR ACCIONA
            //OBTENEMOS LOS INFORMES DE CARGA SURVEYOR ACCIONA DEL EMBARQUE
            $sql_where_informes_carga_surveyor_acciona = " WHERE F.TIPO_OBJETO='EmbarqueGC' AND F.ID_EMBARQUE_GC=" . $rowEmbarque->ID_EMBARQUE . " AND F.SECCION='InformeCargaSurveyorAEPuertoOrigen' ";

            //OBTENGO EL NÚMERO DE ESQUEMAS DEFINITIVOS DEL EMBARQUE
            $num_informes_carga_surveyor_acciona = $this->getNumFicheros($sql_where_informes_carga_surveyor_acciona);

            if ($num_informes_carga_surveyor_acciona->NUM_FICHEROS == '0'):
                //SI EL EMBARQUE NO CONTIENE ESTE TIPO DE DOCUMENTOS, SE MUESTRA EL HITO COMO PENDIENTE
                $arr_hitos_pendientes[$i]['ALERTA']           = $auxiliar->traduce('Adjuntar Informe de carga Surveyor Acciona', $administrador->ID_IDIOMA);
                $arr_hitos_pendientes[$i]['CAMPO_ACTIVACION'] = 'FECHA_FIN_CARGA_REAL';
                $i++;
            endif;

            //CONFIRMAR ATD PUERTO ORIGEN Y ETA PUERTO DESTINO
            if (($rowEmbarque->ATD_PUERTO_ORIGEN == "") || ($rowEmbarque->ETA_PUERTO_DESTINO == "")):
                //SI NO SE RELLENA NI LA ATD PUERTO ORIGEN NI LA ETA PUERTO DESTINO SE MUESTRA LA ALERTA
                $arr_hitos_pendientes[$i]['ALERTA']           = $auxiliar->traduce('Confirmar ATD puerto Origen y ETA puerto destino planificado', $administrador->ID_IDIOMA);
                $arr_hitos_pendientes[$i]['CAMPO_ACTIVACION'] = 'ATD_ETA';
                $i++;
            endif;

            //ADJUNTAR INFORME DE DESCARGA SURVEYOR ACCIONA
            //OBTENEMOS LOS INFORMES DE DESCARGA SURVEYOR ACCIONA DEL EMBARQUE
            $sql_where_informes_descarga_surveyor_acciona = " WHERE F.TIPO_OBJETO='EmbarqueGC' AND F.ID_EMBARQUE_GC=" . $rowEmbarque->ID_EMBARQUE . " AND F.SECCION='InformeDescargaSurveyorAEPuertoDestino' ";

            //OBTENGO EL NÚMERO DE ESQUEMAS DEFINITIVOS DEL EMBARQUE
            $num_informes_descarga_surveyor_acciona = $this->getNumFicheros($sql_where_informes_descarga_surveyor_acciona);

            if ($num_informes_descarga_surveyor_acciona->NUM_FICHEROS == '0'):
                //SI EL EMBARQUE NO CONTIENE ESTE TIPO DE DOCUMENTOS, SE MUESTRA EL HITO COMO PENDIENTE
                $arr_hitos_pendientes[$i]['ALERTA']           = $auxiliar->traduce('Adjuntar Informe de descarga Surveyor Acciona', $administrador->ID_IDIOMA);
                $arr_hitos_pendientes[$i]['CAMPO_ACTIVACION'] = 'FECHA_FIN_CARGA_REAL';
                $i++;
            endif;
        endif;

        //OBTENGO LOS DÍAS DE DIFERENCIA ENTRE LA FECHA ACTUAL Y LA ETA PLANIFICADA PUERTO DESTINO
        $dias_diferencia = 0;

        if ($rowEmbarque->ETA_PUERTO_DESTINO != NULL):
            $dias_diferencia = $this->getDiasRestaFechas(date("Y-m-d H:i:s"), $rowEmbarque->ETA_PUERTO_DESTINO);
        endif;

        if ($dias_diferencia <= 7):
            //SI ESTAMOS A UNA SEMANA O MENOS DEL ETA PLANIFICADO PUERTO DESTINO ACTIVO LA ALERTA
            //OBTENGO EL PERSONAL DE ASISTENCIA EN LA DESCARGA DEL EMBARQUE
            $res_personal_asistencia_descarga = $this->getPersonalAsistencia($rowEmbarque->ID_EMBARQUE, 'Descarga');

            if (count((array)$res_personal_asistencia_descarga) == 0):
                //SI EL EMBARQUE NO TIENE PERSONAL DE ASISTENCIA EN LA DESCARGA SALTA LA ALARMA
                $arr_hitos_pendientes[$i]['ALERTA']           = $auxiliar->traduce('Añadir personal de asistencia a la descarga', $administrador->ID_IDIOMA);
                $arr_hitos_pendientes[$i]['CAMPO_ACTIVACION'] = 'ETA_PUERTO_DESTINO';
                $i++;
            endif;

            if ($dias_diferencia <= 5):
                //INFORMAR FECHA/HORA PLANIFICADA DE COMIENZO DE DESCARGA
                //SI QUEDAN 5 DÍAS O MENOS PARA LA ETA PLANIFICADA PUERTO DESTINO SE ACTIVA LA ALERTA
                if ($rowEmbarque->FECHA_COMIENZO_DESCARGA_PLANIFICADA == ""):
                    //SI LA FECHA COMIENZO DESCARGA PLANIFICADA ESTÁ VACÍA SE MUESTRA LA ALERTA
                    $arr_hitos_pendientes[$i]['ALERTA']           = $auxiliar->traduce('Informar fecha/hora planificada de comienzo de descarga', $administrador->ID_IDIOMA);
                    $arr_hitos_pendientes[$i]['CAMPO_ACTIVACION'] = 'FECHA_COMIENZO_DESCARGA_PLANIFICADA';
                    $i++;
                endif;

                //ADJUNTAR ACREDITACIONES PERSONAL EN DESCARGA
                //OBTENEMOS LAS ACREDITACIONES DEL EMBARQUE
                $sql_where_acreditaciones_entrada_puerto = " WHERE F.TIPO_OBJETO='EmbarqueGC' AND F.ID_EMBARQUE_GC=" . $rowEmbarque->ID_EMBARQUE . " AND F.SECCION='AcreditacionesEntradaPuertoDescarga' ";
                $sql_where_acreditaciones_acceso_buque   = " WHERE F.TIPO_OBJETO='EmbarqueGC' AND F.ID_EMBARQUE_GC=" . $rowEmbarque->ID_EMBARQUE . " AND F.SECCION='AcreditacionesAccesoBuqueDescarga' ";

                //OBTENGO EL NÚMERO DE ACREDITACIONES DE ENTRADA A PUERTO DEL EMBARQUE
                $num_acreditaciones_entrada_puerto = $this->getNumFicheros($sql_where_acreditaciones_entrada_puerto);
                //OBTENGO EL NÚMERO DE ACREDITACIONES DE ACCESO BUQUE DEL EMBARQUE
                $num_acreditaciones_acceso_buque = $this->getNumFicheros($sql_where_acreditaciones_acceso_buque);

                if (($num_acreditaciones_entrada_puerto->NUM_FICHEROS == '0') || ($num_acreditaciones_acceso_buque->NUM_FICHEROS == '0')):
                    //SI ALGÚN CAMPO DE LOS MENCIONADOS NO SE RELLENA, SE MUESTRA EL HITO COMO PENDIENTE
                    $arr_hitos_pendientes[$i]['ALERTA']           = $auxiliar->traduce('Adjuntar acreditaciones personal en descarga', $administrador->ID_IDIOMA);
                    $arr_hitos_pendientes[$i]['CAMPO_ACTIVACION'] = 'ACREDITACIONES';
                    $i++;
                endif;
            endif;
        endif;

        //ADJUNTAR INFORME SURVEYOR FWD EN PUERTO DESTINO
        if ($rowEmbarque->FECHA_FIN_DESCARGA_REAL != ""):
            //SI SE RELLENA LA FECHA FIN DE DESCARGA REAL SE ACTIVA LA ALERTA
            //OBTENGO EL NÚMERO DE INFORMES DE DESCARGA SURVEYOR FWD DEL EMBARQUE
            $sql_where_informes_descarga_surveyor_fwd = " WHERE F.TIPO_OBJETO='EmbarqueGC' AND F.ID_EMBARQUE_GC=" . $rowEmbarque->ID_EMBARQUE . " AND F.SECCION='InformeDescargaSurveyorFWDPuertoDestino' ";

            $num_informes_descarga_surveyor_fwd = $this->getNumFicheros($sql_where_informes_descarga_surveyor_fwd);

            if ($num_informes_descarga_surveyor_fwd->NUM_FICHEROS == '0'):
                //SI EL EMBARQUE NO CONTIENE ESTE TIPO DE DOCUMENTOS, SE MUESTRA EL HITO COMO PENDIENTE
                $arr_hitos_pendientes[$i]['ALERTA']           = $auxiliar->traduce('Adjuntar Informe surveryor FWD en puerto destino', $administrador->ID_IDIOMA);
                $arr_hitos_pendientes[$i]['CAMPO_ACTIVACION'] = 'INFORMES_DESCARGA';
                $i++;
            endif;
        endif;

        //REVISIÓN CALIDAD DE ENTREGA DE COMPONENTES
        if ($rowEmbarque->CONFIRMAR_DESPACHO_COMPONENTES == 1):
            //SI EL PROVEEDOR CONFIRMA LA ENTREGA DEL PRIMER COMPONENTE SE ACTIVA LA ALERTA
            //OBTENGO EL NÚMERO DE COMENTARIOS DE CALIDAD DEL EMBARQUE
            $num_observaciones_calidad = count((array)$this->getObservacionesSistemaObjeto("EMBARQUE", $rowEmbarque->ID_EMBARQUE, 'Calidad', 'CalidadTransportesGC'));

            if ($rowEmbarque->CALIDAD_SI_NO != 1 || $num_observaciones_calidad == 0):
                //SI EL CHECK DE CALIDAD SE HA MARCADO Y SE HA AÑADIDO ALGÚN COMENTARIO EN EL CHAT DE CALIDAD, ENTONCES AÑADIMOS LA ALERTA
                $arr_hitos_pendientes[$i]['ALERTA']           = $auxiliar->traduce('Revision calidad de entrega de componentes', $administrador->ID_IDIOMA);
                $arr_hitos_pendientes[$i]['CAMPO_ACTIVACION'] = 'CALIDAD_SI_NO';
                $i++;
            endif;
        endif;

        return $arr_hitos_pendientes;
    }

    /**
     * obtiene datos para el listado
     * @param string $sql_where
     * @param string $id_embarque
     * @param string $sql_order
     * @param string $sql_join
     * @return array
     */
    public function getOtsEmbarque($id_embarque, $sql_join = "", $sql_order = "", $sql_where = "")
    {
        global $bd;

        $sql = "SELECT OTC.ID_ORDEN_TRANSPORTE
                FROM EMBARQUE E
                INNER JOIN ORDEN_TRANSPORTE_CONSTRUCCION_AMPLIACION OTCA ON OTCA.ID_EMBARQUE = E.ID_EMBARQUE
                INNER JOIN ORDEN_TRANSPORTE OT ON OT.ID_ORDEN_TRANSPORTE = OTCA.ID_ORDEN_TRANSPORTE
                INNER JOIN ORDEN_TRANSPORTE_CONSTRUCCION OTC ON OTC.ID_ORDEN_TRANSPORTE = OT.ID_ORDEN_TRANSPORTE
                    $sql_join
                WHERE E.TIPO_EMBARQUE = 'Gran Componente' AND E.ID_EMBARQUE = $id_embarque " . ($sql_where != '' ? ' AND ' . $sql_where : '') .
            $sql_order;

        $result      = $bd->ExecSQL($sql);
        $resultFinal = array();

        while ($row = $bd->SigReg($result)):
            $resultFinal["ID_ORDEN_TRANSPORTE"] = $row->ID_ORDEN_TRANSPORTE;
        endwhile;

        return $resultFinal;
    }

    //MIGRADO DE CODEIGNITER
    public function getNumFicheros($sql_where = null)
    {
        global $bd;

        $sql = "SELECT COUNT(*) AS NUM_FICHEROS
                    FROM FICHERO F 
                    $sql_where";

        //EJECUTAMOS EL SQL
        $result = $bd->ExecSQL($sql);
        $row    = $bd->SigReg($result);

        //DEVOLVEMOS LA QUERY
        return $row;
    }

    /**
     * obtiene las itms del embarque
     * @param string $id_embarque
     * @return array
     */
    public function getItmsEmbarque($id_embarque = "")
    {
        global $bd;

        $sql = "SELECT I.*
                FROM EMBARQUE E 
                        INNER JOIN EMBARQUE_ITM EI ON EI.ID_EMBARQUE = E.ID_EMBARQUE
                        INNER JOIN ITM I ON I.ID_ITM = EI.ID_ITM
                WHERE E.ID_EMBARQUE = $id_embarque";

        $result  = $bd->ExecSQL($sql);
        $arrITMs = array();

        while ($row = $bd->SigReg($result)):

            $arrITMs[] = $row->ID_ITM;

        endwhile;

        return $arrITMs;
    }

    /**
     * obtiene el personal de asistencia de carga del embarque
     * @param string $id_embarque
     * @param string $tipo_personal
     * @return array
     */
    public function getPersonalAsistencia($id_embarque = "", $tipo_personal = "")
    {
        global $bd;

        $sql = "SELECT PA.*
                FROM EMBARQUE E 
                        INNER JOIN PERSONAL_ASISTENCIA_EMBARQUE PAE ON PAE.ID_EMBARQUE = E.ID_EMBARQUE
                        INNER JOIN PERSONAL_ASISTENCIA PA ON PA.ID_PERSONAL_ASISTENCIA = PAE.ID_PERSONAL_ASISTENCIA
                WHERE E.ID_EMBARQUE = $id_embarque AND PAE.TIPO_PERSONAL = '" . $tipo_personal . "' AND PAE.BAJA = 0";

        $result                = $bd->ExecSQL($sql);
        $arrPersonalAsistencia = array();

        while ($row = $bd->SigReg($result)):

            $arrPersonalAsistencia[] = $row->ID_PERSONAL_ASISTENCIA;

        endwhile;

        return $arrPersonalAsistencia;
    }

    //FUNCIÓN MIGRADA DE CODEIGNITER
    public function getObservacionesSistemaObjeto($tipo_objeto, $id_objeto, $tipo_observacion = '', $subtipo_observacion = '')
    {
        global $bd;

        $sql = "SELECT ID_OBSERVACION_SISTEMA
                FROM OBSERVACION_SISTEMA
                WHERE TIPO_OBJETO = '$tipo_objeto' AND ID_OBJETO = $id_objeto AND BAJA = 0";

        if ($tipo_observacion != ''):
            $sql .= " AND TIPO_OBSERVACION = '$tipo_observacion'";
        endif;
        if ($subtipo_observacion != ''):
            $sql .= " AND SUBTIPO_OBSERVACION = '$subtipo_observacion'";
        endif;

        $sql .= " ORDER BY FECHA DESC";

        $result           = $bd->ExecSQL($sql);
        $arrObservaciones = array();

        while ($row = $bd->SigReg($result)):

            $arrObservaciones[] = $row->ID_OBSERVACION_SISTEMA;

        endwhile;

        return $arrObservaciones;
    }

    /**
     * DEVUELVE DIFERENCIA DE DIAS ENTRE DOS FECHAS
     * @param string $fechaInicio
     * @param string $fechaFin
     * @return int
     */
    function getDiasRestaFechas($fechaInicio, $fechaFin)
    {
        return round((strtotime((string)$fechaFin) - strtotime((string)$fechaInicio)) / 86400);
    }

    /**
     * @param $idOrdenTransporte
     * ACTUALIZA EL ESTADO DE LAS ACCIONES DE UN TRANSPORTE DE CONSTRUCCION
     */
    function avanzarEstadoTransporte($idOrdenTransporte)
    {

        global $administrador;
        global $auxiliar;
        global $bd;

        //VARIABLES DE RETORNO
        $resultado_accion  = false;
        $resultado_mensaje = "";

        //OBTENEMOS LA ORDEN DE TRANSPORTE
        $row_orden_transporte = $this->getOrdenTransporteConstruccion($idOrdenTransporte);

        //BUSCAMOS LAS ACCIONES QUE DEBERIAN ESTAR RESUELTAS PARA PODER AVANZAR
        $result_acciones = $this->getAccionesActivasOrdenTransporte($row_orden_transporte->ID_ORDEN_TRANSPORTE, " AND OTA . HITO_CAMBIO_ESTADO = 0 AND OTA . NO_AFECTA_ESTADOS = 0 AND OTAA . ESTADO_ACCION <> 'Resuelta' AND OTA . ESTADO_TRANSPORTE = '" . $row_orden_transporte->ESTADO . "'");

        //SI QUEDA ALGUNA ACTIVA, DEVOLVEMOS ERROR
        if ($bd->NumRegs($result_acciones) > 0):
            $resultado_mensaje = $auxiliar->traduce("Los datos se han grabado correctamente pero no ha sido posible avanzar el estado del Transporte debido a que existen acciones pendientes de resolver", $administrador->ID_IDIOMA);

        else:
            //BUSCAMOS LA ACCION DE AVANZAR DE ESTADO
            $result_accion_hito = $this->getAccionesActivasOrdenTransporte($row_orden_transporte->ID_ORDEN_TRANSPORTE, " AND OTA . HITO_CAMBIO_ESTADO = 1 AND OTAA . ESTADO_ACCION <> 'Resuelta' AND OTA . ESTADO_TRANSPORTE = '" . $row_orden_transporte->ESTADO . "'");

            //OBTENEMOS LA ROW
            $row_accion_hito = $bd->SigReg($result_accion_hito);;

            //ACTUALIZAMOS EL ESTADO DE LA ACCION
            $hitoFinalizado = $this->actualizarEstadoAccionConstruccion($row_accion_hito->ID_ORDEN_TRANSPORTE_ACCION_AVISO);

            //GENERAMOS EL AVISO EN LA PANTALLA SEGUN EL RESULTADO
            if ($hitoFinalizado):

                //ADEMAS AVANZAMOS ESTADO
                $resultado_mensaje = $auxiliar->traduce("Accion Resuelta Correctamente", $administrador->ID_IDIOMA);

                //OBTENEMOS EL ESTADO
                $siguienteEstado = $this->getSiguienteEstadoConstruccion($row_orden_transporte->ESTADO, $row_orden_transporte->TIPO_TRANSPORTE, $row_orden_transporte->TRANSPORTE_CON_ADUANAS, $row_orden_transporte->CON_EMBARQUE_GC);

                //SI EL ESTADO ES ENTREGADO Y SE HA PRODUCIDO UNA RECEPCION ANTICIPADA, EL ESTADO FINAL SERA RECEPCIONADO
                if (($siguienteEstado == "Entregado") && ($row_orden_transporte->RECEPCION_ANTICIPADA == 1)):
                    $siguienteEstado = "Recepcionado";
                endif;

                //GENERAMOS LOS CAMPOS A GUARDAR
                $datos_update = "ESTADO = '$siguienteEstado'";

                //SI EL SIGUIENTE ESTADO ES 'Puerto Destino', INICIAMOS EL Ciclo Entrega en Obra
                if ($siguienteEstado == "Puerto Destino"):
                    $datos_update .= ", ESTADO_ENTREGA_OBRA = 'En Terminal'";
                endif;

                //AVANZAMOS ESTADO
                $sqlUpdate = "UPDATE ORDEN_TRANSPORTE SET
                               $datos_update
                               WHERE ID_ORDEN_TRANSPORTE = $idOrdenTransporte";
                $bd->ExecSQL($sqlUpdate);

                // LOG MOVIMIENTOS
                if ($siguienteEstado != $row_orden_transporte->ESTADO):
                    $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Orden de Transporte", $row_orden_transporte->ID_ORDEN_TRANSPORTE, 'Cambiar estado a ' . $siguienteEstado, "ORDEN_TRANSPORTE");
                endif;

                //SI EL ESTADO INICIAL ES 'Preparado en Proveedor'  ACTULIZO LA FECHA_REAL_CONFIRMAR_MATERIAL_PREPARADO
                if ($row_orden_transporte->ESTADO == 'Preparado en Proveedor'):
                    //GENERAMOS LOS CAMPOS A GUARDAR
                    $sqlUpdate = "UPDATE ORDEN_TRANSPORTE SET
                                   FECHA_REAL_CONFIRMAR_MATERIAL_PREPARADO = '" . date("Y - m - d H:i:s") . "'
                                   WHERE ID_ORDEN_TRANSPORTE = $idOrdenTransporte";
                    $bd->ExecSQL($sqlUpdate);
                endif;

                //RESULTA CORRECTAMENTE
                $resultado_accion = true;

            else:
                $resultado_mensaje = $auxiliar->traduce("Los datos se han grabado correctamente pero la accion sigue pendiente ya que no ha rellenado todos los campos obligatorios", $administrador->ID_IDIOMA);
            endif;

        endif;

        //DEVOLVEMOS RESULTADO
        $arr_resultado              = array();
        $arr_resultado['Resultado'] = $resultado_accion;
        $arr_resultado['Mensaje']   = $resultado_mensaje;

        return $arr_resultado;
    }

    /**
     * @param $nivel_buscado NIVEL DEL QUE SE QUIERE OBTENER EL ESTADO
     * @return string ESTADO
     */
    public function getEstadoConstruccionByNivel($nivel_buscado, $selMedioTransporte = "", $conAduanas = "", $conEmbarque = "")
    {

        $nivelEstado = 1;
        //ASIGNAMOS UN NIVEL A CADA ESTADO POSIBLE (SI SE MODIFICA HABRA QUE MODIFICAR LAS DEMAS FUNCIONES)
        if ($nivel_buscado == $nivelEstado):
            return "Creada";
        endif;
        $nivelEstado++;

        if ($conEmbarque != 1)://EMBARQUE PASA DIRECTO A PUERTO ORIGEN
            if ($nivel_buscado == $nivelEstado):
                return "Preparado en Proveedor";
            endif;
            $nivelEstado++;
        endif;

        //ESTADOS MARITIMOS Y AEREOS
        if ($selMedioTransporte == "Maritimo" || $selMedioTransporte == "Multimodal Maritimo" || $selMedioTransporte == "Aereo" || $selMedioTransporte == "Multimodal Aereo"):
            if ($conEmbarque != 1)://EMBARQUE PASA DIRECTO A PUERTO ORIGEN
                if ($nivel_buscado == $nivelEstado):
                    return "Entregado a Forwarder";
                endif;
                $nivelEstado++;
            endif;

            if ($nivel_buscado == $nivelEstado):
                return "Puerto Origen";
            endif;
            $nivelEstado++;
            if ($nivel_buscado == $nivelEstado):
                return "Transito Internacional";
            endif;
            $nivelEstado++;
            if ($nivel_buscado == $nivelEstado):
                return "Puerto Destino";
            endif;
            $nivelEstado++;
            if ($nivel_buscado == $nivelEstado):
                return "Liberado Aduana";
            endif;
            $nivelEstado++;
            if ($nivel_buscado == $nivelEstado):
                return "Transito Local";
            endif;
            $nivelEstado++;
        endif;

        //ESTADOS TERRESTRES
        if ($selMedioTransporte == "Terrestre"):
            if ($nivel_buscado == $nivelEstado):
                return "Transito Internacional";
            endif;
            $nivelEstado++;

            //SI TIENE ADUANAS
            if ($conAduanas == 1):
                if ($nivel_buscado == $nivelEstado):
                    return "Aduana Destino";
                endif;
                $nivelEstado++;
                if ($nivel_buscado == $nivelEstado):
                    return "Liberado Aduana";
                endif;
                $nivelEstado++;
                if ($nivel_buscado == $nivelEstado):
                    return "Transito Local";
                endif;
                $nivelEstado++;
            endif;
        endif;

        if ($nivel_buscado == $nivelEstado):
            return "Entregado";
        endif;
        $nivelEstado++;
        if ($nivel_buscado == $nivelEstado):
            return "Recepcionado";
        endif;

        //SI NO HA ENTRADO DEVOLVEMOS 100 (SERA UN ESTADO QUE NO APLICA PARA ESE TRANSPORTE)
        return '';
    }

    /**
     * @param $txEstado ESTADO DEL QUE SE QUIERE OBTENER EL ANTERIOR
     * @return string ANTERIOR ESTADO
     */
    function getAnteriorEstadoConstruccion($txEstado, $selMedioTransporte = "", $conAduanas = "", $conEmbarque = "")
    {
        //BUSCAMOS EL NIVEL ACTUAL
        $nivel_actual = $this->getNivelEstadoConstruccion($txEstado, $selMedioTransporte, $conAduanas, $conEmbarque);

        //LE RESTAMOS UNO PARA OBTENER EL ANTERIOR
        $nivel_actual = $nivel_actual - 1;

        //DEVOLVEMOS EL SIGUIENTE ESTADO
        return $this->getEstadoConstruccionByNivel($nivel_actual, $selMedioTransporte, $conAduanas, $conEmbarque);

    }

    /**
     * @param $idOrdenTransporte
     * ACTUALIZA EL ESTADO DE LAS ACCIONES DE UN TRANSPORTE DE CONSTRUCCION
     */
    function retrocederEstadoTransporte($idOrdenTransporte, $idAdministrador)
    {

        global $bd;
        global $administrador;
        global $auxiliar;

        //VARIABLES DE RETORNO
        $resultado_accion  = false;
        $resultado_mensaje = "";

        //OBTENEMOS LA ORDEN DE TRANSPORTE
        $row_orden_transporte = $this->getOrdenTransporteConstruccion($idOrdenTransporte);

        //DAMOS DE BAJA TODAS LAS ACCIONES DEL ESTADO QUE VAMOS A REVERTIR
        $sqlUpdate = "UPDATE ORDEN_TRANSPORTE_ACCION_AVISO OTAA
                                INNER JOIN ORDEN_TRANSPORTE_ACCION OTA ON OTA.ID_ORDEN_TRANSPORTE_ACCION = OTAA.ID_ORDEN_TRANSPORTE_ACCION
                                SET OTAA.BAJA = 1
                                WHERE OTAA.ID_ORDEN_TRANSPORTE = " . $row_orden_transporte->ID_ORDEN_TRANSPORTE . " AND OTA.ESTADO_TRANSPORTE = '" . $row_orden_transporte->ESTADO . "'";
        $bd->ExecSQL($sqlUpdate);

        //OBTENEMOS EL ESTADO
        $anteriorEstado = $this->getAnteriorEstadoConstruccion($row_orden_transporte->ESTADO, $row_orden_transporte->TIPO_TRANSPORTE, $row_orden_transporte->TRANSPORTE_CON_ADUANAS, $row_orden_transporte->CON_EMBARQUE_GC);

        //SI EL ESTADO final 'Preparado en Proveedor' BORRO LA FECHA_REAL_CONFIRMAR_MATERIAL_PREPARADO
        if ($anteriorEstado == 'Preparado en Proveedor'):
            //GENERAMOS LOS CAMPOS A GUARDAR
            $datos_update                                            = array();
            $datos_update['FECHA_REAL_CONFIRMAR_MATERIAL_PREPARADO'] = NULL;
            $this->save_table($datos_update, $row_orden_transporte['ID_ORDEN_TRANSPORTE'], 'ORDEN_TRANSPORTE_CONSTRUCCION', 'ID_ORDEN_TRANSPORTE');
        endif;

        //GENERAMOS LOS CAMPOS A GUARDAR
        $datos_update = "ESTADO = '$anteriorEstado'";

        //SI EL ANTERIOR ESTADO ES 'Puerto Destino', REVERTIMOS EL Ciclo Entrega en Obra
        if ($row_orden_transporte->ESTADO == "Puerto Destino"):
            $datos_update .= ", ESTADO_ENTREGA_OBRA = 'No Aplica'";
        endif;

        //AVANZAMOS ESTADO
        $sqlUpdate = "UPDATE ORDEN_TRANSPORTE SET
                        $datos_update
                        WHERE ID_ORDEN_TRANSPORTE = $idOrdenTransporte";
        $bd->ExecSQL($sqlUpdate);

        // LOG MOVIMIENTOS
        if ($anteriorEstado != $row_orden_transporte->ESTADO):
            $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Orden de Transporte", $row_orden_transporte->ID_ORDEN_TRANSPORTE, 'Cambiar estado a ' . $anteriorEstado, "ORDEN_TRANSPORTE");
        endif;

        //BUSCAMOS EL HITO DE CAMBIO DE ESTADO AL QUE SE ESTA REVIRTIENDO
        $sqlAccionHito   = "SELECT OTA.TIPO_ACCION, OTAA.ID_ORDEN_TRANSPORTE_ACCION_AVISO
                                FROM ORDEN_TRANSPORTE_ACCION OTA
                                INNER JOIN ORDEN_TRANSPORTE_ACCION_AVISO OTAA ON OTAA.ID_ORDEN_TRANSPORTE_ACCION = OTA.ID_ORDEN_TRANSPORTE_ACCION
                                WHERE OTAA.BAJA = 0 AND OTAA.ID_ORDEN_TRANSPORTE = " . $row_orden_transporte->ID_ORDEN_TRANSPORTE . " AND OTA.HITO_CAMBIO_ESTADO = 1 AND OTA.ESTADO_TRANSPORTE = '" . $anteriorEstado . "'";
        $result          = $bd->ExecSQL($sqlAccionHito);
        $row_accion_hito = $bd->SigReg($result);

        //SI NO EXISTE, DEVOLVEMOS ERROR
        if ($row_accion_hito == false):
            $resultado_accion  = false;
            $resultado_mensaje = $auxiliar->traduce('Falta Accion Cambio Estado', $administrador->ID_IDIOMA);
        else:
            //ADEMAS AVANZAMOS ESTADO
            $resultado_mensaje = $auxiliar->traduce("Estado Revertido Correctamente", $administrador->ID_IDIOMA);
            $resultado_accion  = true;

            //GENERAMOS LOS CAMPOS A GUARDAR EN EL HITO
            $sqlUpdate = "UPDATE ORDEN_TRANSPORTE_ACCION_AVISO SET
                            ESTADO_ACCION = 'Pendiente'
                            , ID_ORDEN_TRANSPORTE_ACCION_AVISO_AGRUPADA = NULL
                            , FECHA_RESOLUCION = NULL
                            WHERE ID_ORDEN_TRANSPORTE_ACCION_AVISO = " . $row_accion_hito->ID_ORDEN_TRANSPORTE_ACCION_AVISO;
            $bd->ExecSQL($sqlUpdate);
        endif;


        //DEVOLVEMOS RESULTADO
        $arr_resultado              = array();
        $arr_resultado['Resultado'] = $resultado_accion;
        $arr_resultado['Mensaje']   = $resultado_mensaje;

        return $arr_resultado;
    }

    /**
     * MIGRADO A CODEIGNITER (SI SE ACTUALIZA, ACTUALIZAR AMBAS actualizar_acciones_construccion)
     * @param $idOrdenTransporte
     * ACTUALIZA EL ESTADO DE LAS ACCIONES DE UN TRANSPORTE DE CONSTRUCCION
     */
    function actualizarAccionesConstruccion($idOrdenTransporte)
    {
        global $html;
        global $bd;
        global $administrador;
        global $auxiliar;

        //BUSCAMOS LAS ACCIONES DEL TRANSPORTE Y QUE NO SON HITOS DE CAMBIO DE ESTADO
        $sqlAcciones    = "SELECT OTA.TIPO_ACCION, OTAA.ID_ORDEN_TRANSPORTE_ACCION_AVISO
                            FROM ORDEN_TRANSPORTE_ACCION OTA
                            INNER JOIN ORDEN_TRANSPORTE_ACCION_AVISO OTAA ON OTAA.ID_ORDEN_TRANSPORTE_ACCION = OTA.ID_ORDEN_TRANSPORTE_ACCION
                            WHERE OTAA.BAJA = 0 AND OTAA.ID_ORDEN_TRANSPORTE = $idOrdenTransporte AND OTA.HITO_CAMBIO_ESTADO = 0";
        $resultAcciones = $bd->ExecSQL($sqlAcciones);

        //RECORREMOS LAS ACCIONES
        while ($rowAcciones = $bd->SigReg($resultAcciones)):
            //ACTUALIZAMOS EL ESTADO DE LA ACCION
            $this->actualizarEstadoAccionConstruccion($rowAcciones->ID_ORDEN_TRANSPORTE_ACCION_AVISO);
        endwhile;
    }


    /**
     * MIGRADO A CODEIGNITER (SI SE ACTUALIZA, ACTUALIZAR AMBAS actualizar_estado_accion_construccion)
     * @param $idOrdenTransporte
     * ACTUALIZA EL ESTADO DE UNA ACCION DEL TRANSPORTE, DEVUELVE CIERTO SI HA PODIDO FINALIZAR EL ESTADO
     */
    function actualizarEstadoAccionConstruccion($idOrdenTransporteAccionAviso, $anulacionAccion = "", $comprobarImporte = "")
    {
        global $html;
        global $bd;
        global $administrador;
        global $auxiliar;
        global $orden_transporte;

        //BUSCAMOS EL AVISO ACCION
        $rowAvisoAccion = $bd->VerReg("ORDEN_TRANSPORTE_ACCION_AVISO", "ID_ORDEN_TRANSPORTE_ACCION_AVISO", $idOrdenTransporteAccionAviso);

        //BUSCAMOS LA ACCION
        $rowAccion = $bd->VerReg("ORDEN_TRANSPORTE_ACCION", "ID_ORDEN_TRANSPORTE_ACCION", $rowAvisoAccion->ID_ORDEN_TRANSPORTE_ACCION);

        //OBTENEMOS LOS CAMPOS QUE APLICAN A LA ACCION
        $campoSinRellenar = false;
        $arrayCampos      = $this->getArrayCamposAccionConstruccion($rowAvisoAccion->ID_ORDEN_TRANSPORTE_ACCION_AVISO);

        //OBTENEMOS LA ORDEN DE TRANSPORTE CON LOS CAMPOS
        $rowOrdenTransporte = $this->getOrdenTransporteConstruccion($rowAvisoAccion->ID_ORDEN_TRANSPORTE);


        //SI NOS HA DEVUELTO CAMPOS
        if (count((array)$arrayCampos) > 0):

            //RECORREMOS LOS CAMPOS PARA DETECTAR SI NO SE HA INTRODUCIDO VALOR
            foreach ($arrayCampos as $nombreCampo => $arrayDatosCampo):

                $nombreCampoTabla = $arrayDatosCampo['Campo'];

                //region AQUI PONEMOS EXCEPCIONES

                //CAMPOS NO NULOS QUE PUEDEN TRAER VALOR QUE CONSIDERAMOS NULO (EJ importe 0....)
                if (($nombreCampoTabla == "RESERVA_MEDIOS_REALIZADA") || ($nombreCampoTabla == "ACEPTACION_DOCUMENTACION_PROVEEDOR") || ($nombreCampoTabla == "ACEPTACION_CMR") || ($nombreCampoTabla == "ACEPTACION_BORRADOR_BL") || ($nombreCampoTabla == "ACEPTACION_BL_DEFINITIVO") || ($nombreCampoTabla == "ACEPTACION_DOCUMENTACION_EN_ADUANA") || ($nombreCampoTabla == "ENVIO_CONTENEDOR_VACIO_RECOGIDA") || ($nombreCampoTabla == "ENTREGA_A_TRANSPORTISTA_INLAND")):
                    if ($rowOrdenTransporte->$nombreCampoTabla == 0):
                        $campoSinRellenar = true;
                    endif;

                    //CONTINUAMOS CON EL SIGUIENTE CAMPO
                    continue;
                endif;

                //SI ES UN CAMPO DE TIPO IMPORTE, COMPROBAMOS IMPORTE MAYOR QUE CERO Y MONEDA RELLENA
                if ($arrayDatosCampo['Tipo'] == "ImporteMoneda"):
                    $nombreCampoImporte = "IMPORTE_" . $nombreCampoTabla;
                    $nombreCampoMoneda  = "ID_MONEDA_" . $nombreCampoTabla;

                    //SI LOS CAMPOS SON IVA O ARANCEl; SIRVE CON QUE UNO DE LOS DOS ESTE RELLENO
                    if (($nombreCampoImporte == "IMPORTE_ARANCEL") || ($nombreCampoImporte == "IMPORTE_IVA_ADUANA")):
                        if ((($rowOrdenTransporte->IMPORTE_ARANCEL == 0) && ($rowOrdenTransporte->IMPORTE_IVA_ADUANA == 0)) || (($rowOrdenTransporte->ID_MONEDA_ARANCEL == NULL) && ($rowOrdenTransporte->ID_MONEDA_IVA_ADUANA == NULL))):
                            $campoSinRellenar = true;
                        endif;

                    else://COMPROBAMOS MONEDA e IMPORTE INTRODUCIDO
                        if ($comprobarImporte == 1):
                            if ($rowOrdenTransporte->$nombreCampoImporte == 0):
                                $campoSinRellenar = true;
                            endif;

                            if ($rowOrdenTransporte->$nombreCampoMoneda == NULL):
                                $campoSinRellenar = true;
                            endif;
                        endif;
                    endif;


                    //EN EL CASO DE IMPORTE APROVISIONADO TESORERIA, COMPROBAMOS QUE NO ES MENOR QUE EL IMPORTE A APROVISIONAR (IVA + ARANCEL)
                    if ($nombreCampoTabla == "APROVISIONADO_TESORERIA"):

                        if ($rowOrdenTransporte->IMPORTE_APROVISIONADO_TESORERIA < ($rowOrdenTransporte->IMPORTE_IVA_ADUANA + $rowOrdenTransporte->IMPORTE_ARANCEL)):
                            $campoSinRellenar = true;
                        endif;
                    endif;

                    //CONTINUAMOS CON EL SIGUIENTE CAMPO
                    continue;
                endif;

                //EXCEPCION DE DATOS DE TRANSITO, COMPROBAMOS LA TABLA ORDEN_TRANSPORTE_TRANSBORDO
                if ($arrayDatosCampo['Tipo'] == "ExcepcionBuques"):

                    //SI ES ANULACION Y EL UN CAMPO OPCIONAL, DEJAMOS QUE SE ANULE
                    if (($anulacionAccion == "Si") && ($arrayDatosCampo['Opcional'] == "Si")):
                        $campoSinRellenar = true;

                    else://COMPROBAMOS QUE AL MENOS TIENE UN BUQUE
                        $numeroTransbordos = $bd->NumRegsTabla("ORDEN_TRANSPORTE_TRANSBORDO", "ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND BAJA = 0");

                        if ($numeroTransbordos == 0):
                            $campoSinRellenar = true;
                        endif;
                    endif;

                    //CONTINUAMOS CON EL SIGUIENTE CAMPO
                    continue;

                elseif ($arrayDatosCampo['Tipo'] == "ExcepcionAviones"):


                    //SI ES ANULACION Y EL UN CAMPO OPCIONAL, DEJAMOS QUE SE ANULE
                    if (($anulacionAccion == "Si") && ($arrayDatosCampo['Opcional'] == "Si")):
                        $campoSinRellenar = true;

                    else://COMPROBAMOS QUE AL MENOS TIENE UN BUQUE
                        $numeroEscalas = $bd->NumRegsTabla("ORDEN_TRANSPORTE_TRANSBORDO", "ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND BAJA = 0");

                        if ($numeroEscalas == 0):
                            $campoSinRellenar = true;
                        endif;
                    endif;

                    //CONTINUAMOS CON EL SIGUIENTE CAMPO
                    continue;
                endif;

                //EXCEPCION DE DATOS DE ACCIONES DE ACTUALIZAR DATOS TRANSITO
                if ($arrayDatosCampo['Tipo'] == "ExcepcionETA" || $arrayDatosCampo['Tipo'] == "ExcepcionTransbordo"):

                    //SI ES ANULACION, DEJAMOS QUE SE ANULE
                    if (($anulacionAccion == "Si") && ($arrayDatosCampo['Opcional'] == "Si")):
                        $campoSinRellenar = true;
                    endif;

                    //CONTINUAMOS CON EL SIGUIENTE CAMPO
                    continue;
                endif;

                //EXCEPCION DE EXTRACOSTES, BUSCAMOS SI YA SE HA TOMADO DECISION
                if ($arrayDatosCampo['Tipo'] == "ExcepcionExtracoste"):

                    //OBTENEMOS LOS EXTRACOSTES SIN DECISION
                    $numeroExtracostes = $bd->NumRegsTabla("EXTRACOSTE_ORDEN_TRANSPORTE", "ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND BAJA = 0 AND DECISION_EXTRACOSTE IS NULL");

                    //SI TIENE ALGUNO, NO DAMOS LA ACCION POR RESUELTA
                    if ($numeroExtracostes > 0):
                        $campoSinRellenar = true;
                    endif;

                    //CONTINUAMOS CON EL SIGUIENTE CAMPO
                    continue;
                endif;

                //endregion FIN EXCEPCIONES

                //SI NO ESTA RELLENO, GUARDAMOS EL CAMPO
                if ($rowOrdenTransporte->$nombreCampoTabla == ""):
                    $campoSinRellenar = true;
                endif;


            endforeach;

        else://SI NO TIENE CAMPOS Y ES UNA ANULACION, NO DAMOS POR ANULADA LA ACCION
            if ($anulacionAccion == "Si"):
                $campoSinRellenar = true;
            endif;
        endif;//HA DEVUELTO CAMPOS

        //ACCIONES DE ACEPTACION DE DOCUMENTACION QUE TIENEN UN FLUJO ESPECIAL
        if ($rowAccion->TIPO_ACCION == "Revisar Borrador BL"):
            if ($rowOrdenTransporte->ACEPTACION_BORRADOR_BL == 2)://SI SE HA RECHAZADO, SE REACTIVA LA ACCION PARA AÑADIR DOC
                $campoSinRellenar = true;
            endif;

        elseif ($rowAccion->TIPO_ACCION == "Revisar BL Definitivo"):
            if ($rowOrdenTransporte->ACEPTACION_BL_DEFINITIVO == 2)://SI SE HA RECHAZADO, SE REACTIVA LA ACCION PARA AÑADIR DOC
                $campoSinRellenar = true;
            endif;

        elseif ($rowAccion->TIPO_ACCION == "Revisar CMR"):
            if ($rowOrdenTransporte->ACEPTACION_CMR == 2)://SI SE HA RECHAZADO, SE REACTIVA LA ACCION PARA AÑADIR DOC
                $campoSinRellenar = true;
            endif;

        elseif ($rowAccion->TIPO_ACCION == "Modificar Documentacion"):
            if ($rowOrdenTransporte->ACEPTACION_DOCUMENTACION_PROVEEDOR == 2)://SI SE HA RECHAZADO, SE REACTIVA LA ACCION PARA AÑADIR DOC
                $campoSinRellenar = true;
            endif;
        endif;


        //SI ESTAN TODOS RELLENOS, FINALIZAMOS LA ACCION
        if ($campoSinRellenar == false):

            //DEFINIMOS SI PODEMOS RESOLVER LA ACCIÓN
            $resolverAccion = true;

            if (($rowAccion->TIPO_ACCION == "Actualizar ETA") || ($rowAccion->TIPO_ACCION == "Generar Transbordo")): //SI SON CUALQUIERA DE ESTA DOS ACCIONES, COMPROBAMOS SI SU SIGUIENTE ESTE ES O NO TRANSITO INTERNACIONAL

                //OBTENEMOS EL SIGUIENTE ESTADO DE LA OT
                $siguienteEstado = $orden_transporte->getSiguienteEstadoConstruccion($rowOrdenTransporte->ESTADO, $rowOrdenTransporte->TIPO_TRANSPORTE, $rowOrdenTransporte->TRANSPORTE_CON_ADUANAS, $rowOrdenTransporte->CON_EMBARQUE_GC);

                if (($rowOrdenTransporte->ESTADO == "Transito Internacional") || ($siguienteEstado == "Transito Internacional")): //SI EL SIGUIENTE ESTADO ES TRÁNSITO INTERNACIONAL
                    $resolverAccion = false;
                endif;

            endif;

            if ($resolverAccion):

                //NO VOLVEMOS A FINALIZAR SI YA LO ESTA
                if ($rowAvisoAccion->ESTADO_ACCION != "Resuelta"):
                    //FINALIZAMOS LA ACCION
                    $sqlUpdate = "UPDATE ORDEN_TRANSPORTE_ACCION_AVISO SET
                                  ESTADO_ACCION = 'Resuelta'
                                  , FECHA_RESOLUCION = '" . date('Y-m-d H:i:s') . "'
                                  WHERE ID_ORDEN_TRANSPORTE_ACCION_AVISO = $rowAvisoAccion->ID_ORDEN_TRANSPORTE_ACCION_AVISO";
                    $bd->ExecSQL($sqlUpdate);

                    //SI LA ACCIÓN ES CONFIRMAR LLEGADA BUQUE, RESOLVEMOS LAS ACCIONES DE ACTUALIZAR ETA Y GENERAR TRANSBORDOS
                    if ($rowAccion->TIPO_ACCION == "Confirmar Llegada Buque"):

                        //OBTENEMOS EL ID DE LA ACCIÓN DE ACTUALIZAR ETA
                        $rowActualizarETA = $bd->VerRegRest("ORDEN_TRANSPORTE_ACCION", "TIPO_ACCION = 'Actualizar ETA' AND BAJA = 0");

                        //OBTENEMOS EL ID DE LA ACCIÓN DE GENERAR TRANSBORDOS
                        $rowGenerarTransbordo = $bd->VerRegRest("ORDEN_TRANSPORTE_ACCION", "TIPO_ACCION = 'Generar Transbordo' AND BAJA = 0");

                        //RESOLVEMOS ESTAS ACCIONES
                        $sqlUpdateAcciones = "UPDATE ORDEN_TRANSPORTE_ACCION_AVISO SET
                                                  ESTADO_ACCION = 'Resuelta'
                                                  , FECHA_RESOLUCION = '" . date('Y-m-d H:i:s') . "'
                                              WHERE ID_ORDEN_TRANSPORTE_ACCION IN ($rowActualizarETA->ID_ORDEN_TRANSPORTE_ACCION, $rowGenerarTransbordo->ID_ORDEN_TRANSPORTE_ACCION) AND ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND BAJA = 0";
                        $bd->ExecSQL($sqlUpdateAcciones);

                    endif;

                    //SI SE HAN ACEPTADO LAS DOS ACCIONES DE ACEPTACION DE DOCUMENTACION, MARCAMOS EL CHECK GENERAL
                    if (($rowAccion->TIPO_ACCION == "Aceptar/Rechazar BL Definitivo") || ($rowAccion->TIPO_ACCION == "Aceptar/Rechazar CMR") || ($rowAccion->TIPO_ACCION == "Aceptar/Rechazar Documentacion Proveedor") || ($rowAccion->TIPO_ACCION == "Modificar Documentacion")):
                        //SI ESTA ACEPTADA LA DOCUMENTACION DE PROVEEDOR, Y LA DOCUMENTACION CMR/BL SEGUN TIPO DE TRANSPORTE
                        if ((
                                ($rowOrdenTransporte->TIPO_TRANSPORTE == "Terrestre" && $rowOrdenTransporte->ACEPTACION_CMR == 1)
                                || ($rowOrdenTransporte->TIPO_TRANSPORTE != "Terrestre" && $rowOrdenTransporte->ACEPTACION_BL_DEFINITIVO == 1)
                            )
                            && ($rowOrdenTransporte->ACEPTACION_DOCUMENTACION_PROVEEDOR == 1)
                        ):
                            //SI ESTAN ACEPTADOS AMBOS CICLOS, ACEPTAMOS LA DOCUMENTACION EN ADUANA
                            $sqlUpdate = "UPDATE ORDEN_TRANSPORTE_CONSTRUCCION SET ACEPTACION_DOCUMENTACION_EN_ADUANA = 1 WHERE ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE";
                            $bd->ExecSQL($sqlUpdate);

                            //EN CASO DE QUE ESTE CREADA Y PDTE LA ACCION DE ACEPTACION DOCUMENTACION ADUANA, LA PONEMOS RESULTA
                            $sqlAccionAceptacion    = "SELECT OTAA.ID_ORDEN_TRANSPORTE_ACCION_AVISO
                                            FROM ORDEN_TRANSPORTE_ACCION OTA
                                              INNER JOIN ORDEN_TRANSPORTE_ACCION_AVISO OTAA ON OTAA.ID_ORDEN_TRANSPORTE_ACCION = OTA.ID_ORDEN_TRANSPORTE_ACCION
                                              WHERE OTAA.ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND OTAA.BAJA = 0 AND OTAA.ESTADO_ACCION = 'Pendiente' AND OTA.TIPO_ACCION = 'Aceptar Documentacion'";
                            $resultAccionAceptacion = $bd->ExecSQL($sqlAccionAceptacion);
                            if ($bd->NumRegs($resultAccionAceptacion) > 0):
                                $rowAccionAceptacion = $bd->SigReg($resultAccionAceptacion);
                                //FINALIZAMOS LA ACCION
                                $sqlUpdate = "UPDATE ORDEN_TRANSPORTE_ACCION_AVISO SET
                              ESTADO_ACCION = 'Resuelta'
                              , FECHA_RESOLUCION = '" . date('Y-m-d H:i:s') . "'
                              WHERE ID_ORDEN_TRANSPORTE_ACCION_AVISO = $rowAccionAceptacion->ID_ORDEN_TRANSPORTE_ACCION_AVISO";
                                $bd->ExecSQL($sqlUpdate);

                                //BUSCAMOS SI TENEMOS QUE GENERAR ALGUNA
                                $sqlAccionesRelacionadas    = $this->getQueryAccionesTransporte($rowOrdenTransporte->ID_ORDEN_TRANSPORTE, "ACCION_PREVIA = 'Aceptar Documentacion'");
                                $resultAccionesRelacionadas = $bd->ExecSQL($sqlAccionesRelacionadas);

                                while ($rowAccionesRelacionadas = $bd->SigReg($resultAccionesRelacionadas)):

                                    //COMPROBAMOS QUE NO ESTE GENERADA YA LA ACCION
                                    $GLOBALS["NotificaErrorPorEmail"] = "No";
                                    $rowAccionAvisoRelacionada        = $bd->VerRegRest("ORDEN_TRANSPORTE_ACCION_AVISO", "ID_ORDEN_TRANSPORTE_ACCION = $rowAccionesRelacionadas->ID_ORDEN_TRANSPORTE_ACCION AND BAJA = 0 AND ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE", "No");
                                    unset($GLOBALS["NotificaErrorPorEmail"]);

                                    //SI NO EXISTE LA ACCION AVISO, LA GENERAMOS
                                    if ($rowAccionAvisoRelacionada == false):
                                        //OBTENEMOS LA FECHA RELACIONADA (SI VIENE UN CAMPO UTILIZAMOS EL CAMPO, SI NO, LA FECHA ACTUAL)
                                        if ($rowAccionesRelacionadas->CAMPO_FECHA_RELACIONADO != ""):
                                            $nombreCampo = $rowAccionesRelacionadas->CAMPO_FECHA_RELACIONADO;

                                            $fechaRelacionadaAccion = $rowOrdenTransporte->$nombreCampo;
                                        else:
                                            $fechaRelacionadaAccion = date('Y-m-d H:i:s');
                                        endif;

                                        //INSERTAMOS EL REGISTRO
                                        $sqlInsert = "INSERT INTO ORDEN_TRANSPORTE_ACCION_AVISO SET
                                      ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE
                                    , ID_ORDEN_TRANSPORTE_ACCION = $rowAccionesRelacionadas->ID_ORDEN_TRANSPORTE_ACCION
                                    , ESTADO_ACCION = 'Pendiente'
                                    , FECHA_CREACION = '" . date('Y-m-d H:i:s') . "'
                                    , FECHA_RELACIONADA = '" . $fechaRelacionadaAccion . "'";
                                        $bd->ExecSQL($sqlInsert);

                                        //OBTENEMOS EL ID
                                        $idOrdenTransporteAccionAvisoGenerada = $bd->IdAsignado();

                                        //LLAMAOS RECURSIVAMENTE A ESTA FUNCION POR SI DEBEMOS FINALIZARLA, SI NO ES UN HITO DE CAMBIO DE ESTADO
                                        if ($rowAccionesRelacionadas->HITO_CAMBIO_ESTADO == 0):
                                            $this->actualizarEstadoAccionConstruccion($idOrdenTransporteAccionAvisoGenerada);
                                        endif;
                                    else:
                                        //LLAMAOS RECURSIVAMENTE A ESTA FUNCION POR SI DEBEMOS FINALIZARLA, SI NO ES UN HITO DE CAMBIO DE ESTADO
                                        if ($rowAccionesRelacionadas->HITO_CAMBIO_ESTADO == 0):
                                            $this->actualizarEstadoAccionConstruccion($rowAccionAvisoRelacionada->ID_ORDEN_TRANSPORTE_ACCION_AVISO);
                                        endif;
                                    endif;//FIN SI NO EXISTE LA ACCION AVISO, LA GENERAMOS
                                endwhile;
                                //FIN ACCIONES RELACIONADAS CON ACEPTACION DOCUMENTACION
                            endif;

                        else:
                            //MARCAMOS LA ACEPTACION EN ADUANA COMO FALSO
                            $sqlUpdate = "UPDATE ORDEN_TRANSPORTE_CONSTRUCCION SET ACEPTACION_DOCUMENTACION_EN_ADUANA = 0 WHERE ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE";
                            $bd->ExecSQL($sqlUpdate);

                            //EN CASO DE QUE ESTE CREADA Y RESUELTA LA ACCION DE ACEPTACION DOCUMENTACION ADUANA, LA PONEMOS PDTE
                            $sqlAccionAceptacion    = "SELECT OTAA.ID_ORDEN_TRANSPORTE_ACCION_AVISO
                                            FROM ORDEN_TRANSPORTE_ACCION OTA
                                              INNER JOIN ORDEN_TRANSPORTE_ACCION_AVISO OTAA ON OTAA.ID_ORDEN_TRANSPORTE_ACCION = OTA.ID_ORDEN_TRANSPORTE_ACCION
                                              WHERE OTAA.ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND OTAA.BAJA = 0 AND OTAA.ESTADO_ACCION = 'Resuelta' AND OTA.TIPO_ACCION = 'Aceptar Documentacion'";
                            $resultAccionAceptacion = $bd->ExecSQL($sqlAccionAceptacion);
                            if ($bd->NumRegs($resultAccionAceptacion) > 0):
                                $rowAccionAceptacion = $bd->SigReg($resultAccionAceptacion);
                                //ECHAMOS ATRAS LA ACCION
                                $sqlUpdate = "UPDATE ORDEN_TRANSPORTE_ACCION_AVISO SET
                                          ESTADO_ACCION = 'Pendiente'
                                        , FECHA_RESOLUCION = NULL
                                        , ID_ORDEN_TRANSPORTE_ACCION_AVISO_AGRUPADA = NULL
                            WHERE ID_ORDEN_TRANSPORTE_ACCION_AVISO = $rowAccionAceptacion->ID_ORDEN_TRANSPORTE_ACCION_AVISO";
                                $bd->ExecSQL($sqlUpdate);


                                //ADEMAS SI EXISTEN ACCIONES HIJAS LAS DAMOS DE BAJA (2 niveles, PETICION FONDOS Y CONFIRMAR PROVISION)
                                $sqlAccionesRelacionadas    = $this->getQueryAccionesTransporte($rowOrdenTransporte->ID_ORDEN_TRANSPORTE, "ACCION_PREVIA = 'Aceptar Documentacion'");
                                $resultAccionesRelacionadas = $bd->ExecSQL($sqlAccionesRelacionadas);

                                //RECORREMOS LAS ACCIONES RELACIONADAS
                                while ($rowAccionesRelacionadas = $bd->SigReg($resultAccionesRelacionadas)):

                                    //COMPROBAMOS QUE NO ESTE GENERADA YA LA ACCION
                                    $GLOBALS["NotificaErrorPorEmail"] = "No";
                                    $rowAccionAvisoRelacionada        = $bd->VerRegRest("ORDEN_TRANSPORTE_ACCION_AVISO", "ID_ORDEN_TRANSPORTE_ACCION = $rowAccionesRelacionadas->ID_ORDEN_TRANSPORTE_ACCION AND BAJA = 0 AND ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE", "No");
                                    unset($GLOBALS["NotificaErrorPorEmail"]);

                                    //SI EXISTE LA ACCION AVISO, LA DAMOS DE BAJA
                                    if ($rowAccionAvisoRelacionada != false):

                                        $sqlUpdate = "UPDATE ORDEN_TRANSPORTE_ACCION_AVISO SET BAJA = 1 WHERE ID_ORDEN_TRANSPORTE_ACCION_AVISO = $rowAccionAvisoRelacionada->ID_ORDEN_TRANSPORTE_ACCION_AVISO";
                                        $bd->ExecSQL($sqlUpdate);

                                        //BUSCAMOS SI EXITE ALGUNA HIJA A LA QUE DAR DE BAJA
                                        $sqlAccionesRelacionadasHija    = $this->getQueryAccionesTransporte($rowOrdenTransporte->ID_ORDEN_TRANSPORTE, "ACCION_PREVIA = '" . $rowAccionesRelacionadas->TIPO_ACCION . "'");
                                        $resultAccionesRelacionadasHija = $bd->ExecSQL($sqlAccionesRelacionadasHija);

                                        while ($rowAccionesRelacionadasHija = $bd->SigReg($resultAccionesRelacionadasHija)):

                                            //COMPROBAMOS QUE NO ESTE GENERADA YA LA ACCION
                                            $GLOBALS["NotificaErrorPorEmail"] = "No";
                                            $rowAccionAvisoRelacionadaHija    = $bd->VerRegRest("ORDEN_TRANSPORTE_ACCION_AVISO", "ID_ORDEN_TRANSPORTE_ACCION = $rowAccionesRelacionadasHija->ID_ORDEN_TRANSPORTE_ACCION AND BAJA = 0 AND ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE", "No");
                                            unset($GLOBALS["NotificaErrorPorEmail"]);

                                            //SI EXISTE LA ACCION AVISO, LA DAMOS DE BAJA
                                            if ($rowAccionAvisoRelacionadaHija != false):
                                                $sqlUpdate = "UPDATE ORDEN_TRANSPORTE_ACCION_AVISO SET BAJA = 1 WHERE ID_ORDEN_TRANSPORTE_ACCION_AVISO = $rowAccionAvisoRelacionadaHija->ID_ORDEN_TRANSPORTE_ACCION_AVISO";
                                                $bd->ExecSQL($sqlUpdate);
                                            endif;
                                        endwhile;
                                    endif;
                                endwhile;
                                //FIN RECORREMOS LAS ACCIONES RELACIONADAS
                            endif;
                        endif;
                    endif;
                endif;

                //COMPROBAMOS SI SIGUE EL CICLO NORMAL O EL CICLO DE ACEPTACION/RECHAZO
                if (($rowAccion->TIPO_ACCION == "Revisar Borrador BL") && ($rowOrdenTransporte->ACEPTACION_BORRADOR_BL == 0))://SI SE HA VUELTO A INTRODUCIR, VOLVEMOS A ACTIVAR LA ACCION DE ACEPTACION
                    $whereAccion = "TIPO_ACCION = 'Aceptar/Rechazar Borrador BL'";

                elseif (($rowAccion->TIPO_ACCION == "Revisar BL Definitivo") && ($rowOrdenTransporte->ACEPTACION_BL_DEFINITIVO == 0))://SI SE HA VUELTO A INTRODUCIR, VOLVEMOS A ACTIVAR LA ACCION DE ACEPTACION
                    $whereAccion = "TIPO_ACCION = 'Aceptar/Rechazar BL Definitivo'";

                elseif (($rowAccion->TIPO_ACCION == "Revisar CMR") && ($rowOrdenTransporte->ACEPTACION_CMR == 0))://SI SE HA VUELTO A INTRODUCIR, VOLVEMOS A ACTIVAR LA ACCION DE ACEPTACION
                    $whereAccion = "TIPO_ACCION = 'Aceptar/Rechazar CMR'";

                elseif (($rowAccion->TIPO_ACCION == "Modificar Documentacion") && ($rowOrdenTransporte->ACEPTACION_DOCUMENTACION_PROVEEDOR == 0))://SI SE HA VUELTO A INTRODUCIR, VOLVEMOS A ACTIVAR LA ACCION DE ACEPTACION
                    $whereAccion = "TIPO_ACCION = 'Aceptar/Rechazar Documentacion Proveedor'";

                elseif (($rowAccion->TIPO_ACCION == "Aceptar/Rechazar Borrador BL") && ($rowOrdenTransporte->ACEPTACION_BORRADOR_BL == 2))://SI SE RECHAZA NO ACTIVAMOS LA DE AÑADIR BL
                    $whereAccion = " (TIPO_ACCION <> 'Añadir BL Definitivo' AND ACCION_PREVIA = '" . $bd->escapeCondicional($rowAccion->TIPO_ACCION) . "')";

                elseif (($rowAccion->TIPO_ACCION == "Aceptar/Rechazar Borrador BL") && ($rowOrdenTransporte->ACEPTACION_BORRADOR_BL == 1))://SI SE HA ACEPTADO, NO CREAMOS REVISION
                    $whereAccion = " (TIPO_ACCION <> 'Revisar Borrador BL' AND ACCION_PREVIA = '" . $bd->escapeCondicional($rowAccion->TIPO_ACCION) . "')";

                elseif (($rowAccion->TIPO_ACCION == "Aceptar/Rechazar BL Definitivo") && ($rowOrdenTransporte->ACEPTACION_BL_DEFINITIVO == 1))://SI SE HA ACEPTADO, NO CREAMOS REVISION
                    $whereAccion = " (TIPO_ACCION <> 'Revisar BL Definitivo' AND ACCION_PREVIA = '" . $bd->escapeCondicional($rowAccion->TIPO_ACCION) . "')";

                elseif (($rowAccion->TIPO_ACCION == "Aceptar/Rechazar CMR") && ($rowOrdenTransporte->ACEPTACION_CMR == 1))://SI SE HA VUELTO A INTRODUCIR, VOLVEMOS A ACTIVAR LA ACCION DE ACEPTACION
                    $whereAccion = " (TIPO_ACCION <> 'Revisar CMR' AND ACCION_PREVIA = '" . $bd->escapeCondicional($rowAccion->TIPO_ACCION) . "')";
                else:
                    //BUSCAMOS SI TENEMOS QUE GENERAR ALGUNA
                    $whereAccion = "ACCION_PREVIA = '" . $bd->escapeCondicional($rowAccion->TIPO_ACCION) . "'";
                endif;

                //OBTENEMOS LA QUERY
                $sqlAccionesRelacionadas    = $this->getQueryAccionesTransporte($rowOrdenTransporte->ID_ORDEN_TRANSPORTE, $whereAccion);
                $resultAccionesRelacionadas = $bd->ExecSQL($sqlAccionesRelacionadas);

                while ($rowAccionesRelacionadas = $bd->SigReg($resultAccionesRelacionadas)):

                    //COMPROBAMOS QUE NO ESTE GENERADA YA LA ACCION
                    $GLOBALS["NotificaErrorPorEmail"] = "No";
                    $rowAccionAvisoRelacionada        = $bd->VerRegRest("ORDEN_TRANSPORTE_ACCION_AVISO", "ID_ORDEN_TRANSPORTE_ACCION = $rowAccionesRelacionadas->ID_ORDEN_TRANSPORTE_ACCION AND BAJA = 0 AND ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE", "No");
                    unset($GLOBALS["NotificaErrorPorEmail"]);

                    //SI NO EXISTE LA ACCION AVISO, LA GENERAMOS
                    if ($rowAccionAvisoRelacionada == false):
                        //OBTENEMOS LA FECHA RELACIONADA (SI VIENE UN CAMPO UTILIZAMOS EL CAMPO, SI NO, LA FECHA ACTUAL)
                        if ($rowAccionesRelacionadas->CAMPO_FECHA_RELACIONADO != ""):
                            $nombreCampo = $rowAccionesRelacionadas->CAMPO_FECHA_RELACIONADO;

                            $fechaRelacionadaAccion = $rowOrdenTransporte->$nombreCampo;
                        else:
                            $fechaRelacionadaAccion = date('Y-m-d H:i:s');
                        endif;

                        //INSERTAMOS EL REGISTRO
                        $sqlInsert = "INSERT INTO ORDEN_TRANSPORTE_ACCION_AVISO SET
                                      ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE
                                    , ID_ORDEN_TRANSPORTE_ACCION = $rowAccionesRelacionadas->ID_ORDEN_TRANSPORTE_ACCION
                                    , ESTADO_ACCION = 'Pendiente'
                                    , FECHA_CREACION = '" . date('Y-m-d H:i:s') . "'
                                    , FECHA_RELACIONADA = '" . $fechaRelacionadaAccion . "'";
                        $bd->ExecSQL($sqlInsert);

                        //OBTENEMOS EL ID
                        $idOrdenTransporteAccionAvisoGenerada = $bd->IdAsignado();

                        //LLAMAOS RECURSIVAMENTE A ESTA FUNCION POR SI DEBEMOS FINALIZARLA, SI NO ES UN HITO DE CAMBIO DE ESTADO
                        if ($rowAccionesRelacionadas->HITO_CAMBIO_ESTADO == 0):
                            $this->actualizarEstadoAccionConstruccion($idOrdenTransporteAccionAvisoGenerada);
                        endif;
                    else:
                        //LLAMAOS RECURSIVAMENTE A ESTA FUNCION POR SI DEBEMOS FINALIZARLA, SI NO ES UN HITO DE CAMBIO DE ESTADO
                        if ($rowAccionesRelacionadas->HITO_CAMBIO_ESTADO == 0):
                            $this->actualizarEstadoAccionConstruccion($rowAccionAvisoRelacionada->ID_ORDEN_TRANSPORTE_ACCION_AVISO);
                        endif;
                    endif;//FIN SI NO EXISTE LA ACCION AVISO, LA GENERAMOS

                endwhile;

                return true;
            endif;

        elseif ($rowAvisoAccion->ESTADO_ACCION == "Resuelta")://SI NO ESTAN RELLENOS LOS CAMPOS NECESARIOS DE LA ACCION, Y ESTA FINALIZADA, LA PASAMOS A PENDIENTE
            //ECHAMOS ATRAS LA ACCION
            $sqlUpdate = "UPDATE ORDEN_TRANSPORTE_ACCION_AVISO SET
                              ESTADO_ACCION = 'Pendiente'
                            , ID_ORDEN_TRANSPORTE_ACCION_AVISO_AGRUPADA = NULL
                            , FECHA_RESOLUCION = NULL
                            WHERE ID_ORDEN_TRANSPORTE_ACCION_AVISO = $rowAvisoAccion->ID_ORDEN_TRANSPORTE_ACCION_AVISO";
            $bd->ExecSQL($sqlUpdate);

        endif;

        return false;
    }

    /**
     * MIGRADO A CODEIGNITER (SI SE ACTUALIZA, ACTUALIZAR AMBAS)
     * @param $idOrdenTransporte
     * ACTUALIZA EL ESTADO DE UNA ACCION DEL TRANSPORTE, DEVUELVE CIERTO SI HA PODIDO FINALIZAR EL ESTADO
     */
    function actualizar_contrataciones_transporte_construccion($idOrdenTransporte, $idOrdenContratacion = "", $regenerar_informe_certificacion = false)
    {
        global $html;
        global $bd;
        global $administrador;
        global $auxiliar;
        global $incidencia_sistema;
        global $sap;

        // OBTENEMOS LA ORDEN DE TRANSPORTE CON LOS CAMPOS
        $rowOrdenTransporte = $this->getOrdenTransporteConstruccion($idOrdenTransporte);

        //SI SE FILTRA POR UNA ESPECIFICA
        $where_contratacion = "";
        if ($idOrdenContratacion != ""):
            $where_contratacion = " AND OC.ID_ORDEN_CONTRATACION = $idOrdenContratacion ";
        endif;

        //COMPRUEBO SI TIENE CONTRATACIONES
        $sqlContrataciones    = "SELECT OC.*
                                    FROM ORDEN_CONTRATACION OC
                                    WHERE OC.ID_ORDEN_TRANSPORTE = " . $rowOrdenTransporte->ID_ORDEN_TRANSPORTE . " AND OC.HITO_MANUAL = 0 AND OC.BAJA = 0 $where_contratacion";
        $resultContrataciones = $bd->ExecSQL($sqlContrataciones);

        //SI NOS HA DEVUELTO CAMPOS
        if ($bd->NumRegs($resultContrataciones) > 0):

            while ($rowOrdenContratacion = $bd->SigReg($resultContrataciones)):

                $fecha_ejecucion = ($rowOrdenContratacion->FECHA_EJECUCION == '0000-00-00' ? "" : $rowOrdenContratacion->FECHA_EJECUCION);
                $fecha_hito      = "";

                //BUSCAMOS EL HITO DE LA CONTRATACION
                $sqlHitoContratacion = "SELECT HC.*
                                          FROM ORDEN_TRANSPORTE_SERVICIO_PROVEEDOR OTSP
                                          INNER JOIN HITO_CONTRATACION HC ON HC.ID_HITO_CONTRATACION = OTSP.ID_HITO_CONTRATACION
                                          WHERE OTSP.ID_ORDEN_TRANSPORTE = " . $rowOrdenTransporte->ID_ORDEN_TRANSPORTE . " AND OTSP.ID_ORDEN_TRANSPORTE_SERVICIO_PROVEEDOR = " . $rowOrdenContratacion->ID_ORDEN_TRANSPORTE_SERVICIO_PROVEEDOR . " AND OTSP.ID_PROVEEDOR = " . $rowOrdenContratacion->ID_PROVEEDOR . " AND OTSP.BAJA = 0";

                $resultHitoContratacion = $bd->ExecSQL($sqlHitoContratacion);
                if ($bd->NumRegs($resultHitoContratacion) > 0):
                    $rowHito = $bd->SigReg($resultHitoContratacion);

                    //OBTENEMOS LA FECHA GUARDADA
                    $fecha_hito = $rowOrdenTransporte->{$rowHito->CAMPO_RELACIONADO};

                    //NOS QUEDAMOS SOLO CON LA FECHA
                    if ($fecha_hito != ""):
                        $fecha_hito = substr((string)$fecha_hito, 0, 10);
                    endif;

                    //SI ES 0000-00-00 LO TOMAMOS COMO VACIO
                    if ($fecha_hito == '0000-00-00'):
                        $fecha_hito = "";
                    endif;
                endif;

                //SI LA FECHA DEL HITO ES DIFERENTE A LA FECHA EJECUCION
                if (($fecha_ejecucion != $fecha_hito) || (($fecha_ejecucion == "") && ($fecha_hito == "")) || ($regenerar_informe_certificacion)):

                    //SI ESTA CERTIFICADO, NO ACTUALIZAREMOS Y CREAREMOS UNA INCIDENCIA DE SISTEMA
                    if ($rowOrdenContratacion->ESTADO == "Certificada"):
                        //GENERAMOS LA INCIDENCIA DE SISTEMA
                        $valoresModificados = "Fecha Ejecucion: " . $fecha_ejecucion . ". Nueva Fecha Hito: " . $fecha_hito . ". Hito: " . $rowHito->NOMBRE_ESP;
                        $incidencia_sistema->insertarIncidenciaSistema('Contratacion Certificada', 'Cambio Hito Ejecucion', 'ORDEN_CONTRATACION', $rowOrdenContratacion->ID_ORDEN_CONTRATACION, "", "", $valoresModificados);

                    else:
                        //ACTUALIZAMOS ESTADO DE LA CONTRATACION
                        $estado_contratacion = "";
                        $estado              = "";
                        if ($fecha_hito == "" && $rowOrdenContratacion->ESTADO != 'En Ejecucion' && $rowOrdenContratacion->CON_RETENCION != 1):
                            $estado_contratacion = " , ESTADO = 'En Ejecucion'";
                            $estado              = "En Ejecucion";

                            $this->desasignarReferenciaFacturacion($rowOrdenContratacion->ID_ORDEN_CONTRATACION);
                        elseif ($fecha_hito != ""):
                            if ($rowOrdenContratacion->ESTADO == 'En Ejecucion' && $fecha_hito <= date('Y-m-d')):
                                $estado_contratacion = " , ESTADO = 'Ejecutada'";
                                $estado              = "Ejecutada";
                            elseif ($rowOrdenContratacion->ESTADO == 'Ejecutada' && $fecha_hito > date('Y-m-d')):
                                $estado_contratacion = " , ESTADO = 'En Ejecucion'";
                                $estado              = "En Ejecucion";
                            endif;

                            //SI LA FECHA DEL HITO ES MAS TARDIA QUE LA ACTUAL Y LA CONTRATACION TIENE REFERENCIA DE FACTURACION, DESASIGNAMOS LA REFERENCIA DE FACTURACION
                            if (($fecha_hito > date('Y-m-d')) && ($rowOrdenContratacion->REFERENCIA_FACTURACION != "")):
                                $this->desasignarReferenciaFacturacion($rowOrdenContratacion->ID_ORDEN_CONTRATACION);
                            endif;

                        endif;

                        //OBTENEMOS LA SOCIEDAD CONTRATANTE
                        if ($rowOrdenContratacion->ID_ORDEN_TRANSPORTE != ''):
                            $rowSociedadContratante = $bd->VerReg("SOCIEDAD", "ID_SOCIEDAD", $rowOrdenTransporte->ID_SOCIEDAD_CONTRATANTE, "No");
                        else:
                            $rowSociedadContratante = $bd->VerReg("SOCIEDAD", "ID_SOCIEDAD", $rowOrdenContratacion->ID_SOCIEDAD_CONTRATANTE, "No");
                        endif;

                        //COMPROBAMOS SI LA OT TIENE ASOCIADA SOCIEDAD CONTRATANTE
                        $html->PagErrorCondicionado($rowSociedadContratante->ID_SOCIEDAD, "==", NULL, $auxiliar->traduce("No se ha podido realizar la accion debido a que la OT", $administrador->ID_IDIOMA) . " $rowOrdenTransporte->ID_ORDEN_TRANSPORTE " . $auxiliar->traduce("no tiene asociada sociedad contratante", $administrador->ID_IDIOMA) . ".");

                        //ACTUALIZAMOS LA FECHA EJECUCION
                        $sqlUpdate = "UPDATE ORDEN_CONTRATACION SET
                                   FECHA_EJECUCION = " . ($fecha_hito != "" ? "'" . $fecha_hito . "'" : "NULL") . "
                                   $estado_contratacion
                                   WHERE ID_ORDEN_CONTRATACION = $rowOrdenContratacion->ID_ORDEN_CONTRATACION";
                        $bd->ExecSQL($sqlUpdate);

                        //OBTENEMOS LA ROW ACTUALIZADA
                        $rowOrdenContratacion = $bd->VerReg("ORDEN_CONTRATACION", "ID_ORDEN_CONTRATACION", $rowOrdenContratacion->ID_ORDEN_CONTRATACION);

                        //SI SE HA EJECUTADO LA CONTRATACIÓN Y ES DE CONSTRUCCIÓN, CREAMOS Y GUARDAMOS EL TIPO DE CAMBIO DE MONEDA
                        if (($estado == "Ejecutada") && ($rowOrdenContratacion->TIPO_ORDEN_TRANSPORTE == 'OTC')):

                            //OBTENGO LA ORDEN DE TRANSPORTE ASOCIADA A LA CONTRATACIÓN
                            if ($rowOrdenContratacion->ID_ORDEN_TRANSPORTE != NULL):

                                $rowOrdenTransporteConstruccion = $this->getOrdenTransporteConstruccion($rowOrdenContratacion->ID_ORDEN_TRANSPORTE);

                            elseif ($rowOrdenContratacion->ID_ORDEN_TRANSPORTE_SERVICIO_PROVEEDOR != NULL):

                                $GLOBALS["NotificaErrorPorEmail"] = "No";
                                $rowOTSP                          = $bd->VerReg("ORDEN_TRANSPORTE_SERVICIO_PROVEEDOR", "ID_ORDEN_TRANSPORTE_SERVICIO_PROVEEDOR", $rowOrdenContratacion->ID_ORDEN_TRANSPORTE_SERVICIO_PROVEEDOR, "No");
                                unset($GLOBALS["NotificaErrorPorEmail"]);

                                $rowOrdenTransporteConstruccion = $this->getOrdenTransporteConstruccion($rowOTSP->ID_ORDEN_TRANSPORTE);

                            endif;

                            //OBTENGO EL ID DE LA SOCIEDAD CONTRATANTE
                            $idSociedad = NULL;

                            if ($rowOrdenContratacion->ID_SOCIEDAD_CONTRATANTE != NULL):

                                //SI VIENE EN LA PROPIA CONTRATACIÓN, LA ASIGNO
                                $idSociedad = $rowOrdenContratacion->ID_SOCIEDAD_CONTRATANTE;

                            else:

                                //OBTENGO LA SOCIEDAD DE LA CONTRATACIÓN
                                if ($rowOrdenTransporteConstruccion->ID_ORDEN_TRANSPORTE != NULL):

                                    $GLOBALS["NotificaErrorPorEmail"] = "No";
                                    $rowSociedad                      = $bd->VerReg("SOCIEDAD", "ID_SOCIEDAD", $rowOrdenTransporteConstruccion->ID_SOCIEDAD_CONTRATANTE, "No");
                                    unset($GLOBALS["NotificaErrorPorEmail"]);

                                endif;

                                $idSociedad = $rowSociedad->ID_SOCIEDAD;

                            endif;

                            //OBTENEMOS EL PROYECTO
                            if ($rowOrdenTransporteConstruccion->ID_ORDEN_TRANSPORTE != NULL):

                                //SI LA CONTRATACIÓN TIENE ASIGNADA OT, LO OBTENGO DE LA OT
                                $GLOBALS["NotificaErrorPorEmail"] = "No";
                                $rowCentroFisico                  = $bd->VerReg("CENTRO_FISICO", "ID_CENTRO_FISICO", $rowOrdenTransporteConstruccion->ID_CENTRO_FISICO_PROYECTO, "No");
                                unset($GLOBALS["NotificaErrorPorEmail"]);

                            else:

                                //SI NO, LO OBTENGO DEL PEDIDO
                                //OBTENGO EN PRIMER LUGAR LA LINEA DEL PEDIDO
                                $GLOBALS["NotificaErrorPorEmail"] = "No";
                                $rowPedidoContratoLinea           = $bd->VerReg("PEDIDO_CONTRATO_LINEA", "ID_PEDIDO_CONTRATO_LINEA", $rowOrdenContratacion->ID_PEDIDO_CONTRATO_LINEA, "No");
                                unset($GLOBALS["NotificaErrorPorEmail"]);

                                //OBTENGO EL PEDIDO
                                $GLOBALS["NotificaErrorPorEmail"] = "No";
                                $rowPedidoContrato                = $bd->VerReg("PEDIDO_CONTRATO", "ID_PEDIDO_CONTRATO", $rowPedidoContratoLinea->ID_PEDIDO_CONTRATO, "No");
                                unset($GLOBALS["NotificaErrorPorEmail"]);

                                //OBTENEMOS EL PROYECTO
                                $GLOBALS["NotificaErrorPorEmail"] = "No";
                                $rowCentroFisico                  = $bd->VerReg("CENTRO_FISICO", "ID_CENTRO_FISICO", $rowPedidoContrato->ID_CENTRO_FISICO, "No");
                                unset($GLOBALS["NotificaErrorPorEmail"]);

                            endif;

                            if ($rowOrdenContratacion->TIPO_CAMBIO_MANUAL == 0):

                                //SI NO SE HA MODIFICADO MANUALMENTE EL TIPO DE CAMBIO, OBTENEMOS EL TIPO DE CAMBIO
                                $tipoCambio = 0;

                                //COMPROBAMOS SI EL PROYECTO TIENE LA MCP ASIGNADA
                                if ($rowCentroFisico->ID_MONEDA_CONTROL != NULL):
                                    if ($rowOrdenContratacion->ID_MONEDA == $rowCentroFisico->ID_MONEDA_CONTROL):

                                        //SI LA MONEDA DE ORIGEN Y DESTINO SON IGUALES, LO PONGO A 1
                                        $tipoCambio = 1;

                                    else:
                                        //REALIZAMOS LA LLAMADA A SAP PARA OBTENER EL IMPORTE CONVERTIDO
                                        $resultado = $sap->ConvertirImporteMonedaOrigenMonedaDestinoConstruccion($rowOrdenContratacion->IMPORTE_MODIFICADO, $rowOrdenContratacion->ID_MONEDA, $rowCentroFisico->ID_MONEDA_CONTROL, ($rowOrdenContratacion->FECHA_CONTABILIZACION != '0000-00-00' ? $rowOrdenContratacion->FECHA_CONTABILIZACION : $rowOrdenContratacion->FECHA_EJECUCION));

                                        if ($resultado['RESULTADO'] == 'OK'):

                                            //OBTENGO EL TIPO DE CAMBIO (IMPORTE CALCULADO ENTRE EL ORIGINAL)
                                            $tipoCambio = $resultado['IMPORTE_DEVUELTO'] / $rowOrdenContratacion->IMPORTE_MODIFICADO;

                                        endif;
                                    endif;
                                else:
                                    //SI NO LA TIENE MUESTRO UN MENSAJE DE ERROR
                                    $html->PagError($auxiliar->traduce("El proyecto seleccionado no tiene asignada la Moneda Control de Proyecto", $administrador->ID_IDIOMA));
                                endif;

                                //ACTUALIZAMOS LA CONTRATACION
                                $sqlUpdate = "UPDATE ORDEN_CONTRATACION SET
                                  TIPO_CAMBIO = $tipoCambio
                                  , ID_MONEDA_CONTROL_PROYECTO = " . ($rowCentroFisico->ID_MONEDA_CONTROL != NULL ? $rowCentroFisico->ID_MONEDA_CONTROL : "NULL") . "
                                  WHERE ID_ORDEN_CONTRATACION = $rowOrdenContratacion->ID_ORDEN_CONTRATACION";
                                $bd->ExecSQL($sqlUpdate);

                            endif;
                        endif;

                        //BUSCO EL NUMERO DE REFERENCIA DE FACTURACION CORRESPONDIENTE
//                            $arr_facturacion          = $this->ObtenerReferenciaFacturacionConstruccion($rowOrdenContratacion->ID_ORDEN_CONTRATACION);
//                            $numReferenciaFacturacion = $arr_facturacion['REFERENCIA_FACTURACION'];
//                            $fecha_contabilizacion    = $arr_facturacion['FECHA_CONTABILIZACION'];
//
//                            //GUARDO LA REFERENCIA DE FACTURACION
//                            $sqlUpdate = "UPDATE ORDEN_CONTRATACION SET
//                                           REFERENCIA_FACTURACION = '" . $numReferenciaFacturacion . "'
//                                           , FECHA_CONTABILIZACION = '" . $fecha_contabilizacion . "'
//                                           WHERE ID_ORDEN_CONTRATACION = $rowOrdenContratacion->ID_ORDEN_CONTRATACION";
//                            $bd->ExecSQL($sqlUpdate);

                        //REGENERAMOS INFORME DE CERTIFICACION SI ES NECESARIO
                        if ($regenerar_informe_certificacion):
                            $this->RegenerarInformeCertificacion($rowOrdenContratacion->ID_ORDEN_CONTRATACION, $regenerar_informe_certificacion);
                        endif;

                        //OBTENEMOS LA ROW ACTUALIZADA
                        $rowOrdenContratacionActualizada = $bd->VerReg("ORDEN_CONTRATACION", "ID_ORDEN_CONTRATACION", $rowOrdenContratacion->ID_ORDEN_CONTRATACION);

                        //LOG MOVIMIENTOS
                        $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Orden de Contratacion", $rowOrdenContratacion->ID_ORDEN_CONTRATACION, "Actualizar Datos Facturacion", "ORDEN_CONTRATACION", $rowOrdenContratacion, $rowOrdenContratacionActualizada);
                    endif;
                endif;
            endwhile;
        endif;

        //AHORA BUSCAMOS CONTRATACIONES DE BLs
        //OBTENEMOS EL ID DEL SERVICIO DE GESTIÓN DE BLS
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowServicio                      = $bd->VerRegRest("SERVICIO", "REFERENCIA='GESTION_BLS' AND BAJA=0", "No");
        if ($rowServicio != false && $rowOrdenTransporte->NUMERO_BL != ""):
            //SI SE HA INTRODUCIDO EL BL MASTER, COMPROBAMOS SI LA OT CONTIENE EL SERVICIO DE BLs (puede haber mas de uno)
            $sqlOTServicioBL    = "SELECT DISTINCT OTSP.ID_PROVEEDOR, HC.*
                                        FROM ORDEN_TRANSPORTE_SERVICIO_PROVEEDOR OTSP
                                        INNER JOIN HITO_CONTRATACION HC ON HC.ID_HITO_CONTRATACION = OTSP.ID_HITO_CONTRATACION
                                        WHERE OTSP.ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND OTSP.ID_SERVICIO= $rowServicio->ID_SERVICIO AND OTSP.ID_PROVEEDOR IS NOT NULL AND OTSP.BAJA=0";
            $resultOTServicioBL = $bd->ExecSQL($sqlOTServicioBL);

            //RECORREMOS LOS DIFERENTES PROVEEDORES DEL SERVICIO BL
            while ($rowOTServicioBL = $bd->SigReg($resultOTServicioBL)):
                //BUSCAMOS LA CONTRATACION (solo si el hito manual es 0)
                $sqlContratacionBL    = "SELECT OC.*
                                                FROM BL_MASTER BL 
                                                INNER JOIN ORDEN_CONTRATACION OC ON OC.ID_BL_MASTER = BL.ID_BL_MASTER
                                                WHERE BL.ID_PROVEEDOR = " . $rowOTServicioBL->ID_PROVEEDOR . " AND BL.ID_CENTRO_FISICO = " . $rowOrdenTransporte->ID_CENTRO_FISICO_PROYECTO . " AND BL.NUMERO_BL = '" . $bd->escapeCondicional($rowOrdenTransporte->NUMERO_BL) . "' AND OC.HITO_MANUAL = 0 AND BL.BAJA = 0 AND OC.BAJA = 0 $where_contratacion";
                $resultContratacionBL = $bd->ExecSQL($sqlContratacionBL);
                $rowContratacionBL    = $bd->SigReg($resultContratacionBL);

                //SI EXISTE, BUSCAMOS EL HITO
                if ($rowContratacionBL != false):

                    $fecha_ejecucion = ($rowContratacionBL->FECHA_EJECUCION == '0000-00-00' ? "" : $rowContratacionBL->FECHA_EJECUCION);
                    $fecha_hito      = "";

                    //MONTAMOS EL CAMPO DEL HITO
                    $tabla_hito = "";
                    switch ($rowOTServicioBL->TABLA_RELACIONADA):
                        case 'ORDEN_TRANSPORTE':
                            $tabla_hito = "OT";
                            break;
                        case 'ORDEN_TRANSPORTE_CONSTRUCCION':
                            $tabla_hito = "OTC";
                            break;
                        case 'ORDEN_TRANSPORTE_CONSTRUCCION_AMPLIACION':
                            $tabla_hito = "OTCA";
                            break;
                    endswitch;
                    $tabla_hito = $tabla_hito . "." . $rowOTServicioBL->CAMPO_RELACIONADO;

                    //BUSCAMOS LOS TRANSPORTES CON EL MISMO SERVICIO DE BL
                    $sqlFechaHito    = "SELECT DISTINCT OTC.ID_ORDEN_TRANSPORTE, " . $tabla_hito . "
                                                FROM ORDEN_TRANSPORTE OT
                                                INNER JOIN ORDEN_TRANSPORTE_CONSTRUCCION OTC ON OTC.ID_ORDEN_TRANSPORTE = OT.ID_ORDEN_TRANSPORTE
                                                INNER JOIN ORDEN_TRANSPORTE_CONSTRUCCION_AMPLIACION OTCA ON OTCA.ID_ORDEN_TRANSPORTE = OT.ID_ORDEN_TRANSPORTE
                                                INNER JOIN ORDEN_TRANSPORTE_SERVICIO_PROVEEDOR OTSP ON OTSP.ID_ORDEN_TRANSPORTE = OTC.ID_ORDEN_TRANSPORTE
                                                WHERE OTC.ID_CENTRO_FISICO_PROYECTO = $rowOrdenTransporte->ID_CENTRO_FISICO_PROYECTO AND OTSP.ID_SERVICIO = $rowServicio->ID_SERVICIO AND OTSP.ID_PROVEEDOR = $rowOTServicioBL->ID_PROVEEDOR AND OTC.NUMERO_BL = '" . $bd->escapeCondicional($rowOrdenTransporte->NUMERO_BL) . "' AND OTSP.BAJA = 0 AND OT.BAJA = 0
                                                      AND $tabla_hito IS NOT NULL
                                                ORDER BY $tabla_hito ASC";
                    $resultFechaHito = $bd->ExecSQL($sqlFechaHito);
                    if ($bd->NumRegs($resultFechaHito) > 0):
                        $rowFechaHito = $bd->SigReg($resultFechaHito);
                        //OBTENEMOS LA FECHA GUARDADA
                        $fecha_hito = $rowFechaHito->{$rowOTServicioBL->CAMPO_RELACIONADO};

                        //NOS QUEDAMOS SOLO CON LA FECHA
                        if ($fecha_hito != ""):
                            $fecha_hito = substr((string)$fecha_hito, 0, 10);
                        endif;

                        //SI ES 0000-00-00 LO TOMAMOS COMO VACIO
                        if ($fecha_hito == '0000-00-00'):
                            $fecha_hito = "";
                        endif;
                    endif;

                    //SI LA FECHA DEL HITO ES DIFERENTE A LA FECHA EJECUCION
                    if (($fecha_ejecucion != $fecha_hito) || (($fecha_ejecucion == "") && ($fecha_hito == "")) || ($regenerar_informe_certificacion)):

                        //SI ESTA CERTIFICADO, NO ACTUALIZAREMOS Y CREAREMOS UNA INCIDENCIA DE SISTEMA
                        if ($rowContratacionBL->ESTADO == "Certificada"):
                            //GENERAMOS LA INCIDENCIA DE SISTEMA
                            $valoresModificados = "Fecha Ejecucion: " . $fecha_ejecucion . ". Nueva Fecha Hito: " . $fecha_hito . ". Hito: " . $rowOTServicioBL->NOMBRE_ESP;
                            $incidencia_sistema->insertarIncidenciaSistema('Contratacion Certificada', 'Cambio Hito Ejecucion', 'ORDEN_CONTRATACION', $rowContratacionBL->ID_ORDEN_CONTRATACION, "", "", $valoresModificados);

                        else:
                            //ACTUALIZAMOS ESTADO DE LA CONTRATACION
                            $estado_contratacion = "";
                            $estado              = "";
                            if ($fecha_hito == "" && $rowContratacionBL->ESTADO != 'En Ejecucion'):
                                $estado_contratacion = " , ESTADO = 'En Ejecucion'";
                                $estado              = "En Ejecucion";

                                $this->desasignarReferenciaFacturacion($rowContratacionBL->ID_ORDEN_CONTRATACION);
                            elseif ($fecha_hito != ""):
                                if ($rowContratacionBL->ESTADO == 'En Ejecucion' && $fecha_hito <= date('Y-m-d')):
                                    $estado_contratacion = " , ESTADO = 'Ejecutada'";
                                    $estado              = "Ejecutada";
                                elseif ($rowContratacionBL->ESTADO == 'Ejecutada' && $fecha_hito > date('Y-m-d')):
                                    $estado_contratacion = " , ESTADO = 'En Ejecucion'";
                                    $estado              = "En Ejecucion";
                                endif;

                                //SI LA FECHA DEL HITO ES MAS TARDIA QUE LA ACTUAL Y LA CONTRATACION TIENE REFERENCIA DE FACTURACION, DESASIGNAMOS LA REFERENCIA DE FACTURACION
                                if (($fecha_hito > date('Y-m-d')) && ($rowContratacionBL->REFERENCIA_FACTURACION != "")):
                                    $this->desasignarReferenciaFacturacion($rowContratacionBL->ID_ORDEN_CONTRATACION);
                                endif;
                            endif;

                            //ACTUALIZAMOS LA FECHA EJECUCION
                            $sqlUpdate = "UPDATE ORDEN_CONTRATACION SET
                                   FECHA_EJECUCION = " . ($fecha_hito != "" ? "'" . $fecha_hito . "'" : "NULL") . "
                                   $estado_contratacion
                                   WHERE ID_ORDEN_CONTRATACION = $rowContratacionBL->ID_ORDEN_CONTRATACION";
                            $bd->ExecSQL($sqlUpdate);

                            //OBTENEMOS LA ROW ACTUALIZADA
                            $rowContratacionBL = $bd->VerReg("ORDEN_CONTRATACION", "ID_ORDEN_CONTRATACION", $rowContratacionBL->ID_ORDEN_CONTRATACION);

                            //SI SE HA EJECUTADO LA CONTRATACIÓN Y ES DE CONSTRUCCIÓN, CREAMOS Y GUARDAMOS EL TIPO DE CAMBIO DE MONEDA
                            if (($estado == "Ejecutada") && ($rowContratacionBL->TIPO_ORDEN_TRANSPORTE == 'OTC')):

                                //OBTENGO LA ORDEN DE TRANSPORTE ASOCIADA A LA CONTRATACIÓN
                                if ($rowContratacionBL->ID_ORDEN_TRANSPORTE != NULL):

                                    $rowOrdenTransporteConstruccion = $this->getOrdenTransporteConstruccion($rowContratacionBL->ID_ORDEN_TRANSPORTE);

                                elseif ($rowContratacionBL->ID_ORDEN_TRANSPORTE_SERVICIO_PROVEEDOR != NULL):

                                    $GLOBALS["NotificaErrorPorEmail"] = "No";
                                    $rowOTSP                          = $bd->VerReg("ORDEN_TRANSPORTE_SERVICIO_PROVEEDOR", "ID_ORDEN_TRANSPORTE_SERVICIO_PROVEEDOR", $rowContratacionBL->ID_ORDEN_TRANSPORTE_SERVICIO_PROVEEDOR, "No");
                                    unset($GLOBALS["NotificaErrorPorEmail"]);

                                    $rowOrdenTransporteConstruccion = $this->getOrdenTransporteConstruccion($rowOTSP->ID_ORDEN_TRANSPORTE);

                                endif;

                                //OBTENGO EL ID DE LA SOCIEDAD CONTRATANTE
                                $idSociedad = NULL;

                                if ($rowContratacionBL->ID_SOCIEDAD_CONTRATANTE != NULL):

                                    //SI VIENE EN LA PROPIA CONTRATACIÓN, LA ASIGNO
                                    $idSociedad = $rowContratacionBL->ID_SOCIEDAD_CONTRATANTE;

                                else:

                                    //OBTENGO LA SOCIEDAD DE LA CONTRATACIÓN
                                    if ($rowOrdenTransporteConstruccion->ID_ORDEN_TRANSPORTE != NULL):

                                        $GLOBALS["NotificaErrorPorEmail"] = "No";
                                        $rowSociedad                      = $bd->VerReg("SOCIEDAD", "ID_SOCIEDAD", $rowOrdenTransporteConstruccion->ID_SOCIEDAD_CONTRATANTE, "No");
                                        unset($GLOBALS["NotificaErrorPorEmail"]);

                                    endif;

                                    $idSociedad = $rowSociedad->ID_SOCIEDAD;

                                endif;

                                //OBTENEMOS EL PROYECTO
                                if ($rowOrdenTransporteConstruccion->ID_ORDEN_TRANSPORTE != NULL):

                                    //SI LA CONTRATACIÓN TIENE ASIGNADA OT, LO OBTENGO DE LA OT
                                    $GLOBALS["NotificaErrorPorEmail"] = "No";
                                    $rowCentroFisico                  = $bd->VerReg("CENTRO_FISICO", "ID_CENTRO_FISICO", $rowOrdenTransporteConstruccion->ID_CENTRO_FISICO_PROYECTO, "No");
                                    unset($GLOBALS["NotificaErrorPorEmail"]);

                                else:

                                    //SI NO, LO OBTENGO DEL PEDIDO
                                    //OBTENGO EN PRIMER LUGAR LA LINEA DEL PEDIDO
                                    $GLOBALS["NotificaErrorPorEmail"] = "No";
                                    $rowPedidoContratoLinea           = $bd->VerReg("PEDIDO_CONTRATO_LINEA", "ID_PEDIDO_CONTRATO_LINEA", $rowContratacionBL->ID_PEDIDO_CONTRATO_LINEA, "No");
                                    unset($GLOBALS["NotificaErrorPorEmail"]);

                                    //OBTENGO EL PEDIDO
                                    $GLOBALS["NotificaErrorPorEmail"] = "No";
                                    $rowPedidoContrato                = $bd->VerReg("PEDIDO_CONTRATO", "ID_PEDIDO_CONTRATO", $rowPedidoContratoLinea->ID_PEDIDO_CONTRATO, "No");
                                    unset($GLOBALS["NotificaErrorPorEmail"]);

                                    //OBTENEMOS EL PROYECTO
                                    $GLOBALS["NotificaErrorPorEmail"] = "No";
                                    $rowCentroFisico                  = $bd->VerReg("CENTRO_FISICO", "ID_CENTRO_FISICO", $rowPedidoContrato->ID_CENTRO_FISICO, "No");
                                    unset($GLOBALS["NotificaErrorPorEmail"]);

                                endif;

                                if ($rowContratacionBL->TIPO_CAMBIO_MANUAL == 0):

                                    //SI NO SE HA MODIFICADO MANUALMENTE EL TIPO DE CAMBIO, OBTENEMOS EL TIPO DE CAMBIO
                                    $tipoCambio = 0;

                                    //COMPROBAMOS SI EL PROYECTO TIENE LA MCP ASIGNADA
                                    if ($rowCentroFisico->ID_MONEDA_CONTROL != NULL):
                                        if ($rowContratacionBL->ID_MONEDA == $rowCentroFisico->ID_MONEDA_CONTROL):

                                            //SI LA MONEDA DE ORIGEN Y DESTINO SON IGUALES, LO PONGO A 1
                                            $tipoCambio = 1;

                                        else:
                                            //REALIZAMOS LA LLAMADA A SAP PARA OBTENER EL IMPORTE CONVERTIDO
                                            $resultado = $sap->ConvertirImporteMonedaOrigenMonedaDestinoConstruccion($rowContratacionBL->IMPORTE_MODIFICADO, $rowContratacionBL->ID_MONEDA, $rowCentroFisico->ID_MONEDA_CONTROL, ($rowContratacionBL->FECHA_CONTABILIZACION != '0000-00-00' ? $rowContratacionBL->FECHA_CONTABILIZACION : $rowContratacionBL->FECHA_EJECUCION));

                                            if ($resultado['RESULTADO'] == 'OK'):

                                                //OBTENGO EL TIPO DE CAMBIO (IMPORTE CALCULADO ENTRE EL ORIGINAL)
                                                $tipoCambio = $resultado['IMPORTE_DEVUELTO'] / $rowContratacionBL->IMPORTE_MODIFICADO;

                                            endif;
                                        endif;
                                    else:
                                        //SI NO LA TIENE MUESTRO UN MENSAJE DE ERROR
                                        $html->PagError($auxiliar->traduce("El proyecto seleccionado no tiene asignada la Moneda Control de Proyecto", $administrador->ID_IDIOMA));
                                    endif;

                                    //ACTUALIZAMOS LA CONTRATACION
                                    $sqlUpdate = "UPDATE ORDEN_CONTRATACION SET
                                                  TIPO_CAMBIO = $tipoCambio
                                                  , ID_MONEDA_CONTROL_PROYECTO = " . ($rowCentroFisico->ID_MONEDA_CONTROL != NULL ? $rowCentroFisico->ID_MONEDA_CONTROL : "NULL") . "
                                                  WHERE ID_ORDEN_CONTRATACION = $rowContratacionBL->ID_ORDEN_CONTRATACION";
                                    $bd->ExecSQL($sqlUpdate);

                                endif;
                            endif;

                            //BUSCO EL NUMERO DE REFERENCIA DE FACTURACION CORRESPONDIENTE
//                            $arr_facturacion          = $this->ObtenerReferenciaFacturacionConstruccion($rowContratacionBL->ID_ORDEN_CONTRATACION);
//                            $numReferenciaFacturacion = $arr_facturacion['REFERENCIA_FACTURACION'];
//                            $fecha_contabilizacion    = $arr_facturacion['FECHA_CONTABILIZACION'];
//
//                            //GUARDO LA REFERENCIA DE FACTURACION
//                            $sqlUpdate = "UPDATE ORDEN_CONTRATACION SET
//                                           REFERENCIA_FACTURACION = '" . $numReferenciaFacturacion . "'
//                                           , FECHA_CONTABILIZACION = '" . $fecha_contabilizacion . "'
//                                           WHERE ID_ORDEN_CONTRATACION = $rowContratacionBL->ID_ORDEN_CONTRATACION";
//                            $bd->ExecSQL($sqlUpdate);

                            if ($regenerar_informe_certificacion): //SI SE PUEDE REGENERAR EL INFORME DE CERTIFICACION OBTENEMOS LA REFERENCIA DE FACTURACION Y REGENERAMOS EL INFORME DE CERTIFICACION
                                //REGENERAMOS INFORME DE CERTIFICACION SI ES NECESARIO
                                $this->RegenerarInformeCertificacion($rowContratacionBL->ID_ORDEN_CONTRATACION);
                            endif;

                            //OBTENEMOS LA ROW ACTUALIZADA
                            $rowOrdenContratacionActualizada = $bd->VerReg("ORDEN_CONTRATACION", "ID_ORDEN_CONTRATACION", $rowContratacionBL->ID_ORDEN_CONTRATACION);

                            //LOG MOVIMIENTOS
                            $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Orden de Contratacion", $rowContratacionBL->ID_ORDEN_CONTRATACION, "Actualizar Datos Facturacion", "ORDEN_CONTRATACION", $rowContratacionBL, $rowOrdenContratacionActualizada);
                        endif;
                    endif;//FIN FECHA EJECUCION CAMBIADA

                endif;//FIN EXISTE CONTRATACION BL
            endwhile;//FIN RECORREMOS LOS DIFERENTES PROVEEDORES DEL SERVICIO BL

        endif;//FIN CONTRATACION BL


        return false;
    }


    /**
     * MIGRADO A CODEIGNITER (SI SE ACTUALIZA, ACTUALIZAR AMBAS)
     * @param $idOrdenTransporte
     * ACTUALIZA EL ESTADO DE UNA ACCION DEL TRANSPORTE, DEVUELVE CIERTO SI HA PODIDO FINALIZAR EL ESTADO
     */
    function actualizar_contratacion_manual_transporte_construccion($idOrdenTransporte = "", $idOrdenContratacion = "", $fecha_previa = "", $regenerar_informe_certificacion = false)
    {
        global $html;
        global $bd;
        global $administrador;
        global $auxiliar;
        global $incidencia_sistema;
        global $sap;

        //SI SE FILTRA POR UNA ESPECIFICA
        $where_contratacion = "";
        if ($idOrdenContratacion != ""):
            $where_contratacion = " AND ID_ORDEN_CONTRATACION = $idOrdenContratacion ";
        endif;

        if ($fecha_previa == '0000-00-00'):
            $fecha_previa = "";
        endif;

        $resultContrataciones = false;

        if ($idOrdenTransporte != NULL):
            //OBTENEMOS LA ORDEN DE TRANSPORTE CON LOS CAMPOS, SI OBTENEMOS SU ID
            $rowOrdenTransporte = $this->getOrdenTransporteConstruccion($idOrdenTransporte);

            //COMPRUEBO SI TIENE CONTRATACIONES
            $sqlContrataciones    = "SELECT *
                                    FROM ORDEN_CONTRATACION 
                                    WHERE ID_ORDEN_TRANSPORTE = " . $rowOrdenTransporte->ID_ORDEN_TRANSPORTE . " AND HITO_MANUAL = 1 AND BAJA = 0 $where_contratacion";
            $resultContrataciones = $bd->ExecSQL($sqlContrataciones);
        else:
            //SI NO OBTENEMOS EL ID DE LA ORDEN DE TRANSPORTE OBTENEMOS LA ROW DE LA ORDEN DE CONTRATACIÓN
            $NotificaErrorPorEmail = "No";
            $rowOrdenContratacion  = $bd->VerReg("ORDEN_CONTRATACION", "ID_ORDEN_CONTRATACION", $idOrdenContratacion, "No");

            $sqlContrataciones    = "SELECT *
                                    FROM ORDEN_CONTRATACION 
                                    WHERE ID_SOCIEDAD_CONTRATANTE = " . $rowOrdenContratacion->ID_SOCIEDAD_CONTRATANTE . " AND ID_PROVEEDOR = " . $rowOrdenContratacion->ID_PROVEEDOR . " AND HITO_MANUAL = 1 AND BAJA = 0 $where_contratacion";
            $resultContrataciones = $bd->ExecSQL($sqlContrataciones);

            //OBTENEMOS LA INFORMACIÓN DE LAS OTS ASOCIADAS A LA CONTRATACION
            $rowOrdenTransporte = false;
            if ($rowOrdenContratacion->ID_ORDEN_TRANSPORTE_SERVICIO_PROVEEDOR != ""):
                $rowOTSP = $bd->VerReg("ORDEN_TRANSPORTE_SERVICIO_PROVEEDOR", "ID_ORDEN_TRANSPORTE_SERVICIO_PROVEEDOR", $rowOrdenContratacion->ID_ORDEN_TRANSPORTE_SERVICIO_PROVEEDOR);

                $rowOrdenTransporte = $this->getOrdenTransporteConstruccion($rowOTSP->ID_ORDEN_TRANSPORTE);
            endif;
        endif;

        //SI NOS HA DEVUELTO CAMPOS
        if ($bd->NumRegs($resultContrataciones) > 0):

            while ($rowOrdenContratacion = $bd->SigReg($resultContrataciones)):

                $fecha_ejecucion = ($rowOrdenContratacion->FECHA_EJECUCION == '0000-00-00' ? "" : $rowOrdenContratacion->FECHA_EJECUCION);

                //SI ESTA CERTIFICADO, NO ACTUALIZAREMOS Y CREAREMOS UNA INCIDENCIA DE SISTEMA
                if ($rowOrdenContratacion->ESTADO == "Certificada"):
                    //SI LA FECHA DEL HITO ES DIFERENTE A LA FECHA EJECUCION
                    if ($fecha_ejecucion != $fecha_previa):
                        //GENERAMOS LA INCIDENCIA DE SISTEMA
                        $valoresModificados = "Fecha Ejecucion: " . $fecha_previa . ". Nueva Fecha : " . $fecha_ejecucion;
                        $html->PagError("EstadoContratacionIncorrecto");
                    endif;
                else:
                    //ACTUALIZAMOS ESTADO DE LA CONTRATACION
                    $estado_contratacion = "";
                    $estado              = "";
                    if ($fecha_ejecucion == "" && $rowOrdenContratacion->ESTADO != 'En Ejecucion' && $rowOrdenContratacion->CON_RETENCION != 1):
                        $estado_contratacion = " , ESTADO = 'En Ejecucion'";
                        $estado              = "En Ejecucion";

                        $this->desasignarReferenciaFacturacion($rowOrdenContratacion->ID_ORDEN_CONTRATACION);
                    elseif ($fecha_ejecucion != ""):
                        if ($rowOrdenContratacion->ESTADO == 'En Ejecucion' && $fecha_ejecucion <= date('Y-m-d')):
                            $estado_contratacion = " , ESTADO = 'Ejecutada'";
                            $estado              = "Ejecutada";
                        elseif ($rowOrdenContratacion->ESTADO == 'Ejecutada' && $fecha_ejecucion > date('Y-m-d')):
                            $estado_contratacion = " , ESTADO = 'En Ejecucion'";
                            $estado              = "En Ejecucion";
                        endif;

                        //SI LA FECHA DEL HITO ES MAS TARDIA QUE LA ACTUAL Y LA CONTRATACION TIENE REFERENCIA DE FACTURACION, DESASIGNAMOS LA REFERENCIA DE FACTURACION
                        if (($fecha_hito > date('Y-m-d')) && ($rowOrdenContratacion->REFERENCIA_FACTURACION != "")):
                            $this->desasignarReferenciaFacturacion($rowOrdenContratacion->ID_ORDEN_CONTRATACION);
                        endif;
                    endif;

                    //OBTENEMOS LA SOCIEDAD CONTRATANTE
                    if ($rowOrdenContratacion->ID_ORDEN_TRANSPORTE != ''):
                        $rowSociedadContratante = $bd->VerReg("SOCIEDAD", "ID_SOCIEDAD", $rowOrdenTransporte->ID_SOCIEDAD_CONTRATANTE, "No");
                    else:
                        $rowSociedadContratante = $bd->VerReg("SOCIEDAD", "ID_SOCIEDAD", $rowOrdenContratacion->ID_SOCIEDAD_CONTRATANTE, "No");
                    endif;

                    //COMPROBAMOS SI LA OT TIENE ASOCIADA SOCIEDAD CONTRATANTE
                    $html->PagErrorCondicionado($rowSociedadContratante->ID_SOCIEDAD, "==", NULL, $auxiliar->traduce("No se ha podido realizar la accion debido a que la OT", $administrador->ID_IDIOMA) . " $rowOrdenTransporte->ID_ORDEN_TRANSPORTE " . $auxiliar->traduce("no tiene asociada sociedad contratante", $administrador->ID_IDIOMA) . ".");

                    //GUARDO LA REFERENCIA DE FACTURACION
                    $sqlUpdate = "UPDATE ORDEN_CONTRATACION SET
                                        BAJA = 0
                                       $estado_contratacion
                                       WHERE ID_ORDEN_CONTRATACION = $rowOrdenContratacion->ID_ORDEN_CONTRATACION";
                    $bd->ExecSQL($sqlUpdate);

                    //OBTENEMOS LA ROW ACTUALIZADA
                    $rowOrdenContratacion = $bd->VerReg("ORDEN_CONTRATACION", "ID_ORDEN_CONTRATACION", $rowOrdenContratacion->ID_ORDEN_CONTRATACION);

                    //SI SE HA EJECUTADO LA CONTRATACIÓN Y ES DE CONSTRUCCIÓN, CREAMOS Y GUARDAMOS EL TIPO DE CAMBIO DE MONEDA
                    if (($estado == "Ejecutada") && ($rowOrdenContratacion->TIPO_ORDEN_TRANSPORTE == 'OTC')):

                        //OBTENGO LA ORDEN DE TRANSPORTE ASOCIADA A LA CONTRATACIÓN
                        if ($rowOrdenContratacion->ID_ORDEN_TRANSPORTE != NULL):

                            $rowOrdenTransporteConstruccion = $this->getOrdenTransporteConstruccion($rowOrdenContratacion->ID_ORDEN_TRANSPORTE);

                        elseif ($rowOrdenContratacion->ID_ORDEN_TRANSPORTE_SERVICIO_PROVEEDOR != NULL):

                            $GLOBALS["NotificaErrorPorEmail"] = "No";
                            $rowOTSP                          = $bd->VerReg("ORDEN_TRANSPORTE_SERVICIO_PROVEEDOR", "ID_ORDEN_TRANSPORTE_SERVICIO_PROVEEDOR", $rowOrdenContratacion->ID_ORDEN_TRANSPORTE_SERVICIO_PROVEEDOR, "No");
                            unset($GLOBALS["NotificaErrorPorEmail"]);

                            $rowOrdenTransporteConstruccion = $this->getOrdenTransporteConstruccion($rowOTSP->ID_ORDEN_TRANSPORTE);

                        endif;

                        //OBTENGO EL ID DE LA SOCIEDAD CONTRATANTE
                        $idSociedad = NULL;

                        if ($rowOrdenContratacion->ID_SOCIEDAD_CONTRATANTE != NULL):

                            //SI VIENE EN LA PROPIA CONTRATACIÓN, LA ASIGNO
                            $idSociedad = $rowOrdenContratacion->ID_SOCIEDAD_CONTRATANTE;

                        else:

                            //OBTENGO LA SOCIEDAD DE LA CONTRATACIÓN
                            if ($rowOrdenTransporteConstruccion->ID_ORDEN_TRANSPORTE != NULL):

                                $GLOBALS["NotificaErrorPorEmail"] = "No";
                                $rowSociedad                      = $bd->VerReg("SOCIEDAD", "ID_SOCIEDAD", $rowOrdenTransporteConstruccion->ID_SOCIEDAD_CONTRATANTE, "No");
                                unset($GLOBALS["NotificaErrorPorEmail"]);

                            endif;

                            $idSociedad = $rowSociedad->ID_SOCIEDAD;

                        endif;

                        //OBTENEMOS EL PROYECTO
                        if ($rowOrdenTransporteConstruccion->ID_ORDEN_TRANSPORTE != NULL):

                            //SI LA CONTRATACIÓN TIENE ASIGNADA OT, LO OBTENGO DE LA OT
                            $GLOBALS["NotificaErrorPorEmail"] = "No";
                            $rowCentroFisico                  = $bd->VerReg("CENTRO_FISICO", "ID_CENTRO_FISICO", $rowOrdenTransporteConstruccion->ID_CENTRO_FISICO_PROYECTO, "No");
                            unset($GLOBALS["NotificaErrorPorEmail"]);

                        else:

                            //SI NO, LO OBTENGO DEL PEDIDO
                            //OBTENGO EN PRIMER LUGAR LA LINEA DEL PEDIDO
                            $GLOBALS["NotificaErrorPorEmail"] = "No";
                            $rowPedidoContratoLinea           = $bd->VerReg("PEDIDO_CONTRATO_LINEA", "ID_PEDIDO_CONTRATO_LINEA", $rowOrdenContratacion->ID_PEDIDO_CONTRATO_LINEA, "No");
                            unset($GLOBALS["NotificaErrorPorEmail"]);

                            //OBTENGO EL PEDIDO
                            $GLOBALS["NotificaErrorPorEmail"] = "No";
                            $rowPedidoContrato                = $bd->VerReg("PEDIDO_CONTRATO", "ID_PEDIDO_CONTRATO", $rowPedidoContratoLinea->ID_PEDIDO_CONTRATO, "No");
                            unset($GLOBALS["NotificaErrorPorEmail"]);

                            //OBTENEMOS EL PROYECTO
                            $GLOBALS["NotificaErrorPorEmail"] = "No";
                            $rowCentroFisico                  = $bd->VerReg("CENTRO_FISICO", "ID_CENTRO_FISICO", $rowPedidoContrato->ID_CENTRO_FISICO, "No");
                            unset($GLOBALS["NotificaErrorPorEmail"]);

                        endif;

                        if ($rowOrdenContratacion->TIPO_CAMBIO_MANUAL == 0):

                            //SI NO SE HA MODIFICADO MANUALMENTE EL TIPO DE CAMBIO, OBTENEMOS EL TIPO DE CAMBIO
                            $tipoCambio = 0;

                            //COMPROBAMOS SI EL PROYECTO TIENE LA MCP ASIGNADA
                            if ($rowCentroFisico->ID_MONEDA_CONTROL != NULL):
                                if ($rowOrdenContratacion->ID_MONEDA == $rowCentroFisico->ID_MONEDA_CONTROL):

                                    //SI LA MONEDA DE ORIGEN Y DESTINO SON IGUALES, LO PONGO A 1
                                    $tipoCambio = 1;

                                else:
                                    //REALIZAMOS LA LLAMADA A SAP PARA OBTENER EL IMPORTE CONVERTIDO
                                    $resultado = $sap->ConvertirImporteMonedaOrigenMonedaDestinoConstruccion($rowOrdenContratacion->IMPORTE_MODIFICADO, $rowOrdenContratacion->ID_MONEDA, $rowCentroFisico->ID_MONEDA_CONTROL, ($rowOrdenContratacion->FECHA_CONTABILIZACION != '0000-00-00' ? $rowOrdenContratacion->FECHA_CONTABILIZACION : $rowOrdenContratacion->FECHA_EJECUCION));

                                    if ($resultado['RESULTADO'] == 'OK'):

                                        //OBTENGO EL TIPO DE CAMBIO (IMPORTE CALCULADO ENTRE EL ORIGINAL)
                                        $tipoCambio = $resultado['IMPORTE_DEVUELTO'] / $rowOrdenContratacion->IMPORTE_MODIFICADO;

                                    endif;
                                endif;
                            else:
                                //SI NO LA TIENE MUESTRO UN MENSAJE DE ERROR
                                $html->PagError($auxiliar->traduce("El proyecto seleccionado no tiene asignada la Moneda Control de Proyecto", $administrador->ID_IDIOMA));
                            endif;

                            //ACTUALIZAMOS LA CONTRATACION
                            $sqlUpdate = "UPDATE ORDEN_CONTRATACION SET
						  TIPO_CAMBIO = $tipoCambio
						  , ID_MONEDA_CONTROL_PROYECTO = " . ($rowCentroFisico->ID_MONEDA_CONTROL != NULL ? $rowCentroFisico->ID_MONEDA_CONTROL : "NULL") . "
						  WHERE ID_ORDEN_CONTRATACION = $rowOrdenContratacion->ID_ORDEN_CONTRATACION";
                            $bd->ExecSQL($sqlUpdate);

                        endif;
                    endif;

                    //BUSCO EL NUMERO DE REFERENCIA DE FACTURACION CORRESPONDIENTE
//                    $arr_facturacion      = $this->ObtenerReferenciaFacturacionConstruccion($rowOrdenContratacion->ID_ORDEN_CONTRATACION);
//                    $numReferenciaFacturacion = $arr_facturacion['REFERENCIA_FACTURACION'];
//                    $fecha_contabilizacion    = $arr_facturacion['FECHA_CONTABILIZACION'];
//
//                    //GUARDO LA REFERENCIA DE FACTURACION
//                    $sqlUpdate = "UPDATE ORDEN_CONTRATACION SET
//                                                   REFERENCIA_FACTURACION = '" . $numReferenciaFacturacion . "'
//                                                   , FECHA_CONTABILIZACION = '" . $fecha_contabilizacion . "'
//                                                   WHERE ID_ORDEN_CONTRATACION = $rowOrdenContratacion->ID_ORDEN_CONTRATACION";
//                    $bd->ExecSQL($sqlUpdate);

                    if ($regenerar_informe_certificacion): //SI SE PUEDE REGENERAR EL INFORME DE CERTIFICACION OBTENEMOS LA REFERENCIA DE FACTURACION Y REGENERAMOS EL INFORME DE CERTIFICACION
                        //REGENERAMOS INFORME DE CERTIFICACION SI ES NECESARIO
                        $this->RegenerarInformeCertificacion($rowOrdenContratacion->ID_ORDEN_CONTRATACION);
                    endif;

                    //OBTENEMOS LA ROW ACTUALIZADA
                    $rowOrdenContratacionActualizada = $bd->VerReg("ORDEN_CONTRATACION", "ID_ORDEN_CONTRATACION", $rowOrdenContratacion->ID_ORDEN_CONTRATACION);

                    //LOG MOVIMIENTOS
                    $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Orden de Contratacion", $rowOrdenContratacion->ID_ORDEN_CONTRATACION, "Actualizar Datos Facturacion", "ORDEN_CONTRATACION", $rowOrdenContratacion, $rowOrdenContratacionActualizada);
                endif;
            endwhile;
        endif;

        return false;
    }

    /**
     * MIGRADO A CODEIGNITER get_contrato_construccion(SI SE ACTUALIZA, ACTUALIZAR AMBAS)
     * @param $txImporte
     * @param $idRetencion
     * NOS DEVUELVE UN ARRAY DE CONTRATACIONES CUYA SUMA SE APROXIMA MÁS SUPERIORMENTE A UN IMPORTE INTRODUCIDO POR EL USUARIO
     */
    function getCombinacionContrataciones($txImporte, $idRetencion)
    {
        global $html;
        global $bd;
        global $administrador;
        global $auxiliar;

        $arrContratacionesSuma     = array();
        $arrContratacionesAuxiliar = array();

        //PRIMERO OBTENEMOS LAS CONTRATACIONES RETENIDAS QUE TENGAN EL MISMO O SUPERIOR IMPORTE QUE EL INDICADO POR EL USUARIO
        $sqlContratacionesLiberar    = "SELECT PCL.ID_PEDIDO_CONTRATO_LINEA, PCL.ID_PEDIDO_CONTRATO, OC.ID_ORDEN_CONTRATACION, OC.IMPORTE_MODIFICADO, OC.ID_MONEDA, OC.FECHA_EJECUCION
                                        FROM PEDIDO_CONTRATO_LINEA PCL
                                            INNER JOIN ORDEN_CONTRATACION OC ON OC.ID_PEDIDO_CONTRATO_LINEA = PCL.ID_PEDIDO_CONTRATO_LINEA
                                        WHERE OC.ID_RETENCION = $idRetencion AND OC.CON_RETENCION = 1 AND OC.IMPORTE_MODIFICADO >= $txImporte AND OC.BAJA = 0 AND PCL.BAJA = 0
                                        ORDER BY OC.IMPORTE_MODIFICADO, OC.FECHA_EJECUCION, OC.ID_ORDEN_CONTRATACION";
        $resultContratacionesLiberar = $bd->ExecSQL($sqlContratacionesLiberar);

        if ($bd->NumRegs($resultContratacionesLiberar) > 0):

            //SI TIENE ALGUNA CONTRATACION CON IMPORTE MAYOR O IGUAL QUE EL INDICADO, OBTENGO LA PRIMERA CONTRATACIÓN
            $rowContratacion = $bd->SigReg($resultContratacionesLiberar);

            if ($rowContratacion->IMPORTE_MODIFICADO == $txImporte):

                //SI COINCIDEN LOS IMPORTES, DEVUELVO LA CONTRATACIÓN
                $arrContratacionesSuma[] = $rowContratacion->ID_ORDEN_CONTRATACION;

                return $arrContratacionesSuma;

            else:

                $arrContratacionesSuma[] = $rowContratacion->ID_ORDEN_CONTRATACION;

                //SI NO COINCIDEN, AÑADO LA COMPONENTE AL ARRAY, Y BUSCO LAS CONTRATACIONES CON IMPORTE INFERIOR AL INDICADO POR EL USUARIO
                //AHORA OBTENEMOS LAS CONTRATACIONES CON IMPORTE INFERIOR AL INDICADO
                $sqlContratacionesLiberar    = "SELECT OC.IMPORTE_MODIFICADO, OC.ID_ORDEN_CONTRATACION
                                            FROM PEDIDO_CONTRATO_LINEA PCL
                                                INNER JOIN ORDEN_CONTRATACION OC ON OC.ID_PEDIDO_CONTRATO_LINEA = PCL.ID_PEDIDO_CONTRATO_LINEA
                                            WHERE OC.ID_RETENCION = $idRetencion AND OC.CON_RETENCION = 1 AND OC.IMPORTE_MODIFICADO < $txImporte AND OC.BAJA = 0 AND PCL.BAJA = 0
                                            ORDER BY OC.IMPORTE_MODIFICADO, OC.FECHA_EJECUCION, OC.ID_ORDEN_CONTRATACION";
                $resultContratacionesLiberar = $bd->ExecSQL($sqlContratacionesLiberar);

                if ($bd->NumRegs($resultContratacionesLiberar) > 0):
                    //OBTENEMOS LA CONTRATACIÓN CON IMPORTE MINIMO
                    while ($rowContratacionesLiberar = $bd->SigReg($resultContratacionesLiberar)):
                        //GUARDAMOS EN UN ARRAY LAS ORDENES Y SUS IMPORTES
                        $arrContratacionesIdOrdenAuxiliar[] = $rowContratacionesLiberar->ID_ORDEN_CONTRATACION;
                        $arrContratacionesImporteAuxiliar[] = $rowContratacionesLiberar->IMPORTE_MODIFICADO;
                    endwhile;

                    //OBTENEMOS LAS CLAVES DE LOS ARRAYS
                    $arrClavesAuxiliar = array_keys((array)$arrContratacionesImporteAuxiliar);
                    //GUARDAMOS EL IMPORTE MAS CERCANO ACTUAL
                    $cercana      = $rowContratacion->IMPORTE_MODIFICADO;
                    $ordenCercana = $rowContratacion->ID_ORDEN_CONTRATACION;
                    //OTENEMOS TODAS LAS COMBINACIONES DE CLAVES PARA LUEGO HACER LAS SUMAS
                    $elementos = count((array)$arrClavesAuxiliar);
                    $decimal   = 1;
                    $binario   = str_split(str_pad((string)decbin($decimal), $elementos, '0', STR_PAD_LEFT));
                    while ($decimal < pow(2, $elementos)) {
                        $actual = "";
                        $i      = 0;
                        while ($i < ($elementos)) {
                            if ($binario[$i] == 1) {
                                $actual .= $arrClavesAuxiliar[$i] . " ";
                            }
                            $i++;
                        }
                        $terms[] = $actual;
                        $decimal++;
                        $binario = str_split(str_pad((string)decbin($decimal), $elementos, '0', STR_PAD_LEFT));
                    }
                    //PARA CADA COMBINACION QUE HEMOS OBTENIDO HACEMOS LA SUMA DE LOS IMPORTES Y LO COMPARAMOS CON EL IMPORTE MAS CERCANO
                    if (isset($terms)):
                        foreach ($terms as $res) :
                            $arrayCombinacion = explode(" ", (string)$res);
                            $arrSumar         = [];
                            //OBTENEMOS LOS IMPORTES DEL ARRAY DE IMPORTES Y LOS SUMAMOS
                            foreach ($arrayCombinacion as $pos):
                                $arrSumar[] = $arrContratacionesImporteAuxiliar[$pos];
                            endforeach;
                            $suma = array_sum($arrSumar);
                            //SI LA SUMA DE IMPORTES COINCIDE CON EL INDICADO DEVOLVEMOS LAS ORDENES DE ESA COMBINACION
                            if ($suma == $txImporte) :
                                $arrContratacionesSuma = [];
                                foreach ($arrayCombinacion as $pos):
                                    //SACO LAS ORDENES QUE PERTENECEN A ESA COMBINACION
                                    $arrContratacionesSuma[] = $arrContratacionesIdOrdenAuxiliar[$pos];
                                endforeach;

                                return array_filter($arrContratacionesSuma);
                            endif;
                            //SI LA SUMA DE IMPORTES ES MAS CERCANA A LA INDICADA QUE LA QUE TENEMOS GUARDADA LA SUSTITUIMOS
                            if ($suma > $txImporte && $suma < $cercana) :
                                $cercana      = $suma;
                                $arrayCercana = $arrayCombinacion;
                            endif;
                        endforeach;
                    endif;
                    //SI SE HA INICIALIZADO ESTA VARIABLE LA RESPUESTA ES UNA COMBINACION

                    //EN CASO CONTRARIO ES UNA SOLA ORDEN
                    if (isset($arrayCercana)):
                        $arrContratacionesSuma = [];
                        //SACO LAS ORDENES QUE PERTENECEN A ESA COMBINACION
                        foreach ($arrayCercana as $pos):
                            $arrContratacionesSuma[] = $arrContratacionesIdOrdenAuxiliar[$pos];
                        endforeach;

                        return array_filter($arrContratacionesSuma);
                    else:
                        return $arrContratacionesSuma;
                    endif;
                else:
                    return $arrContratacionesSuma;
                endif;

            endif;

        else:

            //AHORA OBTENEMOS LAS CONTRATACIONES CON IMPORTE INFERIOR AL INDICADO
            $sqlContratacionesLiberar    = "SELECT OC.IMPORTE_MODIFICADO, OC.ID_ORDEN_CONTRATACION
                                            FROM PEDIDO_CONTRATO_LINEA PCL
                                                INNER JOIN ORDEN_CONTRATACION OC ON OC.ID_PEDIDO_CONTRATO_LINEA = PCL.ID_PEDIDO_CONTRATO_LINEA
                                            WHERE OC.ID_RETENCION = $idRetencion AND OC.CON_RETENCION = 1 AND OC.IMPORTE_MODIFICADO < $txImporte AND OC.BAJA = 0 AND PCL.BAJA = 0
                                            ORDER BY OC.IMPORTE_MODIFICADO, OC.FECHA_EJECUCION, OC.ID_ORDEN_CONTRATACION";
            $resultContratacionesLiberar = $bd->ExecSQL($sqlContratacionesLiberar);

            if ($bd->NumRegs($resultContratacionesLiberar) > 0):

                //OBTENEMOS LA CONTRATACIÓN CON IMPORTE MINIMO
                while ($rowContratacionesLiberar = $bd->SigReg($resultContratacionesLiberar)):
                    //GUARDAMOS EN UN ARRAY LAS ORDENES Y SUS IMPORTES
                    $arrContratacionesIdOrdenAuxiliar[] = $rowContratacionesLiberar->ID_ORDEN_CONTRATACION;
                    $arrContratacionesImporteAuxiliar[] = $rowContratacionesLiberar->IMPORTE_MODIFICADO;
                endwhile;

                //OBTENEMOS LAS CLAVES DE LOS ARRAYS
                $arrClavesAuxiliar = array_keys((array)$arrContratacionesImporteAuxiliar);
                //GUARDAMOS EL IMPORTE MAS CERCANO ACTUAL
                $cercana = PHP_INT_MAX;
                //OTENEMOS TODAS LAS COMBINACIONES DE CLAVES PARA LUEGO HACER LAS SUMAS
                $elementos = count((array)$arrClavesAuxiliar);
                $decimal   = 1;
                $binario   = str_split(str_pad((string)decbin($decimal), $elementos, '0', STR_PAD_LEFT));
                while ($decimal < pow(2, $elementos)) {
                    $actual = "";
                    $i      = 0;
                    while ($i < ($elementos)) {
                        if ($binario[$i] == 1) {
                            $actual .= $arrClavesAuxiliar[$i] . " ";
                        }
                        $i++;
                    }
                    $terms[] = $actual;
                    $decimal++;
                    $binario = str_split(str_pad((string)decbin($decimal), $elementos, '0', STR_PAD_LEFT));
                }

                //PARA CADA COMBINACION QUE HEMOS OBTENIDO HACEMOS LA SUMA DE LOS IMPORTES Y LO COMPARAMOS CON EL IMPORTE MAS CERCANO
                if (isset($terms)):
                    foreach ($terms as $res) :
                        $arrayCombinacion = explode(" ", (string)$res);
                        $arrSumar         = [];
                        //OBTENEMOS LOS IMPORTES DEL ARRAY DE IMPORTES Y LOS SUMAMOS
                        foreach ($arrayCombinacion as $pos):
                            $arrSumar[] = $arrContratacionesImporteAuxiliar[$pos];
                        endforeach;
                        $suma = array_sum($arrSumar);
                        //SI LA SUMA DE IMPORTES COINCIDE CON EL INDICADO DEVOLVEMOS LAS ORDENES DE ESA COMBINACION
                        if ($suma == $txImporte) :
                            $arrContratacionesSuma = [];
                            foreach ($arrayCombinacion as $pos):
                                //SACO LAS ORDENES QUE PERTENECEN A ESA COMBINACION
                                $arrContratacionesSuma[] = $arrContratacionesIdOrdenAuxiliar[$pos];
                            endforeach;

                            return array_filter($arrContratacionesSuma);
                        endif;
                        //SI LA SUMA DE IMPORTES ES MAS CERCANA A LA INDICADA QUE LA QUE TENEMOS GUARDADA LA SUSTITUIMOS
                        if ($suma > $txImporte && $suma < $cercana) :
                            $cercana      = $suma;
                            $arrayCercana = $arrayCombinacion;
                        endif;
                    endforeach;
                endif;
                //SI SE HA INICIALIZADO ESTA VARIABLE LA RESPUESTA ES UNA COMBINACION

                if (isset($arrayCercana)):
                    $arrContratacionesSuma = [];
                    //SACO LAS ORDENES QUE PERTENECEN A ESA COMBINACION
                    foreach ($arrayCercana as $pos):
                        $arrContratacionesSuma[] = $arrContratacionesIdOrdenAuxiliar[$pos];
                    endforeach;

                    return array_filter($arrContratacionesSuma);
                else:
                    return $arrContratacionesSuma;
                endif;
            endif;
        endif;

        return $arrContratacionesSuma;

    }


    /**
     * MIGRADO A CODEIGNITER get_contrato_construccion(SI SE ACTUALIZA, ACTUALIZAR AMBAS)
     * @param $idModulo
     * @param $idServicio
     * @param $idProveedor
     * @param $rowOrdenTransporte
     * @param array $arr_extra
     * NOS DEVUELVE EL CONTRATO PARA EL SERVICIO, MODULO, PROVEEDOR Y PROYECTO INDICADOS
     */
    function getContratoConstruccion($idModulo, $idServicio, $idProveedor, $idCentroFisico, $rowOrdenTransporte, $arr_extra = array())
    {
        global $html;
        global $bd;
        global $administrador;
        global $auxiliar;

        // ROW A DEVOLVER
        $rowGastosContrato = false;

        if ($idModulo == '' || $idServicio == '' || $idProveedor == ''):
            return $rowGastosContrato;
        endif;

        //BUSCAMOS EL SERVICIO
        $rowServicio = $bd->VerReg("SERVICIO", "ID_SERVICIO", $idServicio);

        //SI EL SERVICIO TIENE ASIGNADO PROVEEDOR, OBTENEMOS LOS GASTOS DEL CONTRATO
        $sqlGastosContrato = "SELECT CPP.ID_CONTRATO_PROVEEDOR_PROYECTO, CPP.CONTRATO_CONSTRUCCION, CPPL.ID_SERVICIO, CPPL.ID_MONEDA, CPPL.IMPORTE_UNIDAD, CPPL.ID_UNIDAD, CPPL.ID_CONTRATO_PROVEEDOR_PROYECTO_LINEA, CPPL.ID_HITO_CONTRATACION, CPPL.ID_PEDIDO_CONTRATO_LINEA, CPPL.DIAS_PLANIFICADO_TRANSITO, CPPL.DIAS_LIBRES_PUERTO, CPPL.DIAS_DEMURRAGE
                                FROM CONTRATO_PROVEEDOR_PROYECTO CPP
                                INNER JOIN CONTRATO_PROVEEDOR_PROYECTO_LINEA CPPL ON CPPL.ID_CONTRATO_PROVEEDOR_PROYECTO = CPP.ID_CONTRATO_PROVEEDOR_PROYECTO
                                WHERE CPPL.ID_MODULO = " . $idModulo . " AND CPPL.ID_SERVICIO = " . $idServicio . " AND CPP.ID_PROVEEDOR = " . $idProveedor . " AND CPP.ID_CENTRO_FISICO = " . ($rowOrdenTransporte->ID_CENTRO_FISICO_PROYECTO != NULL ? $rowOrdenTransporte->ID_CENTRO_FISICO_PROYECTO : $idCentroFisico);

        //OBTENEMOS LAS LÍNEAS DE PEDIDO SEGÚN EL SERVICIO Y SU "CAMPO CLAVE"
        switch ($rowServicio->TIPO_CONSTRUCCION):
            case 'PuertoOrigen':
                if ($rowOrdenTransporte->ID_PUERTO_ORIGEN == NULL):
                    $sqlGastosContrato .= " AND FALSE ";
                else:
                    $sqlGastosContrato .= " AND CPPL.ID_PUERTO = " . $rowOrdenTransporte->ID_PUERTO_ORIGEN;
                endif;

                break;

            case 'FleteContenedor':
                if ($rowOrdenTransporte->ID_PUERTO_ORIGEN == NULL || $rowOrdenTransporte->ID_PUERTO_DESTINO == NULL || $rowOrdenTransporte->ID_CONTENEDOR_EXPORTACION == NULL):
                    $sqlGastosContrato .= " AND FALSE ";
                else:
                    $sqlGastosContrato .= " AND CPPL.ID_PUERTO = " . $rowOrdenTransporte->ID_PUERTO_ORIGEN . " AND CPPL.ID_PUERTO_FINAL_TRANSITO = " . $rowOrdenTransporte->ID_PUERTO_DESTINO . " AND CPPL.ID_CONTENEDOR_EXPORTACION = " . $rowOrdenTransporte->ID_CONTENEDOR_EXPORTACION;
                endif;
                break;

            case 'FleteGC':
                if ($rowOrdenTransporte->ID_PUERTO_ORIGEN == NULL || $rowOrdenTransporte->ID_PUERTO_DESTINO == NULL || $rowOrdenTransporte->ID_TIPO_MATERIAL == NULL):
                    $sqlGastosContrato .= " AND FALSE ";
                else:
                    $sqlGastosContrato .= " AND CPPL.ID_PUERTO = " . $rowOrdenTransporte->ID_PUERTO_ORIGEN . " AND CPPL.ID_PUERTO_FINAL_TRANSITO = " . $rowOrdenTransporte->ID_PUERTO_DESTINO . " AND CPPL.ID_TIPO_MATERIAL = " . $rowOrdenTransporte->ID_TIPO_MATERIAL;
                endif;

                break;
            case 'Flete':
                if ($rowOrdenTransporte->ID_TIPO_MATERIAL == NULL):
                    $sqlGastosContrato .= " AND FALSE ";
                else:
                    $sqlGastosContrato .= " AND CPPL.ID_TIPO_MATERIAL = " . $rowOrdenTransporte->ID_TIPO_MATERIAL;
                endif;
                break;
            case 'Almacenaje':
                if ($rowOrdenTransporte->ID_PUERTO_DESTINO == NULL || $rowOrdenTransporte->ID_CONTENEDOR_EXPORTACION == NULL):
                    $sqlGastosContrato .= " AND FALSE ";
                else:
                    $sqlGastosContrato .= " AND CPPL.ID_PUERTO_FINAL_TRANSITO = " . $rowOrdenTransporte->ID_PUERTO_DESTINO . " AND CPPL.ID_CONTENEDOR_EXPORTACION = " . $rowOrdenTransporte->ID_CONTENEDOR_EXPORTACION;
                endif;

                break;
            case 'PuertoDestino':
                if ($rowOrdenTransporte->ID_PUERTO_DESTINO == NULL):
                    $sqlGastosContrato .= " AND FALSE ";
                else:
                    $sqlGastosContrato .= " AND CPPL.ID_PUERTO_FINAL_TRANSITO = " . $rowOrdenTransporte->ID_PUERTO_DESTINO;
                endif;
                break;

            case 'ImportacionGC':
                if ($rowOrdenTransporte->ID_TIPO_MATERIAL == NULL || $rowOrdenTransporte->ID_PUERTO_DESTINO == NULL):
                    $sqlGastosContrato .= " AND FALSE ";
                else:
                    $sqlGastosContrato .= " AND CPPL.ID_TIPO_MATERIAL = " . $rowOrdenTransporte->ID_TIPO_MATERIAL . " AND CPPL.ID_PUERTO_FINAL_TRANSITO = " . $rowOrdenTransporte->ID_PUERTO_DESTINO;
                endif;
                break;

            case 'ImportacionContenedor':
                if ($rowOrdenTransporte->ID_PUERTO_DESTINO == NULL || $rowOrdenTransporte->ID_CONTENEDOR_EXPORTACION):
                    $sqlGastosContrato .= " AND FALSE ";
                else:
                    $sqlGastosContrato .= " AND CPPL.ID_PUERTO_FINAL_TRANSITO = " . $rowOrdenTransporte->ID_PUERTO_DESTINO . " AND CPPL.ID_CONTENEDOR_EXPORTACION = " . $rowOrdenTransporte->ID_CONTENEDOR_EXPORTACION;
                endif;
                break;

            case 'Extracoste':
                if ($rowOrdenTransporte->ID_CONTENEDOR_EXPORTACION == NULL):
                    $sqlGastosContrato .= " AND FALSE ";
                else:
                    $sqlGastosContrato .= " AND CPPL.ID_CONTENEDOR_EXPORTACION = " . $rowOrdenTransporte->ID_CONTENEDOR_EXPORTACION;
                endif;
                break;

            case 'ExtracosteGC':
                if ($rowOrdenTransporte->ID_TIPO_MATERIAL == NULL):
                    $sqlGastosContrato .= " AND FALSE ";
                else:
                    $sqlGastosContrato .= " AND CPPL.ID_TIPO_MATERIAL = " . $rowOrdenTransporte->ID_TIPO_MATERIAL;
                endif;
                break;

            default:

                break;

        endswitch;

        $sqlGastosContrato .= " AND CPP.BAJA = 0 AND CPPL.BAJA = 0 
                               GROUP BY CPP.ID_CONTRATO_PROVEEDOR_PROYECTO";

        $resultGastosContrato = $bd->ExecSQL($sqlGastosContrato);

        $rowGastosContrato = $bd->SigReg($resultGastosContrato);

        return $rowGastosContrato;

    }

    /**
     * MIGRADO A CODEIGNITER pedidos_finalizados_ot(SI SE ACTUALIZA, ACTUALIZAR AMBAS)
     * @param $idOrdenTransporte
     * NOS DEVUELVE UN BOOLEANO QUE INDICA SI TODOS LOS PEDIDOS ASOCIADOS A LA OT (DE SUS SERVICIOS) ESTÁN FINALIZADOS
     */
    function pedidosFinalizadosOT($idOrdenTransporte)
    {
        global $bd;

        //OBTENEMOS TODOS LOS PEDIDOS ASOCIADOS A LA OT
        $sqlPedidosOT    = "SELECT DISTINCT PC.ID_PEDIDO_CONTRATO, PC.PEDIDO_FINALIZADO
                            FROM ORDEN_TRANSPORTE_SERVICIO_PROVEEDOR OTSP
                                INNER JOIN PEDIDO_CONTRATO_LINEA PCL ON PCL.ID_PEDIDO_CONTRATO_LINEA = OTSP.ID_PEDIDO_CONTRATO_LINEA
                                INNER JOIN PEDIDO_CONTRATO PC ON PC.ID_PEDIDO_CONTRATO = PCL.ID_PEDIDO_CONTRATO
                            WHERE OTSP.ID_ORDEN_TRANSPORTE = $idOrdenTransporte AND PC.BAJA = 0 AND PCL.BAJA = 0 AND OTSP.BAJA = 0";
        $resultPedidosOT = $bd->ExecSQL($sqlPedidosOT);

        if ($bd->NumRegs($resultPedidosOT) > 0):

            //SI LA OT CONTIENE SERVICIOS, COMPROBAMOS SI TIENE ALGUNO ABIERTO
            while ($rowPedidosOT = $bd->SigReg($resultPedidosOT)):

                //RECORREMOS LOS PEDIDOS DE LA OT PARA VER SI TIENE ALGUNO QUE NO ESTÉ FINALIZADO
                if ($rowPedidosOT->PEDIDO_FINALIZADO == 0):

                    //SI TIENE UN PEDIDO NO FINALIZADO, ENTONCES DEVOLVEMOS FALSE
                    return false;

                endif;

            endwhile;
        else:

            //SI LA OT NO TIENE PEDIDOS ASOCIADOS, ENTONCES DEVOLVEMOS FALSE
            return false;

        endif;

        return true;

    }

    /**
     * MIGRADO A CODEIGNITER (SI SE ACTUALIZA, ACTUALIZAR AMBAS get_acciones_orden_transporte)
     * @param $idOrdenTransporte
     * DEVUELVE LA QUERY
     */
    function getQueryAccionesTransporte($idOrdenTransporte = "", $sqlWhereAccion = "")
    {
        global $html;
        global $bd;
        global $administrador;
        global $auxiliar;

        //MONTAMOS LA QUERY
        $sqlQuery = "SELECT * FROM ORDEN_TRANSPORTE_ACCION WHERE 1=1 ";

        //AÑADIMOS EL WHERE ESPECIFICO
        if ($sqlWhereAccion != ""):
            $sqlQuery .= " AND " . $sqlWhereAccion;
        endif;

        //SI VIENE ORDEN TRANSPORTE
        if ($idOrdenTransporte != ""):
            //BUSCAMOS EL TRANSPORTE
            $rowOrdenTransporte = $this->getOrdenTransporteConstruccion($idOrdenTransporte);

            //AÑADIMOS EL TIPO TRANSPORTE PARA BUSCAR SOLO ACCIONES DE ESE TIPO TRANSPORTE
            $sqlQuery .= " AND (TIPO_TRANSPORTE IS NULL OR FIND_IN_SET('" . $rowOrdenTransporte->TIPO_TRANSPORTE . "', TIPO_TRANSPORTE) ) ";

            //AÑADIMOS CON/SIN ADUANAS
            $sqlQuery .= " AND (TRANSPORTE_CON_ADUANAS IS NULL OR TRANSPORTE_CON_ADUANAS = '" . ($rowOrdenTransporte->TRANSPORTE_CON_ADUANAS == 1 ? "Si" : "No") . "') ";

            //AÑADIMOS CON/SIN EMBARQUE
            $sqlQuery .= " AND (TRANSPORTE_GRAN_COMPONENTE IS NULL OR TRANSPORTE_GRAN_COMPONENTE = '" . ($rowOrdenTransporte->CON_EMBARQUE_GC == 1 ? "Si" : "No") . "') ";
        endif;

        return $sqlQuery;

    }


    /**
     * @param $idSolicitudTransporteDestino
     * ASOCIA A UN DESTINO dE SOLICITUD LOS CONTACTOS POR DEFECTO DE SU DIRECCION
     */
    function asignarContactoDefectoDestinoSolicitud($idSolicitudTransporteDestino)
    {
        global $bd;
        global $auxiliar;
        global $administrador;
        global $NotificaErrorPorEmail;

        //BUSCAMOS EL AVISO ACCION
        $rowDestinoSolicitud = $bd->VerReg("SOLICITUD_TRANSPORTE_DESTINO", "ID_SOLICITUD_TRANSPORTE_DESTINO", $idSolicitudTransporteDestino);

        //SI LA DIRECCION TIENE PERSONASS DE CONTACTO PRINCIPAL, LA ASIGNAMOS
        $NotificaErrorPorEmail          = "No";
        $sqlDireccionPersonaContacto    = "SELECT * FROM DIRECCION_PERSONA_CONTACTO WHERE ID_DIRECCION = $rowDestinoSolicitud->ID_DIRECCION AND BAJA = 0 AND PRINCIPAL = 1";
        $resultDireccionPersonaContacto = $bd->ExecSQL($sqlDireccionPersonaContacto, "No");

        if ($bd->NumRegs($resultDireccionPersonaContacto) > 0):
            while ($rowDireccionPersonaContacto = $bd->SigReg($resultDireccionPersonaContacto)):
                //INSERTAMOS LA PERSONA CONTACTO
                $sqlInsert = "INSERT INTO SOLICITUD_TRANSPORTE_DESTINO_PERSONA_CONTACTO SET
                                    ID_DIRECCION_PERSONA_CONTACTO = $rowDireccionPersonaContacto->ID_DIRECCION_PERSONA_CONTACTO
                                    ,ID_SOLICITUD_TRANSPORTE_DESTINO = $rowDestinoSolicitud->ID_SOLICITUD_TRANSPORTE_DESTINO";
                $bd->ExecSQL($sqlInsert);

                //INSERTAMOS EN LOG
                $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Solicitud Transporte", $rowDestinoSolicitud->ID_SOLICITUD_TRANSPORTE, "Asignar persona contacto $rowDireccionPersonaContacto->ID_DIRECCION_PERSONA_CONTACTO");
            endwhile;
        endif;

    }

    /**
     * @param $rowNecesidad
     * FUNCION QUE MANDA UN CORREO A:
     * UN CORREO AL ROL QUE CORRESPONDA.
     */
    function EnviarNotificacionEmail_AvisoReclamada($idOrdenTransporteAccionAviso)
    {
        global $bd;
        global $auxiliar;
        global $administrador;
        global $NotificaErrorPorEmail;
        global $aviso;

        //OBTENGO LA NECESIDAD
        $sqlOrdenTransporteAccionAviso    = "SELECT  OTAA.ID_ORDEN_TRANSPORTE_ACCION_AVISO, OTAA.ESTADO_ACCION, OTA.TIPO_DESTINATARIO, OTA.TIPO_ACCION, OTAA.FECHA_CREACION, OTAA.FECHA_RESOLUCION,
                      OT.ID_ORDEN_TRANSPORTE, OT.ESTADO, OTC.ID_PROVEEDOR, OTC.ID_PROVEEDOR_FORWARDER, OTC.ID_PROVEEDOR_AGENTE_ADUANAL, OT.ID_AGENCIA, OTAA.OBSERVACION
                        FROM ORDEN_TRANSPORTE_ACCION OTA
                        INNER JOIN ORDEN_TRANSPORTE_ACCION_AVISO OTAA ON OTAA.ID_ORDEN_TRANSPORTE_ACCION = OTA.ID_ORDEN_TRANSPORTE_ACCION
                        INNER JOIN ORDEN_TRANSPORTE OT ON OT.ID_ORDEN_TRANSPORTE = OTAA.ID_ORDEN_TRANSPORTE
                        INNER JOIN ORDEN_TRANSPORTE_CONSTRUCCION OTC ON OT.ID_ORDEN_TRANSPORTE = OTC.ID_ORDEN_TRANSPORTE
                        INNER JOIN ORDEN_TRANSPORTE_CONSTRUCCION_AMPLIACION OTCA ON OT.ID_ORDEN_TRANSPORTE = OTCA.ID_ORDEN_TRANSPORTE
                        WHERE OTAA.ID_ORDEN_TRANSPORTE_ACCION_AVISO = $idOrdenTransporteAccionAviso";
        $resultOrdenTransporteAccionAviso = $bd->ExecSQL($sqlOrdenTransporteAccionAviso, "No");
        $rowOrdenTransporteAccionAviso    = $bd->SigReg($resultOrdenTransporteAccionAviso);

        //ARRAY PARA GUARDAR LOS DESTINATARIOS DEL CORREO
        $arrAdminDestino = array();
        //BUSCO EL ULTIMO TEXTO DE LA NECESIDAD DE TIPO RECLAMACION
        $sqlObservacionesSistema    = "SELECT *
                                    FROM OBSERVACION_SISTEMA OS
                                    WHERE OS.TIPO_OBJETO = 'ORDEN_TRANSPORTE_ACCION_AVISO' AND OS.ID_OBJETO = $rowOrdenTransporteAccionAviso->ID_ORDEN_TRANSPORTE_ACCION_AVISO AND OS.SUBTIPO_OBSERVACION = 'Reclamacion'
                                    ORDER BY OS.ID_OBSERVACION_SISTEMA DESC";
        $resultObservacionesSistema = $bd->ExecSQL($sqlObservacionesSistema);
        $rowObservacionSistema      = $bd->SigReg($resultObservacionesSistema);

        //BUSCO LOS RESPONSABLES DEL ALMACEN DE DESTINO
        if ($rowOrdenTransporteAccionAviso->TIPO_DESTINATARIO == "Proveedor del Material"):
            if ($rowOrdenTransporteAccionAviso->ID_PROVEEDOR != NULL):
                $rowProveedor = $bd->VerReg("PROVEEDOR", "ID_PROVEEDOR", $rowOrdenTransporteAccionAviso->ID_PROVEEDOR);
            endif;

        elseif ($rowOrdenTransporteAccionAviso->TIPO_DESTINATARIO == "Forwarder"):
            if ($rowOrdenTransporteAccionAviso->ID_PROVEEDOR_FORWARDER != NULL):
                $rowProveedor = $bd->VerReg("PROVEEDOR", "ID_PROVEEDOR", $rowOrdenTransporteAccionAviso->ID_PROVEEDOR_FORWARDER);
            endif;
        elseif ($rowOrdenTransporteAccionAviso->TIPO_DESTINATARIO == "Agente Aduanal"):
            if ($rowOrdenTransporteAccionAviso->ID_PROVEEDOR_AGENTE_ADUANAL != NULL):
                $rowProveedor = $bd->VerReg("PROVEEDOR", "ID_PROVEEDOR", $rowOrdenTransporteAccionAviso->ID_PROVEEDOR_AGENTE_ADUANAL);
            endif;
        elseif ($rowOrdenTransporteAccionAviso->TIPO_DESTINATARIO == "Tesoreria"):
            //PROVEEDOR TESORERIA ACCIONA

        elseif ($rowOrdenTransporteAccionAviso->TIPO_DESTINATARIO == "Transportista Inland"):
            if ($rowOrdenTransporteAccionAviso->ID_AGENCIA != NULL):
                $rowProveedor = $bd->VerReg("PROVEEDOR", "ID_PROVEEDOR", $rowOrdenTransporteAccionAviso->ID_AGENCIA);
            endif;
        endif;

        if ($rowProveedor != false):
            //AÑADIMOS ADMINISTRADORES CON ID PROVEEDOR SELECCIONADO
            $sqlAdministradoresCorreo    = "SELECT * FROM ADMINISTRADOR WHERE ID_PROVEEDOR = $rowProveedor->ID_PROVEEDOR ";
            $resultAdministradoresCorreo = $bd->ExecSQL($sqlAdministradoresCorreo, "No");
            if ($bd->NumRegs($resultAdministradoresCorreo) > 0):
                while ($rowAdministradoresCorreo = $bd->SigReg($resultAdministradoresCorreo)):
                    $arrAdminDestino[] = $rowAdministradoresCorreo->ID_ADMINISTRADOR;

                endwhile;
            endif;
        endif;

        //UNIFICO DESTINATARIOS
        $arrAdminDestino       = array_unique((array)$arrAdminDestino);
        $arrAdmin['PROVEEDOR'] = $arrAdminDestino;


        //PREPARO LOS DATOS A ENVIAR POR CORREO ESP
        $Asunto = $auxiliar->traduce("Accion", "ESP") . " " . $idOrdenTransporteAccionAviso . ". " . $auxiliar->traduce("Esta accion ha sido reclamada", "ESP");

        $Cuerpo = $auxiliar->traduce("Esta accion ha sido reclamada", "ESP") . '<br/>';
        $Cuerpo .= $auxiliar->traduce("Texto de la reclamacion", "ESP") . ": " . $bd->escapeCondicional($rowObservacionSistema->TEXTO_OBSERVACION) . '<br/><br/>';
        $Cuerpo .= "Estas involucrado en la reclamacion de la accion $idOrdenTransporteAccionAviso con Orden Transporte $rowOrdenTransporteAccionAviso->ID_ORDEN_TRANSPORTE";

        $idAviso = $aviso->GuardarAviso($idOrdenTransporteAccionAviso, "ORDEN_TRANSPORTE_ACCION_AVISO", $Asunto, $Cuerpo, $arrAdmin);

        //ENVIAMOS EL AVISO
        if ($idAviso != ""):
            $aviso->enviarAviso($idAviso);
        endif;

    }

    /**
     * DEVOLVER LOS CAMPOS A PINTAR SEGÚN EL SERVICIO (DE CONSTRUCCIÓN)
     * @param string $idServicio
     * @return resultCamposServicio
     */
    function getCamposPorServicio($idServicio)
    {
        global $bd;
        global $NotificaErrorPorEmail;

        $arrCamposServicio = array();

        //OBTENGO LOS CAMPOS DEL SERVICIO
        $sqlCamposServicio    = "SELECT SC.*, C.*
                                FROM SERVICIO S
                                    INNER JOIN SERVICIO_CONCEPTO SC ON SC.ID_SERVICIO = S.ID_SERVICIO 
                                    INNER JOIN CONCEPTO C ON C.ID_CONCEPTO = SC.ID_CONCEPTO 
                                WHERE SC.ID_SERVICIO = $idServicio AND SC.BAJA = 0 AND S.BAJA = 0 AND C.BAJA = 0";
        $resultCamposServicio = $bd->ExecSQL($sqlCamposServicio);

        while ($rowCamposServicio = $bd->SigReg($resultCamposServicio)):
            if ($rowCamposServicio->OBLIGATORIO == 1):
                $arrCamposObligatorios                                 = array();
                $arrCamposObligatorios['label']                        = $rowCamposServicio->LABEL;
                $arrCamposObligatorios['campoBBDD']['value']['nombre'] = $rowCamposServicio->NOMBRE_CAMPO_BBDD;
                $arrCamposObligatorios['campos']['tx']                 = $rowCamposServicio->TX_CAMPO;

                if ($rowCamposServicio->TIPO != NULL):
                    $arrCamposObligatorios['tipo'] = $rowCamposServicio->TIPO;
                endif;
                if ($rowCamposServicio->REFERENCIA != NULL):
                    $arrCamposObligatorios['campos']['referencia'] = $rowCamposServicio->REFERENCIA;
                endif;
                if ($rowCamposServicio->NOMBRE_ENG_CAMPO_BBDD != NULL):
                    $arrCamposObligatorios['campos']['nombre_eng'] = $rowCamposServicio->NOMBRE_ENG_CAMPO_BBDD;
                endif;
                if ($rowCamposServicio->ID_CAMPOS_BBDD != NULL):
                    $arrCamposObligatorios['campoBBDD']['id'] = $rowCamposServicio->ID_CAMPOS_BBDD;
                endif;
                if ($rowCamposServicio->ID_CAMPO != NULL):
                    $arrCamposObligatorios['campos']['id'] = $rowCamposServicio->ID_CAMPO;
                endif;
                if ($rowCamposServicio->NOMBRE_CAMPO != NULL):
                    $arrCamposObligatorios['campos']['nombre_campo'] = $rowCamposServicio->NOMBRE_CAMPO;
                endif;
                if ($rowCamposServicio->CONTROLLER != NULL):
                    $arrCamposObligatorios['campos']['controller'] = $rowCamposServicio->CONTROLLER;
                endif;
                if ($rowCamposServicio->DATOS_BUSCADOR != NULL):
                    //CREAMOS LA ESTRUCTURA DEL BUSCADOR
                    $arr_buscador    = array();
                    $campos_buscador = explode(", ", (string)$rowCamposServicio->DATOS_BUSCADOR);

                    foreach ($campos_buscador as $datos_buscador):
                        $cconcepto_buscador                   = explode(" => ", (string)$datos_buscador);
                        $arr_buscador[$cconcepto_buscador[0]] = $cconcepto_buscador[1];
                    endforeach;

                    $arrCamposObligatorios['campos']['datos_buscador'] = $arr_buscador;
                else:
                    $arrCamposObligatorios['campos']['datos_buscador'] = array();
                endif;
                if ($rowCamposServicio->DATOS_AJAX != NULL):
                    //CREAMOS LA ESTRUCTURA DEL AJAX
                    $arr_ajax    = array();
                    $campos_ajax = explode(", ", (string)$rowCamposServicio->DATOS_AJAX);

                    foreach ($campos_ajax as $datos_ajax):
                        $concepto_ajax               = explode(" => ", (string)$datos_ajax);
                        $arr_ajax[$concepto_ajax[0]] = $concepto_ajax[1];
                    endforeach;

                    $arrCamposObligatorios['campos']['datos_ajax'] = $campos_ajax;
                else:
                    $arrCamposObligatorios['campos']['datos_ajax'] = array();
                endif;

                $arrCamposServicio['OBLIGATORIOS'][$contadorObligatorios] = $arrCamposObligatorios;
                $contadorObligatorios++;
            else:
                $arrCamposOpcionales                                 = array();
                $arrCamposOpcionales['label']                        = $rowCamposServicio->LABEL;
                $arrCamposOpcionales['campoBBDD']['value']['nombre'] = $rowCamposServicio->NOMBRE_CAMPO_BBDD;
                $arrCamposOpcionales['campos']['tx']                 = $rowCamposServicio->TX_CAMPO;

                if ($rowCamposServicio->TIPO != NULL):
                    $arrCamposOpcionales['tipo'] = $rowCamposServicio->TIPO;
                endif;
                if ($rowCamposServicio->REFERENCIA != NULL):
                    $arrCamposOpcionales['campos']['referencia'] = $rowCamposServicio->REFERENCIA;
                endif;
                if ($rowCamposServicio->NOMBRE_ENG_CAMPO_BBDD != NULL):
                    $arrCamposOpcionales['campos']['nombre_eng'] = $rowCamposServicio->NOMBRE_ENG_CAMPO_BBDD;
                endif;
                if ($rowCamposServicio->ID_CAMPOS_BBDD != NULL):
                    $arrCamposOpcionales['campoBBDD']['id'] = $rowCamposServicio->ID_CAMPOS_BBDD;
                endif;
                if ($rowCamposServicio->ID_CAMPO != NULL):
                    $arrCamposOpcionales['campos']['id'] = $rowCamposServicio->ID_CAMPO;
                endif;
                if ($rowCamposServicio->NOMBRE_CAMPO != NULL):
                    $arrCamposOpcionales['campos']['nombre_campo'] = $rowCamposServicio->NOMBRE_CAMPO;
                endif;
                if ($rowCamposServicio->CONTROLLER != NULL):
                    $arrCamposOpcionales['campos']['controller'] = $rowCamposServicio->CONTROLLER;
                endif;
                if ($rowCamposServicio->DATOS_BUSCADOR != NULL):
                    //CREAMOS LA ESTRUCTURA DEL BUSCADOR
                    $arr_buscador    = array();
                    $campos_buscador = explode(", ", (string)$rowCamposServicio->DATOS_BUSCADOR);

                    foreach ($campos_buscador as $datos_buscador):
                        $cconcepto_buscador                   = explode(" => ", (string)$datos_buscador);
                        $arr_buscador[$cconcepto_buscador[0]] = $cconcepto_buscador[1];
                    endforeach;

                    $arrCamposOpcionales['campos']['datos_buscador'] = $arr_buscador;
                else:
                    $arrCamposOpcionales['campos']['datos_buscador'] = array();
                endif;
                if ($rowCamposServicio->DATOS_AJAX != NULL):
                    //CREAMOS LA ESTRUCTURA DEL AJAX
                    $arr_ajax    = array();
                    $campos_ajax = explode(", ", (string)$rowCamposServicio->DATOS_AJAX);

                    foreach ($campos_ajax as $datos_ajax):
                        $concepto_ajax               = explode(" => ", (string)$datos_ajax);
                        $arr_ajax[$concepto_ajax[0]] = $concepto_ajax[1];
                    endforeach;

                    $arrCamposOpcionales['campos']['datos_ajax'] = $campos_ajax;
                else:
                    $arrCamposOpcionales['campos']['datos_ajax'] = array();
                endif;

                $arrCamposServicio['OPCIONALES'][$contadorOpcionales] = $arrCamposOpcionales;
                $contadorOpcionales++;
            endif;
        endwhile;

        return $arrCamposServicio;
    }

    /**
     * @param $idOrdenContratacion ORDEN DE CONTRATACION
     * FUNCION UTILIZADA PARA CREAR LOS VIAJES NO PROGRAMADOS CUANDO EL TIPO DEL SERVICIO ES DE TRANSPORTE
     */
    function CrearViajeNoProgramado($idOrdenContratacion)
    {
        global $bd;

        //BUSCO LA ORDEN DE CONTRATACION
        $rowOrdenContratacion = $bd->VerReg("ORDEN_CONTRATACION", "ID_ORDEN_CONTRATACION", $idOrdenContratacion);

        //BUSCO TODAS LAS DIRECCIONES DE LA ORDEN DE CONTRATACION
        $sqlDirecciones    = "SELECT ID_DIRECCION, TIPO, FECHA_SERVICIO, HORA_SERVICIO
                              FROM ORDEN_CONTRATACION_DESTINO
                              WHERE ID_ORDEN_CONTRATACION = $rowOrdenContratacion->ID_ORDEN_CONTRATACION AND BAJA = 0
                              ORDER BY ORDEN ASC";
        $resultDirecciones = $bd->ExecSQL($sqlDirecciones);

        //CREAMOS EL NUEVO VIAJE NO PROGRAMADO
        $sqlInsertViaje = "INSERT INTO RUTA_VIAJE SET
                                ID_RUTA = NULL,
                                VIAJE_PROGRAMADO = 'No Programado',
                                BAJA = 0 ";
        $bd->ExecSQL($sqlInsertViaje);

        //RECUPERO EL ID DEL VIAJE NO PROGRAMADO RECIEN CREADO
        $idRutaViaje = $bd->IdAsignado();

        //CONTADOR DE DIAS
        $actualizarFechaInicio = true;
        $fechaInicio           = "";
        $fechaInsertar         = "";

        while ($rowDireccion = $bd->SigReg($resultDirecciones)):
            if ($actualizarFechaInicio):
                //GUARDAMOS EL DIA Y LA FECHA INICIAL DEL SERVICIO
                $dia           = 1;
                $fechaInicio   = $rowDireccion->FECHA_SERVICIO;
                $fechaInsertar = $rowDireccion->FECHA_SERVICIO;

                //ESTABLECEMOS LA FECHA DE FIN DEL VIAJE
                $sqlUpdateRutaViaje = "UPDATE RUTA_VIAJE
                                           SET FECHA_INICIO = '" . $fechaInicio . "'
                                           WHERE ID_RUTA_VIAJE = $idRutaViaje";
                $bd->ExecSQL($sqlUpdateRutaViaje);

                $actualizarFechaInicio = false;
            else:
                //BUSCAMOS LA SIGUIENTE FECHA INICIAL Y SU CORRESPONDIENTE DIA
                $sqlDiaServicio    = "SELECT DATEDIFF('" . $rowDireccion->FECHA_SERVICIO . "', '" . $fechaInicio . "') + 1 AS DIA";
                $resultDiaServicio = $bd->ExecSQL($sqlDiaServicio);
                $rowDiaServicio    = $bd->SigReg($resultDiaServicio);

                //GUARDAMOS EL DIA Y LA FECHA CORRESPONDIENTES AL SIGUIENTE SERVICIO
                $dia           = $rowDiaServicio->DIA;
                $fechaInsertar = $rowDireccion->FECHA_SERVICIO;
            endif;

            //CREAMOS LAS LINEAS DEL VIAJE NO PROGRAMADO
            $sqlInsertLineaViaje = "INSERT INTO RUTA_VIAJE_LINEA SET
                                        ID_RUTA_VIAJE = $idRutaViaje,
                                        ID_DIRECCION = $rowDireccion->ID_DIRECCION,
                                        TIPO = '$rowDireccion->TIPO',
                                        DIA = '$dia',
                                        FECHA = '$fechaInsertar',
                                        HORA = '$rowDireccion->HORA_SERVICIO',
                                        BAJA = 0";
            $bd->ExecSQL($sqlInsertLineaViaje);
        endwhile;

        //ESTABLECEMOS LA FECHA DE FIN DEL VIAJE
        $sqlUpdateRutaViaje = "UPDATE RUTA_VIAJE
                                   SET FECHA_FIN = '" . $fechaInsertar . "'
                                   WHERE ID_RUTA_VIAJE = $idRutaViaje";
        $bd->ExecSQL($sqlUpdateRutaViaje);

        //GUARDAMOS EL VIAJE EN LA ORDEN DE TRANSPORTE
        $sqlUpdateOrdTrans = "UPDATE ORDEN_TRANSPORTE
                                   SET ID_RUTA_VIAJE = $idRutaViaje
                                   WHERE ID_ORDEN_TRANSPORTE = $rowOrdenContratacion->ID_ORDEN_TRANSPORTE";
        $bd->ExecSQL($sqlUpdateOrdTrans);

        //GUARDAMOS EL VIAJE EN LA SOLICITUD DE TRANSPORTE
        $sqlUpdateSolTrans = "UPDATE SOLICITUD_TRANSPORTE
                                   SET ID_RUTA_VIAJE = $idRutaViaje
                                   WHERE ID_ORDEN_TRANSPORTE = $rowOrdenContratacion->ID_ORDEN_TRANSPORTE";
        $bd->ExecSQL($sqlUpdateSolTrans);
    }

    /**
     * @param $idOrdenContratacion ORDEN DE CONTRATACION
     * FUNCION UTILIZADA PARA ACTUALIZAR LOS VIAJES NO PROGRAMADOS CUANDO EL TIPO DEL SERVICIO ES DE TRANSPORTE
     */
    function ActualizarViaje($idOrdenContratacion)
    {
        global $bd;

        //BUSCO LA ORDEN DE CONTRATACION
        $rowOrdenContratacion = $bd->VerReg("ORDEN_CONTRATACION", "ID_ORDEN_CONTRATACION", $idOrdenContratacion);

        //BUSCO LA ORDEN DE TRANSPORTE
        $rowOrdenTransporte = $bd->VerReg("ORDEN_TRANSPORTE", "ID_ORDEN_TRANSPORTE", $rowOrdenContratacion->ID_ORDEN_TRANSPORTE);

        //BUSCO EL VIAJE
        $rowViaje = $bd->VerReg("RUTA_VIAJE", "ID_RUTA_VIAJE", $rowOrdenTransporte->ID_RUTA_VIAJE);

        //BUSCO TODAS LAS DIRECCIONES DE LA ORDEN DE CONTRATACION
        $sqlDirecciones    = "SELECT ID_DIRECCION, TIPO, FECHA_SERVICIO, HORA_SERVICIO
                              FROM ORDEN_CONTRATACION_DESTINO
                              WHERE ID_ORDEN_CONTRATACION = $rowOrdenContratacion->ID_ORDEN_CONTRATACION AND BAJA = 0
                              ORDER BY ORDEN ASC";
        $resultDirecciones = $bd->ExecSQL($sqlDirecciones);

        //CONTADOR DE DIAS
        $actualizarFechaInicio = true;
        $fechaInicio           = "";
        $fechaInsertar         = "";

        while ($rowDireccion = $bd->SigReg($resultDirecciones)):
            if ($actualizarFechaInicio):
                //GUARDAMOS EL DIA Y LA FECHA INICIAL DEL SERVICIO
                $dia           = 1;
                $fechaInicio   = $rowDireccion->FECHA_SERVICIO;
                $fechaInsertar = $rowDireccion->FECHA_SERVICIO;

                //ESTABLECEMOS LA FECHA DE FIN DEL VIAJE
                $sqlUpdateRutaViaje = "UPDATE RUTA_VIAJE
                                           SET FECHA_INICIO = '" . $fechaInicio . "'
                                           WHERE ID_RUTA_VIAJE = $rowViaje->ID_RUTA_VIAJE";
                $bd->ExecSQL($sqlUpdateRutaViaje);

                $actualizarFechaInicio = false;
            else:
                //BUSCAMOS LA SIGUIENTE FECHA INICIAL Y SU CORRESPONDIENTE DIA
                $sqlDiaServicio    = "SELECT DATEDIFF('" . $rowDireccion->FECHA_SERVICIO . "', '" . $fechaInicio . "') + 1 AS DIA";
                $resultDiaServicio = $bd->ExecSQL($sqlDiaServicio);
                $rowDiaServicio    = $bd->SigReg($resultDiaServicio);

                //GUARDAMOS EL DIA Y LA FECHA CORRESPONDIENTES AL SIGUIENTE SERVICIO
                $dia           = $rowDiaServicio->DIA;
                $fechaInsertar = $rowDireccion->FECHA_SERVICIO;
            endif;

            //BUSCAMOS LA LINEA DEL VIAJE CON ESA DIRECCION
            $GLOBALS["NotificaErrorPorEmail"] = "No";
            $rowLineaViaje                    = $bd->VerRegRest("RUTA_VIAJE_LINEA", "ID_RUTA_VIAJE = $rowViaje->ID_RUTA_VIAJE AND ID_DIRECCION = $rowDireccion->ID_DIRECCION AND BAJA = 0", "No");

            if ($rowLineaViaje != false):
                //ACTUALIZAMOS LA LINEA DEL VIAJE NO PROGRAMADO
                $sqlUpdateLineaViaje = "UPDATE RUTA_VIAJE_LINEA SET
                                            TIPO = '$rowDireccion->TIPO',
                                            DIA = '$dia',
                                            FECHA = '$fechaInsertar',
                                            HORA = '$rowDireccion->HORA_SERVICIO'
                                            WHERE ID_RUTA_VIAJE_LINEA = $rowLineaViaje->ID_RUTA_VIAJE_LINEA";
                $bd->ExecSQL($sqlUpdateLineaViaje);
            else:
                //CREAMOS LA LINEA DEL VIAJE NO PROGRAMADO
                $sqlInsertLineaViaje = "INSERT INTO RUTA_VIAJE_LINEA SET
                                            ID_RUTA_VIAJE = $rowViaje->ID_RUTA_VIAJE,
                                            ID_DIRECCION = $rowDireccion->ID_DIRECCION,
                                            TIPO = '$rowDireccion->TIPO',
                                            DIA = '$dia',
                                            FECHA = '$fechaInsertar',
                                            HORA = '$rowDireccion->HORA_SERVICIO'";
                $bd->ExecSQL($sqlInsertLineaViaje);
            endif;
        endwhile;

        //ESTABLECEMOS LA FECHA DE FIN DEL VIAJE
        $sqlUpdateRutaViaje = "UPDATE RUTA_VIAJE
                                   SET FECHA_FIN = '" . $fechaInsertar . "'
                                   WHERE ID_RUTA_VIAJE = $rowViaje->ID_RUTA_VIAJE";
        $bd->ExecSQL($sqlUpdateRutaViaje);
    }

    /**
     * @param $idOrdenContratacion ORDEN DE CONTRATACION
     * FUNCION UTILIZADA PARA ASIGNAR EL VIAJE DE LA CONTRATACION A LAS LINEAS DE LOS PEDIDOS
     */
    function AsignarViajeLineasPedido($idOrdenContratacion)
    {
        global $bd;

        //BUSCO LA ORDEN DE CONTRATACION
        $rowOrdenContratacion = $bd->VerReg("ORDEN_CONTRATACION", "ID_ORDEN_CONTRATACION", $idOrdenContratacion);

        //BUSCO LA ORDEN DE TRANSPORTE
        $rowOrdenTransporte = $bd->VerReg("ORDEN_TRANSPORTE", "ID_ORDEN_TRANSPORTE", $rowOrdenContratacion->ID_ORDEN_TRANSPORTE);

        //BUSCO TODAS LAS LINEAS DE PEDIDO DEL TRANSPORTE ASOCIADO A LA CONTRATACION
        $sqlLineas    = "SELECT MSL.ID_PEDIDO_SALIDA_LINEA
                          FROM MOVIMIENTO_SALIDA_LINEA MSL
                          INNER JOIN EXPEDICION E ON E.ID_EXPEDICION = MSL.ID_EXPEDICION
                          WHERE E.ID_ORDEN_TRANSPORTE = $rowOrdenContratacion->ID_ORDEN_TRANSPORTE AND MSL.LINEA_ANULADA = 0 AND MSL.BAJA = 0 AND E.BAJA = 0";
        $resultLineas = $bd->ExecSQL($sqlLineas);

        while ($rowLinea = $bd->SigReg($resultLineas)):
            //BUSCO LA ORDEN DE CONTRATACION
            $rowLineaPedido = $bd->VerReg("PEDIDO_SALIDA_LINEA", "ID_PEDIDO_SALIDA_LINEA", $rowLinea->ID_PEDIDO_SALIDA_LINEA);

            if (($rowLineaPedido->CANAL_DE_ENTREGA == 'Semiurgente') || ($rowLineaPedido->CANAL_DE_ENTREGA == 'Urgente')):
                //BUSCO EL ALMACEN DE ORIGEN
                $rowAlmacenOrigen = $bd->VerReg("ALMACEN", "ID_ALMACEN", $rowLineaPedido->ID_ALMACEN_ORIGEN);

                //BUSCO LA DIRECCION DE ORIGEN
                $rowDireccionOrigen = $bd->VerReg("DIRECCION", "ID_CENTRO_FISICO", $rowAlmacenOrigen->ID_CENTRO_FISICO, "No");

                //SE OBTIENE LA DIRECCION DE DESTINO
                $rowPedidoSalida = $bd->VerReg("PEDIDO_SALIDA", "ID_PEDIDO_SALIDA", $rowLineaPedido->ID_PEDIDO_SALIDA);

                //SI EL PEDIDO ES DE COMPONENTES EL DESTINO ES UN CLIENTE
                if ($rowPedidoSalida->TIPO_PEDIDO == 'Venta'):
                    //SE OBTIENE LA DIRECCION EN BASE AL CLIENTE DEL PEDIDO
                    $rowDireccionDestino = $bd->VerReg("DIRECCION", "ID_CLIENTE", $rowPedidoSalida->ID_CLIENTE, "No");

                //SI EL PEDIDO ES DE COMPONENTES EL DESTINO ES UN PROVEEDOR
                elseif ($rowPedidoSalida->TIPO_PEDIDO == 'Componentes a Proveedor'):
                    //SE OBTIENE LA DIRECCION EN BASE AL PROVEEDOR DEL PEDIDO
                    $rowDireccionDestino = $bd->VerReg("DIRECCION", "ID_PROVEEDOR", $rowPedidoSalida->ID_PROVEEDOR, "No");

                //EN CUALQUIER OTRO CASO EL DESTINO ES UN ALMACEN
                else:
                    //BUSCO EL ALMACEN DE DESTINO
                    $rowAlmacenDestino = $bd->VerReg("ALMACEN", "ID_ALMACEN", $rowLineaPedido->ID_ALMACEN_DESTINO);

                    //BUSCO LA DIRECCION DE DESTINO
                    $rowDireccionDestino = $bd->VerReg("DIRECCION", "ID_CENTRO_FISICO", $rowAlmacenDestino->ID_CENTRO_FISICO, "No");
                endif;

                if (($rowDireccionOrigen != false) && ($rowDireccionDestino != false)):
                    //BUSCO LA LINEA DE VIAJE DE TIPO 'Recogida' QUE EXISTA PARA LA MISMA DIRECCION DEL PEDIDO
                    $sqlLineaViajeRecogida    = "SELECT RVL.ID_RUTA_VIAJE_LINEA, RVL.FECHA, RVL.HORA
                                                  FROM RUTA_VIAJE_LINEA RVL
                                                  INNER JOIN RUTA_VIAJE RV ON RV.ID_RUTA_VIAJE = RVL.ID_RUTA_VIAJE
                                                  WHERE RVL.ID_DIRECCION = $rowDireccionOrigen->ID_DIRECCION AND (TIPO = 'Recogida' OR TIPO = 'Recogida y Entrega') AND RVL.ID_RUTA_VIAJE = $rowOrdenTransporte->ID_RUTA_VIAJE AND RV.BAJA = 0 AND RVL.BAJA = 0";
                    $resultLineaViajeRecogida = $bd->ExecSQL($sqlLineaViajeRecogida);

                    if (($resultLineaViajeRecogida != false) && ($bd->NumRegs($resultLineaViajeRecogida) == 1)):
                        $rowLineaViajeRecogida = $bd->SigReg($resultLineaViajeRecogida);

                        //BUSCO LA LINEA DE VIAJE DE TIPO 'Entrega' QUE EXISTA PARA LA MISMA DIRECCION DEL PEDIDO
                        $sqlLineaViajeEntrega    = "SELECT RVL.ID_RUTA_VIAJE_LINEA, RVL.FECHA, RVL.HORA
                                                      FROM RUTA_VIAJE_LINEA RVL
                                                      INNER JOIN RUTA_VIAJE RV ON RV.ID_RUTA_VIAJE = RVL.ID_RUTA_VIAJE
                                                      WHERE RVL.ID_DIRECCION = $rowDireccionDestino->ID_DIRECCION AND (TIPO = 'Entrega' OR TIPO = 'Recogida y Entrega') AND RVL.ID_RUTA_VIAJE = $rowOrdenTransporte->ID_RUTA_VIAJE AND RV.BAJA = 0 AND RVL.BAJA = 0";
                        $resultLineaViajeEntrega = $bd->ExecSQL($sqlLineaViajeEntrega);

                        if (($resultLineaViajeEntrega != false) && ($bd->NumRegs($resultLineaViajeEntrega) == 1)):
                            $rowLineaViajeEntrega = $bd->SigReg($resultLineaViajeEntrega);

                            //GUARDAMOS LA LINEA DE VIAJE DE RECOGIDA Y ENTREGA Y ACTUALIZAMOS LAS FECHAS ESTIMADAS DE EXPEDICION Y LLEGADA EN LA LINEA DE PEDIDO CORRESPONDIENTE
                            $sqlUpdateLineaPedido = "UPDATE PEDIDO_SALIDA_LINEA SET
                                                          ID_RUTA_VIAJE_LINEA_ORIGEN = $rowLineaViajeRecogida->ID_RUTA_VIAJE_LINEA
                                                        , ID_RUTA_VIAJE_LINEA_DESTINO = $rowLineaViajeEntrega->ID_RUTA_VIAJE_LINEA
                                                        , FECHA_SHIPPING = '" . $rowLineaViajeRecogida->FECHA . " " . $rowLineaViajeRecogida->HORA . "'
                                                        , FECHA_ESTIMADA_LLEGADA = '" . $rowLineaViajeEntrega->FECHA . " " . $rowLineaViajeEntrega->HORA . "'
                                                        WHERE ID_PEDIDO_SALIDA_LINEA = $rowLinea->ID_PEDIDO_SALIDA_LINEA";
                            $bd->ExecSQL($sqlUpdateLineaPedido);
                        endif;
                    endif;
                endif;
            endif;
        endwhile;
    }

    /**
     * @param $estadoInicial ESTADO INICIAL DE LA OT
     * @param $estadoFinal ESTADO FINAL DE LA OT
     * FUNCION UTILIZADA PARA CREAR EVENTOS BLOCKCHAIN DE MANERA PARAMETRIZADA
     */
    /*function crearEventosParametrizados($estadoInicial = "", $estadoFinal, $rowOrdenTransporte)
    {

        global $bd;
        global $auxiliar;
        global $administrador;
        global $NotificaErrorPorEmail;
        global $pathClases;
        global $selEstadoMaterial;
        global $selTipoContenido;

        //OBTENEMOS EL ROL SEGÚN EL ESTADO FINAL Y REALIZAMOS LAS ACTUACIONES INICIALES NECESARIAS
        $rol = "";
        if (($estadoFinal == "Creada") || ($estadoFinal == "Entregado a Forwarder") || ($estadoFinal == "Preparado en Proveedor")):

            if ($estadoFinal == "Entregado a Forwarder"):
                //SI EL ESTADO FINAL ES 'Entregado a Forwarder' ACTULIZO LA FECHA_REAL_CONFIRMAR_MATERIAL_PREPARADO
                $sqlUpdate = "UPDATE ORDEN_TRANSPORTE_CONSTRUCCION SET FECHA_REAL_CONFIRMAR_MATERIAL_PREPARADO = '" . date("Y-m-d H:i:s") . "' WHERE ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE";
                $bd->ExecSQL($sqlUpdate);
            endif;

            //OBTENEMOS EL ROL
            $rol = "Proveedor";

        elseif (($estadoFinal == "Puerto Origen") || ($estadoFinal == "Transito Internacional") || ($estadoFinal == "Puerto Destino")):

            if ($estadoFinal == "Puerto Destino"):
                //SI EL SIGUIENTE ESTADO ES 'Puerto Destino', INICIAMOS EL Ciclo Entrega en Obra
                $sqlUpdate = "UPDATE ORDEN_TRANSPORTE SET ESTADO_ENTREGA_OBRA = 'En Terminal' WHERE ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE";
                $bd->ExecSQL($sqlUpdate);
            endif;

            //OBTENEMOS EL ROL
            $rol = "Forwarder";

        elseif (($estadoFinal == "Liberado Aduana") || ($estadoFinal == "Aduana Destino")):

            //OBTENEMOS EL ROL
            $rol = "Agente aduanal";

        elseif (($estadoFinal == "Transito Local") || ($estadoFinal == "Entregado") || ($estadoFinal == "Recepcionado")):

            //OBTENEMOS EL ROL
            $rol = "Transportista InLand";

        endif;

        //OBTENGO EL OBJETO DE BLOCKCHAIN SEGÚN EL ESTADO FINAL
        $NotificaErrorPorEmail = "No";
        $rowObjetoBlockchain   = $bd->VerRegRest("BLOCKCHAIN_OBJETO", "TIPO_EVENTO = '$estadoFinal' AND BAJA = 0", "No");

        //SI EXISTE EL OBJETO BLOCKCHAIN, CREAMOS LOS EVENTOS NECESARIOS
        if ($rowObjetoBlockchain):

            //COMPRUEBO SI YA EXISTE ESE EVENTO PARA LA OTC Y LO DOY DE BAJA
            $sqlEventoDuplicado    = "SELECT ID_EVENTO
                                  FROM EVENTO
                                  WHERE TIPO_EVENTO = '" . $bd->escapeCondicional($rowObjetoBlockchain->TIPO_EVENTO) . "' AND TIPO_OBJETO = 'Orden transporte' AND ID_OBJETO = '$rowOrdenTransporte->ID_ORDEN_TRANSPORTE' AND BAJA = 0";
            $resultEventoDuplicado = $bd->ExecSQL($sqlEventoDuplicado);
            $numDuplicados         = $bd->NumRegs($resultEventoDuplicado);

            if ($numDuplicados > 0):
                while ($rowEventoDuplicado = $bd->SigReg($resultEventoDuplicado)):
                    $sqlUpdate = "UPDATE EVENTO_PENDIENTE_TRANSMITIR SET BAJA = 1 WHERE ID_EVENTO = $rowEventoDuplicado->ID_EVENTO";
                    $bd->ExecSQL($sqlUpdate);

                    //MODIFICO LOS DATOS DEL TRANSBORDO
                    $sqlEventoTransbordoDuplicado    = "SELECT ID_EVENTO_DATOS_TRANSBORDO
                                                          FROM EVENTO_DATOS_TRANSBORDO
                                                          WHERE ID_EVENTO = $rowEventoDuplicado->ID_EVENTO AND BAJA = 0";
                    $resultEventoTransbordoDuplicado = $bd->ExecSQL($sqlEventoTransbordoDuplicado);

                    while ($rowEventoTransbordoDuplicado = $bd->SigReg($resultEventoTransbordoDuplicado)):
                        $sqlUpdate = "UPDATE EVENTO_DATOS_TRANSBORDO SET BAJA = 1 WHERE ID_EVENTO_DATOS_TRANSBORDO = $rowEventoTransbordoDuplicado->ID_EVENTO_DATOS_TRANSBORDO";
                        $bd->ExecSQL($sqlUpdate);
                    endwhile;

                    //$sqlUpdate = "UPDATE EVENTO_DATOS SET BAJA = 1 WHERE ID_EVENTO = $rowEventoDuplicado->ID_EVENTO";
                    //$bd->ExecSQL($sqlUpdate);
                    $sqlUpdate = "UPDATE EVENTO_DATOS_PARAMETRIZADOS SET BAJA = 1 WHERE ID_EVENTO = $rowEventoDuplicado->ID_EVENTO";
                    $bd->ExecSQL($sqlUpdate);

                    $sqlUpdate = "UPDATE EVENTO_DOCUMENTO SET BAJA = 1 WHERE ID_EVENTO = $rowEventoDuplicado->ID_EVENTO";
                    $bd->ExecSQL($sqlUpdate);

                    $sqlUpdate = "UPDATE EVENTO SET BAJA = 1 WHERE ID_EVENTO = $rowEventoDuplicado->ID_EVENTO";
                    $bd->ExecSQL($sqlUpdate);
                endwhile;
            endif;

            // CREACION DE EVENTO PARA REGISTRAR EN BLOCKCHAIN
            $fecha_actual = date("Y-m-d H:i:s");
            $sqlInsert    = "INSERT INTO EVENTO SET
                          TIPO_EVENTO = '" . $bd->escapeCondicional($estadoFinal) . "'
                          , ID_ADMINISTRADOR = '" . $administrador->ID_ADMINISTRADOR . "'
                          , ROL_ADMINISTRADOR = '" . $bd->escapeCondicional($rol) . "'
                          , TIPO_OBJETO = 'Orden transporte'
                          , ID_OBJETO = '" . $rowOrdenTransporte->ID_ORDEN_TRANSPORTE . "'
                          , FECHA_CREACION = '" . $fecha_actual . "'
                          , FECHA_ULTIMA_MODIFICACION = '" . $fecha_actual . "'
                          , BAJA = 0";
            $bd->ExecSQL($sqlInsert);
            $idEvento = $bd->IdAsignado();

            $rowEvento = $bd->VerReg("EVENTO", "ID_EVENTO", $idEvento);

            //OBTENGO LOS CAMPOS DEL OBJETO BLOCKCHAIN
            $sqlCamposObjetoBlockchain    = "SELECT C.NOMBRE_MOSTRAR, C.NOMBRE_CAMPO
                                        FROM BLOCKCHAIN_OBJETO_CAMPO BOC
                                            INNER JOIN CAMPO_SELECCIONABLE_BLOCKCHAIN C ON C.ID_CAMPO_SELECCIONABLE_BLOCKCHAIN = BOC.ID_CAMPO_SELECCIONABLE_BLOCKCHAIN
                                        WHERE BOC.ID_BLOCKCHAIN_OBJETO = $rowObjetoBlockchain->ID_BLOCKCHAIN_OBJETO AND BOC.BAJA = 0";
            $resultCamposObjetoBlockchain = $bd->ExecSQL($sqlCamposObjetoBlockchain);

            //VAMOS INSERTANDO LOS CAMPOS Y SUS VALORES EN LA TABLA EVENTO_DATOS_PARAMETRIZADOS
            $fieldName  = array();
            $fieldValue = array();

            while ($rowCamposObjetoBlockchain = $bd->SigReg($resultCamposObjetoBlockchain)):

                $sqlInsert = "INSERT INTO EVENTO_DATOS_PARAMETRIZADOS SET
                      ID_EVENTO = '" . $idEvento . "'
                      , NOMBRE_MOSTRAR = '" . $bd->escapeCondicional($rowCamposObjetoBlockchain->NOMBRE_MOSTRAR) . "'
                      , NOMBRE_CAMPO = '" . $bd->escapeCondicional($rowCamposObjetoBlockchain->NOMBRE_CAMPO) . "'
                      , VALOR_CAMPO = '" . $bd->escapeCondicional($rowOrdenTransporte->{$rowCamposObjetoBlockchain->NOMBRE_CAMPO}) . "'";
                $bd->ExecSQL($sqlInsert);
                $idEventoDatos = $bd->IdAsignado();

                //CREAMOS LOS ARRAYS
                if (($rowCamposObjetoBlockchain->NOMBRE_CAMPO == "BARCO") || ($rowCamposObjetoBlockchain->NOMBRE_CAMPO == "NAVIERA")):

                    //SI EL CAMPO ES EL BARCO O LA NAVIERA, ENTONCES REGISTRO LOS DATOS DE LOS TRANSBORDOS
                    $sqlTransbordos    = "SELECT OTT.*, B.NOMBRE AS BARCO_NOMBRE, N.NOMBRE AS NAVIERA_NOMBRE
                                FROM ORDEN_TRANSPORTE_TRANSBORDO OTT
                                INNER JOIN BARCO B ON B.ID_BARCO = OTT.ID_BARCO
                                INNER JOIN NAVIERA N ON N.ID_NAVIERA = OTT.ID_NAVIERA
                                WHERE OTT.ID_ORDEN_TRANSPORTE = " . $rowOrdenTransporte->ID_ORDEN_TRANSPORTE;
                    $resultTransbordos = $bd->ExecSQL($sqlTransbordos);

                    $nombreBarcos = array();

                    while ($rowTransbordo = $bd->SigReg($resultTransbordos)):

                        $nombreBarcos[]   = $rowTransbordo->BARCO_NOMBRE;
                        $nombreNavieras[] = $rowTransbordo->NAVIERA_NOMBRE;

                        //COMPRUEBO SI EL EVENTO YA TIENE TRANSBORDOS CON EL MISMO ID_ORDEN_TRANSPORTE_TRANSBORDO
                        $sqlTransbordosEvento    = "SELECT *
                                             FROM EVENTO_DATOS_TRANSBORDO
                                             WHERE ID_EVENTO = $idEvento AND ID_ORDEN_TRANSPORTE_TRANSBORDO = $rowTransbordo->ID_ORDEN_TRANSPORTE_TRANSBORDO AND BAJA = 0";
                        $resultTransbordosEvento = $bd->ExecSQL($sqlTransbordosEvento);

                        if ($bd->NumRegs($resultTransbordosEvento) == 0):
                            $sqlInsert = "INSERT INTO EVENTO_DATOS_TRANSBORDO SET
                                      ID_EVENTO = '" . $idEvento . "'
                                      , ID_ORDEN_TRANSPORTE_TRANSBORDO  = '" . $rowTransbordo->ID_ORDEN_TRANSPORTE_TRANSBORDO . "'
                                      , ID_BARCO  = '" . ($rowTransbordo->ID_BARCO != '' ? $rowTransbordo->ID_BARCO : '') . "'
                                      , BARCO_NOMBRE  = '" . ($rowTransbordo->BARCO_NOMBRE != '' ? $bd->escapeCondicional($rowTransbordo->BARCO_NOMBRE) : '') . "'
                                      , ID_NAVIERA  = '" . ($rowTransbordo->ID_NAVIERA != '' ? $rowTransbordo->ID_NAVIERA : '') . "'
                                      , NAVIERA_NOMBRE  = '" . ($rowTransbordo->NAVIERA_NOMBRE != '' ? $bd->escapeCondicional($rowTransbordo->NAVIERA_NOMBRE) : '') . "'
                                      , BAJA = 0";
                            $bd->ExecSQL($sqlInsert);
                        endif;

                    endwhile;

                    //AÑADIMOS LOS BARCOS Y LAS NAVIERAS A LOS ARRAYS
                    $fieldName[] = "BARCOS";
                    $fieldName[] = "NAVIERAS";

                    $fieldValue[] = $nombreBarcos;
                    $fieldValue[] = $nombreNavieras;

                else:
                    $fieldName[]  = $rowCamposObjetoBlockchain->NOMBRE_CAMPO;
                    $fieldValue[] = $rowOrdenTransporte->{$rowCamposObjetoBlockchain->NOMBRE_CAMPO};
                endif;

            endwhile;

            if ($idEvento != '' && $idEventoDatos != ''):
                //CREAMOS EL EVENTO
                if (($estadoFinal == "Puerto Destino") || ($estadoFinal == "Entregado")):
                    $idEventoPendienteTransmitir = $auxiliar->crear_evento_pendiente_transmitir($rowEvento->ID_EVENTO, $fieldName, $fieldValue, $rowOrdenTransporte->FECHA_ENTREGA_REAL);
                else:
                    $idEventoPendienteTransmitir = $auxiliar->crear_evento_pendiente_transmitir($rowEvento->ID_EVENTO, $fieldName, $fieldValue);
                endif;
            endif;

            //CREAMOS LOS EVENTOS DE DOCUMENTOS

            //OBTENGO LOS CAMPOS DEL OBJETO BLOCKCHAIN
            $sqlDocumentosObjetoBlockchain    = "SELECT BOD.ID_DOCUMENTO_SELECCIONABLE_BLOCKCHAIN, D.TIPO_DOCUMENTO, D.RUTA, D.NOMBRE_CAMPO, D.SECCION_FICHERO
                                        FROM BLOCKCHAIN_OBJETO_DOCUMENTO BOD
                                        INNER JOIN DOCUMENTO_SELECCIONABLE_BLOCKCHAIN D ON D.ID_DOCUMENTO_SELECCIONABLE_BLOCKCHAIN = BOD.ID_DOCUMENTO_SELECCIONABLE_BLOCKCHAIN
                                        WHERE BOD.ID_BLOCKCHAIN_OBJETO = $rowObjetoBlockchain->ID_BLOCKCHAIN_OBJETO AND BOD.BAJA = 0";
            $resultDocumentosObjetoBlockchain = $bd->ExecSQL($sqlDocumentosObjetoBlockchain);

            //RECORREMOS LOS DOCUMENTOS ASOCIADOS A REGISTRAR PARA ESE OBJETO BLOCKCHAIN
            while ($rowDocumentosObjetoBlockchain = $bd->SigReg($resultDocumentosObjetoBlockchain)):
                //FACTUA ES UN CASO ESPECIAL AL PODER ESTAR EL NOMBRE DEL DOCUMENTO EN VARIAS COLUMNAS DISTINTAS
                if ($rowDocumentosObjetoBlockchain->TIPO_DOCUMENTO == 'Factura'):

                    $factura = '';
                    //COMPONENTE USADO
                    if (($selEstadoMaterial == "Usado" || $selEstadoMaterial == "Ambos") && ($selTipoContenido == "Componente" || $selTipoContenido == "Ambos")):
                        $factura = $rowOrdenTransporte->ADJUNTO_FACTURA_COMPONENTE_USADO;
                    //COMPONENTE NUEVO
                    elseif (($selEstadoMaterial == "Nuevo" || $selEstadoMaterial == "Ambos") && ($selTipoContenido == "Componente" || $selTipoContenido == "Ambos")):
                        $factura = $rowOrdenTransporte->ADJUNTO_FACTURA_COMPONENTE_NUEVO;
                    //UTIL USADO
                    elseif (($selEstadoMaterial == "Usado" || $selEstadoMaterial == "Ambos") && ($selTipoContenido == "Util" || $selTipoContenido == "Ambos")):
                        $factura = $rowOrdenTransporte->ADJUNTO_FACTURA_UTIL_USADO;
                    //UTIL NUEVO
                    elseif (($selEstadoMaterial == "Nuevo" || $selEstadoMaterial == "Ambos") && ($selTipoContenido == "Util" || $selTipoContenido == "Ambos")):
                        $factura = $rowOrdenTransporte->ADJUNTO_FACTURA_UTIL_NUEVO;
                    endif;

                    //COMPRUEBO SI TIENE FACTURA PARA CREAR EVENTO_DOCUMENTO
                    if ($factura != ''):
                        $docPL = $pathClases . $rowDocumentosObjetoBlockchain->RUTA . $factura;
                        if (file_exists($docPL) == 1): // HAY DOCUMENTO
                            //CREAMOS EL EVENTO DEL DOCUMENTO
                            $idEventoPendienteTransmitir = $auxiliar->crear_evento_documento_pendiente_transmitir($rowEvento->ID_EVENTO, 'Factura', $factura);
                        endif;
                    endif;
                else:
                    //SOLO EXISTE UN FICHERO DE ESE TIPO
                    if ($rowDocumentosObjetoBlockchain->NOMBRE_CAMPO != ""):
                        $doc = $pathClases . $rowDocumentosObjetoBlockchain->RUTA . $rowOrdenTransporte->{$rowDocumentosObjetoBlockchain->NOMBRE_CAMPO};
                        if (file_exists($doc) == 1): // HAY DOCUMENTO
                            //CREAMOS EL EVENTO DEL DOCUMENTO
                            $idEventoPendienteTransmitir = $auxiliar->crear_evento_documento_pendiente_transmitir($rowEvento->ID_EVENTO, $rowDocumentosObjetoBlockchain->TIPO_DOCUMENTO, $rowOrdenTransporte->{$rowDocumentosObjetoBlockchain->NOMBRE_CAMPO});
                        endif;

                    //EXISTE MAS DE UN FICHERO DE ESE TIPO
                    else:
                        $sqlDocumentosTipo       = "SELECT * FROM FICHERO WHERE TIPO_OBJETO='OrdenTransporte' AND ID_ORDEN_TRANSPORTE=$rowEvento->ID_OBJETO AND SECCION ='" . $rowDocumentosObjetoBlockchain->SECCION_FICHERO . "'";
                        $resultadoDocumentosTipo = $bd->ExecSQL($sqlDocumentosTipo);
                        $numDocTipo              = $bd->NumRegs($resultadoDocumentosTipo);

                        if ($numDocTipo > 0):
                            while ($rowFichero = $bd->SigReg($resultadoDocumentosTipo)):
                                $doc = $pathClases . $rowDocumentosObjetoBlockchain->RUTA . $rowFichero->FICHERO;
                                if (file_exists($doc) == 1): // HAY DOCUMENTO
                                    //CREAMOS EL EVENTO DEL DOCUMENTO
                                    $idEventoPendienteTransmitir = $auxiliar->crear_evento_documento_pendiente_transmitir($rowEvento->ID_EVENTO, $rowDocumentosObjetoBlockchain->TIPO_DOCUMENTO, $rowFichero->FICHERO);
                                endif;
                            endwhile;
                        endif;
                    endif;
                endif;

            endwhile;
        endif;

    }*/

    /**
     * @param $estadoInicial ESTADO INICIAL DE LA OT
     * @param $estadoFinal ESTADO FINAL DE LA OT
     * FUNCION UTILIZADA PARA ELIMINAR EVENTOS BLOCKCHAIN DE MANERA PARAMETRIZADA
     */
    /*function eliminarEventosParametrizados($estadoInicial = "", $estadoFinal, $rowOrdenTransporte)
    {

        global $bd;
        global $auxiliar;
        global $NotificaErrorPorEmail;
        global $observaciones_sistema;

        //REALIZAMOS LAS ACTUACIONES INICIALES NECESARIAS
        if ($estadoFinal == "Preparado en Proveedor"):

            //SI EL ESTADO final 'Preparado en Proveedor' BORRO LA FECHA_REAL_CONFIRMAR_MATERIAL_PREPARADO
            $sqlUpdate = "UPDATE ORDEN_TRANSPORTE_CONSTRUCCION SET FECHA_REAL_CONFIRMAR_MATERIAL_PREPARADO = NULL WHERE ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE";
            $bd->ExecSQL($sqlUpdate);

        elseif ($estadoInicial == "Puerto Destino"):

            //SI EL ANTERIOR ESTADO ES 'Puerto Destino', REVERTIMOS EL Ciclo Entrega en Obra
            $sqlUpdate = "UPDATE ORDEN_TRANSPORTE SET ESTADO_ENTREGA_OBRA = 'No Aplica' WHERE ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE";
            $bd->ExecSQL($sqlUpdate);

        endif;

        //COMPRUEBO SI EXISTE ESE EVENTO PARA LA OTC Y LO DOY DE BAJA
        $sqlEvento    = "SELECT *
                      FROM EVENTO
                      WHERE TIPO_EVENTO = '" . $bd->escapeCondicional($estadoInicial) . "' AND TIPO_OBJETO = 'Orden transporte' AND ID_OBJETO = '$rowOrdenTransporte->ID_ORDEN_TRANSPORTE' AND BAJA = 0";
        $resultEvento = $bd->ExecSQL($sqlEvento);
        $numEvento    = $bd->NumRegs($resultEvento);

        if ($numEvento > 0):
            while ($rowEvento = $bd->SigReg($resultEvento)):
                $NotificaErrorPorEmail = "No";
                $sqlUpdate             = "UPDATE EVENTO_PENDIENTE_TRANSMITIR SET BAJA = 1 WHERE ID_EVENTO = $rowEvento->ID_EVENTO";
                $bd->ExecSQL($sqlUpdate, "No");

                //DOY DE BAJA LOS EVENTOS DE TRANSBORDO
                $sqlEventoTransbordo    = "SELECT *
                                              FROM EVENTO_DATOS_TRANSBORDO
                                              WHERE ID_EVENTO = $rowEvento->ID_EVENTO AND BAJA = 0";
                $resultEventoTransbordo = $bd->ExecSQL($sqlEventoTransbordo);

                while ($rowEventoTransbordo = $bd->SigReg($resultEventoTransbordo)):
                    $sqlUpdate = "UPDATE EVENTO_DATOS_TRANSBORDO SET BAJA = 1 WHERE ID_EVENTO_DATOS_TRANSBORDO = $rowEventoTransbordo->ID_EVENTO_DATOS_TRANSBORDO";
                    $bd->ExecSQL($sqlUpdate, "No");

                endwhile;

                $fecha_finalizacion = date("Y-m-d H:i:s");

                $sqlEventoDocumento    = "SELECT *
                      FROM EVENTO_DOCUMENTO
                      WHERE ID_EVENTO = $rowEvento->ID_EVENTO AND BAJA = 0";
                $resultEventoDocumento = $bd->ExecSQL($sqlEventoDocumento);

                while ($rowEventoDocumento = $bd->SigReg($resultEventoDocumento)):

                    //LO BORRAMOS DE BLOCKCHAIN
                    if ($rowEventoDocumento->HASH != ''):

                        //CREAMOS LA PETICION DE BORRADO DEL EVENTO_DOCUMENTO
                        $idEventoPendienteTransmitir = $auxiliar->eliminar_evento_documento_pendiente_transmitir($rowEvento->ID_EVENTO, $rowEventoDocumento->ID_EVENTO_DOCUMENTO);

                    endif;


                    //COMPRUEBO SI TIENE INCIDENCIAS EL DOCUMENTO, EN CASO AFIRMATIVO, LAS CIERRO
                    $sqlIncidenciaEventoDocumento    = "SELECT *
                      FROM INCIDENCIA_BLOCKCHAIN
                      WHERE TABLA_OBJETO = 'EVENTO_DOCUMENTO' AND ID_OBJETO = $rowEventoDocumento->ID_EVENTO_DOCUMENTO AND (ESTADO = 'Creada' OR ESTADO = 'En Proceso')";
                    $resultIncidenciaEventoDocumento = $bd->ExecSQL($sqlIncidenciaEventoDocumento);

                    while ($rowIncidenciaEventoDocumento = $bd->SigReg($resultIncidenciaEventoDocumento)):
                        //CREO UNA OBSERVACION PARA INDICAR QUE LA INCIDENCIA SE HA CERRADO POR LA ELIMINACION DEL DOCUMENTO
                        $txObservaciones = "La incidencia se ha cerrado porque el documento asociado se ha eliminado. - The issue has been closed because the associated documento has been removed.";
                        $observaciones_sistema->Grabar('INCIDENCIA_BLOCKCHAIN', $rowIncidenciaEventoDocumento->ID_INCIDENCIA_BLOCKCHAIN, $txObservaciones);

                        $sqlUpdate = "UPDATE INCIDENCIA_BLOCKCHAIN SET ESTADO = 'Finalizada', FECHA_RESOLUCION = '" . $fecha_finalizacion . "', TIPO_RESOLUCION = 'Automatica' WHERE ID_INCIDENCIA_BLOCKCHAIN = $rowIncidenciaEventoDocumento->ID_INCIDENCIA_BLOCKCHAIN";
                        $bd->ExecSQL($sqlUpdate);
                    endwhile;

                endwhile;

                //COMPRUEBO SI TIENE INCIDENCIAS EL EVENTO, EN CASO AFIRMATIVO, LAS CIERRO
                $sqlIncidenciaEvento    = "SELECT *
                      FROM INCIDENCIA_BLOCKCHAIN
                      WHERE TABLA_OBJETO = 'EVENTO' AND ID_OBJETO = $rowEvento->ID_EVENTO AND (ESTADO = 'Creada' OR ESTADO = 'En Proceso')";
                $resultIncidenciaEvento = $bd->ExecSQL($sqlIncidenciaEvento);
                while ($rowIncidenciaEvento = $bd->SigReg($resultIncidenciaEvento)):
                    //CREO UNA OBSERVACION PARA INDICAR QUE LA INCIDENCIA SE HA CERRADO POR LA ELIMINACION DEL EVENTO
                    $txObservaciones = "La incidencia se ha cerrado porque el evento asociado se ha eliminado. - The issue has been closed because the associated event has been removed.";
                    $observaciones_sistema->Grabar('INCIDENCIA_BLOCKCHAIN', $rowIncidenciaEvento->ID_INCIDENCIA_BLOCKCHAIN, $txObservaciones);

                    $sqlUpdate = "UPDATE INCIDENCIA_BLOCKCHAIN SET ESTADO = 'Finalizada', FECHA_RESOLUCION = '" . $fecha_finalizacion . "', TIPO_RESOLUCION = 'Automatica' WHERE ID_INCIDENCIA_BLOCKCHAIN = $rowIncidenciaEvento->ID_INCIDENCIA_BLOCKCHAIN";
                    $bd->ExecSQL($sqlUpdate);
                endwhile;

                //LO BORRAMOS DE BLOCKCHAIN
                if ($rowEvento->JOBID != ''):

                    //CREAMOS LA PETICION DE BORRADO DEL EVENTO
                    $idEventoPendienteTransmitir = $auxiliar->eliminar_evento_pendiente_transmitir($rowEvento->ID_EVENTO);

                endif;

                //$sqlUpdate = "UPDATE EVENTO_DATOS SET BAJA = 1 WHERE ID_EVENTO = $rowEvento->ID_EVENTO";
                //$bd->ExecSQL($sqlUpdate);
                $sqlUpdate = "UPDATE EVENTO_DATOS_PARAMETRIZADOS SET BAJA = 1 WHERE ID_EVENTO = $rowEvento->ID_EVENTO";
                $bd->ExecSQL($sqlUpdate);

                $sqlUpdate = "UPDATE EVENTO_DOCUMENTO SET BAJA = 1 WHERE ID_EVENTO = $rowEvento->ID_EVENTO";
                $bd->ExecSQL($sqlUpdate);

                $sqlUpdate = "UPDATE EVENTO SET BAJA = 1 WHERE ID_EVENTO = $rowEvento->ID_EVENTO";
                $bd->ExecSQL($sqlUpdate);

            endwhile;
        endif;

    }*/

    function getImportes($sql)
    {
        global $bd;

        $arrImportes = array();
        $arrCEco     = array();
        $arrPEP      = array();
        $arrOrden    = array();

        $importeCEcoContratacion         = 0;
        $importeCEcoSociedadContratante  = 0;
        $importePEPContratacion          = 0;
        $importePEPSociedadContratante   = 0;
        $importeOrdenContratacion        = 0;
        $importeOrdenSociedadContratante = 0;

        $resultZTLOrig = $bd->ExecSQL($sql);
        while ($rowZTLOrig = $bd->SigReg($resultZTLOrig)):

            $unidadSigno = 1;
            if ($rowZTLOrig->TIPO_PEDIDO_SAP == 'ZTLF' || $rowZTLOrig->TIPO_PEDIDO_SAP == 'ZTLK'):
                $unidadSigno = -1;
            endif;
            //SE GUARDA EL IMPORTE DEL ZTL ORIGINAL
            if ($rowZTLOrig->TIPO_IMPUTACION == 'C'):
                $importeCEcoContratacion        += $rowZTLOrig->IMPORTE_TRANSMITIDO_A_SAP * $unidadSigno;
                $importeCEcoSociedadContratante += $rowZTLOrig->IMPORTE_TRANSMITIDO_A_SAP_SOCIEDAD_CONTRATANTE * $unidadSigno;
            elseif ($rowZTLOrig->TIPO_IMPUTACION == 'P'):
                $importePEPContratacion        += $rowZTLOrig->IMPORTE_TRANSMITIDO_A_SAP * $unidadSigno;
                $importePEPSociedadContratante += $rowZTLOrig->IMPORTE_TRANSMITIDO_A_SAP_SOCIEDAD_CONTRATANTE * $unidadSigno;
            elseif ($rowZTLOrig->TIPO_IMPUTACION == 'O'):
                $importeOrdenContratacion        += $rowZTLOrig->IMPORTE_TRANSMITIDO_A_SAP * $unidadSigno;
                $importeOrdenSociedadContratante += $rowZTLOrig->IMPORTE_TRANSMITIDO_A_SAP_SOCIEDAD_CONTRATANTE * $unidadSigno;
            endif;

            //SE BUSCAN LOS IMNPORTES DE LAS LÍNEAS RELACIONADAS
            $sqlZTLRelacionados    = "SELECT PEL.ID_PEDIDO_ENTRADA_LINEA, PEL.TIPO_IMPUTACION, PEL.IMPORTE_TRANSMITIDO_A_SAP, PEL.IMPORTE_TRANSMITIDO_A_SAP_SOCIEDAD_CONTRATANTE, PE.TIPO_PEDIDO_SAP
                                   FROM PEDIDO_ENTRADA_LINEA PEL
                                   INNER JOIN PEDIDO_ENTRADA PE ON PE.ID_PEDIDO_ENTRADA = PEL.ID_PEDIDO_ENTRADA
                                   WHERE PEL.ID_LINEA_ZTLI_ORIGINAL = $rowZTLOrig->ID_PEDIDO_ENTRADA_LINEA AND PEL.BAJA = 0";
            $resultZTLRelacionados = $bd->ExecSQL($sqlZTLRelacionados);
            while ($rowZTLRelacionado = $bd->SigReg($resultZTLRelacionados)):
                $unidadSignoZTLRelacionado = 1;
                if ($rowZTLRelacionado->TIPO_PEDIDO_SAP == 'ZTLF' || $rowZTLRelacionado->TIPO_PEDIDO_SAP == 'ZTLK'):
                    $unidadSignoZTLRelacionado = -1;
                endif;
                //SE GUARDA EL IMPORTE DEL ZTL ORIGINAL
                if ($rowZTLRelacionado->TIPO_IMPUTACION == 'C'):
                    $importeCEcoContratacion        += $rowZTLRelacionado->IMPORTE_TRANSMITIDO_A_SAP * $unidadSignoZTLRelacionado;
                    $importeCEcoSociedadContratante += $rowZTLRelacionado->IMPORTE_TRANSMITIDO_A_SAP_SOCIEDAD_CONTRATANTE * $unidadSignoZTLRelacionado;
                elseif ($rowZTLRelacionado->TIPO_IMPUTACION == 'P'):
                    $importePEPContratacion        += $rowZTLRelacionado->IMPORTE_TRANSMITIDO_A_SAP * $unidadSignoZTLRelacionado;
                    $importePEPSociedadContratante += $rowZTLRelacionado->IMPORTE_TRANSMITIDO_A_SAP_SOCIEDAD_CONTRATANTE * $unidadSignoZTLRelacionado;
                elseif ($rowZTLRelacionado->TIPO_IMPUTACION == 'O'):
                    $importeOrdenContratacion        += $rowZTLRelacionado->IMPORTE_TRANSMITIDO_A_SAP * $unidadSignoZTLRelacionado;
                    $importeOrdenSociedadContratante += $rowZTLRelacionado->IMPORTE_TRANSMITIDO_A_SAP_SOCIEDAD_CONTRATANTE * $unidadSignoZTLRelacionado;
                endif;
            endwhile;

        endwhile;

        array_push($arrCEco, $importeCEcoContratacion, $importeCEcoSociedadContratante);
        array_push($arrPEP, $importePEPContratacion, $importePEPSociedadContratante);
        array_push($arrOrden, $importeOrdenContratacion, $importeOrdenSociedadContratante);

        $arrImportes['CEco']  = $arrCEco;
        $arrImportes['PEP']   = $arrPEP;
        $arrImportes['Orden'] = $arrOrden;

        return $arrImportes;
    }

    /**
     * @param $idZTL identificador del pedido de entrada ZTL
     * FUNCION UTILIZADA PARA DIVIDIR LOS ZTL EN FUNCION DE LAS LÍNEAS
     */
    function particionarZTL($idZTL)
    {
        global $bd;

        $rowZTL    = $bd->VerReg("PEDIDO_ENTRADA", "ID_PEDIDO_ENTRADA", $idZTL);
        $numLineas = $bd->NumRegsTabla("PEDIDO_ENTRADA_LINEA", "ID_PEDIDO_ENTRADA = $rowZTL->ID_PEDIDO_ENTRADA AND ACTIVA = 1 AND BAJA = 0");

        //SE CALCULA EL Nº DE ZTLs NECESARIOS
        $numZTLs = ceil((float)$numLineas / (float)MAX_LINEAS_ZTL);

        $ztlActual = 2; //ya hay un ZTL creado, el que se quedará con las primeras 1000 líneas
        while ($ztlActual <= $numZTLs):
            $sqlInsert = "INSERT INTO PEDIDO_ENTRADA 
                          SET PEDIDO_SAP = '', 
                              ID_ADMINISTRADOR = $rowZTL->ID_ADMINISTRADOR, 
                              TIPO_PEDIDO = '$rowZTL->TIPO_PEDIDO', 
                              TIPO_PEDIDO_SAP = '$rowZTL->TIPO_PEDIDO_SAP', 
                              TIPO_ZTLI = '$rowZTL->TIPO_ZTLI', 
                              INDICADOR_LIBERACION = '$rowZTL->INDICADOR_LIBERACION', 
                              ID_PROVEEDOR = $rowZTL->ID_PROVEEDOR, 
                              ESTADO = '$rowZTL->ESTADO', 
                              ID_ORDEN_TRANSPORTE = $rowZTL->ID_ORDEN_TRANSPORTE, 
                              FECHA_CREACION = '" . $rowZTL->FECHA_CREACION . "', 
                              ID_ORDEN_CONTRATACION = $rowZTL->ID_ORDEN_CONTRATACION";
            $bd->ExecSQL($sqlInsert);
            $idPedidoZTL = $bd->IdAsignado();

            //DAMOS VALOR A PEDIDO_SAP YA QUE ES CLAVE UNICA
            $sqlUpdate = "UPDATE PEDIDO_ENTRADA 
                          SET PEDIDO_SAP = 'SGA" . str_pad((string)$idPedidoZTL, 7, "0", STR_PAD_LEFT) . "'
                          WHERE ID_PEDIDO_ENTRADA = $idPedidoZTL";
            $bd->ExecSQL($sqlUpdate);

            //se obtiene el ID de la línea 1001
            $sqlIdPrimeraLinea    = "SELECT PEL.ID_PEDIDO_ENTRADA_LINEA
                                  FROM (SELECT ID_PEDIDO_ENTRADA_LINEA
                                        FROM PEDIDO_ENTRADA_LINEA
                                        WHERE ID_PEDIDO_ENTRADA = $rowZTL->ID_PEDIDO_ENTRADA
                                        LIMIT " . (MAX_LINEAS_ZTL + 1) . ") PEL
                                  ORDER BY PEL.ID_PEDIDO_ENTRADA_LINEA DESC
                                  LIMIT 1";
            $resultIdPrimeraLinea = $bd->ExecSQL($sqlIdPrimeraLinea);
            $rowIdPrimeraLinea    = $bd->SigReg($resultIdPrimeraLinea);

            //se obtienen las siguientes 1000 líneas
            $idsLineas    = "";
            $coma         = '';
            $sqlLineas    = "SELECT ID_PEDIDO_ENTRADA_LINEA
                          FROM PEDIDO_ENTRADA_LINEA
                          WHERE ID_PEDIDO_ENTRADA_LINEA >= $rowIdPrimeraLinea->ID_PEDIDO_ENTRADA_LINEA AND ID_PEDIDO_ENTRADA = $rowZTL->ID_PEDIDO_ENTRADA
                          LIMIT " . MAX_LINEAS_ZTL;
            $resultLineas = $bd->ExecSQL($sqlLineas);
            while ($rowLinea = $bd->SigReg($resultLineas)):
                $idsLineas .= $coma . $rowLinea->ID_PEDIDO_ENTRADA_LINEA;
                $coma      = ',';
            endwhile;

            $sqlUpdate = "UPDATE PEDIDO_ENTRADA_LINEA
                          SET ID_PEDIDO_ENTRADA = $idPedidoZTL
                          WHERE ID_PEDIDO_ENTRADA_LINEA IN ($idsLineas)";
            $bd->ExecSQL($sqlUpdate);

            $ztlActual++;
        endwhile;

    }

    function AnularZTL($idOrdenTransporte)
    {
        global $bd;
        global $html;
        global $administrador;
        global $auxiliar;
        global $sap;

        //COMPRUEBO QUE LA ORDEN DE TRANSPORTE VENGA RELLENA
        $html->PagErrorCondicionado($idOrdenTransporte, "==", "", "FaltaOrdenTransporte");

        //BUSCO LA ORDEN DE TRANSPORTE
        $rowOrdenTransporte = $bd->VerReg("ORDEN_TRANSPORTE", "ID_ORDEN_TRANSPORTE", $idOrdenTransporte);

        //COMPRUEBO QUE LA ORDEN DE TRANSPORTE NO ESTE DADA DE BAJA
        $html->PagErrorCondicionado($rowOrdenTransporte->BAJA, "!=", 0, "OrdenTransporteBaja");

        //COMPROBAMOS QUE EL ESTADO SEA CORRECTO
        if (($rowOrdenTransporte->ESTADO_INTERFACES != "ZTL Transmitidos") && ($rowOrdenTransporte->ESTADO_INTERFACES != "ZTL en Transmision") && ($rowOrdenTransporte->ESTADO_INTERFACES != "Finalizada")):
            $html->PagError("EstadoOTIncorrecto");
        endif;

        //SI LA ORDEN DE TRANSPORTE ES DEL MODELO DE TRANSPORTE 'Segundo'
        if ($rowOrdenTransporte->MODELO_TRANSPORTE == 'Segundo'):
            //COMPRUEBO QUE NO EXISTAN RECOGIDAS EN ESTADO 'Parcialmente Expedida', 'Expedida', 'En Tránsito' o 'Recepcionada'
            $num = $bd->NumRegsTabla("EXPEDICION", "ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND BAJA = 0 AND ESTADO IN ('Parcialmente Expedida', 'Expedida', 'En Tránsito','Recepcionada') AND TIPO_ORDEN_RECOGIDA IN ('Recogida en Almacen', 'Recogida en Proveedor')");
            $html->PagErrorCondicionado($num, ">", 0, "RecogidasExpedidas");
        endif;
        //SI LA ORDEN DE TRANSPORTE ES DEL MODELO DE TRANSPORTE 'Segundo'

        //VARIABLE PARA CONTROLAR SI SE PRODUCEN ERRORES
        $errorPedido = false;

        //BUSCO EL PEDIDO
        $sqlPedidos    = "SELECT PE.ID_PEDIDO_ENTRADA, PE.PEDIDO_SAP, PE.TIPO_ZTLI, PE.TIPO_ZTLG, PE.TIPO_ZTLE, PE.TIPO_ZTLF, PE.TIPO_PEDIDO_SAP, PE.ID_ORDEN_CONTRATACION, PE.IMPORTE
                       FROM PEDIDO_ENTRADA PE
                       INNER JOIN ORDEN_TRANSPORTE OT ON OT.ID_ORDEN_TRANSPORTE = PE.ID_ORDEN_TRANSPORTE
                       WHERE PE.ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND PE.BAJA = 0";
        $resultPedidos = $bd->ExecSQL($sqlPedidos);

        //SI NO HAY PEDIDOS MUESTRO EL ERROR CORRESPONDIENTE
        $html->PagErrorCondicionado($bd->NumRegs($resultPedidos), "==", 0, "SinPedidosZTLAsociados");

        //COMPRUEBO QUE NO HAYA PEDIDOS ZTL PENDIENTES DE TRANSMITIR
        $num = $bd->NumRegsTabla("PEDIDO_ENTRADA_LINEA PEL INNER JOIN PEDIDO_ENTRADA PE ON PE.ID_PEDIDO_ENTRADA = PEL.ID_PEDIDO_ENTRADA", "PE.ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND PE.TIPO_PEDIDO_SAP IN ('ZTLI', 'ZTLF', 'ZTLG', 'ZTLE', 'ZTLJ') AND ((PE.TIPO_ZTLI = 'Modificacion Importes') OR (PE.TIPO_ZTLI = 'Importes en Posicion') OR (PE.TIPO_ZTLI = 'Anulacion Linea Pedido Proveedor') OR (PE.TIPO_ZTLG = 'Modificacion Importes') OR (PE.TIPO_ZTLE = 'Modificacion Importes') OR (PE.TIPO_ZTLF = 'Importes en Posicion')) AND PE.BAJA = 0 AND PEL.ENVIADO_SAP = 0 AND PEL.BAJA = 0");
        $html->PagErrorCondicionado($num, ">", 0, "ZTLImportesPendientesTransmitir");

        // LOG MOVIMIENTOS
        $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Orden de Transporte", $rowOrdenTransporte->ID_ORDEN_TRANSPORTE, "Anular ZTL");

        //RECORRO LOS PEDIDOS PARA TRANSMITIRLOS A SAP
        while ($rowPedido = $bd->SigReg($resultPedidos)):

            //INICIO LA TRANSACCION
            $bd->begin_transaction();

            //BUSCO LAS LINEAS DEL PEDIDO PARA MARCARLAS DE BAJA
            $sqlLineasPedido    = "SELECT *
                                FROM PEDIDO_ENTRADA_LINEA
                                WHERE ID_PEDIDO_ENTRADA = $rowPedido->ID_PEDIDO_ENTRADA AND BAJA = 0";
            $resultLineasPedido = $bd->ExecSQL($sqlLineasPedido);

            //VARIABLE PARA SABER SI ESTABA ENVIADO A SAP
            $pedidoEnviadoSap = 0;

            while ($rowLineaPedido = $bd->SigReg($resultLineasPedido)):

                //DOY DE BAJA LAS LINEAS DEL PEDIDO DE SERVICIOS
                $sqlUpdate = "UPDATE PEDIDO_ENTRADA_LINEA SET
                               INDICADOR_BORRADO = 'L'
                              WHERE ID_PEDIDO_ENTRADA_LINEA = $rowLineaPedido->ID_PEDIDO_ENTRADA_LINEA";
                $bd->ExecSQL($sqlUpdate);

                //GUARDO SI ESTABA ENVIADO A SAP
                $pedidoEnviadoSap = $rowLineaPedido->ENVIADO_SAP;

            endwhile;
            //FIN BUSCO LAS LINEAS DEL PEDIDO PARA MARCARLAS DE BAJA

            //DOY DE BAJA EL PEDIDO DE SERVICIOS
            $sqlUpdate = "UPDATE PEDIDO_ENTRADA SET
                          BAJA = 1
                          WHERE ID_PEDIDO_ENTRADA = $rowPedido->ID_PEDIDO_ENTRADA";
            $bd->ExecSQL($sqlUpdate);

            //DOY DE BAJA LAS LINEAS DE LA TABLA PEDIDO SERVICIO LINEA
            $sqlUpdate = "UPDATE PEDIDO_SERVICIO_LINEA SET
                          BAJA = 1
                          WHERE ID_PEDIDO_ENTRADA = $rowPedido->ID_PEDIDO_ENTRADA";
            $bd->ExecSQL($sqlUpdate);

            if ($rowOrdenTransporte->MODELO_TRANSPORTE == 'Primero'):
                //SI TIENE FACTURAS NO COMERCIALES, LAS DAMOS DE BAJA
                $sqlFacturas    = "SELECT ID_FACTURA_NO_COMERCIAL FROM FACTURA_NO_COMERCIAL WHERE NUMERO_FACTURA = '" . $rowPedido->PEDIDO_SAP . "' AND BAJA = 0 AND ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE";
                $resultFacturas = $bd->ExecSQL($sqlFacturas);

                if ($rowFacturas = $bd->SigReg($resultFacturas)):
                    //DOY DE BAJA EL PEDIDO DE SERVICIOS
                    $sqlUpdate = "UPDATE FACTURA_NO_COMERCIAL SET
                          BAJA = 1
                          WHERE ID_FACTURA_NO_COMERCIAL = $rowFacturas->ID_FACTURA_NO_COMERCIAL";
                    $bd->ExecSQL($sqlUpdate);

                    //DOY DE BAJA LAS LINEAS DE LA TABLA PEDIDO SERVICIO LINEA
                    $sqlUpdate = "UPDATE FACTURA_NO_COMERCIAL_MOVIMIENTO SET
                          BAJA = 1
                          WHERE ID_FACTURA_NO_COMERCIAL = $rowFacturas->ID_FACTURA_NO_COMERCIAL";
                    $bd->ExecSQL($sqlUpdate);
                endif;
                //FIN SI TIENE FACTURAS NO COMERCIALEs, LAS DAMOS DE BAJA
            endif;

            //SI HAY ALGUNO DE MODIFICACION DE IMPORTES, AL ANULARSE EL ZTL ACTUALIZAMOS EL IMPORTE PARA LA PROXIMA VEZ ENVIAR LA MODIFICACION DEL IMPORTE COMO PARTE DEL IMPORTE
            if (($rowPedido->TIPO_ZTLI == "Modificacion Importes") || ($rowPedido->TIPO_ZTLG == "Modificacion Importes") || ($rowPedido->TIPO_ZTLE == "Modificacion Importes")):
                //BUSCO LA ORDEN DE CONTRATACION
                $rowOrdenContratacion = $bd->VerReg("ORDEN_CONTRATACION", "ID_ORDEN_CONTRATACION", $rowPedido->ID_ORDEN_CONTRATACION);

                if (($rowPedido->TIPO_PEDIDO_SAP == 'ZTLI') || ($rowPedido->TIPO_PEDIDO_SAP == 'ZTLG')): //SI EL PEDIDO SAP ES ZTLI/ZTLG SUMO EL IMPORTE A ENVIAR LA PROXIMA VEZ
                    $sqlUpdate = "UPDATE ORDEN_CONTRATACION SET
                              IMPORTE = " . $auxiliar->formatoMoneda($rowOrdenContratacion->IMPORTE + $rowPedido->IMPORTE, $rowOrdenContratacion->ID_MONEDA) . "
                              WHERE ID_ORDEN_CONTRATACION = $rowPedido->ID_ORDEN_CONTRATACION";
                elseif (($rowPedido->TIPO_PEDIDO_SAP == 'ZTLF') || ($rowPedido->TIPO_PEDIDO_SAP == 'ZTLE')): //SI EL PEDIDO SAP ES ZTLF/ZTLE RESTO EL IMPORTE A ENVIAR LA PROXIMA VEZ
                    $sqlUpdate = "UPDATE ORDEN_CONTRATACION SET
                              IMPORTE = " . $auxiliar->formatoMoneda($rowOrdenContratacion->IMPORTE - $rowPedido->IMPORTE, $rowOrdenContratacion->ID_MONEDA) . "
                              WHERE ID_ORDEN_CONTRATACION = $rowPedido->ID_ORDEN_CONTRATACION";
                endif;
                $bd->ExecSQL($sqlUpdate);
            endif;

            //ENVIO A SAP EL PEDIDO TRANSPORTE ANULADO EN CASO DE QUE ESTE TRANSMITIDO
            if (($pedidoEnviadoSap == 1) && ($rowPedido->TIPO_PEDIDO_SAP != 'ZTLJ')):
                if (($rowPedido->TIPO_ZTLI == "Modificacion Importes") && ($rowPedido->TIPO_PEDIDO_SAP == "ZTLF")):
                    $resultado = $sap->InformarSAPZTLFFicticio($rowPedido->ID_PEDIDO_ENTRADA);
                elseif (($rowPedido->TIPO_ZTLE == "Modificacion Importes") && ($rowPedido->TIPO_PEDIDO_SAP == "ZTLE")):
                    $resultado = $sap->InformarSAPZTLEFicticio($rowPedido->ID_PEDIDO_ENTRADA);
                elseif ($rowPedido->TIPO_ZTLI == "Modificacion Importes" || $rowPedido->TIPO_ZTLI == "Transporte Gratuito"): //SI ES ZTLI FICTICIO LLAMAMOS A SU FUNCION
                    $resultado = $sap->InformarSAPZTLIFicticio($rowPedido->ID_PEDIDO_ENTRADA);
                else:
                    $resultado = $sap->InformarSAPPedidoServicios($rowPedido->ID_PEDIDO_ENTRADA);
                endif;

                if ($resultado['RESULTADO'] != 'OK'): //No se ha podido grabar la información en SAP
                    foreach ($resultado['ERRORES'] as $arr):
                        foreach ($arr as $mensaje_error):
                            $strError = $mensaje_error . "<br>";
                        endforeach;
                    endforeach;

                    //DESHAGO LA TRANSACCION
                    $bd->rollback_transaction();

                    //GRABO EL ERROR PRODUCIDO EN BASE DE DATOS
                    $sap->InsertarErrores($resultado);

                    //MARCO QUE HAY ERROR EN LA TRANSMISION A SAP
                    $errorPedido = true;
                else://SI HA IDO BIEN
                    //BUSCO LAS LINEAS DEL PEDIDO PARA LIMPIAR LOS DATOS
                    $sqlLineasPedido    = "SELECT *
                                        FROM PEDIDO_ENTRADA_LINEA
                                        WHERE ID_PEDIDO_ENTRADA = $rowPedido->ID_PEDIDO_ENTRADA AND BAJA = 0";
                    $resultLineasPedido = $bd->ExecSQL($sqlLineasPedido);

                    while ($rowLineaPedido = $bd->SigReg($resultLineasPedido)):
                        //DOY DE BAJA LAS LINEAS DEL PEDIDO DE SERVICIOS
                        $sqlUpdate = "UPDATE PEDIDO_ENTRADA_LINEA SET
                                  ID_ORDEN_TRABAJO_RELACIONADO = NULL
                                  , ID_MOVIMIENTO_SALIDA_LINEA_RELACIONADA_MUEVE_MATERIAL = NULL
                                  , ID_PEDIDO_ENTRADA_LINEA_RELACIONADA_RECEPCIONA_MATERIAL = NULL 
                                  , ID_ELEMENTO_IMPUTACION = NULL
                                  , CODIGO_SELECCIONADO_ELEMENTO_IMPUTACION = ''
                                  , BAJA = 1
                                  , ENVIADO_SAP = 1
                                  WHERE ID_PEDIDO_ENTRADA_LINEA = $rowLineaPedido->ID_PEDIDO_ENTRADA_LINEA";
                        $bd->ExecSQL($sqlUpdate);

                    endwhile;
                    //FIN BUSCO LAS LINEAS DEL PEDIDO PARA MARCARLAS DE BAJA
                endif;
            else:
                //BUSCO LAS LINEAS DEL PEDIDO PARA LIMPIAR LOS DATOS
                $sqlLineasPedido    = "SELECT *
                                FROM PEDIDO_ENTRADA_LINEA
                                WHERE ID_PEDIDO_ENTRADA = $rowPedido->ID_PEDIDO_ENTRADA AND BAJA = 0";
                $resultLineasPedido = $bd->ExecSQL($sqlLineasPedido);

                while ($rowLineaPedido = $bd->SigReg($resultLineasPedido)):
                    //DOY DE BAJA LAS LINEAS DEL PEDIDO DE SERVICIOS
                    $sqlUpdate = "UPDATE PEDIDO_ENTRADA_LINEA SET
                                      ID_ORDEN_TRABAJO_RELACIONADO = NULL
                                      , ID_MOVIMIENTO_SALIDA_LINEA_RELACIONADA_MUEVE_MATERIAL = NULL
                                      , ID_PEDIDO_ENTRADA_LINEA_RELACIONADA_RECEPCIONA_MATERIAL = NULL 
                                      , ID_ELEMENTO_IMPUTACION = NULL
                                      , CODIGO_SELECCIONADO_ELEMENTO_IMPUTACION = ''
                                      , BAJA = 1
                                      WHERE ID_PEDIDO_ENTRADA_LINEA = $rowLineaPedido->ID_PEDIDO_ENTRADA_LINEA";
                    $bd->ExecSQL($sqlUpdate);
                endwhile;
                //FIN BUSCO LAS LINEAS DEL PEDIDO PARA MARCARLAS DE BAJA

            endif;//FIN ENVIAR A SAP ANULACION DE PEDIDOS TRANSMITIDOS

            //FINALIZO LA TRANSACCION
            $bd->commit_transaction();

        endwhile;
        //FIN RECORRO LOS PEDIDOS PARA TRANSMITIRLOS A SAP

        //SI EL ESTADO ERA FINALIZADA, ES PORQUE SOLO HAY GASTOS ZTL Y MARCAMOS LAS CONTRATACIONES COMO NO ENVIADAS A SAP
        if (($rowOrdenTransporte->MODELO_TRANSPORTE == 'Primero') && ($rowOrdenTransporte->ESTADO_INTERFACES == 'Finalizada')):
            $sqlUpdate = "UPDATE ORDEN_CONTRATACION SET ENVIADO_SAP = 0 WHERE ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND BAJA = 0";
            $bd->ExecSQL($sqlUpdate);

            //ADEMAS DAMOS DE BAJA LAS LINEAS QUE ESTABAMOS MOSTRANDO EN ROJO POR HABERSE ANULADO CON EL TRANSPORTE FINALIZADO
            $sqlUpdate = "UPDATE ORDEN_TRANSPORTE_LINEA_ANULADA SET BAJA = 1 WHERE ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE";
            $bd->ExecSQL($sqlUpdate);
        endif;

        //CALCULO EL ESTADO SEGUN EL NUMERO DE PEDIDOS ANULADOS
        $numPedidos     = $bd->NumRegsTabla("PEDIDO_ENTRADA", "ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE  AND BAJA = 0");
        $numPedidosBaja = $bd->NumRegsTabla("PEDIDO_ENTRADA", "ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE  AND BAJA = 1");

        //SI TODOS ESTAN DADOS DE BAJA, VOLVEMOS AL ESTADO ANTERIOR (Recogidas Transmitidas)
        if ($numPedidos == 0):

            //BUSCO QUE LA ORDEN TRANSPORTE NO TENGA RECOGIDAS DE OTRO TIPO QUE NO SEA SIN PEDIDO CONOCIDO (sera un transporte sin pedido conocido)
            $numRecogidasSinPedidoConocido = $bd->NumRegsTabla("EXPEDICION", "BAJA=0 AND ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND SUBTIPO_ORDEN_RECOGIDA = 'Sin Pedido Conocido'");

            //SI ES MODELO DE TRANSPORTE 'Segundo' Y SON RECOGIDAS EN PROVEEDOR SIN PEDIDO CONOCIDO DEJAMOS LA ORDEN DE TRANSPORTE COMO CREADA
            if (($rowOrdenTransporte->MODELO_TRANSPORTE == 'Segundo') && ($numRecogidasSinPedidoConocido > 0)):
                $estadoOrdenTransporte = 'Creada';
            else:
                $estadoOrdenTransporte = 'Recogidas Transmitidas';
            endif;

            //ACTUALIZO EL ESTADO DE LA ORDEN DE TRANSPORTE
            $sqlUpdate = "UPDATE ORDEN_TRANSPORTE SET ESTADO_INTERFACES = '" . $estadoOrdenTransporte . "' WHERE ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE";
            $bd->ExecSQL($sqlUpdate);

            //ADEMAS, MARCAMOS DESMARCAMOS LAS RECOGIDAS QUE NO GESTIONAN FACTURAS
            $sqlUpdate = "UPDATE EXPEDICION
                      SET GESTIONA_FACTURAS = 0
                      WHERE ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE ";
            $bd->ExecSQL($sqlUpdate);

        elseif ($numPedidosBaja > 0): //SI YA ALGUNO SE HA DADO DE BAJA, LO PONEMOS EN TRANSMISION

            $sqlUpdate = "UPDATE ORDEN_TRANSPORTE SET ESTADO_INTERFACES = 'ZTL en Transmision' WHERE ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE";
            $bd->ExecSQL($sqlUpdate);

        endif;//SI NO HAY NINGUNO DE BAJA DEJAMOS EL ESTADO COMO ESTABA

        //EN EL MODELO SEGUNDO LAS RECOGIDAS DE OPERACIONES EN PARQUE Y FUERA SISTEMA LAS PASO A ESTADO 'Transmitida a SAP'
        if (($rowOrdenTransporte->MODELO_TRANSPORTE == 'Segundo') && ($errorPedido == false)):
            $sqlUpdate = "UPDATE EXPEDICION SET 
                        ESTADO = 'Transmitida a SAP' 
                        WHERE ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND TIPO_ORDEN_RECOGIDA IN ('Operaciones en Parque', 'Operaciones fuera de Sistema') AND BAJA = 0";
            $bd->ExecSQL($sqlUpdate);
        endif;
        //FIN EN EL MODELO SEGUNDO LAS RECOGIDAS DE OPERACIONES EN PARQUE Y FUERA SISTEMA LAS PASO A ESTADO 'Recepcionada'

        //ACTUALIZAMOS USUARIO DE ULTIMA MODIFICACION
        $sqlUpdate = "UPDATE ORDEN_TRANSPORTE SET
                    ID_ADMINISTRADOR_ULTIMA_MODIFICACION = " . $administrador->ID_ADMINISTRADOR . "
                    , FECHA_ULTIMA_MODIFICACION = '" . date('Y-m-d H:i:s') . "'
                    WHERE ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE";
        $bd->ExecSQL($sqlUpdate);

        //ACTUALIZAMOS EL ESTADO DEL TRANSPORTE
        $this->actualizarEstadoOrdenTransporte($rowOrdenTransporte->ID_ORDEN_TRANSPORTE);

        //SI SE HA PRODUCIDO ALGUN ERROR EN LA TRANSMISION A SAP LO MUESTRO
        if ($errorPedido == true):
            $html->PagError("ErrorSAP");
        endif;

    }

    function AnularTranmitirRecogidasASAP($idOrdenTransporte)
    {
        global $html;
        global $bd;
        global $exp_SAP;
        global $auxiliar;
        global $administrador;
        global $expedicion;
        global $sap;
        global $pedido;

        //GENERO UN ARRAY PARA ALMACENAR LA INFORMACION DE LA TRANSMISION A SAP DE LA ORDEN DE TRANSPORTE
        $arr = array();

        //COMPRUEBO QUE LA ORDEN DE TRANSPORTE VENGA RELLENA
        $html->PagErrorCondicionado($idOrdenTransporte, "==", "", "FaltaOrdenTransporte");

        //BUSCO LA ORDEN DE TRANSPORTE
        $rowOrdenTransporte = $bd->VerReg("ORDEN_TRANSPORTE", "ID_ORDEN_TRANSPORTE", $idOrdenTransporte);

        //COMPRUEBO QUE LA ORDEN DE TRANSPORTE NO ESTE DADA DE BAJA
        $html->PagErrorCondicionado($rowOrdenTransporte->BAJA, "!=", 0, "OrdenTransporteBaja");

        //COMPROBAMOS QUE EL ESTADO SEA CORRECTO
        if (($rowOrdenTransporte->ESTADO_INTERFACES != "Recogidas Transmitidas") && ($rowOrdenTransporte->ESTADO_INTERFACES != "Recogidas en Transmision")):
            $html->PagError("EstadoOTIncorrecto");
        endif;

        //BUSCO EL NUMERO DE RECOGIDAS CON ESTADO MAS AVANZADO DE TRANMITIDO A SAP
        $numRecogidasEstadoAvanzado = $bd->NumRegsTabla("EXPEDICION", "BAJA=0 AND ID_ORDEN_TRANSPORTE=$rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND (ESTADO<>'Creada' AND ESTADO<>'En Transmision' AND ESTADO<>'Transmitida a SAP') AND TIPO_ORDEN_RECOGIDA <> 'Operaciones fuera de Sistema'");

        //COMPRUEBO QUE LA ORDEN DE TRANSPORTE NO TENGA RECOGIDAS EN ESTADO AVANZADO
        $html->PagErrorCondicionado($numRecogidasEstadoAvanzado, "!=", 0, "EstadoRecogidasAvanzado");

        //BUSCO LAS RECOGIDAS SIN TRANSMITIR ASOCIADAS A LA ORDEN DE TRANSPORTE
        $sqlExpedicion                  = "SELECT * FROM EXPEDICION WHERE ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND TIPO_ORDEN_RECOGIDA = 'Recogida en Almacen' AND (ESTADO = 'Transmitida a SAP' OR ESTADO = 'En Transmision') AND BAJA = 0 ";
        $resultExpedicion               = $bd->ExecSQL($sqlExpedicion);
        $numRecogidasAlmacenATransmitir = $bd->NumRegs($resultExpedicion);

        //RECORREMOS LAS RECOGIDAS A TRANSMITIR A SAP
        while ($rowExpedicion = $bd->SigReg($resultExpedicion)):

            //ARRAY PARA ALMACENAR LAS EXPEDICIONES SAP A CANCELAR
            $arrExpedicionesSAP = array();

            //GENERO UN ARRAY CON LAS EXPEDICIONES SAP A CANCELAR
            $sqlExpedicionesSAP    = "SELECT DISTINCT(MSL.EXPEDICION_SAP)
                                    FROM MOVIMIENTO_SALIDA_LINEA MSL
                                    WHERE MSL.ID_EXPEDICION = $rowExpedicion->ID_EXPEDICION AND MSL.ESTADO = 'Transmitido a SAP' AND MSL.LINEA_ANULADA = 0 AND MSL.BAJA = 0";
            $resultExpedicionesSAP = $bd->ExecSQL($sqlExpedicionesSAP);
            while ($rowExpedicionSAP = $bd->SigReg($resultExpedicionesSAP)):
                $arrExpedicionesSAP[] = $rowExpedicionSAP->EXPEDICION_SAP;
            endwhile;

            /******************************************** ACCIONES CANCELACION TRANSMITIR EXPEDICION SAP A SAP ********************************************/
            //SI EXISTEN EXPEDICIONES SAP, LAS TRANSMITIMOS
            if (count((array)$arrExpedicionesSAP) > 0):
                //RECORRO LAS EXPEDICIONES SAP
                foreach ($arrExpedicionesSAP as $expedicionSAP):

                    //CALCULO EL ID DE PEDIDO_SALIDA
                    $idPedidoSalida = $exp_SAP->getIdPedidoSalidaSGA($rowExpedicion->VERSION, $expedicionSAP);

                    //EXTRAIGO LOS VALORES DE LA EXPEDICION SAP
                    $arrValoresExpedicionSAP = explode("_", trim((string)$expedicionSAP));

                    //SI EL ID DE PEDIDO_SALIDA ES NULO ERROR
                    if ($idPedidoSalida == NULL):
                        $arr['errores'] = $arr['errores'] . $auxiliar->traduce("La expedición SAP introducida es incorrecta", $administrador->ID_IDIOMA) . ".<br>";
                        continue; //CONTINUO CON LA SIGUIENTE EXPEDICION SAP
                    endif;

                    //CALCULO LOS DATOS DEL PEDIDO
                    $rowPedSal = $bd->VerReg("PEDIDO_SALIDA", "ID_PEDIDO_SALIDA", $idPedidoSalida, "No");
                    if ($rowPedSal == false):
                        $arr['errores'] = $arr['errores'] . $auxiliar->traduce("La expedición SAP introducida no existe", $administrador->ID_IDIOMA) . ".<br>";
                        continue; //CONTINUO CON LA SIGUIENTE EXPEDICION SAP
                    endif;


                    //INICIO UNA TRANSACCION POR EXPEDICION SAP
                    $bd->begin_transaction();


                    //TRANSMITO LA CANCELACION DE LA EXPEDICION SAP A SAP
                    $arrDevueltoCancelarExpedicionSAPASAP = $expedicion->cancelar_transmision_expedicionSAP_a_SAP($rowExpedicion->VERSION, $expedicionSAP);

                    //SI SE HA PRODUCIDO UN ERROR CON LA EXPEDICION SAP O SU TRANSMISION DESHACEMOS LA TRANSACCION
                    if (($arrDevueltoCancelarExpedicionSAPASAP['error_cancelacion_expedidion_SAP'] == true) || ($arrDevueltoCancelarExpedicionSAPASAP['error_transmision_cancelacion_expedidion_SAP'] == true)):
                        //DESHAGO LA TRANSACCION POR EXPEDICION SAP
                        $bd->rollback_transaction();

                        //SI HA HABIDO UN ERROR EN LA TRANSMISION A SAP, GRABO EL LOG DE ERRRORES
                        if ($arrDevueltoCancelarExpedicionSAPASAP['error_transmision_cancelacion_expedidion_SAP'] == true):
                            //GRABO EL ERROR PRODUCIDO EN BASE DE DATOS
                            $sap->InsertarErrores($arrDevueltoCancelarExpedicionSAPASAP['resultado']);
                        endif;

                        //ME GUARDO LOS ERRORES
                        if ($arrDevueltoCancelarExpedicionSAPASAP['error_cancelacion_expedidion_SAP'] == true):
                            $arr['errores'] = $arr['errores'] . "<strong>" . $auxiliar->traduce("Se han producido los siguientes errores al transmitir la cancelacion de la expedicion SAP", $administrador->ID_IDIOMA) . " " . $expedicionSAP . ": </strong><br>" . $arrDevueltoCancelarExpedicionSAPASAP['errores'] . "<br>";
                        elseif ($arrDevueltoCancelarExpedicionSAPASAP['error_transmision_cancelacion_expedidion_SAP'] == true):
                            $arr['errores'] = $arr['errores'] . $arrDevueltoCancelarExpedicionSAPASAP['errores'] . "<br>";
                        endif;
                    endif;


                    //FINALIZO UNA TRANSACCION POR EXPEDICION SAP
                    $bd->commit_transaction();


                    //SI EL PEDIDO ES DE PREVENTIVO Y LA TRANSMISICON DE LA ANULACION DE EXPEDICION SAP HA IDO CORRECTAMENTE, CONVIERTO EL MATERIAL DE LA EXPEDICION SAP PREVIAMENTE A PREVENTIVO
                    $rowTipoBloqueoPreventivo = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "SP");
                    if (
                        ($rowPedSal->ID_TIPO_BLOQUEO == $rowTipoBloqueoPreventivo->ID_TIPO_BLOQUEO) &&
                        ($arrDevueltoCancelarExpedicionSAPASAP['error_cancelacion_expedidion_SAP'] == false) &&
                        ($arrDevueltoCancelarExpedicionSAPASAP['error_transmision_cancelacion_expedidion_SAP'] == false)
                    ):
                        //SI EXISTE UN CAMBIO DE ESTADO CON ESTA EXPEDICION SAP, LO DESHAGO
                        $num = $bd->NumRegsTabla("CAMBIO_ESTADO_GRUPO", "EXPEDICION_SAP = '" . $expedicionSAP . "'");
                        if ($num > 0):


                            //INICIO UNA TRANSACCION POR CAMBIO ESTADO DE PREVENTIVO A OK DE EXPEDICION SAP
                            $bd->begin_transaction();


                            //GENERO LA EXPEDICION SAP CON IDENTIFICADORES QUE ES LO QUE ESPERA LA FUNCION
                            $expedicionSAPConIdentificadores = $exp_SAP->getExpedicionSAPConIdentificadores($rowExpedicion->VERSION, $expedicionSAP);


                            //SI NO SE HAN PRODUCIDO ERRORES, MODIFICO EL TIPO BLOQUEO DE LAS LINEAS DE MOVIMIENTO DE LA EXPEDICION SAP A LIBRE PARA QUE EL MATERIAL QUEDE PREPARADO COMO CORRECTIVO
                            if ($rowExpedicion->VERSION == 'Tercera'):
                                $sqlUpdate = "UPDATE MOVIMIENTO_SALIDA_LINEA SET
                                        ID_TIPO_BLOQUEO = NULL
                                        WHERE ID_EXPEDICION = $rowExpedicion->ID_EXPEDICION AND ID_PEDIDO_SALIDA = $rowPedSal->ID_PEDIDO_SALIDA AND " . ($rowExpedicion->CON_BULTOS == 0 ? 'ID_MOVIMIENTO_SALIDA = ' : 'ID_BULTO = ') . " $arrValoresExpedicionSAP[2] AND LINEA_ANULADA = 0 AND BAJA = 0";
                                $bd->ExecSQL($sqlUpdate);
                            elseif ($rowExpedicion->VERSION == 'Cuarta'):
                                //BUSCO EL REGISTRO EXPEDICION SAP
                                $rowExpedicionSAP = $bd->VerReg("EXPEDICION_SAP", "ID_EXPEDICION_SAP", $exp_SAP->getIdExpedicionSAP('Cuarta', $expedicionSAP));
                                $sqlUpdate        = "UPDATE MOVIMIENTO_SALIDA_LINEA SET
                                                    ID_TIPO_BLOQUEO = NULL
                                                    WHERE ID_EXPEDICION = $rowExpedicion->ID_EXPEDICION AND ID_PEDIDO_SALIDA = $rowPedSal->ID_PEDIDO_SALIDA AND " . ($rowExpedicion->CON_BULTOS == 0 ? 'ID_MOVIMIENTO_SALIDA = ' . $rowExpedicionSAP->ID_MOVIMIENTO_SALIDA : 'ID_BULTO = ' . $rowExpedicionSAP->ID_BULTO) . " AND LINEA_ANULADA = 0 AND BAJA = 0";
                                $bd->ExecSQL($sqlUpdate);
                            endif;


                            //CREO EL CAMBIO DE ESTADO GRUPO Y LA LLAMADA CORRESPONDIENTE
                            //LA CANCELACION DE LA EXPEDICION DE LA ORDEN DE TRANSPORTE PONE EL MATERIAL EN SM, POR LO QUE EL CAMBIO DE ESTADO ES EN LA UBICACION DE SALIDA
                            $arrDevueltoCambioEstadoExpedicionSAP = $expedicion->generar_cambio_estado_expedicionSAP($rowExpedicion->VERSION, $expedicionSAPConIdentificadores, $rowTipoBloqueoPreventivo->ID_TIPO_BLOQUEO, NULL, 'Salida');

                            //SI SE HA PRODUCIDO UN ERROR CON SU TRANSMISION DESHACEMOS LA TRANSACCION
                            if ($arrDevueltoCambioEstadoExpedicionSAP['error_cambio_estado_expedidion_SAP'] == true):
                                //DESHAGO LA TRANSACCION POR EXPEDICION SAP
                                $bd->rollback_transaction();

                                //ME GUARDO LOS ERRORES
                                if ($arrDevueltoCambioEstadoExpedicionSAP['error_cambio_estado_expedidion_SAP'] == true):
                                    $arr['errores'] = $arr['errores'] . "<strong>" . $auxiliar->traduce("Se han producido los siguientes errores al realizar el cambio de estado de preventivo a libre de la expedicion SAP", $administrador->ID_IDIOMA) . " " . $expedicionSAP . ": </strong><br>" . $arrDevueltoCambioEstadoExpedicionSAP['errores'] . "<br>";
                                endif;

                                //GRABO EL ERROR PRODUCIDO EN BASE DE DATOS
                                $sap->InsertarErrores($arrDevueltoCambioEstadoExpedicionSAP['resultado']);

                                //PONGO EL CAMBIO DE ESTADO ORIGINAL (OK -> P) PENDIENTE DE REVERTIR
                                $sqlUpdate = "UPDATE CAMBIO_ESTADO_GRUPO SET
                                          PENDIENTE_REVERTIR = 1
                                          WHERE ID_EXPEDICION = $rowExpedicion->ID_EXPEDICION AND EXPEDICION_SAP = '" . $expedicionSAP . "'";
                                $bd->ExecSQL($sqlUpdate);

                                //EMAIL CORRESPONDIENTE DE NOTIFICACION
                                $mailEsp           = new PHPMailer();
                                $mailEsp->From     = CAMBIOS_ESTADO_REMITENTE_EMAIL;
                                $mailEsp->FromName = CAMBIOS_ESTADO_REMITENTE_NOMBRE;
                                $mailEsp->Mailer   = "mail";
                                $mailEsp->Body     = $auxiliar->traduce("No se han podido revertir los cambios de estado (de preventivo a libre) generados por la anulacion de la transmision a SAP, sera necesario anularlos manualmente desde la ficha de la orden de transporte.", $administrador->ID_IDIOMA) . " " . $auxiliar->traduce("Orden de transporte", $administrador->ID_IDIOMA) . ": " . $rowExpedicion->ID_EXPEDICION . "<br>" . $auxiliar->traduce("Errores producidos", $administrador->ID_IDIOMA) . ":" . $arrDevueltoCambioEstadoExpedicionSAP['errores'];
                                $mailEsp->Subject  = $auxiliar->traduce("Se ha producido un error al anular la transmision a SAP de la orden de transporte", $administrador->ID_IDIOMA) . ": " . $rowExpedicion->ID_EXPEDICION;
                                $mailEsp->IsHTML(true);
                                $mailEsp->ClearAllRecipients();
                                $mailEsp->AddAddress(CAMBIOS_ESTADO_EMAIL_DESTINATARIO);
                                $mailEsp->Sender = CAMBIOS_ESTADO_REMITENTE_EMAIL;
                                $mailEsp->Send();

                                //SI HAY ERRORES CONTINUO CON LA SIGUIENTE EXPEDICION SAP
                                continue;
                            endif;

                            //FINALIZO UNA TRANSACCION POR CAMBIO ESTADO DE OK A PREVENTIVO DE EXPEDICION SAP
                            $bd->commit_transaction();

                            //INICIO UNA TRANSACCION POR CAMBIO ESTADO DE PREVENTIVO A OK DE EXPEDICION SAP
                            $bd->begin_transaction();


                            //MARCO EL CAMBIO DE ESTADO COMO REVERTIDO
                            $idCambioEstadoGrupoGenerado = $arrDevueltoCambioEstadoExpedicionSAP['idCambioEstadoGrupo'];
                            $sqlUpdate                   = "UPDATE CAMBIO_ESTADO_GRUPO SET
                                                        PENDIENTE_REVERTIR = 0
                                                        WHERE ID_CAMBIO_ESTADO_GRUPO = $idCambioEstadoGrupoGenerado";
                            $bd->ExecSQL($sqlUpdate);


                            //FINALIZO UNA TRANSACCION POR CAMBIO ESTADO DE OK A PREVENTIVO DE EXPEDICION SAP
                            $bd->commit_transaction();


                        endif;
                        //FIN SI EXISTE UN CAMBIO DE ESTADO CON ESTA EXPEDICION SAP, LO DESHAGO
                    endif;
                    //FIN SI EL PEDIDO ES DE PREVENTIVO, CONVIERTO EL MATERIAL DE LA EXPEDICION SAP PREVIAMENTE A PREVENTIVO


                    //EXTRAIGO LAS LINEAS DE PEDIDO INVOLUCRADAS, DEBEREMOS ENVIAR EL BLOQUEO/DESBLOQUEO DE ESTAS A SAP
                    if ($arrDevueltoCancelarExpedicionSAPASAP['lista_lineas_pedido'] != ""):
                        if ((!(isset($arr['lista_lineas_pedido']))) || ($arr['lista_lineas_pedido'] == "")):
                            $arr['lista_lineas_pedido'] = implode(",", (array)$arrDevueltoCancelarExpedicionSAPASAP['lista_lineas_pedido']);
                        else:
                            $arr['lista_lineas_pedido'] = $arr['lista_lineas_pedido'] . "," . implode(",", (array)$arrDevueltoCancelarExpedicionSAPASAP['lista_lineas_pedido']);
                        endif;
                    endif;

                endforeach;
                //FIN RECORRO LAS EXPEDICIONES SAP
            endif;
            //FIN SI EXISTEN EXPEDICIONES SAP, LAS TRANSMITIMOS
            /****************************************** FIN ACCIONES CANCELACION TRANSMITIR EXPEDICION SAP A SAP ******************************************/

            /********************************************** ACCIONES INFORMACION BLOQUEO PEDIDOS **********************************************/
            //INICIO LA TRANSACCION PARA ENVIAR EL BLOQUEO/DESBLOQUEO DE LAS LINEAS IMPLICADAS
            $bd->begin_transaction();


            //INFORMO A SAP DE LAS LINEAS BLOQUEADAS
            if ((isset($arr['lista_lineas_pedido'])) && ($arr['lista_lineas_pedido'] != "")):
                $arrayLineasPedidosInvolucradas = explode(",", (string)$arr['lista_lineas_pedido']);
                $arrayLineasPedidosInvolucradas = array_unique((array)$arrayLineasPedidosInvolucradas);
                $resultado                      = $pedido->controlBloqueoLinea("Salida", 'AnularExpedicionSAP', implode(",", (array)$arrayLineasPedidosInvolucradas));
                if ($resultado['RESULTADO'] != 'OK'): //No se ha podido grabar la información en SAP
                    if (count((array)$resultado['ERRORES']) > 0):
                        foreach ($resultado['ERRORES'] as $arrayDeErrores):
                            foreach ($arrayDeErrores as $mensaje_error):
                                $arr['errores'] = $arr['errores'] . $mensaje_error . "<br>";
                            endforeach;
                        endforeach;
                    endif;

                    //DESHAGO LA TRANSACCION
                    $bd->rollback_transaction();

                    //GRABO EL ERROR PRODUCIDO EN BASE DE DATOS
                    $sap->InsertarErrores($resultado);
                endif;
            endif;


            //FINALIZO LA TRANSACCION PARA ENVIAR EL BLOQUEO/DESBLOQUEO DE LAS LINEAS IMPLICADAS
            $bd->commit_transaction();
            /******************************************** FIN ACCIONES INFORMACION BLOQUEO PEDIDOS ********************************************/

        endwhile; //FIN RECOGIDAS TRANSMITIDAS

        /********************************************* ANULAR FACTURAS COMERCIALES/NO COMERCIALES *******************************************/
        $bd->begin_transaction();

        //SE COMPRUEBA SI EXISTEN FACTURAS COMERCIALES/NO COMERCIALES PARA EL TRANSPORTE
        //COMERCIALES
        $sqlFacturasComercialesOT    = "SELECT DISTINCT ID_FACTURA_COMERCIAL
                                 FROM FACTURA_COMERCIAL
                                 WHERE ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND BAJA = 0";
        $resultFacturasComercialesOT = $bd->ExecSQL($sqlFacturasComercialesOT);
        while ($rowFacturaComercialOT = $bd->SigReg($resultFacturasComercialesOT)):
            $sqlUpdateFC = "UPDATE FACTURA_COMERCIAL
                        SET BAJA = 1
                        WHERE ID_FACTURA_COMERCIAL = $rowFacturaComercialOT->ID_FACTURA_COMERCIAL";
            $bd->ExecSQL($sqlUpdateFC);
            $sqlFCM    = "SELECT ID_FACTURA_COMERCIAL_MOVIMIENTO
                   FROM FACTURA_COMERCIAL_MOVIMIENTO
                   WHERE ID_FACTURA_COMERCIAL = $rowFacturaComercialOT->ID_FACTURA_COMERCIAL";
            $resultFCM = $bd->ExecSQL($sqlFCM);
            while ($rowFCM = $bd->SigReg($resultFCM)):
                $sqlUpdateFCM = "UPDATE FACTURA_COMERCIAL_MOVIMIENTO
                             SET BAJA = 1
                             WHERE ID_FACTURA_COMERCIAL_MOVIMIENTO = $rowFCM->ID_FACTURA_COMERCIAL_MOVIMIENTO";
                $bd->ExecSQL($sqlUpdateFCM);
            endwhile;
        endwhile;

        //NO COMERCIALES
        $auxiliar->eliminarFacturasNoComerciales($rowOrdenTransporte->ID_ORDEN_TRANSPORTE);

        $bd->commit_transaction();
        /****************************************** FIN ANULAR FACTURAS COMERCIALES/NO COMERCIALES ******************************************/

        //ACTUALIZAMOS EL ESTADO DE LA ORDEN DE TRANSPORTE
        //INICIO UNA TRANSACCION
        $bd->begin_transaction();


        //OBTENGO EL ESTADO FINAL DE LA INTERFACE(Si hay recogidas en Almacén, Lo calculamos segun el estado de éstas, si no , lo ponemos a Transmitido)
        if ($numRecogidasAlmacenATransmitir > 0):
            $estadoFinalInterfaces = $this->EstadoTransporteSegunRecogidas($rowOrdenTransporte->ID_ORDEN_TRANSPORTE);
        else:
            $estadoFinalInterfaces = 'Creada';
        endif;

        //SI HAY PEDIDOS ZTL NO TRANSMITIDOS LOS ANULAMOS
        $this->anularPedidosZTLNoTransmitidos($rowOrdenTransporte->ID_ORDEN_TRANSPORTE);

        //RETROCEDEMOS EL ESTADO DE LAS RECOGIDAS DISTINTAS DE EN ALMACEN
        $sqlUpdate = "UPDATE EXPEDICION SET ESTADO = 'Creada' WHERE ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND TIPO_ORDEN_RECOGIDA <> 'Recogida en Almacen'";
        $bd->ExecSQL($sqlUpdate);

        //ACTUALIZAMOS EL ESTADO DE LAS INTERFACES DEL TRANSPORTE
        $sqlUpdate = "UPDATE ORDEN_TRANSPORTE SET
                              ESTADO_INTERFACES = '$estadoFinalInterfaces'
                            , ID_ADMINISTRADOR_ULTIMA_MODIFICACION = " . $administrador->ID_ADMINISTRADOR . "
                            , FECHA_ULTIMA_MODIFICACION = '" . date('Y-m-d H:i:s') . "'
                        WHERE ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE";
        $bd->ExecSQL($sqlUpdate);

        //ACTUALIZAMOS EL ESTADO DEL TRANSPORTE
        $this->actualizarEstadoOrdenTransporte($rowOrdenTransporte->ID_ORDEN_TRANSPORTE);

        // LOG MOVIMIENTOS
        $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Orden de Transporte", $rowOrdenTransporte->ID_ORDEN_TRANSPORTE, "Anular Transmision Recogidas a SAP");


        //FINALIZO TRANSACCION
        $bd->commit_transaction();

        return $arr;

    }

    function TransmitirRecogidasASAP($idExpedicion)
    {
        global $html;
        global $bd;
        global $importe;
        global $expedicion;
        global $auxiliar;
        global $sap;
        global $exp_SAP;
        global $pedido;
        global $administrador;

        //GENERO UN ARRAY PARA ALMACENAR LA INFORMACION DE LA TRANSMISION A SAP DE LA ORDEN DE TRANSPORTE
        $arr = array();

        //COMPRUEBO QUE LA ORDEN DE TRANSPORTE VENGA RELLENA
        $html->PagErrorCondicionado($idExpedicion, "==", "", "FaltaOrdenRecogida");

        $rowExpedicion = $bd->VerReg("EXPEDICION", "ID_EXPEDICION", $idExpedicion);

        //SI TIENE ORDEN DE TRANSPORTE COMPRUEBO QUE SEA SIN GASTOS
        if ($rowExpedicion->ID_ORDEN_TRANSPORTE != ""):

            //COMPRUEBO QUE LA ORDEN DE TRANSPORTE NO TIENE GASTOS
            $rowOrdenTransporte = $bd->VerReg("ORDEN_TRANSPORTE", "ID_ORDEN_TRANSPORTE", $rowExpedicion->ID_ORDEN_TRANSPORTE, "No");
            $html->PagErrorCondicionado($rowOrdenTransporte->TIENE_GASTOS_TRANSPORTE, "==", 1, "OrdenTransporteConGastosDesdeTransporte");

            //SI LA OT ES DEL MODELO NUEVO, NO SE PUEDE ASIGNAR UNA RECOGIDA CON PEDIDOS RELEVANTES PARA ENTREGA ENTRANTE
            if ($rowOrdenTransporte->MODELO_TRANSPORTE == 'Segundo'):
                $sqlCuantosREE    = "SELECT COUNT(PSL.ID_PEDIDO_SALIDA_LINEA) AS NUM_REE
                                     FROM PEDIDO_SALIDA_LINEA PSL
                                     INNER JOIN MOVIMIENTO_SALIDA_LINEA MSL ON MSL.ID_PEDIDO_SALIDA_LINEA = PSL.ID_PEDIDO_SALIDA_LINEA
                                     WHERE MSL.ID_EXPEDICION = $rowExpedicion->ID_EXPEDICION AND PSL.RELEVANTE_ENTREGA_ENTRANTE = 1 AND PSL.BAJA = 0 AND MSL.BAJA = 0 AND MSL.LINEA_ANULADA = 0";
                $resultCuantosREE = $bd->ExecSQL($sqlCuantosREE);
                $rowCuantosREE    = $bd->SigReg($resultCuantosREE);
                $html->PagErrorCondicionado($rowCuantosREE->NUM_REE, ">", 0, "RecogidaRelevanteEntregaEntranteParaOrdenTransporteModeloNuevo");
            endif;

        else: //SI NO TIENE ORDEN DE TRANSPORTE, SE CREA SIN GASTOS DE TRANSPORTE Y LA ASOCIO A LA RECOGIDA

            //SI TIENE SOLICITUD NO DEJO CONTINUAR; SE LE ASOCIARA EL TRANSPORTE DESDE LA SOLICITUD
            $html->PagErrorCondicionado($rowExpedicion->ID_SOLICITUD_TRANSPORTE, "!=", NULL, "RecogidaConSolicitudNoNuevoTransporte");

            //INICIO LA TRANSACCION
            $bd->begin_transaction();

            $sqlInsert = "INSERT INTO ORDEN_TRANSPORTE SET
                            ESTADO = 'Creada'
                            , TIENE_GASTOS_TRANSPORTE = 0
                            , PESO = $rowExpedicion->PESO
                            , ID_ADMINISTRADOR_CREACION='" . $administrador->ID_ADMINISTRADOR . "'
                            , FECHA_CREACION='" . date('Y-m-d H:i:s') . "'
                            , ID_ADMINISTRADOR_ULTIMA_MODIFICACION='" . $administrador->ID_ADMINISTRADOR . "'
                            , FECHA_ULTIMA_MODIFICACION='" . date('Y-m-d H:i:s') . "'
                            , ID_TRANSPORTISTA = " . ($rowExpedicion->ID_TRANSPORTISTA == '' ? 'NULL' : $rowExpedicion->ID_TRANSPORTISTA) . "
                            , ID_AGENCIA = " . ($rowExpedicion->ID_AGENCIA == '' ? 'NULL' : $rowExpedicion->ID_AGENCIA) . "
                            , MATRICULA = '" . trim((string)$bd->escapeCondicional($rowExpedicion->MATRICULA)) . "'
                            , MATRICULA_REMOLQUE = '" . trim((string)$bd->escapeCondicional($rowExpedicion->MATRICULA_REMOLQUE)) . "'
                            , TIPO_TRANSPORTE = '" . ($rowExpedicion->TIPO_TRANSPORTE == '' ? 'NULL' : $rowExpedicion->TIPO_TRANSPORTE) . "'
                            , ID_CONTENEDOR_EXPORTACION = " . ($rowExpedicion->ID_CONTENEDOR_EXPORTACION == '' ? 'NULL' : $rowExpedicion->ID_CONTENEDOR_EXPORTACION) . "
                            , ID_PUERTO_ORIGEN = " . ($rowExpedicion->ID_PUERTO_EXPORTACION_ORIGEN == '' ? 'NULL' : $rowExpedicion->ID_PUERTO_EXPORTACION_ORIGEN) . "
                            , ID_PUERTO_DESTINO = " . ($rowExpedicion->ID_PUERTO_EXPORTACION_DESTINO == '' ? 'NULL' : $rowExpedicion->ID_PUERTO_EXPORTACION_DESTINO) . "
                            , CARGA = " . ($rowExpedicion->CARGA == '' ? 'NULL' : "' $rowExpedicion->CARGA'") . "
                            , ALBARAN_TRANSPORTE = '" . trim((string)$bd->escapeCondicional($rowExpedicion->ALBARAN_TRANSPORTE)) . "'
                            , NACIONAL = " . ($rowExpedicion->NACIONAL == '' ? 'NULL' : "' $rowExpedicion->NACIONAL'") . "
                            , COMUNITARIO = " . ($rowExpedicion->COMUNITARIO == '' ? 'NULL' : "' $rowExpedicion->COMUNITARIO'") . "
                            , ADR = " . ($rowExpedicion->ADR == '' ? 'NULL' : "' $rowExpedicion->ADR'") . "
                            , SEGURO = " . $rowExpedicion->SEGURO . "
                            , CONTRATACION = " . ($rowExpedicion->CONTRATACION == '' ? 'NULL' : "' $rowExpedicion->CONTRATACION'");
            $bd->ExecSQL($sqlInsert);

            //OBTENGO ID CREADO
            $idOrdenTransporte = $bd->IdAsignado();

            //BUSCO LA ORDEN DE TRANSPORTE
            $rowOrdenTransporte = $bd->VerReg("ORDEN_TRANSPORTE", "ID_ORDEN_TRANSPORTE", $idOrdenTransporte);

            //SI LA OT ES DEL MODELO NUEVO, NO SE PUEDE ASIGNAR UNA RECOGIDA CON PEDIDOS RELEVANTES PARA ENTREGA ENTRANTE
            if ($rowOrdenTransporte->MODELO_TRANSPORTE == 'Segundo'):
                $sqlCuantosREE    = "SELECT COUNT(PSL.ID_PEDIDO_SALIDA_LINEA) AS NUM_REE
                                     FROM PEDIDO_SALIDA_LINEA PSL
                                     INNER JOIN MOVIMIENTO_SALIDA_LINEA MSL ON MSL.ID_PEDIDO_SALIDA_LINEA = PSL.ID_PEDIDO_SALIDA_LINEA
                                     WHERE MSL.ID_EXPEDICION = $rowExpedicion->ID_EXPEDICION AND PSL.RELEVANTE_ENTREGA_ENTRANTE = 1
                                     AND PSL.BAJA = 0 AND MSL.BAJA = 0 AND MSL.LINEA_ANULADA = 0";
                $resultCuantosREE = $bd->ExecSQL($sqlCuantosREE);
                $rowCuantosREE    = $bd->SigReg($resultCuantosREE);
                $html->PagErrorCondicionado($rowCuantosREE->NUM_REE, ">", 0, "RecogidaRelevanteEntregaEntranteParaOrdenTransporteModeloNuevo");
            endif;

            //ACTUALIZO EL REGISTRO
            $sqlUpdate = "UPDATE EXPEDICION SET ID_ORDEN_TRANSPORTE = $idOrdenTransporte WHERE ID_EXPEDICION = $rowExpedicion->ID_EXPEDICION";
            $bd->ExecSQL($sqlUpdate);

            //FINALIZO LA TRANSACCION
            $bd->commit_transaction();

        endif;
        //FIN SI NO TIENE ORDEN DE TRANSPORTE, SE CREA SIN GASTOS DE TRANSPORTE Y LA ASOCIO A LA RECOGIDA

        //COMPRUEBO QUE LA ORDEN DE TRANSPORTE NO ESTE DADA DE BAJA
        $html->PagErrorCondicionado($rowOrdenTransporte->BAJA, "!=", 0, "OrdenTransporteBaja");

        //COMPROBAMOS QUE EL ESTADO SEA CORRECTO
        if (($rowOrdenTransporte->ESTADO_INTERFACES != "Creada") && ($rowOrdenTransporte->ESTADO_INTERFACES != "Recogidas en Transmision")):
            $html->PagError("EstadoOTIncorrecto");
        endif;

        //BUSCO LAS CONTRATACIONES ACEPTADAS ASOCIADAS A LA ORDEN DE TRANSPORTE
        $sqlOrdenContratacion    = "SELECT * FROM ORDEN_CONTRATACION WHERE ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE  AND ESTADO='Aceptada' AND BAJA = 0 ";
        $resultOrdenContratacion = $bd->ExecSQL($sqlOrdenContratacion);

        //COMPRUEBO QUE LAS CONTRATACIONES ACEPTADAS COINCIDEN CON LAS QUE DEBE HABER
        $html->PagErrorCondicionado($bd->NumRegs($resultOrdenContratacion), "!=", $rowOrdenTransporte->NUMERO_CONTRATACIONES, "ContratacionesAceptadasNoCoincidenConIndicadas");

        //COMPRUEBO QUE TODAS LAS ORDENES DE CONTRATACION TENGAN FECHA EJECUCION PARA PODER HACER LA CONVERSION DE MONEDAS
        $num = $bd->NumRegsTabla("ORDEN_CONTRATACION", "ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND ESTADO<>'Cancelada' AND ESTADO<>'Rechazada' AND BAJA = 0 AND FECHA_EJECUCION = '0000-00-00'");
        $html->PagErrorCondicionado($num, ">", 0, "OrdenesContratacionSinFechaEjecucion");

        //COMPRUEBO QUE LOS PESOS DE LAS ORDENES DE RECOGIDA SEAN CORRECTOS, COMO MINIMO EL SUMATORIO DE LOS PESOS DE LOS MATERIALES CONTENIDOS EN LA RECOGIDA CON PESO DEFINIDO
        $sqlOrdenesRecogida    = "SELECT *
                                  FROM EXPEDICION E
                                  WHERE E.ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND E.BAJA = 0";
        $resultOrdenesRecogida = $bd->ExecSQL($sqlOrdenesRecogida);
        $html->PagErrorCondicionado($bd->NumRegs($resultOrdenesRecogida), "==", 0, "OrdenTransporteSinRecogidas");

        while ($rowOrdenRecogida = $bd->SigReg($resultOrdenesRecogida)):
            //CALCULO EL PESO DE LOS MATERIALES CON PESO
            $pesoCalculado = $importe->getPesoMaterialesConPesoOrdenRecogida($rowOrdenRecogida->ID_EXPEDICION, 1);

            //SI LA ORDEN DE RECOGIDA TIENE UN PESO INFERIOR AL SUMATORIO DE LOS PESOS DE SUS MATERIALES CON PESO DEFINDO DOY UN ERROR
            $strError = $rowOrdenRecogida->ID_EXPEDICION;
            $html->PagErrorCondicionado(($pesoCalculado - $rowOrdenRecogida->PESO), ">", EPSILON_SISTEMA, "ErrorPesoOrdenRecogida");

            //COMPRUEBO QUE LA RECOGIDA TIENE LINEAS
            $num = "";
            if ($rowOrdenRecogida->TIPO_ORDEN_RECOGIDA == 'Recogida en Almacen'):
                //SI LA ORDEN DE RECOGIDA  EN ALMACEN TIENE LINEAS ASOCIADAS, NO SE PODRA ANULAR
                $num = $bd->NumRegsTabla("MOVIMIENTO_SALIDA_LINEA", "ID_EXPEDICION = $rowOrdenRecogida->ID_EXPEDICION AND BAJA = 0 AND LINEA_ANULADA = 0");

            elseif ($rowOrdenRecogida->TIPO_ORDEN_RECOGIDA == 'Operaciones en Parque'):
                //SI LA ORDEN DE RECOGIDA EN PARQUE TIENE MOVIMIENTOS ASOCIADOS, NO SE PODRA ANULAR
                $num = $bd->NumRegsTabla("ORDEN_TRABAJO_MOVIMIENTO", "ID_EXPEDICION = $rowOrdenRecogida->ID_EXPEDICION AND BAJA = 0");

            elseif ($rowOrdenRecogida->TIPO_ORDEN_RECOGIDA == 'Operaciones fuera de Sistema'):
                //SI LA ORDEN DE RECOGIDA FUERA DE SISTEMA TIENE BULTOS ASOCIADOS, NO SE PODRA ANULAR
                $num = $bd->NumRegsTabla("BULTO", "ID_EXPEDICION = $rowOrdenRecogida->ID_EXPEDICION");

            elseif ($rowOrdenRecogida->TIPO_ORDEN_RECOGIDA == 'Recogida en Proveedor' && $rowOrdenRecogida->SUBTIPO_ORDEN_RECOGIDA == 'Con Pedido Conocido'):
                //SI LA ORDEN DE RECOGIDA EN PROVEEDOR CON PEDIDO CONOCIDO TIENE LINEAS, NO SE PODRA ANULAR
                $num = $bd->NumRegsTabla("EXPEDICION_PEDIDO_CONOCIDO", "ID_EXPEDICION = $rowOrdenRecogida->ID_EXPEDICION AND BAJA = 0");

            elseif ($rowOrdenRecogida->TIPO_ORDEN_RECOGIDA == 'Recogida en Proveedor' && $rowOrdenRecogida->SUBTIPO_ORDEN_RECOGIDA == 'Retorno Material Estropeado desde Proveedor'):
                //SI LA ORDEN DE RECOGIDA EN PROVEEDOR CON PEDIDO CONOCIDO TIENE LINEAS, NO SE PODRA ANULAR
                $num = $bd->NumRegsTabla("MOVIMIENTO_SALIDA_LINEA", "ID_EXPEDICION = $rowOrdenRecogida->ID_EXPEDICION AND BAJA = 0");
            endif;
            $html->PagErrorCondicionado($num, "==", 0, "OrdenRecogidaSinLineas");

            //COMPRUEBO QUE TENGA AL MENOS UN BULTO ASIGNADO POR RECOGIDA
            $html->PagErrorCondicionado($expedicion->RecogidaSinBultoAsignado($rowOrdenRecogida->ID_EXPEDICION), "==", true, "OrdenRecogidaConDestinoSinBulto");
        endwhile;

        //SI HAY RECOGIDAS FUERA DE SISTEMA, COMPRUEBO QUE TENGAN EL ELEMENTO DE IMPUTACION ASIGNADO
        $sqlRecogidasFueraSistema    = "SELECT ID_EXPEDICION
                                     FROM EXPEDICION
                                     WHERE TIPO_ORDEN_RECOGIDA = 'Operaciones fuera de Sistema' AND ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND BAJA = 0 AND (ID_ELEMENTO_IMPUTACION IS NULL OR CODIGO_SELECCIONADO_ELEMENTO_IMPUTACION = '')";
        $resultRecogidasFueraSistema = $bd->ExecSQL($sqlRecogidasFueraSistema);

        //SI EXISTE ALGUNA SIN ELEMENTO DE IMPUTACION MOSTRAMOS ERROR
        if ($bd->NumRegs($resultRecogidasFueraSistema) > 0):
            $coma     = "";
            $strError = "";
            while ($rowRecogidasFueraSistema = $bd->SigReg($resultRecogidasFueraSistema)):
                $strError .= $coma . $rowRecogidasFueraSistema->ID_EXPEDICION;
                $coma     = ", ";
            endwhile;
            $html->PagError("RecogidasFueraDeSistemaSinElementoImputacion");
        endif;

        //BUSCO LAS RECOGIDAS EN ALMACEN SIN TRANSMITIR ASOCIADAS A LA ORDEN DE TRANSPORTE
        $sqlExpedicion                  = "SELECT * 
                                        FROM EXPEDICION 
                                        WHERE ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND TIPO_ORDEN_RECOGIDA = 'Recogida en Almacen' AND (ESTADO = 'Creada' OR ESTADO = 'En Transmision') AND BAJA = 0 ";
        $resultExpedicion               = $bd->ExecSQL($sqlExpedicion);
        $numRecogidasAlmacenATransmitir = $bd->NumRegs($resultExpedicion);

        //RECORREMOS LAS RECOGIDAS EN ALMACEN A TRANSMITIR A SAP
        while ($rowExpedicion = $bd->SigReg($resultExpedicion)):

            //COMPRUEBO SI LA ORDEN DE RECOGIDA TIENE CAMBIOS DE ESTADO GRUPO QUE REVERTIR
            $numCambioEstadoGrupoRevertir = $bd->NumRegsTabla("CAMBIO_ESTADO_GRUPO", "ID_EXPEDICION = $rowExpedicion->ID_EXPEDICION AND PENDIENTE_REVERTIR = 1", "No");
            //SI TIEEN CAMBIOS DE ESTADO, GUARDAMOS EL ERROR Y CONTINUAMOS
            if ($numCambioEstadoGrupoRevertir > 0):

                $arr['errores'] = $arr['errores'] . $auxiliar->traduce("No se puede realizar la operacion porque la orden de transporte tiene cambios de estado pendientes de revertir", $administrador->ID_IDIOMA) . ": $rowExpedicion->ID_EXPEDICION<br><br>";
                continue;
            endif;
            //FIN COMPRUEBO SI LA ORDEN DE RECOGIDA TIENE CAMBIOS DE ESTADO GRUPO QUE REVERTIR


            /************************************************* ACCIONES TIPO PEDIDO ZTRE/ZTRG/ZTRH *************************************************/
            //VARIABLE PARA SABER SI FALLA LA DEVOLUCION DE LOS NUMEROS DE PEDIDO DE TIPO ZTRE/ZTRG/ZTRH
            $errorGeneracionNumeroPedido = false;

            //VARIABLE PARA SABER LOS ERRORES PRODUCIDOS AL GENERAR LOS NUMEROS DE PEDIDO DE TIPO ZTRE/ZTRG/ZTRH
            $textoErrorGeneracionNumeroPedido = "";

            //ACTUALIZAR LOS NUMEROS DE PEDIDO DE TIPO ZTRE/ZTRG/ZTRH
            $sqlLineas    = "SELECT DISTINCT(MSL.ID_PEDIDO_SALIDA)
                            FROM MOVIMIENTO_SALIDA_LINEA MSL
                            INNER JOIN PEDIDO_SALIDA PS ON PS.ID_PEDIDO_SALIDA = MSL.ID_PEDIDO_SALIDA
                            WHERE MSL.ID_EXPEDICION = $rowExpedicion->ID_EXPEDICION AND
                                  PS.PEDIDO_SAP = '' AND PS.TIPO_PEDIDO IN ('Traslado', 'Traspaso Entre Almacenes Material Estropeado') AND
                                  PS.TIPO_PEDIDO_SAP IN ('ZTRE', 'ZTRG', 'ZTRH') AND
                                  MSL.LINEA_ANULADA = 0 AND
                                  MSL.BAJA = 0 AND
                                  MSL.ESTADO = 'Pendiente de Expedir'";
            $resultLineas = $bd->ExecSQL($sqlLineas);
            while ($rowLinea = $bd->SigReg($resultLineas)): //RECORRO LA SELECCION DE PEDIDOS

                //BUSCO EL PEDIDO DE SALIDA
                $rowPedidoSalida = $bd->VerReg("PEDIDO_SALIDA", "ID_PEDIDO_SALIDA", $rowLinea->ID_PEDIDO_SALIDA);


                //INICIO LA TRANSACCION POR PEDIDO A INFORMAR A SAP
                $bd->begin_transaction();


                //LLAMO A SAP
                $resultado = $sap->InformarSAPPedidoTraslado($rowLinea->ID_PEDIDO_SALIDA);
                if ($resultado['RESULTADO'] != 'OK'): //No se ha podido grabar la información en SAP
                    foreach ($resultado['ERRORES'] as $arrayDeErrores):
                        foreach ($arrayDeErrores as $mensaje_error):
                            $arr['errores'] = $arr['errores'] . $mensaje_error . "<br>";
                        endforeach;
                    endforeach;

                    //DESHAGO LA TRANSACCION
                    $bd->rollback_transaction();

                    //GRABO EL ERROR PRODUCIDO EN BASE DE DATOS
                    $sap->InsertarErrores($resultado);

                    $errorGeneracionNumeroPedido = true;

                else:
                    $sqlUpdate = "UPDATE PEDIDO_SALIDA SET
                                    PEDIDO_SAP = '" . $resultado['PEDIDO'] . "'
                                    , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                    WHERE ID_PEDIDO_SALIDA = $rowLinea->ID_PEDIDO_SALIDA";
                    $bd->ExecSQL($sqlUpdate);
                endif;


                //FINALIZO LA TRANSACCION
                $bd->commit_transaction();


            endwhile;
            //FIN ACTUALIZAR LOS NUMEROS DE PEDIDO DE TIPO ZTRE/ZTRG

            //MUESTRO LOS ERRORES PRODUCIDOS AL GENERAR LOS NUMEROS DE PEDIDO DE TIPO ZTRE/ZTRG
            if ($errorGeneracionNumeroPedido == true):
                continue;//CONTINUAMOS CON LA SIGUIENTE RECOGIDA
                //$html->PagError("ErrorSAP");
            endif;
            /*********************************************** FIN ACCIONES TIPO PEDIDO ZTRE/ZTRG/ZTRH ***********************************************/


            /************************************* ACCIONES GENERAR ALBARANES Y ASIGNACION EXPEDICION SAP *************************************/


            //INICIO LA PRIMERA TRANSACCION (GENERACION DE ALBARANES, ASIGNACION DE EXPEDICION SAP Y COMPROBACION DE LINEAS)
            $bd->begin_transaction();

            //COMIENZO TRANSMISION DE LA ORDEN DE TRANSPORTE (GENERO LOS ALBARANES Y ASIGNO LA EXPEDICION SAP A LAS LINEAS)
            $arrDevueltoTransmitirOrdenTransporteASAP = $expedicion->generar_albaranes_y_asignar_expediciones_SAP($rowExpedicion->ID_EXPEDICION);//var_dump($arrDevueltoTransmitirOrdenTransporteASAP);exit;

            //GUARDO LOS ERRORES QUE SE HAN PRODUCIDO EN ESTE PRIMER PASO
            if ($arrDevueltoTransmitirOrdenTransporteASAP['errores'] != ""):
                $arr['errores'] = $arr['errores'] . "<strong>" . $arrDevueltoTransmitirOrdenTransporteASAP['errores'] . "</strong><br>";
            endif;

            //EXTRAIGO LAS EXPEDICIONES SAP A TRANSMITIR A SAP
            $arrExpedicionesSAP = array();
            if ($arrDevueltoTransmitirOrdenTransporteASAP['expediciones_SAP'] != ""):
                $arrExpedicionesSAP = explode(",", (string)$arrDevueltoTransmitirOrdenTransporteASAP['expediciones_SAP']);
            endif;

            //EXTRAIGO LAS LINEAS DE PEDIDO INVOLUCRADAS, DEBEREMOS ENVIAR EL BLOQUEO/DESBLOQUEO DE ESTAS A SAP
            if ($arrDevueltoTransmitirOrdenTransporteASAP['lista_lineas_pedido'] != ""):
                $arrayLineasPedidosInvolucradas = explode(",", (string)$arrDevueltoTransmitirOrdenTransporteASAP['lista_lineas_pedido']);
                $arrayLineasPedidosInvolucradas = array_unique((array)$arrayLineasPedidosInvolucradas);
            endif;


            //FINALIZO LA PRIMERA TRANSACCION (GENERACION DE ALBARANES, ASIGNACION DE EXPEDICION SAP Y COMPROBACION DE LINEAS)
            $bd->commit_transaction();
            /*********************************** FIN ACCIONES GENERAR ALBARANES Y ASIGNACION EXPEDICION SAP ***********************************/


            /******************************************** ACCIONES TRANSMITIR EXPEDICION SAP A SAP ********************************************/
            //DECLARO LA FECHA A TRANSMITIR A SAP
            $txFechaTransmitir = date("Y-m-d");

            /***************************************** ¡IMPORTANTE! VALORES CONTENIDOS EN $arrExpedicionesSAP *****************************************/
            //VERION 'Tercera' -> ELEMENTOS DEL TIPO: ID BULTO/MOVIMIENTO + '_' + ID PEDIDO SALIDA
            //VERION 'Cuarta' -> ELEMENTOS DEL TIPO: ID ORDEN TRANSPORTE + '_' + ID EXPEDICION SAP
            /*************************************** FIN ¡IMPORTANTE! VALORES CONTENIDOS EN $arrExpedicionesSAP ***************************************/

            //SI EXISTEN EXPEDICIONES SAP, LAS TRANSMITIMOS
            if (count((array)$arrExpedicionesSAP) > 0):
                //RECORRO LAS EXPEDICIONES SAP
                foreach ($arrExpedicionesSAP as $expedicionSAP):

                    if ($rowExpedicion->VERSION == "Tercera"):
                        //DESCOMPONGO LA EXPEDICION SAP DEVUELTA ($duplaMovimientoBultoPedido)
                        $arrMovimientoBultoPedido = explode("_", (string)$expedicionSAP);

                        //CONFORMO EL VALOR DE LA EXPEDICION SAP ASIGNADA A LAS LINEAS
                        $expedicionSAPGuardar = $exp_SAP->getExpedicionSAP($rowExpedicion->ID_ORDEN_TRANSPORTE, $rowExpedicion->ID_EXPEDICION, $arrMovimientoBultoPedido[0], $arrMovimientoBultoPedido[1]);

                        //GENERO LA EXPEDICION SAP CON IDENTIFICADORES QUE ES LO QUE ESPERA LA FUNCION PARA TRANSMITIRLA A SAP
                        $expedicionSAPConIdentificadores = $exp_SAP->getExpedicionSAPConIdentificadores($rowExpedicion->VERSION, $expedicionSAPGuardar);

                        //INCLUYO EL PEDIDO EN EL ARRAY DE PEDIDOS PROCESADOS
                        $arrPedidos[] = $exp_SAP->getIdPedidoSalidaSGA($rowExpedicion->VERSION, $expedicionSAPGuardar);

                        //BUSCO EL PEDIDO DE SALIDA POR SI ES PREVENTIVO Y HACE FALTA HACER EL CAMBIO DE ESTADO
                        if ($exp_SAP->getIdPedidoSalidaSGA($rowExpedicion->VERSION, $expedicionSAPGuardar) != NULL):
                            //BUSCO EL PEDIDO DE SALIDA
                            $rowPedSal = $bd->VerReg("PEDIDO_SALIDA", "ID_PEDIDO_SALIDA", $exp_SAP->getIdPedidoSalidaSGA($rowExpedicion->VERSION, $expedicionSAPGuardar));
                        endif;
                    elseif ($rowExpedicion->VERSION == "Cuarta"):
                        //DESCOMPONGO LA EXPEDICION SAP DEVUELTA ($duplaOrdenTransporteMovimientoSalidaLinea)
                        $arrOrdenTransporteExpedicionSAP = explode("_", (string)$expedicionSAP);

                        //CONFORMO EL VALOR DE LA EXPEDICION SAP ASIGNADA A LAS LINEAS
                        $expedicionSAPGuardar = $expedicionSAP;

                        //GENERO LA EXPEDICION SAP CON IDENTIFICADORES QUE ES LO QUE ESPERA LA FUNCION PARA TRANSMITIRLA A SAP
                        $expedicionSAPConIdentificadores = $exp_SAP->getExpedicionSAPConIdentificadores($rowExpedicion->VERSION, $expedicionSAPGuardar);

                        //BUSCO EL PEDIDO DE SALIDA POR SI ES PREVENTIVO Y HACE FALTA HACER EL CAMBIO DE ESTADO
                        if ($exp_SAP->getIdPedidoSalidaSGA($rowExpedicion->VERSION, $expedicionSAPGuardar) != NULL):
                            //BUSCO EL PEDIDO DE SALIDA
                            $rowPedSal = $bd->VerReg("PEDIDO_SALIDA", "ID_PEDIDO_SALIDA", $exp_SAP->getIdPedidoSalidaSGA($rowExpedicion->VERSION, $expedicionSAPGuardar));
                        endif;
                    endif;

                    //SI EL PEDIDO ES DE PREVENTIVO Y EL MATERIAL DE CORRECTIVO, CONVIERTO EL MATERIAL DE LA EXPEDICION SAP PREVIAMENTE A PREVENTIVO
                    $rowTipoBloqueoPreventivo = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "SP");
                    if ($rowPedSal->ID_TIPO_BLOQUEO == $rowTipoBloqueoPreventivo->ID_TIPO_BLOQUEO):
                        //BUSCO UNA LINEA DEL MOVIMIENTO DE SALIDA CORRESPONDIENTE CON EL PEDIDO DE SALIDA CORRESPONDIENTE
                        $rowMovSalLinea = $bd->VerRegRest("MOVIMIENTO_SALIDA_LINEA", "EXPEDICION_SAP = '" . $expedicionSAPGuardar . "' AND LINEA_ANULADA = 0 AND BAJA = 0");

                        //SI LA LINEA DEL MOVIMIENTO DE SALIDA HA SIDO PREPARARADA CON MATERIAL CORRECTIVO, REALIZO LA CONVERSION A PREVENTIVO
                        if ($rowMovSalLinea->ID_TIPO_BLOQUEO == NULL):


                            //INICIO UNA TRANSACCION POR CAMBIO ESTADO DE OK A PREVENTIVO DE EXPEDICION SAP
                            $bd->begin_transaction();


                            //SI NO SE HAN PRODUCIDO ERRORES, MODIFICO EL TIPO BLOQUEO DE LAS LINEAS DE MOVIMIENTO DE LA EXPEDICION SAP A PREVENTIVO PARA QUE EL PROCESO SIGA DE FORMA NORMAL
                            $sqlUpdate = "UPDATE MOVIMIENTO_SALIDA_LINEA SET
                                        ID_TIPO_BLOQUEO = $rowTipoBloqueoPreventivo->ID_TIPO_BLOQUEO
                                        WHERE EXPEDICION_SAP = '" . $expedicionSAPGuardar . "' AND LINEA_ANULADA = 0 AND BAJA = 0";
                            $bd->ExecSQL($sqlUpdate);

                            //CREO EL CAMBIO DE ESTADO GRUPO Y LA LLAMADA CORRESPONDIENTE
                            $arrDevueltoCambioEstadoExpedicionSAP = $expedicion->generar_cambio_estado_expedicionSAP($rowExpedicion->VERSION, $expedicionSAPConIdentificadores, NULL, $rowTipoBloqueoPreventivo->ID_TIPO_BLOQUEO, 'Salida');
                            //SI SE HA PRODUCIDO UN ERROR CON LA EXPEDICION SAP O SU TRANSMISION DESHACEMOS LA TRANSACCION
                            if ($arrDevueltoCambioEstadoExpedicionSAP['error_cambio_estado_expedidion_SAP'] == true):
                                //DESHAGO LA TRANSACCION POR EXPEDICION SAP
                                $bd->rollback_transaction();

                                //ME GUARDO LOS ERRORES
                                if ($arrDevueltoCambioEstadoExpedicionSAP['error_cambio_estado_expedidion_SAP'] == true):
                                    $arr['errores'] = $arr['errores'] . "<strong>" . $auxiliar->traduce("Se han producido los siguientes errores al realizar el cambio de estado de libre a preventivo de la expedicion SAP", $administrador->ID_IDIOMA) . " " . $expedicionSAPGuardar . ": </strong><br>" . $arrDevueltoCambioEstadoExpedicionSAP['errores'] . "<br>";
                                endif;

                                //GRABO EL ERROR PRODUCIDO EN BASE DE DATOS
                                $sap->InsertarErrores($arrDevueltoCambioEstadoExpedicionSAP['resultado']);

                                //SI HAY ERRORES CONTINUO CON LA SIGUIENTE EXPEDICION SAP
                                continue;
                            endif;


                            //FINALIZO UNA TRANSACCION POR CAMBIO ESTADO DE OK A PREVENTIVO DE EXPEDICION SAP
                            $bd->commit_transaction();


                        endif;
                        //FIN SI LA LINEA DEL MOVIMIENTO DE SALIDA HA SIDO PREPARARADA CON MATERIAL CORRECTIVO, REALIZO LA CONVERSION A PREVENTIVO
                    endif;
                    //FIN SI EL PEDIDO ES DE PREVENTIVO, CONVIERTO EL MATERIAL DE LA EXPEDICION SAP PREVIAMENTE A PREVENTIVO


                    //INICIO UNA TRANSACCION POR EXPEDICION SAP
                    $bd->begin_transaction();


                    //TRANSMITO LA EXPEDICION SAP A SAP
                    $arrDevueltoTransmitirOrdenTransporteASAP = $expedicion->transmitir_expedicionSAP_a_SAP($rowExpedicion->VERSION, $expedicionSAPConIdentificadores, $txFechaTransmitir);

                    //SI SE HA PRODUCIDO UN ERROR CON LA EXPEDICION SAP O SU TRANSMISION DESHACEMOS LA TRANSACCION
                    if (($arrDevueltoTransmitirOrdenTransporteASAP['error_expedidion_SAP'] == true) || ($arrDevueltoTransmitirOrdenTransporteASAP['error_transmision_expedidion_SAP'] == true)):
                        //DESHAGO LA TRANSACCION POR EXPEDICION SAP
                        $bd->rollback_transaction();

                        //SI HA HABIDO UN ERROR EN LA TRANSMISION A SAP, GRABO EL LOG DE ERRRORES
                        if ($arrDevueltoTransmitirOrdenTransporteASAP['error_transmision_expedidion_SAP'] == true):
                            //GRABO EL ERROR PRODUCIDO EN BASE DE DATOS
                            $sap->InsertarErrores($arrDevueltoTransmitirOrdenTransporteASAP['resultado']);
                        endif;

                        //ME GUARDO LOS ERRORES
                        if ($arrDevueltoTransmitirOrdenTransporteASAP['error_expedidion_SAP'] == true):
                            $arr['errores'] = $arr['errores'] . "<strong>" . $auxiliar->traduce("Se han producido los siguientes errores al transmitir la expedicion SAP", $administrador->ID_IDIOMA) . " " . $expedicionSAPGuardar . ": </strong><br>" . $arrDevueltoTransmitirOrdenTransporteASAP['errores'] . "<br>";
                        elseif ($arrDevueltoTransmitirOrdenTransporteASAP['error_transmision_expedidion_SAP'] == true):
                            $arr['errores'] = $arr['errores'] . $arrDevueltoTransmitirOrdenTransporteASAP['errores'] . "<br>";
                        endif;


                        //SI EL PEDIDO ES DE PREVENTIVO Y HA FALLADO LA TRANSMISION DE LA CANCELACION DE LA EXPEDICION SAP A SAP INTENTO DESHACER EL CAMBIO ESTADO PREVIO SI EL PEDIDO ERA DE PREVENTIVO
                        if ($rowPedSal->ID_TIPO_BLOQUEO == $rowTipoBloqueoPreventivo->ID_TIPO_BLOQUEO):
                            //SI HA FALLADO LA TRANSMISION DE LA CANCELACION DE LA EXPEDICION SAP A SAP INTENTO DESHACER EL CAMBIO ESTADO PREvIO SI EL PEDIDO ERA DE PREVENTIVO
                            $idCambioEstadoGrupo = $arrDevueltoCambioEstadoExpedicionSAP['idCambioEstadoGrupo'];

                            //SI EXISTE EL CAMBIO DE ESTADO
                            if ($idCambioEstadoGrupo != NULL):

                                //INICIO UNA TRANSACCION POR CAMBIO ESTADO DE OK A PREVENTIVO DE EXPEDICION SAP
                                $bd->begin_transaction();


                                //HAGO LA LLAMADA A SAP
                                $arrDevueltoCambioEstadoExpedicionSAP = $expedicion->revertir_cambio_estado_grupo($idCambioEstadoGrupo);

                                //SI SE HA PRODUCIDO UN ERROR CON LA EXPEDICION SAP O SU TRANSMISION DESHACEMOS LA TRANSACCION
                                if (($arrDevueltoCambioEstadoExpedicionSAP['error_cambio_estado_expedidion_SAP'] == true) || ($arrDevueltoCambioEstadoExpedicionSAP['error_transmision_cambio_estado_expedidion_SAP'] == true)):

                                    //DESHAGO LA TRANSACCION POR EXPEDICION SAP
                                    $bd->rollback_transaction();

                                    //ME GUARDO LOS ERRORES
                                    if ($arrDevueltoCambioEstadoExpedicionSAP['error_cambio_estado_expedidion_SAP'] == true):
                                        $arr['errores'] = $arr['errores'] . "<strong>" . $auxiliar->traduce("Se han producido los siguientes errores al realizar el cambio de estado de preventivo a libre de la expedicion SAP", $administrador->ID_IDIOMA) . " " . $expedicionSAP . ": </strong><br>" . $arrDevueltoCambioEstadoExpedicionSAP['errores'] . "<br>";
                                    endif;

                                    //GRABO EL ERROR PRODUCIDO EN BASE DE DATOS
                                    $sap->InsertarErrores($arrDevueltoCambioEstadoExpedicionSAP['resultado']);

                                    //EMAIL CORRESPONDIENTE DE NOTIFICACION
                                    $mailEsp           = new PHPMailer();
                                    $mailEsp->From     = CAMBIOS_ESTADO_REMITENTE_EMAIL;
                                    $mailEsp->FromName = CAMBIOS_ESTADO_REMITENTE_NOMBRE;
                                    $mailEsp->Mailer   = "mail";
                                    $mailEsp->Body     = $auxiliar->traduce("No se han podido revertir los cambios de estado (de libre a preventivo) generados por la transmision a SAP, sera necesario anularlos manualmente desde la ficha de la orden de transporte.", $administrador->ID_IDIOMA) . " " . $auxiliar->traduce("Orden de transporte", $administrador->ID_IDIOMA) . ": " . $rowExpedicion->ID_EXPEDICION . "<br>" . $auxiliar->traduce("Errores producidos", $administrador->ID_IDIOMA) . ":" . $arrDevueltoCancelarExpedicionSAPASAP['errores'] . "<br>" . $arrDevueltoCambioEstadoExpedicionSAP['errores'];
                                    $mailEsp->Subject  = $auxiliar->traduce("Se ha producido un error al transmitir a SAP la orden de transporte", $administrador->ID_IDIOMA) . ": " . $rowExpedicion->ID_EXPEDICION;
                                    $mailEsp->IsHTML(true);
                                    $mailEsp->ClearAllRecipients();
                                    $mailEsp->AddAddress(CAMBIOS_ESTADO_EMAIL_DESTINATARIO);
                                    $mailEsp->Sender = CAMBIOS_ESTADO_REMITENTE_EMAIL;
                                    $mailEsp->Send();

                                    //SI HAY ERRORES CONTINUO CON LA SIGUIENTE EXPEDICION SAP
                                    continue;

                                endif;


                                //FINALIZO UNA TRANSACCION POR CAMBIO ESTADO DE OK A PREVENTIVO DE EXPEDICION SAP
                                $bd->commit_transaction();


                            endif;
                            //FIN SI EXISTE EL CAMBIO DE ESTADO

                        endif;
                        //FIN SI EL PEDIDO ES DE PREVENTIVO Y HA FALLADO LA TRANSMISION DE LA CANCELACION DE LA EXPEDICION SAP A SAP INTENTO DESHACER EL CAMBIO ESTADO PREvIO SI EL PEDIDO ERA DE PREVENTIVO

                    endif;
                    //FIN SI SE HA PRODUCIDO UN ERROR CON LA EXPEDICION SAP O SU TRANSMISION DESHACEMOS LA TRANSACCION


                    //FINALIZO CADA TRANSACCION POR EXPEDICION SAP
                    $bd->commit_transaction();


                    //SI NO SE HA PRODUCIDO UN ERROR CON LA EXPEDICION SAP NI CON SU TRANSMISION A SAP, REALIZO UNA SERIE DE ACTUACIONES
                    if (($arrDevueltoTransmitirOrdenTransporteASAP['error_expedidion_SAP'] == false) && ($arrDevueltoTransmitirOrdenTransporteASAP['error_transmision_expedidion_SAP'] == false)):


                        //INICIO UNA TRANSACCION POR EXPEDICION SAP
                        $bd->begin_transaction();

                        //SE OBTIENE LA OT BLOQUEÁNDOLA
                        $rowOrdenTransporte = $bd->VerRegRest("ORDEN_TRANSPORTE", "ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE FOR UPDATE");

                        //ME GUARDO LAS EXPEDICIONES SAP
                        $arr['expediciones_SAP'] = $arr['expediciones_SAP'] . $expedicionSAPGuardar . "<br>";

                        //SI RECIBIMOS ENTREGAS SALIENTES PARA EL MODELO DE TRANSPORTE SEGUNDO, LAS GUARDO
                        if (
                            ($rowOrdenTransporte->MODELO_TRANSPORTE == 'Segundo') &&
                            (isset($arrDevueltoTransmitirOrdenTransporteASAP['resultado']['ENTREGAS_SALIENTES_DEVUELTAS'])) &&
                            (count((array)$arrDevueltoTransmitirOrdenTransporteASAP['resultado']['ENTREGAS_SALIENTES_DEVUELTAS']) > 0)
                        ):

                            //EXTRAIGO LA ENTREGA SALIENTE
                            $numEntregaSaliente = $arrDevueltoTransmitirOrdenTransporteASAP['resultado']['E_DELIVERY'];

                            //ENTREGAS SALIENTES
                            foreach ($arrDevueltoTransmitirOrdenTransporteASAP['resultado']['ENTREGAS_SALIENTES_DEVUELTAS'] as $arr_entrega):
                                $idOT_Exp = $arr_entrega['ENTREGA_SALIDA'];//ORDENTRANSPORTE_ORDENRECOGIDA

                                $arridExpedicion = explode("_", (string)$idOT_Exp);//SEPARAMOS TRANSPORTE Y RECOGIDA

                                //SE OBTIENE LA EXP SAP
                                $rowExpedicionSAP = $bd->VerReg("EXPEDICION_SAP", "ID_EXPEDICION_SAP", $arridExpedicion[1]);
                                $rowOrdenRecogida = $bd->VerReg("EXPEDICION", "ID_EXPEDICION", $rowExpedicionSAP->ID_EXPEDICION);

                                //NUM_ENTREGA
                                foreach ($arr_entrega['NUM_ENTREGA'] as $arr_numEntrega):

                                    //POSICION
                                    foreach ($arr_numEntrega['POSICION'] as $arr_posiciones):

                                        $numPedidoSap = $arr_posiciones['PEDIDO'];     //NUM PEDIDO SAP
                                        $posPedido    = $arr_posiciones['POS_PEDIDO']; //NUM POSICION PEDIDO
                                        $posEntrega   = $arr_posiciones['POS_ENTREGA'];//NUM POSICION ENTREGA

                                        //BUSCAMOS LA ENTREGA SALIENTE
                                        if ($rowOrdenRecogida->TIPO_ORDEN_RECOGIDA == "Recogida en Almacen"):
                                            $rowPed    = $bd->VerRegRest("PEDIDO_SALIDA", "PEDIDO_SAP = '$numPedidoSap' AND BAJA = 0", "No");
                                            $rowPedLin = $bd->VerRegRest("PEDIDO_SALIDA_LINEA", "LINEA_PEDIDO_SAP = $posPedido AND ID_PEDIDO_SALIDA = $rowPed->ID_PEDIDO_SALIDA AND BAJA = 0", "No");

                                            //BUSCAMOS LA ENTREGA SALIENTE
                                            $rowEntregaSaliente = $bd->VerRegRest("EXPEDICION_ENTREGA_SALIENTE", "ID_EXPEDICION = $rowOrdenRecogida->ID_EXPEDICION AND ID_PEDIDO_SALIDA = $rowPed->ID_PEDIDO_SALIDA AND EXPEDICION_SAP = '$idOT_Exp'", "No");

                                            //ACTUALIZAMOS EL REGISTRO CON EL NUMERO DE ENTREGA SALIENTE DEVUELTO
                                            if ($rowEntregaSaliente):
                                                $sqlUpdate = "UPDATE EXPEDICION_ENTREGA_SALIENTE SET
                                                            NUMERO_ENTREGA_SALIENTE = '$numEntregaSaliente'
                                                            WHERE ID_EXPEDICION_ENTREGA_SALIENTE = $rowEntregaSaliente->ID_EXPEDICION_ENTREGA_SALIENTE";
                                                $bd->ExecSQL($sqlUpdate);
                                                $idExpEntSal = $rowEntregaSaliente->ID_EXPEDICION_ENTREGA_SALIENTE;
                                            else: //SE CREA EL REGISTRO
                                                $sqlInsert = "INSERT INTO EXPEDICION_ENTREGA_SALIENTE SET
                                                            ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE
                                                            , ID_EXPEDICION = $rowOrdenRecogida->ID_EXPEDICION
                                                            , ID_PEDIDO_SALIDA = $rowPed->ID_PEDIDO_SALIDA
                                                            , EXPEDICION_SAP = '$idOT_Exp'
                                                            , NUMERO_ENTREGA_SALIENTE = '$numEntregaSaliente'";
                                                $bd->ExecSQL($sqlInsert);
                                                $idExpEntSal = $bd->IdAsignado();
                                            endif;

                                            //se comprueba si ya existe una EESP para esa EES, línea y línea de pedido
                                            $numEESP = $bd->NumRegsTabla("EXPEDICION_ENTREGA_SALIENTE_POSICION", "ID_EXPEDICION_ENTREGA_SALIENTE = $idExpEntSal AND LINEA_ENTREGA_SALIENTE = '$posEntrega' AND ID_PEDIDO_SALIDA_LINEA = $rowPedLin->ID_PEDIDO_SALIDA_LINEA");
                                            if ($numEESP == 0):
                                                //NOS GUARDAMOS LA RELACION ENTRE LA POSICION DEL PEDIDO Y LA POSICION DE LA ENTREGA SALIENTE
                                                //CREAMOS EL REGISTRO DE ENTREGA SALIENTE
                                                $sqlInsert = "INSERT INTO EXPEDICION_ENTREGA_SALIENTE_POSICION SET
                                                      ID_EXPEDICION_ENTREGA_SALIENTE = $idExpEntSal
                                                      , LINEA_ENTREGA_SALIENTE = '$posEntrega'
                                                      , ID_PEDIDO_SALIDA_LINEA = $rowPedLin->ID_PEDIDO_SALIDA_LINEA";
                                                $bd->ExecSQL($sqlInsert);
                                            endif;
                                        endif;
                                        //FIN BUSCAMOS LA ENTREGA SALIENTE

                                    endforeach;//FIN POSICION

                                endforeach;//FIN NUM_ENTREGA

                            endforeach;//FIN ENTREGAS SALIENTES

                        endif;
                        //FIN SI RECIBIMOS ENTREGAS SALIENTES PARA EL MODELO DE TRANSPORTE SEGUNDO, LAS GUARDO

                        //SI EL PEDIDO ERA DE PREVENTIVO, ACTUALIZO EL CAMBIO DE ESTADO GRUPO COMO NO PENDIENTE DE REVERSION
                        if (($rowPedSal->ID_TIPO_BLOQUEO == $rowTipoBloqueoPreventivo->ID_TIPO_BLOQUEO) && ($arrDevueltoCambioEstadoExpedicionSAP['idCambioEstadoGrupo'] != NULL)):
                            $idCambioEstadoGrupo = $arrDevueltoCambioEstadoExpedicionSAP['idCambioEstadoGrupo'];
                            $sqlUpdate           = "UPDATE CAMBIO_ESTADO_GRUPO SET
                                                PENDIENTE_REVERTIR = 0
                                                WHERE ID_CAMBIO_ESTADO_GRUPO = $idCambioEstadoGrupo";
                            $bd->ExecSQL($sqlUpdate);
                        endif;

                        //FINALIZO CADA TRANSACCION POR EXPEDICION SAP
                        $bd->commit_transaction();

                    endif;
                    //FIN SI NO SE HA PRODUCIDO UN ERROR CON LA EXPEDICION SAP NI CON SU TRANSMISION A SAP, REALIZO UNA SERIE DE ACTUACIONES

                endforeach;
                //FIN RECORRO LAS EXPEDICIONES SAP
            endif;
            //FIN SI EXISTEN EXPEDICIONES SAP, LAS TRANSMITIMOS
            /****************************************** FIN ACCIONES TRANSMITIR EXPEDICION SAP A SAP ******************************************/

            /****************************************** SOLICITAR FACTURAS NO COMERCIALES ******************************************/
            if ($rowExpedicion->NACIONAL == 'Internacional' && $rowExpedicion->ID_ORDEN_TRANSPORTE != NULL):

                $rowModeloOrdenTransporte = $bd->VerReg("ORDEN_TRANSPORTE", "ID_ORDEN_TRANSPORTE", $rowExpedicion->ID_ORDEN_TRANSPORTE);

                //SI EL ENVÍO ES A PROVEEDOR, NO SE SOLICITAN FACTURAS NO COMERCIALES, SE ENVÍA UN ZTLJ
                if ($rowExpedicion->SUBTIPO_ORDEN_RECOGIDA == 'Componentes a Proveedor' || $rowExpedicion->SUBTIPO_ORDEN_RECOGIDA == 'Material Estropeado a Proveedor' || $rowExpedicion->SUBTIPO_ORDEN_RECOGIDA == 'Material Estropeado Entre Proveedores'
                    || $rowExpedicion->SUBTIPO_ORDEN_RECOGIDA == 'Devoluciones a Proveedor' || $rowExpedicion->SUBTIPO_ORDEN_RECOGIDA == 'Rechazos Anulaciones Proveedor'):

                    $bd->begin_transaction();

                    //CREMOS EL PEDIDO ZTLJ FICTICIO
                    $idPedidoServicios = $this->generarZTLJFicticio($rowExpedicion->ID_ORDEN_TRANSPORTE);

                    //SI HEMOS CREADO PEDIDO ZTLJ
                    if ($idPedidoServicios != ""):

                        //BUSCAMOS EL PEDIDO DE SERVICIOS
                        $rowPedidoServicios = $bd->VerReg("PEDIDO_ENTRADA", "ID_PEDIDO_ENTRADA", $idPedidoServicios);

                        //ENVIO A SAP EL PEDIDO TRANSPORTE
                        $resultado = $sap->InformarSAPZTLJFicticio($rowPedidoServicios->ID_PEDIDO_ENTRADA);

                        if ($resultado['RESULTADO'] != 'OK'): //No se ha podido grabar la información en SAP

                            foreach ($resultado['ERRORES'] as $arr):
                                foreach ($arr as $mensaje_error):
                                    $strError['errores'] = $strError['errores'] . $mensaje_error . "<br>";
                                endforeach;
                            endforeach;

                            //DESHAGO LA TRANSACCION DE EXPEDICION Y GASTOS
                            $bd->rollback_transaction();

                            //GRABO EL ERROR PRODUCIDO EN BASE DE DATOS
                            $sap->InsertarErrores($resultado);


                            //GUARDAMOS QUE NECESITA REENVIAR GASTOS
                            $sqlUpdate = "UPDATE EXPEDICION SET NECESITA_REENVIAR_GASTOS = 1 WHERE ID_EXPEDICION = $rowExpedicion->ID_EXPEDICION";
                            $bd->ExecSQL($sqlUpdate);

                            $html->PagError("ErroresProducidosTransmisionSAPOrdenTransporte");

                        else:
                            //FINALIZO LA TRANSACCION
                            $bd->commit_transaction();


                            //INICIO LA TRANSACCION
                            $bd->begin_transaction();


                            if ($resultado['PEDIDO'] != ""):
                                $sqlUpdate = "UPDATE PEDIDO_ENTRADA SET
                                                    PEDIDO_SAP = '" . $resultado['PEDIDO'] . "'
                                                    , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                                    WHERE ID_PEDIDO_ENTRADA = $rowPedidoServicios->ID_PEDIDO_ENTRADA";
                                $bd->ExecSQL($sqlUpdate);

                                //INSERTAMOS LA FACTURA
                                $sqlInsert = "INSERT INTO FACTURA_NO_COMERCIAL SET
                                                      NUMERO_FACTURA = '" . $resultado['PEDIDO'] . "'
                                                      ,ID_ORDEN_TRANSPORTE = $rowExpedicion->ID_ORDEN_TRANSPORTE";
                                $bd->ExecSQL($sqlInsert);

                                //RECUPERAMOS EL ID
                                $idFacturaNoComercial = $bd->IdAsignado();

                                //RECORREMOS LAS LINEAS
                                $sqlLineasServicios    = "SELECT PEL.ID_MOVIMIENTO_SALIDA_LINEA_RELACIONADA_MUEVE_MATERIAL, PEL.LINEA_PEDIDO_SAP
                                                               FROM PEDIDO_ENTRADA_LINEA PEL
                                                               WHERE PEL.ID_PEDIDO_ENTRADA = $rowPedidoServicios->ID_PEDIDO_ENTRADA AND PEL.BAJA = 0";
                                $resultLineasServicios = $bd->ExecSQL($sqlLineasServicios);

                                while ($rowLineasServicios = $bd->SigReg($resultLineasServicios)):
                                    //INSERTAMOS LAS LINEAS
                                    $sqlInsert = "INSERT INTO FACTURA_NO_COMERCIAL_MOVIMIENTO SET
                                                          ID_FACTURA_NO_COMERCIAL = $idFacturaNoComercial
                                                          ,ID_MOVIMIENTO_SALIDA_LINEA = $rowLineasServicios->ID_MOVIMIENTO_SALIDA_LINEA_RELACIONADA_MUEVE_MATERIAL
                                                          ,NUMERO_POSICION_FACTURA = '$rowLineasServicios->LINEA_PEDIDO_SAP'";
                                    $bd->ExecSQL($sqlInsert);
                                endwhile;

                                //GUARDAMOS QUE GESTION FACTURAS
                                $sqlUpdate = "UPDATE EXPEDICION SET
                                                  GESTIONA_FACTURAS = 1
                                                  , ESTADO_FACTURA_NO_COMERCIAL = 'Pdte. Facturas'
                                                  WHERE ID_EXPEDICION = $rowExpedicion->ID_EXPEDICION";
                                $bd->ExecSQL($sqlUpdate);

                            endif;

                            //FINALIZO LA TRANSACCION
                            $bd->commit_transaction();
                        endif;
                    endif;
                //SI HEMOS CREADO PEDIDO ZTLJ

                elseif ($rowModeloOrdenTransporte->MODELO_TRANSPORTE == 'Segundo'):

                    $bd->begin_transaction();

                    //SE DAN DE BAJA LAS FACTURAS PREVIAS
                    $auxiliar->eliminarFacturasNoComerciales($rowExpedicion->ID_ORDEN_TRANSPORTE);

                    $resultadoSolicitudFacturas = $sap->SolicitudFacturas($rowExpedicion->ID_ORDEN_TRANSPORTE, 'X');
                    if ($resultadoSolicitudFacturas['RESULTADO'] != 'OK'):
                        if (count((array)$resultadoSolicitudFacturas['ERRORES']) > 0):
                            foreach ($resultadoSolicitudFacturas['ERRORES'] as $arr):
                                foreach ($arr as $mensaje_error):
                                    $strError = $strError . $mensaje_error . "<br>";
                                endforeach;
                            endforeach;
                        endif;

                        //DESHAGO LA TRANSACCION
                        $bd->rollback_transaction();

                        //GRABO EL ERROR PRODUCIDO EN BASE DE DATOS
                        $sap->InsertarErrores($resultadoSolicitudFacturas);

                    else:
                        //INICIO LA TRANSACCION
                        $bd->begin_transaction();

                        //SE RECORRE EL ARRAY DEVUELTO POR LA LLAMADA
                        foreach ($resultadoSolicitudFacturas['FACTURAS_DEVUELTAS'] as $arr_facturas):

                            $numFactura = $arr_facturas['FACTURA']; //NÚMERO FACTURA

                            //SE CREA LA FACTURA
                            $sqlInsert = "INSERT INTO FACTURA_NO_COMERCIAL 
                                          SET NUMERO_FACTURA = '" . $bd->escapeCondicional($numFactura) . "', 
                                              ID_ORDEN_TRANSPORTE = $rowExpedicion->ID_ORDEN_TRANSPORTE";
                            $bd->ExecSQL($sqlInsert);
                            $idFactura = $bd->IdAsignado();

                            foreach ($arr_facturas['DOCUMENTOS'] as $arr_documentos):
                                $numEntregaSaliente      = $arr_documentos['DOCUMENTO'];     //Nº Entrega Saliente
                                $numLineaEntregaSaliente = $arr_documentos['POSICION'];      //Nº Posicion Entrega Saliente
                                $numPosFactura           = $arr_documentos['POS_FACTURA'];   //Nº Posicion Factura

                                //COMPROBAMOS QUE VIENE RELLENO
                                $html->PagErrorCondicionado((($numEntregaSaliente == "") || ($numLineaEntregaSaliente == "") || ($numPosFactura == "")), "==", true, "RespuestaFacturaIncompleta");

                                $rowExpEntSal = $bd->VerReg("EXPEDICION_ENTREGA_SALIENTE", "NUMERO_ENTREGA_SALIENTE", $numEntregaSaliente);

                                //SE OBTIENEN LAS LÍNEAS DE LOS PEDIDOS ASOCIADAS A LA ENTREGA SALIENTE Y POSICION
                                $sqlExpEntSalPos    = "SELECT *
                                            FROM EXPEDICION_ENTREGA_SALIENTE_POSICION
                                            WHERE ID_EXPEDICION_ENTREGA_SALIENTE = $rowExpEntSal->ID_EXPEDICION_ENTREGA_SALIENTE AND LINEA_ENTREGA_SALIENTE = '$numLineaEntregaSaliente'";
                                $resultExpEntSalPos = $bd->ExecSQL($sqlExpEntSalPos);
                                while ($rowExpEntSalPos = $bd->SigReg($resultExpEntSalPos)):
                                    //RECORREMOS LOS MOVIMIENTOS SALIDA LINEA
                                    $sqlMovimientosSalidaLinea    = "SELECT DISTINCT ID_MOVIMIENTO_SALIDA_LINEA
                                                              FROM MOVIMIENTO_SALIDA_LINEA
                                                              WHERE ID_PEDIDO_SALIDA_LINEA = $rowExpEntSalPos->ID_PEDIDO_SALIDA_LINEA AND ID_EXPEDICION = $rowExpedicion->ID_EXPEDICION AND BAJA = 0";
                                    $resultMovimientosSalidaLinea = $bd->ExecSQL($sqlMovimientosSalidaLinea);
                                    while ($rowMovimientosSalidaLinea = $bd->SigReg($resultMovimientosSalidaLinea)):
                                        $NotificaErrorPorEmail           = "No";
                                        $rowFacturaNoComercialMovimiento = $bd->VerRegRest("FACTURA_NO_COMERCIAL_MOVIMIENTO", "ID_FACTURA_NO_COMERCIAL = $idFactura AND BAJA = 0 AND ID_MOVIMIENTO_SALIDA_LINEA = $rowMovimientosSalidaLinea->ID_MOVIMIENTO_SALIDA_LINEA", "No");

                                        //SI EL MOVIMIENTO NO TIENE FACTURA, LO GUARDAMOS Y CONTINUAMOS
                                        if ($rowFacturaNoComercialMovimiento == false):
                                            //GUARDAMOS EL MOVIMIENTO Y LA FACTURA
                                            $sqlInsert = "INSERT INTO FACTURA_NO_COMERCIAL_MOVIMIENTO 
                                      SET ID_FACTURA_NO_COMERCIAL = $idFactura, 
                                          ID_MOVIMIENTO_SALIDA_LINEA = $rowMovimientosSalidaLinea->ID_MOVIMIENTO_SALIDA_LINEA, 
                                          NUMERO_POSICION_FACTURA = '" . $numPosFactura . "'";
                                            $bd->ExecSQL($sqlInsert);
                                        endif;
                                    endwhile;
                                endwhile;

                                //SE ACTUALIZA EL ESTADO DE LAS FACTURAS NO COMERCIALES
                                $sqlUpdateEstadoFacturas = "UPDATE EXPEDICION
                                            SET GESTIONA_FACTURAS = 1,
                                                ESTADO_FACTURA_NO_COMERCIAL = 'Recibida'
                                            WHERE ID_EXPEDICION = $rowExpedicion->ID_EXPEDICION";
                                $bd->ExecSQL($sqlUpdateEstadoFacturas);
                                //FIN RECORREMOS LOS MOVIMIENTOS SALIDA LINEA

                            endforeach;
                        endforeach;
                        //RECORREMOS EL ARRAY DEVUELTO POR LA LLAMADA

                        //FINALIZO LA TRANSACCION
                        $bd->commit_transaction();
                    endif;

                    $bd->commit_transaction();

                endif;

            endif;
            /**************************************** FIN SOLICITAR FACTURAS NO COMERCIALES ****************************************/

            /********************************************** ACCIONES INFORMACION BLOQUEO PEDIDOS **********************************************/
            //INICIO LA TRANSACCION PARA ENVIAR EL BLOQUEO/DESBLOQUEO DE LAS LINEAS IMPLICADAS
            $bd->begin_transaction();

            //INFORMO A SAP DE LAS LINEAS BLOQUEADAS
            if (count((array)$arrayLineasPedidosInvolucradas) > 0):
                $resultado = $pedido->controlBloqueoLinea("Salida", 'TransmitirExpedicionASAP', implode(",", (array)$arrayLineasPedidosInvolucradas));
                if ($resultado['RESULTADO'] != 'OK'): //No se ha podido grabar la información en SAP
                    if (count((array)$resultado['ERRORES']) > 0):
                        foreach ($resultado['ERRORES'] as $arrayDeErrores):
                            foreach ($arrayDeErrores as $mensaje_error):
                                $arr['errores'] = $arr['errores'] . $mensaje_error . "<br>";
                            endforeach;
                        endforeach;
                    endif;

                    //DESHAGO LA TRANSACCION
                    $bd->rollback_transaction();

                    //GRABO EL ERROR PRODUCIDO EN BASE DE DATOS
                    $sap->InsertarErrores($resultado);
                endif;
            endif;


            //FINALIZO LA TRANSACCION PARA ENVIAR EL BLOQUEO/DESBLOQUEO DE LAS LINEAS IMPLICADAS
            $bd->commit_transaction();
            /******************************************** FIN ACCIONES INFORMACION BLOQUEO PEDIDOS ********************************************/


        endwhile;// FIN RECORRO LAS RECOGIDAS EN ALMACEN A TRANSMITIR A SAP


        //ACTUALIZAMOS EL ESTADO DE LA ORDEN DE TRANSPORTE
        //INICIO UNA TRANSACCION
        $bd->begin_transaction();


        //OBTENGO EL ESTADO FINAL DE LA INTERFACE(Si hay recogidas en Almacén, Lo calculamos segun el estado de éstas, si no , lo ponemos a Transmitido)
        if ($numRecogidasAlmacenATransmitir > 0):
            $estadoFinalInterfaces = $this->EstadoTransporteSegunRecogidas($rowOrdenTransporte->ID_ORDEN_TRANSPORTE);
        else:
            $estadoFinalInterfaces = 'Recogidas Transmitidas';
        endif;


        //SI EL ESTADO FINAL ES RECOGIDAS TRANSMITIDAS, AVANZAMOS LOS ESTADOS DE LAS RECOGIDAS DISTINTAS DE EN ALMACEN (y  crearemos los ZTL y actualizaremos costes)
        if ($estadoFinalInterfaces == 'Recogidas Transmitidas'):
            $sqlUpdate = "UPDATE EXPEDICION SET ESTADO = 'Transmitida a SAP' WHERE ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND TIPO_ORDEN_RECOGIDA <> 'Recogida en Almacen'";
            $bd->ExecSQL($sqlUpdate);

            //BUSCO LAS RECOGIDAS FUERA DE SISTEMA, AGRUPADAS POR ELEMENTO DE IMPUTACION
            $sqlRecogidasFueraSistema    = "SELECT ID_ELEMENTO_IMPUTACION, CODIGO_SELECCIONADO_ELEMENTO_IMPUTACION
                                             FROM EXPEDICION
                                             WHERE TIPO_ORDEN_RECOGIDA = 'Operaciones fuera de Sistema' AND ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND BAJA = 0
                                             GROUP BY ID_ELEMENTO_IMPUTACION, CODIGO_SELECCIONADO_ELEMENTO_IMPUTACION";
            $resultRecogidasFueraSistema = $bd->ExecSQL($sqlRecogidasFueraSistema);

            //BUSCO LAS RECOGIDAS DE OPERACIONES EN PARQUE, AGRUPADAS POR ORDEN DE TRABAJO
            $sqlRecogidasParque    = "SELECT OT.ID_ORDEN_TRABAJO, OT.ID_CENTRO
                                       FROM EXPEDICION E
                                       INNER JOIN ORDEN_TRABAJO_MOVIMIENTO OTM ON OTM.ID_EXPEDICION = E.ID_EXPEDICION
                                       INNER JOIN ORDEN_TRABAJO OT ON OT.ID_ORDEN_TRABAJO = OTM.ID_ORDEN_TRABAJO
                                       WHERE E.ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND E.BAJA = 0
                                       GROUP BY OTM.ID_ORDEN_TRABAJO";
            $resultRecogidasParque = $bd->ExecSQL($sqlRecogidasParque);

            //BUSCO SI TIENE RECOGIDAS CON ENVIOS Y COMPONENTES A PROVEEDOR (ZTLI)
            $sqlPedidoZTLI    = "SELECT MSL.ID_MOVIMIENTO_SALIDA_LINEA, MSL.ID_ALMACEN, MSL.ID_MATERIAL, MSL.ID_TIPO_BLOQUEO, PSL.ID_UNIDAD, MSL.CANTIDAD
                                  FROM MOVIMIENTO_SALIDA_LINEA MSL
                                  INNER JOIN EXPEDICION E ON E.ID_EXPEDICION = MSL.ID_EXPEDICION
                                  INNER JOIN PEDIDO_SALIDA PS ON PS.ID_PEDIDO_SALIDA = MSL.ID_PEDIDO_SALIDA
                                  INNER JOIN PEDIDO_SALIDA_LINEA PSL ON PSL.ID_PEDIDO_SALIDA_LINEA = MSL.ID_PEDIDO_SALIDA_LINEA
                                  WHERE E.ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND MSL.LINEA_ANULADA = 0 AND MSL.BAJA = 0 AND (PS.TIPO_PEDIDO = 'Componentes a Proveedor' OR PS.TIPO_PEDIDO = 'Material Estropeado a Proveedor' OR PS.TIPO_PEDIDO = 'Material Estropeado Entre Proveedores' OR PS.TIPO_PEDIDO = 'Devolución a Proveedor')";
            $resultPedidoZTLI = $bd->ExecSQL($sqlPedidoZTLI);

            //GENERAMOS LOS PEDIDOS ZTL EN CASO DE TENER RECOGIDAS QUE LOS NECESITEN
            if (
                ((($bd->NumRegs($resultRecogidasFueraSistema) > 0) || ($bd->NumRegs($resultRecogidasParque) > 0) || ($bd->NumRegs($resultPedidoZTLI) > 0)) && ($rowOrdenTransporte->MODELO_TRANSPORTE == 'Primero')) ||
                ($rowOrdenTransporte->MODELO_TRANSPORTE == 'Segundo')
            ):
                $this->generarPedidosZTL($rowOrdenTransporte->ID_ORDEN_TRANSPORTE);
            endif;

            //LLAMO A LA FUNCION PARA CONVERTIR MONEDAS SI PROCEDE Y ACTUALIZAR IMPORTES DE LAS ORDENES DE CONTRATACION Y DE LA ORDEN DE TRANSPORTE(LLAMADAS A SAP INTERNAS)
            $arrResultado = $importe->actualizarImportes($rowOrdenTransporte->ID_ORDEN_TRANSPORTE);

            //ACCIONES SEGUN SE EJECUTE LA FUNCION
            if ($arrResultado['OCURRIO_ERROR'] == false): //SI LA EJECUCION DE LA FUNCION ES CORRECTA ACTUALIZO EL LOG MOVIMIENTOS
                // LOG MOVIMIENTOS
                $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Orden de Transporte", $rowOrdenTransporte->ID_ORDEN_TRANSPORTE, "Convertir monedas si procede y actualizar importes");
            elseif ($arrResultado['OCURRIO_ERROR'] == true): //ERROR EN LA EJECUCION DE LA FUNCION, MUESTRO LOS ERRORES
                global $strError;
                $strError = $arrResultado['TEXTO_ERROR'];

                //DESHAGO LA TRANSACCION QUE ACTUALIZA IMPORTES Y ESTADOS DE RECOGIDAS DISTINTAS DE EN ALMACEN Y PEDIDOS ZTL
                $bd->rollback_transaction();

                //PONGO LA ORDEN DE TRANSPORTE COMO "En Transmisión" (Las recogidas en almacén estarán transmitidas, pero faltará crear los ZTL y actualizar importes)
                $sqlUpdate = "UPDATE ORDEN_TRANSPORTE SET ESTADO_INTERFACES = 'Recogidas en Transmision' WHERE ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE";
                $bd->ExecSQL($sqlUpdate);

                $html->PagError("ErrorSAP");
            endif;
            //FIN ACCIONES SEGUN SE EJECUTE LA FUNCION


        endif;

        $sqlUpdate = "UPDATE ORDEN_TRANSPORTE SET
                             ESTADO_INTERFACES = '$estadoFinalInterfaces'
                            ,ID_ADMINISTRADOR_ULTIMA_MODIFICACION = " . $administrador->ID_ADMINISTRADOR . "
                            , FECHA_ULTIMA_MODIFICACION = '" . date('Y-m-d H:i:s') . "'
                        WHERE ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE";
        $bd->ExecSQL($sqlUpdate);

        //ACTUALIZAMOS EL ESTADO DEL TRANSPORTE
        $this->actualizarEstadoOrdenTransporte($rowOrdenTransporte->ID_ORDEN_TRANSPORTE);

        // LOG MOVIMIENTOS
        $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Orden de Transporte", $rowOrdenTransporte->ID_ORDEN_TRANSPORTE, "Transmitir Recogidas a SAP");


        //FINALIZO TRANSACCION
        $bd->commit_transaction();

        return $arr;

    }

    /**
     * @param $estadoInicial ESTADO INICIAL DE LA OT
     * @param $estadoFinal ESTADO FINAL DE LA OT
     * FUNCION UTILIZADA PARA CREAR EVENTOS BLOCKCHAIN DE MANERA PARAMETRIZADA
     */
    /*function crearEventosParametrizados($estadoInicial = "", $estadoFinal, $rowOrdenTransporte)
    {

        global $bd;
        global $auxiliar;
        global $administrador;
        global $NotificaErrorPorEmail;
        global $pathClases;
        global $selEstadoMaterial;
        global $selTipoContenido;

        //OBTENEMOS EL ROL SEGÚN EL ESTADO FINAL Y REALIZAMOS LAS ACTUACIONES INICIALES NECESARIAS
        $rol = "";
        if (($estadoFinal == "Creada") ||($estadoFinal == "Entregado a Forwarder") || ($estadoFinal == "Preparado en Proveedor")):

            if ($estadoFinal == "Entregado a Forwarder"):
                //SI EL ESTADO FINAL ES 'Entregado a Forwarder' ACTULIZO LA FECHA_REAL_CONFIRMAR_MATERIAL_PREPARADO
                $sqlUpdate = "UPDATE ORDEN_TRANSPORTE_CONSTRUCCION SET FECHA_REAL_CONFIRMAR_MATERIAL_PREPARADO = '" . date("Y-m-d H:i:s") . "' WHERE ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE";
                $bd->ExecSQL($sqlUpdate);
            endif;

            //OBTENEMOS EL ROL
            $rol = "Proveedor";

        elseif (($estadoFinal == "Puerto Origen") || ($estadoFinal == "Transito Internacional") || ($estadoFinal == "Puerto Destino")):

            if ($estadoFinal == "Puerto Destino"):
                //SI EL SIGUIENTE ESTADO ES 'Puerto Destino', INICIAMOS EL Ciclo Entrega en Obra
                $sqlUpdate = "UPDATE ORDEN_TRANSPORTE SET ESTADO_ENTREGA_OBRA = 'En Terminal' WHERE ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE";
                $bd->ExecSQL($sqlUpdate);
            endif;

            //OBTENEMOS EL ROL
            $rol = "Forwarder";

        elseif (($estadoFinal == "Liberado Aduana") || ($estadoFinal == "Aduana Destino")):

            //OBTENEMOS EL ROL
            $rol = "Agente aduanal";

        elseif (($estadoFinal == "Transito Local") || ($estadoFinal == "Entregado") || ($estadoFinal == "Recepcionado")):

            //OBTENEMOS EL ROL
            $rol = "Transportista InLand";

        endif;

        //OBTENGO EL OBJETO DE BLOCKCHAIN SEGÚN EL ESTADO FINAL
        $NotificaErrorPorEmail = "No";
        $rowObjetoBlockchain   = $bd->VerRegRest("BLOCKCHAIN_OBJETO", "TIPO_EVENTO = '$estadoFinal' AND BAJA = 0", "No");

        //SI EXISTE EL OBJETO BLOCKCHAIN, CREAMOS LOS EVENTOS NECESARIOS
        if ($rowObjetoBlockchain):

            //COMPRUEBO SI YA EXISTE ESE EVENTO PARA LA OTC Y LO DOY DE BAJA
            $sqlEventoDuplicado    = "SELECT ID_EVENTO
                                  FROM EVENTO
                                  WHERE TIPO_EVENTO = '" . $bd->escapeCondicional($rowObjetoBlockchain->TIPO_EVENTO) . "' AND TIPO_OBJETO = 'Orden transporte' AND ID_OBJETO = '$rowOrdenTransporte->ID_ORDEN_TRANSPORTE' AND BAJA = 0";
            $resultEventoDuplicado = $bd->ExecSQL($sqlEventoDuplicado);
            $numDuplicados         = $bd->NumRegs($resultEventoDuplicado);

            if ($numDuplicados > 0):
                while ($rowEventoDuplicado = $bd->SigReg($resultEventoDuplicado)):
                    $sqlUpdate = "UPDATE EVENTO_PENDIENTE_TRANSMITIR SET BAJA = 1 WHERE ID_EVENTO = $rowEventoDuplicado->ID_EVENTO";
                    $bd->ExecSQL($sqlUpdate);

                    //MODIFICO LOS DATOS DEL TRANSBORDO
                    $sqlEventoTransbordoDuplicado    = "SELECT ID_EVENTO_DATOS_TRANSBORDO
                                                          FROM EVENTO_DATOS_TRANSBORDO
                                                          WHERE ID_EVENTO = $rowEventoDuplicado->ID_EVENTO AND BAJA = 0";
                    $resultEventoTransbordoDuplicado = $bd->ExecSQL($sqlEventoTransbordoDuplicado);

                    while ($rowEventoTransbordoDuplicado = $bd->SigReg($resultEventoTransbordoDuplicado)):
                        $sqlUpdate = "UPDATE EVENTO_DATOS_TRANSBORDO SET BAJA = 1 WHERE ID_EVENTO_DATOS_TRANSBORDO = $rowEventoTransbordoDuplicado->ID_EVENTO_DATOS_TRANSBORDO";
                        $bd->ExecSQL($sqlUpdate);
                    endwhile;

                    //$sqlUpdate = "UPDATE EVENTO_DATOS SET BAJA = 1 WHERE ID_EVENTO = $rowEventoDuplicado->ID_EVENTO";
                    //$bd->ExecSQL($sqlUpdate);
                    $sqlUpdate = "UPDATE EVENTO_DATOS_PARAMETRIZADOS SET BAJA = 1 WHERE ID_EVENTO = $rowEventoDuplicado->ID_EVENTO";
                    $bd->ExecSQL($sqlUpdate);

                    $sqlUpdate = "UPDATE EVENTO_DOCUMENTO SET BAJA = 1 WHERE ID_EVENTO = $rowEventoDuplicado->ID_EVENTO";
                    $bd->ExecSQL($sqlUpdate);

                    $sqlUpdate = "UPDATE EVENTO SET BAJA = 1 WHERE ID_EVENTO = $rowEventoDuplicado->ID_EVENTO";
                    $bd->ExecSQL($sqlUpdate);
                endwhile;
            endif;

            // CREACION DE EVENTO PARA REGISTRAR EN BLOCKCHAIN
            $fecha_actual = date("Y-m-d H:i:s");
            $sqlInsert    = "INSERT INTO EVENTO SET
                          TIPO_EVENTO = '" . $bd->escapeCondicional($estadoFinal) . "'
                          , ID_ADMINISTRADOR = '" . $administrador->ID_ADMINISTRADOR . "'
                          , ROL_ADMINISTRADOR = '" . $bd->escapeCondicional($rol) . "'
                          , TIPO_OBJETO = 'Orden transporte'
                          , ID_OBJETO = '" . $rowOrdenTransporte->ID_ORDEN_TRANSPORTE . "'
                          , FECHA_CREACION = '" . $fecha_actual . "'
                          , FECHA_ULTIMA_MODIFICACION = '" . $fecha_actual . "'
                          , BAJA = 0";
            $bd->ExecSQL($sqlInsert);
            $idEvento = $bd->IdAsignado();

            $rowEvento = $bd->VerReg("EVENTO", "ID_EVENTO", $idEvento);

            //OBTENGO LOS CAMPOS DEL OBJETO BLOCKCHAIN
            $sqlCamposObjetoBlockchain    = "SELECT C.NOMBRE_MOSTRAR, C.NOMBRE_CAMPO
                                        FROM BLOCKCHAIN_OBJETO_CAMPO BOC
                                            INNER JOIN CAMPO_SELECCIONABLE_BLOCKCHAIN C ON C.ID_CAMPO_SELECCIONABLE_BLOCKCHAIN = BOC.ID_CAMPO_SELECCIONABLE_BLOCKCHAIN
                                        WHERE BOC.ID_BLOCKCHAIN_OBJETO = $rowObjetoBlockchain->ID_BLOCKCHAIN_OBJETO AND BOC.BAJA = 0";
            $resultCamposObjetoBlockchain = $bd->ExecSQL($sqlCamposObjetoBlockchain);

            //VAMOS INSERTANDO LOS CAMPOS Y SUS VALORES EN LA TABLA EVENTO_DATOS_PARAMETRIZADOS
            $fieldName  = array();
            $fieldValue = array();

            while ($rowCamposObjetoBlockchain = $bd->SigReg($resultCamposObjetoBlockchain)):

                $sqlInsert = "INSERT INTO EVENTO_DATOS_PARAMETRIZADOS SET
                      ID_EVENTO = '" . $idEvento . "'
                      , NOMBRE_MOSTRAR = '" . $bd->escapeCondicional($rowCamposObjetoBlockchain->NOMBRE_MOSTRAR) . "'
                      , NOMBRE_CAMPO = '" . $bd->escapeCondicional($rowCamposObjetoBlockchain->NOMBRE_CAMPO) . "'
                      , VALOR_CAMPO = '" . $bd->escapeCondicional($rowOrdenTransporte->{$rowCamposObjetoBlockchain->NOMBRE_CAMPO}) . "'";
                $bd->ExecSQL($sqlInsert);
                $idEventoDatos = $bd->IdAsignado();

                //CREAMOS LOS ARRAYS
                if (($rowCamposObjetoBlockchain->NOMBRE_CAMPO == "BARCO") || ($rowCamposObjetoBlockchain->NOMBRE_CAMPO == "NAVIERA")):

                    //SI EL CAMPO ES EL BARCO O LA NAVIERA, ENTONCES REGISTRO LOS DATOS DE LOS TRANSBORDOS
                    $sqlTransbordos    = "SELECT OTT.*, B.NOMBRE AS BARCO_NOMBRE, N.NOMBRE AS NAVIERA_NOMBRE
                                FROM ORDEN_TRANSPORTE_TRANSBORDO OTT
                                INNER JOIN BARCO B ON B.ID_BARCO = OTT.ID_BARCO
                                INNER JOIN NAVIERA N ON N.ID_NAVIERA = OTT.ID_NAVIERA
                                WHERE OTT.ID_ORDEN_TRANSPORTE = " . $rowOrdenTransporte->ID_ORDEN_TRANSPORTE;
                    $resultTransbordos = $bd->ExecSQL($sqlTransbordos);

                    $nombreBarcos = array();

                    while ($rowTransbordo = $bd->SigReg($resultTransbordos)):

                        $nombreBarcos[]   = $rowTransbordo->BARCO_NOMBRE;
                        $nombreNavieras[] = $rowTransbordo->NAVIERA_NOMBRE;

                        //COMPRUEBO SI EL EVENTO YA TIENE TRANSBORDOS CON EL MISMO ID_ORDEN_TRANSPORTE_TRANSBORDO
                        $sqlTransbordosEvento    = "SELECT *
                                             FROM EVENTO_DATOS_TRANSBORDO
                                             WHERE ID_EVENTO = $idEvento AND ID_ORDEN_TRANSPORTE_TRANSBORDO = $rowTransbordo->ID_ORDEN_TRANSPORTE_TRANSBORDO AND BAJA = 0";
                        $resultTransbordosEvento = $bd->ExecSQL($sqlTransbordosEvento);

                        if ($bd->NumRegs($resultTransbordosEvento) == 0):
                            $sqlInsert = "INSERT INTO EVENTO_DATOS_TRANSBORDO SET
                                      ID_EVENTO = '" . $idEvento . "'
                                      , ID_ORDEN_TRANSPORTE_TRANSBORDO  = '" . $rowTransbordo->ID_ORDEN_TRANSPORTE_TRANSBORDO . "'
                                      , ID_BARCO  = '" . ($rowTransbordo->ID_BARCO != '' ? $rowTransbordo->ID_BARCO : '') . "'
                                      , BARCO_NOMBRE  = '" . ($rowTransbordo->BARCO_NOMBRE != '' ? $bd->escapeCondicional($rowTransbordo->BARCO_NOMBRE) : '') . "'
                                      , ID_NAVIERA  = '" . ($rowTransbordo->ID_NAVIERA != '' ? $rowTransbordo->ID_NAVIERA : '') . "'
                                      , NAVIERA_NOMBRE  = '" . ($rowTransbordo->NAVIERA_NOMBRE != '' ? $bd->escapeCondicional($rowTransbordo->NAVIERA_NOMBRE) : '') . "'
                                      , BAJA = 0";
                            $bd->ExecSQL($sqlInsert);
                        endif;

                    endwhile;

                    //AÑADIMOS LOS BARCOS Y LAS NAVIERAS A LOS ARRAYS
                    $fieldName[] = "BARCOS";
                    $fieldName[] = "NAVIERAS";

                    $fieldValue[] = $nombreBarcos;
                    $fieldValue[] = $nombreNavieras;

                else:
                    $fieldName[]  = $rowCamposObjetoBlockchain->NOMBRE_CAMPO;
                    $fieldValue[] = $rowOrdenTransporte->{$rowCamposObjetoBlockchain->NOMBRE_CAMPO};
                endif;

            endwhile;

            if ($idEvento != '' && $idEventoDatos != ''):
                //CREAMOS EL EVENTO
                if (($estadoFinal == "Puerto Destino") || ($estadoFinal == "Entregado")):
                    $idEventoPendienteTransmitir = $auxiliar->crear_evento_pendiente_transmitir($rowEvento->ID_EVENTO, $fieldName, $fieldValue, $rowOrdenTransporte->FECHA_ENTREGA_REAL);
                else:
                    $idEventoPendienteTransmitir = $auxiliar->crear_evento_pendiente_transmitir($rowEvento->ID_EVENTO, $fieldName, $fieldValue);
                endif;
            endif;

            //CREAMOS LOS EVENTOS DE DOCUMENTOS

            //OBTENGO LOS CAMPOS DEL OBJETO BLOCKCHAIN
            $sqlDocumentosObjetoBlockchain    = "SELECT BOD.ID_DOCUMENTO_SELECCIONABLE_BLOCKCHAIN, D.TIPO_DOCUMENTO, D.RUTA, D.NOMBRE_CAMPO, D.SECCION_FICHERO
                                        FROM BLOCKCHAIN_OBJETO_DOCUMENTO BOD
                                        INNER JOIN DOCUMENTO_SELECCIONABLE_BLOCKCHAIN D ON D.ID_DOCUMENTO_SELECCIONABLE_BLOCKCHAIN = BOD.ID_DOCUMENTO_SELECCIONABLE_BLOCKCHAIN
                                        WHERE BOD.ID_BLOCKCHAIN_OBJETO = $rowObjetoBlockchain->ID_BLOCKCHAIN_OBJETO AND BOD.BAJA = 0";
            $resultDocumentosObjetoBlockchain = $bd->ExecSQL($sqlDocumentosObjetoBlockchain);

            //RECORREMOS LOS DOCUMENTOS ASOCIADOS A REGISTRAR PARA ESE OBJETO BLOCKCHAIN
            while($rowDocumentosObjetoBlockchain = $bd->SigReg($resultDocumentosObjetoBlockchain)):
                //FACTUA ES UN CASO ESPECIAL AL PODER ESTAR EL NOMBRE DEL DOCUMENTO EN VARIAS COLUMNAS DISTINTAS
                if($rowDocumentosObjetoBlockchain->TIPO_DOCUMENTO == 'Factura'):

                    $factura = '';
                    //COMPONENTE USADO
                    if (($selEstadoMaterial == "Usado" || $selEstadoMaterial == "Ambos") && ($selTipoContenido == "Componente" || $selTipoContenido == "Ambos")):
                        $factura = $rowOrdenTransporte->ADJUNTO_FACTURA_COMPONENTE_USADO;
                    //COMPONENTE NUEVO
                    elseif (($selEstadoMaterial == "Nuevo" || $selEstadoMaterial == "Ambos") && ($selTipoContenido == "Componente" || $selTipoContenido == "Ambos")):
                        $factura = $rowOrdenTransporte->ADJUNTO_FACTURA_COMPONENTE_NUEVO;
                    //UTIL USADO
                    elseif (($selEstadoMaterial == "Usado" || $selEstadoMaterial == "Ambos") && ($selTipoContenido == "Util" || $selTipoContenido == "Ambos")):
                        $factura = $rowOrdenTransporte->ADJUNTO_FACTURA_UTIL_USADO;
                    //UTIL NUEVO
                    elseif (($selEstadoMaterial == "Nuevo" || $selEstadoMaterial == "Ambos") && ($selTipoContenido == "Util" || $selTipoContenido == "Ambos")):
                        $factura = $rowOrdenTransporte->ADJUNTO_FACTURA_UTIL_NUEVO;
                    endif;

                    //COMPRUEBO SI TIENE FACTURA PARA CREAR EVENTO_DOCUMENTO
                    if ($factura != ''):
                        $docPL = $pathClases . $rowDocumentosObjetoBlockchain->RUTA . $factura;
                        if (file_exists($docPL) == 1): // HAY DOCUMENTO
                            //CREAMOS EL EVENTO DEL DOCUMENTO
                            $idEventoPendienteTransmitir = $auxiliar->crear_evento_documento_pendiente_transmitir($rowEvento->ID_EVENTO, 'Factura', $factura);
                        endif;
                    endif;
                else:
                    //SOLO EXISTE UN FICHERO DE ESE TIPO
                    if($rowDocumentosObjetoBlockchain->NOMBRE_CAMPO != ""):
                        $doc = $pathClases . $rowDocumentosObjetoBlockchain->RUTA . $rowOrdenTransporte->{$rowDocumentosObjetoBlockchain->NOMBRE_CAMPO};
                        if (file_exists($doc) == 1): // HAY DOCUMENTO
                            //CREAMOS EL EVENTO DEL DOCUMENTO
                            $idEventoPendienteTransmitir = $auxiliar->crear_evento_documento_pendiente_transmitir($rowEvento->ID_EVENTO, $rowDocumentosObjetoBlockchain->TIPO_DOCUMENTO, $rowOrdenTransporte->{$rowDocumentosObjetoBlockchain->NOMBRE_CAMPO});
                        endif;

                    //EXISTE MAS DE UN FICHERO DE ESE TIPO
                    else:
                        $sqlDocumentosTipo       = "SELECT * FROM FICHERO WHERE TIPO_OBJETO='OrdenTransporte' AND ID_ORDEN_TRANSPORTE=$rowEvento->ID_OBJETO AND SECCION ='".$rowDocumentosObjetoBlockchain->SECCION_FICHERO."'";
                        $resultadoDocumentosTipo = $bd->ExecSQL($sqlDocumentosTipo);
                        $numDocTipo                = $bd->NumRegs($resultadoDocumentosTipo);

                        if ($numDocTipo > 0):
                            while ($rowFichero = $bd->SigReg($resultadoDocumentosTipo)):
                                $doc = $pathClases . $rowDocumentosObjetoBlockchain->RUTA . $rowFichero->FICHERO;
                                if (file_exists($doc) == 1): // HAY DOCUMENTO
                                    //CREAMOS EL EVENTO DEL DOCUMENTO
                                    $idEventoPendienteTransmitir = $auxiliar->crear_evento_documento_pendiente_transmitir($rowEvento->ID_EVENTO, $rowDocumentosObjetoBlockchain->TIPO_DOCUMENTO, $rowFichero->FICHERO);
                                endif;
                            endwhile;
                        endif;
                    endif;
                endif;

            endwhile;
        endif;

    }*/

    /**
     * @param $estadoInicial ESTADO INICIAL DE LA OT
     * @param $estadoFinal ESTADO FINAL DE LA OT
     * FUNCION UTILIZADA PARA ELIMINAR EVENTOS BLOCKCHAIN DE MANERA PARAMETRIZADA
     */
    /*function eliminarEventosParametrizados($estadoInicial = "", $estadoFinal, $rowOrdenTransporte)
    {

        global $bd;
        global $auxiliar;
        global $NotificaErrorPorEmail;
        global $observaciones_sistema;

        //REALIZAMOS LAS ACTUACIONES INICIALES NECESARIAS
        if ($estadoFinal == "Preparado en Proveedor"):

            //SI EL ESTADO final 'Preparado en Proveedor' BORRO LA FECHA_REAL_CONFIRMAR_MATERIAL_PREPARADO
            $sqlUpdate = "UPDATE ORDEN_TRANSPORTE_CONSTRUCCION SET FECHA_REAL_CONFIRMAR_MATERIAL_PREPARADO = NULL WHERE ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE";
            $bd->ExecSQL($sqlUpdate);

        elseif ($estadoInicial == "Puerto Destino"):

            //SI EL ANTERIOR ESTADO ES 'Puerto Destino', REVERTIMOS EL Ciclo Entrega en Obra
            $sqlUpdate = "UPDATE ORDEN_TRANSPORTE SET ESTADO_ENTREGA_OBRA = 'No Aplica' WHERE ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE";
            $bd->ExecSQL($sqlUpdate);

        endif;

        //COMPRUEBO SI EXISTE ESE EVENTO PARA LA OTC Y LO DOY DE BAJA
        $sqlEvento    = "SELECT *
                      FROM EVENTO
                      WHERE TIPO_EVENTO = '" . $bd->escapeCondicional($estadoInicial) . "' AND TIPO_OBJETO = 'Orden transporte' AND ID_OBJETO = '$rowOrdenTransporte->ID_ORDEN_TRANSPORTE' AND BAJA = 0";
        $resultEvento = $bd->ExecSQL($sqlEvento);
        $numEvento    = $bd->NumRegs($resultEvento);

        if ($numEvento > 0):
            while ($rowEvento = $bd->SigReg($resultEvento)):
                $NotificaErrorPorEmail = "No";
                $sqlUpdate             = "UPDATE EVENTO_PENDIENTE_TRANSMITIR SET BAJA = 1 WHERE ID_EVENTO = $rowEvento->ID_EVENTO";
                $bd->ExecSQL($sqlUpdate, "No");

                //DOY DE BAJA LOS EVENTOS DE TRANSBORDO
                $sqlEventoTransbordo    = "SELECT *
                                              FROM EVENTO_DATOS_TRANSBORDO
                                              WHERE ID_EVENTO = $rowEvento->ID_EVENTO AND BAJA = 0";
                $resultEventoTransbordo = $bd->ExecSQL($sqlEventoTransbordo);

                while ($rowEventoTransbordo = $bd->SigReg($resultEventoTransbordo)):
                    $sqlUpdate = "UPDATE EVENTO_DATOS_TRANSBORDO SET BAJA = 1 WHERE ID_EVENTO_DATOS_TRANSBORDO = $rowEventoTransbordo->ID_EVENTO_DATOS_TRANSBORDO";
                    $bd->ExecSQL($sqlUpdate, "No");

                endwhile;

                $fecha_finalizacion = date("Y-m-d H:i:s");

                $sqlEventoDocumento    = "SELECT *
                      FROM EVENTO_DOCUMENTO
                      WHERE ID_EVENTO = $rowEvento->ID_EVENTO AND BAJA = 0";
                $resultEventoDocumento = $bd->ExecSQL($sqlEventoDocumento);

                while ($rowEventoDocumento = $bd->SigReg($resultEventoDocumento)):

                    //LO BORRAMOS DE BLOCKCHAIN
                    if ($rowEventoDocumento->HASH != ''):

                        //CREAMOS LA PETICION DE BORRADO DEL EVENTO_DOCUMENTO
                        $idEventoPendienteTransmitir = $auxiliar->eliminar_evento_documento_pendiente_transmitir($rowEvento->ID_EVENTO, $rowEventoDocumento->ID_EVENTO_DOCUMENTO);

                    endif;


                    //COMPRUEBO SI TIENE INCIDENCIAS EL DOCUMENTO, EN CASO AFIRMATIVO, LAS CIERRO
                    $sqlIncidenciaEventoDocumento    = "SELECT *
                      FROM INCIDENCIA_BLOCKCHAIN
                      WHERE TABLA_OBJETO = 'EVENTO_DOCUMENTO' AND ID_OBJETO = $rowEventoDocumento->ID_EVENTO_DOCUMENTO AND (ESTADO = 'Creada' OR ESTADO = 'En Proceso')";
                    $resultIncidenciaEventoDocumento = $bd->ExecSQL($sqlIncidenciaEventoDocumento);

                    while ($rowIncidenciaEventoDocumento = $bd->SigReg($resultIncidenciaEventoDocumento)):
                        //CREO UNA OBSERVACION PARA INDICAR QUE LA INCIDENCIA SE HA CERRADO POR LA ELIMINACION DEL DOCUMENTO
                        $txObservaciones = "La incidencia se ha cerrado porque el documento asociado se ha eliminado. - The issue has been closed because the associated documento has been removed.";
                        $observaciones_sistema->Grabar('INCIDENCIA_BLOCKCHAIN', $rowIncidenciaEventoDocumento->ID_INCIDENCIA_BLOCKCHAIN, $txObservaciones);

                        $sqlUpdate = "UPDATE INCIDENCIA_BLOCKCHAIN SET ESTADO = 'Finalizada', FECHA_RESOLUCION = '" . $fecha_finalizacion . "', TIPO_RESOLUCION = 'Automatica' WHERE ID_INCIDENCIA_BLOCKCHAIN = $rowIncidenciaEventoDocumento->ID_INCIDENCIA_BLOCKCHAIN";
                        $bd->ExecSQL($sqlUpdate);
                    endwhile;

                endwhile;

                //COMPRUEBO SI TIENE INCIDENCIAS EL EVENTO, EN CASO AFIRMATIVO, LAS CIERRO
                $sqlIncidenciaEvento    = "SELECT *
                      FROM INCIDENCIA_BLOCKCHAIN
                      WHERE TABLA_OBJETO = 'EVENTO' AND ID_OBJETO = $rowEvento->ID_EVENTO AND (ESTADO = 'Creada' OR ESTADO = 'En Proceso')";
                $resultIncidenciaEvento = $bd->ExecSQL($sqlIncidenciaEvento);
                while ($rowIncidenciaEvento = $bd->SigReg($resultIncidenciaEvento)):
                    //CREO UNA OBSERVACION PARA INDICAR QUE LA INCIDENCIA SE HA CERRADO POR LA ELIMINACION DEL EVENTO
                    $txObservaciones = "La incidencia se ha cerrado porque el evento asociado se ha eliminado. - The issue has been closed because the associated event has been removed.";
                    $observaciones_sistema->Grabar('INCIDENCIA_BLOCKCHAIN', $rowIncidenciaEvento->ID_INCIDENCIA_BLOCKCHAIN, $txObservaciones);

                    $sqlUpdate = "UPDATE INCIDENCIA_BLOCKCHAIN SET ESTADO = 'Finalizada', FECHA_RESOLUCION = '" . $fecha_finalizacion . "', TIPO_RESOLUCION = 'Automatica' WHERE ID_INCIDENCIA_BLOCKCHAIN = $rowIncidenciaEvento->ID_INCIDENCIA_BLOCKCHAIN";
                    $bd->ExecSQL($sqlUpdate);
                endwhile;

                //LO BORRAMOS DE BLOCKCHAIN
                if ($rowEvento->JOBID != ''):

                    //CREAMOS LA PETICION DE BORRADO DEL EVENTO
                    $idEventoPendienteTransmitir = $auxiliar->eliminar_evento_pendiente_transmitir($rowEvento->ID_EVENTO);

                endif;

                //$sqlUpdate = "UPDATE EVENTO_DATOS SET BAJA = 1 WHERE ID_EVENTO = $rowEvento->ID_EVENTO";
                //$bd->ExecSQL($sqlUpdate);
                $sqlUpdate = "UPDATE EVENTO_DATOS_PARAMETRIZADOS SET BAJA = 1 WHERE ID_EVENTO = $rowEvento->ID_EVENTO";
                $bd->ExecSQL($sqlUpdate);

                $sqlUpdate = "UPDATE EVENTO_DOCUMENTO SET BAJA = 1 WHERE ID_EVENTO = $rowEvento->ID_EVENTO";
                $bd->ExecSQL($sqlUpdate);

                $sqlUpdate = "UPDATE EVENTO SET BAJA = 1 WHERE ID_EVENTO = $rowEvento->ID_EVENTO";
                $bd->ExecSQL($sqlUpdate);

            endwhile;
        endif;

    }*/

} // FIN CLASE