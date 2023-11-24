<?

//Se utilizará para controlar las etiquetas y tipo de etiquetado

class lineas_etiqueta
{

    function idMovimiento($linea)
    {
        global $bd;

        $sqlMovimiento    = "SELECT ID_MOVIMIENTO_ENTRADA AS MOVIMIENTO FROM MOVIMIENTO_ENTRADA_LINEA WHERE ID_MOVIMIENTO_ENTRADA_LINEA = $linea";
        $resultMovimiento = $bd->ExecSQL($sqlMovimiento);
        $rowMovimiento    = $bd->SigReg($resultMovimiento);

        return $rowMovimiento->MOVIMIENTO;
    }

    function subLineas($linea)
    {
        global $bd;

        // HAYO LAS LINEAS A BUSCAR
        $sql          = "SELECT * FROM MOVIMIENTO_ENTRADA_LINEA_ETIQUETAS_MANUALES WHERE ID_MOVIMIENTO_ENTRADA_LINEA = $linea ORDER BY ID_MOVIMIENTO_ENTRADA_LINEA_ETIQUETAS_MANUALES";
        $resultLineas = $bd->ExecSQL($sql);

        return $resultLineas;
    }

    //Cantidad total de una linea
    function cantidad($linea)
    {
        global $bd;

        $sqlCantidad    = "SELECT CANTIDAD FROM MOVIMIENTO_ENTRADA_LINEA WHERE ID_MOVIMIENTO_ENTRADA_LINEA = $linea";
        $resultCantidad = $bd->ExecSQL($sqlCantidad);
        $rowCantidad    = $bd->SigReg($resultCantidad);

        return $rowCantidad->CANTIDAD;
    }

    //Cantidad de la linea asociada en etiquetas
    function cantidadEtiquetas($linea)
    {
        global $bd;

        $sqlCantidadEtiquetas    = "SELECT SUM(TOTAL) AS TOTAL FROM MOVIMIENTO_ENTRADA_LINEA_ETIQUETAS_MANUALES WHERE ID_MOVIMIENTO_ENTRADA_LINEA = $linea";
        $resultCantidadEtiquetas = $bd->ExecSQL($sqlCantidadEtiquetas);
        $rowCantidadEtiquetas    = $bd->SigReg($resultCantidadEtiquetas);

        return $rowCantidadEtiquetas->TOTAL;
    }

    //Cantidad de la linea sin asociar a etiquetas
    function cantidadPendiente($linea)
    {
        return ($this->cantidad($linea) - $this->cantidadEtiquetas($linea));
    }

    function subLineasMaterialFisico($linea, $idMatFisico)
    {
        global $bd;

        // HAYO LAS SUBLINEAS A BUSCAR
        $sql          = "SELECT * FROM MOVIMIENTO_ENTRADA_LINEA_ETIQUETAS_MANUALES WHERE ID_MOVIMIENTO_ENTRADA_LINEA = $linea AND ID_MATERIAL_FISICO = $idMatFisico ORDER BY ID_MOVIMIENTO_ENTRADA_LINEA_ETIQUETAS_MANUALES";
        $resultLineas = $bd->ExecSQL($sql);

        return $resultLineas;
    }

    //Cantidad total de una linea de un material fisico
    function cantidadMaterialFisico($linea, $idMatFisico)
    {
        global $bd;

        $sqlCantidad    = "SELECT CANTIDAD FROM MOVIMIENTO_ENTRADA_LINEA WHERE ID_MOVIMIENTO_ENTRADA_LINEA = $linea AND ID_MATERIAL_FISICO = $idMatFisico";
        $resultCantidad = $bd->ExecSQL($sqlCantidad);
        $rowCantidad    = $bd->SigReg($resultCantidad);

        return $rowCantidad->CANTIDAD;
    }

    //Cantidad de la linea asociada en etiquetas de un material fisico
    function cantidadEtiquetasMaterialFisico($linea, $idMatFisico)
    {
        global $bd;

        $sqlCantidadEtiquetas    = "SELECT SUM(TOTAL) AS TOTAL FROM MOVIMIENTO_ENTRADA_LINEA_ETIQUETAS_MANUALES WHERE ID_MOVIMIENTO_ENTRADA_LINEA = $linea AND ID_MATERIAL_FISICO = $idMatFisico";
        $resultCantidadEtiquetas = $bd->ExecSQL($sqlCantidadEtiquetas);
        $rowCantidadEtiquetas    = $bd->SigReg($resultCantidadEtiquetas);

        return $rowCantidadEtiquetas->TOTAL;
    }

    //Cantidad de la linea sin asociar a etiquetas de un material fisico
    function cantidadPendienteMaterialFisico($linea, $idMatFisico)
    {
        return ($this->cantidadMaterialFisico($linea, $idMatFisico) - $this->cantidadEtiquetasMaterialFisico($linea, $idMatFisico));
    }
}

?>