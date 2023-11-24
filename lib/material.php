<?php

# material
# Clase albaran contiene todas las funciones necesarias para
# la interaccion con la clase albaran
# Se incluira en las sesiones
# Agosto 2004 Ruben Alutiz Duarte

class material
{

    function __construct()
    {
    } // Fin material

    function Crear_Copia($id_mat_fisico)
    {
        global $bd;

        $row        = $bd->VerReg("MATERIAL_FISICO", "ID_MATERIAL_FISICO", $id_mat_fisico);
        $descripMat = addslashes( (string)$row->DESCRIP_MAT_ACTUAL);

        // BORRO LA COPIA DEL MATERIAL FISICO
        $sql = "DELETE FROM MATERIAL_FISICO_COPIA WHERE ID_MATERIAL_FISICO_COPIA=$id_mat_fisico";
        $bd->ExecSQL($sql);

        $sql = "INSERT INTO MATERIAL_FISICO_COPIA SET ID_MATERIAL_FISICO_COPIA=$row->ID_MATERIAL_FISICO,FECHA_CREACION='$row->FECHA_CREACION', ESTADO='$row->ESTADO',EN_ID_PROVEEDOR=$row->EN_ID_PROVEEDOR,HACIA_ID_PROVEEDOR=$row->HACIA_ID_PROVEEDOR,ID_MATERIAL_ACTUAL='$row->ID_MATERIAL_ACTUAL',NIVEL_REVISION_ACTUAL=$row->NIVEL_REVISION_ACTUAL,DESCRIP_MAT_ACTUAL='$descripMat',MAT_SERIABLE=$row->MAT_SERIABLE,NUM_SERIE='$row->NUM_SERIE',TIPO_ENVIO='$row->TIPO_ENVIO',ID_ALBARAN_LINEA=$row->ID_ALBARAN_LINEA,ID_DEVOLUCION_LINEA=$row->ID_DEVOLUCION_LINEA,CANTIDAD=$row->CANTIDAD,UMA='$row->UMA',TIPO='$row->TIPO',ENSAMBLADO=$row->ENSAMBLADO,ULTIMA_FECHA_ENVIO='$row->ULTIMA_FECHA_ENVIO',NUM_LOTE='$row->NUM_LOTE'";

        $bd->ExecSQL($sql);

    } // Fin Crear_Copia

    function Duplicar($id_mat_fisico, $tipo, $id_linea)
    {
        global $bd;

        $row        = $bd->VerReg("MATERIAL_FISICO", "ID_MATERIAL_FISICO", $id_mat_fisico);
        $descripMat = addslashes( (string)$row->DESCRIP_MAT_ACTUAL);
        $fechaAct   = date("Y-m-d H:i:s");
        if ($tipo == "Albaran"):
            $id_alb_linea = $id_linea;
            $id_dev_linea = 0;
            $sqlRest      = " ID_ALBARAN_LINEA = '$id_linea' ";
        else:
            $id_alb_linea = 0;
            $id_dev_linea = $id_linea;
            $sqlRest      = " ID_DEVOLUCION_LINEA = '$id_linea' ";
        endif;

        // INSERTO LA COPIA DEL MATERIAL FISICO
        $sql = "INSERT INTO MATERIAL_FISICO SET FECHA_CREACION='$fechaAct', ESTADO='$row->ESTADO',EN_ID_PROVEEDOR=$row->EN_ID_PROVEEDOR,HACIA_ID_PROVEEDOR=$row->HACIA_ID_PROVEEDOR,ID_MATERIAL_ACTUAL='$row->ID_MATERIAL_ACTUAL',NIVEL_REVISION_ACTUAL=$row->NIVEL_REVISION_ACTUAL,DESCRIP_MAT_ACTUAL='$descripMat',MAT_SERIABLE=$row->MAT_SERIABLE,NUM_SERIE='$row->NUM_SERIE',TIPO_ENVIO='$row->TIPO_ENVIO',ID_ALBARAN_LINEA=$id_alb_linea,ID_DEVOLUCION_LINEA=$id_dev_linea,CANTIDAD=$row->CANTIDAD,UMA='$row->UMA',TIPO='$row->TIPO',ENSAMBLADO=$row->ENSAMBLADO,NUM_LOTE='$row->NUM_LOTE'";
        $bd->ExecSQL($sql);

        $idMatFisInsert = $bd->MaximoIdTablaRest("MATERIAL_FISICO", "ID_MATERIAL_FISICO", $sqlRest);

        return $idMatFisInsert;

    } // Fin Duplicar

    function Restaurar_Copia($id_mat_fisico, $tipo = "Normal")
    {
        global $bd;
        $row        = $bd->VerReg("MATERIAL_FISICO_COPIA", "ID_MATERIAL_FISICO_COPIA", $id_mat_fisico);
        $descripMat = addslashes( (string)$row->DESCRIP_MAT_ACTUAL);

        if ($tipo == "Ensamblado"): // PUES EL MAT FISICO NO EXISTE
            $sqlAccion = "INSERT INTO";
        else:
            $sqlAccion = "UPDATE";
            $sqlWhere  = "WHERE ID_MATERIAL_FISICO=$id_mat_fisico";
        endif;
        $sql = "$sqlAccion MATERIAL_FISICO SET DIAS_ASIGNADO=0,NUM_AVISOS_ASIGNADO=0,ID_MATERIAL_FISICO=$row->ID_MATERIAL_FISICO_COPIA,FECHA_CREACION='$row->FECHA_CREACION', ESTADO='$row->ESTADO',EN_ID_PROVEEDOR=$row->EN_ID_PROVEEDOR,HACIA_ID_PROVEEDOR=$row->HACIA_ID_PROVEEDOR,ID_MATERIAL_ACTUAL='$row->ID_MATERIAL_ACTUAL',NIVEL_REVISION_ACTUAL=$row->NIVEL_REVISION_ACTUAL,DESCRIP_MAT_ACTUAL='$descripMat',MAT_SERIABLE=$row->MAT_SERIABLE,NUM_SERIE='$row->NUM_SERIE',TIPO_ENVIO='$row->TIPO_ENVIO',ID_ALBARAN_LINEA=$row->ID_ALBARAN_LINEA,ID_DEVOLUCION_LINEA=$row->ID_DEVOLUCION_LINEA,CANTIDAD=$row->CANTIDAD,UMA='$row->UMA',TIPO='$row->TIPO',ENSAMBLADO=$row->ENSAMBLADO,ULTIMA_FECHA_ENVIO='$row->ULTIMA_FECHA_ENVIO',NUM_LOTE='$row->NUM_LOTE' $sqlWhere";

        $bd->ExecSQL($sql);

    } // Fin Restaurar_Copia

    function Desasignar($id_mat_fisico, $tipo = "Normal")
    {
        global $bd;

        if ($tipo == "Ensamblado"): // PUES EL MAT FISICO NO EXISTE
            $this->Restaurar_Copia($id_mat_fisico, $tipo);
            $this->Soluciona_Alerta($id_mat_fisico);

            return;
        endif;

        $rowMatFis = $bd->VerReg("MATERIAL_FISICO", "ID_MATERIAL_FISICO", $id_mat_fisico);

        $sqlWhere = "ID_MATERIAL_FISICO=$id_mat_fisico";
        if ($rowMatFis->MAT_SERIABLE == 0):
            if ($rowMatFis->ID_CONTAINER != 0 && $rowMatFis->TIPO == "Existente"):
                // ENTONCES NO HAY QUE BORRAR
            else:
                $sql = "DELETE FROM MATERIAL_FISICO WHERE $sqlWhere";
                $bd->ExecSQL($sql);
            endif;
        elseif ($rowMatFis->TIPO == "Nuevo"):
            $sql = "DELETE FROM MATERIAL_FISICO WHERE $sqlWhere";
            $bd->ExecSQL($sql);
        else: // EXISTENTE
            $this->Restaurar_Copia($id_mat_fisico);
        endif;
        $this->Soluciona_Alerta($id_mat_fisico);

    } // Fin Desasignar

    function Actualizar($id_mat_fisico, $Estado)
    {
        global $bd;
        global $sqlAdicional;

        if ($Estado == "Almacen" || $Estado == "EnviadoRechazado"):
            $sqlResetDias = " ,DIAS_ASIGNADO=0,NUM_AVISOS_ASIGNADO=0 ";
            $this->Soluciona_Alerta($id_mat_fisico);
        endif;

        $sql = "UPDATE MATERIAL_FISICO SET ESTADO='$Estado' $sqlAdicional $sqlResetDias WHERE ID_MATERIAL_FISICO=$id_mat_fisico";
        $bd->ExecSQL($sql);

    } // Fin Actualizar

    function Soluciona_Alerta($id_mat_fisico)
    {
        global $bd;

        $fechaAct = date("Y-m-d H:i:s");

        $sql = "UPDATE ALERTA_PROV SET REVISADA=1,FECHA_RESOLUCION='$fechaAct' WHERE ID_MATERIAL_FISICO=$id_mat_fisico AND FECHA_RESOLUCION=0 ";
        $bd->ExecSQL($sql);

    } // Fin Soluciona_Alertas

    function obtenerArticulo($ID_MATERIAL)
    {
        global $bd;

        $sql    = "SELECT * FROM MATERIAL WHERE ID_MATERIAL='$ID_MATERIAL'";
        $result = $bd->ExecSQL($sql, "No");
        if ($result == false) return "Err.";
        if ($bd->NumRegs($result) > 0):
            $row = $bd->SigReg($result);

            return $row;
        else:
            return false;
        endif;
    } // FIN obtenerArticulo

    function Obtener_Id($referencia)
    {
        global $bd;

        $sql = "SELECT ID_MATERIAL FROM MATERIAL WHERE REFERENCIA='$referencia'";

        $result = $bd->ExecSQL($sql, "No");
        if ($result == false) return "Err.";
        if ($bd->NumRegs($result) > 0):
            $row = $bd->SigReg($result);

            return $row;
        else:
            return false;
        endif;
    } // FIN obtenerId

    function ObtenerIdMaterial($referencia)
    {
        global $bd;

        $sql = "SELECT ID_MATERIAL FROM MATERIAL WHERE REFERENCIA='$referencia'";

        $result = $bd->ExecSQL($sql, "No");
        if ($result == false) return "Err.";
        if ($bd->NumRegs($result) > 0):
            $row = $bd->SigReg($result);

            return $row->ID_MATERIAL;
        else:
            return false;
        endif;
    } // FIN obtenerId

    function ObtenerArtRef($referencia)
    {
        global $bd;

        $sql    = "SELECT * FROM MATERIAL WHERE REFERENCIA='$referencia'";
        $result = $bd->ExecSQL($sql, "No");
        if ($result == false) return "Err.";
        if ($bd->NumRegs($result) > 0):
            $row = $bd->SigReg($result);

            return $row;
        else:
            return false;
        endif;
    } // FIN obtenerId

    function ObtenerArtDesc($descripcion)
    {
        global $bd;

        $sql    = "SELECT * FROM MATERIAL WHERE DESCRIPCION like '%$descripcion%'";
        $result = $bd->ExecSQL($sql, "No");
        if ($result == false) return "Err.";
        if ($bd->NumRegs($result) == 1):
            $row = $bd->SigReg($result);

            return $row;
        else:
            return false;
        endif;
    } // FIN obtenerId

    function Stock_Total($idMaterial)
    {
        global $bd;

        $sql    = "SELECT STOCK_TOTAL FROM MATERIAL WHERE ID_MATERIAL=$idMaterial";
        $result = $bd->ExecSQL($sql, "No");
        if ($result == false) return "Err.";
        if ($bd->NumRegs($result) > 0):
            $row = $bd->SigReg($result);

            return $row->STOCK_TOTAL;
        else:
            return 0;
        endif;
    }

    function ObtenerReferencia($idMaterial)
    {
        global $bd;

        $row = $bd->VerReg("MATERIAL", "ID_MATERIAL", $idMaterial);

        return $row->REFERENCIA;
    }

    function MaterialAsociadoEntradasAsientos($idMaterial)
    {
        global $bd;

        $where = "ID_MATERIAL = $idMaterial";
        if ($bd->NumRegsTabla("MOVIMIENTO_ENTRADA_LINEA", $where) > 0) return true;

        if ($bd->NumRegsTabla("ASIENTO", $where) > 0) return true;

        return false;
    }

    function TieneMaterialFisicoAsociadoEntradasAsientos($idMaterial)
    {
        global $bd;

        // SELECCIONAMOS EL MATERIAL FISICO ASOCIADO AL MATERIAL
        $sql = "SELECT ID_MATERIAL_FISICO FROM MATERIAL_FISICO WHERE ID_MATERIAL = $idMaterial";

        $res = $bd->ExecSQL($sql, "No");
        if ($res):
            while ($row = $bd->SigReg($res)):
                $where = "ID_MATERIAL_FISICO = $row->ID_MATERIAL_FISICO";
                if ($bd->NumRegsTabla("MATERIAL_FISICO_ENTRADA", $where) > 0) return true;

                if ($bd->NumRegsTabla("MATERIAL_FISICO_ASIENTO", $where) > 0) return true;
            endwhile;
        endif;

        return false;
    }

    // FUNCIONES RELACIONADAS CON EL MATERIAL SERIABLE -------------------------------------------------------------

    function TieneStockSeriableLibre($idMaterial)
    {
        global $bd;

        $arrNumeroSerieAux = $this->NumerosSeriePositivo($idMaterial);
        foreach ($arrNumeroSerieAux as $idMatFisico):
            if (!$this->NumeroSerieAsociadoMovSalida($idMatFisico)):
                return true;
            endif;
        endforeach;

        return false;
    }

    function NumerosSerie($idMaterial)
    {
        // DEVUELVE UN ARRAY CON LOS NÚMEROS DE SERIE DEL MATERIAL

        global $bd;

        $sql = "SELECT NUMERO_SERIE_LOTE FROM MATERIAL_FISICO WHERE NOT ISNULL(NUMERO_SERIE_LOTE) AND ID_MATERIAL = $idMaterial AND TIPO_LOTE = 'serie' ORDER BY 1";

        $res = $bd->ExecSQL($sql, "No");
        if ($res):
            while ($row = $bd->SigReg($res)):
                $arr[] = $row->NUMERO_SERIE_LOTE;
            endwhile;
        else:
            $arr = false;
        endif;

        return $arr;
    }

    function NumeroSerieEstaEnUbicacion($idMatFisico, $idUbicacion, $idTipoBloqueo)
    {
        global $bd;

        if ($idTipoBloqueo == NULL):
            $sqlwhere = "IS NULL";
        else:
            $sqlwhere = "= ID_TIPO_BLOQUEO";
        endif;

        $where = "ID_MATERIAL_FISICO = $idMatFisico AND ID_UBICACION = $idUbicacion AND STOCK_TOTAL > 0 AND ID_TIPO_BLOQUEO $sqlwhere";

        if ($bd->NumRegsTabla("MATERIAL_UBICACION", $where) > 0):
            return true;
        else:
            return false;
        endif;
    }

    function NumerosSeriePositivo($idMaterial)
    {
        // DEVUELVE UN ARRAY CON LOS NÚMEROS DE SERIE DEL MATERIAL

        global $bd;


        $sql = "SELECT NUMERO_SERIE_LOTE FROM MATERIAL_FISICO WHERE CANTIDAD > 0 AND NOT ISNULL(NUMERO_SERIE_LOTE) AND ID_MATERIAL = $idMaterial AND TIPO_LOTE = 'serie' ORDER BY 1";

        $res = $bd->ExecSQL($sql, "No");
        if ($res):
            while ($row = $bd->SigReg($res)):
                $arr[] = $row->NUMERO_SERIE_LOTE;
            endwhile;
        else:
            $arr = false;
        endif;

        return $arr;
    }

    function NumerosSerieUbicacion($idUbicacion, $idMaterial = "")
    {

        global $bd;

        if ($idMaterial != ""):
            $whereIdMaterial = "AND MU.ID_MATERIAL = $idMaterial";
        endif;

        $sql = "SELECT MF.ID_MATERIAL_FISICO
						FROM MATERIAL_FISICO MF
						INNER JOIN MATERIAL_UBICACION MU ON MU.ID_MATERIAL_FISICO = MF.ID_MATERIAL_FISICO
						WHERE MF.CANTIDAD_DISPONIBLE > 0 AND MU.ID_UBICACION = $idUbicacion $whereIdMaterial AND MFU.STOCK_TOTAL > 0 ORDER BY MF.NUMERO_SERIE_LOTE";

        $res = $bd->ExecSQL($sql, "No");

        if (!$res):
            return false;
        else:
            while ($row = $bd->SigReg($res)):
                $arr[] = $row->ID_MATERIAL_FISICO;
            endwhile;

            return $arr;
        endif;
    }

    function NumerosSerieUbicacionTransferencia($idUbicacion, $idMaterial = "")
    {

        global $bd;

        if ($idMaterial != ""):
            $whereIdMaterial = "AND MU.ID_MATERIAL = $idMaterial";
        endif;

        $sql = "SELECT MF.ID_MATERIAL_FISICO
						FROM MATERIAL_FISICO MF
						INNER JOIN MATERIAL_UBICACION MU ON MU.ID_MATERIAL_FISICO = MF.ID_MATERIAL_FISICO
						WHERE MU.ID_UBICACION = $idUbicacion $whereIdMaterial AND MU.STOCK_TOTAL > 0 ORDER BY MF.NUMERO_SERIE_LOTE";

        $res = $bd->ExecSQL($sql, "No");

        if (!$res):
            return false;
        else:
            while ($row = $bd->SigReg($res)):
                $arr[] = $row->ID_MATERIAL_FISICO;
            endwhile;

            return $arr;
        endif;
    }


    //OBTIENE LOS NUMEROS DE SERIE DE UN MATERIAL EN UNA UBICACION, HABIENDO STOCK DE ESOS NUMEROS DE SERIE
    function NumerosSerieMaterialUbicacion($idMaterial, $idUbicacion)
    {

        global $bd;

        $sql = "SELECT mf.ID_MATERIAL_FISICO
						FROM MATERIAL_FISICO mf
						INNER JOIN MATERIAL_UBICACION mu ON mu.ID_MATERIAL_FISICO = mf.ID_MATERIAL_FISICO
						WHERE mf.CANTIDAD>0 AND mf.ID_MATERIAL = $idMaterial AND mu.ID_UBICACION = $idUbicacion AND mu.STOCK_TOTAL>0
						ORDER BY mf.NUMERO_SERIE_LOTE";

        $res = $bd->ExecSQL($sql, "No");

        if (!$res):
            return false;
        else:
            while ($row = $bd->SigReg($res)):
                $arr[] = $row->ID_MATERIAL_FISICO;
            endwhile;

            return $arr;
        endif;
    }

    function ObtenerIdMaterialFisico($numeroSerie, $idMaterial)
    {
        global $bd;

        $sql = "SELECT ID_MATERIAL_FISICO
						FROM MATERIAL_FISICO
						WHERE ID_MATERIAL = $idMaterial AND NUMERO_SERIE_LOTE = '$numeroSerie' AND TIPO_LOTE = 'serie'";
        $res = $bd->ExecSQL($sql, "No");

        if (!$res):
            return false;
        else:
            $rowMatFisico = $bd->SigReg($res);

            return $rowMatFisico->ID_MATERIAL_FISICO;
        endif;
    }

    function ObtenerIdMaterialFisicoSerie($numeroSerie, $idMaterial)
    {
        global $bd;

        $sql = "SELECT ID_MATERIAL_FISICO FROM MATERIAL_FISICO WHERE NUMERO_SERIE_LOTE = '$numeroSerie' AND ID_MATERIAL = $idMaterial AND TIPO_LOTE = 'serie'";
        $res = $bd->ExecSQL($sql, "No");
        if ($res):
            $row = $bd->SigReg($res);

            return $row->ID_MATERIAL_FISICO;
        else:
            return false;
        endif;
    }

    function NumeroSerieAsociadoMovSalida($idMaterialFisico)
    {
        // DEVUELVE TRUE O FALSE EN FUNCIÓN DE SI ESTÁ O NO ASOCIADO A UN MOVIMIENTO DE SALIDA
        global $bd;

        $sql = "SELECT COUNT(*) AS ASOCIADO_SALIDA
						FROM MOVIMIENTO_SALIDA_LINEA MSL
						INNER JOIN MOVIMIENTO_SALIDA MS ON (MS.ID_MOVIMIENTO_SALIDA = MSL.ID_MOVIMIENTO_SALIDA)
						WHERE MSL.ID_MATERIAL_FISICO = $idMaterialFisico AND MS.ESTADO <> 'Expedido'";

        $res = $bd->ExecSQL($sql);

        $row = $bd->SigReg($res);

        return $row->ASOCIADO_SALIDA;
    }

    function NumeroSerieAsociadoAsiento($idMaterialFisico)
    {
        // DEVUELVE TRUE O FALSE EN FUNCIÓN DE SI ESTÁ O NO ASOCIADO A UN ASIENTO
        global $bd;

        $where = "ID_MATERIAL_FISICO = $idMaterialFisico";

        if ($bd->NumRegsTabla("ASIENTO", $where) > 0) return true;
        else    return false;
    }

    function NumeroSerieAsociadoEntrada($idMaterialFisico)
    {
        // DEVUELVE TRUE O FALSE EN FUNCIÓN DE SI EL NÚMERO DE SERIE EXISTE O NO
        global $bd;

        $where = "ID_MATERIAL_FISICO = $idMaterialFisico";

        if ($bd->NumRegsTabla("MOVIMIENTO_ENTRADA_LINEA", $where) > 0) return true;
        else    return false;
    }

    function ModificarMaterialSeriable($idMaterialFisico, $arrCampos)
    {
        global $bd;

        $coma = "";
        foreach ($arrCampos as $campo => $valor):
            $listaCampos .= "$coma $campo = '$valor'";
            if ($coma == "") $coma = ",";
        endforeach;
        $sqlUpdate = "UPDATE MATERIAL_FISICO SET $listaCampos WHERE ID_MATERIAL_FISICO = $idMaterialFisico";

        $bd->ExecSQL($sqlUpdate);

    }

    function NumeroSerieYaExiste($idMaterialFisico, $numeroSerie)
    {
        // DEVUELVE TRUE O FALSE EN FUNCIÓN DE SI EL NÚMERO DE SERIE EXISTE O NO
        global $bd;

        $rowMatFisico = $bd->VerReg("MATERIAL_FISICO", "ID_MATERIAL_FISICO", $idMaterialFisico);

        $where = "NUMERO_SERIE_LOTE = '$numeroSerie' AND ID_MATERIAL_FISICO != $idMaterialFisico AND ID_MATERIAL = $rowMatFisico->ID_MATERIAL AND TIPO_LOTE = 'serie'"; // EL NÚMERO DE SERIE PODRÁ REPETIRSE PARA OTRO MATERIAL
        if ($bd->NumRegsTabla("MATERIAL_FISICO", $where) > 0) return true;
        else    return false;

    }

    function NumeroSerieYaExisteEnSistema($numeroSerie, $idMaterial)
    {
        // DEVUELVE TRUE O FALSE EN FUNCIÓN DE SI EL NÚMERO DE SERIE EXISTE O NO
        global $bd;

        $where = "NUMERO_SERIE_LOTE = '$numeroSerie' AND ID_MATERIAL = $idMaterial AND CANTIDAD > 0";
        if ($bd->NumRegsTabla("MATERIAL_FISICO", $where) > 0) return true;
        else    return false;

    }

    function BorrarMaterialSeriable($idMaterialFisico)
    {
        global $bd;

        $sqlDelete = "DELETE FROM MATERIAL_FISICO WHERE ID_MATERIAL_FISICO = $idMaterialFisico";

        $bd->ExecSQL($sqlDelete);
    }

    function AsientoConMaterialSeriableAsociadoAPedidos($idAsiento)
    {
        global $bd;

        // SI EL ASIENTO TIENE ASOCIADO NÚMEROS DE SERIE ASOCIADOS A PEDIDOS RETORNAMOS TRUE
        $rowAsiento = $bd->VerReg("ASIENTO", "ID_ASIENTO", $idAsiento);

        $rowMat = $bd->VerReg("MATERIAL", "ID_MATERIAL", $rowAsiento->ID_MATERIAL);

        if ($rowMat->SERIABLE):
            $arrNumeroSerie = $this->NumerosDeSerieAsiento($idAsiento);
            if ($arrNumeroSerie):
                foreach ($arrNumeroSerie as $idMatFisico):
                    if ($this->NumeroSerieAsociadoMovSalida($idMatFisico)):
                        return true;
                    endif;
                endforeach;
            endif;
        endif;

        return false;

    }

    function EntradaConMaterialSeriableAsociadoAPedidos($idEntrada)
    {
        global $bd;

        // SI LA ENTRADA TIENE ASOCIADOS NÚMEROS DE SERIES ASOCIADOS A PEDIDOS RETORNAMOS TRUE
        $arrNumeroSerie = $this->NumerosDeSerieEntrada($idEntrada);
        if ($arrNumeroSerie):
            foreach ($arrNumeroSerie as $idMatFisico):
                if ($this->NumeroSerieAsociadoMovSalida($idMatFisico)):
                    return true;
                endif;
            endforeach;
        endif;


        return false;

    }

    function EntradaConMaterialSeriableAnulado($idEntrada)
    {
        global $bd;

        // SI LA ENTRADA TIENE ASOCIADOS NÚMEROS DE SERIES ASOCIADOS A PEDIDOS RETORNAMOS TRUE
        $arrNumeroSerie = $this->NumerosDeSerieEntrada($idEntrada);
        if ($arrNumeroSerie):
            foreach ($arrNumeroSerie as $idMatFisico):
                $rowMatFisico = $bd->VerReg("MATERIAL_FISICO", "ID_MATERIAL_FISICO", $idMatFisico);
                if ($this->NumeroSerieAnulado($rowMatFisico->ID_MATERIAL_FISICO)):
                    return true;
                endif;
            endforeach;
        endif;

        return false;

    }

    function NumerosDeSerieAsiento($idAsiento)
    {
        global $bd;

        $sql = "SELECT MF.ID_MATERIAL_FISICO
						FROM MATERIAL_FISICO MF
						INNER JOIN ASIENTO A ON A.ID_MATERIAL_FISICO = MF.ID_MATERIAL_FISICO
						WHERE A.ID_ASIENTO = $idAsiento AND NOT ISNULL(MF.NUMERO_SERIE)
						ORDER BY MF.NUMERO_SERIE_LOTE";
        $res = $bd->ExecSQL($sql, "No");
        if ($res):
            while ($row = $bd->SigReg($res)):
                $arr[] = $row->ID_MATERIAL_FISICO;
            endwhile;
        endif;

        return $arr;
    }

    function NumerosDeSerieEntrada($idEntrada, $idLinea = "")
    {
        global $bd;

        if ($idLinea != "") $whereLinea = " AND MEL.ID_MOVIMIENTO_ENTRADA_LINEA = $idLinea ";

        $sql = "SELECT MF.ID_MATERIAL_FISICO
						FROM MATERIAL_FISICO MF
						INNER JOIN MOVIMIENTO_ENTRADA_LINEA MEL ON MEL.ID_MATERIAL_FISICO = MF.ID_MATERIAL_FISICO
						WHERE MEL.ID_MOVIMIENTO_ENTRADA = $idEntrada AND NOT ISNULL(MF.NUMERO_SERIE) $whereLinea
						ORDER BY MF.ID_MATERIAL_FISICO";

        $res = $bd->ExecSQL($sql, "No");
        if ($res):
            while ($row = $bd->SigReg($res)):
                $arr[] = $row->ID_MATERIAL_FISICO;
            endwhile;
        endif;

        return $arr;
    }

    function NumerosDeSerieEntradaEnEM($idEntrada, $idLinea = "")
    {
        global $bd;

        if ($idLinea != "") $whereLinea = " AND MEL.ID_MOVIMIENTO_ENTRADA_LINEA = $idLinea ";

        $sql = "SELECT MF.ID_MATERIAL_FISICO
						FROM MATERIAL_FISICO MF
						INNER JOIN MATERIAL_FISICO_ENTRADA MFE ON MFE.ID_MATERIAL_FISICO = MF.ID_MATERIAL_FISICO
						INNER JOIN MOVIMIENTO_ENTRADA_LINEA MEL ON MEL.ID_MOVIMIENTO_ENTRADA_LINEA = MFE.ID_MOVIMIENTO_ENTRADA_LINEA
						WHERE MEL.ID_MOVIMIENTO_ENTRADA = $idEntrada AND NOT ISNULL(MF.NUMERO_SERIE) AND MEL.ENTRADO = 0 $whereLinea
						ORDER BY MF.ID_MATERIAL_FISICO";

        $res = $bd->ExecSQL($sql, "No");
        if ($res):
            while ($row = $bd->SigReg($res)):
                $arr[] = $row->ID_MATERIAL_FISICO;
            endwhile;
        endif;

        return $arr;
    }

    function NumerosDeSerieEntradaMaterial($idEntrada, $idMaterial)
    {

        global $bd;


        $sql = "SELECT MF.NUMERO_SERIE_LOTE
						FROM MATERIAL_FISICO MF
						INNER JOIN MOVIMIENTO_ENTRADA_LINEA MEL ON MEL.ID_MATERIAL_FISICO = MF.ID_MATERIAL_FISICO
						WHERE MEL.ID_MOVIMIENTO_ENTRADA = $idEntrada  AND MEL.ID_MATERIAL = $idMaterial
						ORDER BY MF.NUMERO_SERIE_LOTE";

        $res = $bd->ExecSQL($sql, "No");
        if ($res):
            while ($row = $bd->SigReg($res)):
                $arr[] = $row->NUMERO_SERIE_LOTE;
            endwhile;
        endif;

        return $arr;
    }

    function NumerosDeSerieSalidaMaterial($idSalida, $idMaterial, $idLineaSalida = '', $orderby = '')
    {

        global $bd;

        if ($idLineaSalida != ''):
            $whereLinea = "AND MSL.ID_MOVIMIENTO_SALIDA_LINEA = $idLineaSalida";
        endif;

        if ($orderby == "") $order = "MF.NUMERO_SERIE";
        else    $order = $orderby;


        $sql = "SELECT MF.NUMERO_SERIE_LOTE
						FROM MATERIAL_FISICO MF
						INNER JOIN MOVIMIENTO_SALIDA_LINEA MSL ON MSL.ID_MATERIAL_FISICO = MF.ID_MATERIAL_FISICO
						WHERE MSL.ID_MOVIMIENTO_SALIDA = $idSalida  AND MSL.ID_MATERIAL = $idMaterial $whereLinea
						ORDER BY $order";
        $res = $bd->ExecSQL($sql, "No");
        if ($res):
            while ($row = $bd->SigReg($res)):
                $arr[] = $row->NUMERO_SERIE_LOTE;
            endwhile;
        else:
            return false;
        endif;

        return $arr;
    }

    function EsMaterialSeriable($idMaterial)
    {
        global $bd;

        $rowMat = $bd->VerReg("MATERIAL", "ID_MATERIAL", $idMaterial);

        if ($rowMat->TIPO_LOTE == 'serie'):
            return true;
        else:
            return false;
        endif;
    }

    function CuantosNumerosSerieEntradaMaterial($idEntrada, $idMaterial)
    {
        global $bd;

        $sql = "SELECT count(*) as NUM FROM MATERIAL_FISICO MF " .
            "INNER JOIN MATERIAL_FISICO_ENTRADA MFE ON MFE.ID_MATERIAL_FISICO = MF.ID_MATERIAL_FISICO " .
            "INNER JOIN MOVIMIENTO_ENTRADA_LINEA MEL ON MEL.ID_MOVIMIENTO_ENTRADA_LINEA = MFE.ID_MOVIMIENTO_ENTRADA_LINEA " .
            "WHERE MEL.ID_MOVIMIENTO_ENTRADA = $idEntrada  AND MEL.ID_MATERIAL = $idMaterial ";

        $resultado = $bd->ExecSQL($sql);

        $row = $bd->SigReg($resultado);

        return $row->NUM;

    }

    function MovimientoSalidaAsociadoNumeroSerie($idMaterialFisico)
    {
        global $bd;

        $sql = "SELECT MSL.ID_MOVIMIENTO_SALIDA FROM MOVIMIENTO_SALIDA_LINEA MSL " .
            "INNER JOIN MATERIAL_FISICO_SALIDA MFS ON MFS.ID_MOVIMIENTO_SALIDA_LINEA = MSL.ID_MOVIMIENTO_SALIDA_LINEA " .
            "WHERE MFS.ID_MATERIAL_FISICO = $idMaterialFisico";

        $res = $bd->ExecSQL($sql, "No");
        if ($res):
            $row = $bd->SigReg($res);

            return $row->ID_MOVIMIENTO_SALIDA;
        else:
            return false;
        endif;
    }

    function EliminarNumerosDeSerieEntrada($idEntrada, $idLinea = "")
    {
        // SI $idLinea != "" BORRAREMOS SOLO LOS NÚMEROS DE SERIE DE ESA LINEA

        global $bd;

        if ($idLinea != ""):
            $whereLinea = " AND MEL.ID_MOVIMIENTO_ENTRADA_LINEA = $idLinea";
        endif;

        $arrNumeroSerie = $this->NumerosDeSerieEntrada($idEntrada);

        if (count( (array)$arrNumeroSerie) > 0):
            foreach ($arrNumeroSerie as $idMatFisico):

                $idEntradaLinea = "";

                // SELECCIONAMOS EL ID DE LA LINEA DE ENTRADA PARA BORRAR EN TABLA MATERIAL_FISICO_ENTRADA
                $sql = "SELECT MFE.ID_MOVIMIENTO_ENTRADA_LINEA
							FROM MATERIAL_FISICO_ENTRADA MFE
							INNER JOIN MOVIMIENTO_ENTRADA_LINEA MEL ON MEL.ID_MOVIMIENTO_ENTRADA_LINEA = MFE.ID_MOVIMIENTO_ENTRADA_LINEA
							WHERE MEL.ID_MOVIMIENTO_ENTRADA = $idEntrada AND MFE.ID_MATERIAL_FISICO = $idMatFisico $whereLinea";

                $res            = $bd->ExecSQL($sql, "No");
                $row            = $bd->SigReg($res);
                $idEntradaLinea = $row->ID_MOVIMIENTO_ENTRADA_LINEA;

                if ($idEntradaLinea != ""):

                    // BORRAMOS DE LA TABLA MATERIAL_FISICO_ENTRADA
                    $sqlDelete = "DELETE FROM MATERIAL_FISICO_ENTRADA WHERE ID_MATERIAL_FISICO = $idMatFisico AND ID_MOVIMIENTO_ENTRADA_LINEA = $idEntradaLinea";
                    $bd->ExecSQL($sqlDelete);
                endif;
            endforeach;
        endif;
    }

    function InsertarNumerosDeSerieEntrada($idMaterial, $arrNumerosSerie, $idLineaMov, $idUbicacion)
    {
        global $bd;


        foreach ($arrNumerosSerie as $numeroSerie):

            $idMatFisico = $this->ObtenerIdMaterialFisico($numeroSerie, $idMaterial);
            if (!$idMatFisico):
                // 1. INSERTAMOS LOS NÚMEROS DE SERIE NUEVOS EN LA TABLA MATERIAL_FISICO
                $sqlInsert = "INSERT INTO MATERIAL_FISICO SET " .
                    "ID_MATERIAL = $idMaterial, " .
                    "NUMERO_SERIE = '$numeroSerie'";
                $bd->ExecSQL($sqlInsert);

                $idMatFisico = $bd->IdAsignado();
            endif;

            // 2. INSERTAMOS EN TABLA MATERIAL_FISICO_ENTRADA
            $sqlInsert = "INSERT INTO MATERIAL_FISICO_ENTRADA SET " .
                "ID_MATERIAL_FISICO = $idMatFisico, " .
                "ID_MOVIMIENTO_ENTRADA_LINEA = $idLineaMov, " .
                "CANTIDAD = 1";

            $bd->ExecSQL($sqlInsert);

        endforeach;
    }

    function ExisteNumeroSerie($idMatFisico)
    {
        global $bd;
        $where = "ID_MATERIAL_FISICO = $idMatFisico";
        if ($bd->NumRegsTabla("MATERIAL_FISICO", $where) > 0) return true;
        else    return false;
    }

    function NumeroSerieDisponible($idMaterialFisico)
    {
        // SI EL NÚMERO DE SERIE ESTÁ EN ALMACÉN Y SIN ASOCIAR A UN MOVIMIENTO DE SALIDA
        global $bd;

        $rowMatFisico = $bd->VerReg("MATERIAL_FISICO", "ID_MATERIAL_FISICO", $idMaterialFisico);

        if ($rowMatFisico->CANTIDAD_DISPONIBLE == 0):
            return false;
        else:
            if ($this->NumeroSerieAsociadoMovSalida($idMaterialFisico)):
                return false;
            else:
                return true;
            endif;
        endif;
    }

    function NumeroSerieAnulado($idMaterialFisico)
    {
        // SI EL NÚMERO DE SERIE ESTÁ ANULADO
        global $bd;

        $sql = "SELECT * FROM MATERIAL_FISICO WHERE ID_MATERIAL_FISICO = $idMaterialFisico";

        $res = $bd->ExecSQL($sql, "No");

        if (!res):
            return true;
        else:
            $rowMatFisico = $bd->SigReg($res);

            if ($rowMatFisico->CANTIDAD == 0):
                return true;
            else:
                return false;
            endif;
        endif;
    }

    function NumeroSerieIdUbicacion($idMaterialFisico)
    {
        global $bd;

        $sql = "SELECT ID_UBICACION FROM MATERIAL_FISICO_UBICACION " .
            "WHERE ID_MATERIAL_FISICO = $idMaterialFisico AND STOCK_TOTAL > 0";
        $res = $bd->ExecSQL($sql);
        if (!$res):
            return false;
        else:
            $row = $bd->SigReg($res);

            return $row->ID_UBICACION;
        endif;

    }

    function ExisteNumeroSerieUbicacion($idUbicacion, $idMaterialFisico)
    {
        global $bd;

        $where = "ID_UBICACION = $idUbicacion AND ID_MATERIAL_FISICO = $idMaterialFisico";

        if ($bd->NumRegsTabla("MATERIAL_FISICO_UBICACION", $where) > 0) return true;
        else    return false;


    }

    function ExisteNumeroSerieUbicacionPositivo($idUbicacion, $idMaterialFisico)
    {
        global $bd;

        $where = "ID_UBICACION = $idUbicacion AND ID_MATERIAL_FISICO = $idMaterialFisico AND STOCK_TOTAL > 0";

        if ($bd->NumRegsTabla("MATERIAL_FISICO_UBICACION", $where) > 0) return true;
        else    return false;


    }

    function NumerosDeSerieTransferencia($idTransferencia)
    {
        global $bd;

        $sql = "SELECT MF.ID_MATERIAL_FISICO
						FROM MATERIAL_FISICO MF
						INNER JOIN MATERIAL_FISICO_TRANSFERENCIA MFT ON MFT.ID_MATERIAL_FISICO = MF.ID_MATERIAL_FISICO
						WHERE MFT.ID_MOVIMIENTO_TRANSFERENCIA = $idTransferencia  AND NOT ISNULL(MF.NUMERO_SERIE_LOTE)
						ORDER BY MF.ID_MATERIAL_FISICO";

        $res = $bd->ExecSQL($sql, "No");
        if ($res):
            while ($row = $bd->SigReg($res)):
                $arr[] = $row->ID_MATERIAL_FISICO;
            endwhile;
        endif;

        return $arr;
    }

    // FIN FUNCIONES RELACIONADAS CON EL MATERIAL SERIABLE ------------------------------------------------------

    // FUNCIONES RELACIONADAS CON EL MATERIAL LOTABLE -----------------------------------------------------------

    function NumeroLoteYaExiste($idMaterialFisico, $numeroLote)
    {
        // DEVUELVE TRUE O FALSE EN FUNCIÓN DE SI EL NÚMERO DE SERIE EXISTE O NO
        global $bd;

        $rowMatFisico = $bd->VerReg("MATERIAL_FISICO", "ID_MATERIAL_FISICO", $idMaterialFisico);

        $where = "NUMERO_LOTE = '$numeroLote' AND ID_MATERIAL_FISICO != $idMaterialFisico AND ID_MATERIAL = $rowMatFisico->ID_MATERIAL"; // PODRÁ REPETIRSE EL NÚMERO DE LOTE PARA OTRO MATERIAL
        if ($bd->NumRegsTabla("MATERIAL_FISICO", $where) > 0) return true;
        else    return false;

    }

    function NumeroLoteCorrespondeMaterial($idMaterial, $numeroLote)
    {
        // DEVUELVE TRUE O FALSE EN FUNCIÓN DE SI EL NÚMERO DE SERIE EXISTE O NO
        global $bd;

        $where = "NUMERO_LOTE = '$numeroLote' AND ID_MATERIAL = $idMaterial";
        if ($bd->NumRegsTabla("MATERIAL_FISICO", $where) > 0) return true;
        else    return false;

    }

    function NumeroLoteYaExisteEnSistema($numeroLote, $idMaterial)
    {
        // DEVUELVE TRUE O FALSE EN FUNCIÓN DE SI EL NÚMERO DE SERIE EXISTE O NO
        global $bd;

        $where = "ID_MATERIAL = $idMaterial AND NUMERO_LOTE = '$numeroLote'";
        if ($bd->NumRegsTabla("MATERIAL_FISICO", $where) > 0) return true;
        else    return false;

    }

    function EsMaterialLotable($idMaterial)
    {
        global $bd;

        $rowMat = $bd->VerReg("MATERIAL", "ID_MATERIAL", $idMaterial);

        if ($rowMat->TIPO_LOTE == 'lote'):
            return true;
        else:
            return false;
        endif;
    }

    function ObtenerNumeroLote($idMaterialFisico)
    {
        global $bd;

        $sql = "SELECT NUMERO_SERIE_LOTE FROM MATERIAL_FISICO WHERE ID_MATERIAL_FISICO = $idMaterialFisico";
        $res = $bd->ExecSQL($sql, "No");
        if ($res):
            $row = $bd->SigReg($res);

            return $row->NUMERO_SERIE_LOTE;
        else:
            return false;
        endif;
    }

    function ObtenerIdMaterialFisicoLote($numeroLote, $idMaterial)
    {
        global $bd;

        $sql = "SELECT ID_MATERIAL_FISICO FROM MATERIAL_FISICO WHERE NUMERO_SERIE_LOTE = '$numeroLote' AND ID_MATERIAL = $idMaterial AND TIPO_LOTE = 'lote'";
        $res = $bd->ExecSQL($sql, "No");
        if ($res):
            $row = $bd->SigReg($res);

            return $row->ID_MATERIAL_FISICO;
        else:
            return false;
        endif;
    }

    function ObtenerIdMaterialLote($idMaterialFisico)
    {
        global $bd;

        $sql = "SELECT ID_MATERIAL FROM MATERIAL_FISICO WHERE ID_MATERIAL_FISICO = $idMaterialFisico";
        $res = $bd->ExecSQL($sql, "No");
        if ($res):
            $row = $bd->SigReg($res);

            return $row->ID_MATERIAL;
        else:
            return false;
        endif;
    }

    function ObtenerCantidadLote($idMaterialFisico)
    {
        global $bd;

        $sql = "SELECT CANTIDAD FROM MATERIAL_FISICO WHERE ID_MATERIAL_FISICO = $idMaterialFisico";
        $res = $bd->ExecSQL($sql, "No");
        if ($res):
            $row = $bd->SigReg($res);

            return $row->CANTIDAD;
        else:
            return false;
        endif;

    }

    function ObtenerCantidadDisponibleLote($idMaterialFisico)
    {
        global $bd;

        $sql = "SELECT CANTIDAD_DISPONIBLE FROM MATERIAL_FISICO WHERE ID_MATERIAL_FISICO = $idMaterialFisico";
        $res = $bd->ExecSQL($sql, "No");
        if ($res):
            $row = $bd->SigReg($res);

            return $row->CANTIDAD_DISPONIBLE;
        else:
            return false;
        endif;
    }

    function ObtenerStockLoteUbicacion($idMaterialFisico, $idUbicacion)
    {
        global $bd;

        $sql = "SELECT STOCK_TOTAL FROM MATERIAL_FISICO_UBICACION WHERE ID_MATERIAL_FISICO = $idMaterialFisico AND ID_UBICACION = $idUbicacion";
        $res = $bd->ExecSQL($sql, "No");
        if ($res):
            $row = $bd->SigReg($res);

            return $row->STOCK_TOTAL;
        else:
            return false;
        endif;
    }

    function ObtenerStockLoteAlmacen($idMaterialFisico, $idAlmacen)
    {
        global $bd;

        $sql = "SELECT SUM(STOCK_TOTAL) AS TOTAL " .
            "FROM MATERIAL_FISICO_UBICACION MFU " .
            "INNER JOIN UBICACION U ON(U.ID_UBICACION = MFU.ID_UBICACION) " .
            "WHERE MFU.ID_MATERIAL_FISICO = $idMaterialFisico " .
            "AND U.ID_ALMACEN = $idAlmacen";
        $res = $bd->ExecSQL($sql, "No");
        if ($res):
            $row = $bd->SigReg($res);

            return $row->TOTAL;
        else:
            return false;
        endif;
    }

    function ObtenerStockMaterialFisicoUbicacion($idMaterialFisico, $idUbicacion)
    {
        global $bd;

        $sql = "SELECT STOCK_TOTAL FROM MATERIAL_FISICO_UBICACION " .
            "WHERE ID_MATERIAL_FISICO = $idMaterialFisico AND ID_UBICACION = $idUbicacion";
        $res = $bd->ExecSQL($sql, "No");
        if ($res):
            $row = $bd->SigReg($res);

            return $row->STOCK_TOTAL;
        else:
            return false;
        endif;
    }

    function ObtenerRefMaterialLote($idMaterialFisico)
    {
        global $bd;

        $idMaterial = $this->ObtenerIdMaterialLote($idMaterialFisico);

        if ($idMaterial):
            return $this->ObtenerReferencia($idMaterial);
        else:
            return false;
        endif;
    }

    function NumeroLotesLineaEntrada($idLineaEntrada)
    {
        global $bd;

        $sql = "SELECT mel.ID_MATERIAL_FISICO
						FROM MOVIMIENTO_ENTRADA_LINEA mel
						INNER JOIN MATERIAL_FISICO mf ON (mel.ID_MATERIAL_FISICO=mf.ID_MATERIAL_FISICO)
						WHERE mel.ID_MOVIMIENTO_ENTRADA_LINEA = $idLineaEntrada
						ORDER BY mf.NUMERO_SERIE_LOTE";
        $res = $bd->ExecSQL($sql, "No");

        if ($res):
            while ($row = $bd->SigReg($res)):
                $arr[] = $this->ObtenerNumeroLote($row->ID_MATERIAL_FISICO);
            endwhile;

            return $arr;
        else:
            return false;
        endif;
    }

    function NumeroLotesLineaSalida($idLineaSalida, $orderby = '')
    {
        global $bd;

        if ($orderby == "") $order = "mf.NUMERO_LOTE";
        else    $order = $orderby;

        $sql = "SELECT mfs.ID_MATERIAL_FISICO FROM MATERIAL_FISICO_SALIDA mfs";
        $sql = "$sql INNER JOIN MATERIAL_FISICO mf ON (mfs.ID_MATERIAL_FISICO=mf.ID_MATERIAL_FISICO)";
        $sql = "$sql WHERE mfs.ID_MOVIMIENTO_SALIDA_LINEA = $idLineaSalida";
        $sql = "$sql ORDER BY $order";

        $res = $bd->ExecSQL($sql, "No");

        if ($res):
            while ($row = $bd->SigReg($res)):
                $arr[] = $this->ObtenerNumeroLote($row->ID_MATERIAL_FISICO);
            endwhile;

            return $arr;
        else:
            return false;
        endif;
    }

    function InsertarLotableEntrada($idMaterial, $numeroLoteNuevo, $cantidadLoteNuevo, $idLineaMov, $idUbicacion)
    {
        global $bd;

        $numeroLote = $numeroLoteNuevo;
        $cantidad   = $cantidadLoteNuevo;

        if ($this->NumeroLoteYaExisteEnSistema($numeroLote, $idMaterial)):
            $idMatFisico = $this->ObtenerIdMaterialFisicoLote($numeroLote, $idMaterial);
        else:

            // SI NO EXISTE INSERTAMOS EL NUMERO DE LOTE EN LA TABLA MATERIAL_FISICO
            $sqlInsert = "INSERT INTO MATERIAL_FISICO SET " .
                "ID_MATERIAL = $idMaterial, " .
                "NUMERO_LOTE = '$numeroLote', " .
                "TIPO = 'Lote'";

            $bd->ExecSQL($sqlInsert);

            $idMatFisico = $bd->IdAsignado();
        endif;

        // 1. GRABAMOS EN TABLA MATERIAL_FISICO_ENTRADA
        if ($this->ExisteNumeroLoteEnLineaEntrada($idLineaMov, $numeroLote)):

            // 1. ACTUALIZAMOS
            $sqlUpdate = "UPDATE MATERIAL_FISICO_ENTRADA SET " .
                "CANTIDAD = $cantidad " .
                "WHERE ID_MATERIAL_FISICO = $idMatFisico AND " .
                "ID_MOVIMIENTO_ENTRADA_LINEA = $idLineaMov";
            $bd->ExecSQL($sqlUpdate);
        else:
            // 1. INSERTAMOS
            $sqlInsert = "INSERT INTO MATERIAL_FISICO_ENTRADA SET " .
                "ID_MATERIAL_FISICO = $idMatFisico, " .
                "ID_MOVIMIENTO_ENTRADA_LINEA = $idLineaMov, " .
                "CANTIDAD = $cantidad";

            $bd->ExecSQL($sqlInsert);
        endif;

    }

    function InsertarLotableEntradaMasiva($idMaterial, $arrLotes, $idLineaMov, $idUbicacion)
    {
        global $bd;

        foreach ($arrLotes as $numeroLote => $cantidad):

            if ($this->NumeroLoteYaExisteEnSistema($numeroLote, $idMaterial)):
                $idMatFisico = $this->ObtenerIdMaterialFisicoLote($numeroLote, $idMaterial);
            else:

                // SI NO EXISTE INSERTAMOS EL NUMERO DE LOTE EN LA TABLA MATERIAL_FISICO
                $sqlInsert = "INSERT INTO MATERIAL_FISICO SET " .
                    "ID_MATERIAL = $idMaterial, " .
                    "NUMERO_LOTE = '$numeroLote', " .
                    "TIPO = 'Lote'";

                $bd->ExecSQL($sqlInsert);

                $idMatFisico = $bd->IdAsignado();
            endif;

            // 1. GRABAMOS EN TABLA MATERIAL_FISICO_ENTRADA
            if ($this->ExisteNumeroLoteEnLineaEntrada($idLineaMov, $numeroLote)):

                // 1. ACTUALIZAMOS
                $sqlUpdate = "UPDATE MATERIAL_FISICO_ENTRADA SET " .
                    "CANTIDAD = CANTIDAD + $cantidad " .
                    "WHERE ID_MATERIAL_FISICO = $idMatFisico AND " .
                    "ID_MOVIMIENTO_ENTRADA_LINEA = $idLineaMov";
                $bd->ExecSQL($sqlUpdate);
            else:
                // 1. INSERTAMOS
                $sqlInsert = "INSERT INTO MATERIAL_FISICO_ENTRADA SET " .
                    "ID_MATERIAL_FISICO = $idMatFisico, " .
                    "ID_MOVIMIENTO_ENTRADA_LINEA = $idLineaMov, " .
                    "CANTIDAD = $cantidad";

                $bd->ExecSQL($sqlInsert);
            endif;
        endforeach;

    }

    function ObtenerCantidadLineaEntradaLote($idLinea, $idMatFisico)
    {
        global $bd;

        $sql = "SELECT CANTIDAD FROM MATERIAL_FISICO_ENTRADA " .
            "WHERE ID_MATERIAL_FISICO = $idMatFisico AND ID_MOVIMIENTO_ENTRADA_LINEA = $idLinea";

        $res = $bd->ExecSQL($sql, "No");

        if ($res):
            $row = $bd->SigReg($res);

            return $row->CANTIDAD;
        else:
            return false;
        endif;
    }

    function ObtenerCantidadLineaSalidaLote($idLinea, $idMatFisico)
    {
        global $bd;

        $sql = "SELECT CANTIDAD FROM MATERIAL_FISICO_SALIDA " .
            "WHERE ID_MATERIAL_FISICO = $idMatFisico AND ID_MOVIMIENTO_SALIDA_LINEA = $idLinea";

        $res = $bd->ExecSQL($sql, "No");

        if ($res):
            $row = $bd->SigReg($res);

            return $row->CANTIDAD;
        else:
            return false;
        endif;
    }

    function ObtenerCantidadSalidaLote($idSalida, $idMatFisico)
    {
        global $bd;

        $sqlLineas    = "SELECT * FROM MOVIMIENTO_SALIDA_LINEA WHERE ID_MOVIMIENTO_SALIDA = $idSalida";
        $resultLineas = $bd->ExecSQL($sqlLineas);
        $uds          = 0;
        while ($rowLinea = $bd->SigReg($resultLineas)):
            $sql = "SELECT CANTIDAD FROM MATERIAL_FISICO_SALIDA " .
                "WHERE ID_MATERIAL_FISICO = $idMatFisico AND ID_MOVIMIENTO_SALIDA_LINEA = $rowLinea->ID_MOVIMIENTO_SALIDA_LINEA";
            $res = $bd->ExecSQL($sql, "No");

            if ($res):
                $row = $bd->SigReg($res);
                $uds = $uds + $row->CANTIDAD;
            else:
                return false;
            endif;
        endwhile;

        return $uds;
    }

    function ExisteNumeroLoteEnLineaEntrada($idLinea, $numeroLote)
    {
        global $bd;

        $rowLineaEntrada = $bd->VerReg("MOVIMIENTO_ENTRADA_LINEA", "ID_MOVIMIENTO_ENTRADA_LINEA", $idLinea);

        $idMatFisico = $this->ObtenerIdMaterialFisicoLote($numeroLote, $rowLineaEntrada->ID_MATERIAL);

        if ($idMatFisico):
            $where = "ID_MOVIMIENTO_ENTRADA_LINEA = $idLinea AND ID_MATERIAL_FISICO = $idMatFisico";

            if ($bd->NumRegsTabla("MATERIAL_FISICO_ENTRADA", $where) > 0) return true;
            else    return false;
        else:
            return false;
        endif;
    }

    function ObtenerCantidadUbicacionLote($idUbicacion, $idMaterialFisico)
    {
        global $bd;

        $sql = "SELECT STOCK_TOTAL FROM MATERIAL_FISICO_UBICACION " .
            "WHERE ID_MATERIAL_FISICO = $idMaterialFisico AND ID_UBICACION = $idUbicacion";
        $res = $bd->ExecSQL($sql, "No");

        if ($res):
            $row = $bd->SigReg($res);

            return $row->STOCK_TOTAL;
        else:
            return false;
        endif;
    }

    function ExisteNumeroLoteUbicacion($idUbicacion, $idMatFisico)
    {
        global $bd;

        if ($idMatFisico):
            $where = "ID_UBICACION = $idUbicacion AND ID_MATERIAL_FISICO = $idMatFisico";

            if ($bd->NumRegsTabla("MATERIAL_FISICO_UBICACION", $where) > 0) return true;
            else    return false;
        else:
            return false;
        endif;
    }

    function ObtenerCantidadTotalLineaEntradaLotes($idLineaEntrada)
    {
        global $bd;

        $sql = "SELECT SUM(CANTIDAD) AS TOTAL FROM MATERIAL_FISICO_ENTRADA " .
            "WHERE ID_MOVIMIENTO_ENTRADA_LINEA = $idLineaEntrada";

        $res = $bd->ExecSQL($sql, "No");

        if ($res):
            $row = $bd->SigReg($res);

            return $row->TOTAL;
        else:
            return false;
        endif;

    }

    function TieneMaterialLotableEntrada($idEntrada)
    {
        global $bd;

        // SELECCIONAMOS LAS LÍNEAS DE ENTRADA
        $sql = "SELECT * FROM MOVIMIENTO_ENTRADA_LINEA " .
            "WHERE ID_MOVIMIENTO_ENTRADA='$idEntrada' ORDER BY ID_MOVIMIENTO_ENTRADA_LINEA";
        $res = $bd->ExecSQL($sql);
        while ($row = $bd->SigReg($res)):
            $rowMat = $bd->VerReg("MATERIAL", "ID_MATERIAL", $row->ID_MATERIAL);
            if ($rowMat->LOTABLE) return true;
        endwhile;

        return false;
    }

    function FinalizarEntradaLotable($idEntrada)
    {
        global $bd;

        // RECORREMOS LAS LINEAS DE ENTRADA
        $sql = "SELECT *
						FROM MOVIMIENTO_ENTRADA_LINEA
						WHERE ID_MOVIMIENTO_ENTRADA='$idEntrada'
						ORDER BY ID_MOVIMIENTO_ENTRADA_LINEA";
        $res = $bd->ExecSQL($sql);
        while ($row = $bd->SigReg($res)):
            if ($this->EsMaterialLotable($row->ID_MATERIAL)):
                $sqlLineasLote = "SELECT * FROM MATERIAL_FISICO_ENTRADA " .
                    "WHERE ID_MOVIMIENTO_ENTRADA_LINEA = $row->ID_MOVIMIENTO_ENTRADA_LINEA " .
                    "ORDER BY ID_MOVIMIENTO_ENTRADA_LINEA";
                $resLineasLote = $bd->ExecSQL($sqlLineasLote);
                while ($rowLineasLote = $bd->SigReg($resLineasLote)):
                    $idMatFisico = $rowLineasLote->ID_MATERIAL_FISICO;
                    $cantidad    = $rowLineasLote->CANTIDAD;

                    // 1. INCREMENTAREMOS EN MATERIAL_FISICO CANTIDAD, CANTIDAD_DISPONIBLE
                    $sqlUpdate = "UPDATE MATERIAL_FISICO SET " .
                        "CANTIDAD = CANTIDAD + $cantidad, " .
                        "CANTIDAD_DISPONIBLE = CANTIDAD_DISPONIBLE + $cantidad " .
                        "WHERE ID_MATERIAL_FISICO = $idMatFisico";

                    $resUpdate = $bd->ExecSQL($sqlUpdate);

                    // 2. INCREMENTAREMOS EN MATERIAL_FISICO_UBICACION
                    if ($this->ExisteNumeroLoteUbicacion($row->ID_UBICACION, $idMatFisico)):
                        // ACTUALIZAMOS
                        $sqlUpdate = "UPDATE MATERIAL_FISICO_UBICACION SET " .
                            "STOCK_TOTAL = STOCK_TOTAL + $cantidad " .
                            "WHERE ID_UBICACION = $row->ID_UBICACION AND ID_MATERIAL_FISICO = $idMatFisico";

                        $bd->ExecSQL($sqlUpdate);
                    else:
                        // INSERTAMOS
                        $sqlInsert = "INSERT INTO MATERIAL_FISICO_UBICACION SET " .
                            "ID_MATERIAL_FISICO = $idMatFisico, " .
                            "ID_UBICACION = $row->ID_UBICACION, " .
                            "STOCK_TOTAL = $cantidad";

                        $bd->ExecSQL($sqlInsert);
                    endif;

                endwhile;
            endif;
        endwhile;
    }

    function FinalizarEntradaLotableEM($idEntrada)
    {
        global $bd;

        $whereLinea  = "";
        $sqlUbiEM    = "SELECT ID_UBICACION FROM UBICACION WHERE TIPO_UBICACION = 'Entrada'";
        $resultUbiEM = $bd->ExecSQL($sqlUbiEM);
        while ($rowUbiEM = $bd->SigReg($resultUbiEM)):
            $whereLinea .= "AND ID_UBICACION = $rowUbiEM->ID_UBICACION";
        endwhile;

        // RECORREMOS LAS LINEAS DE ENTRADA
        $sql = "SELECT *
						FROM MOVIMIENTO_ENTRADA_LINEA
						WHERE ID_MOVIMIENTO_ENTRADA='$idEntrada' AND ENTRADO = 0 $whereLinea
						ORDER BY ID_MOVIMIENTO_ENTRADA_LINEA";
        $res = $bd->ExecSQL($sql);
        while ($row = $bd->SigReg($res)):
            if ($this->EsMaterialLotable($row->ID_MATERIAL)):
                $sqlLineasLote = "SELECT * FROM MATERIAL_FISICO_ENTRADA " .
                    "WHERE ID_MOVIMIENTO_ENTRADA_LINEA = $row->ID_MOVIMIENTO_ENTRADA_LINEA " .
                    "ORDER BY ID_MOVIMIENTO_ENTRADA_LINEA";
                $resLineasLote = $bd->ExecSQL($sqlLineasLote);
                while ($rowLineasLote = $bd->SigReg($resLineasLote)):
                    $idMatFisico = $rowLineasLote->ID_MATERIAL_FISICO;
                    $cantidad    = $rowLineasLote->CANTIDAD;

                    // 1. INCREMENTAREMOS EN MATERIAL_FISICO CANTIDAD, CANTIDAD_DISPONIBLE
                    $sqlUpdate = "UPDATE MATERIAL_FISICO SET " .
                        "CANTIDAD = CANTIDAD + $cantidad, " .
                        "CANTIDAD_DISPONIBLE = CANTIDAD_DISPONIBLE + $cantidad " .
                        "WHERE ID_MATERIAL_FISICO = $idMatFisico";

                    $resUpdate = $bd->ExecSQL($sqlUpdate);

                    // 2. INCREMENTAREMOS EN MATERIAL_FISICO_UBICACION
                    if ($this->ExisteNumeroLoteUbicacion($row->ID_UBICACION, $idMatFisico)):
                        // ACTUALIZAMOS
                        $sqlUpdate = "UPDATE MATERIAL_FISICO_UBICACION SET " .
                            "STOCK_TOTAL = STOCK_TOTAL + $cantidad " .
                            "WHERE ID_UBICACION = $row->ID_UBICACION AND ID_MATERIAL_FISICO = $idMatFisico";

                        $bd->ExecSQL($sqlUpdate);
                    else:
                        // INSERTAMOS
                        $sqlInsert = "INSERT INTO MATERIAL_FISICO_UBICACION SET " .
                            "ID_MATERIAL_FISICO = $idMatFisico, " .
                            "ID_UBICACION = $row->ID_UBICACION, " .
                            "STOCK_TOTAL = $cantidad";

                        $bd->ExecSQL($sqlInsert);
                    endif;

                endwhile;
            endif;
        endwhile;
    }

    function CantidadLotableEntradaSuperaCantidadStockDisponible($idEntrada)
    {
        global $bd;

        // RECORREMOS LAS LINEAS DE ENTRADA
        $sql = "SELECT * FROM MOVIMIENTO_ENTRADA_LINEA " .
            "WHERE ID_MOVIMIENTO_ENTRADA='$idEntrada' ORDER BY ID_MOVIMIENTO_ENTRADA_LINEA";

        $res = $bd->ExecSQL($sql);

        $hayError = false;
        while ($row = $bd->SigReg($res)):
            if ($this->EsMaterialLotable($row->ID_MATERIAL)):
                $sqlLineasLote = "SELECT * FROM MATERIAL_FISICO_ENTRADA " .
                    "WHERE ID_MOVIMIENTO_ENTRADA_LINEA = $row->ID_MOVIMIENTO_ENTRADA_LINEA " .
                    "ORDER BY ID_MOVIMIENTO_ENTRADA_LINEA";
                $resLineasLote = $bd->ExecSQL($sqlLineasLote);
                while ($rowLineasLote = $bd->SigReg($resLineasLote)):
                    $numeroLote         = $this->ObtenerNumeroLote($rowLineasLote->ID_MATERIAL_FISICO);
                    $cantidadDisponible = $this->ObtenerCantidadDisponibleLote($rowLineasLote->ID_MATERIAL_FISICO);
                    if ($rowLineasLote->CANTIDAD > $cantidadDisponible):
                        $hayError = true;
                    endif;
                endwhile;
            endif;
        endwhile;

        return $hayError;
    }

    function ModificarEntradaFinalizadaLotable($idEntrada)
    {
        global $bd;

        // RECORREMOS LAS LINEAS DE ENTRADA
        $sql = "SELECT * FROM MOVIMIENTO_ENTRADA_LINEA " .
            "WHERE ID_MOVIMIENTO_ENTRADA='$idEntrada' ORDER BY ID_MOVIMIENTO_ENTRADA_LINEA";

        $res = $bd->ExecSQL($sql);

        $hayError = false;
        while ($row = $bd->SigReg($res)):
            if ($this->EsMaterialLotable($row->ID_MATERIAL)):
                $sqlLineasLote = "SELECT * FROM MATERIAL_FISICO_ENTRADA " .
                    "WHERE ID_MOVIMIENTO_ENTRADA_LINEA = $row->ID_MOVIMIENTO_ENTRADA_LINEA " .
                    "ORDER BY ID_MOVIMIENTO_ENTRADA_LINEA";
                $resLineasLote = $bd->ExecSQL($sqlLineasLote);
                while ($rowLineasLote = $bd->SigReg($resLineasLote)):
                    $idMatFisico = $rowLineasLote->ID_MATERIAL_FISICO;
                    $cantidad    = $rowLineasLote->CANTIDAD;

                    // 1. DECREMENTAREMOS EN MATERIAL_FISICO CANTIDAD, CANTIDAD_DISPONIBLE
                    $sqlUpdate = "UPDATE MATERIAL_FISICO SET " .
                        "CANTIDAD = CANTIDAD - $cantidad, " .
                        "CANTIDAD_DISPONIBLE = CANTIDAD_DISPONIBLE - $cantidad " .
                        "WHERE ID_MATERIAL_FISICO = $idMatFisico";

                    $resUpdate = $bd->ExecSQL($sqlUpdate);

                    // 1. DECREMENTAREMOS EN MATERIAL_FISICO_UBICACION STOCK_TOTAL
                    $sqlUpdate = "UPDATE MATERIAL_FISICO_UBICACION SET " .
                        "STOCK_TOTAL = STOCK_TOTAL - $cantidad " .
                        "WHERE ID_MATERIAL_FISICO = $idMatFisico AND ID_UBICACION = $row->ID_UBICACION";

                    $resUpdate = $bd->ExecSQL($sqlUpdate);

                endwhile;
            endif;
        endwhile;

    }

    function CantidadLineasLotablesNoCoinciden($idEntrada)
    {
        global $bd;

        $hayError = false;

        // RECORREMOS LAS LINEAS DE ENTRADA
        $sql = "SELECT * FROM MOVIMIENTO_ENTRADA_LINEA " .
            "WHERE ID_MOVIMIENTO_ENTRADA='$idEntrada' ORDER BY ID_MOVIMIENTO_ENTRADA_LINEA";

        $res = $bd->ExecSQL($sql);

        while ($row = $bd->SigReg($res)):
            if ($this->EsMaterialLotable($row->ID_MATERIAL)):
                $sqlLineasLote    = "SELECT * FROM MATERIAL_FISICO_ENTRADA " .
                    "WHERE ID_MOVIMIENTO_ENTRADA_LINEA = $row->ID_MOVIMIENTO_ENTRADA_LINEA " .
                    "ORDER BY ID_MOVIMIENTO_ENTRADA_LINEA";
                $resLineasLote    = $bd->ExecSQL($sqlLineasLote);
                $cantidadLotables = 0;
                while ($rowLineasLote = $bd->SigReg($resLineasLote)):
                    $cantidadLotables += $rowLineasLote->CANTIDAD;
                endwhile;
                if (number_format((float)$cantidadLotables, 2) != number_format((float)$row->CANTIDAD, 2)) $hayError = true;
            endif;
        endwhile;

        return $hayError;
    }

    function EliminarLotablesEntrada($idEntrada, $idLineaEntrada = "")
    {
        global $bd;

        if ($idLineaEntrada != ""):
            $whereLinea = "AND ID_MOVIMIENTO_ENTRADA_LINEA = $idLineaEntrada";
        endif;

        // RECORREMOS LAS LINEAS DE ENTRADA
        $sql = "SELECT * FROM MOVIMIENTO_ENTRADA_LINEA " .
            "WHERE ID_MOVIMIENTO_ENTRADA = $idEntrada $whereLinea ORDER BY ID_MOVIMIENTO_ENTRADA_LINEA";

        $res = $bd->ExecSQL($sql);

        while ($row = $bd->SigReg($res)):
            if ($this->EsMaterialLotable($row->ID_MATERIAL)):
                //$sqlLineasLote = "SELECT * FROM MATERIAL_FISICO_ENTRADA ".
//			   		   			 "WHERE ID_MOVIMIENTO_ENTRADA_LINEA = $row->ID_MOVIMIENTO_ENTRADA_LINEA ".
//								 "ORDER BY ID_MOVIMIENTO_ENTRADA_LINEA";
//				$resLineasLote = $bd->ExecSQL($sqlLineasLote);
//				while($rowLineasLote = $bd->SigReg($resLineasLote)):
//					$idMatFisico = $rowLineasLote->ID_MATERIAL_FISICO;
//					$cantidad = $rowLineasLote->CANTIDAD;
//
//					// 1. DECREMENTAREMOS EN MATERIAL_FISICO_UBICACION
//					$sqlUpdate = "UPDATE MATERIAL_FISICO_UBICACION SET ".
//								 "STOCK_TOTAL = STOCK_TOTAL - $cantidad ".
//				 				 "WHERE ID_UBICACION = $row->ID_UBICACION AND ID_MATERIAL_FISICO = $idMatFisico";
//					$bd->ExecSQL($sqlUpdate);
//				endwhile;

                // 2. BORRAMOS DE MATERIAL_FISICO_ENTRADA
                $sqlDelete = "DELETE FROM MATERIAL_FISICO_ENTRADA " .
                    "WHERE ID_MOVIMIENTO_ENTRADA_LINEA = $row->ID_MOVIMIENTO_ENTRADA_LINEA";
                $bd->ExecSQL($sqlDelete);

            endif;
        endwhile;
    }

    function ObtenerNumeroLoteAsiento($idAsiento)
    {
        global $bd;

        $sql = "SELECT mfa.ID_MATERIAL_FISICO FROM MATERIAL_FISICO_ASIENTO mfa";
        $sql = "$sql INNER JOIN MATERIAL_FISICO mf ON (mf.ID_MATERIAL_FISICO=mfa.ID_MATERIAL_FISICO)";
        $sql = "$sql WHERE mfa.ID_ASIENTO = $idAsiento";
        $sql = "$sql ORDER BY mf.NUMERO_LOTE";
        $res = $bd->ExecSQL($sql, "No");

        if ($res):
            while ($row = $bd->SigReg($res)):
                $arr[] = $row->ID_MATERIAL_FISICO;
            endwhile;

            return $arr;
        else:
            return false;
        endif;
    }

    function ObtenerCantidadLoteAsiento($idAsiento, $idMaterialFisico)
    {
        global $bd;

        $sql = "SELECT CANTIDAD FROM MATERIAL_FISICO_ASIENTO " .
            "WHERE ID_ASIENTO = $idAsiento AND ID_MATERIAL_FISICO = $idMaterialFisico";
        $res = $bd->ExecSQL($sql, "No");
        if ($res):
            $row = $bd->SigReg($res);

            return $row->CANTIDAD;
        else:
            return false;
        endif;
    }

    function ObtenerCantidadLoteLineaEntrada($idEntradaLinea, $idMaterialFisico)
    {
        global $bd;

        $sql = "SELECT CANTIDAD FROM MATERIAL_FISICO_ENTRADA " .
            "WHERE ID_MOVIMIENTO_ENTRADA_LINEA = $idEntradaLinea AND ID_MATERIAL_FISICO = $idMaterialFisico";
        $res = $bd->ExecSQL($sql, "No");
        if ($res):
            $row = $bd->SigReg($res);

            return $row->CANTIDAD;
        else:
            return false;
        endif;
    }

    function NumerosLoteCantidad($idMaterial)
    {
        // DEVUELVE UN ARRAY DEL TIPO:
        // arrNumeroLote['ID_MATERIAL_FISICO']["NUMERO_SERIE"]
        // arrNumeroLote['ID_MATERIAL_FISICO']["CANTIDAD"]
        // arrNumeroLote['ID_MATERIAL_FISICO']["CANTIDAD_DISPONIBLE"]

        global $bd;

        $sql = "SELECT ID_MATERIAL_FISICO, NUMERO_LOTE, CANTIDAD, CANTIDAD_DISPONIBLE " .
            "FROM MATERIAL_FISICO " .
            "WHERE ID_MATERIAL = $idMaterial AND NOT ISNULL(NUMERO_LOTE)";
        $res = $bd->ExecSQL($sql, "No");

        if ($res):
            while ($row = $bd->SigReg($res)):
                $arr[$row->ID_MATERIAL_FISICO]["NUMERO_LOTE"]         = $row->NUMERO_LOTE;
                $arr[$row->ID_MATERIAL_FISICO]["CANTIDAD"]            = $row->CANTIDAD;
                $arr[$row->ID_MATERIAL_FISICO]["CANTIDAD_DISPONIBLE"] = $row->CANTIDAD_DISPONIBLE;
            endwhile;

            return $arr;
        else:
            return false;
        endif;

    }

    function NumerosLoteCantidadPositivo($idMaterial)
    {
        // DEVUELVE UN ARRAY DEL TIPO:
        // arrNumeroLote['ID_MATERIAL_FISICO']["NUMERO_SERIE"]
        // arrNumeroLote['ID_MATERIAL_FISICO']["CANTIDAD"]
        // arrNumeroLote['ID_MATERIAL_FISICO']["CANTIDAD_DISPONIBLE"]

        global $bd;

        $sql = "SELECT ID_MATERIAL_FISICO, NUMERO_LOTE, CANTIDAD, CANTIDAD_DISPONIBLE " .
            "FROM MATERIAL_FISICO " .
            "WHERE ID_MATERIAL = $idMaterial AND CANTIDAD>0 AND NOT ISNULL(NUMERO_LOTE) ORDER BY NUMERO_LOTE";
        $res = $bd->ExecSQL($sql, "No");

        if ($res):
            while ($row = $bd->SigReg($res)):
                $arr[$row->ID_MATERIAL_FISICO]["NUMERO_LOTE"]         = $row->NUMERO_LOTE;
                $arr[$row->ID_MATERIAL_FISICO]["CANTIDAD"]            = $row->CANTIDAD;
                $arr[$row->ID_MATERIAL_FISICO]["CANTIDAD_DISPONIBLE"] = $row->CANTIDAD_DISPONIBLE;
            endwhile;

            return $arr;
        else:
            return false;
        endif;

    }

    function ActualizarUbicacionLotableLineaEntrada($idLineaMov, $idUbicacionNueva, $idUbicacionAntigua)
    {
        global $bd;

        $sql = "SELECT * FROM MATERIAL_FISICO_ENTRADA WHERE ID_MOVIMIENTO_ENTRADA_LINEA = $idLineaMov";

        $res = $bd->ExecSQL($sql);

        if ($res):
            while ($row = $bd->SigReg($res)):
                // 1. DESCONTAMOS DE LA UBICACIÓN ANTERIOR E INCREMENTAMOS EN LA NUEVA
                $sqlUpdate = "UPDATE MATERIAL_FISICO_UBICACION SET " .
                    "STOCK_TOTAL = STOCK_TOTAL - $row->CANTIDAD " .
                    "WHERE ID_MATERIAL_FISICO = $row->ID_MATERIAL_FISICO " .
                    "AND ID_UBICACION = $idUbicacionAntigua";
                $bd->ExecSQL($sqlUpdate);

                $numeroLote = $this->ObtenerNumeroLote($row->ID_MATERIAL_FISICO);
                // 2. INCREMENTAMOS EN LA NUEVA UBICACIÓN
                if ($this->ExisteNumeroLoteUbicacion($idUbicacionNueva, $row->ID_MATERIAL_FISICO)):
                    $sqlUpdate = "UPDATE MATERIAL_FISICO_UBICACION SET " .
                        "STOCK_TOTAL = STOCK_TOTAL + $row->CANTIDAD " .
                        "WHERE ID_MATERIAL_FISICO = $row->ID_MATERIAL_FISICO " .
                        "AND ID_UBICACION = $idUbicacionNueva";
                    $bd->ExecSQL($sqlUpdate);
                else:
                    $sqlInsert = "INSERT INTO MATERIAL_FISICO_UBICACION SET " .
                        "STOCK_TOTAL = $row->CANTIDAD, " .
                        "ID_MATERIAL_FISICO = $row->ID_MATERIAL_FISICO, " .
                        "ID_UBICACION = $idUbicacionNueva";
                    $bd->ExecSQL($sqlInsert);
                endif;
            endwhile;
        else:
            return false;
        endif;
    }

    function ObtenerLotesDisponiblesUbicacion($ubicacion, $idAlmacen, $idMaterial = "")
    {
        global $bd;

        if ($idMaterial != ""):
            $whereIdMat = "AND MF.ID_MATERIAL = $idMaterial";
        endif;

        // 1. OBTENEMOS LA UBICACION
        $sql = "SELECT * FROM UBICACION WHERE UBICACION = '$ubicacion' AND ID_ALMACEN = $idAlmacen";
        $res = $bd->ExecSQL($sql, "No");
        if (!$res):
            return false;
        else:
            $rowUbicacion = $bd->SigReg($res);
        endif;

        // 2. SELECCIONAMOS LOS LOTES CON CANTIDAD DISPONIBLE
        $sql = "SELECT MF.ID_MATERIAL_FISICO, NUMERO_LOTE, MFU.STOCK_TOTAL FROM MATERIAL_FISICO MF " .
            "INNER JOIN MATERIAL_FISICO_UBICACION MFU ON MFU.ID_MATERIAL_FISICO = MF.ID_MATERIAL_FISICO " .
            "WHERE NOT ISNULL(NUMERO_LOTE) " .
            "AND MFU.STOCK_TOTAL > 0 " .
            "AND MFU.ID_UBICACION = $rowUbicacion->ID_UBICACION $whereIdMat " .
            "ORDER BY NUMERO_LOTE";
        $res = $bd->ExecSQL($sql, "No");

        if (!$res):
            return false;
        else:
            $i = 0;
            while ($row = $bd->SigReg($res)):
                $arr[$i]["ID_MATERIAL_FISICO"]  = $row->ID_MATERIAL_FISICO;
                $arr[$i]["NUMERO_LOTE"]         = $row->NUMERO_LOTE;
                $arr[$i]["CANTIDAD_DISPONIBLE"] = $row->STOCK_TOTAL;
                $i++;
            endwhile;

            return $arr;
        endif;
    }

    function ObtenerLotesUbicacion($ubicacion, $idAlmacen, $idMaterial = "")
    {
        global $bd;

        if ($idMaterial != ""):
            $whereIdMat = "AND MF.ID_MATERIAL = $idMaterial";
        endif;

        // 1. OBTENEMOS LA UBICACION
        $sql = "SELECT * FROM UBICACION WHERE UBICACION = '$ubicacion' AND ID_ALMACEN = $idAlmacen";
        $res = $bd->ExecSQL($sql, "No");
        if (!$res):
            return false;
        else:
            $rowUbicacion = $bd->SigReg($res);
        endif;

        // 2. SELECCIONAMOS LOS LOTES CON CANTIDAD DISPONIBLE
        $sql = "SELECT MF.ID_MATERIAL_FISICO, NUMERO_LOTE, MFU.STOCK_TOTAL FROM MATERIAL_FISICO MF " .
            "INNER JOIN MATERIAL_FISICO_UBICACION MFU ON MFU.ID_MATERIAL_FISICO = MF.ID_MATERIAL_FISICO " .
            "WHERE NOT ISNULL(NUMERO_LOTE) " .
            "AND MFU.STOCK_TOTAL > 0 " .
            "AND MFU.ID_UBICACION = $rowUbicacion->ID_UBICACION $whereIdMat " .
            "ORDER BY NUMERO_LOTE";
        $res = $bd->ExecSQL($sql, "No");

        if (!$res):
            return false;
        else:
            $i = 0;
            while ($row = $bd->SigReg($res)):
                $arr[$i]["ID_MATERIAL_FISICO"] = $row->ID_MATERIAL_FISICO;
                $arr[$i]["NUMERO_LOTE"]        = $row->NUMERO_LOTE;
                $arr[$i]["CANTIDAD"]           = $row->STOCK_TOTAL;
                $i++;
            endwhile;

            return $arr;
        endif;
    }

    function ObtenerNumeroLoteTransferencia($idTransferencia)
    {
        global $bd;

        $sql = "SELECT ID_MATERIAL_FISICO FROM MATERIAL_FISICO_TRANSFERENCIA " .
            "WHERE ID_MOVIMIENTO_TRANSFERENCIA = $idTransferencia";
        $res = $bd->ExecSQL($sql, "No");

        if ($res):
            while ($row = $bd->SigReg($res)):
                $arr[] = $row->ID_MATERIAL_FISICO;
            endwhile;

            return $arr;
        else:
            return false;
        endif;
    }

    function ObtenerCantidadLoteTransferencia($idTransferencia, $idMaterialFisico)
    {
        global $bd;

        $sql = "SELECT CANTIDAD FROM MATERIAL_FISICO_TRANSFERENCIA " .
            "WHERE ID_MOVIMIENTO_TRANSFERENCIA = $idTransferencia AND ID_MATERIAL_FISICO = $idMaterialFisico";
        $res = $bd->ExecSQL($sql, "No");
        if ($res):
            $row = $bd->SigReg($res);

            return $row->CANTIDAD;
        else:
            return false;
        endif;
    }

    function ObtenerStockTotalMaterialAlmacen($idMaterial, $idAlmacen)
    {
        global $bd;

        $sqlTotalAlmacen = "SELECT SUM(STOCK_TOTAL) AS TOTAL_STOCK_ALMACEN
												FROM MATERIAL_UBICACION MU
												INNER JOIN UBICACION U ON U.ID_UBICACION = MU.ID_UBICACION
												WHERE MU.ID_MATERIAL = $idMaterial AND U.ID_ALMACEN = $idAlmacen";

        $resTotalAlmacen = $bd->ExecSQL($sqlTotalAlmacen);

        $rowTotalAlmacen = $bd->SigReg($resTotalAlmacen);

        return $rowTotalAlmacen->TOTAL_STOCK_ALMACEN;
    }

    function ObtenerStockReservadoMaterialAlmacen($idMaterial, $idAlmacen)
    {
        global $bd;

        $sqlReservadoAlmacen = "SELECT SUM(CANTIDAD_PEDIDO) AS RESERVADO_STOCK_ALMACEN
														FROM MOVIMIENTO_SALIDA_LINEA MSL
														INNER JOIN MOVIMIENTO_SALIDA MS ON MS.ID_MOVIMIENTO_SALIDA = MSL.ID_MOVIMIENTO_SALIDA
														INNER JOIN PEDIDO_SALIDA PS ON PS.ID_PEDIDO = MS.ID_PEDIDO_SALIDA
														WHERE PS.ID_ALMACEN_ORIGEN = $idAlmacen AND MSL.ID_MATERIAL = $idMaterial AND MS.ESTADO = 'En Preparacion'";

        $resReservadoAlmacen = $bd->ExecSQL($sqlReservadoAlmacen);

        $rowReservadoAlmacen = $bd->SigReg($resReservadoAlmacen);

        return $rowReservadoAlmacen->RESERVADO_STOCK_ALMACEN;

    }

    function ObtenerCantidadDisponibleMaterialAlmacen($idMaterial, $idAlmacen)
    {
        global $bd;

        $stockTotalAlmacen = $this->ObtenerStockTotalMaterialAlmacen($idMaterial, $idAlmacen);

        $stockReservadoAlmacen = $this->ObtenerStockReservadoMaterialAlmacen($idMaterial, $idAlmacen);

        $stockDisponibleAlmacen = $stockTotalAlmacen - $stockReservadoAlmacen;

        if ($stockDisponibleAlmacen < 0) $stockDisponibleAlmacen = 0;

        return $stockDisponibleAlmacen;
    }

    function ObtenerPdteExpedirNumeroLote($idMatFisico, $idMat, $idAlmacen)
    {
        global $bd;

        // STOCK PDTE. DE EXPEDIR DE UN NUMERO DE LOTE CONCRETO SACADO DE UN ALMACEN CONCRETO
        $sqlPdteExpedirLot = "SELECT SUM(MFS.CANTIDAD) AS TOTAL_PDTE_EXPEDIR " .
            "FROM MOVIMIENTO_SALIDA_LINEA MSL " .
            "INNER JOIN MATERIAL_FISICO_SALIDA MFS " .
            "ON(MFS.ID_MOVIMIENTO_SALIDA_LINEA = MSL.ID_MOVIMIENTO_SALIDA_LINEA) " .
            "INNER JOIN MOVIMIENTO_SALIDA MS " .
            "ON(MS.ID_MOVIMIENTO_SALIDA = MSL.ID_MOVIMIENTO_SALIDA) " .
            "INNER JOIN UBICACION U " .
            "ON(MSL.ID_UBICACION = U.ID_UBICACION) " .
            "WHERE MS.ESTADO = 'Pendiente de Expedir' " .
            "AND MFS.ID_MATERIAL_FISICO = $idMatFisico " .
            "AND U.ID_ALMACEN = $idAlmacen " .
            "AND MSL.ID_MATERIAL = '$idMat'";
        $resPdteExpedirLot = $bd->ExecSQL($sqlPdteExpedirLot);
        $rowPdteExpedirLot = $bd->SigReg($resPdteExpedirLot);

        // STOCK PDTE. DE EXPEDIR EN VICARLI
        return $rowPdteExpedir->TOTAL_PDTE_EXPEDIR;
    }

    function ObtenerEntradasPdteSapNumeroLote($idMatFisico, $idMat, $idAlmacen)
    {
        global $bd;

        // DEVUELVE LA CANTIDAD TOTAL CORRESPONDIENTE A UN NUMERO DE LOTE CONCRETO DE LINEAS DE ENTRADA
        // EN EL ALMACEN PASADO COMO PARAMETRO MARCADAS COMO PENDIENTES SAP
        $sqlEntradasPdteSapLot = "SELECT SUM(MFE.CANTIDAD) AS TOTAL " .
            "FROM MOVIMIENTO_ENTRADA_LINEA MEL " .
            "INNER JOIN MATERIAL_FISICO_ENTRADA MFE " .
            "ON(MFE.ID_MOVIMIENTO_ENTRADA_LINEA = MEL.ID_MOVIMIENTO_ENTRADA_LINEA) " .
            "INNER JOIN MOVIMIENTO_ENTRADA ME " .
            "ON(ME.ID_MOVIMIENTO_ENTRADA = MEL.ID_MOVIMIENTO_ENTRADA) " .
            "INNER JOIN UBICACION U " .
            "ON(MEL.ID_UBICACION = U.ID_UBICACION) " .
            "WHERE ME.PROPUESTA_IMPRESA " .
            "AND MFE.ID_MATERIAL_FISICO = $idMatFisico " .
            "AND MEL.ID_MATERIAL = '$idMat' " .
            "AND U.ID_ALMACEN = $idAlmacen " .
            "AND MEL.PDTE_SAP";

        $resEntradasPdteSapLot = $bd->ExecSQL($sqlEntradasPdteSapLot);
        $rowEntradasPdteSapLot = $bd->SigReg($resEntradasPdteSapLot);

        return $rowEntradasPdteSap->TOTAL;
    }

    // FIN FUNCIONES RELACIONADAS CON EL MATERIAL LOTABLE -----------------------------------

    // FUNCIONES RELACIONADAS CON CANTIDAD DE COMPRA Y CANTIDAD BASE

    //OBTIENE LA CANTIDAD EN UNIDAD BASE DE UN MATERIAL A PARTIR DE SU UNIDAD DE COMPRA
    function cantUnidadBase($idMaterial, $cantUnidadCompra)
    {
        global $bd;
        $row = $bd->VerReg("MATERIAL", "ID_MATERIAL", $idMaterial);
        // SI LA CANTIDAD BASE ES LA MISMA QUE LA DE COMPRA, DEVULVE LA CANTIDAD INTRODUCIDA
        if ($row->DENOMINADOR_CONVERSION == 0):
            return number_format((float)$cantUnidadCompra, 3, ".", "");
        elseif ($row->NUMERADOR_CONVERSION == 0):
            return number_format((float)$cantUnidadCompra, 3, ".", "");
        else:
            $cantUnidadBase = $cantUnidadCompra * $row->NUMERADOR_CONVERSION / $row->DENOMINADOR_CONVERSION;

            return number_format((float)$cantUnidadBase, 3, ".", "");
        endif;
    }

    //OBTIENE LA CANTIDAD EN UNIDAD DE COMPRA DE UN MATERIAL A PARTIR DE SU UNIDAD BASE
    function cantUnidadCompra($idMaterial, $cantUnidadBase)
    {
        global $bd;
        $row = $bd->VerReg("MATERIAL", "ID_MATERIAL", $idMaterial);
        // SI LA CANTIDAD DE COMPRA ES LA MISMA QUE LA BASE, DEVULVE LA CANTIDAD INTRODUCIDA
        if ($row->NUMERADOR_CONVERSION == 0):
            return number_format((float)$cantUnidadBase, 3, ".", "");
        elseif ($row->DENOMINADOR_CONVERSION == 0):
            return number_format((float)$cantUnidadBase, 3, ".", "");
        else:
            $cantUnidadCompra = $cantUnidadBase * $row->DENOMINADOR_CONVERSION / $row->NUMERADOR_CONVERSION;

            return number_format((float)$cantUnidadCompra, 3, ".", "");
        endif;
    }

    //OBTIENE LA ABREVIATURA Y LA DESCRIPCION DE LA UNIDAD BASE  Y DE COMPRA DE UN MATERIAL
    function unidadBaseyCompra($idMaterial)
    {
        global $bd;

        $NotificaErrorPorEmail = "No";
        $sqlMaterial           = "SELECT * FROM MATERIAL WHERE ID_MATERIAL = $idMaterial";
        $resultMaterial        = $bd->ExecSQL($sqlMaterial);
        $rowMat                = $bd->SigReg($resultMaterial);
        //$rowMat = $bd->VerReg("MATERIAL","ID_MATERIAL",$idMaterial);
        unset($NotificaErrorPorEmail);

        $rowUnidadBase   = $bd->VerReg("UNIDAD", "ID_UNIDAD", $rowMat->ID_UNIDAD_MEDIDA);
        $rowUnidadCompra = $bd->VerReg("UNIDAD", "ID_UNIDAD", $rowMat->ID_UNIDAD_COMPRA);

        /*$unidad["unidadBase"]=$rowUnidadBase->UNIDAD;
        $unidad["descripcionBase"]=$rowUnidadBase->DESCRIPCION;
        $unidad["unidadCompra"]=$rowUnidadCompra->UNIDAD;
        $unidad["descripcionCompra"]=$rowUnidadCompra->DESCRIPCION;*/

        //INCLUIMOS IDIOMAS:
        global $administrador;
        $idIdioma = $administrador->ID_IDIOMA;
        if ($idIdioma != "ESP" && $idIdioma != "ENG"): $idIdioma = "ESP"; endif;

        $unidad["unidadBase"]        = $rowUnidadBase->{'UNIDAD_' . $idIdioma};
        $unidad["descripcionBase"]   = $rowUnidadBase->{'DESCRIPCION_' . $idIdioma};
        $unidad["unidadCompra"]      = $rowUnidadCompra->{'UNIDAD_' . $idIdioma};
        $unidad["descripcionCompra"] = $rowUnidadCompra->{'DESCRIPCION_' . $idIdioma};

        return $unidad;

    }

    // FIN FUNCIONES RELACIONADAS CON CANTIDAD DE COMPRA Y CANTIDAD BASE


    function cambioPesoGramoAKg($peso, $unidad)
    {
        global $bd;
        $pesoDevolver = 0;
        if ($unidad == 'G'):
            $pesoEnKG = number_format(($peso / 1000), 3, ".", "");;
            if ($pesoEnKG < 0.001):
                $pesoDevolver = number_format((float)$peso, 3, ".", "") . " " . $unidad;
            else:
                $rowUnidadKG  = $bd->VerReg("UNIDAD", "UNIDAD", "KG", "No");
                $pesoDevolver = number_format((float)$pesoEnKG, 3, ".", "") . " " . $rowUnidadKG->UNIDAD;
            endif;
        else:
            $pesoDevolver = number_format((float)$peso, 3, ".", "") . " " . $unidad;
        endif;

        return $pesoDevolver;
    }

    //OBTIENE LA ABREVIATURA Y EL NOMBRE DEL TIPO DE BLOQUEO DE UN MATERIAL
    function tipoBloqueoMaterial($idTipoBloqueo)
    {
        global $bd;

        //SI ES NULO ES BLOQUEO OK
        if ($idTipoBloqueo == ""):
            $tipoBloqueoMat["siglas"] = "-";
            $tipoBloqueoMat["nombre"] = "Ok";
        else:
            //BUSCAMOS EL TIPO BLOQUEO
            $rowTipoBloqueo = $bd->VerReg("TIPO_BLOQUEO", "ID_TIPO_BLOQUEO", $idTipoBloqueo, "No");

            switch ($rowTipoBloqueo->TIPO_BLOQUEO_INTERNO):
                case "CCNP": //CALIDAD NO PREVENTIVO
                    $tipoBloqueoMat["siglas"] = "SIGLA_CC_CALIDAD_NO_PREVENTIVO";
                    break;
                case "XRC": //RETENIDO CALIDAD NO PREVENTIVO
                    $tipoBloqueoMat["siglas"] = "SIGLA_X_RETENIDO_CALIDAD_NO_PREVENTIVO";
                    break;
                case "PDPA": //PDTE DEVOLVER PROVEEDOR ANULACION
                    $tipoBloqueoMat["siglas"] = "SIGLA_DA_PDTE_DEVOLVER_PROVEEDOR_ANULACION";
                    break;
                case "SPDPC": //PDTE DEVOLVER PROVEEDOR CALIDAD
                    $tipoBloqueoMat["siglas"] = "SIGLA_DC_PDTE_DEVOLVER_PROVEEDOR_CALIDAD";
                    break;
                case "SP": //PREVENTIVO
                    $tipoBloqueoMat["siglas"] = "SIGLA_P_PREVENTIVO";
                    break;
                case "QRNG": //REPARABLE NO EN GARANTIA
                    $tipoBloqueoMat["siglas"] = "SIGLA_R_REPARABLE_NO_GARANTIA";
                    break;
                case "CCP": //CONTROL CALIDAD PREVENTIVO
                    $tipoBloqueoMat["siglas"] = "SIGLA_CP_CONTROL_CALIDAD_PREVENTIVO";
                    break;
                case "QRG": //REPARABLE EN GARANTIA
                    $tipoBloqueoMat["siglas"] = "SIGLA_G_GARANTIA";
                    break;
                case "QNRNG": //NO REPARABLE NO EN GARANTIA
                    $tipoBloqueoMat["siglas"] = "SIGLA_NR_NO_REPARABLE_NO_GARANTIA";
                    break;
                case "LC": //LOTE CADUCADO
                    $tipoBloqueoMat["siglas"] = "SIGLA_LC_LOTE_CADUCADO";
                    break;
                case "XRCP": //REPARABLE EN GARANTIA
                    $tipoBloqueoMat["siglas"] = "SIGLA_XP_RETENIDO_CALIDAD_PREVENTIVO";
                    break;
                case "VH": //VEHICULO
                    $tipoBloqueoMat["siglas"] = "SIGLA_VH_VEHICULO";
                    break;
                case "XRCRNG": //RETENIDO POR CALIDAD NO PREVENTIVO REPARABLE NO EN GARANTIA
                    $tipoBloqueoMat["siglas"] = "SIGLA_XR_RETENIDO_CALIDAD_NO_PREVENTIVO_REPARABLE_NO_GARANTIA";
                    break;
                case "XRCRG": //RETENIDO POR CALIDAD NO PREVENTIVO REPARABLE EN GARANTIA
                    $tipoBloqueoMat["siglas"] = "SIGLA_XG_RETENIDO_CALIDAD_NO_PREVENTIVO_REPARABLE_GARANTIA";
                    break;
                case "XRCNRNG": //RETENIDO POR CALIDAD NO PREVENTIVO NO REPARABLE NO EN GARANTIA
                    $tipoBloqueoMat["siglas"] = "SIGLA_XNR_RETENIDO_CALIDAD_NO_PREVENTIVO_NO_REPARABLE_NO_GARANTIA";
                    break;
                case "XBL": //BLOQUEO LOGISTICA
                    $tipoBloqueoMat["siglas"] = "SIGLA_BL_BLOQUEO_LOGISTICA";
                    break;
                case "RP": //RESERVADO PARA PREPARACION
                    $tipoBloqueoMat["siglas"] = "SIGLA_RP_RESERVADO_PARA_PREPARACION";
                    break;
                default:    //VALOR POR DEFECTO
                    $tipoBloqueoMat["siglas"] = $rowTipoBloqueo->TIPO_BLOQUEO;
                    break;
            endswitch;

            $tipoBloqueoMat["nombre"] = $rowTipoBloqueo->NOMBRE_A_MOSTRAR;
        endif;

        return $tipoBloqueoMat;
    }

    // COMPROBACION DEL STATUS DE UN MATERIAL/ALMACEN

    //OBTIENE SI EL STATUS DE UN MATERIAL ALMACEN
    function statusMaterialAlmacen($idMaterial, $idAlmacen)
    {
        global $bd;
        global $html;

        $NotificaErrorPorEmail = "No";
        $rowMat                = $bd->VerReg("MATERIAL", "ID_MATERIAL", $idMaterial);
        unset($NotificaErrorPorEmail);

        switch ($rowMat->ESTADO_BLOQUEO_MATERIAL):
            case '01-Bloqueo General':
                return 1;
                break;
            case '02-Obsoleto Fin Existencias (Error)':
                return 2;
                break;
            case '03-Código duplicado':
                return 3;
                break;
            case '04-Código inutilizable':
                return 4;
                break;
            case '05-Obsoleto Fin Existencias (Aviso)':
                return 5;
                break;
            case '06-Código Solo Fines Logísticos':
                return 6;
                break;
            case '07-Solo para Refer. Prov':
                return 7;
                break;
            case 'No bloqueado':
                $sqlMatAlm    = "SELECT *
                                    FROM MATERIAL_ALMACEN
                                    WHERE ID_MATERIAL = $idMaterial AND ID_ALMACEN = $idAlmacen";
                $resultMatAlm = $bd->ExecSQL($sqlMatAlm, "No");
                $html->PagErrorCondicionado($resultMatAlm, "==", false, "MaterialAlmacenNoDefinido");
                $html->PagErrorCondicionado($bd->NumRegs($resultMatAlm), "==", 0, "MaterialAlmacenNoDefinido");

                $rowMatAlm = $bd->SigReg($resultMatAlm);
                $html->PagErrorCondicionado($rowMatAlm, "==", false, "MaterialAlmacenNoDefinido");
                //$NotificaErrorPorEmail = "No";
                //$rowMatAlm = $bd->VerRegRest("MATERIAL_ALMACEN", "ID_MATERIAL = $idMaterial AND ID_ALMACEN = $idAlmacen", "No");
                //unset($NotificaErrorPorEmail);

                switch ($rowMatAlm->ESTADO_BLOQUEO_MATERIAL_ALMACEN):
                    case 'No bloqueado':
                        return NULL;
                        break;

                    case '01-Bloqueo General':
                        return 1;
                        break;

                    case '02-Obsoleto Fin Existencias (Error)':
                        return 2;
                        break;

                    case '03-Código duplicado':
                        return 3;
                        break;

                    case '04-Código inutilizable':
                        return 4;
                        break;

                    case '05-Obsoleto Fin Existencias (Aviso)':
                        return 5;
                        break;

                    case '06-Código Solo Fines Logísticos':
                        return 6;
                        break;
                    case '07-Solo para Refer. Prov':
                        return 7;
                        break;
                    case '08-Bloqueo Termosolar':
                        return 8;
                        break;
                    case '09-Bloqueo No Logístico':
                        return 9;
                        break;

                endswitch;
        endswitch;
    }

    //OBTIENE SI EL STATUS DE UN MATERIAL ALMACEN
    function statusMaterialAlmacenProcesoAutomatico($idMaterial, $idAlmacen)
    {
        global $bd;
        global $html;

        $NotificaErrorPorEmail = "No";
        $rowMat                = $bd->VerReg("MATERIAL", "ID_MATERIAL", $idMaterial);
        unset($NotificaErrorPorEmail);

        switch ($rowMat->ESTADO_BLOQUEO_MATERIAL):
            case '01-Bloqueo General':
                return 1;
                break;
            case '02-Obsoleto Fin Existencias (Error)':
                return 2;
                break;
            case '03-Código duplicado':
                return 3;
                break;
            case '04-Código inutilizable':
                return 4;
                break;
            case '05-Obsoleto Fin Existencias (Aviso)':
                return 5;
                break;
            case '06-Código Solo Fines Logísticos':
                return 6;
                break;
            case '07-Solo para Refer. Prov':
                return 7;
                break;

            case 'No bloqueado':
                $sqlMatAlm    = "SELECT *
                                    FROM MATERIAL_ALMACEN
                                    WHERE ID_MATERIAL = $idMaterial AND ID_ALMACEN = $idAlmacen";
                $resultMatAlm = $bd->ExecSQL($sqlMatAlm, "No");
                $errorDatos   = false;

                if (!$resultMatAlm):
                    $errorDatos = true;
                endif;

                $rowMatAlm = $bd->SigReg($resultMatAlm);

                if (!$rowMatAlm):
                    $errorDatos = true;
                endif;

                //$NotificaErrorPorEmail = "No";
                //$rowMatAlm = $bd->VerRegRest("MATERIAL_ALMACEN", "ID_MATERIAL = $idMaterial AND ID_ALMACEN = $idAlmacen", "No");
                //unset($NotificaErrorPorEmail);
                if ($errorDatos):
                    return false;
                    break;
                else:
                    switch ($rowMatAlm->ESTADO_BLOQUEO_MATERIAL_ALMACEN):
                        case 'No bloqueado':
                            return NULL;
                            break;

                        case '01-Bloqueo General':
                            return 1;
                            break;

                        case '02-Obsoleto Fin Existencias (Error)':
                            return 2;
                            break;

                        case '03-Código duplicado':
                            return 3;
                            break;

                        case '04-Código inutilizable':
                            return 4;
                            break;

                        case '05-Obsoleto Fin Existencias (Aviso)':
                            return 5;
                            break;

                        case '06-Código Solo Fines Logísticos':
                            return 6;
                            break;
                        case '07-Solo para Refer. Prov':
                            return 7;
                            break;
                        case '08-Bloqueo Termosolar':
                            return 8;
                            break;

                    endswitch;
                endif;

        endswitch;
    }

    function rellenaSelectEstadoBloqueoMaterial($tabla, $columna)
    {
        global $bd;
        global $auxiliar;
        global $administrador;

        //OBTENGO DATOS DEL ENUM
        $Elementos_estado_bloqueo = array();
        $i                  = 0;
        $sqlBloqueoMaterial = "SHOW COLUMNS FROM " . $tabla . " LIKE '" .$columna . "'" ;

        $resBloqueoMaterial = $bd->ExecSQL($sqlBloqueoMaterial);
        if ($rowBloqueoMaterial = $bd->SigReg($resBloqueoMaterial)):
            //LIMPIAMOS LA CADENA DEL ENUM
            preg_match("/^enum\(\'(.*)\'\)$/", (string) $rowBloqueoMaterial->Type, $arrBloqueos);
            $enum = explode("','", (string) $arrBloqueos[1]); //OBTENEMOS EL DATO
            $numero_bloqueos = count((array) $enum); //CUENTO CUANTOS ELEMENTOS HAY

            for ($i = 0; $i < $numero_bloqueos; $i++):
                $nombreBloqueo = trim( (string)$enum[$i]);
                $Elementos_estado_bloqueo[$i]['text']  = $auxiliar->traduce($nombreBloqueo, $administrador->ID_IDIOMA);
                $Elementos_estado_bloqueo[$i]['valor'] = $nombreBloqueo;
            endfor;
        endif;

        return $Elementos_estado_bloqueo;
    }

    //FUNCION PARA EXTRAER EL ALMACEN SUMINISTRADOR DE UN MATERIAL Y ALMACEN DE DESTINO SEGUN SU CLAVE DE APROVISIONAMIENTO ESPECIAL
    function AlmacenSuministradorSegunClaveAprovisionamientoEspecial($idMaterial, $idAlmacen)
    {
        global $bd;

        $sqlClaveAprovicionamiento    = "SELECT ID_CLAVE_APROVISIONAMIENTO_ESPECIAL
                                      FROM MATERIAL_ALMACEN
                                      WHERE ID_ALMACEN = $idAlmacen AND ID_MATERIAL = $idMaterial AND BAJA = 0";
        $resultClaveAprovicionamiento = $bd->ExecSQL($sqlClaveAprovicionamiento);
        if ($bd->NumRegs($resultClaveAprovicionamiento) == 0):
            return NULL;
        else:
            $rowClaveAprovicionamiento = $bd->SigReg($resultClaveAprovicionamiento);
            if ($rowClaveAprovicionamiento->ID_CLAVE_APROVISIONAMIENTO_ESPECIAL == NULL):
                return NULL;
            else:
                //EXTRAIGO EL ALMACEN SUMINISTRADOR DEL PEDIDO DE PENDIENTES
                $sqlAlmacenSuministrador    = "SELECT A.ID_ALMACEN
                                            FROM ALMACEN A
                                            INNER JOIN CLAVE_APROVISIONAMIENTO_ESPECIAL CAE ON CAE.ID_ALMACEN_APROVISIONAMIENTO = A.ID_ALMACEN
                                            WHERE CAE.ID_CLAVE_APROVISIONAMIENTO_ESPECIAL = $rowClaveAprovicionamiento->ID_CLAVE_APROVISIONAMIENTO_ESPECIAL";
                $resultAlmacenSuministrador = $bd->ExecSQL($sqlAlmacenSuministrador);
                $rowAlmacenSuministrador    = $bd->SigReg($resultAlmacenSuministrador);

                return $rowAlmacenSuministrador->ID_ALMACEN;
            endif;
        endif;
    }

    function rellenaEstadoBloqueoMaterialGrabacion(&$Elementos_estado_bloqueo, &$i, $tabla, $columna, $nombreEnValor, $traduce)
    {
        global $bd;
        global $auxiliar;
        global $administrador;

        $sustituirValor = ($i > 0); //SI NO ES 0, EL INDICE EMPIEZA A PARTIR DE $i

        //OBTENGO DATOS DEL ENUM
        $sqlBloqueoMaterial = "SHOW COLUMNS FROM " . $tabla . " LIKE '" .$columna . "'" ;

        $resBloqueoMaterial = $bd->ExecSQL($sqlBloqueoMaterial);
        //SI ENCONTRAMOS REGISTROS
        if ($rowBloqueoMaterial = $bd->SigReg($resBloqueoMaterial)):
            //LIMPIAMOS LA CADENA DEL ENUM
            preg_match("/^enum\(\'(.*)\'\)$/", (string) $rowBloqueoMaterial->Type, $arrBloqueos);
            $enum = explode("','", (string) $arrBloqueos[1]); //OBTENEMOS EL DATO
            $numero_bloqueos = count((array) $enum); //CUENTO CUANTOS ELEMENTOS HAY
            //USAMOS j PARA RECORRER EL ARRAY, i LO VAMOS INCREMENTANDO MANUALMENTE
            for ($j = 0; $j < $numero_bloqueos; $j++):
                $nombreBloqueo = trim( (string)$enum[$j]);
                if (!$sustituirValor) : //SI $i > 0 AL INICIO, NO ALTERAR $i
                    $i = (stripos((string)$nombreBloqueo,"-") ? intval(substr( (string) $nombreBloqueo,0,strpos( (string)$nombreBloqueo,"-"))) : "0");
                endif;

                if ($traduce):
                    $Elementos_estado_bloqueo[$i]['text']  = $auxiliar->traduce($nombreBloqueo, $administrador->ID_IDIOMA);
                else:
                    $Elementos_estado_bloqueo[$i]['text']  = $nombreBloqueo;
                endif;

                if ($nombreEnValor):
                    $Elementos_estado_bloqueo[$i]['valor'] = $nombreBloqueo;
                else:
                    $Elementos_estado_bloqueo[$i]['valor'] = ($i>0 ? $i : "");
                endif;

                if ($sustituirValor) : //SI $i > 0 AL INICIO, SI INCREMENTAR $i
                    $i = $i + 1;
                endif;
            endfor;
        endif;
    }

    /**
     * FUNCION PARA OBTENER EL IDENTIFICADOR UNA CLAVE DE APROVISIONAMIENTO DADOS UN MATERIAL Y UN ALMACEN
     * DEVUELVE NULL EN CASO DE NO ENCONTRAR O EL IDENTICADOR CORRESPONDIENTE
     */
    function getIdClaveAprovisionamientoEspecial($idMaterial, $idAlmacen)
    {
        global $bd;

        $sqlClaveAprovicionamiento    = "SELECT ID_CLAVE_APROVISIONAMIENTO_ESPECIAL
                                      FROM MATERIAL_ALMACEN
                                      WHERE ID_ALMACEN = $idAlmacen AND ID_MATERIAL = $idMaterial AND BAJA = 0";
        $resultClaveAprovicionamiento = $bd->ExecSQL($sqlClaveAprovicionamiento);
        if ($bd->NumRegs($resultClaveAprovicionamiento) == 0):
            return NULL;
        else:
            $rowClaveAprovicionamiento = $bd->SigReg($resultClaveAprovicionamiento);
            if ($rowClaveAprovicionamiento->ID_CLAVE_APROVISIONAMIENTO_ESPECIAL == NULL):
                return NULL;
            else:
                return $rowClaveAprovicionamiento->ID_CLAVE_APROVISIONAMIENTO_ESPECIAL;
            endif;
        endif;
    }


    function StockDisponible($idMaterial, $idAlmacen, $idTipoBloqueo)
    {
        global $bd;

        $sqlMatUbi    = "SELECT SUM(STOCK_TOTAL) AS TOTAL_STOCK_ALMACEN
                            FROM MATERIAL_UBICACION MU
                            INNER JOIN UBICACION U ON U.ID_UBICACION = MU.ID_UBICACION
                            WHERE MU.ACTIVO = 1 AND U.ID_ALMACEN = $idAlmacen AND MU.ID_MATERIAL = $idMaterial AND ID_TIPO_BLOQUEO " . ($idTipoBloqueo == NULL ? 'IS NULL' : "= $idTipoBloqueo") . " AND U.VALIDA_STOCK_DISPONIBLE = 1";    //echo($sqlMatUbi."<hr>");
        $resultMatUbi = $bd->ExecSQL($sqlMatUbi);

        if ($bd->NumRegs($resultMatUbi) == 0):
            $cantidadDisponible = 0;
        else:
            //EXTRAIGO EL REGISTRO DE MATERIAL UBICACION
            $rowMatUbi = $bd->SigReg($resultMatUbi);
            if ($rowMatUbi->TOTAL_STOCK_ALMACEN == NULL): //HAY QUE PONER ESTO PORQUE CUANDO NO HAY RESULTADOS DEVUELVE NULO EN VEZ DE CERO
                $cantidadDisponible = 0;
            else:
                $cantidadDisponible = $rowMatUbi->TOTAL_STOCK_ALMACEN;
            endif;
        endif;

        return $cantidadDisponible;
    }

    function StockReservado($idMaterial, $idAlmacen, $idTipoBloqueo)
    {
        global $bd;

        $sqlReservadoAlmacen    = "SELECT SUM(STOCK_RESERVADO) AS RESERVADO_STOCK_ALMACEN
														FROM (
																	SELECT MSL.ID_MATERIAL, MSL.ID_MOVIMIENTO_SALIDA, MSL.ID_PEDIDO_SALIDA_LINEA,CANTIDAD_PEDIDO, SUM(MSL.CANTIDAD), (CANTIDAD_PEDIDO - SUM(MSL.CANTIDAD)) AS STOCK_RESERVADO
																	FROM MOVIMIENTO_SALIDA_LINEA MSL
																	WHERE MSL.ID_ALMACEN = $idAlmacen AND MSL.ID_MATERIAL = $idMaterial AND MSL.ID_TIPO_BLOQUEO " . ($idTipoBloqueo == NULL ? 'IS NULL' : "= $idTipoBloqueo") . " AND MSL.ESTADO = 'En Preparacion' AND MSL.BAJA = 0 AND MSL.LINEA_ANULADA = 0
																	GROUP BY MSL.ID_MOVIMIENTO_SALIDA, MSL.ID_PEDIDO_SALIDA_LINEA
																 ) TEMP
														GROUP BY ID_MATERIAL";//echo($sqlReservadoAlmacen."<hr>");
        $resultReservadoAlmacen = $bd->ExecSQL($sqlReservadoAlmacen);

        if ($bd->NumRegs($resultReservadoAlmacen) == 0):
            $cantidadReservada = 0;
        else:
            $rowReservadoAlmacen = $bd->SigReg($resultReservadoAlmacen);
            $cantidadReservada   = $rowReservadoAlmacen->RESERVADO_STOCK_ALMACEN;
        endif;

        return $cantidadReservada;
    }

    //PARA SABER SI UN MATERIAL FISICO ESTA EN TRANSITO
    function MaterialFisicoEnTransito($idMaterialFisico, $idMovimientoSalidaLineaNoTenerEnCuenta = NULL)
    {
        global $bd;

        $sqlWhere = "";
        if ($idMovimientoSalidaLineaNoTenerEnCuenta != NULL):
            $sqlWhere = "AND ID_MOVIMIENTO_SALIDA_LINEA != $idMovimientoSalidaLineaNoTenerEnCuenta";
        endif;

        $sql    = "SELECT *
						FROM MOVIMIENTO_SALIDA_LINEA
						WHERE ESTADO = 'En Tránsito' AND CANTIDAD_PENDIENTE_DE_RECEPCIONAR_EN_DESTINO > 0 AND LINEA_ANULADA = 0 AND BAJA = 0 AND ID_MATERIAL_FISICO = $idMaterialFisico $sqlWhere";
        $result = $bd->ExecSQL($sql);
        if (($result == false) || ($bd->NumRegs($result) == 0)):
            return false;
        else:
            return true;
        endif;
    }

    /**
     * @param $idMaterialFisico Identificador del numero de serie/lote a obtener en una fecha concreta
     * @param $fechaSQL Fecha en formato SQL en la que obtener el valor del numero de serie/lote
     * @return string Valor del numero de serie/lote de un identificador en una fecha
     */
    function getNumeroSerieLoteEnFecha($idMaterialFisico, $fechaSQL)
    {
        global $bd;

        //VARIABLE A RETORNAR
        $numeroSerieLoteEnFecha = "";

        //BUSCO EL MATERIAL FISICO
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowMaterialFisico                = $bd->VerReg("MATERIAL_FISICO", "ID_MATERIAL_FISICO", $idMaterialFisico, "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);

        //SI NO EXISTE EL REGISTRO MATERIAL FISICO DEVUELVO CADENA VACIA
        if ($rowMaterialFisico == false):
            return $numeroSerieLoteEnFecha;
        endif;

        //BUSCO SI SE HAN PRODUCIDO CAMBIOS DE NUMERO DE SERIE
        $num = $bd->NumRegsTabla("CAMBIO_MATERIAL_FISICO", "ID_MATERIAL_FISICO = $rowMaterialFisico->ID_MATERIAL_FISICO");

        //SI NO SE HAN PRODUCIDO CAMBIOS DE NUMERO DE SERIE DEVUELVO EL VALOR ACTUAL DEL NUMERO DE SERIE/LOTE
        if ($num == 0):
            $numeroSerieLoteEnFecha = $rowMaterialFisico->NUMERO_SERIE_LOTE;
        else:
            //ESTABLEZCO EL VALOR DEL NUMERO DE SERIE/LOTE AL ACTUAL
            $numeroSerieLoteEnFecha = $rowMaterialFisico->NUMERO_SERIE_LOTE;

            $sqlCambiosEstado   = "SELECT * FROM CAMBIO_MATERIAL_FISICO WHERE ID_MATERIAL_FISICO = $rowMaterialFisico->ID_MATERIAL_FISICO ORDER BY FECHA DESC";
            $resultCambioEstado = $bd->ExecSQL($sqlCambiosEstado);
            while ($rowCambioEstado = $bd->SigReg($resultCambioEstado)):
                //SI LA FECHA DEL CAMBIO DE NUMERO DE SERIE ES MAYOR QUE LA FECHA EN LA QUE OBTENER EL VALOR DEL NUMERO DE SERIE/LOTE ESTABLEZCO EL NUMERO DE SERIE/LOTE ANTIGUO
                if ($rowCambioEstado->FECHA > $fechaSQL):
                    $numeroSerieLoteEnFecha = $rowCambioEstado->NUMERO_SERIE_LOTE_ANTIGUO;
                endif;
            endwhile;
        endif;

        //DEVULTO EL VALOR DEL NUMERO DE SERIE/LOTE EN LA FECHA ESPECIICADA
        return $numeroSerieLoteEnFecha;
    }

    /**
     * @param $idMaterial SE RECIBE COMO ENTRADA EL ID DEL MATERIAL DEL CUAL SE LOCALIZARAN LOS SUSTITUTOS
     * @param $controlDireccionTipoSustituto SE RECIBE COMO ENTRADA OPCIONAL SI ES NECESARIO CONTROLAR LA DIRECCIONABILIDAD EN EL TIPO DE SUSTITUCION
     * @param $mostrarBajas SE RECIBE COMO ENTRADA OPCIONAL SI ES NECESARIO MOSTRAR LAS BAJAS
     * @return array SE RETORNA UN ARRAY CON LOS SUSTITUTOS DEL MATERIAL (SUSTITUTOS DIRECTOS E INDIRECTOS)
     */
    function getSustitutos($idMaterial, $controlDireccionTipoSustituto = false, $mostrarBajas = false, $mostrarSustitutosRecursivos = false)
    {
        //ARRAY A DEVOLVER
        $arrSustitutos = array();

        //LOCALIZO LOS SUSTITUTOS DIRECTOS DEL MATERIAL
        $arrSustitutosDirectos = $this->getSustitutosDirectos($idMaterial, $controlDireccionTipoSustituto, $mostrarBajas);

        //GENERO LA LISTA CON LOS MATERIALES A NO TENER EN CUENTA
        $listaMaterialesNoIncluir                      = $idMaterial;
        $listaIdMaterialSustitutoRecursivosEncontrados = "0";
        foreach ($arrSustitutosDirectos as $idMaterialSustituto => $arrValores):
            $listaMaterialesNoIncluir                      = $listaMaterialesNoIncluir . "," . $idMaterialSustituto;
            $listaIdMaterialSustitutoRecursivosEncontrados = $listaIdMaterialSustitutoRecursivosEncontrados . "," . $arrValores[0]['ID_MATERIAL_SUSTITUTIVO'];
        endforeach;

        //LOCALIZO LOS SUSTITUTOS INDIRECTOS DEL MATERIAL
        foreach ($arrSustitutosDirectos as $idMaterialSustituto => $arrValores):
            $arrAux = explode(",", (string)$listaMaterialesNoIncluir);
            for ($i = 0; $i < count( (array)$arrAux); $i++):
                if ($arrAux[$i] == $idMaterialSustituto):
                    unset($arrAux[$i]);
                endif;
            endfor;
            $listaAux = implode(",", (array) $arrAux);

            if ($mostrarSustitutosRecursivos == false):
                $arrSustitutos[$idMaterialSustituto] = $arrValores;
            else:
                $arrSustitutos[$idMaterialSustituto] = $arrValores;
                //$arrSustitutos[$idMaterialSustituto]["SUSTITUTOS_INDIRECTOS"] = $this->getSustitutosRecursivos($idMaterialSustituto, $listaMaterialesNoIncluir, $mostrarBajas, $listaIdMaterialSustitutoRecursivosEncontrados);


                $arrSustitutos[$idMaterialSustituto]["SUSTITUTOS_INDIRECTOS"] = $this->getSustitutosRecursivos($idMaterialSustituto, $listaAux, $mostrarBajas, $listaIdMaterialSustitutoRecursivosEncontrados);

                //$arrSustitutos[$idMaterialSustituto]["SUSTITUTOS_INDIRECTOS"] = $this->getSustitutosRecursivos($idMaterialSustituto, $idMaterial . "," . $idMaterialSustituto, $mostrarBajas);
            endif;
        endforeach;

        return $arrSustitutos;
    }

    /**
     * @param $idMaterial SE RECIBE COMO ENTRADA EL ID DEL MATERIAL DEL CUAL SE LOCALIZARAN LOS SUSTITUTOS DIRECTOS
     * @param $controlDireccionTipoSustituto SE RECIBE COMO ENTRADA OPCIONAL SI ES NECESARIO CONTROLAR LA DIRECCIONABILIDAD EN EL TIPO DE SUSTITUCION
     * @param $mostrarBajas SE RECIBE COMO ENTRADA OPCIONAL SI ES NECESARIO MOSTRAR LAS BAJAS
     * @return array SE RETORNA UN ARRAY CON LOS SUSTITUTOS DIRECTOS DEL MATERIAL
     */
    function getSustitutosDirectos($idMaterial, $controlDireccionTipoSustituto = false, $mostrarBajas = false)
    {
        global $bd;

        //ARRAY A DEVOLVER
        $arrSustitutosDirectos = array();

        //CONTRUYO EL WHERE
        if ($mostrarBajas == false):
            $sqlWhere = "AND BAJA = 0";
        else:
            $sqlWhere = "AND 1 = 1";
        endif;

        //BUSCO LOS SUSTITUTOS DIRECTOS
        $sqlSustitutosDirectos    = "SELECT *
                                    FROM MATERIAL_SUSTITUTIVO
                                    WHERE (ID_MATERIAL = $idMaterial OR ID_MATERIAL_SUSTITUTO = $idMaterial) $sqlWhere";//echo($sqlSustitutosDirectos."<hr>");
        $resultSustitutosDirectos = $bd->ExecSQL($sqlSustitutosDirectos);
        while ($rowSustitutoDirecto = $bd->SigReg($resultSustitutosDirectos)):
            //AÑADO LOS SUSTITUTOS A LA IZQUIERDA QUE CORRESPONDAN
            if (
                ($rowSustitutoDirecto->ID_MATERIAL != $idMaterial) &&
                (($controlDireccionTipoSustituto == false) || (($controlDireccionTipoSustituto == true) && (($rowSustitutoDirecto->TIPO == 'Intercambiable 100%') || ($rowSustitutoDirecto->TIPO == 'Intercambiable no 100%'))))
            ):    //SI ES EL MATERIAL DEL CUAL ESTOY BUSCANDO SUSTITUTOS NO LO AÑADO AL ARRAY
                //GENERO EL ARRAY A AÑADIR
                $arrInsertar = array('VALOR' => $rowSustitutoDirecto->ID_MATERIAL, 'TIPO_SUSTITUTO' => $rowSustitutoDirecto->TIPO, 'ID_MATERIAL_SUSTITUTIVO' => $rowSustitutoDirecto->ID_MATERIAL_SUSTITUTIVO, 'BAJA' => $rowSustitutoDirecto->BAJA);
                //var_dump($arrInsertar);echo("<hr>");
                $arrSustitutosDirectos[$rowSustitutoDirecto->ID_MATERIAL][] = $arrInsertar;
            endif;

            //AÑADO LOS SUSTITUTOS A LA DERECHA
            if ($rowSustitutoDirecto->ID_MATERIAL_SUSTITUTO != $idMaterial):    //SI ES EL MATERIAL DEL CUAL ESTOY BUSCANDO SUSTITUTOS NO LO AÑADO AL ARRAY
                //GENERO EL ARRAY A AÑADIR
                $arrInsertar = array('VALOR' => $rowSustitutoDirecto->ID_MATERIAL_SUSTITUTO, 'TIPO_SUSTITUTO' => $rowSustitutoDirecto->TIPO, 'ID_MATERIAL_SUSTITUTIVO' => $rowSustitutoDirecto->ID_MATERIAL_SUSTITUTIVO, 'BAJA' => $rowSustitutoDirecto->BAJA);
                //var_dump($arrInsertar);echo("<hr>");
                $arrSustitutosDirectos[$rowSustitutoDirecto->ID_MATERIAL_SUSTITUTO][] = $arrInsertar;

                //$arrSustitutosDirectos[] = $rowSustitutoDirecto->ID_MATERIAL_SUSTITUTO;
            endif;
        endwhile;

        //DEJO UN SOLO REGISTRO POR MATERIAL
        //$arrSustitutosDirectos = array_unique($arrSustitutosDirectos);

        return $arrSustitutosDirectos;
    }

    /**
     * //     * @param $idMaterial SE RECIBE COMO ENTRADA EL ID DEL MATERIAL DEL CUAL SE LOCALIZARAN LOS SUSTITUTOS INCLUYENDO RECURSIVIDAD
     * //     * @param string $listaMaterialesNoIncluir LISTA OPCIONAL DE MATERIAL A NO INCLUIR EN EL ARRAY A DEVOLVER
     * //     * @param $controlDireccionTipoSustituto SE RECIBE COMO ENTRADA OPCIONAL SI ES NECESARIO CONTROLAR LA DIRECCIONABILIDAD EN EL TIPO DE SUSTITUCION
     * //     * @param $mostrarBajas SE RECIBE COMO ENTRADA OPCIONAL SI ES NECESARIO MOSTRAR LAS BAJAS
     * //     * @return array SE RETORNA UN ARRAY CON TODOS LOS MATERIALES SUSTITUTIVOS RECURSIVAMENTE, PUEDE SER VACIO SI NO EXISTEN SUSTITUTOS
     * //     */
    function getSustitutosRecursivos($idMaterial, $listaMaterialesNoIncluir = "", $mostrarBajas = false, $listaIdMaterialSustitutoRecursivosEncontrados = "0")
    {
        global $bd;

        //VARIABLE A DEVOLVER
        $arrSustitutosDevueltos = array();

        //VARIABLE PARA EXTRAER LO ID DE MATERIAL SUSTITUTIVO RECURSIVOS
        $arrSustitutos = array_unique( (array)explode(",", (string)$this->getSustitutosRecursivosLista($idMaterial, $listaMaterialesNoIncluir, $mostrarBajas, $listaIdMaterialSustitutoRecursivosEncontrados)));

        //RECORRO LOS ID DE MATERIAL SUSTITUTIVO RECURSIVOS PARA VALIDARLOS Y EXTRERLOS
        foreach ($arrSustitutos as $idMaterialSustitutivo):
            if (($idMaterialSustitutivo != "") && ($idMaterialSustitutivo != "0") && ($idMaterialSustitutivo != 0)):
                //LOCALIZO EL REGISTRO MATERIAL SUSTITUTIVO
                $rowMaterialSustitutivo = $bd->VerReg("MATERIAL_SUSTITUTIVO", "ID_MATERIAL_SUSTITUTIVO", $idMaterialSustitutivo);

                //SI EL MATERIAL IZQUIERDO NO ES EL ORIGINAL NI EL DIRECTO LO AÑADO AL ARRAY
                if (($rowMaterialSustitutivo->ID_MATERIAL != $idMaterial) && (!(in_array($rowMaterialSustitutivo->ID_MATERIAL, (array) explode(",", (string) $listaMaterialesNoIncluir))))):
                    $arrInsertar                                                  = array('VALOR' => $rowMaterialSustitutivo->ID_MATERIAL, 'TIPO_SUSTITUTO' => 'INDIRECTO', 'BAJA' => $rowMaterialSustitutivo->BAJA);
                    $arrSustitutosDevueltos[$rowMaterialSustitutivo->ID_MATERIAL] = $arrInsertar;
                endif;

                //SI EL MATERIAL DERECHO NO ES EL ORIGINAL NI EL DIRECTO LO AÑADO AL ARRAY
                if (($rowMaterialSustitutivo->ID_MATERIAL_SUSTITUTO != $idMaterial) && (!(in_array($rowMaterialSustitutivo->ID_MATERIAL_SUSTITUTO, (array) explode(",", (string) $listaMaterialesNoIncluir))))):
                    $arrInsertar                                                            = array('VALOR' => $rowMaterialSustitutivo->ID_MATERIAL_SUSTITUTO, 'TIPO_SUSTITUTO' => 'INDIRECTO', 'BAJA' => $rowMaterialSustitutivo->BAJA);
                    $arrSustitutosDevueltos[$rowMaterialSustitutivo->ID_MATERIAL_SUSTITUTO] = $arrInsertar;
                endif;
            endif;
        endforeach;

        return $arrSustitutosDevueltos;
    }

    function getSustitutosRecursivosLista($idMaterial, $listaMaterialesNoIncluir = "", $mostrarBajas = false, $listaIdMaterialSustitutoRecursivosEncontrados = "0")
    {
        global $bd;

        //VARIABLE PARA ALMACENAR LOS ID DE LOS SUSTITUTOS RECURSIVOS
        $listaIdMaterialSustitutoRecursivos = "";

        if (in_array($idMaterial, (array) explode(",", (string) $listaMaterialesNoIncluir))):
            return $listaIdMaterialSustitutoRecursivos;
        endif;

        //CONTRUYO EL WHERE
        $whereMaterial                               = "";
        $arrIdMaterialSustitutoRecursivosEncontrados = explode(",", (string)$listaIdMaterialSustitutoRecursivosEncontrados);
        $arrIdMaterialSustitutoRecursivosEncontrados = array_unique( (array)$arrIdMaterialSustitutoRecursivosEncontrados); //DEJO SOLO LOS VALORES DIFERENTES
        foreach ($arrIdMaterialSustitutoRecursivosEncontrados as $idMaterialSustitutoRecursivosEncontrados):
            $whereMaterial = $whereMaterial . " AND ID_MATERIAL_SUSTITUTIVO <> $idMaterialSustitutoRecursivosEncontrados";
        endforeach;
        //$whereMaterial = " AND ID_MATERIAL_SUSTITUTIVO NOT IN ($listaIdMaterialSustitutoRecursivosEncontrados)";

        if ($mostrarBajas == false):
            $whereMaterial = $whereMaterial . " AND BAJA = 0";
        else:
            $whereMaterial = $whereMaterial . "AND 1 = 1";
        endif;
        if ($listaMaterialesNoIncluir == ""):
            $whereMaterial = $whereMaterial . " AND TRUE";
        else:
            $parte_izq                   = "1 = 1";
            $parte_dcha                  = "1 = 1";
            $arrlistaMaterialesNoIncluir = explode(",", (string)$listaMaterialesNoIncluir);
            $arrlistaMaterialesNoIncluir = array_unique( (array)$arrlistaMaterialesNoIncluir); //DEJO SOLO LOS VALORES DIFERENTES
            foreach ($arrlistaMaterialesNoIncluir as $idMaterialesNoIncluir):
                $parte_izq  = $parte_izq . " AND ID_MATERIAL <> $idMaterialesNoIncluir";
                $parte_dcha = $parte_dcha . " AND ID_MATERIAL_SUSTITUTIVO <> $idMaterialesNoIncluir";
            endforeach;
            $whereMaterial = $whereMaterial . " AND ( (" . $parte_izq . ") OR (" . $parte_dcha . ") )";
            //$whereMaterial = $whereMaterial . " AND (ID_MATERIAL NOT IN ($listaMaterialesNoIncluir) OR ID_MATERIAL_SUSTITUTIVO NOT IN ($listaMaterialesNoIncluir))";
        endif;

        //BUSCO LOS SUSTITUTOS DIRECTOS
        $sqlSustitutosDirectos    = "SELECT *
                                    FROM MATERIAL_SUSTITUTIVO
                                    WHERE (ID_MATERIAL = $idMaterial OR ID_MATERIAL_SUSTITUTO = $idMaterial) $whereMaterial";
        $resultSustitutosDirectos = $bd->ExecSQL($sqlSustitutosDirectos);
        if ($bd->NumRegs($resultSustitutosDirectos) == 0):
            return "";
        else:
            while ($rowSustitutoDirecto = $bd->SigReg($resultSustitutosDirectos)):
                //INCLUYO EN LA LISTA DE MATERIALES A ENCONTRAR EL MATERIAL SUSTITUTIVO ENCONTRADO
                if ($listaIdMaterialSustitutoRecursivos == ""):
                    $listaIdMaterialSustitutoRecursivos = $rowSustitutoDirecto->ID_MATERIAL_SUSTITUTIVO;
                else:
                    if (!(in_array($rowSustitutoDirecto->ID_MATERIAL_SUSTITUTIVO, (array) explode(",", (string) $listaIdMaterialSustitutoRecursivos)))):
                        $listaIdMaterialSustitutoRecursivos = $listaIdMaterialSustitutoRecursivos . "," . $rowSustitutoDirecto->ID_MATERIAL_SUSTITUTIVO;
                    endif;
                endif;

                //EXTRAIGO LOS SUSTITUOS POR LA IZQUIERDA
                if (($idMaterial != $rowSustitutoDirecto->ID_MATERIAL) && (!(in_array($rowSustitutoDirecto->ID_MATERIAL, (array) explode(",", (string) $listaMaterialesNoIncluir))))):
                    //AÑADO EL MATERIAL IZQUIERDO A LA LISTA DE MATERIALES A NO BUSCAR
                    if ($listaMaterialesNoIncluir == ""):
                        $listaMaterialesNoIncluir = $rowSustitutoDirecto->ID_MATERIAL;
                    else:
                        $listaMaterialesNoIncluir = $listaMaterialesNoIncluir . "," . $rowSustitutoDirecto->ID_MATERIAL;
                    endif;

                    $listaSustitutosIzquierda = $this->getSustitutosRecursivosLista($rowSustitutoDirecto->ID_MATERIAL, $listaMaterialesNoIncluir, $mostrarBajas, $listaIdMaterialSustitutoRecursivosEncontrados . "," . $listaIdMaterialSustitutoRecursivos);

                    //AÑADO LOS SUSTITUOS DE LA IZQUIERDA
                    if ($listaSustitutosIzquierda != ""):
                        if ($listaSustitutosRecursivos == ""):
                            $listaIdMaterialSustitutoRecursivos = $listaSustitutosIzquierda;
                        else:
                            $listaIdMaterialSustitutoRecursivos = $listaIdMaterialSustitutoRecursivos . "," . $listaSustitutosIzquierda;
                        endif;
                    endif;
                endif;

                //EXTRAIGO LOS SUSTITUOS POR LA DERECHA
                if (($idMaterial != $rowSustitutoDirecto->ID_MATERIAL_SUSTITUTO) && (!(in_array($rowSustitutoDirecto->ID_MATERIAL_SUSTITUTO, (array) explode(",", (string) $listaMaterialesNoIncluir))))):
                    //AÑADO EL MATERIAL DERECHO A LA LISTA DE MATERIALES A NO BUSCAR
                    if ($listaMaterialesNoIncluir == ""):
                        $listaMaterialesNoIncluir = $rowSustitutoDirecto->ID_MATERIAL_SUSTITUTO;
                    else:
                        $listaMaterialesNoIncluir = $listaMaterialesNoIncluir . "," . $rowSustitutoDirecto->ID_MATERIAL_SUSTITUTO;
                    endif;

                    $listaSustitutosDerecha = $this->getSustitutosRecursivosLista($rowSustitutoDirecto->ID_MATERIAL_SUSTITUTO, $listaMaterialesNoIncluir, $mostrarBajas, $listaIdMaterialSustitutoRecursivosEncontrados . "," . $listaIdMaterialSustitutoRecursivos);

                    //AÑADO LOS SUSTITUTOS A LA DERECHA
                    if ($listaSustitutosDerecha != ""):
                        if ($listaIdMaterialSustitutoRecursivos == ""):
                            $listaIdMaterialSustitutoRecursivos = $listaSustitutosDerecha;
                        else:
                            $listaIdMaterialSustitutoRecursivos = $listaIdMaterialSustitutoRecursivos . "," . $listaSustitutosDerecha;
                        endif;
                    endif;
                endif;
            endwhile;
        endif;

        //RETURN LISTA SUSTITUTOS SEPARADOS POR COMAS
        return $listaIdMaterialSustitutoRecursivos;
    }

    /**
     * @param $idMaterial EL INPUT SERA EL MATERIAL A BORRAR DE LA TABLA MATERIAL SUSTITUTIVO DESARROLLADO
     * SE BORRARAN TODOS LOS REGISTROS DONDE SE ENCUENTRE EL MATERIAL A BORRAR A IZQ y DCHA
     * ADEMAS BORRAREMOS TODOS LOS REGISTROS CON LOS MATERIALES QUE ESTEN RELACIONADOS% EL MATERIAL A BORRAR
     * DESPUES REGENERAREMOS LAS RELACIONES DE TODOS ESTOS MATERIALES SIN TENER EN CUENTA LA RELACION CON EL MATERIAL A BORRAR
     */
    function borrarRegistroMaterialSustitutivoDesarrollado($idMaterial)
    {
        global $bd;

        //ARRAY PARA GUARDAR LOS MATERIALES A REGENERAR
        $arrMaterialesRegenerar = array();

        //BUSCO LOS MATERIALES RELACIONADOS CON EL MATERIAL A BORRAR SUS RELACIONES
        $sqlLineas    = "SELECT DISTINCT ID_MATERIAL AS ID_MATERIAL
                        FROM MATERIAL_SUSTITUTIVO_DESARROLLADO MSD
                        WHERE MSD.ID_MATERIAL = $idMaterial OR MSD.ID_MATERIAL_SUSTITUTO = $idMaterial
                        UNION
                        SELECT DISTINCT ID_MATERIAL_SUSTITUTO AS ID_MATERIAL
                        FROM MATERIAL_SUSTITUTIVO_DESARROLLADO MSD
                        WHERE MSD.ID_MATERIAL = $idMaterial OR MSD.ID_MATERIAL_SUSTITUTO = $idMaterial";
        $resultLineas = $bd->ExecSQL($sqlLineas);
        while ($rowLinea = $bd->SigReg($resultLineas)):
            //SI EL MATERIAL NO ES EL QUE HAY QUE BORRAR LO AÑADO AL DE MATERIALES A REGENERAR
            if ($rowLinea->ID_MATERIAL != $idMaterial):
                $arrMaterialesRegenerar[] = $rowLinea->ID_MATERIAL;
            endif;

            //BORRAMOS LOS REGISTROS IMPLICADOS
            $sqlDelete = "DELETE FROM MATERIAL_SUSTITUTIVO_DESARROLLADO WHERE ID_MATERIAL = $rowLinea->ID_MATERIAL OR ID_MATERIAL_SUSTITUTO = $rowLinea->ID_MATERIAL";
            $bd->ExecSQL($sqlDelete);
        endwhile;

        //REGENERAMOS LOS MATERIALES RELACIONADOS CON EL MATERIAL BORRADO
        foreach ($arrMaterialesRegenerar as $idMaterialRegenerar):
            $this->crearRegistrosMaterialSustitutivoDesarrolladoConMaterialesRelacionados($idMaterialRegenerar);
        endforeach;
    }

    /**
     * @param $idMaterial EL INPUT SERA EL MATERIAL SOBRE EL CUAL CREAREMOS TODAS LAS RELACIONES NECESARIAS (SUSTITUTOS DIRECTOS E INDIRECTOS)
     * TAMBIEN SE GENERARN LAS NUEVAS RELACIONES QUE PUEDAN SURGIR CON LOS MATERIALES RELACIONEADOS CON EL MATERIAL PRINCIPAL
     */
    function crearRegistrosMaterialSustitutivoDesarrolladoConMaterialesRelacionados($idMaterial)
    {
        global $bd;

        //CREO LAS RELACIONES (DIRECTAS E INDIRECTAS) RESPECTO AL MATERIAL
        $this->crearRegistrosMaterialSustitutivoDesarrolladoUnMaterial($idMaterial);

        //BUSCO LAS RELACIONES DEL MATERIAL DEL CUAL ACABAMOS DE CREAR SUS RELACIONES
        $sqlLineas    = "SELECT DISTINCT ID_MATERIAL AS ID_MATERIAL
                        FROM MATERIAL_SUSTITUTIVO_DESARROLLADO MSD
                        WHERE MSD.ID_MATERIAL_SUSTITUTO = $idMaterial
                        UNION
                        SELECT DISTINCT ID_MATERIAL_SUSTITUTO AS ID_MATERIAL
                        FROM MATERIAL_SUSTITUTIVO_DESARROLLADO MSD
                        WHERE MSD.ID_MATERIAL = $idMaterial";
        $resultLineas = $bd->ExecSQL($sqlLineas);
        while ($rowLinea = $bd->SigReg($resultLineas)):
            $this->crearRegistrosMaterialSustitutivoDesarrolladoUnMaterial($rowLinea->ID_MATERIAL);
        endwhile;
    }

    /**
     * @param $idMaterial EL INPUT SERA EL MATERIAL SOBRE EL CUAL CREAREMOS TODAS LAS RELACIONES NECESARIAS (SUSTITUTOS DIRECTOS E INDIRECTOS)
     */
    function crearRegistrosMaterialSustitutivoDesarrolladoUnMaterial($idMaterial)
    {
        global $bd;

        //EXTRAIGO LOS SUSTITUTOS
        $arrSustitutos = $this->getSustitutos($idMaterial, $controlDireccionTipoSustituto = false, $mostrarBajas = false, $mostrarSustitutosRecursivos = true);

        //RECORRO EL ARRAY DE SUSTITUTOS GENERADOS
        foreach ($arrSustitutos as $idMaterialSustituto => $arrValores):
            //RECORRO EL ARRAY DE SUSTITUTOS GENERADOS POR MATERIAL SUSTITUTO
            foreach ($arrValores as $clave => $arrSustituciones):

                //COMPRUEBO SI EXISTE EL MATERIAL ORIGINAL
                $GLOBALS["NotificaErrorPorEmail"] = "No";
                $rowMaterial                      = $bd->VerReg("MATERIAL", "ID_MATERIAL", $idMaterial, "No");
                unset($GLOBALS["NotificaErrorPorEmail"]);
                if ($rowMaterial == false):
                    //SIGO CON LA ITERACION Y ME SALTO ESTE MATERIAL
                    continue;
                endif;

                //SUSTITUTOS DIRECTOS
                if ((string)$clave != 'SUSTITUTOS_INDIRECTOS'):

                    //COMPRUEBO SI EXISTE EL MATERIAL SUSTITUTIVO
                    $GLOBALS["NotificaErrorPorEmail"] = "No";
                    $rowMaterialSustitutivo           = $bd->VerReg("MATERIAL", "ID_MATERIAL", $arrSustituciones['VALOR'], "No");
                    unset($GLOBALS["NotificaErrorPorEmail"]);
                    if ($rowMaterialSustitutivo == false):
                        //SIGO CON LA ITERACION Y ME SALTO ESTE MATERIAL
                        continue;
                    endif;

                    //COMPRUEBO SI YA EXISTE EL REGISTRO
                    $num = $bd->NumRegsTabla("MATERIAL_SUSTITUTIVO_DESARROLLADO", "ID_MATERIAL = " . $idMaterial . " AND ID_MATERIAL_SUSTITUTO = " . $arrSustituciones['VALOR']);

                    //AÑADO LA PRIMERA DIRECCION IZQ->DCHA
                    if ($num == 0):
                        $sqlInsert = "INSERT INTO MATERIAL_SUSTITUTIVO_DESARROLLADO SET
                                    ID_MATERIAL = " . $idMaterial . "
                                    , ID_MATERIAL_SUSTITUTO = " . $arrSustituciones['VALOR'] . "
                                    , TIPO = '" . $arrSustituciones['TIPO_SUSTITUTO'] . "'";
                        $bd->ExecSQL($sqlInsert);
                    else:
                        $sqlUpdate = "UPDATE MATERIAL_SUSTITUTIVO_DESARROLLADO SET
                                    TIPO = '" . $arrSustituciones['TIPO_SUSTITUTO'] . "'
                                    WHERE ID_MATERIAL = " . $idMaterial . " AND ID_MATERIAL_SUSTITUTO = " . $arrSustituciones['VALOR'];
                        $bd->ExecSQL($sqlUpdate);
                    endif;
                    //FIN AÑADO LA PRIMERA DIRECCION IZQ->DCHA

                    //AÑADO LA SEGUNDA DIRECCION DCHA->IZQ SI SE DIESE EL CASO
                    if (($arrSustituciones['TIPO_SUSTITUTO'] == "Intercambiable 100%") || ($arrSustituciones['TIPO_SUSTITUTO'] == "Intercambiable no 100%")):
                        //COMPRUEBO SI YA EXISTE EL REGISTRO
                        $num = $bd->NumRegsTabla("MATERIAL_SUSTITUTIVO_DESARROLLADO", "ID_MATERIAL = " . $arrSustituciones['VALOR'] . " AND ID_MATERIAL_SUSTITUTO = " . $idMaterial);

                        if ($num == 0):
                            $sqlInsert = "INSERT INTO MATERIAL_SUSTITUTIVO_DESARROLLADO SET
                                        ID_MATERIAL = " . $arrSustituciones['VALOR'] . "
                                        , ID_MATERIAL_SUSTITUTO = " . $idMaterial . "
                                        , TIPO = '" . $arrSustituciones['TIPO_SUSTITUTO'] . "'";
                            $bd->ExecSQL($sqlInsert);
                        else:
                            $sqlUpdate = "UPDATE MATERIAL_SUSTITUTIVO_DESARROLLADO SET
                                        TIPO = '" . $arrSustituciones['TIPO_SUSTITUTO'] . "'
                                        WHERE ID_MATERIAL = " . $arrSustituciones['VALOR'] . " AND ID_MATERIAL_SUSTITUTO = " . $idMaterial;
                            $bd->ExecSQL($sqlUpdate);
                        endif;
                    endif;
                //FIN AÑADO LA SEGUNDA DIRECCION DCHA->IZQ SI SE DIESE EL CASO

                //SUSTITUTOS INDIRECTOS
                elseif ((string)$clave == 'SUSTITUTOS_INDIRECTOS'):
                    //RECORRO CADA MATERIAL SUSTITUTO INDIRECTO
                    foreach ($arrValores['SUSTITUTOS_INDIRECTOS'] as $idMaterialSustitutoIndirecto => $arrValoresMaterialSustitutoIndirecto):

                        //COMPRUEBO SI EXISTE EL MATERIAL SUSTITUTIVO
                        $GLOBALS["NotificaErrorPorEmail"] = "No";
                        $rowMaterialSustitutivo           = $bd->VerReg("MATERIAL", "ID_MATERIAL", $arrValoresMaterialSustitutoIndirecto['VALOR'], "No");
                        unset($GLOBALS["NotificaErrorPorEmail"]);
                        if ($rowMaterialSustitutivo == false):
                            //SIGO CON LA ITERACION Y ME SALTO ESTE MATERIAL
                            continue;
                        endif;

                        //COMPRUEBO SI YA EXISTE EL REGISTRO
                        $num = $bd->NumRegsTabla("MATERIAL_SUSTITUTIVO_DESARROLLADO", "ID_MATERIAL = " . $idMaterial . " AND ID_MATERIAL_SUSTITUTO = " . $arrValoresMaterialSustitutoIndirecto['VALOR']);

                        //AÑADO LA PRIMERA DIRECCION IZQ->DCHA SI NO ESTA YA CREADA
                        if ($num == 0):
                            $sqlInsert = "INSERT INTO MATERIAL_SUSTITUTIVO_DESARROLLADO SET
                                    ID_MATERIAL = " . $idMaterial . "
                                    , ID_MATERIAL_SUSTITUTO = " . $arrValoresMaterialSustitutoIndirecto['VALOR'] . "
                                    , TIPO = '" . $arrValoresMaterialSustitutoIndirecto['TIPO_SUSTITUTO'] . "'";
                            $bd->ExecSQL($sqlInsert);
                        endif;
                        //FIN AÑADO LA PRIMERA DIRECCION IZQ->DCHA SI NO ESTA YA CREADA

                        //COMPRUEBO SI YA EXISTE EL REGISTRO
                        $num = $bd->NumRegsTabla("MATERIAL_SUSTITUTIVO_DESARROLLADO", "ID_MATERIAL = " . $arrValoresMaterialSustitutoIndirecto['VALOR'] . " AND ID_MATERIAL_SUSTITUTO = " . $idMaterial);
//AÑADO LA SEGUNDA DIRECCION DCHA->IZQ SI NO ESTA YA CREADA
                        if ($num == 0):
                            $sqlInsert = "INSERT INTO MATERIAL_SUSTITUTIVO_DESARROLLADO SET
                                    ID_MATERIAL = " . $arrValoresMaterialSustitutoIndirecto['VALOR'] . "
                                    , ID_MATERIAL_SUSTITUTO = " . $idMaterial . "
                                    , TIPO = '" . $arrValoresMaterialSustitutoIndirecto['TIPO_SUSTITUTO'] . "'";
                            $bd->ExecSQL($sqlInsert);
                        endif;
                        //FIN AÑADO LA SEGUNDA DIRECCION DCHA->IZQ SI NO ESTA YA CREADA

                    endforeach;
                    //FIN RECORRO CADA MATERIAL SUSTITUTO INDIRECTO
                endif;
                //FIN SUSTITUTOS DIRECTOS E INDIRECTOS

            endforeach;
            //FIN RECORRO EL ARRAY DE SUSTITUTOS GENERADOS POR MATERIAL SUSTITUTO
        endforeach;
        //FIN RECORRO EL ARRAY DE SUSTITUTOS GENERADOS
    }

    function getSustitutosIndirectos($idMaterial)
    {
        global $bd;

        //ARRAY A DEVOLVER
        $arrSustitutos = array();

        //LOCALIZO LOS SUSTITUTOS DIRECTOS DEL MATERIAL
        $arrSustitutosDirectos = $this->getSustitutosDirectos($idMaterial, $controlDireccionTipoSustituto = false, $mostrarBajas = false);

        //GENERO LA LISTA CON LOS MATERIALES A NO TENER EN CUENTA
        $listaMaterialesNoIncluir                      = "$idMaterial";
        $listaIdMaterialSustitutoRecursivosEncontrados = "0";
        foreach ($arrSustitutosDirectos as $idMaterialSustituto => $arrValores):
            $listaMaterialesNoIncluir                      = $listaMaterialesNoIncluir . "," . $idMaterialSustituto;
            $listaIdMaterialSustitutoRecursivosEncontrados = $listaIdMaterialSustitutoRecursivosEncontrados . "," . $arrValores[0]['ID_MATERIAL_SUSTITUTIVO'];
        endforeach;

        //LOCALIZO LOS SUSTITUTOS INDIRECTOS DEL MATERIAL
        foreach ($arrSustitutosDirectos as $idMaterialSustituto => $arrValores):
            //VARIABLE PARA EXTRAER LOS ID DE MATERIAL SUSTITUTIVO RECURSIVOS
            $arrSustitutosIndirectos = array_unique( (array)explode(",", (string)$this->getSustitutosRecursivosLista($idMaterialSustituto, $listaMaterialesNoIncluir, $mostrarBajas = false, $listaIdMaterialSustitutoRecursivosEncontrados)));

            $arrSustitutos = array_merge((array)$arrSustitutos,(array) $arrSustitutosIndirectos);
        endforeach;

        return array_unique( (array)$arrSustitutos);
    }

    /**
     * //Al cambiar el estado de bloqueo de un material
     * // => Si cambia de 'No bloqueado' a tener un valor => EMAIL A LOS COMPRADORES
     * // => Si cambia de estado (cualquier cambio) => EMAIL a destinatarios fijo (MATERIALES_EMAIL_DESTINATARIO_ESPECIFICOS + MATERIALES_EMAIL_DESTINATARIO_ESPECIFICOS_SIEMPRE)
     *
     * @param $idMaterial
     * @param $estadoBloqueo_old
     * @param $estadoBloqueo_new
     */
    function EnviarNotificacionEmail_MaterialCambioEstadoBloqueo($idMaterial, $estadoBloqueo_old, $estadoBloqueo_new)
    {
        global $bd;
        global $auxiliar;
        global $administrador;
        global $NotificaErrorPorEmail;

        //ESTOS SON LOS POSIBLES ESTADOS:
        //'No bloqueado';
        //'01-Bloqueo General';
        //'02-Obsoleto Fin Existencias (Error)';
        //'03-Código duplicado';
        //'04-Código inutilizable';
        //'05-Obsoleto Fin Existencias (Aviso)';

        //OBTENGO EL MATERIAL
        $rowMat = $bd->VerReg("MATERIAL", "ID_MATERIAL", $idMaterial, "No");
        if (!$rowMat) return;

        //ARRAY PARA GUARDAR LOS DESTINATARIOS DEL CORREO
        $arrCorreosEsp = array();
        $arrCorreosEng = array();

        // => Si cambia de 'No bloqueado' a tener un valor => EMAIL A LOS COMPRADORES
        //OBTENGO LOS COMPRADORES DEL MATERIAL
        if ($estadoBloqueo_old == 'No bloqueado') {
            $sqlCompradores    = "SELECT A.* FROM ADMINISTRADOR A
                            INNER JOIN PLANIFICADOR P ON A.USUARIO_SAP = P.USUARIO_SAP
                            INNER JOIN MATERIAL_ALMACEN MA ON MA.ID_PLANIFICADOR = P.ID_PLANIFICADOR
                            WHERE  MA.ID_MATERIAL = '" . $idMaterial . "'
                            GROUP BY A.ID_ADMINISTRADOR";
            $resultCompradores = $bd->ExecSQL($sqlCompradores);
            while ($rowComprador = $bd->SigReg($resultCompradores)) {
                if ($rowComprador->EMAIL != ""):
                    $rowComprador->IDIOMA_NOTIFICACIONES == "ESP" ? $arrCorreosEsp[] = $rowComprador->EMAIL : $arrCorreosEng[] = $rowComprador->EMAIL;
                endif;
            }
        }

        // => Si cambia de estado (cualquier cambio) => EMAIL a destinatarios fijo (MATERIALES_EMAIL_DESTINATARIO_ESPECIFICOS + MATERIALES_EMAIL_DESTINATARIO_ESPECIFICOS_SIEMPRE)

        $destinatariosCorreo = $auxiliar->correosMatrizPorID(MATERIALES_ID_CORREO);
        if ($destinatariosCorreo != ""):
            $arrCorreosEsp[] = $destinatariosCorreo;
        else:
            $arrCorreosEsp[] = MATERIALES_EMAIL_DESTINATARIO_ESPECIFICOS;
            $arrCorreosEsp[] = MATERIALES_EMAIL_DESTINATARIO_ESPECIFICOS_SIEMPRE;
        endif;
//        $arrCorreosEsp[] = MATERIALES_EMAIL_DESTINATARIO_ESPECIFICOS;
//        $arrCorreosEsp[] = MATERIALES_EMAIL_DESTINATARIO_ESPECIFICOS_SIEMPRE;

        //busco si el material es seriable. Tomaremos como que es seriable si lo es en alguno de los almacenes
        $seriable          = false;
        $sqlMatAlmSeriable = "SELECT ID_MATERIAL_ALMACEN FROM MATERIAL_ALMACEN WHERE ID_MATERIAL = '" . $rowMat->ID_MATERIAL . "' AND TIPO_LOTE = 'serie' AND BAJA = 0";
        $resMatAlmSeriable = $bd->ExecSQL($sqlMatAlmSeriable, "No");
        if ($bd->NumRegs($resMatAlmSeriable) > 0) {
            $seriable = true;
        }

        //busco el material sustituto
        $rowMaterialSustituto = false;
        $sqlMaterialSustituto = "SELECT M.* FROM MATERIAL  M
                                INNER JOIN MATERIAL_SUSTITUTIVO MS ON MS.ID_MATERIAL_SUSTITUTO = M.ID_MATERIAL
                                WHERE  MS.ID_MATERIAL = '" . $rowMat->ID_MATERIAL . "'
                                AND MS.TIPO = 'Sustituto'
                                LIMIT 1";
        $resMaterialSustituto = $bd->ExecSQL($sqlMaterialSustituto, "No");
        if ($bd->NumRegs($resMaterialSustituto) > 0) {
            $rowMaterialSustituto = $bd->SigReg($resMaterialSustituto);
        }

        //GENERO CONTENIDO DEL EMAIL ESP
        $Asunto = $auxiliar->traduce('El material', "ESP") . ' ' . ($rowMat->REFERENCIA_SGA) . ' ' . $auxiliar->traduce('ha cambiado su tipo de bloqueo a', "ESP") . ' ' . $auxiliar->traduce($estadoBloqueo_new, "ESP");
        $Cuerpo = '<br/>- ' . $auxiliar->traduce('Código artículo ', "ESP") . ': ' . ($rowMat->REFERENCIA_SGA);
        $Cuerpo .= '<br/>- ' . $auxiliar->traduce('Descripción artículo ', "ESP") . ': ' . $rowMat->DESCRIPCION;
        $Cuerpo .= '<br/>- ' . $auxiliar->traduce('Rotable ', "ESP") . ': ' . $auxiliar->traduce(($rowMat->ROTABLE == '1' ? 'Si' : 'No'), "ESP");
        $Cuerpo .= '<br/>- ' . $auxiliar->traduce('Seriable ', "ESP") . ': ' . $auxiliar->traduce(($seriable ? 'Si' : 'No'), "ESP");
        $Cuerpo .= '<br/>- ' . $auxiliar->traduce('Estado bloqueo', "ESP") . ': ' . $auxiliar->traduce($estadoBloqueo_new, "ESP");
        $Cuerpo .= '<br/>- ' . $auxiliar->traduce('Tipo de material ', "ESP") . ': ' . $auxiliar->traduce($rowMat->TIPO_MATERIAL, "ESP");
        //-Tecnología del material (eólica, biomasa…)
        //-Cuál es el material que lo sustituye
        $Cuerpo .= '<br/><br/>- ' . $auxiliar->traduce('Código vigente ', "ESP") . ': ';
        if ($rowMaterialSustituto) {
            $Cuerpo .= ($rowMaterialSustituto->REFERENCIA_SGA) . '; ' . $rowMaterialSustituto->DESCRIPCION . '; ' . $auxiliar->traduce($rowMaterialSustituto->ESTADO_BLOQUEO_MATERIAL, "ESP");
        } else {
            $Cuerpo .= $auxiliar->traduce('No encontrado', "ESP");
        }

        //PREPARO EL MAIL
        $correosEsp = '';
        if (MATERIALES_ENVIAR_EMAILS_DESTINATARIOS_REALES) {
            $arrCorreosEsp = array_unique((array) $arrCorreosEsp);
            $correosEsp = implode(',', (array) $arrCorreosEsp);
        }
        $auxiliar->enviarCorreoSistema($Asunto, $Cuerpo, MATERIALES_REMITENTE_EMAIL, MATERIALES_REMITENTE_NOMBRE, $correosEsp, MATERIALES_EMAIL_DESTINATARIO_TEST);

        //GENERO CONTENIDO DEL EMAIL ENG
        $Asunto = $auxiliar->traduce('El material', "ENG") . ' ' . ($rowMat->REFERENCIA_SGA) . ' ' . $auxiliar->traduce('ha cambiado su tipo de bloqueo a', "ENG") . ' ' . $auxiliar->traduce($estadoBloqueo_new, "ENG");
        $Cuerpo = '<br/>- ' . $auxiliar->traduce('Código artículo ', "ENG") . ': ' . ($rowMat->REFERENCIA_SGA);
        $Cuerpo .= '<br/>- ' . $auxiliar->traduce('Descripción artículo', "ENG") . ': ' . $rowMat->DESCRIPCION_EN;
        $Cuerpo .= '<br/>- ' . $auxiliar->traduce('Rotable ', "ENG") . ': ' . $auxiliar->traduce(($rowMat->ROTABLE == '1' ? 'Si' : 'No'), "ENG");
        $Cuerpo .= '<br/>- ' . $auxiliar->traduce('Seriable ', "ENG") . ': ' . $auxiliar->traduce(($seriable ? 'Si' : 'No'), "ENG");
        $Cuerpo .= '<br/>- ' . $auxiliar->traduce('Estado bloqueo', "ENG") . ': ' . $auxiliar->traduce($estadoBloqueo_new, "ENG");
        $Cuerpo .= '<br/>- ' . $auxiliar->traduce('Tipo de material ', "ENG") . ': ' . $auxiliar->traduce($rowMat->TIPO_MATERIAL, "ENG");
        //-Tecnología del material (eólica, biomasa…)
        //-Cuál es el material que lo sustituye
        $Cuerpo .= '<br/><br/>- ' . $auxiliar->traduce('Código vigente ', "ENG") . ': ';
        if ($rowMaterialSustituto) {
            $Cuerpo .= ($rowMaterialSustituto->REFERENCIA_SGA) . '; ' . $rowMaterialSustituto->DESCRIPCION_EN . '; ' . $auxiliar->traduce($rowMaterialSustituto->ESTADO_BLOQUEO_MATERIAL, "ENG");
        } else {
            $Cuerpo .= $auxiliar->traduce('No encontrado', "ENG");
        }

        //PREPARO EL MAIL
        $correosEng = '';
        if (MATERIAL_ALMACEN_ENVIAR_EMAILS_DESTINATARIOS_REALES) {
            $arrCorreosEng = array_unique((array) $arrCorreosEng);
            $correosEng = implode(',', (array) $arrCorreosEng);
        }
        $auxiliar->enviarCorreoSistema($Asunto, $Cuerpo, MATERIALES_REMITENTE_EMAIL, MATERIALES_REMITENTE_NOMBRE, $correosEng, MATERIALES_EMAIL_DESTINATARIO_TEST);
    }

    /**
     * @param $idMaterial
     * @param $idAlmacen
     * FUNCION PARA ENVIAR LOS MATERIAL ALMACEN NO DEFINIDOS EN EL MAESTRO
     */
    function EnviarNotificacionEmail_MaterialAlmacenNoDefinido($idMaterial, $idAlmacen)
    {
        global $bd;
        global $auxiliar;
        global $administrador;

        //OBTENGO EL MATERIAL
        $rowMat = $bd->VerReg("MATERIAL", "ID_MATERIAL", $idMaterial, "No");

        //OBTENGO EL ALMACEN
        $rowAlm = $bd->VerReg("ALMACEN", "ID_ALMACEN", $idAlmacen, "No");

        //OBTENGO EL CENTRO DEL ALMACEN
        $rowCentro = $bd->VerReg("CENTRO", "ID_CENTRO", $rowAlm->ID_CENTRO, "No");

        //SI EL ALMACEN ES DE TIPO ACCIONA Y EL CENTRO ES CON INTEGRACION CON SAP MANDO UN CORREO
        if (($rowAlm->TIPO_ALMACEN == 'acciona') && ($rowCentro->INTEGRACION_CON_SAP == 1)):

            //BUSCO EL MATERIAL-ALMACEN
            $rowMatAlm = $bd->VerRegRest("MATERIAL_ALMACEN", "ID_MATERIAL = $rowMat->ID_MATERIAL AND ID_ALMACEN = $rowAlm->ID_ALMACEN", "No");

            //ALMACENO LA DIRECCION DE LOS CORREOS
            //EXTRAER CORREOS DE LA MATRIZ DE CORREOS.
            $destinatariosCorreo = $auxiliar->correosMatrizPorID(EMAIL_MATERIAL_ALMACEN_NO_DEFINIDO_ID_CORREO);
            if ($destinatariosCorreo != ""):
                $arrCorreos[] = $destinatariosCorreo;
            else:
                $arrCorreos[] = EMAIL_MATERIAL_ALMACEN_NO_DEFINIDO;
            endif;
//            $arrCorreos[] = EMAIL_MATERIAL_ALMACEN_NO_DEFINIDO;

            //GENERO CONTENIDO DEL EMAIL
            $Asunto = $auxiliar->traduce("Dupla no definida", $administrador->ID_IDIOMA) . ". " . $auxiliar->traduce('Material', $administrador->ID_IDIOMA) . ': ' . $rowMat->REFERENCIA_SGA . " - " . $auxiliar->traduce('Almacen ', $administrador->ID_IDIOMA) . ': ' . $rowAlm->REFERENCIA;
            $Cuerpo = $auxiliar->traduce("El siguiente registro se ha creado en SGA. Deberia ser definido en SAP.", $administrador->ID_IDIOMA) . '<br/>';
            $Cuerpo .= '<strong>' . '<br/>' . $auxiliar->traduce('Material', $administrador->ID_IDIOMA) . '</strong>: ' . $rowMat->REFERENCIA_SGA . " - " . ($administrador->ID_IDIOMA == 'ENG' ? $rowMat->DESCRIPCION_EN : $rowMat->DESCRIPCION);
            $Cuerpo .= '<strong>' . '<br/>' . $auxiliar->traduce('Almacen ', $administrador->ID_IDIOMA) . '</strong>: ' . $rowAlm->REFERENCIA . " - " . $rowAlm->NOMBRE;
            $Cuerpo .= '<strong>' . '<br/>' . $auxiliar->traduce('Tipo Lote', $administrador->ID_IDIOMA) . '</strong>: ' . $auxiliar->traduce($rowMatAlm->TIPO_LOTE, $administrador->ID_IDIOMA);
            $Cuerpo .= '<strong>' . '<br/>' . $auxiliar->traduce('Estado Bloqueo', $administrador->ID_IDIOMA) . '</strong>: ' . $auxiliar->traduce($rowMatAlm->ESTADO_BLOQUEO_MATERIAL_ALMACEN, $administrador->ID_IDIOMA) . '<br/><br/>';
            $Cuerpo .= '<a href="' . MAESTRO_MATERIAL_ALMACEN_URL_ENLACE . 'ficha.php?idMaterialAlmacen=' . $rowMatAlm->ID_MATERIAL_ALMACEN . '"> ' . $auxiliar->traduce("Ver registro", $administrador->ID_IDIOMA) . ' </a>';

            //PREPARO EL MAIL
            $correos = '';
            if (MATERIAL_ALMACEN_ENVIAR_EMAILS_DESTINATARIOS_REALES) {
                $arrCorreos = array_unique((array) $arrCorreos);
                $correos = implode(',', (array) $arrCorreos);
            }
            $auxiliar->enviarCorreoSistema($Asunto, $Cuerpo, MATERIAL_ALMACEN_REMITENTE_EMAIL, MATERIAL_ALMACEN_REMITENTE_NOMBRE, $correos, EMAIL_MATERIAL_ALMACEN_NO_DEFINIDO_COPIA_OCULTA);
        endif;
        //FIN SI EL CENTRO ES CON INTEGRACION CON SAP MANDO UN CORREO
    }

    /**
     * @param $idMaterial
     * @param $idAlmacen
     * FUNCION QUE SIRVE PARA CLONAR EL MATERIAL-ALMACEN DE CUALQUIER CENTRO
     * DEVUELVE EL ID_MATERIAL_ALMACEN SI SE PUDO CLONAR, EN CASO CONTRARIO DEVUELVE FALSE
     */
    function ClonarMaterialAlmacen($idMaterial, $idAlmacen, $origen = "")
    {
        global $bd;
        global $auxiliar;
        global $administrador;

        //BUSCO SI ESTE MATERIAL ESTA DEFINIDO PARA OTRO ALMACEN EN ALGUN CENTRO
        $sqlMatAlmLinea    = "SELECT *
						   FROM MATERIAL_ALMACEN
						   WHERE BAJA = 0 AND ID_MATERIAL = $idMaterial AND ID_ALMACEN IN (SELECT ID_ALMACEN
						                                                                   FROM ALMACEN
						                                                                   WHERE TIPO_ALMACEN = 'acciona' AND BAJA = 0)";
        $resultMatAlmLinea = $bd->ExecSQL($sqlMatAlmLinea);
        if ($bd->NumRegs($resultMatAlmLinea) > 0):
            $rowMatAlm = $bd->SigReg($resultMatAlmLinea);

            //BUSCO EL ALMACEN SOBRE EL QUE CLONAR
            $rowAlmacenSobreElQueClonar = $bd->VerReg("ALMACEN", "ID_ALMACEN", $idAlmacen);
            $rowCentroSobreElQueClonar  = $bd->VerReg("CENTRO", "ID_CENTRO", $rowAlmacenSobreElQueClonar->ID_CENTRO);

            $sqlDatosAlmacenAdicionales = "";
            $idIncidenciaCodificacion = "";
            /*ESTADO SERA CORRECTO SI EL ALMACEN ES ACCIONA Y ESTA INTEGRADO CON SAP*/
            if (($rowAlmacenSobreElQueClonar->TIPO_ALMACEN == 'acciona') && ($rowCentroSobreElQueClonar->INTEGRACION_CON_SAP == 1)):
                $estadoMA = 'Pendiente de crear en SAP';

                //COMPROBAMOS SI EL MATERIAL NO ES SGA
                $rowMaterial = $bd->VerReg("MATERIAL", "ID_MATERIAL", $idMaterial);
                if($rowMaterial->MATERIAL_SGA != "1"):

                    //COMPRUEBO SI EXISTE LA DUPLA MATERIAL CENTRO
                    $sqlMaterialCentro = "SELECT * FROM MATERIAL_CENTRO WHERE ID_MATERIAL = " . $idMaterial . " AND ID_CENTRO = " . $rowCentroSobreElQueClonar->ID_CENTRO;
                    $resultMaterialCentro = $bd->ExecSQL($sqlMaterialCentro);
                    if($bd->NumRegs($resultMaterialCentro) == 0):
                        $txCoste = "";
                        //SI NO EXISTE, SE CREA (EN CASO DE QUE PODAMOS RECOGER EL COSTE)

                        //BUSCAMOS LA SOCIEDAD PARA ENCONTRAR LA MONEDA
                        $NotificaErrorPorEmail = "No";
                        $rowSociedad = $bd->VerReg("SOCIEDAD", "ID_SOCIEDAD", $rowCentroSobreElQueClonar->ID_SOCIEDAD, "No");
                        unset($NotificaErrorPorEmail);

                        //BUSCAMOS LA MONEDA
                        $NotificaErrorPorEmail = "No";
                        $rowMoneda = $bd->VerReg("MONEDA", "ID_MONEDA", $rowSociedad->ID_MONEDA, "No");
                        unset($NotificaErrorPorEmail);

                        $arrCentrosMoneda = array();
                        $arrAlmacenesMoneda = array();

                        $sqlAlmacenesMoneda = "SELECT A.ID_ALMACEN, A.ID_CENTRO
                                    FROM ALMACEN A
                                    INNER JOIN CENTRO C ON A.ID_CENTRO = C.ID_CENTRO
                                    INNER JOIN SOCIEDAD S ON S.ID_SOCIEDAD = C.ID_SOCIEDAD
                                    WHERE ID_MONEDA = " . $rowSociedad->ID_MONEDA;

                        $resAlmacenesMoneda = $bd->ExecSQL($sqlAlmacenesMoneda);
                        while($rowAlmacenMoneda = $bd->SigReg($resAlmacenesMoneda)):
                            $arrAlmacenesMoneda[] = $rowAlmacenMoneda->ID_ALMACEN;
                            $arrCentrosMoneda[]   = $rowAlmacenMoneda->ID_CENTRO;
                        endwhile;
                        //LIMPIAMOS LOS REGISTROS DUPLICADOS
                        $arrAlmacenesMoneda = array_filter($arrAlmacenesMoneda);
                        $arrAlmacenesMoneda = array_unique( (array)$arrAlmacenesMoneda);
                        $arrCentrosMoneda = array_filter($arrCentrosMoneda);
                        $arrCentrosMoneda = array_unique( (array)$arrCentrosMoneda);

                        //$txCoste = round(floatval(trim($coste)), 3);

                        $sqlMaterialCentroMoneda = "SELECT * FROM MATERIAL_CENTRO WHERE ID_MATERIAL = $rowMaterial->ID_MATERIAL AND ID_CENTRO IN (" . implode(',', (array) $arrCentrosMoneda) .") LIMIT 10";
                        $resMaterialCentroMoneda = $bd->ExecSQL($sqlMaterialCentroMoneda);
                        if($bd->NumRegs($resMaterialCentroMoneda) > 0):
                            $rowMaterialCentroMoneda = $bd->SigReg($resMaterialCentroMoneda);
                            $txCoste = $rowMaterialCentroMoneda->COSTE;
                        endif;

                        if($txCoste == ""):
                            $sqlMaterialAlmacenMoneda = "SELECT * FROM MATERIAL_ALMACEN WHERE ID_MATERIAL = $rowMaterial->ID_MATERIAL AND ID_ALMACEN IN (" . implode(',', (array) $arrAlmacenesMoneda) . ") LIMIT 10";
                            $resMaterialAlmacenMoneda = $bd->ExecSQL($sqlMaterialAlmacenMoneda);
                            if($bd->NumRegs($resMaterialAlmacenMoneda) > 0):
                                $rowMaterialAlmacenMoneda = $bd->SigReg($resMaterialAlmacenMoneda);
                                $txCoste = $rowMaterialAlmacenMoneda->COSTE_PMV;
                            endif;
                        endif;

                        if($txCoste != ""):
                            //SI PODEMOS ENCONTRAR EL COSTE A PARTIR DE OTRAS DUPLAS, BUSCAMOS EL RESTO DE VALORES DE MATERIAL CENTRO

                            //BUSCAMOS EL PLANIFICADOR QUE LE CORRESPONDA A ESE CENTRO, SEGUN LA FAMILIA REPRO O EL TIPO_MATERIAL
                            $NotificaErrorPorEmail = "No";
                            $rowPlanifCorrecto = $bd->VerRegRest("PLANIFICADOR_CENTRO", "ID_CENTRO = ". $rowCentroSobreElQueClonar->ID_CENTRO ." AND (ID_FAMILIA_REPRO = ". $rowMaterial->ID_FAMILIA_REPRO ." OR TIPO_MATERIAL = '". $rowMaterial->TIPO_MATERIAL ."') AND BAJA = 0", "No");
                            unset($NotificaErrorPorEmail);
                            if($rowPlanifCorrecto):
                                $NotificaErrorPorEmail = "No";
                                $rowPlanificador = $bd->VerReg("PLANIFICADOR", "ID_PLANIFICADOR", $rowPlanifCorrecto->ID_PLANIFICADOR, "No");
                                unset($NotificaErrorPorEmail);
                                //OBTENGO EL PLANIFICADOR EXACTO (EL DEFINIDO CON LA MISMA REFERENCIA QUE EL SELECCIONADO POR EL USUARIO, PERO PARA EL CENTRO DE LA DUPLA)
                                $NotificaErrorPorEmail = "No";
                                $rowPlanificador       = $bd->VerRegRest("PLANIFICADOR", "REFERENCIA = '" . $rowPlanificador->REFERENCIA . "' AND ID_CENTRO = '" . $rowCentroSobreElQueClonar->ID_CENTRO . "'", "No");
                                unset($NotificaErrorPorEmail);
                            endif;

                            $coste = floatval($txCoste);

                            //RECOGEMOS EL TIPO LOTE QUE HAYA EN EL RESTO DE CENTROS PARA ESE MATERIAL
                            $sqlCamposComunes = "SELECT TIPO_LOTE FROM MATERIAL_CENTRO WHERE ID_MATERIAL = '". $bd->escapeCondicional($idMaterial) ."'";
                            $resultCamposComunes = $bd->ExecSQL($sqlCamposComunes);
                            $rowCamposComunes = $bd->SigReg($resultCamposComunes);

                            //TIPO_LOTE
                            $tipoLote = "";
                            if($rowCamposComunes):
                                if($rowCamposComunes->TIPO_LOTE == ''):
                                    $tipoLote = 'ninguno';
                                else:
                                    $tipoLote = $rowCamposComunes->TIPO_LOTE;
                                endif;
                            else:
                                $tipoLote = 'ninguno';
                            endif;

                            //RECOGEMOS LA CATEGORIA VALORACION QUE LE CORRESPONDE
                            $tipoMaterial = $rowMaterial->TIPO_MATERIAL;

                            if($rowMaterial->TIPO_MATERIAL != 'Código I&C' && $rowMaterial->TIPO_MATERIAL != 'Servicios Acciona'):
                                $sqlValoracion = "SELECT * FROM VALORACION_TIPO_MATERIAL WHERE TIPO_MATERIAL = '". $bd->escapeCondicional($tipoMaterial) ."'";
                            else:
                                $sqlValoracion = "SELECT * FROM VALORACION_TIPO_MATERIAL WHERE TIPO_MATERIAL = '". $bd->escapeCondicional($tipoMaterial) ."' AND ID_FAMILIA_REPRO = '". $rowMaterial->ID_FAMILIA_REPRO ."'";
                            endif;
                            $resultValoracion = $bd->ExecSQL($sqlValoracion);
                            $rowValoracion = $bd->SigReg($resultValoracion);

                            $rowFamiliaRepro = $bd->VerReg("FAMILIA_REPRO", "ID_FAMILIA_REPRO", $rowMaterial->ID_FAMILIA_REPRO);

                            // INSERTO EL REGISTRO EN LA BD
                            $sql       = "INSERT INTO MATERIAL_CENTRO SET
                                            ID_MATERIAL ='" . $bd->escapeCondicional($idMaterial) . "'
                                            , ID_CENTRO ='" . $bd->escapeCondicional($rowCentroSobreElQueClonar->ID_CENTRO) . "'
                                            , TIPO_LOTE = '" . $bd->escapeCondicional($rowMatAlm->TIPO_LOTE) . "'
                                            , ESTADO_BLOQUEO_MATERIAL_CENTRO = '" . ($bd->escapeCondicional($rowMaterial->ESTADO_BLOQUEO_MATERIAL)) . "'
                                            , ID_GRUPO_COMPRA = " . ($rowFamiliaRepro->ID_GRUPO_COMPRA == NULL ? ($rowMaterial->ID_GRUPO_COMPRA == NULL ? 'NULL' : $rowMaterial->ID_GRUPO_COMPRA ): $rowFamiliaRepro->ID_GRUPO_COMPRA ) . "
                                            , CATEGORIA_VALORACION = '". ($rowValoracion == NULL ? '' : $rowValoracion->VALORACION) ."'
                                            , ID_UNIDAD_MEDIDA_PEDIDO = " . ($rowMaterial->ID_UNIDAD_COMPRA == NULL ? 'NULL' : $rowMaterial->ID_UNIDAD_COMPRA) . "
                                            , ID_UNIDAD_MEDIDA_VENTA = " . ($rowMaterial->ID_UNIDAD_COMPRA == NULL ? 'NULL' : $rowMaterial->ID_UNIDAD_COMPRA) . "
                                            , COSTE = '" . $coste . "'
                                            , COSTE_MONEDA_SOCIEDAD = '" . $coste . "'
                                            , ID_MONEDA_SOCIEDAD = " . $rowMoneda->ID_MONEDA . "
                                            , ID_PLANIFICADOR = " . ($rowPlanificador == NULL ? 'NULL' : $rowPlanificador->ID_PLANIFICADOR) . "
                                            , ORGANIZACION_VENTAS = '". ($rowCentroSobreElQueClonar->ORGANIZACION_VENTAS == NULL ? '' : $rowCentroSobreElQueClonar->ORGANIZACION_VENTAS) ."'
                                            , CANAL_DISTRIBUCION = '". ($rowCentroSobreElQueClonar->CANAL_DISTRIBUCION == NULL ? '' : $rowCentroSobreElQueClonar->CANAL_DISTRIBUCION) ."'
                                            , FECHA_CREACION = '" . date('Y-m-d H:i:s') . "'
                                            , ID_USUARIO_CREACION = '" . $administrador->ID_ADMINISTRADOR . "'
                                            , ID_USUARIO_ULTIMA_MODIFICACION = '" . $administrador->ID_ADMINISTRADOR . "'
                                            , CREADO_DESDE = '" . ($rowCentroSobreElQueClonar->INTEGRACION_CON_SAP == 1 ? 'SAP' : 'SGA') . "'";
                            $TipoError = "ErrorEjecutarSql";
                            $bd->ExecSQL($sql);

                            //OBTENGO ID CREADO
                            $idMaterialCentro = $bd->IdAsignado();

                            $rowMaterialCentroNuevo = $bd->VerReg("MATERIAL_CENTRO", "ID_MATERIAL_CENTRO", $bd->escapeCondicional($idMaterialCentro), "No");
                            $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, 'Creacion', "Material Centro", $idMaterialCentro, "Nuevo Material-Centro automáticamente", "MATERIAL_CENTRO", $rowMaterialCentroAntiguo, $rowMaterialCentroNuevo);

                            $rowMaterialCentro = $bd->VerReg("MATERIAL_CENTRO", "ID_MATERIAL_CENTRO", $idMaterialCentro);

                        else:
                            //INCIDENCIA DE CODIFICACION
                            $rowISTipo = $bd->VerReg("INCIDENCIA_SISTEMA_TIPO", "INCIDENCIA_SISTEMA_TIPO", "Codificación", "No");
                            $rowISSubTipo = $bd->VerReg("INCIDENCIA_SISTEMA_SUBTIPO", "INCIDENCIA_SISTEMA_SUBTIPO", "Pendiente crear material-centro y enviar a SAP", "No");

                            //BUSCAMOS SI YA EXISTE INCIDENCIA SISTEMA
                            $NotificaErrorPorEmail = "No";
                            $rowIncidenciaSistema             = $bd->VerRegRest("INCIDENCIA_SISTEMA", "ID_INCIDENCIA_SISTEMA_TIPO = $rowISTipo->ID_INCIDENCIA_SISTEMA_TIPO AND ID_INCIDENCIA_SISTEMA_SUBTIPO = $rowISSubTipo->ID_INCIDENCIA_SISTEMA_SUBTIPO AND ESTADO = 'Creada' AND TABLA_OBJETO = 'MATERIAL' AND ID_OBJETO = $rowMaterial->ID_MATERIAL", "No");
                            unset($NotificaErrorPorEmail);

                            if($rowIncidenciaSistema == false):
                                $sqlInsert = "INSERT INTO INCIDENCIA_SISTEMA SET
                                              ID_INCIDENCIA_SISTEMA_TIPO = $rowISTipo->ID_INCIDENCIA_SISTEMA_TIPO
                                              , TIPO = 'Codificación'
                                              , ID_INCIDENCIA_SISTEMA_SUBTIPO = $rowISSubTipo->ID_INCIDENCIA_SISTEMA_SUBTIPO
                                              , SUBTIPO = 'Pendiente crear material-centro y enviar a SAP'
                                              , ESTADO = 'Creada'
                                              , TABLA_OBJETO = 'MATERIAL'
                                              , ID_OBJETO = '$idMaterial'
                                              , FECHA_CREACION = '" . date("Y-m-d H:i:s") . "'
                                              , FECHA_RESOLUCION = '0000-00-00 00:00:00'
                                              , ID_ADMINISTRADOR_CREACION = $administrador->ID_ADMINISTRADOR
                                              , ID_ADMINISTRADOR_ULTIMA_MODIFICACION = $administrador->ID_ADMINISTRADOR
                                              , OBSERVACIONES = 'Es necesario crear crear la dupla material-centro para el centro $rowCentroSobreElQueClonar->REFERENCIA'";
                                $bd->ExecSQL($sqlInsert);
                                $idIncidenciaCodificacion = $bd->IdAsignado();
                                $sqlInsertObservacion = "INSERT INTO OBSERVACION_SISTEMA SET
                                                 TIPO_OBJETO = 'INCIDENCIA_SISTEMA'
                                                 ,ID_OBJETO = $idIncidenciaCodificacion
                                                 ,TEXTO_OBSERVACION = 'Es necesario crear la dupla material-centro para el centro $rowCentroSobreElQueClonar->REFERENCIA'
                                                 ,ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                                 ,FECHA = '" . date("Y-m-d H:i:s") . "'";
                                $bd->ExecSQL($sqlInsertObservacion);
                            else:
                                $idIncidenciaCodificacion = $rowIncidenciaSistema->ID_INCIDENCIA_SISTEMA;
                            endif;
                        endif;

                    else:
                        $rowMaterialCentro = $bd->SigReg($resultMaterialCentro);
                    endif;
                    //FIN COMPRUEBO SI EXISTE LA DUPLA MATERIAL CENTRO

                    //BUSCAMOS LOS DATOS ADICIONALES PARA LOS ALMACENES
                    //TAMANO LOTE MINIMO
                    $factorConversion = 1;
                    if (intval($rowMaterial->DENOMINADOR_CONVERSION) != 0):
                        $factorConversion = $rowMaterial->NUMERADOR_CONVERSION / $rowMaterial->DENOMINADOR_CONVERSION;
                    endif;
                    $tamanoLoteMinimo = $factorConversion;
                    $sqlDatosAlmacenAdicionales .= ", TAMANO_LOTE_MINIMO = " . $tamanoLoteMinimo;

                    //VALOR REDONDEO
                    $valorRedondeo = $factorConversion;
                    $sqlDatosAlmacenAdicionales .= ", VALOR_REDONDEO = ". $valorRedondeo;

                    //PLANIFICADOR
                    $idPlanificador = "";
                    $NotificaErrorPorEmail = "No";
                    $sqlPlanificadorCentro = "SELECT * FROM PLANIFICADOR_CENTRO WHERE ID_CENTRO = " . $rowCentroSobreElQueClonar->ID_CENTRO;
                    unset($NotificaErrorPorEmail);
                    $resPlanificadorCentro = $bd->ExecSQL($sqlPlanificadorCentro);
                    if ($bd->NumRegs($resPlanificadorCentro) > 0):
                        if ($rowCentroSobreElQueClonar->PLANIFICAR_POR_FAMILIA_REPRO == "1"):
                            $NotificaErrorPorEmail = "No";
                            $rowPlanificadorCentro = $bd->VerRegRest('PLANIFICADOR_CENTRO', "ID_CENTRO =  $rowCentroSobreElQueClonar->ID_CENTRO  AND ID_FAMILIA_REPRO = $rowMaterial->ID_FAMILIA_REPRO", "No");
                            unset($NotificaErrorPorEmail);
                        else:
                            $NotificaErrorPorEmail = "No";
                            $rowPlanificadorCentro = $bd->VerRegRest("PLANIFICADOR_CENTRO", "ID_CENTRO = $rowCentroSobreElQueClonar->ID_CENTRO AND TIPO_MATERIAL = '" .$rowMaterial->TIPO_MATERIAL . "'", "No");
                            unset($NotificaErrorPorEmail);
                        endif;
                        $idPlanificador = $rowPlanificadorCentro->ID_PLANIFICADOR;
                    endif;
                    if($idPlanificador != ""):
                        $sqlDatosAlmacenAdicionales .= ", ID_PLANIFICADOR = " . $idPlanificador . "";
                    endif;

                    //CLAVE APROVISIONAMIENTO ESPECIAL
                    $idClaveAprovisionamientoEspecial = "";
                    $NotificaErrorPorEmail = "No";
                    $rowFamiliaMaterial = $bd->VerReg("FAMILIA_MATERIAL", "ID_FAMILIA_MATERIAL", $rowMaterial->ID_FAMILIA_MATERIAL, "No");
                    unset($NotificaErrorPorEmail);
                    if ($rowFamiliaMaterial->ES_FAMILIA_ESPECIAL):
                        $NotificaErrorPorEmail = "No";
                        $rowClaveAprovisionamientoAlmacen = $bd->VerRegRest("CLAVE_APROVISIONAMIENTO_ESPECIAL_ALMACEN", "ID_ALMACEN = $rowAlmacenSobreElQueClonar->ID_ALMACEN AND ES_FAMILIA_ESPECIAL = $rowFamiliaMaterial->ES_FAMILIA_ESPECIAL  AND BAJA = 0", "No");
                        unset($NotificaErrorPorEmail);
                        if ($rowClaveAprovisionamientoAlmacen):
                            $rowClaveAprovisionamiento = $bd->VerReg("CLAVE_APROVISIONAMIENTO_ESPECIAL","ID_CLAVE_APROVISIONAMIENTO_ESPECIAL",$rowClaveAprovisionamientoAlmacen->ID_CLAVE_APROVISIONAMIENTO_ESPECIAL);
                            $idClaveAprovisionamientoEspecial = $rowClaveAprovisionamiento->ID_CLAVE_APROVISIONAMIENTO_ESPECIAL;
                        endif;
                    else:
                        $NotificaErrorPorEmail = "No";
                        $rowClaveAprovisionamientoAlmacen = $bd->VerRegRest("CLAVE_APROVISIONAMIENTO_ESPECIAL_ALMACEN", "ID_ALMACEN = $rowAlmacenSobreElQueClonar->ID_ALMACEN AND ES_REPARABLE = $rowMaterial->REPARABLE AND BAJA = 0", "No");
                        unset($NotificaErrorPorEmail);
                        if ($rowClaveAprovisionamientoAlmacen):
                            $rowClaveAprovisionamiento = $bd->VerReg("CLAVE_APROVISIONAMIENTO_ESPECIAL","ID_CLAVE_APROVISIONAMIENTO_ESPECIAL",$rowClaveAprovisionamientoAlmacen->ID_CLAVE_APROVISIONAMIENTO_ESPECIAL);
                            $idClaveAprovisionamientoEspecial = $rowClaveAprovisionamiento->ID_CLAVE_APROVISIONAMIENTO_ESPECIAL;
                        endif;
                    endif;
                    if($idClaveAprovisionamientoEspecial != ""):
                        $sqlDatosAlmacenAdicionales .= ", ID_CLAVE_APROVISIONAMIENTO_ESPECIAL = " . ($idClaveAprovisionamientoEspecial == "" ? 'NULL' : $idClaveAprovisionamientoEspecial);
                    endif;

                    //SI TIENE DUPLA MATERIAL CENTRO, BUSCAMOS EL CODIGO HS PARA QUE SEA EL MISMO EN MATERIAL ALMACEN
                    if($rowMaterialCentro):
                        $rowCodigoHS = false;
                        if ($rowMaterialCentro->ID_CODIGO_HS != NULL):
                            $NotificaErrorPorEmail = "No";
                            $rowCodigoHS = $bd->VerReg("CODIGO_HS", "ID_CODIGO_HS", $rowMaterialCentro->ID_CODIGO_HS, "No");
                            unset($NotificaErrorPorEmail);
                        endif;
                        $sqlDatosAlmacenAdicionales .= ", CODIGO_HS = '" . $bd->escapeCondicional($rowCodigoHS->CODIGO_HS) . "'";
                    endif;

                    //AREA CARACTERISTICAS
                    $areaCaracteristicas = "";
                    $NotificaErrorPorEmail = "No";
                    if ($bd->VerReg("ALMACEN_TIPO_MRP", "ID_ALMACEN", $rowAlmacenSobreElQueClonar->ID_ALMACEN, "No")):
                        if ($rowMaterial->ESTADO_BLOQUEO_MATERIAL != "No bloqueado"):
                            $NotificaErrorPorEmail = "No";
                            $rowMRPTipo = $bd->VerRegRest("ALMACEN_TIPO_MRP", "ID_ALMACEN = $rowAlmacenSobreElQueClonar->ID_ALMACEN AND STATUS_BLOQUEADO = 1", "No");
                            unset($NotificaErrorPorEmail);
                        else:
                            $NotificaErrorPorEmail = "No";
                            $rowMRPTipo = $bd->VerRegRest("ALMACEN_TIPO_MRP", "ID_ALMACEN = $rowAlmacenSobreElQueClonar->ID_ALMACEN AND TIPO_MATERIAL = '". $rowMaterial->TIPO_MATERIAL ."'", "No");
                            unset($NotificaErrorPorEmail);
                        endif;
                        $areaCaracteristicas = $rowMRPTipo->VALOR;
                    endif;
                    if($areaCaracteristicas != ""):
                        $sqlDatosAlmacenAdicionales .= ", AREA_CARACTERISTICAS = '" . $bd->escapeCondicional($areaCaracteristicas) . "'";
                    endif;

                    //TAMANO LOTE
                    $NotificaErrorPorEmail = "No";
                    $rowFamiliaMaterial = $bd->VerReg("FAMILIA_MATERIAL", "ID_FAMILIA_MATERIAL", $rowMaterial->ID_FAMILIA_MATERIAL, "No");
                    unset($NotificaErrorPorEmail);
                    $tamanoLote = "";
                    $NotificaErrorPorEmail = "No";
                    if ($rowFamiliaMaterial->ES_FAMILIA_ESPECIAL):
                        $rowMedidaLote = $bd->VerRegRest("TAMANO_LOTE_ALMACEN", "ID_ALMACEN = $rowAlmacenSobreElQueClonar->ID_ALMACEN AND ES_FAMILIA_ESPECIAL = 1 AND BAJA = 0", "No");
                        $tamanoLote = $rowMedidaLote->VALOR;
                    else:
                        $rowMedidaLote = $bd->VerRegRest("TAMANO_LOTE_ALMACEN", "ID_ALMACEN = $rowAlmacenSobreElQueClonar->ID_ALMACEN AND ES_FAMILIA_ESPECIAL = 0 AND BAJA = 0", "No");
                        $tamanoLote = $rowMedidaLote->VALOR;
                    endif;
                    unset($NotificaErrorPorEmail);
                    if($tamanoLote != ""):
                        $sqlDatosAlmacenAdicionales .= ", TAMANO_LOTE = '" . $bd->escapeCondicional($tamanoLote) . "'";
                    endif;

                    //FRECUENCIA
                    $frecuencia = "";
                    $NotificaErrorPorEmail = "No";
                    $rowFrecuenciaAprovisionamiento = $bd->VerRegRest("FRECUENCIA_APROVISIONAMIENTO_ALMACEN","ID_ALMACEN = $rowAlmacenSobreElQueClonar->ID_ALMACEN AND ES_FAMILIA_ESPECIAL = " .($rowFamiliaMaterial->ES_FAMILIA_ESPECIAL == '1' ? 1 : 0) . " AND BAJA = 0","No");
                    unset($NotificaErrorPorEmail);
                    if($rowFrecuenciaAprovisionamiento):
                        $frecuencia = $rowFrecuenciaAprovisionamiento->VALOR;
                    endif;
                    if($frecuencia != ""):
                        $sqlDatosAlmacenAdicionales .= ", FRECUENCIA = " . $frecuencia;
                    endif;

                    //LEAD TIME
                    $rowTiempoAprovisionamiento = $bd->VerRegRest("TIEMPO_APROVISIONAMIENTO_ALMACEN","ID_ALMACEN = $rowAlmacenSobreElQueClonar->ID_ALMACEN AND ES_FAMILIA_ESPECIAL = $rowFamiliaMaterial->ES_FAMILIA_ESPECIAL AND BAJA = 0","No");

                    //SE ACTUALIZA EL LEAD TIME COMPRA SI EL CAE ESTA VACIO.
                    if($idClaveAprovisionamientoEspecial == ""):
                        $sqlDatosAlmacenAdicionales .= ", LEAD_TIME_COMPRA = '" .$bd->escapeCondicional($rowTiempoAprovisionamiento->VALOR)."'";
                        $sqlDatosAlmacenAdicionales .= ", LEAD_TIME_COMPRA_SCS = '" .$bd->escapeCondicional($rowTiempoAprovisionamiento->VALOR)."'";

                    //SE ACTUALIZA EL LEAD TIME TRASLADO SI EL CAE NO ESTA VACIO
                    elseif($idClaveAprovisionamientoEspecial != ""):
                        $sqlDatosAlmacenAdicionales .= ", LEAD_TIME_TRASLADO = '".$bd->escapeCondicional($rowTiempoAprovisionamiento->VALOR)."'";
                        $sqlDatosAlmacenAdicionales .= ", LEAD_TIME_TRASLADO_SCS = '".$bd->escapeCondicional($rowTiempoAprovisionamiento->VALOR)."'";
                    endif;
                endif;
            else:
                $estadoMA = 'Correcto';
            endif;

            //BUSCAMOS SI EL REGISTRO YA ESTA CREADO DADO DE BAJA, PARA HACER UPDATE EN VEZ DE INSERT
            $GLOBALS["NotificaErrorPorEmail"] = "No";
            $rowMaterialAlmacenBaja           = $bd->VerRegRest("MATERIAL_ALMACEN", "ID_MATERIAL = $idMaterial AND ID_ALMACEN = $idAlmacen AND BAJA = 1", "No");
            unset($GLOBALS["NotificaErrorPorEmail"]);

            //SQL A EJECUTAR
            $sqlEjecutar = "INSERT INTO MATERIAL_ALMACEN SET
                                 ID_MATERIAL = $idMaterial
                                , ID_ALMACEN = $idAlmacen
                                , TIPO_LOTE = '" . $rowMatAlm->TIPO_LOTE . "'
                                , ESTADO_BLOQUEO_MATERIAL_ALMACEN = '" . $rowMatAlm->ESTADO_BLOQUEO_MATERIAL_ALMACEN . "'
                                , BAJA = $rowMatAlm->BAJA
                                , FECHA_CREACION = '" . date('Y-m-d H:i:s') . "'
                                , ID_USUARIO_CREACION = '" . $administrador->ID_ADMINISTRADOR . "'
                                , ID_USUARIO_ULTIMA_MODIFICACION = '" . $administrador->ID_ADMINISTRADOR . "'
                                , PUNTO_REORDEN = 0
                                , ESTADO = '" . $estadoMA . "'
                                , HA_ESTADO_PENDIENTE_CREAR_EN_SAP = " . ($rowCentroSobreElQueClonar->INTEGRACION_CON_SAP == 1 ? 1 : 0) . "
                                , CREADO_DESDE = '" . ($rowCentroSobreElQueClonar->INTEGRACION_CON_SAP == 1 ? 'SAP' : 'SGA') . "'
                                $sqlDatosAlmacenAdicionales " .
                ($estadoMA == "Correcto" ? "" : ", ORIGEN_INCIDENCIA = '" . $bd->escapeCondicional($origen) . "'") . "
                            ON DUPLICATE KEY UPDATE
                                 BAJA = 0
                                , FECHA_CREACION = '" . date('Y-m-d H:i:s') . "'
                                , ID_USUARIO_CREACION = '" . $administrador->ID_ADMINISTRADOR . "'
                                , ID_USUARIO_ULTIMA_MODIFICACION = '" . $administrador->ID_ADMINISTRADOR . "'
                                , ESTADO = '" . $estadoMA . "'
                                , HA_ESTADO_PENDIENTE_CREAR_EN_SAP = " . ($rowCentroSobreElQueClonar->INTEGRACION_CON_SAP == 1 ? 1 : 0) . "
                                , CREADO_DESDE = '" . ($rowCentroSobreElQueClonar->INTEGRACION_CON_SAP == 1 ? 'SAP' : 'SGA') . "'
                                $sqlDatosAlmacenAdicionales " .
                ($estadoMA == "Correcto" ? "" : ", ORIGEN_INCIDENCIA = '" . $bd->escapeCondicional($origen) . "'");
            //EJECUTO LA SQL
            $bd->ExecSQL($sqlEjecutar);

            //BUSCAMOS EL REGISTRO CREADO/ACTUALIZADO
            $rowMaterialAlmacen               = $bd->VerRegRest("MATERIAL_ALMACEN", "ID_MATERIAL = $idMaterial AND ID_ALMACEN = $idAlmacen", "No");

            //SE CALCULA Y ACTUALIZA EL LEAD TIME SUMINISTRO DEL ALMACEN
            $leadTimeSuministro = $rowMaterialAlmacen->LEAD_TIME_COMPRA_SCS == "" ? $rowMaterialAlmacen->LEAD_TIME_TRASLADO_SCS : $rowMaterialAlmacen->LEAD_TIME_COMPRA_SCS;
            $mat = new material();
            $leadTimeSuministro = $mat->calcularLeadTimeSuministro($rowMaterialAlmacen->ID_MATERIAL_ALMACEN, $leadTimeSuministro);
            $sql       = "UPDATE MATERIAL_ALMACEN SET
                    LEAD_TIME_SUMINISTRO =  " . ($leadTimeSuministro == 0 || $leadTimeSuministro == '' ? 'NULL' : $leadTimeSuministro) . "
                        , ID_USUARIO_ULTIMA_MODIFICACION = '" . $administrador->ID_ADMINISTRADOR . "'
                    WHERE ID_MATERIAL_ALMACEN = $rowMaterialAlmacen->ID_MATERIAL_ALMACEN";
            $bd->ExecSQL($sql);
            $rowMaterialAlmacen               = $bd->VerRegRest("MATERIAL_ALMACEN", "ID_MATERIAL = $idMaterial AND ID_ALMACEN = $idAlmacen", "No");

            $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, 'Creacion', "Material Almacen", $rowMaterialAlmacen->ID_MATERIAL_ALMACEN, "Nuevo Material-Almacen automáticamente", "MATERIAL_ALMACEN", $rowMaterialAlmacenAntiguo, $rowMaterialAlmacen);

            //GUARDAMOS EL ID
            $idMatAlm = $rowMaterialAlmacen->ID_MATERIAL_ALMACEN;

            //GENERAMOS MATERIAL CENTRO FISICO SI NO EXISTE
            $this->GenerarMaterialCentroFisico($idMaterial,$rowAlmacenSobreElQueClonar->ID_CENTRO_FISICO);

            //SI EL MATERIAL NO ES SGA, EL CENTRO TIENE INTEGRACION CON SAP Y NO HAY INCIDENCIA DE CODIFICACION, ENVIAMOS A SAP
            if (($rowAlmacenSobreElQueClonar->TIPO_ALMACEN == 'acciona') && ($rowCentroSobreElQueClonar->INTEGRACION_CON_SAP == 1) && $rowMaterial->MATERIAL_SGA != 1 && $idIncidenciaCodificacion == ''):
                $NotificaErrorPorEmail = "No";
                $rowCola = $bd->VerRegRest("COLA_INTERFACES_CODIFICACION", "ID_MATERIAL = $idMaterial AND TIPO = 'Edicion material' AND BAJA = 0 AND ENVIADO_A_SAP = 0", "No");
                if ($rowCola == false):
                    $sqlInsertCola = "INSERT INTO COLA_INTERFACES_CODIFICACION SET
                              ID_MATERIAL = $idMaterial
                              , TIPO = 'Edicion material'
                              ";
                    $bd->ExecSQL($sqlInsertCola);
                endif;
            endif;


//            if ($rowMaterialAlmacenBaja):
//                //HACEMOS EL UPDATE
//                $sqlUpdate = "UPDATE MATERIAL_ALMACEN SET
//                                  BAJA = 0
//                                , FECHA_CREACION = '" . date("Y-m-d H:i:s") . "'
//                                , ESTADO = '" . $estadoMA . "'
//                                , HA_ESTADO_PENDIENTE_CREAR_EN_SAP = " . ($rowCentroSobreElQueClonar->INTEGRACION_CON_SAP == 1 ? 1 : 0) . "
//                                , CREADO_DESDE = '" . ($rowCentroSobreElQueClonar->INTEGRACION_CON_SAP == 1 ? 'SAP' : 'SGA') . "'" .
//                    ($estadoMA == "Correcto" ? "" : ", ORIGEN_INCIDENCIA = '" . $bd->escapeCondicional($origen) . "'") . "
//                                WHERE ID_MATERIAL_ALMACEN = $rowMaterialAlmacenBaja->ID_MATERIAL_ALMACEN";
//                $bd->ExecSQL($sqlUpdate);
//
//                //GUARDAMOS EL ID
//                $idMatAlm = $rowMaterialAlmacenBaja->ID_MATERIAL_ALMACEN;
//
//            else:
//                //GENERAMOS UNA COPIA DEL MATERIAL_ALMACEN ENCONTRADO
//                $sqlInsert = "INSERT INTO MATERIAL_ALMACEN SET
//                                ID_MATERIAL = $idMaterial
//                                , ID_ALMACEN = $idAlmacen
//                                , TIPO_LOTE = '" . $rowMatAlm->TIPO_LOTE . "'
//                                , ESTADO_BLOQUEO_MATERIAL_ALMACEN = '" . $rowMatAlm->ESTADO_BLOQUEO_MATERIAL_ALMACEN . "'
//                                , BAJA = $rowMatAlm->BAJA
//                                , PUNTO_REORDEN = 0
//                                , ESTADO = '" . $estadoMA . "'
//                                , HA_ESTADO_PENDIENTE_CREAR_EN_SAP = " . ($rowCentroSobreElQueClonar->INTEGRACION_CON_SAP == 1 ? 1 : 0) . "
//                                , CREADO_DESDE = '" . ($rowCentroSobreElQueClonar->INTEGRACION_CON_SAP == 1 ? 'SAP' : 'SGA') . "'" .
//                    ($estadoMA == "Correcto" ? "" : ", ORIGEN_INCIDENCIA = '" . $bd->escapeCondicional($origen) . "'");
//                //, PENDIENTE_DE_CREAR_EN_SAP = " . ($rowAlmacenSobreElQueClonar->TIPO_ALMACEN == 'acciona'?1:0);//PUNTO_REORDEN POR DEFECTO CERO
//                $bd->ExecSQL($sqlInsert);
//                $idMatAlm = $bd->IdAsignado();
//            endif;

            return $idMatAlm;
        else:
            return false;
        endif;
    }

    /**
     * @param $idMaterial
     * @param $idCentroFisico
     * FUNCION QUE SIRVE PARA COMPROBAR SI EXISTE UN MCF Y SI NO, GENERARLO
     * DEVUELVE EL ID_MATERIAL_CENTRO_FISICO
     */
    function GenerarMaterialCentroFisico($idMaterial, $idCentroFisico)
    {
        global $bd;
        global $auxiliar;
        global $administrador;

        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowMaterialCentroFisico          = $bd->VerRegRest("MATERIAL_CENTRO_FISICO", "ID_MATERIAL = $idMaterial AND ID_CENTRO_FISICO = $idCentroFisico", "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);

        if ($rowMaterialCentroFisico == false):
            // INSERTO EL REGISTRO EN LA BD
            $sql       = "INSERT INTO MATERIAL_CENTRO_FISICO SET
                            ID_MATERIAL ='" . $bd->escapeCondicional($idMaterial) . "'
                            , ID_CENTRO_FISICO ='" . $bd->escapeCondicional($idCentroFisico) . "'
                            , FECHA_CREACION = '" . date('Y-m-d H:i:s') . "'
                            , ID_USUARIO_CREACION = '" . $administrador->ID_ADMINISTRADOR . "'
                            , ID_USUARIO_ULTIMA_MODIFICACION = '" . $administrador->ID_ADMINISTRADOR . "'
                            , UBICACION_FIJA = '0'
                            , SUSCEPTIBLE_AUTOSTORE = '0'
                            , TIPO_HUECO_AUTOSTORE = '-'
                            , CANTIDAD_HUECO_AUTOSTORE = '0'
                            , BAJA = '0'";
            $bd->ExecSQL($sql);

            //OBTENGO ID CREADO
            $idMaterialCentroFisico = $bd->IdAsignado();

            $rowMaterialCentroFisicoNuevo = $bd->VerReg("MATERIAL_CENTRO_FISICO", "ID_MATERIAL_CENTRO_FISICO", $bd->escapeCondicional($idMaterialCentroFisico), "No");
            $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, 'Creacion', "Material Centro Fisico", $idMaterialCentroFisico, "Nuevo Material-Centro Fisico Autom.", "MATERIAL_CENTRO_FISICO", $rowMaterialCentroFisico, $rowMaterialCentroFisicoNuevo);

        else:
            $idMaterialCentroFisico = $rowMaterialCentroFisico->ID_MATERIAL_CENTRO_FISICO;
        endif;

        return $idMaterialCentroFisico;
    }

    /**
     * @param datos material origen y destino, $idMovimientoEntradaLineaOrigen si se origina a partir de un movimiento entrada
     * FUNCION QUE SIRVE PARA CREAR UN CAMBIO REFERENCIA PENDIENTE TRANSFERIR A SAP
     * DEVUELVE EL idCambioReferencia SI SE HA PODIDO REALIZAR; FALSE EN OTRO CASO
     */
    function generarCambioReferenciaPendiente($idMaterialOrigen, $idMaterialDestino, $cantidad, $idUbicacion, $idMaterialFisicoOrigen = "", $idTipoBloqueo = "", $idOrdenTrabajoMovimiento = "", $idIncidenciaCalidad = "", $idMovimientoEntradaLineaOrigen = "")
    {
        global $bd;
        global $auxiliar;
        global $administrador;

        //PARA DEVOLVER
        $arrRespuesta         = array();
        $arrRespuesta['OK']   = false;
        $arrCambiosReferencia = array();

        //OBTENEMOS MATERIAL ORIGEN
        $rowMaterialOrigen = $bd->VerReg("MATERIAL", "ID_MATERIAL", $idMaterialOrigen);

        //OBTENEMOS MATERIAL DESTINO
        $rowMaterialDestino = $bd->VerReg("MATERIAL", "ID_MATERIAL", $idMaterialDestino);

        //OBTENEMOS UBICACION
        $rowUbicacion = $bd->VerReg("UBICACION", "ID_UBICACION", $idUbicacion);

        //OBTENEMOS MATERIAL FISICO
        $rowMaterialFisicoOrigen = false;
        $tipoLoteOrigen          = "ninguno";
        if ($idMaterialFisicoOrigen != ""):
            $rowMaterialFisicoOrigen = $bd->VerReg("MATERIAL_FISICO", "ID_MATERIAL_FISICO", $idMaterialFisicoOrigen);
            $tipoLoteOrigen          = $rowMaterialFisicoOrigen->TIPO_LOTE;
        endif;

        //SI ES SERIABLE COMPROBAMOS CANTIDAD 1
        if ($tipoLoteOrigen == "serie" && $cantidad != 1):
            return $arrRespuesta;
        endif;

        //COMPROBAMOS CANTIDAD MAYOR QUE 0
        if ($cantidad <= 0):
            return $arrRespuesta;
        endif;

        //BUSCO EL MATERIAL ALMACEN DEL MATERIAL DESTINO
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowMatAlmDestino                 = $bd->VerRegRest("MATERIAL_ALMACEN", "ID_MATERIAL = $rowMaterialDestino->ID_MATERIAL AND ID_ALMACEN = $rowUbicacion->ID_ALMACEN", "No");
        if ($rowMatAlmDestino == false):
            //GENERAMOS UNA COPIA DEL MATERIAL_ALMACEN ENCONTRADO
            $idMatAlmDest = $this->ClonarMaterialAlmacen($rowMaterialDestino->ID_MATERIAL, $rowUbicacion->ID_ALMACEN, "Cambio Codigo");
            if ($idMatAlmDest != false):
                //INFORMO A LAS PERSONAS CORRESPONDIENTES DE LAS DUPLAS MATERIAL-ALMACEN RECIEN CREADAS
                $this->EnviarNotificacionEmail_MaterialAlmacenNoDefinido($rowMaterialDestino->ID_MATERIAL, $rowUbicacion->ID_ALMACEN);
                //RECUPERO EL OBJETO CREADO
                $rowMatAlmDestino = $bd->VerReg("MATERIAL_ALMACEN", "ID_MATERIAL_ALMACEN", $idMatAlmDest);
            endif;
        endif;

        //SI NO HEMOS ENCONTRADO MATERIAL
        if ($rowMatAlmDestino == false):
            return $arrRespuesta;
        elseif ($rowMatAlmDestino->TIPO_LOTE == "serie"):
            //SI ES SERIABLE COMPROBAMOS QUE LA CANTIDAD ES ENTERA
            if (intval(0 + $cantidad) != $cantidad):
                return $arrRespuesta;
            endif;
        endif;

        $txFecha = date("Y-m-d H:i:s");

        //GENERO EL CAMBIO DE REFERENCIA GRUPO
        $sqlInsert = "INSERT INTO CAMBIO_REFERENCIA_GRUPO SET
                                ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                , ESTADO = 'Pdte Transmitir a SAP'
                                , FECHA = '" . $txFecha . "'
                                , OBSERVACIONES = ''";
        $bd->ExecSQL($sqlInsert);
        $idCambioReferenciaGrupo = $bd->IdAsignado();

        //ASIGNAMOS EL NOMBRE
        $sqlUpdate = "UPDATE CAMBIO_REFERENCIA_GRUPO SET NOMBRE='CR" . $idCambioReferenciaGrupo . "' WHERE ID_CAMBIO_REFERENCIA_GRUPO=$idCambioReferenciaGrupo";
        $bd->ExecSQL($sqlUpdate);


        //SI ES SERIABLE O LOTABLE
        if ($rowMatAlmDestino->TIPO_LOTE == "serie"):

            for ($cont = 0; $cont < $cantidad; $cont++):
                //SI COINCIDEN EN TIPO LOTE, USAREMOS EL MISMO Nº, SI NO GENERAREMOS UNO
                if ($tipoLoteOrigen == $rowMatAlmDestino->TIPO_LOTE):

                    //COGEMOS EL DE ORIGEN
                    $numSerieLoteDestino = $rowMaterialFisicoOrigen->NUMERO_SERIE_LOTE;
                else:
                    //BUSCO EL NUMERO DE LOTE QUE LE CORRESPONDA
                    $rowLote     = $bd->VerRegRest("CONFIG_GRAL", "1=1");
                    $incremental = $rowLote->ULTIMO_SERIE_UTILIZADO_CR + 1;

                    //ACTUALIZO EL ULTIMO NUMERO DE LOTE UTILIZADO
                    $sqlUpdate = "UPDATE CONFIG_GRAL SET ULTIMO_SERIE_UTILIZADO_CR = ULTIMO_SERIE_UTILIZADO_CR + 1";
                    $bd->ExecSQL($sqlUpdate);

                    $numSerieLoteDestino = date("Ymd") . str_pad( (string)$incremental, 6, "0", STR_PAD_LEFT);
                endif;

                //BUSCO EL MATERIAL FISICO
                $GLOBALS["NotificaErrorPorEmail"] = "No";
                $rowMaterialFisicoDestino         = $bd->VerRegRest("MATERIAL_FISICO", "ID_MATERIAL = $rowMaterialDestino->ID_MATERIAL AND TIPO_LOTE = '" . $rowMatAlmDestino->TIPO_LOTE . "' AND NUMERO_SERIE_LOTE = '" . $numSerieLoteDestino . "'", "No");
                if ($rowMaterialFisicoDestino == false):
                    $sqlInsert = "INSERT INTO MATERIAL_FISICO SET
                                        ID_MATERIAL = $rowMaterialDestino->ID_MATERIAL
                                        , TIPO_LOTE = '" . $rowMatAlmDestino->TIPO_LOTE . "'
                                        , NUMERO_SERIE_LOTE = '" . $bd->escapeCondicional($numSerieLoteDestino) . "'";
                    $bd->ExecSQL($sqlInsert);
                    $idMaterialFisicoDestino = $bd->IdAsignado();
                else:
                    $idMaterialFisicoDestino = $rowMaterialFisicoDestino->ID_MATERIAL_FISICO;
                endif;

                //CREO EL CAMBIO_REFERENCIA
                $sqlInsert = "INSERT INTO CAMBIO_REFERENCIA SET
                                ID_CAMBIO_REFERENCIA_GRUPO = $idCambioReferenciaGrupo
                                , ENVIADO_SAP = 0
                                , ID_MATERIAL_ORIGEN = $rowMaterialOrigen->ID_MATERIAL
                                , ID_MATERIAL_DESTINO = $rowMaterialDestino->ID_MATERIAL
                                , ID_UBICACION = $rowUbicacion->ID_UBICACION
                                , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                , FECHA_CREACION = '" . $txFecha . "'
                                , CANTIDAD = 1
                                , ID_MOVIMIENTO_ENTRADA_LINEA_ORIGINAL = " . ($idMovimientoEntradaLineaOrigen != "" ? $idMovimientoEntradaLineaOrigen : "NULL") . "
                                , ID_MATERIAL_FISICO_ORIGEN = " . ($rowMaterialFisicoOrigen->ID_MATERIAL_FISICO == false ? 'NULL' : "$rowMaterialFisicoOrigen->ID_MATERIAL_FISICO") . "
                                , ID_MATERIAL_FISICO_DESTINO = " . ($idMaterialFisicoDestino == "" ? 'NULL' : "$idMaterialFisicoDestino") . "
                                , ID_TIPO_BLOQUEO = " . ($idTipoBloqueo == "" ? 'NULL' : "$idTipoBloqueo") . "
                                , ID_ORDEN_TRABAJO_MOVIMIENTO = " . ($idOrdenTrabajoMovimiento == "" ? 'NULL' : "$idOrdenTrabajoMovimiento") . "
                                , ID_INCIDENCIA_CALIDAD = " . ($idIncidenciaCalidad == "" ? 'NULL' : "$idIncidenciaCalidad");
                $bd->ExecSQL($sqlInsert);
                $idCambioReferencia = $bd->IdAsignado();

                //LO GUARDAMOS PARA DEVOLVERLO
                $arrCambiosReferencia[] = $idCambioReferencia;
            endfor;

        else://LOTABLE O SIN LOTE

            //OBTENMOS EL  LOTE DESTINO
            $numSerieLoteDestino     = "";
            $idMaterialFisicoDestino = "";

            //SI ES LOTABLE
            if ($rowMatAlmDestino->TIPO_LOTE == "lote"):
                //SI COINCIDEN EN TIPO LOTE, USAREMOS EL MISMO Nº, SI NO GENERAREMOS UNO
                if ($tipoLoteOrigen == $rowMatAlmDestino->TIPO_LOTE):

                    //COGEMOS EL DE ORIGEN
                    $numSerieLoteDestino = $rowMaterialFisicoOrigen->NUMERO_SERIE_LOTE;
                else:

                    //BUSCO EL NUMERO DE LOTE QUE LE CORRESPONDA
                    $rowLote             = $bd->VerRegRest("CONFIG_GRAL", "1=1");
                    $numSerieLoteDestino = $rowLote->ULTIMO_LOTE_UTILIZADO + 1;

                    //ACTUALIZO EL ULTIMO NUMERO DE LOTE UTILIZADO
                    $sqlUpdate = "UPDATE CONFIG_GRAL SET ULTIMO_LOTE_UTILIZADO = ULTIMO_LOTE_UTILIZADO + 1";
                    $bd->ExecSQL($sqlUpdate);
                endif;

                //BUSCO EL MATERIAL FISICO
                $GLOBALS["NotificaErrorPorEmail"] = "No";
                $rowMaterialFisicoDestino         = $bd->VerRegRest("MATERIAL_FISICO", "ID_MATERIAL = $rowMaterialDestino->ID_MATERIAL AND TIPO_LOTE = '" . $rowMatAlmDestino->TIPO_LOTE . "' AND NUMERO_SERIE_LOTE = '" . $numSerieLoteDestino . "'", "No");
                if ($rowMaterialFisicoDestino == false):
                    $sqlInsert = "INSERT INTO MATERIAL_FISICO SET
                                                ID_MATERIAL = $rowMaterialDestino->ID_MATERIAL
                                                , TIPO_LOTE = '" . $rowMatAlmDestino->TIPO_LOTE . "'
                                                , NUMERO_SERIE_LOTE = '" . $bd->escapeCondicional($numSerieLoteDestino) . "'";
                    $bd->ExecSQL($sqlInsert);
                    $idMaterialFisicoDestino = $bd->IdAsignado();
                else:
                    $idMaterialFisicoDestino = $rowMaterialFisicoDestino->ID_MATERIAL_FISICO;
                endif;
            endif; //FIN LOTE

            //CREO EL CAMBIO_REFERENCIA
            $sqlInsert = "INSERT INTO CAMBIO_REFERENCIA SET
                                    ID_CAMBIO_REFERENCIA_GRUPO = $idCambioReferenciaGrupo
                                    , ENVIADO_SAP = 0
                                    , ID_MATERIAL_ORIGEN = $rowMaterialOrigen->ID_MATERIAL
                                    , ID_MATERIAL_DESTINO = $rowMaterialDestino->ID_MATERIAL
                                    , ID_UBICACION = $rowUbicacion->ID_UBICACION
                                    , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                    , FECHA_CREACION = '" . $txFecha . "'
                                    , CANTIDAD = '" . $cantidad . "'
                                    , ID_MOVIMIENTO_ENTRADA_LINEA_ORIGINAL = " . ($idMovimientoEntradaLineaOrigen != "" ? $idMovimientoEntradaLineaOrigen : "NULL") . "
                                    , ID_MATERIAL_FISICO_ORIGEN = " . ($rowMaterialFisicoOrigen->ID_MATERIAL_FISICO == false ? 'NULL' : "$rowMaterialFisicoOrigen->ID_MATERIAL_FISICO") . "
                                    , ID_MATERIAL_FISICO_DESTINO = " . ($idMaterialFisicoDestino == "" ? 'NULL' : "$idMaterialFisicoDestino") . "
                                    , ID_TIPO_BLOQUEO = " . ($idTipoBloqueo == "" ? 'NULL' : "$idTipoBloqueo") . "
                                    , ID_ORDEN_TRABAJO_MOVIMIENTO = " . ($idOrdenTrabajoMovimiento == "" ? 'NULL' : "$idOrdenTrabajoMovimiento") . "
                                    , ID_INCIDENCIA_CALIDAD = " . ($idIncidenciaCalidad == "" ? 'NULL' : "$idIncidenciaCalidad");
            $bd->ExecSQL($sqlInsert);
            $idCambioReferencia = $bd->IdAsignado();

            //LO GUARDAMOS PARA DEVOLVERLO
            $arrCambiosReferencia[] = $idCambioReferencia;
        endif;

        //FIN COMPROBACIONES
        $arrRespuesta['OK']                = true;
        $arrRespuesta['CambiosReferencia'] = $arrCambiosReferencia;


        return $arrRespuesta;
    }

    /**
     * @param $idCambioReferencia (Se habra generado en proveedor o al entrar un codigo duplicado en el sistema)
     * FUNCION QUE SIRVE PARA REALIZAR UN CAMBIO DE REFERENCIA PDTE DE TRANSMITIR A SAP
     * DEVUELVE idMaterialUbicacionDestino si se ha podido realizar, si no false
     */
    function realizarCambioReferenciaPendiente($idCambioReferencia)
    {
        global $bd;
        global $auxiliar;
        global $administrador;

        //OBTENEMOS EL CAMBIO_REFERENCIA
        $rowCambioReferencia = $bd->VerReg("CAMBIO_REFERENCIA", "ID_CAMBIO_REFERENCIA", $idCambioReferencia);

        //OBTENEMOS EL CAMBIO_REFERENCIA_GRPO
        $rowCambioReferenciaGrupo = $bd->VerReg("CAMBIO_REFERENCIA_GRUPO", "ID_CAMBIO_REFERENCIA_GRUPO", $rowCambioReferencia->ID_CAMBIO_REFERENCIA_GRUPO);

        //SI NO ESTA EN ESTADO "Pdte Transmitir a SAP" NO SEGUIMOS
        if ($rowCambioReferenciaGrupo->ESTADO != "Pdte Transmitir a SAP"):
            return false;
        endif;
        //BUSCAMOS EL MATERIAL UBICACION ORIGEN
        $rowMaterialUbicacionOrigen = $bd->VerRegRest("MATERIAL_UBICACION", "ID_MATERIAL = $rowCambioReferencia->ID_MATERIAL_ORIGEN AND ID_MATERIAL_FISICO " . ($rowCambioReferencia->ID_MATERIAL_FISICO_ORIGEN == "" ? 'IS NULL' : "= $rowCambioReferencia->ID_MATERIAL_FISICO_ORIGEN") . " AND ID_UBICACION = $rowCambioReferencia->ID_UBICACION AND ID_TIPO_BLOQUEO " . ($rowCambioReferencia->ID_TIPO_BLOQUEO == NULL ? 'IS NULL' : "= $rowCambioReferencia->ID_TIPO_BLOQUEO") . " AND ID_ORDEN_TRABAJO_MOVIMIENTO " . ($rowCambioReferencia->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? 'IS NULL' : "= $rowCambioReferencia->ID_ORDEN_TRABAJO_MOVIMIENTO") . " AND ID_INCIDENCIA_CALIDAD " . ($rowCambioReferencia->ID_INCIDENCIA_CALIDAD == NULL ? 'IS NULL' : "= $rowCambioReferencia->ID_INCIDENCIA_CALIDAD"), "No");
        if (!$rowMaterialUbicacionOrigen):
            return false;
        endif;

        //COMPROBAMOS QUE HAY STOCK
        if ($rowMaterialUbicacionOrigen->STOCK_TOTAL < $rowCambioReferencia->CANTIDAD):
            return false;
        endif;

        //BUSCAMOS SI EL MATERIAL FISICO DESTINO SIGUE LIBRE
        if ($rowCambioReferencia->ID_MATERIAL_FISICO_DESTINO != NULL):
            $rowMaterialFisicoDestino = $bd->VerReg("MATERIAL_FISICO", "ID_MATERIAL_FISICO", $rowCambioReferencia->ID_MATERIAL_FISICO_DESTINO);

            if ($rowMaterialFisicoDestino->TIPO_LOTE == "serie"):
                $numMaterialSeriable = $bd->NumRegsTabla("MATERIAL_UBICACION", "ID_MATERIAL_FISICO = $rowMaterialFisicoDestino->ID_MATERIAL_FISICO AND ACTIVO = 1");
                if ($numMaterialSeriable > 0):
                    return false;

                //COMPRUEBO QUE NO ESTE EN TRANSITO
                elseif ($numMaterialSeriable == 0): //EN ALGUN MOMENTO HA ESTADO EN EL SISTEMA
                    if ($this->MaterialFisicoEnTransito($rowMaterialFisicoDestino->ID_MATERIAL_FISICO) == true):
                        return false;
                    endif;
                endif;
            endif;
        endif;

        //BUSCAMOS MATERIAL UBICACION DESTINO

        //BUSCO EL MATERIAL UBICACION DE DESTINO; SI NO EXISTE LO CREO
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowMaterialUbicacionDestino      = $bd->VerRegRest("MATERIAL_UBICACION", "ID_MATERIAL = $rowCambioReferencia->ID_MATERIAL_DESTINO AND ID_MATERIAL_FISICO " . ($rowCambioReferencia->ID_MATERIAL_FISICO_DESTINO == "" ? 'IS NULL' : "= $rowCambioReferencia->ID_MATERIAL_FISICO_DESTINO") . " AND ID_UBICACION = $rowCambioReferencia->ID_UBICACION AND ID_TIPO_BLOQUEO " . ($rowCambioReferencia->ID_TIPO_BLOQUEO == NULL ? 'IS NULL' : "= $rowCambioReferencia->ID_TIPO_BLOQUEO") . " AND ID_ORDEN_TRABAJO_MOVIMIENTO " . ($rowCambioReferencia->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? 'IS NULL' : "= $rowCambioReferencia->ID_ORDEN_TRABAJO_MOVIMIENTO") . " AND ID_INCIDENCIA_CALIDAD " . ($rowCambioReferencia->ID_INCIDENCIA_CALIDAD == NULL ? 'IS NULL' : "= $rowCambioReferencia->ID_INCIDENCIA_CALIDAD"), "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);

        if ($rowMaterialUbicacionDestino == false):
            $sqlInsert = "INSERT INTO MATERIAL_UBICACION SET
                                            ID_MATERIAL = $rowCambioReferencia->ID_MATERIAL_DESTINO
                                            , ID_MATERIAL_FISICO = " . ($rowCambioReferencia->ID_MATERIAL_FISICO_DESTINO == "" ? 'NULL' : "$rowCambioReferencia->ID_MATERIAL_FISICO_DESTINO") . "
                                            , ID_UBICACION = $rowCambioReferencia->ID_UBICACION
                                            , ID_TIPO_BLOQUEO = " . ($rowCambioReferencia->ID_TIPO_BLOQUEO == NULL ? 'NULL' : "$rowCambioReferencia->ID_TIPO_BLOQUEO") . "
                                            , ID_ORDEN_TRABAJO_MOVIMIENTO = " . ($rowCambioReferencia->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? 'NULL' : "$rowCambioReferencia->ID_ORDEN_TRABAJO_MOVIMIENTO") . "
                                            , ID_INCIDENCIA_CALIDAD = " . ($rowCambioReferencia->ID_INCIDENCIA_CALIDAD == NULL ? 'NULL' : "$rowCambioReferencia->ID_INCIDENCIA_CALIDAD");
            $bd->ExecSQL($sqlInsert);
            $idMaterialUbicacionDestino = $bd->IdAsignado();
        else:
            $idMaterialUbicacionDestino = $rowMaterialUbicacionDestino->ID_MATERIAL_UBICACION;
        endif;

        //DECREMENTO CANTIDAD EN MATERIAL UBICACION ORIGEN
        $sqlUpdate = "UPDATE MATERIAL_UBICACION SET
                            STOCK_TOTAL = STOCK_TOTAL - $rowCambioReferencia->CANTIDAD
                            , STOCK_OK = STOCK_OK - " . ($rowMaterialUbicacionOrigen->ID_TIPO_BLOQUEO == NULL ? $rowCambioReferencia->CANTIDAD : 0) . "
                            , STOCK_BLOQUEADO = STOCK_BLOQUEADO - " . ($rowMaterialUbicacionOrigen->ID_TIPO_BLOQUEO == NULL ? 0 : $rowCambioReferencia->CANTIDAD) . "
                            WHERE ID_MATERIAL_UBICACION = $rowMaterialUbicacionOrigen->ID_MATERIAL_UBICACION";
        $bd->ExecSQL($sqlUpdate);

        //INCREMENTO CANTIDAD EN MATERIAL UBICACION DESTINO
        $sqlUpdate = "UPDATE MATERIAL_UBICACION SET
                            STOCK_TOTAL = STOCK_TOTAL + $rowCambioReferencia->CANTIDAD
                            , STOCK_OK = STOCK_OK + " . ($rowMaterialUbicacionOrigen->ID_TIPO_BLOQUEO == NULL ? $rowCambioReferencia->CANTIDAD : 0) . "
                            , STOCK_BLOQUEADO = STOCK_BLOQUEADO + " . ($rowMaterialUbicacionOrigen->ID_TIPO_BLOQUEO == NULL ? 0 : $rowCambioReferencia->CANTIDAD) . "
                            WHERE ID_MATERIAL_UBICACION = $idMaterialUbicacionDestino";
        $bd->ExecSQL($sqlUpdate);

        //HAGO UPDATE DE LA FECHA
        $sqlUpdate = "UPDATE CAMBIO_REFERENCIA SET
                              FECHA = '" . date("Y-m-d H:i:s") . "'
                            , ENVIADO_SAP = 1
                            WHERE ID_CAMBIO_REFERENCIA = $rowCambioReferencia->ID_CAMBIO_REFERENCIA";
        $bd->ExecSQL($sqlUpdate);

        //HAGO UPDATE EL CAMBIO_REFERENCIA_GRUPO
        $numPdtesEnviar = $bd->NumRegsTabla("CAMBIO_REFERENCIA", "ID_CAMBIO_REFERENCIA_GRUPO = $rowCambioReferencia->ID_CAMBIO_REFERENCIA_GRUPO AND ENVIADO_SAP = 0");
        if ($numPdtesEnviar == 0):
            $sqlUpdate = "UPDATE CAMBIO_REFERENCIA_GRUPO SET
                                ESTADO = 'Transmitida a SAP'
                                WHERE ID_CAMBIO_REFERENCIA_GRUPO = $rowCambioReferencia->ID_CAMBIO_REFERENCIA_GRUPO";
            $bd->ExecSQL($sqlUpdate);
        endif;

        return $idMaterialUbicacionDestino;
    }

    /**
     * @param $idMovimientoEntradaLinea MEL en el que se realizó un Cambio Referencia
     * FUNCION QUE SIRVE PARA CREAR UN CAMBIO REFERENCIA PENDIENTE TRANSFERIR A SAP QUE REVIERTA LOS QUE SE CREARON AL PROCESAR EL MOVIMIENTO
     * DEVUELVE EL/LOS idCambioReferencia SI SE HA PODIDO REALIZAR
     */
    function revertirCambioReferenciaMovimientoEntradaLinea($idMovimientoEntradaLinea)
    {
        global $bd;
        global $auxiliar;
        global $administrador;
        global $sap;

        //PARA DEVOLVER
        $arrRespuesta            = array();
        $arrRespuesta['OK']      = true;
        $arrRespuesta['errores'] = "";
        $arrCambiosReferencia    = array();

        //OBTENEMOS MATERIAL ORIGEN
        $rowMovLinea = $bd->VerReg("MOVIMIENTO_ENTRADA_LINEA", "ID_MOVIMIENTO_ENTRADA_LINEA", $idMovimientoEntradaLinea);

        //SI ALGUN CAMBIO DE REFERENCIA REVERTIDO EN UN INTENTO ANTIGUO HA QUEDADO PENDIENTE DE ENVIAR A SAP TAMBIEN LO DEVOLVEMOS
        $sqlCambiosReferenciaPrevios    = "SELECT DISTINCT CR_R.ID_CAMBIO_REFERENCIA
                                                FROM CAMBIO_REFERENCIA CR
                                                INNER JOIN CAMBIO_REFERENCIA CR_R ON CR.ID_CAMBIO_REFERENCIA_RELACIONADO = CR_R. ID_CAMBIO_REFERENCIA
                                                WHERE CR.ID_MOVIMIENTO_ENTRADA_LINEA_ORIGINAL = $rowMovLinea->ID_MOVIMIENTO_ENTRADA_LINEA AND CR.BAJA = 0 AND CR.REVERTIDO = 1 AND CR.ENVIADO_SAP = 1 AND CR_R.ENVIADO_SAP = 0";
        $resultCambiosReferenciaPrevios = $bd->ExecSQL($sqlCambiosReferenciaPrevios);
        if ($bd->NumRegs($resultCambiosReferenciaPrevios) > 0):
            //RECORREMOS LOS CR
            while ($rowCambioReferenciaPrevios = $bd->SigReg($resultCambiosReferenciaPrevios)):
                //LO GUARDAMOS PARA DEVOLVERLO
                $arrCambiosReferencia[] = $rowCambioReferenciaPrevios->ID_CAMBIO_REFERENCIA;
            endwhile;
        endif;

        //BUSCAMOS LOS CAMBIOS DE REFERENCIA
        $sqlCambiosReferencia    = "SELECT DISTINCT CR.*
                                FROM CAMBIO_REFERENCIA CR
                                WHERE CR.ID_MOVIMIENTO_ENTRADA_LINEA_ORIGINAL = $rowMovLinea->ID_MOVIMIENTO_ENTRADA_LINEA AND CR.BAJA = 0 AND CR.REVERTIDO = 0 AND CR.ENVIADO_SAP = 1";
        $resultCambiosReferencia = $bd->ExecSQL($sqlCambiosReferencia);
        if ($bd->NumRegs($resultCambiosReferencia) > 0):

            //CREO EL NUEVO CAMBIO REFERENCIA GRUPO
            $sqlInsert = "INSERT INTO CAMBIO_REFERENCIA_GRUPO SET
                                ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                , ESTADO = 'Pdte Transmitir a SAP'
                                , FECHA = '" . date("Y-m-d H:i:s") . "'
                                , OBSERVACIONES = 'Revertir Cambio Codigo Movimiento Entrada'";
            $bd->ExecSQL($sqlInsert);
            $idCambioReferenciaGrupoNuevo = $bd->IdAsignado();

            $sqlUpdate = "UPDATE CAMBIO_REFERENCIA_GRUPO SET NOMBRE='CR" . $idCambioReferenciaGrupoNuevo . "' WHERE ID_CAMBIO_REFERENCIA_GRUPO=$idCambioReferenciaGrupoNuevo";
            $bd->ExecSQL($sqlUpdate);


            //RECORREMOS LOS CR
            while ($rowCambioReferencia = $bd->SigReg($resultCambiosReferencia)):

                $infoCambioReferencia = " <br/><br/>";
                $errorLinea           = false;

                //BUSCAMOS EL MATERIAL UBICACION ORIGEN(QUE SERA EL QUE ERA DESTINO
                $rowMaterialUbicacionOrigen = $bd->VerRegRest("MATERIAL_UBICACION", "ID_MATERIAL = $rowCambioReferencia->ID_MATERIAL_DESTINO AND ID_MATERIAL_FISICO " . ($rowCambioReferencia->ID_MATERIAL_FISICO_DESTINO == "" ? 'IS NULL' : "= $rowCambioReferencia->ID_MATERIAL_FISICO_DESTINO") . " AND ID_UBICACION = $rowCambioReferencia->ID_UBICACION AND ID_TIPO_BLOQUEO " . ($rowCambioReferencia->ID_TIPO_BLOQUEO == NULL ? 'IS NULL' : "= $rowCambioReferencia->ID_TIPO_BLOQUEO") . " AND ID_ORDEN_TRABAJO_MOVIMIENTO " . ($rowCambioReferencia->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? 'IS NULL' : "= $rowCambioReferencia->ID_ORDEN_TRABAJO_MOVIMIENTO") . " AND ID_INCIDENCIA_CALIDAD " . ($rowCambioReferencia->ID_INCIDENCIA_CALIDAD == NULL ? 'IS NULL' : "= $rowCambioReferencia->ID_INCIDENCIA_CALIDAD"), "No");
                if (!$rowMaterialUbicacionOrigen):
                    $arrRespuesta['OK']      = false;
                    $errorLinea              = true;
                    $arrRespuesta['errores'] .= $auxiliar->traduce("El Material no se encuentra en la ubicacion");
                endif;

                //COMPROBAMOS QUE HAY STOCK
                if ($rowMaterialUbicacionOrigen->STOCK_TOTAL < $rowCambioReferencia->CANTIDAD):
                    $arrRespuesta['OK']      = false;
                    $errorLinea              = true;
                    $arrRespuesta['errores'] .= $auxiliar->traduce("La Cantidad a Modificar no puede ser mayor que el Stock Total", $administrador->ID_IDIOMA) . $infoCambioReferencia;
                endif;

                //BUSCAMOS SI EL MATERIAL FISICO DESTINO SIGUE LIBRE, EN CASO DE SER SERIABLE
                if ($rowCambioReferencia->ID_MATERIAL_FISICO_ORIGEN != NULL):
                    $rowMaterialFisicoDest = $bd->VerReg("MATERIAL_FISICO", "ID_MATERIAL_FISICO", $rowCambioReferencia->ID_MATERIAL_FISICO_ORIGEN, "No");
                    if ($rowMaterialFisicoDest->TIPO_LOTE == "serie"):
                        $numMaterialSeriable = $bd->NumRegsTabla("MATERIAL_UBICACION", "ID_MATERIAL_FISICO = $rowCambioReferencia->ID_MATERIAL_FISICO_ORIGEN AND ACTIVO = 1");
                        if ($numMaterialSeriable > 0):
                            $arrRespuesta['OK']      = false;
                            $errorLinea              = true;
                            $arrRespuesta['errores'] .= $auxiliar->traduce("El material y numero de serie introducidos ya se encuentran en el sistema", $administrador->ID_IDIOMA) . $infoCambioReferencia;
                        endif;
                    endif;
                endif;

                if ($errorLinea == false):
                    //CREO EL NUEVO CAMBIO REFERENCIA
                    $sqlInsert = "INSERT INTO CAMBIO_REFERENCIA SET
                                        ID_CAMBIO_REFERENCIA_GRUPO = $idCambioReferenciaGrupoNuevo
                                        , ENVIADO_SAP = 0
                                        , ID_MATERIAL_ORIGEN = $rowCambioReferencia->ID_MATERIAL_DESTINO
                                        , ID_MATERIAL_DESTINO = $rowCambioReferencia->ID_MATERIAL_ORIGEN
                                        , ID_UBICACION = $rowCambioReferencia->ID_UBICACION
                                        , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                        , FECHA_CREACION = '" . date("Y-m-d H:i:s") . "'
                                        , FECHA = '" . date("Y-m-d H:i:s") . "'
                                        , CANTIDAD = '" . $rowCambioReferencia->CANTIDAD . "'
                                        , ID_MATERIAL_FISICO_ORIGEN = " . ($rowCambioReferencia->ID_MATERIAL_FISICO_DESTINO == NULL ? 'NULL' : "$rowCambioReferencia->ID_MATERIAL_FISICO_DESTINO") . "
                                        , ID_MATERIAL_FISICO_DESTINO = " . ($rowCambioReferencia->ID_MATERIAL_FISICO_ORIGEN == NULL ? 'NULL' : "$rowCambioReferencia->ID_MATERIAL_FISICO_ORIGEN") . "
                                        , ID_TIPO_BLOQUEO = " . ($rowCambioReferencia->ID_TIPO_BLOQUEO == NULL ? 'NULL' : "$rowCambioReferencia->ID_TIPO_BLOQUEO") . "
                                        , ID_ORDEN_TRABAJO_MOVIMIENTO = " . ($rowCambioReferencia->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? 'NULL' : "$rowCambioReferencia->ID_ORDEN_TRABAJO_MOVIMIENTO") . "
                                        , ID_INCIDENCIA_CALIDAD = " . ($rowCambioReferencia->ID_INCIDENCIA_CALIDAD == NULL ? 'NULL' : "$rowCambioReferencia->ID_INCIDENCIA_CALIDAD");
                    $bd->ExecSQL($sqlInsert);
                    $idCambioReferenciaNuevo = $bd->IdAsignado();


                    //HAGO UPDATE DEL CAMBIO DE REFERENCIA ORIGINAL
                    $sqlUpdate = "UPDATE CAMBIO_REFERENCIA SET
                                      REVERTIDO=1
                                    , ID_CAMBIO_REFERENCIA_RELACIONADO=$idCambioReferenciaNuevo
                                    WHERE ID_CAMBIO_REFERENCIA = $rowCambioReferencia->ID_CAMBIO_REFERENCIA";
                    $bd->ExecSQL($sqlUpdate);

                    // LOG MOVIMIENTOS
                    $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Revertir", "Cambio de Referencia", $rowCambioReferencia->ID_CAMBIO_REFERENCIA, "");

                    //LO GUARDAMOS PARA DEVOLVERLO
                    $arrCambiosReferencia[] = $idCambioReferenciaNuevo;
                endif;

            endwhile;
        endif;


        //FIN COMPROBACIONES
        $arrRespuesta['CambiosReferencia'] = $arrCambiosReferencia;


        return $arrRespuesta;
    }


    /**
     * @param $idMaterial Material del que obtener el Sustitutivo
     * Devolvemos el Id del material sustitutivo siempre que no este bloqueado 03
     */
    function obtenerMaterialSustitutivo($idMaterial)
    {
        global $bd;
        global $auxiliar;
        global $administrador;

        $idMaterialSustitutivo = "";

        //HACEMOS LA BUSQUEDA
        $sqlSustitutivo    = "SELECT ID_MATERIAL,ID_MATERIAL_SUSTITUTO
                            FROM MATERIAL_SUSTITUTIVO_DESARROLLADO
                            WHERE (ID_MATERIAL = $idMaterial OR ID_MATERIAL_SUSTITUTO = $idMaterial) AND TIPO = 'Sustituto' AND BAJA = 0";
        $resultSustitutivo = $bd->ExecSQL($sqlSustitutivo);
        if ($bd->NumRegs($resultSustitutivo) > 0):

            //RECORREMOS LOS SUSTITUTIVOS
            while (($rowSustitutivo = $bd->SigReg($resultSustitutivo)) && ($idMaterialSustitutivo == "")):
                //OBTENEMOS EL SUSTITUTIVO
                if ($idMaterial == $rowSustitutivo->ID_MATERIAL):
                    $rowMaterialSustitutivo = $bd->VerReg("MATERIAL", "ID_MATERIAL", $rowSustitutivo->ID_MATERIAL_SUSTITUTO);
                else:
                    $rowMaterialSustitutivo = $bd->VerReg("MATERIAL", "ID_MATERIAL", $rowSustitutivo->ID_MATERIAL);
                endif;
                //COMPROBAMOS QUE EL SUSTITUTO NO ESTE BLOQUEADO
                if ($rowMaterialSustitutivo->ESTADO_BLOQUEO_MATERIAL != "03-Código duplicado"):
                    $idMaterialSustitutivo = $rowMaterialSustitutivo->ID_MATERIAL;
                endif;
            endwhile;
        endif;

        return $idMaterialSustitutivo;
    }

    function PasoCicloCalidadCicloLogisticaInversa($idMaterialUbicacion)
    {
        global $bd;
        global $auxiliar;
        global $sap;
        global $administrador;
        global $ubicacion;

        //RECUPERO LA VARIABLE GLOBAL DE TEXTO ERROR
        global $strError;
        $strError = "";

        //VARIABLES PARA CONTROLAR LA FUNCION
        $hayErrorSGA = false;
        $hayErrorSAP = false;

        //BUSCO EL MATERIAL UBICACION
        $rowMatUbi = $bd->VerReg("MATERIAL_UBICACION", "ID_MATERIAL_UBICACION", $idMaterialUbicacion, "No");
        if (($hayErrorSGA == false) && ($rowMatUbi == false)):
            $hayErrorSGA = true;
            $strError    = $strError . $auxiliar->traduce("El material y ubicacion seleccionada no es válida", $administrador->ID_IDIOMA);
        endif;
        //$html->PagErrorCondicionado($rowMatUbi, "==", false, "MaterialUbicacionNoExiste");

        //BUSCO LA UBICACION
        $rowUbi = $bd->VerReg("UBICACION", "ID_UBICACION", $rowMatUbi->ID_UBICACION);
        if (($hayErrorSGA == false) && ($rowUbi == false)):
            $hayErrorSGA = true;
            $strError    = $strError . $auxiliar->traduce("La ubicacion de la linea no existe", $administrador->ID_IDIOMA);
        endif;
        //$html->PagErrorCondicionado($rowUbi, "==", false, "UbicacionNoExiste");

        //BUSCO EL ALMACEN
        $rowAlmacen = $bd->VerReg("ALMACEN", "ID_ALMACEN", $rowUbi->ID_ALMACEN);
        if (($hayErrorSGA == false) && ($rowAlmacen == false)):
            $hayErrorSGA = true;
            $strError    = $strError . $auxiliar->traduce("El almacen de la linea no existe", $administrador->ID_IDIOMA);
        endif;
        //$html->PagErrorCondicionado($rowAlmacen, "==", false, "AlmacenNoExiste");
        if (($hayErrorSGA == false) && ($rowAlmacen->ID_CENTRO == NULL)):
            $hayErrorSGA = true;
            $strError    = $strError . $auxiliar->traduce("La ubicacion del material es una ubicacion de proveedor", $administrador->ID_IDIOMA);
        endif;
        //$html->PagErrorCondicionado($rowAlmacen->ID_CENTRO, "==", NULL, "UbicacionProveedor");

        //COMPRUEBO QUE TENGA PERMISOS DE ESCRITURA EN EL ALMACEN
        if (($hayErrorSGA == false) && ($administrador->comprobarAlmacenPermiso($rowAlmacen->ID_ALMACEN, "Escritura") == false)):
            $hayErrorSGA = true;
            $strError    = $strError . $auxiliar->traduce("No tiene permisos para realizar esta operacion en esta subzona", $administrador->ID_IDIOMA);
        endif;
        //$html->PagErrorCondicionado($administrador->comprobarAlmacenPermiso($rowAlmacen->ID_ALMACEN, "Escritura") , "==", false, "SinPermisosSubzona");

        //BUSCO EL CENTRO
        $rowCentro = $bd->VerReg("CENTRO", "ID_CENTRO", $rowAlmacen->ID_CENTRO);
        if (($hayErrorSGA == false) && ($rowCentro == false)):
            $hayErrorSGA = true;
            $strError    = $strError . $auxiliar->traduce("El centro de la linea no existe", $administrador->ID_IDIOMA);
        endif;
        //$html->PagErrorCondicionado($rowCentro, "==", false, "CentroNoExiste");

        //BUSCO LA SOCIEDAD
        $rowSociedad = $bd->VerReg("SOCIEDAD", "ID_SOCIEDAD", $rowCentro->ID_SOCIEDAD);
        if (($hayErrorSGA == false) && ($rowSociedad == false)):
            $hayErrorSGA = true;
            $strError    = $strError . $auxiliar->traduce("La sociedad de la linea no existe", $administrador->ID_IDIOMA);
        endif;
        //$html->PagErrorCondicionado($rowSociedad, "==", false, "SociedadNoExiste");

        //BUSCO EL MATERIAL
        $rowMat = $bd->VerReg("MATERIAL", "ID_MATERIAL", $rowMatUbi->ID_MATERIAL, "No");
        if (($hayErrorSGA == false) && ($rowMat == false)):
            $hayErrorSGA = true;
            $strError    = $strError . $auxiliar->traduce("El material de la linea no existe", $administrador->ID_IDIOMA);
        endif;
        //$html->PagErrorCondicionado($rowMat, "==", false, "MaterialNoExiste");

        //BUSCO EL MATERIAL FISICO
        $rowMatFis = NULL;
        if (($rowMatUbi->ID_MATERIAL_FISICO != NULL) && ($hayErrorSGA == false)):
            $rowMatFis = $bd->VerReg("MATERIAL_FISICO", "ID_MATERIAL_FISICO", $rowMatUbi->ID_MATERIAL_FISICO, "No");
            if (($hayErrorSGA == false) && ($rowMatFis == false)):
                $hayErrorSGA = true;
                $strError    = $strError . $auxiliar->traduce("El material fisico de la linea no existe", $administrador->ID_IDIOMA);
            endif;
            //$html->PagErrorCondicionado($rowMatFis, "==", false, "MaterialFisicoNoExiste");
        endif;

        //COMPRUEBO QUE TENGA STOCK
        if (($hayErrorSGA == false) && ($rowMatUbi->STOCK_TOTAL == 0)):
            $hayErrorSGA = true;
            $strError    = $strError . $auxiliar->traduce("La linea seleccionada no tiene stock", $administrador->ID_IDIOMA);
        endif;
        //$html->PagErrorCondicionado($rowMatUbi->STOCK_TOTAL, "==", 0, "StockNoExiste");

        //SI NO SE HAN PRODUCIDO ERRORES SGA COMPRUEBO EL MOVIMIENTO DE LA ORDEN DE TRABAJO
        if ($hayErrorSGA == false):
            //COMPROBACIONES SI MATERIAL UBICACION TIENE OT MOVIMIENTO ASIGNADO
            if ($rowMatUbi->ID_ORDEN_TRABAJO_MOVIMIENTO != NULL):
                //BUSCO LA INFORMACION DE LA OT
                $rowOTMovimiento = $bd->VerReg("ORDEN_TRABAJO_MOVIMIENTO", "ID_ORDEN_TRABAJO_MOVIMIENTO", $rowMatUbi->ID_ORDEN_TRABAJO_MOVIMIENTO);
                if (($hayErrorSGA == false) && ($rowOTMovimiento == false)):
                    $hayErrorSGA = true;
                    $strError    = $strError . $auxiliar->traduce("El movimiento de la orden de trabajo de la linea no existe", $administrador->ID_IDIOMA);
                endif;
                //$html->PagErrorCondicionado($rowOTMovimiento, "==", false, "OrdenTrabajoMovimientoNoExiste");

                //BUSCO LA OT
                $rowOT = $bd->VerReg("ORDEN_TRABAJO", "ID_ORDEN_TRABAJO", $rowOTMovimiento->ID_ORDEN_TRABAJO);
                if (($hayErrorSGA == false) && ($rowOT == false)):
                    $hayErrorSGA = true;
                    $strError    = $strError . $auxiliar->traduce("La orden de trabajo de la linea no existe", $administrador->ID_IDIOMA);
                endif;
                //$html->PagErrorCondicionado($rowOT, "==", false, "OrdenTrabajoNoExiste");

                //BUSCO LA INSTALACION SOBRE LA QUE ACTUA LA OT
                if ($rowOT->SISTEMA_OT == 'MAXIMO'):
                    $rowInstalacion = $bd->VerReg("INSTALACION", "ID_INSTALACION", $rowOT->ID_INSTALACION);
                    if (($hayErrorSGA == false) && ($rowInstalacion == false)):
                        $hayErrorSGA = true;
                        $strError    = $strError . $auxiliar->traduce("La instalacion de la orden de trabajo de la linea no existe", $administrador->ID_IDIOMA);
                    endif;
                    //$html->PagErrorCondicionado($rowInstalacion, "==", false, "InstalacionNoExiste");
                endif;

                //BUSCO LA MAQUINA SOBRE LA QUE ACTUA LA OT
                if ($rowOT->SISTEMA_OT == 'MAXIMO'):
                    $rowMaquina = $bd->VerRegRest("MAQUINA", "ID_INSTALACION = $rowOT->ID_INSTALACION AND REFERENCIA = '" . $rowOT->MAQUINA_OT . "' AND BAJA = 0");
                    if (($hayErrorSGA == false) && ($rowMaquina == false)):
                        $hayErrorSGA = true;
                        $strError    = $strError . $auxiliar->traduce("La maquina de la orden de trabajo de la linea no existe", $administrador->ID_IDIOMA);
                    endif;
                    //$html->PagErrorCondicionado($rowInstalacion, "==", false, "InstalacionNoExiste");
                endif;

                //COMPRUEBO QUE LA ORDEN NO SEA DE TIPO ENTRADA SIN ORDEN DE COMPRA
                if (($hayErrorSGA == false) && ($rowOT->SISTEMA_OT == 'SGA Entrada Sin OC')):
                    $hayErrorSGA = true;
                    $strError    = $strError . $auxiliar->traduce("La línea esta asociada a una OT que es de tipo 'SGA Entrada Sin Orden de Compra'", $administrador->ID_IDIOMA);
                endif;
                //$html->PagErrorCondicionado($rowOT->SISTEMA_OT, "==", 'SGA Entrada Sin OC', "SistemaOTSGAEntradaSinOCNoValida");

                //ESTABLEZCO EL ID DE ORDEN_TRABAJO_MOVIMIENTO
                $idOrdenTrabajoMovimiento = $rowMatUbi->ID_ORDEN_TRABAJO_MOVIMIENTO;

            else: //BUSCAMOS LA OT DE TIPO 'SGA IC' DEL CENTRO CORRESPONDIENTE, SI NO EXISTE LA CREO
                //BUSCO LA OT
                $GLOBALS["NotificaErrorPorEmail"] = "No";
                $rowOT                            = $bd->VerRegRest("ORDEN_TRABAJO", "ID_CENTRO = $rowCentro->ID_CENTRO AND SISTEMA_OT = 'SGA IC' AND ESTADO = 'Abierta'", "No");
                unset($GLOBALS["NotificaErrorPorEmail"]);
                if ($rowOT == false):
                    //RECOGEMOS EL ID_TECNOLOGIA_GENERICA
                    $rowTecnologiaGenerica = $bd->VerReg("TECNOLOGIA_GENERICA", "NOMBRE", "No Aplica", "No");

                    $sqlInsert = "INSERT INTO ORDEN_TRABAJO SET
                                                ID_CENTRO = $rowCentro->ID_CENTRO
                                                , ORDEN_TRABAJO_SAP = 'SGA_IC_" . $rowCentro->REFERENCIA . "'
                                                , ESTADO = 'Abierta'
                                                , TIPO_ORDEN_TRABAJO = 'No Aplica'
                                                , FECHA_CREACION = '" . date("Y-m-d") . "'
                                                , ID_PAIS = " . ($rowSociedad->ID_PAIS == NULL ? 'NULL' : $rowSociedad->ID_PAIS) . "
                                                , TECNOLOGIA = 'No Aplica'
                                                , ID_TECNOLOGIA=$rowTecnologiaGenerica->ID_TECNOLOGIA_GENERICA
                                                , SISTEMA_OT = 'SGA IC'
                                                , MOSTRAR_NUMERO_OT = 0
                                                , ENVIO_A_SAP = 1";
                    $bd->ExecSQL($sqlInsert);
                    $rowOT = $bd->VerReg("ORDEN_TRABAJO", "ID_ORDEN_TRABAJO", $bd->IdAsignado());
                endif;
            endif;
            //FIN COMPROBACIONES SI MATERIAL UBICACION TIENE OT MOVIMIENTO ASIGNADO
        endif;
        //FIN SI NO SE HAN PRODUCIDO ERRORES SGA COMPRUEBO EL MOVIMIENTO DE LA ORDEN DE TRABAJO

        //SI NO SE HAN PRODUCIDO ERRORES SGA HAGO LA LLAMADA A SAP Y LAS ACCIONES CORRESPONDIENTES
        if ($hayErrorSGA == false):
            //BUSCO LA INFORMACION SOBRE GARANTIA DEL MATERIAL
            $garantia            = false;
            $idProveedorGarantia = NULL;

            if ($rowMatUbi->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL):
                $resultado = $sap->materialEnGarantia($rowCentro->REFERENCIA, "", "", $rowMat, $rowMatFis);
            elseif ($rowOT->SISTEMA_OT == 'MAXIMO'):
                $resultado = $sap->materialEnGarantia($rowCentro->REFERENCIA, $rowInstalacion->REFERENCIA, $rowMaquina->REFERENCIA, $rowMat, $rowMatFis);
            elseif ($rowOT->SISTEMA_OT <> 'MAXIMO'):
                $resultado = $sap->materialEnGarantia($rowCentro->REFERENCIA, "", "", $rowMat, $rowMatFis);
            endif;
            if ($resultado['RESULTADO'] != 'OK'): //No se ha podido grabar la información en SAP
                //DECLARO LA VARIABLE DE ERROR SAP A TRUE
                $hayErrorSAP == true;

                //ALMACENO LOS ERRORES
                foreach ($resultado['ERRORES'] as $arr):
                    foreach ($arr as $mensaje_error):
                        $strError = $strError . $mensaje_error . "<br>";
                    endforeach;
                endforeach;
            else:

                //GARANTIA
                $garantia = $resultado['GARANTIA'];

                //PROVEEDOR, SOLO LO RECOGEMOS SI GARANTIA TIENE VALOR: 0 - Reparable No en Garantia, 1 - Reparable en Garantia
                if (($garantia == 0) || ($garantia == 1)):
                    $GLOBALS["NotificaErrorPorEmail"] = "No";
                    $rowProveedor                     = $bd->VerReg("PROVEEDOR", "REFERENCIA", $resultado['DIR_GARANTIA'], "No");
                    unset($GLOBALS["NotificaErrorPorEmail"]);
                    if ($rowProveedor == false):
                        $idProveedorGarantia = NULL;
                    else:
                        if (($hayErrorSGA == false) && ($rowProveedor == false)):
                            $hayErrorSGA = true;
                            $strError    = $strError . $auxiliar->traduce("La referencia de proveedor devuelta por SAP no coincide con ninguna referencia de proveedor de SGA", $administrador->ID_IDIOMA);
                        endif;
                        //$html->PagErrorCondicionado($rowProveedor, "==", false, "ProveedorNoExiste");
                        $idProveedorGarantia = $rowProveedor->ID_PROVEEDOR;
                    endif;
                elseif ($garantia == 2): //No
                    $idProveedorGarantia = NULL;
                endif;
            endif;

            //COMPRUEBO LA GARANTIA DEVUELTA POR SAP
            switch ($garantia):
                case '0':
                    $rowTipoBloqueoDevueltoSAP = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", 'XRCRNG');
                    break;
                case '1':
                    $rowTipoBloqueoDevueltoSAP = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", 'XRCRG');
                    break;
                case '2':
                    $rowTipoBloqueoDevueltoSAP = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", 'XRCNRNG');
                    break;
                default:
                    if ($hayErrorSGA == false):
                        $hayErrorSGA = true;
                        $strError    = $strError . $auxiliar->traduce("SAP no devuleve un tipo de garantia para este registro", $administrador->ID_IDIOMA);
                    endif;
                    //$html->PagError("ErrorTipoGarantia");
                    break;
            endswitch;
            $idTipoBloqueoDevueltoSAP = $rowTipoBloqueoDevueltoSAP->ID_TIPO_BLOQUEO;

            //CREO EL MOVIMIENTO DE LA ORDEN DE TRABAJO DE TIPO 'SGA IC'
            if ($rowMatUbi->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL):
                //BUSCO EL MAXIMO NUMERO DE LINEA
                $UltimoNumeroLinea       = 0;
                $sqlUltimoNumeroLinea    = "SELECT MAX(CAST(LINEA_ORDEN_TRABAJO_MOVIMIENTO_SAP AS UNSIGNED)) AS NUMERO_LINEA FROM ORDEN_TRABAJO_MOVIMIENTO WHERE ID_ORDEN_TRABAJO = $rowOT->ID_ORDEN_TRABAJO";
                $resultUltimoNumeroLinea = $bd->ExecSQL($sqlUltimoNumeroLinea);
                if ($resultUltimoNumeroLinea != false):
                    $rowUltimoNumeroLinea = $bd->SigReg($resultUltimoNumeroLinea);
                    if ($rowUltimoNumeroLinea->NUMERO_LINEA != NULL):
                        $UltimoNumeroLinea = $rowUltimoNumeroLinea->NUMERO_LINEA;
                    endif;
                endif;

                //INCREMENTO EN 10 EL NUMERO DE LINEA
                $SiguienteNumeroLinea = $UltimoNumeroLinea + 10;

                //GENERO EL MOVIMIENTO DE LA ORDEN DE TRABAJO
                $sqlInsert = "INSERT INTO ORDEN_TRABAJO_MOVIMIENTO SET
                                ID_ORDEN_TRABAJO = $rowOT->ID_ORDEN_TRABAJO
                                , LINEA_ORDEN_TRABAJO_MOVIMIENTO_SAP = '" . str_pad( (string)$SiguienteNumeroLinea, 5, "0", STR_PAD_LEFT) . "'
                                , ID_ORDEN_TRABAJO_MOVIMIENTO_RELACIONADO = NULL
                                , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                , ID_ADMINISTRADOR_CREACION = $administrador->ID_ADMINISTRADOR
                                , FECHA = '" . date('Y-m-d H:i:s') . "'
                                , ID_UBICACION = $rowMatUbi->ID_UBICACION
                                , ID_MATERIAL = $rowMatUbi->ID_MATERIAL
                                , ID_MATERIAL_FISICO = " . ($rowMatUbi->ID_MATERIAL_FISICO == NULL ? 'NULL' : "$rowMatUbi->ID_MATERIAL_FISICO") . "
                                , ID_UNIDAD = $rowMat->ID_UNIDAD_MEDIDA
                                , ID_TIPO_BLOQUEO = " . ($rowMatUbi->ID_TIPO_BLOQUEO == NULL ? 'NULL' : $rowMatUbi->ID_TIPO_BLOQUEO) . "
                                , CANTIDAD = $rowMatUbi->STOCK_TOTAL
                                , TIPO_OPERACION = 'Asociación IC'
                                , TIPO_ACCION = 'Neutra'
                                , ID_PROVEEDOR_GARANTIA = " . ($idProveedorGarantia == NULL ? 'NULL' : $idProveedorGarantia);
                $bd->ExecSQL($sqlInsert);
                $idOrdenTrabajoMovimiento = $bd->IdAsignado();
            endif;
            //FIN CREO EL MOVIMIENTO DE LA ORDEN DE TRABAJO DE TIPO 'SGA IC'

            //PROVEEDOR DEVUELTO NO NULO
            if (($idProveedorGarantia != NULL) && (($garantia == 0) || ($garantia == 1))): //ACCIONES SOLO SI NO ES NULO Y ES REPARABLE
                //ACTUALIZO EL PROVEEDOR DEL MOVIMIENTO DE LA ORDEN TRABAJO
                $sqlUpdate = "UPDATE ORDEN_TRABAJO_MOVIMIENTO SET
                                ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                , ID_PROVEEDOR_GARANTIA = $idProveedorGarantia
                                WHERE ID_ORDEN_TRABAJO_MOVIMIENTO = $idOrdenTrabajoMovimiento";
                $bd->ExecSQL($sqlUpdate);
            endif;

            //SI EL TIPO DE BLOQUEO INICIAL ES DIFERENCTE AL FINAL GENERO UN CAMBIO DE ESTADO
            if ($rowMatUbi->ID_TIPO_BLOQUEO != $idTipoBloqueoDevueltoSAP):
                $sqlInsert = " INSERT INTO CAMBIO_ESTADO SET
                                 FECHA = '" . date('Y-m-d H:i:s') . "'
                                , ID_ADMINISTRADOR = " . $administrador->ID_ADMINISTRADOR . "
                                , TIPO_CAMBIO_ESTADO = 'PasoCicloCalidadCicloLogisticaInversa'
                                , ID_MATERIAL = " . $rowMatUbi->ID_MATERIAL . "
                                , ID_MATERIAL_FISICO = " . ($rowMatUbi->ID_MATERIAL_FISICO == NULL ? 'NULL' : "$rowMatUbi->ID_MATERIAL_FISICO") . "
                                , ID_UBICACION = " . $rowMatUbi->ID_UBICACION . "
                                , CANTIDAD = " . $rowMatUbi->STOCK_TOTAL . "
                                , ID_TIPO_BLOQUEO_INICIAL = " . ($rowMatUbi->ID_TIPO_BLOQUEO == NULL ? "NULL" : $rowMatUbi->ID_TIPO_BLOQUEO) . "
                                , ID_TIPO_BLOQUEO_FINAL = " . $idTipoBloqueoDevueltoSAP . "
                                , ID_INCIDENCIA_CALIDAD = " . $rowMatUbi->ID_INCIDENCIA_CALIDAD . "
                                , ID_ORDEN_TRABAJO_MOVIMIENTO = " . $idOrdenTrabajoMovimiento . "
                                , OBSERVACIONES ='Paso del ciclo de calidad al ciclo de logística inversa'";
                $bd->ExecSQL($sqlInsert);//exit($sqlInsert);
                $idCambioEstado = $bd->IdAsignado();

                // LOG MOVIMIENTOS
                $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Creación", "Cambio estado", $idCambioEstado, "");

                //DECREMENTAMOS EL STOCK DEL MATERIAL_UBICACION ORIGEN
                $sqlUpdate = "UPDATE MATERIAL_UBICACION SET
                                STOCK_TOTAL = STOCK_TOTAL - $rowMatUbi->STOCK_TOTAL
                                , STOCK_OK = STOCK_OK - " . ($rowMatUbi->ID_TIPO_BLOQUEO == NULL ? $rowMatUbi->STOCK_TOTAL : 0) . "
                                , STOCK_BLOQUEADO = STOCK_BLOQUEADO - " . ($rowMatUbi->ID_TIPO_BLOQUEO == NULL ? 0 : $rowMatUbi->STOCK_TOTAL) . "
                                WHERE ID_MATERIAL_UBICACION = $rowMatUbi->ID_MATERIAL_UBICACION";
                $bd->ExecSQL($sqlUpdate);

                //INCREMENTAMOS EL MATERIAL UBICACION DESTINO
                //BUSCO MATERIAL UBICACION, SINO EXISTE, LA CREO
                if (!$ubicacion->Existe_Registro_Ubicacion_Material($rowMatUbi->ID_UBICACION, $rowMatUbi->ID_MATERIAL, $rowMatUbi->ID_MATERIAL_FISICO, $idTipoBloqueoDevueltoSAP, $idOrdenTrabajoMovimiento, $rowMatUbi->ID_INCIDENCIA_CALIDAD)):
                    $sqlInsert = "INSERT INTO MATERIAL_UBICACION SET
                                    ID_MATERIAL = $rowMatUbi->ID_MATERIAL
                                    , ID_UBICACION = $rowMatUbi->ID_UBICACION
                                    , ID_MATERIAL_FISICO = " . ($rowMatUbi->ID_MATERIAL_FISICO == NULL ? 'NULL' : $rowMatUbi->ID_MATERIAL_FISICO) . "
                                    , ID_TIPO_BLOQUEO = " . $idTipoBloqueoDevueltoSAP . "
                                    , ID_ORDEN_TRABAJO_MOVIMIENTO = $idOrdenTrabajoMovimiento
                                    , ID_INCIDENCIA_CALIDAD = " . ($rowMatUbi->ID_INCIDENCIA_CALIDAD == NULL ? 'NULL' : $rowMatUbi->ID_INCIDENCIA_CALIDAD);
                    $bd->ExecSQL($sqlInsert);
                    $idMatUbiDestino = $bd->IdAsignado();
                else:
                    $rowMatUbi       = $bd->VerRegRest("MATERIAL_UBICACION", "ID_MATERIAL = $rowMatUbi->ID_MATERIAL AND ID_UBICACION = $rowMatUbi->ID_UBICACION AND ID_MATERIAL_FISICO " . ($rowMatUbi->ID_MATERIAL_FISICO == NULL ? 'IS NULL' : "= $rowMatUbi->ID_MATERIAL_FISICO") . " AND ID_TIPO_BLOQUEO = $idTipoBloqueoDevueltoSAP AND ID_ORDEN_TRABAJO_MOVIMIENTO = $idOrdenTrabajoMovimiento AND ID_INCIDENCIA_CALIDAD " . ($rowMatUbi->ID_INCIDENCIA_CALIDAD == NULL ? 'IS NULL' : "= $rowMatUbi->ID_INCIDENCIA_CALIDAD"));
                    $idMatUbiDestino = $rowMatUbi->ID_MATERIAL_UBICACION;
                endif;

                //INCREMENTAMOS EL STOCK EN NUESTRO ALMACEN
                $sqlUpdate = "UPDATE MATERIAL_UBICACION SET
                                STOCK_TOTAL = STOCK_TOTAL + $rowMatUbi->STOCK_TOTAL
                                , STOCK_OK = STOCK_OK + " . ($idTipoBloqueoDevueltoSAP == NULL ? $rowMatUbi->STOCK_TOTAL : 0) . "
                                , STOCK_BLOQUEADO = STOCK_BLOQUEADO + " . ($idTipoBloqueoDevueltoSAP == NULL ? 0 : $rowMatUbi->STOCK_TOTAL) . "
                                WHERE ID_MATERIAL_UBICACION = $idMatUbiDestino";
                $bd->ExecSQL($sqlUpdate);

                // SE COMPRUEBA SI EL MATERIAL DEBE ESTAR RETENIDO Y SI ESO SE MARCA
                $matFisicoRetenido = $this->setMaterialSusceptibleRetencionSO99($idMatUbiDestino, 'MATERIAL_UBICACION');
            endif;
        endif;
        //FIN SI NO SE HAN PRODUCIDO ERRORES SGA HAGO LA LLAMADA A SAP Y LAS ACCIONES CORRESPONDIENTES

        //ALMACENO EL VALOR DE RESULTADO
        if (!(isset($resultado))):
            $resultado['RESULTADO'] = 'OK';
        endif;

        //DECLARO UN ARRAY PARA DEVOLVER DATOS
        $arrDevuelto                               = array();
        $arrDevuelto['errores']                    = $strError;
        $arrDevuelto['error_SGA']                  = $hayErrorSGA;
        $arrDevuelto['error_SAP']                  = $hayErrorSAP;
        $arrDevuelto['resultado']                  = $resultado;
        $arrDevuelto['idMaterialUbicacionDestino'] = $idMatUbiDestino;

        //DEVUELVO EL ARRAY
        return $arrDevuelto;
    }

    function AnulacionPasoCicloCalidadCicloLogisticaInversa($idCambioEstado)
    {
        global $bd;
        global $auxiliar;
        global $administrador;

        //RECUPERO LA VARIABLE GLOBAL DE TEXTO ERROR
        global $strError;
        $strError = "";

        //VARIABLES PARA CONTROLAR LA FUNCION
        $hayErrorSGA = false;

        //COMPRUEBO QUE EL CAMBIO DE ESTADO EXISTA
        $rowCambioEstado = $bd->VerReg("CAMBIO_ESTADO", "ID_CAMBIO_ESTADO", $idCambioEstado, "No");
        if (($hayErrorSGA == false) && ($rowCambioEstado == false)):
            $hayErrorSGA = true;
            $strError    = $strError . $auxiliar->traduce("No existe el cambio de estado de paso de calidad a logistica inversa a revertir", $administrador->ID_IDIOMA);
        endif;
        //$html->PagErrorCondicionado($rowCambioEstado, "==", false, "CambioEstadoSeleccionadoNoExiste");

        //COMPRUEBO QUE EL CAMBIO DE ESTADO SEA DE TIPO 'PasoCicloCalidadCicloLogisticaInversa'
        if (($hayErrorSGA == false) && ($rowCambioEstado->TIPO_CAMBIO_ESTADO != 'PasoCicloCalidadCicloLogisticaInversa')):
            $hayErrorSGA = true;
            $strError    = $strError . $auxiliar->traduce("El cambio de estado a revertir no es de tipo 'Paso del ciclo de calidad al ciclo de logistica inversa'", $administrador->ID_IDIOMA);
        endif;

        //COMPRUEBO QUE EL CAMBIO DE ESTADO NO ESTE DADO DE BAJA
        if (($hayErrorSGA == false) && ($rowCambioEstado->BAJA != 0)):
            $hayErrorSGA = true;
            $strError    = $strError . $auxiliar->traduce("El cambio de estado a revertir esta dado de baja", $administrador->ID_IDIOMA);
        endif;
        //$html->PagErrorCondicionado($rowCambioEstado->BAJA, "==", 1, "CambioEstadoBaja");

        //COMPRUEBO QUE EL CAMBIO DE ESTADO TENGA INCIDENCIA CALIDAD EN EL BLOUQEO INICIAL
        if (($hayErrorSGA == false) && ($rowCambioEstado->ID_INCIDENCIA_CALIDAD == NULL)):
            $hayErrorSGA = true;
            $strError    = $strError . $auxiliar->traduce("El cambio de estado a revertir no tiene incidencia de calidad asociada", $administrador->ID_IDIOMA);
        endif;
        //$html->PagErrorCondicionado($rowCambioEstado->ID_INCIDENCIA_CALIDAD, "==", NULL, "CambioEstadoSinIncidenciaCalidad");

        //COMPRUEBO QUE EL CAMBIO DE ESTADO NO HAYA SIDO REVERTIDO
        $numCambioEstadoRelacionado = $bd->NumRegsTabla("CAMBIO_ESTADO", "ID_CAMBIO_ESTADO_RELACIONADO = $rowCambioEstado->ID_CAMBIO_ESTADO AND BAJA = 0");
        if (($hayErrorSGA == false) && ($numCambioEstadoRelacionado > 0)):
            $hayErrorSGA = true;
            $strError    = $strError . $auxiliar->traduce("El cambio de estado que esta intentando revertir ya ha sido revertido", $administrador->ID_IDIOMA);
        endif;
        //$html->PagErrorCondicionado($numCambioEstadoRelacionado, ">", 0, "CambioEstadoRevertido");

        //BUSCO LA UBICACION DEL CAMBIO DE ESTADO
        $rowUbicacion = $bd->VerReg("UBICACION", "ID_UBICACION", $rowCambioEstado->ID_UBICACION);

        //COMPRUEBO QUE TENGA PERMISOS DE ESCRITURA EN LA UBICACION
        if (($hayErrorSGA == false) && ($administrador->comprobarUbicacionPermiso($rowUbicacion->ID_UBICACION, "Escritura") == false)):
            $hayErrorSGA = true;
            $strError    = $strError . $auxiliar->traduce("No tiene permisos para realizar esta operación en esta subzona", $administrador->ID_IDIOMA);
        endif;
        //$html->PagErrorCondicionado($administrador->comprobarUbicacionPermiso($rowUbicacion->ID_UBICACION, "Escritura") , "==", false, "SinPermisosSubzona");

        //COMPRUEBO QUE TENGA PERMISOS DE ESCRITURA EN EL ALMACEN
        if (($hayErrorSGA == false) && ($administrador->comprobarAlmacenPermiso($rowUbicacion->ID_ALMACEN, "Escritura") == false)):
            $hayErrorSGA = true;
            $strError    = $strError . $auxiliar->traduce("No tiene permisos para realizar esta operación en esta subzona", $administrador->ID_IDIOMA);
        endif;
        //$html->PagErrorCondicionado($administrador->comprobarAlmacenPermiso($rowUbicacion->ID_ALMACEN, "Escritura") , "==", false, "SinPermisosSubzona");

        //BUSCO EL MOVIMIENTO DE LA ORDEN DE TRABAJO EN CASO DE NO SER VACIO
        if ($rowCambioEstado->ID_ORDEN_TRABAJO_MOVIMIENTO != NULL):
            $rowMovimientoOrdenTrabajo = $bd->VerReg("ORDEN_TRABAJO_MOVIMIENTO", "ID_ORDEN_TRABAJO_MOVIMIENTO", $rowCambioEstado->ID_ORDEN_TRABAJO_MOVIMIENTO);

            //BUSCO LA ORDEN DE TRABAJO
            $rowOrdenTrabajo = $bd->VerReg("ORDEN_TRABAJO", "ID_ORDEN_TRABAJO", $rowMovimientoOrdenTrabajo->ID_ORDEN_TRABAJO);
        endif;

        //BUSCO EL TIPO DE BLOQUEO INICIAL
        if ($rowCambioEstado->ID_TIPO_BLOQUEO_INICIAL != NULL):
            $rowTipoBloqueoInicial = $bd->VerReg("TIPO_BLOQUEO", "ID_TIPO_BLOQUEO", $rowCambioEstado->ID_TIPO_BLOQUEO_INICIAL);
        endif;

        //BUSCO EL TIPO DE BLOQUEO FINAL
        if ($rowCambioEstado->ID_TIPO_BLOQUEO_FINAL != NULL):
            $rowTipoBloqueoFinal = $bd->VerReg("TIPO_BLOQUEO", "ID_TIPO_BLOQUEO", $rowCambioEstado->ID_TIPO_BLOQUEO_FINAL);
        endif;

        //COMPRUEBO QUE HAYA STOCK SUFICIENTE PARA ANULAR EL CAMBIO DE ESTADO
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowMatUbiOrigen                  = $bd->VerRegRest("MATERIAL_UBICACION", "ID_MATERIAL = $rowCambioEstado->ID_MATERIAL AND ID_UBICACION = $rowCambioEstado->ID_UBICACION AND ID_MATERIAL_FISICO " . ($rowCambioEstado->ID_MATERIAL_FISICO == NULL ? 'IS NULL' : "= $rowCambioEstado->ID_MATERIAL_FISICO") . " AND ID_TIPO_BLOQUEO " . ($rowCambioEstado->ID_TIPO_BLOQUEO_FINAL == NULL ? 'IS NULL' : "= $rowCambioEstado->ID_TIPO_BLOQUEO_FINAL") . " AND ID_ORDEN_TRABAJO_MOVIMIENTO " . ($rowCambioEstado->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? 'IS NULL' : "= $rowCambioEstado->ID_ORDEN_TRABAJO_MOVIMIENTO") . " AND ID_INCIDENCIA_CALIDAD " . ($rowCambioEstado->ID_INCIDENCIA_CALIDAD == NULL ? 'IS NULL' : "= $rowCambioEstado->ID_INCIDENCIA_CALIDAD"), "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);

        //SI NO EXISTE EL REGISTRO, ERROR
        if (($hayErrorSGA == false) && ($rowMatUbiOrigen == false)):
            $hayErrorSGA = true;
            $strError    = $strError . $auxiliar->traduce("No se ha encontrado el registro material ubicacion del que descontar el stock", $administrador->ID_IDIOMA);
        endif;
        //$html->PagErrorCondicionado($rowMatUbiOrigen, "==", false, "MaterialUbicacionIncorrecta");

        //COMPRUEBO QUE HAYA CANTIDAD SUFICIENTE
        if ($rowCambioEstado->ID_TIPO_BLOQUEO_FINAL == NULL):
            if (($hayErrorSGA == false) && ($rowMatUbiOrigen->STOCK_OK < $rowCambioEstado->CANTIDAD)):
                $hayErrorSGA = true;
                $strError    = $strError . $auxiliar->traduce("No hay stock suficiente para revertir el cambio de estado", $administrador->ID_IDIOMA);
            endif;
        //$html->PagErrorCondicionado($rowMatUbiOrigen->STOCK_OK, "<", $rowCambioEstado->CANTIDAD, "CantidadInsuficiente");
        elseif ($rowCambioEstado->ID_TIPO_BLOQUEO_FINAL != NULL):
            if (($hayErrorSGA == false) && ($rowMatUbiOrigen->STOCK_BLOQUEADO < $rowCambioEstado->CANTIDAD)):
                $hayErrorSGA = true;
                $strError    = $strError . $auxiliar->traduce("No hay stock suficiente para revertir el cambio de estado", $administrador->ID_IDIOMA);
            endif;
            //$html->PagErrorCondicionado($rowMatUbiOrigen->STOCK_BLOQUEADO, "<", $rowCambioEstado->CANTIDAD, "CantidadInsuficiente");
        endif;

        //SI NO SE HAN PRODUCIDO ERRORES SGA HAGO LAS ACCIONES CORRESPONDIENTES
        if ($hayErrorSGA == false):
            //GENERO EL CAMBIO DE ESTADO QUE REVIERTE EL ORIGINAL
            $sqlInsert = "INSERT INTO CAMBIO_ESTADO SET
                            FECHA = '" . date("Y-m-d H:i:s") . "'
                            , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                            , ID_MATERIAL = $rowCambioEstado->ID_MATERIAL
                            , ID_MATERIAL_FISICO = " . ($rowCambioEstado->ID_MATERIAL_FISICO == NULL ? "NULL" : $rowCambioEstado->ID_MATERIAL_FISICO) . "
                            , ID_UBICACION = $rowCambioEstado->ID_UBICACION
                            , CANTIDAD = $rowCambioEstado->CANTIDAD
                            , ID_TIPO_BLOQUEO_INICIAL = " . ($rowCambioEstado->ID_TIPO_BLOQUEO_FINAL == NULL ? "NULL" : $rowCambioEstado->ID_TIPO_BLOQUEO_FINAL) . "
                            , ID_TIPO_BLOQUEO_FINAL = " . ($rowCambioEstado->ID_TIPO_BLOQUEO_INICIAL == NULL ? "NULL" : $rowCambioEstado->ID_TIPO_BLOQUEO_INICIAL) . "
                            , ID_INCIDENCIA_CALIDAD = " . ($rowCambioEstado->ID_INCIDENCIA_CALIDAD == NULL ? "NULL" : $rowCambioEstado->ID_INCIDENCIA_CALIDAD) . "
                            , ID_CAMBIO_ESTADO_RELACIONADO = $rowCambioEstado->ID_CAMBIO_ESTADO
                            , ID_ORDEN_TRABAJO_MOVIMIENTO = NULL
                            , OBSERVACIONES = '" . $auxiliar->traduce("Anulación paso del ciclo de calidad al ciclo de logística inversa", $administrador->ID_IDIOMA) . "'
                            , TIPO_CAMBIO_ESTADO = 'AnulacionPasoCicloCalidadCicloLogisticaInversa'";
            $bd->ExecSQL($sqlInsert);//exit($sqlInsert);
            $idCambioEstadoRelacionado = $bd->IdAsignado();

            //ACTUALIZO EL CAMBIO DE ESTADO ORIGINAL CON EL CAMBIO DE ESTADO NUEVO RELACIONADO
            $sqlUpdate = "UPDATE CAMBIO_ESTADO SET
                            ID_CAMBIO_ESTADO_RELACIONADO = $idCambioEstadoRelacionado
                            WHERE ID_CAMBIO_ESTADO = $rowCambioEstado->ID_CAMBIO_ESTADO";
            $bd->ExecSQL($sqlUpdate);

            //DECREMENTO MATERIAL_UBICACION ORIGEN
            $sqlUpdate = "UPDATE MATERIAL_UBICACION SET
                            STOCK_TOTAL = STOCK_TOTAL - $rowCambioEstado->CANTIDAD
                            , STOCK_OK = STOCK_OK - " . ($rowCambioEstado->ID_TIPO_BLOQUEO_FINAL == NULL ? $rowCambioEstado->CANTIDAD : 0) . "
                            , STOCK_BLOQUEADO = STOCK_BLOQUEADO - " . ($rowCambioEstado->ID_TIPO_BLOQUEO_FINAL == NULL ? 0 : $rowCambioEstado->CANTIDAD) . "
                            WHERE ID_MATERIAL_UBICACION = $rowMatUbiOrigen->ID_MATERIAL_UBICACION";
            $bd->ExecSQL($sqlUpdate);

            //BUSCO MATERIAL_UBICACION DESTINO
            if ((isset($rowOrdenTrabajo)) && ($rowOrdenTrabajo->SISTEMA_OT != 'SGA IC')):
                $GLOBALS["NotificaErrorPorEmail"] = "No";
                $rowMatUbiDestino                 = $bd->VerRegRest("MATERIAL_UBICACION", "ID_MATERIAL = $rowCambioEstado->ID_MATERIAL AND ID_UBICACION = $rowCambioEstado->ID_UBICACION AND ID_MATERIAL_FISICO " . ($rowCambioEstado->ID_MATERIAL_FISICO == NULL ? 'IS NULL' : "= $rowCambioEstado->ID_MATERIAL_FISICO") . " AND ID_TIPO_BLOQUEO " . ($rowCambioEstado->ID_TIPO_BLOQUEO_INICIAL == NULL ? 'IS NULL' : "= $rowCambioEstado->ID_TIPO_BLOQUEO_INICIAL") . " AND ID_ORDEN_TRABAJO_MOVIMIENTO " . ($rowCambioEstado->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? 'IS NULL' : "= $rowCambioEstado->ID_ORDEN_TRABAJO_MOVIMIENTO") . " AND ID_INCIDENCIA_CALIDAD " . ($rowCambioEstado->ID_INCIDENCIA_CALIDAD == NULL ? 'IS NULL' : "= $rowCambioEstado->ID_INCIDENCIA_CALIDAD"), "No");
                unset($GLOBALS["NotificaErrorPorEmail"]);
                if ($rowMatUbiDestino == false):
                    //CREO MATERIAL UBICACION DESTINO
                    $sqlInsert = "INSERT INTO MATERIAL_UBICACION SET
                                    ID_MATERIAL = $rowCambioEstado->ID_MATERIAL
                                    , ID_MATERIAL_FISICO = " . ($rowCambioEstado->ID_MATERIAL_FISICO == NULL ? 'NULL' : "$rowCambioEstado->ID_MATERIAL_FISICO") . "
                                    , ID_UBICACION = $rowCambioEstado->ID_UBICACION
                                    , ID_TIPO_BLOQUEO = " . ($rowCambioEstado->ID_TIPO_BLOQUEO_INICIAL == NULL ? 'NULL' : "$rowCambioEstado->ID_TIPO_BLOQUEO_INICIAL") . "
                                    , ID_ORDEN_TRABAJO_MOVIMIENTO = " . ($rowCambioEstado->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? 'NULL' : "$rowCambioEstado->ID_ORDEN_TRABAJO_MOVIMIENTO") . "
                                    , ID_INCIDENCIA_CALIDAD = " . ($rowCambioEstado->ID_INCIDENCIA_CALIDAD == NULL ? 'NULL' : "$rowCambioEstado->ID_INCIDENCIA_CALIDAD");
                    $bd->ExecSQL($sqlInsert);
                    $idMatUbiDestino = $bd->IdAsignado();
                else:
                    $idMatUbiDestino = $rowMatUbiDestino->ID_MATERIAL_UBICACION;
                endif;
            else:
                $GLOBALS["NotificaErrorPorEmail"] = "No";
                $rowMatUbiDestino                 = $bd->VerRegRest("MATERIAL_UBICACION", "ID_MATERIAL = $rowCambioEstado->ID_MATERIAL AND ID_UBICACION = $rowCambioEstado->ID_UBICACION AND ID_MATERIAL_FISICO " . ($rowCambioEstado->ID_MATERIAL_FISICO == NULL ? 'IS NULL' : "= $rowCambioEstado->ID_MATERIAL_FISICO") . " AND ID_TIPO_BLOQUEO " . ($rowCambioEstado->ID_TIPO_BLOQUEO_INICIAL == NULL ? 'IS NULL' : "= $rowCambioEstado->ID_TIPO_BLOQUEO_INICIAL") . " AND ID_ORDEN_TRABAJO_MOVIMIENTO IS NULL AND ID_INCIDENCIA_CALIDAD " . ($rowCambioEstado->ID_INCIDENCIA_CALIDAD == NULL ? 'IS NULL' : "= $rowCambioEstado->ID_INCIDENCIA_CALIDAD"), "No");
                unset($GLOBALS["NotificaErrorPorEmail"]);
                if ($rowMatUbiDestino == false):
                    //CREO MATERIAL UBICACION DESTINO
                    $sqlInsert = "INSERT INTO MATERIAL_UBICACION SET
                                    ID_MATERIAL = $rowCambioEstado->ID_MATERIAL
                                    , ID_MATERIAL_FISICO = " . ($rowCambioEstado->ID_MATERIAL_FISICO == NULL ? 'NULL' : "$rowCambioEstado->ID_MATERIAL_FISICO") . "
                                    , ID_UBICACION = $rowCambioEstado->ID_UBICACION
                                    , ID_TIPO_BLOQUEO = " . ($rowCambioEstado->ID_TIPO_BLOQUEO_INICIAL == NULL ? 'NULL' : "$rowCambioEstado->ID_TIPO_BLOQUEO_INICIAL") . "
                                    , ID_ORDEN_TRABAJO_MOVIMIENTO = NULL
                                    , ID_INCIDENCIA_CALIDAD = " . ($rowCambioEstado->ID_INCIDENCIA_CALIDAD == NULL ? 'NULL' : "$rowCambioEstado->ID_INCIDENCIA_CALIDAD");
                    $bd->ExecSQL($sqlInsert);
                    $idMatUbiDestino = $bd->IdAsignado();
                else:
                    $idMatUbiDestino = $rowMatUbiDestino->ID_MATERIAL_UBICACION;
                endif;
            endif;

            //INCREMENTO MATERIAL_UBICACION DESTINO
            $sqlUpdate = "UPDATE MATERIAL_UBICACION SET
                            STOCK_TOTAL = STOCK_TOTAL + $rowCambioEstado->CANTIDAD
                            , STOCK_OK = STOCK_OK + " . ($rowCambioEstado->ID_TIPO_BLOQUEO_INICIAL == NULL ? $rowCambioEstado->CANTIDAD : 0) . "
                            , STOCK_BLOQUEADO = STOCK_BLOQUEADO + " . ($rowCambioEstado->ID_TIPO_BLOQUEO_INICIAL == NULL ? 0 : $rowCambioEstado->CANTIDAD) . "
                            WHERE ID_MATERIAL_UBICACION = $idMatUbiDestino";
            $bd->ExecSQL($sqlUpdate);
            //FIN CAMBIO DE BLOQUEO

            //ANULO LA LINEA DE MOVIMIENTO ORIGINAL
            $sqlUpdate = "UPDATE ORDEN_TRABAJO_MOVIMIENTO SET
                        ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                        , BAJA = 1
                        WHERE ID_ORDEN_TRABAJO_MOVIMIENTO = $rowCambioEstado->ID_ORDEN_TRABAJO_MOVIMIENTO";
            $bd->ExecSQL($sqlUpdate);

            // LOG MOVIMIENTOS
            $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Creación", "Cambio estado", $idCambioEstadoRelacionado, "");
        endif;
        //FIN SI NO SE HAN PRODUCIDO ERRORES SGA HAGO LAS ACCIONES CORRESPONDIENTES

        //ALMACENO EL VALOR DE RESULTADO
        if ($hayErrorSGA == false):
            $resultado['RESULTADO'] = 'OK';
        else:
            $resultado['RESULTADO'] = 'Error';
        endif;

        //DECLARO UN ARRAY PARA DEVOLVER DATOS
        $arrDevuelto                               = array();
        $arrDevuelto['errores']                    = $strError;
        $arrDevuelto['error_SGA']                  = $hayErrorSGA;
        $arrDevuelto['resultado']                  = $resultado;
        $arrDevuelto['idMaterialUbicacionDestino'] = $idMatUbiDestino;

        //DEVUELVO EL ARRAY
        return $arrDevuelto;
    }

    function RealizarAlmacenCargaMasiva($idAlmacenCargaMasivaLinea)
    {
        global $bd;
        global $auxiliar;
        global $administrador;
        global $mat;
        global $sap;

        //VARIABLE DE TEXTO ERROR
        $erroresProducidos = "";

        //VARIABLES PARA CONTROLAR LA FUNCION
        $hayErrorSGA = false;
        $hayErrorSAP = false;

        //BUSCO LA LINEA DE LA CARGA MASIVA DEL ALMACEN
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowAlmacenCargaMasivaLinea       = $bd->VerReg("ALMACEN_CARGA_MASIVA_LINEA", "ID_ALMACEN_CARGA_MASIVA_LINEA", $idAlmacenCargaMasivaLinea, "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);
        if (($hayErrorSGA == false) && ($rowAlmacenCargaMasivaLinea == false)):
            $hayErrorSGA       = true;
            $erroresProducidos .= $auxiliar->traduce("La linea a procesar no existe", $administrador->ID_IDIOMA);
        endif;

        //SI EXISTE LA LINEA DE CARGA MASIVA
        if ($rowAlmacenCargaMasivaLinea != false):
            //BUSCO LA CARGA MASIVA DEL ALMACEN
            $GLOBALS["NotificaErrorPorEmail"] = "No";
            $rowAlmacenCargaMasiva            = $bd->VerReg("ALMACEN_CARGA_MASIVA", "ID_ALMACEN_CARGA_MASIVA", $rowAlmacenCargaMasivaLinea->ID_ALMACEN_CARGA_MASIVA, "No");
            unset($GLOBALS["NotificaErrorPorEmail"]);

            //BUSCO EL CENTRO
            $rowCentro = $bd->VerReg("CENTRO", "ID_CENTRO", $rowAlmacenCargaMasivaLinea->ID_CENTRO);

            //COMPRUEBO QUE EL CENTRO NO ESTE DADO DE BAJA
            if ($rowCentro->BAJA == 1):
                $erroresProducidos .= $auxiliar->traduce("El centro esta dado de baja", $administrador->ID_IDIOMA) . ': ' . $rowCentro->REFERENCIA . ".<br>";
                $hayErrorSGA       = true;
            endif;

            //COMPRUEBO LA INTEGRACION CON SAP DEL CENTRO Y LO QUE HA INDICADO EL USUARIO RESPECTO A LA INTEGRACION CON SAP DE LA CARGA MASIVA
            if (($rowCentro->INTEGRACION_CON_SAP == 0) && ($rowAlmacenCargaMasiva->INTEGRACION_CON_SAP == 1)):
                $erroresProducidos .= $auxiliar->traduce("El centro no tiene integracion con SAP y ha decidido que la carga masiva tenga integracion con SAP", $administrador->ID_IDIOMA) . ': ' . $auxiliar->traduce("Centro", $administrador->ID_IDIOMA) . ": " . $rowCentro->REFERENCIA . ".<br>";
                $hayErrorSGA       = true;
            endif;

            //BUSCO EL ALMACEN
            $rowAlmacen = $bd->VerReg("ALMACEN", "ID_ALMACEN", $rowAlmacenCargaMasivaLinea->ID_ALMACEN);

            //COMPRUEBO QUE EL ALMACEN NO ESTE DADO DE BAJA
            if ($rowAlmacen->BAJA == 1):
                $erroresProducidos .= $auxiliar->traduce("El almacen esta dado de baja", $administrador->ID_IDIOMA) . ': ' . $rowAlmacen->REFERENCIA . ".<br>";
                $hayErrorSGA       = true;
            endif;

            //COMPRUEBO QUE TENGA PERMISOS DE ESCRITURA EN EL ALMACEN (PERMISOS POR ZONA)
            if ($administrador->comprobarAlmacenPermiso($rowAlmacen->ID_ALMACEN, "Escritura") == false):
                $erroresProducidos .= $auxiliar->traduce("No tiene permisos para realizar esta operación en esta subzona", $administrador->ID_IDIOMA) . ': ' . $rowAlmacen->REFERENCIA . ".<br>";
                $hayErrorSGA       = true;
            endif;

            //BUSCO LA UBICACION
            $rowUbicacion = $bd->VerReg("UBICACION", "ID_UBICACION", $rowAlmacenCargaMasivaLinea->ID_UBICACION);

            //COMPRUEBO QUE LA UBICACION NO ESTE DADA DE BAJA
            if ($rowUbicacion->BAJA == 1):
                $erroresProducidos .= $auxiliar->traduce("La ubicacion esta dada de baja", $administrador->ID_IDIOMA) . ': ' . $rowUbicacion->UBICACION . ".<br>";
                $hayErrorSGA       = true;
            endif;

            //COMPRUEBO QUE TENGA PERMISOS DE ESCRITURA EN LA UBICACION (PERMISOS POR ZONA)
            if ($administrador->comprobarUbicacionPermiso($rowUbicacion->ID_UBICACION, "Escritura") == false):
                $erroresProducidos .= $auxiliar->traduce("No tiene permisos para realizar esta operación en esta subzona", $administrador->ID_IDIOMA) . ': ' . $rowUbicacion->UBICACION . ".<br>";
                $hayErrorSGA       = true;
            endif;

            //BUSCO EL MATERIAL
            $rowMaterial = $bd->VerReg("MATERIAL", "ID_MATERIAL", $rowAlmacenCargaMasivaLinea->ID_MATERIAL);

            //COMPRUEBO QUE EL MATERIAL NO ESTE DADO DE BAJA
            if ($rowMaterial->BAJA == 1):
                $erroresProducidos .= $auxiliar->traduce("El material esta dado de baja", $administrador->ID_IDIOMA) . ': ' . $rowMaterial->REFERENCIA_SGA . ".<br>";
                $hayErrorSGA       = true;
            endif;

            //BUSCO LA DUPLA MATERIAL_ALMACEN
            $rowMaterialAlmacen = $bd->VerRegRest("MATERIAL_ALMACEN", "ID_MATERIAL = $rowMaterial->ID_MATERIAL AND ID_ALMACEN = $rowAlmacen->ID_ALMACEN", "No");

            //COMPRUEBO QUE EL TIPO LOTE ES EL MISMO QUE EL DE LA DUPLA MATERIAL-ALMACEN
            if ($rowMaterialAlmacen->TIPO_LOTE != $rowAlmacenCargaMasivaLinea->TIPO_LOTE):
                $erroresProducidos .= $auxiliar->traduce("La definicion del tipo lote ha variado", $administrador->ID_IDIOMA) . ': ' . $auxiliar->traduce("Material", $administrador->ID_IDIOMA) . ": " . $rowMaterial->REFERENCIA_SGA . " - " . $auxiliar->traduce("Almacen", $administrador->ID_IDIOMA) . ": " . $rowAlmacen->REFERENCIA . ".<br>";
                $hayErrorSGA       = true;
            endif;

            //COMPROBACIONES SI EL MATERIAL ES SERIABLE
            if ($rowAlmacenCargaMasivaLinea->TIPO_LOTE == 'serie'):
                //COMPRUEBO SI EXISTE EL NUMERO DE SERIE
                $NotificaErrorPorEmail = "No";
                $rowMaterialFisico     = $bd->VerRegRest("MATERIAL_FISICO", "ID_MATERIAL = $rowMaterial->ID_MATERIAL AND TIPO_LOTE = 'serie' AND NUMERO_SERIE_LOTE = '" . $rowAlmacenCargaMasivaLinea->NUMERO_SERIE . "'", "No");
                unset($NotificaErrorPorEmail);

                if ($rowMaterialFisico != false): //COMPROBACIONES SI EXISTE MATERIAL FISICO
                    //COMPRUEBO QUE NO TENGA STOCK ACTIVO
                    $num = $bd->NumRegsTabla("MATERIAL_UBICACION", "ID_MATERIAL_FISICO = $rowMaterialFisico->ID_MATERIAL_FISICO AND ACTIVO = 1");
                    if ($num > 0):
                        $erroresProducidos .= $auxiliar->traduce("El material y numero de serie ya existen en SGA", $administrador->ID_IDIOMA) . ': ' . $auxiliar->traduce("Material", $administrador->ID_IDIOMA) . ": " . $rowMaterial->REFERENCIA_SGA . " - " . $auxiliar->traduce("Numero de serie", $administrador->ID_IDIOMA) . ": " . $rowAlmacenCargaMasivaLinea->NUMERO_SERIE . ".<br>";
                        $hayErrorSGA       = true;
                    endif;

                    //COMPRUEBO QUE NO ESTE EN TRANSITO
                    if ($mat->MaterialFisicoEnTransito($rowMaterialFisico->ID_MATERIAL_FISICO) == true):
                        $erroresProducidos .= $auxiliar->traduce("El material y numero de serie estan en transito en SGA", $administrador->ID_IDIOMA) . ': ' . $auxiliar->traduce("Material", $administrador->ID_IDIOMA) . ": " . $rowMaterial->REFERENCIA_SGA . " - " . $auxiliar->traduce("Numero de serie", $administrador->ID_IDIOMA) . ": " . $rowAlmacenCargaMasivaLinea->NUMERO_SERIE . ".<br>";
                        $hayErrorSGA       = true;
                    endif;
                else: //COMPROBACIONES SI NO EXISTE MATERIAL FISICO
//                    //COMPRUEBO QUE NO HAYA OTRA CARGA MASIVA PENDIENTE CON ESTE NUMERO DE SERIE
//                    $num = $bd->NumRegsTabla("ALMACEN_CARGA_MASIVA_LINEA", "ID_MATERIAL = $rowMaterial->ID_MATERIAL AND NUMERO_SERIE = '" . $rowAlmacenCargaMasivaLinea->NUMERO_SERIE . "' AND ESTADO = 'Creada' AND BAJA = 0");
//                    if ($num > 0):
//                        $erroresProducidos .= $auxiliar->traduce("El material y numero de serie ya existen en otra carga masiva", $administrador->ID_IDIOMA) . ': ' . $auxiliar->traduce("Material", $administrador->ID_IDIOMA) . ": " . $rowMaterial->REFERENCIA_SGA . " - " . $auxiliar->traduce("Numero de serie", $administrador->ID_IDIOMA) . ": " . $rowAlmacenCargaMasivaLinea->NUMERO_SERIE . ".<br>";
//                        $hayErrorSGA = true;
//                    endif;
                endif;
            endif;

            //BUSCO LA UNIDAD
            $rowUnidad = $bd->VerReg("UNIDAD", "ID_UNIDAD", $rowAlmacenCargaMasivaLinea->ID_UNIDAD);

            //COMPRUEBO QUE LA UNIDAD DEL ARCHIVO COINCIDA CON LA UNIDAD BASE DEL MATERIAL
            if ($rowUnidad->ID_UNIDAD != $rowMaterial->ID_UNIDAD_MEDIDA):
                $rowUnidadMaterial = $bd->VerReg("UNIDAD", "ID_UNIDAD", $rowMaterial->ID_UNIDAD_MEDIDA, "No");
                $erroresProducidos .= $auxiliar->traduce("La unidad de material no coincide con la unidad del archivo", $administrador->ID_IDIOMA) . ': ' . $auxiliar->traduce("Unidad del material", $administrador->ID_IDIOMA) . ": " . ($administrador->ID_IDIOMA == 'ESP' ? $rowUnidadMaterial->UNIDAD_ESP : $rowUnidadMaterial->UNIDAD_ENG) . " - " . $auxiliar->traduce("Unidad del archivo", $administrador->ID_IDIOMA) . ": " . ($administrador->ID_IDIOMA == 'ESP' ? $rowUnidad->UNIDAD_ESP : $rowUnidad->UNIDAD_ENG) . ".<br>";
                $hayErrorSGA       = true;
            endif;

            //SI NO SE HAN PRODUCIDO ERRORES SGA REALIZO LAS ACCIONES CORRESPONDIENTES Y HAGO LA LLAMADA A SAP SI CORRESPONDE
            if ($hayErrorSGA == false):
                //SI ES SERIABLE O LOTABLE BUSCO O CREO EL MATERIAL FISICO
                if ($rowAlmacenCargaMasivaLinea->TIPO_LOTE != 'ninguno'):
                    $GLOBALS["NotificaErrorPorEmail"] = "No";
                    $rowMaterialFisico                = $bd->VerRegRest("MATERIAL_FISICO", "ID_MATERIAL = $rowMaterial->ID_MATERIAL AND TIPO_LOTE = '" . $rowAlmacenCargaMasivaLinea->TIPO_LOTE . "' AND NUMERO_SERIE_LOTE = '" . ($rowAlmacenCargaMasivaLinea->TIPO_LOTE == 'serie' ? $rowAlmacenCargaMasivaLinea->NUMERO_SERIE : $rowAlmacenCargaMasivaLinea->NUMERO_LOTE) . "'", "No");
                    unset($GLOBALS["NotificaErrorPorEmail"]);

                    //SI NO EXISTE EL MATERIAL FISICO LO CREO
                    if ($rowMaterialFisico == false):
                        $sqlInsert = "INSERT INTO MATERIAL_FISICO SET
                                        ID_MATERIAL = $rowMaterial->ID_MATERIAL
                                        , TIPO_LOTE = '" . $rowAlmacenCargaMasivaLinea->TIPO_LOTE . "'
                                        , NUMERO_SERIE_LOTE = '" . ($rowAlmacenCargaMasivaLinea->TIPO_LOTE == 'serie' ? $rowAlmacenCargaMasivaLinea->NUMERO_SERIE : $rowAlmacenCargaMasivaLinea->NUMERO_LOTE) . "'";
                        $bd->ExecSQL($sqlInsert);
                        $rowMaterialFisico = $bd->VerReg("MATERIAL_FISICO", "ID_MATERIAL_FISICO", $bd->IdAsignado());
                    endif;
                endif;
                //FIN SI ES SERIABLE O LOTABLE BUSCO O CREO EL MATERIAL FISICO

                //BUSCO EL REGISTRO MATERIAL UBICACION DONDE INCREMENTAR EL STOCK
                $GLOBALS["NotificaErrorPorEmail"] = "No";
                $rowMatUbi                        = $bd->VerRegRest("MATERIAL_UBICACION", "ID_MATERIAL = $rowMaterial->ID_MATERIAL AND ID_UBICACION = $rowUbicacion->ID_UBICACION AND ID_MATERIAL_FISICO " . ($rowAlmacenCargaMasivaLinea->TIPO_LOTE == 'ninguno' ? 'IS NULL' : "= $rowMaterialFisico->ID_MATERIAL_FISICO") . " AND ID_TIPO_BLOQUEO " . ($rowAlmacenCargaMasivaLinea->ID_TIPO_BLOQUEO == NULL ? 'IS NULL' : "= $rowAlmacenCargaMasivaLinea->ID_TIPO_BLOQUEO") . " AND ID_ORDEN_TRABAJO_MOVIMIENTO IS NULL AND ID_INCIDENCIA_CALIDAD IS NULL", "No");
                unset($GLOBALS["NotificaErrorPorEmail"]);

                //SI NO EXISTE EL REGISTRO MATERIAL UBICACION LO CREO
                if ($rowMatUbi == false):
                    $sqlInsert = "INSERT INTO MATERIAL_UBICACION SET
                                    ID_MATERIAL = $rowMaterial->ID_MATERIAL
                                    , ID_UBICACION = $rowUbicacion->ID_UBICACION
                                    , ID_MATERIAL_FISICO = " . ($rowAlmacenCargaMasivaLinea->TIPO_LOTE == 'ninguno' ? 'NULL' : $rowMaterialFisico->ID_MATERIAL_FISICO) . "
                                    , ID_TIPO_BLOQUEO = " . ($rowAlmacenCargaMasivaLinea->ID_TIPO_BLOQUEO == NULL ? 'NULL' : $rowAlmacenCargaMasivaLinea->ID_TIPO_BLOQUEO) . "
                                    , ID_ORDEN_TRABAJO_MOVIMIENTO = NULL
                                    , ID_INCIDENCIA_CALIDAD = NULL";
                    $bd->ExecSQL($sqlInsert);
                    $rowMatUbi = $bd->VerReg("MATERIAL_UBICACION", "ID_MATERIAL_UBICACION", $bd->IdAsignado());
                endif;

                //ACTUALIZAMOS MATERIAL UBICACION
                $sqlUpdate = "UPDATE MATERIAL_UBICACION SET
                                STOCK_TOTAL = STOCK_TOTAL  + $rowAlmacenCargaMasivaLinea->CANTIDAD
                                , STOCK_OK = STOCK_OK + " . ($rowMatUbi->ID_TIPO_BLOQUEO == NULL ? $rowAlmacenCargaMasivaLinea->CANTIDAD : 0) . "
                                , STOCK_BLOQUEADO = STOCK_BLOQUEADO + " . ($rowMatUbi->ID_TIPO_BLOQUEO == NULL ? 0 : $rowAlmacenCargaMasivaLinea->CANTIDAD) . "
                                WHERE ID_MATERIAL_UBICACION = $rowMatUbi->ID_MATERIAL_UBICACION";
                $bd->ExecSQL($sqlUpdate);

                //INSERTAMOS EL ASIENTO EN LA BD
                $sqlInsert = "INSERT INTO ASIENTO SET
                                ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                , ID_MATERIAL = $rowMatUbi->ID_MATERIAL
                                , ID_UBICACION = $rowMatUbi->ID_UBICACION
                                , TIPO_LOTE = '" . $rowAlmacenCargaMasivaLinea->TIPO_LOTE . "'
                                , ID_MATERIAL_FISICO = " . ($rowAlmacenCargaMasivaLinea->TIPO_LOTE == 'ninguno' ? 'NULL' : $rowMatUbi->ID_MATERIAL_FISICO) . "
                                , FECHA = '" . date("Y-m-d H:i:s") . "'
                                , CANTIDAD = $rowAlmacenCargaMasivaLinea->CANTIDAD
                                , STOCK_OK = " . ($rowMatUbi->ID_TIPO_BLOQUEO == NULL ? $rowAlmacenCargaMasivaLinea->CANTIDAD : 0) . "
                                , STOCK_BLOQUEADO = " . ($rowMatUbi->ID_TIPO_BLOQUEO == NULL ? 0 : $rowAlmacenCargaMasivaLinea->CANTIDAD) . "
                                , TIPO_ASIENTO = 'Carga Inicial'
                                , OBSERVACIONES = 'Carga inicial desde fichero'
                                , ID_TIPO_BLOQUEO = " . ($rowMatUbi->ID_TIPO_BLOQUEO == NULL ? 'NULL' : $rowMatUbi->ID_TIPO_BLOQUEO) . "
                                , ID_ORDEN_TRABAJO_MOVIMIENTO = NULL
                                , ID_INCIDENCIA_CALIDAD = NULL";
                $bd->ExecSQL($sqlInsert);
                $idAsiento = $bd->IdAsignado();

                //SI LA CARGA MASIVA TIENE INTEGRACION CON SAP HAGO LA LLAMADA CORRESPONDIENTE
                if ($rowAlmacenCargaMasiva->INTEGRACION_CON_SAP == 1):
                    //LLAMADA A SAP
                    $resultado = $sap->AjusteAsiento($idAsiento);

                    //RESULTADO DE LA LLAMADA
                    if ($resultado['RESULTADO'] != 'OK'): //No se ha podido grabar la información en SAP
                        //DECLARO LA VARIABLE DE ERROR SAP A TRUE
                        $hayErrorSAP = true;

                        //ALMACENO LOS ERRORES
                        foreach ($resultado['ERRORES'] as $arr):
                            foreach ($arr as $mensaje_error):
                                $erroresProducidos = $erroresProducidos . $mensaje_error . "<br>";
                            endforeach;
                        endforeach;
                    endif;
                endif;
                //FIN SI LA CARGA MASIVA TIENE INTEGRACION CON SAP HAGO LA LLAMADA CORRESPONDIENTE
            endif;
            //FIN SI NO SE HAN PRODUCIDO ERRORES SGA REALIZO LAS ACCIONES CORRESPONDIENTES Y HAGO LA LLAMADA A SAP SI CORRESPONDE
        endif;
        //FIN SI EXISTE LA LINEA DE CARGA MASIVA

        //SI NO SE HAN PRODUCIDO ERRORES ACTUALIZO EL ESTADO DE LINEA DE CARGA MASIVA Y LA CABECERA SI CORRESPONDE
        if (($hayErrorSGA == false) && ($hayErrorSAP == false)):
            //ACTUALIZO EL ESTADO DE LA LINEA
            $sqlUpdate = "UPDATE ALMACEN_CARGA_MASIVA_LINEA SET
                          ESTADO = 'Finalizada'
                          , ID_MATERIAL_FISICO = " . ($rowAlmacenCargaMasivaLinea->TIPO_LOTE == 'ninguno' ? 'NULL' : $rowMatUbi->ID_MATERIAL_FISICO) . "
                          WHERE ID_ALMACEN_CARGA_MASIVA_LINEA = $rowAlmacenCargaMasivaLinea->ID_ALMACEN_CARGA_MASIVA_LINEA";
            $bd->ExecSQL($sqlUpdate);

            //CALCULO EL NUMERO DE LINEAS ACTIVAS
            $numLineasActivas                = $bd->NumRegsTabla("ALMACEN_CARGA_MASIVA_LINEA", "ID_ALMACEN_CARGA_MASIVA = $rowAlmacenCargaMasivaLinea->ID_ALMACEN_CARGA_MASIVA AND BAJA = 0");
            $numLineasActivasCreadas         = $bd->NumRegsTabla("ALMACEN_CARGA_MASIVA_LINEA", "ID_ALMACEN_CARGA_MASIVA = $rowAlmacenCargaMasivaLinea->ID_ALMACEN_CARGA_MASIVA AND BAJA = 0 AND ESTADO = 'Creada'");
            $numLineasActivasPdtesTransmitir = $bd->NumRegsTabla("ALMACEN_CARGA_MASIVA_LINEA", "ID_ALMACEN_CARGA_MASIVA = $rowAlmacenCargaMasivaLinea->ID_ALMACEN_CARGA_MASIVA AND BAJA = 0 AND ESTADO = 'Pdte. Transmitir a SAP'");
            $numLineasActivasFinalizadas     = $bd->NumRegsTabla("ALMACEN_CARGA_MASIVA_LINEA", "ID_ALMACEN_CARGA_MASIVA = $rowAlmacenCargaMasivaLinea->ID_ALMACEN_CARGA_MASIVA AND BAJA = 0 AND ESTADO = 'Finalizada'");

            //ACTUALIZO EL ESTADO DE LA CARGA MASIVA EN FUNCION DE LAS LINEAS
            if ($numLineasActivas == 0): //LA CARGA MASIVA NO TIENE LINEAS ACTIVAS
                $sqlUpdate = "UPDATE ALMACEN_CARGA_MASIVA SET ESTADO = 'Finalizada' WHERE ID_ALMACEN_CARGA_MASIVA = $rowAlmacenCargaMasivaLinea->ID_ALMACEN_CARGA_MASIVA";
            elseif ($numLineasActivas == $numLineasActivasFinalizadas): //TODAS LAS LINEAS FINALIZADAS
                $sqlUpdate = "UPDATE ALMACEN_CARGA_MASIVA SET ESTADO = 'Finalizada' WHERE ID_ALMACEN_CARGA_MASIVA = $rowAlmacenCargaMasivaLinea->ID_ALMACEN_CARGA_MASIVA";
            elseif ($numLineasActivas == $numLineasActivasCreadas): //TODAS LAS LINEAS CREADAS
                $sqlUpdate = "UPDATE ALMACEN_CARGA_MASIVA SET ESTADO = 'Creada' WHERE ID_ALMACEN_CARGA_MASIVA = $rowAlmacenCargaMasivaLinea->ID_ALMACEN_CARGA_MASIVA";
            else:
                $sqlUpdate = "UPDATE ALMACEN_CARGA_MASIVA SET ESTADO = 'En Proceso' WHERE ID_ALMACEN_CARGA_MASIVA = $rowAlmacenCargaMasivaLinea->ID_ALMACEN_CARGA_MASIVA";
            endif;
            $bd->ExecSQL($sqlUpdate);
        endif;

        //ALMACENO EL VALOR DE RESULTADO
        if (!(isset($resultado))):
            $resultado['RESULTADO'] = 'OK';
        endif;

        //DECLARO UN ARRAY PARA DEVOLVER DATOS
        $arrDevuelto              = array();
        $arrDevuelto['errores']   = $erroresProducidos;
        $arrDevuelto['error_SGA'] = $hayErrorSGA;
        $arrDevuelto['error_SAP'] = $hayErrorSAP;
        $arrDevuelto['resultado'] = $resultado;
        $arrDevuelto['idAsiento'] = $idAsiento;

        //DEVUELVO EL ARRAY
        return $arrDevuelto;
    }

    /**
     *  *  COMPRUEBA SI EL MATERIAL NECESITA FICHA DE SEGURIDAD SI ES ASÍ HACE EL TRATAMIENTO PARA LAS FICHAS DE SEGURIDAD
     * SI NO EXISTE REGISTRO LO CREA Y ENVIA CORREO AL PROVEEDOR (SI PROVEEDOR CONTIENE CORREO) PARA ADJUNTAR FICHA DE SEGURIDAD
     *
     * @param $idPedidoEntradaSalida
     * @param $idAlmacen
     * @param $idMaterial
     * @param string $tipoPedido
     * @param bool $enviarCorreo envio de notificación a proveedor
     * @return bool
     */
    function fichaSeguridadIdiomaMaterialPedidoEntradaSalida($idPedidoEntradaSalida, $idAlmacen, $idMaterial, $tipoPedido = "Entrada", $enviarCorreo = true)
    {
        global $bd;
        global $auxiliar;
        global $administrador;
        global $observaciones_sistema;
        global $url_web_adm;
        global $aviso;

        //PROVEEDOR
        $rowMaterial = $bd->VerReg("MATERIAL", "ID_MATERIAL", $idMaterial, "No");
        //ALMACEN
        $rowAlmacen = $bd->VerReg("ALMACEN", "ID_ALMACEN", $idAlmacen, "No");
        //CENTRO FISICO
        $rowCentroFisico = $bd->VerReg("CENTRO_FISICO", "ID_CENTRO_FISICO", $rowAlmacen->ID_CENTRO_FISICO, "No");

        //ORIGEN INCIDENCIA POR DEFECTO PEDIDOS COMPRA
        $origenIncidencia = "Pedidos Compra";
        //SEGUN TIPO PEDIDO
        if (($tipoPedido == "Salida") || ($tipoPedido == "Material Existente") || ($tipoPedido == "FdS Expirada")):
            if ($tipoPedido == "Salida"):
                $origenIncidencia = "Pedidos Traslado";
            else:
                $origenIncidencia = $tipoPedido;
            endif;
            //RECOGEMOS PEDIDO
            //$rowPedidoSalida = $bd->VerReg("PEDIDO_SALIDA", "ID_PEDIDO_SALIDA", $idPedidoEntradaSalida, "No");

            //CENTRO
            $GLOBALS["NotificaErrorPorEmail"] = "No";
            $rowCentro                        = $bd->VerReg("CENTRO", "ID_CENTRO", $rowAlmacen->ID_CENTRO, "No");


            //PEDIDO ENTRADA DE TIPO COMPRA MAS RECIENTE PARA OBTENER PROVEEDOR
            //PRIMERO INTENTAMOS BUSCAR POR EL MISMO CENTRO QUE EL PEDIDO
            $sqlPedidoEntrada    = "SELECT PE.* FROM PEDIDO_ENTRADA PE
                                    INNER JOIN PEDIDO_ENTRADA_LINEA PEL ON PEL.ID_PEDIDO_ENTRADA = PE.ID_PEDIDO_ENTRADA
                                    WHERE (TIPO_PEDIDO = 'Compra' OR TIPO_PEDIDO = 'Compra SGA Manual') AND FECHA_CREACION =
                                            ( SELECT MAX(FECHA_CREACION) FROM PEDIDO_ENTRADA PE2
                                                INNER JOIN PEDIDO_ENTRADA_LINEA PEL2   ON PEL2.ID_PEDIDO_ENTRADA = PE2.ID_PEDIDO_ENTRADA
                                                WHERE PEL2.ID_MATERIAL = $idMaterial
                                            )
                                    AND PEL.ID_MATERIAL = $idMaterial AND PEL.ID_CENTRO = $rowCentro->ID_CENTRO";
            $resultPedidoEntrada = $bd->ExecSQL($sqlPedidoEntrada);

            if ($resultPedidoEntrada != false && $bd->NumRegs($resultPedidoEntrada) > 0):
                $rowPedido = $bd->SigReg($resultPedidoEntrada);
            else:
                //PEDIDO ENTRADA DE TIPO COMPRA MAS RECIENTE PARA OBTENER PROVEEDOR
                $sqlPedidoEntrada    = "SELECT PE.* FROM PEDIDO_ENTRADA PE
                                    INNER JOIN PEDIDO_ENTRADA_LINEA PEL ON PEL.ID_PEDIDO_ENTRADA = PE.ID_PEDIDO_ENTRADA
                                    WHERE (TIPO_PEDIDO = 'Compra' OR TIPO_PEDIDO = 'Compra SGA Manual') AND FECHA_CREACION =
                                            ( SELECT MAX(FECHA_CREACION) FROM PEDIDO_ENTRADA PE2
                                                INNER JOIN PEDIDO_ENTRADA_LINEA PEL2   ON PEL2.ID_PEDIDO_ENTRADA = PE2.ID_PEDIDO_ENTRADA
                                                WHERE PEL2.ID_MATERIAL = $idMaterial
                                            )
                                    AND PEL.ID_MATERIAL = $idMaterial;";
                $resultPedidoEntrada = $bd->ExecSQL($sqlPedidoEntrada);
                if ($resultPedidoEntrada != false && $bd->NumRegs($resultPedidoEntrada) > 0):
                    $rowPedido = $bd->SigReg($resultPedidoEntrada);
                endif;

            endif;
        elseif ($tipoPedido == "Entrada"):
            //RECOGEMOS PEDIDO
            $rowPedido = $bd->VerReg("PEDIDO_ENTRADA", "ID_PEDIDO_ENTRADA", $idPedidoEntradaSalida, "No");
        endif;


        //PROVEEDOR
        $idProveedor = 'NULL';
        if ((isset($rowPedido)) && ($rowPedido != false)):
            $rowProveedor = $bd->VerReg("PROVEEDOR", "ID_PROVEEDOR", $rowPedido->ID_PROVEEDOR, "No");
            $idProveedor  = $rowProveedor->ID_PROVEEDOR;
        endif;

        //VARIABLE CONTROLAR ENVIO CORREO PROVEEDOR
        $enviarCorreoProveedor = false;

        //SI TIENE PROVEEDOR, COMPROBAMOS EMAILS
        $sqlProveedor = "";
        if ($rowProveedor != false):
            $numEmailProveedor = count( (array)$administrador->obtenerEmailsProveedor($rowProveedor->ID_PROVEEDOR, "FdS"));
            $sqlProveedor      = " ID_PROVEEDOR = $rowProveedor->ID_PROVEEDOR AND ";
        endif;
        //SI MATERIAL DEBE TENER FICHA SEGURIDAD Y NO TIENE SE CREA INCIDENCIA
        if ($rowMaterial->DEBE_TENER_FICHA_SEGURIDAD == 1 && $rowAlmacen->TIPO_ALMACEN != "proveedor"):
            if ($rowCentroFisico->ID_PAIS != ""):
                $rowPais       = $bd->VerReg("PAIS", "ID_PAIS", $rowCentroFisico->ID_PAIS, "No");
                $rowIdiomaPais = $bd->VerReg("IDIOMA", "ID_IDIOMA", $rowPais->ID_IDIOMA_PRINCIPAL, "No");

                $GLOBALS["NotificaErrorPorEmail"] = "No";
                $NotificaErrorPorEmail            = "No";
                //COMPROBAMOS SI EXISTE FICHA
                $rowFichaSeguridadIdioma = $bd->VerRegRest("FICHA_SEGURIDAD_MATERIAL_IDIOMA", "ID_MATERIAL = $idMaterial AND ID_IDIOMA = $rowIdiomaPais->ID_IDIOMA AND ESTADO ='Valida'", "No");


                $GLOBALS["NotificaErrorPorEmail"]  = "No";
                $NotificaErrorPorEmail             = "No";
                $rowIncidenciaFichaSeguridadIdioma = $bd->VerRegRest("INCIDENCIA_FICHA_SEGURIDAD_MATERIAL_IDIOMA", "$sqlProveedor ID_MATERIAL = $idMaterial AND ID_IDIOMA = $rowIdiomaPais->ID_IDIOMA AND ESTADO_INCIDENCIA ='No Resuelta'", "No");
                unset($NotificaErrorPorEmail);
                unset($GLOBALS["NotificaErrorPorEmail"]);

                // SI NO EXISTE O ESTA DADA DE BAJA, CREAMOS Y ENVIAMOS CORREO DE FICHA A PROVEEDOR
                if ((($rowFichaSeguridadIdioma == false) || ($rowFichaSeguridadIdioma->BAJA == 1)) && ($rowIncidenciaFichaSeguridadIdioma == false)):
                    //INSERT Y CORREO A PROVEEDOR
                    $keyCorreo = $auxiliar->generarKey();
                    $sqlInsert = "INSERT INTO INCIDENCIA_FICHA_SEGURIDAD_MATERIAL_IDIOMA SET
                                ID_PROVEEDOR = $idProveedor
                                ,ID_MATERIAL = $idMaterial
                                ,ID_ALMACEN = $idAlmacen
                                ,ID_IDIOMA = $rowIdiomaPais->ID_IDIOMA
                                ,KEY_CORREO = '$keyCorreo'
                                ,ORIGEN_INCIDENCIA = '$origenIncidencia'
                                ,FECHA_CREACION = '" . date("Y-m-d H:i:s") . "'
                                ,ESTADO_INCIDENCIA = 'No Resuelta'
                                ,ESTADO_COMUNICACION_PROVEEDOR ='" . ($numEmailProveedor > 0 && $enviarCorreo ? 'Comunicada a Proveedor-Pdte. Respuesta' : 'Pdte. Comunicar a Proveedor') . "'";
                    //echo "$sqlInsert <br>";
                    $bd->ExecSQL($sqlInsert);
                    $idFichaSeguridad      = $bd->IdAsignado();
                    $enviarCorreoProveedor = true;

                    //LOG MOVIMIENTOS
                    if (($tipoPedido == "Material Existente") || ($tipoPedido == "FdS Expirada")):
                        //BUSCO EL ADMINISTRADOR GENERICO DE PROCESOS AUTOMATICOS
                        $rowAdministradorProcesos = $bd->VerReg("ADMINISTRADOR", "LOGIN", 'ProcesoAutomatico');
                        $administrador->Insertar_Log_Movimientos($rowAdministradorProcesos->ID_ADMINISTRADOR, "Creación", "Incidencia Ficha Seguridad Material", $idFichaSeguridad, "", "INCIDENCIA_FICHA_SEGURIDAD_MATERIAL_IDIOMA");
                    else:
                        $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Creación", "Incidencia Ficha Seguridad Material", $idFichaSeguridad, "", "INCIDENCIA_FICHA_SEGURIDAD_MATERIAL_IDIOMA");
                    endif;

                endif;
            else:
                $bd->EnviarEmailErr("Error Fichas De Seguridad", "Centro Fisico ( $rowCentroFisico->ID_CENTRO_FISICO ) sin PAIS asignado.");
            endif;

            if (($enviarCorreo == true) && ($enviarCorreoProveedor == true) && ($numEmailProveedor > 0)):
                $sqlUpdate = "UPDATE INCIDENCIA_FICHA_SEGURIDAD_MATERIAL_IDIOMA SET
                                FECHA_COMUNICACION_PROVEEDOR = '" . date("Y-m-d H:i:s") . "'
                                WHERE ID_INCIDENCIA_FICHA_SEGURIDAD_MATERIAL_IDIOMA = $idFichaSeguridad ";
                $bd->ExecSQL($sqlUpdate);
                //ENVIAR CORREO A PROVEEDOR AVISANDO DE RECHAZO
                $aviso->envioAvisoFichaSeguridadMaterial($rowProveedor->ID_PROVEEDOR, $rowMaterial->ID_MATERIAL, $idFichaSeguridad, $rowIdiomaPais->ID_IDIOMA);


                //GRABO LAS OBSERVACIONES EN OBSERVACIONES SISTEMA
                $observaciones_sistema->Grabar("INCIDENCIA_FICHA_SEGURIDAD_MATERIAL", $idFichaSeguridad, "Envio peticion a proveedor de Ficha de Seguridad");

            endif;
        endif;

        return false;
    }


    /**
     * FUNCION PARA CREAR UN CAMBIO DE ESTADO
     * Si fallan las comprobaciones devuelve $resultado['id'] nulo y $resulado['errores'] con los errores detectados
     * Si falla la llamada a SAP deshace la transaccion en la que este involucrada y devuelve igual que si fallan las comprobaciones, a no ser que $rollbackSiFalla venga marcado como "No" que entonces el rollback se hará fuera de la función y se le pasará el resultado de la llamada para que se guarde
     * Si el proceso se ejecuta correctamente, devuelve $resultado['id'] con el identificador del objeto creado
     */
    function generarCambioEstado($idCambioEstadoGrupo, $idMaterial, $idUbicacion, $idMaterialFisico, $idTipoBloqueoInicial, $idTipoBloqueoFinal, $idOrdenTrabajoMovimiento, $idIncidenciaCalidad, $idOrdenTrabajoLinea, $cantidad, $tipo, $observaciones, $rollbackSiFalla = "Si")
    {
        global $bd;
        global $auxiliar;
        global $administrador;
        global $sap;

        //COMPRUEBO QUE CANTIDAD SEA DIFERENTE DE CERO
        if ($cantidad == "0"):
            $resultado['errores'] .= $auxiliar->traduce("El valor de cantidad no puede ser cero", $administrador->ID_IDIOMA) . "<br>";
            $resultado['id']      = NULL;

            return $resultado;
        endif;

        //SI LA CANTIDAD ES NEGATIVA MOSTRARE UN ERROR PORQUE NO SE PUEDE CAMBIAR STOCK NEGATIVO
        if ($cantidad < 0):
            $resultado['errores'] .= $auxiliar->traduce("El valor de cantidad no puede ser inferior a cero", $administrador->ID_IDIOMA) . "<br>";
            $resultado['id']      = NULL;

            return $resultado;
        endif;

        //COMPRUEBO QUE EL TIPO DE BLOQUEO INICIAL Y FINAL SEAN DIFERENTES
        if ($idTipoBloqueoInicial == $idTipoBloqueoFinal):
            $resultado['errores'] .= $auxiliar->traduce("El tipo de bloqueo inicial es igual al tipo de bloqueo final", $administrador->ID_IDIOMA) . "<br>";
            $resultado['id']      = NULL;

            return $resultado;
        endif;

        //VARIABLE PARA SABER SI LOS BLOQUEOS SON IGUALES PARA SAP
        $bloqueosIgualesParaSAP = false;

        //COMPRUEBO SI LOS 2 BLOQUEOS SON IGUALES PARA SAP
        if ($idTipoBloqueoInicial == $idTipoBloqueoFinal):
            //COMPRUEBO QUE LOS 2 BLOQUEOS NO SEAN IGUALES PARA SAP
            if (($idTipoBloqueoInicial != NULL) && ($idTipoBloqueoFinal != NULL)):
                //BUSCO EL TIPO DE BLOQUEO INICIAL
                $rowTipoBloqueoInicial = $bd->VerReg("TIPO_BLOQUEO", "ID_TIPO_BLOQUEO", $idTipoBloqueoInicial);
                //BUSCO EL TIPO DE BLOQUEO FINAL
                $rowTipoBloqueoFinal = $bd->VerReg("TIPO_BLOQUEO", "ID_TIPO_BLOQUEO", $idTipoBloqueoFinal);

                if ($rowTipoBloqueoInicial->TIPO_BLOQUEO_SAP == $rowTipoBloqueoFinal->TIPO_BLOQUEO_SAP):
                    $bloqueosIgualesParaSAP = true;
                endif;
            endif;
        endif;

        //COMPRUEBO QUE HAYA STOCK SUFICIENTE DE ESE TIPO EN LA UBICACION
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $clausulaWhere                    = "ID_MATERIAL = $idMaterial AND ID_UBICACION = $idUbicacion AND ID_MATERIAL_FISICO " . ($idMaterialFisico == "" ? "IS NULL" : "= $idMaterialFisico") . " AND ID_TIPO_BLOQUEO " . ($idTipoBloqueoInicial == NULL ? "IS NULL" : "= $idTipoBloqueoInicial") . " AND ID_ORDEN_TRABAJO_MOVIMIENTO " . ($idOrdenTrabajoMovimiento == NULL ? "IS NULL" : "= $idOrdenTrabajoMovimiento") . " AND ID_INCIDENCIA_CALIDAD " . ($idIncidenciaCalidad == NULL ? "IS NULL" : "= $idIncidenciaCalidad");
        $rowMatUbi                        = $bd->VerRegRest("MATERIAL_UBICACION", $clausulaWhere, "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);

        //SI NO EXISTE EL REGISTRO MATERIAL UBICACION
        if ($rowMatUbi == false):
            $resultado['errores'] .= $auxiliar->traduce("No existe el regitro material-ubicacion", $administrador->ID_IDIOMA) . "<br>";
            $resultado['id']      = NULL;

            return $resultado;
        endif;

        //COMPRUEBO QUE EXISTA STOCK SUFICIENTE
        if ($rowMatUbi->STOCK_TOTAL < $cantidad):
            $resultado['errores'] .= $auxiliar->traduce("La cantidad disponible es inferior a la cantidad sobre la que realizar el cambio de estado", $administrador->ID_IDIOMA) . "<br>";
            $resultado['id']      = NULL;

            return $resultado;
        endif;

        //GENERO EL CAMBIO DE ESTADO
        $sqlInsert = "INSERT INTO CAMBIO_ESTADO SET
                        FECHA = '" . date("Y-m-d H:i:s") . "'
                        , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                        , ID_MATERIAL = $idMaterial
                        , ID_MATERIAL_FISICO = " . ($idMaterialFisico == "" ? "NULL" : $idMaterialFisico) . "
                        , ID_UBICACION = $idUbicacion
                        , CANTIDAD = $cantidad
                        , ID_TIPO_BLOQUEO_INICIAL = " . ($idTipoBloqueoInicial == NULL ? "NULL" : $idTipoBloqueoInicial) . "
                        , ID_TIPO_BLOQUEO_FINAL = " . ($idTipoBloqueoFinal == NULL ? "NULL" : $idTipoBloqueoFinal) . "
                        , ID_INCIDENCIA_CALIDAD = " . ($idIncidenciaCalidad == NULL ? "NULL" : $idIncidenciaCalidad) . "
                        , ID_ORDEN_TRABAJO_MOVIMIENTO = " . ($idOrdenTrabajoMovimiento == NULL ? "NULL" : $idOrdenTrabajoMovimiento) . "
                        , ID_ORDEN_TRABAJO_LINEA = " . ($idOrdenTrabajoLinea == NULL ? "NULL" : $idOrdenTrabajoLinea) . "
                        , OBSERVACIONES = '" . $observaciones . "'
                        , TIPO_CAMBIO_ESTADO = '" . $tipo . "'";
        $bd->ExecSQL($sqlInsert);
        $resultado['id'] = $bd->IdAsignado();

        //DECREMENTO MATERIAL_UBICACION ORIGEN
        $sqlUpdate = "UPDATE MATERIAL_UBICACION SET
                        STOCK_TOTAL = STOCK_TOTAL - $cantidad
                        , STOCK_OK = STOCK_OK - " . ($idTipoBloqueoInicial == NULL ? $cantidad : 0) . "
                        , STOCK_BLOQUEADO = STOCK_BLOQUEADO - " . ($idTipoBloqueoInicial == NULL ? 0 : $cantidad) . "
                        WHERE ID_MATERIAL_UBICACION = $rowMatUbi->ID_MATERIAL_UBICACION";
        $bd->ExecSQL($sqlUpdate);

        //BUSCO MATERIAL_UBICACION DESTINO
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $clausulaWhere                    = "ID_MATERIAL = $idMaterial AND ID_UBICACION = $idUbicacion AND ID_MATERIAL_FISICO " . ($idMaterialFisico == "" ? "IS NULL" : "= $idMaterialFisico") . " AND ID_TIPO_BLOQUEO " . ($idTipoBloqueoFinal == NULL ? "IS NULL" : "= $idTipoBloqueoFinal") . " AND ID_ORDEN_TRABAJO_MOVIMIENTO " . ($idOrdenTrabajoMovimiento == NULL ? "IS NULL" : "= $idOrdenTrabajoMovimiento") . " AND ID_INCIDENCIA_CALIDAD " . ($idIncidenciaCalidad == NULL ? "IS NULL" : "= $idIncidenciaCalidad");
        $rowMatUbiDestino                 = $bd->VerRegRest("MATERIAL_UBICACION", $clausulaWhere, "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);
        if ($rowMatUbiDestino == false):
            //CREO MATERIAL UBICACION DESTINO
            $sqlInsert = "INSERT INTO MATERIAL_UBICACION SET
                            ID_MATERIAL = $idMaterial
                            , ID_MATERIAL_FISICO = " . ($idMaterialFisico == NULL ? "NULL" : $idMaterialFisico) . "
                            , ID_UBICACION = $idUbicacion
                            , ID_TIPO_BLOQUEO = " . ($idTipoBloqueoFinal == NULL ? "NULL" : "$idTipoBloqueoFinal") . "
                            , ID_ORDEN_TRABAJO_MOVIMIENTO = " . ($idOrdenTrabajoMovimiento == NULL ? "NULL" : $idOrdenTrabajoMovimiento) . "
                            , ID_INCIDENCIA_CALIDAD = " . ($idIncidenciaCalidad == NULL ? "NULL" : $idIncidenciaCalidad);
            $bd->ExecSQL($sqlInsert);
            $idMatUbiDestino = $bd->IdAsignado();
        else:
            $idMatUbiDestino = $rowMatUbiDestino->ID_MATERIAL_UBICACION;
        endif;

        //INCREMENTO MATERIAL_UBICACION DESTINO
        $sqlUpdate = "UPDATE MATERIAL_UBICACION SET
                        STOCK_TOTAL = STOCK_TOTAL + $cantidad
                        , STOCK_OK = STOCK_OK + " . ($idTipoBloqueoFinal == NULL ? $cantidad : 0) . "
                        , STOCK_BLOQUEADO = STOCK_BLOQUEADO + " . ($idTipoBloqueoFinal == NULL ? 0 : $cantidad) . "
                        WHERE ID_MATERIAL_UBICACION = $idMatUbiDestino";
        $bd->ExecSQL($sqlUpdate);

        //SI LOS BLOQUEOS SON DIFERENTES PARA SAP, HAGO LA LLAMADA
        if ($bloqueosIgualesParaSAP == false):

            //ENVIO A SAP EL CAMBIO DE ESTADO
            $resultadoCambioEstado = $sap->AjusteCambioEstado($resultado['id']);
            if ($resultadoCambioEstado['RESULTADO'] != 'OK'): //No se ha podido grabar la información en SAP
                //ESTABLEZCO EL RESULTADO ID A NULO PARA INDICAR QUE NO SE HA PODIDO GENERAR EL CAMBIO DE ESTADO
                $resultado['id'] = NULL;

                //INICIO LOS ERRORES DEVUELTOS POR LA LLAMADA ERRONEA A SAP
                $resultadoCambioEstado['errores'] .= $auxiliar->traduce("Los errores devueltos por SAP son los siguientes", $administrador->ID_IDIOMA) . ": " . "<br>";

                //RECORRO LOS ERRORES PARA GUARDARLOS Y MOSTRARLOS POSTERIORMENTE
                foreach ($resultadoCambioEstado['ERRORES'] as $arr):
                    foreach ($arr as $mensaje_error):
                        $resultadoCambioEstado['errores'] = $resultadoCambioEstado['errores'] . $mensaje_error . "<br>";
                    endforeach;
                endforeach;

                //SI NOS PIDEN HACER ROLLBACK SI FALLA, LO HACEMOS
                if ($rollbackSiFalla == "Si"):
                    //DESHAGO LA TRANSACCION EN LA QUE ESTE INVOLUCRADA ESTA LLAMADA A SAP
                    $bd->rollback_transaction();

                    //GRABO EL ERROR PRODUCIDO EN BASE DE DATOS
                    $sap->InsertarErrores($resultadoCambioEstado);

                else://SI EL ROLLBACK SE HACE FUERA, NOS ENVIAMOS EL RESULTADO SAP PARA GUARDARLO POSTERIORMENTE
                    $resultado['resultadoSAP'] = $resultadoCambioEstado;
                endif;

                //NO SE MUESTRAN ERRORES, SE GESTIONAN DESDE DONDE SE LLAME A ESTA FUNCION
                //$html->PagError("ErrorSAP");

                //AÑADO AL ARRAY DE ERRORES LOS ERRORES DEVUELTOS EN LA LLAMADA A SAP
                $resultado['errores'] .= $resultadoCambioEstado['errores'] . "<br>";
            endif;

        endif;
        //FIN SI LOS BLOQUEOS SON DIFERENTES PARA SAP, HAGO LA LLAMADA

        // LOG MOVIMIENTOS
        if ($resultado['id'] != NULL):
            //BUSCO EL CAMBIO DE ESTADO CREADO
            $rowCambioEstadoCreado = $bd->VerReg("CAMBIO_ESTADO", "ID_CAMBIO_ESTADO", $resultado['id']);

            $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Creación", "Cambio estado", $resultado['id'], "Creacion cambio estado", "CAMBIO_ESTADO", NULL, $rowCambioEstadoCreado);
        endif;

        //RETORNO EL RESULTADO
        return $resultado;
    }

    function getUnidadManipulacion($idUnidadCompra, $idUnidadMedida, $numerador, $denominador, $idioma = "ESP")
    {
        global $bd, $administrador;

        if ($idUnidadMedida == $idUnidadCompra):
            $textoUnidadManipulacion = "";
        else:
            $rowUnidadManipulacion = $bd->VerReg("UNIDAD", "ID_UNIDAD", $idUnidadCompra);
            $rowUnidadBase         = $bd->VerReg("UNIDAD", "ID_UNIDAD", $idUnidadMedida);

            if ($idioma == "ESP"):
                if (($numerador / $denominador) == 1):
                    $textoUnidadManipulacion = $rowUnidadManipulacion->DESCRIPCION . " " . ($numerador / $denominador) . " " . $rowUnidadBase->DESCRIPCION;
                elseif (preg_match("/[aeiouAEIOU]/", (string) substr((string) $rowUnidadBase->DESCRIPCION, -1))):
                    $textoUnidadManipulacion = $rowUnidadManipulacion->DESCRIPCION . " " . ($numerador / $denominador) . " " . $rowUnidadBase->DESCRIPCION . 's';
                elseif (preg_match("/[a-z]/i", (string) substr((string) $rowUnidadBase->DESCRIPCION, -1))):
                    $textoUnidadManipulacion = $rowUnidadManipulacion->DESCRIPCION . " " . ($numerador / $denominador) . " " . $rowUnidadBase->DESCRIPCION . 'es';
                else:
                    $textoUnidadManipulacion = $rowUnidadManipulacion->DESCRIPCION . " " . ($numerador / $denominador) . " " . $rowUnidadBase->DESCRIPCION;
                endif;
            elseif ($idioma == "ENG"):
                if (($numerador / $denominador) == 1):
                    $textoUnidadManipulacion = $rowUnidadManipulacion->DESCRIPCION_ENG . " " . ($numerador / $denominador) . " " . $rowUnidadBase->DESCRIPCION_ENG;
                elseif (preg_match("/[a-z]/i", (string) substr((string) $rowUnidadBase->DESCRIPCION, -1))):
                    $textoUnidadManipulacion = $rowUnidadManipulacion->DESCRIPCION_ENG . " " . ($numerador / $denominador) . " " . $rowUnidadBase->DESCRIPCION_ENG . 's';
                else:
                    $textoUnidadManipulacion = $rowUnidadManipulacion->DESCRIPCION_ENG . " " . ($numerador / $denominador) . " " . $rowUnidadBase->DESCRIPCION_ENG;
                endif;
            endif;

        endif;

        return $textoUnidadManipulacion;
    }

    function ObtenerCodigoMaterialQR($txMaterial, $txSerieLote = "")
    {

        global $bd, $administrador, $html;

        //COMPROBAMOS QUE NI EL MATERIAL NI LA REFERENCIA TENGAN EL SEPARADOR
        if ((strpos( (string)$txMaterial, SEPARADOR_MATERIAL_PDA) !== false) || (strpos( (string)$txSerieLote, SEPARADOR_MATERIAL_PDA) !== false)):
            $html->PagError("CodigoIncorrectoParaQR");
        endif;

        //AÑADIMOS EL MATERIAL
        $codigoQR = $txMaterial;

        //SI TIENE SERIE LOTE, LO AÑADIMOS CON EL SEPARADOR
        if ($txSerieLote != ""):
            $codigoQR .= SEPARADOR_MATERIAL_PDA . $txSerieLote;
        endif;

        return $codigoQR;
    }


    function ObtenerCodigoQRCompleto($txMaterial, $txSerieLote = "", $txCantidad = "")
    {

        global $html;

        //COMPROBAMOS QUE NI EL MATERIAL NI LA REFERENCIA TENGAN EL SEPARADOR
        if ((strpos( (string)$txMaterial, SEPARADOR_MATERIAL_PDA) !== false) || (strpos( (string)$txSerieLote, SEPARADOR_MATERIAL_PDA) !== false) || (strpos( (string)$txCantidad, SEPARADOR_MATERIAL_PDA) !== false)):
            $html->PagError("CodigoIncorrectoParaQR");
        endif;

        //AÑADIMOS LOS CAMPOS AL CÓDIGO
        $codigoQR = $txMaterial;
        $codigoQR .= SEPARADOR_MATERIAL_PDA . $txSerieLote;
        $codigoQR .= SEPARADOR_MATERIAL_PDA . $txCantidad;

        return $codigoQR;
    }

    //FUNCION PARA SABER EN CUANTOS PEDIDOS ZTRE/ZTRG HA ESTADO IMPLICADA UNA PIEZA ESTROPEADA
    function getNumeroPedidosIntercompanyMaterialEstropeado($idOrdenTrabajoMovimiento)
    {
        global $bd;

        $sqlNumPedidosZTRE    = "SELECT COUNT(*) AS NUM
                              FROM MOVIMIENTO_SALIDA_LINEA MSL
                              INNER JOIN PEDIDO_SALIDA PS ON PS.ID_PEDIDO_SALIDA = MSL.ID_PEDIDO_SALIDA
                              WHERE MSL.ID_ORDEN_TRABAJO_MOVIMIENTO = $idOrdenTrabajoMovimiento AND MSL.LINEA_ANULADA = 0 AND MSL.BAJA = 0 AND PS.TIPO_PEDIDO_SAP IN ('ZTRE', 'ZTRG')";
        $resultNumPedidosZTRE = $bd->ExecSQL($sqlNumPedidosZTRE);
        $rowNumPedidosZTRE    = $bd->SigReg($resultNumPedidosZTRE);

        return $rowNumPedidosZTRE->NUM;
    }

    /**
     * FUNCIÓN PARA COMPROBAR SI SE ESTÁ HACIENDO UN TRATAMIENTO PARCIAL DE LA CANTIDAD DE COMPRA Y SI EL USUARIO TIENE PERMISOS PARA ELLO
     * @param $txCantidad
     * @param $rowMat
     * @param string $nombrePermisos
     * @param string $nombreError
     */
    function tratamientoParcialCantidadCompra($txCantidad, $rowMat, $nombrePermisos = 'ADM_ENTRADAS_TRATAMIENTO_PARCIAL_CC', $nombreError = 'ErrorLineaTratamientoParcialCC')
    {
        global $administrador;
        global $html;

        if ($administrador->Hayar_Permiso_Perfil($nombrePermisos) < 2):
            // SI EL MATERIAL TIENE CANTIDAD DE COMPRA Y NO ES DIVISIBLE
            if (($rowMat->ID_UNIDAD_MEDIDA != $rowMat->ID_UNIDAD_COMPRA && $rowMat->DENOMINADOR_CONVERSION != 0) && $rowMat->DIVISIBILIDAD != 'Si'):
                //CANTIDAD DE COMPRA Y UNIDADES BASE Y COMPRA
                $cantidadCompra      = $this->cantUnidadCompra($rowMat->ID_MATERIAL, $txCantidad);
                $unidadesBaseyCompra = $this->unidadBaseyCompra($rowMat->ID_MATERIAL);

                // SE COMPRUEBA SI LA CANTIDAD DE COMPRA ES UN NÚMERO ENTERO
                if (fmod((float) $cantidadCompra, 1) !== 0.0):
                    $html->PagError($nombreError);
                endif;
            endif;
        endif;
    }

    //FUNCION PARA ACTUALIZAR LA GARANTIA A TRAVES DE UNA LLAMADA A SAP
    //$idMaterialUbicacion REGISTRO MATERIAL UBICACION A ACTUALIZAR LA GARANTIA SAP
    //$arrDevolver ARRAY CON LA INFORMACION A DEVOLVER
    function actualizarGarantiaSAP($idMaterialUbicacion)
    {
        global $bd;
        global $auxiliar;
        global $administrador;
        global $sap;

        //VARIABLE ARRAY A DEVOLVER
        $arrDevolver = array();

        //VARIABLE PARA CONTROLAR SI HAY ERROR EN LINEA
        $errorLinea = false;

        //VARIABLE PARA DEVOLVER LOS ERRORES
        $errores = "";

        //VARIABLE PARA MOSTRAR LOS DATOS ERRONEOS
        $datosLinea = "";

        //BUSCO EL TIPO DE BLOQUEO RETENIDO POR CALIDAD NO PREVENTIVO
        $rowTipoBloqueoRetenidoCalidadNoPreventivo = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "XRC");

        //BUSCO MATERIAL UBICACION
        $rowMaterialUbicacion = $bd->VerReg("MATERIAL_UBICACION", "ID_MATERIAL_UBICACION", $idMaterialUbicacion);

        //BUSCO LA UBICACION
        $rowUbi = $bd->VerReg("UBICACION", "ID_UBICACION", $rowMaterialUbicacion->ID_UBICACION);

        //BUSCO EL ALMACEN
        $rowAlmacen = $bd->VerReg("ALMACEN", "ID_ALMACEN", $rowUbi->ID_ALMACEN);

        //BUSCO EL CENTRO
        $rowCentro = $bd->VerReg("CENTRO", "ID_CENTRO", $rowAlmacen->ID_CENTRO);

        //BUSCO EL MATERIAL
        $rowMat = $bd->VerReg("MATERIAL", "ID_MATERIAL", $rowMaterialUbicacion->ID_MATERIAL);

        //BUSCO EL MATERIAL FISICO
        $rowMatFis = false;
        if ($rowMaterialUbicacion->ID_MATERIAL_FISICO != NULL):
            $NotificaErrorPorEmail = "No";
            $rowMatFis             = $bd->VerReg("MATERIAL_FISICO", "ID_MATERIAL_FISICO", $rowMaterialUbicacion->ID_MATERIAL_FISICO);
            unset($NotificaErrorPorEmail);
        endif;

        //BUSCO EL TIPO DE BLOQUEO
        $rowTipoBloqueo = false;
        if ($rowMaterialUbicacion->ID_TIPO_BLOQUEO != NULL):
            $NotificaErrorPorEmail = "No";
            $rowTipoBloqueo        = $bd->VerReg("TIPO_BLOQUEO", "ID_TIPO_BLOQUEO", $rowMaterialUbicacion->ID_TIPO_BLOQUEO);
            unset($NotificaErrorPorEmail);
        endif;

        //BUSCO EL ORDEN TRABAJO MOVIMIENTO
        $rowOrdenTrabajoMovimiento = false;
        if ($rowMaterialUbicacion->ID_ORDEN_TRABAJO_MOVIMIENTO != NULL):
            $NotificaErrorPorEmail     = "No";
            $rowOrdenTrabajoMovimiento = $bd->VerReg("ORDEN_TRABAJO_MOVIMIENTO", "ID_ORDEN_TRABAJO_MOVIMIENTO", $rowMaterialUbicacion->ID_ORDEN_TRABAJO_MOVIMIENTO);
            unset($NotificaErrorPorEmail);

            //BUSCO LA ORDEN DE TRABAJO
            $rowOrdenTrabajo = $bd->VerReg("ORDEN_TRABAJO", "ID_ORDEN_TRABAJO", $rowOrdenTrabajoMovimiento->ID_ORDEN_TRABAJO);
        endif;

        //BUSCO LA INCIDENCIA DE CALIDAD
        $rowIncidenciaCalidad = false;
        if ($rowMaterialUbicacion->ID_INCIDENCIA_CALIDAD != NULL):
            $NotificaErrorPorEmail = "No";
            $rowIncidenciaCalidad  = $bd->VerReg("INCIDENCIA_CALIDAD", "ID_INCIDENCIA_CALIDAD", $rowMaterialUbicacion->ID_INCIDENCIA_CALIDAD);
            unset($NotificaErrorPorEmail);
        endif;

        //CONFIGURO LOS DATOS DE LA LINEA A MOSTRAR EN CASO DE ERROR
        $datosLinea = $auxiliar->traduce("Material", $administrador->ID_IDIOMA) . ": " . $rowMat->REFERENCIA_SGA . " - " . ($administrador->ID_IDIOMA == 'ESP' ? $rowMat->DESCRIPCION : $rowMat->DESCRIPCION_EN) . "; " . $auxiliar->traduce("Ubicacion", $administrador->ID_IDIOMA) . ": " . $rowUbi->UBICACION . "; " . ($rowMatFis == false ? '' : $auxiliar->traduce(($rowMatFis->TIPO_LOTE == 'serie' ? 'Numero de serie' : 'Numero de lote'), $administrador->ID_IDIOMA) . ": " . $rowMatFis->NUMERO_SERIE_LOTE . "; ") . ($rowTipoBloqueo == false ? '' : $auxiliar->traduce("Tipo Bloqueo", $administrador->ID_IDIOMA) . ": " . $auxiliar->traduce($rowTipoBloqueo->NOMBRE_A_MOSTRAR, $administrador->ID_IDIOMA) . "; ") . ($rowOrdenTrabajoMovimiento == false ? '' : $auxiliar->traduce("Orden Trabajo", $administrador->ID_IDIOMA) . ": " . $rowOrdenTrabajo->ORDEN_TRABAJO_SAP . "; ") . ($rowIncidenciaCalidad == false ? '' : $auxiliar->traduce("Incidencia Calidad", $administrador->ID_IDIOMA) . ": " . $rowIncidenciaCalidad->ID_INCIDENCIA_CALIDAD . "; ") . "<br>";

        //COMPRUEBO QUE TENGA PERMISOS DE ESCRITURA EN EL ALMACEN
        if ($administrador->comprobarAlmacenPermiso($rowUbi->ID_ALMACEN, "Escritura") == false):
            //MARCO ERROR EN LINEA
            $errorLinea = true;

            //GUARDO EL ERROR A DEVOLVER
            $errores .= $auxiliar->traduce("No tiene permisos para realizar esta operación en esta subzona", $administrador->ID_IDIOMA) . ". " . $datosLinea;
        endif;

        //COMPRUEBO QUE EL TIPO DE BLOQUEO SEA VALIDO
        if (($rowTipoBloqueo == false) || ($rowTipoBloqueo->ID_TIPO_BLOQUEO != $rowTipoBloqueoRetenidoCalidadNoPreventivo->ID_TIPO_BLOQUEO)):
            //MARCO ERROR EN LINEA
            $errorLinea = true;

            //GUARDO EL ERROR A DEVOLVER
            $errores .= $auxiliar->traduce("Tipo de bloqueo no valido", $administrador->ID_IDIOMA) . ". " . $datosLinea;
        endif;

        //SI NO SE HAN PRODUCIDO ERRORES HAGO LA LLAMADA A SAP
        if ($errorLinea == false):
            $resultado = $sap->materialEnGarantia($rowCentro->REFERENCIA, '', '', $rowMat, $rowMatFis);
            if ($resultado['RESULTADO'] != 'OK'): //No se ha podido grabar la información en SAP
                foreach ($resultado['ERRORES'] as $arr):
                    foreach ($arr as $mensaje_error):
                        $errores = $errores . $mensaje_error . "<br>";
                    endforeach;
                endforeach;

                //INCLUYO LOS DATOS DE LA LINEA
                $errores .= $datosLinea . "<br>";

                //DESHAGO LA TRANSACCION
                $bd->rollback_transaction();

                //GRABO EL ERROR PRODUCIDO EN BASE DE DATOS
                $sap->InsertarErrores($resultado);

                //CONFIGURO EL ARRAY A DEVOLVER
                $arrDevolver["RESULTADO"] = "Error";
            else:
                $garantia = $resultado['GARANTIA'];

                //EN FUNCION DE LA GARANTIA DEVUELTA
                switch ($garantia):
                    case REPARABLE_NO_GARANTIA:
                            if ($rowMaterialUbicacion->ID_TIPO_BLOQUEO == $rowTipoBloqueoRetenidoCalidadNoPreventivo->ID_TIPO_BLOQUEO):
                                $rowTipoBloqueo = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", 'XRCRNG');
                            else:
                                $rowTipoBloqueo = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", 'QRNG');
                            endif;
                            break;
                        case REPARABLE_GARANTIA:
                            if ($rowMaterialUbicacion->ID_TIPO_BLOQUEO == $rowTipoBloqueoRetenidoCalidadNoPreventivo->ID_TIPO_BLOQUEO):
                                $rowTipoBloqueo = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", 'XRCRG');
                            else:
                                $rowTipoBloqueo = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", 'QRG');
                            endif;
                            break;
                        case NO_REPARABLE:
                            if ($rowMaterialUbicacion->ID_TIPO_BLOQUEO == $rowTipoBloqueoRetenidoCalidadNoPreventivo->ID_TIPO_BLOQUEO):
                                $rowTipoBloqueo = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", 'XRCNRNG');
                            else:
                                $rowTipoBloqueo = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", 'QNRNG');
                            endif;
                            break;
                        default:
                            //MARCO ERROR EN LINEA
                            $errorLinea = true;

                            //GUARDO EL ERROR A DEVOLVER
                            $errores .= $auxiliar->traduce("Tipo de garantia devuelto por SAP no esperado", $administrador->ID_IDIOMA) . ". " . $datosLinea;
                            break;
                    endswitch;

                //SI NO HAY ERRORES REALIZO EL CAMBIO DE ESTADO
                if ($errorLinea == false):
                    //ESTABLEZCO EL NUEVO TIPO DE BLOQUEO
                    $idTipoBloqueo = $rowTipoBloqueo->ID_TIPO_BLOQUEO;

                    //GENERO EL CAMBIO DE ESTADO
                    $sqlInsert = "INSERT INTO CAMBIO_ESTADO SET
                                    FECHA = '" . date("Y-m-d H:i:s") . "'
                                    , ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                    , ID_MATERIAL = $rowMaterialUbicacion->ID_MATERIAL
                                    , ID_MATERIAL_FISICO = " . ($rowMaterialUbicacion->ID_MATERIAL_FISICO == NULL ? "NULL" : $rowMaterialUbicacion->ID_MATERIAL_FISICO) . "
                                    , ID_UBICACION = $rowMaterialUbicacion->ID_UBICACION
                                    , CANTIDAD = $rowMaterialUbicacion->STOCK_TOTAL 
                                    , ID_TIPO_BLOQUEO_INICIAL = " . ($rowMaterialUbicacion->ID_TIPO_BLOQUEO == NULL ? "NULL" : $rowMaterialUbicacion->ID_TIPO_BLOQUEO) . "
                                    , ID_TIPO_BLOQUEO_FINAL = " . $idTipoBloqueo . "
                                    , ID_INCIDENCIA_CALIDAD = " . ($rowMaterialUbicacion->ID_INCIDENCIA_CALIDAD == NULL ? "NULL" : $rowMaterialUbicacion->ID_INCIDENCIA_CALIDAD) . "
                                    , ID_ORDEN_TRABAJO_MOVIMIENTO = " . ($rowMaterialUbicacion->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? "NULL" : $rowMaterialUbicacion->ID_ORDEN_TRABAJO_MOVIMIENTO) . "
                                    , OBSERVACIONES = ''";
                    $bd->ExecSQL($sqlInsert);//exit($sqlInsert);
                    $idCambioEstado = $bd->IdAsignado();

                    //DECREMENTO MATERIAL_UBICACION ORIGEN
                    $sqlUpdate = "UPDATE MATERIAL_UBICACION SET
                                    STOCK_TOTAL = STOCK_TOTAL - $rowMaterialUbicacion->STOCK_TOTAL 
                                    , STOCK_OK = STOCK_OK - " . ($rowMaterialUbicacion->ID_TIPO_BLOQUEO == NULL ? $rowMaterialUbicacion->STOCK_TOTAL : 0) . " 
                                    , STOCK_BLOQUEADO = STOCK_BLOQUEADO - " . ($rowMaterialUbicacion->ID_TIPO_BLOQUEO == NULL ? 0 : $rowMaterialUbicacion->STOCK_TOTAL) . " 
                                    WHERE ID_MATERIAL_UBICACION = $rowMaterialUbicacion->ID_MATERIAL_UBICACION";
                    $bd->ExecSQL($sqlUpdate);

                    //BUSCO MATERIAL_UBICACION DESTINO
                    $GLOBALS["NotificaErrorPorEmail"] = "No";
                    $clausulaWhere                    = "ID_MATERIAL = $rowMaterialUbicacion->ID_MATERIAL AND ID_UBICACION = $rowMaterialUbicacion->ID_UBICACION AND ID_MATERIAL_FISICO " . ($rowMaterialUbicacion->ID_MATERIAL_FISICO == NULL ? "IS NULL" : "= $rowMaterialUbicacion->ID_MATERIAL_FISICO") . " AND ID_TIPO_BLOQUEO = $idTipoBloqueo AND ID_ORDEN_TRABAJO_MOVIMIENTO " . ($rowMaterialUbicacion->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? "IS NULL" : "= $rowMaterialUbicacion->ID_ORDEN_TRABAJO_MOVIMIENTO") . " AND ID_INCIDENCIA_CALIDAD " . ($rowMaterialUbicacion->ID_INCIDENCIA_CALIDAD == NULL ? "IS NULL" : "= $rowMaterialUbicacion->ID_INCIDENCIA_CALIDAD");
                    $rowMaterialUbicacionDestino      = $bd->VerRegRest("MATERIAL_UBICACION", $clausulaWhere, "No");
                    unset($GLOBALS["NotificaErrorPorEmail"]);
                    if ($rowMaterialUbicacionDestino == false):
                        //CREO MATERIAL UBICACION DESTINO
                        $sqlInsert = "INSERT INTO MATERIAL_UBICACION SET
                                        ID_MATERIAL = $rowMaterialUbicacion->ID_MATERIAL
                                        , ID_UBICACION = $rowMaterialUbicacion->ID_UBICACION
                                        , ID_MATERIAL_FISICO = " . ($rowMaterialUbicacion->ID_MATERIAL_FISICO == NULL ? "NULL" : $rowMaterialUbicacion->ID_MATERIAL_FISICO) . "
                                        , ID_TIPO_BLOQUEO = $idTipoBloqueo 
                                        , ID_ORDEN_TRABAJO_MOVIMIENTO = " . ($rowMaterialUbicacion->ID_ORDEN_TRABAJO_MOVIMIENTO == NULL ? "NULL" : $rowMaterialUbicacion->ID_ORDEN_TRABAJO_MOVIMIENTO) . "
                                        , ID_INCIDENCIA_CALIDAD = " . ($rowMaterialUbicacion->ID_INCIDENCIA_CALIDAD == NULL ? "NULL" : $rowMaterialUbicacion->ID_INCIDENCIA_CALIDAD);
                        $bd->ExecSQL($sqlInsert);
                        $idMaterialUbicacionDestino = $bd->IdAsignado();
                    else:
                        $idMaterialUbicacionDestino = $rowMaterialUbicacionDestino->ID_MATERIAL_UBICACION;
                    endif;

                    //INCREMENTO MATERIAL_UBICACION DESTINO
                    $sqlUpdate = "UPDATE MATERIAL_UBICACION SET
                                    STOCK_TOTAL = STOCK_TOTAL + $rowMaterialUbicacion->STOCK_TOTAL 
                                     , STOCK_OK = STOCK_OK + " . ($idTipoBloqueo == NULL ? $rowMaterialUbicacion->STOCK_TOTAL : 0) . " 
                                    , STOCK_BLOQUEADO = STOCK_BLOQUEADO + " . ($idTipoBloqueo == NULL ? 0 : $rowMaterialUbicacion->STOCK_TOTAL) . " 
                                    WHERE ID_MATERIAL_UBICACION = $idMaterialUbicacionDestino";
                    $bd->ExecSQL($sqlUpdate);


                    //CONFIGURO EL ARRAY A DEVOLVER
                    $arrDevolver["RESULTADO"] = "Ok";
                else:
                    //CONFIGURO EL ARRAY A DEVOLVER
                    $arrDevolver["RESULTADO"] = "Error";
                endif;
                //FIN SI NO HAY ERRORES REALIZO EL CAMBIO DE ESTADO
            endif;
        else:
            //CONFIGURO EL ARRAY A DEVOLVER
            $arrDevolver["RESULTADO"] = "Error";
        endif;

        //AÑADO LOS POSIBLES ERRORES AL ARRAY
        $arrDevolver["ERRORES"] = $errores;

        //DEVUELVO EL ARRAY CORRESPONDIENTE
        return $arrDevolver;
    }

    /**
     * @param $idMaterialAlmacen
     * @param $leadTimeSuministro
     * se le pasa un material almacén y se calcula su lead time suministro en base a los valos de los lead time compra y lead time traslado de los
     * material almacén de los que depende
     */
    function calcularLeadTimeSuministro($idMaterialAlmacen, $leadTimeSuministro = 0, $idsActualizados = "", $actualizar = false){
        global $bd;
        global $html;
        global $auxiliar;
        global $administrador;

        $leadTime = 0;

        //SE OBTIENE EL MATERIAL ALMACEN
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowMaterialAlmacen = $bd->VerReg("MATERIAL_ALMACEN", "ID_MATERIAL_ALMACEN", $idMaterialAlmacen, "No");

        $arrActualizados = array();
        if($idsActualizados != ""):
            $arrActualizados = explode(',', (string)$idsActualizados);
        endif;

        if($actualizar):
            return $leadTimeSuministro;
        else:
            //SI EL MATERIAL ALMACEN A EVALUAR YA HA SIDO PROCESADO, SE SALTA
            if(in_array($rowMaterialAlmacen->ID_MATERIAL_ALMACEN, (array) $arrActualizados)):
                return $rowMaterialAlmacen->LEAD_TIME_SUMINISTRO;
            else:
                array_push($arrActualizados, $rowMaterialAlmacen->ID_MATERIAL_ALMACEN);
                $idsActualizados = implode(",", (array) $arrActualizados);

                $GLOBALS["NotificaErrorPorEmail"] = "No";
                $rowAlmacen = $bd->VerReg("ALMACEN", "ID_ALMACEN", $rowMaterialAlmacen->ID_ALMACEN, "No");

                //SI SE ENCUENTRA UN -1 ES PORQUE AL INSERTAR O MODIFICAR SE HAN DEJADO TANTO LTC COMO LTT EN VACÍO
                if($rowAlmacen->TIPO_STOCK != 'SPV' && $rowMaterialAlmacen->LEAD_TIME_SUMINISTRO != NULL && $leadTimeSuministro != -1 && $leadTimeSuministro == 0):
                    return $rowMaterialAlmacen->LEAD_TIME_SUMINISTRO;
                endif;

                if($leadTimeSuministro == -1):
                    $leadTimeSuministro = 0;
                endif;

                //SE ALMACENA EL VALOR DEL LEAD TIME QUE SEA RELEVANTE
                if($leadTimeSuministro == 0):
                    if($rowMaterialAlmacen->LEAD_TIME_COMPRA != NULL && $rowMaterialAlmacen->LEAD_TIME_COMPRA != 0):
                        $leadTime = $rowMaterialAlmacen->LEAD_TIME_COMPRA;
                    elseif($rowMaterialAlmacen->LEAD_TIME_TRASLADO != NULL && $rowMaterialAlmacen->LEAD_TIME_TRASLADO != 0):
                        $leadTime = $rowMaterialAlmacen->LEAD_TIME_TRASLADO;
                    endif;
                else:
                    $leadTime = $leadTimeSuministro;
                endif;

                //SE COMPRUEBA SI EL ALMACEN ES SPV
                if($rowAlmacen->TIPO_STOCK != NULL && $rowAlmacen->TIPO_STOCK == 'SPV'):
                    $GLOBALS["NotificaErrorPorEmail"] = "No";
                    $rowAlmacenMantenedor = $bd->VerRegRest("ALMACEN", "STOCK_COMPARTIDO = 1 AND TIPO_STOCK = 'Mantenedor' AND ID_CENTRO_FISICO = $rowAlmacen->ID_CENTRO_FISICO AND BAJA = 0", "No");
                    if($rowAlmacenMantenedor != false):
                        $GLOBALS["NotificaErrorPorEmail"] = "No";
                        $rowMaterialAlmacenSiguente = $bd->VerRegRest("MATERIAL_ALMACEN", "ID_ALMACEN = $rowAlmacenMantenedor->ID_ALMACEN AND ID_MATERIAL = $rowMaterialAlmacen->ID_MATERIAL", "No");
                        if($rowMaterialAlmacenSiguente != false):
                            if($rowMaterialAlmacenSiguente->LEAD_TIME_SUMINISTRO != NULL):
                                return $rowMaterialAlmacenSiguente->LEAD_TIME_SUMINISTRO;
                            else:
                                //SE CALCULA EL LTS Y SE ACTUALIZA
                                $lts = $this->calcularLeadTimeSuministro($rowMaterialAlmacenSiguente->ID_MATERIAL_ALMACEN, 0, $idsActualizados);
                                $sqlUpdate = "UPDATE MATERIAL_ALMACEN 
                                      SET LEAD_TIME_SUMINISTRO = " .(($lts == 0 || $lts == '') ? 'NULL' : $lts) . "
						              , ID_USUARIO_ULTIMA_MODIFICACION = '" . $administrador->ID_ADMINISTRADOR . "'
                                      WHERE ID_MATERIAL_ALMACEN = $rowMaterialAlmacenSiguente->ID_MATERIAL_ALMACEN";
                                $bd->ExecSQL($sqlUpdate);

                                //BUSCO EL REGISTRO MATERIAL-ALMACEN ACTUALIZADO
                                $rowMaterialAlmacenSiguenteActualizado = $bd->VerReg("MATERIAL_ALMACEN", "ID_MATERIAL_ALMACEN", $rowMaterialAlmacenSiguente->ID_MATERIAL_ALMACEN);

                                //LOG MOVIMIENTOS
                                $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Material Almacen", $rowMaterialAlmacenSiguente->ID_MATERIAL_ALMACEN, "Actualizacion de Lead Times", "MATERIAL_ALMACEN", $rowMaterialAlmacenSiguente, $rowMaterialAlmacenSiguenteActualizado);

                                return $lts;
                            endif;
                        else:
                            return $leadTime;
                        endif;
                    else:
                        return $leadTime;
                    endif;
                else:
                    //SI EL MATERIAL ALMACEN NO TIENE CLAVE DE APROVISIONAMIENTO SE HA LLEGADO AL FINAL Y SE DEVUELVE EL LEAD TIME RELEVANTE
                    if($rowMaterialAlmacen->ID_CLAVE_APROVISIONAMIENTO_ESPECIAL == NULL):
                        return $leadTime;
                    else:
                        //SI TIENE CLAVE DE APROVISIONAMIENTO SE OBTIENE EL MATERIAL ALMACEN SIGUIENTE
                        $GLOBALS["NotificaErrorPorEmail"] = "No";
                        $rowClaveAprovisionamientoEspecial = $bd->VerReg("CLAVE_APROVISIONAMIENTO_ESPECIAL", "ID_CLAVE_APROVISIONAMIENTO_ESPECIAL", $rowMaterialAlmacen->ID_CLAVE_APROVISIONAMIENTO_ESPECIAL, "No");
                        $GLOBALS["NotificaErrorPorEmail"] = "No";
                        $rowMaterialAlmacenSiguente = $bd->VerRegRest("MATERIAL_ALMACEN", "ID_ALMACEN = $rowClaveAprovisionamientoEspecial->ID_ALMACEN_APROVISIONAMIENTO AND ID_MATERIAL = $rowMaterialAlmacen->ID_MATERIAL", "No");
                        if($rowMaterialAlmacenSiguente != false):
                            if($rowMaterialAlmacenSiguente->LEAD_TIME_SUMINISTRO != NULL):
                                return (int) $leadTime + (int) $rowMaterialAlmacenSiguente->LEAD_TIME_SUMINISTRO;
                            else:
                                //SE CALCULA EL LTS Y SE ACTUALIZA
                                $lts = $this->calcularLeadTimeSuministro($rowMaterialAlmacenSiguente->ID_MATERIAL_ALMACEN, 0, $idsActualizados);
                                $sqlUpdate = "UPDATE MATERIAL_ALMACEN 
                                          SET LEAD_TIME_SUMINISTRO = " .(($lts == 0 || $lts == '') ? 'NULL' : $lts) . "
						                  , ID_USUARIO_ULTIMA_MODIFICACION = '" . $administrador->ID_ADMINISTRADOR . "'
                                          WHERE ID_MATERIAL_ALMACEN = $rowMaterialAlmacenSiguente->ID_MATERIAL_ALMACEN";
                                $bd->ExecSQL($sqlUpdate);

                                //BUSCO EL REGISTRO MATERIAL-ALMACEN ACTUALIZADO
                                $rowMaterialAlmacenSiguenteActualizado = $bd->VerReg("MATERIAL_ALMACEN", "ID_MATERIAL_ALMACEN", $rowMaterialAlmacenSiguente->ID_MATERIAL_ALMACEN);

                                //LOG MOVIMIENTOS
                                $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Material Almacen", $rowMaterialAlmacenSiguente->ID_MATERIAL_ALMACEN, "Actualizacion de Lead Times", "MATERIAL_ALMACEN", $rowMaterialAlmacenSiguente, $rowMaterialAlmacenSiguenteActualizado);

                                return (int) $leadTime + (int) $lts;
                            endif;
                        else:
                            return $leadTime;
                        endif;
                    endif;
                endif;
            endif;
        endif;

    }

    /**
     * @param $idMaterialAlmacen
     * se le proporciona un material_almacen para el que se haya modificado el lead time compra o lead time traslado
     * se buscan los materiales almacén cuyo lead time suministro se ve afectado por ese cambio y se actualiza
     */
    function actualizarLeadTimeSuministro($idMaterialAlmacen, $idsActualizados = ""){
        global $bd;
        global $html;
        global $auxiliar;
        global $administrador;

        $idsMatAlm = "";
        $coma = "";

        $arrActualizados = array();
        if($idsActualizados != ""):
            $arrActualizados = explode(',', (string)$idsActualizados);
        endif;

        //SI EL MATERIAL ALMACEN A EVALUAR YA HA SIDO PROCESADO, SE SALTA
        if(in_array($idMaterialAlmacen, (array) $arrActualizados)):
            return;
        else:
            array_push($arrActualizados, $idMaterialAlmacen);
            $idsActualizados = implode(",", (array) $arrActualizados);

            //SE OBTIENE EL MATERIAL ALMACEN
            $rowMaterialAlmacen = $bd->VerReg("MATERIAL_ALMACEN", "ID_MATERIAL_ALMACEN", $idMaterialAlmacen);

            //SE OBTIENE EL ALMACEN
            $rowAlmacen = $bd->VerReg("ALMACEN", "ID_ALMACEN", $rowMaterialAlmacen->ID_ALMACEN);

            //SE BUSCA EL ALMACEN EN CLAVE APROVISIONAMIENTO ESPECIAL
            $GLOBALS["NotificaErrorPorEmail"] = "No";
            $rowClaveAprovEspecial = $bd->VerReg("CLAVE_APROVISIONAMIENTO_ESPECIAL", "ID_ALMACEN_APROVISIONAMIENTO", $rowAlmacen->ID_ALMACEN, "No");
            unset($GLOBALS["NotificaErrorPorEmail"]);

            if($rowClaveAprovEspecial != false):
                //SE BUSCAN LOS MATERIAL ALMACEN RELACIONADOS CON LA CAE
                $sqlMatAlmCAE = "SELECT ID_MATERIAL_ALMACEN
                                 FROM MATERIAL_ALMACEN
                                 WHERE ID_MATERIAL = $rowMaterialAlmacen->ID_MATERIAL AND ID_CLAVE_APROVISIONAMIENTO_ESPECIAL = $rowClaveAprovEspecial->ID_CLAVE_APROVISIONAMIENTO_ESPECIAL AND BAJA = 0";
                $resultMatAlmCAE = $bd->ExecSQL($sqlMatAlmCAE);
                while($rowMatAlmCAE = $bd->SigReg($resultMatAlmCAE)):
                    $idsMatAlm .= $coma . $rowMatAlmCAE->ID_MATERIAL_ALMACEN;
                    $coma = ",";
                endwhile;
            endif;

            //SE BUSCAN LOS MATERIAL ALMACEN SPV RELACIONADOS
            $sqlMatAlmSPV = "SELECT ID_MATERIAL_ALMACEN
                             FROM MATERIAL_ALMACEN
                             WHERE ID_MATERIAL = $rowMaterialAlmacen->ID_MATERIAL AND BAJA = 0 AND ID_ALMACEN IN (
                                SELECT ID_ALMACEN
                                FROM ALMACEN
                                WHERE TIPO_STOCK = 'SPV' AND STOCK_COMPARTIDO = 1 AND ID_CENTRO_FISICO = $rowAlmacen->ID_CENTRO_FISICO AND BAJA = 0)";
            $resultMatAlmSPV = $bd->ExecSQL($sqlMatAlmSPV);
            while ($rowMatAlmSPV = $bd->SigReg($resultMatAlmSPV)):
                if($rowMatAlmSPV->ID_MATERIAL_ALMACEN != $idMaterialAlmacen):
                    $idsMatAlm .= $coma . $rowMatAlmSPV->ID_MATERIAL_ALMACEN;
                    $coma = ",";
                endif;
            endwhile;

            if($idsMatAlm != ""):
                $sqlMatAlmRelacionado = "SELECT ID_MATERIAL_ALMACEN, ID_ALMACEN, LEAD_TIME_COMPRA, LEAD_TIME_TRASLADO
                                     FROM MATERIAL_ALMACEN
                                     WHERE ID_MATERIAL_ALMACEN IN ($idsMatAlm)";
                $resultMatAlmRelacionado = $bd->ExecSQL($sqlMatAlmRelacionado);
                while($rowMatAlmRelacionado = $bd->SigReg($resultMatAlmRelacionado)):
                    $lts = $this->calcularLeadTimeSuministro($rowMatAlmRelacionado->ID_MATERIAL_ALMACEN, $rowMaterialAlmacen->LEAD_TIME_SUMINISTRO, $idsActualizados, true);

                    //SE COMPRUEBA SI EL ALMACEN RELACIONADO ES SPV
                    $rowAlmacenRelacionado = $bd->VerReg("ALMACEN", "ID_ALMACEN", $rowMatAlmRelacionado->ID_ALMACEN);

                    if($rowAlmacenRelacionado->TIPO_STOCK != 'SPV'):
                        if($rowMatAlmRelacionado->LEAD_TIME_COMPRA != NULL && $rowMatAlmRelacionado->LEAD_TIME_COMPRA != 0):
                            $lts += $rowMatAlmRelacionado->LEAD_TIME_COMPRA;
                        elseif($rowMatAlmRelacionado->LEAD_TIME_TRASLADO != NULL && $rowMatAlmRelacionado->LEAD_TIME_TRASLADO != 0):
                            $lts += $rowMatAlmRelacionado->LEAD_TIME_TRASLADO;
                        endif;
                    endif;

                    $rowMatAlmRelacionadoAntiguo = $bd->VerReg("MATERIAL_ALMACEN", "ID_MATERIAL_ALMACEN", $rowMatAlmRelacionado->ID_MATERIAL_ALMACEN);

                    //ACTUALIZAMOS LEAD TIME SUMINISTRO
                    $sqlUpdate = "UPDATE MATERIAL_ALMACEN 
                              SET LEAD_TIME_SUMINISTRO = " . (($lts == 0 || $lts == '') ? 'NULL' : $lts) . "
						      , ID_USUARIO_ULTIMA_MODIFICACION = '" . $administrador->ID_ADMINISTRADOR . "'
                              WHERE ID_MATERIAL_ALMACEN = $rowMatAlmRelacionado->ID_MATERIAL_ALMACEN";
                    $bd->ExecSQL($sqlUpdate);

                    //BUSCO EL REGISTRO MATERIAL-ALMACEN ACTUALIZADO
                    $rowMatAlmRelacionadoActualizado = $bd->VerReg("MATERIAL_ALMACEN", "ID_MATERIAL_ALMACEN", $rowMatAlmRelacionado->ID_MATERIAL_ALMACEN);

                    //LOG MOVIMIENTOS
                    $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Material Almacen", $rowMatAlmRelacionado->ID_MATERIAL_ALMACEN, "Actualizacion de Lead Times", "MATERIAL_ALMACEN", $rowMatAlmRelacionadoAntiguo, $rowMatAlmRelacionadoActualizado);

                    //SE COMPRUEBA SI AL ACTUALIZAR EL ALMACEN SE AFECTA A OTROS
                    $this->actualizarLeadTimeSuministro($rowMatAlmRelacionado->ID_MATERIAL_ALMACEN, $idsActualizados);

                endwhile;
            else:
                return;
            endif;
        endif;

        //se finaliza la ejecución
        return;

    }

    /**
     * COMPROBAMOS ESTADO DEL MATERIAL PARA LA INTEGRACIÓN DE MATERIAL REPARABLE CON SO99
     * Y ACTUALIZAMOS EN BBDD EL VALOR
     * DEVUELVE EL ORDEN TRABAJO MOVIMIENTO ACTUALIZADO O FALSE
     * @param $id
     * @param $tipoMaterial
     * @return object
     */
    function setMaterialSusceptibleRetencionSO99($id, $tipoMaterial = 'MATERIAL_UBICACION', $permitirUbicacionesEspeciales = false)
    {
        global $bd;
        global $administrador;

        //RECUPERAMOS LA INFORMACIÓN DEL ORDEN TRABAJO MOVIMIENTO Y DE SI EL ALMACEN TIENE INTEGRACIÓN CON SO99
        if (trim( (string)$tipoMaterial) == 'MATERIAL_UBICACION'):
            $sql = "SELECT MU.ID_ORDEN_TRABAJO_MOVIMIENTO, MU.ID_TIPO_BLOQUEO, OTM.TIPO_RETENCION, A.INTEGRACION_SO99_MATERIAL_REPARABLE, U.ID_UBICACION
                        FROM MATERIAL_UBICACION MU
                        INNER JOIN ORDEN_TRABAJO_MOVIMIENTO OTM ON OTM.ID_ORDEN_TRABAJO_MOVIMIENTO = MU.ID_ORDEN_TRABAJO_MOVIMIENTO
                        INNER JOIN UBICACION U ON U.ID_UBICACION = MU.ID_UBICACION
                        INNER JOIN ALMACEN A ON A.ID_ALMACEN = U.ID_ALMACEN
                        WHERE MU.ID_MATERIAL_UBICACION = $id AND MU.ID_ORDEN_TRABAJO_MOVIMIENTO IS NOT NULL AND MU.ACTIVO = 1 " . ($permitirUbicacionesEspeciales==true?"":"AND (U.TIPO_UBICACION NOT IN ('Salida', 'Embarque') OR U.TIPO_UBICACION IS NULL)") .  " AND U.BAJA = 0";
        elseif (trim( (string)$tipoMaterial) == 'MATERIAL_FISICO'):
            $sql = "SELECT MU.ID_ORDEN_TRABAJO_MOVIMIENTO, MU.ID_TIPO_BLOQUEO, OTM.TIPO_RETENCION, A.INTEGRACION_SO99_MATERIAL_REPARABLE, U.ID_UBICACION
                        FROM MATERIAL_UBICACION MU
                        INNER JOIN ORDEN_TRABAJO_MOVIMIENTO OTM ON OTM.ID_ORDEN_TRABAJO_MOVIMIENTO = MU.ID_ORDEN_TRABAJO_MOVIMIENTO
                        INNER JOIN UBICACION U ON U.ID_UBICACION = MU.ID_UBICACION
                        INNER JOIN ALMACEN A ON A.ID_ALMACEN = U.ID_ALMACEN
                        WHERE MU.ID_MATERIAL_FISICO = $id AND MU.ID_ORDEN_TRABAJO_MOVIMIENTO IS NOT NULL AND MU.ACTIVO = 1 AND (U.TIPO_UBICACION NOT IN ('Salida', 'Embarque') OR U.TIPO_UBICACION IS NULL) AND U.BAJA = 0";
        else:
            return 'Error tipo de material';
        endif;

        $result = $bd->ExecSQL($sql, "No");
        if ($bd->NumRegs($result) > 0):
            $row = $bd->SigReg($result);

            //ORDEN TRABAJO MOVIMIENTO
            $rowOTM = $bd->VerReg("ORDEN_TRABAJO_MOVIMIENTO", "ID_ORDEN_TRABAJO_MOVIMIENTO", $row->ID_ORDEN_TRABAJO_MOVIMIENTO);

            if ($rowOTM == false):
                return 'Error Orden Trabajo Movimiento';
            endif;

            //BUSCO EL TIPO DE BLOQUEO REPARABLE EN GARANTIA
            $rowBloqueoReparableEnGarantia = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "QRG");

            //BUSCO EL TIPO DE BLOQUEO REPARABLE NO EN GARANTIA
            $rowBloqueoReparableNoEnGarantia = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "QRNG");

            //BUSCO EL TIPO DE BLOQUEO NO REPARABLE NO EN GARANTIA
            $rowBloqueoNoReparableNoEnGarantia = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "QNRNG");

            //BUSCO EL TIPO DE BLOQUEO RETENIDO CALIDAD NO PREVENTIVO REPARABLE EN GARANTIA
            $rowBloqueoRetenidoCalidadNoPreventivoReparableEnGarantia = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "XRCRG");

            //BUSCO EL TIPO DE BLOQUEO RETENIDO CALIDAD NO PREVENTIVO NO REPARABLE NO EN GARANTIA
            $rowBloqueoRetenidoCalidadNoPreventivoNoReparableNoEnGarantia = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "XRCNRNG");

            //BUSCO EL TIPO DE BLOQUEO RETENIDO CALIDAD NO PREVENTIVO REPARABLE NO EN GARANTIA
            $rowBloqueoRetenidoCalidadNoPreventivoReparableNoEnGarantia = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "XRCRNG");

            if (
                ($row->ID_TIPO_BLOQUEO != $rowBloqueoReparableEnGarantia->ID_TIPO_BLOQUEO) &&
                ($row->ID_TIPO_BLOQUEO != $rowBloqueoReparableNoEnGarantia->ID_TIPO_BLOQUEO) &&
                ($row->ID_TIPO_BLOQUEO != $rowBloqueoNoReparableNoEnGarantia->ID_TIPO_BLOQUEO) &&
                ($row->ID_TIPO_BLOQUEO != $rowBloqueoRetenidoCalidadNoPreventivoReparableEnGarantia->ID_TIPO_BLOQUEO) &&
                ($row->ID_TIPO_BLOQUEO != $rowBloqueoRetenidoCalidadNoPreventivoNoReparableNoEnGarantia->ID_TIPO_BLOQUEO) &&
                ($row->ID_TIPO_BLOQUEO != $rowBloqueoRetenidoCalidadNoPreventivoReparableNoEnGarantia->ID_TIPO_BLOQUEO)
            ):
                //ACTUALIZAMOS EN BBDD EL TIPO DE RETENCIÓN
                $sqlUpdateMaterialRetenido = "UPDATE ORDEN_TRABAJO_MOVIMIENTO SET
                                                TIPO_RETENCION = NULL
                                                , FECHA_BLOQUEO = NULL
                                                , FECHA_DESBLOQUEO = NULL
                                                WHERE ID_ORDEN_TRABAJO_MOVIMIENTO = $row->ID_ORDEN_TRABAJO_MOVIMIENTO";
                $bd->ExecSQL($sqlUpdateMaterialRetenido);

                //ORDEN TRABAJO MOVIMIENTO ACTUALIZADO
                $rowOTMActualizado = $bd->VerReg("ORDEN_TRABAJO_MOVIMIENTO", "ID_ORDEN_TRABAJO_MOVIMIENTO", $row->ID_ORDEN_TRABAJO_MOVIMIENTO);

                //LOG MOVIMIENTOS
                $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Orden Trabajo Movimiento", $row->ID_ORDEN_TRABAJO_MOVIMIENTO, "Cambio de estado de la retención", "ORDEN_TRABAJO_MOVIMIENTO", $rowOTM, $rowOTMActualizado);

                return $row;
            endif;

            //SI EL ALMACEN NO TIENE INTEGRACION CON SO99
            if ($row->INTEGRACION_SO99_MATERIAL_REPARABLE == 0):
                if ($row->TIPO_RETENCION != 'Enviar Material SO99'):

                    //SI EL MATERIAL ESTABA RETENIDO LE PONGO LA FECHA DE DESBLOQUEO ACTUAL
                    if ($row->TIPO_RETENCION == 'Retenido'):
                        $fecha = date("Y-m-d H:i:s");
                        $updateFechaDesbloqueo = ", FECHA_DESBLOQUEO = '" . $fecha . "'";
                    endif;

                    //ACTUALIZAMOS EN BBDD EL TIPO DE RETENCIÓN
                    $sqlUpdateMaterialRetenido = "UPDATE ORDEN_TRABAJO_MOVIMIENTO SET
                                                    TIPO_RETENCION = 'Enviar Material SO99'
                                                    $updateFechaDesbloqueo
                                                    WHERE ID_ORDEN_TRABAJO_MOVIMIENTO = $row->ID_ORDEN_TRABAJO_MOVIMIENTO";
                    $bd->ExecSQL($sqlUpdateMaterialRetenido);

                    //ORDEN TRABAJO MOVIMIENTO ACTUALIZADO
                    $rowOTMActualizado = $bd->VerReg("ORDEN_TRABAJO_MOVIMIENTO", "ID_ORDEN_TRABAJO_MOVIMIENTO", $row->ID_ORDEN_TRABAJO_MOVIMIENTO);

                    //LOG MOVIMIENTOS
                    $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Orden Trabajo Movimiento", $row->ID_ORDEN_TRABAJO_MOVIMIENTO, "Cambio de estado de la retención", "ORDEN_TRABAJO_MOVIMIENTO", $rowOTM, $rowOTMActualizado);
                endif;
            //SI EL ALMACEN TIENE INTEGRACION
            else:
                //SI EXISTE EL ORDEN TRABAJO MOVIMIENTO CALCULAMOS EL TIPO DE RETENCIÓN SEGÚN EL TIPO DE BLOQUEO
                $tipoRetencionInicial = $row->TIPO_RETENCION;

                //CALCULAMOS EL TIPO DE RETENCION A APLICAR EN BASE AL TIPO DE BLOQUEO ACTUAL
                //TIPO DE BLOQUEO G
                if ($row->ID_TIPO_BLOQUEO == $rowBloqueoReparableEnGarantia->ID_TIPO_BLOQUEO):
                    $row->TIPO_RETENCION = 'Enviar Material SO99';

                //TIPO DE BLOQUEO R
                elseif ($row->ID_TIPO_BLOQUEO == $rowBloqueoReparableNoEnGarantia->ID_TIPO_BLOQUEO):
                    $row->TIPO_RETENCION = 'Retenido';

                //TIPO DE BLOQUEO NR
                elseif ($row->ID_TIPO_BLOQUEO == $rowBloqueoNoReparableNoEnGarantia->ID_TIPO_BLOQUEO):
                    $row->TIPO_RETENCION = 'Achatarrar';

                //TIPO DE BLOQUEO XG/XNR/XR
                elseif (($row->ID_TIPO_BLOQUEO == $rowBloqueoRetenidoCalidadNoPreventivoReparableEnGarantia->ID_TIPO_BLOQUEO) || ($row->ID_TIPO_BLOQUEO == $rowBloqueoRetenidoCalidadNoPreventivoNoReparableNoEnGarantia->ID_TIPO_BLOQUEO) || ($row->ID_TIPO_BLOQUEO == $rowBloqueoRetenidoCalidadNoPreventivoReparableNoEnGarantia->ID_TIPO_BLOQUEO)):
                    $row->TIPO_RETENCION = 'Pdte. Decision Calidad';

                //NO ES UN MATERIAL DE LOGISTICA INVERSA
                else:
                    $row->TIPO_RETENCION = NULL;
                endif;

                //COMPROBAMOS SI VA A REALIZAR UN CAMBIO DE ESTADO
                if ($row->TIPO_RETENCION != $tipoRetencionInicial):
                    $fecha = date("Y-m-d H:i:s");

                    if ($row->TIPO_RETENCION == 'Retenido'):
                        $updateFechaBloqueo = ", FECHA_BLOQUEO = '" . $fecha . "'";
                        $updateFechaDesbloqueo = ", FECHA_DESBLOQUEO = NULL";
                    endif;

                    if ($tipoRetencionInicial == 'Retenido'):
                        $updateFechaDesbloqueo = ", FECHA_DESBLOQUEO = '" . $fecha . "'";
                    endif;

                    if ($row->TIPO_RETENCION == NULL):
                        $updateFechaBloqueo = ", FECHA_BLOQUEO = NULL";
                        $updateFechaDesbloqueo = ", FECHA_DESBLOQUEO = NULL";
                    endif;

                    //ACTUALIZAMOS EN BBDD EL TIPO DE RETENCIÓN
                    $sqlUpdateMaterialRetenido = "UPDATE ORDEN_TRABAJO_MOVIMIENTO SET
                                                    TIPO_RETENCION = " . ($row->TIPO_RETENCION == NULL ? 'NULL' : "'" . $row->TIPO_RETENCION . "'") . "
                                                    $updateFechaBloqueo
                                                    $updateFechaDesbloqueo
                                                    WHERE ID_ORDEN_TRABAJO_MOVIMIENTO = $row->ID_ORDEN_TRABAJO_MOVIMIENTO";
                    $bd->ExecSQL($sqlUpdateMaterialRetenido);

                    //ORDEN TRABAJO MOVIMIENTO ACTUALIZADO
                    $rowOTMActualizado = $bd->VerReg("ORDEN_TRABAJO_MOVIMIENTO", "ID_ORDEN_TRABAJO_MOVIMIENTO", $row->ID_ORDEN_TRABAJO_MOVIMIENTO);

                    //LOG MOVIMIENTOS
                    $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Orden Trabajo Movimiento", $row->ID_ORDEN_TRABAJO_MOVIMIENTO, "Cambio de estado de la retención", "ORDEN_TRABAJO_MOVIMIENTO", $rowOTM, $rowOTMActualizado);
                endif;
            endif;

            return $row;
        else:
            //SI NO ENCONTRAMOS ORDEN TRABAJO MOVIMIENTO
            return false;
        endif;
    }
    /**
     * ACTUALIZA LA FECHA DEL ORDEN TRABAJO MOVIMIENTO CUANDO ENTRA EN EL ALMACEN
     * @param $idMaterialFisico
     */
    function actualizarFechaEntradaAlmacen($idMatUbicacion)
    {
        global $bd;
        global $administrador;

        //COMPRUEBO QUE TIENE STOCK EL MATERIAL. SI ES MAYOR A CERO ACTUALIZO LA FECHA
        $sqlStock = "SELECT MU.ID_ORDEN_TRABAJO_MOVIMIENTO
                        FROM MATERIAL_UBICACION MU
                        WHERE MU.ID_MATERIAL_UBICACION = $idMatUbicacion AND MU.ID_ORDEN_TRABAJO_MOVIMIENTO IS NOT NULL AND MU.ACTIVO = 1";
        $resultStock = $bd->ExecSQL($sqlStock, "No");
        if ($bd->NumRegs($resultStock) > 0):
            $row = $bd->SigReg($resultStock);

            //ORDEN TRABAJO MOVIMIENTO
            $rowOTM = $bd->VerReg("ORDEN_TRABAJO_MOVIMIENTO", "ID_ORDEN_TRABAJO_MOVIMIENTO", $row->ID_ORDEN_TRABAJO_MOVIMIENTO);

            $fecha = date("Y-m-d H:i:s");
            $sql = "UPDATE ORDEN_TRABAJO_MOVIMIENTO SET FECHA_ENTRADA_ALMACEN = '$fecha' WHERE ID_ORDEN_TRABAJO_MOVIMIENTO = $row->ID_ORDEN_TRABAJO_MOVIMIENTO";
            $bd->ExecSQL($sql);

            //ORDEN TRABAJO MOVIMIENTO ACTUALIZADO
            $rowOTMActualizado = $bd->VerReg("ORDEN_TRABAJO_MOVIMIENTO", "ID_ORDEN_TRABAJO_MOVIMIENTO", $row->ID_ORDEN_TRABAJO_MOVIMIENTO);

            //LOG MOVIMIENTOS
            $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Orden Trabajo Movimiento", $row->ID_ORDEN_TRABAJO_MOVIMIENTO, "Actualización fecha de entrada en el almacén", "ORDEN_TRABAJO_MOVIMIENTO", $rowOTM, $rowOTMActualizado);
        endif;
    }

    /**
     * DEVUELVE EL STRING CON LA ABREVIATURA DEL TIPO DE RETENCIÓN PARA MOSTRAR EN LOS LISTADOS
     * @param $tipoRetencion
     * @return string
     */
    function getAbreviaturaTipoRetencion($tipoRetencion)
    {
        switch ($tipoRetencion):
            case 'Enviar Material SO99':
                $tipoRetencion = 'E';
                break;
            case 'Retenido':
                $tipoRetencion = 'R';
                break;
            case 'Pdte. Decision Calidad':
                $tipoRetencion = 'PDC';
                break;
            case 'Achatarrar':
                $tipoRetencion = 'A';
                break;
        endswitch;

        return $tipoRetencion;
    }

    /**
     * DEVUELVE UN STRING CON TODOS LOS OTMs CON LOS TIPOS DE RETENCIÓN INDICADOS
     * @param $tipoRetencion
     * @return string
     */
    function getOTMsPorTipoRetencion($arrTiposRetencion)
    {
        global $bd;

        $listaTiposRetencion = implode("', '", (array) $arrTiposRetencion);
        $listaOTMs = "0";

        //RECORRO LAS LINEAS DE ORDEN TRABAJO MOVIMIENTO
        $sqlLineas    = "SELECT ID_ORDEN_TRABAJO_MOVIMIENTO
                          FROM ORDEN_TRABAJO_MOVIMIENTO
                          WHERE TIPO_RETENCION IN ('$listaTiposRetencion')";
        $resultLineas = $bd->ExecSQL($sqlLineas);
        while ($rowLinea = $bd->SigReg($resultLineas)):
            if ($listaOTMs == "0"):
                $listaOTMs = $rowLinea->ID_ORDEN_TRABAJO_MOVIMIENTO;
            else:
                $listaOTMs .= ", $rowLinea->ID_ORDEN_TRABAJO_MOVIMIENTO";
            endif;
        endwhile;

        return $listaOTMs;
    }

    /**
     * DEVUELVE UN STRING CON EL TIPO DE GARANTIA
     * @param $tipoGarantia
     * @return string
     */
    function getTipoGarantiaSAP($idTipoGarantiaSAP)
    {
        global $bd;

        $tipoGarantia = "-";
        if($idTipoGarantiaSAP != null):
            $rowTipoGarantia = $bd->VerReg("TIPO_GARANTIA_SAP", "ID_TIPO_GARANTIA_SAP", $idTipoGarantiaSAP);
            $tipoGarantia = $rowTipoGarantia->TIPO_GARANTIA_SAP . " - " . $rowTipoGarantia->DESCRIPCION_TIPO_GARANTIA_SAP;
        endif;

        return $tipoGarantia;
    }

    /**
     * @param $idMaterial
     * @param $selTipoParticionAutostore
     * @param $txCantidadAutostore
     * @return TRUE SI CABE EN AT, FALSE SI NO
     */
    function comprobarMedidasPesoAutoStore($idMaterial, $selTipoParticionAutostore, $txCantidadAutostore)
    {

        global $bd;
        global $html;
        global $auxiliar;

        //COMPROBAMOS QUE CABE EN AUTOSTORE
        $volumenMaxAutoStore = MAX_VOLUMEN_AUTOSTORE;//ESTA EN CCM3
        $pesoMaxAutoStore    = MAX_PESO_AUTOSTORE;   //ESTA EN KG

        //OBTENGO EL MATERIAL
        $rowMaterial = $bd->VerReg("MATERIAL", "ID_MATERIAL", $idMaterial);

        //BUSCAMOS LAS UNIDADES
        $rowUnidadGramos  = $bd->VerReg("UNIDAD", "UNIDAD", "G");
        $rowUnidadMetros3 = $bd->VerReg("UNIDAD", "UNIDAD", "M3");


        $pesoBruto = $rowMaterial->PESO_BRUTO;
        $volumen   = $rowMaterial->VOLUMEN;

        //SE CONVIERTEN LOS VALORES
        if ($rowMaterial->ID_UNIDAD_PESO == $rowUnidadGramos->ID_UNIDAD):
            $pesoBruto = $pesoBruto / 1000;
        endif;

        //SI M3 A CM3
        if ($rowMaterial->ID_UNIDAD_VOLUMEN == $rowUnidadMetros3->ID_UNIDAD):
            $volumen = $volumen * (100 * 100 * 100);
        endif;

        switch ($selTipoParticionAutostore):
            case '1/2':
                $volumenMaxAutoStore = $volumenMaxAutoStore / 2;
                $pesoMaxAutoStore    = $pesoMaxAutoStore / 2;
                break;
            case '1/4':
                $volumenMaxAutoStore = $volumenMaxAutoStore / 4;
                $pesoMaxAutoStore    = $pesoMaxAutoStore / 4;
                break;
            case '1/8' :
                $volumenMaxAutoStore = $volumenMaxAutoStore / 8;
                $pesoMaxAutoStore    = $pesoMaxAutoStore / 8;
                break;
            default:
                break;
        endswitch;

        $maxUnidadesPeso    = 0;
        $maxUnidadesVolumen = 0;
        if ($pesoBruto > 0):
            $maxUnidadesPeso = floor($pesoMaxAutoStore / $pesoBruto);
        endif;

        if ($volumen > 0):
            $maxUnidadesVolumen = floor($volumenMaxAutoStore / $volumen);
        endif;

        //NOS QUEDAMOS CON EL MINIMO
        $maxUnidades = min($maxUnidadesPeso, $maxUnidadesVolumen);

        if (($txCantidadAutostore - $maxUnidades) > EPSILON_SISTEMA):
            return false;
        endif;

        return true;
    }

    /**
     * FUNCION PARA CALCULAR LA CANTIDAD A TRANSMITIR A SAP EN UN INVENTARIO/CONTEO
     * @param $idAlmacen ID del almacen
     * @param $idMaterial ID del material
     * @param $idMaterialFisico ID del material fisico, NULL si no se seriable/lotable
     * @param $tipoStock Tipo de stock a calcular (Libre, Bloqueado o Calidad)
     */
    function getCantidadAlmacenTransmitirSAP($idAlmacen, $idMaterial, $idMaterialFisico, $tipoStock)
    {
        global $bd;

        //VARIABLE PARA CALCULAR LA CANTIDAD A DEVOLVER
        $cantidadDevolver = NULL; //NULL ES QUE HA HABIDO PROBLEMAS

        // BUSCO DIFERENTES TIPOS DE BLOQUEO A UTILIZAR
        //BUSCO EL TIPO DE BLOQUEO RETENIDO POR CALIDAD NO PREVENTIVO
        $rowTipoBloqueoRetenidoCalidadNoPreventivo = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "XRC");
        //BUSCO EL TIPO DE BLOQUEO PREVENTIVO
        $rowTipoBloqueoPreventivo = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "SP");
        //BUSCO EL TIPO DE BLOQUEO LOTE CADUCADO
        $rowTipoBloqueoLoteCaducado = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "LC");
        //BUSCO EL TIPO DE BLOQUEO RETENIDO POR CALIDAD PREVENTIVO
        $rowTipoBloqueoRetenidoCalidadPreventivo = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "XRCP");
        //BUSCO EL TIPO DE BLOQUEO VEHICULO
        $rowTipoBloqueoVehiculo = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "VH");
        //BUSCO EL TIPO DE BLOQUEO RETENIDO POR CALIDAD NO PREVENTIVO REPARABLE NO GARANTIA
        $rowTipoBloqueoRetenidoCalidadNoPreventivoReparableNoEnGarantia = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "XRCRNG");
        //BUSCO EL TIPO DE BLOQUEO RETENIDO POR CALIDAD NO PREVENTIVO REPARABLE GARANTIA
        $rowTipoBloqueoRetenidoCalidadNoPreventivoReparableEnGarantia = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "XRCRG");
        //BUSCO EL TIPO DE BLOQUEO RETENIDO POR CALIDAD NO PREVENTIVO NO REPARABLE NO GARANTIA
        $rowTipoBloqueoRetenidoCalidadNoPreventivoNoReparableNoEnGarantia = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "XRCNRNG");
        //BUSCO EL TIPO DE BLOQUEO BLOQUEADO POR LOGISTICA
        $rowTipoBloqueoBloqueadoPorLogistica = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "XBL");
        //BUSCO EL TIPO DE BLOQUEO RESERVADO PARA PREPARACION
        $rowTipoBloqueoReservadoParaPreparacion = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "RP");
        //BUSCO EL TIPO DE BLOQUEO RESERVADO PARA PREPARACION PREVENTIVO
        $rowTipoBloqueoReservadoParaPreparacionPreventivo = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "RPP");
        //BUSCO EL TIPO DE BLOQUEO RESERVADO
        $rowTipoBloqueoReservado            = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "RV");
        //BUSCO EL TIPO DE BLOQUEO RESERVADO PREVENTIVO
        $rowTipoBloqueoReservadoPlanificado = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "RVP");
        //BUSCO EL TIPO DE BLOQUEO INCOMPLETO
        $rowTipoBloqueoIncompleto = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "INC");
        // FIN BUSCO DIFERENTES TIPOS DE BLOQUEO A UTILIZAR

        //DECLARO LOS TIPOS DE STOCK
        $listaTipoBloqueoLibre = $rowTipoBloqueoVehiculo->ID_TIPO_BLOQUEO . "," . $rowTipoBloqueoReservadoParaPreparacion->ID_TIPO_BLOQUEO . "," . $rowTipoBloqueoReservado->ID_TIPO_BLOQUEO . "," . $rowTipoBloqueoIncompleto->ID_TIPO_BLOQUEO;
        $listaTipoBloqueoBloqueado = $rowTipoBloqueoPreventivo->ID_TIPO_BLOQUEO . "," . $rowTipoBloqueoLoteCaducado->ID_TIPO_BLOQUEO . "," . $rowTipoBloqueoBloqueadoPorLogistica->ID_TIPO_BLOQUEO . "," . $rowTipoBloqueoReservadoParaPreparacionPreventivo->ID_TIPO_BLOQUEO . "," . $rowTipoBloqueoReservadoPlanificado->ID_TIPO_BLOQUEO;
        $listaTipoBloqueoCalidad = $rowTipoBloqueoRetenidoCalidadNoPreventivo->ID_TIPO_BLOQUEO . "," . $rowTipoBloqueoRetenidoCalidadPreventivo->ID_TIPO_BLOQUEO . "," . $rowTipoBloqueoRetenidoCalidadNoPreventivoReparableNoEnGarantia->ID_TIPO_BLOQUEO . "," . $rowTipoBloqueoRetenidoCalidadNoPreventivoReparableEnGarantia->ID_TIPO_BLOQUEO . "," . $rowTipoBloqueoRetenidoCalidadNoPreventivoNoReparableNoEnGarantia->ID_TIPO_BLOQUEO;

        //VARIABLE PARA DETECTAR SI HAY QUE EJECUTAR LA CONSULTA
        $ejecutarConsultaStock = true;

        //CALCULO EL STOCK POR TIPO DE STOCK
        if ($tipoStock == "Libre"):
            $sqlWhere = "(ID_TIPO_BLOQUEO IS NULL OR ID_TIPO_BLOQUEO IN ($listaTipoBloqueoLibre))";
        elseif ($tipoStock == "Bloqueado"):
            $sqlWhere = "(ID_TIPO_BLOQUEO IN ($listaTipoBloqueoBloqueado))";
        elseif ($tipoStock == "Calidad"):
            $sqlWhere = "(ID_TIPO_BLOQUEO IN ($listaTipoBloqueoCalidad))";
        else:
            //SI NO SE HA DEFINIDO UN TIPO STOCK VALIDO NO EJECUTAMOS LA CONSULTA PARA CALCULARLO
            $ejecutarConsultaStock = false;
        endif;

        //SI SE DEBE EJECUTAR LA CONSULTA
        if ($ejecutarConsultaStock == true):
            //CONSULTA DE STOCK
            $sqlConsultaStock = "SELECT IF(SUM(STOCK_TOTAL) IS NULL, 0, SUM(STOCK_TOTAL)) AS CANTIDAD_ALMACEN
                                 FROM MATERIAL_UBICACION MU
                                 INNER JOIN UBICACION U ON U.ID_UBICACION = MU.ID_UBICACION
                                 WHERE MU.ID_MATERIAL = $idMaterial AND U.ID_ALMACEN = $idAlmacen AND ID_MATERIAL_FISICO " . ($idMaterialFisico == NULL ? 'IS NULL' : "= $idMaterialFisico") . " AND (U.TIPO_UBICACION <> 'Embarque' OR U.TIPO_UBICACION IS NULL) AND $sqlWhere
                                 GROUP BY U.ID_ALMACEN";
            $resultConsultaStock = $bd->ExecSQL($sqlConsultaStock);
            if (($resultConsultaStock != false) && ($bd->NumRegs($resultConsultaStock) > 0)):
                $rowConsultaStock = $bd->SigReg($resultConsultaStock);
                $cantidadDevolver = $rowConsultaStock->CANTIDAD_ALMACEN;
            else:
                $cantidadDevolver = 0;
            endif;
        endif;
        //FIN SI SE DEBE EJECUTAR LA CONSULTA

        //DEVOLVEMOS LA CANTIDAD A TRANSMITIR A SAP
        return $cantidadDevolver;
    }

    function getTipoStockSAP($idTipoBloqueo)
    {
        global $bd;

        //VARIABLE PARA DEVOLVER EL TIPO STOCK DE SAP
        $tipoStockSAP = NULL; //NULL ES QUE HA HABIDO PROBLEMAS

        // BUSCO DIFERENTES TIPOS DE BLOQUEO A UTILIZAR
        //BUSCO EL TIPO DE BLOQUEO RETENIDO POR CALIDAD NO PREVENTIVO
        $rowTipoBloqueoRetenidoCalidadNoPreventivo = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "XRC");
        //BUSCO EL TIPO DE BLOQUEO PREVENTIVO
        $rowTipoBloqueoPreventivo = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "SP");
        //BUSCO EL TIPO DE BLOQUEO LOTE CADUCADO
        $rowTipoBloqueoLoteCaducado = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "LC");
        //BUSCO EL TIPO DE BLOQUEO RETENIDO POR CALIDAD PREVENTIVO
        $rowTipoBloqueoRetenidoCalidadPreventivo = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "XRCP");
        //BUSCO EL TIPO DE BLOQUEO VEHICULO
        $rowTipoBloqueoVehiculo = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "VH");
        //BUSCO EL TIPO DE BLOQUEO RETENIDO POR CALIDAD NO PREVENTIVO REPARABLE NO GARANTIA
        $rowTipoBloqueoRetenidoCalidadNoPreventivoReparableNoEnGarantia = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "XRCRNG");
        //BUSCO EL TIPO DE BLOQUEO RETENIDO POR CALIDAD NO PREVENTIVO REPARABLE GARANTIA
        $rowTipoBloqueoRetenidoCalidadNoPreventivoReparableEnGarantia = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "XRCRG");
        //BUSCO EL TIPO DE BLOQUEO RETENIDO POR CALIDAD NO PREVENTIVO NO REPARABLE NO GARANTIA
        $rowTipoBloqueoRetenidoCalidadNoPreventivoNoReparableNoEnGarantia = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "XRCNRNG");
        //BUSCO EL TIPO DE BLOQUEO BLOQUEADO POR LOGISTICA
        $rowTipoBloqueoBloqueadoPorLogistica = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "XBL");
        //BUSCO EL TIPO DE BLOQUEO RESERVADO PARA PREPARACION
        $rowTipoBloqueoReservadoParaPreparacion = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "RP");
        //BUSCO EL TIPO DE BLOQUEO RESERVADO PARA PREPARACION PREVENTIVO
        $rowTipoBloqueoReservadoParaPreparacionPreventivo = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "RPP");
        //BUSCO EL TIPO DE BLOQUEO RESERVADO
        $rowTipoBloqueoReservado            = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "RV");
        //BUSCO EL TIPO DE BLOQUEO RESERVADO PREVENTIVO
        $rowTipoBloqueoReservadoPlanificado = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "RVP");
        //BUSCO EL TIPO DE BLOQUEO INCOMPLETO
        $rowTipoBloqueoIncompleto = $bd->VerReg("TIPO_BLOQUEO", "TIPO_BLOQUEO_INTERNO", "INC");
        // FIN BUSCO DIFERENTES TIPOS DE BLOQUEO A UTILIZAR

        //CALCULO EL TIPO DE STOCK SAP
        if (
                ($idTipoBloqueo == NULL) ||
                ($idTipoBloqueo == $rowTipoBloqueoVehiculo->ID_TIPO_BLOQUEO) ||
                ($idTipoBloqueo == $rowTipoBloqueoReservadoParaPreparacion->ID_TIPO_BLOQUEO) ||
                ($idTipoBloqueo == $rowTipoBloqueoReservado->ID_TIPO_BLOQUEO) ||
                ($idTipoBloqueo == $rowTipoBloqueoIncompleto->ID_TIPO_BLOQUEO)
           ):
            $tipoStockSAP = "Libre";
        elseif (
                ($idTipoBloqueo == $rowTipoBloqueoPreventivo->ID_TIPO_BLOQUEO) ||
                ($idTipoBloqueo == $rowTipoBloqueoLoteCaducado->ID_TIPO_BLOQUEO) ||
                ($idTipoBloqueo == $rowTipoBloqueoBloqueadoPorLogistica->ID_TIPO_BLOQUEO) ||
                ($idTipoBloqueo == $rowTipoBloqueoReservadoParaPreparacionPreventivo->ID_TIPO_BLOQUEO) ||
                ($idTipoBloqueo == $rowTipoBloqueoReservadoPlanificado->ID_TIPO_BLOQUEO)
               ):
            $tipoStockSAP = "Bloqueado";
        elseif (
                ($idTipoBloqueo == $rowTipoBloqueoRetenidoCalidadNoPreventivo->ID_TIPO_BLOQUEO) ||
                ($idTipoBloqueo == $rowTipoBloqueoRetenidoCalidadPreventivo->ID_TIPO_BLOQUEO) ||
                ($idTipoBloqueo == $rowTipoBloqueoRetenidoCalidadNoPreventivoReparableNoEnGarantia->ID_TIPO_BLOQUEO) ||
                ($idTipoBloqueo == $rowTipoBloqueoRetenidoCalidadNoPreventivoReparableEnGarantia->ID_TIPO_BLOQUEO) ||
                ($idTipoBloqueo == $rowTipoBloqueoRetenidoCalidadNoPreventivoNoReparableNoEnGarantia->ID_TIPO_BLOQUEO)
               ):
            $tipoStockSAP = "Calidad";
        endif;

        //DEVOLVEMOS EL TIPO STOCK SAP
        return $tipoStockSAP;
    }



    /**
     * FUNCION PARA CREAR LOS MATERIAL CENTRO Y MATERIAL ALMACEN DE UN MATERIAL OBSOLETO EN UNO SUSTITUTO
     * @param $idMaterialObsoleto
     * @param $idMaterialSustiuto
     * @return objSalida (un objeto con el array de centros y almacenes con sus datos)
     */
    function ampliarAlmacenesSustituto($idMaterialObsoleto,$idMaterialSustiuto)
    {
        global $bd;
        global $administrador;
        //COMPRUEBO QUE EXISTAN AMBOS MATERIALES
        $rowMatObs = $bd->VerReg("MATERIAL", "ID_MATERIAL", $idMaterialObsoleto);
        $rowMatSus = $bd->VerReg("MATERIAL", "ID_MATERIAL", $idMaterialSustiuto);

        //CREO UN OBJETO PARA RELLENAR CON LSO DATOS DE LOS MATERIAL CENTRO Y MATERIAL ALMACEN
        $objSalida = new stdClass();
        $CENTROS = array();
        $ALMACENES = array();
        $objLinea = new stdClass();

        $sqlMatCentrosObs = "SELECT MC.*,C.BAJA,C.INTEGRACION_CON_SAP FROM MATERIAL_CENTRO MC INNER JOIN CENTRO C ON MC.ID_CENTRO = C.ID_CENTRO WHERE MC.ID_MATERIAL = $idMaterialObsoleto AND C.BAJA = 0 AND C.INTEGRACION_CON_SAP = 1";
        $resMatCentrosObs = $bd->ExecSQL($sqlMatCentrosObs);
        while ($rowMatCentroObs = $bd->SigReg($resMatCentrosObs)):
            $GLOBALS['NotificaErrorPorEmail'] = "No";
            $rowMatCentroSus = $bd->VerRegRest("MATERIAL_CENTRO", "ID_MATERIAL = $idMaterialSustiuto AND ID_CENTRO = $rowMatCentroObs->ID_CENTRO", "No");
            if (!$rowMatCentroSus)://SI EL MATERIAL CENTRO NO EXISTE LO CREO
                //LIMPIO LOS DATOS DEL OBJETO
                unset($objLinea);
                //CREO LOS DATOS DEL MATERIAL-CENTRO CON LOS DATOS DEL OBSOLETO
                $objLinea = new stdClass();

                //REFERENCIA DEL CENTRO
                $rowCentro = $bd->VerReg("CENTRO", "ID_CENTRO", $rowMatCentroObs->ID_CENTRO);
                $objLinea->Plant_Code = $rowCentro->REFERENCIA;
                //MONEDA Y COSTE ESTIMADO
                $rowSociedad = $bd->VerReg("SOCIEDAD", "ID_SOCIEDAD", $rowCentro->ID_SOCIEDAD);
                $rowMoneda = $bd->VerReg("MONEDA", "ID_MONEDA", $rowSociedad->ID_MONEDA);
                $objLinea->Estimated_Price = $rowMatCentroObs->COSTE;
                $objLinea->Currency = $rowMoneda->MONEDA;
                //GRUPO DE COMPRA
                if($rowMatCentroObs->ID_GRUPO_COMPRA != NULL):
                    $rowGrupoCompra = $bd->VerReg("GRUPO_COMPRA", "ID_GRUPO_COMPRA", $rowMatCentroObs->ID_GRUPO_COMPRA);
                    $objLinea->Purchasing_Group = $rowGrupoCompra->CODIGO;
                else:
                    $objLinea->Purchasing_Group = "";
                endif;
                //PLANIFICADO
                $objLinea->MRP_Type_2 = "ND";
                $objLinea->Automatic_PO_allowed = "X";
                //NUMERO DE LOTE REQUERIDO
                $objLinea->Batch_req_Ind = $rowMatCentroObs->TIPO_LOTE != "ninguno" ? "X" : "";
                //PERFIL DE NUMERO DE SERIE
                $objLinea->Serial_Number_Profile = $rowMatCentroObs->TIPO_LOTE == "serie" ? "0003" : "";
                //CATEGORIA DE VALORACION
                $objLinea->Valuation_Category = $rowMatCentroObs->TIPO_LOTE == "serie" ? "X" : "";
                //COMPROBACION GRUPO DE DISPONIBILIDAD
                $objLinea->Checking_Group_Availability_Check = "01";
                //CANAL DE DISTRIBUCION
                $objLinea->Distribution_Channel = $rowMatCentroObs->CANAL_DISTRIBUCION;
                //UNIDAD DE COMPRA
                $rowUnidadVenta = $bd->VerReg("UNIDAD", "ID_UNIDAD", $rowMatCentroObs->ID_UNIDAD_MEDIDA_VENTA);
                $rowUnidadPedido = $bd->VerReg("UNIDAD", "ID_UNIDAD", $rowMatCentroObs->ID_UNIDAD_MEDIDA_PEDIDO);
                $objLinea->UMS = $rowUnidadVenta->UNIDAD;
                $objLinea->Unit_Measure_Order = $rowUnidadPedido->UNIDAD;
                //NUMERADOR DE CANTIDAD COMPRA
                $objLinea->Numerator = $rowMatSus->NUMERADOR_CONVERSION;
                //DENOMINADOR DE CANIDAD COMPRA
                $objLinea->Denominator = $rowMatSus->DENOMINADOR_CONVERSION;
                //PAIS DE IMPUESTOS
                $objLinea->Tax_Country = "ES";
                //TIPO DE IMPUESTOS
                $objLinea->Tax_Class_1 = "1";
                $rowPais = $bd->VerReg("PAIS", "ID_PAIS", $rowSociedad->ID_PAIS);
                $objLinea->Tax_Class_2 = ($rowPais->PAIS == "UA" || $rowPais->PAIS == "US") ? "1" : "";
                $objLinea->Tax_Class_3 = ($rowPais->PAIS == "UA" || $rowPais->PAIS == "US") ? "1" : "";
                //ASIGNACION DE CUENTA
                $objLinea->Account_Assigment = "01";
                //GRUPO DE CARGA
                $objLinea->Loading_Group = "0001";
                //TIPO DE PROC
                $objLinea->Proc_Type = "F";
                //CODIGO HS
                $GLOBAL["NotificaErrorPorEmail"] = "No";
                $rowCodigoHSMatSus = $bd->VerRegRest("MATERIAL_CENTRO", "ID_MATERIAL = $idMaterialSustiuto", "No");
                if($rowCodigoHSMatSus):
                    if($rowCodigoHSMatSus->ID_CODIGO_HS != NULL):
                        $rowCodigoHS = $bd->VerReg("CODIGO_HS", "ID_CODIGO_HS", $rowCodigoHSMatSus->ID_CODIGO_HS, "No");
                        $objLinea->HS_Code = $rowCodigoHS->CODIGO_HS;
                    else:
                        $objLinea->HS_Code = "";
                    endif;
                else:
                    $rowCodigoHS = $bd->VerReg("CODIGO_HS", "ID_CODIGO_HS", $rowMatCentroObs->ID_CODIGO_HS, "No");
                    $objLinea->HS_Code = $rowCodigoHS->CODIGO_HS;
                endif;
                //CLASE DE VALORACION
                $objLinea->Valuation_Class = $rowMatCentroObs->CATEGORIA_VALORACION;
                //ORGANIZACION DE VENTAS
                $objLinea->Sales_Org = $rowMatCentroObs->ORGANIZACION_VENTAS;


                //COMPRUEBO EL TIPO DE MATERIAL
                if ($rowMatObs->TIPO_MATERIAL != $rowMatSus->TIPO_MATERIAL)://SI SON DIFERENTE COJO LOS DATOS DE LAS LUT
                    //CLASE DE VALORACION
                    $rowTipoValoracion = $bd->VerReg("VALORACION_TIPO_MATERIAL", "TIPO_MATERIAL", $rowMatSus->TIPO_MATERIAL);
                    $objLinea->Valuation_Class = $rowTipoValoracion->VALORACION;
                endif;
                //COMPRUEBO TIPO LOTE
                $GLOBAL["NotificaErrorPorEmail"] = "No";
                $rowSeriableMatSus = $bd->VerRegRest("MATERIAL_CENTRO", "ID_MATERIAL = $idMaterialSustiuto", "No");
                if ($rowSeriableMatSus):
                    if ($rowSeriableMatSus->TIPO_LOTE != $rowMatCentroObs->TIPO_LOTE):
                        //NUMERO DE LOTE REQUERIDO
                        $objLinea->Batch_req_Ind = $rowSeriableMatSus->TIPO_LOTE != "ninguno" ? "X" : "";
                        //PERFIL DE NUMERO DE SERIE
                        $objLinea->Serial_Number_Profile = $rowSeriableMatSus->TIPO_LOTE == "serie" ? "0003" : "";
                        //CATEGORIA DE VALORACION
                        $objLinea->Valuation_Category = $rowSeriableMatSus->TIPO_LOTE == "serie" ? "X" : "";
                    endif;
                endif;
                //COMPRUEBO UNIDAD BASE
                if ($rowMatObs->ID_UNIDAD_MEDIDA != $rowMatSus->ID_UNIDAD_MEDIDA):
                    $rowUnidadCompraSus = $bd->VerReg("UNIDAD", "ID_UNIDAD", $rowMatSus->ID_UNIDAD_COMPRA);
                    //UNIDAD DE COMPRA
                    $objLinea->UMS = $rowUnidadCompraSus->UNIDAD;
                    $objLinea->Unit_Measure_Order = $rowUnidadCompraSus->UNIDAD;
                endif;
                //COMPRUEBO FORMATO COMPRA
                if ($rowMatObs->ID_UNIDAD_COMPRA != $rowMatSus->ID_UNIDAD_COMPRA):
                    $rowUnidadCompraSus = $bd->VerReg("UNIDAD", "ID_UNIDAD", $rowMatSus->ID_UNIDAD_COMPRA);
                    //UNIDAD DE COMPRA
                    $objLinea->UMS = $rowUnidadCompraSus->UNIDAD;
                    $objLinea->Unit_Measure_Order = $rowUnidadCompraSus->UNIDAD;
                endif;
                //COMPRUEBO CANTIDAD POR FORMATO
                if ($rowMatObs->NUMERADOR_CONVERSION != $rowMatSus->NUMERADOR_CONVERSION || $rowMatObs->DENOMINADOR_CONVERSION != $rowMatSus->DENOMINADOR_CONVERSION):
                    $rowISTipo = $bd->VerReg("INCIDENCIA_SISTEMA_TIPO", "INCIDENCIA_SISTEMA_TIPO", "Codificación", "No");
                    $rowISSubTipo = $bd->VerReg("INCIDENCIA_SISTEMA_SUBTIPO", "INCIDENCIA_SISTEMA_SUBTIPO", "Material centro no creado", "No");

                    $sqlInsert = "INSERT INTO INCIDENCIA_SISTEMA SET
                                          ID_INCIDENCIA_SISTEMA_TIPO = $rowISTipo->ID_INCIDENCIA_SISTEMA_TIPO
                                          , TIPO = 'Codificación'
                                          , ID_INCIDENCIA_SISTEMA_SUBTIPO = $rowISSubTipo->ID_INCIDENCIA_SISTEMA_SUBTIPO
                                          , SUBTIPO = 'Material centro no creado'
                                          , ESTADO = 'Creada'
                                          , TABLA_OBJETO = 'MATERIAL'
                                          , ID_OBJETO = '$idMaterialSustiuto'
                                          , FECHA_CREACION = '" . date("Y-m-d H:i:s") . "'
                                          , FECHA_RESOLUCION = '0000-00-00 00:00:00'
                                          , ID_ADMINISTRADOR_CREACION = $administrador->ID_ADMINISTRADOR
                                          , ID_ADMINISTRADOR_ULTIMA_MODIFICACION = $administrador->ID_ADMINISTRADOR
                                          , OBSERVACIONES = 'No se ha podido crear la dupla material-centro para el centro $rowCentro->REFERENCIA'";
                    $bd->ExecSQL($sqlInsert);
                    $idIncidencia = $bd->IdAsignado();
                    $sqlInsertObservacion = "INSERT INTO OBSERVACION_SISTEMA SET
                                             TIPO_OBJETO = 'INCIDENCIA_SISTEMA'
                                             ,ID_OBJETO = $idIncidencia
                                             ,TEXTO_OBSERVACION = 'No se ha podido crear la dupla material-centro para el centro $rowCentro->REFERENCIA'
                                             ,ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                             ,FECHA = '" . date("Y-m-d H:i:s") . "'";
                    $bd->ExecSQL($sqlInsertObservacion);
                    continue;
                endif;
                //COMPRUEBO GRUPO ARTICULOS
                if ($rowMatObs->ID_FAMILIA_REPRO != $rowMatSus->ID_FAMILIA_REPRO):
                    //GRUPO DE COMPRA
                    $rowFamiliaReproSus = $bd->VerReg("FAMILIA_REPRO", "ID_FAMILIA_REPRO", $rowMatSus->ID_FAMILIA_REPRO);
                    $rowGrupoCompra = $bd->VerReg("GRUPO_COMPRA", "ID_GRUPO_COMPRA", $rowFamiliaReproSus->ID_GRUPO_COMPRA);
                    $objLinea->Purchasing_Group = $rowGrupoCompra->CODIGO;
                endif;

                //AÑADO EL OBJETO AL ARRAY DE CENTROS
                $CENTROS[] = $objLinea;
            else: //SI YA EXISTIA EL MATERIAL CENTRO CONSERVO SUS DATOS
                //LIMPIO LOS VALORES DEL OBJETO LINEA
                unset($objLinea);
                $objLinea = new stdClass();
                //REFERENCIA DEL CENTRO
                $rowCentro = $bd->VerReg("CENTRO", "ID_CENTRO", $rowMatCentroSus->ID_CENTRO);
                $objLinea->Plant_Code = $rowCentro->REFERENCIA;
                //MONEDA Y COSTE ESTIMADO
                $rowSociedad = $bd->VerReg("SOCIEDAD", "ID_SOCIEDAD", $rowCentro->ID_SOCIEDAD);
                $rowMoneda = $bd->VerReg("MONEDA", "ID_MONEDA", $rowSociedad->ID_MONEDA);

                $objLinea->Estimated_Price = $rowMatCentroSus->COSTE;
                $objLinea->Currency = $rowMoneda->MONEDA;

                //GRUPO DE COMPRA
                $rowGrupoCompra = $bd->VerReg("GRUPO_COMPRA", "ID_GRUPO_COMPRA", $rowMatCentroSus->ID_GRUPO_COMPRA);
                $objLinea->Purchasing_Group = $rowGrupoCompra->CODIGO;

                //PLANIFICADO
                $objLinea->MRP_Type_2 = "ND";
                $objLinea->Automatic_PO_allowed = "X";
                //NUMERO DE LOTE REQUERIDO
                $objLinea->Batch_req_Ind = $rowMatCentroSus->TIPO_LOTE != "ninguno" ? "X" : "";
                //PERFIL DE NUMERO DE SERIE
                $objLinea->Serial_Number_Profile = $rowMatCentroSus->TIPO_LOTE == "serie" ? "0003" : "";
                //CATEGORIA DE VALORACION
                $objLinea->Valuation_Category = $rowMatCentroSus->TIPO_LOTE == "serie" ? "X" : "";
                //COMPROBACION GRUPO DE DISPONIBILIDAD
                $objLinea->Checking_Group_Availability_Check = "01";
                //CANAL DE DISTRIBUCION
                $objLinea->Distribution_Channel = $rowMatCentroSus->CANAL_DISTRIBUCION;
                //UNIDAD DE COMPRA
                $rowUnidadVenta = $bd->VerReg("UNIDAD", "ID_UNIDAD", $rowMatCentroSus->ID_UNIDAD_MEDIDA_VENTA);
                $rowUnidadPedido = $bd->VerReg("UNIDAD", "ID_UNIDAD", $rowMatCentroSus->ID_UNIDAD_MEDIDA_PEDIDO);
                $objLinea->UMS = $rowUnidadVenta->UNIDAD;
                $objLinea->Unit_Measure_Order = $rowUnidadPedido->UNIDAD;
                //NUMERADOR DE CANTIDAD COMPRA
                $objLinea->Numerator = $rowMatSus->NUMERADOR_CONVERSION;
                //DENOMINADOR DE CANIDAD COMPRA
                $objLinea->Denominator = $rowMatSus->DENOMINADOR_CONVERSION;
                //PAIS DE IMPUESTOS
                $objLinea->Tax_Country = "ES";
                //TIPO DE IMPUESTOS
                $objLinea->Tax_Class_1 = "1";
                $rowPais = $bd->VerReg("PAIS", "ID_PAIS", $rowSociedad->ID_PAIS);
                $objLinea->Tax_Class_2 = ($rowPais->PAIS == "UA" || $rowPais->PAIS == "US") ? "1" : "";
                $objLinea->Tax_Class_3 = ($rowPais->PAIS == "UA" || $rowPais->PAIS == "US") ? "1" : "";
                //ASIGNACION DE CUENTA
                $objLinea->Account_Assigment = "01";
                //GRUPO DE CARGA
                $objLinea->Loading_Group = "0001";
                //TIPO DE PROC
                $objLinea->Proc_Type = "F";
                //CODIGO HS
                $rowCodigoHS = $bd->VerReg("CODIGO_HS", "ID_CODIGO_HS", $rowMatCentroSus->ID_CODIGO_HS, "No");
                $objLinea->HS_Code = $rowCodigoHS->CODIGO_HS;

                //CLASE DE VALORACION
                $rowTipoValoracion = $bd->VerReg("VALORACION_TIPO_MATERIAL", "TIPO_MATERIAL", $rowMatSus->TIPO_MATERIAL);
                $objLinea->Valuation_Class = $rowMatCentroSus->CATEGORIA_VALORACION;

                //ORGANIZACION DE VENTAS
                $objLinea->Sales_Org = $rowMatCentroSus->ORGANIZACION_VENTAS;

                //AÑADO EL OBJETO AL ARRAY DE CENTROS
                $CENTROS[] = $objLinea;
            endif;
        endwhile;

        $objSalida->CENTROS = $CENTROS;

        $sqlMatAlmacenesObs = "SELECT MA.*,A.TIPO_ALMACEN,A.BAJA,C.BAJA,C.INTEGRACION_CON_SAP FROM MATERIAL_ALMACEN MA INNER JOIN ALMACEN A ON MA.ID_ALMACEN = A.ID_ALMACEN INNER JOIN CENTRO C ON A.ID_CENTRO = C.ID_CENTRO WHERE MA.ID_MATERIAL = $idMaterialObsoleto AND A.TIPO_ALMACEN = 'acciona' AND A.BAJA = 0 AND C.INTEGRACION_CON_SAP = 1";
        $resMatAlmacenesObs = $bd->ExecSQL($sqlMatAlmacenesObs);
        while ($rowMatAlmacenObs = $bd->SigReg($resMatAlmacenesObs)):
            $GLOBALS['NotificaErrorPorEmail'] = "No";
            $rowMatAlmacenSus = $bd->VerRegRest("MATERIAL_ALMACEN", "ID_MATERIAL = $idMaterialSustiuto AND ID_ALMACEN = $rowMatAlmacenObs->ID_ALMACEN", "No");
            if (!$rowMatAlmacenSus)://SI EL MATERIAL ALMACEN NO EXISTE LO CREO
                //LIMPIO LOS DATOS DEL OBJETO
                unset($objLinea);

                //CREO LOS DATOS DEL MATERIAL-CENTRO CON LOS DATOS DEL OBSOLETO
                $objLinea = new stdClass();
                $rowAlmacen = $bd->VerReg("ALMACEN", "ID_ALMACEN", $rowMatAlmacenObs->ID_ALMACEN);
                $rowCentro = $bd->VerReg("CENTRO", "ID_CENTRO", $rowAlmacen->ID_CENTRO);
                //REFERENCIA DE CENTRO
                $objLinea->Plant_Code = $rowCentro->REFERENCIA;
                //REFERENCIA DE ALMACEN
                $objLinea->Warehouse = $rowAlmacen->REFERENCIA;
                //OBTENGO EL VALOR PARA MRP
                $objLinea->MRP_Type = $rowMatAlmacenObs->AREA_CARACTERISTICAS;
                //COMPRUEBO EL TIPO DE MATERIAL
                if ($rowMatObs->TIPO_MATERIAL != $rowMatSus->TIPO_MATERIAL)://SI SON DIFERENTE COJO LOS DATOS DE LAS LUT
                    //OBTENGO EL VALOR PARA MRP
                    $rowMRPTipo = "";
                    $objLinea->MRP_Type = "";
                    $GLOBALS['NotificaErrorPorEmail'] = "No";
                    if ($bd->VerReg("ALMACEN_TIPO_MRP", "ID_ALMACEN", $rowMatAlmacenSus->ID_ALMACEN, "No")):
                        if ($rowMatSus->ESTADO_BLOQUEO_MATERIAL != "No bloqueado"):
                            $rowMRPTipo = $bd->VerRegRest("ALMACEN_TIPO_MRP", "ID_ALMACEN = $rowMatAlmacenSus->ID_ALMACEN AND STATUS_BLOQUEADO = 1");
                        else:
                            $rowMRPTipo = $bd->VerRegRest("ALMACEN_TIPO_MRP", "ID_ALMACEN = $rowMatAlmacenSus->ID_ALMACEN AND TIPO_MATERIAL = '" . $rowMatSus->TIPO_MATERIAL . "'");
                        endif;
                        $objLinea->MRP_Type = $rowMRPTipo->VALOR;
                    endif;
                    unset($GLOBALS["NotificaErrorPorEmail"]);
                endif;
                //SI EL ALMACEN ES SPV, ENTONCES AREA CARACTERISTICAS ES ND
                if ($rowAlmacen->STOCK_COMPARTIDO == 1 && $rowAlmacen->TIPO_STOCK == 'SPV'):
                    $objLinea->MRP_Type = "ND";
                endif;

                $objLinea->MRP_Area = "AP" . $rowAlmacen->REFERENCIA;
                //MRP_Controller
                $GLOBALS["NotificaErrorPorEmail"] = "No";
                $objLinea->MRP_Controller = "";
                $rowPlanificador = $bd->VerReg("PLANIFICADOR", "ID_PLANIFICADOR", $rowMatAlmacenObs->ID_PLANIFICADOR, "No");
                $objLinea->MRP_Controller = $rowPlanificador->REFERENCIA;
                //Special_procurement_type
                $objLinea->Special_procurement_type = "";
                $GLOBALS["NotificaErrorPorEmail"] = "No";
                $rowFamiliaMaterial = $bd->VerReg("FAMILIA_MATERIAL", "ID_FAMILIA_MATERIAL", $rowMatSus->ID_FAMILIA_MATERIAL, "No");
                if ($rowFamiliaMaterial->ES_FAMILIA_ESPECIAL):
                    $rowClaveAprovisionamientoAlmacen = $bd->VerRegRest("CLAVE_APROVISIONAMIENTO_ESPECIAL_ALMACEN", "ID_ALMACEN = $rowAlmacen->ID_ALMACEN AND ES_FAMILIA_ESPECIAL = $rowFamiliaMaterial->ES_FAMILIA_ESPECIAL  AND BAJA = 0", "No");
                    if ($rowClaveAprovisionamientoAlmacen):
                        $rowClaveAprovisionamiento = $bd->VerReg("CLAVE_APROVISIONAMIENTO_ESPECIAL","ID_CLAVE_APROVISIONAMIENTO_ESPECIAL",$rowClaveAprovisionamientoAlmacen->ID_CLAVE_APROVISIONAMIENTO_ESPECIAL);
                        $objLinea->Special_procurement_type = $rowClaveAprovisionamiento->REFERENCIA;
                    endif;
                else:
                    $rowClaveAprovisionamientoAlmacen = $bd->VerRegRest("CLAVE_APROVISIONAMIENTO_ESPECIAL_ALMACEN", "ID_ALMACEN = $rowAlmacen->ID_ALMACEN AND ES_REPARABLE = $rowMatSus->REPARABLE AND BAJA = 0", "No");
                    if ($rowClaveAprovisionamientoAlmacen):
                        $rowClaveAprovisionamiento = $bd->VerReg("CLAVE_APROVISIONAMIENTO_ESPECIAL","ID_CLAVE_APROVISIONAMIENTO_ESPECIAL",$rowClaveAprovisionamientoAlmacen->ID_CLAVE_APROVISIONAMIENTO_ESPECIAL);
                        $objLinea->Special_procurement_type = $rowClaveAprovisionamiento->REFERENCIA;
                    endif;
                endif;

                //Lot_Size
                $objLinea->Lot_Size = "";
                $GLOBALS["NotificaErrorPorEmail"] = "No";
                if ($rowFamiliaMaterial->ES_FAMILIA_ESPECIAL):
                    $rowMedidaLote = $bd->VerRegRest("TAMANO_LOTE_ALMACEN", "ID_ALMACEN = $rowAlmacen->ID_ALMACEN AND ES_FAMILIA_ESPECIAL = 1 AND BAJA = 0", "No");
                    $objLinea->Lot_Size = $rowMedidaLote->VALOR;
                else:
                    $rowMedidaLote = $bd->VerRegRest("TAMANO_LOTE_ALMACEN", "ID_ALMACEN = $rowAlmacen->ID_ALMACEN AND ES_FAMILIA_ESPECIAL = 0 AND BAJA = 0", "No");
                    $objLinea->Lot_Size = $rowMedidaLote->VALOR;
                endif;

                $factorConversion = 1;
                if (intval($rowMatSus->DENOMINADOR_CONVERSION) != 0):
                    $factorConversion = $rowMatSus->NUMERADOR_CONVERSION / $rowMatSus->DENOMINADOR_CONVERSION;
                endif;

                //VALOR DE REDONDEO
                $objLinea->Rounding_value = $factorConversion;
                //TAMAÑO LOTE MINIMO
                $objLinea->Minimum_Lot_Size = $factorConversion;
                //GRUPO MRP
                $objLinea->MRP_Group = "0000";
                //STOCK DE SEGURIDAD
                $objLinea->Safety_Stock = $rowMatAlmacenObs->PUNTO_REORDEN;
                //STOCK MAXIMO
                $objLinea->Maximum_Stock = $rowMatAlmacenObs->STOCK_MAXIMO;
                //LOCALIZACION ALMACENAMIENTO ESTANDAR
                $objLinea->Default_storage_location = $rowAlmacen->REFERENCIA;
                //Planned_Delivery_Time_Days (LEAD TIME SUMINISTRO)
                $objLinea->Planned_Delivery_Time_Days = "";
                $GLOBALS["NotificaErrorPorEmail"] = "No";
                $rowTiempoAprovisionamiento = $bd->VerRegRest("TIEMPO_APROVISIONAMIENTO_ALMACEN","ID_ALMACEN = $rowAlmacen->ID_ALMACEN AND ES_FAMILIA_ESPECIAL = $rowFamiliaMaterial->ES_FAMILIA_ESPECIAL AND BAJA = 0","No");
                if($rowTiempoAprovisionamiento):
                    $objLinea->Planned_Delivery_Time_Days = $rowTiempoAprovisionamiento->VALOR;
                endif;
                //Consider_Planned_Delivery_Time
                $objLinea->Consider_Planned_Delivery_Time = "X";
                //Procurement_Frequency
                $objLinea->Procurement_Frequency = "";
                $GLOBALS["NotificaErrorPorEmail"] = "No";
                $rowFrecuenciaAprovisionamiento = $bd->VerRegRest("FRECUENCIA_APROVISIONAMIENTO_ALMACEN","ID_ALMACEN = $rowAlmacen->ID_ALMACEN AND ES_FAMILIA_ESPECIAL = $rowFamiliaMaterial->ES_FAMILIA_ESPECIAL AND BAJA = 0","No");
                if($rowFrecuenciaAprovisionamiento):
                    $objLinea->Procurement_Frequency = $rowFrecuenciaAprovisionamiento->VALOR;
                endif;
                unset($GLOBALS["NotificaErrorPorEmail"]);

                //COMPRUEBO CANTIDAD POR FORMATO
                if ($rowMatObs->NUMERADOR_CONVERSION != $rowMatSus->NUMERADOR_CONVERSION || $rowMatObs->DENOMINADOR_CONVERSION != $rowMatSus->DENOMINADOR_CONVERSION):
                    $rowISTipo = $bd->VerReg("INCIDENCIA_SISTEMA_TIPO", "INCIDENCIA_SISTEMA_TIPO", "Codificación", "No");
                    $rowISSubTipo = $bd->VerReg("INCIDENCIA_SISTEMA_SUBTIPO", "INCIDENCIA_SISTEMA_SUBTIPO", "Material almacén no creado", "No");

                    $sqlInsert = "INSERT INTO INCIDENCIA_SISTEMA SET
                                          ID_INCIDENCIA_SISTEMA_TIPO = $rowISTipo->ID_INCIDENCIA_SISTEMA_TIPO
                                          , TIPO = 'Codificación'
                                          , ID_INCIDENCIA_SISTEMA_SUBTIPO = $rowISSubTipo->ID_INCIDENCIA_SISTEMA_SUBTIPO
                                          , SUBTIPO = 'Material almacén no creado'
                                          , ESTADO = 'Creada'
                                          , TABLA_OBJETO = 'MATERIAL'
                                          , ID_OBJETO = '$idMaterialSustiuto'
                                          , FECHA_CREACION = '" . date("Y-m-d H:i:s") . "'
                                          , FECHA_RESOLUCION = '0000-00-00 00:00:00'
                                          , ID_ADMINISTRADOR_CREACION = $administrador->ID_ADMINISTRADOR
                                          , ID_ADMINISTRADOR_ULTIMA_MODIFICACION = $administrador->ID_ADMINISTRADOR
                                          , OBSERVACIONES = 'No se ha podido crear la dupla material-almacén para el almacén $rowAlmacen->REFERENCIA'";
                    $bd->ExecSQL($sqlInsert);
                    $idIncidencia = $bd->IdAsignado();
                    $sqlInsertObservacion = "INSERT INTO OBSERVACION_SISTEMA SET
                                             TIPO_OBJETO = 'INCIDENCIA_SISTEMA'
                                             ,ID_OBJETO = $idIncidencia
                                             ,TEXTO_OBSERVACION = 'No se ha podido crear la dupla material-almacén para el almacén " . $rowAlmacen->REFERENCIA . " al crear la relación de sustitución con el material " . $rowMatObs->REFERENCIA . "'
                                             ,ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                             ,FECHA = '" . date("Y-m-d H:i:s") . "'";
                    $bd->ExecSQL($sqlInsertObservacion);
                    continue;
                endif;
                //COMPRUEBO GRUPO ARTICULOS
                if ($rowMatObs->ID_FAMILIA_REPRO != $rowMatSus->ID_FAMILIA_REPRO):
                    //MRP_Controller
                    $objLinea->MRP_Controller = "";
                    $rowAlmacen = $bd->VerReg("ALMACEN", "ID_ALMACEN", $rowMatAlmacenObs->ID_ALMACEN);
                    $rowCentro = $bd->VerReg("CENTRO", "ID_CENTRO", $rowAlmacen->ID_CENTRO);
                    $GLOBALS["NotificaErrorPorEmail"] = "No";
                    $sqlPlanificadorCentro = "SELECT * FROM PLANIFICADOR_CENTRO WHERE ID_CENTRO = " . $rowCentro->ID_CENTRO;
                    $resPlanificadorCentro = $bd->ExecSQL($sqlPlanificadorCentro);
                    if ($bd->NumRegs($resPlanificadorCentro) > 0):
                        if ($rowCentro->PLANIFICAR_POR_FAMILIA_REPRO == "1"):
                            $rowPlanificadorCentro = $bd->VerRegRest('PLANIFICADOR_CENTRO', "ID_CENTRO =  $rowCentro->ID_CENTRO  AND ID_FAMILIA_REPRO = $rowMatSus->ID_FAMILIA_REPRO");
                        else:
                            $rowPlanificadorCentro = $bd->VerRegRest("PLANIFICADOR_CENTRO", "ID_CENTRO = $rowCentro->ID_CENTRO AND TIPO_MATERIAL = '$rowMatSus->TIPO_MATERIAL'");
                        endif;
                        $rowPlanificador = $bd->VerReg("PLANIFICADOR", "ID_PLANIFICADOR", $rowPlanificadorCentro->ID_PLANIFICADOR, "No");
                        $objLinea->MRP_Controller = $rowPlanificador->REFERENCIA;
                    endif;
                    unset($GLOBALS["NotificaErrorPorEmail"]);
                endif;

                //AÑADO EL OBJETO AL ARRAY DE ALMACENES
                $ALMACENES[] = $objLinea;
            else: //SI YA EXISTIA EL MATERIAL ALMACEN CONSERVO SUS DATOS
                //LIMPIO LOS DATOS DEL OBJETO
                unset($objLinea);
                $objLinea = new stdClass();
                $rowAlmacen = $bd->VerReg("ALMACEN", "ID_ALMACEN", $rowMatAlmacenSus->ID_ALMACEN);
                $rowCentro = $bd->VerReg("CENTRO", "ID_CENTRO", $rowAlmacen->ID_CENTRO);
                //REFERENCIA DE CENTRO
                $objLinea->Plant_Code = $rowCentro->REFERENCIA;
                //REFERENCIA DE ALMACEN
                $objLinea->Warehouse = $rowAlmacen->REFERENCIA;

                //OBTENGO EL VALOR PARA MRP

                $objLinea->MRP_Type = $rowMatAlmacenSus->AREA_CARACTERISTICAS;
                $objLinea->MRP_Area = "AP" . $rowAlmacen->REFERENCIA;

                //MRP_Controller
                $GLOBALS["NotificaErrorPorEmail"] = "No";
                $objLinea->MRP_Controller = "";
                $rowPlanificador = $bd->VerReg("PLANIFICADOR", "ID_PLANIFICADOR", $rowMatAlmacenSus->ID_PLANIFICADOR, "No");
                unset($GLOBALS["NotificaErrorPorEmail"]);
                $objLinea->MRP_Controller = $rowPlanificador->REFERENCIA;

                //Special_procurement_type
                $GLOBALS["NotificaErrorPorEmail"] = "No";
                $rowClaveAprovisionamiento = $bd->VerReg("CLAVE_APROVISIONAMIENTO_ESPECIAL", "ID_CLAVE_APROVISIONAMIENTO_ESPECIAL", $rowMatAlmacenSus->ID_CLAVE_APROVISIONAMIENTO_ESPECIAL, "No");
                unset($GLOBALS["NotificaErrorPorEmail"]);
                $objLinea->Special_procurement_type = $rowClaveAprovisionamiento->REFERENCIA;

                //Lot_Size
                $objLinea->Lot_Size = $rowMatAlmacenSus->TAMANO_LOTE;

                //VALOR DE REDONDEO
                $objLinea->Rounding_value = $rowMatAlmacenSus->VALOR_REDONDEO;
                //TAMAÑO LOTE MINIMO
                $objLinea->Minimum_Lot_Size = $rowMatAlmacenSus->TAMANO_LOTE_MINIMO;
                //GRUPO MRP
                $objLinea->MRP_Group = "0000";
                //STOCK DE SEGURIDAD
                $objLinea->Safety_Stock = $rowMatAlmacenSus->PUNTO_REORDEN;
                //STOCK MAXIMO
                $objLinea->Maximum_Stock = $rowMatAlmacenSus->STOCK_MAXIMO;
                //LOCALIZACION ALMACENAMIENTO ESTANDAR
                $objLinea->Default_storage_location = $rowAlmacen->REFERENCIA;

                //Planned_Delivery_Time_Days
                $objLinea->Planned_Delivery_Time_Days = $rowMatAlmacenSus->LEAD_TIME_COMPRA_SCS == NULL ? $rowMatAlmacenSus->LEAD_TIME_TRASLADO_SCS: $rowMatAlmacenSus->LEAD_TIME_COMPRA_SCS;

                //Consider_Planned_Delivery_Time
                $objLinea->Consider_Planned_Delivery_Time = "X";

                //Procurement_Frequency
                $objLinea->Procurement_Frequency = $rowMatAlmacenSus->FRECUENCIA;

                //AÑADO EL OBJETO AL ARRAY DE ALMACENES
                $ALMACENES[] = $objLinea;
            endif;
        endwhile;
        $objSalida->ALMACENES = $ALMACENES;

        return $objSalida;
    }

    /**
     * FUNCION PARA CREAR LOS MATERIAL CENTRO Y MATERIAL ALMACEN DE UN MATERIAL OBSOLETO EN UNO SUSTITUTO
     * @param $idMaterialObsoleto
     * @param $idMaterialSustiuto
     * @return objSalida (un objeto con el array de centros y almacenes con sus datos)
     */
    function crearAlmacenesSustituto($idMaterialObsoleto,$idSolicitudMaterial){
        global $bd;
        //COMPRUEBO QUE EXISTAN AMBOS MATERIALES
        $rowMatObs = $bd->VerReg("MATERIAL","ID_MATERIAL",$idMaterialObsoleto);
        $rowSolSus = $bd->VerReg("SOLICITUD_MATERIAL","ID_SOLICITUD_MATERIAL",$idSolicitudMaterial);

        //CREO UN OBJETO PARA RELLENAR CON LSO DATOS DE LOS MATERIAL CENTRO Y MATERIAL ALMACEN
        $objSalida = new stdClass();
        $CENTROS = array();
        $ALMACENES = array();
        $objLinea = new stdClass();

        $sqlMatCentrosSol = "SELECT * FROM SOLICITUD_MATERIAL_CENTRO WHERE ID_SOLICITUD_MATERIAL = $idSolicitudMaterial AND BAJA = 0";
        $resMatCentrosSol = $bd->ExecSQL($sqlMatCentrosSol);



        while($rowMatCentroSol = $bd->SigReg($resMatCentrosSol)):
            $GLOBALS['NotificaErrorPorEmail'] = "No";
            $rowMatCentroObs = $bd->VerRegRest("MATERIAL_CENTRO","ID_MATERIAL = $idMaterialObsoleto AND ID_CENTRO = $rowMatCentroSol->ID_CENTRO","No");
            if($rowMatCentroObs)://SI EL MATERIAL CENTRO EXISTE COJO SUS DATOS
                //LIMPIO LOS DATOS DEL OBJETO
                unset($objLinea);
                //CREO LOS DATOS DEL MATERIAL-CENTRO CON LOS DATOS DEL OBSOLETO
                $objLinea = new stdClass();

                //REFERENCIA DEL CENTRO
                $rowCentro = $bd->VerReg("CENTRO", "ID_CENTRO", $rowMatCentroObs->ID_CENTRO);
                $objLinea->Plant_Code = $rowCentro->REFERENCIA;
                //MONEDA Y COSTE ESTIMADO
                $rowSociedad = $bd->VerReg("SOCIEDAD", "ID_SOCIEDAD", $rowCentro->ID_SOCIEDAD);
                $rowMoneda = $bd->VerReg("MONEDA", "ID_MONEDA", $rowSociedad->ID_MONEDA);
                $objLinea->Estimated_Price = $rowMatCentroObs->COSTE;
                $objLinea->Currency = $rowMoneda->MONEDA;
                //GRUPO DE COMPRA
                $rowGrupoCompra = $bd->VerReg("GRUPO_COMPRA", "ID_GRUPO_COMPRA", $rowMatCentroObs->ID_GRUPO_COMPRA);
                $objLinea->Purchasing_Group = $rowGrupoCompra->CODIGO;
                //PLANIFICADO
                $objLinea->MRP_Type_2 = "ND";
                $objLinea->Automatic_PO_allowed = "X";
                //NUMERO DE LOTE REQUERIDO
                $objLinea->Batch_req_Ind = $rowMatCentroObs->TIPO_LOTE != "ninguno" ? "X" : "";
                //PERFIL DE NUMERO DE SERIE
                $objLinea->Serial_Number_Profile = $rowMatCentroObs->TIPO_LOTE == "serie" ? "0003" : "";
                //CATEGORIA DE VALORACION
                $objLinea->Valuation_Category = $rowMatCentroObs->TIPO_LOTE == "serie" ? "X" : "";
                //COMPROBACION GRUPO DE DISPONIBILIDAD
                $objLinea->Checking_Group_Availability_Check = "01";
                //CANAL DE DISTRIBUCION
                $objLinea->Distribution_Channel = $rowMatCentroObs->CANAL_DISTRIBUCION;
                //UNIDAD DE COMPRA
                $rowUnidadVenta = $bd->VerReg("UNIDAD","ID_UNIDAD",$rowMatCentroObs->ID_UNIDAD_MEDIDA_VENTA);
                $rowUnidadPedido = $bd->VerReg("UNIDAD","ID_UNIDAD",$rowMatCentroObs->ID_UNIDAD_MEDIDA_PEDIDO);
                $objLinea->UMS = $rowUnidadVenta->UNIDAD;
                $objLinea->Unit_Measure_Order = $rowUnidadPedido->UNIDAD ;
                //NUMERADOR DE CANTIDAD COMPRA
                $objLinea->Numerator = $NUMERADOR;
                //DENOMINADOR DE CANIDAD COMPRA
                $objLinea->Denominator = $DENOMINADOR;
                //PAIS DE IMPUESTOS
                $objLinea->Tax_Country = "ES";
                //TIPO DE IMPUESTOS
                $objLinea->Tax_Class_1 = "1";
                $rowPais = $bd->VerReg("PAIS", "ID_PAIS", $rowSociedad->ID_PAIS);
                $objLinea->Tax_Class_2 = ($rowPais->PAIS == "UA" || $rowPais->PAIS == "US") ? "1" : "";
                $objLinea->Tax_Class_3 = ($rowPais->PAIS == "UA" || $rowPais->PAIS == "US") ? "1" : "";
                //ASIGNACION DE CUENTA
                $objLinea->Account_Assigment = "01";
                //GRUPO DE CARGA
                $objLinea->Loading_Group = "0001";
                //TIPO DE PROC
                $objLinea->Proc_Type = "F";
                //CODIGO HS
                $rowCodigoHS = $bd->VerReg("CODIGO_HS", "ID_CODIGO_HS", $rowMatCentroObs->ID_CODIGO_HS, "No");
                $objLinea->HS_Code = $rowCodigoHS->CODIGO_HS;
                //CLASE DE VALORACION
                $objLinea->Valuation_Class = $rowMatCentroObs->CATEGORIA_VALORACION;
                //ORGANIZACION DE VENTAS
                $objLinea->Sales_Org = $rowMatCentroObs->ORGANIZACION_VENTAS;


                //COMPRUEBO EL TIPO DE MATERIAL
                if($rowMatObs->TIPO_MATERIAL != $rowSolSus->TIPO_MATERIAL)://SI SON DIFERENTE COJO LOS DATOS DE LAS LUT
                    //CLASE DE VALORACION
                    $rowTipoValoracion = $bd->VerReg("VALORACION_TIPO_MATERIAL", "TIPO_MATERIAL", $rowSolSus->TIPO_MATERIAL);
                    $objLinea->Valuation_Class = $rowTipoValoracion->VALORACION;
                endif;
                //COMPRUEBO TIPO LOTE
                $tipoLoteSus = "ninguno";
                if($rowSolSus->LOTABLE == "1"):
                    $tipoLoteSus = "lote";
                endif;
                if($rowSolSus->SERIABLE == "1"):
                    $tipoLoteSus = "serie";
                endif;

                if ($tipoLoteSus != $rowMatCentroObs->TIPO_LOTE):
                    //NUMERO DE LOTE REQUERIDO
                    $objLinea->Batch_req_Ind = $rowSeriableMatSus->TIPO_LOTE != "ninguno" ? "X" : "";
                    //PERFIL DE NUMERO DE SERIE
                    $objLinea->Serial_Number_Profile = $rowSeriableMatSus->TIPO_LOTE == "serie" ? "0003" : "";
                    //CATEGORIA DE VALORACION
                    $objLinea->Valuation_Category = $rowSeriableMatSus->TIPO_LOTE == "serie" ? "X" : "";
                endif;

                //COMPRUEBO UNIDAD BASE
                if($rowMatObs->ID_UNIDAD_MEDIDA != $rowSolSus->ID_UNIDAD_MEDIDA):
                    $rowUnidadCompraSus = $bd->VerReg("UNIDAD","ID_UNIDAD",$rowSolSus->ID_UNIDAD_COMPRA);
                    //UNIDAD DE COMPRA
                    $objLinea->UMS = $rowUnidadCompraSus->UNIDAD;
                    $objLinea->Unit_Measure_Order = $rowUnidadCompraSus->UNIDAD;
                endif;
                //COMPRUEBO FORMATO COMPRA
                if($rowMatObs->ID_UNIDAD_COMPRA != $rowSolSus->ID_UNIDAD_COMPRA):
                    $rowUnidadCompraSus = $bd->VerReg("UNIDAD","ID_UNIDAD",$rowSolSus->ID_UNIDAD_COMPRA);
                    //UNIDAD DE COMPRA
                    $objLinea->UMS = $rowUnidadCompraSus->UNIDAD;
                    $objLinea->Unit_Measure_Order = $rowUnidadCompraSus->UNIDAD;
                endif;
                //COMPRUEBO CANTIDAD POR FORMATO
                if(abs(($rowMatObs->NUMERADOR_CONVERSION / $rowMatObs->DENOMINADOR_CONVERSION) - $rowSolSus->FACTOR_CONVERSION ) > EPSILON_SISTEMA ):
                    //TODO: INCIDENCIA DE SISTEMA
                    continue;
                endif;
                //COMPRUEBO GRUPO ARTICULOS
                if($rowMatObs->ID_FAMILIA_REPRO != $rowSolSus->ID_FAMILIA_REPRO):
                    //GRUPO DE COMPRA
                    $rowFamiliaReproSus = $bd->VerReg("FAMILIA_REPRO", "ID_FAMILIA_REPRO", $rowSolSus->ID_FAMILIA_REPRO);
                    $rowGrupoCompra = $bd->VerReg("GRUPO_COMPRA", "ID_GRUPO_COMPRA", $rowFamiliaReproSus->ID_GRUPO_COMPRA);
                    $objLinea->Purchasing_Group = $rowGrupoCompra->CODIGO;
                endif;

                //AÑADO EL OBJETO AL ARRAY DE CENTROS
                $CENTROS[] = $objLinea;
            else: //SI NO EXISTIA EL MATERIAL CENTRO COJO LOS DATOS DE LAS TABLAS
                //LIMPIO LOS VALORES DEL OBJETO LINEA
                unset($objLinea);
                $objLinea = new stdClass();
                //REFERENCIA DEL CENTRO
                $rowCentro = $bd->VerReg("CENTRO", "ID_CENTRO", $rowMatCentroSol->ID_CENTRO);
                $objLinea->Plant_Code = $rowCentro->REFERENCIA;

                //MONEDA Y COSTE ESTIMADO
                $rowSociedad = $bd->VerReg("SOCIEDAD", "ID_SOCIEDAD", $rowCentro->ID_SOCIEDAD);
                $rowMoneda = $bd->VerReg("MONEDA", "ID_MONEDA", $rowSociedad->ID_MONEDA);
                $rowCoste = $bd->VerRegRest("SOLICITUD_MATERIAL_COSTE", "ID_MONEDA = $rowMoneda->ID_MONEDA AND ID_SOLICITUD_MATERIAL = $idSolicitudMaterial AND BAJA = 0");
                $objLinea->Estimated_Price = $rowCoste->COSTE;
                $objLinea->Currency = $rowMoneda->MONEDA;

                //GRUPO DE COMPRA
                $rowFamiliaRepro = $bd->VerReg("FAMILIA_REPRO", "ID_FAMILIA_REPRO", $rowSolSus->ID_FAMILIA_REPRO);
                $rowGrupoCompra = $bd->VerReg("GRUPO_COMPRA", "ID_GRUPO_COMPRA", $rowFamiliaRepro->ID_GRUPO_COMPRA);
                $objLinea->Purchasing_Group = $rowGrupoCompra->CODIGO;

                //PLANIFICADO
                $objLinea->MRP_Type_2 = "ND";
                $objLinea->Automatic_PO_allowed = "X";
                //NUMERO DE LOTE REQUERIDO
                $objLinea->Batch_req_Ind = $rowSolSus->LOTABLE ? "X" : "";
                //PERFIL DE NUMERO DE SERIE
                $objLinea->Serial_Number_Profile = $rowSolSus->SERIABLE ? "0003" : "";
                //CATEGORIA DE VALORACION
                $objLinea->Valuation_Category = $rowSolSus->SERIABLE ? "X" : "";
                //COMPROBACION GRUPO DE DISPONIBILIDAD
                $objLinea->Checking_Group_Availability_Check = "01";
                //CANAL DE DISTRIBUCION
                $objLinea->Distribution_Channel = $rowCentro->CANAL_DISTRIBUCION;

                //UNIDADES MEDIDA Y COMPRA
                $UNIDAD_BASE = $bd->VerReg("UNIDAD", "ID_UNIDAD", $rowSolicitudMaterial->ID_UNIDAD_MEDIDA)->UNIDAD;
                $UNIDAD_COMPRA = $bd->VerReg("UNIDAD", "ID_UNIDAD", $rowSolicitudMaterial->ID_UNIDAD_COMPRA)->UNIDAD;
                $NUMERADOR = "";
                $DENOMINADOR = "";
                //OBTENGO LOS DATOS DE NUMERADOR Y DENOMINADOR
                if ($rowSolicitudMaterial->FACTOR_CONVERSION != "" && $rowSolicitudMaterial->FACTOR_CONVERSION != 0.000):
                    $NUMERADOR = $auxiliar->formatoNumero($rowSolicitudMaterial->FACTOR_CONVERSION);
                    //MULTIPLICO POR 1000 PARA QUITAR LOS DECIMALES
                    $numera = "" . floatval($NUMERADOR) * 1000;
                    $mcm = $auxiliar->mcm($numera, 1000);

                    //NUMERADOR Y DENOMINADOR - FACTOR DE CONVERSION
                    $NUMERADOR = $mcm / 1000;
                    $DENOMINADOR = $mcm / $numera;
                else:
                    $NUMERADOR = 1;
                    $DENOMINADOR = 1;
                endif;

                //UNIDAD DE COMPRA
                $objLinea->UMS = $UNIDAD_COMPRA;
                $objLinea->Unit_Measure_Order = $UNIDAD_COMPRA;
                //NUMERADOR DE CANTIDAD COMPRA
                $objLinea->Numerator = $NUMERADOR;
                //DENOMINADOR DE CANIDAD COMPRA
                $objLinea->Denominator = $DENOMINADOR;
                //PAIS DE IMPUESTOS
                $rowPais = $bd->VerReg("PAIS", "ID_PAIS", $rowSociedad->ID_PAIS);
                $objLinea->Tax_Country = $rowPais->PAIS;
                //TIPO DE IMPUESTOS
                $objLinea->Tax_Class_1 = "1";
                $objLinea->Tax_Class_2 = ($rowPais->PAIS == "UA" || $rowPais->PAIS == "US") ? "1" : "";
                $objLinea->Tax_Class_3 = ($rowPais->PAIS == "UA" || $rowPais->PAIS == "US") ? "1" : "";
                //ASIGNACION DE CUENTA
                $objLinea->Account_Assigment = "01";
                //GRUPO DE CARGA
                $objLinea->Loading_Group = "0001";
                //TIPO DE PROC
                $objLinea->Proc_Type = "F";
                //CODIGO HS
                $rowCodigoHS = $bd->VerReg("CODIGO_HS", "ID_CODIGO_HS", $rowSolSus->ID_CODIGO_HS, "No");
                $objLinea->HS_Code = $rowCodigoHS->CODIGO_HS;

                //CLASE DE VALORACION
                $rowTipoValoracion = $bd->VerReg("VALORACION_TIPO_MATERIAL", "TIPO_MATERIAL", $rowSolSus->TIPO_MATERIAL);
                $objLinea->Valuation_Class = $rowTipoValoracion->VALORACION;

                //ORGANIZACION DE VENTAS
                $rowSociedad = $bd->VerReg("SOCIEDAD", "ID_SOCIEDAD", $rowCentro->ID_SOCIEDAD);
                $objLinea->Sales_Org = $rowCentro->ORGANIZACION_VENTAS;


                //AÑADO EL OBJETO AL ARRAY DE CENTROS
                $CENTROS[] = $objLinea;
            endif;
        endwhile;

        $objSalida->CENTROS = $CENTROS;

        $sqlMatAlmacenesSol = "SELECT * FROM SOLICITUD_MATERIAL_ALMACEN WHERE ID_SOLICITUD_MATERIAL = $idSolicitudMaterial AND BAJA = 0";
        $resMatAlmacenesSol = $bd->ExecSQL($sqlMatAlmacenesSol);
        while($rowMatAlmacenSol = $bd->SigReg($resMatAlmacenesSol)):
            $GLOBALS['NotificaErrorPorEmail'] = "No";
            $rowMatAlmacenObs = $bd->VerRegRest("MATERIAL_ALMACEN","ID_MATERIAL = $idMaterialObsoleto AND ID_ALMACEN = $rowMatAlmacenSol->ID_ALMACEN","No");
            if($rowMatAlmacenObs)://SI EL MATERIAL ALMACEN EXISTE COJO SUS DATOS
                //LIMPIO LOS DATOS DEL OBJETO
                unset($objLinea);

                //CREO LOS DATOS DEL MATERIAL-CENTRO CON LOS DATOS DEL OBSOLETO
                $objLinea = new stdClass();
                $rowAlmacen = $bd->VerReg("ALMACEN", "ID_ALMACEN", $rowMatAlmacenObs->ID_ALMACEN);
                $rowCentro = $bd->VerReg("CENTRO", "ID_CENTRO", $rowAlmacen->ID_CENTRO);
                //REFERENCIA DE CENTRO
                $objLinea->Plant_Code = $rowCentro->REFERENCIA;
                //REFERENCIA DE ALMACEN
                $objLinea->Warehouse = $rowAlmacen->REFERENCIA;
                //OBTENGO EL VALOR PARA MRP
                $objLinea->MRP_Type = $rowMatAlmacenObs->AREA_CARACTERISTICAS;
                $objLinea->MRP_Area = "AP" . $rowAlmacen->REFERENCIA;
                //MRP_Controller
                $GLOBALS["NotificaErrorPorEmail"]="No";
                $objLinea->MRP_Controller = "";
                $rowPlanificador = $bd->VerReg("PLANIFICADOR", "ID_PLANIFICADOR", $rowMatAlmacenObs->ID_PLANIFICADOR, "No");
                $objLinea->MRP_Controller = $rowPlanificador->PLANIFICADOR;
                //Special_procurement_type
                $GLOBALS["NotificaErrorPorEmail"]="No";
                $rowClaveAprovisionamiento = $bd->VerReg("CLAVE_APROVISIONAMIENTO_ESPECIAL","ID_CLAVE_APROVISIONAMIENTO_ESPECIAL",$rowMatAlmacenObs->ID_CLAVE_APROVISIONAMIENTO_ESPECIAL,"No");
                $objLinea->Special_procurement_type = $rowClaveAprovisionamiento->REFERENCIA;
                //Lot_Size
                $objLinea->Lot_Size = $rowMatAlmacenObs->TAMANO_LOTE;
                //VALOR DE REDONDEO
                $objLinea->Rounding_value = $rowMatAlmacenObs->VALOR_REDONDEO;
                //TAMAÑO LOTE MINIMO
                $objLinea->Minimum_Lot_Size = $rowMatAlmacenObs->TAMANO_LOTE_MINIMO;
                //GRUPO MRP
                $objLinea->MRP_Group = "0000";
                //STOCK DE SEGURIDAD
                $objLinea->Safety_Stock = "";
                //STOCK MAXIMO
                $objLinea->Maximum_Stock = "";
                //LOCALIZACION ALMACENAMIENTO ESTANDAR
                $objLinea->Default_storage_location = $rowAlmacen->REFERENCIA;
                //Planned_Delivery_Time_Days
                $objLinea->Planned_Delivery_Time_Days =$rowMatAlmacenObs->LEAD_TIME_COMPRA_SCS == NULL ? $rowMatAlmacenObs->LEAD_TIME_TRASLADO_SCS: $rowMatAlmacenObs->LEAD_TIME_COMPRA_SCS;
                //Consider_Planned_Delivery_Time
                $objLinea->Consider_Planned_Delivery_Time = "X";
                //Procurement_Frequency
                $objLinea->Procurement_Frequency = $rowMatAlmacenObs->FRECUENCIA;



                //COMPRUEBO EL TIPO DE MATERIAL
                if($rowMatObs->TIPO_MATERIAL != $rowSolSus->TIPO_MATERIAL)://SI SON DIFERENTE COJO LOS DATOS DE LAS LUT
                    //OBTENGO EL VALOR PARA MRP
                    $rowMRPTipo = "";
                    $objLinea->MRP_Type = "";
                    $GLOBALS['NotificaErrorPorEmail'] = "No";
                    if ($bd->VerReg("ALMACEN_TIPO_MRP", "ID_ALMACEN", $rowMatAlmacenSus->ID_ALMACEN, "No")):
                        if ($rowSolicitudMaterial->ESTATUS != "No bloqueado"):
                            $rowMRPTipo = $bd->VerRegRest("ALMACEN_TIPO_MRP", "ID_ALMACEN = $rowMatAlmacenSus->ID_ALMACEN AND STATUS_BLOQUEADO = 1");
                        else:
                            $rowMRPTipo = $bd->VerRegRest("ALMACEN_TIPO_MRP", "ID_ALMACEN = $rowMatAlmacenSus->ID_ALMACEN AND TIPO_MATERIAL = '". $rowSolSus->TIPO_MATERIAL ."'");
                        endif;
                        $objLinea->MRP_Type = $rowMRPTipo->VALOR;
                    endif;
                endif;
                //COMPRUEBO SERIABLE
                $GLOBAL["NotificaErrorPorEmail"] = "No";
                $rowSeriableMatSus = $bd->VerRegRest("MATERIAL_ALMACEN","ID_MATERIAL = $idMaterialSustiuto","No");
                if($rowSeriableMatSus):
                    if($rowSeriableMatSus->TIPO_LOTE != $rowMatAlmacenObs->TIPO_LOTE):
                        if($rowSeriableMatSus->TIPO_LOTE == "serie"):
                            //VALOR DE REDONDEO
                            $objLinea->Rounding_value = "1";
                            //TAMAÑO LOTE MINIMO
                            $objLinea->Minimum_Lot_Size = "1";
                        endif;
                    endif;
                endif;
                //COMPRUEBO UNIDAD BASE
                if($rowMatObs->ID_UNIDAD_MEDIDA != $rowSolSus->ID_UNIDAD_MEDIDA):
                    $cantidadFormatoSus = $rowSolSus->NUMERADOR_CONVERSION / $rowSolSus->DENOMINADOR_CONVERSION;
                    //VALOR DE REDONDEO
                    $objLinea->Rounding_value = $cantidadFormatoSus;
                    //TAMAÑO LOTE MINIMO
                    $objLinea->Minimum_Lot_Size = $rowMatAlmacenObs->TAMANO_LOTE_MINIMO * $cantidadFormatoSus;
                    //STOCK DE SEGURIDAD
                    $objLinea->Safety_Stock = $rowMatAlmacenObs->STOCK_SEGURIDAD * $cantidadFormatoSus;
                    //STOCK MAXIMO
                    $objLinea->Maximum_Stock = $rowMatAlmacenObs->STOCK_MAXIMO * $cantidadFormatoSus;
                endif;
                //COMPRUEBO FORMATO COMPRA
                if($rowMatObs->ID_UNIDAD_COMPRA != $rowSolSus->ID_UNIDAD_COMPRA):
                    //VALOR DE REDONDEO
                    $objLinea->Rounding_value = $cantidadFormatoSus;
                    //TAMAÑO LOTE MINIMO
                    $objLinea->Minimum_Lot_Size = $rowMatAlmacenObs->TAMANO_LOTE_MINIMO * $cantidadFormatoSus;
                    //STOCK DE SEGURIDAD
                    $objLinea->Safety_Stock = $rowMatAlmacenObs->STOCK_SEGURIDAD * $cantidadFormatoSus;
                    //STOCK MAXIMO
                    $objLinea->Maximum_Stock = $rowMatAlmacenObs->STOCK_MAXIMO * $cantidadFormatoSus;
                endif;
                //COMPRUEBO CANTIDAD POR FORMATO
                if (abs(($rowMatObs->NUMERADOR_CONVERSION / $rowMatObs->DENOMINADOR_CONVERSION) - $rowSolSus->FACTOR_CONVERSION) > EPSILON_SISTEMA):
                    $rowISTipo = $bd->VerReg("INCIDENCIA_SISTEMA_TIPO", "INCIDENCIA_SISTEMA_TIPO", "Codificación", "No");
                    $rowISSubTipo = $bd->VerReg("INCIDENCIA_SISTEMA_SUBTIPO", "INCIDENCIA_SISTEMA_SUBTIPO", "Material almacén no creado", "No");

                    $sqlInsert = "INSERT INTO INCIDENCIA_SISTEMA SET
                                          ID_INCIDENCIA_SISTEMA_TIPO = $rowISTipo->ID_INCIDENCIA_SISTEMA_TIPO
                                          , TIPO = 'Codificación'
                                          , ID_INCIDENCIA_SISTEMA_SUBTIPO = $rowISSubTipo->ID_INCIDENCIA_SISTEMA_SUBTIPO
                                          , SUBTIPO = 'Material almacén no creado'
                                          , ESTADO = 'Creada'
                                          , TABLA_OBJETO = 'SOLICITUD_MATERIAL'
                                          , ID_OBJETO = '$idSolicitudMaterial'
                                          , FECHA_CREACION = '" . date("Y-m-d H:i:s") . "'
                                          , FECHA_RESOLUCION = '0000-00-00 00:00:00'
                                          , ID_ADMINISTRADOR_CREACION = $administrador->ID_ADMINISTRADOR
                                          , ID_ADMINISTRADOR_ULTIMA_MODIFICACION = $administrador->ID_ADMINISTRADOR
                                          , ID_LOG_EJECUCION_WS = $idLogEjecucionWS
                                          , OBSERVACIONES = 'No se ha podido crear la dupla material-almacén para el almacen $rowMatAlmacenObs->REFERENCIA'";
                    $bd->ExecSQL($sqlInsert);
                    $idIncidencia = $bd->IdAsignado();
                    $sqlInsertObservacion = "INSERT INTO OBSERVACION_SISTEMA SET
                                             TIPO_OBJETO = 'INCIDENCIA_SISTEMA'
                                             ,ID_OBJETO = $idIncidencia
                                             ,TEXTO_OBSERVACION = 'No se ha podido crear la dupla material-almacén para el almacen $rowMatAlmacenObs->REFERENCIA'
                                             ,ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                             ,FECHA = '" . date("Y-m-d H:i:s") . "'";
                    $bd->ExecSQL($sqlInsertObservacion);
                    continue;
                endif;
                //COMPRUEBO GRUPO ARTICULOS
                if($rowMatObs->ID_FAMILIA_REPRO != $rowSolSus->ID_FAMILIA_REPRO):
                    //MRP_Controller
                    $objLinea->MRP_Controller = "";
                    $rowCentro = $bd->VerReg("CENTRO","ID_CENTRO",$rowMatAlmacenObs->ID_CENTRO);
                    $GLOBALS["NotificaErrorPorEmail"] = "No";
                    $sqlPlanificadorCentro = "SELECT * FROM PLANIFICADOR_CENTRO WHERE ID_CENTRO = " . $rowCentro->ID_CENTRO;
                    $resPlanificadorCentro = $bd->ExecSQL($sqlPlanificadorCentro);
                    if ($bd->NumRegs($resPlanificadorCentro) > 0):
                        if ($rowCentro->PLANIFICAR_POR_FAMILIA_REPRO == "1"):
                            $rowPlanificadorCentro = $bd->VerRegRest('PLANIFICADOR_CENTRO', "ID_CENTRO =  $rowCentro->ID_CENTRO  AND ID_FAMILIA_REPRO = $rowSolSus->ID_FAMILIA_REPRO");
                        else:
                            $rowPlanificadorCentro = $bd->VerRegRest("PLANIFICADOR_CENTRO", "ID_CENTRO = $rowCentro->ID_CENTRO AND TIPO_MATERIAL = '$rowSolSus->TIPO_MATERIAL'");
                        endif;
                        $rowPlanificador = $bd->VerReg("PLANIFICADOR", "ID_PLANIFICADOR", $rowPlanificadorCentro->ID_PLANIFICADOR, "No");
                        $objLinea->MRP_Controller = $rowPlanificador->REFERENCIA;
                    endif;
                endif;

                //AÑADO EL OBJETO AL ARRAY DE ALMACENES
                $ALMACENES[] = $objLinea;
            else: //SI NO EXISTIA EL MATERIAL ALMACEN BUSCO EN LAS TABLAS
                //LIMPIO LOS DATOS DEL OBJETO
                unset($objLinea);
                $objLinea = new stdClass();
                $rowAlmacen = $bd->VerReg("ALMACEN", "ID_ALMACEN", $rowMatAlmacenSol->ID_ALMACEN);
                $rowCentro = $bd->VerReg("CENTRO", "ID_CENTRO", $rowAlmacen->ID_CENTRO);
                //REFERENCIA DE CENTRO
                $objLinea->Plant_Code = $rowCentro->REFERENCIA;
                //REFERENCIA DE ALMACEN
                $objLinea->Warehouse = $rowAlmacen->REFERENCIA;

                //OBTENGO EL VALOR PARA MRP
                $rowMRPTipo = "";
                $objLinea->MRP_Type = "";
                $GLOBALS['NotificaErrorPorEmail'] = "No";
                if ($bd->VerReg("ALMACEN_TIPO_MRP", "ID_ALMACEN", $rowMatAlmacenSol->ID_ALMACEN, "No")):
                    if ($rowSolicitudMaterial->ESTATUS != "No bloqueado"):
                        $rowMRPTipo = $bd->VerRegRest("ALMACEN_TIPO_MRP", "ID_ALMACEN = $rowMatAlmacenSol->ID_ALMACEN AND STATUS_BLOQUEADO = 1");
                    else:
                        $rowMRPTipo = $bd->VerRegRest("ALMACEN_TIPO_MRP", "ID_ALMACEN = $rowMatAlmacenSol->ID_ALMACEN AND TIPO_MATERIAL = '". $rowSolicitudMaterial->TIPO_MATERIAL ."'");
                    endif;
                    $objLinea->MRP_Type = $rowMRPTipo->VALOR;
                endif;
                $objLinea->MRP_Area = "AP" . $rowAlmacen->REFERENCIA;

                //MRP_Controller
                $objLinea->MRP_Controller = "";
                $GLOBALS["NotificaErrorPorEmail"] = "No";
                $sqlPlanificadorCentro = "SELECT * FROM PLANIFICADOR_CENTRO WHERE ID_CENTRO = " . $rowCentro->ID_CENTRO;
                $resPlanificadorCentro = $bd->ExecSQL($sqlPlanificadorCentro);
                if ($bd->NumRegs($resPlanificadorCentro) > 0):
                    if ($rowCentro->PLANIFICAR_POR_FAMILIA_REPRO == "1"):
                        $rowPlanificadorCentro = $bd->VerRegRest('PLANIFICADOR_CENTRO', "ID_CENTRO =  $rowCentro->ID_CENTRO  AND ID_FAMILIA_REPRO = $rowFamiliaRepro->ID_FAMILIA_REPRO");
                    else:
                        $rowPlanificadorCentro = $bd->VerRegRest("PLANIFICADOR_CENTRO", "ID_CENTRO = $rowCentro->ID_CENTRO AND TIPO_MATERIAL = '$rowSolicitudMaterial->TIPO_MATERIAL'");
                    endif;
                    $rowPlanificador = $bd->VerReg("PLANIFICADOR", "ID_PLANIFICADOR", $rowPlanificadorCentro->ID_PLANIFICADOR, "No");
                    $objLinea->MRP_Controller = $rowPlanificador->REFERENCIA;
                endif;

                //Special_procurement_type
                $objLinea->Special_procurement_type = "";
                $GLOBALS["NotificaErrorPorEmail"] = "No";
                $rowFamiliaMaterial = $bd->VerReg("FAMILIA_MATERIAL", "ID_FAMILIA_MATERIAL", $rowSolSus->ID_FAMILIA_MATERIAL);
                if ($rowFamiliaMaterial->ES_FAMILIA_ESPECIAL):
                    $rowClaveAprovisionamientoAlmacen = $bd->VerRegRest("CLAVE_APROVISIONAMIENTO_ESPECIAL_ALMACEN", "ID_ALMACEN = $rowAlmacen->ID_ALMACEN AND ES_FAMILIA_ESPECIAL = $rowFamiliaMaterial->ES_FAMILIA_ESPECIAL  AND BAJA = 0", "No");
                    if ($rowClaveAprovisionamientoAlmacen):
                        $rowClaveAprovisionamiento = $bd->VerReg("CLAVE_APROVISIONAMIENTO_ESPECIAL","ID_CLAVE_APROVISIONAMIENTO_ESPECIAL",$rowClaveAprovisionamientoAlmacen->ID_CLAVE_APROVISIONAMIENTO_ESPECIAL);
                        $objLinea->Special_procurement_type = $rowClaveAprovisionamiento->REFERENCIA;
                    endif;
                else:
                    $rowClaveAprovisionamientoAlmacen = $bd->VerRegRest("CLAVE_APROVISIONAMIENTO_ESPECIAL_ALMACEN", "ID_ALMACEN = $rowAlmacen->ID_ALMACEN AND ES_REPARABLE = $rowSolicitudMaterial->REPARABLE AND BAJA = 0", "No");
                    if ($rowClaveAprovisionamientoAlmacen):
                        $rowClaveAprovisionamiento = $bd->VerReg("CLAVE_APROVISIONAMIENTO_ESPECIAL","ID_CLAVE_APROVISIONAMIENTO_ESPECIAL",$rowClaveAprovisionamientoAlmacen->ID_CLAVE_APROVISIONAMIENTO_ESPECIAL);
                        $objLinea->Special_procurement_type = $rowClaveAprovisionamiento->REFERENCIA;
                    endif;
                endif;

                //Lot_Size
                $rowFamiliaMaterial = $bd->VerReg("FAMILIA_MATERIAL", "ID_FAMILIA_MATERIAL", $rowSolicitudMaterial->ID_FAMILIA_MATERIAL);
                $objLinea->Lot_Size = "";
                $GLOBALS["NotificaErrorPorEmail"] = "No";
                if ($rowFamiliaMaterial->ES_FAMILIA_ESPECIAL):
                    $rowMedidaLote = $bd->VerRegRest("TAMANO_LOTE_ALMACEN", "ID_ALMACEN = $rowAlmacenSolicitud->ID_ALMACEN AND ES_FAMILIA_ESPECIAL = 1 AND BAJA = 0", "No");
                    $objLinea->Lot_Size = $rowMedidaLote->VALOR;
                else:
                    $rowMedidaLote = $bd->VerRegRest("TAMANO_LOTE_ALMACEN", "ID_ALMACEN = $rowAlmacenSolicitud->ID_ALMACEN AND ES_FAMILIA_ESPECIAL = 0 AND BAJA = 0", "No");
                    $objLinea->Lot_Size = $rowMedidaLote->VALOR;
                endif;

                //VALOR DE REDONDEO
                $objLinea->Rounding_value = $rowSolicitudMaterial->FACTOR_CONVERSION;
                //TAMAÑO LOTE MINIMO
                $objLinea->Minimum_Lot_Size = $rowSolicitudMaterial->FACTOR_CONVERSION;
                //GRUPO MRP
                $objLinea->MRP_Group = "0000";
                //STOCK DE SEGURIDAD
                $objLinea->Safety_Stock = "";
                //STOCK MAXIMO
                $objLinea->Maximum_Stock = "";
                //LOCALIZACION ALMACENAMIENTO ESTANDAR
                $objLinea->Default_storage_location = $rowAlmacen->REFERENCIA;

                //Planned_Delivery_Time_Days
                $objLinea->Planned_Delivery_Time_Days = "";
                $GLOBALS["NotificaErrorPorEmail"] = "No";
                $rowTiempoAprovisionamiento = $bd->VerRegRest("TIEMPO_APROVISIONAMIENTO_ALMACEN","ID_ALMACEN = $rowAlmacen->ID_ALMACEN AND ES_FAMILIA_ESPECIAL = $rowFamiliaMaterial->ES_FAMILIA_ESPECIAL AND BAJA = 0","No");
                if($rowTiempoAprovisionamiento):
                    $objLinea->Planned_Delivery_Time_Days = $rowTiempoAprovisionamiento->VALOR;
                endif;

                //Consider_Planned_Delivery_Time
                $objLinea->Consider_Planned_Delivery_Time = "X";

                //Procurement_Frequency
                $objLinea->Procurement_Frequency = "";
                $GLOBALS["NotificaErrorPorEmail"] = "No";
                $rowFrecuenciaAprovisionamiento = $bd->VerRegRest("FRECUENCIA_APROVISIONAMIENTO_ALMACEN","ID_ALMACEN = $rowAlmacen->ID_ALMACEN AND ES_FAMILIA_ESPECIAL = $rowFamiliaMaterial->ES_FAMILIA_ESPECIAL AND BAJA = 0","No");
                if($rowFrecuenciaAprovisionamiento):
                    $objLinea->Procurement_Frequency = $rowFrecuenciaAprovisionamiento->VALOR;
                endif;

                //AÑADO EL OBJETO AL ARRAY DE ALMACENES
                $ALMACENES[] = $objLinea;
            endif;
        endwhile;
        $objSalida->ALMACENES = $ALMACENES;

        return $objSalida;
    }
} // FIN CLASE