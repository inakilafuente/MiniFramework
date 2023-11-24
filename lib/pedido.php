<?php

# pedido
# Clase pedido contiene todas las funciones necesarias para
# la interaccion con la clase albaran
# Se incluira en las sesiones
# Agosto 2006 Carlos Arnáez

class pedido
{

    var $erroresExcel;    //ARRAY DE APOPY PARA LA IMPORTACION DE ERRORES A EXCEL

    function __construct()
    {
    } // Fin pedido

    function Hayar_Materiales($idPedido)
    {

        global $bd;

        $sql    = "SELECT distinct ID_MATERIAL FROM PEDIDO_LINEA WHERE ID_PEDIDO_SALIDA=$idPedido";
        $result = $bd->ExecSQL($sql, "No");
        if ($result == false)
            return "Err.";
        while ($row = $bd->SigReg($result)):
            $Materiales .= ", " . $row->ID_MATERIAL;
        endwhile;
        $Materiales = substr( (string) $Materiales, 2);
        if ($Materiales == "")
            $Materiales = "-";

        return $Materiales;

    } // Fin Hayar_Materiales

    function Hayar_Numero_Pendientes($idPedido)
    {

        global $bd;

        $sql    = "SELECT SUM(POR_ENTREGAR) AS CUANTOS FROM PEDIDO_LINEA WHERE ID_PEDIDO_SALIDA=$idPedido";
        $result = $bd->ExecSQL($sql, "No");
        if ($result == false)
            return "Err.";
        $row = $bd->SigReg($result);

        return $row->CUANTOS;

    } // Fin Hayar_Materiales

    //Consulta si el cliente tiene algún pedido en estado "Grabado" y si tiene lo devuelve
    function Tiene_Pedido_Grabado($idCliente)
    {

        global $bd;

        $sql    = "SELECT * FROM PEDIDO_SALIDA WHERE ID_CLIENTE=$idCliente AND ESTADO='Grabado' ORDER BY ID_PEDIDO_SALIDA DESC";
        $result = $bd->ExecSQL($sql, "No");
        if ($result == false)
            return "Err.";
        if ($bd->NumRegs($result) > 0):

            $row      = $bd->SigReg($result);
            $idPedido = $row->ID_PEDIDO_SALIDA;
            $sql      = "SELECT * FROM PEDIDO_SALIDA WHERE ID_PEDIDO_SALIDA=$idPedido";
            $result   = $bd->ExecSQL($sql, "No");
            if ($result == false)
                return "Err.";
            if ($bd->NumRegs($result) > 0):
                $row = $bd->SigReg($result);

                return $row;
            else:
                return false;
            endif;

        endif;


    } // Fin Tiene_Pedido_Grabado

    //Consulta si el cliente tiene algún pedido en estado "Grabado" del tipo oficina
    function Tiene_Pedido_Grabado_Oficina($idCliente)
    {

        global $bd;

        $sql    = "SELECT * FROM PEDIDO_SALIDA WHERE ID_CLIENTE=$idCliente AND ESTADO='Grabado' AND TIPO_PEDIDO='Oficina' AND CREADO_POR='Oficina' ORDER BY ID_PEDIDO_SALIDA DESC";
        $result = $bd->ExecSQL($sql, "No");
        if ($result == false)
            return "Err.";
        if ($bd->NumRegs($result) > 0):

            $row      = $bd->SigReg($result);
            $idPedido = $row->ID_PEDIDO_SALIDA;
            $sql      = "SELECT * FROM PEDIDO_SALIDA WHERE ID_PEDIDO_SALIDA=$idPedido";
            $result   = $bd->ExecSQL($sql, "No");
            if ($result == false)
                return "Err.";
            if ($bd->NumRegs($result) > 0):
                $row = $bd->SigReg($result);

                return $row;
            else:
                return false;
            endif;

        endif;

    } // Fin Tiene_Pedido_Grabado

    //Consulta si el cliente tiene algún pedido en estado "Grabado" del tipo marketing
    function Tiene_Pedido_Grabado_Marketing($idCliente)
    {

        global $bd;

        $sql    = "SELECT * FROM PEDIDO_SALIDA WHERE ID_CLIENTE=$idCliente AND ESTADO='Grabado' AND TIPO_PEDIDO='Marketing' ORDER BY ID_PEDIDO_SALIDA DESC";
        $result = $bd->ExecSQL($sql, "No");
        if ($result == false)
            return "Err.";
        if ($bd->NumRegs($result) > 0):

            $row      = $bd->SigReg($result);
            $idPedido = $row->ID_PEDIDO_SALIDA;
            $sql      = "SELECT * FROM PEDIDO_SALIDA WHERE ID_PEDIDO_SALIDA=$idPedido";
            $result   = $bd->ExecSQL($sql, "No");
            if ($result == false)
                return "Err.";
            if ($bd->NumRegs($result) > 0):
                $row = $bd->SigReg($result);

                return $row;
            else:
                return false;
            endif;

        endif;

    } // Fin Tiene_Pedido_Grabado

    function Insertar_Pedido_Cliente($idCliente, $idEmpleado, $observacionesG, $observacionesE)
    {
        global $bd;
        global $cli;

        //OBTENEMOS LA PARTIDA PRESUPUESTARIA Y LA CUENTA CONTABLE DE LA OFICINA
        $rowOficina              = $cli->Hayar_Cliente($idCliente);
        $txPartidaPresupuestaria = $rowOficina->PARTIDA_PRESUPUESTARIA;
        $txCuentaContable        = $rowOficina->CUENTA_CONTABLE;

        // INSERTO EL PEDIDO
        $fechaPedido = date("Y-m-d H:i:s");
        if ($idEmpleado == "")
            $idEmpleado = "No Definido";
        $sql = "INSERT INTO PEDIDO_SALIDA (ID_PEDIDO_SALIDA,ID_CLIENTE,ID_EMPLEADO,CREADO_POR,FECHA_PEDIDO,PARTIDA_PRESUPUESTARIA,CUENTA_CONTABLE,OBSERVACIONES_GENERALES,OBSERVACIONES_ETIQUETAS) VALUES('',$idCliente,'$idEmpleado','Oficina','$fechaPedido','$txPartidaPresupuestaria','$txCuentaContable','$observacionesG','$observacionesE')";
        $bd->ExecSQL($sql);

        return $bd->IdAsignado();

    } // Fin Insertar_Pedido_Cliente

    function Insertar_Pedido_Gestor($idCliente, $idEmpleado, $tipo, $fechaEntrega, $partidaPresupuestaria, $cuentaContable, $observacionesG, $observacionesE, $observacionesO, $observacionesEntrega, $txIdPedidoCliente, $txSolicitante, $selAlmacen)
    {
        global $bd;

        // INSERTO EL PEDIDO
        $fechaPedido = date("Y-m-d H:i:s");
        $sql         = "INSERT INTO PEDIDO_SALIDA (ID_PEDIDO_SALIDA,ID_CLIENTE,ID_EMPLEADO,CREADO_POR,TIPO_PEDIDO,FECHA_ENTREGA,FECHA_PEDIDO,PARTIDA_PRESUPUESTARIA,CUENTA_CONTABLE,OBSERVACIONES_GENERALES,OBSERVACIONES_ETIQUETAS,OBSERVACIONES_OPERARIO, OBSERVACIONES_ENTREGA, ID_PEDIDO_CLIENTE, SOLICITANTE,ID_ALMACEN) VALUES('',$idCliente,'$idEmpleado','Gestion','$tipo','$fechaEntrega','$fechaPedido','$partidaPresupuestaria','$cuentaContable','$observacionesG','$observacionesE', '$observacionesO', '$observacionesEntrega', '$txIdPedidoCliente', '$txSolicitante', $selAlmacen)";
        $bd->ExecSQL($sql);

        return $bd->IdAsignado();

    } // Fin Insertar_Pedido_Gestor

    function Obtener_Pedido($idPedido)
    {
        global $bd;

        $sql    = "SELECT * FROM PEDIDO_SALIDA WHERE ID_PEDIDO_SALIDA=$idPedido";
        $result = $bd->ExecSQL($sql, "No");
        if ($result == false)
            return "Err.";
        if ($bd->NumRegs($result) > 0):
            $row = $bd->SigReg($result);

            return $row;
        else:
            return false;
        endif;
    } // Fin Obtener_Pedido

    //PREGUNTA SI EL ESTADO DE UN PEDIDO ES "Grabado"
    function Es_Grabado($idPedido)
    {

        global $bd;

        $sql    = "SELECT * FROM PEDIDO_SALIDA WHERE ID_PEDIDO_SALIDA=$idPedido";
        $result = $bd->ExecSQL($sql, "No");
        if ($result == false)
            return "Err.";
        if ($bd->NumRegs($result) > 0):
            $row = $bd->SigReg($result);
            if ($row->ESTADO == "Grabado"):
                return true;
            else:
                return false;
            endif;
        else:
            return false;
        endif;
    } // Fin Es_Grabado

    //PREGUNTA SI EL ESTADO DE UN PEDIDO ES "Grabado"
    function Es_Pedido_Gestion($idPedido)
    {

        global $bd;

        $sql    = "SELECT * FROM PEDIDO_SALIDA WHERE ID_PEDIDO_SALIDA=$idPedido";
        $result = $bd->ExecSQL($sql, "No");
        if ($result == false)
            return "Err.";
        if ($bd->NumRegs($result) > 0):
            $row = $bd->SigReg($result);
            if ($row->CREADO_POR == "Gestion"):
                return true;
            else:
                return false;
            endif;
        else:
            return false;
        endif;
    } // Fin Es_Pedido_Gestion

    //PREGUNTA SI EL ESTADO DE UN PEDIDO ES "Expedido"
    function Es_Expedido($idPedido)
    {

        global $bd;

        $sql    = "SELECT * FROM PEDIDO_SALIDA WHERE ID_PEDIDO_SALIDA=$idPedido";
        $result = $bd->ExecSQL($sql, "No");
        if ($result == false)
            return "Err.";
        if ($bd->NumRegs($result) > 0):
            $row = $bd->SigReg($result);
            if ($row->ESTADO == "Expedido"):
                return true;
            else:
                return false;
            endif;
        else:
            return false;
        endif;
    } // Fin Es_Grabado

    function Modificar_Pedido_Cliente($idPedido, $observacionesG, $observacionesE)
    {
        global $bd;
        global $cli;

        //OBTENEMOS LA PARTIDA PRESUPUESTARIA Y LA CUENTA CONTABLE DE LA OFICINA POR SI HAN CAMBIADO
        $rowPedido               = $this->Obtener_Pedido($idPedido);
        $rowOficina              = $cli->Hayar_Cliente($rowPedido->ID_CLIENTE);
        $txPartidaPresupuestaria = $rowOficina->PARTIDA_PRESUPUESTARIA;
        $txCuentaContable        = $rowOficina->CUENTA_CONTABLE;

        // MODIFICO EL PEDIDO
        $sql = "UPDATE PEDIDO_SALIDA SET OBSERVACIONES_GENERALES = '$observacionesG', OBSERVACIONES_ETIQUETAS = '$observacionesE', PARTIDA_PRESUPUESTARIA = '$txPartidaPresupuestaria', CUENTA_CONTABLE = '$txCuentaContable' WHERE ID_PEDIDO_SALIDA = $idPedido";
        $bd->ExecSQL($sql);

    } // Fin Modificar_Pedido_Cliente

    function Modificar_Pedido_Gestor($idPedido, $idCliente, $tipo, $fechaEntrega, $partidaPresupuestaria, $cuentaContable, $observacionesG, $observacionesE, $observacionesO, $observacionesEntrega, $txIdPedidoCliente, $txSolicitante, $selAlmacen)
    {
        global $bd;

        // MODIFICO EL PEDIDO
        $sql = "UPDATE PEDIDO_SALIDA SET ID_CLIENTE = $idCliente, TIPO_PEDIDO = '$tipo', FECHA_ENTREGA = '$fechaEntrega', PARTIDA_PRESUPUESTARIA = '$partidaPresupuestaria', CUENTA_CONTABLE = '$cuentaContable', OBSERVACIONES_GENERALES = '$observacionesG', OBSERVACIONES_ETIQUETAS = '$observacionesE', OBSERVACIONES_OPERARIO = '$observacionesO', OBSERVACIONES_ENTREGA = '$observacionesEntrega', ID_PEDIDO_CLIENTE='$txIdPedidoCliente', SOLICITANTE='$txSolicitante', ID_ALMACEN=$selAlmacen WHERE ID_PEDIDO_SALIDA = $idPedido";
        $bd->ExecSQL($sql);

    } // Fin Modificar_Pedido_Gestor


    function Insertar_Linea_Pedido_Cliente($idPedido, $idMaterial, $cantidad, $observaciones)
    {

        global $bd;
        // COMPRUEBA SI YA EXISTE EN ESTE PEDIDO UNA LINEA CON EL MISMO MATERIAL Y OBSERVACIONES. SI ES ASÍ, INCREMENTA LA CANTIDAD.
        // EN CASO CONTRARIO, CREA LA LINEA DE PEDIDO
        $sqlPedidoExiste    = "SELECT * FROM PEDIDO_SALIDA_LINEA WHERE ID_PEDIDO_SALIDA=$idPedido AND ID_MATERIAL=$idMaterial";
        $resultPedidoExiste = $bd->ExecSQL($sqlPedidoExiste);
        if (!($rowPedidoExiste = $bd->SigReg($resultPedidoExiste))):
            // INSERTO LA LÍNEA DE PEDIDO
            $sql = "INSERT INTO PEDIDO_SALIDA_LINEA (ID_PEDIDO_SALIDA_LINEA,ID_PEDIDO_SALIDA,ID_MATERIAL,CANTIDAD,CANTIDAD_PENDIENTE_SERVIR) VALUES('', $idPedido, $idMaterial, $cantidad, $cantidad)";
            $bd->ExecSQL($sql);
        else:
            // INCREMENTO LA CANTIDAD DE LA LINEA DE PEDIDO EXISTENTE
            $sql = "UPDATE PEDIDO_SALIDA_LINEA SET CANTIDAD=CANTIDAD+$cantidad, CANTIDAD_PENDIENTE_SERVIR=CANTIDAD_PENDIENTE_SERVIR+$cantidad WHERE ID_PEDIDO_SALIDA_LINEA=$rowPedidoExiste->ID_PEDIDO_SALIDA_LINEA";
            $bd->ExecSQL($sql);
        endif;

    } // Fin Insertar_Linea_Pedido_Cliente

    function Insertar_Linea_Pedido_Gestor($idPedido, $idMaterial, $cantidad, $observaciones)
    {

        global $bd;
        // COMPRUEBA SI YA EXISTE EN ESTE PEDIDO UNA LINEA CON EL MISMO MATERIAL Y OBSERVACIONES. SI ES ASÍ, INCREMENTA LA CANTIDAD.
        // EN CASO CONTRARIO, CREA LA LINEA DE PEDIDO
        $sqlPedidoExiste    = "SELECT * FROM PEDIDO_SALIDA_LINEA WHERE ID_PEDIDO_SALIDA=$idPedido AND ID_MATERIAL=$idMaterial AND (ISNULL(ID_JOIN_ELEMENT) OR ID_JOIN_ELEMENT='')";
        $resultPedidoExiste = $bd->ExecSQL($sqlPedidoExiste);
        if (!($rowPedidoExiste = $bd->SigReg($resultPedidoExiste))):
            // INSERTO LA LÍNEA DE PEDIDO
            $sql = "INSERT INTO PEDIDO_SALIDA_LINEA (ID_PEDIDO_SALIDA_LINEA,ID_PEDIDO_SALIDA,ID_MATERIAL,CANTIDAD,CANTIDAD_PENDIENTE_SERVIR) VALUES('', $idPedido, $idMaterial, $cantidad, $cantidad)";
            $bd->ExecSQL($sql);
        else:
            // INCREMENTO LA CANTIDAD DE LA LINEA DE PEDIDO EXISTENTE
            $sql = "UPDATE PEDIDO_SALIDA_LINEA SET CANTIDAD=CANTIDAD+$cantidad, CANTIDAD_PENDIENTE_SERVIR=CANTIDAD_PENDIENTE_SERVIR+$cantidad WHERE ID_PEDIDO_SALIDA_LINEA=$rowPedidoExiste->ID_PEDIDO_SALIDA_LINEA";
            $bd->ExecSQL($sql);
        endif;

    } // Fin Insertar_Linea_Pedido_Cliente

    function Borrar_Linea_Pedido_Cliente($idPedido, $idLinea)
    {

        global $bd;

        // BORRO LA LÍNEA DE PEDIDO
        $sql = "DELETE FROM PEDIDO_SALIDA_LINEA WHERE ID_PEDIDO_SALIDA = $idPedido AND ID_PEDIDO_SALIDA_LINEA = $idLinea";
        $bd->ExecSQL($sql);

    } // Borrar_Linea_Pedido_Cliente


    function Obtener_Lineas_Pedido($idPedido)
    {
        global $bd;

        $sql    = "SELECT *
						FROM PEDIDO_SALIDA_LINEA PSL 
						INNER JOIN MATERIAL M ON M.ID_MATERIAL = PSL.ID_MATERIAL 
						WHERE PSL.ID_PEDIDO_SALIDA = $idPedido AND PSL.BAJA = 0 
						ORDER BY M.REFERENCIA";
        $result = $bd->ExecSQL($sql);

        if ($result == false):
            return "Err.";
        endif;

        if ($bd->NumRegs($result) > 0):
            return $result;
        else:
            return false;
        endif;
    } // Fin Obtener_Lineas_Pedido

    //DEVUELVE TRUE SI EL ARTICULO YA ESTA EN UNA LÍNEA DEL PEDIDO
    function Articulo_Esta_Incluido($idPedido, $idMaterial)
    {
        global $bd;

        $sql    = "SELECT * FROM PEDIDO_SALIDA_LINEA WHERE ID_PEDIDO_SALIDA = $idPedido AND ID_MATERIAL = $idMaterial";
        $result = $bd->ExecSQL($sql, "No");
        if ($result == false)
            return "Err.";
        if ($bd->NumRegs($result) == 0):
            return false;
        else:
            return true;
        endif;
    } // Articulo_Esta_Incluido

    //DEVUELVE TRUE SI EL ARTICULO YA ESTA EN UNA LÍNEA DEL PEDIDO
    function Articulo_Observaciones_Esta_Incluido($idPedido, $idMaterial, $observaciones)
    {
        global $bd;

        $sql    = "SELECT * FROM PEDIDO_SALIDA_LINEA WHERE ID_PEDIDO_SALIDA = $idPedido AND ID_MATERIAL = $idMaterial AND OBSERVACIONES='" . str_replace( "'", "''",(string) $observaciones) . "'";
        $result = $bd->ExecSQL($sql, "No");
        if ($result == false)
            return "Err.";
        if ($bd->NumRegs($result) == 0):
            return false;
        else:
            return true;
        endif;
    } // Articulo_Observaciones_Esta_Incluido

    function Borrar_Pedido_Cliente($idPedido)
    {

        global $bd;

        // BORRO LAS LÍNEAS DE PEDIDO
        $sql = "DELETE FROM PEDIDO_SALIDA_LINEA WHERE ID_PEDIDO_SALIDA = $idPedido";
        $bd->ExecSQL($sql);
        $sql = "DELETE FROM PEDIDO_SALIDA WHERE ID_PEDIDO_SALIDA = $idPedido";
        $bd->ExecSQL($sql);

    } // Borrar_Linea_Pedido_Cliente

    function Cantidad_Articulos_pedido($idPedido)
    {
        global $bd;

        $sql       = "SELECT sum(CANTIDAD) as NUM FROM PEDIDO_SALIDA_LINEA WHERE ID_PEDIDO_SALIDA=$idPedido";
        $resultado = $bd->ExecSQL($sql, "No");
        if ($resultado == false)
            return "Err.";
        $row = $bd->SigReg($resultado);

        return $row->NUM;

    } // Cantidad_Articulos_pedido

    function Cantidad_Articulos_Movimiento($idMovimiento)
    {
        global $bd;

        $sql       = "SELECT sum(CANTIDAD_PEDIDO) as NUM FROM MOVIMIENTO_SALIDA_LINEA WHERE ID_MOVIMIENTO_SALIDA = $idMovimiento";
        $resultado = $bd->ExecSQL($sql, "No");
        if ($resultado == false)
            return "Err.";
        $row = $bd->SigReg($resultado);

        return $row->NUM;

    } // Cantidad_Articulos_pedido

    function Obtener_Cantidad_Linea_Pedido($idPedido, $idLinea)
    {
        global $bd;

        $sqlOb       = "SELECT CANTIDAD FROM PEDIDO_SALIDA_LINEA WHERE ID_PEDIDO_SALIDA=$idPedido AND ID_PEDIDO_SALIDA_LINEA=$idLinea";
        $resultadoOb = $bd->ExecSQL($sqlOb, "No");
        if ($resultadoOb == false)
            return "Err.";
        $rowOb = $bd->SigReg($resultadoOb);

        return $rowOb->CANTIDAD;

    } // Cantidad_Articulos_pedido

    function Obtener_Cantidad_Articulo_Pedido($idPedido, $idArticulo)
    {
        global $bd;

        $sqlOb       = "SELECT sum(CANTIDAD) as NUM FROM PEDIDO_SALIDA_LINEA WHERE ID_PEDIDO_SALIDA=$idPedido AND ID_MATERIAL=$idArticulo";
        $resultadoOb = $bd->ExecSQL($sqlOb, "No");
        if ($resultadoOb == false)
            return "Err.";
        $rowOb = $bd->SigReg($resultadoOb);

        return $rowOb->NUM;

    } // Cantidad_Articulos_pedido

    function Obtener_Cantidad_Articulo_Movimiento($idMovimiento, $idArticulo)
    {
        global $bd;

        $sqlOb = "SELECT sum(CANTIDAD_PEDIDO) as NUM FROM MOVIMIENTO_SALIDA_LINEA WHERE ID_MOVIMIENTO_SALIDA=$idMovimiento AND ID_MATERIAL=$idArticulo";

        $resultadoOb = $bd->ExecSQL($sqlOb, "No");
        if ($resultadoOb == false)
            return "Err.";
        $rowOb = $bd->SigReg($resultadoOb);

        return $rowOb->NUM;

    } // Cantidad_Articulos_pedido

    function Calcular_Pedidos_Orden($idOrdenPreparacion)
    {

        global $bd;

        //QUERY
        $sql = "SELECT count(*) AS CANTIDAD";
        $sql .= " FROM PEDIDO_SALIDA";
        $sql .= " WHERE ID_ORDEN_PREPARACION='$idOrdenPreparacion'";

        $result = $bd->ExecSQL($sql, "No");
        if ($result == false)
            return 0;
        $row = $bd->SigReg($result);

        return $row->CANTIDAD;

    }

    function Calcular_Movimientos_Orden($idOrdenPreparacion)
    {

        global $bd;

        //QUERY
        $sql = "SELECT count(*) AS CANTIDAD";
        $sql .= " FROM MOVIMIENTO_SALIDA";
        $sql .= " WHERE ID_ORDEN_PREPARACION='$idOrdenPreparacion'";

        $result = $bd->ExecSQL($sql, "No");
        if ($result == false)
            return 0;
        $row = $bd->SigReg($result);

        return $row->CANTIDAD;

    }

    function Calcular_Pedidos_Finalizados_Orden($idOrdenPreparacion)
    {

        global $bd;

        //QUERY
        $sql = "SELECT count(*) AS CANTIDAD";
        $sql .= " FROM PEDIDO_SALIDA";
        $sql .= " WHERE ID_ORDEN_PREPARACION='$idOrdenPreparacion'";
        $sql .= " AND ESTADO IN ('Pendiente de Expedir','Expedido')";

        $result = $bd->ExecSQL($sql, "No");
        if ($result == false)
            return 0;
        $row = $bd->SigReg($result);

        return $row->CANTIDAD;

    }

    function Calcular_Movimientos_Finalizados_Orden($idOrdenPreparacion)
    {

        global $bd;

        //QUERY
        $sql = "SELECT count(*) AS CANTIDAD";
        $sql .= " FROM MOVIMIENTO_SALIDA";
        $sql .= " WHERE ID_ORDEN_PREPARACION='$idOrdenPreparacion'";
        $sql .= " AND ESTADO IN ('Pendiente de Expedir','Expedido')";

        $result = $bd->ExecSQL($sql, "No");
        if ($result == false)
            return 0;
        $row = $bd->SigReg($result);

        return $row->CANTIDAD;

    }

    function Calcular_Pedidos_Expedicion($idExpedicion)
    {

        global $bd;

        //QUERY
        $sql = "SELECT count(*) AS CANTIDAD";
        $sql .= " FROM MOVIMIENTO_SALIDA";
        $sql .= " WHERE ID_EXPEDICION='$idExpedicion'";

        $result = $bd->ExecSQL($sql, "No");
        if ($result == false)
            return 0;
        $row = $bd->SigReg($result);

        return $row->CANTIDAD;

    }

    //decrementa el stock_disponible e incrementa el stock_reservado
    function Reservar_Cantidad_Pdte_Servir($idPedido, $idLinea, $cantidad)
    {
        global $bd;

        $sqlRs    = "UPDATE PEDIDO_SALIDA_LINEA SET CANTIDAD_PENDIENTE_SERVIR = (CANTIDAD_PENDIENTE_SERVIR + $cantidad) WHERE ID_PEDIDO_SALIDA=$idPedido AND ID_PEDIDO_SALIDA_LINEA=$idLinea";
        $resultRs = $bd->ExecSQL($sqlRs, "No");
        if ($resultRs == false)
            return "Err.";

    } // FIN Reservar_Cantidad_Pdte_Servir

    //decrementa el stock_disponible e incrementa el stock_reservado
    function Devolver_A_Cantidad_Pdte_Servir($idPedido, $idLinea, $cantidad)
    {
        global $bd;

        $sqlRs    = "UPDATE PEDIDO_SALIDA_LINEA SET CANTIDAD_PENDIENTE_SERVIR = (CANTIDAD_PENDIENTE_SERVIR - $cantidad) WHERE ID_PEDIDO_SALIDA=$idPedido AND ID_PEDIDO_SALIDA_LINEA=$idLinea";
        $resultRs = $bd->ExecSQL($sqlRs, "No");
        if ($resultRs == false)
            return "Err.";

    } // FIN Reservar_Cantidad_Pdte_Servir

    function TieneCantidadPendienteServir($idPedido)
    {
        global $bd;

        $where = "ID_PEDIDO_SALIDA = $idPedido AND CANTIDAD_PENDIENTE_SERVIR > 0 AND BAJA = 0 AND INDICADOR_BORRADO IS NULL";
        if ($bd->NumRegsTabla("PEDIDO_SALIDA_LINEA", $where) > 0)
            return true;
        else    return false;

    }

    function ObtenerCantidadPreparada($idPedido, $idMat)
    {
        global $bd;

        $sql = "SELECT SUM(CANTIDAD) AS TOTAL_EXPEDIDO FROM MOVIMIENTO_SALIDA_LINEA MSL " .
            "INNER JOIN MOVIMIENTO_SALIDA MS ON(MS.ID_MOVIMIENTO_SALIDA = MSL.ID_MOVIMIENTO_SALIDA) " .
            "WHERE MS.ID_PEDIDO_SALIDA = $idPedido AND MSL.ID_MATERIAL = $idMat AND MS.ESTADO = 'Expedido'";

        $res = $bd->ExecSQL($sql);
        $row = $bd->SigReg($res);

        if (!$row->TOTAL_EXPEDIDO)
            $row->TOTAL_EXPEDIDO = 0;

        return $row->TOTAL_EXPEDIDO;
    }

    function ObtenerCantidadEnProceso($idPedido, $idMat)
    {
        global $bd;

        //$cantidadExpedida = $this->ObtenerCantidadPreparada($idPedido, $idMat);

        //$cantidadPedido = $this->ObtenerCantidadPedido($idPedido, $idMat);

        $sql = "SELECT DISTINCT MSL.ID_MOVIMIENTO_SALIDA, MSL.CANTIDAD_PEDIDO AS TOTAL_EN_PROCESO " .
            "FROM MOVIMIENTO_SALIDA_LINEA MSL " .
            "INNER JOIN MOVIMIENTO_SALIDA MS ON(MS.ID_MOVIMIENTO_SALIDA = MSL.ID_MOVIMIENTO_SALIDA) " .
            "WHERE MS.ID_PEDIDO_SALIDA = $idPedido AND MSL.ID_MATERIAL = $idMat " .
            "AND (MS.ESTADO = 'En Preparacion' OR MS.ESTADO = 'Pendiente de Expedir')";

        $res = $bd->ExecSQL($sql, "No");
        if (!res):
            $totalEnProceso = 0;
        else:
            $totalEnProceso = 0;
            while ($row = $bd->SigReg($res)):
                if (!$row->TOTAL_EN_PROCESO)
                    $row->TOTAL_EN_PROCESO = 0;
                $totalEnProceso += $row->TOTAL_EN_PROCESO;
            endwhile;
        endif;

        return $totalEnProceso;
    }

    function ObtenerCantidadExpedida($idPedido, $idMat)
    {
        global $bd;

        $sql = "SELECT SUM(CANTIDAD) AS TOTAL_EXPEDIDO
						FROM MOVIMIENTO_SALIDA_LINEA MSL 
						INNER JOIN MOVIMIENTO_SALIDA MS ON(MS.ID_MOVIMIENTO_SALIDA = MSL.ID_MOVIMIENTO_SALIDA) 
						INNER JOIN ORDEN_PREPARACION AS O ON (O.ID_ORDEN_PREPARACION = MS.ID_ORDEN_PREPARACION) 
						WHERE MS.ID_PEDIDO_SALIDA = $idPedido AND MSL.ID_MATERIAL = $idMat 
						AND MS.ESTADO = 'Expedido' 
						AND (O.ESTADO = 'Contabilizado' OR O.ESTADO = 'Expedido')";

        $res = $bd->ExecSQL($sql);
        $row = $bd->SigReg($res);

        if (!$row->TOTAL_EXPEDIDO)
            $row->TOTAL_EXPEDIDO = 0;

        return $row->TOTAL_EXPEDIDO;
    }

    function ObtenerCantidadPdtePrepararSinStock($idPedido, $idMat)
    {
        global $bd;

        $cantidadPedido    = $this->Obtener_Cantidad_Articulo_Pedido($idPedido, $idMat);
        $cantidadPreparada = $this->ObtenerCantidadPreparada($idPedido, $idMat);

        $rowMat          = $bd->VerReg("MATERIAL", "ID_MATERIAL", $idMat);
        $stockDisponible = $rowMat->STOCK_DISPONIBLE;

        //CALCULO LA CANTIDAD PDTE DE PREPARAR
        $cantidadPdtePreparar = $cantidadPedido - $cantidadPreparada;

        //CALCULO LA CANTIDAD PENDIENTE SIN STOCK, PUEDE SER NEGATIVA, ENTONCES CERO
        $cantidadPdtePrepararSinStock = $cantidadPdtePreparar - $stockDisponible;

        if ($cantidadPdtePrepararSinStock >= 0):
            return $cantidadPdtePrepararSinStock;
        else:
            return 0;
        endif;
    }

    function ObtenerCantidadPdtePrepararConStock($idPedido, $idMat)
    {
        global $bd;

        $cantidadPedido    = $this->Obtener_Cantidad_Articulo_Pedido($idPedido, $idMat);
        $cantidadPreparada = $this->ObtenerCantidadPreparada($idPedido, $idMat);

        $rowMat          = $bd->VerReg("MATERIAL", "ID_MATERIAL", $idMat);
        $stockDisponible = $rowMat->STOCK_DISPONIBLE;

        //CALCULO LA CANTIDAD PDTE DE PREPARAR
        $cantidadPdtePreparar = $cantidadPedido - $cantidadPreparada;

        //CALCULO LA CANTIDAD PENDIENTE CON STOCK, PUEDE SER MENOR AL STOCK, ENTONCES CANTIDAD A PDTE
        if ($stockDisponible < $cantidadPdtePreparar):
            return $stockDisponible;
        else:
            return $cantidadPdtePreparar;
        endif;
    }

    function TieneMovimientosEnPreparacion($idPedido)
    {
        global $bd;

        $where = "ID_PEDIDO_SALIDA = '$idPedido' AND ESTADO = 'En Preparacion' AND BAJA = 0 AND LINEA_ANULADA = 0";

        if ($bd->NumRegsTabla("MOVIMIENTO_SALIDA_LINEA", $where) > 0)
            return true;
        else    return false;
    }

    function Obtener_Join_Element($idJoinElement)
    {
        global $bd;

        $sql    = "SELECT * FROM JOIN_ELEMENT WHERE ID_JOIN_ELEMENT = $idJoinElement";
        $result = $bd->ExecSQL($sql, "No");
        if ($result == false)
            return "Err.";
        if ($bd->NumRegs($result) > 0):
            $row = $bd->SigReg($result);

            return $row;
        else:
            return false;
        endif;

    }

    function Obtener_Lineas_Join_Element($idJoinElement)
    {
        global $bd;

        $sql = "SELECT * FROM JOIN_ELEMENT_LINEA JEL " .
            "INNER JOIN MATERIAL M ON(M.ID_MATERIAL = JEL.ID_MATERIAL) " .
            "WHERE ID_JOIN_ELEMENT = $idJoinElement " .
            "ORDER BY M.REFERENCIA";

        $result = $bd->ExecSQL($sql, "No");

        if ($result == false)
            return "Err.";

        if ($bd->NumRegs($result) > 0):
            return $result;
        else:
            return false;
        endif;

    }

    function Existe_Nombre_Join_Element_En_Pedido($idPedido, $txNombre, $idJoinElement = '')
    {
        global $bd;

        if ($idJoinElement != "")
            $restJoinElement = "AND ID_JOIN_ELEMENT != $idJoinElement";

        $rest = "ID_PEDIDO_SALIDA = $idPedido AND NOMBRE = '$txNombre' $restJoinElement";

        if ($bd->NumRegsTabla("JOIN_ELEMENT", $rest) > 0)
            return true;
        else    return false;

    }

    function Insertar_Join_Element($idPedido, $nombre, $cantidad)
    {
        global $bd;

        $sqlInsert = "INSERT INTO JOIN_ELEMENT SET " .
            "NOMBRE = '$nombre', " .
            "ID_PEDIDO_SALIDA = $idPedido, " .
            "CANTIDAD = $cantidad";

        $bd->ExecSQL($sqlInsert);

        return $bd->IdAsignado();

    }

    function Modificar_Join_Element($idJoinElement, $nombre, $cantidad)
    {
        global $bd;
        global $html;
        global $mat;
        global $administrador;
        global $auxiliar;

        $Pagina_Error = "join_element_error.php";

        // OBTENEMOS LA CANTIDAD ANTIGUA, SI SE MODIFICA AFECTARÁ A LINEAS DE PEDIDO
        // Y PODRÍA AFECTAR TB A LÍNEAS DE MOVIMIENTO
        $rowJoinElement = $bd->VerReg("JOIN_ELEMENT", "ID_JOIN_ELEMENT", $idJoinElement);

        $idPedido = $rowJoinElement->ID_PEDIDO_SALIDA;

        $cantidadAntigua = $rowJoinElement->CANTIDAD;

        if ($cantidadAntigua != $cantidad):
            // ACCIONES LINEAS DE PEDIDO Y LINEAS DE MOVIMIENTO
            if ($cantidad > $cantidadAntigua): // SI INCREMENTAMOS LA CANTIDAD
                // OBTENEMOS LAS LINEAS DE PEDIDO ASOCIADAS AL JOIN ELEMENT
                $lineasPedidoJE = $this->Obtener_Lineas_Pedido_Join_Element($idJoinElement, $idPedido);
                if ($lineasPedidoJE):
                    foreach ($lineasPedidoJE as $idLin):
                        // OBTENEMOS LA LINEA DEL PEDIDO
                        $rowPedLin = $bd->VerReg("PEDIDO_SALIDA_LINEA", "ID_PEDIDO_SALIDA_LINEA", $idLin);

                        // OBTENEMOS LA CANTIDAD DE LA QUE SE COMPONE EL JE PARA ESTE MATERIAL
                        $restJE   = "ID_JOIN_ELEMENT = $idJoinElement AND ID_MATERIAL = $rowPedLin->ID_MATERIAL";
                        $rowJELin = $bd->VerRegRest("JOIN_ELEMENT_LINEA", $restJE);

                        $incremento = $cantidad - $cantidadAntigua;

                        $incrementoMaterial = $rowJELin->CANTIDAD * $incremento;

                        // ACTUALIZAMOS EL STOCK PDTE. DE SERVIR EN TABLA MATERIAL
                        $mat->Reservar_En_Stock_Pdte_Servir($rowPedLin->ID_MATERIAL, $incrementoMaterial);

                        // ACTUALIZAMOS LA LINEA DEL PEDIDO
                        $sqlUpdate = "UPDATE PEDIDO_SALIDA_LINEA SET " .
                            "CANTIDAD = CANTIDAD + $incrementoMaterial, " .
                            "CANTIDAD_PENDIENTE_SERVIR = CANTIDAD_PENDIENTE_SERVIR + $incrementoMaterial " .
                            "WHERE ID_PEDIDO_SALIDA_LINEA = $idLin";
                        $bd->ExecSQL($sqlUpdate);

                    endforeach;
                endif;
            else: // SI DECREMENTAMOS LA CANTIDAD
                // OBTENEMOS EL PEDIDO
                $rowPed = $bd->VerReg("PEDIDO_SALIDA", "ID_PEDIDO_SALIDA", $idPedido);
                if ($rowPed->ESTADO == "Grabado"):
                    $strError = "No se puede decrementar la cantidad del JE por que la cantidad a decrementar supera a la cantidad en el pedido.Lista de Materiales:<br /><br />";
                else:
                    $strError = "No se puede decrementar la cantidad del JE por que la cantidad a decrementar supera a la pendiente de servir; El pedido ya tiene movimientos de salida asociados, deberá modificar los movimientos.Lista de Materiales:";
                endif;

                // COMPROBAMOS QUE LA CANTIDAD A DECREMENTAR NO SUPERE A LA PDTE. DE SERVIR
                // OBTENEMOS LAS LINEAS DE PEDIDO ASOCIADAS AL JOIN ELEMENT
                $lineasPedidoJE = $this->Obtener_Lineas_Pedido_Join_Element($idJoinElement, $idPedido);
                if ($lineasPedidoJE):
                    $hayError = false;
                    foreach ($lineasPedidoJE as $idLin): // COMPROBACIONES
                        // OBTENEMOS LA LINEA DEL PEDIDO
                        $rowPedLin = $bd->VerReg("PEDIDO_SALIDA_LINEA", "ID_PEDIDO_SALIDA_LINEA", $idLin);

                        // OBTENEMOS LA CANTIDAD DE LA QUE SE COMPONE EL JE PARA ESTE MATERIAL
                        $restJE   = "ID_JOIN_ELEMENT = $idJoinElement AND ID_MATERIAL = $rowPedLin->ID_MATERIAL";
                        $rowJELin = $bd->VerRegRest("JOIN_ELEMENT_LINEA", $restJE);

                        $decremento = $cantidadAntigua - $cantidad;

                        $decrementoMaterial = $rowJELin->CANTIDAD * $decremento;

                        if ($decrementoMaterial > $rowPedLin->CANTIDAD_PENDIENTE_SERVIR):
                            $rowMat   = $bd->VerReg("MATERIAL", "ID_MATERIAL", $rowPedLin->ID_MATERIAL);
                            $hayError = true;
                            $strError .= "$rowMat->REFERENCIA - " . $auxiliar->traduce("Cant. Pdte. Servir", $administrador->ID_IDIOMA) . ": $rowPedLin->CANTIDAD_PENDIENTE_SERVIR - " . $auxiliar->traduce("Cant. a decrementar", $administrador->ID_IDIOMA) . ": $decrementoMaterial<br />";
                        endif;
                    endforeach;

                    if ($hayError)
                        $html->PagError("DecrementoJESuperaCantPdteServir");

                    foreach ($lineasPedidoJE as $idLin):    // ACCIONES
                        // OBTENEMOS LA LINEA DEL PEDIDO
                        $rowPedLin = $bd->VerReg("PEDIDO_SALIDA_LINEA", "ID_PEDIDO_SALIDA_LINEA", $idLin);

                        // OBTENEMOS LA CANTIDAD DE LA QUE SE COMPONE EL JE PARA ESTE MATERIAL
                        $restJE   = "ID_JOIN_ELEMENT = $idJoinElement AND ID_MATERIAL = $rowPedLin->ID_MATERIAL";
                        $rowJELin = $bd->VerRegRest("JOIN_ELEMENT_LINEA", $restJE);

                        $decremento = $cantidadAntigua - $cantidad;

                        $decrementoMaterial = $rowJELin->CANTIDAD * $decremento;

                        // ACTUALIZAMOS EL STOCK PDTE. DE SERVIR EN TABLA MATERIAL
                        $mat->Reservar_En_Stock_Pdte_Servir($rowPedLin->ID_MATERIAL, (-1) * $decrementoMaterial);

                        // ACTUALIZAMOS LA LINEA DEL PEDIDO
                        $sqlUpdate = "UPDATE PEDIDO_SALIDA_LINEA SET " .
                            "CANTIDAD = CANTIDAD - $decrementoMaterial, " .
                            "CANTIDAD_PENDIENTE_SERVIR = CANTIDAD_PENDIENTE_SERVIR + $decrementoMaterial " .
                            "WHERE ID_PEDIDO_SALIDA_LINEA = $idLin";
                        $bd->ExecSQL($sqlUpdate);
                    endforeach;
                endif;
            endif;
        endif;

        $sqlUpdate = "UPDATE JOIN_ELEMENT SET " .
            "NOMBRE = '$nombre', " .
            "CANTIDAD = $cantidad " .
            "WHERE ID_JOIN_ELEMENT = $idJoinElement";

        $bd->ExecSQL($sqlUpdate);

    }

    function Obtener_Lineas_Pedido_Join_Element($idJoinElement, $idPedido)
    {
        // DEVUELVE UN ARRAY CON LOS IDS DE LAS LINEAS DEL PEDIDO ASOCIADAS AL JOIN ELEMENT
        global $bd;

        $sql = "SELECT ID_PEDIDO_SALIDA_LINEA " .
            "FROM PEDIDO_SALIDA_LINEA " .
            "WHERE ID_PEDIDO_SALIDA = $idPedido AND ID_JOIN_ELEMENT = $idJoinElement " .
            "ORDER BY ID_PEDIDO_SALIDA_LINEA";

        $res = $bd->ExecSQL($sql, "No");

        if (!$res):
            return false;
        else:
            while ($row = $bd->SigReg($res)):
                $arrLineas[] = $row->ID_PEDIDO_SALIDA_LINEA;
            endwhile;

            return $arrLineas;
        endif;

    }

    function Insertar_Linea_Pedido_Join_Element($idPedido, $idJoinElement, $idMaterial, $cantidad, $observaciones)
    {

        global $bd;
        // COMPRUEBA SI YA EXISTE EN ESTE PEDIDO UNA LINEA CON EL MISMO MATERIAL Y OBSERVACIONES. SI ES ASÍ, INCREMENTA LA CANTIDAD.
        // EN CASO CONTRARIO, CREA LA LINEA DE PEDIDO
        $sqlPedidoExiste    = "SELECT * FROM PEDIDO_SALIDA_LINEA WHERE ID_PEDIDO_SALIDA=$idPedido AND ID_MATERIAL=$idMaterial AND ID_JOIN_ELEMENT = $idJoinElement";
        $resultPedidoExiste = $bd->ExecSQL($sqlPedidoExiste);
        if (!($rowPedidoExiste = $bd->SigReg($resultPedidoExiste))):
            // INSERTO LA LÍNEA DE PEDIDO
            $sql = "INSERT INTO PEDIDO_SALIDA_LINEA (ID_PEDIDO_SALIDA_LINEA,ID_PEDIDO_SALIDA,ID_JOIN_ELEMENT,ID_MATERIAL,CANTIDAD,CANTIDAD_PENDIENTE_SERVIR) VALUES('', $idPedido, $idJoinElement, $idMaterial, $cantidad, $cantidad)";
            $bd->ExecSQL($sql);

            return $bd->IdAsignado();

        else:
            // INCREMENTO LA CANTIDAD DE LA LINEA DE PEDIDO EXISTENTE
            $sql = "UPDATE PEDIDO_SALIDA_LINEA SET CANTIDAD=CANTIDAD+$cantidad, CANTIDAD_PENDIENTE_SERVIR=CANTIDAD_PENDIENTE_SERVIR+$cantidad WHERE ID_PEDIDO_SALIDA_LINEA=$rowPedidoExiste->ID_PEDIDO_SALIDA_LINEA";
            $bd->ExecSQL($sql);

        endif;

    } // Fin Insertar_Linea_Pedido_Cliente

    function Reservar_Cantidad_Pdte_Servir_Join_Element($idPedido, $idLinea, $cantidad)
    {
        global $bd;

        $sqlRs    = "UPDATE PEDIDO_SALIDA_LINEA SET " .
            "CANTIDAD = (CANTIDAD + $cantidad), " .
            "CANTIDAD_PENDIENTE_SERVIR = (CANTIDAD_PENDIENTE_SERVIR + $cantidad) " .
            "WHERE ID_PEDIDO_SALIDA=$idPedido AND ID_PEDIDO_SALIDA_LINEA=$idLinea";
        $resultRs = $bd->ExecSQL($sqlRs, "No");
        if ($resultRs == false)
            return "Err.";

    } // FIN Reservar_Cantidad_Pdte_Servir

    function Devolver_A_Cantidad_Pdte_Servir_Join_Element($idPedido, $idLinea, $cantidad)
    {
        global $bd;

        $sqlRs    = "UPDATE PEDIDO_SALIDA_LINEA SET " .
            "CANTIDAD = (CANTIDAD - $cantidad), " .
            "CANTIDAD_PENDIENTE_SERVIR = (CANTIDAD_PENDIENTE_SERVIR - $cantidad) " .
            "WHERE ID_PEDIDO_SALIDA=$idPedido AND ID_PEDIDO_SALIDA_LINEA=$idLinea";
        $resultRs = $bd->ExecSQL($sqlRs, "No");
        if ($resultRs == false)
            return "Err.";

    }

    function Obtener_Cantidad_Linea_Join_Element($idJoinElement, $idLinea)
    {
        global $bd;

        $sqlOb       = "SELECT CANTIDAD FROM JOIN_ELEMENT_LINEA " .
            "WHERE ID_JOIN_ELEMENT = $idJoinElement " .
            "AND ID_JOIN_ELEMENT_LINEA = $idLinea";
        $resultadoOb = $bd->ExecSQL($sqlOb, "No");
        if ($resultadoOb == false)
            return "Err.";
        $rowOb = $bd->SigReg($resultadoOb);

        return $rowOb->CANTIDAD;
    }

    function Borrar_Linea_Join_Element($idJoinElement, $idLinea)
    {
        global $bd;

        $rowJE    = $bd->VerReg("JOIN_ELEMENT", "ID_JOIN_ELEMENT", $idJoinElement);
        $rowLinJE = $bd->VerReg("JOIN_ELEMENT_LINEA", "ID_JOIN_ELEMENT_LINEA", $idLinea);

        // BORRAMOS LA LÍNEA DEL PEDIDO ASOCIADO
        $sql = "DELETE FROM PEDIDO_SALIDA_LINEA " .
            "WHERE ID_PEDIDO_SALIDA = $rowJE->ID_PEDIDO_SALIDA AND " .
            "ID_PEDIDO_SALIDA_LINEA = $rowLinJE->ID_PEDIDO_SALIDA_LINEA";

        $bd->ExecSQL($sql);

        // BORRAMOS LA LÍNEA DEL JOIN ELEMENT
        $sql = "DELETE FROM JOIN_ELEMENT_LINEA " .
            "WHERE ID_JOIN_ELEMENT_LINEA = $idLinea";

        $bd->ExecSQL($sql);

    }

    function Eliminar_Join_Element($idJoinElement)
    {
        global $bd;
        global $mat;

        $rowJoinElement = $bd->VerReg("JOIN_ELEMENT", "ID_JOIN_ELEMENT", $idJoinElement);

        $cantidadPedido = $rowJoinElement->CANTIDAD;

        $lineasPedidoJE = $this->Obtener_Lineas_Pedido_Join_Element($idJoinElement, $rowJoinElement->ID_PEDIDO_SALIDA);

        if ($lineasPedidoJE):
            foreach ($lineasPedidoJE as $idLin):    // ACCIONES
                // OBTENEMOS LA LINEA DEL PEDIDO
                $rowPedLin = $bd->VerReg("PEDIDO_SALIDA_LINEA", "ID_PEDIDO_SALIDA_LINEA", $idLin);

                // OBTENEMOS LA CANTIDAD DE LA QUE SE COMPONE EL JE PARA ESTE MATERIAL
                $restJE   = "ID_JOIN_ELEMENT = $idJoinElement AND ID_MATERIAL = $rowPedLin->ID_MATERIAL";
                $rowJELin = $bd->VerRegRest("JOIN_ELEMENT_LINEA", $restJE);


                $decrementoMaterial = $rowJELin->CANTIDAD * $cantidadPedido;

                // ACTUALIZAMOS EL STOCK PDTE. DE SERVIR EN TABLA MATERIAL
                $mat->Reservar_En_Stock_Pdte_Servir($rowPedLin->ID_MATERIAL, (-1) * $decrementoMaterial);

                // ELIMINAMOS LA LINEA DEL PEDIDO
                $sqlUpdate = "DELETE FROM PEDIDO_SALIDA_LINEA " .
                    "WHERE ID_PEDIDO_SALIDA_LINEA = $idLin";

                $bd->ExecSQL($sqlUpdate);
            endforeach;
        endif;

        // ELIMINAMOS DE JOIN_ELEMENT
        $sqlDelete = "DELETE FROM JOIN_ELEMENT WHERE ID_JOIN_ELEMENT = $idJoinElement";
        $bd->ExecSQL($sqlDelete);

        // ELIMINAMOS DE JOIN_ELEMENT_LINEA
        $sqlDelete = "DELETE FROM JOIN_ELEMENT_LINEA WHERE ID_JOIN_ELEMENT = $idJoinElement";
        $bd->ExecSQL($sqlDelete);

    }

    function Obtener_Lineas_Pedido_Sin_Join_Elements($idPedido)
    {
        global $bd;

        $sql    = "SELECT * FROM PEDIDO_SALIDA_LINEA, MATERIAL " .
            "WHERE ID_PEDIDO_SALIDA=$idPedido " .
            "AND PEDIDO_SALIDA_LINEA.ID_MATERIAL = MATERIAL.ID_MATERIAL " .
            "AND ISNULL(PEDIDO_SALIDA_LINEA.ID_JOIN_ELEMENT) " .
            "ORDER BY MATERIAL.REFERENCIA";
        $result = $bd->ExecSQL($sql, "No");
        if ($result == false)
            return "Err.";
        if ($bd->NumRegs($result) > 0):
            return $result;
        else:
            return false;
        endif;
    }

    function Obtener_Join_Elements_Pedido($idPedido)
    {
        // DEVUELVE UN ARRAY CON LOS JOIN ELEMENTS ASOCIADOS AL PEDIDO
        global $bd;

        $sql = "SELECT * FROM JOIN_ELEMENT " .
            "WHERE ID_PEDIDO_SALIDA = $idPedido " .
            "ORDER BY NOMBRE";

        $res = $bd->ExecSQL($sql, "No");

        if (!$res):
            return false;
        else:
            $i = 0;
            while ($row = $bd->SigReg($res)):
                $arrJE[$i]["ID_JOIN_ELEMENT"] = $row->ID_JOIN_ELEMENT;
                $arrJE[$i]["NOMBRE"]          = $row->NOMBRE;
                $arrJE[$i]["CANTIDAD"]        = $row->CANTIDAD;
                $i++;
            endwhile;

            return $arrJE;
        endif;
    }

    function Obtener_Cantidad_Pendiente_Preparar($idJoinElement)
    {
        global $bd;

        // OBTENEMOS LA CANTIDAD DEL JOIN ELEMENT ASOCIADA A MOVIMIENTOS
        $sqlCantAsignadaMovs = "SELECT SUM(CANTIDAD) AS TOTAL " .
            "FROM JOIN_ELEMENT_MOVIMIENTO_SALIDA " .
            "WHERE ID_JOIN_ELEMENT = $idJoinElement";

        $resCantAsignadaMovs = $bd->ExecSQL($sqlCantAsignadaMovs, "No");

        if (!$resCantAsignadaMovs):
            $cantAsignadaMovs = 0;
        else:
            $rowCantAsignadaMovs = $bd->SigReg($resCantAsignadaMovs);
            $cantAsignadaMovs    = $rowCantAsignadaMovs->TOTAL;
        endif;

        $rowJE = $this->Obtener_Join_Element($idJoinElement);

        $cantPendiente = $rowJE->CANTIDAD - $cantAsignadaMovs;

        return $cantPendiente;

    }

    function OrdenTieneJoinElements($idOrden)
    {
        // DEVUELVE TRUE O FALSE SI LA ORDEN TIENE O NO MOVIMIENTOS CON JOIN-ELEMENTS ASOCIADOS
        global $bd;

        $sql = "SELECT COUNT(*) AS NUM_REGS " .
            "FROM JOIN_ELEMENT_MOVIMIENTO_SALIDA JEMS " .
            "INNER JOIN MOVIMIENTO_SALIDA MS ON(MS.ID_MOVIMIENTO_SALIDA = JEMS.ID_MOVIMIENTO_SALIDA) " .
            "WHERE MS.ID_ORDEN_PREPARACION = $idOrden";

        $res = $bd->ExecSQL($sql);

        $row = $bd->SigReg($res);

        if ($row->NUM_REGS == 0)
            return false;
        else    return true;

    }

    function Calcular_Lineas_Orden($idOrdenPreparacion)
    {

        global $bd;

        //QUERY
        $sql = "SELECT count(*) AS CANTIDAD";
        $sql .= " FROM MOVIMIENTO_SALIDA_LINEA AS MSL";
        $sql .= " INNER JOIN MOVIMIENTO_SALIDA AS MS";
        $sql .= " ON MSL.ID_MOVIMIENTO_SALIDA = MS.ID_MOVIMIENTO_SALIDA";
        $sql .= " WHERE MS.ID_ORDEN_PREPARACION='$idOrdenPreparacion'";

        $result = $bd->ExecSQL($sql, "No");
        if ($result == false)
            return 0;
        $row = $bd->SigReg($result);

        return $row->CANTIDAD;

    }

    function Calcular_Lineas_Finalizadas_Orden($idOrdenPreparacion)
    {

        global $bd;

        //QUERY
        $sql = "SELECT count(*) AS CANTIDAD";
        $sql .= " FROM MOVIMIENTO_SALIDA_LINEA AS MSL";
        $sql .= " INNER JOIN MOVIMIENTO_SALIDA AS MS";
        $sql .= " ON MSL.ID_MOVIMIENTO_SALIDA = MS.ID_MOVIMIENTO_SALIDA";
        $sql .= " WHERE MS.ID_ORDEN_PREPARACION='$idOrdenPreparacion'";
        $sql .= " AND MS.ESTADO IN ('Pendiente de Expedir','Expedido')";

        $result = $bd->ExecSQL($sql, "No");
        if ($result == false)
            return 0;
        $row = $bd->SigReg($result);

        return $row->CANTIDAD;
    }

    function PedidoAEnEntrega($idPedido)
    {
        global $bd;

        $sql = "UPDATE PEDIDO_SALIDA SET ESTADO = 'En Entrega' WHERE ID_PEDIDO_SALIDA = $idPedido";
        $bd->ExecSQL($sql);
    }

    function CrearMovimientoSalida($idPedido, $idOrden, $fecha)
    {
        global $bd;
        global $administrador;
        global $html;

        //BUSCO EL PEDIDO DE SALIDA
        $rowPed = $bd->VerReg("PEDIDO_SALIDA", "ID_PEDIDO_SALIDA", $idPedido);

        //VARIABLE PARA GRABAR DATOS ESPECIALES DE CADA PEDIDO
        $grabarDatosEspecialesPedido = "";

        //BUSCO EL ESTADO
        if ($rowPed->TIPO_PEDIDO == 'Venta'):
            $tipo = 'Venta';
        elseif (($rowPed->TIPO_PEDIDO == 'Traslado') || ($rowPed->TIPO_PEDIDO == 'Pendientes de Ordenes Trabajo')):
            $tipo = 'TraspasoEntreAlmacenesNoEstropeado';
        elseif ($rowPed->TIPO_PEDIDO == 'Componentes a Proveedor'):
            $tipo = 'ComponentesAProveedor';
        elseif ($rowPed->TIPO_PEDIDO == 'Devolución a Proveedor'):
            $tipo                        = 'DevolucionNoEstropeadoAProveedor';
            $grabarDatosEspecialesPedido = ", ID_PROVEEDOR = $rowPed->ID_PROVEEDOR";
        elseif ($rowPed->TIPO_PEDIDO == 'Rechazos y Anulaciones a Proveedor'):
            $tipo = 'MaterialRechazadoAnuladoEnEntradasAProveedor';
        elseif ($rowPed->TIPO_PEDIDO == 'Intra Centro Fisico'):
            $tipo = 'IntraCentroFisico';
        elseif ($rowPed->TIPO_PEDIDO == 'Interno Gama'):
            $tipo = 'InternoGama';
        elseif ($rowPed->TIPO_PEDIDO == 'Traslados OM Construccion'):
            $tipo = 'TrasladoOMConstruccion';
        elseif ($rowPed->TIPO_PEDIDO == 'Preparacion AGM'):
            $tipo = 'PreparacionAGM';
        else:
            $html->PagError("ErrorTipoPedido");
        endif;

        //BUSCO SI HAY UN MOVIMIENTO DE SALIDA QUE ME VALGA
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowMovimientoSalida              = $bd->VerRegRest("MOVIMIENTO_SALIDA", "ID_PEDIDO_SALIDA = $idPedido AND ID_ORDEN_PREPARACION = $idOrden AND TIPO_MOVIMIENTO = '" . $tipo . "' AND BAJA = 0", "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);
        if ($rowMovimientoSalida == false): //NO EXISTE UNO QUE ME VALGA, CREO EL MOVIMIENTO DE SALIDA
            $sql = "INSERT INTO MOVIMIENTO_SALIDA SET
                    ID_PEDIDO_SALIDA = $idPedido
                    , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                    , ID_ORDEN_PREPARACION = $idOrden
                    , TIPO_MOVIMIENTO = '" . $tipo . "'
                    , FECHA = '$fecha' $grabarDatosEspecialesPedido";
            $bd->ExecSQL($sql);
            $idMovimientoSalida = $bd->IdAsignado();
        else:   //EXISTE UNO QUE ME VALGA, ACTUALIZO LOS DATOS DEL MOVIMIENTO DE SALIDA
            $sql = "UPDATE MOVIMIENTO_SALIDA SET
                    ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                    , FECHA_PREPARACION = '0000-00-00 00:00:00'
                    WHERE ID_MOVIMIENTO_SALIDA = $rowMovimientoSalida->ID_MOVIMIENTO_SALIDA";
            $bd->ExecSQL($sql);
            $idMovimientoSalida = $rowMovimientoSalida->ID_MOVIMIENTO_SALIDA;
        endif; //EXISTE O NO UN MOVIMIENTO DE SALIDA QUE ME VALGA

        return $idMovimientoSalida;
    }

    function CrearMovimientoSalidaLinea($idMovimientoSalida, $idPedidoSalidaLinea, $arrDesubicacion)
    {
        global $bd;
        global $html;
        global $administrador;

        //BUSCO LA LINEA DEL PEDIDO DE SALIDA
        $sqlLinea    = "SELECT *
                        FROM PEDIDO_SALIDA_LINEA PSL
                        WHERE PSL.ID_PEDIDO_SALIDA_LINEA = $idPedidoSalidaLinea FOR UPDATE";
        $resultLinea = $bd->ExecSQL($sqlLinea);
        $rowLinea    = $bd->SigReg($resultLinea);

        //BUSCO EL PEDIDO DE SALIDA
        $rowPedidoSalida = $bd->VerReg("PEDIDO_SALIDA", "ID_PEDIDO_SALIDA", $rowLinea->ID_PEDIDO_SALIDA);

        //BUSCO LA UBICACION DESTINO DEL MOVIMIENTO LINEA
        $idUbicacionDestino = NULL;
        if (($rowPedidoSalida->TIPO_PEDIDO == 'Traslado') || ($rowPedidoSalida->TIPO_PEDIDO == 'Intra Centro Fisico') || ($rowPedidoSalida->TIPO_PEDIDO == 'Interno Gama') || ($rowPedidoSalida->TIPO_PEDIDO == 'Traslados OM Construccion') || ($rowPedidoSalida->TIPO_PEDIDO == 'Pendientes de Ordenes Trabajo')):

            //BUSCO EL ALMACEN DEL ALMACEN DESTINO
            $rowAlmDestino = $bd->VerReg("ALMACEN", "ID_ALMACEN", $rowLinea->ID_ALMACEN_DESTINO, "No");
            $html->PagErrorCondicionado($rowAlmDestino, "==", false, "AlmacenNoDefinido");

            $idAlmacenDestino = $rowAlmDestino->ID_ALMACEN;

            //BUSCO LA UBICACION DE EM DEL ALMACEN DESTINO
            $rowUbiDestino = $bd->VerRegRest("UBICACION", "ID_ALMACEN = $rowAlmDestino->ID_ALMACEN AND TIPO_UBICACION = 'Entrada'", "No");
            $html->PagErrorCondicionado($rowUbiDestino, "==", false, "AlmacenDestinoSinUbicacionEM");

            $idUbicacionDestino = $rowUbiDestino->ID_UBICACION;

        elseif ($rowPedidoSalida->TIPO_PEDIDO == 'Preparacion AGM'):

            //BUSCO EL ALMACEN DEL ALMACEN DESTINO
            $rowAlmDestino = $bd->VerReg("ALMACEN", "ID_ALMACEN", $rowLinea->ID_ALMACEN_DESTINO, "No");
            $html->PagErrorCondicionado($rowAlmDestino, "==", false, "AlmacenNoDefinido");

            $idAlmacenDestino = $rowAlmDestino->ID_ALMACEN;

            //BUSCO LA UBICACION DE EM DEL ALMACEN DESTINO
            $rowUbiDestino = $bd->VerRegRest("UBICACION", "ID_ALMACEN = $rowAlmDestino->ID_ALMACEN AND TIPO_UBICACION = 'Componentes AGM'", "No");
            $html->PagErrorCondicionado($rowUbiDestino, "==", false, "AlmacenDestinoSinUbicacionAGMComponentes");

            $idUbicacionDestino = $rowUbiDestino->ID_UBICACION;

        elseif ($rowPedidoSalida->TIPO_PEDIDO == 'Componentes a Proveedor'):

            //BUSCO EL ALMACEN DEL PROVEEDOR
            $rowAlmProveedor = $bd->VerReg("ALMACEN", "ID_PROVEEDOR", $rowPedidoSalida->ID_PROVEEDOR, "No");
            $html->PagErrorCondicionado($rowAlmProveedor, "==", false, "ProveedorSinAlmacen");

            $idAlmacenDestino = $rowAlmProveedor->ID_ALMACEN;

            //BUSCO LA UBICACION DEL PROVEEDOR
            $rowUbiProveedor = $bd->VerRegRest("UBICACION", "ID_ALMACEN = $rowAlmProveedor->ID_ALMACEN", "No");
            $html->PagErrorCondicionado($rowUbiProveedor, "==", false, "ProveedorSinUbicacion");

            $idUbicacionDestino = $rowUbiProveedor->ID_UBICACION;

        endif;
        //FIN BUSCO LA UBICACION DESTINO DEL MOVIMIENTO LINEA

        //BUSCO SI EXISTE UNA LINEA DE MOVIMIENTO SIMILAR PARA INCREMENTAR LA CANTIDAD
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowMovimientoSalidaLinea         = $bd->VerRegRest("MOVIMIENTO_SALIDA_LINEA",
            "ID_MOVIMIENTO_SALIDA = $idMovimientoSalida
                        AND ESTADO = 'Reservado para Preparacion'
                        AND ID_UBICACION = " . $arrDesubicacion["ID_UBICACION"] . "
                        AND ID_ALMACEN = $rowLinea->ID_ALMACEN_ORIGEN
                        AND ID_MATERIAL = " . $arrDesubicacion["ID_MATERIAL"] . "
                        AND ID_MATERIAL_FISICO " . ($arrDesubicacion["ID_MATERIAL_FISICO"] == NULL ? 'IS NULL' : "= " . $arrDesubicacion["ID_MATERIAL_FISICO"]) . "
                        AND ID_PEDIDO_SALIDA = $rowPedidoSalida->ID_PEDIDO_SALIDA
                        AND ID_PEDIDO_SALIDA_LINEA = $rowLinea->ID_PEDIDO_SALIDA_LINEA
                        AND TIPO_LOTE = '" . $arrDesubicacion["TIPO_LOTE"] . "'
                        AND ID_UBICACION_DESTINO " . ($idUbicacionDestino == NULL ? 'IS NULL' : "= $idUbicacionDestino") . "
                        AND ID_ALMACEN_DESTINO " . ($idAlmacenDestino == NULL ? 'IS NULL' : "= $idAlmacenDestino") . "
                        AND ID_TIPO_BLOQUEO " . ($arrDesubicacion["ID_TIPO_BLOQUEO"] == NULL ? 'IS NULL' : "= " . $arrDesubicacion["ID_TIPO_BLOQUEO"]) . "
                        AND ID_ORDEN_TRABAJO_MOVIMIENTO " . ($arrDesubicacion["ID_ORDEN_TRABAJO_MOVIMIENTO"] == NULL ? 'IS NULL' : "= " . $arrDesubicacion["ID_ORDEN_TRABAJO_MOVIMIENTO"]) . "
                        AND ID_INCIDENCIA_CALIDAD " . ($arrDesubicacion["ID_INCIDENCIA_CALIDAD"] == NULL ? 'IS NULL' : "= " . $arrDesubicacion["ID_INCIDENCIA_CALIDAD"]) . "
                        AND ID_PROVEEDOR_GARANTIA IS NULL
                        AND LINEA_ANULADA = 0
                        AND BAJA = 0", "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);

        if ($rowMovimientoSalidaLinea != false): //HAY UNA LINEA DE MOVIMIENTO SALIDA LINEA SIMILAR
            //ACTUALIZO LA LINEA DE MOVIMIENTO DE SALIDA LINEA
            $sqlUpdate = "UPDATE MOVIMIENTO_SALIDA_LINEA SET
                            CANTIDAD = CANTIDAD + " . $arrDesubicacion["CANTIDAD"] . "
                            , CANTIDAD_PEDIDO = 0
                            WHERE ID_MOVIMIENTO_SALIDA_LINEA = $rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA_LINEA";
            $bd->ExecSQL($sqlUpdate);

            //ESTABLEZCO EL IDENTIFICDOR DE LA LINEA DE MOVIMIENTO DE SALIDA IMPLICADA
            $idMovimientoSalidaLinea = $rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA_LINEA;
        else:
            //CREAMOS LA LINEA DEL MOVIMIENTO DE SALIDA
            $sql = "INSERT INTO MOVIMIENTO_SALIDA_LINEA SET
                    ID_MOVIMIENTO_SALIDA = $idMovimientoSalida
                    , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                    , FECHA = '" . date("Y-m-d H:i:s") . "'
                    , ESTADO = 'Reservado para Preparacion'
                    , ID_UBICACION = " . $arrDesubicacion["ID_UBICACION"] . "
                    , ID_ALMACEN = $rowLinea->ID_ALMACEN_ORIGEN
                    , ID_MATERIAL = " . $arrDesubicacion["ID_MATERIAL"] . "
                    , ID_MATERIAL_FISICO = " . ($arrDesubicacion["ID_MATERIAL_FISICO"] == NULL ? 'NULL' : $arrDesubicacion["ID_MATERIAL_FISICO"]) . "
                    , ID_PEDIDO_SALIDA = $rowPedidoSalida->ID_PEDIDO_SALIDA
                    , ID_PEDIDO_SALIDA_LINEA = $rowLinea->ID_PEDIDO_SALIDA_LINEA
                    , TIPO_LOTE = '" . $arrDesubicacion["TIPO_LOTE"] . "'
                    , CANTIDAD = " . $arrDesubicacion["CANTIDAD"] . "
                    , CANTIDAD_PEDIDO = 0
                    , ID_UBICACION_DESTINO = " . ($idUbicacionDestino == NULL ? 'NULL' : "$idUbicacionDestino") . "
                    , ID_ALMACEN_DESTINO = " . ($idAlmacenDestino == NULL ? 'NULL' : "$idAlmacenDestino") . "
                    , ID_TIPO_BLOQUEO = " . ($arrDesubicacion["ID_TIPO_BLOQUEO"] == NULL ? 'NULL' : $arrDesubicacion["ID_TIPO_BLOQUEO"]) . "
                    , ID_ORDEN_TRABAJO_MOVIMIENTO = " . ($arrDesubicacion["ID_ORDEN_TRABAJO_MOVIMIENTO"] == NULL ? 'NULL' : $arrDesubicacion["ID_ORDEN_TRABAJO_MOVIMIENTO"]) . "
                    , ID_INCIDENCIA_CALIDAD = " . ($arrDesubicacion["ID_INCIDENCIA_CALIDAD"] == NULL ? 'NULL' : $arrDesubicacion["ID_INCIDENCIA_CALIDAD"]) . "
                    , ID_PROVEEDOR_GARANTIA = NULL";
            $bd->ExecSQL($sql);
            //ESTABLEZCO EL IDENTIFICDOR DE LA LINEA DE MOVIMIENTO DE SALIDA IMPLICADA
            $idMovimientoSalidaLinea = $bd->IdAsignado();
        endif;

        //BUSCAMOS LA LINEA DE MOVIMIENTO DE SALIDA
        $rowMovimientoSalidaLinea = $bd->VerReg("MOVIMIENTO_SALIDA_LINEA", "ID_MOVIMIENTO_SALIDA_LINEA", $idMovimientoSalidaLinea, "No");

        //OBTENEMOS LA UBICACION DE ORIGEN
        $rowUbiOrigen = $bd->VerReg("UBICACION", "ID_UBICACION", $rowMovimientoSalidaLinea->ID_UBICACION, "No");

        //SI LA UBICACION ES DE AUTOSTORE
        if ($rowUbiOrigen->AUTOSTORE == 1):
            //VERIFICAMOS QUE LA UBICACION ORIGEN TIENE UBICACION DE CENTRO FISICO ASIGNADA
            $html->PagErrorCondicionado($rowUbiOrigen->ID_UBICACION_CENTRO_FISICO, "==", NULL, "UbicacionAutostoreNoTieneAsignadoUbicacionCF");

            //BUSCAMOS LA UBICACION DE CENTRO FISICO
            $rowUbiCentroFisico = $bd->VerRegRest("UBICACION_CENTRO_FISICO", "ID_UBICACION_CENTRO_FISICO = $rowUbiOrigen->ID_UBICACION_CENTRO_FISICO AND BAJA = 0", "No");
            //VERIFICAMOS QUE EXISTA LA UBICACION DE CENTRO FISICO
            $html->PagErrorCondicionado($rowUbiCentroFisico, "==", false, "UbicacionCFNoExiste");

            //OBTENEMOS EL MOVIMIENTO DE SALIDA
            $rowMovimientoSalida = $bd->VerReg("MOVIMIENTO_SALIDA", "ID_MOVIMIENTO_SALIDA", $idMovimientoSalida, "No");

            //MARCAMOS QUE LA ORDEN DE PREPARACION TIENE UBICACIONES DE AUTOSTORE
            $sql = "UPDATE ORDEN_PREPARACION SET TIENE_UBICACIONES_AUTOSTORE = 1, INFORMACION_ENVIADA_A_AUTOSTORE = 0 WHERE ID_ORDEN_PREPARACION = $rowMovimientoSalida->ID_ORDEN_PREPARACION";
            $bd->ExecSQL($sql);

            //BUSCAMOS SI HAY UN REGISTRO DE AUTOSTORE_SALIDA VALIDO
            $rowAutostoreSalida = $bd->VerRegRest("AUTOSTORE_SALIDA", "ID_ORDEN_PREPARACION = " . $rowMovimientoSalida->ID_ORDEN_PREPARACION . " AND ESTADO = 'En Curso' AND CONFIRMACION_SALIDA_EN_AUTOSTORE = 0 AND BAJA = 0", "No");

            //SINO EXISTE LO CREAMOS
            if ($rowAutostoreSalida == false):
                //OBTENEMOS LA ORDEN DE PREPARACION
                $rowOrdenPreparacion = $bd->VerReg("ORDEN_PREPARACION", "ID_ORDEN_PREPARACION", $rowMovimientoSalida->ID_ORDEN_PREPARACION);

                $sqlInsertAutostoreSalida = "INSERT INTO AUTOSTORE_SALIDA SET
                                             ID_CENTRO_FISICO = $rowOrdenPreparacion->ID_CENTRO_FISICO_ORIGEN
                                           , PUESTO = ''
                                           , ID_USUARIO = $administrador->ID_ADMINISTRADOR
                                           , FECHA = '" . date("Y-m-d H:i:s") . "'
                                           , ID_MOVIMIENTO_TRANSFERENCIA = NULL
                                           , ID_ORDEN_PREPARACION = $rowMovimientoSalida->ID_ORDEN_PREPARACION
                                           , ESTADO = 'En curso'
                                           , CONFIRMACION_SALIDA_EN_AUTOSTORE = 0 
                                           , BAJA = 0 ";
                $bd->ExecSQL($sqlInsertAutostoreSalida);
                $idAutostoreSalida = $bd->IdAsignado();
            else:
                $idAutostoreSalida = $rowAutostoreSalida->ID_AUTOSTORE_SALIDA;
            endif;

            //BUSCO EL MAXIMO NUMERO DE LINEA
            $UltimoNumeroLinea       = 0;
            $sqlUltimoNumeroLinea    = "SELECT MAX(CAST(POSICION AS UNSIGNED)) AS NUMERO_LINEA FROM AUTOSTORE_SALIDA_LINEA WHERE ID_AUTOSTORE_SALIDA = $idAutostoreSalida";
            $resultUltimoNumeroLinea = $bd->ExecSQL($sqlUltimoNumeroLinea);
            if ($resultUltimoNumeroLinea != false):
                $rowUltimoNumeroLinea = $bd->SigReg($resultUltimoNumeroLinea);
                if ($rowUltimoNumeroLinea->NUMERO_LINEA != NULL):
                    $UltimoNumeroLinea = $rowUltimoNumeroLinea->NUMERO_LINEA;
                endif;
            endif;

            $SiguienteNumeroLinea = $UltimoNumeroLinea + 10;

            //BUSCO EL TIPO DE BLOQUEO PREVENTIVO
            $rowTipoBloqueoPreventivo = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", 'SP');

            //BUSCO EL TIPO DE BLOQUEO RESERVADO PARA PREPARACION
            $rowTipoBloqueoReservadoParaPreparacion = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", 'RP');

            //BUSCO EL TIPO DE BLOQUEO RESERVADO PARA PREPARACION PREVENTIVO
            $rowTipoBloqueoReservadoParaPreparacionPreventivo = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", 'RPP');

            //BUSCO EL TIPO DE BLOQUEO A PREPARAR
            $idTipoBloqueoPreparar = $rowMovimientoSalidaLinea->ID_TIPO_BLOQUEO;

            //EN FUNCION DEL TIPO DE BLOQUEO A PREPARAR SELECCIONAMOS EL TIPO DE BLOQUEO A RESERVAR
            if ($idTipoBloqueoPreparar == NULL):
                $rowTipoBloqueoReservar = $rowTipoBloqueoReservadoParaPreparacion;
            elseif ($idTipoBloqueoPreparar == $rowTipoBloqueoPreventivo->ID_TIPO_BLOQUEO):
                $rowTipoBloqueoReservar = $rowTipoBloqueoReservadoParaPreparacionPreventivo;
            else:
                $rowTipoBloqueoReservar = $rowTipoBloqueoReservadoParaPreparacion;
            endif;

            //BUSCAMOS SI HAY UN REGISTRO DE AUTOSTORE_SALIDA_LINEA VALIDO
            $rowAutostoreSalidaLinea = $bd->VerRegRest("AUTOSTORE_SALIDA_LINEA", "ID_AUTOSTORE_SALIDA = " . $idAutostoreSalida . " AND ID_MOVIMIENTO_SALIDA_LINEA = " . $rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA_LINEA . " AND CONFIRMACION_SALIDA_EN_AUTOSTORE = 0 AND BAJA = 0", "No");

            //EN FUNCION DE SI EXISTE O NO UN REGISTRO VALIDO
            if ($rowAutostoreSalidaLinea == false):
                //CREAMOS EL REGISTRO DE AUTOSTORE_SALIDA_LINEA
                $sqlInsertAutostoreSalidaLinea = "INSERT INTO AUTOSTORE_SALIDA_LINEA SET
                                                     ID_AUTOSTORE_SALIDA = $idAutostoreSalida
                                                   , POSICION = '" . str_pad( (string)$SiguienteNumeroLinea, 5, "0", STR_PAD_LEFT)  . "'
                                                   , ID_ALMACEN = $rowMovimientoSalidaLinea->ID_ALMACEN
                                                   , ID_UBICACION_ORIGEN = $rowMovimientoSalidaLinea->ID_UBICACION
                                                   , ID_UBICACION_DESTINO = $rowMovimientoSalidaLinea->ID_UBICACION_DESTINO
                                                   , ID_MATERIAL = $rowMovimientoSalidaLinea->ID_MATERIAL
                                                   , ID_MATERIAL_FISICO = " . ($rowMovimientoSalidaLinea->ID_MATERIAL_FISICO == NULL ? 'NULL' : $rowMovimientoSalidaLinea->ID_MATERIAL_FISICO) . "
                                                   , ID_TIPO_BLOQUEO = $rowTipoBloqueoReservar->ID_TIPO_BLOQUEO 
                                                   , ID_ORDEN_TRABAJO_MOVIMIENTO = " . ($rowMovimientoSalidaLinea->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? 'NULL' : $rowMovimientoSalidaLinea->ID_ORDEN_TRABAJO_MOVIMIENTO) . "
                                                   , ID_INCIDENCIA_CALIDAD = " .  ($rowMovimientoSalidaLinea->ID_INCIDENCIA_CALIDAD == NULL ? 'NULL' :  $rowMovimientoSalidaLinea->ID_INCIDENCIA_CALIDAD) . "
                                                   , CANTIDAD = " . $arrDesubicacion["CANTIDAD"] . "
                                                   , CANTIDAD_CONFIRMADA = 0
                                                   , ID_MOVIMIENTO_SALIDA_LINEA = $rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA_LINEA
                                                   , CONFIRMACION_SALIDA_EN_AUTOSTORE = 0
                                                   , BAJA = 0 ";
                $bd->ExecSQL($sqlInsertAutostoreSalidaLinea);
            else:
                //ACTUALIZO LA LINEA DE MOVIMIENTO DE SALIDA LINEA
                $sqlUpdate = "UPDATE AUTOSTORE_SALIDA_LINEA SET
                                CANTIDAD = CANTIDAD + " . $arrDesubicacion["CANTIDAD"] . "
                                WHERE ID_AUTOSTORE_SALIDA_LINEA = $rowAutostoreSalidaLinea->ID_AUTOSTORE_SALIDA_LINEA";
                $bd->ExecSQL($sqlUpdate);
            endif;
            //FIN EN FUNCION DE SI SEXISTE O NO UN REGISTRO VALIDO
        endif;

        //DEVUELVO LAS LINEA DEL MOVIMIENTO DE SALIDA
        return $idMovimientoSalidaLinea;
    }

    function NumeroLineasNoDevolucion($idPedido)
    {
        global $bd;

        $num = $bd->NumRegRest("PEDIDO_SALIDA_LINEA", "ID_PEDIDO_SALIDA = $idPedido AND ID_MOVIMIENTO_ENTRADA_LINEA IS NULL", "No");

        return $num;
    }

    //DEVUELVE LAS INICIALES DEL TIPO DE UN PEDIDO DE ENTRADA
    function tipoPedidoEntrada($tipoPedidoEntrada)
    {

        global $auxiliar;
        global $administrador;

        $tipoPedidoRetorno = "-";
        if ($tipoPedidoEntrada == "Compra"):
            $tipoPedidoRetorno = $auxiliar->traduce("SIGLA_C_COMPRA", $administrador->ID_IDIOMA);
        elseif ($tipoPedidoEntrada == "Reparación"):
            $tipoPedidoRetorno = $auxiliar->traduce("SIGLA_R_REPARACION", $administrador->ID_IDIOMA);
        elseif ($tipoPedidoEntrada == "Garantía"):
            $tipoPedidoRetorno = $auxiliar->traduce("SIGLA_G_GARANTIA", $administrador->ID_IDIOMA);
        elseif ($tipoPedidoEntrada == "Devolución de Venta"):
            $tipoPedidoRetorno = $auxiliar->traduce("SIGLA_DV_DEVOLUCION_VENTA", $administrador->ID_IDIOMA);
        elseif ($tipoPedidoEntrada == "Compra SGA Manual"):
            $tipoPedidoRetorno = $auxiliar->traduce("SIGLA_SGA_COMPRA_SGA_MANUAL", $administrador->ID_IDIOMA);
        elseif ($tipoPedidoEntrada == "Resolucion Licitacion"):
            $tipoPedidoRetorno = $auxiliar->traduce("SIGLA_RL_RESOLUCION_LICITACION", $administrador->ID_IDIOMA);
        elseif ($tipoPedidoEntrada == "Servicios"):
            $tipoPedidoRetorno = $auxiliar->traduce("SIGLA_S_SERVICIOS", $administrador->ID_IDIOMA);
        elseif ($tipoPedidoEntrada == "Construccion"):
            $tipoPedidoRetorno = $auxiliar->traduce("SIGLA_CONS_CONSTRUCCION", $administrador->ID_IDIOMA);
        else:
            $tipoPedidoRetorno = "-";
        endif;

        return $tipoPedidoRetorno;
    }

    //DEVUELVE LAS INICIALES DEL ESTADO DE UN PEDIDO DE ENTRADA
    function estadoPedidoEntrada($estadoPedidoEntrada)
    {

        global $auxiliar;
        global $administrador;

        $estadoPedidoRetorno = "-";
        if ($estadoPedidoEntrada == "Creado"):
            $estadoPedidoRetorno = $auxiliar->traduce("SIGLA_CO_CREADO", $administrador->ID_IDIOMA);
        elseif ($estadoPedidoEntrada == "En Entrega"):
            $estadoPedidoRetorno = $auxiliar->traduce("SIGLA_EE_EN_ENTREGA", $administrador->ID_IDIOMA);
        elseif ($estadoPedidoEntrada == "Entregado"):
            $estadoPedidoRetorno = $auxiliar->traduce("SIGLA_EO_ENTREGADO", $administrador->ID_IDIOMA);
        else:
            $estadoPedidoRetorno = "-";
        endif;

        return $estadoPedidoRetorno;
    }

    //DEVUELVE LAS INICIALES DEL ESTADO DE LIBERACION DE UN PEDIDO DE ENTRADA
    function liberacionPedidoEntrada($liberacionPedidoEntrada)
    {

        global $auxiliar;
        global $administrador;

        $liberacionPedidoRetorno = "-";
        if ($liberacionPedidoEntrada == "En Liberación"):
            $liberacionPedidoRetorno = $auxiliar->traduce("SIGLA_EL_EN_LIBERACION", $administrador->ID_IDIOMA);
        elseif ($liberacionPedidoEntrada == "Liberado"):
            $liberacionPedidoRetorno = $auxiliar->traduce("SIGLA_LB_LIBERADO", $administrador->ID_IDIOMA);
        elseif ($liberacionPedidoEntrada == "Rechazado"):
            $liberacionPedidoRetorno = $auxiliar->traduce("SIGLA_RC_RECHAZADO", $administrador->ID_IDIOMA);
        elseif ($liberacionPedidoEntrada == "No sujeto a liberación"):
            $liberacionPedidoRetorno = $auxiliar->traduce("SIGLA_NS_NO_SUJETO", $administrador->ID_IDIOMA);
        else:
            $liberacionPedidoRetorno = "-";
        endif;

        return $liberacionPedidoRetorno;
    }

    //DEVUELVE LAS INICIALES DEL ESTADO DE UN PEDIDO DE ENTRADA
    function estadoPedidoSalida($estadoPedidoSalida)
    {

        global $auxiliar;
        global $administrador;

        $estadoPedidoRetorno = "-";
        if ($estadoPedidoSalida == "Grabado"):
            $estadoPedidoRetorno = $auxiliar->traduce("SIGLA_GR_GRABADO", $administrador->ID_IDIOMA);
        elseif ($estadoPedidoSalida == "En Entrega"):
            $estadoPedidoRetorno = $auxiliar->traduce("SIGLA_EE_EN_ENTREGA", $administrador->ID_IDIOMA);
        elseif ($estadoPedidoSalida == "Finalizado"):
            $estadoPedidoRetorno = $auxiliar->traduce("SIGLA_FI_FINALIZADO", $administrador->ID_IDIOMA);
        else:
            $estadoPedidoRetorno = "-";
        endif;

        return $estadoPedidoRetorno;
    }

    //DEVUELVE LAS INICIALES DEL TIPO DE UN PEDIDO DE ENTRADA
    function tipoPedidoSalida($tipoPedidoSalida)
    {

        global $auxiliar;
        global $administrador;

        $tipoPedidoRetorno = "-";
        if ($tipoPedidoSalida == "Venta"):
            $tipoPedidoRetorno = $auxiliar->traduce("SIGLA_VE_VENTA", $administrador->ID_IDIOMA);
        elseif ($tipoPedidoSalida == "Traslado"):
            $tipoPedidoRetorno = $auxiliar->traduce("SIGLA_TR_TRASLADO", $administrador->ID_IDIOMA);
        elseif ($tipoPedidoSalida == "Devolución a Proveedor"):
            $tipoPedidoRetorno = $auxiliar->traduce("SIGLA_DP_DEV_PROV", $administrador->ID_IDIOMA);
        elseif ($tipoPedidoSalida == "Componentes a Proveedor"):
            $tipoPedidoRetorno = $auxiliar->traduce("SIGLA_CP_COMP_PROV", $administrador->ID_IDIOMA);
        elseif ($tipoPedidoSalida == "Rechazos y Anulaciones a Proveedor"):
            $tipoPedidoRetorno = $auxiliar->traduce("SIGLA_RAP_RECH_ANUL_PROV", $administrador->ID_IDIOMA);
        elseif ($tipoPedidoSalida == "Intra Centro Fisico"):
            $tipoPedidoRetorno = $auxiliar->traduce("SIGLA_ICF_INTRA_CENTRO_FISICO", $administrador->ID_IDIOMA);
        elseif ($tipoPedidoSalida == "Interno Gama"):
            $tipoPedidoRetorno = $auxiliar->traduce("SIGLA_IG_INTERNO_GAMA", $administrador->ID_IDIOMA);
        elseif ($tipoPedidoSalida == "Material Estropeado a Proveedor"):
            $tipoPedidoRetorno = $auxiliar->traduce("SIGLA_MEP_MATERIAL_ESTROPEADO_A_PROVEEDOR", $administrador->ID_IDIOMA);
        elseif ($tipoPedidoSalida == "Traspaso Entre Almacenes Material Estropeado"):
            $tipoPedidoRetorno = $auxiliar->traduce("SIGLA_TEAE_TRASPADO_ENTRE_ALMACENES_ESTROPEADO", $administrador->ID_IDIOMA);
        elseif ($tipoPedidoSalida == 'Traslados OM Construccion'):
            $tipoPedidoRetorno = $auxiliar->traduce("SIGLA_TOM_TRASLADO_OM", $administrador->ID_IDIOMA);
        elseif ($tipoPedidoSalida == 'Preparacion AGM'):
            $tipoPedidoRetorno = $auxiliar->traduce("SIGLA_PAGM_PREPARACION_AGM", $administrador->ID_IDIOMA);
        elseif ($tipoPedidoSalida == 'Pendientes de Ordenes Trabajo'):
            $tipoPedidoRetorno = $auxiliar->traduce("SIGLA_POT_PENDIENTES_OT", $administrador->ID_IDIOMA);
        else:
            $tipoPedidoRetorno = "-";
        endif;

        return $tipoPedidoRetorno;
    }

    //FUNCION QUE LLAMA A SAP INDICANDO LAS LINEAS QUE ESTAN BLOQUEADAS O NO
    function controlBloqueoLinea($tipoEntradaSalida, $accion, $listaLineasPedidos)
    {
        global $sap;
        global $bd;
        global $administrador;
        global $strError;
        global $html;

        //ARAY DE BLOQUEOS DE LINEAS
        $arrLineas = array();

        if ($tipoEntradaSalida == "Entrada"):    //PEDIDOS DE ENTRADA

            $sqlLineas    = "SELECT * FROM PEDIDO_ENTRADA_LINEA WHERE ID_PEDIDO_ENTRADA_LINEA IN ($listaLineasPedidos)";
            $resultLineas = $bd->ExecSQL($sqlLineas);
            while ($rowLinea = $bd->SigReg($resultLineas)):    //BUCLE LINEA DE PEDIDO
                //VARIABLE PARA SABER SI HAY QUE AÑADIR LA LINEA AL ARRAY DE LINEAS A INFORMAR A SAP
                $informarLinea = false;

                //BUSCO EL PEDIDO DE ENTRADA
                $rowPed = $bd->VerReg("PEDIDO_ENTRADA", "ID_PEDIDO_ENTRADA", $rowLinea->ID_PEDIDO_ENTRADA, "No");

                unset($objLinea);    //VACIO EL OBJETO LINEA
                $objLinea            = new stdClass;
                $objLinea->PEDIDO    = $rowPed->PEDIDO_SAP;    // Número de pedido cuyas posiciones vamos a bloquear (Obligatorio)
                $objLinea->POSICION  = $rowLinea->LINEA_PEDIDO_SAP;    // Línea de pedido a bloquear (Obligatorio)
                $objLinea->BLOQUEADO = '';    // (vacio=NO, X=SI)

                //COMPRUEBO SI LA LINEA DE ENTRADA ESTA EN MOVIMIENTOS DE ENTRADA EN ESTADO EN PROCESO
                $sqlCount    = "SELECT COUNT(*) AS NUM
										 FROM MOVIMIENTO_ENTRADA_LINEA MEL
										 INNER JOIN MOVIMIENTO_ENTRADA ME ON ME.ID_MOVIMIENTO_ENTRADA = MEL.ID_MOVIMIENTO_ENTRADA
										 WHERE MEL.ID_PEDIDO = $rowLinea->ID_PEDIDO_ENTRADA AND MEL.ID_PEDIDO_LINEA = $rowLinea->ID_PEDIDO_ENTRADA_LINEA AND MEL.BAJA=0 AND MEL.LINEA_ANULADA = 0 AND ME.ESTADO = 'En Proceso'";
                $resultCount = $bd->ExecSQL($sqlCount);
                $rowCount    = $bd->SigReg($resultCount);

                if ($rowCount->NUM > 0):
                    $objLinea->BLOQUEADO = 'X';    // (vacio=NO, X=SI)
                endif;

                //COMPRUEBO SI ALGUNA LINEA DE MOVIMIENTO DE ESTA LINEA DE PEDIDO TIENE EL TIPO DE BLOQUEO DEL MATERIAL CON CONTROL DE CALIDAD
                $sqlCount    = "SELECT COUNT(*) AS NUM
										 FROM MOVIMIENTO_ENTRADA_LINEA MEL 
										 WHERE MEL.ID_PEDIDO = $rowLinea->ID_PEDIDO_ENTRADA AND MEL.ID_PEDIDO_LINEA = $rowLinea->ID_PEDIDO_ENTRADA_LINEA AND MEL.BAJA=0 AND MEL.LINEA_ANULADA = 0 AND MEL.CANTIDAD > 0 AND MEL.ID_TIPO_BLOQUEO IN (SELECT ID_TIPO_BLOQUEO FROM TIPO_BLOQUEO WHERE CONTROL_CALIDAD = 1)";
                $resultCount = $bd->ExecSQL($sqlCount);
                $rowCount    = $bd->SigReg($resultCount);

                if ($rowCount->NUM > 0):
                    $objLinea->BLOQUEADO = 'X';    // (vacio=NO, X=SI)
                endif;

                //SI LA LINEA NO ESTA BLOQUEADA Y EL PEDIDO ES DE REPARACION COMPRUEBO LAS LINEAS DE SALIDA (COMPONENTES A PROVEEDOR)
                if (($objLinea->BLOQUEADO == '') && (($rowPed->TIPO_PEDIDO == 'Reparación') || ($rowPed->TIPO_PEDIDO == 'Garantía'))):
                    //BUSCO SI EXISTE LAS LINEAS DEL PEDIDO DE SALIDA DE COMPONENTES
                    $sqlLineasComponentes    = "SELECT * FROM PEDIDO_SALIDA_LINEA WHERE ID_LINEA_ZREP_ZGAR = $rowLinea->ID_PEDIDO_ENTRADA_LINEA";
                    $resultLineasComponentes = $bd->ExecSQL($sqlLineasComponentes);
                    while ($rowLineaComponentes = $bd->SigReg($resultLineasComponentes)):    //BUCLE LINEA DE PEDIDO DE COMPONENTES
                        //COMPRUEBO SI LA LINEA DE SALIDA ESTA EN MOVIMIENTOS DE SALIDA PREPARANDOSE AND MSL.ESTADO IN ('En Preparacion', 'Pendiente de Expedir')
                        //SEGUN (DMND0001666), SE CAMBIA PARA QUE SE DESBLOQUEE SI LOS COMPONENTES ESTAN PREPARADOS O EXPEDIDOS
                        $sqlCount    = "SELECT COUNT(*) AS NUM
												 FROM MOVIMIENTO_SALIDA_LINEA MSL
												 WHERE MSL.ID_PEDIDO_SALIDA = $rowLineaComponentes->ID_PEDIDO_SALIDA AND MSL.ID_PEDIDO_SALIDA_LINEA = $rowLineaComponentes->ID_PEDIDO_SALIDA_LINEA AND LINEA_ANULADA = 0 AND BAJA = 0";
                        $resultCount = $bd->ExecSQL($sqlCount);
                        $rowCount    = $bd->SigReg($resultCount);

                        if ($rowCount->NUM > 0):
                            $objLinea->BLOQUEADO = 'X';    // (vacio=NO, X=SI)
                        endif;
                    endwhile;    //FIN BUCLE LINEA DE PEDIDO	 DE COMPONENTES
                endif;
                //FIN SI LA LINEA NO ESTA BLOQUEADA Y EL PEDIDO ES DE REPARACION COMPRUEBO LAS LINEAS DE SALIDA (COMPONENTES A PROVEEDOR)

                //COMPROBAMOS SI TIENE O NO TIENE NECESIDADES (DMND0001666)
                $tieneNecesidades  = false;
                $sqlNecesidades    = "SELECT SUM(CANTIDAD) AS STOCK,NL.ID_NECESIDAD_LINEA
                                             FROM NECESIDAD_LINEA NL
                                             WHERE NL.ID_PEDIDO_ENTRADA_LINEA = $rowLinea->ID_PEDIDO_ENTRADA_LINEA AND NL.BAJA = 0";
                $resultNecesidades = $bd->ExecSQL($sqlNecesidades);
                $rowNecesidades    = $bd->SigReg($resultNecesidades);
                if (($rowNecesidades != false) && ($rowNecesidades->ID_NECESIDAD_LINEA != NULL)):
                    $tieneNecesidades = true;
                    if ($objLinea->BLOQUEADO == '')://SI ESTUVIESE BLOQUEADO POR X, LA X ES MAS RESTRICTIVA POR ESO NO SE MANDARIA Y
                        $objLinea->BLOQUEADO = 'Y';    // Y = Pedido solo puede modificarse la cantidad al alza
                        $objLinea->CANTIDAD  = $rowNecesidades->STOCK;
                        //ENVIAMOS TAMBIEN LA UNIDAD
                        $rowMat                 = $bd->VerReg("MATERIAL", "ID_MATERIAL", $rowLinea->ID_MATERIAL);
                        $rowUnidad              = $bd->VerReg("UNIDAD", "ID_UNIDAD", $rowMat->ID_UNIDAD_MEDIDA);
                        $objLinea->UNIDADMEDIDA = $rowUnidad->UNIDAD;
                    endif;
                endif;

                if (($accion == 'BloquearSiempre')):
                    $informarLinea       = true;
                    $objLinea->BLOQUEADO = 'X';
                elseif (($accion == 'DesbloquearSiempre')):
                    $informarLinea       = true;
                    $objLinea->BLOQUEADO = '';
                endif;

                if ($accion == 'InsertarLinea'):
                    $informarLinea = true;
                elseif (($accion == 'Procesar') && ($objLinea->BLOQUEADO != 'X')):
                    $informarLinea = true;
                elseif (($accion == 'AnularMovimiento') && ($objLinea->BLOQUEADO != 'X')):
                    $informarLinea = true;
                elseif (($accion == 'BorrarLinea') && ($objLinea->BLOQUEADO != 'X')):
                    $informarLinea = true;
                elseif (($accion == 'AnularLinea') && ($objLinea->BLOQUEADO != 'X')):
                    $informarLinea = true;
                elseif (($accion == 'ControlCalidad') && ($objLinea->BLOQUEADO != 'X')):
                    $informarLinea = true;
                elseif (($accion == 'SplitLineas') && ($objLinea->BLOQUEADO != '')):
                    $informarLinea = true;
                elseif (($accion == 'ModificarNecesidad') && ($objLinea->BLOQUEADO == '' || $objLinea->BLOQUEADO == 'Y'))://Al desasignar una necesidad puede quedar parte bloqueado en otra
                    $informarLinea = true;
                elseif (($accion == 'AsignarNecesidad') && ($objLinea->BLOQUEADO == 'Y')):
                    $informarLinea = true;
                elseif (($accion == 'DesbloquearNecesidad') && ($tieneNecesidades == true))://SE USA PARA DESBLOQUEAR PREVIO A UN SPLIT
                    $objLinea->BLOQUEADO = '';
                    $informarLinea       = true;
                endif;


                //AÑADIMOS LA LINEA AL ARRAY A TRASPASAR SI VARIA EL TIPO DE BLOQUEO
                if ($informarLinea == true):
                    $arrLineas[] = $objLinea;
                endif;
            endwhile;    //FIN BUCLE LINEA DE PEDIDO


        elseif ($tipoEntradaSalida == "Salida"):    //PEDIDOS DE SALIDA

            //CONTROLAMOS QUE NO ENVIEMOS DOS VECES LA MISMA LINEA DE COMPONENTES
            $arr_lineas_componentes = array();

            $sqlLineas    = "SELECT * FROM PEDIDO_SALIDA_LINEA WHERE ID_PEDIDO_SALIDA_LINEA IN ($listaLineasPedidos)";
            $resultLineas = $bd->ExecSQL($sqlLineas);
            while ($rowLinea = $bd->SigReg($resultLineas)):    //BUCLE LINEAS DE PEDIDO
                //VARIABLE PARA SABER SI HAY QUE AÑADIR LA LINEA AL ARRAY DE LINEAS A INFORMAR A SAP
                $informarLinea = false;

                //BUSCO EL PEDIDO DE SALIDA
                $rowPed = $bd->VerReg("PEDIDO_SALIDA", "ID_PEDIDO_SALIDA", $rowLinea->ID_PEDIDO_SALIDA, "No");

                //COMPRUEBO QUE EL PEDIDO DE SALIDA EXISTA
                $html->PagErrorCondicionado($rowPed, "==", false, "PedidoSalidaNoExiste");

                if ($rowPed->TIPO_PEDIDO_SAP == 'ZTRH'):

                    //NO ENVIAR BLOQUEOS

                else:    //RESTO PEDIDOS

                    unset($objLinea);    //VACIO EL OBJETO LINEA
                    $objLinea         = new stdClass;
                    $objLinea->PEDIDO = $rowPed->PEDIDO_SAP;    // Número de pedido cuyas posiciones vamos a bloquear (Obligatorio)

                    if ($rowPed->TIPO_PEDIDO == 'Componentes a Proveedor'):    //PEDIDO COMPONENTES A PROVEEDOR
                        //ENVIAR BLOQUEO DEL ZREP/ZGAR RELACIONADO SI LA LINEA DE COMPONENTE SE ESTA PREPARANDO (DMND0001666)

                        //COMPROBAMOS QUE NO LO ESTEMOS EVIANDO YA
                        if (in_array($rowLinea->ID_LINEA_ZREP_ZGAR, (array) $arr_lineas_componentes)):
                            continue;
                        else:
                            //BUSCO LA LINEA DEL PEDIDO DE ENTRADA
                            $rowPedLineaEntrada       = $bd->VerReg("PEDIDO_ENTRADA_LINEA", "ID_PEDIDO_ENTRADA_LINEA", $rowLinea->ID_LINEA_ZREP_ZGAR);
                            $objLinea->POSICION       = $rowPedLineaEntrada->LINEA_PEDIDO_SAP; // Línea de pedido a bloquear (Obligatorio)
                            $arr_lineas_componentes[] = $rowLinea->ID_LINEA_ZREP_ZGAR;
                        endif;
                    else:
                        $objLinea->POSICION = $rowLinea->LINEA_PEDIDO_SAP;// Línea de pedido a bloquear (Obligatorio)
                    endif;

                    $objLinea->BLOQUEADO = '';    // (vacio=NO, X=SI)

                    //EN EL CASO DE COMPONENTES A PROVEEDOR, EL BLOQUEO ES SIEMPRE QUE TENGA MSL
                    if ($rowPed->TIPO_PEDIDO == 'Componentes a Proveedor'):    //PEDIDO COMPONENTES A PROVEEDOR
                        $sqlCount    = "SELECT COUNT(*) AS NUM
                                     FROM MOVIMIENTO_SALIDA_LINEA MSL
                                     WHERE MSL.ID_PEDIDO_SALIDA = $rowLinea->ID_PEDIDO_SALIDA AND MSL.ID_PEDIDO_SALIDA_LINEA = $rowLinea->ID_PEDIDO_SALIDA_LINEA AND LINEA_ANULADA = 0 AND BAJA = 0";
                        $resultCount = $bd->ExecSQL($sqlCount);
                    else:
                        //COMPRUEBO SI LA LINEA DE SALIDA ESTA PREPARANDOSE
                        $sqlCount    = "SELECT COUNT(*) AS NUM
                                     FROM MOVIMIENTO_SALIDA_LINEA MSL
                                     WHERE MSL.ID_PEDIDO_SALIDA = $rowLinea->ID_PEDIDO_SALIDA AND MSL.ID_PEDIDO_SALIDA_LINEA = $rowLinea->ID_PEDIDO_SALIDA_LINEA AND MSL.ESTADO IN ('Reservado para Preparacion', 'En Preparacion', 'Pendiente de Expedir') AND LINEA_ANULADA = 0 AND BAJA = 0";
                        $resultCount = $bd->ExecSQL($sqlCount);
                    endif;
                    $rowCount = $bd->SigReg($resultCount);
                    if ($rowCount->NUM > 0):
                        $objLinea->BLOQUEADO = 'X';    // (vacio=NO, X=SI)

                        //SI ES ZTRB o ZTRD ENVIAREMOS EL BLOQUEO Y CON LA CANTIDAD PREPARADA
                        if (($rowPed->TIPO_PEDIDO_SAP == 'ZTRB' || $rowPed->TIPO_PEDIDO_SAP == 'ZTRD')):
                            $objLinea->BLOQUEADO = 'Y'; // Y = Pedido puede modificarse a la baja hasta la cantidad preparada
                            $objLinea->CANTIDAD  = $rowLinea->CANTIDAD - $rowLinea->CANTIDAD_PENDIENTE_SERVIR;

                            //ENVIAMOS TAMBIEN LA UNIDAD
                            $rowMat                 = $bd->VerReg("MATERIAL", "ID_MATERIAL", $rowLinea->ID_MATERIAL);
                            $rowUnidad              = $bd->VerReg("UNIDAD", "ID_UNIDAD", $rowMat->ID_UNIDAD_MEDIDA);
                            $objLinea->UNIDADMEDIDA = $rowUnidad->UNIDAD;
                        endif;
                    endif;

                    //SI ES ZTRA,ZTRC, Y NO ESTA BLOQUEADO (EL X ES MAS RESTRICTIVO QUE EL Y), COMPROBAMOS SI TIENE NECESIDADES  (DMND0001666)
                    $tieneNecesidades = false;
                    if (($rowPed->TIPO_PEDIDO_SAP == 'ZTRA' || $rowPed->TIPO_PEDIDO_SAP == 'ZTRC')):
                        //COMPROBAMOS SI TIENE O NO TIENE NECESIDADES (DMND0001666)
                        $sqlNecesidades    = "SELECT SUM(CANTIDAD) AS STOCK, NL.ID_NECESIDAD_LINEA
                                             FROM NECESIDAD_LINEA NL
                                             WHERE NL.ID_PEDIDO_SALIDA_LINEA = $rowLinea->ID_PEDIDO_SALIDA_LINEA AND NL.BAJA = 0";
                        $resultNecesidades = $bd->ExecSQL($sqlNecesidades);
                        $rowNecesidades    = $bd->SigReg($resultNecesidades);
                        if (($rowNecesidades != false) && ($rowNecesidades->ID_NECESIDAD_LINEA != NULL)):
                            $tieneNecesidades = true;
                            if ($objLinea->BLOQUEADO == ''):
                                $objLinea->BLOQUEADO = 'Y';    // Y = Pedido solo puede modificarse la cantidad al alza
                                $objLinea->CANTIDAD  = $rowNecesidades->STOCK;
                                //ENVIAMOS TAMBIEN LA UNIDAD
                                $rowMat                 = $bd->VerReg("MATERIAL", "ID_MATERIAL", $rowLinea->ID_MATERIAL);
                                $rowUnidad              = $bd->VerReg("UNIDAD", "ID_UNIDAD", $rowMat->ID_UNIDAD_MEDIDA);
                                $objLinea->UNIDADMEDIDA = $rowUnidad->UNIDAD;
                            endif;
                        endif;
                    endif;

                    if (($accion == 'BloquearSiempre')):
                        $informarLinea       = true;
                        $objLinea->BLOQUEADO = 'X';
                    elseif (($accion == 'DesbloquearSiempre')):
                        $informarLinea       = true;
                        $objLinea->BLOQUEADO = '';
                    endif;

                    //AÑADIMOS LA LINEA AL ARRAY A TRASPASAR EN FUNCUION DE LA ACCION QUE ESTEMOS HACIENDO
                    if ($accion == 'GenerarMovimientos'):
                        $informarLinea = true;
                    //elseif (($accion == 'DesasignarLineaOrdenPreparacion') && ($objLinea->BLOQUEADO == '')):
                    elseif ($accion == 'DesasignarLineaOrdenPreparacion'): //SI SE HACE SPLIT SE LIBERA PREVIAMENTE LA LINEA
                        $informarLinea = true;
                    elseif (($accion == 'DesasignarLineaNoExpedida') && ($objLinea->BLOQUEADO != 'X')):
                        $informarLinea = true;
                    //elseif ( ($accion == 'ExpedirExpedicion') && ($objLinea->BLOQUEADO == '') ):
                    elseif (($accion == 'TransmitirExpedicionASAP') && ($objLinea->BLOQUEADO != 'X')):
                        $informarLinea = true;
                    elseif ($accion == 'AnularExpedicionSAP'):
                        $informarLinea = true;
                    elseif ($accion == 'AnularMovimiento'):
                        $informarLinea = true;
                    elseif (($accion == 'ModificarNecesidad') && ($objLinea->BLOQUEADO == '' || $objLinea->BLOQUEADO == 'Y') && ($rowPed->TIPO_PEDIDO_SAP == 'ZTRA' || $rowPed->TIPO_PEDIDO_SAP == 'ZTRC')):
                        $informarLinea = true;
                    elseif (($accion == 'AsignarNecesidad') && ($objLinea->BLOQUEADO == 'Y') && ($rowPed->TIPO_PEDIDO_SAP == 'ZTRA' || $rowPed->TIPO_PEDIDO_SAP == 'ZTRC')):
                        $informarLinea = true;
                    elseif (($accion == 'DesbloquearNecesidad') && ($tieneNecesidades == true))://SE USA PARA DESBLOQUEAR PREVIO A UN SPLIT
                        $objLinea->BLOQUEADO = '';
                        $informarLinea       = true;
                    elseif (($accion == 'BloquearSiNecesario') && ($objLinea->BLOQUEADO != '')):
                        $informarLinea = true;
                    endif;

                    if ($informarLinea == true):
                        $arrLineas[] = $objLinea;
                    endif;

                endif;//FIN PEDIDO COMPONENTES A PROVEEDOR SI O NO

            endwhile;    //FIN BUCLE LINEAS DE PEDIDOS

        endif;    //FIN PEDIDOS DE ENTRADA O SALIDA

        //LE PASAMOS A SAP SI LA LINEA DEL PEDIDO CORRESPONDIENTE (ENTRADA, SALIDA) ESTA BLOQUEADA
        if (count( (array)$arrLineas) > 0):
            return $sap->bloqueoLineasPedido($arrLineas);
        else:
            $arrResultado              = array();
            $arrResultado['RESULTADO'] = 'OK';

            return $arrResultado;
        endif;
    }

    /**
     * //Al crear un pedido de reparacioncon contrato marco desde los procesos automaticos >> EMAIL A LOS COMPRADORES
     *
     * @param $idPedidoSalida
     */
    function EnviarNotificacionEmail_PedidoreparacionContratoMarcoGeneradoAutomaticamente($idPedidoSalida)
    {
        global $bd;
        global $auxiliar;
        global $administrador;
        global $NotificaErrorPorEmail;

        //OBTENGO EL PEDIDO
        $rowPedido = $bd->VerReg("PEDIDO_SALIDA", "ID_PEDIDO_SALIDA", $idPedidoSalida, "No");
        if (!$rowPedido)
            return;

        //ARRAY PARA GUARDAR LOS DESTINATARIOS DEL CORREO
        $arrCorreosEsp = array();
        $arrCorreosEng = array();

        //OBTENGO LAS LINEAS
        //BUSCO LAS LINEAS DEL MOVIMIENTO PARA LAS CUALES CREAR UN PEDIDO DE REPARACION
        $sqlLineas    = "SELECT *
                      FROM PEDIDO_SALIDA_LINEA
                      WHERE ID_PEDIDO_SALIDA = " . $rowPedido->ID_PEDIDO_SALIDA . "
                      AND BAJA = 0";
        $resultLineas = $bd->ExecSQL($sqlLineas);

        //RECORRO LAS LINEAS
        while ($rowPedLinea = $bd->SigReg($resultLineas)) {

            //OBTENGO LOS COMPRADORES DE ESE MATERIAL EN ESE ALMACEN
            $sqlCompradores    = "SELECT A.* FROM ADMINISTRADOR A
                            INNER JOIN PLANIFICADOR P ON A.USUARIO_SAP = P.USUARIO_SAP
                            INNER JOIN MATERIAL_ALMACEN MA ON MA.ID_PLANIFICADOR = P.ID_PLANIFICADOR
                            WHERE
                            MA.ID_MATERIAL = '" . $rowPedLinea->ID_MATERIAL . "'
                            AND MA.ID_ALMACEN = '" . $rowPedLinea->ID_ALMACEN_DESTINO . "'
                            GROUP BY A.ID_ADMINISTRADOR";
            $resultCompradores = $bd->ExecSQL($sqlCompradores);
            while ($rowComprador = $bd->SigReg($resultCompradores)) {
                if ($rowComprador->EMAIL != ""):
                    $rowComprador->IDIOMA_NOTIFICACIONES == "ESP" ? $arrCorreosEsp[] = $rowComprador->EMAIL : $arrCorreosEng[] = $rowComprador->EMAIL;
                endif;
            }
        }

        $enlacePedido = '<br/><br/>' . '<a href="' . PEDIDOS_ENTRADA_URL_ENLACE . 'index.php?txIdPedido=' . $rowPedido->PEDIDO_SAP . '&chDetalleMaterial=1"> Ver pedido </a>';

        //GENERO CONTENIDO DEL EMAIL ESP
        $Asunto = $auxiliar->traduce('Pedido de materiales con contrato marco generado automáticamente', "ESP") . '. ' . $auxiliar->traduce('Nº Pedido SGA', "ESP") . ': ' . $idPedidoSalida;
        $Cuerpo = $auxiliar->traduce('Pedido de materiales con contrato marco generado automáticamente', "ESP");
        $Cuerpo .= '<br/><br/> · ' . $auxiliar->traduce('Nº Pedido SGA', "ESP") . ': ' . $idPedidoSalida;
        $Cuerpo .= '<br/> · ' . $auxiliar->traduce('Nº Pedido SAP', "ESP") . ': ' . ($rowPedido->PEDIDO_SAP);
        $Cuerpo .= $enlacePedido;

        //PREPARO EL MAIL
        if (PEDIDOS_CONTRATO_MARCO_ENVIAR_EMAILS_DESTINATARIOS_REALES) {
            $arrCorreosEsp = array_unique((array) $arrCorreosEsp);
            $correosEsp = implode(',', (array) $arrCorreosEsp);
        } else {
            $correosEsp = PEDIDOS_CONTRATO_MARCO_EMAIL_DESTINATARIO_TEST;
        }
        $auxiliar->enviarCorreoSistema($Asunto, $Cuerpo, PEDIDOS_CONTRATO_MARCO_REMITENTE_EMAIL, PEDIDOS_CONTRATO_MARCO_REMITENTE_NOMBRE, $correosEsp);

        //GENERO CONTENIDO DEL EMAIL ENG
        $Asunto = $auxiliar->traduce('Pedido de materiales con contrato marco generado automáticamente', "ENG") . '. ' . $auxiliar->traduce('Nº Pedido SGA', "ENG") . ': ' . $idPedidoSalida;
        $Cuerpo = $auxiliar->traduce('Pedido de materiales con contrato marco generado automáticamente', "ENG");
        $Cuerpo .= '<br/><br/> · ' . $auxiliar->traduce('Nº Pedido SGA', "ENG") . ': ' . $idPedidoSalida;
        $Cuerpo .= '<br/> · ' . $auxiliar->traduce('Nº Pedido SAP', "ENG") . ': ' . ($rowPedido->PEDIDO_SAP);
        $Cuerpo .= $enlacePedido;

        //PREPARO EL MAIL
        if (PEDIDOS_CONTRATO_MARCO_ENVIAR_EMAILS_DESTINATARIOS_REALES) {
            $arrCorreosEng = array_unique((array) $arrCorreosEng);
            $correosEng = implode(',', (array) $arrCorreosEng);
        } else {
            $correosEng = PEDIDOS_CONTRATO_MARCO_EMAIL_DESTINATARIO_TEST;
        }
        $auxiliar->enviarCorreoSistema($Asunto, $Cuerpo, PEDIDOS_CONTRATO_MARCO_REMITENTE_EMAIL, PEDIDOS_CONTRATO_MARCO_REMITENTE_NOMBRE, $correosEng);
    }

    /*
     * FUNCION PARA CREAR UN PEDIDO DE SALIDA
     */
    function CrearPedidoSalida($idCentroOrigen, $tipoPedido, $tipoPedidoSAP, $tipoTraslado, $idTipoBloqueo, $estado, $idOrdenTrabajo, $agrupadorOTs = '')
    {
        global $bd;
        global $administrador;

        //EXTRAIGO LOS DATOS DE LA OT
        if ($idOrdenTrabajo != NULL):
            $rowOrdenTrabajo = $bd->VerReg("ORDEN_TRABAJO", "ID_ORDEN_TRABAJO", $idOrdenTrabajo);
            $agrupadorOTs = $rowOrdenTrabajo->AGRUPADOR_OTS;
        endif;

        $sqlInsert = "INSERT INTO PEDIDO_SALIDA SET
                        TIPO_PEDIDO = '" . $tipoPedido . "'
                        , TIPO_PEDIDO_SAP = '" . ($tipoPedidoSAP == NULL ? "NULL" : $tipoPedidoSAP) . "'
                        , TIPO_TRASLADO = '" . ($tipoTraslado == NULL ? "NULL" : $tipoTraslado) . "'
                        , ID_TIPO_BLOQUEO = '" . ($idTipoBloqueo == NULL ? "NULL" : $idTipoBloqueo) . "'
                        , PREPARACION_TOTAL = " . ((($tipoPedido == 'Pendientes de Ordenes Trabajo') || ($tipoTraslado == 'Planificado Material Obligatorio')) ? 1 : 0) . "
                        , ID_CENTRO_ORIGEN = $idCentroOrigen
                        , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                        , FECHA_CREACION = '" . date("Y-m-d") . "'
                        , ESTADO = '" . $estado . "'
                        , ID_ORDEN_TRABAJO = " . ($idOrdenTrabajo == NULL ? "NULL" : $idOrdenTrabajo) . "
                        , ENVIADO_SO99 = '" . ((($idOrdenTrabajo == NULL) && ($agrupadorOTs == '')) ? 'No Aplica' : 'Sin enviar a SAP') . "'
                        , PENDIENTE_TRANSMITIR_A_SAP = " . ($idOrdenTrabajo == NULL ? 0 : 1) . "
                        , AGRUPADOR_OTS = '" . $agrupadorOTs . "'";
        $bd->ExecSQL($sqlInsert);
        $idPedido = $bd->IdAsignado();

        //BUSCO EL PEDIDO ACTUALIZADO
        $rowPedidoCreado = $bd->VerReg("PEDIDO_SALIDA", "ID_PEDIDO_SALIDA", $idPedido);

        // LOG MOVIMIENTOS
        $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Creación", "Pedido salida", $idPedido, "Creacion pedido salida", "PEDIDO_SALIDA", NULL, $rowPedidoCreado);

        return $idPedido;
    }

    /*
     * FUNCION PARA CREAR UNA LINEA DE UN PEDIDO DE SALIDA
     */
    function CrearPedidoSalidaLinea($idPedidoSalida, $idAlmacenOrigen, $idAlmacenDestino, $idMaterial, $idTipoBloqueo, $idOrdenTrabajoLinea, $cantidad)
    {
        global $bd;
        global $administrador;
        global $mat;
        global $auxiliar;
        global $incidencia_sistema;

        //VARIABLE CON EL ARRAY DE LINEAS A DEVOLVER
        $arrDevolver = array();

        //BUSCO EL PEDIDO DE SALIDA
        $rowPedidoSalida = $bd->VerReg("PEDIDO_SALIDA", "ID_PEDIDO_SALIDA", $idPedidoSalida);

        //COMPRUEBO SI HAY ALGUNA LINEA PREPARADA
        $numLineasPreparadas = $bd->NumRegsTabla("PEDIDO_SALIDA_LINEA", "ID_PEDIDO_SALIDA = $idPedidoSalida AND BAJA = 0 AND CANTIDAD <> CANTIDAD_PENDIENTE_SERVIR");

        //SI EL PEDIDO ESTA MARCADO COMO PREPARACION TOTAL Y SE HA PREPARADO ALGUNA CANTIDAD GENERO UN NUEVO PEDIDO
        if (($rowPedidoSalida->PREPARACION_TOTAL == 1) && ($numLineasPreparadas > 0)):
            //BUSCO LA ORDEN DE TRABAJO
            $rowOrdenTrabajoLinea = $bd->VerReg("ORDEN_TRABAJO_LINEA", "ID_ORDEN_TRABAJO_LINEA", $idOrdenTrabajoLinea);

            //BUSCO EL CENTRO
            $rowAlmacenSuministrador = $bd->VerReg("ALMACEN", "ID_ALMACEN", $idAlmacenOrigen);

            $tipoPedidoSAP = "ZTRB";

            $idPedidoSalida = $this->CrearPedidoSalida($rowAlmacenSuministrador->ID_CENTRO, 'Pendientes de Ordenes Trabajo', $tipoPedidoSAP, NULL, $idTipoBloqueo, 'Grabado', $rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO);

            //BUSCO EL PEDIDO DE SALIDA
            $rowPedidoSalida = $bd->VerReg("PEDIDO_SALIDA", "ID_PEDIDO_SALIDA", $idPedidoSalida);
        endif;

        //ME GUARDO EL PEDIDO UTILIZADO
        $arrDevolver['Pedido'] = $rowPedidoSalida->ID_PEDIDO_SALIDA;

        //VARIABLE OBSERVACIONES ALBARAN
        $observacionesAlbaran = "";

        //BUSCO LA ORDEN DE TRABAJO LINEA
        $rowOrdenTrabajoLinea = false;
        if ($idOrdenTrabajoLinea != NULL):
            $rowOrdenTrabajoLinea = $bd->VerReg("ORDEN_TRABAJO_LINEA", "ID_ORDEN_TRABAJO_LINEA", $idOrdenTrabajoLinea);

            //BUSCO LA ORDEN DE TRABAJO
            $rowOrdenTrabajo = $bd->VerReg("ORDEN_TRABAJO", "ID_ORDEN_TRABAJO", $rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO);

            $observacionesAlbaran = $auxiliar->traduce("Maquina", $administrador->ID_IDIOMA) . ": " . $rowOrdenTrabajo->DESC_MAQUINA . " - " . $rowOrdenTrabajo->MAQUINA_OT . " - " . $auxiliar->traduce("OT", $administrador->ID_IDIOMA) . ": " . $rowOrdenTrabajo->ORDEN_TRABAJO_SAP . " - " . $auxiliar->traduce("Pendiente", $administrador->ID_IDIOMA) . ": " . $rowOrdenTrabajoLinea->NUMERO_PENDIENTE;
        endif;

        //BUSCO EL ALMACEN DE ORIGEN
        $rowAlmacenOrigen = $bd->VerReg("ALMACEN", "ID_ALMACEN", $idAlmacenOrigen);

        //BUSCO EL CENTRO DE ORIGEN
        $rowCentroOrigen = $bd->VerReg("CENTRO", "ID_CENTRO", $rowAlmacenOrigen->ID_CENTRO);

        //BUSCO EL ALMACEN DE DESTINO
        $rowAlmacenDestino = $bd->VerReg("ALMACEN", "ID_ALMACEN", $idAlmacenDestino);

        //BUSCO EL CENTRO DE DESTINO
        $rowCentroDestino = $bd->VerReg("CENTRO", "ID_CENTRO", $rowAlmacenDestino->ID_CENTRO);

        //BUSCO LA SOCIEDAD DE DESTINO
        $rowSociedadDestino = $bd->VerReg("SOCIEDAD", "ID_SOCIEDAD", $rowCentroDestino->ID_SOCIEDAD);

        //BUSCO EL MATERIAL
        $rowMat = $bd->VerReg("MATERIAL", "ID_MATERIAL", $idMaterial);

        //COMPRUEBO SI EXISTE UNA LINEA DE PEDIDO DE SALIDA A LA QUE ASIGNAR LA NUEVA CANTIDAD
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowPedidoSalidaLinea             = $bd->VerRegRest("PEDIDO_SALIDA_LINEA", "ID_PEDIDO_SALIDA = " . $idPedidoSalida . " AND ID_CENTRO_DESTINO = " . $rowAlmacenDestino->ID_CENTRO . " AND ID_ALMACEN_DESTINO = " . $rowAlmacenDestino->ID_ALMACEN . " AND ID_MATERIAL = $rowMat->ID_MATERIAL" . ($idOrdenTrabajoLinea == NULL ? '' : " AND ID_ORDEN_TRABAJO_LINEA = $idOrdenTrabajoLinea") . " AND BAJA = 0", "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);

        //BUSCO EL MATERIAL ALMACEN CORRESPODIENTE
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowMatAlm                        = $bd->VerRegRest("MATERIAL_ALMACEN", "ID_MATERIAL = $rowMat->ID_MATERIAL AND ID_ALMACEN = $rowAlmacenDestino->ID_ALMACEN", "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);
        if ($rowMatAlm == false):
            //GENERAMOS UNA COPIA DEL MATERIAL_ALMACEN ENCONTRADO
            $idMatAlm = $mat->ClonarMaterialAlmacen($rowMat->ID_MATERIAL, $rowAlmacenDestino->ID_ALMACEN, "Creacion Pedido Salida");

            if ($idMatAlm != false):
                //RECUPERO EL OBJETO CREADO
                $rowMatAlm = $bd->VerReg("MATERIAL_ALMACEN", "ID_MATERIAL_ALMACEN", $idMatAlm);
            endif;
        endif;

        //EL TIPO DE PEDIDO ES ZTRB O ZTRD EN FUNCION DE LA SOCIEDAD
        if ($rowCentroOrigen->ID_SOCIEDAD == $rowCentroDestino->ID_SOCIEDAD): //SI LA SOCIEDAD ES IGUAL, EL PEDIDO ES ZTRB
            $tipoPedidoSap = "ZTRB";
        else: //SI LA SOCIEDAD ES DIFERENTE, EL PEDIDO ES ZTRD
            $tipoPedidoSap = "ZTRD";
        endif;
        //FIN EL TIPO DE PEDIDO ES ZTRB O ZTRD EN FUNCION DE LA SOCIEDAD

        //BUSCO EL MAXIMO NUMERO DE LINEA
        $UltimoNumeroLinea       = 0;
        $sqlUltimoNumeroLinea    = "SELECT MAX(CAST(LINEA_PEDIDO_SAP AS UNSIGNED)) AS NUMERO_LINEA FROM PEDIDO_SALIDA_LINEA WHERE ID_PEDIDO_SALIDA = " . $idPedidoSalida;
        $resultUltimoNumeroLinea = $bd->ExecSQL($sqlUltimoNumeroLinea);
        if ($resultUltimoNumeroLinea != false):
            $rowUltimoNumeroLinea = $bd->SigReg($resultUltimoNumeroLinea);
            if ($rowUltimoNumeroLinea->NUMERO_LINEA != NULL):
                $UltimoNumeroLinea = $rowUltimoNumeroLinea->NUMERO_LINEA;
            endif;
        endif;
        $SiguienteNumeroLinea = $UltimoNumeroLinea + 10;

        //DETERMINO SI ES NECESARIO HACER INSERT O UPDATE
        if (
            ($rowPedidoSalidaLinea == false) ||
            ($rowMatAlm->TIPO_LOTE == 'serie') ||
            (($this->RelevanteParaEntregaEntrante($tipoPedidoSap) == 1) && ($rowSociedadDestino->GESTION_TRANSPORTE == 1))
        ):
            $operacion = "insert";
        else:
            $operacion = "update";
        endif;

        //ACCIONES EN FUNCION DEL TIPO DE OPERACION A REALIZAR
        if ($operacion == "update"):
            $sqlUpdate = "UPDATE PEDIDO_SALIDA_LINEA SET
                                CANTIDAD = CANTIDAD + $cantidad
                                , CANTIDAD_PENDIENTE_SERVIR = CANTIDAD_PENDIENTE_SERVIR + $cantidad
                                , FECHA_MODIFICACION = '" . date("Y-m-d") . "'
                                WHERE ID_PEDIDO_SALIDA_LINEA = " . $rowPedidoSalidaLinea->ID_PEDIDO_SALIDA_LINEA;
            $bd->ExecSQL($sqlUpdate);

            //BUSCAMOS LA LINEA DEL PEDIDO
            $rowPedidoSalidaLineaActualizada = $bd->VerReg("PEDIDO_SALIDA_LINEA", "ID_PEDIDO_SALIDA_LINEA", $rowPedidoSalidaLinea->ID_PEDIDO_SALIDA_LINEA);

            // LOG MOVIMIENTOS
            $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Linea Pedido Salida", $rowPedidoSalidaLinea->ID_PEDIDO_SALIDA_LINEA, "Modificacion linea pedido salida", "PEDIDO_SALIDA_LINEA", $rowPedidoSalidaLinea, $rowPedidoSalidaLineaActualizada);

            //INCLUYO LA LINEA MODIFICADA AL ARRAY
            $arrDevolver['Lineas'][] = $rowPedidoSalidaLinea->ID_PEDIDO_SALIDA_LINEA;
        elseif ($operacion == "insert"):
            if ($rowMatAlm->TIPO_LOTE == 'serie'):
                for ($i = 0; $i < $cantidad; $i++):
                    //INSERTO UNA NUEVA LÍNEA EN EL PEDIDO POR CADA UNIDAD
                    $sqlInsert = "INSERT INTO PEDIDO_SALIDA_LINEA SET
                                        ID_PEDIDO_SALIDA = " . $idPedidoSalida . "
                                        , LINEA_PEDIDO_SAP = '" . str_pad( (string)$SiguienteNumeroLinea, 5, "0", STR_PAD_LEFT) . "'
                                        , RELEVANTE_ENTREGA_ENTRANTE = " . ((($this->RelevanteParaEntregaEntrante($tipoPedidoSap) == 1) && ($rowSociedadDestino->GESTION_TRANSPORTE == 1)) ? 1 : 0) . "
                                        , ID_ALMACEN_ORIGEN = $rowAlmacenOrigen->ID_ALMACEN
                                        , ID_MATERIAL = $rowMat->ID_MATERIAL
                                        , ID_TIPO_BLOQUEO = " . ($idTipoBloqueo == NULL ? 'NULL' : $idTipoBloqueo) . "
                                        , CANTIDAD = 1
                                        , CANTIDAD_PENDIENTE_SERVIR = 1
                                        , ID_UNIDAD = $rowMat->ID_UNIDAD_MEDIDA
                                        , FECHA_MODIFICACION = '" . date("Y-m-d") . "'
                                        , FECHA_ENTREGA = '" . ($rowOrdenTrabajoLinea == false ? date("Y-m-d") : $rowOrdenTrabajoLinea->FECHA_PLANIFICADA) . "'
                                        , ID_CENTRO_DESTINO = $rowCentroDestino->ID_CENTRO
                                        , ID_ALMACEN_DESTINO = $rowAlmacenDestino->ID_ALMACEN
                                        , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                        , ID_ORDEN_TRABAJO_LINEA = " . ($idOrdenTrabajoLinea == NULL ? 'NULL' : $idOrdenTrabajoLinea) . "
                                        , OBSERVACIONES_ALBARAN = '" . $bd->escapeCondicional($observacionesAlbaran) . "'";
                    $bd->ExecSQL($sqlInsert);
                    $idPedidoSalidaLinea = $bd->IdAsignado();

                    //INCLUYO LA LINEA CREADA AL ARRAY
                    $arrDevolver['Lineas'][] = $idPedidoSalidaLinea;

                    //BUSCAMOS LA LINEA DEL PEDIDO
                    $rowPedidoSalidaLineaCreada = $bd->VerReg("PEDIDO_SALIDA_LINEA", "ID_PEDIDO_SALIDA_LINEA", $idPedidoSalidaLinea);

                    // LOG MOVIMIENTOS
                    $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Creación", "Linea Pedido Salida", $idPedidoSalidaLinea, "Creacion linea pedido salida", "PEDIDO_SALIDA_LINEA", NULL, $rowPedidoSalidaLineaCreada);

                    //ACTUALIZO EL SIGUIENTE NUMERO DE LINEA
                    $SiguienteNumeroLinea = $SiguienteNumeroLinea + 10;
                endfor;
            else:
                //INSERTO UNA NUEVA LÍNEA EN EL PEDIDO POR LA CANTIDAD TOTAL
                $sqlInsert = "INSERT INTO PEDIDO_SALIDA_LINEA SET
                                    ID_PEDIDO_SALIDA = " . $idPedidoSalida . "
                                    , LINEA_PEDIDO_SAP = '" . str_pad( (string)$SiguienteNumeroLinea, 5, "0", STR_PAD_LEFT) . "'
                                    , RELEVANTE_ENTREGA_ENTRANTE = " . ((($this->RelevanteParaEntregaEntrante($tipoPedidoSap) == 1) && ($rowSociedadDestino->GESTION_TRANSPORTE == 1)) ? 1 : 0) . "
                                    , ID_ALMACEN_ORIGEN = $rowAlmacenOrigen->ID_ALMACEN
                                    , ID_MATERIAL = $rowMat->ID_MATERIAL
                                    , ID_TIPO_BLOQUEO = " . ($idTipoBloqueo == NULL ? 'NULL' : $idTipoBloqueo) . "
                                    , CANTIDAD = $cantidad
                                    , CANTIDAD_PENDIENTE_SERVIR = $cantidad
                                    , ID_UNIDAD = $rowMat->ID_UNIDAD_MEDIDA
                                    , FECHA_MODIFICACION = '" . date("Y-m-d") . "'
                                    , FECHA_ENTREGA = '" . ($rowOrdenTrabajoLinea == false ? date("Y-m-d") : $rowOrdenTrabajoLinea->FECHA_PLANIFICADA) . "'
                                    , ID_CENTRO_DESTINO = $rowCentroDestino->ID_CENTRO
                                    , ID_ALMACEN_DESTINO = $rowAlmacenDestino->ID_ALMACEN
                                    , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                    , ID_ORDEN_TRABAJO_LINEA = " . ($idOrdenTrabajoLinea == NULL ? 'NULL' : $idOrdenTrabajoLinea) . "
                                    , OBSERVACIONES_ALBARAN = '" . $bd->escapeCondicional($observacionesAlbaran) . "'";
                $bd->ExecSQL($sqlInsert);
                $idPedidoSalidaLinea = $bd->IdAsignado();

                //INCLUYO LA LINEA CREADA AL ARRAY
                $arrDevolver['Lineas'][] = $idPedidoSalidaLinea;

                //BUSCAMOS LA LINEA DEL PEDIDO
                $rowPedidoSalidaLineaCreada = $bd->VerReg("PEDIDO_SALIDA_LINEA", "ID_PEDIDO_SALIDA_LINEA", $idPedidoSalidaLinea);

                // LOG MOVIMIENTOS
                $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Creación", "Linea Pedido Salida", $idPedidoSalidaLinea, "Creacion linea pedido salida", "PEDIDO_SALIDA_LINEA", NULL, $rowPedidoSalidaLineaCreada);
            endif;
        endif;

        //CALCULO EL TIPO PEDIDO SAP
        $tipoPedidoSap           = "ZTRB";
        $sqlSociedadesDestino    = "SELECT DISTINCT C.ID_SOCIEDAD
                                         FROM CENTRO C
                                         INNER JOIN PEDIDO_SALIDA_LINEA PSL ON PSL.ID_CENTRO_DESTINO = C.ID_CENTRO
                                         WHERE PSL.ID_PEDIDO_SALIDA = $idPedidoSalida AND PSL.BAJA = 0";
        $resultSociedadesDestino = $bd->ExecSQL($sqlSociedadesDestino);
        while ($rowSociedadDestino = $bd->SigReg($resultSociedadesDestino)):
            if ($rowSociedadDestino->ID_SOCIEDAD != $rowCentroOrigen->ID_SOCIEDAD):
                $tipoPedidoSap = "ZTRD";
                break;
            endif;
        endwhile;

        //ACTUALIZO EL TIPO PEDIDO SAP DEL PEDIDO DE SALIDA
        $sqlUpdate = "UPDATE PEDIDO_SALIDA SET
                          TIPO_PEDIDO_SAP = '" . $tipoPedidoSap . "'
                          , ESTADO = " . ($rowPedidoSalida->ESTADO == 'Finalizado' ? "'En Entrega'" : "ESTADO") . "
                          WHERE ID_PEDIDO_SALIDA = $idPedidoSalida";
        $bd->ExecSQL($sqlUpdate);

        //CALCULO LA FECHA MINIMA DE LAS LINEAS DE PEDIDO DE SALIDA
        $sqlFechaMinima    = "SELECT MIN(FECHA_ENTREGA) AS FECHA_ENTREGA
                                   FROM PEDIDO_SALIDA_LINEA PSL
                                   WHERE PSL.ID_PEDIDO_SALIDA = $idPedidoSalida AND PSL.BAJA = 0";
        $resultFechaMinima = $bd->ExecSQL($sqlFechaMinima);
        if (($resultFechaMinima != false) && ($bd->NumRegs($resultFechaMinima) > 0)):
            $rowFechaMinimo = $bd->SigReg($resultFechaMinima);

            //SI EL PEDIDO ES DE TIPO Pendientes de Ordenes Trabajo, LES PONGO A TODAS LAS FECHAS DE ENTREGA LA FECHA MINIMA
            if ($rowPedidoSalida->TIPO_PEDIDO == 'Pendientes de Ordenes Trabajo'):
                $sqlUpdate = "UPDATE PEDIDO_SALIDA_LINEA SET
                                  FECHA_ENTREGA = '" . $rowFechaMinimo->FECHA_ENTREGA . "'
                                  WHERE ID_PEDIDO_SALIDA = $idPedidoSalida AND BAJA = 0";
                $bd->ExecSQL($sqlUpdate);
            endif;
        endif;

        //CALCULAMOS SHIPPING DATE (POR AHORA SOLO PEDIDOS PENDIENTES)
        if ($rowPedidoSalida->TIPO_PEDIDO == 'Pendientes de Ordenes Trabajo'):
            //ACTUALIZADO EL SHIPPING DATE
            Planificado::actualizarShippingDatePedidoSalida($idPedidoSalida, $rowAlmacenDestino->NUMERO_CICLOS_ANTELACION);
        endif;

        return $arrDevolver;
    }

    /*
     * FUNCION PARA CREAR UNA LINEA DE UN PEDIDO DE SALIDA DE PLANIFICADOS
     */
    function CrearPedidoSalidaLineaPlanificados($idPedidoSalida, $idAlmacenOrigen, $idAlmacenDestino, $idMaterial, $idTipoBloqueo, $idOrdenTrabajoLinea, $cantidadLineaOT, $cantidadLineaPedido, $fechaPlanificada = NULL)
    {
        global $bd;
        global $auxiliar;
        global $administrador;
        global $mat;
        global $reserva;

        //VARIABLE CON EL ARRAY DE LINEAS A DEVOLVER
        $arrDevolver = array();

        //VARIABLE PARA SABER LAS LINEAS DE PEDIDO DE SALIDA CREADAS/MODIFICADAS
        $arrLineasPedidoSalidaCreadasModificadas = array();

        //VARIABLE PARA DEVOLVER LOS ERRORES CORRESPONDIENTES
        $errorCubrirDemanda = "";

        //BUSCO EL PEDIDO DE SALIDA
        $rowPedidoSalida = $bd->VerReg("PEDIDO_SALIDA", "ID_PEDIDO_SALIDA", $idPedidoSalida);

        //BUSCO EL ALMACEN DE ORIGEN
        $rowAlmacenOrigen = $bd->VerReg("ALMACEN", "ID_ALMACEN", $idAlmacenOrigen);

        //BUSCO EL CENTRO DE ORIGEN
        $rowCentroOrigen = $bd->VerReg("CENTRO", "ID_CENTRO", $rowAlmacenOrigen->ID_CENTRO);

        //BUSCO EL ALMACEN DE DESTINO
        $rowAlmacenDestino = $bd->VerReg("ALMACEN", "ID_ALMACEN", $idAlmacenDestino);

        //BUSCO EL CENTRO DE DESTINO
        $rowCentroDestino = $bd->VerReg("CENTRO", "ID_CENTRO", $rowAlmacenDestino->ID_CENTRO);

        //BUSCO LA SOCIEDAD DE DESTINO
        $rowSociedadDestino = $bd->VerReg("SOCIEDAD", "ID_SOCIEDAD", $rowCentroDestino->ID_SOCIEDAD);

        //BUSCO EL MATERIAL
        $rowMat = $bd->VerReg("MATERIAL", "ID_MATERIAL", $idMaterial);

        //BUSCO EL MATERIAL ALMACEN CORRESPODIENTE
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowMatAlm                        = $bd->VerRegRest("MATERIAL_ALMACEN", "ID_MATERIAL = $rowMat->ID_MATERIAL AND ID_ALMACEN = $rowAlmacenDestino->ID_ALMACEN", "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);
        if ($rowMatAlm == false):
            //GENERAMOS UNA COPIA DEL MATERIAL_ALMACEN ENCONTRADO
            $idMatAlm = $mat->ClonarMaterialAlmacen($rowMat->ID_MATERIAL, $rowAlmacenDestino->ID_ALMACEN, "Creacion Pedido Salida");

            if ($idMatAlm != false):
                //RECUPERO EL OBJETO CREADO
                $rowMatAlm = $bd->VerReg("MATERIAL_ALMACEN", "ID_MATERIAL_ALMACEN", $idMatAlm);
            endif;
        endif;

        //BUSCO LA LINEA DE LA ORDEN DE TRABAJO
        $rowOrdenTrabajoLinea = $bd->VerReg("ORDEN_TRABAJO_LINEA", "ID_ORDEN_TRABAJO_LINEA", $idOrdenTrabajoLinea);

        //BUSCO LA ORDEN DE TRABAJO
        $rowOrdenTrabajo = $bd->VerReg("ORDEN_TRABAJO", "ID_ORDEN_TRABAJO", $rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO);

        //VARIABLE PARA SABER SI HACE CASO AL NUMERO DE EQUIPOS
        $materialHaceCasoNumeroEquipos = 'Si';

        //SI EL MATERIAL ES INDIVISIBLE Y NO APLICA EL NUMERO DE EQUIPOS PONGO EL NUMERO DE EQUIPO A 1
        if (($rowMat->DIVISIBILIDAD == 'No') && ($rowMat->GESTION_NUMERO_EQUIPOS_OT == 'No')):
            $rowOrdenTrabajo->NUMERO_EQUIPO_ASIGNADO = 1;
            $materialHaceCasoNumeroEquipos           = 'No';
        endif;

        //ESTABLEZCO LA CANTIDAD CANCELADA POR USUARIO
        if (($rowOrdenTrabajoLinea->CANTIDAD - ($rowOrdenTrabajoLinea->CANTIDAD_PEDIDA + $rowOrdenTrabajoLinea->CANTIDAD_RESERVADA + $cantidadLineaOT)) > EPSILON_SISTEMA): //CANTIDAD NO ENVIADA POR DECISION DEL USUARIO
            $sqlUpdate = "UPDATE ORDEN_TRABAJO_LINEA SET 
                              CANTIDAD_CANCELADA_POR_USUARIO = " . ($rowOrdenTrabajoLinea->CANTIDAD - ($rowOrdenTrabajoLinea->CANTIDAD_PEDIDA + $rowOrdenTrabajoLinea->CANTIDAD_RESERVADA + $cantidadLineaOT)) . " 
                              WHERE ID_ORDEN_TRABAJO_LINEA = $rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO_LINEA";
            $bd->ExecSQL($sqlUpdate);
        else: //EL USUARIO HA DECIDIDO ENVIAR LO NECESARIO
            $sqlUpdate = "UPDATE ORDEN_TRABAJO_LINEA SET 
                              CANTIDAD_CANCELADA_POR_USUARIO = 0 
                              WHERE ID_ORDEN_TRABAJO_LINEA = $rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO_LINEA";
            $bd->ExecSQL($sqlUpdate);
        endif;

        //INICIALIZO LA CANTIDAD PENDIENTE DE ASOCIAR
        $cantidadPentienteAsociar = $cantidadLineaOT;

        //BUSCO LAS LINEAS DE PEDIDO QUE PUEDA REUTIZAR
        if ($rowPedidoSalida->TIPO_TRASLADO == 'Planificado Material Obligatorio'): //BUSCO LA LINEA DE PEDIDO RELACIONADA CON ESTA LINEA DE ORDEN DE TRABAJO
            $sqlLineas = "SELECT * 
                             FROM PEDIDO_SALIDA_LINEA PSL 
                             WHERE PSL.ID_PEDIDO_SALIDA = $rowPedidoSalida->ID_PEDIDO_SALIDA AND ID_ALMACEN_ORIGEN = $rowAlmacenOrigen->ID_ALMACEN AND ID_ALMACEN_DESTINO = $rowAlmacenDestino->ID_ALMACEN AND ID_MATERIAL = $rowMat->ID_MATERIAL
                                AND PSL.CANTIDAD = PSL.CANTIDAD_PENDIENTE_SERVIR AND INDICADOR_BORRADO IS NULL AND BAJA = 0 AND PSL.ID_ORDEN_TRABAJO_LINEA = $rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO_LINEA";
        else: //BUSCO LAS LINEAS DE PEDIDO QUE ME VALGAN PARA ASOCIAR ESTA LINEA DE ORDEN DE TRABAJO (CON FECHA PLANIFICADA IGUAL O INFERIOR A LA DE LA LINEA O SIN EMPEZAR LA PREPARACION)
            $sqlLineas = "SELECT * 
                             FROM PEDIDO_SALIDA_LINEA PSL 
                             WHERE PSL.ID_PEDIDO_SALIDA = $rowPedidoSalida->ID_PEDIDO_SALIDA AND ID_ALMACEN_ORIGEN = $rowAlmacenOrigen->ID_ALMACEN AND ID_ALMACEN_DESTINO = $rowAlmacenDestino->ID_ALMACEN AND ID_MATERIAL = $rowMat->ID_MATERIAL
                                AND PSL.CANTIDAD = PSL.CANTIDAD_PENDIENTE_SERVIR AND INDICADOR_BORRADO IS NULL AND BAJA = 0";
        endif;

        $resultLineas = $bd->ExecSQL($sqlLineas);
        while ($rowLinea = $bd->SigReg($resultLineas)):
            //SI LA LINEA ESTA PENDIENTE DE BORRADO ME LA SALTO
            $GLOBALS["NotificaErrorPorEmail"] = "No";
            $rowAccionPendienteRealizar       = $bd->VerRegRest("ACCION_PENDIENTE_REALIZAR", "ESTADO = 'Creada' AND ACCION = 'Borrar' AND TABLA_OBJETO = 'PEDIDO_SALIDA_LINEA' AND ID_OBJETO = $rowLinea->ID_PEDIDO_SALIDA_LINEA AND BAJA = 0", "No");
            unset($GLOBALS["NotificaErrorPorEmail"]);
            if ($rowAccionPendienteRealizar != false):
                continue;
            endif;

            //CALCULO LA CANTIDAD QUE HA CUBIERTO PARA OT's
            $cantidadCubiertaPedidoLinea = 0;
            if ($rowPedidoSalida->TIPO_TRASLADO == 'Planificado Material Indivisible'): //MATERIAL PLANIFICADO INDIVISIBLE
                //SUMO LO ASIGNADO A OT´s DE SU MISMO NUMERO DE EQUIPO
                if ($materialHaceCasoNumeroEquipos == 'Si'):
                    $sqlCantidadCubiertaPedidoLinea = "SELECT SUM(CANTIDAD_CUBIERTA_POR_PEDIDO_SALIDA_LINEA) AS CANTIDAD_TOTAL
                                                          FROM ORDEN_TRABAJO_LINEA_CUBIERTA_PEDIDO OTLCP
                                                          INNER JOIN ORDEN_TRABAJO OT ON OT.ID_ORDEN_TRABAJO = OTLCP.ID_ORDEN_TRABAJO
                                                          WHERE OTLCP.ID_PEDIDO_SALIDA_LINEA = $rowLinea->ID_PEDIDO_SALIDA_LINEA AND OTLCP.BAJA = 0 AND OT.NUMERO_EQUIPO_ASIGNADO = $rowOrdenTrabajo->NUMERO_EQUIPO_ASIGNADO";
                elseif ($materialHaceCasoNumeroEquipos == 'No'):
                    $sqlCantidadCubiertaPedidoLinea = "SELECT SUM(CANTIDAD_CUBIERTA_POR_PEDIDO_SALIDA_LINEA) AS CANTIDAD_TOTAL
                                                          FROM ORDEN_TRABAJO_LINEA_CUBIERTA_PEDIDO OTLCP
                                                          INNER JOIN ORDEN_TRABAJO OT ON OT.ID_ORDEN_TRABAJO = OTLCP.ID_ORDEN_TRABAJO
                                                          WHERE OTLCP.ID_PEDIDO_SALIDA_LINEA = $rowLinea->ID_PEDIDO_SALIDA_LINEA AND OTLCP.BAJA = 0 AND 1 = $rowOrdenTrabajo->NUMERO_EQUIPO_ASIGNADO";
                endif;
                $resultCantidadCubiertaPedidoLinea = $bd->ExecSQL($sqlCantidadCubiertaPedidoLinea);
                if (($resultCantidadCubiertaPedidoLinea != false) || ($bd->NumRegsTabla($resultCantidadCubiertaPedidoLinea) == 1)):
                    $rowCantidadCubiertaPedidoLinea = $bd->SigReg($resultCantidadCubiertaPedidoLinea);
                    $cantidadCubiertaPedidoLinea    = $rowCantidadCubiertaPedidoLinea->CANTIDAD_TOTAL;
                endif;

                //SI HACE CASO AL NUMERO DE EQUIPOS REVISO LO ASIGNADO A OTROS EQUIPOS
                if ($materialHaceCasoNumeroEquipos == 'Si'):
                    //SUMO LO ASIGNADO A OTRAS OT´s DE OTRO NUMERO DE EQUIPO
                    $sqlCantidadCubiertaPedidoLinea    = "SELECT SUM(CANTIDAD_CUBIERTA_POR_PEDIDO_SALIDA_LINEA) AS CANTIDAD_TOTAL
                                                              FROM ORDEN_TRABAJO_LINEA_CUBIERTA_PEDIDO OTLCP
                                                              INNER JOIN ORDEN_TRABAJO OT ON OT.ID_ORDEN_TRABAJO = OTLCP.ID_ORDEN_TRABAJO
                                                              WHERE OTLCP.ID_PEDIDO_SALIDA_LINEA = $rowLinea->ID_PEDIDO_SALIDA_LINEA AND OTLCP.BAJA = 0 AND OT.NUMERO_EQUIPO_ASIGNADO <> $rowOrdenTrabajo->NUMERO_EQUIPO_ASIGNADO
                                                              GROUP BY OT.NUMERO_EQUIPO_ASIGNADO";
                    $resultCantidadCubiertaPedidoLinea = $bd->ExecSQL($sqlCantidadCubiertaPedidoLinea);
                    while ($rowCantidadCubiertaPedidoLinea = $bd->SigReg($resultCantidadCubiertaPedidoLinea)):
                        //CALCULO EL FACTOR DE CONVERSION
                        $factorConversion = 1;
                        if ($rowMat->ID_UNIDAD_MEDIDA != $rowMat->ID_UNIDAD_COMPRA && $rowMat->DENOMINADOR_CONVERSION != 0):
                            $factorConversion = $rowMat->NUMERADOR_CONVERSION / $rowMat->DENOMINADOR_CONVERSION;
                        endif;

                        //BUSCO LA CANTIDAD DE COMPRA A ENVIAR EN UNIDADES REDONDEADAS A ENTERO SUPERIORMENTE
                        if (($rowMat->ID_UNIDAD_MEDIDA != $rowMat->ID_UNIDAD_COMPRA) && ($rowMat->DENOMINADOR_CONVERSION != 0)):
                            // SI ES DIVISIBLE, NO SE REDONDEA A UN NÚMERO ENTERO
                            if ($rowMat->DIVISIBILIDAD == 'Si'):
                                $cantidadCompraCubierta = number_format((float)$rowCantidadCubiertaPedidoLinea->CANTIDAD_TOTAL / ($rowMat->NUMERADOR_CONVERSION / $rowMat->DENOMINADOR_CONVERSION), 3);

                                //CANTIDAD BASE REDONDEADA ES LA MISMA QUE LA BASE (NO SE REDONDEA) EN MATERIALES DIVISIBLES
                                $cantidadCubierta = $rowCantidadCubiertaPedidoLinea->CANTIDAD_TOTAL;
                            else:
                                $cantidadCompraCubierta = number_format(ceil($rowCantidadCubiertaPedidoLinea->CANTIDAD_TOTAL / ($rowMat->NUMERADOR_CONVERSION / $rowMat->DENOMINADOR_CONVERSION)), 3);
                                //CANTIDAD BASE TRANSFORMADA EN FUNCIÓN A LA DE COMPRA REDONDEADA A ENTERO SUPERIORMENTE
                                $cantidadCubierta = $cantidadCompraCubierta * $factorConversion;
                            endif;
                        else:
                            $cantidadCompraCubierta = ceil((float)$rowCantidadCubiertaPedidoLinea->CANTIDAD_TOTAL);
                            //CANTIDAD BASE TRANSFORMADA EN FUNCIÓN A LA DE COMPRA REDONDEADA A ENTERO SUPERIORMENTE
                            $cantidadCubierta = $cantidadCompraCubierta * $factorConversion;
                        endif;

                        //INCREMENTO LA CANTIDAD CUBIERTA
                        $cantidadCubiertaPedidoLinea = $cantidadCubiertaPedidoLinea + $cantidadCubierta;
                    endwhile;
                endif;
                //FIN SI HACE CASO AL NUMERO DE EQUIPOS REVISO LO ASIGNADO A OTROS EQUIPOS

                //BUSCO SI EXISTE UN REGISTRO DONDE AÑADIR LA CANTIDAD
                $GLOBALS["NotificaErrorPorEmail"]   = "No";
                $rowOrdenTrabajoLineaCubiertaPedido = $bd->VerRegRest("ORDEN_TRABAJO_LINEA_CUBIERTA_PEDIDO", "ID_ORDEN_TRABAJO = $rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO AND ID_ORDEN_TRABAJO_LINEA = $rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO_LINEA AND FECHA_PLANIFICADA = '" . ($fechaPlanificada == NULL ? $rowOrdenTrabajoLinea->FECHA_PLANIFICADA : $fechaPlanificada) . "' AND ID_PEDIDO_SALIDA = $rowLinea->ID_PEDIDO_SALIDA AND ID_PEDIDO_SALIDA_LINEA = $rowLinea->ID_PEDIDO_SALIDA_LINEA AND CANTIDAD_PENDIENTE_CUBRIR_POR_PEDIDO_SALIDA_LINEA = 0 AND CANTIDAD_ENTREGADA_EN_DESTINO = 0 AND BAJA = 0", "No");
                unset($GLOBALS["NotificaErrorPorEmail"]);

                //SOLO REALIZO ACCIONES SI LA CANTIDAD PENDIENTE DE ASOCIAR ES DISTINTA DE CERO
                if ($cantidadPentienteAsociar != 0):
                    //SI NO EXISTE NINGUNA ASOCIACION PARA LA LINEA DE OT CON ESTA LINEA DE PEDIDO
                    if ($rowOrdenTrabajoLineaCubiertaPedido == false):
                        //BUSCO LA CANTIDAD DE LAS OTS YA TRATADAS
                        $cantidadCubiertaPedidoLineaTratada     = 0;
                        $sqlLineasCubiertaPedidoLineaTratada    = "SELECT DISTINCT OTL.ID_ORDEN_TRABAJO_LINEA, OTLCP.CANTIDAD_CUBIERTA_POR_PEDIDO_SALIDA_LINEA
                                                                        FROM ORDEN_TRABAJO OT
                                                                        INNER JOIN ORDEN_TRABAJO_LINEA OTL ON OTL.ID_ORDEN_TRABAJO = OT.ID_ORDEN_TRABAJO
                                                                        INNER JOIN TECNOLOGIA_GENERICA TG ON TG.ID_TECNOLOGIA_GENERICA = OT.ID_TECNOLOGIA
                                                                        INNER JOIN INSTALACION I ON I.ID_INSTALACION = OT.ID_INSTALACION
                                                                        INNER JOIN CENTRO C ON C.ID_CENTRO = OT.ID_CENTRO
                                                                        INNER JOIN ORDEN_TRABAJO_LINEA_CUBIERTA_PEDIDO OTLCP ON OTLCP.ID_ORDEN_TRABAJO_LINEA = OTL.ID_ORDEN_TRABAJO_LINEA
                                                                        WHERE OTLCP.ID_PEDIDO_SALIDA_LINEA = $rowLinea->ID_PEDIDO_SALIDA_LINEA AND OTLCP.BAJA = 0 AND OTLCP.FECHA_PLANIFICADA <= '" . ($fechaPlanificada == NULL ? $rowOrdenTrabajoLinea->FECHA_PLANIFICADA : $fechaPlanificada) . "'
                                                                        ORDER BY OTLCP.FECHA_PLANIFICADA ASC, OT.ORDEN_TRABAJO_SAP ASC, OTL.LINEA_ORDEN_TRABAJO_SAP ASC";
                        $resultLineasCubiertaPedidoLineaTratada = $bd->ExecSQL($sqlLineasCubiertaPedidoLineaTratada);
                        while ($rowLineaCubiertaPedidoLineaTratada = $bd->SigReg($resultLineasCubiertaPedidoLineaTratada)):
                            if ($rowLineaCubiertaPedidoLineaTratada->ID_ORDEN_TRABAJO_LINEA == $rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO_LINEA):
                                break;
                            else:
                                $cantidadCubiertaPedidoLineaTratada += $rowLineaCubiertaPedidoLineaTratada->CANTIDAD_CUBIERTA_POR_PEDIDO_SALIDA_LINEA;
                            endif;
                        endwhile;

                        //CALCULO LA CANTIDAD A ASOCIAR
                        $cantidadAsociar = min($cantidadPentienteAsociar, $rowLinea->CANTIDAD - $cantidadCubiertaPedidoLineaTratada);
                        if (abs( (float)$cantidadAsociar) < EPSILON_SISTEMA):
                            $cantidadAsociar = 0;
                        endif;

                        if (($rowLinea->CANTIDAD == $rowLinea->CANTIDAD_PENDIENTE_SERVIR) && ($rowLinea->FECHA_ENTREGA >= $rowOrdenTrabajoLinea->FECHA_PLANIFICADA) && ($rowMatAlm->TIPO_LOTE != 'serie')):
                            //GRABO LA CANTIDAD ASOCIADA
                            $sqlInsert = "INSERT INTO ORDEN_TRABAJO_LINEA_CUBIERTA_PEDIDO SET 
                                            ID_ORDEN_TRABAJO = $rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO 
                                            , ID_ORDEN_TRABAJO_LINEA = $rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO_LINEA 
                                            , FECHA_PLANIFICADA = '" . ($fechaPlanificada == NULL ? $rowOrdenTrabajoLinea->FECHA_PLANIFICADA : $fechaPlanificada) . "' 
                                            , ID_PEDIDO_SALIDA = $rowLinea->ID_PEDIDO_SALIDA 
                                            , ID_PEDIDO_SALIDA_LINEA = $rowLinea->ID_PEDIDO_SALIDA_LINEA 
                                            , CANTIDAD_POSICION = $rowOrdenTrabajoLinea->CANTIDAD 
                                            , CANTIDAD_CUBIERTA_POR_PEDIDO_SALIDA_LINEA = $cantidadPentienteAsociar 
                                            , CANTIDAD_PENDIENTE_CUBRIR_POR_PEDIDO_SALIDA_LINEA = 0 
                                            , CANTIDAD_ENTREGADA_EN_DESTINO = 0";
                            $bd->ExecSQL($sqlInsert);

                            $cantidadLineaPedidoRedondeada = $cantidadCubiertaPedidoLineaTratada + $cantidadPentienteAsociar;

                            //CALCULO EL FACTOR DE CONVERSION
                            $factorConversion = 1;
                            if ($rowMat->ID_UNIDAD_MEDIDA != $rowMat->ID_UNIDAD_COMPRA && $rowMat->DENOMINADOR_CONVERSION != 0):
                                $factorConversion = $rowMat->NUMERADOR_CONVERSION / $rowMat->DENOMINADOR_CONVERSION;
                            endif;

                            //CALCULO LA CANTIDAD DE COMPRA
                            if (($rowMat->ID_UNIDAD_MEDIDA != $rowMat->ID_UNIDAD_COMPRA) && ($rowMat->DENOMINADOR_CONVERSION != 0)):
                                // SI ES DIVISIBLE, NO SE REDONDEA A UN NÚMERO ENTERO
                                if ($rowMat->DIVISIBILIDAD == 'Si'):
                                    $cantidadLineaPedidoRedondeada = $cantidadLineaPedidoRedondeada;
                                else:
                                    $cantidadCompraPendiente = number_format(ceil($cantidadLineaPedidoRedondeada / ($rowMat->NUMERADOR_CONVERSION / $rowMat->DENOMINADOR_CONVERSION)), 3);

                                    //CANTIDAD BASE TRANSFORMADA EN FUNCIÓN A LA DE COMPRA REDONDEADA A ENTERO SUPERIORMENTE
                                    $cantidadLineaPedidoRedondeada = $cantidadCompraPendiente * $factorConversion;
                                endif;
                            else:
                                $cantidadCompraPendiente = ceil((float)$cantidadLineaPedidoRedondeada);
                                //CANTIDAD BASE TRANSFORMADA EN FUNCIÓN A LA DE COMPRA REDONDEADA A ENTERO SUPERIORMENTE
                                $cantidadLineaPedidoRedondeada = $cantidadCompraPendiente * $factorConversion;
                            endif;

                            $sqlUpdate = "UPDATE PEDIDO_SALIDA_LINEA PSL SET
                                            PSL.CANTIDAD_PENDIENTE_SERVIR = $cantidadLineaPedidoRedondeada - (PSL.CANTIDAD - PSL.CANTIDAD_PENDIENTE_SERVIR)
                                            , PSL.CANTIDAD = $cantidadLineaPedidoRedondeada
                                            WHERE PSL.ID_PEDIDO_SALIDA_LINEA = $rowLinea->ID_PEDIDO_SALIDA_LINEA";
                            $bd->ExecSQL($sqlUpdate);

                            //ACTUALIZO LA DEMANDA Y DEMAS DE LA LINEA DE PEDIDO
                            $arr_resultado_reserva = $reserva->actualizar_demandas_pedido($rowLinea->ID_PEDIDO_SALIDA, $rowLinea->ID_PEDIDO_SALIDA_LINEA);
                            if (isset($arr_resultado_reserva['error']) && $arr_resultado_reserva['error'] != "")://SI VIENE ERROR
                                //ALMACENO EL ERROR Y CONTINUO CON LA SIGUIETE LINEA
                                $errorCubrirDemanda = $errorCubrirDemanda . $auxiliar->traduce("Hubo un error actualizando las demandas de la linea de pedido", $administrador->ID_IDIOMA) . " " . $rowLinea->ID_PEDIDO_SALIDA . " " . $auxiliar->traduce("Error", $administrador->ID_IDIOMA) . " " . $arr_resultado_reserva['error'] . "<br>";
                                break; //SALGO DE LA EJECUCION DE ESTA LINEA DE ORDEN DE TRABAJO
                            endif;

                            //AÑADO LA LINEA AL ARRAY DE LINEAS CREADAS/MODIFICADAS
                            $arrLineasPedidoSalidaCreadasModificadas[] = $rowLinea->ID_PEDIDO_SALIDA_LINEA;

                            $cantidadPentienteAsociar = 0;
                        elseif ($cantidadAsociar > 0):
                            //GRABO LA CANTIDAD ASOCIADA
                            $sqlInsert = "INSERT INTO ORDEN_TRABAJO_LINEA_CUBIERTA_PEDIDO SET 
                                            ID_ORDEN_TRABAJO = $rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO 
                                            , ID_ORDEN_TRABAJO_LINEA = $rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO_LINEA 
                                            , FECHA_PLANIFICADA = '" . ($fechaPlanificada == NULL ? $rowOrdenTrabajoLinea->FECHA_PLANIFICADA : $fechaPlanificada) . "' 
                                            , ID_PEDIDO_SALIDA = $rowLinea->ID_PEDIDO_SALIDA 
                                            , ID_PEDIDO_SALIDA_LINEA = $rowLinea->ID_PEDIDO_SALIDA_LINEA 
                                            , CANTIDAD_POSICION = $rowOrdenTrabajoLinea->CANTIDAD 
                                            , CANTIDAD_CUBIERTA_POR_PEDIDO_SALIDA_LINEA = $cantidadAsociar 
                                            , CANTIDAD_PENDIENTE_CUBRIR_POR_PEDIDO_SALIDA_LINEA = 0 
                                            , CANTIDAD_ENTREGADA_EN_DESTINO = 0";
                            $bd->ExecSQL($sqlInsert);

                            //DESCUENTO LA CANTIDAD QUE ACABO DE ASOCIAR
                            $cantidadPentienteAsociar = $cantidadPentienteAsociar - $cantidadAsociar;
                            if (abs( (float)$cantidadPentienteAsociar) < EPSILON_SISTEMA):
                                $cantidadPentienteAsociar = 0;
                            endif;
                        endif;
                    endif;
                endif;
            endif;

            //SI LA LINEA DE PEDIDO SOLO TIENE ASOCIADA ALGUNA LINEA DE OT, MODIFICO LA FECHA DE ENTREGA DE LA LINEA DEL PEDIDO
            $num = $bd->NumRegsTabla("ORDEN_TRABAJO_LINEA_CUBIERTA_PEDIDO", "ID_PEDIDO_SALIDA_LINEA = $rowLinea->ID_PEDIDO_SALIDA_LINEA AND BAJA = 0");

            //SI LA LINEA DE PEDIDO TIENE LINEAS DE OT ASOCIADAS ACTUALIZO LA FECHA DE ENTREGA
            if ($num > 0):
                //SI LA LINEA DE PEDIDO SE HA EMPEZADO A PREPARAR NO ACTUALIZO SUS FECHAS
                if (($rowLinea->CANTIDAD - $rowLinea->CANTIDAD_PENDIENTE_SERVIR) < EPSILON_SISTEMA):
                    $sqlUpdate = "UPDATE PEDIDO_SALIDA_LINEA PSL 
                                          INNER JOIN ORDEN_TRABAJO_LINEA_CUBIERTA_PEDIDO OTLCP ON OTLCP.ID_PEDIDO_SALIDA_LINEA = PSL.ID_PEDIDO_SALIDA_LINEA 
                                          SET PSL.FECHA_ENTREGA = (SELECT MIN(FECHA_PLANIFICADA) 
                                                                       FROM ORDEN_TRABAJO_LINEA_CUBIERTA_PEDIDO 
                                                                       WHERE ID_PEDIDO_SALIDA_LINEA = $rowLinea->ID_PEDIDO_SALIDA_LINEA AND BAJA = 0)
                                          WHERE PSL.ID_PEDIDO_SALIDA_LINEA = $rowLinea->ID_PEDIDO_SALIDA_LINEA";
                    $bd->ExecSQL($sqlUpdate);
                endif;
                //FIN SI LA LINEA DE PEDIDO SE HA EMPEZADO A PREPARAR NO ACTUALIZO SUS FECHAS

                //AÑADO LA LINEA AL ARRAY DE LINEAS CREADAS/MODIFICADAS
                $arrLineasPedidoSalidaCreadasModificadas[] = $rowLinea->ID_PEDIDO_SALIDA_LINEA;
            endif;

            //BUSCO LA LÍNEA DEL PEDIDO ACTUALIZADA
            $rowLineaActualizada = $bd->VerReg("PEDIDO_SALIDA_LINEA", "ID_PEDIDO_SALIDA_LINEA", $rowLinea->ID_PEDIDO_SALIDA_LINEA);

            // LOG MOVIMIENTOS
            $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Linea Pedido Salida", $rowLinea->ID_PEDIDO_SALIDA_LINEA, "Modificacion linea pedido salida", "PEDIDO_SALIDA_LINEA", $rowLinea, $rowLineaActualizada);
        endwhile;
        //FIN BUSCO LAS LINEAS DE PEDIDO QUE PUEDA REUTIZAR

        //SI LA CANTIDAD PENDIENTE DE ASOCIAR ES MAYOR QUE CERO
        if ($cantidadPentienteAsociar > EPSILON_SISTEMA):

            //EL TIPO DE PEDIDO ES ZTRB O ZTRD EN FUNCION DE LA SOCIEDAD
            if ($rowCentroOrigen->ID_SOCIEDAD == $rowCentroDestino->ID_SOCIEDAD): //SI LA SOCIEDAD ES IGUAL, EL PEDIDO ES ZTRB
                $tipoPedidoSap = "ZTRB";
            else: //SI LA SOCIEDAD ES DIFERENTE, EL PEDIDO ES ZTRD
                $tipoPedidoSap = "ZTRD";
            endif;
            //FIN EL TIPO DE PEDIDO ES ZTRB O ZTRD EN FUNCION DE LA SOCIEDAD

            //BUSCO EL MAXIMO NUMERO DE LINEA
            $UltimoNumeroLinea       = 0;
            $sqlUltimoNumeroLinea    = "SELECT MAX(CAST(LINEA_PEDIDO_SAP AS UNSIGNED)) AS NUMERO_LINEA FROM PEDIDO_SALIDA_LINEA WHERE ID_PEDIDO_SALIDA = " . $rowPedidoSalida->ID_PEDIDO_SALIDA;
            $resultUltimoNumeroLinea = $bd->ExecSQL($sqlUltimoNumeroLinea);
            if ($resultUltimoNumeroLinea != false):
                $rowUltimoNumeroLinea = $bd->SigReg($resultUltimoNumeroLinea);
                if ($rowUltimoNumeroLinea->NUMERO_LINEA != NULL):
                    $UltimoNumeroLinea = $rowUltimoNumeroLinea->NUMERO_LINEA;
                endif;
            endif;
            $SiguienteNumeroLinea = $UltimoNumeroLinea + 10;

            if ($rowMatAlm->TIPO_LOTE == 'serie'):
                //GUARDO LA CANTIDAD TOTAL PENDIENTE DE ASOCIAR
                $cantidadTotalPentienteAsociar = $cantidadPentienteAsociar;

                for ($i = 0; $i < $cantidadTotalPentienteAsociar; $i++):
                    //INSERTO UNA NUEVA LÍNEA EN EL PEDIDO POR CADA UNIDAD
                    $sqlInsert = "INSERT INTO PEDIDO_SALIDA_LINEA SET
                                    ID_PEDIDO_SALIDA = " . $rowPedidoSalida->ID_PEDIDO_SALIDA . "
                                    , LINEA_PEDIDO_SAP = '" . str_pad( (string)$SiguienteNumeroLinea, 5, "0", STR_PAD_LEFT) . "'
                                    , RELEVANTE_ENTREGA_ENTRANTE = " . ((($this->RelevanteParaEntregaEntrante($tipoPedidoSap) == 1) && ($rowSociedadDestino->GESTION_TRANSPORTE == 1)) ? 1 : 0) . "
                                    , ID_ALMACEN_ORIGEN = $rowAlmacenOrigen->ID_ALMACEN
                                    , ID_MATERIAL = $rowMat->ID_MATERIAL
                                    , ID_TIPO_BLOQUEO = " . ($idTipoBloqueo == NULL ? 'NULL' : $idTipoBloqueo) . "
                                    , CANTIDAD = 1
                                    , CANTIDAD_PENDIENTE_SERVIR = 1
                                    , ID_UNIDAD = $rowMat->ID_UNIDAD_MEDIDA
                                    , FECHA_MODIFICACION = '" . date("Y-m-d") . "'
                                    , FECHA_ENTREGA = '" . ($fechaPlanificada == NULL ? $rowOrdenTrabajoLinea->FECHA_PLANIFICADA : $fechaPlanificada) . "'
                                    , ID_CENTRO_DESTINO = $rowCentroDestino->ID_CENTRO
                                    , ID_ALMACEN_DESTINO = $rowAlmacenDestino->ID_ALMACEN
                                    , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                    , ID_ORDEN_TRABAJO_LINEA = " . ($rowPedidoSalida->TIPO_TRASLADO == 'Planificado Material Obligatorio' ? $rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO_LINEA : 'NULL');
                    $bd->ExecSQL($sqlInsert);
                    $idPedidoSalidaLinea = $bd->IdAsignado();

                    //BUSCO LA LÍNEA DEL PEDIDO ACTUALIZADA
                    $rowLineaCreada = $bd->VerReg("PEDIDO_SALIDA_LINEA", "ID_PEDIDO_SALIDA_LINEA", $idPedidoSalidaLinea);

                    // LOG MOVIMIENTOS
                    $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Linea Pedido Salida", $idPedidoSalidaLinea, "Creacion linea pedido salida", "PEDIDO_SALIDA_LINEA", NULL, $rowLineaCreada);

                    //AÑADO LA LINEA AL ARRAY DE LINEAS CREADAS/MODIFICADAS
                    $arrLineasPedidoSalidaCreadasModificadas[] = $idPedidoSalidaLinea;

                    //ACTUALIZO LA DEMANDA Y DEMAS DE LA LINEA DE PEDIDO
                    $arr_resultado_reserva = $reserva->actualizar_demandas_pedido($rowPedidoSalida->ID_PEDIDO_SALIDA, $idPedidoSalidaLinea);
                    if (isset($arr_resultado_reserva['error']) && $arr_resultado_reserva['error'] != "")://SI VIENE ERROR
                        //ALMACENO EL ERROR Y CONTINUO CON LA SIGUIETE LINEA
                        $errorCubrirDemanda = $errorCubrirDemanda . $auxiliar->traduce("Hubo un error actualizando las demandas de la linea de pedido", $administrador->ID_IDIOMA) . " " . $rowPedidoSalida->ID_PEDIDO_SALIDA . " " . $auxiliar->traduce("Error", $administrador->ID_IDIOMA) . " " . arr_resultado_reserva['error'] . "<br>";
                        break; //SALGO DE LA EJECUCION DE ESTA LINEA DE ORDEN DE TRABAJO
                    endif;

                    //GUARDO LA CANTIDAD ASOCIADA A LA LINEA DE LA OT (NUNCA PUEDE SER SUPERIOR A 1)
                    $cantidadAsociar = min($cantidadPentienteAsociar, 1);

                    //GRABO LA CANTIDAD ASOCIADA
                    $sqlInsert = "INSERT INTO ORDEN_TRABAJO_LINEA_CUBIERTA_PEDIDO SET 
                                      ID_ORDEN_TRABAJO = $rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO 
                                      , ID_ORDEN_TRABAJO_LINEA = $rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO_LINEA 
                                      , FECHA_PLANIFICADA = '" . ($fechaPlanificada == NULL ? $rowOrdenTrabajoLinea->FECHA_PLANIFICADA : $fechaPlanificada) . "' 
                                      , ID_PEDIDO_SALIDA = $rowPedidoSalida->ID_PEDIDO_SALIDA 
                                      , ID_PEDIDO_SALIDA_LINEA = $idPedidoSalidaLinea 
                                      , CANTIDAD_POSICION = $rowOrdenTrabajoLinea->CANTIDAD 
                                      , CANTIDAD_CUBIERTA_POR_PEDIDO_SALIDA_LINEA = $cantidadAsociar
                                      , CANTIDAD_PENDIENTE_CUBRIR_POR_PEDIDO_SALIDA_LINEA = 0 
                                      , CANTIDAD_ENTREGADA_EN_DESTINO = 0";
                    $bd->ExecSQL($sqlInsert);

                    //ACTUALIZO LA CANTIDAD PENDIENTE
                    $cantidadPentienteAsociar -= $cantidadAsociar;

                    //ACTUALIZO EL SIGUIENTE NUMERO DE LINEA
                    $SiguienteNumeroLinea = $SiguienteNumeroLinea + 10;
                endfor;
            else:
                //BUSCO SI EXISTE UNA LINEA DE PEDIDO CON LA MISMA INFORMACION
                $GLOBALS["NotificaErrorPorEmail"] = "No";
                $rowPedidoSalidaLinea             = $bd->VerRegRest("PEDIDO_SALIDA_LINEA", "ID_PEDIDO_SALIDA = $rowPedidoSalida->ID_PEDIDO_SALIDA AND ID_ALMACEN_ORIGEN = $rowAlmacenOrigen->ID_ALMACEN AND ID_MATERIAL = $rowMat->ID_MATERIAL AND ID_TIPO_BLOQUEO " . ($idTipoBloqueo == NULL ? 'IS NULL' : "= $idTipoBloqueo") . " AND ID_UNIDAD = $rowMat->ID_UNIDAD_MEDIDA " . (($rowPedidoSalida->TIPO_TRASLADO == 'Planificado Material Indivisible') ? ("AND FECHA_ENTREGA = '" . (($fechaPlanificada == NULL) ? $rowOrdenTrabajoLinea->FECHA_PLANIFICADA : $fechaPlanificada) . "'") : "") . " AND ID_CENTRO_DESTINO = $rowCentroDestino->ID_CENTRO AND ID_ALMACEN_DESTINO = $rowAlmacenDestino->ID_ALMACEN AND ID_ORDEN_TRABAJO_LINEA " . ($rowPedidoSalida->TIPO_TRASLADO == 'Planificado Material Obligatorio' ? "= $rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO_LINEA" : 'IS NULL') . " AND CANTIDAD = CANTIDAD_PENDIENTE_SERVIR AND BAJA = 0 AND INDICADOR_BORRADO IS NULL", "No");

                if ($rowPedidoSalidaLinea != false): //INCREMENTO LA CANTIDAD

                    if ($rowPedidoSalida->TIPO_TRASLADO != 'Planificado Material No Obligatorio'):
                        //CALCULO EL FACTOR DE CONVERSION
                        $factorConversion = 1;
                        if ($rowMat->ID_UNIDAD_MEDIDA != $rowMat->ID_UNIDAD_COMPRA && $rowMat->DENOMINADOR_CONVERSION != 0):
                            $factorConversion = $rowMat->NUMERADOR_CONVERSION / $rowMat->DENOMINADOR_CONVERSION;
                        endif;

                        //CALCULO LA CANTIDAD DE COMPRA
                        if (($rowMat->ID_UNIDAD_MEDIDA != $rowMat->ID_UNIDAD_COMPRA) && ($rowMat->DENOMINADOR_CONVERSION != 0)):
                            // SI ES DIVISIBLE, NO SE REDONDEA A UN NÚMERO ENTERO
                            if ($rowMat->DIVISIBILIDAD == 'Si'):
                                $cantidadLineaPedido = $cantidadPentienteAsociar;
                            else:
                                $cantidadCompraPendiente = number_format(ceil($cantidadPentienteAsociar / ($rowMat->NUMERADOR_CONVERSION / $rowMat->DENOMINADOR_CONVERSION)), 3);

                                //CANTIDAD BASE TRANSFORMADA EN FUNCIÓN A LA DE COMPRA REDONDEADA A ENTERO SUPERIORMENTE
                                $cantidadLineaPedido = $cantidadCompraPendiente * $factorConversion;
                            endif;
                        else:
                            $cantidadCompraPendiente = ceil((float)$cantidadPentienteAsociar);
                            //CANTIDAD BASE TRANSFORMADA EN FUNCIÓN A LA DE COMPRA REDONDEADA A ENTERO SUPERIORMENTE
                            $cantidadLineaPedido = $cantidadCompraPendiente * $factorConversion;
                        endif;
                    else:
                        $cantidadLineaPedido = $cantidadPentienteAsociar;
                    endif;

                    $sqlUpdate = "UPDATE PEDIDO_SALIDA_LINEA SET
                                    CANTIDAD = CANTIDAD + $cantidadLineaPedido,
                                    CANTIDAD_PENDIENTE_SERVIR = CANTIDAD_PENDIENTE_SERVIR + $cantidadLineaPedido,
                                    FECHA_MODIFICACION = '" . date("Y-m-d") . "'
                                    WHERE ID_PEDIDO_SALIDA_LINEA = $rowPedidoSalidaLinea->ID_PEDIDO_SALIDA_LINEA";
                    $bd->ExecSQL($sqlUpdate);
                    $idPedidoSalidaLinea = $rowPedidoSalidaLinea->ID_PEDIDO_SALIDA_LINEA;

                    //AÑADO LA LINEA AL ARRAY DE LINEAS CREADAS/MODIFICADAS
                    $arrLineasPedidoSalidaCreadasModificadas[] = $idPedidoSalidaLinea;

                    //ACTUALIZO LA DEMANDA Y DEMAS DE LA LINEA DE PEDIDO
                    $arr_resultado_reserva = $reserva->actualizar_demandas_pedido($rowPedidoSalidaLinea->ID_PEDIDO_SALIDA, $rowPedidoSalidaLinea->ID_PEDIDO_SALIDA_LINEA);
                    if (isset($arr_resultado_reserva['error']) && $arr_resultado_reserva['error'] != "")://SI VIENE ERROR
                        //ALMACENO EL ERROR Y CONTINUO CON LA SIGUIETE LINEA
                        $errorCubrirDemanda = $errorCubrirDemanda . $auxiliar->traduce("Hubo un error actualizando las demandas de la linea de pedido", $administrador->ID_IDIOMA) . " " . $rowPedidoSalidaLinea->ID_PEDIDO_SALIDA . " " . $auxiliar->traduce("Error", $administrador->ID_IDIOMA) . " " . $arr_resultado_reserva['error'] . "<br>";
                    endif;
                else: //GENERO LA LINEA NUEVA

                    if ($rowPedidoSalida->TIPO_TRASLADO != 'Planificado Material No Obligatorio'):
                        //CALCULO EL FACTOR DE CONVERSION
                        $factorConversion = 1;
                        if ($rowMat->ID_UNIDAD_MEDIDA != $rowMat->ID_UNIDAD_COMPRA && $rowMat->DENOMINADOR_CONVERSION != 0):
                            $factorConversion = $rowMat->NUMERADOR_CONVERSION / $rowMat->DENOMINADOR_CONVERSION;
                        endif;

                        //CALCULO LA CANTIDAD DE COMPRA
                        if (($rowMat->ID_UNIDAD_MEDIDA != $rowMat->ID_UNIDAD_COMPRA) && ($rowMat->DENOMINADOR_CONVERSION != 0)):
                            // SI ES DIVISIBLE, NO SE REDONDEA A UN NÚMERO ENTERO
                            if ($rowMat->DIVISIBILIDAD == 'Si'):
                                $cantidadLineaPedido = $cantidadPentienteAsociar;
                            else:
                                $cantidadCompraPendiente = number_format(ceil($cantidadPentienteAsociar / ($rowMat->NUMERADOR_CONVERSION / $rowMat->DENOMINADOR_CONVERSION)), 3);

                                //CANTIDAD BASE TRANSFORMADA EN FUNCIÓN A LA DE COMPRA REDONDEADA A ENTERO SUPERIORMENTE
                                $cantidadLineaPedido = $cantidadCompraPendiente * $factorConversion;
                            endif;
                        else:
                            $cantidadCompraPendiente = ceil((float)$cantidadPentienteAsociar);
                            //CANTIDAD BASE TRANSFORMADA EN FUNCIÓN A LA DE COMPRA REDONDEADA A ENTERO SUPERIORMENTE
                            $cantidadLineaPedido = $cantidadCompraPendiente * $factorConversion;
                        endif;
                    else:
                        $cantidadLineaPedido = $cantidadPentienteAsociar;
                    endif;

                    //INSERTO UNA NUEVA LÍNEA EN EL PEDIDO
                    $sqlInsert = "INSERT INTO PEDIDO_SALIDA_LINEA SET
                                    ID_PEDIDO_SALIDA = " . $rowPedidoSalida->ID_PEDIDO_SALIDA . "
                                    , LINEA_PEDIDO_SAP = '" . str_pad( (string)$SiguienteNumeroLinea, 5, "0", STR_PAD_LEFT) . "'
                                    , RELEVANTE_ENTREGA_ENTRANTE = " . ((($this->RelevanteParaEntregaEntrante($tipoPedidoSap) == 1) && ($rowSociedadDestino->GESTION_TRANSPORTE == 1)) ? 1 : 0) . "
                                    , ID_ALMACEN_ORIGEN = $rowAlmacenOrigen->ID_ALMACEN
                                    , ID_MATERIAL = $rowMat->ID_MATERIAL
                                    , ID_TIPO_BLOQUEO = " . ($idTipoBloqueo == NULL ? 'NULL' : $idTipoBloqueo) . "
                                    , CANTIDAD = $cantidadLineaPedido
                                    , CANTIDAD_PENDIENTE_SERVIR = $cantidadLineaPedido
                                    , ID_UNIDAD = $rowMat->ID_UNIDAD_MEDIDA
                                    , FECHA_MODIFICACION = '" . date("Y-m-d") . "'
                                    , FECHA_ENTREGA = '" . ($fechaPlanificada == NULL ? $rowOrdenTrabajoLinea->FECHA_PLANIFICADA : $fechaPlanificada) . "'
                                    , ID_CENTRO_DESTINO = $rowCentroDestino->ID_CENTRO
                                    , ID_ALMACEN_DESTINO = $rowAlmacenDestino->ID_ALMACEN
                                    , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                    , ID_ORDEN_TRABAJO_LINEA = " . ($rowPedidoSalida->TIPO_TRASLADO == 'Planificado Material Obligatorio' ? $rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO_LINEA : 'NULL');
                    $bd->ExecSQL($sqlInsert);
                    $idPedidoSalidaLinea = $bd->IdAsignado();

                    //AÑADO LA LINEA AL ARRAY DE LINEAS CREADAS/MODIFICADAS
                    $arrLineasPedidoSalidaCreadasModificadas[] = $idPedidoSalidaLinea;

                    //ACTUALIZO LA DEMANDA Y DEMAS DE LA LINEA DE PEDIDO
                    $arr_resultado_reserva = $reserva->actualizar_demandas_pedido($rowPedidoSalida->ID_PEDIDO_SALIDA, $idPedidoSalidaLinea);
                    if (isset($arr_resultado_reserva['error']) && $arr_resultado_reserva['error'] != "")://SI VIENE ERROR
                        //ALMACENO EL ERROR Y CONTINUO CON LA SIGUIETE LINEA
                        $errorCubrirDemanda = $errorCubrirDemanda . $auxiliar->traduce("Hubo un error actualizando las demandas de la linea de pedido", $administrador->ID_IDIOMA) . " " . $rowPedidoSalida->ID_PEDIDO_SALIDA . " " . $auxiliar->traduce("Error", $administrador->ID_IDIOMA) . " " . $arr_resultado_reserva['error'] . "<br>";
                    endif;
                endif;

                //BUSCO SI EXISTE UN REGISTRO DONDE AÑADIR LA CANTIDAD
                $GLOBALS["NotificaErrorPorEmail"]   = "No";
                $rowOrdenTrabajoLineaCubiertaPedido = $bd->VerRegRest("ORDEN_TRABAJO_LINEA_CUBIERTA_PEDIDO", "ID_ORDEN_TRABAJO = $rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO AND ID_ORDEN_TRABAJO_LINEA = $rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO_LINEA AND FECHA_PLANIFICADA = '" . ($fechaPlanificada == NULL ? $rowOrdenTrabajoLinea->FECHA_PLANIFICADA : $fechaPlanificada) . "' AND ID_PEDIDO_SALIDA = $rowPedidoSalida->ID_PEDIDO_SALIDA AND ID_PEDIDO_SALIDA_LINEA = $idPedidoSalidaLinea AND CANTIDAD_PENDIENTE_CUBRIR_POR_PEDIDO_SALIDA_LINEA = 0 AND CANTIDAD_ENTREGADA_EN_DESTINO = 0 AND BAJA = 0", "No");
                unset($GLOBALS["NotificaErrorPorEmail"]);
                if ($rowOrdenTrabajoLineaCubiertaPedido == false):
                    //GRABO LA CANTIDAD ASOCIADA
                    $sqlInsert = "INSERT INTO ORDEN_TRABAJO_LINEA_CUBIERTA_PEDIDO SET 
                                      ID_ORDEN_TRABAJO = $rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO 
                                      , ID_ORDEN_TRABAJO_LINEA = $rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO_LINEA 
                                      , FECHA_PLANIFICADA = '" . ($fechaPlanificada == NULL ? $rowOrdenTrabajoLinea->FECHA_PLANIFICADA : $fechaPlanificada) . "' 
                                      , ID_PEDIDO_SALIDA = $rowPedidoSalida->ID_PEDIDO_SALIDA 
                                      , ID_PEDIDO_SALIDA_LINEA = $idPedidoSalidaLinea 
                                      , CANTIDAD_POSICION = $rowOrdenTrabajoLinea->CANTIDAD 
                                      , CANTIDAD_CUBIERTA_POR_PEDIDO_SALIDA_LINEA = $cantidadPentienteAsociar 
                                      , CANTIDAD_PENDIENTE_CUBRIR_POR_PEDIDO_SALIDA_LINEA = 0 
                                      , CANTIDAD_ENTREGADA_EN_DESTINO = 0";
                    $bd->ExecSQL($sqlInsert);
                else:
                    //ACTUALIZO LA CANTIDAD ASOCIADA
                    $sqlUpdate = "UPDATE ORDEN_TRABAJO_LINEA_CUBIERTA_PEDIDO SET
                                    CANTIDAD_POSICION = $rowOrdenTrabajoLinea->CANTIDAD 
                                    , CANTIDAD_CUBIERTA_POR_PEDIDO_SALIDA_LINEA = CANTIDAD_CUBIERTA_POR_PEDIDO_SALIDA_LINEA + $cantidadPentienteAsociar 
                                    WHERE ID_ORDEN_TRABAJO_LINEA_CUBIERTA_PEDIDO = $rowOrdenTrabajoLineaCubiertaPedido->ID_ORDEN_TRABAJO_LINEA_CUBIERTA_PEDIDO";
                    $bd->ExecSQL($sqlUpdate);
                endif;

                //BUSCO LA LÍNEA DEL PEDIDO ACTUALIZADA
                $rowPedidoSalidaLineaActualizada = $bd->VerReg("PEDIDO_SALIDA_LINEA", "ID_PEDIDO_SALIDA_LINEA", $idPedidoSalidaLinea);

                //SI LA LINEA DE PEDIDO SE HA EMPEZADO A PREPARAR NO ACTUALIZO SUS FECHAS
                if (($rowPedidoSalidaLineaActualizada->CANTIDAD - $rowPedidoSalidaLineaActualizada->CANTIDAD_PENDIENTE_SERVIR) < EPSILON_SISTEMA):
                    $sqlUpdate = "UPDATE PEDIDO_SALIDA_LINEA PSL 
                                      INNER JOIN ORDEN_TRABAJO_LINEA_CUBIERTA_PEDIDO OTLCP ON OTLCP.ID_PEDIDO_SALIDA_LINEA = PSL.ID_PEDIDO_SALIDA_LINEA 
                                      SET PSL.FECHA_ENTREGA = (SELECT MIN(FECHA_PLANIFICADA) 
                                                                   FROM ORDEN_TRABAJO_LINEA_CUBIERTA_PEDIDO 
                                                                   WHERE ID_PEDIDO_SALIDA_LINEA = $rowPedidoSalidaLineaActualizada->ID_PEDIDO_SALIDA_LINEA AND BAJA = 0)
                                      WHERE PSL.ID_PEDIDO_SALIDA_LINEA = $rowPedidoSalidaLineaActualizada->ID_PEDIDO_SALIDA_LINEA";
                    $bd->ExecSQL($sqlUpdate);
                endif;
                //FIN SI LA LINEA DE PEDIDO SE HA EMPEZADO A PREPARAR NO ACTUALIZO SUS FECHAS

                //BUSCO LA LÍNEA DEL PEDIDO ACTUALIZADA
                $rowPedidoSalidaLineaActualizada = $bd->VerReg("PEDIDO_SALIDA_LINEA", "ID_PEDIDO_SALIDA_LINEA", $idPedidoSalidaLinea);

                // LOG MOVIMIENTOS
                $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Linea Pedido Salida", $idPedidoSalidaLinea, "Modificacion linea pedido salida", "PEDIDO_SALIDA_LINEA", ($rowPedidoSalidaLinea ? $rowPedidoSalidaLinea : NULL), $rowPedidoSalidaLineaActualizada);
            endif;
        endif;
        //FIN SI TODAVIA QUEDA CANTIDAD PENDIENTE DE ASIGNAR

        //CALCULO EL TIPO PEDIDO SAP
        $tipoPedidoSAP           = "ZTRB";
        $sqlSociedadesDestino    = "SELECT DISTINCT C.ID_SOCIEDAD
                                        FROM CENTRO C
                                        INNER JOIN PEDIDO_SALIDA_LINEA PSL ON PSL.ID_CENTRO_DESTINO = C.ID_CENTRO
                                        WHERE PSL.ID_PEDIDO_SALIDA = $rowPedidoSalida->ID_PEDIDO_SALIDA AND PSL.BAJA = 0";
        $resultSociedadesDestino = $bd->ExecSQL($sqlSociedadesDestino);
        while ($rowSociedadDestino = $bd->SigReg($resultSociedadesDestino)):
            if ($rowSociedadDestino->ID_SOCIEDAD != $rowCentroOrigen->ID_SOCIEDAD):
                $tipoPedidoSAP = "ZTRD";
                break;
            endif;
        endwhile;

        //ACTUALIZO EL TIPO PEDIDO SAP DEL PEDIDO DE SALIDA
        $sqlUpdate = "UPDATE PEDIDO_SALIDA SET
                          TIPO_PEDIDO_SAP = '" . $tipoPedidoSAP . "'
                          , ESTADO = " . ($rowPedidoSalida->ESTADO == 'Finalizado' ? "'En Entrega'" : "ESTADO") . "
                          WHERE ID_PEDIDO_SALIDA = $rowPedidoSalida->ID_PEDIDO_SALIDA";
        $bd->ExecSQL($sqlUpdate);

        //SI TENEMOS LINEAS CREADAS Y/O MODIFICADAS
        if (count( (array)$arrLineasPedidoSalidaCreadasModificadas) > 0):
            //DEJO LOS REGISTROS UNICOS PARA NO HACER VARIAS VECES EL MISMO UPDATE
            $arrLineasPedidoSalidaCreadasModificadas = array_unique( (array)$arrLineasPedidoSalidaCreadasModificadas);

            //RECORRO LAS LINEAS PARA ACTUALIZAR EL SHIPPING DATE
            foreach ($arrLineasPedidoSalidaCreadasModificadas as $clave => $idPedidoSalidaLinea):

                //BUSCO LA LINEA DEL PEDIDO DE SALIDA
                $rowPedidoSalidaLinea = $bd->VerReg("PEDIDO_SALIDA_LINEA", "ID_PEDIDO_SALIDA_LINEA", $idPedidoSalidaLinea);

                //REVISO QUE LA LINEA NO ESTE FINALIZADA
                if ($rowPedidoSalidaLinea->ESTADO != 'Finalizada'):

                    //CALCULO LA CANTIDAD EXPEDIDA
                    $cantidadExpedida = 0;
                    $sqlCantidadExpedida = "SELECT IF(SUM(CANTIDAD) IS NULL, 0, SUM(CANTIDAD)) AS CANTIDAD_EXPEDIDA
                                                FROM MOVIMIENTO_SALIDA_LINEA MSL
                                                WHERE MSL.ID_PEDIDO_SALIDA_LINEA = $rowPedidoSalidaLinea->ID_PEDIDO_SALIDA_LINEA AND MSL.ESTADO IN ('Expedido', 'En Tránsito', 'Recepcionado') AND MSL.LINEA_ANULADA = 0 AND MSL.BAJA = 0";
                    $resultCantidadExpedida = $bd->ExecSQL($sqlCantidadExpedida);
                    if ($rowCantidadExpedida = $bd->SigReg($resultCantidadExpedida)):
                        $cantidadExpedida = $rowCantidadExpedida->CANTIDAD_EXPEDIDA;
                    endif;

                    //REVISO SI QUEDA CANTIDAD PENDIENTE DE EXPEDIR
                    if (($rowPedidoSalidaLinea->CANTIDAD - $cantidadExpedida) > EPSILON_SISTEMA):

                        //REVISO SI LA CANTIDAD PENDIENTE DE EXPEDIR TIENE UNA INCIDENCIA DE PROCESO ABIERTA
                        $transporteConIncidenciaAbierta = false;
                        $sqlNumTransportesIncidenciaAbierta = "SELECT DISTINCT OT.ID_ORDEN_TRANSPORTE
                                                                FROM MOVIMIENTO_SALIDA_LINEA MSL
                                                                INNER JOIN EXPEDICION E ON E.ID_EXPEDICION = MSL.ID_EXPEDICION
                                                                INNER JOIN ORDEN_TRANSPORTE OT ON OT.ID_ORDEN_TRANSPORTE = E.ID_ORDEN_TRANSPORTE
                                                                WHERE MSL.ID_PEDIDO_SALIDA_LINEA = $rowPedidoSalidaLinea->ID_PEDIDO_SALIDA_LINEA AND MSL.ESTADO IN ('Reservado para Preparacion', 'En Preparacion', 'Pendiente de Expedir', 'Transmitido a SAP') AND MSL.LINEA_ANULADA = 0 AND MSL.BAJA = 0 AND E.BAJA = 0 AND OT.ESTADO_INCIDENCIA = 'Abierta' AND OT.BAJA = 0";
                        $resultNumTransportesIncidenciaAbierta = $bd->ExecSQL($sqlNumTransportesIncidenciaAbierta);
                        if ($bd->NumRegs($resultNumTransportesIncidenciaAbierta) > 0):
                            $transporteConIncidenciaAbierta = true;
                        endif;

                        //SI LA CANTIDAD ESTA PENDIENTE DE EXPEDIR PERO NO HAY NINGUNA INCIDENCIA DE PROCESO ABIERTA ACTUALIZAMOS LOS VIAJES Y FECHAS
                        if ($transporteConIncidenciaAbierta == false):

                            //ACTUALIZAMOS EL SHIPPING DATE, YA ESTA CALCULADO EN SESSION
                            $arrDatosShipping = $_SESSION["SHIPPING_DATE"][$rowAlmacenOrigen->ID_ALMACEN][$rowAlmacenDestino->ID_ALMACEN][$rowPedidoSalidaLinea->FECHA_ENTREGA][$rowAlmacenDestino->NUMERO_CICLOS_ANTELACION];

                            //ACTUALIZAMOS EL SHIPPING DATE DE LA LINEA O LINEAS
                            $sqlUpdate = "UPDATE PEDIDO_SALIDA_LINEA SET
                                            ID_RUTA_VIAJE_LINEA_ORIGEN = " . ($arrDatosShipping['idRutaViajeLineaOrigen'] != "" ? $arrDatosShipping['idRutaViajeLineaOrigen'] : "NULL") . "
                                            , ID_RUTA_SUBVIAJE_LINEA_ORIGEN = " . ($arrDatosShipping['idRutaSubViajeLineaOrigen'] != "" ? $arrDatosShipping['idRutaSubViajeLineaOrigen'] : "NULL") . "
                                            , ID_RUTA_VIAJE_LINEA_DESTINO = " . ($arrDatosShipping['idRutaViajeLineaDestino'] != "" ? $arrDatosShipping['idRutaViajeLineaDestino'] : "NULL") . "
                                            , ID_RUTA_SUBVIAJE_LINEA_DESTINO = " . ($arrDatosShipping['idRutaSubViajeLineaDestino'] != "" ? $arrDatosShipping['idRutaSubViajeLineaDestino'] : "NULL") . "
                                            , FECHA_ESTIMADA_PREPARACION = '" . ($arrDatosShipping['txFechaEstimadaPreparacion'] != "" ? $arrDatosShipping['txFechaEstimadaPreparacion'] : "0000-00-00 00:00:00") . "'
                                            , FECHA_SHIPPING = '" . ($arrDatosShipping['txShippingDate'] != "" ? $arrDatosShipping['txShippingDate'] : "0000-00-00 00:00:00") . "'
                                            , FECHA_ESTIMADA_LLEGADA = '" . ($arrDatosShipping['txFechaEstimadaLLegada'] != "" ? $arrDatosShipping['txFechaEstimadaLLegada'] : "0000-00-00 00:00:00") . "'
                                            WHERE ID_PEDIDO_SALIDA_LINEA = $rowPedidoSalidaLinea->ID_PEDIDO_SALIDA_LINEA AND BAJA = 0";
                            $bd->ExecSQL($sqlUpdate);

                            //BUSCAMOS LA LINEA DEL PEDIDO
                            $rowPedidoSalidaLineaActualizada = $bd->VerReg("PEDIDO_SALIDA_LINEA", "ID_PEDIDO_SALIDA_LINEA", $rowPedidoSalidaLinea->ID_PEDIDO_SALIDA_LINEA);

                            // LOG MOVIMIENTOS
                            $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Linea Pedido Salida", $rowPedidoSalidaLinea->ID_PEDIDO_SALIDA_LINEA, "Modificacion linea pedido salida por actualizacion de viaje planificado", "PEDIDO_SALIDA_LINEA", $rowPedidoSalidaLinea, $rowPedidoSalidaLineaActualizada);
                        endif;
                    endif;
                endif;
            endforeach;
            //FIN RECORRO LAS LINEAS PARA ACTUALIZAR EL SHIPPING DATE
        endif;

        //FIN SI TENEMOS LINEAS CREADAS Y/O MODIFICADAS

        //AÑADO LOS ERRORES
        $arrDevolver['Error']  = $errorCubrirDemanda;
        $arrDevolver['Lineas'] = $arrLineasPedidoSalidaCreadasModificadas;

        return $arrDevolver;
    }

    /**
     * FUNCION PARA DETERMINAR SI ES NECESARIO LANZAR EL WS DE ORDENES_TRABAJO_SEMAFORO DADO UN IDENTIFICADOR DE PEDIDO LINEA
     */
    function lanzar_ws_ordenes_trabajo_semaforo($idPedidoSalidaLinea)
    {
        global $bd;

        $rowLinea = $bd->VerReg("PEDIDO_SALIDA_LINEA", "ID_PEDIDO_SALIDA_LINEA", $idPedidoSalidaLinea);
        if ($rowLinea->ID_ORDEN_TRABAJO_LINEA != NULL):
            return true;
        else:
            //BUSCO EL PEDIDO
            $rowPedido = $bd->VerReg("PEDIDO_SALIDA", "ID_PEDIDO_SALIDA", $rowLinea->ID_PEDIDO_SALIDA);

            //SI EL PEDIDO ES DE TRASLADO OT´s PREVENTIVO
            if (($rowPedido->TIPO_PEDIDO == 'Traslado') && ($rowPedido->TIPO_TRASLADO == 'OT Preventivo')):
                //CALCULO EL NUMERO DE LINEAS DE MOVIMIENTO DE SALIDA NO RECEPCIONADAS
                $num = $bd->NumRegsTabla("MOVIMIENTO_SALIDA_LINEA", "ID_PEDIDO_SALIDA_LINEA = $rowLinea->ID_PEDIDO_SALIDA_LINEA AND BAJA = 0 AND LINEA_ANULADA = 0 AND ESTADO <> 'Recepcionado'");
                if (($num == 0) && ($rowLinea->CANTIDAD_PENDIENTE_SERVIR == 0)): //NO MOVIMIENTOS DIFERENTE RECEPCIONADOS Y CANTIDAD PENDIENTE SERVIR IGUAL A CERO
                    return true;
                endif;
            endif;
        endif;

        return false;
    }

    function AnularPedidoSalida($idPedido, $darOTLporTratadas = "")
    {
        global $bd;
        global $html;
        global $administrador;
        global $sap;
        global $orden_trabajo;
        global $mat;
        global $reserva;
        global $incidencia_sistema;
        global $strError;

        //OBTENGO EL NUMERO DE LINEAS ACTIVAS DEL PEDIDO ANTES DE QUE SEAN ANUNLADAS
        $numLineasActivasPreviaAnulacion    = 0;
        $sqlLineasActivasPreviaAnulacion    = "SELECT * FROM PEDIDO_SALIDA_LINEA WHERE ID_PEDIDO_SALIDA = $idPedido AND BAJA = 0";
        $resultLineasActivasPreviaAnulacion = $bd->ExecSQL($sqlLineasActivasPreviaAnulacion);
        $numLineasActivasPreviaAnulacion    = $bd->NumRegs($resultLineasActivasPreviaAnulacion);

        //ARRAY CON LAS LINEAS MODIFICADAS
        $arrPedidoSalidaLinea = array();

        //ARRAY CON LAS LINEAS SOBRE LAS QUE GENERAR INCIDENCIA DE SISTEMA
        $arrPedidoSalidaLineaGenerarIncidenciaSistema = array();

        // BUSCO EL PEDIDO
        $rowPed = $bd->VerReg("PEDIDO_SALIDA", "ID_PEDIDO_SALIDA", $idPedido, "No");

        //DOY DE BAJA EL PEDIDO, NUNCA ELIMINAREMOS DATOS
        $sqlUpdate = "UPDATE PEDIDO_SALIDA SET BAJA = 1 WHERE ID_PEDIDO_SALIDA = $idPedido";
        $bd->ExecSQL($sqlUpdate);

        //ANULAMOS LAS RESERVAS ASOCIADAS A LAS LINEAS DE PEDIDO
        $resultDemandas = $reserva->get_demandas_pedido($idPedido);
        if (($resultDemandas != false) && ($bd->NumRegs($resultDemandas) > 0)):
            while ($rowDemanda = $bd->SigReg($resultDemandas)):
                $reserva->anular_demanda($rowDemanda->ID_DEMANDA, "Anulacion Pedido");
            endwhile;
        endif;

        //BUSCO LAS LINEAS PARA ANULAR
        $sqlLineas    = "SELECT *
                            FROM PEDIDO_SALIDA_LINEA
                            WHERE ID_PEDIDO_SALIDA = $idPedido";
        $resultLineas = $bd->ExecSQL($sqlLineas);
        while ($rowLinea = $bd->SigReg($resultLineas)):
            //DOY DE BAJA LAS LINEAS DEL PEDIDO, NUNCA ELIMINAREMOS DATOS
            $sqlUpdate = "UPDATE PEDIDO_SALIDA_LINEA SET
                            BAJA = 1
                            WHERE ID_PEDIDO_SALIDA = $idPedido";
            $bd->ExecSQL($sqlUpdate);

            //BUSCAMOS LA LINEA DEL PEDIDO ACTUALIZADA
            $rowLineaActualizada = $bd->VerReg("PEDIDO_SALIDA_LINEA", "ID_PEDIDO_SALIDA_LINEA", $rowLinea->ID_PEDIDO_SALIDA_LINEA);

            //LOG MOVIMIENTOS
            $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Anulación", "Linea Pedido Salida", $rowLinea->ID_PEDIDO_SALIDA_LINEA, "Anulacion linea pedido salida", "PEDIDO_SALIDA_LINEA", $rowLinea, $rowLineaActualizada);
        endwhile;

        //BUSCO LAS LINEAS PARA AÑADIR AL ARRAY
        $sqlLineas    = "SELECT ID_PEDIDO_SALIDA_LINEA
                        FROM PEDIDO_SALIDA_LINEA PSL
                        WHERE PSL.ID_PEDIDO_SALIDA = $idPedido AND PSL.ENVIADO_SAP = 1 AND PSL.INDICADOR_BORRADO IS NULL";
        $resultLineas = $bd->ExecSQL($sqlLineas);

        //MARCO PARA BORRADO LAS LINEAS DEL PEDIDO YA ENVIADAS A SAP
        $sqlUpdate = "UPDATE PEDIDO_SALIDA_LINEA SET
                            INDICADOR_BORRADO = 'L'
                            WHERE ID_PEDIDO_SALIDA = $idPedido AND ENVIADO_SAP = 1";
        $bd->ExecSQL($sqlUpdate);

        //RECORRO LAS LINEAS A TRANSMITIR
        while ($rowLinea = $bd->SigReg($resultLineas)):
            //AÑADO LA LINEA AL ARRAY
            $arrPedidoSalidaLinea[] = $rowLinea->ID_PEDIDO_SALIDA_LINEA;

            //BUSCO SI HAY UNA ACCION PENDIENTE DE REALIZAR SOBRE ESTA LINEA DE PEDIDO SALIDA
            $GLOBALS["NotificaErrorPorEmail"] = "No";
            $rowAccionPendienteRealizar       = $bd->VerRegRest("ACCION_PENDIENTE_REALIZAR", "ESTADO = 'Creada' AND ACCION = 'Borrar' AND TABLA_OBJETO = 'PEDIDO_SALIDA_LINEA' AND ID_OBJETO = $rowLinea->ID_PEDIDO_SALIDA_LINEA AND BAJA = 0", "No");
            unset($GLOBALS["NotificaErrorPorEmail"]);
            if ($rowAccionPendienteRealizar != false): //EXISTE LA ACCION COMO PENDIENTE DE ERALIZAR, LA MARCO COMO FINALIZADA
                $sqlUpdate = "UPDATE ACCION_PENDIENTE_REALIZAR SET
                              ESTADO = 'Finalizada'
                              , FECHA_RESOLUCION = '" . date("Y-m-d H:i:s") . "'
                              , ID_ADMINISTRADOR_ULTIMA_MODIFICACION = $administrador->ID_ADMINISTRADOR
                              WHERE ID_ACCION_PENDIENTE_REALIZAR = $rowAccionPendienteRealizar->ID_ACCION_PENDIENTE_REALIZAR";
                $bd->ExecSQL($sqlUpdate);

                //AGREGO LA LINEA AL ARRAY DE LINEAS SOBRE LAS QUE GENERAR UNA INCIDENCIA DE SISTEMA
                $arrPedidoSalidaLineaGenerarIncidenciaSistema[] = $rowLinea->ID_PEDIDO_SALIDA_LINEA;
            endif;

            //BUSCO EL TIPO INCIDENCIA 'Linea Pendiente Borrado' Y SUBTIPO 'Pedido Salida'
            $rowISTipo    = $bd->VerReg("INCIDENCIA_SISTEMA_TIPO", "INCIDENCIA_SISTEMA_TIPO", "Linea Pendiente Borrado");
            $rowISSubTipo = $bd->VerReg("INCIDENCIA_SISTEMA_SUBTIPO", "INCIDENCIA_SISTEMA_SUBTIPO", "Pedido Salida");

            //BUSCAMOS SI EXISTE INCIDENCIA SISTEMA PARA LA LINEA
            $GLOBALS["NotificaErrorPorEmail"] = "No";
            $rowIncidenciaSistema             = $bd->VerRegRest("INCIDENCIA_SISTEMA", "ID_INCIDENCIA_SISTEMA_TIPO = $rowISTipo->ID_INCIDENCIA_SISTEMA_TIPO AND ID_INCIDENCIA_SISTEMA_SUBTIPO = $rowISSubTipo->ID_INCIDENCIA_SISTEMA_SUBTIPO AND ESTADO = 'Creada' AND TABLA_OBJETO = 'PEDIDO_SALIDA_LINEA' AND ID_OBJETO = $rowLinea->ID_PEDIDO_SALIDA_LINEA", "No");
            unset($GLOBALS["NotificaErrorPorEmail"]);
            if ($rowIncidenciaSistema != false):
                //ACTUALIZO INCIDENCIA
                $incidencia_sistema->actualizarIncidencia($rowIncidenciaSistema, 'Automatica');
            endif;
        endwhile;

        //MODIFICACIONES ESPECIALES
        if (($rowPed->TIPO_PEDIDO == 'Pendientes de Ordenes Trabajo') || (($rowPed->TIPO_PEDIDO == 'Traslado') && ($rowPed->TIPO_TRASLADO == 'OT Preventivo'))): //ELIMINAREMOS EL IDENTIFICADOR DE PEDIDO DE LA LINEA DE LA OT

            //BUSCO LAS LINEAS PARA ACTUALIZAR ESTADOS
            $sqlLineas    = "SELECT ID_ORDEN_TRABAJO_LINEA, CANTIDAD, ID_PEDIDO_SALIDA_LINEA
                                FROM PEDIDO_SALIDA_LINEA PSL
                                WHERE PSL.ID_PEDIDO_SALIDA = $idPedido AND PSL.ENVIADO_SAP = 1 AND PSL.ID_ORDEN_TRABAJO_LINEA IS NOT NULL";
            $resultLineas = $bd->ExecSQL($sqlLineas);
            while ($rowLinPedido = $bd->SigReg($resultLineas)):

                //BUSCO LA ORDEN DE TRABAJO LINEA
                $rowOrdenTrabajoLinea = $bd->VerReg("ORDEN_TRABAJO_LINEA", "ID_ORDEN_TRABAJO_LINEA", $rowLinPedido->ID_ORDEN_TRABAJO_LINEA);

                //GUARDAMOS LA CANTIDAD CANCELADA
                $cantidadCancelada = 0;

                if (($rowOrdenTrabajoLinea->CANTIDAD_PEDIDA - $rowLinPedido->CANTIDAD) < EPSILON_SISTEMA):
                    $sqlUpdate = "UPDATE ORDEN_TRABAJO_LINEA SET
                                    ID_PEDIDO_SALIDA = NULL
                                    , MODIFICADA = 0
                                    , TRATADA = 0
                                    , CANTIDAD_PEDIDA = 0 
                                    , CANTIDAD_CANCELADA_POR_USUARIO = 0 
                                    WHERE ID_ORDEN_TRABAJO_LINEA = $rowLinPedido->ID_ORDEN_TRABAJO_LINEA";
                    $bd->ExecSQL($sqlUpdate);

                    $cantidadCancelada += $rowOrdenTrabajoLinea->CANTIDAD_PEDIDA;
                else:
                    $sqlUpdate = "UPDATE ORDEN_TRABAJO_LINEA SET
                                    CANTIDAD_PEDIDA = CANTIDAD_PEDIDA - $rowLinPedido->CANTIDAD
                                    WHERE ID_ORDEN_TRABAJO_LINEA = $rowLinPedido->ID_ORDEN_TRABAJO_LINEA";
                    $bd->ExecSQL($sqlUpdate);

                    $cantidadCancelada += $rowLinPedido->CANTIDAD;
                endif;

                //SI LA LINEA DE LA OT TIENE ESTADO TRATAMIENTO 'En Cancelacion' LA PASO A 'Cancelada'
                if ($rowOrdenTrabajoLinea->ESTADO_TRATAMIENTO == 'En Cancelacion'):
                    $sqlUpdate = "UPDATE ORDEN_TRABAJO_LINEA SET
                                    ESTADO_TRATAMIENTO = 'Cancelada'
                                    , CANTIDAD_CANCELADA_POR_USUARIO = $cantidadCancelada
                                    WHERE ID_ORDEN_TRABAJO_LINEA = $rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO_LINEA";
                    $bd->ExecSQL($sqlUpdate);

                    //RECUPERO LA DEMANDA DE LA LINEA DE LA OT
                    $rowDemanda = $reserva->get_demanda("OT", $rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO_LINEA);

                    //SI EXISTE LA DEMANDA
                    if ($rowDemanda != false):
                        //CANCELAMOS LA COLA SI QUEDA ALGO PENDIENTE
                        $reserva->cancelar_cola($rowDemanda->ID_DEMANDA, "Anular Pedido");

                        //BUSCO SI LA LINEA DE OT TIENE MATERIAL RESERVADO PREVIAMENTE
                        $resultLineasReserva = $reserva->get_reservas_linea_ot($rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO_LINEA, "Reservada");

                        //SI TIENE MATERIAL RESERVADO NO UTILIZADO LO ANULAMOS
                        if (($resultLineasReserva != false) && ($bd->NumRegs($resultLineasReserva) > 0)):
                            $cantidadReservada = 0;

                            while ($rowLineaReserva = $bd->SigReg($resultLineasReserva)):
                                $cantidadReservada += $rowLineaReserva->CANTIDAD;
                            endwhile;

                            if ($cantidadReservada > 0):
                                //CANCELO LA RESERVA DE LA LINEA
                                $arrDevueltoReserva = $reserva->cancelar_reserva($rowDemanda->ID_DEMANDA, $cantidadReservada, "Anular Pedido");
                            endif;
                        endif;

                        //ACTUALIZAMOS ESTADO SI ES NECESARIO
                        $reserva->actualizar_estado_demanda($rowDemanda->ID_DEMANDA, "Anular Pedido");
                    endif;
                else:
                    if ($darOTLporTratadas == 'DarlasPorTratadas'):
                        $sqlUpdate = "UPDATE ORDEN_TRABAJO_LINEA SET
                                        ESTADO_TRATAMIENTO = 'Tratada'
                                        , TRATADA = 1 
                                        , FECHA_TRATAMIENTO = '" . date("Y-m-d H:i:s") . "'
                                        , TIPO_TRATAMIENTO = 'Automatico' 
                                        , TIPO_TRATAMIENTO_AUTOMATICO = 'Borrado Linea Pedido'
                                        , CANTIDAD_CANCELADA_POR_USUARIO = $cantidadCancelada
                                        WHERE ID_ORDEN_TRABAJO_LINEA = $rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO_LINEA";
                        $bd->ExecSQL($sqlUpdate);

                        //SI LOS PEDIDOS SON PENDIENTES DE OT
                        if ($rowPed->TIPO_PEDIDO == 'Pendientes de Ordenes Trabajo'):
                            //GRABO LA LINEA ANULADA
                            $sqlInsert = "INSERT INTO PEDIDO_SALIDA_LINEA_ANULADA SET
                                              ID_PEDIDO_SALIDA_LINEA = $rowLinPedido->ID_PEDIDO_SALIDA_LINEA
                                              , ID_ORDEN_TRABAJO_LINEA = $rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO_LINEA
                                              , CANTIDAD_ANULADA = $cantidadCancelada
                                              , ID_ADMINISTRADOR_ANULACION = $administrador->ID_ADMINISTRADOR
                                              , FECHA_ANULACION = '" . date("Y-m-d H:i:s") . "'
                                              , BAJA = 0";

                            $bd->ExecSQL($sqlInsert);
                        endif;
                    elseif ($darOTLporTratadas == 'DejarlasPendientesDeTratar'):
                        $sqlUpdate = "UPDATE ORDEN_TRABAJO_LINEA SET
                                        ESTADO_TRATAMIENTO = 'Pendiente Tratar'
                                        , TRATADA = 0 
                                        , FECHA_TRATAMIENTO = '0000-00-00 00:00:00'
                                        WHERE ID_ORDEN_TRABAJO_LINEA = $rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO_LINEA";
                        $bd->ExecSQL($sqlUpdate);
                    endif;

                    //BUSCAMOS LA DEMANDA NO ASOCIADA A LA LINEA DE OT
                    $rowDemandaOT = $reserva->get_demanda("OT", $rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO_LINEA);

                    if ($rowDemandaOT):

                        //GUARDAMOS LA CANTIDA DEL CAMBIO
                        $array_cambios['CANTIDAD'] = $cantidadCancelada * -1;

                        $reserva->modificacion_demanda($rowDemandaOT->ID_DEMANDA, $array_cambios);

                    endif;
                endif;

                //ACTUALIZO EL ESTADO TRATADA DE LA LINEA DE LA ORDEN DE TRABAJO
                $orden_trabajo->ActualizarLinea_Estados($rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO_LINEA);

                //BUSCO LA LINEA DE LA ORDEN DE TRABAJO ACTUALIZADA
                $rowOrdenTrabajoLineaActualizada = $bd->VerReg("ORDEN_TRABAJO_LINEA", "ID_ORDEN_TRABAJO_LINEA", $rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO_LINEA);

                //LOG MOVIMIENTOS
                $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Linea Lista Materiales OT", $rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO_LINEA, "Modificación linea lista materiales OT de forma manual por anulacion pedido salida", "ORDEN_TRABAJO_LINEA", $rowOrdenTrabajoLinea, $rowOrdenTrabajoLineaActualizada);

            endwhile;

        elseif (
            ($rowPed->TIPO_PEDIDO == 'Traslado') &&
            (($rowPed->TIPO_TRASLADO == 'Planificado Material Obligatorio') || ($rowPed->TIPO_TRASLADO == 'Planificado Material No Obligatorio') || ($rowPed->TIPO_TRASLADO == 'Planificado Material Indivisible'))
        ): //PEDIDOS DE PLANIFICADOS
            //BUSCO LAS LINEAS DE ORDEN DE TRABAO RELACIONADAS CON ESTE PEDIDO
            $sqlOTLineas    = "SELECT ID_ORDEN_TRABAJO_LINEA, CANTIDAD_CUBIERTA_POR_PEDIDO_SALIDA_LINEA, ID_PEDIDO_SALIDA_LINEA
                                FROM ORDEN_TRABAJO_LINEA_CUBIERTA_PEDIDO 
                                WHERE ID_PEDIDO_SALIDA = $rowPed->ID_PEDIDO_SALIDA AND BAJA = 0";
            $resultOTLineas = $bd->ExecSQL($sqlOTLineas);
            while ($rowOTLinea = $bd->SigReg($resultOTLineas)):

                //BUSCO LA ORDEN DE TRABAJO LINEA
                $rowOrdenTrabajoLinea = $bd->VerReg("ORDEN_TRABAJO_LINEA", "ID_ORDEN_TRABAJO_LINEA", $rowOTLinea->ID_ORDEN_TRABAJO_LINEA);

                //GUARDAMOS LA CANTIDAD CANCELADA
                $cantidadCancelada = 0;

                if (($rowOrdenTrabajoLinea->CANTIDAD_PEDIDA - $rowOTLinea->CANTIDAD_CUBIERTA_POR_PEDIDO_SALIDA_LINEA) < EPSILON_SISTEMA):
                    $sqlUpdate = "UPDATE ORDEN_TRABAJO_LINEA SET
                                    ID_PEDIDO_SALIDA = NULL
                                    , MODIFICADA = 0
                                    , TRATADA = 0
                                    , CANTIDAD_PEDIDA = 0 
                                    , CANTIDAD_CANCELADA_POR_USUARIO = 0 
                                    WHERE ID_ORDEN_TRABAJO_LINEA = $rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO_LINEA";
                    $bd->ExecSQL($sqlUpdate);

                    $cantidadCancelada += $rowOrdenTrabajoLinea->CANTIDAD_PEDIDA;
                else:
                    $sqlUpdate = "UPDATE ORDEN_TRABAJO_LINEA SET
                                    CANTIDAD_PEDIDA = CANTIDAD_PEDIDA - $rowOTLinea->CANTIDAD_CUBIERTA_POR_PEDIDO_SALIDA_LINEA
                                    WHERE ID_ORDEN_TRABAJO_LINEA = $rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO_LINEA";
                    $bd->ExecSQL($sqlUpdate);

                    $cantidadCancelada += $rowOTLinea->CANTIDAD_CUBIERTA_POR_PEDIDO_SALIDA_LINEA;
                endif;

                //SI LA LINEA DE LA OT TIENE ESTADO TRATAMIENTO 'En Cancelacion' LA PASO A 'Cancelada'
                if ($rowOrdenTrabajoLinea->ESTADO_TRATAMIENTO == 'En Cancelacion'):
                    //CANCELO LA LINEA DE OT
                    $sqlUpdate = "UPDATE ORDEN_TRABAJO_LINEA SET
                                  ESTADO_TRATAMIENTO = 'Cancelada'
                                  , CANTIDAD_CANCELADA_POR_USUARIO = $cantidadCancelada
                                  WHERE ID_ORDEN_TRABAJO_LINEA = $rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO_LINEA";
                    $bd->ExecSQL($sqlUpdate);

                    //RECUPERO LA DEMANDA DE LA LINEA DE LA OT
                    $rowDemanda = $reserva->get_demanda("OT", $rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO_LINEA);

                    //SI EXISTE LA DEMANDA
                    if ($rowDemanda != false):
                        //ANULAMOS LA COLA SI QUEDA ALGO PENDIENTE
                        $reserva->cancelar_cola($rowDemanda->ID_DEMANDA, "Anular Pedido");

                        //BUSCO SI LA LINEA DE OT TIENE MATERIAL RESERVADO PREVIAMENTE
                        $resultLineasReserva = $reserva->get_reservas_linea_ot($rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO_LINEA, "Reservada");

                        //SI TIENE MATERIAL RESERVADO NO UTILIZADO LO ANULAMOS
                        if (($resultLineasReserva != false) && ($bd->NumRegs($resultLineasReserva) > 0)):
                            $cantidadReservada = 0;

                            while ($rowLineaReserva = $bd->SigReg($resultLineasReserva)):
                                $cantidadReservada += $rowLineaReserva->CANTIDAD;
                            endwhile;

                            if ($cantidadReservada > 0):
                                //CANCELO LA RESERVA DE LA LINEA
                                $arrDevueltoReserva = $reserva->cancelar_reserva($rowDemanda->ID_DEMANDA, $cantidadReservada, "Anular Pedido");
                            endif;
                        endif;

                        //ACTUALIZAMOS ESTADO SI ES NECESARIO
                        $reserva->actualizar_estado_demanda($rowDemanda->ID_DEMANDA, "Anular Pedido");
                    endif;
                else:
                    if ($darOTLporTratadas == 'DarlasPorTratadas'):
                        $sqlUpdate = "UPDATE ORDEN_TRABAJO_LINEA SET
                                        ESTADO_TRATAMIENTO = 'Tratada'
                                        , TRATADA = 1 
                                        , FECHA_TRATAMIENTO = '" . date("Y-m-d H:i:s") . "'
                                        , TIPO_TRATAMIENTO = 'Automatico' 
                                        , TIPO_TRATAMIENTO_AUTOMATICO = 'Borrado Linea Pedido'
                                        , CANTIDAD_CANCELADA_POR_USUARIO = $cantidadCancelada
                                        WHERE ID_ORDEN_TRABAJO_LINEA = $rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO_LINEA";
                        $bd->ExecSQL($sqlUpdate);

                        //GRABO LA LINEA ANULADA
                        $sqlInsert = "INSERT INTO PEDIDO_SALIDA_LINEA_ANULADA SET
                                          ID_PEDIDO_SALIDA_LINEA = $rowOTLinea->ID_PEDIDO_SALIDA_LINEA
                                          , ID_ORDEN_TRABAJO_LINEA = $rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO_LINEA
                                          , CANTIDAD_ANULADA = $cantidadCancelada
                                          , ID_ADMINISTRADOR_ANULACION = $administrador->ID_ADMINISTRADOR
                                          , FECHA_ANULACION = '" . date("Y-m-d H:i:s") . "'
                                          , BAJA = 0";

                        $bd->ExecSQL($sqlInsert);

                    elseif ($darOTLporTratadas == 'DejarlasPendientesDeTratar'):
                        $sqlUpdate = "UPDATE ORDEN_TRABAJO_LINEA SET
                                        ESTADO_TRATAMIENTO = 'Pendiente Tratar'
                                        , TRATADA = 0 
                                        , FECHA_TRATAMIENTO = '0000-00-00 00:00:00'
                                        WHERE ID_ORDEN_TRABAJO_LINEA = $rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO_LINEA";
                        $bd->ExecSQL($sqlUpdate);
                    endif;

                    //BUSCAMOS LA DEMANDA NO ASOCIADA A LA LINEA DE OT
                    $rowDemandaOT = $reserva->get_demanda("OT", $rowOTLinea->ID_ORDEN_TRABAJO_LINEA);

                    if ($rowDemandaOT):
                        //GUARDAMOS LA CANTIDA DEL CAMBIO
                        $array_cambios['CANTIDAD'] = $cantidadCancelada * -1;

                        $reserva->modificacion_demanda($rowDemandaOT->ID_DEMANDA, $array_cambios);
                    endif;
                endif;

                //DOY DE BAJA LA LINEA DE LA ASOCIACION CON LOS PEDIDOS
                $sqlUpdate = "UPDATE ORDEN_TRABAJO_LINEA_CUBIERTA_PEDIDO SET 
                                BAJA = 1 
                                WHERE ID_ORDEN_TRABAJO_LINEA = $rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO_LINEA AND ID_PEDIDO_SALIDA = $idPedido";
                $bd->ExecSQL($sqlUpdate);

                //ACTUALIZO EL ESTADO TRATADA DE LA LINEA DE LA ORDEN DE TRABAJO
                $orden_trabajo->ActualizarLinea_Estados($rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO_LINEA);

                //BUSCO LA LINEA DE LA ORDEN DE TRABAJO ACTUALIZADA
                $rowOrdenTrabajoLineaActualizada = $bd->VerReg("ORDEN_TRABAJO_LINEA", "ID_ORDEN_TRABAJO_LINEA", $rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO_LINEA);

                //SI LOS PEDIDOS DE TRASLADO (PMO, PMNO o PMI)
                //LOG MOVIMIENTOS
                $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Linea Lista Materiales OT", $rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO_LINEA, "Modificación linea lista materiales OT de forma manual por anulacion pedido salida", "ORDEN_TRABAJO_LINEA", $rowOrdenTrabajoLinea, $rowOrdenTrabajoLineaActualizada);

                endwhile;

        elseif ($rowPed->TIPO_PEDIDO == 'Devolución a Proveedor'): //ELIMINAREMOS EL IDENTIFICADOR DE LA LINEA DE LA ANULACION DE LA LINEA DEL PEDIDO SALIDA
            $sqlUpdate = "UPDATE MOVIMIENTO_ENTRADA_LINEA SET ID_PEDIDO_SALIDA = NULL WHERE ID_PEDIDO_SALIDA = $idPedido";
            $bd->ExecSQL($sqlUpdate);
        endif;
        //FIN MODIFICACIONES ESPECIALES

        //BUSCO EL PEDIDO ACTUALIZADO
        $rowPedActualizado = $bd->VerReg("PEDIDO_SALIDA", "ID_PEDIDO_SALIDA", $idPedido);

        // LOG MOVIMIENTOS
        $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Anulación", "Pedido salida", $idPedido, "Anulacion pedido salida de forma manual", "PEDIDO_SALIDA", $rowPed, $rowPedActualizado);

        //LAMAMOS A SAP SI EL PEDIDO TIENE NUMERO DE PEDIDO SAP Y ADEMAS TIENE LINEAS ACTIVAS
        if (($rowPed->PEDIDO_SAP != "") && ($numLineasActivasPreviaAnulacion > 0) && (count( (array)$arrPedidoSalidaLinea) > 0)):

            $listaPedidoSalidaLinea = implode(",", (array) $arrPedidoSalidaLinea);
            $resultado              = $sap->InformarSAPPedidoLineaTraslado($idPedido, $listaPedidoSalidaLinea);
            if ($resultado['RESULTADO'] != 'OK'): //No se ha podido grabar la información en SAP
                foreach ($resultado['ERRORES'] as $arr):
                    foreach ($arr as $mensaje_error):
                        $strError = $strError . $mensaje_error . "<br>";
                    endforeach;
                endforeach;

                //DESHAGO LA TRANSACCION
                $bd->rollback_transaction();

                //GRABO EL ERROR PRODUCIDO EN BASE DE DATOS
                $sap->InsertarErrores($resultado);

                //HAY LINEAS SOBRE LAS QUE GENERAR UNA INCIDENCIA DE SISTEMA
                if (count( (array)$arrPedidoSalidaLineaGenerarIncidenciaSistema) > 0):
                    foreach ($arrPedidoSalidaLineaGenerarIncidenciaSistema as $idPedidoSalidaLinea):
                        //BUSCO EL TIPO INCIDENCIA 'Linea Pendiente Borrado' Y SUBTIPO 'Pedido Salida'
                        $rowISTipo    = $bd->VerReg("INCIDENCIA_SISTEMA_TIPO", "INCIDENCIA_SISTEMA_TIPO", "Linea Pendiente Borrado");
                        $rowISSubTipo = $bd->VerReg("INCIDENCIA_SISTEMA_SUBTIPO", "INCIDENCIA_SISTEMA_SUBTIPO", "Pedido Salida");

                        //BUSCAMOS SI YA EXISTE INCIDENCIA SISTEMA PARA LA LINEA
                        $GLOBALS["NotificaErrorPorEmail"] = "No";
                        $rowIncidenciaSistema             = $bd->VerRegRest("INCIDENCIA_SISTEMA", "ID_INCIDENCIA_SISTEMA_TIPO = $rowISTipo->ID_INCIDENCIA_SISTEMA_TIPO AND ID_INCIDENCIA_SISTEMA_SUBTIPO = $rowISSubTipo->ID_INCIDENCIA_SISTEMA_SUBTIPO AND ESTADO = 'Creada' AND TABLA_OBJETO = 'PEDIDO_SALIDA_LINEA' AND ID_OBJETO = $idPedidoSalidaLinea", "No");
                        unset($GLOBALS["NotificaErrorPorEmail"]);

                        //SI NO EXISTE LO CREAMOS
                        if ($rowIncidenciaSistema == false):
                            //GRABO LA INCIDENCIA DE SISTEMA
                            $sqlInsert = "INSERT INTO INCIDENCIA_SISTEMA SET
                                          ID_INCIDENCIA_SISTEMA_TIPO = $rowISTipo->ID_INCIDENCIA_SISTEMA_TIPO
                                          , TIPO = 'Linea Pendiente Borrado'
                                          , ID_INCIDENCIA_SISTEMA_SUBTIPO = $rowISSubTipo->ID_INCIDENCIA_SISTEMA_SUBTIPO
                                          , SUBTIPO = 'Pedido Salida'
                                          , ESTADO = 'Creada'
                                          , TABLA_OBJETO = 'PEDIDO_SALIDA_LINEA'
                                          , ID_OBJETO = $idPedidoSalidaLinea
                                          , FECHA_CREACION = '" . date("Y-m-d H:i:s") . "'
                                          , FECHA_RESOLUCION = '0000-00-00 00:00:00'
                                          , ID_ADMINISTRADOR_CREACION = $administrador->ID_ADMINISTRADOR
                                          , ID_ADMINISTRADOR_ULTIMA_MODIFICACION = $administrador->ID_ADMINISTRADOR
                                          , ID_LOG_EJECUCION_WS = NULL
                                          , OBSERVACIONES = ''";
                            $bd->ExecSQL($sqlInsert);
                        endif;
                    endforeach;
                endif;

                //FIN HAY LINEAS SOBRE LAS QUE GENERAR UNA INCIDENCIA DE SISTEMA

                return false;
            else:
                $sqlUpdate = "UPDATE PEDIDO_SALIDA SET
                                PEDIDO_SAP = '" . $resultado['PEDIDO'] . "'
                                , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                WHERE ID_PEDIDO_SALIDA = $idPedido";
                $bd->ExecSQL($sqlUpdate);
            endif;
        endif;

        return true;
    }

    function AnularPedidoSalidaLinea($idPedidoSalidaLinea, $darOTLporTratadas = "")
    {
        global $bd;
        global $auxiliar;
        global $html;
        global $administrador;
        global $sap;
        global $orden_trabajo;
        global $mat;
        global $incidencia_sistema;
        global $reserva;
        global $strError;
        global $necesidad;

        //CANCELO LAS NECESIDADES ASOCIADAS A LA OT
        $necesidad->CancelarNecesidadAsociadaOT($idPedidoSalidaLinea, NULL);

        //ARRAY CON LAS LINEAS MODIFICADAS
        $arrPedidoSalidaLinea = array();

        //ARRAY CON LAS LINEAS SOBRE LAS QUE GENERAR INCIDENCIA DE SISTEMA
        $arrPedidoSalidaLineaGenerarIncidenciaSistema = array();

        // BUSCO LA LINEA DEL PEDIDO
        $rowLinea = $bd->VerReg("PEDIDO_SALIDA_LINEA", "ID_PEDIDO_SALIDA_LINEA", $idPedidoSalidaLinea, "No");

        // BUSCO EL PEDIDO
        $rowPed = $bd->VerReg("PEDIDO_SALIDA", "ID_PEDIDO_SALIDA", $rowLinea->ID_PEDIDO_SALIDA, "No");

        //OBTENGO EL NUMERO DE LINEAS ACTIVAS DEL PEDIDO ANTES DE QUE SEAN ANUNLADAS
        $numLineasActivasPreviaAnulacion    = 0;
        $sqlLineasActivasPreviaAnulacion    = "SELECT * FROM PEDIDO_SALIDA_LINEA WHERE ID_PEDIDO_SALIDA = $rowPed->ID_PEDIDO_SALIDA AND BAJA = 0";
        $resultLineasActivasPreviaAnulacion = $bd->ExecSQL($sqlLineasActivasPreviaAnulacion);
        $numLineasActivasPreviaAnulacion    = $bd->NumRegs($resultLineasActivasPreviaAnulacion);

        //DOY DE BAJA LA LINEA DEL PEDIDO, NUNCA ELIMINAREMOS DATOS
        $sqlUpdate = "UPDATE PEDIDO_SALIDA_LINEA SET 
                      BAJA = 1 
                      WHERE ID_PEDIDO_SALIDA_LINEA = $rowLinea->ID_PEDIDO_SALIDA_LINEA";
        $bd->ExecSQL($sqlUpdate);

        //CALCULO EL NUMERO DE LINEAS ACTIVAS POR SI FUERA NECESARIO DAR DE BAJA EL PEDIDO
        $numLineasActivas = $bd->NumRegsTabla("PEDIDO_SALIDA_LINEA", "ID_PEDIDO_SALIDA = $rowPed->ID_PEDIDO_SALIDA AND INDICADOR_BORRADO IS NULL AND BAJA = 0");
        if ($numLineasActivas == 0):
            //DOY DE BAJA EL PEDIDO, NUNCA ELIMINAREMOS DATOS
            $sqlUpdate = "UPDATE PEDIDO_SALIDA SET BAJA = 1 WHERE ID_PEDIDO_SALIDA = $rowPed->ID_PEDIDO_SALIDA";
            $bd->ExecSQL($sqlUpdate);
        endif;

        //AÑADO LA LINEA AL ARRAY
        $arrPedidoSalidaLinea[] = $rowLinea->ID_PEDIDO_SALIDA_LINEA;

        //BUSCO SI HAY UNA ACCION PENDIENTE DE REALIZAR SOBRE ESTA LINEA DE PEDIDO SALIDA
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowAccionPendienteRealizar       = $bd->VerRegRest("ACCION_PENDIENTE_REALIZAR", "ESTADO = 'Creada' AND ACCION = 'Borrar' AND TABLA_OBJETO = 'PEDIDO_SALIDA_LINEA' AND ID_OBJETO = $rowLinea->ID_PEDIDO_SALIDA_LINEA AND BAJA = 0", "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);
        if ($rowAccionPendienteRealizar != false): //EXISTE LA ACCION COMO PENDIENTE DE REALIZAR, LA MARCO COMO FINALIZADA
            $sqlUpdate = "UPDATE ACCION_PENDIENTE_REALIZAR SET
                          ESTADO = 'Finalizada'
                          , FECHA_RESOLUCION = '" . date("Y-m-d H:i:s") . "'
                          , ID_ADMINISTRADOR_ULTIMA_MODIFICACION = $administrador->ID_ADMINISTRADOR
                          WHERE ID_ACCION_PENDIENTE_REALIZAR = $rowAccionPendienteRealizar->ID_ACCION_PENDIENTE_REALIZAR";
            $bd->ExecSQL($sqlUpdate);

            //AGREGO LA LINEA AL ARRAY DE LINEAS SOBRE LAS QUE GENERAR UNA INCIDENCIA DE SISTEMA
            $arrPedidoSalidaLineaGenerarIncidenciaSistema[] = $rowLinea->ID_PEDIDO_SALIDA_LINEA;
        endif;

        //BUSCO EL TIPO INCIDENCIA 'Linea Pendiente Borrado' Y SUBTIPO 'Pedido Salida'
        $rowISTipo    = $bd->VerReg("INCIDENCIA_SISTEMA_TIPO", "INCIDENCIA_SISTEMA_TIPO", "Linea Pendiente Borrado");
        $rowISSubTipo = $bd->VerReg("INCIDENCIA_SISTEMA_SUBTIPO", "INCIDENCIA_SISTEMA_SUBTIPO", "Pedido Salida");

        //BUSCAMOS SI EXISTE INCIDENCIA SISTEMA PARA LA LINEA
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowIncidenciaSistema             = $bd->VerRegRest("INCIDENCIA_SISTEMA", "ID_INCIDENCIA_SISTEMA_TIPO = $rowISTipo->ID_INCIDENCIA_SISTEMA_TIPO AND ID_INCIDENCIA_SISTEMA_SUBTIPO = $rowISSubTipo->ID_INCIDENCIA_SISTEMA_SUBTIPO AND ESTADO = 'Creada' AND TABLA_OBJETO = 'PEDIDO_SALIDA_LINEA' AND ID_OBJETO = $rowLinea->ID_PEDIDO_SALIDA_LINEA", "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);
        if ($rowIncidenciaSistema != false):
            //ACTUALIZO INCIDENCIA
            $incidencia_sistema->actualizarIncidencia($rowIncidenciaSistema, 'Automatica');
        endif;

        //MARCO PARA BORRADO LA LINEA DEL PEDIDO SI YA SIDO ENVIADA A SAP
        $sqlUpdate = "UPDATE PEDIDO_SALIDA_LINEA SET
                      INDICADOR_BORRADO = 'L'
                      WHERE ID_PEDIDO_SALIDA_LINEA = $rowLinea->ID_PEDIDO_SALIDA_LINEA AND ENVIADO_SAP = 1";
        $bd->ExecSQL($sqlUpdate);

        //MODIFICACIONES ESPECIALES
        if (($rowPed->TIPO_PEDIDO == 'Pendientes de Ordenes Trabajo') || (($rowPed->TIPO_PEDIDO == 'Traslado') && ($rowPed->TIPO_TRASLADO == 'OT Preventivo'))): //ELIMINAREMOS EL IDENTIFICADOR DE PEDIDO DE LA LINEA DE LA OT

            //REVISO QUE TENGA UNA LINEA DE ORDEN DE TRABAJO ASOCIADA (SE HA PODIDO INTRODUCIR ALGUNA LINEA MANUALMENTE)
            if ($rowLinea->ID_ORDEN_TRABAJO_LINEA != NULL):

                //BUSCO LA LINEA DE LA ORDEN DE TRABAJO
                $rowOrdenTrabajoLinea = $bd->VerReg("ORDEN_TRABAJO_LINEA", "ID_ORDEN_TRABAJO_LINEA", $rowLinea->ID_ORDEN_TRABAJO_LINEA);

                //GUARDAMOS LA CANTIDAD CANCELADA
                $cantidadCancelada = 0;

                if (($rowOrdenTrabajoLinea->CANTIDAD_PEDIDA - $rowLinea->CANTIDAD) < EPSILON_SISTEMA):
                    $sqlUpdate = "UPDATE ORDEN_TRABAJO_LINEA SET
                                ID_PEDIDO_SALIDA = NULL
                                , MODIFICADA = 0
                                , TRATADA = 0
                                , CANTIDAD_PEDIDA = 0 
                                , CANTIDAD_CANCELADA_POR_USUARIO = 0 
                                WHERE ID_ORDEN_TRABAJO_LINEA = $rowLinea->ID_ORDEN_TRABAJO_LINEA";
                    $bd->ExecSQL($sqlUpdate);

                    $cantidadCancelada += $rowOrdenTrabajoLinea->CANTIDAD_PEDIDA;
                else:
                    $sqlUpdate = "UPDATE ORDEN_TRABAJO_LINEA SET
                                        CANTIDAD_PEDIDA = CANTIDAD_PEDIDA - $rowLinea->CANTIDAD
                                    WHERE ID_ORDEN_TRABAJO_LINEA = $rowLinea->ID_ORDEN_TRABAJO_LINEA";
                    $bd->ExecSQL($sqlUpdate);

                    $cantidadCancelada += $rowLinea->CANTIDAD;
                endif;

                //SI LA LINEA DE LA OT TIENE ESTADO TRATAMIENTO 'En Cancelacion' LA PASO A 'Cancelada'
                if ($rowOrdenTrabajoLinea->ESTADO_TRATAMIENTO == 'En Cancelacion'):
                    $sqlUpdate = "UPDATE ORDEN_TRABAJO_LINEA SET
                                  ESTADO_TRATAMIENTO = 'Cancelada'
                                  , CANTIDAD_CANCELADA_POR_USUARIO = $cantidadCancelada
                                  WHERE ID_ORDEN_TRABAJO_LINEA = $rowLinea->ID_ORDEN_TRABAJO_LINEA";
                    $bd->ExecSQL($sqlUpdate);

                    //RECUPERO LA DEMANDA DE LA LINEA DE LA OT
                    $rowDemanda = $reserva->get_demanda("OT", $rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO_LINEA);

                    //SI EXISTE LA DEMANDA
                    if ($rowDemanda != false):
                        //CANCELAMOS LA COLA SI QUEDA ALGO PENDIENTE
                        $reserva->cancelar_cola($rowDemanda->ID_DEMANDA, "Anular Linea del Pedido");

                        //BUSCO SI LA LINEA DE OT TIENE MATERIAL RESERVADO PREVIAMENTE
                        $resultLineasReserva = $reserva->get_reservas_linea_ot($rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO_LINEA, "Reservada");

                        //SI TIENE MATERIAL RESERVADO NO UTILIZADO LO ANULAMOS
                        if (($resultLineasReserva != false) && ($bd->NumRegs($resultLineasReserva) > 0)):
                            $cantidadReservada = 0;

                            while ($rowLineaReserva = $bd->SigReg($resultLineasReserva)):
                                $cantidadReservada += $rowLineaReserva->CANTIDAD;
                            endwhile;

                            if ($cantidadReservada > 0):
                                //CANCELO LA RESERVA DE LA LINEA
                                $arrDevueltoReserva = $reserva->cancelar_reserva($rowDemanda->ID_DEMANDA, $cantidadReservada, "Anular Linea del Pedido");
                            endif;
                        endif;

                        //ACTUALIZAMOS ESTADO SI ES NECESARIO
                        $reserva->actualizar_estado_demanda($rowDemanda->ID_DEMANDA, "Anular Linea del Pedido");
                    endif;
                else:
                    if ($darOTLporTratadas == 'DarlasPorTratadas'):
                        $sqlUpdate = "UPDATE ORDEN_TRABAJO_LINEA SET
                                      ESTADO_TRATAMIENTO = 'Tratada'
                                      , TRATADA = 1 
                                      , FECHA_TRATAMIENTO = '" . date("Y-m-d H:i:s") . "'
                                      , TIPO_TRATAMIENTO = 'Automatico' 
                                      , TIPO_TRATAMIENTO_AUTOMATICO = 'Borrado Linea Pedido'
                                      , CANTIDAD_CANCELADA_POR_USUARIO = $cantidadCancelada
                                      WHERE ID_ORDEN_TRABAJO_LINEA = $rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO_LINEA";
                        $bd->ExecSQL($sqlUpdate);

                        //SI LOS PEDIDOS SON PENDIENTES DE OT
                        if (($rowPed->TIPO_PEDIDO == 'Pendientes de Ordenes Trabajo')):

                            //GRABO LA LINEA ANULADA
                            $sqlInsert = "INSERT INTO PEDIDO_SALIDA_LINEA_ANULADA SET
                                          ID_PEDIDO_SALIDA_LINEA = $idPedidoSalidaLinea
                                          , ID_ORDEN_TRABAJO_LINEA = $rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO_LINEA
                                          , CANTIDAD_ANULADA = $cantidadCancelada
                                          , ID_ADMINISTRADOR_ANULACION = $administrador->ID_ADMINISTRADOR
                                          , FECHA_ANULACION = '" . date("Y-m-d H:i:s") . "'
                                          , BAJA = 0";
                            $bd->ExecSQL($sqlInsert);

                        endif;
                    elseif ($darOTLporTratadas == 'DejarlasPendientesDeTratar'):
                        $sqlUpdate = "UPDATE ORDEN_TRABAJO_LINEA SET
                                      ESTADO_TRATAMIENTO = 'Pendiente Tratar'
                                      , TRATADA = 0 
                                      , FECHA_TRATAMIENTO = '0000-00-00 00:00:00'
                                      WHERE ID_ORDEN_TRABAJO_LINEA = $rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO_LINEA";
                        $bd->ExecSQL($sqlUpdate);
                    endif;

                    //BUSCAMOS LA DEMANDA NO ASOCIADA A LA LINEA DE OT
                    $rowDemandaOT = $reserva->get_demanda("OT", $rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO_LINEA);

                    if ($rowDemandaOT):

                        //GUARDAMOS LA CANTIDA DEL CAMBIO
                        $array_cambios['CANTIDAD'] = $cantidadCancelada * -1;

                        $reserva->modificacion_demanda($rowDemandaOT->ID_DEMANDA, $array_cambios);

                    endif;
                endif;

                //ACTUALIZO EL ESTADO TRATADA DE LA LINEA DE LA ORDEN DE TRABAJO
                $orden_trabajo->ActualizarLinea_Estados($rowLinea->ID_ORDEN_TRABAJO_LINEA, false, $darOTLporTratadas);

                //BUSCO LA LINEA DE LA ORDEN DE TRABAJO ACTUALIZADA
                $rowOrdenTrabajoLineaActualizada = $bd->VerReg("ORDEN_TRABAJO_LINEA", "ID_ORDEN_TRABAJO_LINEA", $rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO_LINEA);

                //LOG MOVIMIENTOS
                $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Linea Lista Materiales OT", $rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO_LINEA, "Modificación linea lista materiales OT de forma manual por anulacion linea pedido salida", "ORDEN_TRABAJO_LINEA", $rowOrdenTrabajoLinea, $rowOrdenTrabajoLineaActualizada);

            endif;

        elseif (
            ($rowPed->TIPO_PEDIDO == 'Traslado') &&
            (($rowPed->TIPO_TRASLADO == 'Planificado Material Obligatorio') || ($rowPed->TIPO_TRASLADO == 'Planificado Material No Obligatorio') || ($rowPed->TIPO_TRASLADO == 'Planificado Material Indivisible') || ($rowPed->TIPO_TRASLADO == 'Manual'))
        ): //PEDIDOS DE PLANIFICADOS

            //BUSCO LAS LINEAS DE ORDEN DE TRABAO RELACIONADAS CON ESTE PEDIDO
            $sqlOTLineas    = "SELECT ID_ORDEN_TRABAJO_LINEA, CANTIDAD_CUBIERTA_POR_PEDIDO_SALIDA_LINEA
                                FROM ORDEN_TRABAJO_LINEA_CUBIERTA_PEDIDO
                                WHERE ID_PEDIDO_SALIDA_LINEA = $rowLinea->ID_PEDIDO_SALIDA_LINEA AND BAJA = 0";
            $resultOTLineas = $bd->ExecSQL($sqlOTLineas);
            while ($rowOTLinea = $bd->SigReg($resultOTLineas)):

                //BUSCO LA LINEA DE LA ORDEN DE TRABAJO
                $rowOrdenTrabajoLinea = $bd->VerReg("ORDEN_TRABAJO_LINEA", "ID_ORDEN_TRABAJO_LINEA", $rowOTLinea->ID_ORDEN_TRABAJO_LINEA);

                //GUARDAMOS LA CANTIDAD CANCELADA
                $cantidadCancelada = 0;

                if (($rowOrdenTrabajoLinea->CANTIDAD_PEDIDA - $rowOTLinea->CANTIDAD_CUBIERTA_POR_PEDIDO_SALIDA_LINEA) < EPSILON_SISTEMA):
                    $sqlUpdate = "UPDATE ORDEN_TRABAJO_LINEA SET
                                    ID_PEDIDO_SALIDA = NULL
                                    , MODIFICADA = 0
                                    , TRATADA = 0
                                    , CANTIDAD_PEDIDA = 0 
                                    , CANTIDAD_CANCELADA_POR_USUARIO = 0 
                                    WHERE ID_ORDEN_TRABAJO_LINEA = $rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO_LINEA";
                    $bd->ExecSQL($sqlUpdate);

                    $cantidadCancelada += $rowOrdenTrabajoLinea->CANTIDAD_PEDIDA;
                else:
                    $sqlUpdate = "UPDATE ORDEN_TRABAJO_LINEA SET
                                    CANTIDAD_PEDIDA = CANTIDAD_PEDIDA - $rowOTLinea->CANTIDAD_CUBIERTA_POR_PEDIDO_SALIDA_LINEA
                                    WHERE ID_ORDEN_TRABAJO_LINEA = $rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO_LINEA";
                    $bd->ExecSQL($sqlUpdate);

                    $cantidadCancelada += $rowOTLinea->CANTIDAD_CUBIERTA_POR_PEDIDO_SALIDA_LINEA;
                endif;

                //SI LA LINEA DE LA OT TIENE ESTADO TRATAMIENTO 'En Cancelacion' LA PASO A 'Cancelada'
                if ($rowOrdenTrabajoLinea->ESTADO_TRATAMIENTO == 'En Cancelacion'):
                    //CANCELO LA LINEA DE OT
                    $sqlUpdate = "UPDATE ORDEN_TRABAJO_LINEA SET
                                  ESTADO_TRATAMIENTO = 'Cancelada'
                                  , CANTIDAD_CANCELADA_POR_USUARIO = $cantidadCancelada
                                  WHERE ID_ORDEN_TRABAJO_LINEA = $rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO_LINEA";
                    $bd->ExecSQL($sqlUpdate);

                    //RECUPERO LA DEMANDA DE LA LINEA DE LA OT
                    $rowDemanda = $reserva->get_demanda("OT", $rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO_LINEA);

                    //SI EXISTE LA DEMANDA
                    if ($rowDemanda != false):
                        //CANCELAMOS LA COLA SI QUEDA ALGO PENDIENTE
                        $reserva->cancelar_cola($rowDemanda->ID_DEMANDA, "Anular Linea del Pedido");

                        //BUSCO SI LA LINEA DE OT TIENE MATERIAL RESERVADO PREVIAMENTE
                        $resultLineasReserva = $reserva->get_reservas_linea_ot($rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO_LINEA, "Reservada");

                        //SI TIENE MATERIAL RESERVADO NO UTILIZADO LO ANULAMOS
                        if (($resultLineasReserva != false) && ($bd->NumRegs($resultLineasReserva) > 0)):
                            $cantidadReservada = 0;

                            while ($rowLineaReserva = $bd->SigReg($resultLineasReserva)):
                                $cantidadReservada += $rowLineaReserva->CANTIDAD;
                            endwhile;

                            if ($cantidadReservada > 0):
                                //CANCELO LA RESERVA DE LA LINEA
                                $arrDevueltoReserva = $reserva->cancelar_reserva($rowDemanda->ID_DEMANDA, $cantidadReservada, "Anular Linea del Pedido");
                            endif;
                        endif;

                        //ACTUALIZAMOS ESTADO SI ES NECESARIO
                        $reserva->actualizar_estado_demanda($rowDemanda->ID_DEMANDA, "Anular Linea del Pedido");
                    endif;
                else:
                    if ($darOTLporTratadas == 'DarlasPorTratadas'):
                        $sqlUpdate = "UPDATE ORDEN_TRABAJO_LINEA SET
                                      ESTADO_TRATAMIENTO = 'Tratada'
                                      , TRATADA = 1 
                                      , FECHA_TRATAMIENTO = '" . date("Y-m-d H:i:s") . "'
                                      , TIPO_TRATAMIENTO = 'Automatico' 
                                      , TIPO_TRATAMIENTO_AUTOMATICO = 'Borrado Linea Pedido'
                                      , CANTIDAD_CANCELADA_POR_USUARIO = $cantidadCancelada
                                      WHERE ID_ORDEN_TRABAJO_LINEA = $rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO_LINEA";
                        $bd->ExecSQL($sqlUpdate);

                        //GRABO LA LINEA ANULADA
                        $sqlInsert = "INSERT INTO PEDIDO_SALIDA_LINEA_ANULADA SET
                               ID_PEDIDO_SALIDA_LINEA = $idPedidoSalidaLinea
                               , ID_ORDEN_TRABAJO_LINEA = $rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO_LINEA
                               , CANTIDAD_ANULADA = $cantidadCancelada
                               , ID_ADMINISTRADOR_ANULACION = $administrador->ID_ADMINISTRADOR
                               , FECHA_ANULACION = '" . date("Y-m-d H:i:s") . "'
                               , BAJA = 0";
                        $bd->ExecSQL($sqlInsert);

                    elseif ($darOTLporTratadas == 'DejarlasPendientesDeTratar'):
                        $sqlUpdate = "UPDATE ORDEN_TRABAJO_LINEA SET
                                      ESTADO_TRATAMIENTO = 'Pendiente Tratar'
                                      , TRATADA = 0 
                                      , FECHA_TRATAMIENTO = '0000-00-00 00:00:00'
                                      WHERE ID_ORDEN_TRABAJO_LINEA = $rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO_LINEA";
                        $bd->ExecSQL($sqlUpdate);
                    endif;

                    //BUSCAMOS LA DEMANDA NO ASOCIADA A LA LINEA DE OT
                    $rowDemandaOT = $reserva->get_demanda("OT", $rowOTLinea->ID_ORDEN_TRABAJO_LINEA);

                    if ($rowDemandaOT):

                        //GUARDAMOS LA CANTIDA DEL CAMBIO
                        $array_cambios['CANTIDAD'] = $cantidadCancelada * -1;

                        $reserva->modificacion_demanda($rowDemandaOT->ID_DEMANDA, $array_cambios);

                    endif;

                endif;

                //DOY DE BAJA LA LINEA DE LA ASOCIACION CON LOS PEDIDOS
                $sqlUpdate = "UPDATE ORDEN_TRABAJO_LINEA_CUBIERTA_PEDIDO SET 
                              BAJA = 1 
                              WHERE ID_ORDEN_TRABAJO_LINEA = $rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO_LINEA AND ID_PEDIDO_SALIDA_LINEA = $rowLinea->ID_PEDIDO_SALIDA_LINEA";
                $bd->ExecSQL($sqlUpdate);

                //ACTUALIZO EL ESTADO TRATADA DE LA LINEA DE LA ORDEN DE TRABAJO
                $orden_trabajo->ActualizarLinea_Estados($rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO_LINEA, false, $darOTLporTratadas);

                //BUSCO LA LINEA DE LA ORDEN DE TRABAJO ACTUALIZADA
                $rowOrdenTrabajoLineaActualizada = $bd->VerReg("ORDEN_TRABAJO_LINEA", "ID_ORDEN_TRABAJO_LINEA", $rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO_LINEA);

                //LOG MOVIMIENTOS
                $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Linea Lista Materiales OT", $rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO_LINEA, "Modificación linea lista materiales OT de forma manual por anulacion linea pedido salida", "ORDEN_TRABAJO_LINEA", $rowOrdenTrabajoLinea, $rowOrdenTrabajoLineaActualizada);

            endwhile;
        endif;

        //FIN MODIFICACIONES ESPECIALES

        //BUSCO LA LINEA DEL PEDIDO ACTUALIZADA
        $rowLineaActualizada = $bd->VerReg("PEDIDO_SALIDA_LINEA", "ID_PEDIDO_SALIDA_LINEA", $rowLinea->ID_PEDIDO_SALIDA_LINEA);

        //LOG MOVIMIENTOS
        $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Anulación", "Linea Pedido Salida", $rowLinea->ID_PEDIDO_SALIDA_LINEA, "Anulacion linea pedido salida de forma manual", "PEDIDO_SALIDA_LINEA", $rowLinea, $rowLineaActualizada);

        //LAMAMOS A SAP SI EL PEDIDO TIENE NUMERO DE PEDIDO SAP Y ADEMAS TIENE LINEAS ACTIVAS
        if (($rowPed->PEDIDO_SAP != "") && ($numLineasActivasPreviaAnulacion > 0)):

            $listaPedidoSalidaLinea = implode(",", (array) $arrPedidoSalidaLinea);
            $resultado              = $sap->InformarSAPPedidoLineaTraslado($rowPed->ID_PEDIDO_SALIDA, $listaPedidoSalidaLinea);
            if ($resultado['RESULTADO'] != 'OK'): //No se ha podido grabar la información en SAP
                foreach ($resultado['ERRORES'] as $arr):
                    foreach ($arr as $mensaje_error):
                        $strError = $strError . $mensaje_error . "<br>";
                    endforeach;
                endforeach;

                //DESHAGO LA TRANSACCION
                $bd->rollback_transaction();

                //GRABO EL ERROR PRODUCIDO EN BASE DE DATOS
                $sap->InsertarErrores($resultado);

                //HAY LINEAS SOBRE LAS QUE GENERAR UNA INCIDENCIA DE SISTEMA
                if (count( (array)$arrPedidoSalidaLineaGenerarIncidenciaSistema) > 0):
                    foreach ($arrPedidoSalidaLineaGenerarIncidenciaSistema as $idPedidoSalidaLinea):
                        //BUSCO EL TIPO INCIDENCIA 'Linea Pendiente Borrado' Y SUBTIPO 'Pedido Salida'
                        $rowISTipo    = $bd->VerReg("INCIDENCIA_SISTEMA_TIPO", "INCIDENCIA_SISTEMA_TIPO", "Linea Pendiente Borrado");
                        $rowISSubTipo = $bd->VerReg("INCIDENCIA_SISTEMA_SUBTIPO", "INCIDENCIA_SISTEMA_SUBTIPO", "Pedido Salida");

                        //BUSCAMOS SI YA EXISTE INCIDENCIA SISTEMA PARA LA LINEA
                        $GLOBALS["NotificaErrorPorEmail"] = "No";
                        $rowIncidenciaSistema             = $bd->VerRegRest("INCIDENCIA_SISTEMA", "ID_INCIDENCIA_SISTEMA_TIPO = $rowISTipo->ID_INCIDENCIA_SISTEMA_TIPO AND ID_INCIDENCIA_SISTEMA_SUBTIPO = $rowISSubTipo->ID_INCIDENCIA_SISTEMA_SUBTIPO AND ESTADO = 'Creada' AND TABLA_OBJETO = 'PEDIDO_SALIDA_LINEA' AND ID_OBJETO = $idPedidoSalidaLinea", "No");
                        unset($GLOBALS["NotificaErrorPorEmail"]);

                        //SI NO EXISTE LO CREAMOS
                        if ($rowIncidenciaSistema == false):
                            //GRABO LA INCIDENCIA DE SISTEMA
                            $sqlInsert = "INSERT INTO INCIDENCIA_SISTEMA SET
                                          ID_INCIDENCIA_SISTEMA_TIPO = $rowISTipo->ID_INCIDENCIA_SISTEMA_TIPO
                                          , TIPO = 'Linea Pendiente Borrado'
                                          , ID_INCIDENCIA_SISTEMA_SUBTIPO = $rowISSubTipo->ID_INCIDENCIA_SISTEMA_SUBTIPO
                                          , SUBTIPO = 'Pedido Salida'
                                          , ESTADO = 'Creada'
                                          , TABLA_OBJETO = 'PEDIDO_SALIDA_LINEA'
                                          , ID_OBJETO = $idPedidoSalidaLinea
                                          , FECHA_CREACION = '" . date("Y-m-d H:i:s") . "'
                                          , FECHA_RESOLUCION = '0000-00-00 00:00:00'
                                          , ID_ADMINISTRADOR_CREACION = $administrador->ID_ADMINISTRADOR
                                          , ID_ADMINISTRADOR_ULTIMA_MODIFICACION = $administrador->ID_ADMINISTRADOR
                                          , ID_LOG_EJECUCION_WS = NULL
                                          , OBSERVACIONES = ''";
                            $bd->ExecSQL($sqlInsert);
                        endif;
                    endforeach;
                endif;

                //FIN HAY LINEAS SOBRE LAS QUE GENERAR UNA INCIDENCIA DE SISTEMA

                return false;
            else:
                $sqlUpdate = "UPDATE PEDIDO_SALIDA SET
                                PEDIDO_SAP = '" . $resultado['PEDIDO'] . "'
                                , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                WHERE ID_PEDIDO_SALIDA = $rowPed->ID_PEDIDO_SALIDA";
                $bd->ExecSQL($sqlUpdate);
            endif;
        endif;

        return true;
    }

    /**
     * @param $idPedidoEntradaLinea PEDIDO ENTRADA LINEA SOBRE EL QUE CALCULAR
     * @param $idOrdenTransporte e $idExpedicion SI VIENE RELLENO, DEVUELVE LA CANTIDAD ASOCIADA A OTRAS ORDENES DE TRANSPORTE /RECOGIDAS
     * @return CANTIDAD ASOCIADA A PEDIDOS CONOCIDOS SIN RECEPCION (PENDIENTE)
     */
    function ObtenerCantidadPendientePedidoConocidoAsociadaATransporte($idPedidoEntradaLinea, $idOrdenTransporte = '', $idExpedicion = '')
    {
        global $bd;


        //BUSCAMOS LA LINEA
        $rowPedidoEntradaLinea = $bd->VerReg("PEDIDO_ENTRADA_LINEA", "ID_PEDIDO_ENTRADA_LINEA", $idPedidoEntradaLinea, "No");


        //SI VIENE INDICADA ORDEN DE TRANSPORTE, FILTRAMOS POR LAS LINEAS QUE NO PERTENEZCAN A ESA ORDEN TRANSPORTE
        $sqlWhereOT = '';
        if ($idOrdenTransporte != ''):
            $sqlWhereOT = " AND E.ID_ORDEN_TRANSPORTE <> $idOrdenTransporte";
        endif;

        if ($idExpedicion != ''):
            $sqlWhereOT = " AND E.ID_EXPEDICION <> $idExpedicion";
        endif;

        //BUSCAMOS LA CANTIDAD ASIGNADA A RECOGIDAS
        $cantidadAsignada = 0;

        $sqlCantidadAsignada    = "SELECT EPC.CANTIDAD - EPC.CANTIDAD_NO_SERVIDA AS CANTIDAD_ASIGNADA, EPC.ID_EXPEDICION
                                  FROM EXPEDICION_PEDIDO_CONOCIDO EPC
                                  INNER JOIN EXPEDICION E ON E.ID_EXPEDICION = EPC.ID_EXPEDICION
                                  WHERE EPC.BAJA=0 AND EPC.ID_PEDIDO_ENTRADA_LINEA = $rowPedidoEntradaLinea->ID_PEDIDO_ENTRADA_LINEA
                                   $sqlWhereOT";
        $resultCantidadAsignada = $bd->ExecSQL($sqlCantidadAsignada);


        while ($rowCantidadAsignada = $bd->SigReg($resultCantidadAsignada)):
            //EN CASO DE QUE LA RECOGIDA ESTE ASOCIADA YA A MOVIMIENTOS; NO CUENTA
            $sqlMovimientosEntrega    = "SELECT MEL.ID_MOVIMIENTO_ENTRADA_LINEA, SUM(MEL.CANTIDAD) AS CANTIDAD_RECEPCION
                                          FROM MOVIMIENTO_ENTRADA_LINEA MEL
                                          WHERE MEL.BAJA=0 AND MEL.LINEA_ANULADA = 0 AND MEL.ID_EXPEDICION_ENTREGA=$rowCantidadAsignada->ID_EXPEDICION AND MEL.ID_PEDIDO_LINEA = $rowPedidoEntradaLinea->ID_PEDIDO_ENTRADA_LINEA";
            $resultMovimientosEntrega = $bd->ExecSQL($sqlMovimientosEntrega);

            $numMovimientosEntrega = $bd->NumRegs($resultMovimientosEntrega);
            $rowMovimientosEntrega = $bd->SigReg($resultMovimientosEntrega);
            //SI NO ESTA ASOCIADA A MOVIMIENTOS, LA CANTIDAD RESERVADA SERA LA DEL PEDIDO NO CONOCIDO
            if (($numMovimientosEntrega == 0) || ($rowMovimientosEntrega->ID_MOVIMIENTO_ENTRADA_LINEA == NULL)):
                $cantidadAsignada = $cantidadAsignada + $rowCantidadAsignada->CANTIDAD_ASIGNADA;
            else: // RESTAMOS LA PARTE DEL PEDIDO NO CONOCIDO NO ASOCIADA A MOVIMIENTOS, SI EXISTE
                if ($rowMovimientosEntrega->CANTIDAD_RECEPCION < $rowCantidadAsignada->CANTIDAD_ASIGNADA):
                    $cantidadAsignada = $cantidadAsignada + ($rowCantidadAsignada->CANTIDAD_ASIGNADA - $rowMovimientosEntrega->CANTIDAD_RECEPCION);
                endif;
            endif;

        endwhile;

        return $cantidadAsignada;
    }

    /**
     * @param $tipoPedidoSAP TIPO DE PEDIDO SAP PARA SABER SI ES RELEVANTE O NO PARA ENTREGA ENTRANTE
     * @return int VALOR DEVUELTO SOBRE SI UN TIPO DE PEDIDO SAP ES RELEVANTE O NO PARA ENTREGA ENTRANTE. POSIBLES VALORES: 0 - Cero (No Relevante) ; 1 - Uno (Relevante)
     */
    function RelevanteParaEntregaEntrante($tipoPedidoSAP)
    {
        global $bd;

        //SI EL DATO RECIBIDO ES VACIO DEVUELVO NO RELEVANTE (0 - Cero)
        if ($tipoPedidoSAP == ""):
            return 0;
        endif;

        //BUSCO EL TIPO DE PEDIDO SAP
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowTipoPedidoSAP                 = $bd->VerReg("TIPO_PEDIDO_SAP", "TIPO_PEDIDO_SAP", $tipoPedidoSAP, "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);

        //SI NO EXISTE EL TIPO DE PEDIDO SAP DEVUELVO NO RELEVANTE (0 - Cero)
        if ($rowTipoPedidoSAP == false):
            return 0;
        else:
            return $rowTipoPedidoSAP->RELEVANTE_ENTREGA_ENTRANTE;
        endif;
    }

    /**
     * @param $tipoPedidoLinea TIPO DEL PEDIDO DE LINEA (Entrada o Salida)
     * @param $idPedidoLinea IDENTIFICADOR DE LA LINEA DEL PEDIDO DE ENTRADA O SALIDA
     * @param $idObjeto SI ES DE ENTRADA IDENTIFICADOR DE LA ORDEN DE TRANSPORTE, SE BUSCARAN MOVIMIENTOS QUE NO PERTENEZCAN A RECEPCIONES ASOCIADAS A ESA ORDEN DE TRANSPORTE, SI ES DE RECOGIDA, IDENTIFICADOR DE LA RECOGIDA PARA QUE NO SE ASOCIE LA LINEA A OTRAS RECOGIDAS
     * @return mixed DEVUELVE SI LA LINEA DEL PEDIDO DE ENTRADA O SALIDA YA EXISTE EN OTRO MOVIMIENTO QUE GENERE ENTREGA ENTRANTE
     */
    function LineaExistenteParaEntregaEntrante($tipoPedidoLinea, $idPedidoLinea, $idObjeto = '')
    {
        global $bd;
        global $html;

        //DECLARO LA VARIABLE RESULTADO, POR DEFECTO FALSE
        $resultado = false;

        //SI TIPO PEDIDO LINEA ES DIFERENTE DE 'Entrada' o 'Salida' DAREMOS ERROR
        if (($tipoPedidoLinea != 'Entrada') && ($tipoPedidoLinea != 'Salida') && ($tipoPedidoLinea != 'Recogida')):
            $html->PagError("ErrorTipoPedidoLinea");
        endif;

        //BUSCO LA LINEA DEL PEDIDO
        if ($tipoPedidoLinea == 'Entrada' || $tipoPedidoLinea == 'Recogida'):
            $GLOBALS["NotificaErrorPorEmail"] = "No";
            $rowPedidoLinea                   = $bd->VerReg("PEDIDO_ENTRADA_LINEA", "ID_PEDIDO_ENTRADA_LINEA", $idPedidoLinea, "No");
            unset($GLOBALS["NotificaErrorPorEmail"]);
        elseif ($tipoPedidoLinea == 'Salida'):
            $GLOBALS["NotificaErrorPorEmail"] = "No";
            $rowPedidoLinea                   = $bd->VerReg("PEDIDO_SALIDA_LINEA", "ID_PEDIDO_SALIDA_LINEA", $idPedidoLinea, "No");
            unset($GLOBALS["NotificaErrorPorEmail"]);
        endif;

        //SI NO EXISTE LA LINEA DEL PEDIDO DAREMOS ERROR
        $html->PagErrorCondicionado($rowPedidoLinea, "==", false, "NoExisteLinePedido");

        //SI LA LINEA ES RELEVANTE PARA ENTREGA ENTRANTE, COMPRUEBO SI YA SE HAN GENERADO MOVIMIENTOS EN BASE A ESTA LINEA DE PEDIDO
        if ($rowPedidoLinea->RELEVANTE_ENTREGA_ENTRANTE == 1):
            //INICIALIZO LA VARIABLE QUE CALCULA EL NUMERO DE MOVIMIENTOS QUE GENERAN ENTREGA ENTRANTE
            $num = 0;

            //BUSCO EL TIPO DE BLOQUEO PENDIENTE DE DEVOLVER A PROVEEDOR POR CALIDAD
            $rowTipoBloqueoPdteDevolvedorProveedorCalidad = $bd->VerRegRest("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO = 'SPDPC'");

            //EN FUNCION DEL PEDIDO CALCULO EL NUMERO DE LINEAS DE MOVIMIENTO QUE GENERAN ENTREGA ENTRANTE
            if ($tipoPedidoLinea == 'Entrada'):
                //SI VIENE ORDEN DE TRANSPORTE INDICADA
                if ($idObjeto != ""):
                    //BUSCO LINEAS DE MOVIMIENTO DE ENTRADA GENERADAS EN BASE A ESTA LINEA DE PEDIDO DE ENTRADA Y QUE NO PERTENEZCAN AL TRANSPORTE
                    $sqlMovimientosRecepcion    = "SELECT * FROM MOVIMIENTO_ENTRADA_LINEA MEL
                                                INNER JOIN MOVIMIENTO_ENTRADA ME ON ME.ID_MOVIMIENTO_ENTRADA = MEL.ID_MOVIMIENTO_ENTRADA
                                                INNER JOIN MOVIMIENTO_RECEPCION MR ON MR.ID_MOVIMIENTO_RECEPCION = ME.ID_MOVIMIENTO_RECEPCION
                                                 WHERE MEL.ID_PEDIDO_LINEA = $rowPedidoLinea->ID_PEDIDO_ENTRADA_LINEA AND MEL.BAJA = 0 AND MEL.LINEA_ANULADA = 0 AND (MR.ID_ORDEN_TRANSPORTE IS NULL OR MR.ID_ORDEN_TRANSPORTE <> $idObjeto)";
                    $resultMovimientosRecepcion = $bd->ExecSQL($sqlMovimientosRecepcion);
                    $num                        = $bd->NumRegs($resultMovimientosRecepcion);
                else:
                    //BUSCO LINEAS DE MOVIMIENTO DE ENTRADA GENERADAS EN BASE A ESTA LINEA DE PEDIDO DE ENTRADA
                    $num = $bd->NumRegsTabla("MOVIMIENTO_ENTRADA_LINEA", "ID_PEDIDO_LINEA = $rowPedidoLinea->ID_PEDIDO_ENTRADA_LINEA AND BAJA = 0 AND LINEA_ANULADA = 0");
                endif;
            //PEDIDOS DE ENTRADA A AÑADIR A RECOGIDAS
            elseif ($tipoPedidoLinea == 'Recogida'):

                //BUSCO LINEAS DE MOVIMIENTO DE ENTRADA GENERADAS EN BASE A ESTA LINEA DE PEDIDO DE ENTRADA
                $num = $bd->NumRegsTabla("MOVIMIENTO_ENTRADA_LINEA", "ID_PEDIDO_LINEA = $rowPedidoLinea->ID_PEDIDO_ENTRADA_LINEA AND BAJA = 0 AND LINEA_ANULADA = 0");

                //SI VIENE ORDEN DE TRANSPORTE INDICADA
                if ($idObjeto != ""):
                    //BUSCO SI LA LINEA ESTA ASOCIADA A LA RECOGIDA DE OTRO TRANSPORTE
                    $sqlRecogidas    = "SELECT * FROM EXPEDICION_PEDIDO_CONOCIDO EPC
                                                    INNER JOIN EXPEDICION E ON E.ID_EXPEDICION = EPC.ID_EXPEDICION
                                                     WHERE EPC.ID_PEDIDO_ENTRADA_LINEA = $rowPedidoLinea->ID_PEDIDO_ENTRADA_LINEA AND EPC.BAJA = 0 AND E.ID_EXPEDICION <> $idObjeto ";
                    $resultRecogidas = $bd->ExecSQL($sqlRecogidas);
                    $num             = $num + $bd->NumRegs($resultRecogidas);
                endif;

            elseif ($tipoPedidoLinea == 'Salida'):
                //BUSCO LINEAS DE MOVIMIENTO DE SALIDA GENERADAS EN BASE A ESTA LINEA DE PEDIDO DE SALIDA
                $num = $bd->NumRegsTabla("MOVIMIENTO_SALIDA_LINEA", "ID_PEDIDO_SALIDA_LINEA = $rowPedidoLinea->ID_PEDIDO_SALIDA_LINEA AND BAJA = 0 AND LINEA_ANULADA = 0");
            endif;

            //SI EXISTE ALGUN REGISTRO, MARCAREMOS QUE LA LINEA YA SE HA INCLUIDO EN MOVIMIENTOS QUE GENERAN ENTREGA ENTRANTE
            if ($num > 0):
                //ACTUALIZO LA VARIABLE PARA DETERMINAR SI LA LINEA YA SIDO INCLUIDA EN MOVIMIENTOS QUE GENERAN ENTREGA ENTRANTE
                $resultado = true;
            endif;
        endif;

        //RETORNO EL RESULTADO SOBRE SI LA LINEA YA HA SIDO INCLUIDA EN MOVIMIENTOS QUE GENERAN ENTREGA ENTRANTE
        return $resultado;
    }

    /**
     * @param $idPedidoEntradaLinea LINEA DE PEDIDO DE ENTRADA
     * BUSCA EN LOS MOVIMIENTOS DE ENTRADA LINEA SI ALGUNO TIENE CANTIDAD DE COMPRA DECIMAL Y MARCA EL PEDIDO CON ESE PROBLEMA
     */
    function controlCantidadCompraRecepcionPedido($idPedidoEntradaLinea)
    {

        global $bd;
        global $html;
        global $mat;

        //BUSCAMOS EL PEDIDO ENTRADA LINEA
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowPedidoEntradaLinea            = $bd->VerReg("PEDIDO_ENTRADA_LINEA", "ID_PEDIDO_ENTRADA_LINEA", $idPedidoEntradaLinea, "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);

        //SI NO EXISTE LA LINEA DEL PEDIDO DAREMOS ERROR
        $html->PagErrorCondicionado($rowPedidoEntradaLinea, "==", false, "NoExisteLineaPedido");

        //BUSCAMOS LOS MOVIMIENTOS LINEA DE ESA LINEA DE PEDIDO
        $sqlMovimientosLinea    = "SELECT DISTINCT MEL.ID_MOVIMIENTO_ENTRADA_LINEA, MEL.CANTIDAD, MEL.ID_MATERIAL
                                    FROM MOVIMIENTO_ENTRADA_LINEA MEL
                                    INNER JOIN MOVIMIENTO_ENTRADA ME ON ME.ID_MOVIMIENTO_ENTRADA = MEL.ID_MOVIMIENTO_ENTRADA
                                    INNER JOIN PEDIDO_ENTRADA PE ON PE.ID_PEDIDO_ENTRADA = MEL.ID_PEDIDO
                                    INNER JOIN PEDIDO_ENTRADA_LINEA PEL ON PEL.ID_PEDIDO_ENTRADA_LINEA = MEL.ID_PEDIDO_LINEA
                                    INNER JOIN MATERIAL M ON M.ID_MATERIAL = MEL.ID_MATERIAL
                                    WHERE PEL.ID_PEDIDO_ENTRADA_LINEA = $rowPedidoEntradaLinea->ID_PEDIDO_ENTRADA_LINEA AND  M.ID_UNIDAD_COMPRA <> M.ID_UNIDAD_MEDIDA AND MEL.BAJA = 0 AND ME.BAJA = 0 AND MEL.CANTIDAD > 0 AND MEL.LINEA_ANULADA = 0";
        $resultMovimientosLinea = $bd->ExecSQL($sqlMovimientosLinea);

        $conProblemas = 0;

        //RECORREMOS LOS MOVIMIENTOS
        while (($rowMovimientosLinea = $bd->SigReg($resultMovimientosLinea)) && ($conProblemas == 0)):
            //CALCULAMOS LA CANTIDAD COMPRA
            $cantidadCompraLinea = $mat->cantUnidadCompra($rowMovimientosLinea->ID_MATERIAL, $rowMovimientosLinea->CANTIDAD);

            //SI LA CANTIDAD DE COMPRA DA DECIMAL
            if ($cantidadCompraLinea != (int)$cantidadCompraLinea):
                //SI YA TIENE PROBLEMAS CON LAS LINEAS, MARCAMOS COMO TODAS
                if ($rowPedidoEntradaLinea->TIPO_INCIDENCIA_CANTIDAD_CC == 'Cantidad de pedido no multiplo CC'):
                    //MARCAMOS LA LINEA CON TODOS LOS PROBLEMAS
                    $sqlUpdate = "UPDATE PEDIDO_ENTRADA_LINEA SET TIPO_INCIDENCIA_CANTIDAD_CC = 'Todas' WHERE ID_PEDIDO_ENTRADA_LINEA = $rowPedidoEntradaLinea->ID_PEDIDO_ENTRADA_LINEA";
                else://SI NO MARCAMOS COMO SOLO RECEPCIONES
                    //MARCAMOS LA LINEA CON RECEPCION MAL FORMADA
                    $sqlUpdate = "UPDATE PEDIDO_ENTRADA_LINEA SET TIPO_INCIDENCIA_CANTIDAD_CC = 'Cantidad recepcionada no multiplo CC' WHERE ID_PEDIDO_ENTRADA_LINEA = $rowPedidoEntradaLinea->ID_PEDIDO_ENTRADA_LINEA";
                endif;
                $bd->ExecSQL($sqlUpdate);

                //LO MARCAMOS CON PROBLEMAS
                $conProblemas = 1;
            endif;

        endwhile;
        //FIN RECORRERMOS LOS MOVIMIENTOS

        //SI NO SE HAN ANULADO LOS PROBLEMAS MANUALMENTE, ACTUALIZAMOS EL ESTADO
        if ($rowPedidoEntradaLinea->ESTADO_LINEA_CANTIDAD_CC_ERRONEO != 'Anulada Manualmente'):

            if ($conProblemas == 0):
                //ACTUALIZAMOS EL PEDIDO
                $sqlUpdate = "UPDATE PEDIDO_ENTRADA_LINEA SET ESTADO_LINEA_CANTIDAD_CC_ERRONEO ='Correcto' WHERE ID_PEDIDO_ENTRADA_LINEA = $rowPedidoEntradaLinea->ID_PEDIDO_ENTRADA_LINEA ";
                $bd->ExecSQL($sqlUpdate);

            elseif ($conProblemas == 1):
                //ACTUALIZAMOS EL PEDIDO
                $sqlUpdate = "UPDATE PEDIDO_ENTRADA_LINEA SET ESTADO_LINEA_CANTIDAD_CC_ERRONEO ='Pendiente' WHERE ID_PEDIDO_ENTRADA_LINEA = $rowPedidoEntradaLinea->ID_PEDIDO_ENTRADA_LINEA ";
                $bd->ExecSQL($sqlUpdate);
            endif;


        endif;
    }

    /**
     * @param $idPedidoEntradaLinea LINEA DE PEDIDO DE ENTRADA SOBRE LA QUE CALCULAR CUANTA CANTIDAD SE PUEDE ASIGNAR A UNA SOLICITUD DE TRANSPORTE
     * @return mixed CANTIDAD QUE SE PUEDE ASIGNAR A UNA SOLICITUD DE TRANSPORTE
     */
    function cantidadPendienteAsignarSolicitudTransporte($idPedidoEntradaLinea)
    {
        Global $bd;

        //BUSCO LA LINEA DEL PEDIDO DE ENTRADA
        $rowPedidoEntradaLinea = $bd->VerReg("PEDIDO_ENTRADA_LINEA", "ID_PEDIDO_ENTRADA_LINEA", $idPedidoEntradaLinea);

        //DECLARO LA CANTIDAD INICIAL PENDIENTE DE ASIGNAR
        $cantidadPendienteAsignar = $rowPedidoEntradaLinea->CANTIDAD_PDTE;

        //CALCULO LA CANTIDAD ASIGNADA A OTRAS SOLICITUDES DE TRANSPORTE SIN ORDENES DE RECOGIDA ASOCIADAS
        $cantidadAsignadaSolicitudesTransporteSinRecogidas = 0;

        //CALCULO LA CANTIDAD DE LAS SOLICITUDES DE TRASPORTE
        $sqlCantidadSolicitudesTransporte    = "SELECT CANTIDAD, ID_SOLICITUD_TRANSPORTE
                                                FROM SOLICITUD_TRANSPORTE_PROVEEDOR_LINEA STPL
                                                WHERE ID_PEDIDO_ENTRADA_LINEA = $rowPedidoEntradaLinea->ID_PEDIDO_ENTRADA_LINEA AND BAJA = 0";
        $resultCantidadSolicitudesTransporte = $bd->ExecSQL($sqlCantidadSolicitudesTransporte);
        while ($rowCantidadSolicitudTransporte = $bd->SigReg($resultCantidadSolicitudesTransporte)):
            //CALCULO SI LA SOLICITUD DE TRANSPORTE FORMA PARTE DE UNA ORDEN DE RECOGIDA, SI ESA ASI, LA CANTIAD DE ESTA SOLICITUD NO SE TENDRA EN CUENTA
            $numRecogidasSolicitudTransporte = $bd->NumRegsTabla("EXPEDICION", "ID_SOLICITUD_TRANSPORTE = $rowCantidadSolicitudTransporte->ID_SOLICITUD_TRANSPORTE");

            //SI NO TIENE RECOGIDAS ASOCIADAS INCREMENTO LA CANTIDAD ASIGNADA A SOLICITUDES SIN RECOGIDAS ASOCIADAS
            if ($numRecogidasSolicitudTransporte == 0):
                $cantidadAsignadaSolicitudesTransporteSinRecogidas = $cantidadAsignadaSolicitudesTransporteSinRecogidas + $rowCantidadSolicitudTransporte->CANTIDAD;
            endif;
        endwhile;

        //CALCULO LA CANTIDAD ASIGNADA A ORDENES DE RECOGIDA CON PEDIDO CONOCIDO SIN RECEPCIONAR
        $cantidadAsignadaRecogidasSinRecepcionar = 0;

        //CALCULO LA CANTIDAD EN PEDIDOS CONOCIDOS
        $sqlCantidadPedidosConocidos             = "SELECT IF(SUM(CANTIDAD) IS NULL, 0, SUM(CANTIDAD))  AS TOTAL_CANTIDAD, IF(SUM(CANTIDAD_NO_SERVIDA) IS NULL, 0, SUM(CANTIDAD_NO_SERVIDA)) AS TOTAL_CANTIDAD_NO_SERVIDA
                                        FROM EXPEDICION_PEDIDO_CONOCIDO
                                        WHERE ID_PEDIDO_ENTRADA_LINEA = $rowPedidoEntradaLinea->ID_PEDIDO_ENTRADA_LINEA AND BAJA = 0";
        $resultCantidadPedidosConocidos          = $bd->ExecSQL($sqlCantidadPedidosConocidos);
        $rowCantidadPedidosConocidos             = $bd->SigReg($resultCantidadPedidosConocidos);
        $cantidadAsignadaRecogidasSinRecepcionar = $rowCantidadPedidosConocidos->TOTAL_CANTIDAD - $rowCantidadPedidosConocidos->TOTAL_CANTIDAD_NO_SERVIDA;

        //A LO DE PEDIDO CONOCIDO dEBEMOS RECEPCIONAR LO YA ASIGNADO A MOVIMIENTOS
        $sqlCantidadPedidosConocidosEnMovimientos    = "SELECT IF(SUM(MEL.CANTIDAD) IS NULL, 0, SUM(MEL.CANTIDAD)) AS CANTIDAD_TOTAL
                                                        FROM MOVIMIENTO_ENTRADA_LINEA MEL
                                                        INNER JOIN EXPEDICION_PEDIDO_CONOCIDO EPC ON (EPC.ID_EXPEDICION = MEL.ID_EXPEDICION_ENTREGA AND EPC.ID_PEDIDO_ENTRADA = MEL.ID_PEDIDO AND EPC.ID_PEDIDO_ENTRADA_LINEA = MEL.ID_PEDIDO_LINEA)
                                                        WHERE MEL.BAJA = 0 AND MEL.LINEA_ANULADA = 0 AND EPC.ID_PEDIDO_ENTRADA_LINEA = $rowPedidoEntradaLinea->ID_PEDIDO_ENTRADA_LINEA AND EPC.BAJA = 0";
        $resultCantidadPedidosConocidosEnMovimientos = $bd->ExecSQL($sqlCantidadPedidosConocidosEnMovimientos);
        $rowCantidadPedidosConocidosEnMovimientos    = $bd->SigReg($resultCantidadPedidosConocidosEnMovimientos);
        $cantidadAsignadaRecogidasSinRecepcionar     = $cantidadAsignadaRecogidasSinRecepcionar - $rowCantidadPedidosConocidosEnMovimientos->CANTIDAD_TOTAL;

        //A LA CANTIDAD PENDIENTE DE ASIGNAR LE RESTO LO ASIGNADO SOLICITUDES DE TRANSPORTE SIN RECOGIDAS Y LO ASIGNADO A RECOGIDAS CON PEDIDO CONOCIDO NO RECEPCIONADO
        $cantidadPendienteAsignar = $cantidadPendienteAsignar - ($cantidadAsignadaSolicitudesTransporteSinRecogidas + $cantidadAsignadaRecogidasSinRecepcionar);

        //RETORNO LA CANTIDAD PENDIENTE DE ASIGNAR
        return ($cantidadPendienteAsignar < 0 ? 0 : $cantidadPendienteAsignar);
    }

    /**
     * @param $idPedidoSalidaLinea Linea de pedido de salida a asingar a una orden de preparacion de forma automaticamente en funcion del canal de entrega
     * @return $arrDevolver Array con los errores devueltos en modo string y posibles log de ws fallidos a grabar
     */
    function AsignarPedidoSalidaLineaOrdenPreparacion($idPedidoSalidaLinea)
    {
        global $bd;
        global $html;
        global $auxiliar;
        global $administrador;
        global $sap;
        global $orden_trabajo;
        global $mat;
        global $incidencia_sistema;
        global $orden_preparacion;
        global $pedido;
        global $necesidad;
        global $reserva;

        //DECLARO EL ARRAY A DEVOLVER
        $arrDevolver = array();

        //INICIALIZO LOS ERRORES A CADENA VACIA
        $strError = "";
        $strAviso = "";

        //VARIABLE PARA SABER SI HAY QUE ASIGNAR LA LINEA DE PEDIDO SALIDA A UNA ORDEN DE PREPARACION
        $asignarLineaPedidoSalida = false;
        $lineasPreparables        = true;

        //BUSCO LA LINEA DEL PEDIDO DE SALIDA
        $rowPedidoSalidaLinea = $bd->VerReg("PEDIDO_SALIDA_LINEA", "ID_PEDIDO_SALIDA_LINEA", $idPedidoSalidaLinea, "No");

        //ACCIONES EN FUNCION DE LA LINEA DEL PEDIDO DE SALIDA
        if ($rowPedidoSalidaLinea == false): //SI LA LINEA DE PEDIDO NO EXISTE MOSTRARE ERROR
            $strError = $strError . $auxiliar->traduce("La linea de pedido de salida introducida no existe", $administrador->ID_IDIOMA) . ".<br>";
        else: //LINEA EXISTENTE
            //BUSCO EL PEDIDO DE SALIDA
            $rowPedidoSalida = $bd->VerReg("PEDIDO_SALIDA", "ID_PEDIDO_SALIDA", $rowPedidoSalidaLinea->ID_PEDIDO_SALIDA);

            //CONTROL DE ERRRES A NIVEL DE CABECERA
            if ($rowPedidoSalida->PEDIDO_SAP == ""): //SI LA CABECERA DE PEDIDO NO TIENE PEDIDO SAP MOSTRARE ERROR
                $strError = $strError . $auxiliar->traduce("Nº Pedido SAP", $administrador->ID_IDIOMA) . ": " . $rowPedidoSalida->PEDIDO_SAP . " - " . $auxiliar->traduce("Nº Pedido SGA", $administrador->ID_IDIOMA) . ": " . $rowPedidoSalida->ID_PEDIDO_SALIDA . ". " . $auxiliar->traduce("Pedido no enviado a SAP", $administrador->ID_IDIOMA) . ".<br>";
            elseif ($rowPedidoSalida->BAJA != 0): //SI LA CABECERA DE PEDIDO ESTA DADO DE BAJA MOSTRARE ERROR
                $strError = $strError . $auxiliar->traduce("Nº Pedido SAP", $administrador->ID_IDIOMA) . ": " . $rowPedidoSalida->PEDIDO_SAP . " - " . $auxiliar->traduce("Nº Pedido SGA", $administrador->ID_IDIOMA) . ": " . $rowPedidoSalida->ID_PEDIDO_SALIDA . ". " . $auxiliar->traduce("Pedido no activo", $administrador->ID_IDIOMA) . ".<br>";
            else:
                //CONTROL DE LA PREPARACION TOTAL
                if ($rowPedidoSalida->PREPARACION_TOTAL == 1):
                    //SI LA LINEA NO ES PREPARABLE NO SE PREPARA NIGUNA LINEA EL PEDIDO
                    if (!$this->lineaSalidaPreparable($rowPedidoSalidaLinea->ID_PEDIDO_SALIDA_LINEA)):
                        $lineasPreparables         = false;
                        $arrIdsLineasNoPreparables = $this->idLineaSalidaNoPreparablePreparacionTotal($rowPedidoSalidaLinea->ID_PEDIDO_SALIDA_LINEA);
                        //SI DEVUELVE FALSE ES QUE LA LINEA NO PREPARABLE ES LA PROPIA LINEA
                        if (count( (array)$arrIdsLineasNoPreparables) == 0):
                            $arrIdsLineasNoPreparables[] = $rowPedidoSalidaLinea->ID_PEDIDO_SALIDA_LINEA;
                        endif;
                        //SOLO ALMACENAMOS EL ERROR SI LA LINEA QUE NO ES PREPARABLE ES LA MISMA QUE LA QUE SE LE HA PASADO A ESTA FUNCION PARA NO REPETIR ERRORES
                        if (in_array($rowPedidoSalidaLinea->ID_PEDIDO_SALIDA_LINEA, (array) $arrIdsLineasNoPreparables)):
                            //CONTROL PARA NO REPETIR LOS ERRORES DE LIENAS PREPARABLES
                            if (strpos( (string)$strAviso, $auxiliar->traduce("El pedido esta marcado con entrega total y no es preparable", $administrador->ID_IDIOMA) . ".") === false):
                                $strAviso = $strAviso . $auxiliar->traduce("El pedido esta marcado con entrega total y no es preparable", $administrador->ID_IDIOMA) . ".<br>";
                            endif;
                        endif;
                    else:
                        //BUSCO LAS LINEAS ACTIVAS DEL PEDIDO CON CANTIDAD PENDIENTE DE PREPARAR
                        $sqlLineasComprobar    = "SELECT *
                                                   FROM PEDIDO_SALIDA_LINEA PSL
                                                   WHERE PSL.ID_PEDIDO_SALIDA = $rowPedidoSalida->ID_PEDIDO_SALIDA AND PSL.CANTIDAD_PENDIENTE_SERVIR > 0 AND PSL.INDICADOR_BORRADO IS NULL AND PSL.BAJA = 0";
                        $resultLineasComprobar = $bd->ExecSQL($sqlLineasComprobar);
                        while ($rowLineaComprobar = $bd->SigReg($resultLineasComprobar)):
                            //SI LA LINEA NO ES PREPARABLE NO SE PREPARA NIGUNA LINEA EL PEDIDO
                            if (!$this->lineaSalidaPreparable($rowLineaComprobar->ID_PEDIDO_SALIDA_LINEA)):
                                $lineasPreparables = false;
                                //SOLO ALMACENAMOS EL ERROR SI LA LINEA QUE NO ES PREPARABLE ES LA MISMA QUE LA QUE SE LE HA PASADO A ESTA FUNCION PARA NO REPETIR ERRORES
                                if ($rowLineaComprobar->ID_PEDIDO_SALIDA_LINEA == $rowPedidoSalidaLinea->ID_PEDIDO_SALIDA_LINEA):
                                    //CONTROL PARA NO REPETIR LOS ERRORES DE LIENAS PREPARABLES
                                    if (strpos( (string)$strAviso, $auxiliar->traduce("El pedido esta marcado con entrega total y no es preparable", $administrador->ID_IDIOMA) . ".") === false):
                                        $strAviso = $strAviso . $auxiliar->traduce("El pedido esta marcado con entrega total y no es preparable", $administrador->ID_IDIOMA) . ".<br>";
                                    endif;
                                endif;
                            endif;
                        endwhile;
                    endif;
                endif;

                //COMPRUEBO QUE LA LINEA NO ESTE MARCADA PARA ENVIAR EL BORRADO A SAP
                $num = $bd->NumRegsTabla("ACCION_PENDIENTE_REALIZAR", "TABLA_OBJETO = 'PEDIDO_SALIDA_LINEA' AND ID_OBJETO = " . $rowPedidoSalidaLinea->ID_PEDIDO_SALIDA_LINEA . " AND ACCION = 'Borrar' AND ESTADO <> 'Finalizada'");

                //CONTROL DE ERRRES A NIVEL DE LINEA
                if ($rowPedidoSalidaLinea->RETENIDA != 0):
                    $strError = $strError . $auxiliar->traduce("Nº Pedido SAP", $administrador->ID_IDIOMA) . ": " . $rowPedidoSalida->PEDIDO_SAP . " - " . $auxiliar->traduce("Nº Pedido SGA", $administrador->ID_IDIOMA) . ": " . $rowPedidoSalida->ID_PEDIDO_SALIDA . " - " . $auxiliar->traduce("Linea", $administrador->ID_IDIOMA) . ": " . $rowPedidoSalidaLinea->LINEA_PEDIDO_SAP . ". " . $auxiliar->traduce("Linea de pedido de salida retenida", $administrador->ID_IDIOMA) . ".<br>";
                elseif (($rowPedidoSalidaLinea->INDICADOR_BORRADO != NULL) || ($rowPedidoSalidaLinea->BAJA != 0)):
                    $strError = $strError . $auxiliar->traduce("Nº Pedido SAP", $administrador->ID_IDIOMA) . ": " . $rowPedidoSalida->PEDIDO_SAP . " - " . $auxiliar->traduce("Nº Pedido SGA", $administrador->ID_IDIOMA) . ": " . $rowPedidoSalida->ID_PEDIDO_SALIDA . " - " . $auxiliar->traduce("Linea", $administrador->ID_IDIOMA) . ": " . $rowPedidoSalidaLinea->LINEA_PEDIDO_SAP . ". " . $auxiliar->traduce("Linea de pedido de salida no activa", $administrador->ID_IDIOMA) . ".<br>";
                elseif ($num > 0):
                    $strError = $strError . $auxiliar->traduce("Nº Pedido SAP", $administrador->ID_IDIOMA) . ": " . $rowPedidoSalida->PEDIDO_SAP . " - " . $auxiliar->traduce("Nº Pedido SGA", $administrador->ID_IDIOMA) . ": " . $rowPedidoSalida->ID_PEDIDO_SALIDA . " - " . $auxiliar->traduce("Linea", $administrador->ID_IDIOMA) . ": " . $rowPedidoSalidaLinea->LINEA_PEDIDO_SAP . ". " . $auxiliar->traduce("Linea en proceso de borrado", $administrador->ID_IDIOMA) . ".<br>";
                elseif (
                    ($rowPedidoSalidaLinea->ENVIADO_SAP != 1) &&
                    (
                        ($rowPedidoSalida->TIPO_PEDIDO == 'Pendientes de Ordenes Trabajo') ||
                        (($rowPedidoSalida->TIPO_PEDIDO == 'Traslado') && (($rowPedidoSalida->TIPO_TRASLADO == 'Manual') || ($rowPedidoSalida->TIPO_TRASLADO == 'Planificado Material Obligatorio') || ($rowPedidoSalida->TIPO_TRASLADO == 'Planificado Material No Obligatorio') || ($rowPedidoSalida->TIPO_TRASLADO == 'Planificado Material Indivisible') || ($rowPedidoSalida->TIPO_TRASLADO == 'OT Preventivo')))
                    )
                ):
                    $strError = $strError . $auxiliar->traduce("Nº Pedido SAP", $administrador->ID_IDIOMA) . ": " . $rowPedidoSalida->PEDIDO_SAP . " - " . $auxiliar->traduce("Nº Pedido SGA", $administrador->ID_IDIOMA) . ": " . $rowPedidoSalida->ID_PEDIDO_SALIDA . " - " . $auxiliar->traduce("Linea", $administrador->ID_IDIOMA) . ": " . $rowPedidoSalidaLinea->LINEA_PEDIDO_SAP . ". " . $auxiliar->traduce("Linea de pedido de salida no enviada a SAP", $administrador->ID_IDIOMA) . ".<br>";
                elseif (($rowPedidoSalidaLinea->REINTENTAR_SPLIT > 0)):
                    $strError = $strError . $auxiliar->traduce("Nº Pedido SAP", $administrador->ID_IDIOMA) . ": " . $rowPedidoSalida->PEDIDO_SAP . " - " . $auxiliar->traduce("Nº Pedido SGA", $administrador->ID_IDIOMA) . ": " . $rowPedidoSalida->ID_PEDIDO_SALIDA . " - " . $auxiliar->traduce("Linea", $administrador->ID_IDIOMA) . ": " . $rowPedidoSalidaLinea->LINEA_PEDIDO_SAP . ". " . $auxiliar->traduce("Linea de pedido de salida pendiente de realizar split", $administrador->ID_IDIOMA) . ".<br>";
                else:
                    //BUSCO SI TIENE LINEAS DE MOVIMIENTOS ASOCIADOS
                    $sqlMovimientoSalidaLineas    = "SELECT * 
                                                      FROM MOVIMIENTO_SALIDA_LINEA MSL 
                                                      WHERE MSL.ID_PEDIDO_SALIDA_LINEA = $rowPedidoSalidaLinea->ID_PEDIDO_SALIDA_LINEA AND MSL.LINEA_ANULADA = 0 AND MSL.BAJA = 0";
                    $resultMovimientoSalidaLineas = $bd->ExecSQL($sqlMovimientoSalidaLineas);
                    if (($resultMovimientoSalidaLineas != false) && ($bd->NumRegs($resultMovimientoSalidaLineas) > 0)): //SI LA LINEA ESTA ASIGNADA A UNA PREPARACION
                        //RECORRO LAS LINEAS DE MOVIMIENTO PARA MOSTRAR LA INFORMACION CORRESPONDIENTE
                        while ($rowMovimientoSalidaLinea = $bd->SigReg($resultMovimientoSalidaLineas)):
                            //BUSCO EL MOVIMIENTO DE SALIDA
                            $rowMovimientoSalida = $bd->VerReg("MOVIMIENTO_SALIDA", "ID_MOVIMIENTO_SALIDA", $rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA);

                            //BUSCO LA ORDEN DE RECOGIDA
                            $GLOBALS["NotificaErrorPorEmail"] = "No";
                            $rowOrdenRecogida                 = $bd->VerReg("EXPEDICION", "ID_EXPEDICION", $rowMovimientoSalidaLinea->ID_EXPEDICION, "No");
                            unset($GLOBALS["NotificaErrorPorEmail"]);

                            //BUSCO EL BULTO
                            $GLOBALS["NotificaErrorPorEmail"] = "No";
                            $rowBulto                         = $bd->VerReg("BULTO", "ID_BULTO", $rowMovimientoSalidaLinea->ID_BULTO, "No");
                            unset($GLOBALS["NotificaErrorPorEmail"]);

                            //CONFIGURO EL MENSAJE DE ERROR
                            $strError = $strError . $auxiliar->traduce("Nº Pedido SAP", $administrador->ID_IDIOMA) . ": " . $rowPedidoSalida->PEDIDO_SAP . " - " . $auxiliar->traduce("Nº Pedido SGA", $administrador->ID_IDIOMA) . ": " . $rowPedidoSalida->ID_PEDIDO_SALIDA . " - " . $auxiliar->traduce("Linea", $administrador->ID_IDIOMA) . ": " . $rowPedidoSalidaLinea->LINEA_PEDIDO_SAP . ". " . $auxiliar->traduce("Linea de pedido de salida asignada ya a una orden de preparacion", $administrador->ID_IDIOMA) . ". " . $auxiliar->traduce("Orden de preparacion", $administrador->ID_IDIOMA) . ": " . $rowMovimientoSalida->ID_ORDEN_PREPARACION . ($rowOrdenRecogida == false ? '' : ' - ' . $auxiliar->traduce("Orden de recogida", $administrador->ID_IDIOMA) . ": " . $rowOrdenRecogida->ID_EXPEDICION) . ($rowBulto == false ? '' : ' - ' . $auxiliar->traduce("Bulto", $administrador->ID_IDIOMA) . ": " . $rowBulto->REFERENCIA) . ". " . $auxiliar->traduce("Es necesario desasignar la linea para poder realizar la accion", $administrador->ID_IDIOMA) . "<br>";
                        endwhile;
                    elseif (($rowPedidoSalidaLinea->CANAL_DE_ENTREGA == 'Semiurgente') || ($rowPedidoSalidaLinea->CANAL_DE_ENTREGA == 'Urgente')): //CANAL DE ENTREGA SEMIURGENTE O URGENTE, HAY QUE ASIGNAR LA LINEA DE PEDIDO A UNA ORDEN DE PREPARACION
                        $asignarLineaPedidoSalida = true;
                    endif;
                endif;
            endif;
        endif;

        //SI LA VARIABLE PARA SABER SI HAY QUE ASIGNAR LA LINEA DE PEDIDO SALIDA A UNA ORDEN DE PREPARACION ES TRUE LA ASIGNO
        if ($asignarLineaPedidoSalida == true):

            //REVISO QUE LA LINEA NO ESTE FINALIZADA
            if ($rowPedidoSalidaLinea->ESTADO != 'Finalizada'):

                //CALCULO LA CANTIDAD EXPEDIDA
                $cantidadExpedida = 0;
                $sqlCantidadExpedida = "SELECT IF(SUM(CANTIDAD) IS NULL, 0, SUM(CANTIDAD)) AS CANTIDAD_EXPEDIDA
                                            FROM MOVIMIENTO_SALIDA_LINEA MSL
                                            WHERE MSL.ID_PEDIDO_SALIDA_LINEA = $rowPedidoSalidaLinea->ID_PEDIDO_SALIDA_LINEA AND MSL.ESTADO IN ('Expedido', 'En Tránsito', 'Recepcionado') AND MSL.LINEA_ANULADA = 0 AND MSL.BAJA = 0";
                $resultCantidadExpedida = $bd->ExecSQL($sqlCantidadExpedida);
                if ($rowCantidadExpedida = $bd->SigReg($resultCantidadExpedida)):
                    $cantidadExpedida = $rowCantidadExpedida->CANTIDAD_EXPEDIDA;
                endif;

                //REVISO SI QUEDA CANTIDAD PENDIENTE DE EXPEDIR
                if (($rowPedidoSalidaLinea->CANTIDAD - $cantidadExpedida) > EPSILON_SISTEMA):

                    //REVISO SI LA CANTIDAD PENDIENTE DE EXPEDIR TIENE UNA INCIDENCIA DE PROCESO ABIERTA
                    $transporteConIncidenciaAbierta = false;
                    $sqlNumTransportesIncidenciaAbierta = "SELECT DISTINCT OT.ID_ORDEN_TRANSPORTE
                                                            FROM MOVIMIENTO_SALIDA_LINEA MSL
                                                            INNER JOIN EXPEDICION E ON E.ID_EXPEDICION = MSL.ID_EXPEDICION
                                                            INNER JOIN ORDEN_TRANSPORTE OT ON OT.ID_ORDEN_TRANSPORTE = E.ID_ORDEN_TRANSPORTE
                                                            WHERE MSL.ID_PEDIDO_SALIDA_LINEA = $rowPedidoSalidaLinea->ID_PEDIDO_SALIDA_LINEA AND MSL.ESTADO IN ('Reservado para Preparacion', 'En Preparacion', 'Pendiente de Expedir', 'Transmitido a SAP') AND MSL.LINEA_ANULADA = 0 AND MSL.BAJA = 0 AND E.BAJA = 0 AND OT.ESTADO_INCIDENCIA = 'Abierta' AND OT.BAJA = 0";
                    $resultNumTransportesIncidenciaAbierta = $bd->ExecSQL($sqlNumTransportesIncidenciaAbierta);
                    if ($bd->NumRegs($resultNumTransportesIncidenciaAbierta) > 0):
                        $transporteConIncidenciaAbierta = true;
                    endif;

                    //SI LA CANTIDAD ESTA PENDIENTE DE EXPEDIR PERO NO HAY NINGUNA INCIDENCIA DE PROCESO ABIERTA ACTUALIZAMOS LOS VIAJES Y FECHAS
                    if ($transporteConIncidenciaAbierta == false):

                        //EXTRAIGO LAS FECHAS DE LA LINEA DE PEDIDO DE SALIDA
                        $arrFechas = $this->getShippingDate($rowPedidoSalidaLinea->ID_PEDIDO_SALIDA_LINEA);

                        //MODIFICO EL VIAJE Y LAS FECHAS DE PREPARACION/EXPIDICION/LLEGADA DE LA LINEA DE PEDIDO DE SALIDA
                        $sqlUpdate = "UPDATE PEDIDO_SALIDA_LINEA SET
                                        ID_RUTA_VIAJE_LINEA_ORIGEN = " . ($arrFechas['idRutaViajeLineaOrigen'] != "" ? $arrFechas['idRutaViajeLineaOrigen'] : "NULL") . "
                                        , ID_RUTA_SUBVIAJE_LINEA_ORIGEN = " . ($arrFechas['idRutaSubViajeLineaOrigen'] != "" ? $arrFechas['idRutaSubViajeLineaOrigen'] : "NULL") . "
                                        , ID_RUTA_VIAJE_LINEA_DESTINO = " . ($arrFechas['idRutaViajeLineaDestino'] != "" ? $arrFechas['idRutaViajeLineaDestino'] : "NULL") . "
                                        , ID_RUTA_SUBVIAJE_LINEA_DESTINO = " . ($arrFechas['idRutaSubViajeLineaDestino'] != "" ? $arrFechas['idRutaSubViajeLineaDestino'] : "NULL") . "
                                        , FECHA_ESTIMADA_PREPARACION = '" . ($arrFechas['txFechaEstimadaPreparacion'] != "" ? $arrFechas['txFechaEstimadaPreparacion'] : "0000-00-00 00:00:00") . "'
                                        , FECHA_SHIPPING = '" . ($arrFechas['txShippingDate'] != "" ? $arrFechas['txShippingDate'] : "0000-00-00 00:00:00") . "'
                                        , FECHA_ESTIMADA_LLEGADA = '" . ($arrFechas['txFechaEstimadaLLegada'] != "" ? $arrFechas['txFechaEstimadaLLegada'] : "0000-00-00 00:00:00") . "'
                                        WHERE ID_PEDIDO_SALIDA_LINEA = $rowPedidoSalidaLinea->ID_PEDIDO_SALIDA_LINEA";
                        $bd->ExecSQL($sqlUpdate);
                    endif;
                endif;
            endif;

            //SI ESTABA PENDIENTE DE TOMARSE LA DECISION LA DAMOS POR TRATADA AUTOMATICAMENTE
            if ($rowPedidoSalidaLinea->DECISION_CANAL_ENTREGA_TOMADA == 0):
                //QUITO DEL POOL DE DECISION LA LINEA DE PEDIDO DE SALIDA
                $sqlUpdate = "UPDATE PEDIDO_SALIDA_LINEA SET
                                DECISION_CANAL_ENTREGA_TOMADA = 1
                                , POOL_DECISION_CANAL_ENTREGA = 1
                                WHERE ID_PEDIDO_SALIDA_LINEA = $rowPedidoSalidaLinea->ID_PEDIDO_SALIDA_LINEA";
                $bd->ExecSQL($sqlUpdate);
            endif;

            if (!$lineasPreparables):
                //INCLUYO LOS ERRORES EN FORMATO CADENA
                $arrDevolver['ErroresPreparacion'] = $strAviso;

                //DEVUELVO LOS ERRORES EN FORMATO TEXTO
                return $arrDevolver;
            endif;

            //PRIMERO BUSCAMOS LA CANTIDAD RESERVADA
            $cantidadReservada = $reserva->get_cantidad_reservada_linea_pedido($rowPedidoSalidaLinea->ID_PEDIDO_SALIDA_LINEA, 'Reservada');

            //SI LA CANTIDAD RESERVADA NO ES SUFICIENTE, BUSCAMOS CANTIDAD DISPONIBLE
            if ($cantidadReservada < $rowPedidoSalidaLinea->CANTIDAD_PENDIENTE_SERVIR):
                //BUSCO LA CANTIDAD DISPONIBLE EN EL ALMACEN DE ORIGEN
                $cantidadDisponible = $mat->StockDisponible($rowPedidoSalidaLinea->ID_MATERIAL, $rowPedidoSalidaLinea->ID_ALMACEN_ORIGEN, $rowPedidoSalidaLinea->ID_TIPO_BLOQUEO);

                //SUMAMOS PARA SABER EL TOTAL DISPONIBLE
                $cantidadReservada = $cantidadReservada + $cantidadDisponible;
            endif;
            //DETERMINO LA CANTIDAD A ASIGNAR A LA ORDEN DE PREPARACION
            $cantidadPreparar = min($cantidadReservada, $rowPedidoSalidaLinea->CANTIDAD_PENDIENTE_SERVIR);

            //CANTIDAD A PREPARAR POSITIVA
            if ($cantidadPreparar > EPSILON_SISTEMA):
                //BUSCO EL ALMACEN DE ORIGEN
                $rowAlmacenOrigen = $bd->VerReg("ALMACEN", "ID_ALMACEN", $rowPedidoSalidaLinea->ID_ALMACEN_ORIGEN);

                //BUSCO EL CENTRO FISICO DE ORIGEN
                $rowCentroFisicoOrigen = $bd->VerReg("CENTRO_FISICO", "ID_CENTRO_FISICO", $rowAlmacenOrigen->ID_CENTRO_FISICO);

                //BUSCAMOS EL PEDIDO DE SALIDA
                $rowPedidoSalida = $bd->VerReg("PEDIDO_SALIDA", "ID_PEDIDO_SALIDA", $rowPedidoSalidaLinea->ID_PEDIDO_SALIDA);

                //EN FUNCION DEL TIPO DE PEDIDO EXTRAIGO EN TIPO DE LA ORDEN DE PREPARACION
                switch ($rowPedidoSalida->TIPO_PEDIDO):
                    case 'Traslado':
                        $idTipoOrdenAsignado = "OT";
                        break;
                    case 'Venta':
                        $idTipoOrdenAsignado = "OVC";
                        break;
                    case 'Devolución a Proveedor':
                        $idTipoOrdenAsignado = "ODP";
                        break;
                    case 'Componentes a Proveedor':
                        $idTipoOrdenAsignado = "OCP";
                        break;
                    case 'Rechazos y Anulaciones a Proveedor':
                        $idTipoOrdenAsignado = "ORAP";
                        break;
                    case 'Intra Centro Fisico':
                        $idTipoOrdenAsignado = "OICF";
                        break;
                    case 'Interno Gama':
                        $idTipoOrdenAsignado = "OIG";
                        break;
                    case 'Traslados OM Construccion':
                        $idTipoOrdenAsignado = "OTOM";
                        break;
                    case 'Pendientes de Ordenes Trabajo':
                        $idTipoOrdenAsignado = "OT";
                        break;
                endswitch;

                //BUSCO LA HORA CORTE SI EL CANAL DE ENTREGA ES SEMIURGENTE
                if ($rowPedidoSalidaLinea->CANAL_DE_ENTREGA == 'Semiurgente'):
                    if ($arrFechas['txShippingDate'] == ''):
                        $arrFechas['txShippingDate'] = $this->getSiguienteFechaShippingDate($rowPedidoSalidaLinea->ID_PEDIDO_SALIDA_LINEA);
                    endif;
                    $diaCorte  = substr( (string) $arrFechas['txShippingDate'], 0, 10);
                    $horaCorte = substr( (string) $arrFechas['txShippingDate'], 11);

                    if ($diaCorte == ''):
                        $diaCorte = date("Y-m-d");
                    endif;
                else:
                    $diaCorte = date("Y-m-d");
                endif;

                $idOrdenPreparacion = NULL;
                //CONTROL DE LA PREPARACION TOTAL
                if ($rowPedidoSalida->PREPARACION_TOTAL == 1):
                    //BUSCO LAS LINEAS ACTIVAS DEL PEDIDO CON CANTIDAD PENDIENTE DE PREPARAR
                    $sqlLineasComprobar    = "SELECT *
                                               FROM PEDIDO_SALIDA_LINEA PSL
                                               WHERE PSL.ID_PEDIDO_SALIDA = $rowPedidoSalida->ID_PEDIDO_SALIDA AND PSL.CANTIDAD_PENDIENTE_SERVIR > 0 AND PSL.INDICADOR_BORRADO IS NULL AND PSL.BAJA = 0";
                    $resultLineasComprobar = $bd->ExecSQL($sqlLineasComprobar);
                    $sqlWhere              = "";
                    while ($rowLineaComprobar = $bd->SigReg($resultLineasComprobar)):
                        //COMPRUEBO SI EXISTE UNA ORDEN DE PREPARACION EXISTENTE DONDE INCLUIR ESTA LINEA
                        $rowAlmacenOrigen       = $bd->VerReg("ALMACEN", "ID_ALMACEN", $rowLineaComprobar->ID_ALMACEN_ORIGEN);
                        $sqlOrdenPreparacion    = "SELECT * 
                                                    FROM ORDEN_PREPARACION OP 
                                                    WHERE OP.ESTADO = 'Creada' AND OP.ID_CENTRO_FISICO_ORIGEN = $rowAlmacenOrigen->ID_CENTRO_FISICO AND OP.CANAL_DE_ENTREGA = '" . $rowPedidoSalidaLinea->CANAL_DE_ENTREGA . "' AND OP.FECHA_PREVISTA_CARGA = '" . $diaCorte . "' AND OP.TIPO_ORDEN = '" . $idTipoOrdenAsignado . "' AND OP.TIPO_CREACION = 'Automatica' AND OP.BAJA = 0" . ($rowPedidoSalidaLinea->CANAL_DE_ENTREGA == 'Semiurgente' ? " AND OP.HORA_CORTE_PREPARACION_SEMIURGENTE = '" . $horaCorte . "'" : '') . " $sqlWhere";
                        $resultOrdenPreparacion = $bd->ExecSQL($sqlOrdenPreparacion);
                        //SI NO HAY ORDEN SALGO PORQUE HAY QUE CREAR UNA NUEVA
                        if (($resultOrdenPreparacion == false) || ($bd->NumRegs($resultOrdenPreparacion) == 0)):
                            break;
                        else:
                            $idsOrdenesPreparacion = "";
                            //SACO LOS IDS DE LAS ORDENES DE PREPARACION EXISTENTES EN LAS QEU PUEDO INCLUIR ESTA LINEA
                            while ($rowOrdenPreparacion = $bd->SigReg($resultOrdenPreparacion)):
                                $idsOrdenesPreparacion .= $rowOrdenPreparacion->ID_ORDEN_PREPARACION . ",";
                            endwhile;
                            $idsOrdenesPreparacion = substr( (string) $idsOrdenesPreparacion, 0, -1);
                            $sqlWhere              = " AND ID_ORDEN_PREPARACION IN($idsOrdenesPreparacion)";
                        endif;
                    endwhile;
                    $arrIdsOrdenesPreparacion = explode(",", (string)$idsOrdenesPreparacion);
                    $idOrdenPreparacion       = $arrIdsOrdenesPreparacion[0];
                else:
                    //COMPRUEBO SI EXISTE UNA ORDEN DE PREPARACION EXISTENTE DONDE INCLUIR ESTA LINEA
                    $sqlOrdenPreparacion    = "SELECT * 
                                                FROM ORDEN_PREPARACION OP 
                                                WHERE OP.ESTADO = 'Creada' AND OP.ID_CENTRO_FISICO_ORIGEN = $rowAlmacenOrigen->ID_CENTRO_FISICO AND OP.CANAL_DE_ENTREGA = '" . $rowPedidoSalidaLinea->CANAL_DE_ENTREGA . "' AND OP.FECHA_PREVISTA_CARGA = '" . $diaCorte . "' AND OP.TIPO_ORDEN = '" . $idTipoOrdenAsignado . "' AND OP.TIPO_CREACION = 'Automatica' AND OP.BAJA = 0" . ($rowPedidoSalidaLinea->CANAL_DE_ENTREGA == 'Semiurgente' ? " AND OP.HORA_CORTE_PREPARACION_SEMIURGENTE = '" . $horaCorte . "'" : '');
                    $resultOrdenPreparacion = $bd->ExecSQL($sqlOrdenPreparacion);
                    if (($resultOrdenPreparacion != false) && ($bd->NumRegs($resultOrdenPreparacion) > 0)):
                        $rowOrdenPreparacion = $bd->SigReg($resultOrdenPreparacion);
                        $idOrdenPreparacion  = $rowOrdenPreparacion->ID_ORDEN_PREPARACION;
                    endif;
                endif;

                if ($idOrdenPreparacion == NULL): //NO HAY ORDEN DE PREPARACION REAPROVECHABLE, ES NECESARIO GENERAR UNA
                    //CREAMOS LA ORDEN DE PREPARACION
                    $sqlInsert = "INSERT INTO ORDEN_PREPARACION SET
                                      FECHA = '" . date("Y-m-d") . "'
                                    , FECHA_ULTIMA_MODIFICACION = '" . date("Y-m-d") . "'
                                    , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                    , ID_ADMINISTRADOR_ULTIMA_MODIFICACION = $administrador->ID_ADMINISTRADOR
                                    , VIA_PREPARACION = '" . ($rowCentroFisicoOrigen->RADIOFRECUENCIA == 1 ? 'PDA' : 'WEB') . "' 
                                    , FECHA_PREVISTA_CARGA = '" . $diaCorte . "'
                                    , HORA_PREVISTA_CARGA = '" . date("H:i:s") . "'
                                    , ID_CENTRO_FISICO_ORIGEN = $rowCentroFisicoOrigen->ID_CENTRO_FISICO
                                    , CANAL_DE_ENTREGA = '" . $rowPedidoSalidaLinea->CANAL_DE_ENTREGA . "'
                                    , TIPO_ORDEN = '" . $idTipoOrdenAsignado . "'
                                    , TIPO_CREACION = 'Automatica'
                                    , HORA_CORTE_PREPARACION_SEMIURGENTE = '" . ($rowPedidoSalidaLinea->CANAL_DE_ENTREGA == 'Semiurgente' ? $horaCorte : '00:00:00') . "'";
                    $bd->ExecSQL($sqlInsert);
                    $idOrdenPreparacion = $bd->IdAsignado();
                endif;
                //FIN COMPRUEBO SI EXISTE UNA ORDEN DE PREPARACION EXISTENTE DONDE INCLUIR ESTA LINEA

                //DECLARO UN MOVIMIENTO NULO
                $rowMovimiento = NULL;

                //BUSCO LOS MOVIMIENTOS DE SALIDA RELACIONADOS CON EL PEDIDO DE SALIDA
                $sqlMovimientoSalida    = "SELECT *
                                            FROM MOVIMIENTO_SALIDA MS
                                            WHERE MS.ID_ORDEN_PREPARACION = $idOrdenPreparacion AND ID_PEDIDO_SALIDA = $rowPedidoSalidaLinea->ID_PEDIDO_SALIDA AND BAJA = 0";
                $resultMovimientoSalida = $bd->ExecSQL($sqlMovimientoSalida);
                while ($rowMovimientoSalida = $bd->SigReg($resultMovimientoSalida)):
                    //CALCULO EL NUMERO DE LINEAS DEL MOVIMIENTO DE SALIDA
                    $numLineas = $bd->NumRegsTabla("MOVIMIENTO_SALIDA_LINEA", "ID_MOVIMIENTO_SALIDA = $rowMovimientoSalida->ID_MOVIMIENTO_SALIDA");

                    if (($numLineas == 0) || (($rowPedidoSalida->TIPO_PEDIDO != 'Traslado') && ($rowPedidoSalida->TIPO_PEDIDO != 'Pendientes de Ordenes Trabajo'))):
                        $rowMovimiento = $rowMovimientoSalida;
                    else:
                        //RECORRO LAS LINEAS PARA COMPROBAR SI EXISTE UN MOVIMIENTO CON EL MISMO DESTINO
                        $sqlLineasMovimiento    = "SELECT *
                                                    FROM MOVIMIENTO_SALIDA_LINEA MSL
                                                    WHERE MSL.ID_MOVIMIENTO_SALIDA = $rowMovimientoSalida->ID_MOVIMIENTO_SALIDA";
                        $resultLineasMovimiento = $bd->ExecSQL($sqlLineasMovimiento);
                        While ($rowLineaMovimientoSalida = $bd->SigReg($resultLineasMovimiento)):
                            //COMPRUEBO SI COMPARTE EL MISMO DESTINO LA LINEA NUEVA CON LAS EXISTENTES EN EL MOVIMIENTO
                            if ($rowPedidoSalidaLinea->ID_ALMACEN_DESTINO == $rowLineaMovimientoSalida->ID_ALMACEN_DESTINO):
                                $rowMovimiento = $rowMovimientoSalida;
                            endif;
                        endwhile;
                    endif;
                endwhile;

                //SI NO SE HA ENCONTRADO UN MOVIMIENTO DE SALIDA VALIDO GENERO UNO NUEVO
                if ($rowMovimiento == NULL):
                    //BUSCO EL TIPO DE MOVIMIENTO
                    if ($rowPedidoSalida->TIPO_PEDIDO == 'Venta'):
                        $tipo = 'Venta';
                    elseif (($rowPedidoSalida->TIPO_PEDIDO == 'Traslado') || ($rowPedidoSalida->TIPO_PEDIDO == 'Pendientes de Ordenes Trabajo')):
                        $tipo = 'TraspasoEntreAlmacenesNoEstropeado';
                    elseif ($rowPedidoSalida->TIPO_PEDIDO == 'Componentes a Proveedor'):
                        $tipo = 'ComponentesAProveedor';
                    elseif ($rowPedidoSalida->TIPO_PEDIDO == 'Devolución a Proveedor'):
                        $tipo = 'DevolucionNoEstropeadoAProveedor';
                    elseif ($rowPedidoSalida->TIPO_PEDIDO == 'Rechazos y Anulaciones a Proveedor'):
                        $tipo = 'MaterialRechazadoAnuladoEnEntradasAProveedor';
                    elseif ($rowPedidoSalida->TIPO_PEDIDO == 'Intra Centro Fisico'):
                        $tipo = 'IntraCentroFisico';
                    elseif ($rowPedidoSalida->TIPO_PEDIDO == 'Interno Gama'):
                        $tipo = 'InternoGama';
                    elseif ($rowPedidoSalida->TIPO_PEDIDO == 'Traslados OM Construccion'):
                        $tipo = 'TrasladoOMConstruccion';
                    elseif ($rowPedidoSalida->TIPO_PEDIDO == 'Preparacion AGM'):
                        $tipo = 'PreparacionAGM';
                    else:
                        $tipo = 'TraspasoEntreAlmacenesNoEstropeado';
                    endif;

                    $sql = "INSERT INTO MOVIMIENTO_SALIDA SET
                                ID_PEDIDO_SALIDA = $rowPedidoSalidaLinea->ID_PEDIDO_SALIDA
                                , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                , ID_ORDEN_PREPARACION = $idOrdenPreparacion
                                , TIPO_MOVIMIENTO = '$tipo'
                                , FECHA = '" . date("Y/m/d H:i:s") . "'";
                    $bd->ExecSQL($sql);
                    $idMovimientoSalida = $bd->IdAsignado();
                    $rowMovimiento      = $bd->VerReg("MOVIMIENTO_SALIDA", "ID_MOVIMIENTO_SALIDA", $idMovimientoSalida);
                endif;

                //EXTRAIGO EL ARRAY CON LOS DATOS A GRABAR EN LOS MOVIMIENTOS DE SALIDA
                $arrDesubicacion = $orden_preparacion->DesubicacionDefecto($rowPedidoSalidaLinea->ID_PEDIDO_SALIDA_LINEA, $cantidadPreparar);
                if ($arrDesubicacion == NULL): //NO HAY DATOS CON LA INFORMACION A GRABAR DE ESTA LINEA DE PEDIDO DE SALIDA LINEA
                    //BUSCO EL MATERIAL
                    $rowMaterial = $bd->VerReg("MATERIAL", "ID_MATERIAL", $rowPedidoSalidaLinea->ID_MATERIAL);

                    $strError .= $auxiliar->traduce("Nº Pedido SAP", $administrador->ID_IDIOMA) . ": " . $rowPedidoSalida->PEDIDO_SAP . " - " . $auxiliar->traduce("Nº Pedido SGA", $administrador->ID_IDIOMA) . ": " . $rowPedidoSalida->ID_PEDIDO_SALIDA . " - " . $auxiliar->traduce("Linea", $administrador->ID_IDIOMA) . ": " . $rowPedidoSalidaLinea->LINEA_PEDIDO_SAP . ". " . $auxiliar->traduce("no ha podido ser asignada a la orden de preparacion por no disponer de stock no vinculado a ordenes de conteo o ubicaciones validas para preparaciones", $administrador->ID_IDIOMA) . "<br>";
                else:
                    //ARRAY PARA GUARDAR LAS LINEAS DE MOVIMIENTO GENERADAS
                    $arrLineasMovimiento = array();

                    //VARIABLE PARA GUARDAR LA CANTIDAD DE LA LINEA
                    $cantidadLineaPedidoDescontar = 0;

                    //RECORRO LAS LINEAS PARA GENERAR LAS LINEAS DE MOVIMIENTO DE SALIDA
                    foreach ($arrDesubicacion as $indice => $arrValores):

                        //CREAMOS LINEAS SI CANTIDAD MAYOR A EPSILON
                        if ($arrValores["CANTIDAD"] > EPSILON_SISTEMA):

                            //CREAMOS/ACTUALIZAMOS LA LINEA DE MOVIMIENTO CON LOS VALORES CORRESPONDIENTES
                            $idMovimientoSalidaLineaGenerado = $pedido->CrearMovimientoSalidaLinea($rowMovimiento->ID_MOVIMIENTO_SALIDA, $rowPedidoSalidaLinea->ID_PEDIDO_SALIDA_LINEA, $arrValores);

                            //GUARDAMOS LA LINEA DE MOVIMIENTO GENERADA/ACTUALIZADA
                            $arrLineasMovimiento[] = $idMovimientoSalidaLineaGenerado;

                            //ACTUALIZO LA VARIABLE DE LA CANTIDAD A DESCONTAR DE LA LINEA DEL PEDIDO DE SALIDA
                            $cantidadLineaPedidoDescontar = $cantidadLineaPedidoDescontar + $arrValores["CANTIDAD"];
                        endif;
                    endforeach;

                    //ELIMINO LAS LINEAS DUPLICADAS
                    $arrLineasMovimiento = array_unique( (array)$arrLineasMovimiento);

                    //RECORREMOS LAS LINEAS DE DE MOVIMIENTO GENERADAS/ACTUALIZADAS
                    foreach ($arrLineasMovimiento as $idLineaMovimiento):
                        //LLAMO A LA LIBRERIA NECESIDAD POR SI ES NECESARIO ASOCIAR LA NUEVA LINEA A NECESIDADES
                        $necesidad->AsociarMovimientoSalidaLineaGeneradaNuevaEnNecesidades($idLineaMovimiento);
                    endforeach;

                    //CALCULO LA CANTIDAD PEDIDO DE LA LINEA DEL PEDIDO DE SALIDA, SERA LA SUMA DE LAS CANTIDADES DE LAS LINEAS
                    $sqlCantidadLineaPedido    = "SELECT SUM(CANTIDAD) AS STOCK
                                                      FROM MOVIMIENTO_SALIDA_LINEA MSL
                                                      WHERE MSL.ID_PEDIDO_SALIDA_LINEA = $rowPedidoSalidaLinea->ID_PEDIDO_SALIDA_LINEA AND MSL.ID_MOVIMIENTO_SALIDA = $rowMovimiento->ID_MOVIMIENTO_SALIDA AND MSL.LINEA_ANULADA = 0 AND MSL.BAJA = 0";
                    $resultCantidadLineaPedido = $bd->ExecSQL($sqlCantidadLineaPedido);
                    $rowCantidadLineaPedido    = $bd->SigReg($resultCantidadLineaPedido);
                    $cantidadLineaPedido       = $rowCantidadLineaPedido->STOCK;

                    //ACTUALIZO EL VALOR CANTIDAD_PEDIDO DE LAS LINEAS DE LOS MOVIMIENTOS DE SALIDA
                    $sqlUpdate = "UPDATE MOVIMIENTO_SALIDA_LINEA MSL SET
                                    CANTIDAD_PEDIDO = $cantidadLineaPedido
                                    WHERE MSL.ID_PEDIDO_SALIDA_LINEA = $rowPedidoSalidaLinea->ID_PEDIDO_SALIDA_LINEA AND MSL.ID_MOVIMIENTO_SALIDA = $rowMovimiento->ID_MOVIMIENTO_SALIDA AND MSL.LINEA_ANULADA = 0 AND MSL.BAJA = 0";
                    $bd->ExecSQL($sqlUpdate);

                    //ACTUALIZO LA CANTIDAD PENDIENTE DE SERVIR DE LA LINEA DEL PEDIDO A PREPARAR
                    $sqlUpdate = "UPDATE PEDIDO_SALIDA_LINEA SET
                                    CANTIDAD_PENDIENTE_SERVIR = CANTIDAD_PENDIENTE_SERVIR - $cantidadLineaPedidoDescontar
                                    WHERE ID_PEDIDO_SALIDA_LINEA = $rowPedidoSalidaLinea->ID_PEDIDO_SALIDA_LINEA";
                    $bd->ExecSQL($sqlUpdate);

                    //BUSCO LA LINEA DEL PEDIDO DE SALIDA ACTUALIZADA
                    $rowPedidoSalidaLineaActualizada = $bd->VerReg("PEDIDO_SALIDA_LINEA", "ID_PEDIDO_SALIDA_LINEA", $rowPedidoSalidaLinea->ID_PEDIDO_SALIDA_LINEA);
                    if ($rowPedidoSalidaLineaActualizada->CANTIDAD_PENDIENTE_SERVIR < 0):
                        $strError .= $auxiliar->traduce("Nº Pedido SAP", $administrador->ID_IDIOMA) . ": " . $rowPedidoSalida->PEDIDO_SAP . " - " . $auxiliar->traduce("Nº Pedido SGA", $administrador->ID_IDIOMA) . ": " . $rowPedidoSalida->ID_PEDIDO_SALIDA . " - " . $auxiliar->traduce("Linea", $administrador->ID_IDIOMA) . ": " . $rowPedidoSalidaLinea->LINEA_PEDIDO_SAP . ". " . $auxiliar->traduce("no preparada porque se va a quedar con cantidad pendiente de preparar negativa", $administrador->ID_IDIOMA) . "<br>";
                    endif;

                    //MODIFICO LAS LINEAS PARA QUE SE EJECUTEN LOS TRIGGERS Y LAS LINEAS SE QUEDEN COMO CORRESPONDAN
                    $sqlUpdate = "UPDATE PEDIDO_SALIDA_LINEA SET BAJA = BAJA WHERE ID_PEDIDO_SALIDA_LINEA = $rowPedidoSalidaLinea->ID_PEDIDO_SALIDA_LINEA";
                    $bd->ExecSQL($sqlUpdate);
                    $sqlUpdate = "UPDATE NECESIDAD_LINEA SET BAJA = BAJA WHERE ID_PEDIDO_SALIDA_LINEA = $rowPedidoSalidaLinea->ID_PEDIDO_SALIDA_LINEA";
                    $bd->ExecSQL($sqlUpdate);

                    //PONGO EL PEDIDO EN ENTREGA
                    $sqlUpdate = "UPDATE PEDIDO_SALIDA SET
                                    ESTADO = 'En Entrega'
                                    WHERE ID_PEDIDO_SALIDA = $rowPedidoSalida->ID_PEDIDO_SALIDA";
                    $bd->ExecSQL($sqlUpdate);

                    //ACTUALIZAMOS EL ESTADO DE LA ORDEN DE PREPARACION EN FUNCION DE LOS ESTADOS DE LAS LINEAS
                    $orden_preparacion->ActualizarEstadoOrdenPreparacion($idOrdenPreparacion);

                    //INCLUYO LA ORDEN DE PREPARACION AL ARRAY
                    $arrDevolver['OrdenPreparacion'] = $idOrdenPreparacion;

                    // LOG MOVIMIENTOS
                    $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Orden preparación", $idOrdenPreparacion, "Asignar lineas de pedidos de salida a la orden de preparacion");

                    //VARIABLE PARA GUARDAR EL RESULTADO DE LA LLAMADA A SAP
                    $resultadoSplitLineas = NULL;

                    //SI NO SE HA ENVIADO A PREPARAR TODA LA CANTIDAD DE LA LINEA Y ES RELEVANTE PARA ENTREGA ENTRANTE
                    if ((($rowPedidoSalidaLinea->CANTIDAD - $cantidadLineaPedidoDescontar) > EPSILON_SISTEMA) && ($rowPedidoSalidaLinea->RELEVANTE_ENTREGA_ENTRANTE == 1)):
                        //DECLARO EL POSIBLE ARRAY A SOLICITAR SPLIT
                        $arrSplitPedidos = array();

                        //AÑADO LA LINEA AL ARRAY DE SPLIT A REALIZAR
                        $arrSplitPedidos[$rowPedidoSalidaLinea->ID_PEDIDO_SALIDA_LINEA] = $cantidadLineaPedidoDescontar;

                        //BUSCO EL TIPO DE INCIDENCIA SISTEMA 'Split Linea Pedido'
                        $rowISTipo    = $bd->VerReg("INCIDENCIA_SISTEMA_TIPO", "INCIDENCIA_SISTEMA_TIPO", "Split Linea Pedido");
                        $rowISSubTipo = $bd->VerReg("INCIDENCIA_SISTEMA_SUBTIPO", "INCIDENCIA_SISTEMA_SUBTIPO", "Split fallido en orden preparacion");

                        //HAGO LA LLAMADA A SAP PARA INDICARLE EL SPLIT QUE TIENE QUE REALIZAR
                        $strErrorSplit = "";
                        $resultado     = $sap->SplitPedido($arrSplitPedidos, "Salida");
                        if ($resultado['RESULTADO'] != 'OK'): //No se ha podido grabar la información en SAP
                            foreach ($resultado['ERRORES'] as $arr):
                                foreach ($arr as $mensaje_error):
                                    $strErrorSplit = $strErrorSplit . $mensaje_error . ".\n";
                                    $strError      = $strError . $auxiliar->traduce("Error al solicitar split", $administrador->ID_IDIOMA) . ". ";
                                endforeach;
                            endforeach;

                            //ME GUARDO EL RESULTADO DE LA LLAMADA A SAP
                            $resultadoSplitLineas = $resultado;

                            //INCLUYO EL WEB SERVICE ERRONEO A GRABAR
                            $arrDevolver['ErrorResultadoSplit'] = $resultadoSplitLineas;

                            //INCLUYO LOS ERRORES DEVUELTOS POR A LLAMAR AL SPLIT
                            $arrDevolver['ErrorSplit'] = $strErrorSplit;

                            //GUARDAMOS QUE SE DEBE REINTENTAR EL SPLIT
                            $sqlUpdate = "UPDATE PEDIDO_SALIDA_LINEA SET REINTENTAR_SPLIT = 1 WHERE ID_PEDIDO_SALIDA_LINEA = $rowPedidoSalidaLinea->ID_PEDIDO_SALIDA_LINEA";
                            $bd->ExecSQL($sqlUpdate);

                            /* GRABO LA INCIDENCIA TRAS EL FALLO EN EL SPLIT */
                            //EXTRAIGO LOS DATOS DEL $resultadoSplitLineas A GRABAR. SI LOS WS ESTÁN DESACTIVADOS, GUARDO UN VALOR POR DEFECTO (1) PARA QUE NO FALLE LA CONSULTA
                            $idLogEjecucionWS = $resultadoSplitLineas['LOG_ERROR']['EXTERNALREFID'];

                            //COMPRUEBO QUE LA INCIDENDIA NO ESTÉ YA CREADA
                            $numIncidenciasIguales = $bd->NumRegsTabla("INCIDENCIA_SISTEMA I_S", "I_S.ID_INCIDENCIA_SISTEMA_TIPO = $rowISTipo->ID_INCIDENCIA_SISTEMA_TIPO AND I_S.ID_INCIDENCIA_SISTEMA_SUBTIPO = $rowISSubTipo->ID_INCIDENCIA_SISTEMA_SUBTIPO AND I_S.TABLA_OBJETO = 'PEDIDO_SALIDA_LINEA' AND I_S.ID_OBJETO = $rowPedidoSalidaLinea->ID_PEDIDO_SALIDA_LINEA AND I_S.ESTADO <> 'Finalizada'");

                            //SI NO HAY NINGUNA AÚN, GRABO LA INCIDENCIA DE SISTEMA
                            if ($numIncidenciasIguales == 0):
                                $sqlInsert = "INSERT INTO INCIDENCIA_SISTEMA SET
                                                    ID_INCIDENCIA_SISTEMA_TIPO = $rowISTipo->ID_INCIDENCIA_SISTEMA_TIPO
                                                  , TIPO = 'Split Linea Pedido'
                                                  , ID_INCIDENCIA_SISTEMA_SUBTIPO = $rowISSubTipo->ID_INCIDENCIA_SISTEMA_SUBTIPO
                                                  , SUBTIPO = 'Split fallido en orden preparacion'
                                                  , ESTADO = 'Creada'
                                                  , TABLA_OBJETO = 'PEDIDO_SALIDA_LINEA'
                                                  , ID_OBJETO = $rowPedidoSalidaLinea->ID_PEDIDO_SALIDA_LINEA
                                                  , FECHA_CREACION = '" . date("Y-m-d H:i:s") . "'
                                                  , FECHA_RESOLUCION = '0000-00-00 00:00:00'
                                                  , ID_ADMINISTRADOR_CREACION = $administrador->ID_ADMINISTRADOR
                                                  , ID_ADMINISTRADOR_ULTIMA_MODIFICACION = $administrador->ID_ADMINISTRADOR
                                                  , ID_LOG_EJECUCION_WS = " . (empty($idLogEjecucionWS) ? 1 : $idLogEjecucionWS) . " 
                                                  , OBSERVACIONES = ''";
                                $bd->ExecSQL($sqlInsert);
                            endif;
                        /* FIN GRABO LA INCIDENCIA TRAS EL FALLO EN EL SPLIT */
                        else:
                            //COMPRUEBO SI EXISTE UNA INCIDENCIA PARA ESTE PEDIDO_SALIDA_LINEA
                            $GLOBALS["NotificaErrorPorEmail"] = "No";
                            $rowIncidenciaSistema             = null;
                            $rowIncidenciaSistema             = $bd->VerRegRest("INCIDENCIA_SISTEMA I_S", "I_S.ID_INCIDENCIA_SISTEMA_TIPO = $rowISTipo->ID_INCIDENCIA_SISTEMA_TIPO AND I_S.ID_INCIDENCIA_SISTEMA_SUBTIPO = $rowISSubTipo->ID_INCIDENCIA_SISTEMA_SUBTIPO AND I_S.TABLA_OBJETO = 'PEDIDO_SALIDA_LINEA' AND I_S.ID_OBJETO = $rowPedidoSalidaLinea->ID_PEDIDO_SALIDA_LINEA AND I_S.ESTADO <> 'Finalizada'", "No");
                            unset($GLOBALS["NotificaErrorPorEmail"]);

                            //SI EXISTE LA INCIDENCIA, LA RESUELVO
                            if ($rowIncidenciaSistema):
                                $incidencia_sistema->actualizarIncidencia($rowIncidenciaSistema, 'Automatica');
                            endif;
                        endif;
                        //FIN HAGO LA LLAMADA A SAP PARA INDICARLE EL SPLIT QUE TIENE QUE REALIZAR
                    endif;
                    //FIN SI NO SE HA ENVIADO A PREPARAR TODA LA CANTIDAD DE LA LINEA Y ES RELEVANTE PARA ENTREGA ENTRANTE

                    //SI NO SE HAN PRODUCIDO ERRORES EN LA SOLICITUD DE SPLIT ENVIO EL BLOQUEO
                    if ($resultadoSplitLineas == NULL):
                        //DECLARO EL ARRAY CON LOS PEDIDOS IMPLICADOS
                        $arrayLineasPedidosInvolucradas = array();

                        //GUARDO LOS PEDIDOS DE SALIDA INVOLUCRADOS EN ESTE MOVIMIENTO
                        $arrayLineasPedidosInvolucradas[] = $rowPedidoSalidaLinea->ID_PEDIDO_SALIDA_LINEA;

                        //INFORMO A SAP DE LAS LINEAS BLOQUEADAS
                        $strErrorBloqueo = "";
                        $resultado       = $pedido->controlBloqueoLinea("Salida", 'GenerarMovimientos', implode(",", (array) $arrayLineasPedidosInvolucradas));
                        if ($resultado['RESULTADO'] != 'OK'): //No se ha podido grabar la información en SAP
                            if (count( (array)$resultado['ERRORES']) > 0):
                                foreach ($resultado['ERRORES'] as $arr):
                                    foreach ($arr as $mensaje_error):
                                        $strErrorBloqueo = $strErrorBloqueo . $mensaje_error . "<br>";
                                    endforeach;
                                endforeach;
                            endif;

                            //ME GUARDO EL RESULTADO DE LA LLAMADA A SAP
                            $resultadoBloqueoLineas = $resultado;

                            //INCLUYO EL WEB SERVICE ERRONEO A GRABAR
                            $arrDevolver['ErrorResultadoBloqueo'] = $resultadoBloqueoLineas;

                            //INCLUYO LOS ERRORES DEVUELTOS POR A LLAMAR AL BLOQUEO
                            $arrDevolver['ErrorBloqueo'] = $strErrorBloqueo;
                        endif;
                    endif;
                    //FIN SI NO SE HAN PRODUCIDO ERRORES EN LA SOLICITUD DE SPLIT ENVIO EL BLOQUEO
                endif;
            //FIN EXTRAIGO EL ARRAY CON LOS DATOS A GRABAR EN LOS MOVIMIENTOS DE SALIDA
            else:
                $strAviso = $strAviso . $auxiliar->traduce("No hay suficiente cantidad disponible para preparar la orden de preparacion", $administrador->ID_IDIOMA) . ".<br>";

                //INCLUYO LOS ERRORES EN FORMATO CADENA
                $arrDevolver['ErroresPreparacion'] = $strAviso;

                //DEVUELVO LOS ERRORES EN FORMATO TEXTO
                return $arrDevolver;
            endif;
            //FIN CANTIDAD A PREPARAR POSITIVA
        endif;

        //INCLUYO LOS ERRORES EN FORMATO CADENA
        $arrDevolver['Errores'] = $strError;

        //DEVUELVO LOS ERRORES EN FORMATO TEXTO
        return $arrDevolver;
    }

    /**
     * Función para obtener de una línea de pedido de salida las fechas correspondientes
     * @param $idPedidoSalidaLinea
     * @param $calcularProximoViaje
     */
    function getShippingDate($idPedidoSalidaLinea, $calcularProximoViaje = true)
    {
        global $bd;
        global $auxiliar;

        //ARRAY PARA ALMACENAR LAS FECHAS CALCULADAS
        $arrDatosShipping = array();

        //BUSCO LA LINEA DEL PEDIDO DE SALIDA
        $rowPedidoSalidaLinea = $bd->VerReg("PEDIDO_SALIDA_LINEA", "ID_PEDIDO_SALIDA_LINEA", $idPedidoSalidaLinea);

        //ACCIONES EN FUNCION DE SI LA LINEA DEL PEDIDO DE SALIDA ES ESTANDAR, SEMIURGENTE O URGENTE
        if ($rowPedidoSalidaLinea->CANAL_DE_ENTREGA == 'Estandar'):
            //BUSCAMOS EL ALMACEN DE DESTINO PARA REVISAR LOS CICLOS DE ENTREGA
            $rowAlmacenDestino = $bd->VerReg("ALMACEN", "ID_ALMACEN", $rowPedidoSalidaLinea->ID_ALMACEN_DESTINO);

            //OBTENEMOS EL SHIPPING DATE
            $arrDatosShipping = Planificado::getShippingDate($rowPedidoSalidaLinea->ID_ALMACEN_ORIGEN, $rowPedidoSalidaLinea->ID_ALMACEN_DESTINO, $rowPedidoSalidaLinea->FECHA_ENTREGA, $rowAlmacenDestino->NUMERO_CICLOS_ANTELACION, $calcularProximoViaje, true);

        elseif ($rowPedidoSalidaLinea->CANAL_DE_ENTREGA == 'Semiurgente'):
            //BUSCO EL ALMACEN DE ORIGEN
            $rowAlmacenOrigen = $bd->VerReg("ALMACEN", "ID_ALMACEN", $rowPedidoSalidaLinea->ID_ALMACEN_ORIGEN);

            //BUSCO EL CENTRO FISICO DE ORIGEN
            $rowCFOrigen  = $bd->VerReg("CENTRO_FISICO", "ID_CENTRO_FISICO", $rowAlmacenOrigen->ID_CENTRO_FISICO);
            $minsMinimos  = $rowCFOrigen->TIEMPO_MINIMO_CREAR_ORDEN_PREPARACION_MINUTOS;
            $horasMinimas = $rowCFOrigen->TIEMPO_MINIMO_CREAR_ORDEN_PREPARACION_HORAS;

            $fechaCompletaActual = date("Y-m-d H:i:s");
            if ($rowCFOrigen->ID_HUSO_HORARIO != ''):
                $rowHusoHorario      = $bd->VerReg("HUSO_HORARIO_", "ID_HUSO_HORARIO", $rowCFOrigen->ID_HUSO_HORARIO);
                $fechaCompletaActual = $auxiliar->fechaToTimezoneUserParam($rowHusoHorario->ID_HUSO_HORARIO_PHP, $fechaCompletaActual);
            endif;
            $fechaActual = explode(' ', $fechaCompletaActual)[0];

            //BUSCO EL CALENDARIO DEL CENTRO FISICO DE ORIGEN
            $rowCalendarioCF = $bd->VerRegRest("CALENDARIO_FESTIVOS", "ID_CENTRO_FISICO = $rowCFOrigen->ID_CENTRO_FISICO AND YEAR = '" . date('Y') . "' AND BAJA = 0", "No");

            //SI NO SE HA ENCONTRADO EL CALENDARIO ANUAL DEL CENTRO FISICO DE ORIGEN DEVOLVEMOS LOS DATOS VACIOS
            if ($rowCalendarioCF == false):
                //GUARDAMOS EL VIAJE
                $arrDatosShipping['idRutaViajeLineaOrigen']     = "";
                $arrDatosShipping['idRutaSubViajeLineaOrigen']  = "";
                $arrDatosShipping['idRutaViajeLineaDestino']    = "";
                $arrDatosShipping['idRutaSubViajeLineaDestino'] = "";

                //GUARDAMOS LAS FECHAS CALCULADAS
                $arrDatosShipping['txFechaEstimadaPreparacion'] = "";
                $arrDatosShipping['txShippingDate']             = "";
                $arrDatosShipping['txFechaEstimadaLLegada']     = "";

                return $arrDatosShipping;
            else:
                //BUSCO EL HORARIO DEL DIA ACTUAL DEL CENTRO FISICO DE ORIGEN
                $rowHorarioCF = $bd->VerRegRest("CALENDARIO_FESTIVOS_HORARIO", "ID_CALENDARIO_FESTIVOS = $rowCalendarioCF->ID_CALENDARIO_FESTIVOS AND FECHA = '" . date("Y-m-d") . "'", "No");

                //SI NO SE HA ENCONTRADO EL HORARIO DEL DIA ACTUAL DEL CENTRO FISICO DE ORIGEN DEVOLVEMOS LOS DATOS VACIOS
                if ($rowHorarioCF == false):
                    //GUARDAMOS EL VIAJE
                    $arrDatosShipping['idRutaViajeLineaOrigen']     = "";
                    $arrDatosShipping['idRutaSubViajeLineaOrigen']  = "";
                    $arrDatosShipping['idRutaViajeLineaDestino']    = "";
                    $arrDatosShipping['idRutaSubViajeLineaDestino'] = "";

                    //GUARDAMOS LAS FECHAS CALCULADAS
                    $arrDatosShipping['txFechaEstimadaPreparacion'] = "";
                    $arrDatosShipping['txShippingDate']             = "";
                    $arrDatosShipping['txFechaEstimadaLLegada']     = "";

                    return $arrDatosShipping;
                endif;
            endif;

            //REVISAMOS SI TIENE HORA DE FIN POR LA TARDE Y SI NO COGEMOS LA DE LA MAÑANA
            if ($rowHorarioCF->HORA_FIN2 != NULL):
                $campoHoraCierreCF = "HORA_FIN2";
            else:
                $campoHoraCierreCF = "HORA_FIN";
            endif;

            //SI EL CAMPO MINUTOS VIENE VACIO LO INICIALIZO A CERO
            if ($minsMinimos == ""):
                $minsMinimos = 0;
            endif;

            //SI EL CAMPO HORAS VIENE VACIO LO INICIALIZO A CERO
            if ($horasMinimas == ""):
                $horasMinimas = 0;
            endif;

            //CALCULAMOS CUANTOS MINUTOS QUEDAN PARA EL CIERRE DEL CENTRO FISICO EN RELACION CON EL TIEMPO QUE SE TARDA EN REALIZAR LAS PREPARACIONES
            $sqlTieResPrep    = "SELECT TIMESTAMPDIFF(MINUTE, '$fechaCompletaActual', DATE_SUB(DATE_SUB($campoHoraCierreCF, INTERVAL $minsMinimos MINUTE), INTERVAL $horasMinimas HOUR)) AS TIEMPO_RESTANTE
                                    FROM CALENDARIO_FESTIVOS_HORARIO
                                    WHERE ID_CALENDARIO_FESTIVOS_HORARIO = $rowHorarioCF->ID_CALENDARIO_FESTIVOS_HORARIO";
            $resultTieResPrep = $bd->ExecSQL($sqlTieResPrep);
            $rowTieResPrep    = $bd->SigReg($resultTieResPrep);

            //SI QUEDA TIEMPO PARA REALIZAR LA PREPARACION EL DIA DE HOY LE PONEMOS LA FECHA DE HOY, SI NO LA DEL PROXIMO DIA LABORABLE
            if ($rowTieResPrep->TIEMPO_RESTANTE > 0):
                $fechaShippingDate = $fechaActual;
            else:
                //BUSCO EL PROXIMO DIA LABORABLE DEL CENTRO FISICO DE ORIGEN
                $rowHorarioCF      = $bd->VerRegRest("CALENDARIO_FESTIVOS_HORARIO", "ID_CALENDARIO_FESTIVOS = $rowCalendarioCF->ID_CALENDARIO_FESTIVOS AND FECHA > '$fechaActual' ORDER BY FECHA ASC LIMIT 1");
                $fechaShippingDate = $rowHorarioCF->FECHA;

                if ($rowHorarioCF->HORA_FIN2 != NULL):
                    $campoHoraCierreCF = "HORA_FIN2";
                else:
                    $campoHoraCierreCF = "HORA_FIN";
                endif;
            endif;

            //CALCULAMOS CUAL ES LA HORA LIMITE EN BASE AL TIEMPO QUE SE TARDA EN REALIZAR LAS PREPARACIONES Y LA HORA DE CIERRE DEL CENTRO FISICO EL DIA
            $sqlHoraLimite    = "SELECT DATE_SUB(DATE_SUB($campoHoraCierreCF, INTERVAL $minsMinimos MINUTE), INTERVAL $horasMinimas HOUR) AS HORA_LIMITE
                                    FROM CALENDARIO_FESTIVOS_HORARIO
                                    WHERE ID_CALENDARIO_FESTIVOS_HORARIO = $rowHorarioCF->ID_CALENDARIO_FESTIVOS_HORARIO";
            $resultHoraLimite = $bd->ExecSQL($sqlHoraLimite);
            $rowHoraLimite    = $bd->SigReg($resultHoraLimite);

            //GUARDAMOS EL VIAJE
            $arrDatosShipping['idRutaViajeLineaOrigen']     = "";
            $arrDatosShipping['idRutaSubViajeLineaOrigen']  = "";
            $arrDatosShipping['idRutaViajeLineaDestino']    = "";
            $arrDatosShipping['idRutaSubViajeLineaDestino'] = "";

            //GUARDAMOS LAS FECHAS CALCULADAS
            $arrDatosShipping['txFechaEstimadaPreparacion'] = $fechaShippingDate . " " . $rowHoraLimite->HORA_LIMITE;
            $arrDatosShipping['txShippingDate']             = $fechaShippingDate . " " . $rowHoraLimite->HORA_LIMITE;
            $arrDatosShipping['txFechaEstimadaLLegada']     = $fechaShippingDate . " " . $rowHoraLimite->HORA_LIMITE;

        elseif ($rowPedidoSalidaLinea->CANAL_DE_ENTREGA == 'Urgente'):

            //BUSCO EL ALMACEN DE ORIGEN
            $rowAlmacenOrigen = $bd->VerReg("ALMACEN", "ID_ALMACEN", $rowPedidoSalidaLinea->ID_ALMACEN_ORIGEN);

            //BUSCO EL CENTRO FISICO DE ORIGEN
            $rowCFOrigen = $bd->VerReg("CENTRO_FISICO", "ID_CENTRO_FISICO", $rowAlmacenOrigen->ID_CENTRO_FISICO);

            $fechaCompletaActual = date("Y-m-d H:i:s");
            if ($rowCFOrigen->ID_HUSO_HORARIO != ''):
                $rowHusoHorario      = $bd->VerReg("HUSO_HORARIO_", "ID_HUSO_HORARIO", $rowCFOrigen->ID_HUSO_HORARIO);
                $fechaCompletaActual = $auxiliar->fechaToTimezoneJWParam($rowHusoHorario->ID_HUSO_HORARIO_PHP, $fechaCompletaActual);
            endif;
            $fechaActual = explode(' ', $fechaCompletaActual)[0];

            //GUARDAMOS EL VIAJE
            $arrDatosShipping['idRutaViajeLineaOrigen']     = "";
            $arrDatosShipping['idRutaSubViajeLineaOrigen']  = "";
            $arrDatosShipping['idRutaViajeLineaDestino']    = "";
            $arrDatosShipping['idRutaSubViajeLineaDestino'] = "";

            //GUARDAMOS LAS FECHAS CALCULADAS
            $arrDatosShipping['txFechaEstimadaPreparacion'] = $fechaActual . " " . "00:00:00";
            $arrDatosShipping['txShippingDate']             = $fechaActual . " " . "00:00:00";
            $arrDatosShipping['txFechaEstimadaLLegada']     = $fechaActual . " " . "00:00:00";
        endif;

        return $arrDatosShipping;
    }

    /**
     * @param $idPedidoSalidaLinea Linea de pedido de salida a comprobar si es preparable teniendo en cuenta el check de preparacion total a nivel de pedido
     * @return bool Valor de la respuesta (true o false)
     */
    function lineaSalidaPreparable($idPedidoSalidaLinea)
    {
        global $bd;
        global $mat;
        global $reserva;
        global $administrador;

        //VARIABLE A DEVOLVER
        $lineaPreparable = false;

        //BUSCO LA LINEA DE PEDIDO DE SALIDA
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowPedidoSalidaLinea             = $bd->VerReg("PEDIDO_SALIDA_LINEA", "ID_PEDIDO_SALIDA_LINEA", $idPedidoSalidaLinea, "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);

        //SI EXISTE LA LINEA
        if ($rowPedidoSalidaLinea != false):
            //BUSCO EL PEDIDO DE SALIDA
            $rowPedidoSalida = $bd->VerReg("PEDIDO_SALIDA", "ID_PEDIDO_SALIDA", $rowPedidoSalidaLinea->ID_PEDIDO_SALIDA);

            //COMPROBACIONES A NIVEL DE LINEA
            if ($rowPedidoSalidaLinea->RETENIDA != 0):
                //LINEA RETENIDA
                return $lineaPreparable;
            elseif (($rowPedidoSalidaLinea->INDICADOR_BORRADO != NULL) || ($rowPedidoSalidaLinea->BAJA != 0)):
                //LINEA BORRADA
                return $lineaPreparable;
            elseif (
                ($rowPedidoSalidaLinea->ENVIADO_SAP != 1) &&
                (
                    ($rowPedidoSalida->TIPO_PEDIDO == 'Pendientes de Ordenes Trabajo') ||
                    (($rowPedidoSalida->TIPO_PEDIDO == 'Traslado') && (($rowPedidoSalida->TIPO_TRASLADO == 'Manual') || ($rowPedidoSalida->TIPO_TRASLADO == 'Planificado Material Obligatorio') || ($rowPedidoSalida->TIPO_TRASLADO == 'Planificado Material No Obligatorio') || ($rowPedidoSalida->TIPO_TRASLADO == 'Planificado Material Indivisible') || ($rowPedidoSalida->TIPO_TRASLADO == 'OT Preventivo')))
                )
            ):
                //LINEA NO ENVIADA A SAP
                return $lineaPreparable;
            elseif (($rowPedidoSalidaLinea->REINTENTAR_SPLIT > 0)):
                //LINEA PENDIENTE REINTENTAR SPLIT
                return $lineaPreparable;
            endif;
            //FIN COMPROBACIONES A NIVEL DE LINEA

            //BUSCO EL TIPO DE BLOQUEO
            if ($rowPedidoSalidaLinea->ID_TIPO_BLOQUEO == NULL):
                $idTipoBloqueo = NULL;
            else:
                $rowTipoBloqueo = $bd->VerReg("TIPO_BLOQUEO", "ID_TIPO_BLOQUEO", $rowPedidoSalidaLinea->ID_TIPO_BLOQUEO, "No");
                $idTipoBloqueo  = $rowTipoBloqueo->ID_TIPO_BLOQUEO;
            endif;

            //DECLARO EL TIPO DE BLOQUEO A GUARDAR
            $idTipoBloqueoArray = 0;
            if ($rowPedidoSalidaLinea->ID_TIPO_BLOQUEO != NULL):
                $idTipoBloqueoArray = $rowPedidoSalidaLinea->ID_TIPO_BLOQUEO;
            endif;

            //PRIMERO BUSCAMOS LA CANTIDAD RESERVADA
            $cantidadReservada = $reserva->get_cantidad_reservada_linea_pedido($rowPedidoSalidaLinea->ID_PEDIDO_SALIDA_LINEA, 'Reservada');

            //SI LA CANTIDAD RESERVADA NO ES SUFICIENTE, BUSCAMOS CANTIDAD DISPONIBLE
            $cantidadDisponibleLinea = $cantidadReservada;
            if ($cantidadReservada < $rowPedidoSalidaLinea->CANTIDAD_PENDIENTE_SERVIR):

                //SOLO CALCULO LA CANTIDAD DISPONIBLE SI PREVIAMENTE NO HA SIDO CALCULADA
                if (!(isset($arrStockDisponible[$rowPedidoSalidaLinea->ID_MATERIAL][$rowPedidoSalidaLinea->ID_ALMACEN_ORIGEN][$idTipoBloqueoArray]))):
                    $arrStockDisponible[$rowPedidoSalidaLinea->ID_MATERIAL][$rowPedidoSalidaLinea->ID_ALMACEN_ORIGEN][$idTipoBloqueoArray] = $mat->StockDisponible($rowPedidoSalidaLinea->ID_MATERIAL, $rowPedidoSalidaLinea->ID_ALMACEN_ORIGEN, $idTipoBloqueo);
                endif;
                $cantidadDisponibleLinea = $cantidadDisponibleLinea + $arrStockDisponible[$rowPedidoSalidaLinea->ID_MATERIAL][$rowPedidoSalidaLinea->ID_ALMACEN_ORIGEN][$idTipoBloqueoArray];
            endif;

            //COMPROBAMOS SI HAY STOCK PARA ESTA LINEA
            if ($rowPedidoSalidaLinea->CANTIDAD_PENDIENTE_SERVIR > $cantidadDisponibleLinea): //LINEA NO PREPARABLE
                //LINEA NO PREPARABLE
                return $lineaPreparable;
            endif;

            //SI PREPARACION TOTAL COMPRUEBO TODAS SUS LINEAS
            if ($rowPedidoSalida->PREPARACION_TOTAL == 1): //PREPARACION TOTAL DE TODAS LAS LINEAS
                //BUSCO LAS LINEAS DEL PEDIDO EXCEPTO LA LINEA PASADA POR PARAMETRO
                $sqlPedidoSalidaLineas    = "SELECT ID_PEDIDO_SALIDA_LINEA, ID_ALMACEN_ORIGEN, ID_MATERIAL, ID_TIPO_BLOQUEO, CANTIDAD_PENDIENTE_SERVIR, FECHA_CREACION_LINEA
                                                FROM PEDIDO_SALIDA_LINEA PSL 
                                                WHERE PSL.ID_PEDIDO_SALIDA = $rowPedidoSalidaLinea->ID_PEDIDO_SALIDA AND PSL.BAJA = 0 AND PSL.INDICADOR_BORRADO IS NULL AND PSL.CANTIDAD_PENDIENTE_SERVIR > " . EPSILON_SISTEMA . " AND PSL.RETENIDA = 0 AND PSL.REINTENTAR_SPLIT = 0 AND PSL.ID_PEDIDO_SALIDA_LINEA <> $rowPedidoSalidaLinea->ID_PEDIDO_SALIDA_LINEA";
                $resultPedidoSalidaLineas = $bd->ExecSQL($sqlPedidoSalidaLineas);
                while ($rowPedidoSalidaLinea = $bd->SigReg($resultPedidoSalidaLineas)): //RECORRO LAS LINEAS DEL PEDIDO
                    //BUSCO EL TIPO DE BLOQUEO
                    if ($rowPedidoSalidaLinea->ID_TIPO_BLOQUEO == NULL):
                        $idTipoBloqueo = NULL;
                    else:
                        $rowTipoBloqueo = $bd->VerReg("TIPO_BLOQUEO", "ID_TIPO_BLOQUEO", $rowPedidoSalidaLinea->ID_TIPO_BLOQUEO, "No");
                        $idTipoBloqueo  = $rowTipoBloqueo->ID_TIPO_BLOQUEO;
                    endif;

                    //DECLARO EL TIPO DE BLOQUEO A GUARDAR
                    $idTipoBloqueoArray = 0;
                    if ($rowPedidoSalidaLinea->ID_TIPO_BLOQUEO != NULL):
                        $idTipoBloqueoArray = $rowPedidoSalidaLinea->ID_TIPO_BLOQUEO;
                    endif;

                    //PRIMERO BUSCAMOS LA CANTIDAD RESERVADA
                    $cantidadReservada = $reserva->get_cantidad_reservada_linea_pedido($rowPedidoSalidaLinea->ID_PEDIDO_SALIDA_LINEA, 'Reservada');

                    //SI LA CANTIDAD RESERVADA NO ES SUFICIENTE, BUSCAMOS CANTIDAD DISPONIBLE
                    $cantidadDisponibleLinea = $cantidadReservada;
                    if ($cantidadReservada < $rowPedidoSalidaLinea->CANTIDAD_PENDIENTE_SERVIR):
                        //SOLO CALCULO LA CANTIDAD DISPONIBLE SI PREVIAMENTE NO HA SIDO CALCULADA
                        if (!(isset($arrStockDisponible[$rowPedidoSalidaLinea->ID_MATERIAL][$rowPedidoSalidaLinea->ID_ALMACEN_ORIGEN][$idTipoBloqueoArray]))):
                            $arrStockDisponible[$rowPedidoSalidaLinea->ID_MATERIAL][$rowPedidoSalidaLinea->ID_ALMACEN_ORIGEN][$idTipoBloqueoArray] = $mat->StockDisponible($rowPedidoSalidaLinea->ID_MATERIAL, $rowPedidoSalidaLinea->ID_ALMACEN_ORIGEN, $idTipoBloqueo);
                        endif;
                        $cantidadDisponibleLinea = $cantidadDisponibleLinea + $arrStockDisponible[$rowPedidoSalidaLinea->ID_MATERIAL][$rowPedidoSalidaLinea->ID_ALMACEN_ORIGEN][$idTipoBloqueoArray];
                    endif;

                    //COMPROBAMOS SI HAY STOCK PARA ESTA LINEA
                    if ($rowPedidoSalidaLinea->CANTIDAD_PENDIENTE_SERVIR > $cantidadDisponibleLinea): //LINEA NO PREPARABLE
                        //LINEA NO PREPARABLE
                        return $lineaPreparable;
                    else: //LINEA PREPARABLE
                        //DESCUENTO LA CANTIDAD DEL ARRAY
                        $cantidadNoReservada = $rowPedidoSalidaLinea->CANTIDAD_PENDIENTE_SERVIR - $cantidadReservada;
                        if ($cantidadNoReservada > EPSILON_SISTEMA):
                            $arrStockDisponible[$rowPedidoSalidaLinea->ID_MATERIAL][$rowPedidoSalidaLinea->ID_ALMACEN_ORIGEN][$idTipoBloqueoArray] -= $cantidadNoReservada;
                        endif;
                    endif;
                endwhile;
            endif;
            //FIN SI PREPARACION TOTAL Y LA LINEA RECIBIDA POR PARAEMTRO EN PREPARABLE
        endif;
        //FIN SI EXISTE LA LINEA

        //DEVUELVO EL VALOR DE LA VARIABLE LINEA PREPARABLE (true SI LLEGA HASTA ESTE PUNTO)
        return true;
    }

    /**
     * @param $idPedidoSalidaLinea Linea de pedido de salida a comprobar si es preparable teniendo en cuenta el check de preparacion total a nivel de pedido y lineas prioritarias sobre esta
     * @return bool Valor de la respuesta (true o false)
     */
    function lineaSalidaPreparableContemplandoLineasPrioritarias($idPedidoSalidaLinea)
    {
        global $bd;
        global $mat;
        global $reserva;
        global $administrador;

        //VARIABLE A DEVOLVER
        $lineaPreparable = false;

        //BUSCO LA LINEA DE PEDIDO DE SALIDA
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowPedidoSalidaLinea             = $bd->VerReg("PEDIDO_SALIDA_LINEA", "ID_PEDIDO_SALIDA_LINEA", $idPedidoSalidaLinea, "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);

        //SI EXISTE LA LINEA
        if ($rowPedidoSalidaLinea != false):
            //BUSCO EL PEDIDO DE SALIDA
            $rowPedidoSalida = $bd->VerReg("PEDIDO_SALIDA", "ID_PEDIDO_SALIDA", $rowPedidoSalidaLinea->ID_PEDIDO_SALIDA);

            //COMPROBACIONES A NIVEL DE LINEA
            if ($rowPedidoSalidaLinea->RETENIDA != 0):
                //LINEA RETENIDA
                return $lineaPreparable;
            elseif (($rowPedidoSalidaLinea->INDICADOR_BORRADO != NULL) || ($rowPedidoSalidaLinea->BAJA != 0)):
                //LINEA BORRADA
                return $lineaPreparable;
            elseif (
                ($rowPedidoSalidaLinea->ENVIADO_SAP != 1) &&
                (
                    ($rowPedidoSalida->TIPO_PEDIDO == 'Pendientes de Ordenes Trabajo') ||
                    (($rowPedidoSalida->TIPO_PEDIDO == 'Traslado') && (($rowPedidoSalida->TIPO_TRASLADO == 'Manual') || ($rowPedidoSalida->TIPO_TRASLADO == 'Planificado Material Obligatorio') || ($rowPedidoSalida->TIPO_TRASLADO == 'Planificado Material No Obligatorio') || ($rowPedidoSalida->TIPO_TRASLADO == 'Planificado Material Indivisible') || ($rowPedidoSalida->TIPO_TRASLADO == 'OT Preventivo')))
                )
            ):
                //LINEA NO ENVIADA A SAP
                return $lineaPreparable;
            elseif (($rowPedidoSalidaLinea->REINTENTAR_SPLIT > 0)):
                //LINEA PENDIENTE REINTENTAR SPLIT
                return $lineaPreparable;
            endif;
            //FIN COMPROBACIONES A NIVEL DE LINEA

            //BUSCO EL TIPO DE BLOQUEO
            if ($rowPedidoSalidaLinea->ID_TIPO_BLOQUEO == NULL):
                $idTipoBloqueo = NULL;
            else:
                $rowTipoBloqueo = $bd->VerReg("TIPO_BLOQUEO", "ID_TIPO_BLOQUEO", $rowPedidoSalidaLinea->ID_TIPO_BLOQUEO, "No");
                $idTipoBloqueo  = $rowTipoBloqueo->ID_TIPO_BLOQUEO;
            endif;

            //DECLARO EL TIPO DE BLOQUEO A GUARDAR
            $idTipoBloqueoArray = 0;
            if ($rowPedidoSalidaLinea->ID_TIPO_BLOQUEO != NULL):
                $idTipoBloqueoArray = $rowPedidoSalidaLinea->ID_TIPO_BLOQUEO;
            endif;

            //PRIMERO BUSCAMOS LA CANTIDAD RESERVADA
            $cantidadReservada = $reserva->get_cantidad_reservada_linea_pedido($rowPedidoSalidaLinea->ID_PEDIDO_SALIDA_LINEA, 'Reservada');

            //SI LA CANTIDAD RESERVADA NO ES SUFICIENTE, BUSCAMOS CANTIDAD DISPONIBLE
            $cantidadDisponibleLinea = $cantidadReservada;
            if ($cantidadReservada < $rowPedidoSalidaLinea->CANTIDAD_PENDIENTE_SERVIR):

                //SOLO CALCULO LA CANTIDAD DISPONIBLE SI PREVIAMENTE NO HA SIDO CALCULADA
                if (!(isset($arrStockDisponible[$rowPedidoSalidaLinea->ID_MATERIAL][$rowPedidoSalidaLinea->ID_ALMACEN_ORIGEN][$idTipoBloqueoArray]))):
                    $arrStockDisponible[$rowPedidoSalidaLinea->ID_MATERIAL][$rowPedidoSalidaLinea->ID_ALMACEN_ORIGEN][$idTipoBloqueoArray] = $mat->StockDisponible($rowPedidoSalidaLinea->ID_MATERIAL, $rowPedidoSalidaLinea->ID_ALMACEN_ORIGEN, $idTipoBloqueo);
                endif;

                //BUSCO LINEAS PRIORITARIAS SOBRE ESTA PARA DESCONTAR EL STOCK DISPONIBLE
                $sqlCantidadPrioritaria    = "SELECT IF(SUM(CANTIDAD_PENDIENTE_SERVIR) IS NULL, 0, SUM(CANTIDAD_PENDIENTE_SERVIR)) AS CANTIDAD 
                                       FROM PEDIDO_SALIDA_LINEA PSL 
                                       WHERE PSL.BAJA = 0 AND PSL.INDICADOR_BORRADO IS NULL AND PSL.CANTIDAD_PENDIENTE_SERVIR > " . EPSILON_SISTEMA . " AND PSL.RETENIDA = 0 AND PSL.REINTENTAR_SPLIT = 0 AND PSL.ID_PEDIDO_SALIDA_LINEA <> $rowPedidoSalidaLinea->ID_PEDIDO_SALIDA_LINEA AND PSL.ID_ALMACEN_ORIGEN = $rowPedidoSalidaLinea->ID_ALMACEN_ORIGEN AND PSL.ID_MATERIAL = $rowPedidoSalidaLinea->ID_MATERIAL AND PSL.ID_TIPO_BLOQUEO " . ($rowPedidoSalidaLinea->ID_TIPO_BLOQUEO == NULL ? "IS NULL" : "= $rowPedidoSalidaLinea->ID_TIPO_BLOQUEO") . " AND ((PSL.CANAL_DE_ENTREGA IN ('Urgente', 'Semiurgente')) OR (PSL.CANAL_DE_ENTREGA = 'Estandar' AND PSL.FECHA_CREACION_LINEA < '" . $rowPedidoSalidaLinea->FECHA_CREACION_LINEA . "'))";
                $resultCantidadPrioritaria = $bd->ExecSQL($sqlCantidadPrioritaria);
                $rowCantidadPrioritaria    = $bd->SigReg($resultCantidadPrioritaria);

                //GUARDO EL REGISTRO CANTIDAD PRIORITARIA PARA NO VOLVER A CALCULARLA
                $arrCantidadPrioritariaCalculada[$rowPedidoSalidaLinea->ID_ALMACEN_ORIGEN][$rowPedidoSalidaLinea->ID_MATERIAL][$idTipoBloqueoArray] = 1;

                //DESCUENTO LA CANTIDAD PRIORITARIA
                $arrStockDisponible[$rowPedidoSalidaLinea->ID_MATERIAL][$rowPedidoSalidaLinea->ID_ALMACEN_ORIGEN][$idTipoBloqueoArray] -= $rowCantidadPrioritaria->CANTIDAD;

                $cantidadDisponibleLinea = $cantidadDisponibleLinea + $arrStockDisponible[$rowPedidoSalidaLinea->ID_MATERIAL][$rowPedidoSalidaLinea->ID_ALMACEN_ORIGEN][$idTipoBloqueoArray];
            endif;

            //COMPROBAMOS SI HAY STOCK PARA ESTA LINEA
            if ($rowPedidoSalidaLinea->CANTIDAD_PENDIENTE_SERVIR > $cantidadDisponibleLinea): //LINEA NO PREPARABLE
                //LINEA NO PREPARABLE
                return $lineaPreparable;
            endif;

            //SI PREPARACION TOTAL COMPRUEBO TODAS SUS LINEAS
            if ($rowPedidoSalida->PREPARACION_TOTAL == 1): //PREPARACION TOTAL DE TODAS LAS LINEAS
                //BUSCO LAS LINEAS DEL PEDIDO EXCEPTO LA LINEA PASADA POR PARAMETRO
                $sqlPedidoSalidaLineas    = "SELECT ID_PEDIDO_SALIDA_LINEA, ID_ALMACEN_ORIGEN, ID_MATERIAL, ID_TIPO_BLOQUEO, CANTIDAD_PENDIENTE_SERVIR, FECHA_CREACION_LINEA
                                                FROM PEDIDO_SALIDA_LINEA PSL 
                                                WHERE PSL.ID_PEDIDO_SALIDA = $rowPedidoSalidaLinea->ID_PEDIDO_SALIDA AND PSL.BAJA = 0 AND PSL.INDICADOR_BORRADO IS NULL AND PSL.CANTIDAD_PENDIENTE_SERVIR > " . EPSILON_SISTEMA . " AND PSL.RETENIDA = 0 AND PSL.REINTENTAR_SPLIT = 0 AND PSL.ID_PEDIDO_SALIDA_LINEA <> $rowPedidoSalidaLinea->ID_PEDIDO_SALIDA_LINEA";
                $resultPedidoSalidaLineas = $bd->ExecSQL($sqlPedidoSalidaLineas);
                while ($rowPedidoSalidaLinea = $bd->SigReg($resultPedidoSalidaLineas)): //RECORRO LAS LINEAS DEL PEDIDO
                    //BUSCO EL TIPO DE BLOQUEO
                    if ($rowPedidoSalidaLinea->ID_TIPO_BLOQUEO == NULL):
                        $idTipoBloqueo = NULL;
                    else:
                        $rowTipoBloqueo = $bd->VerReg("TIPO_BLOQUEO", "ID_TIPO_BLOQUEO", $rowPedidoSalidaLinea->ID_TIPO_BLOQUEO, "No");
                        $idTipoBloqueo  = $rowTipoBloqueo->ID_TIPO_BLOQUEO;
                    endif;

                    //DECLARO EL TIPO DE BLOQUEO A GUARDAR
                    $idTipoBloqueoArray = 0;
                    if ($rowPedidoSalidaLinea->ID_TIPO_BLOQUEO != NULL):
                        $idTipoBloqueoArray = $rowPedidoSalidaLinea->ID_TIPO_BLOQUEO;
                    endif;

                    //PRIMERO BUSCAMOS LA CANTIDAD RESERVADA
                    $cantidadReservada = $reserva->get_cantidad_reservada_linea_pedido($rowPedidoSalidaLinea->ID_PEDIDO_SALIDA_LINEA, 'Reservada');

                    //SI LA CANTIDAD RESERVADA NO ES SUFICIENTE, BUSCAMOS CANTIDAD DISPONIBLE
                    $cantidadDisponibleLinea = $cantidadReservada;
                    if ($cantidadReservada < $rowPedidoSalidaLinea->CANTIDAD_PENDIENTE_SERVIR):

                        //SOLO CALCULO LA CANTIDAD DISPONIBLE SI PREVIAMENTE NO HA SIDO CALCULADA
                        if (!(isset($arrStockDisponible[$rowPedidoSalidaLinea->ID_MATERIAL][$rowPedidoSalidaLinea->ID_ALMACEN_ORIGEN][$idTipoBloqueoArray]))):
                            $arrStockDisponible[$rowPedidoSalidaLinea->ID_MATERIAL][$rowPedidoSalidaLinea->ID_ALMACEN_ORIGEN][$idTipoBloqueoArray] = $mat->StockDisponible($rowPedidoSalidaLinea->ID_MATERIAL, $rowPedidoSalidaLinea->ID_ALMACEN_ORIGEN, $idTipoBloqueo);
                        endif;

                        //CALCULO LA CANTIDAD PRIORITARIA SI NO HA SIDO CALCULADA ANTERIORMENTE
                        if (
                            (!(isset($arrCantidadPrioritariaCalculada[$rowPedidoSalidaLinea->ID_ALMACEN_ORIGEN][$rowPedidoSalidaLinea->ID_MATERIAL][$idTipoBloqueoArray]))) &&
                            ($arrCantidadPrioritariaCalculada[$rowPedidoSalidaLinea->ID_ALMACEN_ORIGEN][$rowPedidoSalidaLinea->ID_MATERIAL][$idTipoBloqueoArray] != 1)
                        ):
                            //BUSCO LINEAS PRIORITARIAS SOBRE ESTA PARA DESCONTAR EL STOCK DISPONIBLE
                            $sqlCantidadPrioritaria    = "SELECT IF(SUM(CANTIDAD_PENDIENTE_SERVIR) IS NULL, 0, SUM(CANTIDAD_PENDIENTE_SERVIR)) AS CANTIDAD 
                                                   FROM PEDIDO_SALIDA_LINEA PSL 
                                                   WHERE PSL.BAJA = 0 AND PSL.INDICADOR_BORRADO IS NULL AND PSL.CANTIDAD_PENDIENTE_SERVIR > " . EPSILON_SISTEMA . " AND PSL.RETENIDA = 0 AND PSL.REINTENTAR_SPLIT = 0 AND PSL.ID_PEDIDO_SALIDA_LINEA <> $rowPedidoSalidaLinea->ID_PEDIDO_SALIDA_LINEA AND PSL.ID_ALMACEN_ORIGEN = $rowPedidoSalidaLinea->ID_ALMACEN_ORIGEN AND PSL.ID_MATERIAL = $rowPedidoSalidaLinea->ID_MATERIAL AND PSL.ID_TIPO_BLOQUEO " . ($rowPedidoSalidaLinea->ID_TIPO_BLOQUEO == NULL ? "IS NULL" : "= $rowPedidoSalidaLinea->ID_TIPO_BLOQUEO") . " AND ((PSL.CANAL_DE_ENTREGA IN ('Urgente', 'Semiurgente')) OR (PSL.CANAL_DE_ENTREGA = 'Estandar' AND PSL.FECHA_CREACION_LINEA < '" . $rowPedidoSalidaLinea->FECHA_CREACION_LINEA . "'))";
                            $resultCantidadPrioritaria = $bd->ExecSQL($sqlCantidadPrioritaria);
                            $rowCantidadPrioritaria    = $bd->SigReg($resultCantidadPrioritaria);

                            //GUARDO EL REGISTRO CANTIDAD PRIORITARIA PARA NO VOLVER A CALCULARLA
                            $arrCantidadPrioritariaCalculada[$rowPedidoSalidaLinea->ID_ALMACEN_ORIGEN][$rowPedidoSalidaLinea->ID_MATERIAL][$idTipoBloqueoArray] = 1;

                            //DESCUENTO LA CANTIDAD PRIORITARIA
                            $arrStockDisponible[$rowPedidoSalidaLinea->ID_MATERIAL][$rowPedidoSalidaLinea->ID_ALMACEN_ORIGEN][$idTipoBloqueoArray] -= $rowCantidadPrioritaria->CANTIDAD;
                        endif;

                        $cantidadDisponibleLinea = $cantidadDisponibleLinea + $arrStockDisponible[$rowPedidoSalidaLinea->ID_MATERIAL][$rowPedidoSalidaLinea->ID_ALMACEN_ORIGEN][$idTipoBloqueoArray];

                    endif;//FIN SI LA CANTIDAD RESERVADA NO ES SUFICIENTE, BUSCAMOS CANTIDAD DISPONIBLE


                    //COMPROBAMOS SI HAY STOCK PARA ESTA LINEA
                    if ($rowPedidoSalidaLinea->CANTIDAD_PENDIENTE_SERVIR > $cantidadDisponibleLinea): //LINEA NO PREPARABLE
                        //LINEA NO PREPARABLE
                        return $lineaPreparable;
                    else: //LINEA PREPARABLE
                        //DESCUENTO LA CANTIDAD DEL ARRAY
                        $cantidadNoReservada = $rowPedidoSalidaLinea->CANTIDAD_PENDIENTE_SERVIR - $cantidadReservada;
                        if ($cantidadNoReservada > EPSILON_SISTEMA):
                            $arrStockDisponible[$rowPedidoSalidaLinea->ID_MATERIAL][$rowPedidoSalidaLinea->ID_ALMACEN_ORIGEN][$idTipoBloqueoArray] -= $cantidadNoReservada;
                        endif;
                    endif;
                endwhile;
            endif;
            //FIN SI PREPARACION TOTAL Y LA LINEA RECIBIDA POR PARAEMTRO EN PREPARABLE
        endif;
        //FIN SI EXISTE LA LINEA

        //DEVUELVO EL VALOR DE LA VARIABLE LINEA PREPARABLE (true SI LLEGA HASTA ESTE PUNTO)
        return true;
    }

    /**
     * @param $idPedidoSalidaLinea Linea de pedido de salida a comprobar si es preparable teniendo en cuenta el check de preparacion total a nivel de pedido y lineas prioritarias sobre esta
     * @return $arrIdsLineasNoPreparables Array de Ids de lineas no preparables del pedido
     */
    function idLineaSalidaNoPreparablePreparacionTotal($idPedidoSalidaLinea)
    {
        global $bd;
        global $mat;
        global $reserva;
        global $administrador;

        //BUSCO LA LINEA DE PEDIDO DE SALIDA
        $rowPedidoSalidaLinea = $bd->VerReg("PEDIDO_SALIDA_LINEA", "ID_PEDIDO_SALIDA_LINEA", $idPedidoSalidaLinea, "No");

        $arrIdsLineasNoPreparables = array();

        //SI EXISTE LA LINEA
        if ($rowPedidoSalidaLinea != false):

            //BUSCO EL PEDIDO DE SALIDA
            $rowPedidoSalida = $bd->VerReg("PEDIDO_SALIDA", "ID_PEDIDO_SALIDA", $rowPedidoSalidaLinea->ID_PEDIDO_SALIDA);

            //SI PREPARACION TOTAL COMPRUEBO TODAS SUS LINEAS
            if ($rowPedidoSalida->PREPARACION_TOTAL == 1): //PREPARACION TOTAL DE TODAS LAS LINEAS
                //BUSCO LAS LINEAS DEL PEDIDO EXCEPTO LA LINEA PASADA POR PARAMETRO
                $sqlPedidoSalidaLineas    = "SELECT ID_PEDIDO_SALIDA_LINEA, ID_ALMACEN_ORIGEN, ID_MATERIAL, ID_TIPO_BLOQUEO, CANTIDAD_PENDIENTE_SERVIR, FECHA_CREACION_LINEA
                                                FROM PEDIDO_SALIDA_LINEA PSL 
                                                WHERE PSL.ID_PEDIDO_SALIDA = $rowPedidoSalidaLinea->ID_PEDIDO_SALIDA AND PSL.BAJA = 0 AND PSL.INDICADOR_BORRADO IS NULL AND PSL.CANTIDAD_PENDIENTE_SERVIR > " . EPSILON_SISTEMA . " AND PSL.RETENIDA = 0 AND PSL.REINTENTAR_SPLIT = 0";
                $resultPedidoSalidaLineas = $bd->ExecSQL($sqlPedidoSalidaLineas);
                while ($rowPedidoSalidaLinea = $bd->SigReg($resultPedidoSalidaLineas)): //RECORRO LAS LINEAS DEL PEDIDO
                    //BUSCO EL TIPO DE BLOQUEO
                    if ($rowPedidoSalidaLinea->ID_TIPO_BLOQUEO == NULL):
                        $idTipoBloqueo = NULL;
                    else:
                        $rowTipoBloqueo = $bd->VerReg("TIPO_BLOQUEO", "ID_TIPO_BLOQUEO", $rowPedidoSalidaLinea->ID_TIPO_BLOQUEO, "No");
                        $idTipoBloqueo  = $rowTipoBloqueo->ID_TIPO_BLOQUEO;
                    endif;

                    //DECLARO EL TIPO DE BLOQUEO A GUARDAR
                    $idTipoBloqueoArray = 0;
                    if ($rowPedidoSalidaLinea->ID_TIPO_BLOQUEO != NULL):
                        $idTipoBloqueoArray = $rowPedidoSalidaLinea->ID_TIPO_BLOQUEO;
                    endif;

                    //PRIMERO BUSCAMOS LA CANTIDAD RESERVADA
                    $cantidadReservada = $reserva->get_cantidad_reservada_linea_pedido($rowPedidoSalidaLinea->ID_PEDIDO_SALIDA_LINEA, 'Reservada');

                    //SI LA CANTIDAD RESERVADA NO ES SUFICIENTE, BUSCAMOS CANTIDAD DISPONIBLE
                    $cantidadDisponibleLinea = $cantidadReservada;
                    if ($cantidadReservada < $rowPedidoSalidaLinea->CANTIDAD_PENDIENTE_SERVIR):

                        //SOLO CALCULO LA CANTIDAD DISPONIBLE SI PREVIAMENTE NO HA SIDO CALCULADA
                        if (!(isset($arrStockDisponible[$rowPedidoSalidaLinea->ID_MATERIAL][$rowPedidoSalidaLinea->ID_ALMACEN_ORIGEN][$idTipoBloqueoArray]))):
                            $arrStockDisponible[$rowPedidoSalidaLinea->ID_MATERIAL][$rowPedidoSalidaLinea->ID_ALMACEN_ORIGEN][$idTipoBloqueoArray] = $mat->StockDisponible($rowPedidoSalidaLinea->ID_MATERIAL, $rowPedidoSalidaLinea->ID_ALMACEN_ORIGEN, $idTipoBloqueo);
                        endif;

                        //CALCULO LA CANTIDAD PRIORITARIA SI NO HA SIDO CALCULADA ANTERIORMENTE
                        if (
                            (!(isset($arrCantidadPrioritariaCalculada[$rowPedidoSalidaLinea->ID_ALMACEN_ORIGEN][$rowPedidoSalidaLinea->ID_MATERIAL][$idTipoBloqueoArray]))) &&
                            ($arrCantidadPrioritariaCalculada[$rowPedidoSalidaLinea->ID_ALMACEN_ORIGEN][$rowPedidoSalidaLinea->ID_MATERIAL][$idTipoBloqueoArray] != 1)
                        ):
                            //BUSCO LINEAS PRIORITARIAS SOBRE ESTA PARA DESCONTAR EL STOCK DISPONIBLE
                            $sqlCantidadPrioritaria    = "SELECT IF(SUM(CANTIDAD_PENDIENTE_SERVIR) IS NULL, 0, SUM(CANTIDAD_PENDIENTE_SERVIR)) AS CANTIDAD 
                                                   FROM PEDIDO_SALIDA_LINEA PSL 
                                                   WHERE PSL.BAJA = 0 AND PSL.INDICADOR_BORRADO IS NULL AND PSL.CANTIDAD_PENDIENTE_SERVIR > " . EPSILON_SISTEMA . " AND PSL.RETENIDA = 0 AND PSL.REINTENTAR_SPLIT = 0 AND PSL.ID_PEDIDO_SALIDA_LINEA <> $rowPedidoSalidaLinea->ID_PEDIDO_SALIDA_LINEA AND PSL.ID_ALMACEN_ORIGEN = $rowPedidoSalidaLinea->ID_ALMACEN_ORIGEN AND PSL.ID_MATERIAL = $rowPedidoSalidaLinea->ID_MATERIAL AND PSL.ID_TIPO_BLOQUEO " . ($rowPedidoSalidaLinea->ID_TIPO_BLOQUEO == NULL ? "IS NULL" : "= $rowPedidoSalidaLinea->ID_TIPO_BLOQUEO") . " AND ((PSL.CANAL_DE_ENTREGA IN ('Urgente', 'Semiurgente')) OR (PSL.CANAL_DE_ENTREGA = 'Estandar' AND PSL.FECHA_CREACION_LINEA < '" . $rowPedidoSalidaLinea->FECHA_CREACION_LINEA . "'))";
                            $resultCantidadPrioritaria = $bd->ExecSQL($sqlCantidadPrioritaria);
                            $rowCantidadPrioritaria    = $bd->SigReg($resultCantidadPrioritaria);

                            //GUARDO EL REGISTRO CANTIDAD PRIORITARIA PARA NO VOLVER A CALCULARLA
                            $arrCantidadPrioritariaCalculada[$rowPedidoSalidaLinea->ID_ALMACEN_ORIGEN][$rowPedidoSalidaLinea->ID_MATERIAL][$idTipoBloqueoArray] = 1;

                            //DESCUENTO LA CANTIDAD PRIORITARIA
                            $arrStockDisponible[$rowPedidoSalidaLinea->ID_MATERIAL][$rowPedidoSalidaLinea->ID_ALMACEN_ORIGEN][$idTipoBloqueoArray] -= $rowCantidadPrioritaria->CANTIDAD;
                        endif;

                        $cantidadDisponibleLinea = $cantidadDisponibleLinea + $arrStockDisponible[$rowPedidoSalidaLinea->ID_MATERIAL][$rowPedidoSalidaLinea->ID_ALMACEN_ORIGEN][$idTipoBloqueoArray];

                    endif;//FIN SI LA CANTIDAD RESERVADA NO ES SUFICIENTE, BUSCAMOS CANTIDAD DISPONIBLE


                    //COMPROBAMOS SI HAY STOCK PARA ESTA LINEA
                    if ($rowPedidoSalidaLinea->CANTIDAD_PENDIENTE_SERVIR > $cantidadDisponibleLinea): //LINEA NO PREPARABLE
                        //LINEA NO PREPARABLE
                        $arrIdsLineasNoPreparables[] = $rowPedidoSalidaLinea->ID_PEDIDO_SALIDA_LINEA;
                    else: //LINEA PREPARABLE
                        //DESCUENTO LA CANTIDAD DEL ARRAY
                        $cantidadNoReservada = $rowPedidoSalidaLinea->CANTIDAD_PENDIENTE_SERVIR - $cantidadReservada;
                        if ($cantidadNoReservada > EPSILON_SISTEMA):
                            $arrStockDisponible[$rowPedidoSalidaLinea->ID_MATERIAL][$rowPedidoSalidaLinea->ID_ALMACEN_ORIGEN][$idTipoBloqueoArray] -= $cantidadNoReservada;
                        endif;
                    endif;
                endwhile;
            endif;
            //FIN SI PREPARACION TOTAL Y LA LINEA RECIBIDA POR PARAEMTRO EN PREPARABLE
        endif;
        //FIN SI EXISTE LA LINEA
        if (count( (array)$arrIdsLineasNoPreparables) > 0):
            return $arrIdsLineasNoPreparables;
        endif;

        //DEVUELVO EL VALOR DE LA VARIABLE LINEA PREPARABLE (true SI LLEGA HASTA ESTE PUNTO)
        return false;
    }

    /**
     * Función para liberar una línea de pedido de salida (RETENIDA = 0)
     * @param $idPedidoSalidaLinea
     */
    function liberarPedidoSalidaLinea($idPedidoSalidaLinea)
    {
        global $bd;
        global $administrador;

        //MARCO LA LINEA COMO LIBERADA
        $sqlUpdate = "UPDATE PEDIDO_SALIDA_LINEA SET
                              ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                              , RETENIDA = 0
                              , FECHA_RETENCION = '0000-00-00 00:00:00'
                              WHERE ID_PEDIDO_SALIDA_LINEA = $idPedidoSalidaLinea";
        $bd->ExecSQL($sqlUpdate);
    }

    /**
     * Función para retener una línea de pedido de salida (RETENIDA = 1)
     * @param $idPedidoSalidaLinea
     */
    function retenerPedidoSalidaLinea($idPedidoSalidaLinea)
    {
        global $bd;
        global $administrador;

        //MARCO LA LINEA COMO RETENIDA
        $sqlUpdate = "UPDATE PEDIDO_SALIDA_LINEA SET
                              ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                              , RETENIDA = 1
                              , FECHA_RETENCION = '" . date("Y-m-d H:i:s") . "'
                              WHERE ID_PEDIDO_SALIDA_LINEA = $idPedidoSalidaLinea";
        $bd->ExecSQL($sqlUpdate);
    }

    /**
     * Función para obtener si tiene líneas de OT una línea de pedido de salida
     * @param $idPedidoSalidaLinea
     */
    function tieneLineasOTPedidoSalidaLinea($idPedidoSalidaLinea)
    {
        global $bd;
        global $administrador;

        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowPedidoSalidaLinea             = $bd->VerReg("PEDIDO_SALIDA_LINEA", "ID_PEDIDO_SALIDA_LINEA", $idPedidoSalidaLinea, "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);

        //SI NO EXISTE LA LINEA
        if (($rowPedidoSalidaLinea == false) || ($rowPedidoSalidaLinea->BAJA == 1)):
            return false;
        endif;

        //BUSCO EL PEDIDO DE SALIDA
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowPed                           = $bd->VerReg("PEDIDO_SALIDA", "ID_PEDIDO_SALIDA", $rowPedidoSalidaLinea->ID_PEDIDO_SALIDA, "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);
        //SI NO EXISTE EL PEDIDO
        if (($rowPed == false) || ($rowPed->BAJA == 1)):
            return false;
        endif;

        //OBTENEMOS LINEAS DE PENDIENTES
        if (($rowPed->TIPO_PEDIDO == 'Pendientes de Ordenes Trabajo') || (($rowPed->TIPO_PEDIDO == 'Traslado') && ($rowPed->TIPO_TRASLADO == 'OT Preventivo'))):
            //BUSCO LA ORDEN DE TRABAJO LINEA
            $GLOBALS["NotificaErrorPorEmail"] = "No";
            $rowOrdenTrabajoLinea             = $bd->VerRegRest("ORDEN_TRABAJO_LINEA", "ID_ORDEN_TRABAJO_LINEA = $rowPedidoSalidaLinea->ID_ORDEN_TRABAJO_LINEA AND BAJA = 0", "No");
            unset($GLOBALS["NotificaErrorPorEmail"]);
            if ($rowOrdenTrabajoLinea != false):
                return true;
            else:
                return false;
            endif;
        elseif (
            ($rowPed->TIPO_PEDIDO == 'Traslado') &&
            (($rowPed->TIPO_TRASLADO == 'Planificado Material Obligatorio') || ($rowPed->TIPO_TRASLADO == 'Planificado Material No Obligatorio') || ($rowPed->TIPO_TRASLADO == 'Planificado Material Indivisible') || ($rowPed->TIPO_TRASLADO == 'Manual'))
        ): //PEDIDOS DE PLANIFICADOS
            //BUSCO LAS LINEAS DE ORDEN DE TRABAO RELACIONADAS CON ESTE PEDIDO
            $sqlOTLineas    = "SELECT * 
                                FROM ORDEN_TRABAJO_LINEA_CUBIERTA_PEDIDO 
                                WHERE ID_PEDIDO_SALIDA_LINEA = $rowPedidoSalidaLinea->ID_PEDIDO_SALIDA_LINEA AND BAJA = 0";
            $resultOTLineas = $bd->ExecSQL($sqlOTLineas);
            if ($bd->NumRegs($resultOTLineas) > 0):
                return true;
            else:
                return false;
            endif;
        endif;

        return false;
    }

    function tieneLineasOTPedidoSalida($idPedidoSalida)
    {
        global $bd;
        global $administrador;

        //BUSCO EL PEDIDO DE SALIDA
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowPed                           = $bd->VerReg("PEDIDO_SALIDA", "ID_PEDIDO_SALIDA", $idPedidoSalida, "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);
        //SI NO EXISTE EL PEDIDO
        if (($rowPed == false) || ($rowPed->BAJA == 1)):
            return false;
        endif;

        //OBTENEMOS LAS LINEAS DEL PEDIDO
        $sqlLineas    = "SELECT * FROM PEDIDO_SALIDA_LINEA 
                                WHERE ID_PEDIDO_SALIDA = $rowPed->ID_PEDIDO_SALIDA AND BAJA = 0";
        $resultLineas = $bd->ExecSQL($sqlLineas);
        if ($bd->NumRegs($resultLineas) > 0):
            while ($rowLineaPedido = $bd->SigReg($resultLineas)):
                $resultado = $this->tieneLineasOTPedidoSalidaLinea($rowLineaPedido->ID_PEDIDO_SALIDA_LINEA);
                if ($resultado):
                    return true;
                endif;
            endwhile;
        endif;

        return false;
    }

    /**
     * SE ANULAN LAS CANTIDADES PENDIENTES DE LA LÍNEAS PEDIDO SALIDA
     * @param $idPedidoSalidaLinea
     * @retrun array
     */
    function anularCantidadNoSuministradaLinea($idPedidoSalidaLinea, $idOrdenTrabajo = NULL, $vieneDeInterfaz = false)
    {
        global $bd;
        global $auxiliar;
        global $administrador;
        global $orden_trabajo;
        global $incidencia_sistema;
        global $reserva;
        global $necesidad;

        //CANCELO LAS NECESIDADES ASOCIADAS A LA OT
        $necesidad->CancelarNecesidadAsociadaOT($idPedidoSalidaLinea, $idOrdenTrabajo);

        //BUSCO LA LÍNEA DEL PEDIDO
        $rowLinea = $bd->VerReg("PEDIDO_SALIDA_LINEA", "ID_PEDIDO_SALIDA_LINEA", $idPedidoSalidaLinea);

        //BUSCO EL PEDIDO
        $rowPedido = $bd->VerReg("PEDIDO_SALIDA", "ID_PEDIDO_SALIDA", $rowLinea->ID_PEDIDO_SALIDA);

        // COMPRUEBO QUE LA LINEA NO TENGA NECESIDADES ASOCIADAS
        $numNecesidades = $bd->NumRegsTabla("NECESIDAD_LINEA NL", "NL.ID_PEDIDO_SALIDA_LINEA = $rowLinea->ID_PEDIDO_SALIDA_LINEA AND NL.BAJA = 0");

        // SI TIENE NECESIDADES ASOCIADAS, LA AÑADIMOS AL ARRAY Y NO HACEMOS NADA MÁS (PASAMOS A LA SIGUIENTE LÍNEA)
        if ($numNecesidades > 0):
            $numeroLinea = (int)$rowLinea->LINEA_PEDIDO_SAP;
            $strKo       = $auxiliar->traduce("Linea", $administrador->ID_IDIOMA) . ": $numeroLinea. " . $auxiliar->traduce("No se puede borrar la línea, existen necesidades asociadas", $administrador->ID_IDIOMA) . ". " . $auxiliar->traduce("Si quiere borrar la línea deberá modificar dichas necesidades", $administrador->ID_IDIOMA) . ".\n";
            $arrayError  = array(
                'ERROR'                        => true,
                'MENSAJE_ERROR'                => '',
                'STR_KO'                       => $strKo,
                'LINEA_CON_NECESIDAD_ASOCIADA' => $rowLinea->LINEA_PEDIDO_SAP
            );

            return $arrayError;
        endif;

        //BUSCO SI HAY UNA ACCION PENDIENTE DE REALIZAR SOBRE ESTA LINEA DE PEDIDO SALIDA
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowAccionPendienteRealizar       = $bd->VerRegRest("ACCION_PENDIENTE_REALIZAR", "ESTADO = 'Creada' AND ACCION = 'Borrar' AND TABLA_OBJETO = 'PEDIDO_SALIDA_LINEA' AND ID_OBJETO = $rowLinea->ID_PEDIDO_SALIDA_LINEA AND BAJA = 0", "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);
        $pedidoSalidaLineaGenerarIncidenciaSistema = false;
        if ($rowAccionPendienteRealizar != false): //EXISTE LA ACCION COMO PENDIENTE DE ERALIZAR, LA MARCO COMO FINALIZADA
            $sqlUpdate = "UPDATE ACCION_PENDIENTE_REALIZAR SET
                          ESTADO = 'Finalizada'
                          , FECHA_RESOLUCION = '" . date("Y-m-d H:i:s") . "'
                          , ID_ADMINISTRADOR_ULTIMA_MODIFICACION = $administrador->ID_ADMINISTRADOR
                          WHERE ID_ACCION_PENDIENTE_REALIZAR = $rowAccionPendienteRealizar->ID_ACCION_PENDIENTE_REALIZAR";
            $bd->ExecSQL($sqlUpdate);

            //AGREGO LA LINEA AL ARRAY DE LINEAS SOBRE LAS QUE GENERAR UNA INCIDENCIA DE SISTEMA
            $pedidoSalidaLineaGenerarIncidenciaSistema = $rowLinea->ID_PEDIDO_SALIDA_LINEA;
        endif;

        //BUSCO EL TIPO INCIDENCIA 'Linea Pendiente Borrado' Y SUBTIPO 'Pedido Salida'
        $rowISTipo    = $bd->VerReg("INCIDENCIA_SISTEMA_TIPO", "INCIDENCIA_SISTEMA_TIPO", "Linea Pendiente Borrado");
        $rowISSubTipo = $bd->VerReg("INCIDENCIA_SISTEMA_SUBTIPO", "INCIDENCIA_SISTEMA_SUBTIPO", "Pedido Salida");

        //BUSCAMOS SI EXISTE INCIDENCIA SISTEMA PARA LA LINEA
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowIncidenciaSistema             = $bd->VerRegRest("INCIDENCIA_SISTEMA", "ID_INCIDENCIA_SISTEMA_TIPO = $rowISTipo->ID_INCIDENCIA_SISTEMA_TIPO AND ID_INCIDENCIA_SISTEMA_SUBTIPO = $rowISSubTipo->ID_INCIDENCIA_SISTEMA_SUBTIPO AND ESTADO = 'Creada' AND TABLA_OBJETO = 'PEDIDO_SALIDA_LINEA' AND ID_OBJETO = $rowLinea->ID_PEDIDO_SALIDA_LINEA", "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);
        if ($rowIncidenciaSistema != false):
            //ACTUALIZO INCIDENCIA
            $incidencia_sistema->actualizarIncidencia($rowIncidenciaSistema, 'Automatica');
        endif;

        //SI LA LINEA DE PEDIDO NO SE HA COMENZADO A PREPARAR ANULO TODA LA CANTIDAD
        if ($rowLinea->CANTIDAD == $rowLinea->CANTIDAD_PENDIENTE_SERVIR):
            //ACTUALIZO LA LINEA DE PEDIDO
            $sqlUpdate = "UPDATE PEDIDO_SALIDA_LINEA SET
                            ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                            , INDICADOR_BORRADO = 'L'
                            , BAJA = 1
                            WHERE ID_PEDIDO_SALIDA_LINEA = $rowLinea->ID_PEDIDO_SALIDA_LINEA";
            $bd->ExecSQL($sqlUpdate);

            //GUARDO LA CANTIDAD ANULADA DEL PEDIDO
            $variacionLineaPedido = $rowLinea->CANTIDAD;

            //BUSCO LA LÍNEA DEL PEDIDO ACTUALIZADA
            $rowLineaActualizada = $bd->VerReg("PEDIDO_SALIDA_LINEA", "ID_PEDIDO_SALIDA_LINEA", $rowLinea->ID_PEDIDO_SALIDA_LINEA);

            // LOG MOVIMIENTOS
            $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Anulación", "Linea Pedido Salida", $rowLinea->ID_PEDIDO_SALIDA_LINEA, "Anulacion linea pedido salida " . (($vieneDeInterfaz) ? "de forma automatica" : "de forma manual") . " por anulacion cantidad no suministrada", "PEDIDO_SALIDA_LINEA", $rowLinea, $rowLineaActualizada);

        else:
            //ACTUALIZO LA LINEA DE PEDIDO
            $sqlUpdate = "UPDATE PEDIDO_SALIDA_LINEA SET
                            ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                            , CANTIDAD = CANTIDAD - CANTIDAD_PENDIENTE_SERVIR
                            , CANTIDAD_PENDIENTE_SERVIR = 0
                            WHERE ID_PEDIDO_SALIDA_LINEA = $rowLinea->ID_PEDIDO_SALIDA_LINEA";
            $bd->ExecSQL($sqlUpdate);

            //GUARDO LA CANTIDAD ANULADA DEL PEDIDO
            $variacionLineaPedido = $rowLinea->CANTIDAD_PENDIENTE_SERVIR;

            //BUSCO LA LÍNEA DEL PEDIDO ACTUALIZADA
            $rowLineaActualizada = $bd->VerReg("PEDIDO_SALIDA_LINEA", "ID_PEDIDO_SALIDA_LINEA", $rowLinea->ID_PEDIDO_SALIDA_LINEA);

            // LOG MOVIMIENTOS
            $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Linea Pedido Salida", $rowLinea->ID_PEDIDO_SALIDA_LINEA, "Modificacion linea pedido salida " . (($vieneDeInterfaz) ? "de forma automatica" : "de forma manual") . " por anulacion cantidad no suministrada", "PEDIDO_SALIDA_LINEA", $rowLinea, $rowLineaActualizada);
        endif;

        //CREO EL ARRAY PARA GUARDAR LAS LINEAS DE OTS ASOCIADAS A LA LINEA DE PEDIDO (SI LAS HAY) Y TRANSMITIR LAS INTERFACES CORRESPONDIENTES
        $arrInterfacesLineasOrdenTrabajoEnviarSAP = array();

        //SI EL PEDIDO ES DE PENDIENTES
        if ($rowPedido->TIPO_PEDIDO == "Pendientes de Ordenes Trabajo"):
            //COMPRUEBO SI LA LINEA ESTA ASOCIADA A UNA LINEA DE ORDEN DE TRABAJO
            if ($rowLineaActualizada->ID_ORDEN_TRABAJO_LINEA != NULL):

                //BUSCO LA ORDEN DE TRABAJO LINEA
                $rowOrdenTrabajoLinea = $bd->VerReg("ORDEN_TRABAJO_LINEA", "ID_ORDEN_TRABAJO_LINEA", $rowLineaActualizada->ID_ORDEN_TRABAJO_LINEA);

                //BUSCAMOS LA DEMANDA NO ASOCIADA A LA LINEA DE OT
                $rowDemandaOT = $reserva->get_demanda("OT", $rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO_LINEA);

                //CALCULO LA CANTIDAD DE LA LINEA DE PEDIDO ASOCIADA A LA LINEA DE LA OT
                $sqlCantidadLineaPedidoAsociadaLineaOT = "SELECT IF(SUM(CRL.CANTIDAD) IS NULL, 0, SUM(CRL.CANTIDAD)) AS TOTAL_COLA_LINEA_PEDIDO 
                                                            FROM COLA_RESERVA_LINEA CRL 
                                                            INNER JOIN COLA_RESERVA CR ON CR.ID_COLA_RESERVA = CRL.ID_COLA_RESERVA 
                                                            WHERE CR.ID_DEMANDA = $rowDemandaOT->ID_DEMANDA AND CRL.ID_PEDIDO_SALIDA_LINEA = $rowLineaActualizada->ID_PEDIDO_SALIDA_LINEA AND CRL.BAJA = 0 AND CR.ESTADO = 'Esperando Pedido Traslado' AND CR.BAJA = 0";
                $resultCantidadLineaPedidoAsociadaLineaOT = $bd->ExecSQL($sqlCantidadLineaPedidoAsociadaLineaOT);
                $rowCantidadLineaPedidoAsociadaLineaOT = $bd->SigReg($resultCantidadLineaPedidoAsociadaLineaOT);
                $cantidadLineaPedidoAsociadaLineaOT = $rowCantidadLineaPedidoAsociadaLineaOT->TOTAL_COLA_LINEA_PEDIDO;

                //CALCULO LA VARIACION DE LA DEMANDA Y LA LINEA DE OT
                $variacionLineaOT = min($variacionLineaPedido, $cantidadLineaPedidoAsociadaLineaOT);

                //DECREMENTO LA CANTIDAD PEDIDA DE LA LINEA DE LA ORDEN DE TRABAJO
                $sqlUpdate = "UPDATE ORDEN_TRABAJO_LINEA SET
                                CANTIDAD_PEDIDA = CANTIDAD_PEDIDA - $variacionLineaOT
                                WHERE ID_ORDEN_TRABAJO_LINEA = $rowLineaActualizada->ID_ORDEN_TRABAJO_LINEA";
                $bd->ExecSQL($sqlUpdate);

                //GUARDAMOS LA CANTIDAD DEL CAMBIO
                $array_cambios['CANTIDAD'] = $variacionLineaOT * -1;
                $array_cambios['ID_PEDIDO_SALIDA_LINEA'] = $rowLineaActualizada->ID_PEDIDO_SALIDA_LINEA;

                $reserva->modificacion_demanda($rowDemandaOT->ID_DEMANDA, $array_cambios);

                $arrInterfacesLineasOrdenTrabajoEnviarSAP[] = $rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO_LINEA;
            endif;

        //SI EL PEDIDO ES DE PLANIFICADOS
        elseif (($rowPedido->TIPO_PEDIDO == 'Traslado') && (($rowPedido->TIPO_TRASLADO == 'Planificado Material Obligatorio') || ($rowPedido->TIPO_TRASLADO == 'Planificado Material No Obligatorio') || ($rowPedido->TIPO_TRASLADO == 'Planificado Material Indivisible'))):

            //CALCULO LA CANTIDAD DE LA LINEA DE PEDIDO ASOCIADA A LAS LINEAS DE LAS OTs
            $sqlCantidadLineaPedidoAsociadaLineasOT = "SELECT IF(SUM(CANTIDAD_CUBIERTA_POR_PEDIDO_SALIDA_LINEA) IS NULL, 0, SUM(CANTIDAD_CUBIERTA_POR_PEDIDO_SALIDA_LINEA)) AS TOTAL_LINEA_PEDIDO_ASOCIADA 
                                                        FROM ORDEN_TRABAJO_LINEA_CUBIERTA_PEDIDO 
                                                        WHERE ID_PEDIDO_SALIDA_LINEA = $rowLineaActualizada->ID_PEDIDO_SALIDA_LINEA AND BAJA = 0";
            $resultCantidadLineaPedidoAsociadaLineasOT = $bd->ExecSQL($sqlCantidadLineaPedidoAsociadaLineasOT);
            $rowCantidadLineaPedidoAsociadaLineasOT = $bd->SigReg($resultCantidadLineaPedidoAsociadaLineasOT);
            $cantidadLineaPedidoAsociadaLineasOT = $rowCantidadLineaPedidoAsociadaLineasOT->TOTAL_LINEA_PEDIDO_ASOCIADA;

            //CALCULO LA CANTIDAD PENDIENTE DE DESASIGNAR
            $cantidadPdteDesasignar = min($variacionLineaPedido, $cantidadLineaPedidoAsociadaLineasOT);

            //BUSCO LAS LINEAS DE ORDEN DE TRABAO RELACIONADAS CON ESTA LINEA DE PEDIDO
            $sqlOTLineas    = "SELECT OTLCP.ID_ORDEN_TRABAJO_LINEA, OTLCP.CANTIDAD_CUBIERTA_POR_PEDIDO_SALIDA_LINEA
                                FROM ORDEN_TRABAJO_LINEA_CUBIERTA_PEDIDO OTLCP
                                INNER JOIN ORDEN_TRABAJO_LINEA OTL ON OTL.ID_ORDEN_TRABAJO_LINEA = OTLCP.ID_ORDEN_TRABAJO_LINEA
                                INNER JOIN ORDEN_TRABAJO OT ON OT.ID_ORDEN_TRABAJO = OTL.ID_ORDEN_TRABAJO
                                WHERE OTLCP.ID_PEDIDO_SALIDA_LINEA = $rowLineaActualizada->ID_PEDIDO_SALIDA_LINEA AND OTLCP.BAJA = 0
                                ORDER BY OTL.FECHA_PLANIFICADA DESC, OT.ORDEN_TRABAJO_SAP DESC";
            $resultOTLineas = $bd->ExecSQL($sqlOTLineas);
            while ($rowOTLinea = $bd->SigReg($resultOTLineas)):

                $cantidadDesasignar = min($cantidadPdteDesasignar, $rowOTLinea->CANTIDAD_CUBIERTA_POR_PEDIDO_SALIDA_LINEA);

                //BUSCO LA ORDEN DE TRABAJO LINEA
                $rowOrdenTrabajoLinea = $bd->VerReg("ORDEN_TRABAJO_LINEA", "ID_ORDEN_TRABAJO_LINEA", $rowOTLinea->ID_ORDEN_TRABAJO_LINEA);

                //DECREMENTO LA CANTIDAD PEDIDA DE LA LINEA DE LA ORDEN DE TRABAJO
                $sqlUpdate = "UPDATE ORDEN_TRABAJO_LINEA SET
                                CANTIDAD_PEDIDA = CANTIDAD_PEDIDA - $cantidadDesasignar
                                WHERE ID_ORDEN_TRABAJO_LINEA = $rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO_LINEA";
                $bd->ExecSQL($sqlUpdate);

                //ACTUALIZO LOS DATOS DE LA ASOCIACION DE LA LINEA DE LA OT CON LA LINEA DEL PEDIDO
                $updateEstado = "";
                if ($rowOTLinea->CANTIDAD_CUBIERTA_POR_PEDIDO_SALIDA_LINEA - $cantidadDesasignar < EPSILON_SISTEMA):
                    $updateEstado = ", BAJA = 1";
                endif;
                $sqlUpdate = "UPDATE ORDEN_TRABAJO_LINEA_CUBIERTA_PEDIDO SET 
                                CANTIDAD_CUBIERTA_POR_PEDIDO_SALIDA_LINEA = CANTIDAD_CUBIERTA_POR_PEDIDO_SALIDA_LINEA - $cantidadDesasignar
                                $updateEstado
                                WHERE ID_ORDEN_TRABAJO_LINEA = $rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO_LINEA AND ID_PEDIDO_SALIDA_LINEA = $rowLineaActualizada->ID_PEDIDO_SALIDA_LINEA";
                $bd->ExecSQL($sqlUpdate);

                //BUSCAMOS LA DEMANDA NO ASOCIADA A LA LINEA DE OT
                $rowDemandaOT = $reserva->get_demanda("OT", $rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO_LINEA);

                //SI EXISTE LA DEMANDA LA MODIFICO
                if ($rowDemandaOT != false):
                    //GUARDAMOS LA CANTIDAD DEL CAMBIO
                    $array_cambios['CANTIDAD'] = $cantidadDesasignar * -1;
                    $array_cambios['ID_PEDIDO_SALIDA_LINEA'] = $rowLineaActualizada->ID_PEDIDO_SALIDA_LINEA;

                    $reserva->modificacion_demanda($rowDemandaOT->ID_DEMANDA, $array_cambios);
                endif;

                //ACTUALIZO LA CANTIDAD PENDIENTE DE DESASIGNAR
                $cantidadPdteDesasignar -= $cantidadDesasignar;

                $arrInterfacesLineasOrdenTrabajoEnviarSAP[] = $rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO_LINEA;
            endwhile;
        endif;

        //RECORRO EL ARRAY DE LINEAS PARA LANZAR LAS INTERFACES CORRESPONDIENTES
        foreach ($arrInterfacesLineasOrdenTrabajoEnviarSAP as $idOrdenTrabajoLinea):

            //BUSCO LA LINEA DE LA ORDEN DE TRABAJO
            $rowOrdenTrabajoLinea = $bd->VerReg("ORDEN_TRABAJO_LINEA", "ID_ORDEN_TRABAJO_LINEA", $idOrdenTrabajoLinea);

            //BUSCO LA ORDEN DE TRABAJO
            $rowOrdenTrabajo = $bd->VerReg("ORDEN_TRABAJO", "ID_ORDEN_TRABAJO", $rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO);

            if ($orden_trabajo->esTransmitibleInterfaz("Tratamiento OT", "Creacion/Modificacion pedido traslado", "T01", $rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO_LINEA)):

                //SI FALLA LA TRANSMISION DEL SEMAFORO Y NO ESTA YA COMO PENDIENTE, LO GRABO PARA SABER QUE ESTA PENDIENTE DE REALIZARSE
                $num = $bd->NumRegsTabla("INTERFACES_PENDIENTES_TRANSMITIR", "INTERFAZ_PENDIENTE_TRANSMITIR = 'ordenes_trabajo_semaforo' AND ID_OBJETO = $rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO_LINEA AND TABLA_OBJETO = 'ORDEN_TRABAJO_LINEA' AND BAJA = 0");
                if ($num == 0):
                    //DOY DE BAJA CUALQUIER OTRA LLAMADA PENDIENTE DE ESTA LINEA
                    $sqlUpdate = "UPDATE INTERFACES_PENDIENTES_TRANSMITIR SET BAJA = 1 WHERE INTERFAZ_PENDIENTE_TRANSMITIR = 'ordenes_trabajo_semaforo' AND ID_OBJETO = $rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO_LINEA AND TABLA_OBJETO = 'ORDEN_TRABAJO_LINEA' AND BAJA = 0";
                    $bd->ExecSQL($sqlUpdate);

                    $sqlInsert = "INSERT INTO INTERFACES_PENDIENTES_TRANSMITIR SET
                                  INTERFAZ_PENDIENTE_TRANSMITIR = 'ordenes_trabajo_semaforo'
                                  , ID_OBJETO = $rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO_LINEA
                                  , TABLA_OBJETO = 'ORDEN_TRABAJO_LINEA'
                                  , NUMERO_LLAMADAS_INTERFAZ = '0'
                                  , BAJA = 0";
                    $bd->ExecSQL($sqlInsert);
                endif;
            endif;

            if ($orden_trabajo->esTransmitibleInterfaz("Tratamiento OT", "Creacion/Modificacion pedido traslado", "T02", $rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO_LINEA)):

                //GRABO LAS RESERVAS A ENVIAR PARA QUE EL PROCESO AUTOMATICO LAS LANCE
                $num = $bd->NumRegsTabla("INTERFACES_PENDIENTES_TRANSMITIR", "INTERFAZ_PENDIENTE_TRANSMITIR = 'ordenes_trabajo_reserva' AND ID_OBJETO = $rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO_LINEA AND TABLA_OBJETO = 'ORDEN_TRABAJO_LINEA' AND BAJA = 0");
                if ($num == 0):
                    //DOY DE BAJA CUALQUIER OTRA LLAMADA PENDIENTE DE ESTA LINEA
                    $sqlUpdate = "UPDATE INTERFACES_PENDIENTES_TRANSMITIR SET BAJA = 1 WHERE INTERFAZ_PENDIENTE_TRANSMITIR = 'ordenes_trabajo_reserva' AND ID_OBJETO = $rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO_LINEA AND TABLA_OBJETO = 'ORDEN_TRABAJO_LINEA' AND BAJA = 0";
                    $bd->ExecSQL($sqlUpdate);

                    $sqlInsert = "INSERT INTO INTERFACES_PENDIENTES_TRANSMITIR SET
                                INTERFAZ_PENDIENTE_TRANSMITIR = 'ordenes_trabajo_reserva'
                                , ID_OBJETO = $rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO_LINEA
                                , TABLA_OBJETO = 'ORDEN_TRABAJO_LINEA'
                                , BAJA = 0";
                    $bd->ExecSQL($sqlInsert);
                endif;

                //ACTUALIZO EL ESTADO TRATADA DE LA LINEA DE LA ORDEN DE TRABAJO
                $orden_trabajo->ActualizarLinea_Estados($rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO_LINEA);
            endif;

            if ($orden_trabajo->esTransmitibleInterfaz("Tratamiento OT", "Creacion/Modificacion pedido traslado", "T02", $rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO_LINEA, "Si")):

                //GRABO LAS RESERVAS A ENVIAR PARA QUE EL PROCESO AUTOMATICO LAS LANCE
                $num = $bd->NumRegsTabla("INTERFACES_PENDIENTES_TRANSMITIR", "INTERFAZ_PENDIENTE_TRANSMITIR = 'ordenes_trabajo_reserva' AND ID_OBJETO = $rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO_LINEA AND TABLA_OBJETO = 'ORDEN_TRABAJO_LINEA' AND BAJA = 0");
                if ($num == 0):
                    //DOY DE BAJA CUALQUIER OTRA LLAMADA PENDIENTE DE ESTA LINEA
                    $sqlUpdate = "UPDATE INTERFACES_PENDIENTES_TRANSMITIR SET BAJA = 1 WHERE INTERFAZ_PENDIENTE_TRANSMITIR = 'ordenes_trabajo_reserva' AND ID_OBJETO = $rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO_LINEA AND TABLA_OBJETO = 'ORDEN_TRABAJO_LINEA' AND BAJA = 0";
                    $bd->ExecSQL($sqlUpdate);

                    $sqlInsert = "INSERT INTO INTERFACES_PENDIENTES_TRANSMITIR SET
                                    INTERFAZ_PENDIENTE_TRANSMITIR = 'ordenes_trabajo_reserva'
                                    , ID_OBJETO = $rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO_LINEA
                                    , TABLA_OBJETO = 'ORDEN_TRABAJO_LINEA'
                                    , CANCELACION_T02 = 1
                                    , BAJA = 0";
                    $bd->ExecSQL($sqlInsert);
                endif;

                //ACTUALIZO EL ESTADO TRATADA DE LA LINEA DE LA ORDEN DE TRABAJO
                $orden_trabajo->ActualizarLinea_Estados($rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO_LINEA);
            endif;

            if ($orden_trabajo->esTransmitibleInterfaz("Tratamiento OT", "Creacion/Modificacion pedido traslado", "T06", $rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO_LINEA)):
                //MARCAMOS LA LINEA PARA VOLVER A CALCULAR
                $sqlUpdate = "UPDATE ORDEN_TRABAJO_LINEA SET
                                DISPONIBILIDAD_FECHA_PLANIFICADA = 'Pendiente Tratar'
                                WHERE ID_ORDEN_TRABAJO_LINEA = " . $rowOrdenTrabajoLinea->ID_ORDEN_TRABAJO_LINEA;
                $bd->ExecSQL($sqlUpdate);
            endif;
        endforeach;
        //FIN RECORRO EL ARRAY DE LINEAS PARA LANZAR LAS INTERFACES CORRESPONDIENTES

        //TIPOS DE PEDIDO QUE ADMITEN DEMANDAS
        $tipo_pedido_admite_reservas = false;
        if (($rowPedido->TIPO_PEDIDO_SAP == 'ZTRB') || ($rowPedido->TIPO_PEDIDO_SAP == 'ZTRD')):
            $tipo_pedido_admite_reservas = true;
        endif;

        //LLAMAR A UNA FUNCION QUE ACTUALICE LAS DEMANDAS DEL PEDIDO SEGUN EL ESTADO ACTUAL
        if ($tipo_pedido_admite_reservas == true) :
            $arr_resultado_reserva = $reserva->actualizar_demandas_pedido($rowLinea->ID_PEDIDO_SALIDA, $rowLinea->ID_PEDIDO_SALIDA_LINEA);
            if (isset($arr_resultado_reserva['error']) && $arr_resultado_reserva['error'] != "")://SI VIENE ERROR
                $arrayError = array(
                    'ERROR'         => true,
                    'MENSAJE_ERROR' => 'ErrorActualizarDemanda',
                    'STR_KO'        => ''
                );

                return $arrayError;
            endif;
        endif;

        // DEVOLVEMOS EL RESULTADO SIN ERRORES
        $arrayError = array(
            'ERROR'                    => false,
            'MENSAJE_ERROR'            => '',
            'STR_KO'                   => '',
            'LINEA_GENERAR_INCIDENCIA' => $pedidoSalidaLineaGenerarIncidenciaSistema
        );

        return $arrayError;
    }

    /**
     * SI LA FECHA VIENE VACIA COJO LA SIGUIENTE FECHA A PARTIR DEL DÍA DE HOY
     * @param $idPedidoSalidaLinea
     * @retrun string
     */
    function getSiguienteFechaShippingDate($idPedidoSalidaLinea)
    {
        global $bd;
        global $auxiliar;
        global $administrador;
        global $orden_trabajo;

        $rowPedidoSalidaLinea = $bd->VerReg("PEDIDO_SALIDA_LINEA", "ID_PEDIDO_SALIDA_LINEA", $idPedidoSalidaLinea);


        //BUSCO EL ALMACEN DE ORIGEN
        $rowAlmacenOrigen = $bd->VerReg("ALMACEN", "ID_ALMACEN", $rowPedidoSalidaLinea->ID_ALMACEN_ORIGEN);

        //BUSCO EL CENTRO FISICO DE ORIGEN
        $rowCFOrigen  = $bd->VerReg("CENTRO_FISICO", "ID_CENTRO_FISICO", $rowAlmacenOrigen->ID_CENTRO_FISICO);
        $minsMinimos  = $rowCFOrigen->TIEMPO_MINIMO_CREAR_ORDEN_PREPARACION_MINUTOS;
        $horasMinimas = $rowCFOrigen->TIEMPO_MINIMO_CREAR_ORDEN_PREPARACION_HORAS;

        //BUSCO EL CALENDARIO DEL CENTRO FISICO DE ORIGEN
        $rowCalendarioCF = $bd->VerRegRest("CALENDARIO_FESTIVOS", "ID_CENTRO_FISICO = $rowCFOrigen->ID_CENTRO_FISICO AND YEAR = '" . date('Y') . "' AND BAJA = 0", "No");
        if ($rowCalendarioCF != false):
            //BUSCO EL PROXIMO DIA LABORABLE DEL CENTRO FISICO DE ORIGEN
            $rowHorarioCF = $bd->VerRegRest("CALENDARIO_FESTIVOS_HORARIO", "ID_CALENDARIO_FESTIVOS = $rowCalendarioCF->ID_CALENDARIO_FESTIVOS AND FECHA > CURDATE() ORDER BY FECHA ASC LIMIT 1", "No");

            if ($rowHorarioCF != false):
                $fechaShippingDate = $rowHorarioCF->FECHA;

                if ($rowHorarioCF->HORA_FIN2 != NULL):
                    $campoHoraCierreCF = "HORA_FIN2";
                else:
                    $campoHoraCierreCF = "HORA_FIN";
                endif;

                //CALCULAMOS CUAL ES LA HORA LIMITE EN BASE AL TIEMPO QUE SE TARDA EN REALIZAR LAS PREPARACIONES Y LA HORA DE CIERRE DEL CENTRO FISICO EL DIA
                $sqlHoraLimite    = "SELECT DATE_SUB(DATE_SUB($campoHoraCierreCF, INTERVAL $minsMinimos MINUTE), INTERVAL $horasMinimas HOUR) AS HORA_LIMITE
                                    FROM CALENDARIO_FESTIVOS_HORARIO
                                    WHERE ID_CALENDARIO_FESTIVOS_HORARIO = $rowHorarioCF->ID_CALENDARIO_FESTIVOS_HORARIO";
                $resultHoraLimite = $bd->ExecSQL($sqlHoraLimite);
                $rowHoraLimite    = $bd->SigReg($resultHoraLimite);

                return $fechaShippingDate . " " . $rowHoraLimite->HORA_LIMITE;
            endif;
        endif;

        return "";
    }

    /**
     * @param $idPedidoEntradaLinea ID DE LA LINEA DEL PEDIDO DE ENTRADA A GRABAR LAS ENTREGAS
     * @return array ARRAY A DEVOLVER
     */
    function rellenarCantidadEntregasLineaPedido($idPedidoEntradaLinea)
    {
        global $bd;
        global $pedido;

        //VARIABLE A RETORNAR
        $arrDevuelto = array();

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
                                WHERE PE.TIPO_PEDIDO = 'Compra' AND PEL.INDICADOR_BORRADO IS NULL AND PEL.BAJA = 0 AND MEL.ID_PEDIDO_LINEA = $idPedidoEntradaLinea AND MEL.LINEA_ANULADA = 0 AND MEL.BAJA = 0 AND (MEL.ID_TIPO_BLOQUEO IS NULL OR MEL.ID_TIPO_BLOQUEO IN ($rowTipoBloqueoPreventivo->ID_TIPO_BLOQUEO, $rowTipoBloqueoRetenidoCalidadNoPreventivoReparableEnGarantia->ID_TIPO_BLOQUEO, $rowTipoBloqueoRetenidoCalidadNoPreventivoReparableNoEnGarantia->ID_TIPO_BLOQUEO, $rowTipoBloqueoRetenidoCalidadNoPreventivoNoReparableNoEnGarantia->ID_TIPO_BLOQUEO))";

        $resultCantidadRecepcionada = $bd->ExecSQL($sqlCantidadRecepcionada);
        $rowCantidadRecepcionada = $bd->SigReg($resultCantidadRecepcionada);
        $cantidadRecepcionada = $rowCantidadRecepcionada->CANTIDAD_RECEPCIONADA;

        //ASIGNAMOS LAS ENTREGAS DE LA LINEA DEL PEDIDO DE ENTRADA
        $resultadoAsignacionEntregas = $pedido->AsignarEntregasLineaPedido($idPedidoEntradaLinea, $cantidadRecepcionada);
        if ($resultadoAsignacionEntregas["Resultado"] != "OK"):
            $arrDevuelto["Errores"][] = "La cantidad a recepcionar supera la cantidad de las entregas";
        endif;

        //DEVUELVO EL ARRAY
        return $arrDevuelto;
    }

    /**
     * @param $idLineaPedido Linea de pedido a la que asignar las entregas
     * @param $cantidad Cantidad a asignar a las entregas
     * @return array Array con el resultado de la ejecucion de la funcion
     */
    function AsignarEntregasLineaPedido($idLineaPedido, $cantidad)
    {
        global $bd;

        //VARIABLE A DEVOLVER CON EL RESULTADO
        $arrDevolver = array();

        //REVISO SI TIENE ENTREGAS
        $num = $bd->NumRegsTabla("PEDIDO_ENTRADA_LINEA_ENTREGA", "ID_PEDIDO_ENTRADA_LINEA = $idLineaPedido AND BAJA = 0");
        if ($num == 0): //NO TIENE ENTREGAS
            $arrDevolver["Resultado"] = "OK";
        else: //TIENE ENTREGAS
            //BUSCO LAS ENTREGAS CON CANTIDAD PENDIENTE ORDENADAS POR FECHA
            $sqlEntregas = "SELECT * 
                            FROM PEDIDO_ENTRADA_LINEA_ENTREGA 
                            WHERE ID_PEDIDO_ENTRADA_LINEA = $idLineaPedido AND BAJA = 0 AND CANTIDAD_RECEPCIONADA < CANTIDAD 
                            ORDER BY FECHA_ENTREGA ASC";
            $resultEntregas = $bd->ExecSQL($sqlEntregas);
            while (($rowEntrega = $bd->SigReg($resultEntregas)) && ($cantidad > EPSILON_SISTEMA)):
                //EXTRAIGO LA CANTIDAD A ASIGANR A LA ENTREGA
                $cantidadAsignar = min($cantidad, ($rowEntrega->CANTIDAD - $rowEntrega->CANTIDAD_RECEPCIONADA));

                //ACTUALIZO LA ENTREGA
                $sqlUpdate = "UPDATE PEDIDO_ENTRADA_LINEA_ENTREGA SET 
                              CANTIDAD_RECEPCIONADA = CANTIDAD_RECEPCIONADA + $cantidadAsignar 
                              WHERE ID_PEDIDO_ENTRADA_LINEA_ENTREGA = $rowEntrega->ID_PEDIDO_ENTRADA_LINEA_ENTREGA";
                $bd->ExecSQL($sqlUpdate);

                //DESCUENTO LA CANTIDAD ASIGNADA A LA ENTREGA
                $cantidad = $cantidad - $cantidadAsignar;
            endwhile;

            //** TRAS LA AMPLIACIÓN DE LA DMND0006110 SE REPARTE LA CANTIDAD ENTRE TODAS LAS ENTREGAS
            //** SI QUEDARA CANTIDAD RESTANTE SIN ASIGNAR AL HABER NO HABER MAS ENTREGAS DISPONIBLES
            //** YA NO SE MUESTRA UN ERROR

            $arrDevolver["Resultado"] = "OK";

//            //SI QUEDA CANTIDAD POR ASIGNAR A ENTREGAS MUESTRO ERROR
//            if ($cantidad > EPSILON_SISTEMA):
//                //BUSCO LA LINEA DE PEDIDO
//                $rowPedidoEntradaLinea = $bd->VerReg("PEDIDO_ENTRADA_LINEA", "ID_PEDIDO_ENTRADA_LINEA", $idLineaPedido);
//
//                //CALCULO LA CANTIDAD TOLERANCIA DE LA LINEA
//                $cantidadToleranciaLineaPedido = $rowPedidoEntradaLinea->CANTIDAD * ($rowPedidoEntradaLinea->TOLERANCIA / 100);
//
//                //SI LA CANTIDAD PENDIENTE NO SUPERA LA TOLERANCIA
//                if ($cantidad > $cantidadToleranciaLineaPedido): //NO SE PUEDE ASIGNAR MAS CANTIDAD DE LA TOLERANCIA
//                    $arrDevolver["Resultado"] = "Error";
//                    $arrDevolver["Errores"] = "CantidadRecepcionarSuperarCantidadPendienteEntregas";
//                else: //ASIGNO LA CANTIDAD PENDIENTE A LA ULTIMA ENTREGA
//                    $sqlUltimaEntrega = "SELECT *
//                                         FROM PEDIDO_ENTRADA_LINEA_ENTREGA
//                                         WHERE ID_PEDIDO_ENTRADA_LINEA = $idLineaPedido AND BAJA = 0
//                                         ORDER BY FECHA_ENTREGA DESC";
//                    $resultUltimaEntrega = $bd->ExecSQL($sqlUltimaEntrega);
//                    if (($resultUltimaEntrega == false) || ($bd->NumRegs($resultUltimaEntrega) == 0)): //NO HAY REGISTROS
//                        $arrDevolver["Resultado"] = "Error";
//                        $arrDevolver["Errores"] = "CantidadRecepcionarSuperarCantidadPendienteEntregas";
//                    else:
//                        //RECUPERO LA ULTIMA ENTREGA
//                        $rowUltimaEntrega = $bd->SigReg($resultUltimaEntrega);
//
//                        //ACTUALIZO LA ENTREGA
//                        $sqlUpdate = "UPDATE PEDIDO_ENTRADA_LINEA_ENTREGA SET
//                                      CANTIDAD_RECEPCIONADA = CANTIDAD_RECEPCIONADA + $cantidad
//                                      WHERE ID_PEDIDO_ENTRADA_LINEA_ENTREGA = $rowUltimaEntrega->ID_PEDIDO_ENTRADA_LINEA_ENTREGA";
//                        $bd->ExecSQL($sqlUpdate);
//
//                        $arrDevolver["Resultado"] = "OK";
//                    endif;
//                endif;
//            else:
//                $arrDevolver["Resultado"] = "OK";
//            endif;
        endif;
        //FIN REVISO SI TIENE ENTREGAS

        //DEVOLVEMOS EL ARRAY CORRESPONDIENTE
        return $arrDevolver;
    }

    /**
     * @param $idLineaPedido Linea de pedido de la que desasignar las entregas
     * @param $cantidad Cantidad a desasignar de las entregas
     * @return array Array con el resultado de la ejecucion de la funcion
     */
    function DesasignarEntregasLineaPedido($idLineaPedido, $cantidad)
    {
        global $bd;

        //VARIABLE A DEVOLVER CON EL RESULTADO
        $arrDevolver = array();

        //REVISO SI TIENE ENTREGAS
        $num = $bd->NumRegsTabla("PEDIDO_ENTRADA_LINEA_ENTREGA", "ID_PEDIDO_ENTRADA_LINEA = $idLineaPedido AND BAJA = 0");
        if ($num == 0): //NO TIENE ENTREGAS
            $arrDevolver["Resultado"] = "OK";
        else: //TIENE ENTREGAS
            while ($cantidad > EPSILON_SISTEMA):
                //BUSCO LAS ENTREGAS CON CANTIDAD PENDIENTE ORDENADAS POR FECHA
                $sqlEntregas = "SELECT * 
                                FROM PEDIDO_ENTRADA_LINEA_ENTREGA 
                                WHERE ID_PEDIDO_ENTRADA_LINEA = $idLineaPedido AND BAJA = 0 AND CANTIDAD_RECEPCIONADA > 0
                                ORDER BY FECHA_ENTREGA DESC";
                $resultEntregas = $bd->ExecSQL($sqlEntregas);
                if (($resultEntregas == false) || ($bd->NumRegs($resultEntregas) == 0)):
                    //SALGO DEL BUCLE
                    break;
                else:
                    while (($rowEntrega = $bd->SigReg($resultEntregas)) && ($cantidad > EPSILON_SISTEMA)):
                        //EXTRAIGO LA CANTIDAD A DESASIGANR A LA ENTREGA
                        $cantidadDesasignar = min($cantidad, $rowEntrega->CANTIDAD_RECEPCIONADA);

                        //ACTUALIZO LA ENTREGA
                        $sqlUpdate = "UPDATE PEDIDO_ENTRADA_LINEA_ENTREGA SET 
                                      CANTIDAD_RECEPCIONADA = CANTIDAD_RECEPCIONADA - $cantidadDesasignar 
                                      WHERE ID_PEDIDO_ENTRADA_LINEA_ENTREGA = $rowEntrega->ID_PEDIDO_ENTRADA_LINEA_ENTREGA";
                        $bd->ExecSQL($sqlUpdate);

                        //DESCUENTO LA CANTIDAD ASIGNADA A LA ENTREGA
                        $cantidad = $cantidad - $cantidadDesasignar;
                    endwhile;
                endif;
            endwhile;

            //** TRAS LA AMPLIACIÓN DE LA DMND0006110 SE REPARTE LA CANTIDAD ENTRE TODAS LAS ENTREGAS
            //** SI QUEDARA CANTIDAD RESTANTE SIN ASIGNAR AL HABER NO HABER MAS ENTREGAS DISPONIBLES
            //** YA NO SE MUESTRA UN ERROR

            $arrDevolver["Resultado"] = "OK";

//            //SI QUEDA CANTIDAD POR ASIGNAR A ENTREGAS MUESTRO ERROR
//            if ($cantidad > EPSILON_SISTEMA):
//                $arrDevolver["Resultado"] = "Error";
//                $arrDevolver["Errores"] = "CantidadAnularSuperiorCantidadRecepcionada";
//            else:
//                $arrDevolver["Resultado"] = "OK";
//            endif;
        endif;
        //FIN REVISO SI TIENE ENTREGAS

        //DEVOLVEMOS EL ARRAY CORRESPONDIENTE
        return $arrDevolver;
    }

    /**
     * @param $idLineaPedido Linea de pedido de la que inicializar el campo CANTIDAD_RECEPCIONADA
     * @return array Array con el resultado de la ejecucion de la funcion
     */
    function InicializaCantidadRecepcionadaEntregasLineaPedido($idLineaPedido)
    {
        global $bd;

        //REVISO SI TIENE ENTREGAS
        $num = $bd->NumRegsTabla("PEDIDO_ENTRADA_LINEA_ENTREGA", "ID_PEDIDO_ENTRADA_LINEA = $idLineaPedido AND BAJA = 0");
        if ($num == 0): //NO TIENE ENTREGAS
            $arrDevolver["Resultado"] = "OK";
        else: //TIENE ENTREGAS
            $sqlEntregas = "SELECT * 
                                FROM PEDIDO_ENTRADA_LINEA_ENTREGA 
                                WHERE ID_PEDIDO_ENTRADA_LINEA = $idLineaPedido AND BAJA = 0 AND CANTIDAD_RECEPCIONADA > 0
                                ORDER BY FECHA_ENTREGA DESC";
            $resultEntregas = $bd->ExecSQL($sqlEntregas);
            if (($resultEntregas == false) || ($bd->NumRegs($resultEntregas) == 0)):
                //SALGO DEL BUCLE
            else:
                while ($rowEntrega = $bd->SigReg($resultEntregas)):
                    //ACTUALIZO LA ENTREGA
                    $sqlUpdate = "UPDATE PEDIDO_ENTRADA_LINEA_ENTREGA SET 
                                  CANTIDAD_RECEPCIONADA = 0
                                  WHERE ID_PEDIDO_ENTRADA_LINEA_ENTREGA = $rowEntrega->ID_PEDIDO_ENTRADA_LINEA_ENTREGA";
                    $bd->ExecSQL($sqlUpdate);
                    endwhile;
            endif;
        endif;
        //FIN REVISO SI TIENE ENTREGAS
    }
} // FIN CLASE PEDIDO