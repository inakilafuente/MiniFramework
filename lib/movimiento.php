<?php

# movimiento
# Clase movimiento contiene todas las funciones necesarias para la interaccion con la clase movimiento
# Se incluira en las sesiones

class movimiento
{

    function __construct()
    {
    } // Fin movimiento

    function actualizarEstadoMovimientoSalida($idMovimiento)
    {
        global $bd;
        global $administrador;

        //BUSCO EL MOVIMIENTO DE SALIDA
        $rowMov                  = $bd->VerReg("MOVIMIENTO_SALIDA", "ID_MOVIMIENTO_SALIDA", $idMovimiento);
        $estadoInicialMovimiento = $rowMov->ESTADO;

        //CALCULO EL NUMERO DE LINEAS DEL MOVIMIENTO
        $numLineas = $bd->NumRegsTabla("MOVIMIENTO_SALIDA_LINEA", "ID_MOVIMIENTO_SALIDA = $idMovimiento AND LINEA_ANULADA = 0 AND BAJA = 0");
        //CALCULO EL NUMERO DE LINEAS DEL MOVIMIENTO RESERVADAS PARA PREPARACION
        $numLineasReservadasParaPreparacion = $bd->NumRegsTabla("MOVIMIENTO_SALIDA_LINEA", "ID_MOVIMIENTO_SALIDA = $idMovimiento AND LINEA_ANULADA = 0 AND BAJA = 0 AND ESTADO = 'Reservado para Preparacion'");
        //CALCULO EL NUMERO DE LINEAS DEL MOVIMIENTO EN PREPARACION
        $numLineasEnPreparacion = $bd->NumRegsTabla("MOVIMIENTO_SALIDA_LINEA", "ID_MOVIMIENTO_SALIDA = $idMovimiento AND LINEA_ANULADA = 0 AND BAJA = 0 AND ESTADO = 'En Preparacion'");
        //CALCULO EL NUMERO DE LINEAS DEL MOVIMIENTO PENDIENTE DE EXPEDIR
        $numLineasPendienteDeExpedir = $bd->NumRegsTabla("MOVIMIENTO_SALIDA_LINEA", "ID_MOVIMIENTO_SALIDA = $idMovimiento AND LINEA_ANULADA = 0 AND BAJA = 0 AND ESTADO = 'Pendiente de Expedir'");
        //CALCULO EL NUMERO DE LINEAS DEL MOVIMIENTO TRANSMITIDAS A SAP
        $numLineasTransmitidasASAP = $bd->NumRegsTabla("MOVIMIENTO_SALIDA_LINEA", "ID_MOVIMIENTO_SALIDA = $idMovimiento AND LINEA_ANULADA = 0 AND BAJA = 0 AND ESTADO = 'Transmitido a SAP'");
        //CALCULO EL NUMERO DE LINEAS DEL MOVIMIENTO EXPEDIDAS
        $numLineasExpedidas = $bd->NumRegsTabla("MOVIMIENTO_SALIDA_LINEA", "ID_MOVIMIENTO_SALIDA = $idMovimiento AND LINEA_ANULADA = 0 AND BAJA = 0 AND ESTADO = 'Expedido'");
        //CALCULO EL NUMERO DE LINEAS DEL MOVIMIENTO EN TRANSITO
        $numLineasEnTransito = $bd->NumRegsTabla("MOVIMIENTO_SALIDA_LINEA", "ID_MOVIMIENTO_SALIDA = $idMovimiento AND LINEA_ANULADA = 0 AND BAJA = 0 AND ESTADO = 'En Tránsito'");
        //CALCULO EL NUMERO DE LINEAS DEL MOVIMIENTO RECEPCIONADAS
        $numLineasRecepcionadas = $bd->NumRegsTabla("MOVIMIENTO_SALIDA_LINEA", "ID_MOVIMIENTO_SALIDA = $idMovimiento AND LINEA_ANULADA = 0 AND BAJA = 0 AND ESTADO = 'Recepcionado'");


        //SI ESTAN TODAS LAS LINEAS RECEPCIONADAS, EL MOVIMIENTO TAMBIEN
        if ($numLineas == $numLineasRecepcionadas):
            $sqlUpdate = "UPDATE MOVIMIENTO_SALIDA SET
                            ESTADO = 'Recepcionado'
                            , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                            WHERE ID_MOVIMIENTO_SALIDA = $idMovimiento";
        //SI ESTAN TODAS LAS LINEAS EN TRANSITO O RECEPCIONADAS EL MOVIMIENTO ESTARA EN TRANSITO
        elseif ($numLineas == ($numLineasEnTransito + $numLineasRecepcionadas)):
            $sqlUpdate = "UPDATE MOVIMIENTO_SALIDA SET
                            ESTADO = 'En Tránsito'
                            , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                            WHERE ID_MOVIMIENTO_SALIDA = $idMovimiento";
        //SI ESTAN TODAS LAS LINEAS TRANSMITIDAS A SAP, EL MOVIMIENTO TAMBIEN
        elseif ($numLineas == $numLineasExpedidas):
            $sqlUpdate = "UPDATE MOVIMIENTO_SALIDA SET
                            ESTADO = 'Expedido'
                            , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                            WHERE ID_MOVIMIENTO_SALIDA = $idMovimiento";
        //SI ESTAN TODAS LAS LINEAS TRANSMITIDAS A SAP, EL MOVIMIENTO TAMBIEN
        elseif ($numLineas == $numLineasTransmitidasASAP):
            $sqlUpdate = "UPDATE MOVIMIENTO_SALIDA SET
                            ESTADO = 'Transmitido a SAP'
                            , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                            WHERE ID_MOVIMIENTO_SALIDA = $idMovimiento";
        //SI ESTAN TODAS LAS LINEAS PENDIENTES DE EXPEDIR, EL MOVIMIENTO TAMBIEN
        elseif ($numLineas == $numLineasPendienteDeExpedir):
            $sqlUpdate = "UPDATE MOVIMIENTO_SALIDA SET
                            ESTADO = 'Pendiente de Expedir'
                            , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                            WHERE ID_MOVIMIENTO_SALIDA = $idMovimiento";
        //SI ESTAN TODAS LAS LINEAS ENTRE PENDIENTES DE EXPEDIR, TRANSMISION A SAP, EXPEDIDAS, EN TRANSITO Y RECEPCIONADAS, EL MOVIMIENTO EN TRANSMISION
        elseif ($numLineas == ($numLineasPendienteDeExpedir + $numLineasTransmitidasASAP + $numLineasExpedidas + $numLineasEnTransito + $numLineasRecepcionadas)):
            $sqlUpdate = "UPDATE MOVIMIENTO_SALIDA SET
                            ESTADO = 'En Transmision'
                            , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                            WHERE ID_MOVIMIENTO_SALIDA = $idMovimiento";
        //SI ESTAN TODAS LAS LINEAS RESERVADAS PARA PREPARACION, EL MOVIMIENTO TAMBIEN
        elseif ($numLineas == $numLineasReservadasParaPreparacion):
            $sqlUpdate = "UPDATE MOVIMIENTO_SALIDA SET
                            ESTADO = 'Reservado para Preparacion'
                            , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                            WHERE ID_MOVIMIENTO_SALIDA = $idMovimiento";
        //OTRO CASO
        else:
            $sqlUpdate = "UPDATE MOVIMIENTO_SALIDA SET
                            ESTADO = 'En Preparacion'
                            , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                            WHERE ID_MOVIMIENTO_SALIDA = $idMovimiento";
        endif;

        //EJECUTO LA ACTUALIZACION DEL ESTADO DEL MOVIMIENTO DE SALIDA
        $bd->ExecSQL($sqlUpdate);

        //BUSCO EL MOVIMIENTO DE SALIDA ACTUALIZADO
        $rowMov                = $bd->VerReg("MOVIMIENTO_SALIDA", "ID_MOVIMIENTO_SALIDA", $idMovimiento);
        $estadoFinalMovimiento = $rowMov->ESTADO;

        //ACTUALIZO LA FECHA EXPEDICION DE LOS MOVIMIENTOS DE SALIDA
        if (
            (($estadoInicialMovimiento == 'Reservado para Preparacion') || ($estadoInicialMovimiento == 'En Preparacion') || ($estadoInicialMovimiento == 'Pendiente de Expedir') || ($estadoInicialMovimiento == 'En Transmision') || ($estadoInicialMovimiento == 'Transmitido a SAP')) &&
            (($estadoFinalMovimiento == 'Expedido') || ($estadoFinalMovimiento == 'En Tránsito') || ($estadoFinalMovimiento == 'Recepcionado'))
        ):
            $sqlUpdate = "UPDATE MOVIMIENTO_SALIDA SET FECHA_EXPEDICION_REAL = '" . date("Y-m-d H:i:s") . "' WHERE ID_MOVIMIENTO_SALIDA = $idMovimiento";
            $bd->ExecSQL($sqlUpdate);
        endif;
        if (
            (($estadoFinalMovimiento == 'Reservado para Preparacion') || ($estadoFinalMovimiento == 'En Preparacion') || ($estadoFinalMovimiento == 'Pendiente de Expedir') || ($estadoFinalMovimiento == 'En Transmision') || ($estadoInicialMovimiento == 'Transmitido a SAP')) &&
            (($estadoInicialMovimiento == 'Expedido') || ($estadoInicialMovimiento == 'En Tránsito') || ($estadoInicialMovimiento == 'Recepcionado'))
        ):
            $sqlUpdate = "UPDATE MOVIMIENTO_SALIDA SET FECHA_EXPEDICION_REAL = '0000-00-00 00:00:00' WHERE ID_MOVIMIENTO_SALIDA = $idMovimiento";
            $bd->ExecSQL($sqlUpdate);
        endif;
    }

    function estadoMovimientoSalidaIncorrecto($idMovimiento, &$numErrores, &$error)
    {
        global $bd;


        //VARIABLE PARA CONTROLAR LA ACTUALIZACION DE LOS ESTADOS DE LOS MOVIMIENTOS DE SALIDA
        $bloqueado = true;

        //BUSCO EL MOVIMIENTO DE SALIDA
        $rowMov = $bd->VerReg("MOVIMIENTO_SALIDA", "ID_MOVIMIENTO_SALIDA", $idMovimiento);

        //CALCULO EL NUMERO DE LINEAS DEL MOVIMIENTO
        $numLineas = $bd->NumRegsTabla("MOVIMIENTO_SALIDA_LINEA", "ID_MOVIMIENTO_SALIDA = $idMovimiento AND LINEA_ANULADA = 0 AND BAJA = 0");
        //CALCULO EL NUMERO DE LINEAS DEL MOVIMIENTO EN PREPARACION
        $numLineasEnPreparacion = $bd->NumRegsTabla("MOVIMIENTO_SALIDA_LINEA", "ID_MOVIMIENTO_SALIDA = $idMovimiento AND LINEA_ANULADA = 0 AND BAJA = 0 AND ESTADO = 'En Preparacion'");
        //CALCULO EL NUMERO DE LINEAS DEL MOVIMIENTO PENDIENTE DE EXPEDIR
        $numLineasPendienteDeExpedir = $bd->NumRegsTabla("MOVIMIENTO_SALIDA_LINEA", "ID_MOVIMIENTO_SALIDA = $idMovimiento AND LINEA_ANULADA = 0 AND BAJA = 0 AND ESTADO = 'Pendiente de Expedir'");
        //CALCULO EL NUMERO DE LINEAS DEL MOVIMIENTO EXPEDIDAS
        $numLineasExpedidas = $bd->NumRegsTabla("MOVIMIENTO_SALIDA_LINEA", "ID_MOVIMIENTO_SALIDA = $idMovimiento AND LINEA_ANULADA = 0 AND BAJA = 0 AND ESTADO = 'Expedido'");
        //CALCULO EL NUMERO DE LINEAS DEL MOVIMIENTO EN TRANSITO
        $numLineasEnTransito = $bd->NumRegsTabla("MOVIMIENTO_SALIDA_LINEA", "ID_MOVIMIENTO_SALIDA = $idMovimiento AND LINEA_ANULADA = 0 AND BAJA = 0 AND ESTADO = 'En Tránsito'");
        //CALCULO EL NUMERO DE LINEAS DEL MOVIMIENTO RECEPCIONADAS
        $numLineasRecepcionadas = $bd->NumRegsTabla("MOVIMIENTO_SALIDA_LINEA", "ID_MOVIMIENTO_SALIDA = $idMovimiento AND LINEA_ANULADA = 0 AND BAJA = 0 AND ESTADO = 'Recepcionado'");

        //EL MATERIAL SALE Y YA NO TENEMOS CONTROL DEL MATERIAL
        if (
            ($rowMov->TIPO_MOVIMIENTO == 'Venta') ||
            ($rowMov->TIPO_MOVIMIENTO == 'MaterialRechazadoAnuladoEnEntradasAProveedor') ||
            ($rowMov->TIPO_MOVIMIENTO == 'DevolucionNoEstropeadoAProveedor') ||
            ($rowMov->TIPO_MOVIMIENTO == 'ComponentesAProveedor')
        ):

            //SI ESTAN TODAS LAS LINEAS EXPEDIDAS EL MOVIMIENTO TAMBIEN, SINO EN PREPARACION
            if ($numLineas == $numLineasExpedidas):
                $estadoCorrespondiente = "Expedido";
            else:
                $estadoCorrespondiente = "En Preparacion";
            endif;

        //EL MATERIAL SALE Y HAY QUE RECEPCIONARLO
        elseif ($rowMov->TIPO_MOVIMIENTO == 'TraspasoEntreAlmacenesNoEstropeado'):

            //SI ESTAN TODAS LAS LINEAS RECEPCIONADAS EL MOVIMIENTO TAMBIEN
            if ($numLineas == $numLineasRecepcionadas):
                $estadoCorrespondiente = "Recepcionado";
            //SI ESTAN TODAS LAS LINEAS EN TRANSITO O RECEPCIONADAS EL MOVIMIENTO ESTARA EN TRANSITO
            elseif ($numLineas == ($numLineasEnTransito + $numLineasRecepcionadas)):
                $estadoCorrespondiente = "En Tránsito";

            //EN CASO CONTRARIO EL MOVIMIENTO ESTARA EN PREPARACION
            else:
                $estadoCorrespondiente = "En Preparacion";

            endif;

        endif; //FIN TIPOS DE MOVIMIENTO DE SALIDA


        if ($rowMov->ESTADO == $estadoCorrespondiente):
            $error = $error . "El estado del movimiento de salida " . $rowMov->ID_MOVIMIENTO_SALIDA . " es " . $rowMov->ESTADO . " y es correcto.<br>";
        else:
            $numErrores = $numErrores + 1;
            $error      = $error . "<span style='color:#F00;'>El estado del movimiento de salida " . $rowMov->ID_MOVIMIENTO_SALIDA . " es " . $rowMov->ESTADO . " y debería ser " . $estadoCorrespondiente . ".</span><br>";

            if ($bloqueado == false):
                $sql = "UPDATE MOVIMIENTO_SALIDA SET
								ESTADO = '" . $estadoCorrespondiente . "' 
								WHERE ID_MOVIMIENTO_SALIDA = $rowMov->ID_MOVIMIENTO_SALIDA";
                $bd->ExecSQL($sql);
            endif;
        endif;
    }

    function UrgenciaTramoKilometrosMovimientoSalida($kilometros)
    {
        $distancia = $kilometros / 2;

        if (($distancia > 0) && ($distancia <= 50)):
            return '0-50';

        elseif (($distancia > 50) && ($distancia <= 100)):
            return '51-100';

        elseif (($distancia > 100) && ($distancia <= 150)):
            return '101-150';

        elseif (($distancia > 150) && ($distancia <= 200)):
            return '151-200';

        elseif (($distancia > 200) && ($distancia <= 250)):
            return '201-250';

        elseif (($distancia > 250)):
            return '>250';

        elseif (($distancia == 0)):
            return 'Sin desplazamiento';

        else:
            return NULL;

        endif;
    }


    /**
     * @param $idMovimientoSalida Movimiento de Salida que anular
     * DEVUELVE UN ARRAY CON LOS ERRORES EN CASO DE QUE LOS HUBIESE
     */
    function anularEnvioAProveedor($idMovimientoSalida)
    {
        global $bd;
        global $administrador;
        global $html;
        global $sap;
        global $auxiliar;
        global $expedicion;
        global $strError;


        //BUSCO EL MOVIMIENTO
        $rowMov = $bd->VerReg("MOVIMIENTO_SALIDA", "ID_MOVIMIENTO_SALIDA", $idMovimientoSalida);

        //COMPRUEBO QUE EL MOVIMIENTO ESTE RECEPCIONADO
        $html->PagErrorCondicionado($rowMov->ESTADO, "!=", "Recepcionado", "EstadoMovimientoIncorrecto");

        //COMPRUEBO QUE EL MOVIMIENTO NO ESTE DADA DE BAJA
        $html->PagErrorCondicionado($rowMov->BAJA, "==", 1, "MovimientoBaja");


        //VARIABLE PARA SABER SI HAY QUE TRANSMITIR EL MOVIMIENTO DIRECTO A PROVEEDOR A SAP
        $Sistema_OT = NULL;

        //VARIABLE PARA SABER EL ORIGEN DEL MATERIAL
        $origenMaterial = NULL;

        //VARIABLE PARA CONTROLAR ERRORES
        $strError             = "";
        $hayErrorSGA          = false;
        $hayErrorAnulacionSAP = false;

        //RECORRO LAS LINEAS DEL MOVIMIENTO
        $sqlLineas    = "SELECT * FROM MOVIMIENTO_SALIDA_LINEA WHERE ID_MOVIMIENTO_SALIDA = $rowMov->ID_MOVIMIENTO_SALIDA AND BAJA = 0";
        $resultLineas = $bd->ExecSQL($sqlLineas);
        while ($rowLinea = $bd->SigReg($resultLineas)):

            //BUSCO EL MATERIAL
            $rowMat = $bd->VerReg("MATERIAL", "ID_MATERIAL", $rowLinea->ID_MATERIAL);

            //DATOS PARA LOS ERRORES
            $datosLinea = " " . $auxiliar->traduce("Linea", $administrador->ID_IDIOMA) . " $rowLinea->LINEA_MOVIMIENTO_SAP " . $auxiliar->traduce("Material", $administrador->ID_IDIOMA) . " " . $rowMat->REFERENCIA_SGA . " - " . ($administrador->ID_IDIOMA == "ESP" ? $rowMat->DESCRIPCION : $rowMat->DESCRIPCION_ENG) . "<br>";


            //COMPRUEBO QUE TENGA PERMISOS DE ESCRITURA EN EL ALMACEN
            if ($administrador->comprobarAlmacenPermiso($rowLinea->ID_ALMACEN, "Escritura") == false):
                $strError    .= $auxiliar->traduce("No tiene permisos para realizar esta operacion en esta subzona", $administrador->ID_IDIOMA) . $datosLinea;
                $hayErrorSGA = true;
                continue;
            endif;

            //COMPRUEBO QUE NO TENGAN DECISION TOMADA
            if ($rowLinea->DECISION_COMPRADOR != NULL):
                $strError    .= $auxiliar->traduce("Esta línea ya tiene una decisión tomada por el comprador", $administrador->ID_IDIOMA) . $datosLinea;
                $hayErrorSGA = true;
                continue;
            endif;

            //COMPRUEBO QUE HAYA STOCK SUFICIENTE EN MATERIAL UBICACION PROVEEDOR
            $rowMatUbi = $bd->VerRegRest("MATERIAL_UBICACION", "ID_MATERIAL = $rowLinea->ID_MATERIAL AND ID_UBICACION = $rowLinea->ID_UBICACION_DESTINO AND ID_MATERIAL_FISICO " . ($rowLinea->ID_MATERIAL_FISICO == NULL ? 'IS NULL' : "= $rowLinea->ID_MATERIAL_FISICO") . " AND ID_TIPO_BLOQUEO " . ($rowLinea->ID_TIPO_BLOQUEO == NULL ? 'IS NULL' : "= $rowLinea->ID_TIPO_BLOQUEO") . " AND ID_ORDEN_TRABAJO_MOVIMIENTO " . ($rowLinea->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? 'IS NULL' : "= $rowLinea->ID_ORDEN_TRABAJO_MOVIMIENTO") . " AND ID_INCIDENCIA_CALIDAD " . ($rowLinea->ID_INCIDENCIA_CALIDAD == NULL ? 'IS NULL' : "= $rowLinea->ID_INCIDENCIA_CALIDAD"), "No");

            //COMPRUEBO QUE HAYA CANTIDAD SUFICIENTE EN MATERIAL UBICACION
            if ($rowMatUbi->STOCK_TOTAL < $rowLinea->CANTIDAD):
                $strError    .= $auxiliar->traduce("No hay suficiente stock para realizar esta acción", $administrador->ID_IDIOMA) . $datosLinea;
                $hayErrorSGA = true;
                continue;
            endif;

            //BUSCO LA ORDEN DE TRABAJO MOVIMIENTO
            $rowOrdenTrabajoMovimiento = $bd->VerReg("ORDEN_TRABAJO_MOVIMIENTO", "ID_ORDEN_TRABAJO_MOVIMIENTO", $rowLinea->ID_ORDEN_TRABAJO_MOVIMIENTO);

            //BUSCO LA ORDEN DE TRABAJO
            $rowOrdenTrabajo = $bd->VerReg("ORDEN_TRABAJO", "ID_ORDEN_TRABAJO", $rowOrdenTrabajoMovimiento->ID_ORDEN_TRABAJO);

            //BUSCO EL CENTRO DE LA OT
            $rowCentroOT = $bd->VerReg("CENTRO", "ID_CENTRO", $rowOrdenTrabajo->ID_CENTRO);

            //BUSCO LA SOCIEDAD DE LA OT
            $rowSociedadOT = $bd->VerReg("SOCIEDAD", "ID_SOCIEDAD", $rowCentroOT->ID_SOCIEDAD);

            //COMPRUEBO QUE TODAS LAS LINEAS SEAN CON/SIN ENTRADA COMPRA
            if ($Sistema_OT == NULL):
                $Sistema_OT = $rowOrdenTrabajo->SISTEMA_OT;
            else:
                if ($Sistema_OT != $rowOrdenTrabajo->SISTEMA_OT):
                    $strError    .= $auxiliar->traduce("Esta mezclando lineas de material estropeado de Ots con lineas sin orden de compra", $administrador->ID_IDIOMA) . $datosLinea;
                    $hayErrorSGA = true;
                    continue;
                endif;
            endif;

            //COMPRUEBO QUE EL TIPO BLOQUEO NO SEA NULO
            if ($rowLinea->ID_TIPO_BLOQUEO == NULL):
                $strError    .= $auxiliar->traduce("La linea no tiene tipo bloqueo asociado", $administrador->ID_IDIOMA) . $datosLinea;
                $hayErrorSGA = true;
                continue;
            endif;

            //BUSCO EL TIPO DE BLOQUEO
            $rowTipoBloqueo = $bd->VerReg("TIPO_BLOQUEO", "ID_TIPO_BLOQUEO", $rowLinea->ID_TIPO_BLOQUEO);

            //BUSCO EL ALMACEN DE PROVEEDOR
            $rowAlmacenProveedor = $bd->VerReg("ALMACEN", "ID_ALMACEN", $rowLinea->ID_ALMACEN_DESTINO);

            //BUSCO EL CENTRO DE PROVEEDOR
            $rowCentroProveedor = $bd->VerReg("CENTRO", "ID_CENTRO", $rowAlmacenProveedor->ID_CENTRO);

            //BUSCO LA SOCIEDAD DE PROVEEDOR
            $rowSociedadProveedor = $bd->VerReg("SOCIEDAD", "ID_SOCIEDAD", $rowCentroProveedor->ID_SOCIEDAD);

            //VARIABLE PAA SABER EL NUEVO TIPO ORIGEN DE LA SIGUIENTE OT
            $origenMaterialNuevo = NULL;

            //COMPRUEBO EL ORIGEN DEL MATERIAL
            if ($origenMaterial == NULL):
                if (substr( (string) $rowTipoBloqueo->TIPO_BLOQUEO_INTERNO, 0, 1) == 'X'):
                    $origenMaterial = 'Calidad';
                elseif ($rowSociedadOT->ID_SOCIEDAD == $rowSociedadProveedor->ID_SOCIEDAD):
                    $origenMaterial = 'Orden Trabajo';
                elseif ($rowSociedadOT->ID_SOCIEDAD != $rowSociedadProveedor->ID_SOCIEDAD):
                    $origenMaterial = 'Traslado Internacional';
                else:
                    $html->PagError("ERROR NO CONTROLADO");
                endif;
            else:
                if (substr( (string) $rowTipoBloqueo->TIPO_BLOQUEO_INTERNO, 0, 1) == 'X'):
                    $origenMaterialNuevo = 'Calidad';
                elseif ($rowSociedadOT->ID_SOCIEDAD == $rowSociedadProveedor->ID_SOCIEDAD):
                    $origenMaterialNuevo = 'Orden Trabajo';
                elseif ($rowSociedadOT->ID_SOCIEDAD != $rowSociedadProveedor->ID_SOCIEDAD):
                    $origenMaterialNuevo = 'Traslado Internacional';
                else:
                    $html->PagError("ERROR NO CONTROLADO");
                endif;
                if ($origenMaterial != $origenMaterialNuevo):
                    $strError    .= $auxiliar->traduce("Esta mezclando lineas con diferente origen del material", $administrador->ID_IDIOMA) . $datosLinea;
                    $hayErrorSGA = true;
                    continue;
                endif;
            endif;

            //ACTUALIZO MATERIAL_UBICACION EN PROVEEDOR
            $sqlUpdate = "UPDATE MATERIAL_UBICACION SET
                            STOCK_TOTAL = STOCK_TOTAL - $rowLinea->CANTIDAD
                            , STOCK_BLOQUEADO = STOCK_BLOQUEADO - $rowLinea->CANTIDAD
                            WHERE ID_MATERIAL_UBICACION = $rowMatUbi->ID_MATERIAL_UBICACION";
            $bd->ExecSQL($sqlUpdate);

            //DOY DE BAJA EL ASIENTO EN PROVEEDOR
            $sqlUpdate = "UPDATE ASIENTO SET
                            BAJA = 1
                            , FECHA_BAJA = '" . date("Y-m-d H:i:s") . "'
                            WHERE ID_MOVIMIENTO_SALIDA_LINEA = $rowLinea->ID_MOVIMIENTO_SALIDA_LINEA AND TIPO_ASIENTO = 'Recepción de Material Estropeado en Proveedor' AND BAJA = 0 ";
            $bd->ExecSQL($sqlUpdate);

            //BUSCO LA UBICACION DESTINO EN FUNCION DE SI TIENE PEDIDO O NO
            if ($rowLinea->ID_PEDIDO_SALIDA == NULL): //VERSION MOVIMIENTO DIRECTO A PROVEEDOR
                $idUbiDestino = $rowLinea->ID_UBICACION;
            else: //TIENE PEDIDO, BUSCO LA UBICACION DE EMBARQUE
                $rowUbiDestino = $bd->VerRegRest("UBICACION", "ID_ALMACEN = $rowLinea->ID_ALMACEN AND TIPO_UBICACION = 'Embarque'", "No");
                if ($rowUbiDestino == false):
                    $strError    .= $auxiliar->traduce("No se puede anular el envio a proveedor por no haber definidad un ubicacion de embarque", $administrador->ID_IDIOMA) . $datosLinea;
                    $hayErrorSGA = true;
                    continue;
                endif;
                $idUbiDestino = $rowUbiDestino->ID_UBICACION;
            endif;

            //COMPRUEBO SI EXISTE MATERIAL UBICACION ORIGEN (PUEDE NO EXISTIR SI SE HA HECHO HA CAMBIADO MATERIAL EN GARANTIA A NO GARANTIA)
            $NotificaErrorPorEmail = "No";
            $rowMatUbiOrigen       = $bd->VerRegRest("MATERIAL_UBICACION", "ID_MATERIAL = $rowLinea->ID_MATERIAL AND ID_UBICACION = $idUbiDestino AND ID_MATERIAL_FISICO " . ($rowLinea->ID_MATERIAL_FISICO == NULL ? 'IS NULL' : "= $rowLinea->ID_MATERIAL_FISICO") . " AND ID_TIPO_BLOQUEO = $rowLinea->ID_TIPO_BLOQUEO AND ID_ORDEN_TRABAJO_MOVIMIENTO = $rowLinea->ID_ORDEN_TRABAJO_MOVIMIENTO AND ID_INCIDENCIA_CALIDAD " . ($rowLinea->ID_INCIDENCIA_CALIDAD == NULL ? 'IS NULL' : "= $rowLinea->ID_INCIDENCIA_CALIDAD"), "No");
            unset($NotificaErrorPorEmail);
            if ($rowMatUbiOrigen == false):
                $sqlInsert = "INSERT INTO MATERIAL_UBICACION SET
											ID_MATERIAL = $rowLinea->ID_MATERIAL
											, ID_UBICACION = $idUbiDestino
											, ID_MATERIAL_FISICO = " . ($rowLinea->ID_MATERIAL_FISICO == NULL ? 'NULL' : "$rowLinea->ID_MATERIAL_FISICO") . "
											, ID_TIPO_BLOQUEO = $rowLinea->ID_TIPO_BLOQUEO
											, ID_ORDEN_TRABAJO_MOVIMIENTO = $rowLinea->ID_ORDEN_TRABAJO_MOVIMIENTO
											, ID_INCIDENCIA_CALIDAD = " . ($rowLinea->ID_INCIDENCIA_CALIDAD == NULL ? 'NULL' : "$rowLinea->ID_INCIDENCIA_CALIDAD");
                $bd->ExecSQL($sqlInsert);
                $idMatUbiOrigen = $bd->IdAsignado();
            else:
                $idMatUbiOrigen = $rowMatUbiOrigen->ID_MATERIAL_UBICACION;
            endif;

            //INCREMENTO LA CANTIDAD EN MATERIAL UBICACION ORIGEN
            $sqlUpdate = "UPDATE MATERIAL_UBICACION SET
                            STOCK_TOTAL = STOCK_TOTAL + $rowLinea->CANTIDAD
                            , STOCK_BLOQUEADO = STOCK_BLOQUEADO + $rowLinea->CANTIDAD
                            WHERE ID_MATERIAL_UBICACION = $idMatUbiOrigen";
            $bd->ExecSQL($sqlUpdate);//exit($sqlUpdate);

            //ACCIONES ESPECIFICAS SI TIENE PEDIDO
            if ($rowLinea->ID_PEDIDO_SALIDA != NULL):
                //ACTUALIZO EL ESTADO DE LA LINEA
                $sqlUpdate = "UPDATE MOVIMIENTO_SALIDA_LINEA SET
                                ESTADO = 'Transmitido a SAP'
                                , FECHA_EXPEDICION = '0000-00-00 00:00:00'
                                , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                WHERE ID_MOVIMIENTO_SALIDA_LINEA = $rowLinea->ID_MOVIMIENTO_SALIDA_LINEA";
                $bd->ExecSQL($sqlUpdate);

                //ACTUALIZO LA CABECERA DEL MOVIMIENTO
                $this->actualizarEstadoMovimientoSalida($rowLinea->ID_MOVIMIENTO_SALIDA);

                //ACTUALIZO EL ESTADO DEL PEDIDO
                $sqlUpdate = "UPDATE PEDIDO_SALIDA SET ESTADO = 'En Entrega' WHERE ID_PEDIDO_SALIDA = $rowLinea->ID_PEDIDO_SALIDA";
                $bd->ExecSQL($sqlUpdate);

                //ACTUALIZO LA ORDEN DE RECOGIDA
                $sqlUpdate = "UPDATE EXPEDICION SET
                                FECHA_EXPEDICION = '0000-00-00 00:00:00'
                                WHERE ID_EXPEDICION = $rowLinea->ID_EXPEDICION";
                $bd->ExecSQL($sqlUpdate);

                //ACTUALIZO EL ESTADO DE LA ORDEN DE TRANSPORTE
                $expedicion->actualizar_estado_orden_transporte($rowLinea->ID_EXPEDICION);
            endif;
            //FIN ACCIONES ESPECIFICAS SI TIENE PEDIDO
        endwhile;

        //ACCIONES ESPECIFICAS SI NO TIENE PEDIDO
        if ($rowMov->ID_PEDIDO_SALIDA == NULL):

            //BORRO EL MOVIMIENTO DE SALIDA
            $sqlUpdate = "UPDATE MOVIMIENTO_SALIDA SET
                            BAJA = 1
                            , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                            WHERE ID_MOVIMIENTO_SALIDA = $rowMov->ID_MOVIMIENTO_SALIDA";
            $bd->ExecSQL($sqlUpdate);

            // LOG MOVIMIENTOS
            $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Anulación", "Mov. salida", $rowMov->ID_MOVIMIENTO_SALIDA, "");

            //SOLO INFORMAMOS A SAP SI ESTA MARCADA ASI LA OT
            if ($rowOrdenTrabajo->ENVIO_A_SAP == 1):

                //ENVIO A SAP EL MOVIMIENTO DIRECTO A PROVEEDOR
                $resultado = $sap->AnularAjusteTraspasoMaterialAProveedor($rowMov->ID_MOVIMIENTO_SALIDA, $origenMaterial);
                if ($resultado['RESULTADO'] != 'OK'): //No se ha podido grabar la información en SAP
                    foreach ($resultado['ERRORES'] as $arr):
                        $hayErrorAnulacionSAP = true;
                        $strError             .= $auxiliar->traduce("Se han producido los siguientes errores en el intercambio de informacion con SAP", $administrador->ID_IDIOMA) . ":<br><br>";

                        foreach ($arr as $mensaje_error):
                            $strError = $strError . $mensaje_error . "<br>";
                        endforeach;
                    endforeach;

                    //DESHAGO LA TRANSACCION
                    //$bd->rollback_transaction();

                    //GRABO EL ERROR PRODUCIDO EN BASE DE DATOS
                    //$sap->InsertarErrores($resultado);

                    //$html->PagError("ErrorSAP");
                endif;

            endif;
            //FIN SOLO LLAMAMOS A SAP EN CASO DE QUE EL MATERIAL A TRANFERIR TENGA ENTRADA CON COMPRA
        endif;
        //FIN ACCIONES ESPECIFICAS SI NO TIENE PEDIDO

        //DECLARO UN ARRAY PARA DEVOLVER DATOS
        $arrDevuelto                                = array();
        $arrDevuelto['errores']                     = $strError;
        $arrDevuelto['error_anulacion']             = $hayErrorSGA;
        $arrDevuelto['error_transmision_anulacion'] = $hayErrorAnulacionSAP;
        $arrDevuelto['resultado']                   = $resultado;


        //DEVUELVO EL ARRAY
        return $arrDevuelto;


    }

    function anularLineaMovimientoEntrada($idLineaMov, $anulacion_stock = "Si", $devolver_error = false)
    {
        global $bd;
        global $administrador;
        global $html;
        global $mat;
        global $sap;
        global $auxiliar;
        global $pedido;
        global $necesidad;;
        global $reserva;
        global $strError;

        //ARRAY ERROR
        $arr_error = array();

        //BUSCO LA LINEA DEL MOVIMIENTO A ANULAR
        $NotificaErrorPorEmail = "No";
        $rowMovLinea           = $bd->VerReg("MOVIMIENTO_ENTRADA_LINEA", "ID_MOVIMIENTO_ENTRADA_LINEA", $idLineaMov, "No");
        unset($NotificaErrorPorEmail);

        //COMPRUEBO QUE TENGA PERMISOS DE ESCRITURA EN LA UBICACION
        if ($devolver_error == false) :
            $html->PagErrorCondicionado($administrador->comprobarUbicacionPermiso($rowMovLinea->ID_UBICACION, "Escritura"), "==", false, "SinPermisosSubzona");
        else:
            if ($administrador->comprobarUbicacionPermiso($rowMovLinea->ID_UBICACION, "Escritura") == false):
                $arr_error['ERROR'] = $auxiliar->traduce("No tiene permisos para realizar esta operación en esta subzona", $administrador->ID_IDIOMA) . ".";

                return $arr_error;
            endif;
        endif;

        //BUSCO EL MATERIAL DE LA LINEA DEL MOVIMIENTO A ANULAR
        $NotificaErrorPorEmail = "No";
        $rowMat                = $bd->VerReg("MATERIAL", "ID_MATERIAL", $rowMovLinea->ID_MATERIAL, "No");
        unset($NotificaErrorPorEmail);

        //BUSCO EL MOVIMIENTO
        $NotificaErrorPorEmail   = "No";
        $idMovimiento            = $rowMovLinea->ID_MOVIMIENTO_ENTRADA;
        $rowMov                  = $bd->VerReg("MOVIMIENTO_ENTRADA", "ID_MOVIMIENTO_ENTRADA", $idMovimiento, "No");
        $estadoInicialMovimiento = $rowMov->ESTADO;
        unset($NotificaErrorPorEmail);

        //COMPRUEBO QUE NO ESTE DADO DE BAJA
        if ($devolver_error == false) :
            $html->PagErrorCondicionado($rowMov->BAJA, "==", 1, "MovimientoBaja");
        else:
            if ($rowMov->BAJA == 1):
                $arr_error['ERROR'] = $auxiliar->traduce("No se puede realizar la operación correspondiente porque el movimiento está dado de baja", $administrador->ID_IDIOMA);

                return $arr_error;
            endif;
        endif;

        //BUSCO LA RECEPCION
        $NotificaErrorPorEmail  = "No";
        $rowRecepcion           = $bd->VerReg("MOVIMIENTO_RECEPCION", "ID_MOVIMIENTO_RECEPCION", $rowMov->ID_MOVIMIENTO_RECEPCION, "No");
        $estadoInicialRecepcion = $rowMov->ESTADO;
        unset($NotificaErrorPorEmail);

        //BUSCO EL CENTRO FISICO
        $NotificaErrorPorEmail = "No";
        $rowCentroFisico       = $bd->VerReg("CENTRO_FISICO", "ID_CENTRO_FISICO", $rowRecepcion->ID_CENTRO_FISICO, "No");
        unset($NotificaErrorPorEmail);

        if (($rowMov->TIPO_MOVIMIENTO == 'PedidoProveedor') || ($rowMov->TIPO_MOVIMIENTO == 'PedidoDevolucionVenta') || ($rowMov->TIPO_MOVIMIENTO == 'PedidoCompraSGAManual') || ($rowMov->TIPO_MOVIMIENTO == 'Construccion')): //TIPO DE PEDIDO DE PROVEEDOR
            //BUSCO EL PEDIDO DE ENTRADA
            $NotificaErrorPorEmail = "No";
            $rowPed                = $bd->VerReg("PEDIDO_ENTRADA", "ID_PEDIDO_ENTRADA", $rowMovLinea->ID_PEDIDO, "No");

            //COMPRUEBO QUE EL PEDIDO NO ESTE FACTURADO POR SAP
            if ($devolver_error == false) :
                $html->PagErrorCondicionado($sap->pedidoEntradaFacturado($rowPed->ID_PEDIDO_ENTRADA), "==", true, "PedidoEntradaFacturado");
            else:
                if ($sap->pedidoEntradaFacturado($rowPed->ID_PEDIDO_ENTRADA)):
                    $arr_error['ERROR'] = $auxiliar->traduce("El pedido de entrada ya esta facturado", $administrador->ID_IDIOMA);

                    return $arr_error;
                endif;
            endif;
        endif;

        //COMPRUEBO QUE SE ESTA EN EL ESTADO CORRECTO
        $posibleAnular = false;
        if (
            ((($rowMov->ESTADO == "Procesado") || ($rowMov->ESTADO == "En Ubicacion") || ($rowMov->ESTADO == "Ubicado") || ($rowMov->ESTADO == "Escaneado y Finalizado")) && ($rowRecepcion->VIA_RECEPCION == 'PDA')) ||
            ((($rowMov->ESTADO == "Ubicado") || ($rowMov->ESTADO == "Escaneado y Finalizado")) && ($rowRecepcion->VIA_RECEPCION == 'WEB'))
        ):
            $posibleAnular = true;
        endif;
        if ($devolver_error == false) :
            $html->PagErrorCondicionado(($posibleAnular), "==", false, "AccionEnEstadoIncorrecto");
        else:
            if (!$posibleAnular):
                $arr_error['ERROR'] = $auxiliar->traduce("No es posible realizar esta acción en el estado actual del Movimiento", $administrador->ID_IDIOMA);

                return $arr_error;
            endif;
        endif;

        //COMPRUEBO QUE LA LINEA NO ESTE ANULADA YA
        if ($devolver_error == false) :
            $html->PagErrorCondicionado($rowMovLinea->LINEA_ANULADA, "==", 1, "LineaAnulada");
        else:
            if ($rowMovLinea->LINEA_ANULADA == 1):
                $auxiliar->traduce("La línea está anulada, no puede realizar la operación correspondiente", $administrador->ID_IDIOMA);
            endif;
        endif;

        //TIPO_BLOQUEO PREVENTIVO
        $rowTipoBloqueoPreventivo = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "SP", "No");
        $idTipoBloqueoPreventivo  = $rowTipoBloqueoPreventivo->ID_TIPO_BLOQUEO;
        //BUSCO EL TIPO DE BLOQUEO RESERVADO
        $rowTipoBloqueoReservado = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "RV");
        $idTipoBloqueoReservado  = $rowTipoBloqueoReservado->ID_TIPO_BLOQUEO;
        //BUSCO EL TIPO DE BLOQUEO RESERVADO PLANIFICADO
        $rowTipoBloqueoReservadoPlanificado = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "RVP");
        $idTipoBloqueoReservadoPlanificado  = $rowTipoBloqueoReservadoPlanificado->ID_TIPO_BLOQUEO;
        //TIPO_BLOQUEO RETENIDO POR CALIDAD NO PREVENTIVO
        $rowTipoBloqueoRetenidoPorCalidadNoPreventivo = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "XRC", "No");
        $idTipoBloqueoRetenidoPorCalidadNoPreventivo  = $rowTipoBloqueoRetenidoPorCalidadNoPreventivo->ID_TIPO_BLOQUEO;
        //TIPO_BLOQUEO RETENIDO POR CALIDAD PREVENTIVO
        $rowTipoBloqueoRetenidoPorCalidadPreventivo = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "XRCP", "No");
        $idTipoBloqueoRetenidoPorCalidadPreventivo  = $rowTipoBloqueoRetenidoPorCalidadPreventivo->ID_TIPO_BLOQUEO;
        //TIPO BLOQUEO PDTE DEVOLVER PROVEEODR CALIDAD
        $rowTipoBloqueoLineaDevolverProveedorCalidad = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "SPDPC");

        //BUSCO LA TRANSFERENCIA DE ESTA LINEA
        $sqlTransferencias    = "SELECT DISTINCT *
                                    FROM MOVIMIENTO_TRANSFERENCIA 
                                    WHERE  TIPO = 'Recepcion' AND ID_MOVIMIENTO_ENTRADA_LINEA = $rowMovLinea->ID_MOVIMIENTO_ENTRADA_LINEA AND BAJA = 0";
        $resultTransferencias = $bd->ExecSQL($sqlTransferencias);

        $arrUbisDestino = array();

        while ($rowTransferencia = $bd->SigReg($resultTransferencias)):

            //SI NO SE HA ANULADO LA IC DE LA UBICACION DESTINO
            if (!isset($arrUbisDestino[$rowTransferencia->ID_UBICACION_DESTINO])):

                $arrUbisDestino[$rowTransferencia->ID_UBICACION_DESTINO] = 1;

                //SI TIENE INCIDENCIA DE CALIDAD
                $idICTrans                        = NULL;
                $GLOBALS["NotificaErrorPorEmail"] = "No";
                $rowIC                            = $bd->VerRegRest("INCIDENCIA_CALIDAD", "ID_MOVIMIENTO_ENTRADA_LINEA= $idLineaMov AND ID_UBICACION_TRANSFERENCIA =$rowTransferencia->ID_UBICACION_DESTINO", "No");
                $idICTrans                        = $rowIC->ID_INCIDENCIA_CALIDAD;

                //SI TIENE INCIDENCIA Y STOCK BLOQUEADO HAREMOS:
                // 1º UN CAMBIO DE ESTADO PREVIO
                // 2º DESBLOQUEAR EL STOCK PARA REALIZAR LA ANULACION DE LA LINEA DE MANERA HABITUAL

                if (($idICTrans != NULL)):

                    //BUSCO SI HAY UN CAMBIO DE ESTADO QUE PASO EL MATERIAL DE CALIDAD A LOGISTICA INVERSA
                    $sqlCambioEstado    = "SELECT CE.ID_CAMBIO_ESTADO, CE.ID_TIPO_BLOQUEO_FINAL, CE.ID_ORDEN_TRABAJO_MOVIMIENTO
                                FROM CAMBIO_ESTADO CE
                                INNER JOIN ORDEN_TRABAJO_MOVIMIENTO OTM ON OTM.ID_ORDEN_TRABAJO_MOVIMIENTO = CE.ID_ORDEN_TRABAJO_MOVIMIENTO
                                INNER JOIN ORDEN_TRABAJO OT ON OT.ID_ORDEN_TRABAJO = OTM.ID_ORDEN_TRABAJO
                                WHERE OT.SISTEMA_OT = 'SGA IC' AND CE.TIPO_CAMBIO_ESTADO = 'PasoCicloCalidadCicloLogisticaInversa' AND CE.ID_CAMBIO_ESTADO_RELACIONADO IS NULL AND CE.BAJA = 0 AND CE.ID_MATERIAL = $rowMovLinea->ID_MATERIAL AND CE.ID_UBICACION = $rowTransferencia->ID_UBICACION_DESTINO AND CE.ID_TIPO_BLOQUEO_INICIAL = " . ($rowMovLinea->ID_TIPO_BLOQUEO == $idTipoBloqueoPreventivo ? $idTipoBloqueoRetenidoPorCalidadPreventivo : $idTipoBloqueoRetenidoPorCalidadNoPreventivo) . " AND CE.ID_MATERIAL_FISICO " . ($rowMovLinea->ID_MATERIAL_FISICO != NULL ? " = $rowMovLinea->ID_MATERIAL_FISICO" : " IS NULL") . " AND CE.ID_ORDEN_TRABAJO_MOVIMIENTO IS NOT NULL AND CE.ID_INCIDENCIA_CALIDAD = $idICTrans";
                    $resultCambioEstado = $bd->ExecSQL($sqlCambioEstado);
                    $rowCambioEstado    = $bd->SigReg($resultCambioEstado);

                    //SI OBTENGO ID DE CAMBIO DE ESTADO Calidad -> Logistica Inversa
                    if ($rowCambioEstado->ID_CAMBIO_ESTADO != NULL):
                        $GLOBALS["NotificaErrorPorEmail"] = "No";
                        $rowMatUbiIC                      = $bd->VerRegRest("MATERIAL_UBICACION", "ID_MATERIAL = $rowMovLinea->ID_MATERIAL AND ID_UBICACION = $rowTransferencia->ID_UBICACION_DESTINO AND ID_MATERIAL_FISICO" . ($rowMovLinea->ID_MATERIAL_FISICO != NULL ? " = $rowMovLinea->ID_MATERIAL_FISICO" : " IS NULL") . " AND ID_TIPO_BLOQUEO = $rowCambioEstado->ID_TIPO_BLOQUEO_FINAL AND ID_ORDEN_TRABAJO_MOVIMIENTO = $rowCambioEstado->ID_ORDEN_TRABAJO_MOVIMIENTO AND ID_INCIDENCIA_CALIDAD = $idICTrans", "No");
                        unset($GLOBALS["NotificaErrorPorEmail"]);
                    else:
                        $GLOBALS["NotificaErrorPorEmail"] = "No";
                        $rowMatUbiIC                      = $bd->VerRegRest("MATERIAL_UBICACION", "ID_MATERIAL = $rowMovLinea->ID_MATERIAL AND ID_UBICACION = $rowTransferencia->ID_UBICACION_DESTINO AND ID_MATERIAL_FISICO" . ($rowMovLinea->ID_MATERIAL_FISICO != NULL ? " = $rowMovLinea->ID_MATERIAL_FISICO" : " IS NULL") . " AND ID_TIPO_BLOQUEO " . ($rowMovLinea->ID_TIPO_BLOQUEO == $idTipoBloqueoPreventivo ? "= $idTipoBloqueoRetenidoPorCalidadPreventivo" : "= $idTipoBloqueoRetenidoPorCalidadNoPreventivo") . " AND ID_ORDEN_TRABAJO_MOVIMIENTO IS NULL AND ID_INCIDENCIA_CALIDAD = $idICTrans", "No");
                        unset($GLOBALS["NotificaErrorPorEmail"]);
                    endif;

                    if ($rowMatUbiIC->STOCK_BLOQUEADO > 0):

                        //INICIO LA TRANSACCION DE CAMBIO DE ESTADO
                        $bd->begin_transaction();

                        //SI OBTENGO ID DE CAMBIO DE ESTADO Calidad -> Logistica Inversa
                        if ($rowCambioEstado->ID_CAMBIO_ESTADO != NULL):
                            //REALIZO LA LLAMADA A SAP Y LA MODIFICACION DE DATOS
                            $arrDevuelto = $mat->AnulacionPasoCicloCalidadCicloLogisticaInversa($rowCambioEstado->ID_CAMBIO_ESTADO);

                            //SI SE HA PRODUCIDO UN ERROR CON LA ANULACION DEL PASO DEL CICLO DE CALIDAD AL CICLO DE LOGISITCA INVERSA MUESTRO LOS ERRORES POR PANTALLA
                            if (($arrDevuelto['error_SGA'] == true) || ($arrDevuelto['resultado']['RESULTADO'] != 'OK')):

                                //DECLARO LA VARIABLE GLOBAL $strError
                                global $strError;

                                //ESTABLEZCO LOS ERRORES
                                $strError = $arrDevuelto['errores'];

                                //SI HAY ERRORES LOS MUESTRO POR PANTALLA
                                if ($devolver_error == false) :
                                    $html->PagErrorCondicionado($strError, "!=", "", "ErroresAnulacionPasoCicloCalidadCicloLogisticaInversa");
                                else:
                                    if ($strError != ""):
                                        $arr_error['ERROR'] = $auxiliar->traduce("Se han producido los siguientes errores al revertir el paso del ciclo de calidad al ciclo de logistica inversa", $administrador->ID_IDIOMA) . ": " . $strError;

                                        return $arr_error;
                                    endif;
                                endif;
                            else:
                                $idMatUbiIC  = $arrDevuelto['idMaterialUbicacionDestino'];
                                $rowMatUbiIC = $bd->VerReg("MATERIAL_UBICACION", "ID_MATERIAL_UBICACION", $idMatUbiIC);
                            endif;
                            //FIN SI SE HA PRODUCIDO UN ERROR CON LA ANULACION DEL PASO DEL CICLO DE CALIDAD AL CICLO DE LOGISITCA INVERSA MUESTRO LOS ERRORES POR PANTALLA
                        endif;
                        //FIN SI OBTENGO ID DE CAMBIO DE ESTADO Calidad -> Logistica Inversa

                        //GENERO EL CAMBIO DE ESTADO
                        $sqlInsert = "INSERT INTO CAMBIO_ESTADO SET
                            FECHA = '" . date("Y-m-d H:i:s") . "'
                            , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                            , ID_MATERIAL = $rowMovLinea->ID_MATERIAL
                            , ID_MATERIAL_FISICO = " . ($rowMovLinea->ID_MATERIAL_FISICO == NULL ? 'NULL' : $rowMovLinea->ID_MATERIAL_FISICO) . "
                            , ID_UBICACION = $rowTransferencia->ID_UBICACION_DESTINO
                            , CANTIDAD = $rowMatUbiIC->STOCK_TOTAL
                            , ID_TIPO_BLOQUEO_INICIAL = $rowMatUbiIC->ID_TIPO_BLOQUEO
                            , ID_TIPO_BLOQUEO_FINAL = " . ($rowMovLinea->ID_TIPO_BLOQUEO == NULL ? 'NULL' : $rowMovLinea->ID_TIPO_BLOQUEO) . "
                            , OBSERVACIONES = '" . $auxiliar->traduce("Reversión de una entrada con incidencia", $administrador->ID_IDIOMA) . "'";
                        $bd->ExecSQL($sqlInsert);//exit($sqlInsert);
                        $idCambioEstado = $bd->IdAsignado();

                        //DECREMENTO MATERIAL_UBICACION ORIGEN
                        $sqlUpdate = "UPDATE MATERIAL_UBICACION SET
                            STOCK_TOTAL = STOCK_TOTAL - $rowMatUbiIC->STOCK_TOTAL
                            , STOCK_BLOQUEADO = STOCK_BLOQUEADO - $rowMatUbiIC->STOCK_BLOQUEADO
                            WHERE ID_MATERIAL_UBICACION = $rowMatUbiIC->ID_MATERIAL_UBICACION";
                        $bd->ExecSQL($sqlUpdate);

                        //BUSCO MATERIAL_UBICACION DESTINO
                        $NotificaErrorPorEmail = "No";
                        $clausulaWhere         = "ID_MATERIAL = $rowMovLinea->ID_MATERIAL AND ID_UBICACION = $rowTransferencia->ID_UBICACION_DESTINO AND ID_MATERIAL_FISICO " . ($rowMovLinea->ID_MATERIAL_FISICO == "" ? "IS NULL" : "= $rowMovLinea->ID_MATERIAL_FISICO") . " AND ID_TIPO_BLOQUEO " . ($rowMovLinea->ID_TIPO_BLOQUEO == NULL ? "IS NULL" : "= $rowMovLinea->ID_TIPO_BLOQUEO") . " AND ID_ORDEN_TRABAJO_MOVIMIENTO IS NULL AND ID_INCIDENCIA_CALIDAD IS NULL";
                        $rowMatUbiDestino      = $bd->VerRegRest("MATERIAL_UBICACION", $clausulaWhere, "No");
                        if ($rowMatUbiDestino == false):
                            //CREO MATERIAL UBICACION DESTINO
                            $sqlInsert = "INSERT INTO MATERIAL_UBICACION SET
                                ID_MATERIAL = $rowMovLinea->ID_MATERIAL
                                , ID_MATERIAL_FISICO = " . ($rowMovLinea->ID_MATERIAL_FISICO == "" ? 'NULL' : "$rowMovLinea->ID_MATERIAL_FISICO") . "
                                , ID_UBICACION = $rowTransferencia->ID_UBICACION_DESTINO
                                , ID_TIPO_BLOQUEO = " . ($rowMovLinea->ID_TIPO_BLOQUEO == "" ? 'NULL' : "$rowMovLinea->ID_TIPO_BLOQUEO") . "
                                , ID_ORDEN_TRABAJO_MOVIMIENTO = NULL
                                , ID_INCIDENCIA_CALIDAD = NULL";
                            $bd->ExecSQL($sqlInsert);
                            $idMatUbiDestino = $bd->IdAsignado();
                        else:
                            $idMatUbiDestino = $rowMatUbiDestino->ID_MATERIAL_UBICACION;
                        endif;

                        //INCREMENTO MATERIAL_UBICACION DESTINO
                        $sqlUpdate = "UPDATE MATERIAL_UBICACION SET
                            STOCK_TOTAL = STOCK_TOTAL + $rowMatUbiIC->STOCK_TOTAL
                            , STOCK_OK = STOCK_OK + " . ($rowMovLinea->ID_TIPO_BLOQUEO == NULL ? $rowMatUbiIC->STOCK_TOTAL : 0) . "
                            , STOCK_BLOQUEADO = STOCK_BLOQUEADO + " . ($rowMovLinea->ID_TIPO_BLOQUEO == NULL ? 0 : $rowMatUbiIC->STOCK_TOTAL) . "
                            WHERE ID_MATERIAL_UBICACION = $idMatUbiDestino";
                        $bd->ExecSQL($sqlUpdate);

                        //VARIABLE PARA SABER SI TENGO QUE ENVIAR EL CAMBIO DE ESTADO A SAP
                        $bloqueosIgualesParaSAP = false;

                        //BUSCO EL TIPO DE BLOQUEO INICIAL
                        $rowTipoBloqueoInicial = $bd->VerReg("TIPO_BLOQUEO", "ID_TIPO_BLOQUEO", $rowMatUbiIC->ID_TIPO_BLOQUEO);

                        //BUSCO EL TIPO DE BLOQUEO FINAL
                        if ($rowMovLinea->ID_TIPO_BLOQUEO != NULL):
                            $rowTipoBloqueoFinal = $bd->VerReg("TIPO_BLOQUEO", "ID_TIPO_BLOQUEO", $rowMovLinea->ID_TIPO_BLOQUEO);
                        endif;

                        //COMPRUEBO SI EL TIPO DE BLOQUEO INICIAL Y FINAL SON IGUALES PARA SAP
                        if (($rowMovLinea->ID_TIPO_BLOQUEO != NULL) && ($rowTipoBloqueoInicial->TIPO_BLOQUEO_SAP == $rowTipoBloqueoFinal->TIPO_BLOQUEO_SAP)):
                            $bloqueosIgualesParaSAP = true;
                        endif;

                        //SI LOS TIPOS DE BLOQUEO INICIAL Y FINAL SON DIFERENTES PARA SAP, HAGO LA LLAMADA AL WEB SERVICE
                        if ($bloqueosIgualesParaSAP == false):

                            //ACTUALIZO LA VARIABLE QUE ME INDICA SI SE HA REALIZADO LLAMADA A CAMBIO DE ESTADO
                            $llamadaBloqueadoToLibre = true;

                            //ENVIO A SAP EL CAMBIO DE ESTADO
                            $resultado = $sap->AjusteCambioEstado($idCambioEstado);
                            if ($resultado['RESULTADO'] != 'OK'): //No se ha podido grabar la información en SAP
                                foreach ($resultado['ERRORES'] as $arr):
                                    foreach ($arr as $mensaje_error):
                                        if ($devolver_error == false) :
                                            $strError = $strError . $mensaje_error . "<br>";
                                        else:
                                            $strError = $strError . $mensaje_error . ". ";
                                        endif;
                                    endforeach;
                                endforeach;

                                //DESHAGO LA TRANSACCION
                                $bd->rollback_transaction();

                                //GRABO EL ERROR PRODUCIDO EN BASE DE DATOS
                                $sap->InsertarErrores($resultado);

                                if ($devolver_error == false) :
                                    $html->PagError("ErrorSAP");
                                else:
                                    return "We have received the following errors from SAP/Se han producido los siguientes errores en el intercambio de información con SAP" . ": " . $strError;
                                endif;
                            endif;
                        endif;
                        //FIN SI LOS TIPOS DE BLOQUEO INICIAL Y FINAL SON DIFERENTES PARA SAP, HAGO LA LLAMADA AL WEB SERVICE

                        //FINALIZO LA TRANSACCION DE CAMBIO DE ESTADO
                        $bd->commit_transaction();
                        
                    endif;
                endif;
                //FIN SI TIENE INCIDENCIA Y STOCK HAREMOS UN CAMBIO DE ESTADO PREVIO
            endif;//FIN SI NO SE HA ANULADO LA IC DE LA UBICACION DESTINO

            //BUSCO MATERIAL UBICACION FINAL DONDE SE DEPOSITÓ EL MATERIAL (UBICACION FINAL)
            $GLOBALS["NotificaErrorPorEmail"] = "No";
            $rowMatUbi                        = $bd->VerRegRest("MATERIAL_UBICACION", "ID_MATERIAL = $rowTransferencia->ID_MATERIAL AND ID_UBICACION = $rowTransferencia->ID_UBICACION_DESTINO AND ID_MATERIAL_FISICO " . ($rowTransferencia->ID_MATERIAL_FISICO == NULL ? 'IS NULL' : " = $rowTransferencia->ID_MATERIAL_FISICO") . " AND ID_TIPO_BLOQUEO " . ($rowTransferencia->ID_TIPO_BLOQUEO == NULL ? 'IS NULL' : "= $rowTransferencia->ID_TIPO_BLOQUEO") . " AND ID_ORDEN_TRABAJO_MOVIMIENTO " . ($rowTransferencia->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? 'IS NULL' : "= $rowTransferencia->ID_ORDEN_TRABAJO_MOVIMIENTO") . " AND ID_INCIDENCIA_CALIDAD " . ($rowTransferencia->ID_INCIDENCIA_CALIDAD == NULL ? 'IS NULL' : "= $rowTransferencia->ID_INCIDENCIA_CALIDAD"), "No");
            unset($GLOBALS["NotificaErrorPorEmail"]);

            //COMPRUEBO QUE EXISTE EL MATERIAL UBICACION ORIGEN (UBICACION FINAL)
            if ($rowMatUbi == false):
                if ($devolver_error == false) :
                    $html->PagError($auxiliar->traduce("No existe la ubicacion destino donde fue desubicado el material", $administrador->ID_IDIOMA));
                else:
                    return $auxiliar->traduce("No existe la ubicacion destino donde fue desubicado el material", $administrador->ID_IDIOMA);
                endif;
            endif;

            //COMPRUEBO QUE EN LA UBICACION ORIGEN (UBICACION FINAL) HAYA SUFICIENTE STOCK
            if ($rowMatUbi->STOCK_TOTAL < $rowTransferencia->CANTIDAD):
                if ($devolver_error == false) :
                    $html->PagError($auxiliar->traduce("No existe stock suficiente para anular la linea seleccionada", $administrador->ID_IDIOMA));
                else:
                    return $auxiliar->traduce("No existe stock suficiente para anular la linea seleccionada", $administrador->ID_IDIOMA);
                endif;
            endif;

            //DECREMENTO CANTIDAD EN MATERIAL UBICACION ORIGEN (DEBERIA SER DE TIPO SALIDA - SM)
            $sqlUpdate = "UPDATE MATERIAL_UBICACION SET
                                    STOCK_TOTAL = STOCK_TOTAL - $rowTransferencia->CANTIDAD
                                    , STOCK_OK = STOCK_OK - " . ($rowTransferencia->ID_TIPO_BLOQUEO == NULL ? $rowTransferencia->CANTIDAD : 0) . "
                                    , STOCK_BLOQUEADO = STOCK_BLOQUEADO - " . ($rowTransferencia->ID_TIPO_BLOQUEO == NULL ? 0 : $rowTransferencia->CANTIDAD) . "
                                    WHERE ID_MATERIAL_UBICACION = $rowMatUbi->ID_MATERIAL_UBICACION";
            $bd->ExecSQL($sqlUpdate);

            //BUSCO LA UBICACION DESTINO (DESDE DONDE SE UBICO EL MATERIAL)
            $GLOBALS["NotificaErrorPorEmail"] = "No";
            $rowMatUbi                        = $bd->VerRegRest("MATERIAL_UBICACION", "ID_MATERIAL = $rowTransferencia->ID_MATERIAL AND ID_UBICACION = $rowTransferencia->ID_UBICACION_ORIGEN AND ID_MATERIAL_FISICO " . ($rowTransferencia->ID_MATERIAL_FISICO == NULL ? 'IS NULL' : " = $rowTransferencia->ID_MATERIAL_FISICO") . " AND ID_TIPO_BLOQUEO " . ($rowTransferencia->ID_TIPO_BLOQUEO == NULL ? 'IS NULL' : "= $rowTransferencia->ID_TIPO_BLOQUEO") . " AND ID_ORDEN_TRABAJO_MOVIMIENTO " . ($rowTransferencia->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? 'IS NULL' : "= $rowTransferencia->ID_ORDEN_TRABAJO_MOVIMIENTO") . " AND ID_INCIDENCIA_CALIDAD " . ($rowTransferencia->ID_INCIDENCIA_CALIDAD == NULL ? 'IS NULL' : "= $rowTransferencia->ID_INCIDENCIA_CALIDAD"), "No");
            unset($GLOBALS["NotificaErrorPorEmail"]);
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

            //BUSCO LAS POSIBLES DEMANDAS DEL MATERIAL UBICACION ORIGINAL
            $sqlDemandas    = "SELECT D.TIPO_DEMANDA, D.ID_DEMANDA, D.ID_PEDIDO_SALIDA_LINEA, D.ID_ORDEN_TRABAJO_LINEA 
                                FROM DEMANDA D
                                INNER JOIN RESERVA R ON R.ID_DEMANDA = D.ID_DEMANDA
                                INNER JOIN RESERVA_LINEA RL ON RL.ID_RESERVA = R.ID_RESERVA
                                WHERE RL.ID_MATERIAL = $rowTransferencia->ID_MATERIAL AND RL.ID_MATERIAL_FISICO " . ($rowTransferencia->ID_MATERIAL_FISICO == NULL ? 'IS NULL' : "= $rowTransferencia->ID_MATERIAL_FISICO") . " AND RL.ID_UBICACION = $rowTransferencia->ID_UBICACION_DESTINO AND RL.ID_TIPO_BLOQUEO " . ($rowTransferencia->ID_TIPO_BLOQUEO == NULL ? 'IS NULL' : "= $rowTransferencia->ID_TIPO_BLOQUEO") . " AND RL.ID_ORDEN_TRABAJO_MOVIMIENTO " . ($rowTransferencia->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? 'IS NULL' : "= $rowTransferencia->ID_ORDEN_TRABAJO_MOVIMIENTO") . " AND RL.ID_INCIDENCIA_CALIDAD " . ($rowTransferencia->ID_INCIDENCIA_CALIDAD == NULL ? 'IS NULL' : "= $rowTransferencia->ID_INCIDENCIA_CALIDAD") . " AND RL.ESTADO_LINEA = 'Reservada' AND R.BAJA = 0 AND RL.BAJA = 0";
            $resultDemandas = $bd->ExecSQL($sqlDemandas);

            //ME GUARDO LA CANTIDAD MOVIDA DE LAS DEMANDAS
            $cantidadDemandasMovida = 0;

            //RECORRO LAS DEMANDAS PARA MOVER EL MATERIAL CORRESPONDIENTE
            while (($rowDemanda = $bd->SigReg($resultDemandas)) && (($rowTransferencia->CANTIDAD - $cantidadDemandasMovida > EPSILON_SISTEMA))):
                //ACCIONES EN FUNCION DEL TIPO DE DEMANDA
                if ($rowDemanda->TIPO_DEMANDA == "Pedido"):
                    //BUSCO LAS RESERVAS LINEA DE LA DEMANDA
                    $resultLineasReserva = $reserva->get_reservas_linea_pedido($rowDemanda->ID_PEDIDO_SALIDA_LINEA, 'Reservada', $rowDemanda->ID_DEMANDA);

                    //SI EXISTE LA DEMANDA
                    if ($resultLineasReserva != false):
                        //BUSCO LAS RESERVAS LINEA ESPECIFICAS DE LA DEMANDA
                        $resultLineasReservaEspecificas = $reserva->get_reservas_lineas_especificas($resultLineasReserva, $rowTransferencia->ID_MATERIAL, $rowTransferencia->ID_UBICACION_DESTINO, $rowTransferencia->ID_TIPO_BLOQUEO, $rowTransferencia->ID_MATERIAL_FISICO, $rowTransferencia->ID_ORDEN_TRABAJO_MOVIMIENTO, $rowTransferencia->ID_INCIDENCIA_CALIDAD);

                        //SI EXISTEN LINEAS DE RESERVA VALIDAS
                        if ($resultLineasReservaEspecificas != false):
                            //CANTIDAD RESERVA LINEAS
                            $cantidadReservasLineas = 0;

                            //RECORRO LAS LINEAS PARA SABER LA CANTIDAD QUE SE PUEDE MOVER
                            while ($rowReservaLinea = $bd->SigReg($resultLineasReservaEspecificas)):
                                $cantidadReservasLineas = $cantidadReservasLineas + $rowReservaLinea->CANTIDAD;
                            endwhile;
                            //FIN RECORRO LAS LINEAS PARA SABER LA CANTIDAD QUE SE PUEDE MOVER

                            //MUEVO EL PUNTERO DE LAS RESERVAS LINEAS
                            $bd->mover($resultLineasReservaEspecificas, 0);

                            //CALCULO LA CANTIDAD A TRANSFERIR DEL REGISTRO CANDIDATO
                            $cantidadDemandasMoverRegistro = min($cantidadReservasLineas, ($rowTransferencia->CANTIDAD - $cantidadDemandasMovida));

                            //LLAMO A MODIFICAR LA UBICACION PARA UNA PARTE
                            $arrDevueltoReservas = $reserva->modificar_ubicacion_reserva_lineas($resultLineasReservaEspecificas, $rowTransferencia->ID_UBICACION_ORIGEN, $cantidadDemandasMoverRegistro);

                            //SI SE DEVUELVEN ERRORES
                            if ((isset($arrDevueltoReservas['error'])) && ($arrDevueltoReservas['error'] != "")):
                                $strError = $arrDevueltoReservas['error'];
                                $html->PagError("ErrorMoverReservasDemandas");
                            endif;

                            //ACTUALIZO LA CANTIDAD DE LAS DEMANDAS MOVIDA
                            $cantidadDemandasMovida = $cantidadDemandasMovida + $cantidadDemandasMoverRegistro;
                        endif;
                    endif;
                    //FIN SI EXISTE LA DEMANDA
                endif;
                //FIN ACCIONES EN FUNCION DEL TIPO DE DEMANDA
            endwhile;
            //FIN RECORRO LAS DEMANDAS PARA MOVER EL MATERIAL CORRESPONDIENTE

            //DAMOS DE BAJA LA TRANSFERENCIA AUTOMATICA
            $sqlUpdate = "UPDATE MOVIMIENTO_TRANSFERENCIA SET
                                    ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                    , BAJA = 1
                                    WHERE ID_MOVIMIENTO_TRANSFERENCIA = $rowTransferencia->ID_MOVIMIENTO_TRANSFERENCIA";
            $bd->ExecSQL($sqlUpdate);

            // LOG MOVIMIENTOS
            $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, 'Anulación', "Transferencia", $rowTransferencia->ID_MOVIMIENTO_TRANSFERENCIA, "");
        endwhile;
        //FIN BUSCO LA TRANSFERENCIA DE ESTA LINEA

        //BUSCAMOS LA INCIDENCIA CALIDAD ASIGNADA (SI ES QUE LA TIENE)
        global $NotificaErrorPorEmail;
        $NotificaErrorPorEmail = "No";
        $rowIC                 = $bd->VerRegRest("INCIDENCIA_CALIDAD", "ID_MOVIMIENTO_ENTRADA_LINEA = $idLineaMov AND ID_UBICACION_TRANSFERENCIA IS NULL", "No");
        $idIC                  = $rowIC->ID_INCIDENCIA_CALIDAD;
        unset($NotificaErrorPorEmail);

        //VARIABLE QUE CONTIENE LA CANTIDAD TOTAL QUE SE PUEDE BORRAR
        $cantidadPosibleBorrar = 0;

        //COMPRUEBO QUE LA CANTIDAD SE ENCUENTRE EN LA UBICACION CORRECTA
        if ($idIC != NULL):
            //BUSCO SI HAY UN CAMBIO DE ESTADO QUE PASO EL MATERIAL DE CALIDAD A LOGISTICA INVERSA
            $sqlCambioEstado    = "SELECT CE.ID_CAMBIO_ESTADO, CE.ID_TIPO_BLOQUEO_FINAL, CE.ID_ORDEN_TRABAJO_MOVIMIENTO
                                FROM CAMBIO_ESTADO CE
                                INNER JOIN ORDEN_TRABAJO_MOVIMIENTO OTM ON OTM.ID_ORDEN_TRABAJO_MOVIMIENTO = CE.ID_ORDEN_TRABAJO_MOVIMIENTO
                                INNER JOIN ORDEN_TRABAJO OT ON OT.ID_ORDEN_TRABAJO = OTM.ID_ORDEN_TRABAJO
                                WHERE OT.SISTEMA_OT = 'SGA IC' AND CE.TIPO_CAMBIO_ESTADO = 'PasoCicloCalidadCicloLogisticaInversa' AND CE.ID_CAMBIO_ESTADO_RELACIONADO IS NULL AND CE.BAJA = 0 AND CE.ID_MATERIAL = $rowMovLinea->ID_MATERIAL AND CE.ID_UBICACION = $rowMovLinea->ID_UBICACION AND CE.ID_TIPO_BLOQUEO_INICIAL = " . ($rowMovLinea->ID_TIPO_BLOQUEO == $idTipoBloqueoPreventivo ? $idTipoBloqueoRetenidoPorCalidadPreventivo : $idTipoBloqueoRetenidoPorCalidadNoPreventivo) . " AND CE.ID_MATERIAL_FISICO " . ($rowMovLinea->ID_MATERIAL_FISICO != NULL ? " = $rowMovLinea->ID_MATERIAL_FISICO" : " IS NULL") . " AND CE.ID_ORDEN_TRABAJO_MOVIMIENTO IS NOT NULL AND CE.ID_INCIDENCIA_CALIDAD = $idIC";
            $resultCambioEstado = $bd->ExecSQL($sqlCambioEstado);
            $rowCambioEstado    = $bd->SigReg($resultCambioEstado);

            //SI OBTENGO ID DE CAMBIO DE ESTADO Calidad -> Logistica Inversa
            if ($rowCambioEstado->ID_CAMBIO_ESTADO != NULL):
                $GLOBALS["NotificaErrorPorEmail"] = "No";
                $rowMatUbiIC                      = $bd->VerRegRest("MATERIAL_UBICACION", "ID_MATERIAL = $rowMovLinea->ID_MATERIAL AND ID_UBICACION = $rowMovLinea->ID_UBICACION AND ID_MATERIAL_FISICO" . ($rowMovLinea->ID_MATERIAL_FISICO != NULL ? " = $rowMovLinea->ID_MATERIAL_FISICO" : " IS NULL") . " AND ID_TIPO_BLOQUEO = $rowCambioEstado->ID_TIPO_BLOQUEO_FINAL AND ID_ORDEN_TRABAJO_MOVIMIENTO = $rowCambioEstado->ID_ORDEN_TRABAJO_MOVIMIENTO AND ID_INCIDENCIA_CALIDAD = $idIC", "No");
                unset($GLOBALS["NotificaErrorPorEmail"]);

                //ACTUALIZO LA CANTIDAD TOTAL QUE SE PUEDE BORRAR
                $cantidadPosibleBorrar = $cantidadPosibleBorrar + $rowMatUbiIC->STOCK_TOTAL;
            else:
                $GLOBALS["NotificaErrorPorEmail"] = "No";
                $rowMatUbiIC                      = $bd->VerRegRest("MATERIAL_UBICACION", "ID_MATERIAL = $rowMovLinea->ID_MATERIAL AND ID_UBICACION = $rowMovLinea->ID_UBICACION AND ID_MATERIAL_FISICO" . ($rowMovLinea->ID_MATERIAL_FISICO != NULL ? " = $rowMovLinea->ID_MATERIAL_FISICO" : " IS NULL") . " AND ID_TIPO_BLOQUEO " . ($rowMovLinea->ID_TIPO_BLOQUEO == $idTipoBloqueoPreventivo ? "= $idTipoBloqueoRetenidoPorCalidadPreventivo" : "= $idTipoBloqueoRetenidoPorCalidadNoPreventivo") . " AND ID_ORDEN_TRABAJO_MOVIMIENTO IS NULL AND ID_INCIDENCIA_CALIDAD = $idIC", "No");
                unset($GLOBALS["NotificaErrorPorEmail"]);

                //ACTUALIZO LA CANTIDAD TOTAL QUE SE PUEDE BORRAR
                $cantidadPosibleBorrar = $cantidadPosibleBorrar + $rowMatUbiIC->STOCK_TOTAL;
            endif;
        endif;

        //SI LA LINEA DEL MOVIMIENTO DE ENTRADA ES STOCK OK O PREVENTIVO
        if (
            ($rowMovLinea->ID_TIPO_BLOQUEO == NULL) ||
            ($rowMovLinea->ID_TIPO_BLOQUEO == $rowTipoBloqueoPreventivo->ID_TIPO_BLOQUEO)
        ):
            //SI EXISTE CANTIDAD RESERVADA, LA PASO A COLA DE RESERVAS
            $sqlDemandas    = "SELECT D.TIPO_DEMANDA, D.ID_DEMANDA, D.ID_PEDIDO_SALIDA_LINEA, D.ID_ORDEN_TRABAJO_LINEA 
                                FROM DEMANDA D
                                INNER JOIN RESERVA R ON R.ID_DEMANDA = D.ID_DEMANDA
                                INNER JOIN RESERVA_LINEA RL ON RL.ID_RESERVA = R.ID_RESERVA
                                WHERE RL.ID_MATERIAL = $rowMovLinea->ID_MATERIAL AND RL.ID_MATERIAL_FISICO " . ($rowMovLinea->ID_MATERIAL_FISICO == NULL ? 'IS NULL' : "= $rowMovLinea->ID_MATERIAL_FISICO") . " AND RL.ID_UBICACION = $rowMovLinea->ID_UBICACION AND RL.ID_TIPO_BLOQUEO IN ('" . $idTipoBloqueoReservado . "', '" . $idTipoBloqueoReservadoPlanificado . "') AND ID_ORDEN_TRABAJO_MOVIMIENTO IS NULL AND ID_INCIDENCIA_CALIDAD IS NULL AND RL.ESTADO_LINEA = 'Reservada' AND R.BAJA = 0 AND RL.BAJA = 0";
            $resultDemandas = $bd->ExecSQL($sqlDemandas);

            //ME GUARDO LA CANTIDAD MOVIDA DE LAS DEMANDAS
            $cantidadDemandasPasadaAColaReservas = 0;

            //RECORRO LAS DEMANDAS PARA PASAR LA CANTIDAD RESERVADA A LA COLA DE RESERVAS
            while (($rowDemanda = $bd->SigReg($resultDemandas)) && (($rowMovLinea->CANTIDAD - $cantidadDemandasPasadaAColaReservas > EPSILON_SISTEMA))):
                //ACCIONES EN FUNCION DEL TIPO DE DEMANDA
                if ($rowDemanda->TIPO_DEMANDA == "Pedido"):
                    //BUSCO LAS RESERVAS LINEA DE LA DEMANDA
                    $resultLineasReserva = $reserva->get_reservas_linea_pedido($rowDemanda->ID_PEDIDO_SALIDA_LINEA, 'Reservada', $rowDemanda->ID_DEMANDA);

                    //SI EXISTE LA DEMANDA
                    if ($resultLineasReserva != false):
                        while (($rowReservaLinea = $bd->SigReg($resultLineasReserva)) && (($rowMovLinea->CANTIDAD - $cantidadDemandasPasadaAColaReservas > EPSILON_SISTEMA))):

                            //CALCULO LA CANTIDAD A QUITAR DE LA RESERVA
                            $cantidadAnularReserva = min(($rowMovLinea->CANTIDAD - $cantidadDemandasPasadaAColaReservas), $rowReservaLinea->CANTIDAD);

                            //CANCELO LA RESERVA DE LINEAS DE OT ASOCIADAS AL CAMBIO DE ESTADO
                            $arrDevueltoReserva = $reserva->anular_reserva($rowDemanda->ID_DEMANDA, $cantidadAnularReserva, 1);

                            //SI SE PRODUCEN ERRORES ABORTO LA OPERACION
                            if (isset($arrDevueltoReserva['error']) && ($arrDevueltoReserva['error'] != "")):
                                //ESTABLEZCO LOS ERRORES
                                $strError .= $arrDevueltoReserva['errores'];

                                //DESHAGO LA TRANSACCION POR CAMBIO DE ESTADO A GENERAR
                                $bd->rollback_transaction();

                                //DEVOLVEMOS EL ERROR
                                $html->PagError("ErroresAnulacionReserva");
                            endif;

                            //INCREMENTO LA CANTIDAD PASADA A COLA
                            $cantidadDemandasPasadaAColaReservas = $cantidadDemandasPasadaAColaReservas + $cantidadAnularReserva;
                        endwhile;
                    endif;
                    //FIN SI EXISTE LA DEMANDA
                endif;
                //FIN ACCIONES EN FUNCION DEL TIPO DE DEMANDA
            endwhile;
            //FIN RECORRO LAS DEMANDAS PARA PASAR LA CANTIDAD RESERVADA A LA COLA DE RESERVAS
        endif;
        //FIN SI LA LINEA DEL MOVIMIENTO DE ENTRADA ES STOCK OK O PREVENTIVO

        //BUSCO EL MATERIAL UBICACION A ANULAR
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowMatUbi                        = $bd->VerRegRest("MATERIAL_UBICACION", "ID_MATERIAL = $rowMovLinea->ID_MATERIAL AND ID_UBICACION = $rowMovLinea->ID_UBICACION AND ID_MATERIAL_FISICO" . ($rowMovLinea->ID_MATERIAL_FISICO != NULL ? " = $rowMovLinea->ID_MATERIAL_FISICO" : " IS NULL") . " AND ID_TIPO_BLOQUEO " . ($rowMovLinea->ID_TIPO_BLOQUEO == NULL ? 'IS NULL' : "= $rowMovLinea->ID_TIPO_BLOQUEO") . " AND ID_ORDEN_TRABAJO_MOVIMIENTO IS NULL AND ID_INCIDENCIA_CALIDAD IS NULL", "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);

        //ACTUALIZO LA CANTIDAD TOTAL QUE SE PUEDE BORRAR
        $cantidadPosibleBorrar = $cantidadPosibleBorrar + $rowMatUbi->STOCK_TOTAL;

        // BUSCO LA UBICACION
        $rowUbi = $bd->VerReg("UBICACION", "ID_UBICACION", $rowMovLinea->ID_UBICACION, "No");

        //BUSCO EL ALMACEN
        $rowAlm = $bd->VerReg("ALMACEN", "ID_ALMACEN", $rowUbi->ID_ALMACEN, "No");

        //BUSCO EL CENTRO FISICO
        $rowCentroFisico = $bd->VerReg("CENTRO_FISICO", "ID_CENTRO_FISICO", $rowAlm->ID_CENTRO_FISICO, "No");

        //SI ES UN ALMACEN CON RADIOFRECUENCIA, EL MATERIAL DEBERÁ ESTAR EN EM. EN OTRO CASO, NO HABRÁ TRANSFERENCIAS Y NO SERA NECESARIO
        if ($rowRecepcion->VIA_RECEPCION == 'PDA'):
            if ($rowMat->GESTION_CALIDAD == 0):
                //COMPRUEBO QUE LA UBICACION ESTE MARCADA COMO ENTRADA DE MATERIAL
                if ($devolver_error == false) :
                    $html->PagErrorCondicionado($rowUbi->TIPO_UBICACION, "!=", 'Entrada', "MaterialNoEnEM");
                else:
                    if ($rowUbi->TIPO_UBICACION != 'Entrada'):
                        $auxiliar->traduce("La ubicacion no es de entrada de material", $administrador->ID_IDIOMA);
                    endif;
                endif;
            elseif ($rowMat->GESTION_CALIDAD == 1):
                //COMPRUEBO QUE LA UBICACION ESTE MARCADA COMO CONTROL CALIDAD
                if ($devolver_error == false) :
                    $html->PagErrorCondicionado($rowUbi->TIPO_UBICACION, "!=", 'Calidad', "MaterialNoEnCC");
                else:
                    if ($rowUbi->TIPO_UBICACION != 'Entrada'):
                        $auxiliar->traduce("La ubicacion no es de calidad", $administrador->ID_IDIOMA);
                    endif;
                endif;
            endif;
        endif;

        //COMPROBAMOS QUE DISPONEMOS DE STOCK A BORRAR
        if ($rowMovLinea->CANTIDAD > $cantidadPosibleBorrar):
            //BUSCO EL MATERIAL FISICO
            if ($rowMovLinea->ID_MATERIAL_FISICO != NULL):
                $rowMatFis = $bd->VerReg("MATERIAL_FISICO", "ID_MATERIAL_FISICO", $rowMovLinea->ID_MATERIAL_FISICO);
            endif;

            //BUSCO LA UNIDAD BASE DEL MATERIAL
            $unidadesBaseyCompra = $mat->unidadBaseyCompra($rowMovLinea->ID_MATERIAL);

            //CONTRUYO EL ERROR A MOSTRAR
            $strError = $strError .
                $auxiliar->traduce("Material", $administrador->ID_IDIOMA) . ": " . $rowMat->REFERENCIA_SGA . " - " . ($administrador->ID_IDIOMA == 'ESP' ? $rowMat->DESCRIPCION : DESCRIPCION_EN) .
                ($rowMovLinea->ID_MATERIAL_FISICO == NULL ? '' : " - " . $auxiliar->traduce("Numero", $administrador->ID_IDIOMA) . " " . $auxiliar->traduce($rowMatFis->TIPO_LOTE, $administrador->ID_IDIOMA) . ": " . $rowMatFis->NUMERO_SERIE_LOTE) .
                " - " . $auxiliar->traduce("Ubicacion", $administrador->ID_IDIOMA) . ": " . $rowUbi->UBICACION . ". " .
                $auxiliar->traduce("Cantidad a anular", $administrador->ID_IDIOMA) . ": " . $auxiliar->formatoNumero($rowMovLinea->CANTIDAD) . " " . $unidadesBaseyCompra['descripcionBase'] . ", " .
                $auxiliar->traduce("Cantidad existente", $administrador->ID_IDIOMA) . ": " . $auxiliar->formatoNumero($cantidadPosibleBorrar) . " " . $unidadesBaseyCompra['descripcionBase'];

            if ($devolver_error == false) :
                $html->PagError("StockInsuficiente");
            else:
                return $auxiliar->traduce("No es posible realizar esta acción debido a que no hay suficiente stock de material en la ubicación correspondiente", $administrador->ID_IDIOMA) . ". " . $auxiliar->traduce("Material", $administrador->ID_IDIOMA) . ": " . $rowMat->REFERENCIA_SGA . " - " . ($administrador->ID_IDIOMA == 'ESP' ? $rowMat->DESCRIPCION : DESCRIPCION_EN) . ($rowLinea->ID_MATERIAL_FISICO == NULL ? '' : " - " . $auxiliar->traduce("Numero", $administrador->ID_IDIOMA) . " " . $auxiliar->traduce($rowMatFis->TIPO_LOTE, $administrador->ID_IDIOMA) . ": " . $rowMatFis->NUMERO_SERIE_LOTE) . " - " . $auxiliar->traduce("Ubicacion", $administrador->ID_IDIOMA) . ": " . $rowUbi->UBICACION . ". " . $auxiliar->traduce("Cantidad a anular", $administrador->ID_IDIOMA) . ": " . $auxiliar->formatoNumero($rowLinea->CANTIDAD) . " " . $unidadesBaseyCompra['descripcionBase'] . ", " . $auxiliar->traduce("Cantidad existente", $administrador->ID_IDIOMA) . ": " . $auxiliar->formatoNumero($rowMatUbi->STOCK_TOTAL) . " " . $unidadesBaseyCompra['descripcionBase'];
            endif;
        endif;

        //VARIABLE PARA CONTROLAR SI SE HA REALIZADO LLAMADA A CAMBIO DE ESTADO PARA PASAR MATERIAL BLOQUEADO POR INCIDENCIA DE CALIDAD A OK
        $llamadaBloqueadoToLibre = false;

        //SI TIENE INCIDENCIA Y STOCK BLOQUEADO HAREMOS:
        // 1º UN CAMBIO DE ESTADO PREVIO
        // 2º DESBLOQUEAR EL STOCK PARA REALIZAR LA ANULACION DE LA LINEA DE MANERA HABITUAL

        if (($idIC != NULL) && ($rowMatUbiIC->STOCK_BLOQUEADO > 0)):

            //INICIO LA TRANSACCION DE CAMBIO DE ESTADO
            $bd->begin_transaction();

            //SI OBTENGO ID DE CAMBIO DE ESTADO Calidad -> Logistica Inversa
            if ($rowCambioEstado->ID_CAMBIO_ESTADO != NULL):
                //REALIZO LA LLAMADA A SAP Y LA MODIFICACION DE DATOS
                $arrDevuelto = $mat->AnulacionPasoCicloCalidadCicloLogisticaInversa($rowCambioEstado->ID_CAMBIO_ESTADO);

                //SI SE HA PRODUCIDO UN ERROR CON LA ANULACION DEL PASO DEL CICLO DE CALIDAD AL CICLO DE LOGISITCA INVERSA MUESTRO LOS ERRORES POR PANTALLA
                if (($arrDevuelto['error_SGA'] == true) || ($arrDevuelto['resultado']['RESULTADO'] != 'OK')):

                    //DESHAGO LA TRANSACCION
                    $bd->rollback_transaction();

                    //DECLARO LA VARIABLE GLOBAL $strError
                    global $strError;

                    //ESTABLEZCO LOS ERRORES
                    $strError = $arrDevuelto['errores'];

                    //SI HAY ERRORES LOS MUESTRO POR PANTALLA
                    if ($devolver_error == false) :
                        $html->PagErrorCondicionado($strError, "!=", "", "ErroresAnulacionPasoCicloCalidadCicloLogisticaInversa");
                    else:
                        if ($strError != ""):
                            return $auxiliar->traduce("Se han producido los siguientes errores al revertir el paso del ciclo de calidad al ciclo de logistica inversa", $administrador->ID_IDIOMA) . ": " . $strError;
                        endif;
                    endif;
                else:
                    $idMatUbiIC  = $arrDevuelto['idMaterialUbicacionDestino'];
                    $rowMatUbiIC = $bd->VerReg("MATERIAL_UBICACION", "ID_MATERIAL_UBICACION", $idMatUbiIC);
                endif;
                //FIN SI SE HA PRODUCIDO UN ERROR CON LA ANULACION DEL PASO DEL CICLO DE CALIDAD AL CICLO DE LOGISITCA INVERSA MUESTRO LOS ERRORES POR PANTALLA
            endif;
            //FIN SI OBTENGO ID DE CAMBIO DE ESTADO Calidad -> Logistica Inversa

            //GENERO EL CAMBIO DE ESTADO
            $sqlInsert = "INSERT INTO CAMBIO_ESTADO SET
                            FECHA = '" . date("Y-m-d H:i:s") . "'
                            , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                            , ID_MATERIAL = $rowMovLinea->ID_MATERIAL
                            , ID_MATERIAL_FISICO = " . ($rowMovLinea->ID_MATERIAL_FISICO == NULL ? 'NULL' : $rowMovLinea->ID_MATERIAL_FISICO) . "
                            , ID_UBICACION = $rowMovLinea->ID_UBICACION
                            , CANTIDAD = $rowMatUbiIC->STOCK_TOTAL
                            , ID_TIPO_BLOQUEO_INICIAL = $rowMatUbiIC->ID_TIPO_BLOQUEO
                            , ID_TIPO_BLOQUEO_FINAL = " . ($rowMovLinea->ID_TIPO_BLOQUEO == NULL ? 'NULL' : $rowMovLinea->ID_TIPO_BLOQUEO) . "
                            , OBSERVACIONES = '" . $auxiliar->traduce("Reversión de una entrada con incidencia", $administrador->ID_IDIOMA) . "'";
            $bd->ExecSQL($sqlInsert);//exit($sqlInsert);
            $idCambioEstado = $bd->IdAsignado();

            //DECREMENTO MATERIAL_UBICACION ORIGEN
            $sqlUpdate = "UPDATE MATERIAL_UBICACION SET
                            STOCK_TOTAL = STOCK_TOTAL - $rowMatUbiIC->STOCK_TOTAL
                            , STOCK_BLOQUEADO = STOCK_BLOQUEADO - $rowMatUbiIC->STOCK_BLOQUEADO
                            WHERE ID_MATERIAL_UBICACION = $rowMatUbiIC->ID_MATERIAL_UBICACION";
            $bd->ExecSQL($sqlUpdate);

            //BUSCO MATERIAL_UBICACION DESTINO
            $NotificaErrorPorEmail = "No";
            $clausulaWhere         = "ID_MATERIAL = $rowMovLinea->ID_MATERIAL AND ID_UBICACION = $rowMovLinea->ID_UBICACION AND ID_MATERIAL_FISICO " . ($rowMovLinea->ID_MATERIAL_FISICO == "" ? "IS NULL" : "= $rowMovLinea->ID_MATERIAL_FISICO") . " AND ID_TIPO_BLOQUEO " . ($rowMovLinea->ID_TIPO_BLOQUEO == NULL ? "IS NULL" : "= $rowMovLinea->ID_TIPO_BLOQUEO") . " AND ID_ORDEN_TRABAJO_MOVIMIENTO IS NULL AND ID_INCIDENCIA_CALIDAD IS NULL";
            $rowMatUbiDestino      = $bd->VerRegRest("MATERIAL_UBICACION", $clausulaWhere, "No");
            if ($rowMatUbiDestino == false):
                //CREO MATERIAL UBICACION DESTINO
                $sqlInsert = "INSERT INTO MATERIAL_UBICACION SET
                                ID_MATERIAL = $rowMovLinea->ID_MATERIAL
                                , ID_MATERIAL_FISICO = " . ($rowMovLinea->ID_MATERIAL_FISICO == "" ? 'NULL' : "$rowMovLinea->ID_MATERIAL_FISICO") . "
                                , ID_UBICACION = $rowMovLinea->ID_UBICACION
                                , ID_TIPO_BLOQUEO = " . ($rowMovLinea->ID_TIPO_BLOQUEO == "" ? 'NULL' : "$rowMovLinea->ID_TIPO_BLOQUEO") . "
                                , ID_ORDEN_TRABAJO_MOVIMIENTO = NULL
                                , ID_INCIDENCIA_CALIDAD = NULL";
                $bd->ExecSQL($sqlInsert);
                $idMatUbiDestino = $bd->IdAsignado();
            else:
                $idMatUbiDestino = $rowMatUbiDestino->ID_MATERIAL_UBICACION;
            endif;

            //INCREMENTO MATERIAL_UBICACION DESTINO
            $sqlUpdate = "UPDATE MATERIAL_UBICACION SET
                            STOCK_TOTAL = STOCK_TOTAL + $rowMatUbiIC->STOCK_TOTAL
                            , STOCK_OK = STOCK_OK + " . ($rowMovLinea->ID_TIPO_BLOQUEO == NULL ? $rowMatUbiIC->STOCK_TOTAL : 0) . "
                            , STOCK_BLOQUEADO = STOCK_BLOQUEADO + " . ($rowMovLinea->ID_TIPO_BLOQUEO == NULL ? 0 : $rowMatUbiIC->STOCK_TOTAL) . "
                            WHERE ID_MATERIAL_UBICACION = $idMatUbiDestino";
            $bd->ExecSQL($sqlUpdate);

            //VARIABLE PARA SABER SI TENGO QUE ENVIAR EL CAMBIO DE ESTADO A SAP
            $bloqueosIgualesParaSAP = false;

            //BUSCO EL TIPO DE BLOQUEO INICIAL
            $rowTipoBloqueoInicial = $bd->VerReg("TIPO_BLOQUEO", "ID_TIPO_BLOQUEO", $rowMatUbiIC->ID_TIPO_BLOQUEO);

            //BUSCO EL TIPO DE BLOQUEO FINAL
            if ($rowMovLinea->ID_TIPO_BLOQUEO != NULL):
                $rowTipoBloqueoFinal = $bd->VerReg("TIPO_BLOQUEO", "ID_TIPO_BLOQUEO", $rowMovLinea->ID_TIPO_BLOQUEO);
            endif;

            //COMPRUEBO SI EL TIPO DE BLOQUEO INICIAL Y FINAL SON IGUALES PARA SAP
            if (($rowMovLinea->ID_TIPO_BLOQUEO != NULL) && ($rowTipoBloqueoInicial->TIPO_BLOQUEO_SAP == $rowTipoBloqueoFinal->TIPO_BLOQUEO_SAP)):
                $bloqueosIgualesParaSAP = true;
            endif;

            //SI LOS TIPOS DE BLOQUEO INICIAL Y FINAL SON DIFERENTES PARA SAP, HAGO LA LLAMADA AL WEB SERVICE
            if ($bloqueosIgualesParaSAP == false):

                //ACTUALIZO LA VARIABLE QUE ME INDICA SI SE HA REALIZADO LLAMADA A CAMBIO DE ESTADO
                $llamadaBloqueadoToLibre = true;

                //ENVIO A SAP EL CAMBIO DE ESTADO
                $resultado = $sap->AjusteCambioEstado($idCambioEstado);
                if ($resultado['RESULTADO'] != 'OK'): //No se ha podido grabar la información en SAP
                    foreach ($resultado['ERRORES'] as $arr):
                        foreach ($arr as $mensaje_error):
                            if ($devolver_error == false) :
                                $strError = $strError . $mensaje_error . "<br>";
                            else:
                                $strError = $strError . $mensaje_error . ". ";
                            endif;
                        endforeach;
                    endforeach;

                    //DESHAGO LA TRANSACCION
                    $bd->rollback_transaction();

                    //GRABO EL ERROR PRODUCIDO EN BASE DE DATOS
                    $sap->InsertarErrores($resultado);

                    if ($devolver_error == false) :
                        $html->PagError("ErrorSAP");
                    else:
                        return "We have received the following errors from SAP/Se han producido los siguientes errores en el intercambio de información con SAP" . ": " . $strError;
                    endif;
                endif;
            endif;
            //FIN SI LOS TIPOS DE BLOQUEO INICIAL Y FINAL SON DIFERENTES PARA SAP, HAGO LA LLAMADA AL WEB SERVICE

            //FINALIZO LA TRANSACCION DE CAMBIO DE ESTADO
            $bd->commit_transaction();

        endif;
        //FIN SI TIENE INCIDENCIA Y STOCK HAREMOS UN CAMBIO DE ESTADO PREVIO

        //INICIO LA TRANSACCION DE ACTUALIZACION DE STOCK
        $bd->begin_transaction();

        //ACTUALIZO MATERIAL UBICACION, DESCONTANTO EL MATERIAL CORRESPONDIENTE
        $sqlUpdate = "UPDATE MATERIAL_UBICACION SET
                        STOCK_TOTAL = STOCK_TOTAL - $rowMovLinea->CANTIDAD
                        , STOCK_OK = STOCK_OK - " . ($rowMovLinea->ID_TIPO_BLOQUEO == NULL ? $rowMovLinea->CANTIDAD : 0) . "
                        , STOCK_BLOQUEADO = STOCK_BLOQUEADO - " . ($rowMovLinea->ID_TIPO_BLOQUEO == NULL ? 0 : $rowMovLinea->CANTIDAD) . "
                        WHERE ID_MATERIAL = $rowMovLinea->ID_MATERIAL AND ID_UBICACION = $rowMovLinea->ID_UBICACION AND ID_MATERIAL_FISICO " . ($rowMovLinea->ID_MATERIAL_FISICO == NULL ? "IS NULL" : "= $rowMovLinea->ID_MATERIAL_FISICO") . " AND ID_TIPO_BLOQUEO " . ($rowMovLinea->ID_TIPO_BLOQUEO == NULL ? "IS NULL" : "= $rowMovLinea->ID_TIPO_BLOQUEO") . " AND ID_ORDEN_TRABAJO_MOVIMIENTO IS NULL AND ID_INCIDENCIA_CALIDAD IS NULL";
        $bd->ExecSQL($sqlUpdate);

        //ARRAY CON LOS PEDIDOS IMPLICADOS
        $arrayLineasPedidosInvolucradas = array();

        if (($rowMov->TIPO_MOVIMIENTO == 'PedidoProveedor') || ($rowMov->TIPO_MOVIMIENTO == 'PedidoCompraSGAManual') || ($rowMov->TIPO_MOVIMIENTO == 'Construccion')): //TIPO DE PEDIDO PROVENIENTE DEL PROVEEDOR
            //BUSCO EL PEDIDO
            $rowPed = $bd->VerReg("PEDIDO_ENTRADA", "ID_PEDIDO_ENTRADA", $rowMovLinea->ID_PEDIDO);

            //GUARDO LOS PEDIDOS DE ENTRADA INVOLUCRADOS EN ESTA LINEA DE MOVIMIENTO
            if (!in_array($rowMovLinea->ID_PEDIDO_LINEA, (array) $arrayLineasPedidosInvolucradas)):
                $arrayLineasPedidosInvolucradas[] = $rowMovLinea->ID_PEDIDO_LINEA;
            endif;

            //SI ES UN MATERIAL DE UNA CAJA, LE DESMARCO CALIDAD
            if ($rowMovLinea->ID_MATERIAL_FISICO != NULL):
                $rowMaterialFisico = $bd->VerReg("MATERIAL_FISICO", "ID_MATERIAL_FISICO", $rowMovLinea->ID_MATERIAL_FISICO);
                //SI ESTA MARCADA PARA CALIDAD
                if ($rowMaterialFisico->MARCADA_PARA_CONTROL_CALIDAD == 1):
                    $sqlUpdate = "UPDATE MATERIAL_FISICO SET MARCADA_PARA_CONTROL_CALIDAD = 0 WHERE ID_MATERIAL_FISICO = $rowMaterialFisico->ID_MATERIAL_FISICO";
                    $bd->ExecSQL($sqlUpdate);
                endif;
            endif;
            //FIN SI ES UN MATERIAL DE UNA CAJA, LE DESMARCO CALIDAD

            //BUSCO LA LINEA DEL PEDIDO DE ENTRADA CORRESPONDIENTE
            $rowPedLin = $bd->VerReg("PEDIDO_ENTRADA_LINEA", "ID_PEDIDO_ENTRADA_LINEA", $rowMovLinea->ID_PEDIDO_LINEA);

            //COMPRUEBO SI LA LINEA DE PEDIDO ESTA PENDIENTE DE RECIBIR EL PEDIDO ACTUALIZADO
            if ($devolver_error == false):
                $html->PagErrorCondicionado($rowPedLin->REINTENTAR_SPLIT, "!=", 0, "LineaPendienteActualizar");
            else:
                if ($rowPedLin->REINTENTAR_SPLIT != 0):
                    //DESHAGO LA TRANSACCION
                    $bd->rollback_transaction();

                    return $auxiliar->traduce("No se puede realizar la operacion debido a que el registro esta pendiente de actualizacion", $administrador->ID_IDIOMA);
                endif;
            endif;

            //BUSCO EL TIPO DE BLOQUEO PREVENTIVO
            $rowTipoBloqueoPreventivo = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "SP");

            //INCREMENTAMOS STOCK EN EL ALMACÉN DE STOCK EXTERNALIZADO SI ES UN PEDIDO DE COMPRA
            if (
                ($rowPed->TIPO_PEDIDO == 'Compra') &&
                (($rowMovLinea->ID_TIPO_BLOQUEO == NULL) || ($rowMovLinea->ID_TIPO_BLOQUEO == $rowTipoBloqueoPreventivo->ID_TIPO_BLOQUEO))
               ):
                $arrErrores = $this->incrementarStockExternalizado($rowMov->ID_PROVEEDOR, $rowAlm->ID_CENTRO, $rowMovLinea->ID_MATERIAL, $rowMovLinea->CANTIDAD);

                if (count( (array)$arrErrores) > 0):

                    //SI HA HABIDO ERRORES, LOS MUESTRO
                    $errores    = "";
                    $saltoLinea = "";
                    foreach ($arrErrores as $error):

                        $errores    .= $saltoLinea . $error;
                        $saltoLinea = "<br>";

                    endforeach;

                    $html->PagError($errores);

                endif;
            endif;

            //SI ES UNA RECEPCION EN BASE A CONTENEDOR ENTRANTE, DECREMENTAMOS LA CANTIDAD DE LA LINEA DEL PEDIDO DE ENTRADA
            if ($rowRecepcion->ID_CONTENEDOR_ENTRANTE != NULL):
                $sqlUpdate = "UPDATE PEDIDO_ENTRADA_LINEA SET
                              CANTIDAD = CANTIDAD - $rowMovLinea->CANTIDAD
                              , CANTIDAD_SAP = CANTIDAD_SAP - " . $mat->cantUnidadCompra($rowMovLinea->ID_MATERIAL, $rowMovLinea->CANTIDAD) . "
                              WHERE ID_PEDIDO_ENTRADA = $rowMovLinea->ID_PEDIDO AND ID_PEDIDO_ENTRADA_LINEA = $rowMovLinea->ID_PEDIDO_LINEA";
                $bd->ExecSQL($sqlUpdate);
            endif;
            //FIN SI ES UNA RECEPCION EN BASE A CONTENEDOR ENTRANTE, DECREMENTAMOS LA CANTIDAD DE LA LINEA DEL PEDIDO DE ENTRADA

            //BUSCO LA CANTIDAD ASIGNADA A OTROS MOVIMIENTOS DE ENTRADA LINEA, QUE NO SEAN DC
            $sqlSuma    = "SELECT IF(SUM(CANTIDAD) IS NULL,0,SUM(CANTIDAD)) AS SUMA_CANTIDAD
                            FROM MOVIMIENTO_ENTRADA_LINEA 
                            WHERE ID_PEDIDO_LINEA = $rowMovLinea->ID_PEDIDO_LINEA AND ID_MOVIMIENTO_ENTRADA_LINEA <> $idLineaMov AND LINEA_ANULADA = 0 AND BAJA = 0 AND (ID_TIPO_BLOQUEO <> $rowTipoBloqueoLineaDevolverProveedorCalidad->ID_TIPO_BLOQUEO OR ID_TIPO_BLOQUEO IS NULL)";
            $resultSuma = $bd->ExecSQL($sqlSuma);
            if (($resultSuma == false) || ($bd->NumRegs($resultSuma) == 0)):
                $cantidadEnMovimientos = 0;
            else:
                $rowSuma               = $bd->SigReg($resultSuma);
                $cantidadEnMovimientos = $rowSuma->SUMA_CANTIDAD;
            endif;
            //FIN BUSCO LA CANTIDAD ASIGNADA A OTROS MOVIMIENTOS DE ENTRADA LINEA

            //ACTUALIZO LA CANTIDAD PENDIENTE DE SERVIR EN EL PEDIDO
            $sqlUpdate = "UPDATE PEDIDO_ENTRADA_LINEA SET
                            CANTIDAD_PDTE = CANTIDAD - $cantidadEnMovimientos 
                            , CANTIDAD_NO_SUMINISTRADA = 0 
                            WHERE ID_PEDIDO_ENTRADA_LINEA = $rowMovLinea->ID_PEDIDO_LINEA";
            $bd->ExecSQL($sqlUpdate);
            //FIN ACTUALIZO LA CANTIDAD PENDIENTE DE SERVIR EN EL PEDIDO

            $pedido->InicializaCantidadRecepcionadaEntregasLineaPedido($rowMovLinea->ID_PEDIDO_LINEA);

            //BUSCO EL TIPO DE BLOQUEO PREVENTIVO
            $rowTipoBloqueoPreventivo = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "SP");
            //BUSCO EL TIPO DE BLOQUEO RETENIDO POR CALIDAD NO PREVENTIVO REPARABLE EN GARANTIA
            $rowTipoBloqueoRetenidoCalidadNoPreventivoReparableEnGarantia = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "XRCRG");
            //BUSCO EL TIPO DE BLOQUEO RETENIDO POR CALIDAD NO PREVENTIVO REPARABLE NO EN GARANTIA
            $rowTipoBloqueoRetenidoCalidadNoPreventivoReparableNoEnGarantia = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "XRCRNG");
            //BUSCO EL TIPO DE BLOQUEO RETENIDO POR CALIDAD NO PREVENTIVO NO REPARABLE NO EN GARANTIA
            $rowTipoBloqueoRetenidoCalidadNoPreventivoNoReparableNoEnGarantia = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "XRCNRNG");

            //CALCULO LA CANTIDAD RECEPCIONADA PARA ASIGNARLA A LAS ENTREGAS DE LA LINEA DE PEDIDO
            $sqlCantidadRecepcionada = "SELECT IF(SUM(MEL.CANTIDAD) IS NULL, 0, SUM(MEL.CANTIDAD)) AS CANTIDAD_RECEPCIONADA 
                                FROM MOVIMIENTO_ENTRADA_LINEA MEL
                                INNER JOIN PEDIDO_ENTRADA_LINEA PEL ON PEL.ID_PEDIDO_ENTRADA_LINEA = MEL.ID_PEDIDO_LINEA
                                INNER JOIN PEDIDO_ENTRADA PE ON PE.ID_PEDIDO_ENTRADA = PEL.ID_PEDIDO_ENTRADA
                                WHERE PE.TIPO_PEDIDO = 'Compra' AND PEL.INDICADOR_BORRADO IS NULL AND PEL.BAJA = 0 AND MEL.ID_PEDIDO_LINEA = $rowMovLinea->ID_PEDIDO_LINEA AND MEL.LINEA_ANULADA = 0 AND MEL.BAJA = 0 AND (MEL.ID_TIPO_BLOQUEO IS NULL OR MEL.ID_TIPO_BLOQUEO IN ($rowTipoBloqueoPreventivo->ID_TIPO_BLOQUEO, $rowTipoBloqueoRetenidoCalidadNoPreventivoReparableEnGarantia->ID_TIPO_BLOQUEO, $rowTipoBloqueoRetenidoCalidadNoPreventivoReparableNoEnGarantia->ID_TIPO_BLOQUEO, $rowTipoBloqueoRetenidoCalidadNoPreventivoNoReparableNoEnGarantia->ID_TIPO_BLOQUEO))";

            $resultCantidadRecepcionada = $bd->ExecSQL($sqlCantidadRecepcionada);
            $rowCantidadRecepcionada = $bd->SigReg($resultCantidadRecepcionada);
            $cantidadRecepcionada = $rowCantidadRecepcionada->CANTIDAD_RECEPCIONADA;

            //ASIGNAMOS LAS ENTREGAS DE LA LINEA DEL PEDIDO DE ENTRADA
            $resultadoAsignacionEntregas = $pedido->AsignarEntregasLineaPedido($rowMovLinea->ID_PEDIDO_LINEA, $cantidadRecepcionada - $rowMovLinea->CANTIDAD);
            if ($resultadoAsignacionEntregas["Resultado"] != "OK"):
                $html->PagError($resultadoAsignacionEntregas["Errores"]);
            endif;

            if (($rowPed->TIPO_PEDIDO == 'Compra') || ($rowPed->TIPO_PEDIDO == 'Compra SGA Manual') || ($rowPed->TIPO_PEDIDO == 'Construccion')): //PEDIDO DE COMPRA A PROVEEDOR NORMAL

                //SI NO HAY ANULACION DE STOCK, SE CREARÁ STOCK PDPA
                if ($anulacion_stock == 'No'):

                    //BUSCO EL TIPO DE BLOQUEO ANULACION
                    $rowBloqueoAnulacion = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "PDPA");

                    //COMPRUEBO SI LA UBICACION DEL MATERIAL CORRESPONDIENTE EXITE, SINO LA CREO
                    $NotificaErrorPorEmail = "No";
                    $sqlRest               = "ID_MATERIAL = $rowMovLinea->ID_MATERIAL AND ID_UBICACION = $rowMovLinea->ID_UBICACION AND ID_MATERIAL_FISICO " . ($rowMovLinea->ID_MATERIAL_FISICO == NULL ? "IS NULL" : "= $rowMovLinea->ID_MATERIAL_FISICO") . " AND ID_TIPO_BLOQUEO = $rowBloqueoAnulacion->ID_TIPO_BLOQUEO AND ID_ORDEN_TRABAJO_MOVIMIENTO IS NULL AND ID_INCIDENCIA_CALIDAD IS NULL";
                    $num                   = $bd->NumRegsTabla("MATERIAL_UBICACION", $sqlRest, "No");
                    if ($num == 0):
                        $sqlInsert = "INSERT INTO MATERIAL_UBICACION SET
                                        ID_MATERIAL = $rowMovLinea->ID_MATERIAL
                                        , ID_MATERIAL_FISICO = " . ($rowMovLinea->ID_MATERIAL_FISICO == NULL ? "NULL" : "$rowMovLinea->ID_MATERIAL_FISICO") . "
                                        , ID_UBICACION = $rowMovLinea->ID_UBICACION 
                                        , ID_TIPO_BLOQUEO = $rowBloqueoAnulacion->ID_TIPO_BLOQUEO 
                                        , ID_ORDEN_TRABAJO_MOVIMIENTO = NULL 
                                        , ID_INCIDENCIA_CALIDAD = NULL";
                        $bd->ExecSQL($sqlInsert);
                    endif;

                    //ACTUALIZO MATERIAL UBICACION, INCREMENTANDO EL MATERIAL BLOQUEADO
                    $sqlUpdate = "UPDATE MATERIAL_UBICACION SET
                                    STOCK_TOTAL = STOCK_TOTAL + " . $rowMovLinea->CANTIDAD . " 
                                    , STOCK_BLOQUEADO = STOCK_BLOQUEADO + " . $rowMovLinea->CANTIDAD . " 
                                    WHERE $sqlRest";
                    $bd->ExecSQL($sqlUpdate);
                endif;
                //FIN SI NO HAY ANULACION DE STOCK, SE CREARÁ STOCK PDPA

                //MODIFICO ANULACION STOCK A CERO
                if ($anulacion_stock == 'No'):
                    $anulacion_stock = 0;
                else:
                    $anulacion_stock = 1;
                endif;

            else: //PEDIDO QUE NO ES DE COMPRA (REPARACION O GARANTIA)
                //MODIFICO ANULACION STOCK A CERO
                $anulacion_stock = 0;

                //SI ES SERIABLE, ACTUALIZO EL NUMERO DE VECES REPARADO
                //if ($rowMovLinea->TIPO_LOTE == 'serie'): //MATERIAL SERIABLE REPARACION/GARANTIA
                if (($rowMovLinea->TIPO_LOTE == 'serie') && ($rowPed->TIPO_PEDIDO == 'Reparación')): //MATERIAL SERIABLE REPARACION
                    $sqlUpdate = "UPDATE MATERIAL_FISICO SET
												NUMERO_REPARACIONES = NUMERO_REPARACIONES - 1 
												WHERE ID_MATERIAL_FISICO = $rowMovLinea->ID_MATERIAL_FISICO";
                    $bd->ExecSQL($sqlUpdate);
                endif;

                //BUSCO EL MOVIMIENTO SALIDA DIRECTO
                $sqlMovSalDirecto    = "SELECT MSL.*
														 FROM MOVIMIENTO_SALIDA_LINEA MSL 
														 INNER JOIN PEDIDO_SALIDA_LINEA PSL ON PSL.ID_MOVIMIENTO_SALIDA_LINEA = MSL.ID_MOVIMIENTO_SALIDA_LINEA 
														 INNER JOIN PEDIDO_SALIDA PS ON PS.ID_PEDIDO_SALIDA = PSL.ID_PEDIDO_SALIDA 
														 WHERE PSL.LINEA_PEDIDO_SAP = '" . $rowPedLin->LINEA_PEDIDO_SAP . "' AND PS.PEDIDO_SAP = '" . $rowPed->PEDIDO_SAP . "' AND ID_LINEA_ZREP_ZGAR IS NULL";
                $resultMovSaldirecto = $bd->ExecSQL($sqlMovSalDirecto);
                $rowMovSalDirecto    = $bd->SigReg($resultMovSaldirecto);

                if ($devolver_error == false):
                    $html->PagErrorCondicionado($rowMovSalDirecto, "==", false, "MovimientoSalidaDirectoNoExiste");
                else:
                    if (!$rowMovSalDirecto):
                        //DESHAGO LA TRANSACCION
                        $bd->rollback_transaction();

                        return $auxiliar->traduce("El movimiento de salida directo no existe", $administrador->ID_IDIOMA);
                    endif;
                endif;

                //BUSCO LA UBICACION DEL PROVEEDOR
                $rowMatUbiProveedor = $bd->VerRegRest("MATERIAL_UBICACION", "ID_MATERIAL = $rowMovSalDirecto->ID_MATERIAL AND ID_UBICACION = $rowMovSalDirecto->ID_UBICACION_DESTINO AND ID_MATERIAL_FISICO " . ($rowMovSalDirecto->ID_MATERIAL_FISICO == NULL ? "IS NULL" : "= $rowMovSalDirecto->ID_MATERIAL_FISICO") . " AND ID_TIPO_BLOQUEO = $rowMovSalDirecto->ID_TIPO_BLOQUEO AND ID_ORDEN_TRABAJO_MOVIMIENTO = $rowMovSalDirecto->ID_ORDEN_TRABAJO_MOVIMIENTO AND ID_INCIDENCIA_CALIDAD " . ($rowMovSalDirecto->ID_INCIDENCIA_CALIDAD == NULL ? "IS NULL" : "= $rowMovSalDirecto->ID_INCIDENCIA_CALIDAD"), "No");

                if ($rowMatUbiProveedor == false):
                    $sqlInsert = "INSERT INTO MATERIAL_UBICACION SET
												ID_MATERIAL = $rowMovSalDirecto->ID_MATERIAL
												, ID_MATERIAL_FISICO = " . ($rowMovSalDirecto->ID_MATERIAL_FISICO == NULL ? "NULL" : "$rowMovSalDirecto->ID_MATERIAL_FISICO") . "
												, ID_UBICACION = $rowMovSalDirecto->ID_UBICACION_DESTINO 
												, ID_TIPO_BLOQUEO = $rowMovSalDirecto->ID_TIPO_BLOQUEO 
												, ID_ORDEN_TRABAJO_MOVIMIENTO = $rowMovSalDirecto->ID_ORDEN_TRABAJO_MOVIMIENTO  
												, ID_INCIDENCIA_CALIDAD = " . ($rowMovSalDirecto->ID_INCIDENCIA_CALIDAD == NULL ? "NULL" : "$rowMovSalDirecto->ID_INCIDENCIA_CALIDAD");/* . "
												, ID_PROVEEDOR_GARANTIA = $rowMovSalDirecto->ID_PROVEEDOR_GARANTIA";*/
                    $bd->execSQL($sqlInsert);
                    $idMatUbiProveedor = $bd->IdAsignado();
                else:
                    $idMatUbiProveedor = $rowMatUbiProveedor->ID_MATERIAL_UBICACION;
                endif;

                //ACTUALIZO MATERIAL UBICACION, INCREMENTANDO EL MATERIAL BLOQUEADO EN PROVEEDOR
                $sqlUpdate = "UPDATE MATERIAL_UBICACION SET
											STOCK_TOTAL = STOCK_TOTAL + " . $rowMovLinea->CANTIDAD . " 
											, STOCK_BLOQUEADO = STOCK_BLOQUEADO + " . $rowMovLinea->CANTIDAD . " 
											WHERE ID_MATERIAL_UBICACION = $idMatUbiProveedor";
                $bd->ExecSQL($sqlUpdate);

                //BUSCO EL ASIENTO QUE GENERO LA SALIDA PARA DARLO DE BAJA
                $rowAsientoProveedor = $bd->VerRegRest("ASIENTO", "TIPO_ASIENTO = 'Baja Material Estropeado en Proveedor' AND ID_MOVIMIENTO_ENTRADA_LINEA = $rowMovLinea->ID_MOVIMIENTO_ENTRADA_LINEA AND BAJA = 0", "No");

                if ($devolver_error == false):
                    $html->PagErrorCondicionado($rowAsientoProveedor, "==", false, "AsientoBajaProveedorNoEncontrado");
                else:
                    if (!$rowAsientoProveedor):
                        //DESHAGO LA TRANSACCION
                        $bd->rollback_transaction();

                        return $auxiliar->traduce("El asiento de baja de material estropeado en proveedor no se encuentra.", $administrador->ID_IDIOMA);
                    endif;
                endif;

                //DOY DE BAJA EL ASIENTO
                $sqlUpdate = "UPDATE ASIENTO SET BAJA = 1 WHERE ID_ASIENTO = $rowAsientoProveedor->ID_ASIENTO";
                $bd->ExecSQL($sqlUpdate);

                //BUSCO EL NUMERO DE LINEAS ENTRADAS CON EL MISMO NUMERO DE LINEA DE PEDIDO
                $num = $bd->NumRegsTabla("MOVIMIENTO_ENTRADA_LINEA", "ESTADO <> 'Creada' AND ID_PEDIDO_LINEA = $rowPedLin->ID_PEDIDO_ENTRADA_LINEA AND BAJA = 0 AND LINEA_ANULADA = 0 AND ID_MOVIMIENTO_ENTRADA_LINEA <> $rowMovLinea->ID_MOVIMIENTO_ENTRADA_LINEA");
                if ($num == 0):
                    //ES LA PRIMERA VEZ QUE RECEPCIONE MATERIAL DE ESTA LINEA DE PEDIDO, DOY DE ALTA EN PROVEEDOR SUS COMPONENTES

                    //BUSCO LOS MOVIMIENTOS DE SALIDA DE LA LINEA DEL PEDIDO
                    $sqlMovSalLineas    = "SELECT SUM(MSL.CANTIDAD) AS CANTIDAD, MSL.ID_MATERIAL, MSL.ID_MATERIAL_FISICO, MSL.ID_UBICACION_DESTINO
															FROM MOVIMIENTO_SALIDA_LINEA MSL
															INNER JOIN MOVIMIENTO_SALIDA MS ON MS.ID_MOVIMIENTO_SALIDA = MSL.ID_MOVIMIENTO_SALIDA 
															INNER JOIN PEDIDO_SALIDA_LINEA PSL ON PSL.ID_PEDIDO_SALIDA_LINEA = MSL.ID_PEDIDO_SALIDA_LINEA 
															WHERE PSL.ID_LINEA_ZREP_ZGAR = $rowPedLin->ID_PEDIDO_ENTRADA_LINEA AND MSL.ESTADO = 'Expedido' 
															GROUP BY MSL.ID_MATERIAL, MSL.ID_MATERIAL_FISICO, PSL.ID_LINEA_ZREP_ZGAR";
                    $resultMovSalLineas = $bd->ExecSQL($sqlMovSalLineas);
                    while ($rowMovSalLinea = $bd->SigReg($resultMovSalLineas)):
                        $rowMatUbiComponenetesProveedor = $bd->VerRegRest("MATERIAL_UBICACION", "ID_MATERIAL = $rowMovSalLinea->ID_MATERIAL AND ID_UBICACION = $rowMovSalLinea->ID_UBICACION_DESTINO AND ID_MATERIAL_FISICO " . ($rowMovSalLinea->ID_MATERIAL_FISICO == NULL ? "IS NULL" : "= $rowMovSalLinea->ID_MATERIAL_FISICO") . " AND ID_TIPO_BLOQUEO IS NULL AND ID_ORDEN_TRABAJO_MOVIMIENTO IS NULL AND ID_INCIDENCIA_CALIDAD IS NULL", "No");

                        if ($devolver_error == false):
                            $html->PagErrorCondicionado($rowMatUbiComponenetesProveedor, "==", false, "MaterialUbicacionComponentesProveedorNoExiste");
                        else:
                            if (!$rowMatUbiComponenetesProveedor):
                                //DESHAGO LA TRANSACCION
                                $bd->rollback_transaction();

                                return $auxiliar->traduce("El registro de material ubicacion no existe", $administrador->ID_IDIOMA);
                            endif;
                        endif;

                        //ACTUALIZO MATERIAL UBICACION, INCREMENTANDO EL MATERIAL LIBRE (COMPONENTES)
                        $sqlUpdate = "UPDATE MATERIAL_UBICACION SET
													STOCK_TOTAL = STOCK_TOTAL + " . $rowMovSalLinea->CANTIDAD . " 
													, STOCK_OK = STOCK_OK + " . $rowMovSalLinea->CANTIDAD . " 
													WHERE ID_MATERIAL_UBICACION = $rowMatUbiComponenetesProveedor->ID_MATERIAL_UBICACION";
                        $bd->ExecSQL($sqlUpdate);

                        //BUSCO EL ASIENTO QUE GENERO LA SALIDA PARA DARLO DE BAJA
                        $rowAsientoComponentesProveedor = $bd->VerRegRest("ASIENTO", "TIPO_ASIENTO = 'Baja Componentes en Proveedor' AND ID_MOVIMIENTO_ENTRADA_LINEA = $rowMovLinea->ID_MOVIMIENTO_ENTRADA_LINEA AND BAJA = 0", "No");
                        if ($devolver_error == false):
                            $html->PagErrorCondicionado($rowAsientoComponentesProveedor, "==", false, "AsientoBajaComponentesProveedorNoEncontrado");
                        else:
                            if (!$rowAsientoComponentesProveedor):
                                //DESHAGO LA TRANSACCION
                                $bd->rollback_transaction();

                                return $auxiliar->traduce("El asiento de 'Baja Componentes en Proveedor no existe'", $administrador->ID_IDIOMA);
                            endif;
                        endif;

                        //DOY DE BAJA EL ASIENTO
                        $sqlUpdate = "UPDATE ASIENTO SET BAJA = 1 WHERE ID_ASIENTO = $rowAsientoComponentesProveedor->ID_ASIENTO";
                        $bd->ExecSQL($sqlUpdate);
                    endwhile; //BUCLE COMPONENTES

                    //ACTUALIZO LAS LINEAS DEL PEDIDO DE COMPONENTES PARA QUE SE PUEDAN PREPARAR Y EXPEDIR DE NUEVO
                    $sqlComponentesSinPreparar    = "SELECT PSL.ID_PEDIDO_SALIDA_LINEA, PSL.ID_PEDIDO_SALIDA
																				FROM PEDIDO_SALIDA_LINEA PSL 
																				INNER JOIN PEDIDO_SALIDA PS ON PS.ID_PEDIDO_SALIDA = PSL.ID_PEDIDO_SALIDA 
																				WHERE PS.PEDIDO_SAP = '" . $rowPed->PEDIDO_SAP . "' AND PSL.ID_LINEA_ZREP_ZGAR = $rowPedLin->ID_PEDIDO_ENTRADA_LINEA AND PSL.CANTIDAD_CANCELADA_POR_ENTREGA_FINAL > 0 AND ENTREGA_FINAL = 1 AND PSL.INDICADOR_BORRADO IS NULL AND PSL.BAJA = 0";
                    $resultComponentesSinPreparar = $bd->ExecSQL($sqlComponentesSinPreparar);
                    while ($rowComponentesSinPreparar = $bd->SigReg($resultComponentesSinPreparar)):
                        $sqlUpdate = "UPDATE PEDIDO_SALIDA_LINEA SET
													CANTIDAD_PENDIENTE_SERVIR = CANTIDAD_CANCELADA_POR_ENTREGA_FINAL 
													, CANTIDAD_CANCELADA_POR_ENTREGA_FINAL = 0 
													, ENTREGA_FINAL = 0 
													WHERE ID_PEDIDO_SALIDA_LINEA = $rowComponentesSinPreparar->ID_PEDIDO_SALIDA_LINEA";
                        $sqlUpdate = $bd->ExecSQL($sqlUpdate);

                        //SI EL PEDIDO DE SALIDA TIENE TODA LA CANTIDAD POR PREPARAR Y EXPEDIR LO PONGO A ESTADO GRABADO, SINO EN ENTREGA
                        $num = $bd->NumRegsTabla("PEDIDO_SALIDA_LINEA", "CANTIDAD_PENDIENTE_SERVIR <> CANTIDAD AND ID_PEDIDO_SALIDA = $rowComponentesSinPreparar->ID_PEDIDO_SALIDA AND INDICADOR_BORRADO IS NULL AND BAJA = 0");
                        if ($num > 0):
                            $actualizacion = "ESTADO = 'En Entrega'";
                        else:
                            $actualizacion = "ESTADO = 'Grabado'";
                        endif;

                        $sqlUpdate = "UPDATE PEDIDO_SALIDA SET
													$actualizacion 
													WHERE ID_PEDIDO_SALIDA = $rowComponentesSinPreparar->ID_PEDIDO_SALIDA";
                        $bd->ExecSQL($sqlUpdate);
                    endwhile;

                endif;
                //FIN BUSCO EL NUMERO DE MOVIMIENTOS PROCESADOS CON EL MISMO NUMERO DE LINEA DE PEDIDO

            endif; //FIN TIPOS DE PEDIDO DE PROVEEDOR (COMPRA O NO (REPARACION Y GARANTIA))

            //ACTUALIZO EL ESTADO DEL PEDIDO EN FUNCION DE LA CANTIDAD PEDIDA Y LA CANTIDAD PENDIENTE DE SERVIR
            $num = $bd->NumRegsTabla("PEDIDO_ENTRADA_LINEA", "ID_PEDIDO_ENTRADA = $rowMovLinea->ID_PEDIDO AND CANTIDAD <> CANTIDAD_PDTE AND INDICADOR_BORRADO IS NULL", "No");
            if ($num == 0):
                //CALCULO EL NUMERO DE LINEAS CUYA CANTIDAD EN MAYOR QUE CERO
                $numLineasConCantidad = $bd->NumRegsTabla("PEDIDO_ENTRADA_LINEA", "ID_PEDIDO_ENTRADA = $rowMovLinea->ID_PEDIDO AND CANTIDAD > 0 AND INDICADOR_BORRADO IS NULL", "No");
                if ($numLineasConCantidad > 0):
                    $sqlUpdate = "UPDATE PEDIDO_ENTRADA SET ESTADO = 'Creado' WHERE ID_PEDIDO_ENTRADA = $rowMovLinea->ID_PEDIDO";
                else:
                    $sqlUpdate = "UPDATE PEDIDO_ENTRADA SET ESTADO = 'Entregado' WHERE ID_PEDIDO_ENTRADA = $rowMovLinea->ID_PEDIDO";
                endif;
            else:
                $sqlUpdate = "UPDATE PEDIDO_ENTRADA SET ESTADO = 'En Entrega' WHERE ID_PEDIDO_ENTRADA = $rowMovLinea->ID_PEDIDO";
            endif;
            $bd->ExecSQL($sqlUpdate);
        //FIN ACTUALIZO EL ESTADO DEL PEDIDO EN FUNCION DE LA CANTIDAD PEDIDA Y LA CANTIDAD PENDIENTE DE SERVIR

        elseif ($rowMov->TIPO_MOVIMIENTO == 'PedidoDevolucionVenta'): //TIPO DE PEDIDO PROVENIENTE DEL CLIENTE

            //BUSCO EL PEDIDO
            $rowPed = $bd->VerReg("PEDIDO_ENTRADA", "ID_PEDIDO_ENTRADA", $rowMovLinea->ID_PEDIDO);

            //GUARDO LOS PEDIDOS DE ENTRADA INVOLUCRADOS EN ESTA LINEA DE MOVIMIENTO
            if (!in_array($rowMovLinea->ID_PEDIDO_LINEA, (array) $arrayLineasPedidosInvolucradas)):
                $arrayLineasPedidosInvolucradas[] = $rowMovLinea->ID_PEDIDO_LINEA;
            endif;

            //BUSCO LA LINEA DEL PEDIDO DE ENTRADA CORRESPONDIENTE
            $rowPedLin = $bd->VerReg("PEDIDO_ENTRADA_LINEA", "ID_PEDIDO_ENTRADA_LINEA", $rowMovLinea->ID_PEDIDO_LINEA);

            //BUSCO LA CANTIDAD ASIGNADA A OTROS MOVIMIENTOS DE ENTRADA LINEA
            $sqlSuma    = "SELECT IF(SUM(CANTIDAD) IS NULL,0,SUM(CANTIDAD)) AS SUMA_CANTIDAD
									FROM MOVIMIENTO_ENTRADA_LINEA 
									WHERE ID_PEDIDO_LINEA = $rowMovLinea->ID_PEDIDO_LINEA AND ID_MOVIMIENTO_ENTRADA_LINEA <> $idLineaMov AND LINEA_ANULADA = 0 AND BAJA = 0
									AND (ID_TIPO_BLOQUEO <> $rowTipoBloqueoLineaDevolverProveedorCalidad->ID_TIPO_BLOQUEO OR ID_TIPO_BLOQUEO IS NULL)";
            $resultSuma = $bd->ExecSQL($sqlSuma);
            if (($resultSuma == false) || ($bd->NumRegs($resultSuma) == 0)):
                $cantidadEnMovimientos = 0;
            else:
                $rowSuma               = $bd->SigReg($resultSuma);
                $cantidadEnMovimientos = $rowSuma->SUMA_CANTIDAD;
            endif;
            //FIN BUSCO LA CANTIDAD ASIGNADA A OTROS MOVIMIENTOS DE ENTRADA LINEA

            //ACTUALIZO LA CANTIDAD PENDIENTE DE SERVIR EN EL PEDIDO
            $sqlUpdate = "UPDATE PEDIDO_ENTRADA_LINEA SET
										CANTIDAD_PDTE = CANTIDAD - $cantidadEnMovimientos 
										, CANTIDAD_NO_SUMINISTRADA = 0 
										WHERE ID_PEDIDO_ENTRADA_LINEA = $rowMovLinea->ID_PEDIDO_LINEA";
            $bd->ExecSQL($sqlUpdate);
            //FIN ACTUALIZO LA CANTIDAD PENDIENTE DE SERVIR EN EL PEDIDO

            $anulacion_stock = 1;

            //ACTUALIZO EL ESTADO DEL PEDIDO EN FUNCION DE LA CANTIDAD PEDIDA Y LA CANTIDAD PENDIENTE DE SERVIR
            $num = $bd->NumRegsTabla("PEDIDO_ENTRADA_LINEA", "ID_PEDIDO_ENTRADA = $rowMovLinea->ID_PEDIDO AND CANTIDAD <> CANTIDAD_PDTE AND INDICADOR_BORRADO IS NULL", "No");
            if ($num == 0):
                //CALCULO EL NUMERO DE LINEAS CUYA CANTIDAD EN MAYOR QUE CERO
                $numLineasConCantidad = $bd->NumRegsTabla("PEDIDO_ENTRADA_LINEA", "ID_PEDIDO_ENTRADA = $rowMovLinea->ID_PEDIDO AND CANTIDAD > 0 AND INDICADOR_BORRADO IS NULL", "No");
                if ($numLineasConCantidad > 0):
                    $sqlUpdate = "UPDATE PEDIDO_ENTRADA SET ESTADO = 'Creado' WHERE ID_PEDIDO_ENTRADA = $rowMovLinea->ID_PEDIDO";
                else:
                    $sqlUpdate = "UPDATE PEDIDO_ENTRADA SET ESTADO = 'Entregado' WHERE ID_PEDIDO_ENTRADA = $rowMovLinea->ID_PEDIDO";
                endif;
            else:
                $sqlUpdate = "UPDATE PEDIDO_ENTRADA SET ESTADO = 'En Entrega' WHERE ID_PEDIDO_ENTRADA = $rowMovLinea->ID_PEDIDO";
            endif;
            $bd->ExecSQL($sqlUpdate);
        //FIN ACTUALIZO EL ESTADO DEL PEDIDO EN FUNCION DE LA CANTIDAD PEDIDA Y LA CANTIDAD PENDIENTE DE SERVIR

        elseif ($rowMov->TIPO_MOVIMIENTO == 'PedidoTraslado'): //TIPO DE PEDIDO DE TRASLADO
            //MODIFICO ANULACION STOCK A CERO
            $anulacion_stock = 0;

            //BUSCO EL MOVIMIENTO DE SALIDA
            $rowMovSal = $bd->VerReg("MOVIMIENTO_SALIDA", "ID_MOVIMIENTO_SALIDA", $rowMovLinea->ID_MOVIMIENTO_SALIDA);

            //ACTUALIZO EL MOVIMIENTO DE SALIDA LINEA QUE SE RECEPCIONO EN ESTA LINEA DE MOVIMIENTO DE ENTRADA
            $sqlUpdate = "UPDATE MOVIMIENTO_SALIDA_LINEA SET
										ESTADO = 'En Tránsito' 
										, CANTIDAD_PENDIENTE_DE_RECEPCIONAR_EN_DESTINO = CANTIDAD_PENDIENTE_DE_RECEPCIONAR_EN_DESTINO + $rowMovLinea->CANTIDAD 
										WHERE ID_MOVIMIENTO_SALIDA_LINEA = $rowMovLinea->ID_MOVIMIENTO_SALIDA_LINEA";
            $bd->ExecSQL($sqlUpdate);

            //ACTUALIZO EL MOVIMIENTO DE SALIDA QUE SE RECEPCIONO EN ESTA LINEA DE MOVIMIENTO DE ENTRADA
            $sqlUpdate = "UPDATE MOVIMIENTO_SALIDA SET
										ESTADO = 'En Tránsito' 
										WHERE ID_MOVIMIENTO_SALIDA = $rowMovLinea->ID_MOVIMIENTO_SALIDA";
            $bd->ExecSQL($sqlUpdate);

            //ACTUALIZO LA EXPEDICION A ESTADO EN TRANSITO
            $sqlUpdate = "UPDATE EXPEDICION SET
										ESTADO = 'En Tránsito' 
										WHERE ID_EXPEDICION = $rowMovSal->ID_EXPEDICION";
            $bd->ExecSQL($sqlUpdate);
        elseif ($rowMov->TIPO_MOVIMIENTO == 'DevolucionOM' || $rowMov->TIPO_MOVIMIENTO == 'MultiOM'): //DEVOLUCION OM

            //MODIFICO ANULACION STOCK A CERO
            $anulacion_stock = 1;

            //BUSCAMOS LA ORDEN DE MONTAJE MOVIMIENTO
            $rowOrdenMontajeMovimiento = $bd->VerReg("ORDEN_MONTAJE_MOVIMIENTO", "ID_ORDEN_MONTAJE_MOVIMIENTO", $rowMovLinea->ID_ORDEN_MONTAJE_MOVIMIENTO);

            if ($rowOrdenMontajeMovimiento->TIPO_OPERACION == 'Entrega')://SI ES UNA ENTREGA, DAMOS DE ALTA EL MATERIAL EN MAQUINA

                //ACTUALIZAMOS EL MOVIMIENTO DE ORDEN MONTAJE
                $sqlUpdate = "UPDATE ORDEN_MONTAJE_MOVIMIENTO SET CANTIDAD_ANULADA = CANTIDAD_ANULADA - $rowMovLinea->CANTIDAD WHERE ID_ORDEN_MONTAJE_MOVIMIENTO = $rowOrdenMontajeMovimiento->ID_ORDEN_MONTAJE_MOVIMIENTO ";
                $bd->ExecSQL($sqlUpdate);

                //BUSCAMOS EL MATERIAL UBICACION EN MAQUINA
                $rowMatUbiOMM = $bd->VerRegRest("MATERIAL_UBICACION", "ID_MATERIAL = $rowOrdenMontajeMovimiento->ID_MATERIAL AND ID_UBICACION = $rowOrdenMontajeMovimiento->ID_UBICACION AND ID_MATERIAL_FISICO " . ($rowOrdenMontajeMovimiento->ID_MATERIAL_FISICO == NULL ? "IS NULL" : "= $rowOrdenMontajeMovimiento->ID_MATERIAL_FISICO") . " AND ID_TIPO_BLOQUEO IS NULL AND ID_ORDEN_TRABAJO_MOVIMIENTO IS NULL AND ID_INCIDENCIA_CALIDAD IS NULL", "No");

                if ($devolver_error == false):
                    $html->PagErrorCondicionado($rowMatUbiOMM, "==", false, "MaterialUbicacionOrdenMontajeNoExiste");
                else:
                    if (!$rowMatUbiOMM):
                        //DESHAGO LA TRANSACCION
                        $bd->rollback_transaction();

                        return $auxiliar->traduce("El material ubicacion en maquina no existe", $administrador->ID_IDIOMA);
                    endif;
                endif;

                //ACTUALIZO MATERIAL UBICACION, INCREMENTANDO EL MATERIAL LIBRE (MAQUINA)
                $sqlUpdate = "UPDATE MATERIAL_UBICACION SET
                                STOCK_TOTAL = STOCK_TOTAL + " . $rowMovLinea->CANTIDAD . "
                                , STOCK_OK = STOCK_OK + " . $rowMovLinea->CANTIDAD . "
                                WHERE ID_MATERIAL_UBICACION = $rowMatUbiOMM->ID_MATERIAL_UBICACION";
                $bd->ExecSQL($sqlUpdate);

                //BUSCO EL ASIENTO QUE GENERO LA SALIDA PARA DARLO DE BAJA
                $rowAsientoBajaMaquina = $bd->VerRegRest("ASIENTO", "TIPO_ASIENTO = 'Devolucion OM' AND ID_MOVIMIENTO_ENTRADA_LINEA = $rowMovLinea->ID_MOVIMIENTO_ENTRADA_LINEA AND ID_ORDEN_MONTAJE_MOVIMIENTO = $rowOrdenMontajeMovimiento->ID_ORDEN_MONTAJE_MOVIMIENTO AND BAJA = 0", "No");

                if ($devolver_error == false):
                    $html->PagErrorCondicionado($rowAsientoBajaMaquina, "==", false, "AsientoBajaMaquinaNoEncontrado");
                else:
                    if (!$rowAsientoBajaMaquina):
                        //DESHAGO LA TRANSACCION
                        $bd->rollback_transaction();

                        return $auxiliar->traduce("El asiento de baja de Devolucion OM no se encuentra", $administrador->ID_IDIOMA);
                    endif;
                endif;

                //DOY DE BAJA EL ASIENTO Y DESASOCIO EL MOVIMIENTO ENTRADA LINEA DADO DE BAJA
                $sqlUpdate = "UPDATE ASIENTO SET BAJA = 1, ID_MOVIMIENTO_ENTRADA_LINEA = NULL WHERE ID_ASIENTO = $rowAsientoBajaMaquina->ID_ASIENTO";
                $bd->ExecSQL($sqlUpdate);
            else:
                //ACTUALIZAMOS EL MOVIMIENTO DE ORDEN MONTAJE
                $sqlUpdate = "UPDATE ORDEN_MONTAJE_MOVIMIENTO SET CANTIDAD_ANULADA = $rowMovLinea->CANTIDAD WHERE ID_ORDEN_MONTAJE_MOVIMIENTO = $rowOrdenMontajeMovimiento->ID_ORDEN_MONTAJE_MOVIMIENTO ";
                $bd->ExecSQL($sqlUpdate);
            endif;
        endif;
        //FIN TIPOS DE PEDIDOS DE ENTRADA

        // 10. ACTUALIZO LA LINEA DEL MOVIMIENTO DE ENTRADA
        $sqlUpdate = "UPDATE MOVIMIENTO_ENTRADA_LINEA SET
									ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR 
									, LINEA_ANULADA = 1 
									, FECHA_ANULACION = '" . date("Y-m-d H:i:s") . "' 
									, ANULACION_STOCK = $anulacion_stock 
									WHERE ID_MOVIMIENTO_ENTRADA_LINEA = $idLineaMov";
        $bd->ExecSQL($sqlUpdate);

        //DESASOCIO LAS POSIBLES LINEAS DE NECESIDAD
        if (($rowMov->TIPO_MOVIMIENTO != 'DevolucionOM') && ($rowMov->TIPO_MOVIMIENTO != 'MultiOM') && ($rowMovLinea->ID_PEDIDO != NULL) && ($rowMovLinea->ID_PEDIDO_LINEA != NULL)):
            $necesidad->DesasociarMovimientoEntradaLineaAnulada($idLineaMov);
        endif;

        // 12. BORRAMOS LAS ETIQUETAS MANUALES SI TIENE
        if ($rowMovLinea->TIPO_ETIQUETADO == 'Manual'):
            $sqlDelete = "DELETE FROM MOVIMIENTO_ENTRADA_LINEA_ETIQUETAS_MANUALES WHERE ID_MOVIMIENTO_ENTRADA_LINEA = $idLineaMov";
            $bd->ExecSQL($sqlDelete);
        endif;

        // SI EL MOVIMIENTO NO TIENE LINEAS (TODAS LAS LINEAS HAN SIDO ANULADAS) LO PASO A UBICADO
        $estado = $this->actualizarEstadoMovimientoEntrada($rowMov->ID_MOVIMIENTO_ENTRADA);
//        $estado = $rowMov->ESTADO;
//        $numLineasActivas = $bd->NumRegsTabla("MOVIMIENTO_ENTRADA_LINEA", "ID_MOVIMIENTO_ENTRADA = $rowMov->ID_MOVIMIENTO_ENTRADA AND CANTIDAD <> 0 AND BAJA = 0 AND LINEA_ANULADA = 0 ", "No");
//        if ($numLineasActivas == 0):
//            if ($rowMov->ADJUNTO == NULL):
//                $estado = 'Ubicado';
//            else:
//                $estado = 'Escaneado y Finalizado';
//            endif;
//        else:
//            $numLineasTotal = $bd->NumRegsTabla("MOVIMIENTO_ENTRADA_LINEA", "ID_MOVIMIENTO_ENTRADA = $rowMov->ID_MOVIMIENTO_ENTRADA AND CANTIDAD <> 0 AND BAJA = 0", "No");
//            if($numLineasActivas != $numLineasTotal):
//                $estado = 'En Ubicacion';
//            endif;
//        endif;
        $sqlUpdate = "UPDATE MOVIMIENTO_ENTRADA 
                      SET ESTADO = '" . $estado . "' 
                      WHERE ID_MOVIMIENTO_ENTRADA = $rowMov->ID_MOVIMIENTO_ENTRADA";
        $bd->ExecSQL($sqlUpdate);

        //OBTENGO EL ESTADO FINAL DE LA RECEPCION TRAS LOS CAMBIOS
        $estadoFinalRecepcion = $this->getEstadoRecepcion($rowRecepcion->ID_MOVIMIENTO_RECEPCION);

        //ACTUALIZO EL ESTADO DE LA RECEPCION
        $sqlUpdate = "UPDATE MOVIMIENTO_RECEPCION SET ESTADO = '" . $estadoFinalRecepcion . "' WHERE ID_MOVIMIENTO_RECEPCION = $rowRecepcion->ID_MOVIMIENTO_RECEPCION";
        $bd->ExecSQL($sqlUpdate);

        if ($rowPed->TIPO_PEDIDO == 'Compra'):
            // 14. AVISAMOS AL PROVEEDOR DE LA ANULACION
            $rowMovEntrada = $bd->VerReg("MOVIMIENTO_ENTRADA", "ID_MOVIMIENTO_ENTRADA", $rowMovLinea->ID_MOVIMIENTO_ENTRADA, "No");
            $rowProv       = $bd->VerReg("PROVEEDOR", "ID_PROVEEDOR", $rowMovEntrada->ID_PROVEEDOR, "No");
            $Destinatario  = "";
            $Cuerpo        = "";

            $rowMat = $bd->VerReg("MATERIAL", "ID_MATERIAL", $rowMovLinea->ID_MATERIAL, "No");
            if ($rowMov->TIPO_MOVIMIENTO == 'PedidoProveedor'):
                //BUSCO LA LINEA DEL PEDIDO DE ENTRADA CORRESPONDIENTE
                $rowPedLin      = $bd->VerReg("PEDIDO_ENTRADA_LINEA", "ID_PEDIDO_ENTRADA_LINEA", $rowMovLinea->ID_PEDIDO_LINEA);
                $rowPedido      = $bd->VerReg("PEDIDO_ENTRADA", "ID_PEDIDO_ENTRADA", $rowMovLinea->ID_PEDIDO, "No");
                $rowPedidoLinea = $bd->VerReg("PEDIDO_ENTRADA_LINEA", "ID_PEDIDO_ENTRADA_LINEA", $rowMovLinea->ID_PEDIDO_LINEA, "No");
                $Cuerpo         = "La línea $rowPedidoLinea->LINEA_PEDIDO_SAP del pedido $rowPedido->PEDIDO_SAP ha sido anulada.\n";
            elseif ($rowMov->TIPO_MOVIMIENTO == 'PedidoTraslado'):
                //BUSCO EL MOVIMIENTO DE SALIDA
                $rowMovSalLin = $bd->VerReg("MOVIMIENTO_SALIDA_LINEA", "ID_MOVIMIENTO_SALIDA_LINEA", $rowMovLinea->ID_MOVIMIENTO_SALIDA_LINEA);
                $rowMovSal    = $bd->VerReg("MOVIMIENTO_SALIDA", "ID_MOVIMIENTO_SALIDA", $rowMovSalLin->ID_MOVIMIENTO_SALIDA);
                $Cuerpo       = $auxiliar->traduce("La línea", $administrador->ID_IDIOMA) . " $rowMovSalLin->LINEA_MOVIMIENTO_SAP " . $auxiliar->traduce("del movimiento de traslado", $administrador->ID_IDIOMA) . " $rowPedido->PEDIDO_SAP " . $auxiliar->traduce("ha sido anulada", $administrador->ID_IDIOMA) . ".\n";
            endif;
            $Cuerpo .= $auxiliar->traduce("Material", $administrador->ID_IDIOMA) . ": $rowMat->REFERENCIA - " . ($administrador->ID_IDIOMA == "ESP" ? $rowMat->DESCRIPCION : $rowMat->DESCRIPCION_EN) . ".\n";
            $Cuerpo .= $auxiliar->traduce("Cantidad", $administrador->ID_IDIOMA) . ": $rowMovLinea->CANTIDAD.";

            if ($rowProv->EMAIL != ""):
                $Destinatario = $rowProv->EMAIL;
            else:
                global $email_soporte;

                $Destinatario = $email_soporte;
                $Cuerpo       = $auxiliar->traduce("El proveedor", $administrador->ID_IDIOMA) . " $rowProv->NOMBRE " . $auxiliar->traduce("no tiene email asociado", $administrador->ID_IDIOMA) . ".\n" . $auxiliar->traduce("Mensaje original", $administrador->ID_IDIOMA) . ":\n\n" . $Cuerpo;
            endif;
        endif;

        //BUSCO EL MOVIMIENTO Y LA RECEPCION ACTUALIZADOS
        $rowMov                = $bd->VerReg("MOVIMIENTO_ENTRADA", "ID_MOVIMIENTO_ENTRADA", $idMovimiento, "No");
        $estadoFinalMovimiento = $rowMov->ESTADO;
        $rowRecepcion          = $bd->VerReg("MOVIMIENTO_RECEPCION", "ID_MOVIMIENTO_RECEPCION", $rowMov->ID_MOVIMIENTO_RECEPCION, "No");
        $estadoFinalRecepcion  = $rowMov->ESTADO;

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

        //SI SON SIN PEDIDO CONODIDO O CON PEDIDO CONOCIDO, AVANZAMOS EL ESTADO A RECEPCIONADO SI SE HAN PROCESADO TODAS LAS LINEAS
        $this->actualizarRecogidasEnProveedor($idMovimiento);
        //FIN SI SON SIN PEDIDO CONODIDO O CON PEDIDO CONOCIDO, AVANZAMOS EL ESTADO A RECEPCIONADO SI SE HAN PROCESADO TODAS LAS LINEAS

        //SI ES UNA ANULACION CORRECTA INFORMO AL PROVEEDOR
        if ($anulacion_stock == 0):
            if ($rowPed->TIPO_PEDIDO == 'Compra'):
                //$aviso->LineaMovimientoEntradaAnulada($Destinatario, $Cuerpo);
                //$bd->EnviarEmail($Destinatario,$auxiliar->traduce("Devolución de material",$administrador->ID_IDIOMA),$Cuerpo);
            endif;
        endif;

        // 15. INFORMAMOS A SAP DE LA LINEA ANULADA, EN CASO DE QUE SAP TENGA CONSTANCIA DE ESTA LINEA
        $rowTipoBloqueoLineaControlCalidadNoPreventivo = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "CCNP", "No");
        $rowTipoBloqueoLineaControlCalidadPreventivo   = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "CCP", "No");
        $rowTipoBloqueoLineaDevolverProveedorCalidad   = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "SPDPC", "No");

        if (
            ($rowMovLinea->ID_TIPO_BLOQUEO != $rowTipoBloqueoLineaControlCalidadNoPreventivo->ID_TIPO_BLOQUEO) &&
            ($rowMovLinea->ID_TIPO_BLOQUEO != $rowTipoBloqueoLineaControlCalidadPreventivo->ID_TIPO_BLOQUEO) &&
            ($rowMovLinea->ID_TIPO_BLOQUEO != $rowTipoBloqueoLineaDevolverProveedorCalidad->ID_TIPO_BLOQUEO)
        ):

            if ($rowMov->TIPO_MOVIMIENTO == 'PedidoDevolucionVenta'):
                $resultado['RESULTADO'] = 'OK';
            elseif ($rowMov->TIPO_MOVIMIENTO == 'DevolucionOM' || $rowMov->TIPO_MOVIMIENTO == 'MultiOM' || $rowMov->TIPO_MOVIMIENTO == 'Construccion'):
                $resultado['RESULTADO'] = 'OK';
            else:
                $resultado = $sap->EnvioLineaMovimientoEntradaAnulada($idLineaMov);
            endif;

            if ($resultado['RESULTADO'] != 'OK'): //No se ha podido grabar la información en SAP
                foreach ($resultado['ERRORES'] as $arr):
                    foreach ($arr as $mensaje_error):
                        if ($devolver_error == false):
                            $strErrorSAPAnulacion = $strErrorSAPAnulacion . $mensaje_error . "<br>";
                        else:
                            $strErrorSAPAnulacion = $strErrorSAPAnulacion . $mensaje_error . ". ";
                        endif;
                    endforeach;
                endforeach;

                //DESHAGO LA TRANSACCION
                $bd->rollback_transaction();

                //INICIO TRANSACCION
                $bd->begin_transaction();

                //GRABO EL ERROR PRODUCIDO EN BASE DE DATOS
                $sap->InsertarErrores($resultado);

                //FINALIZO LA TRANSACCION DE GRABAR EL ERROR
                $bd->commit_transaction();

                //SI INICIALMENTE EL MATERIAL ESTABA ASOCIADO A UNA INICIDENCIA DE CALIDAD Y TENIA STOCK BLOQUEADO POR CALIDAD LO INTENTO BLOQUEAR
                if (($idIC != NULL) && ($rowMatUbiIC->STOCK_BLOQUEADO > 0)):

                    //INICIO TRANSACCION
                    $bd->begin_transaction();

                    //BUSCAMOS LA INCIDENCIA CALIDAD ASIGNADA
                    $rowIC = $bd->VerReg("INCIDENCIA_CALIDAD", "ID_INCIDENCIA_CALIDAD", $idIC, "No");

                    //RECUPERO LA LINEA DEL MOVIMIENTO ACTUALIZADA
                    $NotificaErrorPorEmail = "No";
                    $rowMovLinea           = $bd->VerReg("MOVIMIENTO_ENTRADA_LINEA", "ID_MOVIMIENTO_ENTRADA_LINEA", $rowMovLinea->ID_MOVIMIENTO_ENTRADA_LINEA, "No");
                    unset($NotificaErrorPorEmail);

                    //TIPO BLOQUEO PREVENTIVO
                    $rowTipoBloqueoPreventivo = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", 'SP'); //Bloqueo Preventivo
                    $idTipoBloqueoPreventivo  = $rowTipoBloqueoPreventivo->ID_TIPO_BLOQUEO;
                    $TipoBloqueoPreventivoSAP = $rowTipoBloqueoPreventivo->TIPO_BLOQUEO_SAP;

                    //TIPO BLOQUEO CALIDAD NO PREVENTIVO
                    $rowTipoBloqueoCalidadNoPreventivo = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", 'XRC'); //Bloqueo Calidad No Preventivo
                    $idTipoBloqueoCalidadNoPreventivo  = $rowTipoBloqueoCalidadNoPreventivo->ID_TIPO_BLOQUEO;
                    $TipoBloqueoNoPreventivoSAP        = $rowTipoBloqueoCalidadNoPreventivo->TIPO_BLOQUEO_SAP;

                    //TIPO BLOQUEO CALIDAD PREVENTIVO
                    $rowTipoBloqueoCalidadPreventivo = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", 'XRCP'); //Bloqueo Calidad No Preventivo
                    $idTipoBloqueoCalidadPreventivo  = $rowTipoBloqueoCalidadPreventivo->ID_TIPO_BLOQUEO;
                    $TipoBloqueoCalidadPreventivoSAP = $rowTipoBloqueoCalidadPreventivo->TIPO_BLOQUEO_SAP;

                    //EN FUNCION DEL TIPO DE BLOQUEO DE LA LINEA SE ESTABLECE EL NUEVO TIPO DE BLOQUEO
                    if ($rowMovLinea->ID_TIPO_BLOQUEO == $idTipoBloqueoPreventivo):
                        $idTipoBloqueoFinal = $idTipoBloqueoCalidadPreventivo;
                    else:
                        $idTipoBloqueoFinal = $idTipoBloqueoCalidadNoPreventivo;
                    endif;

                    //MATERIAL UBICACION ORIGEN
                    $NotificaErrorPorEmail = "No";
                    $clausulaWhere         = "ID_MATERIAL = $rowMovLinea->ID_MATERIAL AND ID_UBICACION = $rowMovLinea->ID_UBICACION AND ID_MATERIAL_FISICO " . ($rowMovLinea->ID_MATERIAL_FISICO == NULL ? "IS NULL" : "= $rowMovLinea->ID_MATERIAL_FISICO") . " AND ID_TIPO_BLOQUEO " . ($rowMovLinea->ID_TIPO_BLOQUEO == NULL ? "IS NULL" : "= $rowMovLinea->ID_TIPO_BLOQUEO") . " AND ID_ORDEN_TRABAJO_MOVIMIENTO IS NULL AND ID_INCIDENCIA_CALIDAD IS NULL ";
                    $rowMatUbiOrigen       = $bd->VerRegRest("MATERIAL_UBICACION", $clausulaWhere, "No");
                    if ($rowMatUbiOrigen == false):
                        if ($devolver_error == false):
                            $html->PagError($auxiliar->traduce("Error en la ubicación de origen", $administrador->ID_IDIOMA));
                        else:
                            //DESHAGO LA TRANSACCION
                            $bd->rollback_transaction();

                            return $auxiliar->traduce("Error en la ubicación de origen", $administrador->ID_IDIOMA);
                        endif;
                    else:
                        $idMatUbiOrigen = $rowMatUbiOrigen->ID_MATERIAL_UBICACION;
                    endif;

                    //DECREMENTO LA CANTIDAD EN MATERIAL UBICACION ORIGEN
                    $sqlUpdate = "UPDATE MATERIAL_UBICACION SET
                                    STOCK_TOTAL = STOCK_TOTAL - $rowMatUbiIC->STOCK_BLOQUEADO
                                    , STOCK_OK = STOCK_OK - " . ($rowMovLinea->ID_TIPO_BLOQUEO == NULL ? $rowMatUbiIC->STOCK_BLOQUEADO : 0) . "
                                    , STOCK_BLOQUEADO = STOCK_BLOQUEADO - " . ($rowMovLinea->ID_TIPO_BLOQUEO == NULL ? 0 : $rowMatUbiIC->STOCK_BLOQUEADO) . "
                                    WHERE ID_MATERIAL_UBICACION = $idMatUbiOrigen";
                    $bd->ExecSQL($sqlUpdate);

                    //COMPRUEBO QUE EXISTA LA UBICACION DE LA INCIDENCIA DONDE DEPOSITAR EL MATERIAL DE LA LINEA
                    $NotificaErrorPorEmail = "No";
                    $rowMatUbiIncidencia   = $bd->VerRegRest("MATERIAL_UBICACION", "ID_MATERIAL = $rowMovLinea->ID_MATERIAL AND ID_UBICACION = $rowMovLinea->ID_UBICACION AND ID_MATERIAL_FISICO " . ($rowMovLinea->ID_MATERIAL_FISICO == NULL ? "IS NULL" : "= $rowMovLinea->ID_MATERIAL_FISICO") . " AND ID_TIPO_BLOQUEO " . ($idTipoBloqueoFinal == NULL ? 'IS NULL' : "= $idTipoBloqueoFinal") . " AND ID_ORDEN_TRABAJO_MOVIMIENTO IS NULL AND ID_INCIDENCIA_CALIDAD = $idIC", "No");
                    unset($NotificaErrorPorEmail);
                    if ($rowMatUbiIncidencia == false):
                        $sqlInsert = "INSERT INTO MATERIAL_UBICACION SET
                                        ID_MATERIAL = $rowMovLinea->ID_MATERIAL
                                        , ID_UBICACION = $rowMovLinea->ID_UBICACION
                                        , ID_MATERIAL_FISICO = " . ($rowMovLinea->ID_MATERIAL_FISICO == NULL ? 'NULL' : "$rowMovLinea->ID_MATERIAL_FISICO") . "
                                        , ID_TIPO_BLOQUEO = " . ($idTipoBloqueoFinal == NULL ? 'NULL' : "$idTipoBloqueoFinal") . "
                                        , ID_ORDEN_TRABAJO_MOVIMIENTO = NULL
                                        , ID_INCIDENCIA_CALIDAD = $idIC";
                        $bd->execSQL($sqlInsert);
                        $idMatUbiDestinoIncidencia = $bd->IdAsignado();
                    else:
                        $idMatUbiDestinoIncidencia = $rowMatUbiIncidencia->ID_MATERIAL_UBICACION;
                    endif;

                    //INCREMENTO LA CANTIDAD EN MATERIAL UBICACION INCIDENCIA DESTINO
                    $sqlUpdate = "UPDATE MATERIAL_UBICACION SET
                                    STOCK_TOTAL = STOCK_TOTAL + $rowMatUbiIC->STOCK_BLOQUEADO
                                    , STOCK_OK = STOCK_OK + " . ($idTipoBloqueoFinal == NULL ? $rowMatUbiIC->STOCK_BLOQUEADO : 0) . "
                                    , STOCK_BLOQUEADO = STOCK_BLOQUEADO + " . ($idTipoBloqueoFinal == NULL ? 0 : $rowMatUbiIC->STOCK_BLOQUEADO) . "
                                    WHERE ID_MATERIAL_UBICACION = $idMatUbiDestinoIncidencia";
                    $bd->ExecSQL($sqlUpdate);

                    //SI EL BLOQUEO INICIAL NO ES CALIDAD SE REALIZARÁ EL CAMBIO DE ESTADO
                    if (($rowMovLinea->ID_TIPO_BLOQUEO != $idTipoBloqueoCalidadNoPreventivo) && ($rowMovLinea->ID_TIPO_BLOQUEO != $idTipoBloqueoCalidadPreventivo)):
                        //GENERO EL CAMBIO DE ESTADO
                        $sqlInsert = " INSERT INTO CAMBIO_ESTADO SET
                                         FECHA = '" . date('Y-m-d H:i:s') . "'
                                        , ID_ADMINISTRADOR = " . $administrador->ID_ADMINISTRADOR . "
                                        , ID_MATERIAL = " . $rowMovLinea->ID_MATERIAL . "
                                        , ID_MATERIAL_FISICO = " . ($rowMovLinea->ID_MATERIAL_FISICO == NULL ? "NULL" : $rowMovLinea->ID_MATERIAL_FISICO) . "
                                        , ID_UBICACION = " . $rowMovLinea->ID_UBICACION . "
                                        , CANTIDAD = " . $rowMatUbiIC->STOCK_BLOQUEADO . "
                                        , ID_TIPO_BLOQUEO_INICIAL = " . ($rowMovLinea->ID_TIPO_BLOQUEO == NULL ? "NULL" : $rowMovLinea->ID_TIPO_BLOQUEO) . "
                                        , ID_TIPO_BLOQUEO_FINAL = " . $idTipoBloqueoFinal . "
                                        , OBSERVACIONES ='" . trim( (string)$bd->escapeCondicional($rowIC->DESCRIPCION)) . "'";
                        $bd->ExecSQL($sqlInsert); //exit($sqlInsert);
                        $idCambioEstado = $bd->IdAsignado();

                        //LLAMADA A SAP
                        //ENVIO A SAP EL CAMBIO DE ESTADO
                        if ($rowMovLinea->ID_TIPO_BLOQUEO != $idTipoBloqueoPreventivo):
                            $resultado = $sap->AjusteCambioEstado($idCambioEstado);
                            if ($resultado['RESULTADO'] != 'OK'): //No se ha podido grabar la información en SAP
                                foreach ($resultado['ERRORES'] as $arr):
                                    foreach ($arr as $mensaje_error):
                                        if ($devolver_error == false):
                                            $strError = $strError . $mensaje_error . "<br>";
                                        else:
                                            $strError = $strError . $mensaje_error . ". ";
                                        endif;
                                    endforeach;
                                endforeach;

                                //DESHAGO LA TRANSACCION
                                $bd->rollback_transaction();

                                //GRABO EL ERROR PRODUCIDO EN BASE DE DATOS
                                $sap->InsertarErrores($resultado);

                                if ($devolver_error == false):
                                    $html->PagError($auxiliar->traduce("Error SAP: Se ha desbloqueado el material para anular la linea. La anulacion de la linea no ha sido posible, y tampoco bloquear de nuevo el material, por favor, vaya a Almacen >> Stock por ubicación y genere el cambio de estado de forma manual", $administrador->ID_IDIOMA) . "<br><br>" . $strErrorSAPAnulacion . "<br>" . $strError);
                                else:
                                    return $auxiliar->traduce("Error SAP: Se ha desbloqueado el material para anular la linea. La anulacion de la linea no ha sido posible, y tampoco bloquear de nuevo el material, por favor, vaya a Almacen >> Stock por ubicación y genere el cambio de estado de forma manual", $administrador->ID_IDIOMA) . ". " . $strErrorSAPAnulacion . ". " . $strError;
                                endif;

                            endif;
                        endif;
                    endif;

                    //FINALIZO LA TRANSACCION
                    $bd->commit_transaction();

                    //REALIZAMOS EL PASO DEL CICLO DE CALIDAD AL CICLO DE LOGISTICA INVERSA

                    //INICIO LA TRANSACCION
                    $bd->begin_transaction();

                    //REALIZO LA LLAMADA A SAP Y LA MODIFICACION DE DATOS
                    $arrDevuelto = $mat->PasoCicloCalidadCicloLogisticaInversa($idMatUbiDestinoIncidencia);

                    //SI SE HA PRODUCIDO UN ERROR CON EL PASO DEL CICLO DE CALIDAD AL CICLO DE LOGISITCA INVERSA O LA LLAMADA DE GARANTIAS A SAP DESHACEMOS LA TRANSACCION
                    if (($arrDevuelto['error_SGA'] == true) || ($arrDevuelto['error_SAP'] == true) || ($arrDevuelto['resultado']['RESULTADO'] != 'OK')):

                        //DESHAGO LA TRANSACCION
                        $bd->rollback_transaction();

                        if ($arrDevuelto['error_SAP'] == true):
                            //GRABO EL ERROR PRODUCIDO EN BASE DE DATOS
                            $sap->InsertarErrores($arrDevuelto['resultado']);
                        endif;

                        //DECLARO LA VARIABLE GLOBAL $strError
                        global $strError;

                        //ESTABLEZCO LOS ERRORES
                        $strError = $arrDevuelto['errores'];

                        //SI HAY ERRORES LOS MUESTRO POR PANTALLA
                        if ($devolver_error == false):
                            $html->PagErrorCondicionado($strError, "!=", "", "ErroresPasoCicloCalidadCicloLogisticaInversa");
                        else:
                            if ($strError != ''):
                                return $auxiliar->traduce("Ha habido errores en el paso del ciclo de calidad al ciclo de logistica inversa", $administrador->ID_IDIOMA);
                            endif;
                        endif;

                    else: //SI NO SE HAN PRODUCIDO ERRORES CONFIRMO LA TRANSACCION

                        //FINALIZO LA TRANSACCION
                        $bd->commit_transaction();

                    endif;
                    //FIN ACCIONES EN FUNCION DE SI LA LLAMADA SE REALIZA CORRECTAMENTE O SE PRODUCEN ERRORES
                endif;
                //FIN SI TIENE INCIDENCIA Y STOCK HAREMOS UN CAMBIO DE ESTADO PREVIO

                //ESTABLEZCO LOS ERRORES SAP DE ANULAR LINEA
                $strError = $strErrorSAPAnulacion;

                //MUESTRO LOS ERRORES SAP
                if ($devolver_error == false):
                    $html->PagError("ErrorSAP");
                else:
                    return "We have received the following errors from SAP/Se han producido los siguientes errores en el intercambio de información con SAP" . ": " . $strError;
                endif;
            endif;

        endif; //FIN INFORMAMOS A SAP DE LA LINEA ANULADA, EN CASO DE QUE SAP TENGA CONSTANCIA DE ESTA LINEA

        // LOG MOVIMIENTOS
        $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Mov. entrada", $idMovimiento, "Anulación línea" . " $idLineaMov");

        //FINALIZO LA TRANSACCION DE ACTUALIZACION DE STOCK
        $bd->commit_transaction();

        //INICIO TRANSACCION
        $bd->begin_transaction();

        if (
        (count( (array)$arrayLineasPedidosInvolucradas) > 0)
        ):
            //INFORMO A SAP DE LAS LINEAS BLOQUEADAS
            $resultado = $pedido->controlBloqueoLinea("Entrada", 'AnularLinea', implode(",", (array) $arrayLineasPedidosInvolucradas));
            if ($resultado['RESULTADO'] != 'OK'): //No se ha podido grabar la información en SAP
                if (count( (array)$resultado['ERRORES']) > 0):
                    foreach ($resultado['ERRORES'] as $arr):
                        foreach ($arr as $mensaje_error):
                            $strError = $strError . $mensaje_error . "<br>";
                        endforeach;
                    endforeach;
                endif;

                $bd->rollback_transaction();

                //GRABO EL ERROR PRODUCIDO EN BASE DE DATOS
                $sap->InsertarErrores($resultado);
            endif;
        endif;

        //FINALIZO LA TRANSACCION
        $bd->commit_transaction();

        return NULL;
    }

    /**
     * @param $idMovimientoRecepcion
     * @return $estadoMovimiento String CON EL ESTADO DEL MOVIMIENTO
     * OBTIENE EL ESTADO DE UN MOVIMIENTO RECEPCION EN FUNCION DE SU NUMERO DE ALBARANES Y MOVIMIENTOS DE ENTRADA
     */
    function getEstadoRecepcion($idMovimientoRecepcion)
    {

        global $bd;

        //OBTENGO LA RECEPCION MODIFICADA
        $rowDoc = $bd->VerReg("MOVIMIENTO_RECEPCION", "ID_MOVIMIENTO_RECEPCION", $idMovimientoRecepcion);

        //OBTENGO EL CF DESTINO
        $rowCentroFisico = $bd->VerReg("CENTRO_FISICO", "ID_CENTRO_FISICO", $rowDoc->ID_CENTRO_FISICO);

        //SI ES RECEPCION DE CONSTRUCCION Y EL CENTRO FISICO NO TIENE GESTION LOGISTICA, NO MIRAMOS MOVIMIENTOS
        if (($rowDoc->TIPO_RECEPCION == 'Construccion') && ($rowCentroFisico->GESTION_LOGISTICA_PROYECTO == 0)):

            if ($rowDoc->ADJUNTO <> NULL):    //CON DOCUMENTO ADJUNTO
                return "Escaneado y Finalizado";
            else:
                return "Ubicado";
            endif;

        else:

            //CALCULO EL NUMERO DE MOVIMIENTOS EN FUNCION DEL ESTADO DE ESTOS
            $numFinalizadosYEscaneado = $bd->NumRegsTabla("MOVIMIENTO_ENTRADA", "ID_MOVIMIENTO_RECEPCION = $rowDoc->ID_MOVIMIENTO_RECEPCION AND ESTADO = 'Escaneado y Finalizado' AND BAJA = 0");
            $numUbicados              = $bd->NumRegsTabla("MOVIMIENTO_ENTRADA", "ID_MOVIMIENTO_RECEPCION = $rowDoc->ID_MOVIMIENTO_RECEPCION AND ESTADO = 'Ubicado' AND BAJA = 0");
            $numEnUbicacion           = $bd->NumRegsTabla("MOVIMIENTO_ENTRADA", "ID_MOVIMIENTO_RECEPCION = $rowDoc->ID_MOVIMIENTO_RECEPCION AND ESTADO = 'En Ubicacion' AND BAJA = 0");
            $numProcesados            = $bd->NumRegsTabla("MOVIMIENTO_ENTRADA", "ID_MOVIMIENTO_RECEPCION = $rowDoc->ID_MOVIMIENTO_RECEPCION AND ESTADO = 'Procesado' AND BAJA = 0");
            $numEnProceso             = $bd->NumRegsTabla("MOVIMIENTO_ENTRADA", "ID_MOVIMIENTO_RECEPCION = $rowDoc->ID_MOVIMIENTO_RECEPCION AND ESTADO = 'En Proceso' AND BAJA = 0");
            $numMovimientos           = $bd->NumRegsTabla("MOVIMIENTO_ENTRADA", "ID_MOVIMIENTO_RECEPCION = $rowDoc->ID_MOVIMIENTO_RECEPCION AND BAJA = 0");

            if ($rowDoc->ALBARANES == $numMovimientos):
                if (($rowDoc->ADJUNTO <> NULL) && ($numFinalizadosYEscaneado == $numMovimientos)):    //TODOS LOS MOVIMIENTOS ESCANEADOS Y FINALIZADOS, Y CON DOCUMENTO ADJUNTO
                    return "Escaneado y Finalizado";
                elseif (($numUbicados + $numFinalizadosYEscaneado) == $numMovimientos):    //TODOS LOS MOVIMIENTOS UBICADOS O ESCANEADOS Y FINALIZADOS
                    return "Ubicado";
                elseif ($numEnProceso > 0):    //ALGUN MOVIMIENTO EN PROCESO
                    return "En Proceso";
                elseif ($numProcesados == $numMovimientos):    //TODOS LOS MOVIMIENTOS PROCESADOS
                    return "Procesado";
                else:
                    return "En Ubicacion";
                endif;
            elseif ($rowDoc->ALBARANES != $numMovimientos):    //NUMERO DE ALBARANES DIFERENTES DEL NUMERO DE MOVIMIENTOS DE ENTRADA
                return "En Proceso";
            endif;

        endif;


    }

    /**
     * @param $idMovimientoEntrada MOVIMIENTO DE ENTRADA SOBRE EL QUE GENERAR FOTO ENTRANTE SI CORRESPONDE
     * @return array ARRAY DEVUELTO CON LOS VALORES OBTENIDOS DE LA LLAMADA
     */
    function enviarFotoParaEntregasEntrantes($idMovimientoEntrada)
    {
        global $bd;
        global $sap;

        //VARIABLE PARA SABER SI HAY QUE MANDAR FOTO
        $relevanteParaEntregaEntrante = false;

        //ARRAY A DEVOLVER
        $arrDevuelto = array();

        //BUSCO EL MOVIMIENTO DE ENTRADA
        $rowMovimientoEntrada = $bd->VerReg("MOVIMIENTO_ENTRADA", "ID_MOVIMIENTO_ENTRADA", $idMovimientoEntrada);

        // BUSCO LA RECEPCION
        $rowDoc = $bd->VerReg("MOVIMIENTO_RECEPCION", "ID_MOVIMIENTO_RECEPCION", $rowMovimientoEntrada->ID_MOVIMIENTO_RECEPCION);

        //SI ESTA ASOCIADO A UNA ORDEN DE TRANSPORTE, COMPROBAMOS  QUE TIENE GASTOS
        /*if ($rowDoc->ID_ORDEN_TRANSPORTE != ""):
            //BUSCO LA ORDEN DE TRANSPORTE
            $rowOrdenTransporte = $bd->VerReg("ORDEN_TRANSPORTE", "ID_ORDEN_TRANSPORTE", $rowDoc->ID_ORDEN_TRANSPORTE);

            //SI TIENE GASTOS, LA FOTO SE ENVIA DESDE OTRO LUGAR
            if ($rowOrdenTransporte->TIENE_GASTOS_TRANSPORTE == 1):
                $arrDevuelto['ErrorFoto'] = false;
                $arrDevuelto['Resultado'] = NULL;

                return $arrDevuelto;
            endif;
        endif;*/
        //FIN SI ESTA ASOCIADO A UNA ORDEN DE TRANSPORTE, COMPROBAMOS  QUE TIENE GASTOS

        //COMPRUEBO SI YA TIENE FOTO ENVIADA
        $num = $bd->NumRegsTabla("EXPEDICION_ENTREGA_ENTRANTE", "ID_MOVIMIENTO_ENTRADA = $rowMovimientoEntrada->ID_MOVIMIENTO_ENTRADA");

        //SI TIENE FOTO ENVIADA SALGO DE LA FUNCION
        if ($num > 0):
            $arrDevuelto['ErrorFoto'] = false;
            $arrDevuelto['Resultado'] = NULL;

            return $arrDevuelto;
        endif;

        //BUSCO LAS LINEAS DEL MOVIMIENTO DE ENTRADA
        $sqlLineas    = "SELECT PEL.ID_PEDIDO_ENTRADA
                         FROM MOVIMIENTO_ENTRADA_LINEA MEL
                         INNER JOIN PEDIDO_ENTRADA_LINEA PEL ON PEL.ID_PEDIDO_ENTRADA_LINEA = MEL.ID_PEDIDO_LINEA
                         WHERE MEL.ID_MOVIMIENTO_ENTRADA = $rowMovimientoEntrada->ID_MOVIMIENTO_ENTRADA AND MEL.LINEA_ANULADA = 0 AND MEL.BAJA = 0 AND PEL.RELEVANTE_ENTREGA_ENTRANTE = 1";
        $resultLineas = $bd->ExecSQL($sqlLineas);

        $numLineasRelevantes = $bd->NumRegs($resultLineas);

        //SI TIENE LINEAS DE PEDIDO RELEVANTES, MANDAREMOS FOTO
        if ($numLineasRelevantes > 0):
            $relevanteParaEntregaEntrante = true;
        endif;

        //SI HAY QUE HACER LA RECEPCION POR ENTREGA ENTRANTE PREVIAMENTE DEBEREMOS MANDAR LA FOTO
        if ($relevanteParaEntregaEntrante == true):
            //ENVIO LA FOTO, DEVUELVE FALSE SI NO HAY NADA RELEVANTE
            $resultado = $sap->EnviarFotoMovimientoEntrada($rowMovimientoEntrada->ID_MOVIMIENTO_ENTRADA);

            //CONTRUYO EL ARRAR DE VUELTA EN FUNCION DEL RESULTADO
            if ($resultado == NULL): //NO SE HA REALIZADO LLAMADA POR NO HABER NADA QUE ENVIAR O ESTAR LOS WEB SERVICES DESHABILITADOS
                $arrDevuelto['ErrorFoto'] = false;
                $arrDevuelto['Resultado'] = $resultado;
            elseif ($resultado['RESULTADO'] == 'OK'): //LLAMDA CORRECTA, EXTRAIGO LAS ENTREGAS ENTRANTES
                $arrDevuelto['ErrorFoto']         = false;
                $arrDevuelto['Resultado']         = 'OK';
                $arrDevuelto['EntregasEntrantes'] = $resultado['ENTREGAS_ENTRANTES_DEVUELTAS'];
            else: //LLAMADA NO CORRECTA, GRABO LOS ERRORES
                foreach ($resultado['ERRORES'] as $arr):
                    foreach ($arr as $mensaje_error):
                        $strError = $strError . $mensaje_error . "<br>";
                    endforeach;
                endforeach;
                $arrDevuelto['ErrorFoto'] = true;
                $arrDevuelto['Errores']   = $strError;
                $arrDevuelto['Resultado'] = $resultado;
            endif;
        else:
            $arrDevuelto['ErrorFoto'] = false;
            $arrDevuelto['Resultado'] = NULL;
        endif;

        //FIN SI HAY QUE HACER LA RECEPCION POR ENTREGA ENTRANTE PREVIAMENTE DEBEREMOS MANDAR LA FOTO

        return $arrDevuelto;
    }

    /**
     * @param $idMovimientoEntrada MOVIMIENTO DE ENTRADA SOBRE EL QUE ANULAR LA FOTO ENTRANTE SI CORRESPONDE
     * @return array ARRAY DEVUELTO CON LOS VALORES OBTENIDOS DE LA LLAMADA
     */
    function anularFotoParaEntregasEntrantes($idMovimientoEntrada)
    {
        global $bd;
        global $sap;

        //ARRAY A DEVOLVER
        $arrDevuelto = array();

        //BUSCO EL MOVIMIENTO DE ENTRADA
        $rowMovimientoEntrada = $bd->VerReg("MOVIMIENTO_ENTRADA", "ID_MOVIMIENTO_ENTRADA", $idMovimientoEntrada);

        // BUSCO LA RECEPCION
        $rowDoc = $bd->VerReg("MOVIMIENTO_RECEPCION", "ID_MOVIMIENTO_RECEPCION", $rowMovimientoEntrada->ID_MOVIMIENTO_RECEPCION);

        //SI ESTA ASOCIADO A UNA ORDEN DE TRANSPORTE, COMPROBAMOS  QUE TIENE GASTOS
        /*if ($rowDoc->ID_ORDEN_TRANSPORTE != ""):
            //BUSCO LA ORDEN DE TRANSPORTE
            $rowOrdenTransporte = $bd->VerReg("ORDEN_TRANSPORTE", "ID_ORDEN_TRANSPORTE", $rowDoc->ID_ORDEN_TRANSPORTE);

            //SI TIENE GASTOS, LA FOTO SE ENVIA DESDE OTRO LUGAR
            if ($rowOrdenTransporte->TIENE_GASTOS_TRANSPORTE == 1):
                $arrDevuelto['ErrorFoto'] = false;
                $arrDevuelto['Resultado'] = NULL;

                return $arrDevuelto;
            endif;
        endif;*/
        //FIN SI ESTA ASOCIADO A UNA ORDEN DE TRANSPORTE, COMPROBAMOS  QUE TIENE GASTOS

        //COMPRUEBO SI YA TIENE FOTO ENVIADA
        $num = $bd->NumRegsTabla("EXPEDICION_ENTREGA_ENTRANTE", "ID_MOVIMIENTO_ENTRADA = $rowMovimientoEntrada->ID_MOVIMIENTO_ENTRADA");

        //SI NO TIENE FOTO ENVIADA SALGO DE LA FUNCION
        if ($num == 0):
            $arrDevuelto['ErrorFoto'] = false;
            $arrDevuelto['Resultado'] = NULL;

            return $arrDevuelto;
        endif;

        //ENVIO LA FOTO, DEVUELVE FALSE SI NO HAY NADA RELEVANTE
        $resultado = $sap->EnviarFotoMovimientoEntrada($rowMovimientoEntrada->ID_MOVIMIENTO_ENTRADA, "Si");

        //CONTRUYO EL ARRAR DE VUELTA EN FUNCION DEL RESULTADO
        if ($resultado == NULL): //NO SE HA REALIZADO LLAMADA POR NO HABER NADA QUE ENVIAR O ESTAR LOS WEB SERVICES DESHABILITADOS
            $arrDevuelto['ErrorFoto'] = false;
            $arrDevuelto['Resultado'] = $resultado;
        elseif ($resultado['RESULTADO'] == 'OK'): //LLAMDA CORRECTA, EXTRAIGO LAS ENTREGAS ENTRANTES
            $arrDevuelto['ErrorFoto']         = false;
            $arrDevuelto['Resultado']         = 'OK';
            $arrDevuelto['EntregasEntrantes'] = $resultado['ENTREGAS_ENTRANTES_DEVUELTAS'];
        else: //LLAMADA NO CORRECTA, GRABO LOS ERRORES
            foreach ($resultado['ERRORES'] as $arr):
                foreach ($arr as $mensaje_error):
                    $strError = $strError . $mensaje_error . "<br>";
                endforeach;
            endforeach;
            $arrDevuelto['ErrorFoto'] = true;
            $arrDevuelto['Errores']   = $strError;
            $arrDevuelto['Resultado'] = $resultado;
        endif;

        return $arrDevuelto;
    }

    /**
     * @param $idMovimientoEntrada MOVIMIENTO DE ENTRADA SOBRE EL QUE EXTRAER SU NUMERO DE ENTREGA ENTRANTE
     * @return string NUMERO DE ENTREGA ENTRANTE DEVUELTA, PUEDE SER VACIA SI NO HAY
     */
    function getEntregaEntrante($idMovimientoEntrada)
    {
        global $bd;

        //ENTREGAS ENTRANTES DEL MOVIMIENTO DE ENTRADA
        $arrEntregaEntrante = array();

        //BUSCO EL MOVIMIENTO DE ENTRADA
        $rowMovimientoEntrada = $bd->VerReg("MOVIMIENTO_ENTRADA", "ID_MOVIMIENTO_ENTRADA", $idMovimientoEntrada);

        //BUSCAMOS EL NUMERO DE ENTREGA ENTRANTE
        $sqlEntregaEntrante    = "SELECT NUMERO_ENTREGA_ENTRANTE
                                   FROM EXPEDICION_ENTREGA_ENTRANTE
                                   WHERE ID_MOVIMIENTO_ENTRADA = $rowMovimientoEntrada->ID_MOVIMIENTO_ENTRADA";
        $resultEntregaEntrante = $bd->ExecSQL($sqlEntregaEntrante);
        while ($rowEntregaEntrante = $bd->SigReg($resultEntregaEntrante)):
            $arrEntregaEntrante[] = $rowEntregaEntrante->NUMERO_ENTREGA_ENTRANTE;
        endwhile;

        //DEVUELVO EL ARRAY CON LAS ENTREGAS ENTRANTES
        return $arrEntregaEntrante;
    }

    /**
     * @param $idMovimientoEntrada MOVIMIENTO DE ENTRADA QUE PUEDE TENER RECOGIDAS EN PROVEEDOR
     * ACTUALIZA LOS ESTADOS DE LAS RECOGIDAS "con pedido conocido" y "sin pedido conocido" EN CASO DE QUE SE HAYAN RECEPCIONADO SUS LINEAS
     */
    function actualizarRecogidasEnProveedor($idMovimientoEntrada){
        global $bd;
        global $administrador;

        //BUSCO EL MOVIMIENTO DE ENTRADA
        $rowMovimientoEntrada = $bd->VerReg("MOVIMIENTO_ENTRADA", "ID_MOVIMIENTO_ENTRADA", $idMovimientoEntrada);


        $sqlRecogidasEntrega    = "SELECT DISTINCT E.ID_EXPEDICION, E.SUBTIPO_ORDEN_RECOGIDA
                            FROM MOVIMIENTO_ENTRADA_LINEA MEL
                            INNER JOIN EXPEDICION E ON E.ID_EXPEDICION = MEL.ID_EXPEDICION_ENTREGA
                            WHERE MEL.ID_MOVIMIENTO_ENTRADA = " . $rowMovimientoEntrada->ID_MOVIMIENTO_ENTRADA . " AND E.BAJA = 0";
        $resultRecogidasEntrega = $bd->ExecSQL($sqlRecogidasEntrega);


        //RECORREMOS LAS RECOGIDAS ASOCIADAS A LAS LINEAS DEL MOVIMIENTO
        while ($rowRecogidasEntrega = $bd->SigReg($resultRecogidasEntrega)):

            //SI SON SIN PEDIDO CONOCIDO
            if ($rowRecogidasEntrega->SUBTIPO_ORDEN_RECOGIDA == 'Sin Pedido Conocido'):

                //BUSCAMOS TODOS LOS MOVIMIENTOS ESTEN YA PROCESADOS
                $sqlMovimientosEntradaProcesados = "SELECT DISTINCT ME.ID_MOVIMIENTO_ENTRADA
                                                    FROM MOVIMIENTO_ENTRADA ME
                                                    INNER JOIN MOVIMIENTO_ENTRADA_LINEA MEL ON MEL.ID_MOVIMIENTO_ENTRADA = ME.ID_MOVIMIENTO_ENTRADA
                                                    WHERE MEL.ID_EXPEDICION_ENTREGA = $rowRecogidasEntrega->ID_EXPEDICION AND MEL.BAJA = 0 AND ME.ESTADO <> 'En Proceso'
                                                    UNION
                                                    SELECT ID_MOVIMIENTO_ENTRADA
                                                    FROM INCIDENCIA_CALIDAD
                                                    WHERE ID_MOVIMIENTO_ENTRADA IN (SELECT ID_MOVIMIENTO_ENTRADA 
                                                                                    FROM MOVIMIENTO_ENTRADA_LINEA
                                                                                    WHERE ID_EXPEDICION_ENTREGA = $rowRecogidasEntrega->ID_EXPEDICION AND BAJA = 0)";
                $resultMovimientosProcesados = $bd->ExecSQL($sqlMovimientosEntradaProcesados);

                //BUSCAMOS TODOS LOS MOVIMIENTOS
                $sqlMovimientosEntrada = "SELECT DISTINCT ME.ID_MOVIMIENTO_ENTRADA
                                          FROM MOVIMIENTO_ENTRADA ME
                                          INNER JOIN MOVIMIENTO_ENTRADA_LINEA MEL ON MEL.ID_MOVIMIENTO_ENTRADA = ME.ID_MOVIMIENTO_ENTRADA
                                          WHERE MEL.ID_EXPEDICION_ENTREGA = $rowRecogidasEntrega->ID_EXPEDICION AND MEL.BAJA = 0 
                                          UNION
                                          SELECT ID_MOVIMIENTO_ENTRADA
                                          FROM INCIDENCIA_CALIDAD
                                          WHERE ID_MOVIMIENTO_ENTRADA IN (SELECT ID_MOVIMIENTO_ENTRADA 
                                                                          FROM MOVIMIENTO_ENTRADA_LINEA
                                                                          WHERE ID_EXPEDICION_ENTREGA = $rowRecogidasEntrega->ID_EXPEDICION AND BAJA = 0)";
                $resultMovimientosEntrada = $bd->ExecSQL($sqlMovimientosEntrada);

                if (($bd->NumRegs($resultMovimientosProcesados) == $bd->NumRegs($resultMovimientosEntrada)) && ($bd->NumRegs($resultMovimientosEntrada) > 0)):
                    $sqlUpdate = "UPDATE EXPEDICION SET ESTADO = 'Recepcionada' WHERE ID_EXPEDICION = $rowRecogidasEntrega->ID_EXPEDICION";
                    $bd->ExecSQL($sqlUpdate);
                else:
                    $sqlUpdate = "UPDATE EXPEDICION SET ESTADO = 'Creada' WHERE ID_EXPEDICION = $rowRecogidasEntrega->ID_EXPEDICION";
                    $bd->ExecSQL($sqlUpdate);
                endif;

            //SI SON CON PEDIDO CONOCIDO, COMPROBAMOS SI ESTAN PROCESADOS TODOS LOS PEDIDOS IMPLICADOS
            elseif ($rowRecogidasEntrega->SUBTIPO_ORDEN_RECOGIDA == 'Con Pedido Conocido'):

                //BUSCAMOS QUE TODAS LAS LINEAS DE PEDIDO ESTEN RECEPCIONADAS
                $sqlPedidoConocidoSinRecepcionar = "SELECT SUM(EPC.CANTIDAD - EPC.CANTIDAD_NO_SERVIDA) AS CANTIDAD_PEDIDO_CONOCIDO, PEL.ID_PEDIDO_ENTRADA_LINEA
                                                FROM EXPEDICION_PEDIDO_CONOCIDO EPC
                                                INNER JOIN PEDIDO_ENTRADA_LINEA PEL ON PEL.ID_PEDIDO_ENTRADA_LINEA = EPC.ID_PEDIDO_ENTRADA_LINEA
                                                WHERE EPC.ID_EXPEDICION = $rowRecogidasEntrega->ID_EXPEDICION AND EPC.BAJA = 0 AND PEL.INDICADOR_BORRADO IS NULL
                                                GROUP BY PEL.ID_PEDIDO_ENTRADA_LINEA";

                $resultPedidoConocidoSinRecepcionar = $bd->ExecSQL($sqlPedidoConocidoSinRecepcionar);

                $recogidaFinalizada = true;
                //RECORREMOS LAS LINEAS DE PEDIDO CONOCIDO
                while ($rowPedidoConocidoSinRecepcionar = $bd->SigReg($resultPedidoConocidoSinRecepcionar)):
                    if ($rowPedidoConocidoSinRecepcionar->ID_PEDIDO_ENTRADA_LINEA != NULL):
                        //BUSCAMOS QUE LA CANTIDAD EN MOVIMIENTOS DE ESOS PEDIDOS CONOCIDOS ESTE RECEPCIONADA
                        $sqlMovimientosEntrada    = "SELECT SUM(MEL.CANTIDAD) AS CANTIDAD_RECEPCIONADA
                                                    FROM MOVIMIENTO_ENTRADA_LINEA MEL
                                                    INNER JOIN MOVIMIENTO_ENTRADA ME ON ME.ID_MOVIMIENTO_ENTRADA = MEL.ID_MOVIMIENTO_ENTRADA
                                                    WHERE MEL.ID_PEDIDO_LINEA = $rowPedidoConocidoSinRecepcionar->ID_PEDIDO_ENTRADA_LINEA AND ME.ESTADO <>'En Proceso' AND MEL.BAJA = 0 AND MEL.LINEA_ANULADA = 0
                                                    GROUP BY MEL.ID_PEDIDO_LINEA";
                        $resultMovimientosEntrada = $bd->ExecSQL($sqlMovimientosEntrada);

                        $rowMovimientosEntrada = $bd->SigReg($resultMovimientosEntrada);

                        if ($rowMovimientosEntrada == false):
                            $recogidaFinalizada = false;
                        else:
                            if ($rowPedidoConocidoSinRecepcionar->CANTIDAD_PEDIDO_CONOCIDO > $rowMovimientosEntrada->CANTIDAD_RECEPCIONADA):
                                $recogidaFinalizada = false;
                            endif;
                        endif;
                    endif;
                endwhile;


                //SI TODAS ESTAN RECEPCIONADAS, ACTUALIZAMOS LA RECOGIDA A RECEPCIONADA
                if ($recogidaFinalizada):
                    $sqlUpdate = "UPDATE EXPEDICION SET ESTADO = 'Recepcionada' WHERE ID_EXPEDICION = $rowRecogidasEntrega->ID_EXPEDICION";
                    $bd->ExecSQL($sqlUpdate);
                else:
                    $sqlUpdate = "UPDATE EXPEDICION SET ESTADO = 'Transmitida a SAP' WHERE ID_EXPEDICION = $rowRecogidasEntrega->ID_EXPEDICION";
                    $bd->ExecSQL($sqlUpdate);
                endif;

            endif;

        endwhile;
        //FIN RECORREMOS LAS RECOGIDAS ASOCIADAS A LAS LINEAS DEL MOVIMIENTO
    }

    /**
     * @param $idMovimientoRecepcion
     * LIBERA CANTIDADES DE PEDIDOS CONOCIDOS UNA VEZ QUE LA RECEPCION SE PROCESA MANDANDO SPLIT SI ES NECESARIO
     */
    function liberarCantidadPedidoConocido($idMovimientoRecepcion, $mostrar_error = "")
    {

        global $bd;
        global $administrador;
        global $html;
        global $sap;
        global $pedido;
        global $strError;

        //OBTENGO LA RECEPCION MODIFICADA
        $rowDoc = $bd->VerReg("MOVIMIENTO_RECEPCION", "ID_MOVIMIENTO_RECEPCION", $idMovimientoRecepcion);

        //OBTENGO EL ESTADO FINAL DE LA RECEPCION TRAS LOS CAMBIOS
        $estadoFinalRecepcion = $rowDoc->ESTADO;

        //ARRAY PARA GUARDAR LAS LINEAS PARA LANZAR SPLIT SI FUESE NECESARIO
        $arrayLineasNecesitanSplit = array();
        $arrayLineasConSplit       = array();
        $arr_error                 = array();

        //ARRAY PARA MANDAR A SAP EL DESBLOQUEO DE LAS LINEAS DE PEDIDO
        $arrBloqueoLineas = array();

        //SI LA RECEPCION SE HA PROCESADO Y ESTA ASOCIADA A UNA ORDEN DE TRANSPORTE, LIBERAMOS LAS CANTIDADES DE LOS PEDIDOS CONOCIDOS SOBRANTES
        if ($rowDoc->ID_ORDEN_TRANSPORTE != NULL):

            //SI EL ESTADO NO ES EN PROCESO, LIBERAMOS
            if ($estadoFinalRecepcion != "En Proceso"):

                //BUSCAMOS LOS PEDIDOS AFECTADOS CON DESTINO ESE CENTRO FISICO
                $sqlPedidosConocidos    = "SELECT SUM(EPC.CANTIDAD - EPC.CANTIDAD_NO_SERVIDA) AS CANTIDAD_CONOCIDO, EPC.ID_PEDIDO_ENTRADA_LINEA , PEL.CANTIDAD, PEL.CANTIDAD_PDTE, PEL.RELEVANTE_ENTREGA_ENTRANTE
                                                FROM EXPEDICION_PEDIDO_CONOCIDO EPC
                                                INNER JOIN EXPEDICION E ON E.ID_EXPEDICION = EPC.ID_EXPEDICION
                                                INNER JOIN PEDIDO_ENTRADA_LINEA PEL ON PEL.ID_PEDIDO_ENTRADA_LINEA=EPC.ID_PEDIDO_ENTRADA_LINEA
                                                INNER JOIN ALMACEN A ON A.ID_ALMACEN = PEL.ID_ALMACEN
                                                WHERE E.ID_ORDEN_TRANSPORTE = $rowDoc->ID_ORDEN_TRANSPORTE AND EPC.BAJA = 0 AND E.BAJA = 0 AND A.ID_CENTRO_FISICO = $rowDoc->ID_CENTRO_FISICO
                                                GROUP BY EPC.ID_PEDIDO_ENTRADA_LINEA";
                $resultPedidosConocidos = $bd->ExecSQL($sqlPedidosConocidos);

                //RECORREMOS LOS PEDIDOS CONOCIDOS CON DESTINO ESE CENTRO FISICO
                while ($rowPedidosConocidos = $bd->SigReg($resultPedidosConocidos)):
                    if ($rowPedidosConocidos->ID_PEDIDO_ENTRADA_LINEA != NULL):
                        //AÑADO LA LINEA AL ARRAY DE LINEAS A ENVIAR EL DESBLOQUEO A SAP
                        $arrBloqueoLineas[] = $rowPedidosConocidos->ID_PEDIDO_ENTRADA_LINEA;

                        //BUSCAMOS LA CANTIDAD RECEPCIONADA
                        $sqlCantidadRecepcionada    = " SELECT SUM(MEL.CANTIDAD) AS CANTIDAD_RECEPCIONADA
                                                        FROM MOVIMIENTO_ENTRADA ME
                                                        INNER JOIN MOVIMIENTO_ENTRADA_LINEA MEL ON MEL.ID_MOVIMIENTO_ENTRADA = ME.ID_MOVIMIENTO_ENTRADA
                                                        WHERE ME.ID_MOVIMIENTO_RECEPCION = $rowDoc->ID_MOVIMIENTO_RECEPCION AND MEL.ID_PEDIDO_LINEA = $rowPedidosConocidos->ID_PEDIDO_ENTRADA_LINEA  AND ME.ESTADO <> 'En Proceso' AND MEL.BAJA = 0 AND MEL.LINEA_ANULADA = 0 AND ME.BAJA = 0";
                        $resultCantidadRecepcionada = $bd->ExecSQL($sqlCantidadRecepcionada);

                        $rowCantidadRecepcionada = $bd->SigReg($resultCantidadRecepcionada);

                        if (($rowCantidadRecepcionada != false) && ($rowCantidadRecepcionada->CANTIDAD_RECEPCIONADA != NULL)):
                            $cantidadRecepcionada = $rowCantidadRecepcionada->CANTIDAD_RECEPCIONADA;
                        else:
                            $cantidadRecepcionada = 0;
                        endif;

                        //SI ES RELEVANTE Y NO SE HA RECEPCIONADO TODA LA CANTIDAD CONOCIDO NOS LA GUARDAMOS COMO LINEA QUE NECESITA SPLIT
                        if (($rowPedidosConocidos->RELEVANTE_ENTREGA_ENTRANTE == 1) && ($cantidadRecepcionada < $rowPedidosConocidos->CANTIDAD_CONOCIDO)):
                            $arrayLineasNecesitanSplit[$rowPedidosConocidos->ID_PEDIDO_ENTRADA_LINEA] = ($rowPedidosConocidos->CANTIDAD - $rowPedidosConocidos->CANTIDAD_PDTE);
                        endif;

                        //SI SE HA RECEPCIONADO MENOS CANTIDAD QUE LA ASIGNADA A PEDIDO CONOCIDO
                        if ($cantidadRecepcionada < $rowPedidosConocidos->CANTIDAD_CONOCIDO):
                            //CALCULAMOS LA CANTIDAD NO SERVIDA
                            $cantidadNoServida = $rowPedidosConocidos->CANTIDAD_CONOCIDO - $cantidadRecepcionada;

                            //ACTUALIZAMOS LA CANTIDAD ASOCIADA A ORDENES DE RECOGIDA
                            $sqlUpdate = "UPDATE PEDIDO_ENTRADA_LINEA SET CANTIDAD_ASIGNADA_ORDENES_RECOGIDA = $cantidadRecepcionada WHERE ID_PEDIDO_ENTRADA_LINEA =  $rowPedidosConocidos->ID_PEDIDO_ENTRADA_LINEA";
                            $bd->ExecSQL($sqlUpdate);

                            //BUSCAMOS LOS PEDIDOS CONOCIDOS AFECTADOS
                            $sqlEPC    = "SELECT DISTINCT EPC.ID_EXPEDICION_PEDIDO_CONOCIDO, EPC.CANTIDAD, EPC.ID_EXPEDICION
                                          FROM EXPEDICION_PEDIDO_CONOCIDO EPC
                                          INNER JOIN EXPEDICION E ON E.ID_EXPEDICION = EPC.ID_EXPEDICION
                                          WHERE EPC.BAJA = 0 AND E.ID_ORDEN_TRANSPORTE = $rowDoc->ID_ORDEN_TRANSPORTE AND EPC.ID_PEDIDO_ENTRADA_LINEA = $rowPedidosConocidos->ID_PEDIDO_ENTRADA_LINEA";
                            $resultEPC = $bd->ExecSQL($sqlEPC);

                            //RECORREMOS LOS PEDIDOS CONOCIDOS HASTA ASIGNARLES TODA LA CANTIDAD NO SERVIDA
                            while (($cantidadNoServida > 0) && ($rowEPC = $bd->SigReg($resultEPC))):

                                //CALCULAMOS LA CANTIDAD A RESTAR DE ESTA LINEA
                                $cantidadARestar = ($cantidadNoServida > $rowEPC->CANTIDAD ? $rowEPC->CANTIDAD : $cantidadNoServida);

                                $sqlUpdate = "UPDATE EXPEDICION_PEDIDO_CONOCIDO SET CANTIDAD_NO_SERVIDA = CANTIDAD_NO_SERVIDA + $cantidadARestar WHERE ID_EXPEDICION_PEDIDO_CONOCIDO = $rowEPC->ID_EXPEDICION_PEDIDO_CONOCIDO";
                                $bd->ExecSQL($sqlUpdate);

                                //RESTAMOS LA CANTIDAD NO SERVIDA DE LA YA ASIGNADA A LA LINEA
                                $cantidadNoServida = $cantidadNoServida - $cantidadARestar;

                                //SI LA CANTIDAD SE QUEDA A 0, LO DAMOS DE BAJA Y LO GUARDAMOS EN LA TABLA DE TRANSPORTES ANULADOS
                                $rowEPCActualizada = $bd->VerReg("EXPEDICION_PEDIDO_CONOCIDO", "ID_EXPEDICION_PEDIDO_CONOCIDO", $rowEPC->ID_EXPEDICION_PEDIDO_CONOCIDO);

                                if ($rowEPCActualizada->CANTIDAD_NO_SERVIDA == $rowEPCActualizada->CANTIDAD):
                                    //DAMOS DE BAJA
                                    $sqlUpdate = "UPDATE EXPEDICION_PEDIDO_CONOCIDO SET BAJA = 1 WHERE ID_EXPEDICION_PEDIDO_CONOCIDO = $rowEPC->ID_EXPEDICION_PEDIDO_CONOCIDO";
                                    $bd->ExecSQL($sqlUpdate);

                                    //GUARDAMOS EL REGISTRO
                                    $sqlInsert = "INSERT INTO ORDEN_TRANSPORTE_LINEA_ANULADA SET
                                                      ID_ORDEN_TRANSPORTE = $rowDoc->ID_ORDEN_TRANSPORTE
                                                    , ID_EXPEDICION = $rowEPC->ID_EXPEDICION
                                                    , ID_EXPEDICION_PEDIDO_CONOCIDO = $rowEPC->ID_EXPEDICION_PEDIDO_CONOCIDO";
                                    $bd->ExecSQL($sqlInsert);
                                endif;
                            endwhile;
                        endif;
                    endif;
                endwhile;//FIN RECORREMOS LOS PEDIDOS CONOCIDOS CON DESTINO ESE CENTRO FISICO
            endif;//FIN ESTADO FINAL

        endif;//FIN, TIENE ORDEN DE TRANSPORTE

        //LNAZAMOS SPLIT SI ES NECESARIO
        if (count( (array)$arrayLineasNecesitanSplit) > 0):
            $splitNecesario = true;

            //DESBLOQUEAMOS LA LINEA
            $arrLineas = array();
            foreach ($arrayLineasNecesitanSplit as $idLinea => $cantidadLinea):    //BUCLE LINEA DE PEDIDO

                //BUSCO EL PEDIDO DE ENTRADA
                $rowLinea = $bd->VerReg("PEDIDO_ENTRADA_LINEA", "ID_PEDIDO_ENTRADA_LINEA", $idLinea);
                $rowPed   = $bd->VerReg("PEDIDO_ENTRADA", "ID_PEDIDO_ENTRADA", $rowLinea->ID_PEDIDO_ENTRADA);

                unset($objLinea);    //VACIO EL OBJETO LINEA
                $objLinea            = new stdClass;
                $objLinea->PEDIDO    = $rowPed->PEDIDO_SAP;    // Número de pedido cuyas posiciones vamos a bloquear (Obligatorio)
                $objLinea->POSICION  = $rowLinea->LINEA_PEDIDO_SAP;    // Línea de pedido a bloquear (Obligatorio)
                $objLinea->BLOQUEADO = '';    // (vacio=NO, X=SI)

                $arrLineas[] = $objLinea;
            endforeach;

            //INFORMO A SAP DEL DESBLOQUEO (SI FALLA ROLLBACK Y PANTALLA DE ERROR)
            $resultadoDesbloqueo = $sap->bloqueoLineasPedido($arrLineas);
            if ($resultadoDesbloqueo['RESULTADO'] != 'OK'): //No se ha podido grabar la información en SAP
                if (count( (array)$resultadoDesbloqueo['ERRORES']) > 0):
                    foreach ($resultadoDesbloqueo['ERRORES'] as $arr):
                        foreach ($arr as $mensaje_error):
                            $strError = $strError . $mensaje_error . "<br>";
                        endforeach;
                    endforeach;
                endif;

                //DESHAGO LA TRANSACCION
                $bd->rollback_transaction();

                //GRABO EL ERROR PRODUCIDO EN BASE DE DATOS
                $sap->InsertarErrores($resultadoDesbloqueo);

                if ($mostrar_error == 'texto'):
                    $arr_error['ERROR'] = "We have received the following errors from SAP/Se han producido los siguientes errores en el intercambio de información con SAP" . ": " . '<br>' . $strError;

                    return $arr_error;
                else:
                    $html->PagError("ErrorSAP");
                endif;
            endif;
            //FIN DESBLOQUEAMOS LA LINEA

            //HAGO LA LLAMADA A SAP PARA INDICARLE EL SPLIT QUE TIENE QUE REALIZAR (SI FALLA ROLLBACK Y GUARDAMOS TANTO ESTA LLAMADA COMO LA DE DESBLOQUEO, PERO NO MOSTRAMOS PANTALLA DE ERROR, SEGUIMOS PARA MANDAR EL BLOQUEO)
            $resultadoSplit = $sap->SplitPedido($arrayLineasNecesitanSplit, "Entrada");
            if ($resultadoSplit['RESULTADO'] != 'OK'): //No se ha podido grabar la información en SAP
                $hayErrorSplit = true;
                foreach ($resultadoSplit['ERRORES'] as $arr):
                    foreach ($arr as $mensaje_error):
                        $strError = $strError . $mensaje_error . "<br>";
                    endforeach;
                endforeach;

                //DESHAGO LA TRANSACCION
                $bd->rollback_transaction();

                //GRABO LA LLAMADA OK A DESBLOQUEO QUE NO HABIAMOS GRABADO
                $sap->InsertarErrores($resultadoDesbloqueo);

                //GRABO EL ERROR PRODUCIDO EN BASE DE DATOS
                $sap->InsertarErrores($resultadoSplit);

            else:
                // DIFERENCIO ENTRE PEDIDOS CON SPLIT CORRECTO (S) E INCORRECTO (E)
                $arrayLineasConSplit = $arrayLineasNecesitanSplit;

                foreach ($arrayLineasConSplit as $idLinea => $cantidadLinea):
                    // OBTENGO LOS DATOS DE LA LÍNEA Y DEL PEDIDO
                    $rowPedidoLinea = $bd->VerReg("PEDIDO_ENTRADA_LINEA", "ID_PEDIDO_ENTRADA_LINEA", $idLinea);
                    $rowPedido      = $bd->VerReg("PEDIDO_ENTRADA", "ID_PEDIDO_ENTRADA", $rowPedidoLinea->ID_PEDIDO_ENTRADA);

                    // DIFERENCIO ENTRE PEDIDOS CON SPLIT CORRECTO (S) E INCORRECTO (E)
                    if (!in_array($rowPedido->PEDIDO_SAP, (array) $resultadoSplit['SPLIT_PEDIDOS_CORRECTOS'])):
                        unset($arrayLineasConSplit[$idLinea]);
                    endif;
                endforeach;

                if (count( (array)$resultadoSplit['ERRORES']) > 0):
                    foreach ($resultadoSplit['ERRORES'] as $arr):
                        foreach ($arr as $mensaje_error):
                            $strError = $strError . $mensaje_error . "<br>";
                        endforeach;
                    endforeach;
                endif;
            endif;// FIN HAGO LLAMADA SPLIT

            //BLOQUEAMOS LAS LINEAS DE NUEVO
            //SI NO HA HECHO FALTA SPLIT, SOLO SE MANDA DESBLOQUEO, SI HA HECHO FALTA MANDAMOS LO QUE SEA NECESARIO
            $resultado = $pedido->controlBloqueoLinea("Entrada", 'SplitLineas', implode(",", (array) $arrayLineasNecesitanSplit));
            if ($resultado['RESULTADO'] != 'OK'): //No se ha podido grabar la información en SAP
                if (count( (array)$resultado['ERRORES']) > 0):
                    foreach ($resultado['ERRORES'] as $arr):
                        foreach ($arr as $mensaje_error):
                            $strError = $strError . $mensaje_error . "<br>";
                        endforeach;
                    endforeach;
                endif;

                $bd->rollback_transaction();

                //GRABO EL ERROR PRODUCIDO EN BASE DE DATOS
                $sap->InsertarErrores($resultado);
                if ($mostrar_error == 'texto'):
                    $arr_error['ERROR'] = "We have received the following errors from SAP/Se han producido los siguientes errores en el intercambio de información con SAP" . ": " . '<br>' . $strError;

                    return $arr_error;
                else:
                    $html->PagError("ErrorSAP");
                endif;
            endif;

            //MOSTRAMOS SI HA HABIDO ERROR EN EL SPLIT
            if ($hayErrorSplit == true):
                if ($mostrar_error == 'texto'):
                    $arr_error['ERROR'] = "We have received the following errors from SAP/Se han producido los siguientes errores en el intercambio de información con SAP" . ": " . '<br>' . $strError;

                    return $arr_error;
                else:
                    $html->PagError("ErrorSAP");
                endif;
            endif;

        endif;
        //FIN SI HEMOS ENCONTRADO LINEAS PARA HACER SPLIT , LO LANZAMOS

        //MANDO EL BLOQUEO DE LINEAS A SAP PARA QUE NO LAS MODIFIQUE
        if (count((array) $arrBloqueoLineas) > 0):
            $resultadoBloqueo = $pedido->controlBloqueoLinea("Entrada", 'InsertarLinea', implode(", ", (array) $arrBloqueoLineas));
            if ($resultadoBloqueo['RESULTADO'] != 'OK'): //No se ha podido grabar la información en SAP
                if (count( (array)$resultadoBloqueo['ERRORES']) > 0):
                    foreach ($resultadoBloqueo['ERRORES'] as $arrayDeErrores):
                        foreach ($arrayDeErrores as $mensaje_error):
                            $strError = $strError . $mensaje_error . "<br>";
                        endforeach;
                    endforeach;
                endif;

                $bd->rollback_transaction();

                //GRABO EL ERROR PRODUCIDO EN BASE DE DATOS
                $sap->InsertarErrores($resultado);
                if ($mostrar_error == 'texto'):
                    $arr_error['ERROR'] = "We have received the following errors from SAP/Se han producido los siguientes errores en el intercambio de información con SAP" . ": " . '<br>' . $strError;

                    return $arr_error;
                else:
                    $html->PagError("ErrorSAP");
                endif;
            endif;
        endif;

        //DEVUELVO EL ARRAY DE LINEAS SOBRE LAS QUE SE HA REALIZADO SPLIT
        return $arrayLineasConSplit;
    }

    /**
     * @param $idMovimientoRecepcion
     * LOCALIZA LAS LINEAS QUE ESTABAN PREVISTAS RECEPCIONAR Y NO SE RECEPCIONAN FINALMENTE
     */
    function lineasNoRecepcionadas($idMovimientoRecepcion)
    {

        global $bd;
        global $administrador;
        global $html;
        global $sap;
        global $pedido;
        global $strError;

        //OBTENGO LA RECEPCION MODIFICADA
        $rowDoc = $bd->VerReg("MOVIMIENTO_RECEPCION", "ID_MOVIMIENTO_RECEPCION", $idMovimientoRecepcion);

        //OBTENGO EL ESTADO FINAL DE LA RECEPCION TRAS LOS CAMBIOS
        $estadoFinalRecepcion = $rowDoc->ESTADO;

        //ARRAY PARA GUARDAR LAS LINEAS NO RECEPCIONADAS
        $arrayLineasNoRecepcionadas = array();

        //SI LA RECEPCION SE HA PROCESADO Y ESTA ASOCIADA A UNA ORDEN DE TRANSPORTE, LOCALIZO LINEAS QUE ESTABAN PREVISTAS RECEPCIONAR Y NO SE RECEPCIONAN FINALMENTE
        if ($rowDoc->ID_ORDEN_TRANSPORTE != NULL):

            //SI EL ESTADO NO ES EN PROCESO, LIBERAMOS
            if ($estadoFinalRecepcion != "En Proceso"):

                //BUSCAMOS LOS PEDIDOS AFECTADOS CON DESTINO ESE CENTRO FISICO
                $sqlPedidosConocidos    = "SELECT SUM(EPC.CANTIDAD - EPC.CANTIDAD_NO_SERVIDA) AS CANTIDAD_CONOCIDO, EPC.ID_PEDIDO_ENTRADA_LINEA , PEL.CANTIDAD, PEL.CANTIDAD_PDTE, PEL.RELEVANTE_ENTREGA_ENTRANTE
                                            FROM EXPEDICION_PEDIDO_CONOCIDO EPC
                                            INNER JOIN EXPEDICION E ON E.ID_EXPEDICION = EPC.ID_EXPEDICION
                                            INNER JOIN PEDIDO_ENTRADA_LINEA PEL ON PEL.ID_PEDIDO_ENTRADA_LINEA=EPC.ID_PEDIDO_ENTRADA_LINEA
                                            INNER JOIN ALMACEN A ON A.ID_ALMACEN = PEL.ID_ALMACEN
                                            WHERE E.ID_ORDEN_TRANSPORTE = $rowDoc->ID_ORDEN_TRANSPORTE AND EPC.BAJA = 0 AND E.BAJA = 0 AND A.ID_CENTRO_FISICO = $rowDoc->ID_CENTRO_FISICO
                                            GROUP BY EPC.ID_PEDIDO_ENTRADA_LINEA";
                $resultPedidosConocidos = $bd->ExecSQL($sqlPedidosConocidos);

                //RECORREMOS LOS PEDIDOS CONOCIDOS CON DESTINO ESE CENTRO FISICO
                while ($rowPedidosConocidos = $bd->SigReg($resultPedidosConocidos)):
                    if ($rowPedidosConocidos->ID_PEDIDO_ENTRADA_LINEA != NULL):
                        //BUSCAMOS LA CANTIDAD RECEPCIONADA
                        $sqlCantidadRecepcionada    = " SELECT SUM(MEL.CANTIDAD) AS CANTIDAD_RECEPCIONADA
                                                        FROM MOVIMIENTO_ENTRADA ME
                                                        INNER JOIN MOVIMIENTO_ENTRADA_LINEA MEL ON MEL.ID_MOVIMIENTO_ENTRADA = ME.ID_MOVIMIENTO_ENTRADA
                                                        WHERE ME.ID_MOVIMIENTO_RECEPCION = $rowDoc->ID_MOVIMIENTO_RECEPCION AND MEL.ID_PEDIDO_LINEA = $rowPedidosConocidos->ID_PEDIDO_ENTRADA_LINEA  AND ME.ESTADO <> 'En Proceso' AND MEL.BAJA = 0 AND MEL.LINEA_ANULADA = 0 AND ME.BAJA = 0";
                        $resultCantidadRecepcionada = $bd->ExecSQL($sqlCantidadRecepcionada);

                        $rowCantidadRecepcionada = $bd->SigReg($resultCantidadRecepcionada);

                        if (($rowCantidadRecepcionada != false) && ($rowCantidadRecepcionada->CANTIDAD_RECEPCIONADA != NULL)):
                            $cantidadRecepcionada = $rowCantidadRecepcionada->CANTIDAD_RECEPCIONADA;
                        else:
                            $cantidadRecepcionada = 0;
                        endif;

                        //SI LA CANTIDAD RECEPCIONADA ES CERO AÑADO LA LINEA AL ARRAY A DEVOLVER
                        if ($cantidadRecepcionada == 0):
                            $arrayLineasNoRecepcionadas[] = $rowPedidosConocidos->ID_PEDIDO_ENTRADA_LINEA;
                        endif;
                    endif;
                endwhile;//FIN RECORREMOS LOS PEDIDOS CONOCIDOS CON DESTINO ESE CENTRO FISICO
            endif;//FIN ESTADO FINAL

        endif;
        //FIN SI LA RECEPCION SE HA PROCESADO Y ESTA ASOCIADA A UNA ORDEN DE TRANSPORTE, LOCALIZO LINEAS QUE ESTABAN PREVISTAS RECEPCIONAR Y NO SE RECEPCIONAN FINALMENTE

        //DEVUELVO EL ARRAY DE LINEAS QUE ESTABAN PREVISTAS RECEPCIONAR Y NO SE RECEPCIONAN FINALMENTE
        return $arrayLineasNoRecepcionadas;
    }

    /**
     * @param $idMovimientoSalidaLinea
     * ACTUALIZA EL ESTADO DE LA LINEA DEL PEDIDO DEL QUE PROCEDE LA LINEA DEL MOVIMIENTO DE SALIDA
     */
    function actualizarEstadoLineaPedidoSalida($idMovimientoSalidaLinea)
    {
        global $bd;

        //BUSCO LA LINEA DEL MOVIMIENTO DE SALIDA
        $rowMovimientoSalidaLinea = $bd->VerReg("MOVIMIENTO_SALIDA_LINEA", "ID_MOVIMIENTO_SALIDA_LINEA", $idMovimientoSalidaLinea);

        //CALCULO LA CANTIDAD EXPEDIDA
        $cantidadExpedida       = 0;
        $sqlCantidadExpedida    = "SELECT SUM(CANTIDAD) AS CANTIDAD_EXPEDIDA
                                FROM MOVIMIENTO_SALIDA_LINEA MSL
                                WHERE MSL.BAJA = 0 AND MSL.LINEA_ANULADA = 0 AND MSL.ID_PEDIDO_SALIDA_LINEA = $rowMovimientoSalidaLinea->ID_PEDIDO_SALIDA_LINEA AND MSL.ESTADO IN ('Expedido', 'En Transito', 'Recepcionado')";
        $resultCantidadExpedida = $bd->ExecSQL($sqlCantidadExpedida);
        if (($resultCantidadExpedida != false) && ($bd->NumRegs($resultCantidadExpedida) > 0)):
            $rowCantidadExpedida = $bd->SigReg($resultCantidadExpedida);
            $cantidadExpedida    = $rowCantidadExpedida->CANTIDAD_EXPEDIDA;
        endif;

        //BUSCO LA LINEA DEL PEDIDO DE SALIDA
        $rowPedidoSalidaLinea = $bd->VerReg("PEDIDO_SALIDA_LINEA", "ID_PEDIDO_SALIDA_LINEA", $rowMovimientoSalidaLinea->ID_PEDIDO_SALIDA_LINEA);

        //EN FUNCION DE LA CANTIDAD EXPEDIDA Y DE LA CANTIDAD DE LA LINEA DEL PEDIDO, ACTUALIZO EL ESTADO DE LA LINEA DE PEDIDO
        if ($cantidadExpedida >= $rowPedidoSalidaLinea->CANTIDAD):
            $sqlUpdate = "UPDATE PEDIDO_SALIDA_LINEA SET ESTADO = 'Finalizada' WHERE ID_PEDIDO_SALIDA_LINEA = $rowPedidoSalidaLinea->ID_PEDIDO_SALIDA_LINEA";
        elseif ($cantidadExpedida > 0):
            $sqlUpdate = "UPDATE PEDIDO_SALIDA_LINEA SET ESTADO = 'En Entrega' WHERE ID_PEDIDO_SALIDA_LINEA = $rowPedidoSalidaLinea->ID_PEDIDO_SALIDA_LINEA";
        else:
            $sqlUpdate = "UPDATE PEDIDO_SALIDA_LINEA SET ESTADO = 'Grabada' WHERE ID_PEDIDO_SALIDA_LINEA = $rowPedidoSalidaLinea->ID_PEDIDO_SALIDA_LINEA";
        endif;
        $bd->ExecSQL($sqlUpdate);
    }

    /**
     * @param $idMovimientoSalida
     * ACTUALIZA EL ESTADO DE LA LINEA DE LA NECESIDAD ASOCIADA AL MOVIMIENTO DE SALIDA
     */
    function actualizarEstadoLineaNecesidad($idMovimientoSalida)
    {
        global $bd;

        //BUSCO EL MOVIMIENTO DE SALIDA
        $rowMovimientoSalida = $bd->VerReg("MOVIMIENTO_SALIDA", "ID_MOVIMIENTO_SALIDA", $idMovimientoSalida);

        //BUSCO LAS LINEAS DEL MOVIMIENTO DE SALIDA NO DADAS DE BAJA NI ANULADAS
        $sqlLineas    = "SELECT *
                        FROM MOVIMIENTO_SALIDA_LINEA MSL
                        WHERE MSL.ID_MOVIMIENTO_SALIDA = $rowMovimientoSalida->ID_MOVIMIENTO_SALIDA AND LINEA_ANULADA = 0 AND BAJA = 0";
        $resultLineas = $bd->ExecSQL($sqlLineas);
        while ($rowLinea = $bd->SigReg($resultLineas)):
            $sqlUpdate = "UPDATE NECESIDAD_LINEA SET ESTADO = '" . $rowLinea->ESTADO . "' WHERE ID_MOVIMIENTO_SALIDA_LINEA = $rowLinea->ID_MOVIMIENTO_SALIDA_LINEA";
            $bd->ExecSQL($sqlUpdate);
        endwhile;
    }


    /**
     * @param $idMovimientoEntrada MOVIMIENTO DE ENTRADA QUE PUEDE CONTENER MATERIALES QUE MOVER A LA INSTALACION
     * GENERA LOS MOVIMIENTOS NECESARIO PARA PASAR DE LA UBICACION DE CONSOLIDACION A LA UBICACION FINAL
     */
    function moverMaterialSuministroDirectoConstruccion($idMovimientoEntrada)
    {

        global $bd;
        global $administrador;
        global $html;
        global $auxiliar;
        global $pedido;
        global $importe;
        global $expedicion;
        global $sap;
        global $exp_SAP;
        global $albaran;
        global $orden_trabajo;

        //BUSCO EL MOVIMIENTO DE ENTRADA
        $rowMovimientoEntrada = $bd->VerReg("MOVIMIENTO_ENTRADA", "ID_MOVIMIENTO_ENTRADA", $idMovimientoEntrada);

        //BUSCAMOS RECEPCION
        $rowMovimientoRecepcion = $bd->VerReg("MOVIMIENTO_RECEPCION", "ID_MOVIMIENTO_RECEPCION", $rowMovimientoEntrada->ID_MOVIMIENTO_RECEPCION);

        //COMPROBAMOS QUE EL MOVIMIENTO ES DE TIPO CONSTRUCCION
        $html->PagErrorCondicionado($rowMovimientoEntrada->TIPO_MOVIMIENTO, "!=", "Construccion", "TipoMovimientoIncorrecto");
        $html->PagErrorCondicionado($rowMovimientoEntrada->ESTADO, "==", "En Proceso", "AccionEnEstadoIncorrecto");


        //BUSCO LAS LINEAS QUE HAYAN ACABADO EN UN ALMACEN DISTINTO AL DEL PEDIDO (AGRUPADOS POR MATERIAL Y UOP DESTINO)
        $sqlLineas    = "SELECT MEL.ID_MOVIMIENTO_ENTRADA_LINEA, MEL.ID_UBICACION, MEL.ID_UBICACION_SUMINISTRO_DIRECTO, MEL.ID_MATERIAL, SUM(MEL.CANTIDAD) AS CANTIDAD_A_TRASLADAR
                        FROM MOVIMIENTO_ENTRADA_LINEA MEL
                        WHERE MEL.ID_MOVIMIENTO_ENTRADA = " . $rowMovimientoEntrada->ID_MOVIMIENTO_ENTRADA . " AND MEL.ID_UBICACION_SUMINISTRO_DIRECTO IS NOT NULL AND MEL.LINEA_ANULADA = 0 AND MEL.BAJA = 0
                        GROUP BY MEL.ID_MATERIAL, MEL.ID_UBICACION_SUMINISTRO_DIRECTO ";
        $resultLineas = $bd->ExecSQL($sqlLineas);

        //VARIABLES
        $arrError = array();

        //ARRAY PARA GUARDAR LAS LINEAS QUE TENGO QUE PREPARAR
        $arrPedidoLinea = array();

        //ARRAY DE LOS MEL A REUBICAR
        $arrEntradas = array();

        //DECLARO LA FECHA A TRANSMITIR A SAP
        $txFechaHora = date("Y-m-d H:i:s");

        //RECORREMOS LOS MATERIALES A TRASLADAR
        while ($rowLinea = $bd->SigReg($resultLineas)):

            //CANTIDAD A TRASLADAR
            $cantidadTrasladar = $rowLinea->CANTIDAD_A_TRASLADAR;

            //BUSCAMOS LISTAS DE MATERIALES PARA EL MATERIAL Y UOP
            $sqlListasMateriales    = "SELECT PSL.ID_PEDIDO_SALIDA_LINEA, PSL.ID_PEDIDO_SALIDA, PSL.CANTIDAD_PENDIENTE_SERVIR, PSL.ID_MATERIAL
                                        FROM ORDEN_MONTAJE_LINEA OML
                                        INNER JOIN ORDEN_MONTAJE OM ON OM.ID_ORDEN_MONTAJE = OML.ID_ORDEN_MONTAJE
                                        INNER JOIN PEDIDO_SALIDA_LINEA PSL ON PSL.ID_ORDEN_MONTAJE_LINEA = OML.ID_ORDEN_MONTAJE_LINEA
                                        INNER JOIN PEDIDO_SALIDA PS ON PS.ID_PEDIDO_SALIDA = PSL.ID_PEDIDO_SALIDA
                                        WHERE OM.ID_UBICACION_MAQUINA = $rowLinea->ID_UBICACION_SUMINISTRO_DIRECTO AND PSL.ID_MATERIAL = $rowLinea->ID_MATERIAL AND OML.LUGAR_APROVISIONAMIENTO = 'Suministro Directo' AND OML.TIPO_LINEA = 'Materiales' AND OML.BAJA = 0 AND PSL.ESTADO <> 'Finalizada' AND (PS.ESTADO='Grabado' OR PS.ESTADO='En Entrega') AND PSL.CANTIDAD_PENDIENTE_SERVIR > 0
                                        ORDER BY OML.FECHA_PLANIFICADA ASC";
            $resultListasMateriales = $bd->ExecSQL($sqlListasMateriales);

            //RECORREMOS LA LISTA
            while (($rowListasMateriales = $bd->SigReg($resultListasMateriales)) && ($cantidadTrasladar > 0)):
                //SI LA CANTIDAD PDTE ASIGNAR ES MAYOR QUE LA DEL PEDIDO
                if ($cantidadTrasladar >= $rowListasMateriales->CANTIDAD_PENDIENTE_SERVIR):
                    $arrPedidoLinea[$rowListasMateriales->ID_PEDIDO_SALIDA_LINEA]['Cantidad'] = $rowListasMateriales->CANTIDAD_PENDIENTE_SERVIR;
                    $arrPedidoLinea[$rowListasMateriales->ID_PEDIDO_SALIDA_LINEA]['UOP']      = $rowLinea->ID_UBICACION_SUMINISTRO_DIRECTO;

                    $cantidadTrasladar = $cantidadTrasladar - $rowListasMateriales->CANTIDAD_PENDIENTE_SERVIR;

                else://SI LA CANTIDAD PDTE ASIGNAR ES MENOR QUE LA DEL PEDIDO, LA ASIGNAMOS AL PEDIDO
                    $arrPedidoLinea[$rowListasMateriales->ID_PEDIDO_SALIDA_LINEA]['Cantidad'] = $cantidadTrasladar;
                    $arrPedidoLinea[$rowListasMateriales->ID_PEDIDO_SALIDA_LINEA]['UOP']      = $rowLinea->ID_UBICACION_SUMINISTRO_DIRECTO;

                    $cantidadTrasladar = 0;
                endif;

            endwhile;

            //SI QUEDA CANTIDAD PENDIENTE, GUARDAMOS EL ERROR
            if ($cantidadTrasladar > 0):
                //GUARDAMOS EL ERROR
                $rowUbicacion = $bd->VerReg("UBICACION", "ID_UBICACION", $rowLinea->ID_UBICACION_SUMINISTRO_DIRECTO);
                $rowMaterial  = $bd->VerReg("MATERIAL", "ID_MATERIAL", $rowLinea->ID_MATERIAL);
                $arrError[]   = $auxiliar->traduce("No hay suficiente Cantidad en los pedidos de OMs", $administrador->ID_IDIOMA) . ". " . $auxiliar->traduce("UOP", $administrador->ID_IDIOMA) . ": " . $rowUbicacion->UBICACION . " - " . $auxiliar->traduce("Material", $administrador->ID_IDIOMA) . ": " . $rowMaterial->REFERENCIA_SGA;
            endif;


            //GUARDAMOS LOS MEL (HACEMOS LA BUSQUEDA, QUE LA ANTERIOR ESTABA AGRUPADA)
            $sqlMEL    = "SELECT MEL.ID_MOVIMIENTO_ENTRADA_LINEA, MEL.ID_UBICACION, MEL.ID_UBICACION_SUMINISTRO_DIRECTO, MEL.ID_MATERIAL, MEL.CANTIDAD
                        FROM MOVIMIENTO_ENTRADA_LINEA MEL
                        WHERE MEL.ID_MOVIMIENTO_ENTRADA = " . $rowMovimientoEntrada->ID_MOVIMIENTO_ENTRADA . " AND MEL.ID_MATERIAL = $rowLinea->ID_MATERIAL AND MEL.ID_UBICACION_SUMINISTRO_DIRECTO = $rowLinea->ID_UBICACION_SUMINISTRO_DIRECTO AND MEL.LINEA_ANULADA = 0 AND MEL.BAJA = 0";
            $resultMEL = $bd->ExecSQL($sqlMEL);
            while ($rowMel = $bd->SigReg($resultMEL)):

                //LO REGISTRAMOS
                $arrEntradas[$rowMel->ID_UBICACION_SUMINISTRO_DIRECTO][$rowMel->ID_MATERIAL][$rowMel->ID_MOVIMIENTO_ENTRADA_LINEA] = $rowMel->CANTIDAD;
            endwhile;
        endwhile;//FIN RECORREMOS LOS MATERIALES A TRASLADAR

        //SI HAY ERRORES SALIMOS
        if (count( (array)$arrError) > 0):
            return $arrError;
        endif;

        //SI NO HAY ERROR Y HEMOS ESCOGIDO LOS PEDIDOS A ENTREGAR
        if (count( (array)$arrPedidoLinea) > 0):

            //1. Generamos orden de preparación (la prepararemos automaticamente)
            $sqlInsert = "INSERT INTO ORDEN_PREPARACION SET
                            FECHA = '" . date("Y-m-d") . "'
                            , FECHA_ULTIMA_MODIFICACION = '" . date("Y-m-d") . "'
                            , VIA_PREPARACION = 'WEB'
                            , TIPO_PREPARACION = 'Estandar'
                            , ESTADO = 'Preparada'
                            , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                            , ID_ADMINISTRADOR_ULTIMA_MODIFICACION = $administrador->ID_ADMINISTRADOR
                            , FECHA_PREVISTA_CARGA = '" . date("Y-m-d") . "'
                            , HORA_PREVISTA_CARGA = '" . date("H:i:s") . "'
                            , DESCRIPCION = 'Preparacion Automatica Suministro Directo'
                            , ID_CENTRO_FISICO_ORIGEN = $rowMovimientoRecepcion->ID_CENTRO_FISICO
                            , TIPO_ORDEN = 'OTOM'";
            $bd->ExecSQL($sqlInsert);
            $idOrdenPreparacion = $bd->IdAsignado();

            // LOG MOVIMIENTOS
            $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Creación", "Orden preparación", $idOrdenPreparacion, "");


            //2. Generamos la expedicion (inicialmente estado Creada y luego avanzaremos estados)
            $sqlInsert = "INSERT INTO EXPEDICION SET
                            ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                            , ID_ADMINISTRADOR_CREACION = $administrador->ID_ADMINISTRADOR
                            , TIPO_GENERACION = 'Manual'
                            , ESTADO = 'Creada'
                            , DESCRIPCION_ORDEN_TRANSPORTE = 'Suministro directo Construccion'
                            , SUBTIPO_ORDEN_RECOGIDA = 'Entrega Material OM'
                            , FECHA = '" . date("Y-m-d") . "'
                            , HORA = '" . date("H:i:s") . "'
                            , CON_BULTOS = 0
                            , NUM_BULTOS = '" . $rowMovimientoRecepcion->BULTOS . "'
                            , ID_TRANSPORTISTA = " . ($rowMovimientoRecepcion->ID_TRANSPORTISTA_EFECTIVO == '' ? 'NULL' : $rowMovimientoRecepcion->ID_TRANSPORTISTA_EFECTIVO) . "
                            , ID_AGENCIA = " . ($rowMovimientoRecepcion->ID_TRANSPORTISTA == '' ? 'NULL' : $rowMovimientoRecepcion->ID_TRANSPORTISTA) . "
                            , ID_CHOFER = NULL
                            , MATRICULA = '" . trim( (string)$bd->escapeCondicional($rowMovimientoRecepcion->MATRICULA)) . "'
                            , MATRICULA_REMOLQUE = '" . trim( (string)$bd->escapeCondicional($rowMovimientoRecepcion->MATRICULA)) . "'
                            , ID_CENTRO_FISICO = $rowMovimientoRecepcion->ID_CENTRO_FISICO
                            , ID_CENTRO_FISICO_DESTINO = NULL
                            , TIPO_EXPEDICION = 'LogisticaDirecta'
                            , TIPO_ORDEN_RECOGIDA = 'Recogida en Almacen'";
            $bd->ExecSQL($sqlInsert); //exit($sqlInsert);
            $idExpedicion = $bd->IdAsignado();


            // LOG MOVIMIENTOS
            $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Creación", "Orden de Recogida", $idExpedicion, "");


            /************************************ DESHABILITO TRIGGERS IMPLICADOS ************************************/
            $sqlDeshabilitarTriggers = "SET @Trigger_MOVIMIENTO_SALIDA_LINEA_INSERT_Deshabilitado = 1";
            $bd->ExecSQL($sqlDeshabilitarTriggers);
            $sqlDeshabilitarTriggers = "SET @Trigger_MOVIMIENTO_SALIDA_LINEA_UPDATE_Deshabilitado = 1";
            $bd->ExecSQL($sqlDeshabilitarTriggers);
            $sqlDeshabilitarTriggers = "SET @Trigger_PEDIDO_SALIDA_LINEA_UPDATE_Deshabilitado = 1";
            $bd->ExecSQL($sqlDeshabilitarTriggers);
            /********************************** FIN DESHABILITO TRIGGERS IMPLICADOS **********************************/


            //3.Recorremos las lineas de pedido para generar los movimientos de salida en estado pendiente de expedir y pasamos el material a SM
            foreach ($arrPedidoLinea as $idPedidoLinea => $arrDatosLinea):

                //BUSCO LAS LINEAS DEL PEDIDO
                $sqlLinea    = "SELECT PSL.*
                                 FROM PEDIDO_SALIDA_LINEA PSL
                                 WHERE PSL.ID_PEDIDO_SALIDA_LINEA = $idPedidoLinea FOR UPDATE";
                $resultLinea = $bd->ExecSQL($sqlLinea);
                $rowLinea    = $bd->SigReg($resultLinea);

                //GUARDAMOS CANTIDAD LINEA
                $cantidadPdteLinea = $arrDatosLinea['Cantidad'];
                $idUOP             = $arrDatosLinea['UOP'];

                //BUSCO EL MOVIMIENTO DE SALIDA ACORDE CON LA LINEA A MOVER
                $GLOBALS["NotificaErrorPorEmail"] = "No";
                $rowMovSal                        = $bd->VerRegRest("MOVIMIENTO_SALIDA", "ID_ORDEN_PREPARACION = $idOrdenPreparacion AND ID_PEDIDO_SALIDA = $rowLinea->ID_PEDIDO_SALIDA AND BAJA = 0", "No");
                unset($GLOBALS["NotificaErrorPorEmail"]);
                if ($rowMovSal == false):

                    //PASAMOS EL PEDIDO A EN ENTREGA
                    $pedido->PedidoAEnEntrega($rowLinea->ID_PEDIDO_SALIDA);

                    //GENERAMOS MOVIMIENTO SALIDA
                    $sqlInsert = "INSERT INTO MOVIMIENTO_SALIDA SET
                                    ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                    , ID_PEDIDO_SALIDA = $rowLinea->ID_PEDIDO_SALIDA
                                    , ID_ORDEN_PREPARACION = $idOrdenPreparacion
                                    , FECHA = '" . $txFechaHora . "'
                                    , FECHA_PREPARACION = '" . $txFechaHora . "'
                                    , ESTADO = 'Pendiente de Expedir'
                                    , TIPO_MOVIMIENTO = 'TrasladoOMConstruccion'";
                    $bd->ExecSQL($sqlInsert);
                    $idMovimientoSalida = $bd->IdAsignado();

                    // LOG MOVIMIENTOS
                    $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Creación", "Mov. Salida", $idMovimientoSalida, "");

                else:
                    $idMovimientoSalida = $rowMovSal->ID_MOVIMIENTO_SALIDA;
                endif;

                //PARA LA UOP DE DESTINO Y EL MATERIAL, BUSCAMOS LOS MEL QUE ACABAMOS DE RECEPCIONAR
                foreach ($arrEntradas[$idUOP][$rowLinea->ID_MATERIAL] as $idMovimientoEntradaLinea => $cantidadEntradaLinea):

                    //BUSCAMOS EL MOVIMIENTO ENTRADA LINEA
                    $rowMovimientoEntradaLinea = $bd->VerReg("MOVIMIENTO_ENTRADA_LINEA", "ID_MOVIMIENTO_ENTRADA_LINEA", $idMovimientoEntradaLinea);

                    //BUSCAMOS TIPO LOTE
                    $tipoLote = "ninguno";
                    if ($rowMovimientoEntradaLinea->ID_MATERIAL_FISICO != NULL):
                        $rowMaterialFisico = $bd->VerReg("MATERIAL_FISICO", "ID_MATERIAL_FISICO", $rowMovimientoEntradaLinea->ID_MATERIAL_FISICO);
                        $tipoLote          = $rowMaterialFisico->TIPO_LOTE;
                    endif;

                    //SI LA CANTIDAD A ASIGNAR AL PEDIDO ES MAYOR QUE LA CANTIDAD DEL MOVIMIENTO DE ENTRADA
                    if ($cantidadPdteLinea >= $cantidadEntradaLinea):
                        $cantidadMovimientoSalidaLinea = $cantidadEntradaLinea;
                    else:
                        $cantidadMovimientoSalidaLinea = $cantidadPdteLinea;
                    endif;

                    //ACTUALIZAMOS LAS CANTIDADES PENDIENTES
                    $cantidadEntradaLinea = $cantidadEntradaLinea - $cantidadMovimientoSalidaLinea;
                    $cantidadPdteLinea    = $cantidadPdteLinea - $cantidadMovimientoSalidaLinea;

                    //BUSCAMOS EL MATERIAL UBICACION INICIO
                    $clausulaWhere   = "ID_MATERIAL = $rowLinea->ID_MATERIAL AND ID_UBICACION = $rowMovimientoEntradaLinea->ID_UBICACION AND ID_MATERIAL_FISICO " . ($rowMovimientoEntradaLinea->ID_MATERIAL_FISICO == NULL ? 'IS NULL' : "= $rowMovimientoEntradaLinea->ID_MATERIAL_FISICO") . " AND ID_TIPO_BLOQUEO IS NULL AND ID_ORDEN_TRABAJO_MOVIMIENTO IS NULL AND ID_INCIDENCIA_CALIDAD IS NULL";
                    $rowMatUbiOrigen = $bd->VerRegRest("MATERIAL_UBICACION", $clausulaWhere);


                    //BUSCO SI EXISTE UNA LINEA DE MOVIMIENTO SIMILAR PARA INCREMENTAR LA CANTIDAD
                    $GLOBALS["NotificaErrorPorEmail"] = "No";
                    $rowMovimientoSalidaLinea         = $bd->VerRegRest("MOVIMIENTO_SALIDA_LINEA",
                        "ID_MOVIMIENTO_SALIDA = $idMovimientoSalida
                                                            AND ESTADO = 'Pendiente de Expedir'
                                                            AND ID_MOVIMIENTO_ENTRADA_LINEA_SUMINISTRO_DIRECTO = " . $rowMovimientoEntradaLinea->ID_MOVIMIENTO_ENTRADA_LINEA . "
                                                            AND ID_UBICACION = " . $rowMovimientoEntradaLinea->ID_UBICACION . "
                                                            AND ID_ALMACEN = $rowLinea->ID_ALMACEN_ORIGEN
                                                            AND ID_MATERIAL = " . $rowLinea->ID_MATERIAL . "
                                                            AND ID_MATERIAL_FISICO " . ($rowMovimientoEntradaLinea->ID_MATERIAL_FISICO == NULL ? 'IS NULL' : "= " . $rowMovimientoEntradaLinea->ID_MATERIAL_FISICO) . "
                                                            AND ID_PEDIDO_SALIDA = $rowLinea->ID_PEDIDO_SALIDA
                                                            AND ID_PEDIDO_SALIDA_LINEA = $rowLinea->ID_PEDIDO_SALIDA_LINEA
                                                            AND TIPO_LOTE = '" . $tipoLote . "'
                                                            AND ID_EXPEDICION = $idExpedicion
                                                            AND ID_UBICACION_DESTINO " . $idUOP . "
                                                            AND ID_ALMACEN_DESTINO " . $rowLinea->ID_ALMACEN_DESTINO . "
                                                            AND ID_TIPO_BLOQUEO IS NULL
                                                            AND ID_ORDEN_TRABAJO_MOVIMIENTO IS NULL
                                                            AND ID_INCIDENCIA_CALIDAD IS NULL
                                                            AND ID_PROVEEDOR_GARANTIA IS NULL
                                                            AND LINEA_ANULADA = 0
                                                            AND BAJA = 0", "No");
                    unset($GLOBALS["NotificaErrorPorEmail"]);


                    if ($rowMovimientoSalidaLinea != false): //HAY UNA LINEA DE MOVIMIENTO SALIDA LINEA SIMILAR
                        //ACTUALIZO LA LINEA DE MOVIMIENTO DE SALIDA LINEA
                        $sqlUpdate = "UPDATE MOVIMIENTO_SALIDA_LINEA SET
                                        CANTIDAD = CANTIDAD + " . $cantidadMovimientoSalidaLinea . "
                                        , CANTIDAD_PEDIDO = CANTIDAD_PEDIDO + " . $cantidadMovimientoSalidaLinea . "
                                        WHERE ID_MOVIMIENTO_SALIDA_LINEA = $rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA_LINEA";
                        $bd->ExecSQL($sqlUpdate);

                        //ESTABLEZCO EL IDENTIFICDOR DE LA LINEA DE MOVIMIENTO DE SALIDA IMPLICADA
                        $idMovimientoSalidaLinea = $rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA_LINEA;
                    else:
                        //CREAMOS LA LINEA DEL MOVIMIENTO DE SALIDA
                        $sqlInsert = "INSERT INTO MOVIMIENTO_SALIDA_LINEA SET
                                    ID_MOVIMIENTO_SALIDA = $idMovimientoSalida
                                    , ID_MOVIMIENTO_ENTRADA_LINEA_SUMINISTRO_DIRECTO = " . $rowMovimientoEntradaLinea->ID_MOVIMIENTO_ENTRADA_LINEA . "
                                    , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                    , FECHA = '" . $txFechaHora . "'
                                    , FECHA_PREPARACION = '" . $txFechaHora . "'
                                    , ESTADO = 'Pendiente de Expedir'
                                    , ID_UBICACION = " . $rowMovimientoEntradaLinea->ID_UBICACION . "
                                    , ID_ALMACEN = $rowLinea->ID_ALMACEN_ORIGEN
                                    , ID_MATERIAL = " . $rowLinea->ID_MATERIAL . "
                                    , ID_MATERIAL_FISICO = " . ($rowMovimientoEntradaLinea->ID_MATERIAL_FISICO == NULL ? 'NULL' : $rowMovimientoEntradaLinea->ID_MATERIAL_FISICO) . "
                                    , ID_PEDIDO_SALIDA = $rowLinea->ID_PEDIDO_SALIDA
                                    , ID_PEDIDO_SALIDA_LINEA = $rowLinea->ID_PEDIDO_SALIDA_LINEA
                                    , TIPO_LOTE = '" . $tipoLote . "'
                                    , CANTIDAD = " . $cantidadMovimientoSalidaLinea . "
                                    , CANTIDAD_PEDIDO = " . $cantidadMovimientoSalidaLinea . "
                                    , ID_UBICACION_DESTINO = " . $idUOP . "
                                    , ID_ALMACEN_DESTINO = " . $rowLinea->ID_ALMACEN_DESTINO . "
                                    , ID_EXPEDICION = $idExpedicion
                                    , ID_TIPO_BLOQUEO = NULL
                                    , ID_ORDEN_TRABAJO_MOVIMIENTO = NULL
                                    , ID_INCIDENCIA_CALIDAD = NULL
                                    , ID_PROVEEDOR_GARANTIA = NULL";
                        $bd->ExecSQL($sqlInsert);
                        //ESTABLEZCO EL IDENTIFICDOR DE LA LINEA DE MOVIMIENTO DE SALIDA IMPLICADA
                        $idMovimientoSalidaLinea = $bd->IdAsignado();
                    endif;


                    //BUSCO LA UBICACION SALIDA DEL ALMACEN DE ORIGEN
                    $rowUbiSalida = $bd->VerRegRest("UBICACION", "ID_ALMACEN = $rowLinea->ID_ALMACEN_ORIGEN AND TIPO_UBICACION = 'Salida' AND BAJA = 0");


                    //GENERO LA TRANSFERENCIA DE DESUBICACION
                    $sqlInsert = "INSERT INTO MOVIMIENTO_TRANSFERENCIA SET
                                    ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                    , ID_MATERIAL = $rowMatUbiOrigen->ID_MATERIAL
                                    , ID_MATERIAL_FISICO = " . ($rowMatUbiOrigen->ID_MATERIAL_FISICO == NULL ? 'NULL' : $rowMatUbiOrigen->ID_MATERIAL_FISICO) . "
                                    , ID_UBICACION_ORIGEN = $rowMatUbiOrigen->ID_UBICACION
                                    , ID_UBICACION_DESTINO = $rowUbiSalida->ID_UBICACION
                                    , ID_MOVIMIENTO_SALIDA = $idMovimientoSalida
                                    , ID_MOVIMIENTO_SALIDA_LINEA = $idMovimientoSalidaLinea
                                    , ID_ORDEN_PREPARACION = $idOrdenPreparacion
                                    , ID_ORDEN_TRABAJO_MOVIMIENTO = NULL
                                    , ID_INCIDENCIA_CALIDAD = NULL
                                    , CANTIDAD = $cantidadMovimientoSalidaLinea
                                    , FECHA = '" . $txFechaHora . "'
                                    , TIPO = 'Automatico'
                                    , STOCK_OK = " . $cantidadMovimientoSalidaLinea . "
                                    , STOCK_BLOQUEADO = 0
                                    , ID_TIPO_BLOQUEO = NULL";//echo($sqlInsert."<hr>");
                    $bd->ExecSQL($sqlInsert);

                    ///DECREMENTO MATERIAL UBICACION (LO MARCAMOS PARA QUE NO ENTRE POR INTEGRIDAD DE MATERIAL FISICO)
                    $sqlUpdate = "UPDATE MATERIAL_UBICACION SET
                                    STOCK_TOTAL = STOCK_TOTAL - " . $cantidadMovimientoSalidaLinea . "
                                    , STOCK_OK = STOCK_OK - " . $cantidadMovimientoSalidaLinea . "
                                    , PENDIENTE_REVISAR_MATERIAL_FISICO_UBICACION = 2
                                    , PENDIENTE_REVISAR_MATERIAL_UBICACION_TIPO_BLOQUEO = 1
                                    WHERE ID_MATERIAL_UBICACION = $rowMatUbiOrigen->ID_MATERIAL_UBICACION";
                    $bd->ExecSQL($sqlUpdate);


                    //BUSCO EL MATERIAL UBICACION DESTINO
                    $GLOBALS["NotificaErrorPorEmail"] = "No";
                    $rowMatUbiDestino                 = $bd->VerRegRest("MATERIAL_UBICACION", "ID_MATERIAL = $rowMatUbiOrigen->ID_MATERIAL AND ID_UBICACION = $rowUbiSalida->ID_UBICACION AND ID_MATERIAL_FISICO " . ($rowMatUbiOrigen->ID_MATERIAL_FISICO == NULL ? 'IS NULL' : "= $rowMatUbiOrigen->ID_MATERIAL_FISICO") . " AND ID_TIPO_BLOQUEO IS NULL AND ID_ORDEN_TRABAJO_MOVIMIENTO IS NULL AND ID_INCIDENCIA_CALIDAD IS NULL", "No");
                    unset($GLOBALS["NotificaErrorPorEmail"]);
                    if ($rowMatUbiDestino == false):
                        $sqlInsert = "INSERT INTO MATERIAL_UBICACION SET
                                        ID_MATERIAL = $rowMatUbiOrigen->ID_MATERIAL
                                        , ID_UBICACION = $rowUbiSalida->ID_UBICACION
                                        , ID_MATERIAL_FISICO = " . ($rowMatUbiOrigen->ID_MATERIAL_FISICO == NULL ? 'NULL' : $rowMatUbiOrigen->ID_MATERIAL_FISICO) . "
                                        , ID_TIPO_BLOQUEO = NULL
                                        , ID_ORDEN_TRABAJO_MOVIMIENTO = NULL
                                        , ID_INCIDENCIA_CALIDAD = NULL";
                        $bd->execSQL($sqlInsert);
                        $idMatUbiDestino = $bd->IdAsignado();
                    else:
                        $idMatUbiDestino = $rowMatUbiDestino->ID_MATERIAL_UBICACION;
                    endif;

                    //INCREMENTO MATERIAL UBICACION EN DESTINO(LO MARCAMOS PARA QUE NO ENTRE POR INTEGRIDAD DE MATERIAL FISICO)
                    $sqlUpdate = "UPDATE MATERIAL_UBICACION SET
                                    STOCK_TOTAL = STOCK_TOTAL + " . $cantidadMovimientoSalidaLinea . "
                                    , STOCK_OK = STOCK_OK + " . $cantidadMovimientoSalidaLinea . "
                                    , PENDIENTE_REVISAR_MATERIAL_FISICO_UBICACION = 2
                                    , PENDIENTE_REVISAR_MATERIAL_UBICACION_TIPO_BLOQUEO = 1
                                    WHERE ID_MATERIAL_UBICACION = $idMatUbiDestino";
                    $bd->ExecSQL($sqlUpdate);

                    //CALCULO LA CANTIDAD PEDIDO DE LA LINEA DEL PEDIDO DE SALIDA, SERA LA SUMA DE LAS CANTIDADES DE LAS LINEAS
                    $sqlCantidadLineaPedido    = "SELECT SUM(CANTIDAD) AS STOCK
                                                  FROM MOVIMIENTO_SALIDA_LINEA MSL
                                                  WHERE MSL.ID_PEDIDO_SALIDA_LINEA = $rowLinea->ID_PEDIDO_SALIDA_LINEA AND MSL.ID_MOVIMIENTO_SALIDA = $idMovimientoSalida AND MSL.LINEA_ANULADA = 0 AND MSL.BAJA = 0";
                    $resultCantidadLineaPedido = $bd->ExecSQL($sqlCantidadLineaPedido);
                    $rowCantidadLineaPedido    = $bd->SigReg($resultCantidadLineaPedido);
                    $cantidadLineaPedido       = $rowCantidadLineaPedido->STOCK;

                    //ACTUALIZO EL VALOR CANTIDAD_PEDIDO DE LAS LINEAS DE LOS MOVIMIENTOS DE SALIDA
                    $sqlUpdate = "UPDATE MOVIMIENTO_SALIDA_LINEA MSL SET
                            CANTIDAD_PEDIDO = $cantidadLineaPedido
                            WHERE MSL.ID_PEDIDO_SALIDA_LINEA = $rowLinea->ID_PEDIDO_SALIDA_LINEA AND MSL.ID_MOVIMIENTO_SALIDA = $idMovimientoSalida AND MSL.LINEA_ANULADA = 0 AND MSL.BAJA = 0";
                    $bd->ExecSQL($sqlUpdate);

                    //ACTUALIZO LA CANTIDAD PENDIENTE DE SERVIR DE LA LINEA DEL PEDIDO A PREPARAR
                    $sqlUpdate = "UPDATE PEDIDO_SALIDA_LINEA SET
                            CANTIDAD_PENDIENTE_SERVIR = CANTIDAD_PENDIENTE_SERVIR - $cantidadMovimientoSalidaLinea
                            WHERE ID_PEDIDO_SALIDA_LINEA = $rowLinea->ID_PEDIDO_SALIDA_LINEA";
                    $bd->ExecSQL($sqlUpdate);

                    //SI NOS HEMOS QUEDADO SIN CANTIDAD, LO QUITAMOS DEL ARRAY
                    if ($cantidadEntradaLinea == 0):
                        unset($arrEntradas[$idUOP][$rowLinea->ID_MATERIAL][$idMovimientoEntradaLinea]);
                    else://ACTUALIZAMOS EL ARRAY
                        $arrEntradas[$idUOP][$rowLinea->ID_MATERIAL][$idMovimientoEntradaLinea] = $cantidadEntradaLinea;
                    endif;

                    //SI HEMOS ACABADO CON LA LINEA SALIMOS DEL FOREACH
                    if ($cantidadPdteLinea == 0):
                        break;
                    endif;

                endforeach;
            endforeach;

            //4. Generamos el transporte de la Recogida

            //RECUPERAMOS LA RECOGIDA
            $rowExpedicion = $bd->VerReg("EXPEDICION", "ID_EXPEDICION", $idExpedicion);

            $pesoCalculado = $importe->getPesoMaterialesConPesoOrdenRecogida($rowExpedicion->ID_EXPEDICION);

            //GENERAMOS LA ORDEN DE TRANSPORTE
            $sqlInsert = "INSERT INTO ORDEN_TRANSPORTE SET
                                  ESTADO = 'Creada'
                                , TIENE_GASTOS_TRANSPORTE = 0
                                , PESO = $pesoCalculado
                                , ID_ADMINISTRADOR_CREACION='" . $administrador->ID_ADMINISTRADOR . "'
                                , FECHA_CREACION='" . $txFechaHora . "'
                                , ID_ADMINISTRADOR_ULTIMA_MODIFICACION='" . $administrador->ID_ADMINISTRADOR . "'
                                , FECHA_ULTIMA_MODIFICACION='" . $txFechaHora . "'
                                , ID_TRANSPORTISTA = " . ($rowExpedicion->ID_TRANSPORTISTA == '' ? 'NULL' : $rowExpedicion->ID_TRANSPORTISTA) . "
                                , ID_AGENCIA = " . ($rowExpedicion->ID_AGENCIA == '' ? 'NULL' : $rowExpedicion->ID_AGENCIA) . "
                                , MATRICULA = '" . trim( (string)$bd->escapeCondicional($rowExpedicion->MATRICULA)) . "'
                                , MATRICULA_REMOLQUE = '" . trim( (string)$bd->escapeCondicional($rowExpedicion->MATRICULA_REMOLQUE)) . "'
                                , TIPO_TRANSPORTE = '" . ($rowExpedicion->TIPO_TRANSPORTE == '' ? 'NULL' : $rowExpedicion->TIPO_TRANSPORTE) . "'
                                , ID_CONTENEDOR_EXPORTACION = " . ($rowExpedicion->ID_CONTENEDOR_EXPORTACION == '' ? 'NULL' : $rowExpedicion->ID_CONTENEDOR_EXPORTACION) . "
                                , ID_PUERTO_ORIGEN = " . ($rowExpedicion->ID_PUERTO_EXPORTACION_ORIGEN == '' ? 'NULL' : $rowExpedicion->ID_PUERTO_EXPORTACION_ORIGEN) . "
                                , ID_PUERTO_DESTINO = " . ($rowExpedicion->ID_PUERTO_EXPORTACION_DESTINO == '' ? 'NULL' : $rowExpedicion->ID_PUERTO_EXPORTACION_DESTINO) . "
                                , CARGA = " . ($rowExpedicion->CARGA == '' ? 'NULL' : "' $rowExpedicion->CARGA'") . "
                                , ALBARAN_TRANSPORTE = '" . trim( (string)$bd->escapeCondicional($rowExpedicion->ALBARAN_TRANSPORTE)) . "'
                                , NACIONAL = " . ($rowExpedicion->NACIONAL == '' ? 'NULL' : "' $rowExpedicion->NACIONAL'") . "
                                , COMUNITARIO = " . ($rowExpedicion->COMUNITARIO == '' ? 'NULL' : "' $rowExpedicion->COMUNITARIO'") . "
                                , ADR = " . ($rowExpedicion->ADR == '' ? 'NULL' : "' $rowExpedicion->ADR'") . "
                                , SEGURO = " . $rowExpedicion->SEGURO . "
                                , CONTRATACION = " . ($rowExpedicion->CONTRATACION == '' ? 'NULL' : "' $rowExpedicion->CONTRATACION'");
            $bd->ExecSQL($sqlInsert);

            //OBTENGO ID CREADO
            $idOrdenTransporte = $bd->IdAsignado();

            //SI LA OT ES DEL MODELO NUEVO, NO SE PUEDE ASIGNAR UNA RECOGIDA CON PEDIDOS RELEVANTES PARA ENTREGA ENTRANTE
            $sqlCuantosREE    = "SELECT COUNT(PSL.ID_PEDIDO_SALIDA_LINEA) AS NUM_REE
                              FROM PEDIDO_SALIDA_LINEA PSL
                              INNER JOIN MOVIMIENTO_SALIDA_LINEA MSL ON MSL.ID_PEDIDO_SALIDA_LINEA = PSL.ID_PEDIDO_SALIDA_LINEA
                              WHERE MSL.ID_EXPEDICION = $rowExpedicion->ID_EXPEDICION AND PSL.RELEVANTE_ENTREGA_ENTRANTE = 1
                              AND PSL.BAJA = 0 AND MSL.BAJA = 0 AND MSL.LINEA_ANULADA = 0";
            $resultCuantosREE = $bd->ExecSQL($sqlCuantosREE);
            $rowCuantosREE    = $bd->SigReg($resultCuantosREE);
            $html->PagErrorCondicionado($rowCuantosREE->NUM_REE, ">", 0, "RecogidaRelevanteEntregaEntranteParaOrdenTransporteModeloNuevo");

            //ACTUALIZO LA RECOGIDA
            $sqlUpdate = "UPDATE EXPEDICION SET
                             ID_ORDEN_TRANSPORTE = $idOrdenTransporte
                            , PESO = '" . $pesoCalculado . "'
                            WHERE ID_EXPEDICION = $rowExpedicion->ID_EXPEDICION";
            $bd->ExecSQL($sqlUpdate);

            //TRAIGO EL REGISTRO ACTUALIZADO
            $rowExpedicion = $bd->VerReg("EXPEDICION", "ID_EXPEDICION", $rowExpedicion->ID_EXPEDICION, "No");


            //5. Expedimos y se hara la recepcion automática

            //COMIENZO TRANSMISION DE LA ORDEN DE TRANSPORTE (GENERO LOS ALBARANES Y ASIGNO LA EXPEDICION SAP A LAS LINEAS)
            $arrDevueltoTransmitirOrdenTransporteASAP = $expedicion->generar_albaranes_y_asignar_expediciones_SAP($rowExpedicion->ID_EXPEDICION);

            //GUARDO LOS ERRORES QUE SE HAN PRODUCIDO EN ESTE PRIMER PASO
            if ($arrDevueltoTransmitirOrdenTransporteASAP['errores'] != ""):
                $arrError[] = "<strong>" . $arrDevueltoTransmitirOrdenTransporteASAP['errores'] . "</strong>";

                //SI HAY ERRORES SALIMOS
                return $arrError;

            endif;

            //EXTRAIGO LAS EXPEDICIONES SAP A TRANSMITIR A SAP
            $arrExpedicionesSAP = array();
            if ($arrDevueltoTransmitirOrdenTransporteASAP['expediciones_SAP'] != ""):
                $arrExpedicionesSAP = explode(",", (string)$arrDevueltoTransmitirOrdenTransporteASAP['expediciones_SAP']);
            endif;


            //SI EXISTEN EXPEDICIONES SAP, LAS TRANSMITIMOS
            if (count( (array)$arrExpedicionesSAP) > 0):


                /************************************ DESHABILITO TRIGGERS IMPLICADOS POR SI SE HAN HABILITADO EN ALGUNA FUNCION************************************/
                $sqlDeshabilitarTriggers = "SET @Trigger_MOVIMIENTO_SALIDA_LINEA_INSERT_Deshabilitado = 1";
                $bd->ExecSQL($sqlDeshabilitarTriggers);
                $sqlDeshabilitarTriggers = "SET @Trigger_MOVIMIENTO_SALIDA_LINEA_UPDATE_Deshabilitado = 1";
                $bd->ExecSQL($sqlDeshabilitarTriggers);
                $sqlDeshabilitarTriggers = "SET @Trigger_PEDIDO_SALIDA_LINEA_UPDATE_Deshabilitado = 1";
                $bd->ExecSQL($sqlDeshabilitarTriggers);
                /********************************** FIN DESHABILITO TRIGGERS IMPLICADOS POR SI SE HAN HABILITADO EN ALGUNA FUNCION**********************************/

                //RECORRO LAS EXPEDICIONES SAP (DESARROLLO A PARTIR DE VERSION CUARTA
                foreach ($arrExpedicionesSAP as $expedicionSAP):

                    //DESCOMPONGO LA EXPEDICION SAP DEVUELTA ($duplaOrdenTransporteMovimientoSalidaLinea)
                    $arrOrdenTransporteExpedicionSAP = explode("_", (string)$expedicionSAP);

                    //CONFORMO EL VALOR DE LA EXPEDICION SAP ASIGNADA A LAS LINEAS
                    $expedicionSAPGuardar = $expedicionSAP;

                    //GENERO LA EXPEDICION SAP CON IDENTIFICADORES QUE ES LO QUE ESPERA LA FUNCION PARA TRANSMITIRLA A SAP
                    $expedicionSAPConIdentificadores = $exp_SAP->getExpedicionSAPConIdentificadores($rowExpedicion->VERSION, $expedicionSAPGuardar);


                    //TRANSMITO LA EXPEDICION SAP A SAP (No hace transmision pero si que hace updates)
                    $arrDevueltoTransmitirOrdenTransporteASAP = $expedicion->transmitir_expedicionSAP_a_SAP($rowExpedicion->VERSION, $expedicionSAPConIdentificadores, $txFechaHora);

                    //SI SE HA PRODUCIDO UN ERROR CON LA EXPEDICION SAP O SU TRANSMISION DESHACEMOS LA TRANSACCION
                    if ($arrDevueltoTransmitirOrdenTransporteASAP['error_expedidion_SAP'] == true):
                        //ME GUARDO LOS ERRORES
                        if ($arrDevueltoTransmitirOrdenTransporteASAP['error_expedidion_SAP'] == true):
                            $arrError[] = "<strong>" . $auxiliar->traduce("Se han producido los siguientes errores al transmitir la expedicion SAP", $administrador->ID_IDIOMA) . " " . $expedicionSAP . ": </strong><br>" . $arrDevueltoTransmitirOrdenTransporteASAP['errores'] . "<br>";
                        endif;

                    endif;

                endforeach;//FIN RECORRO LAS EXPEDICIONES SAP
            endif;
            //FIN SI EXISTEN EXPEDICIONES SAP, LAS TRANSMITIMOS


            //SI HAY ERRORES SALIMOS
            if (count( (array)$arrError) > 0):
                return $arrError;
            endif;

            /************************************ DESHABILITO TRIGGERS IMPLICADOS POR SI SE HAN HABILITADO EN ALGUNA FUNCION************************************/
            $sqlDeshabilitarTriggers = "SET @Trigger_MOVIMIENTO_SALIDA_LINEA_INSERT_Deshabilitado = 1";
            $bd->ExecSQL($sqlDeshabilitarTriggers);
            $sqlDeshabilitarTriggers = "SET @Trigger_MOVIMIENTO_SALIDA_LINEA_UPDATE_Deshabilitado = 1";
            $bd->ExecSQL($sqlDeshabilitarTriggers);
            $sqlDeshabilitarTriggers = "SET @Trigger_PEDIDO_SALIDA_LINEA_UPDATE_Deshabilitado = 1";
            $bd->ExecSQL($sqlDeshabilitarTriggers);
            /********************************** FIN DESHABILITO TRIGGERS IMPLICADOS POR SI SE HAN HABILITADO EN ALGUNA FUNCION**********************************/

            //EXPIDO LA ORDEN DE TRANSPORTE
            $arrDevueltoExpedirOrdenTransporte = $expedicion->expedir_orden_transporte($rowExpedicion->ID_EXPEDICION);

            //SI HAY ERRORES AL EXPEDIR LA ORDEN DE TRANSPORTE LOS MUESTRO EN PANTALLA
            if ($arrDevueltoExpedirOrdenTransporte['errores'] != ""):
                //ASIGNO LOS ERRORES Y LAS EXPEDICIONES SAP PROCESADAS A $strError
                $arrError[] = $arrDevueltoExpedirOrdenTransporte;

                //DEVOLVEMOS LOS ERRORES
                return $arrError;
            endif;


            /************************************ HABILITO TRIGGERS IMPLICADOS ************************************/
            $sqlHabilitarTriggers = "SET @Trigger_MOVIMIENTO_SALIDA_LINEA_INSERT_Deshabilitado = NULL";
            $bd->ExecSQL($sqlHabilitarTriggers);
            $sqlHabilitarTriggers = "SET @Trigger_MOVIMIENTO_SALIDA_LINEA_UPDATE_Deshabilitado = NULL";
            $bd->ExecSQL($sqlHabilitarTriggers);
            $sqlHabilitarTriggers = "SET @Trigger_PEDIDO_SALIDA_LINEA_UPDATE_Deshabilitado = NULL";
            $bd->ExecSQL($sqlHabilitarTriggers);
            /********************************** FIN HABILITO TRIGGERS IMPLICADOS **********************************/


            //MODIFICO LAS LINEAS PARA QUE SE EJECUTEN LOS TRIGGERS Y LAS LINEAS SE QUEDEN COMO CORRESPONDAN
            foreach ($arrPedidoLinea as $idPedidoLinea => $cantidad):
                $sqlUpdate = "UPDATE PEDIDO_SALIDA_LINEA SET BAJA = BAJA WHERE ID_PEDIDO_SALIDA_LINEA = $idPedidoLinea";
                $bd->ExecSQL($sqlUpdate);
            endforeach;

        endif;
        //FIN RECORREMOS LAS LINEAS

        //DEVOLVEMOS LOS ERRORES
        return $arrError;
    }


    /**
     * @param $idMovimientoEntrada MOVIMIENTO DE ENTRADA QUE PUEDE CONTENER MATERIALES QUE SE MOVIERON A LAS UOPS
     * DESHACE TODOS LOS MOVIMIENTOS GENERADOS PARA EL SUMINISTRO DIRECTO Y DEJA EL MATERIAL DE NUEVO EN CONSOLIDACION
     */
    function deshacerSuministroDirectoConstruccion($idMovimientoEntrada)
    {

        global $bd;
        global $administrador;
        global $html;
        global $auxiliar;
        global $pedido;
        global $importe;
        global $expedicion;
        global $sap;
        global $exp_SAP;
        global $albaran;
        global $orden_trabajo;

        //BUSCO EL MOVIMIENTO DE ENTRADA
        $rowMovimientoEntrada = $bd->VerReg("MOVIMIENTO_ENTRADA", "ID_MOVIMIENTO_ENTRADA", $idMovimientoEntrada);

        //BUSCAMOS RECEPCION
        $rowMovimientoRecepcion = $bd->VerReg("MOVIMIENTO_RECEPCION", "ID_MOVIMIENTO_RECEPCION", $rowMovimientoEntrada->ID_MOVIMIENTO_RECEPCION);

        //COMPROBAMOS QUE EL MOVIMIENTO ES DE TIPO CONSTRUCCION
        $html->PagErrorCondicionado($rowMovimientoEntrada->TIPO_MOVIMIENTO, "!=", "Construccion", "TipoMovimientoIncorrecto");
        $html->PagErrorCondicionado($rowMovimientoEntrada->ESTADO, "==", "En Proceso", "AccionEnEstadoIncorrecto");

        //VARIABLES
        $arrError = array();

        //ARRAY PARA GUARDAR LAS LINEAS QUE TENGO QUE PREPARAR
        $arrPedidoLinea = array();
        $arrPedido      = array();


        //BUSCAMOS LA EXPEDICION GENERADA AL CREAR LA RECEPCION
        $sqlRecogidaMovimiento    = "SELECT DISTINCT MSL.ID_EXPEDICION
                            FROM MOVIMIENTO_SALIDA_LINEA MSL
                            INNER JOIN MOVIMIENTO_ENTRADA_LINEA MEL ON MEL.ID_MOVIMIENTO_ENTRADA_LINEA = MSL.ID_MOVIMIENTO_ENTRADA_LINEA_SUMINISTRO_DIRECTO
                            WHERE  MEL.ID_MOVIMIENTO_ENTRADA = $rowMovimientoEntrada->ID_MOVIMIENTO_ENTRADA AND MEL.BAJA = 0 AND MEL.LINEA_ANULADA = 0 AND MSL.BAJA = 0 AND MSL.LINEA_ANULADA = 0";
        $resultRecogidaMovimiento = $bd->ExecSQL($sqlRecogidaMovimiento);
        $rowRecogidaMovimiento    = $bd->SigReg($resultRecogidaMovimiento);

        //SI HUBO SUMINISTRO DIRECTO, LO DESHACEMOS
        if (($rowRecogidaMovimiento != false) && ($rowRecogidaMovimiento->ID_EXPEDICION != "")):


            /************************************ DESHABILITO TRIGGERS IMPLICADOS ************************************/
            $sqlDeshabilitarTriggers = "SET @Trigger_MOVIMIENTO_SALIDA_LINEA_INSERT_Deshabilitado = 1";
            $bd->ExecSQL($sqlDeshabilitarTriggers);
            $sqlDeshabilitarTriggers = "SET @Trigger_MOVIMIENTO_SALIDA_LINEA_UPDATE_Deshabilitado = 1";
            $bd->ExecSQL($sqlDeshabilitarTriggers);
            $sqlDeshabilitarTriggers = "SET @Trigger_PEDIDO_SALIDA_LINEA_UPDATE_Deshabilitado = 1";
            $bd->ExecSQL($sqlDeshabilitarTriggers);
            /********************************** FIN DESHABILITO TRIGGERS IMPLICADOS **********************************/

            //TRAIGO EL REGISTRO ACTUALIZADO
            $rowExpedicion = $bd->VerReg("EXPEDICION", "ID_EXPEDICION", $rowRecogidaMovimiento->ID_EXPEDICION, "No");

            //1. CANCELAMOS LA EXPEDICION Y RECEPCION AUTOMATICA
            $arrDevueltoCancelarExpedicionOrdenTransporte = $expedicion->cancelar_expedicion_orden_transporte($rowExpedicion->ID_EXPEDICION);

            //SI HAY ERRORES AL EXPEDIR LA ORDEN DE TRANSPORTE LOS MUESTRO EN PANTALLA
            if ($arrDevueltoCancelarExpedicionOrdenTransporte['errores'] != ""):

                $arrError[] = $arrDevueltoCancelarExpedicionOrdenTransporte['errores'];

                return $arrError;
            endif;

            //2.CANCELAMOS LA EXPEDICION

            //GENERO UN ARRAY PARA ALMACENAR LA INFORMACION DE LA CANCELACION DE LA TRANSMISION A SAP DE LAS EXPEDICIONES SAP
            $errorCancelarExpedicion = "";

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
            if (count( (array)$arrExpedicionesSAP) > 0):
                //RECORRO LAS EXPEDICIONES SAP
                foreach ($arrExpedicionesSAP as $expedicionSAP):

                    //CALCULO EL ID DE PEDIDO_SALIDA
                    $idPedidoSalida = $exp_SAP->getIdPedidoSalidaSGA($rowExpedicion->VERSION, $expedicionSAP);

                    //EXTRAIGO LOS VALORES DE LA EXPEDICION SAP
                    $arrValoresExpedicionSAP = explode("_", trim( (string)$expedicionSAP));

                    //SI EL ID DE PEDIDO_SALIDA ES NULO ERROR
                    if ($idPedidoSalida == NULL):
                        $errorCancelarExpedicion = $errorCancelarExpedicion . $auxiliar->traduce("La expedición SAP introducida es incorrecta", $administrador->ID_IDIOMA) . ".<br>";
                        continue; //CONTINUO CON LA SIGUIENTE EXPEDICION SAP
                    endif;

                    //CALCULO LOS DATOS DEL PEDIDO
                    $rowPedSal = $bd->VerReg("PEDIDO_SALIDA", "ID_PEDIDO_SALIDA", $idPedidoSalida, "No");
                    if ($rowPedSal == false):
                        $errorCancelarExpedicion = $errorCancelarExpedicion . $auxiliar->traduce("La expedición SAP introducida no existe", $administrador->ID_IDIOMA) . ".<br>";
                        continue; //CONTINUO CON LA SIGUIENTE EXPEDICION SAP
                    endif;

                    //TRANSMITO LA CANCELACION DE LA EXPEDICION SAP A SAP
                    $arrDevueltoCancelarExpedicionSAPASAP = $expedicion->cancelar_transmision_expedicionSAP_a_SAP($rowExpedicion->VERSION, $expedicionSAP);

                    //SI SE HA PRODUCIDO UN ERROR CON LA EXPEDICION SAP O SU TRANSMISION DESHACEMOS LA TRANSACCION
                    if (($arrDevueltoCancelarExpedicionSAPASAP['error_cancelacion_expedidion_SAP'] == true)):
                        $errorCancelarExpedicion = $errorCancelarExpedicion . "<strong>" . $auxiliar->traduce("Se han producido los siguientes errores al transmitir la cancelacion de la expedicion SAP", $administrador->ID_IDIOMA) . " " . $expedicionSAP . ": </strong><br>" . $arrDevueltoCancelarExpedicionSAPASAP['errores'] . "<br>";
                    endif;

                endforeach;

                //SI HAY ERRORES AL EXPEDIR LA ORDEN DE TRANSPORTE LOS MUESTRO EN PANTALLA
                if ($errorCancelarExpedicion != ""):

                    $arrError[] = $errorCancelarExpedicion;

                    return $arrError;
                endif;
                //FIN RECORRO LAS EXPEDICIONES SAP
            endif;
            //FIN SI EXISTEN EXPEDICIONES SAP, LAS TRANSMITIMOS
            /****************************************** FIN ACCIONES CANCELACION TRANSMITIR EXPEDICION SAP A SAP ******************************************/


            //3. LA EXPEDICION ESTA EN ESTADO CREADA Y EL MATERIAL EN SM, ANULAMOS RECOGIDA Y PREPARACION Y TRANSFERIMOS A LA UBICACION DE ENTRADA

            //TRAIGO EL REGISTRO ACTUALIZADO
            $rowExpedicionActualizada = $bd->VerReg("EXPEDICION", "ID_EXPEDICION", $rowExpedicion->ID_EXPEDICION, "No");


            //BUSCAMOS LOS MOVIMIENTOS DE ENTRADA RELACIONADOS
            $sqlMovimientos    = "SELECT DISTINCT MSL.*
                                    FROM MOVIMIENTO_SALIDA_LINEA MSL
                                    INNER JOIN MOVIMIENTO_ENTRADA_LINEA MEL ON MEL.ID_MOVIMIENTO_ENTRADA_LINEA = MSL.ID_MOVIMIENTO_ENTRADA_LINEA_SUMINISTRO_DIRECTO
                                    WHERE  MEL.ID_MOVIMIENTO_ENTRADA = $rowMovimientoEntrada->ID_MOVIMIENTO_ENTRADA AND MEL.BAJA = 0 AND MEL.LINEA_ANULADA = 0 AND MSL.BAJA = 0 AND MSL.LINEA_ANULADA = 0";
            $resultMovimientos = $bd->ExecSQL($sqlMovimientos);


            /************************************ DESHABILITO TRIGGERS IMPLICADOS POR SI SE HAN HABILITADO EN ALGUNA FUNCION************************************/
            $sqlDeshabilitarTriggers = "SET @Trigger_MOVIMIENTO_SALIDA_LINEA_INSERT_Deshabilitado = 1";
            $bd->ExecSQL($sqlDeshabilitarTriggers);
            $sqlDeshabilitarTriggers = "SET @Trigger_MOVIMIENTO_SALIDA_LINEA_UPDATE_Deshabilitado = 1";
            $bd->ExecSQL($sqlDeshabilitarTriggers);
            $sqlDeshabilitarTriggers = "SET @Trigger_PEDIDO_SALIDA_LINEA_UPDATE_Deshabilitado = 1";
            $bd->ExecSQL($sqlDeshabilitarTriggers);
            /********************************** FIN DESHABILITO TRIGGERS IMPLICADOS POR SI SE HAN HABILITADO EN ALGUNA FUNCION**********************************/

            //RECORREMOS LOS MOVIMIENTOS PARA ANULARLOS
            while ($rowMovimientoSalidaLinea = $bd->SigReg($resultMovimientos)):

                //BUSCO LA TRANSFERENCIA DE ESTA LINEA DE MOVIMIENTO DE SALIDA (SOLO HABRA UNA EN PRINCIPIO)
                $sqlTransferencias    = "SELECT *
                                            FROM MOVIMIENTO_TRANSFERENCIA
                                            WHERE ID_MOVIMIENTO_SALIDA_LINEA = $rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA_LINEA AND TIPO = 'Automatico' AND BAJA = 0";
                $resultTransferencias = $bd->ExecSQL($sqlTransferencias);

                while ($rowTransferencia = $bd->SigReg($resultTransferencias)):
                    //BUSCO MATERIAL UBICACION  DONDE SE DEPOSITÓ EL MATERIAL (DEBERIA SER DE TIPO SALIDA)
                    $GLOBALS["NotificaErrorPorEmail"] = "No";
                    $rowMatUbi                        = $bd->VerRegRest("MATERIAL_UBICACION", "ID_MATERIAL = $rowTransferencia->ID_MATERIAL AND ID_UBICACION = $rowTransferencia->ID_UBICACION_DESTINO AND ID_MATERIAL_FISICO " . ($rowTransferencia->ID_MATERIAL_FISICO == NULL ? 'IS NULL' : " = $rowTransferencia->ID_MATERIAL_FISICO") . " AND ID_TIPO_BLOQUEO " . ($rowTransferencia->ID_TIPO_BLOQUEO == NULL ? 'IS NULL' : "= $rowTransferencia->ID_TIPO_BLOQUEO") . " AND ID_ORDEN_TRABAJO_MOVIMIENTO " . ($rowTransferencia->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? 'IS NULL' : "= $rowTransferencia->ID_ORDEN_TRABAJO_MOVIMIENTO") . " AND ID_INCIDENCIA_CALIDAD " . ($rowTransferencia->ID_INCIDENCIA_CALIDAD == NULL ? 'IS NULL' : "= $rowTransferencia->ID_INCIDENCIA_CALIDAD"), "No");
                    unset($GLOBALS["NotificaErrorPorEmail"]);

                    //COMPRUEBO QUE EXISTE EL MATERIAL UBICACION ORIGEN (SM)
                    if ($rowMatUbi == false):
                        $arrError[] = $auxiliar->traduce("No existe la ubicacion destino donde fue desubicado el material", $administrador->ID_IDIOMA);

                        return $arrError;
                    endif;

                    //COMPRUEBO QUE EN LA UBICACION ORIGEN (SM) HAYA SUFICIENTE STOCK
                    if ($rowMatUbi->STOCK_TOTAL < $rowTransferencia->CANTIDAD):
                        $arrError[] = $auxiliar->traduce("No existe stock suficiente para anular la linea seleccionada", $administrador->ID_IDIOMA);

                        return $arrError;
                    endif;

                    //DECREMENTO CANTIDAD EN MATERIAL UBICACION ORIGEN (DEBERIA SER DE TIPO SALIDA - SM)
                    $sqlUpdate = "UPDATE MATERIAL_UBICACION SET
                                    STOCK_TOTAL = STOCK_TOTAL - $rowTransferencia->CANTIDAD
                                    , STOCK_OK = STOCK_OK - " . ($rowTransferencia->ID_TIPO_BLOQUEO == NULL ? $rowTransferencia->CANTIDAD : 0) . "
                                    , STOCK_BLOQUEADO = STOCK_BLOQUEADO - " . ($rowTransferencia->ID_TIPO_BLOQUEO == NULL ? 0 : $rowTransferencia->CANTIDAD) . "
                                    WHERE ID_MATERIAL_UBICACION = $rowMatUbi->ID_MATERIAL_UBICACION";
                    $bd->ExecSQL($sqlUpdate);

                    //BUSCO LA UBICACION DESTINO (DE DONDE SE DESUBICO EL MATERIAL)
                    $GLOBALS["NotificaErrorPorEmail"] = "No";
                    $rowMatUbi                        = $bd->VerRegRest("MATERIAL_UBICACION", "ID_MATERIAL = $rowTransferencia->ID_MATERIAL AND ID_UBICACION = $rowTransferencia->ID_UBICACION_ORIGEN AND ID_MATERIAL_FISICO " . ($rowTransferencia->ID_MATERIAL_FISICO == NULL ? 'IS NULL' : " = $rowTransferencia->ID_MATERIAL_FISICO") . " AND ID_TIPO_BLOQUEO " . ($rowTransferencia->ID_TIPO_BLOQUEO == NULL ? 'IS NULL' : "= $rowTransferencia->ID_TIPO_BLOQUEO") . " AND ID_ORDEN_TRABAJO_MOVIMIENTO " . ($rowTransferencia->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? 'IS NULL' : "= $rowTransferencia->ID_ORDEN_TRABAJO_MOVIMIENTO") . " AND ID_INCIDENCIA_CALIDAD " . ($rowTransferencia->ID_INCIDENCIA_CALIDAD == NULL ? 'IS NULL' : "= $rowTransferencia->ID_INCIDENCIA_CALIDAD"), "No");
                    unset($GLOBALS["NotificaErrorPorEmail"]);
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


                //SI TIENE ALBARAN ASIGNADO LO DECREMENTO
                if ($rowMovimientoSalidaLinea->ID_ALBARAN_LINEA != NULL):
                    $albaran->quitar_linea($rowMovimientoSalidaLinea); //ESTA FUNCION VACIA EL ID_ALBARAN E ID_ALBARAN_LINEA DE LA LINEA DEL MOVIMIENTO DE SALIDA

                    /************************************ DESHABILITO TRIGGERS IMPLICADOS PORQUE SE HAN HABILITADO DENTRO DE LA FUNCION ************************************/
                    $sqlDeshabilitarTriggers = "SET @Trigger_MOVIMIENTO_SALIDA_LINEA_UPDATE_Deshabilitado = 1";
                    $bd->ExecSQL($sqlDeshabilitarTriggers);
                    /********************************** FIN DESHABILITO TRIGGERS IMPLICADOS PORQUE SE HAN HABILITADO DENTRO DE LA FUNCION **********************************/
                endif;


                //DAMOS DE BAJA EL MOVIMIENTO SALIDA LINEA
                $sqlUpdate = "UPDATE MOVIMIENTO_SALIDA_LINEA SET
                                  BAJA = 1
                                , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                WHERE ID_MOVIMIENTO_SALIDA_LINEA = $rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA_LINEA";
                $bd->ExecSQL($sqlUpdate);


                //ACTUALIZO LA CANTIDAD PENDIENTE DE SERVIR DE LA LINEA DEL PEDIDO A PREPARAR
                $sqlUpdate = "UPDATE PEDIDO_SALIDA_LINEA SET
                            CANTIDAD_PENDIENTE_SERVIR = CANTIDAD_PENDIENTE_SERVIR + $rowMovimientoSalidaLinea->CANTIDAD
                            WHERE ID_PEDIDO_SALIDA_LINEA = $rowMovimientoSalidaLinea->ID_PEDIDO_SALIDA_LINEA";
                $bd->ExecSQL($sqlUpdate);

                //GUARDAMOS EL PEDIDO
                $arrPedidoLinea[$rowMovimientoSalidaLinea->ID_PEDIDO_SALIDA_LINEA] = 1;
                $arrPedido[$rowMovimientoSalidaLinea->ID_PEDIDO_SALIDA]            = 1;

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

                    //BUSCO EL MOVIMIENTO DE SALIDA
                    $rowMovimientoSalida = $bd->VerReg("MOVIMIENTO_SALIDA", "ID_MOVIMIENTO_SALIDA", $rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA);

                    //BUSCAMOS SI QUEDAN MOVIMIENTOS ACTIVOS EN LA PREPARACION Y SI NO, LA ANULAMOS
                    $GLOBALS["NotificaErrorPorEmail"] = "No";
                    $numMovimientosActivos            = $bd->VerRegRest("MOVIMIENTO_SALIDA", "ID_ORDEN_PREPARACION = $rowMovimientoSalida->ID_ORDEN_PREPARACION  AND BAJA = 0", "No");
                    if ($numMovimientosActivos == 0):
                        //DAMOS DE BAJA LA ORDEN DE PREPARACION
                        $sqlUpdate = "UPDATE ORDEN_PREPARACION SET
                                        ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                        , BAJA = 1
                                        WHERE ID_ORDEN_PREPARACION = $rowMovimientoSalida->ID_ORDEN_PREPARACION";
                        $bd->ExecSQL($sqlUpdate);

                        // LOG MOVIMIENTOS
                        $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Anulación", "Orden preparación", $rowMovimientoSalida->ID_ORDEN_PREPARACION, "");
                    endif;
                endif;//FIN ANULAMOS MOVIMIENTO Y PREPARACION

            endwhile;

            /************************************ HABILITO TRIGGERS IMPLICADOS ************************************/
            $sqlHabilitarTriggers = "SET @Trigger_MOVIMIENTO_SALIDA_LINEA_INSERT_Deshabilitado = NULL";
            $bd->ExecSQL($sqlHabilitarTriggers);
            $sqlHabilitarTriggers = "SET @Trigger_MOVIMIENTO_SALIDA_LINEA_UPDATE_Deshabilitado = NULL";
            $bd->ExecSQL($sqlHabilitarTriggers);
            $sqlHabilitarTriggers = "SET @Trigger_PEDIDO_SALIDA_LINEA_UPDATE_Deshabilitado = NULL";
            $bd->ExecSQL($sqlHabilitarTriggers);
            /********************************** FIN HABILITO TRIGGERS IMPLICADOS **********************************/

            //MODIFICO LAS LINEAS PARA QUE SE EJECUTEN LOS TRIGGERS Y LAS LINEAS SE QUEDEN COMO CORRESPONDAN
            foreach ($arrPedidoLinea as $idPedidoLinea => $fool):
                $sqlUpdate = "UPDATE PEDIDO_SALIDA_LINEA SET BAJA = BAJA WHERE ID_PEDIDO_SALIDA_LINEA = $idPedidoLinea";
                $bd->ExecSQL($sqlUpdate);
            endforeach;

            //MODIFICO EL ESTADO DE LOS PEDIDOS
            foreach ($arrPedido as $idPedidoSalida => $fool):

                //SI EL PEDIDO DE SALIDA TIENE TODA LA CANTIDAD POR PREPARAR Y EXPEDIR LO PONGO A ESTADO GRABADO, SINO EN ENTREGA
                $numLineas = $bd->NumRegsTabla("PEDIDO_SALIDA_LINEA", "CANTIDAD_PENDIENTE_SERVIR <> CANTIDAD AND ID_PEDIDO_SALIDA = $idPedidoSalida AND INDICADOR_BORRADO IS NULL AND BAJA = 0");
                if ($numLineas > 0):
                    $actualizacion = "ESTADO = 'En Entrega'";
                else:
                    $actualizacion = "ESTADO = 'Grabado'";
                endif;

                $sqlUpdate = "UPDATE PEDIDO_SALIDA SET
                                        $actualizacion
                                        WHERE ID_PEDIDO_SALIDA = $idPedidoSalida";
                $bd->ExecSQL($sqlUpdate);
            endforeach;

            //DAMOS DE BAJA EL TRANSPORTE
            $sqlUpdate = "UPDATE ORDEN_TRANSPORTE SET BAJA = 1 WHERE ID_ORDEN_TRANSPORTE = $rowExpedicionActualizada->ID_ORDEN_TRANSPORTE";
            $bd->ExecSQL($sqlUpdate);
            //DAMOS DE BAJA LA EXPEDICION
            $sqlUpdate = "UPDATE EXPEDICION SET BAJA = 1 WHERE ID_EXPEDICION = $rowExpedicionActualizada->ID_EXPEDICION";
            $bd->ExecSQL($sqlUpdate);


        endif;//FIN SI HUBO SUMINISTRO DIRECTO, LO DESHACEMOS


        //DEVOLVEMOS LOS ERRORES
        return $arrError;
    }

    /**
     * @param $idLineaMovimientoSalida LINEA DE MOVIMIENTO DE SALIDA PARA PASAR DE REPARACION A GARANTIA O VICEVERSA
     * @param $tipoCambioEstado TIPO DE CAMBIO DE ESTADO A REALIZAR SOBRE LA LINEA DE MOVIMIENTOS DE SALIDA
     * REALIZA LAS OPERACIONES NECESARIAS PARA CONVERTIR UN MATERIAL ENVIADO A PROVEEDOR DE REPARACION A GARANTIA O VICEVERSA
     */
    function CambioEstadoEnProveedor($idLineaMovimientoSalida, $tipoCambioEstado)
    {
        global $bd;
        global $administrador;
        global $auxiliar;
        global $expedicion;
        global $albaran;

        //VARIABLE PARA SABER SI SE PRODUCEN ERRORES
        $textoError = "";
        $error      = false;

        //BUSCO LA FECHA ACTUAL
        $fechaActual = date("Y-m-d H:i:s");

        //BUSCO LA FECHA ACTUAL MAS UN SEGUNDO PARA LA ORDENACION EN TRAZABILIDAD
        $fechaActualMasUnSegundo = date("Y-m-d H:i:s", strtotime( (string)$fechaActual . " + " . 1 . " seconds"));

        //BUSCO LA LINEA EL MOVIMIENTO DE SALIDA
        $rowMovimientoSalidaLinea = $bd->VerReg("MOVIMIENTO_SALIDA_LINEA", "ID_MOVIMIENTO_SALIDA_LINEA", $idLineaMovimientoSalida);

        //BUSCO EL MOVIMIENTO DE SALIDA
        $rowMovimientoSalida = $bd->VerReg("MOVIMIENTO_SALIDA", "ID_MOVIMIENTO_SALIDA", $rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA);

        //BUSCO LA ORDEN DE RECOGIDA ORIGINAL
        $rowExpedicion = $bd->VerReg("EXPEDICION", "ID_EXPEDICION", $rowMovimientoSalida->ID_EXPEDICION);

        //ESTABLEZCO LOS DATOS DE LINEA
        $datosLinea = $auxiliar->traduce("Albaran", $administrador->ID_IDIOMA) . ": I" . ($rowMovimientoSalidaLinea->ID_ALBARAN == "" ? $rowMovimientoSalida->ID_MOVIMIENTO_SALIDA : $rowMovimientoSalidaLinea->ID_ALBARAN) . ". " . $auxiliar->traduce("Linea", $administrador->ID_IDIOMA) . ": " . $rowMovimientoSalidaLinea->LINEA_MOVIMIENTO_SAP . ".<br>";

        //CREO LA ORDEN DE RECOGIDA DEL MOVIMIENTO CORRESPONDIENTE SI NO EXISTE PREVIAMENTE
        $sqlInsert = "INSERT INTO EXPEDICION SET
                                      ID_ORDEN_TRANSPORTE = $rowExpedicion->ID_ORDEN_TRANSPORTE
                                      , ID_SOLICITUD_TRANSPORTE = " . ($rowExpedicion->ID_SOLICITUD_TRANSPORTE == NULL ? 'NULL' : $rowExpedicion->ID_SOLICITUD_TRANSPORTE) . "
                                      , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                      , ID_ADMINISTRADOR_CREACION = $administrador->ID_ADMINISTRADOR
                                      , FECHA_EXPEDICION = '" . $rowExpedicion->FECHA_EXPEDICION . "'
                                      , ID_CENTRO_FISICO = $rowExpedicion->ID_CENTRO_FISICO
                                      , ID_CENTRO_CONTRATANTE = " . ($rowExpedicion->ID_CENTRO_CONTRATANTE == NULL ? 'NULL' : $rowExpedicion->ID_CENTRO_CONTRATANTE) . "
                                      , ID_DIRECCION_ENTREGA = " . ($rowExpedicion->ID_DIRECCION_ENTREGA == NULL ? 'NULL' : $rowExpedicion->ID_DIRECCION_ENTREGA) . "
                                      , ADR = '" . $rowExpedicion->ADR . "'
                                      , NACIONAL = '" . $rowExpedicion->NACIONAL . "'
                                      , COMUNITARIO = '" . $rowExpedicion->COMUNITARIO . "'
                                      , MULTIMODAL = $rowExpedicion->MULTIMODAL
                                      , SEGURO = $rowExpedicion->SEGURO
                                      , CONTRATACION = " . ($rowExpedicion->CONTRATACION == NULL ? 'NULL' : "'" . $rowExpedicion->CONTRATACION . "'") . "
                                      , TIPO_ORDEN_RECOGIDA = '" . $rowExpedicion->TIPO_ORDEN_RECOGIDA . "'
                                      , SUBTIPO_ORDEN_RECOGIDA = '" . $rowExpedicion->SUBTIPO_ORDEN_RECOGIDA . "'
                                      , TIPO_EXPEDICION = '" . $rowExpedicion->TIPO_EXPEDICION . "'
                                      , TIPO_GENERACION = '" . $rowExpedicion->TIPO_GENERACION . "'
                                      , TIPO_TRANSPORTE = '" . $rowExpedicion->TIPO_TRANSPORTE . "'
                                      , CARGA = " . ($rowExpedicion->CARGA == NULL ? 'NULL' : "'" . $rowExpedicion->CARGA . "'") . "
                                      , ID_TRANSPORTISTA = $rowExpedicion->ID_TRANSPORTISTA
                                      , ID_CENTRO_FISICO_DESTINO = " . ($rowExpedicion->ID_CENTRO_FISICO_DESTINO == NULL ? 'NULL' : $rowExpedicion->ID_CENTRO_FISICO_DESTINO) . "
                                      , ESTADO = 'Recepcionada'
                                      , FECHA = '" . $rowExpedicion->FECHA . "'
                                      , HORA = '" . $rowExpedicion->HORA . "'
                                      , CON_BULTOS = $rowExpedicion->CON_BULTOS
                                      , NUM_BULTOS = $rowExpedicion->NUM_BULTOS
                                      , ALBARAN_TRANSPORTE = '" . $rowExpedicion->ALBARAN_TRANSPORTE . "'
                                      , MATRICULA = '" . $rowExpedicion->MATRICULA . "'
                                      , ID_AGENCIA = " . ($rowExpedicion->ID_AGENCIA == NULL ? 'NULL' : $rowExpedicion->ID_AGENCIA) . "
                                      , ID_CHOFER = " . ($rowExpedicion->ID_CHOFER == NULL ? 'NULL' : $rowExpedicion->ID_CHOFER) . "
                                      , PESO = $rowExpedicion->PESO
                                      , MATRICULA_REMOLQUE = '" . $rowExpedicion->MATRICULA_REMOLQUE . "'
                                      , OBSERVACIONES = '" . $rowExpedicion->OBSERVACIONES . "'
                                      , OBSERVACIONES_DOCUMENTO_MULTIMODAL = '" . $rowExpedicion->OBSERVACIONES_DOCUMENTO_MULTIMODAL . "'
                                      , DESCRIPCION_ORDEN_TRANSPORTE = '" . $rowExpedicion->DESCRIPCION_ORDEN_TRANSPORTE . "'
                                      , OBSERVACIONES_ESPECIALES_ENTREGA = '" . $rowExpedicion->OBSERVACIONES_ESPECIALES_ENTREGA . "'
                                      , GESTIONA_FACTURAS = $rowExpedicion->GESTIONA_FACTURAS
                                      , ESTADO_FACTURA_NO_COMERCIAL = " . ($rowExpedicion->ESTADO_FACTURA_NO_COMERCIAL == NULL ? 'NULL' : "'" . $rowExpedicion->ESTADO_FACTURA_NO_COMERCIAL . "'") . "
                      ON DUPLICATE KEY UPDATE
                                      ID_ORDEN_TRANSPORTE = $rowExpedicion->ID_ORDEN_TRANSPORTE
                                      , ID_SOLICITUD_TRANSPORTE = " . ($rowExpedicion->ID_SOLICITUD_TRANSPORTE == NULL ? 'NULL' : $rowExpedicion->ID_SOLICITUD_TRANSPORTE) . "
                                      , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                      , ID_ADMINISTRADOR_CREACION = $administrador->ID_ADMINISTRADOR
                                      , FECHA_EXPEDICION = '" . $rowExpedicion->FECHA_EXPEDICION . "'
                                      , ID_CENTRO_FISICO = $rowExpedicion->ID_CENTRO_FISICO
                                      , ID_CENTRO_CONTRATANTE = " . ($rowExpedicion->ID_CENTRO_CONTRATANTE == NULL ? 'NULL' : $rowExpedicion->ID_CENTRO_CONTRATANTE) . "
                                      , ID_DIRECCION_ENTREGA = " . ($rowExpedicion->ID_DIRECCION_ENTREGA == NULL ? 'NULL' : $rowExpedicion->ID_DIRECCION_ENTREGA) . "
                                      , ADR = '" . $rowExpedicion->ADR . "'
                                      , NACIONAL = '" . $rowExpedicion->NACIONAL . "'
                                      , COMUNITARIO = '" . $rowExpedicion->COMUNITARIO . "'
                                      , MULTIMODAL = $rowExpedicion->MULTIMODAL
                                      , SEGURO = $rowExpedicion->SEGURO
                                      , CONTRATACION = " . ($rowExpedicion->CONTRATACION == NULL ? 'NULL' : "'" . $rowExpedicion->CONTRATACION . "'") . "
                                      , TIPO_ORDEN_RECOGIDA = '" . $rowExpedicion->TIPO_ORDEN_RECOGIDA . "'
                                      , SUBTIPO_ORDEN_RECOGIDA = '" . $rowExpedicion->SUBTIPO_ORDEN_RECOGIDA . "'
                                      , TIPO_EXPEDICION = '" . $rowExpedicion->TIPO_EXPEDICION . "'
                                      , TIPO_GENERACION = '" . $rowExpedicion->TIPO_GENERACION . "'
                                      , TIPO_TRANSPORTE = '" . $rowExpedicion->TIPO_TRANSPORTE . "'
                                      , CARGA = " . ($rowExpedicion->CARGA == NULL ? 'NULL' : "'" . $rowExpedicion->CARGA . "'") . "
                                      , ID_TRANSPORTISTA = $rowExpedicion->ID_TRANSPORTISTA
                                      , ID_CENTRO_FISICO_DESTINO = " . ($rowExpedicion->ID_CENTRO_FISICO_DESTINO == NULL ? 'NULL' : $rowExpedicion->ID_CENTRO_FISICO_DESTINO) . "
                                      , ESTADO = 'Recepcionada'
                                      , FECHA = '" . $rowExpedicion->FECHA . "'
                                      , HORA = '" . $rowExpedicion->HORA . "'
                                      , CON_BULTOS = $rowExpedicion->CON_BULTOS
                                      , NUM_BULTOS = $rowExpedicion->NUM_BULTOS
                                      , ALBARAN_TRANSPORTE = '" . $rowExpedicion->ALBARAN_TRANSPORTE . "'
                                      , MATRICULA = '" . $rowExpedicion->MATRICULA . "'
                                      , ID_AGENCIA = " . ($rowExpedicion->ID_AGENCIA == NULL ? 'NULL' : $rowExpedicion->ID_AGENCIA) . "
                                      , ID_CHOFER = " . ($rowExpedicion->ID_CHOFER == NULL ? 'NULL' : $rowExpedicion->ID_CHOFER) . "
                                      , PESO = $rowExpedicion->PESO
                                      , MATRICULA_REMOLQUE = '" . $rowExpedicion->MATRICULA_REMOLQUE . "'
                                      , OBSERVACIONES = '" . $rowExpedicion->OBSERVACIONES . "'
                                      , OBSERVACIONES_DOCUMENTO_MULTIMODAL = '" . $rowExpedicion->OBSERVACIONES_DOCUMENTO_MULTIMODAL . "'
                                      , DESCRIPCION_ORDEN_TRANSPORTE = '" . $rowExpedicion->DESCRIPCION_ORDEN_TRANSPORTE . "'
                                      , OBSERVACIONES_ESPECIALES_ENTREGA = '" . $rowExpedicion->OBSERVACIONES_ESPECIALES_ENTREGA . "'
                                      , GESTIONA_FACTURAS = $rowExpedicion->GESTIONA_FACTURAS
                                      , ESTADO_FACTURA_NO_COMERCIAL = " . ($rowExpedicion->ESTADO_FACTURA_NO_COMERCIAL == NULL ? 'NULL' : "'" . $rowExpedicion->ESTADO_FACTURA_NO_COMERCIAL . "'");
        $bd->ExecSQL($sqlInsert);
        $idNuevaExpedicion = $bd->IdAsignado();//ANTERIORMENTE SE LLAMABA $idExpedicionReparacion

        //BUSCO EL PEDIDO SALIDA ORIGINAL
        $rowPedidoSalidaOriginal = $bd->VerReg("PEDIDO_SALIDA", "ID_PEDIDO_SALIDA", $rowMovimientoSalida->ID_PEDIDO_SALIDA); //ANTES ERA $rowPedidoSalidaGarantia

        //CREO EL PEDIDO CORRESPONDIENTE SI NO EXISTE PREVIAMENTE
        $sqlInsert = "INSERT INTO PEDIDO_SALIDA SET
                        TIPO_PEDIDO = 'Material Estropeado a Proveedor'
                        , TIPO_PEDIDO_SAP = NULL
                        , TIPO_TRASLADO = 'Logística Inversa'
                        , ID_CENTRO_ORIGEN = $rowPedidoSalidaOriginal->ID_CENTRO_ORIGEN
                        , ID_PROVEEDOR = $rowPedidoSalidaOriginal->ID_PROVEEDOR
                        , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                        , FECHA_CREACION = '" . $rowPedidoSalidaOriginal->FECHA_CREACION . "'
                        , ESTADO = 'Finalizado'
                     ON DUPLICATE KEY UPDATE
                        TIPO_PEDIDO = 'Material Estropeado a Proveedor'
                        , TIPO_PEDIDO_SAP = NULL
                        , TIPO_TRASLADO = 'Logística Inversa'
                        , ID_CENTRO_ORIGEN = $rowPedidoSalidaOriginal->ID_CENTRO_ORIGEN
                        , ID_PROVEEDOR = $rowPedidoSalidaOriginal->ID_PROVEEDOR
                        , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                        , FECHA_CREACION = '" . $rowPedidoSalidaOriginal->FECHA_CREACION . "'
                        , ESTADO = 'Finalizado' ";
        $bd->ExecSQL($sqlInsert);
        $idNuevoPedidoSalida = $bd->IdAsignado();//ANTES ERA $idPedidoReparacion

        //CREO LA CABECERA DEL MOVIMIENTO CORRESPONDIENTE SI NO EXISTE PREVIAMENTE
        $sqlInsert = "INSERT INTO MOVIMIENTO_SALIDA SET
                        FECHA_EXPEDICION_REAL = '" . $fechaActualMasUnSegundo . "'
                        , ID_EXPEDICION = $idNuevaExpedicion
                        , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                        , ID_PEDIDO_SALIDA = $idNuevoPedidoSalida
                        , ID_ORDEN_PREPARACION = $rowMovimientoSalida->ID_ORDEN_PREPARACION
                        , ESTADO = 'Recepcionado'
                        , FECHA = '" . $fechaActualMasUnSegundo . "'
                        , FECHA_EXPEDICION = '" . $fechaActualMasUnSegundo . "'
                        , TIPO_MOVIMIENTO = 'MaterialEstropeadoAProveedor'
                        , ID_ALMACEN_DESTINO = " . ($rowMovimientoSalida->ID_ALMACEN_DESTINO == NULL ? 'NULL' : $rowMovimientoSalida->ID_ALMACEN_DESTINO) . "
                        , ID_PROVEEDOR = $rowMovimientoSalida->ID_PROVEEDOR
                        , BULTOS = $rowMovimientoSalida->BULTOS
                        , TIPO_ENVIO_A_PROVEEDOR = '" . ($tipoCambioEstado == "CambioDeGarantiaAReparacion" ? "Reparación" : "Garantía") . "'
                      ON DUPLICATE KEY UPDATE
                        FECHA_EXPEDICION_REAL = '" . $fechaActualMasUnSegundo . "'
                        , ID_EXPEDICION = $idNuevaExpedicion
                        , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                        , ID_PEDIDO_SALIDA = $idNuevoPedidoSalida
                        , ID_ORDEN_PREPARACION = $rowMovimientoSalida->ID_ORDEN_PREPARACION
                        , ESTADO = 'Recepcionado'
                        , FECHA = '" . $fechaActualMasUnSegundo . "'
                        , FECHA_EXPEDICION = '" . $fechaActualMasUnSegundo . "'
                        , TIPO_MOVIMIENTO = 'MaterialEstropeadoAProveedor'
                        , ID_ALMACEN_DESTINO = " . ($rowMovimientoSalida->ID_ALMACEN_DESTINO == NULL ? 'NULL' : $rowMovimientoSalida->ID_ALMACEN_DESTINO) . "
                        , ID_PROVEEDOR = $rowMovimientoSalida->ID_PROVEEDOR
                        , BULTOS = $rowMovimientoSalida->BULTOS
                        , TIPO_ENVIO_A_PROVEEDOR = '" . ($tipoCambioEstado == "CambioDeGarantiaAReparacion" ? "Reparación" : "Garantía") . "'";
        $bd->ExecSQL($sqlInsert);
        $idNuevoMovimientoSalida = $bd->IdAsignado();//ANTES ERA $idMovimientoReparacion


        //COMPRUEBO QUE LA LINEA NO TENGA DECISION TOMADA O LA DECISION SEA REPARAR
        if (($rowMovimientoSalidaLinea->DECISION_COMPRADOR != NULL) && ($rowMovimientoSalidaLinea->DECISION_COMPRADOR != 'Reparar')):
            $textoError = $textoError . $auxiliar->traduce("Esta línea ya tiene una decisión tomada por el comprador", $administrador->ID_IDIOMA) . ". " . $datosLinea . "<br>";
            $error      = true;
        endif;
        //$html->PagErrorCondicionado($rowMovLinea->DECISION_COMPRADOR, "!=", NULL, "LineaConDecisionTomada");

        //BUSCO EL MATERIAL UBICACION DE PROVEEDOR
        $rowMatUbi = $bd->VerRegRest("MATERIAL_UBICACION", "ID_MATERIAL = $rowMovimientoSalidaLinea->ID_MATERIAL AND ID_UBICACION = $rowMovimientoSalidaLinea->ID_UBICACION_DESTINO AND ID_MATERIAL_FISICO " . ($rowMovimientoSalidaLinea->ID_MATERIAL_FISICO == NULL ? 'IS NULL' : "= $rowMovimientoSalidaLinea->ID_MATERIAL_FISICO") . " AND ID_TIPO_BLOQUEO = $rowMovimientoSalidaLinea->ID_TIPO_BLOQUEO AND ID_ORDEN_TRABAJO_MOVIMIENTO = $rowMovimientoSalidaLinea->ID_ORDEN_TRABAJO_MOVIMIENTO AND ID_INCIDENCIA_CALIDAD " . ($rowMovimientoSalidaLinea->ID_INCIDENCIA_CALIDAD == NULL ? 'IS NULL' : "= $rowMovimientoSalidaLinea->ID_INCIDENCIA_CALIDAD"), "No");
        if (($error == false) && ($rowMatUbi == false)):
            $textoError = $textoError . $auxiliar->traduce("El material en la ubicación del proveedor no existe", $administrador->ID_IDIOMA) . ". " . $datosLinea . "<br>";
            $error      = true;
        ////COMPRUEBO QUE EXISTA EL REGISTRO MATERIAL UBICACION
        //$html->PagErrorCondicionado($rowMatUbi, "==", false, "MaterialUbicacionProveedorNoExiste");
        elseif (($error == false) && ($rowMatUbi->STOCK_TOTAL < $rowMovimientoSalidaLinea->CANTIDAD)):
            $textoError = $textoError . $auxiliar->traduce("No hay suficiente stock para realizar esta acción", $administrador->ID_IDIOMA) . ". " . $datosLinea . "<br>";
            $error      = true;
            ////COMPRUEBO QUE HAYA STOCK SUFICIENTE
            //$html->PagErrorCondicionado($rowMatUbi->STOCK_TOTAL, "<", $rowMovLinea->CANTIDAD, "StockInsuficiente");
        endif;

        //BUSCO EL ASIENTO DE RECEPCION DE MATERIAL EN PROVEEDOR
        $rowAsientoRecepcion = $bd->VerRegRest("ASIENTO", "TIPO_ASIENTO = 'Recepción de Material Estropeado en Proveedor' AND BAJA = 0 AND ID_MOVIMIENTO_SALIDA_LINEA = $rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA_LINEA", "No");
        if (($error == false) && ($rowAsientoRecepcion == false)):
            $textoError = $textoError . $auxiliar->traduce("El asiento de recepción de material estropeado en proveedor no ha podido ser localizado", $administrador->ID_IDIOMA) . ". " . $datosLinea . "<br>";
            $error      = true;
            //$html->PagErrorCondicionado($rowAsientoRecepcion, "==", false, "AsientoRecepcionNoExiste");
        endif;


        //SI NO SE HAN PRODUCIDO ERRORES REALIZO LAS ACCIONES CORRESPONDIENTES
        if ($error == false):
            //DECREMENTO EL STOCK DE UN TIPO DE BLOQUEO EN MATERIAL UBICACION DE PROVEEDOR
            $sqlUpdate = "UPDATE MATERIAL_UBICACION SET
                            STOCK_TOTAL = STOCK_TOTAL - $rowMovimientoSalidaLinea->CANTIDAD
                            , STOCK_BLOQUEADO = STOCK_BLOQUEADO - $rowMovimientoSalidaLinea->CANTIDAD
                            WHERE ID_MATERIAL_UBICACION = $rowMatUbi->ID_MATERIAL_UBICACION";
            $bd->ExecSQL($sqlUpdate);

            //DOY DE BAJA LA LINEA DEL MOVIMIENTO DE SALIDA ORIGINAL Y LA DESASIGNO DE LA EXPEDICION SAP
            $sqlUpdate = "UPDATE MOVIMIENTO_SALIDA_LINEA SET
                            BAJA = 1
                            , DECISION_COMPRADOR = '" . ($tipoCambioEstado == "CambioDeGarantiaAReparacion" ? "Paso de Garantía a Reparación" : "Paso de Reparación a Garantía") . "'
                            , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                            , EXPEDICION_SAP = ''
                            , ID_EXPEDICION_SAP = NULL
                            WHERE ID_MOVIMIENTO_SALIDA_LINEA = $rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA_LINEA";
            $bd->ExecSQL($sqlUpdate);

            //DOY DE BAJA EL MOVIMIENTO ORIGINAL SI SE QUEDA SIN LINEAS
            $numLineasMovimientoActivas = $bd->NumRegsTabla("MOVIMIENTO_SALIDA_LINEA", "ID_MOVIMIENTO_SALIDA = $rowMovimientoSalida->ID_MOVIMIENTO_SALIDA AND BAJA = 0 AND LINEA_ANULADA = 0");
            if ($numLineasMovimientoActivas == 0):
                $sqlUpdate = "UPDATE MOVIMIENTO_SALIDA SET BAJA = 1 WHERE ID_MOVIMIENTO_SALIDA = $rowMovimientoSalida->ID_MOVIMIENTO_SALIDA";
                $bd->ExecSQL($sqlUpdate);
            endif;

            //DOY DE BAJA EL ASIENTO DE RECEPCION DE MATERIAL EN PROVEEDOR
            $sqlUpdate = "UPDATE ASIENTO SET
                            BAJA = 1
                            , FECHA_BAJA = '" . $fechaActual . "'
                            WHERE ID_ASIENTO = $rowAsientoRecepcion->ID_ASIENTO";
            $bd->ExecSQL($sqlUpdate);

            //BUSCO EL NUEVO TIPO DE BLOQUEO EN FUNCION DEL TIPO DE CAMBIO SOLICITADO
            if ($tipoCambioEstado == "CambioDeGarantiaAReparacion"):
                //BUSCO EL NUEVO TIPO DE BLOQUEO (REPARACION NO EN GARANTIA O RETENIDO POR CALIDAD NO PREVENTIVO REPARABLE NO EN GARANTIA)
                $rowNuevoTipoBloqueo = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "QRNG");
                if ($rowMovimientoSalidaLinea->ID_INCIDENCIA_CALIDAD != NULL):
                    $rowNuevoTipoBloqueo = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "XRCRNG");
                endif;
            else:
                //BUSCO EL NUEVO TIPO DE BLOQUEO (REPARACION EN GARANTIA O RETENIDO POR CALIDAD NO PREVENTIVO REPARABLE EN GARANTIA)
                $rowNuevoTipoBloqueo = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "QRG");
                if ($rowMovimientoSalidaLinea->ID_INCIDENCIA_CALIDAD != NULL):
                    $rowNuevoTipoBloqueo = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "XRCRG");
                endif;
            endif;

            //REALIZO EL CAMBIO DE ESTADO EN LA UBICACION DE DONDE SALIO EL MATERIAL
            $sqlInsert = "INSERT INTO CAMBIO_ESTADO SET
                            FECHA = '" . $fechaActual . "'
                            , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                            , ID_MATERIAL = $rowMovimientoSalidaLinea->ID_MATERIAL
                            , ID_MATERIAL_FISICO = " . ($rowMovimientoSalidaLinea->ID_MATERIAL_FISICO == NULL ? "NULL" : $rowMovimientoSalidaLinea->ID_MATERIAL_FISICO) . "
                            , ID_UBICACION = $rowMovimientoSalidaLinea->ID_UBICACION
                            , CANTIDAD = $rowMovimientoSalidaLinea->CANTIDAD
                            , ID_TIPO_BLOQUEO_INICIAL = $rowMovimientoSalidaLinea->ID_TIPO_BLOQUEO
                            , ID_TIPO_BLOQUEO_FINAL = $rowNuevoTipoBloqueo->ID_TIPO_BLOQUEO
                            , ID_ORDEN_TRABAJO_MOVIMIENTO = " . ($rowMovimientoSalidaLinea->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? "NULL" : $rowMovimientoSalidaLinea->ID_ORDEN_TRABAJO_MOVIMIENTO) . "
                            , ID_INCIDENCIA_CALIDAD = " . ($rowMovimientoSalidaLinea->ID_INCIDENCIA_CALIDAD == NULL ? "NULL" : $rowMovimientoSalidaLinea->ID_INCIDENCIA_CALIDAD) . "
                            , OBSERVACIONES = 'Cambio de línea de " . ($tipoCambioEstado == "CambioDeGarantiaAReparacion" ? "movimiento en garantia a movimiento en reparación" : "movimiento en reparación a movimiento en garantia") . "\nMovimiento original: $rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA - Línea: $rowMovimientoSalidaLinea->LINEA_MOVIMIENTO_SAP'";
            $bd->ExecSQL($sqlInsert);
            $idNuevoCambioEstado = $bd->IdAsignado();

            //BUSCO EL MAXIMO NUMERO DE LINEA DE PEDIDO DE REPARACION
            $UltimoNumeroLinea       = 0;
            $sqlUltimoNumeroLinea    = "SELECT MAX(CAST(LINEA_PEDIDO_SAP AS UNSIGNED)) AS NUMERO_LINEA FROM PEDIDO_SALIDA_LINEA WHERE ID_PEDIDO_SALIDA = " . $idNuevoPedidoSalida;
            $resultUltimoNumeroLinea = $bd->ExecSQL($sqlUltimoNumeroLinea);
            if ($resultUltimoNumeroLinea != false):
                $rowUltimoNumeroLinea = $bd->SigReg($resultUltimoNumeroLinea);
                if ($rowUltimoNumeroLinea->NUMERO_LINEA != NULL):
                    $UltimoNumeroLinea = $rowUltimoNumeroLinea->NUMERO_LINEA;
                endif;
            endif;

            //INCREMENTO EN 10 EL NUMERO DE LINEA
            $SiguienteNumeroLinea = $UltimoNumeroLinea + 10;

            //BUSCO LA LINEA DEL PEDIDO DE SALIDA QUE GENERO EL ENVIO A PROVEEDOR ORIGINAL
            $rowPedidoSalidaLinea = $bd->VerReg("PEDIDO_SALIDA_LINEA", "ID_PEDIDO_SALIDA_LINEA", $rowMovimientoSalidaLinea->ID_PEDIDO_SALIDA_LINEA);

            //CREO LA LINEA DEL PEDIDO DE SALIDA
            $sqlInsert = "INSERT INTO PEDIDO_SALIDA_LINEA SET
                            ID_PEDIDO_SALIDA = $idNuevoPedidoSalida
                            , ID_ALMACEN_ORIGEN = $rowPedidoSalidaLinea->ID_ALMACEN_ORIGEN
                            , ID_MATERIAL = $rowPedidoSalidaLinea->ID_MATERIAL
                            , ID_TIPO_BLOQUEO = $rowNuevoTipoBloqueo->ID_TIPO_BLOQUEO
                            , ID_UNIDAD = $rowPedidoSalidaLinea->ID_UNIDAD
                            , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                            , LINEA_PEDIDO_SAP = '" . str_pad( (string)$SiguienteNumeroLinea, 5, "0", STR_PAD_LEFT) . "'
                            , CANTIDAD = $rowPedidoSalidaLinea->CANTIDAD
                            , FECHA_ENTREGA = '" . $rowPedidoSalidaLinea->FECHA_ENTREGA . "'
                            , ESTADO = 'Finalizada'";
            $bd->ExecSQL($sqlInsert);
            $idNuevoPedidoSalidaLinea = $bd->IdAsignado();//ANTES ERA $idPedidoLineaReparacion

            //BUSCO EL MAXIMO NUMERO DE LINEA DEL NUEVO MOVIMIENTO
            $UltimoNumeroLinea       = 0;
            $sqlUltimoNumeroLinea    = "SELECT MAX(CAST(LINEA_MOVIMIENTO_SAP AS UNSIGNED)) AS NUMERO_LINEA FROM MOVIMIENTO_SALIDA_LINEA WHERE ID_MOVIMIENTO_SALIDA = $idNuevoMovimientoSalida";
            $resultUltimoNumeroLinea = $bd->ExecSQL($sqlUltimoNumeroLinea);
            if ($resultUltimoNumeroLinea != false):
                $rowUltimoNumeroLinea = $bd->SigReg($resultUltimoNumeroLinea);
                if ($rowUltimoNumeroLinea->NUMERO_LINEA != NULL):
                    $UltimoNumeroLinea = $rowUltimoNumeroLinea->NUMERO_LINEA;
                endif;
            endif;

            $SiguienteNumeroLinea = $UltimoNumeroLinea + 10;

            //CREO LA LINEA DEL MOVIMIENTO DE SALIDA NUEVO
            $sqlInsert = "INSERT INTO MOVIMIENTO_SALIDA_LINEA SET
                            FECHA_EXPEDICION = '" . $fechaActualMasUnSegundo . "'
                            , ID_EXPEDICION = $idNuevaExpedicion
                            , EXPEDICION_SAP = ''
                            , ID_EXPEDICION_SAP = NULL
                            , ID_ALBARAN = NULL
                            , ID_ALBARAN_LINEA = NULL
                            , ID_MOVIMIENTO_SALIDA = $idNuevoMovimientoSalida
                            , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                            , FECHA = '" . $fechaActualMasUnSegundo . "'
                            , FECHA_TRANSMISION_A_SAP_TEORICA = '" . $rowMovimientoSalidaLinea->FECHA_TRANSMISION_A_SAP_TEORICA . "'
                            , FECHA_PREPARACION = '" . $rowMovimientoSalidaLinea->FECHA_PREPARACION . "'
                            , LINEA_MOVIMIENTO_SAP = '" . str_pad( (string)$SiguienteNumeroLinea, 5, "0", STR_PAD_LEFT) . "'
                            , ESTADO = '" . $rowMovimientoSalidaLinea->ESTADO . "'
                            , ID_ALMACEN = $rowMovimientoSalidaLinea->ID_ALMACEN
                            , ID_UBICACION = $rowMovimientoSalidaLinea->ID_UBICACION
                            , ID_MATERIAL = $rowMovimientoSalidaLinea->ID_MATERIAL
                            , ID_MATERIAL_FISICO = " . ($rowMovimientoSalidaLinea->ID_MATERIAL_FISICO == NULL ? 'NULL' : "$rowMovimientoSalidaLinea->ID_MATERIAL_FISICO") . "
                            , ID_PEDIDO_SALIDA = $idNuevoPedidoSalida
                            , ID_PEDIDO_SALIDA_LINEA = $idNuevoPedidoSalidaLinea
                            , TIPO_LOTE = '" . ($rowMovimientoSalidaLinea->TIPO_LOTE == NULL ? 'ninguno' : "$rowMovimientoSalidaLinea->TIPO_LOTE") . "'
                            , CANTIDAD = $rowMovimientoSalidaLinea->CANTIDAD
                            , CANTIDAD_PEDIDO = $rowMovimientoSalidaLinea->CANTIDAD_PEDIDO
                            , CANTIDAD_PENDIENTE_DE_RECEPCIONAR_EN_DESTINO = $rowMovimientoSalidaLinea->CANTIDAD_PENDIENTE_DE_RECEPCIONAR_EN_DESTINO
                            , ID_ALMACEN_DESTINO = $rowMovimientoSalidaLinea->ID_ALMACEN_DESTINO
                            , ID_UBICACION_DESTINO = $rowMovimientoSalidaLinea->ID_UBICACION_DESTINO
                            , ID_TIPO_BLOQUEO = $rowNuevoTipoBloqueo->ID_TIPO_BLOQUEO
                            , ID_ORDEN_TRABAJO_MOVIMIENTO = " . ($rowMovimientoSalidaLinea->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? 'NULL' : "$rowMovimientoSalidaLinea->ID_ORDEN_TRABAJO_MOVIMIENTO") . "
                            , ID_INCIDENCIA_CALIDAD = " . ($rowMovimientoSalidaLinea->ID_INCIDENCIA_CALIDAD == NULL ? 'NULL' : "$rowMovimientoSalidaLinea->ID_INCIDENCIA_CALIDAD") . "
                            , ID_PROVEEDOR_GARANTIA = " . ($rowMovimientoSalidaLinea->ID_PROVEEDOR_GARANTIA == NULL ? 'NULL' : "$rowMovimientoSalidaLinea->ID_PROVEEDOR_GARANTIA") . "
                            , ENVIADO_SAP = 1";
            $bd->ExecSQL($sqlInsert);
            $idNuevoMovimientoSalidaLinea  = $bd->IdAsignado();//ANTES ERA $idMovimientoLineaReparacion
            $rowNuevoMovimientoSalidaLinea = $bd->VerReg("MOVIMIENTO_SALIDA_LINEA", "ID_MOVIMIENTO_SALIDA_LINEA", $idNuevoMovimientoSalidaLinea);

            //SI LA FECHA EXPEDICION INFORME FACTURACION ES CERO, LA ACTUALIZO
            if ($rowNuevoMovimientoSalidaLinea->FECHA_EXPEDICION_INFORME_FACTURACION == "0000-00-00 00:00:00"):
                $sqlUpdate = "UPDATE MOVIMIENTO_SALIDA_LINEA SET
                                FECHA_EXPEDICION_INFORME_FACTURACION = '" . $fechaActualMasUnSegundo . "'
                              WHERE ID_MOVIMIENTO_SALIDA_LINEA = $idNuevoMovimientoSalidaLinea";
                $bd->ExecSQL($sqlUpdate);
            endif;

            //DOY DE BAJA LA LINEA DEL PEDIDO DE SALIDA ORIGINAL
            $sqlUpdate = "UPDATE PEDIDO_SALIDA_LINEA SET
                          BAJA = 1
                          WHERE ID_PEDIDO_SALIDA_LINEA = $rowMovimientoSalidaLinea->ID_PEDIDO_SALIDA_LINEA";
            $bd->ExecSQL($sqlUpdate);

            //BUSCO LA NUEVA LINEA DEL PEDIDO DE SALIDA GENERADA
            $rowNuevaPedidoSalidaLinea = $bd->VerReg("PEDIDO_SALIDA_LINEA", "ID_PEDIDO_SALIDA_LINEA", $idNuevoPedidoSalidaLinea);//ANTES ERA $rowPedidoSalidaLinea

            //QUITO LA LINEA ORIGINAL DEL ALBARAN
            $albaran->quitar_linea($rowMovimientoSalidaLinea);

            //GENERO/AÑADO LA LINEA DE ALBARAN
            $idAlbaranNuevo = $albaran->anadir_linea($idNuevaExpedicion, $rowNuevoMovimientoSalidaLinea, $rowNuevaPedidoSalidaLinea);

            //BUSCAMOS LA EXPEDICION SAP DEL MOVIMIENTO ORIGINAL
            $GLOBALS["NotificaErrorPorEmail"] = "No";
            $rowExpedicionSAP                 = $bd->VerReg("EXPEDICION_SAP", "ID_EXPEDICION_SAP", $rowMovimientoSalidaLinea->ID_EXPEDICION_SAP);
            unset($GLOBALS["NotificaErrorPorEmail"]);

            //SI LA EXPEDICION SAP NO TIENE LINEAS ACTIVAS ASIGNADAS LA DOY DE BAJA
            $numLineasActivasExpedicionSAP = $bd->NumRegsTabla("MOVIMIENTO_SALIDA_LINEA", "ID_EXPEDICION_SAP = $rowExpedicionSAP->ID_EXPEDICION_SAP AND LINEA_ANULADA = 0 AND BAJA = 0");
            if ($numLineasActivasExpedicionSAP == 0):
                $sqlUpdate = "UPDATE EXPEDICION_SAP SET BAJA = 1 WHERE ID_EXPEDICION_SAP = $rowExpedicionSAP->ID_EXPEDICION_SAP";
                $bd->ExecSQL($sqlUpdate);
            endif;

            //REVISO SI EXISTE UNA EXPEDICION_SAP VALIDA, SINO PARA CREAR UNA
            $GLOBALS["NotificaErrorPorEmail"] = "No";
            $rowExpedicionSAPValida           = $bd->VerRegRest("EXPEDICION_SAP", "ID_ORDEN_TRANSPORTE = $rowExpedicionSAP->ID_ORDEN_TRANSPORTE AND ID_EXPEDICION = $idNuevaExpedicion AND ID_MOVIMIENTO_SALIDA " . ($rowExpedicionSAP->ID_MOVIMIENTO_SALIDA == NULL ? 'IS NULL' : "= $idNuevoMovimientoSalida") . " AND ID_BULTO " . ($rowExpedicionSAP->ID_BULTO == NULL ? 'IS NULL' : "= $rowExpedicionSAP->ID_BULTO") . " AND ID_ALBARAN = $idAlbaranNuevo AND ID_PEDIDO_SALIDA = $rowNuevaPedidoSalidaLinea->ID_PEDIDO_SALIDA", "No");
            unset($GLOBALS["NotificaErrorPorEmail"]);
            if ($rowExpedicionSAPValida == false):
                $sqlInsert = "INSERT INTO EXPEDICION_SAP SET
                              ID_ORDEN_TRANSPORTE = $rowExpedicionSAP->ID_ORDEN_TRANSPORTE
                              , ID_EXPEDICION = $idNuevaExpedicion
                              , ID_MOVIMIENTO_SALIDA = " . ($rowExpedicionSAP->ID_MOVIMIENTO_SALIDA == NULL ? 'NULL' : $idNuevoMovimientoSalida) . "
                              , ID_BULTO = " . ($rowExpedicionSAP->ID_BULTO == NULL ? 'NULL' : $rowExpedicionSAP->ID_BULTO) . "
                              , ID_ALBARAN = $idAlbaranNuevo
                              , ID_PEDIDO_SALIDA = $rowNuevaPedidoSalidaLinea->ID_PEDIDO_SALIDA";
                $bd->ExecSQL($sqlInsert);
                $idExpedicionSAPValida  = $bd->IdAsignado();
                $rowExpedicionSAPValida = $bd->VerReg("EXPEDICION_SAP", "ID_EXPEDICION_SAP", $idExpedicionSAPValida);
            endif;

            //BUSCO LA LINEA DEL MOVIMIENTO DE SALIDA PREVIO A LA ACTUALIZACION
            $rowLineaMovimientoSalidaPrevioActualizacion = $bd->VerReg("MOVIMIENTO_SALIDA_LINEA", "ID_MOVIMIENTO_SALIDA_LINEA", $idNuevoMovimientoSalidaLinea);

            //ACTUALIZO LA LINEA DE MOVIMIENTO CON LA EXPEDICION SAP
            $sqlUpdate = "UPDATE MOVIMIENTO_SALIDA_LINEA SET
                          EXPEDICION_SAP = '" . $rowExpedicionSAPValida->ID_ORDEN_TRANSPORTE . "_" . $idExpedicionSAPValida . "'
                          , ID_EXPEDICION_SAP = $rowExpedicionSAPValida->ID_EXPEDICION_SAP
                          WHERE ID_MOVIMIENTO_SALIDA_LINEA = $idNuevoMovimientoSalidaLinea";
            $bd->ExecSQL($sqlUpdate);

            //BUSCO LA LINEA DEL MOVIMIENTO DE SALIDA POSTERIOR A LA ACTUALIZACION
            $rowLineaMovimientoSalidaPosteriorActualizacion = $bd->VerReg("MOVIMIENTO_SALIDA_LINEA", "ID_MOVIMIENTO_SALIDA_LINEA", $idNuevoMovimientoSalidaLinea);

            // LOG MOVIMIENTOS
            $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Mov. salida", $rowLineaMovimientoSalidaPrevioActualizacion->ID_MOVIMIENTO_SALIDA, "Asignar expedicion SAP a la linea de movimiento con ID: $idNuevoMovimientoSalidaLinea", "MOVIMIENTO_SALIDA_LINEA", $rowLineaMovimientoSalidaPrevioActualizacion, $rowLineaMovimientoSalidaPosteriorActualizacion);

            //GENERO EL ASIENTO EN PROVEEDOR PARA INCREMENTAR EL STOCK
            $sql = "INSERT INTO ASIENTO SET
                    ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                    , ID_MATERIAL = $rowMovimientoSalidaLinea->ID_MATERIAL
                    , TIPO_LOTE = '" . ($rowMovimientoSalidaLinea->TIPO_LOTE == NULL ? 'ninguno' : "$rowMovimientoSalidaLinea->TIPO_LOTE") . "'
                    , ID_MATERIAL_FISICO = " . ($rowMovimientoSalidaLinea->ID_MATERIAL_FISICO == NULL ? 'NULL' : $rowMovimientoSalidaLinea->ID_MATERIAL_FISICO) . "
                    , ID_UBICACION = $rowMovimientoSalidaLinea->ID_UBICACION_DESTINO
                    , FECHA = '" . $fechaActualMasUnSegundo . "'
                    , CANTIDAD = $rowMovimientoSalidaLinea->CANTIDAD
                    , STOCK_OK = 0
                    , STOCK_BLOQUEADO = $rowMovimientoSalidaLinea->CANTIDAD
                    , ID_TIPO_BLOQUEO = $rowNuevoTipoBloqueo->ID_TIPO_BLOQUEO
                    , OBSERVACIONES = ''
                    , TIPO_ASIENTO = 'Recepción de Material Estropeado en Proveedor'
                    , ID_MOVIMIENTO_SALIDA_LINEA = $idNuevoMovimientoSalidaLinea";
            $bd->ExecSQL($sql);
            $idAsientoReparacion = $bd->IdAsignado();

            //BUSCO LA ORDEN DE TRABAJO MOVIMIENTO
            $rowOrdenTrabajoMovimiento = $bd->VerReg("ORDEN_TRABAJO_MOVIMIENTO", "ID_ORDEN_TRABAJO_MOVIMIENTO", $rowMovimientoSalidaLinea->ID_ORDEN_TRABAJO_MOVIMIENTO);

            //COMPRUEBO SI EXISTE MATERIAL UBICACION DE PROVEEDOR CON EL NUEVO TIPO DE BLOQUEO
            $GLOBALS["NotificaErrorPorEmail"] = "No";
            $rowMatUbiDestino                 = $bd->VerRegRest("MATERIAL_UBICACION", "ID_MATERIAL = $rowMovimientoSalidaLinea->ID_MATERIAL AND ID_UBICACION = $rowMovimientoSalidaLinea->ID_UBICACION_DESTINO AND ID_MATERIAL_FISICO " . ($rowMovimientoSalidaLinea->ID_MATERIAL_FISICO == NULL ? 'IS NULL' : "= $rowMovimientoSalidaLinea->ID_MATERIAL_FISICO") . " AND ID_TIPO_BLOQUEO = $rowNuevoTipoBloqueo->ID_TIPO_BLOQUEO AND ID_ORDEN_TRABAJO_MOVIMIENTO = $rowMovimientoSalidaLinea->ID_ORDEN_TRABAJO_MOVIMIENTO AND ID_INCIDENCIA_CALIDAD " . ($rowMovimientoSalidaLinea->ID_INCIDENCIA_CALIDAD == NULL ? 'IS NULL' : "= $rowMovimientoSalidaLinea->ID_INCIDENCIA_CALIDAD"), "No");
            unset($GLOBALS["NotificaErrorPorEmail"]);
            if ($rowMatUbiDestino == false):
                $sqlInsert = "INSERT INTO MATERIAL_UBICACION SET
                                ID_MATERIAL = $rowMovimientoSalidaLinea->ID_MATERIAL
                                , ID_UBICACION = $rowMovimientoSalidaLinea->ID_UBICACION_DESTINO
                                , ID_MATERIAL_FISICO = " . ($rowMovimientoSalidaLinea->ID_MATERIAL_FISICO == NULL ? 'NULL' : "$rowMovimientoSalidaLinea->ID_MATERIAL_FISICO") . "
                                , ID_TIPO_BLOQUEO = $rowNuevoTipoBloqueo->ID_TIPO_BLOQUEO
                                , ID_ORDEN_TRABAJO_MOVIMIENTO = $rowMovimientoSalidaLinea->ID_ORDEN_TRABAJO_MOVIMIENTO
                                , ID_INCIDENCIA_CALIDAD = " . ($rowMovimientoSalidaLinea->ID_INCIDENCIA_CALIDAD == NULL ? 'NULL' : "$rowMovimientoSalidaLinea->ID_INCIDENCIA_CALIDAD");
                $bd->ExecSQL($sqlInsert);
                $idMatUbiDestino = $bd->IdAsignado();
            else:
                $idMatUbiDestino = $rowMatUbiDestino->ID_MATERIAL_UBICACION;
            endif;

            //INCREMENTO EL STOCK DEL NUEVO TIPO DE BLOQUEO EN MATERIAL UBICACION DE PROVEEDOR
            $sqlUpdate = "UPDATE MATERIAL_UBICACION SET
                            STOCK_TOTAL = STOCK_TOTAL + $rowMovimientoSalidaLinea->CANTIDAD
                            , STOCK_BLOQUEADO = STOCK_BLOQUEADO + $rowMovimientoSalidaLinea->CANTIDAD
                            WHERE ID_MATERIAL_UBICACION = $idMatUbiDestino";
            $bd->ExecSQL($sqlUpdate);

            //ACTUALIZO LA LINEA ORIGINAL CON LA NUEVA LINEA GENERADA
            $sqlUpdate = "UPDATE MOVIMIENTO_SALIDA_LINEA SET
                            ID_MOVIMIENTO_SALIDA_LINEA_REPARACION = $idNuevoMovimientoSalidaLinea
                            WHERE ID_MOVIMIENTO_SALIDA_LINEA = $rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA_LINEA";
            $bd->ExecSQL($sqlUpdate);

            //BUSCO LA TRANSFERENCIA QUE GENERO LA LINEA DEL MOVIMIENTO DE SALIDA ORIGINAL DE SM A EMBARQUE
            $rowMovimientoTransferencia = $bd->VerRegRest("MOVIMIENTO_TRANSFERENCIA", "ID_MOVIMIENTO_SALIDA_LINEA = $rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA_LINEA AND TIPO = 'Embarque' AND BAJA = 0", "No");
            if ($rowMovimientoTransferencia != false):
                $sqlUpdate = "UPDATE MOVIMIENTO_TRANSFERENCIA SET
                              ID_MOVIMIENTO_SALIDA = $idNuevoMovimientoSalida
                              , ID_MOVIMIENTO_SALIDA_LINEA = $idNuevoMovimientoSalidaLinea
                              , ID_TIPO_BLOQUEO = $rowNuevoTipoBloqueo->ID_TIPO_BLOQUEO
                              WHERE ID_MOVIMIENTO_TRANSFERENCIA = $rowMovimientoTransferencia->ID_MOVIMIENTO_TRANSFERENCIA";
                $bd->ExecSQL($sqlUpdate);
            endif;

            //BUSCO LA TRANSFERENCIA QUE GENERO LA LINEA DEL MOVIMIENTO DE SALIDA ORIGINAL DE LA UBICACION DE DESUBICACION A SM
            $rowMovimientoTransferencia = $bd->VerRegRest("MOVIMIENTO_TRANSFERENCIA", "ID_MOVIMIENTO_SALIDA_LINEA = $rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA_LINEA AND TIPO = 'Automatico' AND BAJA = 0", "No");
            if ($rowMovimientoTransferencia != false):
                $sqlUpdate = "UPDATE MOVIMIENTO_TRANSFERENCIA SET
                              ID_MOVIMIENTO_SALIDA = $idNuevoMovimientoSalida
                              , ID_MOVIMIENTO_SALIDA_LINEA = $idNuevoMovimientoSalidaLinea
                              , ID_TIPO_BLOQUEO = $rowNuevoTipoBloqueo->ID_TIPO_BLOQUEO
                              WHERE ID_MOVIMIENTO_TRANSFERENCIA = $rowMovimientoTransferencia->ID_MOVIMIENTO_TRANSFERENCIA";
                $bd->ExecSQL($sqlUpdate);
            endif;

            //ACTUALIZO LAS FACTURAS NO COMERCIALES EN CASO DE EXISTIR
            $sqlUpdate = "UPDATE FACTURA_NO_COMERCIAL_MOVIMIENTO SET
                          ID_MOVIMIENTO_SALIDA_LINEA = $idNuevoMovimientoSalidaLinea
                          WHERE ID_MOVIMIENTO_SALIDA_LINEA = $rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA_LINEA";
            $bd->ExecSQL($sqlUpdate);

            //ACTUALIZO EL REPARTO DE COSTES EN CASO DE EXISTIR
            $sqlUpdate = "UPDATE ORDEN_TRANSPORTE_REPARTO_COSTES SET
                          ID_MOVIMIENTO_SALIDA_LINEA = $idNuevoMovimientoSalidaLinea
                          WHERE ID_MOVIMIENTO_SALIDA_LINEA = $rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA_LINEA";
            $bd->ExecSQL($sqlUpdate);

            //REORGANIZO LAS LINEAS DE ALBARAN
            $albaran->reordenar_lineas_albaran($idAlbaranNuevo);

            // LOG MOVIMIENTOS
            $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Mov. salida", $rowMovimientoSalida->ID_MOVIMIENTO_SALIDA, "Linea: " . $rowMovimientoSalidaLinea->LINEA_MOVIMIENTO_SAP . ". Decision: Paso de " . ($tipoCambioEstado == "CambioDeGarantiaAReparacion" ? "Garantía a Reparación" : "Reparación a Garantía"));

            //ACTUALIZO EL ESTADO DEL MOVIMIENTO DE SALIDA REPARACION
            $this->actualizarEstadoMovimientoSalida($idNuevoMovimientoSalida);

            //SI LA ORDEN DE RECOGIDA DE GARANTIA SE QUEDA SIN LINEAS LA DOY DE BAJA
            $numLineasActivasOrdenRecogida = $bd->NumRegsTabla("MOVIMIENTO_SALIDA_LINEA", "ID_EXPEDICION = $rowMovimientoSalida->ID_EXPEDICION AND BAJA = 0 AND LINEA_ANULADA = 0");
            if ($numLineasActivasOrdenRecogida == 0):
                $sqlUpdate = "UPDATE EXPEDICION SET
                              BAJA = 1
                              WHERE ID_EXPEDICION = $rowMovimientoSalida->ID_EXPEDICION";
                $bd->ExecSQL($sqlUpdate);
            endif;

            //ACTUALIZO EL ESTADO DE LA ORDEN DE RECOGIDA REPARACION
            $expedicion->actualizar_estado_orden_recogida($idNuevaExpedicion);

            //ACTUALIZO EL ESTADO DE LA ORDEN DE RECOGIDA ORIGINAL
            $expedicion->actualizar_estado_orden_recogida($rowMovimientoSalida->ID_EXPEDICION);
        endif;
        //FIN SI NO SE HAN PRODUCIDO ERRORES REALIZO LAS ACCIONES CORRESPONDIENTES


        //CREO Y DEVUELVO EL ARRAY A RETORNAR
        $arrDevuelto['hayError']                     = $error;
        $arrDevuelto['textoError']                   = $textoError;
        $arrDevuelto['idCambioEstado']               = $idNuevoCambioEstado;
        $arrDevuelto['idNuevoMovimientoSalida']      = $idNuevoMovimientoSalida;
        $arrDevuelto['idNuevoMovimientoSalidaLinea'] = $idNuevoMovimientoSalidaLinea;


        //DEVUELVO LOS ERRORES
        return $arrDevuelto;
    }

    /**
     * @param $idLineaMovimientoSalida LINEA DE MOVIMIENTO DE SALIDA PARA BORRAR LA LINEA DEL PEDIDO FICTICIO
     * REALIZA EL BORRADO DE LA LINEA DEL PEDIDO FICTICIO E INDICA A SAP COMO DEBE SER EL PEDIDO. ADEMAS GESTIONA UN POSIBLE ERROR EN LA LLAMADA A SAP Y GENERA UNA INCIDENCIA DE SISTEMA
     */
    function BorrarLineaPedidoFicticio($idLineaMovimientoSalida)
    {
        global $bd;
        global $administrador;
        global $auxiliar;
        global $sap;
        global $incidencia_sistema;

        //VARIABLE PARA SABER SI SE PRODUCEN ERRORES
        $textoError = "";
        $error      = false;

        //BUSCO LA LINEA EL MOVIMIENTO DE SALIDA
        $rowMovimientoSalidaLinea = $bd->VerReg("MOVIMIENTO_SALIDA_LINEA", "ID_MOVIMIENTO_SALIDA_LINEA", $idLineaMovimientoSalida);

        //BUSCO EL MOVIMIENTO DE SALIDA
        $rowMovimientoSalida = $bd->VerReg("MOVIMIENTO_SALIDA", "ID_MOVIMIENTO_SALIDA", $rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA);

        //ESTABLEZCO LOS DATOS DE LINEA
        $datosLinea = $auxiliar->traduce("Albaran", $administrador->ID_IDIOMA) . ": I" . ($rowMovimientoSalidaLinea->ID_ALBARAN == "" ? $rowMovimientoSalida->ID_MOVIMIENTO_SALIDA : $rowMovimientoSalidaLinea->ID_ALBARAN) . ". " . $auxiliar->traduce("Linea", $administrador->ID_IDIOMA) . ": " . $rowMovimientoSalidaLinea->LINEA_MOVIMIENTO_SAP . ".<br>";

        //BUSCO EL TIPO DE BLOQUEO REPARABLE NO GARANTIA
        $rowTipoBloqueoReparableNoGarantia = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "QRNG");
        //BUSCO EL TIPO DE BLOQUEO REPARABLE EN GARANTIA
        $rowTipoBloqueoReparableGarantia = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "QRG");
        //BUSCO EL TIPO DE BLOQUEO Retenido Calidad No Preventivo REPARABLE NO GARANTIA
        $rowTipoBloqueoRetenidoCalidadNoPreventivoReparableNoGarantia = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "XRCRNG");
        //BUSCO EL TIPO DE BLOQUEO Retenido Calidad No Preventivo REPARABLE EN GARANTIA
        $rowTipoBloqueoRetenidoCalidadNoPreventivoReparableGarantia = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "XRCRG");

        //BUSCO LA LINEA DEL PEDIDO DE SALIDA FICTICIO
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowPedidoSalidaLineaFicticio     = $bd->VerRegRest("PEDIDO_SALIDA_LINEA", "ID_MOVIMIENTO_SALIDA_LINEA = $rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA_LINEA AND BAJA = 0", "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);
        if ($rowPedidoSalidaLineaFicticio != false): //TIENE LINEA DE PEDIDO FICTICIO ASOCIADA
            //DOY DE BAJA LA LINEA DEL PEDIDO FICTICIO
            $sqlUpdate = "UPDATE PEDIDO_SALIDA_LINEA SET BAJA = 1 WHERE ID_PEDIDO_SALIDA_LINEA = $rowPedidoSalidaLineaFicticio->ID_PEDIDO_SALIDA_LINEA";
            $bd->ExecSQL($sqlUpdate);

            //BUSCO EL PEDIDO FICTICIO
            $rowPedidoSalidaFicticio = $bd->VerReg("PEDIDO_SALIDA", "ID_PEDIDO_SALIDA", $rowPedidoSalidaLineaFicticio->ID_PEDIDO_SALIDA);

            //COMPRUEBO CUANTAS LINEAS TIENE ACTIVAS EL PEDIDO FICTICIO
            $numLineasFicticioActivas = $bd->NumRegsTabla("PEDIDO_SALIDA_LINEA", "ID_PEDIDO_SALIDA = $rowPedidoSalidaFicticio->ID_PEDIDO_SALIDA AND BAJA = 0");
            if ($numLineasFicticioActivas == 0): //SI EL PEDIDO NO TIENE LINEAS ACTIVAS LOS MARCO DE BAJA
                $sqlUpdate = "UPDATE PEDIDO_SALIDA SET
                              BAJA = 1
                              , ESTADO = 'Finalizado'
                              WHERE ID_PEDIDO_SALIDA = $rowPedidoSalidaFicticio->ID_PEDIDO_SALIDA";
                $bd->ExecSQL($sqlUpdate);
            endif;

            //DETECTO SI ES UNA LINEA DE REPARACIO O GARANTIA
            if (
                ($rowMovimientoSalidaLinea->ID_TIPO_BLOQUEO == $rowTipoBloqueoReparableNoGarantia->ID_TIPO_BLOQUEO) ||
                ($rowMovimientoSalidaLinea->ID_TIPO_BLOQUEO == $rowTipoBloqueoRetenidoCalidadNoPreventivoReparableNoGarantia->ID_TIPO_BLOQUEO)
            ):
                $tipoLinea = 'Reparacion';
            elseif (
                ($rowMovimientoSalidaLinea->ID_TIPO_BLOQUEO == $rowTipoBloqueoReparableGarantia->ID_TIPO_BLOQUEO) ||
                ($rowMovimientoSalidaLinea->ID_TIPO_BLOQUEO == $rowTipoBloqueoRetenidoCalidadNoPreventivoReparableGarantia->ID_TIPO_BLOQUEO)
            ):
                $tipoLinea = 'Garantia';
            endif;
            //FIN DETECTO SI ES UNA LINEA DE REPARACIO O GARANTIA

            //ENVIO A SAP EL PEDIDO MODIFICADO
            if ($tipoLinea == 'Garantia'):
                $resultado = $sap->InformarPedidoGarantia($rowPedidoSalidaFicticio->ID_PEDIDO_SALIDA);
            elseif ($tipoLinea == 'Reparacion'):
                $resultado = $sap->InformarPedidoReparacion($rowPedidoSalidaFicticio->ID_PEDIDO_SALIDA, ($rowMovimientoSalidaLinea->CONTRATO_ASOCIADO == '1' ? true : false));
            endif;
            if ($resultado['RESULTADO'] != 'OK'): //No se ha podido grabar la información en SAP
                $textoError = $textoError . $auxiliar->traduce("Se han producido los siguientes errores en el intercambio de informacion con SAP", $administrador->ID_IDIOMA) . ".<br>" . $datosLinea . "<br>";
                foreach ($resultado['ERRORES'] as $arr):
                    foreach ($arr as $mensaje_error):
                        $textoError = $textoError . $mensaje_error . "<br>";
                    endforeach;
                endforeach;
                $textoError = $textoError . "<br>";

                //DESHAGO LA TRANSACCION
                $bd->rollback_transaction();

                //GRABO EL ERROR PRODUCIDO EN BASE DE DATOS
                $sap->InsertarErrores($resultado);

                //MARCO QUE HA HABIDO ERROR
                $error = true;

                //EXTRAIGO EL IDENTIFICADOR DEL WEB SERVICE
                $idWebService = $resultado['LOG_ERROR']['EXTERNALREFID'];

                //BUSCO EL TIPO DE INCIDENCIA SISTEMA "Interfaz Sin Transmitir"
                $rowISTipo = $bd->VerReg("INCIDENCIA_SISTEMA_TIPO", "INCIDENCIA_SISTEMA_TIPO", "Error Transmitir Interfaz");
                //BUSCO EL TIPO DE INCIDENCIA SISTEMA "Ordenes Trabajo Semaforo"
                $rowISSubTipo = $bd->VerReg("INCIDENCIA_SISTEMA_SUBTIPO", "INCIDENCIA_SISTEMA_SUBTIPO", "Borrado Pedido Reparacion/Garantia en Proveedor");

                //COMPRUEBO SI EXISTE UNA INCIDENCIA YA CREADA PARA ESTA LINEA DE MOVIMIENTO DE SALIDA
                $num = $bd->NumRegsTabla("INCIDENCIA_SISTEMA", "TIPO = 'Error Transmitir Interfaz' AND SUBTIPO = 'Borrado Pedido Reparacion/Garantia en Proveedor' AND ID_OBJETO = $rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA_LINEA AND TABLA_OBJETO = 'MOVIMIENTO_SALIDA_LINEA' AND ESTADO <> 'Finalizada'");
                if ($num == 0): //SI NO TIENE INCIDENCIA SISTEMA LA CREO NUEVA
                    //GENERO UNA INCIDENCIA DE SISTEMA
                    $sqlInsert = "INSERT INTO INCIDENCIA_SISTEMA SET
                                  ID_INCIDENCIA_SISTEMA_TIPO = $rowISTipo->ID_INCIDENCIA_SISTEMA_TIPO
                                  , TIPO = 'Error Transmitir Interfaz'
                                  , ID_INCIDENCIA_SISTEMA_SUBTIPO = $rowISSubTipo->ID_INCIDENCIA_SISTEMA_SUBTIPO
                                  , SUBTIPO = 'Borrado Pedido Reparacion/Garantia en Proveedor'
                                  , ESTADO = 'Creada'
                                  , TABLA_OBJETO = 'MOVIMIENTO_SALIDA_LINEA'
                                  , ID_OBJETO = $rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA_LINEA
                                  , FECHA_CREACION = '" . date("Y-m-d H:i:s") . "'
                                  , FECHA_RESOLUCION = '0000-00-00 00:00:00'
                                  , ID_ADMINISTRADOR_CREACION = $administrador->ID_ADMINISTRADOR
                                  , ID_ADMINISTRADOR_ULTIMA_MODIFICACION = $administrador->ID_ADMINISTRADOR
                                  , ID_LOG_EJECUCION_WS = $idWebService";
                    $bd->ExecSQL($sqlInsert);
                endif;
            else:
                //ACTUALIZO EL NUMERO DE PEDIDO SAP DEL PEDIDO FICTICIO
                $sqlUpdate = "UPDATE PEDIDO_SALIDA SET PEDIDO_SAP = '" . $resultado['PEDIDO'] . "' WHERE ID_PEDIDO_SALIDA = $rowPedidoSalidaFicticio->ID_PEDIDO_SALIDA";
                $bd->ExecSQL($sqlUpdate, "No");

                //BUSCO LA POSIBLE INCIDENCIA DE SISTEMA
                $GLOBALS["NotificaErrorPorEmail"] = "No";
                $rowIncidenciaSistema             = $bd->VerRegRest("INCIDENCIA_SISTEMA", "TIPO = 'Error Transmitir Interfaz' AND SUBTIPO = 'Borrado Pedido Reparacion/Garantia en Proveedor' AND ID_OBJETO = $rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA_LINEA AND TABLA_OBJETO = 'MOVIMIENTO_SALIDA_LINEA' AND ESTADO <> 'Finalizada'", "No");
                unset($GLOBALS["NotificaErrorPorEmail"]);
                if ($rowIncidenciaSistema != false):
                    //ACTUALIZO INCIDENCIA
                    $incidencia_sistema->actualizarIncidencia($rowIncidenciaSistema, 'Manual');
                endif;
            endif;
        endif;
        //FIN TIENE LINEA DE PEDIDO FICTICIO ASOCIADA


        //CREO Y DEVUELVO EL ARRAY A RETORNAR
        $arrDevuelto['hayError']            = $error;
        $arrDevuelto['textoError']          = $textoError;
        $arrDevuelto['resultadoLlamadaSAP'] = $resultado;


        //DEVUELVO LOS ERRORES
        return $arrDevuelto;
    }

    /**
     * @param $idLineaMovimientoSalida LA NUEVA LINEA DE MOVIMIENTO DE SALIDA PARA CREAR EL PEDIDO DE REPARACION/GARANTIA CORRESPONDIENTE
     * REALIZA LA CREACION DE LA LINEA DEL PEDIDO FICTICIO E INDICA A SAP COMO DEBE SER EL NUEVO PEDIDO. ADEMAS GESTIONA UN POSIBLE ERROR EN LA LLAMADA A SAP Y GENERA UNA INCIDENCIA DE SISTEMA
     */
    function CrearLineaPedidoFicticio($idLineaMovimientoSalida)
    {
        global $bd;
        global $administrador;
        global $auxiliar;
        global $sap;
        global $html;
        global $incidencia_sistema;

        //VARIABLE PARA SABER SI SE PRODUCEN ERRORES
        $textoError = "";
        $error      = false;

        //BUSCO LA LINEA EL MOVIMIENTO DE SALIDA
        $rowMovimientoSalidaLinea = $bd->VerReg("MOVIMIENTO_SALIDA_LINEA", "ID_MOVIMIENTO_SALIDA_LINEA", $idLineaMovimientoSalida);

        //BUSCO EL MOVIMIENTO DE SALIDA
        $rowMovimientoSalida = $bd->VerReg("MOVIMIENTO_SALIDA", "ID_MOVIMIENTO_SALIDA", $rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA);

        //ESTABLEZCO LOS DATOS DE LINEA
        $datosLinea = $auxiliar->traduce("Albaran", $administrador->ID_IDIOMA) . ": I" . ($rowMovimientoSalidaLinea->ID_ALBARAN == "" ? $rowMovimientoSalida->ID_MOVIMIENTO_SALIDA : $rowMovimientoSalidaLinea->ID_ALBARAN) . ". " . $auxiliar->traduce("Linea", $administrador->ID_IDIOMA) . ": " . $rowMovimientoSalidaLinea->LINEA_MOVIMIENTO_SAP . ".<br>";

        //BUSCO EL TIPO DE BLOQUEO REPARABLE NO GARANTIA
        $rowTipoBloqueoReparableNoGarantia = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "QRNG");
        //BUSCO EL TIPO DE BLOQUEO REPARABLE EN GARANTIA
        $rowTipoBloqueoReparableGarantia = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "QRG");
        //BUSCO EL TIPO DE BLOQUEO Retenido Calidad No Preventivo REPARABLE NO GARANTIA
        $rowTipoBloqueoRetenidoCalidadNoPreventivoReparableNoGarantia = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "XRCRNG");
        //BUSCO EL TIPO DE BLOQUEO Retenido Calidad No Preventivo REPARABLE EN GARANTIA
        $rowTipoBloqueoRetenidoCalidadNoPreventivoReparableGarantia = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "XRCRG");

        //BUSCO LA UBICACION A DONDE FUE UBICADA LA LINEA
        $rowUbi      = $bd->VerReg("UBICACION", "ID_UBICACION", $rowMovimientoSalidaLinea->ID_UBICACION_DESTINO);
        $rowAlm      = $bd->VerReg("ALMACEN", "ID_ALMACEN", $rowUbi->ID_ALMACEN);
        $idProveedor = $rowAlm->ID_PROVEEDOR;

        //BUSCO EL PROVEEDOR
        $rowProvEnvio = $bd->VerReg("PROVEEDOR", "ID_PROVEEDOR", $idProveedor);
        if ($rowProvEnvio->ID_PROVEEDOR_PADRE == NULL):
            $rowProv = $bd->VerReg("PROVEEDOR", "ID_PROVEEDOR", $idProveedor);
        else:
            $rowProv = $bd->VerReg("PROVEEDOR", "ID_PROVEEDOR", $rowProvEnvio->ID_PROVEEDOR_PADRE);
        endif;
        $idProveedor = $rowProv->ID_PROVEEDOR; // Proveedor para pedidos de reparación y garantía (ZREP y ZGAR campo Obligatorio), SIEMPRE SERA EL PROVEEDOR PADRE

        //CREO LA CABECERA DEL NUEVO PEDIDO
        $sqlInsert = "INSERT INTO PEDIDO_SALIDA SET
                        PEDIDO_SAP = ''
                        , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                        , TIPO_PEDIDO = 'Traslado'
                        , TIPO_TRASLADO = 'Ficticio'
                        , ID_CENTRO_ORIGEN = NULL
                        , ID_PROVEEDOR = $idProveedor
                        , ESTADO = 'Finalizado'
                        , FECHA_CREACION = '" . date("Y-m-d") . "'";
        $bd->ExecSQL($sqlInsert);
        $idPedido = $bd->IdAsignado();

        //BUSCO EL MATERIAL
        $rowMat = $bd->VerReg("MATERIAL", "ID_MATERIAL", $rowMovimientoSalidaLinea->ID_MATERIAL);

        //BUSCO LA UBICACION DE DONDE SALIO EL MATERIAL
        $rowUbiOrigen = $bd->VerReg("UBICACION", "ID_UBICACION", $rowMovimientoSalidaLinea->ID_UBICACION);

        //BUSCO EL ALMACEN DE DONDE SALIO EL MATERIAL
        $rowAlmOrigen = $bd->VerReg("ALMACEN", "ID_ALMACEN", $rowUbiOrigen->ID_ALMACEN);

        if ($rowAlmOrigen->STOCK_COMPARTIDO == 1):
            $sqlAlmacenMantenedor    = "SELECT *
                                          FROM ALMACEN A
                                          INNER JOIN CENTRO_FISICO CF ON CF.ID_CENTRO_FISICO=A.ID_CENTRO_FISICO
                                          WHERE CF.GESTION_STOCK_TERCEROS=1 AND CF.ID_CENTRO_FISICO= " . $rowAlmOrigen->ID_CENTRO_FISICO . " AND A.TIPO_STOCK='Mantenedor'";
            $resultAlmacenMantenedor = $bd->ExecSQL($sqlAlmacenMantenedor);

            if ($bd->NumRegs($resultAlmacenMantenedor) == 0):
                $html->PagError("NoExisteAlmacenMantenedor");
            else:
                $rowAlmOrigen = $bd->SigReg($resultAlmacenMantenedor);
            endif;
        endif;

        //BUSCO EL CENTRO DE DONDE SALIO EL MATERIAL
        $rowCentroOrigen = $bd->VerReg("CENTRO", "ID_CENTRO", $rowAlmOrigen->ID_CENTRO);

        //CREO LA LINEA DEL PEDIDO
        $sqlInsert = "INSERT INTO PEDIDO_SALIDA_LINEA SET
                        ID_PEDIDO_SALIDA = $idPedido
                        , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                        , LINEA_PEDIDO_SAP = '00010'
                        , ID_ALMACEN_ORIGEN = $rowUbi->ID_ALMACEN
                        , ID_MATERIAL = $rowMovimientoSalidaLinea->ID_MATERIAL
                        , CANTIDAD = $rowMovimientoSalidaLinea->CANTIDAD
                        , CANTIDAD_PENDIENTE_SERVIR = 0
                        , ID_TIPO_BLOQUEO = $rowMovimientoSalidaLinea->ID_TIPO_BLOQUEO
                        , ID_UNIDAD = $rowMat->ID_UNIDAD_MEDIDA
                        , ID_CENTRO_DESTINO = $rowCentroOrigen->ID_CENTRO
                        , ID_ALMACEN_DESTINO = $rowAlmOrigen->ID_ALMACEN
                        , CONTRATO_ASOCIADO = $rowMovimientoSalidaLinea->CONTRATO_ASOCIADO
                        , CONTRATO_REFERENCIA = " . ($rowMovimientoSalidaLinea->CONTRATO_REFERENCIA == '' ? 'NULL' : "'" . $rowMovimientoSalidaLinea->CONTRATO_REFERENCIA . "'") . "
                        , CONTRATO_POSICION = " . ($rowMovimientoSalidaLinea->CONTRATO_POSICION == '' ? 'NULL' : "'" . $rowMovimientoSalidaLinea->CONTRATO_POSICION . "'") . "
                        , ID_MOVIMIENTO_SALIDA_LINEA = $rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA_LINEA";
        $bd->ExecSQL($sqlInsert);

        //ACTUALIZO LA LINEA CON LA DECISION TOMADA
        $sqlUpdate = "UPDATE MOVIMIENTO_SALIDA_LINEA SET
                        DECISION_COMPRADOR = 'Reparar'
                        , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                        WHERE ID_MOVIMIENTO_SALIDA_LINEA = $rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA_LINEA";
        $bd->ExecSQL($sqlUpdate);

        // LOG MOVIMIENTOS
        $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Mov. salida", $rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA, "Linea: " . $rowMovimientoSalidaLinea->LINEA_MOVIMIENTO_SAP . ". Decision: Reparar");

        //DETECTO SI ES UNA LINEA DE REPARACIO O GARANTIA
        if (
            ($rowMovimientoSalidaLinea->ID_TIPO_BLOQUEO == $rowTipoBloqueoReparableNoGarantia->ID_TIPO_BLOQUEO) ||
            ($rowMovimientoSalidaLinea->ID_TIPO_BLOQUEO == $rowTipoBloqueoRetenidoCalidadNoPreventivoReparableNoGarantia->ID_TIPO_BLOQUEO)
        ):
            $tipoLinea = 'Reparacion';
        elseif (
            ($rowMovimientoSalidaLinea->ID_TIPO_BLOQUEO == $rowTipoBloqueoReparableGarantia->ID_TIPO_BLOQUEO) ||
            ($rowMovimientoSalidaLinea->ID_TIPO_BLOQUEO == $rowTipoBloqueoRetenidoCalidadNoPreventivoReparableGarantia->ID_TIPO_BLOQUEO)
        ):
            $tipoLinea = 'Garantia';
        endif;
        //FIN DETECTO SI ES UNA LINEA DE REPARACIO O GARANTIA

        //ENVIO A SAP EL PEDIDO MODIFICADO
        if ($tipoLinea == 'Garantia'):
            $resultado = $sap->InformarPedidoGarantia($idPedido);
        elseif ($tipoLinea == 'Reparacion'):
            $resultado = $sap->InformarPedidoReparacion($idPedido, ($rowMovimientoSalidaLinea->CONTRATO_ASOCIADO == '1' ? true : false));
        endif;

        if ($resultado['RESULTADO'] != 'OK'): //No se ha podido grabar la información en SAP
            $textoError = $textoError . $auxiliar->traduce("Se han producido los siguientes errores en el intercambio de informacion con SAP", $administrador->ID_IDIOMA) . ".<br>" . $datosLinea . "<br>";
            foreach ($resultado['ERRORES'] as $arr):
                foreach ($arr as $mensaje_error):
                    $textoError = $textoError . $mensaje_error . "<br>";
                endforeach;
            endforeach;
            $textoError = $textoError . "<br>";

            //DESHAGO LA TRANSACCION
            $bd->rollback_transaction();

            //GRABO EL ERROR PRODUCIDO EN BASE DE DATOS
            $sap->InsertarErrores($resultado);

            //MARCO QUE HA HABIDO ERROR
            $error = true;

            //EXTRAIGO EL IDENTIFICADOR DEL WEB SERVICE
            $idWebService = $resultado['LOG_ERROR']['EXTERNALREFID'];

            //BUSCO EL TIPO DE INCIDENCIA SISTEMA "Interfaz Sin Transmitir"
            $rowISTipo = $bd->VerReg("INCIDENCIA_SISTEMA_TIPO", "INCIDENCIA_SISTEMA_TIPO", "Error Transmitir Interfaz");
            //BUSCO EL TIPO DE INCIDENCIA SISTEMA "Ordenes Trabajo Semaforo"
            $rowISSubTipo = $bd->VerReg("INCIDENCIA_SISTEMA_SUBTIPO", "INCIDENCIA_SISTEMA_SUBTIPO", "Creacion Pedido Reparacion/Garantia en Proveedor");

            //COMPRUEBO SI EXISTE UNA INCIDENCIA YA CREADA PARA ESTA LINEA DE MOVIMIENTO DE SALIDA
            $num = $bd->NumRegsTabla("INCIDENCIA_SISTEMA", "TIPO = 'Error Transmitir Interfaz' AND SUBTIPO = 'Creacion Pedido Reparacion/Garantia en Proveedor' AND ID_OBJETO = $rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA_LINEA AND TABLA_OBJETO = 'MOVIMIENTO_SALIDA_LINEA' AND ESTADO <> 'Finalizada'");
            if ($num == 0): //SI NO TIENE INCIDENCIA SISTEMA LA CREO NUEVA
                //GENERO UNA INCIDENCIA DE SISTEMA
                $sqlInsert = "INSERT INTO INCIDENCIA_SISTEMA SET
                              ID_INCIDENCIA_SISTEMA_TIPO = $rowISTipo->ID_INCIDENCIA_SISTEMA_TIPO
                              , TIPO = 'Error Transmitir Interfaz'
                              , ID_INCIDENCIA_SISTEMA_SUBTIPO = $rowISSubTipo->ID_INCIDENCIA_SISTEMA_SUBTIPO
                              , SUBTIPO = 'Creacion Pedido Reparacion/Garantia en Proveedor'
                              , ESTADO = 'Creada'
                              , TABLA_OBJETO = 'MOVIMIENTO_SALIDA_LINEA'
                              , ID_OBJETO = $rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA_LINEA
                              , FECHA_CREACION = '" . date("Y-m-d H:i:s") . "'
                              , FECHA_RESOLUCION = '0000-00-00 00:00:00'
                              , ID_ADMINISTRADOR_CREACION = $administrador->ID_ADMINISTRADOR
                              , ID_ADMINISTRADOR_ULTIMA_MODIFICACION = $administrador->ID_ADMINISTRADOR
                              , ID_LOG_EJECUCION_WS = $idWebService";
                $bd->ExecSQL($sqlInsert);
            endif;
        else:
            //ACTUALIZO EL NUMERO DE PEDIDO SAP DEL PEDIDO FICTICIO
            $sqlUpdate = "UPDATE PEDIDO_SALIDA SET PEDIDO_SAP = '" . $resultado['PEDIDO'] . "' WHERE ID_PEDIDO_SALIDA = $idPedido";
            $bd->ExecSQL($sqlUpdate, "No");

            //BUSCO LA POSIBLE INCIDENCIA DE SISTEMA
            $GLOBALS["NotificaErrorPorEmail"] = "No";
            $rowIncidenciaSistema             = $bd->VerRegRest("INCIDENCIA_SISTEMA", "TIPO = 'Error Transmitir Interfaz' AND SUBTIPO = 'Creacion Pedido Reparacion/Garantia en Proveedor' AND ID_OBJETO = $rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA_LINEA AND TABLA_OBJETO = 'MOVIMIENTO_SALIDA_LINEA' AND ESTADO <> 'Finalizada'", "No");
            unset($GLOBALS["NotificaErrorPorEmail"]);
            if ($rowIncidenciaSistema != false):
                //ACTUALIZO INCIDENCIA
                $incidencia_sistema->actualizarIncidencia($rowIncidenciaSistema, 'Manual');
            endif;
        endif;


        //CREO Y DEVUELVO EL ARRAY A RETORNAR
        $arrDevuelto['hayError']            = $error;
        $arrDevuelto['textoError']          = $textoError;
        $arrDevuelto['resultadoLlamadaSAP'] = $resultado;


        //DEVUELVO LOS ERRORES
        return $arrDevuelto;
    }

    /**
     * FUNCION PARA COMPROBAR QUE LA NUEVA CANTIDAD ADICIONAL NO SUPERA LA CANTIDAD A PREPARAR EN LA LINEA DEL PEDIDO
     */
    function CantidadLineaPedidoSuperada($idMovimientoSalidaLinea, $nuevaCantidadAdicional)
    {
        //DECLARACION DE VARIABLES GLOBALES
        global $bd;

        //VARIABLE PARA SABER SI SE PRODUCEN ERRORES
        $error = true;

        //BUSCO LA LINEA DEL MOVIMIENTO DE SALIDA
        $rowMovimientoSalidaLinea = $bd->VerReg("MOVIMIENTO_SALIDA_LINEA", "ID_MOVIMIENTO_SALIDA_LINEA", $idMovimientoSalidaLinea);

        //BUSCO LA LINEA DEL PEDIDO DE SALIDA
        $rowPedidoSalidaLinea = $bd->VerReg("PEDIDO_SALIDA_LINEA", "ID_PEDIDO_SALIDA_LINEA", $rowMovimientoSalidaLinea->ID_PEDIDO_SALIDA_LINEA);

        //CALCULO LA CANTIDAD EN LINEAS DE MOVIMIENTOS DE SALIDA YA DESUBICADAS
        $sqlCantidadPreparadaEnMovimientos    = "SELECT IF(SUM(CANTIDAD) IS NULL, 0, SUM(CANTIDAD)) AS CANTIDAD_PREPARADA
                                                FROM MOVIMIENTO_SALIDA_LINEA
                                                WHERE ID_PEDIDO_SALIDA_LINEA = $rowPedidoSalidaLinea->ID_PEDIDO_SALIDA_LINEA AND LINEA_ANULADA = 0 AND BAJA = 0 AND ESTADO <> 'Reservado para Preparacion'";
        $resultCantidadPreparadaEnMovimientos = $bd->ExecSQL($sqlCantidadPreparadaEnMovimientos);
        $rowCantidadPreparadaEnMovimientos    = $bd->SigReg($resultCantidadPreparadaEnMovimientos);

        //COMPRUEBO QUE LA NUEVA CANTIDAD SE PUEDE PREPARAR
        if ((($rowCantidadPreparadaEnMovimientos->CANTIDAD_PREPARADA + $nuevaCantidadAdicional) - $rowPedidoSalidaLinea->CANTIDAD) > EPSILON_SISTEMA):
            $error = true;
        else:
            $error = false;
        endif;

        //DEVUELVO SI HAY ERROR O NO
        return $error;
    }

    /**
     * @param $idProveedor
     * $idCentro
     * $idMaterial
     * $cantidad
     * AL RECEPCIONAR MATERIAL UBICADO EN ALMACENES DE STOCK EXTERNALIZADO DE REPARACIÓN, MARCAMOS ESTOS MATERIALES COMO REPARADOS
     */
    function recepcionStockExternalizadoReparacion($idProveedor, $idCentro, $idMaterial, $idMaterialFisico)
    {
        global $bd;
        global $administrador;
        global $auxiliar;
        global $html;

        //ARRAY DE ERRORES
        $arrErrores = array();

        //OBTENEMOS EL PROVEEDOR
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowProveedor                     = $bd->VerReg("PROVEEDOR", "ID_PROVEEDOR", $idProveedor, "No");

        //OBTENEMOS EL CENTRO
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowCentro                        = $bd->VerReg("CENTRO", "ID_CENTRO", $idCentro, "No");

        //OBTENEMOS EL MATERIAL
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowMaterial                      = $bd->VerReg("MATERIAL", "ID_MATERIAL", $idMaterial, "No");

        //OBTENEMOS EL MATERIAL FÍSICO
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowMaterialFisico                = $bd->VerReg("MATERIAL_FISICO", "ID_MATERIAL_FISICO", $idMaterialFisico, "No");

        //CREAMOS MENSAJE DE ERROR GENÉRICO
        $errorGenerico = $auxiliar->traduce("Proveedor", $administrador->ID_IDIOMA) . ": " . $rowProveedor->REFERENCIA . " - " . $rowProveedor->NOMBRE . ". ";
        $errorGenerico .= $auxiliar->traduce("Centro", $administrador->ID_IDIOMA) . ": " . $rowCentro->REFERENCIA . " - " . $rowCentro->CENTRO . ". ";
        $errorGenerico .= $auxiliar->traduce("Material", $administrador->ID_IDIOMA) . ": " . $rowMaterial->REFERENCIA_SGA . " - " . $rowMaterial->{'DESCRIPCION' . ($administrador->ID_IDIOMA == 'ESP' ? '' : '_EN')} . ". ";
        $errorGenerico .= $auxiliar->traduce("Serie/Lote", $administrador->ID_IDIOMA) . ": " . $rowMaterialFisico->NUMERO_SERIE_LOTE . ". ";

        //OBTENEMOS EL ALMACÉN DE STOCK EXTERNALIZADO A PARTIR DEL PROVEEDOR Y CENTRO
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowAlmacenStockExternalizado     = $bd->VerRegRest("ALMACEN", "ID_PROVEEDOR = $idProveedor AND ID_CENTRO = $idCentro AND TIPO_ALMACEN = 'externalizado_reparacion' AND BAJA = 0", "No");

        if ($rowAlmacenStockExternalizado):

            //SI EXISTE EL ALMACÉN DE STOCK EXTERNALIZADO PARA EL PROVEEDOR Y CENTRO, COMPRUEBO SI EXISTE EL REGISTRO MATERIAL-ALMACÉN
            $GLOBALS["NotificaErrorPorEmail"] = "No";
            $rowMaterialAlmacenExternalizado  = $bd->VerRegRest("MATERIAL_ALMACEN", "ID_ALMACEN = $rowAlmacenStockExternalizado->ID_ALMACEN AND ID_MATERIAL = $idMaterial AND BAJA = 0", "No");

            if (($rowMaterialAlmacenExternalizado) && ($rowMaterialFisico->REPARADO == 0)):

                //SI EXISTE Y EL MATERIAL FÍSICO NO ESTÁ REPARADO, MARCAMOS COMO REPARADO EL MATERIAL FÍSICO Y CREAMOS EL AJUSTE NECESARIO
                $sqlUpdate = "UPDATE MATERIAL_FISICO SET
                                    REPARADO = 1
                                 WHERE ID_MATERIAL_FISICO = $idMaterialFisico";
                $bd->ExecSQL($sqlUpdate);

                //OBTENEMOS EL MATERIAL FISICO ACTUALIZADO
                $GLOBALS["NotificaErrorPorEmail"] = "No";
                $rowMaterialFisicoActualizado     = $bd->VerReg("MATERIAL_FISICO", "ID_MATERIAL_FISICO", $idMaterialFisico, "No");

                // LOG MOVIMIENTOS
                $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Material Fisico", $idMaterialFisico, "Reparación del material " . $rowMaterial->REFERENCIA_SGA . " con numero de serie " . $rowMaterialFisicoActualizado->NUMERO_SERIE_LOTE, "MATERIAL_FISICO", $rowMaterialFisico, $rowMaterialFisicoActualizado);

                //HAGO LOS AJUSTES NECESARIOS
                //OBTENGO EL MOTIVO DEL AJUSTE DE LA BBDD
                $GLOBALS["NotificaErrorPorEmail"] = "No";
                $rowMotivoAjuste                  = $bd->VerRegRest("MOTIVO_AJUSTE_INVENTARIO", "NOMBRE = 'Ajustar stock en almacén externalizado' AND BAJA = 0", "No");

                $html->PagErrorCondicionado($rowMotivoAjuste, "==", false, "MotivoAjusteNoExiste");

                //CREAMOS LA ORDEN DE CONTEO
                $sqlInsert = "INSERT INTO INVENTARIO_ORDEN_CONTEO SET
                    ID_INVENTARIO = NULL
                    , TIPO = 'Ajuste Stock Externalizado Reparacion Automatico'
                    , ID_MOTIVO_AJUSTE_INVENTARIO = $rowMotivoAjuste->ID_MOTIVO_AJUSTE_INVENTARIO
                    , ID_ALMACEN = $rowAlmacenStockExternalizado->ID_ALMACEN
                    , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                    , FECHA = '" . date("Y-m-d H:i:s") . "'
                    , FECHA_PROCESADO = '" . date("Y-m-d H:i:s") . "'
                    , ESTADO = 'Finalizado'";
                $bd->ExecSQL($sqlInsert);
                $idConteo = $bd->IdAsignado();

                //OBTENEMOS EL CONTEO
                $rowInventarioOrdenConteo = $bd->VerReg("INVENTARIO_ORDEN_CONTEO", "ID_INVENTARIO_ORDEN_CONTEO", $idConteo);

                //SI LA FECHA PROCESADO INFORME FACTURACION ES CERO, LA ACTUALIZO
                if ($rowInventarioOrdenConteo->FECHA_PROCESADO_INFORME_FACTURACION == "0000-00-00 00:00:00"):
                    $sqlUpdate = "UPDATE INVENTARIO_ORDEN_CONTEO SET
                                    FECHA_PROCESADO_INFORME_FACTURACION = '" . date("Y-m-d H:i:s") . "'
                                  WHERE ID_INVENTARIO_ORDEN_CONTEO = $idConteo";
                    $bd->ExecSQL($sqlUpdate);
                endif;

                // LOG MOVIMIENTOS
                $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Creación", "Ajuste de Inventario", $idConteo, "");


                //OBTENEMOS LA UBICACION DEL ALMACEN STOCK EXTERNALIZADO DE REPARACION
                $GLOBALS["NotificaErrorPorEmail"] = "No";
                $rowUbicacionStockExternalizado   = $bd->VerRegRest("UBICACION", "ID_ALMACEN = $rowAlmacenStockExternalizado->ID_ALMACEN AND BAJA = 0", "No");


                //CREAMOS LA LÍNEA DE LA ORDEN DE CONTEO
                $sqlInsert = "INSERT INTO INVENTARIO_ORDEN_CONTEO_LINEA SET
                    ID_INVENTARIO_ORDEN_CONTEO = $idConteo 
                    , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                    , ID_UBICACION = $rowUbicacionStockExternalizado->ID_UBICACION
                    , ID_MATERIAL = $idMaterial
                    , ID_MATERIAL_FISICO = " . ($idMaterialFisico != NULL ? $idMaterialFisico : "NULL") . "
                    , ID_TIPO_BLOQUEO = NULL
                    , CANTIDAD_SISTEMA = 0  
                    , CANTIDAD_SISTEMA_ALMACEN_SAP = 0 
                    , CANTIDAD_CONTADA = 0
                    , CANTIDAD_RECUENTO = 0
                    , DIFERENCIA_PORCENTAJE = 0
                    , CANTIDAD_DIFERENCIA = 0
                    , DESVIACION = 0
                    , LINEA_CONTEO_SAP = ''
                    , FECHA_PROCESADO = '" . date("Y-m-d H:i:s") . "'
                    , LINEA_CONTEO_NUESTRO = '001'";
                $bd->ExecSQL($sqlInsert);
                $idLineaConteo = $bd->IdAsignado();

                // LOG MOVIMIENTOS
                $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Ajuste de Inventario", $idConteo, "Creación línea ajuste de inventario" . " " . $idLineaConteo);

            endif;

        endif;

        return $arrErrores;

    }

    /**
     * @param $idProveedor
     * $idCentro
     * $idMaterial
     * $cantidad
     * AL RECEPCIONAR MATERIAL UBICADO EN ALMACENES DE STOCK EXTERNALIZADO, ESTE STOCK DEBE DECREMENTARSE EN ESTOS ALMACENES
     */
    function decrementarStockExternalizado($idProveedor, $idCentro, $idMaterial, $idTipoBloqueo, $cantidad)
    {
        global $bd;
        global $administrador;
        global $auxiliar;
        global $html;
        global $ubicacion;

        //ARRAY DE ERRORES
        $arrErrores = array();

        //BUSCO EL TIPO DE BLOQUEO PREVENTIVO
        $rowTipoBloqueoPreventivo = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "SP");

        //SI SE TRATA DE STOCK LIBRE O PREVENTIVO
        if (($idTipoBloqueo == NULL) || ($idTipoBloqueo == $rowTipoBloqueoPreventivo->ID_TIPO_BLOQUEO)):
            //OBTENEMOS EL PROVEEDOR
            $GLOBALS["NotificaErrorPorEmail"] = "No";
            $rowProveedor                     = $bd->VerReg("PROVEEDOR", "ID_PROVEEDOR", $idProveedor, "No");

            //OBTENEMOS EL CENTRO
            $GLOBALS["NotificaErrorPorEmail"] = "No";
            $rowCentro                        = $bd->VerReg("CENTRO", "ID_CENTRO", $idCentro, "No");

            //OBTENEMOS EL MATERIAL
            $GLOBALS["NotificaErrorPorEmail"] = "No";
            $rowMaterial                      = $bd->VerReg("MATERIAL", "ID_MATERIAL", $idMaterial, "No");

            //CREAMOS MENSAJE DE ERROR GENÉRICO
            $errorGenerico = $auxiliar->traduce("Proveedor", $administrador->ID_IDIOMA) . ": " . $rowProveedor->REFERENCIA . " - " . $rowProveedor->NOMBRE . ". ";
            $errorGenerico .= $auxiliar->traduce("Centro", $administrador->ID_IDIOMA) . ": " . $rowCentro->REFERENCIA . " - " . $rowCentro->CENTRO . ". ";
            $errorGenerico .= $auxiliar->traduce("Material", $administrador->ID_IDIOMA) . ": " . $rowMaterial->REFERENCIA_SGA . " - " . $rowMaterial->{'DESCRIPCION' . ($administrador->ID_IDIOMA == 'ESP' ? '' : '_EN')} . ". ";

            //OBTENEMOS EL ALMACÉN DE STOCK EXTERNALIZADO A PARTIR DEL PROVEEDOR Y CENTRO
            $GLOBALS["NotificaErrorPorEmail"] = "No";
            $rowAlmacenStockExternalizado     = $bd->VerRegRest("ALMACEN", "ID_PROVEEDOR = $idProveedor AND ID_CENTRO = $idCentro AND TIPO_ALMACEN = 'externalizado' AND BAJA = 0", "No");

            if ($rowAlmacenStockExternalizado):

                //SI EXISTE EL ALMACÉN DE STOCK EXTERNALIZADO PARA EL PROVEEDOR Y CENTRO, COMPRUEBO SI EXISTE EL REGISTRO MATERIAL-ALMACÉN
                $GLOBALS["NotificaErrorPorEmail"] = "No";
                $rowMaterialAlmacenExternalizado  = $bd->VerRegRest("MATERIAL_ALMACEN", "ID_ALMACEN = $rowAlmacenStockExternalizado->ID_ALMACEN AND ID_MATERIAL = $idMaterial AND BAJA = 0", "No");

                if ($rowMaterialAlmacenExternalizado):

                    //OBTENEMOS LA UBICACIÓN DE DICHO ALMACÉN (ÚNICAMENTE HAY UNA UBICACIÓN POR ALMACÉN)
                    $GLOBALS["NotificaErrorPorEmail"] = "No";
                    $rowUbicacionStockExternalizado   = $bd->VerRegRest("UBICACION", "ID_ALMACEN = $rowAlmacenStockExternalizado->ID_ALMACEN AND UBICACION = 'EXT_" . $rowAlmacenStockExternalizado->REFERENCIA . "' AND BAJA = 0", "No");

                    //COMPROBAMOS SI EN ESTE ALMACÉN HAY STOCK DEL MATERIAL INDICADO
                    $sqlStockMaterialAlmacenExternalizado    = "SELECT SUM(STOCK_OK) AS STOCK_OK
                                                        FROM MATERIAL_UBICACION
                                                        WHERE ID_UBICACION = $rowUbicacionStockExternalizado->ID_UBICACION AND ID_MATERIAL = $idMaterial AND STOCK_OK > 0 AND ACTIVO = 1";
                    $resultStockMaterialAlmacenExternalizado = $bd->ExecSQL($sqlStockMaterialAlmacenExternalizado);
                    $rowStockMaterialAlmacenExternalizado    = $bd->SigReg($resultStockMaterialAlmacenExternalizado);

                    if (($rowStockMaterialAlmacenExternalizado->STOCK_OK == NULL) || ($rowStockMaterialAlmacenExternalizado->STOCK_OK < $cantidad)):
                        //SI NO HAY STOCK SUFICIENTE, HAGO UN AJUSTE CORRECTOR EN EL ALMACÉN DE STOCK EXTERNALIZADO
                        //PRIMERO OBTENGO LA CANTIDAD DEL AJUSTE A REALIZAR
                        $cantidadAjusteCorrecto = $cantidad - $rowStockMaterialAlmacenExternalizado->STOCK_OK;

                        //OBTENGO EL MOTIVO DEL AJUSTE DE LA BBDD
                        $GLOBALS["NotificaErrorPorEmail"] = "No";
                        $rowMotivoAjuste                  = $bd->VerRegRest("MOTIVO_AJUSTE_INVENTARIO", "NOMBRE = 'Ajustar stock en almacén externalizado' AND BAJA = 0", "No");

                        $html->PagErrorCondicionado($rowMotivoAjuste, "==", false, "MotivoAjusteNoExiste");

                        //CREAMOS LA ORDEN DE CONTEO
                        $sqlInsert = "INSERT INTO INVENTARIO_ORDEN_CONTEO SET
                                        ID_INVENTARIO = NULL
                                        , TIPO = 'Ajuste Stock Externalizado Automatico'
                                        , ID_MOTIVO_AJUSTE_INVENTARIO = $rowMotivoAjuste->ID_MOTIVO_AJUSTE_INVENTARIO
                                        , ID_ALMACEN = $rowAlmacenStockExternalizado->ID_ALMACEN
                                        , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                        , FECHA = '" . date("Y-m-d H:i:s") . "'
                                        , ESTADO = 'En proceso'";
                        $bd->ExecSQL($sqlInsert);
                        $idConteo = $bd->IdAsignado();

                        // LOG MOVIMIENTOS
                        $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Creación", "Ajuste de Inventario", $idConteo, "");

                        //CREAMOS LA LÍNEA DE LA ORDEN DE CONTEO
                        $sqlInsert = "INSERT INTO INVENTARIO_ORDEN_CONTEO_LINEA SET
                                        ID_INVENTARIO_ORDEN_CONTEO = $idConteo 
                                        , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                        , ID_UBICACION = $rowUbicacionStockExternalizado->ID_UBICACION
                                        , ID_MATERIAL = $idMaterial
                                        , ID_MATERIAL_FISICO = NULL
                                        , ID_TIPO_BLOQUEO = NULL
                                        , CANTIDAD_SISTEMA = 0  
                                        , CANTIDAD_SISTEMA_ALMACEN_SAP = 0 
                                        , CANTIDAD_CONTADA = 0
                                        , CANTIDAD_RECUENTO = 0
                                        , DIFERENCIA_PORCENTAJE = 0
                                        , CANTIDAD_DIFERENCIA = $cantidadAjusteCorrecto
                                        , DESVIACION = 0
                                        , LINEA_CONTEO_SAP = ''
                                        , FECHA_PROCESADO = '" . date("Y-m-d H:i:s") . "'
                                        , LINEA_CONTEO_NUESTRO = '001'";
                        $bd->ExecSQL($sqlInsert);
                        $idLineaConteo = $bd->IdAsignado();

                        //OBTENEMOS EL CONTEO
                        $rowInventarioOrdenConteoLinea = $bd->VerReg("INVENTARIO_ORDEN_CONTEO_LINEA", "ID_INVENTARIO_ORDEN_CONTEO_LINEA", $idLineaConteo);

                        //SI LA FECHA PROCESADO INFORME FACTURACION ES CERO, LA ACTUALIZO
                        if ($rowInventarioOrdenConteoLinea->FECHA_PROCESADO_INFORME_FACTURACION == "0000-00-00 00:00:00"):
                            $sqlUpdate = "UPDATE INVENTARIO_ORDEN_CONTEO_LINEA SET
                                            FECHA_PROCESADO_INFORME_FACTURACION = '" . date("Y-m-d H:i:s") . "'
                                          WHERE ID_INVENTARIO_ORDEN_CONTEO_LINEA = $idLineaConteo";
                            $bd->ExecSQL($sqlUpdate);
                        endif;

                        // LOG MOVIMIENTOS
                        $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Ajuste de Inventario", $idConteo, "Creación línea ajuste de inventario" . " " . $idLineaConteo);

                        //COMPRUEBO SI EXISTE MATERIAL UBICACION
                        if (!$ubicacion->Existe_Registro_Ubicacion_Material($rowUbicacionStockExternalizado->ID_UBICACION, $idMaterial, NULL, NULL, NULL, NULL)):
                            //CREO MATERIAL UBICACION SI NO EXISTE
                            $sql = "INSERT INTO MATERIAL_UBICACION SET
                                ID_MATERIAL = $idMaterial
                                , ID_MATERIAL_FISICO = NULL
                                , ID_UBICACION = $rowUbicacionStockExternalizado->ID_UBICACION
                                , ID_TIPO_BLOQUEO = NULL
                                , ID_ORDEN_TRABAJO_MOVIMIENTO = NULL
                                , ID_INCIDENCIA_CALIDAD = NULL";
                            $bd->ExecSQL($sql);
                        endif;

                        //AÑADIMOS EL STOCK EN LA UBICACIÓN DE STOCK EXTERNALIZADO
                        $sqlUpdateStockExternalizado = "UPDATE MATERIAL_UBICACION SET
                                                STOCK_TOTAL = STOCK_TOTAL + $cantidadAjusteCorrecto
                                                , STOCK_OK = STOCK_OK + $cantidadAjusteCorrecto
                                                WHERE ID_UBICACION = $rowUbicacionStockExternalizado->ID_UBICACION AND ID_MATERIAL = $idMaterial";
                        $bd->ExecSQL($sqlUpdateStockExternalizado);

                        //CREO EL ASIENTO
                        $sqlInsertAsiento = "INSERT INTO ASIENTO SET
                                        ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                        , ID_MATERIAL = $idMaterial
                                        , TIPO_LOTE = 'ninguno'
                                        , ID_MATERIAL_FISICO = NULL
                                        , ID_UBICACION = $rowUbicacionStockExternalizado->ID_UBICACION
                                        , FECHA = '" . date("Y-m-d H:i:s") . "'
                                        , CANTIDAD = $cantidadAjusteCorrecto
                                        , STOCK_OK = $cantidadAjusteCorrecto
                                        , STOCK_BLOQUEADO = 0
                                        , ID_TIPO_BLOQUEO = NULL
                                        , OBSERVACIONES = ''
                                        , TIPO_ASIENTO = 'Stock Externalizado'
                                        , ID_INVENTARIO_ORDEN_CONTEO_LINEA = $idLineaConteo";
                        $bd->ExecSQL($sqlInsertAsiento);

                        //ACTUALIZO EL CONTEO A ESTADO FINALIZADO
                        $sqlUpdate = "UPDATE INVENTARIO_ORDEN_CONTEO SET
                                ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                , FECHA = '" . date("Y-m-d H:i:s") . "'
                                , FECHA_PROCESADO = '" . date("Y-m-d H:i:s") . "'
                                , ESTADO = 'Finalizado'
                                WHERE ID_INVENTARIO_ORDEN_CONTEO = $idConteo";
                        $bd->ExecSQL($sqlUpdate);

                //OBTENEMOS EL CONTEO
                $rowInventarioOrdenConteo = $bd->VerReg("INVENTARIO_ORDEN_CONTEO", "ID_INVENTARIO_ORDEN_CONTEO", $idConteo);

                //SI LA FECHA PROCESADO INFORME FACTURACION ES CERO, LA ACTUALIZO
                if ($rowInventarioOrdenConteo->FECHA_PROCESADO_INFORME_FACTURACION == "0000-00-00 00:00:00"):
                    $sqlUpdate = "UPDATE INVENTARIO_ORDEN_CONTEO SET
                                    FECHA_PROCESADO_INFORME_FACTURACION = '" . date("Y-m-d H:i:s") . "'
                                  WHERE ID_INVENTARIO_ORDEN_CONTEO = $idConteo";
                    $bd->ExecSQL($sqlUpdate);
                endif;

                        //OBTENEMOS EL CONTEO
                        $rowInventarioOrdenConteo = $bd->VerReg("INVENTARIO_ORDEN_CONTEO", "ID_INVENTARIO_ORDEN_CONTEO", $idConteo);

                        //SI LA FECHA PROCESADO INFORME FACTURACION ES CERO, LA ACTUALIZO
                        if ($rowInventarioOrdenConteo->FECHA_PROCESADO_INFORME_FACTURACION == "0000-00-00 00:00:00"):
                            $sqlUpdate = "UPDATE INVENTARIO_ORDEN_CONTEO SET
                                            FECHA_PROCESADO_INFORME_FACTURACION = '" . date("Y-m-d H:i:s") . "'
                                          WHERE ID_INVENTARIO_ORDEN_CONTEO = $idConteo";
                            $bd->ExecSQL($sqlUpdate);
                        endif;

                        // LOG MOVIMIENTOS
                        $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Ajuste de Inventario", $idConteo, "Procesar ajuste de inventario");

                    endif;

                    //AHORA HAGO LOS AJUSTES NECESARIOS PARA DECREMENTAR EL STOCK EN EL ALMACÉN DE STOCK EXTERNALIZADO
                    //OBTENGO EL MOTIVO DEL AJUSTE DE LA BBDD
                    $GLOBALS["NotificaErrorPorEmail"] = "No";
                    $rowMotivoAjuste                  = $bd->VerRegRest("MOTIVO_AJUSTE_INVENTARIO", "NOMBRE = 'Ajustar stock en almacén externalizado' AND BAJA = 0", "No");

                    $html->PagErrorCondicionado($rowMotivoAjuste, "==", false, "MotivoAjusteNoExiste");

                    //CREAMOS LA ORDEN DE CONTEO
                    $sqlInsert = "INSERT INTO INVENTARIO_ORDEN_CONTEO SET
                            ID_INVENTARIO = NULL
                            , TIPO = 'Recepcion Stock Externalizado Manual'
                            , ID_MOTIVO_AJUSTE_INVENTARIO = $rowMotivoAjuste->ID_MOTIVO_AJUSTE_INVENTARIO
                            , ID_ALMACEN = $rowAlmacenStockExternalizado->ID_ALMACEN
                            , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                            , FECHA = '" . date("Y-m-d H:i:s") . "'
                            , ESTADO = 'En proceso'";
                    $bd->ExecSQL($sqlInsert);
                    $idConteo = $bd->IdAsignado();

                    // LOG MOVIMIENTOS
                    $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Creación", "Ajuste de Inventario", $idConteo, "");

                    //CREAMOS LA LÍNEA DE LA ORDEN DE CONTEO
                    $sqlInsert = "INSERT INTO INVENTARIO_ORDEN_CONTEO_LINEA SET
                            ID_INVENTARIO_ORDEN_CONTEO = $idConteo 
                            , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                            , ID_UBICACION = $rowUbicacionStockExternalizado->ID_UBICACION
                            , ID_MATERIAL = $idMaterial
                            , ID_MATERIAL_FISICO = NULL
                            , ID_TIPO_BLOQUEO = NULL
                            , CANTIDAD_SISTEMA = 0  
                            , CANTIDAD_SISTEMA_ALMACEN_SAP = 0 
                            , CANTIDAD_CONTADA = 0
                            , CANTIDAD_RECUENTO = 0
                            , DIFERENCIA_PORCENTAJE = 0
                            , CANTIDAD_DIFERENCIA = -$cantidad
                            , DESVIACION = 0
                            , LINEA_CONTEO_SAP = ''
                            , FECHA_PROCESADO = '" . date("Y-m-d H:i:s") . "'
                            , LINEA_CONTEO_NUESTRO = '001'";
                    $bd->ExecSQL($sqlInsert);
                    $idLineaConteo = $bd->IdAsignado();

                    //OBTENEMOS EL CONTEO
                    $rowInventarioOrdenConteoLinea = $bd->VerReg("INVENTARIO_ORDEN_CONTEO_LINEA", "ID_INVENTARIO_ORDEN_CONTEO_LINEA", $idLineaConteo);

                    //SI LA FECHA PROCESADO INFORME FACTURACION ES CERO, LA ACTUALIZO
                    if ($rowInventarioOrdenConteoLinea->FECHA_PROCESADO_INFORME_FACTURACION == "0000-00-00 00:00:00"):
                        $sqlUpdate = "UPDATE INVENTARIO_ORDEN_CONTEO_LINEA SET
                                        FECHA_PROCESADO_INFORME_FACTURACION = '" . date("Y-m-d H:i:s") . "'
                                      WHERE ID_INVENTARIO_ORDEN_CONTEO_LINEA = $idLineaConteo";
                        $bd->ExecSQL($sqlUpdate);
                    endif;

                    // LOG MOVIMIENTOS
                    $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Ajuste de Inventario", $idConteo, "Creación línea ajuste de inventario" . " " . $idLineaConteo);

                    $sqlUpdateStockExternalizado = "UPDATE MATERIAL_UBICACION SET
                                            STOCK_TOTAL = STOCK_TOTAL - $cantidad
                                            , STOCK_OK = STOCK_OK - $cantidad
                                            WHERE ID_UBICACION = $rowUbicacionStockExternalizado->ID_UBICACION AND ID_MATERIAL = $idMaterial";
                    $bd->ExecSQL($sqlUpdateStockExternalizado);

                    //CREO EL ASIENTO
                    $sqlInsertAsiento = "INSERT INTO ASIENTO SET
                                    ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                    , ID_MATERIAL = $idMaterial
                                    , TIPO_LOTE = 'ninguno'
                                    , ID_MATERIAL_FISICO = NULL
                                    , ID_UBICACION = $rowUbicacionStockExternalizado->ID_UBICACION
                                    , FECHA = '" . date("Y-m-d H:i:s") . "'
                                    , CANTIDAD = -$cantidad
                                    , STOCK_OK = -$cantidad
                                    , STOCK_BLOQUEADO = 0
                                    , ID_TIPO_BLOQUEO = NULL
                                    , OBSERVACIONES = ''
                                    , TIPO_ASIENTO = 'Stock Externalizado'
                                    , ID_INVENTARIO_ORDEN_CONTEO_LINEA = $idLineaConteo";
                    $bd->ExecSQL($sqlInsertAsiento);

                    //ACTUALIZO EL CONTEO A ESTADO FINALIZADO
                    $sqlUpdate = "UPDATE INVENTARIO_ORDEN_CONTEO SET
                        ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                        , FECHA = '" . date("Y-m-d H:i:s") . "'
                        , FECHA_PROCESADO = '" . date("Y-m-d H:i:s") . "'
                        , ESTADO = 'Finalizado'
                        WHERE ID_INVENTARIO_ORDEN_CONTEO = $idConteo";
                    $bd->ExecSQL($sqlUpdate);

                    //OBTENEMOS EL CONTEO
                    $rowInventarioOrdenConteo = $bd->VerReg("INVENTARIO_ORDEN_CONTEO", "ID_INVENTARIO_ORDEN_CONTEO", $idConteo);

                    //SI LA FECHA PROCESADO INFORME FACTURACION ES CERO, LA ACTUALIZO
                    if ($rowInventarioOrdenConteo->FECHA_PROCESADO_INFORME_FACTURACION == "0000-00-00 00:00:00"):
                        $sqlUpdate = "UPDATE INVENTARIO_ORDEN_CONTEO SET
                                        FECHA_PROCESADO_INFORME_FACTURACION = '" . date("Y-m-d H:i:s") . "'
                                      WHERE ID_INVENTARIO_ORDEN_CONTEO = $idConteo";
                        $bd->ExecSQL($sqlUpdate);
                    endif;

                    // LOG MOVIMIENTOS
                    $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Ajuste de Inventario", $idConteo, "Procesar ajuste de inventario");

                endif;
            endif;
        endif;

        return $arrErrores;

    }

    /**
     * @param $idProveedor
     * $idCentro
     * $idMaterial
     * $cantidad
     * SI ANULAMOS LA RECEPCIÓN DE UN MOVIMIENTO DE ENTRADA QUE SE HIZO DESDE UN ALMACÉN DE STOCK EXTERNALIZADO, DEBEMOS INCREMENTAR EL STOCK DE DICHO MATERIAL EN EL ALMACÉN
     */
    function incrementarStockExternalizado($idProveedor, $idCentro, $idMaterial, $cantidad)
    {
        global $bd;
        global $administrador;
        global $auxiliar;
        global $html;
        global $ubicacion;

        //ARRAY DE ERRORES
        $arrErrores = array();

        //OBTENEMOS EL PROVEEDOR
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowProveedor                     = $bd->VerReg("PROVEEDOR", "ID_PROVEEDOR", $idProveedor, "No");

        //OBTENEMOS EL CENTRO
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowCentro                        = $bd->VerReg("CENTRO", "ID_CENTRO", $idCentro, "No");

        //OBTENEMOS EL MATERIAL
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowMaterial                      = $bd->VerReg("MATERIAL", "ID_MATERIAL", $idMaterial, "No");

        //CREAMOS MENSAJE DE ERROR GENÉRICO
        $errorGenerico = $auxiliar->traduce("Proveedor", $administrador->ID_IDIOMA) . ": " . $rowProveedor->REFERENCIA . " - " . $rowProveedor->NOMBRE . ". ";
        $errorGenerico .= $auxiliar->traduce("Centro", $administrador->ID_IDIOMA) . ": " . $rowCentro->REFERENCIA . " - " . $rowCentro->CENTRO . ". ";
        $errorGenerico .= $auxiliar->traduce("Material", $administrador->ID_IDIOMA) . ": " . $rowMaterial->REFERENCIA_SGA . " - " . $rowMaterial->{'DESCRIPCION' . ($administrador->ID_IDIOMA == 'ESP' ? '' : '_EN')} . ". ";

        //OTENEMOS EL ALMACÉN DE STOCK EXTERNALIZADO A PARTIR DEL PROVEEDOR Y CENTRO
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowAlmacenStockExternalizado     = $bd->VerRegRest("ALMACEN", "ID_PROVEEDOR = $idProveedor AND ID_CENTRO = $idCentro AND TIPO_ALMACEN = 'externalizado' AND BAJA = 0", "No");

        if ($rowAlmacenStockExternalizado):

            //SI EXISTE EL ALMACÉN DE STOCK EXTERNALIZADO PARA EL PROVEEDOR Y CENTRO, COMPRUEBO SI EXISTE EL REGISTRO MATERIAL-ALMACÉN
            $GLOBALS["NotificaErrorPorEmail"] = "No";
            $rowMaterialAlmacenExternalizado  = $bd->VerRegRest("MATERIAL_ALMACEN", "ID_ALMACEN = $rowAlmacenStockExternalizado->ID_ALMACEN AND ID_MATERIAL = $idMaterial AND BAJA = 0", "No");

            if ($rowMaterialAlmacenExternalizado):

                //OBTENEMOS LA UBICACIÓN DE DICHO ALMACÉN (ÚNICAMENTE HAY UNA UBICACIÓN POR ALMACÉN)
                $GLOBALS["NotificaErrorPorEmail"] = "No";
                $rowUbicacionStockExternalizado   = $bd->VerRegRest("UBICACION", "ID_ALMACEN = $rowAlmacenStockExternalizado->ID_ALMACEN AND UBICACION = 'EXT_" . $rowAlmacenStockExternalizado->REFERENCIA . "' AND BAJA = 0", "No");

                //SI HAY STOCK, SUMAMOS LA CANTIDAD DEVUELTA
                //PRIMERO REALIZAMOS UN AJUSTE POSITIVO EN EL ALMACÉN DE STOCK EXTERNALIZADO
                //OBTENGO EL MOTIVO DEL AJUSTE DE LA BBDD
                $GLOBALS["NotificaErrorPorEmail"] = "No";
                $rowMotivoAjuste                  = $bd->VerRegRest("MOTIVO_AJUSTE_INVENTARIO", "NOMBRE = 'Ajustar stock en almacén externalizado' AND BAJA = 0", "No");

                $html->PagErrorCondicionado($rowMotivoAjuste, "==", false, "MotivoAjusteNoExiste");

                //CREAMOS LA ORDEN DE CONTEO
                $sqlInsert = "INSERT INTO INVENTARIO_ORDEN_CONTEO SET
                    ID_INVENTARIO = NULL
                    , TIPO = 'Anulacion Recepcion Stock Externalizado Manual'
                    , ID_MOTIVO_AJUSTE_INVENTARIO = $rowMotivoAjuste->ID_MOTIVO_AJUSTE_INVENTARIO
                    , ID_ALMACEN = $rowAlmacenStockExternalizado->ID_ALMACEN
                    , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                    , FECHA = '" . date("Y-m-d H:i:s") . "'
                    , ESTADO = 'En proceso'";
                $bd->ExecSQL($sqlInsert);
                $idConteo = $bd->IdAsignado();

                // LOG MOVIMIENTOS
                $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Creación", "Ajuste de Inventario", $idConteo, "");

                //CREAMOS LA LÍNEA DE LA ORDEN DE CONTEO
                $sqlInsert = "INSERT INTO INVENTARIO_ORDEN_CONTEO_LINEA SET
                    ID_INVENTARIO_ORDEN_CONTEO = $idConteo 
                    , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                    , ID_UBICACION = $rowUbicacionStockExternalizado->ID_UBICACION
                    , ID_MATERIAL = $idMaterial
                    , ID_MATERIAL_FISICO = NULL
                    , ID_TIPO_BLOQUEO = NULL
                    , CANTIDAD_SISTEMA = 0  
                    , CANTIDAD_SISTEMA_ALMACEN_SAP = 0 
                    , CANTIDAD_CONTADA = 0
                    , CANTIDAD_RECUENTO = 0
                    , DIFERENCIA_PORCENTAJE = 0
                    , CANTIDAD_DIFERENCIA = $cantidad
                    , DESVIACION = 0
                    , LINEA_CONTEO_SAP = ''
                    , FECHA_PROCESADO = '" . date("Y-m-d H:i:s") . "'
                    , LINEA_CONTEO_NUESTRO = '001'";
                $bd->ExecSQL($sqlInsert);
                $idLineaConteo = $bd->IdAsignado();

                // LOG MOVIMIENTOS
                $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Ajuste de Inventario", $idConteo, "Creación línea ajuste de inventario" . " " . $idLineaConteo);

                //COMPRUEBO SI EXISTE MATERIAL UBICACION
                if (!$ubicacion->Existe_Registro_Ubicacion_Material($rowUbicacionStockExternalizado->ID_UBICACION, $idMaterial, NULL, NULL, NULL, NULL)):
                    //CREO MATERIAL UBICACION SI NO EXISTE
                    $sql = "INSERT INTO MATERIAL_UBICACION SET
                                ID_MATERIAL = $idMaterial
                                , ID_MATERIAL_FISICO = NULL
                                , ID_UBICACION = $rowUbicacionStockExternalizado->ID_UBICACION
                                , ID_TIPO_BLOQUEO = NULL
                                , ID_ORDEN_TRABAJO_MOVIMIENTO = NULL
                                , ID_INCIDENCIA_CALIDAD = NULL";
                    $bd->ExecSQL($sql);
                endif;

                //AÑADIMOS EL STOCK EN LA UBICACIÓN DE STOCK EXTERNALIZADO
                $sqlUpdateStockExternalizado = "UPDATE MATERIAL_UBICACION SET
                                                    STOCK_TOTAL = STOCK_TOTAL + $cantidad
                                                    , STOCK_OK = STOCK_OK + $cantidad
                                                WHERE ID_UBICACION = $rowUbicacionStockExternalizado->ID_UBICACION AND ID_MATERIAL = $idMaterial";
                $bd->ExecSQL($sqlUpdateStockExternalizado);

                //CREO EL ASIENTO
                $sqlInsertAsiento = "INSERT INTO ASIENTO SET
                            ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                            , ID_MATERIAL = $idMaterial
                            , TIPO_LOTE = 'ninguno'
                            , ID_MATERIAL_FISICO = NULL
                            , ID_UBICACION = $rowUbicacionStockExternalizado->ID_UBICACION
                            , FECHA = '" . date("Y-m-d H:i:s") . "'
                            , CANTIDAD = $cantidad
                            , STOCK_OK = $cantidad
                            , STOCK_BLOQUEADO = 0
                            , ID_TIPO_BLOQUEO = NULL
                            , OBSERVACIONES = ''
                            , TIPO_ASIENTO = 'Stock Externalizado'
                            , ID_INVENTARIO_ORDEN_CONTEO_LINEA = $idLineaConteo";
                $bd->ExecSQL($sqlInsertAsiento);

                //ACTUALIZO EL CONTEO A ESTADO FINALIZADO
                $sqlUpdate = "UPDATE INVENTARIO_ORDEN_CONTEO SET
                        ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                        , FECHA = '" . date("Y-m-d H:i:s") . "'
                        , FECHA_PROCESADO = '" . date("Y-m-d H:i:s") . "'
                        , ESTADO = 'Finalizado'
                        WHERE ID_INVENTARIO_ORDEN_CONTEO = $idConteo";
                $bd->ExecSQL($sqlUpdate);

            //OBTENEMOS EL CONTEO
            $rowInventarioOrdenConteo = $bd->VerReg("INVENTARIO_ORDEN_CONTEO", "ID_INVENTARIO_ORDEN_CONTEO", $idConteo);

            //SI LA FECHA PROCESADO INFORME FACTURACION ES CERO, LA ACTUALIZO
            if ($rowInventarioOrdenConteo->FECHA_PROCESADO_INFORME_FACTURACION == "0000-00-00 00:00:00"):
                $sqlUpdate = "UPDATE INVENTARIO_ORDEN_CONTEO SET
                                FECHA_PROCESADO_INFORME_FACTURACION = '" . date("Y-m-d H:i:s") . "'
                              WHERE ID_INVENTARIO_ORDEN_CONTEO = $idConteo";
                $bd->ExecSQL($sqlUpdate);
            endif;

                // LOG MOVIMIENTOS
                $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Ajuste de Inventario", $idConteo, "Procesar ajuste de inventario");

            endif;
        endif;

        return $arrErrores;

    }

    /**
     * @param $idMovimientoEntrada
     * $txFechaContabilizacion
     * $mostrar_error SI EL VALOR ES 'texto' EN VEZ DE SALIR PAGINA DE ERROR SE OBTENDRA UN TEXTO
     * REALIZA LAS ACCIONES QUE POR LA LOGICA DEL PROCESO DE EJECUTAN DESPUES DE RECEPCIONAR EL MOVIMIENTO DE ENTRADA
     */
    function ProcesarEntradaMaterial($idMovimiento, $txFechaContabilizacion = "", $mostrar_error = "")
    {
        global $bd;
        global $administrador;
        global $auxiliar;
        global $sap;
        global $html;
        global $mat;
        global $ubicacion;
        global $orden_trabajo;
        global $orden_transporte;
        global $reserva;

        //ARRAY ERRORES
        $arr_error = array();

        //ARRAY CON LOS PEDIDOS IMPLICADOS
        $arrayLineasPedidosInvolucradas = array();

        //ARRAY PARA GUARDARME LAS NECESIDADES ASOCIADAS A LA LINEA RECEPCIONADA
        $arrNecesidadesAsociadasLineaRecepcion = array();

        //ARRAY PARA GUARDARME LAS LINEAS DE PEDIDO ASOCIADOS A LA LINEA RECEPCIONADA
        $arrLineasPedidoAsociadaLineaRecepcion = array();

        //ARRAY CAMBIOS REFERENCIA
        $arrCambioReferencia = array();

        //BUSCO EL TIPO BLOQUEO = CCNP (Control de Calidad de material no preventivo)
        $rowBloqueoCCNP = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "CCNP", "No");

        //BUSCO EL TIPO BLOQUEO = CCP (Control de Calidad de material preventivo)
        $rowBloqueoCCP = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "CCP", "No");

        //BUSCO EL TIPO DE BLOQUEO PREVENTIVO
        $rowTipoBloqueoPreventivo = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "SP", "No");

        //BUSCO LOS TIPOS DE BLOQUEO PDTE CALIDAD Y DC, DEPENDIENDO DE LA DECISION DE CALIDAD PARA LOTES DE FABRICACION SE PUEDEN RECEPCIONAR ASI
        $rowTipoBloqueoPdteCalidad      = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "PC", "No");
        $rowTipoBloqueoRechazadoCalidad = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "SPDPC", "No");

        if ($rowBloqueoCCNP == false || $rowBloqueoCCP == false || $rowTipoBloqueoPreventivo == false || $rowTipoBloqueoPdteCalidad == false || $rowTipoBloqueoRechazadoCalidad == false):
            if ($mostrar_error == 'texto'):
                $arr_error['ERROR']     = $auxiliar->traduce("El Tipo de Bloqueo no existe", $administrador->ID_IDIOMA);
                $arr_error['ERROR_SGA'] = 1;

                return $arr_error;
            else:
                $html->PagError("TipoBloqueoNoExiste");
            endif;
        endif;

        //BLOQUEAMOS EL MOVIMIENTO DE ENTRADA
        $sqlMov    = "SELECT * FROM MOVIMIENTO_ENTRADA WHERE ID_MOVIMIENTO_ENTRADA = $idMovimiento FOR UPDATE";
        $resultMov = $bd->ExecSQL($sqlMov);
        $rowMov    = $bd->SigReg($resultMov);

        //COMPRUEBO QUE NO ESTE DADO DE BAJA
        if ($rowMov->BAJA == 1):
            if ($mostrar_error == 'texto'):
                $arr_error['ERROR']     = $auxiliar->traduce("No se puede realizar la operación correspondiente porque el movimiento está dado de baja", $administrador->ID_IDIOMA);
                $arr_error['ERROR_SGA'] = 1;

                return $arr_error;
            else:
                $html->PagError("MovimientoBaja");
            endif;
        endif;

        // 1. COMPRUEBO QUE SE ESTA EN EL ESTADO CORRECTO
        if ($rowMov->ESTADO != "En Proceso"):
            if ($mostrar_error == 'texto'):
                $arr_error['ERROR']     = $auxiliar->traduce("No es posible realizar esta acción en el estado actual del Movimiento", $administrador->ID_IDIOMA);
                $arr_error['ERROR_SGA'] = 1;

                return $arr_error;
            else:
                $html->PagError("AccionEnEstadoIncorrecto");
            endif;
        endif;
        $estadoInicialMovimiento = $rowMov->ESTADO;

        // BUSCO LA RECEPCION
        $rowDoc                 = $bd->VerReg("MOVIMIENTO_RECEPCION", "ID_MOVIMIENTO_RECEPCION", $rowMov->ID_MOVIMIENTO_RECEPCION);
        $estadoInicialRecepcion = $rowDoc->ESTADO;

        //SI ESTA ASOCIADO A UNA ORDEN DE TRANSPORTE, COMPROBAMOS QUE SI ES SIN PEDIDO CONOCIDO,NO SE PROCESA SIN ENTREGA ENTRANTE
        $rowOrdenTransporte = false;
        if ($rowDoc->ID_ORDEN_TRANSPORTE != ""):
            //BUSCO LA ORDEN DE TRANSPORTE
            $rowOrdenTransporte = $bd->VerReg("ORDEN_TRANSPORTE", "ID_ORDEN_TRANSPORTE", $rowDoc->ID_ORDEN_TRANSPORTE);

            //COMPRUEBO EL ESTADO DE LAS INTERFACES
            if (
                (($rowOrdenTransporte->MODELO_TRANSPORTE == 'Primero') && ($rowOrdenTransporte->TIENE_GASTOS_TRANSPORTE == 1) && ($rowOrdenTransporte->ESTADO_INTERFACES != 'Finalizada')) ||
                (($rowOrdenTransporte->MODELO_TRANSPORTE == 'Segundo') && ($rowOrdenTransporte->TIENE_GASTOS_TRANSPORTE == 1) && ($rowOrdenTransporte->ESTADO_INTERFACES != 'ZTL Transmitidos'))
            ):
                if ($mostrar_error == 'texto'):
                    $arr_error['ERROR']     = $auxiliar->traduce("Se deben transmitir todas las Interfaces desde la Orden de Transporte antes de poder Procesar la Entrada de Material", $administrador->ID_IDIOMA);
                    $arr_error['ERROR_SGA'] = 1;

                    return $arr_error;
                else:
                    $html->PagError("FaltaEnvioInterfaces");
                endif;
            endif;
        endif;

        //COMPROBAMOS QUE SI TIENE ASOCIADO UN CONTENEDOR QUE ESTA MARCADADO PARA CALIDAD, SE HAYAN SELECCIONADO LAS CAJAS SUFICIENTES
        if ($rowDoc->ID_CONTENEDOR_ENTRANTE != NULL):
            //BUSCO EL CONTENEDOR ENTRANTE
            $rowContenedorEntrante = $bd->VerReg("CONTENEDOR_ENTRANTE", "ID_CONTENEDOR_ENTRANTE", $rowDoc->ID_CONTENEDOR_ENTRANTE);
            //SI EL CONTENEDOR ENTRANTE ESTA MARCADO PARA CONTROL DE CALIDAD
            if ($rowContenedorEntrante->MARCADO_PARA_CONTROL_CALIDAD == 1):

                //BUSCO LAS CAJAS DEL MOVIMIENTO DE ENTRADA
                $sqlCajas                       = "SELECT DISTINCT MF.NUMERO_ETIQUETA_CAJA
                                                     FROM MOVIMIENTO_ENTRADA_LINEA MEL
                                                     INNER JOIN MATERIAL_FISICO MF ON MF.ID_MATERIAL_FISICO = MEL.ID_MATERIAL_FISICO
                                                     WHERE MEL.ID_MOVIMIENTO_ENTRADA = $rowMov->ID_MOVIMIENTO_ENTRADA AND MEL.LINEA_ANULADA = 0 AND MEL.BAJA = 0 AND MF.MARCADA_PARA_CONTROL_CALIDAD = 1";
                $resultCajas                    = $bd->ExecSQL($sqlCajas);
                $numCajasMarcadasControlCalidad = $bd->NumRegs($resultCajas);

                //SI AUN NO HAY SUFICINETES CAJAS MARCADAS, DESHABILITAMOS EL BOTON DE PROCESAR
                if ((int)$numCajasMarcadasControlCalidad == "0"):
                    if ($mostrar_error == 'texto'):
                        $arr_error['ERROR']     = $auxiliar->traduce("No se han marcado suficientes cajas del contenedor para pasar Control de Calidad", $administrador->ID_IDIOMA);
                        $arr_error['ERROR_SGA'] = 1;

                        return $arr_error;
                    else:
                        $html->PagError("FaltanMarcarCajasCC");
                    endif;
                endif;
            endif;
        endif;

        // 2. COMPRUEBO QUE TENGA LINEAS
        $clausulaWhere = "ID_MOVIMIENTO_ENTRADA = $idMovimiento AND BAJA = 0";
        $numLineas     = $bd->NumRegsTabla("MOVIMIENTO_ENTRADA_LINEA", $clausulaWhere);
        if ($numLineas == 0):
            if ($mostrar_error == 'texto'):
                $arr_error['ERROR']     = $auxiliar->traduce("El movimiento no tiene articulos", $administrador->ID_IDIOMA);
                $arr_error['ERROR_SGA'] = 1;

                return $arr_error;
            else:
                $html->PagError("SinLineas");
            endif;
        endif;

        // 3. COMPRUEBO TODAS LAS LINEAS UBICADAS Y CON CANTIDADES Y ESTADO CORRECTAS
        $clausulaWhere      = "ID_MOVIMIENTO_ENTRADA = $idMovimiento AND (ID_UBICACION = 0 OR CANTIDAD = 0) AND BAJA = 0";
        $numLineasSinUbicar = $bd->NumRegsTabla("MOVIMIENTO_ENTRADA_LINEA", $clausulaWhere);
        if ($numLineasSinUbicar > 0):
            if ($mostrar_error == 'texto'):
                $arr_error['ERROR']     = $auxiliar->traduce("Existen lineas sin ubicación, con cantidad Cero o sin numeros de serie/lote", $administrador->ID_IDIOMA);
                $arr_error['ERROR_SGA'] = 1;

                return $arr_error;
            else:
                $html->PagError("ExistenLineasSinUbicar");
            endif;
        endif;

        //EL USUARIO PODRA PROCESAR EL MOVIMIENTO SI TIENE PERMISO DE ESCRITURA PARA EL ALMACEN DESTINO DE TODAS SUS LINEAS
        //AÑADO SEGURIDAD DE ACCESO DE ALMACENES
        if ($administrador->esRestringidoPorZonas()):
            $joinAlmacenPermisosZonas  = " INNER JOIN UBICACION U ON (mel.ID_UBICACION=U.ID_UBICACION)
										   INNER JOIN ALMACEN APZ ON (U.ID_ALMACEN=APZ.ID_ALMACEN) ";
            $whereAlmacenPermisosZonas = " AND APZ.ID_ALMACEN IN " . ($administrador->listadoAlmacenesPermiso("Escritura", "STRING")) . " ";

            // LINEAS CON PERMISO
            $sql                 = "SELECT * FROM MOVIMIENTO_ENTRADA_LINEA mel
                                    $joinAlmacenPermisosZonas
                                    WHERE mel.ID_MOVIMIENTO_ENTRADA = $idMovimiento AND mel.BAJA = 0
                            $whereAlmacenPermisosZonas";
            $resultLineasPermiso = $bd->ExecSQL($sql);

            //LINEAS TODAS
            $sqlLineasTodas    = "SELECT *
                                    FROM MOVIMIENTO_ENTRADA_LINEA MEL 							
                                    WHERE MEL.ID_MOVIMIENTO_ENTRADA = $idMovimiento AND MEL.BAJA = 0";
            $resultLineasTodas = $bd->ExecSQL($sqlLineasTodas);

            //COMPROBACION PERMISO
            $puedeProcesar = $bd->NumRegs($resultLineasTodas) == $bd->NumRegs($resultLineasPermiso);
            if ($puedeProcesar == false):
                if ($mostrar_error == 'texto'):
                    $arr_error['ERROR']     = $auxiliar->traduce("No tiene permisos para realizar esta operacion en esta subzona", $administrador->ID_IDIOMA);
                    $arr_error['ERROR_SGA'] = 1;

                    return $arr_error;
                else:
                    $html->PagError("SinPermisosSubzona");
                endif;
            endif;
        endif;

        // SELECCIONO LAS LÍNES DE ENTRADA CON LA CANTIDAD TOTAL DE CADA ENTRADA
        $sql          = "SELECT ID_MOVIMIENTO_ENTRADA, ID_MATERIAL, ID_MATERIAL_FISICO, TIPO_LOTE, SUM(CANTIDAD) AS totalCantidadMat, ID_PEDIDO, ID_PEDIDO_LINEA, ID_MOVIMIENTO_SALIDA_LINEA
                            FROM MOVIMIENTO_ENTRADA_LINEA 
                            WHERE ID_MOVIMIENTO_ENTRADA=" . $rowMov->ID_MOVIMIENTO_ENTRADA . " AND BAJA = 0
                            GROUP BY ID_MATERIAL, ID_MATERIAL_FISICO, ID_PEDIDO_LINEA, ID_MOVIMIENTO_SALIDA_LINEA 
                            ORDER BY ID_MOVIMIENTO_ENTRADA_LINEA";
        $resultLineas = $bd->ExecSQL($sql);

        // COMPROBAMOS QUE LOS NÚMEROS DE SERIE DE LA ENTRADA NO SE ENCUENTREN CON STOCK POSITIVO EN EL SISTEMA
        $hayErrorSerieLoteEnSistema  = false;
        $hayErrorSerieLoteEnTransito = false;
        $strErrorEnSistema           = "";
        $strErrorEnTransito          = "";
        $strError                    = "";
        while ($rowLinea = $bd->SigReg($resultLineas)):
            if (($rowMov->TIPO_MOVIMIENTO == 'PedidoProveedor') || ($rowMov->TIPO_MOVIMIENTO == 'PedidoDevolucionVenta') || ($rowMov->TIPO_MOVIMIENTO == 'PedidoCompraSGAManual') || ($rowMov->TIPO_MOVIMIENTO == 'Construccion')):
                //BUSCO LA LINEA DEL PEDIDO DE ENTRADA CORRESPONDIENTE
                $rowPedLin = $bd->VerReg("PEDIDO_ENTRADA_LINEA", "ID_PEDIDO_ENTRADA_LINEA", $rowLinea->ID_PEDIDO_LINEA);

                //GUARDO LOS PEDIDOS DE ENTRADA INVOLUCRADOS EN ESTE MOVIMIENTO
                if (!in_array($rowPedLin->ID_PEDIDO_ENTRADA_LINEA, (array) $arrayLineasPedidosInvolucradas)):
                    $arrayLineasPedidosInvolucradas[] = $rowPedLin->ID_PEDIDO_ENTRADA_LINEA;
                endif;
            elseif ($rowMov->TIPO_MOVIMIENTO == 'PedidoTraslado'):
                //BUSCO EL MOVIMIENTO DE SALIDA
                $rowMovSalLin = $bd->VerReg("MOVIMIENTO_SALIDA_LINEA", "ID_MOVIMIENTO_SALIDA_LINEA", $rowLinea->ID_MOVIMIENTO_SALIDA_LINEA);
                //BUSCO LA LINEA DEL PEDIDO DE SALIDA CORRESPONDIENTE
                $rowPedLin = $bd->VerReg("PEDIDO_SALIDA_LINEA", "ID_PEDIDO_SALIDA_LINEA", $rowMovSalLin->ID_PEDIDO_SALIDA_LINEA);
            endif;

            if (($rowLinea->ID_MATERIAL_FISICO != NULL) && ($rowMov->TIPO_MOVIMIENTO <> 'DevolucionOM') && ($rowMov->TIPO_MOVIMIENTO <> 'MultiOM')):
                //BUSCO EL MATERIAL
                $rowMat = $bd->VerReg("MATERIAL", "ID_MATERIAL", $rowLinea->ID_MATERIAL);

                //BUSCO EL MATERIAL ALMACEN
                $rowMatAlm = $bd->VerRegRest("MATERIAL_ALMACEN", "ID_MATERIAL = $rowLinea->ID_MATERIAL AND ID_ALMACEN = $rowPedLin->ID_ALMACEN");

                //COMPRUEBO EL TIPO DE MATERIAL ALMACEN COINCIDE CON EL TIPO DE MATERIAL FISICO
                if ($rowMatAlm->TIPO_LOTE != $rowLinea->TIPO_LOTE):
                    //BUSCO EL MATERIAL
                    $rowAlmDest = $bd->VerReg("ALMACEN", "ID_ALMACEN", $rowPedLin->ID_ALMACEN);

                    //BUSCO EL MATERIAL A PARTIR DEL MATERIAL FISICO
                    $rowMatFisOri = $bd->VerReg("MATERIAL_FISICO", "ID_MATERIAL_FISICO", $rowLinea->ID_MATERIAL_FISICO);

                    //BUSCO EL MATERIAL A PARTIR DEL MATERIAL FISICO
                    $rowMatOri = $bd->VerReg("MATERIAL", "ID_MATERIAL", $rowMatFisOri->ID_MATERIAL);

                    $strError = "<br>" . $auxiliar->traduce("El tipo de lote para el material", $administrador->ID_IDIOMA) . " " . $rowMatOri->REFERENCIA_SGA . " " . $auxiliar->traduce("en el movimiento es", $administrador->ID_IDIOMA) . ": " . $rowLinea->TIPO_LOTE . "<br>";
                    $strError .= $auxiliar->traduce("El tipo de lote para el material", $administrador->ID_IDIOMA) . " " . $rowMat->REFERENCIA_SGA . " " . $auxiliar->traduce("en el almacen", $administrador->ID_IDIOMA) . " " . $rowAlmDest->REFERENCIA . " " . $auxiliar->traduce("es", $administrador->ID_IDIOMA) . ": " . $rowMatAlm->TIPO_LOTE;
                    if ($mostrar_error == 'texto'):
                        $arr_error['ERROR']     = $auxiliar->traduce("El tipo de material no coincide con el especificado para el material y almacen", $administrador->ID_IDIOMA) . ".<br>" . $strError;
                        $arr_error['ERROR_SGA'] = 1;

                        return $arr_error;
                    else:
                        $html->PagError("TipoLoteDiferentes");
                    endif;
                endif;

                $rowMatFis = $bd->VerReg("MATERIAL_FISICO", "ID_MATERIAL_FISICO", $rowLinea->ID_MATERIAL_FISICO, "No");
                if ($rowMatFis->TIPO_LOTE == 'serie'): //MATERIAL SERIABLE
                    if (($rowMov->TIPO_MOVIMIENTO == 'PedidoProveedor') || ($rowMov->TIPO_MOVIMIENTO == 'PedidoDevolucionVenta') || ($rowMov->TIPO_MOVIMIENTO == 'PedidoCompraSGAManual') || ($rowMov->TIPO_MOVIMIENTO == 'Construccion')):
                        //BUSCO EL PEDIDO DE ENTRADA
                        $rowPed = $bd->VerReg("PEDIDO_ENTRADA", "ID_PEDIDO_ENTRADA", $rowLinea->ID_PEDIDO);

                        $sqlWhere = "";
                        //PEDIDO DE REPARACION O GARANTIA
                        if (($rowPed->TIPO_PEDIDO == "Reparación") || ($rowPed->TIPO_PEDIDO == "Garantía")):
                            $sqlWhere = "AND A.TIPO_ALMACEN != 'proveedor'";
                        endif;
                        $numMaterialSeriable = $bd->NumRegsTabla("MATERIAL_UBICACION MU INNER JOIN UBICACION U ON U.ID_UBICACION = MU.ID_UBICACION INNER JOIN ALMACEN A ON A.ID_ALMACEN = U.ID_ALMACEN", "MU.ID_MATERIAL_FISICO = $rowLinea->ID_MATERIAL_FISICO AND MU.STOCK_TOTAL > 0 $sqlWhere");
                        if ($numMaterialSeriable > 0):
                            $hayErrorSerieLoteEnSistema = true;
                            $strErrorEnSistema          .= "$rowMatFis->NUMERO_SERIE_LOTE<br />";
                        endif;
                    endif;

                    if ($mat->MaterialFisicoEnTransito($rowLinea->ID_MATERIAL_FISICO) == true):
                        $hayErrorSerieLoteEnTransito = true;
                        $strErrorEnTransito          .= "$rowMatFis->NUMERO_SERIE_LOTE<br />";
                    endif;
                endif; //FIN MATERIAL SERIABLE
            endif;
        endwhile;
        if ($hayErrorSerieLoteEnSistema == true):
            $strError = $strErrorEnSistema;
            if ($mostrar_error == 'texto'):
                $arr_error['ERROR']     = $auxiliar->traduce("No se puede procesar la entrada, los siguientes Números de Serie ya se encuentran con stock positivo en el sistema", $administrador->ID_IDIOMA) . ":<br /><br />$strError";
                $arr_error['ERROR_SGA'] = 1;

                return $arr_error;
            else:
                $html->PagError("NseriesYaSeEncuentranEnSistema");
            endif;
        endif;
        if ($hayErrorSerieLoteEnTransito == true):
            $strError = $strErrorEnTransito;
            if ($mostrar_error == 'texto'):
                $arr_error['ERROR']     = $auxiliar->traduce("No se puede procesar la entrada, los siguientes Números de Serie se encuentran en tránsito dentro del sistema", $administrador->ID_IDIOMA) . ":<br /><br />$strError";
                $arr_error['ERROR_SGA'] = 1;

                return $arr_error;
            else:
                $html->PagError("NseriesYaSeEncuentranEnTransito");
            endif;
        endif;

        //ACTUALIZO EL CAMPO ESTADO DE LA LINEA
        if (($rowDoc->VIA_RECEPCION == 'PDA')):
            $estado = 'Procesado';
        else:
            if ($rowMov->ADJUNTO == NULL):
                $estado = 'Ubicado';
            else:
                $estado = 'Escaneado y Finalizado';
            endif;
        endif;

//        $estado = $this->actualizarEstadoMovimientoEntrada($idMovimiento);
        // 4. ACTUALIZO EL MOVIMIENTO DE ENTRADA AL ESTADO CORRECTO Y FECHA CONTABILIZACION SI NO ESTA RELLENA
        $sql       = "UPDATE MOVIMIENTO_ENTRADA SET
                        ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                        , ESTADO = '" . $estado . "'
                        , FECHA_CONTABILIZACION = '" . ($txFechaContabilizacion != "" ? $auxiliar->fechaFmtoSQL($txFechaContabilizacion) : date("Y-m-d")) . "'
                        WHERE ID_MOVIMIENTO_ENTRADA = $idMovimiento";
        $resultAct = $bd->ExecSQL($sql);

        //BUSCO LOS TIPOS DE BLOQUEO QUE PASAN CONTROL DE CALIDAD
        $listaTiposBloqueoControlCalidad  = "";
        $coma                             = "";
        $sqlTiposBloqueoControlCalidad    = "SELECT ID_TIPO_BLOQUEO
                                                FROM TIPO_BLOQUEO 
                                                WHERE CONTROL_CALIDAD = 1";
        $resultTiposBloqueoControlCalidad = $bd->ExecSQL($sqlTiposBloqueoControlCalidad);
        if ($resultTiposBloqueoControlCalidad == false):
            $listaTiposBloqueoControlCalidad = 0;
        else:
            while ($rowTipoBloqueoControlCalidad = $bd->SigReg($resultTiposBloqueoControlCalidad)):
                $listaTiposBloqueoControlCalidad = $listaTiposBloqueoControlCalidad . $coma . $rowTipoBloqueoControlCalidad->ID_TIPO_BLOQUEO;
                $coma                            = ", ";
            endwhile;
        endif;

        //ACTUALIZO LAS LINEAS DEL MOVIMIENTO DE ENTRADA
        $sql = "UPDATE MOVIMIENTO_ENTRADA_LINEA SET
                FECHA_CONTABILIZACION = '" . ($txFechaContabilizacion != "" ? $auxiliar->fechaFmtoSQL($txFechaContabilizacion) : date("Y-m-d")) . "'
                WHERE ID_MOVIMIENTO_ENTRADA = $idMovimiento AND (ID_TIPO_BLOQUEO IS NULL OR ID_TIPO_BLOQUEO NOT IN ($listaTiposBloqueoControlCalidad))";
        $bd->ExecSQL($sql);

        // 5. SUMO AL STOCK LAS CANTIDADES INDICADAS
        $sql          = "SELECT *
                            FROM MOVIMIENTO_ENTRADA_LINEA 
                            WHERE ID_MOVIMIENTO_ENTRADA = $idMovimiento AND BAJA = 0
                            ORDER BY ID_MOVIMIENTO_ENTRADA_LINEA";
        $resultLineas = $bd->ExecSQL($sql);

        while ($rowLinea = $bd->SigReg($resultLineas)):
            //BUSCO EL MATERIAL
            $rowMat = $bd->VerReg("MATERIAL", "ID_MATERIAL", $rowLinea->ID_MATERIAL, "No");

            if (($rowMov->TIPO_MOVIMIENTO == 'PedidoProveedor') || ($rowMov->TIPO_MOVIMIENTO == 'PedidoDevolucionVenta') || ($rowMov->TIPO_MOVIMIENTO == 'PedidoCompraSGAManual') || ($rowMov->TIPO_MOVIMIENTO == 'Construccion')):
                //BUSCO LA LINEA DEL PEDIDO DE ENTRADA CORRESPONDIENTE
                $rowPedLin = $bd->VerReg("PEDIDO_ENTRADA_LINEA", "ID_PEDIDO_ENTRADA_LINEA", $rowLinea->ID_PEDIDO_LINEA);
            elseif ($rowMov->TIPO_MOVIMIENTO == 'PedidoTraslado'):
                //BUSCO EL MOVIMIENTO DE SALIDA
                $rowMovSalLin = $bd->VerReg("MOVIMIENTO_SALIDA_LINEA", "ID_MOVIMIENTO_SALIDA_LINEA", $rowLinea->ID_MOVIMIENTO_SALIDA_LINEA);
                //BUSCO LA LINEA DEL PEDIDO DE SALIDA CORRESPONDIENTE
                $rowPedLin = $bd->VerReg("PEDIDO_SALIDA_LINEA", "ID_PEDIDO_SALIDA_LINEA", $rowMovSalLin->ID_PEDIDO_SALIDA_LINEA);
            endif;

            if (($rowMov->TIPO_MOVIMIENTO == 'PedidoProveedor') || ($rowMov->TIPO_MOVIMIENTO == 'PedidoDevolucionVenta') || ($rowMov->TIPO_MOVIMIENTO == 'PedidoCompraSGAManual')):
                //COMPRUEBO QUE EL PEDIDO ESTA LIBERADO
                $rowPed = $bd->VerReg("PEDIDO_ENTRADA", "ID_PEDIDO_ENTRADA", $rowLinea->ID_PEDIDO);
                if (($rowPed->INDICADOR_LIBERACION != 'Liberado') && ($rowPed->INDICADOR_LIBERACION != 'No sujeto a liberación')):
                    if ($mostrar_error == 'texto'):
                        $arr_error['ERROR']     = $auxiliar->traduce("El Pedido no esta Liberado", $administrador->ID_IDIOMA);
                        $arr_error['ERROR_SGA'] = 1;

                        return $arr_error;
                    else:
                        $html->PagError("PedidoNoDisponible");
                    endif;
                endif;
            endif;

            //COMPROBAR QUE LA LINEA NO ESTE DADA DE BAJA
            if ($rowLinea->BAJA == 1):
                if ($mostrar_error == 'texto'):
                    $arr_error['ERROR']     = $auxiliar->traduce("La linea de movimiento esta dada de baja", $administrador->ID_IDIOMA);
                    $arr_error['ERROR_SGA'] = 1;

                    return $arr_error;
                else:
                    $html->PagError("LineaDeBaja");
                endif;
            endif;

            //COMPRUEBO MATERIAL FISICO
            if ($rowLinea->ID_MATERIAL_FISICO != NULL):
                $rowMatFis = $bd->VerReg("MATERIAL_FISICO", "ID_MATERIAL_FISICO", $rowLinea->ID_MATERIAL_FISICO, "No");
                //COMPRUEBO SI ES NECESARIO QUE LLEVE LOTE PROVEEDOR
                if (($rowMat->CON_LOTE_PROVEEDOR == 1) && ($rowMatFis->LOTE_PROVEEDOR == NULL)):
                    if ($mostrar_error == 'texto'):
                        $arr_error['ERROR']     = $auxiliar->traduce("No se ha introducido el Lote Proveedor", $administrador->ID_IDIOMA);
                        $arr_error['ERROR_SGA'] = 1;

                        return $arr_error;
                    else:
                        $html->PagError("LoteProveedorVacio");
                    endif;
                endif;

                //COMPRUEBO SI ES NECESARIO QUE LLEVE FECHA CADUCIDAD PROVEEDOR
                if (($rowMat->CON_FECHA_CADUCIDAD_PROVEEDOR == 1) && ($rowMatFis->FECHA_CADUCIDAD == NULL)):
                    if ($mostrar_error == 'texto'):
                        $arr_error['ERROR']     = $auxiliar->traduce("No se ha introducido la Fecha de Caducidad", $administrador->ID_IDIOMA);
                        $arr_error['ERROR_SGA'] = 1;

                        return $arr_error;
                    else:
                        $html->PagError("FechaCaducidadVacia");
                    endif;
                endif;
            endif;

            //COMPRUEBO QUE LA UBICACION DESTINO SEA CORRECTA
            $rowUbiDestinoMaterial = $bd->VerReg("UBICACION", "ID_UBICACION", $rowLinea->ID_UBICACION);

            //COMPRUEBO QUE LA UBICACION NO ESTE DADA DE BAJA
            if ($rowUbiDestinoMaterial->BAJA != 0):
                if ($mostrar_error == 'texto'):
                    $arr_error['ERROR']     = $auxiliar->traduce("La ubicación seleccionada está dada de baja", $administrador->ID_IDIOMA);
                    $arr_error['ERROR_SGA'] = 1;

                    return $arr_error;
                else:
                    $html->PagError("UbicacionBaja");
                endif;
            endif;

            if ($rowDoc->VIA_RECEPCION == 'WEB'): //ALMACEN SIN RADIOFRECUENCIA, LA UBICACION NO PUEDE TENER TIPO DE UBICACION
                if ($rowLinea->ID_TIPO_BLOQUEO != NULL): //MATERIAL CON BLOQUEO
                    $rowTipoBloqueo = $bd->VerReg("TIPO_BLOQUEO", "ID_TIPO_BLOQUEO", $rowLinea->ID_TIPO_BLOQUEO);
                    if ($rowTipoBloqueo->CONTROL_CALIDAD == 1): //BLOQUEO CON CONTROL DE CALIDAD
                        if ($rowUbiDestinoMaterial->TIPO_UBICACION != 'Calidad'):
                            $strError = "Material " . $rowMat->REFERENCIA_SGA . " - " . $rowMat->DESCRIPCION . ", Ubicación: " . $rowUbiDestinoMaterial->UBICACION;
                            if ($mostrar_error == 'texto'):
                                $arr_error['ERROR']     = $auxiliar->traduce("La ubicación de la línea no es válida para el almacén", $administrador->ID_IDIOMA) . ".<br>" . $strError;
                                $arr_error['ERROR_SGA'] = 1;

                                return $arr_error;
                            else:
                                $html->PagError("UbicacionDestinoNoValida");
                            endif;
                        endif;
                    else: //BLOQUEO SIN CONTROL DE CALIDAD
                        if ($rowUbiDestinoMaterial->TIPO_UBICACION != NULL):
                            $strError = "Material " . $rowMat->REFERENCIA_SGA . " - " . $rowMat->DESCRIPCION . ", Ubicación: " . $rowUbiDestinoMaterial->UBICACION;
                            if ($mostrar_error == 'texto'):
                                $arr_error['ERROR']     = $auxiliar->traduce("La ubicación de la línea no es válida para el almacén", $administrador->ID_IDIOMA) . ".<br>" . $strError;
                                $arr_error['ERROR_SGA'] = 1;

                                return $arr_error;
                            else:
                                $html->PagError("UbicacionDestinoNoValida");
                            endif;
                        endif;
                    endif;
                else: //MATERIAL SIN BLOQUEO
                    if (($rowUbiDestinoMaterial->TIPO_UBICACION != NULL) && ($rowMov->TIPO_MOVIMIENTO != 'Construccion')):
                        $strError = "Material " . $rowMat->REFERENCIA_SGA . " - " . $rowMat->DESCRIPCION . ", Ubicación: " . $rowUbiDestinoMaterial->UBICACION;
                        if ($mostrar_error == 'texto'):
                            $arr_error['ERROR']     = $auxiliar->traduce("La ubicación de la línea no es válida para el almacén", $administrador->ID_IDIOMA) . ".<br>" . $strError;
                            $arr_error['ERROR_SGA'] = 1;

                            return $arr_error;
                        else:
                            $html->PagError("UbicacionDestinoNoValida");
                        endif;
                    endif;
                endif;

            elseif ($rowDoc->VIA_RECEPCION == 'PDA'): //LA UBICACION TIENE QUE SER DE TIPO UBICACION ENTRADA
                if ($rowLinea->ID_TIPO_BLOQUEO != NULL): //MATERIAL CON BLOQUEO
                    $rowTipoBloqueo = $bd->VerReg("TIPO_BLOQUEO", "ID_TIPO_BLOQUEO", $rowLinea->ID_TIPO_BLOQUEO);
                    if ($rowTipoBloqueo->CONTROL_CALIDAD == 1): //BLOQUEO CON CONTROL DE CALIDAD
                        if ($rowUbiDestinoMaterial->TIPO_UBICACION != 'Calidad'):
                            $strError = "Material " . $rowMat->REFERENCIA_SGA . " - " . $rowMat->DESCRIPCION . ", Ubicación: " . $rowUbiDestinoMaterial->UBICACION;
                            if ($mostrar_error == 'texto'):
                                $arr_error['ERROR'] = $auxiliar->traduce("La ubicación de la línea no es válida para el almacén", $administrador->ID_IDIOMA) . ".<br>" . $strError;

                                return $arr_error;
                            else:
                                $html->PagError("UbicacionDestinoNoValida");
                            endif;
                        endif;
                    else: //BLOQUEO SIN CONTROL DE CALIDAD
                        if ($rowUbiDestinoMaterial->TIPO_UBICACION != 'Entrada'):
                            $strError = "Material " . $rowMat->REFERENCIA_SGA . " - " . $rowMat->DESCRIPCION . ", Ubicación: " . $rowUbiDestinoMaterial->UBICACION;
                            if ($mostrar_error == 'texto'):
                                $arr_error['ERROR'] = $auxiliar->traduce("La ubicación de la línea no es válida para el almacén", $administrador->ID_IDIOMA) . ".<br>" . $strError;

                                return $arr_error;
                            else:
                                $html->PagError("UbicacionDestinoNoValida");
                            endif;
                        endif;
                    endif;
                else: //MATERIAL SIN BLOQUEO
                    if ($rowUbiDestinoMaterial->TIPO_UBICACION != 'Entrada'):
                        $strError = "Material " . $rowMat->REFERENCIA_SGA . " - " . $rowMat->DESCRIPCION . ", Ubicación: " . $rowUbiDestinoMaterial->UBICACION;
                        if ($mostrar_error == 'texto'):
                            $arr_error['ERROR']     = $auxiliar->traduce("La ubicación de la línea no es válida para el almacén", $administrador->ID_IDIOMA) . ".<br>" . $strError;
                            $arr_error['ERROR_SGA'] = 1;

                                return $arr_error;
                            else:
                                $html->PagError("UbicacionDestinoNoValida");
                            endif;
                        endif;
                    endif;
                endif;

            //COMPROBAMOS QUE EL MATERIAL SE UBICA EN EL ALMACEN CORRESPONDIENTE
            if (($rowMov->TIPO_MOVIMIENTO == 'PedidoProveedor') || ($rowMov->TIPO_MOVIMIENTO == 'PedidoDevolucionVenta') || ($rowMov->TIPO_MOVIMIENTO == 'PedidoCompraSGAManual')):
                //BUSCO EL PEDIDO LINEA
                $rowPedidoLinea = $bd->VerReg("PEDIDO_ENTRADA_LINEA", "ID_PEDIDO_ENTRADA_LINEA", $rowLinea->ID_PEDIDO_LINEA);
                if ($rowPedidoLinea->ID_ALMACEN != $rowUbiDestinoMaterial->ID_ALMACEN):
                    if ($mostrar_error == 'texto'):
                        $arr_error['ERROR']     = $auxiliar->traduce("La ubicación seleccionada no pertenece al almacén esperado", $administrador->ID_IDIOMA);
                        $arr_error['ERROR_SGA'] = 1;

                        return $arr_error;
                    else:
                        $html->PagError("UbicacionAlmacenNoValida");
                    endif;
                endif;

            elseif ($rowMov->TIPO_MOVIMIENTO == 'PedidoTraslado'):
                //BUSCO EL ALBARAN
                $rowAlbaran = $bd->VerReg("ALBARAN", "ID_ALBARAN", $rowLinea->ID_ALBARAN);
                if ($rowAlbaran->ID_ALMACEN_DESTINO != $rowUbiDestinoMaterial->ID_ALMACEN):
                    if ($mostrar_error == 'texto'):
                        $arr_error['ERROR']     = $auxiliar->traduce("La ubicación seleccionada no pertenece al almacén esperado", $administrador->ID_IDIOMA);
                        $arr_error['ERROR_SGA'] = 1;

                        return $arr_error;
                    else:
                        $html->PagError("UbicacionAlmacenNoValida");
                    endif;
                endif;

            elseif (($rowMov->TIPO_MOVIMIENTO == 'RecepcionMaterialEstropeado') || ($rowMov->TIPO_MOVIMIENTO == 'RecepcionMaterialNoConforme')):
                //BUSCO EL MOVIMIENTO DE SALIDA LINEA
                $rowMovimientoSalidaLinea = $bd->VerReg("MOVIMIENTO_SALIDA_LINEA", "ID_MOVIMIENTO_SALIDA_LINEA", $rowLinea->ID_MOVIMIENTO_SALIDA_LINEA);
                if ($rowMovimientoSalidaLinea->ID_ALMACEN_DESTINO != $rowUbiDestinoMaterial->ID_ALMACEN):
                    if ($mostrar_error == 'texto'):
                        $arr_error['ERROR']     = $auxiliar->traduce("La ubicación seleccionada no pertenece al almacén esperado", $administrador->ID_IDIOMA);
                        $arr_error['ERROR_SGA'] = 1;

                        return $arr_error;
                    else:
                        $html->PagError("UbicacionAlmacenNoValida");
                    endif;
                endif;

            elseif ($rowMov->TIPO_MOVIMIENTO == 'Construccion')://COMPROBAMOS QUE EL ALMACEN DESTINO PERTENECE AL CENTRO FISICO, SI ES SUMINISTRO DIRECTO PUEDE IR A CUALQUIER ALMACEN DE CONSOLIDACION

                //BUSCO EL PEDIDO LINEA
                $rowPedidoLinea = $bd->VerReg("PEDIDO_ENTRADA_LINEA", "ID_PEDIDO_ENTRADA_LINEA", $rowLinea->ID_PEDIDO_LINEA);

                //SI EL TRANSPORTE ASOCIADO TIENE SUMINISTRO DIRECTO PUEDE QUE LA UBICACION FINAL ESTE EN UNA INSTALACION
                if ($rowOrdenTransporte->LUGAR_APROVISIONAMIENTO_CONSTRUCCION == "Suministro Directo"):

                    if ($rowPedidoLinea->ID_ALMACEN != $rowUbiDestinoMaterial->ID_ALMACEN): //SI NO VA A CONSOLIDACION, COMPROBAMOS QUE VA A UNA INSTALACION DEL CF
                        //BUSCAMOS EL ALMACEN DESTINO
                        $rowAlmacenDestinoMaterial = $bd->VerReg("ALMACEN", "ID_ALMACEN", $rowUbiDestinoMaterial->ID_ALMACEN);
                        $rowAlmacenDestinoPedido   = $bd->VerReg("ALMACEN", "ID_ALMACEN", $rowPedidoLinea->ID_ALMACEN);

                        //COMPROBAMOS QUE PERTENECEN AL MISMO CF
                        if ($rowAlmacenDestinoPedido->ID_CENTRO_FISICO != $rowAlmacenDestinoMaterial->ID_CENTRO_FISICO):
                            if ($mostrar_error == 'texto'):
                                $arr_error['ERROR']     = $auxiliar->traduce("La ubicación seleccionada no pertenece al almacén esperado", $administrador->ID_IDIOMA);
                                $arr_error['ERROR_SGA'] = 1;

                                return $arr_error;
                            else:
                                $html->PagError("UbicacionAlmacenNoValida");
                            endif;
                        endif;

                        //BUSCAMOS UBICACION PARA DEJAR EL MATERIAL
                        $GLOBALS["NotificaErrorPorEmail"] = "No";
                        $rowUbiConsolidacion              = $bd->VerRegRest("UBICACION", "ID_ALMACEN = $rowAlmacenDestinoPedido->ID_ALMACEN AND UBICACION = '" . $rowAlmacenDestinoPedido->REFERENCIA . "' AND TIPO_UBICACION IS NULL", "No");
                        if ($rowUbiConsolidacion == false):
                            if ($mostrar_error == 'texto'):
                                $arr_error['ERROR']     = $auxiliar->traduce("No hay definida una ubicación de tipo entrada", $administrador->ID_IDIOMA);
                                $arr_error['ERROR_SGA'] = 1;

                                return $arr_error;
                            else:
                                $html->PagError("UbicacionTipoEntradaNoDefinida");
                            endif;
                        endif;

                        //ACTUALIZAMOS LA UBICACION PARA DEJAR EL MATERIAL EN CONSOLIDACION Y POSTERIORMENTE TRASPASARLO A LA UBICACION FINAL
                        $rowLinea->ID_UBICACION = $rowUbiConsolidacion->ID_UBICACION;
                    endif;
                else:
                    //COMPROBAMOS QUE VA AL ALMACEN DE CONSOLIDACION
                    if ($rowPedidoLinea->ID_ALMACEN != $rowUbiDestinoMaterial->ID_ALMACEN):
                        if ($mostrar_error == 'texto'):
                            $arr_error['ERROR']     = $auxiliar->traduce("La ubicación seleccionada no pertenece al almacén esperado", $administrador->ID_IDIOMA);
                            $arr_error['ERROR_SGA'] = 1;

                            return $arr_error;
                        else:
                            $html->PagError("UbicacionAlmacenNoValida");
                        endif;
                    endif;
                endif;
            endif;

            //OBTENEMOS LA INFORMACIÓN DEL ALMACÉN DE DESTINO
            $NotificaErrorPorEmail = "No";
            $rowAlmacen            = $bd->VerReg("ALMACEN", "ID_ALMACEN", $rowPedidoLinea->ID_ALMACEN, "No");

            //AHORA OBTENEMOS EL CENTRO
            $rowCentro = $bd->VerReg("CENTRO", "ID_CENTRO", $rowAlmacen->ID_CENTRO);

            //OBTENEMOS EL TIPO DE BLOQUEO
            $rowTipoBloqueo = false;
            if ($rowLinea->ID_TIPO_BLOQUEO != NULL):
                $NotificaErrorPorEmail = "No";
                $rowTipoBloqueo        = $bd->VerReg("TIPO_BLOQUEO", "ID_TIPO_BLOQUEO", $rowLinea->ID_TIPO_BLOQUEO, "No");
            endif;

            //ANTES DE INCREMENTAR STOCK EN EL ALMACÉN DE DESTINO, SI SE TRATA DE UN PEDIDO DE COMPRA COMPROBAMOS SI LA CONFIGURACIÓN PROVEEDOR-CENTRO-MATERIAL ES DE STOCK EXTERNALIZAOD
            if ($rowPed->TIPO_PEDIDO == "Compra"):

                //DECREMENTAMOS STOCK EN EL ALMACÉN DE STOCK EXTERNALIZADO SI EL MATERIAL ES OK O DE PREVENTIVO (P)
                $arrErrores = $this->decrementarStockExternalizado($rowPed->ID_PROVEEDOR, $rowCentro->ID_CENTRO, $rowLinea->ID_MATERIAL, $rowLinea->ID_TIPO_BLOQUEO, $rowLinea->CANTIDAD);

                if (count( (array)$arrErrores) > 0):

                    //SI HA HABIDO ERRORES, LOS MUESTRO
                    $errores    = "";
                    $saltoLinea = "";
                    foreach ($arrErrores as $error):

                        $errores    .= $saltoLinea . $error;
                        $saltoLinea = "<br>";

                    endforeach;

                    $html->PagError($errores);

                endif;

            elseif ($rowPed->TIPO_PEDIDO == "Reparación"):

                //GESTIONAMOS LA RECEPCIÓN DE MATERIAL EN ALMACENES DE STOCK EXTERNALIZADO DE REPARACIÓN SI EL MATERIAL ES 'R' O 'XR'
                $arrErrores = $this->recepcionStockExternalizadoReparacion($rowPed->ID_PROVEEDOR, $rowCentro->ID_CENTRO, $rowLinea->ID_MATERIAL, $rowLinea->ID_MATERIAL_FISICO);

                if (count( (array)$arrErrores) > 0):

                    //SI HA HABIDO ERRORES, LOS MUESTRO
                    $errores    = "";
                    $saltoLinea = "";
                    foreach ($arrErrores as $error):

                        $errores    .= $saltoLinea . $error;
                        $saltoLinea = "<br>";

                    endforeach;

                    $html->PagError($errores);

                endif;

            endif;

            //COMPRUEBO SI EXISTE MATERIAL UBICACION
            if (!$ubicacion->Existe_Registro_Ubicacion_Material($rowLinea->ID_UBICACION, $rowLinea->ID_MATERIAL, $rowLinea->ID_MATERIAL_FISICO, $rowLinea->ID_TIPO_BLOQUEO, NULL, NULL)):
                //CREO MATERIAL UBICACION SI NO EXISTE
                $sql = "INSERT INTO MATERIAL_UBICACION SET
                    ID_MATERIAL = $rowLinea->ID_MATERIAL
                    , ID_MATERIAL_FISICO = " . ($rowLinea->ID_MATERIAL_FISICO != NULL ? "$rowLinea->ID_MATERIAL_FISICO" : "NULL") . "
                    , ID_UBICACION = $rowLinea->ID_UBICACION
                    , ID_TIPO_BLOQUEO = " . ($rowLinea->ID_TIPO_BLOQUEO == NULL ? 'NULL' : "$rowLinea->ID_TIPO_BLOQUEO") . "
                    , ID_ORDEN_TRABAJO_MOVIMIENTO = NULL
                    , ID_INCIDENCIA_CALIDAD = NULL";
                $bd->ExecSQL($sql);
            endif;

            //INCREMENTO MATERIAL UBICACION (SI ES DE CONSTRUCCION EVITAMOS QUE ENTRE EN EL EL PROCESO DE INTEGRIDAD DE MATERIAL FISICO, EN MATERIAL UBICACION SI DEBERIA ENTRAR)
            $sql = "UPDATE MATERIAL_UBICACION SET
                    STOCK_TOTAL = STOCK_TOTAL + $rowLinea->CANTIDAD
                    , STOCK_OK = STOCK_OK + " . ($rowLinea->ID_TIPO_BLOQUEO == NULL ? $rowLinea->CANTIDAD : 0) . "
                    , STOCK_BLOQUEADO = STOCK_BLOQUEADO + " . ($rowLinea->ID_TIPO_BLOQUEO == NULL ? 0 : $rowLinea->CANTIDAD)
                . ($rowMov->TIPO_MOVIMIENTO == 'Construccion' ? ", PENDIENTE_REVISAR_MATERIAL_FISICO_UBICACION = 2, PENDIENTE_REVISAR_MATERIAL_UBICACION_TIPO_BLOQUEO = 1" : "") . "
                    WHERE ID_MATERIAL = $rowLinea->ID_MATERIAL AND ID_UBICACION = $rowLinea->ID_UBICACION AND ID_MATERIAL_FISICO " . ($rowLinea->ID_MATERIAL_FISICO == NULL ? 'IS NULL' : "= $rowLinea->ID_MATERIAL_FISICO") . " AND ID_TIPO_BLOQUEO " . ($rowLinea->ID_TIPO_BLOQUEO == NULL ? 'IS NULL' : "= $rowLinea->ID_TIPO_BLOQUEO") . " AND ID_ORDEN_TRABAJO_MOVIMIENTO IS NULL AND ID_INCIDENCIA_CALIDAD IS NULL";
            $bd->ExecSQL($sql);

            //SI ES SERIABLE, ACTUALIZO EL NUMERO DE VECES REPARADO
            if (($rowMov->TIPO_MOVIMIENTO == 'PedidoProveedor') && ($rowPed->TIPO_PEDIDO == "Reparación") && ($rowLinea->TIPO_LOTE == 'serie')): //MATERIAL SERIABLE REPARACION
                $sqlUpdate = "UPDATE MATERIAL_FISICO SET
                                NUMERO_REPARACIONES = NUMERO_REPARACIONES + 1
                                WHERE ID_MATERIAL_FISICO = $rowLinea->ID_MATERIAL_FISICO";
                $bd->ExecSQL($sqlUpdate);
            endif;

            //COMPRUEBO SI ALGUNA LINEA TIENE MARCADO EL INDICADOR DE ENTREGA FINAL
            if (($rowLinea->ENTREGA_FINAL_PEDIDO == 1) && ($rowMov->TIPO_MOVIMIENTO == 'PedidoProveedor')):
                //COMPRUEBO QUE EL PEDIDO SEA DE RECEPCION
                if ($rowPed->TIPO_PEDIDO != "Compra"):
                    if ($mostrar_error == 'texto'):
                        $arr_error['ERROR']     = $auxiliar->traduce("Las líneas de pedidos de reparación/garantía no pueden tener marcadas el check de entrega final", $administrador->ID_IDIOMA);
                        $arr_error['ERROR_SGA'] = 1;

                        return $arr_error;
                    else:
                        $html->PagError("PedidoReparacionGarantia");
                    endif;
                endif;

                //SI NO HAY LINEAS DE ESE PEDIDO CON CANTIDAD PDTE DE SERVIR PASO EL PEDIDO A ESTADO ENTREGADO
                $num = $bd->NumRegsTabla("PEDIDO_ENTRADA_LINEA", "ID_PEDIDO_ENTRADA = $rowLinea->ID_PEDIDO AND CANTIDAD_PDTE <> 0 AND INDICADOR_BORRADO IS NULL", "No");
                if ($num == 0):
                    $sqlUpdate = "UPDATE PEDIDO_ENTRADA SET ESTADO = 'Entregado' WHERE ID_PEDIDO_ENTRADA = $rowPedLin->ID_PEDIDO_ENTRADA";
                    $bd->ExecSQL($sqlUpdate);
                endif;
            endif;

            //ACCIONES SI EL PEDIDO NO ES DE RECEPCION Y PROVENIENTE DE PROVEEDOR. LOGISTICA INVERSA
            if (($rowPed->TIPO_PEDIDO != "Compra") && ($rowPed->TIPO_PEDIDO != "Compra SGA Manual") && ($rowMov->TIPO_MOVIMIENTO == 'PedidoProveedor')): //PEDIDO DE REPARACION O GARANTIA
                //COMPRUEBO QUE NO HAYA COMPONENTES EN PREPARACION O PENDIENTES DE EXPEDIR PARA ESTA LINEA
                $sqlComponentesPreparandose    = "SELECT COUNT(*) AS NUM
                                                     FROM MOVIMIENTO_SALIDA_LINEA MSL
                                                     INNER JOIN PEDIDO_SALIDA_LINEA PSL ON PSL.ID_PEDIDO_SALIDA_LINEA = MSL.ID_PEDIDO_SALIDA_LINEA
                                                     INNER JOIN PEDIDO_SALIDA PS ON PS.ID_PEDIDO_SALIDA = PSL.ID_PEDIDO_SALIDA
                                                     WHERE MSL.ESTADO IN ('Reservado para Preparacion', 'En Preparacion', 'Pendiente de Expedir', 'Transmitido a SAP') AND PS.PEDIDO_SAP = '" . $rowPed->PEDIDO_SAP . "' AND PSL.ID_LINEA_ZREP_ZGAR = $rowPedLin->ID_PEDIDO_ENTRADA_LINEA AND MSL.LINEA_ANULADA = 0 AND MSL.BAJA = 0";
                $resultComponentesPreparandose = $bd->ExecSQL($sqlComponentesPreparandose);
                if (($resultComponentesPreparandose != false) && ($bd->NumRegs($resultComponentesPreparandose) == 1)):
                    $rowComponentesPreparandose = $bd->SigReg($resultComponentesPreparandose);
                    $numComponentesPreparandose = $rowComponentesPreparandose->NUM;

                    if ($numComponentesPreparandose > 0):
                        if ($mostrar_error == 'texto'):
                            $arr_error['ERROR']     = $auxiliar->traduce("No se puede recepcionar un material reparado si se están preparando componentes para repararlo", $administrador->ID_IDIOMA);
                            $arr_error['ERROR_SGA'] = 1;

                            return $arr_error;
                        else:
                            $html->PagError("RecepcionMaterialComponentesPreparandose");
                        endif;
                    endif;
                endif;

                //BUSCO LA LINEA DEL PEDIDO DE TRASLADO FICTICIO
                $sqlPedidoSalidaLinea    = "SELECT *
                                            FROM PEDIDO_SALIDA_LINEA PSL
                                            INNER JOIN PEDIDO_SALIDA PS ON PS.ID_PEDIDO_SALIDA = PSL.ID_PEDIDO_SALIDA
                                            WHERE PSL.LINEA_PEDIDO_SAP = '" . $rowPedLin->LINEA_PEDIDO_SAP . "' AND PS.PEDIDO_SAP = '" . $rowPed->PEDIDO_SAP . "' AND ID_LINEA_ZREP_ZGAR IS NULL";
                $resultPedidoSalidaLinea = $bd->ExecSQL($sqlPedidoSalidaLinea);
                if ($bd->NumRegs($resultPedidoSalidaLinea) != 1):
                    if ($mostrar_error == 'texto'):
                        $arr_error['ERROR']     = $auxiliar->traduce("No se ha encontrado la Linea del traslado Ficticio", $administrador->ID_IDIOMA);
                        $arr_error['ERROR_SGA'] = 1;

                        return $arr_error;
                    else:
                        $html->PagError("ErrorLineaPedidoTraslado");
                    endif;
                endif;

                $rowLineaPedidoTraslado = $bd->SigReg($resultPedidoSalidaLinea);

                //BUSCO EL MOVIENTO SALIDA LINEA
                $rowMovSalDirecto = $bd->VerReg("MOVIMIENTO_SALIDA_LINEA", "ID_MOVIMIENTO_SALIDA_LINEA", $rowLineaPedidoTraslado->ID_MOVIMIENTO_SALIDA_LINEA);
                if ($rowMovSalDirecto == false):
                    if ($mostrar_error == 'texto'):
                        $arr_error['ERROR']     = $auxiliar->traduce("El movimiento de salida directo no existe", $administrador->ID_IDIOMA);
                        $arr_error['ERROR_SGA'] = 1;

                        return $arr_error;
                    else:
                        $html->PagError("MovimientoSalidaDirectoNoExiste");
                    endif;
                endif;

                //BUSCO EL MATERIAL UBICACION DEL PROVEEDOR
                $rowMatUbi = $bd->VerRegRest("MATERIAL_UBICACION", "ID_MATERIAL = $rowMovSalDirecto->ID_MATERIAL AND ID_MATERIAL_FISICO " . ($rowMovSalDirecto->ID_MATERIAL_FISICO == NULL ? 'IS NULL' : "= $rowMovSalDirecto->ID_MATERIAL_FISICO") . " AND ID_UBICACION = $rowMovSalDirecto->ID_UBICACION_DESTINO AND ID_TIPO_BLOQUEO = $rowMovSalDirecto->ID_TIPO_BLOQUEO AND ID_ORDEN_TRABAJO_MOVIMIENTO = $rowMovSalDirecto->ID_ORDEN_TRABAJO_MOVIMIENTO AND ID_INCIDENCIA_CALIDAD " . ($rowMovSalDirecto->ID_INCIDENCIA_CALIDAD == NULL ? 'IS NULL' : "= $rowMovSalDirecto->ID_INCIDENCIA_CALIDAD"), "No");
                $strError  = "Material " . $rowMat->REFERENCIA_SGA . " - " . $rowMat->DESCRIPCION . ", Ubicación: " . $rowUbiDestinoMaterial->UBICACION;
                if ($rowMatUbi == false):
                    if ($mostrar_error == 'texto'):
                        $arr_error['ERROR']     = $auxiliar->traduce("No existe el material a recepcionar en el proveedor", $administrador->ID_IDIOMA) . ".<br /><br />$strError";
                        $arr_error['ERROR_SGA'] = 1;

                        return $arr_error;
                    else:
                        $html->PagError("MaterialUbicacionProveedorNoExiste");
                    endif;
                endif;

                //COMPRUEBO QUE HAYA STOCK SUFICIENTE EN PROVEEDOR
                if ($rowMatUbi->STOCK_TOTAL < $rowLinea->CANTIDAD):
                    $strError = $auxiliar->traduce("Material", $administrador->ID_IDIOMA) . " " . $rowMat->REFERENCIA_SGA . " - " . ($administrador->ID_IDIOMA == 'ESP' ? $rowMat->DESCRIPCION : $rowMat->DESCRIPCION_EN) . "; ";
                    if ($rowMovSalDirecto->ID_MATERIAL_FISICO != NULL):
                        $rowMaterialFisicoProveedor = $bd->VerReg("MATERIAL_FISICO", "ID_MATERIAL_FISICO", $rowMovSalDirecto->ID_MATERIAL_FISICO);
                        $strError                   = $strError . $auxiliar->traduce("Numero de", $administrador->ID_IDIOMA) . " " . $auxiliar->traduce($rowMaterialFisicoProveedor->TIPO_LOTE, $administrador->ID_IDIOMA) . " " . $rowMaterialFisicoProveedor->NUMERO_SERIE_LOTE . "; ";
                    endif;
                    $strError = $strError . $auxiliar->traduce("Cantidad en proveedor", $administrador->ID_IDIOMA) . ": " . $rowMatUbi->STOCK_TOTAL;
                    if ($mostrar_error == 'texto'):
                        $arr_error['ERROR']     = $auxiliar->traduce("Stock insuficiente en proveedor", $administrador->ID_IDIOMA) . ": " . "<br><br>" . $strError;
                        $arr_error['ERROR_SGA'] = 1;

                        return $arr_error;
                    else:
                        $html->PagError("StockInsuficienteEnProveedorDetalle");
                    endif;
                endif;

                //DESCUENTO EL MATERIAL EN PROVEEDOR
                $sqlUpdate = "UPDATE MATERIAL_UBICACION SET
                                STOCK_TOTAL = STOCK_TOTAL - $rowLinea->CANTIDAD
                                , STOCK_BLOQUEADO = STOCK_BLOQUEADO - $rowLinea->CANTIDAD
                                WHERE ID_MATERIAL = $rowMovSalDirecto->ID_MATERIAL AND ID_MATERIAL_FISICO " . ($rowMovSalDirecto->ID_MATERIAL_FISICO == NULL ? 'IS NULL' : "= $rowMovSalDirecto->ID_MATERIAL_FISICO") . " AND ID_UBICACION = $rowMovSalDirecto->ID_UBICACION_DESTINO AND ID_TIPO_BLOQUEO = $rowMovSalDirecto->ID_TIPO_BLOQUEO AND ID_ORDEN_TRABAJO_MOVIMIENTO = $rowMovSalDirecto->ID_ORDEN_TRABAJO_MOVIMIENTO AND ID_INCIDENCIA_CALIDAD " . ($rowMovSalDirecto->ID_INCIDENCIA_CALIDAD == NULL ? 'IS NULL' : "= $rowMovSalDirecto->ID_INCIDENCIA_CALIDAD");
                $bd->ExecSQL($sqlUpdate);

                //GENERO EL ASIENTO NEGATIVO EN PROVEEDOR
                $sqlInsert = "INSERT INTO ASIENTO SET
                                ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                , ID_MATERIAL = $rowMovSalDirecto->ID_MATERIAL
                                , TIPO_LOTE = '" . ($rowMovSalDirecto->ID_MATERIAL_FISICO == NULL ? 'ninguno' : "$rowMatFis->TIPO_LOTE") . "'
                                , ID_MATERIAL_FISICO = " . ($rowMovSalDirecto->ID_MATERIAL_FISICO == NULL ? 'NULL' : "$rowMovSalDirecto->ID_MATERIAL_FISICO") . "
                                , ID_UBICACION = $rowMovSalDirecto->ID_UBICACION_DESTINO
                                , FECHA = '" . date("Y-m-d H:i:s") . "'
                                , CANTIDAD = " . ($rowLinea->CANTIDAD * -1) . "
                                , STOCK_OK = " . ($rowLinea->ID_TIPO_BLOQUEO == NULL ? ($rowLinea->CANTIDAD * -1) : 0) . "
                                , STOCK_BLOQUEADO = " . ($rowLinea->ID_TIPO_BLOQUEO == NULL ? 0 : ($rowLinea->CANTIDAD * -1)) . "
                                , ID_TIPO_BLOQUEO = " . ($rowLinea->ID_TIPO_BLOQUEO == NULL ? 'NULL' : $rowLinea->ID_TIPO_BLOQUEO) . "
                                , ID_ORDEN_TRABAJO_MOVIMIENTO = " . ($rowLinea->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? 'NULL' : $rowLinea->ID_ORDEN_TRABAJO_MOVIMIENTO) . "
                                , ID_INCIDENCIA_CALIDAD = " . ($rowLinea->ID_INCIDENCIA_CALIDAD == NULL ? 'NULL' : $rowLinea->ID_INCIDENCIA_CALIDAD) . "
                                , OBSERVACIONES = ''
                                , TIPO_ASIENTO = 'Baja Material Estropeado en Proveedor'
                                , ID_MOVIMIENTO_ENTRADA_LINEA = $rowLinea->ID_MOVIMIENTO_ENTRADA_LINEA";
                $bd->ExecSQL($sqlInsert);

                //SI ES SERIABLE Y NO VUELVE EL ENVIADO COMPRUEBO QUE NO SE HAYAN CRUZADOS LOS NUMERO DE SERIE
                if (($rowMovSalDirecto->ID_MATERIAL_FISICO != NULL) && ($rowMatFis->TIPO_LOTE == 'serie') && ($rowMovSalDirecto->ID_MATERIAL_FISICO != $rowLinea->ID_MATERIAL_FISICO)):
                    $numSeriables = $bd->NumRegsTabla("MATERIAL_UBICACION", "ID_MATERIAL_FISICO = $rowLinea->ID_MATERIAL_FISICO AND ID_MATERIAL = $rowMovSalDirecto->ID_MATERIAL AND STOCK_TOTAL > 0");
                    if ($numSeriables > 1):
                        $strError = "Número de serie: " . $rowMatFis->NUMERO_SERIE_LOTE;
                        if ($mostrar_error == 'texto'):
                            $arr_error['ERROR']     = $auxiliar->traduce("El número de serie que se está intentando recepcionar no coincide con el enviado", $administrador->ID_IDIOMA) . ":<br /><br />$strError";
                            $arr_error['ERROR_SGA'] = 1;

                            return $arr_error;
                        else:
                            $html->PagError("ErrorMaterialSeriableEnviado");
                        endif;
                    endif;
                endif;

                //BUSCO EL NUMERO DE MOVIMIENTOS PROCESADOS CON EL MISMO NUMERO DE LINEA DE PEDIDO
                $num = $bd->NumRegsTabla("MOVIMIENTO_ENTRADA_LINEA MEL INNER JOIN MOVIMIENTO_ENTRADA ME ON ME.ID_MOVIMIENTO_ENTRADA = MEL.ID_MOVIMIENTO_ENTRADA", "ME.ESTADO <> 'En Proceso' AND MEL.ID_PEDIDO_LINEA = $rowPedLin->ID_PEDIDO_ENTRADA_LINEA AND MEL.ID_MOVIMIENTO_ENTRADA_LINEA <> $rowLinea->ID_MOVIMIENTO_ENTRADA_LINEA AND MEL.BAJA = 0 AND MEL.LINEA_ANULADA = 0 AND MEL.CANTIDAD > 0"); //exit($num);
                if ($num == 0):
                    //ES LA PRIMERA VEZ QUE RECEPCIONO MATERIAL DE ESTA LINEA DE PEDIDO, DOY DE BAJA SUS COMPONENTES

                    //BUSCO LOS MOVIMIENTOS DE SALIDA DE LA LINEA DEL PEDIDO
                    $sqlMovSalLineas    = "SELECT SUM(MSL.CANTIDAD) AS CANTIDAD, MSL.ID_MATERIAL, ID_MATERIAL_FISICO, ID_UBICACION_DESTINO
                                            FROM MOVIMIENTO_SALIDA_LINEA MSL
                                            INNER JOIN MOVIMIENTO_SALIDA MS ON MS.ID_MOVIMIENTO_SALIDA = MSL.ID_MOVIMIENTO_SALIDA
                                            INNER JOIN PEDIDO_SALIDA_LINEA PSL ON PSL.ID_PEDIDO_SALIDA_LINEA = MSL.ID_PEDIDO_SALIDA_LINEA
                                            WHERE PSL.ID_LINEA_ZREP_ZGAR = $rowPedLin->ID_PEDIDO_ENTRADA_LINEA AND MSL.ESTADO = 'Expedido' AND MSL.LINEA_ANULADA = 0 AND MSL.BAJA = 0
                                            GROUP BY MSL.ID_MATERIAL, MSL.ID_MATERIAL_FISICO, PSL.ID_LINEA_ZREP_ZGAR";
                    $resultMovSalLineas = $bd->ExecSQL($sqlMovSalLineas);
                    while ($rowMovSalLinea = $bd->SigReg($resultMovSalLineas)):
                        //BUSCO EL ALMACEN DEL PROVEEDOR
                        $rowAlm = $bd->VerReg("ALMACEN", "ID_PROVEEDOR", $rowPed->ID_PROVEEDOR, "No");
                        if ($rowAlm == false):
                            if ($mostrar_error == 'texto'):
                                $arr_error['ERROR']     = $auxiliar->traduce("El almacén del proveedor no existe", $administrador->ID_IDIOMA);
                                $arr_error['ERROR_SGA'] = 1;

                                return $arr_error;
                            else:
                                $html->PagError("AlmacenProveedorNoExiste");
                            endif;
                        endif;

                        //BUSCO LA UBICACION DE PROVEEDOR
                        $rowUbi = $bd->VerReg("UBICACION", "ID_ALMACEN", $rowAlm->ID_ALMACEN, "No");
                        if ($rowUbi == false):
                            if ($mostrar_error == 'texto'):
                                $arr_error['ERROR']     = $auxiliar->traduce("La ubicacion del proveedor no existe", $administrador->ID_IDIOMA);
                                $arr_error['ERROR_SGA'] = 1;

                                return $arr_error;
                            else:
                                $html->PagError("UbicacionProveedorNoExiste");
                            endif;
                        endif;

                        //BUSCO MATERIAL UBICACION EN PROVEEDOR
                        $rowMatUbiComponentes = $bd->VerRegRest("MATERIAL_UBICACION", "ID_MATERIAL = $rowMovSalLinea->ID_MATERIAL AND ID_UBICACION = $rowUbi->ID_UBICACION AND ID_MATERIAL_FISICO " . ($rowMovSalLinea->ID_MATERIAL_FISICO == NULL ? 'IS NULL' : "= $rowMovSalLinea->ID_MATERIAL_FISICO") . " AND ID_TIPO_BLOQUEO IS NULL AND ID_ORDEN_TRABAJO_MOVIMIENTO IS NULL AND ID_INCIDENCIA_CALIDAD IS NULL", "No");
                        if ($rowMatUbi == false):
                            if ($mostrar_error == 'texto'):
                                $arr_error['ERROR']     = $auxiliar->traduce("No existe el material a recepcionar en el proveedor", $administrador->ID_IDIOMA) . ".<br /><br />$strError";
                                $arr_error['ERROR_SGA'] = 1;

                                return $arr_error;
                            else:
                                $html->PagError("MaterialUbicacionProveedorNoExiste");
                            endif;
                        endif;

                        //COMPRUEBO QUE HAYA STOCK SUFICIENTE DE LOS COMPONENTES EN PROVEEDOR
                        if ($rowMatUbiComponentes->STOCK_TOTAL < $rowMovSalLinea->CANTIDAD):
                            if ($mostrar_error == 'texto'):
                                $arr_error['ERROR']     = $auxiliar->traduce("No existe el material a recepcionar en el proveedor", $administrador->ID_IDIOMA) . ".<br /><br />$strError";
                                $arr_error['ERROR_SGA'] = 1;

                                return $arr_error;
                            else:
                                $html->PagError("StockComponentesInsuficienteEnProveedor");
                            endif;
                        endif;

                        //DESCUENTO LOS COMPONENTES DEL MATERIAL EN PROVEEDOR
                        $sqlUpdate = "UPDATE MATERIAL_UBICACION SET
                                        STOCK_TOTAL = STOCK_TOTAL - $rowMovSalLinea->CANTIDAD
                                        , STOCK_OK = STOCK_OK - $rowMovSalLinea->CANTIDAD
                                        WHERE ID_MATERIAL = $rowMovSalLinea->ID_MATERIAL AND ID_MATERIAL_FISICO " . ($rowMovSalLinea->ID_MATERIAL_FISICO == NULL ? 'IS NULL' : "= $rowMovSalLinea->ID_MATERIAL_FISICO") . " AND ID_UBICACION = $rowMovSalLinea->ID_UBICACION_DESTINO AND ID_TIPO_BLOQUEO IS NULL AND ID_ORDEN_TRABAJO_MOVIMIENTO IS NULL AND ID_INCIDENCIA_CALIDAD IS NULL";
                        $bd->ExecSQL($sqlUpdate);

                        //BUSCO EL MATERIAL FISICO DEL COMPONENTE
                        $GLOBALS["NotificaErrorPorEmail"] = "No";
                        $rowMatFisComponente              = $bd->VerReg("MATERIAL_FISICO", "ID_MATERIAL_FISICO", $rowMovSalLinea->ID_MATERIAL_FISICO, "No");
                        unset($GLOBALS["NotificaErrorPorEmail"]);

                        //GENERO EL ASIENTO NEGATIVO EN PROVEEDOR
                        $sqlInsert = "INSERT INTO ASIENTO SET
                                        ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                        , ID_MATERIAL = $rowMovSalLinea->ID_MATERIAL
                                        , TIPO_LOTE = '" . ($rowMovSalLinea->ID_MATERIAL_FISICO == NULL ? 'ninguno' : "$rowMatFisComponente->TIPO_LOTE") . "'
                                        , ID_MATERIAL_FISICO = " . ($rowMovSalLinea->ID_MATERIAL_FISICO == NULL ? 'NULL' : "$rowMovSalLinea->ID_MATERIAL_FISICO") . "
                                        , ID_UBICACION = $rowMovSalLinea->ID_UBICACION_DESTINO
                                        , FECHA = '" . date("Y-m-d H:i:s") . "'
                                        , CANTIDAD = " . ($rowMovSalLinea->CANTIDAD * -1) . "
                                        , STOCK_OK = " . ($rowMovSalLinea->CANTIDAD * -1) . "
                                        , STOCK_BLOQUEADO = 0
                                        , ID_TIPO_BLOQUEO = NULL
                                        , OBSERVACIONES = ''
                                        , TIPO_ASIENTO = 'Baja Componentes en Proveedor'
                                        , ID_MOVIMIENTO_ENTRADA_LINEA = $rowLinea->ID_MOVIMIENTO_ENTRADA_LINEA";
                        $bd->ExecSQL($sqlInsert);
                    endwhile;

                endif;
                //FIN BUSCO EL NUMERO DE MOVIMIENTOS PROCESADOS CON EL MISMO NUMERO DE LINEA DE PEDIDO

                //BUSCO SI QUEDAN COMPONENTES DE ENVIAR A PROVEEDOR DE ESTA LINEA, SI ES ASI, LOS MARCARE COMO ENTREGA FINAL
                $sqlComponentesSinPreparar    = "SELECT PSL.ID_PEDIDO_SALIDA_LINEA, PSL.ID_PEDIDO_SALIDA, PSL.CANTIDAD, PSL.CANTIDAD_PENDIENTE_SERVIR
                                                    FROM PEDIDO_SALIDA_LINEA PSL
                                                    INNER JOIN PEDIDO_SALIDA PS ON PS.ID_PEDIDO_SALIDA = PSL.ID_PEDIDO_SALIDA
                                                    WHERE PS.PEDIDO_SAP = '" . $rowPed->PEDIDO_SAP . "' AND PSL.ID_LINEA_ZREP_ZGAR = $rowPedLin->ID_PEDIDO_ENTRADA_LINEA AND PSL.CANTIDAD_PENDIENTE_SERVIR > 0 AND PSL.INDICADOR_BORRADO IS NULL AND PSL.BAJA = 0";
                $resultComponentesSinPreparar = $bd->ExecSQL($sqlComponentesSinPreparar);
                while ($rowComponentesSinPreparar = $bd->SigReg($resultComponentesSinPreparar)):
                    $sqlUpdate = "UPDATE PEDIDO_SALIDA_LINEA SET
                                    CANTIDAD_CANCELADA_POR_ENTREGA_FINAL = CANTIDAD_PENDIENTE_SERVIR
                                    , CANTIDAD_PENDIENTE_SERVIR = 0
                                    , ENTREGA_FINAL = 1
                                    , ESTADO = '" . ($rowComponentesSinPreparar->CANTIDAD == $rowComponentesSinPreparar->CANTIDAD_PENDIENTE_SERVIR ? 'Finalizada' : 'En Entrega') . "'
                                    WHERE ID_PEDIDO_SALIDA_LINEA = $rowComponentesSinPreparar->ID_PEDIDO_SALIDA_LINEA";
                    $sqlUpdate = $bd->ExecSQL($sqlUpdate);

                    //SI EL PEDIDO DE SALIDA NO TIENE CANTIDAD PENDIENTE DE SERVIR LO ACTUALIZO A ESTADO FINALIZADO
                    $num                             = $bd->NumRegsTabla("PEDIDO_SALIDA_LINEA PSL", "PSL.CANTIDAD_PENDIENTE_SERVIR > 0 AND PSL.ID_PEDIDO_SALIDA = $rowComponentesSinPreparar->ID_PEDIDO_SALIDA AND PSL.INDICADOR_BORRADO IS NULL AND PSL.BAJA = 0");
                    $rowPedidoComponentesSinPreparar = $bd->VerReg("PEDIDO_SALIDA", "ID_PEDIDO_SALIDA", $rowComponentesSinPreparar->ID_PEDIDO_SALIDA);
                    if (($num == 0) && (($rowPedidoComponentesSinPreparar->ESTADO == 'En Entrega') || ($rowPedidoComponentesSinPreparar->ESTADO == 'Grabado'))):
                        $sqlUpdate = "UPDATE PEDIDO_SALIDA SET
                                        ESTADO = 'Finalizado' 
                                        WHERE ID_PEDIDO_SALIDA = $rowComponentesSinPreparar->ID_PEDIDO_SALIDA";
                        $bd->ExecSQL($sqlUpdate);
                    endif;

                    //BUSCAMOS LA DEMANDA NO ASOCIADA A LA LINEA DE PEDIDO
                    $rowDemandaPedido = $reserva->get_demanda("Pedido", $rowComponentesSinPreparar->ID_PEDIDO_SALIDA_LINEA);

                    //GUARDAMOS LA CANTIDAD DEL CAMBIO
                    $array_cambios['CANTIDAD'] = $rowComponentesSinPreparar->CANTIDAD_PENDIENTE_SERVIR * -1;

                    $arr_modificacion = $reserva->modificacion_demanda($rowDemandaPedido->ID_DEMANDA, $array_cambios);
                    if (isset($arr_modificacion['error']) && $arr_modificacion['error'] != "")://SI VIENE ERROR
                        if ($mostrar_error == 'texto'):
                            $arr_error['ERROR']     = $auxiliar->traduce("Error actualizando demanda pedido de componentes", $administrador->ID_IDIOMA);
                            $arr_error['ERROR_SGA'] = 1;

                            return $arr_error;
                        else:
                            $html->PagError("ErrorActualizarDemanda");
                        endif;
                    endif;
                endwhile;

                //BUSCAMOS EL MATERIAL
                $rowMaterial = $bd->VerReg("MATERIAL", "ID_MATERIAL", $rowMovSalDirecto->ID_MATERIAL, "No");

                //SI EL MSL TIENE GUARDADO UN ID_CAMBIO_REFERENCIA Y NO TIENE QUE PASAR CONTROL DE CALIDAD, MARCO COMO PDTE TRANSMITIR Y HAGO UPDATE A LA UBICACION
                if (
                    ($rowMovSalDirecto->ID_CAMBIO_REFERENCIA_GRUPO != "") &&
                    ($rowLinea->ID_TIPO_BLOQUEO != $rowBloqueoCCNP->ID_TIPO_BLOQUEO) &&
                    ($rowLinea->ID_TIPO_BLOQUEO != $rowBloqueoCCP->ID_TIPO_BLOQUEO)
                ):

                    //BUSCO LOS CAMBIOS DE REFERENCIA ASOCIADOS
                    $sqlCambiosReferencia    = "SELECT * FROM CAMBIO_REFERENCIA WHERE BAJA=0 AND ID_CAMBIO_REFERENCIA_GRUPO=$rowMovSalDirecto->ID_CAMBIO_REFERENCIA_GRUPO";
                    $resultCambiosReferencia = $bd->ExecSQL($sqlCambiosReferencia);

                    while ($rowCambioReferencia = $bd->SigReg($resultCambiosReferencia)):
                        //HAGO UPDATE EL CAMBIO_REFERENCIA
                        //BUSCO EL MATERIAL FISICO ORIGEN(NUEVO) Y DESTINO, SI SON DEL MISMO TIPO, DEBEN COINCIDIR
                        $idMaterialFisicoDestinoNuevo = "";
                        if ($rowLinea->ID_MATERIAL_FISICO != NULL):
                            $rowMatFisicoOrigenNuevo = $bd->VerReg("MATERIAL_FISICO", "ID_MATERIAL_FISICO", $rowLinea->ID_MATERIAL_FISICO, "No");
                            $rowMatFisicoDestino     = false;
                            if ($rowCambioReferencia->ID_MATERIAL_FISICO_DESTINO != NULL):
                                $rowMatFisicoDestino = $bd->VerReg("MATERIAL_FISICO", "ID_MATERIAL_FISICO", $rowCambioReferencia->ID_MATERIAL_FISICO_DESTINO, "No");
                            endif;                        //SI SON DISTINTOS, CREO EL NUEVO
                            if (($rowMatFisicoOrigenNuevo->TIPO_LOTE == $rowMatFisicoDestino->TIPO_LOTE) && ($rowMatFisicoOrigenNuevo->NUMERO_SERIE_LOTE != $rowMatFisicoDestino->NUMERO_SERIE_LOTE)):
                                $GLOBALS["NotificaErrorPorEmail"] = "No";
                                $rowMatFisicoDestinoNuevo         = $bd->VerRegRest("MATERIAL_FISICO", "ID_MATERIAL = $rowMatFisicoDestino->ID_MATERIAL AND TIPO_LOTE = '" . $rowMatFisicoDestino->TIPO_LOTE . "' AND NUMERO_SERIE_LOTE = '" . $rowMatFisicoOrigenNuevo->NUMERO_SERIE_LOTE . "'", "No");
                                if ($rowMatFisicoDestinoNuevo == false):
                                    $sqlInsert = "INSERT INTO MATERIAL_FISICO SET
                                                    ID_MATERIAL = $rowMatFisicoDestino->ID_MATERIAL
                                                    , TIPO_LOTE = '" . $rowMatFisicoDestino->TIPO_LOTE . "'
                                                    , NUMERO_SERIE_LOTE = '" . $rowMatFisicoOrigenNuevo->NUMERO_SERIE_LOTE . "'";
                                    $bd->ExecSQL($sqlInsert);
                                    $idMaterialFisicoDestinoNuevo = $bd->IdAsignado();
                                else:
                                    $idMaterialFisicoDestinoNuevo = $rowMatFisicoDestinoNuevo->ID_MATERIAL_FISICO;
                                endif;
                            endif;
                        endif;
                        $sqlUpdate = "UPDATE CAMBIO_REFERENCIA SET
                                        ID_UBICACION = $rowLinea->ID_UBICACION
                                        ,ID_MOVIMIENTO_ENTRADA_LINEA_ORIGINAL = " . ($rowLinea->ID_MOVIMIENTO_ENTRADA_LINEA != "" ? $rowLinea->ID_MOVIMIENTO_ENTRADA_LINEA : "NULL") . "
                                        ,ID_TIPO_BLOQUEO =" . ($rowLinea->ID_TIPO_BLOQUEO == NULL ? 'NULL' : "$rowLinea->ID_TIPO_BLOQUEO") . "
                                        ,ID_ORDEN_TRABAJO_MOVIMIENTO = NULL
                                        ,ID_INCIDENCIA_CALIDAD = NULL
                                        ,ID_MATERIAL_FISICO_ORIGEN = " . ($rowLinea->ID_MATERIAL_FISICO == NULL ? 'NULL' : "$rowLinea->ID_MATERIAL_FISICO") . "
                                        " . ($idMaterialFisicoDestinoNuevo != "" ? ",ID_MATERIAL_FISICO_DESTINO=$idMaterialFisicoDestinoNuevo" : "") . "
                                        ,FECHA = '" . date("Y-m-d H:i:s") . "'
                                        WHERE ID_CAMBIO_REFERENCIA = $rowCambioReferencia->ID_CAMBIO_REFERENCIA";
                        $sqlUpdate = $bd->ExecSQL($sqlUpdate);

                        $arrCambioReferencia[] = $rowCambioReferencia->ID_CAMBIO_REFERENCIA;
                    endwhile;

                    //HAGO UPDATE EL CAMBIO_REFERENCIA_GRUPO
                    $sqlUpdate = "UPDATE CAMBIO_REFERENCIA_GRUPO SET
                                    ESTADO = 'Pdte Transmitir a SAP'
                                    WHERE ID_CAMBIO_REFERENCIA_GRUPO = $rowMovSalDirecto->ID_CAMBIO_REFERENCIA_GRUPO";
                    $bd->ExecSQL($sqlUpdate);

                elseif (($rowMaterial->ESTADO_BLOQUEO_MATERIAL == "03-Código duplicado") &&
                    ($rowLinea->ID_TIPO_BLOQUEO != $rowBloqueoCCNP->ID_TIPO_BLOQUEO) &&
                    ($rowLinea->ID_TIPO_BLOQUEO != $rowBloqueoCCP->ID_TIPO_BLOQUEO)
                )://SI ES CODIGO DUPLICADO Y NO PASA CONTROL CALIDAD
                    //BUSCAMOS SI TIENE SUSTITUTIVO
                    $idMaterialSustitutivo = $mat->obtenerMaterialSustitutivo($rowMaterial->ID_MATERIAL);

                    if ($idMaterialSustitutivo != ""):
                        //GENERAMOS EL CAMBIO REFERENCIA
                        $arrResultado = $mat->generarCambioReferenciaPendiente($rowMaterial->ID_MATERIAL, $idMaterialSustitutivo, $rowLinea->CANTIDAD, $rowLinea->ID_UBICACION, ($rowLinea->ID_MATERIAL_FISICO == NULL ? NULL : "$rowLinea->ID_MATERIAL_FISICO"), ($rowLinea->ID_TIPO_BLOQUEO == NULL ? 'NULL' : "$rowLinea->ID_TIPO_BLOQUEO"), NULL, NULL, $rowLinea->ID_MOVIMIENTO_ENTRADA_LINEA);
                        if ($arrResultado['OK'] == true):
                            $arrCambioReferencia = array_merge((array)$arrCambioReferencia, $arrResultado['CambiosReferencia']);
                        endif;
                    endif;
                endif;

            elseif ($rowMov->TIPO_MOVIMIENTO == 'PedidoProveedor'): //PEDIDOS DE PROVEEDOR DE COMPRA Y COMPRA SGA

                //CODIGO DUPLICADO SI TIENE SUSTITUTIVO HACEMOS CAMBIO REFERENCIA
                if (($rowMat->ESTADO_BLOQUEO_MATERIAL == "03-Código duplicado") &&
                    ($rowLinea->ID_TIPO_BLOQUEO != $rowBloqueoCCNP->ID_TIPO_BLOQUEO) &&
                    ($rowLinea->ID_TIPO_BLOQUEO != $rowBloqueoCCP->ID_TIPO_BLOQUEO)
                )://SI ES CODIGO DUPLICADO Y NO PASA CONTROL CALIDAD
                    //BUSCAMOS SI TIENE SUSTITUTIVO
                    $idMaterialSustitutivo = $mat->obtenerMaterialSustitutivo($rowMat->ID_MATERIAL);

                    if ($idMaterialSustitutivo != ""):
                        //GENERAMOS EL CAMBIO REFERENCIA
                        $arrResultado = $mat->generarCambioReferenciaPendiente($rowMat->ID_MATERIAL, $idMaterialSustitutivo, $rowLinea->CANTIDAD, $rowLinea->ID_UBICACION, ($rowLinea->ID_MATERIAL_FISICO == NULL ? NULL : "$rowLinea->ID_MATERIAL_FISICO"), ($rowLinea->ID_TIPO_BLOQUEO == NULL ? 'NULL' : "$rowLinea->ID_TIPO_BLOQUEO"), NULL, NULL, $rowLinea->ID_MOVIMIENTO_ENTRADA_LINEA);
                        if ($arrResultado['OK'] == true):
                            $arrCambioReferencia = array_merge((array)$arrCambioReferencia, $arrResultado['CambiosReferencia']);
                        endif;
                    endif;
                endif;

            endif;
            //FIN ACCIONES SI EL PEDIDO NO ES DE RECEPCION

        endwhile; //FIN BUCLE LINEAS

        //OBTENGO LA RECEPCION MODIFICADA
        $rowDoc = $bd->VerReg("MOVIMIENTO_RECEPCION", "ID_MOVIMIENTO_RECEPCION", $rowMov->ID_MOVIMIENTO_RECEPCION);

        //OBTENGO EL ESTADO FINAL DE LA RECEPCION TRAS LOS CAMBIOS
        $estadoFinalRecepcion = $this->getEstadoRecepcion($rowDoc->ID_MOVIMIENTO_RECEPCION);

        //ACTUALIZO EL ESTADO DE LA RECEPCION
        $sqlUpdate = "UPDATE MOVIMIENTO_RECEPCION SET ESTADO = '" . $estadoFinalRecepcion . "' WHERE ID_MOVIMIENTO_RECEPCION = $rowDoc->ID_MOVIMIENTO_RECEPCION";
        $bd->ExecSQL($sqlUpdate);

        // LOG MOVIMIENTOS.
        $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Mov. entrada", $idMovimiento, "Paso a procesado");

        //BUSCO EL NUMERO DE LINEAS A TRANSMITIR A SAP
        $numOK = $bd->NumRegSTabla("MOVIMIENTO_ENTRADA_LINEA", "ID_MOVIMIENTO_ENTRADA = $rowMov->ID_MOVIMIENTO_ENTRADA AND LINEA_ANULADA = 0 AND BAJA = 0 AND (ID_TIPO_BLOQUEO IS NULL OR ID_TIPO_BLOQUEO = $rowTipoBloqueoPreventivo->ID_TIPO_BLOQUEO)");

        //ACTUALIZO LA FECHA PROCESADO DE LAS LINEAS DEL MOVIMIENTO A PROCESAR (SI TIENE CONTENEDOR ENTRANTE, AÑADIMOS LOS BLOQUEOS NECESARIOS)
        $sqlLineasProcesar    = "SELECT *
                            FROM MOVIMIENTO_ENTRADA_LINEA
                            WHERE ID_MOVIMIENTO_ENTRADA = '" . $idMovimiento . "' AND LINEA_ANULADA = 0 AND BAJA = 0";
        $resultLineasProcesar = $bd->ExecSQL($sqlLineasProcesar);

        //DEFINO UN CONTADOR PARA CONTAR EL NÚMERO DE LÍNEAS A PROCESAR
        $contadorLinea = 0;

        while ($rowLineasProcesar = $bd->SigReg($resultLineasProcesar)):
            //AUMENTO EL VALOR DEL CONTADOR
            $contadorLinea++;

            //BUSCO EL MATERIAL
            $GLOBALS["NotificaErrorPorEmail"] = "No";
            $rowMaterial                      = $bd->VerReg("MATERIAL", "ID_MATERIAL", $rowLineasProcesar->ID_MATERIAL, "No");
            //COMPRUEBO SI EL MATERIAL TIENE UNIDAD DE COMPRA Y SI EL USUARIO TIENE PERMISOS PARA EL TRATAMIENTO PARCIAL DE LA CC
            if ($rowMaterial->ID_UNIDAD_MEDIDA != $rowMaterial->ID_UNIDAD_COMPRA && $rowMaterial->DENOMINADOR_CONVERSION != 0 && $administrador->Hayar_Permiso_Perfil('ADM_ENTRADAS_TRATAMIENTO_PARCIAL_CC') < 2):
                //CANTIDAD DE COMPRA Y UNIDADES BASE Y COMPRA
                $cantidadCompra      = $mat->cantUnidadCompra($rowLineasProcesar->ID_MATERIAL, $rowLineasProcesar->CANTIDAD);
                $unidadesBaseyCompra = $mat->unidadBaseyCompra($rowLineasProcesar->ID_MATERIAL);

                // SE COMPRUEBA SI LA CANTIDAD DE COMPRA ES UN NÚMERO ENTERO
                if (fmod((float) $cantidadCompra, 1) !== 0.0):
                    $strErrorLinea    = $contadorLinea;
                    $strErrorCantidad = $rowLineasProcesar->CANTIDAD . " " . $unidadesBaseyCompra["unidadBase"] . " - " . $cantidadCompra . " " . $unidadesBaseyCompra["unidadCompra"];
                    if ($mostrar_error == 'texto'):
                        $arr_error['ERROR']     = $auxiliar->traduce("Linea", $administrador->ID_IDIOMA) . ": " . $strErrorLinea . ". " . $auxiliar->traduce("El usuario no tiene permitido recepcionar cantidades de compra no enteras", $administrador->ID_IDIOMA) . " ($strErrorCantidad).";
                        $arr_error['ERROR_SGA'] = 1;

                        return $arr_error;
                    else:
                        $html->PagError("ErrorRecepcionTratamientoParcialCC");
                    endif;
                endif;
            endif;

            $sqlUpdate = "UPDATE MOVIMIENTO_ENTRADA_LINEA SET FECHA_PROCESADO = '" . date("Y-m-d H:i:s") . "' WHERE ID_MOVIMIENTO_ENTRADA_LINEA = $rowLineasProcesar->ID_MOVIMIENTO_ENTRADA_LINEA";
            $bd->ExecSQL($sqlUpdate);

            //ACTUALIZO LAS LINEAS DE NECESIDAD ASOCIADAS A ESTA LINEA DE MOVIMIENTO ENTRADA A ESTADO 'Recepcionado'
            $sqlUpdate = "UPDATE NECESIDAD_LINEA SET
                            ESTADO = 'Recepcionado'
                            WHERE ID_MOVIMIENTO_ENTRADA_LINEA = $rowLineasProcesar->ID_MOVIMIENTO_ENTRADA_LINEA AND BAJA = 0";
            $bd->ExecSQL($sqlUpdate);

            //BUSCO LAS LINEAS DE PEDIDO ASOCIADAS A LA LINEA DE RECEPCION
            $GLOBALS["NotificaErrorPorEmail"] = "No";
            $rowPedidoEntradaLinea            = $bd->VerReg("PEDIDO_ENTRADA_LINEA", "ID_PEDIDO_ENTRADA_LINEA", $rowLineasProcesar->ID_PEDIDO_LINEA, "No");
            unset($GLOBALS["NotificaErrorPorEmail"]);
            //SI TIENE ALMACEN SOLPED Y ES DISTINTO AL ALMACEN DE DESTINO GUARDAMOS LINEA PARA ENVIAR AVISO
            if (($rowPedidoEntradaLinea->ID_ALMACEN_SOLPED != "") && ($rowPedidoEntradaLinea->ID_ALMACEN != "") && ($rowPedidoEntradaLinea->ID_ALMACEN != $rowPedidoEntradaLinea->ID_ALMACEN_SOLPED)):
                $arrLineasPedidoAsociadaLineaRecepcion[] = $rowPedidoEntradaLinea->ID_PEDIDO_ENTRADA_LINEA;
            endif;

            //BUSCO LAS NECESIDADES ASOCIADAS A ESTA LINEA DE RECEPCION
            $sqlNecesidadesAsociadas    = "SELECT DISTINCT (ID_NECESIDAD) AS ID_NECESIDAD
                                        FROM NECESIDAD_LINEA NL
                                        WHERE ID_MOVIMIENTO_ENTRADA_LINEA = $rowLineasProcesar->ID_MOVIMIENTO_ENTRADA_LINEA AND BAJA = 0";
            $resultNecesidadesAsociadas = $bd->ExecSQL($sqlNecesidadesAsociadas);
            while ($rowNecesidadAsociada = $bd->SigReg($resultNecesidadesAsociadas)):
                $arrNecesidadesAsociadasLineaRecepcion[] = $rowNecesidadAsociada->ID_NECESIDAD;
            endwhile;

            /* COMPRUEBO SI REQUIERE FDS, SI EL CENTRO FÍSICO TIENE CONTROL DE FDS Y SI NO EXISTE FDS VÁLIDA CARGADA EN EL SISTEMA */
            if ($rowPedidoEntradaLinea):
                // OBTENGO LOS DATOS NECESARIOS PARA LAS COMPROBACIONES
                $GLOBALS["NotificaErrorPorEmail"] = "No";
                $rowAlmacen                       = $bd->VerReg("ALMACEN", "ID_ALMACEN", $rowPedidoEntradaLinea->ID_ALMACEN, "No");
                $GLOBALS["NotificaErrorPorEmail"] = "No";
                $rowCentroFisico                  = $bd->VerReg("CENTRO_FISICO", "ID_CENTRO_FISICO", $rowAlmacen->ID_CENTRO_FISICO, "No");
                $GLOBALS["NotificaErrorPorEmail"] = "No";
                $rowPais                          = $bd->VerReg("PAIS", "ID_PAIS", $rowCentroFisico->ID_PAIS, "No");
                $GLOBALS["NotificaErrorPorEmail"] = "No";
                $rowMat                           = $bd->VerReg("MATERIAL", "ID_MATERIAL", $rowPedidoEntradaLinea->ID_MATERIAL, "No");

                // BUSCO LA FDS CORRESPONDIENTE AL MATERIAL
                $GLOBALS["NotificaErrorPorEmail"] = "No";
                $rowFDS                           = $bd->VerRegRest("FICHA_SEGURIDAD_MATERIAL_IDIOMA", "ID_MATERIAL = $rowMat->ID_MATERIAL AND ID_IDIOMA = $rowPais->ID_IDIOMA_PRINCIPAL AND ESTADO ='Valida'", "No");
                unset($GLOBALS["NotificaErrorPorEmail"]);

                // SI CUMPLE TODAS LAS CONDICIONES
                if ($rowMat->DEBE_TENER_FICHA_SEGURIDAD == 1 && $rowCentroFisico->CONTROL_FDS == 1 && !$rowFDS):

                    // CREO LA INCIDENCIA DE CALIDAD
                    //TIPOLOGIA
                    $rowTipologia          = $bd->VerReg("TIPOLOGIA_INCIDENCIA", "ES_POR_FALTA_FDS", "1", "No");
                    $idTipologiaIncidencia = $rowTipologia->ID_TIPOLOGIA_INCIDENCIA;
                    $selTipologia          = $rowTipologia->NOMBRE_ESP;

                    //INSERTAMOS LA INCIDENCIA EN LA TABLA INCIDENCIA_CALIDAD
                    $sqlInsert = " INSERT INTO INCIDENCIA_CALIDAD SET
									 ID_MATERIAL = " . $rowLineasProcesar->ID_MATERIAL . "
									, ID_ADMINISTRADOR_CREACION = " . $administrador->ID_ADMINISTRADOR . "
									, ID_MATERIAL_FISICO = " . ($rowLineasProcesar->ID_MATERIAL_FISICO == NULL ? 'NULL' : "$rowLineasProcesar->ID_MATERIAL_FISICO") . "
									, ID_UNIDAD = " . $rowMat->ID_UNIDAD_MEDIDA . "
									, CANTIDAD = " . $rowLineasProcesar->CANTIDAD . "
									, FECHA = '" . date('Y-m-d') . "'
					                , ID_TIPOLOGIA_INCIDENCIA = '" . $idTipologiaIncidencia . "'
									, ESTADO = 'Creada'
									, TIPO_INCIDENCIA = 'RecepcionCalidad'
									, ID_MOVIMIENTO_ENTRADA = " . $rowLineasProcesar->ID_MOVIMIENTO_ENTRADA . "
									, ID_MOVIMIENTO_ENTRADA_LINEA = " . $rowLineasProcesar->ID_MOVIMIENTO_ENTRADA_LINEA;
                    $bd->ExecSQL($sqlInsert);
                    $idIncidenciaCalidad = $bd->IdAsignado();

                    // ACTUALIZAMOS EL REGISTRO DE MATERIAL_UBICACION PARA INCLUIR EL ID DE LA INCIDENCIA DE CALIDAD CREADA
                    $sqlUpdate = "UPDATE MATERIAL_UBICACION SET
                                    ID_INCIDENCIA_CALIDAD = $idIncidenciaCalidad
                                    WHERE ID_MATERIAL = $rowLineasProcesar->ID_MATERIAL AND " . ($rowLineasProcesar->ID_MATERIAL_FISICO != NULL ? "ID_MATERIAL_FISICO = $rowLineasProcesar->ID_MATERIAL_FISICO" : "ID_MATERIAL_FISICO IS NULL") . " AND ID_UBICACION = $rowLineasProcesar->ID_UBICACION AND ID_TIPO_BLOQUEO = " . ($rowLineasProcesar->ID_TIPO_BLOQUEO == NULL ? 'NULL' : "$rowLineasProcesar->ID_TIPO_BLOQUEO");
                    $bd->ExecSQL($sqlUpdate);
                endif;
            endif;
            /* COMPRUEBO SI REQUIERE FDS, SI EL CENTRO FÍSICO TIENE CONTROL DE FDS Y SI NO EXISTE FDS VÁLIDA CARGADA EN EL SISTEMA */
        endwhile;
        //FIN ACTUALIZO LA FECHA PROCESADO DE LAS LINEAS DEL MOVIMIENTO A PROCESAR

        //RECUPERO EL MOVIMIENTO Y LA RECEPCION ACTUALIZADOS
        $rowMov                = $bd->VerReg("MOVIMIENTO_ENTRADA", "ID_MOVIMIENTO_ENTRADA", $idMovimiento);
        $estadoFinalMovimiento = $rowMov->ESTADO;
        $rowDoc                = $bd->VerReg("MOVIMIENTO_RECEPCION", "ID_MOVIMIENTO_RECEPCION", $rowMov->ID_MOVIMIENTO_RECEPCION);
        $estadoFinalRecepcion  = $rowDoc->ESTADO;

        //ACTUALIZO LA FECHA DE PROCESADO DEL MOVIMIENTO DE ENTRADA
        if (($estadoInicialMovimiento == 'En Proceso') && ($estadoFinalMovimiento != 'En Proceso')):
            $sqlUpdate = "UPDATE MOVIMIENTO_ENTRADA SET FECHA_PROCESADO = '" . date("Y-m-d H:i:s") . "' WHERE ID_MOVIMIENTO_ENTRADA = $idMovimiento";
            $bd->ExecSQL($sqlUpdate);
        elseif (($estadoInicialMovimiento != 'En Proceso') && ($estadoFinalMovimiento == 'En Proceso')):
            $sqlUpdate = "UPDATE MOVIMIENTO_ENTRADA SET FECHA_PROCESADO = '0000-00-00 00:00:00' WHERE ID_MOVIMIENTO_ENTRADA = $idMovimiento";
            $bd->ExecSQL($sqlUpdate);
        endif;

        //VARIABLE PARA SABER SI TENGO QUE LLAMAR A LA FUNCION liberarCantidadPedidoConocido (incluye llamadas a SAP)
        $llamarLiberarCantidadPedidoConocido = false;

        //ACTUALIZO LA FECHA DE PROCESADO DE LA RECEPCION Y LIBERO LAS LINEAS DE PEDIDO NO RECEPCIONADAS
        if (($estadoInicialRecepcion == 'En Proceso') && ($estadoFinalRecepcion != 'En Proceso')):
            $sqlUpdate = "UPDATE MOVIMIENTO_RECEPCION SET FECHA_PROCESADO = '" . date("Y-m-d H:i:s") . "' WHERE ID_MOVIMIENTO_RECEPCION = $rowDoc->ID_MOVIMIENTO_RECEPCION";
            $bd->ExecSQL($sqlUpdate);

            //ADEMAS LIBERAMOS POSIBLES PEDIDOS CONOCIDOS NO RECEPCIONADOS (hay llamada a SAP)
            $llamarLiberarCantidadPedidoConocido = true;

        elseif (($estadoInicialRecepcion != 'En Proceso') && ($estadoFinalRecepcion == 'En Proceso')):
            $sqlUpdate = "UPDATE MOVIMIENTO_RECEPCION SET FECHA_PROCESADO = '0000-00-00 00:00:00' WHERE ID_MOVIMIENTO_RECEPCION = $rowDoc->ID_MOVIMIENTO_RECEPCION";
            $bd->ExecSQL($sqlUpdate);

            //ADEMAS DES-LIBERAMOS POSIBLES PEDIDOS CONOCIDOS (hay llamada a SAP)
            $llamarLiberarCantidadPedidoConocido = true;
        endif;
        //FIN ACTUALIZO LA FECHA DE PROCESADO

        //SI SON SIN PEDIDO CONODIDO O CON PEDIDO CONOCIDO, AVANZAMOS EL ESTADO A RECEPCIONADO SI SE HAN PROCESADO TODAS LAS LINEAS
        $this->actualizarRecogidasEnProveedor($idMovimiento);

        //SI EL TIPO MOVIMIENTO ES Construccion
        if ($rowMov->TIPO_MOVIMIENTO == 'Construccion'):
            //AVANZAMOS EL ESTADO DE LA RECOGIDA

            //SI EL TRANSPORTE ASOCIADO TIENE SUMINISTRO DIRECTO PUEDE QUE HAYA QUE MOVER MATERIAL A LA INSTALACION
            if ($rowOrdenTransporte->LUGAR_APROVISIONAMIENTO_CONSTRUCCION == "Suministro Directo"):
                $arrMoverMaterial = $this->moverMaterialSuministroDirectoConstruccion($idMovimiento);

                if (count( (array)$arrMoverMaterial) > 0):

                    //OBTENEMOS EL ERROR
                    foreach ($arrMoverMaterial as $mensaje_error):
                        $strError = $strError . $mensaje_error . "<br>";
                    endforeach;

                    //DESHAGO LA TRANSACCION
                    $bd->rollback_transaction();

                    if ($mostrar_error == 'texto'):
                        $arr_error['ERROR'] = $auxiliar->traduce("Se han obtenido los siguientes errores a la hora de realizar el Suministro Directo", $administrador->ID_IDIOMA) . ":<br> " . $strError . "";

                        return $arr_error;
                    else:
                        $html->PagError("ErrorMoverMaterialSuministroDirecto");
                    endif;
                endif;
            endif;
        endif;

        //SI EN SCS TODAS LAS OPERACIONES SE HAN REALIZADO CORRECTAMENTE Y NO HAY FECHA CONTABLE PARA EL INFORME DE FACTURACION LA ASIGNO
        if ($rowMov->FECHA_CONTABLE_INFORME_FACTURACION == '0000-00-00'):
            $sqlUpdate = "UPDATE MOVIMIENTO_ENTRADA SET FECHA_CONTABLE_INFORME_FACTURACION = '" . date("Y-m-d") . "' WHERE ID_MOVIMIENTO_ENTRADA = $rowMov->ID_MOVIMIENTO_ENTRADA";
            $bd->ExecSQL($sqlUpdate);
        endif;

        if ($numOK > 0):
            if ($rowMov->TIPO_MOVIMIENTO == 'PedidoDevolucionVenta'):
                $resultado = $sap->EnvioMovimientoEntradaProcesadoDevolucionVenta($idMovimiento);
            elseif ($rowMov->TIPO_MOVIMIENTO == 'DevolucionOM' || $rowMov->TIPO_MOVIMIENTO == 'MultiOM' || $rowMov->TIPO_MOVIMIENTO == 'Construccion')://LAS DEVOLUCION DE ORDEN DE MONTAJE NO TIENEN INTEGRACION CON SAP
                $resultado['RESULTADO'] = 'OK';
            else:
                //SI EL MOVMIENTO ESTA PENDIENTE CONFIRMACION, NOS ESTAN RESPONDIENDO DESDE SAP QUE ES OK Y LO MARCAMOS COMO ENVIADO A SAP
                if ($rowMov->PENDIENTE_CONFIRMACION_SAP == 1):
                    $resultado['RESULTADO'] = 'OK';

                    //BUSCO LOS TIPOS DE BLOQUEO QUE SE PUEDEN RECEPCIONAR (TODOS MENOS CC)
                    $rowTipoBloqueoRetenidoCalidadNoPreventivo = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "XRC");
                    $rowTipoBloqueoRetenidoCalidadPreventivo   = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "XRCP");

                    $rowTipoBloqueoReparableEnGarantia     = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "QRG");
                    $rowTipoBloqueoReparableNoEnGarantia   = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "QRNG");
                    $rowTipoBloqueoNoReparableNoEnGarantia = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "QNRNG");

                    $rowTipoBloqueoRetenidoCalidadNoPreventivoReparableEnGarantia     = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "XRCRG");
                    $rowTipoBloqueoRetenidoCalidadNoPreventivoReparableNoEnGarantia   = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "XRCRNG");
                    $rowTipoBloqueoRetenidoCalidadNoPreventivoNoReparableNoEnGarantia = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "XRCNRNG");

                    //MARCO LA LINEA DEL MOVIMIENTO DE ENTRADA COMO ENVIADO A SAP
                    $sqlUpdate = "UPDATE MOVIMIENTO_ENTRADA_LINEA 
                                    SET ENVIADO_SAP = 1 
                                    WHERE ID_MOVIMIENTO_ENTRADA = $rowMov->ID_MOVIMIENTO_ENTRADA AND
                                        ENVIADO_SAP = 0 AND
                                        BAJA = 0 AND
                                        LINEA_ANULADA = 0 AND
                                        (ID_TIPO_BLOQUEO IS NULL OR ID_TIPO_BLOQUEO = $rowTipoBloqueoPreventivo->ID_TIPO_BLOQUEO OR ID_TIPO_BLOQUEO = $rowTipoBloqueoRetenidoCalidadNoPreventivo->ID_TIPO_BLOQUEO OR ID_TIPO_BLOQUEO = $rowTipoBloqueoRetenidoCalidadPreventivo->ID_TIPO_BLOQUEO OR ID_TIPO_BLOQUEO = $rowTipoBloqueoRetenidoCalidadNoPreventivoReparableEnGarantia->ID_TIPO_BLOQUEO OR ID_TIPO_BLOQUEO = $rowTipoBloqueoRetenidoCalidadNoPreventivoReparableNoEnGarantia->ID_TIPO_BLOQUEO OR ID_TIPO_BLOQUEO = $rowTipoBloqueoRetenidoCalidadNoPreventivoNoReparableNoEnGarantia->ID_TIPO_BLOQUEO OR ID_TIPO_BLOQUEO = $rowTipoBloqueoReparableEnGarantia->ID_TIPO_BLOQUEO OR ID_TIPO_BLOQUEO = $rowTipoBloqueoReparableNoEnGarantia->ID_TIPO_BLOQUEO OR ID_TIPO_BLOQUEO = $rowTipoBloqueoNoReparableNoEnGarantia->ID_TIPO_BLOQUEO)";
                    $bd->ExecSQL($sqlUpdate);

                else:
                    $resultado = $sap->EnvioMovimientoEntradaProcesado($idMovimiento);
                endif;
            endif;
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

                if ($mostrar_error == 'texto'):
                    $arr_error['ERROR'] = "We have received the following errors from SAP/Se han producido los siguientes errores en el intercambio de información con SAP" . ": " . '<br>' . $strError;

                    return $arr_error;
                else:
                    $html->PagError("ErrorSAP");
                endif;
            endif;
        endif;

        //GENERAMOS EL ARRAY DE RESPUESTA
        $arrDevuelto                                          = array();
        $arrDevuelto['llamarLiberarCantidadPedidoConocido']   = $llamarLiberarCantidadPedidoConocido;
        $arrDevuelto['arrNecesidadesAsociadasLineaRecepcion'] = $arrNecesidadesAsociadasLineaRecepcion;
        $arrDevuelto['arrLineasPedidoAsociadaLineaRecepcion'] = $arrLineasPedidoAsociadaLineaRecepcion;
        $arrDevuelto['arrCambioReferencia']                   = $arrCambioReferencia;
        $arrDevuelto['arrayLineasPedidosInvolucradas']        = $arrayLineasPedidosInvolucradas;

        return $arrDevuelto;
    }


    /**
     * @param $idMovimientoEntrada
     * $mostrar_error SI EL VALOR ES 'texto' EN VEZ DE SALIR PAGINA DE ERROR SE OBTENDRA UN TEXTO
     * HACE LAS COMPROBACIONES NECESARIAS Y LLAMA A LA INTERFAZ ASINCRONA DE RECEPCIONES
     */
    function EntradaMaterialAsincrona($idMovimiento, $txFechaContabilizacion = "", $mostrar_error = "")
    {
        global $bd;
        global $administrador;
        global $auxiliar;
        global $sap;
        global $html;
        global $mat;
        global $ubicacion;

        //ARRAY ERRORES
        $arr_error = array();


        //BUSCO EL TIPO BLOQUEO = CCNP (Control de Calidad de material no preventivo)
        $rowBloqueoCCNP = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "CCNP", "No");

        //BUSCO EL TIPO BLOQUEO = CCP (Control de Calidad de material preventivo)
        $rowBloqueoCCP = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "CCP", "No");

        //BUSCO EL TIPO DE BLOQUEO PREVENTIVO
        $rowTipoBloqueoPreventivo = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "SP", "No");

        //BUSCO LOS TIPOS DE BLOQUEO PDTE CALIDAD Y DC, DEPENDIENDO DE LA DECISION DE CALIDAD PARA LOTES DE FABRICACION SE PUEDEN RECEPCIONAR ASI
        $rowTipoBloqueoPdteCalidad      = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "PC", "No");
        $rowTipoBloqueoRechazadoCalidad = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "SPDPC", "No");

        if ($rowBloqueoCCNP == false || $rowBloqueoCCP == false || $rowTipoBloqueoPreventivo == false || $rowTipoBloqueoPdteCalidad == false || $rowTipoBloqueoRechazadoCalidad == false):
            if ($mostrar_error == 'texto'):
                $arr_error['ERROR'] = $auxiliar->traduce("El Tipo de Bloqueo no existe", $administrador->ID_IDIOMA);

                return $arr_error;
            else:
                $html->PagError("TipoBloqueoNoExiste");
            endif;
        endif;

        //BLOQUEAMOS EL MOVIMIENTO DE ENTRADA
        $sqlMov    = "SELECT * FROM MOVIMIENTO_ENTRADA WHERE ID_MOVIMIENTO_ENTRADA = $idMovimiento FOR UPDATE";
        $resultMov = $bd->ExecSQL($sqlMov);
        $rowMov    = $bd->SigReg($resultMov);

        //COMPRUEBO QUE NO ESTE DADO DE BAJA
        if ($rowMov->BAJA == 1):
            if ($mostrar_error == 'texto'):
                $arr_error['ERROR'] = $auxiliar->traduce("No se puede realizar la operación correspondiente porque el movimiento está dado de baja", $administrador->ID_IDIOMA);

                return $arr_error;
            else:
                $html->PagError("MovimientoBaja");
            endif;
        endif;

        //COMPRUEBO QUE EL TIPO DE MOVIMIENTO ADMITE ASINCRONISMO
        if ($rowMov->TIPO_MOVIMIENTO == 'PedidoDevolucionVenta' || $rowMov->TIPO_MOVIMIENTO == 'DevolucionOM' || $rowMov->TIPO_MOVIMIENTO == 'MultiOM' || $rowMov->TIPO_MOVIMIENTO == 'Construccion'):
            if ($mostrar_error == 'texto'):
                $arr_error['ERROR'] = $auxiliar->traduce("El tipo del movimiento de entrada es incorrecto para realizar la operacion correspondiente", $administrador->ID_IDIOMA);

                return $arr_error;
            else:
                $html->PagError("TipoMovimientoIncorrecto");
            endif;
        endif;

        // 1. COMPRUEBO QUE SE ESTA EN EL ESTADO CORRECTO
        if ($rowMov->ESTADO != "En Proceso"):
            if ($mostrar_error == 'texto'):
                $arr_error['ERROR'] = $auxiliar->traduce("No es posible realizar esta acción en el estado actual del Movimiento", $administrador->ID_IDIOMA);

                return $arr_error;
            else:
                $html->PagError("AccionEnEstadoIncorrecto");
            endif;
        endif;

        // BUSCO LA RECEPCION
        $rowDoc = $bd->VerReg("MOVIMIENTO_RECEPCION", "ID_MOVIMIENTO_RECEPCION", $rowMov->ID_MOVIMIENTO_RECEPCION);

        //SI ESTA ASOCIADO A UNA ORDEN DE TRANSPORTE, COMPROBAMOS QUE SI ES SIN PEDIDO CONOCIDO,NO SE PROCESA SIN ENTREGA ENTRANTE
        $rowOrdenTransporte = false;
        if ($rowDoc->ID_ORDEN_TRANSPORTE != ""):
            //BUSCO LA ORDEN DE TRANSPORTE
            $rowOrdenTransporte = $bd->VerReg("ORDEN_TRANSPORTE", "ID_ORDEN_TRANSPORTE", $rowDoc->ID_ORDEN_TRANSPORTE);

            //COMPRUEBO EL ESTADO DE LAS INTERFACES
            if (
                (($rowOrdenTransporte->MODELO_TRANSPORTE == 'Primero') && ($rowOrdenTransporte->TIENE_GASTOS_TRANSPORTE == 1) && ($rowOrdenTransporte->ESTADO_INTERFACES != 'Finalizada')) ||
                (($rowOrdenTransporte->MODELO_TRANSPORTE == 'Segundo') && ($rowOrdenTransporte->TIENE_GASTOS_TRANSPORTE == 1) && ($rowOrdenTransporte->ESTADO_INTERFACES != 'ZTL Transmitidos'))
            ):
                if ($mostrar_error == 'texto'):
                    $arr_error['ERROR'] = $auxiliar->traduce("Se deben transmitir todas las Interfaces desde la Orden de Transporte antes de poder Procesar la Entrada de Material", $administrador->ID_IDIOMA);

                    return $arr_error;
                else:
                    $html->PagError("FaltaEnvioInterfaces");
                endif;
            endif;
        endif;

        //COMPROBAMOS QUE SI TIENE ASOCIADO UN CONTENEDOR QUE ESTA MARCADADO PARA CALIDAD, SE HAYAN SELECCIONADO LAS CAJAS SUFICIENTES
        if ($rowDoc->ID_CONTENEDOR_ENTRANTE != NULL):
            //BUSCO EL CONTENEDOR ENTRANTE
            $rowContenedorEntrante = $bd->VerReg("CONTENEDOR_ENTRANTE", "ID_CONTENEDOR_ENTRANTE", $rowDoc->ID_CONTENEDOR_ENTRANTE);
            //SI EL CONTENEDOR ENTRANTE ESTA MARCADO PARA CONTROL DE CALIDAD
            if ($rowContenedorEntrante->MARCADO_PARA_CONTROL_CALIDAD == 1):

                //BUSCO LAS CAJAS DEL MOVIMIENTO DE ENTRADA
                $sqlCajas                       = "SELECT DISTINCT MF.NUMERO_ETIQUETA_CAJA
                             FROM MOVIMIENTO_ENTRADA_LINEA MEL
                             INNER JOIN MATERIAL_FISICO MF ON MF.ID_MATERIAL_FISICO = MEL.ID_MATERIAL_FISICO
                             WHERE MEL.ID_MOVIMIENTO_ENTRADA = $rowMov->ID_MOVIMIENTO_ENTRADA AND MEL.LINEA_ANULADA = 0 AND MEL.BAJA = 0 AND MF.MARCADA_PARA_CONTROL_CALIDAD = 1";
                $resultCajas                    = $bd->ExecSQL($sqlCajas);
                $numCajasMarcadasControlCalidad = $bd->NumRegs($resultCajas);

                //SI AUN NO HAY SUFICINETES CAJAS MARCADAS, DESHABILITAMOS EL BOTON DE PROCESAR
                if ((int)$numCajasMarcadasControlCalidad == "0"):
                    if ($mostrar_error == 'texto'):
                        $arr_error['ERROR'] = $auxiliar->traduce("No se han marcado suficientes cajas del contenedor para pasar Control de Calidad", $administrador->ID_IDIOMA);

                        return $arr_error;
                    else:
                        $html->PagError("FaltanMarcarCajasCC");
                    endif;
                endif;
            endif;
        endif;

        // 2. COMPRUEBO QUE TENGA LINEAS
        $clausulaWhere = "ID_MOVIMIENTO_ENTRADA = $idMovimiento AND BAJA = 0";
        $numLineas     = $bd->NumRegsTabla("MOVIMIENTO_ENTRADA_LINEA", $clausulaWhere);
        if ($numLineas == 0):
            if ($mostrar_error == 'texto'):
                $arr_error['ERROR'] = $auxiliar->traduce("El movimiento no tiene articulos", $administrador->ID_IDIOMA);

                return $arr_error;
            else:
                $html->PagError("SinLineas");
            endif;
        endif;

        // 3. COMPRUEBO TODAS LAS LINEAS UBICADAS Y CON CANTIDADES Y ESTADO CORRECTAS
        $clausulaWhere      = "ID_MOVIMIENTO_ENTRADA = $idMovimiento AND (ID_UBICACION = 0 OR CANTIDAD = 0) AND BAJA = 0";
        $numLineasSinUbicar = $bd->NumRegsTabla("MOVIMIENTO_ENTRADA_LINEA", $clausulaWhere);
        if ($numLineasSinUbicar > 0):
            if ($mostrar_error == 'texto'):
                $arr_error['ERROR'] = $auxiliar->traduce("Existen lineas sin ubicación, con cantidad Cero o sin numeros de serie/lote", $administrador->ID_IDIOMA);

                return $arr_error;
            else:
                $html->PagError("ExistenLineasSinUbicar");
            endif;
        endif;


        //EL USUARIO PODRA PROCESAR EL MOVIMIENTO SI TIENE PERMISO DE ESCRITURA PARA EL ALMACEN DESTINO DE TODAS SUS LINEAS
        //AÑADO SEGURIDAD DE ACCESO DE ALMACENES
        if ($administrador->esRestringidoPorZonas()):
            $joinAlmacenPermisosZonas  = " INNER JOIN UBICACION U ON (mel.ID_UBICACION=U.ID_UBICACION)
										   INNER JOIN ALMACEN APZ ON (U.ID_ALMACEN=APZ.ID_ALMACEN) ";
            $whereAlmacenPermisosZonas = " AND APZ.ID_ALMACEN IN " . ($administrador->listadoAlmacenesPermiso("Escritura", "STRING")) . " ";

            // LINEAS CON PERMISO
            $sql                 = "SELECT * FROM MOVIMIENTO_ENTRADA_LINEA mel
                            $joinAlmacenPermisosZonas
                            WHERE mel.ID_MOVIMIENTO_ENTRADA = $idMovimiento AND mel.BAJA = 0
                            $whereAlmacenPermisosZonas";
            $resultLineasPermiso = $bd->ExecSQL($sql);

            //LINEAS TODAS
            $sqlLineasTodas    = "SELECT *
								FROM MOVIMIENTO_ENTRADA_LINEA MEL 							
								WHERE MEL.ID_MOVIMIENTO_ENTRADA = $idMovimiento AND MEL.BAJA = 0";
            $resultLineasTodas = $bd->ExecSQL($sqlLineasTodas);

            //COMPROBACION PERMISO
            $puedeProcesar = $bd->NumRegs($resultLineasTodas) == $bd->NumRegs($resultLineasPermiso);
            if ($puedeProcesar == false):
                if ($mostrar_error == 'texto'):
                    $arr_error['ERROR'] = $auxiliar->traduce("No tiene permisos para realizar esta operacion en esta subzona", $administrador->ID_IDIOMA);

                    return $arr_error;
                else:
                    $html->PagError("SinPermisosSubzona");
                endif;
            endif;
        endif;

        // SELECCIONO LAS LÍNES DE ENTRADA CON LA CANTIDAD TOTAL DE CADA ENTRADA
        $sql          = "SELECT ID_MOVIMIENTO_ENTRADA, ID_MATERIAL, ID_MATERIAL_FISICO, TIPO_LOTE, SUM(CANTIDAD) AS totalCantidadMat, ID_PEDIDO, ID_PEDIDO_LINEA, ID_MOVIMIENTO_SALIDA_LINEA
                            FROM MOVIMIENTO_ENTRADA_LINEA 
                            WHERE ID_MOVIMIENTO_ENTRADA=" . $rowMov->ID_MOVIMIENTO_ENTRADA . " AND BAJA = 0
                            GROUP BY ID_MATERIAL, ID_MATERIAL_FISICO, ID_PEDIDO_LINEA, ID_MOVIMIENTO_SALIDA_LINEA 
                            ORDER BY ID_MOVIMIENTO_ENTRADA_LINEA";
        $resultLineas = $bd->ExecSQL($sql);

        // COMPROBAMOS QUE LOS NÚMEROS DE SERIE DE LA ENTRADA NO SE ENCUENTREN CON STOCK POSITIVO EN EL SISTEMA
        $hayErrorSerieLoteEnSistema  = false;
        $hayErrorSerieLoteEnTransito = false;
        $strErrorEnSistema           = "";
        $strErrorEnTransito          = "";
        $strError                    = "";
        while ($rowLinea = $bd->SigReg($resultLineas)):
            if (($rowMov->TIPO_MOVIMIENTO == 'PedidoProveedor') || ($rowMov->TIPO_MOVIMIENTO == 'PedidoDevolucionVenta') || ($rowMov->TIPO_MOVIMIENTO == 'PedidoCompraSGAManual') || ($rowMov->TIPO_MOVIMIENTO == 'Construccion')):
                //BUSCO LA LINEA DEL PEDIDO DE ENTRADA CORRESPONDIENTE
                $rowPedLin = $bd->VerReg("PEDIDO_ENTRADA_LINEA", "ID_PEDIDO_ENTRADA_LINEA", $rowLinea->ID_PEDIDO_LINEA);

            elseif ($rowMov->TIPO_MOVIMIENTO == 'PedidoTraslado'):
                //BUSCO EL MOVIMIENTO DE SALIDA
                $rowMovSalLin = $bd->VerReg("MOVIMIENTO_SALIDA_LINEA", "ID_MOVIMIENTO_SALIDA_LINEA", $rowLinea->ID_MOVIMIENTO_SALIDA_LINEA);
                //BUSCO LA LINEA DEL PEDIDO DE SALIDA CORRESPONDIENTE
                $rowPedLin = $bd->VerReg("PEDIDO_SALIDA_LINEA", "ID_PEDIDO_SALIDA_LINEA", $rowMovSalLin->ID_PEDIDO_SALIDA_LINEA);
            endif;

            if (($rowLinea->ID_MATERIAL_FISICO != NULL) && ($rowMov->TIPO_MOVIMIENTO <> 'DevolucionOM') && ($rowMov->TIPO_MOVIMIENTO <> 'MultiOM')):
                //BUSCO EL MATERIAL
                $rowMat = $bd->VerReg("MATERIAL", "ID_MATERIAL", $rowLinea->ID_MATERIAL);

                //BUSCO EL MATERIAL ALMACEN
                $rowMatAlm = $bd->VerRegRest("MATERIAL_ALMACEN", "ID_MATERIAL = $rowLinea->ID_MATERIAL AND ID_ALMACEN = $rowPedLin->ID_ALMACEN");

                //COMPRUEBO EL TIPO DE MATERIAL ALMACEN COINCIDE CON EL TIPO DE MATERIAL FISICO
                if ($rowMatAlm->TIPO_LOTE != $rowLinea->TIPO_LOTE):
                    //BUSCO EL MATERIAL
                    $rowAlmDest = $bd->VerReg("ALMACEN", "ID_ALMACEN", $rowPedLin->ID_ALMACEN);

                    //BUSCO EL MATERIAL A PARTIR DEL MATERIAL FISICO
                    $rowMatFisOri = $bd->VerReg("MATERIAL_FISICO", "ID_MATERIAL_FISICO", $rowLinea->ID_MATERIAL_FISICO);

                    //BUSCO EL MATERIAL A PARTIR DEL MATERIAL FISICO
                    $rowMatOri = $bd->VerReg("MATERIAL", "ID_MATERIAL", $rowMatFisOri->ID_MATERIAL);

                    $strError = "<br>" . $auxiliar->traduce("El tipo de lote para el material", $administrador->ID_IDIOMA) . " " . $rowMatOri->REFERENCIA_SGA . " " . $auxiliar->traduce("en el movimiento es", $administrador->ID_IDIOMA) . ": " . $rowLinea->TIPO_LOTE . "<br>";
                    $strError .= $auxiliar->traduce("El tipo de lote para el material", $administrador->ID_IDIOMA) . " " . $rowMat->REFERENCIA_SGA . " " . $auxiliar->traduce("en el almacen", $administrador->ID_IDIOMA) . " " . $rowAlmDest->REFERENCIA . " " . $auxiliar->traduce("es", $administrador->ID_IDIOMA) . ": " . $rowMatAlm->TIPO_LOTE;
                    if ($mostrar_error == 'texto'):
                        $arr_error['ERROR'] = $auxiliar->traduce("El tipo de material no coincide con el especificado para el material y almacen", $administrador->ID_IDIOMA) . ".<br>" . $strError;

                        return $arr_error;
                    else:
                        $html->PagError("TipoLoteDiferentes");
                    endif;
                endif;

                $rowMatFis = $bd->VerReg("MATERIAL_FISICO", "ID_MATERIAL_FISICO", $rowLinea->ID_MATERIAL_FISICO, "No");
                if ($rowMatFis->TIPO_LOTE == 'serie'): //MATERIAL SERIABLE
                    if (($rowMov->TIPO_MOVIMIENTO == 'PedidoProveedor') || ($rowMov->TIPO_MOVIMIENTO == 'PedidoDevolucionVenta') || ($rowMov->TIPO_MOVIMIENTO == 'PedidoCompraSGAManual') || ($rowMov->TIPO_MOVIMIENTO == 'Construccion')):
                        //BUSCO EL PEDIDO DE ENTRADA
                        $rowPed = $bd->VerReg("PEDIDO_ENTRADA", "ID_PEDIDO_ENTRADA", $rowLinea->ID_PEDIDO);

                        $sqlWhere = "";
                        //PEDIDO DE REPARACION O GARANTIA
                        if (($rowPed->TIPO_PEDIDO == "Reparación") || ($rowPed->TIPO_PEDIDO == "Garantía")):
                            $sqlWhere = "AND A.TIPO_ALMACEN != 'proveedor'";
                        endif;
                        $numMaterialSeriable = $bd->NumRegsTabla("MATERIAL_UBICACION MU INNER JOIN UBICACION U ON U.ID_UBICACION = MU.ID_UBICACION INNER JOIN ALMACEN A ON A.ID_ALMACEN = U.ID_ALMACEN", "MU.ID_MATERIAL_FISICO = $rowLinea->ID_MATERIAL_FISICO AND MU.STOCK_TOTAL > 0 $sqlWhere");
                        if ($numMaterialSeriable > 0):
                            $hayErrorSerieLoteEnSistema = true;
                            $strErrorEnSistema          .= "$rowMatFis->NUMERO_SERIE_LOTE<br />";
                        endif;
                    endif;

                    if ($mat->MaterialFisicoEnTransito($rowLinea->ID_MATERIAL_FISICO) == true):
                        $hayErrorSerieLoteEnTransito = true;
                        $strErrorEnTransito          .= "$rowMatFis->NUMERO_SERIE_LOTE<br />";
                    endif;
                endif; //FIN MATERIAL SERIABLE
            endif;
        endwhile;
        if ($hayErrorSerieLoteEnSistema == true):
            $strError = $strErrorEnSistema;
            if ($mostrar_error == 'texto'):
                $arr_error['ERROR'] = $auxiliar->traduce("No se puede procesar la entrada, los siguientes Números de Serie ya se encuentran con stock positivo en el sistema", $administrador->ID_IDIOMA) . ":<br /><br />$strError";

                return $arr_error;
            else:
                $html->PagError("NseriesYaSeEncuentranEnSistema");
            endif;
        endif;
        if ($hayErrorSerieLoteEnTransito == true):
            $strError = $strErrorEnTransito;
            if ($mostrar_error == 'texto'):
                $arr_error['ERROR'] = $auxiliar->traduce("No se puede procesar la entrada, los siguientes Números de Serie se encuentran en tránsito dentro del sistema", $administrador->ID_IDIOMA) . ":<br /><br />$strError";

                return $arr_error;
            else:
                $html->PagError("NseriesYaSeEncuentranEnTransito");
            endif;
        endif;

        // 4. ACTUALIZO EL MOVIMIENTO DE ENTRADA AL ESTADO CORRECTO Y FECHA CONTABILIZACION SI NO ESTA RELLENA
        $sqlUpdate = "UPDATE MOVIMIENTO_ENTRADA SET
                        ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                        , PENDIENTE_CONFIRMACION_SAP = 1
                        , FECHA_CONTABILIZACION = '" . ($txFechaContabilizacion != "" ? $auxiliar->fechaFmtoSQL($txFechaContabilizacion) : date("Y-m-d")) . "'
                        WHERE ID_MOVIMIENTO_ENTRADA = $idMovimiento";
        $bd->ExecSQL($sqlUpdate);

        // 5. SUMO AL STOCK LAS CANTIDADES INDICADAS
        $sql          = "SELECT *
                            FROM MOVIMIENTO_ENTRADA_LINEA 
                            WHERE ID_MOVIMIENTO_ENTRADA = $idMovimiento AND BAJA = 0
                            ORDER BY ID_MOVIMIENTO_ENTRADA_LINEA";
        $resultLineas = $bd->ExecSQL($sql);

        while ($rowLinea = $bd->SigReg($resultLineas)):
            //BUSCO EL MATERIAL
            $rowMat = $bd->VerReg("MATERIAL", "ID_MATERIAL", $rowLinea->ID_MATERIAL, "No");

            //COMPROBAMOS SI LA CANTIDAD DE LA LINEA ES CORRECTA
            $GLOBALS["NotificaErrorPorEmail"] = "No";
            $rowFaltante                      = $bd->VerRegRest("FALTANTE", "ID_PEDIDO_ENTRADA_LINEA=" . $rowLinea->ID_PEDIDO_LINEA . " AND BAJA=0", "No");

            if ($rowFaltante != false && $rowFaltante->ID_PEDIDO_ENTRADA_LINEA != NULL):
                //CANTIDAD ENTREGADA
                $cantidadEntregada       = 0;
                $sqlCantidadEntregada    = "SELECT MEL.CANTIDAD
                                            FROM MOVIMIENTO_ENTRADA_LINEA MEL 
                                            INNER JOIN MOVIMIENTO_ENTRADA ME ON ME.ID_MOVIMIENTO_ENTRADA = MEL.ID_MOVIMIENTO_ENTRADA
                                            INNER JOIN MOVIMIENTO_RECEPCION MR ON MR.ID_MOVIMIENTO_RECEPCION = ME.ID_MOVIMIENTO_RECEPCION
                                            WHERE MEL.ID_PEDIDO_LINEA = $rowFaltante->ID_PEDIDO_ENTRADA_LINEA AND MR.ESTADO <> 'En Proceso' AND MEL.LINEA_ANULADA = 0 AND ME.BAJA = 0 AND MR.BAJA = 0";
                $resultCantidadEntregada = $bd->ExecSQL($sqlCantidadEntregada);

                while ($rowCantidadEntregada = $bd->SigReg($resultCantidadEntregada)):
                    $cantidadEntregada += $rowCantidadEntregada->CANTIDAD;
                endwhile;

                $cantidadEntregada = $auxiliar->formatoMoneda($cantidadEntregada, $rowFaltante->ID_MONEDA);

                //CANTIDAD EN ENTREGA
                $cantidadEnEntrega       = 0;
                $sqlCantidadEnentrega    = "SELECT BL.CANTIDAD, E.ID_ORDEN_TRANSPORTE
                                            FROM BULTO_LINEA BL
                                            INNER JOIN BULTO B ON B.ID_BULTO = BL.ID_BULTO
                                            INNER JOIN EXPEDICION E ON E.ID_EXPEDICION = B.ID_EXPEDICION
                                            WHERE BL.ID_PEDIDO_ENTRADA_LINEA = $rowFaltante->ID_PEDIDO_ENTRADA_LINEA AND E.BAJA = 0";
                $resultCantidadEnEntrega = $bd->ExecSQL($sqlCantidadEnentrega);

                while ($rowCantidadEnEntrega = $bd->SigReg($resultCantidadEnEntrega)):
                    //OBTENEMOS LA RECEPCIÓN
                    $NotificaErrorPorEmail  = "No";
                    $rowMovimientoRecepcion = $bd->VerRegRest("MOVIMIENTO_RECEPCION", "ID_ORDEN_TRANSPORTE=" . $rowCantidadEnEntrega->ID_ORDEN_TRANSPORTE . " AND BAJA=0", "No");

                    if ($rowMovimientoRecepcion == false):
                        //SI NO TIENE RECEPCIÓN, SE AÑADE LA CANTIDAD
                        $cantidadEnEntrega += $rowCantidadEnEntrega->CANTIDAD;
                    else:
                        if (($rowMovimientoRecepcion->ESTADO != "Procesado") && ($rowMovimientoRecepcion->ESTADO != "Ubicado") && ($rowMovimientoRecepcion->ESTADO != "Escaneado y Finalizado")):
                            //DEPENDIENDO EL ESTADO DE LA RECEPCION, SE AÑADE LA CANTIDAD
                            $cantidadEnEntrega += $rowCantidadEnEntrega->CANTIDAD;
                        endif;
                    endif;
                endwhile;

                $cantidadEnEntrega = $auxiliar->formatoMoneda($cantidadEnEntrega, $rowFaltante->ID_MONEDA);

                $cantidadDisponible = $rowFaltante->CANTIDAD_FALTANTE - $cantidadEntregada;

                if ($rowLinea->CANTIDAD > $cantidadDisponible):
                    $arr_error['ERROR'] = $auxiliar->traduce("La cantidad introducida es incorrecta", $administrador->ID_IDIOMA);

                    return $arr_error;
                endif;
            endif;

            if (($rowMov->TIPO_MOVIMIENTO == 'PedidoProveedor') || ($rowMov->TIPO_MOVIMIENTO == 'PedidoDevolucionVenta') || ($rowMov->TIPO_MOVIMIENTO == 'PedidoCompraSGAManual') || ($rowMov->TIPO_MOVIMIENTO == 'Construccion')):
                //BUSCO LA LINEA DEL PEDIDO DE ENTRADA CORRESPONDIENTE
                $rowPedLin = $bd->VerReg("PEDIDO_ENTRADA_LINEA", "ID_PEDIDO_ENTRADA_LINEA", $rowLinea->ID_PEDIDO_LINEA);
            elseif ($rowMov->TIPO_MOVIMIENTO == 'PedidoTraslado'):
                //BUSCO EL MOVIMIENTO DE SALIDA
                $rowMovSalLin = $bd->VerReg("MOVIMIENTO_SALIDA_LINEA", "ID_MOVIMIENTO_SALIDA_LINEA", $rowLinea->ID_MOVIMIENTO_SALIDA_LINEA);
                //BUSCO LA LINEA DEL PEDIDO DE SALIDA CORRESPONDIENTE
                $rowPedLin = $bd->VerReg("PEDIDO_SALIDA_LINEA", "ID_PEDIDO_SALIDA_LINEA", $rowMovSalLin->ID_PEDIDO_SALIDA_LINEA);
            endif;

            if (($rowMov->TIPO_MOVIMIENTO == 'PedidoProveedor') || ($rowMov->TIPO_MOVIMIENTO == 'PedidoDevolucionVenta') || ($rowMov->TIPO_MOVIMIENTO == 'PedidoCompraSGAManual')):
                //COMPRUEBO QUE EL PEDIDO ESTA LIBERADO
                $rowPed = $bd->VerReg("PEDIDO_ENTRADA", "ID_PEDIDO_ENTRADA", $rowLinea->ID_PEDIDO);
                if (($rowPed->INDICADOR_LIBERACION != 'Liberado') && ($rowPed->INDICADOR_LIBERACION != 'No sujeto a liberación')):
                    if ($mostrar_error == 'texto'):
                        $arr_error['ERROR'] = $auxiliar->traduce("El Pedido no esta Liberado", $administrador->ID_IDIOMA);

                        return $arr_error;
                    else:
                        $html->PagError("PedidoNoDisponible");
                    endif;
                endif;
            endif;

            //COMPROBAR QUE LA LINEA NO ESTE DADA DE BAJA
            if ($rowLinea->BAJA == 1):
                if ($mostrar_error == 'texto'):
                    $arr_error['ERROR'] = $auxiliar->traduce("La linea de movimiento esta dada de baja", $administrador->ID_IDIOMA);

                    return $arr_error;
                else:
                    $html->PagError("LineaDeBaja");
                endif;
            endif;

            //COMPRUEBO MATERIAL FISICO
            if ($rowLinea->ID_MATERIAL_FISICO != NULL):
                $rowMatFis = $bd->VerReg("MATERIAL_FISICO", "ID_MATERIAL_FISICO", $rowLinea->ID_MATERIAL_FISICO, "No");
                //COMPRUEBO SI ES NECESARIO QUE LLEVE LOTE PROVEEDOR
                if (($rowMat->CON_LOTE_PROVEEDOR == 1) && ($rowMatFis->LOTE_PROVEEDOR == NULL)):
                    if ($mostrar_error == 'texto'):
                        $arr_error['ERROR'] = $auxiliar->traduce("No se ha introducido el Lote Proveedor", $administrador->ID_IDIOMA);

                        return $arr_error;
                    else:
                        $html->PagError("LoteProveedorVacio");
                    endif;
                endif;

                //COMPRUEBO SI ES NECESARIO QUE LLEVE FECHA CADUCIDAD PROVEEDOR
                if (($rowMat->CON_FECHA_CADUCIDAD_PROVEEDOR == 1) && ($rowMatFis->FECHA_CADUCIDAD == NULL)):
                    if ($mostrar_error == 'texto'):
                        $arr_error['ERROR'] = $auxiliar->traduce("No se ha introducido la Fecha de Caducidad", $administrador->ID_IDIOMA);

                        return $arr_error;
                    else:
                        $html->PagError("FechaCaducidadVacia");
                    endif;
                endif;
            endif;

            //COMPRUEBO QUE LA UBICACION DESTINO SEA CORRECTA
            $rowUbiDestinoMaterial = $bd->VerReg("UBICACION", "ID_UBICACION", $rowLinea->ID_UBICACION);

            //COMPRUEBO QUE LA UBICACION NO ESTE DADA DE BAJA
            if ($rowUbiDestinoMaterial->BAJA != 0):
                if ($mostrar_error == 'texto'):
                    $arr_error['ERROR'] = $auxiliar->traduce("La ubicación seleccionada está dada de baja", $administrador->ID_IDIOMA);

                    return $arr_error;
                else:
                    $html->PagError("UbicacionBaja");
                endif;
            endif;

            if ($rowDoc->VIA_RECEPCION == 'WEB'): //ALMACEN SIN RADIOFRECUENCIA, LA UBICACION NO PUEDE TENER TIPO DE UBICACION
                if ($rowLinea->ID_TIPO_BLOQUEO != NULL): //MATERIAL CON BLOQUEO
                    $rowTipoBloqueo = $bd->VerReg("TIPO_BLOQUEO", "ID_TIPO_BLOQUEO", $rowLinea->ID_TIPO_BLOQUEO);
                    if ($rowTipoBloqueo->CONTROL_CALIDAD == 1): //BLOQUEO CON CONTROL DE CALIDAD
                        if ($rowUbiDestinoMaterial->TIPO_UBICACION != 'Calidad'):
                            $strError = "Material " . $rowMat->REFERENCIA_SGA . " - " . $rowMat->DESCRIPCION . ", Ubicación: " . $rowUbiDestinoMaterial->UBICACION;
                            if ($mostrar_error == 'texto'):
                                $arr_error['ERROR'] = $auxiliar->traduce("La ubicación de la línea no es válida para el almacén", $administrador->ID_IDIOMA) . ".<br>" . $strError;

                                return $arr_error;
                            else:
                                $html->PagError("UbicacionDestinoNoValida");
                            endif;
                        endif;
                    else: //BLOQUEO SIN CONTROL DE CALIDAD
                        if ($rowUbiDestinoMaterial->TIPO_UBICACION != NULL):
                            $strError = "Material " . $rowMat->REFERENCIA_SGA . " - " . $rowMat->DESCRIPCION . ", Ubicación: " . $rowUbiDestinoMaterial->UBICACION;
                            if ($mostrar_error == 'texto'):
                                $arr_error['ERROR'] = $auxiliar->traduce("La ubicación de la línea no es válida para el almacén", $administrador->ID_IDIOMA) . ".<br>" . $strError;

                                return $arr_error;
                            else:
                                $html->PagError("UbicacionDestinoNoValida");
                            endif;
                        endif;
                    endif;
                else: //MATERIAL SIN BLOQUEO
                    if (($rowUbiDestinoMaterial->TIPO_UBICACION != NULL) && ($rowMov->TIPO_MOVIMIENTO != 'Construccion')):
                        $strError = "Material " . $rowMat->REFERENCIA_SGA . " - " . $rowMat->DESCRIPCION . ", Ubicación: " . $rowUbiDestinoMaterial->UBICACION;
                        if ($mostrar_error == 'texto'):
                            $arr_error['ERROR'] = $auxiliar->traduce("La ubicación de la línea no es válida para el almacén", $administrador->ID_IDIOMA) . ".<br>" . $strError;

                            return $arr_error;
                        else:
                            $html->PagError("UbicacionDestinoNoValida");
                        endif;
                    endif;
                endif;
            elseif ($rowDoc->VIA_RECEPCION == 'PDA'): //LA UBICACION TIENE QUE SER DE TIPO UBICACION ENTRADA
                if ($rowLinea->ID_TIPO_BLOQUEO != NULL): //MATERIAL CON BLOQUEO
                    $rowTipoBloqueo = $bd->VerReg("TIPO_BLOQUEO", "ID_TIPO_BLOQUEO", $rowLinea->ID_TIPO_BLOQUEO);
                    if ($rowTipoBloqueo->CONTROL_CALIDAD == 1): //BLOQUEO CON CONTROL DE CALIDAD
                        if ($rowUbiDestinoMaterial->TIPO_UBICACION != 'Calidad'):
                            $strError = "Material " . $rowMat->REFERENCIA_SGA . " - " . $rowMat->DESCRIPCION . ", Ubicación: " . $rowUbiDestinoMaterial->UBICACION;
                            if ($mostrar_error == 'texto'):
                                $arr_error['ERROR'] = $auxiliar->traduce("La ubicación de la línea no es válida para el almacén", $administrador->ID_IDIOMA) . ".<br>" . $strError;

                                return $arr_error;
                            else:
                                $html->PagError("UbicacionDestinoNoValida");
                            endif;
                        endif;
                    else: //BLOQUEO SIN CONTROL DE CALIDAD
                        if ($rowUbiDestinoMaterial->TIPO_UBICACION != 'Entrada'):
                            $strError = "Material " . $rowMat->REFERENCIA_SGA . " - " . $rowMat->DESCRIPCION . ", Ubicación: " . $rowUbiDestinoMaterial->UBICACION;
                            if ($mostrar_error == 'texto'):
                                $arr_error['ERROR'] = $auxiliar->traduce("La ubicación de la línea no es válida para el almacén", $administrador->ID_IDIOMA) . ".<br>" . $strError;

                                return $arr_error;
                            else:
                                $html->PagError("UbicacionDestinoNoValida");
                            endif;
                        endif;
                    endif;
                else: //MATERIAL SIN BLOQUEO
                    if ($rowUbiDestinoMaterial->TIPO_UBICACION != 'Entrada'):
                        $strError = "Material " . $rowMat->REFERENCIA_SGA . " - " . $rowMat->DESCRIPCION . ", Ubicación: " . $rowUbiDestinoMaterial->UBICACION;
                        if ($mostrar_error == 'texto'):
                            $arr_error['ERROR'] = $auxiliar->traduce("La ubicación de la línea no es válida para el almacén", $administrador->ID_IDIOMA) . ".<br>" . $strError;

                            return $arr_error;
                        else:
                            $html->PagError("UbicacionDestinoNoValida");
                        endif;
                    endif;
                endif;
            endif;

            //COMPROBAMOS QUE EL MATERIAL SE UBICA EN EL ALMACEN CORRESPONDIENTE
            if (($rowMov->TIPO_MOVIMIENTO == 'PedidoProveedor') || ($rowMov->TIPO_MOVIMIENTO == 'PedidoDevolucionVenta') || ($rowMov->TIPO_MOVIMIENTO == 'PedidoCompraSGAManual')):
                //BUSCO EL PEDIDO LINEA
                $rowPedidoLinea = $bd->VerReg("PEDIDO_ENTRADA_LINEA", "ID_PEDIDO_ENTRADA_LINEA", $rowLinea->ID_PEDIDO_LINEA);
                if ($rowPedidoLinea->ID_ALMACEN != $rowUbiDestinoMaterial->ID_ALMACEN):
                    if ($mostrar_error == 'texto'):
                        $arr_error['ERROR'] = $auxiliar->traduce("La ubicación seleccionada no pertenece al almacén esperado", $administrador->ID_IDIOMA);

                        return $arr_error;
                    else:
                        $html->PagError("UbicacionAlmacenNoValida");
                    endif;
                endif;

            elseif ($rowMov->TIPO_MOVIMIENTO == 'PedidoTraslado'):
                //BUSCO EL ALBARAN
                $rowAlbaran = $bd->VerReg("ALBARAN", "ID_ALBARAN", $rowLinea->ID_ALBARAN);
                if ($rowAlbaran->ID_ALMACEN_DESTINO != $rowUbiDestinoMaterial->ID_ALMACEN):
                    if ($mostrar_error == 'texto'):
                        $arr_error['ERROR'] = $auxiliar->traduce("La ubicación seleccionada no pertenece al almacén esperado", $administrador->ID_IDIOMA);

                        return $arr_error;
                    else:
                        $html->PagError("UbicacionAlmacenNoValida");
                    endif;
                endif;
            elseif (($rowMov->TIPO_MOVIMIENTO == 'RecepcionMaterialEstropeado') || ($rowMov->TIPO_MOVIMIENTO == 'RecepcionMaterialNoConforme')):
                //BUSCO EL MOVIMIENTO DE SALIDA LINEA
                $rowMovimientoSalidaLinea = $bd->VerReg("MOVIMIENTO_SALIDA_LINEA", "ID_MOVIMIENTO_SALIDA_LINEA", $rowLinea->ID_MOVIMIENTO_SALIDA_LINEA);
                if ($rowMovimientoSalidaLinea->ID_ALMACEN_DESTINO != $rowUbiDestinoMaterial->ID_ALMACEN):
                    if ($mostrar_error == 'texto'):
                        $arr_error['ERROR'] = $auxiliar->traduce("La ubicación seleccionada no pertenece al almacén esperado", $administrador->ID_IDIOMA);

                        return $arr_error;
                    else:
                        $html->PagError("UbicacionAlmacenNoValida");
                    endif;
                endif;
            elseif ($rowMov->TIPO_MOVIMIENTO == 'Construccion')://COMPROBAMOS QUE EL ALMACEN DESTINO PERTENECE AL CENTRO FISICO, SI ES SUMINISTRO DIRECTO PUEDE IR A CUALQUIER ALMACEN DE CONSOLIDACION

                //BUSCO EL PEDIDO LINEA
                $rowPedidoLinea = $bd->VerReg("PEDIDO_ENTRADA_LINEA", "ID_PEDIDO_ENTRADA_LINEA", $rowLinea->ID_PEDIDO_LINEA);

                //SI EL TRANSPORTE ASOCIADO TIENE SUMINISTRO DIRECTO PUEDE QUE LA UBICACION FINAL ESTE EN UNA INSTALACION
                if ($rowOrdenTransporte->LUGAR_APROVISIONAMIENTO_CONSTRUCCION == "Suministro Directo"):

                    if ($rowPedidoLinea->ID_ALMACEN != $rowUbiDestinoMaterial->ID_ALMACEN): //SI NO VA A CONSOLIDACION, COMPROBAMOS QUE VA A UNA INSTALACION DEL CF
                        //BUSCAMOS EL ALMACEN DESTINO
                        $rowAlmacenDestinoMaterial = $bd->VerReg("ALMACEN", "ID_ALMACEN", $rowUbiDestinoMaterial->ID_ALMACEN);
                        $rowAlmacenDestinoPedido   = $bd->VerReg("ALMACEN", "ID_ALMACEN", $rowPedidoLinea->ID_ALMACEN);

                        //COMPROBAMOS QUE PERTENECEN AL MISMO CF
                        if ($rowAlmacenDestinoPedido->ID_CENTRO_FISICO != $rowAlmacenDestinoMaterial->ID_CENTRO_FISICO):
                            if ($mostrar_error == 'texto'):
                                $arr_error['ERROR'] = $auxiliar->traduce("La ubicación seleccionada no pertenece al almacén esperado", $administrador->ID_IDIOMA);

                                return $arr_error;
                            else:
                                $html->PagError("UbicacionAlmacenNoValida");
                            endif;
                        endif;

                        //BUSCAMOS UBICACION PARA DEJAR EL MATERIAL
                        $GLOBALS["NotificaErrorPorEmail"] = "No";
                        $rowUbiConsolidacion              = $bd->VerRegRest("UBICACION", "ID_ALMACEN = $rowAlmacenDestinoPedido->ID_ALMACEN AND UBICACION = '" . $rowAlmacenDestinoPedido->REFERENCIA . "' AND TIPO_UBICACION IS NULL", "No");
                        if ($rowUbiConsolidacion == false):
                            if ($mostrar_error == 'texto'):
                                $arr_error['ERROR'] = $auxiliar->traduce("No hay definida una ubicación de tipo entrada", $administrador->ID_IDIOMA);

                                return $arr_error;
                            else:
                                $html->PagError("UbicacionTipoEntradaNoDefinida");
                            endif;
                        endif;

                        //ACTUALIZAMOS LA UBICACION PARA DEJAR EL MATERIAL EN CONSOLIDACION Y POSTERIORMENTE TRASPASARLO A LA UBICACION FINAL
                        $rowLinea->ID_UBICACION = $rowUbiConsolidacion->ID_UBICACION;
                    endif;
                else:
                    //COMPROBAMOS QUE VA AL ALMACEN DE CONSOLIDACION
                    if ($rowPedidoLinea->ID_ALMACEN != $rowUbiDestinoMaterial->ID_ALMACEN):
                        if ($mostrar_error == 'texto'):
                            $arr_error['ERROR'] = $auxiliar->traduce("La ubicación seleccionada no pertenece al almacén esperado", $administrador->ID_IDIOMA);

                            return $arr_error;
                        else:
                            $html->PagError("UbicacionAlmacenNoValida");
                        endif;
                    endif;
                endif;
            endif;

            //COMPRUEBO SI ALGUNA LINEA TIENE MARCADO EL INDICADOR DE ENTREGA FINAL
            if (($rowLinea->ENTREGA_FINAL_PEDIDO == 1) && ($rowMov->TIPO_MOVIMIENTO == 'PedidoProveedor')):
                //COMPRUEBO QUE EL PEDIDO SEA DE RECEPCION
                if ($rowPed->TIPO_PEDIDO != "Compra"):
                    if ($mostrar_error == 'texto'):
                        $arr_error['ERROR'] = $auxiliar->traduce("Las líneas de pedidos de reparación/garantía no pueden tener marcadas el check de entrega final", $administrador->ID_IDIOMA);

                        return $arr_error;
                    else:
                        $html->PagError("PedidoReparacionGarantia");
                    endif;
                endif;
            endif;

            //ACCIONES SI EL PEDIDO NO ES DE RECEPCION Y PROVENIENTE DE PROVEEDOR. LOGISTICA INVERSA
            if (($rowPed->TIPO_PEDIDO != "Compra") && ($rowPed->TIPO_PEDIDO != "Compra SGA Manual") && ($rowMov->TIPO_MOVIMIENTO == 'PedidoProveedor')): //PEDIDO DE REPARACION O GARANTIA
                //COMPRUEBO QUE NO HAYA COMPONENTES EN PREPARACION O PENDIENTES DE EXPEDIR PARA ESTA LINEA
                $sqlComponentesPreparandose    = "SELECT COUNT(*) AS NUM
                                                     FROM MOVIMIENTO_SALIDA_LINEA MSL
                                                     INNER JOIN PEDIDO_SALIDA_LINEA PSL ON PSL.ID_PEDIDO_SALIDA_LINEA = MSL.ID_PEDIDO_SALIDA_LINEA
                                                     INNER JOIN PEDIDO_SALIDA PS ON PS.ID_PEDIDO_SALIDA = PSL.ID_PEDIDO_SALIDA
                                                     WHERE MSL.ESTADO IN ('Reservado para Preparacion', 'En Preparacion', 'Pendiente de Expedir', 'Transmitido a SAP') AND PS.PEDIDO_SAP = '" . $rowPed->PEDIDO_SAP . "' AND PSL.ID_LINEA_ZREP_ZGAR = $rowPedLin->ID_PEDIDO_ENTRADA_LINEA AND MSL.LINEA_ANULADA = 0 AND MSL.BAJA = 0";
                $resultComponentesPreparandose = $bd->ExecSQL($sqlComponentesPreparandose);
                if (($resultComponentesPreparandose != false) && ($bd->NumRegs($resultComponentesPreparandose) == 1)):
                    $rowComponentesPreparandose = $bd->SigReg($resultComponentesPreparandose);
                    $numComponentesPreparandose = $rowComponentesPreparandose->NUM;

                    if ($numComponentesPreparandose > 0):
                        if ($mostrar_error == 'texto'):
                            $arr_error['ERROR'] = $auxiliar->traduce("No se puede recepcionar un material reparado si se están preparando componentes para repararlo", $administrador->ID_IDIOMA);

                            return $arr_error;
                        else:
                            $html->PagError("RecepcionMaterialComponentesPreparandose");
                        endif;
                    endif;
                endif;

                //BUSCO LA LINEA DEL PEDIDO DE TRASLADO FICTICIO
                $sqlPedidoSalidaLinea    = "SELECT *
                                        FROM PEDIDO_SALIDA_LINEA PSL
                                        INNER JOIN PEDIDO_SALIDA PS ON PS.ID_PEDIDO_SALIDA = PSL.ID_PEDIDO_SALIDA
                                        WHERE PSL.LINEA_PEDIDO_SAP = '" . $rowPedLin->LINEA_PEDIDO_SAP . "' AND PS.PEDIDO_SAP = '" . $rowPed->PEDIDO_SAP . "' AND ID_LINEA_ZREP_ZGAR IS NULL";
                $resultPedidoSalidaLinea = $bd->ExecSQL($sqlPedidoSalidaLinea);
                if ($bd->NumRegs($resultPedidoSalidaLinea) != 1):
                    if ($mostrar_error == 'texto'):
                        $arr_error['ERROR'] = $auxiliar->traduce("No se ha encontrado la Linea del traslado Ficticio", $administrador->ID_IDIOMA);

                        return $arr_error;
                    else:
                        $html->PagError("ErrorLineaPedidoTraslado");
                    endif;
                endif;

                $rowLineaPedidoTraslado = $bd->SigReg($resultPedidoSalidaLinea);

                //BUSCO EL MOVIENTO SALIDA LINEA
                $rowMovSalDirecto = $bd->VerReg("MOVIMIENTO_SALIDA_LINEA", "ID_MOVIMIENTO_SALIDA_LINEA", $rowLineaPedidoTraslado->ID_MOVIMIENTO_SALIDA_LINEA);
                if ($rowMovSalDirecto == false):
                    if ($mostrar_error == 'texto'):
                        $arr_error['ERROR'] = $auxiliar->traduce("El movimiento de salida directo no existe", $administrador->ID_IDIOMA);

                        return $arr_error;
                    else:
                        $html->PagError("MovimientoSalidaDirectoNoExiste");
                    endif;
                endif;

                //BUSCO EL MATERIAL UBICACION DEL PROVEEDOR
                $rowMatUbi = $bd->VerRegRest("MATERIAL_UBICACION", "ID_MATERIAL = $rowMovSalDirecto->ID_MATERIAL AND ID_MATERIAL_FISICO " . ($rowMovSalDirecto->ID_MATERIAL_FISICO == NULL ? 'IS NULL' : "= $rowMovSalDirecto->ID_MATERIAL_FISICO") . " AND ID_UBICACION = $rowMovSalDirecto->ID_UBICACION_DESTINO AND ID_TIPO_BLOQUEO = $rowMovSalDirecto->ID_TIPO_BLOQUEO AND ID_ORDEN_TRABAJO_MOVIMIENTO = $rowMovSalDirecto->ID_ORDEN_TRABAJO_MOVIMIENTO AND ID_INCIDENCIA_CALIDAD " . ($rowMovSalDirecto->ID_INCIDENCIA_CALIDAD == NULL ? 'IS NULL' : "= $rowMovSalDirecto->ID_INCIDENCIA_CALIDAD"), "No");
                $strError  = "Material " . $rowMat->REFERENCIA_SGA . " - " . $rowMat->DESCRIPCION . ", Ubicación: " . $rowUbiDestinoMaterial->UBICACION;
                if ($rowMatUbi == false):
                    if ($mostrar_error == 'texto'):
                        $arr_error['ERROR'] = $auxiliar->traduce("No existe el material a recepcionar en el proveedor", $administrador->ID_IDIOMA) . ".<br /><br />$strError";

                        return $arr_error;
                    else:
                        $html->PagError("MaterialUbicacionProveedorNoExiste");
                    endif;
                endif;

                //COMPRUEBO QUE HAYA STOCK SUFICIENTE EN PROVEEDOR
                if ($rowMatUbi->STOCK_TOTAL < $rowLinea->CANTIDAD):
                    $strError = $auxiliar->traduce("Material", $administrador->ID_IDIOMA) . " " . $rowMat->REFERENCIA_SGA . " - " . ($administrador->ID_IDIOMA == 'ESP' ? $rowMat->DESCRIPCION : $rowMat->DESCRIPCION_EN) . "; ";
                    if ($rowMovSalDirecto->ID_MATERIAL_FISICO != NULL):
                        $rowMaterialFisicoProveedor = $bd->VerReg("MATERIAL_FISICO", "ID_MATERIAL_FISICO", $rowMovSalDirecto->ID_MATERIAL_FISICO);
                        $strError                   = $strError . $auxiliar->traduce("Numero de", $administrador->ID_IDIOMA) . " " . $auxiliar->traduce($rowMaterialFisicoProveedor->TIPO_LOTE, $administrador->ID_IDIOMA) . " " . $rowMaterialFisicoProveedor->NUMERO_SERIE_LOTE . "; ";
                    endif;
                    $strError = $strError . $auxiliar->traduce("Cantidad en proveedor", $administrador->ID_IDIOMA) . ": " . $rowMatUbi->STOCK_TOTAL;
                    if ($mostrar_error == 'texto'):
                        $arr_error['ERROR'] = $auxiliar->traduce("Stock insuficiente en proveedor", $administrador->ID_IDIOMA) . ": " . "<br><br>" . $strError;

                        return $arr_error;
                    else:
                        $html->PagError("StockInsuficienteEnProveedorDetalle");
                    endif;
                endif;

                //SI ES SERIABLE Y NO VUELVE EL ENVIADO COMPRUEBO QUE NO SE HAYAN CRUZADOS LOS NUMERO DE SERIE
                if (($rowMovSalDirecto->ID_MATERIAL_FISICO != NULL) && ($rowMatFis->TIPO_LOTE == 'serie') && ($rowMovSalDirecto->ID_MATERIAL_FISICO != $rowLinea->ID_MATERIAL_FISICO)):
                    $numSeriables = $bd->NumRegsTabla("MATERIAL_UBICACION", "ID_MATERIAL_FISICO = $rowLinea->ID_MATERIAL_FISICO AND ID_MATERIAL = $rowMovSalDirecto->ID_MATERIAL AND ACTIVO = 1");
                    if ($numSeriables > 0):
                        $strError = "Número de serie: " . $rowMatFis->NUMERO_SERIE_LOTE;
                        if ($mostrar_error == 'texto'):
                            $arr_error['ERROR'] = $auxiliar->traduce("El número de serie que se está intentando recepcionar no coincide con el enviado", $administrador->ID_IDIOMA) . ":<br /><br />$strError";

                            return $arr_error;
                        else:
                            $html->PagError("ErrorMaterialSeriableEnviado");
                        endif;
                    endif;
                endif;

                //BUSCO EL NUMERO DE MOVIMIENTOS PROCESADOS CON EL MISMO NUMERO DE LINEA DE PEDIDO
                $num = $bd->NumRegsTabla("MOVIMIENTO_ENTRADA_LINEA MEL INNER JOIN MOVIMIENTO_ENTRADA ME ON ME.ID_MOVIMIENTO_ENTRADA = MEL.ID_MOVIMIENTO_ENTRADA", "ME.ESTADO <> 'En Proceso' AND MEL.ID_PEDIDO_LINEA = $rowPedLin->ID_PEDIDO_ENTRADA_LINEA AND MEL.ID_MOVIMIENTO_ENTRADA_LINEA <> $rowLinea->ID_MOVIMIENTO_ENTRADA_LINEA AND MEL.BAJA = 0 AND MEL.LINEA_ANULADA = 0 AND MEL.CANTIDAD > 0"); //exit($num);
                if ($num == 0):
                    //ES LA PRIMERA VEZ QUE RECEPCIONO MATERIAL DE ESTA LINEA DE PEDIDO, DOY DE BAJA SUS COMPONENTES

                    //BUSCO LOS MOVIMIENTOS DE SALIDA DE LA LINEA DEL PEDIDO
                    $sqlMovSalLineas    = "SELECT SUM(MSL.CANTIDAD) AS CANTIDAD, MSL.ID_MATERIAL, ID_MATERIAL_FISICO, ID_UBICACION_DESTINO
                                        FROM MOVIMIENTO_SALIDA_LINEA MSL
                                        INNER JOIN MOVIMIENTO_SALIDA MS ON MS.ID_MOVIMIENTO_SALIDA = MSL.ID_MOVIMIENTO_SALIDA
                                        INNER JOIN PEDIDO_SALIDA_LINEA PSL ON PSL.ID_PEDIDO_SALIDA_LINEA = MSL.ID_PEDIDO_SALIDA_LINEA
                                        WHERE PSL.ID_LINEA_ZREP_ZGAR = $rowPedLin->ID_PEDIDO_ENTRADA_LINEA AND MSL.ESTADO = 'Expedido' AND MSL.LINEA_ANULADA = 0 AND MSL.BAJA = 0
                                        GROUP BY MSL.ID_MATERIAL, MSL.ID_MATERIAL_FISICO, PSL.ID_LINEA_ZREP_ZGAR";
                    $resultMovSalLineas = $bd->ExecSQL($sqlMovSalLineas);
                    while ($rowMovSalLinea = $bd->SigReg($resultMovSalLineas)):
                        //BUSCO EL ALMACEN DEL PROVEEDOR
                        $rowAlm = $bd->VerReg("ALMACEN", "ID_PROVEEDOR", $rowPed->ID_PROVEEDOR, "No");
                        if ($rowAlm == false):
                            if ($mostrar_error == 'texto'):
                                $arr_error['ERROR'] = $auxiliar->traduce("El almacén del proveedor no existe", $administrador->ID_IDIOMA);

                                return $arr_error;
                            else:
                                $html->PagError("AlmacenProveedorNoExiste");
                            endif;
                        endif;

                        //BUSCO LA UBICACION DE PROVEEDOR
                        $rowUbi = $bd->VerReg("UBICACION", "ID_ALMACEN", $rowAlm->ID_ALMACEN, "No");
                        if ($rowUbi == false):
                            if ($mostrar_error == 'texto'):
                                $arr_error['ERROR'] = $auxiliar->traduce("La ubicacion del proveedor no existe", $administrador->ID_IDIOMA);

                                return $arr_error;
                            else:
                                $html->PagError("UbicacionProveedorNoExiste");
                            endif;
                        endif;

                        //BUSCO MATERIAL UBICACION EN PROVEEDOR
                        $rowMatUbiComponentes = $bd->VerRegRest("MATERIAL_UBICACION", "ID_MATERIAL = $rowMovSalLinea->ID_MATERIAL AND ID_UBICACION = $rowUbi->ID_UBICACION AND ID_MATERIAL_FISICO " . ($rowMovSalLinea->ID_MATERIAL_FISICO == NULL ? 'IS NULL' : "= $rowMovSalLinea->ID_MATERIAL_FISICO") . " AND ID_TIPO_BLOQUEO IS NULL AND ID_ORDEN_TRABAJO_MOVIMIENTO IS NULL AND ID_INCIDENCIA_CALIDAD IS NULL", "No");
                        if ($rowMatUbi == false):
                            if ($mostrar_error == 'texto'):
                                $arr_error['ERROR'] = $auxiliar->traduce("No existe el material a recepcionar en el proveedor", $administrador->ID_IDIOMA) . ".<br /><br />$strError";

                                return $arr_error;
                            else:
                                $html->PagError("MaterialUbicacionProveedorNoExiste");
                            endif;
                        endif;

                        //COMPRUEBO QUE HAYA STOCK SUFICIENTE DE LOS COMPONENTES EN PROVEEDOR
                        if ($rowMatUbiComponentes->STOCK_TOTAL < $rowMovSalLinea->CANTIDAD):
                            if ($mostrar_error == 'texto'):
                                $arr_error['ERROR'] = $auxiliar->traduce("No existe el material a recepcionar en el proveedor", $administrador->ID_IDIOMA) . ".<br /><br />$strError";

                                return $arr_error;
                            else:
                                $html->PagError("StockComponentesInsuficienteEnProveedor");
                            endif;
                        endif;

                    endwhile;

                endif;
                //FIN BUSCO EL NUMERO DE MOVIMIENTOS PROCESADOS CON EL MISMO NUMERO DE LINEA DE PEDIDO

            endif;
            //FIN ACCIONES SI EL PEDIDO NO ES DE RECEPCION

        endwhile; //FIN BUCLE LINEAS

        // LOG MOVIMIENTOS.
        $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Mov. entrada", $idMovimiento, "Procesamiento asincrono");

        //BUSCO EL NUMERO DE LINEAS A TRANSMITIR A SAP
        $numOK = $bd->NumRegSTabla("MOVIMIENTO_ENTRADA_LINEA", "ID_MOVIMIENTO_ENTRADA = $rowMov->ID_MOVIMIENTO_ENTRADA AND LINEA_ANULADA = 0 AND BAJA = 0 AND (ID_TIPO_BLOQUEO IS NULL OR ID_TIPO_BLOQUEO = $rowTipoBloqueoPreventivo->ID_TIPO_BLOQUEO)");

        //ACTUALIZO LA FECHA PROCESADO DE LAS LINEAS DEL MOVIMIENTO A PROCESAR (SI TIENE CONTENEDOR ENTRANTE, AÑADIMOS LOS BLOQUEOS NECESARIOS)
        $sqlLineasProcesar    = "SELECT *
                            FROM MOVIMIENTO_ENTRADA_LINEA
                            WHERE ID_MOVIMIENTO_ENTRADA = '" . $idMovimiento . "' AND LINEA_ANULADA = 0 AND BAJA = 0";
        $resultLineasProcesar = $bd->ExecSQL($sqlLineasProcesar);

        //DEFINO UN CONTADOR PARA CONTAR EL NÚMERO DE LÍNEAS A PROCESAR
        $contadorLinea = 0;

        while ($rowLineasProcesar = $bd->SigReg($resultLineasProcesar)):
            //AUMENTO EL VALOR DEL CONTADOR
            $contadorLinea++;

            //BUSCO EL MATERIAL
            $GLOBALS["NotificaErrorPorEmail"] = "No";
            $rowMaterial                      = $bd->VerReg("MATERIAL", "ID_MATERIAL", $rowLineasProcesar->ID_MATERIAL, "No");
            //COMPRUEBO SI EL MATERIAL TIENE UNIDAD DE COMPRA Y SI EL USUARIO TIENE PERMISOS PARA EL TRATAMIENTO PARCIAL DE LA CC
            if ($rowMaterial->ID_UNIDAD_MEDIDA != $rowMaterial->ID_UNIDAD_COMPRA && $rowMaterial->DENOMINADOR_CONVERSION != 0 && $administrador->Hayar_Permiso_Perfil('ADM_ENTRADAS_TRATAMIENTO_PARCIAL_CC') < 2):
                //CANTIDAD DE COMPRA Y UNIDADES BASE Y COMPRA
                $cantidadCompra      = $mat->cantUnidadCompra($rowLineasProcesar->ID_MATERIAL, $rowLineasProcesar->CANTIDAD);
                $unidadesBaseyCompra = $mat->unidadBaseyCompra($rowLineasProcesar->ID_MATERIAL);

                // SE COMPRUEBA SI LA CANTIDAD DE COMPRA ES UN NÚMERO ENTERO
                if (fmod((float) $cantidadCompra, 1) !== 0.0):
                    $strErrorLinea    = $contadorLinea;
                    $strErrorCantidad = $rowLineasProcesar->CANTIDAD . " " . $unidadesBaseyCompra["unidadBase"] . " - " . $cantidadCompra . " " . $unidadesBaseyCompra["unidadCompra"];
                    if ($mostrar_error == 'texto'):
                        $arr_error['ERROR'] = $auxiliar->traduce("Linea", $administrador->ID_IDIOMA) . ": " . $strErrorLinea . ". " . $auxiliar->traduce("El usuario no tiene permitido recepcionar cantidades de compra no enteras", $administrador->ID_IDIOMA) . " ($strErrorCantidad).";

                        return $arr_error;
                    else:
                        $html->PagError("ErrorRecepcionTratamientoParcialCC");
                    endif;
                endif;
            endif;
        endwhile;
        //FIN ACTUALIZO LA FECHA PROCESADO DE LAS LINEAS DEL MOVIMIENTO A PROCESAR

        //SI EN SCS TODAS LAS OPERACIONES SE HAN REALIZADO CORRECTAMENTE Y NO HAY FECHA CONTABLE PARA EL INFORME DE FACTURACION LA ASIGNO
        if ($rowMov->FECHA_CONTABLE_INFORME_FACTURACION == '0000-00-00'):
            $sqlUpdate = "UPDATE MOVIMIENTO_ENTRADA SET FECHA_CONTABLE_INFORME_FACTURACION = '" . date("Y-m-d") . "' WHERE ID_MOVIMIENTO_ENTRADA = $rowMov->ID_MOVIMIENTO_ENTRADA";
            $bd->ExecSQL($sqlUpdate);
        endif;

        if ($numOK > 0):

            $resultado = $sap->EnvioMovimientoEntradaProcesadoPendienteConfirmacionSAP($idMovimiento);

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

                if ($mostrar_error == 'texto'):
                    $arr_error['ERROR'] = "We have received the following errors from SAP/Se han producido los siguientes errores en el intercambio de información con SAP" . ": " . '<br>' . $strError;

                    return $arr_error;
                else:
                    $html->PagError("ErrorSAP");
                endif;
            endif;
        endif;

        //GENERAMOS EL ARRAY DE RESPUESTA
        $arrDevuelto = array();

        return $arrDevuelto;
    }


    /**
     * @param $idMovimientoEntrada
     * $arr_extra TRAE INFORMACION DE OBJETOS A SER TRATADOS:
     *   - 'llamarLiberarCantidadPedidoConocido' => NOS INDICA SI HAY QUE LIBERAR CANTIDAD PEDIDO CONOCIDO
     *   - 'arrNecesidadesAsociadasLineaRecepcion' => ARRAY CON NECESIDADES
     *   - 'arrLineasPedidoAsociadaLineaRecepcion' => ARRAY CON LINEAS QUE NECESITAN UN AVISO SOLPED
     *   - 'arrayLineasPedidosInvolucradas' => ARRAY CON LINEAS INVOLUCRADAS PARA LLAMAR A CONTROL DE BLOQUEO
     *   - 'arrCambioReferencia' => ARRAY CON CAMBIOS DE REFERENCIA
     * $mostrar_error SI EL VALOR ES 'texto' EN VEZ DE SALIR PAGINA DE ERROR SE OBTENDRA UN TEXTO
     * REALIZA LAS ACCIONES QUE POR LA LOGICA DEL PROCESO SE EJECUTAN DESPUES DE RECEPCIONAR EL MOVIMIENTO DE ENTRADA
     */
    function RealizarAccionesPostRecepcion($idMovimiento, $arr_extra = array(), $mostrar_error = "")
    {
        global $bd;
        global $administrador;
        global $auxiliar;
        global $sap;
        global $html;
        global $incidencia_sistema;
        global $orden_transporte;
        global $importe;
        global $necesidad;
        global $reserva;
        global $aviso;
        global $mat;
        global $pedido;

        //BUSCO EL MOVIMIENTO
        $rowMov = $bd->VerReg("MOVIMIENTO_ENTRADA", "ID_MOVIMIENTO_ENTRADA", $idMovimiento);

        //BUSCO LA RECEPCION
        $rowDoc = $bd->VerReg("MOVIMIENTO_RECEPCION", "ID_MOVIMIENTO_RECEPCION", $rowMov->ID_MOVIMIENTO_RECEPCION);

        //ARRAY DEVOLVER
        $arr_devolver = array();

        //DATOS QUE NOS VIENEN DEL PROCESAMIENTO DE LA RECEPCION
        $llamarLiberarCantidadPedidoConocido = true;
        if (isset($arr_extra['llamarLiberarCantidadPedidoConocido'])):
            $llamarLiberarCantidadPedidoConocido = $arr_extra['llamarLiberarCantidadPedidoConocido'];
        endif;
        $arrNecesidadesAsociadasLineaRecepcion = array();
        if (isset($arr_extra['arrNecesidadesAsociadasLineaRecepcion']) && (is_array($arr_extra['arrNecesidadesAsociadasLineaRecepcion']))):
            $arrNecesidadesAsociadasLineaRecepcion = $arr_extra['arrNecesidadesAsociadasLineaRecepcion'];
        endif;
        $arrLineasPedidoAsociadaLineaRecepcion = array();
        if (isset($arr_extra['arrLineasPedidoAsociadaLineaRecepcion']) && (is_array($arr_extra['arrLineasPedidoAsociadaLineaRecepcion']))):
            $arrLineasPedidoAsociadaLineaRecepcion = $arr_extra['arrLineasPedidoAsociadaLineaRecepcion'];
        endif;
        $arrCambioReferencia = array();
        if (isset($arr_extra['arrCambioReferencia']) && (is_array($arr_extra['arrCambioReferencia']))):
            $arrCambioReferencia = $arr_extra['arrCambioReferencia'];
        endif;
        $arrayLineasPedidosInvolucradas = array();
        if (isset($arr_extra['arrayLineasPedidosInvolucradas']) && (is_array($arr_extra['arrayLineasPedidosInvolucradas']))):
            $arrayLineasPedidosInvolucradas = $arr_extra['arrayLineasPedidosInvolucradas'];
        endif;

        //ARRAY PARA GUARDAR LAS LINEAS QUE HAN NECESITADO SPLIT
        $arrayLineasSplit = array();

        //ARRAY PARA GUARDAR LOS PEDIDO ZTLI CON EL IMPORTE CORRESPONDIENTE DE LA LINEA
        $arrPedidoZTLIPendienteTransmitir = array();

        //ARRAY PARA GUARDAR LOS PEDIDO ZTLI ORIGINALES CON LA LINEA ANULADA
        $arrPedidoZTLIOriginalPendienteTransmitir = array();

        //SI HAY QUE LLAMAR A LA FUNCION liberarCantidadPedidoConocido LA LLAMO MAS ABAJO YA QUE INCLUYE LLAMADAS A SAP
        if ($llamarLiberarCantidadPedidoConocido == true):
            //INICIO TRANSACCION
            $bd->begin_transaction();

            //CALCULO LAS LINEAS NO RECEPCIONADAS QUE TEORICAMNTE IBAN A RECEPCIONARSE
            $arrayLineasNoRecepcionadas = $this->lineasNoRecepcionadas($rowMov->ID_MOVIMIENTO_RECEPCION);

            //RECORRO EL ARRAY DE LINEAS QUE HAN GENERADO SPLIT, SI ES SPLIT CERO ENVIARE A SAP UN ZTLI CON EL IMPORTE CORRESPONDIENTE DE LA LINEA
            if (count( (array)$arrayLineasNoRecepcionadas) > 0):
                foreach ($arrayLineasNoRecepcionadas as $clave => $idPedidoEntradaLinea):
                    //BUSCO LA LINEA DEL PEDIDO DE ENTRADA
                    $rowPedidoLinea = $bd->VerReg("PEDIDO_ENTRADA_LINEA", "ID_PEDIDO_ENTRADA_LINEA", $idPedidoEntradaLinea, "No");

                    //BUSCO SI LA LINEA ESTA ASOCIADA A UNA ORDEN DE TRANSPORTE DE MODELO DE TRANSPORTE 'Segundo'
                    $GLOBALS["NotificaErrorPorEmail"] = "No";
                    $rowOrdenTransporte               = $bd->VerReg("ORDEN_TRANSPORTE", "ID_ORDEN_TRANSPORTE", $rowDoc->ID_ORDEN_TRANSPORTE, "No");
                    unset($GLOBALS["NotificaErrorPorEmail"]);
                    if (($rowOrdenTransporte != false) && ($rowOrdenTransporte->MODELO_TRANSPORTE == 'Segundo')):

                        //BUSCO LOS PEDIDOS ZTLI ORIGINALES RELACIONADOS CON LA LINEA DE PEDIDO
                        $sqlPedidoServiciosLineas    = "SELECT PE.ID_ORDEN_CONTRATACION, PE.ID_PEDIDO_ENTRADA, PEL.ID_PEDIDO_ENTRADA_LINEA, PEL.IMPORTE_TRANSMITIDO_A_SAP, PEL.ID_MATERIAL, PEL.UNIDAD_SAP,
PEL.ID_MOVIMIENTO_SALIDA_LINEA_RELACIONADA_MUEVE_MATERIAL, PEL.ID_PEDIDO_ENTRADA_LINEA_RELACIONADA_RECEPCIONA_MATERIAL, PEL.ID_MOVIMIENTO_ENTRADA_LINEA_RELACIONADA_RECEPCIONA_MATERIAL, PEL.ID_ELEMENTO_IMPUTACION, PEL.CODIGO_SELECCIONADO_ELEMENTO_IMPUTACION, PEL.ID_ORDEN_TRABAJO_RELACIONADO, PEL.CANTIDAD_SAP, PEL.CANTIDAD, PEL.CANTIDAD_PDTE, PEL.NUMERO_CONTRATO, PEL.NUMERO_CONTRATO_LINEA 
                                                     FROM PEDIDO_ENTRADA_LINEA PEL 
                                                     INNER JOIN PEDIDO_ENTRADA PE ON PE.ID_PEDIDO_ENTRADA = PEL.ID_PEDIDO_ENTRADA 
                                                     WHERE PE.TIPO_PEDIDO = 'Servicios' AND PEL.ID_PEDIDO_ENTRADA_LINEA_RELACIONADA_RECEPCIONA_MATERIAL = $rowPedidoLinea->ID_PEDIDO_ENTRADA_LINEA AND PEL.INDICADOR_BORRADO IS NULL AND PEL.BAJA = 0";
                        $resultPedidoServiciosLineas = $bd->ExecSQL($sqlPedidoServiciosLineas);
                        while ($rowPedidoServiciosLinea = $bd->SigReg($resultPedidoServiciosLineas)):
                            //DOY DE BAJA LA LINEA DEL PEDIDO DE SERVICIOS
                            $sqlUpdate = "UPDATE PEDIDO_ENTRADA_LINEA SET 
                                          INDICADOR_BORRADO = 'L', 
                                          ACTIVA = 0 
                                          WHERE ID_PEDIDO_ENTRADA_LINEA = $rowPedidoServiciosLinea->ID_PEDIDO_ENTRADA_LINEA";
                            $bd->ExecSQL($sqlUpdate);

                            //AÑADO EL PEDIDO AL ARRAY DE PEDIDOS ZTLI ORIGINALES A RETRANSMITIR
                            $arrPedidoZTLIOriginalPendienteTransmitir[] = $rowPedidoServiciosLinea->ID_PEDIDO_ENTRADA;

                            //SE COMPRUEBA SI YA EXISTE UN PEDIDO ZTLI FICTICIO
                            $rowOrdenContratacion = $bd->VerReg("ORDEN_CONTRATACION", "ID_ORDEN_CONTRATACION", $rowPedidoServiciosLinea->ID_ORDEN_CONTRATACION);

                            $rowOrdenTransporte = $bd->VerReg("ORDEN_TRANSPORTE", "ID_ORDEN_TRANSPORTE", $rowOrdenContratacion->ID_ORDEN_TRANSPORTE);

                            $numPedidosZTLI = $bd->NumRegsTabla("PEDIDO_ENTRADA", "TIPO_PEDIDO = 'Servicios' AND TIPO_PEDIDO_SAP = 'ZTLI' AND TIPO_ZTLI = 'Anulacion Linea Pedido Proveedor' AND ID_ORDEN_TRANSPORTE = $rowOrdenContratacion->ID_ORDEN_TRANSPORTE AND ID_ORDEN_CONTRATACION = $rowOrdenContratacion->ID_ORDEN_CONTRATACION AND BAJA = 0");
                            if ($numPedidosZTLI > 0):
                                $rowPedidoZTLI = $bd->VerRegRest("PEDIDO_ENTRADA", "TIPO_PEDIDO = 'Servicios' AND TIPO_PEDIDO_SAP = 'ZTLI' AND TIPO_ZTLI = 'Anulacion Linea Pedido Proveedor' AND ID_ORDEN_TRANSPORTE = $rowOrdenContratacion->ID_ORDEN_TRANSPORTE AND ID_ORDEN_CONTRATACION = $rowOrdenContratacion->ID_ORDEN_CONTRATACION AND BAJA = 0");

                                //BUSCO EL MAXIMO NUMERO DE LINEA
                                $UltimoNumeroLinea       = 0;
                                $sqlUltimoNumeroLinea    = "SELECT MAX(CAST(LINEA_PEDIDO_SAP AS UNSIGNED)) AS NUMERO_LINEA FROM PEDIDO_ENTRADA_LINEA WHERE ID_PEDIDO_ENTRADA = $rowPedidoZTLI->ID_PEDIDO_ENTRADA AND BAJA = 0";
                                $resultUltimoNumeroLinea = $bd->ExecSQL($sqlUltimoNumeroLinea);
                                if ($resultUltimoNumeroLinea != false):
                                    $rowUltimoNumeroLinea = $bd->SigReg($resultUltimoNumeroLinea);
                                    if ($rowUltimoNumeroLinea->NUMERO_LINEA != NULL):
                                        $UltimoNumeroLinea = $rowUltimoNumeroLinea->NUMERO_LINEA;
                                    endif;
                                endif;

                                //INCREMENTO EN 10 EL SIGUIENTE NUMERO DE LINEA
                                $SiguienteNumeroLinea = $UltimoNumeroLinea + 10;

                                //AÑADO EL ELEMENTO RELACIONADO CON EL MATERIAL DE TRANSPORTE
                                $whereLineaRelacionadaMaterial = "";
                                if ($rowPedidoServiciosLinea->ID_MOVIMIENTO_SALIDA_LINEA_RELACIONADA_MUEVE_MATERIAL != NULL):
                                    $whereLineaRelacionadaMaterial = ", ID_MOVIMIENTO_SALIDA_LINEA_RELACIONADA_MUEVE_MATERIAL = $rowPedidoServiciosLinea->ID_MOVIMIENTO_SALIDA_LINEA_RELACIONADA_MUEVE_MATERIAL ";
                                elseif ($rowPedidoServiciosLinea->ID_PEDIDO_ENTRADA_LINEA_RELACIONADA_RECEPCIONA_MATERIAL != NULL):
                                    $whereLineaRelacionadaMaterial = ", ID_PEDIDO_ENTRADA_LINEA_RELACIONADA_RECEPCIONA_MATERIAL = $rowPedidoServiciosLinea->ID_PEDIDO_ENTRADA_LINEA_RELACIONADA_RECEPCIONA_MATERIAL ";
                                elseif ($rowPedidoServiciosLinea->ID_MOVIMIENTO_ENTRADA_LINEA_RELACIONADA_RECEPCIONA_MATERIAL != NULL):
                                    $whereLineaRelacionadaMaterial = ", ID_MOVIMIENTO_ENTRADA_LINEA_RELACIONADA_RECEPCIONA_MATERIAL = $rowPedidoServiciosLinea->ID_MOVIMIENTO_ENTRADA_LINEA_RELACIONADA_RECEPCIONA_MATERIAL ";
                                elseif ($rowPedidoServiciosLinea->ID_ELEMENTO_IMPUTACION != NULL):
                                    $whereLineaRelacionadaMaterial = ", ID_ELEMENTO_IMPUTACION = $rowPedidoServiciosLinea->ID_ELEMENTO_IMPUTACION 
                                                                      , CODIGO_SELECCIONADO_ELEMENTO_IMPUTACION = '" . $rowPedidoServiciosLinea->CODIGO_SELECCIONADO_ELEMENTO_IMPUTACION . "'";
                                elseif ($rowPedidoServiciosLinea->ID_ORDEN_TRABAJO_RELACIONADO != NULL):
                                    $whereLineaRelacionadaMaterial = ", ID_ORDEN_TRABAJO_RELACIONADO = $rowPedidoServiciosLinea->ID_ORDEN_TRABAJO_RELACIONADO ";
                                endif;

                                //CREO LA LINEA DEL PEDIDO DE ENTRADA
                                $sqlInsert = "INSERT INTO PEDIDO_ENTRADA_LINEA SET
                                              ID_PEDIDO_ENTRADA = $rowPedidoZTLI->ID_PEDIDO_ENTRADA
                                              , ID_ALMACEN = NULL
                                              , ID_CENTRO = $rowOrdenTransporte->ID_CENTRO_CONTRATANTE
                                              , ID_MATERIAL = $rowPedidoServiciosLinea->ID_MATERIAL
                                              , ID_TIPO_BLOQUEO = NULL
                                              , UNIDAD_SAP = $rowPedidoServiciosLinea->UNIDAD_SAP
                                              , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                              , LINEA_PEDIDO_SAP = '" . str_pad( (string)$SiguienteNumeroLinea, 5, "0", STR_PAD_LEFT) . "'
                                              , CANTIDAD_SAP = $rowPedidoServiciosLinea->CANTIDAD_SAP 
                                              , CANTIDAD = $rowPedidoServiciosLinea->CANTIDAD 
                                              , CANTIDAD_PDTE = $rowPedidoServiciosLinea->CANTIDAD_PDTE 
                                              $whereLineaRelacionadaMaterial 
                                              , NUMERO_CONTRATO =  '" . $bd->escapeCondicional($rowPedidoServiciosLinea->NUMERO_CONTRATO) . "'
                                              , NUMERO_CONTRATO_LINEA = '" . $bd->escapeCondicional($rowPedidoServiciosLinea->NUMERO_CONTRATO_LINEA) . "'
                                              , FECHA_ENTREGA = '" . date("Y-m-d") . "'
                                              , IMPORTE_TRANSMITIDO_A_SAP = $rowPedidoServiciosLinea->IMPORTE_TRANSMITIDO_A_SAP
                                              , ID_MONEDA_TRANSMITIDA_A_SAP = $rowOrdenContratacion->ID_MONEDA
                                              , ESTADO = 'Sin Recepcionar' 
                                              , ACTIVA = 1 
                                              , TIPO_IMPUTACION = 'O' 
                                              , ID_LINEA_ZTLI_ORIGINAL = $rowPedidoServiciosLinea->ID_PEDIDO_ENTRADA_LINEA";
                                $bd->ExecSQL($sqlInsert);

                                $idPedidoServicios = $rowPedidoZTLI->ID_PEDIDO_ENTRADA;

                            else:

                                //CREAMOS EL PEDIDO ZTLI FICTICIO
                                $idPedidoServicios = $orden_transporte->generarZTLIAnulacionLineaPedidoZTLI($rowPedidoLinea->ID_PEDIDO_ENTRADA_LINEA, $rowPedidoServiciosLinea->ID_PEDIDO_ENTRADA_LINEA);

                            endif;

                            //AÑADO EL PEDIDO AL ARRAY DE PEDIDOS A TRANSMITIR A SAP
                            $arrPedidoZTLIPendienteTransmitir[] = $idPedidoServicios;
                        endwhile;
                    endif;
                    //FIN BUSCO SI LA LINEA ESTA ASOCIADA A UNA ORDEN DE TRANSPORTE DE MODELO DE TRANSPORTE 'Segundo'
                endforeach;
            endif;
            //FIN RECORRO EL ARRAY DE LINEAS QUE HAN GENERADO SPLIT, SI ES SPLIT CERO ENVIARE A SAP UN ZTLI CON EL IMPORTE CORRESPONDIENTE DE LA LINEA

            //FINALIZO LA TRANSACCION
            $bd->commit_transaction();


            //INICIO TRANSACCION
            $bd->begin_transaction();

            //ADEMAS LIBERAMOS POSIBLES PEDIDOS CONOCIDOS NO RECEPCIONADOS (hay llamada a SAP)
            $arrayLineasSplit = $this->liberarCantidadPedidoConocido($rowMov->ID_MOVIMIENTO_RECEPCION, $mostrar_error);

            //SI DEVUELVE ERROR (si mostrar_texto = texto)
            if (isset($arrayLineasSplit['ERROR']) && ($arrayLineasSplit['ERROR'] != '')):
                $arr_devolver['ERRORES'] = $arrayLineasSplit['ERROR'];

                return $arr_devolver;
            endif;

            //RECORRO EL ARRAY DE LINEAS QUE HAN GENERADO SPLIT, SI ES SPLIT CERO ENVIARE A SAP UN ZTLI CON EL IMPORTE CORRESPONDIENTE DE LA LINEA
            if (count( (array)$arrayLineasSplit) > 0):
                foreach ($arrayLineasSplit as $idPedidoEntradaLinea => $cantidadSplit):
                    //BUSCO LA LINEA DEL PEDIDO DE ENTRADA
                    $rowPedidoLinea = $bd->VerReg("PEDIDO_ENTRADA_LINEA", "ID_PEDIDO_ENTRADA_LINEA", $idPedidoEntradaLinea, "No");

                    //BUSCO EL MATERIAL
                    $rowMaterial = $bd->VerReg("MATERIAL", "ID_MATERIAL", $rowPedidoLinea->ID_MATERIAL, "No");

                    //SI EL SPLIT ES POR CERO, Y EL MATERIAL TIENE PESO MANDAREMOS A SAP UN ZTLI CON EL IMPORTE DE LA LINEA QUE SE HA REALIZADO SPLIT
                    if (($cantidadSplit == 0) && ($rowMaterial->PESO_BRUTO > 0)):

                        //BUSCO SI LA LINEA ESTA ASOCIADA A UNA ORDEN DE TRANSPORTE DE MODELO DE TRANSPORTE 'Primero'
                        $GLOBALS["NotificaErrorPorEmail"] = "No";
                        $rowOrdenTransporte               = $bd->VerReg("ORDEN_TRANSPORTE", "ID_ORDEN_TRANSPORTE", $rowDoc->ID_ORDEN_TRANSPORTE, "No");
                        unset($GLOBALS["NotificaErrorPorEmail"]);
                        if (($rowOrdenTransporte != false) && ($rowOrdenTransporte->MODELO_TRANSPORTE == 'Primero')):

                            //BUSCAMOS CENTRO CONTRATANTE
                            $rowCentroContratante = false;
                            if ($rowOrdenTransporte->ID_CENTRO_CONTRATANTE != NULL):
                                $rowCentroContratante = $bd->VerReg("CENTRO", "ID_CENTRO", $rowOrdenTransporte->ID_CENTRO_CONTRATANTE);
                            endif;

                            //BUSCAMOS CENTRO DEL PEDIDO
                            $rowAlmacenDestino = $bd->VerReg("ALMACEN", "ID_ALMACEN", $rowPedidoLinea->ID_ALMACEN);
                            $rowCentroDestino  = $bd->VerReg("CENTRO", "ID_CENTRO", $rowAlmacenDestino->ID_CENTRO);
                            //SI LA SOCIEDAD DESTINO COINCIDE CON LA CONTRATANTE, ENVIAMOS ZTLI
                            if ($rowCentroDestino->ID_SOCIEDAD == $rowCentroContratante->ID_SOCIEDAD):

                                //BUSCO LAS ORDENES DE CONTRATACION DE LA ORDEN DE TRANSPORTE
                                $sqlOrdenesContratacion    = "SELECT *
                                                            FROM ORDEN_CONTRATACION
                                                            WHERE ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND ESTADO = 'Aceptada' AND BAJA = 0";
                                $resultOrdenesContratacion = $bd->ExecSQL($sqlOrdenesContratacion);
                                while ($rowOrdenContratacion = $bd->SigReg($resultOrdenesContratacion)): //RECORRO LAS CONTRATACIONES PARA TRANMITIR CADA PEDIDO ZTLI

                                    //CALCULO EL IMPORTE DEL PEDIDO ZTLI FICTICIO
                                    $importePedidoZTLI = 0;

                                    //BUSCO LA EXPEDICION PEDIDO CONOCIDO
                                    $rowPedidoConocido = $bd->VerRegRest("EXPEDICION_PEDIDO_CONOCIDO EPC INNER JOIN EXPEDICION E ON E.ID_EXPEDICION = EPC.ID_EXPEDICION", "E.ID_ORDEN_TRANSPORTE = $rowDoc->ID_ORDEN_TRANSPORTE AND EPC.ID_PEDIDO_ENTRADA_LINEA = $rowPedidoLinea->ID_PEDIDO_ENTRADA_LINEA", "No");

                                    //SI EXISTE EXPEDICION PEDIDO CONOCIDO
                                    if ($rowPedidoConocido != false):
                                        $rowImportePedidoZTLI = $bd->VerRegRest("ORDEN_TRANSPORTE_REPARTO_COSTES", "ID_ORDEN_TRANSPORTE = $rowOrdenTransporte->ID_ORDEN_TRANSPORTE AND ID_EXPEDICION_PEDIDO_CONOCIDO = $rowPedidoConocido->ID_EXPEDICION_PEDIDO_CONOCIDO");
                                        $importePedidoZTLI    = $rowImportePedidoZTLI->IMPORTE_SAP_SEGUN_SGA;

                                        //ACTUALIZO DATOS EN FUNCION DEL PORCENTAJE DE LA ORDEN DE CONTRATACION
                                        $importePedidoZTLI = $importePedidoZTLI * $importe->getPorcentajeImporteContratacionRespectoOrdenTransporte($rowOrdenContratacion->ID_ORDEN_CONTRATACION, $rowOrdenTransporte->ID_ORDEN_TRANSPORTE);

                                        //CREAMOS EL PEDIDO ZTLI FICTICIO
                                        $idPedidoServicios = $orden_transporte->generarZTLIFicticioAnulacionLineaPedidoProveedor($rowPedidoLinea->ID_PEDIDO_ENTRADA_LINEA, $rowOrdenContratacion->ID_ORDEN_CONTRATACION, $importePedidoZTLI, $rowPedidoLinea->CANTIDAD);

                                        //AÑADO EL PEDIDO AL ARRAY DE PEDIDOS A TRANSMITIR A SAP
                                        $arrPedidoZTLIPendienteTransmitir[] = $idPedidoServicios;
                                    endif;
                                    //FIN SI EXISTE EXPEDICION PEDIDO CONOCIDO
                                endwhile;
                                //FIN RECORRO LAS CONTRATACIONES PARA TRANMITIR CADA PEDIDO ZTLI
                            endif;
                            //FIN SI LA SOCIEDAD DESTINO COINCIDE CON LA CONTRATANTE, ENVIAMOS ZTLI
                        endif;
                        //FIN BUSCO SI LA LINEA ESTA ASOCIADA A UNA ORDEN DE TRANSPORTE DE MODELO DE TRANSPORTE 'Primero'
                    endif;
                    //FIN SI EL SPLIT ES POR CERO, Y EL MATERIAL TIENE PESO MANDAREMOS A SAP UN ZTLI CON EL IMPORTE DE LA LINEA QUE SE HA REALIZADO SPLIT
                endforeach;
            endif;
            //FIN RECORRO EL ARRAY DE LINEAS QUE HAN GENERADO SPLIT, SI ES SPLIT CERO ENVIARE A SAP UN ZTLI CON EL IMPORTE CORRESPONDIENTE DE LA LINEA

            //FINALIZO LA TRANSACCION
            $bd->commit_transaction();
        endif;
        //FIN SI HAY QUE LLAMAR A LA FUNCION liberarCantidadPedidoConocido la llamo (incluye llamadas a SAP)

        //SI ESTA RELLENO EL ARRAY DE PEDIDOS ZTLI ORIGINALES
        if (count( (array)$arrPedidoZTLIOriginalPendienteTransmitir) > 0):
            //ME QUEDO CON LOS VALORES UNICOS
            $arrPedidoZTLIOriginalPendienteTransmitir = array_unique( (array)$arrPedidoZTLIOriginalPendienteTransmitir);

            //UNIFICO ESTE ARRAY CON EL DE LOS NUEVOS ZTLI A TRANSMITIR
            $arrPedidoZTLIPendienteTransmitir = array_merge((array)$arrPedidoZTLIPendienteTransmitir, $arrPedidoZTLIOriginalPendienteTransmitir);
        endif;

        //ENVIO LOS PEDIDOS DE SERVICIOS PENDIENTES DE TRANSMITIR A SAP
        foreach ($arrPedidoZTLIPendienteTransmitir as $idPedidoServicios):
            //BUSCAMOS EL PEDIDO DE SERVICIOS
            $rowPedidoServicios = $bd->VerReg("PEDIDO_ENTRADA", "ID_PEDIDO_ENTRADA", $idPedidoServicios);

            //INICIO LA TRANSACCION
            $bd->begin_transaction();

            //ENVIO A SAP EL PEDIDO TRANSPORTE
            $resultado = $sap->InformarSAPPedidoServicios($rowPedidoServicios->ID_PEDIDO_ENTRADA);
            if ($resultado['RESULTADO'] != 'OK'): //No se ha podido grabar la información en SAP
                foreach ($resultado['ERRORES'] as $arr):
                    foreach ($arr as $mensaje_error):
                        $strError = $strError . $mensaje_error . "<br>";
                    endforeach;
                endforeach;

                //DESHAGO LA TRANSACCION DE EXPEDICION Y GASTOS
                $bd->rollback_transaction();

                //GRABO EL ERROR PRODUCIDO EN BASE DE DATOS
                $sap->InsertarErrores($resultado);

                //BUSCO EL TIPO DE INCIDENCIA SISTEMA 'ZTL Pendiente Transmitir'
                $rowISTipo    = $bd->VerReg("INCIDENCIA_SISTEMA_TIPO", "INCIDENCIA_SISTEMA_TIPO", "ZTL Pendiente Transmitir");
                $rowISSubTipo = $bd->VerReg("INCIDENCIA_SISTEMA_SUBTIPO", "INCIDENCIA_SISTEMA_SUBTIPO", "ZTLI");

                //EXTRAIGO EL IDENTIFICADOR DEL WEB SERVICE
                $idWebService = $resultado['LOG_ERROR']['EXTERNALREFID'];

                //GRABO LA INCIDENCIA DE SISTEMA DEL TIME OUT
                $sqlInsert = "INSERT INTO INCIDENCIA_SISTEMA SET
                              ID_INCIDENCIA_SISTEMA_TIPO = $rowISTipo->ID_INCIDENCIA_SISTEMA_TIPO
                              , TIPO = 'ZTL Pendiente Transmitir'
                              , ID_INCIDENCIA_SISTEMA_SUBTIPO = $rowISSubTipo->ID_INCIDENCIA_SISTEMA_SUBTIPO
                              , SUBTIPO = 'ZTLI'
                              , ESTADO = 'Creada'
                              , TABLA_OBJETO = 'PEDIDO_ENTRADA'
                              , ID_OBJETO = $rowPedidoServicios->ID_PEDIDO_ENTRADA
                              , FECHA_CREACION = '" . date("Y-m-d H:i:s") . "'
                              , FECHA_RESOLUCION = '0000-00-00 00:00:00'
                              , ID_ADMINISTRADOR_CREACION = $administrador->ID_ADMINISTRADOR
                              , ID_ADMINISTRADOR_ULTIMA_MODIFICACION = $administrador->ID_ADMINISTRADOR
                              , ID_LOG_EJECUCION_WS = $idWebService
                              , OBSERVACIONES = ''";
                $bd->ExecSQL($sqlInsert);

            else:

                if ($resultado['PEDIDO'] != ""):

                    $sqlUpdate = "UPDATE PEDIDO_ENTRADA_LINEA SET ENVIADO_SAP = 1 WHERE ID_PEDIDO_ENTRADA = $rowPedidoServicios->ID_PEDIDO_ENTRADA AND BAJA = 0";
                    $bd->ExecSQL($sqlUpdate);

                    $sqlUpdate = "UPDATE PEDIDO_ENTRADA SET
                                PEDIDO_SAP = '" . $resultado['PEDIDO'] . "'
                              , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                              WHERE ID_PEDIDO_ENTRADA = $rowPedidoServicios->ID_PEDIDO_ENTRADA";
                    $bd->ExecSQL($sqlUpdate);
                endif;

            endif;
            //FIN ENVIO A SAP EL PEDIDO TRANSPORTE

            //FINALIZO LA TRANSACCION
            $bd->commit_transaction();

        endforeach;
        //FIN ENVIO LOS PEDIDOS DE SERVICIOS PENDIENTES DE TRANSMITIR A SAP

        //ENVIO LAS NECESIDADES ASOCIADAS A LA LINEA RECEPCIONADA
        if (count( (array)$arrNecesidadesAsociadasLineaRecepcion) > 0):
            foreach ($arrNecesidadesAsociadasLineaRecepcion as $idNecesidad):
                //INICIO TRANSACCION
                $bd->begin_transaction();

                //TODO: CORREGIR ESE $txCantidadOk, ESTABA MAL DE ANTES (NO ESTA DEFINIDA ESA VARIABLE)
                $necesidad->EnviarNotificacionEmail_RecepcionEnAlmacenNecesidadProveedor($idNecesidad, $txCantidadOk);

                //FINALIZO LA TRANSACCION
                $bd->commit_transaction();
            endforeach;
        endif;

        //ENVIO INFORME DE LOS PEDIDOS CON ALMACEN DESTINO DISTINTO AL DE SOLPED A LA LINEA RECEPCIONADA
        if (count( (array)$arrLineasPedidoAsociadaLineaRecepcion) > 0):
            //INICIO TRANSACCION
            $bd->begin_transaction();

            $aviso->envioAvisoAlmacenSolpedDistinto($arrLineasPedidoAsociadaLineaRecepcion, $idMovimiento);

            //FINALIZO LA TRANSACCION
            $bd->commit_transaction();
        endif;

        //AVISOS TRASLADOS
        $sqlLineasProcesar    = "SELECT ID_MOVIMIENTO_ENTRADA_LINEA,ID_UBICACION,ID_MATERIAL, ID_TIPO_BLOQUEO, CANTIDAD
                            FROM MOVIMIENTO_ENTRADA_LINEA
                            WHERE ID_MOVIMIENTO_ENTRADA = " . $rowMov->ID_MOVIMIENTO_ENTRADA . " AND LINEA_ANULADA = 0 AND BAJA = 0";
        $resultLineasProcesar = $bd->ExecSQL($sqlLineasProcesar);
        while ($rowLineasProcesar = $bd->SigReg($resultLineasProcesar)):
            //INICIO TRANSACCION
            $bd->begin_transaction();

            //OBTENEMOS EL TIPO DE BLOQUEO PREVENTIVO
            $rowTipoBloqueoPreventivo = $bd->VerRegRest("TIPO_BLOQUEO", "TIPO_BLOQUEO = 'P' AND BAJA = 0", "No");

            if (($rowLineasProcesar->ID_TIPO_BLOQUEO == NULL) || ($rowLineasProcesar->ID_TIPO_BLOQUEO == $rowTipoBloqueoPreventivo->ID_TIPO_BLOQUEO)):
                //SI EL TIPO DE BLOQUEO ES OK O PREVENTIVO, ENVIAMOS NOTIFICACION
                $rowUbic = $bd->VerReg("UBICACION", "ID_UBICACION", $rowLineasProcesar->ID_UBICACION);
                $necesidad->EnviarNotificacionEmail_RecepcionEnAlmacenOrigenDePedidoTraslado($rowLineasProcesar->ID_MATERIAL, $rowUbic->ID_ALMACEN, $rowLineasProcesar->CANTIDAD);
            endif;

            //FINALIZO LA TRANSACCION
            $bd->commit_transaction();
        endwhile;

        //SI TIENE CAMBIOS REFERENCIA
        if (count( (array)$arrCambioReferencia) > 0):
            $cambiosReferenciaOK   = 0;
            $cambiosReferenciaKO   = 0;
            $listaCambioReferencia = "";
            foreach ($arrCambioReferencia as $idCambioReferencia):
                //INICIO TRANSACCION
                $bd->begin_transaction();

                //SI PUEDO REALIZAR EL CAMBIO DE REFERENCIA
                $idMaterialUbicacionCR = $mat->realizarCambioReferenciaPendiente($idCambioReferencia);
                if ($idMaterialUbicacionCR != false):

                    //INFORMO A SAP DEL CONTEO
                    $resultado = $sap->AjusteCambioReferencia($idCambioReferencia);

                    if ($resultado['RESULTADO'] != 'OK'): //No se ha podido grabar la información en SAP
                        //DESHAGO LA TRANSACCION
                        $bd->rollback_transaction();

                        //GRABO EL ERROR PRODUCIDO EN BASE DE DATOS
                        $sap->InsertarErrores($resultado);

                        $cambiosReferenciaKO++;
                    else:
                        //GUARDAMOS EL MATERIAL UBICACION FINAL
                        $listaCambioReferencia .= ($listaCambioReferencia != "" ? ", " : "") . $idCambioReferencia;
                        $cambiosReferenciaOK++;
                    endif;
                else:
                    $cambiosReferenciaKO++;
                endif;

                //FINALIZO LA TRANSACCION
                $bd->commit_transaction();
            endforeach;
            $arr_devolver['listaCambioReferencia'] = $listaCambioReferencia;
        endif;

        //INICIO TRANSACCION
        $bd->begin_transaction();


        if (($rowMov->TIPO_MOVIMIENTO == 'PedidoProveedor') || ($rowMov->TIPO_MOVIMIENTO == 'PedidoDevolucionVenta') || ($rowMov->TIPO_MOVIMIENTO == 'PedidoCompraSGAManual')):
            //INFORMO A SAP DE LAS LINEAS BLOQUEADAS
            $resultado = $pedido->controlBloqueoLinea("Entrada", 'Procesar', implode(",", (array) $arrayLineasPedidosInvolucradas));
            if ($resultado['RESULTADO'] != 'OK'): //No se ha podido grabar la información en SAP
                if (count( (array)$resultado['ERRORES']) > 0):
                    foreach ($resultado['ERRORES'] as $arr):
                        foreach ($arr as $mensaje_error):
                            $strError = $strError . $mensaje_error . "<br>";
                        endforeach;
                    endforeach;
                endif;

                $bd->rollback_transaction();

                //GRABO EL ERROR PRODUCIDO EN BASE DE DATOS
                $sap->InsertarErrores($resultado);
            endif;
        endif;


        //FINALIZO LA TRANSACCION
        $bd->commit_transaction();


        return $arr_devolver;

    }

    /**
     * @param $idMovimientoEntrada
     * $mostrar_error SI EL VALOR ES 'texto' EN VEZ DE SALIR PAGINA DE ERROR SE OBTENDRA UN TEXTO
     * HACE LAS COMPROBACIONES NECESARIAS Y LLAMA A LA INTERFAZ ASINCRONA DE RECEPCIONES
     */
    function AnulacionEntradaLineaMaterialAsincrona($idMovimiento, $listaIdsLineasMovimiento, $anulacion_stock = "Si", $mostrar_error = "")
    {
        global $bd;
        global $administrador;
        global $auxiliar;
        global $sap;
        global $html;
        global $mat;
        global $ubicacion;

        //ARRAY ERRORES
        $arr_error = array();


        //BUSCO EL TIPO BLOQUEO = CCNP (Control de Calidad de material no preventivo)
        $rowBloqueoCCNP = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "CCNP", "No");

        //BUSCO EL TIPO BLOQUEO = CCP (Control de Calidad de material preventivo)
        $rowBloqueoCCP = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "CCP", "No");

        //BUSCO EL TIPO DE BLOQUEO PREVENTIVO
        $rowTipoBloqueoPreventivo = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "SP", "No");

        //BUSCO LOS TIPOS DE BLOQUEO PDTE CALIDAD Y DC, DEPENDIENDO DE LA DECISION DE CALIDAD PARA LOTES DE FABRICACION SE PUEDEN RECEPCIONAR ASI
        $rowTipoBloqueoPdteCalidad      = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "PC", "No");
        $rowTipoBloqueoRechazadoCalidad = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "SPDPC", "No");

        if ($rowBloqueoCCNP == false || $rowBloqueoCCP == false || $rowTipoBloqueoPreventivo == false || $rowTipoBloqueoPdteCalidad == false || $rowTipoBloqueoRechazadoCalidad == false):
            if ($mostrar_error == 'texto'):
                $arr_error['ERROR'] = $auxiliar->traduce("El Tipo de Bloqueo no existe", $administrador->ID_IDIOMA);

                return $arr_error;
            else:
                $html->PagError("TipoBloqueoNoExiste");
            endif;
        endif;


        //BLOQUEAMOS EL MOVIMIENTO DE ENTRADA
        $sqlMov    = "SELECT * FROM MOVIMIENTO_ENTRADA WHERE ID_MOVIMIENTO_ENTRADA = $idMovimiento FOR UPDATE";
        $resultMov = $bd->ExecSQL($sqlMov);
        $rowMov    = $bd->SigReg($resultMov);

        //BUSCO LA RECEPCION
        $rowRecepcion = $bd->VerReg("MOVIMIENTO_RECEPCION", "ID_MOVIMIENTO_RECEPCION", $rowMov->ID_MOVIMIENTO_RECEPCION);

        //COMPRUEBO QUE NO ESTE DADO DE BAJA
        if ($rowMov->BAJA == 1):
            if ($mostrar_error == 'texto'):
                $arr_error['ERROR'] = $auxiliar->traduce("No se puede realizar la operación correspondiente porque el movimiento está dado de baja", $administrador->ID_IDIOMA);

                return $arr_error;
            else:
                $html->PagError("MovimientoBaja");
            endif;
        endif;

        //COMPRUEBO QUE EL TIPO DE MOVIMIENTO ADMITE ASINCRONISMO
        if ($rowMov->TIPO_MOVIMIENTO == 'PedidoDevolucionVenta' || $rowMov->TIPO_MOVIMIENTO == 'DevolucionOM' || $rowMov->TIPO_MOVIMIENTO == 'Construccion'):
            if ($mostrar_error == 'texto'):
                $arr_error['ERROR'] = $auxiliar->traduce("El tipo del movimiento de entrada es incorrecto para realizar la operacion correspondiente", $administrador->ID_IDIOMA);

                return $arr_error;
            else:
                $html->PagError("TipoMovimientoIncorrecto");
            endif;
        endif;

        // 1. COMPRUEBO QUE SE ESTA EN EL ESTADO CORRECTO
        $posibleAnular = false;
        if (
            ((($rowMov->ESTADO == "Procesado") || ($rowMov->ESTADO == "En Ubicacion") || ($rowMov->ESTADO == "Ubicado") || ($rowMov->ESTADO == "Escaneado y Finalizado")) && ($rowRecepcion->VIA_RECEPCION == 'PDA')) ||
            ((($rowMov->ESTADO == "Ubicado") || ($rowMov->ESTADO == "Escaneado y Finalizado")) && ($rowRecepcion->VIA_RECEPCION == 'WEB'))
        ):
            $posibleAnular = true;
        endif;
        if ($posibleAnular == false):
            if ($mostrar_error == 'texto'):
                $arr_error['ERROR'] = $auxiliar->traduce("No es posible realizar esta acción en el estado actual del Movimiento", $administrador->ID_IDIOMA);

                return $arr_error;
            else:
                $html->PagError("AccionEnEstadoIncorrecto");
            endif;
        endif;


        // 2. COMPRUEBO QUE TENGA LINEAS
        $clausulaWhere = "ID_MOVIMIENTO_ENTRADA = $idMovimiento AND ID_MOVIMIENTO_ENTRADA_LINEA IN (" . $listaIdsLineasMovimiento . ")";
        $numLineas     = $bd->NumRegsTabla("MOVIMIENTO_ENTRADA_LINEA", $clausulaWhere);
        if ($numLineas == 0):
            if ($mostrar_error == 'texto'):
                $arr_error['ERROR'] = $auxiliar->traduce("El movimiento no tiene articulos", $administrador->ID_IDIOMA);

                return $arr_error;
            else:
                $html->PagError("SinLineas");
            endif;
        endif;

        //EL USUARIO PODRA PROCESAR EL MOVIMIENTO SI TIENE PERMISO DE ESCRITURA PARA EL ALMACEN DESTINO DE TODAS SUS LINEAS
        //AÑADO SEGURIDAD DE ACCESO DE ALMACENES
        if ($administrador->esRestringidoPorZonas()):
            $joinAlmacenPermisosZonas  = " INNER JOIN UBICACION U ON (mel.ID_UBICACION=U.ID_UBICACION)
										INNER JOIN ALMACEN APZ ON (U.ID_ALMACEN=APZ.ID_ALMACEN) ";
            $whereAlmacenPermisosZonas = " AND APZ.ID_ALMACEN IN " . ($administrador->listadoAlmacenesPermiso("Escritura", "STRING")) . " ";

            // LINEAS CON PERMISO
            $sql                 = "SELECT * FROM MOVIMIENTO_ENTRADA_LINEA mel
                                        $joinAlmacenPermisosZonas
                                        WHERE mel.ID_MOVIMIENTO_ENTRADA = $idMovimiento AND mel.BAJA = 0
                            $whereAlmacenPermisosZonas";
            $resultLineasPermiso = $bd->ExecSQL($sql);

            //LINEAS TODAS
            $sqlLineasTodas    = "SELECT *
								FROM MOVIMIENTO_ENTRADA_LINEA MEL 							
								WHERE MEL.ID_MOVIMIENTO_ENTRADA = $idMovimiento AND MEL.BAJA = 0";
            $resultLineasTodas = $bd->ExecSQL($sqlLineasTodas);

            //COMPROBACION PERMISO
            $puedeProcesar = $bd->NumRegs($resultLineasTodas) == $bd->NumRegs($resultLineasPermiso);
            if ($puedeProcesar == false):
                if ($mostrar_error == 'texto'):
                    $arr_error['ERROR'] = $auxiliar->traduce("No tiene permisos para realizar esta operacion en esta subzona", $administrador->ID_IDIOMA);

                    return $arr_error;
                else:
                    $html->PagError("SinPermisosSubzona");
                endif;
            endif;
        endif;

        // SELECCIONO LAS LÍNES DE ENTRADA CON LA CANTIDAD TOTAL DE CADA ENTRADA
        $sql          = "SELECT ID_MOVIMIENTO_ENTRADA,ID_MOVIMIENTO_ENTRADA_LINEA, ID_MATERIAL, ID_MATERIAL_FISICO, CANTIDAD, ID_PEDIDO, ID_PEDIDO_LINEA, ID_UBICACION, ID_TIPO_BLOQUEO, ID_INCIDENCIA_CALIDAD, LINEA_ANULADA, BAJA
						FROM MOVIMIENTO_ENTRADA_LINEA 
						WHERE ID_MOVIMIENTO_ENTRADA=" . $rowMov->ID_MOVIMIENTO_ENTRADA . " AND ID_MOVIMIENTO_ENTRADA_LINEA IN (" . $listaIdsLineasMovimiento . ")
						ORDER BY ID_MOVIMIENTO_ENTRADA_LINEA";
        $resultLineas = $bd->ExecSQL($sql);
        $numOK        = 0;
        // COMPROBAMOS QUE LOS NÚMEROS DE SERIE DE LA ENTRADA NO SE ENCUENTREN CON STOCK POSITIVO EN EL SISTEMA
        $strErrorEnSistema  = "";
        $strErrorEnTransito = "";
        $strError           = "";
        while ($rowMovLinea = $bd->SigReg($resultLineas)):

            //COMPROBAMOS YA ANULADO
            if ($rowMovLinea->LINEA_ANULADA == 1):
                if ($mostrar_error == 'texto'):
                    $arr_error['ERROR'] = $auxiliar->traduce("La línea está anulada, no puede realizar la operación correspondiente", $administrador->ID_IDIOMA);

                    return $arr_error;
                else:
                    $html->PagError("LineaAnulada");
                endif;
            endif;

            //COMPROBAR QUE LA LINEA NO ESTE DADA DE BAJA
            if ($rowMovLinea->BAJA == 1):
                if ($mostrar_error == 'texto'):
                    $arr_error['ERROR'] = $auxiliar->traduce("La linea de movimiento esta dada de baja", $administrador->ID_IDIOMA);

                    return $arr_error;
                else:
                    $html->PagError("LineaDeBaja");
                endif;
            endif;


            //BUSCO LA LINEA DEL PEDIDO DE ENTRADA CORRESPONDIENTE
            if ($rowMovLinea->ID_PEDIDO_LINEA != NULL):
                $rowPedLin = $bd->VerReg("PEDIDO_ENTRADA_LINEA", "ID_PEDIDO_ENTRADA_LINEA", $rowMovLinea->ID_PEDIDO_LINEA);

                //COMPRUEBO SI LA LINEA DE PEDIDO ESTA PENDIENTE DE RECIBIR EL PEDIDO ACTUALIZADO
                if ($rowPedLin->REINTENTAR_SPLIT != 0):
                    if ($mostrar_error == 'texto'):
                        $arr_error['ERROR'] = $auxiliar->traduce("No se puede realizar la operacion debido a que el registro esta pendiente de actualizacion", $administrador->ID_IDIOMA);

                        return $arr_error;
                    else:
                        $html->PagError("LineaPendienteActualizar");
                    endif;
                endif;
            endif;

            //TIPO_BLOQUEO PREVENTIVO
            $rowTipoBloqueoPreventivo = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "SP", "No");
            $idTipoBloqueoPreventivo  = $rowTipoBloqueoPreventivo->ID_TIPO_BLOQUEO;
            //TIPO_BLOQUEO RETENIDO POR CALIDAD NO PREVENTIVO
            $rowTipoBloqueoRetenidoPorCalidadNoPreventivo = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "XRC", "No");
            $idTipoBloqueoRetenidoPorCalidadNoPreventivo  = $rowTipoBloqueoRetenidoPorCalidadNoPreventivo->ID_TIPO_BLOQUEO;
            //TIPO_BLOQUEO RETENIDO POR CALIDAD PREVENTIVO
            $rowTipoBloqueoRetenidoPorCalidadPreventivo = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "XRCP", "No");
            $idTipoBloqueoRetenidoPorCalidadPreventivo  = $rowTipoBloqueoRetenidoPorCalidadPreventivo->ID_TIPO_BLOQUEO;
            //TIPO BLOQUEO PDTE DEVOLVER PROVEEODR CALIDAD
            $rowTipoBloqueoLineaDevolverProveedorCalidad = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "SPDPC");

            //CANTIDAD TOTAL DE LA LINEA
            $cantidadComprobar = $rowMovLinea->CANTIDAD;

            //BUSCO LA TRANSFERENCIA DE ESTA LINEA
            $sqlTransferencias    = "SELECT DISTINCT *
                                    FROM MOVIMIENTO_TRANSFERENCIA 
                                    WHERE  TIPO = 'Recepcion' AND ID_MOVIMIENTO_ENTRADA_LINEA = $rowMovLinea->ID_MOVIMIENTO_ENTRADA_LINEA AND BAJA = 0";
            $resultTransferencias = $bd->ExecSQL($sqlTransferencias);

            $arrUbisDestino = array();
            while ($rowTransferencia = $bd->SigReg($resultTransferencias)):

                //SI NO SE HA ANULADO LA IC DE LA UBICACION DESTINO
                if (!isset($arrUbisDestino[$rowTransferencia->ID_UBICACION_DESTINO])):

                    $arrUbisDestino[$rowTransferencia->ID_UBICACION_DESTINO] = 1;

                    //SI TIENE INCIDENCIA DE CALIDAD
                    $idICTrans                        = NULL;
                    $GLOBALS["NotificaErrorPorEmail"] = "No";
                    $rowIC                            = $bd->VerRegRest("INCIDENCIA_CALIDAD", "ID_MOVIMIENTO_ENTRADA_LINEA= $rowMovLinea->ID_MOVIMIENTO_ENTRADA_LINEA AND ID_UBICACION_TRANSFERENCIA = $rowTransferencia->ID_UBICACION_DESTINO", "No");
                    $idICTrans                        = $rowIC->ID_INCIDENCIA_CALIDAD;

                    //SI TIENE INCIDENCIA Y STOCK BLOQUEADO HAREMOS:
                    // 1º UN CAMBIO DE ESTADO PREVIO
                    // 2º DESBLOQUEAR EL STOCK PARA REALIZAR LA ANULACION DE LA LINEA DE MANERA HABITUAL
                    $cantidadTotal = 0;
                    if (($idICTrans != NULL)):

                        //BUSCO SI HAY UN CAMBIO DE ESTADO QUE PASO EL MATERIAL DE CALIDAD A LOGISTICA INVERSA
                        $sqlCambioEstado    = "SELECT CE.ID_CAMBIO_ESTADO, CE.ID_TIPO_BLOQUEO_FINAL, CE.ID_ORDEN_TRABAJO_MOVIMIENTO
                                FROM CAMBIO_ESTADO CE
                                INNER JOIN ORDEN_TRABAJO_MOVIMIENTO OTM ON OTM.ID_ORDEN_TRABAJO_MOVIMIENTO = CE.ID_ORDEN_TRABAJO_MOVIMIENTO
                                INNER JOIN ORDEN_TRABAJO OT ON OT.ID_ORDEN_TRABAJO = OTM.ID_ORDEN_TRABAJO
                                WHERE OT.SISTEMA_OT = 'SGA IC' AND CE.TIPO_CAMBIO_ESTADO = 'PasoCicloCalidadCicloLogisticaInversa' AND CE.ID_CAMBIO_ESTADO_RELACIONADO IS NULL AND CE.BAJA = 0 AND CE.ID_MATERIAL = $rowMovLinea->ID_MATERIAL AND CE.ID_UBICACION = $rowTransferencia->ID_UBICACION_DESTINO AND CE.ID_TIPO_BLOQUEO_INICIAL = " . ($rowMovLinea->ID_TIPO_BLOQUEO == $idTipoBloqueoPreventivo ? $idTipoBloqueoRetenidoPorCalidadPreventivo : $idTipoBloqueoRetenidoPorCalidadNoPreventivo) . " AND CE.ID_MATERIAL_FISICO " . ($rowMovLinea->ID_MATERIAL_FISICO != NULL ? " = $rowMovLinea->ID_MATERIAL_FISICO" : " IS NULL") . " AND CE.ID_ORDEN_TRABAJO_MOVIMIENTO IS NOT NULL AND CE.ID_INCIDENCIA_CALIDAD = $idICTrans";
                        $resultCambioEstado = $bd->ExecSQL($sqlCambioEstado);
                        $rowCambioEstado    = $bd->SigReg($resultCambioEstado);

                        //SI OBTENGO ID DE CAMBIO DE ESTADO Calidad -> Logistica Inversa
                        if ($rowCambioEstado->ID_CAMBIO_ESTADO != NULL):
                            $GLOBALS["NotificaErrorPorEmail"] = "No";
                            $rowMatUbiIC                      = $bd->VerRegRest("MATERIAL_UBICACION", "ID_MATERIAL = $rowMovLinea->ID_MATERIAL AND ID_UBICACION = $rowTransferencia->ID_UBICACION_DESTINO AND ID_MATERIAL_FISICO" . ($rowMovLinea->ID_MATERIAL_FISICO != NULL ? " = $rowMovLinea->ID_MATERIAL_FISICO" : " IS NULL") . " AND ID_TIPO_BLOQUEO = $rowCambioEstado->ID_TIPO_BLOQUEO_FINAL AND ID_ORDEN_TRABAJO_MOVIMIENTO = $rowCambioEstado->ID_ORDEN_TRABAJO_MOVIMIENTO AND ID_INCIDENCIA_CALIDAD = $idICTrans", "No");
                            unset($GLOBALS["NotificaErrorPorEmail"]);
                        else:
                            $GLOBALS["NotificaErrorPorEmail"] = "No";
                            $rowMatUbiIC                      = $bd->VerRegRest("MATERIAL_UBICACION", "ID_MATERIAL = $rowMovLinea->ID_MATERIAL AND ID_UBICACION = $rowTransferencia->ID_UBICACION_DESTINO AND ID_MATERIAL_FISICO" . ($rowMovLinea->ID_MATERIAL_FISICO != NULL ? " = $rowMovLinea->ID_MATERIAL_FISICO" : " IS NULL") . " AND ID_TIPO_BLOQUEO " . ($rowMovLinea->ID_TIPO_BLOQUEO == $idTipoBloqueoPreventivo ? "= $idTipoBloqueoRetenidoPorCalidadPreventivo" : "= $idTipoBloqueoRetenidoPorCalidadNoPreventivo") . " AND ID_ORDEN_TRABAJO_MOVIMIENTO IS NULL AND ID_INCIDENCIA_CALIDAD = $idICTrans", "No");
                            unset($GLOBALS["NotificaErrorPorEmail"]);
                        endif;

                        //COMPRUEBO QUE EXISTE EL MATERIAL UBICACION ORIGEN (SM)
                        if ($rowMatUbiIC == false):
                            if ($mostrar_error == 'texto'):
                                $arr_error['ERROR'] = $auxiliar->traduce("No existe la ubicacion destino donde fue desubicado el material tras generarse la IC", $administrador->ID_IDIOMA);

                                return $arr_error;
                            else:
                                $html->PagError($auxiliar->traduce("No existe la ubicacion destino donde fue desubicado el material  tras generarse la IC", $administrador->ID_IDIOMA));
                            endif;
                        endif;

                        $cantidadTotal = $rowMatUbiIC->STOCK_TOTAL;
                    endif;
                    //FIN SI TIENE INCIDENCIA Y STOCK HAREMOS UN CAMBIO DE ESTADO PREVIO

                endif;//FIN SI NO SE HA ANULADO LA IC DE LA UBICACION DESTINO


                //BUSCO MATERIAL UBICACION  DONDE SE DEPOSITÓ EL MATERIAL (DEBERIA SER DE TIPO SALIDA)
                $GLOBALS["NotificaErrorPorEmail"] = "No";
                $rowMatUbi                        = $bd->VerRegRest("MATERIAL_UBICACION", "ID_MATERIAL = $rowTransferencia->ID_MATERIAL AND ID_UBICACION = $rowTransferencia->ID_UBICACION_DESTINO AND ID_MATERIAL_FISICO " . ($rowTransferencia->ID_MATERIAL_FISICO == NULL ? 'IS NULL' : " = $rowTransferencia->ID_MATERIAL_FISICO") . " AND ID_TIPO_BLOQUEO " . ($rowTransferencia->ID_TIPO_BLOQUEO == NULL ? 'IS NULL' : "= $rowTransferencia->ID_TIPO_BLOQUEO") . " AND ID_ORDEN_TRABAJO_MOVIMIENTO " . ($rowTransferencia->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? 'IS NULL' : "= $rowTransferencia->ID_ORDEN_TRABAJO_MOVIMIENTO") . " AND ID_INCIDENCIA_CALIDAD " . ($rowTransferencia->ID_INCIDENCIA_CALIDAD == NULL ? 'IS NULL' : "= $rowTransferencia->ID_INCIDENCIA_CALIDAD"), "No");
                unset($GLOBALS["NotificaErrorPorEmail"]);

                //COMPRUEBO QUE EXISTE EL MATERIAL UBICACION ORIGEN (SM)
                if ($rowMatUbi == false):
                    if ($mostrar_error == 'texto'):
                        $arr_error['ERROR'] = $auxiliar->traduce("No existe la ubicacion destino donde fue desubicado el material", $administrador->ID_IDIOMA);

                        return $arr_error;
                    else:
                        $html->PagError($auxiliar->traduce("No existe la ubicacion destino donde fue desubicado el material", $administrador->ID_IDIOMA));
                    endif;
                endif;

                $cantidadTotal = $cantidadTotal + $rowMatUbi->STOCK_TOTAL;

                //COMPRUEBO QUE EN LA UBICACION ORIGEN (SM) HAYA SUFICIENTE STOCK
                if ($cantidadTotal < $rowTransferencia->CANTIDAD):
                    if ($mostrar_error == 'texto'):
                        $arr_error['ERROR'] = $auxiliar->traduce("No existe stock suficiente para anular la linea seleccionada", $administrador->ID_IDIOMA);

                        return $arr_error;
                    else:
                        $html->PagError($auxiliar->traduce("No existe stock suficiente para anular la linea seleccionada", $administrador->ID_IDIOMA));
                    endif;
                endif;

                //QUITAMOS ESA CANTIDAD
                $cantidadComprobar = $cantidadComprobar - $rowTransferencia->CANTIDAD;

            endwhile;
            //FIN BUSCO LAS TRANSFERENCIA DE ESTA LINEA DE MOVIMIENTO DE SALIDA

            //SI QUEDA CANTIDAD, BUSCAMOS EN LA UBICACION DEL MEL
            if ($cantidadComprobar > EPSILON_SISTEMA):

                //BUSCO MATERIAL UBICACION  DONDE SE DEPOSITÓ EL MATERIAL (DEBERIA SER DE TIPO SALIDA)
                $GLOBALS["NotificaErrorPorEmail"] = "No";
                $rowMatUbi                        = $bd->VerRegRest("MATERIAL_UBICACION", "ID_MATERIAL = $rowMovLinea->ID_MATERIAL AND ID_UBICACION = $rowMovLinea->ID_UBICACION AND ID_MATERIAL_FISICO " . ($rowMovLinea->ID_MATERIAL_FISICO == NULL ? 'IS NULL' : " = $rowMovLinea->ID_MATERIAL_FISICO") . " AND ID_TIPO_BLOQUEO " . ($rowMovLinea->ID_TIPO_BLOQUEO == NULL ? 'IS NULL' : "= $rowMovLinea->ID_TIPO_BLOQUEO") . " AND ID_ORDEN_TRABAJO_MOVIMIENTO IS NULL AND ID_INCIDENCIA_CALIDAD " . ($rowMovLinea->ID_INCIDENCIA_CALIDAD == NULL ? 'IS NULL' : "= $rowMovLinea->ID_INCIDENCIA_CALIDAD"), "No");
                unset($GLOBALS["NotificaErrorPorEmail"]);

                //COMPRUEBO QUE EXISTE EL MATERIAL UBICACION ORIGEN (SM)
                if ($rowMatUbi == false):
                    if ($mostrar_error == 'texto'):
                        $arr_error['ERROR'] = $auxiliar->traduce("No existe la ubicacion destino donde fue desubicado el material", $administrador->ID_IDIOMA);

                        return $arr_error;
                    else:
                        $html->PagError($auxiliar->traduce("No existe la ubicacion destino donde fue desubicado el material", $administrador->ID_IDIOMA));
                    endif;
                endif;

                //COMPRUEBO QUE EN LA UBICACION ORIGEN (SM) HAYA SUFICIENTE STOCK
                if ($rowMatUbi->STOCK_TOTAL < $cantidadComprobar):
                    if ($mostrar_error == 'texto'):
                        $arr_error['ERROR'] = $auxiliar->traduce("No existe stock suficiente para anular la linea seleccionada", $administrador->ID_IDIOMA);

                        return $arr_error;
                    else:
                        $html->PagError($auxiliar->traduce("No existe stock suficiente para anular la linea seleccionada", $administrador->ID_IDIOMA));
                    endif;
                endif;
            endif;


            // BUSCO LA UBICACION
            $rowUbi = $bd->VerReg("UBICACION", "ID_UBICACION", $rowMovLinea->ID_UBICACION, "No");

            //SI ES UN ALMACEN CON RADIOFRECUENCIA, EL MATERIAL DEBERÁ ESTAR EN EM. EN OTRO CASO, NO HABRÁ TRANSFERENCIAS Y NO SERA NECESARIO
            if (($rowRecepcion->VIA_RECEPCION == 'PDA') && ($rowUbi->TIPO_UBICACION != 'Entrada')):
                //COMPRUEBO QUE LA UBICACION ESTE MARCADA COMO ENTRADA DE MATERIAL
                if ($mostrar_error == 'texto'):
                    $arr_error['ERROR'] = $auxiliar->traduce("La ubicacion no es de entrada de material", $administrador->ID_IDIOMA);

                    return $arr_error;
                else:
                    $html->PagError("MaterialNoEnEM");

                endif;
            endif;

            // 4. ACTUALIZO EL MOVIMIENTO DE ENTRADA AL ESTADO CORRECTO Y FECHA CONTABILIZACION SI NO ESTA RELLENA
            $sqlUpdate = "UPDATE MOVIMIENTO_ENTRADA_LINEA SET
                             LINEA_PENDIENTE_CONFIRMACION_SAP = 1
                             , FECHA_ANULACION = '" . date("Y-m-d H:i:s") . "' 
							 , ANULACION_STOCK = " . ($anulacion_stock == "Si" ? "1" : "0") . "
                            WHERE ID_MOVIMIENTO_ENTRADA_LINEA = $rowMovLinea->ID_MOVIMIENTO_ENTRADA_LINEA";
            $bd->ExecSQL($sqlUpdate);

            $numOK = $numOK + 1;
        endwhile;
        //FIN RECORRER LINEAS


        if ($numOK > 0):

            // 4. ACTUALIZO EL MOVIMIENTO DE ENTRADA AL ESTADO CORRECTO Y FECHA CONTABILIZACION SI NO ESTA RELLENA
            $sqlUpdate = "UPDATE MOVIMIENTO_ENTRADA SET
                        ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                        , PENDIENTE_CONFIRMACION_SAP = 1
                        WHERE ID_MOVIMIENTO_ENTRADA = $idMovimiento";
            $bd->ExecSQL($sqlUpdate);


            // LOG MOVIMIENTOS.
            $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Mov. entrada", $idMovimiento, "Procesamiento asincrono");


            $resultado = $sap->EnvioMovimientoEntradaAnuladoPendienteConfirmacionSAP($idMovimiento, $listaIdsLineasMovimiento);

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

                if ($mostrar_error == 'texto'):
                    $arr_error['ERROR'] = "We have received the following errors from SAP/Se han producido los siguientes errores en el intercambio de información con SAP" . ": " . '<br>' . $strError;

                    return $arr_error;
                else:
                    $html->PagError("ErrorSAP");
                endif;
            endif;
        endif;

        //GENERAMOS EL ARRAY DE RESPUESTA
        $arrDevuelto = array();

        return $arrDevuelto;

    }


    /**
     * @param $idMovimientoEntrada
     * $arr_extra TRAE INFORMACION DE OBJETOS A SER TRATADOS:
     *   - 'arrayLineasMovimientoInvolucradas' => ARRAY CON LINEAS INVOLUCRADAS
     * $mostrar_error SI EL VALOR ES 'texto' EN VEZ DE SALIR PAGINA DE ERROR SE OBTENDRA UN TEXTO
     * REALIZA LAS ACCIONES QUE POR LA LOGICA DEL PROCESO DE EJECUTAN DESPUES DE RECEPCIONAR EL MOVIMIENTO DE ENTRADA
     */
    function RealizarAccionesPostAnulacionRecepcion($idMovimiento, $arr_extra = array(), $mostrar_error = "")
    {
        global $bd;
        global $administrador;
        global $auxiliar;
        global $sap;
        global $html;
        global $incidencia_sistema;
        global $orden_transporte;
        global $importe;
        global $necesidad;
        global $aviso;
        global $mat;
        global $pedido;
        global $expedicion;

        //BUSCO EL MOVIMIENTO
        $rowMov = $bd->VerReg("MOVIMIENTO_ENTRADA", "ID_MOVIMIENTO_ENTRADA", $idMovimiento);

        //BUSCO LA RECEPCION
        $rowDoc = $bd->VerReg("MOVIMIENTO_RECEPCION", "ID_MOVIMIENTO_RECEPCION", $rowMov->ID_MOVIMIENTO_RECEPCION);

        //ARRAY DEVOLVER
        $arr_devolver = array();

        //DATOS QUE NOS VIENEN DEL PROCESAMIENTO DE LA RECEPCION
        $arrayLineasMovimientoInvolucradas = array();
        if (isset($arr_extra['arrayLineasMovimientoInvolucradas']) && (is_array($arr_extra['arrayLineasMovimientoInvolucradas']))):
            $arrayLineasMovimientoInvolucradas = $arr_extra['arrayLineasMovimientoInvolucradas'];
        endif;
        $arrayLineasPedidosInvolucradas = array();

        //INICIO TRANSACCION
        $bd->begin_transaction();


        //RECORREMOS LAS LINEAS
        $sqlLineasProcesar    = "SELECT ID_MOVIMIENTO_ENTRADA_LINEA,ID_UBICACION,ID_MATERIAL, CANTIDAD, ID_EXPEDICION_ENTREGA, ID_PEDIDO, ID_PEDIDO_LINEA
                            FROM MOVIMIENTO_ENTRADA_LINEA
                            WHERE ID_MOVIMIENTO_ENTRADA = " . $rowMov->ID_MOVIMIENTO_ENTRADA . " AND LINEA_ANULADA = 1 AND BAJA = 0 AND ID_MOVIMIENTO_ENTRADA_LINEA IN (" . implode(",", (array) $arrayLineasMovimientoInvolucradas) . ")";
        $resultLineasProcesar = $bd->ExecSQL($sqlLineasProcesar);
        while ($rowMovLinea = $bd->SigReg($resultLineasProcesar)):

            //SI TIENE EXPEDICION ASOCIADA, ACTUALIZAMOS SUS DATOS
            if ($rowMovLinea->ID_EXPEDICION_ENTREGA != ""):
                //RECUPERAMOS LA RECOGIDA
                $rowRecogidaEntrega = $bd->VerReg("EXPEDICION", "ID_EXPEDICION", $rowMovLinea->ID_EXPEDICION_ENTREGA, "No");

                //SI ES UNA RECOGIDA EN PROVEEDOR SIN PEDIDO CONOCIDO CALCULO LOS DATOS DE LA RECOGIDA PARA GRABARLOS
                if (($rowRecogidaEntrega->TIPO_ORDEN_RECOGIDA == 'Recogida en Proveedor') && ($rowRecogidaEntrega->SUBTIPO_ORDEN_RECOGIDA == 'Sin Pedido Conocido')):
                    //ACTUALIZO EL ADR
                    $expedicion->calcularADRExpedicion($rowRecogidaEntrega->ID_EXPEDICION);
                    $expedicion->calcularAmbitoYComunidadExpedicion($rowRecogidaEntrega->ID_EXPEDICION);

                    //CALCULO EL PESO Y LO GUARDO
                    $pesoRecogida = $importe->getPesoMaterialesConPesoOrdenRecogida($rowRecogidaEntrega->ID_EXPEDICION);

                    $sqlUpdate = "UPDATE EXPEDICION SET PESO='$pesoRecogida' WHERE ID_EXPEDICION=$rowRecogidaEntrega->ID_EXPEDICION";
                    $bd->ExecSQL($sqlUpdate);


                    //ACTUALIZO EL PESO DE LA ORDEN DE TRANSPORTE
                    $orden_transporte->ActualizarPeso($rowRecogidaEntrega->ID_ORDEN_TRANSPORTE);
                endif;
                //FIN SI ES UNA RECOGIDA EN PROVEEDOR SIN PEDIDO CONOCIDO CALCULO LOS DATOS DE LA RECOGIDA PARA GRABARLOS
            endif;
            //FIN SI TIENE EXPEDICION ASOCIADA, ACTUALIZAMOS SUS DATOS

            //SI LA LINEA ES RELEVANTE Y TIENE CANTIDAD PENDIENTE DE RECEPCIONAR, MANDAMOS SPLIT
            if ($rowMovLinea->ID_PEDIDO != NULL):

                //GUARDO LOS PEDIDOS DE ENTRADA INVOLUCRADOS EN ESTA LINEA DE MOVIMIENTO
                if (!in_array($rowMovLinea->ID_PEDIDO_LINEA, (array) $arrayLineasPedidosInvolucradas)):
                    $arrayLineasPedidosInvolucradas[] = $rowMovLinea->ID_PEDIDO_LINEA;
                endif;

                //OBTENEMOS LA LINEA DE PEDIDO ACTUALIZADA
                $rowPedidoLinea   = $bd->VerReg("PEDIDO_ENTRADA_LINEA", "ID_PEDIDO_ENTRADA_LINEA", $rowMovLinea->ID_PEDIDO_LINEA, "No");
                $rowPedidoEntrada = $bd->VerReg("PEDIDO_ENTRADA", "ID_PEDIDO_ENTRADA", $rowPedidoLinea->ID_PEDIDO_ENTRADA, "No");

                //BUSCAMOS SI FORMA PARTE DE UN PEDIDO CONOCIDO NO RECEPCIONADO
                $conPedidoConocidoNoRecepcionado = false;
                if ($rowMovLinea->ID_EXPEDICION_ENTREGA != ""):
                    $GLOBALS["NotificaErrorPorEmail"] = "No";
                    $rowPedidoConocido                = $bd->VerRegRest("EXPEDICION_PEDIDO_CONOCIDO", "ID_EXPEDICION = $rowMovLinea->ID_EXPEDICION_ENTREGA AND ID_PEDIDO_ENTRADA_LINEA = $rowPedidoLinea->ID_PEDIDO_ENTRADA_LINEA AND BAJA = 0", "No");
                    //SI PERTENECE A UN PEDIDO CONOCIDO Y NO ESTA RECEPCIONADO
                    if (($rowPedidoConocido) && ($rowDoc->ESTADO == "En Proceso")):
                        $conPedidoConocidoNoRecepcionado = true;
                    endif;
                endif;
                //SI ES RELEVANTE, NO ESTA TOTALMENTE RECEPCIONADA Y NO FORMA PARTE DE UN PEDIDO CONOCIDO(YA QUE ESTAS SE PUEDEN VOLVER A ASOCIAR A OTRO MOVIMIENTO DEL TRANSPORTE MIENTRAS LA RECEPCION NO ESTE PROCESADA)
                if (($rowPedidoLinea->RELEVANTE_ENTREGA_ENTRANTE == 1) && ($rowPedidoLinea->CANTIDAD_PDTE > 0) && ($conPedidoConocidoNoRecepcionado == false)):

                    //INICIO LA TRANSACCION
                    //$bd->begin_transaction();

                    //BUSCAMOS EL TIPO DE EXPEDICION
                    if ($rowMovLinea->ID_EXPEDICION_ENTREGA != ""):
                        $rowExpedicionEntrega = $bd->VerReg("EXPEDICION", "ID_EXPEDICION", $rowMovLinea->ID_EXPEDICION_ENTREGA, "No");
                        //SI ES SIN PEDIDO CONOCIDO
                        if ($rowExpedicionEntrega->SUBTIPO_ORDEN_RECOGIDA == "Sin Pedido Conocido"):
                            //NOS GUARDAMOS EN TRANSPORTE ANULADOS
                            $sqlInsert = "INSERT INTO ORDEN_TRANSPORTE_LINEA_ANULADA SET
                                                                  ID_ORDEN_TRANSPORTE = $rowDoc->ID_ORDEN_TRANSPORTE
                                                                , ID_EXPEDICION = $rowExpedicionEntrega->ID_EXPEDICION
                                                                , ID_MOVIMIENTO_ENTRADA_LINEA = $rowMovLinea->ID_MOVIMIENTO_ENTRADA_LINEA";
                            $bd->ExecSQL($sqlInsert);
                        elseif ($rowPedidoConocido)://SI ES CON PEDIDO CONOCIDO Y LO HEMOS ENCONTRADO ANTES

                            //EL PEDIDO CONOCIDO FORMA PARTE DE UNA RECEPCION RECEPCIONADA, LO ANULADO LO QUITAMOS
                            $sqlUpdate = "UPDATE EXPEDICION_PEDIDO_CONOCIDO SET
                                                CANTIDAD_NO_SERVIDA = CANTIDAD_NO_SERVIDA + $rowMovLinea->CANTIDAD
                                                WHERE ID_EXPEDICION_PEDIDO_CONOCIDO =$rowPedidoConocido->ID_EXPEDICION_PEDIDO_CONOCIDO";
                            $bd->ExecSQL($sqlUpdate);

                            //SI LA CANTIDAD SE QUEDA A 0, LO DAMOS DE BAJA Y LO GUARDAMOS EN LA TABLA DE TRANSPORTES ANULADOS
                            $rowEPCActualizada = $bd->VerReg("EXPEDICION_PEDIDO_CONOCIDO", "ID_EXPEDICION_PEDIDO_CONOCIDO", $rowPedidoConocido->ID_EXPEDICION_PEDIDO_CONOCIDO, "No");

                            //ACTUALIZAMOS LA CANTIDAD ASOCIADA A ORDENES DE RECOGIDA
                            $sqlUpdate = "UPDATE PEDIDO_ENTRADA_LINEA SET CANTIDAD_ASIGNADA_ORDENES_RECOGIDA = " . ($rowEPCActualizada->CANTIDAD - $rowEPCActualizada->CANTIDAD_NO_SERVIDA) . " WHERE ID_PEDIDO_ENTRADA_LINEA =  $rowPedidoConocido->ID_PEDIDO_ENTRADA_LINEA";
                            $bd->ExecSQL($sqlUpdate);

                            if ($rowEPCActualizada->CANTIDAD_NO_SERVIDA == $rowEPCActualizada->CANTIDAD):
                                //DAMOS DE BAJA
                                $sqlUpdate = "UPDATE EXPEDICION_PEDIDO_CONOCIDO SET BAJA = 1 WHERE ID_EXPEDICION_PEDIDO_CONOCIDO = $rowPedidoConocido->ID_EXPEDICION_PEDIDO_CONOCIDO";
                                $bd->ExecSQL($sqlUpdate);

                                //GUARDAMOS EL REGISTRO
                                $sqlInsert = "INSERT INTO ORDEN_TRANSPORTE_LINEA_ANULADA SET
                                                                  ID_ORDEN_TRANSPORTE = $rowDoc->ID_ORDEN_TRANSPORTE
                                                                , ID_EXPEDICION = $rowPedidoConocido->ID_EXPEDICION
                                                                , ID_EXPEDICION_PEDIDO_CONOCIDO = $rowPedidoConocido->ID_EXPEDICION_PEDIDO_CONOCIDO";
                                $bd->ExecSQL($sqlInsert);
                            endif;
                        endif;//SI FORMA PARTE DE UN PEDIDO CONOCIDO

                    endif;
                    //FIN BUSCAMOS EL TIPO DE EXPEDICION


                    // EN EDF NO HAY SPLITS DE ENTRADAS POR AHORA
                    //DESBLOQUEAMOS LA LINEA
                    /*unset($objLinea);    //VACIO EL OBJETO LINEA
                    unset($arrLineas); //VACIO EL ARRAY A ENVIAR
                    $objLinea            = new stdClass();
                    $objLinea->PEDIDO    = $rowPedidoEntrada->PEDIDO_SAP;    // Número de pedido cuyas posiciones vamos a bloquear (Obligatorio)
                    $objLinea->POSICION  = $rowPedidoLinea->LINEA_PEDIDO_SAP;    // Línea de pedido a bloquear (Obligatorio)
                    $objLinea->BLOQUEADO = '';    // (vacio=NO, X=SI)
                    $arrLineas[]         = $objLinea;

                    //INFORMO A SAP DEL DESBLOQUEO (SI FALLA ROLLBACK Y PANTALLA DE ERROR)
                    $resultadoDesbloqueo = $sap->bloqueoLineasPedido($arrLineas);
                    if ($resultadoDesbloqueo['RESULTADO'] != 'OK'): //No se ha podido grabar la información en SAP
                        if (count($resultadoDesbloqueo['ERRORES']) > 0):
                            $erroresLineas .= $datosErrorLinea;
                            foreach ($resultadoDesbloqueo['ERRORES'] as $arr):
                                foreach ($arr as $mensaje_error):
                                    $erroresLineas .= $mensaje_error . ". ";
                                endforeach;
                            endforeach;
                            $erroresLineas .= "<br>";
                        endif;

                        //DESHAGO LA TRANSACCION
                        $bd->rollback_transaction();

                        //GRABO EL ERROR PRODUCIDO EN BASE DE DATOS
                        $sap->InsertarErrores($resultadoDesbloqueo);

                        continue;
                        //$html->PagError("ErrorSAP");
                    endif;


                    //FINALIZO LA TRANSACCION
                    $bd->commit_transaction();


                    //INICIO LA TRANSACCION
                    $bd->begin_transaction();


                    //INTRODUCIMOS LA LINEA AL ARRAY
                    $arrSplitPedidos                                           = array();
                    $arrSplitPedidos[$rowPedidoLinea->ID_PEDIDO_ENTRADA_LINEA] = ($rowPedidoLinea->CANTIDAD - $rowPedidoLinea->CANTIDAD_PDTE);

                    //HAGO LA LLAMADA A SAP PARA INDICARLE EL SPLIT QUE TIENE QUE REALIZAR (SI FALLA ROLLBACK, PERO NO MOSTRAMOS PANTALLA DE ERROR, SEGUIMOS PARA MANDAR EL BLOQUEO)
                    $resultadoSplit = $sap->SplitPedido($arrSplitPedidos, "Entrada");
                    if ($resultadoSplit['RESULTADO'] != 'OK'): //No se ha podido grabar la información en SAP
                        $hayErrorSplit = true;
                        $erroresLineas .= $datosErrorLinea;
                        foreach ($resultadoSplit['ERRORES'] as $arr):
                            foreach ($arr as $mensaje_error):
                                $erroresLineas .= $mensaje_error . ". ";
                            endforeach;
                        endforeach;
                        $erroresLineas .= "<br>";

                        //DESHAGO LA TRANSACCION
                        $bd->rollback_transaction();

                        //GRABO EL ERROR PRODUCIDO EN BASE DE DATOS
                        $sap->InsertarErrores($resultadoSplit);

                        //GUARDAMOS QUE HA HABIDO FALLO EN EL SPLIT
                        $sqlUpdate = "UPDATE PEDIDO_ENTRADA_LINEA SET REINTENTAR_SPLIT = 1 WHERE ID_PEDIDO_ENTRADA_LINEA = $rowPedidoLinea->ID_PEDIDO_ENTRADA_LINEA ";
                        $bd->ExecSQL($sqlUpdate);

                        //BUSCO EL TIPO DE INCIDENCIA SISTEMA 'Time Out'
                        $rowISTipo    = $bd->VerReg("INCIDENCIA_SISTEMA_TIPO", "INCIDENCIA_SISTEMA_TIPO", "Split Linea Pedido", "No");
                        $rowISSubTipo = $bd->VerReg("INCIDENCIA_SISTEMA_SUBTIPO", "INCIDENCIA_SISTEMA_SUBTIPO", "Error en solicitud", "No");

                        //EXTRAIGO EL IDENTIFICADOR DEL WEB SERVICE
                        $idWebService = $resultadoSplit['LOG_ERROR']['EXTERNALREFID'];

                        //GRABO LA INCIDENCIA DE SISTEMA DEL TIME OUT
                        $sqlInsert = "INSERT INTO INCIDENCIA_SISTEMA SET
                                      ID_INCIDENCIA_SISTEMA_TIPO = $rowISTipo->ID_INCIDENCIA_SISTEMA_TIPO
                                      , TIPO = 'Split Linea Pedido'
                                      , ID_INCIDENCIA_SISTEMA_SUBTIPO = $rowISSubTipo->ID_INCIDENCIA_SISTEMA_SUBTIPO
                                      , SUBTIPO = 'Error en solicitud'
                                      , ESTADO = 'Creada'
                                      , TABLA_OBJETO = 'PEDIDO_ENTRADA_LINEA'
                                      , ID_OBJETO = $rowPedidoLinea->ID_PEDIDO_ENTRADA_LINEA
                                      , FECHA_CREACION = '" . date("Y-m-d H:i:s") . "'
                                      , FECHA_RESOLUCION = '0000-00-00 00:00:00'
                                      , ID_ADMINISTRADOR_CREACION = $administrador->ID_ADMINISTRADOR
                                      , ID_ADMINISTRADOR_ULTIMA_MODIFICACION = $administrador->ID_ADMINISTRADOR
                                      , ID_LOG_EJECUCION_WS = $idWebService
                                      , OBSERVACIONES = ''";
                        $bd->ExecSQL($sqlInsert);

                        continue;
                        //$html->PagError("ErrorSAP");
                    endif;
                    //FIN HAGO LA LLAMADA A SAP PARA INDICARLE EL SPLIT QUE TIENE QUE REALIZAR

                    //FINALIZO LA TRANSACCION
                    $bd->commit_transaction();


                    //INICIO LA TRANSACCION
                    $bd->begin_transaction();


                    //INFORMO A SAP DE LAS LINEAS BLOQUEADAS
                    $resultado = $pedido->controlBloqueoLinea("Entrada", 'SplitLineas', $rowPedidoLinea->ID_PEDIDO_ENTRADA_LINEA);
                    if ($resultado['RESULTADO'] != 'OK'): //No se ha podido grabar la información en SAP
                        if (count($resultado['ERRORES']) > 0):
                            $erroresLineas .= $datosErrorLinea;
                            foreach ($resultado['ERRORES'] as $arr):
                                foreach ($arr as $mensaje_error):
                                    $erroresLineas .= $mensaje_error . ". ";
                                endforeach;
                            endforeach;
                            $erroresLineas .= "<br>";
                        endif;

                        //DESHAGO LA TRANSACCION
                        $bd->rollback_transaction();

                        //GRABO EL ERROR PRODUCIDO EN BASE DE DATOS
                        $sap->InsertarErrores($resultado);

                        continue;
                        //$html->PagError("ErrorSAP");
                    endif;


                    //FINALIZO LA TRANSACCION
                    $bd->commit_transaction();


                    //MOSTRAMOS SI HA HABIDO ERROR EN EL SPLIT Y EN EL ZTLI
                    if (($hayErrorSplit == true) && ($hayErrorZTLI == true)):
                        $erroresLineas .= $datosErrorLinea . $auxiliar->traduce("La linea ha sido anulada, pero se han producido los siguientes errores SAP al realizar split del pedido e informar del pedido ZTLI, debera volverlo a intentar desde incidencias de sistema", $administrador->ID_IDIOMA) . "<br>";
                        continue;
                    elseif ($hayErrorSplit == true):
                        $erroresLineas .= $datosErrorLinea . $auxiliar->traduce("La linea ha sido anulada, pero se han producido los siguientes errores SAP al realizar split del pedido, debera volverlo a intentar desde incidencias de sistema", $administrador->ID_IDIOMA) . '<br>';
                        continue;
                    elseif ($hayErrorZTLI == true):
                        $erroresLineas .= $datosErrorLinea . $auxiliar->traduce("La linea ha sido anulada y se ha solicitado el split del pedido, pero se han producido los siguientes errores SAP al informar del pedido ZTLI, debera volverlo a intentar desde incidencias de sistema", $administrador->ID_IDIOMA) . '<br>';
                        continue;
                    endif;*/

                endif;
            endif;//FIN SI LA LINEA ES RELEVANTE Y TIENE CANTIDAD PENDIENTE DE REPCECIONAR, MANDAMOS SPLIT

        endwhile;

        //SI SON SIN PEDIDO CONODIDO O CON PEDIDO CONOCIDO, AVANZAMOS EL ESTADO A RECEPCIONADO SI SE HAN PROCESADO TODAS LAS LINEAS
        $this->actualizarRecogidasEnProveedor($rowMov->ID_MOVIMIENTO_ENTRADA);

        //BUSCO NUMERO DE LINEAS DEL MOVIMIENTO DE ENTRADA
        $numLineas = $bd->NumRegsTabla("MOVIMIENTO_ENTRADA_LINEA", "ID_MOVIMIENTO_ENTRADA = $rowMov->ID_MOVIMIENTO_ENTRADA AND BAJA = 0 AND LINEA_ANULADA = 0");

        //SI ES UNA RECEPCION EN BASE A CONTENEDOR ENTRANTE Y ES LA ULTIMA LINEA, LA DOY DE BAJA Y EL CONTENEDOR LO PASO A ESTADO 'Pendiente de Recepcionar'
        if (($rowDoc->ID_CONTENEDOR_ENTRANTE != NULL) && ($numLineas == 0)):
            //BUSCO NUMERO DE MOVIMIENTOS DE LA RECEPCION
            $numMovimientos = $bd->NumRegsTabla("MOVIMIENTO_ENTRADA", "ID_MOVIMIENTO_RECEPCION = $rowDoc->ID_MOVIMIENTO_RECEPCION AND BAJA = 0");

            if ($numMovimientos == 0):
                $sqlUpdate = "UPDATE MOVIMIENTO_RECEPCION SET BAJA = 1 WHERE ID_MOVIMIENTO_RECEPCION = $rowDoc->ID_MOVIMIENTO_RECEPCION";
                $bd->ExecSQL($sqlUpdate);

                // LOG MOVIMIENTOS
                $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Anulación", "Recepción", $rowDoc->ID_MOVIMIENTO_RECEPCION, "");
            endif;

            $sqlUpdate = "UPDATE CONTENEDOR_ENTRANTE SET ESTADO = 'Pendiente de Recepcionar' WHERE ID_CONTENEDOR_ENTRANTE = $rowDoc->ID_CONTENEDOR_ENTRANTE";
            $bd->ExecSQL($sqlUpdate);
        endif;
        //FIN SI ES UNA RECEPCION EN BASE A CONTENEDOR ENTRANTE, LA DOY DE BAJA Y EL CONTENEDOR LO PASO A ESTADO 'Pendiente de Recepcionar'


        //FINALIZO LA TRANSACCION
        $bd->commit_transaction();


        //INICIO TRANSACCION
        $bd->begin_transaction();


        if ((count( (array)$arrayLineasPedidosInvolucradas) > 0) && ($rowMov->TIPO_MOVIMIENTO == 'PedidoProveedor') || ($rowMov->TIPO_MOVIMIENTO == 'PedidoDevolucionVenta') || ($rowMov->TIPO_MOVIMIENTO == 'PedidoCompraSGAManual')):
            //INFORMO A SAP DE LAS LINEAS BLOQUEADAS
            $resultado = $pedido->controlBloqueoLinea("Entrada", 'AnularLinea', implode(",", (array) $arrayLineasPedidosInvolucradas));
            if ($resultado['RESULTADO'] != 'OK'): //No se ha podido grabar la información en SAP
                if (count( (array)$resultado['ERRORES']) > 0):
                    foreach ($resultado['ERRORES'] as $arr):
                        foreach ($arr['MENSAJE'] as $mensaje_error):
                            $strError = $strError . $mensaje_error . "<br>";
                        endforeach;
                    endforeach;
                endif;

                $bd->rollback_transaction();

                //GRABO EL ERROR PRODUCIDO EN BASE DE DATOS
                $sap->InsertarErrores($resultado);
            endif;
        endif;


        //FINALIZO LA TRANSACCION
        $bd->commit_transaction();


        return $arr_devolver;

    }


    /**
     * @string $txFechaInicio Fecha en formato SQL
     * @string $txFechaFin  Fecha en formato SQL
     * @return array
     */
    function generarInformeIncidenciasCalidad($txFechaInicio, $txFechaFin, $idIdiomaInforme = "ENG")
    {

        global $bd;
        global $auxiliar;
        global $comp;
        global $administrador;
        global $html;

        //ARRAY DEVOLVER
        $arrayInforme = array();

        //DATOS AGREGADOS
        $arrIncidencias = array();


        $sqlFiltros = " AND IC.FECHA <= '" . $txFechaFin . "' AND IC.FECHA >= '" . $txFechaInicio . "' ";

        $sqlIncidencias    = "SELECT IC.ID_INCIDENCIA_CALIDAD, IC.ID_MATERIAL,IC.OBSERVACIONES,
                            IC.ID_MATERIAL_FISICO, IC.FECHA, IC.CANTIDAD AS CANTIDAD,IC.ID_TIPOLOGIA_INCIDENCIA, IC.ESTADO, IC.ID_PROVEEDOR_INCIDENCIA,
                            IC.DECISION, IC.TIPO_INCIDENCIA, IC.ID_ORDEN_TRABAJO, IC.ID_ORDEN_TRABAJO_MOVIMIENTO, IC.DESCRIPCION,
                            IC.ID_ALBARAN AS IDENT_ALBARAN, IC.ID_ALBARAN_LINEA, IC.ID_MOVIMIENTO_ENTRADA, IC.ID_ORDEN_MONTAJE, IC.ID_ORDEN_MONTAJE_PARAMETROS_ACCION, IC.ID_ORDEN_MONTAJE_PARAMETROS_ACCION_CHECKLIST,
                            IC.ID_MOVIMIENTO_ENTRADA_LINEA, ADM.NOMBRE AS USUARIO_CREACION, ADM.ID_ADMINISTRADOR AS ID_ADMINISTRADOR_CREACION,
                            IC.ID_ADMINISTRADOR_MODIFICACION, IC.FECHA_CIERRE, IC.NO_CONFORMIDAD, TI.NOMBRE_ENG, TI.NOMBRE_ESP,
                            M.DESCRIPCION,M.DESCRIPCION_EN
                                    FROM INCIDENCIA_CALIDAD IC
                                    INNER JOIN TIPOLOGIA_INCIDENCIA TI ON TI.ID_TIPOLOGIA_INCIDENCIA = IC.ID_TIPOLOGIA_INCIDENCIA
                                    INNER JOIN ADMINISTRADOR ADM ON ADM.ID_ADMINISTRADOR = IC.ID_ADMINISTRADOR_CREACION
                                    INNER JOIN MATERIAL M ON M.ID_MATERIAL = IC.ID_MATERIAL
                                    WHERE 1=1 AND IC.BAJA = 0 $sqlFiltros";
        $resultIncidencias = $bd->ExecSQL($sqlIncidencias);

        if ($bd->NumRegs($resultIncidencias) > 0):
            while ($rowIncidencia = $bd->SigReg($resultIncidencias)):

                //CAMBIO DE ESTADO
                $GLOBALS["NotificaErrorPorEmail"] = "No";
                $rowCambioEstado                  = $bd->VerRegRest("CAMBIO_ESTADO", "ID_INCIDENCIA_CALIDAD = " . $rowIncidencia->ID_INCIDENCIA_CALIDAD . " ORDER BY ID_CAMBIO_ESTADO DESC", "No"); // ASÍ COGE EL MÁS NUEVO


                //MATERIAL UBICACIÓN
                $GLOBALS["NotificaErrorPorEmail"] = "No";
                $rowMaterialUbicacion             = $bd->VerReg("MATERIAL_UBICACION", "ID_INCIDENCIA_CALIDAD", $rowIncidencia->ID_INCIDENCIA_CALIDAD, "No");


                //MOVIMIENTO ENTRADA LÍNEA
                $GLOBALS["NotificaErrorPorEmail"] = "No";
                $rowMovLinea                      = $bd->VerReg("MOVIMIENTO_ENTRADA_LINEA", "ID_MOVIMIENTO_ENTRADA_LINEA", $rowIncidencia->ID_MOVIMIENTO_ENTRADA_LINEA, "No");

                //ALBARÁN
                $GLOBALS["NotificaErrorPorEmail"] = "No";
                $rowAlbaran                       = $bd->VerReg("ALBARAN", "ID_ALBARAN", $rowIncidencia->IDENT_ALBARAN, "No");


                //BUSCAMOS ALMACEN INCIDENCIA
                $idAlmacenIncidencia = "";
                if ($rowCambioEstado):
                    //SI EXISTE CAMBIO DE ESTADO, SACAMOS LOS DATOS DEL REGISTRO
                    $idUbicacionIncidencia = $rowCambioEstado->ID_UBICACION;

                    //UBICACIÓN
                    $GLOBALS["NotificaErrorPorEmail"] = "No";
                    $rowUbicacionIncidencia           = $bd->VerReg("UBICACION", "ID_UBICACION", $idUbicacionIncidencia, "No");

                    //OBTENEMOS EL ID DEL ALMACÉN
                    $idAlmacenIncidencia = $rowUbicacionIncidencia->ID_ALMACEN;

                elseif ($rowMaterialUbicacion):
                    //SI EXISTE MATERIAL_UBICACIÓN, SACAMOS LOS DATOS DEL REGISTRO
                    $idUbicacionIncidencia = $rowMaterialUbicacion->ID_UBICACION;

                    //UBICACIÓN
                    $GLOBALS["NotificaErrorPorEmail"] = "No";
                    $rowUbicacionIncidencia           = $bd->VerReg("UBICACION", "ID_UBICACION", $idUbicacionIncidencia, "No");

                    //OBTENEMOS EL ID DEL ALMACÉN
                    $idAlmacenIncidencia = $rowUbicacionIncidencia->ID_ALMACEN;

                elseif ($rowMovLinea):
                    // SI NO EXISTE, SACAMOS LOS DATOS DEL MOVIMIENTO_ENTRADA
                    $idUbicacionIncidencia = $rowMovLinea->ID_UBICACION;

                    //UBICACIÓN
                    $GLOBALS["NotificaErrorPorEmail"] = "No";
                    $rowUbicacionIncidencia           = $bd->VerReg("UBICACION", "ID_UBICACION", $idUbicacionIncidencia, "No");

                    //OBTENEMOS EL ID DEL ALMACÉN
                    $idAlmacenIncidencia = $rowUbicacionIncidencia->ID_ALMACEN;
                else:
                    //OBTENEMOS EL ID DEL ALMACÉN DESDE EL ALBARÁN
                    $idAlmacenIncidencia = $rowAlbaran->ID_ALMACEN_DESTINO;
                endif;

                //ALMACÉN/ALMACEN DESTINO
                $RefAlmacenIncidencia    = "";
                $nombreAlmacenIncidencia = "";
                if ($idAlmacenIncidencia != ""):
                    $GLOBALS["NotificaErrorPorEmail"] = "No";
                    $rowAlmacenIncidencia             = $bd->VerReg("ALMACEN", "ID_ALMACEN", $idAlmacenIncidencia, "No");
                    $RefAlmacenIncidencia             = $rowAlmacenIncidencia->REFERENCIA;
                    $nombreAlmacenIncidencia          = $rowAlmacenIncidencia->NOMBRE;
                endif;


                //MATERIAL
                $refMaterial    = "";
                $nombreMaterial = "";
                if ($rowIncidencia->ID_MATERIAL != NULL):
                    $rowMaterial    = $bd->VerReg("MATERIAL", "ID_MATERIAL", $rowIncidencia->ID_MATERIAL);
                    $refMaterial    = $rowMaterial->REFERENCIA_SGA;
                    $nombreMaterial = ($idIdiomaInforme == "ESP" ? $rowMaterial->DESCRIPCION : $rowMaterial->DESCRIPCION_EN);
                endif;


                //BUSCAMOS PEDIDO Y PROVEEDOR
                $txPedido        = "";
                $txPedidoLinea   = "";
                $refProveedor    = "";
                $nombreProveedor = "";
                $rowPedido       = NULL;

                //MOVIMIENTO_ENTRADA
                $rowMov = "";
                if ($rowIncidencia->ID_MOVIMIENTO_ENTRADA != NULL):
                    $rowMov           = $bd->VerReg("MOVIMIENTO_ENTRADA", "ID_MOVIMIENTO_ENTRADA", $rowIncidencia->ID_MOVIMIENTO_ENTRADA);
                    $albaranProveedor = $rowMov->ALBARAN;
                endif;

                //CALIDAD Y RECEPCIÓN LOGÍSTICA
                $selTipoIncidencia = $rowIncidencia->TIPO_INCIDENCIA;
                if (($selTipoIncidencia == 'RecepcionProveedor') || ($selTipoIncidencia == 'RecepcionCalidad') || ($selTipoIncidencia == 'TransporteConstructivo') || ($selTipoIncidencia == 'AlmacenConstruccion')):

                    //MOVIMIENTO_ENTRADA_LINEA
                    if ($rowMovLinea != false):
                        $idPedido      = $rowMovLinea->ID_PEDIDO;
                        $idPedidoLinea = $rowMovLinea->ID_PEDIDO_LINEA;

                        //PEDIDO_ENTRADA
                        if ($idPedido != NULL):
                            $rowPedido = $bd->VerReg("PEDIDO_ENTRADA", "ID_PEDIDO_ENTRADA", $idPedido);
                            $txPedido  = $rowPedido->PEDIDO_SAP;

                            if ($rowMov->TIPO_MOVIMIENTO == 'PedidoDevolucionVenta'):
                                //CLIENTE;
                            else:
                                //PROVEEDOR
                                $rowProveedor    = $bd->VerReg("PROVEEDOR", "ID_PROVEEDOR", $rowPedido->ID_PROVEEDOR);
                                $refProveedor    = $rowProveedor->REFERENCIA;
                                $nombreProveedor = $rowProveedor->NOMBRE;
                            endif;
                        endif;

                        //PEDIDO_ENTRADA_LINEA
                        if ($idPedidoLinea != NULL):
                            $rowPedidoLinea = $bd->VerReg("PEDIDO_ENTRADA_LINEA", "ID_PEDIDO_ENTRADA_LINEA", $idPedidoLinea);
                            $txPedidoLinea  = (int)$rowPedidoLinea->LINEA_PEDIDO_SAP;
                        endif;
                    endif;
                endif;

                //GENERAMOS EL ARRAY
                $arrLinea                    = array();
                $arrLinea['RefAlmacen']      = $RefAlmacenIncidencia;
                $arrLinea['NombreAlmacen']   = $nombreAlmacenIncidencia;
                $arrLinea['RefProveedor']    = $refProveedor;
                $arrLinea['NombreProveedor'] = $nombreProveedor;
                $arrLinea['RefMaterial']     = $refMaterial;
                $arrLinea['NombreMaterial']  = $nombreMaterial;
                $arrLinea['TipoIncidencia']  = ($idIdiomaInforme == "ESP" ? $rowIncidencia->NOMBRE_ESP : $rowIncidencia->NOMBRE_ENG);
                $arrLinea['PedidoSAP']       = $txPedido;
                $arrLinea['LineaPedidoSAP']  = $txPedidoLinea;
                $arrLinea['FechaCreacion']   = $rowIncidencia->FECHA;


                $arrIncidencias[$rowIncidencia->ID_INCIDENCIA_CALIDAD] = $arrLinea;
            endwhile;
        endif;


        //AÑADIMOS EL DOCUMENTO AL ARRAY DEVUELTO
        $arrayInforme['arrIncidencias'] = $arrIncidencias;


        return $arrayInforme;
    }

    /**
     * actualizarEstadoMovimientoEntrada
     */
    function actualizarEstadoMovimientoEntrada ($idMovimientoEntrada){
        global $bd;

        //BUSCO EL MOVIMIENTO DE SALIDA
        $rowMov                  = $bd->VerReg("MOVIMIENTO_ENTRADA", "ID_MOVIMIENTO_ENTRADA", $idMovimientoEntrada);
        $rowMovRecepcion         = $bd->VerReg("MOVIMIENTO_RECEPCION", "ID_MOVIMIENTO_RECEPCION", $rowMov->ID_MOVIMIENTO_RECEPCION);

        $estado = $rowMov->ESTADO;

        //NUMERO DE LINEAS DEL MOVIMIENTO
        $numLineasActivas = $bd->NumRegsTabla("MOVIMIENTO_ENTRADA_LINEA", "ID_MOVIMIENTO_ENTRADA = $rowMov->ID_MOVIMIENTO_ENTRADA AND CANTIDAD <> 0 AND BAJA = 0 AND LINEA_ANULADA = 0");

        //NUMERO DE LINEAS TOTALES DEL MOVIMIENTO
        $numLineasTotal = $bd->NumRegsTabla("MOVIMIENTO_ENTRADA_LINEA", "ID_MOVIMIENTO_ENTRADA = $rowMov->ID_MOVIMIENTO_ENTRADA AND CANTIDAD <> 0");

        //NUMERO DE LINEAS DADAS DE BAJA DEL MOVIMIENTO
        $numLineasAnuladas = $bd->NumRegsTabla("MOVIMIENTO_ENTRADA_LINEA", "ID_MOVIMIENTO_ENTRADA = $rowMov->ID_MOVIMIENTO_ENTRADA AND CANTIDAD <> 0 AND (BAJA = 1 OR LINEA_ANULADA = 1)");

        //NUMERO DE LINEAS ENVIADAS A SAP
        $numLineasEnviadasSAP = $bd->NumRegsTabla("MOVIMIENTO_ENTRADA_LINEA", "ID_MOVIMIENTO_ENTRADA = $rowMov->ID_MOVIMIENTO_ENTRADA AND CANTIDAD <> 0 AND BAJA = 0 AND ENVIADO_SAP = 1");

        //NUMERO DE LINEAS CON TRANSFERENCIAS
        $numLineasTransferencias = $bd->NumRegsTabla("MOVIMIENTO_TRANSFERENCIA", "ID_MOVIMIENTO_ENTRADA = $rowMov->ID_MOVIMIENTO_ENTRADA AND BAJA = 0");

        if ($numLineasTotal == $numLineasAnuladas):
            if($rowMov->ADJUNTO == null):
                $estado = "Ubicado";
            else:
                $estado = "Escaneado y Finalizado";
            endif;
        elseif ($numLineasTotal == 0):
            $estado = "En Proceso";
        elseif ($rowMovRecepcion->VIA_RECEPCION == 'WEB'):
            if($numLineasActivas == $numLineasEnviadasSAP):
                if($rowMov->ADJUNTO == null):
                    $estado = "Ubicado";
                else:
                    $estado = "Escaneado y Finalizado";
                endif;
            endif;
        elseif ($rowMovRecepcion->VIA_RECEPCION == 'PDA'):
            if($numLineasActivas - $numLineasTransferencias == $numLineasActivas):
                $estado = "Procesado";
            else:
                $sqlSumCtdLineas = "SELECT SUM(CANTIDAD) AS CANTIDAD
                                         FROM MOVIMIENTO_ENTRADA_LINEA
                                         WHERE ID_MOVIMIENTO_ENTRADA = $rowMov->ID_MOVIMIENTO_ENTRADA AND BAJA = 0 AND LINEA_ANULADA = 0";
                $resultSumCtdLineas = $bd->ExecSQL($sqlSumCtdLineas);
                $rowCtdLineas = $bd->SigReg($resultSumCtdLineas);

                $sqlSumCtdTrans = "SELECT SUM(CANTIDAD) AS CANTIDAD
                                         FROM MOVIMIENTO_TRANSFERENCIA
                                         WHERE ID_MOVIMIENTO_ENTRADA = $rowMov->ID_MOVIMIENTO_ENTRADA AND BAJA = 0";
                $resultSumCtdTrans = $bd->ExecSQL($sqlSumCtdTrans);
                $rowCtdTrans = $bd->SigReg($resultSumCtdTrans);
                if($rowCtdLineas->CANTIDAD == $rowCtdTrans->CANTIDAD):
                    if($rowMov->ADJUNTO == null):
                        $estado = "Ubicado";
                    else:
                        $estado = "Escaneado y Finalizado";
                    endif;
                else:
                    $estado = "En Ubicacion";
                endif;
            endif;
        else:
            $estado = "En Proceso";
        endif;

        return $estado;

    }

} // FIN CLASE
?>