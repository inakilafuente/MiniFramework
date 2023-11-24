<?php

# expedicion
# Clase expedicion contiene todas las funciones necesarias para la interaccion con la clase expedicion
# Se incluira en las sesiones

class expedicion
{

    function __construct()
    {
    } // Fin albaran

    /**
     * @param $idOrdenTransporte ORDEN DE TRANSPORTE
     * @param $idMovimientoSalidaLinea LINEA DE MOVIMIENTO DE SALIDA SOBRE LA QUE HAREMOS EL SPLIT
     * @param $txCantidad NUEVA CANTIDAD DE LA LINEA
     * FUNCION UTILIZADA PARA HACER UN SPLIT DE LINEA SIN BULTO DECREMENTANDO LA CANTIDAD DE LA LINEA ASIGNADA A LA ORDEN DE TRANSPORTE
     */
    function SplitLineaMovimientoSinBulto($idOrdenTransporte, $idMovimientoSalidaLinea, $txCantidad)
    {
        global $bd;
        global $html;
        global $comp;
        global $auxiliar;
        global $administrador;
        global $necesidad;
        global $mat;

        //BUSCO LA ORDEN DE TRANSPORTE
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowOrdenTransporte               = $bd->VerReg("EXPEDICION", "ID_EXPEDICION", $idOrdenTransporte, "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);

        //COMPRUEBO QUE EXISTA LA ORDEN DE TRANSPORTE
        $html->PagErrorCondicionado($rowOrdenTransporte, "==", false, "OrdenTransporteNoEncontrada");

        //COMPRUEBO QUE EL ESTADO DE LA ORDEN DE TRANSPORTE SEA 'Creada' o 'En Transmision'
        $html->PagErrorCondicionado((($rowOrdenTransporte->ESTADO != 'Creada') && ($rowOrdenTransporte->ESTADO != 'En Transmision')), "==", true, "EstadoOrdenTransporteIncorrecto");

        //BUSCO LA LINEA DEL MOVIMIENTO DE SALIDA
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowMovimientoSalidaLinea         = $bd->VerReg("MOVIMIENTO_SALIDA_LINEA", "ID_MOVIMIENTO_SALIDA_LINEA", $idMovimientoSalidaLinea, "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);

        //COMPRUEBO QUE EXISTA LA LINEA
        $html->PagErrorCondicionado($rowMovimientoSalidaLinea, "==", false, "MovimientoSalidaLineaNoEncontrada");

        //COMPRUEBO QUE EL ESTADO DE LA LINEA SEA 'Pendiente de Expedir'
        $html->PagErrorCondicionado($rowMovimientoSalidaLinea->ESTADO, "!=", 'Pendiente de Expedir', "EstadoMovimientoSalidaLineaIncorrecto");

//        //COMPRUEBO QUE LA LINEA NO PERTENEZCA A NINGUN CONTENEDOR
//        $html->PagErrorCondicionado($rowMovimientoSalidaLinea->ID_CONTENEDOR, "!=", NULL, "MovimientoSalidaLineaPertenecienteContenedor");

        //COMPRUEBO QUE LA LINEA TENGA ORDEN DE TRANSPORTE ASIGNADA
        $html->PagErrorCondicionado($rowMovimientoSalidaLinea->ID_EXPEDICION, "==", '', "MovimientoSalidaLineaSinOrdenTransporte");

        //COMPRUEBO QUE LA LINEA PERTENEZCA A ESTA ORDEN DE TRANSPORTE
        $html->PagErrorCondicionado($rowMovimientoSalidaLinea->ID_EXPEDICION, "!=", $rowOrdenTransporte->ID_EXPEDICION, "MovimientoSalidaLineaPertenecienteOtraOrdenTransporte");

        //BUSCO EL MOVIMIENTO DE SALIDA
        $rowMovimientoSalida = $bd->VerReg("MOVIMIENTO_SALIDA", "ID_MOVIMIENTO_SALIDA", $rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA);

        //COMPRUEBO QUE LA CABECERA DE LA LINEA ESTE ASIGNADA A UNA ORDEN DE PREPARACION
        if ($rowMovimientoSalida->ID_ORDEN_PREPARACION == NULL):
            $arrDevuelto['errores'] = $auxiliar->traduce("La linea no pertecene a ninguna orden de preparacion", $administrador->ID_IDIOMA);
        endif;

        //BUSCO LA ORDEN DE PREPARACION
        $rowOrdenPreparacion = $bd->VerReg("ORDEN_PREPARACION", "ID_ORDEN_PREPARACION", $rowMovimientoSalida->ID_ORDEN_PREPARACION);

        //COMPRUEBO QUE LA ORDEN DE TRANSPORTE NO SEA DE TIPO OLI (ORDEN DE LOGISTICA INVERSA)
        if ($rowOrdenPreparacion->TIPO_ORDEN == 'OLI'):
            $arrDevuelto['errores'] = $auxiliar->traduce("La orden de preparacion de la linea es de logistica inversa, no se puede realizar la operacion.", $administrador->ID_IDIOMA);
        endif;

        //COMPRUEBO QUE ESTE RELLENO EL CAMPO CANTIDAD
        unset($arr_tx);
        $i                   = 0;
        $arr_tx[$i]["err"]   = $auxiliar->traduce("Nueva cantidad linea", $administrador->ID_IDIOMA);
        $arr_tx[$i]["valor"] = $txCantidad;
        $comp->ComprobarTexto($arr_tx, "CampoSinRellenar");

        //COMPRUEBO QUE SEA DECIMAL EL CAMPO CANTIDAD
        $comp->ComprobarDec($arr_tx, "NoDecimal");

        //COMPRUEBO QUE LA CANTIDAD SE SUPERIOR A CERO
        $html->PagErrorCondicionado($txCantidad, "<=", 0, "CantidadMenorIgualCero");

        //COMPRUEBO QUE LA CANTIDAD SE INFERIOR AL TOTAL DE LA LINEA
        $html->PagErrorCondicionado($txCantidad, ">=", $rowMovimientoSalidaLinea->CANTIDAD, "CantidadMayorIgualTotalLinea");

        // COMPRUEBO SI SE ESTÁ HACIENDO UN TRATAMIENTO PARCIAL DE LA CANTIDAD DE COMPRA Y SI EL USUARIO TIENE PERMISOS PARA ELLO
        if ($administrador->Hayar_Permiso_Perfil('ADM_ENTRADAS_TRATAMIENTO_PARCIAL_CC') < 2):
            // SI EL MATERIAL TIENE CANTIDAD DE COMPRA Y NO ES DIVISIBLE
            $GLOBALS["NotificaErrorPorEmail"] = "No";
            $rowMat                           = $bd->VerReg("MATERIAL", "ID_MATERIAL", $rowMovimientoSalidaLinea->ID_MATERIAL, "No");
            if (($rowMat->ID_UNIDAD_MEDIDA != $rowMat->ID_UNIDAD_COMPRA && $rowMat->DENOMINADOR_CONVERSION != 0) && $rowMat->DIVISIBILIDAD != 'Si'):
                //CANTIDAD DE COMPRA Y UNIDADES BASE Y COMPRA
                $cantidadCompra      = $mat->cantUnidadCompra($rowMat->ID_MATERIAL, $txCantidad);
                $unidadesBaseyCompra = $mat->unidadBaseyCompra($rowMat->ID_MATERIAL);

                // SE COMPRUEBA SI LA CANTIDAD DE COMPRA ES UN NÚMERO ENTERO
                if (fmod((float) $cantidadCompra, 1) !== 0.0):
                    $html->PagError("ErrorLineaTratamientoParcialCC");
                endif;
            endif;
        endif;

        //BUSCO EL MATERIAL-ALMACEN
        $rowMatAlm = $bd->VerRegRest("MATERIAL_ALMACEN", "ID_MATERIAL = $rowMovimientoSalidaLinea->ID_MATERIAL AND ID_ALMACEN = $rowMovimientoSalidaLinea->ID_ALMACEN", "No");

        //SI ES SERIABLE, LA CANTIDAD SERA UN NUMERO ENTERO
        if ($rowMatAlm->TIPO_LOTE == 'serie'):
            $html->PagErrorCondicionado($comp->EsEnteroCorrecto($txCantidad), "==", false, "MaterialSeriableCantidadDecimal");
        endif;

        //ACTUALIZO LA CANTIDAD DE LA LINEA ORIGINAL
        $sqlUpdate = "UPDATE MOVIMIENTO_SALIDA_LINEA SET
                        CANTIDAD = $txCantidad
                        WHERE ID_MOVIMIENTO_SALIDA_LINEA = $rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA_LINEA";
        $bd->ExecSQL($sqlUpdate);

        //ACCIONES SI LA LINEA YA TENIA ALBARANES GENERADOS
        if ($rowMovimientoSalidaLinea->ID_ALBARAN_LINEA != NULL):
            //DECREMENTO LA CANTIDAD DE LA LINEA DE ALBARAN
            $sqlUpdate = "UPDATE ALBARAN_LINEA SET
                            CANTIDAD = CANTIDAD - ($rowMovimientoSalidaLinea->CANTIDAD - $txCantidad)
                            WHERE ID_ALBARAN_LINEA = $rowMovimientoSalidaLinea->ID_ALBARAN_LINEA";
            //, CANTIDAD_PENDIENTE_DE_RECEPCIONAR_EN_DESTINO = CANTIDAD_PENDIENTE_DE_RECEPCIONAR_EN_DESTINO - ($rowMovimientoSalidaLinea->CANTIDAD - $txCantidad)
            $bd->ExecSQL($sqlUpdate);

            //DOY DE BAJA LA LINEA DE ALBARAN SI SE HA QUEDADO CON CANTIDAD CERO
            $sqlUpdate = "UPDATE ALBARAN_LINEA SET
                            BAJA = 1
                            WHERE ID_ALBARAN_LINEA = $rowMovimientoSalidaLinea->ID_ALBARAN_LINEA AND CANTIDAD = 0";
            $bd->ExecSQL($sqlUpdate);

            //COMPRUEBO SI EL ALBARAN TIENE LINEAS ACTIVAS
            $numLineasAlbaran = $bd->NumRegsTabla("ALBARAN_LINEA", "ID_ALBARAN = $rowMovimientoSalidaLinea->ID_ALBARAN AND BAJA = 0");
            if ($numLineasAlbaran == 0): //SI NO TIENE LINEAS, LO DOY DE BAJA TAMBIEN
                $sqlUpdate = "UPDATE ALBARAN SET
                                BAJA = 1
                                WHERE ID_ALBARAN = $rowMovimientoSalidaLinea->ID_ALBARAN";
                $bd->ExecSQL($sqlUpdate);
            endif;

            //MARCO EL ALBARAN COMO NO IMPRESO
            $sqlUpdate = "UPDATE ALBARAN SET IMPRESO = 0 WHERE ID_ALBARAN = $rowMovimientoSalidaLinea->ID_ALBARAN";
            $bd->ExecSQL($sqlUpdate);
        endif;
        //FIN ACCIONES SI LA LINEA YA TENIA ALBARANES GENERADOS

        //BUSCO SI EXISTE UNA LINEA SIMILAR A LA QUE REAGRUPAR LA CANTIDAD SOBRANTE
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowMovimientoSalidaLineaSimilar  = $bd->VerRegRest("MOVIMIENTO_SALIDA_LINEA", "LINEA_ANULADA = 0 AND
                BAJA = 0 AND
                ESTADO = 'Pendiente de Expedir' AND
                ID_CONTENEDOR " . ($rowMovimientoSalidaLinea->ID_CONTENEDOR == NULL ? 'IS NULL' : "= $rowMovimientoSalidaLinea->ID_CONTENEDOR") . " AND
                ID_CONTENEDOR_LINEA " . ($rowMovimientoSalidaLinea->ID_CONTENEDOR_LINEA == NULL ? 'IS NULL' : "= $rowMovimientoSalidaLinea->ID_CONTENEDOR_LINEA") . " AND
                ID_BULTO " . ($rowMovimientoSalidaLinea->ID_BULTO == NULL ? 'IS NULL' : "= $rowMovimientoSalidaLinea->ID_BULTO") . " AND
                ID_BULTO_LINEA " . ($rowMovimientoSalidaLinea->ID_BULTO_LINEA == NULL ? 'IS NULL' : "= $rowMovimientoSalidaLinea->ID_BULTO_LINEA") . " AND
                ID_EXPEDICION IS NULL AND
                ID_ALBARAN IS NULL AND
                ID_ALBARAN_LINEA IS NULL AND
                EXPEDICION_SAP = '' AND
                ID_EXPEDICION_SAP IS NULL AND
                ID_MOVIMIENTO_SALIDA = $rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA AND
                ID_PEDIDO_SALIDA_LINEA = $rowMovimientoSalidaLinea->ID_PEDIDO_SALIDA_LINEA AND
                ID_UBICACION = $rowMovimientoSalidaLinea->ID_UBICACION AND
                ID_MATERIAL_FISICO " . ($rowMovimientoSalidaLinea->ID_MATERIAL_FISICO == NULL ? 'IS NULL' : "= $rowMovimientoSalidaLinea->ID_MATERIAL_FISICO") . " AND
                ID_ORDEN_TRABAJO_MOVIMIENTO " . ($rowMovimientoSalidaLinea->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? 'IS NULL' : "= $rowMovimientoSalidaLinea->ID_ORDEN_TRABAJO_MOVIMIENTO") . " AND
                ID_INCIDENCIA_CALIDAD " . ($rowMovimientoSalidaLinea->ID_INCIDENCIA_CALIDAD == NULL ? 'IS NULL' : "= $rowMovimientoSalidaLinea->ID_INCIDENCIA_CALIDAD") . " AND
                ID_TIPO_BLOQUEO " . ($rowMovimientoSalidaLinea->ID_TIPO_BLOQUEO == NULL ? 'IS NULL' : "= $rowMovimientoSalidaLinea->ID_TIPO_BLOQUEO"), "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);

        //ACCIONES EN FUNCION DE SI EXISTE UNA LINEA DE MOVIMIENTO DE SALIDA SIMILAR O NO
        if ($rowMovimientoSalidaLineaSimilar != false): //SI EXISTE UNA LINEA SIMILAR, INCREMENTO LA CANTIDAD DE LA LINEA

            $sqlUpdate = "UPDATE MOVIMIENTO_SALIDA_LINEA SET
                          CANTIDAD = CANTIDAD + ($rowMovimientoSalidaLinea->CANTIDAD - $txCantidad)
                          WHERE ID_MOVIMIENTO_SALIDA_LINEA = $rowMovimientoSalidaLineaSimilar->ID_MOVIMIENTO_SALIDA_LINEA";
            $bd->ExecSQL($sqlUpdate); //NO EXISTE UNA LINEA SIMILAR, GENERO UNA NUEVA LINEA

            //OBTENGO LA LINEA DONDE SE REASIGNA LA CANTIDAD SOBRANTE
            $idMovimientoSalidaLineaDestinoCantidadSobrante = $rowMovimientoSalidaLineaSimilar->ID_MOVIMIENTO_SALIDA_LINEA;

            //CALCULO LA CANTIDAD A INTERCAMBIAR
            $cantidadIntercambiar = $rowMovimientoSalidaLinea->CANTIDAD - $txCantidad;

        else:
            $sqlInsert = "INSERT INTO MOVIMIENTO_SALIDA_LINEA SET
                            ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                            , ID_MOVIMIENTO_SALIDA = $rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA
                            , FECHA = '" . $rowMovimientoSalidaLinea->FECHA . "'
                            , ESTADO = '" . $rowMovimientoSalidaLinea->ESTADO . "'
                            , ID_CONTENEDOR = " . ($rowMovimientoSalidaLinea->ID_CONTENEDOR == NULL ? 'NULL' : "$rowMovimientoSalidaLinea->ID_CONTENEDOR") . "
                            , ID_CONTENEDOR_LINEA = " . ($rowMovimientoSalidaLinea->ID_CONTENEDOR_LINEA == NULL ? 'NULL' : "$rowMovimientoSalidaLinea->ID_CONTENEDOR_LINEA") . "
                            , ID_BULTO = " . ($rowMovimientoSalidaLinea->ID_BULTO == NULL ? 'NULL' : "$rowMovimientoSalidaLinea->ID_BULTO") . "
                            , ID_BULTO_LINEA = " . ($rowMovimientoSalidaLinea->ID_BULTO_LINEA == NULL ? 'NULL' : "$rowMovimientoSalidaLinea->ID_BULTO_LINEA") . "
                            , ID_UBICACION = $rowMovimientoSalidaLinea->ID_UBICACION
                            , ID_ALMACEN = $rowMovimientoSalidaLinea->ID_ALMACEN
                            , ID_MATERIAL = $rowMovimientoSalidaLinea->ID_MATERIAL
                            , ID_MATERIAL_FISICO = " . ($rowMovimientoSalidaLinea->ID_MATERIAL_FISICO == NULL ? 'NULL' : $rowMovimientoSalidaLinea->ID_MATERIAL_FISICO) . "
                            , ID_PEDIDO_SALIDA = $rowMovimientoSalidaLinea->ID_PEDIDO_SALIDA
                            , ID_PEDIDO_SALIDA_LINEA = $rowMovimientoSalidaLinea->ID_PEDIDO_SALIDA_LINEA
                            , TIPO_LOTE = '" . $rowMovimientoSalidaLinea->TIPO_LOTE . "'
                            , CANTIDAD = " . ($rowMovimientoSalidaLinea->CANTIDAD - $txCantidad) . "
                            , CANTIDAD_PEDIDO = $rowMovimientoSalidaLinea->CANTIDAD_PEDIDO
                            , ID_UBICACION_DESTINO = " . ($rowMovimientoSalidaLinea->ID_UBICACION_DESTINO == NULL ? 'NULL' : "$rowMovimientoSalidaLinea->ID_UBICACION_DESTINO") . "
                            , ID_ALMACEN_DESTINO = " . ($rowMovimientoSalidaLinea->ID_ALMACEN_DESTINO == NULL ? 'NULL' : "$rowMovimientoSalidaLinea->ID_ALMACEN_DESTINO") . "
                            , ID_TIPO_BLOQUEO = " . ($rowMovimientoSalidaLinea->ID_TIPO_BLOQUEO == NULL ? 'NULL' : "$rowMovimientoSalidaLinea->ID_TIPO_BLOQUEO") . "
                            , ID_ORDEN_TRABAJO_MOVIMIENTO = " . ($rowMovimientoSalidaLinea->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? 'NULL' : "$rowMovimientoSalidaLinea->ID_ORDEN_TRABAJO_MOVIMIENTO") . "
                            , ID_INCIDENCIA_CALIDAD = " . ($rowMovimientoSalidaLinea->ID_INCIDENCIA_CALIDAD == NULL ? 'NULL' : "$rowMovimientoSalidaLinea->ID_INCIDENCIA_CALIDAD") . "
                            , FECHA_PREPARACION = '" . $rowMovimientoSalidaLinea->FECHA_PREPARACION . "'";
            $bd->ExecSQL($sqlInsert);

            //OBTENGO LA LINEA DONDE SE REASIGNA LA CANTIDAD SOBRANTE
            $idMovimientoSalidaLineaDestinoCantidadSobrante = $bd->IdAsignado();

            //CALCULO LA CANTIDAD A INTERCAMBIAR
            $cantidadIntercambiar = $rowMovimientoSalidaLinea->CANTIDAD - $txCantidad;
        endif;
        //FIN ACCIONES EN FUNCION DE SI EXISTE UNA LINEA DE MOVIMIENTO DE SALIDA SIMILAR O NO

        //DESASOCIO DE LA LINEA DEL MOVIMIENTO DE SALIDA LA CANTIDAD A INTERCAMBIAR Y LA ASOCIO AL NUEVO O SIMILAR
        $necesidad->DesasociarMovimientoSalidaLineaCanceladaParcialmenteYAsociarMovimientoSalidaLineaNuevo($idMovimientoSalidaLinea, $idMovimientoSalidaLineaDestinoCantidadSobrante, $cantidadIntercambiar);

        //DECLARO UN ARRAY PARA DEVOLVER DATOS
        $arrDevuelto                      = array();
        $arrDevuelto['idMovimiento']      = $rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA;
        $arrDevuelto['idLineaMovimiento'] = $idMovimientoSalidaLineaDestinoCantidadSobrante;

        //DEVUELVO EL ARRAY
        return $arrDevuelto;
    }

    /**
     * @param $idMovimientoSalidaLinea LINEA A REUBICAR EL MATERIAL DE LA ORDEN DE PREPARACION
     * @return array ARRAY DEVUELTO CON LOS POSIBLES ERRORES Y EL MOVIMIENTO DE SALIDA IMPLICADO
     * FUNCION UTILIZADA PARA REUBICAR EL MATERIAL DESUBICADO POR UNA LINEA, LA CANTIDAD DE LA LINEA SE DEBERA VOLVER A PREPARAR DESDE LA ORDEN DE PREPARACION
     */
    function ReubicarMaterialLineaMovimientoSalida($idMovimientoSalidaLinea, $eliminarBulto = 0)
    {
        global $bd;
        global $auxiliar;
        global $administrador;
        global $albaran;
        global $movimiento;
        global $orden_preparacion;
        global $necesidad;
        global $orden_transporte;
        global $exp_SAP;
        global $reserva;

        //DECLARO UN ARRAY PARA DEVOLVER DATOS
        $arrDevuelto = array();

        /************************************ DESHABILITO TRIGGERS IMPLICADOS ************************************/
        $sqlDeshabilitarTriggers = "SET @Trigger_MOVIMIENTO_SALIDA_LINEA_UPDATE_Deshabilitado = 1";
        $bd->ExecSQL($sqlDeshabilitarTriggers);
        $sqlDeshabilitarTriggers = "SET @Trigger_NECESIDAD_LINEA_UPDATE_Deshabilitado = 1";
        $bd->ExecSQL($sqlDeshabilitarTriggers);
        /********************************** FIN DESHABILITO TRIGGERS IMPLICADOS **********************************/

        //BUSCO LA LINEA DE MOVIMIENTO DE SALIDA A ELIMINAR DE LA ORDEN DE TRANSPORTE
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowMovimientoSalidaLinea         = $bd->VerReg("MOVIMIENTO_SALIDA_LINEA", "ID_MOVIMIENTO_SALIDA_LINEA", $idMovimientoSalidaLinea, "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);
        if ($rowMovimientoSalidaLinea == false):
            $arrDevuelto['errores'] = $auxiliar->traduce("La linea para reubicar ha variado, actualice", $administrador->ID_IDIOMA);
        endif;

        //AÑADO AL ARRAY A DEVOLVER EL MOVIMIENTO DE SALIDA MODIFICADO
        $arrDevuelto['idMovimientoSalida'] = $rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA;

        //COMPRUEBO QUE TENGA PERMISOS DE ESCRITURA EN EL ALMACEN
        if ($administrador->comprobarAlmacenPermiso($rowMovimientoSalidaLinea->ID_ALMACEN, "Escritura") == false):
            $arrDevuelto['errores'] = $auxiliar->traduce("No tiene permisos para realizar esta operacion en esta subzona", $administrador->ID_IDIOMA);
        endif;

        //COMPRUEBO QUE EL ESTADO DE LA LINEA SEA EN PREPARACION O PENDIENTE DE TRANSMITIR A SAP
        if (($rowMovimientoSalidaLinea->ESTADO != 'Pendiente de Expedir') && ($rowMovimientoSalidaLinea->ESTADO != 'En Preparacion')):
            $arrDevuelto['errores'] = $auxiliar->traduce("La linea esta en un estado incorrecto, debe estar 'Pendiente de Transmitir a SAP' o 'En Preparacion'", $administrador->ID_IDIOMA);
        endif;

        //COMPRUEBO QUE LA LINEA NO HAYA SIDO ENVIADA A SAP
        if ($rowMovimientoSalidaLinea->ENVIADO_SAP != 0):
            $arrDevuelto['errores'] = $auxiliar->traduce("La linea ya ha sido transmitida a SAP", $administrador->ID_IDIOMA);
        endif;

        //COMPRUEBO QUE LA LINEA NO ESTE DADA DE BAJA
        if ($rowMovimientoSalidaLinea->BAJA != 0):
            $arrDevuelto['errores'] = $auxiliar->traduce("La linea esta marcada como baja", $administrador->ID_IDIOMA);
        endif;

        //BUSCO EL MOVIMIENTO DE SALIDA
        $rowMovimientoSalida = $bd->VerReg("MOVIMIENTO_SALIDA", "ID_MOVIMIENTO_SALIDA", $rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA);

        //COMPRUEBO QUE LA CABECERA DE LA LINEA ESTE ASIGNADA A UNA ORDEN DE PREPARACION
        if ($rowMovimientoSalida->ID_ORDEN_PREPARACION == NULL):
            $arrDevuelto['errores'] = $auxiliar->traduce("La linea no pertecene a ninguna orden de preparacion", $administrador->ID_IDIOMA);
        endif;

        //BUSCO LA ORDEN DE PREPARACION
        $rowOrdenPreparacion = $bd->VerReg("ORDEN_PREPARACION", "ID_ORDEN_PREPARACION", $rowMovimientoSalida->ID_ORDEN_PREPARACION);

        //COMPRUEBO QUE LA ORDEN DE TRANSPORTE NO SEA DE TIPO OLI (ORDEN DE LOGISTICA INVERSA)
        if ($rowOrdenPreparacion->TIPO_ORDEN == 'OLI'):
            $arrDevuelto['errores'] = $auxiliar->traduce("La orden de preparacion es de logistica inversa, no se puede realizar la operacion. <br>Para este tipo de ordenes de preparacion solo esta permitido la operacion de 'Reubicar y desasignar' desde bultos u ordenes de transporte.", $administrador->ID_IDIOMA);
        endif;

        /***************************************** COMPROBACIONES SI LA LINEA ESTA ASIGNADA A UNA ORDEN DE TRANSPORTE *****************************************/
        if ($rowMovimientoSalidaLinea->ID_EXPEDICION != NULL):
            //COMPRUEBO QUE LA ORDEN DE TRANSPORTE EXISTE
            $GLOBALS["NotificaErrorPorEmail"] = "No";
            $rowOrdenTransporte               = $bd->VerReg("EXPEDICION", "ID_EXPEDICION", $rowMovimientoSalidaLinea->ID_EXPEDICION, "No");
            unset($GLOBALS["NotificaErrorPorEmail"]);
            if ($rowOrdenTransporte == false):
                $arrDevuelto['errores'] = $auxiliar->traduce("La orden de transporte a la que esta asignada la linea no existe", $administrador->ID_IDIOMA);
            endif;

            //COMPRUEBO QUE LA ORDEN DE TRANSPORTE NO ESTE DADA DE BAJA
            if ($rowOrdenTransporte->BAJA == 1):
                $arrDevuelto['errores'] = $auxiliar->traduce("La orden de transporte a la que esta asignada la linea esta dada de baja", $administrador->ID_IDIOMA);
            endif;

            //COMPRUEBO QUE LA ORDEN DE TRANSPORTE ESTE EN ESTADO CREADA O EN TRANSMISION
            if (($rowOrdenTransporte->ESTADO == 'Creada') && ($rowOrdenTransporte->ESTADO == 'En Transmision')):
                $arrDevuelto['errores'] = $auxiliar->traduce("La orden de transporte a la que esta asignada la linea debe estar en estado 'Creada' o 'En Transmision'", $administrador->ID_IDIOMA);
            endif;
        endif;
        /*************************************** FIN COMPROBACIONES SI LA LINEA ESTA ASIGNADA A UNA ORDEN DE TRANSPORTE ***************************************/

        /************************************************* COMPROBACIONES SI LA LINEA ESTA ASIGNADA A UN BULTO *************************************************/
        if ($rowMovimientoSalidaLinea->ID_BULTO != NULL):
            //COMPRUEBO QUE EL BULTO EXISTE
            $GLOBALS["NotificaErrorPorEmail"] = "No";
            $rowBulto                         = $bd->VerReg("BULTO", "ID_BULTO", $rowMovimientoSalidaLinea->ID_BULTO, "No");
            unset($GLOBALS["NotificaErrorPorEmail"]);
            if ($rowBulto == false):
                $arrDevuelto['errores'] = $auxiliar->traduce("El bulto al que esta asignada la linea no existe", $administrador->ID_IDIOMA);
            endif;

            //COMPRUEBO QUE EL BULTO ESTE EN ESTADO ABIERTO O CERRADO
            if (($rowBulto->ESTADO != 'Abierto') && ($rowBulto->ESTADO != 'Cerrado')):
                $arrDevuelto['errores'] = $auxiliar->traduce("El bulto al que esta asignada la linea debe estar en estado 'Abierto' o 'Cerrado'", $administrador->ID_IDIOMA);
            endif;

            //COMPRUEBO QUE EL BULTO NO ESTE ASIGNADO A UNA ORDEN DE RECOGIDA
            if ($rowMovimientoSalidaLinea->ID_EXPEDICION != NULL):
                $arrDevuelto['errores'] = $auxiliar->traduce("El bulto esta asignado a una orden de recogida, desasignelo para poder continuar", $administrador->ID_IDIOMA) . "<br>";
                $arrDevuelto['errores'] .= $auxiliar->traduce("Bulto", $administrador->ID_IDIOMA) . ": " . $rowBulto->REFERENCIA . " - " . $auxiliar->traduce("Orden de Recogida", $administrador->ID_IDIOMA) . ": " . "<a href=\"../expediciones/ficha.php?idExpedicion=" . $rowMovimientoSalidaLinea->ID_EXPEDICION . "\"" . " target=\"_blank\" class=\"enlaceceldasacceso\">" . $rowMovimientoSalidaLinea->ID_EXPEDICION . "</a>";
            endif;
        endif;
        /*********************************************** FIN COMPROBACIONES SI LA LINEA ESTA ASIGNADA A UN BULTO ***********************************************/

        //BUSCO EL ALMACEN DE ORIGEN
        $rowAlmacenSalida = $bd->VerReg("ALMACEN", "ID_ALMACEN", $rowMovimientoSalidaLinea->ID_ALMACEN);

        //BUSCO LA UBICACION DE ORIGEN
        $rowUbicacionSalida = $bd->VerReg("UBICACION", "ID_UBICACION", $rowMovimientoSalidaLinea->ID_UBICACION);

        //BUSCO LA UBICACION PENDIENTE DE UBICAR DE ESTE ALMACEN
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowUbiPendienteUbicar            = $bd->VerRegRest("UBICACION", "ID_ALMACEN = $rowAlmacenSalida->ID_ALMACEN AND TIPO_UBICACION = 'Pendiente Ubicar'", "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);

        //BUSCO EL CARRO DE REUBICACION DEL CENTRO FISICO
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowCarroReubicacionCentroFisico = $bd->VerRegRest("UBICACION_CENTRO_FISICO", "ID_CENTRO_FISICO = " . $rowAlmacenSalida->ID_CENTRO_FISICO . " AND TIPO_UBICACION_CF = 'Carro' AND TIPO_CARRO = 'Reubicacion' AND BAJA = 0", "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);

        //BUSCO EL CARRO DE REUBICACION DEL ALMACEN
        if ($rowCarroReubicacionCentroFisico == false):
            $rowCarroReubicacionAlmacen = false;
        else:
            $GLOBALS["NotificaErrorPorEmail"] = "No";
            $rowCarroReubicacionAlmacen = $bd->VerRegRest("UBICACION", "ID_UBICACION_CENTRO_FISICO = " . $rowCarroReubicacionCentroFisico->ID_UBICACION_CENTRO_FISICO . " AND ID_ALMACEN = $rowAlmacenSalida->ID_ALMACEN AND BAJA = 0", "No");
            unset($GLOBALS["NotificaErrorPorEmail"]);
        endif;

        //COMPROBACIONES SOBRE LA UBICACION DONDE REUBICAR EL MATERIAL
        if ($rowUbicacionSalida->AUTOSTORE == 1): //SI LA UBICACION ORIGEN ES DE AUTOSTORE
            if ($rowCarroReubicacionAlmacen == false):
                $arrDevuelto['errores'] = $auxiliar->traduce("No hay definido un carro de reubicacion en el almacen", $administrador->ID_IDIOMA) . " " . $rowAlmacenSalida->REFERENCIA . " - " . $rowAlmacenSalida->NOMBRE;
            endif;
        elseif (($rowUbiPendienteUbicar == false) && ($rowOrdenPreparacion->VIA_PREPARACION == 'PDA')): //COMPRUEBO QUE EXISTE LA UBICACION DE PENDIENTE DE UBICAR EN EL ALMACEN QUE ESTOY
            $arrDevuelto['errores'] = $auxiliar->traduce("No hay definida una ubicacion de tipo pendiente de ubicar en el almacen", $administrador->ID_IDIOMA) . " " . $rowAlmacenSalida->REFERENCIA . " - " . $rowAlmacenSalida->NOMBRE;
        endif;

        //BUSCO LA UBICACION DESTINO
        if ($rowUbicacionSalida->AUTOSTORE == 1):
            $rowUbiDestino = $rowCarroReubicacionAlmacen;
        elseif ($rowOrdenPreparacion->VIA_PREPARACION == 'PDA'):
            $rowUbiDestino = $rowUbiPendienteUbicar;
        else:
            $GLOBALS["NotificaErrorPorEmail"] = "No";
            $rowUbiDestino                    = $bd->VerReg("UBICACION", "ID_UBICACION", $rowMovimientoSalidaLinea->ID_UBICACION, "No");
            unset($GLOBALS["NotificaErrorPorEmail"]);
        endif;

        //COMPRUEBO QUE EXISTE LA UBICACION DE DESTINO
        if ($rowUbiDestino == false):
            $arrDevuelto['errores'] = $auxiliar->traduce("No hay definida una ubicacion donde reubicar el material, actualice", $administrador->ID_IDIOMA);
        endif;

        //ACCIONES PARA DESCONTAR EL MATERIAL DE DONDE CORRESPONDA
        if ($rowMovimientoSalidaLinea->ID_CONTENEDOR != NULL):  //LINEA PREPARADA EN BASE A BULTOS
            //BUSCO EL CONTENEDOR
            $rowContenedor = $bd->VerReg("CONTENEDOR", "ID_CONTENEDOR", $rowMovimientoSalidaLinea->ID_CONTENEDOR);

            //BUSCO LA UBICACION DE ORIGEN, SERA LA DEL CONTENEDOR ACTUAL, DONDE ESTA EL MATERIAL DESUBICADO POR LA LINEA HASTA EL MOMENTO
            $GLOBALS["NotificaErrorPorEmail"] = "No";
            $rowUbiOrigen                     = $bd->VerReg("UBICACION", "ID_UBICACION", $rowContenedor->ID_UBICACION, "No");
            unset($GLOBALS["NotificaErrorPorEmail"]);

            //COMPRUEBO QUE EXISTE LA UBICACION DEL CONTENEDOR DEL MATERIAL EN EL ALMACEN QUE ESTOY
            if ($rowUbiOrigen == false):
                $arrDevuelto['errores'] = $auxiliar->traduce("No hay definida una ubicacion para el contenedor donde esta el material de la linea en el almacen", $administrador->ID_IDIOMA) . " " . $rowAlmacenSalida->REFERENCIA . " - " . $rowAlmacenSalida->NOMBRE;
            endif;
        else:   //LINEA PREPARADA SIN BULTOS
            //BUSCO LA UBICACION DE ORIGEN
            if ($rowOrdenPreparacion->TIPO_ORDEN == 'OPAGM'):// PARA PREPARACION DE AGMS SERA LA DE COMPONENTES
                $GLOBALS["NotificaErrorPorEmail"] = "No";
                $rowUbiOrigen                     = $bd->VerRegRest("UBICACION", "ID_ALMACEN = $rowAlmacenSalida->ID_ALMACEN AND TIPO_UBICACION = 'Componentes AGM'", "No");
                unset($GLOBALS["NotificaErrorPorEmail"]);
            else:// SERA DE TIPO SALIDA, DONDE ESTA EL MATERIAL DESUBICADO POR LA LINEA
                $GLOBALS["NotificaErrorPorEmail"] = "No";
                $rowUbiOrigen                     = $bd->VerRegRest("UBICACION", "ID_ALMACEN = $rowAlmacenSalida->ID_ALMACEN AND TIPO_UBICACION = 'Salida'", "No");
                unset($GLOBALS["NotificaErrorPorEmail"]);
            endif;

            //COMPRUEBO QUE EXISTE LA UBICACION DE SALIDA DE MATERIAL EN EL ALMACEN QUE ESTOY
            if ($rowUbiOrigen == false):
                $arrDevuelto['errores'] = $auxiliar->traduce("No hay definida una ubicacion de tipo salida en el almacen", $administrador->ID_IDIOMA) . " " . $rowAlmacenSalida->REFERENCIA . " - " . $rowAlmacenSalida->NOMBRE;
            endif;
        endif;
        //FIN ACCIONES PARA DESCONTAR EL MATERIAL DE DONDE CORRESPONDA

        //SI NO SE HAN PRODUCIDO ERRORES EJECUTAMOS LAS OPERACIONES CORRESPONDIENTES
        if ((!(isset($arrDevuelto['errores']))) || ($arrDevuelto['errores'] == "")):

            //BUSCO EL MATERIAL
            $rowMat = $bd->VerReg("MATERIAL", "ID_MATERIAL", $rowMovimientoSalidaLinea->ID_MATERIAL);

            //ACCIONES SOLO SI LA CANTIDAD ES POSITIVA, PUEDE QUE ESTEMOS DESASIGNANDO UNA LINEA QUE AUN NO SE HAYA EMPEZADO A PREPARAR Y TENGA CANTIDAD CERO
            if ($rowMovimientoSalidaLinea->CANTIDAD > 0):

                //CREO UNA TRANSFERENCIA DE TIPO 'ReubicacionMaterial' PARA MOVER EL MATERIAL DE ORIGEN A LA UBICACION CORRESPONDIENTE (PENDIENTE DE UBICAR SI PREPARACION VIA PDA O LA UBICACION ORIGINAL SI PREPARACION VIA WEB)
                $sqlInsert = "INSERT INTO MOVIMIENTO_TRANSFERENCIA SET
                                ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                , ID_MATERIAL = $rowMovimientoSalidaLinea->ID_MATERIAL
                                , ID_MATERIAL_FISICO = " . ($rowMovimientoSalidaLinea->ID_MATERIAL_FISICO == NULL ? 'NULL' : $rowMovimientoSalidaLinea->ID_MATERIAL_FISICO) . "
                                , ID_UBICACION_ORIGEN = $rowUbiOrigen->ID_UBICACION
                                , ID_UBICACION_DESTINO = $rowUbiDestino->ID_UBICACION
                                , ID_MOVIMIENTO_SALIDA = $rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA
                                , ID_MOVIMIENTO_SALIDA_LINEA = $rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA_LINEA
                                , ID_ORDEN_PREPARACION = $rowMovimientoSalida->ID_ORDEN_PREPARACION
                                , ID_ORDEN_TRABAJO_MOVIMIENTO = " . ($rowMovimientoSalidaLinea->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? 'NULL' : "$rowMovimientoSalidaLinea->ID_ORDEN_TRABAJO_MOVIMIENTO") . "
                                , ID_INCIDENCIA_CALIDAD = " . ($rowMovimientoSalidaLinea->ID_INCIDENCIA_CALIDAD == NULL ? 'NULL' : "$rowMovimientoSalidaLinea->ID_INCIDENCIA_CALIDAD") . "
                                , CANTIDAD = $rowMovimientoSalidaLinea->CANTIDAD
                                , FECHA = '" . date("Y-m-d H:i:s") . "'
                                , TIPO = 'ReubicacionMaterial'
                                , STOCK_OK = " . ($rowMovimientoSalidaLinea->ID_TIPO_BLOQUEO == NULL ? $rowMovimientoSalidaLinea->CANTIDAD : 0) . "
                                , STOCK_BLOQUEADO = " . ($rowMovimientoSalidaLinea->ID_TIPO_BLOQUEO == NULL ? 0 : $rowMovimientoSalidaLinea->CANTIDAD) . "
                                , ID_TIPO_BLOQUEO = " . ($rowMovimientoSalidaLinea->ID_TIPO_BLOQUEO == NULL ? 'NULL' : $rowMovimientoSalidaLinea->ID_TIPO_BLOQUEO);
                $bd->ExecSQL($sqlInsert);
                $idTransferencia = $bd->IdAsignado();

                // LOG MOVIMIENTOS
                $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Creación", "Transferencia", $idTransferencia, 'ReubicacionMaterial');

                //BUSCO EL MATERIAL UBICACION ORIGEN (SALIDA DE MATERIAL)
                $GLOBALS["NotificaErrorPorEmail"] = "No";
                $rowMaterialUbicacionOrigen       = $bd->VerRegRest("MATERIAL_UBICACION", "ID_MATERIAL = $rowMovimientoSalidaLinea->ID_MATERIAL AND ID_UBICACION = $rowUbiOrigen->ID_UBICACION AND ID_MATERIAL_FISICO " . ($rowMovimientoSalidaLinea->ID_MATERIAL_FISICO == NULL ? 'IS NULL' : "= $rowMovimientoSalidaLinea->ID_MATERIAL_FISICO") . " AND ID_TIPO_BLOQUEO " . ($rowMovimientoSalidaLinea->ID_TIPO_BLOQUEO == NULL ? 'IS NULL' : "= $rowMovimientoSalidaLinea->ID_TIPO_BLOQUEO") . " AND ID_ORDEN_TRABAJO_MOVIMIENTO " . ($rowMovimientoSalidaLinea->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? 'IS NULL' : "= $rowMovimientoSalidaLinea->ID_ORDEN_TRABAJO_MOVIMIENTO") . " AND ID_INCIDENCIA_CALIDAD " . ($rowMovimientoSalidaLinea->ID_INCIDENCIA_CALIDAD == NULL ? 'IS NULL' : "= $rowMovimientoSalidaLinea->ID_INCIDENCIA_CALIDAD"), "No");
                unset($GLOBALS["NotificaErrorPorEmail"]);

                //COMPRUEBO QUE EXISTE EL MATERIAL UBICACION DONDE DECREMENTAR EL MATERIAL
                if ($rowMaterialUbicacionOrigen == false):
                    $arrDevuelto['errores'] = $auxiliar->traduce("No hay stock para reubicar esta linea", $administrador->ID_IDIOMA) . ". " . $auxiliar->traduce("Material", $administrador->ID_IDIOMA) . ": " . $rowMat->REFERENCIA_SGA . ". " . $auxiliar->traduce("Ubicacion", $administrador->ID_IDIOMA) . ": " . $rowUbiOrigen->UBICACION;
                endif;

                //COMPRUEBO QUE HAYA EN LA UBICACION ORIGEN SUFICIENTE STOCKE PARA DECREMENTAR EL MATERIAL
                if ($rowMaterialUbicacionOrigen->STOCK_TOTAL < $rowMovimientoSalidaLinea->CANTIDAD):
                    $arrDevuelto['errores'] = $auxiliar->traduce("No hay stock suficiente en la ubicacion para reubicar esta linea", $administrador->ID_IDIOMA) . ". " . $auxiliar->traduce("Material", $administrador->ID_IDIOMA) . ": " . $rowMat->REFERENCIA_SGA . ". " . $auxiliar->traduce("Ubicacion", $administrador->ID_IDIOMA) . ": " . $rowUbiOrigen->UBICACION;
                endif;

                //SI NO SE HAN PRODUCIDO ERRORES EJECUTAMOS LAS OPERACIONES CORRESPONDIENTES
                if ((!(isset($arrDevuelto['errores']))) || ($arrDevuelto['errores'] == "")):

                    //DECREMENTO LA CANTIDAD EN MATERIAL UBICACION ORIGEN
                    $sqlUpdate = "UPDATE MATERIAL_UBICACION SET
                                    STOCK_TOTAL = STOCK_TOTAL - $rowMovimientoSalidaLinea->CANTIDAD
                                    , STOCK_OK = STOCK_OK - " . ($rowMovimientoSalidaLinea->ID_TIPO_BLOQUEO == NULL ? $rowMovimientoSalidaLinea->CANTIDAD : 0) . "
                                    , STOCK_BLOQUEADO = STOCK_BLOQUEADO - " . ($rowMovimientoSalidaLinea->ID_TIPO_BLOQUEO == NULL ? 0 : $rowMovimientoSalidaLinea->CANTIDAD) . "
                                    WHERE ID_MATERIAL_UBICACION = $rowMaterialUbicacionOrigen->ID_MATERIAL_UBICACION";//echo($sqlUpdate."<hr>");
                    $bd->ExecSQL($sqlUpdate);

                    //BUSCO EL MATERIAL UBICACION DESTINO (PENDIENTE DE UBICAR SI PREPARACION VIA PDA O LA UBICACION ORIGINAL SI PREPARACION VIA WEB)
                    $GLOBALS["NotificaErrorPorEmail"] = "No";
                    $rowMaterialUbicacionDestino      = $bd->VerRegRest("MATERIAL_UBICACION", "ID_MATERIAL = $rowMovimientoSalidaLinea->ID_MATERIAL AND ID_UBICACION = $rowUbiDestino->ID_UBICACION AND ID_MATERIAL_FISICO " . ($rowMovimientoSalidaLinea->ID_MATERIAL_FISICO == NULL ? 'IS NULL' : " = $rowMovimientoSalidaLinea->ID_MATERIAL_FISICO") . " AND ID_TIPO_BLOQUEO " . ($rowMovimientoSalidaLinea->ID_TIPO_BLOQUEO == NULL ? 'IS NULL' : "= $rowMovimientoSalidaLinea->ID_TIPO_BLOQUEO") . " AND ID_ORDEN_TRABAJO_MOVIMIENTO " . ($rowMovimientoSalidaLinea->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? 'IS NULL' : "= $rowMovimientoSalidaLinea->ID_ORDEN_TRABAJO_MOVIMIENTO") . " AND ID_INCIDENCIA_CALIDAD " . ($rowMovimientoSalidaLinea->ID_INCIDENCIA_CALIDAD == NULL ? 'IS NULL' : "= $rowMovimientoSalidaLinea->ID_INCIDENCIA_CALIDAD"), "No");
                    unset($GLOBALS["NotificaErrorPorEmail"]);

                    //EXTRAIGO EL IDENTIFICADOR MATERIAL UBICACION DONDE INCREMENTAR LA CANTIDAD REUBICADA
                    if ($rowMaterialUbicacionDestino == false):
                        //CREO MATERIAL UBICACION DESTINO (PENDIENTE DE UBICAR)
                        $sqlInsert = "INSERT INTO MATERIAL_UBICACION SET
                                        ID_MATERIAL = $rowMovimientoSalidaLinea->ID_MATERIAL
                                        , ID_UBICACION = $rowUbiDestino->ID_UBICACION
                                        , ID_MATERIAL_FISICO = " . ($rowMovimientoSalidaLinea->ID_MATERIAL_FISICO == NULL ? 'NULL' : "$rowMovimientoSalidaLinea->ID_MATERIAL_FISICO") . "
                                        , ID_TIPO_BLOQUEO = " . ($rowMovimientoSalidaLinea->ID_TIPO_BLOQUEO == NULL ? 'NULL' : "$rowMovimientoSalidaLinea->ID_TIPO_BLOQUEO") . "
                                        , ID_ORDEN_TRABAJO_MOVIMIENTO = " . ($rowMovimientoSalidaLinea->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? 'NULL' : "$rowMovimientoSalidaLinea->ID_ORDEN_TRABAJO_MOVIMIENTO") . "
                                        , ID_INCIDENCIA_CALIDAD = " . ($rowMovimientoSalidaLinea->ID_INCIDENCIA_CALIDAD == NULL ? 'NULL' : "$rowMovimientoSalidaLinea->ID_INCIDENCIA_CALIDAD");
                        $bd->ExecSQL($sqlInsert);
                        $idMaterialUbicacionDestino = $bd->IdAsignado();
                    else:
                        $idMaterialUbicacionDestino = $rowMaterialUbicacionDestino->ID_MATERIAL_UBICACION;
                    endif;

                    //INCREMENTO LA CANTIDAD EN MATERIAL UBICACION DESTINO (PENDIENTE DE UBICAR SI PREPARACION VIA PDA O LA UBICACION ORIGINAL SI PREPARACION VIA WEB)
                    $sqlUpdate = "UPDATE MATERIAL_UBICACION SET
                                    STOCK_TOTAL = STOCK_TOTAL + $rowMovimientoSalidaLinea->CANTIDAD
                                    , STOCK_OK = STOCK_OK + " . ($rowMovimientoSalidaLinea->ID_TIPO_BLOQUEO == NULL ? $rowMovimientoSalidaLinea->CANTIDAD : 0) . "
                                    , STOCK_BLOQUEADO = STOCK_BLOQUEADO + " . ($rowMovimientoSalidaLinea->ID_TIPO_BLOQUEO == NULL ? 0 : $rowMovimientoSalidaLinea->CANTIDAD) . "
                                    WHERE ID_MATERIAL_UBICACION = $idMaterialUbicacionDestino";//echo($sqlUpdate."<hr>");
                    $bd->ExecSQL($sqlUpdate);

                    //SI LA PREPARACION ES EN PDA HAY QUE MODIFICAR LA UBICACION DE LA POSIBLE RESERVA, YA QUE EL MATERIAL NO VUELVE A LA UBICACION ORIGEN SINO A PENDIENTE DE UBICAR
                    if ($rowOrdenPreparacion->VIA_PREPARACION == 'PDA'):

                        //BUSCO LAS RESERVAS LINEA DE LA DEMANDA
                        $resultLineasReserva = $reserva->get_reservas_linea_pedido($rowMovimientoSalidaLinea->ID_PEDIDO_SALIDA_LINEA, 'Finalizada');
                        //SI EXISTE LA DEMANDA
                        if ($resultLineasReserva != false):
                            //BUSCO LAS RESERVAS LINEA ESPECIFICAS DE LA DEMANDA
                            $resultLineasReservaEspecificas = $reserva->get_reservas_lineas_especificas($resultLineasReserva, $rowMovimientoSalidaLinea->ID_MATERIAL, $rowMovimientoSalidaLinea->ID_UBICACION, $rowMovimientoSalidaLinea->ID_TIPO_BLOQUEO, $rowMovimientoSalidaLinea->ID_MATERIAL_FISICO, $rowMovimientoSalidaLinea->ID_ORDEN_TRABAJO_MOVIMIENTO, $rowMovimientoSalidaLinea->ID_INCIDENCIA_CALIDAD);

                            //SI EXISTEN LINEAS DE RESERVA VALIDAS
                            if ($resultLineasReservaEspecificas != false):
                                //LLAMO A MODIFICAR LA UBICACION PARA UNA PARTE
                                $arrDevueltoReservas = $reserva->modificar_ubicacion_reserva_lineas($resultLineasReservaEspecificas, $rowUbiDestino->ID_UBICACION, $rowMovimientoSalidaLinea->CANTIDAD);

                                //SI SE DEVUELVEN ERRORES
                                if ((isset($arrDevueltoReservas['error'])) && ($arrDevueltoReservas['error'] != "")):
                                    $arrDevuelto['errores'] = $arrDevueltoReservas['error'];
                                endif;
                            endif;
                            //FIN SI EXISTEN LINEAS DE RESERVA VALIDAS
                        endif;
                        //FIN SI EXISTE LA DEMANDA
                    endif;

                    //SI TIENE ALBARAN ASIGNADO LO DECREMENTO
                    if ($rowMovimientoSalidaLinea->ID_ALBARAN_LINEA != NULL):
                        $albaran->quitar_linea($rowMovimientoSalidaLinea); //ESTA FUNCION VACIA EL ID_ALBARAN E ID_ALBARAN_LINEA DE LA LINEA DEL MOVIMIENTO DE SALIDA

                        /************************************ DESHABILITO TRIGGERS IMPLICADOS PORQUE SE HAN HABILITADO DENTRO DE LA FUNCION ************************************/
                        $sqlDeshabilitarTriggers = "SET @Trigger_MOVIMIENTO_SALIDA_LINEA_UPDATE_Deshabilitado = 1";
                        $bd->ExecSQL($sqlDeshabilitarTriggers);
                        /********************************** FIN DESHABILITO TRIGGERS IMPLICADOS PORQUE SE HAN HABILITADO DENTRO DE LA FUNCION **********************************/
                    endif;

                    //COMPRUEBO SI HAY UNA LINEA SIMILAR A LA QUE AGRUPAR LA LINEA QUE ESTOY REUBICANDO
                    $GLOBALS["NotificaErrorPorEmail"] = "No";
                    $rowLineaMovimientoSimilar        = $bd->VerRegRest("MOVIMIENTO_SALIDA_LINEA",
                        "ID_MOVIMIENTO_SALIDA = $rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA AND
                                                                        ID_ALMACEN = $rowMovimientoSalidaLinea->ID_ALMACEN AND
                                                                        ID_PEDIDO_SALIDA_LINEA = $rowMovimientoSalidaLinea->ID_PEDIDO_SALIDA_LINEA AND
                                                                        ID_MATERIAL = $rowMovimientoSalidaLinea->ID_MATERIAL AND
                                                                        ID_UBICACION = $rowUbiDestino->ID_UBICACION AND
                                                                        ID_MATERIAL_FISICO " . ($rowMovimientoSalidaLinea->ID_MATERIAL_FISICO == NULL ? 'IS NULL' : "= $rowMovimientoSalidaLinea->ID_MATERIAL_FISICO") . " AND
                                                                        ID_TIPO_BLOQUEO " . ($rowMovimientoSalidaLinea->ID_TIPO_BLOQUEO == NULL ? 'IS NULL' : "= $rowMovimientoSalidaLinea->ID_TIPO_BLOQUEO") . " AND
                                                                        ID_ORDEN_TRABAJO_MOVIMIENTO " . ($rowMovimientoSalidaLinea->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? 'IS NULL' : "= $rowMovimientoSalidaLinea->ID_ORDEN_TRABAJO_MOVIMIENTO") . " AND
                                                                        ID_INCIDENCIA_CALIDAD " . ($rowMovimientoSalidaLinea->ID_INCIDENCIA_CALIDAD == NULL ? 'IS NULL' : "= $rowMovimientoSalidaLinea->ID_INCIDENCIA_CALIDAD") . " AND
                                                                        ID_EXPEDICION IS NULL AND
                                                                        ID_BULTO_LINEA IS NULL AND
                                                                        ID_CONTENEDOR_LINEA IS NULL AND
                                                                        ID_MOVIMIENTO_SALIDA_LINEA <> $rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA_LINEA AND
                                                                        ESTADO = 'Reservado para Preparacion' AND LINEA_ANULADA = 0 AND BAJA = 0", "No");
                    unset($GLOBALS["NotificaErrorPorEmail"]);

                    //ACCIONES EN FUNCION DE SI EXISTE UNA LINEA SIMILAR O NO
                    if ($rowLineaMovimientoSimilar != false):   //EXISTE UNA LINEA SIMILAR

                        //HAGO EL SPLIT INVERSO DE LAS POSIBLES LINEAS DE NECESIDAD ASOCIADAS
                        $necesidad->ModificarLineasMovimientoAsociadasEnNecesidadesReubicacion($rowLineaMovimientoSimilar->ID_MOVIMIENTO_SALIDA_LINEA, $rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA_LINEA);

                        //MARCO LA LINEA DE BAJA
                        $sqlDelete = "UPDATE MOVIMIENTO_SALIDA_LINEA SET
                                      ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                      , BAJA = 1
                                      , ID_CONTENEDOR = NULL
                                      , ID_CONTENEDOR_LINEA = NULL
                                      , ID_BULTO = NULL
                                      , ID_BULTO_LINEA = NULL
                                      WHERE ID_MOVIMIENTO_SALIDA_LINEA = $rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA_LINEA";
                        $bd->ExecSQL($sqlDelete);

                        //INCREMENTO LA CANTIDAD DE LA LINEA SIMILAR
                        $sqlUpdate = "UPDATE MOVIMIENTO_SALIDA_LINEA SET
                                    ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                    , CANTIDAD = CANTIDAD + $rowMovimientoSalidaLinea->CANTIDAD
                                    WHERE ID_MOVIMIENTO_SALIDA_LINEA = $rowLineaMovimientoSimilar->ID_MOVIMIENTO_SALIDA_LINEA";//echo($sqlUpdate."<hr>");
                        $bd->ExecSQL($sqlUpdate);

                        $rowLineaMovimientoSimilarActualizada = $bd->VerReg("MOVIMIENTO_SALIDA_LINEA", "ID_MOVIMIENTO_SALIDA_LINEA", $rowLineaMovimientoSimilar->ID_MOVIMIENTO_SALIDA_LINEA);
                        if ($rowLineaMovimientoSimilarActualizada->CANTIDAD > $rowLineaMovimientoSimilarActualizada->CANTIDAD_PEDIDO):
                            $arrDevuelto['errores'] = $auxiliar->traduce("Se ha producido un error critico al reubicar esta linea", $administrador->ID_IDIOMA);
                        endif;

                        //ME GUARDO LA LINEA DONDE SE HA REUBICADO EL MATERIAL Y LA CANTIDAD REUBICADA
                        $arrDevuelto['idMovimientoSalidaLineaReubicacionMaterial'] = $rowLineaMovimientoSimilar->ID_MOVIMIENTO_SALIDA_LINEA;
                        $arrDevuelto['cantidadReubicacionMaterial']                = $rowMovimientoSalidaLinea->CANTIDAD;

                    else:   //NO EXISTE UNA LINEA SIMILAR

                        //BUSCO LA LINEA DEL MOVIMIENTO DE SALIDA PREVIO A LA ACTUALIZACION
                        $rowLineaMovimientoSalidaPrevioActualizacion = $bd->VerReg("MOVIMIENTO_SALIDA_LINEA", "ID_MOVIMIENTO_SALIDA_LINEA", $rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA_LINEA);

                        //VACIO LOS DATOS DE LA LINEA ACTUAL QUE SE GRABARON AL DESUBICAR
                        $sqlUpdate = "UPDATE MOVIMIENTO_SALIDA_LINEA SET
                                        ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                        , ID_UBICACION = $rowUbiDestino->ID_UBICACION
                                        , ID_CONTENEDOR = NULL
                                        , ID_CONTENEDOR_LINEA = NULL
                                        , ID_BULTO = NULL
                                        , ID_BULTO_LINEA = NULL
                                        , ID_EXPEDICION = NULL
                                        , EXPEDICION_SAP = ''
                                        , ID_EXPEDICION_SAP = NULL
                                        , ESTADO = 'Reservado para Preparacion'
                                        , FECHA_PREPARACION = '0000-00-00 00:00:00'
                                        WHERE ID_MOVIMIENTO_SALIDA_LINEA = $rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA_LINEA";//echo($sqlUpdate."<hr>");
                        $bd->ExecSQL($sqlUpdate);

                        //BUSCO LA LINEA DEL MOVIMIENTO DE SALIDA POSTERIOR A LA ACTUALIZACION
                        $rowLineaMovimientoSalidaPosteriorActualizacion = $bd->VerReg("MOVIMIENTO_SALIDA_LINEA", "ID_MOVIMIENTO_SALIDA_LINEA", $rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA_LINEA);

                        // LOG MOVIMIENTOS
                        $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Mov. salida", $rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA, "Desasignar expedicion SAP a la linea de movimiento con ID: $rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA_LINEA", "MOVIMIENTO_SALIDA_LINEA", $rowLineaMovimientoSalidaPrevioActualizacion, $rowLineaMovimientoSalidaPosteriorActualizacion);

                        //SI LA LINEA TENIA ID_EXPEDICION_SAP ASIGNADA LA DOY DE BAJA SI PUEDO
                        $exp_SAP->darDeBajaIdExpedicionSAPSiCorresponde($rowMovimientoSalidaLinea->ID_EXPEDICION_SAP);

                        //ME GUARDO LA LINEA DONDE SE HA REUBICADO EL MATERIAL Y LA CANTIDAD REUBICADA
                        $arrDevuelto['idMovimientoSalidaLineaReubicacionMaterial'] = $rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA_LINEA;
                        $arrDevuelto['cantidadReubicacionMaterial']                = $rowMovimientoSalidaLinea->CANTIDAD;

                    endif;
                    //FIN ACCIONES EN FUNCION DE SI EXISTE UNA LINEA SIMILAR O NO

                    //BUSCO EL TIPO DE BLOQUEO PREVENTIVO
                    $rowTipoBloqueoPreventivo = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "SP");

                    //SI EL TIPO DE BLOQUEO ES OK O PREVENTIVO, DEBEREMOS ANULAR LA LIBERACION DEL MATERIAL RESERVADO PARA PREPARACION
                    if (($rowMovimientoSalidaLinea->ID_TIPO_BLOQUEO == NULL) || ($rowMovimientoSalidaLinea->ID_TIPO_BLOQUEO == $rowTipoBloqueoPreventivo->ID_TIPO_BLOQUEO)):
                        //BUSCO EL TIPO DE BLOQUEO RESERVADO PARA PREPARACION
                        $rowTipoBloqueoReservadoParaPreparacion = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", 'RP');

                        //BUSCO EL TIPO DE BLOQUEO RESERVADO PARA PREPARACION PREVENTIVO
                        $rowTipoBloqueoReservadoParaPreparacionPreventivo = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", 'RPP');

                        //EN FUNCION DEL TIPO DE BLOQUEO A REUBICAR SELECCIONAMOS EL TIPO DE BLOQUEO A RESERVAR
                        if ($rowMovimientoSalidaLinea->ID_TIPO_BLOQUEO == NULL):
                            $rowTipoBloqueoReservar = $rowTipoBloqueoReservadoParaPreparacion;
                        elseif ($rowMovimientoSalidaLinea->ID_TIPO_BLOQUEO == $rowTipoBloqueoPreventivo->ID_TIPO_BLOQUEO):
                            $rowTipoBloqueoReservar = $rowTipoBloqueoReservadoParaPreparacionPreventivo;
                        else:
                            $rowTipoBloqueoReservar = $rowTipoBloqueoReservadoParaPreparacion;
                        endif;

                        //BLOQUEO DE NUEVO EL MATERIAL CORRESPONDIENTE MEDIANTE UN CAMBIO DE ESTADO
                        $sqlInsert = "INSERT INTO CAMBIO_ESTADO SET
                                    FECHA = '" . date("Y-m-d H:i:s") . "'
                                        , TIPO_CAMBIO_ESTADO = '" . ($rowMovimientoSalidaLinea->ID_TIPO_BLOQUEO == $rowTipoBloqueoPreventivo->ID_TIPO_BLOQUEO ? "AnulacionLiberarReservadoParaPreparacionPreventivo" : "AnulacionLiberarReservadoParaPreparacion") . "'
                                    , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                    , ID_MATERIAL = $rowMovimientoSalidaLinea->ID_MATERIAL
                                    , ID_MATERIAL_FISICO = " . ($rowMovimientoSalidaLinea->ID_MATERIAL_FISICO == NULL ? 'NULL' : $rowMovimientoSalidaLinea->ID_MATERIAL_FISICO) . "
                                    , ID_UBICACION = $rowUbiDestino->ID_UBICACION
                                    , CANTIDAD = $rowMovimientoSalidaLinea->CANTIDAD
                                    , ID_TIPO_BLOQUEO_INICIAL = " . ($rowMovimientoSalidaLinea->ID_TIPO_BLOQUEO == NULL ? 'NULL' : $rowMovimientoSalidaLinea->ID_TIPO_BLOQUEO) . "
                                        , ID_TIPO_BLOQUEO_FINAL = $rowTipoBloqueoReservar->ID_TIPO_BLOQUEO
                                    , ID_ORDEN_TRABAJO_MOVIMIENTO = " . ($rowMovimientoSalidaLinea->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? 'NULL' : $rowMovimientoSalidaLinea->ID_ORDEN_TRABAJO_MOVIMIENTO) . "
                                    , ID_INCIDENCIA_CALIDAD = " . ($rowMovimientoSalidaLinea->ID_INCIDENCIA_CALIDAD == NULL ? 'NULL' : $rowMovimientoSalidaLinea->ID_INCIDENCIA_CALIDAD);
                        $bd->ExecSQL($sqlInsert);//exit($sqlInsert);
                        $idCambioEstado = $bd->IdAsignado();

                        //DECREMENTO LA CANTIDAD EN MATERIAL UBICACION ORIGEN
                        $sqlUpdate = "UPDATE MATERIAL_UBICACION SET
                                    STOCK_TOTAL = STOCK_TOTAL - $rowMovimientoSalidaLinea->CANTIDAD
                                    , STOCK_OK = STOCK_OK - " . ($rowMovimientoSalidaLinea->ID_TIPO_BLOQUEO == NULL ? $rowMovimientoSalidaLinea->CANTIDAD : 0) . "
                                    , STOCK_BLOQUEADO = STOCK_BLOQUEADO - " . ($rowMovimientoSalidaLinea->ID_TIPO_BLOQUEO == NULL ? 0 : $rowMovimientoSalidaLinea->CANTIDAD) . "
                                    WHERE ID_MATERIAL_UBICACION = $idMaterialUbicacionDestino";//echo($sqlUpdate."<hr>");
                        $bd->ExecSQL($sqlUpdate);

                        //BUSCO SI EXISTE LA UBICACION DESTINO, EN CASO CONTRARIO LA CREO
                        $GLOBALS["NotificaErrorPorEmail"] = "No";
                        $rowMatUbiDestino                 = $bd->VerRegRest("MATERIAL_UBICACION", "ID_MATERIAL = $rowMovimientoSalidaLinea->ID_MATERIAL AND ID_UBICACION = $rowUbiDestino->ID_UBICACION AND ID_MATERIAL_FISICO " . ($rowMovimientoSalidaLinea->ID_MATERIAL_FISICO == NULL ? 'IS NULL' : "= $rowMovimientoSalidaLinea->ID_MATERIAL_FISICO") . " AND ID_TIPO_BLOQUEO = $rowTipoBloqueoReservar->ID_TIPO_BLOQUEO  AND ID_ORDEN_TRABAJO_MOVIMIENTO " . ($rowMovimientoSalidaLinea->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? 'IS NULL' : "= $rowMovimientoSalidaLinea->ID_ORDEN_TRABAJO_MOVIMIENTO") . " AND ID_INCIDENCIA_CALIDAD " . ($rowMovimientoSalidaLinea->ID_INCIDENCIA_CALIDAD == NULL ? 'IS NULL' : "= $rowMovimientoSalidaLinea->ID_INCIDENCIA_CALIDAD"), "No");
                        unset($GLOBALS["NotificaErrorPorEmail"]);
                        if ($rowMatUbiDestino == false):
                            $sqlInsert = "INSERT INTO MATERIAL_UBICACION SET
                                        ID_MATERIAL = $rowMovimientoSalidaLinea->ID_MATERIAL
                                        , ID_UBICACION = $rowUbiDestino->ID_UBICACION
                                        , ID_MATERIAL_FISICO = " . ($rowMovimientoSalidaLinea->ID_MATERIAL_FISICO == NULL ? 'NULL' : $rowMovimientoSalidaLinea->ID_MATERIAL_FISICO) . "
                                            , ID_TIPO_BLOQUEO = $rowTipoBloqueoReservar->ID_TIPO_BLOQUEO
                                        , ID_ORDEN_TRABAJO_MOVIMIENTO = " . ($rowMovimientoSalidaLinea->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? 'NULL' : $rowMovimientoSalidaLinea->ID_ORDEN_TRABAJO_MOVIMIENTO) . "
                                        , ID_INCIDENCIA_CALIDAD = " . ($rowMovimientoSalidaLinea->ID_INCIDENCIA_CALIDAD == NULL ? 'NULL' : $rowMovimientoSalidaLinea->ID_INCIDENCIA_CALIDAD);//echo($sqlInsert."<hr>");
                            $bd->ExecSQL($sqlInsert);
                            $idMatUbiDestino = $bd->IdAsignado();
                        else:
                            $idMatUbiDestino = $rowMatUbiDestino->ID_MATERIAL_UBICACION;
                        endif;

                        //INCREMENTO LA CANTIDAD EN MATERIAL UBICACION DESTINO (SM)
                        $sqlUpdate = "UPDATE MATERIAL_UBICACION SET
                                    STOCK_TOTAL = STOCK_TOTAL + $rowMovimientoSalidaLinea->CANTIDAD
                                    , STOCK_BLOQUEADO = STOCK_BLOQUEADO + $rowMovimientoSalidaLinea->CANTIDAD
                                    WHERE ID_MATERIAL_UBICACION = $idMatUbiDestino";//echo($sqlUpdate."<hr>");
                        $bd->ExecSQL($sqlUpdate);
                    endif;
                    //FIN SI EL TIPO DE BLOQUEO ES OK, DEBEREMOS ANULAR LA LIBERACION DEL MATERIAL RESERVADO PARA PREPARACION
                endif;
            endif;
            //FIN ACCIONES SOLO SI LA CANTIDAD ES POSITIVA, PUEDE QUE ESTEMOS DESASIGNANDO UNA LINEA QUE AUN NO SE HAYA EMPEZADO A PREPARAR Y TENGA CANTIDAD CERO

            //SI TIENE CONTENDOR ASIGNADO LO DECREMENTO
            if ($rowMovimientoSalidaLinea->ID_CONTENEDOR_LINEA != NULL):
                //ACTUALIZO LA CANTIDAD DEL CONTENEDOR LINEA
                $sqlUpdate = "UPDATE CONTENEDOR_LINEA SET
                                STOCK_TOTAL = STOCK_TOTAL - $rowMovimientoSalidaLinea->CANTIDAD
                                , STOCK_OK = STOCK_OK - " . ($rowMovimientoSalidaLinea->ID_TIPO_BLOQUEO == NULL ? $rowMovimientoSalidaLinea->CANTIDAD : 0) . "
                                , STOCK_BLOQUEADO = STOCK_BLOQUEADO - " . ($rowMovimientoSalidaLinea->ID_TIPO_BLOQUEO == NULL ? 0 : $rowMovimientoSalidaLinea->CANTIDAD) . "
                                WHERE ID_CONTENEDOR_LINEA = $rowMovimientoSalidaLinea->ID_CONTENEDOR_LINEA";
                $bd->ExecSQL($sqlUpdate);

                //BUSCO LAS LINEAS DE MOVIMIENTO TIENEN ASIGNADA ESTA LINEA DE CONTENEDOR
                $sqlLinenasAsignadasContenedorLinea    = "SELECT ID_MOVIMIENTO_SALIDA_LINEA, BAJA
                                                           FROM MOVIMIENTO_SALIDA_LINEA
                                                           WHERE ID_CONTENEDOR_LINEA = $rowMovimientoSalidaLinea->ID_CONTENEDOR_LINEA AND BAJA = 1";
                $resultLinenasAsignadasContenedorLinea = $bd->ExecSQL($sqlLinenasAsignadasContenedorLinea);
                if ($bd->NumRegs($resultLinenasAsignadasContenedorLinea) > 0):
                    while ($rowLinenaAsignadasContenedorLinea = $bd->SigReg($resultLinenasAsignadasContenedorLinea)):
                        $sqlUpdate = "UPDATE MOVIMIENTO_SALIDA_LINEA SET
                                        ID_CONTENEDOR = NULL
                                        , ID_CONTENEDOR_LINEA = NULL
                                        WHERE ID_MOVIMIENTO_SALIDA_LINEA = $rowLinenaAsignadasContenedorLinea->ID_MOVIMIENTO_SALIDA_LINEA";
                        $bd->ExecSQL($sqlUpdate);
                    endwhile;
                endif;

                //BORRO EL CONTENEDOR LINEA SI NO TIENE STOCK
                $num = $bd->NumRegsTabla("CONTENEDOR_LINEA", "ID_CONTENEDOR_LINEA = $rowMovimientoSalidaLinea->ID_CONTENEDOR_LINEA AND STOCK_TOTAL = 0");
                if ($num > 0):
                    $sqlDelete = "DELETE FROM CONTENEDOR_LINEA WHERE ID_CONTENEDOR_LINEA = $rowMovimientoSalidaLinea->ID_CONTENEDOR_LINEA AND STOCK_TOTAL = 0";
                    $bd->ExecSQL($sqlDelete);
                endif;
            endif;

            //ACTUALIZO LOS CAMPOS ADR Y NACIONAL
            if ($rowMovimientoSalidaLinea->ID_EXPEDICION != NULL):
                //$this->calcularAmbitoYComunidadExpedicion($rowMovimientoSalidaLinea->ID_EXPEDICION);
                //$this->calcularADRExpedicion($rowMovimientoSalidaLinea->ID_EXPEDICION);
                //AÑADO AL ARRAY A DEVOLVER LA ORDEN DE RECOGIDA
                $arrDevuelto['idExpedicion'] = $rowMovimientoSalidaLinea->ID_EXPEDICION;
            endif;

            //SI TIENE BULTO ASIGNADO LO DECREMENTO
            if ($rowMovimientoSalidaLinea->ID_BULTO_LINEA != NULL):
                //BUSCO LAS LINEAS DE MOVIMIENTO DE ENTRADA QUE HAYAN PODIDO SER RECEPCIONADAS BAJO ESTA LINEA DE BULTO
                $sqlBultoLineasRecepcionados    = "SELECT ID_MOVIMIENTO_ENTRADA_LINEA
                                                   FROM MOVIMIENTO_ENTRADA_LINEA
                                                   WHERE ID_BULTO_LINEA = $rowMovimientoSalidaLinea->ID_BULTO_LINEA AND LINEA_ANULADA = 1 AND BAJA = 0";
                $resultBultoLineasRecepcionados = $bd->ExecSQL($sqlBultoLineasRecepcionados);
                while ($rowBultoLineasRecepcionados = $bd->SigReg($resultBultoLineasRecepcionados)):
                    $sqlUpdate = "UPDATE MOVIMIENTO_ENTRADA_LINEA SET
                                    ID_BULTO_LINEA = NULL
                                    WHERE ID_MOVIMIENTO_ENTRADA_LINEA = $rowBultoLineasRecepcionados->ID_MOVIMIENTO_ENTRADA_LINEA";
                    $bd->ExecSQL($sqlUpdate);
                endwhile;

                //ACTUALIZO LA CANTIDAD DEL BULTO LINEA
                $sqlUpdate = "UPDATE BULTO_LINEA SET
                                CANTIDAD = CANTIDAD - $rowMovimientoSalidaLinea->CANTIDAD
                                WHERE ID_BULTO_LINEA = $rowMovimientoSalidaLinea->ID_BULTO_LINEA";
                $bd->ExecSQL($sqlUpdate);

                //BORRO EL BULTO LINEA SI NO TIENE STOCK
                $sqlDelete = "DELETE FROM BULTO_LINEA WHERE ID_BULTO_LINEA = $rowMovimientoSalidaLinea->ID_BULTO_LINEA AND CANTIDAD = 0";
                $bd->ExecSQL($sqlDelete);
            endif;
            //FIN SI TIENE CONTENDOR ASIGNADO LO DECREMENTO

            //ACTUALIZO EL ESTADO DEL BULTO DE LA LINEA EN CASO DE ESTAR ASIGNADA A UNO
            if ($rowMovimientoSalidaLinea->ID_BULTO != NULL):
                $orden_preparacion->ActualizarEstadoBulto($rowMovimientoSalidaLinea->ID_BULTO, 'Abierto');

                //SI EL BULTO NO TIENE LINEAS ASOCIADAS, LO DESASOCIO DE LA ORDEN DE TRANSPORTE
                $num = $bd->NumRegsTabla("MOVIMIENTO_SALIDA_LINEA", "ID_BULTO = $rowMovimientoSalidaLinea->ID_BULTO AND LINEA_ANULADA = 0 AND BAJA = 0");
                if ($num == 0):
                    $sqlUpdate = "UPDATE BULTO SET
                                  ID_EXPEDICION = NULL
                                  WHERE ID_BULTO = $rowMovimientoSalidaLinea->ID_BULTO";
                    $bd->ExecSQL($sqlUpdate);
                endif;

                //COMPRUEBO SI ES LA ULTIMA LINEA Y EL USUARIO DECIDIO ELIMINAR EL BULTO
                if ($eliminarBulto == 1):
                    //COMPRUEBO QUE EL BULTO NO TENGA LINEAS ASOCIADAS
                    $num = $bd->NumRegsTabla("MOVIMIENTO_SALIDA_LINEA", "ID_BULTO = $rowMovimientoSalidaLinea->ID_BULTO AND LINEA_ANULADA = 0 AND BAJA = 0");
                    if ($num > 0):
                        $arrDevuelto['errores'] = $auxiliar->traduce("Hay lineas asignadas al bulto que desea eliminar", $administrador->ID_IDIOMA);
                    endif;

                    //COMPRUEBO SI EL BULTO TIENE CANTIDAD ASOCIADA AL CONTENEDOR
                    $sqlCantidadContenedor    = "SELECT SUM(STOCK_TOTAL) AS CANTIDAD
                                                FROM CONTENEDOR_LINEA CL
                                                INNER JOIN CONTENEDOR C ON C.ID_CONTENEDOR = CL.ID_CONTENEDOR
                                                WHERE C.ID_BULTO = $rowMovimientoSalidaLinea->ID_BULTO AND CL.BAJA = 0";
                    $resultCantidadContenedor = $bd->ExecSQL($sqlCantidadContenedor);
                    if (($resultCantidadContenedor != false) && ($bd->NumRegs($resultCantidadContenedor) == 1)):
                        $rowCantidadContenedor = $bd->SigReg($resultCantidadContenedor);
                        if ($rowCantidadContenedor->CANTIDAD > 0):
                            $arrDevuelto['errores'] = $auxiliar->traduce("El bulto que desea eliminar tiene cantidad asignada", $administrador->ID_IDIOMA);
                        endif;
                    endif;

                    //ELIMINO EL CONTENEDOR DEL BULTO
                    $sqlDelete = "DELETE FROM CONTENEDOR WHERE ID_BULTO = $rowMovimientoSalidaLinea->ID_BULTO";
                    $bd->ExecSQL($sqlDelete);

                    //ELIMINO LA ETIQUETA PENDIENTE DE IMPRIMIR
                    $sqlDelete = "DELETE FROM ETIQUETA_PDTE WHERE TIPO = 'Bulto' AND ID_BULTO = $rowMovimientoSalidaLinea->ID_BULTO";
                    $bd->ExecSQL($sqlDelete);

                    //DOY DE BAJA EL BULTO EN OBJETOS
                    $sqlUpdate = "UPDATE SOLICITUD_TRANSPORTE_BULTO SET ID_BULTO = NULL WHERE ID_BULTO =$rowMovimientoSalidaLinea->ID_BULTO";
                    $bd->ExecSQL($sqlUpdate);
                    $sqlUpdate = "DELETE FROM BULTO_CONTRATACION_DESTINO WHERE ID_BULTO = $rowMovimientoSalidaLinea->ID_BULTO";
                    $bd->ExecSQL($sqlUpdate);
                    //ELIMINO EL BULTO
                    $sqlDelete = "DELETE FROM BULTO WHERE ID_BULTO = $rowMovimientoSalidaLinea->ID_BULTO";
                    $bd->ExecSQL($sqlDelete);

                    //ACTUALIZO LOS DATOS DE LA ORDEN DE TRANSPORTE EN CASO DE PERTENCER A UNA
                    if ($rowMovimientoSalidaLinea->ID_EXPEDICION != NULL):
                        $sqlUpdate = "UPDATE EXPEDICION SET
                                        ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                        , NUM_BULTOS = NUM_BULTOS - 1
                                        , PESO = PESO - " . $rowBulto->PESO . "
                                        WHERE ID_EXPEDICION = $rowMovimientoSalidaLinea->ID_EXPEDICION";
                        $bd->ExecSQL($sqlUpdate);

                        //BUSCO LA ORDEN DE RECOGIDA (EXPEDICION)
                        $rowExpedicion = $bd->VerReg("EXPEDICION", "ID_EXPEDICION", $rowMovimientoSalidaLinea->ID_EXPEDICION);

                        //ACTUALIZO EL PESO DE LA ORDEN DE TRANSPORTE
                        if ($rowExpedicion->ID_ORDEN_TRANSPORTE != NULL):
                            //$orden_transporte->ActualizarPeso($rowExpedicion->ID_ORDEN_TRANSPORTE);
                            //AÑADO AL ARRAY A DEVOLVER LA ORDEN DE TRANSPORTE
                            $arrDevuelto['idOrdenTransporte'] = $rowExpedicion->ID_ORDEN_TRANSPORTE;
                        endif;
                        //FIN ACTUALIZO EL PESO DE LA ORDEN DE TRANSPORTE
                    endif;
                endif;
                //FIN COMPRUEBO SI ES LA ULTIMA LINEA Y EL USUARIO DECIDIO ELIMINAR EL BULTO
            endif;
            //FIN ACTUALIZO EL ESTADO DEL BULTO DE LA LINEA EN CASO DE ESTAR ASIGNADA A UNO

            //BUSCO LAS ORDENES DE PREPARACION PARA ACTUALIZAR SU ESTADO
            $sqlOrdenesPreparacion    = "SELECT DISTINCT(ID_ORDEN_PREPARACION)
                                        FROM MOVIMIENTO_SALIDA MS
                                        INNER JOIN MOVIMIENTO_SALIDA_LINEA MSL ON MSL.ID_MOVIMIENTO_SALIDA = MS.ID_MOVIMIENTO_SALIDA
                                        WHERE ID_MOVIMIENTO_SALIDA_LINEA = $rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA_LINEA";
            $resultOrdenesPreparacion = $bd->ExecSQL($sqlOrdenesPreparacion);
            while ($rowOrdenPreparacion = $bd->SigReg($resultOrdenesPreparacion)):
                //ACTUALIZO EL ESTADO DE LA ORDEN DE PREPARACION
                //$orden_preparacion->ActualizarEstadoOrdenPreparacion($rowOrdenPreparacion->ID_ORDEN_PREPARACION);
                //AÑADO AL ARRAY A DEVOLVER LA ORDEN DE PREPARACION
                $arrDevuelto['idOrdenPreparacion'] = $rowOrdenPreparacion->ID_ORDEN_PREPARACION;
            endwhile;

            //BUSCO LOS MOVIMIENTOS DE SALIDA PARA ACTUALIZAR SU ESTADO
            $sqlMovimientosSalida    = "SELECT DISTINCT(MSL.ID_MOVIMIENTO_SALIDA)
                                        FROM MOVIMIENTO_SALIDA MS
                                        INNER JOIN MOVIMIENTO_SALIDA_LINEA MSL ON MSL.ID_MOVIMIENTO_SALIDA = MS.ID_MOVIMIENTO_SALIDA
                                        WHERE ID_MOVIMIENTO_SALIDA_LINEA = $rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA_LINEA";
            $resultMovimientosSalida = $bd->ExecSQL($sqlMovimientosSalida);
            while ($rowMovimientosSalida = $bd->SigReg($resultMovimientosSalida)):
                //ACTUALIZO EL ESTADO DEL MOVIMIENTO DE SALIDA
                //$movimiento->actualizarEstadoMovimientoSalida($rowMovimientosSalida->ID_MOVIMIENTO_SALIDA);
            endwhile;

            //ACCIONES SI LA LINEA ORIGINALMENTE ESTABA ASIGNADA A UNA ORDEN DE TRANSPORTE
            if ($rowMovimientoSalidaLinea->ID_EXPEDICION != NULL):
                //ACTUALIZAR VERSION/BULTOS DE LA ORDEN DE TRANSPORTE
                //$this->actualizar_version_bultos_orden_transporte($rowMovimientoSalidaLinea->ID_EXPEDICION);

                //ACTUALIZO EL ESTADO DE LA ORDEN DE RECOGIDA
                //$this->actualizar_estado_orden_transporte($rowMovimientoSalidaLinea->ID_EXPEDICION);

                //ACTUALIZO EL TIPO Y SUBTIPO DE LA ORDEN DE RECOGIDA
                //$this->actualizar_tipo_orden_transporte($rowMovimientoSalidaLinea->ID_EXPEDICION);
            endif;
        endif;
        //FIN SI NO SE HAN PRODUCIDO ERRORES EJECUTAMOS LAS OPERACIONES CORRESPONDIENTES

        /************************************ HABILITO TRIGGERS IMPLICADOS ************************************/
        $sqlHabilitarTriggers = "SET @Trigger_MOVIMIENTO_SALIDA_LINEA_UPDATE_Deshabilitado = NULL";
        $bd->ExecSQL($sqlHabilitarTriggers);
        $sqlHabilitarTriggers = "SET @Trigger_NECESIDAD_LINEA_UPDATE_Deshabilitado = NULL";
        $bd->ExecSQL($sqlHabilitarTriggers);
        /********************************** FIN HABILITO TRIGGERS IMPLICADOS **********************************/

        //MODIFICO LA LINEA PARA QUE SE EJECUTEN LOS TRIGGERS Y LAS LINEAS SE QUEDEN COMO CORRESPONDAN
        $sqlUpdate = "UPDATE MOVIMIENTO_SALIDA_LINEA SET BAJA = BAJA WHERE ID_MOVIMIENTO_SALIDA_LINEA = $rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA_LINEA";
        $bd->ExecSQL($sqlUpdate);
        $sqlUpdate = "UPDATE NECESIDAD_LINEA SET BAJA = BAJA WHERE ID_MOVIMIENTO_SALIDA_LINEA = $rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA_LINEA";
        $bd->ExecSQL($sqlUpdate);
        if ($rowLineaMovimientoSimilar != false):   //EXISTE UNA LINEA SIMILAR
            $sqlUpdate = "UPDATE NECESIDAD_LINEA SET BAJA = BAJA WHERE ID_MOVIMIENTO_SALIDA_LINEA = $rowLineaMovimientoSimilar->ID_MOVIMIENTO_SALIDA_LINEA";
            $bd->ExecSQL($sqlUpdate);
        endif;

        //DEVUELVO EL ARRAY
        return $arrDevuelto;
    }

    /**
     * @param $idMovimientoSalidaLinea LINEA A DESASIGNAR DE LA ORDEN DE PREPARACION
     * @param $idOrdenTransporte ORDEN DE TRANSPORTE OPCIONAL AL QUE LA LINEA ORIGINALMENTE PODIA ESTAR ASIGNADA
     * @return array ARRAY DEVUELTO CON LOS POSIBLES ERRORES Y EL MOVIMIENTO DE SALIDA IMPLICADO
     * FUNCION UTILIZADA PARA DESASIGNAR UNA LINEA O PARTE DE ELLA, DE UNA ORDEN DE PREPARACION
     */
    function DesasignarLineaMovimientoSalidaOrdenPreparacion($idMovimientoSalidaLinea, $cantidadDesasignar, $idOrdenTransporte = NULL)
    {
        global $bd;
        global $auxiliar;
        global $administrador;
        global $albaran;
        global $movimiento;
        global $orden_preparacion;
        global $necesidad;
        global $reserva;
        global $orden_trabajo;
        global $sap;

        //DECLARO UN ARRAY PARA DEVOLVER DATOS
        $arrDevuelto = array();

        /************************************ DESHABILITO TRIGGERS IMPLICADOS ************************************/
        $sqlDeshabilitarTriggers = "SET @Trigger_MOVIMIENTO_SALIDA_LINEA_UPDATE_Deshabilitado = 1";
        $bd->ExecSQL($sqlDeshabilitarTriggers);
        $sqlDeshabilitarTriggers = "SET @Trigger_PEDIDO_SALIDA_LINEA_UPDATE_Deshabilitado = 1";
        $bd->ExecSQL($sqlDeshabilitarTriggers);
        $sqlDeshabilitarTriggers = "SET @Trigger_NECESIDAD_LINEA_UPDATE_Deshabilitado = 1";
        $bd->ExecSQL($sqlDeshabilitarTriggers);
        /********************************** FIN DESHABILITO TRIGGERS IMPLICADOS **********************************/

        //BUSCO LA LINEA DE MOVIMIENTO DE SALIDA A ELIMINAR DE LA ORDEN DE TRANSPORTE
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowMovimientoSalidaLinea         = $bd->VerReg("MOVIMIENTO_SALIDA_LINEA", "ID_MOVIMIENTO_SALIDA_LINEA", $idMovimientoSalidaLinea, "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);
        if ($rowMovimientoSalidaLinea == false):
            $arrDevuelto['errores'] = $auxiliar->traduce("La linea a desasignar de la orden de preparacion ha variado, actualice", $administrador->ID_IDIOMA);
        endif;

        //AÑADO AL ARRAY A DEVOLVER EL MOVIMIENTO DE SALIDA MODIFICADO
        $arrDevuelto['idMovimientoSalida'] = $rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA;

        //COMPRUEBO QUE TENGA PERMISOS DE ESCRITURA EN EL ALMACEN
        if ($administrador->comprobarAlmacenPermiso($rowMovimientoSalidaLinea->ID_ALMACEN, "Escritura") == false):
            $arrDevuelto['errores'] = $auxiliar->traduce("No tiene permisos para realizar esta operacion en esta subzona", $administrador->ID_IDIOMA);
        endif;

        //COMPRUEBO QUE EL ESTADO DE LA LINEA SEA RESERVADO PARA PREPARACION
        if ($rowMovimientoSalidaLinea->ESTADO != 'Reservado para Preparacion'):
            $arrDevuelto['errores'] = $auxiliar->traduce("La linea esta en un estado incorrecto, debe estar 'Reservado para Preparacion'", $administrador->ID_IDIOMA);
        endif;

        //COMPRUEBO QUE LA LINEA NO HAYA SIDO ENVIADA A SAP
        if ($rowMovimientoSalidaLinea->ENVIADO_SAP != 0):
            $arrDevuelto['errores'] = $auxiliar->traduce("La linea ya ha sido transmitida a SAP", $administrador->ID_IDIOMA);
        endif;

        //COMPRUEBO QUE LA LINEA NO ESTE DADA DE BAJA
        if ($rowMovimientoSalidaLinea->BAJA != 0):
            $arrDevuelto['errores'] = $auxiliar->traduce("La linea esta marcada como baja", $administrador->ID_IDIOMA);
        endif;

        //COMPRUEBO QUE LA LINEA NO ESTE ANULADA
        if ($rowMovimientoSalidaLinea->LINEA_ANULADA != 0):
            $arrDevuelto['errores'] = $auxiliar->traduce("La linea esta anulada", $administrador->ID_IDIOMA);
        endif;

        //BUSCO EL MOVIMIENTO DE SALIDA
        $rowMovimientoSalida = $bd->VerReg("MOVIMIENTO_SALIDA", "ID_MOVIMIENTO_SALIDA", $rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA);

        //COMPRUEBO QUE LA CABECERA DE LA LINEA ESTE ASIGNADA A UNA ORDEN DE PREPARACION
        if ($rowMovimientoSalida->ID_ORDEN_PREPARACION == NULL):
            $arrDevuelto['errores'] = $auxiliar->traduce("La linea no pertecene a ninguna orden de preparacion", $administrador->ID_IDIOMA);
        endif;

        //BUSCO LA ORDEN DE PREPARACION
        $rowOrdenPreparacion = $bd->VerReg("ORDEN_PREPARACION", "ID_ORDEN_PREPARACION", $rowMovimientoSalida->ID_ORDEN_PREPARACION);

        //COMPRUEBO QUE LA ORDEN DE TRANSPORTE NO SEA DE TIPO OLI (ORDEN DE LOGISTICA INVERSA)
        if ($rowOrdenPreparacion->TIPO_ORDEN == 'OLI'):
            $arrDevuelto['errores'] = $auxiliar->traduce("La orden de preparacion es de logistica inversa, no se puede desasignar, debe pulsar el boton borrar de la orden de preparacion", $administrador->ID_IDIOMA);
        endif;

        //BUSCO EL PEDIDO DE SALIDA
        $rowPedidoSalida      = $bd->VerReg("PEDIDO_SALIDA", "ID_PEDIDO_SALIDA", $rowMovimientoSalidaLinea->ID_PEDIDO_SALIDA);
        $rowPedidoSalidaLinea = $bd->VerReg("PEDIDO_SALIDA_LINEA", "ID_PEDIDO_SALIDA_LINEA", $rowMovimientoSalidaLinea->ID_PEDIDO_SALIDA_LINEA);

        //SI EL PEDIDO ESTA MARCADO COMO ENTREGA TOTAL NO DEJO DESASIGNAR DE LA ORDEN DE PREPARACION
        if ($rowPedidoSalida->PREPARACION_TOTAL == 1):
            $arrDevuelto['errores'] = $auxiliar->traduce("El pedido esta marcado como 'Entrega Total', la linea no se puede desasignar", $administrador->ID_IDIOMA);
        endif;

        /***************************************** COMPROBACIONES SI LA FUNCION TRAE UNA ORDEN DE TRANSPORTE *****************************************/
        if ($idOrdenTransporte != NULL):
            //COMPRUEBO QUE LA ORDEN DE TRANSPORTE EXISTE
            $GLOBALS["NotificaErrorPorEmail"] = "No";
            $rowOrdenTransporte               = $bd->VerReg("EXPEDICION", "ID_EXPEDICION", $idOrdenTransporte, "No");
            unset($GLOBALS["NotificaErrorPorEmail"]);
            if ($rowOrdenTransporte == false):
                $arrDevuelto['errores'] = $auxiliar->traduce("La orden de transporte a la que esta asignada la linea no existe", $administrador->ID_IDIOMA);
            endif;

            //COMPRUEBO QUE LA ORDEN DE TRANSPORTE NO ESTE DADA DE BAJA
            if ($rowOrdenTransporte->BAJA == 1):
                $arrDevuelto['errores'] = $auxiliar->traduce("La orden de transporte a la que esta asignada la linea esta dada de baja", $administrador->ID_IDIOMA);
            endif;

            //COMPRUEBO QUE LA ORDEN DE TRANSPORTE ESTE EN ESTADO CREADA O EN TRANSMISION
            if (($rowOrdenTransporte->ESTADO == 'Creada') && ($rowOrdenTransporte->ESTADO == 'En Transmision')):
                $arrDevuelto['errores'] = $auxiliar->traduce("La orden de transporte a la que esta asignada la linea debe estar en estado 'Creada' o 'En Transmision'", $administrador->ID_IDIOMA);
            endif;
        endif;
        /*************************************** FIN COMPROBACIONES SI LA LINEA ESTA ASIGNADA A UNA ORDEN DE TRANSPORTE ***************************************/

        //LISTA CON LOS PEDIDOS IMPLICADOS
        $listaLineasPedidoAExpedir = "";

        //AÑADO LA LINEA DE PEDIDO A LA LISTA DE LINEAS DE PEDIDO SALIDA QUE SE ENVIARA EL BLOQUEO O DESBLOQUEO DE LINEA
        if ($listaLineasPedidoAExpedir == ""):
            $listaLineasPedidoAExpedir = $rowMovimientoSalidaLinea->ID_PEDIDO_SALIDA_LINEA;
        else:
            $listaLineasPedidoAExpedir = $listaLineasPedidoAExpedir . "," . $rowMovimientoSalidaLinea->ID_PEDIDO_SALIDA_LINEA;
        endif;

        //SI NO SE HAN PRODUCIDO ERRORES EJECUTAMOS LAS OPERACIONES CORRESPONDIENTES
        if ((!(isset($arrDevuelto['errores']))) || ($arrDevuelto['errores'] == "")):

            //BUSCO EL TIPO DE BLOQUEO PREVENTIVO
            $rowTipoBloqueoPreventivo = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "SP");

            //SI EL TIPO DE BLOQUEO DE LA LINEA ES OK O PREVENTIVO, SERA NECESARIO REVERTIR EL CAMBIO DE ESTADO REALIZADO PARA BLOQUEAR EL MATERIAL CON TIPO DE BLOQUEO CORRESPONDIENTE
            if (($rowMovimientoSalidaLinea->ID_TIPO_BLOQUEO == NULL) || ($rowMovimientoSalidaLinea->ID_TIPO_BLOQUEO == $rowTipoBloqueoPreventivo->ID_TIPO_BLOQUEO)):
                //BUSCO EL TIPO DE BLOQUEO 'Reservado para Preparacion'
                $rowTipoBloqueoReservadoParaPreparacion = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", 'RP');

                //BUSCO EL TIPO DE BLOQUEO 'Reservado para Preparacion Preventivo'
                $rowTipoBloqueoReservadoParaPreparacionPreventivo = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", 'RPP');

                //EN FUNCION DEL TIPO DE BLOQUEO A DESASIGNAR SELECCIONAMOS EL TIPO DE BLOQUEO A RESERVAR
                if ($rowMovimientoSalidaLinea->ID_TIPO_BLOQUEO == NULL):
                    $rowTipoBloqueoReservar = $rowTipoBloqueoReservadoParaPreparacion;
                elseif ($rowMovimientoSalidaLinea->ID_TIPO_BLOQUEO == $rowTipoBloqueoPreventivo->ID_TIPO_BLOQUEO):
                    $rowTipoBloqueoReservar = $rowTipoBloqueoReservadoParaPreparacionPreventivo;
                else:
                    $rowTipoBloqueoReservar = $rowTipoBloqueoReservadoParaPreparacion;
                endif;

                //BUSCO EL MATERIAL UBICACION ORIGEN
                $GLOBALS["NotificaErrorPorEmail"] = "No";
                $rowUbiOriginal                   = $bd->VerRegRest("MATERIAL_UBICACION", "ID_MATERIAL = $rowMovimientoSalidaLinea->ID_MATERIAL AND ID_UBICACION = $rowMovimientoSalidaLinea->ID_UBICACION AND ID_MATERIAL_FISICO " . ($rowMovimientoSalidaLinea->ID_MATERIAL_FISICO == NULL ? 'IS NULL' : "= $rowMovimientoSalidaLinea->ID_MATERIAL_FISICO") . " AND ID_TIPO_BLOQUEO = $rowTipoBloqueoReservar->ID_TIPO_BLOQUEO AND ID_ORDEN_TRABAJO_MOVIMIENTO " . ($rowMovimientoSalidaLinea->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? 'IS NULL' : "= $rowMovimientoSalidaLinea->ID_ORDEN_TRABAJO_MOVIMIENTO") . " AND ID_INCIDENCIA_CALIDAD " . ($rowMovimientoSalidaLinea->ID_INCIDENCIA_CALIDAD == NULL ? 'IS NULL' : "= $rowMovimientoSalidaLinea->ID_INCIDENCIA_CALIDAD"), "No");
                unset($GLOBALS["NotificaErrorPorEmail"]);

                //COMPRUEBO QUE EXISTA MATERIAL_UBICACION ORIGEN
                if ($rowUbiOriginal == false):
                    $arrDevuelto['errores'] = $auxiliar->traduce("No se ha encontrado el registro para poder revertir el cambio de estado", $administrador->ID_IDIOMA);
                endif;

                //SI LA UBICACION ORIGINAL EXISTE PODREMOS REALIZAR ACCIONES
                if ($rowUbiOriginal != false):
                    //OBTENGO LA CANTIDAD A CAMBIAR DE ESTADO
                    $cantidadCambiarEstado = $cantidadDesasignar;

                    //SI LA CANTIDAD A CAMBIAR DE ESTADO ES MAYOR QUE CERO CONTINUO
                    if ($cantidadCambiarEstado > 0):
                        //BUSCO LAS RESERVAS LINEA DE LA DEMANDA
                        $resultLineasReservaEspecificas = false;
                        $resultLineasReserva            = $reserva->get_reservas_linea_pedido($rowMovimientoSalidaLinea->ID_PEDIDO_SALIDA_LINEA, 'Finalizada');

                        //BUSCO LAS RESERVAS LINEA ESPECIFICAS DE LA DEMANDA
                        if (($resultLineasReserva != false) && ($resultLineasReserva != NULL) && ($bd->NumRegs($resultLineasReserva) > 0)):
                            $resultLineasReservaEspecificas = $reserva->get_reservas_lineas_especificas($resultLineasReserva, $rowMovimientoSalidaLinea->ID_MATERIAL, $rowMovimientoSalidaLinea->ID_UBICACION, $rowMovimientoSalidaLinea->ID_TIPO_BLOQUEO, $rowMovimientoSalidaLinea->ID_MATERIAL_FISICO, $rowMovimientoSalidaLinea->ID_ORDEN_TRABAJO_MOVIMIENTO, $rowMovimientoSalidaLinea->ID_INCIDENCIA_CALIDAD);

                        else:
                            //SI NO HAY DEMANDA POR SER PREVIO A LA SUBIDA A PRODUCCION
                            //ACTUALIZAMOS LAS DEMANDAS DE LA LINEA DE PEDIDO. SI ESTA PARTE YA PREPARADO, EL PROCESO DE COLAS PASARA DE COLA A RESERVA FINALIZADA
                            $arr_demanda = $reserva->actualizar_demandas_pedido($rowMovimientoSalidaLinea->ID_PEDIDO_SALIDA, $rowMovimientoSalidaLinea->ID_PEDIDO_SALIDA_LINEA);

                            //ACCIONES EN FUNCION DEL RESULTADO
                            if ((isset($arr_demanda['error'])) && ($arr_demanda['error'] != "")):
                                $arrDevuelto['errores'] = $arr_demanda['error'];
                            endif;
                        endif;

                        //SI EXISTEN LINEAS DE RESERVA VALIDAS
                        if ($resultLineasReservaEspecificas != false):
                            //BUSCO EL TIPO DE BLOQUEO PREVENTIVO, MATERIAL USADO PARA PREPARAR ESTE TIPO DE PEDIDOS DE TRASLADO DE PENDIENTES
                            $rowTipoBloqueoPlanificado = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "SP");

                            //BLOQUEOS RESERVADO
                            $rowTipoBloqueoReservado            = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "RV");
                            $rowTipoBloqueoReservadoPlanificado = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "RVP");

                            $idTipoBloqueoFinal = ($rowMovimientoSalidaLinea->ID_TIPO_BLOQUEO == NULL ? $rowTipoBloqueoReservado->ID_TIPO_BLOQUEO : ($rowMovimientoSalidaLinea->ID_TIPO_BLOQUEO == $rowTipoBloqueoPlanificado->ID_TIPO_BLOQUEO ? $rowTipoBloqueoReservadoPlanificado->ID_TIPO_BLOQUEO : $rowMovimientoSalidaLinea->ID_TIPO_BLOQUEO));
                        else:
                            $idTipoBloqueoFinal = $rowMovimientoSalidaLinea->ID_TIPO_BLOQUEO;
                        endif;

                        //DESBLOQUEO EL MATERIAL CORRESPONDIENTE MEDIANTE UN CAMBIO DE ESTADO
                        $sqlInsert = "INSERT INTO CAMBIO_ESTADO SET
                                        FECHA = '" . date("Y-m-d H:i:s") . "'
                                        , TIPO_CAMBIO_ESTADO = '" . ($rowMovimientoSalidaLinea->ID_TIPO_BLOQUEO == $rowTipoBloqueoPreventivo->ID_TIPO_BLOQUEO ? "AnulacionReservadoParaPreparacionPreventivo" : "AnulacionReservadoParaPreparacion") . "'
                                        , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                        , ID_MATERIAL = $rowMovimientoSalidaLinea->ID_MATERIAL
                                        , ID_MATERIAL_FISICO = " . ($rowMovimientoSalidaLinea->ID_MATERIAL_FISICO == NULL ? 'NULL' : $rowMovimientoSalidaLinea->ID_MATERIAL_FISICO) . "
                                        , ID_UBICACION = $rowMovimientoSalidaLinea->ID_UBICACION
                                        , CANTIDAD = $cantidadCambiarEstado
                                        , ID_TIPO_BLOQUEO_INICIAL = $rowTipoBloqueoReservar->ID_TIPO_BLOQUEO
                                        , ID_TIPO_BLOQUEO_FINAL = " . ($idTipoBloqueoFinal == NULL ? 'NULL' : $idTipoBloqueoFinal) . "
                                        , ID_ORDEN_TRABAJO_MOVIMIENTO = " . ($rowMovimientoSalidaLinea->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? 'NULL' : $rowMovimientoSalidaLinea->ID_ORDEN_TRABAJO_MOVIMIENTO) . "
                                        , ID_INCIDENCIA_CALIDAD = " . ($rowMovimientoSalidaLinea->ID_INCIDENCIA_CALIDAD == NULL ? 'NULL' : $rowMovimientoSalidaLinea->ID_INCIDENCIA_CALIDAD);
                        $bd->ExecSQL($sqlInsert);//exit($sqlInsert);
                        $idCambioEstado = $bd->IdAsignado();

                        //BUSCO MATERIAL UBICACION DONDE SE BLOQUEO EL MATERIAL PARA PREPARAR
                        $GLOBALS["NotificaErrorPorEmail"] = "No";
                        $rowMatUbi                        = $bd->VerRegRest("MATERIAL_UBICACION", "ID_MATERIAL = $rowMovimientoSalidaLinea->ID_MATERIAL AND ID_UBICACION = $rowMovimientoSalidaLinea->ID_UBICACION AND ID_MATERIAL_FISICO " . ($rowMovimientoSalidaLinea->ID_MATERIAL_FISICO == NULL ? 'IS NULL' : "= $rowMovimientoSalidaLinea->ID_MATERIAL_FISICO") . " AND ID_TIPO_BLOQUEO = $rowTipoBloqueoReservar->ID_TIPO_BLOQUEO AND ID_ORDEN_TRABAJO_MOVIMIENTO " . ($rowMovimientoSalidaLinea->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? 'IS NULL' : "= $rowMovimientoSalidaLinea->ID_ORDEN_TRABAJO_MOVIMIENTO") . " AND ID_INCIDENCIA_CALIDAD " . ($rowMovimientoSalidaLinea->ID_INCIDENCIA_CALIDAD == NULL ? 'IS NULL' : "= $rowMovimientoSalidaLinea->ID_INCIDENCIA_CALIDAD"), "No");
                        unset($GLOBALS["NotificaErrorPorEmail"]);

                        //COMPRUEBO QUE EXISTA MATERIAL_UBICACION ORIGEN
                        if ($rowMatUbi == false):
                            $arrDevuelto['errores'] = $auxiliar->traduce("No existe la dupla material-ubicacion donde descontar el material", $administrador->ID_IDIOMA);
                        endif;

                        //COMPRUEBO QUE EN LA UBICACION ORIGEN (UBICACION DE DONDE SE DESUBICA) HAYA SUFICIENTE STOCK
                        if ($rowMatUbi->STOCK_TOTAL < $cantidadCambiarEstado):
                            $arrDevuelto['errores'] = $auxiliar->traduce("No hay stock suficiente para reubicar y desasignar la linea", $administrador->ID_IDIOMA);
                        endif;

                        //SI NO SE HAN PRODUCIDO ERRORES EJECUTAMOS LAS OPERACIONES CORRESPONDIENTES
                        if ((!(isset($arrDevuelto['errores']))) || ($arrDevuelto['errores'] == "")):
                            //DECREMENTO MATERIAL_UBICACION ORIGEN
                            $sqlUpdate = "UPDATE MATERIAL_UBICACION SET
                                            STOCK_TOTAL = STOCK_TOTAL - $cantidadCambiarEstado
                                            , STOCK_BLOQUEADO = STOCK_BLOQUEADO - $cantidadCambiarEstado
                                            WHERE ID_MATERIAL_UBICACION = $rowMatUbi->ID_MATERIAL_UBICACION";
                            $bd->ExecSQL($sqlUpdate);
                        endif;

                        //BUSCO MATERIAL_UBICACION DESTINO
                        $GLOBALS["NotificaErrorPorEmail"] = "No";
                        $clausulaWhere                    = "ID_MATERIAL = $rowMovimientoSalidaLinea->ID_MATERIAL AND ID_UBICACION = $rowMovimientoSalidaLinea->ID_UBICACION AND ID_MATERIAL_FISICO " . ($rowMovimientoSalidaLinea->ID_MATERIAL_FISICO == NULL ? 'IS NULL' : "= $rowMovimientoSalidaLinea->ID_MATERIAL_FISICO") . " AND ID_TIPO_BLOQUEO " . ($idTipoBloqueoFinal == NULL ? 'IS NULL' : "= $idTipoBloqueoFinal") . " AND ID_ORDEN_TRABAJO_MOVIMIENTO " . ($rowMovimientoSalidaLinea->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? 'IS NULL' : "= $rowMovimientoSalidaLinea->ID_ORDEN_TRABAJO_MOVIMIENTO") . " AND ID_INCIDENCIA_CALIDAD " . ($rowMovimientoSalidaLinea->ID_INCIDENCIA_CALIDAD == NULL ? 'IS NULL' : "= $rowMovimientoSalidaLinea->ID_INCIDENCIA_CALIDAD");
                        $rowMatUbiDestino                 = $bd->VerRegRest("MATERIAL_UBICACION", $clausulaWhere, "No");
                        unset($GLOBALS["NotificaErrorPorEmail"]);
                        if ($rowMatUbiDestino == false):
                            //CREO MATERIAL UBICACION DESTINO
                            $sqlInsert = "INSERT INTO MATERIAL_UBICACION SET
                                            ID_MATERIAL = $rowMovimientoSalidaLinea->ID_MATERIAL
                                            , ID_UBICACION = $rowMovimientoSalidaLinea->ID_UBICACION
                                            , ID_MATERIAL_FISICO = " . ($rowMovimientoSalidaLinea->ID_MATERIAL_FISICO == NULL ? 'NULL' : $rowMovimientoSalidaLinea->ID_MATERIAL_FISICO) . "
                                            , ID_TIPO_BLOQUEO = " . ($idTipoBloqueoFinal == NULL ? 'NULL' : $idTipoBloqueoFinal) . "
                                            , ID_ORDEN_TRABAJO_MOVIMIENTO = " . ($rowMovimientoSalidaLinea->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? 'NULL' : $rowMovimientoSalidaLinea->ID_ORDEN_TRABAJO_MOVIMIENTO) . "
                                            , ID_INCIDENCIA_CALIDAD = " . ($rowMovimientoSalidaLinea->ID_INCIDENCIA_CALIDAD == NULL ? 'NULL' : $rowMovimientoSalidaLinea->ID_INCIDENCIA_CALIDAD);
                            $bd->ExecSQL($sqlInsert);
                            $idMatUbiDestino = $bd->IdAsignado();
                        else:
                            $idMatUbiDestino = $rowMatUbiDestino->ID_MATERIAL_UBICACION;
                        endif;

                        //INCREMENTO MATERIAL_UBICACION DESTINO
                        $sqlUpdate = "UPDATE MATERIAL_UBICACION SET
                                        STOCK_TOTAL = STOCK_TOTAL + $cantidadCambiarEstado
                                        , STOCK_OK = STOCK_OK + " . ($idTipoBloqueoFinal == NULL ? $cantidadCambiarEstado : 0) . "
                                        , STOCK_BLOQUEADO = STOCK_BLOQUEADO + " . ($idTipoBloqueoFinal == NULL ? 0 : $cantidadCambiarEstado) . "
                                        WHERE ID_MATERIAL_UBICACION = $idMatUbiDestino";
                        $bd->ExecSQL($sqlUpdate);


                        //SI EXISTEN LINEAS DE RESERVA VALIDAS
                        if ($resultLineasReservaEspecificas != false):
                            //VARIABLE PARA SABER LA CANTIDAD PENDIENTE
                            $cantidadPendiente = $rowMovimientoSalidaLinea->CANTIDAD;

                            //RECORREMOS EL RESULT DE LINEAS DE RESERVA
                            while (($rowReservaLinea = $bd->SigReg($resultLineasReservaEspecificas)) && ($cantidadPendiente > EPSILON_SISTEMA)):
                                //CALCULO LA CANTIDAD A CAMBIAR DE UBICACION
                                $cantidadModificar = min($cantidadPendiente, $rowReservaLinea->CANTIDAD);

                                //REACTIVO LAS RESERVAS CORRESPONDIENTES
                                $arrDevueltoReservas = $reserva->reactivar_reserva_linea($rowReservaLinea->ID_RESERVA_LINEA, $cantidadModificar);

                                //ACCIONES EN FUNCION DEL RESULTADO
                                if ((isset($arrDevueltoReservas['error'])) && ($arrDevueltoReservas['error'] != "")):
                                    $arrDevuelto['errores'] = $arrDevueltoReservas['error'];
                                else:
                                    //ACTUALIZO LA CANTIDAD PENDIENTE
                                    $cantidadPendiente = $cantidadPendiente - $cantidadModificar;
                                endif;
                            endwhile;
                            //FIN RECORREMOS EL RESULT DE LINEAS DE RESERVA
                        endif;
                        //FIN SI EXISTEN LINEAS DE RESERVA VALIDAS

                        //ACTUALIZAMOS ESTADO DEMANDA SI ES NECESARIO(se hace dentro de reactivar_reserva_linea)
                    endif;
                    //FIN SI EXISTE LA DEMANDA
                endif;
            endif;
            //FIN SI EL TIPO DE BLOQUEO DE LA LINEA ES OK O PREVENTIVO, SERA NECESARIO REVERTIR EL CAMBIO DE ESTADO REALIZADO PARA BLOQUEAR EL MATERIAL CON TIPO DE BLOQUEO CORRESPONDIENTE

            //DECREMENTO LA CANTIDAD PEDIDO DE LAS LINEAS DE MOVIMIENTO SALIDA QUE COINCIDAN LA LINEA DEL PEDIDO Y EL MOVIMIENTO DE SALIDA
            $sqlUpdate = "UPDATE MOVIMIENTO_SALIDA_LINEA SET
                            CANTIDAD_PEDIDO = CANTIDAD_PEDIDO - " . $cantidadDesasignar . "
                            WHERE ID_MOVIMIENTO_SALIDA = $rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA AND ID_PEDIDO_SALIDA_LINEA = $rowMovimientoSalidaLinea->ID_PEDIDO_SALIDA_LINEA AND LINEA_ANULADA = 0 AND BAJA = 0";
            $bd->ExecSQL($sqlUpdate);

            //INCREMENTO LA CANTIDAD A LOS PEDIDOS CORRESPONDIENTES
            $sqlUpdate = "UPDATE PEDIDO_SALIDA_LINEA SET
                            CANTIDAD_PENDIENTE_SERVIR = CANTIDAD_PENDIENTE_SERVIR + " . $cantidadDesasignar . "
                            WHERE ID_PEDIDO_SALIDA_LINEA = $rowMovimientoSalidaLinea->ID_PEDIDO_SALIDA_LINEA";
            $bd->ExecSQL($sqlUpdate);

            //ACTULIZO EL ESTADO DEL PEDIDO DE SALIDA SI SE PUEDE
            $numLineasPedido                    = $bd->NumRegsTabla("PEDIDO_SALIDA_LINEA", "ID_PEDIDO_SALIDA = $rowMovimientoSalidaLinea->ID_PEDIDO_SALIDA AND BAJA = 0");
            $numLineasPedidoSinEmpezarAPreparar = $bd->NumRegsTabla("PEDIDO_SALIDA_LINEA", "ID_PEDIDO_SALIDA = $rowMovimientoSalidaLinea->ID_PEDIDO_SALIDA AND CANTIDAD_PENDIENTE_SERVIR = CANTIDAD AND BAJA = 0");
            if ($numLineasPedido == $numLineasPedidoSinEmpezarAPreparar):
                $sqlUpdate = "UPDATE PEDIDO_SALIDA SET
                                ESTADO = 'Grabado'
                                WHERE ID_PEDIDO_SALIDA = $rowMovimientoSalidaLinea->ID_PEDIDO_SALIDA";
            else:
                $sqlUpdate = "UPDATE PEDIDO_SALIDA SET
                                ESTADO = 'En Entrega'
                                WHERE ID_PEDIDO_SALIDA = $rowMovimientoSalidaLinea->ID_PEDIDO_SALIDA";
            endif;
            $bd->ExecSQL($sqlUpdate);
            //FIN ACTULIZO EL ESTADO DEL PEDIDO DE SALIDA SI SE PUEDE

            //SI LA LINEA TIENE OTL, LA MARCO PARA VOLVER A CALCULAR LA FECHA PLANIFICADA
            if ($rowPedidoSalidaLinea->ID_ORDEN_TRABAJO_LINEA != NULL):
                //BUSCAMOS LA ULTIMA LLAMADA A LA T06, Y SI PREPARACION O POSTERIOR, LO VOLVEREMOS A CALCULAR
                $GLOBALS["NotificaErrorPorEmail"]                   = "No";
                $rowOrdenTrabajoLineaDisponibilidadFechaPlanificada = $bd->VerRegRest("ORDEN_TRABAJO_LINEA_DISPONIBILIDAD_FECHA_PLANIFICADA", "ID_ORDEN_TRABAJO_LINEA =  $rowPedidoSalidaLinea->ID_ORDEN_TRABAJO_LINEA AND BAJA = 0", "No");
                if (($rowOrdenTrabajoLineaDisponibilidadFechaPlanificada == false) ||
                    (($rowOrdenTrabajoLineaDisponibilidadFechaPlanificada != false) && ($rowOrdenTrabajoLineaDisponibilidadFechaPlanificada->ESCENARIO_PLANIFICACION == 'Preparacion' || $rowOrdenTrabajoLineaDisponibilidadFechaPlanificada->ESCENARIO_PLANIFICACION == 'Expedicion'))
                ):

                    if ($orden_trabajo->esTransmitibleInterfaz("Preparacion", "Anulacion Preparacion", "T06", $rowPedidoSalidaLinea->ID_ORDEN_TRABAJO_LINEA)):
                        //MARCAMOS LA LINEA PARA VOLVER A CALCULAR
                        $sqlUpdate = "UPDATE ORDEN_TRABAJO_LINEA SET
                                      DISPONIBILIDAD_FECHA_PLANIFICADA = 'Pendiente Tratar'
                                      WHERE ID_ORDEN_TRABAJO_LINEA = " . $rowPedidoSalidaLinea->ID_ORDEN_TRABAJO_LINEA;
                        $bd->ExecSQL($sqlUpdate);
                    endif;
                endif;

                if ($orden_trabajo->esTransmitibleInterfaz("Preparacion", "Anulacion Preparacion", "T01", $rowPedidoSalidaLinea->ID_ORDEN_TRABAJO_LINEA)):
                    //INICIO UNA TRANSACCION PARA LA INTERFAZ DEL SEMAFORO PARA INFORMAR A SAP DEL BLOQUEO Y CANTIDAD RECEPCIONADA
                    $bd->begin_transaction();


                    //COMPRUEBO SI TIENE INCIDENCIA DE SISTEMA CREADA
                    $num = $bd->NumRegsTabla("INCIDENCIA_SISTEMA", "TIPO = '" . INCIDENCIA_TIPO_INTERFAZ_SIN_TRANSMITIR . "' AND SUBTIPO = '" . INCIDENCIA_SUBTIPO_ORDENES_TRABAJO_SEMAFORO . "' AND ID_OBJETO = $rowPedidoSalidaLinea->ID_ORDEN_TRABAJO_LINEA AND TABLA_OBJETO = 'ORDEN_TRABAJO_LINEA' AND ESTADO <> 'Finalizada'");
                    if ($num == 0): //SI NO TIENE INCIDENCIA SISTEMA CREADA REALIZO LA LLAMADA A SAP


                        $resultado = $sap->OrdenesTrabajoSemaforo($rowPedidoSalidaLinea->ID_ORDEN_TRABAJO_LINEA);
                        if ($resultado['RESULTADO'] != 'OK'): //No se ha podido grabar la información en SAP
                            $strError .= $auxiliar->traduce("Se han producido errores al transmitir la interfaz del semaforo de ordenes de trabajo a SAP", $administrador->ID_IDIOMA) . ". " . $datosLinea;
                            $strError .= "<br>";
                            //NO INFORMO DE LOS ERRORES PORQUE ES UNA INTERFAZ ASINCRONA DONDE NO ENVIAN ERRORES SI SE PRODUCEN


                            //DESHAGO LA TRANSACCION
                            $bd->rollback_transaction();


                            //GRABO EL ERROR PRODUCIDO EN BASE DE DATOS
                            $sap->InsertarErrores($resultado);

                            //SI FALLA LA TRANSMISION DEL SEMAFORO Y NO ESTA YA COMO PENDIENTE, LO GRABO PARA SABER QUE ESTA PENDIENTE DE REALIZARSE
                            $num = $bd->NumRegsTabla("INTERFACES_PENDIENTES_TRANSMITIR", "INTERFAZ_PENDIENTE_TRANSMITIR = 'ordenes_trabajo_semaforo' AND ID_OBJETO = $rowPedidoSalidaLinea->ID_ORDEN_TRABAJO_LINEA AND TABLA_OBJETO = 'ORDEN_TRABAJO_LINEA' AND BAJA = 0");
                            if ($num == 0):
                                $sqlInsert = "INSERT INTO INTERFACES_PENDIENTES_TRANSMITIR SET
                                                      INTERFAZ_PENDIENTE_TRANSMITIR = 'ordenes_trabajo_semaforo'
                                                      , ID_OBJETO = $rowPedidoSalidaLinea->ID_ORDEN_TRABAJO_LINEA
                                                      , TABLA_OBJETO = 'ORDEN_TRABAJO_LINEA'
                                                      , NUMERO_LLAMADAS_INTERFAZ = '0'
                                                      , BAJA = 0";
                                $bd->ExecSQL($sqlInsert);
                            endif;


                            //ACTUALIZO EL ESTADO TRATADA DE LA LINEA DE LA ORDEN DE TRABAJO
                            $orden_trabajo->ActualizarLinea_Estados($rowPedidoSalidaLinea->ID_ORDEN_TRABAJO_LINEA);
                        endif;
                    endif;

                    //FINALIZO LA TRANSACCION PARA LA INTERFAZ DEL SEMAFORO PARA INFORMAR A SAP DEL BLOQUEO Y CANTIDAD RECEPCIONADA
                    $bd->commit_transaction();

                endif;


                if ($orden_trabajo->esTransmitibleInterfaz("Preparacion", "Anulacion Preparacion", "T02", $rowPedidoSalidaLinea->ID_ORDEN_TRABAJO_LINEA)):

                    //INICIO UNA TRANSACCION PARA LA INTERFAZ DE LA RESERVA DE MATERIAL PARA INFORMAR A SO99 DEL BLOQUEO Y CANTIDAD PEDIDA
                    $bd->begin_transaction();

                    //COMPRUEBO SI TIENE INCIDENCIA DE SISTEMA CREADA
                    $num = $bd->NumRegsTabla("INCIDENCIA_SISTEMA", "TIPO = '" . INCIDENCIA_TIPO_INTERFAZ_SIN_TRANSMITIR . "' AND SUBTIPO = '" . INCIDENCIA_SUBTIPO_ORDENES_TRABAJO_RESERVA . "' AND ID_OBJETO = $rowPedidoSalidaLinea->ID_ORDEN_TRABAJO_LINEA AND TABLA_OBJETO = 'ORDEN_TRABAJO_LINEA' AND ESTADO <> 'Finalizada'");
                    if ($num == 0): //SI NO TIENE INCIDENCIA SISTEMA CREADA REALIZO LA LLAMADA A SAP

                        $resultado = $sap->OrdenesTrabajoReserva($rowPedidoSalidaLinea->ID_ORDEN_TRABAJO_LINEA);
                        if ($resultado['RESULTADO'] != 'OK'): //No se ha podido grabar la información en SAP
                            $strError .= $auxiliar->traduce("Se han producido errores al transmitir la interfaz de la reserva de material de ordenes de trabajo a SO99", $administrador->ID_IDIOMA) . ". " . $datosLinea;
                            foreach ($resultado['ERRORES'] as $arr):
                                foreach ($arr as $mensaje_error):
                                    $strError = $strError . $mensaje_error . "<br>";
                                endforeach;
                            endforeach;


                            //DESHAGO LA TRANSACCION
                            $bd->rollback_transaction();


                            //GRABO EL ERROR PRODUCIDO EN BASE DE DATOS
                            $sap->InsertarErrores($resultado);

                            //SI FALLA LA TRANSMISION DEL SEMAFORO Y NO ESTA YA COMO PENDIENTE, LO GRABO PARA SABER QUE ESTA PENDIENTE DE REALIZARSE
                            $num = $bd->NumRegsTabla("INTERFACES_PENDIENTES_TRANSMITIR", "INTERFAZ_PENDIENTE_TRANSMITIR = 'ordenes_trabajo_reserva' AND ID_OBJETO = $rowPedidoSalidaLinea->ID_ORDEN_TRABAJO_LINEA AND TABLA_OBJETO = 'ORDEN_TRABAJO_LINEA' AND BAJA = 0");
                            if ($num == 0):
                                $sqlInsert = "INSERT INTO INTERFACES_PENDIENTES_TRANSMITIR SET
                                                      INTERFAZ_PENDIENTE_TRANSMITIR = 'ordenes_trabajo_reserva'
                                                      , ID_OBJETO = $rowPedidoSalidaLinea->ID_ORDEN_TRABAJO_LINEA
                                                      , TABLA_OBJETO = 'ORDEN_TRABAJO_LINEA'
                                                      , NUMERO_LLAMADAS_INTERFAZ = '0'
                                                      , BAJA = 0";
                                $bd->ExecSQL($sqlInsert);
                            endif;
                        endif;
                    endif;
                    //FINALIZO UNA TRANSACCION PARA LA INTERFAZ DE LA RESERVA DE MATERIAL PARA INFORMAR A SO99 DEL BLOQUEO Y CANTIDAD PEDIDA
                    $bd->commit_transaction();
                endif;
            endif;

            //DOY ESTA LINEA DE BAJA SI LA CANTIDAD A DESASIGNAR COINCIDE CON LA CANTIDAD DE LA LINEA, SINO SOLO DECREMENTO LA CANTIDAD
            if (abs( (float)$cantidadDesasignar - $rowMovimientoSalidaLinea->CANTIDAD) < EPSILON_SISTEMA):
//            if ((float)(string)$cantidadDesasignar == (float)(string)$rowMovimientoSalidaLinea->CANTIDAD):
                $sqlUpdate = "UPDATE MOVIMIENTO_SALIDA_LINEA SET
                                BAJA = 1
                                  , ID_CONTENEDOR = NULL
                                  , ID_CONTENEDOR_LINEA = NULL
                                  , ID_BULTO = NULL
                                  , ID_BULTO_LINEA = NULL
                                , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                WHERE ID_MOVIMIENTO_SALIDA_LINEA = $rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA_LINEA";
                $bd->ExecSQL($sqlUpdate);
            else:
                $sqlUpdate = "UPDATE MOVIMIENTO_SALIDA_LINEA SET
                            CANTIDAD = CANTIDAD - " . $cantidadDesasignar . "
                            , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                            WHERE ID_MOVIMIENTO_SALIDA_LINEA = $rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA_LINEA";
                $bd->ExecSQL($sqlUpdate);
            endif;

            //DESASOCIO LAS POSIBLES LINEAS DE NECESIDAD ASOCIADAS
            $necesidad->DesasociarMovimientoSalidaLineaCancelada($rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA_LINEA);

            //COMPRUEBO SI HAY QUE MARCAR EL MOVIMIENTO COMO PREPARADO (SI TIENE TODAS LAS LINEAS PREPARADAS, LE DAMOS LA FECHA DE LA ULTIMA PREPARADA
            $numLineasMovimientoNoPreparadas = $bd->NumRegsTabla("MOVIMIENTO_SALIDA_LINEA", "ID_MOVIMIENTO_SALIDA = $rowMovimientoSalida->ID_MOVIMIENTO_SALIDA AND LINEA_ANULADA = 0 AND BAJA = 0 AND FECHA_PREPARACION = '0000-00-00 00:00:00'");
            if ($numLineasMovimientoNoPreparadas == 0):
                $sqlUltimaLineasPreparada    = "SELECT *
                                             FROM MOVIMIENTO_SALIDA_LINEA
                                             WHERE ID_MOVIMIENTO_SALIDA = $rowMovimientoSalida->ID_MOVIMIENTO_SALIDA AND LINEA_ANULADA = 0 AND BAJA = 0 AND FECHA_PREPARACION <> '0000-00-00 00:00:00' ORDER BY FECHA_PREPARACION DESC LIMIT 1";
                $resultUltimaLineasPreparada = $bd->ExecSQL($sqlUltimaLineasPreparada);
                while ($rowUltimaLineasPreparada = $bd->SigReg($resultUltimaLineasPreparada)):
                    $sqlUpdate = "UPDATE MOVIMIENTO_SALIDA SET
                                    FECHA_PREPARACION = '" . $rowUltimaLineasPreparada->FECHA_PREPARACION . "'
                                    WHERE ID_MOVIMIENTO_SALIDA = $rowMovimientoSalida->ID_MOVIMIENTO_SALIDA";
                    $bd->ExecSQL($sqlUpdate);
                endwhile;
            else:
                $sqlUpdate = "UPDATE MOVIMIENTO_SALIDA SET
                                FECHA_PREPARACION = '0000-00-00 00:00:00'
                                WHERE ID_MOVIMIENTO_SALIDA = $rowMovimientoSalida->ID_MOVIMIENTO_SALIDA";
                $bd->ExecSQL($sqlUpdate);
            endif;

            //COMPRUEBO SI HAY QUE DAR DE BAJA EL MOVIMIENTO TAMBIEN
            $numLineasMovimiento = $bd->NumRegsTabla("MOVIMIENTO_SALIDA_LINEA", "ID_MOVIMIENTO_SALIDA = $rowMovimientoSalida->ID_MOVIMIENTO_SALIDA AND LINEA_ANULADA = 0 AND BAJA = 0");
            if ($numLineasMovimiento == 0):
                $sqlUpdate = "UPDATE MOVIMIENTO_SALIDA SET
                                BAJA = 1
                                , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                WHERE ID_MOVIMIENTO_SALIDA = $rowMovimientoSalida->ID_MOVIMIENTO_SALIDA";
                $bd->ExecSQL($sqlUpdate);

                //AÑADO AL ARRAY EL MOVIMIENTO DE SALIDA SI LO DAMOS DE BAJA
                $arrDevuelto['idMovimientoSalidaMarcadoBaja'] = $rowMovimientoSalida->ID_MOVIMIENTO_SALIDA;
            endif;

            //ACTUALIZO EL ESTADO DE LA ORDEN DE PREPARACION
            //$orden_preparacion->ActualizarEstadoOrdenPreparacion($rowOrdenPreparacion->ID_ORDEN_PREPARACION);
            //AÑADO AL ARRAY A DEVOLVER LA ORDEN DE PREPARACION
            $arrDevuelto['idOrdenPreparacion'] = $rowOrdenPreparacion->ID_ORDEN_PREPARACION;


            //BUSCO LOS MOVIMIENTOS DE SALIDA PARA ACTUALIZAR SU ESTADO
            $sqlMovimientosSalida    = "SELECT DISTINCT(MSL.ID_MOVIMIENTO_SALIDA)
                                        FROM MOVIMIENTO_SALIDA MS
                                        INNER JOIN MOVIMIENTO_SALIDA_LINEA MSL ON MSL.ID_MOVIMIENTO_SALIDA = MS.ID_MOVIMIENTO_SALIDA
                                        WHERE MS.ID_ORDEN_PREPARACION = $rowOrdenPreparacion->ID_ORDEN_PREPARACION AND MS.BAJA = 0 AND MSL.LINEA_ANULADA = 0 AND MSL.BAJA = 0";
            $resultMovimientosSalida = $bd->ExecSQL($sqlMovimientosSalida);
            while ($rowMovimientosSalida = $bd->SigReg($resultMovimientosSalida)):
                //ACTUALIZO EL ESTADO DEL MOVIMIENTO DE SALIDA
                //$movimiento->actualizarEstadoMovimientoSalida($rowMovimientosSalida->ID_MOVIMIENTO_SALIDA);
            endwhile;

            //ACCIONES SI LA LINEA ORIGINALMENTE ESTABA ASIGNADA A UNA ORDEN DE TRANSPORTE
            if ($rowMovimientoSalidaLinea->ID_EXPEDICION != NULL):
                //ACTUALIZAR VERSION/BULTOS DE LA ORDEN DE TRANSPORTE
                //$this->actualizar_version_bultos_orden_transporte($rowOrdenTransporte->ID_EXPEDICION);
                //AÑADO AL ARRAY A DEVOLVER LA ORDEN DE RECOGIDA
                $arrDevuelto['idExpedicion'] = $rowOrdenTransporte->ID_EXPEDICION;
            endif;

            //SI SE RECIBE ORDEN DE RECOGIDA EN LA FUNCION LA ACTUALIZO, SE PIERDE EL VALOR AL REUBICAR LA LINEA
            if ($idOrdenTransporte != NULL):
                //ACTUALIZO EL ESTADO DE LA ORDEN DE RECOGIDA
                $this->actualizar_estado_orden_transporte($idOrdenTransporte);
            endif;
        endif;
        //FIN SI NO SE HAN PRODUCIDO ERRORES EJECUTAMOS LAS OPERACIONES CORRESPONDIENTES

        //AÑADO LA LISTA DE LINEAS DE PEDIDOS MODIFICADAS
        $arrDevuelto['lista_lineas_pedido'] = $listaLineasPedidoAExpedir;

        /************************************ HABILITO TRIGGERS IMPLICADOS ************************************/
        $sqlHabilitarTriggers = "SET @Trigger_MOVIMIENTO_SALIDA_LINEA_UPDATE_Deshabilitado = NULL";
        $bd->ExecSQL($sqlHabilitarTriggers);
        $sqlHabilitarTriggers = "SET @Trigger_PEDIDO_SALIDA_LINEA_UPDATE_Deshabilitado = NULL";
        $bd->ExecSQL($sqlHabilitarTriggers);
        $sqlHabilitarTriggers = "SET @Trigger_NECESIDAD_LINEA_UPDATE_Deshabilitado = NULL";
        $bd->ExecSQL($sqlHabilitarTriggers);
        /********************************** FIN HABILITO TRIGGERS IMPLICADOS **********************************/

        //MODIFICO LA LINEA PARA QUE SE EJECUTEN LOS TRIGGERS Y LAS LINEAS SE QUEDEN COMO CORRESPONDAN
        $sqlUpdate = "UPDATE MOVIMIENTO_SALIDA_LINEA SET BAJA = BAJA WHERE ID_MOVIMIENTO_SALIDA_LINEA = $rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA_LINEA";
        $bd->ExecSQL($sqlUpdate);
        $sqlUpdate = "UPDATE NECESIDAD_LINEA SET BAJA = BAJA WHERE ID_MOVIMIENTO_SALIDA_LINEA = $rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA_LINEA";
        $bd->ExecSQL($sqlUpdate);

        //DEVUELVO EL ARRAY
        return $arrDevuelto;
    }

    /**
     * @param $idMovimientoSalidaLinea ID_MOVIMIENTO_SALIDA A ELIMINAR DE UNA ORDEN DE RECOGIDA
     * @param int $eliminarBulto
     * @return array
     */
    function ReubicarDesasignarMaterialLineaMovimientoSalidaLogisticaInversa($idMovimientoSalidaLinea, $eliminarBulto = 0)
    {
        global $bd;
        global $auxiliar;
        global $administrador;
        global $albaran;
        global $movimiento;
        global $orden_preparacion;
        global $necesidad;
        global $stock_compartido;
        global $orden_transporte;

        //DECLARO UN ARRAY PARA DEVOLVER DATOS
        $arrDevuelto = array();

        /************************************ DESHABILITO TRIGGERS IMPLICADOS ************************************/
        $sqlDeshabilitarTriggers = "SET @Trigger_MOVIMIENTO_SALIDA_LINEA_UPDATE_Deshabilitado = 1";
        $bd->ExecSQL($sqlDeshabilitarTriggers);
        $sqlDeshabilitarTriggers = "SET @Trigger_PEDIDO_SALIDA_LINEA_UPDATE_Deshabilitado = 1";
        $bd->ExecSQL($sqlDeshabilitarTriggers);
        /********************************** FIN DESHABILITO TRIGGERS IMPLICADOS **********************************/

        //BUSCO LA LINEA DE MOVIMIENTO DE SALIDA A ELIMINAR
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowMovimientoSalidaLinea         = $bd->VerReg("MOVIMIENTO_SALIDA_LINEA", "ID_MOVIMIENTO_SALIDA_LINEA", $idMovimientoSalidaLinea, "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);
        if ($rowMovimientoSalidaLinea == false):
            $arrDevuelto['errores'] = $auxiliar->traduce("La linea para reubicar ha variado, actualice", $administrador->ID_IDIOMA);
        endif;

        //BUSCO EL PEDIDO DE SALIDA
        $rowPedidoSalida = $bd->VerReg("PEDIDO_SALIDA", "ID_PEDIDO_SALIDA", $rowMovimientoSalidaLinea->ID_PEDIDO_SALIDA);

        //AÑADO AL ARRAY A DEVOLVER EL MOVIMIENTO DE SALIDA MODIFICADO
        $arrDevuelto['idMovimientoSalida'] = $rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA;

        //COMPRUEBO QUE TENGA PERMISOS DE ESCRITURA EN EL ALMACEN
        if ($administrador->comprobarAlmacenPermiso($rowMovimientoSalidaLinea->ID_ALMACEN, "Escritura") == false):
            $arrDevuelto['errores'] = $auxiliar->traduce("No tiene permisos para realizar esta operacion en esta subzona", $administrador->ID_IDIOMA);
        endif;

        //COMPRUEBO QUE EL ESTADO DE LA LINEA SEA PENDIENTE DE TRANSMITIR A SAP
        if (($rowMovimientoSalidaLinea->ESTADO != 'Pendiente de Expedir')):
            $arrDevuelto['errores'] = $auxiliar->traduce("La linea esta en un estado incorrecto, debe estar 'Pendiente de Transmitir a SAP'", $administrador->ID_IDIOMA);
        endif;

        //COMPRUEBO QUE LA LINEA NO HAYA SIDO ENVIADA A SAP
        if ($rowMovimientoSalidaLinea->ENVIADO_SAP != 0):
            $arrDevuelto['errores'] = $auxiliar->traduce("La linea ya ha sido transmitida a SAP", $administrador->ID_IDIOMA);
        endif;

        //BUSCO EL MOVIMIENTO DE SALIDA
        $rowMovimientoSalida = $bd->VerReg("MOVIMIENTO_SALIDA", "ID_MOVIMIENTO_SALIDA", $rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA);

        //COMPRUEBO QUE LA CABECERA DE LA LINEA ESTE ASIGNADA A UNA ORDEN DE PREPARACION
        if ($rowMovimientoSalida->ID_ORDEN_PREPARACION == NULL):
            $arrDevuelto['errores'] = $auxiliar->traduce("La linea no pertecene a ninguna orden de preparacion", $administrador->ID_IDIOMA);
        endif;

        //BUSCO LA ORDEN DE PREPARACION
        $rowOrdenPreparacion = $bd->VerReg("ORDEN_PREPARACION", "ID_ORDEN_PREPARACION", $rowMovimientoSalida->ID_ORDEN_PREPARACION);

        //COMPRUEBO QUE LA ORDEN DE TRANSPORTE SEA DE TIPO OLI (ORDEN DE LOGISTICA INVERSA)
        if ($rowOrdenPreparacion->TIPO_ORDEN != 'OLI'):
            $arrDevuelto['errores'] = $auxiliar->traduce("La orden de preparacion no es de logistica inversa, no se puede realizar la operacion.", $administrador->ID_IDIOMA);
        endif;

        //COMPRUEBO QUE TODAS LAS LINEAS DE LA ORDEN DE PREPARACION HAYAN SIDO PREPARADAS Y ASIGNADAS A BULTOS
        $num = $bd->NumRegsTabla("MOVIMIENTO_SALIDA_LINEA MSL INNER JOIN MOVIMIENTO_SALIDA MS ON MS.ID_MOVIMIENTO_SALIDA = MSL.ID_MOVIMIENTO_SALIDA", "MS.ID_ORDEN_PREPARACION = $rowOrdenPreparacion->ID_ORDEN_PREPARACION AND MS.BAJA = 0 AND MSL.LINEA_ANULADA = 0 AND MSL.BAJA = 0 AND MSL.ESTADO IN ('Reservado para Preparacion', 'En Preparacion')");
        if ($num > 0):
            $arrDevuelto['errores'] = $auxiliar->traduce("Hay lineas en la orden de preparacion aun no asignadas a bultos", $administrador->ID_IDIOMA);
        endif;

        /***************************************** COMPROBACIONES SI LA LINEA ESTA ASIGNADA A UNA ORDEN DE TRANSPORTE *****************************************/
        if ($rowMovimientoSalidaLinea->ID_EXPEDICION != NULL):
            //COMPRUEBO QUE LA ORDEN DE TRANSPORTE EXISTE
            $GLOBALS["NotificaErrorPorEmail"] = "No";
            $rowExpedicion                    = $bd->VerReg("EXPEDICION", "ID_EXPEDICION", $rowMovimientoSalidaLinea->ID_EXPEDICION, "No");
            unset($GLOBALS["NotificaErrorPorEmail"]);
            if ($rowExpedicion == false):
                $arrDevuelto['errores'] = $auxiliar->traduce("La orden de transporte a la que esta asignada la linea no existe", $administrador->ID_IDIOMA);
            endif;

            //COMPRUEBO QUE LA ORDEN DE TRANSPORTE NO ESTE DADA DE BAJA
            if ($rowExpedicion->BAJA == 1):
                $arrDevuelto['errores'] = $auxiliar->traduce("La orden de transporte a la que esta asignada la linea esta dada de baja", $administrador->ID_IDIOMA);
            endif;

            //COMPRUEBO QUE LA ORDEN DE TRANSPORTE ESTE EN ESTADO CREADA O EN TRANSMISION
            if (($rowExpedicion->ESTADO != 'Creada') && ($rowExpedicion->ESTADO != 'En Transmision')):
                $arrDevuelto['errores'] = $auxiliar->traduce("La orden de transporte a la que esta asignada la linea debe estar en estado 'Creada' o 'En Transmision'", $administrador->ID_IDIOMA);
            endif;

            //SI ESTA ASIGNADA A UN TRANSPORTE LO BUSCAMOS
            if ($rowExpedicion->ID_ORDEN_TRANSPORTE != NULL):
                $rowOrdenTransporte = $bd->VerReg("ORDEN_TRANSPORTE", "ID_ORDEN_TRANSPORTE", $rowExpedicion->ID_ORDEN_TRANSPORTE);
            endif;
        endif;
        /*************************************** FIN COMPROBACIONES SI LA LINEA ESTA ASIGNADA A UNA ORDEN DE TRANSPORTE ***************************************/

        /************************************************* COMPROBACIONES SI LA LINEA ESTA ASIGNADA A UN BULTO *************************************************/
        if ($rowMovimientoSalidaLinea->ID_BULTO != NULL):
            //COMPRUEBO QUE EL BULTO EXISTE
            $GLOBALS["NotificaErrorPorEmail"] = "No";
            $rowBulto                         = $bd->VerReg("BULTO", "ID_BULTO", $rowMovimientoSalidaLinea->ID_BULTO, "No");
            unset($GLOBALS["NotificaErrorPorEmail"]);
            if ($rowBulto == false):
                $arrDevuelto['errores'] = $auxiliar->traduce("El bulto al que esta asignada la linea no existe", $administrador->ID_IDIOMA);
            endif;

            //COMPRUEBO QUE EL BULTO ESTE EN ESTADO ABIERTO O CERRADO
            if (($rowBulto->ESTADO != 'Abierto') && ($rowBulto->ESTADO != 'Cerrado')):
                $arrDevuelto['errores'] = $auxiliar->traduce("El bulto al que esta asignada la linea debe estar en estado 'Abierto' o 'Cerrado'", $administrador->ID_IDIOMA);
            endif;
        endif;
        /*********************************************** FIN COMPROBACIONES SI LA LINEA ESTA ASIGNADA A UN BULTO ***********************************************/

        //ACTUALIZO LA LINEA SIN EL ALBARAN Y ALBARAN LINEA CORRESPONDIENTES, SOLO EN EL CASO DE ESTAR GENERADOS
        if ($rowMovimientoSalidaLinea->ID_EXPEDICION != NULL):
            $num = $bd->NumRegsTabla("ALBARAN", "ID_EXPEDICION = $rowExpedicion->ID_EXPEDICION AND BAJA = 0");
            if (($num > 0) && ($rowMovimientoSalidaLinea->ID_ALBARAN_LINEA != NULL)):
                $albaran->quitar_linea($rowMovimientoSalidaLinea);

                /************************************ DESHABILITO TRIGGERS IMPLICADOS PORQUE SE HAN HABILITADO DENTRO DE LA FUNCION ************************************/
                $sqlDeshabilitarTriggers = "SET @Trigger_MOVIMIENTO_SALIDA_LINEA_UPDATE_Deshabilitado = 1";
                $bd->ExecSQL($sqlDeshabilitarTriggers);
                /********************************** FIN DESHABILITO TRIGGERS IMPLICADOS PORQUE SE HAN HABILITADO DENTRO DE LA FUNCION **********************************/
            endif;

            //LE DESASIGNO LOS ALBARANES AL MOVIMIENTO SALIDA LINEA
            $sqlUpdate = "UPDATE MOVIMIENTO_SALIDA_LINEA SET
                            ID_ALBARAN = NULL
                            , ID_ALBARAN_LINEA = NULL
                            , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                            WHERE ID_MOVIMIENTO_SALIDA_LINEA = " . $rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA_LINEA;
            $bd->ExecSQL($sqlUpdate);
        endif;

        //BUSCO LA UBICACION DESTINO
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowUbiDestino                    = $bd->VerReg("UBICACION", "ID_UBICACION", $rowMovimientoSalidaLinea->ID_UBICACION, "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);

        //COMPRUEBO QUE EXISTE LA UBICACION DE DESTINO
        if ($rowUbiDestino == false):
            $arrDevuelto['errores'] = $auxiliar->traduce("No esta definida la ubicacion de donde salio el material", $administrador->ID_IDIOMA);
        endif;

        //SI LA VIA DE PREPARACION HA SIDO PDA
        if ($rowOrdenPreparacion->VIA_PREPARACION == 'PDA'):

            //ACTUALIZO LA CANTIDAD DEL CONTENEDOR LINEA
            $sqlUpdate = "UPDATE CONTENEDOR_LINEA SET
                            STOCK_TOTAL = STOCK_TOTAL - $rowMovimientoSalidaLinea->CANTIDAD
                            , STOCK_BLOQUEADO = STOCK_BLOQUEADO - $rowMovimientoSalidaLinea->CANTIDAD
                            WHERE ID_CONTENEDOR_LINEA = $rowMovimientoSalidaLinea->ID_CONTENEDOR_LINEA";
            $bd->ExecSQL($sqlUpdate);

            //ACTUALIZO LA CANTIDAD DEL BULTO LINEA
            $sqlUpdate = "UPDATE BULTO_LINEA SET
                            CANTIDAD = CANTIDAD - $rowMovimientoSalidaLinea->CANTIDAD
                            WHERE ID_BULTO_LINEA = $rowMovimientoSalidaLinea->ID_BULTO_LINEA";
            $bd->ExecSQL($sqlUpdate);

            //INICIALIZO LA LINEA DEL MOVIMIENTO DE SALIDA
            $sqlUpdate = "UPDATE MOVIMIENTO_SALIDA_LINEA SET
                            ID_CONTENEDOR = NULL
                            , ID_CONTENEDOR_LINEA = NULL
                            , ID_BULTO = NULL
                            , ID_BULTO_LINEA = NULL
                            WHERE ID_MOVIMIENTO_SALIDA_LINEA = $rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA_LINEA";
            $bd->ExecSQL($sqlUpdate);

            //BORRO EL CONTENEDOR LINEA SI NO TIENE STOCK
            $sqlDelete = "DELETE FROM CONTENEDOR_LINEA WHERE ID_CONTENEDOR_LINEA = $rowMovimientoSalidaLinea->ID_CONTENEDOR_LINEA AND STOCK_TOTAL = 0";
            $bd->ExecSQL($sqlDelete);

            //BORRO EL BULTO LINEA SI NO TIENE STOCK
            $sqlDelete = "DELETE FROM BULTO_LINEA WHERE ID_BULTO_LINEA = $rowMovimientoSalidaLinea->ID_BULTO_LINEA AND CANTIDAD = 0";
            $bd->ExecSQL($sqlDelete);

            // LOG MOVIMIENTOS
            $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Bulto", $rowMovimientoSalidaLinea->ID_BULTO, "Desasignacion linea " . $rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA_LINEA . " de bulto");

            //SI EL BULTO NO TIENE LINEAS ASOCIADAS, LO DESASOCIO DE LA ORDEN DE TRANSPORTE
            $num = $bd->NumRegsTabla("MOVIMIENTO_SALIDA_LINEA", "ID_BULTO = $rowMovimientoSalidaLinea->ID_BULTO AND LINEA_ANULADA = 0 AND BAJA = 0");
            if ($num == 0):
                $sqlUpdate = "UPDATE BULTO SET
                              ID_EXPEDICION = NULL
                              WHERE ID_BULTO = $rowMovimientoSalidaLinea->ID_BULTO";
                $bd->ExecSQL($sqlUpdate);

                //BUSCO EL BULTO
                $rowBulto = $bd->VerReg("BULTO", "ID_BULTO", $rowMovimientoSalidaLinea->ID_BULTO);

                //ACTUALIZO LOS DATOS DE LA ORDEN DE TRANSPORTE EN CASO DE PERTENCER A UNA
                if ($rowMovimientoSalidaLinea->ID_EXPEDICION != NULL):
                    $sqlUpdate = "UPDATE EXPEDICION SET
                                    ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                    , NUM_BULTOS = NUM_BULTOS - 1
                                    , PESO = PESO - " . $rowBulto->PESO . "
                                    WHERE ID_EXPEDICION = $rowMovimientoSalidaLinea->ID_EXPEDICION";
                    $bd->ExecSQL($sqlUpdate);

                    //BUSCO LA ORDEN DE RECOGIDA (EXPEDICION)
                    $rowExpedicion = $bd->VerReg("EXPEDICION", "ID_EXPEDICION", $rowMovimientoSalidaLinea->ID_EXPEDICION);

                    //ACTUALIZO EL PESO DE LA ORDEN DE TRANSPORTE
                    if ($rowExpedicion->ID_ORDEN_TRANSPORTE != NULL):
                        $orden_transporte->ActualizarPeso($rowExpedicion->ID_ORDEN_TRANSPORTE);
                    endif;
                    //FIN ACTUALIZO EL PESO DE LA ORDEN DE TRANSPORTE
                endif;

                //COMPRUEBO SI EL BULTO TIENE CANTIDAD ASOCIADA AL CONTENEDOR
                $sqlCantidadContenedor    = "SELECT SUM(STOCK_TOTAL) AS CANTIDAD
                                                FROM CONTENEDOR_LINEA CL
                                                INNER JOIN CONTENEDOR C ON C.ID_CONTENEDOR = CL.ID_CONTENEDOR
                                                WHERE C.ID_BULTO = $rowMovimientoSalidaLinea->ID_BULTO AND CL.BAJA = 0";
                $resultCantidadContenedor = $bd->ExecSQL($sqlCantidadContenedor);
                if (($resultCantidadContenedor != false) && ($bd->NumRegs($resultCantidadContenedor) == 1)):
                    $rowCantidadContenedor = $bd->SigReg($resultCantidadContenedor);
                    if ($rowCantidadContenedor->CANTIDAD == 0): //SI EL CONTENEDOR NO TIENE STOCK
                        //ELIMINO EL CONTENEDOR DEL BULTO
                        $sqlDelete = "DELETE FROM CONTENEDOR WHERE ID_BULTO = $rowMovimientoSalidaLinea->ID_BULTO";
                        $bd->ExecSQL($sqlDelete);

                        //ELIMINO LA ETIQUETA PENDIENTE DE IMPRIMIR
                        $sqlDelete = "DELETE FROM ETIQUETA_PDTE WHERE TIPO = 'Bulto' AND ID_BULTO = $rowMovimientoSalidaLinea->ID_BULTO";
                        $bd->ExecSQL($sqlDelete);

                        //DOY DE BAJA EL BULTO EN OBJETOS
                        $sqlUpdate = "UPDATE SOLICITUD_TRANSPORTE_BULTO SET ID_BULTO = NULL WHERE ID_BULTO =$rowMovimientoSalidaLinea->ID_BULTO";
                        $bd->ExecSQL($sqlUpdate);
                        $sqlUpdate = "DELETE FROM BULTO_CONTRATACION_DESTINO WHERE ID_BULTO = $rowMovimientoSalidaLinea->ID_BULTO";
                        $bd->ExecSQL($sqlUpdate);

                        //BORRAMOS BULTO
                        $sqlDelete = "DELETE FROM BULTO WHERE ID_BULTO = $rowMovimientoSalidaLinea->ID_BULTO";
                        $bd->ExecSQL($sqlDelete);
                    endif;
                    //FIN SI EL CONTENEDOR NO TIENE STOCK
                endif;
                //FIN COMPRUEBO SI EL BULTO TIENE CANTIDAD ASOCIADA AL CONTENEDOR
            endif;
            //FIN SI EL BULTO NO TIENE LINEAS ASOCIADAS, LO DESASOCIO DE LA ORDEN DE TRANSPORTE
        endif;//FIN SI LA VIA DE PREPARACION ES PDA

        $sqlTransferencias    = "SELECT *
                                    FROM MOVIMIENTO_TRANSFERENCIA
                                    WHERE ID_MOVIMIENTO_SALIDA_LINEA = $rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA_LINEA AND BAJA = 0";
        $resultTransferencias = $bd->ExecSQL($sqlTransferencias);
        while ($rowTransferencia = $bd->SigReg($resultTransferencias)):
            //BUSCO MATERIAL UBICACION  DONDE SE DEPOSITÓ EL MATERIAL (DEBERIA SER DE TIPO SALIDA)
            $GLOBALS["NotificaErrorPorEmail"] = "No";
            $rowMatUbi                        = $bd->VerRegRest("MATERIAL_UBICACION", "ID_MATERIAL = $rowTransferencia->ID_MATERIAL AND ID_UBICACION = $rowTransferencia->ID_UBICACION_DESTINO AND ID_MATERIAL_FISICO " . ($rowTransferencia->ID_MATERIAL_FISICO == NULL ? 'IS NULL' : " = $rowTransferencia->ID_MATERIAL_FISICO") . " AND ID_TIPO_BLOQUEO " . ($rowTransferencia->ID_TIPO_BLOQUEO == NULL ? 'IS NULL' : "= $rowTransferencia->ID_TIPO_BLOQUEO") . " AND ID_ORDEN_TRABAJO_MOVIMIENTO " . ($rowTransferencia->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? 'IS NULL' : "= $rowTransferencia->ID_ORDEN_TRABAJO_MOVIMIENTO") . " AND ID_INCIDENCIA_CALIDAD " . ($rowTransferencia->ID_INCIDENCIA_CALIDAD == NULL ? 'IS NULL' : "= $rowTransferencia->ID_INCIDENCIA_CALIDAD"), "No");
            unset($GLOBALS["NotificaErrorPorEmail"]);

            //COMPRUEBO QUE EXISTA MATERIAL_UBICACION ORIGEN
            if ($rowMatUbi == false):
                $arrDevuelto['errores'] = $auxiliar->traduce("No existe la dupla material-ubicacion donde descontar el material", $administrador->ID_IDIOMA);
            endif;
            //$html->PagErrorCondicionado($rowMatUbi, "==", false, "UbicacionSalidaNoExiste");

            //COMPRUEBO QUE EN LA UBICACION ORIGEN (SM) HAYA SUFICIENTE STOCK
            if ($rowMatUbi->STOCK_TOTAL < $rowTransferencia->CANTIDAD):
                $arrDevuelto['errores'] = $auxiliar->traduce("No hay stock suficiente para reubicar y desasignar la linea", $administrador->ID_IDIOMA);
            endif;

            //SI NO SE HAN PRODUCIDO ERRORES EJECUTAMOS LAS OPERACIONES CORRESPONDIENTES
            if ((!(isset($arrDevuelto['errores']))) || ($arrDevuelto['errores'] == "")):
                //DECREMENTO CANTIDAD EN MATERIAL UBICACION ORIGEN (DEBERIA SER DE TIPO SALIDA)
                $sqlUpdate = "UPDATE MATERIAL_UBICACION SET
                            STOCK_TOTAL = STOCK_TOTAL - $rowTransferencia->CANTIDAD
                            , STOCK_OK = STOCK_OK - " . ($rowTransferencia->ID_TIPO_BLOQUEO == NULL ? $rowTransferencia->CANTIDAD : 0) . "
                            , STOCK_BLOQUEADO = STOCK_BLOQUEADO - " . ($rowTransferencia->ID_TIPO_BLOQUEO == NULL ? 0 : $rowTransferencia->CANTIDAD) . "
                            WHERE ID_MATERIAL_UBICACION = $rowMatUbi->ID_MATERIAL_UBICACION";//echo($sqlUpdate."<hr>");
                $bd->ExecSQL($sqlUpdate);
            endif;

            //BUSCO LA UBICACION DESTINO (DE DONDE SE DESUBICO EL MATERIAL)
            $GLOBALS["NotificaErrorPorEmail"] = "No";
            $rowMatUbi                        = $bd->VerRegRest("MATERIAL_UBICACION", "ID_MATERIAL = $rowTransferencia->ID_MATERIAL AND ID_UBICACION = $rowTransferencia->ID_UBICACION_ORIGEN AND ID_MATERIAL_FISICO " . ($rowTransferencia->ID_MATERIAL_FISICO == NULL ? 'IS NULL' : " = $rowTransferencia->ID_MATERIAL_FISICO") . " AND ID_TIPO_BLOQUEO " . ($rowTransferencia->ID_TIPO_BLOQUEO == NULL ? 'IS NULL' : "= $rowTransferencia->ID_TIPO_BLOQUEO") . " AND ID_ORDEN_TRABAJO_MOVIMIENTO " . ($rowTransferencia->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? 'IS NULL' : "= $rowTransferencia->ID_ORDEN_TRABAJO_MOVIMIENTO") . " AND ID_INCIDENCIA_CALIDAD " . ($rowTransferencia->ID_INCIDENCIA_CALIDAD == NULL ? 'IS NULL' : "= $rowTransferencia->ID_INCIDENCIA_CALIDAD"), "No");
            unset($GLOBALS["NotificaErrorPorEmail"]);
            if ($rowMatUbi == false):
                //CREO MATERIAL UBICACION
                $sqlInsert = "INSERT INTO MATERIAL_UBICACION SET
                                ID_MATERIAL = $rowTransferencia->ID_MATERIAL
                                , ID_MATERIAL_FISICO = " . ($rowTransferencia->ID_MATERIAL_FISICO == NULL ? 'NULL' : "$rowTransferencia->ID_MATERIAL_FISICO") . "
                                , ID_UBICACION = $rowTransferencia->ID_UBICACION_ORIGEN
                                , ID_TIPO_BLOQUEO = " . ($rowTransferencia->ID_TIPO_BLOQUEO == NULL ? 'NULL' : "$rowTransferencia->ID_TIPO_BLOQUEO") . "
                                , ID_ORDEN_TRABAJO_MOVIMIENTO = " . ($rowTransferencia->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? 'NULL' : "$rowTransferencia->ID_ORDEN_TRABAJO_MOVIMIENTO") . "
                                , ID_INCIDENCIA_CALIDAD =" . ($rowTransferencia->ID_INCIDENCIA_CALIDAD == NULL ? 'NULL' : "$rowTransferencia->ID_INCIDENCIA_CALIDAD");//echo($sqlInsert."<hr>");
                $bd->ExecSQL($sqlInsert);
                $idMatUbi = $bd->IdAsignado();
            else:
                $idMatUbi = $rowMatUbi->ID_MATERIAL_UBICACION;
            endif;

            //INCREMENTO CANTIDAD EN MATERIAL UBICACION DESTINO (DE DONDE SE DESUBICO EL MATERIAL)
            $sqlUpdate = "UPDATE MATERIAL_UBICACION SET
                            STOCK_TOTAL = STOCK_TOTAL + $rowTransferencia->CANTIDAD
                            , STOCK_OK = STOCK_OK + " . ($rowTransferencia->ID_TIPO_BLOQUEO == NULL ? $rowTransferencia->CANTIDAD : 0) . "
                            , STOCK_BLOQUEADO = STOCK_BLOQUEADO + " . ($rowTransferencia->ID_TIPO_BLOQUEO == NULL ? 0 : $rowTransferencia->CANTIDAD) . "
                            WHERE ID_MATERIAL_UBICACION = $idMatUbi";//echo($sqlUpdate."<hr>");
            $bd->ExecSQL($sqlUpdate);

            //DAMOS DE BAJA LA TRANSFERENCIA AUTOMATICA
            $sqlUpdate = "UPDATE MOVIMIENTO_TRANSFERENCIA SET
                            ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                            , BAJA = 1
                            WHERE ID_MOVIMIENTO_TRANSFERENCIA = $rowTransferencia->ID_MOVIMIENTO_TRANSFERENCIA";
            $bd->ExecSQL($sqlUpdate);
        endwhile; //FIN BUCLE TRANSFERENCIA

        //DAMOS DE BAJA LA LINEA DEL MOVIMIENTO
        $sqlUpdate = "UPDATE MOVIMIENTO_SALIDA_LINEA SET
                        ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                        , BAJA = 1
                        , ID_CONTENEDOR = NULL
                        , ID_CONTENEDOR_LINEA = NULL
                        , ID_BULTO = NULL
                        , ID_BULTO_LINEA = NULL
                        , ID_EXPEDICION_SAP = NULL
                        , EXPEDICION_SAP = ''
                        WHERE ID_MOVIMIENTO_SALIDA_LINEA = $rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA_LINEA";
        $bd->ExecSQL($sqlUpdate);

        //QUITAMOS EL MOVIMIENTO SALIDA LINEA DE FACTURAS NO COMERCIALES (SON FACTURAS QUE YA ESTARAN DADAS DE BAJA)
        $sqlUpdate = "UPDATE FACTURA_NO_COMERCIAL_MOVIMIENTO SET ID_MOVIMIENTO_SALIDA_LINEA = NULL WHERE ID_MOVIMIENTO_SALIDA_LINEA = $rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA_LINEA AND BAJA = 1";
        $bd->ExecSQL($sqlUpdate);

        //BORRAMOS DEL REPARTO DE COSTES LA LINEA CORRESPONDIENTE SI EXISTE
        $sqlDelete = "DELETE FROM ORDEN_TRANSPORTE_REPARTO_COSTES WHERE ID_MOVIMIENTO_SALIDA_LINEA = $rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA_LINEA";
        $bd->ExecSQL($sqlDelete);

        // LOG MOVIMIENTOS
        $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Mov. salida", $rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA, "Anulacion linea: " . $rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA_LINEA);

        //ACTUALIZO EL ESTADO DEL MOVIMIENTO DE SALIDA
        $movimiento->actualizarEstadoMovimientoSalida($rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA);

        //COMPRUEBO SI EL MOVIMIENTO NO TIENE LINEAS PARA DARLO DE BAJA
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $numLineasMovimientoActivas       = $bd->VerRegRest("MOVIMIENTO_SALIDA_LINEA", "ID_MOVIMIENTO_SALIDA = $rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA AND LINEA_ANULADA = 0 AND BAJA = 0", "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);
        if ($numLineasMovimientoActivas == 0):
            //DAMOS DE BAJA EL MOVIMIENTO
            $sqlUpdate = "UPDATE MOVIMIENTO_SALIDA SET
                            ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                            , BAJA = 1
                            WHERE ID_MOVIMIENTO_SALIDA = $rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA";
            $bd->ExecSQL($sqlUpdate);

            // LOG MOVIMIENTOS
            $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Anulación", "Mov. salida", $rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA, "");
        endif;

        //BUSCO LA LINEA DEL PEDIDO DE SALIDA AFECTADA
        $rowPedidoSalidaLinea = $bd->VerReg("PEDIDO_SALIDA_LINEA", "ID_PEDIDO_SALIDA_LINEA", $rowMovimientoSalidaLinea->ID_PEDIDO_SALIDA_LINEA);

        //DAMOS DE BAJA LA LINEA DEL PEDIDO
        $sqlUpdate = "UPDATE PEDIDO_SALIDA_LINEA SET
                        ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                        , CANTIDAD_PENDIENTE_SERVIR = CANTIDAD_PENDIENTE_SERVIR + $rowMovimientoSalidaLinea->CANTIDAD
                        , BAJA = 1
                        , INDICADOR_BORRADO = 'L'
                        WHERE ID_PEDIDO_SALIDA_LINEA = $rowMovimientoSalidaLinea->ID_PEDIDO_SALIDA_LINEA";
        $bd->ExecSQL($sqlUpdate);

        //MARCAMOS EL PEDIDO PENDIENTE DE TRANSMITIR A SAP
        $sqlUpdate = "UPDATE PEDIDO_SALIDA SET
                        ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                        , PENDIENTE_TRANSMITIR_A_SAP = 1
                        WHERE ID_PEDIDO_SALIDA = $rowMovimientoSalidaLinea->ID_PEDIDO_SALIDA";
        $bd->ExecSQL($sqlUpdate);

        // LOG MOVIMIENTOS
        $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Pedido salida", $rowMovimientoSalidaLinea->ID_PEDIDO_SALIDA, "Anulacion linea: " . $rowMovimientoSalidaLinea->ID_PEDIDO_SALIDA_LINEA);

        //COMPRUEBO SI EL PEDIDO NO TIENE LINEAS PARA DARLO DE BAJA
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $numLineasPedidoActivas           = $bd->VerRegRest("PEDIDO_SALIDA_LINEA", "ID_PEDIDO_SALIDA = $rowMovimientoSalidaLinea->ID_PEDIDO_SALIDA AND BAJA = 0", "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);
        if ($numLineasPedidoActivas == 0):
            //DAMOS DE BAJA EL PEDIDO
            $sqlUpdate = "UPDATE PEDIDO_SALIDA SET
                            ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                            , BAJA = 1
                            , ESTADO = 'Finalizado'
                            WHERE ID_PEDIDO_SALIDA = $rowMovimientoSalidaLinea->ID_PEDIDO_SALIDA";
            $bd->ExecSQL($sqlUpdate);

            // LOG MOVIMIENTOS
            $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Anulación", "Pedido salida", $rowMovimientoSalidaLinea->ID_PEDIDO_SALIDA, "");
        endif;

        //ACTUALIZO EL ESTADO DE LA ORDEN DE PREPARACION
        $orden_preparacion->ActualizarEstadoOrdenPreparacion($rowOrdenPreparacion->ID_ORDEN_PREPARACION);

        //SI TODAS LAS LINEAS DE LA ORDEN DE PREPARACION HAN SIDO ANULADAS, ANULO LA ORDEN
        $num = $bd->NumRegsTabla("MOVIMIENTO_SALIDA_LINEA MSL INNER JOIN MOVIMIENTO_SALIDA MS ON MS.ID_MOVIMIENTO_SALIDA = MSL.ID_MOVIMIENTO_SALIDA", "MS.ID_ORDEN_PREPARACION = $rowOrdenPreparacion->ID_ORDEN_PREPARACION AND MS.BAJA = 0 AND MSL.BAJA = 0 AND MSL.LINEA_ANULADA = 0");
        if ($num == 0):
            //DAMOS DE BAJA LA ORDEN DE PREPARACION
            $sqlUpdate = "UPDATE ORDEN_PREPARACION SET
                            ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                            , BAJA = 1
                            WHERE ID_ORDEN_PREPARACION = $rowOrdenPreparacion->ID_ORDEN_PREPARACION";
            $bd->ExecSQL($sqlUpdate);

            // LOG MOVIMIENTOS
            $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Anulación", "Orden preparación", $rowOrdenPreparacion->ID_ORDEN_PREPARACION, "");
        endif;

        //ACCIONES SI LA LINEA ORIGINALMENTE ESTABA ASIGNADA A UNA ORDEN DE TRANSPORTE
        if ($rowMovimientoSalidaLinea->ID_EXPEDICION != NULL):
            $this->actualizar_estado_orden_transporte($rowMovimientoSalidaLinea->ID_EXPEDICION);
            $this->actualizar_tipo_orden_transporte($rowMovimientoSalidaLinea->ID_EXPEDICION);
        endif;
        //FIN ACCIONES SI LA LINEA ORIGINALMENTE ESTABA ASIGNADA A UNA ORDEN DE TRANSPORTE

        //SI LA LINEA DEL MOVIMIENTO DE SALIDA GENERO UN TRASLADO DIRECTO PREVIO Y NO SE HAN PRODUCIDO ERRORES, LO REVIERTO
        if (($rowMovimientoSalidaLinea->ID_MOVIMIENTO_TRASLADO_DIRECTO != NULL) && (!(isset($arrDevuelto['errores'])))):
            $arrDevueltoRevertirTrasladoDirecto = $stock_compartido->Revertir_Traslado_Directo($rowMovimientoSalidaLinea->ID_MOVIMIENTO_TRASLADO_DIRECTO, 'LogisticaInversaBorrarLineaPropuestaPreparacion');
            if ($arrDevueltoRevertirTrasladoDirecto['resultado'] != 'OK'):
                $arrDevuelto['erroresSAP'] = $arrDevueltoRevertirTrasladoDirecto['errores'];
            endif;
        endif;

        /************************************ HABILITO TRIGGERS IMPLICADOS ************************************/
        $sqlHabilitarTriggers = "SET @Trigger_MOVIMIENTO_SALIDA_LINEA_UPDATE_Deshabilitado = NULL";
        $bd->ExecSQL($sqlHabilitarTriggers);
        $sqlHabilitarTriggers = "SET @Trigger_PEDIDO_SALIDA_LINEA_UPDATE_Deshabilitado = NULL";
        $bd->ExecSQL($sqlHabilitarTriggers);
        /********************************** FIN HABILITO TRIGGERS IMPLICADOS **********************************/

        //MODIFICO LA LINEA PARA QUE SE EJECUTEN LOS TRIGGERS Y LAS LINEAS SE QUEDEN COMO CORRESPONDAN
        $sqlUpdate = "UPDATE MOVIMIENTO_SALIDA_LINEA SET BAJA = BAJA WHERE ID_MOVIMIENTO_SALIDA_LINEA = $rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA_LINEA";
        $bd->ExecSQL($sqlUpdate);

        //DEVUELVO EL ARRAY
        return $arrDevuelto;
    }

    /**
     * @param $idOrdenTransporte ORDEN DE RECOGIDA
     * @param $lineasSeleccionadas LINEAS SELECCIONADAS PARA QUITAR DE LA ORDEN DE RECOGIDA, ES UN ARRAY CUYA CLAVE ES "ID_MOVIMIENTO_ID_MOVIMIENTO_LINEA" Y EL VALOR ES UN 1 SI SE VA A DESASIGNAR
     * FUNCION UTILIZADA PARA DESASIGNAR UNA O VARIAS LINEAS DE UNA ORDEN DE RECOGIDA
     */
    function desasignar_lineas_orden_transporte($idOrdenTransporte, $lineasSeleccionadas)
    {
        global $bd;
        global $html;
        global $auxiliar;
        global $administrador;
        global $albaran;
        global $orden_transporte;
        global $exp_SAP;

        //BUSCO LA ORDEN DE RECOGIDA
        $rowOrdenTransporte = $bd->VerReg("EXPEDICION", "ID_EXPEDICION", $idOrdenTransporte, "No");

        //VARIABLE PARA SABER POR LOS BULTOS PASADOS PARA NO DESCONTAR VARIAS VECES DE LA ORDEN DE TRANSPORTE
        $arrBultosProcesados = array();

        /************************************ DESHABILITO TRIGGERS IMPLICADOS ************************************/
        $sqlDeshabilitarTriggers = "SET @Trigger_MOVIMIENTO_SALIDA_LINEA_UPDATE_Deshabilitado = 1";
        $bd->ExecSQL($sqlDeshabilitarTriggers);
        /********************************** FIN DESHABILITO TRIGGERS IMPLICADOS **********************************/

        //BUCLE LINEAS A DESASIGNAR
        foreach ($lineasSeleccionadas as $clave => $valor):
            if (($valor == 1) && (strpos( (string)$clave, "_") != false)): //SI ESTA MARCADO Y ES UNA LINEA
                //EXTRAIGO LAS CLAVES
                $arrClaves = explode("_", (string)$clave);

                //BUSCO LA LINEA DEL MOVIMIENTO DE SALIDA
                $GLOBALS["NotificaErrorPorEmail"] = "No";
                $rowMovSalLinea                   = $bd->VerReg("MOVIMIENTO_SALIDA_LINEA", "ID_MOVIMIENTO_SALIDA_LINEA", $arrClaves[1], "No");
                unset($GLOBALS["NotificaErrorPorEmail"]);

                //DATOS ERRONEOS DE LINEA
                //BUSCO EL MOVIMIENTO DE SALIDA
                $rowMovimientoSalida = $bd->VerReg("MOVIMIENTO_SALIDA", "ID_MOVIMIENTO_SALIDA", $rowMovSalLinea->ID_MOVIMIENTO_SALIDA);
                //BUSCO EL PEDIDO DE SALIDA
                $rowPedidoSalida = $bd->VerReg("PEDIDO_SALIDA", "ID_PEDIDO_SALIDA", $rowMovSalLinea->ID_PEDIDO_SALIDA);
                //BUSCO LA LINEA DEL PEDIDO DE SALIDA
                $rowPedidoSalidaLinea = $bd->VerReg("PEDIDO_SALIDA_LINEA", "ID_PEDIDO_SALIDA_LINEA", $rowMovSalLinea->ID_PEDIDO_SALIDA_LINEA);

                //DATOS DEL ERROR DE LINEA
                $datosLinea = $auxiliar->traduce("Movimiento", $administrador->ID_IDIOMA) . ": " . $rowMovimientoSalida->ID_MOVIMIENTO_SALIDA . " - " . $auxiliar->traduce(($rowPedidoSalida->PEDIDO_SAP != '' ? 'Pedido SAP' : 'Pedido SGA'), $administrador->ID_IDIOMA) . ": " . ($rowPedidoSalida->PEDIDO_SAP != '' ? $rowPedidoSalida->PEDIDO_SAP : $rowPedidoSalida->ID_PEDIDO_SALIDA) . " - " . $auxiliar->traduce("Nº Linea", $administrador->ID_IDIOMA) . ": " . (int)$rowPedidoSalidaLinea->LINEA_PEDIDO_SAP . " - " . $auxiliar->traduce("Orden de Recogida", $administrador->ID_IDIOMA) . ": " . $rowOrdenTransporte->ID_EXPEDICION;
                //FIN DATOS DEL ERROR DE LINEA

                //COMPRUEBO QUE TENGA PERMISOS DE ESCRITURA EN EL ALMACEN
                $html->PagErrorCondicionado($administrador->comprobarAlmacenPermiso($rowMovSalLinea->ID_ALMACEN, "Escritura"), "==", false, "SinPermisosSubzona");

                //COMPRUEBO SI LA LINEA PERTENECE A BULTO Y LA ORDEN ES SIN BULTOS O VICEVERSA, SI ES ASI ERROR
                if ($rowOrdenTransporte->CON_BULTOS == 0):
                    $html->PagErrorCondicionado($rowMovSalLinea->ID_BULTO, "!=", NULL, "LineaPerteneceBulto");
                elseif ($rowOrdenTransporte->CON_BULTOS == 1):
                    $html->PagErrorCondicionado($rowMovSalLinea->ID_BULTO, "==", NULL, "LineaNoPerteneceBulto");
                endif;

                //COMPROBACIONES SI LA ORDEN DE TRANSPORTE ES CON BULTOS
                if ($rowOrdenTransporte->CON_BULTOS == 1):
                    //COMPRUEBO QUE EL BULTO ESTE EN ESTADO 'Cerrado'
                    $rowBulto = $bd->VerReg("BULTO", "ID_BULTO", $rowMovSalLinea->ID_BULTO);
                    $html->PagErrorCondicionado($rowBulto->ESTADO, "!=", 'Cerrado', "EstadoBultoIncorrecto");

                    //COMPRUEBO QUE TODAS LAS LINEAS DEL BULTO HAYA SIDO MARCADAS
                    $sqlLineasBulto    = "SELECT MSL.ID_BULTO, MSL.ID_MOVIMIENTO_SALIDA_LINEA
                                         FROM MOVIMIENTO_SALIDA_LINEA MSL
                                         WHERE MSL.ID_BULTO = $rowMovSalLinea->ID_BULTO AND MSL.LINEA_ANULADA = 0 AND MSL.BAJA = 0";
                    $resultLineasBulto = $bd->ExecSQL($sqlLineasBulto);
                    while ($rowLineaBulto = $bd->SigReg($resultLineasBulto)):
                        $variableNecesariaSeleccionada = $lineasSeleccionadas[$rowLineaBulto->ID_BULTO . "_" . $rowLineaBulto->ID_MOVIMIENTO_SALIDA_LINEA];
                        if ((!(isset($variableNecesariaSeleccionada))) || ($variableNecesariaSeleccionada != 1)):
                            $html->PagError("SeleccionLineasBultoNoCompleto");
                        endif;
                    endwhile;

                    //BUSCO LA ORDEN DE PREPARACION
                    $GLOBALS["NotificaErrorPorEmail"] = "No";
                    $rowOrdenPreparacion              = $bd->VerReg("ORDEN_PREPARACION", "ID_ORDEN_PREPARACION", $rowMovimientoSalida->ID_ORDEN_PREPARACION, "No");
                    unset($GLOBALS["NotificaErrorPorEmail"]);
                    if ($rowOrdenPreparacion->TIPO_ORDEN == 'OLI'):
                        $html->PagError("OrdenPreparacionLogisticaInversa");
                    endif;

                    //SI EL BULTO DE LA LINEA NO ESTA EN EL ARRAY DE LOS BULTOS PROCESADOS ACTUALIZO LA ORDEN DE TRANSPORTE Y EL BULTO
                    if (!(in_array($rowBulto->ID_BULTO, (array) $arrBultosProcesados))):

                        //ACTUALIZO EL NUMERO DE BULTOS Y EL PESO DE LA ORDEN DE TRANSPORTE
                        $sqlUpdate = "UPDATE EXPEDICION SET
                                      ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                      , NUM_BULTOS = NUM_BULTOS - 1
                                      , PESO = PESO - $rowBulto->PESO
                                      WHERE ID_EXPEDICION = $rowOrdenTransporte->ID_EXPEDICION";
                        $bd->ExecSQL($sqlUpdate);

                        //BUSCO LA ORDEN DE RECOGIDA (EXPEDICION)
                        $rowExpedicion = $bd->VerReg("EXPEDICION", "ID_EXPEDICION", $rowOrdenTransporte->ID_EXPEDICION);

                        //ACTUALIZO EL PESO DE LA ORDEN DE TRANSPORTE
                        if ($rowExpedicion->ID_ORDEN_TRANSPORTE != NULL):
                            $orden_transporte->ActualizarPeso($rowExpedicion->ID_ORDEN_TRANSPORTE);
                        endif;
                        //FIN ACTUALIZO EL PESO DE LA ORDEN DE TRANSPORTE

                        //VACIO LA ORDEN DE TRANSPORTE DEL BULTO
                        $sqlUpdate = "UPDATE BULTO SET
                                      ID_EXPEDICION = NULL
                                      WHERE ID_BULTO = $rowBulto->ID_BULTO";
                        $bd->ExecSQL($sqlUpdate);

                        //QUITO EL BULTO DE LA CONTRATACION EN CASO DE ESTAR ASIGNADO
                        $sqlUpdate = "UPDATE BULTO_CONTRATACION_DESTINO SET
                                      BAJA = 1
                                      WHERE ID_BULTO = $rowBulto->ID_BULTO AND BAJA = 0";
                        $bd->ExecSQL($sqlUpdate);

                        //AÑADO EL BULTO A LOS PROCESADOS
                        $arrBultosProcesados[] = $rowBulto->ID_BULTO;
                    endif;
                endif;

                /************************************************* COMPROBACIONES DE LINEA *************************************************/
                //COMPRUEBO QUE NO ESTE DADA DE BAJA
                if ($rowMovSalLinea->BAJA == 1):
                    global $strError;
                    $strError = $datosLinea;
                    $html->PagError("LineaBaja");
                endif;
                //$html->PagErrorCondicionado($rowMovSalLinea->BAJA, "==", 1, "LineaBaja");

                //COMPRUEBO QUE NO ESTE ANULADA
                if ($rowMovSalLinea->LINEA_ANULADA == 1):
                    global $strError;
                    $strError = $datosLinea;
                    $html->PagError("LineaAnulada");
                endif;
                //$html->PagErrorCondicionado($rowMovSalLinea->LINEA_ANULADA, "==", 1, "LineaAnulada");

//                //COMPRUEBO QUE NO ESTE EN ESTADO PENDIENTE DE EXPEDIR
//                $html->PagErrorCondicionado($rowMovSalLinea->ESTADO, "!=", "Pendiente de Expedir", "EstadoLineaMovimientoIncorrecto");

                //COMPRUEBO QUE NO ESTE ENVIADA A SAP
                if ($rowMovSalLinea->ENVIADO_SAP == 1):
                    global $strError;
                    $strError = $datosLinea;
                    $html->PagError("LineaEnviadaSAP");
                endif;
                //$html->PagErrorCondicionado($rowMovSalLinea->ENVIADO_SAP, "==", 1, "LineaEnviadaSAP");

                //COMPRUEBO QUE TENGA EXPEDICION SGA
                $html->PagErrorCondicionado($rowMovSalLinea->ID_EXPEDICION, "==", NULL, "LineaSinExpedicionSGA");
                /*********************************************** FIN COMPROBACIONES DE LA LINEA ***********************************************/

                //ACTUALIZO LA LINEA SIN EL ALBARAN Y ALBARAN LINEA CORRESPONDIENTES, SOLO EN EL CASO DE ESTAR GENERADOS
                $num = $bd->NumRegsTabla("ALBARAN", "ID_EXPEDICION = $rowOrdenTransporte->ID_EXPEDICION AND BAJA = 0");
                if (($num > 0) && ($rowMovSalLinea->ID_ALBARAN_LINEA != NULL)):
                    $albaran->quitar_linea($rowMovSalLinea);

                    /************************************ DESHABILITO TRIGGERS IMPLICADOS PORQUE SE HAN HABILITADO DENTRO DE LA FUNCION ************************************/
                    $sqlDeshabilitarTriggers = "SET @Trigger_MOVIMIENTO_SALIDA_LINEA_UPDATE_Deshabilitado = 1";
                    $bd->ExecSQL($sqlDeshabilitarTriggers);
                    /********************************** FIN DESHABILITO TRIGGERS IMPLICADOS PORQUE SE HAN HABILITADO DENTRO DE LA FUNCION **********************************/
                endif;

                //BUSCO LA LINEA DEL MOVIMIENTO DE SALIDA PREVIO A LA ACTUALIZACION
                $rowLineaMovimientoSalidaPrevioActualizacion = $bd->VerReg("MOVIMIENTO_SALIDA_LINEA", "ID_MOVIMIENTO_SALIDA_LINEA", $rowMovSalLinea->ID_MOVIMIENTO_SALIDA_LINEA);

                //LE DESASIGNO LA EXPEDICION A LA LINEA DEL MOVIMIENTO DE SALIDA
                $sqlUpdate = "UPDATE MOVIMIENTO_SALIDA_LINEA SET
                                ID_EXPEDICION = NULL
                                , EXPEDICION_SAP = ''
                                , ID_EXPEDICION_SAP = NULL
                                , ID_ALBARAN = NULL
                                , ID_ALBARAN_LINEA = NULL
                                , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                WHERE ID_MOVIMIENTO_SALIDA_LINEA = " . $rowMovSalLinea->ID_MOVIMIENTO_SALIDA_LINEA;
                $bd->ExecSQL($sqlUpdate);

                //BUSCO LA LINEA DEL MOVIMIENTO DE SALIDA POSTERIOR A LA ACTUALIZACION
                $rowLineaMovimientoSalidaPosteriorActualizacion = $bd->VerReg("MOVIMIENTO_SALIDA_LINEA", "ID_MOVIMIENTO_SALIDA_LINEA", $rowMovSalLinea->ID_MOVIMIENTO_SALIDA_LINEA);

                // LOG MOVIMIENTOS
                $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Mov. salida", $rowMovSalLinea->ID_MOVIMIENTO_SALIDA, "Desasignar expedicion SAP a la linea de movimiento con ID: $rowMovSalLinea->ID_MOVIMIENTO_SALIDA_LINEA", "MOVIMIENTO_SALIDA_LINEA", $rowLineaMovimientoSalidaPrevioActualizacion, $rowLineaMovimientoSalidaPosteriorActualizacion);

                //SI LA LINEA TENIA ID_EXPEDICION_SAP ASIGNADA LA DOY DE BAJA SI PUEDO
                $exp_SAP->darDeBajaIdExpedicionSAPSiCorresponde($rowMovSalLinea->ID_EXPEDICION_SAP);

                //COMPRUEBO SI HAY UNA LINEA SIMILAR A LA QUE AGRUPAR LA LINEA QUE ESTOY TRANSFIRIENDO
                $GLOBALS["NotificaErrorPorEmail"] = "No";
                $rowLineaMovimientoSimilar        = $bd->VerRegRest("MOVIMIENTO_SALIDA_LINEA", "LINEA_ANULADA = 0 AND
                        BAJA = 0 AND
                        ID_MOVIMIENTO_SALIDA = $rowMovSalLinea->ID_MOVIMIENTO_SALIDA AND
                        ID_PEDIDO_SALIDA_LINEA = $rowMovSalLinea->ID_PEDIDO_SALIDA_LINEA AND
                        ID_UBICACION = $rowMovSalLinea->ID_UBICACION AND
                        ID_CONTENEDOR " . ($rowMovSalLinea->ID_CONTENEDOR == NULL ? 'IS NULL' : "= $rowMovSalLinea->ID_CONTENEDOR") . " AND
                        ID_MATERIAL_FISICO " . ($rowMovSalLinea->ID_MATERIAL_FISICO == NULL ? 'IS NULL' : "= $rowMovSalLinea->ID_MATERIAL_FISICO") . " AND
                        ID_ORDEN_TRABAJO_MOVIMIENTO " . ($rowMovSalLinea->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? 'IS NULL' : "= $rowMovSalLinea->ID_ORDEN_TRABAJO_MOVIMIENTO") . " AND
                        ID_INCIDENCIA_CALIDAD " . ($rowMovSalLinea->ID_INCIDENCIA_CALIDAD == NULL ? 'IS NULL' : "= $rowMovSalLinea->ID_INCIDENCIA_CALIDAD") . " AND
                        ID_TIPO_BLOQUEO " . ($rowMovSalLinea->ID_TIPO_BLOQUEO == NULL ? 'IS NULL' : "= $rowMovSalLinea->ID_TIPO_BLOQUEO") . " AND
                        ID_EXPEDICION IS NULL AND
                        EXPEDICION_SAP = '' AND
                        ID_EXPEDICION_SAP IS NULL AND
                        ID_ALBARAN IS NULL AND
                        ID_ALBARAN_LINEA IS NULL AND
                        ESTADO = 'Pendiente de Expedir' AND
                        ID_MOVIMIENTO_SALIDA_LINEA <> " . $rowMovSalLinea->ID_MOVIMIENTO_SALIDA_LINEA, "No");
                unset($GLOBALS["NotificaErrorPorEmail"]);

                //ACCIONES EN FUNCION DE SI EXISTE UNA LINEA SIMILAR O NO
                if ($rowLineaMovimientoSimilar != false):   //EXISTE UNA LINEA SIMILAR

                    //INCREMENTO LA CANTIDAD EN LA LINEA SIMILAR
                    $sqlUpdate = "UPDATE MOVIMIENTO_SALIDA_LINEA SET
                                    CANTIDAD = CANTIDAD + $rowMovSalLinea->CANTIDAD
                                    WHERE ID_MOVIMIENTO_SALIDA_LINEA = $rowLineaMovimientoSimilar->ID_MOVIMIENTO_SALIDA_LINEA";//echo($sqlUpdate."<hr>");
                    $bd->ExecSQL($sqlUpdate);

                    //DOY DE BAJA LA LINEA ACTUAL
                    $sqlUpdate = "UPDATE MOVIMIENTO_SALIDA_LINEA SET
                                    BAJA = 1
                                    , ID_CONTENEDOR = NULL
                                    , ID_CONTENEDOR_LINEA = NULL
                                    , ID_BULTO = NULL
                                    , ID_BULTO_LINEA = NULL
                                    WHERE ID_MOVIMIENTO_SALIDA_LINEA = $rowMovSalLinea->ID_MOVIMIENTO_SALIDA_LINEA";//echo($sqlUpdate."<hr>");
                    $bd->ExecSQL($sqlUpdate);

                else:   //NO EXISTE UNA LINEA SIMILAR

                    //NO HACER NADA

                endif;
                //FIN ACCIONES EN FUNCION DE SI EXISTE UNA LINEA SIMILAR O NO
            endif;
        endforeach;
        //FIN BUCLE LINEAS A DESASIGNAR

        /************************************ HABILITO TRIGGERS IMPLICADOS ************************************/
        $sqlHabilitarTriggers = "SET @Trigger_MOVIMIENTO_SALIDA_LINEA_UPDATE_Deshabilitado = NULL";
        $bd->ExecSQL($sqlHabilitarTriggers);
        /********************************** FIN HABILITO TRIGGERS IMPLICADOS **********************************/

        //ACTUALIZAR VERSION/BULTOS DE LA ORDEN DE TRANSPORTE
        $this->actualizar_version_bultos_orden_transporte($rowOrdenTransporte->ID_EXPEDICION);

        //ACTUALIZO EL ESTADO DE LA ORDEN DE RECOGIDA
        $this->actualizar_estado_orden_transporte($rowOrdenTransporte->ID_EXPEDICION);

        //ACTUALIZO EL TIPO Y SUBTIPO DE LA ORDEN DE RECOGIDA
        $this->actualizar_tipo_orden_transporte($rowOrdenTransporte->ID_EXPEDICION);

        //ACTUALIZO LOS CAMPOS ADR Y NACIONAL
        $this->calcularAmbitoYComunidadExpedicion($rowOrdenTransporte->ID_EXPEDICION);
        $this->calcularADRExpedicion($rowOrdenTransporte->ID_EXPEDICION);

        // LOG MOVIMIENTOS
        $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Expedición", $rowOrdenTransporte->ID_EXPEDICION, "Desasignacion lineas de orden de transporte");
    }

    /**
     * @param $idPedidoSalidaLinea LINEA DE PEDIDO A DESBLOQUEAR
     * @return array ARRAY CON EL RESULTADO SAP DEL DESBLOQUEO DE LA LINEA DE PEDIDO
     */
    function desbloquearLineaPedidoSalida($idPedidoSalidaLinea)
    {
        global $bd;
        global $sap;

        //BUSCO LA LINEA DEL PEDIDO SE SALIDA
        $rowPedidoSalidaLinea = $bd->VerReg("PEDIDO_SALIDA_LINEA", "ID_PEDIDO_SALIDA_LINEA", $idPedidoSalidaLinea);

        //BUSCO EL PEDIDO DE SALIDA
        $rowPedidoSalida = $bd->VerReg("PEDIDO_SALIDA", "ID_PEDIDO_SALIDA", $rowPedidoSalidaLinea->ID_PEDIDO_SALIDA);

        unset($objLinea);   //VACIO EL OBJETO LINEA
        unset($arrLineas);  //VACIO EL ARRAY A ENVIAR
        $objLinea->PEDIDO    = $rowPedidoSalida->PEDIDO_SAP;    // Número de pedido cuyas posiciones vamos a desbloquear (Obligatorio)
        $objLinea->POSICION  = $rowPedidoSalidaLinea->LINEA_PEDIDO_SAP;    // Línea de pedido a desbloquear (Obligatorio)
        $objLinea->BLOQUEADO = '';    // (vacio=NO, X=SI)
        $arrLineas[]         = $objLinea;

        //INFORMO A SAP DEL DESBLOQUEO
        $resultado = $sap->bloqueoLineasPedido($arrLineas);

        //DEVUELVO EL RESULTADO DE LA LLAMADA A SAP
        return $resultado;
    }

    /**
     * @param $idPedidoSalidaLinea LINEA DE PEDIDO SOBRE LA QUE HACER EL SPLIT
     * @param $cantidad CANTIDAD POR LA QUE HACER EL SPLIT
     * @return array ARRAY CON EL RESULTADO SAP DEL SPLIT DE LA LINEA DE PEDIDO
     */
    function splitLineaPedidoSalida($idPedidoSalidaLinea, $cantidad)
    {
        global $bd;
        global $sap;

        //BUSCO LA LINEA DEL PEDIDO SE SALIDA
        $rowPedidoSalidaLinea = $bd->VerReg("PEDIDO_SALIDA_LINEA", "ID_PEDIDO_SALIDA_LINEA", $idPedidoSalidaLinea);

        //BUSCO EL PEDIDO DE SALIDA
        $rowPedidoSalida = $bd->VerReg("PEDIDO_SALIDA", "ID_PEDIDO_SALIDA", $rowPedidoSalidaLinea->ID_PEDIDO_SALIDA);

        //VACIO EL ARRAY A ENVIAR
        unset($arrSplitPedidos);

        //AÑADO LA LINEA AL ARRAY DE SPLIT A REALIZAR
        $arrSplitPedidos[$rowPedidoSalidaLinea->ID_PEDIDO_SALIDA_LINEA] = $cantidad;

        //INFORMO A SAP DEL SPLIT
        $resultado = $sap->SplitPedido($arrSplitPedidos, "Salida");

        //DEVUELVO EL RESULTADO DE LA LLAMADA A SAP
        return $resultado;
    }

    /**
     * @param $idOrdenTransporte ORDEN DE RECOGIDA
     * @return array ARRAY DEVUELTO CON LOS ERRORES Y LA LISTA DE EXPEDICIONES SAP A TRANSMITIR A SAP
     * FUNCION UTLIZADA PARA GENERAR LOS ALBARANES EN CASO DE NO ESTAR GENERADOS Y ASIGNAR A LAS LINEAS LAS EXPEDICIONES SAP CORRESPONDIENTES
     */
    function generar_albaranes_y_asignar_expediciones_SAP($idOrdenTransporte)
    {
        global $bd;
        global $auxiliar;
        global $administrador;
        global $html;
        global $albaran;
        global $exp_SAP;

        //BUSCO LA ORDEN DE RECOGIDA
        $rowOrdenTransporte = $bd->VerReg("EXPEDICION", "ID_EXPEDICION", $idOrdenTransporte, "No");

        //BLOQUEO LA ORDEN DE RECOGIDA
        $sqlOrdenRecogida = "SELECT * 
                             FROM EXPEDICION 
                             WHERE ID_EXPEDICION = $idOrdenTransporte FOR UPDATE";
        $bd->ExecSQL($sqlOrdenRecogida);

        //CALCULO EL NUMERO DE BULTOS DE LA ORDEN DE RECOGIDA
        $numBultos = $bd->NumRegsTabla("BULTO", "ID_EXPEDICION = $rowOrdenTransporte->ID_EXPEDICION");

        //COMPRUEBO QUE LA EXPEDICION ESTE EN ESTADO CORRECTO
        $html->PagErrorCondicionado((($rowOrdenTransporte->ESTADO != 'Creada') && ($rowOrdenTransporte->ESTADO != 'En Transmision')), "==", true, "EstadoExpedicionIncorrecto");

        //COMPRUEBO QUE LA RECOGIDA ESTE ASIGNADA A UNA ORDEN DE TRANSPORTE
        $html->PagErrorCondicionado($rowOrdenTransporte->ID_ORDEN_TRANSPORTE, "==", NULL, "OrdenRecogidaSinOrdenTransporte");

        //SI NO HAY ALBARANES GENERADOS, LOS GENERO
        $num = $bd->NumRegsTabla("ALBARAN", "ID_EXPEDICION = $rowOrdenTransporte->ID_EXPEDICION AND BAJA = 0");
        if ($num == 0):
            //ASIGNO EL ALBARAN A LAS LINEAS
            $albaran->generar_albaran_salida($rowOrdenTransporte->ID_EXPEDICION);
        endif;

        //ARRAY DE EXPEDICIONES SAP Y ALBARANES GENERADOS
        $arrExpSAP = array();

        //LISTA PARA SABER QUE LINEAS DE MOVIMIENTO SALIDA SE VAN A EXPEDIR
        $listaLineasAExpedir = "";

        //LISTA PARA SABER QUE LINEAS DE PEDIDO SALIDA SE VAN A EXPEDIR, SOBRE ESTA LISTA HABRA QUE ENVIAR EL BLOQUEO O DESBLOQUEO DE LINEAS
        $listaLineasPedidoAExpedir = "";

        //VARIABLE PARA SABER LAS DUPLAS MOVIMIENTO-PEDIDO o BULTO_PEDIDO QUE NO SE PUEDEN EXPEDIR
        if ($rowOrdenTransporte->VERSION == 'Tercera'):
            $arrMovimientoBultoPedidosNoExpedir = array();
        endif;

        //ARRAY PARA GUARDARME LOS ALBARANES MODIFICADOS
        $arrAlbaranModificado = array();

        //RECUPERO LA VARIABLE GLOBAL DE TEXTO ERROR
        global $strError;
        $strError = "";

        $sqlLineas    = "SELECT *
                            FROM MOVIMIENTO_SALIDA_LINEA
                            WHERE ID_EXPEDICION = $rowOrdenTransporte->ID_EXPEDICION AND ID_ALBARAN IS NULL AND LINEA_ANULADA = 0 AND BAJA = 0 AND ESTADO = 'Pendiente de Expedir' FOR UPDATE";
        $resultLineas = $bd->ExecSQL($sqlLineas);
        while ($rowLinea = $bd->SigReg($resultLineas)): //RECORRO LA SELECCION DE LINEAS PARA ASIGNARLE ALBARAN EN CASO DE NO TENERLO YA ASIGNADO
            //BUSCO LA LINEA DEL PEDIDO DE SALIDA
            $NotificaErrorPorEmail = "No";
            $rowPedSalLinea        = $bd->VerReg("PEDIDO_SALIDA_LINEA", "ID_PEDIDO_SALIDA_LINEA", $rowLinea->ID_PEDIDO_SALIDA_LINEA, "No");
            unset($NotificaErrorPorEmail);

            //SI LA LINEA NO FORMA PARTE DE NINGUN ALBARAN, LA ASIGNO AL ALBARAN CORRESPONDIENTE, ESTE CASO SE DA AL CANCELAR UNA EXPEDICION SAP QUE FORMA PARTE CON OTRAS DE UNA EXPEDICION SGA
            $idAlbaranModificado = $albaran->anadir_linea($rowOrdenTransporte->ID_EXPEDICION, $rowLinea, $rowPedSalLinea);

            //INCLUYO EL ALBARAN EN EL ARRAY DE ALBARANES A REORDENAR
            $arrAlbaranModificado[$idAlbaranModificado] = 1;
        endwhile;
        //FIN RECORRO LA SELECCION DE LINEAS PARA ASIGNARLE ALBARAN EN CASO DE NO TENERLO YA ASIGNADO

        //RECORRO EL ARRAY DE ALBARANES A REORDENAR
        foreach ($arrAlbaranModificado as $idAlbaranReordenar => $valor):
            //REORDENO LAS LINEAS DEL ALBARAN
            $albaran->reordenar_lineas_albaran($idAlbaranReordenar);
        endforeach;

        //RECORRO LA SELECCION DE LINEAS
        $sqlLineas    = "SELECT *
                            FROM MOVIMIENTO_SALIDA_LINEA
                            WHERE ID_EXPEDICION = $rowOrdenTransporte->ID_EXPEDICION AND LINEA_ANULADA = 0 AND BAJA = 0 AND ESTADO = 'Pendiente de Expedir' FOR UPDATE";
        $resultLineas = $bd->ExecSQL($sqlLineas);

        /************************************ DESHABILITO TRIGGERS IMPLICADOS ************************************/
        $sqlDeshabilitarTriggers = "SET @Trigger_MOVIMIENTO_SALIDA_LINEA_UPDATE_Deshabilitado = 1";
        $bd->ExecSQL($sqlDeshabilitarTriggers);
        /********************************** FIN DESHABILITO TRIGGERS IMPLICADOS **********************************/
        while ($rowLinea = $bd->SigReg($resultLineas))://RECORRO LA SELECCION DE LINEAS
            //ACTUALIZAMOS VARIABLE PARA SABER SI SE PRODUCE UN ERROR DE LINEA
            $hayErrorLinea = false;

            //BUSCO EL MATERIAL
            $rowMat = $bd->VerReg("MATERIAL", "ID_MATERIAL", $rowLinea->ID_MATERIAL, "No");

            //BUSCO EL PEDIDO DE SALIDA
            $NotificaErrorPorEmail = "No";
            $rowPedSal             = $bd->VerReg("PEDIDO_SALIDA", "ID_PEDIDO_SALIDA", $rowLinea->ID_PEDIDO_SALIDA, "No");
            unset($NotificaErrorPorEmail);

            //BUSCO LA UBICACION DE ORIGEN
            $NotificaErrorPorEmail = "No";
            $rowUbiOrigen          = $bd->VerReg("UBICACION", "ID_UBICACION", $rowLinea->ID_UBICACION, "No");
            unset($NotificaErrorPorEmail);

            //BUSCO EL ALMACEN DE ORIGEN
            $NotificaErrorPorEmail = "No";
            $rowAlmOrigen          = $bd->VerReg("ALMACEN", "ID_ALMACEN", $rowUbiOrigen->ID_ALMACEN, "No");
            unset($NotificaErrorPorEmail);

            //BUSCO EL ALBARAN DE LA LINEA
            $rowAlbaran = $bd->VerReg("ALBARAN", "ID_ALBARAN", $rowLinea->ID_ALBARAN);

            //BUSCO LA LINEA DEL ALBARAN DE LA LINEA
            $rowAlbaranLinea = $bd->VerReg("ALBARAN_LINEA", "ID_ALBARAN_LINEA", $rowLinea->ID_ALBARAN_LINEA);

            /************************************************* COMPROBACIONES DE LA LINEA *************************************************/
            //COMPRUEBO QUE TENGA PERMISOS DE ESCRITURA EN EL ALMACEN (SI FALLA UNA LINEA CANCELO TODA LA OPERACION)
            $html->PagErrorCondicionado($administrador->comprobarAlmacenPermiso($rowLinea->ID_ALMACEN, "Escritura"), "==", false, "SinPermisosSubzona");

            //COMPRUEBO QUE NO ESTE DADA DE BAJA
            if ($rowLinea->BAJA == 1):
                $hayErrorLinea = true;
                $strError      = $strError . $auxiliar->traduce("La línea número", $administrador->ID_IDIOMA) . " " . $rowAlbaranLinea->NUMERO_LINEA . " " . $auxiliar->traduce("del albarán", $administrador->ID_IDIOMA) . " " . $rowAlbaran->ID_ALBARAN . " " . $auxiliar->traduce("está dada de baja", $administrador->ID_IDIOMA) . ".<br>";
            endif;

            //COMPRUEBO QUE NO ESTE ANULADA
            if ($rowLinea->LINEA_ANULADA == 1):
                $hayErrorLinea = true;
                $strError      = $strError . $auxiliar->traduce("La línea número", $administrador->ID_IDIOMA) . " " . $rowAlbaranLinea->NUMERO_LINEA . " " . $auxiliar->traduce("del albarán", $administrador->ID_IDIOMA) . " " . $rowAlbaran->ID_ALBARAN . " " . $auxiliar->traduce("está anulada", $administrador->ID_IDIOMA) . ".<br>";
            endif;

            //COMPRUEBO QUE NO ESTE EN ESTADO PENDIENTE DE EXPEDIR
            if ($rowLinea->ESTADO != "Pendiente de Expedir"):
                $hayErrorLinea = true;
                $strError      = $strError . $auxiliar->traduce("La línea número", $administrador->ID_IDIOMA) . " " . $rowAlbaranLinea->NUMERO_LINEA . " " . $auxiliar->traduce("del albarán", $administrador->ID_IDIOMA) . " " . $rowAlbaran->ID_ALBARAN . " " . $auxiliar->traduce("no está en estado pendiente de expedir", $administrador->ID_IDIOMA) . ".<br>";
            endif;

            //COMPRUEBO QUE NO ESTE ENVIADA A SAP
            if ($rowLinea->ENVIADO_SAP == 1):
                $hayErrorLinea = true;
                $strError      = $strError . $auxiliar->traduce("La línea número", $administrador->ID_IDIOMA) . " " . $rowAlbaranLinea->NUMERO_LINEA . " " . $auxiliar->traduce("del albarán", $administrador->ID_IDIOMA) . " " . $rowAlbaran->ID_ALBARAN . " " . $auxiliar->traduce("ya está enviada a SAP", $administrador->ID_IDIOMA) . ".<br>";
            endif;

            //COMPRUEBO QUE TENGA EXPEDICION SGA
            if ($rowLinea->ID_EXPEDICION != $rowOrdenTransporte->ID_EXPEDICION):
                $hayErrorLinea = true;
                $strError      = $strError . $auxiliar->traduce("La línea número", $administrador->ID_IDIOMA) . " " . $rowAlbaranLinea->NUMERO_LINEA . " " . $auxiliar->traduce("del albarán", $administrador->ID_IDIOMA) . " " . $rowAlbaran->ID_ALBARAN . " " . $auxiliar->traduce("tiene asignada una expedicion SGA incorrecta", $administrador->ID_IDIOMA) . ".<br>";
            endif;

            //COMPRUEBO QUE EL ORIGEN DE LA LINEA ES CORRECTO
            if ($rowOrdenTransporte->ID_CENTRO_FISICO != $rowAlmOrigen->ID_CENTRO_FISICO):
                $hayErrorLinea = true;
                $strError      = $strError . $auxiliar->traduce("La línea número", $administrador->ID_IDIOMA) . " " . $rowAlbaranLinea->NUMERO_LINEA . " " . $auxiliar->traduce("del albarán", $administrador->ID_IDIOMA) . " " . $rowAlbaran->ID_ALBARAN . " " . $auxiliar->traduce("tiene asignada un centro físico diferente al del albarán", $administrador->ID_IDIOMA) . ".<br>";
            endif;

            //BUSCO EL MOVIMIENTO DE SALIDA
            $rowMovimientoSalida = $bd->VerReg("MOVIMIENTO_SALIDA", "ID_MOVIMIENTO_SALIDA", $rowLinea->ID_MOVIMIENTO_SALIDA);

            //BUSCO LA ORDEN DE PREPARACION
            $rowOrdenPreparacion = $bd->VerReg("ORDEN_PREPARACION", "ID_ORDEN_PREPARACION", $rowMovimientoSalida->ID_ORDEN_PREPARACION);
            /*********************************************** FIN COMPROBACIONES DE LA LINEA ***********************************************/

            if (($rowPedSal->TIPO_PEDIDO == "Traslado") || ($rowPedSal->TIPO_PEDIDO == "Traspaso Entre Almacenes Material Estropeado") || ($rowPedSal->TIPO_PEDIDO == "Intra Centro Fisico") || ($rowPedSal->TIPO_PEDIDO == "Interno Gama") || ($rowPedSal->TIPO_PEDIDO == "Traslados OM Construccion") || ($rowPedSal->TIPO_PEDIDO == "Pendientes de Ordenes Trabajo")):
                //BUSCO EL ALMACEN DE DESTINO
                $NotificaErrorPorEmail = "No";
                $rowAlmDestino         = $bd->VerReg("ALMACEN", "ID_ALMACEN", $rowLinea->ID_ALMACEN_DESTINO, "No");
                unset($NotificaErrorPorEmail);

                //BUSCO LA DUPLA MATERIAL ALMACEN
                $sqlMaterialAlmacen    = "SELECT * FROM MATERIAL_ALMACEN WHERE ID_MATERIAL = $rowLinea->ID_MATERIAL AND ID_ALMACEN = $rowLinea->ID_ALMACEN_DESTINO";
                $resultMaterialAlmacen = $bd->ExecSQL($sqlMaterialAlmacen, "No");
                if (($resultMaterialAlmacen == false) || ($bd->NumRegs($resultMaterialAlmacen) == 0)):
                    $hayErrorLinea = true;
                    $strError      = $strError . $auxiliar->traduce("No está definido el material", $administrador->ID_IDIOMA) . " " . $rowMat->REFERENCIA_SGA . " - " . ($administrador->ID_IDIOMA == "ESP" ? $rowMat->DESCRIPCION : $rowMat->DESCRIPCION_EN) . " " . $auxiliar->traduce("para el almacén", $administrador->ID_IDIOMA) . " " . $rowAlmDestino->REFERENCIA . " - " . $rowAlmDestino->NOMBRE . "<br>";
                endif;
            endif; //FIN TIPO PEDIDO TRASLADO

            //SI NO HAY ERROR DE LINEA LA AÑADO AL ARRAY DE EXPEDICIONES SAP, SINO A LA DUPLA MOVIMIENTOBULTO-PEDIDOS A NO EXPEDIR
            if ($hayErrorLinea == false):

                //AÑADO LA LINEA AL ARRAY DE EXPEDICIONES SAP SI LA RECOGIDA ES VERSION 'Tercera'
                if ($rowOrdenTransporte->VERSION == 'Tercera'):
                    if ($rowOrdenTransporte->CON_BULTOS == 0):
                        $arrExpSAP[$rowLinea->ID_MOVIMIENTO_SALIDA . "_" . $rowLinea->ID_PEDIDO_SALIDA][] = $rowLinea->ID_MOVIMIENTO_SALIDA_LINEA;
                    elseif ($rowOrdenTransporte->CON_BULTOS == 1):
                        $arrExpSAP[$rowLinea->ID_BULTO . "_" . $rowLinea->ID_PEDIDO_SALIDA][] = $rowLinea->ID_MOVIMIENTO_SALIDA_LINEA;
                    endif;
                endif;

                //AÑADO LA LINEA A LA LISTA DE LINEAS DE MOVIMIENTO SALIDA A EXPEDIR
                if ($listaLineasAExpedir == ""):
                    $listaLineasAExpedir = $rowLinea->ID_MOVIMIENTO_SALIDA_LINEA;
                else:
                    $listaLineasAExpedir = $listaLineasAExpedir . "," . $rowLinea->ID_MOVIMIENTO_SALIDA_LINEA;
                endif;

                //AÑADO LA LINEA DE PEDIDO A LA LISTA DE LINEAS DE PEDIDO SALIDA QUE SE ENVIARA EL BLOQUEO O DESBLOQUEO DE LINEA
                if ($listaLineasPedidoAExpedir == ""):
                    $listaLineasPedidoAExpedir = $rowLinea->ID_PEDIDO_SALIDA_LINEA;
                else:
                    $listaLineasPedidoAExpedir = $listaLineasPedidoAExpedir . "," . $rowLinea->ID_PEDIDO_SALIDA_LINEA;
                endif;

                //SI LA RECOGIDA ES DE VERSION 'Cuarta' ASIGNO YA LAS EXPEDICIONES SAP A LAS LINEAS
                if ($rowOrdenTransporte->VERSION == 'Cuarta'):
                    //BUSCO SI EXISTE UNA EXPEDICION SAP VALIDA PARA LA LINEA
                    $GLOBALS["NotificaErrorPorEmail"] = "No";
                    $rowExpedicionSAP                 = $bd->VerRegRest("EXPEDICION_SAP", "ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND ID_EXPEDICION = $rowOrdenTransporte->ID_EXPEDICION AND ID_MOVIMIENTO_SALIDA " . ($rowOrdenTransporte->CON_BULTOS == 0 ? "= $rowLinea->ID_MOVIMIENTO_SALIDA" : "IS NULL") . " AND ID_BULTO " . ($rowOrdenTransporte->CON_BULTOS == 0 ? "IS NULL" : "= $rowLinea->ID_BULTO") . " AND ID_ALBARAN = $rowLinea->ID_ALBARAN AND ID_PEDIDO_SALIDA = $rowLinea->ID_PEDIDO_SALIDA AND BAJA = 0", "No");
                    unset($GLOBALS["NotificaErrorPorEmail"]);

                    //SI NO EXISTE NINGUNA EXPEDICION SAP A LA QUE ASIGNAR LA LINEA LA CREAMOS
                    if ($rowExpedicionSAP == false):
                        //LA EXPEDICION SAP, EN SU VERSION CUARTA ES LA ORDEN DE TRANSPORTE SEGUIDA DEL IDENTIFICADOR DE EXPEDICION SAP
                        $sqlInsert = "INSERT INTO EXPEDICION_SAP SET
                                      ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE
                                      , ID_EXPEDICION = $rowOrdenTransporte->ID_EXPEDICION
                                      , ID_MOVIMIENTO_SALIDA = " . ($rowOrdenTransporte->CON_BULTOS == 0 ? $rowLinea->ID_MOVIMIENTO_SALIDA : "NULL") . "
                                      , ID_BULTO = " . ($rowOrdenTransporte->CON_BULTOS == 0 ? "NULL" : $rowLinea->ID_BULTO) . "
                                      , ID_ALBARAN = $rowLinea->ID_ALBARAN
                                      , ID_PEDIDO_SALIDA = $rowLinea->ID_PEDIDO_SALIDA";
                        $bd->ExecSQL($sqlInsert);
                        $idExpedicionSAP = $bd->IdAsignado();
                        $expedicionSAP   = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE . "_" . $idExpedicionSAP;
                    else:
                        $idExpedicionSAP = $rowExpedicionSAP->ID_EXPEDICION_SAP;
                        $expedicionSAP   = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE . "_" . $idExpedicionSAP;
                    endif;

                    //BUSCO LA LINEA DEL MOVIMIENTO DE SALIDA PREVIO A LA ACTUALIZACION
                    $rowLineaMovimientoSalidaPrevioActualizacion = $bd->VerReg("MOVIMIENTO_SALIDA_LINEA", "ID_MOVIMIENTO_SALIDA_LINEA", $rowLinea->ID_MOVIMIENTO_SALIDA_LINEA);

                    //ACTUALIZO LA EXPEDICION SAP DE LA LINEA DEL MOVIMIENTO
                    $sqlUpdate = "UPDATE MOVIMIENTO_SALIDA_LINEA SET
                                    EXPEDICION_SAP = '" . $expedicionSAP . "'
                                    , ID_EXPEDICION_SAP = $idExpedicionSAP
                                    WHERE ID_MOVIMIENTO_SALIDA_LINEA = $rowLinea->ID_MOVIMIENTO_SALIDA_LINEA";
                    $bd->ExecSQL($sqlUpdate);

                    //BUSCO LA LINEA DEL MOVIMIENTO DE SALIDA POSTERIOR A LA ACTUALIZACION
                    $rowLineaMovimientoSalidaPosteriorActualizacion = $bd->VerReg("MOVIMIENTO_SALIDA_LINEA", "ID_MOVIMIENTO_SALIDA_LINEA", $rowLinea->ID_MOVIMIENTO_SALIDA_LINEA);

                    // LOG MOVIMIENTOS
                    $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Mov. salida", $rowLinea->ID_MOVIMIENTO_SALIDA, "Asignar expedicion SAP a la linea de movimiento con ID: $rowLinea->ID_MOVIMIENTO_SALIDA_LINEA", "MOVIMIENTO_SALIDA_LINEA", $rowLineaMovimientoSalidaPrevioActualizacion, $rowLineaMovimientoSalidaPosteriorActualizacion);

                    //SI LA LINEA TENIA ID_EXPEDICION_SAP ASIGNADA LA DOY DE BAJA SI PUEDO
                    $exp_SAP->darDeBajaIdExpedicionSAPSiCorresponde($rowLinea->ID_EXPEDICION_SAP);

                    //AÑADO LA LINEA AL ARRAY DE EXPEDICIONES SAP
                    $arrExpSAP[] = $expedicionSAP;
                endif;

            else:

                //AÑADO LA DUPLA MOVIMIENTO BULTO-PEDIDO A LA LISTA DE LOS QUE NO SE EXPEDIRAN SI LA RECOGIDA ES VERSION 'Tercera'
                if ($rowOrdenTransporte->VERSION == 'Tercera'):
                    if ($rowOrdenTransporte->CON_BULTOS == 0):
                        $arrMovimientoBultoPedidosNoExpedir[] = $rowLinea->ID_MOVIMIENTO_SALIDA . "_" . $rowLinea->ID_PEDIDO_SALIDA;
                    elseif ($rowOrdenTransporte->CON_BULTOS == 1):
                        $arrMovimientoBultoPedidosNoExpedir[] = $rowLinea->ID_BULTO . "_" . $rowLinea->ID_PEDIDO_SALIDA;
                    endif;
                    $arrMovimientoBultoPedidosNoExpedir = array_unique( (array)$arrMovimientoBultoPedidosNoExpedir);
                endif;

            endif;
            //FIN SI NO HAY ERROR DE LINEA LA AÑADO AL ARRAY DE EXPEDICIONES SAP, SINO A LA DUPLA MOVIMIENTOBULTO-PEDIDOS A NO EXPEDIR (ESTO ULTIMO SOLO PARA RECOGIDAS DE VERSION 'Tercera')

        endwhile;
        //FIN RECORRO LA SELECCION DE LINEAS

        /************************************ HABILITO TRIGGERS IMPLICADOS ************************************/
        $sqlHabilitarTriggers = "SET @Trigger_MOVIMIENTO_SALIDA_LINEA_UPDATE_Deshabilitado = NULL";
        $bd->ExecSQL($sqlHabilitarTriggers);
        /********************************** FIN HABILITO TRIGGERS IMPLICADOS **********************************/

        //ACCIONES EN FUNCION DE LA VERSION
        if ($rowOrdenTransporte->VERSION == 'Tercera'):
            //BORRO LAS EXPEDICIONES SAP QUE NO SE PODRAN ENVIAR POR TENER FALLOS
            foreach ($arrExpSAP as $duplaMovimientoBultoPedido => $valores):
                if (in_array($duplaMovimientoBultoPedido, (array) $arrMovimientoBultoPedidosNoExpedir)):
                    unset($arrExpSAP[$duplaMovimientoBultoPedido]);
                endif;
            endforeach;

            /************************************ DESHABILITO TRIGGERS IMPLICADOS ************************************/
            $sqlDeshabilitarTriggers = "SET @Trigger_MOVIMIENTO_SALIDA_LINEA_UPDATE_Deshabilitado = 1";
            $bd->ExecSQL($sqlDeshabilitarTriggers);
            /********************************** FIN DESHABILITO TRIGGERS IMPLICADOS **********************************/

            //ASIGNO LA EXPEDICION SAP A LAS LINEAS
            foreach ($arrExpSAP as $duplaMovimientoBultoPedido => $valores):
                //EXTRAIGO LOS VALORES DE LA DUPLA
                $arrValoresDupla   = explode("_", (string)$duplaMovimientoBultoPedido);
                $idMovimientoBulto = $arrValoresDupla[0];
                $idPedido          = $arrValoresDupla[1];

                //CONFORMO EL VALOR DE LA EXPEDICION SAP
                $expedicionSAP = $exp_SAP->getExpedicionSAP($rowOrdenTransporte->ID_ORDEN_TRANSPORTE, $rowOrdenTransporte->ID_EXPEDICION, $idMovimientoBulto, $idPedido);

                foreach ($valores as $idLineaMovimiento):
                    $sqlUpdate = "UPDATE MOVIMIENTO_SALIDA_LINEA SET
                                    EXPEDICION_SAP = '" . $expedicionSAP . "'
                                    WHERE ID_MOVIMIENTO_SALIDA_LINEA = $idLineaMovimiento AND ID_MOVIMIENTO_SALIDA_LINEA IN ($listaLineasAExpedir)";
                    $bd->ExecSQL($sqlUpdate);
                endforeach; //FIN BUCLE LINEAS DIFERENTES
            endforeach; //FIN BUCLE EXPEDICIONES SAP DIFERENTES

            /************************************ HABILITO TRIGGERS IMPLICADOS ************************************/
            $sqlHabilitarTriggers = "SET @Trigger_MOVIMIENTO_SALIDA_LINEA_UPDATE_Deshabilitado = NULL";
            $bd->ExecSQL($sqlHabilitarTriggers);
            /********************************** FIN HABILITO TRIGGERS IMPLICADOS **********************************/

            //EXTRAIGO LAS DIFERENTES EXPEDICIONES SAP PARA DEVOLVERLO A LA FUNCION Y LAS TRANSMITA A SAP
            $arrListaExpSAP = array_keys( (array)$arrExpSAP);
        elseif ($rowOrdenTransporte->VERSION == 'Cuarta'):
            //ME QUEDO CON LOS VALORES UNICOS DE EXPEDICIONES SAP CREADAS
            $arrExpSAP = array_unique( (array)$arrExpSAP);

            //EXTRAIGO LAS DIFERENTES EXPEDICIONES SAP PARA DEVOLVERLO A LA FUNCION Y LAS TRANSMITA A SAP
            $arrListaExpSAP = $arrExpSAP;
        endif;
        //FIN ACCIONES E FUNCION DE LA VERSION

        //DECLARO UN ARRAY PARA DEVOLVER DATOS
        $arrDevuelto                        = array();
        $arrDevuelto['errores']             = $strError;
        $arrDevuelto['expediciones_SAP']    = implode(",", (array) $arrListaExpSAP);
        $arrDevuelto['lista_lineas_pedido'] = $listaLineasPedidoAExpedir;

        //DEVUELVO EL ARRAY
        return $arrDevuelto;
    }

    /**
     * @param $expedicionSAP EXPEDICION SAP A TRANSMITIR A SAP
     * @param $fechaTransmitir FECHA A TRANSMITIR A SAP
     * @return array ARRAY DEVUELTO CON LOS POSIBLES ERRORES
     * FUNCION UTILIZADA PARA TRANSMITIR A SAP UNA EXPEDICION SAP
     */
    function transmitir_expedicionSAP_a_SAP($version, $expedicionSAPConIdentificadores, $fechaTransmitir)
    {
        global $bd;
        global $auxiliar;
        global $administrador;
        global $html;
        global $pedido;
        global $sap;
        global $albaran;
        global $movimiento;
        global $necesidad;
        global $orden_preparacion;
        global $exp_SAP;
        global $mat;

        //RECUPERO LA VARIABLE GLOBAL DE TEXTO ERROR
        global $strError;
        $strError = "";

        //DESCOMPONGO LA EXPEDICION SAP
        $arrValoresExpedicionSAP = $exp_SAP->getIdentificadoresExpedicionSAPConIdentificadores($version, $expedicionSAPConIdentificadores);
        $idOrdenTransporte       = $arrValoresExpedicionSAP['ID_EXPEDICION'];
        if ($version == 'Tercera'):
            $idMovimientoBulto = $arrValoresExpedicionSAP['ID_MOVIMIENTO_BULTO'];
        elseif ($version == 'Cuarta'):
            $idExpedicionSAP = $arrValoresExpedicionSAP['ID_EXPEDICION_SAP'];
        else:
            $html->PagError("VersionNoValida");
        endif;
        $idPedido = $arrValoresExpedicionSAP['ID_PEDIDO_SALIDA'];

        //BUSCO LA ORDEN DE TRANSPORTE
        $NotificaErrorPorEmail = "No";
        $rowOrdenTransporte    = $bd->VerReg("EXPEDICION", "ID_EXPEDICION", $idOrdenTransporte, "No");
        $html->PagErrorCondicionado($rowOrdenTransporte, "==", false, "ExpedicionNoExiste");
        unset($NotificaErrorPorEmail);

        //VARIABLE PARA SABER SI SE PRODUCE UN ERROR EN LA EXPEDICION SAP
        $hayErrorExpedicionSAP = false;

        //VARIABLE PARA SABER SI SE PRODUCE UN ERROR EN LA TRANSMISION DE LA EXPEDICION SAP
        $hayErrorTransmisionExpedicionSAP = false;

        //ARRAY PARA GUARDAR LOS MOVIMIENTOS/BULTOS A ACTUALIZAR
        $arrPosiblesMovimientosBultosActualizar = array();

        //CONFORMO EL VALOR DE LA EXPEDICION SAP
        if ($version == 'Tercera'):
            $expedicionSAP = $exp_SAP->getExpedicionSAP($rowOrdenTransporte->ID_ORDEN_TRANSPORTE, $rowOrdenTransporte->ID_EXPEDICION, $idMovimientoBulto, $idPedido);
        elseif ($version == 'Cuarta'):
            $expedicionSAP = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE . "_" . $idExpedicionSAP;
        endif;

        //BUSCO EL PEDIDO DE SALIDA
        $rowPedSal = $bd->VerReg("PEDIDO_SALIDA", "ID_PEDIDO_SALIDA", $idPedido);

        //ARRAY DE OBJETOS A ACTUALIZAR DESPUES DEL WHILE
        $arrMovimientosActualizar  = array();
        $arrBultosActualizar       = array();
        $arrPedidosLineaActualizar = array();

        /************************************ DESHABILITO TRIGGERS IMPLICADOS ************************************/
        $sqlDeshabilitarTriggers = "SET @Trigger_MOVIMIENTO_SALIDA_LINEA_UPDATE_Deshabilitado = 1";
        $bd->ExecSQL($sqlDeshabilitarTriggers);
        /********************************** FIN DESHABILITO TRIGGERS IMPLICADOS **********************************/

        //BUSCO LAS LINEAS DE LA EXPEDICION SAP
        $sqlLineas    = "SELECT *
                        FROM MOVIMIENTO_SALIDA_LINEA
                        WHERE EXPEDICION_SAP = '" . $expedicionSAP . "' AND ESTADO = 'Pendiente de Expedir' AND LINEA_ANULADA = 0 AND BAJA = 0 FOR UPDATE ";
        $resultLineas = $bd->ExecSQL($sqlLineas);//exit($sqlLineas);
        //RECORRO LA SELECCION DE LINEAS DE LA EXPEDICION SAP
        while ($rowLinea = $bd->SigReg($resultLineas)):

            //PARA SIMULAR ERROR SAP
            //if ($rowLinea->ID_MOVIMIENTO_SALIDA_LINEA == 1958):
            //	continue;
            //endif;

            //BUSCO EL ALBARAN DE LA LINEA
            $rowAlbaran = $bd->VerReg("ALBARAN", "ID_ALBARAN", $rowLinea->ID_ALBARAN);

            //BUSCO LA LINEA DEL ALBARAN DE LA LINEA
            $rowAlbaranLinea = $bd->VerReg("ALBARAN_LINEA", "ID_ALBARAN_LINEA", $rowLinea->ID_ALBARAN_LINEA);

            //BUSCO EL MOVIMIENTO
            $rowMov = $bd->VerReg("MOVIMIENTO_SALIDA", "ID_MOVIMIENTO_SALIDA", $rowLinea->ID_MOVIMIENTO_SALIDA, "No");

            //ME GUARDO LAS LINEAS DE PEDIDO DE SALIDA IMPLICADAS
            $arrPedidosLineaActualizar[] = $rowLinea->ID_PEDIDO_SALIDA_LINEA;

            if (
                ($rowMov->TIPO_MOVIMIENTO == 'Venta') ||
                ($rowMov->TIPO_MOVIMIENTO == 'MaterialRechazadoAnuladoEnEntradasAProveedor') ||
                ($rowMov->TIPO_MOVIMIENTO == 'DevolucionNoEstropeadoAProveedor') ||
                ($rowMov->TIPO_MOVIMIENTO == 'ComponentesAProveedor') ||
                ($rowMov->TIPO_MOVIMIENTO == 'TraspasoEntreAlmacenesNoEstropeado') ||
                ($rowMov->TIPO_MOVIMIENTO == 'LogisticaInversaConPreparacion') ||
                ($rowMov->TIPO_MOVIMIENTO == 'IntraCentroFisico') ||
                ($rowMov->TIPO_MOVIMIENTO == 'InternoGama') ||
                ($rowMov->TIPO_MOVIMIENTO == 'TraspasoEntreAlmacenesEstropeado') ||
                ($rowMov->TIPO_MOVIMIENTO == 'TrasladoOMConstruccion') ||
                ($rowMov->TIPO_MOVIMIENTO == 'MaterialEstropeadoAProveedor')
            ):
                $estado = 'Transmitido a SAP';
            else:
                $html->PagError("ErrorTipoMovimiento");
            endif;

            //COMPRUEBO QUE NO EXISTA OTRA TRANSFERENCIA DE TIPO EMBARQUE ACTIVA CONTRA LA MISMA LINEA DE MOVIMIENTO DE SALIDA
            $num = $bd->NumRegsTabla("MOVIMIENTO_TRANSFERENCIA", "TIPO = 'Embarque' AND BAJA = 0 AND ID_MOVIMIENTO_SALIDA_LINEA = $rowLinea->ID_MOVIMIENTO_SALIDA_LINEA");
            if ($num > 0):
                $hayErrorExpedicionSAP = true;
                $strError              = $strError . $auxiliar->traduce("La línea número", $administrador->ID_IDIOMA) . " " . $rowAlbaranLinea->NUMERO_LINEA . " " . $auxiliar->traduce("del albarán", $administrador->ID_IDIOMA) . " " . $rowAlbaran->ID_ALBARAN . " " . $auxiliar->traduce("ya ha sido transferida a SAP", $administrador->ID_IDIOMA) . ".<br>";
            endif;

            //RECUPERO DE NUEVO LA LINEA DEL MOVIMIENTO DE SALIDA PARA COMPROBAR QUE NADIE HA MODIFICADO LA LINEA EN OTRO PROCESO
            $rowLineaMovimientoActualizada = $bd->VerReg("MOVIMIENTO_SALIDA_LINEA", "ID_MOVIMIENTO_SALIDA_LINEA", $rowLinea->ID_MOVIMIENTO_SALIDA_LINEA);
            if ($rowLineaMovimientoActualizada->ESTADO != "Pendiente de Expedir"):
                $hayErrorExpedicionSAP = true;
                $strError              = $strError . $auxiliar->traduce("La línea número", $administrador->ID_IDIOMA) . " " . $rowAlbaranLinea->NUMERO_LINEA . " " . $auxiliar->traduce("del albarán", $administrador->ID_IDIOMA) . " " . $rowAlbaran->ID_ALBARAN . " " . $auxiliar->traduce("no está en estado pendiente de expedir", $administrador->ID_IDIOMA) . ".<br>";
            endif;

            //ACTUALIZO LA LINEA DEL MOVIMIENTO DE SALIDA
            $sqlUpdate = "UPDATE MOVIMIENTO_SALIDA_LINEA SET
                            ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                            , ESTADO = '" . $estado . "'
                            , FECHA_TRANSMISION_A_SAP_TEORICA = '" . $fechaTransmitir . "'
                            WHERE ID_MOVIMIENTO_SALIDA_LINEA = $rowLinea->ID_MOVIMIENTO_SALIDA_LINEA";
            $bd->ExecSQL($sqlUpdate);

            //ACTUALIZO LA CABECERA DEL MOVIMIENTO O DEL BULTO
            if ($rowOrdenTransporte->CON_BULTOS == 0):
                //$arrPosiblesMovimientosBultosActualizar[] = $rowLinea->ID_MOVIMIENTO_SALIDA;
                $arrMovimientosActualizar[] = $rowLinea->ID_MOVIMIENTO_SALIDA;
            elseif ($rowOrdenTransporte->CON_BULTOS == 1):
                //$arrPosiblesMovimientosBultosActualizar[] = $rowLinea->ID_BULTO;
                $arrBultosActualizar[] = $rowLinea->ID_BULTO;
            endif;

            //BUSCO LA UBICACION DE DONDE SE MOVIO EL MATERIAL
            $rowUbiDesubicacion = $bd->VerReg("UBICACION", "ID_UBICACION", $rowLinea->ID_UBICACION);

            //BUSCO LA UBICACION DE SALIDA DEL ALMACEN EN EL QUE ESTOY
            $rowUbiSM = $bd->VerRegRest("UBICACION", "ID_ALMACEN = $rowUbiDesubicacion->ID_ALMACEN AND TIPO_UBICACION = 'Salida' AND BAJA = 0", "No");

            //COMPRUEBO QUE EXISTE UNA UBICACION DE SALIDA DE MATERIAL EN EL ALMACEN QUE ESTOY
            if ($rowUbiSM == false):
                $hayErrorExpedicionSAP = true;
                //BUSCO EL ALMACEN DE LA UBICACION DE SALIDA
                $rowAlmacenSalida = $bd->VerReg("ALMACEN", "ID_ALMACEN", $rowUbiDesubicacion->ID_ALMACEN);
                $strError         = $strError . $auxiliar->traduce("La línea número", $administrador->ID_IDIOMA) . " " . $rowAlbaranLinea->NUMERO_LINEA . " " . $auxiliar->traduce("del albarán", $administrador->ID_IDIOMA) . " " . $rowAlbaran->ID_ALBARAN . " " . $auxiliar->traduce("no tiene asignada una ubicación de salida en el almacén", $administrador->ID_IDIOMA) . " $rowAlmacenSalida->REFERENCIA - $rowAlmacenSalida->NOMBRE.<br>";
            endif;

            //BUSCO LA UBICACION DE EMBARQUE DEL ALMACEN EN EL QUE ESTOY
            $rowUbiEmbarque = $bd->VerRegRest("UBICACION", "ID_ALMACEN = $rowUbiDesubicacion->ID_ALMACEN AND TIPO_UBICACION = 'Embarque'", "No");//var_dump($rowUbiEmbarque);exit;

            //COMPRUEBO QUE EXISTE UNA UBICACION DE EMBARQUE DE MATERIAL EN EL ALMACEN QUE ESTOY
            if ($rowUbiEmbarque == false):
                $hayErrorExpedicionSAP = true;
                //BUSCO EL ALMACEN DE LA UBICACION DE EMBARQUE
                $rowAlmacenEmbarque = $bd->VerReg("ALMACEN", "ID_ALMACEN", $rowUbiDesubicacion->ID_ALMACEN);
                $strError           = $strError . $auxiliar->traduce("La línea número", $administrador->ID_IDIOMA) . " " . $rowAlbaranLinea->NUMERO_LINEA . " " . $auxiliar->traduce("del albarán", $administrador->ID_IDIOMA) . " " . $rowAlbaran->ID_ALBARAN . " " . $auxiliar->traduce("no tiene asignada una ubicación de embarque en el almacén", $administrador->ID_IDIOMA) . " $rowAlmacenEmbarque->REFERENCIA - $rowAlmacenEmbarque->NOMBRE.<br>";
            endif;

            //BUSCO EL MATERIAL UBICACION DE SM
            $rowMatUbi = $bd->VerRegRest("MATERIAL_UBICACION", "ID_MATERIAL = $rowLinea->ID_MATERIAL AND ID_UBICACION = $rowUbiSM->ID_UBICACION AND ID_MATERIAL_FISICO " . ($rowLinea->ID_MATERIAL_FISICO == NULL ? 'IS NULL' : "= $rowLinea->ID_MATERIAL_FISICO") . " AND ID_TIPO_BLOQUEO " . ($rowLinea->ID_TIPO_BLOQUEO == NULL ? 'IS NULL' : "= $rowLinea->ID_TIPO_BLOQUEO") . " AND ID_ORDEN_TRABAJO_MOVIMIENTO " . ($rowLinea->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? 'IS NULL' : "= $rowLinea->ID_ORDEN_TRABAJO_MOVIMIENTO") . " AND ID_INCIDENCIA_CALIDAD " . ($rowLinea->ID_INCIDENCIA_CALIDAD == NULL ? 'IS NULL' : "= $rowLinea->ID_INCIDENCIA_CALIDAD"), "No");

            //SI NO EXISTE MATERIAL UBICACION MUESTRO UN ERROR
            if ($rowMatUbi == false):
                $hayErrorExpedicionSAP = true;
                $strError              = $strError . $auxiliar->traduce("No hay suficiente cantidad de material para servir la línea número", $administrador->ID_IDIOMA) . " " . $rowAlbaranLinea->NUMERO_LINEA . " " . $auxiliar->traduce("del albarán", $administrador->ID_IDIOMA) . " " . $rowAlbaran->ID_ALBARAN . ".<br>";
            endif;

            //COMPRUEBO QUE HAYA STOCK SUFICIENTE
            if (($rowMatUbi != false) && ($rowMatUbi->STOCK_TOTAL < $rowLinea->CANTIDAD)):
                $hayErrorExpedicionSAP = true;
                $strError              = $strError . $auxiliar->traduce("No hay suficiente cantidad de material para servir la línea número", $administrador->ID_IDIOMA) . " " . $rowAlbaranLinea->NUMERO_LINEA . " " . $auxiliar->traduce("del albarán", $administrador->ID_IDIOMA) . " " . $rowAlbaran->ID_ALBARAN . ".<br>";
            endif;

            if ($hayErrorExpedicionSAP == false):
                //CREO UNA TRANSFERENCIA AUTOMATICA DE SM A EMBARQUE DE TIPO 'Embarque'
                $sqlInsert = "INSERT INTO MOVIMIENTO_TRANSFERENCIA SET
                                ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                , ID_MATERIAL = $rowLinea->ID_MATERIAL
                                , ID_MATERIAL_FISICO = " . ($rowLinea->ID_MATERIAL_FISICO == NULL ? 'NULL' : "$rowLinea->ID_MATERIAL_FISICO") . "
                                , ID_UBICACION_ORIGEN = $rowUbiSM->ID_UBICACION
                                , ID_UBICACION_DESTINO = $rowUbiEmbarque->ID_UBICACION
                                , ID_MOVIMIENTO_SALIDA = $rowLinea->ID_MOVIMIENTO_SALIDA
                                , ID_MOVIMIENTO_SALIDA_LINEA = $rowLinea->ID_MOVIMIENTO_SALIDA_LINEA
                                , ID_ORDEN_PREPARACION = $rowMov->ID_ORDEN_PREPARACION
                                , ID_ORDEN_TRABAJO_MOVIMIENTO = " . ($rowLinea->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? 'NULL' : "$rowLinea->ID_ORDEN_TRABAJO_MOVIMIENTO") . "
                                , ID_INCIDENCIA_CALIDAD = " . ($rowLinea->ID_INCIDENCIA_CALIDAD == NULL ? 'NULL' : "$rowLinea->ID_INCIDENCIA_CALIDAD") . "
                                , CANTIDAD = $rowLinea->CANTIDAD
                                , FECHA = '" . date("Y-m-d H:i:s") . "'
                                , TIPO = 'Embarque'
                                , STOCK_OK = " . ($rowLinea->ID_TIPO_BLOQUEO == NULL ? "$rowLinea->CANTIDAD" : 0) . "
                                , STOCK_BLOQUEADO = " . ($rowLinea->ID_TIPO_BLOQUEO == NULL ? 0 : "$rowLinea->CANTIDAD") . "
                                , ID_TIPO_BLOQUEO = " . ($rowLinea->ID_TIPO_BLOQUEO == NULL ? 'NULL' : "$rowLinea->ID_TIPO_BLOQUEO");//echo($sqlInsert."<hr>");
                $bd->ExecSQL($sqlInsert);

                //DECREMENTO MATERIAL UBICACION (SM) (SI ES DE CONSTRUCCION EVITAMOS QUE ENTRE EN EL EL PROCESO DE INTEGRIDAD DE MATERIAL FISICO, EN MATERIAL UBICACION SI DEBERIA ENTRAR)
                $sqlUpdate = "UPDATE MATERIAL_UBICACION SET
                                STOCK_TOTAL = STOCK_TOTAL - $rowLinea->CANTIDAD
                                , STOCK_OK = STOCK_OK - " . ($rowLinea->ID_TIPO_BLOQUEO == NULL ? "$rowLinea->CANTIDAD" : 0)
                    . ($rowMov->TIPO_MOVIMIENTO == 'TrasladoOMConstruccion' ? ", PENDIENTE_REVISAR_MATERIAL_FISICO_UBICACION = 2, PENDIENTE_REVISAR_MATERIAL_UBICACION_TIPO_BLOQUEO = 1" : "") . "
                                , STOCK_BLOQUEADO = STOCK_BLOQUEADO - " . ($rowLinea->ID_TIPO_BLOQUEO == NULL ? 0 : "$rowLinea->CANTIDAD") . "
                                WHERE ID_MATERIAL_UBICACION = $rowMatUbi->ID_MATERIAL_UBICACION";
                $bd->ExecSQL($sqlUpdate);

                //BUSCO EL MATERIAL UBICACION DE EMBARQUE
                $rowMatUbiEmbarque = $bd->VerRegRest("MATERIAL_UBICACION", "ID_MATERIAL = $rowLinea->ID_MATERIAL AND ID_UBICACION = $rowUbiEmbarque->ID_UBICACION AND ID_MATERIAL_FISICO " . ($rowLinea->ID_MATERIAL_FISICO == NULL ? 'IS NULL' : "= $rowLinea->ID_MATERIAL_FISICO") . " AND ID_TIPO_BLOQUEO " . ($rowLinea->ID_TIPO_BLOQUEO == NULL ? 'IS NULL' : "= $rowLinea->ID_TIPO_BLOQUEO") . " AND ID_ORDEN_TRABAJO_MOVIMIENTO " . ($rowLinea->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? 'IS NULL' : "= $rowLinea->ID_ORDEN_TRABAJO_MOVIMIENTO") . " AND ID_INCIDENCIA_CALIDAD " . ($rowLinea->ID_INCIDENCIA_CALIDAD == NULL ? 'IS NULL' : "= $rowLinea->ID_INCIDENCIA_CALIDAD"), "No");
                if ($rowMatUbiEmbarque == false):
                    $sqlInsert = "INSERT INTO MATERIAL_UBICACION SET
                                    ID_MATERIAL = $rowLinea->ID_MATERIAL
                                    , ID_UBICACION = $rowUbiEmbarque->ID_UBICACION
                                    , ID_MATERIAL_FISICO = " . ($rowLinea->ID_MATERIAL_FISICO == NULL ? 'NULL' : "$rowLinea->ID_MATERIAL_FISICO") . "
                                    , ID_TIPO_BLOQUEO = " . ($rowLinea->ID_TIPO_BLOQUEO == NULL ? 'NULL' : "$rowLinea->ID_TIPO_BLOQUEO") . "
                                    , ID_ORDEN_TRABAJO_MOVIMIENTO = " . ($rowLinea->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? 'NULL' : "$rowLinea->ID_ORDEN_TRABAJO_MOVIMIENTO") . "
                                    , ID_INCIDENCIA_CALIDAD = " . ($rowLinea->ID_INCIDENCIA_CALIDAD == NULL ? 'NULL' : "$rowLinea->ID_INCIDENCIA_CALIDAD");
                    $bd->ExecSQL($sqlInsert);
                    $idMatUbiEmbarque = $bd->IdAsignado();
                else:
                    $idMatUbiEmbarque = $rowMatUbiEmbarque->ID_MATERIAL_UBICACION;
                endif;

                //INCREMENTO MATERIAL UBICACION (EMBARQUE) (SI ES DE CONSTRUCCION EVITAMOS QUE ENTRE EN EL EL PROCESO DE INTEGRIDAD DE MATERIAL FISICO, EN MATERIAL UBICACION SI DEBERIA ENTRAR)
                $sqlUpdate = "UPDATE MATERIAL_UBICACION SET
                                STOCK_TOTAL = STOCK_TOTAL + $rowLinea->CANTIDAD
                                , STOCK_OK = STOCK_OK + " . ($rowLinea->ID_TIPO_BLOQUEO == NULL ? "$rowLinea->CANTIDAD" : 0) . "
                                , STOCK_BLOQUEADO = STOCK_BLOQUEADO + " . ($rowLinea->ID_TIPO_BLOQUEO == NULL ? 0 : "$rowLinea->CANTIDAD")
                    . ($rowMov->TIPO_MOVIMIENTO == 'TrasladoOMConstruccion' ? ", PENDIENTE_REVISAR_MATERIAL_FISICO_UBICACION = 2, PENDIENTE_REVISAR_MATERIAL_UBICACION_TIPO_BLOQUEO = 1" : "") . "
                                WHERE ID_MATERIAL_UBICACION = $idMatUbiEmbarque";
                $bd->ExecSQL($sqlUpdate);
            endif;

            //DOY DE BAJA LOS DATOS DE CONTENEDOR Y CONTENEDOR LINEA EN CASO DE TENERLOS
            if ($rowOrdenTransporte->CON_BULTOS == 1):
                $sqlContenedor    = "SELECT MSL.ID_CONTENEDOR_LINEA, MSL.ID_BULTO_LINEA, MSL.CANTIDAD, MSL.ID_TIPO_BLOQUEO
                                      FROM MOVIMIENTO_SALIDA_LINEA MSL
                                      WHERE MSL.ID_MOVIMIENTO_SALIDA_LINEA = $rowLinea->ID_MOVIMIENTO_SALIDA_LINEA";
                $resultContenedor = $bd->ExecSQL($sqlContenedor);
                while ($rowContenedor = $bd->SigReg($resultContenedor)):
                    //ACTUALIZO EL STOCK TOTAL DEL CONTENDOR LINEA
                    $sqlUpdate = "UPDATE CONTENEDOR_LINEA SET
                                    STOCK_TOTAL = STOCK_TOTAL - $rowContenedor->CANTIDAD
                                    , STOCK_OK = STOCK_OK - " . ($rowLinea->ID_TIPO_BLOQUEO == NULL ? "$rowLinea->CANTIDAD" : 0) . "
                                    , STOCK_BLOQUEADO = STOCK_BLOQUEADO - " . ($rowLinea->ID_TIPO_BLOQUEO == NULL ? 0 : "$rowLinea->CANTIDAD") . "
                                    WHERE ID_CONTENEDOR_LINEA = $rowContenedor->ID_CONTENEDOR_LINEA";
                    $bd->ExecSQL($sqlUpdate);

                    //DOY DE BAJA EL CONTENEDOR LINEA SI NO TIENE STOCK
                    $sqlUpdate = "UPDATE CONTENEDOR_LINEA SET
                                    BAJA = 1
                                    WHERE ID_CONTENEDOR_LINEA = $rowContenedor->ID_CONTENEDOR_LINEA AND STOCK_TOTAL = 0";
                    $bd->ExecSQL($sqlUpdate);
                endwhile;
            endif;
            //DOY DE BAJA LOS DATOS DE CONTENEDOR Y CONTENEDOR LINEA EN CASO DE TENERLOS
        endwhile; //FIN RECORRO LA SELECCION DE LINEAS DE LA EXPEDICION SAP

        //DEJO LOS VALORES UNICOS DE LOS ARRAY A ACTUALIZAR
        $arrMovimientosActualizar  = array_unique( (array)$arrMovimientosActualizar);
        $arrBultosActualizar       = array_unique( (array)$arrBultosActualizar);
        $arrPedidosLineaActualizar = array_unique( (array)$arrPedidosLineaActualizar);

        //ACTUALIZO LOS DIFERENTES OBJETOS

        //ACTUALIZO LOS MOVIMIENTOS
        foreach ($arrMovimientosActualizar as $idMovimientoSalida):
            $movimiento->actualizarEstadoMovimientoSalida($idMovimientoSalida);
        endforeach;

        //ACTUALIZO LOS BULTOS
        foreach ($arrBultosActualizar as $idBulto):
            $orden_preparacion->ActualizarEstadoBulto($idBulto);
        endforeach;
        //FIN ACTUALIZO LOS DIFERENTES OBJETOS

        /************************************ HABILITO TRIGGERS IMPLICADOS ************************************/
        $sqlHabilitarTriggers = "SET @Trigger_MOVIMIENTO_SALIDA_LINEA_UPDATE_Deshabilitado = NULL";
        $bd->ExecSQL($sqlHabilitarTriggers);
        /********************************** FIN HABILITO TRIGGERS IMPLICADOS **********************************/

        //ACTUALIZO UNA LINEA DE MOVIMIENTO DE CADA LINEA DE PEDIDO
        foreach ($arrPedidosLineaActualizar as $idPedidoLineaSalida):
            //BUSCO UNA LINEA DE MOVIMIENTO DE SALIDA LINEA DE ESTA LINEA DE PEDIDO
            $rowMovimientoSalidaLinea = $bd->VerRegRest("MOVIMIENTO_SALIDA_LINEA", "ID_PEDIDO_SALIDA_LINEA = $idPedidoLineaSalida AND LINEA_ANULADA = 0 AND BAJA = 0", "No");

            //ACTUALIZO EL ESTADO DE LA LINEA DEL PEDIDO DE SALIDA
            $movimiento->actualizarEstadoLineaPedidoSalida($rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA_LINEA);
            //ACTUALIZO EL ESTADO DE LA NECESIDAD DE LA LINEA DEL MOVIMIENTO DE SALIDA
            $movimiento->actualizarEstadoLineaNecesidad($rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA);
        endforeach;

        //SI ES UN PEDIDO DE MATERIAL ESTROPEADO A PROVEEDOR TENDRE QUE ACTUALIZAR LOS CONTRATOS MARCO Y LAS FECHAS DE EXPEDICION SI PREVIAMENTE NO SE HAN PRODUCIDO ERRORES
        if (($rowPedSal->TIPO_PEDIDO == 'Material Estropeado a Proveedor') && ($hayErrorExpedicionSAP == false)):
            //BUSCO UNA LINEA DE MOVIMIENTO SALIDA LINEA ASOCIADA AL PEDIDO, TODAS SON REPARABLES Y PUEDEN ESTAR EN GARANTIA O NO
            $rowLineaMovimientoSalida = $bd->VerRegRest("MOVIMIENTO_SALIDA_LINEA MSL", "MSL.ID_PEDIDO_SALIDA = $rowPedSal->ID_PEDIDO_SALIDA AND MSL.ESTADO = 'Transmitido a SAP' AND MSL.LINEA_ANULADA = 0 AND MSL.BAJA = 0");

            //BUSCO EL TIPO DE BLOQUEO DE LA LINEA
            $rowTipoBloqueo = $bd->VerReg("TIPO_BLOQUEO", "ID_TIPO_BLOQUEO", $rowLineaMovimientoSalida->ID_TIPO_BLOQUEO);

            //BUSCO EL TIPO DE BLOQUEO REPARABLE NO EN GARANTIA
            $rowTipoBloqueoQRNG = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", 'QRNG', "No"); //REPARABLE NO EN GARANTIA
            //BUSCO EL TIPO DE BLOQUEO RETENIDO POR CALIDAD NO PREVENTIVO REPARABLE NO EN GARANTIA
            $rowTipoBloqueoXRCRNG = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", 'XRCRNG', "No"); //REPARABLE NO EN GARANTIA

            //SI LA LINEA ES REPARABLE NO EN GARANTIA, PIDO A SAP DATOS DE CONTRATO PARA CADA MATERIAL
            if (($rowTipoBloqueo->ID_TIPO_BLOQUEO == $rowTipoBloqueoQRNG->ID_TIPO_BLOQUEO) || ($rowTipoBloqueo->ID_TIPO_BLOQUEO == $rowTipoBloqueoXRCRNG->ID_TIPO_BLOQUEO)):

                $arr_materiales_contrato_comprobar = array(); //cada item tendrá (ID_PROVEEDOR , ID_CENTRO , ID_MATERIAL)
                $arr_materiales_contrato_inicial   = array();//AL AÑADIR ALMACEN A LA QUERY PUEDE QUE METAMOS REGISTROS USADOS

                //OBTENGO ID_MATERIAL Y ID_CENTRO PARA LO SELECCIONDO
                $sqlMaterialCentro    = "SELECT DISTINCT MSL.ID_MATERIAL, A.ID_CENTRO, AD.ID_PROVEEDOR, A.ID_ALMACEN, A.STOCK_COMPARTIDO, A.ID_CENTRO_FISICO
                                         FROM MOVIMIENTO_SALIDA_LINEA MSL
                                         INNER JOIN UBICACION U ON U.ID_UBICACION = MSL.ID_UBICACION
                                         INNER JOIN ALMACEN A ON A.ID_ALMACEN = U.ID_ALMACEN
                                         INNER JOIN ALMACEN AD ON AD.ID_ALMACEN = MSL.ID_ALMACEN_DESTINO
                                         WHERE MSL.ENVIADO_SAP = 0 AND MSL.LINEA_ANULADA = 0 AND MSL.BAJA = 0 AND MSL.ID_PEDIDO_SALIDA = $rowPedSal->ID_PEDIDO_SALIDA";
                $resultMaterialCentro = $bd->ExecSQL($sqlMaterialCentro);

                //RECORRO EL RESULTADO PARA IR RELLENADO EL ARRAY DE MATERIAL A COMPROBAR EL CONTRATO EN SAP
                $indice = 0;
                while ($rowMaterialCentro = $bd->SigReg($resultMaterialCentro)):
                    //BUSCO EL PROVEEDOR
                    $rowProv = $bd->VerReg("PROVEEDOR", "ID_PROVEEDOR", $rowMaterialCentro->ID_PROVEEDOR, "No");
                    if ($rowProv->ID_PROVEEDOR_PADRE != NULL):
                        $rowProv = $bd->VerReg("PROVEEDOR", "ID_PROVEEDOR", $rowProv->ID_PROVEEDOR_PADRE, "No");
                    endif;

                    if ($rowMaterialCentro->STOCK_COMPARTIDO == 1):
                        $sqlAlmacenMantenedor    = "SELECT A.ID_CENTRO
                                                      FROM ALMACEN A
                                                      INNER JOIN CENTRO_FISICO CF ON CF.ID_CENTRO_FISICO=A.ID_CENTRO_FISICO
                                                      WHERE CF.GESTION_STOCK_TERCEROS=1 AND CF.ID_CENTRO_FISICO= " . $rowMaterialCentro->ID_CENTRO_FISICO . " AND A.TIPO_STOCK='Mantenedor'";
                        $resultAlmacenMantenedor = $bd->ExecSQL($sqlAlmacenMantenedor);

                        if ($bd->NumRegs($resultAlmacenMantenedor) == 0):
                            $hayErrorExpedicionSAP = true;
                            $strError              = $strError . $auxiliar->traduce("No existe almacen mantenedor para el almacen SPV", $administrador->ID_IDIOMA) . ". " . $rowAlbaranLinea->NUMERO_LINEA . " " . $auxiliar->traduce("del albarán", $administrador->ID_IDIOMA) . " " . $rowAlbaran->ID_ALBARAN . ".<br>";
                        //$html->PagError("NoExisteAlmacenMantenedor");
                        else:
                            $rowAlmMantenedor             = $bd->SigReg($resultAlmacenMantenedor);
                            $rowMaterialCentro->ID_CENTRO = $rowAlmMantenedor->ID_CENTRO;
                        endif;
                    endif;

                    $arr_materiales_contrato_inicial[$rowProv->ID_PROVEEDOR . "_" . $rowMaterialCentro->ID_CENTRO . "_" . $rowMaterialCentro->ID_MATERIAL] = 1;
                endwhile;

                //RECORRO EL RESULTADO PARA IR RELLENADO EL ARRAY DE MATERIAL A COMPROBAR EL CONTRATO EN SAP
                $indice = 0;
                foreach ($arr_materiales_contrato_inicial as $clave_array => $valor_array):
                    //BUSCO EL PROVEEDOR
                    $arr_clave = explode("_", (string)$clave_array);

                    $arr_materiales_contrato_comprobar[$indice]['ID_PROVEEDOR'] = $arr_clave[0];
                    $arr_materiales_contrato_comprobar[$indice]['ID_CENTRO']    = $arr_clave[1];
                    $arr_materiales_contrato_comprobar[$indice]['ID_MATERIAL']  = $arr_clave[2];
                    $indice++;
                endforeach;

                //LLAMO A SAP
                $resultado = $sap->materialesConContrato($arr_materiales_contrato_comprobar);

                if ($resultado['RESULTADO'] != 'OK'): //No se ha podido grabar la información en SAP
                    foreach ($resultado['ERRORES'] as $arr):
                        //ACTUALIZO LA VARIABLE ERROR EN LA TRANSMISION A TRUE
                        $hayErrorTransmisionExpedicionSAP = true;
                        $hayErrorExpedicionSAP            = true;

                        //GRABO LA CEBECERA DEL ERROR SAP
                        $strError = "<strong>" . $auxiliar->traduce("Se han producido los siguientes errores SAP al solicitar el contrato marco de las lineas de la expedicion SAP", $administrador->ID_IDIOMA) . " " . $expedicionSAP . ":</strong><br>";

                        foreach ($arr as $mensaje_error):
                            $strError = $strError . $mensaje_error . "<br>";
                        endforeach;
                    endforeach;
                else:
                    $arr_materiales_contrato_comprobados = $resultado['MATERIALES']; //devuelve un array con claves ['ID_PROVEEDOR'_'ID_CENTRO'_'ID_MATERIAL']  y la informacion de CONTRATO y POSICION_CONTRATO
                endif;
            endif;
            //FIN SI LA LINEA ES REPARABLE NO EN GARANTIA, PIDO A SAP DATOS DE CONTRATO PARA CADA MATERIAL

            //SI NO HAY ERRORES EN LA EXPEDICION SAP, ACTUALIZO LAS LINEAS DEL MOVIMIENTO DE SALIDA DE MATERIAL ESTROPEADO A PROVEEDOR
            if ($hayErrorExpedicionSAP == false):

                //BUSCO EL MOVIMIENTO ASOCIADO AL PEDIDO
                $sqlMovimientoSalida    = "SELECT MS.*
                                            FROM MOVIMIENTO_SALIDA MS
                                            WHERE MS.ID_PEDIDO_SALIDA = $rowPedSal->ID_PEDIDO_SALIDA AND MS.BAJA = 0";
                $resultMovimientoSalida = $bd->ExecSQL($sqlMovimientoSalida);
                if ($bd->NumRegs($resultMovimientoSalida) != 1):
                    $hayErrorExpedicionSAP = true;
                    $strError              = $strError . $auxiliar->traduce("No se ha encontrado un unico movimiento de envio de material estropeado a proveedor para servir la linea", $administrador->ID_IDIOMA) . " " . $rowAlbaranLinea->NUMERO_LINEA . " " . $auxiliar->traduce("del albarán", $administrador->ID_IDIOMA) . " " . $rowAlbaran->ID_ALBARAN . ".<br>";
                else:
                    //RECUPERO EL MOVIMIENTO DE SALIDA CORRESPONDIENTE
                    $rowMovimientoSalida = $bd->SigReg($resultMovimientoSalida);

                    //VARIABLE PARA DETERMINAR EL TIPO DE MOVIMIENTO A ENVIAR A SAP
                    $tipoMovimiento = NULL;

                    //VARIABLE PARA SABER SI HAYA QUE TRANSMITIR EL MOVIMIENTO DIRECTO A PROVEEDOR A SAP
                    $SistemaOT = NULL;

                    //BUSCO LAS LINEAS DEL MOVIMIENTO DE SALIDA
                    $sqlLineasMovimientoSalida    = "SELECT *
                                                      FROM MOVIMIENTO_SALIDA_LINEA MSL
                                                      WHERE MSL.ID_MOVIMIENTO_SALIDA = $rowMovimientoSalida->ID_MOVIMIENTO_SALIDA AND MSL.ENVIADO_SAP = 0 AND MSL.ESTADO = 'Transmitido a SAP' AND MSL.LINEA_ANULADA = 0 AND MSL.BAJA = 0";
                    $resultLineasMovimientoSalida = $bd->ExecSQL($sqlLineasMovimientoSalida);
                    while ($rowLineaMovimientoSalida = $bd->SigReg($resultLineasMovimientoSalida)):

                        //BUSCO EL MATERIAL
                        $rowMat = $bd->VerReg("MATERIAL", "ID_MATERIAL", $rowLineaMovimientoSalida->ID_MATERIAL);

                        //BUSCO EL MATERIAL FISICO
                        if ($rowLineaMovimientoSalida->ID_MATERIAL_FISICO == NULL):
                            $idMatFis = NULL;
                        else:
                            $rowMatFis = $bd->VerReg("MATERIAL_FISICO", "ID_MATERIAL_FISICO", $rowLineaMovimientoSalida->ID_MATERIAL_FISICO);
                            $idMatFis  = $rowMatFis->ID_MATERIAL_FISICO;
                        endif;

                        //BUSCO LA INFORMACION DE LA OT
                        $rowOTMovimiento = $bd->VerReg("ORDEN_TRABAJO_MOVIMIENTO", "ID_ORDEN_TRABAJO_MOVIMIENTO", $rowLineaMovimientoSalida->ID_ORDEN_TRABAJO_MOVIMIENTO);

                        //BUSCO LA OT
                        $rowOT = $bd->VerReg("ORDEN_TRABAJO", "ID_ORDEN_TRABAJO", $rowOTMovimiento->ID_ORDEN_TRABAJO);

                        //BUSCO EL TIPO DE BLOQUEO
                        $rowTipoBloqueo = $bd->VerReg("TIPO_BLOQUEO", "ID_TIPO_BLOQUEO", $rowOTMovimiento->ID_TIPO_BLOQUEO);

                        //BUSCO EL CENTRO DE LA OT
                        $rowCentroOT = $bd->VerReg("CENTRO", "ID_CENTRO", $rowOT->ID_CENTRO);

                        //BUSCO LA SOCIEDAD DE LA OT
                        $rowSociedadOT = $bd->VerReg("SOCIEDAD", "ID_SOCIEDAD", $rowCentroOT->ID_SOCIEDAD);

                        //BUSCO EL CENTRO ORIGEN DEL PEDIDO
                        $rowCentroOrigenPedido = $bd->VerReg("CENTRO", "ID_CENTRO", $rowPedSal->ID_CENTRO_ORIGEN);

                        //BUSCAMOS ALMACEN ORIGEN POR SI TIENE STOCK COMPARTIDO
                        $idCentroContrato    = $rowCentroOrigenPedido->ID_CENTRO;
                        $rowAlmacenOrigenMov = $bd->VerReg("ALMACEN", "ID_ALMACEN", $rowLineaMovimientoSalida->ID_ALMACEN);
                        if ($rowAlmacenOrigenMov->STOCK_COMPARTIDO == 1):
                            $sqlAlmacenMantenedor    = "SELECT A.ID_CENTRO
                                                      FROM ALMACEN A
                                                      INNER JOIN CENTRO_FISICO CF ON CF.ID_CENTRO_FISICO=A.ID_CENTRO_FISICO
                                                      WHERE CF.GESTION_STOCK_TERCEROS=1 AND CF.ID_CENTRO_FISICO= " . $rowAlmacenOrigenMov->ID_CENTRO_FISICO . " AND A.TIPO_STOCK='Mantenedor'";
                            $resultAlmacenMantenedor = $bd->ExecSQL($sqlAlmacenMantenedor);
                            if ($bd->NumRegs($resultAlmacenMantenedor) == 0):
                                $hayErrorExpedicionSAP = true;
                                $strError              = $strError . $auxiliar->traduce("No existe almacen mantenedor para el almacen SPV", $administrador->ID_IDIOMA) . ".<br>";
                            else:
                                $rowAlmMantenedor = $bd->SigReg($resultAlmacenMantenedor);
                                $idCentroContrato = $rowAlmMantenedor->ID_CENTRO;
                            endif;
                        endif;

                        //BUSCO LA SOCIEDAD DEL PEDIDO
                        $rowSociedadOrigenPedido = $bd->VerReg("SOCIEDAD", "ID_SOCIEDAD", $rowCentroOrigenPedido->ID_SOCIEDAD);

                        //BUSCO EL ALMACEN DESTINO DE LA LINEA DEL MATERIAL (UN ALMACEN DE PROVEEDOR)
                        $rowAlmacenDestino = $bd->VerReg("ALMACEN", "ID_ALMACEN", $rowLineaMovimientoSalida->ID_ALMACEN_DESTINO);

                        //BUSCO EL PROVEEDOR DE ENVIO
                        $rowProvEnvio = $bd->VerReg("PROVEEDOR", "ID_PROVEEDOR", $rowAlmacenDestino->ID_PROVEEDOR);

                        //BUSCO EL PROVEEDOR
                        if ($rowProvEnvio->ID_PROVEEDOR_PADRE == NULL):
                            $rowProv = $bd->VerReg("PROVEEDOR", "ID_PROVEEDOR", $rowProvEnvio->ID_PROVEEDOR, "No");
                        else:
                            $rowProv = $bd->VerReg("PROVEEDOR", "ID_PROVEEDOR", $rowProvEnvio->ID_PROVEEDOR_PADRE, "No");
                        endif;

                        //COJO LOS DATOS DE CONTRATO ASOCIADO PARA ASIGNARLOS (los he cogido antes de SAP)
                        $contrato_asociado   = '0';
                        $contrato_referencia = $arr_materiales_contrato_comprobados[$rowProv->ID_PROVEEDOR . '_' . $idCentroContrato . '_' . $rowLineaMovimientoSalida->ID_MATERIAL]['CONTRATO'];
                        $contrato_posicion   = $arr_materiales_contrato_comprobados[$rowProv->ID_PROVEEDOR . '_' . $idCentroContrato . '_' . $rowLineaMovimientoSalida->ID_MATERIAL]['POSICION_CONTRATO'];

                        if ($contrato_referencia != NULL):
                            //ACTUALIZO LA LINEA PARA RELLENARLE EL CONTRATO MARCO
                            $sqlUpdate = "UPDATE MOVIMIENTO_SALIDA_LINEA SET
                                            CONTRATO_ASOCIADO = 1
                                            , CONTRATO_REFERENCIA = " . ($contrato_referencia === NULL ? 'NULL' : "'" . $contrato_referencia . "'") . "
                                            , CONTRATO_POSICION = " . ($contrato_posicion === NULL ? 'NULL' : "'" . $contrato_posicion . "'") . "
                                            WHERE ID_MOVIMIENTO_SALIDA_LINEA = $rowLineaMovimientoSalida->ID_MOVIMIENTO_SALIDA_LINEA";
                            $bd->ExecSQL($sqlUpdate);
                        endif;

                        //COMPRUEBO QUE TODAS LAS LINEAS SEAN CON/SIN ENTRADA COMPRA
                        if ($SistemaOT == NULL):
                            $SistemaOT = $rowOT->SISTEMA_OT;
                        elseif ($SistemaOT != $rowOT->SISTEMA_OT):
                            $hayErrorExpedicionSAP = true;
                            $strError              = $strError . $auxiliar->traduce("Esta mezclando lineas de material estropeado de Ots con lineas sin orden de compra para servir la línea número", $administrador->ID_IDIOMA) . " " . $rowAlbaranLinea->NUMERO_LINEA . " " . $auxiliar->traduce("del albarán", $administrador->ID_IDIOMA) . " " . $rowAlbaran->ID_ALBARAN . ".<br>";
                        endif;

                        //DETERMINO EL TIPO DE MOVIMIENTO A INFORMAR A SAP
                        if ($rowOT->SISTEMA_OT == 'SGA Entrada Sin OC'):
                            $tipoMovimiento = "Sin Compra";
                        elseif (substr( (string) $rowTipoBloqueo->TIPO_BLOQUEO_INTERNO, 0, 1) == 'X'):
                            $tipoMovimiento = "Calidad";
                        elseif (substr( (string) $rowTipoBloqueo->TIPO_BLOQUEO_INTERNO, 0, 1) == 'Q'):
                            //CALCULO EL NUMERO DE PEDIDOS INTERCOMPANY DE MATERIAL ESTROPEADO EN LOS QUE ESTE IMPLICADO ESTE MOVIMIENTO DE ORDEN DE TRABAJO
                            $num = $mat->getNumeroPedidosIntercompanyMaterialEstropeado($rowOTMovimiento->ID_ORDEN_TRABAJO_MOVIMIENTO);

                            //EN FUNCION DEL NUMERO DE PEDIDOS INTERCOMPANY DE MATERIAL ESTROPEADO CALCULO EL TIPO
                            if ($num == 0):
                                $tipoMovimiento = 'Orden Trabajo';
                            elseif ($num == 1):
                                $tipoMovimiento = 'Traslado Internacional';
                            else:
                                $tipoMovimiento = 'Calidad';
                            endif;

//                            //COMPRUEBO EL NUMERO DE SOCIEDADES DONDE HA ESTADO EL MATERIAL ESTROPEADO
//                            $sqlNumSociedades = "SELECT COUNT(TEMP.ID_MATERIAL_UBICACION) AS NUM FROM
//                                                    (SELECT MU.ID_MATERIAL_UBICACION
//                                                     FROM MATERIAL_UBICACION MU
//                                                     INNER JOIN UBICACION U ON U.ID_UBICACION = MU.ID_UBICACION
//                                                     INNER JOIN ALMACEN A ON A.ID_ALMACEN = U.ID_ALMACEN
//                                                     INNER JOIN CENTRO C ON C.ID_CENTRO = A.ID_CENTRO
//                                                     WHERE ID_ORDEN_TRABAJO_MOVIMIENTO = $rowOTMovimiento->ID_ORDEN_TRABAJO_MOVIMIENTO
//                                                     GROUP BY C.ID_SOCIEDAD) AS TEMP";
//                            $resultNumSociedades = $bd->ExecSQL($sqlNumSociedades);
//                            $rowNumSociedades = $bd->SigReg($resultNumSociedades);
//                            $numSociedades = $rowNumSociedades->NUM;
//                            if ($numSociedades > 2): //SE HA MOVIDO ENTRE 3 SOCIEDADES O MAS
//                                $tipoMovimiento = 'Calidad';
//                            elseif (($numSociedades == 2) && ($rowSociedadOT->ID_SOCIEDAD == $rowSociedadOrigenPedido->ID_SOCIEDAD)): //SE HA MOVIDO A OTRA SOCIEDAD Y HA VUELTO
//                                $tipoMovimiento = 'Calidad';
//                            else: //NO SE HA MOVIDO DE LA SOCIEDAD ORIGINAL O SOLO SE HA MOVIDO UNA VEZ A OTRA SOCIEDAD
//                                if ($rowSociedadOT->ID_SOCIEDAD == $rowSociedadOrigenPedido->ID_SOCIEDAD):
//                                    $tipoMovimiento = 'Orden Trabajo';
//                                else:
//                                    $tipoMovimiento = 'Traslado Internacional';
//                                endif;
//                            endif;
////                        elseif ((substr($rowTipoBloqueo->TIPO_BLOQUEO_INTERNO, 0, 1) == 'Q') && ($rowSociedadOT->ID_SOCIEDAD == $rowSociedadOrigenPedido->ID_SOCIEDAD)):
////                            $tipoMovimiento = "Orden Trabajo";
////                        elseif ((substr($rowTipoBloqueo->TIPO_BLOQUEO_INTERNO, 0, 1) == 'Q') && ($rowSociedadOT->ID_SOCIEDAD != $rowSociedadOrigenPedido->ID_SOCIEDAD)):
////                            $tipoMovimiento = "Traslado Internacional";
                        else:
                            $html->PagError("LineasConErrores");
                        endif;

                        //ACTUALIZO LA LINEA PARA ACTUALIZAR EL ESTADO
                        $sqlUpdate = "UPDATE MOVIMIENTO_SALIDA_LINEA SET
                                      FECHA_EXPEDICION = '" . date("Y-m-d H:i:s") . "'
                                      WHERE ID_MOVIMIENTO_SALIDA_LINEA = $rowLineaMovimientoSalida->ID_MOVIMIENTO_SALIDA_LINEA";
                        $bd->ExecSQL($sqlUpdate);

                        if ($rowLineaMovimientoSalida->FECHA_EXPEDICION_INFORME_FACTURACION == "0000-00-00 00:00:00"):
                            //ACTUALIZO LA LINEA PARA ACTUALIZAR EL ESTADO
                            $sqlUpdate = "UPDATE MOVIMIENTO_SALIDA_LINEA SET
                                          FECHA_EXPEDICION_INFORME_FACTURACION = '" . date("Y-m-d H:i:s") . "'
                                          WHERE ID_MOVIMIENTO_SALIDA_LINEA = $rowLineaMovimientoSalida->ID_MOVIMIENTO_SALIDA_LINEA";
                            $bd->ExecSQL($sqlUpdate);
                        endif;
                    endwhile;
                    //FIN BUSCO LAS LINEAS DEL MOVIMIENTO DE SALIDA

                    //ACTUALIZO LA FECHA EXPEDICION DEL MOVIMIENTO DE SALIDA
                    $sqlUpdate = "UPDATE MOVIMIENTO_SALIDA SET
                                  FECHA_EXPEDICION = '" . date("Y-m-d") . "'
                                  WHERE ID_MOVIMIENTO_SALIDA = $rowMovimientoSalida->ID_MOVIMIENTO_SALIDA";
                    $bd->ExecSQL($sqlUpdate);
                endif;
                //FIN BUSCO EL MOVIMIENTO ASOCIADO AL PEDIDO
            endif;
            //FIN SI NO HAY ERRORES EN LA EXPEDICION SAP, ACTUALIZO LAS LINEAS DEL MOVIMIENTO DE SALIDA DE MATERIAL ESTROPEADO A PROVEEDOR
        endif;
        //FIN SI ES UN PEDIDO DE MATERIAL ESTROPEADO A PROVEEDOR TENDRE QUE ACTUALIZAR LOS CONTRATOS MARCO Y LAS FECHAS DE EXPEDICION SI PREVIAMENTE NO SE HAN PRODUCIDO ERRORES


        //ACTUALIZO EL ESTADO DE LA ORDEN DE TRANSPORTE
        $this->actualizar_estado_orden_transporte($rowOrdenTransporte->ID_EXPEDICION);

        //SI NO HAY ERRORES EN LA EXPEDICION, HAGO LA LLAMADA A SAP, EL CAMPO ENVIADO_SAP Y FECHA_TRANSMISION_A_SAP_REAL SE ACTUALIZAN EN LA LIBRERIA SAP
        if ($hayErrorExpedicionSAP == false):

            /************************************ DESHABILITO TRIGGERS IMPLICADOS ************************************/
            $sqlDeshabilitarTriggers = "SET @Trigger_MOVIMIENTO_SALIDA_LINEA_UPDATE_Deshabilitado = 1";
            $bd->ExecSQL($sqlDeshabilitarTriggers);
            /********************************** FIN DESHABILITO TRIGGERS IMPLICADOS **********************************/

            unset($resultado);
            if (
                ($rowPedSal->TIPO_PEDIDO == "Venta") ||
                ($rowPedSal->TIPO_PEDIDO == "Traslado") ||
                ($rowPedSal->TIPO_PEDIDO == "Intra Centro Fisico") ||
                ($rowPedSal->TIPO_PEDIDO == "Interno Gama") ||
                ($rowPedSal->TIPO_PEDIDO == "Pendientes de Ordenes Trabajo") ||
                ($rowPedSal->TIPO_PEDIDO == "Componentes a Proveedor") ||
                ($rowPedSal->TIPO_PEDIDO == "Traspaso Entre Almacenes Material Estropeado")
            ):

                $resultado = $sap->InformarSAPExpedicion($rowOrdenTransporte->VERSION, $expedicionSAP);

            elseif ($rowPedSal->TIPO_PEDIDO == "Devolución a Proveedor"):

                $resultado = $sap->ExpedicionMovimientoDevolucionAProveedor($rowOrdenTransporte->VERSION, $expedicionSAP);

            elseif (($rowPedSal->TIPO_PEDIDO == "Material Estropeado a Proveedor") && ($tipoMovimiento != "Sin Compra")):

                $resultado = $sap->AjusteTraspasoMaterialAProveedor($rowMovimientoSalida->ID_MOVIMIENTO_SALIDA, $tipoMovimiento);

            endif;

            /************************************ HABILITO TRIGGERS IMPLICADOS ************************************/
            $sqlHabilitarTriggers = "SET @Trigger_MOVIMIENTO_SALIDA_LINEA_UPDATE_Deshabilitado = NULL";
            $bd->ExecSQL($sqlHabilitarTriggers);
            /********************************** FIN HABILITO TRIGGERS IMPLICADOS **********************************/

            //SOLO SI ESTA DEFINIDO, HAY MOVIMIENTOS QUE SE EXPIDEN Y SE INFORMA A SAP DE ELLOS
            //LOS DE TIPO Devolucion a proveedor por anulacion o calidad NO SE INFORMA DE ELLOS
            if (isset($resultado)):
                if ($resultado['RESULTADO'] != 'OK'): //No se ha podido grabar la información en SAP
                    //ACTUALIZO LA VARIABLE ERROR EN LA TRANSMISION A TRUE
                    $hayErrorTransmisionExpedicionSAP = true;

                    //GRABO LA CEBECERA DEL ERROR SAP
                    $strError = "<strong>" . $auxiliar->traduce("Se han producido los siguientes errores SAP al transmitir la expedicion SAP", $administrador->ID_IDIOMA) . " " . $expedicionSAP . ":</strong><br>";

                    foreach ($resultado['ERRORES'] as $arr):
                        foreach ($arr as $mensaje_error):
                            $strError = $strError . $mensaje_error . "<br>";
                        endforeach;
                    endforeach;
                endif;
            else: //MOVIMIENTO QUE NO SE INFORMAN A SAP
                //CALCULO LA FECHA Y HORA REAL DE TRANSMITIR LA EXPEDICION SAP A SAP
                $fechaHoraActual = date("Y-m-d H:i:s");

                /************************************ DESHABILITO TRIGGERS IMPLICADOS ************************************/
                $sqlDeshabilitarTriggers = "SET @Trigger_MOVIMIENTO_SALIDA_LINEA_UPDATE_Deshabilitado = 1";
                $bd->ExecSQL($sqlDeshabilitarTriggers);
                /********************************** FIN DESHABILITO TRIGGERS IMPLICADOS **********************************/

                //RECORRO LAS LINEAS DEL MOVIMIENTO PARA RELLENAR (ENVIADO_SAP Y FECHA_TRANSMISION_A_SAP_REAL) EN LOS MOVIMIENTOSQUE QUE NO SE TRANSMITEN A SAP (ENVIOS A PROVEEDOR DE MATERIAL RECHAZADO POR CALIDAD Y POR ANULACIONES Y TRASLADOS DESCONETADOS DE SAP)
                $sqlLineas    = "SELECT DISTINCT MSL.ID_MOVIMIENTO_SALIDA_LINEA
                                    FROM MOVIMIENTO_SALIDA_LINEA MSL
                                    INNER JOIN MOVIMIENTO_SALIDA MS ON MS.ID_MOVIMIENTO_SALIDA = MSL.ID_MOVIMIENTO_SALIDA
                                    INNER JOIN PEDIDO_SALIDA PS ON PS.ID_PEDIDO_SALIDA = MSL.ID_PEDIDO_SALIDA
                                    WHERE EXPEDICION_SAP = '" . $expedicionSAP . "' AND
                                    (
                                        (MS.TIPO_MOVIMIENTO = 'MaterialRechazadoAnuladoEnEntradasAProveedor') OR
                                        (MS.TIPO_MOVIMIENTO = 'MaterialEstropeadoAProveedor') OR
                                        (MS.TIPO_MOVIMIENTO = 'TrasladoOMConstruccion') OR
                                        ( (MS.TIPO_MOVIMIENTO = 'TraspasoEntreAlmacenesNoEstropeado') AND (PS.TIPO_PEDIDO = 'Traslado') AND (PS.TIPO_TRASLADO = 'Desconectado') )
                                    )";
                $resultLineas = $bd->ExecSQL($sqlLineas);
                while ($rowLinea = $bd->SigReg($resultLineas)):
                    $sqlUpdate = "UPDATE MOVIMIENTO_SALIDA_LINEA SET
                                    ENVIADO_SAP = 1
                                    , FECHA_TRANSMISION_A_SAP_REAL = '" . $fechaHoraActual . "'
                                    WHERE ID_MOVIMIENTO_SALIDA_LINEA = $rowLinea->ID_MOVIMIENTO_SALIDA_LINEA";
                    $bd->ExecSQL($sqlUpdate);
                endwhile; //BUCLE LINEAS DE MOVIMIENTO DE SALIDA

                //BUSCAMOS LOS MOVIMIENTOS DE ENTRADA
                $sqlMovimientosLineas    = "SELECT DISTINCT MS.ID_MOVIMIENTO_SALIDA
                                            FROM MOVIMIENTO_SALIDA_LINEA MSL
                                            INNER JOIN MOVIMIENTO_SALIDA MS ON MS.ID_MOVIMIENTO_SALIDA = MSL.ID_MOVIMIENTO_SALIDA
                                            INNER JOIN PEDIDO_SALIDA PS ON PS.ID_PEDIDO_SALIDA = MSL.ID_PEDIDO_SALIDA
                                            WHERE EXPEDICION_SAP = '" . $expedicionSAP . "' AND
                                            (
                                                (MS.TIPO_MOVIMIENTO = 'MaterialRechazadoAnuladoEnEntradasAProveedor') OR
                                                (MS.TIPO_MOVIMIENTO = 'MaterialEstropeadoAProveedor') OR
                                                (MS.TIPO_MOVIMIENTO = 'TrasladoOMConstruccion') OR
                                                ( (MS.TIPO_MOVIMIENTO = 'TraspasoEntreAlmacenesNoEstropeado') AND (PS.TIPO_PEDIDO = 'Traslado') AND (PS.TIPO_TRASLADO = 'Desconectado') )
                                            )";
                $resultMovimientosLineas = $bd->ExecSQL($sqlMovimientosLineas);
                while ($rowMovimientosLinea = $bd->SigReg($resultMovimientosLineas)):

                    //ACTUALIZO LA CABECERA DEL MOVIMIENTO
                    $movimiento->actualizarEstadoMovimientoSalida($rowMovimientosLinea->ID_MOVIMIENTO_SALIDA);

                endwhile; //BUCLE LINEAS DE MOVIMIENTO DE SALIDA

                //SI LA ORDEN ES CON BULTOS ACTUALIZO EL ESTADO DEL BULTO
                if ($rowOrdenTransporte->CON_BULTOS == 1):
                    //BUSCAMOS LOS BULTOS
                    $sqlBultosLineas    = "SELECT DISTINCT MSL.ID_BULTO
                                            FROM MOVIMIENTO_SALIDA_LINEA MSL
                                            INNER JOIN MOVIMIENTO_SALIDA MS ON MS.ID_MOVIMIENTO_SALIDA = MSL.ID_MOVIMIENTO_SALIDA
                                            INNER JOIN PEDIDO_SALIDA PS ON PS.ID_PEDIDO_SALIDA = MSL.ID_PEDIDO_SALIDA
                                            WHERE EXPEDICION_SAP = '" . $expedicionSAP . "' AND
                                            (
                                                (MS.TIPO_MOVIMIENTO = 'MaterialRechazadoAnuladoEnEntradasAProveedor') OR
                                                (MS.TIPO_MOVIMIENTO = 'MaterialEstropeadoAProveedor') OR
                                                (MS.TIPO_MOVIMIENTO = 'TrasladoOMConstruccion') OR
                                                ( (MS.TIPO_MOVIMIENTO = 'TraspasoEntreAlmacenesNoEstropeado') AND (PS.TIPO_PEDIDO = 'Traslado') AND (PS.TIPO_TRASLADO = 'Desconectado') )
                                            )";
                    $resultBultosLineas = $bd->ExecSQL($sqlBultosLineas);
                    while ($rowBultosLinea = $bd->SigReg($resultBultosLineas)):

                        //ACTUALIZAMOS CABECERA BULTO
                        $orden_preparacion->ActualizarEstadoBulto($rowBultosLinea->ID_BULTO);

                    endwhile; //BUCLE LINEAS DE MOVIMIENTO DE SALIDA
                endif;

                /************************************ HABILITO TRIGGERS IMPLICADOS ************************************/
                $sqlHabilitarTriggers = "SET @Trigger_MOVIMIENTO_SALIDA_LINEA_UPDATE_Deshabilitado = NULL";
                $bd->ExecSQL($sqlHabilitarTriggers);
                /********************************** FIN HABILITO TRIGGERS IMPLICADOS **********************************/
            endif;
        endif;
        //FIN SI NO HAY ERRORES EN LA EXPEDICION, HAGO LA LLAMADA A SAP

        //DECLARO UN ARRAY PARA DEVOLVER DATOS
        $arrDevuelto                                     = array();
        $arrDevuelto['errores']                          = $strError;
        $arrDevuelto['error_expedidion_SAP']             = $hayErrorExpedicionSAP;
        $arrDevuelto['error_transmision_expedidion_SAP'] = $hayErrorTransmisionExpedicionSAP;
        $arrDevuelto['resultado']                        = $resultado;

        //DEVUELVO EL ARRAY
        return $arrDevuelto;
    }

    /**
     * @param $expedicionSAP EXPEDICION SAP A CANCELAR SU TRANSMISION A SAP
     * FUNCION UTILIZADA PARA ANULAR LA TRANSMISION A SAP UNA EXPEDICION SAP
     */
    function cancelar_transmision_expedicionSAP_a_SAP($version, $expedicionSAP)
    {
        global $bd;
        global $auxiliar;
        global $administrador;
        global $html;
        global $comp;
        global $sap;
        global $pedido;
        global $albaran;
        global $movimiento;
        global $orden_preparacion;
        global $orden_trabajo;
        global $exp_SAP;
        global $mat;

        //RECUPERO LA VARIABLE GLOBAL DE TEXTO ERROR
        global $strError;
        $strError = "";

        //QUITO ESPACIOS A LA EXPEDICION SAP
        $expedicionSAP = trim( (string)$expedicionSAP);

        //COMPRUEBO QUE ESTE RELLENO LA EXPEDICION SAP
        unset($arr_tx);
        $arr_tx[0]["err"]   = $auxiliar->traduce("Nº Expedicion SAP", $administrador->ID_IDIOMA);
        $arr_tx[0]["valor"] = $expedicionSAP;
        $comp->ComprobarTexto($arr_tx, "CampoSinRellenar");

        //VARIABLE PARA SABER SI SE PRODUCE UN ERROR EN LA CANCELACION DE LA EXPEDICION SAP
        $hayErrorCancelacionExpedicionSAP = false;

        //VARIABLE PARA SABER SI SE PRODUCE UN ERROR EN LA TRANSMISION DE LA CANCELACION DE LA EXPEDICION SAP
        $hayErrorTransmisionCancelacionExpedicionSAP = false;

        //COMPRUEBO QUE LA EXPEDICION SAP SEA CORRECTA
        $arr = explode("_", (string)$expedicionSAP);
        if ((count( (array)$arr) != 2) && (count( (array)$arr) != 3) && (count( (array)$arr) != 4)):
            $hayErrorCancelacionExpedicionSAP = true;
            $strError                         = $strError . $auxiliar->traduce("La expedición SAP introducida es incorrecta", $administrador->ID_IDIOMA) . ".<br>";
        endif;
        //$html->PagErrorCondicionado( ( (count($arr) != 2) && (count($arr) != 3) && (count($arr) != 4) ), "==", true, "ExpedicionSAPIncorrecta");

        //BUSCO LA ORDEN DE RECOGIDA
        $NotificaErrorPorEmail = "No";
        $rowOrdenTransporte    = $bd->VerReg("EXPEDICION", "ID_EXPEDICION", $exp_SAP->getIdOrdenRecogida($version, $expedicionSAP), "No");
        unset($NotificaErrorPorEmail);
        if ($rowOrdenTransporte == false):
            $hayErrorCancelacionExpedicionSAP = true;
            $strError                         = $strError . $auxiliar->traduce("La expedición SAP introducida no existe", $administrador->ID_IDIOMA) . ".<br>";
        endif;

        //EXTRAIGO EL ID PEDIDO DE SALIDA
        $idPedidoSalida = $exp_SAP->getIdPedidoSalidaSGA($version, $expedicionSAP);
        if ($idPedidoSalida == NULL):
            $hayErrorCancelacionExpedicionSAP = true;
            $strError                         = $strError . $auxiliar->traduce("La expedición SAP introducida es incorrecta", $administrador->ID_IDIOMA) . ".<br>";
        endif;

        //BUSCO EL PEDIDO
        $rowPedido = $bd->VerReg("PEDIDO_SALIDA", "ID_PEDIDO_SALIDA", $idPedidoSalida, "No");

        //EXTRAIGO EL PEDIDO ENVIADO A SAP
        $pedidoEnviadoSAP = $exp_SAP->getPedidoSalidaSAP($rowOrdenTransporte->VERSION, $expedicionSAP);

        if ($rowPedido == false):
            $hayErrorCancelacionExpedicionSAP = true;
            $strError                         = $strError . $auxiliar->traduce("La expedición SAP introducida no existe", $administrador->ID_IDIOMA) . ".<br>";
        endif;

        //COMPRUEBO QUE TODAS LAS LINEAS HAYAN SIDO ENVIADAS A SAP
        $numLineas         = $bd->NumRegsTabla("MOVIMIENTO_SALIDA_LINEA", "EXPEDICION_SAP = '" . $expedicionSAP . "' AND LINEA_ANULADA = 0 AND BAJA = 0");
        $numLineasEnviadas = $bd->NumRegsTabla("MOVIMIENTO_SALIDA_LINEA", "EXPEDICION_SAP = '" . $expedicionSAP . "' AND LINEA_ANULADA = 0 AND BAJA = 0 AND ENVIADO_SAP = 1");
        $html->PagErrorCondicionado($numLineas, "==", 0, "ExpedicionSAPSinLineas");
        if (substr( (string) $pedidoEnviadoSAP, 0, 3) != "SGA"):
            if ($numLineasEnviadas == 0):
                $hayErrorCancelacionExpedicionSAP = true;
                $strError                         = $strError . $auxiliar->traduce("La expedición SAP seleccionada no ha sido enviada a SAP", $administrador->ID_IDIOMA) . ".<br>";
            endif;
        endif;

        //ARRAY CON LOS PEDIDOS IMPLICADOS
        $arrayLineasPedidosInvolucradas = array();

        //RECORRO LAS LINEAS DE MOVIMIENTO SALIDA
        $sqlLineas    = "SELECT * FROM MOVIMIENTO_SALIDA_LINEA WHERE EXPEDICION_SAP = '" . $expedicionSAP . "' FOR UPDATE";
        $resultLineas = $bd->ExecSQL($sqlLineas);

        //ARRAY DE OBJETOS A ACTUALIZAR DESPUES DEL WHILE
        $arrMovimientosActualizar  = array();
        $arrBultosActualizar       = array();
        $arrPedidosLineaActualizar = array();

        /************************************ DESHABILITO TRIGGERS IMPLICADOS ************************************/
        $sqlDeshabilitarTriggers = "SET @Trigger_MOVIMIENTO_SALIDA_LINEA_UPDATE_Deshabilitado = 1";
        $bd->ExecSQL($sqlDeshabilitarTriggers);
        /********************************** FIN DESHABILITO TRIGGERS IMPLICADOS **********************************/

        while ($rowLinea = $bd->SigReg($resultLineas)):

            //GUARDO LOS PEDIDOS DE SALIDA INVOLUCRADOS
            if (!in_array($rowLinea->ID_PEDIDO_SALIDA_LINEA, (array) $arrayLineasPedidosInvolucradas)):
                $arrayLineasPedidosInvolucradas[] = $rowLinea->ID_PEDIDO_SALIDA_LINEA;
            endif;

            //COMPRUEBO QUE TENGA PERMISOS DE ESCRITURA EN EL ALMACEN ORIGEN
            $html->PagErrorCondicionado($administrador->comprobarAlmacenPermiso($rowLinea->ID_ALMACEN, "Escritura"), "==", false, "SinPermisosSubzona");

            //COMPRUEBO EL ESTADO DE LA LINEA
            if (($rowLinea->ESTADO != 'Transmitido a SAP') && ($rowLinea->ESTADO != 'Expedido') && ($rowLinea->ESTADO != 'En Tránsito')):
                $hayErrorCancelacionExpedicionSAP = true;
                $strError                         = $strError . $auxiliar->traduce("El estado de la línea es incorrecto para realizar la operación", $administrador->ID_IDIOMA) . ".<br>";
            endif;

            //COMPRUEBO SI LA LINEA PERTENECE A BULTO Y LA ORDEN ES SIN BULTOS O VICEVERSA, SI ES ASI ERROR
            if ($rowOrdenTransporte->CON_BULTOS == 0):
                if ($rowLinea->ID_BULTO != NULL):
                    $hayErrorCancelacionExpedicionSAP = true;
                    $strError                         = $strError . $auxiliar->traduce("La linea seleccionada pertenece a un bulto", $administrador->ID_IDIOMA) . ".<br>";
                endif;
            elseif ($rowOrdenTransporte->CON_BULTOS == 1):
                if ($rowLinea->ID_BULTO == NULL):
                    $hayErrorCancelacionExpedicionSAP = true;
                    $strError                         = $strError . $auxiliar->traduce("La línea seleccionada no pertenece a un bulto", $administrador->ID_IDIOMA) . ".<br>";
                endif;

                //BUSCO EL BULTO
                $rowBulto = $bd->VerReg("BULTO", "ID_BULTO", $rowLinea->ID_BULTO);

                //COMPRUEBO EL ESTADO DEL BULTO
                if (($rowBulto->ESTADO != 'En Transmision') && ($rowBulto->ESTADO != 'Pdte. de Embarcar') && ($rowBulto->ESTADO != 'Embarcado') && ($rowBulto->ESTADO != 'Expedido')):
                    $hayErrorCancelacionExpedicionSAP = true;
                    $strError                         = $strError . $auxiliar->traduce("El estado del bulto es incorrecto para realizar la operación", $administrador->ID_IDIOMA) . ".<br>";
                endif;
            endif;

            //COMPRUEBO QUE NO HAYA LINEAS PREPARANDOSE
            if ($rowLinea->CANTIDAD == 0):
                $hayErrorCancelacionExpedicionSAP = true;
                $strError                         = $strError . $auxiliar->traduce("La linea no ha sido preparada todavía", $administrador->ID_IDIOMA) . ".<br>";
            endif;

            //COMPRUEBO QUE NO HAYA LINEAS ANULADAS
            if ($rowLinea->LINEA_ANULADA == 1):
                $hayErrorCancelacionExpedicionSAP = true;
                $strError                         = $strError . $auxiliar->traduce("Esta intentando realizar operaciones con lineas anuladas", $administrador->ID_IDIOMA) . ".<br>";
            endif;

            //COMPRUEBO QUE NO HAYA LINEAS DADAS DE BAJA
            if ($rowLinea->BAJA == 1):
                $hayErrorCancelacionExpedicionSAP = true;
                $strError                         = $strError . $auxiliar->traduce("Esta intentando realizar operaciones con lineas dadas de baja", $administrador->ID_IDIOMA) . ".<br>";
            endif;

            //COMPRUEBO QUE NO HAYA LINEAS SIN ENVIAR A SAP
            if ($rowPedido->PEDIDO_SAP != ''):
                if ($rowLinea->ENVIADO_SAP == 0):
                    $hayErrorCancelacionExpedicionSAP = true;
                    $strError                         = $strError . $auxiliar->traduce("Está intentando anular líneas de expedicion SAP aun no enviadas a SAP", $administrador->ID_IDIOMA) . ".<br>";
                endif;
            endif;

            //BUSCO EL MOVIMIENTO DE SALIDA DE LA LINEA
            $rowMovimientoSalida = $bd->VerReg("MOVIMIENTO_SALIDA", "ID_MOVIMIENTO_SALIDA", $rowLinea->ID_MOVIMIENTO_SALIDA, "No");

            //ACCIONES ESPECIFICAS EN FUNCION DEL TIPO DE MOVIMIENTO EXPEDIDO
            if (
                (($rowLinea->ESTADO == 'Expedido') || ($rowLinea->ESTADO == 'En Tránsito')) &&
                (
                    ($rowMovimientoSalida->TIPO_MOVIMIENTO == 'TraspasoEntreAlmacenesNoEstropeado') ||
                    ($rowMovimientoSalida->TIPO_MOVIMIENTO == 'TrasladoOMConstruccion') ||
                    ($rowMovimientoSalida->TIPO_MOVIMIENTO == 'IntraCentroFisico') ||
                    ($rowMovimientoSalida->TIPO_MOVIMIENTO == 'InternoGama') ||
                    ($rowMovimientoSalida->TIPO_MOVIMIENTO == 'LogisticaInversaConPreparacion') ||
                    ($rowMovimientoSalida->TIPO_MOVIMIENTO == 'TraspasoEntreAlmacenesEstropeado')
                )
            ):
                //ACTUALIZO LA CANTIDAD PENDIENTE DE RECEPCIONAR EN DESTINO (LINEA MOVIMIENTO SALIDA)
                $sqlUpdate = "UPDATE MOVIMIENTO_SALIDA_LINEA SET
                                CANTIDAD_PENDIENTE_DE_RECEPCIONAR_EN_DESTINO = 0
                                WHERE ID_MOVIMIENTO_SALIDA_LINEA = $rowLinea->ID_MOVIMIENTO_SALIDA_LINEA";
                $bd->ExecSQL($sqlUpdate);

                //ACTUALIZO LA CANTIDAD PENDIENTE DE RECEPCIONAR EN DESTINO (LINEA ALBARAN)
                $sqlUpdate = "UPDATE ALBARAN_LINEA SET
                                CANTIDAD_PENDIENTE_DE_RECEPCIONAR_EN_DESTINO = CANTIDAD_PENDIENTE_DE_RECEPCIONAR_EN_DESTINO - $rowLinea->CANTIDAD
                                WHERE ID_ALBARAN_LINEA = $rowLinea->ID_ALBARAN_LINEA";
                $bd->ExecSQL($sqlUpdate);
            elseif (
                (($rowLinea->ESTADO == 'Expedido') || ($rowLinea->ESTADO == 'En Tránsito')) &&
                (
                    ($rowMovimientoSalida->TIPO_MOVIMIENTO == 'ComponentesAProveedor') ||
                    ($rowMovimientoSalida->TIPO_MOVIMIENTO == 'MaterialEstropeadoAProveedor')
                )
            ): //DOY DE ALTA EL MATERIAL EN LA UBICACION DE PROVEEDOR

                //BUSCO EL MATERIAL UBICACION DESTINO, UBICACION DE PROVEEDOR
                $rowMatUbiProveedor = $bd->VerRegRest("MATERIAL_UBICACION", "ID_MATERIAL = $rowLinea->ID_MATERIAL AND ID_UBICACION = $rowLinea->ID_UBICACION_DESTINO AND ID_MATERIAL_FISICO " . ($rowLinea->ID_MATERIAL_FISICO == NULL ? 'IS NULL' : "= $rowLinea->ID_MATERIAL_FISICO") . " AND ID_TIPO_BLOQUEO " . ($rowLinea->ID_TIPO_BLOQUEO == NULL ? 'IS NULL' : "= $rowLinea->ID_TIPO_BLOQUEO") . " AND ID_ORDEN_TRABAJO_MOVIMIENTO " . ($rowLinea->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? 'IS NULL' : "= $rowLinea->ID_ORDEN_TRABAJO_MOVIMIENTO") . " AND ID_INCIDENCIA_CALIDAD " . ($rowLinea->ID_INCIDENCIA_CALIDAD == NULL ? 'IS NULL' : "= $rowLinea->ID_INCIDENCIA_CALIDAD"), "No");

                //COMPRUEBO QUE EXISTE EL MATERIAL UBICACION DEL PROVEEDOR
                $html->PagErrorCondicionado($rowMatUbiProveedor, "==", false, "MaterialUbicacionProveedorNoExiste");

                //COMPRUEBO QUE HAYA STOCK SUFICIENTE
                $html->PagErrorCondicionado($rowMatUbiProveedor->STOCK_TOTAL, "<", $rowLinea->CANTIDAD, "StockInsuficienteEnProveedor");

                //DECREMENTO MATERIAL UBICACION (EN PROVEEDOR)
                $sql = "UPDATE MATERIAL_UBICACION SET
                            STOCK_TOTAL = STOCK_TOTAL - $rowLinea->CANTIDAD
                            , STOCK_OK = STOCK_OK - " . ($rowLinea->ID_TIPO_BLOQUEO == NULL ? $rowLinea->CANTIDAD : 0) . "
                            , STOCK_BLOQUEADO = STOCK_BLOQUEADO - " . ($rowLinea->ID_TIPO_BLOQUEO == NULL ? 0 : $rowLinea->CANTIDAD) . "
                            WHERE ID_MATERIAL_UBICACION = $rowMatUbiProveedor->ID_MATERIAL_UBICACION";
                $bd->ExecSQL($sql);

                //GENERO UN ASIENTO EN PROVEEDOR PARA DECREMENTAR EL STOCK DE COMPONENTES
                $sqlInsert = "INSERT INTO ASIENTO SET
                                    ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                    , ID_MATERIAL = $rowLinea->ID_MATERIAL
                                    , TIPO_LOTE = '" . $rowLinea->TIPO_LOTE . "'
                                    , ID_MATERIAL_FISICO = " . ($rowLinea->ID_MATERIAL_FISICO == NULL ? 'NULL' : "$rowLinea->ID_MATERIAL_FISICO") . "
                                    , ID_UBICACION = $rowLinea->ID_UBICACION_DESTINO
                                    , FECHA = '" . date("Y-m-d H:i:s") . "'
                                    , CANTIDAD = " . ($rowLinea->CANTIDAD * -1) . "
                                    , STOCK_OK = " . ($rowLinea->CANTIDAD * -1) . "
                                    , STOCK_BLOQUEADO = 0
                                    , ID_TIPO_BLOQUEO = " . ($rowLinea->ID_TIPO_BLOQUEO == NULL ? 'NULL' : "$rowLinea->ID_TIPO_BLOQUEO") . "
                                    , ID_ORDEN_TRABAJO_MOVIMIENTO = " . ($rowLinea->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? 'NULL' : "$rowLinea->ID_ORDEN_TRABAJO_MOVIMIENTO") . "
                                    , ID_MOVIMIENTO_SALIDA_LINEA = $rowLinea->ID_MOVIMIENTO_SALIDA_LINEA
                                    , ID_INCIDENCIA_CALIDAD = " . ($rowLinea->ID_INCIDENCIA_CALIDAD == NULL ? 'NULL' : "$rowLinea->ID_INCIDENCIA_CALIDAD") . "
                                    , OBSERVACIONES = ''
                                    , TIPO_ASIENTO = '" . ($rowMovimientoSalida->TIPO_MOVIMIENTO == 'ComponentesAProveedor' ? 'Baja Componentes en Proveedor' : 'Baja Material Estropeado en Proveedor') . "'";
                $bd->ExecSQL($sqlInsert);
            endif;
            //FIN ACCIONES ESPECIFICAS EN FUNCION DEL TIPO DE MOVIMIENTO EXPEDIDO

            if (($rowLinea->ESTADO == 'Expedido') || ($rowLinea->ESTADO == 'En Tránsito')): //SI LA LINEA HA SIDO EXPEDIDA INCREMENTO EL STOCK EN EL ALMACEN ORIGEN
                //SI ES UN MATERIAL FISICO SERIABLE COMPRUEBO QUE NO EXISTA EN EL SISTEMA NI ESTE EN TRANSITO
                if ($rowLinea->ID_MATERIAL_FISICO != NULL):
                    $GLOBALS["NotificaErrorPorEmail"] = "No";
                    $rowMatFis                        = $bd->VerReg("MATERIAL_FISICO", "ID_MATERIAL_FISICO", $rowLinea->ID_MATERIAL_FISICO, "No");
                    unset($GLOBALS["NotificaErrorPorEmail"]);
                    if ($rowMatFis == false):
                        $hayErrorCancelacionExpedicionSAP = true;
                        $strError                         = $strError . $auxiliar->traduce("El numero de serie/lote no existe", $administrador->ID_IDIOMA) . ".<br>";
                    endif;

                    if ($rowMatFis->TIPO_LOTE == 'serie'):    //SI ES UN MATERIAL SERIABLE
                        $numMaterialSeriable = $bd->NumRegsTabla("MATERIAL_UBICACION", "ID_MATERIAL_FISICO = $rowLinea->ID_MATERIAL_FISICO AND ACTIVO = 1");
                        if ($numMaterialSeriable > 0):    //EXISTE EN EL SISTEMA
                            $hayErrorCancelacionExpedicionSAP = true;
                            $strError                         = $strError . $auxiliar->traduce("El numero de serie ya existe en el sistema", $administrador->ID_IDIOMA) . ". " . $auxiliar->traduce("Numero de serie", $administrador->ID_IDIOMA) . ": " . $rowMatFis->NUMERO_SERIE_LOTE . "<br>";
                        else:
                            if ($mat->MaterialFisicoEnTransito($rowLinea->ID_MATERIAL_FISICO, $rowLinea->ID_MOVIMIENTO_SALIDA_LINEA) == true):    //ESTA EN TRANSITO DENTRO DEL SISTEMA
                                $hayErrorCancelacionExpedicionSAP = true;
                                $strError                         = $strError . $auxiliar->traduce("El numero de serie esta en transito dentro del sistema", $administrador->ID_IDIOMA) . ". " . $auxiliar->traduce("Numero de serie", $administrador->ID_IDIOMA) . ": " . $rowMatFis->NUMERO_SERIE_LOTE . "<br>";
                            endif;
                        endif;
                    endif;    //FIN SI ES UN MATERIAL SERIABLE
                endif;
                //FIN SI ES UN MATERIAL FISICO SERIABLE COMPRUEBO QUE NO EXISTA EN EL SISTEMA NI ESTE EN TRANSITO

                //BUSCO SI SE HICIERON TRANSFERENCIAS DESDE LA UBICACION DE SM (SALIDA) A LA UBICACION DE EB (EMBARQUE) PARA INCREMENTAR EL STOCK EN SM O EB
                $sqlTransf    = "SELECT *
                                FROM MOVIMIENTO_TRANSFERENCIA
                                WHERE ID_MOVIMIENTO_SALIDA = $rowLinea->ID_MOVIMIENTO_SALIDA AND ID_MOVIMIENTO_SALIDA_LINEA = $rowLinea->ID_MOVIMIENTO_SALIDA_LINEA AND TIPO = 'Embarque' AND BAJA = 0";
                $resultTransf = $bd->ExecSQL($sqlTransf);
                if ($bd->NumRegs($resultTransf) == 0):  //SON ORDENES DE TRANSPORTE ANTIGUAS, EL MATERIAL SE EXPIDE DIRECTAMENTE DE SM
                    //BUSCO LA UBICACION DE SALIDA DEL ALMACEN DE ORIGEN
                    $rowUbiSalida = $bd->VerRegRest("UBICACION", "ID_ALMACEN = $rowLinea->ID_ALMACEN AND TIPO_UBICACION = 'Salida'", "No");

                    //COMPRUEBO QUE LA UBICACION EXISTE
                    if ($rowUbiSalida == false):
                        $hayErrorCancelacionExpedicionSAP = true;
                        $strError                         = $strError . $auxiliar->traduce("No hay una ubicacion de salida donde reubicar el material", $administrador->ID_IDIOMA) . ".<br>";
                    else:
                        //BUSCO LA UBICACION DONDE INCREMENTAR EL MATERIAL
                        $rowMatUbiDestino = $bd->VerRegRest("MATERIAL_UBICACION", "ID_MATERIAL = $rowLinea->ID_MATERIAL AND ID_UBICACION = $rowUbiSalida->ID_UBICACION AND ID_MATERIAL_FISICO " . ($rowLinea->ID_MATERIAL_FISICO == NULL ? 'IS NULL' : "= $rowLinea->ID_MATERIAL_FISICO") . " AND ID_TIPO_BLOQUEO " . ($rowLinea->ID_TIPO_BLOQUEO == NULL ? 'IS NULL' : "= $rowLinea->ID_TIPO_BLOQUEO") . " AND ID_ORDEN_TRABAJO_MOVIMIENTO " . ($rowLinea->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? 'IS NULL' : "= $rowLinea->ID_ORDEN_TRABAJO_MOVIMIENTO") . " AND ID_INCIDENCIA_CALIDAD " . ($rowLinea->ID_INCIDENCIA_CALIDAD == NULL ? 'IS NULL' : "= $rowLinea->ID_INCIDENCIA_CALIDAD"), "No");
                        if ($rowMatUbiDestino == false):
                            //CREO MATERIAL UBICACION SI NO EXISTE
                            $sql = "INSERT INTO MATERIAL_UBICACION SET
                                        ID_MATERIAL = $rowLinea->ID_MATERIAL
                                        , ID_MATERIAL_FISICO = " . ($rowLinea->ID_MATERIAL_FISICO == NULL ? 'NULL' : "$rowLinea->ID_MATERIAL_FISICO") . "
                                        , ID_UBICACION = $rowUbiSalida->ID_UBICACION
                                        , ID_TIPO_BLOQUEO = " . ($rowLinea->ID_TIPO_BLOQUEO == NULL ? 'NULL' : "$rowLinea->ID_TIPO_BLOQUEO") . "
                                        , ID_ORDEN_TRABAJO_MOVIMIENTO = " . ($rowLinea->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? 'NULL' : "$rowLinea->ID_ORDEN_TRABAJO_MOVIMIENTO") . "
                                        , ID_INCIDENCIA_CALIDAD = " . ($rowLinea->ID_INCIDENCIA_CALIDAD == NULL ? 'NULL' : "$rowLinea->ID_INCIDENCIA_CALIDAD");
                            $bd->ExecSQL($sql);
                            $idMatUbi = $bd->IdAsignado();
                        else:
                            $idMatUbi = $rowMatUbiDestino->ID_MATERIAL_UBICACION;
                        endif;

                        //INCREMENTO MATERIAL UBICACION (EN UBICACION DE TIPO SALIDA)
                        $sql = "UPDATE MATERIAL_UBICACION SET
                                    STOCK_TOTAL = STOCK_TOTAL + $rowLinea->CANTIDAD
                                    , STOCK_OK = STOCK_OK + " . ($rowLinea->ID_TIPO_BLOQUEO == NULL ? $rowLinea->CANTIDAD : 0) . "
                                    , STOCK_BLOQUEADO = STOCK_BLOQUEADO + " . ($rowLinea->ID_TIPO_BLOQUEO == NULL ? 0 : $rowLinea->CANTIDAD) . "
                                    WHERE ID_MATERIAL_UBICACION = $idMatUbi";
                        $bd->ExecSQL($sql);
                    endif;
                else:   //SON ORDENES DE TRANSPORTE NUEVAS, EL MATERIAL SE EXPIDE DIRECTAMENTE DE EB PREVIO PASO POR SM ANTES DE TRANSMITIR A SAP
                    //RECORRO LAS TRANSFERENCIAS DE ESTA LINEA DE MOVIMIENTO DE SALIDA
                    while ($rowTransf = $bd->SigReg($resultTransf)):
                        //BUSCO LA UBICACION DONDE INCREMENTAR EL MATERIAL
                        $rowMatUbiDestino = $bd->VerRegRest("MATERIAL_UBICACION", "ID_MATERIAL = $rowTransf->ID_MATERIAL AND ID_UBICACION = $rowTransf->ID_UBICACION_DESTINO AND ID_MATERIAL_FISICO " . ($rowTransf->ID_MATERIAL_FISICO == NULL ? 'IS NULL' : "= $rowTransf->ID_MATERIAL_FISICO") . " AND ID_TIPO_BLOQUEO " . ($rowTransf->ID_TIPO_BLOQUEO == NULL ? 'IS NULL' : "= $rowTransf->ID_TIPO_BLOQUEO") . " AND ID_ORDEN_TRABAJO_MOVIMIENTO " . ($rowTransf->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? 'IS NULL' : "= $rowTransf->ID_ORDEN_TRABAJO_MOVIMIENTO") . " AND ID_INCIDENCIA_CALIDAD " . ($rowTransf->ID_INCIDENCIA_CALIDAD == NULL ? 'IS NULL' : "= $rowTransf->ID_INCIDENCIA_CALIDAD"), "No");
                        if ($rowMatUbiDestino == false):
                            //CREO MATERIAL UBICACION SI NO EXISTE
                            $sql = "INSERT INTO MATERIAL_UBICACION SET
                                        ID_MATERIAL = $rowTransf->ID_MATERIAL
                                        , ID_MATERIAL_FISICO = " . ($rowTransf->ID_MATERIAL_FISICO == NULL ? 'NULL' : "$rowTransf->ID_MATERIAL_FISICO") . "
                                        , ID_UBICACION = $rowTransf->ID_UBICACION_DESTINO
                                        , ID_TIPO_BLOQUEO = " . ($rowTransf->ID_TIPO_BLOQUEO == NULL ? 'NULL' : "$rowTransf->ID_TIPO_BLOQUEO") . "
                                        , ID_ORDEN_TRABAJO_MOVIMIENTO = " . ($rowTransf->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? 'NULL' : "$rowTransf->ID_ORDEN_TRABAJO_MOVIMIENTO") . "
                                        , ID_INCIDENCIA_CALIDAD = " . ($rowTransf->ID_INCIDENCIA_CALIDAD == NULL ? 'NULL' : "$rowTransf->ID_INCIDENCIA_CALIDAD");
                            $bd->ExecSQL($sql);
                            $idMatUbi = $bd->IdAsignado();
                        else:
                            $idMatUbi = $rowMatUbiDestino->ID_MATERIAL_UBICACION;
                        endif;

                        //INCREMENTO MATERIAL UBICACION (EN UBICACION DE TIPO EMBARQUE)
                        $sql = "UPDATE MATERIAL_UBICACION SET
                                    STOCK_TOTAL = STOCK_TOTAL + $rowTransf->CANTIDAD
                                    , STOCK_OK = STOCK_OK + " . ($rowTransf->ID_TIPO_BLOQUEO == NULL ? $rowTransf->CANTIDAD : 0) . "
                                    , STOCK_BLOQUEADO = STOCK_BLOQUEADO + " . ($rowTransf->ID_TIPO_BLOQUEO == NULL ? 0 : $rowTransf->CANTIDAD) . "
                                    WHERE ID_MATERIAL_UBICACION = $idMatUbi";
                        $bd->ExecSQL($sql);
                    endwhile;
                    //FIN RECORRO LAS TRANSFERENCIAS DE ESTA LINEA DE MOVIMIENTO DE SALIDA
                endif;
                //FIN BUSCO SI SE HICIERON TRANSFERENCIAS DESDE LA UBICACION DE SM (SALIDA) A LA UBICACION DE EB (EMBARQUE)
            endif;
            //FIN SI LA LINEA HA SIDO EXPEDIDA INCREMENTO EL STOCK EN EL ALMACEN ORIGEN

            //ACTUALIZO LA LINEA SIN EL ALBARAN Y ALBARAN LINEA CORRESPONDIENTES, SOLO EN EL CASO DE ESTAR GENERADOS
            $num = $bd->NumRegsTabla("ALBARAN", "ID_EXPEDICION = $rowLinea->ID_EXPEDICION AND BAJA = 0");
            if (($num > 0) && ($rowLinea->ID_ALBARAN_LINEA != NULL) && ($rowMovimientoSalida->TIPO_MOVIMIENTO != 'MaterialEstropeadoAProveedor')):
                $albaran->quitar_linea($rowLinea);

                /************************************ DESHABILITO TRIGGERS IMPLICADOS PORQUE SE HAN HABILITADO DENTRO DE LA FUNCION ************************************/
                $sqlDeshabilitarTriggers = "SET @Trigger_MOVIMIENTO_SALIDA_LINEA_UPDATE_Deshabilitado = 1";
                $bd->ExecSQL($sqlDeshabilitarTriggers);
                /********************************** FIN DESHABILITO TRIGGERS IMPLICADOS PORQUE SE HAN HABILITADO DENTRO DE LA FUNCION **********************************/
            endif;

            //ACCIONES EN FUNCION DE TIPO DE EXPEDICION SAP
            if ($rowOrdenTransporte->VERSION == 'Primera'): //EXPEDICION SAP ANTIGUA
                //NO HACER NADA, LA CANCELACION DE LA EXPEDICION DE LA ORDEN DE TRANSPORTE PONE EL MATERIAL EN SM
            elseif (($rowOrdenTransporte->VERSION == 'Segunda') || ($rowOrdenTransporte->VERSION == 'Tercera') || ($rowOrdenTransporte->VERSION == 'Cuarta')): //EXPEDICION SAP NUEVA
                //BUSCO LAS TRANSFERENCIAS DE EMBARQUE DE LA LINEA PARA MOVER EL MATERIAL DE LA UBICACION DE EMBARQUE A LA UBICACION DE SALIDA
                $sqlTransf    = "SELECT *
                                FROM MOVIMIENTO_TRANSFERENCIA
                                WHERE ID_MOVIMIENTO_SALIDA = $rowLinea->ID_MOVIMIENTO_SALIDA AND ID_MOVIMIENTO_SALIDA_LINEA = $rowLinea->ID_MOVIMIENTO_SALIDA_LINEA AND TIPO = 'Embarque' AND BAJA = 0";
                $resultTransf = $bd->ExecSQL($sqlTransf);
                if ($bd->NumRegs($resultTransf) == 0):
                    if ($rowTransf == false):
                        $hayErrorCancelacionExpedicionSAP = true;
                        $strError                         = $strError . $auxiliar->traduce("ErrorTransmisionSAPLinea", $administrador->ID_IDIOMA) . ".<br>";
                    endif;
                else:
                    while ($rowTransf = $bd->SigReg($resultTransf)):
                        //BUSCO EL MATERIAL UBICACION DE EMBARQUE
                        $rowMatUbi = $bd->VerRegRest("MATERIAL_UBICACION", "ID_MATERIAL = $rowLinea->ID_MATERIAL AND ID_UBICACION = $rowTransf->ID_UBICACION_DESTINO AND ID_MATERIAL_FISICO " . ($rowLinea->ID_MATERIAL_FISICO == NULL ? 'IS NULL' : "= $rowLinea->ID_MATERIAL_FISICO") . " AND ID_TIPO_BLOQUEO " . ($rowLinea->ID_TIPO_BLOQUEO == NULL ? 'IS NULL' : "= $rowLinea->ID_TIPO_BLOQUEO") . " AND ID_ORDEN_TRABAJO_MOVIMIENTO " . ($rowLinea->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? 'IS NULL' : "= $rowLinea->ID_ORDEN_TRABAJO_MOVIMIENTO") . " AND ID_INCIDENCIA_CALIDAD " . ($rowLinea->ID_INCIDENCIA_CALIDAD == NULL ? 'IS NULL' : "= $rowLinea->ID_INCIDENCIA_CALIDAD"));

                        //COMPRUEBO QUE HAYA STOCK SUFICIENTE EN LA UBICACION DE EMBARQUE
                        if ($rowMatUbi->STOCK_TOTAL < $rowLinea->CANTIDAD):
                            $hayErrorAnulacionExpedicionSAP = true;
                            $strError                       = $strError . $auxiliar->traduce("No hay suficiente cantidad de material para anular la línea número", $administrador->ID_IDIOMA) . " " . $rowAlbaranLinea->NUMERO_LINEA . " " . $auxiliar->traduce("del albarán", $administrador->ID_IDIOMA) . " " . $rowAlbaran->ID_ALBARAN . ".<br>";
                        endif;

                        //SI LAS VALIDACIONES SON CORRECTAS PROCEDO A MODIFICAR LAS TRANSFERENCIAS Y LOS STOCK
                        if ($hayErrorAnulacionExpedicionSAP == false):
                            //DOY DE BAJA LA TRANSFERENCIA DE SM A EMBARQUE DE TIPO 'Embarque'
                            $sqlUpdate = "UPDATE MOVIMIENTO_TRANSFERENCIA SET
                                            ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                            , BAJA = 1
                                            WHERE ID_MOVIMIENTO_TRANSFERENCIA = $rowTransf->ID_MOVIMIENTO_TRANSFERENCIA";
                            $bd->ExecSQL($sqlUpdate);

                            //DECREMENTO MATERIAL UBICACION (EMBARQUE - EB)
                            $sqlUpdate = "UPDATE MATERIAL_UBICACION SET
                                            STOCK_TOTAL = STOCK_TOTAL - $rowTransf->CANTIDAD
                                            , STOCK_OK = STOCK_OK - " . ($rowLinea->ID_TIPO_BLOQUEO == NULL ? "$rowTransf->CANTIDAD" : 0) . "
                                            , STOCK_BLOQUEADO = STOCK_BLOQUEADO - " . ($rowLinea->ID_TIPO_BLOQUEO == NULL ? 0 : "$rowTransf->CANTIDAD") . "
                                            WHERE ID_MATERIAL_UBICACION = $rowMatUbi->ID_MATERIAL_UBICACION";
                            $bd->ExecSQL($sqlUpdate);

                            //BUSCO EL MATERIAL UBICACION DE SM
                            $rowMatUbiSM = $bd->VerRegRest("MATERIAL_UBICACION", "ID_MATERIAL = $rowLinea->ID_MATERIAL AND ID_UBICACION = $rowTransf->ID_UBICACION_ORIGEN AND ID_MATERIAL_FISICO " . ($rowLinea->ID_MATERIAL_FISICO == NULL ? 'IS NULL' : "= $rowLinea->ID_MATERIAL_FISICO") . " AND ID_TIPO_BLOQUEO " . ($rowLinea->ID_TIPO_BLOQUEO == NULL ? 'IS NULL' : "= $rowLinea->ID_TIPO_BLOQUEO") . " AND ID_ORDEN_TRABAJO_MOVIMIENTO " . ($rowLinea->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? 'IS NULL' : "= $rowLinea->ID_ORDEN_TRABAJO_MOVIMIENTO") . " AND ID_INCIDENCIA_CALIDAD " . ($rowLinea->ID_INCIDENCIA_CALIDAD == NULL ? 'IS NULL' : "= $rowLinea->ID_INCIDENCIA_CALIDAD"), "No");
                            if ($rowMatUbiSM == false):
                                $sqlInsert = "INSERT INTO MATERIAL_UBICACION SET
                                                ID_MATERIAL = $rowLinea->ID_MATERIAL
                                                , ID_UBICACION = $rowTransf->ID_UBICACION_ORIGEN
                                                , ID_MATERIAL_FISICO = " . ($rowLinea->ID_MATERIAL_FISICO == NULL ? 'NULL' : "$rowLinea->ID_MATERIAL_FISICO") . "
                                                , ID_TIPO_BLOQUEO = " . ($rowLinea->ID_TIPO_BLOQUEO == NULL ? 'NULL' : "$rowLinea->ID_TIPO_BLOQUEO") . "
                                                , ID_ORDEN_TRABAJO_MOVIMIENTO = " . ($rowLinea->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? 'NULL' : "$rowLinea->ID_ORDEN_TRABAJO_MOVIMIENTO") . "
                                                , ID_INCIDENCIA_CALIDAD = " . ($rowLinea->ID_INCIDENCIA_CALIDAD == NULL ? 'NULL' : "$rowLinea->ID_INCIDENCIA_CALIDAD");
                                $bd->ExecSQL($sqlInsert);
                                $idMatUbiSM = $bd->IdAsignado();
                            else:
                                $idMatUbiSM = $rowMatUbiSM->ID_MATERIAL_UBICACION;
                            endif;

                            //INCREMENTO MATERIAL UBICACION (SALIDA MATERIAL - SM)
                            $sqlUpdate = "UPDATE MATERIAL_UBICACION SET
                                            STOCK_TOTAL = STOCK_TOTAL + $rowTransf->CANTIDAD
                                            , STOCK_OK = STOCK_OK + " . ($rowLinea->ID_TIPO_BLOQUEO == NULL ? "$rowTransf->CANTIDAD" : 0) . "
                                            , STOCK_BLOQUEADO = STOCK_BLOQUEADO + " . ($rowLinea->ID_TIPO_BLOQUEO == NULL ? 0 : "$rowTransf->CANTIDAD") . "
                                            WHERE ID_MATERIAL_UBICACION = $idMatUbiSM";
                            $bd->ExecSQL($sqlUpdate);

                            //SI LA LINEA TIENE CONTENEDOR ASIGNADO, HABRA QUE INCREMENTARLO YA QUE SE DECREMENTA AL TRANSMITIR A SAP
                            if ($rowLinea->ID_CONTENEDOR_LINEA != NULL):
                                //ACTUALIZO LA CANTIDAD Y PARAMETRO BAJA DEL CONTENEDOR LINEA
                                $sqlUpdate = "UPDATE CONTENEDOR_LINEA SET
                                                STOCK_TOTAL = STOCK_TOTAL + $rowTransf->CANTIDAD
                                                , STOCK_OK = STOCK_OK + " . ($rowLinea->ID_TIPO_BLOQUEO == NULL ? $rowTransf->CANTIDAD : 0) . "
                                                , STOCK_BLOQUEADO = STOCK_BLOQUEADO + " . ($rowLinea->ID_TIPO_BLOQUEO == NULL ? 0 : $rowTransf->CANTIDAD) . "
                                                , BAJA = 0
                                                WHERE ID_CONTENEDOR_LINEA = $rowLinea->ID_CONTENEDOR_LINEA";
                                $bd->ExecSQL($sqlUpdate);
                            endif;
                        endif;
                    endwhile; //BUCLE TRANSFERENCIA DE LINEAS DE MOVIMIENTO DE SALIDA
                endif;
            //FIN BUSCO LAS TRANSFERENCIAS DE LA LINEA PARA DEJAR EL MATERIAL EN LA UBICACION DE SALIDA EN VEZ DE EN LA UBICACION DE EMBARQUE, SOLO PARA LAS NUEVAS EXPEDICIONES SAP DE 3 TOKENS
            else:   //EXPEDICION SAP INCORRECTA
                $hayErrorCancelacionExpedicionSAP = true;
                $strError                         = $strError . $auxiliar->traduce("La expedición SAP introducida es incorrecta", $administrador->ID_IDIOMA) . ".<br>";
            endif;
            //FIN ACCIONES EN FUNCION DE TIPO DE EXPEDICION SAP

            //BUSCO LA ORDEN DE TRANSPORTE
            $NotificaErrorPorEmail       = "No";
            $rowOrdenTransportePrincipal = $bd->VerReg("ORDEN_TRANSPORTE", "ID_ORDEN_TRANSPORTE", $exp_SAP->getIdOrdenTransporte($version, $expedicionSAP), "No");
            unset($NotificaErrorPorEmail);
            if (($rowOrdenTransportePrincipal != false) && ($rowOrdenTransportePrincipal->MODELO_TRANSPORTE == 'Segundo')): //SI EXISTE LA ORDEN DE TRANSPORTE Y ES DE MODELO_TRANSPORTE Segundo
                //SI LA LINEA DEL MOVIMIENTO DE SALIDA PROVIENE DE UNA LINEA DE PEDIDO DE SALIDA
                if ($rowLinea->ID_PEDIDO_SALIDA_LINEA != NULL):

                    //SE ELIMINA LA ENTREGA SALIENTE CORRESPONDIENTE A LA EXPEDICION SAP
                    $sqlExpedicionEntregaSalientePosicion    = "SELECT DISTINCT EESP.ID_EXPEDICION_ENTREGA_SALIENTE 
                                                             FROM EXPEDICION_ENTREGA_SALIENTE_POSICION EESP 
                                                             INNER JOIN EXPEDICION_ENTREGA_SALIENTE EES ON EES.ID_EXPEDICION_ENTREGA_SALIENTE = EESP.ID_EXPEDICION_ENTREGA_SALIENTE 
                                                             WHERE EESP.ID_PEDIDO_SALIDA_LINEA = $rowLinea->ID_PEDIDO_SALIDA_LINEA AND EES.ID_EXPEDICION = $rowOrdenTransporte->ID_EXPEDICION AND EES.ID_ORDEN_TRANSPORTE = $rowOrdenTransportePrincipal->ID_ORDEN_TRANSPORTE AND EES.EXPEDICION_SAP = '$expedicionSAP'";
                    $resultExpedicionEntregaSalientePosicion = $bd->ExecSQL($sqlExpedicionEntregaSalientePosicion);
                    while ($rowExpedicionEntregaSalientePosicion = $bd->SigReg($resultExpedicionEntregaSalientePosicion)):
                        //SE ELIMINAN LAS POSICIONES DE LA ENTREGA SALIENTE
                        $sqlDelete = "DELETE FROM EXPEDICION_ENTREGA_SALIENTE_POSICION 
                                      WHERE ID_EXPEDICION_ENTREGA_SALIENTE = $rowExpedicionEntregaSalientePosicion->ID_EXPEDICION_ENTREGA_SALIENTE";
                        $bd->ExecSQL($sqlDelete);

                        //SE ELIMINA LA ENTREGA SALIENTE
                        $sqlDelete = "DELETE FROM EXPEDICION_ENTREGA_SALIENTE 
                                      WHERE ID_EXPEDICION_ENTREGA_SALIENTE = $rowExpedicionEntregaSalientePosicion->ID_EXPEDICION_ENTREGA_SALIENTE";
                        $bd->ExecSQL($sqlDelete);
                    endwhile;

                    /*
                    //BUSCO SI LA LINEA DE PEDIDO ESTA INCLUIDA EN LA ORDEN DE RECOGIDA BAJO OTRA EXPEDICION SAP Y HA SIDO ENVIADO A SAP
                    $num = $bd->NumRegsTabla("MOVIMIENTO_SALIDA_LINEA", "ID_PEDIDO_SALIDA_LINEA = $rowLinea->ID_PEDIDO_SALIDA_LINEA AND EXPEDICION_SAP <> '" . $expedicionSAP . "' AND LINEA_ANULADA = 0 AND BAJA = 0 AND ENVIADO_SAP = 1");
                    if ($num == 0): //LA LINEA DE PEDIDO SOLO ESTA INCLUIDA EN ESTA EXPEDICION SAP
                        //BUSCO LA ENTREGA SALIENTE DE ESTA LINEA PEDIDO Y EXPEDICION SAP
                        $sqlExpedicionEntregaSalientePosicion = "SELECT EESP.ID_EXPEDICION_ENTREGA_SALIENTE_POSICION, EESP.ID_EXPEDICION_ENTREGA_SALIENTE
                                                                 FROM EXPEDICION_ENTREGA_SALIENTE_POSICION EESP
                                                                 INNER JOIN EXPEDICION_ENTREGA_SALIENTE EES ON EES.ID_EXPEDICION_ENTREGA_SALIENTE = EESP.ID_EXPEDICION_ENTREGA_SALIENTE
                                                                 WHERE EESP.ID_PEDIDO_SALIDA_LINEA = $rowLinea->ID_PEDIDO_SALIDA_LINEA AND EES.ID_EXPEDICION = $rowOrdenTransporte->ID_EXPEDICION AND EES.ID_ORDEN_TRANSPORTE = $rowOrdenTransportePrincipal->ID_ORDEN_TRANSPORTE";
                        $resultExpedicionEntregaSalientePosicion = $bd->ExecSQL($sqlExpedicionEntregaSalientePosicion);
                        $rowExpedicionEntregaSalientePosicion = $bd->SigReg($resultExpedicionEntregaSalientePosicion);

                        if($rowExpedicionEntregaSalientePosicion != false):

                            //ELIMINO LA ENTREGA SALIENTE DE ESTA LINEA PEDIDO
                            $sqlDelete = "DELETE FROM EXPEDICION_ENTREGA_SALIENTE_POSICION 
                                        WHERE ID_EXPEDICION_ENTREGA_SALIENTE_POSICION = $rowExpedicionEntregaSalientePosicion->ID_EXPEDICION_ENTREGA_SALIENTE_POSICION";
                            $bd->ExecSQL($sqlDelete);

                            //CALCULO EL NUMERO DE POSICIONES DE LA ENTREGA SALIENTE CON LA QUE ESTAMOS TRABAJANDO
                            $numPosiciones = $bd->NumRegsTabla("EXPEDICION_ENTREGA_SALIENTE_POSICION", "ID_EXPEDICION_ENTREGA_SALIENTE = $rowExpedicionEntregaSalientePosicion->ID_EXPEDICION_ENTREGA_SALIENTE");
                            if ($numPosiciones == 0): //NO HAY MAS POSICIONES DE ENTREGA SALIENTE RELACIONADAS CON ENTREGA SALIENTE CON LA QUE ESTAMOS TRABAJANDO
                                //ELIMINO LA ENTREGA SALIENTE CON LA QUE ESTAMOS TRABAJANDO
                                $sqlDelete = "DELETE FROM EXPEDICION_ENTREGA_SALIENTE 
                                            WHERE ID_EXPEDICION_ENTREGA_SALIENTE = $rowExpedicionEntregaSalientePosicion->ID_EXPEDICION_ENTREGA_SALIENTE";
                                $bd->ExecSQL($sqlDelete);
                            endif;

                        endif;

                    endif;
                    //FIN LA LINEA DE PEDIDO SOLO ESTA INCLUIDA EN ESTA EXPEDICION SAP
*/

                endif;
            endif;
            //FIN SI EXISTE LA ORDEN DE TRANSPORTE Y ES DE MODELO_TRANSPORTE Segundo
        endwhile; //BUCLE LINEAS DE MOVIMIENTO DE SALIDA

        //ACTUALIZO EL ESTADO DEL PEDIDO A ESTADO EN ENTREGA
        $sqlUpdate = "UPDATE PEDIDO_SALIDA SET ESTADO = 'En Entrega' WHERE ID_PEDIDO_SALIDA = $rowPedido->ID_PEDIDO_SALIDA";
        $bd->ExecSQL($sqlUpdate);

        // LOG MOVIMIENTOS
        $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Expedición", $rowOrdenTransporte->ID_EXPEDICION, "Anulacion expedicion SAP: " . $expedicionSAP);

//        //ARRAY PARA GUARDAR LOS MOVIMIENTOS/BULTOS A ACTUALIZAR
//        $arrPosiblesMovimientosActualizar = array();
//        $arrPosiblesBultosActualizar      = array();

        //RECORRO LAS LINEAS DEL MOVIMIENTO PARA VACIARLAS, EL CAMPO ENVIADO_SAP, EXPEDICION_SAP E ID_EXPEDICION_SAP SE ACTUALIZAN EN LA LIBRERIA SAP
        $sqlLineas    = "SELECT * FROM MOVIMIENTO_SALIDA_LINEA WHERE EXPEDICION_SAP = '" . $expedicionSAP . "' FOR UPDATE";
        $resultLineas = $bd->ExecSQL($sqlLineas);
        while ($rowLinea = $bd->SigReg($resultLineas)):
            //ME GUARDO LAS LINEAS DE PEDIDO DE SALIDA IMPLICADAS
            $arrPedidosLineaActualizar[] = $rowLinea->ID_PEDIDO_SALIDA_LINEA;

            $sqlUpdate = "UPDATE MOVIMIENTO_SALIDA_LINEA SET
                            ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                            , ESTADO = 'Pendiente de Expedir'
                            , ID_ALBARAN = " . ($rowMovimientoSalida->TIPO_MOVIMIENTO == 'MaterialEstropeadoAProveedor' ? $rowLinea->ID_ALBARAN : 'NULL') . "
                            , ID_ALBARAN_LINEA = " . ($rowMovimientoSalida->TIPO_MOVIMIENTO == 'MaterialEstropeadoAProveedor' ? $rowLinea->ID_ALBARAN_LINEA : 'NULL') . "
                            , FECHA_TRANSMISION_A_SAP_REAL = '0000-00-00 00:00:00'
                            , FECHA_TRANSMISION_A_SAP_TEORICA = '0000-00-00 00:00:00'
                            , FECHA_EXPEDICION = '0000-00-00 00:00:00'
                            WHERE ID_MOVIMIENTO_SALIDA_LINEA = $rowLinea->ID_MOVIMIENTO_SALIDA_LINEA";
            $bd->ExecSQL($sqlUpdate);

            //BUSCO SI EXISTE UNA LINEA SIMILAR A LA QUE REAGRUPAR LA CANTIDAD DE ESTA LINEA
            $GLOBALS["NotificaErrorPorEmail"] = "No";
            $rowMovimientoSalidaLineaSimilar  = $bd->VerRegRest("MOVIMIENTO_SALIDA_LINEA", "LINEA_ANULADA = 0 AND
                BAJA = 0 AND
                ESTADO = 'Pendiente de Expedir' AND
                ID_CONTENEDOR_LINEA " . ($rowLinea->ID_CONTENEDOR_LINEA == NULL ? 'IS NULL' : "= $rowLinea->ID_CONTENEDOR_LINEA") . " AND
                ID_BULTO_LINEA " . ($rowLinea->ID_BULTO_LINEA == NULL ? 'IS NULL' : "= $rowLinea->ID_BULTO_LINEA") . " AND
                ID_EXPEDICION = $rowOrdenTransporte->ID_EXPEDICION AND
                ID_ALBARAN " . ($rowMovimientoSalida->TIPO_MOVIMIENTO == 'MaterialEstropeadoAProveedor' ? $rowLinea->ID_ALBARAN : "IS NULL") . " AND
                ID_ALBARAN_LINEA " . ($rowMovimientoSalida->TIPO_MOVIMIENTO == 'MaterialEstropeadoAProveedor' ? $rowLinea->ID_ALBARAN_LINEA : "IS NULL") . " AND
                EXPEDICION_SAP = '' AND
                ID_EXPEDICION_SAP IS NULL AND
                ENVIADO_SAP = 0 AND
                ID_MOVIMIENTO_SALIDA = $rowLinea->ID_MOVIMIENTO_SALIDA AND
                ID_MOVIMIENTO_SALIDA_LINEA <> $rowLinea->ID_MOVIMIENTO_SALIDA_LINEA AND
                ID_PEDIDO_SALIDA_LINEA = $rowLinea->ID_PEDIDO_SALIDA_LINEA AND
                ID_UBICACION = $rowLinea->ID_UBICACION AND
                ID_MATERIAL_FISICO " . ($rowLinea->ID_MATERIAL_FISICO == NULL ? 'IS NULL' : "= $rowLinea->ID_MATERIAL_FISICO") . " AND
                ID_ORDEN_TRABAJO_MOVIMIENTO " . ($rowLinea->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? 'IS NULL' : "= $rowLinea->ID_ORDEN_TRABAJO_MOVIMIENTO") . " AND
                ID_INCIDENCIA_CALIDAD " . ($rowLinea->ID_INCIDENCIA_CALIDAD == NULL ? 'IS NULL' : "= $rowLinea->ID_INCIDENCIA_CALIDAD") . " AND
                ID_TIPO_BLOQUEO " . ($rowLinea->ID_TIPO_BLOQUEO == NULL ? 'IS NULL' : "= $rowLinea->ID_TIPO_BLOQUEO"), "No");
            unset($GLOBALS["NotificaErrorPorEmail"]);

            //ACCIONES EN FUNCION DE SI EXISTE UNA LINEA DE MOVIMIENTO DE SALIDA SIMILAR O NO
            if ($rowMovimientoSalidaLineaSimilar != false): //SI EXISTE UNA LINEA SIMILAR, INCREMENTO LA CANTIDAD DE LA LINEA SIMILAR
                $sqlUpdate = "UPDATE MOVIMIENTO_SALIDA_LINEA SET
                          CANTIDAD = CANTIDAD + $rowLinea->CANTIDAD
                          WHERE ID_MOVIMIENTO_SALIDA_LINEA = $rowMovimientoSalidaLineaSimilar->ID_MOVIMIENTO_SALIDA_LINEA";
                $bd->ExecSQL($sqlUpdate);

                //DOY DE BAJA LA LINEA ORIGINAL
                $sqlUpdate = "UPDATE MOVIMIENTO_SALIDA_LINEA SET
                          BAJA = 1
                          , ID_CONTENEDOR = NULL
                          , ID_CONTENEDOR_LINEA = NULL
                          , ID_BULTO = NULL
                          , ID_BULTO_LINEA = NULL
                          WHERE ID_MOVIMIENTO_SALIDA_LINEA = $rowLinea->ID_MOVIMIENTO_SALIDA_LINEA";
                $bd->ExecSQL($sqlUpdate);
            else:
                //NO HACER NADA
            endif;
            //FIN ACCIONES EN FUNCION DE SI EXISTE UNA LINEA DE MOVIMIENTO DE SALIDA SIMILAR O NO

            //ACTUALIZO LA CABECERA DEL MOVIMIENTO
            //$arrPosiblesMovimientosActualizar[] = $rowLinea->ID_MOVIMIENTO_SALIDA;
            //$movimiento->actualizarEstadoMovimientoSalida($rowLinea->ID_MOVIMIENTO_SALIDA);
            $arrMovimientosActualizar[] = $rowLinea->ID_MOVIMIENTO_SALIDA;

            //SI LA ORDEN ES CON BULTOS ACTUALIZO EL ESTADO DEL BULTO
            if (($rowOrdenTransporte->CON_BULTOS == 1) && ($rowLinea->ID_BULTO != NULL)):
                //$arrPosiblesBultosActualizar[] = $rowLinea->ID_BULTO;
                //$orden_preparacion->ActualizarEstadoBulto($rowLinea->ID_BULTO);
                $arrBultosActualizar[] = $rowLinea->ID_BULTO;
            endif;
        endwhile; //BUCLE LINEAS DE MOVIMIENTO DE SALIDA

//        //ME QUEDO CON LOS REGISTROS UNICOS
//        $arrPosiblesMovimientosActualizar = array_unique($arrPosiblesMovimientosActualizar);
//        $arrPosiblesBultosActualizar      = array_unique($arrPosiblesBultosActualizar);
//
//        //RECORRO LOS MOVIMIENTOS PARA ACTUALIZAR LA CABECERA
//        foreach ($arrPosiblesMovimientosActualizar as $idMovimientoActualizar):
//            $movimiento->actualizarEstadoMovimientoSalida($idMovimientoActualizar);
//        endforeach;
//
//        //RECORRO LOS BULTOS PARA ACTUALIZAR EL ESTADO
//        foreach ($arrPosiblesBultosActualizar as $idBultoActualizar):
//            $orden_preparacion->actualizarEstadoBulto($idBultoActualizar);
//        endforeach;

        //DEJO LOS VALORES UNICOS DE LOS ARRAY A ACTUALIZAR
        $arrMovimientosActualizar  = array_unique( (array)$arrMovimientosActualizar);
        $arrBultosActualizar       = array_unique( (array)$arrBultosActualizar);
        $arrPedidosLineaActualizar = array_unique( (array)$arrPedidosLineaActualizar);

        //ACTUALIZO LOS DIFERENTES OBJETOS

        //ACTUALIZO LOS MOVIMIENTOS
        foreach ($arrMovimientosActualizar as $idMovimientoSalida):
            $movimiento->actualizarEstadoMovimientoSalida($idMovimientoSalida);
        endforeach;
        //ACTUALIZO LOS BULTOS
        foreach ($arrBultosActualizar as $idBulto):
            $orden_preparacion->ActualizarEstadoBulto($idBulto);
        endforeach;
        //FIN ACTUALIZO LOS DIFERENTES OBJETOS

        /************************************ HABILITO TRIGGERS IMPLICADOS ************************************/
        $sqlHabilitarTriggers = "SET @Trigger_MOVIMIENTO_SALIDA_LINEA_UPDATE_Deshabilitado = NULL";
        $bd->ExecSQL($sqlHabilitarTriggers);
        /********************************** FIN HABILITO TRIGGERS IMPLICADOS **********************************/

        //ACTUALIZO UNA LINEA DE MOVIMIENTO DE CADA LINEA DE PEDIDO
        foreach ($arrPedidosLineaActualizar as $idPedidoLineaSalida):
            //BUSCO UNA LINEA DE MOVIMIENTO DE SALIDA LINEA DE ESTA LINEA DE PEDIDO
            $rowMovimientoSalidaLinea = $bd->VerRegRest("MOVIMIENTO_SALIDA_LINEA", "ID_PEDIDO_SALIDA_LINEA = $idPedidoLineaSalida AND LINEA_ANULADA = 0 AND BAJA = 0", "No");

            //BUSCO EL PEDIDO LINEA PARA GUARDAR LA OTL EN CASO DE QUE TENGA
            $rowPedidoSalidaLinea = $bd->VerReg("PEDIDO_SALIDA_LINEA", "ID_PEDIDO_SALIDA_LINEA", $idPedidoLineaSalida, "No");

            //SI SE ESTA ANULANDO UNA LINEA DE UNA OTL
            if ($rowPedidoSalidaLinea->ID_ORDEN_TRABAJO_LINEA != NULL):

                //BUSCAMOS LA ULTIMA LLAMADA A LA T06, Y ES EXPEDICION, LO VOLVEREMOS A CALCULAR
                $GLOBALS["NotificaErrorPorEmail"]                   = "No";
                $rowOrdenTrabajoLineaDisponibilidadFechaPlanificada = $bd->VerRegRest("ORDEN_TRABAJO_LINEA_DISPONIBILIDAD_FECHA_PLANIFICADA", "ID_ORDEN_TRABAJO_LINEA =  $rowPedidoSalidaLinea->ID_ORDEN_TRABAJO_LINEA AND BAJA = 0", "No");
                if (($rowOrdenTrabajoLineaDisponibilidadFechaPlanificada == false) ||
                    (($rowOrdenTrabajoLineaDisponibilidadFechaPlanificada != false) && ($rowOrdenTrabajoLineaDisponibilidadFechaPlanificada->ESCENARIO_PLANIFICACION == 'Expedicion'))
                ):

                    if ($orden_trabajo->esTransmitibleInterfaz("Expedicion", "Anulacion Expedicion (anulacion puesta en transito en SCS)", "T06", $rowPedidoSalidaLinea->ID_ORDEN_TRABAJO_LINEA)):
                        //MARCAMOS LA LINEA PARA VOLVER A CALCULAR
                        $sqlUpdate = "UPDATE ORDEN_TRABAJO_LINEA SET
                                      DISPONIBILIDAD_FECHA_PLANIFICADA = 'Pendiente Tratar'
                                      WHERE ID_ORDEN_TRABAJO_LINEA = " . $rowPedidoSalidaLinea->ID_ORDEN_TRABAJO_LINEA;
                        $bd->ExecSQL($sqlUpdate);
                    endif;

                    if ($orden_trabajo->esTransmitibleInterfaz("Expedicion", "Anulacion Expedicion (anulacion puesta en transito en SCS)", "T01", $rowPedidoSalidaLinea->ID_ORDEN_TRABAJO_LINEA)):
                        //INICIO UNA TRANSACCION PARA LA INTERFAZ DEL SEMAFORO PARA INFORMAR A SAP DEL BLOQUEO Y CANTIDAD RECEPCIONADA
                        $bd->begin_transaction();

                        //COMPRUEBO SI TIENE INCIDENCIA DE SISTEMA CREADA
                        $num = $bd->NumRegsTabla("INCIDENCIA_SISTEMA", "TIPO = '" . INCIDENCIA_TIPO_INTERFAZ_SIN_TRANSMITIR . "' AND SUBTIPO = '" . INCIDENCIA_SUBTIPO_ORDENES_TRABAJO_SEMAFORO . "' AND ID_OBJETO = $rowPedidoSalidaLinea->ID_ORDEN_TRABAJO_LINEA AND TABLA_OBJETO = 'ORDEN_TRABAJO_LINEA' AND ESTADO <> 'Finalizada'");
                        if ($num == 0): //SI NO TIENE INCIDENCIA SISTEMA CREADA REALIZO LA LLAMADA A SAP

                            $resultado = $sap->OrdenesTrabajoSemaforo($rowPedidoSalidaLinea->ID_ORDEN_TRABAJO_LINEA);
                            if ($resultado['RESULTADO'] != 'OK'): //No se ha podido grabar la información en SAP
                                $strError .= $auxiliar->traduce("Se han producido errores al transmitir la interfaz del semaforo de ordenes de trabajo a SAP", $administrador->ID_IDIOMA) . ". " . $datosLinea;
                                $strError .= "<br>";
                                //NO INFORMO DE LOS ERRORES PORQUE ES UNA INTERFAZ ASINCRONA DONDE NO ENVIAN ERRORES SI SE PRODUCEN

                                //DESHAGO LA TRANSACCION
                                $bd->rollback_transaction();

                                //GRABO EL ERROR PRODUCIDO EN BASE DE DATOS
                                $sap->InsertarErrores($resultado);

                                //SI FALLA LA TRANSMISION DEL SEMAFORO Y NO ESTA YA COMO PENDIENTE, LO GRABO PARA SABER QUE ESTA PENDIENTE DE REALIZARSE
                                $num = $bd->NumRegsTabla("INTERFACES_PENDIENTES_TRANSMITIR", "INTERFAZ_PENDIENTE_TRANSMITIR = 'ordenes_trabajo_semaforo' AND ID_OBJETO = $rowPedidoSalidaLinea->ID_ORDEN_TRABAJO_LINEA AND TABLA_OBJETO = 'ORDEN_TRABAJO_LINEA' AND BAJA = 0");
                                if ($num == 0):
                                    $sqlInsert = "INSERT INTO INTERFACES_PENDIENTES_TRANSMITIR SET
                                                      INTERFAZ_PENDIENTE_TRANSMITIR = 'ordenes_trabajo_semaforo'
                                                      , ID_OBJETO = $rowPedidoSalidaLinea->ID_ORDEN_TRABAJO_LINEA
                                                      , TABLA_OBJETO = 'ORDEN_TRABAJO_LINEA'
                                                      , NUMERO_LLAMADAS_INTERFAZ = '0'
                                                      , BAJA = 0";
                                    $bd->ExecSQL($sqlInsert);
                                endif;

                                //ACTUALIZO EL ESTADO TRATADA DE LA LINEA DE LA ORDEN DE TRABAJO
                                $orden_trabajo->ActualizarLinea_Estados($rowPedidoSalidaLinea->ID_ORDEN_TRABAJO_LINEA);
                            endif;
                        endif;

                        //FINALIZO LA TRANSACCION PARA LA INTERFAZ DEL SEMAFORO PARA INFORMAR A SAP DEL BLOQUEO Y CANTIDAD RECEPCIONADA
                        $bd->commit_transaction();
                    endif;

                    if ($orden_trabajo->esTransmitibleInterfaz("Expedicion", "Anulacion Expedicion (anulacion puesta en transito en SCS)", "T02", $rowPedidoSalidaLinea->ID_ORDEN_TRABAJO_LINEA)):

                        //INICIO UNA TRANSACCION PARA LA INTERFAZ DE LA RESERVA DE MATERIAL PARA INFORMAR A SO99 DEL BLOQUEO Y CANTIDAD PEDIDA
                        $bd->begin_transaction();

                        //COMPRUEBO SI TIENE INCIDENCIA DE SISTEMA CREADA
                        $num = $bd->NumRegsTabla("INCIDENCIA_SISTEMA", "TIPO = '" . INCIDENCIA_TIPO_INTERFAZ_SIN_TRANSMITIR . "' AND SUBTIPO = '" . INCIDENCIA_SUBTIPO_ORDENES_TRABAJO_RESERVA . "' AND ID_OBJETO = $rowPedidoSalidaLinea->ID_ORDEN_TRABAJO_LINEA AND TABLA_OBJETO = 'ORDEN_TRABAJO_LINEA' AND ESTADO <> 'Finalizada'");
                        if ($num == 0): //SI NO TIENE INCIDENCIA SISTEMA CREADA REALIZO LA LLAMADA A SAP

                            $resultado = $sap->OrdenesTrabajoReserva($rowPedidoSalidaLinea->ID_ORDEN_TRABAJO_LINEA);
                            if ($resultado['RESULTADO'] != 'OK'): //No se ha podido grabar la información en SAP
                                $strError .= $auxiliar->traduce("Se han producido errores al transmitir la interfaz de la reserva de material de ordenes de trabajo a SO99", $administrador->ID_IDIOMA) . ". " . $datosLinea;
                                foreach ($resultado['ERRORES'] as $arr):
                                    foreach ($arr as $mensaje_error):
                                        $strError = $strError . $mensaje_error . "<br>";
                                    endforeach;
                                endforeach;

                                //DESHAGO LA TRANSACCION
                                $bd->rollback_transaction();

                                //GRABO EL ERROR PRODUCIDO EN BASE DE DATOS
                                $sap->InsertarErrores($resultado);

                                //SI FALLA LA TRANSMISION DEL SEMAFORO Y NO ESTA YA COMO PENDIENTE, LO GRABO PARA SABER QUE ESTA PENDIENTE DE REALIZARSE
                                $num = $bd->NumRegsTabla("INTERFACES_PENDIENTES_TRANSMITIR", "INTERFAZ_PENDIENTE_TRANSMITIR = 'ordenes_trabajo_reserva' AND ID_OBJETO = $rowPedidoSalidaLinea->ID_ORDEN_TRABAJO_LINEA AND TABLA_OBJETO = 'ORDEN_TRABAJO_LINEA' AND BAJA = 0");
                                if ($num == 0):
                                    $sqlInsert = "INSERT INTO INTERFACES_PENDIENTES_TRANSMITIR SET
                                                      INTERFAZ_PENDIENTE_TRANSMITIR = 'ordenes_trabajo_reserva'
                                                      , ID_OBJETO = $rowPedidoSalidaLinea->ID_ORDEN_TRABAJO_LINEA
                                                      , TABLA_OBJETO = 'ORDEN_TRABAJO_LINEA'
                                                      , NUMERO_LLAMADAS_INTERFAZ = '0'
                                                      , BAJA = 0";
                                    $bd->ExecSQL($sqlInsert);
                                endif;
                            endif;
                        endif;

                        //FINALIZO UNA TRANSACCION PARA LA INTERFAZ DE LA RESERVA DE MATERIAL PARA INFORMAR A SO99 DEL BLOQUEO Y CANTIDAD PEDIDA
                        $bd->commit_transaction();
                    endif;
                endif;
            endif;

            //ACTUALIZO EL ESTADO DE LA LINEA DEL PEDIDO DE SALIDA
            $movimiento->actualizarEstadoLineaPedidoSalida($rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA_LINEA);
            //ACTUALIZO EL ESTADO DE LA NECESIDAD DE LA LINEA DEL MOVIMIENTO DE SALIDA
            $movimiento->actualizarEstadoLineaNecesidad($rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA);
        endforeach;

        //SI ES UN PEDIDO DE MATERIAL ESTROPEADO A PROVEEDOR TENDRE QUE ACTUALIZAR LOS CONTRATOS MARCO Y LAS FECHAS DE EXPEDICION SI PREVIAMENTE NO SE HAN PRODUCIDO ERRORES
        if (($rowPedido->TIPO_PEDIDO == 'Material Estropeado a Proveedor') && ($hayErrorCancelacionExpedicionSAP == false)):

            //BUSCO EL MOVIMIENTO ASOCIADO AL PEDIDO
//            if ($version == 'Tercera'):
            $sqlMovimientoSalida = "SELECT *
                                    FROM MOVIMIENTO_SALIDA MS
                                    WHERE MS.ID_PEDIDO_SALIDA = $rowPedido->ID_PEDIDO_SALIDA AND MS.BAJA = 0";
//            elseif ($version == 'Cuarta'):
//                $sqlMovimientoSalida = "SELECT *
//                                        FROM MOVIMIENTO_SALIDA MS
//                                        WHERE MS.ID_PEDIDO_SALIDA = $rowPedido->ID_PEDIDO_SALIDA AND MS.BAJA = 0";
//            endif;

            $resultMovimientoSalida = $bd->ExecSQL($sqlMovimientoSalida);
            if ($bd->NumRegs($resultMovimientoSalida) != 1):
                $hayErrorCancelacionExpedicionSAP = true;
                $strError                         = $strError . $auxiliar->traduce("No se ha encontrado un unico movimiento de envio de material estropeado a proveedor para revertir", $administrador->ID_IDIOMA) . ".<br>";
            else:
                //RECUPERO EL MOVIMIENTO DE SALIDA CORRESPONDIENTE
                $rowMovimientoSalida = $bd->SigReg($resultMovimientoSalida);

                //VARIABLE PARA DETERMINAR EL TIPO DE MOVIMIENTO A ENVIAR A SAP
                $tipoMovimiento = NULL;

                //VARIABLE PARA SABER SI HAYA QUE TRANSMITIR EL MOVIMIENTO DIRECTO A PROVEEDOR A SAP
                $SistemaOT = NULL;

                //BUSCO LAS LINEAS DEL MOVIMIENTO DE SALIDA
                $sqlLineasMovimientoSalida    = "SELECT *
                                                  FROM MOVIMIENTO_SALIDA_LINEA MSL
                                                  WHERE MSL.ID_MOVIMIENTO_SALIDA = $rowMovimientoSalida->ID_MOVIMIENTO_SALIDA AND MSL.ENVIADO_SAP = 1 AND MSL.ESTADO = 'Pendiente de Expedir' AND MSL.LINEA_ANULADA = 0 AND MSL.BAJA = 0";
                $resultLineasMovimientoSalida = $bd->ExecSQL($sqlLineasMovimientoSalida);
                while ($rowLineaMovimientoSalida = $bd->SigReg($resultLineasMovimientoSalida)):

                    //BUSCO EL MATERIAL
                    $rowMat = $bd->VerReg("MATERIAL", "ID_MATERIAL", $rowLineaMovimientoSalida->ID_MATERIAL);

                    //BUSCO EL MATERIAL FISICO
                    if ($rowLineaMovimientoSalida->ID_MATERIAL_FISICO == NULL):
                        $idMatFis = NULL;
                    else:
                        $rowMatFis = $bd->VerReg("MATERIAL_FISICO", "ID_MATERIAL_FISICO", $rowLineaMovimientoSalida->ID_MATERIAL_FISICO);
                        $idMatFis  = $rowMatFis->ID_MATERIAL_FISICO;
                    endif;

                    //BUSCO LA INFORMACION DE LA OT
                    $rowOTMovimiento = $bd->VerReg("ORDEN_TRABAJO_MOVIMIENTO", "ID_ORDEN_TRABAJO_MOVIMIENTO", $rowLineaMovimientoSalida->ID_ORDEN_TRABAJO_MOVIMIENTO);

                    //BUSCO LA OT
                    $rowOT = $bd->VerReg("ORDEN_TRABAJO", "ID_ORDEN_TRABAJO", $rowOTMovimiento->ID_ORDEN_TRABAJO);

                    //BUSCO EL TIPO DE BLOQUEO
                    $rowTipoBloqueo = $bd->VerReg("TIPO_BLOQUEO", "ID_TIPO_BLOQUEO", $rowOTMovimiento->ID_TIPO_BLOQUEO);

                    //BUSCO EL CENTRO DE LA OT
                    $rowCentroOT = $bd->VerReg("CENTRO", "ID_CENTRO", $rowOT->ID_CENTRO);

                    //BUSCO LA SOCIEDAD DE LA OT
                    $rowSociedadOT = $bd->VerReg("SOCIEDAD", "ID_SOCIEDAD", $rowCentroOT->ID_SOCIEDAD);

                    //BUSCO EL CENTRO ORIGEN DEL PEDIDO
                    $rowCentroOrigenPedido = $bd->VerReg("CENTRO", "ID_CENTRO", $rowPedido->ID_CENTRO_ORIGEN);

                    //BUSCO LA SOCIEDAD DEL PEDIDO
                    $rowSociedadOrigenPedido = $bd->VerReg("SOCIEDAD", "ID_SOCIEDAD", $rowCentroOrigenPedido->ID_SOCIEDAD);

                    //COMPRUEBO QUE TODAS LAS LINEAS SEAN CON/SIN ENTRADA COMPRA
                    if ($SistemaOT == NULL):
                        $SistemaOT = $rowOT->SISTEMA_OT;
                    elseif ($SistemaOT != $rowOT->SISTEMA_OT):
                        $hayErrorCancelacionExpedicionSAP = true;
                        $strError                         = $strError . $auxiliar->traduce("Esta mezclando lineas de material estropeado de Ots con lineas sin orden de compra para revertir", $administrador->ID_IDIOMA) . ".<br>";
                    endif;

                    //DETERMINO EL TIPO DE MOVIMIENTO A INFORMAR A SAP
                    if ($rowOT->SISTEMA_OT == 'SGA Entrada Sin OC'):
                        $tipoMovimiento = "Sin Compra";
                    elseif (substr( (string) $rowTipoBloqueo->TIPO_BLOQUEO_INTERNO, 0, 1) == 'X'):
                        $tipoMovimiento = "Calidad";
                    elseif (substr( (string) $rowTipoBloqueo->TIPO_BLOQUEO_INTERNO, 0, 1) == 'Q'):
                        //CALCULO EL NUMERO DE PEDIDOS INTERCOMPANY DE MATERIAL ESTROPEADO EN LOS QUE ESTE IMPLICADO ESTE MOVIMIENTO DE ORDEN DE TRABAJO
                        $num = $mat->getNumeroPedidosIntercompanyMaterialEstropeado($rowOTMovimiento->ID_ORDEN_TRABAJO_MOVIMIENTO);

                        //EN FUNCION DEL NUMERO DE PEDIDOS INTERCOMPANY DE MATERIAL ESTROPEADO CALCULO EL TIPO
                        if ($num == 0):
                            $tipoMovimiento = 'Orden Trabajo';
                        elseif ($num == 1):
                            $tipoMovimiento = 'Traslado Internacional';
                        else:
                            $tipoMovimiento = 'Calidad';
                        endif;

//                        //COMPRUEBO EL NUMERO DE SOCIEDADES DONDE HA ESTADO EL MATERIAL ESTROPEADO
//                        $sqlNumSociedades = "SELECT COUNT(TEMP.ID_MATERIAL_UBICACION) AS NUM FROM
//                                                    (SELECT MU.ID_MATERIAL_UBICACION
//                                                     FROM MATERIAL_UBICACION MU
//                                                     INNER JOIN UBICACION U ON U.ID_UBICACION = MU.ID_UBICACION
//                                                     INNER JOIN ALMACEN A ON A.ID_ALMACEN = U.ID_ALMACEN
//                                                     INNER JOIN CENTRO C ON C.ID_CENTRO = A.ID_CENTRO
//                                                     WHERE ID_ORDEN_TRABAJO_MOVIMIENTO = $rowOTMovimiento->ID_ORDEN_TRABAJO_MOVIMIENTO
//                                                     GROUP BY C.ID_SOCIEDAD) AS TEMP";
//                        $resultNumSociedades = $bd->ExecSQL($sqlNumSociedades);
//                        $rowNumSociedades = $bd->SigReg($resultNumSociedades);
//                        $numSociedades = $rowNumSociedades->NUM;
//                        if ($numSociedades > 2): //SE HA MOVIDO ENTRE 3 SOCIEDADES O MAS
//                            $tipoMovimiento = 'Calidad';
//                        elseif (($numSociedades == 2) && ($rowSociedadOT->ID_SOCIEDAD == $rowSociedadOrigenPedido->ID_SOCIEDAD)): //SE HA MOVIDO A OTRA SOCIEDAD Y HA VUELTO
//                            $tipoMovimiento = 'Calidad';
//                        else: //NO SE HA MOVIDO DE LA SOCIEDAD ORIGINAL O SOLO SE HA MOVIDO UNA VEZ A OTRA SOCIEDAD
//                            if ($rowSociedadOT->ID_SOCIEDAD == $rowSociedadOrigenPedido->ID_SOCIEDAD):
//                                $tipoMovimiento = 'Orden Trabajo';
//                            else:
//                                $tipoMovimiento = 'Traslado Internacional';
//                            endif;
//                        endif;
////                    elseif ((substr($rowTipoBloqueo->TIPO_BLOQUEO_INTERNO, 0, 1) == 'Q') && ($rowSociedadOT->ID_SOCIEDAD == $rowSociedadOrigenPedido->ID_SOCIEDAD)):
////                        $tipoMovimiento = "Orden Trabajo";
////                    elseif ((substr($rowTipoBloqueo->TIPO_BLOQUEO_INTERNO, 0, 1) == 'Q') && ($rowSociedadOT->ID_SOCIEDAD != $rowSociedadOrigenPedido->ID_SOCIEDAD)):
////                        $tipoMovimiento = "Traslado Internacional";
                    else:
                        $html->PagError("LineasConErrores");
                    endif;

                    //ACTUALIZO LA LINEA PARA ACTUALIZAR EL ESTADO
                    $sqlUpdate = "UPDATE MOVIMIENTO_SALIDA_LINEA SET
                                  ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                  , FECHA_EXPEDICION = '0000-00-00 00:00:00'
                                  , CONTRATO_ASOCIADO = 0
                                  , CONTRATO_REFERENCIA = NULL
                                  , CONTRATO_POSICION = NULL
                                  WHERE ID_MOVIMIENTO_SALIDA_LINEA = $rowLineaMovimientoSalida->ID_MOVIMIENTO_SALIDA_LINEA";
                    $bd->ExecSQL($sqlUpdate);
                endwhile;
                //FIN BUSCO LAS LINEAS DEL MOVIMIENTO DE SALIDA

                //ACTUALIZO LA FECHA EXPEDICION DEL MOVIMIENTO DE SALIDA
                $sqlUpdate = "UPDATE MOVIMIENTO_SALIDA SET
                              ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                              , FECHA_EXPEDICION = '0000-00-00'
                              WHERE ID_MOVIMIENTO_SALIDA = $rowMovimientoSalida->ID_MOVIMIENTO_SALIDA";
                $bd->ExecSQL($sqlUpdate);
            endif;
            //FIN BUSCO EL MOVIMIENTO ASOCIADO AL PEDIDO
        endif;
        //FIN SI ES UN PEDIDO ZTLI DE MATERIAL ESTROPEADO A PROVEEDOR TENDRE QUE INFORMAR DE LA EXPEDICION DEL MATERIAL (MOVIMIENTOS 541, 917 Y/O 925)

        //ACTUALIZO EL ESTADO DE LA ORDEN DE TRANSPORTE
        $this->actualizar_estado_orden_transporte($rowOrdenTransporte->ID_EXPEDICION);

        //SI NO HAY ERRORES EN LA ANULACION DE LA EXPEDICION SAP, HAGO LA LLAMADA A SAP, VACIA CAMPOS (ENVIADO_SAP Y EXPEDICION_SAP)
        if ($hayErrorCancelacionExpedicionSAP == false):

            /************************************ DESHABILITO TRIGGERS IMPLICADOS ************************************/
            $sqlDeshabilitarTriggers = "SET @Trigger_MOVIMIENTO_SALIDA_LINEA_UPDATE_Deshabilitado = 1";
            $bd->ExecSQL($sqlDeshabilitarTriggers);
            /********************************** FIN DESHABILITO TRIGGERS IMPLICADOS **********************************/

            //INFORMO A SAP DE LA ANULACION DE LA EXPEDICION SAP, VACIA CAMPOS (ENVIADO_SAP Y EXPEDICION_SAP)
            unset($resultado);
            if (
                ($rowPedido->TIPO_PEDIDO == "Venta") ||
                (($rowPedido->TIPO_PEDIDO == "Traslado") && ($rowPedido->TIPO_TRASLADO != 'Desconectado')) ||
                ($rowPedido->TIPO_PEDIDO == "Componentes a Proveedor") ||
                ($rowPedido->TIPO_PEDIDO == "Intra Centro Fisico") ||
                ($rowPedido->TIPO_PEDIDO == "Interno Gama") ||
                ($rowPedido->TIPO_PEDIDO == "Pendientes de Ordenes Trabajo") ||
                ($rowPedido->TIPO_PEDIDO == "Traspaso Entre Almacenes Material Estropeado")
            ):

                $resultado = $sap->AnularInformarSAPExpedicion($rowOrdenTransporte->VERSION, $expedicionSAP);

            elseif ($rowPedido->TIPO_PEDIDO == 'Devolución a Proveedor'):

                $resultado = $sap->AnularExpedicionMovimientoDevolucionAProveedor($rowOrdenTransporte->VERSION, $expedicionSAP);

            elseif (($rowPedido->TIPO_PEDIDO == 'Material Estropeado a Proveedor') && ($tipoMovimiento != "Sin Compra")):

                $resultado = $sap->AnularAjusteTraspasoMaterialAProveedor($rowMovimientoSalida->ID_MOVIMIENTO_SALIDA, $tipoMovimiento);

            endif;

            /************************************ HABILITO TRIGGERS IMPLICADOS ************************************/
            $sqlHabilitarTriggers = "SET @Trigger_MOVIMIENTO_SALIDA_LINEA_UPDATE_Deshabilitado = NULL";
            $bd->ExecSQL($sqlHabilitarTriggers);
            /********************************** FIN HABILITO TRIGGERS IMPLICADOS **********************************/

            if (isset($resultado)): //SOLO SI ESTA DEFINIDO, HAY MOVIMIENTOS QUE SE EXPIDEN (Devolucion a proveedor por anulacion o calidad) Y NO SE INFORMA A SAP DE ELLOS

                if ($resultado['RESULTADO'] != 'OK'): //No se ha podido grabar la información en SAP
                    //ACTUALIZO LA VARIABLE ERROR EN LA TRANSMISION DE LA ANULACION DE LA EXPEDICION SAP A TRUE
                    $hayErrorTransmisionCancelacionExpedicionSAP = true;

                    //GRABO LA CEBECERA DEL ERROR SAP
                    $strError = "<strong>" . $auxiliar->traduce("Se han producido los siguientes errores SAP al transmitir la expedicion SAP", $administrador->ID_IDIOMA) . " " . $expedicionSAP . ":</strong><br>";

                    foreach ($resultado['ERRORES'] as $arr):
                        foreach ($arr as $mensaje_error):
                            $strError = $strError . $mensaje_error . "<br>";
                        endforeach;
                    endforeach;
                endif;

            else: //MOVIMIENTO QUE NO SE INFORMAN A SAP

                //ARRAY DE OBJETOS A ACTUALIZAR DESPUES DEL WHILE
                $arrMovimientosActualizar  = array();
                $arrBultosActualizar       = array();
                $arrPedidosLineaActualizar = array();

                /************************************ DESHABILITO TRIGGERS IMPLICADOS ************************************/
                $sqlDeshabilitarTriggers = "SET @Trigger_MOVIMIENTO_SALIDA_LINEA_UPDATE_Deshabilitado = 1";
                $bd->ExecSQL($sqlDeshabilitarTriggers);
                /********************************** FIN DESHABILITO TRIGGERS IMPLICADOS **********************************/

                //RECORRO LAS LINEAS DEL MOVIMIENTO PARA VACIAR (ENVIADO_SAP Y EXPEDICION_SAP) EN LOS MOVIMIENTOSQUE QUE NO SE TRANSMITEN A SAP (ENVIOS A PROVEEDOR DE MATERIAL RECHAZADO POR CALIDAD Y POR ANULACIONES Y TRASLADOS DESCONETADOS DE SAP)
                $sqlLineas    = "SELECT *
                                FROM MOVIMIENTO_SALIDA_LINEA MSL
                                INNER JOIN MOVIMIENTO_SALIDA MS ON MS.ID_MOVIMIENTO_SALIDA = MSL.ID_MOVIMIENTO_SALIDA
                                INNER JOIN PEDIDO_SALIDA PS ON PS.ID_PEDIDO_SALIDA = MSL.ID_PEDIDO_SALIDA
                                WHERE EXPEDICION_SAP = '" . $expedicionSAP . "' AND
                                (
                                    (MS.TIPO_MOVIMIENTO = 'MaterialRechazadoAnuladoEnEntradasAProveedor') OR
                                    (MS.TIPO_MOVIMIENTO = 'MaterialEstropeadoAProveedor') OR
                                    (MS.TIPO_MOVIMIENTO = 'TrasladoOMConstruccion') OR
                                    ( (MS.TIPO_MOVIMIENTO = 'TraspasoEntreAlmacenesNoEstropeado') AND (PS.TIPO_PEDIDO = 'Traslado') AND (PS.TIPO_TRASLADO = 'Desconectado') )
                                )";
                $resultLineas = $bd->ExecSQL($sqlLineas);
                while ($rowLinea = $bd->SigReg($resultLineas)):
                    //ME GUARDO LAS LINEAS DE PEDIDO DE SALIDA IMPLICADAS
                    $arrPedidosLineaActualizar[] = $rowLinea->ID_PEDIDO_SALIDA_LINEA;

                    //BUSCO LA LINEA DEL MOVIMIENTO DE SALIDA PREVIO A LA ACTUALIZACION
                    $rowLineaMovimientoSalidaPrevioActualizacion = $bd->VerReg("MOVIMIENTO_SALIDA_LINEA", "ID_MOVIMIENTO_SALIDA_LINEA", $rowLinea->ID_MOVIMIENTO_SALIDA_LINEA);

                    $sqlUpdate = "UPDATE MOVIMIENTO_SALIDA_LINEA SET
                                    ENVIADO_SAP = 0
                                    , EXPEDICION_SAP = ''
                                    , ID_EXPEDICION_SAP = NULL
                                    WHERE ID_MOVIMIENTO_SALIDA_LINEA = $rowLinea->ID_MOVIMIENTO_SALIDA_LINEA";
                    $bd->ExecSQL($sqlUpdate);

                    //BUSCO LA LINEA DEL MOVIMIENTO DE SALIDA POSTERIOR A LA ACTUALIZACION
                    $rowLineaMovimientoSalidaPosteriorActualizacion = $bd->VerReg("MOVIMIENTO_SALIDA_LINEA", "ID_MOVIMIENTO_SALIDA_LINEA", $rowLinea->ID_MOVIMIENTO_SALIDA_LINEA);

                    // LOG MOVIMIENTOS
                    $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Mov. salida", $rowLinea->ID_MOVIMIENTO_SALIDA, "Asignar expedicion SAP a la linea de movimiento con ID: $rowLinea->ID_MOVIMIENTO_SALIDA_LINEA", "MOVIMIENTO_SALIDA_LINEA", $rowLineaMovimientoSalidaPrevioActualizacion, $rowLineaMovimientoSalidaPosteriorActualizacion);

                    //SI LA LINEA TENIA ID_EXPEDICION_SAP ASIGNADA LA DOY DE BAJA SI PUEDO
                    $exp_SAP->darDeBajaIdExpedicionSAPSiCorresponde($rowLinea->ID_EXPEDICION_SAP);

                    //ACTUALIZO LA CABECERA DEL MOVIMIENTO
                    //$movimiento->actualizarEstadoMovimientoSalida($rowLinea->ID_MOVIMIENTO_SALIDA);
                    $arrMovimientosActualizar[] = $rowLinea->ID_MOVIMIENTO_SALIDA;

                    //SI LA ORDEN ES CON BULTOS ACTUALIZO EL ESTADO DEL BULTO
                    if ($rowOrdenTransporte->CON_BULTOS == 1):
                        //$orden_preparacion->ActualizarEstadoBulto($rowLinea->ID_BULTO);
                        $arrBultosActualizar[] = $rowLinea->ID_BULTO;
                    endif;

                endwhile; //BUCLE LINEAS DE MOVIMIENTO DE SALIDA

                //DEJO LOS VALORES UNICOS DE LOS ARRAY A ACTUALIZAR
                $arrMovimientosActualizar  = array_unique( (array)$arrMovimientosActualizar);
                $arrBultosActualizar       = array_unique( (array)$arrBultosActualizar);
                $arrPedidosLineaActualizar = array_unique( (array)$arrPedidosLineaActualizar);

                //ACTUALIZO LOS DIFERENTES OBJETOS

                //ACTUALIZO LOS MOVIMIENTOS
                foreach ($arrMovimientosActualizar as $idMovimientoSalida):
                    $movimiento->actualizarEstadoMovimientoSalida($idMovimientoSalida);
                endforeach;
                //ACTUALIZO LOS BULTOS
                foreach ($arrBultosActualizar as $idBulto):
                    $orden_preparacion->ActualizarEstadoBulto($idBulto);
                endforeach;
                //FIN ACTUALIZO LOS DIFERENTES OBJETOS

                /************************************ HABILITO TRIGGERS IMPLICADOS ************************************/
                $sqlHabilitarTriggers = "SET @Trigger_MOVIMIENTO_SALIDA_LINEA_UPDATE_Deshabilitado = NULL";
                $bd->ExecSQL($sqlHabilitarTriggers);
                /********************************** FIN HABILITO TRIGGERS IMPLICADOS **********************************/

                //ACTUALIZO UNA LINEA DE MOVIMIENTO DE CADA LINEA DE PEDIDO
                foreach ($arrPedidosLineaActualizar as $idPedidoLineaSalida):
                    //BUSCO UNA LINEA DE MOVIMIENTO DE SALIDA LINEA DE ESTA LINEA DE PEDIDO
                    $rowMovimientoSalidaLinea = $bd->VerRegRest("MOVIMIENTO_SALIDA_LINEA", "ID_PEDIDO_SALIDA_LINEA = $idPedidoLineaSalida AND LINEA_ANULADA = 0 AND BAJA = 0", "No");

                    //ACTUALIZO EL ESTADO DE LA LINEA DEL PEDIDO DE SALIDA
                    $movimiento->actualizarEstadoLineaPedidoSalida($rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA_LINEA);
                    //ACTUALIZO EL ESTADO DE LA NECESIDAD DE LA LINEA DEL MOVIMIENTO DE SALIDA
                    $movimiento->actualizarEstadoLineaNecesidad($rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA);
                endforeach;

            endif; //FIN MOVIMIENTOS INFORMADOS Y NO INFORMADOS A SAP

        endif; //FIN SI NO HAY ERRORES EN LA ANULACION DE LA EXPEDICION SAP, HAGO LA LLAMADA A SAP, VACIA CAMPOS (ENVIADO_SAP Y EXPEDICION_SAP)


        //DECLARO UN ARRAY PARA DEVOLVER DATOS
        $arrDevuelto                                                 = array();
        $arrDevuelto['errores']                                      = $strError;
        $arrDevuelto['error_cancelacion_expedidion_SAP']             = $hayErrorCancelacionExpedicionSAP;
        $arrDevuelto['error_transmision_cancelacion_expedidion_SAP'] = $hayErrorTransmisionCancelacionExpedicionSAP;
        $arrDevuelto['lista_lineas_pedido']                          = $arrayLineasPedidosInvolucradas;
        $arrDevuelto['resultado']                                    = $resultado;

        //DEVUELVO EL ARRAY
        return $arrDevuelto;
    }

    /**
     *
     * @param $idOrdenTransporte ORDEN DE RECOGIDA
     * @return array
     * FUNCION UTLIZADA PARA EXPEDIR UNA ORDEN DE RECOGIDA
     */
    function expedir_orden_transporte($idOrdenTransporte)
    {
        global $bd;
        global $auxiliar;
        global $administrador;
        global $html;
        global $movimiento;
        global $necesidad;
        global $orden_preparacion;
        global $orden_trabajo;
        global $sap;

        //RECUPERO LA VARIABLE GLOBAL DE TEXTO ERROR
        global $strError;
        $strError = "";

        //VARIABLE PARA SABER SI HAY ERROR EN LA ORDEN DE TRANSPORTE
        $hayErrorExpedirOrdenTransporte = false;

        //ARRAY DE LINEAS DE MOVIMIENTOS DE SALIDA PARA LAS ALERTAS DE NECESIDADES
        $arrIdsMovimientosLineasExpedidos = array();

        //ARRAY PARA GUARDAR LOS PEDIDOS A ACTUALIZAR
        //$arrPosiblesPedidosActualizar = array();

        //BUSCO LA ORDEN DE TRANSPORTE
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowOrdenTransporte               = $bd->VerReg("EXPEDICION", "ID_EXPEDICION", $idOrdenTransporte, "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);

        //COMPRUEBO QUE EXISTA LA ORDEN DE TRANSPORTE
        $html->PagErrorCondicionado($rowOrdenTransporte, "==", false, "OrdenTransporteNoEncontrada");

        //COMPRUEBO EL ESTADO DE LA ORDEN DE TRANSPORTE EN FUNCION DE SI ES CON BULTOS O NO
        if ($rowOrdenTransporte->CON_BULTOS == 0):
            if (($rowOrdenTransporte->ESTADO != 'Transmitida a SAP') && ($rowOrdenTransporte->ESTADO != 'Parcialmente Expedida')):
                $html->PagError("EstadoOrdeRecogidaIncorrectoParaExpedir");
            endif;
        //$html->PagErrorCondicionado($rowOrdenTransporte->ESTADO, "!=", 'Transmitida a SAP', "EstadoOrdenTransporteDiferenteTransmitidaASAP");
        elseif ($rowOrdenTransporte->CON_BULTOS == 1):
            if (($rowOrdenTransporte->ESTADO != 'Embarcada') && ($rowOrdenTransporte->ESTADO != 'Parcialmente Expedida')):
                $html->PagError("EstadoOrdeRecogidaIncorrectoParaExpedir");
            endif;
        //$html->PagErrorCondicionado($rowOrdenTransporte->ESTADO, "!=", 'Embarcada', "EstadoOrdenTransporteDiferenteEmbarcada");
        else:
            $html->PagError("OrdenTransporteConBultosIncorrecta");
        endif;

        //SI LA ORDEN DE TRANSPORTE ES CON BULTOS, COMPRUEBO QUE TODOS ESTEN EMBARCADOS O EXPEDIDOS
        if ($rowOrdenTransporte->CON_BULTOS == 1):
            $num = $bd->NumRegsTabla("BULTO", "ID_EXPEDICION = $rowOrdenTransporte->ID_EXPEDICION AND ESTADO <> 'Embarcado' AND ESTADO <> 'Expedido'");
            $html->PagErrorCondicionado($num, ">", 0, "OrdenTransporteConBultosEstadoDiferenteEmbarcadosExpedidos");
        endif;

        //COMPRUEBO QUE TODAS LAS LINEAS ESTEN EN ESTADO TRANSMITIDO A SAP
        $num = $bd->NumRegsTabla("MOVIMIENTO_SALIDA_LINEA", "ID_EXPEDICION = $rowOrdenTransporte->ID_EXPEDICION AND ESTADO IN ('Reservado para Preparacion', 'En Preparacion', 'Pendiente de Expedir') AND ENVIADO_SAP = 1 AND LINEA_ANULADA = 0 AND BAJA = 0");
        $html->PagErrorCondicionado($num, ">", 0, "OrdenTransporteConLineasPreparandose");

        //CALCULO LA FECHA Y HORA DE EXPEDICION
        $fechaHoraExpedicion = date("Y-m-d H:i:s");

        //ARRAY DE OBJETOS A ACTUALIZAR DESPUES DEL WHILE
        $arrMovimientosActualizar  = array();
        $arrBultosActualizar       = array();
        $arrPedidosActualizar      = array();
        $arrPedidosLineaActualizar = array();
        $arrOrdenTrabajoLinea      = array();

        /************************************ DESHABILITO TRIGGERS IMPLICADOS ************************************/
        $sqlDeshabilitarTriggers = "SET @Trigger_MOVIMIENTO_SALIDA_LINEA_UPDATE_Deshabilitado = 1";
        $bd->ExecSQL($sqlDeshabilitarTriggers);
        /********************************** FIN DESHABILITO TRIGGERS IMPLICADOS **********************************/

        //RECORRO LAS LINEAS PARA REALIZAR LAS DIFERENTES OPERACIONES
        $sqlLineas    = "SELECT *
                        FROM MOVIMIENTO_SALIDA_LINEA MSL
                        WHERE MSL.ID_EXPEDICION = $rowOrdenTransporte->ID_EXPEDICION AND ESTADO = 'Transmitido a SAP' AND ENVIADO_SAP = 1 AND LINEA_ANULADA = 0 AND BAJA = 0 FOR UPDATE";
        $resultLineas = $bd->ExecSQL($sqlLineas);
        while ($rowLinea = $bd->SigReg($resultLineas)):
            //BUSCO EL ALBARAN DE LA LINEA
            $rowAlbaran = $bd->VerReg("ALBARAN", "ID_ALBARAN", $rowLinea->ID_ALBARAN);

            //BUSCO LA LINEA DEL ALBARAN DE LA LINEA
            $rowAlbaranLinea = $bd->VerReg("ALBARAN_LINEA", "ID_ALBARAN_LINEA", $rowLinea->ID_ALBARAN_LINEA);

            //BUSCO LA UBICACION DE EMBARQUE DEL ALMACEN EN EL QUE ESTOY PARA DESCONTAR EL MATERIAL
            $rowUbiEmbarque = $bd->VerRegRest("UBICACION", "ID_ALMACEN = $rowLinea->ID_ALMACEN AND TIPO_UBICACION = 'Embarque'", "No");

            //ME GUARDO LAS LINEAS DE PEDIDO DE SALIDA IMPLICADAS
            $arrPedidosLineaActualizar[] = $rowLinea->ID_PEDIDO_SALIDA_LINEA;

            //COMPRUEBO QUE EXISTE UNA UBICACION DE EMBARQUE DE MATERIAL EN EL ALMACEN QUE ESTOY
            if ($rowUbiEmbarque == false):
                $hayErrorExpedirOrdenTransporte = true;

                //CONFORMO EL ERROR A MOSTRAR
                $rowAlmacenEmbarque = $bd->VerReg("ALMACEN", "ID_ALMACEN", $rowUbiEmbarque->ID_ALMACEN);
                $strError           = $strError . $auxiliar->traduce("La línea número", $administrador->ID_IDIOMA) . " " . $rowAlbaranLinea->NUMERO_LINEA . " " . $auxiliar->traduce("del albarán", $administrador->ID_IDIOMA) . " " . $rowAlbaran->ID_ALBARAN . " " . $auxiliar->traduce("no tiene asignada una ubicación de embaque en el almacén", $administrador->ID_IDIOMA) . " $rowAlmacenEmbarque->REFERENCIA - $rowAlmacenEmbarque->NOMBRE.<br>";
            endif;

            //BUSCO EL MATERIAL UBICACION DE EMBARQUE
            $rowMatUbi                 = $bd->VerRegRest("MATERIAL_UBICACION", "ID_MATERIAL = $rowLinea->ID_MATERIAL AND ID_UBICACION = $rowUbiEmbarque->ID_UBICACION AND ID_MATERIAL_FISICO " . ($rowLinea->ID_MATERIAL_FISICO == NULL ? 'IS NULL' : "= $rowLinea->ID_MATERIAL_FISICO") . " AND ID_TIPO_BLOQUEO " . ($rowLinea->ID_TIPO_BLOQUEO == NULL ? 'IS NULL' : "= $rowLinea->ID_TIPO_BLOQUEO") . " AND ID_ORDEN_TRABAJO_MOVIMIENTO " . ($rowLinea->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? 'IS NULL' : "= $rowLinea->ID_ORDEN_TRABAJO_MOVIMIENTO") . " AND ID_INCIDENCIA_CALIDAD " . ($rowLinea->ID_INCIDENCIA_CALIDAD == NULL ? 'IS NULL' : "= $rowLinea->ID_INCIDENCIA_CALIDAD"));
            $errorCantidadInsuficiente = false;
            if ($rowMatUbi->STOCK_TOTAL < $rowLinea->CANTIDAD):
                $hayErrorExpedirOrdenTransporte = true;

                //CONFORMO EL ERROR A MOSTRAR
                $strError                  = $strError . $auxiliar->traduce("No hay suficiente cantidad de material para servir la línea número", $administrador->ID_IDIOMA) . " " . $rowAlbaranLinea->NUMERO_LINEA . " " . $auxiliar->traduce("del albarán", $administrador->ID_IDIOMA) . " " . $rowAlbaran->ID_ALBARAN . ".<br>";
                $errorCantidadInsuficiente = true;
            endif;

            //BUSCO EL MOVIMIENTO DE SALIDA DE LA LINEA
            $rowMovimientoSalida = $bd->VerReg("MOVIMIENTO_SALIDA", "ID_MOVIMIENTO_SALIDA", $rowLinea->ID_MOVIMIENTO_SALIDA, "No");

            //DECREMENTO MATERIAL UBICACION (EMBARQUE)(SI ES DE CONSTRUCCION EVITAMOS QUE ENTRE EN EL EL PROCESO DE INTEGRIDAD DE MATERIAL FISICO, EN MATERIAL UBICACION SI DEBERIA ENTRAR)
            if ($errorCantidadInsuficiente == false && $hayErrorExpedicionSAP == false):
                $sqlUpdate = "UPDATE MATERIAL_UBICACION SET
                                STOCK_TOTAL = STOCK_TOTAL - $rowLinea->CANTIDAD
                                , STOCK_OK = STOCK_OK - " . ($rowLinea->ID_TIPO_BLOQUEO == NULL ? "$rowLinea->CANTIDAD" : 0) . "
                                , STOCK_BLOQUEADO = STOCK_BLOQUEADO - " . ($rowLinea->ID_TIPO_BLOQUEO == NULL ? 0 : "$rowLinea->CANTIDAD")
                    . ($rowMovimientoSalida->TIPO_MOVIMIENTO == 'TrasladoOMConstruccion' ? ", PENDIENTE_REVISAR_MATERIAL_FISICO_UBICACION = 2, PENDIENTE_REVISAR_MATERIAL_UBICACION_TIPO_BLOQUEO = 1" : "") . "
                                WHERE ID_MATERIAL_UBICACION = $rowMatUbi->ID_MATERIAL_UBICACION";
                $bd->ExecSQL($sqlUpdate);
            endif;

            //CALCULO EL NUEVO ESTADO DE LINEA
            if (
                ($rowMovimientoSalida->TIPO_MOVIMIENTO == 'Venta') ||
                ($rowMovimientoSalida->TIPO_MOVIMIENTO == 'MaterialRechazadoAnuladoEnEntradasAProveedor') ||
                ($rowMovimientoSalida->TIPO_MOVIMIENTO == 'DevolucionNoEstropeadoAProveedor') ||
                ($rowMovimientoSalida->TIPO_MOVIMIENTO == 'ComponentesAProveedor')
            ):
                $estado = 'Expedido';
            elseif (
                ($rowMovimientoSalida->TIPO_MOVIMIENTO == 'TraspasoEntreAlmacenesNoEstropeado') ||
                ($rowMovimientoSalida->TIPO_MOVIMIENTO == 'TrasladoOMConstruccion') ||
                ($rowMovimientoSalida->TIPO_MOVIMIENTO == 'LogisticaInversaConPreparacion') ||
                ($rowMovimientoSalida->TIPO_MOVIMIENTO == 'IntraCentroFisico') ||
                ($rowMovimientoSalida->TIPO_MOVIMIENTO == 'InternoGama') ||
                ($rowMovimientoSalida->TIPO_MOVIMIENTO == 'TraspasoEntreAlmacenesEstropeado')
            ):
                $estado = 'En Tránsito';
            elseif ($rowMovimientoSalida->TIPO_MOVIMIENTO == 'MaterialEstropeadoAProveedor'):
                $estado = 'Recepcionado';
            else:
                $html->PagError("ErrorTipoMovimiento");
            endif;

            //ACTUALIZO LA LINEA DEL MOVIMIENTO DE SALIDA
            $sqlUpdate = "UPDATE MOVIMIENTO_SALIDA_LINEA SET
                            ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                            , ESTADO = '" . $estado . "'
                            , FECHA_EXPEDICION = '" . $fechaHoraExpedicion . "'
                            WHERE ID_MOVIMIENTO_SALIDA_LINEA = $rowLinea->ID_MOVIMIENTO_SALIDA_LINEA";
            $bd->ExecSQL($sqlUpdate);

            if ($rowLinea->FECHA_EXPEDICION_INFORME_FACTURACION == "0000-00-00 00:00:00"):
                //ACTUALIZO LA LINEA DEL MOVIMIENTO DE SALIDA
                $sqlUpdate = "UPDATE MOVIMIENTO_SALIDA_LINEA SET
                              FECHA_EXPEDICION_INFORME_FACTURACION = '" . date("Y-m-d H:i:s") . "'
                              WHERE ID_MOVIMIENTO_SALIDA_LINEA = $rowLinea->ID_MOVIMIENTO_SALIDA_LINEA";
                $bd->ExecSQL($sqlUpdate);
            endif;

            //ACTUALIZO LA CABECERA DEL MOVIMIENTO
            //$movimiento->actualizarEstadoMovimientoSalida($rowLinea->ID_MOVIMIENTO_SALIDA);
            $arrMovimientosActualizar[] = $rowLinea->ID_MOVIMIENTO_SALIDA;

            //SI LA ORDEN DE TRANSPORTE ES CON BULTOS Y LA LINEA ESTA ASIGNADA A UN BULTO, ACTUALIZO EL ESTADO DEL BULTO
            if (($rowOrdenTransporte->CON_BULTOS == 1) && ($rowLinea->ID_BULTO != NULL)):
                //ACTUALIZO EL ESTADO DEL BULTO
                //$orden_preparacion->ActualizarEstadoBulto($rowLinea->ID_BULTO);
                $arrBultosActualizar[] = $rowLinea->ID_BULTO;
            endif;

            //GUARDO EL POSIBLE PEDIDO QUE SE PUEDE ACTUALIZAR
            //$arrPosiblesPedidosActualizar[] = $rowLinea->ID_PEDIDO_SALIDA;

//            //BUSCO NUMERO DE LINEAS DEL PEDIDO DE LA LINEA NO DADAS DE BAJA
//            $num            = $bd->NumRegsTabla("PEDIDO_SALIDA_LINEA", "ID_PEDIDO_SALIDA = $rowLinea->ID_PEDIDO_SALIDA AND BAJA = 0 AND INDICADOR_BORRADO IS NULL");
//            $numFinalizadas = $bd->NumRegsTabla("PEDIDO_SALIDA_LINEA", "ID_PEDIDO_SALIDA = $rowLinea->ID_PEDIDO_SALIDA AND BAJA = 0 AND INDICADOR_BORRADO IS NULL AND CANTIDAD_PENDIENTE_SERVIR = 0");
//
//            //SI HAY TANTAS LINEAS FINALIZADAS COMO LINEAS DEL PEDIDO, ACTUALIZO EL ESTADO DEL PEDIDO
//            if ($num == $numFinalizadas):
//                //VARIABLE PARA CONTROLAR SI TODAS LAS LINEAS HAN SIDO EXPEDIDAS
//                $todasLasLineasExpedidas = true;
//
//                //COMPROBAREMOS QUE TODAS LAS LINEAS ESTEN EXPEDIDAS PARA PASAR EL PEDIDO A FINALIZADO
//                $sqlLineasPedido    = "SELECT *
//                                    FROM PEDIDO_SALIDA_LINEA
//                                    WHERE ID_PEDIDO_SALIDA = $rowLinea->ID_PEDIDO_SALIDA AND INDICADOR_BORRADO IS NULL AND BAJA = 0 AND CANTIDAD_PENDIENTE_SERVIR = 0";
//                $resultLineasPedido = $bd->ExecSQL($sqlLineasPedido);
//                while ($rowLineaPedido = $bd->SigReg($resultLineasPedido)):
//                    //BUSCO EL NUMERO DE LINEAS DE MOVIMIENTOS DE SALIDA NO EXPEDIDOS
//                    $numero = $bd->NumRegsTabla("MOVIMIENTO_SALIDA_LINEA", "ID_PEDIDO_SALIDA_LINEA = $rowLineaPedido->ID_PEDIDO_SALIDA_LINEA AND ESTADO IN ('Reservado para Preparacion', 'En Preparacion', 'Pendiente de Expedir', 'Transmitido a SAP') AND LINEA_ANULADA = 0 AND BAJA = 0");
//
//                    //SI HAY LINEAS QUE NO HAN SIDO EXPEDIDAS
//                    if ($numero > 0):
//                        $todasLasLineasExpedidas = false;
//                    endif;
//                endwhile;
//
//                //TODAS LAS LINEAS DEL PEDIDO ESTAN EXPEDIDAS
//                if ($todasLasLineasExpedidas == true):
//                    $sqlUpdate = "UPDATE PEDIDO_SALIDA SET ESTADO = 'Finalizado' WHERE ID_PEDIDO_SALIDA = $rowLinea->ID_PEDIDO_SALIDA";
//                    $bd->ExecSQL($sqlUpdate);
//                endif;
//            endif;
            $arrPedidosActualizar[] = $rowLinea->ID_PEDIDO_SALIDA;

            //ACCIONES ESPECIFICAS EN FUNCION DEL TIPO DE MOVIMIENTO EXPEDIDO
            if (
                ($rowMovimientoSalida->TIPO_MOVIMIENTO == 'TraspasoEntreAlmacenesNoEstropeado') ||
                ($rowMovimientoSalida->TIPO_MOVIMIENTO == 'TrasladoOMConstruccion') ||
                ($rowMovimientoSalida->TIPO_MOVIMIENTO == 'IntraCentroFisico') ||
                ($rowMovimientoSalida->TIPO_MOVIMIENTO == 'InternoGama') ||
                ($rowMovimientoSalida->TIPO_MOVIMIENTO == 'LogisticaInversaConPreparacion') ||
                ($rowMovimientoSalida->TIPO_MOVIMIENTO == 'TraspasoEntreAlmacenesEstropeado')
            ):

                //ACTUALIZO LA CANTIDAD PENDIENTE DE RECEPCIONAR EN DESTINO (LINEA MOVIMIENTO SALIDA)
                $sqlUpdate = "UPDATE MOVIMIENTO_SALIDA_LINEA SET
                                CANTIDAD_PENDIENTE_DE_RECEPCIONAR_EN_DESTINO = CANTIDAD
                                WHERE ID_MOVIMIENTO_SALIDA_LINEA = $rowLinea->ID_MOVIMIENTO_SALIDA_LINEA";
                $bd->ExecSQL($sqlUpdate);

                //ACTUALIZO LA CANTIDAD PENDIENTE DE RECEPCIONAR EN DESTINO (LINEA ALBARAN)
                $sqlUpdate = "UPDATE ALBARAN_LINEA SET
                                CANTIDAD_PENDIENTE_DE_RECEPCIONAR_EN_DESTINO = CANTIDAD_PENDIENTE_DE_RECEPCIONAR_EN_DESTINO + $rowLinea->CANTIDAD
                                WHERE ID_ALBARAN_LINEA = $rowLinea->ID_ALBARAN_LINEA";
                $bd->ExecSQL($sqlUpdate);

            elseif (
                ($rowMovimientoSalida->TIPO_MOVIMIENTO == 'ComponentesAProveedor') ||
                ($rowMovimientoSalida->TIPO_MOVIMIENTO == 'MaterialEstropeadoAProveedor')
            ): //DOY DE ALTA EL MATERIAL EN LA UBICACION DE PROVEEDOR

                //BUSCO EL MATERIAL UBICACION DESTINO, AL PASAR A RECEPCIONADO, EN PROVEEDOR
                $GLOBALS["NotificaErrorPorEmail"] = "No";
                $rowMatUbiDestino                 = $bd->VerRegRest("MATERIAL_UBICACION", "ID_MATERIAL = $rowLinea->ID_MATERIAL AND ID_UBICACION = $rowLinea->ID_UBICACION_DESTINO AND ID_MATERIAL_FISICO " . ($rowLinea->ID_MATERIAL_FISICO == NULL ? 'IS NULL' : "= $rowLinea->ID_MATERIAL_FISICO") . " AND ID_TIPO_BLOQUEO " . ($rowLinea->ID_TIPO_BLOQUEO == NULL ? 'IS NULL' : "= $rowLinea->ID_TIPO_BLOQUEO") . " AND ID_ORDEN_TRABAJO_MOVIMIENTO " . ($rowLinea->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? 'IS NULL' : "= $rowLinea->ID_ORDEN_TRABAJO_MOVIMIENTO") . " AND ID_INCIDENCIA_CALIDAD " . ($rowLinea->ID_INCIDENCIA_CALIDAD == NULL ? 'IS NULL' : "= $rowLinea->ID_INCIDENCIA_CALIDAD"), "No");
                unset($GLOBALS["NotificaErrorPorEmail"]);
                if ($rowMatUbiDestino == false):
                    //CREO MATERIAL UBICACION SI NO EXISTE
                    $sql = "INSERT INTO MATERIAL_UBICACION SET
                                ID_MATERIAL = $rowLinea->ID_MATERIAL
                                , ID_MATERIAL_FISICO = " . ($rowLinea->ID_MATERIAL_FISICO == NULL ? 'NULL' : "$rowLinea->ID_MATERIAL_FISICO") . "
                                , ID_UBICACION = $rowLinea->ID_UBICACION_DESTINO
                                , ID_TIPO_BLOQUEO = " . ($rowLinea->ID_TIPO_BLOQUEO == NULL ? 'NULL' : "$rowLinea->ID_TIPO_BLOQUEO") . "
                                , ID_ORDEN_TRABAJO_MOVIMIENTO = " . ($rowLinea->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? 'NULL' : "$rowLinea->ID_ORDEN_TRABAJO_MOVIMIENTO") . "
                                , ID_INCIDENCIA_CALIDAD = " . ($rowLinea->ID_INCIDENCIA_CALIDAD == NULL ? 'NULL' : "$rowLinea->ID_INCIDENCIA_CALIDAD");
                    $bd->ExecSQL($sql);
                    $idMatUbi = $bd->IdAsignado();
                else:
                    $idMatUbi = $rowMatUbiDestino->ID_MATERIAL_UBICACION;
                endif;

                //INCREMENTO MATERIAL UBICACION (EN PROVEEDOR)
                $sql = "UPDATE MATERIAL_UBICACION SET
                            STOCK_TOTAL = STOCK_TOTAL + $rowLinea->CANTIDAD
                            , STOCK_OK = STOCK_OK + " . ($rowLinea->ID_TIPO_BLOQUEO == NULL ? $rowLinea->CANTIDAD : 0) . "
                            , STOCK_BLOQUEADO = STOCK_BLOQUEADO + " . ($rowLinea->ID_TIPO_BLOQUEO == NULL ? 0 : $rowLinea->CANTIDAD) . "
                            WHERE ID_MATERIAL_UBICACION = $idMatUbi";
                $bd->ExecSQL($sql);

                //GENERO EL ASIENTO EN PROVEEDOR PARA INCREMENTAR EL STOCK
                $sql = "INSERT INTO ASIENTO SET
                            ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                            , ID_MATERIAL = $rowLinea->ID_MATERIAL
                            , TIPO_LOTE = '" . $rowLinea->TIPO_LOTE . "'
                            , ID_MATERIAL_FISICO = " . ($rowLinea->ID_MATERIAL_FISICO == NULL ? 'NULL' : "$rowLinea->ID_MATERIAL_FISICO") . "
                            , ID_UBICACION = $rowLinea->ID_UBICACION_DESTINO
                            , FECHA = '" . date("Y-m-d H:i:s") . "'
                            , CANTIDAD = $rowLinea->CANTIDAD
                            , STOCK_OK = $rowLinea->CANTIDAD
                            , STOCK_BLOQUEADO = 0
                            , ID_TIPO_BLOQUEO = " . ($rowLinea->ID_TIPO_BLOQUEO == NULL ? 'NULL' : "$rowLinea->ID_TIPO_BLOQUEO") . "
                            , ID_ORDEN_TRABAJO_MOVIMIENTO = " . ($rowLinea->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? 'NULL' : "$rowLinea->ID_ORDEN_TRABAJO_MOVIMIENTO") . "
                            , ID_MOVIMIENTO_SALIDA_LINEA = $rowLinea->ID_MOVIMIENTO_SALIDA_LINEA
                            , ID_INCIDENCIA_CALIDAD = " . ($rowLinea->ID_INCIDENCIA_CALIDAD == NULL ? 'NULL' : "$rowLinea->ID_INCIDENCIA_CALIDAD") . "
                            , OBSERVACIONES = ''
                            , TIPO_ASIENTO = '" . ($rowMovimientoSalida->TIPO_MOVIMIENTO == 'ComponentesAProveedor' ? 'Recepción de Componentes en Proveedor' : 'Recepción de Material Estropeado en Proveedor') . "'";
                $bd->ExecSQL($sql);
                $idAsiento = $bd->IdAsignado();

            endif;
            //FIN ACCIONES ESPECIFICAS EN FUNCION DEL TIPO DE MOVIMIENTO EXPEDIDO

            //ALMACENO LOS Ids PARA LAS ALERTAS DE NECESIDADES
            $arrIdsMovimientosLineasExpedidos[] = $rowLinea->ID_MOVIMIENTO_SALIDA_LINEA;
        endwhile;
        //FIN RECORRO LAS LINEAS PARA REALIZAR LAS DIFERENTES OPERACIONES

//        //EL ARRAY DE PEDIDOS ME QUEDO CON LOS VALORES UNICOS
//        $arrPosiblesPedidosActualizar = array_unique($arrPosiblesPedidosActualizar);
//
//        //RECORRO LOS POSIBLES PEDIDOS A ACTUALIZAR
//        foreach ($arrPosiblesPedidosActualizar as $idPosiblePedidoActualizar):
//            //BUSCO NUMERO DE LINEAS DEL PEDIDO DE LA LINEA NO DADAS DE BAJA
//            $num            = $bd->NumRegsTabla("PEDIDO_SALIDA_LINEA", "ID_PEDIDO_SALIDA = $idPosiblePedidoActualizar AND BAJA = 0 AND INDICADOR_BORRADO IS NULL");
//            $numFinalizadas = $bd->NumRegsTabla("PEDIDO_SALIDA_LINEA", "ID_PEDIDO_SALIDA = $idPosiblePedidoActualizar AND BAJA = 0 AND INDICADOR_BORRADO IS NULL AND CANTIDAD_PENDIENTE_SERVIR = 0");
//
//            //SI HAY TANTAS LINEAS FINALIZADAS COMO LINEAS DEL PEDIDO, ACTUALIZO EL ESTADO DEL PEDIDO
//            if ($num == $numFinalizadas):
//                //VARIABLE PARA CONTROLAR SI TODAS LAS LINEAS HAN SIDO EXPEDIDAS
//                $todasLasLineasExpedidas = true;
//
//                //COMPROBAREMOS QUE TODAS LAS LINEAS ESTEN EXPEDIDAS PARA PASAR EL PEDIDO A FINALIZADO
//                $sqlLineasPedido    = "SELECT *
//                                        FROM PEDIDO_SALIDA_LINEA
//                                        WHERE ID_PEDIDO_SALIDA = $idPosiblePedidoActualizar AND INDICADOR_BORRADO IS NULL AND BAJA = 0 AND CANTIDAD_PENDIENTE_SERVIR = 0";
//                $resultLineasPedido = $bd->ExecSQL($sqlLineasPedido);
//                while ($rowLineaPedido = $bd->SigReg($resultLineasPedido)):
//                    //BUSCO EL NUMERO DE LINEAS DE MOVIMIENTOS DE SALIDA NO EXPEDIDOS
//                    $numero = $bd->NumRegsTabla("MOVIMIENTO_SALIDA_LINEA", "ID_PEDIDO_SALIDA_LINEA = $rowLineaPedido->ID_PEDIDO_SALIDA_LINEA AND ESTADO IN ('Reservado para Preparacion', 'En Preparacion', 'Pendiente de Expedir', 'Transmitido a SAP') AND LINEA_ANULADA = 0 AND BAJA = 0");
//
//                    //SI HAY LINEAS QUE NO HAN SIDO EXPEDIDAS
//                    if ($numero > 0):
//                        $todasLasLineasExpedidas = false;
//                    endif;
//                endwhile;
//
//                //TODAS LAS LINEAS DEL PEDIDO ESTAN EXPEDIDAS
//                if ($todasLasLineasExpedidas == true):
//                    $sqlUpdate = "UPDATE PEDIDO_SALIDA SET ESTADO = 'Finalizado' WHERE ID_PEDIDO_SALIDA = $idPosiblePedidoActualizar";
//                    $bd->ExecSQL($sqlUpdate);
//                endif;
//            endif;
//        endforeach;
//        //FIN RECORRO LOS POSIBLES PEDIDOS A ACTUALIZAR

        //DEJO LOS VALORES UNICOS DE LOS ARRAY A ACTUALIZAR
        $arrMovimientosActualizar  = array_unique( (array)$arrMovimientosActualizar);
        $arrBultosActualizar       = array_unique( (array)$arrBultosActualizar);
        $arrPedidosActualizar      = array_unique( (array)$arrPedidosActualizar);
        $arrPedidosLineaActualizar = array_unique( (array)$arrPedidosLineaActualizar);

        //ACTUALIZO LOS DIFERENTES OBJETOS

        //ACTUALIZO LOS MOVIMIENTOS
        foreach ($arrMovimientosActualizar as $idMovimientoSalida):
            $movimiento->actualizarEstadoMovimientoSalida($idMovimientoSalida);
        endforeach;
        //ACTUALIZO LOS BULTOS
        foreach ($arrBultosActualizar as $idBulto):
            $orden_preparacion->ActualizarEstadoBulto($idBulto);
        endforeach;
        //ACTUALIZO LOS PEDIDOS
        foreach ($arrPedidosActualizar as $idPedidoSalida):
            //BUSCO NUMERO DE LINEAS DEL PEDIDO DE LA LINEA NO DADAS DE BAJA
            $num            = $bd->NumRegsTabla("PEDIDO_SALIDA_LINEA", "ID_PEDIDO_SALIDA = $idPedidoSalida AND BAJA = 0 AND INDICADOR_BORRADO IS NULL");
            $numFinalizadas = $bd->NumRegsTabla("PEDIDO_SALIDA_LINEA", "ID_PEDIDO_SALIDA = $idPedidoSalida AND BAJA = 0 AND INDICADOR_BORRADO IS NULL AND CANTIDAD_PENDIENTE_SERVIR = 0");

            //SI HAY TANTAS LINEAS FINALIZADAS COMO LINEAS DEL PEDIDO, ACTUALIZO EL ESTADO DEL PEDIDO
            if ($num == $numFinalizadas):
                //VARIABLE PARA CONTROLAR SI TODAS LAS LINEAS HAN SIDO EXPEDIDAS
                $todasLasLineasExpedidas = true;

                //COMPROBAREMOS QUE TODAS LAS LINEAS ESTEN EXPEDIDAS PARA PASAR EL PEDIDO A FINALIZADO
                $sqlLineasPedido    = "SELECT *
                                    FROM PEDIDO_SALIDA_LINEA
                                    WHERE ID_PEDIDO_SALIDA = $idPedidoSalida AND INDICADOR_BORRADO IS NULL AND BAJA = 0 AND CANTIDAD_PENDIENTE_SERVIR = 0";
                $resultLineasPedido = $bd->ExecSQL($sqlLineasPedido);
                while ($rowLineaPedido = $bd->SigReg($resultLineasPedido)):
                    //BUSCO EL NUMERO DE LINEAS DE MOVIMIENTOS DE SALIDA NO EXPEDIDOS
                    $numero = $bd->NumRegsTabla("MOVIMIENTO_SALIDA_LINEA", "ID_PEDIDO_SALIDA_LINEA = $rowLineaPedido->ID_PEDIDO_SALIDA_LINEA AND ESTADO IN ('Reservado para Preparacion', 'En Preparacion', 'Pendiente de Expedir', 'Transmitido a SAP') AND LINEA_ANULADA = 0 AND BAJA = 0");

                    //SI HAY LINEAS QUE NO HAN SIDO EXPEDIDAS
                    if ($numero > 0):
                        $todasLasLineasExpedidas = false;
                    endif;
                endwhile;

                //TODAS LAS LINEAS DEL PEDIDO ESTAN EXPEDIDAS
                if ($todasLasLineasExpedidas == true):
                    $sqlUpdate = "UPDATE PEDIDO_SALIDA SET ESTADO = 'Finalizado' WHERE ID_PEDIDO_SALIDA = $idPedidoSalida";
                    $bd->ExecSQL($sqlUpdate);
                endif;
            endif;
        endforeach;
        //FIN ACTUALIZO LOS DIFERENTES OBJETOS

        /************************************ HABILITO TRIGGERS IMPLICADOS ************************************/
        $sqlHabilitarTriggers = "SET @Trigger_MOVIMIENTO_SALIDA_LINEA_UPDATE_Deshabilitado = NULL";
        $bd->ExecSQL($sqlHabilitarTriggers);
        /********************************** FIN HABILITO TRIGGERS IMPLICADOS **********************************/

        //ACTUALIZO UNA LINEA DE MOVIMIENTO DE CADA LINEA DE PEDIDO
        foreach ($arrPedidosLineaActualizar as $idPedidoLineaSalida):
            //BUSCO UNA LINEA DE MOVIMIENTO DE SALIDA LINEA DE ESTA LINEA DE PEDIDO
            $rowMovimientoSalidaLinea = $bd->VerRegRest("MOVIMIENTO_SALIDA_LINEA", "ID_PEDIDO_SALIDA_LINEA = $idPedidoLineaSalida AND LINEA_ANULADA = 0 AND BAJA = 0", "No");

            //BUSCO EL PEDIDO LINEA PARA GUARDAR LA OTL EN CASO DE QUE TENGA
            $rowPedidoSalidaLinea = $bd->VerReg("PEDIDO_SALIDA_LINEA", "ID_PEDIDO_SALIDA_LINEA", $idPedidoLineaSalida, "No");

            //GUARDAMOS LA OTL
            if ($rowPedidoSalidaLinea->ID_ORDEN_TRABAJO_LINEA != NULL):
                $arrOrdenTrabajoLinea[] = $rowPedidoSalidaLinea->ID_ORDEN_TRABAJO_LINEA;
            endif;

            //ACTUALIZO EL ESTADO DE LA LINEA DEL PEDIDO DE SALIDA
            $movimiento->actualizarEstadoLineaPedidoSalida($rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA_LINEA);
            //ACTUALIZO EL ESTADO DE LA NECESIDAD DE LA LINEA DEL MOVIMIENTO DE SALIDA
            $movimiento->actualizarEstadoLineaNecesidad($rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA);
        endforeach;

        //SI SE HAN PREPARADO LINEAS DE OTL
        if (count( (array)$arrOrdenTrabajoLinea) > 0):

            //UNIFICAMOS EL ARRAY
            $arrOrdenTrabajoLinea = array_unique( (array)$arrOrdenTrabajoLinea);

            //RECORREMOS
            foreach ($arrOrdenTrabajoLinea as $idOTL):

                //SI ESTAN LAS LINEAS COMPLETAMENTE PREPARADAS Y EXPEDIDAS, MARCAMOS LA OTL PARA QUE SE RECALCULE LA FECHA PLANIFICADA
                $numLineasPedido           = $bd->NumRegsTabla("PEDIDO_SALIDA_LINEA", "ID_ORDEN_TRABAJO_LINEA = " . $idOTL . " AND BAJA = 0");
                $numLineasPedidoPreparadas = $bd->NumRegsTabla("PEDIDO_SALIDA_LINEA", "ID_ORDEN_TRABAJO_LINEA = " . $idOTL . " AND CANTIDAD_PENDIENTE_SERVIR = 0 AND BAJA = 0");
                if (($numLineasPedido > 0) && ($numLineasPedido == $numLineasPedidoPreparadas)):
                    //COMPROBAMOS SI TODA LA CANTIDAD SE HA EXPEDIDO
                    $sqlCantidadNoExpedida    = "SELECT SUM(MSL.CANTIDAD) AS CANTIDAD_NO_EXPEDIDA
                                        FROM MOVIMIENTO_SALIDA_LINEA MSL
                                        INNER JOIN PEDIDO_SALIDA_LINEA PSL ON PSL.ID_PEDIDO_SALIDA_LINEA = MSL.ID_PEDIDO_SALIDA_LINEA
                                        WHERE PSL.ID_ORDEN_TRABAJO_LINEA = " . $idOTL . " AND MSL.ESTADO NOT IN ('Expedido', 'En Tránsito', 'Recepcionado') AND MSL.LINEA_ANULADA = 0 AND MSL.BAJA = 0";
                    $resultCantidadNoExpedida = $bd->ExecSQL($sqlCantidadNoExpedida);
                    $rowCantidadNoExpedida    = $bd->SigReg($resultCantidadNoExpedida);
                    //OBTENEMOS LA CANTIDAD NO EXPEDIDA
                    $cantidadNoExpedida = 0;
                    if ($rowCantidadNoExpedida != false && $rowCantidadNoExpedida->CANTIDAD_NO_EXPEDIDA != NULL):
                        $cantidadNoExpedida = $rowCantidadNoExpedida->CANTIDAD_NO_EXPEDIDA;
                    endif;
                    //SI ESTA EXPEDIDA COMPLETAMENTE
                    if ($cantidadNoExpedida == 0):
                        if ($orden_trabajo->esTransmitibleInterfaz("Expedicion", "Expedicion (puesta en transito en SCS)", "T06", $idOTL)):
                            //MARCAMOS LA LINEA
                            $sqlUpdate = "UPDATE ORDEN_TRABAJO_LINEA SET
                                          DISPONIBILIDAD_FECHA_PLANIFICADA = 'Pendiente Tratar'
                                          WHERE ID_ORDEN_TRABAJO_LINEA = " . $idOTL;
                            $bd->ExecSQL($sqlUpdate);
                        endif;

                        if ($orden_trabajo->esTransmitibleInterfaz("Expedicion", "Expedicion (puesta en transito en SCS)", "T01", $idOTL)):
                            //INICIO UNA TRANSACCION PARA LA INTERFAZ DEL SEMAFORO PARA INFORMAR A SAP DEL BLOQUEO Y CANTIDAD RECEPCIONADA
                            $bd->begin_transaction();


                            //COMPRUEBO SI TIENE INCIDENCIA DE SISTEMA CREADA
                            $num = $bd->NumRegsTabla("INCIDENCIA_SISTEMA", "TIPO = '" . INCIDENCIA_TIPO_INTERFAZ_SIN_TRANSMITIR . "' AND SUBTIPO = '" . INCIDENCIA_SUBTIPO_ORDENES_TRABAJO_SEMAFORO . "' AND ID_OBJETO = $idOTL AND TABLA_OBJETO = 'ORDEN_TRABAJO_LINEA' AND ESTADO <> 'Finalizada'");
                            if ($num == 0): //SI NO TIENE INCIDENCIA SISTEMA CREADA REALIZO LA LLAMADA A SAP


                                $resultado = $sap->OrdenesTrabajoSemaforo($idOTL);
                                if ($resultado['RESULTADO'] != 'OK'): //No se ha podido grabar la información en SAP
                                    $strError .= $auxiliar->traduce("Se han producido errores al transmitir la interfaz del semaforo de ordenes de trabajo a SAP", $administrador->ID_IDIOMA) . ". " . $datosLinea;
                                    $strError .= "<br>";
                                    //NO INFORMO DE LOS ERRORES PORQUE ES UNA INTERFAZ ASINCRONA DONDE NO ENVIAN ERRORES SI SE PRODUCEN


                                    //DESHAGO LA TRANSACCION
                                    $bd->rollback_transaction();


                                    //GRABO EL ERROR PRODUCIDO EN BASE DE DATOS
                                    $sap->InsertarErrores($resultado);

                                    //SI FALLA LA TRANSMISION DEL SEMAFORO Y NO ESTA YA COMO PENDIENTE, LO GRABO PARA SABER QUE ESTA PENDIENTE DE REALIZARSE
                                    $num = $bd->NumRegsTabla("INTERFACES_PENDIENTES_TRANSMITIR", "INTERFAZ_PENDIENTE_TRANSMITIR = 'ordenes_trabajo_semaforo' AND ID_OBJETO = $idOTL AND TABLA_OBJETO = 'ORDEN_TRABAJO_LINEA' AND BAJA = 0");
                                    if ($num == 0):
                                        $sqlInsert = "INSERT INTO INTERFACES_PENDIENTES_TRANSMITIR SET
                                                      INTERFAZ_PENDIENTE_TRANSMITIR = 'ordenes_trabajo_semaforo'
                                                      , ID_OBJETO = $idOTL
                                                      , TABLA_OBJETO = 'ORDEN_TRABAJO_LINEA'
                                                      , NUMERO_LLAMADAS_INTERFAZ = '0'
                                                      , BAJA = 0";
                                        $bd->ExecSQL($sqlInsert);
                                    endif;


                                    //ACTUALIZO EL ESTADO TRATADA DE LA LINEA DE LA ORDEN DE TRABAJO
                                    $orden_trabajo->ActualizarLinea_Estados($idOTL);
                                endif;
                            endif;

                            //FINALIZO LA TRANSACCION PARA LA INTERFAZ DEL SEMAFORO PARA INFORMAR A SAP DEL BLOQUEO Y CANTIDAD RECEPCIONADA
                            $bd->commit_transaction();
                        endif;

                        if ($orden_trabajo->esTransmitibleInterfaz("Expedicion", "Expedicion (puesta en transito en SCS)", "T02", $idOTL)):

                            //INICIO UNA TRANSACCION PARA LA INTERFAZ DE LA RESERVA DE MATERIAL PARA INFORMAR A SO99 DEL BLOQUEO Y CANTIDAD PEDIDA
                            $bd->begin_transaction();

                            //COMPRUEBO SI TIENE INCIDENCIA DE SISTEMA CREADA
                            $num = $bd->NumRegsTabla("INCIDENCIA_SISTEMA", "TIPO = '" . INCIDENCIA_TIPO_INTERFAZ_SIN_TRANSMITIR . "' AND SUBTIPO = '" . INCIDENCIA_SUBTIPO_ORDENES_TRABAJO_RESERVA . "' AND ID_OBJETO = $idOTL AND TABLA_OBJETO = 'ORDEN_TRABAJO_LINEA' AND ESTADO <> 'Finalizada'");
                            if ($num == 0): //SI NO TIENE INCIDENCIA SISTEMA CREADA REALIZO LA LLAMADA A SAP

                                $resultado = $sap->OrdenesTrabajoReserva($idOTL);
                                if ($resultado['RESULTADO'] != 'OK'): //No se ha podido grabar la información en SAP
                                    $strError .= $auxiliar->traduce("Se han producido errores al transmitir la interfaz de la reserva de material de ordenes de trabajo a SO99", $administrador->ID_IDIOMA) . ". " . $datosLinea;
                                    foreach ($resultado['ERRORES'] as $arr):
                                        foreach ($arr as $mensaje_error):
                                            $strError = $strError . $mensaje_error . "<br>";
                                        endforeach;
                                    endforeach;


                                    //DESHAGO LA TRANSACCION
                                    $bd->rollback_transaction();


                                    //GRABO EL ERROR PRODUCIDO EN BASE DE DATOS
                                    $sap->InsertarErrores($resultado);

                                    //SI FALLA LA TRANSMISION DEL SEMAFORO Y NO ESTA YA COMO PENDIENTE, LO GRABO PARA SABER QUE ESTA PENDIENTE DE REALIZARSE
                                    $num = $bd->NumRegsTabla("INTERFACES_PENDIENTES_TRANSMITIR", "INTERFAZ_PENDIENTE_TRANSMITIR = 'ordenes_trabajo_reserva' AND ID_OBJETO = $idOTL AND TABLA_OBJETO = 'ORDEN_TRABAJO_LINEA' AND BAJA = 0");
                                    if ($num == 0):
                                        $sqlInsert = "INSERT INTO INTERFACES_PENDIENTES_TRANSMITIR SET
                                                      INTERFAZ_PENDIENTE_TRANSMITIR = 'ordenes_trabajo_reserva'
                                                      , ID_OBJETO = $idOTL
                                                      , TABLA_OBJETO = 'ORDEN_TRABAJO_LINEA'
                                                      , NUMERO_LLAMADAS_INTERFAZ = '0'
                                                      , BAJA = 0";
                                        $bd->ExecSQL($sqlInsert);
                                    endif;
                                endif;
                            endif;
                            //FINALIZO UNA TRANSACCION PARA LA INTERFAZ DE LA RESERVA DE MATERIAL PARA INFORMAR A SO99 DEL BLOQUEO Y CANTIDAD PEDIDA
                            $bd->commit_transaction();
                        endif;

                    endif;
                endif;
            endforeach;
        endif;


        //ACTUALIZO LA FECHA EXPEDICION DE LA ORDEN DE TRANSPORTE
        $numLineasOrdenTransporte          = $bd->NumRegsTabla("MOVIMIENTO_SALIDA_LINEA", "ID_EXPEDICION = $rowOrdenTransporte->ID_EXPEDICION AND LINEA_ANULADA = 0 AND BAJA = 0");
        $numLineasOrdenTransporteExpedidas = $bd->NumRegsTabla("MOVIMIENTO_SALIDA_LINEA", "ID_EXPEDICION = $rowOrdenTransporte->ID_EXPEDICION AND LINEA_ANULADA = 0 AND BAJA = 0 AND ESTADO IN ('Expedido', 'En Tránsito', 'Recepcionado')");
        if (($numLineasOrdenTransporte > 0) && ($numLineasOrdenTransporteExpedidas > 0) && ($rowOrdenTransporte->FECHA_EXPEDICION == '0000-00-00 00:00:00')):
            $sqlUpdate = "UPDATE EXPEDICION SET
                            FECHA_EXPEDICION = '" . $fechaHoraExpedicion . "'
                            WHERE ID_EXPEDICION = $rowOrdenTransporte->ID_EXPEDICION";
            $bd->ExecSQL($sqlUpdate);
        elseif (($numLineasOrdenTransporte > 0) && ($numLineasOrdenTransporteExpedidas == 0)):
            $sqlUpdate = "UPDATE EXPEDICION SET
                            FECHA_EXPEDICION = '0000-00-00 00:00:00'
                            WHERE ID_EXPEDICION = $rowOrdenTransporte->ID_EXPEDICION";
            $bd->ExecSQL($sqlUpdate);
        endif;

        //ACTUALIZO EL ESTADO DE LA ORDEN DE TRANSPORTE
        $this->actualizar_estado_orden_transporte($rowOrdenTransporte->ID_EXPEDICION);

        //BUSCO EL DESTINO DE LAS LINEAS DE LA ORDEN DE TRANSPORTE PARA COMPROBAR SI HAY QUE RECEPCIONAR DE FORMA AUTOMATICA LA ORDEN DE TRANSPORTE EN DESTINO
        $sqlLineas    = "SELECT DISTINCT(ALB.ID_ALBARAN)
                        FROM ALBARAN ALB
                        INNER JOIN MOVIMIENTO_SALIDA_LINEA MSL ON MSL.ID_ALBARAN = ALB.ID_ALBARAN
                        INNER JOIN ALMACEN A ON A.ID_ALMACEN = ALB.ID_ALMACEN_DESTINO
                        INNER JOIN CENTRO C ON C.ID_CENTRO = A.ID_CENTRO
                        WHERE MSL.ID_EXPEDICION = '" . $rowOrdenTransporte->ID_EXPEDICION . "' AND MSL.BAJA = 0 AND MSL.LINEA_ANULADA = 0 AND ALB.ID_ALMACEN_DESTINO IS NOT NULL AND A.TIPO_ALMACEN = 'acciona' AND A.CATEGORIA_ALMACEN = 'Construccion: Instalacion'";
        $resultLineas = $bd->ExecSQL($sqlLineas);
        while ($rowLinea = $bd->SigReg($resultLineas)): //RECORRO LA SELECCION DE LINEAS DE LA ORDEN DE TRANSPORTE
            $this->RecepcionAutomaticaAlbaranEnDestino($rowLinea->ID_ALBARAN);
        endwhile;
        //FIN BUSCO EL DESTINO DE LAS LINEAS DE LA ORDEN DE TRANSPORTE PARA COMPROBAR SI HAY QUE RECEPCIONAR DE FORMA AUTOMATICA LA ORDEN DE TRANSPORTE EN DESTINO

        // LOG MOVIMIENTOS
        $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Expedición", $rowOrdenTransporte->ID_EXPEDICION, "Expedicion orden de transporte: " . $rowOrdenTransporte->ID_EXPEDICION);

        //ENVIO ALERTAS DE NECESIDAD SI ES NECESARIO
        if ($hayErrorExpedirOrdenTransporte == false):
            $arrIdsMovimientosLineasExpedidos = array_unique( (array)$arrIdsMovimientosLineasExpedidos);
            $necesidad->EnviarNotificacionEmail_ExpedicionMovimientosLineas($arrIdsMovimientosLineasExpedidos);
        endif;

        //DECLARO UN ARRAY PARA DEVOLVER DATOS
        $arrDevuelto            = array();
        $arrDevuelto['errores'] = $strError;

        //DEVUELVO EL ARRAY
        return $arrDevuelto;
    }

    function cancelar_expedicion_orden_transporte($idOrdenTransporte)
    {
        global $bd;
        global $auxiliar;
        global $administrador;
        global $html;
        global $mat;
        global $movimiento;
        global $orden_preparacion;
        global $necesidad;
        global $orden_trabajo;
        global $sap;

        //RECUPERO LA VARIABLE GLOBAL DE TEXTO ERROR
        global $strError;
        $strError = $idOrdenTransporte;

        //VARIABLE PARA SABER SI HAY ERROR EN LA ORDEN DE TRANSPORTE
        $hayErrorCancelarExpedicionOrdenTransporte = false;

        //ARRAY DE LINEAS DE MOVIMIENTOS DE SALIDA PARA LAS ALERTAS DE NECESIDADES
        $arrIdsMovimientosLineasCanceladosExpedicion = array();

        //VARIABLE PARA SABER SI LA ORDEN DE TRANSPORTE SOLO INCLUYE MATERIAL QUE SE EXPIDIO A UNN ALMACEN CON CATEGORIA 'Construccion: Instalacion'
        $OrdenTransporteDestinoSoloAlmacenCategoriaContruccionInstalacion = true;

        //BUSCO LA ORDEN DE TRANSPORTE
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowOrdenTransporte               = $bd->VerReg("EXPEDICION", "ID_EXPEDICION", $idOrdenTransporte, "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);

        //COMPRUEBO QUE EXISTA LA ORDEN DE TRANSPORTE
        $html->PagErrorCondicionado($rowOrdenTransporte, "==", false, "OrdenTransporteNoEncontrada");

        //COMPRUEBO SI TODAS LAS LINEAS DE LA ORDEN DE TRANSPORTE FUERON EXPEDIDAS A UN ALMACEN CON CATEGORIA 'Construccion: Instalacion', LA MAYORIA NO LO SON
        $sqlLineas    = "SELECT DISTINCT(A.CATEGORIA_ALMACEN)
                        FROM MOVIMIENTO_SALIDA_LINEA MSL
                        INNER JOIN ALMACEN A ON A.ID_ALMACEN = MSL.ID_ALMACEN_DESTINO
                        WHERE MSL.ID_EXPEDICION = '" . $rowOrdenTransporte->ID_EXPEDICION . "' AND MSL.BAJA = 0 AND MSL.LINEA_ANULADA = 0 AND MSL.ID_ALMACEN_DESTINO IS NOT NULL AND A.TIPO_ALMACEN = 'acciona'";
        $resultLineas = $bd->ExecSQL($sqlLineas);
        if ($bd->NumRegs($resultLineas) == 0):
            $OrdenTransporteDestinoSoloAlmacenCategoriaContruccionInstalacion = false;
        else:
            while ($rowLinea = $bd->SigReg($resultLineas)): //RECORRO LA SELECCION DE LINEAS DE LA ORDEN DE TRANSPORTE
                if ($rowLinea->CATEGORIA_ALMACEN != 'Construccion: Instalacion'):
                    $OrdenTransporteDestinoSoloAlmacenCategoriaContruccionInstalacion = false;
                endif;
            endwhile;
        endif;

        //COMPRUEBO EL ESTADO DE LA ORDEN DE TRANSPORTE
        if ($OrdenTransporteDestinoSoloAlmacenCategoriaContruccionInstalacion == true):
            $html->PagErrorCondicionado($rowOrdenTransporte->ESTADO, "!=", 'Recepcionada', "EstadoOrdenTransporteDiferenteRecepcionada");
        elseif (($rowOrdenTransporte->ESTADO == 'Recepcionada') && ($OrdenTransporteDestinoSoloAlmacenCategoriaContruccionInstalacion == false)):
            $html->PagError("EstadoOrdenTransporteDiferenteExpedidaEnTransito");
        else:
            $html->PagErrorCondicionado((($rowOrdenTransporte->ESTADO != 'Expedida') && ($rowOrdenTransporte->ESTADO != 'En Tránsito')), "==", true, "EstadoOrdenTransporteDiferenteExpedidaEnTransito");
        endif;

        //SI LA ORDEN DE TRANSPORTE ES CON BULTOS, COMPRUEBO QUE TODOS ESTEN EXPEDIDOS
        if ($rowOrdenTransporte->CON_BULTOS == 1):
            $num = $bd->NumRegsTabla("BULTO", "ID_EXPEDICION = $rowOrdenTransporte->ID_EXPEDICION AND ESTADO <> 'Expedido'");
            $html->PagErrorCondicionado($num, ">", 0, "OrdenTransporteConBultosEstadoDiferenteExpedidos");
        endif;

        //COMPRUEBO EL ESTADO DE LAS LINEAS
        if ($OrdenTransporteDestinoSoloAlmacenCategoriaContruccionInstalacion == true):
            $num = $bd->NumRegsTabla("MOVIMIENTO_SALIDA_LINEA", "ID_EXPEDICION = $rowOrdenTransporte->ID_EXPEDICION AND ESTADO <> 'Recepcionado' AND ENVIADO_SAP = 1 AND LINEA_ANULADA = 0 AND BAJA = 0");
            $html->PagErrorCondicionado($num, ">", 0, "OrdenTransporteConLineasEstadoDiferenteRecepcionado");
        else:
            $num = $bd->NumRegsTabla("MOVIMIENTO_SALIDA_LINEA", "ID_EXPEDICION = $rowOrdenTransporte->ID_EXPEDICION AND ESTADO <> 'Expedido' AND ESTADO <> 'En Tránsito' AND ENVIADO_SAP = 1 AND LINEA_ANULADA = 0 AND BAJA = 0");
            $html->PagErrorCondicionado($num, ">", 0, "OrdenTransporteConLineasEstadoDiferenteExpedidoEnTransito");
        endif;

        //ARRAY DE OBJETOS A ACTUALIZAR DESPUES DEL WHILE
        $arrPedidosLineaActualizar = array();

        /************************************ DESHABILITO TRIGGERS IMPLICADOS ************************************/
        $sqlDeshabilitarTriggers = "SET @Trigger_MOVIMIENTO_SALIDA_LINEA_UPDATE_Deshabilitado = 1";
        $bd->ExecSQL($sqlDeshabilitarTriggers);
        /********************************** FIN DESHABILITO TRIGGERS IMPLICADOS **********************************/

        //SI ES DE CONTRUCCION, CANCELO LA RECEPCION AUTOMATICA EN DESTINO
        if ($OrdenTransporteDestinoSoloAlmacenCategoriaContruccionInstalacion == true):
            //RECORRO LAS LINEAS PARA REALIZAR LAS DIFERENTES OPERACIONES
            $sqlLineas    = "SELECT *
                            FROM MOVIMIENTO_SALIDA_LINEA MSL
                            WHERE MSL.ID_EXPEDICION = $rowOrdenTransporte->ID_EXPEDICION AND ESTADO IN ('Recepcionado') AND ENVIADO_SAP = 1 AND LINEA_ANULADA = 0 AND BAJA = 0";
            $resultLineas = $bd->ExecSQL($sqlLineas);
            while ($rowLinea = $bd->SigReg($resultLineas)):
                //BUSCO EL PEDIDO DE SALIDA
                $rowPed = $bd->VerReg("PEDIDO_SALIDA", "ID_PEDIDO_SALIDA", $rowLinea->ID_PEDIDO_SALIDA);

                //ME GUARDO LAS LINEAS DE PEDIDO DE SALIDA IMPLICADAS
                $arrPedidosLineaActualizar[] = $rowLinea->ID_PEDIDO_SALIDA_LINEA;

                //SI ES TRASLADO DESCONECTADO ES DE CONTRUCCION DE UN PARQUE CON RECEPCION AUTOMATICA
                if (($rowPed->TIPO_PEDIDO == 'Traslados OM Construccion') ||
                    (($rowPed->TIPO_PEDIDO == 'Traslado') && ($rowPed->TIPO_TRASLADO == 'Desconectado'))
                ):
                    //SI TIENE ORDEN DE MONTAJE, BUSCAMOS LA UBICACION DE TIPO MAQUINA
                    if ($rowPed->ID_ORDEN_MONTAJE != NULL):
                        //BUSCO LA ORDEN DE MONTAJE
                        $rowOrdenMontaje = $bd->VerReg("ORDEN_MONTAJE", "ID_ORDEN_MONTAJE", $rowPed->ID_ORDEN_MONTAJE, "No");

                        //BUSCO LA LINEA DE ORDEN DE MONTAJE, PARA SABER SI ES LISTA DE MATERIALES O DE UTILES
                        $sqlOrdenMontajeLinea    = "SELECT OML.TIPO_LINEA
                                                    FROM ORDEN_MONTAJE_LINEA OML
                                                    INNER JOIN PEDIDO_SALIDA_LINEA PSL ON PSL.ID_ORDEN_MONTAJE_LINEA = OML.ID_ORDEN_MONTAJE_LINEA
                                                    WHERE PSL.ID_PEDIDO_SALIDA_LINEA = $rowLinea->ID_PEDIDO_SALIDA_LINEA";
                        $resultOrdenMontajeLinea = $bd->ExecSQL($sqlOrdenMontajeLinea);
                        $rowOrdenMontajeLinea    = $bd->SigReg($resultOrdenMontajeLinea);

                        //SI ES DE UTILES, BUSCAMOS LA UBICACION UTILES DEL ALMACEN DE INSTALACION (DESTINO)
                        if ($rowOrdenMontajeLinea->TIPO_LINEA == 'Utiles'):
                            $GLOBALS["NotificaErrorPorEmail"] = "No";
                            $rowUbiTipoConstruccionDestino    = $bd->VerRegRest("UBICACION", "TIPO_UBICACION = 'Utiles' AND ID_ALMACEN = $rowLinea->ID_ALMACEN_DESTINO", "No");
                        else: //BUSCAMOS LA UBCACION DE TIPO MAQUINA
                            $GLOBALS["NotificaErrorPorEmail"] = "No";
                            $rowUbiTipoConstruccionDestino    = $bd->VerReg("UBICACION", "ID_UBICACION", $rowOrdenMontaje->ID_UBICACION_MAQUINA, "No");
                        endif;


                    else://BUSCO LA UBICACION CONTRUCCION DEL ALMACEN DE DESTINO
                        $GLOBALS["NotificaErrorPorEmail"] = "No";
                        $rowUbiTipoConstruccionDestino    = $bd->VerRegRest("UBICACION", "TIPO_UBICACION = 'Construccion' AND ID_ALMACEN = $rowLinea->ID_ALMACEN_DESTINO", "No");
                    endif;

                    //COMPRUEBO QUE LA UBICACION DE TIPO CONSTRUCCION EN EL ALMACEN DE DESTINO EXISTA
                    $html->PagErrorCondicionado($rowUbiTipoConstruccionDestino, "==", false, "UbicacionTipoConstruccionNoexiste");

                    //BUSCO EL REGISTRO DE LA CANTIDAD EN LA UBICACION DE CONTRUCCION EN EL ALMACEN DE DESTINO
                    $rowMatUbiTipoConstruccionDestino = $bd->VerRegRest("MATERIAL_UBICACION", "ID_MATERIAL = $rowLinea->ID_MATERIAL AND ID_UBICACION = $rowUbiTipoConstruccionDestino->ID_UBICACION AND ID_MATERIAL_FISICO " . ($rowLinea->ID_MATERIAL_FISICO == NULL ? 'IS NULL' : "= $rowLinea->ID_MATERIAL_FISICO") . " AND ID_TIPO_BLOQUEO " . ($rowLinea->ID_TIPO_BLOQUEO == NULL ? 'IS NULL' : "= $rowLinea->ID_TIPO_BLOQUEO") . " AND ID_ORDEN_TRABAJO_MOVIMIENTO " . ($rowLinea->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? 'IS NULL' : "= $rowLinea->ID_ORDEN_TRABAJO_MOVIMIENTO") . " AND ID_INCIDENCIA_CALIDAD " . ($rowLinea->ID_INCIDENCIA_CALIDAD == NULL ? 'IS NULL' : "= $rowLinea->ID_INCIDENCIA_CALIDAD"), "No");

                    //COMPRUEBO QUE EXISTE EL REGISTRO DE LA CANTIDAD EN LA UBICACION DE CONTRUCCION EN EL ALMACEN DE DESTINO
                    $html->PagErrorCondicionado($rowMatUbiTipoConstruccionDestino, "==", false, "CantidadUbicacionTipoConstruccionNoExiste");

                    //COMPRUEBO QUE LA CANTIDAD A CANCELAR EXISTE EN LA UBICACION DE CONTRUCCION EN EL ALMACEN DE DESTINO
                    $html->PagErrorCondicionado($rowMatUbiTipoConstruccionDestino->STOCK_TOTAL, "<", $rowLinea->CANTIDAD, "CantidadInsufienteEnUbicacionTipoConstruccion");

                    //ACTUALIZO MATERIAL UBICACION, DESCONTANTO EL MATERIAL CORRESPONDIENTE
                    $sqlUpdate = "UPDATE MATERIAL_UBICACION SET
                                    STOCK_TOTAL = STOCK_TOTAL - $rowLinea->CANTIDAD
                                    , STOCK_OK = STOCK_OK - " . ($rowLinea->ID_TIPO_BLOQUEO == NULL ? $rowLinea->CANTIDAD : 0) . "
                                    , STOCK_BLOQUEADO = STOCK_BLOQUEADO - " . ($rowLinea->ID_TIPO_BLOQUEO == NULL ? 0 : $rowLinea->CANTIDAD) . "
                                    WHERE ID_MATERIAL = $rowLinea->ID_MATERIAL AND ID_UBICACION = $rowUbiTipoConstruccionDestino->ID_UBICACION AND ID_MATERIAL_FISICO " . ($rowLinea->ID_MATERIAL_FISICO == NULL ? "IS NULL" : "= $rowLinea->ID_MATERIAL_FISICO") . " AND ID_TIPO_BLOQUEO " . ($rowLinea->ID_TIPO_BLOQUEO == NULL ? "IS NULL" : "= $rowLinea->ID_TIPO_BLOQUEO") . " AND ID_ORDEN_TRABAJO_MOVIMIENTO " . ($rowLinea->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? "IS NULL" : "= $rowLinea->ID_ORDEN_TRABAJO_MOVIMIENTO") . " AND ID_INCIDENCIA_CALIDAD " . ($rowLinea->ID_INCIDENCIA_CALIDAD == NULL ? "IS NULL" : "= $rowLinea->ID_INCIDENCIA_CALIDAD");
                    $bd->ExecSQL($sqlUpdate);

                    //ACCIONES A REALIZAR SI LA LINEA DEL MOVIMIENTO DE ENTRADA SE QUEDA CON CANTIDAD CERO
                    $sqlLineasMovimientoEntradaImplicadas    = "SELECT *
                                                                FROM MOVIMIENTO_ENTRADA_LINEA MEL
                                                                WHERE MEL.ID_ALBARAN_LINEA = $rowLinea->ID_ALBARAN_LINEA AND MEL.CANTIDAD > 0 AND MEL.LINEA_ANULADA = 0 AND MEL.BAJA = 0";
                    $resultLineasMovimientoEntradaImplicadas = $bd->ExecSQL($sqlLineasMovimientoEntradaImplicadas);

                    while ($rowLineaMovimientoEntradaImplicada = $bd->SigReg($resultLineasMovimientoEntradaImplicadas)):

                        //ACTUALIZO LA LINEA DEL MOVIMIENTO DE ENTRADA
                        $sqlUpdate = "UPDATE MOVIMIENTO_ENTRADA_LINEA SET
                                        ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                        , LINEA_ANULADA = 1
                                        , FECHA_ANULACION = '" . date("Y-m-d H:i:s") . "'
                                        , ANULACION_STOCK = 0
                                        WHERE ID_MOVIMIENTO_ENTRADA_LINEA = $rowLineaMovimientoEntradaImplicada->ID_MOVIMIENTO_ENTRADA_LINEA";
                        $bd->ExecSQL($sqlUpdate);

                        //BORRAMOS LAS ETIQUETAS MANUALES SI TIENE
                        if ($rowLineaMovimientoEntradaImplicada->TIPO_ETIQUETADO == 'Manual'):
                            $sqlDelete = "DELETE FROM MOVIMIENTO_ENTRADA_LINEA_ETIQUETAS_MANUALES WHERE ID_MOVIMIENTO_ENTRADA_LINEA = $rowLineaMovimientoEntradaImplicada->ID_MOVIMIENTO_ENTRADA_LINEA";
                            $bd->ExecSQL($sqlDelete);
                        endif;

                        // SI EL MOVIMIENTO NO TIENE LINEAS (TODAS LAS LINEAS HAN SIDO ANULADAS) LO PASO A UBICADO
                        /*$num = $bd->NumRegsTabla("MOVIMIENTO_ENTRADA_LINEA", "ID_MOVIMIENTO_ENTRADA = $rowLineaMovimientoEntradaImplicada->ID_MOVIMIENTO_ENTRADA AND CANTIDAD <> 0 AND BAJA = 0 AND LINEA_ANULADA = 0", "No");
                        if ($num == 0):
                            if ($rowMov->ADJUNTO == NULL):
                                $estado = 'Ubicado';
                            else:
                                $estado = 'Escaneado y Finalizado';
                            endif;

                            $sqlUpdate = "UPDATE MOVIMIENTO_ENTRADA SET
                                    ESTADO = '" . $estado . "'
                                    WHERE ID_MOVIMIENTO_ENTRADA = $rowLineaMovimientoEntradaImplicada->ID_MOVIMIENTO_ENTRADA";
                            $bd->ExecSQL($sqlUpdate);
                        endif;*/
                        $sqlUpdate = "UPDATE MOVIMIENTO_ENTRADA SET
                                ESTADO = '" . $movimiento->actualizarEstadoMovimientoEntrada($rowLineaMovimientoEntradaImplicada->ID_MOVIMIENTO_ENTRADA) . "'
                                WHERE ID_MOVIMIENTO_ENTRADA = $rowLineaMovimientoEntradaImplicada->ID_MOVIMIENTO_ENTRADA";
                        $bd->ExecSQL($sqlUpdate);

                        //BUSCO EL MOVIMIENTO
                        $rowMovimientoEntradaImplicado = $bd->VerReg("MOVIMIENTO_ENTRADA", "ID_MOVIMIENTO_ENTRADA", $rowLineaMovimientoEntradaImplicada->ID_MOVIMIENTO_ENTRADA);

                        //BUSCO LA RECEPCION DEL MOVIMIENTO
                        $rowRecepcion = $bd->VerReg("MOVIMIENTO_RECEPCION", "ID_MOVIMIENTO_RECEPCION", $rowMovimientoEntradaImplicado->ID_MOVIMIENTO_RECEPCION);

                        //OBTENGO EL ESTADO FINAL DE LA RECEPCION TRAS LOS CAMBIOS
                        $estadoFinalRecepcion = $movimiento->getEstadoRecepcion($rowRecepcion->ID_MOVIMIENTO_RECEPCION);

                        //ACTUALIZO EL ESTADO DE LA RECEPCION
                        $sqlUpdate = "UPDATE MOVIMIENTO_RECEPCION SET ESTADO = '" . $estadoFinalRecepcion . "' WHERE ID_MOVIMIENTO_RECEPCION = $rowRecepcion->ID_MOVIMIENTO_RECEPCION";
                        $bd->ExecSQL($sqlUpdate);

                        //BUSCO EL MOVIMIENTO Y LA RECEPCION ACTUALIZADOS
                        $rowMov                = $bd->VerReg("MOVIMIENTO_ENTRADA", "ID_MOVIMIENTO_ENTRADA", $rowLineaMovimientoEntradaImplicada->ID_MOVIMIENTO_ENTRADA, "No");
                        $estadoFinalMovimiento = $rowMov->ESTADO;
                        $rowRecepcion          = $bd->VerReg("MOVIMIENTO_RECEPCION", "ID_MOVIMIENTO_RECEPCION", $rowMov->ID_MOVIMIENTO_RECEPCION, "No");
                        $estadoFinalRecepcion  = $rowRecepcion->ESTADO;

                        //ACTUALIZO LA FECHA DE PROCESADO
                        if (($estadoInicialMovimiento == 'En Proceso') && ($estadoFinalMovimiento != 'En Proceso')):
                            $sqlUpdate = "UPDATE MOVIMIENTO_ENTRADA SET FECHA_PROCESADO = '" . date("Y-m-d H:i:s") . "' WHERE ID_MOVIMIENTO_ENTRADA = $idMovimiento";
                            $bd->ExecSQL($sqlUpdate);
                        elseif (($estadoInicialMovimiento != 'En Proceso') && ($estadoFinalMovimiento == 'En Proceso')):
                            $sqlUpdate = "UPDATE MOVIMIENTO_ENTRADA SET FECHA_PROCESADO = '0000-00-00 00:00:00' WHERE ID_MOVIMIENTO_ENTRADA = $idMovimiento";
                            $bd->ExecSQL($sqlUpdate);
                        endif;
                        if (($estadoInicialRecepcion == 'En Proceso') && ($estadoFinalRecepcion != 'En Proceso')):
                            $sqlUpdate = "UPDATE MOVIMIENTO_RECEPCION SET FECHA_PROCESADO = '" . date("Y-m-d H:i:s") . "' WHERE ID_MOVIMIENTO_RECEPCION = $rowRecepcion->ID_MOVIMIENTO_RECEPCION";
                            $bd->ExecSQL($sqlUpdate);
                        elseif (($estadoInicialRecepcion != 'En Proceso') && ($estadoFinalRecepcion == 'En Proceso')):
                            $sqlUpdate = "UPDATE MOVIMIENTO_RECEPCION SET FECHA_PROCESADO = '0000-00-00 00:00:00' WHERE ID_MOVIMIENTO_RECEPCION = $rowRecepcion->ID_MOVIMIENTO_RECEPCION";
                            $bd->ExecSQL($sqlUpdate);
                        endif;
                        //FIN ACTUALIZO LA FECHA DE PROCESADO
                    endwhile; //FIN BUCLE LINEAS DE MOVIMIENTO DE ENTRADA DE LA MISMA LINEA DE ALBARAN

                    //SI TIENE ORDEN DE MONTAJE
                    if ($rowPed->ID_ORDEN_MONTAJE != NULL):
                        //BUSCAMOS LA ORDEN DE MONTAJE LINEA AFECTADA
                        $GLOBALS["NotificaErrorPorEmail"] = "No";
                        $rowOrdenMontajeMovimiento        = $bd->VerRegRest("ORDEN_MONTAJE_MOVIMIENTO", "TIPO_OPERACION = 'Entrega' AND ID_ORDEN_MONTAJE = $rowPed->ID_ORDEN_MONTAJE AND ID_MATERIAL = $rowLinea->ID_MATERIAL AND ID_MATERIAL_FISICO " . ($rowLinea->ID_MATERIAL_FISICO == NULL ? 'IS NULL' : "=$rowLinea->ID_MATERIAL_FISICO") . " AND ID_UBICACION = $rowUbiTipoConstruccionDestino->ID_UBICACION AND BAJA = 0", "No");

                        //SI EXISTE, ACTUALIZAMOS LA CANTIDAD
                        if ($rowOrdenMontajeMovimiento):
                            //COMPRUEBO QUE NO SE HA DEVUELTO YA DEMASIADA CANTIDAD Y NO SE PUEDE DESHACER
                            $html->PagErrorCondicionado($rowOrdenMontajeMovimiento->CANTIDAD - $rowOrdenMontajeMovimiento->CANTIDAD_ANULADA, "<", $rowLinea->CANTIDAD, "CantidadInsufienteEnUbicacionTipoConstruccion");

                            //ACTUALIZAMOS, DANDO DE BAJA SI CORRESPONDE
                            $sqlUpdate = "UPDATE ORDEN_MONTAJE_MOVIMIENTO SET
                                           CANTIDAD = CANTIDAD - $rowLinea->CANTIDAD
                                          ,BAJA     = " . ($rowOrdenMontajeMovimiento->CANTIDAD <= $rowLinea->CANTIDAD ? 1 : 0) . "
                                          WHERE ID_ORDEN_MONTAJE_MOVIMIENTO = $rowOrdenMontajeMovimiento->ID_ORDEN_MONTAJE_MOVIMIENTO";
                            $bd->ExecSQL($sqlUpdate);
                        endif;
                    endif;

                    //ACTUALIZO LA LINEA DEL MOVIMIENTO DE SALIDA
                    $sqlUpdate = "UPDATE MOVIMIENTO_SALIDA_LINEA SET
                                    ESTADO = 'En Tránsito'
                                    , CANTIDAD_PENDIENTE_DE_RECEPCIONAR_EN_DESTINO = CANTIDAD
                                    WHERE ID_MOVIMIENTO_SALIDA_LINEA = $rowLinea->ID_MOVIMIENTO_SALIDA_LINEA";
                    $bd->ExecSQL($sqlUpdate);

                    //RECUPERO LA LINEA CON LOS VALORES ACTUALIZADOS
                    $rowLinea = $bd->VerReg("MOVIMIENTO_SALIDA_LINEA", "ID_MOVIMIENTO_SALIDA_LINEA", $rowLinea->ID_MOVIMIENTO_SALIDA_LINEA);


                    //ACTUALIZO LA LINEA DE ALBARAN
                    $sqlUpdate = "UPDATE ALBARAN_LINEA SET
                                    CANTIDAD_PENDIENTE_DE_RECEPCIONAR_EN_DESTINO = CANTIDAD_PENDIENTE_DE_RECEPCIONAR_EN_DESTINO + $rowLinea->CANTIDAD
                                    WHERE ID_ALBARAN_LINEA = $rowLinea->ID_ALBARAN_LINEA";
                    $bd->ExecSQL($sqlUpdate);
                endif;
            endwhile;
            //FIN RECORRO LAS LINEAS PARA REALIZAR LAS DIFERENTES OPERACIONES
        endif;
        //FIN SI ES DE CONTRUCCION, CANCELO LA RECEPCION AUTOMATICA

        //DEJO LOS VALORES UNICOS DE LOS ARRAY A ACTUALIZAR
        $arrPedidosLineaActualizar = array_unique( (array)$arrPedidosLineaActualizar);

        /************************************ HABILITO TRIGGERS IMPLICADOS ************************************/
        $sqlHabilitarTriggers = "SET @Trigger_MOVIMIENTO_SALIDA_LINEA_UPDATE_Deshabilitado = NULL";
        $bd->ExecSQL($sqlHabilitarTriggers);
        /********************************** FIN HABILITO TRIGGERS IMPLICADOS **********************************/

        //ACTUALIZO UNA LINEA DE MOVIMIENTO DE CADA LINEA DE PEDIDO
        foreach ($arrPedidosLineaActualizar as $idPedidoLineaSalida):
            //BUSCO UNA LINEA DE MOVIMIENTO DE SALIDA LINEA DE ESTA LINEA DE PEDIDO
            $rowMovimientoSalidaLinea = $bd->VerRegRest("MOVIMIENTO_SALIDA_LINEA", "ID_PEDIDO_SALIDA_LINEA = $idPedidoLineaSalida AND LINEA_ANULADA = 0 AND BAJA = 0", "No");

            //ACTUALIZO EL ESTADO DE LA LINEA DEL PEDIDO DE SALIDA
            $movimiento->actualizarEstadoLineaPedidoSalida($rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA_LINEA);
            //ACTUALIZO EL ESTADO DE LA NECESIDAD DE LA LINEA DEL MOVIMIENTO DE SALIDA
            $movimiento->actualizarEstadoLineaNecesidad($rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA);
        endforeach;


        //ARRAY DE OBJETOS A ACTUALIZAR DESPUES DEL WHILE
        $arrMovimientosActualizar  = array();
        $arrPedidosLineaActualizar = array();

        /************************************ DESHABILITO TRIGGERS IMPLICADOS ************************************/
        $sqlDeshabilitarTriggers = "SET @Trigger_MOVIMIENTO_SALIDA_LINEA_UPDATE_Deshabilitado = 1";
        $bd->ExecSQL($sqlDeshabilitarTriggers);
        /********************************** FIN DESHABILITO TRIGGERS IMPLICADOS **********************************/

//        //ARRAY PARA GUARDAR LOS MOVIMIENTOS/BULTOS A ACTUALIZAR
//        $arrPosiblesMovimientosActualizar = array();
//        $arrPosiblesBultosActualizar      = array();

        //RECORRO LAS LINEAS PARA REALIZAR LAS DIFERENTES OPERACIONES PARA ANULAR LA EXPEDICION DE LA ORDEN DE TRANSPORTE
        $sqlLineas    = "SELECT *
                        FROM MOVIMIENTO_SALIDA_LINEA MSL
                        WHERE MSL.ID_EXPEDICION = $rowOrdenTransporte->ID_EXPEDICION AND ESTADO IN ('Expedido', 'En Tránsito') AND ENVIADO_SAP = 1 AND LINEA_ANULADA = 0 AND BAJA = 0 FOR UPDATE";
        $resultLineas = $bd->ExecSQL($sqlLineas);
        while ($rowLinea = $bd->SigReg($resultLineas)):
            //BUSCO EL MOVIMIENTO DE SALIDA DE LA LINEA
            $rowMovimientoSalida = $bd->VerReg("MOVIMIENTO_SALIDA", "ID_MOVIMIENTO_SALIDA", $rowLinea->ID_MOVIMIENTO_SALIDA, "No");

            //ME GUARDO LAS LINEAS DE PEDIDO DE SALIDA IMPLICADAS
            $arrPedidosLineaActualizar[] = $rowLinea->ID_PEDIDO_SALIDA_LINEA;

            //ACCIONES ESPECIFICAS EN FUNCION DEL TIPO DE MOVIMIENTO EXPEDIDO
            if (
                ($rowMovimientoSalida->TIPO_MOVIMIENTO == 'TraspasoEntreAlmacenesNoEstropeado') ||
                ($rowMovimientoSalida->TIPO_MOVIMIENTO == 'TrasladoOMConstruccion') ||
                ($rowMovimientoSalida->TIPO_MOVIMIENTO == 'IntraCentroFisico') ||
                ($rowMovimientoSalida->TIPO_MOVIMIENTO == 'InternoGama') ||
                ($rowMovimientoSalida->TIPO_MOVIMIENTO == 'LogisticaInversaConPreparacion') ||
                ($rowMovimientoSalida->TIPO_MOVIMIENTO == 'TraspasoEntreAlmacenesEstropeado')
            ):

                //ACTUALIZO LA CANTIDAD PENDIENTE DE RECEPCIONAR EN DESTINO (LINEA MOVIMIENTO SALIDA)
                $sqlUpdate = "UPDATE MOVIMIENTO_SALIDA_LINEA SET
                                CANTIDAD_PENDIENTE_DE_RECEPCIONAR_EN_DESTINO = 0
                                WHERE ID_MOVIMIENTO_SALIDA_LINEA = $rowLinea->ID_MOVIMIENTO_SALIDA_LINEA";
                $bd->ExecSQL($sqlUpdate);

                //ACTUALIZO LA CANTIDAD PENDIENTE DE RECEPCIONAR EN DESTINO (LINEA ALBARAN)
                $sqlUpdate = "UPDATE ALBARAN_LINEA SET
                                CANTIDAD_PENDIENTE_DE_RECEPCIONAR_EN_DESTINO = CANTIDAD_PENDIENTE_DE_RECEPCIONAR_EN_DESTINO - $rowLinea->CANTIDAD
                                WHERE ID_ALBARAN_LINEA = $rowLinea->ID_ALBARAN_LINEA";
                $bd->ExecSQL($sqlUpdate);

            elseif (
                ($rowMovimientoSalida->TIPO_MOVIMIENTO == 'ComponentesAProveedor') ||
                ($rowMovimientoSalida->TIPO_MOVIMIENTO == 'MaterialEstropeadoAProveedor')
            ): //DOY DE ALTA EL MATERIAL EN LA UBICACION DE PROVEEDOR

                //BUSCO EL MATERIAL UBICACION DESTINO, UBICACION DE PROVEEDOR
                $rowMatUbiProveedor = $bd->VerRegRest("MATERIAL_UBICACION", "ID_MATERIAL = $rowLinea->ID_MATERIAL AND ID_UBICACION = $rowLinea->ID_UBICACION_DESTINO AND ID_MATERIAL_FISICO " . ($rowLinea->ID_MATERIAL_FISICO == NULL ? 'IS NULL' : "= $rowLinea->ID_MATERIAL_FISICO") . " AND ID_TIPO_BLOQUEO " . ($rowLinea->ID_TIPO_BLOQUEO == NULL ? 'IS NULL' : "= $rowLinea->ID_TIPO_BLOQUEO") . " AND ID_ORDEN_TRABAJO_MOVIMIENTO " . ($rowLinea->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? 'IS NULL' : "= $rowLinea->ID_ORDEN_TRABAJO_MOVIMIENTO") . " AND ID_INCIDENCIA_CALIDAD " . ($rowLinea->ID_INCIDENCIA_CALIDAD == NULL ? 'IS NULL' : "= $rowLinea->ID_INCIDENCIA_CALIDAD"), "No");

                //COMPRUEBO QUE EXISTE EL MATERIAL UBICACION DEL PROVEEDOR
                $html->PagErrorCondicionado($rowMatUbiProveedor, "==", false, "MaterialUbicacionProveedorNoExiste");

                //COMPRUEBO QUE HAYA STOCK SUFICIENTE
                $html->PagErrorCondicionado($rowMatUbiProveedor->STOCK_TOTAL, "<", $rowLinea->CANTIDAD, "StockInsuficienteEnProveedor");

                //DECREMENTO MATERIAL UBICACION (EN PROVEEDOR)
                $sql = "UPDATE MATERIAL_UBICACION SET
                            STOCK_TOTAL = STOCK_TOTAL - $rowLinea->CANTIDAD
                            , STOCK_OK = STOCK_OK - " . ($rowLinea->ID_TIPO_BLOQUEO == NULL ? $rowLinea->CANTIDAD : 0) . "
                            , STOCK_BLOQUEADO = STOCK_BLOQUEADO - " . ($rowLinea->ID_TIPO_BLOQUEO == NULL ? 0 : $rowLinea->CANTIDAD) . "
                            WHERE ID_MATERIAL_UBICACION = $rowMatUbiProveedor->ID_MATERIAL_UBICACION";
                $bd->ExecSQL($sql);

                //GENERO UN ASIENTO EN PROVEEDOR PARA DECREMENTAR EL STOCK DE COMPONENTES
                $sqlInsert = "INSERT INTO ASIENTO SET
                                    ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                    , ID_MATERIAL = $rowLinea->ID_MATERIAL
                                    , TIPO_LOTE = '" . $rowLinea->TIPO_LOTE . "'
                                    , ID_MATERIAL_FISICO = " . ($rowLinea->ID_MATERIAL_FISICO == NULL ? 'NULL' : "$rowLinea->ID_MATERIAL_FISICO") . "
                                    , ID_UBICACION = $rowLinea->ID_UBICACION_DESTINO
                                    , FECHA = '" . date("Y-m-d H:i:s") . "'
                                    , CANTIDAD = " . ($rowLinea->CANTIDAD * -1) . "
                                    , STOCK_OK = " . ($rowLinea->CANTIDAD * -1) . "
                                    , STOCK_BLOQUEADO = 0
                                    , ID_TIPO_BLOQUEO = " . ($rowLinea->ID_TIPO_BLOQUEO == NULL ? 'NULL' : "$rowLinea->ID_TIPO_BLOQUEO") . "
                                    , ID_ORDEN_TRABAJO_MOVIMIENTO = " . ($rowLinea->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? 'NULL' : "$rowLinea->ID_ORDEN_TRABAJO_MOVIMIENTO") . "
                                    , ID_MOVIMIENTO_SALIDA_LINEA = $rowLinea->ID_MOVIMIENTO_SALIDA_LINEA
                                    , ID_INCIDENCIA_CALIDAD = " . ($rowLinea->ID_INCIDENCIA_CALIDAD == NULL ? 'NULL' : "$rowLinea->ID_INCIDENCIA_CALIDAD") . "
                                    , OBSERVACIONES = ''
                                    , TIPO_ASIENTO = '" . ($rowMovimientoSalida->TIPO_MOVIMIENTO == 'ComponentesAProveedor' ? 'Baja Componentes en Proveedor' : 'Baja Material Estropeado en Proveedor') . "'";
                $bd->ExecSQL($sqlInsert);

            endif;
            //FIN ACCIONES ESPECIFICAS EN FUNCION DEL TIPO DE MOVIMIENTO EXPEDIDO

            //SI ES UN MATERIAL FISICO SERIABLE COMPRUEBO QUE NO EXISTA EN EL SISTEMA NI ESTE EN TRANSITO
            if ($rowLinea->ID_MATERIAL_FISICO != NULL):
                $NotificaErrorPorEmail = "No";
                $rowMatFis             = $bd->VerReg("MATERIAL_FISICO", "ID_MATERIAL_FISICO", $rowLinea->ID_MATERIAL_FISICO, "No");
                unset($NotificaErrorPorEmail);
                $html->PagErrorCondicionado($rowMatFis, "==", false, "MaterialFisicoNoExiste");
                if ($rowMatFis->TIPO_LOTE == 'serie'):    //SI ES UN MATERIAL SERIABLE
                    if ($rowMovimientoSalida->TIPO_MOVIMIENTO == 'ComponentesAProveedor'): //NO TENDREMOS EN CUENTA LOS ALMACENES DE PROVEEDOR
                        $numMaterialSeriable = $bd->NumRegsTabla("MATERIAL_UBICACION MU INNER JOIN UBICACION U ON U.ID_UBICACION = MU.ID_UBICACION INNER JOIN ALMACEN A ON A.ID_ALMACEN = U.ID_ALMACEN", "MU.ID_MATERIAL_FISICO = $rowLinea->ID_MATERIAL_FISICO AND MU.ACTIVO = 1 AND A.TIPO_ALMACEN != 'proveedor'");
                    else:
                        $numMaterialSeriable = $bd->NumRegsTabla("MATERIAL_UBICACION", "ID_MATERIAL_FISICO = $rowLinea->ID_MATERIAL_FISICO AND ACTIVO = 1");
                    endif;
                    if ($numMaterialSeriable > 0):    //EXISTE EN EL SISTEMA
                        $hayErrorSerieLoteEnSistema = true;
                        $strErrorEnSistema          .= "$rowMatFis->NUMERO_SERIE_LOTE<br />";
                    else:
                        if ($mat->MaterialFisicoEnTransito($rowLinea->ID_MATERIAL_FISICO, $rowLinea->ID_MOVIMIENTO_SALIDA_LINEA) == true):    //ESTA EN TRANSITO DENTRO DEL SISTEMA
                            $hayErrorSerieLoteEnTransito = true;
                            $strErrorEnTransito          .= "$rowMatFis->NUMERO_SERIE_LOTE<br />";
                        endif;
                    endif;
                endif;    //FIN SI ES UN MATERIAL SERIABLE
            endif;
            //FIN SI ES UN MATERIAL FISICO SERIABLE COMPRUEBO QUE NO EXISTA EN EL SISTEMA NI ESTE EN TRANSITO

            //BUSCO SI SE HICIERON TRANSFERENCIAS DESDE LA UBICACION DE SM (SALIDA) A LA UBICACION DE EB (EMBARQUE)
            $sqlTransf    = "SELECT *
                            FROM MOVIMIENTO_TRANSFERENCIA
                            WHERE ID_MOVIMIENTO_SALIDA = $rowLinea->ID_MOVIMIENTO_SALIDA AND ID_MOVIMIENTO_SALIDA_LINEA = $rowLinea->ID_MOVIMIENTO_SALIDA_LINEA AND TIPO = 'Embarque' AND BAJA = 0";
            $resultTransf = $bd->ExecSQL($sqlTransf);
            if ($bd->NumRegs($resultTransf) == 0):  //SON ORDENES DE TRANSPORTE ANTIGUAS, EL MATERIAL SE EXPIDE DIRECTAMENTE DE SM
                //BUSCO LA UBICACION DE SALIDA DEL ALMACEN DE ORIGEN
                $rowUbiSalida = $bd->VerRegRest("UBICACION", "ID_ALMACEN = $rowLinea->ID_ALMACEN AND TIPO_UBICACION = 'Salida'", "No");

                //COMPRUEBO QUE LA UBICACION EXISTE
                $html->PagErrorCondicionado($rowUbiSalida, "==", false, "UbicacionSalidaNoExiste");

                //BUSCO LA UBICACION DONDE INCREMENTAR EL MATERIAL
                $rowMatUbiDestino = $bd->VerRegRest("MATERIAL_UBICACION", "ID_MATERIAL = $rowLinea->ID_MATERIAL AND ID_UBICACION = $rowUbiSalida->ID_UBICACION AND ID_MATERIAL_FISICO " . ($rowLinea->ID_MATERIAL_FISICO == NULL ? 'IS NULL' : "= $rowLinea->ID_MATERIAL_FISICO") . " AND ID_TIPO_BLOQUEO " . ($rowLinea->ID_TIPO_BLOQUEO == NULL ? 'IS NULL' : "= $rowLinea->ID_TIPO_BLOQUEO") . " AND ID_ORDEN_TRABAJO_MOVIMIENTO " . ($rowLinea->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? 'IS NULL' : "= $rowLinea->ID_ORDEN_TRABAJO_MOVIMIENTO") . " AND ID_INCIDENCIA_CALIDAD " . ($rowLinea->ID_INCIDENCIA_CALIDAD == NULL ? 'IS NULL' : "= $rowLinea->ID_INCIDENCIA_CALIDAD"), "No");
                if ($rowMatUbiDestino == false):
                    //CREO MATERIAL UBICACION SI NO EXISTE
                    $sql = "INSERT INTO MATERIAL_UBICACION SET
                                    ID_MATERIAL = $rowLinea->ID_MATERIAL
                                    , ID_MATERIAL_FISICO = " . ($rowLinea->ID_MATERIAL_FISICO == NULL ? 'NULL' : "$rowLinea->ID_MATERIAL_FISICO") . "
                                    , ID_UBICACION = $rowUbiSalida->ID_UBICACION
                                    , ID_TIPO_BLOQUEO = " . ($rowLinea->ID_TIPO_BLOQUEO == NULL ? 'NULL' : "$rowLinea->ID_TIPO_BLOQUEO") . "
                                    , ID_ORDEN_TRABAJO_MOVIMIENTO = " . ($rowLinea->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? 'NULL' : "$rowLinea->ID_ORDEN_TRABAJO_MOVIMIENTO") . "
                                    , ID_INCIDENCIA_CALIDAD = " . ($rowLinea->ID_INCIDENCIA_CALIDAD == NULL ? 'NULL' : "$rowLinea->ID_INCIDENCIA_CALIDAD");
                    $bd->ExecSQL($sql);
                    $idMatUbi = $bd->IdAsignado();
                else:
                    $idMatUbi = $rowMatUbiDestino->ID_MATERIAL_UBICACION;
                endif;

                //INCREMENTO MATERIAL UBICACION (EN UBICACION DE TIPO EMBARQUE)
                $sql = "UPDATE MATERIAL_UBICACION SET
                                STOCK_TOTAL = STOCK_TOTAL + $rowLinea->CANTIDAD
                                , STOCK_OK = STOCK_OK + " . ($rowLinea->ID_TIPO_BLOQUEO == NULL ? $rowLinea->CANTIDAD : 0) . "
                                , STOCK_BLOQUEADO = STOCK_BLOQUEADO + " . ($rowLinea->ID_TIPO_BLOQUEO == NULL ? 0 : $rowLinea->CANTIDAD) . "
                                WHERE ID_MATERIAL_UBICACION = $idMatUbi";
                $bd->ExecSQL($sql);

                // SE COMPRUEBA SI EL MATERIAL DEBE ESTAR RETENIDO Y SI ESO SE MARCA
                $ordenTrabajoMovimientoRetenido = $mat->setMaterialSusceptibleRetencionSO99($idMatUbi, 'MATERIAL_UBICACION', true);
            else:   //SON ORDENES DE TRANSPORTE NUEVAS, EL MATERIAL SE EXPIDE DIRECTAMENTE DE EB PREVIO PASO POR SM ANTES DE TRANSMITIR A SAP
                //RECORRO LAS TRANSFERENCIAS DE ESTA LINEA DE MOVIMIENTO DE SALIDA
                while ($rowTransf = $bd->SigReg($resultTransf)):
                    //BUSCO LA UBICACION DONDE INCREMENTAR EL MATERIAL
                    $rowMatUbiDestino = $bd->VerRegRest("MATERIAL_UBICACION", "ID_MATERIAL = $rowTransf->ID_MATERIAL AND ID_UBICACION = $rowTransf->ID_UBICACION_DESTINO AND ID_MATERIAL_FISICO " . ($rowTransf->ID_MATERIAL_FISICO == NULL ? 'IS NULL' : "= $rowTransf->ID_MATERIAL_FISICO") . " AND ID_TIPO_BLOQUEO " . ($rowTransf->ID_TIPO_BLOQUEO == NULL ? 'IS NULL' : "= $rowTransf->ID_TIPO_BLOQUEO") . " AND ID_ORDEN_TRABAJO_MOVIMIENTO " . ($rowTransf->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? 'IS NULL' : "= $rowTransf->ID_ORDEN_TRABAJO_MOVIMIENTO") . " AND ID_INCIDENCIA_CALIDAD " . ($rowTransf->ID_INCIDENCIA_CALIDAD == NULL ? 'IS NULL' : "= $rowTransf->ID_INCIDENCIA_CALIDAD"), "No");
                    if ($rowMatUbiDestino == false):
                        //CREO MATERIAL UBICACION SI NO EXISTE
                        $sql = "INSERT INTO MATERIAL_UBICACION SET
                                    ID_MATERIAL = $rowTransf->ID_MATERIAL
                                    , ID_MATERIAL_FISICO = " . ($rowTransf->ID_MATERIAL_FISICO == NULL ? 'NULL' : "$rowTransf->ID_MATERIAL_FISICO") . "
                                    , ID_UBICACION = $rowTransf->ID_UBICACION_DESTINO
                                    , ID_TIPO_BLOQUEO = " . ($rowTransf->ID_TIPO_BLOQUEO == NULL ? 'NULL' : "$rowTransf->ID_TIPO_BLOQUEO") . "
                                    , ID_ORDEN_TRABAJO_MOVIMIENTO = " . ($rowTransf->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? 'NULL' : "$rowTransf->ID_ORDEN_TRABAJO_MOVIMIENTO") . "
                                    , ID_INCIDENCIA_CALIDAD = " . ($rowTransf->ID_INCIDENCIA_CALIDAD == NULL ? 'NULL' : "$rowTransf->ID_INCIDENCIA_CALIDAD");
                        $bd->ExecSQL($sql);
                        $idMatUbi = $bd->IdAsignado();
                    else:
                        $idMatUbi = $rowMatUbiDestino->ID_MATERIAL_UBICACION;
                    endif;

                    //INCREMENTO MATERIAL UBICACION (EN UBICACION DE TIPO EMBARQUE)
                    $sql = "UPDATE MATERIAL_UBICACION SET
                                STOCK_TOTAL = STOCK_TOTAL + $rowTransf->CANTIDAD
                                , STOCK_OK = STOCK_OK + " . ($rowTransf->ID_TIPO_BLOQUEO == NULL ? $rowTransf->CANTIDAD : 0) . "
                                , STOCK_BLOQUEADO = STOCK_BLOQUEADO + " . ($rowTransf->ID_TIPO_BLOQUEO == NULL ? 0 : $rowTransf->CANTIDAD) . "
                                WHERE ID_MATERIAL_UBICACION = $idMatUbi";
                    $bd->ExecSQL($sql);

                    // SE COMPRUEBA SI EL MATERIAL DEBE ESTAR RETENIDO Y SI ESO SE MARCA
                    $ordenTrabajoMovimientoRetenido = $mat->setMaterialSusceptibleRetencionSO99($idMatUbi, 'MATERIAL_UBICACION', true);
                endwhile;
                //FIN RECORRO LAS TRANSFERENCIAS DE ESTA LINEA DE MOVIMIENTO DE SALIDA
            endif;
            //FIN BUSCO SI SE HICIERON TRANSFERENCIAS DESDE LA UBICACION DE SM (SALIDA) A LA UBICACION DE EB (EMBARQUE)

            //ACTUALIZO EL ESTADO DEL PEDIDO
            $sqlUpdate = "UPDATE PEDIDO_SALIDA SET ESTADO = 'En Entrega' WHERE ID_PEDIDO_SALIDA = $rowLinea->ID_PEDIDO_SALIDA";
            $bd->ExecSQL($sqlUpdate);

            //ACTUALIZO LA LINEA DEL MOVIMIENTO DE SALIDA
            $sqlUpdate = "UPDATE MOVIMIENTO_SALIDA_LINEA SET
                            ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                            , ESTADO = 'Transmitido a SAP'
                            , FECHA_EXPEDICION = '0000-00-00 00:00:00'
                            WHERE ID_MOVIMIENTO_SALIDA_LINEA = $rowLinea->ID_MOVIMIENTO_SALIDA_LINEA";
            $bd->ExecSQL($sqlUpdate);

            //ACTUALIZO LA CABECERA DEL MOVIMIENTO
            //$arrPosiblesMovimientosActualizar[] = $rowLinea->ID_MOVIMIENTO_SALIDA;
            //$movimiento->actualizarEstadoMovimientoSalida($rowLinea->ID_MOVIMIENTO_SALIDA);
            $arrMovimientosActualizar[] = $rowLinea->ID_MOVIMIENTO_SALIDA;

            //SI LA ORDEN DE TRANSPORTE ES CON BULTOS Y LA LINEA ESTA ASIGNADA A UN BULTO, ACTUALIZO EL ESTADO DEL BULTO
            if (($rowOrdenTransporte->CON_BULTOS == 1) && ($rowLinea->ID_BULTO != NULL)):
                //ACTUALIZO EL ESTADO DEL BULTO A EMBARCADO
                //$arrPosiblesBultosActualizar[] = $rowLinea->ID_BULTO;
                //$orden_preparacion->ActualizarEstadoBulto($rowLinea->ID_BULTO, 'Embarcado');
                $orden_preparacion->ActualizarEstadoBulto($rowLinea->ID_BULTO, 'Embarcado');
            endif;
        endwhile;
        //FIN RECORRO LAS LINEAS PARA REALIZAR LAS DIFERENTES OPERACIONES PARA ANULAR LA EXPEDICION DE LA ORDEN DE TRANSPORTE

//        //ME QUEDO CON LOS REGISTROS UNICOS
//        $arrPosiblesMovimientosActualizar = array_unique($arrPosiblesMovimientosActualizar);
//        $arrPosiblesBultosActualizar      = array_unique($arrPosiblesBultosActualizar);
//
//        //RECORRO LOS MOVIMIENTOS PARA ACTUALIZAR LA CABECERA
//        foreach ($arrPosiblesMovimientosActualizar as $idMovimientoActualizar):
//            $movimiento->actualizarEstadoMovimientoSalida($idMovimientoActualizar);
//        endforeach;
//
//        //RECORRO LOS BULTOS PARA ACTUALIZAR EL ESTADO
//        foreach ($arrPosiblesBultosActualizar as $idBultoActualizar):
//            $orden_preparacion->actualizarEstadoBulto($idBultoActualizar, 'Embarcado');
//        endforeach;

        //DEJO LOS VALORES UNICOS DE LOS ARRAY A ACTUALIZAR
        $arrMovimientosActualizar  = array_unique( (array)$arrMovimientosActualizar);
        $arrPedidosLineaActualizar = array_unique( (array)$arrPedidosLineaActualizar);

        //ACTUALIZO LOS MOVIMIENTOS
        foreach ($arrMovimientosActualizar as $idMovimientoSalida):
            $movimiento->actualizarEstadoMovimientoSalida($idMovimientoSalida);
        endforeach;

        /************************************ HABILITO TRIGGERS IMPLICADOS ************************************/
        $sqlHabilitarTriggers = "SET @Trigger_MOVIMIENTO_SALIDA_LINEA_UPDATE_Deshabilitado = NULL";
        $bd->ExecSQL($sqlHabilitarTriggers);
        /********************************** FIN HABILITO TRIGGERS IMPLICADOS **********************************/

        //ACTUALIZO UNA LINEA DE MOVIMIENTO DE CADA LINEA DE PEDIDO
        foreach ($arrPedidosLineaActualizar as $idPedidoLineaSalida):
            //BUSCO UNA LINEA DE MOVIMIENTO DE SALIDA LINEA DE ESTA LINEA DE PEDIDO
            $rowMovimientoSalidaLinea = $bd->VerRegRest("MOVIMIENTO_SALIDA_LINEA", "ID_PEDIDO_SALIDA_LINEA = $idPedidoLineaSalida AND LINEA_ANULADA = 0 AND BAJA = 0", "No");

            //BUSCO EL PEDIDO LINEA PARA GUARDAR LA OTL EN CASO DE QUE TENGA
            $rowPedidoSalidaLinea = $bd->VerReg("PEDIDO_SALIDA_LINEA", "ID_PEDIDO_SALIDA_LINEA", $idPedidoLineaSalida, "No");

            //SI SE ESTA ANULANDO UNA LINEA DE UNA OTL
            if ($rowPedidoSalidaLinea->ID_ORDEN_TRABAJO_LINEA != NULL):

                //BUSCAMOS LA ULTIMA LLAMADA A LA T06, Y ES EXPEDICION, LO VOLVEREMOS A CALCULAR
                $GLOBALS["NotificaErrorPorEmail"]                   = "No";
                $rowOrdenTrabajoLineaDisponibilidadFechaPlanificada = $bd->VerRegRest("ORDEN_TRABAJO_LINEA_DISPONIBILIDAD_FECHA_PLANIFICADA", "ID_ORDEN_TRABAJO_LINEA =  $rowPedidoSalidaLinea->ID_ORDEN_TRABAJO_LINEA AND BAJA = 0", "No");
                if (($rowOrdenTrabajoLineaDisponibilidadFechaPlanificada == false) ||
                    (($rowOrdenTrabajoLineaDisponibilidadFechaPlanificada != false) && ($rowOrdenTrabajoLineaDisponibilidadFechaPlanificada->ESCENARIO_PLANIFICACION == 'Expedicion'))
                ):


                    if ($orden_trabajo->esTransmitibleInterfaz("Expedicion", "Anulacion Expedicion (anulacion puesta en transito en SCS)", "T06", $rowPedidoSalidaLinea->ID_ORDEN_TRABAJO_LINEA)):
                        //MARCAMOS LA LINEA PARA VOLVER A CALCULAR
                        $sqlUpdate = "UPDATE ORDEN_TRABAJO_LINEA SET
                                  DISPONIBILIDAD_FECHA_PLANIFICADA = 'Pendiente Tratar'
                                  WHERE ID_ORDEN_TRABAJO_LINEA = " . $rowPedidoSalidaLinea->ID_ORDEN_TRABAJO_LINEA;
                        $bd->ExecSQL($sqlUpdate);
                    endif;

                    if ($orden_trabajo->esTransmitibleInterfaz("Expedicion", "Anulacion Expedicion (anulacion puesta en transito en SCS)", "T01", $rowPedidoSalidaLinea->ID_ORDEN_TRABAJO_LINEA)):
                        //INICIO UNA TRANSACCION PARA LA INTERFAZ DEL SEMAFORO PARA INFORMAR A SAP DEL BLOQUEO Y CANTIDAD RECEPCIONADA
                        $bd->begin_transaction();


                        //COMPRUEBO SI TIENE INCIDENCIA DE SISTEMA CREADA
                        $num = $bd->NumRegsTabla("INCIDENCIA_SISTEMA", "TIPO = '" . INCIDENCIA_TIPO_INTERFAZ_SIN_TRANSMITIR . "' AND SUBTIPO = '" . INCIDENCIA_SUBTIPO_ORDENES_TRABAJO_SEMAFORO . "' AND ID_OBJETO = $rowPedidoSalidaLinea->ID_ORDEN_TRABAJO_LINEA AND TABLA_OBJETO = 'ORDEN_TRABAJO_LINEA' AND ESTADO <> 'Finalizada'");
                        if ($num == 0): //SI NO TIENE INCIDENCIA SISTEMA CREADA REALIZO LA LLAMADA A SAP


                            $resultado = $sap->OrdenesTrabajoSemaforo($rowPedidoSalidaLinea->ID_ORDEN_TRABAJO_LINEA);
                            if ($resultado['RESULTADO'] != 'OK'): //No se ha podido grabar la información en SAP
                                $strError .= $auxiliar->traduce("Se han producido errores al transmitir la interfaz del semaforo de ordenes de trabajo a SAP", $administrador->ID_IDIOMA) . ". " . $datosLinea;
                                $strError .= "<br>";
                                //NO INFORMO DE LOS ERRORES PORQUE ES UNA INTERFAZ ASINCRONA DONDE NO ENVIAN ERRORES SI SE PRODUCEN


                                //DESHAGO LA TRANSACCION
                                $bd->rollback_transaction();


                                //GRABO EL ERROR PRODUCIDO EN BASE DE DATOS
                                $sap->InsertarErrores($resultado);

                                //SI FALLA LA TRANSMISION DEL SEMAFORO Y NO ESTA YA COMO PENDIENTE, LO GRABO PARA SABER QUE ESTA PENDIENTE DE REALIZARSE
                                $num = $bd->NumRegsTabla("INTERFACES_PENDIENTES_TRANSMITIR", "INTERFAZ_PENDIENTE_TRANSMITIR = 'ordenes_trabajo_semaforo' AND ID_OBJETO = $rowPedidoSalidaLinea->ID_ORDEN_TRABAJO_LINEA AND TABLA_OBJETO = 'ORDEN_TRABAJO_LINEA' AND BAJA = 0");
                                if ($num == 0):
                                    $sqlInsert = "INSERT INTO INTERFACES_PENDIENTES_TRANSMITIR SET
                                                  INTERFAZ_PENDIENTE_TRANSMITIR = 'ordenes_trabajo_semaforo'
                                                  , ID_OBJETO = $rowPedidoSalidaLinea->ID_ORDEN_TRABAJO_LINEA
                                                  , TABLA_OBJETO = 'ORDEN_TRABAJO_LINEA'
                                                  , NUMERO_LLAMADAS_INTERFAZ = '0'
                                                  , BAJA = 0";
                                    $bd->ExecSQL($sqlInsert);
                                endif;


                                //ACTUALIZO EL ESTADO TRATADA DE LA LINEA DE LA ORDEN DE TRABAJO
                                $orden_trabajo->ActualizarLinea_Estados($rowPedidoSalidaLinea->ID_ORDEN_TRABAJO_LINEA);
                            endif;
                        endif;

                        //FINALIZO LA TRANSACCION PARA LA INTERFAZ DEL SEMAFORO PARA INFORMAR A SAP DEL BLOQUEO Y CANTIDAD RECEPCIONADA
                        $bd->commit_transaction();

                    endif;


                    if ($orden_trabajo->esTransmitibleInterfaz("Expedicion", "Anulacion Expedicion (anulacion puesta en transito en SCS)", "T02", $rowPedidoSalidaLinea->ID_ORDEN_TRABAJO_LINEA)):

                        //INICIO UNA TRANSACCION PARA LA INTERFAZ DE LA RESERVA DE MATERIAL PARA INFORMAR A SO99 DEL BLOQUEO Y CANTIDAD PEDIDA
                        $bd->begin_transaction();

                        //COMPRUEBO SI TIENE INCIDENCIA DE SISTEMA CREADA
                        $num = $bd->NumRegsTabla("INCIDENCIA_SISTEMA", "TIPO = '" . INCIDENCIA_TIPO_INTERFAZ_SIN_TRANSMITIR . "' AND SUBTIPO = '" . INCIDENCIA_SUBTIPO_ORDENES_TRABAJO_RESERVA . "' AND ID_OBJETO = $rowPedidoSalidaLinea->ID_ORDEN_TRABAJO_LINEA AND TABLA_OBJETO = 'ORDEN_TRABAJO_LINEA' AND ESTADO <> 'Finalizada'");
                        if ($num == 0): //SI NO TIENE INCIDENCIA SISTEMA CREADA REALIZO LA LLAMADA A SAP

                            $resultado = $sap->OrdenesTrabajoReserva($rowPedidoSalidaLinea->ID_ORDEN_TRABAJO_LINEA);
                            if ($resultado['RESULTADO'] != 'OK'): //No se ha podido grabar la información en SAP
                                $strError .= $auxiliar->traduce("Se han producido errores al transmitir la interfaz de la reserva de material de ordenes de trabajo a SO99", $administrador->ID_IDIOMA) . ". " . $datosLinea;
                                foreach ($resultado['ERRORES'] as $arr):
                                    foreach ($arr as $mensaje_error):
                                        $strError = $strError . $mensaje_error . "<br>";
                                    endforeach;
                                endforeach;


                                //DESHAGO LA TRANSACCION
                                $bd->rollback_transaction();


                                //GRABO EL ERROR PRODUCIDO EN BASE DE DATOS
                                $sap->InsertarErrores($resultado);

                                //SI FALLA LA TRANSMISION DEL SEMAFORO Y NO ESTA YA COMO PENDIENTE, LO GRABO PARA SABER QUE ESTA PENDIENTE DE REALIZARSE
                                $num = $bd->NumRegsTabla("INTERFACES_PENDIENTES_TRANSMITIR", "INTERFAZ_PENDIENTE_TRANSMITIR = 'ordenes_trabajo_reserva' AND ID_OBJETO = $rowPedidoSalidaLinea->ID_ORDEN_TRABAJO_LINEA AND TABLA_OBJETO = 'ORDEN_TRABAJO_LINEA' AND BAJA = 0");
                                if ($num == 0):
                                    $sqlInsert = "INSERT INTO INTERFACES_PENDIENTES_TRANSMITIR SET
                                                  INTERFAZ_PENDIENTE_TRANSMITIR = 'ordenes_trabajo_reserva'
                                                  , ID_OBJETO = $rowPedidoSalidaLinea->ID_ORDEN_TRABAJO_LINEA
                                                  , TABLA_OBJETO = 'ORDEN_TRABAJO_LINEA'
                                                  , NUMERO_LLAMADAS_INTERFAZ = '0'
                                                  , BAJA = 0";
                                    $bd->ExecSQL($sqlInsert);
                                endif;
                            endif;
                        endif;
                        //FINALIZO UNA TRANSACCION PARA LA INTERFAZ DE LA RESERVA DE MATERIAL PARA INFORMAR A SO99 DEL BLOQUEO Y CANTIDAD PEDIDA
                        $bd->commit_transaction();
                    endif;
                endif;
            endif;

            //ACTUALIZO EL ESTADO DE LA LINEA DEL PEDIDO DE SALIDA
            $movimiento->actualizarEstadoLineaPedidoSalida($rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA_LINEA);
            //ACTUALIZO EL ESTADO DE LA NECESIDAD DE LA LINEA DEL MOVIMIENTO DE SALIDA
            $movimiento->actualizarEstadoLineaNecesidad($rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA);
        endforeach;

        //ACTUALIZO LA FECHA EXPEDICION DE LA ORDEN DE TRANSPORTE
        $sqlUpdate = "UPDATE EXPEDICION SET
                        FECHA_EXPEDICION = '0000-00-00 00:00:00'
                        WHERE ID_EXPEDICION = $rowOrdenTransporte->ID_EXPEDICION";
        $bd->ExecSQL($sqlUpdate);

        //MUESTRO LOS ERRORES DE MATERIAL SERIABLE EN CASO DE OCURRIR
        $strError = "";
        if ($hayErrorSerieLoteEnSistema == true):
            $strError = $strErrorEnSistema;
            $html->PagError("NseriesYaSeEncuentranEnSistema");
        endif;
        if ($hayErrorSerieLoteEnTransito == true):
            $strError = $strErrorEnTransito;
            $html->PagError("NseriesYaSeEncuentranEnTransito");
        endif;

        //ACTUALIZO EL ESTADO DE LA ORDEN DE TRANSPORTE
        $this->actualizar_estado_orden_transporte($rowOrdenTransporte->ID_EXPEDICION);

        // LOG MOVIMIENTOS
        $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Expedición", $rowOrdenTransporte->ID_EXPEDICION, "Cancelar expedición orden de transporte: " . $rowOrdenTransporte->ID_EXPEDICION);

        //DECLARO UN ARRAY PARA DEVOLVER DATOS
        $arrDevuelto            = array();
        $arrDevuelto['errores'] = $strError;

        //DEVUELVO EL ARRAY
        return $arrDevuelto;
    }

    /**
     * @param $idMovimientoSalidaLinea LINEA DE MOVIMIENTO DE SALIDA A ANULAR SU DESUBICACION Y LINEA DE PEDIDO CORRESPONDIENTE
     */
    function anular_desubicacion_linea_material($idMovimientoSalidaLinea)
    {
        global $bd;
        global $html;
        global $auxiliar;
        global $administrador;

        //RECUPERO LA VARIABLE GLOBAL DE TEXTO ERROR
        global $strError;
        $strError = "";

        //VARIABLE PARA SABER SI HAY ERROR EN LA ANULACION DE LA LINEA
        $hayErrorAnulacionLinea = false;

        //BUSCO LA LINEA DEL MOVIMIENTO DE SALIDA
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowMovimientoSalidaLinea         = $bd->VerReg("MOVIMIENTO_SALIDA_LINEA", "ID_MOVIMIENTO_SALIDA_LINEA", $idMovimientoSalidaLinea, "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);

        //COMPRUEBO QUE EXISTA LA LINEA
        if (($hayErrorAnulacionLinea == false) && ($rowMovimientoSalidaLinea == false)):
            $hayErrorAnulacionLinea = true;
            $strError               = $strError . $auxiliar->traduce("La linea seleccionada no existe", $administrador->ID_IDIOMA);
            $html->PagError("ErrorAnulacionDesubicacionLineaMaterial");
        endif;

        //COMPRUEBO QUE LA LINEA NO ESTE DADA DE BAJA
        if (($hayErrorAnulacionLinea == false) && ($rowMovimientoSalidaLinea->BAJA != 0)):
            $hayErrorAnulacionLinea = true;
            $strError               = $strError . $auxiliar->traduce("La linea seleccionada esta dada de baja", $administrador->ID_IDIOMA);
            $html->PagError("ErrorAnulacionDesubicacionLineaMaterial");
        endif;

        //COMPRUEBO QUE LA LINEA NO ESTE ANULADA
        if (($hayErrorAnulacionLinea == false) && ($rowMovimientoSalidaLinea->LINEA_ANULADA != 0)):
            $hayErrorAnulacionLinea = true;
            $strError               = $strError . $auxiliar->traduce("La linea seleccionada esta anulada", $administrador->ID_IDIOMA);
            $html->PagError("ErrorAnulacionDesubicacionLineaMaterial");
        endif;

        //COMPRUEBO EL ESTADO DE LA LINEA
        if (($hayErrorAnulacionLinea == false) && ($rowMovimientoSalidaLinea->ESTADO != 'Pendiente de Expedir')):
            $hayErrorAnulacionLinea = true;
            $strError               = $strError . $auxiliar->traduce("La linea seleccionada no esta en estado 'Pendiente de Transmitir'", $administrador->ID_IDIOMA);
            $html->PagError("ErrorAnulacionDesubicacionLineaMaterial");
        endif;

        //BUSCO EL MOVIMIENTO DE SALIDA
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowMovimientoSalida              = $bd->VerReg("MOVIMIENTO_SALIDA", "ID_MOVIMIENTO_SALIDA", $rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA, "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);

        //COMPRUEBO QUE EXISTA EL MOVIMIENTO
        if (($hayErrorAnulacionLinea == false) && ($rowMovimientoSalida == false)):
            $hayErrorAnulacionLinea = true;
            $strError               = $strError . $auxiliar->traduce("El movimiento de la linea seleccionada no existe", $administrador->ID_IDIOMA);
            $html->PagError("ErrorAnulacionDesubicacionLineaMaterial");
        endif;

        //COMPRUEBO QUE EL MOVIMIENTO NO ESTE DADO DE BAJA
        if (($hayErrorAnulacionLinea == false) && ($rowMovimientoSalida->BAJA != 0)):
            $hayErrorAnulacionLinea = true;
            $strError               = $strError . $auxiliar->traduce("El movimiento de la linea seleccionada esta dado de baja", $administrador->ID_IDIOMA);
            $html->PagError("ErrorAnulacionDesubicacionLineaMaterial");
        endif;

        //COMPRUEBO QUE EL MOVIMIENTO SEA DE TIPO 'MaterialEstropeadoAProveedor' O 'TraspasoEntreAlmacenesEstropeado'
        if (
            ($hayErrorAnulacionLinea == false) &&
            ($rowMovimientoSalida->TIPO_MOVIMIENTO != 'MaterialEstropeadoAProveedor') &&
            ($rowMovimientoSalida->TIPO_MOVIMIENTO != 'TraspasoEntreAlmacenesEstropeado')
        ):
            $hayErrorAnulacionLinea = true;
            $strError               = $strError . $auxiliar->traduce("El tipo de movimiento no es valido para anular la linea seleccionada", $administrador->ID_IDIOMA);
            $html->PagError("ErrorAnulacionDesubicacionLineaMaterial");
        endif;

        //BUSCO LA ORDEN DE PREPARACION
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowOrdenPreparacion              = $bd->VerReg("ORDEN_PREPARACION", "ID_ORDEN_PREPARACION", $rowMovimientoSalida->ID_ORDEN_PREPARACION, "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);

        //COMPRUEBO QUE EXISTA LA ORDEN DE PREPARACION
        if (($hayErrorAnulacionLinea == false) && ($rowMovimientoSalida == false)):
            $hayErrorAnulacionLinea = true;
            $strError               = $strError . $auxiliar->traduce("La orden de preparacion de la linea seleccionada no existe", $administrador->ID_IDIOMA);
            $html->PagError("ErrorAnulacionDesubicacionLineaMaterial");
        endif;

        //COMPRUEBO QUE LA PREPARACION DE LA ORDEN SEA WEB
        if (($hayErrorAnulacionLinea == false) && ($rowOrdenPreparacion->VIA_PREPARACION != 'WEB')):
            $hayErrorAnulacionLinea = true;
            $strError               = $strError . $auxiliar->traduce("La via preparacion de la orden de preparacion de la linea seleccionada no es de tipo web", $administrador->ID_IDIOMA);
            $html->PagError("ErrorAnulacionDesubicacionLineaMaterial");
        endif;

        //COMPRUEBO QUE LA PREPARACION SEA DE PEDIDOS DE LOGISTICA INVERSA
        if (($hayErrorAnulacionLinea == false) && ($rowOrdenPreparacion->TIPO_ORDEN != 'OLI')):
            $hayErrorAnulacionLinea = true;
            $strError               = $strError . $auxiliar->traduce("La orden de preparacion de la linea seleccionada no es de tipo 'Logistica Inversa'", $administrador->ID_IDIOMA);
            $html->PagError("ErrorAnulacionDesubicacionLineaMaterial");
        endif;

        //BUSCO EL PEDIDO
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowPedidoSalida                  = $bd->VerReg("PEDIDO_SALIDA", "ID_PEDIDO_SALIDA", $rowMovimientoSalidaLinea->ID_PEDIDO_SALIDA, "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);

        //COMPRUEBO QUE EXISTA EL PEDIDO
        if (($hayErrorAnulacionLinea == false) && ($rowPedidoSalida == false)):
            $hayErrorAnulacionLinea = true;
            $strError               = $strError . $auxiliar->traduce("El pedido de la linea seleccionada no existe", $administrador->ID_IDIOMA);
            $html->PagError("ErrorAnulacionDesubicacionLineaMaterial");
        endif;

        //COMPRUEBO QUE EL PEDIDO NO ESTE DADA DE BAJA
        if (($hayErrorAnulacionLinea == false) && ($rowPedidoSalida->BAJA != 0)):
            $hayErrorAnulacionLinea = true;
            $strError               = $strError . $auxiliar->traduce("El pedido de la linea seleccionada esta dado de baja", $administrador->ID_IDIOMA);
            $html->PagError("ErrorAnulacionDesubicacionLineaMaterial");
        endif;

        //COMPRUEBO QUE EL PEDIDO SEA DE TIPO 'Material Estropeado a Proveedor' O 'Traspaso Entre Almacenes Material Estropeado'
        if (
            ($hayErrorAnulacionLinea == false) &&
            ($rowPedidoSalida->TIPO_PEDIDO != 'Material Estropeado a Proveedor') &&
            ($rowPedidoSalida->TIPO_PEDIDO != 'Traspaso Entre Almacenes Material Estropeado')
        ):
            $hayErrorAnulacionLinea = true;
            $strError               = $strError . $auxiliar->traduce("El tipo de movimiento no es valido para anular la linea seleccionada", $administrador->ID_IDIOMA);
            $html->PagError("ErrorAnulacionDesubicacionLineaMaterial");
        endif;

        //BUSCO LA LINEA DE PEDIDO
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowPedidoSalidaLinea             = $bd->VerReg("PEDIDO_SALIDA_LINEA", "ID_PEDIDO_SALIDA_LINEA", $rowMovimientoSalidaLinea->ID_PEDIDO_SALIDA_LINEA, "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);

        //COMPRUEBO QUE EXISTA LA LINEA DE PEDIDO
        if (($hayErrorAnulacionLinea == false) && ($rowPedidoSalidaLinea == false)):
            $hayErrorAnulacionLinea = true;
            $strError               = $strError . $auxiliar->traduce("La linea de pedido de la linea seleccionada no existe", $administrador->ID_IDIOMA);
            $html->PagError("ErrorAnulacionDesubicacionLineaMaterial");
        endif;

        //COMPRUEBO QUE LA LINEA DE PEDIDO NO ESTE DADA DE BAJA
        if (($hayErrorAnulacionLinea == false) && ($rowPedidoSalidaLinea->BAJA != 0)):
            $hayErrorAnulacionLinea = true;
            $strError               = $strError . $auxiliar->traduce("La linea de pedido de la linea seleccionada esta dada de baja", $administrador->ID_IDIOMA);
            $html->PagError("ErrorAnulacionDesubicacionLineaMaterial");
        endif;

        //COMPRUEBO QUE TENGA PERMISOS DE ESCRITURA EN EL ALMACEN
        if (($hayErrorAnulacionLinea == false) && ($administrador->comprobarAlmacenPermiso($rowMovimientoSalidaLinea->ID_ALMACEN, "Escritura") == false)):
            $hayErrorAnulacionLinea = true;
            $strError               = $strError . $auxiliar->traduce("No tiene permisos para realizar esta operación en esta subzona", $administrador->ID_IDIOMA);
            $html->PagError("ErrorAnulacionDesubicacionLineaMaterial");
        endif;

        //BUSCO LAS TRANSFERENCIA DE ESTA LINEA DE MOVIMIENTO DE SALIDA
        $sqlTransferencias    = "SELECT *
                                FROM MOVIMIENTO_TRANSFERENCIA
                                WHERE ID_MOVIMIENTO_SALIDA_LINEA = $rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA_LINEA AND TIPO = 'Automatico' AND BAJA = 0";
        $resultTransferencias = $bd->ExecSQL($sqlTransferencias);
        while ($rowTransferencia = $bd->SigReg($resultTransferencias)):
            //BUSCO MATERIAL UBICACION  DONDE SE DEPOSITÓ EL MATERIAL (DEBERIA SER DE TIPO SALIDA)
            $NotificaErrorPorEmail = "No";
            $rowMatUbi             = $bd->VerRegRest("MATERIAL_UBICACION", "ID_MATERIAL = $rowTransferencia->ID_MATERIAL AND ID_UBICACION = $rowTransferencia->ID_UBICACION_DESTINO AND ID_MATERIAL_FISICO " . ($rowTransferencia->ID_MATERIAL_FISICO == NULL ? 'IS NULL' : " = $rowTransferencia->ID_MATERIAL_FISICO") . " AND ID_TIPO_BLOQUEO " . ($rowTransferencia->ID_TIPO_BLOQUEO == NULL ? 'IS NULL' : "= $rowTransferencia->ID_TIPO_BLOQUEO") . " AND ID_ORDEN_TRABAJO_MOVIMIENTO " . ($rowTransferencia->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? 'IS NULL' : "= $rowTransferencia->ID_ORDEN_TRABAJO_MOVIMIENTO") . " AND ID_INCIDENCIA_CALIDAD " . ($rowTransferencia->ID_INCIDENCIA_CALIDAD == NULL ? 'IS NULL' : "= $rowTransferencia->ID_INCIDENCIA_CALIDAD"), "No");
            unset($NotificaErrorPorEmail);

            //COMPRUEBO QUE EXISTE EL MATERIAL UBICACION ORIGEN (SM)
            if (($hayErrorAnulacionLinea == false) && ($rowMatUbi == false)):
                $hayErrorAnulacionLinea = true;
                $strError               = $strError . $auxiliar->traduce("No existe la ubicacion destino donde fue desubicado el material", $administrador->ID_IDIOMA);
                $html->PagError("ErrorAnulacionDesubicacionLineaMaterial");
            endif;

            //COMPRUEBO QUE EN LA UBICACION ORIGEN (SM) HAYA SUFICIENTE STOCK
            if (($hayErrorAnulacionLinea == false) && ($rowMatUbi->STOCK_TOTAL < $rowTransferencia->CANTIDAD)):
                $hayErrorAnulacionLinea = true;
                $strError               = $strError . $auxiliar->traduce("No existe stock suficiente para anular la linea seleccionada", $administrador->ID_IDIOMA);
                $html->PagError("ErrorAnulacionDesubicacionLineaMaterial");
            endif;

            //DECREMENTO CANTIDAD EN MATERIAL UBICACION ORIGEN (DEBERIA SER DE TIPO SALIDA - SM)
            $sqlUpdate = "UPDATE MATERIAL_UBICACION SET
                            STOCK_TOTAL = STOCK_TOTAL - $rowTransferencia->CANTIDAD
                            , STOCK_OK = STOCK_OK - " . ($rowTransferencia->ID_TIPO_BLOQUEO == NULL ? $rowTransferencia->CANTIDAD : 0) . "
                            , STOCK_BLOQUEADO = STOCK_BLOQUEADO - " . ($rowTransferencia->ID_TIPO_BLOQUEO == NULL ? 0 : $rowTransferencia->CANTIDAD) . "
                            WHERE ID_MATERIAL_UBICACION = $rowMatUbi->ID_MATERIAL_UBICACION";
            $bd->ExecSQL($sqlUpdate);

            //BUSCO LA UBICACION DESTINO (DE DONDE SE DESUBICO EL MATERIAL)
            $NotificaErrorPorEmail = "No";
            $rowMatUbi             = $bd->VerRegRest("MATERIAL_UBICACION", "ID_MATERIAL = $rowTransferencia->ID_MATERIAL AND ID_UBICACION = $rowTransferencia->ID_UBICACION_ORIGEN AND ID_MATERIAL_FISICO " . ($rowTransferencia->ID_MATERIAL_FISICO == NULL ? 'IS NULL' : " = $rowTransferencia->ID_MATERIAL_FISICO") . " AND ID_TIPO_BLOQUEO " . ($rowTransferencia->ID_TIPO_BLOQUEO == NULL ? 'IS NULL' : "= $rowTransferencia->ID_TIPO_BLOQUEO") . " AND ID_ORDEN_TRABAJO_MOVIMIENTO " . ($rowTransferencia->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? 'IS NULL' : "= $rowTransferencia->ID_ORDEN_TRABAJO_MOVIMIENTO") . " AND ID_INCIDENCIA_CALIDAD " . ($rowTransferencia->ID_INCIDENCIA_CALIDAD == NULL ? 'IS NULL' : "= $rowTransferencia->ID_INCIDENCIA_CALIDAD"), "No");
            unset($NotificaErrorPorEmail);
            if ($rowMatUbi == false):
                //CREO MATERIAL UBICACION
                $sqlInsert = "INSERT INTO MATERIAL_UBICACION SET
                                ID_MATERIAL = $rowTransferencia->ID_MATERIAL
                                , ID_UBICACION = $rowTransferencia->ID_UBICACION_ORIGEN
                                , ID_MATERIAL_FISICO = " . ($rowTransferencia->ID_MATERIAL_FISICO == NULL ? 'NULL' : "$rowTransferencia->ID_MATERIAL_FISICO") . "
                                , ID_TIPO_BLOQUEO = " . ($rowTransferencia->ID_TIPO_BLOQUEO == NULL ? 'NULL' : "$rowTransferencia->ID_TIPO_BLOQUEO") . "
                                , ID_ORDEN_TRABAJO_MOVIMIENTO = " . ($rowTransferencia->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? 'NULL' : "$rowTransferencia->ID_ORDEN_TRABAJO_MOVIMIENTO") . "
                                , ID_INCIDENCIA_CALIDAD =" . ($rowTransferencia->ID_INCIDENCIA_CALIDAD == NULL ? 'NULL' : "$rowTransferencia->ID_INCIDENCIA_CALIDAD");
                $bd->ExecSQL($sqlInsert);
                $idMatUbi = $bd->IdAsignado();
            else:
                $idMatUbi = $rowMatUbi->ID_MATERIAL_UBICACION;
            endif;

            //INCREMENTO CANTIDAD EN MATERIAL UBICACION DESTINO (DE DONDE SE DESUBICO EL MATERIAL)
            $sqlUpdate = "UPDATE MATERIAL_UBICACION SET
                            STOCK_TOTAL = STOCK_TOTAL + $rowTransferencia->CANTIDAD
                            , STOCK_OK = STOCK_OK + " . ($rowTransferencia->ID_TIPO_BLOQUEO == NULL ? $rowTransferencia->CANTIDAD : 0) . "
                            , STOCK_BLOQUEADO = STOCK_BLOQUEADO + " . ($rowTransferencia->ID_TIPO_BLOQUEO == NULL ? 0 : $rowTransferencia->CANTIDAD) . "
                            WHERE ID_MATERIAL_UBICACION = $idMatUbi";
            $bd->ExecSQL($sqlUpdate);

            //DAMOS DE BAJA LA TRANSFERENCIA AUTOMATICA
            $sqlUpdate = "UPDATE MOVIMIENTO_TRANSFERENCIA SET
                            ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                            , BAJA = 1
                            WHERE ID_MOVIMIENTO_TRANSFERENCIA = $rowTransferencia->ID_MOVIMIENTO_TRANSFERENCIA";
            $bd->ExecSQL($sqlUpdate);
        endwhile;
        //FIN BUSCO LAS TRANSFERENCIA DE ESTA LINEA DE MOVIMIENTO DE SALIDA

        //BUSCO LA EXPEDICION
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowExpedicion                    = $bd->VerReg("EXPEDICION", "ID_EXPEDICION", $rowMovimientoSalidaLinea->ID_EXPEDICION, "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);

        if ($rowExpedicion->ID_ORDEN_TRANSPORTE != NULL):
            $GLOBALS["NotificaErrorPorEmail"] = "No";
            $rowOrdenTransporte               = $bd->VerReg("ORDEN_TRANSPORTE", "ID_ORDEN_TRANSPORTE", $rowExpedicion->ID_ORDEN_TRANSPORTE, "No");
            unset($GLOBALS["NotificaErrorPorEmail"]);

            //SI TIENE GASTOS DE TRANSPORTE Y ESTAN ENVIADOS, MARCAMOS LA LINEA PARA QUE SE SIGA MOSTRANDO
            if ($rowOrdenTransporte->TIENE_GASTOS_TRANSPORTE == 1 && $rowOrdenTransporte->ESTADO_INTERFACES == "Finalizada"):
                $sqlInsert = "INSERT INTO ORDEN_TRANSPORTE_LINEA_ANULADA SET
                                  ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE
                                , ID_EXPEDICION = $rowExpedicion->ID_EXPEDICION
                                , ID_MOVIMIENTO_SALIDA_LINEA = $rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA_LINEA";
                $bd->ExecSQL($sqlInsert);
            endif;
            //FIN SI TIENE GASTOS DE TRANSPORTE Y ESTAN ENVIADOS, MARCAMOS LA LINEA PARA QUE SE SIGA MOSTRANDO
        endif;

        //DAMOS DE BAJA LA LINEA DEL MOVIMIENTO
        $sqlUpdate = "UPDATE MOVIMIENTO_SALIDA_LINEA SET
                        ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                        , BAJA = 1
                        WHERE ID_MOVIMIENTO_SALIDA_LINEA = $rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA_LINEA";
        $bd->ExecSQL($sqlUpdate);

        // LOG MOVIMIENTOS
        $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Mov. salida", $rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA, "Anulacion linea: " . $rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA_LINEA);

        //COMPRUEBO SI EL MOVIMIENTO NO TIENE LINEAS PARA DARLO DE BAJA
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $numLineasMovimientoActivas       = $bd->VerRegRest("MOVIMIENTO_SALIDA_LINEA", "ID_MOVIMIENTO_SALIDA = $rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA AND LINEA_ANULADA = 0 AND BAJA = 0", "No");
        if ($numLineasMovimientoActivas == 0):
            //DAMOS DE BAJA EL MOVIMIENTO
            $sqlUpdate = "UPDATE MOVIMIENTO_SALIDA SET
                            ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                            , BAJA = 1
                            WHERE ID_MOVIMIENTO_SALIDA = $rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA";
            $bd->ExecSQL($sqlUpdate);

            // LOG MOVIMIENTOS
            $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Anulación", "Mov. salida", $rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA, "");
        endif;

        //DAMOS DE BAJA LA LINEA DEL PEDIDO
        $sqlUpdate = "UPDATE PEDIDO_SALIDA_LINEA SET
                        ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                        , BAJA = 1
                        , INDICADOR_BORRADO = '" . ($rowPedidoSalidaLinea->ENVIADO_SAP == 1 ? 'L' : $rowPedidoSalidaLinea->INDICADOR_BORRADO) . "'
                        WHERE ID_PEDIDO_SALIDA_LINEA = $rowMovimientoSalidaLinea->ID_PEDIDO_SALIDA_LINEA";
        $bd->ExecSQL($sqlUpdate);

        // LOG MOVIMIENTOS
        $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Pedido salida", $rowMovimientoSalidaLinea->ID_PEDIDO_SALIDA, "Anulacion linea: " . $rowMovimientoSalidaLinea->ID_PEDIDO_SALIDA_LINEA);

        //COMPRUEBO SI EL PEDIDO NO TIENE LINEAS PARA DARLO DE BAJA
        $numLineasPedidoActivas = $bd->VerRegRest("PEDIDO_SALIDA_LINEA", "ID_PEDIDO_SALIDA = $rowMovimientoSalidaLinea->ID_PEDIDO_SALIDA AND BAJA = 0", "No");
        if ($numLineasPedidoActivas == 0):
            //DAMOS DE BAJA EL PEDIDO
            $sqlUpdate = "UPDATE PEDIDO_SALIDA SET
                            ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                            , BAJA = 1
                            , ESTADO = 'Finalizado'
                            WHERE ID_PEDIDO_SALIDA = $rowMovimientoSalidaLinea->ID_PEDIDO_SALIDA";
            $bd->ExecSQL($sqlUpdate);

            // LOG MOVIMIENTOS
            $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Anulación", "Pedido salida", $rowMovimientoSalidaLinea->ID_PEDIDO_SALIDA, "");
        endif;

        //SI TODAS LAS LINEAS DE LA ORDEN DE PREPARACION HAN SIDO ANULADAS, ANULO LA ORDEN
        $num = $bd->NumRegsTabla("MOVIMIENTO_SALIDA_LINEA MSL INNER JOIN MOVIMIENTO_SALIDA MS ON MS.ID_MOVIMIENTO_SALIDA = MSL.ID_MOVIMIENTO_SALIDA", "MS.ID_ORDEN_PREPARACION = $rowOrdenPreparacion->ID_ORDEN_PREPARACION AND MS.BAJA = 0 AND MSL.BAJA = 0 AND MSL.LINEA_ANULADA = 0");
        if ($num == 0):
            //DAMOS DE BAJA LA ORDEN DE PREPARACION
            $sqlUpdate = "UPDATE ORDEN_PREPARACION SET
                            ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                            , BAJA = 1
                            WHERE ID_ORDEN_PREPARACION = $rowOrdenPreparacion->ID_ORDEN_PREPARACION";
            $bd->ExecSQL($sqlUpdate);

            // LOG MOVIMIENTOS
            $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Anulación", "Orden preparación", $rowOrdenPreparacion->ID_ORDEN_PREPARACION, "");
        endif;

        //ACTUALIZO EL ESTADO DE LA ORDEN DE RECOGIDA
        if ($rowMovimientoSalidaLinea->ID_EXPEDICION != NULL):
            $this->actualizar_estado_orden_transporte($rowMovimientoSalidaLinea->ID_EXPEDICION);
        endif;
    }

    /**
     * @param $idOrdenTransporte ORDEN DE TRANSPORTE
     * FUNCION UTILIZADA PARA DADA UNA ORDEN DE RECOGIDA, ACTUALIZAR LA VERSION Y BORRAR FISICAMENTE LOS BULTOS ASOCIADOS A ESTA SI ES NECESARIO
     */
    function actualizar_version_bultos_orden_transporte($idOrdenTransporte)
    {
        global $bd;

        //BUSCO LA ORDEN DE TRANSPORTE
        $rowOrdenTransporte = $bd->VerReg("EXPEDICION", "ID_EXPEDICION", $idOrdenTransporte);

        //SI LA ORDEN DE TRANSPORTE ES DE PRIMERA VERSION COMPRUEBO SI ES NECESARIO BORRAR LOS BULTOS ASOCIADOS A ESTA
        if ($rowOrdenTransporte->VERSION == 'Primera'):
            //BUSCO LOS BULTOS ASOCIADOS A ESTA ORDEN DE TRANSPORTE
            $sqlBultosAsociadosOrdenTransporte    = "SELECT DISTINCT(ID_BULTO)
                                                    FROM BULTO B
                                                    WHERE B.ID_EXPEDICION = $rowOrdenTransporte->ID_EXPEDICION";
            $resultBultosAsociadosOrdenTransporte = $bd->ExecSQL($sqlBultosAsociadosOrdenTransporte);
            while ($rowBultosAsociadosOrdenTransporte = $bd->SigReg($resultBultosAsociadosOrdenTransporte)):
                //COMPRUEBO QUE EL BULTO NO TENGA MATERIAL ASOCIADO
                $numLineasBultoConMaterial = $bd->NumRegsTabla("BULTO_LINEA", "ID_BULTO = $rowBultosAsociadosOrdenTransporte->ID_BULTO AND CANTIDAD <> 0");

                //SI NO HAY MATERIAL ASOCIADO
                if ($numLineasBultoConMaterial == 0):
                    //COMPRUEBO QUE EL CONTENEDOR DE TIPO BULTO TAMPOCO TENGA MATERIAL ASOCIADO
                    $numLineasContenedorConMaterial = $bd->NumRegsTabla("CONTENEDOR_LINEA CL INNER JOIN CONTENEDOR C ON C.ID_CONTENEDOR = CL.ID_CONTENEDOR", "C.ID_BULTO = $rowBultosAsociadosOrdenTransporte->ID_BULTO AND CL.STOCK_TOTAL <> 0 AND CL.BAJA = 0");

                    //SI O HAY LINEAS DE CONTENEDOR CON STOCK, LAS BORRAMOS
                    if ($numLineasContenedorConMaterial == 0):
                        //BORRO LAS LINEAS DEL CONTENEDOR
                        $sqlDelete = "DELETE FROM CONTENEDOR_LINEA CL
                                        INNER JOIN CONTENEDOR C ON C.ID_CONTENEDOR = CL.ID_CONTENEDOR
                                        WHERE C.ID_BULTO = $rowBultosAsociadosOrdenTransporte->ID_BULTO";
                        $bd->ExecSQL($sqlDelete);

                        //BORRO EL CONTENEDOR
                        $sqlDelete = "DELETE FROM CONTENEDOR C
                                        WHERE C.ID_BULTO = $rowBultosAsociadosOrdenTransporte->ID_BULTO";
                        $bd->ExecSQL($sqlDelete);

                        //BORRO LAS LINEAS DEL BULTO
                        $sqlDelete = "DELETE FROM BULTO_LINEA BL
                                        WHERE BL.ID_BULTO = $rowBultosAsociadosOrdenTransporte->ID_BULTO";
                        $bd->ExecSQL($sqlDelete);

                        //BORRO EL BULTO
                        $sqlDelete = "DELETE FROM BULTO B
                                        WHERE B.ID_BULTO = $rowBultosAsociadosOrdenTransporte->ID_BULTO";
                        $bd->ExecSQL($sqlDelete);
                    endif;
                endif;
            endwhile;
        endif;

        //SI LA ORDEN DE TRANSPORTE SE QUEDA SIN LINEAS, ACTUALIZO LA VERSION DE LA ORDEN DE TRANSPORTE
        $numLineasOrdenTransporte = $bd->NumRegsTabla("MOVIMIENTO_SALIDA_LINEA", "ID_EXPEDICION = $rowOrdenTransporte->ID_EXPEDICION AND LINEA_ANULADA = 0 AND BAJA = 0");
        if ($numLineasOrdenTransporte == 0):
            $sqlUpdate = "UPDATE EXPEDICION SET
                            VERSION = '" . $this->getVersionExpedicion() . "'
                            WHERE ID_EXPEDICION = $rowOrdenTransporte->ID_EXPEDICION";
            $bd->ExecSQL($sqlUpdate);
        endif;
    }

    /**
     * @param $idOrdenTransporte ORDEN DE RECOGIDA
     * FUNCION UTILIZADA PARA DADA UNA ORDEN DE RECOGIDA, ASIGNARLE EL ESTADO CORRESPONDIENTE
     */
    function actualizar_estado_orden_recogida($idOrdenRecogida)
    {
        global $bd;
        global $orden_transporte;

        //BUSCO LA RECOGIDA
        $rowOrdenRecogida = $bd->VerReg("EXPEDICION", "ID_EXPEDICION", $idOrdenRecogida);//var_dump($rowOrdenRecogida);exit;

        //VARIABLE PARA ESTABLECER EL VALOR FINAL DE LA ORDEN DE TRANSPORTE
        $estadoOrdenTransporte = 'Creada';

        //CALCULO EL NUMERO DE LINEAS EN LOS DIFERENTES ESTADO DE LA ORDEN DE TRANSPORTE
        $numLineas = $bd->NumRegsTabla("MOVIMIENTO_SALIDA_LINEA", "ID_EXPEDICION = $rowOrdenRecogida->ID_EXPEDICION AND LINEA_ANULADA = 0 AND BAJA = 0");

        $numLineasEnPreparacion      = $bd->NumRegsTabla("MOVIMIENTO_SALIDA_LINEA", "ID_EXPEDICION = $rowOrdenRecogida->ID_EXPEDICION AND LINEA_ANULADA = 0 AND BAJA = 0 AND ESTADO = 'En Preparacion'");
        $numLineasPendienteDeExpedir = $bd->NumRegsTabla("MOVIMIENTO_SALIDA_LINEA", "ID_EXPEDICION = $rowOrdenRecogida->ID_EXPEDICION AND LINEA_ANULADA = 0 AND BAJA = 0 AND ESTADO = 'Pendiente de Expedir'");
        $numLineasTransmitidasASAP   = $bd->NumRegsTabla("MOVIMIENTO_SALIDA_LINEA", "ID_EXPEDICION = $rowOrdenRecogida->ID_EXPEDICION AND LINEA_ANULADA = 0 AND BAJA = 0 AND ESTADO = 'Transmitido a SAP'");
        $numLineasExpedidas          = $bd->NumRegsTabla("MOVIMIENTO_SALIDA_LINEA", "ID_EXPEDICION = $rowOrdenRecogida->ID_EXPEDICION AND LINEA_ANULADA = 0 AND BAJA = 0 AND ESTADO = 'Expedido'");
        $numLineasEnTransito         = $bd->NumRegsTabla("MOVIMIENTO_SALIDA_LINEA", "ID_EXPEDICION = $rowOrdenRecogida->ID_EXPEDICION AND LINEA_ANULADA = 0 AND BAJA = 0 AND ESTADO = 'En Tránsito'");
        $numLineasRecepcionadas      = $bd->NumRegsTabla("MOVIMIENTO_SALIDA_LINEA", "ID_EXPEDICION = $rowOrdenRecogida->ID_EXPEDICION AND LINEA_ANULADA = 0 AND BAJA = 0 AND ESTADO = 'Recepcionado'");
        //FIN CALCULO EL NUMERO DE LINEAS EN LOS DIFERENTES ESTADO DE LA ORDEN DE TRANSPORTE

        //CALCULO EL NUMERO DE BULTOS EN LOS DIFERENTES ESTADO DE LA ORDEN DE TRANSPORTE
        $numBultos             = $bd->NumRegsTabla("BULTO", "ID_EXPEDICION = $rowOrdenRecogida->ID_EXPEDICION");
        $numBultosPdteEmbarque = $bd->NumRegsTabla("BULTO", "ID_EXPEDICION = $rowOrdenRecogida->ID_EXPEDICION AND ESTADO = 'Pdte. de Embarcar'");
        $numBultosEmbarcados   = $bd->NumRegsTabla("BULTO", "ID_EXPEDICION = $rowOrdenRecogida->ID_EXPEDICION AND ESTADO = 'Embarcado'");
        //FIN CALCULO EL NUMERO DE LINEAS EN LOS DIFERENTES ESTADO DE LA ORDEN DE TRANSPORTE

        //EN FUNCION DEL NUMERO DE LINEAS Y BULTOS CALCULO EL ESTADO FINAL DE LA ORDEN DE TRANSPORTE
        if ($numLineas == 0):
            $estadoOrdenTransporte = 'Creada';
        elseif ($numLineasEnPreparacion > 0):
            $estadoOrdenTransporte = 'Creada';
        elseif ($numLineasPendienteDeExpedir == $numLineas):
            $estadoOrdenTransporte = 'Creada';
        elseif (($rowOrdenRecogida->CON_BULTOS == 1) && ($numLineasTransmitidasASAP == $numLineas) && ($numBultosEmbarcados == $numBultos)):
            $estadoOrdenTransporte = 'Embarcada';
        elseif (($rowOrdenRecogida->CON_BULTOS == 1) && ($numLineasTransmitidasASAP == $numLineas) && (($numBultosEmbarcados + $numBultosPdteEmbarque) == $numBultos) && ($numBultosEmbarcados > 0)):
            $estadoOrdenTransporte = 'En Embarque';
        elseif ($numLineasTransmitidasASAP == $numLineas):
            $estadoOrdenTransporte = 'Transmitida a SAP';
        elseif (($numLineasPendienteDeExpedir + $numLineasTransmitidasASAP) == $numLineas):
            $estadoOrdenTransporte = 'En Transmision';
        elseif ($numLineasRecepcionadas == $numLineas):
            $estadoOrdenTransporte = 'Recepcionada';
        elseif (($numLineasRecepcionadas + $numLineasEnTransito) == $numLineas):
            $estadoOrdenTransporte = 'En Tránsito';
        elseif (($numLineasRecepcionadas + $numLineasEnTransito + $numLineasExpedidas) == $numLineas):
            $estadoOrdenTransporte = 'Expedida';
        elseif (($numLineasRecepcionadas + $numLineasEnTransito + $numLineasExpedidas + $numLineasTransmitidasASAP) == $numLineas):
            $estadoOrdenTransporte = 'Parcialmente Expedida';
        elseif (($numLineasRecepcionadas + $numLineasEnTransito + $numLineasExpedidas + $numLineasTransmitidasASAP + $numLineasPendienteDeExpedir) == $numLineas):
            $estadoOrdenTransporte = 'En Transmision';
        endif;

        //ESTABLEZCO EL ESTADO DE LA ORDEN DE RECOGIDA
        $sqlUpdate = "UPDATE EXPEDICION SET
                      ESTADO = '" . $estadoOrdenTransporte . "'
                      WHERE ID_EXPEDICION = $rowOrdenRecogida->ID_EXPEDICION";
        $bd->ExecSQL($sqlUpdate);
    }

    /**
     * @param $idOrdenTransporte ORDEN DE RECOGIDA
     * FUNCION UTILIZADA PARA DADA UNA ORDEN DE RECOGIDA, ASIGNARLE EL ESTADO CORRESPONDIENTE
     */
    function actualizar_estado_orden_transporte($idOrdenTransporte)
    {
        global $bd;
        global $orden_transporte;

        //BUSCO LA RECOGIDA
        $rowOrdenRecogida = $bd->VerReg("EXPEDICION", "ID_EXPEDICION", $idOrdenTransporte);//var_dump($rowOrdenRecogida);exit;

        //VARIABLE PARA ESTABLECER EL VALOR FINAL DE LA ORDEN DE TRANSPORTE
        $estadoOrdenTransporte = 'Creada';

        //CALCULO EL NUMERO DE LINEAS EN LOS DIFERENTES ESTADO DE LA ORDEN DE TRANSPORTE
        $numLineas = $bd->NumRegsTabla("MOVIMIENTO_SALIDA_LINEA", "ID_EXPEDICION = $rowOrdenRecogida->ID_EXPEDICION AND LINEA_ANULADA = 0 AND BAJA = 0");

        $numLineasEnPreparacion      = $bd->NumRegsTabla("MOVIMIENTO_SALIDA_LINEA", "ID_EXPEDICION = $rowOrdenRecogida->ID_EXPEDICION AND LINEA_ANULADA = 0 AND BAJA = 0 AND ESTADO = 'En Preparacion'");
        $numLineasPendienteDeExpedir = $bd->NumRegsTabla("MOVIMIENTO_SALIDA_LINEA", "ID_EXPEDICION = $rowOrdenRecogida->ID_EXPEDICION AND LINEA_ANULADA = 0 AND BAJA = 0 AND ESTADO = 'Pendiente de Expedir'");
        $numLineasTransmitidasASAP   = $bd->NumRegsTabla("MOVIMIENTO_SALIDA_LINEA", "ID_EXPEDICION = $rowOrdenRecogida->ID_EXPEDICION AND LINEA_ANULADA = 0 AND BAJA = 0 AND ESTADO = 'Transmitido a SAP'");
        $numLineasExpedidas          = $bd->NumRegsTabla("MOVIMIENTO_SALIDA_LINEA", "ID_EXPEDICION = $rowOrdenRecogida->ID_EXPEDICION AND LINEA_ANULADA = 0 AND BAJA = 0 AND ESTADO = 'Expedido'");
        $numLineasEnTransito         = $bd->NumRegsTabla("MOVIMIENTO_SALIDA_LINEA", "ID_EXPEDICION = $rowOrdenRecogida->ID_EXPEDICION AND LINEA_ANULADA = 0 AND BAJA = 0 AND ESTADO = 'En Tránsito'");
        $numLineasRecepcionadas      = $bd->NumRegsTabla("MOVIMIENTO_SALIDA_LINEA", "ID_EXPEDICION = $rowOrdenRecogida->ID_EXPEDICION AND LINEA_ANULADA = 0 AND BAJA = 0 AND ESTADO = 'Recepcionado'");
        //FIN CALCULO EL NUMERO DE LINEAS EN LOS DIFERENTES ESTADO DE LA ORDEN DE TRANSPORTE

        //CALCULO EL NUMERO DE BULTOS EN LOS DIFERENTES ESTADO DE LA ORDEN DE TRANSPORTE
        $numBultos             = $bd->NumRegsTabla("BULTO", "ID_EXPEDICION = $rowOrdenRecogida->ID_EXPEDICION");
        $numBultosPdteEmbarque = $bd->NumRegsTabla("BULTO", "ID_EXPEDICION = $rowOrdenRecogida->ID_EXPEDICION AND ESTADO = 'Pdte. de Embarcar'");
        $numBultosEmbarcados   = $bd->NumRegsTabla("BULTO", "ID_EXPEDICION = $rowOrdenRecogida->ID_EXPEDICION AND ESTADO = 'Embarcado'");
        //FIN CALCULO EL NUMERO DE LINEAS EN LOS DIFERENTES ESTADO DE LA ORDEN DE TRANSPORTE

        //EN FUNCION DEL NUMERO DE LINEAS Y BULTOS CALCULO EL ESTADO FINAL DE LA ORDEN DE TRANSPORTE
        if ($numLineas == 0):
            $estadoOrdenTransporte = 'Creada';
        elseif ($numLineasEnPreparacion > 0):
            $estadoOrdenTransporte = 'Creada';
        elseif ($numLineasPendienteDeExpedir == $numLineas):
            $estadoOrdenTransporte = 'Creada';
        elseif (($rowOrdenRecogida->CON_BULTOS == 1) && ($numLineasTransmitidasASAP == $numLineas) && ($numBultosEmbarcados == $numBultos)):
            $estadoOrdenTransporte = 'Embarcada';
        elseif (($rowOrdenRecogida->CON_BULTOS == 1) && ($numLineasTransmitidasASAP == $numLineas) && (($numBultosEmbarcados + $numBultosPdteEmbarque) == $numBultos) && ($numBultosEmbarcados > 0)):
            $estadoOrdenTransporte = 'En Embarque';
        elseif ($numLineasTransmitidasASAP == $numLineas):
            $estadoOrdenTransporte = 'Transmitida a SAP';
        elseif (($numLineasPendienteDeExpedir + $numLineasTransmitidasASAP) == $numLineas):
            $estadoOrdenTransporte = 'En Transmision';
        elseif ($numLineasRecepcionadas == $numLineas):
            $estadoOrdenTransporte = 'Recepcionada';
        elseif (($numLineasRecepcionadas + $numLineasEnTransito) == $numLineas):
            $estadoOrdenTransporte = 'En Tránsito';
        elseif (($numLineasRecepcionadas + $numLineasEnTransito + $numLineasExpedidas) == $numLineas):
            $estadoOrdenTransporte = 'Expedida';
        elseif (($numLineasRecepcionadas + $numLineasEnTransito + $numLineasExpedidas + $numLineasTransmitidasASAP) == $numLineas):
            $estadoOrdenTransporte = 'Parcialmente Expedida';
        elseif (($numLineasRecepcionadas + $numLineasEnTransito + $numLineasExpedidas + $numLineasTransmitidasASAP + $numLineasPendienteDeExpedir) == $numLineas):
            $estadoOrdenTransporte = 'En Transmision';
        endif;

        //ESTABLEZCO EL ESTADO DE LA ORDEN DE RECOGIDA
        $sqlUpdate = "UPDATE EXPEDICION SET
                      ESTADO = '" . $estadoOrdenTransporte . "'
                      WHERE ID_EXPEDICION = $rowOrdenRecogida->ID_EXPEDICION";
        $bd->ExecSQL($sqlUpdate);


        //SI LA RECOGIDA TIENE ASIGNADO UN TRANSPORTE, ACTUALIZO SU ESTADO
        if ($rowOrdenRecogida->ID_ORDEN_TRANSPORTE != ""):
            $rowOrdenTransporte = $bd->VerReg("ORDEN_TRANSPORTE", "ID_ORDEN_TRANSPORTE", $rowOrdenRecogida->ID_ORDEN_TRANSPORTE);

            //ACTUALIZAMOS EL ESTADO DEL TRANSPORTE
            $orden_transporte->actualizarEstadoOrdenTransporte($rowOrdenTransporte->ID_ORDEN_TRANSPORTE);

            //ACTUALIZO EL ESTADO INTERFACES DE LA ORDEN DE TRANSPORTE SI ESTA CREADA, EN TRANSMISION O TRANSMITIDA SI ES TRANSPORTE CON GASTOS
            if (($rowOrdenTransporte->ESTADO_INTERFACES == 'Creada' || $rowOrdenTransporte->ESTADO_INTERFACES == "Recogidas en Transmision" || $rowOrdenTransporte->ESTADO_INTERFACES == "Recogidas Transmitidas") &&
                ($rowOrdenTransporte->TIENE_GASTOS_TRANSPORTE == 1)
            ):
                //OBTENGO EL ESTADO FINAL DE LA INTERFACE
                $estadoFinalInterfaces = $orden_transporte->EstadoTransporteSegunRecogidas($rowOrdenTransporte->ID_ORDEN_TRANSPORTE);

                $sqlUpdate = "UPDATE ORDEN_TRANSPORTE SET ESTADO_INTERFACES = '$estadoFinalInterfaces' WHERE ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE";
                $bd->ExecSQL($sqlUpdate);

            endif;
            //ACTUALIZO EL ESTADO DE LA ORDEN DE TRANSPORTE
        endif;

    }

    /**
     * @param $idOrdenTransporte ORDEN DE RECOGIDA
     * FUNCION UTILIZADA PARA DADA UNA ORDEN DE RECOGIDA, ASIGNARLE EL TIPO Y SUBTIPO CORRESPONDIENTE
     */
    function actualizar_tipo_orden_transporte($idOrdenTransporte)
    {
        global $bd;

        //BUSCO LA RECOGIDA
        $rowOrdenRecogida = $bd->VerReg("EXPEDICION", "ID_EXPEDICION", $idOrdenTransporte);

        //SOLO REVISO SI ES UNA RECOGIDA DE TIPO_ORDEN_RECOGIDA 'Recogida en Almacen' QUE SON LAS UNICAS QUE PUEDEN CAMBIAR DE SUBTIPO
        if ($rowOrdenRecogida->TIPO_ORDEN_RECOGIDA == 'Recogida en Almacen'):
            //CALCULO EL NUMERO DE LINEAS DE RECOGIDA
            $numLineas = $bd->NumRegsTabla("MOVIMIENTO_SALIDA_LINEA", "ID_EXPEDICION = $rowOrdenRecogida->ID_EXPEDICION AND LINEA_ANULADA = 0 AND BAJA = 0");
            //SI TIENE LINEAS ACTUALIZO EL SUBTIPO
            if ($numLineas > 0):
                $tieneMaterialEstropeado = false;
                //COMPRUEBO SI LA VARIABLE CONTIENE MATERIAL ESTROPEADO
                $numMaterialEstropeado = $bd->NumRegsTabla("MOVIMIENTO_SALIDA_LINEA MSL INNER JOIN PEDIDO_SALIDA PS ON PS.ID_PEDIDO_SALIDA = MSL.ID_PEDIDO_SALIDA", "PS.TIPO_PEDIDO_SAP IN ('ZTRE', 'ZTRG', 'ZTRH') AND MSL.ID_EXPEDICION = $rowOrdenRecogida->ID_EXPEDICION AND MSL.LINEA_ANULADA = 0 AND MSL.BAJA = 0");
                if ($numMaterialEstropeado > 0):
                    $tieneMaterialEstropeado = true;
                endif;

                $tieneMaterialNoEstropeado = false;
                //COMPRUEBO SI LA VARIABLE CONTIENE MATERIAL NO ESTROPEADO
                $numMaterialNoEstropeado = $bd->NumRegsTabla("MOVIMIENTO_SALIDA_LINEA MSL INNER JOIN PEDIDO_SALIDA PS ON PS.ID_PEDIDO_SALIDA = MSL.ID_PEDIDO_SALIDA", "PS.TIPO_PEDIDO_SAP NOT IN ('ZTRE', 'ZTRG', 'ZTRH') AND MSL.ID_EXPEDICION = $rowOrdenRecogida->ID_EXPEDICION AND MSL.LINEA_ANULADA = 0 AND MSL.BAJA = 0");
                if ($numMaterialNoEstropeado > 0):
                    $tieneMaterialNoEstropeado = true;
                endif;

                //CALCULO LOS DIFERENTES TIPOS DE PEDIDO SGA Y TIPOS DE PEDIDO SAP
                $sqlTiposPedidoSGA_SAP    = "SELECT DISTINCT PS.TIPO_PEDIDO, PS.TIPO_PEDIDO_SAP
                                          FROM MOVIMIENTO_SALIDA_LINEA MSL
                                          INNER JOIN PEDIDO_SALIDA PS ON PS.ID_PEDIDO_SALIDA = MSL.ID_PEDIDO_SALIDA
                                          WHERE MSL.ID_EXPEDICION = $rowOrdenRecogida->ID_EXPEDICION AND MSL.LINEA_ANULADA = 0 AND MSL.BAJA = 0";
                $resultTiposPedidoSGA_SAP = $bd->ExecSQL($sqlTiposPedidoSGA_SAP);

                //EN FUNCION DEL NUMERO DE REGISTROS DEVUELTOS ACTUALIZAMOS LA ORDEN DE RECOGIDA
                if ($bd->NumRegs($resultTiposPedidoSGA_SAP) == 1):  //SI SOLO HAY UN TIPO LE ASIGNO EL SUBTIPO CORRESPONDIENTE A LA ORDEN DE RECOGIDA
                    //EXTRAIGO EL TIPO DE PEDIDO
                    $rowTipoPedidoSGA_SAP = $bd->SigReg($resultTiposPedidoSGA_SAP);

                    //EN FUNCION DEL TIPO DE PEDIDO SGA ACTUALIZO EL SUBTIPO DE LA ORDEN DE RECOGIDA
                    if (($rowTipoPedidoSGA_SAP->TIPO_PEDIDO_SAP == 'ZTRE') || ($rowTipoPedidoSGA_SAP->TIPO_PEDIDO_SAP == 'ZTRG')):
                        $sqlUpdate = "UPDATE EXPEDICION SET SUBTIPO_ORDEN_RECOGIDA = 'Material Estropeado entre Almacenes Intercompany' WHERE ID_EXPEDICION = $rowOrdenRecogida->ID_EXPEDICION";
                    elseif ($rowTipoPedidoSGA_SAP->TIPO_PEDIDO_SAP == 'ZTRH'):
                        $sqlUpdate = "UPDATE EXPEDICION SET SUBTIPO_ORDEN_RECOGIDA = 'Material Estropeado entre Almacenes Intracompany' WHERE ID_EXPEDICION = $rowOrdenRecogida->ID_EXPEDICION";
                    elseif ($rowTipoPedidoSGA_SAP->TIPO_PEDIDO == 'Venta'):
                        $sqlUpdate = "UPDATE EXPEDICION SET SUBTIPO_ORDEN_RECOGIDA = 'Traslados y Ventas' WHERE ID_EXPEDICION = $rowOrdenRecogida->ID_EXPEDICION";
                    elseif ($rowTipoPedidoSGA_SAP->TIPO_PEDIDO == 'Traslado'):
                        $sqlUpdate = "UPDATE EXPEDICION SET SUBTIPO_ORDEN_RECOGIDA = 'Traslados y Ventas' WHERE ID_EXPEDICION = $rowOrdenRecogida->ID_EXPEDICION";
                    elseif ($rowTipoPedidoSGA_SAP->TIPO_PEDIDO == 'Intra Centro Fisico'):
                        $sqlUpdate = "UPDATE EXPEDICION SET SUBTIPO_ORDEN_RECOGIDA = 'Traslados y Ventas' WHERE ID_EXPEDICION = $rowOrdenRecogida->ID_EXPEDICION";
                    elseif ($rowTipoPedidoSGA_SAP->TIPO_PEDIDO == 'Interno Gama'):
                        $sqlUpdate = "UPDATE EXPEDICION SET SUBTIPO_ORDEN_RECOGIDA = 'Traslados y Ventas' WHERE ID_EXPEDICION = $rowOrdenRecogida->ID_EXPEDICION";
                    elseif ($rowTipoPedidoSGA_SAP->TIPO_PEDIDO == 'Pendientes de Ordenes Trabajo'):
                        $sqlUpdate = "UPDATE EXPEDICION SET SUBTIPO_ORDEN_RECOGIDA = 'Traslados y Ventas' WHERE ID_EXPEDICION = $rowOrdenRecogida->ID_EXPEDICION";
                    elseif ($rowTipoPedidoSGA_SAP->TIPO_PEDIDO == 'Devolución a Proveedor'):
                        $sqlUpdate = "UPDATE EXPEDICION SET SUBTIPO_ORDEN_RECOGIDA = 'Devoluciones a Proveedor' WHERE ID_EXPEDICION = $rowOrdenRecogida->ID_EXPEDICION";
                    elseif ($rowTipoPedidoSGA_SAP->TIPO_PEDIDO == 'Componentes a Proveedor'):
                        $sqlUpdate = "UPDATE EXPEDICION SET SUBTIPO_ORDEN_RECOGIDA = 'Componentes a Proveedor' WHERE ID_EXPEDICION = $rowOrdenRecogida->ID_EXPEDICION";
                    elseif ($rowTipoPedidoSGA_SAP->TIPO_PEDIDO == 'Rechazos y Anulaciones a Proveedor'):
                        $sqlUpdate = "UPDATE EXPEDICION SET SUBTIPO_ORDEN_RECOGIDA = 'Rechazos Anulaciones Proveedor' WHERE ID_EXPEDICION = $rowOrdenRecogida->ID_EXPEDICION";
                    elseif ($rowTipoPedidoSGA_SAP->TIPO_PEDIDO == 'Material Estropeado a Proveedor'):
                        $sqlUpdate = "UPDATE EXPEDICION SET SUBTIPO_ORDEN_RECOGIDA = 'Material Estropeado a Proveedor' WHERE ID_EXPEDICION = $rowOrdenRecogida->ID_EXPEDICION";
                    elseif ($rowTipoPedidoSGA_SAP->TIPO_PEDIDO == 'Traslados OM Construccion'):
                        $sqlUpdate = "UPDATE EXPEDICION SET SUBTIPO_ORDEN_RECOGIDA = 'Entrega Material OM' WHERE ID_EXPEDICION = $rowOrdenRecogida->ID_EXPEDICION";
                    elseif ($rowTipoPedidoSGA_SAP->TIPO_PEDIDO == 'Traspaso Entre Almacenes Material Estropeado'):
                        exit ('No debería darse el caso');
                        $sqlUpdate = "UPDATE EXPEDICION SET SUBTIPO_ORDEN_RECOGIDA = 'Material Estropeado entre Almacenes Intracompany' WHERE ID_EXPEDICION = $rowOrdenRecogida->ID_EXPEDICION";
                    endif;
                else:
                    if (($tieneMaterialNoEstropeado == true) && ($tieneMaterialEstropeado == true)):
                        $sqlUpdate = "UPDATE EXPEDICION SET SUBTIPO_ORDEN_RECOGIDA = 'Traslado Multi-Estado' WHERE ID_EXPEDICION = $rowOrdenRecogida->ID_EXPEDICION";
                    elseif (($tieneMaterialNoEstropeado == false) && ($tieneMaterialEstropeado == false)):
                        $sqlUpdate = "UPDATE EXPEDICION SET SUBTIPO_ORDEN_RECOGIDA = 'Traslados y Ventas' WHERE ID_EXPEDICION = $rowOrdenRecogida->ID_EXPEDICION";
                    elseif (($tieneMaterialNoEstropeado == true) && ($tieneMaterialEstropeado == false)):
                        $sqlUpdate = "UPDATE EXPEDICION SET SUBTIPO_ORDEN_RECOGIDA = 'Traslados y Ventas' WHERE ID_EXPEDICION = $rowOrdenRecogida->ID_EXPEDICION";
                    elseif (($tieneMaterialNoEstropeado == false) && ($tieneMaterialEstropeado == true)):
                        $numZTRH      = 0; //SI SON TODOS ZTRH -> 'Material Estropeado entre Almacenes Intracompany'
                        $numZTRE_ZTRG = 0; //SI SON TODOS ZTRE/ZTRG -> 'Material Estropeado entre Almacenes Intercompany'
                        //OTRO CASO -> 'Traslado Multi-Estado'
                        while ($rowTipoPedidoSGA_SAP = $bd->SigReg($resultTiposPedidoSGA_SAP)):
                            if ($rowTipoPedidoSGA_SAP->TIPO_PEDIDO_SAP == 'ZTRH'):
                                $numZTRH = $numZTRH + 1;
                            elseif (($rowTipoPedidoSGA_SAP->TIPO_PEDIDO_SAP == 'ZTRE') || ($rowTipoPedidoSGA_SAP->TIPO_PEDIDO_SAP == 'ZTRG')):
                                $numZTRE_ZTRG = $numZTRE_ZTRG + 1;
                            endif;
                        endwhile;

                        if (($numZTRH > 0) && ($numZTRE_ZTRG == 0)):
                            $sqlUpdate = "UPDATE EXPEDICION SET SUBTIPO_ORDEN_RECOGIDA = 'Material Estropeado entre Almacenes Intracompany' WHERE ID_EXPEDICION = $rowOrdenRecogida->ID_EXPEDICION";
                        elseif (($numZTRH == 0) && ($numZTRE_ZTRG > 0)):
                            $sqlUpdate = "UPDATE EXPEDICION SET SUBTIPO_ORDEN_RECOGIDA = 'Material Estropeado entre Almacenes Intercompany' WHERE ID_EXPEDICION = $rowOrdenRecogida->ID_EXPEDICION";
                        elseif (($numZTRH > 0) && ($numZTRE_ZTRG > 0)):
                            $sqlUpdate = "UPDATE EXPEDICION SET SUBTIPO_ORDEN_RECOGIDA = 'Traslado Multi-Estado' WHERE ID_EXPEDICION = $rowOrdenRecogida->ID_EXPEDICION";
                        else:
                            exit ('No debería darse el caso. No existen pedidos ZTRH/ZTRE/ZTRG');
                        endif;
                    endif;
                endif;
                //FIN EN FUNCION DEL NUMERO DE REGISTROS DEVUELTOS ACTUALIZAMOS LA ORDEN DE RECOGIDA

                //ASIGNO EL SUBTIPO CORREPONDIENTE A LA ORDEN DE RECOGIDA
                $bd->ExecSQL($sqlUpdate);
            endif;
            //FIN SI TIENE LINEAS ACTUALIZO EL SUBTIPO
        endif;
        //FIN SOLO REVISO SI ES UNA RECOGIDA DE TIPO 'Recogida en Almacen' QUE SON LAS UNICAS QUE PUEDEN CAMBIAR DE SUBTIPO
    }

    /**
     * @param $arrIdBultosCerradosModificados array de bultos involucrados en la operacion correspondiente
     * @return string lista de bultos que han cambiado su estructura de lineas
     * FUNCION UTILIZADA PARA CALCULAR DE UNA LISTA DE BULTOS CERRADOS, CUALES HAN CAMBIADO SU ESTRUCTURA DE LINEAS Y SIGUEN CERRADOS
     */
    function CalcularBultosCerradosCambianEstructuraLineas($arrIdBultosCerradosModificados)
    {
        global $bd;

        //RECORRO LOS BULTOS PARA COMPROBAR CUALES SIGUEN EXISTIENDO CON UNA ESTRUCTURA DE LINEAS DIFERENTE A LA ORIGINAL
        for ($i = 0; $i < count( (array)$arrIdBultosCerradosModificados); $i++):
            //BUSCO EL BULTO
            $GLOBALS["NotificaErrorPorEmail"] = "No";
            $rowBulto                         = $bd->VerReg("BULTO", "ID_BULTO", $arrIdBultosCerradosModificados[$i], "No");
            unset($GLOBALS["NotificaErrorPorEmail"]);

            //SI EXISTE EL BULTO SIGO HACIENDO COMPROBACIONES
            if ($rowBulto != false):
                //CALCULO EL NUMERO DE LINEAS
                $num = $bd->NumRegsTabla("MOVIMIENTO_SALIDA_LINEA", "ID_BULTO = $rowBulto->ID_BULTO AND LINEA_ANULADA = 0 AND BAJA = 0");
                if (($num == 0) || ($rowBulto->ESTADO != 'Cerrado')): //SI NO TIENE LINEAS O NO ESTA CERRADO, LO DESASIGNO DE LOS BULTOS A MOSTRAR PARA MODIFICAR SUS DIMENSIONES/PESO
                    unset($arrIdBultosCerradosModificados[$i]);
                endif;
            else:   //SI NO EXISTE EL BULTO, LO DESASIGNO DE LOS BULTOS A MOSTRAR PARA MODIFICAR SUS DIMENSIONES/PESO
                unset($arrIdBultosCerradosModificados[$i]);
            endif;
        endfor;

        if (count( (array)$arrIdBultosCerradosModificados) == 0):
            $listaDevolver = "";
        else:
            $listaDevolver = implode(",", (array) $arrIdBultosCerradosModificados);
        endif;

        return $listaDevolver;
    }

    /**
     * @param $expedicionSAPConIdentificadores ES LA EXPEDICION SAP QUE CONTIENE LOS MATERIALES SOBRE LOS QUE HACER EL CAMBIO DE ESTADO
     * @param $idTipoBloqueoOriginal ES EL TIPO DE BLOQUEO ORIGINAL DE LOS MATERIALES
     * @param $idTipoBloqueoFinal ES EL TIPO DE BLOQUEO FINAL DE LOS MATERIALES
     * @param $tipoUbicacion ES EL TIPO DE UBICACION DONDE SE GENERA EL CAMBIO DE ESTADO GRUPO
     * @return $array ARARY DEVUELTO CON LOS POSIBLES ERRORES
     * FUNCION UTLIZADA PARA REALIZAR UN CAMBIO DE ESTADO PREVIO A UNA ACCION SOBRE LA ORDEN DE TRANSPORTE SI EL PEDIDO ES DE TIPO PREVENTIVO, SE PREPARA CON MATERIAL CORRECTIVO Y SE TRANSMITE A SAP Y SE EXPIDE COMO PREVENTIVO
     */
    function generar_cambio_estado_expedicionSAP($version, $expedicionSAPConIdentificadores, $idTipoBloqueoOriginal, $idTipoBloqueoFinal, $tipoUbicacion)
    {
        global $bd;
        global $html;
        global $auxiliar;
        global $administrador;
        global $sap;
        global $exp_SAP;

        //RECUPERO LA VARIABLE GLOBAL DE TEXTO ERROR
        global $strError;
        $strError = "";

        //DECLARO UNA FECHA PARA QUE TODOS LOS CAMBIOS DE ESTADO Y EL DE GRUPO VAYAN CON LA MISMA
        $fechaHora = date("Y-m-d H:i:s");

        //DESCOMPONGO LA EXPEDICION SAP FORMADA POR IDENTIFICADORES
        $arrValoresExpedicionSAP = $exp_SAP->getIdentificadoresExpedicionSAPConIdentificadores($version, $expedicionSAPConIdentificadores);
        $idOrdenTransporte       = $arrValoresExpedicionSAP['ID_EXPEDICION'];
        if ($version == 'Tercera'):
            $idMovimientoBulto = $arrValoresExpedicionSAP['ID_MOVIMIENTO_BULTO'];
        elseif ($version == 'Cuarta'):
            $idExpedicionSAP = $arrValoresExpedicionSAP['ID_EXPEDICION_SAP'];
        else:
            $html->PagError("VersionNoValida");
        endif;
        $idPedido = $arrValoresExpedicionSAP['ID_PEDIDO_SALIDA'];

        //BUSCO LA ORDEN DE TRANSPORTE
        $NotificaErrorPorEmail = "No";
        $rowOrdenTransporte    = $bd->VerReg("EXPEDICION", "ID_EXPEDICION", $idOrdenTransporte, "No");
        $html->PagErrorCondicionado($rowOrdenTransporte, "==", false, "ExpedicionNoExiste");
        unset($NotificaErrorPorEmail);

        //VARIABLE PARA SABER SI SE PRODUCE UN ERROR EN LA TRANSMISION DEL CAMBIO DE ESTADO DE LOS MATERIALES DE LA EXPEDICION SAP
        $hayErrorCambioEstadoExpedicionSAP = false;

        //CONFORMO EL VALOR DE LA EXPEDICION SAP
        if ($version == 'Tercera'):
            $expedicionSAP = $exp_SAP->getExpedicionSAP($rowOrdenTransporte->ID_ORDEN_TRANSPORTE, $rowOrdenTransporte->ID_EXPEDICION, $idMovimientoBulto, $idPedido);
        elseif ($version == 'Cuarta'):
            $expedicionSAP = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE . "_" . $idExpedicionSAP;
        endif;

        //BUSCO EL PEDIDO DE SALIDA
        $rowPedSal = $bd->VerReg("PEDIDO_SALIDA", "ID_PEDIDO_SALIDA", $idPedido);

        //BUSCO EL TIPO DE BLOQUEO PREVENTIVO
        $rowTipoBloqueoPreventivo = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "SP");

        //EN FUNCION DE LOS TIPOS DE BLOQUEO MANDARE UN TIPO CAMBIO ESTADO U OTRO
        if (($idTipoBloqueoOriginal == NULL) && ($idTipoBloqueoFinal == $rowTipoBloqueoPreventivo->ID_TIPO_BLOQUEO)):
            $tipoCambioEstado = 'LibrePreventivo';
        elseif (($idTipoBloqueoOriginal == $rowTipoBloqueoPreventivo->ID_TIPO_BLOQUEO) && ($idTipoBloqueoFinal == NULL)):
            $tipoCambioEstado = 'PreventivoLibre';
        else:
            $html->PagError("TiposBloqueoNoValidos");
        endif;

        //BUSCO LAS LINEAS DE LA EXPEDICION SAP
        if ($tipoCambioEstado == "LibrePreventivo"):
            $sqlLineas = "SELECT *
                            FROM MOVIMIENTO_SALIDA_LINEA
                            WHERE EXPEDICION_SAP = '" . $expedicionSAP . "' AND LINEA_ANULADA = 0 AND BAJA = 0";
        elseif ($tipoCambioEstado == "PreventivoLibre"):
            if ($version == 'Tercera'):
                $sqlLineas = "SELECT *
                                FROM MOVIMIENTO_SALIDA_LINEA
                                WHERE ID_EXPEDICION = $idOrdenTransporte AND ID_PEDIDO_SALIDA = $idPedido AND " . ($rowOrdenTransporte->CON_BULTOS == 0 ? 'ID_MOVIMIENTO_SALIDA = ' : 'ID_BULTO = ') . " $idMovimientoBulto AND LINEA_ANULADA = 0 AND BAJA = 0";
            elseif ($version == 'Cuarta'):
                //BUSCO EL REGISTRO EXPEDICION SAP
                $rowExpedicionSAP = $bd->VerReg("EXPEDICION_SAP", "ID_EXPEDICION_SAP", $idExpedicionSAP);
                $sqlLineas        = "SELECT *
                                FROM MOVIMIENTO_SALIDA_LINEA
                                WHERE ID_EXPEDICION = $idOrdenTransporte AND ID_PEDIDO_SALIDA = $idPedido AND " . ($rowOrdenTransporte->CON_BULTOS == 0 ? 'ID_MOVIMIENTO_SALIDA = ' . $rowExpedicionSAP->ID_MOVIMIENTO_SALIDA : 'ID_BULTO = ' . $rowExpedicionSAP->ID_BULTO) . " AND LINEA_ANULADA = 0 AND BAJA = 0";
            endif;
        endif;
        $resultLineas = $bd->ExecSQL($sqlLineas);//exit($sqlLineas);

        //SI HAY ALGUNA LINEA, GENERO EL CAMBIO DE ESTADO GRUPO
        if ($bd->NumRegs($resultLineas) > 0):
            //GENERO EL CAMBIO DE ESTADO GRUPO
            $sqlInsert = "INSERT INTO CAMBIO_ESTADO_GRUPO SET
                            FECHA = '" . $fechaHora . "'
                            , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                            , NOMBRE = 'D+" . $rowOrdenTransporte->ID_EXPEDICION . "'
                            , PENDIENTE_REVERTIR = 1
                            , ID_EXPEDICION = $rowOrdenTransporte->ID_EXPEDICION
                            , EXPEDICION_SAP = '" . $expedicionSAP . "'";
            $bd->ExecSQL($sqlInsert);//exit($sqlInsert);
            $idCambioEstadoGrupo = $bd->IdAsignado();

            //ACTUALIZO EL NOMBRE DEL CAMBIO DE ESTADO GRUPO GENERADO
            $sqlUpdate = "UPDATE CAMBIO_ESTADO_GRUPO SET
                            NOMBRE = 'D" . $idCambioEstadoGrupo . "'
                            WHERE ID_CAMBIO_ESTADO_GRUPO = $idCambioEstadoGrupo";
            $bd->ExecSQL($sqlUpdate);
        endif;

        //RECORRO LA SELECCION DE LINEAS DE LA EXPEDICION SAP
        while ($rowLinea = $bd->SigReg($resultLineas)):
            //BUSCO LA UBICACION DONDE GENERAR EL CAMBIO DE ESTADO
            $GLOBALS["NotificaErrorPorEmail"] = "No";
            $rowUbi                           = $bd->VerRegRest("UBICACION", "ID_ALMACEN = $rowLinea->ID_ALMACEN AND TIPO_UBICACION = '" . $tipoUbicacion . "' AND BAJA = 0", "No");
            unset($GLOBALS["NotificaErrorPorEmail"]);
            if ($rowUbi == false):
                $hayErrorCambioEstadoExpedicionSAP = true;
                $strError                          = $strError . $auxiliar->traduce("No se ha encontrado la ubicacion de salida donde realizar el cambio de estado", $administrador->ID_IDIOMA) . ".<br>";
            endif;

            //SI NO HAY ERRORES PROCEDO A GENERAR LOS CAMBIOS DE ESTADO CORRESPONDIENTES
            if ($hayErrorCambioEstadoExpedicionSAP == false):
                //GENERO EL CAMBIO DE ESTADO
                $sqlInsert = "INSERT INTO CAMBIO_ESTADO SET
                                ID_CAMBIO_ESTADO_GRUPO = $idCambioEstadoGrupo
                                , FECHA = '" . $fechaHora . "'
                                , TIPO_CAMBIO_ESTADO = 'Automatico'
                                , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                , ID_MATERIAL = $rowLinea->ID_MATERIAL
                                , ID_MATERIAL_FISICO = " . ($rowLinea->ID_MATERIAL_FISICO == NULL ? "NULL" : $rowLinea->ID_MATERIAL_FISICO) . "
                                , ID_UBICACION = $rowUbi->ID_UBICACION
                                , CANTIDAD = $rowLinea->CANTIDAD
                                , ID_TIPO_BLOQUEO_INICIAL = " . ($idTipoBloqueoOriginal == NULL ? "NULL" : $idTipoBloqueoOriginal) . "
                                , ID_TIPO_BLOQUEO_FINAL = " . ($idTipoBloqueoFinal == NULL ? "NULL" : $idTipoBloqueoFinal) . "
                                , ID_ORDEN_TRABAJO_MOVIMIENTO = " . ($rowLinea->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? "NULL" : $rowLinea->ID_ORDEN_TRABAJO_MOVIMIENTO) . "
                                , ID_INCIDENCIA_CALIDAD = " . ($rowLinea->ID_INCIDENCIA_CALIDAD == NULL ? "NULL" : $rowLinea->ID_INCIDENCIA_CALIDAD) . "
                                , OBSERVACIONES = ''";
                $bd->ExecSQL($sqlInsert);
                $idCambioEstado = $bd->IdAsignado();

                //BUSCO MATERIAL_UBICACION ORIGEN
                $clausulaWhere                    = "ID_MATERIAL = $rowLinea->ID_MATERIAL AND ID_UBICACION = $rowUbi->ID_UBICACION AND ID_MATERIAL_FISICO " . ($rowLinea->ID_MATERIAL_FISICO == NULL ? "IS NULL" : "= $rowLinea->ID_MATERIAL_FISICO") . " AND ID_TIPO_BLOQUEO " . ($idTipoBloqueoOriginal == NULL ? "IS NULL" : "= $idTipoBloqueoOriginal") . " AND ID_ORDEN_TRABAJO_MOVIMIENTO " . ($rowLinea->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? "IS NULL" : "= $rowLinea->ID_ORDEN_TRABAJO_MOVIMIENTO") . " AND ID_INCIDENCIA_CALIDAD " . ($rowLinea->ID_INCIDENCIA_CALIDAD == NULL ? "IS NULL" : "= $rowLinea->ID_INCIDENCIA_CALIDAD");
                $GLOBALS["NotificaErrorPorEmail"] = "No";
                $rowMatUbiOrigen                  = $bd->VerRegRest("MATERIAL_UBICACION", $clausulaWhere, "No");
                unset($GLOBALS["NotificaErrorPorEmail"]);

                //COMPRUEBO QUE HAYA STOCK SUFICIENTE EN ORIGEN
                if ($rowMatUbiOrigen->STOCK_TOTAL < $rowLinea->CANTIDAD):
                    $hayErrorCambioEstadoExpedicionSAP = true;
                    $strError                          = $strError . $auxiliar->traduce("No hay stock suficiente en la ubicacion de origen para realizar el cambio de estado", $administrador->ID_IDIOMA) . ".<br>";
                endif;

                //DECREMENTO MATERIAL_UBICACION ORIGEN
                $sqlUpdate = "UPDATE MATERIAL_UBICACION SET
                                STOCK_TOTAL = STOCK_TOTAL - $rowLinea->CANTIDAD
                                , STOCK_OK = STOCK_OK - " . ($idTipoBloqueoOriginal == NULL ? $rowLinea->CANTIDAD : 0) . "
                                , STOCK_BLOQUEADO = STOCK_BLOQUEADO - " . ($idTipoBloqueoOriginal == NULL ? 0 : $rowLinea->CANTIDAD) . "
                                WHERE ID_MATERIAL_UBICACION = $rowMatUbiOrigen->ID_MATERIAL_UBICACION";
                $bd->ExecSQL($sqlUpdate);

                //BUSCO MATERIAL_UBICACION DESTINO
                $clausulaWhere                    = "ID_MATERIAL = $rowLinea->ID_MATERIAL AND ID_UBICACION = $rowUbi->ID_UBICACION AND ID_MATERIAL_FISICO " . ($rowLinea->ID_MATERIAL_FISICO == NULL ? "IS NULL" : "= $rowLinea->ID_MATERIAL_FISICO") . " AND ID_TIPO_BLOQUEO " . ($idTipoBloqueoFinal == NULL ? "IS NULL" : "= $idTipoBloqueoFinal") . " AND ID_ORDEN_TRABAJO_MOVIMIENTO " . ($rowLinea->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? "IS NULL" : "= $rowLinea->ID_ORDEN_TRABAJO_MOVIMIENTO") . " AND ID_INCIDENCIA_CALIDAD " . ($rowLinea->ID_INCIDENCIA_CALIDAD == NULL ? "IS NULL" : "= $rowLinea->ID_INCIDENCIA_CALIDAD");
                $GLOBALS["NotificaErrorPorEmail"] = "No";
                $rowMatUbiDestino                 = $bd->VerRegRest("MATERIAL_UBICACION", $clausulaWhere, "No");
                unset($GLOBALS["NotificaErrorPorEmail"]);
                if ($rowMatUbiDestino == false):
                    //CREO MATERIAL UBICACION DESTINO
                    $sqlInsert = "INSERT INTO MATERIAL_UBICACION SET
                                    ID_MATERIAL = $rowLinea->ID_MATERIAL
                                    , ID_UBICACION = $rowUbi->ID_UBICACION
                                    , ID_MATERIAL_FISICO = " . ($rowLinea->ID_MATERIAL_FISICO == NULL ? "NULL" : $rowLinea->ID_MATERIAL_FISICO) . "
                                    , ID_TIPO_BLOQUEO = " . ($idTipoBloqueoFinal == NULL ? "NULL" : $idTipoBloqueoFinal) . "
                                    , ID_ORDEN_TRABAJO_MOVIMIENTO = " . ($rowLinea->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? "NULL" : $rowLinea->ID_ORDEN_TRABAJO_MOVIMIENTO) . "
                                    , ID_INCIDENCIA_CALIDAD = " . ($rowLinea->ID_INCIDENCIA_CALIDAD == NULL ? "NULL" : $rowLinea->ID_INCIDENCIA_CALIDAD);
                    $bd->ExecSQL($sqlInsert);
                    $idMatUbiDestino = $bd->IdAsignado();
                else:
                    $idMatUbiDestino = $rowMatUbiDestino->ID_MATERIAL_UBICACION;
                endif;

                //INCREMENTO MATERIAL_UBICACION DESTINO
                $sqlUpdate = "UPDATE MATERIAL_UBICACION SET
                                STOCK_TOTAL = STOCK_TOTAL + $rowLinea->CANTIDAD
                                , STOCK_OK = STOCK_OK + " . ($idTipoBloqueoFinal == NULL ? $rowLinea->CANTIDAD : 0) . "
                                , STOCK_BLOQUEADO = STOCK_BLOQUEADO + " . ($idTipoBloqueoFinal == NULL ? 0 : $rowLinea->CANTIDAD) . "
                                WHERE ID_MATERIAL_UBICACION = $idMatUbiDestino";
                $bd->ExecSQL($sqlUpdate);

                // LOG MOVIMIENTOS
                $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Creación", "Cambio estado", $idCambioEstado, "");
            endif;
            //FIN SI NO HAY ERRORES PROCEDO A GENERAR LOS CAMBIOS DE ESTADO CORRESPONDIENTES
        endwhile;
        //FIN RECORRO LA SELECCION DE LINEAS DE LA EXPEDICION SAP


        //VARAIBLE PARA SABER SI ES NECESARIO LLAMAR A SAP
        $bloqueosIgualesParaSAP = true;

        //TIPO BLOQUEO ORIGINAL
        if ($idTipoBloqueoOriginal != NULL):
            $rowTipoBloqueoOriginal = $bd->VerReg("TIPO_BLOQUEO", "ID_TIPO_BLOQUEO", $idTipoBloqueoOriginal);
        endif;

        //TIPO BLOQUEO FINAL
        if ($idTipoBloqueoFinal != NULL):
            $rowTipoBloqueoFinal = $bd->VerReg("TIPO_BLOQUEO", "ID_TIPO_BLOQUEO", $idTipoBloqueoFinal);
        endif;

        //CALCULO SI ES NECESARIO TRANSMITIR EL CAMBIO A SAP
        if (
            (($idTipoBloqueoOriginal == NULL) && ($idTipoBloqueoFinal != NULL)) ||
            (($idTipoBloqueoOriginal != NULL) && ($idTipoBloqueoFinal == NULL)) ||
            ($rowTipoBloqueoOriginal->TIPO_BLOQUEO_SAP != $rowTipoBloqueoFinal->TIPO_BLOQUEO_SAP)
        ):
            $bloqueosIgualesParaSAP = false;
        endif;

        //SI LOS BLOQUEOS SON DIFERENTES PARA SAP Y AUN NO SE HAN PRODUCIDO ERRORES, HAGO LA LLAMADA
        if (($bloqueosIgualesParaSAP == false) && ($hayErrorCambioEstadoExpedicionSAP == false)):

            //BUSCO EL TIPO DE BLOQUEO PREVENTIVO
            $rowTipoBloqueoPreventivo = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "SP");

            //EN FUNCION DE LOS TIPOS DE BLOQUEO MANDARE UN TIPO CAMBIO ESTADO U OTRO
            if (($idTipoBloqueoOriginal == NULL) && ($idTipoBloqueoFinal == $rowTipoBloqueoPreventivo->ID_TIPO_BLOQUEO)):
                $tipoCambioEstado = 'LibrePreventivo';
            elseif (($idTipoBloqueoOriginal == $rowTipoBloqueoPreventivo->ID_TIPO_BLOQUEO) && ($idTipoBloqueoFinal == NULL)):
                $tipoCambioEstado = 'PreventivoLibre';
            else:
                $html->PagError("TiposBloqueoNoValidos");
            endif;

            //ENVIO A SAP EL CAMBIO DE ESTADO GRUPO
            $resultado = $sap->AjusteCambioEstadoGrupo($idCambioEstadoGrupo, $tipoCambioEstado);
            //$resultado['RESULTADO'] = 'Error'; //PARA SIMULAR ERROR SAP
            if ($resultado['RESULTADO'] != 'OK'): //No se ha podido grabar la información en SAP
                //ACTUALIZO LA VARIABLE ERROR EN EL CAMBIO ESTADO
                $hayErrorCambioEstadoExpedicionSAP = true;

                //RECUPERO LOS ERRORES
                foreach ($resultado['ERRORES'] as $arr):
                    foreach ($arr as $mensaje_error):
                        $strError = $strError . $mensaje_error . "<br>";
                    endforeach;
                endforeach;
            endif;
        endif;
        //FIN SI LOS BLOQUEOS SON DIFERENTES PARA SAP Y AUN NO SE HAN PRODUCIDO ERRORES, HAGO LA LLAMADA

        //DECLARO UN ARRAY PARA DEVOLVER DATOS
        $arrDevuelto                                       = array();
        $arrDevuelto['errores']                            = $strError;
        $arrDevuelto['error_cambio_estado_expedidion_SAP'] = $hayErrorCambioEstadoExpedicionSAP;
        $arrDevuelto['idCambioEstadoGrupo']                = $idCambioEstadoGrupo;
        $arrDevuelto['resultado']                          = $resultado;

        //DEVUELVO EL ARRAY
        return $arrDevuelto;
    }

    /**
     * @param $idCambioEstadoGrupo ES EL CAMBIO ESTADO GRUPO A REVERTIR PORQUE LA SEGUNDA LLAMADA A SAP HA FALLADO
     * @return array
     * FUNCION UTILIZADA PARA REVERTIR UN CAMBIO DE ESTADO GRUPO
     */
    function revertir_cambio_estado_grupo($idCambioEstadoGrupoPendienteRevertir)
    {
        global $bd;
        global $html;
        global $auxiliar;
        global $administrador;
        global $sap;

        //RECUPERO LA VARIABLE GLOBAL DE TEXTO ERROR
        global $strError;
        $strError = "";

        //DECLARO LOS TIPO BLOQUEO
        $idTipoBloqueoOriginal = NULL;
        $idTipoBloqueoFinal    = NULL;

        //DECLARO UNA FECHA PARA QUE TODOS LOS CAMBIOS DE ESTADO Y EL DE GRUPO VAYAN CON LA MISMA
        $fechaHora = date("Y-m-d H:i:s");

        //BUSCO EL CAMBIO DE ESTADO GRUPO
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowCambioEstadoGrupo             = $bd->VerReg("CAMBIO_ESTADO_GRUPO", "ID_CAMBIO_ESTADO_GRUPO", $idCambioEstadoGrupoPendienteRevertir, "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);
        $html->PagErrorCondicionado($rowCambioEstadoGrupo, "==", false, "CambioEstadoGrupoNoExiste");

        //COMPRUEBO QUE EL CAMBIO ESTADO GRUPO ESTE PENDIENTE DE REVERTIR
        $html->PagErrorCondicionado($rowCambioEstadoGrupo->PENDIENTE_REVERTIR, "!=", 1, "CambioEstadoGrupoNoPendienteRevertir");

        //VARIABLE PARA SABER SI SE PRODUCE UN ERROR EN LA TRANSMISION DEL CAMBIO DE ESTADO DE LOS MATERIALES DE LA EXPEDICION SAP
        $hayErrorCambioEstadoExpedicionSAP = false;

        //VARIABLE PARA SABER SI SE PRODUCE UN ERROR SAP EN LA TRANSMISION DEL CAMBIO DE ESTADO
        $hayErrorTransmisionCambioEstadoExpedicionSAP = false;

        //BUSCO LOS CAMBIOS DE ESTADO AGRUPADOS BAJO EL MISMO CAMBIO ESTADO GRUPO
        $sqlLineas    = "SELECT *
                        FROM CAMBIO_ESTADO
                        WHERE ID_CAMBIO_ESTADO_GRUPO = $rowCambioEstadoGrupo->ID_CAMBIO_ESTADO_GRUPO";
        $resultLineas = $bd->ExecSQL($sqlLineas);

        //SI HAY ALGUNA LINEA, GENERO UN CAMBIO DE ESTADO GRUPO INVERSO AL QUE HAY QUE REVERTIR
        if ($bd->NumRegs($resultLineas) > 0):
            //GENERO EL CAMBIO DE ESTADO GRUPO
            $sqlInsert = "INSERT INTO CAMBIO_ESTADO_GRUPO SET
                            FECHA = '" . $fechaHora . "'
                            , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                            , NOMBRE = 'D+" . $rowCambioEstadoGrupo->ID_EXPEDICION . "'
                            , PENDIENTE_REVERTIR = 0
                            , ID_EXPEDICION = $rowCambioEstadoGrupo->ID_EXPEDICION";
            $bd->ExecSQL($sqlInsert);//exit($sqlInsert);
            $idCambioEstadoGrupo = $bd->IdAsignado();

            //ACTUALIZO EL NOMBRE DEL CAMBIO DE ESTADO GRUPO GENERADO
            $sqlUpdate = "UPDATE CAMBIO_ESTADO_GRUPO SET
                            NOMBRE = 'D" . $idCambioEstadoGrupo . "'
                            WHERE ID_CAMBIO_ESTADO_GRUPO = $idCambioEstadoGrupo";
            $bd->ExecSQL($sqlUpdate);
        endif;

        //RECORRO LOS CAMBIOS DE ESTADO
        while ($rowCambioEstado = $bd->SigReg($resultLineas)):
            //GENERO UN CAMBIO DE ESTADO INVERSO AL QUE REVERTIR
            $sqlInsert = "INSERT INTO CAMBIO_ESTADO SET
                            ID_CAMBIO_ESTADO_GRUPO = $idCambioEstadoGrupo
                            , FECHA = '" . $fechaHora . "'
                            , TIPO_CAMBIO_ESTADO = 'Automatico'
                            , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                            , ID_MATERIAL = $rowCambioEstado->ID_MATERIAL
                            , ID_MATERIAL_FISICO = " . ($rowCambioEstado->ID_MATERIAL_FISICO == NULL ? "NULL" : $rowCambioEstado->ID_MATERIAL_FISICO) . "
                            , ID_UBICACION = $rowCambioEstado->ID_UBICACION
                            , CANTIDAD = $rowCambioEstado->CANTIDAD
                            , ID_TIPO_BLOQUEO_INICIAL = " . ($rowCambioEstado->ID_TIPO_BLOQUEO_FINAL == NULL ? "NULL" : $rowCambioEstado->ID_TIPO_BLOQUEO_FINAL) . "
                            , ID_TIPO_BLOQUEO_FINAL = " . ($rowCambioEstado->ID_TIPO_BLOQUEO_INICIAL == NULL ? "NULL" : $rowCambioEstado->ID_TIPO_BLOQUEO_INICIAL) . "
                            , ID_ORDEN_TRABAJO_MOVIMIENTO = " . ($rowCambioEstado->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? "NULL" : $rowCambioEstado->ID_ORDEN_TRABAJO_MOVIMIENTO) . "
                            , ID_INCIDENCIA_CALIDAD = " . ($rowCambioEstado->ID_INCIDENCIA_CALIDAD == NULL ? "NULL" : $rowCambioEstado->ID_INCIDENCIA_CALIDAD) . "
                            , OBSERVACIONES = ''";
            $bd->ExecSQL($sqlInsert);
            $idCambioEstado = $bd->IdAsignado();

            //ACTUALIZO LOS TIPOS DE BLOQUEO ORIGINAL Y FINAL
            $idTipoBloqueoOriginal = $rowCambioEstado->ID_TIPO_BLOQUEO_FINAL;
            $idTipoBloqueoFinal    = $rowCambioEstado->ID_TIPO_BLOQUEO_INICIAL;

            //BUSCO MATERIAL_UBICACION ORIGEN
            $clausulaWhere                    = "ID_MATERIAL = $rowCambioEstado->ID_MATERIAL AND ID_UBICACION = $rowCambioEstado->ID_UBICACION AND ID_MATERIAL_FISICO " . ($rowCambioEstado->ID_MATERIAL_FISICO == NULL ? "IS NULL" : "= $rowCambioEstado->ID_MATERIAL_FISICO") . " AND ID_TIPO_BLOQUEO " . ($rowCambioEstado->ID_TIPO_BLOQUEO_FINAL == NULL ? "IS NULL" : "= $rowCambioEstado->ID_TIPO_BLOQUEO_FINAL") . " AND ID_ORDEN_TRABAJO_MOVIMIENTO " . ($rowCambioEstado->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? "IS NULL" : "= $rowCambioEstado->ID_ORDEN_TRABAJO_MOVIMIENTO") . " AND ID_INCIDENCIA_CALIDAD " . ($rowCambioEstado->ID_INCIDENCIA_CALIDAD == NULL ? "IS NULL" : "= $rowCambioEstado->ID_INCIDENCIA_CALIDAD");
            $GLOBALS["NotificaErrorPorEmail"] = "No";
            $rowMatUbiOrigen                  = $bd->VerRegRest("MATERIAL_UBICACION", $clausulaWhere, "No");
            unset($GLOBALS["NotificaErrorPorEmail"]);

            //COMPRUEBO QUE HAYA STOCK SUFICIENTE EN ORIGEN
            if ($rowMatUbiOrigen->STOCK_TOTAL < $rowCambioEstado->CANTIDAD):
                $hayErrorCambioEstadoExpedicionSAP = true;
                $strError                          = $strError . $auxiliar->traduce("No hay stock suficiente en la ubicacion de origen para realizar el cambio de estado", $administrador->ID_IDIOMA) . ".<br>";
            endif;

            //SI NO HAY ERRORES SIGO EJECUTANDO EL CODIGO
            if ($hayErrorCambioEstadoExpedicionSAP == false):
                //DECREMENTO MATERIAL_UBICACION ORIGEN
                $sqlUpdate = "UPDATE MATERIAL_UBICACION SET
                                STOCK_TOTAL = STOCK_TOTAL - $rowCambioEstado->CANTIDAD
                                , STOCK_OK = STOCK_OK - " . ($rowCambioEstado->ID_TIPO_BLOQUEO_FINAL == NULL ? $rowCambioEstado->CANTIDAD : 0) . "
                                , STOCK_BLOQUEADO = STOCK_BLOQUEADO - " . ($rowCambioEstado->ID_TIPO_BLOQUEO_FINAL == NULL ? 0 : $rowCambioEstado->CANTIDAD) . "
                                WHERE ID_MATERIAL_UBICACION = $rowMatUbiOrigen->ID_MATERIAL_UBICACION";
                $bd->ExecSQL($sqlUpdate);

                //BUSCO MATERIAL_UBICACION DESTINO
                $clausulaWhere                    = "ID_MATERIAL = $rowCambioEstado->ID_MATERIAL AND ID_UBICACION = $rowCambioEstado->ID_UBICACION AND ID_MATERIAL_FISICO " . ($rowCambioEstado->ID_MATERIAL_FISICO == NULL ? "IS NULL" : "= $rowCambioEstado->ID_MATERIAL_FISICO") . " AND ID_TIPO_BLOQUEO " . ($rowCambioEstado->ID_TIPO_BLOQUEO_INICIAL == NULL ? "IS NULL" : "= $rowCambioEstado->ID_TIPO_BLOQUEO_INICIAL") . " AND ID_ORDEN_TRABAJO_MOVIMIENTO " . ($rowCambioEstado->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? "IS NULL" : "= $rowCambioEstado->ID_ORDEN_TRABAJO_MOVIMIENTO") . " AND ID_INCIDENCIA_CALIDAD " . ($rowCambioEstado->ID_INCIDENCIA_CALIDAD == NULL ? "IS NULL" : "= $rowCambioEstado->ID_INCIDENCIA_CALIDAD");
                $GLOBALS["NotificaErrorPorEmail"] = "No";
                $rowMatUbiDestino                 = $bd->VerRegRest("MATERIAL_UBICACION", $clausulaWhere, "No");
                unset($GLOBALS["NotificaErrorPorEmail"]);
                if ($rowMatUbiDestino == false):
                    //CREO MATERIAL UBICACION DESTINO
                    $sqlInsert = "INSERT INTO MATERIAL_UBICACION SET
                                        ID_MATERIAL = $rowCambioEstado->ID_MATERIAL
                                        , ID_UBICACION = $rowCambioEstado->ID_UBICACION
                                        , ID_MATERIAL_FISICO = " . ($rowCambioEstado->ID_MATERIAL_FISICO == NULL ? "NULL" : $rowCambioEstado->ID_MATERIAL_FISICO) . "
                                        , ID_TIPO_BLOQUEO = " . ($rowCambioEstado->ID_TIPO_BLOQUEO_INICIAL == NULL ? "NULL" : $rowCambioEstado->ID_TIPO_BLOQUEO_INICIAL) . "
                                        , ID_ORDEN_TRABAJO_MOVIMIENTO = " . ($rowCambioEstado->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? "NULL" : $rowCambioEstado->ID_ORDEN_TRABAJO_MOVIMIENTO) . "
                                        , ID_INCIDENCIA_CALIDAD = " . ($rowCambioEstado->ID_INCIDENCIA_CALIDAD == NULL ? "NULL" : $rowCambioEstado->ID_INCIDENCIA_CALIDAD);
                    $bd->ExecSQL($sqlInsert);
                    $idMatUbiDestino = $bd->IdAsignado();
                else:
                    $idMatUbiDestino = $rowMatUbiDestino->ID_MATERIAL_UBICACION;
                endif;

                //INCREMENTO MATERIAL_UBICACION DESTINO
                $sqlUpdate = "UPDATE MATERIAL_UBICACION SET
                                STOCK_TOTAL = STOCK_TOTAL + $rowCambioEstado->CANTIDAD
                                , STOCK_OK = STOCK_OK + " . ($rowCambioEstado->ID_TIPO_BLOQUEO_INICIAL == NULL ? $rowCambioEstado->CANTIDAD : 0) . "
                                , STOCK_BLOQUEADO = STOCK_BLOQUEADO + " . ($rowCambioEstado->ID_TIPO_BLOQUEO_INICIAL == NULL ? 0 : $rowCambioEstado->CANTIDAD) . "
                                WHERE ID_MATERIAL_UBICACION = $idMatUbiDestino";
                $bd->ExecSQL($sqlUpdate);

                // LOG MOVIMIENTOS
                $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Creación", "Cambio estado", $idCambioEstado, "");


                //ACTUALIZO EL TIPO BLOQUEO DE LAS LINEAS DE MOVIMIENTO SALIDA POR EXPEDICION SAP
                $sqlUpdate = "UPDATE MOVIMIENTO_SALIDA_LINEA SET
                                ID_TIPO_BLOQUEO = " . ($rowCambioEstado->ID_TIPO_BLOQUEO_INICIAL == NULL ? "NULL" : $rowCambioEstado->ID_TIPO_BLOQUEO_INICIAL) . "
                                WHERE EXPEDICION_SAP = '" . $rowCambioEstadoGrupo->EXPEDICION_SAP . "' AND LINEA_ANULADA = 0 AND BAJA = 0";
                $bd->ExecSQL($sqlUpdate);
            endif;
            //FIN SI NO HAY ERRORES SIGO EJECUTANDO EL CODIGO
        endwhile;
        //FIN RECORRO LOS CAMBIOS DE ESTADO

        //MARCO EL CAMBIO DE ESTADO ORIGINAL COMO REVERTIDO
        $sqlUpdate = "UPDATE CAMBIO_ESTADO_GRUPO SET
                        PENDIENTE_REVERTIR = 0
                        WHERE ID_CAMBIO_ESTADO_GRUPO = $rowCambioEstadoGrupo->ID_CAMBIO_ESTADO_GRUPO";
        $bd->ExecSQL($sqlUpdate);


        //VARAIBLE PARA SABER SI ES NECESARIO LLAMAR A SAP
        $bloqueosIgualesParaSAP = true;

        //TIPO BLOQUEO ORIGINAL
        if ($idTipoBloqueoOriginal != NULL):
            $rowTipoBloqueoOriginal = $bd->VerReg("TIPO_BLOQUEO", "ID_TIPO_BLOQUEO", $idTipoBloqueoOriginal);
        endif;

        //TIPO BLOQUEO FINAL
        if ($idTipoBloqueoFinal != NULL):
            $rowTipoBloqueoFinal = $bd->VerReg("TIPO_BLOQUEO", "ID_TIPO_BLOQUEO", $idTipoBloqueoFinal);
        endif;

        //CALCULO SI ES NECESARIO TRANSMITIR EL CAMBIO A SAP
        if (
            (($idTipoBloqueoOriginal == NULL) && ($idTipoBloqueoFinal != NULL)) ||
            (($idTipoBloqueoOriginal != NULL) && ($idTipoBloqueoFinal == NULL)) ||
            ($rowTipoBloqueoOriginal->TIPO_BLOQUEO_SAP != $rowTipoBloqueoFinal->TIPO_BLOQUEO_SAP)
        ):
            $bloqueosIgualesParaSAP = false;
        endif;

        //SI LOS BLOQUEOS SON DIFERENTES PARA SAP Y AUN NO SE HAN PRODUCIDO ERRORES, HAGO LA LLAMADA
        if (($bloqueosIgualesParaSAP == false) && ($hayErrorCambioEstadoExpedicionSAP == false)):

            //BUSCO EL TIPO DE BLOQUEO PREVENTIVO
            $rowTipoBloqueoPreventivo = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "SP");

            //EN FUNCION DE LOS TIPOS DE BLOQUEO MANDARE UN TIPO CAMBIO ESTADO U OTRO
            if (($idTipoBloqueoOriginal == NULL) && ($rowTipoBloqueoFinal->ID_TIPO_BLOQUEO == $rowTipoBloqueoPreventivo->ID_TIPO_BLOQUEO)):
                $tipoCambioEstado = 'LibrePreventivo';
            elseif (($rowTipoBloqueoOriginal->ID_TIPO_BLOQUEO == $rowTipoBloqueoPreventivo->ID_TIPO_BLOQUEO) && ($idTipoBloqueoFinal == NULL)):
                $tipoCambioEstado = 'PreventivoLibre';
            else:
                $html->PagError("TiposBloqueoNoValidos");
            endif;

            //ENVIO A SAP EL CAMBIO DE ESTADO GRUPO
            $resultado = $sap->AjusteCambioEstadoGrupo($idCambioEstadoGrupo, $tipoCambioEstado);
            if ($resultado['RESULTADO'] != 'OK'): //No se ha podido grabar la información en SAP
                //ACTUALIZO LA VARIABLE ERROR EN EL CAMBIO ESTADO
                $hayErrorTransmisionCambioEstadoExpedicionSAP = true;

                //RECUPERO LOS ERRORES
                foreach ($resultado['ERRORES'] as $arr):
                    foreach ($arr as $mensaje_error):
                        $strError = $strError . $mensaje_error . "<br>";
                    endforeach;
                endforeach;
            endif;
        endif;
        //FIN SI LOS BLOQUEOS SON DIFERENTES PARA SAP Y AUN NO SE HAN PRODUCIDO ERRORES, HAGO LA LLAMADA

        //DECLARO UN ARRAY PARA DEVOLVER DATOS
        $arrDevuelto                                                   = array();
        $arrDevuelto['errores']                                        = $strError;
        $arrDevuelto['error_cambio_estado_expedidion_SAP']             = $hayErrorCambioEstadoExpedicionSAP;
        $arrDevuelto['error_transmision_cambio_estado_expedidion_SAP'] = $hayErrorTransmisionCambioEstadoExpedicionSAP;
        $arrDevuelto['idCambioEstadoGrupo']                            = $idCambioEstadoGrupo;
        $arrDevuelto['resultado']                                      = $resultado;

        //DEVUELVO EL ARRAY
        return $arrDevuelto;
    }

    /**
     * FUNCIO UTILLIZADA PARA RECEPCIONAR EN DESTINO UN ALBARAN DE UNA ORDEN DE TRANSPORTE DE TIPO GENERACION AUTOMATICA
     * @param $idAlbaran ALBARAN A RECEPCIONAR EN DESTINO
     */
    function RecepcionAutomaticaAlbaranOrdenTransporteAutomatica($idAlbaran)
    {
        global $bd;
        global $auxiliar;
        global $administrador;
        global $html;
        global $mat;
        global $movimiento;
        global $sap;

        //VARIABLES PARA CONTROLAR LOS ERRORES
        $hayError                    = false;
        $hayErrorRecepcionAlbaranSAP = false;
        global $strError;

        //VARIEBLE PARA GUARDAR LA MISMA FECHA Y HORA
        $fechaHora = date("Y-m-d H:i:s");

        //COMPRUEBO QUE EXISTA EL ALBARAN
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowAlbaran                       = $bd->VerReg("ALBARAN", "ID_ALBARAN", $idAlbaran, "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);
        if ($rowAlbaran == false):
            $hayError = true;
            $strError = $strError . $auxiliar->traduce("El albaran a recepcionar no existe", $administrador->ID_IDIOMA) . "<br>" . $auxiliar->traduce("Albaran", $administrador->ID_IDIOMA) . ": " . $idAlbaran . "<br>";
        endif;

        //MARCO EL ALBARAN COMO SIN GENERAR CONTEO
        $sqlUpdate = "UPDATE ALBARAN SET SIN_GENERAR_CONTEO = 1 WHERE ID_ALBARAN = $rowAlbaran->ID_ALBARAN";
        $bd->ExecSQL($sqlUpdate);

        //ESPECIFICO LA VIA DE RECEPCION A VALOR WEB
        $via_recepcion = "WEB";

        //BUSCO EL ALMACEN DESTINO
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowAlmacenDestino                = $bd->VerReg("ALMACEN", "ID_ALMACEN", $rowAlbaran->ID_ALMACEN_DESTINO, "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);
        if ($rowAlmacenDestino == false):
            $hayError = true;
            $strError = $strError . $auxiliar->traduce("El almacen destino del albaran no existe", $administrador->ID_IDIOMA) . "<br>" . $auxiliar->traduce("Albaran", $administrador->ID_IDIOMA) . ": " . $idAlbaran . "<br>";
        endif;

        //COMPRUEBO QUE TENGA PERMISOS DE ESCRITURA EN EL ALMACEN
        if ($administrador->comprobarAlmacenPermiso($rowAlmacenDestino->ID_ALMACEN, "Escritura") == false):
            $hayError = true;
            $strError = $strError . $auxiliar->traduce("No tiene permisos para realizar esta operación en esta subzona", $administrador->ID_IDIOMA) . "<br>" . $auxiliar->traduce("Albaran", $administrador->ID_IDIOMA) . ": " . $idAlbaran . "<br>";
        endif;

        //BUSCO EL CENTRO FISICO
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowCentroFisico                  = $bd->VerReg("CENTRO_FISICO", "ID_CENTRO_FISICO", $rowAlmacenDestino->ID_CENTRO_FISICO, "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);
        if ($rowCentroFisico == false):
            $hayError = true;
            $strError = $strError . $auxiliar->traduce("El centro fisico destino del albaran no existe", $administrador->ID_IDIOMA) . "<br>" . $auxiliar->traduce("Albaran", $administrador->ID_IDIOMA) . ": " . $idAlbaran . "<br>";
        endif;

        //BUSCO LA EXPEDICION
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowExpedicion                    = $bd->VerReg("EXPEDICION", "ID_EXPEDICION", $rowAlbaran->ID_EXPEDICION, "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);
        if ($rowExpedicion == false):
            $hayError = true;
            $strError = $strError . $auxiliar->traduce("La orden de transporte del albaran no existe", $administrador->ID_IDIOMA) . "<br>" . $auxiliar->traduce("Albaran", $administrador->ID_IDIOMA) . ": " . $idAlbaran . "<br>";
        endif;

        //BUSCO LA ORDEN DE TRANSPORTE
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowOTransporte                   = $bd->VerReg("ORDEN_TRANSPORTE", "ID_ORDEN_TRANSPORTE", $rowExpedicion->ID_ORDEN_TRANSPORTE, "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);

        //COMPRUEBO QUE LA EXPEDICION ESTE EN TRANSITO
        if ($rowExpedicion->ESTADO != 'En Tránsito'):
            $hayError = true;
            $strError = $strError . $auxiliar->traduce("La orden de transporte del albaran no esta en estado 'En Transito'", $administrador->ID_IDIOMA) . "<br>" . $auxiliar->traduce("Albaran", $administrador->ID_IDIOMA) . ": " . $idAlbaran . "<br>";
        endif;

        //SI NO SE HAN PRODUCIDO ERRORES EJECUTO ACCIONES
        if ($hayError == false):

            //CREO EL MOVIMIENTO DE RECEPCION
            $sqlInsert = "INSERT INTO MOVIMIENTO_RECEPCION SET
                            VIA_RECEPCION = '" . $via_recepcion . "'
                            , ID_ORDEN_TRANSPORTE = " . ($rowOTransporte ? $rowOTransporte->ID_ORDEN_TRANSPORTE : 'NULL') . "
                            , FECHA_PROCESADO = '" . $fechaHora . "'
                            , ID_TRANSPORTISTA = " . ($rowOTransporte->ID_AGENCIA == NULL ? 'NULL' : "$rowOTransporte->ID_AGENCIA") . "
                            , ID_TRANSPORTISTA_EFECTIVO = " . ($rowOTransporte->ID_TRANSPORTISTA == NULL ? 'NULL' : "$rowOTransporte->ID_TRANSPORTISTA") . "
                            , ID_CENTRO_FISICO = $rowCentroFisico->ID_CENTRO_FISICO
                            , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                            , ID_ADMINISTRADOR_CREADOR = $administrador->ID_ADMINISTRADOR
                            , TIPO_RECEPCION = 'PedidoTraslado'
                            , ESTADO = '" . ($via_recepcion == 'PDA' ? 'Procesado' : 'Ubicado') . "'
                            , FECHA = '" . date("Y-m-d") . "'
                            , HORA = '" . date(" H:i:s") . "'
                            , ALBARAN_TRANSPORTE = '" . $rowExpedicion->ALBARAN_TRANSPORTE . "'
                            , MATRICULA = '" . $rowExpedicion->MATRICULA . "'
                            , ALBARANES = 1";    //DE ESTE TIPO SIEMPRE SE GENERARA UNO Y SOLO UN ALBARAN (MOVIMIENTO DE ENTRADA)
            $bd->ExecSQL($sqlInsert);
            $idRecepcion = $bd->IdAsignado();

            //CREO EL MOVIMIENTO DE ENTRADA
            $sql = "INSERT INTO MOVIMIENTO_ENTRADA SET
                    TIPO_MOVIMIENTO = 'PedidoTraslado'
                    , FECHA_PROCESADO = '" . $fechaHora . "'
                    , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                    , ID_ADMINISTRADOR_CREADOR = $administrador->ID_ADMINISTRADOR
                    , ID_MOVIMIENTO_RECEPCION = $idRecepcion
                    , FECHA = '" . date("Y-m-d H:i:s") . "'
                    , FECHA_CONTABILIZACION = '" . date("Y-m-d") . "'
                    , ID_EXPEDICION = $rowExpedicion->ID_EXPEDICION
                    , PROPUESTA_IMPRESA = 1
                    , PEDIR_PEDIMENTO = '0'
                    , ESTADO = '" . ($via_recepcion == 'PDA' ? 'Procesado' : 'Ubicado') . "'";
            $bd->ExecSQL($sql);
            $idMovimiento = $bd->IdAsignado();

            //RECORRO LAS LINEAS DEL ALBARAN PARA REALIZAR LAS DIFERENTES OPERACIONES
            $sqlLineasAlbaran    = "SELECT *
                                    FROM ALBARAN_LINEA AL
                                    WHERE AL.ID_ALBARAN = $rowAlbaran->ID_ALBARAN AND AL.CANTIDAD_PENDIENTE_DE_RECEPCIONAR_EN_DESTINO > 0 AND AL.BAJA = 0 FOR UPDATE";
            $resultLineasAlbaran = $bd->ExecSQL($sqlLineasAlbaran);
            while ($rowAlbaranLinea = $bd->SigReg($resultLineasAlbaran)): //RECORRO LAS LINEAS DEL ALBARAN
                //BUSCO LAS LINEAS DE LOS MOVIMIENTOS DE SALIDA AGRUPADAS BAJO LA MISMA LINEA DE ALBARAN
                $sqlLineas    = "SELECT *
                                FROM MOVIMIENTO_SALIDA_LINEA MSL
                                WHERE MSL.ID_EXPEDICION = $rowExpedicion->ID_EXPEDICION AND MSL.ID_ALBARAN_LINEA = $rowAlbaranLinea->ID_ALBARAN_LINEA AND MSL.ESTADO = 'En Transito' AND ENVIADO_SAP = 1 AND LINEA_ANULADA = 0 AND BAJA = 0 FOR UPDATE";
                $resultLineas = $bd->ExecSQL($sqlLineas);
                while ($rowLinea = $bd->SigReg($resultLineas)): //RECORRO LAS LINEAS DE LOS MOVIMIENTOS DE SALIDA AGRUPADAS BAJO LA MISMA LINEA DE ALBARAN
                    //BUSCO EL MATERIAL
                    $rowMaterial = $bd->VerReg("MATERIAL", "ID_MATERIAL", $rowLinea->ID_MATERIAL);

                    //BUSCO EL ALMACEN DE ORIGEN
                    $rowAlmacenOrigen = $bd->VerReg("ALMACEN", "ID_ALMACEN", $rowLinea->ID_ALMACEN);

                    //BUSCO EL MATERIAL ALMACEN, SIEMPRE EXISTIRA
                    $GLOBALS["NotificaErrorPorEmail"] = "No";
                    $rowMatAlm                        = $bd->VerRegRest("MATERIAL_ALMACEN", "ID_MATERIAL = $rowLinea->ID_MATERIAL AND ID_ALMACEN = $rowAlmacenDestino->ID_ALMACEN", "No");
                    unset($GLOBALS["NotificaErrorPorEmail"]);

                    //COMPRUEBO QUE EL TIPO LOTE EN ORIGEN SEA IGUAL AL TIPO LOTE EN DESTINO
                    if ($rowMatAlm->TIPO_LOTE != $rowLinea->TIPO_LOTE):
                        $hayError = true;
                        $strError = $strError . $auxiliar->traduce("El tipo de lote en origen es diferente del tipo lote en destino", $administrador->ID_IDIOMA) . "<br>" . $auxiliar->traduce("Albaran", $administrador->ID_IDIOMA) . ": " . $idAlbaran . "<br>";
                    endif;

                    //BUSCO EL TIPO DE ETIQUETADO
                    if ($rowMatAlm->TIPO_LOTE == 'serie'):
                        $etiquetado = "Unitario";
                    else:
                        $etiquetado = "Total";
                    endif;

                    //BUSCO LA UBICACION DE ORIGEN DEL MATERIAL
                    $rowUbiOriginal = $bd->VerReg("UBICACION", "ID_UBICACION", $rowLinea->ID_UBICACION);

                    //BUSCO LA UBICACION EN EL ALMACEN DE DESTINO EN FUNCION DE LA UBICACION DE ORIGEN DEL MATERIAL
                    if ($rowUbiOriginal->TIPO_UBICACION != NULL):
                        $GLOBALS["NotificaErrorPorEmail"] = "No";
                        $rowUbicacionDestino              = $bd->VerRegRest("UBICACION", "ID_ALMACEN = $rowAlmacenDestino->ID_ALMACEN AND TIPO_UBICACION = '" . $rowUbiOriginal->TIPO_UBICACION . "' AND BAJA = 0", "No");
                        unset($GLOBALS["NotificaErrorPorEmail"]);
                        if ($rowUbicacionDestino == false):
                            $arrPartesUbicacion = explode("_", (string)$rowUbiOriginal->UBICACION);
                            $sqlInsert          = "INSERT INTO UBICACION SET
                                            UBICACION = '" . $arrPartesUbicacion[0] . "_" . $rowAlmacenDestino->REFERENCIA . "'
                                            , ID_ALMACEN = $rowAlmacenDestino->ID_ALMACEN
                                            , TIPO_UBICACION = '" . $rowUbiOriginal->TIPO_UBICACION . "'";
                            $bd->ExecSQL($sqlInsert);
                            $rowUbicacionDestino = $bd->IdAsignado();
                        endif;
                    elseif ($rowUbiOriginal->UBICACION == $rowAlmacenOrigen->REFERENCIA):
                        $GLOBALS["NotificaErrorPorEmail"] = "No";
                        $rowUbiDestino                    = $bd->VerRegRest("UBICACION", "ID_ALMACEN = $rowAlmacenDestino->ID_ALMACEN AND UBICACION = '" . $rowAlmacenDestino->REFERENCIA . "' AND BAJA = 0", "No");
                        unset($GLOBALS["NotificaErrorPorEmail"]);
                        if ($rowUbicacionDestino == false):
                            $arrPartesUbicacion = explode("_", (string)$rowUbiOriginal->UBICACION);
                            $sqlInsert          = "INSERT INTO UBICACION SET
                                            UBICACION = '" . $rowAlmacenOrigen->REFERENCIA . "'
                                            , ID_ALMACEN = $rowAlmacenDestino->ID_ALMACEN
                                            , TIPO_UBICACION = NULL";
                            $bd->ExecSQL($sqlInsert);
                            $rowUbicacionDestino = $bd->IdAsignado();
                        endif;
                    else:
                        $GLOBALS["NotificaErrorPorEmail"] = "No";
                        $rowUbicacionDestino              = $bd->VerRegRest("UBICACION", "ID_ALMACEN = $rowAlmacenDestino->ID_ALMACEN AND UBICACION = '" . $rowUbiOriginal->UBICACION . "' AND BAJA = 0", "No");
                        unset($GLOBALS["NotificaErrorPorEmail"]);
                        if ($rowUbicacionDestino == false):
                            $arrPartesUbicacion = explode("_", (string)$rowUbiOriginal->UBICACION);
                            $sqlInsert          = "INSERT INTO UBICACION SET
                                            UBICACION = '" . $rowUbiOriginal->UBICACION . "'
                                            , ID_ALMACEN = $rowAlmacenDestino->ID_ALMACEN
                                            , TIPO_UBICACION = NULL";
                            $bd->ExecSQL($sqlInsert);
                            $rowUbicacionDestino = $bd->IdAsignado();
                        endif;
                    endif;

                    //COMPRUEBO QUE EXISTA LA UBICACION DONDE DEPOSITAR EL MATERIAL DE LA LINEA
                    $GLOBALS["NotificaErrorPorEmail"] = "No";
                    $rowMatUbi                        = $bd->VerRegRest("MATERIAL_UBICACION", "ID_MATERIAL = $rowAlbaranLinea->ID_MATERIAL AND ID_UBICACION = $rowUbicacionDestino->ID_UBICACION AND ID_MATERIAL_FISICO " . ($rowAlbaranLinea->TIPO_LOTE == 'ninguno' ? 'IS NULL' : "= $rowAlbaranLinea->ID_MATERIAL_FISICO") . " AND ID_TIPO_BLOQUEO " . ($rowAlbaranLinea->ID_TIPO_BLOQUEO == NULL ? 'IS NULL' : "= $rowAlbaranLinea->ID_TIPO_BLOQUEO") . " AND ID_ORDEN_TRABAJO_MOVIMIENTO IS NULL AND ID_INCIDENCIA_CALIDAD IS NULL", "No");
                    unset($GLOBALS["NotificaErrorPorEmail"]);
                    if ($rowMatUbi == false):
                        $sqlInsert = "INSERT INTO MATERIAL_UBICACION SET
                                        ID_MATERIAL = $rowAlbaranLinea->ID_MATERIAL
                                        , ID_UBICACION = $rowUbicacionDestino->ID_UBICACION
                                        , ID_MATERIAL_FISICO = " . ($rowAlbaranLinea->TIPO_LOTE == 'ninguno' ? 'NULL' : "$rowAlbaranLinea->ID_MATERIAL_FISICO") . "
                                        , ID_TIPO_BLOQUEO = " . ($rowAlbaranLinea->ID_TIPO_BLOQUEO == NULL ? 'NULL' : "$rowAlbaranLinea->ID_TIPO_BLOQUEO") . "
                                        , ID_ORDEN_TRABAJO_MOVIMIENTO = NULL
                                        , ID_INCIDENCIA_CALIDAD = NULL";
                        $bd->execSQL($sqlInsert);
                        $idMatUbiDestino = $bd->IdAsignado();
                    else:
                        $idMatUbiDestino = $rowMatUbi->ID_MATERIAL_UBICACION;
                    endif;

                    //SI ES UN MATERIAL FISICO  SERIABLE COMPRUEBO QUE NO EXISTA EN EL SISTEMA NI ESTE EN TRANSITO
                    if ($rowAlbaranLinea->ID_MATERIAL_FISICO != NULL):
                        $GLOBALS["NotificaErrorPorEmail"] = "No";
                        $rowMatFis                        = $bd->VerReg("MATERIAL_FISICO", "ID_MATERIAL_FISICO", $rowAlbaranLinea->ID_MATERIAL_FISICO, "No");
                        unset($GLOBALS["NotificaErrorPorEmail"]);
                        if ($rowMatFis == false):
                            $hayError = true;
                            $strError = $strError . $auxiliar->traduce("El numero de serie/lote no existe", $administrador->ID_IDIOMA) . "<br>" . $auxiliar->traduce("Albaran", $administrador->ID_IDIOMA) . ": " . $idAlbaran . "<br>";
                        endif;
                        //$html->PagErrorCondicionado($rowMatFis, "==", false, "MaterialFisicoNoExiste");
                        if ($rowMatFis->TIPO_LOTE == 'serie'):    //SI ES UN MATERIAL SERIABLE
                            $numMaterialSeriable = $bd->NumRegsTabla("MATERIAL_UBICACION", "ID_MATERIAL_FISICO = $rowAlbaranLinea->ID_MATERIAL_FISICO AND ACTIVO = 1");
                            if ($numMaterialSeriable > 0):    //EXISTE EN EL SISTEMA
                                $hayError = true;
                                $strError = $strError . $auxiliar->traduce("Existe material seriable ya existente en el sistema", $administrador->ID_IDIOMA) . "<br>" . $auxiliar->traduce("Albaran", $administrador->ID_IDIOMA) . ": " . $idAlbaran . "<br>";
                            else:
                                $rowMovSalLinea = $bd->VerReg("MOVIMIENTO_SALIDA_LINEA", "ID_ALBARAN_LINEA", $rowAlbaranLinea->ID_ALBARAN_LINEA);
                                if ($mat->MaterialFisicoEnTransito($rowAlbaranLinea->ID_MATERIAL_FISICO, $rowLinea->ID_MOVIMIENTO_SALIDA_LINEA) == true):    //ESTA EN TRANSITO DENTRO DEL SISTEMA
                                    $hayError = true;
                                    $strError = $strError . $auxiliar->traduce("Existe material seriable en transito en el sistema", $administrador->ID_IDIOMA) . "<br>" . $auxiliar->traduce("Albaran", $administrador->ID_IDIOMA) . ": " . $idAlbaran . "<br>";
                                endif;
                            endif;
                        endif;    //FIN SI ES UN MATERIAL SERIABLE
                    endif;
                    //FIN SI ES UN MATERIAL FISICO  SERIABLE COMPRUEBO QUE NO EXISTA EN EL SISTEMA NI ESTE EN TRANSITO

                    //INCREMENTO LA CANTIDAD EN MATERIAL UBICACION ORIGEN
                    $sqlUpdate = "UPDATE MATERIAL_UBICACION SET
                                    STOCK_TOTAL = STOCK_TOTAL + $rowLinea->CANTIDAD_PENDIENTE_DE_RECEPCIONAR_EN_DESTINO
                                    , STOCK_OK = STOCK_OK + " . ($rowAlbaranLinea->ID_TIPO_BLOQUEO == NULL ? $rowLinea->CANTIDAD_PENDIENTE_DE_RECEPCIONAR_EN_DESTINO : 0) . "
                                    , STOCK_BLOQUEADO = STOCK_BLOQUEADO + " . ($rowAlbaranLinea->ID_TIPO_BLOQUEO == NULL ? 0 : $rowLinea->CANTIDAD_PENDIENTE_DE_RECEPCIONAR_EN_DESTINO) . "
                                    WHERE ID_MATERIAL_UBICACION = $idMatUbiDestino";
                    $bd->ExecSQL($sqlUpdate);

                    //GENERO LA LINEA DEL MOVIMIENTO DE ENTRADA
                    $sqlInsert = "INSERT INTO MOVIMIENTO_ENTRADA_LINEA SET
                                        ID_MOVIMIENTO_ENTRADA = $idMovimiento
                                        , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                        , FECHA = '" . $fechaHora . "'
                                        , ID_ALBARAN = $rowAlbaranLinea->ID_ALBARAN
                                        , ID_ALBARAN_LINEA = $rowAlbaranLinea->ID_ALBARAN_LINEA
                                        , ID_UBICACION = $rowUbicacionDestino->ID_UBICACION
                                        , ID_MATERIAL = $rowAlbaranLinea->ID_MATERIAL
                                        , ID_MATERIAL_FISICO = " . ($rowAlbaranLinea->TIPO_LOTE == 'ninguno' ? 'NULL' : "$rowAlbaranLinea->ID_MATERIAL_FISICO") . "
                                        , ID_TIPO_BLOQUEO = " . ($rowAlbaranLinea->ID_TIPO_BLOQUEO == NULL ? 'NULL' : "$rowAlbaranLinea->ID_TIPO_BLOQUEO") . "
                                        , ID_INCIDENCIA_CALIDAD = " . ($rowLinea->ID_INCIDENCIA_CALIDAD == NULL ? 'NULL' : "$rowLinea->ID_INCIDENCIA_CALIDAD") . "
                                        , CONTROL_CODIFICACION = " . ((($rowMat->DESCRIPCION_REVISADA == 'No') || ($rowMat->FOTO == 0)) ? 1 : 0) . "
                                        , LOTE_PROVEEDOR_OBLIGATORIO = " . ($rowMatAlm->TIPO_LOTE == 'ninguno' ? 0 : $rowMaterial->CON_LOTE_PROVEEDOR) . "
                                        , TIPO_PEDIDO = 'Traslado'
                                        , ESTADO = '" . ($via_recepcion == 'PDA' ? 'Creada' : 'Finalizada') . "'
                                        , TIPO_ETIQUETADO = '$etiquetado'
                                        , TIPO_LOTE = '" . $rowAlbaranLinea->TIPO_LOTE . "'
                                        , CANTIDAD = $rowLinea->CANTIDAD_PENDIENTE_DE_RECEPCIONAR_EN_DESTINO
                                        , PEDIMENTO = ''
                                        , PEDIMENTO_FECHA_PAGO = ''
                                        , PEDIMENTO_ADUANA = ''
                                        , PEDIMENTO_ID_ADUANA = ''
                                        , ENTREGA_FINAL_PEDIDO = 0";
                    $bd->ExecSQL($sqlInsert);

                    //ACTUALIZO LAS LINEAS DE MOVIMIENTO ASOCIADAS A ESTA LINEA DE ALBARAN
                    $sqlUpdate = "UPDATE MOVIMIENTO_SALIDA_LINEA SET
                                    CANTIDAD_PENDIENTE_DE_RECEPCIONAR_EN_DESTINO = CANTIDAD_PENDIENTE_DE_RECEPCIONAR_EN_DESTINO - $rowLinea->CANTIDAD_PENDIENTE_DE_RECEPCIONAR_EN_DESTINO
                                    , ESTADO = 'Recepcionado'
                                    WHERE ID_MOVIMIENTO_SALIDA_LINEA = $rowLinea->ID_MOVIMIENTO_SALIDA_LINEA";
                    $bd->ExecSQL($sqlUpdate);

                    //BUSCO LAS LINEAS DEL MOVIMIENTO DE SALIDA ASOCIADAS A ESTA LINEA DE ALBARAN PARA ACTUALIZAR LA CABECERA DEL MOVIMIENTO DE SALIDA
                    $sqlLineasMovimientoSalida    = "SELECT DISTINCT ID_MOVIMIENTO_SALIDA
                                                    FROM MOVIMIENTO_SALIDA_LINEA
                                                    WHERE ID_ALBARAN_LINEA = $rowAlbaranLinea->ID_ALBARAN_LINEA AND LINEA_ANULADA = 0 AND BAJA = 0";
                    $resultLineasMovimientoSalida = $bd->ExecSQL($sqlLineasMovimientoSalida);
                    while ($rowLineaMovimientoSalida = $bd->SigReg($resultLineasMovimientoSalida)):
                        //ACTUALIZO LA CABECERA DEL MOVIMIENTO
                        $movimiento->actualizarEstadoMovimientoSalida($rowLineaMovimientoSalida->ID_MOVIMIENTO_SALIDA);
                    endwhile;

                    //ACTUALIZO EL ESTADO DE LA ORDEN DE TRANSPORTE IMPLICADA/S
                    $sqlOrdenesTransporte    = "SELECT DISTINCT ID_EXPEDICION
                                                FROM MOVIMIENTO_SALIDA_LINEA MSL
                                                WHERE MSL.ID_ALBARAN_LINEA = $rowAlbaranLinea->ID_ALBARAN_LINEA AND LINEA_ANULADA = 0 AND BAJA = 0";
                    $resultOrdenesTransporte = $bd->ExecSQL($sqlOrdenesTransporte);
                    while ($rowOrdenTransporte = $bd->SigReg($resultOrdenesTransporte)):
                        $this->actualizar_estado_orden_transporte($rowOrdenTransporte->ID_EXPEDICION);
                    endwhile;

                endwhile; //FIN RECORRO LAS LINEAS DE LOS MOVIMIENTOS DE SALIDA AGRUPADAS BAJO LA MISMA LINEA DE ALBARAN

                //ACTUALIZO LA LINEA DE ALBARAN
                $sqlUpdate = "UPDATE ALBARAN_LINEA SET
                                CANTIDAD_PENDIENTE_DE_RECEPCIONAR_EN_DESTINO = 0
                                WHERE ID_ALBARAN_LINEA = $rowAlbaranLinea->ID_ALBARAN_LINEA";
                $bd->ExecSQL($sqlUpdate);

            endwhile; //FIN RECORRO LAS LINEAS DEL ALBARAN

            //ACTUALIZO EL MOVIMIENTO DE ENTRADA A UBICADO
            $sqlUpdate = "UPDATE MOVIMIENTO_ENTRADA SET ESTADO = '" . ($via_recepcion == 'PDA' ? 'Procesado' : 'Ubicado') . "' WHERE ID_MOVIMIENTO_ENTRADA = $idMovimiento";
            $bd->ExecSQL($sqlUpdate);

            //OBTENGO EL ESTADO FINAL DE LA RECEPCION TRAS LOS CAMBIOS
            $estadoFinalRecepcion = $movimiento->getEstadoRecepcion($idRecepcion);

            //ACTUALIZO EL ESTADO DE LA RECEPCION
            $sqlUpdate = "UPDATE MOVIMIENTO_RECEPCION SET ESTADO = '" . $estadoFinalRecepcion . "' WHERE ID_MOVIMIENTO_RECEPCION = $idRecepcion";
            $bd->ExecSQL($sqlUpdate);

            //BUSCO EL TIPO DE BLOQUEO PREVENTIVO
            $rowTipoBloqueoPreventivo = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "SP", "No");
            //BUSCO EL TIPO DE BLOQUEO RETENIDO CALIDAD NO PREVENTIVO
            $rowTipoBloqueoRetenidoCalidadNoPreventivo = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "XRC", "No");

            //ACTUALIZO LA FECHA PROCESADO DE LAS LINEAS DEL MOVIMIENTO A PROCESAR
            $sqlLineasProcesar    = "SELECT *
                                    FROM MOVIMIENTO_ENTRADA_LINEA
                                    WHERE ID_MOVIMIENTO_ENTRADA = '" . $idMovimiento . "' AND BAJA = 0 AND (ID_TIPO_BLOQUEO IS NULL OR ID_TIPO_BLOQUEO = $rowTipoBloqueoPreventivo->ID_TIPO_BLOQUEO OR ID_TIPO_BLOQUEO = $rowTipoBloqueoRetenidoCalidadNoPreventivo->ID_TIPO_BLOQUEO)";
            $resultLineasProcesar = $bd->ExecSQL($sqlLineasProcesar);
            while ($rowLineasProcesar = $bd->SigReg($resultLineasProcesar)):
                $sqlUpdate = "UPDATE MOVIMIENTO_ENTRADA_LINEA SET FECHA_PROCESADO = '" . $fechaHora . "' WHERE ID_MOVIMIENTO_ENTRADA_LINEA = $rowLineasProcesar->ID_MOVIMIENTO_ENTRADA_LINEA";
                $bd->ExecSQL($sqlUpdate);
            endwhile;
            //FIN ACTUALIZO LA FECHA PROCESADO DE LAS LINEAS DEL MOVIMIENTO A PROCESAR

            $numOK = $bd->NumRegsTabla("MOVIMIENTO_ENTRADA_LINEA", "ID_MOVIMIENTO_ENTRADA = $idMovimiento AND BAJA = 0 AND (ID_TIPO_BLOQUEO IS NULL OR ID_TIPO_BLOQUEO = $rowTipoBloqueoPreventivo->ID_TIPO_BLOQUEO OR ID_TIPO_BLOQUEO = $rowTipoBloqueoRetenidoCalidadNoPreventivo->ID_TIPO_BLOQUEO)");

            if (($numOK > 0) && ($hayError == false)):
                $resultado = $sap->RecepcionExpedicion($idMovimiento);
                if ($resultado['RESULTADO'] != 'OK'): //No se ha podido grabar la información en SAP
                    //ACTUALIZO LA VARIABLE ERROR EN LA RECEPCION DEL ALBARAN A TRUE
                    $hayErrorRecepcionAlbaranSAP = true;

                    //GRABO LA CABECERA DEL ERROR SAP
                    $strError = "<strong>" . $auxiliar->traduce("Se han producido los siguientes errores SAP al recepcionar el albaran", $administrador->ID_IDIOMA) . " " . $idAlbaran . ":</strong><br>";

                    foreach ($resultado['ERRORES'] as $arr):
                        foreach ($arr as $mensaje_error):
                            $strError = $strError . $mensaje_error . "<br>";
                        endforeach;
                    endforeach;
                endif;
            endif;
        endif;
        //FIN SI NO SE HAN PRODUCIDO ERRORES EJECUTO ACCIONES

        //DECLARO UN ARRAY PARA DEVOLVER DATOS
        $arrDevuelto                                = array();
        $arrDevuelto['errores']                     = $strError;
        $arrDevuelto['error_recepcion_albaran']     = $hayError;
        $arrDevuelto['error_recepcion_albaran_SAP'] = $hayErrorRecepcionAlbaranSAP;
        $arrDevuelto['resultado']                   = $resultado;

        //DEVUELVO EL ARRAY
        return $arrDevuelto;
    }

    /**
     * FUNCION UTILIZADA PARA REALIZAR LA RECEPCION AUTOMATICA DE ALBARANES EN ALMACENES DE TIPO CONSTRUCCION
     * @param $idAlbaran ALBARAN A RECEPCIONAR
     */
    function RecepcionAutomaticaAlbaranEnDestino($idAlbaran)
    {
        global $bd;
        global $auxiliar;
        global $administrador;
        global $html;
        global $mat;
        global $sap;
        global $movimiento;
        global $necesidad;

        //VARIABLES PARA CONTROLAR LOS ERRORES
        $hayError = false;
        global $strError;

        //COMPRUEBO QUE EXISTA EL ALBARAN
        $NotificaErrorPorEmail = "No";
        $rowAlbaran            = $bd->VerReg("ALBARAN", "ID_ALBARAN", $idAlbaran, "No");
        unset($NotificaErrorPorEmail);
        $html->PagErrorCondicionado($rowAlbaran, "==", false, "AlbaranNoExiste");

        //MARCO EL ALBARAN COMO SIN GENERAR CONTEO
        $sqlUpdate = "UPDATE ALBARAN SET SIN_GENERAR_CONTEO = 1 WHERE ID_ALBARAN = $idAlbaran";
        $bd->ExecSQL($sqlUpdate);

        //ESPECIFICO LA VIA DE RECEPCION A VALOR WEB
        $via_recepcion = "WEB";

        //BUSCO EL ALMACEN DESTINO
        $rowAlmacenDestino = $bd->VerReg("ALMACEN", "ID_ALMACEN", $rowAlbaran->ID_ALMACEN_DESTINO, "No");
        $html->PagErrorCondicionado($rowAlmacenDestino, "==", false, "AlmacenDestinoNoExiste");

        //BUSCO EL CENTRO DESTINO
        $rowCentroDestino = $bd->VerReg("CENTRO", "ID_CENTRO", $rowAlmacenDestino->ID_CENTRO, "No");
        //NO PUEDE TENER INTEGRACION CON SAP, YA QUE TRAS LA EXPEDICION SE MANDA LA ENTREGA ENTRANTE DESDE LA ACCION DE salidas/expediciones
        $html->PagErrorCondicionado($rowCentroDestino->INTEGRACION_CON_SAP, "==", 1, "ErrorFuncionRecepcionAutomaticaAlbaranEnDestinoLibreriaExpedicion");

        //COMPRUEBO QUE TENGA PERMISOS DE ESCRITURA EN EL ALMACEN
        $html->PagErrorCondicionado($administrador->comprobarAlmacenPermiso($rowAlmacenDestino->ID_ALMACEN, "Escritura"), "==", false, "SinPermisosSubzona");

        //BUSCO EL CENTRO FISICO
        $rowCentroFisico = $bd->VerReg("CENTRO_FISICO", "ID_CENTRO_FISICO", $rowAlmacenDestino->ID_CENTRO_FISICO, "No");
        $html->PagErrorCondicionado($rowCentroFisico, "==", false, "CentroFisicoNoExiste");

        //BUSCO LA EXPEDICION
        $rowExpedicion = $bd->VerReg("EXPEDICION", "ID_EXPEDICION", $rowAlbaran->ID_EXPEDICION, "No");
        $html->PagErrorCondicionado($rowExpedicion, "==", false, "ExpedicionNoExiste");

        //BUSCO LA ORDEN DE TRANSPORTE
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowOTransporte                   = $bd->VerReg("ORDEN_TRANSPORTE", "ID_ORDEN_TRANSPORTE", $rowExpedicion->ID_ORDEN_TRANSPORTE, "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);

        //COMPRUEBO QUE LA EXPEDICION ESTE EN TRANSITO
        $html->PagErrorCondicionado(($rowExpedicion->ESTADO == 'En Tránsito'), "==", false, "EstadoExpedicionIncorrecto");

        //CREO EL MOVIMIENTO DE RECEPCION
        $sqlInsert = "INSERT INTO MOVIMIENTO_RECEPCION SET
                        VIA_RECEPCION = '" . $via_recepcion . "'
                        , ID_ORDEN_TRANSPORTE = " . ($rowOTransporte ? $rowOTransporte->ID_ORDEN_TRANSPORTE : 'NULL') . "
                        , FECHA_PROCESADO = '" . date("Y-m-d H:i:s") . "'
                        , ID_TRANSPORTISTA = " . ($rowOTransporte->ID_AGENCIA == NULL ? 'NULL' : "$rowOTransporte->ID_AGENCIA") . "
                        , ID_TRANSPORTISTA_EFECTIVO = " . ($rowOTransporte->ID_TRANSPORTISTA == NULL ? 'NULL' : "$rowOTransporte->ID_TRANSPORTISTA") . "
                        , ID_CENTRO_FISICO = $rowCentroFisico->ID_CENTRO_FISICO
                        , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                        , ID_ADMINISTRADOR_CREADOR = $administrador->ID_ADMINISTRADOR
                        , TIPO_RECEPCION = 'PedidoTraslado'
                        , ESTADO = '" . ($via_recepcion == 'PDA' ? 'Procesado' : 'Ubicado') . "'
                        , FECHA = '" . date("Y-m-d") . "'
                        , HORA = '" . date(" H:i:s") . "'
                        , ALBARAN_TRANSPORTE = '" . $rowExpedicion->ALBARAN_TRANSPORTE . "'
                        , MATRICULA = '" . $rowExpedicion->MATRICULA . "'
                        , ALBARANES = 1";    //DE ESTE TIPO SIEMPRE SE GENERARA UNO Y SOLO UN ALBARAN (MOVIMIENTO DE ENTRADA)
        $bd->ExecSQL($sqlInsert);
        $idRecepcion = $bd->IdAsignado();

        //CREO EL MOVIMIENTO DE ENTRADA
        $sql = "INSERT INTO MOVIMIENTO_ENTRADA SET
                TIPO_MOVIMIENTO = 'PedidoTraslado'
                , FECHA_PROCESADO = '" . date("Y-m-d H:i:s") . "'
                , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                , ID_ADMINISTRADOR_CREADOR = $administrador->ID_ADMINISTRADOR
                , ID_MOVIMIENTO_RECEPCION = $idRecepcion
                , FECHA = '" . date("Y-m-d H:i:s") . "'
                , FECHA_CONTABILIZACION = '" . date("Y-m-d") . "'
                , ID_EXPEDICION = $rowExpedicion->ID_EXPEDICION
                , PROPUESTA_IMPRESA = 1
                , PEDIR_PEDIMENTO = '" . ($pedir_pedimento == '1' ? '1' : '0') . "'
                , ESTADO = '" . ($via_recepcion == 'PDA' ? 'Procesado' : 'Ubicado') . "'";
        $bd->ExecSQL($sql);
        $idMovimiento = $bd->IdAsignado();


        //SI EL ALBARAN TIENE ORDEN MONTAJE ASOCIADA, BUSCAMOS LA UBICACION DE TIPO MAQUINA
        if ($rowAlbaran->ID_ORDEN_MONTAJE != NULL):
            //BUSCO LA ORDEN DE MONTAJE
            $rowOrdenMontaje = $bd->VerReg("ORDEN_MONTAJE", "ID_ORDEN_MONTAJE", $rowAlbaran->ID_ORDEN_MONTAJE, "No");

            if ($rowAlbaran->TIPO_LISTA_OM == 'Utiles'):
                //BUSCAMOS LA UBCACION DE TIPO MAQUINA
                $GLOBALS["NotificaErrorPorEmail"] = "No";
                $rowUbicacionDestino              = $bd->VerRegRest("UBICACION", "ID_ALMACEN = $rowAlmacenDestino->ID_ALMACEN AND TIPO_UBICACION = 'Utiles' AND BAJA = 0", "No");
                $html->PagErrorCondicionado($rowUbicacionDestino, "==", false, 'UbicacionTipoUtilesEnDestinoNoExiste');
            else:
                //BUSCAMOS LA UBCACION DE TIPO MAQUINA
                $GLOBALS["NotificaErrorPorEmail"] = "No";
                $rowUbicacionDestino              = $bd->VerReg("UBICACION", "ID_UBICACION", $rowOrdenMontaje->ID_UBICACION_MAQUINA, "No");
                $html->PagErrorCondicionado($rowUbicacionDestino, "==", false, 'UbicacionTipoMaquinaEnDestinoNoExiste');
            endif;

        else://BUSCO LA UBICACION DE TIPO Construcción EN EL ALMACEN DE DESTINO
            $GLOBALS["NotificaErrorPorEmail"] = "No";
            $rowUbicacionDestino              = $bd->VerRegRest("UBICACION", "ID_ALMACEN = $rowAlmacenDestino->ID_ALMACEN AND TIPO_UBICACION = 'Construccion' AND BAJA = 0", "No");
            $html->PagErrorCondicionado($rowUbicacionDestino, "==", false, 'UbicacionTipoConstruccionEnDestinoNoExiste');
        endif;

        //ARRAY DE OBJETOS A ACTUALIZAR DESPUES DEL WHILE
        $arrMovimientosActualizar  = array();
        $arrPedidosLineaActualizar = array();

        /************************************ DESHABILITO TRIGGERS IMPLICADOS ************************************/
        $sqlDeshabilitarTriggers = "SET @Trigger_MOVIMIENTO_SALIDA_LINEA_UPDATE_Deshabilitado = 1";
        $bd->ExecSQL($sqlDeshabilitarTriggers);
        /********************************** FIN DESHABILITO TRIGGERS IMPLICADOS **********************************/

        //BUSCO LAS LINEAS DEL ALBARAN
        $sqlAlbaranLinea    = "SELECT *
                            FROM ALBARAN_LINEA AL
                            WHERE AL.ID_ALBARAN = $rowAlbaran->ID_ALBARAN AND AL.BAJA = 0";
        $resultAlbaranLinea = $bd->ExecSQL($sqlAlbaranLinea);
        //BUCLE LINEAS DE ALBARAN
        while ($rowAlbaranLinea = $bd->SigReg($resultAlbaranLinea)):
            //BUSCO EL MATERIAL
            $rowMat = $bd->VerReg("MATERIAL", "ID_MATERIAL", $rowAlbaranLinea->ID_MATERIAL, "No");

            //BUSCO EL ALMACEN
            $rowAlm = $bd->VerReg("ALMACEN", "ID_ALMACEN", $rowAlbaran->ID_ALMACEN_DESTINO, "No");

            //BUSCO EL MATERIAL ALMACEN
            $NotificaErrorPorEmail = "No";
            $rowMatAlm             = $bd->VerRegRest("MATERIAL_ALMACEN", "ID_MATERIAL = $rowMat->ID_MATERIAL AND ID_ALMACEN = $rowAlm->ID_ALMACEN", "No");
            unset($NotificaErrorPorEmail);
            if ($rowMatAlm == false):
                //BUSCO SI ESTE MATERIAL ESTA DEFINIDO PARA OTRO ALMACEN DE ESTE MISMO CENTRO
                $sqlMatAlmLinea    = "SELECT *
                                     FROM MATERIAL_ALMACEN
                                     WHERE BAJA = 0 AND ID_MATERIAL = $rowMat->ID_MATERIAL AND ID_ALMACEN IN (SELECT ID_ALMACEN
                                                                                                                FROM ALMACEN
                                                                                                                WHERE ID_CENTRO = $rowAlm->ID_CENTRO)";
                $resultMatAlmLinea = $bd->ExecSQL($sqlMatAlmLinea);
                if ($bd->NumRegs($resultMatAlmLinea) > 0):
                    $rowMatAlm = $bd->SigReg($resultMatAlmLinea);

                    //GENERAMOS UNA COPIA DEL MATERIAL_ALMACEN ENCONTRADO
                    $sqlInsert = "INSERT INTO MATERIAL_ALMACEN SET
                                    ID_MATERIAL = $rowMat->ID_MATERIAL
                                    , ID_ALMACEN = $rowAlm->ID_ALMACEN
                                    , TIPO_LOTE = '" . $rowMatAlm->TIPO_LOTE . "'
                                    , ESTADO_BLOQUEO_MATERIAL_ALMACEN = '" . $rowMatAlm->ESTADO_BLOQUEO_MATERIAL_ALMACEN . "'
                                    , BAJA = $rowMatAlm->BAJA
                                    , FECHA_CREACION = '" . date('Y-m-d H:i:s') . "'
                                    , ID_USUARIO_CREACION = '" . $administrador->ID_ADMINISTRADOR . "'
                                    , ID_USUARIO_ULTIMA_MODIFICACION = '" . $administrador->ID_ADMINISTRADOR . "'
                                    , PUNTO_REORDEN = 0";//POR DEFECTO CERO, SI LA CANTIDAD BAJA DE ESTA CANTIDAD EL SISTEMA DEBERA HACER UN PEDIDO. EL PROCESO SE LANZARA POR LA NOCHE.
                    $bd->ExecSQL($sqlInsert);
                    $idMatAlm = $bd->IdAsignado();

                    //RECUPERO EL OBJETO CREADO
                    $rowMatAlm = $bd->VerReg("MATERIAL_ALMACEN", "ID_MATERIAL_ALMACEN", $idMatAlm);

                    //GENERAMOS MATERIAL CENTRO FISICO SI NO EXISTE
                    $mat->GenerarMaterialCentroFisico($rowMat->ID_MATERIAL,$rowAlm->ID_CENTRO_FISICO);

                    $materialAlmacenEncontrado = true;
                endif;
            else:
                $materialAlmacenEncontrado = true;
            endif;

            //COMPRUEBO QUE EXISTA MATERIAL ALMACEN
            if ($materialAlmacenEncontrado == false):
                $strError = $strError . $auxiliar->traduce("Dupla Material", $administrador->ID_IDIOMA) . ": " . $rowMat->REFERENCIA_SGA . " - " . ($administrador->ID_IDIOMA == "ESP" ? $rowMat->DESCRIPCION : $rowMat->DESCRIPCION_EN) . " - " . $auxiliar->traduce("Almacén", $administrador->ID_IDIOMA) . ": " . $rowAlm->REFERENCIA . " - " . $rowAlm->NOMBRE . " " . $auxiliar->traduce("no definida", $administrador->ID_IDIOMA) . ".</br>";
                $hayError = true;
            endif;

            if ($hayError == true):
                $html->PagError("ErrorMaterialAlmacen");
            endif;

            //COMPRUEBO QUE EL TIPO LOTE EN ORIGEN SEA IGUAL AL TIPO LOTE EN DESTINO
            if ($rowMatAlm->TIPO_LOTE != $rowAlbaranLinea->TIPO_LOTE):
                //BUSCO EL ALBARAN
                $rowAlbaran = $bd->VerReg("ALBARAN", "ID_ALBARAN", $rowAlbaranLinea->ID_ALBARAN);

                //BUSCO EL ALMACEN DE DESTINO
                $rowAlmacenOrigen = $bd->VerReg("ALMACEN", "ID_ALMACEN", $rowAlbaran->ID_ALMACEN_ORIGEN);

                $strError = $strError . $auxiliar->traduce("Material", $administrador->ID_IDIOMA) . ": " . $rowMat->REFERENCIA_SGA . " - " . ($administrador->ID_IDIOMA == "ESP" ? $rowMat->DESCRIPCION : $rowMat->DESCRIPCION_EN) . ". " . $auxiliar->traduce("Almacén Origen", $administrador->ID_IDIOMA) . ": " . $rowAlmacenOrigen->REFERENCIA . " - " . $rowAlmacenOrigen->NOMBRE . " - " . $auxiliar->traduce("Tipo Lote", $administrador->ID_IDIOMA) . ": " . $auxiliar->traduce($rowAlbaranLinea->TIPO_LOTE, $administrador->ID_IDIOMA) . ". " . $auxiliar->traduce("Almacén Destino", $administrador->ID_IDIOMA) . ": " . $rowAlm->REFERENCIA . " - " . $rowAlm->NOMBRE . " - " . $auxiliar->traduce("Tipo Lote", $administrador->ID_IDIOMA) . ": " . $auxiliar->traduce($rowMatAlm->TIPO_LOTE, $administrador->ID_IDIOMA) . "</br>";

                $html->PagErrorCondicionado($rowMatAlm->TIPO_LOTE, "!=", $rowAlbaranLinea->TIPO_LOTE, "ErrorTipoLoteOrigenDestino");
            endif;

            //BUSCO EL TIPO DE ETIQUETADO
            if ($rowMatAlm->TIPO_LOTE == 'serie'):
                $etiquetado = "Unitario";
            else:
                $etiquetado = "Total";
            endif;

            //COMPRUEBO QUE EXISTA LA UBICACION DONDE DEPOSITAR EL MATERIAL DE LA LINEA
            $NotificaErrorPorEmail = "No";
            $rowMatUbi             = $bd->VerRegRest("MATERIAL_UBICACION", "ID_MATERIAL = $rowAlbaranLinea->ID_MATERIAL AND ID_UBICACION = $rowUbicacionDestino->ID_UBICACION AND ID_MATERIAL_FISICO " . ($rowAlbaranLinea->TIPO_LOTE == 'ninguno' ? 'IS NULL' : "= $rowAlbaranLinea->ID_MATERIAL_FISICO") . " AND ID_TIPO_BLOQUEO " . ($rowAlbaranLinea->ID_TIPO_BLOQUEO == NULL ? 'IS NULL' : "= $rowAlbaranLinea->ID_TIPO_BLOQUEO") . " AND ID_ORDEN_TRABAJO_MOVIMIENTO IS NULL AND ID_INCIDENCIA_CALIDAD IS NULL", "No");
            unset($NotificaErrorPorEmail);
            if ($rowMatUbi == false):
                $sqlInsert = "INSERT INTO MATERIAL_UBICACION SET
                                ID_MATERIAL = $rowAlbaranLinea->ID_MATERIAL
                                , ID_UBICACION = $rowUbicacionDestino->ID_UBICACION
                                , ID_MATERIAL_FISICO = " . ($rowAlbaranLinea->TIPO_LOTE == 'ninguno' ? 'NULL' : "$rowAlbaranLinea->ID_MATERIAL_FISICO") . "
                                , ID_TIPO_BLOQUEO = " . ($rowAlbaranLinea->ID_TIPO_BLOQUEO == NULL ? 'NULL' : "$rowAlbaranLinea->ID_TIPO_BLOQUEO") . "
                                , ID_ORDEN_TRABAJO_MOVIMIENTO = NULL
                                , ID_INCIDENCIA_CALIDAD = NULL";
                $bd->execSQL($sqlInsert);
                $idMatUbiDestino = $bd->IdAsignado();
            else:
                $idMatUbiDestino = $rowMatUbi->ID_MATERIAL_UBICACION;
            endif;

            //SI ES UN MATERIAL FISICO SERIABLE COMPRUEBO QUE NO EXISTA EN EL SISTEMA NI ESTE EN TRANSITO
            if ($rowAlbaranLinea->ID_MATERIAL_FISICO != NULL):
                $NotificaErrorPorEmail = "No";
                $rowMatFis             = $bd->VerReg("MATERIAL_FISICO", "ID_MATERIAL_FISICO", $rowAlbaranLinea->ID_MATERIAL_FISICO, "No");
                unset($NotificaErrorPorEmail);
                $html->PagErrorCondicionado($rowMatFis, "==", false, "MaterialFisicoNoExiste");
                if ($rowMatFis->TIPO_LOTE == 'serie'):    //SI ES UN MATERIAL SERIABLE
                    $numMaterialSeriable = $bd->NumRegsTabla("MATERIAL_UBICACION", "ID_MATERIAL_FISICO = $rowAlbaranLinea->ID_MATERIAL_FISICO AND ACTIVO = 1");
                    if ($numMaterialSeriable > 0):    //EXISTE EN EL SISTEMA
                        $hayErrorSerieLoteEnSistema = true;
                        $strErrorEnSistema          .= "$rowMatFis->NUMERO_SERIE_LOTE<br />";
                    else:
                        $rowMovSalLinea = $bd->VerReg("MOVIMIENTO_SALIDA_LINEA", "ID_ALBARAN_LINEA", $rowAlbaranLinea->ID_ALBARAN_LINEA);
                        if ($mat->MaterialFisicoEnTransito($rowAlbaranLinea->ID_MATERIAL_FISICO, $rowMovSalLinea->ID_MOVIMIENTO_SALIDA_LINEA) == true):    //ESTA EN TRANSITO DENTRO DEL SISTEMA
                            $hayErrorSerieLoteEnTransito = true;
                            $strErrorEnTransito          .= "$rowMatFis->NUMERO_SERIE_LOTE<br />";
                        endif;
                    endif;
                endif;    //FIN SI ES UN MATERIAL SERIABLE
            endif;
            //FIN SI ES UN MATERIAL FISICO  SERIABLE COMPRUEBO QUE NO EXISTA EN EL SISTEMA NI ESTE EN TRANSITO

            //INCREMENTO LA CANTIDAD EN MATERIAL UBICACION ORIGEN(SI ES DE CONSTRUCCION EVITAMOS QUE ENTRE EN EL EL PROCESO DE INTEGRIDAD DE MATERIAL FISICO, EN MATERIAL UBICACION SI DEBERIA ENTRAR)
            $sqlUpdate = "UPDATE MATERIAL_UBICACION SET
                                STOCK_TOTAL = STOCK_TOTAL + $rowAlbaranLinea->CANTIDAD_PENDIENTE_DE_RECEPCIONAR_EN_DESTINO
                                , STOCK_OK = STOCK_OK + " . ($rowAlbaranLinea->ID_TIPO_BLOQUEO == NULL ? $rowAlbaranLinea->CANTIDAD_PENDIENTE_DE_RECEPCIONAR_EN_DESTINO : 0) . "
                                , STOCK_BLOQUEADO = STOCK_BLOQUEADO + " . ($rowAlbaranLinea->ID_TIPO_BLOQUEO == NULL ? 0 : $rowAlbaranLinea->CANTIDAD_PENDIENTE_DE_RECEPCIONAR_EN_DESTINO)
                . ($rowAlbaran->ID_ORDEN_MONTAJE != NULL ? ", PENDIENTE_REVISAR_MATERIAL_FISICO_UBICACION = 2, PENDIENTE_REVISAR_MATERIAL_UBICACION_TIPO_BLOQUEO = 1" : "") . "
                                WHERE ID_MATERIAL_UBICACION = $idMatUbiDestino";
            $bd->ExecSQL($sqlUpdate);

            //GENERO LA LINEA DEL MOVIMIENTO DE ENTRADA
            $sqlInsert = "INSERT INTO MOVIMIENTO_ENTRADA_LINEA SET
                                ID_MOVIMIENTO_ENTRADA = $idMovimiento
                                , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                , FECHA = '" . date("Y-m-d H:i:s") . "'
                                , ID_ALBARAN = $rowAlbaranLinea->ID_ALBARAN
                                , ID_ALBARAN_LINEA = $rowAlbaranLinea->ID_ALBARAN_LINEA
                                , ID_UBICACION = $rowUbicacionDestino->ID_UBICACION
                                , ID_MATERIAL = $rowAlbaranLinea->ID_MATERIAL
                                , ID_MATERIAL_FISICO = " . ($rowAlbaranLinea->TIPO_LOTE == 'ninguno' ? 'NULL' : "$rowAlbaranLinea->ID_MATERIAL_FISICO") . "
                                , ID_TIPO_BLOQUEO = " . ($rowAlbaranLinea->ID_TIPO_BLOQUEO == NULL ? 'NULL' : "$rowAlbaranLinea->ID_TIPO_BLOQUEO") . "
                                , ID_INCIDENCIA_CALIDAD = " . ($rowLineaMovimientoSalida->ID_INCIDENCIA_CALIDAD == NULL ? 'NULL' : "$rowLineaMovimientoSalida->ID_INCIDENCIA_CALIDAD") . "
                                , CONTROL_CODIFICACION = " . ((($rowMat->DESCRIPCION_REVISADA == 'No') || ($rowMat->FOTO == 0)) ? 1 : 0) . "
                                , LOTE_PROVEEDOR_OBLIGATORIO = " . ($rowMatAlm->TIPO_LOTE == 'ninguno' ? 0 : $rowMat->CON_LOTE_PROVEEDOR) . "
                                , TIPO_PEDIDO = 'Traslado'
                                , ESTADO = '" . ($via_recepcion == 'PDA' ? 'Creada' : 'Finalizada') . "'
                                , TIPO_ETIQUETADO = '$etiquetado'
                                , TIPO_LOTE = '" . $rowAlbaranLinea->TIPO_LOTE . "'
                                , CANTIDAD = $rowAlbaranLinea->CANTIDAD_PENDIENTE_DE_RECEPCIONAR_EN_DESTINO
                                , ENTREGA_FINAL_PEDIDO = 0";
            $bd->ExecSQL($sqlInsert);

            //ACTUALIZO LAS LINEAS DE MOVIMIENTO ASOCIADAS A ESTA LINEA DE ALBARAN
            $sqlUpdate = "UPDATE MOVIMIENTO_SALIDA_LINEA SET
                            CANTIDAD_PENDIENTE_DE_RECEPCIONAR_EN_DESTINO = 0
                            , ESTADO = 'Recepcionado'
                            WHERE ID_ALBARAN_LINEA = $rowAlbaranLinea->ID_ALBARAN_LINEA AND LINEA_ANULADA = 0 AND BAJA = 0";
            $bd->ExecSQL($sqlUpdate);

            //BUSCO LAS LINEAS DEL MOVIMIENTO DE SALIDA ASOCIADAS A ESTA LINEA DE ALBARAN PARA ACTUALIZAR LA CABECERA DEL MOVIMIENTO DE SALIDA Y LA LINEA DEL PEDIDO
            $sqlLineasMovimientoSalida    = "SELECT DISTINCT ID_MOVIMIENTO_SALIDA, ID_PEDIDO_SALIDA_LINEA
                                            FROM MOVIMIENTO_SALIDA_LINEA
                                            WHERE ID_ALBARAN_LINEA = $rowAlbaranLinea->ID_ALBARAN_LINEA AND LINEA_ANULADA = 0 AND BAJA = 0";
            $resultLineasMovimientoSalida = $bd->ExecSQL($sqlLineasMovimientoSalida);
            while ($rowLineaMovimientoSalida = $bd->SigReg($resultLineasMovimientoSalida)):
//                //ACTUALIZO LA CABECERA DEL MOVIMIENTO
//                $movimiento->actualizarEstadoMovimientoSalida($rowLineaMovimientoSalida->ID_MOVIMIENTO_SALIDA);
                //AÑADO EL MOVIMIENTO AL ARRAY A ACTUALIZAR
                $arrMovimientosActualizar[] = $rowLineaMovimientoSalida->ID_MOVIMIENTO_SALIDA;

                //ME GUARDO LAS LINEAS DE PEDIDO DE SALIDA IMPLICADAS
                $arrPedidosLineaActualizar[] = $rowLineaMovimientoSalida->ID_PEDIDO_SALIDA_LINEA;
            endwhile;

            //OBTENGO POSIBLES NECESIADES ASOCIADAS PARA LUEGO ENVIAR ALERTAS Y MOSTRAR AVISO NUEVAMENTE POR PANTALLA
            $sqlMovimientosSalidaLinea   = "SELECT * FROM MOVIMIENTO_SALIDA_LINEA MSL WHERE MSL.ID_ALBARAN_LINEA = $rowAlbaranLinea->ID_ALBARAN_LINEA AND MSL.BAJA = 0 ";
            $resultMovimientoSalidaLinea = $bd->ExecSQL($sqlMovimientosSalidaLinea);
            while ($rowMovSalidaLinea = $bd->SigReg($resultMovimientoSalidaLinea)):
                //OBTENGO POSIBLES NECESIDADES ASOCIADAS A ESE MATERIAL Y QUE TENGAN ALGUN PEDIDO DE TRASLADO CON ORIGEN EL ALMACEN DE LA RECEPCION
                $necesidades = $necesidad->getNecesidadesByMovimientoSalidaLinea($rowMovSalidaLinea->ID_MOVIMIENTO_SALIDA_LINEA, array(), array());
                while ($nec = $bd->SigReg($necesidades)):
                    $necesidadesAsociadasAvisar[$nec->ID_NECESIDAD] = $cantidad;
                endwhile;
            endwhile;

            //SI EL ALBARAN TIENE ORDEN MONTAJE ASOCIADA, CREAMOS EL MOVIMIENTO
            if ($rowAlbaran->ID_ORDEN_MONTAJE != NULL):
                //BUSCAMOS SI YA EXISTE MOVIMIENTO PARA ESE MATERIAL/UBICACION/MATERIAL FISICO
                $GLOBALS["NotificaErrorPorEmail"] = "No";
                $rowOrdenMontajeMovimiento        = $bd->VerRegRest("ORDEN_MONTAJE_MOVIMIENTO", "TIPO_OPERACION = 'Entrega' AND ID_ORDEN_MONTAJE = $rowAlbaran->ID_ORDEN_MONTAJE AND ID_MATERIAL = $rowAlbaranLinea->ID_MATERIAL AND ID_MATERIAL_FISICO " . ($rowAlbaranLinea->TIPO_LOTE == 'ninguno' ? 'IS NULL' : "=$rowAlbaranLinea->ID_MATERIAL_FISICO") . " AND ID_UBICACION = $rowUbicacionDestino->ID_UBICACION AND BAJA = 0", "No");
                if ($rowOrdenMontajeMovimiento == false):

                    //INSERTAMOS EL MOVIMIENTO
                    $sqlInsert = "INSERT INTO ORDEN_MONTAJE_MOVIMIENTO SET
                                          ID_ORDEN_MONTAJE = $rowAlbaran->ID_ORDEN_MONTAJE
                                        , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                        , FECHA            = '" . date("Y-m-d H:i:s") . "'
                                        , ID_UBICACION     = $rowUbicacionDestino->ID_UBICACION
                                        , ID_MATERIAL      = $rowAlbaranLinea->ID_MATERIAL
                                        , ID_MATERIAL_FISICO = " . ($rowAlbaranLinea->TIPO_LOTE == 'ninguno' ? 'NULL' : "$rowAlbaranLinea->ID_MATERIAL_FISICO") . "
                                        , CANTIDAD         =  $rowAlbaranLinea->CANTIDAD_PENDIENTE_DE_RECEPCIONAR_EN_DESTINO
                                        , TIPO_OPERACION   = 'Entrega'";
                    $bd->ExecSQL($sqlInsert);
                else:
                    //ACTUALIZAMOS LA CANTIDAD DEL MOVIMIENTO
                    $sqlUpdate = "UPDATE ORDEN_MONTAJE_MOVIMIENTO
                                    SET CANTIDAD = CANTIDAD + $rowAlbaranLinea->CANTIDAD_PENDIENTE_DE_RECEPCIONAR_EN_DESTINO
                                    WHERE ID_ORDEN_MONTAJE_MOVIMIENTO = $rowOrdenMontajeMovimiento->ID_ORDEN_MONTAJE_MOVIMIENTO";
                    $bd->ExecSQL($sqlUpdate);
                endif;

            endif;
            //SI EL ALBARAN TIENE ORDEN MONTAJE ASOCIADA, CREAMOS EL MOVIMIENTO

            //ACTUALIZO LA LINEA DE ALBARAN
            $sqlUpdate = "UPDATE ALBARAN_LINEA SET
                            CANTIDAD_PENDIENTE_DE_RECEPCIONAR_EN_DESTINO = 0
                            WHERE ID_ALBARAN_LINEA = $rowAlbaranLinea->ID_ALBARAN_LINEA";
            $bd->ExecSQL($sqlUpdate);

            //OBTENEMOS EL TIPO DE BLOQUEO PREVENTIVO
            $rowTipoBloqueoPreventivo = $bd->VerRegRest("TIPO_BLOQUEO", "TIPO_BLOQUEO = 'P' AND BAJA = 0", "No");

            if (($rowAlbaranLinea->ID_TIPO_BLOQUEO == NULL) || ($rowAlbaranLinea->ID_TIPO_BLOQUEO == $rowTipoBloqueoPreventivo->ID_TIPO_BLOQUEO)):
                //INFORMO DE LAS NECESIDADES ASOCIADAS QUE TENGAN PEDIDO DE TRASLADO CON ALMACEN ORIGEN EL DE ESTA RECEPCION (SOLAMENTE SI ES MATERIAL OK O PREVENTIVO)
                $necesidad->EnviarNotificacionEmail_RecepcionEnAlmacenOrigenDePedidoTraslado($rowAlbaranLinea->ID_MATERIAL, $rowAlm->ID_ALMACEN, $cantidad);
            endif;
        endwhile;
        //FIN BUCLE LINEAS DE ALBARAN

        //DEJO LOS VALORES UNICOS DE LOS ARRAY A ACTUALIZAR
        $arrMovimientosActualizar  = array_unique( (array)$arrMovimientosActualizar);
        $arrPedidosLineaActualizar = array_unique( (array)$arrPedidosLineaActualizar);

        //ACTUALIZO LOS MOVIMIENTOS
        foreach ($arrMovimientosActualizar as $idMovimientoSalida):
            $movimiento->actualizarEstadoMovimientoSalida($idMovimientoSalida);
        endforeach;

        /************************************ HABILITO TRIGGERS IMPLICADOS ************************************/
        $sqlHabilitarTriggers = "SET @Trigger_MOVIMIENTO_SALIDA_LINEA_UPDATE_Deshabilitado = NULL";
        $bd->ExecSQL($sqlHabilitarTriggers);
        /********************************** FIN HABILITO TRIGGERS IMPLICADOS **********************************/

        //ACTUALIZO UNA LINEA DE MOVIMIENTO DE CADA LINEA DE PEDIDO
        foreach ($arrPedidosLineaActualizar as $idPedidoLineaSalida):
            //BUSCO UNA LINEA DE MOVIMIENTO DE SALIDA LINEA DE ESTA LINEA DE PEDIDO
            $rowMovimientoSalidaLinea = $bd->VerRegRest("MOVIMIENTO_SALIDA_LINEA", "ID_PEDIDO_SALIDA_LINEA = $idPedidoLineaSalida AND LINEA_ANULADA = 0 AND BAJA = 0", "No");

            //ACTUALIZO EL ESTADO DE LA LINEA DEL PEDIDO DE SALIDA
            $movimiento->actualizarEstadoLineaPedidoSalida($rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA_LINEA);
            //ACTUALIZO EL ESTADO DE LA NECESIDAD DE LA LINEA DEL MOVIMIENTO DE SALIDA
            $movimiento->actualizarEstadoLineaNecesidad($rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA);
        endforeach;

        //ACTUALIZO EL ESTADO DE LA ORDEN DE TRANSPORTE
        $this->actualizar_estado_orden_transporte($rowExpedicion->ID_EXPEDICION);

        //MUESTRO LOS ERRORES DE MATERIAL SERIABLE EN CASO DE OCURRIR
        if ($hayErrorSerieLoteEnSistema == true):
            $strError = $strErrorEnSistema;
            $html->PagError("NseriesYaSeEncuentranEnSistema");
        endif;
        if ($hayErrorSerieLoteEnTransito == true):
            $strError = $strErrorEnTransito;
            $html->PagError("NseriesYaSeEncuentranEnTransito");
        endif;

        //ACTUALIZO EL MOVIMIENTO DE ENTRADA A UBICADO
        $sqlUpdate = "UPDATE MOVIMIENTO_ENTRADA SET ESTADO = '" . ($via_recepcion == 'PDA' ? 'Procesado' : 'Ubicado') . "' WHERE ID_MOVIMIENTO_ENTRADA = $idMovimiento";
        $bd->ExecSQL($sqlUpdate);

        //OBTENGO EL ESTADO FINAL DE LA RECEPCION TRAS LOS CAMBIOS
        $estadoFinalRecepcion = $movimiento->getEstadoRecepcion($idRecepcion);

        //ACTUALIZO EL ESTADO DE LA RECEPCION
        $sqlUpdate = "UPDATE MOVIMIENTO_RECEPCION SET ESTADO = '" . $estadoFinalRecepcion . "' WHERE ID_MOVIMIENTO_RECEPCION = $idRecepcion";
        $bd->ExecSQL($sqlUpdate);

        //BUSCO EL TIPO DE BLOQUEO PREVENTIVO
        $rowTipoBloqueoPreventivo = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "SP", "No");
        $numOK                    = $bd->NumRegsTabla("MOVIMIENTO_ENTRADA_LINEA", "ID_MOVIMIENTO_ENTRADA = $idMovimiento AND BAJA = 0 AND (ID_TIPO_BLOQUEO IS NULL OR ID_TIPO_BLOQUEO = $rowTipoBloqueoPreventivo->ID_TIPO_BLOQUEO)");

        //ACTUALIZO LA FECHA PROCESADO DE LAS LINEAS DEL MOVIMIENTO A PROCESAR
        $sqlLineasProcesar    = "SELECT *
                                FROM MOVIMIENTO_ENTRADA_LINEA
                                WHERE ID_MOVIMIENTO_ENTRADA = '" . $idMovimiento . "' AND BAJA = 0 AND (ID_TIPO_BLOQUEO IS NULL OR ID_TIPO_BLOQUEO = $rowTipoBloqueoPreventivo->ID_TIPO_BLOQUEO)";
        $resultLineasProcesar = $bd->ExecSQL($sqlLineasProcesar);
        while ($rowLineasProcesar = $bd->SigReg($resultLineasProcesar)):
            $sqlUpdate = "UPDATE MOVIMIENTO_ENTRADA_LINEA SET FECHA_PROCESADO = '" . date("Y-m-d H:i:s") . "' WHERE ID_MOVIMIENTO_ENTRADA_LINEA = $rowLineasProcesar->ID_MOVIMIENTO_ENTRADA_LINEA";
            $bd->ExecSQL($sqlUpdate);
        endwhile;
        //FIN ACTUALIZO LA FECHA PROCESADO DE LAS LINEAS DEL MOVIMIENTO A PROCESAR

        if ($numOK > 0):
            $resultado = $sap->RecepcionExpedicion($idMovimiento);
            if ($resultado['RESULTADO'] != 'OK'): //No se ha podido grabar la información en SAP
                if (count( (array)$resultado['ERRORES']) > 0):
                    foreach ($resultado['ERRORES'] as $arr):
                        foreach ($arr as $mensaje_error):
                            $strError = $strError . $mensaje_error . "<br>";
                        endforeach;
                    endforeach;
                endif;

                //DESHAGO LA TRANSACCION
                $bd->rollback_transaction();

                //GRABO EL ERROR PRODUCIDO EN BASE DE DATOS
                $sap->InsertarErrores($resultado);

                $html->PagError("ErrorSAP");
            endif;
        endif;

        // LOG MOVIMIENTOS
        $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Recepción", "Albarán", $rowAlbaran->ID_ALBARAN, "");
    }

    function calcularADRExpedicion($idExpedicion)
    {
        global $bd;
        global $auxiliar;
        global $mat;
        global $orden_transporte;
        global $html;

        //VARIABLE PARA SABER EL SUMATORIO DE PELIGROSIDAD
        $sumatorioPeligrosidadOrdenTransporte = 0;

        //BUSCO LA RECOGIDA
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowExp                           = $bd->VerReg("EXPEDICION", "ID_EXPEDICION", $idExpedicion,"No");
        $html->PagErrorCondicionado($rowExp,"==",false,"ExpedicionNoExiste");

        //DEPENDIENDO DEL TIPO TENDRA LOS MATERIALES EN DISTINTOS LUGARES
        if ($rowExp->TIPO_ORDEN_RECOGIDA == "Recogida en Almacen"):
            //BUSCO LAS LINEAS IMPLICADAS
            $sqlLineasPeligrosas = "SELECT M.ID_MATERIAL, M.ID_ONU, M.UNIDAD_RIESGO, M.ID_CATEGORIA_TRANSPORTE, MSL.CANTIDAD AS CANTIDAD_PELIGROSA
                                                FROM MOVIMIENTO_SALIDA_LINEA MSL
                                                INNER JOIN MATERIAL M ON MSL.ID_MATERIAL=M.ID_MATERIAL
                                                WHERE MSL.ID_EXPEDICION=$rowExp->ID_EXPEDICION AND MSL.BAJA = 0 AND M.CON_ADR = 1";

        elseif ($rowExp->TIPO_ORDEN_RECOGIDA == "Recogida en Proveedor"):
            if ($rowExp->SUBTIPO_ORDEN_RECOGIDA == "Sin Pedido Conocido"):
                //BUSCO LAS LINEAS IMPLICADAS
                $sqlLineasPeligrosas = "SELECT M.ID_MATERIAL, M.ID_ONU, M.UNIDAD_RIESGO, M.ID_CATEGORIA_TRANSPORTE, MEL.CANTIDAD AS CANTIDAD_PELIGROSA
                                                FROM MOVIMIENTO_ENTRADA_LINEA MEL
                                                INNER JOIN MATERIAL M ON MEL.ID_MATERIAL=M.ID_MATERIAL
                                                WHERE MEL.ID_EXPEDICION_ENTREGA=$rowExp->ID_EXPEDICION AND MEL.BAJA = 0 AND MEL.LINEA_ANULADA=0 AND M.CON_ADR = 1";

            elseif ($rowExp->SUBTIPO_ORDEN_RECOGIDA == "Con Pedido Conocido"):
                //BUSCO LAS LINEAS IMPLICADAS
                $sqlLineasPeligrosas = "SELECT M.ID_MATERIAL, M.ID_ONU, M.UNIDAD_RIESGO, M.ID_CATEGORIA_TRANSPORTE, EPC.CANTIDAD AS CANTIDAD_PELIGROSA
                                                FROM EXPEDICION_PEDIDO_CONOCIDO EPC
                                                INNER JOIN MATERIAL M ON EPC.ID_MATERIAL=M.ID_MATERIAL
                                                WHERE EPC.ID_EXPEDICION=$rowExp->ID_EXPEDICION AND M.CON_ADR = 1 AND EPC.BAJA=0";

            elseif ($rowExp->SUBTIPO_ORDEN_RECOGIDA == 'Retorno Material Estropeado desde Proveedor'):
                //MOVIMIENTO SALIDA LINEA RELACIONADO CON EL MOVIMIENTO DE MATERIAL ESTROPEADO
                $sqlLineasPeligrosas = "SELECT M.ID_MATERIAL, M.ID_ONU, M.UNIDAD_RIESGO, M.ID_CATEGORIA_TRANSPORTE, MSL.CANTIDAD AS CANTIDAD_PELIGROSA
                                            FROM  MOVIMIENTO_SALIDA_LINEA MSL
                                            INNER JOIN MATERIAL M ON MSL.ID_MATERIAL = M.ID_MATERIAL
                                            WHERE MSL.ID_EXPEDICION = $rowExp->ID_EXPEDICION AND MSL.BAJA = 0 AND M.CON_ADR = 1";
            endif;

        elseif ($rowExp->TIPO_ORDEN_RECOGIDA == "Operaciones fuera de Sistema"):
            return false;//NO TIENE MATERIALES PELIGROSOS

        elseif ($rowExp->TIPO_ORDEN_RECOGIDA == "Operaciones en Parque"):
            //BUSCO LAS LINEAS PELIGROSAS
            $sqlLineasPeligrosas = "SELECT M.ID_MATERIAL, M.ID_ONU, M.UNIDAD_RIESGO, M.ID_CATEGORIA_TRANSPORTE, (OTM.CANTIDAD-OTM.CANTIDAD_ANULADA) AS CANTIDAD_PELIGROSA
                                                FROM ORDEN_TRABAJO_MOVIMIENTO OTM
                                                INNER JOIN MATERIAL M ON OTM.ID_MATERIAL=M.ID_MATERIAL
                                                WHERE OTM.ID_EXPEDICION=$rowExp->ID_EXPEDICION AND OTM.BAJA = 0 AND M.CON_ADR = 1";

        elseif ($rowExp->TIPO_ORDEN_RECOGIDA == "Recogida en Proveedor Construccion"):
            //BUSCO LAS LINEAS IMPLICADAS
            $sqlLineasPeligrosas = "SELECT M.ID_MATERIAL, M.ID_ONU, M.UNIDAD_RIESGO, M.ID_CATEGORIA_TRANSPORTE, BL.CANTIDAD AS CANTIDAD_PELIGROSA
                                            FROM BULTO_LINEA BL
                                            INNER JOIN BULTO B ON B.ID_BULTO = BL.ID_BULTO
                                            INNER JOIN MATERIAL M ON BL.ID_MATERIAL=M.ID_MATERIAL
                                            WHERE B.ID_EXPEDICION=$rowExp->ID_EXPEDICION AND M.CON_ADR = 1";

        endif;

        $resultLineasPeligrosas = $bd->ExecSQL($sqlLineasPeligrosas);

        while ($rowLineasPeligrosas = $bd->SigReg($resultLineasPeligrosas)):
            //UNIDADES DE COMPRA
            $cantidadCompra      = 0;
            $unidadesBaseyCompra = $mat->unidadBaseyCompra($rowLineasPeligrosas->ID_MATERIAL);

            if ($unidadesBaseyCompra["unidadBase"] <> $unidadesBaseyCompra["unidadCompra"]):
                $cantidadCompra = $auxiliar->formatoNumero($mat->cantUnidadCompra($rowLineasPeligrosas->ID_MATERIAL, $rowLineasPeligrosas->CANTIDAD_PELIGROSA));
            else:
                $cantidadCompra = $rowLineasPeligrosas->CANTIDAD_PELIGROSA;
            endif;


            //BUSCO LA CATEGORIA TRANSPORTE DEL MATERIAL
            $NotificaErrorPorEmail  = "No";
            $rowCategoriaTransporte = $bd->VerReg("CATEGORIA_TRANSPORTE", "ID_CATEGORIA_TRANSPORTE", $rowLineasPeligrosas->ID_CATEGORIA_TRANSPORTE, "No");
            unset($NotificaErrorPorEmail);

            //CALCULAMOS LA PELIGROSIDAD DE LA LINEA
            $peligrosidadLinea = 0;
            if ($rowCategoriaTransporte != false):
                //SI LA CATEGORIA DE TRANSPORTE ES 1, SE PUEDE MULTIPLICAR POR 20 SI EL MATERIAL TIENE ONU:0081-84,0241,0331,0332,0482,1005,1017
                if ($rowCategoriaTransporte->CATEGORIA_TRANSPORTE == 1 && $rowLineasPeligrosas->ID_ONU != NULL):
                    //BUSCO LA ONU
                    $rowONU = $bd->VerReg("ONU", "ID_ONU", $rowLineasPeligrosas->ID_ONU);
                    $onu_20 = array('0081', '0082', '0083', '0084', '0241', '0331', '0332', '0482', '1005', '1017');
                    if (in_array(str_pad((string) $rowONU->NUMERO, 4, "0", STR_PAD_LEFT), (array) $onu_20)):
                        $rowCategoriaTransporte->FACTOR_RIESGO = 20;
                    endif;
                endif;
                $peligrosidadLinea = $cantidadCompra * $rowLineasPeligrosas->UNIDAD_RIESGO * $rowCategoriaTransporte->FACTOR_RIESGO;
            endif;

            //AÑADO LA PELIGROSIDAD DE LA LINEA AL SUMATORIO
            $sumatorioPeligrosidadOrdenTransporte = $sumatorioPeligrosidadOrdenTransporte + $peligrosidadLinea;
        endwhile; //BUCLE LINEAS

        if (isset($sumatorioPeligrosidadOrdenTransporte)):
            if ($sumatorioPeligrosidadOrdenTransporte == 0):
                $sqlUpdate = "UPDATE EXPEDICION SET
											ADR = 'No ADR'
											WHERE ID_EXPEDICION = $rowExp->ID_EXPEDICION";
                $bd->ExecSQL($sqlUpdate);
            elseif ($sumatorioPeligrosidadOrdenTransporte >= 1000):
                $sqlUpdate = "UPDATE EXPEDICION SET
											ADR = 'ADR'
											WHERE ID_EXPEDICION = $rowExp->ID_EXPEDICION";
                $bd->ExecSQL($sqlUpdate);
            else:
                $sqlUpdate = "UPDATE EXPEDICION SET
											ADR = 'Exento'
											WHERE ID_EXPEDICION = $rowExp->ID_EXPEDICION";
                $bd->ExecSQL($sqlUpdate);
            endif;
        else:
            //NO HACER NADA
        endif;

        //SI TIENE ORDEN DE TRANSPORTE, CALCULAMOS EL ADR
        if ($rowExp->ID_ORDEN_TRANSPORTE != ""):
            $orden_transporte->calcularADROrdenTransporte($rowExp->ID_ORDEN_TRANSPORTE);
        endif;
    }

    function calcularAmbitoYComunidadExpedicion($idExpedicion)
    {
        global $bd;
        global $auxiliar;
        global $mat;
        global $orden_transporte;

        //BUSCAMOS AL ORDEN DE RECOGIDA
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowExpedicion                    = $bd->VerReg("EXPEDICION", "ID_EXPEDICION", $idExpedicion, "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);

        if ($rowExpedicion == false):
            return false;
        endif;

        //DECLARAMOS LAS VARIABLES
        $nacional    = true;
        $comunitario = true;


        //DEPENDIENDO DEL TIPO DE ORDEN DE RECOGIDA BUSCAMOS SU ORIGEN/DESTINO

//**************OPERACIONES FUERA DE SISTEMA***************//
        if ($rowExpedicion->TIPO_ORDEN_RECOGIDA == "Operaciones fuera de Sistema"):
            //BUSCAMOS LA DIRECCION DE ORIGEN Y DESTINO DE LOS BULTOS
            $sqlBultos    = "SELECT ID_DIRECCION_ORIGEN, ID_DIRECCION_DESTINO
                            FROM BULTO
                            WHERE ID_EXPEDICION = $rowExpedicion->ID_EXPEDICION";
            $resultBultos = $bd->ExecSQL($sqlBultos);

            while ($rowBulto = $bd->SigReg($resultBultos)):
                //BUSCAMOS LAS DIRECCIONES DE ORIGEN Y DESTINO
                if ($rowBulto->ID_DIRECCION_ORIGEN != "" && $rowBulto->ID_DIRECCION_DESTINO != ""):
                    $rowDireccionOrigen  = $bd->VerReg("DIRECCION", "ID_DIRECCION", $rowBulto->ID_DIRECCION_ORIGEN, "No");
                    $rowDireccionDestino = $bd->VerReg("DIRECCION", "ID_DIRECCION", $rowBulto->ID_DIRECCION_DESTINO, "No");

                    //BUSCAMOS LOS PAISES
                    if (($rowDireccionOrigen->ID_PAIS != "") && ($rowDireccionDestino->ID_PAIS != "")):
                        $rowPaisOrigen  = $bd->VerReg("PAIS", "ID_PAIS", $rowDireccionOrigen->ID_PAIS, "No");
                        $rowPaisDestino = $bd->VerReg("PAIS", "ID_PAIS", $rowDireccionDestino->ID_PAIS, "No");

                        //SI SON DISTINTOS MARCAMOS COMO INTERNACIONAL
                        if ($rowPaisOrigen->ID_PAIS != $rowPaisDestino->ID_PAIS):
                            $nacional = false;
                        endif;

                        //SI SON DISTINTAS COMUNIDADES MARCAMOS COMO EXTRACOMUNITARIO
                        if ($rowPaisOrigen->COMUNIDAD != $rowPaisDestino->COMUNIDAD):
                            $comunitario = false;
                        endif;

                    endif;
                    //FIN BUSCAMOS LOS PAISES
                endif;
                //FIN BUSCAMOS LAS DIRECCIONES DE ORIGEN Y DESTINO

            endwhile;

//**************OPERACIONES EN PARQUE*****************//
        elseif ($rowExpedicion->TIPO_ORDEN_RECOGIDA == "Operaciones en Parque"):

            $sqlOTM    = "SELECT DISTINCT OTM.ID_UBICACION, OT.ID_INSTALACION
                                                            FROM ORDEN_TRABAJO_MOVIMIENTO OTM
                                                            INNER JOIN ORDEN_TRABAJO OT ON OT.ID_ORDEN_TRABAJO = OTM.ID_ORDEN_TRABAJO
                                                            WHERE OTM.ID_EXPEDICION = $rowExpedicion->ID_EXPEDICION AND OTM.BAJA= 0";
            $resultOTM = $bd->ExecSQL($sqlOTM);

            while ($rowOTM = $bd->SigReg($resultOTM)):

                //BUSCAMOS EL PAIS DE LA UBICACION DE LA OTM (CENTRO FISICO)
                $sqlPaisOTM    = "SELECT DISTINCT P.ID_PAIS, P.COMUNIDAD
                                            FROM UBICACION U
                                            INNER JOIN ALMACEN A ON A.ID_ALMACEN = U.ID_ALMACEN
                                            INNER JOIN DIRECCION D ON A.ID_CENTRO_FISICO = D.ID_CENTRO_FISICO
                                            INNER JOIN PAIS P ON P.ID_PAIS = D.ID_PAIS
                                            WHERE D.TIPO_DIRECCION = 'Centro Fisico' AND U.ID_UBICACION = $rowOTM->ID_UBICACION AND D.BAJA = 0";
                $resultPaisOTM = $bd->ExecSQL($sqlPaisOTM);

                $rowPaisOTM = $bd->SigReg($resultPaisOTM);

                //SI LA OT TIENE INSTALACION
                if ($rowOTM->ID_INSTALACION != ""):
                    //BUSCAMOS LA DIRECCION DE LA INSTALACION
                    $sqlPaisInstalacion    = "SELECT DISTINCT P.ID_PAIS, P.COMUNIDAD
                                            FROM DIRECCION D
                                            INNER JOIN PAIS P ON P.ID_PAIS = D.ID_PAIS
                                            WHERE D.TIPO_DIRECCION = 'Instalacion' AND D.ID_INSTALACION = $rowOTM->ID_INSTALACION AND D.BAJA = 0";
                    $resultPaisInstalacion = $bd->ExecSQL($sqlPaisInstalacion);

                    if ($rowPaisInstalacion = $bd->SigReg($resultPaisInstalacion)):
                        //SI SON DISTINTOS MARCAMOS COMO INTERNACIONAL
                        if ($rowPaisInstalacion->ID_PAIS != $rowPaisOTM->ID_PAIS):
                            $nacional = false;
                        endif;

                        //SI SON DISTINTAS COMUNIDADES MARCAMOS COMO EXTRACOMUNITARIO
                        if ($rowPaisInstalacion->COMUNIDAD != $rowPaisOTM->COMUNIDAD):
                            $comunitario = false;
                        endif;
                    endif;
                endif;

            endwhile;

//**************RECOGIDA EN PROVEEDOR*****************//
        elseif ($rowExpedicion->TIPO_ORDEN_RECOGIDA == "Recogida en Proveedor" || $rowExpedicion->TIPO_ORDEN_RECOGIDA == "Recogida en Proveedor Construccion"):


            if ($rowExpedicion->TIPO_ORDEN_RECOGIDA == 'Recogida en Proveedor Construccion'):
                //MOVIMIENTO ENTRADA
                $sqlPedidoEntrada = "SELECT DISTINCT PE.ID_PROVEEDOR, PEL.ID_ALMACEN
                                        FROM BULTO B
                                        INNER JOIN BULTO_LINEA BL ON BL.ID_BULTO = B.ID_BULTO
                                        INNER JOIN PEDIDO_ENTRADA_LINEA PEL ON PEL.ID_PEDIDO_ENTRADA_LINEA=BL.ID_PEDIDO_ENTRADA_LINEA
                                        INNER JOIN PEDIDO_ENTRADA PE ON PE.ID_PEDIDO_ENTRADA = PEL.ID_PEDIDO_ENTRADA
                                        WHERE B.ID_EXPEDICION = $rowExpedicion->ID_EXPEDICION AND PEL.BAJA = 0 AND PEL.INDICADOR_BORRADO IS NULL";

            elseif ($rowExpedicion->SUBTIPO_ORDEN_RECOGIDA == 'Sin Pedido Conocido'):
                //MOVIMIENTO ENTRADA
                $sqlPedidoEntrada = "SELECT DISTINCT PE.ID_PROVEEDOR,PE.ID_CLIENTE,PEL.ID_ALMACEN
                            FROM MOVIMIENTO_ENTRADA ME
                            INNER JOIN MOVIMIENTO_ENTRADA_LINEA MEL ON MEL.ID_MOVIMIENTO_ENTRADA=ME.ID_MOVIMIENTO_ENTRADA
                            INNER JOIN PEDIDO_ENTRADA PE ON PE.ID_PEDIDO_ENTRADA=MEL.ID_PEDIDO
                            INNER JOIN PEDIDO_ENTRADA_LINEA PEL ON PEL.ID_PEDIDO_ENTRADA_LINEA=MEL.ID_PEDIDO_LINEA
                            WHERE MEL.ID_EXPEDICION_ENTREGA = $rowExpedicion->ID_EXPEDICION AND ME.BAJA = 0";

            elseif ($rowExpedicion->SUBTIPO_ORDEN_RECOGIDA == 'Con Pedido Conocido'):
                //PEDIDO CONOCIDO
                $sqlPedidoEntrada = "SELECT DISTINCT PE.ID_PROVEEDOR,PE.ID_CLIENTE,PEL.ID_ALMACEN
                            FROM EXPEDICION_PEDIDO_CONOCIDO EPC
                            INNER JOIN PEDIDO_ENTRADA PE ON PE.ID_PEDIDO_ENTRADA = EPC.ID_PEDIDO_ENTRADA
                            INNER JOIN PEDIDO_ENTRADA_LINEA PEL ON PEL.ID_PEDIDO_ENTRADA_LINEA = EPC.ID_PEDIDO_ENTRADA_LINEA
                            WHERE EPC.ID_EXPEDICION = $rowExpedicion->ID_EXPEDICION AND EPC.BAJA = 0";

            elseif ($rowExpedicion->SUBTIPO_ORDEN_RECOGIDA == 'Retorno Material Estropeado desde Proveedor'):
                //MOVIMIENTO SALIDA LINEA RELACIONADO CON EL MOVIMIENTO DE MATERIAL ESTROPEADO
                $sqlPedidoEntrada = "SELECT DISTINCT P.ID_PROVEEDOR,MSL.ID_ALMACEN_DESTINO AS ID_ALMACEN
                            FROM  MOVIMIENTO_SALIDA_LINEA MSL
                            INNER JOIN MOVIMIENTO_SALIDA MS_REL ON MSL.ID_MOVIMIENTO_SALIDA_MATERIAL_ESTROPEADO = MS_REL.ID_MOVIMIENTO_SALIDA
                            INNER JOIN PROVEEDOR P ON P.ID_PROVEEDOR = MS_REL.ID_PROVEEDOR
                            WHERE MSL.ID_EXPEDICION = $rowExpedicion->ID_EXPEDICION AND MSL.BAJA = 0";

            endif;

            $resultPedidoEntrada = $bd->ExecSQL($sqlPedidoEntrada);


            while ($rowPedidosEntrada = $bd->SigReg($resultPedidoEntrada)):

                //EL PROVEEDOR/CLIENTE DE ORIGEN LO SACAMOS DEL PEDIDO ENTRADA
                $rowPaisOrigen = false;
                if ($rowPedidosEntrada->ID_PROVEEDOR != ""):
                    //BUSCAMOS LA DIRECCION DEL PROVEEDOR
                    $sqlPaisOrigen    = "SELECT DISTINCT P.ID_PAIS, P.COMUNIDAD
                                                FROM DIRECCION D
                                                INNER JOIN PAIS P ON P.ID_PAIS = D.ID_PAIS
                                                WHERE D.TIPO_DIRECCION = 'Proveedor' AND D.ID_PROVEEDOR = $rowPedidosEntrada->ID_PROVEEDOR AND D.BAJA = 0";
                    $resultPaisOrigen = $bd->ExecSQL($sqlPaisOrigen);
                    $rowPaisOrigen    = $bd->SigReg($resultPaisOrigen);

                elseif ($rowPedidosEntrada->ID_CLIENTE != ""):
                    //BUSCAMOS LA DIRECCION DEL CLIENTE
                    $sqlPaisOrigen    = "SELECT DISTINCT P.ID_PAIS, P.COMUNIDAD
                                                FROM DIRECCION D
                                                INNER JOIN PAIS P ON P.ID_PAIS = D.ID_PAIS
                                                WHERE D.TIPO_DIRECCION = 'Cliente' AND D.ID_CLIENTE = $rowPedidosEntrada->ID_PROVEEDOR AND D.BAJA = 0";
                    $resultPaisOrigen = $bd->ExecSQL($sqlPaisOrigen);
                    $rowPaisOrigen    = $bd->SigReg($resultPaisOrigen);
                endif;

                //BUSCAMOS EL PAIS DEL ALMACEN DESTINO(CENTRO FISICO)
                $sqlPaisDestino    = "SELECT DISTINCT P.ID_PAIS, P.COMUNIDAD
                                                FROM ALMACEN A
                                                INNER JOIN DIRECCION D ON A.ID_CENTRO_FISICO = D.ID_CENTRO_FISICO
                                                INNER JOIN PAIS P ON P.ID_PAIS = D.ID_PAIS
                                                WHERE D.TIPO_DIRECCION = 'Centro Fisico' AND A.ID_ALMACEN = $rowPedidosEntrada->ID_ALMACEN AND D.BAJA = 0";
                $resultPaisDestino = $bd->ExecSQL($sqlPaisDestino);
                $rowPaisDestino    = $bd->SigReg($resultPaisDestino);

                if ($rowPaisDestino && $rowPaisOrigen):
                    //SI SON DISTINTOS MARCAMOS COMO INTERNACIONAL
                    if ($rowPaisDestino->ID_PAIS != $rowPaisOrigen->ID_PAIS):
                        $nacional = false;
                    endif;

                    //SI SON DISTINTAS COMUNIDADES MARCAMOS COMO EXTRACOMUNITARIO
                    if ($rowPaisDestino->COMUNIDAD != $rowPaisOrigen->COMUNIDAD):
                        $comunitario = false;
                    endif;
                endif;

            endwhile;


//**************RECOGIDA EN ALMACEN*****************//
        elseif ($rowExpedicion->TIPO_ORDEN_RECOGIDA == "Recogida en Almacen"):

            //SE DIVIDEN ENTRE CON BULTOS O SIN BULTOS
            if ($rowExpedicion->CON_BULTOS == 1):
                //BULTOS
                $sqlMSL = "SELECT DISTINCT MSL.ID_PEDIDO_SALIDA_LINEA, MSL.ID_PEDIDO_SALIDA, MSL.ID_ALMACEN
                FROM MOVIMIENTO_SALIDA_LINEA MSL
                INNER JOIN BULTO B ON B.ID_BULTO = MSL.ID_BULTO
                WHERE MSL.ID_EXPEDICION = $rowExpedicion->ID_EXPEDICION AND MSL.LINEA_ANULADA = 0 AND MSL.BAJA = 0";

            elseif ($rowExpedicion->CON_BULTOS == 0):
                //MOVIMIENTO_SALIDA
                $sqlMSL = "SELECT DISTINCT MSL.ID_PEDIDO_SALIDA_LINEA, MSL.ID_PEDIDO_SALIDA, MSL.ID_ALMACEN
                FROM MOVIMIENTO_SALIDA_LINEA MSL
                INNER JOIN MOVIMIENTO_SALIDA MS ON MS.ID_MOVIMIENTO_SALIDA = MSL.ID_MOVIMIENTO_SALIDA
                WHERE MSL.ID_EXPEDICION = $rowExpedicion->ID_EXPEDICION AND MSL.LINEA_ANULADA = 0 AND MSL.BAJA = 0";
            endif;

            $resultMSL = $bd->ExecSQL($sqlMSL);

            while ($rowMSL = $bd->SigReg($resultMSL)):

                //BUSCAMOS EL PAIS ORIGEN DEL ALMACEN
                $rowPaisOrigen = false;
                if ($rowMSL->ID_ALMACEN != ""):
                    //BUSCAMOS EL PAIS DEL ALMACEN DESTINO(CENTRO FISICO)
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

                //SI ES PEDIDO DE VENTA, ALMACENAMOS EL CLIENTE
                if ($rowPedidoSalida->TIPO_PEDIDO == 'Venta'):
                    //BUSCAMOS LA DIRECCION DEL CLIENTE
                    $sqlPaisDestino    = "SELECT DISTINCT P.ID_PAIS, P.COMUNIDAD
                                                FROM DIRECCION D
                                                INNER JOIN PAIS P ON P.ID_PAIS = D.ID_PAIS
                                                WHERE D.TIPO_DIRECCION = 'Cliente' AND D.ID_CLIENTE = $rowPedidoSalida->ID_CLIENTE AND D.BAJA = 0";
                    $resultPaisDestino = $bd->ExecSQL($sqlPaisDestino);
                    $rowPaisDestino    = $bd->SigReg($resultPaisDestino);

                //SI EL DESTINO DEL PEDIDO ES UN PROVEEDOR
                elseif (
                    ($rowPedidoSalida->TIPO_PEDIDO == 'Devolución a Proveedor') ||
                    ($rowPedidoSalida->TIPO_PEDIDO == 'Componentes a Proveedor') ||
                    ($rowPedidoSalida->TIPO_PEDIDO == 'Rechazos y Anulaciones a Proveedor') ||
                    ($rowPedidoSalida->TIPO_PEDIDO == 'Material Estropeado a Proveedor')
                ):
                    //BUSCAMOS LA DIRECCION DEL PROVEEDOR
                    $sqlPaisDestino    = "SELECT DISTINCT P.ID_PAIS, P.COMUNIDAD
                                                FROM DIRECCION D
                                                INNER JOIN PAIS P ON P.ID_PAIS = D.ID_PAIS
                                                WHERE D.TIPO_DIRECCION = 'Proveedor' AND D.ID_PROVEEDOR = $rowPedidoSalida->ID_PROVEEDOR AND D.BAJA = 0";
                    $resultPaisDestino = $bd->ExecSQL($sqlPaisDestino);
                    $rowPaisDestino    = $bd->SigReg($resultPaisDestino);
                //SI EL PEDIDO ES DE TRASLADO, ALMACENAMOS EL ALMACEN DEL PSL
                elseif (
                    ($rowPedidoSalida->TIPO_PEDIDO == 'Traslado') ||
                    ($rowPedidoSalida->TIPO_PEDIDO == 'Traslados OM Construccion') ||
                    ($rowPedidoSalida->TIPO_PEDIDO == 'Traspaso Entre Almacenes Material Estropeado') ||
                    ($rowPedidoSalida->TIPO_PEDIDO == 'Intra Centro Fisico') ||
                    ($rowPedidoSalida->TIPO_PEDIDO == 'Interno Gama') ||
                    ($rowPedidoSalida->TIPO_PEDIDO == 'Pendientes de Ordenes Trabajo')
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

                endif;


                if ($rowPaisDestino && $rowPaisOrigen):
                    //SI SON DISTINTOS MARCAMOS COMO INTERNACIONAL
                    if ($rowPaisDestino->ID_PAIS != $rowPaisOrigen->ID_PAIS):
                        $nacional = false;
                    endif;

                    //SI SON DISTINTAS COMUNIDADES MARCAMOS COMO EXTRACOMUNITARIO
                    if ($rowPaisDestino->COMUNIDAD != $rowPaisOrigen->COMUNIDAD):
                        $comunitario = false;
                    endif;
                endif;

            endwhile;

        endif; //FIN RECORRER TIPOS RECOGIDAS


        //ACTUALIZAMOS LA RECOGIDA
        $sqlUpdate = "UPDATE EXPEDICION SET
                        NACIONAL = '" . ($nacional == true ? "Nacional" : "Internacional") . "'
                        ,COMUNITARIO = '" . ($comunitario == true ? "Comunitario" : "Extracomunitario") . "'
                        WHERE ID_EXPEDICION = $rowExpedicion->ID_EXPEDICION";
        $bd->ExecSQL($sqlUpdate);


        //SI TIENE ORDEN DE TRANSPORTE, CALCULAMOS EL ADR
        if ($rowExpedicion->ID_ORDEN_TRANSPORTE != ""):
            $orden_transporte->calcularAmbitoYComunidadOrdenTransporte($rowExpedicion->ID_ORDEN_TRANSPORTE);
        endif;

    }

    /**
     * @param $objetoOrigen Objeto origen del que calcular su pais
     * @param $idObjetoOrigen Identificador del objeto origen del que calcular su pais
     * @param $objetoDestino Objeto destino del que calcular su pais
     * @param $idObjetoDestino Identificador del objeto destino del que calcular su pais
     * @return bool|string Valor del tipo de ambito retornado por la funcion (Nacional|Internacional), tambien puede ser false si alguno de los paises no existe
     */
    function calcularAmbito($objetoOrigen, $idObjetoOrigen, $objetoDestino, $idObjetoDestino)
    {
        global $bd;

        //VARIABLE PARA DETERMINAR SI EL AMBITO ENTRE ORIGEN Y DESTINO ES NACIONAL O NO
        $valorDevolver = false;

        //EN FUNCION DEL ORIGEN CALCULO EL PAIS DE ORIGEN
        switch ($objetoOrigen):
            case 'CENTRO_FISICO':
                //BUSCAMOS EL PAIS DEL CENTRO FISICO
                $sqlPaisOrigen    = "SELECT DISTINCT P.ID_PAIS
                                    FROM CENTRO_FISICO CF
                                    INNER JOIN DIRECCION D ON D.ID_CENTRO_FISICO = CF.ID_CENTRO_FISICO
                                    INNER JOIN PAIS P ON P.ID_PAIS = D.ID_PAIS
                                    WHERE D.TIPO_DIRECCION = 'Centro Fisico' AND CF.ID_CENTRO_FISICO = $idObjetoOrigen AND D.BAJA = 0";
                $resultPaisOrigen = $bd->ExecSQL($sqlPaisOrigen);
                $rowPaisOrigen    = $bd->SigReg($resultPaisOrigen);
                break;
        endswitch;

        //EN FUNCION DEL DESTINO CALCULO EL PAIS DE DESTINO
        switch ($objetoDestino):
            case 'ALMACEN':
                //BUSCAMOS EL PAIS DEL CENTRO FISICO DEL ALMACEN
                $sqlPaisDestino    = "SELECT DISTINCT P.ID_PAIS
                                    FROM ALMACEN A
                                    INNER JOIN DIRECCION D ON D.ID_CENTRO_FISICO = A.ID_CENTRO_FISICO
                                    INNER JOIN PAIS P ON P.ID_PAIS = D.ID_PAIS
                                    WHERE D.TIPO_DIRECCION = 'Centro Fisico' AND A.ID_ALMACEN = $idObjetoDestino AND D.BAJA = 0";
                $resultPaisDestino = $bd->ExecSQL($sqlPaisDestino);
                $rowPaisDestino    = $bd->SigReg($resultPaisDestino);
        endswitch;

        //COMPARAMOS EL PAIS DE ORIGEN CON EL DE DESTINO
        if ($rowPaisDestino && $rowPaisOrigen):
            //SI SON DISTINTOS MARCAMOS COMO INTERNACIONAL
            if ($rowPaisDestino->ID_PAIS == $rowPaisOrigen->ID_PAIS):
                $valorDevolver = 'Nacional';
            else:
                $valorDevolver = 'InterNacional';
            endif;
        endif;

        //DEVULVO LA VARIABLE A RETORNAR
        return $valorDevolver;
    }

    /**
     * @param $idExpedicion Recogida que analizar
     * @return array con las distintas direcciones de destino
     * Destinos de la Recogida en Almacen Sin Bultos
     */
    function obtenerDestinosRecogida($idExpedicion)
    {

        global $bd;
        global $auxiliar;

        //ARRAY PARA GUARDAR LOS DESTINOS SIN BULTO ASIGNADO
        $arrDestinos = array();

        //BUSCO LA ORDEN DE RECOGIDA
        $rowOrdenRecogida = $bd->VerReg("EXPEDICION", "ID_EXPEDICION", $idExpedicion);

        //RECOGIDAS EN ALMACEN SIN BULTOS
        if (($rowOrdenRecogida->TIPO_ORDEN_RECOGIDA == "Recogida en Almacen") && ($rowOrdenRecogida->CON_BULTOS == 0)):
            //MOVIMIENTO_SALIDA
            $sqlMSL    = "SELECT DISTINCT MSL.ID_PEDIDO_SALIDA_LINEA, MSL.ID_PEDIDO_SALIDA, MSL.ID_ALMACEN
                            FROM MOVIMIENTO_SALIDA_LINEA MSL
                            INNER JOIN MOVIMIENTO_SALIDA MS ON MS.ID_MOVIMIENTO_SALIDA = MSL.ID_MOVIMIENTO_SALIDA
                            WHERE MSL.ID_EXPEDICION = $rowOrdenRecogida->ID_EXPEDICION AND MSL.LINEA_ANULADA = 0 AND MSL.BAJA = 0";
            $resultMSL = $bd->ExecSQL($sqlMSL);

            while ($rowMSL = $bd->SigReg($resultMSL)):

                //BUSCAMOS EL TIPO DE PEDIDO PARA VER EL DESTINO
                $GLOBALS["NotificaErrorPorEmail"] = "No";
                $rowPedidoSalida                  = $bd->VerReg("PEDIDO_SALIDA", "ID_PEDIDO_SALIDA", $rowMSL->ID_PEDIDO_SALIDA, "No");

                //SI ES PEDIDO DE VENTA, ALMACENAMOS EL CLIENTE
                if ($rowPedidoSalida->TIPO_PEDIDO == 'Venta'):
                    //BUSCAMOS LA DIRECCION DEL CLIENTE
                    $sqlDireccionDestino    = "SELECT DISTINCT D.ID_DIRECCION
                                                FROM DIRECCION D
                                                WHERE D.TIPO_DIRECCION = 'Cliente' AND D.ID_CLIENTE = $rowPedidoSalida->ID_CLIENTE AND D.BAJA = 0";
                    $resultDireccionDestino = $bd->ExecSQL($sqlDireccionDestino);
                    $rowDireccionDestino    = $bd->SigReg($resultDireccionDestino);

                //SI EL DESTINO DEL PEDIDO ES UN PROVEEDOR
                elseif (
                    ($rowPedidoSalida->TIPO_PEDIDO == 'Devolución a Proveedor') ||
                    ($rowPedidoSalida->TIPO_PEDIDO == 'Componentes a Proveedor') ||
                    ($rowPedidoSalida->TIPO_PEDIDO == 'Rechazos y Anulaciones a Proveedor') ||
                    ($rowPedidoSalida->TIPO_PEDIDO == 'Material Estropeado a Proveedor')
                ):
                    //BUSCAMOS LA DIRECCION DEL PROVEEDOR
                    $sqlDireccionDestino    = "SELECT DISTINCT D.ID_DIRECCION
                                                FROM DIRECCION D
                                                WHERE D.TIPO_DIRECCION = 'Proveedor' AND D.ID_PROVEEDOR = $rowPedidoSalida->ID_PROVEEDOR AND D.BAJA = 0";
                    $resultDireccionDestino = $bd->ExecSQL($sqlDireccionDestino);
                    $rowDireccionDestino    = $bd->SigReg($resultDireccionDestino);
//                    //SE BUSCA LA DIRECCION DE ENVIO A TRAVES DE LA ORDEN DE TRABAJO
//                    $NotificaErrorPorEmail = "No";
//                    $rowMatUbi = $bd->VerRegRest("MATERIAL_UBICACION", "ID_MATERIAL = $rowMSL->ID_MATERIAL AND ID_UBICACION = $rowMSL->ID_UBICACION", "No");
//                    unset($NotificaErrorPorEmail);
//                    $NotificaErrorPorEmail = "No";
//                    $rowOrdenTrabajoMov = $bd->VerReg("ORDEN_TRABAJO_MOVIMIENTO", "ID_ORDEN_TRABAJO_MOVIMIENTO", $rowMatUbi->ID_ORDEN_TRABAJO_MOVIMIENTO, "No");
//                    unset($NotificaErrorPorEmail);
//                    //BUSCAMOS LA DIRECCION DEL PROVEEDOR
//                    if($rowOrdenTrabajoMov != false && $rowOrdenTrabajoMov->ID_PROVEEDOR_GARANTIA != NULL):
//                        $sqlDireccionDestino    = "SELECT DISTINCT D.ID_DIRECCION
//                                                   FROM DIRECCION D
//                                                   WHERE D.TIPO_DIRECCION = 'Proveedor' AND D.ID_PROVEEDOR = $rowOrdenTrabajoMov->ID_PROVEEDOR_GARANTIA AND D.BAJA = 0";
//                    else:
//                    $sqlDireccionDestino    = "SELECT DISTINCT D.ID_DIRECCION
//                                                FROM DIRECCION D
//                                                WHERE D.TIPO_DIRECCION = 'Proveedor' AND D.ID_PROVEEDOR = $rowPedidoSalida->ID_PROVEEDOR AND D.BAJA = 0";
//                    endif;
//                    $resultDireccionDestino = $bd->ExecSQL($sqlDireccionDestino);
//                    $rowDireccionDestino    = $bd->SigReg($resultDireccionDestino);

                //SI EL PEDIDO ES DE TRASLADO, ALMACENAMOS EL ALMACEN DEL PSL
                elseif (
                    ($rowPedidoSalida->TIPO_PEDIDO == 'Traslado') ||
                    ($rowPedidoSalida->TIPO_PEDIDO == 'Traslados OM Construccion') ||
                    ($rowPedidoSalida->TIPO_PEDIDO == 'Traspaso Entre Almacenes Material Estropeado') ||
                    ($rowPedidoSalida->TIPO_PEDIDO == 'Intra Centro Fisico') ||
                    ($rowPedidoSalida->TIPO_PEDIDO == 'Interno Gama') ||
                    ($rowPedidoSalida->TIPO_PEDIDO == 'Pendientes de Ordenes Trabajo')
                ):
                    //BUSCAMOS EL PSL
                    $rowPedidoSalidaLinea = $bd->VerReg("PEDIDO_SALIDA_LINEA", "ID_PEDIDO_SALIDA_LINEA", $rowMSL->ID_PEDIDO_SALIDA_LINEA, "No");

                    //BUSCAMOS LA DIRECCION DEL ALMACEN DESTINO(CENTRO FISICO)
                    $sqlDireccionDestino    = "SELECT DISTINCT D.ID_DIRECCION
                                                    FROM ALMACEN A
                                                    INNER JOIN DIRECCION D ON A.ID_CENTRO_FISICO = D.ID_CENTRO_FISICO
                                                    WHERE D.TIPO_DIRECCION = 'Centro Fisico' AND A.ID_ALMACEN = $rowPedidoSalidaLinea->ID_ALMACEN_DESTINO AND D.BAJA = 0";
                    $resultDireccionDestino = $bd->ExecSQL($sqlDireccionDestino);
                    $rowDireccionDestino    = $bd->SigReg($resultDireccionDestino);
                endif;

                //NOS GUARDAMOS LA DIRECCION EN EL ARRAY
                if ($rowDireccionDestino->ID_DIRECCION != NULL):
                    $arrDestinos[] = $rowDireccionDestino->ID_DIRECCION;
                endif;

            endwhile;

        elseif (($rowOrdenRecogida->TIPO_ORDEN_RECOGIDA == "Recogida en Proveedor") && ($rowOrdenRecogida->SUBTIPO_ORDEN_RECOGIDA == "Con Pedido Conocido")):
            //PEDIDO CONOCIDO
            $sqlPedidoEntrada    = "SELECT DISTINCT PEL.ID_ALMACEN
                                    FROM EXPEDICION_PEDIDO_CONOCIDO EPC
                                    INNER JOIN PEDIDO_ENTRADA_LINEA PEL ON PEL.ID_PEDIDO_ENTRADA_LINEA = EPC.ID_PEDIDO_ENTRADA_LINEA
                                    WHERE EPC.ID_EXPEDICION = $rowOrdenRecogida->ID_EXPEDICION AND EPC.BAJA = 0";
            $resultPedidoEntrada = $bd->ExecSQL($sqlPedidoEntrada);


            while ($rowPedidosEntrada = $bd->SigReg($resultPedidoEntrada)):

                //BUSCAMOS LA DIRECCION DEL ALMACEN DESTINO(CENTRO FISICO)
                $sqlDireccionDestino    = "SELECT DISTINCT D.ID_DIRECCION
                                                FROM ALMACEN A
                                                INNER JOIN DIRECCION D ON A.ID_CENTRO_FISICO = D.ID_CENTRO_FISICO
                                                WHERE D.TIPO_DIRECCION = 'Centro Fisico' AND A.ID_ALMACEN = $rowPedidosEntrada->ID_ALMACEN AND D.BAJA = 0";
                $resultDireccionDestino = $bd->ExecSQL($sqlDireccionDestino);
                $rowDireccionDestino    = $bd->SigReg($resultDireccionDestino);

                //NOS GUARDAMOS LA DIRECCION EN EL ARRAY
                if ($rowDireccionDestino->ID_DIRECCION != NULL):
                    $arrDestinos[] = $rowDireccionDestino->ID_DIRECCION;
                endif;

            endwhile;

        endif;//FIN RECOGIDAS EN ALMACEN SIN BULTOS

        //UNIFICAMOS VALORES
        $arrDestinos = array_unique( (array)$arrDestinos);

        return $arrDestinos;
    }

    /**
     * @param $idExpedicion
     * Devuelve SI LA EXPEDICION TIENE AL MENOS UN BULTO (ANTES BUSCABA UN BULTO POR DESTINO PERO PUEDE PASAR QUE AGRUPEN EN UN BULTO VARIOS DESTINOS)
     */
    function RecogidaSinBultoAsignado($idExpedicion)
    {

        global $bd;
        global $auxiliar;
        global $expedicion;


        //BUSCO LA ORDEN DE RECOGIDA
        $rowOrdenRecogida = $bd->VerReg("EXPEDICION", "ID_EXPEDICION", $idExpedicion);

        $recogidaSinBulto = false;
        //BUSCAMOS SI HAY UN BULTO PARA ESA RECOGIDA
        $sqlBultos    = "SELECT ID_BULTO
                            FROM BULTO B
                            INNER JOIN EXPEDICION E ON E.ID_EXPEDICION = B.ID_EXPEDICION
                            WHERE E.ID_EXPEDICION = $rowOrdenRecogida->ID_EXPEDICION ";
        $resultBultos = $bd->ExecSQL($sqlBultos);

        //SI NO HAY NINGUN BULTO CON ESE DESTINO
        if ($bd->NumRegs($resultBultos) == 0):
            $recogidaSinBulto = true;
        endif;


        return $recogidaSinBulto;
    }

    /**
     * @param $idExpedicion
     * Devuelve un array con los destinos sin Bulto
     */
    function DestinoRecogidaSinBulto($idExpedicion)
    {

        global $bd;
        global $auxiliar;
        global $expedicion;


        //BUSCO LA ORDEN DE RECOGIDA
        $rowOrdenRecogida = $bd->VerReg("EXPEDICION", "ID_EXPEDICION", $idExpedicion);

        //OBTENEMOS LOS DESTINOS
        $arrDestinos = $expedicion->obtenerDestinosRecogida($rowOrdenRecogida->ID_EXPEDICION);

        //VARIABLE A RETORNAR
        $arrDestinosSinBulto = array();

        //SI NO TIENE DESTINOS PREFIJADOS, COMPROBAMOS QUE AL MENOS EXISTA UN BULTO
        if (count( (array)$arrDestinos) > 0):
            //RECORREMOS LOS DESTINOS
            foreach ($arrDestinos as $idDestino):
                //BUSCAMOS SI HAY UN BULTO PARA ESE DESTINO
                $sqlBultos    = "SELECT ID_BULTO
                                FROM BULTO B
                                INNER JOIN EXPEDICION E ON E.ID_EXPEDICION = B.ID_EXPEDICION
                                WHERE E.ID_EXPEDICION = $rowOrdenRecogida->ID_EXPEDICION AND ( (B.ID_DIRECCION_DESTINO = $idDestino AND ID_DIRECCION_DESTINO_ORIGINAL IS NULL) OR B.ID_DIRECCION_DESTINO_ORIGINAL = $idDestino )";
                $resultBultos = $bd->ExecSQL($sqlBultos);

                //SI NO HAY NINGUN BULTO CON ESE DESTINO
                if ($bd->NumRegs($resultBultos) == 0):
                    $arrDestinosSinBulto[] = $idDestino;
                endif;
            endforeach;
        endif;

        //UNIFICAMOS VALORES
        $arrDestinosSinBulto = array_unique( (array)$arrDestinosSinBulto);

        return $arrDestinosSinBulto;
    }

    /**
     * @param $idExpedicion Recogida que analizar
     * @return idCentro que deberia contrataar
     * Devuelve el centro contratante de una Recogida. Sera el centro de destino cuya sociedad tenga gestion de transporte (para traslados), para ventas y proveedor centro origen.
     */
    function obtenerCentroContratanteRecogida($idExpedicion)
    {

        global $bd;
        global $auxiliar;

        //ARRAY PARA GUARDAR LOS DESTINOS SIN BULTO ASIGNADO
        $idCentro = "";

        //BUSCO LA ORDEN DE RECOGIDA
        $rowOrdenRecogida = $bd->VerReg("EXPEDICION", "ID_EXPEDICION", $idExpedicion);

        /****RECOGIDA EN ALMACEN****/
        if ($rowOrdenRecogida->TIPO_ORDEN_RECOGIDA == "Recogida en Almacen"):

            //SE DIVIDEN ENTRE CON BULTOS O SIN BULTOS
            if ($rowOrdenRecogida->CON_BULTOS == 1):
                //BULTOS
                $sqlMSL = "SELECT DISTINCT MSL.ID_ALMACEN, PS.TIPO_PEDIDO, MSL.ID_ALMACEN_DESTINO
                            FROM MOVIMIENTO_SALIDA_LINEA MSL
                            INNER JOIN BULTO B ON B.ID_BULTO = MSL.ID_BULTO
                            INNER JOIN PEDIDO_SALIDA PS ON PS.ID_PEDIDO_SALIDA = MSL.ID_PEDIDO_SALIDA
                            WHERE MSL.ID_EXPEDICION = $rowOrdenRecogida->ID_EXPEDICION AND MSL.LINEA_ANULADA = 0 AND MSL.BAJA = 0";

            elseif ($rowOrdenRecogida->CON_BULTOS == 0):
                //MOVIMIENTO_SALIDA
                $sqlMSL = "SELECT DISTINCT MSL.ID_ALMACEN, PS.TIPO_PEDIDO, MSL.ID_ALMACEN_DESTINO
                            FROM MOVIMIENTO_SALIDA_LINEA MSL
                            INNER JOIN MOVIMIENTO_SALIDA MS ON MS.ID_MOVIMIENTO_SALIDA = MSL.ID_MOVIMIENTO_SALIDA
                            INNER JOIN PEDIDO_SALIDA PS ON PS.ID_PEDIDO_SALIDA = MSL.ID_PEDIDO_SALIDA
                            WHERE MSL.ID_EXPEDICION = $rowOrdenRecogida->ID_EXPEDICION AND MSL.LINEA_ANULADA = 0 AND MSL.BAJA = 0";
            endif;
            $resultMSL = $bd->ExecSQL($sqlMSL);

            //BUSCAMOS LAS DIRECCIONES DE DESTINO
            $idAlmacenOrigen = "";
            while ($rowMSL = $bd->SigReg($resultMSL)):
                //GUARDAMOS EL ALMACEN ORIGEN POR SI EL DESTINO NO TIENE GT
                $idAlmacenOrigen = $rowMSL->ID_ALMACEN;

                //SI EL PEDIDO ES DE TRASLADO, UTILIZAMOS SOCIEDAD DESTINO
                if (
                    ($rowMSL->TIPO_PEDIDO == 'Traslado') ||
                    ($rowMSL->TIPO_PEDIDO == 'Traslados OM Construccion') ||
                    ($rowMSL->TIPO_PEDIDO == 'Traspaso Entre Almacenes Material Estropeado') ||
                    ($rowMSL->TIPO_PEDIDO == 'Intra Centro Fisico') ||
                    ($rowMSL->TIPO_PEDIDO == 'Interno Gama') ||
                    ($rowMSL->TIPO_PEDIDO == 'Pendientes de Ordenes Trabajo')
                ):
                    //TOMAMOS LA SOCIEDAD DEL ALMACEN DE DESTINO
                    $rowAlmacenDestino  = $bd->VerReg("ALMACEN", "ID_ALMACEN", $rowMSL->ID_ALMACEN_DESTINO);
                    $rowCentroDestino   = $bd->VerReg("CENTRO", "ID_CENTRO", $rowAlmacenDestino->ID_CENTRO);
                    $rowSociedadDestino = $bd->VerReg("SOCIEDAD", "ID_SOCIEDAD", $rowCentroDestino->ID_SOCIEDAD);

                    //DEVOLVEMOS LA SOCIEDAD ORIGEN DE LA VENTA SI NO TIENE GESTION DE TRANSPORTE
                    if ($rowSociedadDestino->GESTION_TRANSPORTE == 1):
                        return $rowCentroDestino->ID_CENTRO;
                    endif;
                endif;
            endwhile;

            //SI NO HEMOS ENCONTRADO SOCIEDAD DESTINO CON GESTION DE TRANSPORTE, INTENTAMOS CON LA DE ORIGEN
            if ($idAlmacenOrigen != ""):
                //TOMAMOS LA SOCIEDAD DEL ALMACEN DE ORIGEN
                $rowAlmacenDestino  = $bd->VerReg("ALMACEN", "ID_ALMACEN", $idAlmacenOrigen);
                $rowCentroDestino   = $bd->VerReg("CENTRO", "ID_CENTRO", $rowAlmacenDestino->ID_CENTRO);
                $rowSociedadDestino = $bd->VerReg("SOCIEDAD", "ID_SOCIEDAD", $rowCentroDestino->ID_SOCIEDAD);

                //DEVOLVEMOS LA SOCIEDAD ORIGEN DE LA VENTA SI TIENE GESTION DE TRANSPORTE
                if ($rowSociedadDestino->GESTION_TRANSPORTE == 1):
                    return $rowCentroDestino->ID_CENTRO;
                endif;
            endif;

        /****RECOGIDA EN PROVEEDOR****/
        elseif ($rowOrdenRecogida->TIPO_ORDEN_RECOGIDA == "Recogida en Proveedor"):

            if ($rowOrdenRecogida->SUBTIPO_ORDEN_RECOGIDA == 'Sin Pedido Conocido'):
                //MOVIMIENTO ENTRADA
                $sqlPedidoEntrada = "SELECT DISTINCT PE.ID_PROVEEDOR,PE.ID_CLIENTE,PEL.ID_ALMACEN
                                        FROM MOVIMIENTO_ENTRADA ME
                                        INNER JOIN MOVIMIENTO_ENTRADA_LINEA MEL ON MEL.ID_MOVIMIENTO_ENTRADA=ME.ID_MOVIMIENTO_ENTRADA
                                        INNER JOIN PEDIDO_ENTRADA PE ON PE.ID_PEDIDO_ENTRADA=MEL.ID_PEDIDO
                                        INNER JOIN PEDIDO_ENTRADA_LINEA PEL ON PEL.ID_PEDIDO_ENTRADA_LINEA=MEL.ID_PEDIDO_LINEA
                                        WHERE MEL.ID_EXPEDICION_ENTREGA = $rowOrdenRecogida->ID_EXPEDICION AND ME.BAJA = 0";

            elseif ($rowOrdenRecogida->SUBTIPO_ORDEN_RECOGIDA == 'Con Pedido Conocido'):
                //PEDIDO CONOCIDO
                $sqlPedidoEntrada = "SELECT DISTINCT PE.ID_PROVEEDOR,PE.ID_CLIENTE,PEL.ID_ALMACEN
                                        FROM EXPEDICION_PEDIDO_CONOCIDO EPC
                                        INNER JOIN PEDIDO_ENTRADA PE ON PE.ID_PEDIDO_ENTRADA = EPC.ID_PEDIDO_ENTRADA
                                        INNER JOIN PEDIDO_ENTRADA_LINEA PEL ON PEL.ID_PEDIDO_ENTRADA_LINEA = EPC.ID_PEDIDO_ENTRADA_LINEA
                                        WHERE EPC.ID_EXPEDICION = $rowOrdenRecogida->ID_EXPEDICION AND EPC.BAJA = 0";

            elseif ($rowOrdenRecogida->SUBTIPO_ORDEN_RECOGIDA == 'Retorno Material Estropeado desde Proveedor'):
                //MOVIMIENTO SALIDA LINEA RELACIONADO CON EL MOVIMIENTO DE MATERIAL ESTROPEADO
                $sqlPedidoEntrada = "SELECT DISTINCT P.ID_PROVEEDOR,MSL.ID_ALMACEN_DESTINO AS ID_ALMACEN
                                        FROM  MOVIMIENTO_SALIDA_LINEA MSL
                                        INNER JOIN MOVIMIENTO_SALIDA MS_REL ON MSL.ID_MOVIMIENTO_SALIDA_MATERIAL_ESTROPEADO = MS_REL.ID_MOVIMIENTO_SALIDA
                                        INNER JOIN PROVEEDOR P ON P.ID_PROVEEDOR = MS_REL.ID_PROVEEDOR
                                        WHERE MSL.ID_EXPEDICION = $rowOrdenRecogida->ID_EXPEDICION AND MSL.BAJA = 0";

            endif;
            $resultPedidoEntrada = $bd->ExecSQL($sqlPedidoEntrada);

            while ($rowPedidosEntrada = $bd->SigReg($resultPedidoEntrada)):
                //TOMAMOS LA SOCIEDAD DEL ALMACEN DE DESTINO
                $rowAlmacenDestino  = $bd->VerReg("ALMACEN", "ID_ALMACEN", $rowPedidosEntrada->ID_ALMACEN);
                $rowCentroDestino   = $bd->VerReg("CENTRO", "ID_CENTRO", $rowAlmacenDestino->ID_CENTRO);
                $rowSociedadDestino = $bd->VerReg("SOCIEDAD", "ID_SOCIEDAD", $rowCentroDestino->ID_SOCIEDAD);

                //DEVOLVEMOS LA SOCIEDAD ORIGEN DE LA VENTA SI NO TIENE GESTION DE TRANSPORTE
                if ($rowSociedadDestino->GESTION_TRANSPORTE == 1):
                    return $rowCentroDestino->ID_CENTRO;
                endif;
            endwhile;

        /****OPERACIONES EN PARQUE****/
        elseif ($rowOrdenRecogida->TIPO_ORDEN_RECOGIDA == "Operaciones en Parque"):

            //BUSCAMOS LAS LINEAS
            $sqlOTM    = "SELECT DISTINCT U.ID_ALMACEN
                            FROM ORDEN_TRABAJO_MOVIMIENTO OTM
                            INNER JOIN ORDEN_TRABAJO OT ON OT.ID_ORDEN_TRABAJO = OTM.ID_ORDEN_TRABAJO
                            INNER JOIN UBICACION U ON U.ID_UBICACION = OTM.ID_UBICACION
                            WHERE OTM.ID_EXPEDICION = $rowOrdenRecogida->ID_EXPEDICION AND OTM.BAJA= 0";
            $resultOTM = $bd->ExecSQL($sqlOTM);
            while ($rowOTM = $bd->SigReg($resultOTM)):
                //TOMAMOS LA SOCIEDAD DEL ALMACEN DE DESTINO
                $rowAlmacenDestino  = $bd->VerReg("ALMACEN", "ID_ALMACEN", $rowOTM->ID_ALMACEN);
                $rowCentroDestino   = $bd->VerReg("CENTRO", "ID_CENTRO", $rowAlmacenDestino->ID_CENTRO);
                $rowSociedadDestino = $bd->VerReg("SOCIEDAD", "ID_SOCIEDAD", $rowCentroDestino->ID_SOCIEDAD);

                //DEVOLVEMOS LA SOCIEDAD ORIGEN DE LA VENTA SI NO TIENE GESTION DE TRANSPORTE
                if ($rowSociedadDestino->GESTION_TRANSPORTE == 1):
                    return $rowCentroDestino->ID_CENTRO;
                endif;
            endwhile;

        /****OPERACIONES EN PARQUE****/
        elseif ($rowOrdenRecogida->TIPO_ORDEN_RECOGIDA == "Operaciones fuera de Sistema"):

            //BUSCAMOS LA DIRECCION DE ORIGEN Y DESTINO DE LOS BULTOS
            /*$sqlBultos    = "SELECT ID_DIRECCION_ORIGEN, ID_DIRECCION_DESTINO
                                FROM BULTO
                                WHERE ID_EXPEDICION = $rowOrdenRecogida->ID_EXPEDICION";
            $resultBultos = $bd->ExecSQL($sqlBultos);

            while ($rowBulto = $bd->SigReg($resultBultos)):

            endwhile;*/

        endif;//FIN RECORRER TIPOS RECOGIDAS

        //SI NO HEMOS ENCONTRADO SOCIEDAD SIN GASTOS DEVOLVEMOS VACIO
        return false;
    }

    /**
     * @param $idExpedicion Recogida que analizar
     * @return idSociedad que deberia contrataar
     * Devuelve la sociedad contratante de una Recogida. Sera la sociedad de destino con gestion de transporte (para traslados), para ventas y proveedor sociedad origen.
     */
    function obtenerSociedadContratanteRecogida($idExpedicion)
    {

        global $bd;
        global $auxiliar;

        //ARRAY PARA GUARDAR LOS DESTINOS SIN BULTO ASIGNADO
        $idSociedad = "";

        //BUSCO LA ORDEN DE RECOGIDA
        $rowOrdenRecogida = $bd->VerReg("EXPEDICION", "ID_EXPEDICION", $idExpedicion);

        /****RECOGIDA EN ALMACEN****/
        if ($rowOrdenRecogida->TIPO_ORDEN_RECOGIDA == "Recogida en Almacen"):

            //SE DIVIDEN ENTRE CON BULTOS O SIN BULTOS
            if ($rowOrdenRecogida->CON_BULTOS == 1):
                //BULTOS
                $sqlMSL = "SELECT DISTINCT MSL.ID_ALMACEN, PS.TIPO_PEDIDO, MSL.ID_ALMACEN_DESTINO
                FROM MOVIMIENTO_SALIDA_LINEA MSL
                INNER JOIN BULTO B ON B.ID_BULTO = MSL.ID_BULTO
                INNER JOIN PEDIDO_SALIDA PS ON PS.ID_PEDIDO_SALIDA = MSL.ID_PEDIDO_SALIDA
                WHERE MSL.ID_EXPEDICION = $rowOrdenRecogida->ID_EXPEDICION AND MSL.LINEA_ANULADA = 0 AND MSL.BAJA = 0";

            elseif ($rowOrdenRecogida->CON_BULTOS == 0):
                //MOVIMIENTO_SALIDA
                $sqlMSL = "SELECT DISTINCT MSL.ID_ALMACEN, PS.TIPO_PEDIDO, MSL.ID_ALMACEN_DESTINO
                                FROM MOVIMIENTO_SALIDA_LINEA MSL
                                INNER JOIN MOVIMIENTO_SALIDA MS ON MS.ID_MOVIMIENTO_SALIDA = MSL.ID_MOVIMIENTO_SALIDA
                                INNER JOIN PEDIDO_SALIDA PS ON PS.ID_PEDIDO_SALIDA = MSL.ID_PEDIDO_SALIDA
                                WHERE MSL.ID_EXPEDICION = $rowOrdenRecogida->ID_EXPEDICION AND MSL.LINEA_ANULADA = 0 AND MSL.BAJA = 0";
            endif;
            $resultMSL = $bd->ExecSQL($sqlMSL);


            //BUSCAMOS LAS DIRECCIONES DE DESTINO
            $idAlmacenOrigen = "";
            while ($rowMSL = $bd->SigReg($resultMSL)):

                //GUARDAMOS EL ALMACEN ORIGEN POR SI EL DESTINO NO TIENE GT
                $idAlmacenOrigen = $rowMSL->ID_ALMACEN;

                //SI EL PEDIDO ES DE TRASLADO, UTILIZAMOS SOCIEDAD DESTINO
                if (
                    ($rowMSL->TIPO_PEDIDO == 'Traslado') ||
                    ($rowMSL->TIPO_PEDIDO == 'Traslados OM Construccion') ||
                    ($rowMSL->TIPO_PEDIDO == 'Traspaso Entre Almacenes Material Estropeado') ||
                    ($rowMSL->TIPO_PEDIDO == 'Intra Centro Fisico') ||
                    ($rowMSL->TIPO_PEDIDO == 'Interno Gama') ||
                    ($rowMSL->TIPO_PEDIDO == 'Pendientes de Ordenes Trabajo')
                ):
                    //TOMAMOS LA SOCIEDAD DEL ALMACEN DE DESTINO
                    $rowAlmacenDestino  = $bd->VerReg("ALMACEN", "ID_ALMACEN", $rowMSL->ID_ALMACEN_DESTINO);
                    $rowCentroDestino   = $bd->VerReg("CENTRO", "ID_CENTRO", $rowAlmacenDestino->ID_CENTRO);
                    $rowSociedadDestino = $bd->VerReg("SOCIEDAD", "ID_SOCIEDAD", $rowCentroDestino->ID_SOCIEDAD);

                    //DEVOLVEMOS LA SOCIEDAD ORIGEN DE LA VENTA SI NO TIENE GESTION DE TRANSPORTE
                    if ($rowSociedadDestino->GESTION_TRANSPORTE == 1):
                        return $rowSociedadDestino->ID_SOCIEDAD;
                    endif;
                endif;
            endwhile;

            //SI NO HEMOS ENCONTRADO SOCIEDAD DESTINO CON GESTION DE TRANSPORTE, INTENTAMOS CON LA DE ORIGEN
            if ($idAlmacenOrigen != ""):
                //TOMAMOS LA SOCIEDAD DEL ALMACEN DE ORIGEN
                $rowAlmacenDestino  = $bd->VerReg("ALMACEN", "ID_ALMACEN", $idAlmacenOrigen);
                $rowCentroDestino   = $bd->VerReg("CENTRO", "ID_CENTRO", $rowAlmacenDestino->ID_CENTRO);
                $rowSociedadDestino = $bd->VerReg("SOCIEDAD", "ID_SOCIEDAD", $rowCentroDestino->ID_SOCIEDAD);

                //DEVOLVEMOS LA SOCIEDAD ORIGEN DE LA VENTA SI TIENE GESTION DE TRANSPORTE
                if ($rowSociedadDestino->GESTION_TRANSPORTE == 1):
                    return $rowSociedadDestino->ID_SOCIEDAD;
                endif;
            endif;

        /****RECOGIDA EN PROVEEDOR****/
        elseif ($rowOrdenRecogida->TIPO_ORDEN_RECOGIDA == "Recogida en Proveedor"):

            if ($rowOrdenRecogida->SUBTIPO_ORDEN_RECOGIDA == 'Sin Pedido Conocido'):
                //MOVIMIENTO ENTRADA
                $sqlPedidoEntrada = "SELECT DISTINCT PE.ID_PROVEEDOR,PE.ID_CLIENTE,PEL.ID_ALMACEN
                            FROM MOVIMIENTO_ENTRADA ME
                            INNER JOIN MOVIMIENTO_ENTRADA_LINEA MEL ON MEL.ID_MOVIMIENTO_ENTRADA=ME.ID_MOVIMIENTO_ENTRADA
                            INNER JOIN PEDIDO_ENTRADA PE ON PE.ID_PEDIDO_ENTRADA=MEL.ID_PEDIDO
                            INNER JOIN PEDIDO_ENTRADA_LINEA PEL ON PEL.ID_PEDIDO_ENTRADA_LINEA=MEL.ID_PEDIDO_LINEA
                            WHERE MEL.ID_EXPEDICION_ENTREGA = $rowOrdenRecogida->ID_EXPEDICION AND ME.BAJA = 0";

            elseif ($rowOrdenRecogida->SUBTIPO_ORDEN_RECOGIDA == 'Con Pedido Conocido'):
                //PEDIDO CONOCIDO
                $sqlPedidoEntrada = "SELECT DISTINCT PE.ID_PROVEEDOR,PE.ID_CLIENTE,PEL.ID_ALMACEN
                            FROM EXPEDICION_PEDIDO_CONOCIDO EPC
                            INNER JOIN PEDIDO_ENTRADA PE ON PE.ID_PEDIDO_ENTRADA = EPC.ID_PEDIDO_ENTRADA
                            INNER JOIN PEDIDO_ENTRADA_LINEA PEL ON PEL.ID_PEDIDO_ENTRADA_LINEA = EPC.ID_PEDIDO_ENTRADA_LINEA
                            WHERE EPC.ID_EXPEDICION = $rowOrdenRecogida->ID_EXPEDICION AND EPC.BAJA = 0";

            elseif ($rowOrdenRecogida->SUBTIPO_ORDEN_RECOGIDA == 'Retorno Material Estropeado desde Proveedor'):
                //MOVIMIENTO SALIDA LINEA RELACIONADO CON EL MOVIMIENTO DE MATERIAL ESTROPEADO
                $sqlPedidoEntrada = "SELECT DISTINCT P.ID_PROVEEDOR,MSL.ID_ALMACEN_DESTINO AS ID_ALMACEN
                            FROM  MOVIMIENTO_SALIDA_LINEA MSL
                            INNER JOIN MOVIMIENTO_SALIDA MS_REL ON MSL.ID_MOVIMIENTO_SALIDA_MATERIAL_ESTROPEADO = MS_REL.ID_MOVIMIENTO_SALIDA
                            INNER JOIN PROVEEDOR P ON P.ID_PROVEEDOR = MS_REL.ID_PROVEEDOR
                            WHERE MSL.ID_EXPEDICION = $rowOrdenRecogida->ID_EXPEDICION AND MSL.BAJA = 0";

            endif;
            $resultPedidoEntrada = $bd->ExecSQL($sqlPedidoEntrada);

            while ($rowPedidosEntrada = $bd->SigReg($resultPedidoEntrada)):
                //TOMAMOS LA SOCIEDAD DEL ALMACEN DE DESTINO
                $rowAlmacenDestino  = $bd->VerReg("ALMACEN", "ID_ALMACEN", $rowPedidosEntrada->ID_ALMACEN);
                $rowCentroDestino   = $bd->VerReg("CENTRO", "ID_CENTRO", $rowAlmacenDestino->ID_CENTRO);
                $rowSociedadDestino = $bd->VerReg("SOCIEDAD", "ID_SOCIEDAD", $rowCentroDestino->ID_SOCIEDAD);

                //DEVOLVEMOS LA SOCIEDAD ORIGEN DE LA VENTA SI NO TIENE GESTION DE TRANSPORTE
                if ($rowSociedadDestino->GESTION_TRANSPORTE == 1):
                    return $rowSociedadDestino->ID_SOCIEDAD;
                endif;
            endwhile;

        /****OPERACIONES EN PARQUE****/
        elseif ($rowOrdenRecogida->TIPO_ORDEN_RECOGIDA == "Operaciones en Parque"):

            //BUSCAMOS LAS LINEAS
            $sqlOTM    = "SELECT DISTINCT U.ID_ALMACEN
                                    FROM ORDEN_TRABAJO_MOVIMIENTO OTM
                                    INNER JOIN ORDEN_TRABAJO OT ON OT.ID_ORDEN_TRABAJO = OTM.ID_ORDEN_TRABAJO
                                    INNER JOIN UBICACION U ON U.ID_UBICACION = OTM.ID_UBICACION
                                    WHERE OTM.ID_EXPEDICION = $rowOrdenRecogida->ID_EXPEDICION AND OTM.BAJA= 0";
            $resultOTM = $bd->ExecSQL($sqlOTM);


            while ($rowOTM = $bd->SigReg($resultOTM)):

                //TOMAMOS LA SOCIEDAD DEL ALMACEN DE DESTINO
                $rowAlmacenDestino  = $bd->VerReg("ALMACEN", "ID_ALMACEN", $rowOTM->ID_ALMACEN);
                $rowCentroDestino   = $bd->VerReg("CENTRO", "ID_CENTRO", $rowAlmacenDestino->ID_CENTRO);
                $rowSociedadDestino = $bd->VerReg("SOCIEDAD", "ID_SOCIEDAD", $rowCentroDestino->ID_SOCIEDAD);

                //DEVOLVEMOS LA SOCIEDAD ORIGEN DE LA VENTA SI NO TIENE GESTION DE TRANSPORTE
                if ($rowSociedadDestino->GESTION_TRANSPORTE == 1):
                    return $rowSociedadDestino->ID_SOCIEDAD;
                endif;

            endwhile;

        /****OPERACIONES EN PARQUE****/
        elseif ($rowOrdenRecogida->TIPO_ORDEN_RECOGIDA == "Operaciones fuera de Sistema"):

            //BUSCAMOS LA DIRECCION DE ORIGEN Y DESTINO DE LOS BULTOS
            /*$sqlBultos    = "SELECT ID_DIRECCION_ORIGEN, ID_DIRECCION_DESTINO
                                FROM BULTO
                                WHERE ID_EXPEDICION = $rowOrdenRecogida->ID_EXPEDICION";
            $resultBultos = $bd->ExecSQL($sqlBultos);

            while ($rowBulto = $bd->SigReg($resultBultos)):

            endwhile;*/

        endif;//FIN RECORRER TIPOS RECOGIDAS

        //SI NO HEMOS ENCONTRADO SOCIEDAD SIN GASTOS DEVOLVEMOS VACIO
        return false;
    }

    /**
     * @param $idExpedicion Recogida que analizar
     * @return array Array con las sociedades relacionadas con un recogida potencialmente contratantes, puede ser un array vacio
     * Devuelve las sociedades potencialmente contratantes relacionadas con una recogida, ya sea sociedad de origen o destino
     */
    function obtenerPosiblesSociedadesContratantesRecogida($idExpedicion)
    {
        global $bd;
        global $auxiliar;

        //ARRAY PARA GUARDAR LAS SOCIEDADES RELACIONADAS CON LA RECOGIDA
        $arrSociedades = array();

        //BUSCO LA ORDEN DE RECOGIDA
        $rowOrdenRecogida = $bd->VerReg("EXPEDICION", "ID_EXPEDICION", $idExpedicion);

        /****RECOGIDA EN ALMACEN****/
        if ($rowOrdenRecogida->TIPO_ORDEN_RECOGIDA == "Recogida en Almacen"):

            //SE DIVIDEN ENTRE CON BULTOS O SIN BULTOS
            if ($rowOrdenRecogida->CON_BULTOS == 1):
                //BULTOS
                $sqlMSL = "SELECT DISTINCT MSL.ID_ALMACEN, PS.TIPO_PEDIDO, MSL.ID_ALMACEN_DESTINO
                            FROM MOVIMIENTO_SALIDA_LINEA MSL
                            INNER JOIN BULTO B ON B.ID_BULTO = MSL.ID_BULTO
                            INNER JOIN PEDIDO_SALIDA PS ON PS.ID_PEDIDO_SALIDA = MSL.ID_PEDIDO_SALIDA
                            WHERE MSL.ID_EXPEDICION = $rowOrdenRecogida->ID_EXPEDICION AND MSL.LINEA_ANULADA = 0 AND MSL.BAJA = 0";
            elseif ($rowOrdenRecogida->CON_BULTOS == 0):
                //MOVIMIENTO_SALIDA
                $sqlMSL = "SELECT DISTINCT MSL.ID_ALMACEN, PS.TIPO_PEDIDO, MSL.ID_ALMACEN_DESTINO
                            FROM MOVIMIENTO_SALIDA_LINEA MSL
                            INNER JOIN MOVIMIENTO_SALIDA MS ON MS.ID_MOVIMIENTO_SALIDA = MSL.ID_MOVIMIENTO_SALIDA
                            INNER JOIN PEDIDO_SALIDA PS ON PS.ID_PEDIDO_SALIDA = MSL.ID_PEDIDO_SALIDA
                            WHERE MSL.ID_EXPEDICION = $rowOrdenRecogida->ID_EXPEDICION AND MSL.LINEA_ANULADA = 0 AND MSL.BAJA = 0";
            endif;
            $resultMSL = $bd->ExecSQL($sqlMSL);
            while ($rowMSL = $bd->SigReg($resultMSL)): //BUSCAMOS LAS DIRECCIONES DE DESTINO
                //SI EL PEDIDO ES DE TRASLADO, UTILIZAMOS SOCIEDAD DESTINO
                if (
                    ($rowMSL->TIPO_PEDIDO == 'Traslado') ||
                    ($rowMSL->TIPO_PEDIDO == 'Traslados OM Construccion') ||
                    ($rowMSL->TIPO_PEDIDO == 'Traspaso Entre Almacenes Material Estropeado') ||
                    ($rowMSL->TIPO_PEDIDO == 'Intra Centro Fisico') ||
                    ($rowMSL->TIPO_PEDIDO == 'Interno Gama') ||
                    ($rowMSL->TIPO_PEDIDO == 'Pendientes de Ordenes Trabajo')
                ):
                    //TOMAMOS LA SOCIEDAD DEL ALMACEN DE DESTINO
                    $rowAlmacenDestino  = $bd->VerReg("ALMACEN", "ID_ALMACEN", $rowMSL->ID_ALMACEN_DESTINO);
                    $rowCentroDestino   = $bd->VerReg("CENTRO", "ID_CENTRO", $rowAlmacenDestino->ID_CENTRO);
                    $rowSociedadDestino = $bd->VerReg("SOCIEDAD", "ID_SOCIEDAD", $rowCentroDestino->ID_SOCIEDAD);

                    //SI LA SOCIEDAD TIENE GESTION DE TRANSPORTE LA AÑADO AL ARRAY DE SOCIEDADES
                    if ($rowSociedadDestino->GESTION_TRANSPORTE == 1):
                        //ALMACENO LA SOCIEDAD DE DESTINO EN EL ARRAY
                        $arrSociedades[] = $rowCentroDestino->ID_SOCIEDAD;
                    endif;
                else: //SI EL PEDIDO NO ES DE TRASLADO, UTILIZAMOS SOCIEDAD ORIGEN
                    //TOMAMOS LA SOCIEDAD DEL ALMACEN DE ORIGEN
                    $rowAlmacenOrigen  = $bd->VerReg("ALMACEN", "ID_ALMACEN", $rowMSL->ID_ALMACEN);
                    $rowCentroOrigen   = $bd->VerReg("CENTRO", "ID_CENTRO", $rowAlmacenOrigen->ID_CENTRO);
                    $rowSociedadOrigen = $bd->VerReg("SOCIEDAD", "ID_SOCIEDAD", $rowCentroOrigen->ID_SOCIEDAD);

                    //SI LA SOCIEDAD TIENE GESTION DE TRANSPORTE LA AÑADO AL ARRAY DE SOCIEDADES
                    if ($rowSociedadOrigen->GESTION_TRANSPORTE == 1):
                        //ALMACENO LA SOCIEDAD DE DESTINO EN EL ARRAY
                        $arrSociedades[] = $rowSociedadOrigen->ID_SOCIEDAD;
                    endif;
                endif;
            endwhile;

        /****RECOGIDA EN PROVEEDOR****/
        elseif ($rowOrdenRecogida->TIPO_ORDEN_RECOGIDA == "Recogida en Proveedor"):

            if ($rowOrdenRecogida->SUBTIPO_ORDEN_RECOGIDA == 'Sin Pedido Conocido'):
                //MOVIMIENTO ENTRADA
                $sqlPedidoEntrada = "SELECT DISTINCT PE.ID_PROVEEDOR,PE.ID_CLIENTE,PEL.ID_ALMACEN
                                        FROM MOVIMIENTO_ENTRADA ME
                                        INNER JOIN MOVIMIENTO_ENTRADA_LINEA MEL ON MEL.ID_MOVIMIENTO_ENTRADA=ME.ID_MOVIMIENTO_ENTRADA
                                        INNER JOIN PEDIDO_ENTRADA PE ON PE.ID_PEDIDO_ENTRADA=MEL.ID_PEDIDO
                                        INNER JOIN PEDIDO_ENTRADA_LINEA PEL ON PEL.ID_PEDIDO_ENTRADA_LINEA=MEL.ID_PEDIDO_LINEA
                                        WHERE MEL.ID_EXPEDICION_ENTREGA = $rowOrdenRecogida->ID_EXPEDICION AND ME.BAJA = 0";
            elseif ($rowOrdenRecogida->SUBTIPO_ORDEN_RECOGIDA == 'Con Pedido Conocido'):
                //PEDIDO CONOCIDO
                $sqlPedidoEntrada = "SELECT DISTINCT PE.ID_PROVEEDOR,PE.ID_CLIENTE,PEL.ID_ALMACEN
                                        FROM EXPEDICION_PEDIDO_CONOCIDO EPC
                                        INNER JOIN PEDIDO_ENTRADA PE ON PE.ID_PEDIDO_ENTRADA = EPC.ID_PEDIDO_ENTRADA
                                        INNER JOIN PEDIDO_ENTRADA_LINEA PEL ON PEL.ID_PEDIDO_ENTRADA_LINEA = EPC.ID_PEDIDO_ENTRADA_LINEA
                                        WHERE EPC.ID_EXPEDICION = $rowOrdenRecogida->ID_EXPEDICION AND EPC.BAJA = 0";
            elseif ($rowOrdenRecogida->SUBTIPO_ORDEN_RECOGIDA == 'Retorno Material Estropeado desde Proveedor'):
                //MOVIMIENTO SALIDA LINEA RELACIONADO CON EL MOVIMIENTO DE MATERIAL ESTROPEADO
                $sqlPedidoEntrada = "SELECT DISTINCT P.ID_PROVEEDOR,MSL.ID_ALMACEN_DESTINO AS ID_ALMACEN
                                        FROM  MOVIMIENTO_SALIDA_LINEA MSL
                                        INNER JOIN MOVIMIENTO_SALIDA MS_REL ON MSL.ID_MOVIMIENTO_SALIDA_MATERIAL_ESTROPEADO = MS_REL.ID_MOVIMIENTO_SALIDA
                                        INNER JOIN PROVEEDOR P ON P.ID_PROVEEDOR = MS_REL.ID_PROVEEDOR
                                        WHERE MSL.ID_EXPEDICION = $rowOrdenRecogida->ID_EXPEDICION AND MSL.BAJA = 0";
            endif;
            $resultPedidoEntrada = $bd->ExecSQL($sqlPedidoEntrada);
            while ($rowPedidosEntrada = $bd->SigReg($resultPedidoEntrada)):
                //TOMAMOS LA SOCIEDAD DEL ALMACEN DE DESTINO
                $rowAlmacenDestino  = $bd->VerReg("ALMACEN", "ID_ALMACEN", $rowPedidosEntrada->ID_ALMACEN);
                $rowCentroDestino   = $bd->VerReg("CENTRO", "ID_CENTRO", $rowAlmacenDestino->ID_CENTRO);
                $rowSociedadDestino = $bd->VerReg("SOCIEDAD", "ID_SOCIEDAD", $rowCentroDestino->ID_SOCIEDAD);

                //DEVOLVEMOS LA SOCIEDAD ORIGEN DE LA VENTA SI NO TIENE GESTION DE TRANSPORTE
                if ($rowSociedadDestino->GESTION_TRANSPORTE == 1):
                    //ALMACENO LA SOCIEDAD DE DESTINO EN EL ARRAY
                    $arrSociedades[] = $rowSociedadDestino->ID_SOCIEDAD;
                endif;
            endwhile;

        /****OPERACIONES EN PARQUE****/
        elseif ($rowOrdenRecogida->TIPO_ORDEN_RECOGIDA == "Operaciones en Parque"):

            //BUSCAMOS LAS LINEAS
            $sqlOTM    = "SELECT DISTINCT U.ID_ALMACEN
                                    FROM ORDEN_TRABAJO_MOVIMIENTO OTM
                                    INNER JOIN ORDEN_TRABAJO OT ON OT.ID_ORDEN_TRABAJO = OTM.ID_ORDEN_TRABAJO
                                    INNER JOIN UBICACION U ON U.ID_UBICACION = OTM.ID_UBICACION
                                    WHERE OTM.ID_EXPEDICION = $rowOrdenRecogida->ID_EXPEDICION AND OTM.BAJA= 0";
            $resultOTM = $bd->ExecSQL($sqlOTM);
            while ($rowOTM = $bd->SigReg($resultOTM)):
                //TOMAMOS LA SOCIEDAD DEL ALMACEN DE DESTINO
                $rowAlmacenDestino  = $bd->VerReg("ALMACEN", "ID_ALMACEN", $rowOTM->ID_ALMACEN);
                $rowCentroDestino   = $bd->VerReg("CENTRO", "ID_CENTRO", $rowAlmacenDestino->ID_CENTRO);
                $rowSociedadDestino = $bd->VerReg("SOCIEDAD", "ID_SOCIEDAD", $rowCentroDestino->ID_SOCIEDAD);

                //DEVOLVEMOS LA SOCIEDAD ORIGEN DE LA VENTA SI NO TIENE GESTION DE TRANSPORTE
                if ($rowSociedadDestino->GESTION_TRANSPORTE == 1):
                    //ALMACENO LA SOCIEDAD DE DESTINO EN EL ARRAY
                    $arrSociedades[] = $rowSociedadDestino->ID_SOCIEDAD;
                endif;
            endwhile;

        /****OPERACIONES EN PARQUE****/
        elseif ($rowOrdenRecogida->TIPO_ORDEN_RECOGIDA == "Operaciones fuera de Sistema"):

        endif;//FIN RECORRER TIPOS RECOGIDAS

        //ELIMINAMOS LOS REGISTROS DUPLICADOS
        $arrSociedades = array_unique( (array)$arrSociedades);

        //ORDENO LOS VALORES Y REINDEXO LOS INDICES
        sort($arrSociedades);

        //DEVOLVEMOS EL ARRAY DE SOCIEDADES POTENCIALMENTE CONTRATANTES RELACIONADAS CON LA RECOGIDA, PUEDE SER VACIO
        return $arrSociedades;
    }

    /**
     * @param $idExpedicion Recogida que analizar
     * @return array con las distintos Materiales de la Recogida, y su direccion de origen y destino
     * Destinos de cualquier tipo de Recogida
     */
    function obtenerDireccionesOrigenYDestinoRecogida($idExpedicion)
    {

        global $bd;
        global $auxiliar;

        //ARRAY PARA GUARDAR LOS DESTINOS SIN BULTO ASIGNADO
        $arrOrigenes = array();
        $arrDestinos = array();
        $arrMaterial = array();

        //BUSCO LA ORDEN DE RECOGIDA
        $rowOrdenRecogida = $bd->VerReg("EXPEDICION", "ID_EXPEDICION", $idExpedicion);

        /****RECOGIDA EN ALMACEN****/
        if ($rowOrdenRecogida->TIPO_ORDEN_RECOGIDA == "Recogida en Almacen"):

            //SE DIVIDEN ENTRE CON BULTOS O SIN BULTOS
            if ($rowOrdenRecogida->CON_BULTOS == 1):
                //BULTOS
                $sqlMSL = "SELECT DISTINCT MSL.ID_PEDIDO_SALIDA_LINEA, MSL.ID_PEDIDO_SALIDA, MSL.ID_ALMACEN, MSL.CANTIDAD_PEDIDO, MSL.ID_MATERIAL
                FROM MOVIMIENTO_SALIDA_LINEA MSL
                INNER JOIN BULTO B ON B.ID_BULTO = MSL.ID_BULTO
                WHERE MSL.ID_EXPEDICION = $rowOrdenRecogida->ID_EXPEDICION AND MSL.LINEA_ANULADA = 0 AND MSL.BAJA = 0";

            elseif ($rowOrdenRecogida->CON_BULTOS == 0):
                //MOVIMIENTO_SALIDA
                $sqlMSL = "SELECT DISTINCT MSL.ID_PEDIDO_SALIDA_LINEA, MSL.ID_PEDIDO_SALIDA, MSL.ID_ALMACEN, MSL.CANTIDAD_PEDIDO, MSL.ID_MATERIAL
                                FROM MOVIMIENTO_SALIDA_LINEA MSL
                                INNER JOIN MOVIMIENTO_SALIDA MS ON MS.ID_MOVIMIENTO_SALIDA = MSL.ID_MOVIMIENTO_SALIDA
                                WHERE MSL.ID_EXPEDICION = $rowOrdenRecogida->ID_EXPEDICION AND MSL.LINEA_ANULADA = 0 AND MSL.BAJA = 0";
            endif;
            $resultMSL = $bd->ExecSQL($sqlMSL);


            //BUSCAMOS LA DIRECCION DEL ALMACEN ORIGEN(CENTRO FISICO)
            $sqlDireccionOrigen    = "SELECT DISTINCT D.ID_DIRECCION
                                                FROM  DIRECCION D
                                                WHERE D.TIPO_DIRECCION = 'Centro Fisico' AND  D.ID_CENTRO_FISICO= $rowOrdenRecogida->ID_CENTRO_FISICO AND D.BAJA = 0";
            $resultDireccionOrigen = $bd->ExecSQL($sqlDireccionOrigen);
            $rowDireccionOrigen    = $bd->SigReg($resultDireccionOrigen);

            //GUARDAMOS EL ORIGEN
            if ($rowDireccionOrigen->ID_DIRECCION != NULL):
                $arrOrigenes[] = $rowDireccionOrigen->ID_DIRECCION;
            endif;

            //BUSCAMOS LAS DIRECCIONES DE DESTINO
            while ($rowMSL = $bd->SigReg($resultMSL)):

                //BUSCAMOS EL TIPO DE PEDIDO PARA VER EL DESTINO
                $GLOBALS["NotificaErrorPorEmail"] = "No";
                $rowPedidoSalida                  = $bd->VerReg("PEDIDO_SALIDA", "ID_PEDIDO_SALIDA", $rowMSL->ID_PEDIDO_SALIDA, "No");

                $rowDireccionDestino = false;
                //SI ES PEDIDO DE VENTA, ALMACENAMOS EL CLIENTE
                if ($rowPedidoSalida->TIPO_PEDIDO == 'Venta'):
                    //BUSCAMOS LA DIRECCION DEL CLIENTE
                    $sqlDireccionDestino    = "SELECT DISTINCT D.ID_DIRECCION
                                                FROM DIRECCION D
                                                WHERE D.TIPO_DIRECCION = 'Cliente' AND D.ID_CLIENTE = $rowPedidoSalida->ID_CLIENTE AND D.BAJA = 0";
                    $resultDireccionDestino = $bd->ExecSQL($sqlDireccionDestino);
                    $rowDireccionDestino    = $bd->SigReg($resultDireccionDestino);

                //SI EL DESTINO DEL PEDIDO ES UN PROVEEDOR
                elseif (
                    ($rowPedidoSalida->TIPO_PEDIDO == 'Devolución a Proveedor') ||
                    ($rowPedidoSalida->TIPO_PEDIDO == 'Componentes a Proveedor') ||
                    ($rowPedidoSalida->TIPO_PEDIDO == 'Rechazos y Anulaciones a Proveedor') ||
                    ($rowPedidoSalida->TIPO_PEDIDO == 'Material Estropeado a Proveedor')
                ):
                    //BUSCAMOS LA DIRECCION DEL PROVEEDOR
                    $sqlDireccionDestino    = "SELECT DISTINCT D.ID_DIRECCION
                                                FROM DIRECCION D
                                                WHERE D.TIPO_DIRECCION = 'Proveedor' AND D.ID_PROVEEDOR = $rowPedidoSalida->ID_PROVEEDOR AND D.BAJA = 0";
                    $resultDireccionDestino = $bd->ExecSQL($sqlDireccionDestino);
                    $rowDireccionDestino    = $bd->SigReg($resultDireccionDestino);

                //SI EL PEDIDO ES DE TRASLADO, ALMACENAMOS EL ALMACEN DEL PSL
                elseif (
                    ($rowPedidoSalida->TIPO_PEDIDO == 'Traslado') ||
                    ($rowPedidoSalida->TIPO_PEDIDO == 'Traslados OM Construccion') ||
                    ($rowPedidoSalida->TIPO_PEDIDO == 'Traspaso Entre Almacenes Material Estropeado') ||
                    ($rowPedidoSalida->TIPO_PEDIDO == 'Intra Centro Fisico') ||
                    ($rowPedidoSalida->TIPO_PEDIDO == 'Interno Gama') ||
                    ($rowPedidoSalida->TIPO_PEDIDO == 'Pendientes de Ordenes Trabajo')
                ):
                    //BUSCAMOS EL PSL
                    $rowPedidoSalidaLinea = $bd->VerReg("PEDIDO_SALIDA_LINEA", "ID_PEDIDO_SALIDA_LINEA", $rowMSL->ID_PEDIDO_SALIDA_LINEA, "No");

                    //BUSCAMOS LA DIRECCION DEL ALMACEN DESTINO(CENTRO FISICO)
                    $sqlDireccionDestino    = "SELECT DISTINCT D.ID_DIRECCION
                                                    FROM ALMACEN A
                                                    INNER JOIN DIRECCION D ON A.ID_CENTRO_FISICO = D.ID_CENTRO_FISICO
                                                    WHERE D.TIPO_DIRECCION = 'Centro Fisico' AND A.ID_ALMACEN = $rowPedidoSalidaLinea->ID_ALMACEN_DESTINO AND D.BAJA = 0";
                    $resultDireccionDestino = $bd->ExecSQL($sqlDireccionDestino);
                    $rowDireccionDestino    = $bd->SigReg($resultDireccionDestino);
                endif;

                //NOS GUARDAMOS LA DIRECCION EN EL ARRAY
                if ($rowDireccionDestino->ID_DIRECCION != NULL):
                    $arrDestinos[] = $rowDireccionDestino->ID_DIRECCION;

                    //GUARDAMOS EL MATERIAL Y LA CANTIDAD
                    if ($rowDireccionOrigen->ID_DIRECCION != NULL):
                        if (!isset($arrMaterial[$rowMSL->ID_MATERIAL][$rowDireccionOrigen->ID_DIRECCION][$rowDireccionDestino->ID_DIRECCION])):
                            $arrMaterial[$rowMSL->ID_MATERIAL][$rowDireccionOrigen->ID_DIRECCION][$rowDireccionDestino->ID_DIRECCION] = $rowMSL->CANTIDAD_PEDIDO;
                        else:
                            $arrMaterial[$rowMSL->ID_MATERIAL][$rowDireccionOrigen->ID_DIRECCION][$rowDireccionDestino->ID_DIRECCION] = $arrMaterial[$rowMSL->ID_MATERIAL][$rowDireccionOrigen->ID_DIRECCION][$rowDireccionDestino->ID_DIRECCION] + $rowMSL->CANTIDAD_PEDIDO;
                        endif;
                    endif;

                endif;
            endwhile;

        /****RECOGIDA EN PROVEEDOR****/
        elseif ($rowOrdenRecogida->TIPO_ORDEN_RECOGIDA == "Recogida en Proveedor"):

            if ($rowOrdenRecogida->SUBTIPO_ORDEN_RECOGIDA == 'Sin Pedido Conocido'):
                //MOVIMIENTO ENTRADA
                $sqlPedidoEntrada = "SELECT DISTINCT PE.ID_PROVEEDOR,PE.ID_CLIENTE,PEL.ID_ALMACEN
                            FROM MOVIMIENTO_ENTRADA ME
                            INNER JOIN MOVIMIENTO_ENTRADA_LINEA MEL ON MEL.ID_MOVIMIENTO_ENTRADA=ME.ID_MOVIMIENTO_ENTRADA
                            INNER JOIN PEDIDO_ENTRADA PE ON PE.ID_PEDIDO_ENTRADA=MEL.ID_PEDIDO
                            INNER JOIN PEDIDO_ENTRADA_LINEA PEL ON PEL.ID_PEDIDO_ENTRADA_LINEA=MEL.ID_PEDIDO_LINEA
                            WHERE MEL.ID_EXPEDICION_ENTREGA = $rowOrdenRecogida->ID_EXPEDICION AND ME.BAJA = 0";

            elseif ($rowOrdenRecogida->SUBTIPO_ORDEN_RECOGIDA == 'Con Pedido Conocido'):
                //PEDIDO CONOCIDO
                $sqlPedidoEntrada = "SELECT DISTINCT PE.ID_PROVEEDOR,PE.ID_CLIENTE,PEL.ID_ALMACEN
                            FROM EXPEDICION_PEDIDO_CONOCIDO EPC
                            INNER JOIN PEDIDO_ENTRADA PE ON PE.ID_PEDIDO_ENTRADA = EPC.ID_PEDIDO_ENTRADA
                            INNER JOIN PEDIDO_ENTRADA_LINEA PEL ON PEL.ID_PEDIDO_ENTRADA_LINEA = EPC.ID_PEDIDO_ENTRADA_LINEA
                            WHERE EPC.ID_EXPEDICION = $rowOrdenRecogida->ID_EXPEDICION AND EPC.BAJA = 0";

            elseif ($rowOrdenRecogida->SUBTIPO_ORDEN_RECOGIDA == 'Retorno Material Estropeado desde Proveedor'):
                //MOVIMIENTO SALIDA LINEA RELACIONADO CON EL MOVIMIENTO DE MATERIAL ESTROPEADO
                $sqlPedidoEntrada = "SELECT DISTINCT P.ID_PROVEEDOR,MSL.ID_ALMACEN_DESTINO AS ID_ALMACEN
                            FROM  MOVIMIENTO_SALIDA_LINEA MSL
                            INNER JOIN MOVIMIENTO_SALIDA MS_REL ON MSL.ID_MOVIMIENTO_SALIDA_MATERIAL_ESTROPEADO = MS_REL.ID_MOVIMIENTO_SALIDA
                            INNER JOIN PROVEEDOR P ON P.ID_PROVEEDOR = MS_REL.ID_PROVEEDOR
                            WHERE MSL.ID_EXPEDICION = $rowOrdenRecogida->ID_EXPEDICION AND MSL.BAJA = 0";

            endif;
            $resultPedidoEntrada = $bd->ExecSQL($sqlPedidoEntrada);

            while ($rowPedidosEntrada = $bd->SigReg($resultPedidoEntrada)):

                //EL PROVEEDOR/CLIENTE DE ORIGEN LO SACAMOS DEL PEDIDO ENTRADA
                $rowDireccionOrigen = false;
                if ($rowPedidosEntrada->ID_PROVEEDOR != ""):
                    //BUSCAMOS LA DIRECCION DEL PROVEEDOR
                    $sqlDireccionOrigen    = "SELECT DISTINCT D.ID_DIRECCION
                                                FROM DIRECCION D
                                                WHERE D.TIPO_DIRECCION = 'Proveedor' AND D.ID_PROVEEDOR = $rowPedidosEntrada->ID_PROVEEDOR AND D.BAJA = 0";
                    $resultDireccionOrigen = $bd->ExecSQL($sqlDireccionOrigen);
                    $rowDireccionOrigen    = $bd->SigReg($resultDireccionOrigen);

                elseif ($rowPedidosEntrada->ID_CLIENTE != ""):
                    //BUSCAMOS LA DIRECCION DEL CLIENTE
                    $sqlDireccionOrigen    = "SELECT DISTINCT D.ID_DIRECCION
                                                FROM DIRECCION D
                                                WHERE D.TIPO_DIRECCION = 'Cliente' AND D.ID_CLIENTE = $rowPedidosEntrada->ID_PROVEEDOR AND D.BAJA = 0";
                    $resultDireccionOrigen = $bd->ExecSQL($sqlDireccionOrigen);
                    $rowDireccionOrigen    = $bd->SigReg($resultDireccionOrigen);
                endif;

                //GUARDAMOS EL ORIGEN
                if ($rowDireccionOrigen->ID_DIRECCION != NULL):
                    $arrOrigenes[] = $rowDireccionOrigen->ID_DIRECCION;
                endif;

                //BUSCAMOS LA DIRECCION DEL ALMACEN DESTINO(CENTRO FISICO)
                $sqlDireccionDestino    = "SELECT DISTINCT D.ID_DIRECCION
                                                    FROM ALMACEN A
                                                    INNER JOIN DIRECCION D ON A.ID_CENTRO_FISICO = D.ID_CENTRO_FISICO
                                                    WHERE D.TIPO_DIRECCION = 'Centro Fisico' AND A.ID_ALMACEN = $rowPedidosEntrada->ID_ALMACEN AND D.BAJA = 0";
                $resultDireccionDestino = $bd->ExecSQL($sqlDireccionDestino);
                $rowDireccionDestino    = $bd->SigReg($resultDireccionDestino);

                //NOS GUARDAMOS LA DIRECCION EN EL ARRAY
                if ($rowDireccionDestino->ID_DIRECCION != NULL):
                    $arrDestinos[] = $rowDireccionDestino->ID_DIRECCION;
                endif;

            endwhile;

        /****OPERACIONES EN PARQUE****/
        elseif ($rowOrdenRecogida->TIPO_ORDEN_RECOGIDA == "Operaciones en Parque"):

            //BUSCAMOS LAS LINEAS
            $sqlOTM    = "SELECT DISTINCT OTM.ID_UBICACION, OT.ID_INSTALACION, OTM.TIPO_ACCION
                                                            FROM ORDEN_TRABAJO_MOVIMIENTO OTM
                                                            INNER JOIN ORDEN_TRABAJO OT ON OT.ID_ORDEN_TRABAJO = OTM.ID_ORDEN_TRABAJO
                                                            WHERE OTM.ID_EXPEDICION = $rowOrdenRecogida->ID_EXPEDICION AND OTM.BAJA= 0";
            $resultOTM = $bd->ExecSQL($sqlOTM);


            while ($rowOTM = $bd->SigReg($resultOTM)):

                //BUSCAMOS EL PAIS DE LA UBICACION DE LA OTM (CENTRO FISICO)
                $rowDireccionOrigen    = "SELECT DISTINCT D.ID_DIRECCION
                                            FROM UBICACION U
                                            INNER JOIN ALMACEN A ON A.ID_ALMACEN = U.ID_ALMACEN
                                            INNER JOIN DIRECCION D ON A.ID_CENTRO_FISICO = D.ID_CENTRO_FISICO
                                            WHERE D.TIPO_DIRECCION = 'Centro Fisico' AND U.ID_UBICACION = $rowOTM->ID_UBICACION AND D.BAJA = 0";
                $resultDireccionOrigen = $bd->ExecSQL($rowDireccionOrigen);
                $rowDireccionOrigen    = $bd->SigReg($resultDireccionOrigen);

                //NOS GUARDAMOS LA DIRECCION EN EL ARRAY
                if ($rowDireccionOrigen->ID_DIRECCION != NULL):
                    //SI ES UNA ACCION DE SALIDA, EL ALMACEN ES EL ORIGEN
                    if ($rowOTM->TIPO_ACCION == "Salida"):
                        $arrOrigenes[] = $rowDireccionOrigen->ID_DIRECCION;
                    else:
                        $arrDestinos[] = $rowDireccionOrigen->ID_DIRECCION;
                    endif;
                endif;


                //SI LA OT TIENE INSTALACION
                if ($rowOTM->ID_INSTALACION != ""):
                    //BUSCAMOS LA DIRECCION DE LA INSTALACION
                    $sqlDireccionDestino    = "SELECT DISTINCT D.ID_DIRECCION
                                                FROM DIRECCION D
                                                WHERE D.TIPO_DIRECCION = 'Instalacion' AND D.ID_INSTALACION = $rowOTM->ID_INSTALACION AND D.BAJA = 0";
                    $resultDireccionDestino = $bd->ExecSQL($sqlDireccionDestino);
                    $rowDireccionDestino    = $bd->SigReg($resultDireccionDestino);

                    //NOS GUARDAMOS LA DIRECCION EN EL ARRAY
                    if ($rowDireccionDestino->ID_DIRECCION != NULL):
                        //SI ES UNA ACCION DE SALIDA, EL ALMACEN ES EL ORIGEN
                        if ($rowOTM->TIPO_ACCION == "Salida"):
                            $arrOrigenes[] = $rowDireccionDestino->ID_DIRECCION;
                        else:
                            $arrDestinos[] = $rowDireccionDestino->ID_DIRECCION;
                        endif;
                    endif;
                endif;

            endwhile;

        /****OPERACIONES EN PARQUE****/
        elseif ($rowOrdenRecogida->TIPO_ORDEN_RECOGIDA == "Operaciones fuera de Sistema"):

            //BUSCAMOS LA DIRECCION DE ORIGEN Y DESTINO DE LOS BULTOS
            $sqlBultos    = "SELECT ID_DIRECCION_ORIGEN, ID_DIRECCION_DESTINO
                                FROM BULTO
                                WHERE ID_EXPEDICION = $rowOrdenRecogida->ID_EXPEDICION";
            $resultBultos = $bd->ExecSQL($sqlBultos);

            while ($rowBulto = $bd->SigReg($resultBultos)):
                $arrOrigenes[] = $rowBulto->ID_DIRECCION_ORIGEN;
                $arrDestinos[] = $rowBulto->ID_DIRECCION_DESTINO;
            endwhile;

        endif;//FIN RECORRER TIPOS RECOGIDAS

        //UNIFICAMOS VALORES
        $arrOrigenes = array_unique( (array)$arrOrigenes);
        $arrDestinos = array_unique( (array)$arrDestinos);

        //INSERTAMOS EN EL ARRAY A DEVOLVER
        $arrDevolver               = array();
        $arrDevolver['Origen']     = $arrOrigenes;
        $arrDevolver['Destino']    = $arrDestinos;
        $arrDevolver['Materiales'] = $arrMaterial;

        return $arrDevolver;

    }

    /**
     * @param $idOrdenRecogida ORDEN DE RECOGIDA A ACTUALIZAR EL CANAL DE ENTREGA
     * FUNCION UTILIZADA PARA CALCULAR Y ACTUALIZAR EL CANAL DE ENTREGA TRAS LA ASIGNACION/DESASIGNACION DE LINEAS
     */
    function ActualizarCanalEntregaRecogida($idOrdenRecogida)
    {
        global $bd;

        //BUSCAMOS EL CANAL DE ENTREGA MAS RESTRICTIVO
        $sqlCanalEntrega    = "SELECT PSL.CANAL_DE_ENTREGA
                                FROM MOVIMIENTO_SALIDA_LINEA MSL
                                INNER JOIN PEDIDO_SALIDA_LINEA PSL ON PSL.ID_PEDIDO_SALIDA_LINEA = MSL.ID_PEDIDO_SALIDA_LINEA
                                WHERE MSL.ID_EXPEDICION = $idOrdenRecogida AND MSL.LINEA_ANULADA = 0 AND MSL.BAJA = 0
                                ORDER BY CANAL_DE_ENTREGA DESC";
        $resultCanalEntrega = $bd->ExecSQL($sqlCanalEntrega);
        $numLineas          = $bd->NumRegs($resultCanalEntrega);

        if ($numLineas > 0):
            $rowCanalEntrega = $bd->SigReg($resultCanalEntrega);
            $sqlUpdate       = "UPDATE EXPEDICION SET
                            CANAL_DE_ENTREGA = '" . $rowCanalEntrega->CANAL_DE_ENTREGA . "'
                            WHERE ID_EXPEDICION = $idOrdenRecogida";
            $bd->ExecSQL($sqlUpdate);
        else:
            //SI LA ORDEN DE RECOGIDA NO CONTIENE LINEAS ACTIVA LE PONEMOS POR DEFECTO EL CANAL ESTANDAR
            $sqlUpdate = "UPDATE EXPEDICION SET
                            CANAL_DE_ENTREGA = 'Estandar'
                            WHERE ID_EXPEDICION = $idOrdenRecogida";
            $bd->ExecSQL($sqlUpdate);
        endif;
    }

    function getVersionExpedicion(){
        global $bd;

        $sqlVersionDefecto = "SELECT DISTINCT DEFAULT (VERSION) AS VERSION FROM EXPEDICION";
        $resultVersionDefecto = $bd->ExecSQL($sqlVersionDefecto);
        $rowVersionDefecto = $bd->SigReg($resultVersionDefecto);
        return $rowVersionDefecto->VERSION;

    }
} // FIN CLASE