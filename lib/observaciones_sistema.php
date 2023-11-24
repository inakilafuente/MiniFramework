<?php

# observaciones_sistema
# Clase observaciones_sistema contiene todas las funciones necesarias para la interaccion con la clase observaciones_sistema
# Se incluira en las sesiones

class observaciones_sistema
{

    function __construct()
    {
    } // Fin observaciones_sistema

    function Grabar($tipoObjeto, $idObjeto, $texto, $categoria = NULL, $subcategoria = NULL, $fechaCreacion = NULL, $estado = NULL)
    {
        global $bd;
        global $administrador;

        //CATEGORIA POR DEFECTO SI ES NULO O VACIA, SOLO APLICA A LOS SIGUIENTES CASOS
        if (
            ($tipoObjeto == "NECESIDAD") ||
            ($tipoObjeto == "NECESIDAD_LINEA") ||
            ($tipoObjeto == "MOVIMIENTO_RECEPCION") ||
            ($tipoObjeto == "ORDEN_CONTRATACION") ||
            ($tipoObjeto == "ORDEN_TRANSPORTE") ||
            ($tipoObjeto == "AUTOFACTURA") ||
            ($tipoObjeto == "ORDEN_CONTRATACION_INCIDENCIA")
        ):
            if (($categoria == NULL) || ($categoria == "")):
                if ($administrador->esComprador()):
                    $categoria = 'Comprador';
                elseif ($administrador->esLogistico()):
                    $categoria = 'Logistico';
                elseif ($administrador->esTecnico()):
                    $categoria = 'Tecnico';
                endif;
            endif;
        endif;

        if ($fechaCreacion == NULL):
            $fechaCreacion = date("Y-m-d H:i:s");
        endif;

        $sql = "INSERT INTO OBSERVACION_SISTEMA SET
                ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
			    , FECHA = '" . $fechaCreacion . "'
                , TIPO_OBSERVACION = " . ($categoria == NULL ? 'NULL' : "'" . $categoria . "'") . "
                , SUBTIPO_OBSERVACION = " . ($subcategoria == NULL ? 'NULL' : "'" . $subcategoria . "'") . "
                , TIPO_OBJETO = '" . $tipoObjeto . "'
                , ID_OBJETO = $idObjeto
                , TEXTO_OBSERVACION = '" . $bd->escapeCondicional($texto) . "'
                , ESTADO = '$estado'";//exit($sql);
        $bd->ExecSQL($sql);

        $idNuevaObservacion = $bd->IdAsignado();

        //SI ES DE CONTROL DE CALIDAD, REGISTRAMOS QUE ES CON PROBELMAS
        if ($tipoObjeto == "CONTROL TIEMPO DE REVISION"):
            $rowMovimientoEntradaLinea = $bd->VerReg("MOVIMIENTO_ENTRADA_LINEA", "ID_MOVIMIENTO_ENTRADA_LINEA", $idObjeto, "No");
            if ($rowMovimientoEntradaLinea->CC_CON_PROBLEMAS == 0):
                $sqlUpdateMovimientoEntradaLinea = "UPDATE MOVIMIENTO_ENTRADA_LINEA SET
													CC_CON_PROBLEMAS = 1
													WHERE ID_MOVIMIENTO_ENTRADA_LINEA = $idObjeto";
                $bd->ExecSQL($sqlUpdateMovimientoEntradaLinea);
            endif;
        endif;

        return $idNuevaObservacion;
    }

    function Clonar($tipoObjetoOrigen, $idObjetoOrigen, $tipoObjetoDestino, $idObjetoDestino)
    {
        global $bd;
        global $administrador;

        //CONSULTO LOS REGISTROS DEL OBJETO ORIGEN
        $sql    = "SELECT *
						FROM OBSERVACION_SISTEMA 
						WHERE TIPO_OBJETO = '" . $tipoObjetoOrigen . "' AND ID_OBJETO = '" . $idObjetoOrigen . "'";
        $result = $bd->ExecSQL($sql);
        //RECORRO LOS REGISTROS ORIGEN ENCONTRADOS
        while ($row = $bd->SigReg($result)):
            //CREO LOS REGISTROS DESTINO CORRESPONDIENTES
            $sqlInsert = "INSERT INTO OBSERVACION_SISTEMA SET
										ID_ADMINISTRADOR = $row->ID_ADMINISTRADOR 
										, FECHA = '" . $row->FECHA . "'
										, TIPO_OBSERVACION = '" . ($row->TIPO_OBSERVACION == NULL ? 'NULL' : $row->TIPO_OBSERVACION) . "'
										, TIPO_OBJETO = '" . $tipoObjetoDestino . "' 
										, ID_OBJETO = $idObjetoDestino 
										, TEXTO_OBSERVACION = '" . $bd->escapeCondicional($row->TEXTO_OBSERVACION) . "'";
            $bd->ExecSQL($sqlInsert);
        endwhile;
    }

    /**
     * @param $tipoObjeto
     * @param $idObjeto
     * @param array $tipo_observacion ejemplo array('Comprador','Tecnico')
     */
    function getObservaciones($tipoObjeto, $idObjeto, $tipo_observacion = array(), $subtipo_observacion = array(), $sqlWhere = "")
    {
        global $bd;

        if (($idObjeto == NULL) || ($idObjeto == "")):
            $sql = "SELECT ID_ADMINISTRADOR, FECHA, TEXTO_OBSERVACION, TIPO_OBSERVACION,SUBTIPO_OBSERVACION, ID_OBSERVACION_SISTEMA, ESTADO
                FROM OBSERVACION_SISTEMA OS
                WHERE TIPO_OBJETO = '" . $tipoObjeto . "' AND FALSE" . $sqlWhere;
        else:
            $sql = "SELECT ID_ADMINISTRADOR, FECHA, TEXTO_OBSERVACION, TIPO_OBSERVACION,SUBTIPO_OBSERVACION, ID_OBSERVACION_SISTEMA, ESTADO
						FROM OBSERVACION_SISTEMA OS
						WHERE TIPO_OBJETO = '" . $tipoObjeto . "' AND ID_OBJETO = " . $idObjeto . $sqlWhere;
        endif;
        if (is_array($tipo_observacion)):
            if (count((array) $tipo_observacion) > 0) {
                $sql .= " AND TIPO_OBSERVACION IN ('" . implode("','", (array) $tipo_observacion) . "')";
            }
        endif;
        if (is_array($subtipo_observacion)):
            if (count((array) $subtipo_observacion) > 0) {
                $sql .= " AND SUBTIPO_OBSERVACION IN ('" . implode(",", (array) $subtipo_observacion) . "')";
            }
        endif;
        $sql .= " ORDER BY ID_OBSERVACION_SISTEMA DESC";

        $result = $bd->ExecSQL($sql);

        return $result;
    }

    /**
     * @param $subTipoObservacion
     */
    function getTipoChatPorSubtipoObservacion($subTipoObservacion)
    {
        global $bd;

        $sql = "SELECT * FROM TIPO_CHAT WHERE SUBTIPO_OBSERVACION = '$subTipoObservacion'";

        $result = $bd->ExecSQL($sql);

        return $result;
    }

    function Leer($tipoObjeto, $idObjeto, $tipo_observacion = '', $mostrarUsuario = false, $mostrarFechaHora = false, $mostrarCategoria = false)
    {
        global $bd;
        global $auxiliar;
        global $administrador;

        //VARIABLE PARA ACUMULAR LOS TEXTOS
        $texto = "";

        $tipo_observacion_array = array();
        if (($tipo_observacion != '') && ($tipo_observacion != null)):
            $tipo_observacion_array = array($tipo_observacion);
        endif;

        $result = $this->getObservaciones($tipoObjeto, $idObjeto, $tipo_observacion_array);
        while ($row = $bd->SigReg($result)):
            //RECUPERO EL USUARIO EN CASO DE TENER QUE MOSTRARLO
            if ($mostrarUsuario == true):
                $rowAdministradorObservaciones = $bd->VerReg("ADMINISTRADOR", "ID_ADMINISTRADOR", $row->ID_ADMINISTRADOR);
            endif;

            //CONCATENO EL TEXTO A MOSTRAR
            $texto .= ' · ';
            $texto .= ($mostrarFechaHora != true ? '' : $auxiliar->fechaFmtoEsp($row->FECHA) . ' ' . substr( (string) $row->FECHA, 11, 5) . " - ");
            $texto .= (($mostrarCategoria == true && $row->TIPO_OBSERVACION != NULL) ? $auxiliar->traduce($row->TIPO_OBSERVACION, $administrador->ID_IDIOMA) . " - " : '');
            $texto .= ($mostrarUsuario != true ? '' : $rowAdministradorObservaciones->NOMBRE . " - ");
            $texto .= ($row->SUBTIPO_OBSERVACION != "" ? $auxiliar->traduce($row->SUBTIPO_OBSERVACION, $administrador->ID_IDIOMA) . ": " : "");
            $texto .= $row->TEXTO_OBSERVACION . "\r\n";
        endwhile;

        return $texto;
    }

    function Borrar($idObservacionSistema)
    {
        global $bd;

        $sql = "DELETE FROM OBSERVACION_SISTEMA WHERE ID_OBSERVACION_SISTEMA = $idObservacionSistema";
        $bd->ExecSQL($sql);
    }

} // FIN CLASE