<?php

# reserva
# Clase reserva contiene todas las funciones necesarias para la interaccion con la clase reserva
# Se incluira en las sesiones

class reserva
{

    function __construct()
    {
    } // Fin reserva

    /**
     * DEVUELVE LOS TIPO DE PEDIDO QUE ADMITEN DEMANDA
     */
    function get_tipos_pedido_admiten_demanda()
    {
        $array_respuesta = array('Traslado', 'Venta', 'Componentes a Proveedor', 'Pendientes de Ordenes Trabajo'); //TODO: ¿'Traslados OM Construccion',?

        return $array_respuesta;

    }

    /**DEVUELVE EL TIPO DE TRASLADO SAP QUE ADMITE DEMANDA **/
    function get_tipos_traslado_admiten_demanda()
    {
        $array_respuesta = array('ZTRA', 'ZTRB', 'ZTRC', 'ZTRD');

        return $array_respuesta;
    }

    /**
     * @param string $tipoPedido
     * @param string $tipoPedidoSAP
     * @return bool DEVUELVE SI EL TIPO DE PEDIDO ADMITE DEMANDAS
     */
    function pedido_admite_demanda($tipoPedido, $tipoPedidoSAP = "")
    {
        $admite_demanda = false;
        if (in_array($tipoPedido, (array) $this->get_tipos_pedido_admiten_demanda())):

            //PARA TRASLADOS COMPROBAMOS TAMBIEN SU TIPO PEDIDO SAP
            if ($tipoPedido == 'Traslado'):
                if (in_array($tipoPedidoSAP, (array) $this->get_tipos_traslado_admiten_demanda())):
                    $admite_demanda = true;
                endif;
            else:
                $admite_demanda = true;
            endif;
        endif;

        return $admite_demanda;

    }


    /**
     * @string $tipoObjeto Puede ser Pedido, OT o Manual
     * @string $idObjeto PSL u OTL (idMaterial para reservas manuales)
     * @double $cantidadDemandada La cantidad Demandada. Si ya existe una demanda se sumará esta cantidad.
     * @string $idNecesidad Cuando es de pedido, puede indicar la necesidad
     * @string $idAlmacen: si la demanda es de tipo Manual, se le pasa el almacén
     * @string $idUsuarioSolicitante: si la demanda es de tipo Manual, usuario que ha solicitado realizar la demanda
     * CREA LA DEMANDA Y LA COLA DE RESERVAS
     * SI LA DEMANDA EXISTE, NO ACTUALIZA LA FECHA DE LA DEMANDA, ESO SE DEBE HACER CON modificar_demanda
     * $arr_resultado Devuelve 'error' o bien 'idDemanda' e 'idColaReserva'. Si no aplica en el centro, devuelve array vacio
     */
    function creacion_demanda($tipoObjeto, $idObjeto, $cantidadDemandada, $idNecesidad = NULL, $idAlmacen = '', $idUsuarioSolicitante = '')
    {
        global $bd;
        global $html;
        global $auxiliar;
        global $administrador;

        $arr_resultado = array();

        //BUSCAMOS EL OBJETO
        $rowMaterial          = false;
        $rowPedidoSalidaLinea = false;
        $rowNecesidad         = false;
        $rowOrdenTrabajoLinea = false;
        $rowAlmacenDemanda    = false;
        $rowAlmacenLeadtime   = false;
        $fecha_demanda        = "";

        switch ($tipoObjeto):
            case "Pedido":
                $GLOBALS["NotificaErrorPorEmail"] = "No";
                $rowPedidoSalidaLinea             = $bd->VerReg("PEDIDO_SALIDA_LINEA", "ID_PEDIDO_SALIDA_LINEA", $idObjeto, "No");
                if ($rowPedidoSalidaLinea != false):

                    //COMPROBAMOS TIPO DE PEDIDO
                    $rowPedido = $bd->VerReg("PEDIDO_SALIDA", "ID_PEDIDO_SALIDA", $rowPedidoSalidaLinea->ID_PEDIDO_SALIDA);

                    //SI TIENE ACTIVA LA GESTION DE DEMANDAS
                    if ($this->pedido_admite_demanda($rowPedido->TIPO_PEDIDO, $rowPedido->TIPO_PEDIDO_SAP) == false):
                        //NO CREAMOS DEMANDA
                        return $arr_resultado;
                    endif;

                    //BUSCAMOS MATERIAL
                    $rowMaterial = $bd->VerReg("MATERIAL", "ID_MATERIAL", $rowPedidoSalidaLinea->ID_MATERIAL);

                    //BUSAMOS ALMACEN LeadTime (Destino en caso de existir)
                    if ($rowPedidoSalidaLinea->ID_ALMACEN_DESTINO != NULL):
                        $rowAlmacenLeadtime = $bd->VerReg("ALMACEN", "ID_ALMACEN", $rowPedidoSalidaLinea->ID_ALMACEN_DESTINO);
                    else:
                        $rowAlmacenLeadtime = $bd->VerReg("ALMACEN", "ID_ALMACEN", $rowPedidoSalidaLinea->ID_ALMACEN_ORIGEN);
                    endif;

                    //BUSCAMOS ALMACEN DEMANDA (Se ha decidido que sea origen tambien)
                    $rowAlmacenDemanda = $bd->VerReg("ALMACEN", "ID_ALMACEN", $rowPedidoSalidaLinea->ID_ALMACEN_ORIGEN);

                    //FECHA DEMANDA ES LA Fecha Entrega
                    if ($rowPedidoSalidaLinea->FECHA_ENTREGA != "" && $rowPedidoSalidaLinea->FECHA_ENTREGA != "0000-00-00"):
                        $fecha_demanda = $rowPedidoSalidaLinea->FECHA_ENTREGA;
                    else://NUNCA DEBERIA DARSE QUE NO ESTE DEFINIDA LA FECHA ENTREGA
                        $fecha_demanda = date("Y-m-d");
                    endif;

                else:
                    $arr_resultado['error'] = $auxiliar->traduce("El Registro Introducido no existe en Base de datos", $administrador->ID_IDIOMA) . " " . $auxiliar->traduce("Pedido", $administrador->ID_IDIOMA);
                endif;

                //SI VIENE NECESIDAD, LA BUSCAMOS
                if ($idNecesidad != NULL):
                    $GLOBALS["NotificaErrorPorEmail"] = "No";
                    $rowNecesidad                     = $bd->VerReg("NECESIDAD", "ID_NECESIDAD", $idNecesidad, "No");
                    if ($rowNecesidad == false):
                        $arr_resultado['error'] = $auxiliar->traduce("El Registro Introducido no existe en Base de datos", $administrador->ID_IDIOMA) . " " . $auxiliar->traduce("Necesidad", $administrador->ID_IDIOMA);
                    endif;
                endif;

                break;

            case "OT":
                $GLOBALS["NotificaErrorPorEmail"] = "No";
                $rowOrdenTrabajoLinea             = $bd->VerReg("ORDEN_TRABAJO_LINEA", "ID_ORDEN_TRABAJO_LINEA", $idObjeto, "No");
                if ($rowOrdenTrabajoLinea != false):

                    //BUSCAMOS MATERIAL
                    $rowMaterial = $bd->VerReg("MATERIAL", "ID_MATERIAL", $rowOrdenTrabajoLinea->ID_MATERIAL);

                    //BUSAMOS ALMACEN RESERVA (Origen)
                    $rowAlmacenLeadtime = $bd->VerReg("ALMACEN", "ID_ALMACEN", $rowOrdenTrabajoLinea->ID_ALMACEN);

                    //BUSCAMOS ALMACEN DEMANDA (Se ha decidido que sea origen tambien)
                    $rowAlmacenDemanda = $bd->VerReg("ALMACEN", "ID_ALMACEN", $rowOrdenTrabajoLinea->ID_ALMACEN);

                    //FECHA DEMANDA ES LA Fecha Entrega
                    $fecha_demanda = $rowOrdenTrabajoLinea->FECHA_PLANIFICADA;

                else:
                    $arr_resultado['error'] = $auxiliar->traduce("El Registro Introducido no existe en Base de datos", $administrador->ID_IDIOMA) . " " . $auxiliar->traduce("OT", $administrador->ID_IDIOMA);
                endif;

                break;

            case "Manual":
                $GLOBALS["NotificaErrorPorEmail"] = "No";
                //BUSCAMOS MATERIAL
                $rowMaterial = $bd->VerReg("MATERIAL", "ID_MATERIAL", $idObjeto);

                //BUSAMOS ALMACEN RESERVA (Origen)
                $rowAlmacenLeadtime = $bd->VerReg("ALMACEN", "ID_ALMACEN", $idAlmacen);

                //BUSCAMOS ALMACEN DEMANDA (Se ha decidido que sea origen tambien)
                $rowAlmacenDemanda = $bd->VerReg("ALMACEN", "ID_ALMACEN", $idAlmacen);

                //USUARIO SOLICITANTE
                //BUSCAMOS ALMACEN DEMANDA (Se ha decidido que sea origen tambien)
                $rowUsuarioSolicitante = $bd->VerReg("ADMINISTRADOR", "ID_ADMINISTRADOR", $idUsuarioSolicitante);

                $fecha_demanda = date("Y-m-d");

                break;

            default:
                $arr_resultado['error'] = $auxiliar->traduce("Tipo de Demanda no contemplado", $administrador->ID_IDIOMA);
                break;
        endswitch;

        //SI HAY ERROR, DEVOLVEMOS
        if (isset($arr_resultado['error']) && ($arr_resultado['error'] != "")):
            return $arr_resultado;
        endif;

        //BUSCAMOS EL CENTRO DE LA DEMANDA
        $rowCentroReserva = $bd->VerReg("CENTRO", "ID_CENTRO", $rowAlmacenDemanda->ID_CENTRO);

        //SI TIENE ACTIVA LA GESTION DE DEMANDAS
        if ($rowCentroReserva->GESTION_RESERVAS == 1):

            //BUSCAMOS SI EXISTE YA UNA DEMANDA
            $rowDemanda = $this->get_demanda($tipoObjeto, $idObjeto, $idNecesidad);
            if ($rowDemanda != false):

                //ACTUALIZAMOS LA CANTIDAD DE LA DEMANDA, ACTUALIZAMOS LA COLA
                $sqlUpdate = "UPDATE DEMANDA SET 
                                  ESTADO = 'Activa'
                                , ID_MATERIAL = $rowMaterial->ID_MATERIAL
                                , CANTIDAD_DEMANDA =  CANTIDAD_DEMANDA + $cantidadDemandada
                                , CANTIDAD_PENDIENTE_RESERVAR = CANTIDAD_PENDIENTE_RESERVAR + $cantidadDemandada
                                , ID_ALMACEN_DEMANDA = $rowAlmacenDemanda->ID_ALMACEN
                                , BAJA = 0
                                WHERE ID_DEMANDA = $rowDemanda->ID_DEMANDA";
                $bd->ExecSQL($sqlUpdate);
                $idDemanda = $rowDemanda->ID_DEMANDA;

                //BUSCO LA DEMANDA ACTUALIZADA
                $rowDemandaActualizada = $bd->VerReg("DEMANDA", "ID_DEMANDA", $idDemanda);

                //SI LA CANTIDAD PENDIENTE RESERVAR VA A SER NEGATIVA DEVUELVO UN ERROR
                if ($rowDemandaActualizada->CANTIDAD_PENDIENTE_RESERVAR < (EPSILON_SISTEMA * -1)):
                    $arr_resultado['error'] = $auxiliar->traduce("La cantidad pendiente de reservar en la demanda es negativa", $administrador->ID_IDIOMA) . ". " . $auxiliar->traduce("Demanda", $administrador->ID_IDIOMA) . ": " . $rowDemandaActualizada->ID_DEMANDA;

                    return $arr_resultado;
                endif;

                //COMPROBAMOS SI LA CANTIDAD DEMANDADA ES MAYOR O MENOR QUE 0 PARA TRAZAR CORRECTAMENTE
                if ($cantidadDemandada > EPSILON_SISTEMA):
                    // LOG MOVIMIENTOS
                    $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Demanda", $idDemanda, "Aumentar Cantidad: $cantidadDemandada", 'DEMANDA', $rowDemanda, $rowDemandaActualizada);
                else:// LOG MOVIMIENTOS
                    $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Demanda", $idDemanda, "Reducir Cantidad: " . abs( (float)$cantidadDemandada), 'DEMANDA', $rowDemanda, $rowDemandaActualizada);
                endif;

                if ($tipoObjeto != 'Manual'):
                    //COMPROBAMOS SI LA CANTIDAD DEMANDADA ES MAYOR O MENOR QUE 0 PARA TRAZAR CORRECTAMENTE
                    if ($cantidadDemandada > EPSILON_SISTEMA):
                        //ACTUALIZAMOS LA COLA
                        $arr_resultado['idColaReserva'] = $this->poner_cantidad_cola($idDemanda, $cantidadDemandada, "Aumentar Cantidad: $cantidadDemandada");
                    else:
                        //ACTUALIZAMOS LA COLA
                        $arr_resultado['idColaReserva'] = $this->poner_cantidad_cola($idDemanda, $cantidadDemandada, "Reducir Cantidad: " . abs( (float)$cantidadDemandada));
                    endif;
                endif;

            else://BUSCAMOS SI HAY UNA DEMANDA DADA DE BAJA, SE USARA LA MISMA PARA NO PERDER EL ID

                //OBTENEMOS EL GRUPO DEMANDA
                $idPrioridadDemanda = $this->get_prioridad_demanda($tipoObjeto, $idObjeto, $idNecesidad);

                //OBTENEMOS LA FECHA ESTIMADA RESERVA
                $arr_fechas = $this->get_fecha_estimada_reserva($fecha_demanda, $rowMaterial->ID_MATERIAL, $rowAlmacenLeadtime->ID_ALMACEN, $idPrioridadDemanda);
                if (isset($arr_fechas['error']) && ($arr_fechas['error'] != "")):
                    $arr_resultado['error'] = $arr_fechas['error'];

                    return $arr_resultado;
                endif;

                //BUSCAMOS SI EXISTE YA UNA DEMANDA
                $rowDemanda = $this->get_demanda($tipoObjeto, $idObjeto, $idNecesidad, "Si");

                if ($rowDemanda != false): //LA DAMOS DE ALTA

                    //SI LA CANTIDAD DE LA DEMANDA ES SUPERIOR A 0, ACTUALIZAMOS LA DEMANDA
                    if ($cantidadDemandada > EPSILON_SISTEMA):
                        $sqlUpdate = "UPDATE DEMANDA SET 
                                         ESTADO = 'Activa'
                                        , ID_MATERIAL = $rowMaterial->ID_MATERIAL
                                        , CANTIDAD_DEMANDA = $cantidadDemandada
                                        , CANTIDAD_PENDIENTE_RESERVAR = $cantidadDemandada
                                        , ID_PRIORIDAD_DEMANDA = " . ($idPrioridadDemanda != "" ? $idPrioridadDemanda : "NULL") . "
                                        , ID_ALMACEN_DEMANDA = $rowAlmacenDemanda->ID_ALMACEN
                                        , FECHA_DEMANDA = '" . $fecha_demanda . "'
                                        , ID_CLAVE_APROVISIONAMIENTO_ESPECIAL = " . ($arr_fechas['cae'] == NULL ? 'NULL' : ("'" . $arr_fechas['cae']) . "'") . "
                                        , LEAD_TIME = " . ($arr_fechas['lead_time'] == NULL ? 'NULL' : ("'" . $arr_fechas['lead_time']) . "'") . "
                                        , DEADLINE = '" . $arr_fechas['deadline'] . "'
                                        , MARGEN_RESERVA = '" . $arr_fechas['margen'] . "'
                                        , FECHA_ESTIMADA_RESERVA = '" . $arr_fechas['fecha_estimada_reserva'] . "'
                                        , BAJA = 0
                                        WHERE ID_DEMANDA = $rowDemanda->ID_DEMANDA";
                        $bd->ExecSQL($sqlUpdate);
                        $idDemanda = $rowDemanda->ID_DEMANDA;

                        // LOG MOVIMIENTOS
                        $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Creación", "Demanda", $idDemanda, "Reactivación");
                    else:
                        $sqlUpdate = "UPDATE DEMANDA SET 
                                         ESTADO = 'Finalizada'
                                        , CANTIDAD_DEMANDA = 0
                                        , CANTIDAD_PENDIENTE_RESERVAR = 0
                                        , BAJA = 1
                                        WHERE ID_DEMANDA = $rowDemanda->ID_DEMANDA";
                        $bd->ExecSQL($sqlUpdate);
                        $idDemanda = $rowDemanda->ID_DEMANDA;

                        // LOG MOVIMIENTOS
                        $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Demanda", $idDemanda, "Anulacion Demanda: Linea Necesidad Desasignada");
                    endif;

                else: //CREAMOS LA DEMANDA
                    $sqlUsuarioSolicitante = '';
                    if ($tipoObjeto == 'Manual'):
                        $sqlUsuarioSolicitante = ", USUARIO_SOLICITUD = $rowUsuarioSolicitante->ID_ADMINISTRADOR";
                    endif;
                    $sqlInsert = "INSERT INTO DEMANDA SET 
                                 TIPO_DEMANDA = '" . $tipoObjeto . "'
                                , ESTADO = 'Activa'
                                , FECHA_CREACION = '" . $arr_fechas['fecha_actual'] . "'
                                , ID_MATERIAL = $rowMaterial->ID_MATERIAL
                                , CANTIDAD_DEMANDA = $cantidadDemandada
                                , CANTIDAD_PENDIENTE_RESERVAR = $cantidadDemandada
                                , ID_PRIORIDAD_DEMANDA = " . ($idPrioridadDemanda != "" ? $idPrioridadDemanda : "NULL") . "
                                , ID_ALMACEN_DEMANDA = $rowAlmacenDemanda->ID_ALMACEN
                                , ID_PEDIDO_SALIDA_LINEA = " . ($rowPedidoSalidaLinea != false ? $rowPedidoSalidaLinea->ID_PEDIDO_SALIDA_LINEA : "NULL") . "
                                , ID_NECESIDAD = " . ($rowNecesidad != false ? $rowNecesidad->ID_NECESIDAD : "NULL") . "
                                , ID_ORDEN_TRABAJO_LINEA = " . ($rowOrdenTrabajoLinea != false ? $rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO_LINEA : "NULL") . "
                                , FECHA_DEMANDA = '" . $fecha_demanda . "'
                                , ID_CLAVE_APROVISIONAMIENTO_ESPECIAL = " . ($arr_fechas['cae'] == NULL ? 'NULL' : ("'" . $arr_fechas['cae']) . "'") . "
                                , LEAD_TIME = " . ($arr_fechas['lead_time'] == NULL ? 'NULL' : ("'" . $arr_fechas['lead_time']) . "'") . "
                                , DEADLINE = '" . $arr_fechas['deadline'] . "'
                                , MARGEN_RESERVA = '" . $arr_fechas['margen'] . "'
                                , FECHA_ESTIMADA_RESERVA = '" . $arr_fechas['fecha_estimada_reserva'] . "'
                                , USUARIO_CREACION = $administrador->ID_ADMINISTRADOR
                                $sqlUsuarioSolicitante";
                    $bd->ExecSQL($sqlInsert);
                    $idDemanda = $bd->IdAsignado();

                    // LOG MOVIMIENTOS
                    $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Creación", "Demanda", $idDemanda, '');

                endif;

                if ($tipoObjeto != 'Manual'):
                    //GENEMOS LA COLA DE RESERVA
                    $idColaReserva                  = $this->poner_cantidad_cola($idDemanda, $cantidadDemandada, '');
                    $arr_resultado['idColaReserva'] = $idColaReserva;
                endif;
            endif;

            $arr_resultado['idDemanda'] = $idDemanda;

            //SI LA DEMANDA ES DE TIPO OT, ACTUALIZAMOS LAS TRANSFERENCIAS DE PENDIENTES SI LAS HAY
            if ($rowDemanda->TIPO_DEMANDA == 'OT'):
                $this->actualizar_transferencias_pendientes($idDemanda);
            endif;

        endif;//FIN CENTRO CON RESERVAS ACTIVADAS

        return $arr_resultado;
    }

    /**
     * @string $idDemanda Demanda a anula
     * ANULA LO RELACIONADO CON LA DEMANDA
     * $arr_resultado Devuelve 'error' o vacío si ha ido bien
     */
    function anular_demanda($idDemanda, $observacionesLog = "")
    {
        global $bd;
        global $html;
        global $auxiliar;
        global $administrador;

        $arr_resultado = array();

        //BUSCAMOS SI EXISTE YA UNA DEMANDA
        $rowDemanda = $bd->VerReg("DEMANDA", "ID_DEMANDA", $idDemanda);
        if ($rowDemanda != false):

            //SI HAY RESERVAS SERVIDAS (EN CASO DE PEDIDOS, LAS LINEAS ESTARIAN PREPARADAS POR LO QUE NO DEBERIA LLAMARSE A ESTA FUNCION) NO SE PUEDE ANULAR
            $resultLineas = $this->get_lineas_reservas_demanda($rowDemanda->ID_DEMANDA, 'Finalizada');
            if (($resultLineas != false) && ($bd->NumRegs($resultLineas) > 0)):
                $arr_resultado['error'] = $auxiliar->traduce("No es posible anular la demanda con reservas ya finalizadas", $administrador->ID_IDIOMA);

                return $arr_resultado;
            endif;

            //BUSCAMOS LAS LINEAS EN ESTADO RESERVADA Y LIBERAMOS EL STOCK
            $cantidadReservada = $this->get_cantidad_reserva_demanda($rowDemanda->ID_DEMANDA, 'Reservada');
            if ($cantidadReservada > EPSILON_SISTEMA):
                //LIBERAR STOCK RESERVADO Y METERLO EN LA COLA PROGRAMADA
                $arr_anulacion = $this->anular_reserva($rowDemanda->ID_DEMANDA, $cantidadReservada);
                if (isset($arr_anulacion['error']) && $arr_anulacion['error'] != "")://SI VIENE ERROR
                    $arr_respuesta['error'] = $arr_anulacion['error'];

                    return $arr_respuesta;
                endif;
            endif;

            //DAMOS DE BAJA LAS RESERVAS Y SUS LINEAS
            $rowReserva = $this->get_reserva_demanda($rowDemanda->ID_DEMANDA);
            if ($rowReserva != false):
                //LINEAS
                $sqlUpdate = "UPDATE RESERVA_LINEA SET BAJA = 1 WHERE ID_RESERVA = $rowReserva->ID_RESERVA";
                $bd->ExecSQL($sqlUpdate);

                //RESERVA
                $sqlUpdate = "UPDATE RESERVA SET BAJA = 1 WHERE ID_RESERVA = $rowReserva->ID_RESERVA";
                $bd->ExecSQL($sqlUpdate);

                //LOG
                $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Anulación", "Reserva", $rowReserva->ID_RESERVA, "Anulacion Demanda");
            endif;

            //DAMOS DE BAJA LA COLA
            $rowCola = $this->get_cola_reserva($rowDemanda->ID_DEMANDA);
            if ($rowCola != false):

                //LINEAS
                $sqlUpdate = "UPDATE COLA_RESERVA_LINEA SET BAJA = 1 WHERE ID_COLA_RESERVA = $rowCola->ID_COLA_RESERVA";
                $bd->ExecSQL($sqlUpdate);

                //COLA
                $sqlUpdate = "UPDATE COLA_RESERVA SET BAJA = 1 WHERE ID_COLA_RESERVA = $rowCola->ID_COLA_RESERVA";
                $bd->ExecSQL($sqlUpdate);

                //LOG
                $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Anulación", "Cola Reserva", $rowCola->ID_COLA_RESERVA, "Anulacion Demanda");
            endif;

            //DAMOS DE BAJA LA DEMANDA
            $sqlUpdate = "UPDATE DEMANDA SET BAJA = 1 WHERE ID_DEMANDA = $rowDemanda->ID_DEMANDA";
            $bd->ExecSQL($sqlUpdate);

            //LOG
            $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Anulación", "Demanda", $idDemanda, "Anulacion Demanda " . $observacionesLog);

            //SI LA DEMANDA ES DE TIPO OT, ACTUALIZAMOS LAS TRANSFERENCIAS DE PENDIENTES SI LAS HAY
            if ($rowDemanda->TIPO_DEMANDA == 'OT'):
                $this->actualizar_transferencias_pendientes($rowDemanda->ID_DEMANDA);
            endif;
        endif;

        return $arr_resultado;
    }

    /**
     * @string $tipoObjeto Puede ser Pedido u OT
     * @string $idObjeto PSL o OTL
     * @string $idNecesidad Cuando es de pedido, puede indicar la necesidad
     * DEVUELVE EL GRUPO DE DEMANDA AL QUE PERTENECE EL OBJETO
     */
    function get_prioridad_demanda($tipoObjeto, $idObjeto, $idNecesidad = NULL)
    {
        global $bd;
        global $html;
        global $auxiliar;
        global $administrador;


        //GUARDAREMOS LOS GRUPOS DE DEMANDA Y OBTENDREMOS EL QUE MAS PRIORIDAD TENGA
        $grupos_demanda     = "";
        $coma               = "";
        $idPrioridadDemanda = "";

        //BUSCAMOS EL OBJETO
        switch ($tipoObjeto):
            case "Pedido":
                //BUSCAMOS EL PEDIDO SALIDA LINEA
                $rowPedidoSalidaLinea = $bd->VerReg("PEDIDO_SALIDA_LINEA", "ID_PEDIDO_SALIDA_LINEA", $idObjeto);

                //BUSCAMOS EL PEDIDO DE SALIDA
                $rowPedidoSalida = $bd->VerReg("PEDIDO_SALIDA", "ID_PEDIDO_SALIDA", $rowPedidoSalidaLinea->ID_PEDIDO_SALIDA);

                //SI EL PEDIDO ES DE PENDIENTE
                if ($rowPedidoSalida->TIPO_PEDIDO == 'Pendientes de Ordenes Trabajo' && $rowPedidoSalidaLinea->ID_ORDEN_TRABAJO_LINEA != NULL):
                    //BUSCO LA LINEA DE OT RELACIONADA CON LA LINEA DE PEDIDO
                    $rowOrdenTrabajoLinea = $bd->VerReg("ORDEN_TRABAJO_LINEA", "ID_ORDEN_TRABAJO_LINEA", $rowPedidoSalidaLinea->ID_ORDEN_TRABAJO_LINEA);

                    //CALCULO EL GRUPO DEMANDA EN FUNCION DE LA PRIORIDAD DEL PENDIENTE
                    switch ($rowOrdenTrabajoLinea->PRIORIDAD_NUMERO_PENDIENTE):
                        case '1-Alta':
                            $grupos_demanda .= $coma . "'PP1'";
                            $coma           = ",";
                            break;
                        case '2-Media':
                            $grupos_demanda .= $coma . "'PP2'";
                            $coma           = ",";
                            break;
                        case '3-Baja':
                            $grupos_demanda .= $coma . "'PP3'";
                            $coma           = ",";
                            break;
                    endswitch;
                endif; //SI EL PEDIDO ES DE PENDIENTE

                //COMPRUEBO SI LA LINEA DE PEDIDO TIENE OT LINEA ASOCIADA
                if (($rowPedidoSalida->TIPO_PEDIDO == 'Traslado') &&
                    (($rowPedidoSalida->TIPO_TRASLADO == 'Planificado Material Obligatorio') || ($rowPedidoSalida->TIPO_TRASLADO == 'Planificado Material No Obligatorio') || ($rowPedidoSalida->TIPO_TRASLADO == 'Planificado Material Indivisible'))
                ):
                    $grupos_demanda .= $coma . "'P'";
                    $coma           = ",";
                    break;
                endif;// FIN COMPRUEBO SI LA LINEA DE PEDIDO TIENE OT LINEA ASOCIADA

                //SEGUN EL TIPO DE PEDIDO SAP
                switch ($rowPedidoSalida->TIPO_PEDIDO_SAP):
                    case 'ZTRA':
                    case 'ZTRC':
                        //BUSCO EL TIPO DE BLOQUEO 'Preventivo'
                        $rowTipoBloqueoPreventivo = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", 'SP');

                        if ($rowPedidoSalidaLinea->ID_TIPO_BLOQUEO == $rowTipoBloqueoPreventivo->ID_TIPO_BLOQUEO):
                            //SI ES UN TRASLADO PLANIFICADO
                            $grupos_demanda .= $coma . "'PTP'";
                            $coma           = ",";
                        else:
                            //SI ES UN TRASLADO CORRECTIVO
                            $grupos_demanda .= $coma . "'PTC'";
                            $coma           = ",";
                        endif;
                        break;
                    case 'ZTRB':
                    case 'ZTRD':
                        //BUSCO EL TIPO DE BLOQUEO 'Preventivo'
                        $rowTipoBloqueoPreventivo = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", 'SP');

                        if ($rowPedidoSalidaLinea->ID_TIPO_BLOQUEO == $rowTipoBloqueoPreventivo->ID_TIPO_BLOQUEO):
                            //SI ES UN TRASLADO PLANIFICADO
                            $grupos_demanda .= $coma . "'PMSP'";
                            $coma           = ",";
                        else:
                            //SI ES UN TRASLADO CORRECTIVO
                            $grupos_demanda .= $coma . "'PMSC'";
                            $coma           = ",";
                        endif;
                        break;
                    default:
                        if ($rowPedidoSalida->TIPO_PEDIDO == 'Venta'):
                            //SI ES VENTA
                            $grupos_demanda .= $coma . "'PV'";
                            $coma           = ",";
                        elseif ($rowPedidoSalida->TIPO_PEDIDO == 'Componentes a Proveedor'):
                            //SI ES COMPONENTES A PROVEEDOR
                            $grupos_demanda .= $coma . "'CAP'";
                            $coma           = ",";
                        endif;
                        break;
                endswitch;
                //FIN SEGUN EL TIPO DE PEDIDO SAP

                //SI VIENE NECESIDAD, LA BUSCAMOS
                if ($idNecesidad != NULL):
                    $rowNecesidad = $bd->VerReg("NECESIDAD", "ID_NECESIDAD", $idNecesidad);
                    if ($rowNecesidad->GRADO == 'Urgencia'):
                        if ($rowNecesidad->MAQUINA_PARADA == 1):
                            $grupos_demanda .= $coma . "'UCMP'";
                            $coma           = ",";
                        else:
                            $grupos_demanda .= $coma . "'USMP'";
                            $coma           = ",";
                        endif;
                    elseif ($rowNecesidad->GRADO == 'Necesidad Urgente'):
                        $grupos_demanda .= $coma . "'NU'";
                        $coma           = ",";
                    elseif ($rowNecesidad->GRADO == 'Necesidad'):
                        $grupos_demanda .= $coma . "'N'";
                        $coma           = ",";
                    endif;
                endif;
                //FIN TIPO SEGUN NECESIDAD

                break;
            case "OT":
                //BUSCO LA LINEA DE OT
                $rowOrdenTrabajoLinea = $bd->VerReg("ORDEN_TRABAJO_LINEA", "ID_ORDEN_TRABAJO_LINEA", $idObjeto);

                //BUSCO LA OT
                $rowOrdenTrabajo = $bd->VerReg("ORDEN_TRABAJO", "ID_ORDEN_TRABAJO", $rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO);

                //CALCULO LOS OBJETOS PARA CALCULAR EL GRUPO DE DEMANDA
                if (
                    (($rowOrdenTrabajo->SISTEMA_OT == 'MAXIMO') || ($rowOrdenTrabajo->SISTEMA_OT == 'SGA Manual')) &&
                    ($rowOrdenTrabajoLinea->NUMERO_PENDIENTE != '') &&
                    (($rowOrdenTrabajoLinea->PRIORIDAD_NUMERO_PENDIENTE == '1-Alta') || ($rowOrdenTrabajoLinea->PRIORIDAD_NUMERO_PENDIENTE == '2-Media') || ($rowOrdenTrabajoLinea->PRIORIDAD_NUMERO_PENDIENTE == '3-Baja'))
                ): //LINEA DE OT DE PENDIENTES
                    switch ($rowOrdenTrabajoLinea->PRIORIDAD_NUMERO_PENDIENTE): //PRIORIDAD DEL PENDIENTE
                        case "1-Alta":
                            $grupos_demanda .= $coma . "'OTPP1'";
                            break;
                        case "2-Media":
                            $grupos_demanda .= $coma . "'OTPP2'";
                            break;
                        case "3-Baja":
                            $grupos_demanda .= $coma . "'OTPP3'";
                            break;
                    endswitch;
                //FIN PRIORIDAD DEL PENDIENTE
                elseif (
                    (($rowOrdenTrabajo->SISTEMA_OT == 'MAXIMO') || ($rowOrdenTrabajo->SISTEMA_OT == 'SGA Manual')) &&
                    ($rowOrdenTrabajoLinea->NUMERO_PENDIENTE == '') &&
                    ($rowOrdenTrabajoLinea->PRIORIDAD_NUMERO_PENDIENTE == '_Null_') &&
                    ($rowOrdenTrabajo->AGRUPADOR_OTS != '') &&
                    (($rowOrdenTrabajo->TECNOLOGIA == 'Eólico') || ($rowOrdenTrabajo->TECNOLOGIA == 'Hidráulico de régimen ordinario') || ($rowOrdenTrabajo->TECNOLOGIA == 'Hidráulico de régimen especial')) &&
                    (($rowOrdenTrabajo->TIPO_LISTA == 'Preventivo a inspecciones periodicas') || ($rowOrdenTrabajo->TIPO_LISTA == 'Mejoras y otros correctivos diferibles que lleven plan y no sean pendientes'))
                ): //LINEA DE OT DE PLANIFICADOS
                    $grupos_demanda .= $coma . "'OTP'";
                endif;
                //FIN CALCULO LOS OBJETOS PARA CALCULAR EL GRUPO DE DEMANDA

                break;
            case "Manual":
                $grupos_demanda .= "'M'";
                break;
            default:
                break;
        endswitch;

        if ($grupos_demanda != ""):
            //SI HEMOS OBTENIDO grupo_demanda, BUSCAMOS EL QUE MAS PRIORIDAD TIENE
            $sqlPrioridadDemanda    = "SELECT ID_PRIORIDAD_DEMANDA
                                            FROM PRIORIDAD_DEMANDA 
                                            WHERE TIPO_DEMANDA_INTERNO IN (" . $grupos_demanda . ")
                                            ORDER BY PRIORIDAD ASC";
            $resultPrioridadDemanda = $bd->ExecSQL($sqlPrioridadDemanda);
            $rowPrioridadDemanda    = $bd->SigReg($resultPrioridadDemanda);
            $idPrioridadDemanda     = $rowPrioridadDemanda->ID_PRIORIDAD_DEMANDA;
        endif;

        return $idPrioridadDemanda;
    }


    //Devuelve Dead Line (fecha demanda - LT Suministro) y Fecha Estimada Reserva (Dead Line - Margen)
    //Si el hito es Inmediato, Fecha Estimada Reserva y Dead Line es la fecha actual
    function get_fecha_estimada_reserva($fecha_demanda, $idMaterial, $idAlmacen, $idPrioridadDemanda = NULL)
    {
        global $bd;
        global $html;
        global $auxiliar;
        global $administrador;
        global $mat;

        $arr_fechas   = array();
        $fecha_actual = date("Y-m-d H:i:s");

        //SI NO VIENE PRIORIDAD (Reserva Preparacion o Expedicion), LA FECHA DE RESERVA SERA INMEDIATA
        if ($idPrioridadDemanda == NULL):
            $arr_fechas['fecha_actual']           = $fecha_actual;
            $arr_fechas['cae']                    = NULL;
            $arr_fechas['lead_time']              = 0;
            $arr_fechas['deadline']               = $fecha_actual;
            $arr_fechas['margen']                 = 0;
            $arr_fechas['fecha_estimada_reserva'] = $fecha_actual;
        else:
            //BUSCAMOS EL REGITRO
            $rowPrioridadDemanda = $bd->VerReg("PRIORIDAD_DEMANDA", "ID_PRIORIDAD_DEMANDA", $idPrioridadDemanda);

            //SI ES DEADLINE, LO CALCULAMOS
            if ($rowPrioridadDemanda->HITO_RESERVA == 'Deadline'):

                //BUSCO EL MATERIAL ALMACEN
                $GLOBALS["NotificaErrorPorEmail"] = "No";
                $rowMaterialAlmacen               = $bd->VerRegRest("MATERIAL_ALMACEN", "ID_MATERIAL = $idMaterial AND ID_ALMACEN = $idAlmacen", "No");
                if ($rowMaterialAlmacen == false):
                    //GENERAMOS UNA COPIA DEL MATERIAL_ALMACEN ENCONTRADO
                    $idMaterialAlmacen = $mat->ClonarMaterialAlmacen($idMaterial, $idAlmacen, "Reservas");

                    if ($idMaterialAlmacen != false):
                        //RECUPERO EL OBJETO CREADO
                        $rowMaterialAlmacen = $bd->VerReg("MATERIAL_ALMACEN", "ID_MATERIAL_ALMACEN", $idMaterialAlmacen);
                    endif;
                endif;

                //COMPRUEBO QUE EXISTA MATERIAL ALMACEN ORIGEN
                if ($rowMaterialAlmacen == false):
                    $rowMaterial         = $bd->VerReg("MATERIAL", "ID_MATERIAL", $idMaterial);
                    $rowAlmacen          = $bd->VerReg("ALMACEN", "ID_ALMACEN", $idAlmacen);
                    $arr_fechas['error'] = $auxiliar->traduce("El material", $administrador->ID_IDIOMA) . " $rowMaterial->REFERENCIA_SGA - " . ($administrador->ID_IDIOMA == "ESP" ? $rowMaterial->DESCRIPCION : $rowMaterial->DESCRIPCION_EN) . " " . $auxiliar->traduce("no está definido para el almacén", $administrador->ID_IDIOMA) . " $rowAlmacen->REFERENCIA - $rowAlmacen->NOMBRE.<br>";

                    return $arr_fechas;
                endif;
                //BUSCAMOS EL REGISTRO MATERIAL ALMACEN

                //DEADLINE ES Fecha Demanda MENOS LOS LT
                $fecha_deadline = $auxiliar->restarDiasFecha($fecha_demanda, $rowMaterialAlmacen->LEAD_TIME_SUMINISTRO);

                //FECHA_ESTIMADA_RESERVA ES Deadline MENOS Margen
                $fecha_estimada_reserva = $auxiliar->restarDiasFecha($fecha_deadline, $rowPrioridadDemanda->MARGEN_RESERVA);

                $arr_fechas['fecha_actual']           = $fecha_actual;
                $arr_fechas['cae']                    = $rowMaterialAlmacen->ID_CLAVE_APROVISIONAMIENTO_ESPECIAL;
                $arr_fechas['lead_time']              = $rowMaterialAlmacen->LEAD_TIME_SUMINISTRO;
                $arr_fechas['deadline']               = $fecha_deadline;
                $arr_fechas['margen']                 = $rowPrioridadDemanda->MARGEN_RESERVA;
                $arr_fechas['fecha_estimada_reserva'] = $fecha_estimada_reserva;

            else://SI ES INMEDIATA, DEVOLVEMOS LAS FECHAS ACTUALES
                $arr_fechas['fecha_actual']           = $fecha_actual;
                $arr_fechas['cae']                    = NULL;
                $arr_fechas['lead_time']              = 0;
                $arr_fechas['deadline']               = $fecha_actual;
                $arr_fechas['margen']                 = 0;
                $arr_fechas['fecha_estimada_reserva'] = $fecha_actual;
            endif;
        endif;

        return $arr_fechas;
    }


    /**
     * @param $idDemanda
     * DEVUELVE EL ALMACEN DE RESERVA
     */
    function get_almacen_reserva($idDemanda)
    {
        global $bd;
        global $html;
        global $auxiliar;
        global $administrador;

        //BUSCAMOS LA DEMANDA
        $idAlmacenReserva = NULL;
        $rowDemanda       = $bd->VerReg("DEMANDA", "ID_DEMANDA", $idDemanda);

        //POR TIPO DE DEMANDA, DEVOLVEMOS EL ALMACEN DE LA RESERVA
        switch ($rowDemanda->TIPO_DEMANDA):
            case "Pedido":
            case "OT":
            case "Manual":
            default:
                //EN ESTOS TIPOS, EL ALMACEN DEMANDA ES EL MISMO QUE EL DE RESERVA
                $idAlmacenReserva = $rowDemanda->ID_ALMACEN_DEMANDA;
                break;
        endswitch;

        return $idAlmacenReserva;
    }

    /**
     * @string $tipoObjeto
     * @string $idObjeto
     * @string $idNecesidad
     * @return $object La row si la encuentra, false en otro caso
     */
    function get_demanda($tipoObjeto, $idObjeto, $idNecesidad = NULL, $selBaja = "")
    {
        global $bd;
        global $html;
        global $auxiliar;
        global $administrador;

        //GENERAMOS EL WHERE SEGUN EL TIPO OBJETO
        //BUSCAMOS EL OBJETO
        switch ($tipoObjeto):
            case "Pedido":
                $sql_where = " AND ID_PEDIDO_SALIDA_LINEA = " . $idObjeto . " AND ID_NECESIDAD " . ((($idNecesidad != NULL) && ($idNecesidad != "")) ? " = " . $idNecesidad : " IS NULL");
                break;
            case "OT":
                $sql_where = " AND ID_ORDEN_TRABAJO_LINEA = " . $idObjeto;
                break;
            case "Manual":
            default:
                $sql_where = " AND FALSE ";
                break;
        endswitch;

        //FILTRO BAJA
        if ($selBaja == "Si"):
            $sql_where .= " AND BAJA = 1 ";
        else:
            $sql_where .= " AND BAJA = 0 ";
        endif;

        $sqlDemandas    = "SELECT * 
                            FROM DEMANDA
                            WHERE 1=1 $sql_where ";
        $resultDemandas = $bd->ExecSQL($sqlDemandas);
        $rowDemanda     = $bd->SigReg($resultDemandas);

        return $rowDemanda;
    }

    /**
     * @string $idPedidoSalida
     * @string $idNecesidad Si se quiere filtrar por necesidad, valor "Si"/"No" si se quiere obtener la que no tiene necesidad
     * @string $idPedidoSalidaLinea Si se quiere filtrar por un línea en particular
     * @return $result con las Demandas asociadas a ese Pedido
     */
    function get_demandas_pedido($idPedidoSalida, $idNecesidad = "", $idPedidoSalidaLinea = "")
    {
        global $bd;
        global $html;
        global $auxiliar;
        global $administrador;

        //FILTRO NECESIDAD
        $sql_where = "";
        if ($idNecesidad == "No"):
            $sql_where .= " AND D.ID_NECESIDAD IS NULL ";
        elseif ($idNecesidad == "No"):
            $sql_where .= " AND D.ID_NECESIDAD IS NOT NULL ";
        elseif ($idNecesidad != ""):
            $sql_where .= " AND D.ID_NECESIDAD = " . $idNecesidad;
        endif;

        //FILTRO PSL
        if ($idPedidoSalidaLinea != ""):
            $sql_where .= " AND D.ID_PEDIDO_SALIDA_LINEA = " . $idPedidoSalidaLinea;
        endif;

        //BUSCAMOS LAS DEMANDAS DEL PEDIDO
        $sqlDemandas    = "SELECT D.*
                            FROM DEMANDA D
                            INNER JOIN PEDIDO_SALIDA_LINEA PSL ON PSL.ID_PEDIDO_SALIDA_LINEA = D.ID_PEDIDO_SALIDA_LINEA
                            WHERE PSL.ID_PEDIDO_SALIDA = $idPedidoSalida AND D.BAJA = 0$sql_where ";
        $resultDemandas = $bd->ExecSQL($sqlDemandas);

        return $resultDemandas;
    }

    /**
     * @string $idDemanda
     * @string $estado. Estado de la cola
     * @return $cantidadCola Cantidad en cola
     */
    function get_cantidad_cola_demanda($idDemanda, $estado = "")
    {
        global $bd;
        global $html;
        global $auxiliar;
        global $administrador;

        //CANTIDAD A RETORNAR
        $cantidadCola = 0;

        //ESTADO
        $sql_where = "";
        if ($estado != ""):
            $sql_where .= "AND ESTADO = '" . $estado . "'";
        endif;

        //OBTENEMOS LA SUMA DE LA CANTIDAD TOTAL EN COLA DE LAS COLAS DE LA DEMANDA
        $sqlColas    = "SELECT SUM(CANTIDAD_EN_COLA) AS TOTAL_COLA
                            FROM COLA_RESERVA
                            WHERE ID_DEMANDA = $idDemanda AND BAJA = 0 $sql_where";
        $resultColas = $bd->ExecSQL($sqlColas);
        $rowColas    = $bd->SigReg($resultColas);
        if (($rowColas != false) && ($rowColas->TOTAL_COLA != NULL)):
            $cantidadCola = $rowColas->TOTAL_COLA;
        endif;

        return $cantidadCola;
    }

    /**
     * @string $idDemanda
     * @string $estadoLinea. CanceladaYFinalizada devuelve ambas
     * @return Cantidad Asignada Reservada
     */
    function get_cantidad_reserva_demanda($idDemanda, $estadoLinea = "")
    {
        global $bd;
        global $html;
        global $auxiliar;
        global $administrador;

        //CANTIDAD A RETORNAR
        $cantidadReservada = 0;

        //ESTADO
        $sql_where = "";
        if ($estadoLinea == "CanceladaYFinalizada"):
            $sql_where .= " AND (RL.ESTADO_LINEA = 'Cancelada' OR RL.ESTADO_LINEA = 'Finalizada') ";

        elseif ($estadoLinea != ""):
            $sql_where .= " AND RL.ESTADO_LINEA = '" . $estadoLinea . "' ";
        endif;

        //BUSCAMOS LAS RESERVAS DE LA DEMANDA
        $sqlReservas    = "SELECT SUM(RL.CANTIDAD) AS TOTAL_RESERVA
                                    FROM RESERVA_LINEA RL
                                    INNER JOIN RESERVA R ON R.ID_RESERVA = RL.ID_RESERVA
                                    WHERE R.ID_DEMANDA = $idDemanda AND R.BAJA = 0 AND RL.BAJA = 0 " . $sql_where;
        $resultReservas = $bd->ExecSQL($sqlReservas);
        $rowReservas    = $bd->SigReg($resultReservas);
        if (($rowReservas != false) && ($rowReservas->TOTAL_RESERVA != NULL)):
            $cantidadReservada = $rowReservas->TOTAL_RESERVA;
        endif;

        return $cantidadReservada;
    }

    /**
     * @string $idPedidoSalidaLinea
     * @string $idNecesidad Si se quiere filtrar por necesidad, valor "Si"/"No" si se quiere obtener la que no tiene necesidad
     * @return Cantidad Asignada a las demandas
     */
    function get_cantidad_demanda_linea_pedido($idPedidoSalidaLinea, $idNecesidad = "")
    {
        global $bd;
        global $html;
        global $auxiliar;
        global $administrador;

        //CANTIDAD A RETORNAR
        $cantidadDemanda = 0;

        //FILTRO NECESIDAD
        $sql_where = "";
        if ($idNecesidad == "No"):
            $sql_where .= " AND ID_NECESIDAD IS NULL ";
        elseif ($idNecesidad == "Si"):
            $sql_where .= " AND ID_NECESIDAD IS NOT NULL ";
        elseif ($idNecesidad != ""):
            $sql_where .= " AND ID_NECESIDAD = $idNecesidad ";
        endif;

        //BUSCAMOS LAS DEMANDAS DEL PEDIDO
        $sqlDemandas    = "SELECT SUM(D.CANTIDAD_DEMANDA) AS TOTAL_DEMANDA
                            FROM DEMANDA D
                            WHERE D.ID_PEDIDO_SALIDA_LINEA = $idPedidoSalidaLinea AND D.TIPO_DEMANDA = 'Pedido' AND D.BAJA = 0 $sql_where ";
        $resultDemandas = $bd->ExecSQL($sqlDemandas);
        $rowDemandas    = $bd->SigReg($resultDemandas);
        if (($rowDemandas != false) && ($rowDemandas->TOTAL_DEMANDA != NULL)):
            $cantidadDemanda = $rowDemandas->TOTAL_DEMANDA;
        endif;

        return $cantidadDemanda;
    }

    /**
     * @string $idPedidoSalidaLinea
     * @string $idNecesidad Si se quiere filtrar por necesidad, valor "Si"/"No" si se quiere obtener la que no tiene necesidad
     * @return Cantidad Asignada a las demandas
     */
    function get_cantidad_demanda_linea_OT($idOrdenTrabajoLinea)
    {
        global $bd;
        global $html;
        global $auxiliar;
        global $administrador;

        //CANTIDAD A RETORNAR
        $cantidadDemanda = 0;

        //BUSCAMOS LAS DEMANDAS DEL PEDIDO
        $sqlDemandas    = "SELECT SUM(D.CANTIDAD_DEMANDA) AS TOTAL_DEMANDA
                            FROM DEMANDA D
                            WHERE D.ID_ORDEN_TRABAJO_LINEA = $idOrdenTrabajoLinea AND D.TIPO_DEMANDA = 'OT' AND D.BAJA = 0";
        $resultDemandas = $bd->ExecSQL($sqlDemandas);
        $rowDemandas    = $bd->SigReg($resultDemandas);
        if (($rowDemandas != false) && ($rowDemandas->TOTAL_DEMANDA != NULL)):
            $cantidadDemanda = $rowDemandas->TOTAL_DEMANDA;
        endif;

        return $cantidadDemanda;
    }

    /**
     * @string $idPedidoSalidaLinea
     * @string $estadoLinea
     * @string $idDemanda
     * @return Cantidad Reservada
     */
    function get_cantidad_reservada_linea_pedido($idPedidoSalidaLinea, $estadoLinea = "", $idDemanda = "")
    {
        global $bd;
        global $html;
        global $auxiliar;
        global $administrador;

        //CANTIDAD A RETORNAR
        $cantidadReservada = 0;

        //FILTRO DEMANDA
        $sql_where = "";
        if ($idDemanda != ""):
            $sql_where .= " AND D.ID_DEMANDA = $idDemanda ";
        endif;

        //FILTRO ESTADO LINEA
        if ($estadoLinea != ""):
            $sql_where .= " AND RL.ESTADO_LINEA = '" . $estadoLinea . "' ";
        endif;

        //BUSCAMOS LAS DEMANDAS DEL PEDIDO
        $sqlDemandas    = "SELECT SUM(RL.CANTIDAD) AS TOTAL_RESERVADO
                            FROM DEMANDA D
                            INNER JOIN RESERVA R ON R.ID_DEMANDA = D.ID_DEMANDA
                            INNER JOIN RESERVA_LINEA RL ON RL.ID_RESERVA = R.ID_RESERVA
                            WHERE D.ID_PEDIDO_SALIDA_LINEA = $idPedidoSalidaLinea AND D.TIPO_DEMANDA = 'Pedido' AND D.BAJA = 0 AND RL.BAJA = 0 AND R.BAJA = 0 $sql_where ";
        $resultDemandas = $bd->ExecSQL($sqlDemandas);
        $rowDemandas    = $bd->SigReg($resultDemandas);
        if (($rowDemandas != false) && ($rowDemandas->TOTAL_RESERVADO != NULL)):
            $cantidadReservada = $rowDemandas->TOTAL_RESERVADO;
        endif;

        return $cantidadReservada;
    }

    /**
     * @string $idPedidoSalidaLinea
     * @string $estadoLinea
     * @string $idDemanda
     * @string $idNecesidad
     * @return Cantidad Reservada
     */
    function get_cantidad_reservada_linea_pedido_necesidad($idPedidoSalidaLinea, $estadoLinea = "", $idNecesidad = "")
    {
        global $bd;
        global $html;
        global $auxiliar;
        global $administrador;

        //CANTIDAD A RETORNAR
        $cantidadReservada = 0;

        //FILTRO ESTADO LINEA
        $sql_where = "";
        if ($estadoLinea != ""):
            $sql_where .= " AND RL.ESTADO_LINEA = '$estadoLinea' ";
        endif;

        //FILTRO NECESIDAD
        if ($idNecesidad == "No"):
            $sql_where .= " AND D.ID_NECESIDAD IS NULL ";
        elseif ($idNecesidad == "Si"):
            $sql_where .= " AND D.ID_NECESIDAD IS NOT NULL ";
        elseif ($idNecesidad != ""):
            $sql_where .= " AND D.ID_NECESIDAD = $idNecesidad ";
        endif;

        //BUSCAMOS LAS DEMANDAS DEL PEDIDO
        $sqlDemandas    = "SELECT SUM(RL.CANTIDAD) AS TOTAL_RESERVADO
                            FROM DEMANDA D
                            INNER JOIN RESERVA R ON R.ID_DEMANDA = D.ID_DEMANDA
                            INNER JOIN RESERVA_LINEA RL ON RL.ID_RESERVA = R.ID_RESERVA
                            WHERE D.ID_PEDIDO_SALIDA_LINEA = $idPedidoSalidaLinea AND D.TIPO_DEMANDA = 'Pedido' AND D.BAJA = 0 AND R.BAJA = 0 AND RL.BAJA = 0 $sql_where ";
        $resultDemandas = $bd->ExecSQL($sqlDemandas);
        $rowDemandas    = $bd->SigReg($resultDemandas);
        if (($rowDemandas != false) && ($rowDemandas->TOTAL_RESERVADO != NULL)):
            $cantidadReservada = $rowDemandas->TOTAL_RESERVADO;
        endif;

        return $cantidadReservada;
    }

    /**
     * @string $idReserva
     * @string $estadoLinea. CanceladaYFinalizada devuelve ambas
     * @return Cantidad Asignada Reservada
     */
    function get_cantidad_reserva_especifica($idReserva, $estadoLinea = "", $idMaterial = "", $idMaterialFisico = "", $idUbicacion = "")
    {
        global $bd;
        global $html;
        global $auxiliar;
        global $administrador;

        //CANTIDAD A RETORNAR
        $cantidadReservada = 0;

        //ESTADO
        $sql_where = "";
        if ($estadoLinea == "CanceladaYFinalizada"):
            $sql_where .= " AND (RL.ESTADO_LINEA = 'Cancelada' OR RL.ESTADO_LINEA = 'Finalizada') ";

        elseif ($estadoLinea != ""):
            $sql_where .= " AND RL.ESTADO_LINEA = '" . $estadoLinea . "' ";
        endif;

        //MATERIAL
        if ($idMaterial != ""):
            $sql_where .= " AND RL.ID_MATERIAL = '" . $idMaterial . "' ";
        endif;
        //MATERIAL_FISICO
        if ($idMaterialFisico != ""):
            $sql_where .= " AND RL.ID_MATERIAL_FISICO = '" . $idMaterialFisico . "' ";
        endif;
        //UBICACION
        if ($idUbicacion != ""):
            $sql_where .= " AND RL.ID_UBICACION = '" . $idUbicacion . "' ";
        endif;

        //BUSCAMOS LAS RESERVAS DE LA DEMANDA
        $sqlReservas    = "SELECT SUM(RL.CANTIDAD) AS TOTAL_RESERVA
                                    FROM RESERVA_LINEA RL
                                    INNER JOIN RESERVA R ON R.ID_RESERVA = RL.ID_RESERVA
                                    WHERE R.ID_RESERVA = $idReserva AND R.BAJA = 0 AND RL.BAJA = 0 " . $sql_where;
        $resultReservas = $bd->ExecSQL($sqlReservas);
        $rowReservas    = $bd->SigReg($resultReservas);
        if (($rowReservas != false) && ($rowReservas->TOTAL_RESERVA != NULL)):
            $cantidadReservada = $rowReservas->TOTAL_RESERVA;
        endif;

        return $cantidadReservada;
    }

    /**
     * @string $idPedidoSalidaLinea
     * @string $estadoLinea
     * @string $idDemanda
     * @return $result con las lineas de reserva de esa linea de pedido
     */
    function get_reservas_linea_pedido($idPedidoSalidaLinea, $estadoLinea = "", $idDemanda = "")
    {
        global $bd;
        global $html;
        global $auxiliar;
        global $administrador;


        //FILTRO DEMANDA
        $sql_where = "";
        if ($idDemanda != ""):
            $sql_where .= " AND D.ID_DEMANDA = $idDemanda ";
        endif;

        //FILTRO ESTADO LINEA
        if ($estadoLinea != ""):
            $sql_where .= " AND RL.ESTADO_LINEA = '" . $estadoLinea . "' ";
        endif;

        //BUSCAMOS LAS DEMANDAS DEL PEDIDO
        $sqlDemandas    = "SELECT DISTINCT RL.*
                            FROM DEMANDA D
                            INNER JOIN RESERVA R ON R.ID_DEMANDA = D.ID_DEMANDA
                            INNER JOIN RESERVA_LINEA RL ON RL.ID_RESERVA = R.ID_RESERVA
                            WHERE D.ID_PEDIDO_SALIDA_LINEA = $idPedidoSalidaLinea AND D.TIPO_DEMANDA = 'Pedido' AND D.BAJA = 0 AND RL.BAJA = 0 AND R.BAJA = 0 $sql_where ";
        $resultDemandas = $bd->ExecSQL($sqlDemandas);

        return $resultDemandas;
    }

    /**
     * @string $idOrdenTrabajoLinea
     * @string $estadoLinea
     * @string $idDemanda
     * @return $result con las lineas de reserva de esa linea de ot
     */
    function get_reservas_linea_ot($idOrdenTrabajoLinea, $estadoLinea = "", $idDemanda = "")
    {
        global $bd;
        global $html;
        global $auxiliar;
        global $administrador;

        //FILTRO DEMANDA
        $sql_where = "";
        if ($idDemanda != ""):
            $sql_where .= " AND D.ID_DEMANDA = $idDemanda ";
        endif;

        //FILTRO ESTADO LINEA
        if ($estadoLinea != ""):
            $sql_where .= " AND RL.ESTADO_LINEA = '" . $estadoLinea . "' ";
        endif;

        //BUSCAMOS LAS LINEAS DE RESERVA DE LA OT
        $sqlLineasReserva    = "SELECT DISTINCT RL.*
                                FROM DEMANDA D
                                INNER JOIN RESERVA R ON R.ID_DEMANDA = D.ID_DEMANDA
                                INNER JOIN RESERVA_LINEA RL ON RL.ID_RESERVA = R.ID_RESERVA
                                WHERE D.ID_ORDEN_TRABAJO_LINEA = $idOrdenTrabajoLinea AND D.TIPO_DEMANDA = 'OT' AND D.BAJA = 0 AND RL.BAJA = 0 AND R.BAJA = 0 $sql_where ";
        $resultLineasReserva = $bd->ExecSQL($sqlLineasReserva);

        return $resultLineasReserva;
    }

    /**
     * @string $idPedidoSalidaLinea
     * @string $estadoLinea
     * @string $idDemanda
     * @return $result con las lineas de reserva de esa linea de pedido
     */
    function get_colas_reserva_pedido_linea($idPedidoSalidaLinea, $estadoLinea = "", $idDemanda = "")
    {
        global $bd;
        global $html;
        global $auxiliar;
        global $administrador;


        //FILTRO DEMANDA
        $sql_where = "";
        if ($idDemanda != ""):
            $sql_where .= " AND D.ID_DEMANDA = $idDemanda ";
        endif;

        //FILTRO ESTADO LINEA
        if ($estadoLinea == "No Cubierta"):
            $sql_where .= " AND CR.ESTADO <> 'Cubierta' ";
        elseif ($estadoLinea != ""):
            $sql_where .= " AND CR.ESTADO = '" . $estadoLinea . "' ";
        endif;

        //BUSCAMOS LAS COLAS DEL PEDIDO
        $sqlDemandas    = "SELECT DISTINCT CR.*
                            FROM DEMANDA D
                            INNER JOIN COLA_RESERVA CR ON CR.ID_DEMANDA = D.ID_DEMANDA
                            INNER JOIN PRIORIDAD_DEMANDA PD ON PD.ID_PRIORIDAD_DEMANDA = D.ID_PRIORIDAD_DEMANDA
                            WHERE D.ID_PEDIDO_SALIDA_LINEA = $idPedidoSalidaLinea AND D.TIPO_DEMANDA = 'Pedido' AND D.BAJA = 0 AND CR.BAJA = 0 $sql_where 
                            ORDER BY PD.PRIORIDAD ASC, D.FECHA_CREACION ASC";
        $resultDemandas = $bd->ExecSQL($sqlDemandas);

        return $resultDemandas;
    }

    /**
     * @string $idOrdenTrabajoLinea
     * @string $estadoLinea
     * @string $idDemanda
     * @return $result con las lineas de reserva de esa linea de Orden Trabajo
     */
    function get_colas_reserva_OT_linea($idOrdenTrabajoLinea, $estadoLinea = "", $idDemanda = "")
    {
        global $bd;
        global $html;
        global $auxiliar;
        global $administrador;


        //FILTRO DEMANDA
        $sql_where = "";
        if ($idDemanda != ""):
            $sql_where .= " AND D.ID_DEMANDA = $idDemanda ";
        endif;

        //FILTRO ESTADO LINEA
        if ($estadoLinea == "No Cubierta"):
            $sql_where .= " AND CR.ESTADO <> 'Cubierta' ";
        elseif ($estadoLinea != ""):
            $sql_where .= " AND CR.ESTADO = '" . $estadoLinea . "' ";
        endif;

        //BUSCAMOS LAS COLAS DEL PEDIDO
        //TODO: QUITAMOS EL JOIN DE PRIORIDAD HASTA QUE ESTE HECHO D24. Priorización demanda OT (212026)
        //INNER JOIN PRIORIDAD_DEMANDA PD ON PD.ID_PRIORIDAD_DEMANDA = D.ID_PRIORIDAD_DEMANDA
        //ORDER BY PD.PRIORIDAD ASC
        $sqlDemandas    = "SELECT DISTINCT CR.*
                            FROM DEMANDA D
                            INNER JOIN COLA_RESERVA CR ON CR.ID_DEMANDA = D.ID_DEMANDA
                            WHERE D.ID_ORDEN_TRABAJO_LINEA = $idOrdenTrabajoLinea AND D.TIPO_DEMANDA = 'OT' AND D.BAJA = 0 AND CR.BAJA = 0 $sql_where 
                            ORDER BY D.FECHA_CREACION ASC";
        $resultDemandas = $bd->ExecSQL($sqlDemandas);

        return $resultDemandas;
    }

    /**
     * @string $idDemanda
     * @return $object La row si la encuentra, false en otro caso
     */
    function get_cola_reserva($idDemanda, $estadoCola = "", $selBaja = "")
    {
        global $bd;
        global $html;
        global $auxiliar;
        global $administrador;

        //FILTRO ESTADO
        $sql_where = "";
        if ($estadoCola != ""):
            $sql_where .= " AND ESTADO = '" . $estadoCola . "' ";
        endif;

        //FILTRO BAJA
        if ($selBaja == "Si"):
            $sql_where .= " AND BAJA = 1 ";
        else:
            $sql_where .= " AND BAJA = 0 ";
        endif;

        $sqlColaProgramada    = "SELECT * 
                                    FROM COLA_RESERVA
                                    WHERE ID_DEMANDA = '" . $idDemanda . "' " . $sql_where;
        $resultColaProgramada = $bd->ExecSQL($sqlColaProgramada);
        $rowColaProgramada    = $bd->SigReg($resultColaProgramada);

        return $rowColaProgramada;
    }

    /**
     * @string $idDemanda
     * @return $object La row si la encuentra, false en otro caso
     * DEVUELVE EL $result CON LAS RESERVAS
     */
    function get_reserva_demanda($idDemanda, $selBaja = "")
    {
        global $bd;
        global $html;
        global $auxiliar;
        global $administrador;

        //FILTRO BAJA
        if ($selBaja == "Si"):
            $sql_where = " AND BAJA = 1 ";
        else:
            $sql_where = " AND BAJA = 0 ";
        endif;

        $sqlReservas    = "SELECT R.*
                            FROM RESERVA R 
                            WHERE R.ID_DEMANDA = $idDemanda " . $sql_where;
        $resultReservas = $bd->ExecSQL($sqlReservas);
        $rowReserva     = $bd->SigReg($resultReservas);

        return $rowReserva;
    }


    /**
     * @string $idDemanda
     * @return $object La row si la encuentra, false en otro caso
     * DEVUELVE EL $result CON LAS RESERVAS Y RESERVAS LINEAS
     */
    function get_lineas_reservas($idReserva, $estadoLinea = "", $idReservaLinea = "")
    {
        global $bd;
        global $html;
        global $auxiliar;
        global $administrador;


        $sql_where = "";
        if ($estadoLinea != ""):
            $sql_where .= " AND RL.ESTADO_LINEA = '" . $estadoLinea . "' ";
        endif;
        if ($idReservaLinea != ""):
            $sql_where .= " AND RL.ID_RESERVA_LINEA = '" . $idReservaLinea . "' ";
        endif;

        $sqlReservas    = "SELECT  RL.* 
                                    FROM RESERVA_LINEA RL 
                                    WHERE RL.ID_RESERVA = $idReserva AND RL.BAJA = 0" . $sql_where;
        $resultReservas = $bd->ExecSQL($sqlReservas);

        return $resultReservas;
    }


    /**
     * @string $idDemanda
     * @string $estado_linea
     * @string $ordenacionLineas => POR SI NECESITAMOS ORDENARLAS
     * @string $reservasFijas => Si, solo saca las fijas, No, no las saca, vacío, saca todas
     * @return $object La row si la encuentra, false en otro caso
     * DEVUELVE EL $result CON LAS RESERVAS Y RESERVAS LINEAS
     */
    function get_lineas_reservas_demanda($idDemanda, $estadoLinea = "", $ordenacionLineas = "", $reservasFijas = "", $idReservaLinea = "")
    {
        global $bd;
        global $html;
        global $auxiliar;
        global $administrador;

        //ESTADO
        $sql_where = "";
        if ($estadoLinea != ""):
            $sql_where .= " AND RL.ESTADO_LINEA = '" . $estadoLinea . "' ";
        endif;

        //RESERVAS FIJAS
        if ($reservasFijas == "Si"):
            $sql_where .= " AND RL.RESERVA_FIJA = 1 ";
        elseif ($reservasFijas == "No"):
            $sql_where .= " AND RL.RESERVA_FIJA = 0 ";
        endif;

        //LINEA PREFIJADA
        if ($idReservaLinea != ""):
            $sql_where .= " AND RL.ID_RESERVA_LINEA =  " . $idReservaLinea;
        endif;

        //ORDENACION
        $sql_order = "";
        if ($ordenacionLineas != ""):
            $sql_order .= " ORDER BY " . $ordenacionLineas;
        endif;

        $sqlReservas    = "SELECT R.*, RL.* 
                                    FROM RESERVA_LINEA RL
                                    INNER JOIN RESERVA R ON R.ID_RESERVA = RL.ID_RESERVA
                                    WHERE R.ID_DEMANDA = $idDemanda AND R.BAJA = 0 AND  RL.BAJA = 0 " . $sql_where .
            $sql_order;
        $resultReservas = $bd->ExecSQL($sqlReservas);

        return $resultReservas;
    }

    /**
     * @param $idDemanda
     * @param $observacionesLog
     * ACTUALIZA EL ESTADO DE LA DEMANDA EN CASO DE QUE SEA NECESARIO
     */
    function actualizar_estado_demanda($idDemanda, $observacionesLog)
    {
        global $bd;
        global $html;
        global $auxiliar;
        global $administrador;

        //BUSCAMOS LA DEMANDA
        $rowDemanda = $bd->VerReg("DEMANDA", "ID_DEMANDA", $idDemanda);

        $estadoReal = "";
        if ($rowDemanda->CANTIDAD_PENDIENTE_RESERVAR > EPSILON_SISTEMA):
            //BUSCAMOS CUANTA CANTIDAD EN COLA ESTA CANCELADA
            $cantidadColaCancelada = $this->get_cantidad_cola_demanda($rowDemanda->ID_DEMANDA, 'Cancelada');

            if ($cantidadColaCancelada > EPSILON_SISTEMA):
                //BUSCAMOS CUANTA CANTIDAD RESERVADA ESTA CANCELADA Y FINALIZADA
                $cantidadReservadaCanceladaYFinalizada = $this->get_cantidad_reserva_demanda($rowDemanda->ID_DEMANDA, 'CanceladaYFinalizada');

                //SI LA CANTIDAD CANCELADA O FINALIZADA DE LA COLA Y RESERVA MENOS LA DE LA DEMANDA ES IGUAL QUE 0 FINALIZO LA DEMANDA
                if (abs(($cantidadColaCancelada + $cantidadReservadaCanceladaYFinalizada) - ($rowDemanda->CANTIDAD_DEMANDA)) < EPSILON_SISTEMA):
                    $estadoReal = 'Finalizada';
                else:
                    $estadoReal = 'Activa';
                endif;
            else:
                $estadoReal = "Activa";
            endif;
        else:
            //BUSCAMOS CUANTA CANTIDAD RESERVADA ESTA CANCELADA Y FINALIZADA
            $cantidadReservadaCanceladaYFinalizada = $this->get_cantidad_reserva_demanda($rowDemanda->ID_DEMANDA, 'CanceladaYFinalizada');

            if ($cantidadReservadaCanceladaYFinalizada == $rowDemanda->CANTIDAD_DEMANDA):
                $estadoReal = 'Finalizada';
            else:
                $estadoReal = 'Cubierta';
            endif;
        endif;

        //SI EL ESTADO CAMBIA, LO ACTUALIZAMOS
        if ($estadoReal != $rowDemanda->ESTADO):
            //SI PASA DE ACTIVA, GUARDAMOS LA FECHA CIERRA RESERVA
            $updateEstado = "";
            if ($rowDemanda->ESTADO == 'Activa'):
                $updateEstado = " , FECHA_CIERRE_RESERVA = '" . date("Y-m-d H:i:s") . "'";
            endif;

            $sqlUpdate = "UPDATE DEMANDA SET 
                                    ESTADO = '" . $estadoReal . "'
                                    $updateEstado
                                    WHERE ID_DEMANDA = $rowDemanda->ID_DEMANDA";
            $bd->ExecSQL($sqlUpdate);

            //LOG MOVIMIENTOS
            $rowDemandaActualizada = $bd->VerReg("DEMANDA", "ID_DEMANDA", $rowDemanda->ID_DEMANDA);
            $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Demanda", $rowDemanda->ID_DEMANDA, $observacionesLog, 'DEMANDA', $rowDemanda, $rowDemandaActualizada);
        endif;

    }

    /**
     * @param $idMaterial
     * @param $idAlmacen
     * @param $idTipoBloqueoReservar
     * @param $cantidad
     * @param $ambitoInternacional
     * @return array con los materiales que se ha podido encontrar para la cantidad indicada
     */
    function reserva_material_defecto($idMaterial, $idAlmacen, $idTipoBloqueoReservar, $cantidad, $ambitoInternacional, $reservaParcial, $reservaOT)
    {
        global $bd;
        global $mat;
        global $html;
        global $auxiliar;
        global $administrador;

        //ARRAY PARA GUARDAR LA CANTIDAD YA RESERVADA
        $arrStockReservado = array();

        //ARRAY A DEVOLVER
        $arr_respuesta = array();

        //BUSCO EL TIPO DE BLOQUEO PLANIFICADO
        $rowTipoBloqueoPlanificado = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", 'SP');

        //EN FUNCION DEL TIPO DE BLOQUEO A RESERVAR SELECCIONAMOS EL TIPO DE BLOQUEO A RESERVAR
        if ($idTipoBloqueoReservar == $rowTipoBloqueoPlanificado->ID_TIPO_BLOQUEO):
            //BUSCO EL TIPO DE BLOQUEO RESERVADO PLANIFICADO
            /*$rowTipoBloqueoReservadoPlanificado = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", 'RVP');

            $rowTipoBloqueoReservar = $rowTipoBloqueoReservadoPlanificado;*/
        else:
            //SI VIENE VACIO, LE DAMOS VALOR NULL
            $idTipoBloqueoReservar = ($idTipoBloqueoReservar == "" ? NULL : $idTipoBloqueoReservar);

            //BUSCO EL TIPO DE BLOQUEO RESERVADO
            /*$rowTipoBloqueoReservado = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", 'RV');

            $rowTipoBloqueoReservar = $rowTipoBloqueoReservado;*/
        endif;

        //BUSCO LA DUPLA MATERIAL ALMACEN PARA SABER QUE TIPO LOTE ES
        $rowMatAlm = $bd->VerRegRest("MATERIAL_ALMACEN", "ID_MATERIAL = $idMaterial AND ID_ALMACEN = $idAlmacen");

        //EN FUNCION DEL TIPO LOTE VARIA LA FORMA DE BUSCAR LAS UBICACIONES VALIDAS PARA PREPARAR EL MATERIAL
        if ($rowMatAlm->TIPO_LOTE == 'ninguno'): //NO SERIABLE NO LOTABLE

            $sqlBuscaUbicaciones = "SELECT MU.ID_MATERIAL_UBICACION
                                        FROM MATERIAL_UBICACION MU
                                        INNER JOIN UBICACION U ON U.ID_UBICACION = MU.ID_UBICACION
                                        WHERE MU.ACTIVO = 1 AND U.ID_ALMACEN = $idAlmacen AND MU.ID_MATERIAL = $idMaterial AND U.VALIDA_STOCK_DISPONIBLE = 1 AND MU.ID_TIPO_BLOQUEO " . ($idTipoBloqueoReservar == NULL ? 'IS NULL' : "= $idTipoBloqueoReservar") . "
                                        GROUP BY MU.ID_UBICACION, MU.ID_MATERIAL_FISICO, ID_ORDEN_TRABAJO_MOVIMIENTO, ID_INCIDENCIA_CALIDAD
                                        ORDER BY U.TIPO_UBICACION ASC, STOCK_TOTAL ASC";
        else:
            $sqlBuscaUbicaciones = "SELECT MU.ID_MATERIAL_UBICACION
                                        FROM MATERIAL_UBICACION MU
                                        INNER JOIN UBICACION U ON U.ID_UBICACION = MU.ID_UBICACION
                                        INNER JOIN MATERIAL_FISICO MF ON MF.ID_MATERIAL_FISICO = MU.ID_MATERIAL_FISICO
                                        WHERE MU.ACTIVO = 1 AND U.ID_ALMACEN = $idAlmacen AND MU.ID_MATERIAL = $idMaterial AND U.VALIDA_STOCK_DISPONIBLE = 1 AND MU.ID_TIPO_BLOQUEO " . ($idTipoBloqueoReservar == NULL ? 'IS NULL' : "= $idTipoBloqueoReservar") . " AND MU.ID_MATERIAL_FISICO IS NOT NULL AND MF.TIPO_LOTE = '" . $rowMatAlm->TIPO_LOTE . "'
                                        GROUP BY MU.ID_UBICACION, MU.ID_MATERIAL_FISICO, ID_ORDEN_TRABAJO_MOVIMIENTO, ID_INCIDENCIA_CALIDAD
                                        ORDER BY MF.FECHA_CADUCIDAD " . ($ambitoInternacional == true ? 'DESC' : 'ASC') . ", U.TIPO_UBICACION ASC, NUMERO_SERIE_LOTE ASC, STOCK_TOTAL ASC";
        endif;

        //ESTABLEZCO LA CANTIDAD PENDIENTE DE RESERVAR
        $cantidadPendienteReservar = $cantidad;

        //EJECUTO LA CONSULTA DE LAS UBICACIONES
        $resultBuscaUbicaciones = $bd->ExecSQL($sqlBuscaUbicaciones);

        //VARIABLE PARA CONTROLAR EL INDICE
        $indice = 0;

        //BUSCO EL MATERIAL
        $rowMat = $bd->VerReg("MATERIAL", "ID_MATERIAL", $idMaterial);

        //CALCULO EL FACTOR DE CONVERSION
        $factorConversion = 1;
        if ($rowMat->ID_UNIDAD_MEDIDA != $rowMat->ID_UNIDAD_COMPRA && $rowMat->DENOMINADOR_CONVERSION != 0):
            $factorConversion = $rowMat->NUMERADOR_CONVERSION / $rowMat->DENOMINADOR_CONVERSION;
        endif;

        //RECORRO LAS UBICACIONES PARA RESERVAR EL MATERIAL NECESARIO
        while (($rowBuscaUbicacion = $bd->SigReg($resultBuscaUbicaciones)) && ($cantidadPendienteReservar > EPSILON_SISTEMA)):

            //BUSCO EL MATERIAL UBICACION
            $rowMatUbiReservar = $bd->VerReg("MATERIAL_UBICACION", "ID_MATERIAL_UBICACION", $rowBuscaUbicacion->ID_MATERIAL_UBICACION);

            //PRIMERO COMPRUEBO QUE EL MATERIAL Y UBICACION NO ESTEN INCLUIDOS EN UNA ORDEN DE CONTEO ACTIVA
            $sqlDatosIncluidosEnOrdenesConteoActivas    = "SELECT COUNT(*) AS NUM
                                                            FROM INVENTARIO_ORDEN_CONTEO_LINEA IOCL
                                                            INNER JOIN INVENTARIO_ORDEN_CONTEO IOC ON IOC.ID_INVENTARIO_ORDEN_CONTEO = IOCL.ID_INVENTARIO_ORDEN_CONTEO
                                                            WHERE IOCL.ID_MATERIAL = $idMaterial AND IOCL.ID_UBICACION = $rowMatUbiReservar->ID_UBICACION AND IOCL.BAJA = 0 AND IOC.TIPO = 'Inventario' AND IOC.ESTADO <> 'Finalizado' AND IOC.BAJA = 0";
            $resultDatosIncluidosEnOrdenesConteoActivas = $bd->ExecSQL($sqlDatosIncluidosEnOrdenesConteoActivas);
            $rowDatosIncluidosEnOrdenesConteoActivas    = $bd->SigReg($resultDatosIncluidosEnOrdenesConteoActivas);
            if ($rowDatosIncluidosEnOrdenesConteoActivas->NUM > 0): //SI LOS DATOS ESTAN INCLUIDOS EN UNA ORDEN DE CONTEO ACTIVA ME SALTO LA UBICACION
                continue;
            endif;

            //CALCULO LA CANTIDAD A PREPARAR DE LA UBICACION CORRESPONDIENTE DEPENDIENDO DE LA DIVISIBILIDAD DEL MATERIAL
            if ($rowMat->ID_UNIDAD_MEDIDA != $rowMat->ID_UNIDAD_COMPRA && $rowMat->DENOMINADOR_CONVERSION != 0):
                if ($rowMat->DIVISIBILIDAD == 'Si'):
                    if (($rowMatUbiReservar->STOCK_TOTAL - $cantidadPendienteReservar) > EPSILON_SISTEMA):
                        $cantidadReservar = $cantidadPendienteReservar;
                    else:
                        $cantidadReservar = $rowMatUbiReservar->STOCK_TOTAL;
                    endif;
                else:
                    //CALCULO LA CANTIDAD DE COMPRA DEL STOCK EXISTENTE EN LA UBICACION
                    if ($rowMat->NUMERADOR_CONVERSION == 0):
                        $cantidadCompraStockDisponible = $rowMatUbiReservar->STOCK_TOTAL;
                    else:
                        $cantidadCompraStockDisponible = $rowMatUbiReservar->STOCK_TOTAL * $rowMat->DENOMINADOR_CONVERSION / $rowMat->NUMERADOR_CONVERSION;
                    endif;

                    $cantidadCompraStockDisponibleRedondeada = floor((float)$cantidadCompraStockDisponible);

                    //SI HAY STOCK SUFICIENTE PARA SATISFACER AL MENOS UNA UNIDAD DE COMPRA ENTERA, SI NO ME SALTO LA UBICACION
                    if ($cantidadCompraStockDisponibleRedondeada > EPSILON_SISTEMA):
                        //CALCULO LA CANTIDAD DE COMPRA DE LA CANTIDAD PENDIENTE DE RESERVAR
                        if ($rowMat->NUMERADOR_CONVERSION == 0):
                            $cantidadCompraPendienteReservar = $cantidadPendienteReservar;
                        else:
                            $cantidadCompraPendienteReservar = $cantidadPendienteReservar * $rowMat->DENOMINADOR_CONVERSION / $rowMat->NUMERADOR_CONVERSION;
                        endif;

                        $cantidadCompraPendienteReservarRedondeada = floor((float)$cantidadCompraPendienteReservar);

                        //SI HAY STOCK SUFICIENTE PARA SATISFACER AL MENOS UNA UNIDAD DE COMPRA ENTERA, SI NO ME SALTO LA UBICACION
                        if ($cantidadCompraPendienteReservarRedondeada > EPSILON_SISTEMA):
                            $cantidadCompraReservar = min($cantidadCompraPendienteReservarRedondeada, $cantidadCompraStockDisponibleRedondeada);
                            $cantidadReservar = $cantidadCompraReservar * $factorConversion;
                        else:
                            continue;
                        endif;
                    else:
                        continue;
                    endif;
                endif;
            else:
                if (($rowMatUbiReservar->STOCK_TOTAL - $cantidadPendienteReservar) > EPSILON_SISTEMA):
                    $cantidadReservar = $cantidadPendienteReservar;
                else:
                    $cantidadReservar = $rowMatUbiReservar->STOCK_TOTAL;
                endif;
            endif;

            //RELLENO EL ARRAY
            $arr_respuesta[$indice]["ID_MATERIAL"]                 = $idMaterial;
            $arr_respuesta[$indice]["ID_UBICACION"]                = $rowMatUbiReservar->ID_UBICACION;
            $arr_respuesta[$indice]["ID_MATERIAL_FISICO"]          = ($rowMatUbiReservar->ID_MATERIAL_FISICO == NULL ? NULL : $rowMatUbiReservar->ID_MATERIAL_FISICO);
            $arr_respuesta[$indice]["ID_TIPO_BLOQUEO"]             = ($idTipoBloqueoReservar == NULL ? NULL : $idTipoBloqueoReservar);
            $arr_respuesta[$indice]["ID_ORDEN_TRABAJO_MOVIMIENTO"] = ($rowMatUbiReservar->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? NULL : $rowMatUbiReservar->ID_ORDEN_TRABAJO_MOVIMIENTO);
            $arr_respuesta[$indice]["ID_INCIDENCIA_CALIDAD"]       = ($rowMatUbiReservar->ID_INCIDENCIA_CALIDAD == NULL ? NULL : $rowMatUbiReservar->ID_INCIDENCIA_CALIDAD);
            $arr_respuesta[$indice]["TIPO_LOTE"]                   = $rowMatAlm->TIPO_LOTE;
            $arr_respuesta[$indice]["CANTIDAD"]                    = $cantidadReservar;

            //ACTUALIZO EL INDICE
            $indice = $indice + 1;

            //ACTUALIZO LA CANTIDAD PENDIENTE DE PREPARAR
            $cantidadPendienteReservar = $cantidadPendienteReservar - $cantidadReservar;

            //ACTUALIZO LA CANTIDAD RESERVADA
            $arrStockReservado[$rowBuscaUbicacion->ID_MATERIAL_UBICACION] += $cantidadReservar;
        endwhile;
        //FIN RECORRO LAS UBICACIONES PARA RESERVAR EL MATERIAL NECESARIO

        //SI SE PUEDE REALIZAR LA RESERVA DE FORMA PARCIAL
        if (($cantidadPendienteReservar > EPSILON_SISTEMA) && ($reservaParcial == "Si")):
            //CALCULO LA CANTIDAD DE COMPRA DE LA CANTIDAD TOTAL PENDIENTE DE RESERVAR
            if ($rowMat->NUMERADOR_CONVERSION == 0):
                $cantidadCompraTotalPendienteReservar = $cantidad;
            else:
                $cantidadCompraTotalPendienteReservar = $cantidad * $rowMat->DENOMINADOR_CONVERSION / $rowMat->NUMERADOR_CONVERSION;
            endif;

            //CALCULO LA CANTIDAD DE COMPRA DE LA CANTIDAD PENDIENTE DE RESERVAR
            if ($rowMat->NUMERADOR_CONVERSION == 0):
                $cantidadCompraPendienteReservar = $cantidadPendienteReservar;
            else:
                $cantidadCompraPendienteReservar = $cantidadPendienteReservar * $rowMat->DENOMINADOR_CONVERSION / $rowMat->NUMERADOR_CONVERSION;
            endif;

            if (fmod((float) $cantidadCompraTotalPendienteReservar, 1) == fmod((float) $cantidadCompraPendienteReservar, 1)):
                $cantidadExcedente = fmod((float) $cantidadCompraPendienteReservar, 1) * $factorConversion;

                //EJECUTO LA CONSULTA DE LAS UBICACIONES
                $resultBuscaUbicaciones = $bd->ExecSQL($sqlBuscaUbicaciones);

                //RECORRO LAS UBICACIONES PARA RESERVAR EL EXCEDENTE DE MATERIAL NECESARIO
                while (($rowBuscaUbicacion = $bd->SigReg($resultBuscaUbicaciones)) && ($cantidadExcedente > EPSILON_SISTEMA)):

                    //BUSCO EL MATERIAL UBICACION
                    $rowMatUbiReservar = $bd->VerReg("MATERIAL_UBICACION", "ID_MATERIAL_UBICACION", $rowBuscaUbicacion->ID_MATERIAL_UBICACION);

                    //COMPRUEBO SI HAY STOCK RESTANTE EN LA UBICACION CONTANDO LO YA GESTIONADO PREVIAMENTE
                    if (($rowMatUbiReservar->STOCK_TOTAL - $arrStockReservado[$rowBuscaUbicacion->ID_MATERIAL_UBICACION]) > EPSILON_SISTEMA):
                        //PRIMERO COMPRUEBO QUE EL MATERIAL Y UBICACION NO ESTEN INCLUIDOS EN UNA ORDEN DE CONTEO ACTIVA
                        $sqlDatosIncluidosEnOrdenesConteoActivas    = "SELECT COUNT(*) AS NUM
                                                                        FROM INVENTARIO_ORDEN_CONTEO_LINEA IOCL
                                                                        INNER JOIN INVENTARIO_ORDEN_CONTEO IOC ON IOC.ID_INVENTARIO_ORDEN_CONTEO = IOCL.ID_INVENTARIO_ORDEN_CONTEO
                                                                        WHERE IOCL.ID_MATERIAL = $idMaterial AND IOCL.ID_UBICACION = $rowMatUbiReservar->ID_UBICACION AND IOCL.BAJA = 0 AND IOC.TIPO = 'Inventario' AND IOC.ESTADO <> 'Finalizado' AND IOC.BAJA = 0";
                        $resultDatosIncluidosEnOrdenesConteoActivas = $bd->ExecSQL($sqlDatosIncluidosEnOrdenesConteoActivas);
                        $rowDatosIncluidosEnOrdenesConteoActivas    = $bd->SigReg($resultDatosIncluidosEnOrdenesConteoActivas);
                        if ($rowDatosIncluidosEnOrdenesConteoActivas->NUM > 0): //SI LOS DATOS ESTAN INCLUIDOS EN UNA ORDEN DE CONTEO ACTIVA ME SALTO LA UBICACION
                            continue;
                        endif;

                        //CALCULO LA CANTIDAD DE COMPRA DE LA CANTIDAD PENDIENTE DE RESERVAR
                        if ($rowMat->NUMERADOR_CONVERSION == 0):
                            $cantidadCompraUbicacion = $rowMatUbiReservar->STOCK_TOTAL;
                        else:
                            $cantidadCompraUbicacion = $rowMatUbiReservar->STOCK_TOTAL * $rowMat->DENOMINADOR_CONVERSION / $rowMat->NUMERADOR_CONVERSION;
                        endif;

                        //COMPRUEBO SI LA UBICACION TIENE EXCEDENTE DEL MATERIAL
                        if (fmod((float) $cantidadCompraUbicacion, 1) !== 0.00):
                            $cantidadExcedenteUbicacion = fmod((float) $cantidadCompraUbicacion, 1) * $factorConversion;

                            //SI HAY STOCK EXCEDENTE SUFICIENTE PARA SATISFACER LA CANTIDAD RESTANTE RESERVO EL EXCEDENTE NECESARIO, SI NO ME LLEVO EL EXCEDENTE AL COMPLETO
                            if (($cantidadExcedenteUbicacion - $cantidadExcedente) > EPSILON_SISTEMA):
                                $cantidadReservar = $cantidadExcedente;
                            else:
                                $cantidadReservar = $cantidadExcedenteUbicacion;
                            endif;

                            //RELLENO EL ARRAY
                            $arr_respuesta[$indice]["ID_MATERIAL"]                 = $idMaterial;
                            $arr_respuesta[$indice]["ID_UBICACION"]                = $rowMatUbiReservar->ID_UBICACION;
                            $arr_respuesta[$indice]["ID_MATERIAL_FISICO"]          = ($rowMatUbiReservar->ID_MATERIAL_FISICO == NULL ? NULL : $rowMatUbiReservar->ID_MATERIAL_FISICO);
                            $arr_respuesta[$indice]["ID_TIPO_BLOQUEO"]             = ($idTipoBloqueoReservar == NULL ? NULL : $idTipoBloqueoReservar);
                            $arr_respuesta[$indice]["ID_ORDEN_TRABAJO_MOVIMIENTO"] = ($rowMatUbiReservar->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? NULL : $rowMatUbiReservar->ID_ORDEN_TRABAJO_MOVIMIENTO);
                            $arr_respuesta[$indice]["ID_INCIDENCIA_CALIDAD"]       = ($rowMatUbiReservar->ID_INCIDENCIA_CALIDAD == NULL ? NULL : $rowMatUbiReservar->ID_INCIDENCIA_CALIDAD);
                            $arr_respuesta[$indice]["TIPO_LOTE"]                   = $rowMatAlm->TIPO_LOTE;
                            $arr_respuesta[$indice]["CANTIDAD"]                    = $cantidadReservar;

                            //ACTUALIZO EL INDICE
                            $indice = $indice + 1;

                            //ACTUALIZO LA CANTIDAD PENDIENTE DE PREPARAR
                            $cantidadExcedente = $cantidadExcedente - $cantidadReservar;
                            $cantidadPendienteReservar = $cantidadPendienteReservar - $cantidadReservar;

                            //ACTUALIZO LA CANTIDAD RESERVADA
                            $arrStockReservado[$rowBuscaUbicacion->ID_MATERIAL_UBICACION] += $cantidadReservar;
                        endif;
                    endif;
                endwhile;
                //FIN RECORRO LAS UBICACIONES PARA RESERVAR EL EXCEDENTE DE MATERIAL NECESARIO
            endif;
        endif;

        //SI SE PUEDE REALIZAR LA RESERVA PARA DEMANDAS DE OT
        if (($cantidadPendienteReservar > EPSILON_SISTEMA) && ($reservaOT)):

            //EJECUTO LA CONSULTA DE LAS UBICACIONES
            $resultBuscaUbicaciones = $bd->ExecSQL($sqlBuscaUbicaciones);

            //RECORRO LAS UBICACIONES PARA RESERVAR EL EXCEDENTE DE MATERIAL NECESARIO
            while (($rowBuscaUbicacion = $bd->SigReg($resultBuscaUbicaciones)) && ($cantidadPendienteReservar > EPSILON_SISTEMA)):

                //BUSCO EL MATERIAL UBICACION
                $rowMatUbiReservar = $bd->VerReg("MATERIAL_UBICACION", "ID_MATERIAL_UBICACION", $rowBuscaUbicacion->ID_MATERIAL_UBICACION);

                //COMPRUEBO SI HAY STOCK RESTANTE EN LA UBICACION CONTANDO LO YA GESTIONADO PREVIAMENTE
                if (($rowMatUbiReservar->STOCK_TOTAL - $arrStockReservado[$rowBuscaUbicacion->ID_MATERIAL_UBICACION]) > EPSILON_SISTEMA):
                    //PRIMERO COMPRUEBO QUE EL MATERIAL Y UBICACION NO ESTEN INCLUIDOS EN UNA ORDEN DE CONTEO ACTIVA
                    $sqlDatosIncluidosEnOrdenesConteoActivas    = "SELECT COUNT(*) AS NUM
                                                                    FROM INVENTARIO_ORDEN_CONTEO_LINEA IOCL
                                                                    INNER JOIN INVENTARIO_ORDEN_CONTEO IOC ON IOC.ID_INVENTARIO_ORDEN_CONTEO = IOCL.ID_INVENTARIO_ORDEN_CONTEO
                                                                    WHERE IOCL.ID_MATERIAL = $idMaterial AND IOCL.ID_UBICACION = $rowMatUbiReservar->ID_UBICACION AND IOCL.BAJA = 0 AND IOC.TIPO = 'Inventario' AND IOC.ESTADO <> 'Finalizado' AND IOC.BAJA = 0";
                    $resultDatosIncluidosEnOrdenesConteoActivas = $bd->ExecSQL($sqlDatosIncluidosEnOrdenesConteoActivas);
                    $rowDatosIncluidosEnOrdenesConteoActivas    = $bd->SigReg($resultDatosIncluidosEnOrdenesConteoActivas);
                    if ($rowDatosIncluidosEnOrdenesConteoActivas->NUM > 0): //SI LOS DATOS ESTAN INCLUIDOS EN UNA ORDEN DE CONTEO ACTIVA ME SALTO LA UBICACION
                        continue;
                    endif;

                    //SI HAY STOCK EXCEDENTE SUFICIENTE PARA SATISFACER LA CANTIDAD RESTANTE RESERVO EL EXCEDENTE NECESARIO, SI NO ME LLEVO EL EXCEDENTE AL COMPLETO
                    if (($rowMatUbiReservar->STOCK_TOTAL - $cantidadPendienteReservar) > EPSILON_SISTEMA):
                        $cantidadReservar = $cantidadPendienteReservar;
                    else:
                        $cantidadReservar = $rowMatUbiReservar->STOCK_TOTAL;
                    endif;

                    //RELLENO EL ARRAY
                    $arr_respuesta[$indice]["ID_MATERIAL"]                 = $idMaterial;
                    $arr_respuesta[$indice]["ID_UBICACION"]                = $rowMatUbiReservar->ID_UBICACION;
                    $arr_respuesta[$indice]["ID_MATERIAL_FISICO"]          = ($rowMatUbiReservar->ID_MATERIAL_FISICO == NULL ? NULL : $rowMatUbiReservar->ID_MATERIAL_FISICO);
                    $arr_respuesta[$indice]["ID_TIPO_BLOQUEO"]             = ($idTipoBloqueoReservar == NULL ? NULL : $idTipoBloqueoReservar);
                    $arr_respuesta[$indice]["ID_ORDEN_TRABAJO_MOVIMIENTO"] = ($rowMatUbiReservar->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? NULL : $rowMatUbiReservar->ID_ORDEN_TRABAJO_MOVIMIENTO);
                    $arr_respuesta[$indice]["ID_INCIDENCIA_CALIDAD"]       = ($rowMatUbiReservar->ID_INCIDENCIA_CALIDAD == NULL ? NULL : $rowMatUbiReservar->ID_INCIDENCIA_CALIDAD);
                    $arr_respuesta[$indice]["TIPO_LOTE"]                   = $rowMatAlm->TIPO_LOTE;
                    $arr_respuesta[$indice]["CANTIDAD"]                    = $cantidadReservar;

                    //ACTUALIZO EL INDICE
                    $indice = $indice + 1;

                    //ACTUALIZO LA CANTIDAD PENDIENTE DE PREPARAR
                    $cantidadPendienteReservar = $cantidadPendienteReservar - $cantidadReservar;

                    //ACTUALIZO LA CANTIDAD RESERVADA
                    $arrStockReservado[$rowBuscaUbicacion->ID_MATERIAL_UBICACION] += $cantidadReservar;
                endif;
            endwhile;
            //FIN RECORRO LAS UBICACIONES PARA RESERVAR EL EXCEDENTE DE MATERIAL NECESARIO
        endif;

        return $arr_respuesta;
    }

    /**
     * @param String $idColaReserva
     * @param Array $arrCambioRedFallidos => Nos indica cambios de red que han fallado para no volverlos a intentar.Clave: idMaterial_idAlmacen_idTipoBloqueoInicial_idMatFisico(0 si no tiene)
     * @return array
     * SE INTENTA REALIZAR LA RESERVA DE UNA COLA PROGRAMADA/PENDIENTE. SI SE CONSIGUE, QUEDA Cubierta y SI NO, Pendiente
     * El Orden es: Stock libre misma red, stock libre otra red, stock reservado ordenado por prioridad
     * SI DEVUELVE $idCambioEstadoGrupo, DEBEMOS ENVIARLO A SAP FUERA DE LA FUNCION
     */
    function procesar_cola_reserva($idColaReserva, $arrCambioRedFallidos = array())
    {
        global $bd;
        global $html;
        global $mat;
        global $auxiliar;
        global $administrador;
        global $incidencia_sistema;

        //ARRAY A DEVOLVER
        $arr_respuesta = array();

        //PARA CONTROLAR CAMBIOS DE RED
        $idCambioEstadoGrupo = NULL;
        $tipoCambioRed       = "";

        //OBTENEMOS LA COLA
        $rowCola = $bd->VerReg("COLA_RESERVA", "ID_COLA_RESERVA", $idColaReserva);

        //COMPROBAMOS QUE SIGUE PENDIENTE
        if (($rowCola->BAJA != 0) || (($rowCola->ESTADO != 'Programada') && ($rowCola->ESTADO != 'Pendiente'))):
            $arr_respuesta['error'] = $auxiliar->traduce('La cola de reserva esta en una estado que no permite ser procesada', $administrador->ID_IDIOMA);

            return $arr_respuesta;
        endif;

        //BUSCO LA DUPLA MATERIAL ALMACEN
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowMatAlm = $bd->VerRegRest("MATERIAL_ALMACEN", "ID_MATERIAL = $rowCola->ID_MATERIAL AND ID_ALMACEN = $rowCola->ID_ALMACEN_RESERVA", "No");
        unset($NotificaErrorPorEmail);

        if ($rowMatAlm == false):
            //GENERAMOS UNA COPIA DEL MATERIAL_ALMACEN ENCONTRADO
            $idMatAlm = $mat->ClonarMaterialAlmacen($rowCola->ID_MATERIAL, $rowCola->ID_ALMACEN_RESERVA, "Reservas");

            if ($idMatAlm != false):
                //INFORMO A LAS PERSONAS CORRESPONDIENTES DE LAS DUPLAS MATERIAL-ALMACEN RECIEN CREADAS
                $mat->EnviarNotificacionEmail_MaterialAlmacenNoDefinido($rowCola->ID_MATERIAL, $rowCola->ID_ALMACEN_RESERVA);

                //RECUPERO EL OBJETO CREADO
                $rowMatAlm = $bd->VerReg("MATERIAL_ALMACEN", "ID_MATERIAL_ALMACEN", $idMatAlm);
            else:
                $arr_respuesta['error'] = $auxiliar->traduce('La cola de reserva no ha podido ser procesada ya que la dupla material-almacen no esta definida', $administrador->ID_IDIOMA);

                return $arr_respuesta;
            endif;
        endif;

        //BUSCAMOS LA DEMANDA
        $rowDemanda = $bd->VerReg("DEMANDA", "ID_DEMANDA", $rowCola->ID_DEMANDA);

        //BUSCO EL TIPO DE BLOQUEO PLANIFICADO
        $rowTipoBloqueoPlanificado = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "SP");

        //BUSCO EL TIPO DE BLOQUEO RESERVADO
        $rowTipoBloqueoReservado = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "RV");

        //BUSCO EL TIPO DE BLOQUEO RESERVADO PLANIFICADO
        $rowTipoBloqueoReservadoPlanificado = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "RVP");

        //SEGUN EL TIPO DE OBJETO, BUSCAMOS LA RED DE BLOQUEO Y EL AMBITO
        $ambitoInternacional = false;
        $red_reserva         = "";
        switch ($rowDemanda->TIPO_DEMANDA):
            case "Pedido":
                //BUSCAMOS LA LINEA DE PEDIDO PARA OBTENER EL BLOQUEO
                $rowPedidoSalidaLinea = $bd->VerReg("PEDIDO_SALIDA_LINEA", "ID_PEDIDO_SALIDA_LINEA", $rowDemanda->ID_PEDIDO_SALIDA_LINEA);
                $idTipoBloqueo        = ($rowPedidoSalidaLinea->ID_TIPO_BLOQUEO != NULL ? $rowPedidoSalidaLinea->ID_TIPO_BLOQUEO : NULL);
                $red_reserva          = ($rowPedidoSalidaLinea->ID_TIPO_BLOQUEO == $rowTipoBloqueoPlanificado->ID_TIPO_BLOQUEO ? "Planificado" : "Correctivo");
                $tipoCambioRed        = ($idTipoBloqueo == NULL ? "PreventivoLibre" : "LibrePreventivo");

                //CALCULAMOS SI ES AMBITO INTERNACIONAL
                if (($rowPedidoSalidaLinea->ID_ALMACEN_ORIGEN != NULL) && ($rowPedidoSalidaLinea->ID_ALMACEN_DESTINO != NULL)):
                    //BUSCO EL ALMACEN ORIGEN
                    $rowAlmacenOrigen = $bd->VerReg("ALMACEN", "ID_ALMACEN", $rowPedidoSalidaLinea->ID_ALMACEN_ORIGEN);
                    //BUSCO EL CENTRO FISICO ORIGEN
                    $rowCentroFisicoOrigen = $bd->VerReg("CENTRO_FISICO", "ID_CENTRO_FISICO", $rowAlmacenOrigen->ID_CENTRO_FISICO);
                    //BUSCO LA DIRECCION DEL CENTRO FISICO ORIGEN
                    $rowDireccionCentroFisicoOrigen = $bd->VerRegRest("DIRECCION", "ID_CENTRO_FISICO = $rowCentroFisicoOrigen->ID_CENTRO_FISICO AND TIPO_DIRECCION = 'Centro Fisico'");
                    //BUSCO EL ALMACEN DESTINO
                    $rowAlmacenDestino = $bd->VerReg("ALMACEN", "ID_ALMACEN", $rowPedidoSalidaLinea->ID_ALMACEN_DESTINO);
                    //BUSCO EL CENTRO FISICO DESTINO
                    $rowCentroFisicoDestino = $bd->VerReg("CENTRO_FISICO", "ID_CENTRO_FISICO", $rowAlmacenDestino->ID_CENTRO_FISICO);
                    //BUSCO LA DIRECCION DEL CENTRO FISICO DESTINO
                    $rowDireccionCentroFisicoDestino = $bd->VerRegRest("DIRECCION", "ID_CENTRO_FISICO = $rowCentroFisicoDestino->ID_CENTRO_FISICO AND TIPO_DIRECCION = 'Centro Fisico'");

                    //COMPRUEBO SI ES DE AMBITO INTERNACIONAL
                    if ($rowDireccionCentroFisicoOrigen->ID_PAIS != $rowDireccionCentroFisicoDestino->ID_PAIS):
                        $ambitoInternacional = true;
                    endif;
                endif;//FIN CALCULAMOS SI ES AMBITO INTERNACIONAL

                break;
            case "OT":
                $idTipoBloqueo = $rowTipoBloqueoPlanificado->ID_TIPO_BLOQUEO;
                $red_reserva   = "Planificado";
                $tipoCambioRed = ($idTipoBloqueo == NULL ? "PreventivoLibre" : "LibrePreventivo");

                break;
            default:
                $idTipoBloqueo = NULL;
                break;
        endswitch;

        //BUSCAMOS LA PRIORIDAD DEMANDA
        $rowPrioridadDemanda = false;
        if ($rowDemanda->ID_PRIORIDAD_DEMANDA != NULL):
            $rowPrioridadDemanda = $bd->VerReg("PRIORIDAD_DEMANDA", "ID_PRIORIDAD_DEMANDA", $rowDemanda->ID_PRIORIDAD_DEMANDA);
        endif;

        //RESERVA PARA CREAR SOLO UNA EN ESTE PROCESO
        $idReserva = NULL;

        //VARIABLE PARA CONTROLAR LO RESERVADO
        $cantidadReservada       = 0;
        $cantidadPdteReservar    = $rowCola->CANTIDAD_EN_COLA;
        $cantidadPosibleReservar = $rowCola->CANTIDAD_EN_COLA;

        //SI ES LA RED LIBRE Y NO ESTA PERMITIDO QUITAR POR DEBAJO DEL SS, OBTENEMOS LA CANTIDAD MAXIMA POSIBLE A RESERVAR EN EL ALMACEN
        if (($idTipoBloqueo == NULL) && ($rowPrioridadDemanda->APLICA_CAMBIO_RED_DEBAJO_SS == 0)):
            //BUSCAMOS EL MATERIAL ALMACEN
            $rowMatAlm = $bd->VerRegRest("MATERIAL_ALMACEN", "ID_MATERIAL = " . $rowCola->ID_MATERIAL . " AND ID_ALMACEN = " . $rowCola->ID_ALMACEN_RESERVA);

            if ($rowMatAlm->PUNTO_REORDEN > 0):
                //BUSCMAOS LA CANTIDAD DISPONIBLE EN ALMACEN
                $CantidadDisponibleEnAlmacen = $mat->StockDisponible($rowCola->ID_MATERIAL, $rowCola->ID_ALMACEN_RESERVA, NULL);

                //SI LO QUE TENEMOS QUE RESERVAR MAS EL STOCK MINIMO ES MAS DE LO QUE HAY EN EL ALMACEN
                if ($CantidadDisponibleEnAlmacen < $cantidadPosibleReservar + $rowMatAlm->PUNTO_REORDEN):

                    //NOS QUEDAMOS CON EL MAXIMO POSIBLE A RESERVAR
                    $cantidadPosibleReservar = $CantidadDisponibleEnAlmacen - $rowMatAlm->PUNTO_REORDEN;
                    $cantidadPosibleReservar = ($cantidadPosibleReservar < EPSILON_SISTEMA ? 0 : $cantidadPosibleReservar);
                endif;
            endif;
        endif;

        //GUARDO LA VARIABLE PARA INDICAR SI SE PUEDE REALIZAR LA RESERVA DE FORMA PARCIAL
        $reservaParcial = "No";
        $reservaOT = false;
        if ($rowDemanda->TIPO_DEMANDA == "OT"):
            $reservaParcial = "Si";
            $reservaOT = true;
        elseif ($cantidadPdteReservar == $cantidadPosibleReservar):
            //BUSCO EL MATERIAL
            $rowMat = $bd->VerReg("MATERIAL", "ID_MATERIAL", $rowCola->ID_MATERIAL);

            if ($rowMat->ID_UNIDAD_MEDIDA != $rowMat->ID_UNIDAD_COMPRA && $rowMat->DENOMINADOR_CONVERSION != 0):
                if ($rowMat->DIVISIBILIDAD != 'Si'):
                    //CALCULO LA CANTIDAD DE COMPRA DE LA CANTIDAD PENDIENTE DE RESERVAR
                    $cantidadCompraPdteReservar = $mat->cantUnidadCompra($rowCola->ID_MATERIAL, $cantidadPdteReservar);

                    if (fmod((float) $cantidadCompraPdteReservar, 1) !== 0.00):
                        $reservaParcial = "Si";
                    endif;
                endif;
            endif;
        endif;

        //BUSCAMOS SI HAY STOCK DENTRO DE LA RED
        $arr_stock = $this->reserva_material_defecto($rowCola->ID_MATERIAL, $rowCola->ID_ALMACEN_RESERVA, $idTipoBloqueo, $cantidadPosibleReservar, $ambitoInternacional, $reservaParcial, $reservaOT);

        //EN CASO DE HABER, HACEMOS LA RESERVA
        if (($arr_stock != NULL) && (count( (array)$arr_stock) > 0)):
            //ASIGNAMOS ESE STOCK A LAS RESERVAS LINEA Y CAMBIAMOS EL ESTADO A RV/RVP
            $arr_reserva_linea = $this->asociar_reserva($rowCola->ID_DEMANDA, 'MaterialUbicacion', $arr_stock);
            if (isset($arr_reserva_linea['error']) && ($arr_reserva_linea['error'] != "")):
                $arr_respuesta['error'] = $arr_reserva_linea['error'];

                return $arr_respuesta;
            else:
                //ACTUALIZO LA VARIABLE DE LA CANTIDAD A DESCONTAR DE LA LINEA DEL PEDIDO DE SALIDA
                $cantidadReservada    = $cantidadReservada + $arr_reserva_linea["cantidad_reservada"];
                $cantidadPdteReservar = $cantidadPdteReservar - $arr_reserva_linea["cantidad_reservada"];
                $idReserva            = $arr_reserva_linea["idReserva"];
            endif;
        endif;

        //SI NO HAY STOCK Y LA PRIORIDAD_DEMANDA ADMITE CAMBIO DE RED, LO EJECUTAMOS
        if (($cantidadPdteReservar > EPSILON_SISTEMA) && ($rowPrioridadDemanda != false) && ($rowPrioridadDemanda->APLICA_CAMBIO_RED == 1)):

            //OBTENEMOS EL TIPO DE BLOQUEO DE LA OTRA RED
            $idTipoBloqueoOtraRed = ($idTipoBloqueo == NULL ? $rowTipoBloqueoPlanificado->ID_TIPO_BLOQUEO : NULL);

            //SI EL CAMBIO ES DE LIBRE A P Y NO ESTA PERMITIDO QUITAR POR DEBAJO DEL SS, OBTENEMOS LA CANTIDAD MAXIMA POSIBLE A RESERVAR EN EL ALMACEN
            $cantidadPosibleReservar = $cantidadPdteReservar;
            if (($idTipoBloqueoOtraRed == NULL) && ($rowPrioridadDemanda->APLICA_CAMBIO_RED_DEBAJO_SS == 0)):
                //BUSCAMOS EL MATERIAL ALMACEN
                $rowMatAlm = $bd->VerRegRest("MATERIAL_ALMACEN", "ID_MATERIAL = " . $rowCola->ID_MATERIAL . " AND ID_ALMACEN = " . $rowCola->ID_ALMACEN_RESERVA);
                if ($rowMatAlm->PUNTO_REORDEN > 0):
                    //BUSCMAOS LA CANTIDAD DISPONIBLE EN ALMACEN
                    $CantidadDisponibleEnAlmacen = $mat->StockDisponible($rowCola->ID_MATERIAL, $rowCola->ID_ALMACEN_RESERVA, NULL);

                    //SI LO QUE TENEMOS QUE RESERVAR MAS EL STOCK MINIMO ES MAS DE LO QUE HAY EN EL ALMACEN
                    if ($CantidadDisponibleEnAlmacen < $cantidadPosibleReservar + $rowMatAlm->PUNTO_REORDEN):
                        //NOS QUEDAMOS CON EL MAXIMO POSIBLE A RESERVAR
                        $cantidadPosibleReservar = $CantidadDisponibleEnAlmacen - $rowMatAlm->PUNTO_REORDEN;
                        $cantidadPosibleReservar = ($cantidadPosibleReservar < EPSILON_SISTEMA ? 0 : $cantidadPosibleReservar);
                    endif;
                endif;
            endif;

            //GUARDO LA VARIABLE PARA INDICAR SI SE PUEDE REALIZAR LA RESERVA DE FORMA PARCIAL
            $reservaParcial = "No";
            $reservaOT = false;
            if ($rowDemanda->TIPO_DEMANDA == "OT"):
                $reservaParcial = "Si";
                $reservaOT = true;
            elseif ($cantidadPdteReservar == $cantidadPosibleReservar):
                //BUSCO EL MATERIAL
                $rowMat = $bd->VerReg("MATERIAL", "ID_MATERIAL", $rowCola->ID_MATERIAL);

                if ($rowMat->ID_UNIDAD_MEDIDA != $rowMat->ID_UNIDAD_COMPRA && $rowMat->DENOMINADOR_CONVERSION != 0):
                    if ($rowMat->DIVISIBILIDAD != 'Si'):
                        //CALCULO LA CANTIDAD DE COMPRA DE LA CANTIDAD PENDIENTE DE RESERVAR
                        $cantidadCompraPdteReservar = $mat->cantUnidadCompra($rowCola->ID_MATERIAL, $cantidadPdteReservar);

                        if (fmod((float) $cantidadCompraPdteReservar, 1) !== 0.00):
                            $reservaParcial = "Si";
                        endif;
                    endif;
                endif;
            endif;

            //OBTEMOS EL STOCK A RESERVAR
            $arr_stock_otra_red = $this->reserva_material_defecto($rowCola->ID_MATERIAL, $rowCola->ID_ALMACEN_RESERVA, $idTipoBloqueoOtraRed, $cantidadPosibleReservar, $ambitoInternacional, $reservaParcial, $reservaOT);

            //EN CASO DE HABER, HACEMOS LA RESERVA
            if (($arr_stock_otra_red != NULL) && (count( (array)$arr_stock_otra_red) > 0)):

                //HACEMOS LOS CAMBIOS DE ESTADO
                foreach ($arr_stock_otra_red as $indice => $arrValores):

                    //SI EL MATERIAL HA DADO ERROR EN SAP, NOS LO SALTAMOS
                    //CLAVE: idMaterial_idAlmacen_idTipoBloqueoInicial_idMatFisico(0 si no tiene)
                    $clave_mat = $rowCola->ID_MATERIAL . "_" . $rowCola->ID_ALMACEN_RESERVA . "_" . ($idTipoBloqueoOtraRed == NULL ? "0" : $idTipoBloqueoOtraRed) . "_" . ($arrValores['ID_MATERIAL_FISICO'] != NULL ? $arrValores['ID_MATERIAL_FISICO'] : "0");
                    if (isset($arrCambioRedFallidos[$clave_mat]) && ($arrCambioRedFallidos[$clave_mat] != "")):
                        unset($arr_stock_otra_red[$indice]);
                        continue;
                    endif;

                    //PRIMERO CAMBIAMOS EL STOCK DE RED
                    if ($idCambioEstadoGrupo == NULL):
                        //GENERO EL CAMBIO DE ESTADO GRUPO
                        $sqlInsert = "INSERT INTO CAMBIO_ESTADO_GRUPO SET
                                    FECHA = '" . date("Y-m-d H:i:s") . "'
                                    , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                    , NOMBRE = 'R+" . $rowDemanda->ID_DEMANDA . "'
                                    , PENDIENTE_REVERTIR = 0";
                        $bd->ExecSQL($sqlInsert);
                        $idCambioEstadoGrupo = $bd->IdAsignado();

                        //ACTUALIZO EL NOMBRE DEL CAMBIO DE ESTADO GRUPO GENERADO
                        $sqlUpdate = "UPDATE CAMBIO_ESTADO_GRUPO SET
                                    NOMBRE = 'D" . $idCambioEstadoGrupo . "'
                                    WHERE ID_CAMBIO_ESTADO_GRUPO = $idCambioEstadoGrupo";
                        $bd->ExecSQL($sqlUpdate);
                    endif;

                    //BUSCO MATERIAL_UBICACION DESTINO
                    $clausulaWhere        = "ID_MATERIAL = " . $arrValores['ID_MATERIAL'] . " AND ID_UBICACION = " . $arrValores['ID_UBICACION'] . " AND ID_MATERIAL_FISICO " . ($arrValores['ID_MATERIAL_FISICO'] == NULL ? "IS NULL" : "= " . $arrValores['ID_MATERIAL_FISICO']) . " AND ID_TIPO_BLOQUEO " . ($arrValores['ID_TIPO_BLOQUEO'] == NULL ? " IS NULL " : " = " . $arrValores['ID_TIPO_BLOQUEO']) . " AND ID_ORDEN_TRABAJO_MOVIMIENTO " . ($arrValores['ID_ORDEN_TRABAJO_MOVIMIENTO'] == NULL ? "IS NULL" : "= " . $arrValores['ID_ORDEN_TRABAJO_MOVIMIENTO']) . " AND ID_INCIDENCIA_CALIDAD " . ($arrValores['ID_INCIDENCIA_CALIDAD'] == NULL ? "IS NULL" : "= " . $arrValores['ID_INCIDENCIA_CALIDAD']);
                    $rowMaterialUbicacion = $bd->VerRegRest("MATERIAL_UBICACION", $clausulaWhere);

                    //GENERAMOS EL CAMBIO DE ESTADO
                    $sqlInsert = "INSERT INTO CAMBIO_ESTADO SET
                                        ID_CAMBIO_ESTADO_GRUPO = $idCambioEstadoGrupo
                                        , FECHA = '" . date("Y-m-d H:i:s") . "'
                                        , TIPO_CAMBIO_ESTADO = 'Automatico'
                                        , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                        , ID_MATERIAL = $rowMaterialUbicacion->ID_MATERIAL
                                        , ID_MATERIAL_FISICO = " . ($rowMaterialUbicacion->ID_MATERIAL_FISICO == NULL ? "NULL" : $rowMaterialUbicacion->ID_MATERIAL_FISICO) . "
                                        , ID_UBICACION = $rowMaterialUbicacion->ID_UBICACION
                                        , CANTIDAD = " . $arrValores['CANTIDAD'] . "
                                        , ID_TIPO_BLOQUEO_INICIAL = " . ($idTipoBloqueoOtraRed == NULL ? "NULL" : $rowTipoBloqueoPlanificado->ID_TIPO_BLOQUEO) . "
                                        , ID_TIPO_BLOQUEO_FINAL = " . ($idTipoBloqueoOtraRed == NULL ? $rowTipoBloqueoPlanificado->ID_TIPO_BLOQUEO : "NULL") . "
                                        , ID_ORDEN_TRABAJO_MOVIMIENTO = " . ($rowMaterialUbicacion->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? "NULL" : $rowMaterialUbicacion->ID_ORDEN_TRABAJO_MOVIMIENTO) . "
                                        , ID_INCIDENCIA_CALIDAD = " . ($rowMaterialUbicacion->ID_INCIDENCIA_CALIDAD == NULL ? "NULL" : $rowMaterialUbicacion->ID_INCIDENCIA_CALIDAD) . "
                                        , OBSERVACIONES = 'Cambio para Demanda" . $rowDemanda->ID_DEMANDA . "'";
                    $bd->ExecSQL($sqlInsert);
                    $idCambioEstado = $bd->IdAsignado();

                    //DECREMENTO MATERIAL_UBICACION ORIGEN
                    $sqlUpdate = "UPDATE MATERIAL_UBICACION SET
                                        STOCK_TOTAL = STOCK_TOTAL - " . $arrValores['CANTIDAD'] . "
                                        , STOCK_OK = STOCK_OK - " . ($idTipoBloqueoOtraRed == NULL ? $arrValores['CANTIDAD'] : 0) . "
                                        , STOCK_BLOQUEADO = STOCK_BLOQUEADO - " . ($idTipoBloqueoOtraRed == NULL ? 0 : $arrValores['CANTIDAD']) . "
                                        WHERE ID_MATERIAL_UBICACION = $rowMaterialUbicacion->ID_MATERIAL_UBICACION";
                    $bd->ExecSQL($sqlUpdate);

                    //BUSCO MATERIAL_UBICACION DESTINO
                    $clausulaWhere                    = "ID_MATERIAL = $rowMaterialUbicacion->ID_MATERIAL AND ID_UBICACION = $rowMaterialUbicacion->ID_UBICACION AND ID_MATERIAL_FISICO " . ($rowMaterialUbicacion->ID_MATERIAL_FISICO == NULL ? "IS NULL" : "= $rowMaterialUbicacion->ID_MATERIAL_FISICO") . " AND ID_TIPO_BLOQUEO " . ($idTipoBloqueoOtraRed == NULL ? " = " . $rowTipoBloqueoPlanificado->ID_TIPO_BLOQUEO : " IS NULL ") . " AND ID_ORDEN_TRABAJO_MOVIMIENTO " . ($rowMaterialUbicacion->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? "IS NULL" : "= $rowMaterialUbicacion->ID_ORDEN_TRABAJO_MOVIMIENTO") . " AND ID_INCIDENCIA_CALIDAD " . ($rowMaterialUbicacion->ID_INCIDENCIA_CALIDAD == NULL ? "IS NULL" : "= $rowMaterialUbicacion->ID_INCIDENCIA_CALIDAD");
                    $GLOBALS["NotificaErrorPorEmail"] = "No";
                    $rowMatUbiDestino                 = $bd->VerRegRest("MATERIAL_UBICACION", $clausulaWhere, "No");
                    unset($GLOBALS["NotificaErrorPorEmail"]);
                    if ($rowMatUbiDestino == false):
                        //CREO MATERIAL UBICACION DESTINO
                        $sqlInsert = "INSERT INTO MATERIAL_UBICACION SET
                                            ID_MATERIAL = $rowMaterialUbicacion->ID_MATERIAL
                                            , ID_UBICACION = $rowMaterialUbicacion->ID_UBICACION
                                            , ID_MATERIAL_FISICO = " . ($rowMaterialUbicacion->ID_MATERIAL_FISICO == NULL ? "NULL" : $rowMaterialUbicacion->ID_MATERIAL_FISICO) . "
                                            , ID_TIPO_BLOQUEO = " . ($idTipoBloqueoOtraRed == NULL ? $rowTipoBloqueoPlanificado->ID_TIPO_BLOQUEO : "NULL") . "
                                            , ID_ORDEN_TRABAJO_MOVIMIENTO = " . ($rowMaterialUbicacion->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? "NULL" : $rowMaterialUbicacion->ID_ORDEN_TRABAJO_MOVIMIENTO) . "
                                            , ID_INCIDENCIA_CALIDAD = " . ($rowMaterialUbicacion->ID_INCIDENCIA_CALIDAD == NULL ? "NULL" : $rowMaterialUbicacion->ID_INCIDENCIA_CALIDAD);
                        $bd->ExecSQL($sqlInsert);
                        $idMatUbiDestino = $bd->IdAsignado();
                    else:
                        $idMatUbiDestino = $rowMatUbiDestino->ID_MATERIAL_UBICACION;
                    endif;

                    //INCREMENTO MATERIAL_UBICACION DESTINO
                    $sqlUpdate = "UPDATE MATERIAL_UBICACION SET
                                        STOCK_TOTAL = STOCK_TOTAL + " . $arrValores['CANTIDAD'] . "
                                        , STOCK_OK = STOCK_OK + " . ($idTipoBloqueoOtraRed == NULL ? 0 : $arrValores['CANTIDAD']) . "
                                        , STOCK_BLOQUEADO = STOCK_BLOQUEADO + " . ($idTipoBloqueoOtraRed == NULL ? $arrValores['CANTIDAD'] : 0) . "
                                        WHERE ID_MATERIAL_UBICACION = $idMatUbiDestino";
                    $bd->ExecSQL($sqlUpdate);

                    // LOG MOVIMIENTOS
                    $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Creación", "Cambio estado", $idCambioEstado, "Cambio estado por Demanda:" . $rowDemanda->ID_DEMANDA);

                    //ACTUALIZAMOS EL BLOQUEO DEL ARRAY PARA HACER LA RESERVA
                    $arr_stock_otra_red[$indice]['ID_TIPO_BLOQUEO'] = $idTipoBloqueo;
                endforeach;
            endif;

            //ASIGNAMOS ESE STOCK A LAS RESERVAS LINEA Y CAMBIAMOS EL ESTADO A RV/RVP
            //EN CASO DE HABER, HACEMOS LA RESERVA
            if (($arr_stock_otra_red != NULL) && (count( (array)$arr_stock_otra_red) > 0)):
                $arr_reserva_linea = $this->asociar_reserva($rowCola->ID_DEMANDA, 'MaterialUbicacion', $arr_stock_otra_red);
                if (isset($arr_reserva_linea['error']) && ($arr_reserva_linea['error'] != "")):
                    $arr_respuesta['error'] = $arr_reserva_linea['error'];

                    return $arr_respuesta;
                else:
                    //ACTUALIZO LA VARIABLE DE LA CANTIDAD A DESCONTAR DE LA LINEA DEL PEDIDO DE SALIDA
                    $cantidadReservada    = $cantidadReservada + $arr_reserva_linea["cantidad_reservada"];
                    $cantidadPdteReservar = $cantidadPdteReservar - $arr_reserva_linea["cantidad_reservada"];
                    $idReserva            = $arr_reserva_linea["idReserva"];
                endif;
            endif;//FIN HAY STOCK EN LA OTRA RED
        endif;//FIN SI NO HAY STOCK Y LA PRIORIDAD_DEMANDA ADMITE CAMBIO DE RED, LO EJECUTAMOS


        //SI QUEDA CANTIDAD Y LA PRIORIDAD DEMANDA DEJA HACER ROBOS
        if (($cantidadPdteReservar > EPSILON_SISTEMA) && ($rowPrioridadDemanda != false) && (($rowPrioridadDemanda->APLICA_SOBRE_RESERVADO == 1) || ($rowPrioridadDemanda->APLICA_CAMBIO_RED_SOBRE_RESERVADO == 1))):

            //BUSCAMOS RESERVAS DE DEMANDAS CON MENOS PRIORIDAD, O A MISMA PRIORIDAD CON FECHA CREACION MAS NUEVA
            //SOLO DEMANDAS DE PEDIDOS
            $sqlReservas    = "SELECT SUM(RL.CANTIDAD) AS CANTIDAD_RESERVADA, R.ID_DEMANDA, D.TIPO_DEMANDA, D.ID_PEDIDO_SALIDA_LINEA, RL.ID_MATERIAL_FISICO
                                FROM RESERVA_LINEA RL 
                                INNER JOIN RESERVA R ON R.ID_RESERVA = RL.ID_RESERVA
                                INNER JOIN DEMANDA D ON D.ID_DEMANDA = R.ID_DEMANDA
                                INNER JOIN PRIORIDAD_DEMANDA PD ON PD.ID_PRIORIDAD_DEMANDA = D.ID_PRIORIDAD_DEMANDA
                                WHERE D.TIPO_DEMANDA = 'Pedido' AND RL.ID_MATERIAL = $rowCola->ID_MATERIAL AND R.ID_ALMACEN_RESERVA = $rowCola->ID_ALMACEN_RESERVA AND RL.ESTADO_LINEA = 'Reservada' 
                                      AND (PD.PRIORIDAD >  " . $rowPrioridadDemanda->PRIORIDAD . " OR (PD.PRIORIDAD = " . $rowPrioridadDemanda->PRIORIDAD . " AND D.FECHA_CREACION > '" . $rowDemanda->FECHA_CREACION . "'))
                                      AND RL.RESERVA_FIJA = 0 AND R.BAJA = 0 AND RL.BAJA = 0
                                GROUP BY R.ID_DEMANDA
                                ORDER BY PD.PRIORIDAD DESC, D.FECHA_CREACION DESC";
            $resultReservas = $bd->ExecSQL($sqlReservas);
            while (($rowReservasARobar = $bd->SigReg($resultReservas)) && ($cantidadPdteReservar > EPSILON_SISTEMA)):

                //BUSCAMOS LA RED DE LA DEMANDA
                $red_reserva_robo = "";
                switch ($rowReservasARobar->TIPO_DEMANDA):
                    case 'Pedido':
                        //BUSCAMOS LA LINEA DE PEDIDO
                        $rowPSL               = $bd->VerReg("PEDIDO_SALIDA_LINEA", "ID_PEDIDO_SALIDA_LINEA", $rowReservasARobar->ID_PEDIDO_SALIDA_LINEA);
                        $red_reserva_robo     = ($rowPSL->ID_TIPO_BLOQUEO == $rowTipoBloqueoPlanificado->ID_TIPO_BLOQUEO ? "Planificado" : "Correctivo");
                        $idTipoBloqueoOtraRed = ($rowPSL->ID_TIPO_BLOQUEO == $rowTipoBloqueoPlanificado->ID_TIPO_BLOQUEO ? $rowTipoBloqueoPlanificado->ID_TIPO_BLOQUEO : NULL);
                        break;

                    case 'OT'://NO SE PUEDE ROBAR A RESERVAS DE OT (Azure 212028)
                    default:
                        //continue;
                        break;
                endswitch;

                //SI ES LA MISMA RED Y LA PRIORIDAD LO ADMITE
                if (($red_reserva_robo == $red_reserva) && ($rowPrioridadDemanda->APLICA_SOBRE_RESERVADO == 1)):

                    //SI HAY SUFICIENTE CANTIDAD PARA ROBAR
                    if ($rowReservasARobar->CANTIDAD_RESERVADA >= $cantidadPdteReservar):
                        $cantidadRobar        = $cantidadPdteReservar;
                        $cantidadPdteReservar = 0;
                    else:
                        $cantidadRobar        = $rowReservasARobar->CANTIDAD_RESERVADA;
                        $cantidadPdteReservar = $cantidadPdteReservar - $rowReservasARobar->CANTIDAD_RESERVADA;
                    endif;
                    //EJECUTAMOS EL ROBO CON TIPO ROBO DESDE MISMA RED
                    $arr_respuesta_robo = $this->robo_reserva_entre_demandas($rowReservasARobar->ID_DEMANDA, $rowDemanda->ID_DEMANDA, $cantidadRobar, "RoboDesdeCola");
                    if (isset($arr_respuesta_robo['error']) && ($arr_respuesta_robo['error'] != "")):
                        $arr_respuesta['error'] = $arr_respuesta_robo['error'];

                        return $arr_respuesta;
                    endif;

                    //BUSCO LA DEMANDA A LA QUE LE ROBAN
                    $rowDemandaRobada = $bd->VerReg("DEMANDA", "ID_DEMANDA", $rowReservasARobar->ID_DEMANDA);

                    //SI LA DEMANDA ES DE PEDIDO
                    if ($rowDemandaRobada->ID_PEDIDO_SALIDA_LINEA != NULL):
                        $sqlIdOrdenTrabajoLinea    = "SELECT PSL.ID_ORDEN_TRABAJO_LINEA 
                                                            FROM PEDIDO_SALIDA_LINEA PSL
                                                            WHERE PSL.ID_PEDIDO_SALIDA_LINEA = $rowDemandaRobada->ID_PEDIDO_SALIDA_LINEA AND PSL.BAJA = 0
                                                        UNION 
                                                        SELECT OTLCP.ID_ORDEN_TRABAJO_LINEA 
                                                            FROM ORDEN_TRABAJO_LINEA_CUBIERTA_PEDIDO OTLCP 
                                                            WHERE OTLCP.ID_PEDIDO_SALIDA_LINEA = $rowDemandaRobada->ID_PEDIDO_SALIDA_LINEA AND OTLCP.BAJA = 0";
                        $resultIdOrdenTrabajoLinea = $bd->ExecSQL($sqlIdOrdenTrabajoLinea);
                        while ($rowIdOrdenTrabajoLinea = $bd->SigReg($resultIdOrdenTrabajoLinea)):
                            $arr_respuesta['lanzar_T06'][] = $rowIdOrdenTrabajoLinea->ID_ORDEN_TRABAJO_LINEA;
                        endwhile;
                    endif;
                    //FIN SI LA DEMANDA ES DE PEDIDO

                    //SI LA DEMANDA ES DE PEDIDO
                    if ($rowDemanda->ID_PEDIDO_SALIDA_LINEA != NULL):
                        $sqlIdOrdenTrabajoLinea    = "SELECT PSL.ID_ORDEN_TRABAJO_LINEA 
                                                           FROM PEDIDO_SALIDA_LINEA PSL
                                                           WHERE PSL.ID_PEDIDO_SALIDA_LINEA = $rowDemanda->ID_PEDIDO_SALIDA_LINEA AND PSL.BAJA = 0
                                                       UNION 
                                                       SELECT OTLCP.ID_ORDEN_TRABAJO_LINEA 
                                                           FROM ORDEN_TRABAJO_LINEA_CUBIERTA_PEDIDO OTLCP 
                                                           WHERE OTLCP.ID_PEDIDO_SALIDA_LINEA = $rowDemanda->ID_PEDIDO_SALIDA_LINEA AND OTLCP.BAJA = 0";
                        $resultIdOrdenTrabajoLinea = $bd->ExecSQL($sqlIdOrdenTrabajoLinea);
                        while ($rowIdOrdenTrabajoLinea = $bd->SigReg($resultIdOrdenTrabajoLinea)):
                            $arr_respuesta['lanzar_T06'][] = $rowIdOrdenTrabajoLinea->ID_ORDEN_TRABAJO_LINEA;
                        endwhile;
                    endif;
                    //FIN SI LA DEMANDA ES DE PEDIDO

                //SI NO HAY STOCK Y LA PRIORIDAD_DEMANDA ADMITE ROBO A OTRAS RESERVAS DE OTRAS RED, LO EJECUTAMOS
                elseif (($red_reserva_robo != $red_reserva) && ($rowPrioridadDemanda->APLICA_CAMBIO_RED_SOBRE_RESERVADO == 1)):

                    //SI EL MATERIAL HA DADO ERROR EN SAP, NOS LO SALTAMOS
                    //SI ES NO LOTABLE/SERIABLE
                    if ($rowReservasARobar->ID_MATERIAL_FISICO == NULL):
                        //CLAVE: idMaterial_idAlmacen_idTipoBloqueoInicial_idMatFisico(0 si no tiene)
                        $clave_mat = $rowCola->ID_MATERIAL . "_" . $rowCola->ID_ALMACEN_RESERVA . "_" . ($idTipoBloqueoOtraRed == NULL ? "0" : $idTipoBloqueoOtraRed) . "_0";
                        if (isset($arrCambioRedFallidos[$clave_mat]) && ($arrCambioRedFallidos[$clave_mat] != "")):
                            continue;
                        endif;
                    else:
                        //OBTENEMOS LAS LINEAS DE LA DEMANDA SIN GROUP BY
                        $sqlLineas    = "SELECT RL.ID_RESERVA_LINEA, RL.ID_MATERIAL_FISICO
                                            FROM RESERVA_LINEA RL 
                                            INNER JOIN RESERVA R ON R.ID_RESERVA = RL.ID_RESERVA
                                            WHERE R.ID_DEMANDA = " . $rowReservasARobar->ID_DEMANDA . " AND RL.ESTADO_LINEA = 'Reservada' AND RL.RESERVA_FIJA = 0 AND R.BAJA = 0 AND RL.BAJA = 0";
                        $resultLineas = $bd->ExecSQL($sqlLineas);
                        while ($rowLinea = $bd->SigReg($resultLineas)):
                            //CLAVE: idMaterial_idAlmacen_idTipoBloqueoInicial_idMatFisico(0 si no tiene)
                            $clave_mat = $rowCola->ID_MATERIAL . "_" . $rowCola->ID_ALMACEN_RESERVA . "_" . ($idTipoBloqueoOtraRed == NULL ? "0" : $idTipoBloqueoOtraRed) . "_" . ($rowLinea->ID_MATERIAL_FISICO != NULL ? $rowLinea->ID_MATERIAL_FISICO : "0");
                            if (isset($arrCambioRedFallidos[$clave_mat]) && ($arrCambioRedFallidos[$clave_mat] != "")):
                                continue 2; //NOS SALTAMS LA DEMANDA
                            endif;
                        endwhile;
                    endif;

                    //SI HAY SUFICIENTE CANTIDAD PARA ROBAR
                    if ($rowReservasARobar->CANTIDAD_RESERVADA >= $cantidadPdteReservar):
                        $cantidadRobar        = $cantidadPdteReservar;
                        $cantidadPdteReservar = 0;
                    else:
                        $cantidadRobar        = $rowReservasARobar->CANTIDAD_RESERVADA;
                        $cantidadPdteReservar = $cantidadPdteReservar - $rowReservasARobar->CANTIDAD_RESERVADA;
                    endif;
                    //EJECUTAMOS EL ROBO CON TIPO ROBO DESDE OTRA RED
                    $arr_respuesta_robo = $this->robo_reserva_entre_demandas($rowReservasARobar->ID_DEMANDA, $rowDemanda->ID_DEMANDA, $cantidadRobar, "RoboDesdeColaOtraRed");
                    if (isset($arr_respuesta_robo['error']) && ($arr_respuesta_robo['error'] != "")):
                        $arr_respuesta['error'] = $arr_respuesta_robo['error'];

                        return $arr_respuesta;

                    elseif ((isset($arr_respuesta_robo['arrLineasRobadas'])) && (count( (array)$arr_respuesta_robo['arrLineasRobadas']) > 0))://HACEMOS EL CAMBIO DE ESTADO DEL STOCK ROBADO

                        //CAMBIAMOS EL STOCK ENTRE RV y RVP
                        if ($idCambioEstadoGrupo == NULL):
                            //GENERO EL CAMBIO DE ESTADO GRUPO

                            $sqlInsert = "INSERT INTO CAMBIO_ESTADO_GRUPO SET
                                            FECHA = '" . date("Y-m-d H:i:s") . "'
                                            , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                            , NOMBRE = 'R+" . $rowDemanda->ID_DEMANDA . "'
                                            , PENDIENTE_REVERTIR = 0";
                            $bd->ExecSQL($sqlInsert);
                            $idCambioEstadoGrupo = $bd->IdAsignado();

                            //ACTUALIZO EL NOMBRE DEL CAMBIO DE ESTADO GRUPO GENERADO
                            $sqlUpdate = "UPDATE CAMBIO_ESTADO_GRUPO SET
                                            NOMBRE = 'D" . $idCambioEstadoGrupo . "'
                                            WHERE ID_CAMBIO_ESTADO_GRUPO = $idCambioEstadoGrupo";
                            $bd->ExecSQL($sqlUpdate);
                        endif;

                        //HACEMOS LOS CAMBIOS DE ESTADO
                        foreach ($arr_respuesta_robo['arrLineasRobadas'] as $idReservaLinea => $cantidadCambiar):
                            //BUSCAMOS LA RESERVA LINEA
                            $rowRLRobada = $bd->VerReg("RESERVA_LINEA", "ID_RESERVA_LINEA", $idReservaLinea);

                            //BUSCO MATERIAL_UBICACION ORIGEN
                            $clausulaWhere        = "ID_MATERIAL = " . $rowRLRobada->ID_MATERIAL . " AND ID_UBICACION = " . $rowRLRobada->ID_UBICACION . " AND ID_MATERIAL_FISICO " . ($rowRLRobada->ID_MATERIAL_FISICO == NULL ? "IS NULL" : "= " . $rowRLRobada->ID_MATERIAL_FISICO) . " AND ID_TIPO_BLOQUEO " . ($rowRLRobada->ID_TIPO_BLOQUEO == NULL ? " IS NULL " : " = " . $rowRLRobada->ID_TIPO_BLOQUEO) . " AND ID_ORDEN_TRABAJO_MOVIMIENTO " . ($rowRLRobada->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? "IS NULL" : "= " . $rowRLRobada->ID_ORDEN_TRABAJO_MOVIMIENTO) . " AND ID_INCIDENCIA_CALIDAD " . ($rowRLRobada->ID_INCIDENCIA_CALIDAD == NULL ? "IS NULL" : "= " . $rowRLRobada->ID_INCIDENCIA_CALIDAD);
                            $rowMaterialUbicacion = $bd->VerRegRest("MATERIAL_UBICACION", $clausulaWhere);

                            //GENERAMOS EL CAMBIO DE ESTADO
                            $sqlInsert = "INSERT INTO CAMBIO_ESTADO SET
                                            ID_CAMBIO_ESTADO_GRUPO = $idCambioEstadoGrupo
                                            , FECHA = '" . date("Y-m-d H:i:s") . "'
                                            , TIPO_CAMBIO_ESTADO = 'Automatico'
                                            , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                            , ID_MATERIAL = $rowMaterialUbicacion->ID_MATERIAL
                                            , ID_MATERIAL_FISICO = " . ($rowMaterialUbicacion->ID_MATERIAL_FISICO == NULL ? "NULL" : $rowMaterialUbicacion->ID_MATERIAL_FISICO) . "
                                            , ID_UBICACION = $rowMaterialUbicacion->ID_UBICACION
                                            , CANTIDAD = " . $cantidadCambiar . "
                                            , ID_TIPO_BLOQUEO_INICIAL = " . ($idTipoBloqueoOtraRed == NULL ? $rowTipoBloqueoReservado->ID_TIPO_BLOQUEO : $rowTipoBloqueoReservadoPlanificado->ID_TIPO_BLOQUEO) . "
                                            , ID_TIPO_BLOQUEO_FINAL = " . ($idTipoBloqueoOtraRed == NULL ? $rowTipoBloqueoReservadoPlanificado->ID_TIPO_BLOQUEO : $rowTipoBloqueoReservado->ID_TIPO_BLOQUEO) . "
                                            , ID_ORDEN_TRABAJO_MOVIMIENTO = " . ($rowMaterialUbicacion->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? "NULL" : $rowMaterialUbicacion->ID_ORDEN_TRABAJO_MOVIMIENTO) . "
                                            , ID_INCIDENCIA_CALIDAD = " . ($rowMaterialUbicacion->ID_INCIDENCIA_CALIDAD == NULL ? "NULL" : $rowMaterialUbicacion->ID_INCIDENCIA_CALIDAD) . "
                                            , OBSERVACIONES = 'Cambio Red para Demanda" . $rowDemanda->ID_DEMANDA . "'";
                            $bd->ExecSQL($sqlInsert);
                            $idCambioEstado = $bd->IdAsignado();

                            //DECREMENTO MATERIAL_UBICACION ORIGEN
                            $sqlUpdate = "UPDATE MATERIAL_UBICACION SET
                                            STOCK_TOTAL = STOCK_TOTAL - " . $cantidadCambiar . "
                                            , STOCK_BLOQUEADO = STOCK_BLOQUEADO - " . $cantidadCambiar . "
                                            WHERE ID_MATERIAL_UBICACION = $rowMaterialUbicacion->ID_MATERIAL_UBICACION";
                            $bd->ExecSQL($sqlUpdate);

                            //BUSCO MATERIAL_UBICACION DESTINO
                            $clausulaWhere                    = "ID_MATERIAL = $rowMaterialUbicacion->ID_MATERIAL AND ID_UBICACION = $rowMaterialUbicacion->ID_UBICACION AND ID_MATERIAL_FISICO " . ($rowMaterialUbicacion->ID_MATERIAL_FISICO == NULL ? "IS NULL" : "= $rowMaterialUbicacion->ID_MATERIAL_FISICO") . " AND ID_TIPO_BLOQUEO " . ($idTipoBloqueoOtraRed == NULL ? " = " . $rowTipoBloqueoReservadoPlanificado->ID_TIPO_BLOQUEO : " = " . $rowTipoBloqueoReservado->ID_TIPO_BLOQUEO) . " AND ID_ORDEN_TRABAJO_MOVIMIENTO " . ($rowMaterialUbicacion->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? "IS NULL" : "= $rowMaterialUbicacion->ID_ORDEN_TRABAJO_MOVIMIENTO") . " AND ID_INCIDENCIA_CALIDAD " . ($rowMaterialUbicacion->ID_INCIDENCIA_CALIDAD == NULL ? "IS NULL" : "= $rowMaterialUbicacion->ID_INCIDENCIA_CALIDAD");
                            $GLOBALS["NotificaErrorPorEmail"] = "No";
                            $rowMatUbiDestino                 = $bd->VerRegRest("MATERIAL_UBICACION", $clausulaWhere, "No");
                            unset($GLOBALS["NotificaErrorPorEmail"]);
                            if ($rowMatUbiDestino == false):
                                //CREO MATERIAL UBICACION DESTINO
                                $sqlInsert = "INSERT INTO MATERIAL_UBICACION SET
                                                ID_MATERIAL = $rowMaterialUbicacion->ID_MATERIAL
                                                , ID_UBICACION = $rowMaterialUbicacion->ID_UBICACION
                                                , ID_MATERIAL_FISICO = " . ($rowMaterialUbicacion->ID_MATERIAL_FISICO == NULL ? "NULL" : $rowMaterialUbicacion->ID_MATERIAL_FISICO) . "
                                                , ID_TIPO_BLOQUEO = " . ($idTipoBloqueoOtraRed == NULL ? $rowTipoBloqueoReservadoPlanificado->ID_TIPO_BLOQUEO : $rowTipoBloqueoReservado->ID_TIPO_BLOQUEO) . "
                                                , ID_ORDEN_TRABAJO_MOVIMIENTO = " . ($rowMaterialUbicacion->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? "NULL" : $rowMaterialUbicacion->ID_ORDEN_TRABAJO_MOVIMIENTO) . "
                                                , ID_INCIDENCIA_CALIDAD = " . ($rowMaterialUbicacion->ID_INCIDENCIA_CALIDAD == NULL ? "NULL" : $rowMaterialUbicacion->ID_INCIDENCIA_CALIDAD);
                                $bd->ExecSQL($sqlInsert);
                                $idMatUbiDestino = $bd->IdAsignado();
                            else:
                                $idMatUbiDestino = $rowMatUbiDestino->ID_MATERIAL_UBICACION;
                            endif;

                            //INCREMENTO MATERIAL_UBICACION DESTINO
                            $sqlUpdate = "UPDATE MATERIAL_UBICACION SET
                                            STOCK_TOTAL = STOCK_TOTAL + " . $cantidadCambiar . "
                                            , STOCK_BLOQUEADO = STOCK_BLOQUEADO + " . $cantidadCambiar . "
                                            WHERE ID_MATERIAL_UBICACION = $idMatUbiDestino";
                            $bd->ExecSQL($sqlUpdate);

                            // LOG MOVIMIENTOS
                            $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Creación", "Cambio estado", $idCambioEstado, "Cambio estado por Demanda:" . $rowDemanda->ID_DEMANDA);
                        endforeach;

                        //BUSCO LA DEMANDA A LA QUE LE ROBAN
                        $rowDemandaRobada = $bd->VerReg("DEMANDA", "ID_DEMANDA", $rowReservasARobar->ID_DEMANDA);

                        //SI LA DEMANDA ES DE PEDIDO
                        if ($rowDemandaRobada->ID_PEDIDO_SALIDA_LINEA != NULL):
                            $sqlIdOrdenTrabajoLinea    = "SELECT PSL.ID_ORDEN_TRABAJO_LINEA 
                                                                FROM PEDIDO_SALIDA_LINEA PSL
                                                                WHERE PSL.ID_PEDIDO_SALIDA_LINEA = $rowDemandaRobada->ID_PEDIDO_SALIDA_LINEA AND PSL.BAJA = 0
                                                            UNION 
                                                            SELECT OTLCP.ID_ORDEN_TRABAJO_LINEA 
                                                                FROM ORDEN_TRABAJO_LINEA_CUBIERTA_PEDIDO OTLCP 
                                                                WHERE OTLCP.ID_PEDIDO_SALIDA_LINEA = $rowDemandaRobada->ID_PEDIDO_SALIDA_LINEA AND OTLCP.BAJA = 0";
                            $resultIdOrdenTrabajoLinea = $bd->ExecSQL($sqlIdOrdenTrabajoLinea);
                            while ($rowIdOrdenTrabajoLinea = $bd->SigReg($resultIdOrdenTrabajoLinea)):
                                $arr_respuesta['lanzar_T06'][] = $rowIdOrdenTrabajoLinea->ID_ORDEN_TRABAJO_LINEA;
                            endwhile;
                        endif;
                        //FIN SI LA DEMANDA ES DE PEDIDO

                        //SI LA DEMANDA ES DE PEDIDO
                        if ($rowDemandaRobada->ID_PEDIDO_SALIDA_LINEA != NULL):
                            $sqlIdOrdenTrabajoLinea    = " SELECT PSL.ID_ORDEN_TRABAJO_LINEA 
                                                       FROM PEDIDO_SALIDA_LINEA PSL
                                                       WHERE PSL.ID_PEDIDO_SALIDA_LINEA = $rowDemandaRobada->ID_PEDIDO_SALIDA_LINEA AND PSL.BAJA = 0
                                                       UNION 
                                                       SELECT OTLCP.ID_ORDEN_TRABAJO_LINEA 
                                                       FROM ORDEN_TRABAJO_LINEA_CUBIERTA_PEDIDO OTLCP 
                                                       WHERE OTLCP.ID_PEDIDO_SALIDA_LINEA = $rowDemandaRobada->ID_PEDIDO_SALIDA_LINEA AND OTLCP.BAJA = 0";
                            $resultIdOrdenTrabajoLinea = $bd->ExecSQL($sqlIdOrdenTrabajoLinea);
                            while ($rowIdOrdenTrabajoLinea = $bd->SigReg($resultIdOrdenTrabajoLinea)):
                                $arr_respuesta['lanzar_T06'][] = $rowIdOrdenTrabajoLinea->ID_ORDEN_TRABAJO_LINEA;
                            endwhile;
                        endif;
                        //FIN SI LA DEMANDA ES DE PEDIDO

                    endif;//FIN HACEMOS EL CAMBIO DE ESTADO DEL MATERIAL ROBADO EN OTRA RED
                endif;//FIN ROBAR MISMA RED/ DIFERENTE

            endwhile;//FIN BUSCAMOS RESERVAS DE DEMANDAS CON MENOS PRIORIDAD, O A MISMA PRIORIDAD CON FECHA CREACION MAS NUEVA

        endif;


        //SI LA COLA ESTA PROGRAMADA, ACTUALIZAMOS LA COLA A PENDIENTE
        $rowCola = $bd->VerReg("COLA_RESERVA", "ID_COLA_RESERVA", $idColaReserva);
        if ($rowCola->ESTADO == 'Programada'):
            $sqlUpdate = "UPDATE COLA_RESERVA SET ESTADO = 'Pendiente' WHERE ID_COLA_RESERVA = " . $rowCola->ID_COLA_RESERVA;
            $bd->ExecSQL($sqlUpdate);

            //LOG
            $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Cola Reserva", $rowCola->ID_COLA_RESERVA, 'Actualizar estado a Pendiente');

        elseif ($rowCola->ESTADO == 'Cubierta')://SI QUEDA CUBIERTA, ACTUALIZAMOS LAS INCIDENCIA SISTEMA

            //BUSCAMOS SI EXISTE INCIDENCIA SISTEMA DE CAMBIO ESTADO PARA LA LINEA
            $rowIncidenciaSistema = $incidencia_sistema->getIncidenciaSistema("Error Transmitir Interfaz", "Cambio Estado Previo Reserva", 'COLA_RESERVA', $rowCola->ID_COLA_RESERVA);
            if (($rowIncidenciaSistema != false) && ($rowIncidenciaSistema->ID_INCIDENCIA_SISTEMA != NULL)):
                //ACTUALIZO INCIDENCIA
                $incidencia_sistema->actualizarIncidencia($rowIncidenciaSistema, 'Automatica');
            endif;
            //BUSCAMOS SI EXISTE INCIDENCIA SISTEMA DE CONTROL DE COLA PARA LA LINEA
            $rowIncidenciaSistema = $incidencia_sistema->getIncidenciaSistema("Reservas", "Control de Cola", 'COLA_RESERVA', $rowCola->ID_COLA_RESERVA);
            if (($rowIncidenciaSistema != false) && ($rowIncidenciaSistema->ID_INCIDENCIA_SISTEMA != NULL)):
                //ACTUALIZO INCIDENCIA
                $incidencia_sistema->actualizarIncidencia($rowIncidenciaSistema, 'Automatica');
            endif;
        endif;

        //SI TUVIMOS CAMBIO DE RED, LO EJECUTAREMOS
        if ($idCambioEstadoGrupo != NULL):
            $arr_respuesta['idCambioEstadoGrupo'] = $idCambioEstadoGrupo;
            $arr_respuesta['tipoCambioRed']       = $tipoCambioRed;
        endif;
        $arr_respuesta['cantidadPdteReservar'] = $cantidadPdteReservar;

        //SI LA DEMANDA ES DE TIPO OT, ACTUALIZAMOS LAS TRANSFERENCIAS DE PENDIENTES SI LAS HAY
        if ($rowDemanda->TIPO_DEMANDA == 'OT'):
            $this->actualizar_transferencias_pendientes($rowDemanda->ID_DEMANDA);
        endif;

        return $arr_respuesta;
    }

    /**
     * @string $idDemanda
     * @string $cantidad
     */
    function quitar_cantidad_cola($idDemanda, $cantidad, $observacionesLog, $idPedidoSalidaLinea = NULL, $arrCambioEstadoQuitarColaReserva = array())
    {
        global $bd;
        global $html;
        global $auxiliar;
        global $administrador;

        //BUSCAMOS SI HAY COLA PARA LA DEMANDA QUE ROBA
        $rowColaProgramada = $this->get_cola_reserva($idDemanda);
        if ($rowColaProgramada != false):

            //COMPROBAMOS SI HAY QUE FINALIZAR
            if (($rowColaProgramada->CANTIDAD_EN_COLA - $cantidad) < EPSILON_SISTEMA):
                // DAMOS DE BAJA LAS LÍNEAS DE LA COLA SI EL ESTADO INCIAL ES PENDIENTE O PROGRAMADA
                if ($rowColaProgramada->ESTADO == 'Pendiente' || $rowColaProgramada->ESTADO == 'Programada'):
                    $sqlUpdateLineas = "UPDATE COLA_RESERVA_LINEA SET BAJA = 1 WHERE ID_COLA_RESERVA = $rowColaProgramada->ID_COLA_RESERVA";
                    $bd->ExecSQL($sqlUpdateLineas);
                endif;

                //ACTUALIZAMOS LA COLA PROGRAMADA
                $sqlUpdate = "UPDATE COLA_RESERVA SET 
                                CANTIDAD_EN_COLA = 0
                                , ESTADO = 'Cubierta'
                                , FECHA_FINALIZACION = '" . date("Y-m-d H:i:s") . "' 
                                WHERE ID_COLA_RESERVA = $rowColaProgramada->ID_COLA_RESERVA";
                $bd->ExecSQL($sqlUpdate);
            else:
                //ACTUALIZAMOS LA COLA PROGRAMADA
                $sqlUpdate = "UPDATE COLA_RESERVA SET 
                                CANTIDAD_EN_COLA = CANTIDAD_EN_COLA - $cantidad
                                WHERE ID_COLA_RESERVA = $rowColaProgramada->ID_COLA_RESERVA";
                $bd->ExecSQL($sqlUpdate);

                //SI VIENE LINEA DE PEDIDO DE SALIDA ACTUALIZO LA LINEA DE RESERVA CORRESPONDIENTE
                if ($idPedidoSalidaLinea != NULL):
                    //ACTUALIZAMOS LA LINEA COLA PROGRAMADA
                    $sqlUpdate = "UPDATE COLA_RESERVA_LINEA SET 
                                    CANTIDAD = CANTIDAD - $cantidad 
                                    WHERE ID_COLA_RESERVA = $rowColaProgramada->ID_COLA_RESERVA AND ID_PEDIDO_SALIDA_LINEA = $idPedidoSalidaLinea AND BAJA = 0";
                    $bd->ExecSQL($sqlUpdate);
                endif;

                //SI EXISTE EL ARRAY DE CAMBIOS DE ESTADO Y NO ES VACIO
                if ((isset($arrCambioEstadoQuitarColaReserva)) &&(count( (array)$arrCambioEstadoQuitarColaReserva) > 0)):
                    //RECORRO LOS CAMBIOS DE ESTADO PARA QUITARLOS DE LA COLA DE RESERVAS LINEA
                    foreach ($arrCambioEstadoQuitarColaReserva as $idCambioEstado):
                        $sqlUpdateLineas = "UPDATE COLA_RESERVA_LINEA SET 
                                            BAJA = 1 
                                            WHERE ID_COLA_RESERVA = $rowColaProgramada->ID_COLA_RESERVA AND ID_CAMBIO_ESTADO = $idCambioEstado AND BAJA = 0";
                        $bd->ExecSQL($sqlUpdateLineas);
                    endforeach;
                    //FIN RECORRO LOS CAMBIOS DE ESTADO PARA QUITARLOS DE LA COLA DE RESERVAS LINEA
                endif;
            endif;

            // LOG MOVIMIENTOS
            $rowColaProgramadaActualizada = $bd->VerReg("COLA_RESERVA", "ID_COLA_RESERVA", $rowColaProgramada->ID_COLA_RESERVA);
            $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Cola Reserva", $rowColaProgramada->ID_COLA_RESERVA, $observacionesLog, 'COLA_RESERVA', $rowColaProgramada, $rowColaProgramadaActualizada);
        endif;

        return $rowColaProgramada->ID_COLA_RESERVA;
    }

    /**
     * @string $idDemanda
     * @string $cantidad
     */
    function poner_cantidad_cola($idDemanda, $cantidad, $observacionesLog = "")
    {
        global $bd;
        global $html;
        global $auxiliar;
        global $administrador;

        //BUSCAMOS SI HAY COLA PARA LA DEMANDA QUE ROBA
        $rowColaProgramada = $this->get_cola_reserva($idDemanda);

        //BUSCAMOS LA DEMANDA
        $rowDemanda = $bd->VerReg("DEMANDA", "ID_DEMANDA", $idDemanda);

        //ESTABLEZCO EL ESTADO DE COLA RESERVA
        $estadoColaReserva = 'Programada';
        if ($rowDemanda->TIPO_DEMANDA == 'OT'):
            //BUSCO LA LINEA DE ORDEN DE TRABAJO
            $rowOrdenTrabajoLinea = $bd->VerReg("ORDEN_TRABAJO_LINEA", "ID_ORDEN_TRABAJO_LINEA", $rowDemanda->ID_ORDEN_TRABAJO_LINEA);

            //SI LA LINEA DE LA ORDEN DE TRABAJO TIENE PEDIDO ASIGNADO EL ESTADO SERA 'Esperando Pedido Traslado'
            if ($rowOrdenTrabajoLinea->ID_PEDIDO_SALIDA != NULL):
                $estadoColaReserva = 'Esperando Pedido Traslado';
            endif;
        endif;

        if ($rowColaProgramada != false):

            //SI ESTABA CUBIERTA, LA CANTIDAD EN COLA DEBERIA SER 0 , PERO POR SI ACASO
            $cantidadCola = ($rowColaProgramada->ESTADO == 'Cubierta' ? $cantidad : $rowColaProgramada->CANTIDAD_EN_COLA + $cantidad);

            //ACTUALIZAMOS LA COLA PROGRAMADA
            $sqlUpdate = "UPDATE COLA_RESERVA SET 
                              CANTIDAD_EN_COLA = $cantidadCola
                            , ESTADO = '" . $estadoColaReserva . "'
                            , FECHA_FINALIZACION = NULL
                            WHERE ID_COLA_RESERVA = $rowColaProgramada->ID_COLA_RESERVA";
            $bd->ExecSQL($sqlUpdate);
            $idColaReserva = $rowColaProgramada->ID_COLA_RESERVA;

            // LOG MOVIMIENTOS
            $rowColaProgramadaActualizada = $bd->VerReg("COLA_RESERVA", "ID_COLA_RESERVA", $rowColaProgramada->ID_COLA_RESERVA);
            $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Cola Reserva", $rowColaProgramada->ID_COLA_RESERVA, $observacionesLog, 'COLA_RESERVA', $rowColaProgramada, $rowColaProgramadaActualizada);

        else:

            //SI EXISTE PERO DADA DE BAJA, LA REACTIVAMOS
            $rowColaProgramada = $this->get_cola_reserva($idDemanda, "", "Si");
            if ($rowColaProgramada != false):
                //OBTENEMOS LA DEMANDA
                $rowDemanda = $bd->VerReg("DEMANDA", "ID_DEMANDA", $idDemanda);

                //ACTUALIZAMOS LA COLA PROGRAMADA
                $sqlUpdate = "UPDATE COLA_RESERVA SET 
                              CANTIDAD_EN_COLA = $cantidad
                            , ID_MATERIAL  = $rowDemanda->ID_MATERIAL
                            , ESTADO = '" . $estadoColaReserva . "'
                            , FECHA_FINALIZACION = NULL
                            , BAJA = 0
                            WHERE ID_COLA_RESERVA = $rowColaProgramada->ID_COLA_RESERVA";
                $bd->ExecSQL($sqlUpdate);
                $idColaReserva = $rowColaProgramada->ID_COLA_RESERVA;

                // LOG MOVIMIENTOS
                $rowColaProgramadaActualizada = $bd->VerReg("COLA_RESERVA", "ID_COLA_RESERVA", $rowColaProgramada->ID_COLA_RESERVA);
                $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Cola Reserva", $rowColaProgramada->ID_COLA_RESERVA, $observacionesLog, 'COLA_RESERVA', $rowColaProgramada, $rowColaProgramadaActualizada);

            else://LA CREAMOS

                //OBTENEMOS LA DEMANDA
                $rowDemanda = $bd->VerReg("DEMANDA", "ID_DEMANDA", $idDemanda);

                //OBTENEMOS EL ALMACEN RESERVA
                $idAlmacenReserva = $this->get_almacen_reserva($rowDemanda->ID_DEMANDA);

                //GENERAMOS LA COLA PROGRAMADA
                $sqlInsert = "INSERT INTO COLA_RESERVA SET 
                                 ID_DEMANDA = '" . $idDemanda . "'
                                , ESTADO = '" . $estadoColaReserva . "'
                                , FECHA_CREACION = '" . date("Y-m-d H:i:s") . "'
                                , ID_MATERIAL  =$rowDemanda->ID_MATERIAL
                                , CANTIDAD_EN_COLA = $cantidad
                                , ID_ALMACEN_RESERVA = " . $idAlmacenReserva;
                $bd->ExecSQL($sqlInsert);
                $idColaReserva = $bd->IdAsignado();

                // LOG MOVIMIENTOS
                $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Creación", "Cola Reserva", $idColaReserva, $observacionesLog);
            endif;
        endif;

        return $idColaReserva;
    }

    /**
     * @string $idDemanda
     * @string $observacionesLog
     */
    function cancelar_cola($idDemanda, $observacionesLog = "")
    {
        global $bd;
        global $administrador;

        //BUSCAMOS SI HAY COLA PROGRAMADA
        $rowColaProgramada = $this->get_cola_reserva($idDemanda);
        if ($rowColaProgramada != false):

            //COMPROBAMOS SI HAY QUE CANCELAR
            if (($rowColaProgramada->CANTIDAD_EN_COLA != 0) && ($rowColaProgramada->ESTADO != "Cancelada")):
                //ACTUALIZAMOS LA COLA PROGRAMADA
                $sqlUpdate = "UPDATE COLA_RESERVA SET ESTADO = 'Cancelada' WHERE ID_COLA_RESERVA = $rowColaProgramada->ID_COLA_RESERVA";
                $bd->ExecSQL($sqlUpdate);

                //LOG MOVIMIENTOS
                $rowColaProgramadaActualizada = $bd->VerReg("COLA_RESERVA", "ID_COLA_RESERVA", $rowColaProgramada->ID_COLA_RESERVA);
                $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Cola Reserva", $rowColaProgramada->ID_COLA_RESERVA, $observacionesLog, 'COLA_RESERVA', $rowColaProgramada, $rowColaProgramadaActualizada);
            endif;
        endif;

        return $rowColaProgramada->ID_COLA_RESERVA;
    }

    /**
     * @string $idDemanda
     * @string $observacionesLog
     */
    function deshacer_cancelar_cola($idDemanda, $observacionesLog = "")
    {
        global $bd;
        global $administrador;

        //BUSCAMOS SI HAY COLA PARA LA DEMANDA QUE ROBA
        $rowColaProgramada = $this->get_cola_reserva($idDemanda, "Cancelada");
        if ($rowColaProgramada != false):

            //BUSCAMOS LA DEMANDA
            $rowDemanda = $bd->VerReg("DEMANDA", "ID_DEMANDA", $idDemanda);

            //ESTABLEZCO EL ESTADO DE COLA RESERVA
            $estadoColaReserva = 'Programada';
            if ($rowDemanda->TIPO_DEMANDA == 'OT'):
                //BUSCO LA LINEA DE ORDEN DE TRABAJO
                $rowOrdenTrabajoLinea = $bd->VerReg("ORDEN_TRABAJO_LINEA", "ID_ORDEN_TRABAJO_LINEA", $rowDemanda->ID_ORDEN_TRABAJO_LINEA);

                //SI LA LINEA DE LA ORDEN DE TRABAJO TIENE PEDIDO ASIGNADO EL ESTADO SERA 'Esperando Pedido Traslado'
                if ($rowOrdenTrabajoLinea->ID_PEDIDO_SALIDA != NULL):
                    $estadoColaReserva = 'Esperando Pedido Traslado';
                endif;
            endif;

            //ACTUALIZAMOS LA COLA PROGRAMADA
            $sqlUpdate = "UPDATE COLA_RESERVA SET ESTADO = '" . $estadoColaReserva . "' WHERE ID_COLA_RESERVA = $rowColaProgramada->ID_COLA_RESERVA";
            $bd->ExecSQL($sqlUpdate);

            // LOG MOVIMIENTOS
            $rowColaProgramadaActualizada = $bd->VerReg("COLA_RESERVA", "ID_COLA_RESERVA", $rowColaProgramada->ID_COLA_RESERVA);
            $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Cola Reserva", $rowColaProgramada->ID_COLA_RESERVA, $observacionesLog, 'COLA_RESERVA', $rowColaProgramada, $rowColaProgramadaActualizada);
        endif;

        return $rowColaProgramada->ID_COLA_RESERVA;
    }


    /**
     * @param $idPedidoSalidaLinea
     * @param $arr_datos Array de datos [indice][camposdeMaterialUbicacion y Cantidad]
     * Convierte  cola de reserva en reserva con los datos de $arr_datos. Se usa cuando se lanza una preparacion antes de hacerse la reserva
     */
    function asociar_reserva_pedido($idPedidoSalidaLinea, $arr_datos)
    {
        global $bd;
        global $html;
        global $auxiliar;
        global $administrador;

        //BUSCO LA COLA DE RESERVAS
        $resultCola = $this->get_colas_reserva_pedido_linea($idPedidoSalidaLinea, "No Cubierta");
        if (($resultCola != false) && ($bd->NumRegs($resultCola) > 0)):

            //BUSCO EL TIPO DE BLOQUEO PREVENTIVO, MATERIAL USADO PARA PREPARAR ESTE TIPO DE PEDIDOS DE TRASLADO DE PENDIENTES
            $rowTipoBloqueoPlanificado = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "SP");

            //BLOQUEOS RESERVADO
            $rowTipoBloqueoReservado            = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "RV");
            $rowTipoBloqueoReservadoPlanificado = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "RVP");

            //OBTENEMOS LA PRIMERA COLA
            $rowColaActual      = $bd->SigReg($resultCola);
            $cantidadColaActual = $rowColaActual->CANTIDAD_EN_COLA;
            $idReservaCola      = NULL;

            //RECORREMOS LOS MATERIALES A RESERVAR
            foreach ($arr_datos as $indice => $arrayValores):

                if ($arrayValores["CANTIDAD"] > EPSILON_SISTEMA):
                    $cantidadAsignar = $arrayValores["CANTIDAD"];

                    //ASIGNAMOS LA CANTIDAD A LA COLA/S
                    while (($rowColaActual != false) && ($cantidadAsignar > EPSILON_SISTEMA)):

                        //SI NO HEMOS CREADO AUN LA RESERVA PARA LA COLA
                        if ($idReservaCola == NULL):
                            $idReservaCola = $this->obtener_o_crear_reserva($rowColaActual->ID_DEMANDA);
                        endif;

                        //SI EN LA COLA HAY MAS CANTIDAD DE LA QUE NECESITAMOS
                        if ($cantidadColaActual > $cantidadAsignar):
                            //GENERAMOS LA RESERVA LINEA A PARTIR DE ESTA RESERVA LINEA
                            $sqlInsert = "INSERT INTO RESERVA_LINEA SET 
                                          ID_RESERVA = " . $idReservaCola . "
                                        , FECHA_CREACION_LINEA = '" . date("Y-m-d H:i:s") . "'
                                        , CANTIDAD =  " . $cantidadAsignar . "
                                        , ESTADO_LINEA = 'Finalizada'
                                        , ID_MATERIAL = " . $arrayValores['ID_MATERIAL'] . "
                                        , ID_TIPO_BLOQUEO= " . ($arrayValores['ID_TIPO_BLOQUEO'] == $rowTipoBloqueoPlanificado->ID_TIPO_BLOQUEO ? $rowTipoBloqueoReservadoPlanificado->ID_TIPO_BLOQUEO : $rowTipoBloqueoReservado->ID_TIPO_BLOQUEO) . "
                                        , ID_MATERIAL_FISICO = " . ($arrayValores['ID_MATERIAL_FISICO'] != NULL ? $arrayValores['ID_MATERIAL_FISICO'] : "NULL") . "
                                        , ID_INCIDENCIA_CALIDAD = " . ($arrayValores['ID_INCIDENCIA_CALIDAD'] != NULL ? $arrayValores['ID_INCIDENCIA_CALIDAD'] : "NULL") . "
                                        , ID_UBICACION= " . ($arrayValores['ID_UBICACION'] != NULL ? $arrayValores['ID_UBICACION'] : "NULL") . "
                                        , ID_ORDEN_TRABAJO_MOVIMIENTO = " . ($arrayValores['ID_ORDEN_TRABAJO_MOVIMIENTO'] != NULL ? $arrayValores['ID_ORDEN_TRABAJO_MOVIMIENTO'] : "NULL");
                            $bd->ExecSQL($sqlInsert);

                            $cantidadColaActual = $cantidadColaActual - $cantidadAsignar;
                            $cantidadAsignar    = 0;

                        else://SI NO, ASIGNAMOS LO QUE SE PUEDA Y BUSCAMOS OTRA COLA DE ESE PEDIDO (PUEDE ESTAR EN OTRA DEMANDA DE OTRA NECESIDAD)

                            //GENERAMOS LA RESERVA LINEA A PARTIR DE ESTA RESERVA LINEA
                            $sqlInsert = "INSERT INTO RESERVA_LINEA SET 
                                          ID_RESERVA = " . $idReservaCola . "
                                        , FECHA_CREACION_LINEA = '" . date("Y-m-d H:i:s") . "'
                                        , CANTIDAD =  " . $cantidadColaActual . "
                                        , ESTADO_LINEA = 'Finalizada'
                                        , ID_MATERIAL = " . $arrayValores['ID_MATERIAL'] . "
                                        , ID_TIPO_BLOQUEO= " . ($arrayValores['ID_TIPO_BLOQUEO'] == $rowTipoBloqueoPlanificado->ID_TIPO_BLOQUEO ? $rowTipoBloqueoReservadoPlanificado->ID_TIPO_BLOQUEO : $rowTipoBloqueoReservado->ID_TIPO_BLOQUEO) . "
                                        , ID_MATERIAL_FISICO = " . ($arrayValores['ID_MATERIAL_FISICO'] != NULL ? $arrayValores['ID_MATERIAL_FISICO'] : "NULL") . "
                                        , ID_INCIDENCIA_CALIDAD = " . ($arrayValores['ID_INCIDENCIA_CALIDAD'] != NULL ? $arrayValores['ID_INCIDENCIA_CALIDAD'] : "NULL") . "
                                        , ID_UBICACION= " . ($arrayValores['ID_UBICACION'] != NULL ? $arrayValores['ID_UBICACION'] : "NULL") . "
                                        , ID_ORDEN_TRABAJO_MOVIMIENTO = " . ($arrayValores['ID_ORDEN_TRABAJO_MOVIMIENTO'] != NULL ? $arrayValores['ID_ORDEN_TRABAJO_MOVIMIENTO'] : "NULL");
                            $bd->ExecSQL($sqlInsert);

                            $cantidadAsignar    = $cantidadAsignar - $cantidadColaActual;
                            $cantidadColaActual = 0;
                        endif;

                        //SI LA CANTIDAD EN LA COLA ACTUAL ES 0, BUSCAMOS LA SIGUIENTE COLA
                        if ($cantidadColaActual < EPSILON_SISTEMA):
                            //QUITAMOS LA CANTIDAD DE LA COLA
                            $this->quitar_cantidad_cola($rowColaActual->ID_DEMANDA, $rowColaActual->CANTIDAD_EN_COLA, 'Reserva Cantidad: ' . $rowColaActual->CANTIDAD_EN_COLA . ' en la Reserva ' . $idReservaCola);

                            //ACTUALIZAMOS LA DEMANDA
                            $sqlUpdate = "UPDATE DEMANDA SET 
                                           CANTIDAD_PENDIENTE_RESERVAR = CANTIDAD_PENDIENTE_RESERVAR - $rowColaActual->CANTIDAD_EN_COLA
                                            WHERE ID_DEMANDA = $rowColaActual->ID_DEMANDA";
                            $bd->ExecSQL($sqlUpdate);

                            //LOG MOVIMIENTOS
                            $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Demanda", $rowColaActual->ID_DEMANDA, 'Reserva del Material:' . $idReservaCola);

                            //ACTUALIZAMOS ESTAMOS SI ES NECESARIO
                            $this->actualizar_estado_demanda($rowColaActual->ID_DEMANDA, "Cola a Reserva de Pedido");

                            //BUSCAMOS LA SIGUIENTE COLA
                            $idReservaCola = NULL;
                            $rowColaActual = $bd->SigReg($resultCola);
                            if ($rowColaActual != false):
                                $cantidadColaActual = $rowColaActual->CANTIDAD_EN_COLA;
                            endif;
                        endif;
                    endwhile;
                endif;
            endforeach;//FIN RECORRER DATOS A CONVERTIR EN RESERVAS

            //SI QUEDA CANTIDAD EN LA COLA, Y LA CANTIDAD ES DISTINTA DE LA DE LA COLA, SE LA QUITAMOS
            if (($cantidadColaActual > EPSILON_SISTEMA) && ($rowColaActual->CANTIDAD_EN_COLA > $cantidadColaActual)):
                //QUITAMOS LA CANTIDAD DE LA COLA
                $cantidadQuitar = $rowColaActual->CANTIDAD_EN_COLA - $cantidadColaActual;
                $this->quitar_cantidad_cola($rowColaActual->ID_DEMANDA, $cantidadQuitar, 'Reserva Cantidad: ' . $cantidadQuitar . ' en la Reserva ' . $idReservaCola);

                //ACTUALIZAMOS LA DEMANDA
                $sqlUpdate = "UPDATE DEMANDA SET 
                                    CANTIDAD_PENDIENTE_RESERVAR = CANTIDAD_PENDIENTE_RESERVAR - $cantidadQuitar
                                    WHERE ID_DEMANDA = $rowColaActual->ID_DEMANDA";
                $bd->ExecSQL($sqlUpdate);

                //LOG MOVIMIENTOS
                $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Demanda", $rowColaActual->ID_DEMANDA, 'Reserva de Material:' . $idReservaCola);

                //ACTUALIZAMOS ESTAMOS SI ES NECESARIO
                $this->actualizar_estado_demanda($rowColaActual->ID_DEMANDA, "Cola a Reserva de Pedido");
            endif;
        endif;//FIN EXISTEN COLA DE RESERVAS
    }

    /**
     * @param $idOrdenTrabajoLinea
     * @param $arr_datos Array de datos [indice][camposdeMaterialUbicacion y Cantidad]
     * Convierte cola de reserva en reserva con los datos de $arr_datos. Se usa cuando se recepciona un pedido de pendientes o planificados
     */
    function asociar_reserva_OT_Pedido($idOrdenTrabajoLinea, $arr_datos)
    {
        global $bd;
        global $html;
        global $auxiliar;
        global $administrador;

        //ARRAY RESULTADO
        $arr_respuesta = array();

        //BUSCO LA COLA DE RESERVAS
        $resultCola = $this->get_colas_reserva_OT_linea($idOrdenTrabajoLinea, "Esperando Pedido Traslado");
        if (($resultCola != false) && ($bd->NumRegs($resultCola) > 0)):

            //BUSCO EL TIPO DE BLOQUEO PREVENTIVO, MATERIAL USADO PARA PREPARAR ESTE TIPO DE PEDIDOS DE TRASLADO DE PENDIENTES
            $rowTipoBloqueoPlanificado = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "SP");

            //BLOQUEOS RESERVADO
            $rowTipoBloqueoReservado            = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "RV");
            $rowTipoBloqueoReservadoPlanificado = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "RVP");

            //OBTENEMOS LA PRIMERA COLA
            $rowColaActual      = $bd->SigReg($resultCola);
            $cantidadColaActual = $rowColaActual->CANTIDAD_EN_COLA;
            $idReservaCola      = NULL;

            //BUSCO LA LINEA DE ORDEN DE TRABAJO
            $rowLineaOrdenTrabajo = $bd->VerReg("ORDEN_TRABAJO_LINEA", "ID_ORDEN_TRABAJO_LINEA", $idOrdenTrabajoLinea);

            //BUSCO LA ORDEN DE TRABAJO
            $rowOrdenTrabajo = $bd->VerReg("ORDEN_TRABAJO", "ID_ORDEN_TRABAJO", $rowLineaOrdenTrabajo->ID_ORDEN_TRABAJO);

            //BUSCAMOS CANTIDAD CONSUMIDA
            $sqlLineasOTConsumidas    = "SELECT *
                                          FROM ORDEN_TRABAJO_MOVIMIENTO
                                          WHERE ID_ORDEN_TRABAJO_LINEA_RELACIONADO = $rowLineaOrdenTrabajo->ID_ORDEN_TRABAJO_LINEA";
            $resultLineasOTConsumidas = $bd->ExecSQL($sqlLineasOTConsumidas);
            $cantidad_consumida       = 0;
            while ($rowLineaConsumida = $bd->SigReg($resultLineasOTConsumidas)):
                if (
                    ($rowLineaConsumida->TIPO_OPERACION == 'Consumo Lista') ||
                    (($rowLineaConsumida->TIPO_OPERACION == 'Consumo') && ($rowLineaConsumida->CONSUMIDO_DESDE_LISTA == 1) && ($rowLineaConsumida->TIPO_ACCION == 'Salida')) ||
                    (($rowLineaConsumida->TIPO_OPERACION == 'No Conforme') && ($rowLineaConsumida->CONSUMIDO_DESDE_LISTA == 1) && ($rowLineaConsumida->TIPO_ACCION == 'Salida')) ||
                    (($rowLineaConsumida->TIPO_OPERACION == 'Intercambio') && ($rowLineaConsumida->CONSUMIDO_DESDE_LISTA == 1) && ($rowLineaConsumida->TIPO_ACCION == 'Salida')) ||
                    (($rowLineaConsumida->TIPO_OPERACION == 'Alta') && ($rowLineaConsumida->CONSUMIDO_DESDE_LISTA == 1) && ($rowLineaConsumida->TIPO_ACCION == 'Salida'))
                ):
                    $cantidad_consumida = $cantidad_consumida + $rowLineaConsumida->CANTIDAD;
                elseif (
                (($rowLineaConsumida->TIPO_OPERACION == 'Anulación') && ($rowLineaConsumida->CONSUMIDO_DESDE_LISTA == 1) && ($rowLineaConsumida->TIPO_ACCION == 'Entrada'))
                ):
                    $cantidad_consumida = $cantidad_consumida - $rowLineaConsumida->CANTIDAD;
                endif;
            endwhile;

            //ACCIONES EN FUNCION DE SI LA LINEA DE LA OT ESTA ACTIVA O NO
            if (
                (($rowOrdenTrabajo->ESTADO == 'Creada') || ($rowOrdenTrabajo->ESTADO == 'Abierta')) &&
                ($cantidad_consumida < EPSILON_SISTEMA)
            )://SI LA LINEA DE LA OT ESTA ACTIVA REALIZO EL CAMBIO DE ESTADO DE P A RVP

                //RECORREMOS DATOS A CONVERTIR EN RESERVAS GENERANDO LAS LINEAS DE RESERVA
                foreach ($arr_datos as $indice => $arrayValores):

                    if ($arrayValores["CANTIDAD"] > EPSILON_SISTEMA):
                        $cantidadAsignar  = $arrayValores["CANTIDAD"];
                        $cantidadAsignada = 0;

                        //ASIGNAMOS LA CANTIDAD A LA COLA/S
                        while (($rowColaActual != false) && ($cantidadAsignar > EPSILON_SISTEMA)):

                            //SI NO HEMOS CREADO AUN LA RESERVA PARA LA COLA
                            if ($idReservaCola == NULL):
                                $idReservaCola = $this->obtener_o_crear_reserva($rowColaActual->ID_DEMANDA);
                            endif;

                            //SI EN LA COLA HAY MAS CANTIDAD DE LA QUE NECESITAMOS
                            if ($cantidadColaActual > $cantidadAsignar):
                                //GENERAMOS LA RESERVA LINEA A PARTIR DE ESTA RESERVA LINEA
                                $sqlInsert = "INSERT INTO RESERVA_LINEA SET 
                                              ID_RESERVA = " . $idReservaCola . "
                                            , FECHA_CREACION_LINEA = '" . date("Y-m-d H:i:s") . "'
                                            , CANTIDAD =  " . $cantidadAsignar . "
                                            , ESTADO_LINEA = 'Reservada'
                                            , ID_MATERIAL = " . $arrayValores['ID_MATERIAL'] . "
                                            , ID_UBICACION= " . ($arrayValores['ID_UBICACION'] != NULL ? $arrayValores['ID_UBICACION'] : "NULL") . "
                                            , ID_TIPO_BLOQUEO= " . ($arrayValores['ID_TIPO_BLOQUEO'] == $rowTipoBloqueoPlanificado->ID_TIPO_BLOQUEO ? $rowTipoBloqueoReservadoPlanificado->ID_TIPO_BLOQUEO : $rowTipoBloqueoReservado->ID_TIPO_BLOQUEO) . "
                                            , ID_MATERIAL_FISICO = " . ($arrayValores['ID_MATERIAL_FISICO'] != NULL ? $arrayValores['ID_MATERIAL_FISICO'] : "NULL") . "
                                            , ID_INCIDENCIA_CALIDAD = " . ($arrayValores['ID_INCIDENCIA_CALIDAD'] != NULL ? $arrayValores['ID_INCIDENCIA_CALIDAD'] : "NULL") . "
                                            , ID_ORDEN_TRABAJO_MOVIMIENTO = " . ($arrayValores['ID_ORDEN_TRABAJO_MOVIMIENTO'] != NULL ? $arrayValores['ID_ORDEN_TRABAJO_MOVIMIENTO'] : "NULL");
                                $bd->ExecSQL($sqlInsert);

                                $cantidadColaActual = $cantidadColaActual - $cantidadAsignar;
                                $cantidadAsignada   = $cantidadAsignada + $cantidadAsignar;
                                $cantidadAsignar    = 0;

                            else://SI NO, ASIGNAMOS LO QUE SE PUEDA Y BUSCAMOS OTRA COLA DE ESA OT

                                //GENERAMOS LA RESERVA LINEA A PARTIR DE ESTA RESERVA LINEA
                                $sqlInsert = "INSERT INTO RESERVA_LINEA SET 
                                              ID_RESERVA = " . $idReservaCola . "
                                            , FECHA_CREACION_LINEA = '" . date("Y-m-d H:i:s") . "'
                                            , CANTIDAD =  " . $cantidadColaActual . "
                                            , ESTADO_LINEA = 'Reservada'
                                            , ID_MATERIAL = " . $arrayValores['ID_MATERIAL'] . "
                                            , ID_UBICACION= " . ($arrayValores['ID_UBICACION'] != NULL ? $arrayValores['ID_UBICACION'] : "NULL") . "
                                            , ID_TIPO_BLOQUEO= " . ($arrayValores['ID_TIPO_BLOQUEO'] == $rowTipoBloqueoPlanificado->ID_TIPO_BLOQUEO ? $rowTipoBloqueoReservadoPlanificado->ID_TIPO_BLOQUEO : $rowTipoBloqueoReservado->ID_TIPO_BLOQUEO) . "
                                            , ID_MATERIAL_FISICO = " . ($arrayValores['ID_MATERIAL_FISICO'] != NULL ? $arrayValores['ID_MATERIAL_FISICO'] : "NULL") . "
                                            , ID_INCIDENCIA_CALIDAD = " . ($arrayValores['ID_INCIDENCIA_CALIDAD'] != NULL ? $arrayValores['ID_INCIDENCIA_CALIDAD'] : "NULL") . "
                                            , ID_ORDEN_TRABAJO_MOVIMIENTO = " . ($arrayValores['ID_ORDEN_TRABAJO_MOVIMIENTO'] != NULL ? $arrayValores['ID_ORDEN_TRABAJO_MOVIMIENTO'] : "NULL");
                                $bd->ExecSQL($sqlInsert);

                                $cantidadAsignar    = $cantidadAsignar - $cantidadColaActual;
                                $cantidadAsignada   = $cantidadAsignada + $cantidadColaActual;
                                $cantidadColaActual = 0;
                            endif;

                            //SI LA CANTIDAD EN LA COLA ACTUAL ES 0, BUSCAMOS LA SIGUIENTE COLA
                            if ($cantidadColaActual < EPSILON_SISTEMA):
                                //QUITAMOS LA CANTIDAD DE LA COLA
                                $this->quitar_cantidad_cola($rowColaActual->ID_DEMANDA, $rowColaActual->CANTIDAD_EN_COLA, 'Reserva Cantidad: ' . $rowColaActual->CANTIDAD_EN_COLA . ' en la Reserva ' . $idReservaCola);

                                //ACTUALIZAMOS LA DEMANDA
                                $sqlUpdate = "UPDATE DEMANDA SET 
                                              CANTIDAD_PENDIENTE_RESERVAR = CANTIDAD_PENDIENTE_RESERVAR - $rowColaActual->CANTIDAD_EN_COLA
                                              WHERE ID_DEMANDA = $rowColaActual->ID_DEMANDA";
                                $bd->ExecSQL($sqlUpdate);

                                //LOG MOVIMIENTOS
                                $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Demanda", $rowColaActual->ID_DEMANDA, 'Reserva del Material:' . $idReservaCola);

                                //ACTUALIZAMOS ESTAMOS SI ES NECESARIO
                                $this->actualizar_estado_demanda($rowColaActual->ID_DEMANDA, "Cola a Reserva de OT");

                                //BUSCAMOS LA SIGUIENTE COLA
                                $idReservaCola = NULL;
                                $rowColaActual = $bd->SigReg($resultCola);
                                if ($rowColaActual != false):
                                    $cantidadColaActual = $rowColaActual->CANTIDAD_EN_COLA;
                                endif;
                            endif;

                        endwhile;

                        //ACTUALIZAMOS EL ARRAY CON LA CANTIDAD REALMENTE ASIGNADA A RESERVAS
                        $arr_datos[$indice]['CANTIDAD_RESERVADA'] = $cantidadAsignada;

                    endif;
                endforeach;
                //FIN RECORREMOS DATOS A CONVERTIR EN RESERVAS GENERANDO LAS LINEAS DE RESERVA

                //SI QUEDA CANTIDAD EN LA COLA, Y LA CANTIDAD ES DISTINTA DE LA DE LA COLA, SE LA QUITAMOS
                if (($cantidadColaActual > EPSILON_SISTEMA) && ($rowColaActual->CANTIDAD_EN_COLA > $cantidadColaActual)):
                    //QUITAMOS LA CANTIDAD DE LA COLA
                    $cantidadQuitar = $rowColaActual->CANTIDAD_EN_COLA - $cantidadColaActual;
                    $this->quitar_cantidad_cola($rowColaActual->ID_DEMANDA, $cantidadQuitar, 'Reserva Cantidad: ' . $cantidadQuitar . ' en la Reserva ' . $idReservaCola);

                    //ACTUALIZAMOS LA DEMANDA
                    $sqlUpdate = "UPDATE DEMANDA SET 
                                  CANTIDAD_PENDIENTE_RESERVAR = CANTIDAD_PENDIENTE_RESERVAR - $cantidadQuitar
                                  WHERE ID_DEMANDA = $rowColaActual->ID_DEMANDA";
                    $bd->ExecSQL($sqlUpdate);

                    //LOG MOVIMIENTOS
                    $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Demanda", $rowColaActual->ID_DEMANDA, 'Reserva de Material:' . $idReservaCola);

                    //ACTUALIZAMOS ESTAMOS SI ES NECESARIO
                    $this->actualizar_estado_demanda($rowColaActual->ID_DEMANDA, "Cola a Reserva de OT");
                endif;

                //RECORREMOS LOS MATERIALES A RESERVAR GENERANDO LOS CAMBIOS DE ESTADO
                foreach ($arr_datos as $indice => $arrayValores):

                    //SI HAY CANTIDAD RESERVADA Y ES MAYOR QUE 0
                    if (isset($arrayValores["CANTIDAD_RESERVADA"]) && ($arrayValores["CANTIDAD_RESERVADA"] > EPSILON_SISTEMA)):
                        //CREO EL CAMBIO DE ESTADO
                        $sqlInsert = "INSERT INTO CAMBIO_ESTADO SET 
                                  FECHA = '" . date("Y-m-d H:i:s") . "' 
                                  , TIPO_CAMBIO_ESTADO = 'ReservaDemanda' 
                                  , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR 
                                  , ID_MATERIAL = " . $arrayValores['ID_MATERIAL'] . " 
                                  , ID_UBICACION= " . ($arrayValores['ID_UBICACION'] != NULL ? $arrayValores['ID_UBICACION'] : "NULL") . " 
                                  , CANTIDAD = " . $arrayValores["CANTIDAD_RESERVADA"] . " 
                                  , ID_TIPO_BLOQUEO_INICIAL = " . $arrayValores['ID_TIPO_BLOQUEO'] . " 
                                  , ID_TIPO_BLOQUEO_FINAL = " . ($arrayValores['ID_TIPO_BLOQUEO'] == $rowTipoBloqueoPlanificado->ID_TIPO_BLOQUEO ? $rowTipoBloqueoReservadoPlanificado->ID_TIPO_BLOQUEO : $rowTipoBloqueoReservado->ID_TIPO_BLOQUEO) . " 
                                  , ID_MATERIAL_FISICO = " . ($arrayValores['ID_MATERIAL_FISICO'] != NULL ? $arrayValores['ID_MATERIAL_FISICO'] : "NULL") . " 
                                  , ID_ORDEN_TRABAJO_MOVIMIENTO = " . ($arrayValores['ID_ORDEN_TRABAJO_MOVIMIENTO'] != NULL ? $arrayValores['ID_ORDEN_TRABAJO_MOVIMIENTO'] : "NULL") . " 
                                  , ID_INCIDENCIA_CALIDAD = " . ($arrayValores['ID_INCIDENCIA_CALIDAD'] != NULL ? $arrayValores['ID_INCIDENCIA_CALIDAD'] : "NULL");
                        $bd->ExecSQL($sqlInsert);

                        //BUSCO MATERIAL_UBICACION ORIGEN
                        $GLOBALS["NotificaErrorPorEmail"] = "No";
                        $clausulaWhere                    = "ID_MATERIAL = " . $arrayValores['ID_MATERIAL'] . " AND ID_UBICACION = " . $arrayValores['ID_UBICACION'] . " AND ID_MATERIAL_FISICO " . ($arrayValores['ID_MATERIAL_FISICO'] != NULL ? "= " . $arrayValores['ID_MATERIAL_FISICO'] : "IS NULL") . " AND ID_TIPO_BLOQUEO = " . $arrayValores['ID_TIPO_BLOQUEO'] . "  AND ID_ORDEN_TRABAJO_MOVIMIENTO " . ($arrayValores['ID_ORDEN_TRABAJO_MOVIMIENTO'] != NULL ? "= " . $arrayValores['ID_ORDEN_TRABAJO_MOVIMIENTO'] : "IS NULL") . " AND ID_INCIDENCIA_CALIDAD " . ($arrayValores['ID_INCIDENCIA_CALIDAD'] != NULL ? "= " . $arrayValores['ID_INCIDENCIA_CALIDAD'] : "IS NULL");
                        $rowMatUbiOrigen                  = $bd->VerRegRest("MATERIAL_UBICACION", $clausulaWhere, "No");
                        if ($rowMatUbiOrigen == false):
                            $rowMatInsuficiente     = $bd->VerReg("MATERIAL", "ID_MATERIAL", $arrayValores['ID_MATERIAL']);
                            $rowUbi                 = $bd->VerReg("UBICACION", "ID_UBICACION", $arrayValores['ID_UBICACION']);
                            $arr_respuesta['error'] = $auxiliar->traduce("Stock Insuficiente del material", $administrador->ID_IDIOMA) . " " . $rowMatInsuficiente->REFERENCIA_SGA . " - " . ($administrador->ID_IDIOMA == 'ENG' ? $rowMatInsuficiente->DESCRIPCION_EN : $rowMatInsuficiente->DESCRIPCION) . ". " . $auxiliar->traduce("Ubicacion", $administrador->ID_IDIOMA) . ": $rowUbi->UBICACION. " . $auxiliar->traduce("Es necesario disponer de", $administrador->ID_IDIOMA) . " " . ($arrayValores["CANTIDAD_RESERVADA"]);

                            return $arr_respuesta;
                        elseif (($arrayValores["CANTIDAD_RESERVADA"] - $rowMatUbiOrigen->STOCK_TOTAL) > EPSILON_SISTEMA):
                            $rowMatInsuficiente     = $bd->VerReg("MATERIAL", "ID_MATERIAL", $arrayValores['ID_MATERIAL']);
                            $rowUbi                 = $bd->VerReg("UBICACION", "ID_UBICACION", $arrayValores['ID_UBICACION']);
                            $arr_respuesta['error'] = $auxiliar->traduce("Stock Insuficiente del material", $administrador->ID_IDIOMA) . " " . $rowMatInsuficiente->REFERENCIA_SGA . " - " . ($administrador->ID_IDIOMA == 'ENG' ? $rowMatInsuficiente->DESCRIPCION_EN : $rowMatInsuficiente->DESCRIPCION) . ". " . $auxiliar->traduce("Ubicacion", $administrador->ID_IDIOMA) . ": $rowUbi->UBICACION. " . $auxiliar->traduce("Es necesario disponer de", $administrador->ID_IDIOMA) . " " . ($arrayValores["CANTIDAD_RESERVADA"]);

                            return $arr_respuesta;
                        endif;

                        //DECREMENTO MATERIAL_UBICACION ORIGEN
                        $sqlUpdate = "UPDATE MATERIAL_UBICACION SET 
                                  STOCK_TOTAL = STOCK_TOTAL - " . $arrayValores["CANTIDAD_RESERVADA"] . " 
                                  , STOCK_BLOQUEADO = STOCK_BLOQUEADO - " . $arrayValores["CANTIDAD_RESERVADA"] . " 
                                  WHERE ID_MATERIAL_UBICACION = " . $rowMatUbiOrigen->ID_MATERIAL_UBICACION;
                        $bd->ExecSQL($sqlUpdate);

                        //BUSCO MATERIAL_UBICACION DESTINO
                        $GLOBALS["NotificaErrorPorEmail"] = "No";
                        $clausulaWhere                    = "ID_MATERIAL = " . $arrayValores['ID_MATERIAL'] . " AND ID_UBICACION = " . $arrayValores['ID_UBICACION'] . " AND ID_TIPO_BLOQUEO = " . ($arrayValores['ID_TIPO_BLOQUEO'] == $rowTipoBloqueoPlanificado->ID_TIPO_BLOQUEO ? $rowTipoBloqueoReservadoPlanificado->ID_TIPO_BLOQUEO : $rowTipoBloqueoReservado->ID_TIPO_BLOQUEO) . " AND ID_MATERIAL_FISICO " . ($arrayValores['ID_MATERIAL_FISICO'] != NULL ? "= " . $arrayValores['ID_MATERIAL_FISICO'] : "IS NULL") . " AND ID_ORDEN_TRABAJO_MOVIMIENTO " . ($arrayValores['ID_ORDEN_TRABAJO_MOVIMIENTO'] != NULL ? "= " . $arrayValores['ID_ORDEN_TRABAJO_MOVIMIENTO'] : "IS NULL") . " AND ID_INCIDENCIA_CALIDAD " . ($arrayValores['ID_INCIDENCIA_CALIDAD'] != NULL ? "= " . $arrayValores['ID_INCIDENCIA_CALIDAD'] : "IS NULL");
                        $rowMatUbiDestino                 = $bd->VerRegRest("MATERIAL_UBICACION", $clausulaWhere, "No");
                        if ($rowMatUbiDestino == false):
                            //CREO MATERIAL UBICACION DESTINO
                            $sqlInsert = "INSERT INTO MATERIAL_UBICACION SET
                                          ID_MATERIAL = " . $arrayValores['ID_MATERIAL'] . " 
                                          , ID_UBICACION = " . ($arrayValores['ID_UBICACION'] != NULL ? $arrayValores['ID_UBICACION'] : "NULL") . " 
                                          , ID_MATERIAL_FISICO = " . ($arrayValores['ID_MATERIAL_FISICO'] != NULL ? $arrayValores['ID_MATERIAL_FISICO'] : "NULL") . " 
                                          , ID_TIPO_BLOQUEO = " . ($arrayValores['ID_TIPO_BLOQUEO'] == $rowTipoBloqueoPlanificado->ID_TIPO_BLOQUEO ? $rowTipoBloqueoReservadoPlanificado->ID_TIPO_BLOQUEO : $rowTipoBloqueoReservado->ID_TIPO_BLOQUEO) . "
                                          , ID_ORDEN_TRABAJO_MOVIMIENTO = " . ($arrayValores['ID_ORDEN_TRABAJO_MOVIMIENTO'] != NULL ? $arrayValores['ID_ORDEN_TRABAJO_MOVIMIENTO'] : "NULL") . " 
                                          , ID_INCIDENCIA_CALIDAD = " . ($arrayValores['ID_INCIDENCIA_CALIDAD'] != NULL ? $arrayValores['ID_INCIDENCIA_CALIDAD'] : "NULL");
                            $bd->ExecSQL($sqlInsert);

                            //GUARDO EL ID MATERIAL UBICACION DESTINO
                            $idMatUbiDestino = $bd->IdAsignado();
                        else:
                            //GUARDO EL ID MATERIAL UBICACION DESTINO
                            $idMatUbiDestino = $rowMatUbiDestino->ID_MATERIAL_UBICACION;
                        endif;

                        //INCREMENTO MATERIAL_UBICACION DESTINO
                        $sqlUpdate = "UPDATE MATERIAL_UBICACION SET
                                  STOCK_TOTAL = STOCK_TOTAL + " . $arrayValores["CANTIDAD_RESERVADA"] . " 
                                  , STOCK_BLOQUEADO = STOCK_BLOQUEADO + " . $arrayValores["CANTIDAD_RESERVADA"] . " 
                                  WHERE ID_MATERIAL_UBICACION = $idMatUbiDestino";
                        $bd->ExecSQL($sqlUpdate);

                    endif;//FIN SI HAY CANTIDAD RESERVADA
                endforeach;
            //FIN RECORREMOS LOS MATERIALES A RESERVAR GENERANDO LOS CAMBIOS DE ESTADO

            else: //SI LA LINEA NO ESTA ACTIVA, NO SE HACE RESERVA Y SE CANCELA LA DEMANDA, COLAS, RESERVAS

                //BUSCO LA DEMANDA
                $rowDemanda = $this->get_demanda("OT", $idOrdenTrabajoLinea);

                //SI EXISTE LA DEMANDA, LA ANULO
                if ($rowDemanda != false):
                    //LLAMO A LA FUNCION PARA CANCELAR LA DEMANDA Y LA COLA
                    $this->cancelar_cola($rowDemanda->ID_DEMANDA, "Recepcion pedido OT");
                endif;
            endif;
            //FIN ACCIONES EN FUNCION DE SI LA LINEA DE LA OT ESTA ACTIVA O NO

            //SI LA DEMANDA ES DE TIPO OT, ACTUALIZAMOS LAS TRANSFERENCIAS DE PENDIENTES SI LAS HAY
            $this->actualizar_transferencias_pendientes($rowDemanda->ID_DEMANDA);
        endif;
        //FIN EXISTEN COLA DE RESERVAS

        return $arr_respuesta;
    }

    /**
     * @param $idColaReserva Cola de Reservas a incluir sus lineas
     * @param $arr_datos Array de datos con el que satisfacer la cola de reservas
     */
    function asociar_cola_reserva_lineas($idColaReserva, $arr_datos)
    {
        global $bd;
        global $html;
        global $auxiliar;
        global $administrador;

        //BUSCO LA COLA DE RESERVAS
        $rowColaReserva = $bd->VerReg("COLA_RESERVA", "ID_COLA_RESERVA", $idColaReserva);

        //DOY DE BAJA TODAS LAS LINEAS RELACIONADAS CON ESTA COLA DE RESERVAS
        $sqlUpdate = "UPDATE COLA_RESERVA_LINEA 
                      SET BAJA = 1 
                      WHERE ID_COLA_RESERVA = $rowColaReserva->ID_COLA_RESERVA";
        $bd->ExecSQL($sqlUpdate);

        //TRATO EL ARRAY DE DATOS
        foreach ($arr_datos as $tipoObjeto => $arrCantidades):
            switch ($tipoObjeto):
                case 'CambioEstado':
                    foreach ($arrCantidades as $idCambioEstado => $cantidad):
                        //COMPRUEBO SI HAY UNA LINEA RELACIONADO CON LA COLA DE RESERVAS Y EL OBJETO QUE RESERVA
                        $num = $bd->NumRegsTabla("COLA_RESERVA_LINEA", "ID_COLA_RESERVA = $rowColaReserva->ID_COLA_RESERVA AND ID_CAMBIO_ESTADO = $idCambioEstado");

                        //ACCIONES EN FUNCION DE SI EXISTE UNA LINEA DE COLA DE RESERVA RELACIONADA O NO
                        if ($num == 0):
                            $sqlInsert = "INSERT INTO COLA_RESERVA_LINEA SET 
                                          ID_COLA_RESERVA = $rowColaReserva->ID_COLA_RESERVA 
                                          , CANTIDAD = $cantidad 
                                          , ID_CAMBIO_ESTADO = $idCambioEstado 
                                          , BAJA = 0";
                            $bd->ExecSQL($sqlInsert);
                        else:
                            $sqlUpdate = "UPDATE COLA_RESERVA_LINEA SET 
                                          CANTIDAD = $cantidad 
                                          , BAJA = 0 
                                          WHERE ID_COLA_RESERVA = $rowColaReserva->ID_COLA_RESERVA AND ID_CAMBIO_ESTADO = $idCambioEstado";
                            $bd->ExecSQL($sqlUpdate);
                        endif;
                        //FIN ACCIONES EN FUNCION DE SI EXISTE UNA LINEA DE COLA DE RESERVA RELACIONADA O NO
                    endforeach;

                    break;

                case 'PedidoSalidaLinea':
                    foreach ($arrCantidades as $idPedidoSalidaLinea => $cantidad):
                        //COMPRUEBO SI HAY UNA LINEA RELACIONADO CON LA COLA DE RESERVAS Y EL OBJETO QUE RESERVA
                        $num = $bd->NumRegsTabla("COLA_RESERVA_LINEA", "ID_COLA_RESERVA = $rowColaReserva->ID_COLA_RESERVA AND ID_PEDIDO_SALIDA_LINEA = $idPedidoSalidaLinea");

                        //ACCIONES EN FUNCION DE SI EXISTE UNA LINEA DE COLA DE RESERVA RELACIONADA O NO
                        if ($num == 0):
                            $sqlInsert = "INSERT INTO COLA_RESERVA_LINEA SET 
                                          ID_COLA_RESERVA = $rowColaReserva->ID_COLA_RESERVA 
                                          , CANTIDAD = $cantidad 
                                          , ID_PEDIDO_SALIDA_LINEA = $idPedidoSalidaLinea 
                                          , BAJA = 0";
                            $bd->ExecSQL($sqlInsert);
                        else:
                            $sqlUpdate = "UPDATE COLA_RESERVA_LINEA SET 
                                          CANTIDAD = $cantidad 
                                          , BAJA = 0 
                                          WHERE ID_COLA_RESERVA = $rowColaReserva->ID_COLA_RESERVA AND ID_PEDIDO_SALIDA_LINEA = $idPedidoSalidaLinea";
                            $bd->ExecSQL($sqlUpdate);
                        endif;
                        //FIN ACCIONES EN FUNCION DE SI EXISTE UNA LINEA DE COLA DE RESERVA RELACIONADA O NO
                    endforeach;

                    break;

                case 'PedidoEntradaLinea':
                    foreach ($arrCantidades as $idPedidoEntradaLinea => $cantidad):
                        //COMPRUEBO SI HAY UNA LINEA RELACIONADO CON LA COLA DE RESERVAS Y EL OBJETO QUE RESERVA
                        $num = $bd->NumRegsTabla("COLA_RESERVA_LINEA", "ID_COLA_RESERVA = $rowColaReserva->ID_COLA_RESERVA AND ID_PEDIDO_ENTRADA_LINEA = $idPedidoEntradaLinea");

                        //ACCIONES EN FUNCION DE SI EXISTE UNA LINEA DE COLA DE RESERVA RELACIONADA O NO
                        if ($num == 0):
                            $sqlInsert = "INSERT INTO COLA_RESERVA_LINEA SET 
                                          ID_COLA_RESERVA = $rowColaReserva->ID_COLA_RESERVA 
                                          , CANTIDAD = $cantidad 
                                          , ID_PEDIDO_ENTRADA_LINEA = $idPedidoEntradaLinea 
                                          , BAJA = 0";
                            $bd->ExecSQL($sqlInsert);
                        else:
                            $sqlUpdate = "UPDATE COLA_RESERVA_LINEA SET 
                                          CANTIDAD = $cantidad 
                                          , BAJA = 0 
                                          WHERE ID_COLA_RESERVA = $rowColaReserva->ID_COLA_RESERVA AND ID_PEDIDO_ENTRADA_LINEA = $idPedidoEntradaLinea";
                            $bd->ExecSQL($sqlUpdate);
                        endif;
                        //FIN ACCIONES EN FUNCION DE SI EXISTE UNA LINEA DE COLA DE RESERVA RELACIONADA O NO
                    endforeach;

                    break;

                default:
                    break;
            endswitch;
        endforeach;
        //TRATO EL ARRAY DE DATOS
    }


    /**
     * @param $idColaReserva Cola de Reservas a incluir sus lineas
     * @param $idOrdenTrabajoLinea Linea de orden de trabajo a la que rellenar sus lineas de cola de reservas
     * @return bool falso en caso de producirse algun error
     */
    function asociar_cola_reserva_lineas_OT($idColaReserva, $idOrdenTrabajoLinea)
    {
        global $bd;
        global $html;
        global $auxiliar;
        global $administrador;

        //ARRAY CON LOS DATOS A ASOCIAR A LA LINEA DE LA OR
        $arrDatos = array();

        //BUSCO LA COLA DE RESERVAS
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowColaReserva                   = $bd->VerReg("COLA_RESERVA", "ID_COLA_RESERVA", $idColaReserva, "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);

        //SI NO EXISTE LA COLA DE RESERVAS ME SALGO DE LA FUNCION
        if ($rowColaReserva == false):
            return false;
        endif;

        //BUSCO LA LINEA DE LA ORDEN DE TRABAJO
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowOrdenTrabajoLinea             = $bd->VerReg("ORDEN_TRABAJO_LINEA", "ID_ORDEN_TRABAJO_LINEA", $idOrdenTrabajoLinea, "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);

        //SI NO EXISTE LA LINEA DE LA ORDEN DE TRABAJO ME SALGO DE LA FUNCION
        if ($rowOrdenTrabajoLinea == false):
            return false;
        endif;

        //BUSCO LA ORDEN DE TRABAJO
        $rowOrdenTrabajo = $bd->VerReg("ORDEN_TRABAJO", "ID_ORDEN_TRABAJO", $rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO);

        //CALCULO LOS OBJETOS PARA ASOCIAR LAS LINEAS DE LA COLA DE RESERVAS
        if (
            (($rowOrdenTrabajo->SISTEMA_OT == 'MAXIMO') || ($rowOrdenTrabajo->SISTEMA_OT == 'SGA Manual')) &&
            ($rowOrdenTrabajoLinea->NUMERO_PENDIENTE != '') &&
            (($rowOrdenTrabajoLinea->PRIORIDAD_NUMERO_PENDIENTE == '1-Alta') || ($rowOrdenTrabajoLinea->PRIORIDAD_NUMERO_PENDIENTE == '2-Media') || ($rowOrdenTrabajoLinea->PRIORIDAD_NUMERO_PENDIENTE == '3-Baja'))
        ): //LINEA DE OT DE PENDIENTES
            //LOCALIZO LOS CAMBIOS DE ESTADO RELACIONADOS CON ESTA LINEA DE ORDEN DE TRABAJO
            $sqlCambiosEstado    = "SELECT CE.ID_CAMBIO_ESTADO, CE.CANTIDAD 
                                     FROM CAMBIO_ESTADO CE 
                                     WHERE CE.ID_ORDEN_TRABAJO_LINEA = $rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO_LINEA AND CE.TIPO_CAMBIO_ESTADO = 'ReservaPlanificadoOrdenesTrabajo' AND CE.ID_CAMBIO_ESTADO_RELACIONADO IS NULL AND BAJA = 0";
            $resultCambiosEstado = $bd->ExecSQL($sqlCambiosEstado);
            while ($rowCambioEstado = $bd->SigReg($resultCambiosEstado)):
                //CALCULO LA CANTIDAD (EL MINIMO ENTRE EL CAMBIO DE ESTADO, LA CANTIDAD RESERVADA DE LA LINEA DE OT Y LA CANTIDAD DE LA COLA DE RESERVAS)
                $cantidad = min($rowCambioEstado->CANTIDAD, $rowOrdenTrabajoLinea->CANTIDAD_RESERVADA, $rowColaReserva->CANTIDAD_EN_COLA);

                //AÑADO AL ARRAY LO NECESARIO
                $arrDatos['CambioEstado'][$rowCambioEstado->ID_CAMBIO_ESTADO] = $cantidad;
            endwhile;

            //LOCALIZO LAS LINEAS DE PEDIDO RELACIONADAS CON ESTA LINEA DE ORDEN DE TRABAJO
            $sqlLineasPedidoSalida    = "SELECT PSL.ID_PEDIDO_SALIDA_LINEA, PSL.CANTIDAD 
                                          FROM PEDIDO_SALIDA_LINEA PSL 
                                          WHERE PSL.ID_ORDEN_TRABAJO_LINEA = $rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO_LINEA AND PSL.ESTADO != 'Finalizada' AND PSL.INDICADOR_BORRADO IS NULL AND PSL.BAJA = 0";
            $resultLineasPedidoSalida = $bd->ExecSQL($sqlLineasPedidoSalida);
            while ($rowLineaPedidoSalida = $bd->SigReg($resultLineasPedidoSalida)):
                //CALCULO LA CANTIDAD (EL MINIMO ENTRE LA LINEA DE PEDIDO Y LA LINEA DE OT)
                $cantidad = min($rowLineaPedidoSalida->CANTIDAD, $rowOrdenTrabajoLinea->CANTIDAD_PEDIDA, $rowColaReserva->CANTIDAD_EN_COLA);

                //AÑADO AL ARRAY LO NECESARIO
                $arrDatos['PedidoSalidaLinea'][$rowLineaPedidoSalida->ID_PEDIDO_SALIDA_LINEA] = $cantidad;
            endwhile;
        elseif (
            (($rowOrdenTrabajo->SISTEMA_OT == 'MAXIMO') || ($rowOrdenTrabajo->SISTEMA_OT == 'SGA Manual')) &&
            ($rowOrdenTrabajoLinea->NUMERO_PENDIENTE == '') &&
            ($rowOrdenTrabajoLinea->PRIORIDAD_NUMERO_PENDIENTE == '_Null_') &&
            ($rowOrdenTrabajo->AGRUPADOR_OTS != '') &&
            (($rowOrdenTrabajo->TECNOLOGIA == 'Eólico') || ($rowOrdenTrabajo->TECNOLOGIA == 'Hidráulico de régimen ordinario') || ($rowOrdenTrabajo->TECNOLOGIA == 'Hidráulico de régimen especial')) &&
            (($rowOrdenTrabajo->TIPO_LISTA == 'Preventivo a inspecciones periodicas') || ($rowOrdenTrabajo->TIPO_LISTA == 'Mejoras y otros correctivos diferibles que lleven plan y no sean pendientes'))
        ): //LINEA DE OT DE PLANIFICADOS
            //LOCALIZO LAS LINEAS DE PEDIDO RELACIONADAS CON ESTA LINEA DE ORDEN DE TRABAJO
            $sqlLineasPedidoSalida    = "SELECT OTLCP.ID_PEDIDO_SALIDA_LINEA, OTLCP.CANTIDAD_CUBIERTA_POR_PEDIDO_SALIDA_LINEA AS CANTIDAD 
                                        FROM ORDEN_TRABAJO_LINEA_CUBIERTA_PEDIDO OTLCP
                                        WHERE OTLCP.ID_ORDEN_TRABAJO_LINEA = $rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO_LINEA AND OTLCP.BAJA = 0";
            $resultLineasPedidoSalida = $bd->ExecSQL($sqlLineasPedidoSalida);
            while ($rowLineaPedidoSalida = $bd->SigReg($resultLineasPedidoSalida)):
                //CALCULO LA CANTIDAD (EL MINIMO ENTRE LA LINEA DE PEDIDO Y LA LINEA DE OT)
                $cantidad = $rowLineaPedidoSalida->CANTIDAD;

                //AÑADO AL ARRAY LO NECESARIO
                $arrDatos['PedidoSalidaLinea'][$rowLineaPedidoSalida->ID_PEDIDO_SALIDA_LINEA] = $cantidad;
            endwhile;
        endif;
        //FIN CALCULO LOS OBJETOS PARA ASOCIAR LAS LINEAS DE LA COLA DE RESERVAS

        //SI EXISTE EL ARRAY DATOS CON LINEAS DE PEDIDO ACTUALIZO EL ESTADO DE COLA DE RESERVAS
        if ((isset($arrDatos['PedidoSalidaLinea'])) && ($arrDatos['PedidoSalidaLinea'] > 0)):
            $sqlUpdate = "UPDATE COLA_RESERVA SET 
                          ESTADO = 'Esperando Pedido Traslado' 
                          WHERE ID_COLA_RESERVA = $rowColaReserva->ID_COLA_RESERVA AND BAJA = 0";
            $bd->ExecSQL($sqlUpdate);
        endif;

        //LLAMO A LA FUNCION PARA ASIGNAR LAS LINEAS DE LA COLA DE RESERVAS
        $this->asociar_cola_reserva_lineas($idColaReserva, $arrDatos);
    }


    /**
     * @param $idDemanda
     * @param $origenDatos
     * @param $arr_datos
     * SI NO EXISTE LA RESERVA, SE CREA. CREA LAS RESERVAS LINEAS (con bloqueo RV o RVP) y GENERA LOS CAMBIOS DE ESTADO A RV/RVP
     */
    function asociar_reserva($idDemanda, $origenDatos, $arr_datos, $revisarTransferenciasPendientes = false)
    {
        global $bd;
        global $html;
        global $auxiliar;
        global $administrador;

        //ARRAY A DEVOLVER
        $arr_respuesta      = array();
        $cantidad_reservada = 0;

        //SOLO HAY UNA RESERVA POR DEMANDA
        $idReserva = $this->obtener_o_crear_reserva($idDemanda);

        //BUSCAMOS LA RESERVA
        $rowReserva = $bd->VerReg("RESERVA", "ID_RESERVA", $idReserva);

        //ARRAY PARA ALMACENAR LOS CAMBIOS DE ESTADO A QUITAR DE LA COLA DE RESERVAS
        $arrCambioEstadoQuitarColaReserva = array();

        //ARRAY PARA ALMACENAR LOS CAMBIOS DE ESTADO DE MATERIAL RESERVADO A REALIZAR
        $arrCambioEstadoReservado = array();

        //BUSCO EL TIPO DE BLOQUEO PLANIFICADO
        $rowTipoBloqueoPlanificado = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "SP");

        //BUSCO EL TIPO DE BLOQUEO RESERVADO
        $rowTipoBloqueoReservado = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "RV");

        //BUSCO EL TIPO DE BLOQUEO RESERVADO PLANIFICADO
        $rowTipoBloqueoReservadoPlanificado = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "RVP");

        //SEGUN EL ORIGEN DE LOS DATOS
        switch ($origenDatos):
            case 'CambioEstado':
                foreach ($arr_datos as $idCambioEstado => $cantidad):

                    //BUSCO EL CAMBIO DE ESTADO
                    $rowCambioEstado    = $bd->VerReg("CAMBIO_ESTADO", "ID_CAMBIO_ESTADO", $idCambioEstado);
                    $cantidad_pendiente = $rowCambioEstado->CANTIDAD;

                    if ($revisarTransferenciasPendientes):
                        //RECORRO LAS LINEAS DE LAS TRANSFERENCIAS DE LA OT
                        $sqlLineas    = "SELECT ID_MATERIAL, ID_UBICACION_DESTINO, ID_MATERIAL_FISICO, CANTIDAD
                                        FROM MOVIMIENTO_TRANSFERENCIA_PENDIENTES_LINEA
                                        WHERE ID_ORDEN_TRABAJO_LINEA = $rowCambioEstado->ID_ORDEN_TRABAJO_LINEA";
                        $resultLineas = $bd->ExecSQL($sqlLineas);
                        while ($rowLinea = $bd->SigReg($resultLineas)):
                            //GENERO UNA RESERVA LINEA POR TRANSFERENCIA
                            $sqlInsert = "INSERT INTO RESERVA_LINEA SET
                                          ID_RESERVA = $rowReserva->ID_RESERVA
                                          , FECHA_CREACION_LINEA = '" . date("Y-m-d H:i:s") . "'
                                          , ESTADO_LINEA = 'Reservada'
                                          , CANTIDAD = $rowLinea->CANTIDAD
                                          , ID_MATERIAL = $rowLinea->ID_MATERIAL
                                          , ID_UBICACION = $rowLinea->ID_UBICACION_DESTINO
                                          , ID_MATERIAL_FISICO = " . ($rowLinea->ID_MATERIAL_FISICO == NULL ? "NULL" : $rowLinea->ID_MATERIAL_FISICO) . "
                                          , ID_TIPO_BLOQUEO = " . $rowTipoBloqueoReservadoPlanificado->ID_TIPO_BLOQUEO . "
                                          , ID_ORDEN_TRABAJO_MOVIMIENTO = " . ($rowCambioEstado->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? "NULL" : $rowCambioEstado->ID_ORDEN_TRABAJO_MOVIMIENTO) . "
                                          , ID_INCIDENCIA_CALIDAD = " . ($rowCambioEstado->ID_INCIDENCIA_CALIDAD == NULL ? "NULL" : $rowCambioEstado->ID_INCIDENCIA_CALIDAD);
                            $bd->ExecSQL($sqlInsert);

                            //CREO LA CLAVE DEL ARRAY
                            $clave = $rowLinea->ID_MATERIAL . "_" . $rowLinea->ID_UBICACION_DESTINO . "_" . ($rowLinea->ID_MATERIAL_FISICO == NULL ? 0 : $rowLinea->ID_MATERIAL_FISICO) . "_" . $rowTipoBloqueoPlanificado->ID_TIPO_BLOQUEO . "_" . ($rowCambioEstado->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? 0 : $rowCambioEstado->ID_ORDEN_TRABAJO_MOVIMIENTO) . "_" . ($rowCambioEstado->ID_INCIDENCIA_CALIDAD == NULL ? 0 : $rowCambioEstado->ID_INCIDENCIA_CALIDAD);

                            //AÑADO LA LINEA AL ARRAY DE CAMBIOS DE ESTADO A RESERVADO A REALIZAR
                            if (!isset($arrCambioEstadoReservado[$clave])):
                                $arrCambioEstadoReservado[$clave] = $rowLinea->CANTIDAD;
                            else:
                                $arrCambioEstadoReservado[$clave] += $rowLinea->CANTIDAD;
                            endif;

                            //AÑADO EL CAMBIO DE ESTADO A QUITAR DE LA COLA DE RESERVAS
                            $arrCambioEstadoQuitarColaReserva[] = $rowCambioEstado->ID_CAMBIO_ESTADO;

                            //CANTIDAD TOTAL
                            $cantidad_reservada = $cantidad_reservada + $rowLinea->CANTIDAD;
                            $cantidad_pendiente = $cantidad_pendiente - $rowLinea->CANTIDAD;
                        endwhile;
                    endif;

                    if ($cantidad_pendiente > EPSILON_SISTEMA):
                        //SI EL TIPO DE BLOQUEO ES PLANIFICADO
                        if ($rowCambioEstado->ID_TIPO_BLOQUEO_FINAL == $rowTipoBloqueoPlanificado->ID_TIPO_BLOQUEO):
                            $idTipoBloqueoLinea = $rowTipoBloqueoReservadoPlanificado->ID_TIPO_BLOQUEO;
                        elseif ($rowCambioEstado->ID_TIPO_BLOQUEO_FINAL == $rowTipoBloqueoReservadoPlanificado->ID_TIPO_BLOQUEO):
                            $idTipoBloqueoLinea = $rowTipoBloqueoReservadoPlanificado->ID_TIPO_BLOQUEO;
                        else:
                            $idTipoBloqueoLinea = $rowTipoBloqueoReservado->ID_TIPO_BLOQUEO;
                        endif;

                        $GLOBALS["NotificaErrorPorEmail"] = "No";
                        $rowOrdenTrabajoLinea             = $bd->VerReg("ORDEN_TRABAJO_LINEA", "ID_ORDEN_TRABAJO_LINEA", $rowCambioEstado->ID_ORDEN_TRABAJO_LINEA, "No");
                        unset($GLOBALS["NotificaErrorPorEmail"]);

                        $materialSustitutivo = false;
                        if (($rowOrdenTrabajoLinea != false) && ($rowOrdenTrabajoLinea->ID_MATERIAL != $rowCambioEstado->ID_MATERIAL)):
                            $materialSustitutivo = true;
                        endif;

                        //BUSCO SI EXISTE UNA LINEA DE RESERVA SIMILAR
                        $GLOBALS["NotificaErrorPorEmail"] = "No";
                        $rowLineaReservaSimilar           = $bd->VerRegRest("RESERVA_LINEA", "ID_RESERVA = $rowReserva->ID_RESERVA AND ESTADO_LINEA = 'Reservada' AND ID_MATERIAL = $rowCambioEstado->ID_MATERIAL AND ID_UBICACION = $rowCambioEstado->ID_UBICACION AND ID_MATERIAL_FISICO " . ($rowCambioEstado->ID_MATERIAL_FISICO == NULL ? "IS NULL" : " = $rowCambioEstado->ID_MATERIAL_FISICO") . " AND RESERVA_CON_SUSTITUTIVO = " . ($materialSustitutivo == false ? 0 : 1) . " AND ID_TIPO_BLOQUEO " . ($idTipoBloqueoLinea != NULL ? " = $idTipoBloqueoLinea" : " IS NULL ") . " AND ID_ORDEN_TRABAJO_MOVIMIENTO " . ($rowCambioEstado->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? " IS NULL" : " = $rowCambioEstado->ID_ORDEN_TRABAJO_MOVIMIENTO") . " AND ID_INCIDENCIA_CALIDAD " . ($rowCambioEstado->ID_INCIDENCIA_CALIDAD == NULL ? " IS NULL " : " = $rowCambioEstado->ID_INCIDENCIA_CALIDAD") . " AND BAJA = 0", "No");
                        if ($rowLineaReservaSimilar != false):
                            $sqlUpdate = "UPDATE RESERVA_LINEA SET 
                                              CANTIDAD = CANTIDAD + $cantidad_pendiente 
                                              WHERE ID_RESERVA_LINEA = $rowLineaReservaSimilar->ID_RESERVA_LINEA";
                            $bd->ExecSQL($sqlUpdate);
                        else:
                            //GENERO UNA RESERVA LINEA POR CAMBIO DE ESTADO
                            $sqlInsert = "INSERT INTO RESERVA_LINEA SET
                                              ID_RESERVA = $rowReserva->ID_RESERVA
                                              , FECHA_CREACION_LINEA = '" . date("Y-m-d H:i:s") . "'
                                              , ESTADO_LINEA = 'Reservada'
                                              , CANTIDAD = $cantidad_pendiente
                                              , ID_MATERIAL = $rowCambioEstado->ID_MATERIAL
                                              , ID_UBICACION = $rowCambioEstado->ID_UBICACION
                                              , ID_MATERIAL_FISICO = " . ($rowCambioEstado->ID_MATERIAL_FISICO == NULL ? "NULL" : $rowCambioEstado->ID_MATERIAL_FISICO) . "
                                              , RESERVA_CON_SUSTITUTIVO = " . ($materialSustitutivo == false ? 0 : 1) . "
                                              , ID_TIPO_BLOQUEO = " . $idTipoBloqueoLinea . "
                                              , ID_ORDEN_TRABAJO_MOVIMIENTO = " . ($rowCambioEstado->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? "NULL" : $rowCambioEstado->ID_ORDEN_TRABAJO_MOVIMIENTO) . "
                                              , ID_INCIDENCIA_CALIDAD = " . ($rowCambioEstado->ID_INCIDENCIA_CALIDAD == NULL ? "NULL" : $rowCambioEstado->ID_INCIDENCIA_CALIDAD);
                            $bd->ExecSQL($sqlInsert);
                        endif;

                        //CREO LA CLAVE DEL ARRAY
                        $clave = $rowCambioEstado->ID_MATERIAL . "_" . $rowCambioEstado->ID_UBICACION . "_" . ($rowCambioEstado->ID_MATERIAL_FISICO == NULL ? 0 : $rowCambioEstado->ID_MATERIAL_FISICO) . "_" . ($rowCambioEstado->ID_TIPO_BLOQUEO_FINAL == NULL ? 0 : $rowCambioEstado->ID_TIPO_BLOQUEO_FINAL) . "_" . ($rowCambioEstado->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? 0 : $rowCambioEstado->ID_ORDEN_TRABAJO_MOVIMIENTO) . "_" . ($rowCambioEstado->ID_INCIDENCIA_CALIDAD == NULL ? 0 : $rowCambioEstado->ID_INCIDENCIA_CALIDAD);

                        //AÑADO LA LINEA AL ARRAY DE CAMBIOS DE ESTADO A RESERVADO A REALIZAR
                        if (!isset($arrCambioEstadoReservado[$clave])):
                            $arrCambioEstadoReservado[$clave] = $cantidad_pendiente;
                        else:
                            $arrCambioEstadoReservado[$clave] += $cantidad_pendiente;
                        endif;

                        //AÑADO EL CAMBIO DE ESTADO A QUITAR DE LA COLA DE RESERVAS
                        $arrCambioEstadoQuitarColaReserva[] = $rowCambioEstado->ID_CAMBIO_ESTADO;

                        //CANTIDAD TOTAL
                        $cantidad_reservada = $cantidad_reservada + $cantidad_pendiente;
                    endif;
                endforeach;

                break;

            case 'PedidoSalidaLinea':
                break;

            case 'PedidoEntradaLinea':
                break;

            case 'MaterialUbicacion':
                foreach ($arr_datos as $indice => $arrayValores):

                    if ($arrayValores["CANTIDAD"] > EPSILON_SISTEMA):

                        //SI EL TIPO DE BLOQUEO ES PLANIFICADO
                        if ($arrayValores["ID_TIPO_BLOQUEO"] == $rowTipoBloqueoPlanificado->ID_TIPO_BLOQUEO):
                            $idTipoBloqueoLinea = $rowTipoBloqueoReservadoPlanificado->ID_TIPO_BLOQUEO;
                        else:
                            $idTipoBloqueoLinea = $rowTipoBloqueoReservado->ID_TIPO_BLOQUEO;
                        endif;

                        //GENERO UNA RESERVA LINEA
                        $sqlInsert = "INSERT INTO RESERVA_LINEA SET
                                      ID_RESERVA = $rowReserva->ID_RESERVA  
                                      , FECHA_CREACION_LINEA = '" . date("Y-m-d H:i:s") . "' 
                                      , ESTADO_LINEA = 'Reservada' 
                                      , CANTIDAD = " . $arrayValores["CANTIDAD"] . "
                                      , ID_MATERIAL = " . $arrayValores["ID_MATERIAL"] . "
                                      , ID_UBICACION = " . $arrayValores["ID_UBICACION"] . " 
                                      , ID_MATERIAL_FISICO = " . ($arrayValores["ID_MATERIAL_FISICO"] == NULL ? "NULL" : $arrayValores["ID_MATERIAL_FISICO"]) . "
                                      , ID_TIPO_BLOQUEO = " . $idTipoBloqueoLinea . " 
                                      , ID_ORDEN_TRABAJO_MOVIMIENTO = " . ($arrayValores["ID_ORDEN_TRABAJO_MOVIMIENTO"] == NULL ? "NULL" : $arrayValores["ID_ORDEN_TRABAJO_MOVIMIENTO"]) . " 
                                      , ID_INCIDENCIA_CALIDAD = " . ($arrayValores["ID_INCIDENCIA_CALIDAD"] == NULL ? "NULL" : $arrayValores["ID_INCIDENCIA_CALIDAD"]);
                        $bd->ExecSQL($sqlInsert);

                        //CREO LA CLAVE DEL ARRAY
                        $clave = $arrayValores["ID_MATERIAL"] . "_" . $arrayValores["ID_UBICACION"] . "_" . ($arrayValores["ID_MATERIAL_FISICO"] == NULL ? 0 : $arrayValores["ID_MATERIAL_FISICO"]) . "_" . ($arrayValores["ID_TIPO_BLOQUEO"] == NULL ? 0 : $arrayValores["ID_TIPO_BLOQUEO"]) . "_" . ($arrayValores["ID_ORDEN_TRABAJO_MOVIMIENTO"] == NULL ? 0 : $arrayValores["ID_ORDEN_TRABAJO_MOVIMIENTO"]) . "_" . ($arrayValores["ID_INCIDENCIA_CALIDAD"] == NULL ? 0 : $arrayValores["ID_INCIDENCIA_CALIDAD"]);

                        //AÑADO LA LINEA AL ARRAY DE CAMBIOS DE ESTADO A RESERVADO A REALIZAR
                        if (!isset($arrCambioEstadoReservado[$clave])):
                            $arrCambioEstadoReservado[$clave] = $arrayValores["CANTIDAD"];
                        else:
                            $arrCambioEstadoReservado[$clave] += $arrayValores["CANTIDAD"];
                        endif;

                        //CANTIDAD TOTAL
                        $cantidad_reservada = $cantidad_reservada + $arrayValores["CANTIDAD"];
                    endif;
                endforeach;

                break;

            case 'MaterialUbicacionManual':
                foreach ($arr_datos as $indice => $arrayValores):

                    if ($arrayValores["CANTIDAD"] > EPSILON_SISTEMA):

                        //SI EL TIPO DE BLOQUEO ES PLANIFICADO
                        if ($arrayValores["ID_TIPO_BLOQUEO"] == $rowTipoBloqueoPlanificado->ID_TIPO_BLOQUEO):
                            $idTipoBloqueoLinea = $rowTipoBloqueoReservadoPlanificado->ID_TIPO_BLOQUEO;
                        else:
                            $idTipoBloqueoLinea = $rowTipoBloqueoReservado->ID_TIPO_BLOQUEO;
                        endif;

                        //GENERO UNA RESERVA LINEA
                        $sqlInsert = "INSERT INTO RESERVA_LINEA SET
                                      ID_RESERVA = $rowReserva->ID_RESERVA  
                                      , FECHA_CREACION_LINEA = '" . date("Y-m-d H:i:s") . "' 
                                      , ESTADO_LINEA = 'Reservada' 
                                      , CANTIDAD = " . $arrayValores["CANTIDAD"] . "
                                      , ID_MATERIAL = " . $arrayValores["ID_MATERIAL"] . "
                                      , ID_UBICACION = " . $arrayValores["ID_UBICACION"] . " 
                                      , ID_MATERIAL_FISICO = " . ($arrayValores["ID_MATERIAL_FISICO"] == NULL ? "NULL" : $arrayValores["ID_MATERIAL_FISICO"]) . "
                                      , ID_TIPO_BLOQUEO = " . $idTipoBloqueoLinea . " 
                                      , ID_ORDEN_TRABAJO_MOVIMIENTO = " . ($arrayValores["ID_ORDEN_TRABAJO_MOVIMIENTO"] == NULL ? "NULL" : $arrayValores["ID_ORDEN_TRABAJO_MOVIMIENTO"]) . " 
                                      , RESERVA_FIJA = 1
                                      , ID_INCIDENCIA_CALIDAD = " . ($arrayValores["ID_INCIDENCIA_CALIDAD"] == NULL ? "NULL" : $arrayValores["ID_INCIDENCIA_CALIDAD"]);
                        $bd->ExecSQL($sqlInsert);

                        //CREO LA CLAVE DEL ARRAY
                        $clave = $arrayValores["ID_MATERIAL"] . "_" . $arrayValores["ID_UBICACION"] . "_" . ($arrayValores["ID_MATERIAL_FISICO"] == NULL ? 0 : $arrayValores["ID_MATERIAL_FISICO"]) . "_" . ($arrayValores["ID_TIPO_BLOQUEO"] == NULL ? 0 : $arrayValores["ID_TIPO_BLOQUEO"]) . "_" . ($arrayValores["ID_ORDEN_TRABAJO_MOVIMIENTO"] == NULL ? 0 : $arrayValores["ID_ORDEN_TRABAJO_MOVIMIENTO"]) . "_" . ($arrayValores["ID_INCIDENCIA_CALIDAD"] == NULL ? 0 : $arrayValores["ID_INCIDENCIA_CALIDAD"]);

                        //AÑADO LA LINEA AL ARRAY DE CAMBIOS DE ESTADO A RESERVADO A REALIZAR
                        if (!isset($arrCambioEstadoReservado[$clave])):
                            $arrCambioEstadoReservado[$clave] = $arrayValores["CANTIDAD"];
                        else:
                            $arrCambioEstadoReservado[$clave] += $arrayValores["CANTIDAD"];
                        endif;

                        //CANTIDAD TOTAL
                        $cantidad_reservada = $cantidad_reservada + $arrayValores["CANTIDAD"];
                    endif;
                endforeach;

                break;

            default:
                break;
        endswitch;
        //SEGUN EL ORIGEN DE LOS DATOS

        //RECORRER MATERIALES UBICACION PARA HACER EL CAMBIO DE ESTADO CORRESPONDIENTE
        foreach ($arrCambioEstadoReservado as $clave => $cantidad):
            //EXTRAIGO LOS VALORES DE LA CLAVE
            $arrClave = explode("_", (string)$clave); //Material/Ubicacion/Material Fisico/Tipo Bloqueo/Orden Trabajo Movimiento/Incidencia Calidad

            //Tipo Bloqueo
            $rowNuevoTipoBloqueo = NULL;
            if ($arrClave[3] == 0):
                $rowNuevoTipoBloqueo = $rowTipoBloqueoReservado;
            elseif ($arrClave[3] == $rowTipoBloqueoPlanificado->ID_TIPO_BLOQUEO):
                $rowNuevoTipoBloqueo = $rowTipoBloqueoReservadoPlanificado;
            else:
                continue;
            endif;

            //SI EL NUEVO TIPO DE BLOQEO NO ES NULO GENERAMOS EL CAMBIO DE ESTADO CORRESPONDIENTE PARA RESERVAR EL MATERIAL
            if ($rowNuevoTipoBloqueo != NULL):
                //CREO EL CAMBIO DE ESTADO
                $sqlInsert = "INSERT INTO CAMBIO_ESTADO SET
                                  FECHA = '" . date("Y-m-d H:i:s") . "'
                                  , TIPO_CAMBIO_ESTADO = 'ReservaDemanda'
                                  , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                  , ID_MATERIAL = " . $arrClave[0] . " 
                                  , ID_UBICACION = " . $arrClave[1] . " 
                                  , CANTIDAD = $cantidad 
                                  , ID_TIPO_BLOQUEO_INICIAL = " . ($arrClave[3] == 0 ? "NULL" : $arrClave[3]) . " 
                                  , ID_TIPO_BLOQUEO_FINAL = $rowNuevoTipoBloqueo->ID_TIPO_BLOQUEO 
                                  , ID_MATERIAL_FISICO = " . ($arrClave[2] == 0 ? "NULL" : $arrClave[2]) . " 
                                  , ID_ORDEN_TRABAJO_MOVIMIENTO = " . ($arrClave[4] == 0 ? "NULL" : $arrClave[4]) . " 
                                  , ID_INCIDENCIA_CALIDAD = " . ($arrClave[5] == 0 ? "NULL" : $arrClave[5]);
                $bd->ExecSQL($sqlInsert);

                //BUSCO MATERIAL_UBICACION ORIGEN
                $GLOBALS["NotificaErrorPorEmail"] = "No";
                $clausulaWhere                    = "ID_MATERIAL = $arrClave[0] AND ID_UBICACION = $arrClave[1] AND ID_MATERIAL_FISICO " . ($arrClave[2] == 0 ? "IS NULL" : "= $arrClave[2]") . " AND ID_TIPO_BLOQUEO " . ($arrClave[3] == 0 ? "IS NULL" : "= $arrClave[3]") . "  AND ID_ORDEN_TRABAJO_MOVIMIENTO " . ($arrClave[4] == 0 ? "IS NULL" : "= $arrClave[4]") . " AND ID_INCIDENCIA_CALIDAD " . ($arrClave[5] == 0 ? "IS NULL" : "= $arrClave[5]");
                $rowMatUbiOrigen                  = $bd->VerRegRest("MATERIAL_UBICACION", $clausulaWhere, "No");
                if ($rowMatUbiOrigen == false):
                    $rowMatInsuficiente     = $bd->VerReg("MATERIAL", "ID_MATERIAL", $arrClave[0]);
                    $rowUbi                 = $bd->VerReg("UBICACION", "ID_UBICACION", $arrClave[1]);
                    $arr_respuesta['error'] = $auxiliar->traduce("Stock Insuficiente del material", $administrador->ID_IDIOMA) . " " . $rowMatInsuficiente->REFERENCIA_SGA . " - " . ($administrador->ID_IDIOMA == 'ENG' ? $rowMatInsuficiente->DESCRIPCION_EN : $rowMatInsuficiente->DESCRIPCION) . ". " . $auxiliar->traduce("Ubicacion", $administrador->ID_IDIOMA) . ": $rowUbi->UBICACION. " . $auxiliar->traduce("Es necesario disponer de", $administrador->ID_IDIOMA) . " " . ($cantidad);

                    return $arr_respuesta;
                elseif (($cantidad - $rowMatUbiOrigen->STOCK_TOTAL) > EPSILON_SISTEMA):
                    $rowMatInsuficiente     = $bd->VerReg("MATERIAL", "ID_MATERIAL", $arrClave[0]);
                    $rowUbi                 = $bd->VerReg("UBICACION", "ID_UBICACION", $arrClave[1]);
                    $arr_respuesta['error'] = $auxiliar->traduce("Stock Insuficiente del material", $administrador->ID_IDIOMA) . " " . $rowMatInsuficiente->REFERENCIA_SGA . " - " . ($administrador->ID_IDIOMA == 'ENG' ? $rowMatInsuficiente->DESCRIPCION_EN : $rowMatInsuficiente->DESCRIPCION) . ". " . $auxiliar->traduce("Ubicacion", $administrador->ID_IDIOMA) . ": $rowUbi->UBICACION. " . $auxiliar->traduce("Es necesario disponer de", $administrador->ID_IDIOMA) . " " . ($cantidad);

                    return $arr_respuesta;
                endif;

                //DECREMENTO MATERIAL_UBICACION ORIGEN
                $sqlUpdate = "UPDATE MATERIAL_UBICACION SET
                                      STOCK_TOTAL = STOCK_TOTAL - $cantidad 
                                      , STOCK_OK = STOCK_OK - " . ($arrClave[3] == 0 ? $cantidad : 0) . "
                                      , STOCK_BLOQUEADO = STOCK_BLOQUEADO - " . ($arrClave[3] == 0 ? 0 : $cantidad) . "
                                      WHERE ID_MATERIAL_UBICACION = " . $rowMatUbiOrigen->ID_MATERIAL_UBICACION;
                $bd->ExecSQL($sqlUpdate);

                //BUSCO MATERIAL_UBICACION DESTINO
                $GLOBALS["NotificaErrorPorEmail"] = "No";
                $clausulaWhere                    = "ID_MATERIAL = $arrClave[0] AND ID_UBICACION = $arrClave[1] AND ID_MATERIAL_FISICO " . ($arrClave[2] == 0 ? "IS NULL" : " = $arrClave[2]") . " AND ID_TIPO_BLOQUEO = $rowNuevoTipoBloqueo->ID_TIPO_BLOQUEO AND ID_ORDEN_TRABAJO_MOVIMIENTO " . ($arrClave[4] == 0 ? "IS NULL" : " = $arrClave[4]") . " AND ID_INCIDENCIA_CALIDAD " . ($arrClave[5] == 0 ? "IS NULL" : " = $arrClave[5]");
                $rowMatUbiDestino                 = $bd->VerRegRest("MATERIAL_UBICACION", $clausulaWhere, "No");
                if ($rowMatUbiDestino == false):
                    //CREO MATERIAL UBICACION DESTINO
                    $sqlInsert = "INSERT INTO MATERIAL_UBICACION SET
                                          ID_MATERIAL = $arrClave[0] 
                                          , ID_UBICACION = $arrClave[1]
                                          , ID_MATERIAL_FISICO = " . ($arrClave[2] == 0 ? "NULL" : $arrClave[2]) . " 
                                          , ID_TIPO_BLOQUEO = $rowNuevoTipoBloqueo->ID_TIPO_BLOQUEO 
                                          , ID_ORDEN_TRABAJO_MOVIMIENTO = " . ($arrClave[4] == 0 ? "NULL" : $arrClave[4]) . " 
                                          , ID_INCIDENCIA_CALIDAD = " . ($arrClave[5] == 0 ? "NULL" : $arrClave[5]);
                    $bd->ExecSQL($sqlInsert);

                    //GUARDO EL ID MATERIAL UBICACION DESTINO
                    $idMatUbiDestino = $bd->IdAsignado();
                else:
                    //GUARDO EL ID MATERIAL UBICACION DESTINO
                    $idMatUbiDestino = $rowMatUbiDestino->ID_MATERIAL_UBICACION;
                endif;

                //INCREMENTO MATERIAL_UBICACION DESTINO
                $sqlUpdate = "UPDATE MATERIAL_UBICACION SET
                                      STOCK_TOTAL = STOCK_TOTAL + $cantidad
                                      , STOCK_OK = STOCK_OK + " . ($rowNuevoTipoBloqueo->ID_TIPO_BLOQUEO == NULL ? $cantidad : 0) . "
                                      , STOCK_BLOQUEADO = STOCK_BLOQUEADO + " . ($rowNuevoTipoBloqueo->ID_TIPO_BLOQUEO == NULL ? 0 : $cantidad) . "
                                      WHERE ID_MATERIAL_UBICACION = $idMatUbiDestino";
                $bd->ExecSQL($sqlUpdate);
            endif;
            //FIN SI EL NUEVO TIPO DE BLOQEO NO ES NULO GENERAMOS EL CAMBIO DE ESTADO CORRESPONDIENTE PARA RESERVAR EL MATERIAL
        endforeach;
        //FIN RECORRER MATERIALES UBICACION PARA HACER EL CAMBIO DE ESTADO CORRESPONDIENTE

        //BUSCAMOS LA DEMANDA
        $rowDemanda = $bd->VerReg("DEMANDA", "ID_DEMANDA", $rowReserva->ID_DEMANDA);

        //ACTUALIZAMOS COLA Y RESERVA
        if ($cantidad_reservada > EPSILON_SISTEMA):
            //QUITAMOS LA CANTIDAD DE LA COLA
            $this->quitar_cantidad_cola($rowDemanda->ID_DEMANDA, $cantidad_reservada, 'Reserva Cantidad: ' . $cantidad_reservada . ' en la Reserva ' . $rowReserva->ID_RESERVA, NULL, $arrCambioEstadoQuitarColaReserva);

            //ACTUALIZAMOS LA DEMANDA
            $updateEstado = "";
            if ($rowDemanda->CANTIDAD_PENDIENTE_RESERVAR - $cantidad_reservada < EPSILON_SISTEMA):
                $updateEstado = " , FECHA_CIERRE_RESERVA = '" . date("Y-m-d H:i:s") . "'
                                  , ESTADO = 'Cubierta' ";
            endif;
            $sqlUpdate = "UPDATE DEMANDA SET 
                               CANTIDAD_PENDIENTE_RESERVAR = CANTIDAD_PENDIENTE_RESERVAR - $cantidad_reservada
                               " . $updateEstado . "
                        WHERE ID_DEMANDA = $rowDemanda->ID_DEMANDA";
            $bd->ExecSQL($sqlUpdate);

            //SI LA CANTIDAD PENDIENTE RESERVAR VA A SER NEGATIVA DEVUELVO UN ERROR
            if (($rowDemanda->CANTIDAD_PENDIENTE_RESERVAR - $cantidad_reservada) < (EPSILON_SISTEMA * -1)):
                $arr_respuesta['error'] = $auxiliar->traduce("La cantidad pendiente de reservar en la demanda es negativa", $administrador->ID_IDIOMA) . ". " . $auxiliar->traduce("Demanda", $administrador->ID_IDIOMA) . ": " . $rowDemanda->ID_DEMANDA;

                return $arr_respuesta;
            endif;

            //LOG MOVIMIENTOS
            $rowDemandaActualizada = $bd->VerReg("DEMANDA", "ID_DEMANDA", $rowDemanda->ID_DEMANDA);
            $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Demanda", $rowDemanda->ID_DEMANDA, 'Reserva de Material:' . $rowReserva->ID_RESERVA, 'DEMANDA', $rowDemanda, $rowDemandaActualizada);
        endif;

        $arr_respuesta['cantidad_reservada'] = $cantidad_reservada;
        $arr_respuesta['idReserva']          = $idReserva;

        //SI LA DEMANDA ES DE TIPO OT, ACTUALIZAMOS LAS TRANSFERENCIAS DE PENDIENTES SI LAS HAY
        if ($rowDemanda->TIPO_DEMANDA == 'OT'):
            $this->actualizar_transferencias_pendientes($rowDemanda->ID_DEMANDA);
        endif;

        return $arr_respuesta;
    }


    /**
     * @param $idDemanda
     * @param $cantidadAnular
     * @param $actualizarDemanda => Si está a 1, lo que se saca de la reserva se mete en la cola y se actualiza la demanda
     * DESHACE LA RESERVA
     */
    function anular_reserva($idDemanda, $cantidadAnular, $actualizarDemanda = 0, $observacionesLog = "")
    {
        global $bd;
        global $html;
        global $auxiliar;
        global $administrador;

        //ARRAY A DEVOLVER
        $arr_respuesta = array();

        //ARRAY PARA ALMACENAR LOS CAMBIOS DE ESTADO DE MATERIAL RESERVADO A REALIZAR
        $arrCambioEstadoReservado = array();

        $cantidadPdteLiberar = $cantidadAnular;

        //SE OBTIENE LA RESERVA
        $rowReserva = $this->get_reserva_demanda($idDemanda);
        if ($rowReserva != false):

            //BUSCAMOS LAS LINEAS EN ESTADO RESERVADA Y LIBERAMOS EL STOCK
            $resultLineas = $this->get_lineas_reservas_demanda($idDemanda, 'Reservada');
            if (($resultLineas != false) && ($bd->NumRegs($resultLineas) > 0)):
                //BUSCO EL TIPO DE BLOQUEO PLANIFICADO
                $rowTipoBloqueoPlanificado = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "SP");

                //BUSCO EL TIPO DE BLOQUEO RESERVADO
                $rowTipoBloqueoReservado = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "RV");

                //BUSCO EL TIPO DE BLOQUEO RESERVADO PLANIFICADO
                $rowTipoBloqueoReservadoPlanificado = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "RVP");

                //SEGUN EL ORIGEN DE LOS DATOS
                while (($rowLinea = $bd->SigReg($resultLineas)) && ($cantidadPdteLiberar > EPSILON_SISTEMA)):

                    //SI NECESITAMOS RESERVAR TODA LA LINEA, LA DAMOS DE BAJA
                    if ((($cantidadPdteLiberar - $rowLinea->CANTIDAD) > EPSILON_SISTEMA) || ((($cantidadPdteLiberar - $rowLinea->CANTIDAD) < EPSILON_SISTEMA) && (($cantidadPdteLiberar - $rowLinea->CANTIDAD) > -EPSILON_SISTEMA))):
                        $cantidadPdteLiberar   = $cantidadPdteLiberar - $rowLinea->CANTIDAD;
                        $cantidadLiberadaLinea = $rowLinea->CANTIDAD;
                        $sqlUpdate             = "UPDATE RESERVA_LINEA SET BAJA = 1 WHERE ID_RESERVA_LINEA = " . $rowLinea->ID_RESERVA_LINEA;
                        $bd->ExecSQL($sqlUpdate);
                    else:
                        //SI SOBRA CANTIDAD DE LA LINEA, SE LO QUITAMOS
                        $cantidadLiberadaLinea = $cantidadPdteLiberar;
                        $cantidadPdteLiberar   = 0;
                        $sqlUpdate             = "UPDATE RESERVA_LINEA SET CANTIDAD = CANTIDAD - $cantidadLiberadaLinea WHERE ID_RESERVA_LINEA = " . $rowLinea->ID_RESERVA_LINEA;
                        $bd->ExecSQL($sqlUpdate);
                    endif;

                    //SI LA CANTIDAD DE LA LÍNEA ERA MAYOR QUE 0
                    if ($rowLinea->CANTIDAD > EPSILON_SISTEMA):

                        //LOG MOVIMIENTOS
                        $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Reserva", $rowLinea->ID_RESERVA, 'Quitar Cantidad:' . $cantidadLiberadaLinea . " . Reserva Linea: " . $rowLinea->ID_RESERVA_LINEA . " " . $observacionesLog);

                        //CREO LA CLAVE DEL ARRAY
                        $clave = $rowLinea->ID_MATERIAL . "_" . $rowLinea->ID_UBICACION . "_" . ($rowLinea->ID_MATERIAL_FISICO == NULL ? 0 : $rowLinea->ID_MATERIAL_FISICO) . "_" . ($rowLinea->ID_TIPO_BLOQUEO == NULL ? 0 : $rowLinea->ID_TIPO_BLOQUEO) . "_" . ($rowLinea->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? 0 : $rowLinea->ID_ORDEN_TRABAJO_MOVIMIENTO) . "_" . ($rowLinea->ID_INCIDENCIA_CALIDAD == NULL ? 0 : $rowLinea->ID_INCIDENCIA_CALIDAD);

                        //AÑADO LA LINEA AL ARRAY DE CAMBIOS DE ESTADO A RESERVADO A REALIZAR
                        if (!isset($arrCambioEstadoReservado[$clave])):
                            $arrCambioEstadoReservado[$clave] = $cantidadLiberadaLinea;
                        else:
                            $arrCambioEstadoReservado[$clave] += $cantidadLiberadaLinea;
                        endif;
                    endif;
                endwhile;//FIN RECORRER LINEAS
            endif;
        else:
            $arr_respuesta['error'] = $auxiliar->traduce("No se encuentra la Reserva para la Demanda", $administrador->ID_IDIOMA) . " $idDemanda";

            return $arr_respuesta;
        endif;//FIN SI EXISTE LA RESERVA

        if ($cantidadPdteLiberar > EPSILON_SISTEMA):
            $arr_respuesta['error'] = $auxiliar->traduce("No se ha podido anular toda la cantidad Reservada", $administrador->ID_IDIOMA);

            return $arr_respuesta;
        endif;


        //RECORRER MATERIALES UBICACION PARA HACER EL CAMBIO DE ESTADO CORRESPONDIENTE
        if (count( (array)$arrCambioEstadoReservado) > 0):
            foreach ($arrCambioEstadoReservado as $clave => $cantidad):
                //EXTRAIGO LOS VALORES DE LA CLAVE
                $arrClave = explode("_", (string)$clave); //Material/Ubicacion/Material Fisico/Tipo Bloqueo/Orden Trabajo Movimiento/Incidencia Calidad

                //Tipo Bloqueo
                if ($arrClave[3] == $rowTipoBloqueoReservado->ID_TIPO_BLOQUEO):
                    $idNuevoTipoBloqueo = 0;
                elseif ($arrClave[3] == $rowTipoBloqueoReservadoPlanificado->ID_TIPO_BLOQUEO):
                    $idNuevoTipoBloqueo = $rowTipoBloqueoPlanificado->ID_TIPO_BLOQUEO;
                else:
                    continue;
                endif;

                //BUSCAMOS EL MATERIAL UBICACION ORIGEN PARA COMPROBAR SI HAY SUFICIENTE STOCK
                $GLOBALS["NotificaErrorPorEmail"] = "No";
                $clausulaWhere                    = "ID_MATERIAL = $arrClave[0] AND ID_UBICACION = $arrClave[1] AND ID_MATERIAL_FISICO " . ($arrClave[2] == 0 ? "IS NULL" : " = $arrClave[2]") . " AND ID_TIPO_BLOQUEO " . ($arrClave[3] == 0 ? "IS NULL" : " = $arrClave[3]") . " AND ID_ORDEN_TRABAJO_MOVIMIENTO " . ($arrClave[4] == 0 ? "IS NULL" : " = $arrClave[4]") . " AND ID_INCIDENCIA_CALIDAD " . ($arrClave[5] == 0 ? "IS NULL" : " = $arrClave[5]");
                $rowMatUbiOrigen                  = $bd->VerRegRest("MATERIAL_UBICACION", $clausulaWhere, "No");
                if (($rowMatUbiOrigen == false) || (($cantidad - $rowMatUbiOrigen->STOCK_TOTAL) > EPSILON_SISTEMA)):
                    $arr_respuesta['error'] = $auxiliar->traduce("No se ha podido anular la Reserva porque el Stock ya no se encuentra Reservado", $administrador->ID_IDIOMA) . "<br>";

                    //PREPARAMOS EL ERROR
                    $rowUbi  = $bd->VerReg("UBICACION", "ID_UBICACION", $arrClave[1]);
                    $textoMF = "-";
                    if ($arrClave[2] != 0):
                        $rowMF   = $bd->VerReg("MATERIAL_FISICO", "ID_MATERIAL_FISICO", $arrClave[2]);
                        $textoMF = $rowMF->NUMERO_SERIE_LOTE;
                    endif;
                    $arr_respuesta['error'] .= $auxiliar->traduce("Cantidad", $administrador->ID_IDIOMA) . ": " . $rowMatUbiOrigen->STOCK_TOTAL . ", " . $auxiliar->traduce("Ubicacion", $administrador->ID_IDIOMA) . ": " . $rowUbi->UBICACION . ", " . $auxiliar->traduce("S / L", $administrador->ID_IDIOMA) . ": " . $textoMF . "<br>";

                    return $arr_respuesta;
                endif;

                //CREO EL CAMBIO DE ESTADO
                $sqlInsert = "INSERT INTO CAMBIO_ESTADO SET
                              FECHA = '" . date("Y-m-d H:i:s") . "'
                              , TIPO_CAMBIO_ESTADO = 'AnulacionReservaDemanda'
                              , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                              , ID_MATERIAL = " . $arrClave[0] . " 
                              , ID_UBICACION = " . $arrClave[1] . " 
                              , CANTIDAD = $cantidad 
                              , ID_TIPO_BLOQUEO_INICIAL = " . ($arrClave[3] == 0 ? "NULL" : $arrClave[3]) . " 
                              , ID_TIPO_BLOQUEO_FINAL = " . ($idNuevoTipoBloqueo == 0 ? "NULL" : $idNuevoTipoBloqueo) . " 
                              , ID_MATERIAL_FISICO = " . ($arrClave[2] == 0 ? "NULL" : $arrClave[2]) . " 
                              , ID_ORDEN_TRABAJO_MOVIMIENTO = " . ($arrClave[4] == 0 ? "NULL" : $arrClave[4]) . " 
                              , ID_INCIDENCIA_CALIDAD = " . ($arrClave[5] == 0 ? "NULL" : $arrClave[5]);
                $bd->ExecSQL($sqlInsert);

                //DECREMENTO MATERIAL_UBICACION ORIGEN
                $sqlUpdate = "UPDATE MATERIAL_UBICACION SET
                              STOCK_TOTAL = STOCK_TOTAL - $cantidad 
                              , STOCK_OK = STOCK_OK - " . ($arrClave[3] == 0 ? $cantidad : 0) . "
                              , STOCK_BLOQUEADO = STOCK_BLOQUEADO - " . ($arrClave[3] == 0 ? 0 : $cantidad) . "
                              WHERE ID_MATERIAL_UBICACION = " . $rowMatUbiOrigen->ID_MATERIAL_UBICACION;
                $bd->ExecSQL($sqlUpdate);

                //BUSCO MATERIAL_UBICACION DESTINO
                $GLOBALS["NotificaErrorPorEmail"] = "No";
                $clausulaWhere                    = "ID_MATERIAL = $arrClave[0] AND ID_UBICACION = $arrClave[1] AND ID_MATERIAL_FISICO " . ($arrClave[2] == 0 ? "IS NULL" : " = $arrClave[2]") . " AND ID_TIPO_BLOQUEO " . ($idNuevoTipoBloqueo == 0 ? "IS NULL" : " = $idNuevoTipoBloqueo") . " AND ID_ORDEN_TRABAJO_MOVIMIENTO " . ($arrClave[4] == 0 ? "IS NULL" : " = $arrClave[4]") . " AND ID_INCIDENCIA_CALIDAD " . ($arrClave[5] == 0 ? "IS NULL" : " = $arrClave[5]");
                $rowMatUbiDestino                 = $bd->VerRegRest("MATERIAL_UBICACION", $clausulaWhere, "No");
                if ($rowMatUbiDestino == false):
                    //CREO MATERIAL UBICACION DESTINO
                    $sqlInsert = "INSERT INTO MATERIAL_UBICACION SET
                                  ID_MATERIAL = $arrClave[0] 
                                  , ID_UBICACION = $arrClave[1]
                                  , ID_MATERIAL_FISICO = " . ($arrClave[2] == 0 ? "NULL" : $arrClave[2]) . " 
                                  , ID_TIPO_BLOQUEO = " . ($idNuevoTipoBloqueo == 0 ? "NULL" : $idNuevoTipoBloqueo) . " 
                                  , ID_ORDEN_TRABAJO_MOVIMIENTO = " . ($arrClave[4] == 0 ? "NULL" : $arrClave[4]) . " 
                                  , ID_INCIDENCIA_CALIDAD = " . ($arrClave[5] == 0 ? "NULL" : $arrClave[5]);
                    $bd->ExecSQL($sqlInsert);

                    //GUARDO EL ID MATERIAL UBICACION DESTINO
                    $idMatUbiDestino = $bd->IdAsignado();
                else:
                    //GUARDO EL ID MATERIAL UBICACION DESTINO
                    $idMatUbiDestino = $rowMatUbiDestino->ID_MATERIAL_UBICACION;
                endif;

                //INCREMENTO MATERIAL_UBICACION DESTINO
                $sqlUpdate = "UPDATE MATERIAL_UBICACION SET
                              STOCK_TOTAL = STOCK_TOTAL + $cantidad
                              , STOCK_OK = STOCK_OK + " . ($idNuevoTipoBloqueo == 0 ? $cantidad : 0) . "
                              , STOCK_BLOQUEADO = STOCK_BLOQUEADO + " . ($idNuevoTipoBloqueo == 0 ? 0 : $cantidad) . "
                              WHERE ID_MATERIAL_UBICACION = $idMatUbiDestino";
                $bd->ExecSQL($sqlUpdate);

            endforeach;
        endif;
        //FIN RECORRER MATERIALES UBICACION PARA HACER EL CAMBIO DE ESTADO CORRESPONDIENTE

        //BUSCAMOS LA DEMANDA
        $rowDemanda = $bd->VerReg("DEMANDA", "ID_DEMANDA", $rowReserva->ID_DEMANDA);

        //ACTUALIZAMOS COLA Y RESERVA
        if ($actualizarDemanda == 1):
            //QUITAMOS LA CANTIDAD DE LA COLA
            $this->poner_cantidad_cola($rowDemanda->ID_DEMANDA, $cantidadAnular, 'Anular Reserva Cantidad: ' . $cantidadAnular . ' en la Reserva ' . $rowReserva->ID_RESERVA);

            //ACTUALIZAMOS LA DEMANDA
            $sqlUpdate = "UPDATE DEMANDA SET 
                                 CANTIDAD_PENDIENTE_RESERVAR = CANTIDAD_PENDIENTE_RESERVAR + $cantidadAnular
                               , FECHA_CIERRE_RESERVA = NULL
                               , ESTADO = 'Activa'
                        WHERE ID_DEMANDA = $rowDemanda->ID_DEMANDA";
            $bd->ExecSQL($sqlUpdate);

            //BUSCO LA DEMANDA ACTUALIZADA
            $rowDemandaActualizada = $bd->VerReg("DEMANDA", "ID_DEMANDA", $rowDemanda->ID_DEMANDA);

            //SI LA CANTIDAD PENDIENTE RESERVAR VA A SER NEGATIVA DEVUELVO UN ERROR
            if ($rowDemandaActualizada->CANTIDAD_PENDIENTE_RESERVAR < (EPSILON_SISTEMA * -1)):
                $arr_respuesta['error'] = $auxiliar->traduce("La cantidad pendiente de reservar en la demanda es negativa", $administrador->ID_IDIOMA) . ". " . $auxiliar->traduce("Demanda", $administrador->ID_IDIOMA) . ": " . $rowDemandaActualizada->ID_DEMANDA;

                return $arr_respuesta;
            endif;

            //LOG MOVIMIENTOS
            $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Demanda", $rowDemanda->ID_DEMANDA, 'Anular Reserva de Material:' . $rowReserva->ID_RESERVA, 'DEMANDA', $rowDemanda, $rowDemandaActualizada);
        endif;

        //SI LA DEMANDA ES DE TIPO OT, ACTUALIZAMOS LAS TRANSFERENCIAS DE PENDIENTES SI LAS HAY
        if ($rowDemanda->TIPO_DEMANDA == 'OT'):
            $this->actualizar_transferencias_pendientes($rowDemanda->ID_DEMANDA);
        endif;

        return $arr_respuesta;
    }


    /**
     * @param $idDemanda
     * @param $cantidad
     * CANCELA LA RESERVA. USADO PARA PASAR EL MATERIAL A PLNA
     */
    function cancelar_reserva($idDemanda, $cantidad, $observacionesLog = "")
    {
        global $bd;
        global $html;
        global $auxiliar;
        global $administrador;

        //ARRAY A DEVOLVER
        $arr_respuesta = array();

        //ARRAY PARA ALMACENAR LOS CAMBIOS DE ESTADO DE MATERIAL RESERVADO A REALIZAR
        $arrCambioEstadoReservado = array();

        $cantidadPdteLiberar = $cantidad;

        //SE OBTIENE LA RESERVA
        $rowReserva = $this->get_reserva_demanda($idDemanda);
        if ($rowReserva != false):

            //BUSCAMOS LAS LINEAS EN ESTADO RESERVADA Y LIBERAMOS EL STOCK
            $resultLineas = $this->get_lineas_reservas_demanda($idDemanda, 'Reservada');
            if (($resultLineas != false) && ($bd->NumRegs($resultLineas) > 0)):
                //BUSCO EL TIPO DE BLOQUEO PLANIFICADO
                $rowTipoBloqueoPlanificado = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "SP");

                //BUSCO EL TIPO DE BLOQUEO RESERVADO
                $rowTipoBloqueoReservado = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "RV");

                //BUSCO EL TIPO DE BLOQUEO RESERVADO PLANIFICADO
                $rowTipoBloqueoReservadoPlanificado = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "RVP");

                //SEGUN EL ORIGEN DE LOS DATOS
                while (($rowLinea = $bd->SigReg($resultLineas)) && ($cantidadPdteLiberar > EPSILON_SISTEMA)):

                    //BUSCAMOS SI PARA LOS DATOS DE ESA LINEA YA EXISTE UNA Cancelada
                    $GLOBALS["NotificaErrorPorEmail"] = "No";
                    $clausulaWhere                    = "ID_RESERVA = $rowLinea->ID_RESERVA AND BAJA = 0 AND ESTADO_LINEA = 'Cancelada' AND ID_MATERIAL = $rowLinea->ID_MATERIAL AND ID_UBICACION = " . $rowLinea->ID_UBICACION . " AND ID_MATERIAL_FISICO " . ($rowLinea->ID_MATERIAL_FISICO == NULL ? 'IS NULL' : " = " . $rowLinea->ID_MATERIAL_FISICO) . " AND ID_TIPO_BLOQUEO " . ($rowLinea->ID_TIPO_BLOQUEO == NULL ? 'IS NULL' : " = $rowLinea->ID_TIPO_BLOQUEO") . " AND ID_ORDEN_TRABAJO_MOVIMIENTO " . ($rowLinea->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? 'IS NULL' : " = " . $rowLinea->ID_ORDEN_TRABAJO_MOVIMIENTO) . " AND ID_INCIDENCIA_CALIDAD " . ($rowLinea->ID_INCIDENCIA_CALIDAD == NULL ? 'IS NULL' : " = " . $rowLinea->ID_INCIDENCIA_CALIDAD);
                    $rowReservaLineaFinalizada        = $bd->VerRegRest("RESERVA_LINEA", $clausulaWhere, "No");
                    unset($GLOBALS["NotificaErrorPorEmail"]);
                    if ($rowReservaLineaFinalizada != false):

                        //SI NECESITAMOS RESERVAR TODA LA LINEA, LA DAMOS DE BAJA. SI ES MAYOR QUE EPSILON ES MAYOR LA CANTIDAD PENDIENTE DE LIBERAR Y SI ESTA ENTRE EPSILON Y -EPSILON ES QUE LAS CANTIDADES PENDIENTE LIBERAR Y DE LA LINEA SON IGUALES
                        if ((($cantidadPdteLiberar - $rowLinea->CANTIDAD) > EPSILON_SISTEMA) || ((($cantidadPdteLiberar - $rowLinea->CANTIDAD) < EPSILON_SISTEMA) && (($cantidadPdteLiberar - $rowLinea->CANTIDAD) > -EPSILON_SISTEMA))):
                            $cantidadPdteLiberar   = $cantidadPdteLiberar - $rowLinea->CANTIDAD;
                            $cantidadLiberadaLinea = $rowLinea->CANTIDAD;

                            //DAMOS DE BAJA LA LINEA RESERVADA
                            $sqlUpdate = "UPDATE RESERVA_LINEA SET BAJA = 1 WHERE ID_RESERVA_LINEA = " . $rowLinea->ID_RESERVA_LINEA;
                            $bd->ExecSQL($sqlUpdate);
                        else:
                            //SI SOBRA CANTIDAD DE LA LINEA, SE LO QUITAMOS
                            $cantidadLiberadaLinea = $rowLinea->CANTIDAD - $cantidadPdteLiberar;
                            $cantidadPdteLiberar   = 0;
                            $sqlUpdate             = "UPDATE RESERVA_LINEA SET CANTIDAD = CANTIDAD - $cantidadLiberadaLinea WHERE ID_RESERVA_LINEA = " . $rowLinea->ID_RESERVA_LINEA;
                            $bd->ExecSQL($sqlUpdate);
                        endif;

                        //SI EXISTE, AUMENTAMOS LA CANTIDAD DE LA CANCELADA
                        $sqlUpdate = "UPDATE RESERVA_LINEA SET 
                                        CANTIDAD = CANTIDAD + " . $cantidadLiberadaLinea . " 
                                        WHERE ID_RESERVA_LINEA = " . $rowReservaLineaFinalizada->ID_RESERVA_LINEA;
                        $bd->ExecSQL($sqlUpdate);

                    else:

                        //SI NECESITAMOS RESERVAR TODA LA LINEA, LA CAMBIAMOS A CANCELADA. SI ES MAYOR QUE EPSILON ES MAYOR LA CANTIDAD PENDIENTE DE LIBERAR Y SI ESTA ENTRE EPSILON Y -EPSILON ES QUE LAS CANTIDADES PENDIENTE LIBERAR Y DE LA LINEA SON IGUALES
                        if ((($cantidadPdteLiberar - $rowLinea->CANTIDAD) > EPSILON_SISTEMA) || ((($cantidadPdteLiberar - $rowLinea->CANTIDAD) < EPSILON_SISTEMA) && (($cantidadPdteLiberar - $rowLinea->CANTIDAD) > -EPSILON_SISTEMA))):
                            $cantidadPdteLiberar   = $cantidadPdteLiberar - $rowLinea->CANTIDAD;
                            $cantidadLiberadaLinea = $rowLinea->CANTIDAD;

                            //SI NO EXISTE, CAMBIAMOS EL ESTADO DE LA ACTUAL
                            $sqlUpdate = "UPDATE RESERVA_LINEA SET 
                                            ESTADO_LINEA = 'Cancelada'
                                            WHERE ID_RESERVA_LINEA = " . $rowLinea->ID_RESERVA_LINEA;
                            $bd->ExecSQL($sqlUpdate);
                        else:
                            //SI SOBRA CANTIDAD DE LA LINEA, SE LO QUITAMOS
                            $cantidadLiberadaLinea = $rowLinea->CANTIDAD - $cantidadPdteLiberar;
                            $cantidadPdteLiberar   = 0;
                            $sqlUpdate             = "UPDATE RESERVA_LINEA SET CANTIDAD = CANTIDAD - $cantidadLiberadaLinea WHERE ID_RESERVA_LINEA = " . $rowLinea->ID_RESERVA_LINEA;
                            $bd->ExecSQL($sqlUpdate);

                            //GENERAMOS UNA LINEA EN ESTADO CANCELADA
                            $sqlInsert = "INSERT INTO RESERVA_LINEA SET
                                          ID_RESERVA = $rowLinea->ID_RESERVA  
                                          , FECHA_CREACION_LINEA = '" . date("Y-m-d H:i:s") . "' 
                                          , ESTADO_LINEA = 'Cancelada' 
                                          , CANTIDAD = " . $cantidadLiberadaLinea . "
                                          , ID_MATERIAL = " . $rowLinea->ID_MATERIAL . "
                                          , ID_UBICACION = " . $rowLinea->ID_UBICACION . " 
                                          , ID_MATERIAL_FISICO = " . ($rowLinea->ID_MATERIAL_FISICO == NULL ? "NULL" : $rowLinea->ID_MATERIAL_FISICO) . "
                                          , ID_TIPO_BLOQUEO = " . ($rowLinea->ID_TIPO_BLOQUEO == NULL ? "NULL" : $rowLinea->ID_TIPO_BLOQUEO) . " 
                                          , ID_ORDEN_TRABAJO_MOVIMIENTO = " . ($rowLinea->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? "NULL" : $rowLinea->ID_ORDEN_TRABAJO_MOVIMIENTO) . " 
                                          , ID_INCIDENCIA_CALIDAD = " . ($rowLinea->ID_INCIDENCIA_CALIDAD == NULL ? "NULL" : $rowLinea->ID_INCIDENCIA_CALIDAD);
                            $bd->ExecSQL($sqlInsert);
                        endif;
                    endif;

                    //LOG MOVIMIENTOS
                    $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Reserva", $rowLinea->ID_RESERVA, 'Cancelar Cantidad:' . $cantidadLiberadaLinea . " . Reserva Linea: " . $rowLinea->ID_RESERVA_LINEA . " " . $observacionesLog);

                    //CREO LA CLAVE DEL ARRAY
                    $clave = $rowLinea->ID_MATERIAL . "_" . $rowLinea->ID_UBICACION . "_" . ($rowLinea->ID_MATERIAL_FISICO == NULL ? 0 : $rowLinea->ID_MATERIAL_FISICO) . "_" . ($rowLinea->ID_TIPO_BLOQUEO == NULL ? 0 : $rowLinea->ID_TIPO_BLOQUEO) . "_" . ($rowLinea->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? 0 : $rowLinea->ID_ORDEN_TRABAJO_MOVIMIENTO) . "_" . ($rowLinea->ID_INCIDENCIA_CALIDAD == NULL ? 0 : $rowLinea->ID_INCIDENCIA_CALIDAD);

                    //AÑADO LA LINEA AL ARRAY DE CAMBIOS DE ESTADO A RESERVADO A REALIZAR
                    if (!isset($arrCambioEstadoReservado[$clave])):
                        $arrCambioEstadoReservado[$clave] = $cantidadLiberadaLinea;
                    else:
                        $arrCambioEstadoReservado[$clave] += $cantidadLiberadaLinea;
                    endif;

                endwhile;//FIN RECORRER LINEAS
            endif;
        else:
            $arr_respuesta['error'] = $auxiliar->traduce("No se encuentra la Reserva para la Demanda", $administrador->ID_IDIOMA) . " $idDemanda";

            return $arr_respuesta;
        endif;//FIN SI EXISTE LA RESERVA

        if ($cantidadPdteLiberar > EPSILON_SISTEMA):
            $arr_respuesta['error'] = $auxiliar->traduce("No se ha podido anular toda la cantidad Reservada", $administrador->ID_IDIOMA);

            return $arr_respuesta;
        endif;

        //RECORRER MATERIALES UBICACION PARA HACER EL CAMBIO DE ESTADO CORRESPONDIENTE
        if (count( (array)$arrCambioEstadoReservado) > 0):
            foreach ($arrCambioEstadoReservado as $clave => $cantidad):
                //EXTRAIGO LOS VALORES DE LA CLAVE
                $arrClave = explode("_", (string)$clave); //Material/Ubicacion/Material Fisico/Tipo Bloqueo/Orden Trabajo Movimiento/Incidencia Calidad

                //Tipo Bloqueo
                if ($arrClave[3] == $rowTipoBloqueoReservado->ID_TIPO_BLOQUEO):
                    $idNuevoTipoBloqueo = 0;
                elseif ($arrClave[3] == $rowTipoBloqueoReservadoPlanificado->ID_TIPO_BLOQUEO):
                    $idNuevoTipoBloqueo = $rowTipoBloqueoPlanificado->ID_TIPO_BLOQUEO;
                else:
                    continue;
                endif;

                //BUSCAMOS EL MATERIAL UBICACION ORIGEN PARA COMPROBAR SI HAY SUFICIENTE STOCK
                $GLOBALS["NotificaErrorPorEmail"] = "No";
                $clausulaWhere                    = "ID_MATERIAL = $arrClave[0] AND ID_UBICACION = $arrClave[1] AND ID_MATERIAL_FISICO " . ($arrClave[2] == 0 ? "IS NULL" : " = $arrClave[2]") . " AND ID_TIPO_BLOQUEO " . ($arrClave[3] == 0 ? "IS NULL" : " = $arrClave[3]") . " AND ID_ORDEN_TRABAJO_MOVIMIENTO " . ($arrClave[4] == 0 ? "IS NULL" : " = $arrClave[4]") . " AND ID_INCIDENCIA_CALIDAD " . ($arrClave[5] == 0 ? "IS NULL" : " = $arrClave[5]");
                $rowMatUbiOrigen                  = $bd->VerRegRest("MATERIAL_UBICACION", $clausulaWhere, "No");
                if (($rowMatUbiOrigen == false) || (($cantidad - $rowMatUbiOrigen->STOCK_TOTAL) > EPSILON_SISTEMA)):
                    $arr_respuesta['error'] = $auxiliar->traduce("No se ha podido anular la Reserva porque el Stock ya no se encuentra Reservado", $administrador->ID_IDIOMA) . "<br>";

                    //PREPARAMOS EL ERROR
                    $rowUbi  = $bd->VerReg("UBICACION", "ID_UBICACION", $arrClave[1]);
                    $textoMF = "-";
                    if ($arrClave[2] != 0):
                        $rowMF   = $bd->VerReg("MATERIAL_FISICO", "ID_MATERIAL_FISICO", $arrClave[2]);
                        $textoMF = $rowMF->NUMERO_SERIE_LOTE;
                    endif;
                    $arr_respuesta['error'] .= $auxiliar->traduce("Cantidad", $administrador->ID_IDIOMA) . ": " . $rowMatUbiOrigen->STOCK_TOTAL . ", " . $auxiliar->traduce("Ubicacion", $administrador->ID_IDIOMA) . ": " . $rowUbi->UBICACION . ", " . $auxiliar->traduce("S / L", $administrador->ID_IDIOMA) . ": " . $textoMF . "<br>";

                    return $arr_respuesta;
                endif;

                //CREO EL CAMBIO DE ESTADO
                $sqlInsert = "INSERT INTO CAMBIO_ESTADO SET
                              FECHA = '" . date("Y-m-d H:i:s") . "'
                              , TIPO_CAMBIO_ESTADO = 'AnulacionReservaDemanda'
                              , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                              , ID_MATERIAL = " . $arrClave[0] . " 
                              , ID_UBICACION = " . $arrClave[1] . " 
                              , CANTIDAD = $cantidad 
                              , ID_TIPO_BLOQUEO_INICIAL = " . ($arrClave[3] == 0 ? "NULL" : $arrClave[3]) . " 
                              , ID_TIPO_BLOQUEO_FINAL = " . ($idNuevoTipoBloqueo == 0 ? "NULL" : $idNuevoTipoBloqueo) . " 
                              , ID_MATERIAL_FISICO = " . ($arrClave[2] == 0 ? "NULL" : $arrClave[2]) . " 
                              , ID_ORDEN_TRABAJO_MOVIMIENTO = " . ($arrClave[4] == 0 ? "NULL" : $arrClave[4]) . " 
                              , ID_INCIDENCIA_CALIDAD = " . ($arrClave[5] == 0 ? "NULL" : $arrClave[5]);
                $bd->ExecSQL($sqlInsert);

                //DECREMENTO MATERIAL_UBICACION ORIGEN
                $sqlUpdate = "UPDATE MATERIAL_UBICACION SET
                              STOCK_TOTAL = STOCK_TOTAL - $cantidad 
                              , STOCK_OK = STOCK_OK - " . ($arrClave[3] == 0 ? $cantidad : 0) . "
                              , STOCK_BLOQUEADO = STOCK_BLOQUEADO - " . ($arrClave[3] == 0 ? 0 : $cantidad) . "
                              WHERE ID_MATERIAL_UBICACION = " . $rowMatUbiOrigen->ID_MATERIAL_UBICACION;
                $bd->ExecSQL($sqlUpdate);

                //BUSCO MATERIAL_UBICACION DESTINO
                $GLOBALS["NotificaErrorPorEmail"] = "No";
                $clausulaWhere                    = "ID_MATERIAL = $arrClave[0] AND ID_UBICACION = $arrClave[1] AND ID_MATERIAL_FISICO " . ($arrClave[2] == 0 ? "IS NULL" : " = $arrClave[2]") . " AND ID_TIPO_BLOQUEO " . ($idNuevoTipoBloqueo == 0 ? "IS NULL" : " = $idNuevoTipoBloqueo") . " AND ID_ORDEN_TRABAJO_MOVIMIENTO " . ($arrClave[4] == 0 ? "IS NULL" : " = $arrClave[4]") . " AND ID_INCIDENCIA_CALIDAD " . ($arrClave[5] == 0 ? "IS NULL" : " = $arrClave[5]");
                $rowMatUbiDestino                 = $bd->VerRegRest("MATERIAL_UBICACION", $clausulaWhere, "No");
                if ($rowMatUbiDestino == false):
                    //CREO MATERIAL UBICACION DESTINO
                    $sqlInsert = "INSERT INTO MATERIAL_UBICACION SET
                                  ID_MATERIAL = $arrClave[0] 
                                  , ID_UBICACION = $arrClave[1]
                                  , ID_MATERIAL_FISICO = " . ($arrClave[2] == 0 ? "NULL" : $arrClave[2]) . " 
                                  , ID_TIPO_BLOQUEO = " . ($idNuevoTipoBloqueo == 0 ? "NULL" : $idNuevoTipoBloqueo) . " 
                                  , ID_ORDEN_TRABAJO_MOVIMIENTO = " . ($arrClave[4] == 0 ? "NULL" : $arrClave[4]) . " 
                                  , ID_INCIDENCIA_CALIDAD = " . ($arrClave[5] == 0 ? "NULL" : $arrClave[5]);
                    $bd->ExecSQL($sqlInsert);

                    //GUARDO EL ID MATERIAL UBICACION DESTINO
                    $idMatUbiDestino = $bd->IdAsignado();
                else:
                    //GUARDO EL ID MATERIAL UBICACION DESTINO
                    $idMatUbiDestino = $rowMatUbiDestino->ID_MATERIAL_UBICACION;
                endif;

                //INCREMENTO MATERIAL_UBICACION DESTINO
                $sqlUpdate = "UPDATE MATERIAL_UBICACION SET
                              STOCK_TOTAL = STOCK_TOTAL + $cantidad
                              , STOCK_OK = STOCK_OK + " . ($idNuevoTipoBloqueo == 0 ? $cantidad : 0) . "
                              , STOCK_BLOQUEADO = STOCK_BLOQUEADO + " . ($idNuevoTipoBloqueo == 0 ? 0 : $cantidad) . "
                              WHERE ID_MATERIAL_UBICACION = $idMatUbiDestino";
                $bd->ExecSQL($sqlUpdate);
            endforeach;
        endif;
        //FIN RECORRER MATERIALES UBICACION PARA HACER EL CAMBIO DE ESTADO CORRESPONDIENTE

        //BUSCAMOS LA DEMANDA
        $rowDemanda = $bd->VerReg("DEMANDA", "ID_DEMANDA", $idDemanda);

        //SI LA DEMANDA ES DE TIPO OT, ACTUALIZAMOS LAS TRANSFERENCIAS DE PENDIENTES SI LAS HAY
        if ($rowDemanda->TIPO_DEMANDA == 'OT'):
            $this->actualizar_transferencias_pendientes($rowDemanda->ID_DEMANDA);
        endif;

        return $arr_respuesta;
    }

    /**
     * @param $idDemanda
     * @param $idUbicacion
     * @param $observacionesLog
     * DESHACE LA CANCELACION DE LA RESERVA. BUSCA EL MATERIAL EN P/PLNA Y SI SIGUE EN EL MISMO LUGAR REACTIVA LA RESERVA
     */
    function deshacer_cancelar_reserva($idDemanda, $idUbicacion, $observacionesLog = "")
    {
        global $bd;
        global $html;
        global $auxiliar;
        global $administrador;

        //ARRAY A DEVOLVER
        $arr_respuesta = array();

        //OBTENEMOS LA CANTIDAD CANCELADA
        $cantidadCancelada = $this->get_cantidad_reserva_demanda($idDemanda, 'Cancelada');

        //SE OBTIENE LA RESERVA
        if ($cantidadCancelada > EPSILON_SISTEMA):
            //BUSCAMOS LAS LINEAS EN ESTADO CANCELADA Y VOLVEMOS A RESERVAR EL STOCK SI ES POSIBLE
            $resultLineas = $this->get_lineas_reservas_demanda($idDemanda, 'Cancelada');
            if (($resultLineas != false) && ($bd->NumRegs($resultLineas) > 0)):
                //BUSCO EL TIPO DE BLOQUEO PLANIFICADO
                $rowTipoBloqueoPlanificado = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "SP");

                //BUSCO EL TIPO DE BLOQUEO RESERVADO
                $rowTipoBloqueoReservado = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "RV");

                //BUSCO EL TIPO DE BLOQUEO RESERVADO PLANIFICADO
                $rowTipoBloqueoReservadoPlanificado = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "RVP");

                //SEGUN EL ORIGEN DE LOS DATOS
                while ($rowLinea = $bd->SigReg($resultLineas)):

                    //CANTIDAD A LIBERAR
                    $cantidad = $rowLinea->CANTIDAD;

                    //Tipo Bloqueo
                    if ($rowLinea->ID_TIPO_BLOQUEO == $rowTipoBloqueoReservado->ID_TIPO_BLOQUEO):
                        $idNuevoTipoBloqueo = 0;
                    elseif ($rowLinea->ID_TIPO_BLOQUEO == $rowTipoBloqueoReservadoPlanificado->ID_TIPO_BLOQUEO):
                        $idNuevoTipoBloqueo = $rowTipoBloqueoPlanificado->ID_TIPO_BLOQUEO;
                    else:
                        continue;
                    endif;

                    //BUSCAMOS SI EL STOCK SIGUE SIENDO POSIBLE RESERVARLO
                    //BUSCAMOS EL MATERIAL UBICACION ORIGEN PARA COMPROBAR SI HAY SUFICIENTE STOCK
                    $clausulaWhere = "ID_MATERIAL = $rowLinea->ID_MATERIAL AND ID_UBICACION = $rowLinea->ID_UBICACION AND ID_MATERIAL_FISICO " . ($rowLinea->ID_MATERIAL_FISICO == NULL ? "IS NULL" : " = $rowLinea->ID_MATERIAL_FISICO") . " AND ID_TIPO_BLOQUEO " . ($idNuevoTipoBloqueo == 0 ? "IS NULL" : " = $idNuevoTipoBloqueo") . " AND ID_ORDEN_TRABAJO_MOVIMIENTO " . ($rowLinea->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? "IS NULL" : " = $rowLinea->ID_ORDEN_TRABAJO_MOVIMIENTO") . " AND ID_INCIDENCIA_CALIDAD " . ($rowLinea->ID_INCIDENCIA_CALIDAD == NULL ? "IS NULL" : " = $rowLinea->ID_INCIDENCIA_CALIDAD");
                    $rowMatUbiOrigen = $bd->VerRegRest("MATERIAL_UBICACION", $clausulaWhere, "No");
                    if (($rowMatUbiOrigen == false) || (($cantidad - $rowMatUbiOrigen->STOCK_TOTAL) > EPSILON_SISTEMA)):
                        $arr_respuesta['error'] = $auxiliar->traduce("No se ha podido reactivar la Reserva el stock ya no se encuentra disponible", $administrador->ID_IDIOMA) . "<br>";

                        //PREPARAMOS EL ERROR
                        $rowUbi  = $bd->VerReg("UBICACION", "ID_UBICACION", $rowLinea->ID_UBICACION);
                        $textoMF = "-";
                        if ($rowLinea->ID_MATERIAL_FISICO != ""):
                            $rowMF   = $bd->VerReg("MATERIAL_FISICO", "ID_MATERIAL_FISICO", $rowLinea->ID_MATERIAL_FISICO);
                            $textoMF = $rowMF->NUMERO_SERIE_LOTE;
                        endif;
                        $arr_respuesta['error'] .= $auxiliar->traduce("Cantidad", $administrador->ID_IDIOMA) . " $cantidad , " . $auxiliar->traduce("Ubicacion", $administrador->ID_IDIOMA) . " $rowUbi->UBICACION , " . $auxiliar->traduce("S / L", $administrador->ID_IDIOMA) . " $textoMF. <br>";

                        continue;
                    endif;

                    //CREO EL CAMBIO DE ESTADO
                    $sqlInsert = "INSERT INTO CAMBIO_ESTADO SET
                                  FECHA = '" . date("Y-m-d H:i:s") . "'
                                  , TIPO_CAMBIO_ESTADO = 'ReservaDemanda'
                                  , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                  , ID_MATERIAL = " . $rowLinea->ID_MATERIAL . " 
                                  , ID_UBICACION = " . $rowLinea->ID_UBICACION . " 
                                  , CANTIDAD = $cantidad 
                                  , ID_TIPO_BLOQUEO_INICIAL = " . ($idNuevoTipoBloqueo == 0 ? "NULL" : $idNuevoTipoBloqueo) . " 
                                  , ID_TIPO_BLOQUEO_FINAL = " . ($rowLinea->ID_TIPO_BLOQUEO == 0 ? "NULL" : $rowLinea->ID_TIPO_BLOQUEO) . " 
                                  , ID_MATERIAL_FISICO = " . ($rowLinea->ID_MATERIAL_FISICO == 0 ? "NULL" : $rowLinea->ID_MATERIAL_FISICO) . " 
                                  , ID_ORDEN_TRABAJO_MOVIMIENTO = " . ($rowLinea->ID_ORDEN_TRABAJO_MOVIMIENTO == 0 ? "NULL" : $rowLinea->ID_ORDEN_TRABAJO_MOVIMIENTO) . " 
                                  , ID_INCIDENCIA_CALIDAD = " . ($rowLinea->ID_INCIDENCIA_CALIDAD == 0 ? "NULL" : $rowLinea->ID_INCIDENCIA_CALIDAD);
                    $bd->ExecSQL($sqlInsert);

                    //DECREMENTO MATERIAL_UBICACION ORIGEN
                    $sqlUpdate = "UPDATE MATERIAL_UBICACION SET
                                  STOCK_TOTAL = STOCK_TOTAL - $cantidad 
                                  , STOCK_OK = STOCK_OK - " . ($idNuevoTipoBloqueo == 0 ? $cantidad : 0) . "
                                  , STOCK_BLOQUEADO = STOCK_BLOQUEADO - " . ($idNuevoTipoBloqueo == 0 ? 0 : $cantidad) . "
                                  WHERE ID_MATERIAL_UBICACION = " . $rowMatUbiOrigen->ID_MATERIAL_UBICACION;
                    $bd->ExecSQL($sqlUpdate);

                    //SI LA UBICACION CAMBIA, HACEMOS UNA TRANSFERENCIA
                    if (($idUbicacion != 0) && ($idUbicacion != $rowLinea->ID_UBICACION)):

                        //BUSCO LA UBICACION DE ORIGEN
                        $rowUbicacionOrigen = $bd->VerReg("UBICACION", "ID_UBICACION", $rowLinea->ID_UBICACION);

                        //BUSCO EL ALMACEN DE ORIGEN
                        $rowAlmacenOrigen = $bd->VerReg("ALMACEN", "ID_ALMACEN", $rowUbicacionOrigen->ID_ALMACEN);

                        //BUSCO LA UBICACION DE DESTINO
                        $rowUbicacionDestino = $bd->VerReg("UBICACION", "ID_UBICACION", $idUbicacion);

                        //BUSCO EL ALMACEN DE DESTINO
                        $rowAlmacenDestino = $bd->VerReg("ALMACEN", "ID_ALMACEN", $rowUbicacionDestino->ID_ALMACEN);

                        //COMPRUEBO QUE NO SE TRATE DE STOCK COMPARTIDO, SI ES EL CASO, LA TRANSFERENCIA NO APLICA
                        if (($rowAlmacenOrigen->ID_CENTRO_FISICO == $rowAlmacenDestino->ID_CENTRO_FISICO) && ($rowAlmacenDestino->STOCK_COMPARTIDO == 1) && ($rowAlmacenDestino->TIPO_STOCK == 'SPV')):
                            //NO HACEMOS TRANSFERENCIA
                        else:
                            //GENERO LA TRANSFERENCIA DE DESUBICACION
                            $sqlInsert = "INSERT INTO MOVIMIENTO_TRANSFERENCIA SET
                                            ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                            , ID_MATERIAL = $rowLinea->ID_MATERIAL
                                            , ID_MATERIAL_FISICO = " . ($rowLinea->ID_MATERIAL_FISICO == NULL ? 'NULL' : $rowLinea->ID_MATERIAL_FISICO) . "
                                            , ID_UBICACION_ORIGEN = $rowLinea->ID_UBICACION
                                            , ID_UBICACION_DESTINO = $idUbicacion
                                            , ID_ORDEN_TRABAJO_MOVIMIENTO = " . ($rowLinea->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? 'NULL' : $rowLinea->ID_ORDEN_TRABAJO_MOVIMIENTO) . "
                                            , ID_INCIDENCIA_CALIDAD = " . ($rowLinea->ID_INCIDENCIA_CALIDAD == NULL ? 'NULL' : $rowLinea->ID_INCIDENCIA_CALIDAD) . "
                                            , CANTIDAD = $cantidad
                                            , FECHA = '" . date("Y-m-d H:i:s") . "'
                                            , TIPO = 'Automatico'
                                            , STOCK_OK = " . ($rowLinea->ID_TIPO_BLOQUEO == NULL ? $cantidad : 0) . "
                                            , STOCK_BLOQUEADO = " . ($rowLinea->ID_TIPO_BLOQUEO == NULL ? 0 : $cantidad) . "
                                            , ID_TIPO_BLOQUEO = " . ($rowLinea->ID_TIPO_BLOQUEO == NULL ? 'NULL' : $rowLinea->ID_TIPO_BLOQUEO);//echo($sqlInsert."<hr>");
                            $bd->ExecSQL($sqlInsert);


                            //BUSCO MATERIAL_UBICACION DESTINO DIRECTAMENTE
                            $clausulaWhere = "ID_MATERIAL = $rowLinea->ID_MATERIAL AND ID_UBICACION = $idUbicacion AND ID_MATERIAL_FISICO " . ($rowLinea->ID_MATERIAL_FISICO == NULL ? "IS NULL" : " = $rowLinea->ID_MATERIAL_FISICO") . " AND ID_TIPO_BLOQUEO " . ($rowLinea->ID_TIPO_BLOQUEO == NULL ? "IS NULL" : " = $rowLinea->ID_TIPO_BLOQUEO") . " AND ID_ORDEN_TRABAJO_MOVIMIENTO " . ($rowLinea->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? "IS NULL" : " = $rowLinea->ID_ORDEN_TRABAJO_MOVIMIENTO") . " AND ID_INCIDENCIA_CALIDAD " . ($rowLinea->ID_INCIDENCIA_CALIDAD == NULL ? "IS NULL" : " = $rowLinea->ID_INCIDENCIA_CALIDAD");
                            $rowMatUbiDestino = $bd->VerRegRest("MATERIAL_UBICACION", $clausulaWhere, "No");
                            if ($rowMatUbiDestino == false):
                                //CREO MATERIAL UBICACION DESTINO
                                $sqlInsert = "INSERT INTO MATERIAL_UBICACION SET
                                              ID_MATERIAL = $rowLinea->ID_MATERIAL 
                                              , ID_UBICACION = $idUbicacion
                                              , ID_MATERIAL_FISICO = " . ($rowLinea->ID_MATERIAL_FISICO == 0 ? "NULL" : $rowLinea->ID_MATERIAL_FISICO) . " 
                                              , ID_TIPO_BLOQUEO = " . ($rowLinea->ID_TIPO_BLOQUEO == 0 ? "NULL" : $rowLinea->ID_TIPO_BLOQUEO) . " 
                                              , ID_ORDEN_TRABAJO_MOVIMIENTO = " . ($rowLinea->ID_ORDEN_TRABAJO_MOVIMIENTO == 0 ? "NULL" : $rowLinea->ID_ORDEN_TRABAJO_MOVIMIENTO) . " 
                                              , ID_INCIDENCIA_CALIDAD = " . ($rowLinea->ID_INCIDENCIA_CALIDAD == 0 ? "NULL" : $rowLinea->ID_INCIDENCIA_CALIDAD);
                                $bd->ExecSQL($sqlInsert);

                                //GUARDO EL ID MATERIAL UBICACION DESTINO
                                $idMatUbiDestino = $bd->IdAsignado();
                            else:
                                //GUARDO EL ID MATERIAL UBICACION DESTINO
                                $idMatUbiDestino = $rowMatUbiDestino->ID_MATERIAL_UBICACION;
                            endif;

                            //INCREMENTO MATERIAL_UBICACION DESTINO
                            $sqlUpdate = "UPDATE MATERIAL_UBICACION SET
                                          STOCK_TOTAL = STOCK_TOTAL + $cantidad
                                          , STOCK_OK = STOCK_OK + " . ($rowLinea->ID_TIPO_BLOQUEO == NULL ? $cantidad : 0) . "
                                          , STOCK_BLOQUEADO = STOCK_BLOQUEADO + " . ($rowLinea->ID_TIPO_BLOQUEO == NULL ? 0 : $cantidad) . "
                                          WHERE ID_MATERIAL_UBICACION = $idMatUbiDestino";
                            $bd->ExecSQL($sqlUpdate);
                        endif;

                    else://NO HAY TRANSFERENCIA, LO DEJAMOS DIRECTAMENTE EN LA UBICACION DE LA LINEA

                        //ACTUALIZO LA UBICACION EN CASO DE NO VENIR PARA SER LA MISMA QUE LA DE LA LINEA
                        $idUbicacion = $rowLinea->ID_UBICACION;

                        //BUSCO MATERIAL_UBICACION DESTINO
                        $clausulaWhere = "ID_MATERIAL = $rowLinea->ID_MATERIAL AND ID_UBICACION = $rowLinea->ID_UBICACION AND ID_MATERIAL_FISICO " . ($rowLinea->ID_MATERIAL_FISICO == NULL ? "IS NULL" : " = $rowLinea->ID_MATERIAL_FISICO") . " AND ID_TIPO_BLOQUEO " . ($rowLinea->ID_TIPO_BLOQUEO == NULL ? "IS NULL" : " = $rowLinea->ID_TIPO_BLOQUEO") . " AND ID_ORDEN_TRABAJO_MOVIMIENTO " . ($rowLinea->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? "IS NULL" : " = $rowLinea->ID_ORDEN_TRABAJO_MOVIMIENTO") . " AND ID_INCIDENCIA_CALIDAD " . ($rowLinea->ID_INCIDENCIA_CALIDAD == NULL ? "IS NULL" : " = $rowLinea->ID_INCIDENCIA_CALIDAD");
                        $rowMatUbiDestino = $bd->VerRegRest("MATERIAL_UBICACION", $clausulaWhere, "No");
                        if ($rowMatUbiDestino == false):
                            //CREO MATERIAL UBICACION DESTINO
                            $sqlInsert = "INSERT INTO MATERIAL_UBICACION SET
                                          ID_MATERIAL = $rowLinea->ID_MATERIAL 
                                          , ID_UBICACION = $rowLinea->ID_UBICACION
                                          , ID_MATERIAL_FISICO = " . ($rowLinea->ID_MATERIAL_FISICO == 0 ? "NULL" : $rowLinea->ID_MATERIAL_FISICO) . " 
                                          , ID_TIPO_BLOQUEO = " . ($rowLinea->ID_TIPO_BLOQUEO == 0 ? "NULL" : $rowLinea->ID_TIPO_BLOQUEO) . " 
                                          , ID_ORDEN_TRABAJO_MOVIMIENTO = " . ($rowLinea->ID_ORDEN_TRABAJO_MOVIMIENTO == 0 ? "NULL" : $rowLinea->ID_ORDEN_TRABAJO_MOVIMIENTO) . " 
                                          , ID_INCIDENCIA_CALIDAD = " . ($rowLinea->ID_INCIDENCIA_CALIDAD == 0 ? "NULL" : $rowLinea->ID_INCIDENCIA_CALIDAD);
                            $bd->ExecSQL($sqlInsert);

                            //GUARDO EL ID MATERIAL UBICACION DESTINO
                            $idMatUbiDestino = $bd->IdAsignado();
                        else:
                            //GUARDO EL ID MATERIAL UBICACION DESTINO
                            $idMatUbiDestino = $rowMatUbiDestino->ID_MATERIAL_UBICACION;
                        endif;

                        //INCREMENTO MATERIAL_UBICACION DESTINO
                        $sqlUpdate = "UPDATE MATERIAL_UBICACION SET
                                      STOCK_TOTAL = STOCK_TOTAL + $cantidad
                                      , STOCK_OK = STOCK_OK + " . ($rowLinea->ID_TIPO_BLOQUEO == NULL ? $cantidad : 0) . "
                                      , STOCK_BLOQUEADO = STOCK_BLOQUEADO + " . ($rowLinea->ID_TIPO_BLOQUEO == NULL ? 0 : $cantidad) . "
                                      WHERE ID_MATERIAL_UBICACION = $idMatUbiDestino";
                        $bd->ExecSQL($sqlUpdate);
                    endif;

                    //BUSCAMOS SI PARA LOS DATOS DE ESA LINEA YA EXISTE UNA Reservada
                    $clausulaWhere = "ID_RESERVA = $rowLinea->ID_RESERVA AND BAJA = 0 AND ESTADO_LINEA = 'Reservada' AND ID_MATERIAL = $rowLinea->ID_MATERIAL AND ID_UBICACION = " . $idUbicacion . " AND ID_MATERIAL_FISICO " . ($rowLinea->ID_MATERIAL_FISICO == NULL ? 'IS NULL' : " = " . $rowLinea->ID_MATERIAL_FISICO) . " AND ID_TIPO_BLOQUEO " . ($rowLinea->ID_TIPO_BLOQUEO == NULL ? 'IS NULL' : " = $rowLinea->ID_TIPO_BLOQUEO") . " AND ID_ORDEN_TRABAJO_MOVIMIENTO " . ($rowLinea->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? 'IS NULL' : " = " . $rowLinea->ID_ORDEN_TRABAJO_MOVIMIENTO) . " AND ID_INCIDENCIA_CALIDAD " . ($rowLinea->ID_INCIDENCIA_CALIDAD == NULL ? 'IS NULL' : " = " . $rowLinea->ID_INCIDENCIA_CALIDAD);
                    $rowReservaLineaReservada = $bd->VerRegRest("RESERVA_LINEA", $clausulaWhere, "No");

                    if ($rowReservaLineaReservada != false):
                        //DAMOS DE BAJA LA LINEA CANCELADA
                        $sqlUpdate = "UPDATE RESERVA_LINEA SET BAJA = 1 WHERE ID_RESERVA_LINEA = " . $rowLinea->ID_RESERVA_LINEA;
                        $bd->ExecSQL($sqlUpdate);

                        //SI EXISTE, AUMENTAMOS LA CANTIDAD DE LA CANCELADA
                        $sqlUpdate = "UPDATE RESERVA_LINEA SET 
                                        CANTIDAD = CANTIDAD + " . $cantidad . " 
                                        WHERE ID_RESERVA_LINEA = " . $rowReservaLineaReservada->ID_RESERVA_LINEA;
                        $bd->ExecSQL($sqlUpdate);
                    else:
                        //SI NO EXISTE, CAMBIAMOS EL ESTADO DE LA ACTUAL Y LA UBICACION
                        $sqlUpdate = "UPDATE RESERVA_LINEA SET 
                                            ESTADO_LINEA = 'Reservada'
                                            , ID_UBICACION = $idUbicacion
                                            WHERE ID_RESERVA_LINEA = " . $rowLinea->ID_RESERVA_LINEA;
                        $bd->ExecSQL($sqlUpdate);
                    endif;

                    //LOG MOVIMIENTOS
                    $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Reserva", $rowLinea->ID_RESERVA, 'Deshacer cancelacion Cantidad:' . $cantidad . " . Reserva Linea: " . $rowLinea->ID_RESERVA_LINEA . " " . $observacionesLog);
                endwhile;//FIN RECORRER LINEAS
            endif;
        endif;//FIN SI EXISTE LA RESERVA

        //BUSCAMOS LA DEMANDA
        $rowDemanda = $bd->VerReg("DEMANDA", "ID_DEMANDA", $idDemanda);

        //SI LA DEMANDA ES DE TIPO OT, ACTUALIZAMOS LAS TRANSFERENCIAS DE PENDIENTES SI LAS HAY
        if ($rowDemanda->TIPO_DEMANDA == 'OT'):
            $this->actualizar_transferencias_pendientes($rowDemanda->ID_DEMANDA);
        endif;

        return $arr_respuesta;
    }

    /**
     * @param $idReservaLinea
     * @param $cantidad_cancelar
     * DESHACE LA LINEA DE LA RESERVA
     */
    function anular_reserva_linea($idReservaLinea, $cantidad_cancelar)
    {
        global $bd;
        global $html;
        global $auxiliar;
        global $administrador;

        //ARRAY A DEVOLVER
        $arr_respuesta = array();

        //BUSCO EL TIPO DE BLOQUEO PLANIFICADO
        $rowTipoBloqueoPlanificado = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "SP");

        //BUSCO EL TIPO DE BLOQUEO RESERVADO PLANIFICADO
        $rowTipoBloqueoReservadoPlanificado = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "RVP");

        //SE OBTIENE LA LINEA DE LA RESERVA
        $rowReservaLin = $bd->VerReg("RESERVA_LINEA", "ID_RESERVA_LINEA", $idReservaLinea);

        //SI NECESITAMOS RESERVAR TODA LA LINEA, LA DAMOS DE BAJA
        if ($cantidad_cancelar >= $rowReservaLin->CANTIDAD):
            $cantidad  = $rowReservaLin->CANTIDAD;
            $sqlUpdate = "UPDATE RESERVA_LINEA SET BAJA = 1 WHERE ID_RESERVA_LINEA = " . $rowReservaLin->ID_RESERVA_LINEA;
            $bd->ExecSQL($sqlUpdate);
        else:
            //SI SOBRA CANTIDAD DE LA LINEA, SE LO QUITAMOS
            $cantidad  = $rowReservaLin->CANTIDAD - $cantidad_cancelar;
            $sqlUpdate = "UPDATE RESERVA_LINEA SET CANTIDAD = $cantidad WHERE ID_RESERVA_LINEA = " . $rowReservaLin->ID_RESERVA_LINEA;
            $bd->ExecSQL($sqlUpdate);
        endif;

        //SI LA CANTIDAD DE LA LINEA ERA MAYOR QUE 0
        if ($rowReservaLin->CANTIDAD > EPSILON_SISTEMA):
            //LOG MOVIMIENTOS
            $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Reserva", $rowReservaLin->ID_RESERVA, 'Quitar Cantidad:' . $cantidad_cancelar . " . Reserva Linea: " . $rowReservaLin->ID_RESERVA_LINEA);

            $idNuevoTipoBloqueo = 0;
            if ($rowReservaLin->ID_TIPO_BLOQUEO == $rowTipoBloqueoReservadoPlanificado->ID_TIPO_BLOQUEO):
                $idNuevoTipoBloqueo = $rowTipoBloqueoPlanificado->ID_TIPO_BLOQUEO;
            endif;

            //BUSCAMOS EL MATERIAL UBICACION ORIGEN PARA COMPROBAR SI HAY SUFICIENTE STOCK
            $GLOBALS["NotificaErrorPorEmail"] = "No";
            $clausulaWhere                    = "ID_MATERIAL = $rowReservaLin->ID_MATERIAL AND ID_UBICACION = $rowReservaLin->ID_UBICACION AND ID_MATERIAL_FISICO " . ($rowReservaLin->ID_MATERIAL_FISICO == 0 ? "IS NULL" : " = $rowReservaLin->ID_MATERIAL_FISICO") . " AND ID_TIPO_BLOQUEO " . ($rowReservaLin->ID_TIPO_BLOQUEO == 0 ? "IS NULL" : " = $rowReservaLin->ID_TIPO_BLOQUEO") . " AND ID_ORDEN_TRABAJO_MOVIMIENTO " . ($rowReservaLin->ID_ORDEN_TRABAJO_MOVIMIENTO == 0 ? "IS NULL" : " = $rowReservaLin->ID_ORDEN_TRABAJO_MOVIMIENTO") . " AND ID_INCIDENCIA_CALIDAD " . ($rowReservaLin->ID_INCIDENCIA_CALIDAD == 0 ? "IS NULL" : " = $rowReservaLin->ID_INCIDENCIA_CALIDAD");
            $rowMatUbiOrigen                  = $bd->VerRegRest("MATERIAL_UBICACION", $clausulaWhere, "No");
            if (($rowMatUbiOrigen == false) || (($cantidad - $rowMatUbiOrigen->STOCK_TOTAL) > EPSILON_SISTEMA)):
                $arr_respuesta['error'] = $auxiliar->traduce("No se ha podido anular la Reserva porque el Stock ya no se encuentra Reservado", $administrador->ID_IDIOMA) . "<br>";

                //PREPARAMOS EL ERROR
                $rowUbi  = $bd->VerReg("UBICACION", "ID_UBICACION", $rowReservaLin->ID_UBICACION);
                $textoMF = "-";
                if ($rowReservaLin->ID_MATERIAL_FISICO != 0):
                    $rowMF   = $bd->VerReg("MATERIAL_FISICO", "ID_MATERIAL_FISICO", $rowReservaLin->ID_MATERIAL_FISICO);
                    $textoMF = $rowMF->NUMERO_SERIE_LOTE;
                endif;
                $arr_respuesta['error'] .= $auxiliar->traduce("Cantidad", $administrador->ID_IDIOMA) . ": " . $rowMatUbiOrigen->STOCK_TOTAL . ", " . $auxiliar->traduce("Ubicacion", $administrador->ID_IDIOMA) . ": " . $rowUbi->UBICACION . ", " . $auxiliar->traduce("S / L", $administrador->ID_IDIOMA) . ": " . $textoMF . "<br>";

                return $arr_respuesta;
            endif;

            //SE CREA EL CAMBIO DE ESTADO
            $sqlInsert = "INSERT INTO CAMBIO_ESTADO SET
                                  FECHA = '" . date("Y-m-d H:i:s") . "'
                                  , TIPO_CAMBIO_ESTADO = 'AnulacionReservaDemanda'
                                  , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                  , ID_MATERIAL = " . $rowReservaLin->ID_MATERIAL . " 
                                  , ID_MATERIAL_FISICO = " . ($rowReservaLin->ID_MATERIAL_FISICO == '' ? "NULL" : $rowReservaLin->ID_MATERIAL_FISICO) . "
                                  , ID_UBICACION = " . $rowReservaLin->ID_UBICACION . " 
                                  , CANTIDAD = $cantidad_cancelar 
                                  , ID_TIPO_BLOQUEO_INICIAL = " . ($rowReservaLin->ID_TIPO_BLOQUEO == 0 ? "NULL" : $rowReservaLin->ID_TIPO_BLOQUEO) . " 
                                  , ID_TIPO_BLOQUEO_FINAL = " . ($idNuevoTipoBloqueo == 0 ? "NULL" : $idNuevoTipoBloqueo) . " 
                                  , ID_ORDEN_TRABAJO_MOVIMIENTO = " . ($rowReservaLin->ID_ORDEN_TRABAJO_MOVIMIENTO == '' ? "NULL" : $rowReservaLin->ID_ORDEN_TRABAJO_MOVIMIENTO) . " 
                                  , ID_INCIDENCIA_CALIDAD = " . ($rowReservaLin->ID_INCIDENCIA_CALIDAD == '' ? "NULL" : $rowReservaLin->ID_INCIDENCIA_CALIDAD);
            $bd->ExecSQL($sqlInsert);

            //DECREMENTO MATERIAL_UBICACION ORIGEN
            $sqlUpdate = "UPDATE MATERIAL_UBICACION SET
                                  STOCK_TOTAL = STOCK_TOTAL - $cantidad_cancelar 
                                  , STOCK_OK = STOCK_OK - " . ($rowReservaLin->ID_TIPO_BLOQUEO == 0 ? $cantidad_cancelar : 0) . "
                                  , STOCK_BLOQUEADO = STOCK_BLOQUEADO - " . ($rowReservaLin->ID_TIPO_BLOQUEO == 0 ? 0 : $cantidad_cancelar) . "
                                  WHERE ID_MATERIAL_UBICACION = " . $rowMatUbiOrigen->ID_MATERIAL_UBICACION;
            $bd->ExecSQL($sqlUpdate);

            //BUSCO MATERIAL_UBICACION DESTINO
            $GLOBALS["NotificaErrorPorEmail"] = "No";
            $clausulaWhere                    = "ID_MATERIAL = $rowReservaLin->ID_MATERIAL AND ID_UBICACION = $rowReservaLin->ID_UBICACION AND ID_MATERIAL_FISICO " . ($rowReservaLin->ID_MATERIAL_FISICO == 0 ? "IS NULL" : " = $rowReservaLin->ID_MATERIAL_FISICO") . " AND ID_TIPO_BLOQUEO " . ($idNuevoTipoBloqueo == 0 ? "IS NULL" : " = $idNuevoTipoBloqueo") . " AND ID_ORDEN_TRABAJO_MOVIMIENTO " . ($rowReservaLin->ID_ORDEN_TRABAJO_MOVIMIENTO == 0 ? "IS NULL" : " = $rowReservaLin->ID_ORDEN_TRABAJO_MOVIMIENTO") . " AND ID_INCIDENCIA_CALIDAD " . ($rowReservaLin->ID_INCIDENCIA_CALIDAD == 0 ? "IS NULL" : " = $rowReservaLin->ID_INCIDENCIA_CALIDAD");
            $rowMatUbiDestino                 = $bd->VerRegRest("MATERIAL_UBICACION", $clausulaWhere, "No");
            if ($rowMatUbiDestino == false):
                //CREO MATERIAL UBICACION DESTINO
                $sqlInsert = "INSERT INTO MATERIAL_UBICACION SET
                                      ID_MATERIAL = $rowReservaLin->ID_MATERIAL 
                                      , ID_UBICACION = $rowReservaLin->ID_UBICACION
                                      , ID_MATERIAL_FISICO = " . ($rowReservaLin->ID_MATERIAL_FISICO == 0 ? "NULL" : $rowReservaLin->ID_MATERIAL_FISICO) . " 
                                      , ID_TIPO_BLOQUEO = " . ($idNuevoTipoBloqueo == 0 ? "NULL" : $idNuevoTipoBloqueo) . " 
                                      , ID_ORDEN_TRABAJO_MOVIMIENTO = " . ($rowReservaLin->ID_ORDEN_TRABAJO_MOVIMIENTO == 0 ? "NULL" : $rowReservaLin->ID_ORDEN_TRABAJO_MOVIMIENTO) . " 
                                      , ID_INCIDENCIA_CALIDAD = " . ($rowReservaLin->ID_INCIDENCIA_CALIDAD == 0 ? "NULL" : $rowReservaLin->ID_INCIDENCIA_CALIDAD);
                $bd->ExecSQL($sqlInsert);

                //GUARDO EL ID MATERIAL UBICACION DESTINO
                $idMatUbiDestino = $bd->IdAsignado();
            else:
                //GUARDO EL ID MATERIAL UBICACION DESTINO
                $idMatUbiDestino = $rowMatUbiDestino->ID_MATERIAL_UBICACION;
            endif;

            //INCREMENTO MATERIAL_UBICACION DESTINO
            $sqlUpdate = "UPDATE MATERIAL_UBICACION SET
                                  STOCK_TOTAL = STOCK_TOTAL + $cantidad_cancelar
                                  , STOCK_OK = STOCK_OK + " . ($idNuevoTipoBloqueo == 0 ? $cantidad_cancelar : 0) . "
                                  , STOCK_BLOQUEADO = STOCK_BLOQUEADO + " . ($idNuevoTipoBloqueo == 0 ? 0 : $cantidad_cancelar) . "
                                  WHERE ID_MATERIAL_UBICACION = $idMatUbiDestino";
            $bd->ExecSQL($sqlUpdate);
        endif;

        //BUSCAMOS LA RESERVA
        $rowReserva = $bd->VerReg("RESERVA", "ID_RESERVA", $rowReservaLin->ID_RESERVA);

        //BUSCAMOS LA DEMANDA
        $rowDemanda = $bd->VerReg("DEMANDA", "ID_DEMANDA", $rowReserva->ID_DEMANDA);

        //SI LA DEMANDA ES DE TIPO OT, ACTUALIZAMOS LAS TRANSFERENCIAS DE PENDIENTES SI LAS HAY
        if ($rowDemanda->TIPO_DEMANDA == 'OT'):
            $this->actualizar_transferencias_pendientes($rowDemanda->ID_DEMANDA);
        endif;

        return $arr_respuesta;
    }

    /*
     *@string $idReservaLinea
     *@string $cantidad
     * PASA LA RESERVA LINEA A CUBIERTA Y ACTUALIZA LA DEMANDA SI ES NECESARIO
     */
    function finalizar_reserva_linea($idReservaLinea, $cantidad)
    {
        global $bd;
        global $html;
        global $auxiliar;
        global $administrador;

        //ARRAY A DEVOLVER
        $arr_respuesta = array();

        //BUSCAMOS LA RESERVA LINEA
        $rowReservaLinea = $bd->VerReg("RESERVA_LINEA", "ID_RESERVA_LINEA", $idReservaLinea);

        //COMPROBAMOS QUE LA RESERVA LINEA ESTE EN ESTADO RESERVADA Y LA CANTIDAD ES SUFICIENTE
        if ($rowReservaLinea->ESTADO_LINEA != 'Reservada' || ($rowReservaLinea->CANTIDAD < $cantidad)):
            $arr_respuesta['error'] = $auxiliar->traduce("El estado de la Reserva es incorrecto para realizar la operacion", $administrador->ID_IDIOMA) . ": " . $rowReservaLinea->ID_RESERVA;

            return $arr_respuesta;
        endif;

        //SI LA CANTIDAD DE LA LINEA ES MAYOR, LA DIVIDIMOS EN DOS PARA DEJAR PARTE FINALIZADA Y PARTE RESERVADA
        if (($rowReservaLinea->CANTIDAD - $cantidad) > EPSILON_SISTEMA):

            //DEJAMOS LA LINEA ACTUAL CUBIERTA QUITANDO LA CANTIDAD
            $sqlUpdate = "UPDATE RESERVA_LINEA SET 
                            CANTIDAD = CANTIDAD - " . $cantidad . " 
                            WHERE ID_RESERVA_LINEA = " . $rowReservaLinea->ID_RESERVA_LINEA;
            $bd->ExecSQL($sqlUpdate);

            //BUSCAMOS SI YA EXISTIA UNA FINALIZADA
            $GLOBALS["NotificaErrorPorEmail"] = "No";
            $clausulaWhere                    = "ID_RESERVA = $rowReservaLinea->ID_RESERVA AND BAJA = 0 AND ESTADO_LINEA = 'Finalizada' AND FECHA_CREACION_LINEA = '" . $rowReservaLinea->FECHA_CREACION_LINEA . "' AND ID_MATERIAL = $rowReservaLinea->ID_MATERIAL AND ID_UBICACION = " . $rowReservaLinea->ID_UBICACION . " AND ID_MATERIAL_FISICO " . ($rowReservaLinea->ID_MATERIAL_FISICO == NULL ? 'IS NULL' : " = " . $rowReservaLinea->ID_MATERIAL_FISICO) . " AND ID_TIPO_BLOQUEO " . ($rowReservaLinea->ID_TIPO_BLOQUEO == NULL ? 'IS NULL' : " = $rowReservaLinea->ID_TIPO_BLOQUEO") . " AND ID_ORDEN_TRABAJO_MOVIMIENTO " . ($rowReservaLinea->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? 'IS NULL' : " = " . $rowReservaLinea->ID_ORDEN_TRABAJO_MOVIMIENTO) . " AND ID_INCIDENCIA_CALIDAD " . ($rowReservaLinea->ID_INCIDENCIA_CALIDAD == NULL ? 'IS NULL' : " = " . $rowReservaLinea->ID_INCIDENCIA_CALIDAD);
            $rowReservaLineaFinalizada        = $bd->VerRegRest("RESERVA_LINEA", $clausulaWhere, "No");
            unset($GLOBALS["NotificaErrorPorEmail"]);
            if ($rowReservaLineaFinalizada != false):
                $sqlUpdate = "UPDATE RESERVA_LINEA SET 
                            CANTIDAD = CANTIDAD + " . $cantidad . " 
                            WHERE ID_RESERVA_LINEA = " . $rowReservaLineaFinalizada->ID_RESERVA_LINEA;
                $bd->ExecSQL($sqlUpdate);
            else:
                //GENERO UNA RESERVA LINEA
                $sqlInsert = "INSERT INTO RESERVA_LINEA SET
                                      ID_RESERVA = $rowReservaLinea->ID_RESERVA  
                                      , FECHA_CREACION_LINEA = '" . $rowReservaLinea->FECHA_CREACION_LINEA . "' 
                                      , ID_RESERVA_LINEA_PREVIA = " . ($rowReservaLinea->ID_RESERVA_LINEA_PREVIA == NULL ? "NULL" : $rowReservaLinea->ID_RESERVA_LINEA_PREVIA) . "
                                      , ESTADO_LINEA = 'Finalizada' 
                                      , CANTIDAD = " . $cantidad . "
                                      , ID_MATERIAL = " . $rowReservaLinea->ID_MATERIAL . "
                                      , ID_UBICACION = " . $rowReservaLinea->ID_UBICACION . " 
                                      , ID_MATERIAL_FISICO = " . ($rowReservaLinea->ID_MATERIAL_FISICO == NULL ? "NULL" : $rowReservaLinea->ID_MATERIAL_FISICO) . "
                                      , ID_TIPO_BLOQUEO = " . ($rowReservaLinea->ID_TIPO_BLOQUEO == NULL ? "NULL" : $rowReservaLinea->ID_TIPO_BLOQUEO) . " 
                                      , ID_ORDEN_TRABAJO_MOVIMIENTO = " . ($rowReservaLinea->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? "NULL" : $rowReservaLinea->ID_ORDEN_TRABAJO_MOVIMIENTO) . " 
                                      , ID_INCIDENCIA_CALIDAD = " . ($rowReservaLinea->ID_INCIDENCIA_CALIDAD == NULL ? "NULL" : $rowReservaLinea->ID_INCIDENCIA_CALIDAD);
                $bd->ExecSQL($sqlInsert);
            endif;

        else:
            //BUSCAMOS SI PARA LOS DATOS DE ESA LINEA YA EXISTE UNA FINALIZADA
            $GLOBALS["NotificaErrorPorEmail"] = "No";
            $clausulaWhere                    = "ID_RESERVA = $rowReservaLinea->ID_RESERVA AND BAJA = 0 AND ESTADO_LINEA = 'Finalizada' AND FECHA_CREACION_LINEA = '" . $rowReservaLinea->FECHA_CREACION_LINEA . "' AND ID_MATERIAL = $rowReservaLinea->ID_MATERIAL AND ID_UBICACION = " . $rowReservaLinea->ID_UBICACION . " AND ID_MATERIAL_FISICO " . ($rowReservaLinea->ID_MATERIAL_FISICO == NULL ? 'IS NULL' : " = " . $rowReservaLinea->ID_MATERIAL_FISICO) . " AND ID_TIPO_BLOQUEO " . ($rowReservaLinea->ID_TIPO_BLOQUEO == NULL ? 'IS NULL' : " = $rowReservaLinea->ID_TIPO_BLOQUEO") . " AND ID_ORDEN_TRABAJO_MOVIMIENTO " . ($rowReservaLinea->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? 'IS NULL' : " = " . $rowReservaLinea->ID_ORDEN_TRABAJO_MOVIMIENTO) . " AND ID_INCIDENCIA_CALIDAD " . ($rowReservaLinea->ID_INCIDENCIA_CALIDAD == NULL ? 'IS NULL' : " = " . $rowReservaLinea->ID_INCIDENCIA_CALIDAD);
            $rowReservaLineaFinalizada        = $bd->VerRegRest("RESERVA_LINEA", $clausulaWhere, "No");

            unset($GLOBALS["NotificaErrorPorEmail"]);
            if ($rowReservaLineaFinalizada != false):
                //SI EXISTE, AUMENTAMOS LA CANTIDAD DE LA FINALIZADA
                $sqlUpdate = "UPDATE RESERVA_LINEA SET 
                                CANTIDAD = CANTIDAD + " . $cantidad . " 
                                WHERE ID_RESERVA_LINEA = " . $rowReservaLineaFinalizada->ID_RESERVA_LINEA;
                $bd->ExecSQL($sqlUpdate);

                //DAMOS DE BAJA LA ACTUAL
                $sqlUpdate = "UPDATE RESERVA_LINEA SET 
                                 BAJA = 1
                                WHERE ID_RESERVA_LINEA = " . $rowReservaLinea->ID_RESERVA_LINEA;
                $bd->ExecSQL($sqlUpdate);

            else:
                //SI NO EXISTE, CAMBIAMOS EL ESTADO DE LA ACTUAL
                $sqlUpdate = "UPDATE RESERVA_LINEA SET 
                                ESTADO_LINEA = 'Finalizada'
                                WHERE ID_RESERVA_LINEA = " . $rowReservaLinea->ID_RESERVA_LINEA;
                $bd->ExecSQL($sqlUpdate);
            endif;
        endif;

        //LOG MOVIMIENTOS
        $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Reserva", $rowReservaLinea->ID_RESERVA, 'Finalizacion cantidad Reserva: ' . $cantidad . ' Reserva Linea:' . $rowReservaLinea->ID_RESERVA_LINEA);

        //ACTUALIZAMOS LA DEMANDA
        $rowReserva = $bd->VerReg("RESERVA", "ID_RESERVA", $rowReservaLinea->ID_RESERVA);
        $this->actualizar_estado_demanda($rowReserva->ID_DEMANDA, "Finalizacion Reserva :" . $rowReserva->ID_RESERVA);

        //BUSCAMOS LA DEMANDA
        $rowDemanda = $bd->VerReg("DEMANDA", "ID_DEMANDA", $rowReserva->ID_DEMANDA);

        //SI LA DEMANDA ES DE TIPO OT, ACTUALIZAMOS LAS TRANSFERENCIAS DE PENDIENTES SI LAS HAY
        if ($rowDemanda->TIPO_DEMANDA == 'OT'):
            $this->actualizar_transferencias_pendientes($rowDemanda->ID_DEMANDA);
        endif;

        return $arr_respuesta;

    }

    /*
     *@string $idReservaLinea
     *@string $cantidad
     * PASA LA RESERVA LINEA A RESERVADA Y ACTUALIZA LA DEMANDA SI ES NECESARIO
     */
    function reactivar_reserva_linea($idReservaLinea, $cantidad)
    {
        global $bd;
        global $html;
        global $auxiliar;
        global $administrador;

        //ARRAY A DEVOLVER
        $arr_respuesta                              = array();
        $arr_respuesta['idReservaLineaActualizada'] = "";

        //BUSCAMOS LA RESERVA LINEA
        $rowReservaLinea = $bd->VerReg("RESERVA_LINEA", "ID_RESERVA_LINEA", $idReservaLinea);

        //COMPROBAMOS QUE LA RESERVA LINEA ESTE EN ESTADO FINALIZADA Y LA CANTIDAD ES SUFICIENTE
        if (($rowReservaLinea->ESTADO_LINEA != 'Finalizada') || ($cantidad - $rowReservaLinea->CANTIDAD > EPSILON_SISTEMA)):
            $arr_respuesta['error'] = $auxiliar->traduce("El estado de la Reserva es incorrecto para realizar la operacion", $administrador->ID_IDIOMA) . ": " . $rowReservaLinea->ID_RESERVA;

            return $arr_respuesta;
        endif;

        //SI LA CANTIDAD DE LA LINEA ES MAYOR, LA DIVIDIMOS EN DOS PARA DEJAR PARTE FINALIZADA Y PARTE RESERVADA
        if ($rowReservaLinea->CANTIDAD - $cantidad > EPSILON_SISTEMA):

            //DEJAMOS LA LINEA ACTUAL CUBIERTA AÑADIENDO LA CANTIDAD
            $sqlUpdate = "UPDATE RESERVA_LINEA SET 
                            CANTIDAD = CANTIDAD - " . $cantidad . " 
                            WHERE ID_RESERVA_LINEA = " . $rowReservaLinea->ID_RESERVA_LINEA;
            $bd->ExecSQL($sqlUpdate);

            //BUSCAMOS SI YA EXISTIA UNA RESERVADA
            $GLOBALS["NotificaErrorPorEmail"] = "No";
            $clausulaWhere                    = "ID_RESERVA = $rowReservaLinea->ID_RESERVA AND ESTADO_LINEA = 'Reservada' AND BAJA = 0 AND FECHA_CREACION_LINEA = '" . $rowReservaLinea->FECHA_CREACION_LINEA . "' AND ID_MATERIAL = $rowReservaLinea->ID_MATERIAL AND ID_UBICACION = " . $rowReservaLinea->ID_UBICACION . " AND ID_MATERIAL_FISICO " . ($rowReservaLinea->ID_MATERIAL_FISICO == NULL ? 'IS NULL' : " = " . $rowReservaLinea->ID_MATERIAL_FISICO) . " AND ID_TIPO_BLOQUEO " . ($rowReservaLinea->ID_TIPO_BLOQUEO == NULL ? 'IS NULL' : " = $rowReservaLinea->ID_TIPO_BLOQUEO") . " AND ID_ORDEN_TRABAJO_MOVIMIENTO " . ($rowReservaLinea->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? 'IS NULL' : " = " . $rowReservaLinea->ID_ORDEN_TRABAJO_MOVIMIENTO) . " AND ID_INCIDENCIA_CALIDAD " . ($rowReservaLinea->ID_INCIDENCIA_CALIDAD == NULL ? 'IS NULL' : " = " . $rowReservaLinea->ID_INCIDENCIA_CALIDAD);
            $rowReservaLineaReservada         = $bd->VerRegRest("RESERVA_LINEA", $clausulaWhere, "No");
            unset($GLOBALS["NotificaErrorPorEmail"]);
            if ($rowReservaLineaReservada != false):
                $sqlUpdate = "UPDATE RESERVA_LINEA SET 
                                CANTIDAD = CANTIDAD + " . $cantidad . " 
                                WHERE ID_RESERVA_LINEA = " . $rowReservaLineaReservada->ID_RESERVA_LINEA;
                $bd->ExecSQL($sqlUpdate);

                //DEVOLVEMOS LA LINEA REALMENTE RESERVADA
                $arr_respuesta['idReservaLineaActualizada'] = $rowReservaLineaReservada->ID_RESERVA_LINEA;
            else:
                //GENERO UNA RESERVA LINEA
                $sqlInsert = "INSERT INTO RESERVA_LINEA SET
                                  ID_RESERVA = $rowReservaLinea->ID_RESERVA  
                                  , FECHA_CREACION_LINEA = '" . $rowReservaLinea->FECHA_CREACION_LINEA . "' 
                                  , ID_RESERVA_LINEA_PREVIA = " . ($rowReservaLinea->ID_RESERVA_LINEA_PREVIA == NULL ? "NULL" : $rowReservaLinea->ID_RESERVA_LINEA_PREVIA) . "
                                  , ESTADO_LINEA = 'Reservada' 
                                  , CANTIDAD = " . $cantidad . "
                                  , ID_MATERIAL = " . $rowReservaLinea->ID_MATERIAL . "
                                  , ID_UBICACION = " . $rowReservaLinea->ID_UBICACION . " 
                                  , ID_MATERIAL_FISICO = " . ($rowReservaLinea->ID_MATERIAL_FISICO == NULL ? "NULL" : $rowReservaLinea->ID_MATERIAL_FISICO) . "
                                  , ID_TIPO_BLOQUEO = " . ($rowReservaLinea->ID_TIPO_BLOQUEO == NULL ? "NULL" : $rowReservaLinea->ID_TIPO_BLOQUEO) . " 
                                  , ID_ORDEN_TRABAJO_MOVIMIENTO = " . ($rowReservaLinea->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? "NULL" : $rowReservaLinea->ID_ORDEN_TRABAJO_MOVIMIENTO) . " 
                                  , ID_INCIDENCIA_CALIDAD = " . ($rowReservaLinea->ID_INCIDENCIA_CALIDAD == NULL ? "NULL" : $rowReservaLinea->ID_INCIDENCIA_CALIDAD);
                $bd->ExecSQL($sqlInsert);
                $idReservaLineaNueva = $bd->IdAsignado();

                //DEVOLVEMOS LA LINEA REALMENTE RESERVADA
                $arr_respuesta['idReservaLineaActualizada'] = $idReservaLineaNueva;
            endif;

        else:
            //BUSCAMOS SI PARA LOS DATOS DE ESA LINEA YA EXISTE UNA RESERVADA
            $GLOBALS["NotificaErrorPorEmail"] = "No";
            $clausulaWhere                    = "ID_RESERVA = $rowReservaLinea->ID_RESERVA AND ESTADO_LINEA = 'Reservada' AND BAJA = 0 AND FECHA_CREACION_LINEA = '" . $rowReservaLinea->FECHA_CREACION_LINEA . "' AND ID_MATERIAL = $rowReservaLinea->ID_MATERIAL AND ID_UBICACION = " . $rowReservaLinea->ID_UBICACION . " AND ID_MATERIAL_FISICO " . ($rowReservaLinea->ID_MATERIAL_FISICO == NULL ? 'IS NULL' : " = " . $rowReservaLinea->ID_MATERIAL_FISICO) . " AND ID_TIPO_BLOQUEO " . ($rowReservaLinea->ID_TIPO_BLOQUEO == NULL ? 'IS NULL' : " = $rowReservaLinea->ID_TIPO_BLOQUEO") . " AND ID_ORDEN_TRABAJO_MOVIMIENTO " . ($rowReservaLinea->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? 'IS NULL' : " = " . $rowReservaLinea->ID_ORDEN_TRABAJO_MOVIMIENTO) . " AND ID_INCIDENCIA_CALIDAD " . ($rowReservaLinea->ID_INCIDENCIA_CALIDAD == NULL ? 'IS NULL' : " = " . $rowReservaLinea->ID_INCIDENCIA_CALIDAD);
            $rowReservaLineaReservada         = $bd->VerRegRest("RESERVA_LINEA", $clausulaWhere, "No");

            unset($GLOBALS["NotificaErrorPorEmail"]);
            if ($rowReservaLineaReservada != false):
                //SI EXISTE, AUMENTAMOS LA CANTIDAD DE LA RESERVADA
                $sqlUpdate = "UPDATE RESERVA_LINEA SET 
                                CANTIDAD = CANTIDAD + " . $cantidad . " 
                                WHERE ID_RESERVA_LINEA = " . $rowReservaLineaReservada->ID_RESERVA_LINEA;
                $bd->ExecSQL($sqlUpdate);

                //DEVOLVEMOS LA LINEA REALMENTE RESERVADA
                $arr_respuesta['idReservaLineaActualizada'] = $rowReservaLineaReservada->ID_RESERVA_LINEA;

                //DAMOS DE BAJA LA ACTUAL
                $sqlUpdate = "UPDATE RESERVA_LINEA SET 
                                 BAJA = 1
                                WHERE ID_RESERVA_LINEA = " . $rowReservaLinea->ID_RESERVA_LINEA;
                $bd->ExecSQL($sqlUpdate);

            else:
                //SI NO EXISTE, CAMBIAMOS EL ESTADO DE LA ACTUAL
                $sqlUpdate = "UPDATE RESERVA_LINEA SET 
                                ESTADO_LINEA = 'Reservada'
                                WHERE ID_RESERVA_LINEA = " . $rowReservaLinea->ID_RESERVA_LINEA;
                $bd->ExecSQL($sqlUpdate);

                //DEVOLVEMOS LA LINEA REALMENTE RESERVADA
                $arr_respuesta['idReservaLineaActualizada'] = $rowReservaLinea->ID_RESERVA_LINEA;
            endif;
        endif;

        //LOG MOVIMIENTOS
        $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Reserva", $rowReservaLinea->ID_RESERVA, 'Reactivacion cantidad Reserva: ' . $cantidad . ' Reserva Linea:' . $rowReservaLinea->ID_RESERVA_LINEA);

        //ACTUALIZAMOS LA DEMANDA
        $rowReserva = $bd->VerReg("RESERVA", "ID_RESERVA", $rowReservaLinea->ID_RESERVA);
        $this->actualizar_estado_demanda($rowReserva->ID_DEMANDA, "Reactivacion Reserva :" . $rowReserva->ID_RESERVA);

        //BUSCAMOS LA DEMANDA
        $rowDemanda = $bd->VerReg("DEMANDA", "ID_DEMANDA", $rowReserva->ID_DEMANDA);

        //SI LA DEMANDA ES DE TIPO OT, ACTUALIZAMOS LAS TRANSFERENCIAS DE PENDIENTES SI LAS HAY
        if ($rowDemanda->TIPO_DEMANDA == 'OT'):
            $this->actualizar_transferencias_pendientes($rowDemanda->ID_DEMANDA);
        endif;

        return $arr_respuesta;
    }

    /**
     * @param $idDemanda Demanda sobre la que crear reservas
     * @param $idOrdenTrabajoLinea Linea de orden de trabajo sobre la que crear reservas
     */
    function asociar_reserva_OT_CambioEstado($idDemanda, $idOrdenTrabajoLinea, $arrCambioEstado = NULL, $revisarTransferenciasPendientes = false)
    {
        global $bd;
        global $html;
        global $auxiliar;
        global $administrador;

        //ARRAY CON LOS DATOS A ASOCIAR A LA LINEA DE LA OR
        $arrDatos = array();

        //ARRAY RESPUESTA
        $arr_respuesta = array();

        //BUSCO LA DEMANDA
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowDemanda                       = $bd->VerReg("DEMANDA", "ID_DEMANDA", $idDemanda, "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);

        //SI NO EXISTE LA DEMANDA ME SALGO DE LA FUNCION
        if ($rowDemanda == false):
            $arr_respuesta['error'] = $auxiliar->traduce("La demanda no existe en la Base de datos", $administrador->ID_IDIOMA);

            return $arr_respuesta;
        endif;

        //BUSCO LA LINEA DE LA ORDEN DE TRABAJO
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowOrdenTrabajoLinea             = $bd->VerReg("ORDEN_TRABAJO_LINEA", "ID_ORDEN_TRABAJO_LINEA", $idOrdenTrabajoLinea, "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);

        //SI NO EXISTE LA LINEA DE LA ORDEN DE TRABAJO ME SALGO DE LA FUNCION
        if ($rowOrdenTrabajoLinea == false):
            $arr_respuesta['error'] = $auxiliar->traduce("La Linea de Orden Trabajo no existe en la Base de datos", $administrador->ID_IDIOMA);

            return $arr_respuesta;
        endif;

        //BUSCO LA ORDEN DE TRABAJO
        $rowOrdenTrabajo = $bd->VerReg("ORDEN_TRABAJO", "ID_ORDEN_TRABAJO", $rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO);

        //CALCULO LOS OBJETOS PARA ASOCIAR LAS LINEAS DE LA RESERVA
        if (
            (($rowOrdenTrabajo->SISTEMA_OT == 'MAXIMO') || ($rowOrdenTrabajo->SISTEMA_OT == 'SGA Manual')) &&
            ($rowOrdenTrabajoLinea->NUMERO_PENDIENTE != '') &&
            (($rowOrdenTrabajoLinea->PRIORIDAD_NUMERO_PENDIENTE == '1-Alta') || ($rowOrdenTrabajoLinea->PRIORIDAD_NUMERO_PENDIENTE == '2-Media') || ($rowOrdenTrabajoLinea->PRIORIDAD_NUMERO_PENDIENTE == '3-Baja'))
        ): //LINEA DE OT DE PENDIENTES
            if ($arrCambioEstado == NULL): //BUSCO TODOS SUS CAMBIOS DE ESTADO
                //LOCALIZO LOS CAMBIOS DE ESTADO RELACIONADOS CON ESTA LINEA DE ORDEN DE TRABAJO
                $sqlCambiosEstado    = "SELECT CE.ID_CAMBIO_ESTADO, CE.CANTIDAD 
                                     FROM CAMBIO_ESTADO CE 
                                     WHERE CE.ID_ORDEN_TRABAJO_LINEA = $rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO_LINEA AND CE.TIPO_CAMBIO_ESTADO = 'ReservaPlanificadoOrdenesTrabajo' AND CE.ID_CAMBIO_ESTADO_RELACIONADO IS NULL AND CE.BAJA = 0";
                $resultCambiosEstado = $bd->ExecSQL($sqlCambiosEstado);
                while ($rowCambioEstado = $bd->SigReg($resultCambiosEstado)):
                    //AÑADO AL ARRAY LO NECESARIO
                    $arrDatos[$rowCambioEstado->ID_CAMBIO_ESTADO] = $rowCambioEstado->CANTIDAD;
                endwhile;
            else: //BUSCO LOS CAMBIOS DE ESTADO PASADOS POR PARAMETRO
                foreach ($arrCambioEstado as $idCambioEstado => $cantidad):
                    //AÑADO AL ARRAY LO NECESARIO
                    $arrDatos[$idCambioEstado] = $cantidad;
                endforeach;
            endif;
        endif;
        //FIN CALCULO LOS OBJETOS PARA ASOCIAR LAS LINEAS DE LA COLA DE RESERVAS

        //LLAMO A LA FUNCION PARA ASIGNAR LAS LINEAS DE LA COLA DE RESERVAS
        if (count( (array)$arrDatos) > 0):
            $arr_reserva_linea = $this->asociar_reserva($rowDemanda->ID_DEMANDA, 'CambioEstado', $arrDatos, $revisarTransferenciasPendientes);
            if (isset($arr_reserva_linea['error']) && ($arr_reserva_linea['error'] != "")):
                $arr_respuesta['error'] = $arr_reserva_linea['error'];

                return $arr_respuesta;
            endif;
        endif;

        //SI LA DEMANDA ES DE TIPO OT, ACTUALIZAMOS LAS TRANSFERENCIAS DE PENDIENTES SI LAS HAY
        if ($rowDemanda->TIPO_DEMANDA == 'OT'):
            $this->actualizar_transferencias_pendientes($rowDemanda->ID_DEMANDA);
        endif;

        return $arr_respuesta;
    }

    function grabar_recepcion_OTL($idOrdenTrabajoLinea, $idMovimientoEntradaLinea, $cantidad)
    {
        global $bd;

        //BUSCO SI EXISTE LA LINEA DE ORDEN DE TRABAJO
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowOrdenTrabajoLinea             = $bd->VerReg("ORDEN_TRABAJO_LINEA", "ID_ORDEN_TRABAJO_LINEA", $idOrdenTrabajoLinea, "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);

        //BUSCO SI EXISTE LA LINEA DEL MOVIMIENTO DE ENTRADA
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowMovimientoEntradaLinea        = $bd->VerReg("MOVIMIENTO_ENTRADA_LINEA", "ID_MOVIMIENTO_ENTRADA_LINEA", $idMovimientoEntradaLinea, "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);

        //SI ESTAN DEFINIDOS LOS OBJETOS RECIBIDOS POR PARAMETROS
        if (($rowOrdenTrabajoLinea != false) && ($rowMovimientoEntradaLinea != false)):
            //BUSCO SI EXISTE EL REGISTRO
            $GLOBALS["NotificaErrorPorEmail"] = "No";
            $rowOrdenTrabajoLineaRecepcion    = $bd->VerRegRest("ORDEN_TRABAJO_LINEA_RECEPCION", "ID_ORDEN_TRABAJO_LINEA = $idOrdenTrabajoLinea AND ID_MOVIMIENTO_ENTRADA_LINEA = $idMovimientoEntradaLinea AND BAJA = 0", "No");
            unset($GLOBALS["NotificaErrorPorEmail"]);

            //ACTUACIONES EN FUNCION DE SI EXISTE O NO EL REGISTRO
            if ($rowOrdenTrabajoLineaRecepcion != false): //SI EXISTE EL REGISTRO LO ACTUALIZO
                $sqlUpdate = "UPDATE ORDEN_TRABAJO_LINEA_RECEPCION SET 
                              CANTIDAD = CANTIDAD + $cantidad 
                              WHERE ID_ORDEN_TRABAJO_LINEA_RECEPCION = $rowOrdenTrabajoLineaRecepcion->ID_ORDEN_TRABAJO_LINEA_RECEPCION";
                $bd->ExecSQL($sqlUpdate);
            else: //SI NO EXISTE EL REGISTRO LO CREO
                $sqlInsert = "INSERT INTO ORDEN_TRABAJO_LINEA_RECEPCION SET 
                              ID_MOVIMIENTO_ENTRADA = $rowMovimientoEntradaLinea->ID_MOVIMIENTO_ENTRADA 
                              , ID_MOVIMIENTO_ENTRADA_LINEA = $rowMovimientoEntradaLinea->ID_MOVIMIENTO_ENTRADA_LINEA 
                              , ID_ORDEN_TRABAJO_LINEA = $rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO_LINEA
                              , ESTADO = 'Pendiente Confirmacion SAP' 
                              , CANTIDAD = $cantidad 
                              , BAJA = 0";
                $bd->ExecSQL($sqlInsert);
            endif;
            //FIN ACTUACIONES EN FUNCION DE SI EXISTE O NO EL REGISTRO
        endif;
        //FIN SI ESTAN DEFINIDOS LOS OBJETOS RECIBIDOS POR PARAMETROS
    }


    /**
     * @param String $idDemanda
     * @return ID DE LA RESERVA DE LA DEMANDA. SI NO EXISTE O ESTA DE BAJA, SE CREA/ACTIVA
     * //TODO: ESTAMOS SUPONIENDO QUE SOLO HAY UNA RESERVA POR DEMANDA, PERO EN CASO DE SPVs puede HABER MAS
     */
    function obtener_o_crear_reserva($idDemanda)
    {
        global $bd;
        global $html;
        global $auxiliar;
        global $administrador;

        //BUSCAMOS SI EXISTE LA RESERVA
        $idReserva  = NULL;
        $rowReserva = $this->get_reserva_demanda($idDemanda);
        if ($rowReserva != false):
            $idReserva = $rowReserva->ID_RESERVA;
        else:
            //BUSCAMOS SI ESTA DADA DE BAJA
            $rowReserva = $this->get_reserva_demanda($idDemanda, "Si");

            //SI ESTA DADA DE BAJA, LA REACTIVAMOS
            if ($rowReserva != false):
                //LO REACTIVAMOS
                $sqlUpdate = "UPDATE RESERVA SET BAJA = 0 WHERE ID_RESERVA = $rowReserva->ID_RESERVA";
                $bd->ExecSQL($sqlUpdate);
                $idReserva = $rowReserva->ID_RESERVA;

                // LOG MOVIMIENTOS
                $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Reserva", $idReserva, 'Reactivacion Reserva');

            else:
                //BUSCAMOS EL ALMACEN DE LA DEMANDA
                $idAlmacenReserva = $this->get_almacen_reserva($idDemanda);

                //CREAMOS LA RESERVA
                $sqlInsert = "INSERT INTO RESERVA SET 
                              ID_DEMANDA = $idDemanda 
                              , ID_ALMACEN_RESERVA = $idAlmacenReserva
                              , FECHA_CREACION = '" . date("Y-m-d H:i:s") . "'";
                $bd->ExecSQL($sqlInsert);

                //EXTRAIGO EL ID DE RESERVA
                $idReserva = $bd->IdAsignado();

                // LOG MOVIMIENTOS
                $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Creación", "Reserva", $idReserva, 'Creacion Reserva');
            endif;
        endif;

        //BUSCAMOS LA DEMANDA
        $rowDemanda = $bd->VerReg("DEMANDA", "ID_DEMANDA", $idDemanda);

        //SI LA DEMANDA ES DE TIPO OT, ACTUALIZAMOS LAS TRANSFERENCIAS DE PENDIENTES SI LAS HAY
        if ($rowDemanda->TIPO_DEMANDA == 'OT'):
            $this->actualizar_transferencias_pendientes($rowDemanda->ID_DEMANDA);
        endif;

        return $idReserva;
    }


    /**
     * @string $idDemandaOriginal
     * @string $idDemandaNueva
     * @string $cantidadRobo
     * @string $idReservaLineaRobar => en tipos manuales se prefija la linea a robar
     * @string $tipoRobo => Si es tipo 'Split', no se reactivará la cola original y se actualizaran las demandas
     * @string $tipoRobo => Si es tipo 'RoboDesdeCola', solo se robara material reservado de su red.
     * @string $tipoRobo => Si es tipo 'RoboDesdeColaOtraRed', solo se robara material reservado de otra red. Devolveremos esos cambios de red PARA CREAR LOS CAMBIO DE ESTADO Y ENVIARLOS A SAP
     * @string $tipoRobo => Si es tipo 'RoboManual', 'RoboManualOtraRed'  robos generados por el usuario. Se marcaran las lineas para que el proceso de colas no rehaga el robo
     * SE INTENTARA COGER STOCK RESERVADO DE LA DEMANDA ORIGINAL Y ASIGNARLO A LA NUEVA, DEJANDO LO ORIGNAL EN LA COLA PROGRAMADA
     * SE DEBERA HABER CREADO LA COLA DE RESERVAS EN LA DEMANDA NUEVA
     */
    function robo_reserva_entre_demandas($idDemandaOriginal, $idDemandaNueva, $cantidadRobo, $tipoRobo = "", $idReservaLineaRobar = "")
    {

        global $bd;
        global $html;
        global $auxiliar;
        global $administrador;
        global $necesidad;

        //ARRAY A DEVOLVER
        $arr_respuesta = array();

        //PARA GUARDAR LA CANTIDAD TOTAL ROBADA Y METERLA EN COLA
        $idReservaNueva         = NULL;
        $cantidadRobadaReservas = 0;
        $cantidadRobadaCola     = 0;
        $cantidadPdteRobar      = $cantidadRobo;
        $arr_lineas_robadas     = array();

        //BUSCAMOS LA DEMANDA ORIGINAL
        $rowDemandaOriginal = $bd->VerReg("DEMANDA", "ID_DEMANDA", $idDemandaOriginal);

        //SI ES SPLIT, PRIMERO INTENTAMOS ROBAR DE LA COLA. LA COLA DE LA NUEVA DEMANDA YA EXISTE POR LO QUE NO HACE FALTA METERLO
        if ($tipoRobo == "Split"):
            if ($cantidadPdteRobar > EPSILON_SISTEMA):
                //LO QUITAMOS DE LA COLA
                $rowColaProgramada = $this->get_cola_reserva($rowDemandaOriginal->ID_DEMANDA);
                if ($rowColaProgramada != false && $rowColaProgramada->CANTIDAD_EN_COLA != 0):
                    //SI LA COLA TIENE SUFICIENTE CANTIDAD PARA ROBAR
                    if ($rowColaProgramada->CANTIDAD_EN_COLA >= $cantidadPdteRobar):
                        $this->quitar_cantidad_cola($rowDemandaOriginal->ID_DEMANDA, $cantidadPdteRobar, 'Quitar cantidad: ' . $cantidadPdteRobar . ' por Robo de la Demanda: ' . $idDemandaNueva);
                        $cantidadRobadaCola = $cantidadPdteRobar;
                        $cantidadPdteRobar  = 0;

                    else://LA COLA NO TIENE SUFICIENTE CANTIDAD
                        $this->quitar_cantidad_cola($rowDemandaOriginal->ID_DEMANDA, $rowColaProgramada->CANTIDAD_EN_COLA, 'Quitar cantidad: ' . $rowColaProgramada->CANTIDAD_EN_COLA . ' por Robo de la Demanda: ' . $idDemandaNueva);
                        $cantidadRobadaCola = $rowColaProgramada->CANTIDAD_EN_COLA;
                        $cantidadPdteRobar  = $cantidadPdteRobar - $rowColaProgramada->CANTIDAD_EN_COLA;
                    endif;
                endif;
            endif;
        endif;//FIN SI ES SPLIT, PRIMERO INTENTAMOS ROBAR DE LA COLA


        //OBTENEMOS LAS RESERVAS DE LA DEMANDA ORIGINAL, PRIORIZANDO LAS QUE ESTAN SIN UTILIZAR
        if (($tipoRobo == "RoboDesdeCola") || ($tipoRobo == "RoboDesdeColaOtraRed")):
            //ROBOS SOLO SOBRE RESERVAS ACTIVAS Y NO FIJAS
            $resultReservasOriginal = $this->get_lineas_reservas_demanda($rowDemandaOriginal->ID_DEMANDA, 'Reservada', "RL.FECHA_CREACION_LINEA DESC", 'No');
        elseif (($tipoRobo == "RoboManual") || ($tipoRobo == "RoboManualOtraRed")):
            //ROBOS SOLO SOBRE RESERVAS ACTIVAS Y NO FIJAS
            $resultReservasOriginal = $this->get_lineas_reservas_demanda($rowDemandaOriginal->ID_DEMANDA, 'Reservada', "RL.FECHA_CREACION_LINEA DESC", '', $idReservaLineaRobar);
        else:
            $resultReservasOriginal = $this->get_lineas_reservas_demanda($rowDemandaOriginal->ID_DEMANDA, '', "RL.ESTADO_LINEA ASC");
        endif;
        if ($resultReservasOriginal != false):

            //BLOQUEOS RESERVADO
            $rowTipoBloqueoReservado            = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "RV");
            $rowTipoBloqueoReservadoPlanificado = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "RVP");

            //RECORREMOS LAS LINEAS
            while (($rowReservaLinea = $bd->SigReg($resultReservasOriginal)) && ($cantidadPdteRobar > EPSILON_SISTEMA)):

                //SI LA LINEA TIENE MAS CANTIDAD DE LA NECESARIA
                if ($rowReservaLinea->CANTIDAD > $cantidadPdteRobar):
                    //QUITAMOS LA CANTIDAD A LA RESERVA ORIGINAL
                    $sqlUpdate = " UPDATE RESERVA_LINEA SET CANTIDAD = CANTIDAD - $cantidadPdteRobar WHERE ID_RESERVA_LINEA = $rowReservaLinea->ID_RESERVA_LINEA";
                    $bd->ExecSQL($sqlUpdate);

                    $cantidadRobada    = $cantidadPdteRobar;
                    $cantidadPdteRobar = 0;

                else://SI LA LINEA SE QUEDA SIN CANTIDAD
                    //DAMOS DE BAJA LA RESERVA LINEA ORIGINAL
                    $sqlUpdate = " UPDATE RESERVA_LINEA SET BAJA = 1 WHERE ID_RESERVA_LINEA = $rowReservaLinea->ID_RESERVA_LINEA";
                    $bd->ExecSQL($sqlUpdate);

                    $cantidadPdteRobar = $cantidadPdteRobar - $rowReservaLinea->CANTIDAD;
                    $cantidadRobada    = $rowReservaLinea->CANTIDAD;
                endif;

                //GUSDAMOS LINEA Y CANTIDAD ROBADA
                if (!isset($arr_lineas_robadas[$rowReservaLinea->ID_RESERVA_LINEA])):
                    $arr_lineas_robadas[$rowReservaLinea->ID_RESERVA_LINEA] = $cantidadRobada;
                else:
                    $arr_lineas_robadas[$rowReservaLinea->ID_RESERVA_LINEA] = $arr_lineas_robadas[$rowReservaLinea->ID_RESERVA_LINEA] + $cantidadRobada;
                endif;

                //SI AUN NO HEMOS QUITADO EN ESTA RESERVA, LA CREAMOS
                if ($idReservaNueva == NULL):
                    $idReservaNueva = $this->obtener_o_crear_reserva($idDemandaNueva);
                endif;

                //TIPO BLOQUEO CAMBIA SI LO INDICA EL TIPO DE ROBO
                $idTipoBloqueoLinea = ($rowReservaLinea->ID_TIPO_BLOQUEO != NULL ? $rowReservaLinea->ID_TIPO_BLOQUEO : "NULL");
                if ($tipoRobo == "RoboDesdeColaOtraRed" || $tipoRobo == "RoboManualOtraRed")://SI SE ROBA DESDE OTRA RED, ACTUALIZAMOS EL TIPO BLOQUEO
                    $idTipoBloqueoLinea = ($idTipoBloqueoLinea == $rowTipoBloqueoReservadoPlanificado->ID_TIPO_BLOQUEO ? $rowTipoBloqueoReservado->ID_TIPO_BLOQUEO : $rowTipoBloqueoReservadoPlanificado->ID_TIPO_BLOQUEO);
                endif;

                //PARA SABER SI LA LINEA VA A PODER ROBARSE POR EL PROCESO DE COLAS
                $reservaFija = 0;
                if (($tipoRobo == "RoboManual") || ($tipoRobo == "RoboManualOtraRed")):
                    $reservaFija = 1;
                elseif ($tipoRobo == "Split")://EN EL CASO DE SPLIT, LO HEREDAMOS
                    $reservaFija = $rowReservaLinea->RESERVA_FIJA;
                endif;

                //GENERAMOS LA RESERVA LINEA A PARTIR DE ESTA RESERVA LINEA
                $sqlInsert = "INSERT INTO RESERVA_LINEA SET 
                                          ID_RESERVA = " . $idReservaNueva . "
                                        , ID_RESERVA_LINEA_PREVIA = $rowReservaLinea->ID_RESERVA_LINEA
                                        , FECHA_CREACION_LINEA = '" . date("Y-m-d H:i:s") . "'
                                        , CANTIDAD = " . $cantidadRobada . "
                                        , ESTADO_LINEA = '" . $rowReservaLinea->ESTADO_LINEA . "'
                                        , RESERVA_FIJA = " . $reservaFija . "
                                        , ID_MATERIAL = $rowReservaLinea->ID_MATERIAL
                                        , ID_MATERIAL_FISICO = " . ($rowReservaLinea->ID_MATERIAL_FISICO != NULL ? $rowReservaLinea->ID_MATERIAL_FISICO : "NULL") . "
                                        , ID_INCIDENCIA_CALIDAD = " . ($rowReservaLinea->ID_INCIDENCIA_CALIDAD != NULL ? $rowReservaLinea->ID_INCIDENCIA_CALIDAD : "NULL") . "
                                        , ID_UBICACION = " . ($rowReservaLinea->ID_UBICACION != NULL ? $rowReservaLinea->ID_UBICACION : "NULL") . "
                                        , ID_TIPO_BLOQUEO = " . $idTipoBloqueoLinea . "
                                        , ID_ORDEN_TRABAJO_MOVIMIENTO = " . ($rowReservaLinea->ID_ORDEN_TRABAJO_MOVIMIENTO != NULL ? $rowReservaLinea->ID_ORDEN_TRABAJO_MOVIMIENTO : "NULL");
                $bd->ExecSQL($sqlInsert);
                $idReservaLineaNueva = $bd->IdAsignado();

                //GUARDAMOS LA CANTIDAD TOTAL
                $cantidadRobadaReservas = $cantidadRobadaReservas + $cantidadRobada;

                //LOG MOVIMIENTOS
                $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Reserva", $idReservaNueva, 'Robo de : ' . $cantidadRobada . ' a la Reserva: ' . $rowReservaLinea->ID_RESERVA . ' .Reserva Linea:' . $idReservaLineaNueva);
                $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Reserva", $rowReservaLinea->ID_RESERVA, 'Robo de : ' . $cantidadRobada . ' por la Reserva: ' . $idReservaNueva . ' .Reserva Linea:' . $rowReservaLinea->ID_RESERVA_LINEA);
            endwhile;
        endif;

        //SI ES SPLIT, ACTUALIZAMOS LA DEMANDA ORIGINAL QUITANDO CANTIDADES
        if ($tipoRobo == "Split"):
            //OBTENEMOS LO ROBADO ENTRE RESERVAS Y COLA
            $cantidadTotalRobada = $cantidadRobadaReservas + $cantidadRobadaCola;
            if ($cantidadTotalRobada > EPSILON_SISTEMA):
                //ACTUALIZAMOS LA DEMANDA ORIGINAL, SI SE ROBA COMPLETAMENTE LA DAMOS DE BAJA
                $updateEstado = "";
                if ($rowDemandaOriginal->CANTIDAD_DEMANDA - $cantidadTotalRobada < EPSILON_SISTEMA):
                    $updateEstado = ",BAJA = 1";
                endif;
                $sqlUpdate = "UPDATE DEMANDA SET 
                                CANTIDAD_DEMANDA = CANTIDAD_DEMANDA - $cantidadTotalRobada
                              , CANTIDAD_PENDIENTE_RESERVAR = CANTIDAD_PENDIENTE_RESERVAR - $cantidadRobadaCola
                            " . $updateEstado . "
                        WHERE ID_DEMANDA = $rowDemandaOriginal->ID_DEMANDA";
                $bd->ExecSQL($sqlUpdate);

                //LOG MOVIMIENTOS
                $rowDemandaOriginalActualizada = $bd->VerReg("DEMANDA", "ID_DEMANDA", $rowDemandaOriginal->ID_DEMANDA);
                $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Demanda", $rowDemandaOriginal->ID_DEMANDA, 'Quitar cantidad: ' . $cantidadTotalRobada . ' por Robo de la Demanda: ' . $idDemandaNueva, 'DEMANDA', $rowDemandaOriginal, $rowDemandaOriginalActualizada);

                //ACTUALIZAMOS ESTADO SI ES NECESARIO
                if ($rowDemandaOriginalActualizada->BAJA == 0):
                    $this->actualizar_estado_demanda($rowDemandaOriginal->ID_DEMANDA, 'Robo de la Demanda: ' . $idDemandaNueva);
                endif;
            endif;
        endif;

        //SI SE HA CONSEGUIDO ROBAR RESERVADO DE LA DEMANDA ORIGINAL, METEMOS ESA CANTIDAD EN LA COLA ORIGINAL
        if ($cantidadRobadaReservas > EPSILON_SISTEMA):

            //SI NO ES TIPO SPLIT, REACTIVAMOS LA COLA ORIGINAL
            if ($tipoRobo != "Split"):

                //SI ES MANUAL, QUITAMOS LA CANTIDAD
                if ($rowDemandaOriginal->TIPO_DEMANDA == "Manual"):

                    //ACTUALIZAMOS LA DEMANDA ORIGINAL, SI SE ROBA COMPLETAMENTE LA DAMOS DE BAJA
                    $updateEstado = "";
                    if ($rowDemandaOriginal->CANTIDAD_DEMANDA - $cantidadRobadaReservas < EPSILON_SISTEMA):
                        $updateEstado = ",BAJA = 1";
                    endif;
                    $sqlUpdate = "UPDATE DEMANDA SET 
                                CANTIDAD_DEMANDA = CANTIDAD_DEMANDA - $cantidadRobadaReservas
                            " . $updateEstado . "
                        WHERE ID_DEMANDA = $rowDemandaOriginal->ID_DEMANDA";
                    $bd->ExecSQL($sqlUpdate);

                    //LOG MOVIMIENTOS
                    $rowDemandaOriginalActualizada = $bd->VerReg("DEMANDA", "ID_DEMANDA", $rowDemandaOriginal->ID_DEMANDA);
                    $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Demanda", $rowDemandaOriginal->ID_DEMANDA, 'Quitar cantidad: ' . $cantidadRobadaReservas . ' por Robo de la Demanda: ' . $idDemandaNueva, 'DEMANDA', $rowDemandaOriginal, $rowDemandaOriginalActualizada);

                else:
                    //PONEMOS LA CANTIDAD EN LA COLA ORIGINAL
                    $this->poner_cantidad_cola($rowDemandaOriginal->ID_DEMANDA, $cantidadRobadaReservas, 'Robo cantidad: ' . $cantidadRobadaReservas . ' por Demanda: ' . $idDemandaNueva);

                    //ACTUALIZAMOS CANTIDAD PDTE RESERVAS
                    $sqlUpdate = "UPDATE DEMANDA SET 
                               CANTIDAD_PENDIENTE_RESERVAR = CANTIDAD_PENDIENTE_RESERVAR + $cantidadRobadaReservas
                                WHERE ID_DEMANDA = $rowDemandaOriginal->ID_DEMANDA";
                    $bd->ExecSQL($sqlUpdate);

                    //LOG MOVIMIENTOS
                    $rowDemandaOriginalActualizada = $bd->VerReg("DEMANDA", "ID_DEMANDA", $rowDemandaOriginal->ID_DEMANDA);
                    $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Demanda", $rowDemandaOriginal->ID_DEMANDA, 'Quitar cantidad Reservada: ' . $cantidadRobadaReservas . ' por Robo de la Demanda: ' . $idDemandaNueva, 'DEMANDA', $rowDemandaOriginal, $rowDemandaOriginalActualizada);

                    //ACTUALIZAMOS ESTADO SI ES NECESARIO
                    $this->actualizar_estado_demanda($rowDemandaOriginal->ID_DEMANDA, 'Robo de la Demanda: ' . $idDemandaNueva);
                endif;
            endif;

            //QUITAMOS LA CANTIDAD ROBADA DE LA COLA DE LA DEMANDA QUE ROBA
            $rowDemandaNueva = $bd->VerReg("DEMANDA", "ID_DEMANDA", $idDemandaNueva);
            $this->quitar_cantidad_cola($rowDemandaNueva->ID_DEMANDA, $cantidadRobadaReservas, 'Robo cantidad: ' . $cantidadRobadaReservas . ' a la Demanda: ' . $rowDemandaOriginal->ID_DEMANDA);

            //ACTUALIZAMOS LA DEMANDA CON LO QUE HEMOS CONSEGUIDO DE RESERVAS
            $sqlUpdate = "UPDATE DEMANDA SET 
                          CANTIDAD_PENDIENTE_RESERVAR = CANTIDAD_PENDIENTE_RESERVAR - $cantidadRobadaReservas
                          WHERE ID_DEMANDA = $rowDemandaNueva->ID_DEMANDA";
            $bd->ExecSQL($sqlUpdate);

            //SI LA CANTIDAD PENDIENTE RESERVAR SE VA A QUEDAR NEGATIVA DEVOLVERE UN ERROR
            if (($cantidadRobadaReservas - $rowDemandaNueva->CANTIDAD_PENDIENTE_RESERVAR) > EPSILON_SISTEMA):
                $arr_respuesta['cantidadPdteRobar'] = $cantidadPdteRobar;

                $bd->EnviarEmailErr("Cantidad Pendiente Reservar en demanda negativa", "Demanda que roba " . $rowDemandaNueva->ID_DEMANDA . ". Demanda a la que se roba " . $rowDemandaOriginal->ID_DEMANDA);
            endif;

            //LOG MOVIMIENTOS
            $rowDemandaNuevaActualizada = $bd->VerReg("DEMANDA", "ID_DEMANDA", $rowDemandaNueva->ID_DEMANDA);
            $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Demanda", $rowDemandaNuevaActualizada->ID_DEMANDA, 'Robo de : ' . $cantidadRobadaReservas . ' a la Demanda: ' . $rowDemandaOriginal->ID_DEMANDA, 'DEMANDA', $rowDemandaNueva, $rowDemandaNuevaActualizada);

            //ACTUALIZAMOS ESTADO SI ES NECESARIO
            $this->actualizar_estado_demanda($rowDemandaNueva->ID_DEMANDA, 'Robo de la Demanda: ' . $idDemandaNueva);
        endif;

        //DEVOLVEMOS LINEAS ROBADAS
        if (count( (array)$arr_lineas_robadas) > 0):
            $arr_respuesta['arrLineasRobadas'] = $arr_lineas_robadas;
        endif;
        $arr_respuesta['cantidadPdteRobar'] = $cantidadPdteRobar;

        //SI LA DEMANDA ORIGINAL ES DE TIPO OT, ACTUALIZAMOS LAS TRANSFERENCIAS DE PENDIENTES SI LAS HAY
        if ($rowDemandaOriginal->TIPO_DEMANDA == 'OT'):
            $this->actualizar_transferencias_pendientes($rowDemandaOriginal->ID_DEMANDA);
        endif;

        //SI LA DEMANDA NUEVA ES DE TIPO OT, ACTUALIZAMOS LAS TRANSFERENCIAS DE PENDIENTES SI LAS HAY
        $rowDemandaNueva = $bd->VerReg("DEMANDA", "ID_DEMANDA", $idDemandaNueva);

        //SI LA DEMANDA NUEVA ES DE TIPO OT, ACTUALIZAMOS LAS TRANSFERENCIAS DE PENDIENTES SI LAS HAY
        if ($rowDemandaNueva->TIPO_DEMANDA == 'OT'):
            $this->actualizar_transferencias_pendientes($rowDemandaNueva->ID_DEMANDA);
        endif;

        return $arr_respuesta;
    }

    /**
     * @string $idPedidoSalidaLineaNuevo
     * //CUANDO CAE EL SPLIT DE UNA LINEA, SE HACE EL SPLIT DE LA DEMANDA Y RESERVAS
     */
    function split_linea_pedido_demanda($idPedidoSalidaLineaNuevo, $cantidadSplit, $idNecesidad = NULL)
    {
        global $bd;
        global $html;
        global $auxiliar;
        global $administrador;

        //ARRAY A DEVOLVER
        $arr_respuesta = array();

        //COMPROBAMOS PEDIDOS
        $rowPSLNuevo = $bd->VerReg("PEDIDO_SALIDA_LINEA", "ID_PEDIDO_SALIDA_LINEA", $idPedidoSalidaLineaNuevo);

        //BUSAMOS ALMACEN RESERVA (Origen)
        $rowAlmacenReserva = $bd->VerReg("ALMACEN", "ID_ALMACEN", $rowPSLNuevo->ID_ALMACEN_ORIGEN);
        //BUSCAMOS EL CENTRO DE LA DEMANDA
        $rowCentroReserva = $bd->VerReg("CENTRO", "ID_CENTRO", $rowAlmacenReserva->ID_CENTRO);

        //COMPROBAMOS TIPO DE PEDIDO
        $rowPedido = $bd->VerReg("PEDIDO_SALIDA", "ID_PEDIDO_SALIDA", $rowPSLNuevo->ID_PEDIDO_SALIDA);

        //SI TIENE ACTIVA LA GESTION DE DEMANDAS
        if (($rowCentroReserva->GESTION_RESERVAS == 1) && ($this->pedido_admite_demanda($rowPedido->TIPO_PEDIDO, $rowPedido->TIPO_PEDIDO_SAP))):

            if ($rowPSLNuevo->ID_PEDIDO_SALIDA_LINEA_ORIGINAL_SPLIT != NULL):
                $rowPSLOriginal = $bd->VerReg("PEDIDO_SALIDA_LINEA", "ID_PEDIDO_SALIDA_LINEA", $rowPSLNuevo->ID_PEDIDO_SALIDA_LINEA_ORIGINAL_SPLIT);
                if ($rowPSLOriginal == false):
                    $arr_respuesta['error'] = $auxiliar->traduce("Error al generar la demanda", $administrador->ID_IDIOMA) . " " . $auxiliar->traduce("Error Interno", $administrador->ID_IDIOMA) . " < br>";

                    return $arr_respuesta;
                endif;
            else:
                $arr_respuesta['error'] = $auxiliar->traduce("Error al generar la demanda", $administrador->ID_IDIOMA) . " " . $auxiliar->traduce("La linea que intentar hacer Split no tiene definida la linea original", $administrador->ID_IDIOMA) . " < br>";

                return $arr_respuesta;
            endif;

            //CREAMOS LA DEMANDA Y COLA PARA LA NUEVA LINEA
            $arr_creacion = $this->creacion_demanda("Pedido", $rowPSLNuevo->ID_PEDIDO_SALIDA_LINEA, $cantidadSplit, $idNecesidad);

            if (isset($arr_creacion['idDemanda']) && $arr_creacion['idDemanda'] != ""):

                //BUSCAMOS LA DEMANDA DE LA LINEA ORIGINAL
                $rowDemandaOriginal = $this->get_demanda("Pedido", $rowPSLOriginal->ID_PEDIDO_SALIDA_LINEA, $idNecesidad);
                if ($rowDemandaOriginal != false):

                    //BUSCAMOS LA NUEVA DEMANDA
                    $rowDemandaNueva = $bd->VerReg("DEMANDA", "ID_DEMANDA", $arr_creacion['idDemanda']);

                    //QUITAMOS CANTIDAD DE LA DEMANDA ORIGINAL
                    $this->robo_reserva_entre_demandas($rowDemandaOriginal->ID_DEMANDA, $rowDemandaNueva->ID_DEMANDA, $cantidadSplit, "Split");

                endif;//FIN SI HABIA DEMANDAS

            elseif (isset($arr_creacion['error']) && $arr_creacion['error'] != "")://SI VIENE ERROR
                $arr_respuesta['error'] = $arr_creacion['error'];
            endif;
        endif;

        return $arr_respuesta;

    }

    /**
     * @param $idReservaLinea Demanda a modificar
     * @param $idMaterialUbicacionFinal Registro Material Ubicacion de donde se ha cogido el material
     * @string $cantidad Cantidad a mover en la reserva
     * @return mixed
     * @throws Exception
     */
    function swap_entre_reservas($idReservaLinea, $idMaterialUbicacionFinal, $cantidad)
    {
        global $bd;
        global $html;
        global $auxiliar;
        global $administrador;

        //ARRAY A DEVOLVER
        $arr_respuesta = array();

        //BLOQUEOS RESERVADO
        $rowTipoBloqueoReservado            = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "RV");
        $rowTipoBloqueoReservadoPlanificado = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "RVP");

        //BUSCO EL TIPO DE BLOQUEO RESERVADO PARA PREPARACION
        $rowTipoBloqueoReservadoParaPreparacion = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", 'RP');

        //BUSCO EL TIPO DE BLOQUEO RESERVADO PARA PREPARACION PREVENTIVO
        $rowTipoBloqueoReservadoParaPreparacionPreventivo = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", 'RPP');

        //BUSCO EL TIPO DE BLOQUEO PREVENTIVO, MATERIAL USADO PARA PREPARAR ESTE TIPO DE PEDIDOS DE TRASLADO DE PENDIENTES
        $rowTipoBloqueoPlanificado = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "SP");

        //BUSCO EL MATERIAL UBICACION FINAL
        $rowMaterialUbicacionFinal = $bd->VerReg("MATERIAL_UBICACION", "ID_MATERIAL_UBICACION", $idMaterialUbicacionFinal);

        //SI EL MATERIAL UBICACION FINAL NO EXISTE DEVUELVO ERROR
        if ($rowMaterialUbicacionFinal == false):
            $arr_resultado['error'] = $auxiliar->traduce("El Registro material - ubicacion final no existe en Base de datos", $administrador->ID_IDIOMA);

            return $arr_resultado;
        endif;

        //BUSCAMOS EL TIPO BLOQUEO
        $idTipoBloqueoFinal = -1;
        if (($rowMaterialUbicacionFinal->ID_TIPO_BLOQUEO == NULL) || ($rowMaterialUbicacionFinal->ID_TIPO_BLOQUEO == $rowTipoBloqueoReservadoParaPreparacion->ID_TIPO_BLOQUEO) || ($rowMaterialUbicacionFinal->ID_TIPO_BLOQUEO == $rowTipoBloqueoReservado->ID_TIPO_BLOQUEO)):
            $idTipoBloqueoFinal = $rowTipoBloqueoReservado->ID_TIPO_BLOQUEO;
        elseif (($rowMaterialUbicacionFinal->ID_TIPO_BLOQUEO == $rowTipoBloqueoPlanificado->ID_TIPO_BLOQUEO) || ($rowMaterialUbicacionFinal->ID_TIPO_BLOQUEO == $rowTipoBloqueoReservadoParaPreparacionPreventivo->ID_TIPO_BLOQUEO) || ($rowMaterialUbicacionFinal->ID_TIPO_BLOQUEO == $rowTipoBloqueoReservadoPlanificado->ID_TIPO_BLOQUEO)):
            $idTipoBloqueoFinal = $rowTipoBloqueoReservadoPlanificado->ID_TIPO_BLOQUEO;
        endif;

        //BUSCO LA RESERVA LINEA ORIGINAL
        $sqlReservaLineaOriginal    = "SELECT RL.* 
                                    FROM RESERVA_LINEA RL 
                                    INNER JOIN RESERVA R ON R.ID_RESERVA = RL.ID_RESERVA 
                                    WHERE RL.ID_RESERVA_LINEA = $idReservaLinea AND RL.ESTADO_LINEA = 'Reservada' AND RL.BAJA = 0 AND R.BAJA = 0 ";
        $resultReservaLineaOriginal = $bd->ExecSQL($sqlReservaLineaOriginal);
        if (($resultReservaLineaOriginal == false) || ($bd->NumRegs($resultReservaLineaOriginal) < 1)):
            $arr_resultado['error'] = $auxiliar->traduce("Reserva original no existe en Base de datos", $administrador->ID_IDIOMA);

            return $arr_resultado;
        else:
            //RECOJO LA LINEA DE RESERVA ORIGINAL
            $rowReservaLineaOriginal = $bd->SigReg($resultReservaLineaOriginal);
        endif;

        //MODIFICO LA RESERVA LINEA ORIGINAL
        if (($cantidad - $rowReservaLineaOriginal->CANTIDAD) > EPSILON_SISTEMA):
            $arr_resultado['error'] = $auxiliar->traduce("Cantidad superior a la reservada", $administrador->ID_IDIOMA) . " " . $auxiliar->traduce("Reserva", $administrador->ID_IDIOMA) . ": " . $rowReservaLineaOriginal->ID_RESERVA . " - " . $auxiliar->traduce("Cantidad", $administrador->ID_IDIOMA) . ": " . $cantidad;

            return $arr_resultado;
        else:
            //DECREMENTO LA CANTIDAD DE LA RESERVA LINEA ORIGINAL
            $sqlUpdate = "UPDATE RESERVA_LINEA SET 
                          CANTIDAD = $rowReservaLineaOriginal->CANTIDAD - $cantidad 
                          WHERE ID_RESERVA_LINEA = $rowReservaLineaOriginal->ID_RESERVA_LINEA";
            $bd->ExecSQL($sqlUpdate);

            //BUSCO LA RESERVA LINEA ORIGINAL ACTUALIZADA
            $rowReservaLineaOriginalActualizada = $bd->VerReg("RESERVA_LINEA", "ID_RESERVA_LINEA", $rowReservaLineaOriginal->ID_RESERVA_LINEA);

            //LOG MOVIMIENTOS
            $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Reserva", $rowReservaLineaOriginal->ID_RESERVA, 'Quitar Cantidad:' . $cantidad . " . Reserva Linea: " . $rowReservaLineaOriginal->ID_RESERVA_LINEA, "RESERVA_LINEA", $rowReservaLineaOriginal, $rowReservaLineaOriginalActualizada);

            //BUSCO SI EXISTE UNA RESERVA LINEA SIMILAR
            $GLOBALS["NotificaErrorPorEmail"] = "No";
            $rowLineaReservaSimilar           = $bd->VerRegRest("RESERVA_LINEA", "ID_RESERVA = $rowReservaLineaOriginalActualizada->ID_RESERVA AND ESTADO_LINEA = 'Reservada' AND ID_MATERIAL = $rowMaterialUbicacionFinal->ID_MATERIAL AND ID_UBICACION = $rowMaterialUbicacionFinal->ID_UBICACION AND ID_MATERIAL_FISICO " . ($rowMaterialUbicacionFinal->ID_MATERIAL_FISICO == NULL ? "IS NULL" : " = $rowMaterialUbicacionFinal->ID_MATERIAL_FISICO") . " AND ID_TIPO_BLOQUEO = " . $idTipoBloqueoFinal . " AND ID_ORDEN_TRABAJO_MOVIMIENTO " . ($rowMaterialUbicacionFinal->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? "IS NULL" : " = $rowMaterialUbicacionFinal->ID_ORDEN_TRABAJO_MOVIMIENTO") . " AND ID_INCIDENCIA_CALIDAD " . ($rowMaterialUbicacionFinal->ID_INCIDENCIA_CALIDAD == NULL ? "IS NULL" : " = $rowMaterialUbicacionFinal->ID_INCIDENCIA_CALIDAD") . " AND BAJA = 0", "No");

            //ACTUACIONES EN FUNCION DE LA CANTIDAD QUE QUEDA EN LA LINEA DE RESERVA
            if ($rowLineaReservaSimilar != false):
                $sqlUpdate = "UPDATE RESERVA_LINEA SET 
                              CANTIDAD = CANTIDAD + $cantidad 
                              WHERE ID_RESERVA_LINEA = $rowLineaReservaSimilar->ID_RESERVA_LINEA";
                $bd->ExecSQL($sqlUpdate);

                //BUSCO LA RESERVA LINEA ORIGINAL ACTUALIZADA CON LOS DATOS NUEVOS
                $rowReservaLineaOriginalRellenada = $bd->VerReg("RESERVA_LINEA", "ID_RESERVA_LINEA", $rowLineaReservaSimilar->ID_RESERVA_LINEA);

                //LOG MOVIMIENTOS
                $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Reserva", $rowReservaLineaOriginalRellenada->ID_RESERVA, 'Poner Cantidad:' . $cantidad . " . Reserva Linea: " . $rowReservaLineaOriginalRellenada->ID_RESERVA_LINEA, "RESERVA_LINEA", $rowLineaReservaSimilar, $rowReservaLineaOriginalRellenada);

                //SI LA LINEA ORIGINAL SE QUEDA SIN CANTIDAD LA MARCAMOS DE BAJA
                if ($rowReservaLineaOriginalActualizada->CANTIDAD < EPSILON_SISTEMA): //CANTIDAD CERO, ACTUALIZO DATOS
                    $sqlUpdate = "UPDATE RESERVA_LINEA SET 
                                  BAJA = 1 
                                  WHERE ID_RESERVA_LINEA = $rowReservaLineaOriginalActualizada->ID_RESERVA_LINEA";
                    $bd->ExecSQL($sqlUpdate);

                    //BUSCO LA RESERVA LINEA ORIGINAL ACTUALIZADA CON LOS DATOS NUEVOS
                    $rowReservaLineaOriginalRellenada = $bd->VerReg("RESERVA_LINEA", "ID_RESERVA_LINEA", $rowReservaLineaOriginalActualizada->ID_RESERVA_LINEA);

                    //LOG MOVIMIENTOS
                    $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Reserva", $rowReservaLineaOriginalActualizada->ID_RESERVA, 'Dar de baja:' . $cantidad . " . Reserva Linea: " . $rowReservaLineaOriginalRellenada->ID_RESERVA_LINEA, "RESERVA_LINEA", $rowReservaLineaOriginalActualizada, $rowReservaLineaOriginalRellenada);
                endif;
            elseif ($rowReservaLineaOriginalActualizada->CANTIDAD < EPSILON_SISTEMA): //CANTIDAD CERO, ACTUALIZO DATOS
                $sqlUpdate = "UPDATE RESERVA_LINEA SET 
                              ESTADO_LINEA = 'Reservada' 
                              , CANTIDAD = $cantidad 
                              , ID_MATERIAL = $rowMaterialUbicacionFinal->ID_MATERIAL 
                              , ID_UBICACION = $rowMaterialUbicacionFinal->ID_UBICACION 
                              , ID_MATERIAL_FISICO = " . ($rowMaterialUbicacionFinal->ID_MATERIAL_FISICO == NULL ? "NULL" : $rowMaterialUbicacionFinal->ID_MATERIAL_FISICO) . " 
                              , ID_TIPO_BLOQUEO = " . $idTipoBloqueoFinal . " 
                              , ID_ORDEN_TRABAJO_MOVIMIENTO = " . ($rowMaterialUbicacionFinal->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? "NULL" : $rowMaterialUbicacionFinal->ID_ORDEN_TRABAJO_MOVIMIENTO) . " 
                              , ID_INCIDENCIA_CALIDAD = " . ($rowMaterialUbicacionFinal->ID_INCIDENCIA_CALIDAD == NULL ? "NULL" : $rowMaterialUbicacionFinal->ID_INCIDENCIA_CALIDAD) . " 
                              WHERE ID_RESERVA_LINEA = $rowReservaLineaOriginal->ID_RESERVA_LINEA";
                $bd->ExecSQL($sqlUpdate);

                //BUSCO LA RESERVA LINEA ORIGINAL ACTUALIZADA CON LOS DATOS NUEVOS
                $rowReservaLineaOriginalRellenada = $bd->VerReg("RESERVA_LINEA", "ID_RESERVA_LINEA", $rowReservaLineaOriginal->ID_RESERVA_LINEA);

                //LOG MOVIMIENTOS
                $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Reserva", $rowReservaLineaOriginalRellenada->ID_RESERVA, 'Poner Cantidad:' . $cantidad . " . Reserva Linea: " . $rowReservaLineaOriginalRellenada->ID_RESERVA_LINEA, "RESERVA_LINEA", $rowReservaLineaOriginalActualizada, $rowReservaLineaOriginalRellenada);
            else: //CANTIDAD MAYOR QUE CERO, CREO REGISTRO LINEA
                $sqlInsert = "INSERT INTO RESERVA_LINEA SET 
                              ID_RESERVA = $rowReservaLineaOriginalActualizada->ID_RESERVA 
                              , FECHA_CREACION_LINEA = '" . date("Y-m-d H:i:s") . "' 
                              , CANTIDAD = $cantidad 
                              , ESTADO_LINEA = 'Reservada' 
                              , ID_MATERIAL = $rowMaterialUbicacionFinal->ID_MATERIAL 
                              , ID_UBICACION = $rowMaterialUbicacionFinal->ID_UBICACION 
                              , ID_MATERIAL_FISICO = " . ($rowMaterialUbicacionFinal->ID_MATERIAL_FISICO == NULL ? "NULL" : $rowMaterialUbicacionFinal->ID_MATERIAL_FISICO) . " 
                              , ID_TIPO_BLOQUEO = " . $idTipoBloqueoFinal . " 
                              , ID_ORDEN_TRABAJO_MOVIMIENTO = " . ($rowMaterialUbicacionFinal->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? "NULL" : $rowMaterialUbicacionFinal->ID_ORDEN_TRABAJO_MOVIMIENTO) . " 
                              , ID_INCIDENCIA_CALIDAD = " . ($rowMaterialUbicacionFinal->ID_INCIDENCIA_CALIDAD == NULL ? "NULL" : $rowMaterialUbicacionFinal->ID_INCIDENCIA_CALIDAD);
                $bd->ExecSQL($sqlInsert);

                //BUSCO LA RESERVA LINEA ORIGINAL ACTUALIZADA CON LOS DATOS NUEVOS
                $rowReservaLineaOriginalRellenada = $bd->VerReg("RESERVA_LINEA", "ID_RESERVA_LINEA", $rowReservaLineaOriginal->ID_RESERVA_LINEA);

                //LOG MOVIMIENTOS
                $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Reserva", $rowReservaLineaOriginalRellenada->ID_RESERVA, 'Poner Cantidad:' . $cantidad . " . Reserva Linea: " . $rowReservaLineaOriginalRellenada->ID_RESERVA_LINEA, "RESERVA_LINEA", $rowReservaLineaOriginalActualizada, $rowReservaLineaOriginalRellenada);
            endif;

            //BUSCAMOS LA RESERVA
            $rowReserva = $bd->VerReg("RESERVA", "ID_RESERVA", $rowReservaLineaOriginal->ID_RESERVA);

            //BUSCAMOS LA DEMANDA
            $rowDemanda = $bd->VerReg("DEMANDA", "ID_DEMANDA", $rowReserva->ID_DEMANDA);

            //SI LA DEMANDA ES DE TIPO OT, ACTUALIZAMOS LAS TRANSFERENCIAS DE PENDIENTES SI LAS HAY
            if ($rowDemanda->TIPO_DEMANDA == 'OT'):
                $this->actualizar_transferencias_pendientes($rowDemanda->ID_DEMANDA);
            endif;
        endif;
    }

    /**
     * @param $idDemanda Demanda a modificar
     * @param $idMaterialUbicacionInicial Registro Material Ubicacion de donde se deberia coger el material
     * @param $idMaterialUbicacionFinal Registro Material Ubicacion de donde se ha cogido el material
     * @string $cantidad Cantidad a mover entre reservas
     * @return mixed
     * @throws Exception
     */
    function swap_entre_reservas_PSL_especifico($idPedidoSalidaLinea, $idMaterialUbicacionInicial, $idMaterialUbicacionFinal, $cantidad)
    {
        global $bd;
        global $html;
        global $auxiliar;
        global $administrador;

        //ARRAY A DEVOLVER
        $arr_respuesta = array();

        //BLOQUEOS RESERVADO
        $rowTipoBloqueoReservado            = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "RV");
        $rowTipoBloqueoReservadoPlanificado = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "RVP");

        //BUSCO EL TIPO DE BLOQUEO RESERVADO PARA PREPARACION
        $rowTipoBloqueoReservadoParaPreparacion = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", 'RP');

        //BUSCO EL TIPO DE BLOQUEO RESERVADO PARA PREPARACION PREVENTIVO
        $rowTipoBloqueoReservadoParaPreparacionPreventivo = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", 'RPP');

        //BUSCO EL TIPO DE BLOQUEO PREVENTIVO, MATERIAL USADO PARA PREPARAR ESTE TIPO DE PEDIDOS DE TRASLADO DE PENDIENTES
        $rowTipoBloqueoPlanificado = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "SP");

        //BUSCO EL MATERIAL UBICACION INICIAL
        $GLOBALS['NotificaErrorPorEmail'] = 'No';
        $rowMaterialUbicacionInicial = $bd->VerReg("MATERIAL_UBICACION", "ID_MATERIAL_UBICACION", $idMaterialUbicacionInicial, 'No');
        unset($GLOBALS['NotificaErrorPorEmail']);

        //SI EL MATERIAL UBICACION INICIAL NO EXISTE DEVUELVO ERROR
        if ($rowMaterialUbicacionInicial == false):
            $arr_resultado['error'] = $auxiliar->traduce("El Registro material - ubicacion inicial no existe en Base de datos", $administrador->ID_IDIOMA);

            return $arr_resultado;
        endif;

        //BUSCO EL MATERIAL UBICACION FINAL
        $GLOBALS['NotificaErrorPorEmail'] = 'No';
        $rowMaterialUbicacionFinal = $bd->VerReg("MATERIAL_UBICACION", "ID_MATERIAL_UBICACION", $idMaterialUbicacionFinal, 'No');
        unset($GLOBALS['NotificaErrorPorEmail']);

        //SI EL MATERIAL UBICACION FINAL NO EXISTE DEVUELVO ERROR
        if ($rowMaterialUbicacionFinal == false):
            $arr_resultado['error'] = $auxiliar->traduce("El Registro material - ubicacion final no existe en Base de datos", $administrador->ID_IDIOMA);

            return $arr_resultado;
        endif;

        //BUSCO LA RESERVA LINEA ORIGINAL
        $sqlReservaLineaOriginal    = "SELECT RL.* 
                                    FROM RESERVA_LINEA RL 
                                    INNER JOIN RESERVA R ON R.ID_RESERVA = RL.ID_RESERVA 
                                    INNER JOIN DEMANDA D ON D.ID_DEMANDA = R.ID_DEMANDA
                                    WHERE D.ID_PEDIDO_SALIDA_LINEA = $idPedidoSalidaLinea AND D.TIPO_DEMANDA = 'Pedido' AND RL.ESTADO_LINEA = 'Finalizada' AND RL.BAJA = 0 AND R.BAJA = 0 AND D.BAJA = 0
                                    AND RL.ID_MATERIAL = $rowMaterialUbicacionInicial->ID_MATERIAL AND RL.ID_UBICACION = $rowMaterialUbicacionInicial->ID_UBICACION AND RL.ID_MATERIAL_FISICO " . ($rowMaterialUbicacionInicial->ID_MATERIAL_FISICO == NULL ? "IS NULL" : " = $rowMaterialUbicacionInicial->ID_MATERIAL_FISICO") . " AND RL.ID_TIPO_BLOQUEO = " . ((($rowMaterialUbicacionInicial->ID_TIPO_BLOQUEO == NULL) || ($rowMaterialUbicacionInicial->ID_TIPO_BLOQUEO == $rowTipoBloqueoReservadoParaPreparacion->ID_TIPO_BLOQUEO)) ? $rowTipoBloqueoReservado->ID_TIPO_BLOQUEO : ((($rowMaterialUbicacionInicial->ID_TIPO_BLOQUEO == $rowTipoBloqueoPlanificado->ID_TIPO_BLOQUEO) || ($rowMaterialUbicacionInicial->ID_TIPO_BLOQUEO == $rowTipoBloqueoReservadoParaPreparacionPreventivo->ID_TIPO_BLOQUEO)) ? $rowTipoBloqueoReservadoPlanificado->ID_TIPO_BLOQUEO : -1)) . " AND RL.ID_ORDEN_TRABAJO_MOVIMIENTO " . ($rowMaterialUbicacionInicial->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? "IS NULL" : " = $rowMaterialUbicacionInicial->ID_ORDEN_TRABAJO_MOVIMIENTO") . " AND RL.ID_INCIDENCIA_CALIDAD " . ($rowMaterialUbicacionInicial->ID_INCIDENCIA_CALIDAD == NULL ? "IS NULL" : " = $rowMaterialUbicacionInicial->ID_INCIDENCIA_CALIDAD");
        $resultReservaLineaOriginal = $bd->ExecSQL($sqlReservaLineaOriginal);
        if (($resultReservaLineaOriginal == false) || ($bd->NumRegs($resultReservaLineaOriginal) < 1)):
            $arr_resultado['error'] = $auxiliar->traduce("Reserva original no existe en Base de datos", $administrador->ID_IDIOMA);

            return $arr_resultado;
        endif;

        //RECOJO LA LINEA DE RESERVA ORIGINAL
        $cantidadPdte = $cantidad;
        while (($rowReservaLineaOriginal = $bd->SigReg($resultReservaLineaOriginal)) && ($cantidadPdte > EPSILON_SISTEMA)):

            //ACTUALIZAMOS CANTIDADES
            $cantidadSwap = min($cantidadPdte, $rowReservaLineaOriginal->CANTIDAD);
            $cantidadPdte = $cantidadPdte - $cantidadSwap;

            //DECREMENTO LA CANTIDAD DE LA RESERVA LINEA ORIGINAL
            $sqlUpdate = "UPDATE RESERVA_LINEA SET 
                          CANTIDAD = CANTIDAD - $cantidadSwap 
                          WHERE ID_RESERVA_LINEA = $rowReservaLineaOriginal->ID_RESERVA_LINEA";
            $bd->ExecSQL($sqlUpdate);

            //BUSCO LA RESERVA LINEA ORIGINAL ACTUALIZADA
            $rowReservaLineaOriginalActualizada = $bd->VerReg("RESERVA_LINEA", "ID_RESERVA_LINEA", $rowReservaLineaOriginal->ID_RESERVA_LINEA);

            //LOG MOVIMIENTOS
            $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Reserva", $rowReservaLineaOriginal->ID_RESERVA, 'Quitar Cantidad:' . $cantidadSwap . " . Reserva Linea: " . $rowReservaLineaOriginal->ID_RESERVA_LINEA, "RESERVA_LINEA", $rowReservaLineaOriginal, $rowReservaLineaOriginalActualizada);

            //BUSCO SI EXISTE UNA RESERVA LINEA SIMILAR
            $GLOBALS["NotificaErrorPorEmail"] = "No";
            $rowLineaReservaSimilar           = $bd->VerRegRest("RESERVA_LINEA", "ID_RESERVA = $rowReservaLineaOriginalActualizada->ID_RESERVA AND ESTADO_LINEA = 'Finalizada' AND ID_MATERIAL = $rowMaterialUbicacionFinal->ID_MATERIAL AND ID_UBICACION = $rowMaterialUbicacionFinal->ID_UBICACION AND ID_MATERIAL_FISICO " . ($rowMaterialUbicacionFinal->ID_MATERIAL_FISICO == NULL ? "IS NULL" : " = $rowMaterialUbicacionFinal->ID_MATERIAL_FISICO") . " AND ID_TIPO_BLOQUEO " . ((($rowMaterialUbicacionFinal->ID_TIPO_BLOQUEO == NULL) || ($rowMaterialUbicacionFinal->ID_TIPO_BLOQUEO == $rowTipoBloqueoReservadoParaPreparacion->ID_TIPO_BLOQUEO)) ? " = $rowTipoBloqueoReservado->ID_TIPO_BLOQUEO" : ((($rowMaterialUbicacionFinal->ID_TIPO_BLOQUEO == $rowTipoBloqueoPlanificado->ID_TIPO_BLOQUEO) || ($rowMaterialUbicacionFinal->ID_TIPO_BLOQUEO == $rowTipoBloqueoReservadoParaPreparacionPreventivo->ID_TIPO_BLOQUEO)) ? " = $rowTipoBloqueoReservadoPlanificado->ID_TIPO_BLOQUEO" : " = -1")) . " AND ID_ORDEN_TRABAJO_MOVIMIENTO " . ($rowMaterialUbicacionFinal->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? "IS NULL" : " = $rowMaterialUbicacionFinal->ID_ORDEN_TRABAJO_MOVIMIENTO") . " AND ID_INCIDENCIA_CALIDAD " . ($rowMaterialUbicacionFinal->ID_INCIDENCIA_CALIDAD == NULL ? "IS NULL" : " = $rowMaterialUbicacionFinal->ID_INCIDENCIA_CALIDAD") . " AND BAJA = 0", "No");

            //ACTUACIONES EN FUNCION DE LA CANTIDAD QUE QUEDA EN LA LINEA DE RESERVA
            if ($rowLineaReservaSimilar != false):
                $sqlUpdate = "UPDATE RESERVA_LINEA SET 
                              CANTIDAD = CANTIDAD + $cantidadSwap 
                              WHERE ID_RESERVA_LINEA = $rowLineaReservaSimilar->ID_RESERVA_LINEA";
                $bd->ExecSQL($sqlUpdate);

                //BUSCO LA RESERVA LINEA ORIGINAL ACTUALIZADA CON LOS DATOS NUEVOS
                $rowReservaLineaOriginalRellenada = $bd->VerReg("RESERVA_LINEA", "ID_RESERVA_LINEA", $rowLineaReservaSimilar->ID_RESERVA_LINEA);

                //LOG MOVIMIENTOS
                $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Reserva", $rowReservaLineaOriginalRellenada->ID_RESERVA, 'Poner Cantidad:' . $cantidadSwap . " . Reserva Linea: " . $rowReservaLineaOriginalRellenada->ID_RESERVA_LINEA, "RESERVA_LINEA", $rowLineaReservaSimilar, $rowReservaLineaOriginalRellenada);

                //SI LA LINEA ORIGINAL SE QUEDA SIN CANTIDAD LA MARCAMOS DE BAJA
                if ($rowReservaLineaOriginalActualizada->CANTIDAD < EPSILON_SISTEMA): //CANTIDAD CERO, ACTUALIZO DATOS
                    $sqlUpdate = "UPDATE RESERVA_LINEA SET 
                                  BAJA = 1 
                                  WHERE ID_RESERVA_LINEA = $rowReservaLineaOriginalActualizada->ID_RESERVA_LINEA";
                    $bd->ExecSQL($sqlUpdate);

                    //BUSCO LA RESERVA LINEA ORIGINAL ACTUALIZADA CON LOS DATOS NUEVOS
                    $rowReservaLineaOriginalRellenada = $bd->VerReg("RESERVA_LINEA", "ID_RESERVA_LINEA", $rowReservaLineaOriginalActualizada->ID_RESERVA_LINEA);

                    //LOG MOVIMIENTOS
                    $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Reserva", $rowReservaLineaOriginalActualizada->ID_RESERVA, 'Dar de baja:' . $cantidadSwap . " . Reserva Linea: " . $rowReservaLineaOriginalActualizada->ID_RESERVA_LINEA, "RESERVA_LINEA", $rowReservaLineaOriginalActualizada, $rowReservaLineaOriginalActualizada);
                endif;
            elseif ($rowReservaLineaOriginalActualizada->CANTIDAD < EPSILON_SISTEMA): //CANTIDAD CERO, ACTUALIZO DATOS
                $sqlUpdate = "UPDATE RESERVA_LINEA SET 
                              ESTADO_LINEA = 'Finalizada' 
                              , CANTIDAD = $cantidadSwap 
                              , ID_MATERIAL = $rowMaterialUbicacionFinal->ID_MATERIAL 
                              , ID_UBICACION = $rowMaterialUbicacionFinal->ID_UBICACION 
                              , ID_MATERIAL_FISICO = " . ($rowMaterialUbicacionFinal->ID_MATERIAL_FISICO == NULL ? "NULL" : $rowMaterialUbicacionFinal->ID_MATERIAL_FISICO) . " 
                              , ID_TIPO_BLOQUEO = " . ((($rowMaterialUbicacionFinal->ID_TIPO_BLOQUEO == NULL) || ($rowMaterialUbicacionFinal->ID_TIPO_BLOQUEO == $rowTipoBloqueoReservadoParaPreparacion->ID_TIPO_BLOQUEO)) ? $rowTipoBloqueoReservado->ID_TIPO_BLOQUEO : ((($rowMaterialUbicacionFinal->ID_TIPO_BLOQUEO == $rowTipoBloqueoPlanificado->ID_TIPO_BLOQUEO) || ($rowMaterialUbicacionFinal->ID_TIPO_BLOQUEO == $rowTipoBloqueoReservadoParaPreparacionPreventivo->ID_TIPO_BLOQUEO)) ? $rowTipoBloqueoReservadoPlanificado->ID_TIPO_BLOQUEO : -1)) . " 
                              , ID_ORDEN_TRABAJO_MOVIMIENTO = " . ($rowMaterialUbicacionFinal->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? "NULL" : $rowMaterialUbicacionFinal->ID_ORDEN_TRABAJO_MOVIMIENTO) . " 
                              , ID_INCIDENCIA_CALIDAD = " . ($rowMaterialUbicacionFinal->ID_INCIDENCIA_CALIDAD == NULL ? "NULL" : $rowMaterialUbicacionFinal->ID_INCIDENCIA_CALIDAD) . " 
                              WHERE ID_RESERVA_LINEA = $rowReservaLineaOriginal->ID_RESERVA_LINEA";
                $bd->ExecSQL($sqlUpdate);

                //BUSCO LA RESERVA LINEA ORIGINAL ACTUALIZADA CON LOS DATOS NUEVOS
                $rowReservaLineaOriginalRellenada = $bd->VerReg("RESERVA_LINEA", "ID_RESERVA_LINEA", $rowReservaLineaOriginal->ID_RESERVA_LINEA);

                //LOG MOVIMIENTOS
                $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Reserva", $rowReservaLineaOriginalRellenada->ID_RESERVA, 'Poner Cantidad:' . $cantidadSwap . " . Reserva Linea: " . $rowReservaLineaOriginalRellenada->ID_RESERVA_LINEA, "RESERVA_LINEA", $rowReservaLineaOriginalActualizada, $rowReservaLineaOriginalRellenada);
            else: //CANTIDAD MAYOR QUE CERO, CREO REGISTRO LINEA
                $sqlInsert = "INSERT INTO RESERVA_LINEA SET 
                              ID_RESERVA = $rowReservaLineaOriginalActualizada->ID_RESERVA 
                              , FECHA_CREACION_LINEA = '" . date("Y-m-d H:i:s") . "' 
                              , CANTIDAD = $cantidadSwap 
                              , ESTADO_LINEA = 'Finalizada' 
                              , ID_MATERIAL = $rowMaterialUbicacionFinal->ID_MATERIAL 
                              , ID_UBICACION = $rowMaterialUbicacionFinal->ID_UBICACION 
                              , ID_MATERIAL_FISICO = " . ($rowMaterialUbicacionFinal->ID_MATERIAL_FISICO == NULL ? "NULL" : $rowMaterialUbicacionFinal->ID_MATERIAL_FISICO) . " 
                              , ID_TIPO_BLOQUEO = " . ((($rowMaterialUbicacionFinal->ID_TIPO_BLOQUEO == NULL) || ($rowMaterialUbicacionFinal->ID_TIPO_BLOQUEO == $rowTipoBloqueoReservadoParaPreparacion->ID_TIPO_BLOQUEO)) ? $rowTipoBloqueoReservado->ID_TIPO_BLOQUEO : ((($rowMaterialUbicacionFinal->ID_TIPO_BLOQUEO == $rowTipoBloqueoPlanificado->ID_TIPO_BLOQUEO) || ($rowMaterialUbicacionFinal->ID_TIPO_BLOQUEO == $rowTipoBloqueoReservadoParaPreparacionPreventivo->ID_TIPO_BLOQUEO)) ? $rowTipoBloqueoReservadoPlanificado->ID_TIPO_BLOQUEO : -1)) . " 
                              , ID_ORDEN_TRABAJO_MOVIMIENTO = " . ($rowMaterialUbicacionFinal->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? "NULL" : $rowMaterialUbicacionFinal->ID_ORDEN_TRABAJO_MOVIMIENTO) . " 
                              , ID_INCIDENCIA_CALIDAD = " . ($rowMaterialUbicacionFinal->ID_INCIDENCIA_CALIDAD == NULL ? "NULL" : $rowMaterialUbicacionFinal->ID_INCIDENCIA_CALIDAD);
                $bd->ExecSQL($sqlInsert);

                //BUSCO LA RESERVA LINEA ORIGINAL ACTUALIZADA CON LOS DATOS NUEVOS
                $rowReservaLineaOriginalRellenada = $bd->VerReg("RESERVA_LINEA", "ID_RESERVA_LINEA", $rowReservaLineaOriginal->ID_RESERVA_LINEA);

                //LOG MOVIMIENTOS
                $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Reserva", $rowReservaLineaOriginalRellenada->ID_RESERVA, 'Poner Cantidad:' . $cantidadSwap . " . Reserva Linea: " . $rowReservaLineaOriginalRellenada->ID_RESERVA_LINEA, "RESERVA_LINEA", $rowReservaLineaOriginalActualizada, $rowReservaLineaOriginalRellenada);
            endif;

        endwhile; //FIN RECORRER LINEAS

        //MODIFICO LA RESERVA LINEA ORIGINAL
        if ($cantidadPdte > EPSILON_SISTEMA):
            $arr_resultado['error'] = $auxiliar->traduce("Cantidad superior a la reservada", $administrador->ID_IDIOMA) . " " . $auxiliar->traduce("Cantidad", $administrador->ID_IDIOMA) . ": " . $cantidad;

            return $arr_resultado;

        endif;
    }


    /**
     * @param $idReservaLineaRobar => Reserva Linea que se quiere usar en el consumo
     * @param $idOrdenTrabajoLineaConsumo => Línea que consume
     * @param $cantidad => Cantidad a Consumir
     * SI HAY RESERVA EN LA OTL (y es el mismo almacen) SE HACE UN SWAP. Si no es posible el SWAP, la Reserva Robada queda Pdte Tratar
     */
    function robo_reserva_en_consumo($idReservaLineaRobar, $idOrdenTrabajoLineaConsumo, $cantidad, $idOrdenTrabajoConsumo = '')
    {
        global $bd;
        global $html;
        global $auxiliar;
        global $administrador;
        global $orden_trabajo;
        global $necesidad;

        //ARRAY A DEVOLVER
        $arr_respuesta = array();

        //OBTENEMOS LA RESERVA LINEA
        $rowReservaLineaRobar = $bd->VerReg("RESERVA_LINEA", "ID_RESERVA_LINEA", $idReservaLineaRobar);
        $rowReservaRobar      = $bd->VerReg("RESERVA", "ID_RESERVA", $rowReservaLineaRobar->ID_RESERVA);
        $rowDemandaRobar      = $bd->VerReg("DEMANDA", "ID_DEMANDA", $rowReservaRobar->ID_DEMANDA);

        //OBTENEMOS LA LINEA DE LA ORDEN DE TRABAJO Y LA ORDEN DE TRABAJO
        if ($idOrdenTrabajoLineaConsumo != ''):
            $rowOTLConsumo = $bd->VerReg("ORDEN_TRABAJO_LINEA", "ID_ORDEN_TRABAJO_LINEA", $idOrdenTrabajoLineaConsumo);
            $rowOTConsumo  = $bd->VerReg("ORDEN_TRABAJO", "ID_ORDEN_TRABAJO", $rowOTLConsumo->ID_ORDEN_TRABAJO);

            //BUSCO EL ALMACEN DE LA LINEA DE LA ORDEN DE TRABAJO LINEA
            $rowAlmacenOrdenTrabajoLinea = $bd->VerReg("ALMACEN", "ID_ALMACEN", $rowOTLConsumo->ID_ALMACEN);
        elseif ($idOrdenTrabajoConsumo != ''):
            $rowOTConsumo  = $bd->VerReg("ORDEN_TRABAJO", "ID_ORDEN_TRABAJO", $idOrdenTrabajoConsumo);
        endif;

        if ($idOrdenTrabajoConsumo == ''):
            //SI EL ALMACEN ESTA MARCADO COMO ALMACEN DE CABECERA CON GESTION DE OTS, PERMITIMOS EL ROBO A DEMANDAS DE TIPO 'Pedido'
            if (($rowAlmacenOrdenTrabajoLinea->CABECERA_CON_GESTION_OTS == 0) && ($rowDemandaRobar->TIPO_DEMANDA != 'OT')):
                $arr_respuesta['error'] = $auxiliar->traduce("El robo entre reservas al consumir solo puede ser entre Demandas de Tipo OT", $administrador->ID_IDIOMA);

                return $arr_respuesta;
            endif;
        endif;

        if ($rowReservaLineaRobar->CANTIDAD < $cantidad):
            $arr_respuesta['error'] = $auxiliar->traduce("No hay suficiente cantidad en la Reserva a robar en el Consumo", $administrador->ID_IDIOMA);

            return $arr_respuesta;
        endif;

        //CANTIDAD
        $cantidadPdteReasignar = $cantidad;

        //A LA LINEA ROBADA LE QUITAMOS LA CANTIDAD
        //SI LA LINEA ORIGINAL SE QUEDA SIN CANTIDAD LA MARCAMOS DE BAJA
        if ($rowReservaLineaRobar->CANTIDAD - $cantidad < EPSILON_SISTEMA):
            $sqlUpdate = "UPDATE RESERVA_LINEA SET 
                          BAJA = 1 
                          WHERE ID_RESERVA_LINEA = $rowReservaLineaRobar->ID_RESERVA_LINEA";
            $bd->ExecSQL($sqlUpdate);

            //LOG MOVIMIENTOS
            if ($idOrdenTrabajoLineaConsumo != ''):
                $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Reserva", $rowReservaLineaRobar->ID_RESERVA, 'Baja por consumo Cantidad:' . $cantidad . " en la OT: $rowOTConsumo->ORDEN_TRABAJO_SAP. Lin: $rowOTLConsumo->LINEA_ORDEN_TRABAJO_SAP. Reserva Linea: " . $rowReservaLineaRobar->ID_RESERVA_LINEA);
            elseif ($idOrdenTrabajoConsumo != ''):
                $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Reserva", $rowReservaLineaRobar->ID_RESERVA, 'Baja por consumo Cantidad:' . $cantidad . " en la OT: $rowOTConsumo->ORDEN_TRABAJO_SAP. Reserva Linea: " . $rowReservaLineaRobar->ID_RESERVA_LINEA);
            endif;
        else:
            $sqlUpdate = "UPDATE RESERVA_LINEA SET 
                          CANTIDAD = CANTIDAD - $cantidad 
                          WHERE ID_RESERVA_LINEA = $rowReservaLineaRobar->ID_RESERVA_LINEA";
            $bd->ExecSQL($sqlUpdate);

            //LOG MOVIMIENTOS
            if ($idOrdenTrabajoLineaConsumo != ''):
                $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Reserva", $rowReservaLineaRobar->ID_RESERVA, 'Quitar Cantidad:' . $cantidad . " por consumo en la OT: $rowOTConsumo->ORDEN_TRABAJO_SAP. Lin: $rowOTLConsumo->LINEA_ORDEN_TRABAJO_SAP. Reserva Linea: " . $rowReservaLineaRobar->ID_RESERVA_LINEA);
            elseif ($idOrdenTrabajoConsumo != ''):
                $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Reserva", $rowReservaLineaRobar->ID_RESERVA, 'Quitar Cantidad:' . $cantidad . " por consumo en la OT: $rowOTConsumo->ORDEN_TRABAJO_SAP. Reserva Linea: " . $rowReservaLineaRobar->ID_RESERVA_LINEA);
            endif;
        endif;

        if ($idOrdenTrabajoLineaConsumo != ''):
            //BUSCO LA DEMANDA DEL CONSUMO
            $rowDemanda = $this->get_demanda("OT", $idOrdenTrabajoLineaConsumo);
            if ($rowDemanda != false):
                //SI LA CANTIDAD RESERVADA DE LA OT QUE ROBA ES MENOR QUE LA CANTIDAD A ROBAR LANZAMOS LA T01 PARA LA OT A LA QUE LE ROBAN
                if ($this->get_cantidad_reserva_demanda($rowDemanda->ID_DEMANDA) < $cantidad):
                    //ME GUARDO LA OT A LA QUE LE ROBAN STOCK PARA LANZAR INTERFAZ T01
                    $arr_respuesta['lanzar_T01'] = $rowDemandaRobar->ID_ORDEN_TRABAJO_LINEA;
                endif;

                //CALCULO LAS RESERVAS LINEAS DE LA OT QUE SE CONSUME
                $resultLineasReserva = $this->get_lineas_reservas_demanda($rowDemanda->ID_DEMANDA, 'Reservada');
                if ($bd->NumRegs($resultLineasReserva) > 0):
                    //RECORREMOS LO RESERVADO EN EL CONSUMO (LA QUE ROBA)
                    while (($rowReservaLineaConsumo = $bd->SigReg($resultLineasReserva)) && ($cantidadPdteReasignar > EPSILON_SISTEMA)):

                        //SI EL ALMACEN ES EL MISMO, HACEMOS UN SWAP
                        if ($rowReservaRobar->ID_ALMACEN_RESERVA == $rowReservaLineaConsumo->ID_ALMACEN_RESERVA):

                            //CONTROLAMOS CANTIDADES
                            if ($cantidadPdteReasignar > $rowReservaLineaConsumo->CANTIDAD):
                                $cantidadPdteReasignar = $cantidadPdteReasignar - $rowReservaLineaConsumo->CANTIDAD;
                                $cantidadRobo          = $rowReservaLineaConsumo->CANTIDAD;
                            else:
                                $cantidadRobo          = $cantidadPdteReasignar;
                                $cantidadPdteReasignar = 0;
                            endif;

                            //BUSCO SI EXISTE UNA RESERVA LINEA EN LA ROBADA SIMILAR A LA DEL CONSUMO PARA HACER SWAP
                            $GLOBALS["NotificaErrorPorEmail"] = "No";
                            $rowLineaReservaSimilar           = $bd->VerRegRest("RESERVA_LINEA", "ID_RESERVA = $rowReservaLineaRobar->ID_RESERVA AND ESTADO_LINEA = 'Reservada' AND ID_MATERIAL = $rowReservaLineaConsumo->ID_MATERIAL AND ID_UBICACION = $rowReservaLineaConsumo->ID_UBICACION AND ID_MATERIAL_FISICO " . ($rowReservaLineaConsumo->ID_MATERIAL_FISICO == NULL ? "IS NULL" : " = $rowReservaLineaConsumo->ID_MATERIAL_FISICO") . " AND ID_TIPO_BLOQUEO " . ($rowReservaLineaConsumo->ID_TIPO_BLOQUEO != NULL ? " = $rowReservaLineaConsumo->ID_TIPO_BLOQUEO" : " IS NULL ") . " AND ID_ORDEN_TRABAJO_MOVIMIENTO " . ($rowReservaLineaConsumo->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? " IS NULL" : " = $rowReservaLineaConsumo->ID_ORDEN_TRABAJO_MOVIMIENTO") . " AND ID_INCIDENCIA_CALIDAD " . ($rowReservaLineaConsumo->ID_INCIDENCIA_CALIDAD == NULL ? " IS NULL " : " = $rowReservaLineaConsumo->ID_INCIDENCIA_CALIDAD") . " AND BAJA = 0", "No");
                            if ($rowLineaReservaSimilar != false):
                                $sqlUpdate = "UPDATE RESERVA_LINEA SET 
                                              CANTIDAD = CANTIDAD + $cantidadRobo 
                                              WHERE ID_RESERVA_LINEA = $rowLineaReservaSimilar->ID_RESERVA_LINEA";
                                $bd->ExecSQL($sqlUpdate);

                                //BUSCO LA RESERVA LINEA ORIGINAL ACTUALIZADA CON LOS DATOS NUEVOS
                                $rowReservaLineaOriginalRellenada = $bd->VerReg("RESERVA_LINEA", "ID_RESERVA_LINEA", $rowLineaReservaSimilar->ID_RESERVA_LINEA);

                                //LOG MOVIMIENTOS
                                $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Reserva", $rowReservaLineaOriginalRellenada->ID_RESERVA, 'Swap Cantidad:' . $cantidadRobo . " por Robo al Consumir Reserva: $rowReservaLineaConsumo->ID_RESERVA. Reserva Linea: " . $rowReservaLineaOriginalRellenada->ID_RESERVA_LINEA, "RESERVA_LINEA", $rowLineaReservaSimilar, $rowReservaLineaOriginalRellenada);

                            else: //CANTIDAD MAYOR QUE CERO, CREO REGISTRO LINEA
                                $sqlInsert = "INSERT INTO RESERVA_LINEA SET 
                                              ID_RESERVA = $rowReservaLineaRobar->ID_RESERVA 
                                              , FECHA_CREACION_LINEA = '" . date("Y-m-d H:i:s") . "' 
                                              , RESERVA_CON_SUSTITUTIVO = " . ($rowReservaLineaRobar->ID_MATERIAL == $rowReservaLineaConsumo->ID_MATERIAL ? 0 : 1) . "
                                              , CANTIDAD = $cantidadRobo 
                                              , ESTADO_LINEA = 'Reservada' 
                                              , ID_MATERIAL = $rowReservaLineaConsumo->ID_MATERIAL 
                                              , ID_UBICACION = $rowReservaLineaConsumo->ID_UBICACION 
                                              , ID_MATERIAL_FISICO = " . ($rowReservaLineaConsumo->ID_MATERIAL_FISICO == NULL ? "NULL" : $rowReservaLineaConsumo->ID_MATERIAL_FISICO) . " 
                                              , ID_TIPO_BLOQUEO = " . ($rowReservaLineaConsumo->ID_TIPO_BLOQUEO == NULL ? "NULL" : $rowReservaLineaConsumo->ID_TIPO_BLOQUEO) . " 
                                              , ID_ORDEN_TRABAJO_MOVIMIENTO = " . ($rowReservaLineaConsumo->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? "NULL" : $rowReservaLineaConsumo->ID_ORDEN_TRABAJO_MOVIMIENTO) . " 
                                              , ID_INCIDENCIA_CALIDAD = " . ($rowReservaLineaConsumo->ID_INCIDENCIA_CALIDAD == NULL ? "NULL" : $rowReservaLineaConsumo->ID_INCIDENCIA_CALIDAD);
                                $bd->ExecSQL($sqlInsert);

                                //LOG MOVIMIENTOS
                                $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Reserva", $rowReservaLineaRobar->ID_RESERVA, 'Swap Cantidad:' . $cantidadRobo . " por Robo al Consumir Reserva: $rowReservaLineaConsumo->ID_RESERVA. Reserva Linea: " . $bd->IdAsignado());
                            endif;

                            //BUSCO SI EXISTE UNA RESERVA LINEA EN LA QUE ROBA SIMILAR A LA QUE ESTMAOS ROBANDO (LA DEJAMOS YA FINALIZADA)
                            $GLOBALS["NotificaErrorPorEmail"] = "No";
                            $rowLineaReservaSimilar           = $bd->VerRegRest("RESERVA_LINEA", "ID_RESERVA = $rowReservaLineaConsumo->ID_RESERVA AND ESTADO_LINEA = 'Finalizada' AND ID_MATERIAL = $rowReservaLineaRobar->ID_MATERIAL AND ID_UBICACION = $rowReservaLineaRobar->ID_UBICACION AND ID_MATERIAL_FISICO " . ($rowReservaLineaRobar->ID_MATERIAL_FISICO == NULL ? "IS NULL" : " = $rowReservaLineaRobar->ID_MATERIAL_FISICO") . " AND ID_TIPO_BLOQUEO " . ($rowReservaLineaRobar->ID_TIPO_BLOQUEO != NULL ? " = $rowReservaLineaRobar->ID_TIPO_BLOQUEO" : " IS NULL ") . " AND ID_ORDEN_TRABAJO_MOVIMIENTO " . ($rowReservaLineaRobar->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? " IS NULL" : " = $rowReservaLineaRobar->ID_ORDEN_TRABAJO_MOVIMIENTO") . " AND ID_INCIDENCIA_CALIDAD " . ($rowReservaLineaRobar->ID_INCIDENCIA_CALIDAD == NULL ? " IS NULL " : " = $rowReservaLineaRobar->ID_INCIDENCIA_CALIDAD") . " AND BAJA = 0", "No");
                            if ($rowLineaReservaSimilar != false):
                                $sqlUpdate = "UPDATE RESERVA_LINEA SET 
                                              CANTIDAD = CANTIDAD + $cantidadRobo 
                                              WHERE ID_RESERVA_LINEA = $rowLineaReservaSimilar->ID_RESERVA_LINEA";
                                $bd->ExecSQL($sqlUpdate);

                                //BUSCO LA RESERVA LINEA ORIGINAL ACTUALIZADA CON LOS DATOS NUEVOS
                                $rowReservaLineaOriginalRellenada = $bd->VerReg("RESERVA_LINEA", "ID_RESERVA_LINEA", $rowLineaReservaSimilar->ID_RESERVA_LINEA);

                                //LOG MOVIMIENTOS
                                $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Reserva", $rowReservaLineaOriginalRellenada->ID_RESERVA, 'Swap Cantidad:' . $cantidadRobo . " por Consumir de la Reserva: $rowReservaLineaRobar->ID_RESERVA. Reserva Linea: " . $rowReservaLineaOriginalRellenada->ID_RESERVA_LINEA, "RESERVA_LINEA", $rowLineaReservaSimilar, $rowReservaLineaOriginalRellenada);

                            else: //CANTIDAD MAYOR QUE CERO, CREO REGISTRO LINEA
                                $sqlInsert = "INSERT INTO RESERVA_LINEA SET 
                                              ID_RESERVA = $rowReservaLineaConsumo->ID_RESERVA 
                                              , FECHA_CREACION_LINEA = '" . date("Y-m-d H:i:s") . "' 
                                              , RESERVA_CON_SUSTITUTIVO = " . ($rowReservaLineaRobar->ID_MATERIAL == $rowReservaLineaConsumo->ID_MATERIAL ? 0 : 1) . "
                                              , CANTIDAD = $cantidadRobo 
                                              , ESTADO_LINEA = 'Finalizada' 
                                              , ID_MATERIAL = $rowReservaLineaRobar->ID_MATERIAL 
                                              , ID_UBICACION = $rowReservaLineaRobar->ID_UBICACION 
                                              , ID_MATERIAL_FISICO = " . ($rowReservaLineaRobar->ID_MATERIAL_FISICO == NULL ? "NULL" : $rowReservaLineaRobar->ID_MATERIAL_FISICO) . " 
                                              , ID_TIPO_BLOQUEO = " . ($rowReservaLineaRobar->ID_TIPO_BLOQUEO == NULL ? "NULL" : $rowReservaLineaRobar->ID_TIPO_BLOQUEO) . " 
                                              , ID_ORDEN_TRABAJO_MOVIMIENTO = " . ($rowReservaLineaRobar->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? "NULL" : $rowReservaLineaRobar->ID_ORDEN_TRABAJO_MOVIMIENTO) . " 
                                              , ID_INCIDENCIA_CALIDAD = " . ($rowReservaLineaRobar->ID_INCIDENCIA_CALIDAD == NULL ? "NULL" : $rowReservaLineaRobar->ID_INCIDENCIA_CALIDAD);
                                $bd->ExecSQL($sqlInsert);

                                //LOG MOVIMIENTOS
                                $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Reserva", $rowReservaLineaConsumo->ID_RESERVA, 'Swap Cantidad:' . $cantidadRobo . " por Consumir de la Reserva: $rowReservaLineaRobar->ID_RESERVA. Reserva Linea: " . $bd->IdAsignado());
                            endif;

                            //DECREMENTAMOS LA LINEA DEL CONSUMO QUE HEMOS ASIGNADO A LA ROBADA
                            if ($rowReservaLineaConsumo->CANTIDAD - $cantidadRobo < EPSILON_SISTEMA):
                                $sqlUpdate = "UPDATE RESERVA_LINEA SET 
                                                  BAJA = 1 
                                                  WHERE ID_RESERVA_LINEA = $rowReservaLineaConsumo->ID_RESERVA_LINEA";
                                $bd->ExecSQL($sqlUpdate);
                            else:
                                $sqlUpdate = "UPDATE RESERVA_LINEA SET 
                                                  CANTIDAD = CANTIDAD - $cantidadRobo 
                                                  WHERE ID_RESERVA_LINEA = $rowReservaLineaConsumo->ID_RESERVA_LINEA";
                                $bd->ExecSQL($sqlUpdate);
                            endif;

                            //SI LA UBICACION ES DISTINTA HAY QUE LANZAR INTERFAZ T01
                            if ($rowReservaLineaRobar->ID_UBICACION != $rowReservaLineaConsumo->ID_UBICACION):
                                $arr_respuesta['lanzar_T01'] = $rowDemandaRobar->ID_ORDEN_TRABAJO_LINEA;
                            endif;

                        endif;//FIN SI EL ALMACEN ES EL MISMO, HACEMOS UN SWAP

                    endwhile;//FIN RECORREMOS LO RESERVADO EN EL CONSUMO (LA QUE ROBA)

                    //ACTUALIZAMOS LA DEMANDA
                    $this->actualizar_estado_demanda($rowDemanda->ID_DEMANDA, "Reservas finalizadas por Consumo");

                    //SI LA DEMANDA ES DE TIPO OT, ACTUALIZAMOS LAS TRANSFERENCIAS DE PENDIENTES SI LAS HAY
                    if ($rowDemanda->TIPO_DEMANDA == 'OT'):
                        $this->actualizar_transferencias_pendientes($rowDemanda->ID_DEMANDA);
                    endif;
                endif;
            endif;//FIN EXISTE DEMANDA EN LA OTL QUE CONSUME
        endif;

        //SI AUN QUEDA CANTIDAD PDTE DE REASIGNAR (LA DEMANDA QUE CONSUME NO TENIA RESERVADO SUFICIENTE)
        if ($cantidadPdteReasignar > EPSILON_SISTEMA):

            if ($rowDemandaRobar->TIPO_DEMANDA != 'OT'):
                //PONEMOS LA CANTIDAD EN LA COLA ORIGINAL
                $this->poner_cantidad_cola($rowDemandaRobar->ID_DEMANDA, $cantidadPdteReasignar, "Robo cantidad: $cantidadPdteReasignar por la OT: $rowOTConsumo->ORDEN_TRABAJO_SAP.");

                //ACTUALIZAMOS CANTIDAD PDTE RESERVAS
                $sqlUpdate = "UPDATE DEMANDA SET 
                                CANTIDAD_PENDIENTE_RESERVAR = CANTIDAD_PENDIENTE_RESERVAR + $cantidadPdteReasignar
                                WHERE ID_DEMANDA = $rowDemandaRobar->ID_DEMANDA";
                $bd->ExecSQL($sqlUpdate);

                //LOG MOVIMIENTOS
                $rowDemandaOriginalActualizada = $bd->VerReg("DEMANDA", "ID_DEMANDA", $rowDemandaRobar->ID_DEMANDA);
                $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Demanda", $rowDemandaRobar->ID_DEMANDA, "Quitar cantidad: $cantidadPdteReasignar por Consumo en OT: $rowOTConsumo->ORDEN_TRABAJO_SAP.", 'DEMANDA', $rowDemandaRobar, $rowDemandaOriginalActualizada);

                //ACTUALIZAMOS ESTADO SI ES NECESARIO
                $this->actualizar_estado_demanda($rowDemandaRobar->ID_DEMANDA, "Consumo en otra OT: $rowOTConsumo->ORDEN_TRABAJO_SAP");

            else:

                //DISMINUIMOS LA CANTIDAD DE LA DEMANDA DE LA OT ROBADA Y DEJAMOS LA OTL PDTE DE TRATAR
                $updateEstado = "";
                if ($rowDemandaRobar->CANTIDAD_DEMANDA - $cantidadPdteReasignar < EPSILON_SISTEMA):
                    $updateEstado = " , BAJA = 1";
                endif;
                $sqlUpdate = "UPDATE DEMANDA SET 
                                CANTIDAD_DEMANDA = CANTIDAD_DEMANDA - $cantidadPdteReasignar
                                $updateEstado
                                WHERE ID_DEMANDA = $rowDemandaRobar->ID_DEMANDA";
                $bd->ExecSQL($sqlUpdate);

                $rowDemandaActualizada = $bd->VerReg("DEMANDA", "ID_DEMANDA", $rowDemandaRobar->ID_DEMANDA);

                //LOG MOVIMIENTOS
                if ($idOrdenTrabajoLineaConsumo != ''):
                    $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Demanda", $rowDemandaRobar->ID_DEMANDA, 'Quitar cantidad: ' . $cantidadPdteReasignar . ' por Consumo en OT: ' . $rowOTConsumo->ORDEN_TRABAJO_SAP . '. Lin:' . $rowOTLConsumo->LINEA_ORDEN_TRABAJO_SAP, 'DEMANDA', $rowDemandaRobar, $rowDemandaActualizada);
                elseif ($idOrdenTrabajoConsumo != ''):
                    $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Demanda", $rowDemandaRobar->ID_DEMANDA, 'Quitar cantidad: ' . $cantidadPdteReasignar . ' por Consumo en OT: ' . $rowOTConsumo->ORDEN_TRABAJO_SAP, 'DEMANDA', $rowDemandaRobar, $rowDemandaActualizada);
                endif;

                    //ACTUALIZAMOS ESTADO SI ES NECESARIO
                if ($rowDemandaActualizada->BAJA == 0):
                    $this->actualizar_estado_demanda($rowDemandaRobar->ID_DEMANDA, 'Consumo en otra OT:' . $rowOTConsumo->ORDEN_TRABAJO_SAP . '.Lin:' . $rowOTLConsumo->LINEA_ORDEN_TRABAJO_SAP);
                endif;

                //LA OTL LA DEJAMOS PDTE DE TRATAR
                $this->dejar_pdte_tratar_OTL($rowDemandaRobar->ID_ORDEN_TRABAJO_LINEA, $cantidadPdteReasignar, "Cantidad Pdte Tratar tras Consumo desde otra OT");
            endif;
        endif;//FIN SI AUN QUEDA CANTIDAD PDTE DE REASIGNAR (LA DEMANDA QUE CONSUME NO TENIA RESERVADO SUFICIENTE)

        //SI LA DEMANDA ES DE TIPO OT, ACTUALIZAMOS LAS TRANSFERENCIAS DE PENDIENTES SI LAS HAY
        if ($rowDemandaRobar->TIPO_DEMANDA == 'OT'):
            $this->actualizar_transferencias_pendientes($rowDemandaRobar->ID_DEMANDA);
        endif;

        return $arr_respuesta;
    }

    /*
     * DEJA LA $idOrdenTrabajoLinea CON CANTIDAD PDTE DE TRATAR $cantidadPdteReasignar
     *
     */
    function dejar_pdte_tratar_OTL($idOrdenTrabajoLinea, $cantidadPdteReasignar, $observacionesLog = "")
    {
        global $bd;
        global $html;
        global $auxiliar;
        global $administrador;
        global $orden_trabajo;

        //BUSCAMOS LA OTL ORIGINAL
        $rowOTLRobar = $bd->VerReg("ORDEN_TRABAJO_LINEA", "ID_ORDEN_TRABAJO_LINEA", $idOrdenTrabajoLinea);

        //DECREMENTAMOS CANTIDAD CE/CANTIDAD PEDIDO
        if ($rowOTLRobar->CANTIDAD_RESERVADA > EPSILON_SISTEMA):
            $cantidadModificar     = min($rowOTLRobar->CANTIDAD_RESERVADA, $cantidadPdteReasignar);
            $cantidadPdteReasignar = $cantidadPdteReasignar - $cantidadModificar;

            $sqlUpdate = "UPDATE ORDEN_TRABAJO_LINEA SET 
                                CANTIDAD_RESERVADA = CANTIDAD_RESERVADA - $cantidadModificar 
                                WHERE ID_ORDEN_TRABAJO_LINEA = $rowOTLRobar->ID_ORDEN_TRABAJO_LINEA";
            $bd->ExecSQL($sqlUpdate);
        endif;
        if (($rowOTLRobar->CANTIDAD_PEDIDA > EPSILON_SISTEMA) && ($cantidadPdteReasignar > EPSILON_SISTEMA)):
            $cantidadModificar     = min($rowOTLRobar->CANTIDAD_PEDIDA, $cantidadPdteReasignar);
            $cantidadPdteReasignar = $cantidadPdteReasignar - $cantidadModificar;

            $sqlUpdate = "UPDATE ORDEN_TRABAJO_LINEA SET 
                                CANTIDAD_PEDIDA = CANTIDAD_PEDIDA - $cantidadModificar 
                                WHERE ID_ORDEN_TRABAJO_LINEA = $rowOTLRobar->ID_ORDEN_TRABAJO_LINEA";
            $bd->ExecSQL($sqlUpdate);
        endif;

        //ACTUALIZO EL ESTADO TRATADA DE LA LINEA DE LA ORDEN DE TRABAJO
        $orden_trabajo->ActualizarLinea_Estados($rowOTLRobar->ID_ORDEN_TRABAJO_LINEA);

        //BUSCAMOS LA OTL ACTUALIZADA
        $rowOTLRobarActualizada = $bd->VerReg("ORDEN_TRABAJO_LINEA", "ID_ORDEN_TRABAJO_LINEA", $rowOTLRobar->ID_ORDEN_TRABAJO_LINEA);

        //LOG MOVIMIENTOS
        $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, 'Modificación', "Linea Lista Materiales OT", $rowOTLRobar->ID_ORDEN_TRABAJO_LINEA, $observacionesLog, "ORDEN_TRABAJO_LINEA", $rowOTLRobar, $rowOTLRobarActualizada);
    }

    /**
     * @param $resultLineasReserva Result de lineas de reserva a analizar
     * @param $idMaterial Material con el que comparar
     * @param $idUbicacion Ubicacion con la que comparar
     * @param $idTipoBloqueo Tipo de bloqueo con el que comparar, se pasa el tipo de bloqueo original (RV compara con Nulo y RVP compara con P)
     * @param $idMaterialFisico Material Fisico con el que comparar
     * @param $idOrdenTrabajoMovimiento Orden Trabajo Movimiento con el que comparar
     * @param $idIncidenciaCalidad Incidencia Calidad con la que comparar
     * @return bool|mysqli_result
     * @throws Exception
     */
    function get_reservas_lineas_especificas($resultLineasReserva, $idMaterial, $idUbicacion, $idTipoBloqueo, $idMaterialFisico, $idOrdenTrabajoMovimiento, $idIncidenciaCalidad)
    {
        global $bd;
        global $html;
        global $auxiliar;
        global $administrador;

        //BUSCO EL TIPO DE BLOQUEO PREVENTIVO, MATERIAL USADO PARA PREPARAR ESTE TIPO DE PEDIDOS DE TRASLADO DE PENDIENTES
        $rowTipoBloqueoPlanificado = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "SP");

        //BLOQUEOS RESERVADO
        $rowTipoBloqueoReservado            = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "RV");
        $rowTipoBloqueoReservadoPlanificado = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "RVP");

        //VARIABLE PARA ALMACENAR LOS ID´s VALIDOS
        $arrIds = array();

        //RECORRO EL RESULT
        while ($rowLineaReserva = $bd->SigReg($resultLineasReserva)):
            if (
                ($rowLineaReserva->ID_MATERIAL == $idMaterial) &&
                ($rowLineaReserva->ID_UBICACION == $idUbicacion) &&
                ((($rowLineaReserva->ID_TIPO_BLOQUEO == $rowTipoBloqueoReservado->ID_TIPO_BLOQUEO) && ($idTipoBloqueo == NULL)) || (($rowLineaReserva->ID_TIPO_BLOQUEO == $rowTipoBloqueoReservadoPlanificado->ID_TIPO_BLOQUEO) && ($idTipoBloqueo == $rowTipoBloqueoPlanificado->ID_TIPO_BLOQUEO)) || (($rowLineaReserva->ID_TIPO_BLOQUEO == $rowTipoBloqueoReservado->ID_TIPO_BLOQUEO) && ($idTipoBloqueo == $rowTipoBloqueoReservado->ID_TIPO_BLOQUEO)) || (($rowLineaReserva->ID_TIPO_BLOQUEO == $rowTipoBloqueoReservadoPlanificado->ID_TIPO_BLOQUEO) && ($idTipoBloqueo == $rowTipoBloqueoReservadoPlanificado->ID_TIPO_BLOQUEO))) &&
                ((($rowLineaReserva->ID_MATERIAL_FISICO == NULL) && ($idMaterialFisico == NULL)) || ($rowLineaReserva->ID_MATERIAL_FISICO == $idMaterialFisico)) &&
                ((($rowLineaReserva->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL) && ($idOrdenTrabajoMovimiento == NULL)) || ($rowLineaReserva->ID_ORDEN_TRABAJO_MOVIMIENTO == $idOrdenTrabajoMovimiento)) &&
                ((($rowLineaReserva->ID_INCIDENCIA_CALIDAD == NULL) && ($idIncidenciaCalidad == NULL)) || ($rowLineaReserva->ID_INCIDENCIA_CALIDAD == $idIncidenciaCalidad))
            ): //SI COINCIDEN TODOS LOS PARAMETROS AÑADO LA LINEA DE RESERVA AL ARRAY
                $arrIds[] = $rowLineaReserva->ID_RESERVA_LINEA;
            endif;
        endwhile;
        //RECORRO EL RESULT

        //EN FUNCION DEL ARRAY REALIZO UNA CONSULTA U OTRA
        if (count( (array)$arrIds) == 0):
            return false;
        else:
            $sqlLineasReserva    = "SELECT * FROM RESERVA_LINEA WHERE ID_RESERVA_LINEA IN(" . implode(", ", (array) $arrIds) . ")";
            $resultLineasReserva = $bd->ExecSQL($sqlLineasReserva);

            return $resultLineasReserva;
        endif;
    }

    /**
     * @param $resultLineasReserva Conjunto de lineas a las que se le puede cambiar la ubicacion
     * @param $idNuevaUbicacion Nueva ubicacion de las reserva lineas
     * @param $cantidad Cantidad a cambiar de ubicacion
     * @return array
     * @throws Exception
     */
    function modificar_ubicacion_reserva_lineas($resultLineasReserva, $idNuevaUbicacion, $cantidad)
    {
        global $bd;
        global $html;
        global $auxiliar;
        global $administrador;

        //ARRAY A DEVOLVER
        $arr_respuesta = array();

        //VARIABLE PARA SABER LA CANTIDAD PENDIENTE
        $cantidadPendiente = $cantidad;

        //COMPRUEBO QUE EL RESULT NO SEA VACIO NI SEA FALSE
        if (($resultLineasReserva == false) || ($bd->NumRegs($resultLineasReserva) == 0)):
            $arr_respuesta['error'] = $auxiliar->traduce('No existen lineas de reserva para modificar la ubicacion', $administrador->ID_IDIOMA);

            return $arr_respuesta;
        endif;

        //COMPRUEBO QUE LA UBICACION EXISTA
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowUbicacion                     = $bd->VerReg("UBICACION", "ID_UBICACION", $idNuevaUbicacion, "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);
        if ($rowUbicacion == false):
            $arr_respuesta['error'] = $auxiliar->traduce('Nueva ubicacion reserva no existe', $administrador->ID_IDIOMA);

            return $arr_respuesta;
        endif;

        //RECORREMOS EL RESULT DE LINEAS DE RESERVA
        while (($rowReservaLinea = $bd->SigReg($resultLineasReserva)) && ($cantidadPendiente > EPSILON_SISTEMA)):
            //CALCULO LA CANTIDAD A CAMBIAR DE UBICACION
            $cantidadModificar = min($cantidadPendiente, $rowReservaLinea->CANTIDAD);

            //ACTUALIZO LA CANTIDAD CORRESPONDIENTE
            if (abs( (float)$rowReservaLinea->CANTIDAD - $cantidadModificar) < EPSILON_SISTEMA): //SI LA CANTIDAD A MODIFICAR COINCIDE CON LA DE LA LINEA ACTUALIZO LA UBICACION DE LA RESERVA LINEA
                $sqlUpdate = "UPDATE RESERVA_LINEA SET 
                              ID_UBICACION = $rowUbicacion->ID_UBICACION 
                              WHERE ID_RESERVA_LINEA = $rowReservaLinea->ID_RESERVA_LINEA";
                $bd->ExecSQL($sqlUpdate);

                //LOG MOVIMIENTOS
                $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Reserva", $rowReservaLinea->ID_RESERVA, 'Nueva ubicacion:' . $rowUbicacion->UBICACION . " . Reserva Linea: " . $rowReservaLinea->ID_RESERVA_LINEA);
            elseif ($rowReservaLinea->CANTIDAD > $cantidadModificar):
                $sqlUpdate = "UPDATE RESERVA_LINEA SET 
                              CANTIDAD = CANTIDAD - $cantidadModificar 
                              WHERE ID_RESERVA_LINEA = $rowReservaLinea->ID_RESERVA_LINEA";
                $bd->ExecSQL($sqlUpdate);

                //LOG MOVIMIENTOS
                $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Reserva", $rowReservaLinea->ID_RESERVA, 'Quitar Cantidad:' . $cantidadModificar . " . Reserva Linea: " . $rowReservaLinea->ID_RESERVA_LINEA);

                //GENERAMOS LA RESERVA LINEA A PARTIR DE ESTA RESERVA LINEA
                $sqlInsert = "INSERT INTO RESERVA_LINEA SET 
                                ID_RESERVA = " . $rowReservaLinea->ID_RESERVA . "
                                , FECHA_CREACION_LINEA = '" . date("Y-m-d H:i:s") . "'
                                , CANTIDAD = " . $cantidadModificar . "
                                , ESTADO_LINEA = '" . $rowReservaLinea->ESTADO_LINEA . "'
                                , ID_MATERIAL = " . $rowReservaLinea->ID_MATERIAL . "
                                , ID_UBICACION = " . $rowUbicacion->ID_UBICACION . "
                                , ID_TIPO_BLOQUEO = " . ($rowReservaLinea->ID_TIPO_BLOQUEO == NULL ? "NULL" : $rowReservaLinea->ID_TIPO_BLOQUEO) . "
                                , ID_MATERIAL_FISICO = " . ($rowReservaLinea->ID_MATERIAL_FISICO == NULL ? "NULL" : $rowReservaLinea->ID_MATERIAL_FISICO) . "
                                , ID_ORDEN_TRABAJO_MOVIMIENTO = " . ($rowReservaLinea->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? "NULL" : $rowReservaLinea->ID_ORDEN_TRABAJO_MOVIMIENTO) . "
                                , ID_INCIDENCIA_CALIDAD = " . ($rowReservaLinea->ID_INCIDENCIA_CALIDAD == NULL ? "NULL" : $rowReservaLinea->ID_INCIDENCIA_CALIDAD);
                $bd->ExecSQL($sqlInsert);
                $idNuevaReservaLinea = $bd->IdAsignado();

                //BUSCO LA RESERVA LINEA NUEVA
                $rowReservaLineaNueva = $bd->VerReg("RESERVA_LINEA", "ID_RESERVA_LINEA", $idNuevaReservaLinea);

                //LOG MOVIMIENTOS
                $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Reserva", $rowReservaLineaNueva->ID_RESERVA, 'Poner Cantidad:' . $cantidadModificar . " . Reserva Linea: " . $rowReservaLineaNueva->ID_RESERVA_LINEA);
            else:
                $arr_respuesta['error'] = $auxiliar->traduce('La cantidad reservada a modificar la ubicacion es superior a la cantidad de la linea', $administrador->ID_IDIOMA);

                return $arr_respuesta;
            endif;
            //FIN ACTUALIZO LA CANTIDAD CORRESPONDIENTE

            //ACTUALIZO LA CANTIDAD PENDIENTE
            $cantidadPendiente = $cantidadPendiente - $cantidadModificar;

            //BUSCAMOS LA RESERVA
            $rowReserva = $bd->VerReg("RESERVA", "ID_RESERVA", $rowReservaLinea->ID_RESERVA);

            //BUSCAMOS LA DEMANDA
            $rowDemanda = $bd->VerReg("DEMANDA", "ID_DEMANDA", $rowReserva->ID_DEMANDA);

            //SI LA DEMANDA ES DE TIPO OT, ACTUALIZAMOS LAS TRANSFERENCIAS DE PENDIENTES SI LAS HAY
            if ($rowDemanda->TIPO_DEMANDA == 'OT'):
                $this->actualizar_transferencias_pendientes($rowDemanda->ID_DEMANDA);
            endif;
        endwhile;
        //FIN RECORREMOS EL RESULT DE LINEAS DE RESERVA

        if ($cantidadPendiente > EPSILON_SISTEMA):
            $arr_respuesta['error'] = $auxiliar->traduce('La cantidad a modificar ubicacion superior a cantidad de la reserva', $administrador->ID_IDIOMA);

            return $arr_respuesta;
        endif;
    }

    /**
     * @param string $idDemanda
     * @param array $arrCambios CANTIDAD (Positiva o negativa), FECHA_DEMANDA
     * MODIFICA LA DEMANDA Y SUS RESERVAS/COLA
     */
    function modificacion_demanda($idDemanda, $arrCambios = array())
    {
        global $bd;
        global $html;
        global $auxiliar;
        global $administrador;

        //ARRAY A DEVOLVER
        $arr_respuesta = array();

        //BUSCAMOS LA DEMANDA
        $rowDemanda = $bd->VerReg("DEMANDA", "ID_DEMANDA", $idDemanda);

        //OBTENEMOS SUS DATOS
        $idNecesidad = NULL;
        $idObjeto    = NULL;
        switch ($rowDemanda->TIPO_DEMANDA):
            case "Pedido":
                $idObjeto = $rowDemanda->ID_PEDIDO_SALIDA_LINEA;
                break;
            case "OT":
                $idObjeto = $rowDemanda->ID_ORDEN_TRABAJO_LINEA;
                break;
            default:
                $arr_respuesta['error'] = 'Tipo Demanda no contemplado';

                return $arr_respuesta;
                break;
        endswitch;

        //SI VIENE UNA MODIFICACION EN LA CANTIDAD
        if (isset($arrCambios['CANTIDAD']) && ($arrCambios['CANTIDAD'] != "")):
            //SI ES MODIFICACION POSITIVA
            if ($arrCambios['CANTIDAD'] > EPSILON_SISTEMA):
                //METEMOS LA CANTIDAD EN LA DEMANDA
                $arr_creacion = $this->creacion_demanda($rowDemanda->TIPO_DEMANDA, $idObjeto, $arrCambios['CANTIDAD'], $idNecesidad);
                if (isset($arr_creacion['error']) && $arr_creacion['error'] != "")://SI VIENE ERROR
                    $arr_respuesta['error'] = $arr_creacion['error'];

                    return $arr_respuesta;
                endif;

            elseif ($arrCambios['CANTIDAD'] < -EPSILON_SISTEMA):
                $cantidadPdteSacar = abs( (float)$arrCambios['CANTIDAD']);

                //SI LA CANTIDAD CON LA QUE SE QUEDA LA DEMANAD ES 0, LA ANULAMOS
                if ($rowDemanda->CANTIDAD_DEMANDA <= $cantidadPdteSacar):
                    $arr_anulacion = $this->anular_demanda($rowDemanda->ID_DEMANDA, "Modificacion Cantidad Demanda Quitando: " . $cantidadPdteSacar);

                    if (isset($arr_anulacion['error']) && $arr_anulacion['error'] != "")://SI VIENE ERROR
                        $arr_respuesta['error'] = $arr_anulacion['error'];

                        return $arr_respuesta;
                    endif;

                else:
                    //PRIMERO INTENTAMOS SACAR DE LA COLA
                    $cantidadQuitadaCola     = 0;
                    $cantidadQuitadaReservas = 0;
                    $rowColaProgramada       = $this->get_cola_reserva($rowDemanda->ID_DEMANDA);
                    if ($rowColaProgramada != false && $rowColaProgramada->CANTIDAD_EN_COLA != 0):
                        //EXTRAIGO EL IDENTIFICADOR DE LA LINEA DEL PEDIDO DE SALIDA PARA SABER LA LINEA DE LA RESERVA EN LA QUE QUITAR CANTIDAD
                        if ((isset($arrCambios['ID_PEDIDO_SALIDA_LINEA'])) && ($arrCambios['ID_PEDIDO_SALIDA_LINEA'] != NULL)):
                            $idPedidoSalidaLinea = $arrCambios['ID_PEDIDO_SALIDA_LINEA'];
                        else:
                            $idPedidoSalidaLinea = NULL;
                        endif;

                        //SI LA COLA TIENE SUFICIENTE CANTIDAD PARA ROBAR
                        if ($rowColaProgramada->CANTIDAD_EN_COLA >= $cantidadPdteSacar):
                            $this->quitar_cantidad_cola($rowDemanda->ID_DEMANDA, $cantidadPdteSacar, 'Quitar cantidad: ' . $cantidadPdteSacar . ' por Modificacion Demanda', $idPedidoSalidaLinea);
                            $cantidadQuitadaCola = $cantidadPdteSacar;
                            $cantidadPdteSacar   = 0;

                        else://LA COLA NO TIENE SUFICIENTE CANTIDAD
                            $this->quitar_cantidad_cola($rowDemanda->ID_DEMANDA, $rowColaProgramada->CANTIDAD_EN_COLA, 'Quitar cantidad: ' . $rowColaProgramada->CANTIDAD_EN_COLA . ' por Modificacion Demanda', $idPedidoSalidaLinea);
                            $cantidadQuitadaCola = $rowColaProgramada->CANTIDAD_EN_COLA;
                            $cantidadPdteSacar   = $cantidadPdteSacar - $rowColaProgramada->CANTIDAD_EN_COLA;
                        endif;
                    endif;

                    //SI AUN QUEDA CANTIDAD PENDIENTE DE QUITAR, LO INTENTAMOS DE LAS RESERVAS
                    if ($cantidadPdteSacar > EPSILON_SISTEMA):
                        $arr_anulacion = $this->anular_reserva($rowDemanda->ID_DEMANDA, $cantidadPdteSacar);
                        if (isset($arr_anulacion['error']) && $arr_anulacion['error'] != "")://SI VIENE ERROR
                            $arr_respuesta['error'] = $arr_anulacion['error'];

                            return $arr_respuesta;
                        else:
                            $cantidadQuitadaReservas = $cantidadPdteSacar;
                        endif;
                    endif;

                    //OBTENEMOS LO ROBADO ENTRE RESERVAS Y COLA
                    $cantidadTotal = $cantidadQuitadaReservas + $cantidadQuitadaCola;
                    if ($cantidadTotal > EPSILON_SISTEMA):
                        //ACTUALIZAMOS LA DEMANDA ORIGINAL, SI SE ROBA COMPLETAMENTE LA DAMOS DE BAJA
                        $updateEstado = "";
                        if ($rowDemanda->CANTIDAD_DEMANDA - $cantidadTotal < EPSILON_SISTEMA):
                            $updateEstado = " ,BAJA = 1";
                        endif;
                        $sqlUpdate = "UPDATE DEMANDA SET 
                                        CANTIDAD_DEMANDA = CANTIDAD_DEMANDA - $cantidadTotal
                                      , CANTIDAD_PENDIENTE_RESERVAR = CANTIDAD_PENDIENTE_RESERVAR - $cantidadQuitadaCola
                                        " . $updateEstado . "
                                 WHERE ID_DEMANDA = $rowDemanda->ID_DEMANDA";
                        $bd->ExecSQL($sqlUpdate);

                        //LOG MOVIMIENTOS
                        $rowDemandaActualizada = $bd->VerReg("DEMANDA", "ID_DEMANDA", $rowDemanda->ID_DEMANDA);
                        $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Demanda", $rowDemanda->ID_DEMANDA, 'Quitar cantidad: ' . round( (float)$cantidadTotal, 3) . ' por Modificacion Demanda', 'DEMANDA', $rowDemanda, $rowDemandaActualizada);

                        //ACTUALIZAMOS ESTADO SI ES NECESARIO
                        if ($rowDemandaActualizada->BAJA == 0):
                            $this->actualizar_estado_demanda($rowDemanda->ID_DEMANDA, 'Modificacion Cantidad Demanda');
                        endif;
                    endif;
                endif;//FIN SI LA CANTIDAD CON LA QUE SE QUEDA LA DEMANDA ES MAYOR QUE 0
            endif;//FIN MODIFICACION CANTIDAD POSITIVA/NEGATIVA
        endif;//FIN SI VIENE UNA MODIFICACION EN LA CANTIDAD

        //SI VIENE UNA MODIFICACION DE LA FECHA DEMANDA
        if (isset($arrCambios['FECHA_DEMANDA']) && ($arrCambios['FECHA_DEMANDA'] != "") && ($arrCambios['FECHA_DEMANDA'] != "0000 - 00 - 00")):

            //COMPROBAMOS SI LA FECHA HA VARIADO
            if ($rowDemanda->FECHA_DEMANDA != $arrCambios['FECHA_DEMANDA']):

                //BUSCAMOS EL HITO
                $hito_reserva = "";
                if ($rowDemanda->ID_PRIORIDAD_DEMANDA != NULL):
                    $rowPrioridadDemanda = $bd->VerReg("PRIORIDAD_DEMANDA", "ID_PRIORIDAD_DEMANDA", $rowDemanda->ID_PRIORIDAD_DEMANDA);
                    $hito_reserva        = $rowPrioridadDemanda->HITO_RESERVA;
                endif;

                //SI EL HITO ES DEADLINE, RECALCULAMOS SUS FECHAS
                if ($hito_reserva == "Deadline"):

                    //SI LA DEMANDA ES DE PEDIDO DE TRASLADO, EL ALMACEN SERA EL DE DESTINO
                    $idAlmacenLeadTime = $rowDemanda->ID_ALMACEN_DEMANDA;
                    if ($rowDemanda->TIPO_DEMANDA == "Pedido"):
                        $rowPSL = $bd->VerReg("PEDIDO_SALIDA_LINEA", "ID_PEDIDO_SALIDA_LINEA", $rowDemanda->ID_PEDIDO_SALIDA_LINEA);
                        if ($rowPSL->ID_ALMACEN_DESTINO != NULL):
                            $idAlmacenLeadTime = $rowPSL->ID_ALMACEN_DESTINO;
                        endif;
                    endif;

                    //OBTENEMOS LA FECHA ESTIMADA RESERVA
                    $arr_fechas = $this->get_fecha_estimada_reserva($arrCambios['FECHA_DEMANDA'], $rowDemanda->ID_MATERIAL, $idAlmacenLeadTime, $rowDemanda->ID_PRIORIDAD_DEMANDA);
                    if (isset($arr_fechas['error']) && ($arr_fechas['error'] != "")):
                        $arr_respuesta['error'] = $arr_fechas['error'];

                        return $arr_respuesta;
                    endif;

                    //ACTUALIZAMOS LA PRIORIDAD Y FECHAS
                    $sqlUpdate = "UPDATE DEMANDA SET 
                                    FECHA_DEMANDA = '" . $arrCambios['FECHA_DEMANDA'] . "'
                                    , ID_CLAVE_APROVISIONAMIENTO_ESPECIAL = " . ($arr_fechas['cae'] == NULL ? 'NULL' : ("'" . $arr_fechas['cae']) . "'") . "
                                    , LEAD_TIME = " . ($arr_fechas['lead_time'] == NULL ? 'NULL' : ("'" . $arr_fechas['lead_time']) . "'") . "
                                    , DEADLINE = '" . $arr_fechas['deadline'] . "'
                                    , MARGEN_RESERVA = '" . $arr_fechas['margen'] . "'
                                    , FECHA_ESTIMADA_RESERVA = '" . $arr_fechas['fecha_estimada_reserva'] . "'
                                    WHERE ID_DEMANDA = $rowDemanda->ID_DEMANDA";
                    $bd->ExecSQL($sqlUpdate);

                    // CALCULAMOS LA FECHA ACTUAL SEGÚN EL HUSO HORARIO
                    $rowAlmacenDemanda = $bd->VerReg("ALMACEN", "ID_ALMACEN", $rowDemanda->ID_ALMACEN_DEMANDA);
                    $rowCFDemanda      = $bd->VerReg("CENTRO_FISICO", "ID_CENTRO_FISICO", $rowAlmacenDemanda->ID_CENTRO_FISICO);
                    if ($rowCFDemanda->ID_HUSO_HORARIO != NULL):
                        $rowHusoHorario = $bd->VerReg("HUSO_HORARIO_", "ID_HUSO_HORARIO", $rowCFDemanda->ID_HUSO_HORARIO);
                        $diaActualCF    = $auxiliar->fechaToTimezoneUserParam($rowHusoHorario->ID_HUSO_HORARIO_PHP, date("Y - m - d H:i:s"));
                        $diaActualCF    = substr( (string) $diaActualCF, 0, 10);
                    else:
                        $diaActualCF = date("Y - m - d");
                    endif;

                    //SI LA NUEVA FECHA ESTIMADA RESERVA ES POSTERIOR A HOY, DESHACEMOS LAS RESERVA NO FINALIZADAS Y LAS COLAS LAS PASAMOS A PROGRAMADAS
                    if ($arr_fechas['fecha_estimada_reserva'] > $diaActualCF):

                        //BUSCAMOS LAS LINEAS EN ESTADO RESERVADA Y LIBERAMOS EL STOCK
                        $cantidadReservada = $this->get_cantidad_reserva_demanda($rowDemanda->ID_DEMANDA, 'Reservada');
                        if ($cantidadReservada > EPSILON_SISTEMA):
                            //LIBERAR STOCK RESERVADO Y METERLO EN LA COLA PROGRAMADA
                            $arr_anulacion = $this->anular_reserva($rowDemanda->ID_DEMANDA, $cantidadReservada, "1");
                            if (isset($arr_anulacion['error']) && $arr_anulacion['error'] != "")://SI VIENE ERROR
                                $arr_respuesta['error'] = $arr_anulacion['error'];

                                return $arr_respuesta;
                            endif;
                        endif;

                        //PONEMOS LA COLA COMO PROGRAMADA
                        $rowColaProgramada = $this->get_cola_reserva($rowDemanda->ID_DEMANDA);
                        if ($rowColaProgramada != false && $rowColaProgramada->ESTADO == 'Pendiente'):
                            //ACTUALIZAMOS LA COLA PROGRAMADA
                            $sqlUpdate = "UPDATE COLA_RESERVA SET 
                                             ESTADO = 'Programada'
                                            , FECHA_FINALIZACION = NULL
                                            WHERE ID_COLA_RESERVA = $rowColaProgramada->ID_COLA_RESERVA";
                            $bd->ExecSQL($sqlUpdate);

                            // LOG MOVIMIENTOS
                            $rowColaProgramadaActualizada = $bd->VerReg("COLA_RESERVA", "ID_COLA_RESERVA", $rowColaProgramada->ID_COLA_RESERVA);
                            $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Cola Reserva", $rowColaProgramada->ID_COLA_RESERVA, 'Cambio Fecha Demanda', 'COLA_RESERVA', $rowColaProgramada, $rowColaProgramadaActualizada);
                        endif;
                    endif;

                else://EN OTRO CASO, SOLO VARIA SU FECHA DEMANDA
                    $sqlUpdate = "UPDATE DEMANDA SET 
                                     FECHA_DEMANDA = '" . $arrCambios['FECHA_DEMANDA'] . "'
                                    WHERE ID_DEMANDA = $rowDemanda->ID_DEMANDA";
                    $bd->ExecSQL($sqlUpdate);
                endif;

                //LOG MOVIMIENTOS
                $rowDemandaActualizada = $bd->VerReg("DEMANDA", "ID_DEMANDA", $rowDemanda->ID_DEMANDA);
                $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Demanda", $rowDemanda->ID_DEMANDA, 'Cambiar Fecha Demanda', 'DEMANDA', $rowDemanda, $rowDemandaActualizada);
            endif;//FIN SI LA FECHA HA VARIADO
        endif;//FIN SI VIENE UNA MODIFICACION DE LA FECHA DEMANDA

        //SI LA DEMANDA ES DE TIPO OT, ACTUALIZAMOS LAS TRANSFERENCIAS DE PENDIENTES SI LAS HAY
        if ($rowDemanda->TIPO_DEMANDA == 'OT'):
            $this->actualizar_transferencias_pendientes($rowDemanda->ID_DEMANDA);
        endif;

        return $arr_respuesta;
    }

    /**
     * @string $idNecesidad
     * ACTUALIZA LAS DEMANDAS ASOCIADAD A UNA NECESIDAD.
     * SI SE PONE CANTIDAD, LO TOMA DE LA DEMANDA SIN NECESIDAD, SI SE QUITA CANTIDAD, LO METE EN LA DEMANDA SIN NECESIDAD
     */
    function actualizar_demandas_necesidad($idNecesidad)
    {
        global $bd;
        global $html;
        global $auxiliar;
        global $administrador;

        //ARRAY A DEVOLVER
        $arr_respuesta = array();

        //BUSCAMOS LA NECESIDAD
        $rowNecesidad = $bd->VerReg("NECESIDAD", "ID_NECESIDAD", $idNecesidad);

        //BUSCAMOS LAS LINEAS DE PEDIDO ASOCIADAS A LA NECESIDAD
        $arrayLineasPedidosSalidaInvolucrados = array();
        $sqlPedidos                           = "SELECT ID_PEDIDO_SALIDA_LINEA, SUM(CANTIDAD) AS CANTIDAD_ASOCIADA
                                                    FROM NECESIDAD_LINEA 
                                                    WHERE ID_NECESIDAD = $rowNecesidad->ID_NECESIDAD AND ID_PEDIDO_SALIDA_LINEA IS NOT NULL AND ID_NECESIDAD_SUBORDINADA IS NULL AND BAJA = 0
                                                    GROUP BY ID_PEDIDO_SALIDA_LINEA";
        $resultPedidos                        = $bd->ExecSQL($sqlPedidos);
        while ($rowPedidos = $bd->SigReg($resultPedidos)):
            if ($rowPedidos->ID_PEDIDO_SALIDA_LINEA != NULL):
                $arrayLineasPedidosSalidaInvolucrados[] = $rowPedidos->ID_PEDIDO_SALIDA_LINEA;

                //COMPROBAMOS PEDIDOS
                $rowPSLNuevo = $bd->VerReg("PEDIDO_SALIDA_LINEA", "ID_PEDIDO_SALIDA_LINEA", $rowPedidos->ID_PEDIDO_SALIDA_LINEA);

                //COMPROBAMOS TIPO DE PEDIDO
                $rowPedido = $bd->VerReg("PEDIDO_SALIDA", "ID_PEDIDO_SALIDA", $rowPSLNuevo->ID_PEDIDO_SALIDA);

                //BUSAMOS ALMACEN RESERVA (Origen)
                $rowAlmacenReserva = $bd->VerReg("ALMACEN", "ID_ALMACEN", $rowPSLNuevo->ID_ALMACEN_ORIGEN);
                //BUSCAMOS EL CENTRO DE LA DEMANDA
                $rowCentroReserva = $bd->VerReg("CENTRO", "ID_CENTRO", $rowAlmacenReserva->ID_CENTRO);

                //SI TIENE ACTIVA LA GESTION DE DEMANDAS
                if (($rowCentroReserva->GESTION_RESERVAS == 1) && ($this->pedido_admite_demanda($rowPedido->TIPO_PEDIDO, $rowPedido->TIPO_PEDIDO_SAP))):

                    //BUSCAMOS SI EXISTE YA UNA DEMANDA
                    $rowDemandaNecesidad = $this->get_demanda("Pedido", $rowPSLNuevo->ID_PEDIDO_SALIDA_LINEA, $rowNecesidad->ID_NECESIDAD);

                    //SI LA CANTIDAD VARÍA, MODIFICAMOS LA DEMANDA
                    if ($rowDemandaNecesidad != false):

                        //OBTENEMOS EL GRUPO DEMANDA
                        $idPrioridadDemanda = $this->get_prioridad_demanda("Pedido", $rowPSLNuevo->ID_PEDIDO_SALIDA_LINEA, $rowNecesidad->ID_NECESIDAD);

                        //SI HA VARIADO, LO ACTUALIZAMOS JUNTO A SUS FECHAS
                        if ($idPrioridadDemanda != $rowDemandaNecesidad->ID_PRIORIDAD_DEMANDA):
                            //OBTENEMOS LA FECHA ESTIMADA RESERVA
                            $arr_fechas = $this->get_fecha_estimada_reserva($rowPSLNuevo->FECHA_ENTREGA, $rowPSLNuevo->ID_MATERIAL, ($rowPSLNuevo->ID_ALMACEN_DESTINO != NULL ? $rowPSLNuevo->ID_ALMACEN_DESTINO : $rowPSLNuevo->ID_ALMACEN_ORIGEN), $idPrioridadDemanda);
                            if (isset($arr_fechas['error']) && ($arr_fechas['error'] != "")):
                                $arr_respuesta['error'] = $arr_fechas['error'];

                                return $arr_respuesta;
                            endif;

                            //ACTUALIZAMOS LA PRIORIDAD Y FECHAS
                            $sqlUpdate = "UPDATE DEMANDA SET 
                                           ID_PRIORIDAD_DEMANDA = " . ($idPrioridadDemanda != "" ? $idPrioridadDemanda : "NULL") . "
                                            , FECHA_DEMANDA = '" . $rowPSLNuevo->FECHA_ENTREGA . "'
                                            , ID_CLAVE_APROVISIONAMIENTO_ESPECIAL = " . ($arr_fechas['cae'] == NULL ? 'NULL' : ("'" . $arr_fechas['cae']) . "'") . "
                                            , LEAD_TIME = " . ($arr_fechas['lead_time'] == NULL ? 'NULL' : ("'" . $arr_fechas['lead_time']) . "'") . "
                                            , DEADLINE = '" . $arr_fechas['deadline'] . "'
                                            , MARGEN_RESERVA = '" . $arr_fechas['margen'] . "'
                                            , FECHA_ESTIMADA_RESERVA = '" . $arr_fechas['fecha_estimada_reserva'] . "'
                                        WHERE ID_DEMANDA = $rowDemandaNecesidad->ID_DEMANDA";
                            $bd->ExecSQL($sqlUpdate);

                            //LOG
                            $rowDemandaActualizada = $bd->VerReg("DEMANDA", "ID_DEMANDA", $rowDemandaNecesidad->ID_DEMANDA);
                            $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Demanda", $rowDemandaNecesidad->ID_DEMANDA, 'Cambio Prioridad Necesidad', 'DEMANDA', $rowDemandaNecesidad, $rowDemandaActualizada);
                        endif;

                        //SI SE HA DESASIGNADO CANTIDAD, PASAMOS ESA CANTIDAD A LA DEMANDA DEL PEDIDO
                        if ($rowDemandaNecesidad->CANTIDAD_DEMANDA > $rowPedidos->CANTIDAD_ASOCIADA):

                            //CANTIDAD A MOVER
                            $cantidadSplit = $rowDemandaNecesidad->CANTIDAD_DEMANDA - $rowPedidos->CANTIDAD_ASOCIADA;
                            if ($cantidadSplit > EPSILON_SISTEMA):
                                //PRIMERO, METEMOS EN LA DEMANDA DEL PEDIDO
                                $arr_creacion = $this->creacion_demanda("Pedido", $rowPedidos->ID_PEDIDO_SALIDA_LINEA, $cantidadSplit, NULL);
                                if (isset($arr_creacion['error']) && $arr_creacion['error'] != "")://SI VIENE ERROR
                                    $arr_respuesta['error'] = $arr_creacion['error'];

                                    return $arr_respuesta;
                                else:
                                    $idDemandaPedido = $arr_creacion['idDemanda'];
                                endif;

                                //SI SE HA QUITADO CANTIDAD, LA PASAMOS A LA DEMANDA SIN NECESIDAD
                                $this->robo_reserva_entre_demandas($rowDemandaNecesidad->ID_DEMANDA, $idDemandaPedido, $cantidadSplit, "Split");
                            endif; //FIN CANTIDAD A MOVER

                        //SI SE HA AÑADIDO CANTIDAD, LA QUITAMOS DE LA DEMANDA SIN NECESIDAD
                        elseif ($rowDemandaNecesidad->CANTIDAD_DEMANDA < $rowPedidos->CANTIDAD_ASOCIADA):

                            //CANTIDAD A MOVER
                            $cantidadSplit = $rowPedidos->CANTIDAD_ASOCIADA - $rowDemandaNecesidad->CANTIDAD_DEMANDA;
                            if ($cantidadSplit > EPSILON_SISTEMA):

                                //PRIMERO, METEMOS EN LA DEMANDA DE LA NECESIDAD
                                $arr_creacion = $this->creacion_demanda("Pedido", $rowPedidos->ID_PEDIDO_SALIDA_LINEA, $cantidadSplit, $rowNecesidad->ID_NECESIDAD);
                                if (isset($arr_creacion['error']) && $arr_creacion['error'] != "")://SI VIENE ERROR
                                    $arr_respuesta['error'] = $arr_creacion['error'];

                                    return $arr_respuesta;
                                endif;

                                //BUSCAMOS LA DEMANDA DE PEDIDO A LA QUE ROBAR
                                $rowDemandaPedido = $this->get_demanda("Pedido", $rowPedidos->ID_PEDIDO_SALIDA_LINEA, NULL);
                                if ($rowDemandaPedido != false): //SI EXISTE, LE ROBAMOS EL STOCK
                                    $this->robo_reserva_entre_demandas($rowDemandaPedido->ID_DEMANDA, $rowDemandaNecesidad->ID_DEMANDA, $cantidadSplit, "Split");
                                endif;
                            endif;//FIN CANTIDAD MOVER
                        endif;//FIN CANTIDAD VARIADA

                    else://LA DEMANDA NO EXISTIA, INTENTAMOS ROBAR DE LA DEMANDA DEL PEDIDO EN CASO DE QUE EXISTA

                        //CANTIDAD A MOVER
                        $cantidadSplit = $rowPedidos->CANTIDAD_ASOCIADA;

                        //PRIMERO, METEMOS EN LA DEMANDA DE LA NECESIDAD
                        $arr_creacion = $this->creacion_demanda("Pedido", $rowPedidos->ID_PEDIDO_SALIDA_LINEA, $cantidadSplit, $rowNecesidad->ID_NECESIDAD);
                        if (isset($arr_creacion['error']) && $arr_creacion['error'] != "")://SI VIENE ERROR
                            $arr_respuesta['error'] = $arr_creacion['error'];

                            return $arr_respuesta;
                        else:
                            $idDemandaNecesidad = $arr_creacion['idDemanda'];
                        endif;

                        //BUSCAMOS LA DEMANDA DE PEDIDO A LA QUE ROBAR
                        $rowDemandaPedido = $this->get_demanda("Pedido", $rowPedidos->ID_PEDIDO_SALIDA_LINEA, NULL);
                        if ($rowDemandaPedido != false): //SI EXISTE, LE ROBAMOS EL STOCK
                            $this->robo_reserva_entre_demandas($rowDemandaPedido->ID_DEMANDA, $idDemandaNecesidad, $cantidadSplit, "Split");
                        endif;
                    endif;
                endif;//FIN CENTRO GESTIONA RESERVAS
            endif;
        endwhile;


        //BUSCAMOS DEMANDAS ACTIVAS ASOCIADAS A LA NECESIDAD PERO QUE YA NO PERTENECEN A ELLA
        $sqlWhere = "";
        if (count((array) $arrayLineasPedidosSalidaInvolucrados) > 0):
            $sqlWhere = " AND ID_PEDIDO_SALIDA_LINEA NOT IN(" . implode(", ", (array) $arrayLineasPedidosSalidaInvolucrados) . ")";
        else:
            $sqlWhere = " AND ID_PEDIDO_SALIDA_LINEA IS NOT NULL ";
        endif;
        $sqlDemandasNecesidad   = "SELECT ID_DEMANDA, ID_PEDIDO_SALIDA_LINEA, CANTIDAD_DEMANDA
                                    FROM DEMANDA 
                                    WHERE ID_NECESIDAD = $rowNecesidad->ID_NECESIDAD AND TIPO_DEMANDA = 'Pedido' $sqlWhere AND BAJA = 0";
        $resultDemandaNecesidad = $bd->ExecSQL($sqlDemandasNecesidad);
        if ($bd->NumRegs($resultDemandaNecesidad) > 0):
            while ($rowDemandaNecesidad = $bd->SigReg($resultDemandaNecesidad)):

                //PRIMERO, METEMOS LA CANTIDAD EN LA DEMANDA DEL PEDIDO
                $arr_creacion = $this->creacion_demanda("Pedido", $rowDemandaNecesidad->ID_PEDIDO_SALIDA_LINEA, $rowDemandaNecesidad->CANTIDAD_DEMANDA, NULL);
                if (isset($arr_creacion['error']) && $arr_creacion['error'] != "")://SI VIENE ERROR
                    $arr_respuesta['error'] = $arr_creacion['error'];

                    return $arr_respuesta;
                else:
                    $idDemandaPedido = $arr_creacion['idDemanda'];
                endif;

                //LA PASAMOS A LA DEMANDA SIN NECESIDAD
                $this->robo_reserva_entre_demandas($rowDemandaNecesidad->ID_DEMANDA, $idDemandaPedido, $rowDemandaNecesidad->CANTIDAD_DEMANDA, "Split");
            endwhile;

        endif;//FIN DEMANDAS ASOCIADAS A LA NECESIDAD QUE YA NO PERTENECEN A ELLA

        return $arr_respuesta;
    }


    /**
     * @string $idPedidoSalida
     * @string $idPedidoSalidaLinea Opcional
     * ACTUALIZA LAS DEMANDAS ASOCIADAS A UN PEDIDO TRAS UNA ACTUALIZACION DEL PEDIDO
     * NO TIENE EN CUENTA SPLITs, LOS SPLITs SE DEBEN TRATAR ANTES DE LLAMAR A ESTAR FUNCION
     */
    function actualizar_demandas_pedido($idPedidoSalida, $idPedidoSalidaLinea = "")
    {
        global $bd;
        global $html;
        global $auxiliar;
        global $administrador;

        //ARRAY A DEVOLVER
        $arr_respuesta = array();

        //BUSCAMOS LA NECESIDAD
        $rowPedido = $bd->VerReg("PEDIDO_SALIDA", "ID_PEDIDO_SALIDA", $idPedidoSalida);

        //FILTRO LINEA
        $sqlWhere = "";
        if ($idPedidoSalidaLinea != ""):
            $sqlWhere .= " AND PSL.ID_PEDIDO_SALIDA_LINEA = " . $idPedidoSalidaLinea;
        endif;

        //BUSCAMOS LAS LINEAS DE PEDIDO CUYO ORIGEN GESTIONE RESERVAS
        $sqlLineas    = "SELECT DISTINCT PSL.ID_PEDIDO_SALIDA_LINEA, PSL.INDICADOR_BORRADO, PSL.BAJA, PSL.CANTIDAD, PSL.FECHA_ENTREGA, PSL.CANTIDAD_CANCELADA_POR_ENTREGA_FINAL
                            FROM PEDIDO_SALIDA_LINEA PSL
                            INNER JOIN ALMACEN AO ON AO.ID_ALMACEN = PSL.ID_ALMACEN_ORIGEN
                            INNER JOIN CENTRO C ON C.ID_CENTRO = AO.ID_CENTRO
                            WHERE PSL.ID_PEDIDO_SALIDA = $rowPedido->ID_PEDIDO_SALIDA AND C.GESTION_RESERVAS = 1" . $sqlWhere;
        $resultLineas = $bd->ExecSQL($sqlLineas);
        if ($bd->NumRegs($resultLineas) > 0):
            while ($rowLinea = $bd->SigReg($resultLineas)):

                //SI ESTA DADA DE BAJA, DAMOS DE BAJA LAS DEMANDAS
                if (($rowLinea->INDICADOR_BORRADO != NULL) || ($rowLinea->BAJA != 0)):
                    $resultDemandas = $this->get_demandas_pedido($rowPedido->ID_PEDIDO_SALIDA, '', $rowLinea->ID_PEDIDO_SALIDA_LINEA);
                    if (($resultDemandas != false) && ($bd->NumRegs($resultDemandas) > 0)):
                        while ($rowDemanda = $bd->SigReg($resultDemandas)):
                            $arr_anulacion = $this->anular_demanda($rowDemanda->ID_DEMANDA, "Linea Pedido dada de Baja");

                            if (isset($arr_anulacion['error']) && $arr_anulacion['error'] != "")://SI VIENE ERROR
                                $arr_respuesta['error'] = $arr_anulacion['error'];

                                return $arr_respuesta;
                            endif;
                        endwhile;
                    endif;

                else: //SI NO, GESTIONAMOS SUS DEMANDAS

                    //ARRAY PARA SABER QUE DEMANDAS SIGUEN ACTIVAS
                    $arr_demandas = array();

                    //CANTIDAD A METER EN DEMANDAS
                    $cantidadPdteDemanda = max(0, $rowLinea->CANTIDAD - $rowLinea->CANTIDAD_CANCELADA_POR_ENTREGA_FINAL);

                    //PRIMERO BUSCAMOS LA CANTIDAD ASIGNADA A NECESIDADES
                    $sqlNecesidades    = "SELECT ID_NECESIDAD, SUM(CANTIDAD) AS CANTIDAD_ASOCIADA
                                                                FROM NECESIDAD_LINEA 
                                                                WHERE ID_PEDIDO_SALIDA_LINEA = $rowLinea->ID_PEDIDO_SALIDA_LINEA AND ID_NECESIDAD_SUBORDINADA IS NULL AND BAJA = 0
                                                                GROUP BY ID_NECESIDAD";
                    $resultNecesidades = $bd->ExecSQL($sqlNecesidades);
                    while ($rowNecesidades = $bd->SigReg($resultNecesidades)):
                        if ($rowNecesidades->ID_NECESIDAD != NULL):
                            //BUSCAMOS LA DEMANDA ASOCIADA A ESA NECESIDAD
                            $rowDemandaNecesidad = $this->get_demanda("Pedido", $rowLinea->ID_PEDIDO_SALIDA_LINEA, $rowNecesidades->ID_NECESIDAD);

                            //SI EXISTE, COMPROBAMOS SI VARIA LA CANTIDAD
                            if ($rowDemandaNecesidad != false):

                                //ARRAY CAMBIOS
                                $array_cambios = array();

                                //SI LA CANTIDAD HA DISMINUIDO
                                if ($rowDemandaNecesidad->CANTIDAD_DEMANDA != $rowNecesidades->CANTIDAD_ASOCIADA):
                                    //OBTENEMOS LA VARIACION, DA IGUAL QUE SEA NEGATIVO, LA FUNCION LO ADMITE
                                    $cantidadModificada = $rowNecesidades->CANTIDAD_ASOCIADA - $rowDemandaNecesidad->CANTIDAD_DEMANDA;

                                    //LO GUARDAMOS COMO CAMBIO
                                    $array_cambios['CANTIDAD'] = $cantidadModificada;
                                endif;

                                //SI LA FECHA DEMANDA HA VARIADO
                                if ($rowLinea->FECHA_ENTREGA != $rowDemandaNecesidad->FECHA_DEMANDA):
                                    $array_cambios['FECHA_DEMANDA'] = $rowLinea->FECHA_ENTREGA;
                                endif;

                                //SI HACEN FALTA CAMBIOS
                                if (count( (array)$array_cambios) > 0):
                                    $arr_modificacion = $this->modificacion_demanda($rowDemandaNecesidad->ID_DEMANDA, $array_cambios);
                                    if (isset($arr_modificacion['error']) && $arr_modificacion['error'] != "")://SI VIENE ERROR
                                        $arr_respuesta['error'] = $arr_modificacion['error'];

                                        return $arr_respuesta;
                                    endif;
                                endif;

                                //GUARDAMOS LA DEMANDA
                                $arr_demandas[] = $rowDemandaNecesidad->ID_DEMANDA;

                            else://SI NO EXISTE, LO GENERAMOS
                                $arr_creacion = $this->creacion_demanda("Pedido", $rowLinea->ID_PEDIDO_SALIDA_LINEA, $rowNecesidades->CANTIDAD_ASOCIADA, $rowNecesidades->ID_NECESIDAD);
                                if (isset($arr_creacion['error']) && $arr_creacion['error'] != "")://SI VIENE ERROR
                                    $arr_respuesta['error'] = $arr_creacion['error'];

                                    return $arr_respuesta;
                                else:
                                    //GUARDAMOS LA DEMANDA
                                    $arr_demandas[] = $arr_creacion['idDemanda'];
                                endif;
                            endif;

                            //ACTUALIZAMOS LA CANTIDAD PENDIENTE DE DEMANDA
                            $cantidadPdteDemanda = $cantidadPdteDemanda - $rowNecesidades->CANTIDAD_ASOCIADA;

                        endif;
                    endwhile;//FIN RECORRER NECESIDADES


                    //SI QUEDA CANTIDAD PENDIENTE NO ASOCIADA A NECESIDADES
                    //SI NO QUEDA CANTIDAD PENDIENTE, AL NO METER LA DEMANDA SIN NECESIDAD AL $arr_demandas, LA ANULAREMOS EN ESE BUCLE
                    if ($cantidadPdteDemanda > EPSILON_SISTEMA):
                        //BUSCAMOS LA DEMANDA NO ASOCIADA A NECESIDADES
                        $rowDemandaPedido = $this->get_demanda("Pedido", $rowLinea->ID_PEDIDO_SALIDA_LINEA);

                        //SI EXISTE, COMPROBAMOS SI VARIA LA CANTIDAD
                        if ($rowDemandaPedido != false):

                            //ARRAY CAMBIOS
                            $array_cambios = array();

                            //SI LA CANTIDAD HA SIDO MODIFICADA
                            if ($rowDemandaPedido->CANTIDAD_DEMANDA != $cantidadPdteDemanda):
                                //OBTENEMOS LA VARIACION, DA IGUAL QUE SEA NEGATIVO, LA FUNCION LO ADMITE
                                $cantidadModificada = $cantidadPdteDemanda - $rowDemandaPedido->CANTIDAD_DEMANDA;

                                //LO GUARDAMOS COMO CAMBIO
                                $array_cambios['CANTIDAD'] = $cantidadModificada;
                            endif;

                            //SI LA FECHA DEMANDA HA VARIADO
                            if ($rowLinea->FECHA_ENTREGA != $rowDemandaPedido->FECHA_DEMANDA):
                                $array_cambios['FECHA_DEMANDA'] = $rowLinea->FECHA_ENTREGA;
                            endif;

                            //SI HACEN FALTA CAMBIOS
                            if (count( (array)$array_cambios) > 0):
                                $arr_modificacion = $this->modificacion_demanda($rowDemandaPedido->ID_DEMANDA, $array_cambios);
                                if (isset($arr_modificacion['error']) && $arr_modificacion['error'] != "")://SI VIENE ERROR
                                    $arr_respuesta['error'] = $arr_modificacion['error'];

                                    return $arr_respuesta;
                                endif;
                            endif;


                            //GUARDAMOS LA DEMANDA
                            $arr_demandas[] = $rowDemandaPedido->ID_DEMANDA;

                        else://SI NO EXISTE, LO GENERAMOS
                            $arr_creacion = $this->creacion_demanda("Pedido", $rowLinea->ID_PEDIDO_SALIDA_LINEA, $cantidadPdteDemanda);
                            if (isset($arr_creacion['error']) && $arr_creacion['error'] != "")://SI VIENE ERROR
                                $arr_respuesta['error'] = $arr_creacion['error'];

                                return $arr_respuesta;
                            else:
                                //GUARDAMOS LA DEMANDA
                                $arr_demandas[] = $arr_creacion['idDemanda'];
                            endif;
                        endif;
                    endif;//FIN SI QUEDA CANTIDAD PENDIENTE


                    //BUSCAMOS DEMANDAS ACTIVAS ASOCIADAS A LA LINEA PERO QUE YA NO DEBERIAN ESTAR ACTIVAS Y LAS ANULAMOS
                    $sqlWhere = "";
                    if (count((array) $arr_demandas) > 0):
                        $sqlWhere = " AND ID_DEMANDA NOT IN(" . implode(", ", (array) $arr_demandas) . ")";
                    endif;
                    $sqlDemandasPedido    = "SELECT ID_DEMANDA, ID_PEDIDO_SALIDA_LINEA, CANTIDAD_DEMANDA
                                                FROM DEMANDA 
                                                WHERE ID_PEDIDO_SALIDA_LINEA = $rowLinea->ID_PEDIDO_SALIDA_LINEA AND TIPO_DEMANDA = 'Pedido' $sqlWhere AND BAJA = 0";
                    $resultDemandasPedido = $bd->ExecSQL($sqlDemandasPedido);
                    if ($bd->NumRegs($resultDemandasPedido) > 0):
                        while ($rowDemandaPedido = $bd->SigReg($resultDemandasPedido)):
                            //PRIMERO, METEMOS LA CANTIDAD EN LA DEMANDA DEL PEDIDO
                            $arr_anulacion = $this->anular_demanda($rowDemandaPedido->ID_DEMANDA, "Actualizar Pedido");
                            if (isset($arr_anulacion['error']) && $arr_anulacion['error'] != "")://SI VIENE ERROR
                                $arr_respuesta['error'] = $arr_anulacion['error'];

                                return $arr_respuesta;
                            endif;
                        endwhile;

                    endif;//FIN DEMANDAS ASOCIADAS A LA NECESIDAD QUE YA NO PERTENECEN A ELLA


                endif;//FIN SI LA LINEA ESTA/NO ESTA DE BAJA
            endwhile;//FIN RECORRER LINEAS
        endif;


        return $arr_respuesta;
    }

    /**
     * @param $idDemanda
     * @param $cantidad
     * CANCELA LA RESERVA. USADO PARA PASAR EL MATERIAL A PLNA
     */
    function cancelar_reserva_linea($idReservaLinea, $observacionesLog = "")
    {
        global $bd;
        global $html;
        global $auxiliar;
        global $administrador;

        //ARRAY A DEVOLVER
        $arr_respuesta = array();

        //ARRAY PARA ALMACENAR LOS CAMBIOS DE ESTADO DE MATERIAL RESERVADO A REALIZAR
        $arrCambioEstadoReservado = array();
        $rowReservaLinea          = $bd->VerReg("RESERVA_LINEA", "ID_RESERVA_LINEA", $idReservaLinea);

        //SE OBTIENE LA RESERVA
        $rowReserva = $bd->VerReg("RESERVA", "ID_RESERVA", $rowReservaLinea->ID_RESERVA);
        if ($rowReservaLinea != false):

            if (($rowReservaLinea->ESTADO_LINEA == 'Cancelada')):
                return $arr_respuesta;
            endif;

            //BUSCO EL TIPO DE BLOQUEO PLANIFICADO
            $rowTipoBloqueoPlanificado = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "SP");

            //BUSCO EL TIPO DE BLOQUEO RESERVADO
            $rowTipoBloqueoReservado = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "RV");

            //BUSCO EL TIPO DE BLOQUEO RESERVADO PLANIFICADO
            $rowTipoBloqueoReservadoPlanificado = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "RVP");

            //SI NO EXISTE, CAMBIAMOS EL ESTADO DE LA ACTUAL
            $sqlUpdate = "UPDATE RESERVA_LINEA SET 
                                            ESTADO_LINEA = 'Cancelada'
                                            WHERE ID_RESERVA_LINEA = " . $rowReservaLinea->ID_RESERVA_LINEA;
            $bd->ExecSQL($sqlUpdate);


            //LOG MOVIMIENTOS
            $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Reserva", $rowReservaLinea->ID_RESERVA, 'Cancelar Cantidad:' . $rowReservaLinea->CANTIDAD . " . Reserva Linea: " . $rowReservaLinea->ID_RESERVA_LINEA . " " . $observacionesLog);

            //CREO LA CLAVE DEL ARRAY
            $clave    = $rowReservaLinea->ID_MATERIAL . "_" . $rowReservaLinea->ID_UBICACION . "_" . ($rowReservaLinea->ID_MATERIAL_FISICO == NULL ? 0 : $rowReservaLinea->ID_MATERIAL_FISICO) . "_" . ($rowReservaLinea->ID_TIPO_BLOQUEO == NULL ? 0 : $rowReservaLinea->ID_TIPO_BLOQUEO) . "_" . ($rowReservaLinea->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? 0 : $rowReservaLinea->ID_ORDEN_TRABAJO_MOVIMIENTO) . "_" . ($rowReservaLinea->ID_INCIDENCIA_CALIDAD == NULL ? 0 : $rowReservaLinea->ID_INCIDENCIA_CALIDAD);
            $cantidad = $rowReservaLinea->CANTIDAD;

            //EXTRAIGO LOS VALORES DE LA CLAVE
            $arrClave = explode("_", (string)$clave); //Material/Ubicacion/Material Fisico/Tipo Bloqueo/Orden Trabajo Movimiento/Incidencia Calidad

            //Tipo Bloqueo
            if ($arrClave[3] == $rowTipoBloqueoReservado->ID_TIPO_BLOQUEO):
                $idNuevoTipoBloqueo = 0;
            elseif ($arrClave[3] == $rowTipoBloqueoReservadoPlanificado->ID_TIPO_BLOQUEO):
                $idNuevoTipoBloqueo = $rowTipoBloqueoPlanificado->ID_TIPO_BLOQUEO;
            else:
                return $arr_respuesta;
            endif;

            //BUSCAMOS EL MATERIAL UBICACION ORIGEN PARA COMPROBAR SI HAY SUFICIENTE STOCK
            $GLOBALS["NotificaErrorPorEmail"] = "No";
            $clausulaWhere                    = "ID_MATERIAL = $arrClave[0] AND ID_UBICACION = $arrClave[1] AND ID_MATERIAL_FISICO " . ($arrClave[2] == 0 ? "IS NULL" : " = $arrClave[2]") . " AND ID_TIPO_BLOQUEO " . ($arrClave[3] == 0 ? "IS NULL" : " = $arrClave[3]") . " AND ID_ORDEN_TRABAJO_MOVIMIENTO " . ($arrClave[4] == 0 ? "IS NULL" : " = $arrClave[4]") . " AND ID_INCIDENCIA_CALIDAD " . ($arrClave[5] == 0 ? "IS NULL" : " = $arrClave[5]");
            $rowMatUbiOrigen                  = $bd->VerRegRest("MATERIAL_UBICACION", $clausulaWhere, "No");
            if (($rowMatUbiOrigen == false) || (($cantidad - $rowMatUbiOrigen->STOCK_TOTAL) > EPSILON_SISTEMA)):
                $arr_respuesta['error'] = $auxiliar->traduce("No se ha podido anular la Reserva porque el Stock ya no se encuentra Reservado", $administrador->ID_IDIOMA) . "<br>";

                //PREPARAMOS EL ERROR
                $rowUbi  = $bd->VerReg("UBICACION", "ID_UBICACION", $arrClave[1]);
                $textoMF = "-";
                if ($arrClave[2] != 0):
                    $rowMF   = $bd->VerReg("MATERIAL_FISICO", "ID_MATERIAL_FISICO", $arrClave[2]);
                    $textoMF = $rowMF->NUMERO_SERIE_LOTE;
                endif;
                $arr_respuesta['error'] .= $auxiliar->traduce("Cantidad", $administrador->ID_IDIOMA) . ": " . $rowMatUbiOrigen->STOCK_TOTAL . ", " . $auxiliar->traduce("Ubicacion", $administrador->ID_IDIOMA) . ": " . $rowUbi->UBICACION . ", " . $auxiliar->traduce("S / L", $administrador->ID_IDIOMA) . ": " . $textoMF . "<br>";

                return $arr_respuesta;
            endif;

            //CREO EL CAMBIO DE ESTADO
            $sqlInsert = "INSERT INTO CAMBIO_ESTADO SET
                              FECHA = '" . date("Y-m-d H:i:s") . "'
                              , TIPO_CAMBIO_ESTADO = 'AnulacionReservaDemanda'
                              , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                              , ID_MATERIAL = " . $arrClave[0] . " 
                              , ID_UBICACION = " . $arrClave[1] . " 
                              , CANTIDAD = $cantidad 
                              , ID_TIPO_BLOQUEO_INICIAL = " . ($arrClave[3] == 0 ? "NULL" : $arrClave[3]) . " 
                              , ID_TIPO_BLOQUEO_FINAL = " . ($idNuevoTipoBloqueo == 0 ? "NULL" : $idNuevoTipoBloqueo) . " 
                              , ID_MATERIAL_FISICO = " . ($arrClave[2] == 0 ? "NULL" : $arrClave[2]) . " 
                              , ID_ORDEN_TRABAJO_MOVIMIENTO = " . ($arrClave[4] == 0 ? "NULL" : $arrClave[4]) . " 
                              , ID_INCIDENCIA_CALIDAD = " . ($arrClave[5] == 0 ? "NULL" : $arrClave[5]);
            $bd->ExecSQL($sqlInsert);

            //DECREMENTO MATERIAL_UBICACION ORIGEN
            $sqlUpdate = "UPDATE MATERIAL_UBICACION SET
                              STOCK_TOTAL = STOCK_TOTAL - $cantidad 
                              , STOCK_OK = STOCK_OK - " . ($arrClave[3] == 0 ? $cantidad : 0) . "
                              , STOCK_BLOQUEADO = STOCK_BLOQUEADO - " . ($arrClave[3] == 0 ? 0 : $cantidad) . "
                              WHERE ID_MATERIAL_UBICACION = " . $rowMatUbiOrigen->ID_MATERIAL_UBICACION;
            $bd->ExecSQL($sqlUpdate);

            //BUSCO MATERIAL_UBICACION DESTINO
            $GLOBALS["NotificaErrorPorEmail"] = "No";
            $clausulaWhere                    = "ID_MATERIAL = $arrClave[0] AND ID_UBICACION = $arrClave[1] AND ID_MATERIAL_FISICO " . ($arrClave[2] == 0 ? "IS NULL" : " = $arrClave[2]") . " AND ID_TIPO_BLOQUEO " . ($idNuevoTipoBloqueo == 0 ? "IS NULL" : " = $idNuevoTipoBloqueo") . " AND ID_ORDEN_TRABAJO_MOVIMIENTO " . ($arrClave[4] == 0 ? "IS NULL" : " = $arrClave[4]") . " AND ID_INCIDENCIA_CALIDAD " . ($arrClave[5] == 0 ? "IS NULL" : " = $arrClave[5]");
            $rowMatUbiDestino                 = $bd->VerRegRest("MATERIAL_UBICACION", $clausulaWhere, "No");
            if ($rowMatUbiDestino == false):
                //CREO MATERIAL UBICACION DESTINO
                $sqlInsert = "INSERT INTO MATERIAL_UBICACION SET
                              ID_MATERIAL = $arrClave[0] 
                              , ID_UBICACION = $arrClave[1]
                              , ID_MATERIAL_FISICO = " . ($arrClave[2] == 0 ? "NULL" : $arrClave[2]) . " 
                              , ID_TIPO_BLOQUEO = " . ($idNuevoTipoBloqueo == 0 ? "NULL" : $idNuevoTipoBloqueo) . " 
                              , ID_ORDEN_TRABAJO_MOVIMIENTO = " . ($arrClave[4] == 0 ? "NULL" : $arrClave[4]) . " 
                              , ID_INCIDENCIA_CALIDAD = " . ($arrClave[5] == 0 ? "NULL" : $arrClave[5]);
                $bd->ExecSQL($sqlInsert);

                //GUARDO EL ID MATERIAL UBICACION DESTINO
                $idMatUbiDestino = $bd->IdAsignado();
            else:
                //GUARDO EL ID MATERIAL UBICACION DESTINO
                $idMatUbiDestino = $rowMatUbiDestino->ID_MATERIAL_UBICACION;
            endif;

            //INCREMENTO MATERIAL_UBICACION DESTINO
            $sqlUpdate = "UPDATE MATERIAL_UBICACION SET
                          STOCK_TOTAL = STOCK_TOTAL + $cantidad
                          , STOCK_OK = STOCK_OK + " . ($idNuevoTipoBloqueo == 0 ? $cantidad : 0) . "
                          , STOCK_BLOQUEADO = STOCK_BLOQUEADO + " . ($idNuevoTipoBloqueo == 0 ? 0 : $cantidad) . "
                          WHERE ID_MATERIAL_UBICACION = $idMatUbiDestino";
            $bd->ExecSQL($sqlUpdate);
            //FIN RECORRER MATERIALES UBICACION PARA HACER EL CAMBIO DE ESTADO CORRESPONDIENTE

            //BUSCAMOS LA DEMANDA
            $rowDemanda = $bd->VerReg("DEMANDA", "ID_DEMANDA", $rowReserva->ID_DEMANDA);

            //SI LA DEMANDA ES DE TIPO OT, ACTUALIZAMOS LAS TRANSFERENCIAS DE PENDIENTES SI LAS HAY
            if ($rowDemanda->TIPO_DEMANDA == 'OT'):
                $this->actualizar_transferencias_pendientes($rowDemanda->ID_DEMANDA);
            endif;

            return $arr_respuesta;
        else:
            $arr_respuesta['errores'] = $auxiliar->traduce("No se encuentra la Reserva para la Demanda", $administrador->ID_IDIOMA) . " $rowReserva->ID_DEMANDA";

            return $arr_respuesta;
        endif;//FIN SI EXISTE LA RESERVA
    }

    function get_material_ubicacion_ot($idOrdenTrabajo)
    {
        global $bd;

        $listaMUR = "0";

        //BUSCO LAS LINEAS DE MATERIAL RESERVADO PARA LA OT
        $sqlLineas    = "SELECT RL.ID_MATERIAL, RL.ID_MATERIAL_FISICO, RL.ID_UBICACION, RL.ID_TIPO_BLOQUEO, RL.ID_ORDEN_TRABAJO_MOVIMIENTO, RL.ID_INCIDENCIA_CALIDAD
                            FROM RESERVA_LINEA RL
                            INNER JOIN RESERVA R ON R.ID_RESERVA = RL.ID_RESERVA
                            INNER JOIN DEMANDA D ON D.ID_DEMANDA = R.ID_DEMANDA
                            INNER JOIN ORDEN_TRABAJO_LINEA OTL ON OTL.ID_ORDEN_TRABAJO_LINEA = D.ID_ORDEN_TRABAJO_LINEA
                            WHERE OTL.ID_ORDEN_TRABAJO = $idOrdenTrabajo AND D.TIPO_DEMANDA = 'OT' AND RL.ESTADO_LINEA = 'Reservada' AND D.BAJA = 0 AND R.BAJA = 0 AND RL.BAJA = 0";
        $resultLineas = $bd->ExecSQL($sqlLineas);

        //RECORRO LAS LINEAS DE MATERIAL RESERVADO PARA LA OT
        while ($rowLinea = $bd->SigReg($resultLineas)):
            $rowMatUbiRes = $bd->VerRegRest("MATERIAL_UBICACION", "ID_MATERIAL = $rowLinea->ID_MATERIAL AND ID_UBICACION = $rowLinea->ID_UBICACION AND ID_MATERIAL_FISICO " . ($rowLinea->ID_MATERIAL_FISICO == NULL ? 'IS NULL' : "= $rowLinea->ID_MATERIAL_FISICO") . " AND ID_TIPO_BLOQUEO " . ($rowLinea->ID_TIPO_BLOQUEO == NULL ? 'IS NULL' : "= $rowLinea->ID_TIPO_BLOQUEO") . " AND ID_ORDEN_TRABAJO_MOVIMIENTO " . ($rowLinea->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? 'IS NULL' : "= $rowLinea->ID_ORDEN_TRABAJO_MOVIMIENTO") . " AND ID_INCIDENCIA_CALIDAD " . ($rowLinea->ID_INCIDENCIA_CALIDAD == NULL ? 'IS NULL' : "= $rowLinea->ID_INCIDENCIA_CALIDAD"), "No");
            if ($listaMUR == "0"):
                $listaMUR = $rowMatUbiRes->ID_MATERIAL_UBICACION;
            else:
                $listaMUR .= ", $rowMatUbiRes->ID_MATERIAL_UBICACION";
            endif;
        endwhile;

        return $listaMUR;
    }

    /**
     * @param $idDemanda
     * ACTUALIZA LAS TRANSFERENCIAS DE PENDIENTES ASOCIADAS A LA OT DE LA DEMANDA
     */
    function actualizar_transferencias_pendientes($idDemanda)
    {
        global $bd;
        global $html;
        global $auxiliar;
        global $administrador;
        global $orden_trabajo;

        //BUSCAMOS LA DEMANDA
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowDemanda = $bd->VerReg("DEMANDA", "ID_DEMANDA", $idDemanda, "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);

        //SI EXISTE LA DEMANDA
        if ($rowDemanda->TIPO_DEMANDA == 'OT'):

            //BUSCO LA LINEA DE LA ORDEN DE TRABAJO
            $GLOBALS["NotificaErrorPorEmail"] = "No";
            $rowOrdenTrabajoLinea             = $bd->VerReg("ORDEN_TRABAJO_LINEA", "ID_ORDEN_TRABAJO_LINEA", $rowDemanda->ID_ORDEN_TRABAJO_LINEA, "No");
            unset($GLOBALS["NotificaErrorPorEmail"]);

            //SI EXISTE LA LINEA DE LA ORDEN DE TRABAJO
            if ($rowOrdenTrabajoLinea != false):

                //BUSCO LA ORDEN DE TRABAJO
                $GLOBALS["NotificaErrorPorEmail"] = "No";
                $rowOrdenTrabajo = $bd->VerReg("ORDEN_TRABAJO", "ID_ORDEN_TRABAJO", $rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO, "No");
                unset($GLOBALS["NotificaErrorPorEmail"]);

                if ($rowOrdenTrabajo->TIPO_LISTA == 'OTs de Pendientes'):

                    //BUSCO SI LA OT TIENE ALGUN PEDIDO ASOCIADO PENDIENTE DE RECEPCIONAR
                    $lineasPendientesRecepcionar = false;
                    $sqlLineasPendientesRecepcionar      = "SELECT PSL.ID_PEDIDO_SALIDA_LINEA, CANTIDAD
                                                                FROM PEDIDO_SALIDA PS
                                                                INNER JOIN PEDIDO_SALIDA_LINEA PSL ON PSL.ID_PEDIDO_SALIDA = PS.ID_PEDIDO_SALIDA
                                                                WHERE PS.ID_ORDEN_TRABAJO = $rowOrdenTrabajo->ID_ORDEN_TRABAJO AND PS.BAJA = 0 AND PSL.INDICADOR_BORRADO IS NULL AND PSL.BAJA = 0";
                    $resultLineasPendientesRecepcionar   = $bd->ExecSQL($sqlLineasPendientesRecepcionar);
                    while ($rowLineaPendienteRecepcionar = $bd->SigReg($resultLineasPendientesRecepcionar)):

                        //CALCULO LA CANTIDAD RECEPCIONADA
                        $sqlCantidadRecepcionada    = "SELECT IF(SUM(MSL.CANTIDAD) IS NULL, 0, SUM(MSL.CANTIDAD)) AS CANTIDAD_RECEPCIONADA
                                                        FROM MOVIMIENTO_SALIDA_LINEA MSL
                                                        INNER JOIN PEDIDO_SALIDA_LINEA PSL ON PSL.ID_PEDIDO_SALIDA_LINEA = MSL.ID_PEDIDO_SALIDA_LINEA
                                                        WHERE MSL.ID_PEDIDO_SALIDA_LINEA = $rowLineaPendienteRecepcionar->ID_PEDIDO_SALIDA_LINEA AND MSL.BAJA = 0 AND MSL.LINEA_ANULADA = 0 AND MSL.ESTADO IN ('Recepcionado')";
                        $resultCantidadRecepcionada = $bd->ExecSQL($sqlCantidadRecepcionada);
                        $rowCantidadRecepcionada    = $bd->SigReg($resultCantidadRecepcionada);

                        if (($rowLineaPendienteRecepcionar->CANTIDAD - $rowCantidadRecepcionada->CANTIDAD_RECEPCIONADA) > EPSILON_SISTEMA):
                            $lineasPendientesRecepcionar = true;
                        endif;
                    endwhile;

                    //SI HAY CANTIDAD PENDIENTE DE RECEPCIONAR ASOCIADA A ALGUNA DE LAS LINEAS DE LA OT, CANCELAMOS LAS TRANSFERENCIAS ACTIVAS
                    if ($lineasPendientesRecepcionar):
                        //BUSCO LAS LINEAS DE TRANSFERENCIAS DE PENDIENTES ASOCIADAS A LA LINEA DE OT QUE ESTEN PENDIENTES DE TRANSFERIR Y NO SE HAYAN ACTUALIZADO
                        $sqlLineasTransferenciasPendientes = "SELECT MTPL.ID_MOVIMIENTO_TRANSFERENCIA_PENDIENTES_LINEA, MTPL.ID_MOVIMIENTO_TRANSFERENCIA_PENDIENTES
                                                                FROM MOVIMIENTO_TRANSFERENCIA_PENDIENTES_LINEA MTPL
                                                                INNER JOIN MOVIMIENTO_TRANSFERENCIA_PENDIENTES MTP ON MTP.ID_MOVIMIENTO_TRANSFERENCIA_PENDIENTES = MTPL.ID_MOVIMIENTO_TRANSFERENCIA_PENDIENTES
                                                                INNER JOIN ORDEN_TRABAJO_LINEA OTL ON OTL.ID_ORDEN_TRABAJO_LINEA = MTPL.ID_ORDEN_TRABAJO_LINEA
                                                                WHERE MTP.ESTADO IN ('Creada', 'En Proceso') AND MTP.BAJA = 0 AND OTL.ID_ORDEN_TRABAJO = $rowOrdenTrabajo->ID_ORDEN_TRABAJO
                                                                AND MTPL.ESTADO = 'Pendiente Transferir' AND MTPL.BAJA = 0";
                        $resultLineasTransferenciasPendientes = $bd->ExecSQL($sqlLineasTransferenciasPendientes);

                        //RECORRO LAS LINEAS DE TRANSFERENCIAS DE PENDIENTES
                        while ($rowTransferenciaPendientesLinea = $bd->SigReg($resultLineasTransferenciasPendientes)):
                            //CANCELO LA LINEA DE LA TRANSFERENCIA DE PENDIENTES
                            $orden_trabajo->CancelarMovimientoTransferenciaPendientesLinea($rowTransferenciaPendientesLinea->ID_MOVIMIENTO_TRANSFERENCIA_PENDIENTES_LINEA, "Manual", "CambioMaterialOrigen", "Generación Pedido");
                        endwhile;
                    else:
                        //BUSCO LAS LINEAS DE RESERVA QUE TENGA ACTIVAS PARA LA LINEA DE LA OT
                        $resultLineasReserva = $this->get_reservas_linea_ot($rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO_LINEA, "Reservada", $rowDemanda->ID_DEMANDA);

                        //GUARDO EL ARRAY CON LAS LINEAS DE TRANSFERENCIAS DE PENDIENTES CREADAS/ACTUALIZADAS PARA CANCELAR LAS RESTANTES
                        $arrMTPL = array();

                        //RECORRO LAS LINEAS DE TRANSFERENCIAS DE PENDIENTES ASOCIADAS A LA LINEA DE OT
                        while ($rowLineaReserva = $bd->SigReg($resultLineasReserva)):
                            //COMPRUEBO SI YA EXISTE UNA LINEA DE TRANSFERENCIA DE PENDIENTES SIMILAR CREADA PARA ESA LINEA DE OT
                            $GLOBALS["NotificaErrorPorEmail"] = "No";
                            $rowTransferenciaPendientesLinea  = $bd->VerRegRest("MOVIMIENTO_TRANSFERENCIA_PENDIENTES_LINEA", "ID_ORDEN_TRABAJO_LINEA = $rowDemanda->ID_ORDEN_TRABAJO_LINEA AND ID_MATERIAL = $rowLineaReserva->ID_MATERIAL AND ID_MATERIAL_FISICO " . ($rowLineaReserva->ID_MATERIAL_FISICO == NULL ? "IS NULL" : "= $rowLineaReserva->ID_MATERIAL_FISICO") . " AND ID_UBICACION_ORIGEN = $rowLineaReserva->ID_UBICACION AND ESTADO <> 'Cancelada' AND BAJA = 0 ORDER BY FECHA_CREACION DESC", "No");
                            unset($GLOBALS["NotificaErrorPorEmail"]);

                            if ($rowTransferenciaPendientesLinea != false):
                                if ($rowTransferenciaPendientesLinea->ESTADO == "Pendiente Transferir"):
                                    //ACTUALIZO LA LINEA DE LA TRANSFERENCIA DE PENDIENTES
                                    $sqlUpdate = "UPDATE MOVIMIENTO_TRANSFERENCIA_PENDIENTES_LINEA SET
                                                    ID_ADMINISTRADOR_ULTIMA_MODIFICACION = $administrador->ID_ADMINISTRADOR
                                                    , ID_UBICACION_ORIGEN = $rowLineaReserva->ID_UBICACION
                                                    , CANTIDAD = $rowLineaReserva->CANTIDAD
                                                    WHERE ID_MOVIMIENTO_TRANSFERENCIA_PENDIENTES_LINEA = $rowTransferenciaPendientesLinea->ID_MOVIMIENTO_TRANSFERENCIA_PENDIENTES_LINEA";
                                    $bd->ExecSQL($sqlUpdate);
                                    $arrMTPL[] = $rowTransferenciaPendientesLinea->ID_MOVIMIENTO_TRANSFERENCIA_PENDIENTES_LINEA;

                                    //BUSCO LA LINEA DE LA TRANSFERENCIA DE PENDIENTES ACTUALIZADA
                                    $rowTransferenciaPendientesLineaActualizada = $bd->VerReg("MOVIMIENTO_TRANSFERENCIA_PENDIENTES_LINEA", "ID_MOVIMIENTO_TRANSFERENCIA_PENDIENTES_LINEA", $rowTransferenciaPendientesLinea->ID_MOVIMIENTO_TRANSFERENCIA_PENDIENTES_LINEA);

                                    // LOG MOVIMIENTOS
                                    $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, 'Creación', "Linea Transferencia Pendientes", $rowTransferenciaPendientesLinea->ID_MOVIMIENTO_TRANSFERENCIA_PENDIENTES_LINEA, "Modificacion linea transferencia pendientes de forma manual por actualización transferencia en base a reservas", "MOVIMIENTO_TRANSFERENCIA_PENDIENTES_LINEA", $rowTransferenciaPendientesLinea, $rowTransferenciaPendientesLineaActualizada);

                                    //BUSCO LA TRANSFERENCIA DE PENDIENTES
                                    $rowTransferenciaPendientes = $bd->VerReg("MOVIMIENTO_TRANSFERENCIA_PENDIENTES", "ID_MOVIMIENTO_TRANSFERENCIA_PENDIENTES", $rowTransferenciaPendientesLinea->ID_MOVIMIENTO_TRANSFERENCIA_PENDIENTES);

                                    //ACTUALIZO EL ESTADO DE LA TRANSFERENCIA DE PENDIENTES
                                    $orden_trabajo->ActualizarEstadoTransferenciaPendientes($rowTransferenciaPendientes->ID_MOVIMIENTO_TRANSFERENCIA_PENDIENTES);

                                    //BUSCO LA TRANSFERENCIA DE PENDIENTES ACTUALIZADA
                                    $rowTransferenciaPendientesActualizada = $bd->VerReg("MOVIMIENTO_TRANSFERENCIA_PENDIENTES", "ID_MOVIMIENTO_TRANSFERENCIA_PENDIENTES", $rowTransferenciaPendientes->ID_MOVIMIENTO_TRANSFERENCIA_PENDIENTES);

                                    // LOG MOVIMIENTOS
                                    $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, 'Modificación', "Transferencia Pendientes", $rowTransferenciaPendientes->ID_MOVIMIENTO_TRANSFERENCIA_PENDIENTES, "Modificacion transferencia pendientes de forma manual por actualización transferencia en base a reservas", "MOVIMIENTO_TRANSFERENCIA_PENDIENTES", $rowTransferenciaPendientes, $rowTransferenciaPendientesActualizada);
                                endif;
                            else:
                                //BUSCO LA UBICACION DONDE SE REALIZO EL CAMBIO DE ESTADO
                                $rowUbicacionOrigen = $bd->VerReg("UBICACION", "ID_UBICACION", $rowLineaReserva->ID_UBICACION);

                                //BUSCO LA UBICACION DESTINO DONDE REUBICAREMOS EL MATERIAL, PRIMERO SI HAY ALGO DE LA OT YA TRANSFERIDO Y SINO LA UBICACION GENERICA DE PREVENTIVO DE PENDIENTES
                                $sqlUbicacionDestino    = "SELECT DISTINCT OTUT.ID_UBICACION
                                                            FROM ORDEN_TRABAJO_UBICACION_TRANSFERENCIA OTUT
                                                            INNER JOIN UBICACION U ON U.ID_UBICACION = OTUT.ID_UBICACION
                                                            WHERE OTUT.ID_ORDEN_TRABAJO = $rowOrdenTrabajo->ID_ORDEN_TRABAJO AND OTUT.BAJA = 0 AND U.ID_ALMACEN = $rowUbicacionOrigen->ID_ALMACEN AND U.BAJA = 0
                                                            ORDER BY OTUT.ULTIMA_UBICACION_UTILIZADA DESC";
                                $resultUbicacionDestino = $bd->ExecSQL($sqlUbicacionDestino);
                                while ($rowUbicacionDestino = $bd->SigReg($resultUbicacionDestino)): //UBICACIONES DONDE HA SIDO TRANFERIDA PARTE DEL MATERIAL DE LA ORDEN DE TRABAJO
                                    $num = $bd->NumRegsTabla("MATERIAL_UBICACION", "ID_UBICACION = $rowUbicacionDestino->ID_UBICACION AND ACTIVO = 1");
                                    if ($num > 0):
                                        $rowUbicacionDestino = $bd->VerReg("UBICACION", "ID_UBICACION", $rowUbicacionDestino->ID_UBICACION);
                                        break;
                                    endif;
                                endwhile;
                                if ($rowUbicacionDestino == false): //UBICACION GENERICA DE PREVENTIVO DE PENDIENTES
                                    $GLOBALS["NotificaErrorPorEmail"] = "No";
                                    $rowUbicacionDestino              = $bd->VerRegRest("UBICACION", "ID_ALMACEN = $rowUbicacionOrigen->ID_ALMACEN AND TIPO_UBICACION = 'Preventivo' AND TIPO_PREVENTIVO = 'Pendientes'", "No");
                                    unset($GLOBALS["NotificaErrorPorEmail"]);
                                    if ($rowUbicacionDestino == false):
                                        continue;
                                    endif;
                                endif;

                                //SI LA UBICACION DE DESTINO ES LA MISMA QUE LA DE ORIGEN, NO GENERAMOS LA TRANSFERENCIA DE PENDIENTES
                                if ($rowUbicacionDestino->ID_UBICACION == $rowUbicacionOrigen->ID_UBICACION):
                                    continue;
                                endif;

                                //COMPRUEBO SI EXISTE UNA TRANSFERENCIA DE PENDIENTES EN ESTADO CREADA PARA ESE ALMACEN
                                $GLOBALS["NotificaErrorPorEmail"] = "No";
                                $rowTransferenciaPendientes       = $bd->VerRegRest("MOVIMIENTO_TRANSFERENCIA_PENDIENTES", "ESTADO = 'Creada' AND ID_ALMACEN = $rowUbicacionOrigen->ID_ALMACEN AND BAJA = 0", "No");
                                unset($GLOBALS["NotificaErrorPorEmail"]);

                                //SI NO EXISTE LA TRANSFERENCIA DE PENDIENTES LA GENERO
                                if ($rowTransferenciaPendientes == false):
                                    $sqlInsert = "INSERT INTO MOVIMIENTO_TRANSFERENCIA_PENDIENTES SET
                                                    FECHA_CREACION = '" . date("Y-m-d H:i:s") . "'
                                                    , ESTADO = 'Creada'
                                                    , ID_ALMACEN = $rowUbicacionOrigen->ID_ALMACEN";
                                    $bd->ExecSQL($sqlInsert);
                                    $rowTransferenciaPendientes = $bd->VerReg("MOVIMIENTO_TRANSFERENCIA_PENDIENTES", "ID_MOVIMIENTO_TRANSFERENCIA_PENDIENTES", $bd->IdAsignado());

                                    // LOG MOVIMIENTOS
                                    $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, 'Creación', "Transferencia Pendientes", $rowTransferenciaPendientes->ID_MOVIMIENTO_TRANSFERENCIA_PENDIENTES, "Creacion transferencia pendientes de forma manual por actualización transferencia en base a reservas", "MOVIMIENTO_TRANSFERENCIA_PENDIENTES", NULL, $rowTransferenciaPendientes);
                                endif;

                                //BUSCO EL MAXIMO NUMERO DE LINEA
                                $UltimoNumeroLinea       = 0;
                                $sqlUltimoNumeroLinea    = "SELECT MAX(CAST(POSICION AS UNSIGNED)) AS NUMERO_LINEA FROM MOVIMIENTO_TRANSFERENCIA_PENDIENTES_LINEA WHERE ID_MOVIMIENTO_TRANSFERENCIA_PENDIENTES = $rowTransferenciaPendientes->ID_MOVIMIENTO_TRANSFERENCIA_PENDIENTES";
                                $resultUltimoNumeroLinea = $bd->ExecSQL($sqlUltimoNumeroLinea);
                                if ($resultUltimoNumeroLinea != false):
                                    $rowUltimoNumeroLinea = $bd->SigReg($resultUltimoNumeroLinea);
                                    if ($rowUltimoNumeroLinea->NUMERO_LINEA != NULL):
                                        $UltimoNumeroLinea = $rowUltimoNumeroLinea->NUMERO_LINEA;
                                    endif;
                                endif;

                                //INCREMENTO EN 10 EL NUMERO DE POSICION
                                $SiguienteNumeroLinea = $UltimoNumeroLinea + 10;

                                //CREO LA LINEA DE LA TRANSFERENCIA DE PENDIENTES
                                $sqlInsert = "INSERT INTO MOVIMIENTO_TRANSFERENCIA_PENDIENTES_LINEA SET
                                                ID_MOVIMIENTO_TRANSFERENCIA_PENDIENTES = $rowTransferenciaPendientes->ID_MOVIMIENTO_TRANSFERENCIA_PENDIENTES
                                                , POSICION = '" . str_pad( (string)$SiguienteNumeroLinea, 5, "0", STR_PAD_LEFT) . "'
                                                , FECHA_CREACION = '" . date("Y-m-d H:i:s") . "'
                                                , ESTADO = 'Pendiente Transferir'
                                                , ID_ADMINISTRADOR_CREACION = $administrador->ID_ADMINISTRADOR
                                                , ID_ORDEN_TRABAJO_LINEA = $rowDemanda->ID_ORDEN_TRABAJO_LINEA
                                                , ID_MATERIAL = $rowLineaReserva->ID_MATERIAL
                                                , ID_MATERIAL_FISICO = " . ($rowLineaReserva->ID_MATERIAL_FISICO == NULL ? 'NULL' : $rowLineaReserva->ID_MATERIAL_FISICO) . "
                                                , ID_UBICACION_ORIGEN = $rowLineaReserva->ID_UBICACION
                                                , ID_UBICACION_DESTINO = $rowUbicacionDestino->ID_UBICACION
                                                , CANTIDAD = $rowLineaReserva->CANTIDAD";
                                $bd->ExecSQL($sqlInsert);
                                $idMovimientoTransferenciaPendientesLinea = $bd->IdAsignado();

                                $arrMTPL[] = $idMovimientoTransferenciaPendientesLinea;

                                //BUSCO LA LINEA DE LA TRANSFERENCIA DE PENDIENTES ACTUALIZADA
                                $rowTransferenciaPendientesLineaActualizada = $bd->VerReg("MOVIMIENTO_TRANSFERENCIA_PENDIENTES_LINEA", "ID_MOVIMIENTO_TRANSFERENCIA_PENDIENTES_LINEA", $idMovimientoTransferenciaPendientesLinea);

                                // LOG MOVIMIENTOS
                                $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, 'Creación', "Linea Transferencia Pendientes", $idMovimientoTransferenciaPendientesLinea, "Modificacion linea transferencia pendientes de forma manual por actualización transferencia en base a reservas", "MOVIMIENTO_TRANSFERENCIA_PENDIENTES_LINEA", NULL, $rowTransferenciaPendientesLineaActualizada);

                                //ACTUALIZO EL ESTADO DE LA TRANSFERENCIA DE PENDIENTES
                                $orden_trabajo->ActualizarEstadoTransferenciaPendientes($rowTransferenciaPendientes->ID_MOVIMIENTO_TRANSFERENCIA_PENDIENTES);

                                //BUSCO LA TRANSFERENCIA DE PENDIENTES ACTUALIZADA
                                $rowTransferenciaPendientesActualizada = $bd->VerReg("MOVIMIENTO_TRANSFERENCIA_PENDIENTES", "ID_MOVIMIENTO_TRANSFERENCIA_PENDIENTES", $rowTransferenciaPendientes->ID_MOVIMIENTO_TRANSFERENCIA_PENDIENTES);

                                // LOG MOVIMIENTOS
                                $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, 'Modificación', "Transferencia Pendientes", $rowTransferenciaPendientes->ID_MOVIMIENTO_TRANSFERENCIA_PENDIENTES, "Modificacion transferencia pendientes de forma manual por actualización transferencia en base a reservas", "MOVIMIENTO_TRANSFERENCIA_PENDIENTES", $rowTransferenciaPendientes, $rowTransferenciaPendientesActualizada);
                            endif;
                        endwhile;

                        //SI HAY DESTINOS DEFINIDOS NO LOS TENEMOS EN CUENTA
                        $sqlWhere = "";
                        if (count((array) $arrMTPL) > 0):
                            $sqlWhere = "AND MTPL.ID_MOVIMIENTO_TRANSFERENCIA_PENDIENTES_LINEA NOT IN (" . implode(',', (array) $arrMTPL) . ")";
                        endif;

                        //BUSCO LAS LINEAS DE TRANSFERENCIAS DE PENDIENTES ASOCIADAS A LA LINEA DE OT QUE ESTEN PENDIENTES DE TRANSFERIR Y NO SE HAYAN ACTUALIZADO
                        $sqlLineasTransferenciasPendientes = "SELECT MTPL.ID_MOVIMIENTO_TRANSFERENCIA_PENDIENTES_LINEA, MTPL.ID_MOVIMIENTO_TRANSFERENCIA_PENDIENTES
                                                                FROM MOVIMIENTO_TRANSFERENCIA_PENDIENTES_LINEA MTPL
                                                                INNER JOIN MOVIMIENTO_TRANSFERENCIA_PENDIENTES MTP ON MTP.ID_MOVIMIENTO_TRANSFERENCIA_PENDIENTES = MTPL.ID_MOVIMIENTO_TRANSFERENCIA_PENDIENTES
                                                                WHERE MTP.ESTADO IN ('Creada', 'En Proceso') AND MTP.BAJA = 0 AND MTPL.ID_ORDEN_TRABAJO_LINEA = $rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO_LINEA
                                                                AND MTPL.ESTADO = 'Pendiente Transferir' AND MTPL.BAJA = 0 $sqlWhere";
                        $resultLineasTransferenciasPendientes = $bd->ExecSQL($sqlLineasTransferenciasPendientes);

                        //RECORRO LAS LINEAS DE TRANSFERENCIAS DE PENDIENTES
                        while ($rowTransferenciaPendientesLinea = $bd->SigReg($resultLineasTransferenciasPendientes)):
                            //CANCELO LA LINEA DE LA TRANSFERENCIA DE PENDIENTES
                            $orden_trabajo->CancelarMovimientoTransferenciaPendientesLinea($rowTransferenciaPendientesLinea->ID_MOVIMIENTO_TRANSFERENCIA_PENDIENTES_LINEA, "Manual", "CambioMaterialOrigen", "Demanda OT Actualizada");
                        endwhile;
                    endif;
                endif;
            endif;
        endif;
    }
} // FIN CLASE