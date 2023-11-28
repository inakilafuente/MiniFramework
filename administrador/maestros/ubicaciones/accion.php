<?
// PATHS DE LA WEB
$pathRaiz   = "../../";
$pathClases = "../../../";

//echo "Almacén: $idAlmacen Centro: $idCentro"; exit;

// INCLUDES DE LIBRERIAS PROPIAS
require_once $pathClases . "lib/basedatos.php";
require_once $pathClases . "lib/administrador.php";
require_once $pathClases . "lib/comprobar.php";
require_once $pathClases . "lib/html.php";
require_once $pathClases . "lib/navegar.php";
require_once $pathClases . "lib/auxiliar.php";

session_start();
include $pathRaiz . "seguridad_admin.php";
if ($pantallaSolar == 1):
    $tituloPag = $auxiliar->traduce("Unidad Organizativa", $administrador->ID_IDIOMA);
    $tituloNav = $auxiliar->traduce("Construccion Solar", $administrador->ID_IDIOMA) . " >> " . $tituloPag;

    $ZonaTablaPadre    = "ConstruccionSolar";
    $ZonaSubTablaPadre = "ConstruccionSolarMenuEstructura";
    $ZonaTabla         = "ConstruccionSolarUnidadOrganizativa";

    // COMPRUEBA SI TIENE PERMISOS
    if ($administrador->Hayar_Permiso_Perfil('ADM_CONSTRUCCION_SOLAR_UNIDAD_ORGANIZATIVA') < 2):
        $html->PagError("SinPermisos");
    endif;

elseif ($pantallaConstruccion == 1):

    $tituloPag = $auxiliar->traduce("Unidad Organizativa", $administrador->ID_IDIOMA);
    $tituloNav = $auxiliar->traduce("Construccion", $administrador->ID_IDIOMA) . " >> " . $tituloPag;

    $ZonaTablaPadre    = "Construccion";
    $ZonaSubTablaPadre = "ConstruccionMenuEstructura";
    $ZonaTabla         = "ConstruccionUnidadOrganizativa";

    // COMPRUEBA SI TIENE PERMISOS
    if ($administrador->Hayar_Permiso_Perfil('ADM_CONSTRUCCION_UNIDAD_ORGANIZATIVA') < 2):
        $html->PagError("SinPermisos");
    endif;

else:
    $tituloPag         = $auxiliar->traduce("Ubicaciones", $administrador->ID_IDIOMA);
    $tituloNav         = $auxiliar->traduce("Maestros", $administrador->ID_IDIOMA) . " >> " . $auxiliar->traduce("Ubicaciones", $administrador->ID_IDIOMA);
    $ZonaTablaPadre    = "Maestros";
    $ZonaSubTablaPadre = "MaestrosSubmenuAlmacen";
    $ZonaTabla         = "MaestrosUbicaciones";

// COMPRUEBA SI TIENE PERMISOS
    if ($administrador->Hayar_Permiso_Perfil('ADM_MAESTROS_UBICACIONES') < 2):
        $html->PagError("SinPermisos");
    endif;
endif;

// RECUERDO DE BUSQUEDAS REALIZADAS
include $pathRaiz . "busqueda_recordar.php";

//GENERO EL ARRAY DE UBICACIONES QUE NO CONTABILIZAN PARA EL STOCK DISPONIBLE EN EL ALMACEN
$arrUbicacionNoContabilizaStockDisponible = explode(",", LISTA_UBICACIONES_NO_CONTABILIZAN_STOCK_DISPONIBLE);

if ($accion == "Modificar"):

    //COMPROBAR LA NO EXISTENCIA DE CAMPOS OBLIGATORIOS VACÍOS
    unset($arr_tx);
    $i                   = 0;
    $arr_tx[$i]["err"]   = $auxiliar->traduce("Ubicacion", $administrador->ID_IDIOMA);
    $arr_tx[$i]["valor"] = $txUbicacion;
    $i++;

    //SI VIENE DE CONSTRUCCION O CONSTRUCCION SOLAR CAMBIAMOS EL TIPO DE UBICACION
    $selTipoUbicacion = ($pantallaSolar == 1 ? "Power Block" : ($pantallaConstruccion == 1 ? "Maquina" : $selTipoUbicacion));

    //SI ES DE TIPO MAQUINA O POWER BLOCK, COMPRUEBO NOMBRE MAQUINA
    if (($selTipoUbicacion == 'Maquina') || ($selTipoUbicacion == 'Power Block')):
        $arr_tx[$i]["err"]   = $auxiliar->traduce("Denominacion", $administrador->ID_IDIOMA) . " " . $auxiliar->traduce("Unidad Organizativa Proceso", $administrador->ID_IDIOMA);
        $arr_tx[$i]["valor"] = $txNombreMaquina;
        $i++;
        $arr_tx[$i]["err"]   = $auxiliar->traduce("Tipo Unidad Organizativa Proceso", $administrador->ID_IDIOMA);
        $arr_tx[$i]["valor"] = $txUnidadOrganizativaProceso;
        $i++;
    endif;
    $comp->ComprobarTexto($arr_tx, "CampoSinRellenar");
    unset($arr_tx);
    $i = 0;
    if ($txPanelesPowerblock != ""):
        $arr_tx[$i]["err"]   = $auxiliar->traduce("Paneles Powerblock", $administrador->ID_IDIOMA);
        $arr_tx[$i]["valor"] = $txPanelesPowerblock;
        $i                   = $i + 1;
    endif;
    if ($txPotenciaPowerblock != ""):
        $arr_tx[$i]["err"]   = $auxiliar->traduce("Potencia Megavatio Pico", $administrador->ID_IDIOMA);
        $arr_tx[$i]["valor"] = $txPotenciaPowerblock;
        $i                   = $i + 1;
    endif;
    $comp->ComprobarEnt($arr_tx, "NoEntero");
    unset($arr_tx);

    $sqlTipoPowerBlock = "";
    if (($selTipoUbicacion == 'Power Block')):
        $sqlTipoPowerBlock = " , POTENCIA_PMW_POWERBLOCK = '" . $bd->escapeCondicional($txPotenciaPowerblock) . "'
						        , CANTIDAD_PANELES_POWERBLOCK = '" . $bd->escapeCondicional($txPanelesPowerblock) . "' ";
    endif;

    // COMPRUEBO NO CREADO OTRA UBICACION CON IGUAL NOMBRE Y ALMACEN
    $sql          = "SELECT COUNT(ID_UBICACION) as NUM_REGS FROM UBICACION WHERE UBICACION='" . $bd->escapeCondicional($txUbicacion) . "' AND ID_ALMACEN = " . $bd->escapeCondicional($idAlmacen) . " AND ID_UBICACION <> " . $bd->escapeCondicional($idUbicacion);
    $resultNumero = $bd->ExecSQL($sql);
    $rowNumero    = $bd->SigReg($resultNumero);
    if ($rowNumero->NUM_REGS > 0) $html->PagErrorCond("Error", "Error", "UbicacionExistente", "error.php");

    //BUSCO LA UBICACION
    $rowUbicacion = $bd->VerReg("UBICACION", "ID_UBICACION", $idUbicacion);

    //BUSCO EL ALMACEN
    $rowAlmacen = $bd->VerReg("ALMACEN", "ID_ALMACEN", $rowUbicacion->ID_ALMACEN);

    //COMPRUEBO QUE TENGA PERMISOS DE ESCRITURA EN LA UBICACION
    $html->PagErrorCondicionado($administrador->comprobarUbicacionPermiso($rowUbicacion->ID_UBICACION, "Escritura"), "==", false, "SinPermisosSubzona");

    //COMPRUEBO QUE NO SEA DE STOCK EXTERNALIZADO
    $html->PagErrorCondicionado($rowAlmacen->TIPO_ALMACEN, "==", "externalizado", "ModificacionUbicacionStockExternalizado");

    //COMPROBAMOS UBICACION CENTRO FISICO
    if ($idUbicacionCentroFisico != ""): //VIENE DEL DESPLEGABLE O DEL AJAX
        $NotificaErrorPorEmail = "No";
        $rowUbicacionCentroFisico = $bd->VerReg("UBICACION_CENTRO_FISICO", "ID_UBICACION_CENTRO_FISICO", $idUbicacionCentroFisico, "No");
        $html->PagErrorCondicionado($rowUbicacionCentroFisico, "==", false, "ErrorDatosUbicacionCentroFisico");
        unset($NotificaErrorPorEmail);
    elseif ($txUbicacionCentroFisico != ""):
        $NotificaErrorPorEmail = "No";
        $rowUbicacionCentroFisico = $bd->VerRegRest("UBICACION_CATEGORIA", "REFERENCIA_UBICACION = '" . $bd->escapeCondicional($txUbicacionCentroFisico) . "' AND ID_CENTRO_FISICO = ". $rowAlmacen->ID_CENTRO_FISICO, "No");
        $html->PagErrorCondicionado($rowUbicacionCentroFisico, "==", false, "ErrorDatosUbicacionCentroFisico");
        $idUbicacionCentroFisico = $rowUbicacionCentroFisico->ID_UBICACION_CENTRO_FISICO;
        unset($NotificaErrorPorEmail);
    else:
        $idUbicacionCentroFisico = NULL;
    endif;

    //COMPROBAMOS NO CAMBIAN TIPO UBICACION DE LA UBI CF Y OBTENEMOS DATOS COMUNES
    if ($rowUbicacionCentroFisico != false):
        $html->PagErrorCondicionado($rowUbicacion->TIPO_UBICACION, "!=", $rowUbicacionCentroFisico->TIPO_UBICACION_CF, "TipoUbicacionDiferenteCF");
        $html->PagErrorCondicionado($rowAlmacen->ID_CENTRO_FISICO, "!=", $rowUbicacionCentroFisico->ID_CENTRO_FISICO, "CentroFisicoNoCoincide");

        $idCategoriaUbicacion = $rowUbicacionCentroFisico->ID_UBICACION_CATEGORIA;
        $selAPQ = $rowUbicacionCentroFisico->CLASE_APQ;
        $chPrecioFijo = $rowUbicacionCentroFisico->PRECIO_FIJO;
    endif;

    //BUSCO EL TIPO DE UBICACION
    $tipoUbicacion = $rowUbicacion->TIPO_UBICACION;

    //COMPROBAMOS SI LA UBICACIÓN TIENE MATERIAL. SI LO TIENE Y SE ESTÁ INTENTANDO MODIFICAR EL TIPO DEVOLVEREMOS ERROR.
    if ($tipoUbicacion != $selTipoUbicacion):
        //COMPROBAREMOS SI EXISTE MATERIAL EN ESA UBICACION
        $NotificaErrorPorEmail = "No";
        $rowMatUbi             = $bd->VerRegRest("MATERIAL_UBICACION", "ID_UBICACION = $idUbicacion AND ACTIVO = 1", "No");
        unset($NotificaErrorPorEmail);
        if ($rowMatUbi):
            $html->PagError("UbicacionConMaterial");
        endif;
    endif;

    //COMPROBAMOS SI LA UBICACIÓN ES DE TIPO SECTOR Y TIENE MATERIAL NO PUEDA MODIFICAR EL NUMERO DE PANELES POR SECTOR
    if (($tipoUbicacion == 'Sector') && ($rowUbicacion->CANTIDAD_PANELES <> $bd->escapeCondicional($txCantidadPanelesSector))):
        //COMPROBAREMOS SI EXISTE MATERIAL EN ESA UBICACION
        $NotificaErrorPorEmail = "No";
        $rowMatUbi             = $bd->VerRegRest("MATERIAL_UBICACION", "ID_UBICACION = $idUbicacion AND ACTIVO = 1", "No");
        unset($NotificaErrorPorEmail);
        if ($rowMatUbi):
            $html->PagError("CantidadPenelesCambiadoExistiendoStock");
        endif;
    endif;

    //SI SE MARCA COMO DE TIPO ENTRADA COMPROBAR QUE NO HAY OTRA QUE SEA DEL MISMO TIPO EN EL MISMO ALMACEN
    if ($selTipoUbicacion == 'Entrada'):
        $NotificaErrorPorEmail = "No";
        $rowUbicacionEntrada   = $bd->VerRegRest("UBICACION", "ID_ALMACEN=" . $idAlmacen . " AND TIPO_UBICACION='" . $selTipoUbicacion . "' AND ID_UBICACION <> " . $idUbicacion, "No");
        unset($NotificaErrorPorEmail);
        if ($rowUbicacionEntrada != false):
            $html->PagError("ErrorVariasUbicacionEntradaPorAlmacen");
        endif;
    endif;

    //SI SE MARCA COMO DE TIPO SALIDA COMPROBAR QUE NO HAY OTRA QUE SEA DEL MISMO TIPO EN EL MISMO ALMACEN
    if ($selTipoUbicacion == 'Salida'):
        $NotificaErrorPorEmail = "No";
        $rowUbicacionSalida    = $bd->VerRegRest("UBICACION", "ID_ALMACEN=" . $idAlmacen . " AND TIPO_UBICACION='" . $selTipoUbicacion . "' AND ID_UBICACION <> " . $idUbicacion, "No");
        unset($NotificaErrorPorEmail);
        if ($rowUbicacionSalida != false):
            $html->PagError("ErrorVariasUbicacionSalidaPorAlmacen");
        endif;
    endif;

    //SI SE MARCA COMO DE TIPO EMBARQUE COMPROBAR QUE NO HAY OTRA QUE SEA DEL MISMO TIPO EN EL MISMO ALMACEN
    if ($selTipoUbicacion == 'Embarque'):
        $NotificaErrorPorEmail = "No";
        $rowUbicacionEmbarque  = $bd->VerRegRest("UBICACION", "ID_ALMACEN=" . $idAlmacen . " AND TIPO_UBICACION='" . $selTipoUbicacion . "' AND ID_UBICACION <> " . $idUbicacion, "No");
        unset($NotificaErrorPorEmail);
        if ($rowUbicacionEmbarque != false):
            $html->PagError("ErrorVariasUbicacionEmbarquePorAlmacen");
        endif;
    endif;

    //SI SE MARCA COMO DE TIPO CONSUMOS MASIVOS COMPROBAR QUE NO HAY OTRA QUE SEA DEL MISMO TIPO EN EL MISMO ALMACEN
    if ($selTipoUbicacion == 'Consumos Masivos'):
        $NotificaErrorPorEmail       = "No";
        $rowUbicacionConsumosMasivos = $bd->VerRegRest("UBICACION", "ID_ALMACEN=" . $idAlmacen . " AND TIPO_UBICACION='" . $selTipoUbicacion . "' AND ID_UBICACION <> " . $idUbicacion, "No");
        unset($NotificaErrorPorEmail);
        if ($rowUbicacionConsumosMasivos != false):
            $html->PagError("ErrorVariasUbicacionConsumosMasivosPorAlmacen");
        endif;
    endif;

    //SI SE MARCA COMO DE TIPO RETORNOS MASIVOS COMPROBAR QUE NO HAY OTRA QUE SEA DEL MISMO TIPO EN EL MISMO ALMACEN
    if ($selTipoUbicacion == 'Retornos Masivos'):
        $NotificaErrorPorEmail       = "No";
        $rowUbicacionRetornosMasivos = $bd->VerRegRest("UBICACION", "ID_ALMACEN=" . $idAlmacen . " AND TIPO_UBICACION='" . $selTipoUbicacion . "' AND ID_UBICACION <> " . $idUbicacion, "No");
        unset($NotificaErrorPorEmail);
        if ($rowUbicacionRetornosMasivos != false):
            $html->PagError("ErrorVariasUbicacionRetornosMasivosPorAlmacen");
        endif;
    endif;

    //SI SE DA DE BAJA LA UBICACION HAY QUE COMPROBAR VARIAS COSAS
    if ($chBaja == '1'):
        //QUE NO CONTENGA MATERIAL
        $sqlMaterialUbicacion = "SELECT SUM(STOCK_TOTAL) AS CANTIDAD
															 FROM MATERIAL_UBICACION 
															 WHERE ID_UBICACION = '" . $bd->escapeCondicional($idUbicacion) . "'";
        $resMaterialUbicacion = $bd->ExecSQL($sqlMaterialUbicacion);
        while ($rowMaterialUbicacion = $bd->SigReg($resMaterialUbicacion)):
            if (($rowMaterialUbicacion->CANTIDAD != NULL) && ($rowMaterialUbicacion->CANTIDAD != 0)):
                $html->PagError("UbicacionConMaterialUbicado");
            endif;
        endwhile;

        //QUE NO ESTE INVOLUCRADA EN NINGUN CONTEO ACTIVO
        $sqlConteoUbicacion = "SELECT COUNT(*) AS NUM
                                 FROM INVENTARIO_ORDEN_CONTEO_LINEA IOCL
                                 INNER JOIN INVENTARIO_ORDEN_CONTEO IOC ON IOC.ID_INVENTARIO_ORDEN_CONTEO = IOCL.ID_INVENTARIO_ORDEN_CONTEO
                                 WHERE IOCL.ID_UBICACION = " . $bd->escapeCondicional($idUbicacion) . " AND IOCL.BAJA = 0 AND IOC.ESTADO <> 'Finalizado' AND IOC.BAJA = 0";
        $resConteoUbicacion = $bd->ExecSQL($sqlConteoUbicacion);
        $rowConteoUbicacion = $bd->SigReg($resConteoUbicacion);
        if ($rowConteoUbicacion->NUM > 0):
            $html->PagError("UbicacionEnInventario");
        endif;

        //QUE NO ESTE SIENDO UTILIZADA EN UNA LINEA DE UN MOVIMIENTO DE ENTRADA ACTIVA
        $sqlMovimientoEntrada = "SELECT COUNT(*) AS NUM
                                  FROM MOVIMIENTO_ENTRADA_LINEA MEL
                                  JOIN MOVIMIENTO_ENTRADA ME ON MEL.ID_MOVIMIENTO_ENTRADA = ME.ID_MOVIMIENTO_ENTRADA
                                  WHERE MEL.ID_UBICACION = " . $bd->escapeCondicional($idUbicacion) . " AND MEL.LINEA_ANULADA = 0 AND MEL.BAJA = 0 AND ME.ESTADO = 'En Proceso'";
        $resMovimientoEntrada = $bd->ExecSQL($sqlMovimientoEntrada);
        $rowMovimientoEntrada = $bd->SigReg($resMovimientoEntrada);
        if ($rowMovimientoEntrada->NUM > 0):
            //GUARDO LOS MOVIMIENTOS DE ENTRADA AFECTADOS
            $movimientosEntrada = "";
            $sqlMovimientoEntradaError = "SELECT DISTINCT MEL.ID_MOVIMIENTO_ENTRADA
                                          FROM MOVIMIENTO_ENTRADA_LINEA MEL
                                          JOIN MOVIMIENTO_ENTRADA ME ON MEL.ID_MOVIMIENTO_ENTRADA = ME.ID_MOVIMIENTO_ENTRADA
                                          WHERE MEL.ID_UBICACION = " . $bd->escapeCondicional($idUbicacion) . " AND MEL.LINEA_ANULADA = 0 AND MEL.BAJA = 0 AND ME.ESTADO = 'En Proceso'";
            $resMovimientoEntradaError = $bd->ExecSQL($sqlMovimientoEntradaError);
            while ($rowMovimientoEntradaError = $bd->SigReg($resMovimientoEntradaError)):
                $movimientosEntrada .=  $rowMovimientoEntradaError->ID_MOVIMIENTO_ENTRADA . ", ";
            endwhile;
            $movimientosEntrada = trim( (string)$movimientosEntrada, ", ");
            $_SESSION["movimientosEntradaBajaUbicacion"] = $movimientosEntrada;
            $html->PagError("UbicacionEnMovimientoEntrada");
        endif;
    endif;

    //COMPROBAMOS LA CATEGORIA DE LA UBICACION
    if ($idCategoriaUbicacion != ""): //VIENE DEL DESPLEGABLE O DEL AJAX
        $NotificaErrorPorEmail = "No";
        $rowCategoriaUbicacion = $bd->VerReg("UBICACION_CATEGORIA", "ID_UBICACION_CATEGORIA", $idCategoriaUbicacion, "No");
        $html->PagErrorCondicionado($rowCategoriaUbicacion, "==", false, "ErrorDatosCategoriaUbicacion");
        unset($NotificaErrorPorEmail);
    elseif ($txCategoriaUbicacion != ""):
        $NotificaErrorPorEmail = "No";
        $rowCategoriaUbicacion = $bd->VerReg("UBICACION_CATEGORIA", "NOMBRE", $bd->escapeCondicional($txCategoriaUbicacion), "No");
        $html->PagErrorCondicionado($rowCategoriaUbicacion, "==", false, "ErrorDatosCategoriaUbicacion");
        $idCategoriaUbicacion = $rowCategoriaUbicacion->ID_UBICACION_CATEGORIA;
        unset($NotificaErrorPorEmail);
    else:
        $idCategoriaUbicacion = NULL;
    endif;

    //SI ES DE TIPO MAQUINA O POWER BLOCK, COMPRUEBO TIPO UOP
    $rowUOP       = false;
    $sqlUpdateUOP = "";
    if (($selTipoUbicacion == 'Maquina') || ($selTipoUbicacion == 'Power Block')):
        //COMPROBAMOS SI SE HA SELECCIONADO CORRECTAMENTE
        if ($idUnidadOrganizativaProceso != ""): //VIENE DEL DESPLEGABLE O DEL AJAX
            $NotificaErrorPorEmail = "No";
            $rowUOP                = $bd->VerReg("UNIDAD_ORGANIZATIVA_PROCESO", "ID_UNIDAD_ORGANIZATIVA_PROCESO", $idUnidadOrganizativaProceso, "No");
            $html->PagErrorCondicionado($rowUOP, "==", false, "ErrorUOP");
            unset($NotificaErrorPorEmail);

        elseif ($idUnidadOrganizativaProceso == ""):
            $NotificaErrorPorEmail = "No";
            $rowUOP                = $bd->VerReg("UNIDAD_ORGANIZATIVA_PROCESO", ($administrador->ID_IDIOMA == "ESP" ? "TIPO_UOP_ESP" : "TIPO_UOP_ENG"), $bd->escapeCondicional($txUnidadOrganizativaProceso), "No");
            $html->PagErrorCondicionado($rowUOP, "==", false, "ErrorUOP");
            unset($NotificaErrorPorEmail);
        endif;


        //SI SE HA MODIFICADO LA UOP, REASIGNAMOS ORDEN Y QUITAMOS GAP
        if ($rowUOP->ID_UNIDAD_ORGANIZATIVA_PROCESO != $rowUbicacion->ID_UNIDAD_ORGANIZATIVA_PROCESO):
            //BUSCO EL MAXIMO NUMERO DE UOP
            $UltimoNumeroLinea       = 0;
            $sqlUltimoNumeroLinea    = "SELECT MAX(CAST(ORDEN_MAQUINA AS UNSIGNED)) AS NUMERO_LINEA FROM UBICACION WHERE ID_ALMACEN = $rowUbicacion->ID_ALMACEN AND BAJA = 0 AND ID_UNIDAD_ORGANIZATIVA_PROCESO = $rowUOP->ID_UNIDAD_ORGANIZATIVA_PROCESO";
            $resultUltimoNumeroLinea = $bd->ExecSQL($sqlUltimoNumeroLinea);
            if ($resultUltimoNumeroLinea != false):
                $rowUltimoNumeroLinea = $bd->SigReg($resultUltimoNumeroLinea);
                if ($rowUltimoNumeroLinea->NUMERO_LINEA != NULL):
                    $UltimoNumeroLinea = $rowUltimoNumeroLinea->NUMERO_LINEA;
                endif;
            endif;
            $SiguienteNumeroLinea = $UltimoNumeroLinea + 1;

            //GUARDAMOS EL UPDATE
            $sqlUpdateUOP = ", ORDEN_MAQUINA = $SiguienteNumeroLinea
                             , NUM_DIAS_GAP_MAQUINAS = 0
                             , NUM_HORAS_GAP_MAQUINAS = 0";
        endif;
    endif;


    //GENERO LA TRANSACCION
    $bd->begin_transaction();


    //ACTUALIZO LA TABLA CONTENEDORES DE TIPO GAVETA
    if ($rowUbicacion->TIPO_UBICACION == 'Gaveta'):
        //COMPRUEBO QUE EL ALMACEN DE ORIGEN NO SEA EL MISMO QUE EL ALMACEN DE DESTINO DE LA GAVETA
        $html->PagErrorCondicionado($idAlmacenDestinoGaveta, "==", $idAlmacen, "AlmacenDestinoGavetaIgualAlmacenOrigen");

        //COMPRUEBO QUE HAYA RELLENADO PASILLO Y PROFUNDIDAD CON NUMEROS DE 2 DIGITOS
        unset($arr_tx);
        $arr_tx[0]["err"]   = $auxiliar->traduce("Pasillo", $administrador->ID_IDIOMA);
        $arr_tx[0]["valor"] = $txPasilloGaveta;
        $arr_tx[1]["err"]   = $auxiliar->traduce("Profundidad", $administrador->ID_IDIOMA);
        $arr_tx[1]["valor"] = $txProfundidadGaveta;

        //COMPROBAR LA NO EXISTENCIA DE CAMPOS OBLIGATORIOS VACÍOS
        $comp->ComprobarTexto($arr_tx, "CampoSinRellenar");

        //COMPROBAMOS QUE SEAN CAMPOS ENTEROS
        $comp->ComprobarEnt($arr_tx, "NoEntero");

        //COMPROBAMOS QUE LA LONGITUD SEA 2
        $html->PagErrorCondicionado(strlen( (string)$txPasilloGaveta), "!=", 2, "ErrorLongitudPasilloIncorrecta");
        $html->PagErrorCondicionado(strlen( (string)$txProfundidadGaveta), "!=", 2, "ErrorLongitudProfundidadIncorrecta");

        //BUSCO EL CONTENEDOR
        $NotificaErrorPorEmail = "No";
        $rowContenedorGaveta   = $bd->VerRegRest("CONTENEDOR", "ID_UBICACION = $rowUbicacion->ID_UBICACION AND TIPO = 'Gaveta'", "No");
        unset($NotificaErrorPorEmail);

        if (($chBaja == '1') && ($rowUbicacion->BAJA == 0)):    //SE DA DE BAJA LA UBICACION
            //ELIMINO EL CONTENEDOR DE TIPO GAVETA
            $sqlDelete = "DELETE FROM CONTENEDOR WHERE ID_UBICACION = $idUbicacion AND TIPO = 'Gaveta'";
            $bd->ExecSQL($sqlDelete);

        elseif (($chBaja == '') && ($rowUbicacion->BAJA == 1)):    //SE BORRA EL CAMPO BAJA DE LA UBICACION
            unset($arr_tx);
            $arr_tx[0]["err"]   = $auxiliar->traduce("Almacén Destino Gaveta", $administrador->ID_IDIOMA);
            $arr_tx[0]["valor"] = $txAlmacenDestinoGaveta;

            //COMPROBAR LA NO EXISTENCIA DE CAMPOS OBLIGATORIOS VACÍOS
            $comp->ComprobarTexto($arr_tx, "CampoSinRellenar");

            //COMPROBAMOS SI EL ALMACEN DESTINO GAVETA SE HA SELECCIONADO CORRECTAMENTE
            if ($idAlmacenDestinoGaveta != ""): //VIENE DEL DESPLEGABLE O DEL AJAX
                $arrCampos               = explode("-", (string)$txAlmacenDestinoGaveta);
                $NotificaErrorPorEmail   = "No";
                $rowAlmacenDestinoGaveta = $bd->VerReg("ALMACEN", "ID_ALMACEN", $idAlmacenDestinoGaveta, "No");
                $html->PagErrorCondicionado((($bd->escapeCondicional(trim( (string)$arrCampos[0]))) != trim( (string)$bd->escapeCondicional($rowAlmacenDestinoGaveta->REFERENCIA))) || (($bd->escapeCondicional(trim( (string)$arrCampos[1]))) != trim( (string)$bd->escapeCondicional($rowAlmacenDestinoGaveta->NOMBRE))), "==", true, "ErrorDatosAlmacenDestinoGaveta");
                unset($NotificaErrorPorEmail);
            elseif ($idAlmacenDestinoGaveta == ""):
                $NotificaErrorPorEmail   = "No";
                $rowAlmacenDestinoGaveta = $bd->VerReg("ALMACEN", "REFERENCIA", $bd->escapeCondicional($txAlmacenDestinoGaveta), "No");
                $html->PagErrorCondicionado($rowAlmacenDestinoGaveta, "==", false, "ErrorDatosAlmacenDestinoGaveta");
                $idAlmacenDestinoGaveta = $rowAlmacenDestinoGaveta->ID_ALMACEN;
                unset($NotificaErrorPorEmail);
            endif;

            //COMPRUEBO QUE NO HAYA OTRA GAVETA CON ESTE ALMACEN ORIGEN Y ALMACEN DESTINO
            $num = $bd->NumRegsTabla("CONTENEDOR", "TIPO = 'Gaveta' AND ID_ALMACEN_DESTINO_GAVETA = $idAlmacenDestinoGaveta AND ID_UBICACION IN (SELECT ID_UBICACION FROM UBICACION WHERE ID_ALMACEN = $rowUbicacion->ID_ALMACEN)");
            $html->PagErrorCondicionado($num, ">", 0, "GavetaAlmacenRepetida");

            //COMPRUEBO QUE EL ALMACEN TENGA RUTA ASOCIADA
            $numRutas = $auxiliar->getNumRutasAlmacen($rowAlmacenDestinoGaveta->ID_ALMACEN);
            //COMPRUEBO QUE EL ALMACEN TENGA RUTA
            if ($numRutas > 0):
                //SI HAY MAS DE UNA RUTA AL ALMACEN DE DESTINO COJO LA QUE TIENEN EN COMUN ORIGEN Y DESTINO
                $idRuta = $auxiliar->getIdRutaDeAlmacenes($idAlmacenDestinoGaveta, $rowAlmacenDestinoGaveta->ID_ALMACEN);
                //SI NO HAY UNA RUTA COMÚN PONGO UNA DE LAS RUTAS DEL ALMACEN DE DESTINO
                if ($idRuta == NULL):
                    $arrayRutas = $auxiliar->getIdsRutasDeAlmacen($rowAlmacenDestinoGaveta->ID_ALMACEN);
                    $idRuta     = $arrayRutas[0];
                endif;
            else:
                $html->PagError("AlmacenDestinoGavetaSinRuta");
            endif;

            //COMPRUEBO QUE EL ALMACEN TENGA SUBRUTA ASOCIADA
            //$html->PagErrorCondicionado($rowAlmacenDestinoGaveta->ID_SUBRUTA, "==", NULL, "AlmacenDestinoGavetaSinSubRuta");

            //INSERTO EL CONTENEDOR DE TIPO GAVETA
            $sqlInsert = "INSERT INTO CONTENEDOR SET
											TIPO = 'Gaveta' 
											, ID_UBICACION = $idUbicacion 
											, ID_ALMACEN = '" . $bd->escapeCondicional($idAlmacen) . "'
											, ID_ALMACEN_DESTINO_GAVETA = $idAlmacenDestinoGaveta 
											, ID_RUTA = $idRuta 
											, GAVETA_PASILLO = '" . $bd->escapeCondicional($txPasilloGaveta) . "' 
											, GAVETA_PROFUNDIDAD = '" . $bd->escapeCondicional($txProfundidadGaveta) . "'";
            $bd->ExecSQL($sqlInsert);

        elseif ($idAlmacenDestinoGaveta != $rowContenedorGaveta->ID_ALMACEN_DESTINO_GAVETA):    //SE MODIFICA EL ALMACEN DESTINO DE LA GAVETA
            //COMPRUEBO QUE EL CONTENEDOR ESTE VACIO
            $num = $bd->NumRegsTabla("CONTENEDOR_LINEA", "ID_CONTENEDOR = $rowContenedorGaveta->ID_CONTENEDOR", "No");
            $html->PagErrorCondicionado($num, ">", 0, "GavetaConMaterial");

            //ELIMINO EL CONTENEDOR DE TIPO GAVETA
            $sqlDelete = "DELETE FROM CONTENEDOR WHERE ID_CONTENEDOR = $rowContenedorGaveta->ID_CONTENEDOR";
            $bd->ExecSQL($sqlDelete);

            //COMPRUEBO QUE NO HAYA OTRA GAVETA CON ESTE ALMACEN ORIGEN Y ALMACEN DESTINO
            $num = $bd->NumRegsTabla("CONTENEDOR", "TIPO = 'Gaveta' AND ID_ALMACEN_DESTINO_GAVETA = $idAlmacenDestinoGaveta AND ID_UBICACION IN (SELECT ID_UBICACION FROM UBICACION WHERE ID_ALMACEN = $rowUbicacion->ID_ALMACEN)");
            $html->PagErrorCondicionado($num, ">", 0, "GavetaAlmacenRepetida");

            //COMPRUEBO QUE EL ALMACEN TENGA RUTA ASOCIADA
            $numRutas = $auxiliar->getNumRutasAlmacen($idAlmacenDestinoGaveta);

            //COMPRUEBO QUE EL ALMACEN TENGA RUTA
            if ($numRutas > 0):
                //SI HAY MAS DE UNA RUTA AL ALMACEN DE DESTINO COJO LA QUE TIENEN EN COMUN ORIGEN Y DESTINO
                $idRuta = $auxiliar->getIdRutaDeAlmacenes($idAlmacen, $idAlmacenDestinoGaveta);
                //SI NO HAY UNA RUTA COMÚN PONGO UNA DE LAS RUTAS DEL ALMACEN DE DESTINO
                if ($idRuta == NULL):
                    $arrayRutas = $auxiliar->getIdsRutasDeAlmacen($idAlmacenDestinoGaveta);
                    $idRuta     = $arrayRutas[0];
                endif;
            else:
                $html->PagError("AlmacenDestinoGavetaSinRuta");
            endif;

            //COMPRUEBO QUE EL ALMACEN TENGA SUBRUTA ASOCIADA
            //$html->PagErrorCondicionado($rowAlmacenDestinoGaveta->ID_SUBRUTA, "==", NULL, "AlmacenDestinoGavetaSinSubRuta");

            //INSERTO EL CONTENEDOR DE TIPO GAVETA
            $sqlInsert = "INSERT INTO CONTENEDOR SET
											TIPO = 'Gaveta' 
											, ID_UBICACION = $idUbicacion 
											, ID_ALMACEN = '" . $bd->escapeCondicional($idAlmacen) . "'
											, ID_ALMACEN_DESTINO_GAVETA = $idAlmacenDestinoGaveta 
											, ID_RUTA = $idRuta 
											, GAVETA_PASILLO = '" . $bd->escapeCondicional($txPasilloGaveta) . "' 
											, GAVETA_PROFUNDIDAD = '" . $bd->escapeCondicional($txProfundidadGaveta) . "'";
            $bd->ExecSQL($sqlInsert);

        elseif (
            ($txPasilloGaveta != $rowContenedorGaveta->GAVETA_PASILLO) ||
            ($txProfundidadGaveta != $rowContenedorGaveta->GAVETA_PROFUNDIDAD)
        ):
            //UPDATO EL CONTENEDOR DE TIPO GAVETA
            $sqlInsert = "UPDATE CONTENEDOR SET
											GAVETA_PASILLO = '" . $bd->escapeCondicional($txPasilloGaveta) . "' 
											, GAVETA_PROFUNDIDAD = '" . $bd->escapeCondicional($txProfundidadGaveta) . "' 
											WHERE ID_CONTENEDOR = $rowContenedorGaveta->ID_CONTENEDOR";
            $bd->ExecSQL($sqlInsert);

        endif;
    endif;

    //SI ES DE TIPO SECTOR, COMPRUEBO DATOS ADICIONALES
    $sqlTipoSector = "";
    if ($rowUbicacion->TIPO_UBICACION == 'Sector'):

        //COMPRUEBO QUE HAYA RELLENADO LO NECESARIO
        $arr_tx[0]["err"]   = $auxiliar->traduce("Tipo Sector", $administrador->ID_IDIOMA);
        $arr_tx[0]["valor"] = $idTipoSector;

        //COMPROBAR LA NO EXISTENCIA DE CAMPOS OBLIGATORIOS VACÍOS
        $comp->ComprobarTexto($arr_tx, "CampoSinRellenar");

        //COMPROBAMOS QUE SEAN CAMPOS ENTEROS
        $comp->ComprobarEnt($arr_tx, "NoEntero");

        $sqlTipoSector = " , CANTIDAD_PANELES = '" . $bd->escapeCondicional($txCantidadPanelesSector) . "' , ID_TIPO_SECTOR = " . $idTipoSector;
    endif;

    //ACTUALIZAMOS
    $sql = "UPDATE UBICACION SET
            UBICACION ='" . trim( (string)$bd->escapeCondicional($txUbicacion)) . "'
            ,ID_ALMACEN = '" . $bd->escapeCondicional($idAlmacen) . "'
            , TIPO_UBICACION = " . ($bd->escapeCondicional($selTipoUbicacion) == '' ? 'NULL' : "'" . $bd->escapeCondicional($selTipoUbicacion) . "'") . "
            , TIPO_PREVENTIVO = " . ((($selTipoUbicacion == 'Preventivo') && ($chPreventivoDePendientes == 1)) ? "'Pendientes'" : 'NULL') . "
            , ID_UBICACION_CATEGORIA = " . ($bd->escapeCondicional($idCategoriaUbicacion) == '' ? 'NULL' : $bd->escapeCondicional($idCategoriaUbicacion)) . "
            , CLASE_APQ = " . ($bd->escapeCondicional($selAPQ) == '' ? 'NULL' : "'" . $bd->escapeCondicional($selAPQ) . "'") . "
            , PRECIO_FIJO = '" . $chPrecioFijo . "'
            , DESCRIPCION = '" . $bd->escapeCondicional($txDescripcion) . "'
            , VALIDA_STOCK_DISPONIBLE = " . ((($bd->escapeCondicional($selTipoUbicacion) != '') && (in_array($bd->escapeCondicional($selTipoUbicacion), (array) $arrUbicacionNoContabilizaStockDisponible))) ? 0 : 1) . "
            , AUTOSTORE = '" . $chAutostore . "'
            , BAJA = '" . $chBaja . "'
            , NOMBRE_MAQUINA = '" . $bd->escapeCondicional($txNombreMaquina) . "'
            , ID_UNIDAD_ORGANIZATIVA_PROCESO = " . ($rowUOP != false ? $rowUOP->ID_UNIDAD_ORGANIZATIVA_PROCESO : "NULL") . "
            , ID_UBICACION_CENTRO_FISICO = " . ($rowUbicacionCentroFisico != false ? $rowUbicacionCentroFisico->ID_UBICACION_CENTRO_FISICO : "NULL") . "
            $sqlTipoPowerBlock
            $sqlTipoSector $sqlUpdateUOP
            WHERE ID_UBICACION = $rowUbicacion->ID_UBICACION";
    $bd->ExecSQL($sql);

    //BUSCO LA UBICACION ACTUALIZADA
    $rowUbicacionActualizada = $bd->VerReg("UBICACION", "ID_UBICACION", $rowUbicacion->ID_UBICACION);

    // LOG MOVIMIENTOS
    $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Maestro", $rowUbicacion->ID_UBICACION, "Ubicación", "UBICACION", $rowUbicacion, $rowUbicacionActualizada);


    //FINALIZO LA TRANSACCION
    $bd->commit_transaction();


elseif ($accion == "Insertar"):

    //COMPROBAMOS CAMPOS OBLIGATORIOS
    $i                   = 0;
    $arr_tx[$i]["err"]   = $auxiliar->traduce("Ubicacion", $administrador->ID_IDIOMA);
    $arr_tx[$i]["valor"] = $txUbicacion;
    $i++;
    $arr_tx[$i]["err"]   = $auxiliar->traduce("Almacén", $administrador->ID_IDIOMA);
    $arr_tx[$i]["valor"] = $txAlmacen;
    $i++;
    //SI VIENE DE CONSTRUCCION O CONSTRUCCION SOLAR CAMBIAMOS EL TIPO DE UBICACION
    $selTipoUbicacion = ($pantallaSolar == 1 ? "Power Block" : ($pantallaConstruccion == 1 ? "Maquina" : $selTipoUbicacion));
    //SI ES DE TIPO MAQUINA O POWER BLOCK, COMPRUEBO NOMBRE MAQUINA
    if (($selTipoUbicacion == 'Maquina') || ($selTipoUbicacion == 'Power Block')):
        $arr_tx[$i]["err"]   = $auxiliar->traduce("Denominacion", $administrador->ID_IDIOMA) . " " . $auxiliar->traduce("Unidad Organizativa Proceso", $administrador->ID_IDIOMA);
        $arr_tx[$i]["valor"] = $txNombreMaquina;
        $i++;
        $arr_tx[$i]["err"]   = $auxiliar->traduce("Tipo Unidad Organizativa Proceso", $administrador->ID_IDIOMA);
        $arr_tx[$i]["valor"] = $txUnidadOrganizativaProceso;
        $i++;
    endif;
    //COMPROBAR LA NO EXISTENCIA DE CAMPOS OBLIGATORIOS VACÍOS
    $comp->ComprobarTexto($arr_tx, "CampoSinRellenar");

    unset($arr_tx);
    $i = 0;
    if ($txPanelesPowerblock != ""):
        $arr_tx[$i]["err"]   = $auxiliar->traduce("Paneles Powerblock", $administrador->ID_IDIOMA);
        $arr_tx[$i]["valor"] = $txPanelesPowerblock;
        $i                   = $i + 1;
    endif;
    if ($txPotenciaPowerblock != ""):
        $arr_tx[$i]["err"]   = $auxiliar->traduce("Potencia Megavatio Pico", $administrador->ID_IDIOMA);
        $arr_tx[$i]["valor"] = $txPotenciaPowerblock;
        $i                   = $i + 1;
    endif;
    $comp->ComprobarEnt($arr_tx, "NoEntero");
    unset($arr_tx);

    $sqlTipoPowerBlock = "";
    if (($selTipoUbicacion == 'Power Block')):
        $sqlTipoPowerBlock = " , POTENCIA_PMW_POWERBLOCK = '" . $bd->escapeCondicional($txPotenciaPowerblock) . "'
						        , CANTIDAD_PANELES_POWERBLOCK = '" . $bd->escapeCondicional($txPanelesPowerblock) . "' ";
    endif;

    //COMPROBAMOS SI EL ALMACEN SE HA SELECCIONADO CORRECTAMENTE
    if ($idAlmacen != ""): //VIENE DEL DESPLEGABLE O DEL AJAX
        $NotificaErrorPorEmail = "No";
        $rowAlmacen            = $bd->VerReg("ALMACEN", "ID_ALMACEN", $idAlmacen, "No");
        $html->PagErrorCondicionado($rowAlmacen, "==", false, "ErrorDatosAlmacen");
        unset($NotificaErrorPorEmail);
    elseif ($idAlmacen == ""):
        $NotificaErrorPorEmail = "No";
        $rowAlmacen            = $bd->VerReg("ALMACEN", "REFERENCIA", $bd->escapeCondicional($txAlmacen), "No");
        $html->PagErrorCondicionado($rowAlmacen, "==", false, "ErrorDatosAlmacen");
        $idAlmacen = $rowAlmacen->ID_ALMACEN;
        unset($NotificaErrorPorEmail);
    endif;

    //COMPRUEBO QUE TENGA PERMISOS DE ESCRITURA EN EL ALMACEN
    $html->PagErrorCondicionado($administrador->comprobarAlmacenPermiso($rowAlmacen->ID_ALMACEN, "Escritura"), "==", false, "SinPermisosSubzona");

    //COMPRUEBO QUE NO SEA DE STOCK EXTERNALIZADO
    $html->PagErrorCondicionado($rowAlmacen->TIPO_ALMACEN, "==", "externalizado", "InsercionUbicacionStockExternalizado");

    //COMPROBAMOS UBICACION CENTRO FISICO
    if ($idUbicacionCentroFisico != ""): //VIENE DEL DESPLEGABLE O DEL AJAX
        $NotificaErrorPorEmail = "No";
        $rowUbicacionCentroFisico = $bd->VerReg("UBICACION_CENTRO_FISICO", "ID_UBICACION_CENTRO_FISICO", $idUbicacionCentroFisico, "No");
        $html->PagErrorCondicionado($rowUbicacionCentroFisico, "==", false, "ErrorDatosUbicacionCentroFisico");
        unset($NotificaErrorPorEmail);
    elseif ($txUbicacionCentroFisico != ""):
        $NotificaErrorPorEmail = "No";
        $rowUbicacionCentroFisico = $bd->VerRegRest("UBICACION_CATEGORIA", "REFERENCIA_UBICACION = '" . $bd->escapeCondicional($txUbicacionCentroFisico) . "' AND ID_CENTRO_FISICO = ". $rowAlmacen->ID_CENTRO_FISICO, "No");
        $html->PagErrorCondicionado($rowUbicacionCentroFisico, "==", false, "ErrorDatosUbicacionCentroFisico");
        $idUbicacionCentroFisico = $rowUbicacionCentroFisico->ID_UBICACION_CENTRO_FISICO;
        unset($NotificaErrorPorEmail);
    else:
        $idUbicacionCentroFisico = NULL;
    endif;

    //COMPROBAMOS NO CAMBIAN TIPO UBICACION DE LA UBI CF Y OBTENEMOS DATOS COMUNES
    if ($rowUbicacionCentroFisico != false):
        $html->PagErrorCondicionado($selTipoUbicacion, "!=", $rowUbicacionCentroFisico->TIPO_UBICACION_CF, "TipoUbicacionDiferenteCF");
        $html->PagErrorCondicionado($rowAlmacen->ID_CENTRO_FISICO, "!=", $rowUbicacionCentroFisico->ID_CENTRO_FISICO, "CentroFisicoNoCoincide");

        $idCategoriaUbicacion = $rowUbicacionCentroFisico->ID_UBICACION_CATEGORIA;
        $selAPQ = $rowUbicacionCentroFisico->CLASE_APQ;
        $chPrecioFijo = $rowUbicacionCentroFisico->PRECIO_FIJO;
    endif;

    //COMPROBAMOS LA CATEGORIA DE LA UBICACION
    if ($idCategoriaUbicacion != ""): //VIENE DEL DESPLEGABLE O DEL AJAX
        $arrCampos             = explode("-", (string)$txCategoriaUbicacion);
        $NotificaErrorPorEmail = "No";
        $rowCategoriaUbicacion = $bd->VerReg("UBICACION_CATEGORIA", "ID_UBICACION_CATEGORIA", $idCategoriaUbicacion, "No");
        $html->PagErrorCondicionado($rowCategoriaUbicacion, "==", false, "ErrorDatosCategoriaUbicacion");
        unset($NotificaErrorPorEmail);
    elseif ($txCategoriaUbicacion != ""):
        $NotificaErrorPorEmail = "No";
        $rowCategoriaUbicacion = $bd->VerReg("UBICACION_CATEGORIA", "NOMBRE", $bd->escapeCondicional($txCategoriaUbicacion), "No");
        $html->PagErrorCondicionado($rowCategoriaUbicacion, "==", false, "ErrorDatosCategoriaUbicacion");
        $idCategoriaUbicacion = $rowCategoriaUbicacion->ID_UBICACION_CATEGORIA;
        unset($NotificaErrorPorEmail);
    else:
        $idCategoriaUbicacion = NULL;
    endif;

    //SI SE DA DE ALTA LA UBICACION Y ES DE TIPO EMBARQUE COMPROBAR QUE NO HAY OTRA QUE SEA DEL MISMO TIPO EN EL MISMO ALMACEN
    if ($bd->escapeCondicional($selTipoUbicacion) == 'Embarque'):
        $NotificaErrorPorEmail = "No";
        $rowUbicacionEmbarque  = $bd->VerRegRest("UBICACION", "ID_ALMACEN=" . $idAlmacen . " AND TIPO_UBICACION='" . $selTipoUbicacion . "'", "No");
        unset($NotificaErrorPorEmail);
        if ($rowUbicacionEmbarque != false):
            $html->PagError("ErrorVariasUbicacionEmbarquePorAlmacen");
        endif;
    endif;

    //SI SE DA DE ALTA LA UBICACION Y ES DE TIPO CONSUMOS MASIVOS COMPROBAR QUE NO HAY OTRA QUE SEA DEL MISMO TIPO EN EL MISMO ALMACEN
    if ($selTipoUbicacion == 'Consumos Masivos'):
        $NotificaErrorPorEmail       = "No";
        $rowUbicacionConsumosMasivos = $bd->VerRegRest("UBICACION", "ID_ALMACEN=" . $idAlmacen . " AND TIPO_UBICACION='" . $selTipoUbicacion . "' AND ID_UBICACION <> " . $idUbicacion, "No");
        unset($NotificaErrorPorEmail);
        if ($rowUbicacionConsumosMasivos != false):
            $html->PagError("ErrorVariasUbicacionConsumosMasivosPorAlmacen");
        endif;
    endif;

    //SI SE DA DE ALTA LA UBICACION Y ES DE TIPO RETORNOS MASIVOS COMPROBAR QUE NO HAY OTRA QUE SEA DEL MISMO TIPO EN EL MISMO ALMACEN
    if ($selTipoUbicacion == 'Retornos Masivos'):
        $NotificaErrorPorEmail       = "No";
        $rowUbicacionRetornosMasivos = $bd->VerRegRest("UBICACION", "ID_ALMACEN=" . $idAlmacen . " AND TIPO_UBICACION='" . $selTipoUbicacion . "' AND ID_UBICACION <> " . $idUbicacion, "No");
        unset($NotificaErrorPorEmail);
        if ($rowUbicacionRetornosMasivos != false):
            $html->PagError("ErrorVariasUbicacionRetornosMasivosPorAlmacen");
        endif;
    endif;

    //SI ES DE TIPO GAVETA COMPROBAMOS QUE HAYA RELLENADO EL ALMACEN DESTINO GAVETA
    if ($bd->escapeCondicional($selTipoUbicacion) == 'Gaveta'):
        //COMPRUEBO QUE EL ALMACEN DE ORIGEN NO SEA EL MISMO QUE EL ALMACEN DE DESTINO DE LA GAVETA
        $html->PagErrorCondicionado($idAlmacenDestinoGaveta, "==", $idAlmacen, "AlmacenDestinoGavetaIgualAlmacenOrigen");

        unset($arr_tx);
        $arr_tx[0]["err"]   = $auxiliar->traduce("Almacén Destino Gaveta", $administrador->ID_IDIOMA);
        $arr_tx[0]["valor"] = $txUbicacion;

        //COMPROBAR LA NO EXISTENCIA DE CAMPOS OBLIGATORIOS VACÍOS
        $comp->ComprobarTexto($arr_tx, "CampoSinRellenar");

        //COMPROBAMOS SI EL ALMACEN DESTINO GAVETA SE HA SELECCIONADO CORRECTAMENTE
        if ($idAlmacenDestinoGaveta != ""): //VIENE DEL DESPLEGABLE O DEL AJAX
            $arrCampos               = explode("-", (string)$txAlmacenDestinoGaveta);
            $NotificaErrorPorEmail   = "No";
            $rowAlmacenDestinoGaveta = $bd->VerReg("ALMACEN", "ID_ALMACEN", $idAlmacenDestinoGaveta, "No");
            $html->PagErrorCondicionado((($bd->escapeCondicional(trim( (string)$arrCampos[0]))) != trim( (string)$bd->escapeCondicional($rowAlmacenDestinoGaveta->REFERENCIA))) || (($bd->escapeCondicional(trim( (string)$arrCampos[1]))) != trim( (string)$bd->escapeCondicional($rowAlmacenDestinoGaveta->NOMBRE))), "==", true, "ErrorDatosAlmacenDestinoGaveta");
            unset($NotificaErrorPorEmail);
        elseif ($idAlmacenDestinoGaveta == ""):
            $NotificaErrorPorEmail   = "No";
            $rowAlmacenDestinoGaveta = $bd->VerReg("ALMACEN", "REFERENCIA", $bd->escapeCondicional($txAlmacenDestinoGaveta), "No");
            $html->PagErrorCondicionado($rowAlmacenDestinoGaveta, "==", false, "ErrorDatosAlmacenDestinoGaveta");
            $idAlmacenDestinoGaveta = $rowAlmacenDestinoGaveta->ID_ALMACEN;
            unset($NotificaErrorPorEmail);
        endif;

        //COMPRUEBO QUE HAYA RELLENADO PASILLO Y PROFUNDIDAD CON NUMEROS DE 2 DIGITOS
        unset($arr_tx);
        $arr_tx[0]["err"]   = $auxiliar->traduce("Pasillo", $administrador->ID_IDIOMA);
        $arr_tx[0]["valor"] = $txPasilloGaveta;
        $arr_tx[1]["err"]   = $auxiliar->traduce("Profundidad", $administrador->ID_IDIOMA);
        $arr_tx[1]["valor"] = $txProfundidadGaveta;

        //COMPROBAR LA NO EXISTENCIA DE CAMPOS OBLIGATORIOS VACÍOS
        $comp->ComprobarTexto($arr_tx, "CampoSinRellenar");

        //COMPROBAMOS QUE SEAN CAMPOS ENTEROS
        $comp->ComprobarEnt($arr_tx, "NoEntero");

        //COMPROBAMOS QUE LA LONGITUD SEA 2
        $html->PagErrorCondicionado(strlen( (string)$txPasilloGaveta), "!=", 2, "ErrorLongitudPasilloIncorrecta");
        $html->PagErrorCondicionado(strlen( (string)$txProfundidadGaveta), "!=", 2, "ErrorLongitudProfundidadIncorrecta");

        //COMPRUEBO QUE NO HAYA OTRA GAVETA CON ESTE ALMACEN ORIGEN Y ALMACEN DESTINO
        $num = $bd->NumRegsTabla("CONTENEDOR", "TIPO = 'Gaveta' AND ID_ALMACEN_DESTINO_GAVETA = $idAlmacenDestinoGaveta AND ID_UBICACION IN (SELECT ID_UBICACION FROM UBICACION WHERE ID_ALMACEN = $idAlmacen)");
        $html->PagErrorCondicionado($num, ">", 0, "GavetaAlmacenRepetida");

        //COMPRUEBO QUE EL ALMACEN TENGA RUTA ASOCIADA
        //$html->PagErrorCondicionado($rowAlmacenDestinoGaveta->ID_RUTA, "==", NULL, "AlmacenDestinoGavetaSinRuta");
        $numRutas = $auxiliar->getNumRutasAlmacen($rowAlmacenDestinoGaveta->ID_ALMACEN);
        $html->PagErrorCondicionado($numRutas, "==", 0, "AlmacenDestinoGavetaSinRuta");

        //COMPRUEBO QUE EL ALMACEN TENGA SUBRUTA ASOCIADA
        //$html->PagErrorCondicionado($rowAlmacenDestinoGaveta->ID_SUBRUTA, "==", NULL, "AlmacenDestinoGavetaSinSubRuta");
    endif;
    //FIN SI ES DE TIPO GAVETA COMPROBAMOS QUE HAYA RELLENADO EL ALMACEN DESTINO GAVETA

    // COMPRUEBO NO CREADO OTRA UBICACION CON IGUAL NOMBRE Y ALMACEN
    $sql = "SELECT COUNT(ID_UBICACION) as NUM_REGS FROM UBICACION WHERE UBICACION='" . $bd->escapeCondicional($txUbicacion) . "' AND ID_ALMACEN = " . $bd->escapeCondicional($idAlmacen);

    $resultNumero = $bd->ExecSQL($sql);
    $rowNumero    = $bd->SigReg($resultNumero);
    if ($rowNumero->NUM_REGS > 0) $html->PagErrorCond("Error", "Error", "UbicacionExistente", "error.php");


    //SI ES DE TIPO SECTOR, COMPRUEBO DATOS ADICIONALES
    $sqlTipoSector = "";
    if ($selTipoUbicacion == 'Sector'):

        //COMPRUEBO QUE HAYA RELLENADO LO NECESARIO
        $arr_tx[0]["err"]   = $auxiliar->traduce("Tipo Sector", $administrador->ID_IDIOMA);
        $arr_tx[0]["valor"] = $idTipoSector;

        //COMPROBAR LA NO EXISTENCIA DE CAMPOS OBLIGATORIOS VACÍOS
        $comp->ComprobarTexto($arr_tx, "CampoSinRellenar");

        //COMPROBAMOS QUE SEAN CAMPOS ENTEROS
        $comp->ComprobarEnt($arr_tx, "NoEntero");

        $sqlTipoSector = " , CANTIDAD_PANELES = '" . $bd->escapeCondicional($txCantidadPanelesSector) . "' , ID_TIPO_SECTOR = " . $idTipoSector;

    endif;

    //SI ES DE TIPO MAQUINA O POWER BLOCK, COMPRUEBO TIPO UOP
    $rowUOP = false;
    if (($selTipoUbicacion == 'Maquina') || ($selTipoUbicacion == 'Power Block')):
        //COMPROBAMOS SI SE HA SELECCIONADO CORRECTAMENTE
        if ($idUnidadOrganizativaProceso != ""): //VIENE DEL DESPLEGABLE O DEL AJAX
            $NotificaErrorPorEmail = "No";
            $rowUOP                = $bd->VerReg("UNIDAD_ORGANIZATIVA_PROCESO", "ID_UNIDAD_ORGANIZATIVA_PROCESO", $idUnidadOrganizativaProceso, "No");
            $html->PagErrorCondicionado($rowUOP, "==", false, "ErrorUOP");
            unset($NotificaErrorPorEmail);

        elseif ($idUnidadOrganizativaProceso == ""):
            $NotificaErrorPorEmail = "No";
            $rowUOP                = $bd->VerReg("UNIDAD_ORGANIZATIVA_PROCESO", ($administrador->ID_IDIOMA == "ESP" ? "TIPO_UOP_ESP" : "TIPO_UOP_ENG"), $bd->escapeCondicional($txUnidadOrganizativaProceso), "No");
            $html->PagErrorCondicionado($rowUOP, "==", false, "ErrorUOP");
            unset($NotificaErrorPorEmail);
        endif;
    endif;


    //INICIO LA TRANSACCION
    $bd->begin_transaction();


    // INSERTO EL REGISTROEN LA BD
    $sql       = "INSERT INTO UBICACION SET
						UBICACION ='" . trim( (string)$bd->escapeCondicional($txUbicacion)) . "'
						,ID_ALMACEN = '" . $bd->escapeCondicional($idAlmacen) . "'
						, TIPO_UBICACION = " . ($bd->escapeCondicional($selTipoUbicacion) == '' ? 'NULL' : "'" . $bd->escapeCondicional($selTipoUbicacion) . "'") . "
                        , TIPO_PREVENTIVO = " . ((($selTipoUbicacion == 'Preventivo') && ($chPreventivoDePendientes == 1)) ? "'Pendientes'" : 'NULL') . "
						, ID_UBICACION_CATEGORIA = " . ($bd->escapeCondicional($idCategoriaUbicacion) == '' ? 'NULL' : $bd->escapeCondicional($idCategoriaUbicacion)) . "
                        , CLASE_APQ = " . ($bd->escapeCondicional($selAPQ) == '' ? 'NULL' : "'" . $bd->escapeCondicional($selAPQ) . "'") . "
						, PRECIO_FIJO = '" . $chPrecioFijo . "'
						, DESCRIPCION = '" . $bd->escapeCondicional($txDescripcion) . "'
                        , VALIDA_STOCK_DISPONIBLE = " . ((($bd->escapeCondicional($selTipoUbicacion) != '') && (in_array($bd->escapeCondicional($selTipoUbicacion), (array) $arrUbicacionNoContabilizaStockDisponible))) ? 0 : 1) . "
                        , NOMBRE_MAQUINA = '" . $bd->escapeCondicional($txNombreMaquina) . "'
                        , ID_UNIDAD_ORGANIZATIVA_PROCESO = " . ($rowUOP != false ? $rowUOP->ID_UNIDAD_ORGANIZATIVA_PROCESO : "NULL") . "
                        , ID_UBICACION_CENTRO_FISICO = " . ($rowUbicacionCentroFisico != false ? $rowUbicacionCentroFisico->ID_UBICACION_CENTRO_FISICO : "NULL") . "
						, AUTOSTORE = '" . $chAutostore . "'
						, BAJA = '" . $chBaja . "'
						$sqlTipoPowerBlock $sqlTipoSector";
    $TipoError = "ErrorEjecutarSql";
    $bd->ExecSQL($sql);

    //OBTENGO ID CREADO
    $idUbicacion = $bd->IdAsignado();


    //SI ES DE TIPO GAVETA
    if ($bd->escapeCondicional($selTipoUbicacion) == 'Gaveta'):
        //COMPRUEBO QUE EL ALMACEN TENGA RUTA ASOCIADA
        $numRutas = $auxiliar->getNumRutasAlmacen($rowAlmacenDestinoGaveta->ID_ALMACEN);

        //SI HAY MAS DE UNA RUTA AL ALMACEN DE DESTINO COJO LA QUE TIENEN EN COMUN ORIGEN Y DESTINO
        $idRuta = $auxiliar->getIdRutaDeAlmacenes($idAlmacenDestinoGaveta, $rowAlmacenDestinoGaveta->ID_ALMACEN);
        //SI NO HAY UNA RUTA COMÚN PONGO UNA DE LAS RUTAS DEL ALMACEN DE DESTINO
        if (($idRuta == NULL) && ($numRutas > 0)):
            $arrayRutas = $auxiliar->getIdsRutasDeAlmacen($rowAlmacenDestinoGaveta->ID_ALMACEN);
            $idRuta     = $arrayRutas[0];
        endif;

        //GENERO EL CONTENEDOR
        $sqlInsert = "INSERT INTO CONTENEDOR SET
										TIPO = 'Gaveta' 
										, ID_UBICACION = $idUbicacion 
										, ID_ALMACEN = '" . $bd->escapeCondicional($idAlmacen) . "'
										, ID_ALMACEN_DESTINO_GAVETA = $idAlmacenDestinoGaveta 
										, ID_RUTA = $idRuta
										, GAVETA_PASILLO = '" . $bd->escapeCondicional($txPasilloGaveta) . "' 
										, GAVETA_PROFUNDIDAD = '" . $bd->escapeCondicional($txProfundidadGaveta) . "'";
        $bd->ExecSQL($sqlInsert);
    endif;
    //FIN SI ES DE TIPO GAVETA


    //FINALIZO LA TRANSACCION
    $bd->commit_transaction();

    // LOG MOVIMIENTOS
    $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Creación", "Maestro", $idUbicacion, "Ubicación");

elseif ($accion == "bajaMasivaUbicaciones"):

    if ($administrador->Hayar_Permiso_Perfil('ADM_MAESTROS_UBICACIONES_BAJA_MASIVA') < 2):
        $html->PagError("SinPermisos");
    endif;

    //DEFINIMOS PAGINA DE ERROR
    $Pagina_Error = "error_pequeno.php";

    //VARIABLES PARA CONTROLAR LO MOSTRADO
    $strOk   = "";
    $filasOk = 0;
    $strKo   = "";
    $filasKo = 0;

    //CONTROLAR SI UNA LINEA SE HA FORMADO BIEN
    $commit = false;
    //RECORREMOS LAS LINEAS SELECCIONADAS
    $linea = 1;

    foreach ($_POST as $clave => $valor):

        $errorLinea = false;

        //COMPROBAMOS QUE LA LINEA ESTE MARCADA
        if ((substr( (string) $clave, 0, 8) == 'chLinea_') && ($valor == 1)):
            //CALCULO EL NUMERO DE LINEA
            $linea       = substr( (string) $clave, 8);
            $idUbicacion = $bd->escapeCondicional(${"idUbicacion" . $linea});
            //BUSCO EL MATERIAL UBICACION
            $rowUbicacion = $bd->VerReg("UBICACION", "ID_UBICACION", $idUbicacion, "No");
            //BUSCO EL ALMACEN
            $rowAlmacen = $bd->VerReg("ALMACEN", "ID_ALMACEN", $rowUbicacion->ID_ALMACEN, "No");
            //CUPERO MENSAJE ERROR
            $mensajeError = $auxiliar->traduce("Ubicacion", $administrador->ID_IDIOMA) . ":" . $rowUbicacion->UBICACION . " \n";

            if ($rowAlmacen->TIPO_ALMACEN == "externalizado"):
                //SI LA UBICACIÓN ES DE STOCK EXTERNALIZADO, NO DEJAMOS DARLA DE BAJA
                $errorLinea = true;
                $strKo      .= $auxiliar->traduce("La ubicacion que intenta dar de baja es de tipo stock externalizado", $administrador->ID_IDIOMA) . ". " . $mensajeError;
            endif;

            //COMPROBAR QUE NO CONTENGA MATERIAL
            $sqlMaterialUbicacion = "SELECT SUM(STOCK_TOTAL) AS CANTIDAD
															 FROM MATERIAL_UBICACION
															 WHERE ID_UBICACION =  $rowUbicacion->ID_UBICACION";

            $resMaterialUbicacion = $bd->ExecSQL($sqlMaterialUbicacion);
            while ($rowMaterialUbicacion = $bd->SigReg($resMaterialUbicacion)):
                if (($rowMaterialUbicacion->CANTIDAD != NULL) && ($rowMaterialUbicacion->CANTIDAD != 0)):
                    $errorLinea = true;
                    $strKo      .= $auxiliar->traduce("La ubicación que intenta dar de baja tiene material ubicado", $administrador->ID_IDIOMA) . ". " . $mensajeError;

                endif;
            endwhile;
            //QUE NO ESTE SIENDO UTILIZADA EN UNA LINEA DE UN MOVIMIENTO DE ENTRADA ACTIVA
            $sqlMovimientoEntrada = "SELECT COUNT(*) AS NUM
                                      FROM MOVIMIENTO_ENTRADA_LINEA MEL
                                      JOIN MOVIMIENTO_ENTRADA ME ON MEL.ID_MOVIMIENTO_ENTRADA = ME.ID_MOVIMIENTO_ENTRADA
                                      WHERE MEL.ID_UBICACION = $rowUbicacion->ID_UBICACION AND MEL.LINEA_ANULADA = 0 AND MEL.BAJA = 0 AND ME.ESTADO = 'En Proceso'";
            $resMovimientoEntrada = $bd->ExecSQL($sqlMovimientoEntrada);
            while ($rowMovimientoEntrada = $bd->SigReg($resMovimientoEntrada)):
                if (($rowMovimientoEntrada->NUM != NULL) && ($rowMovimientoEntrada->NUM != 0)):

                    //GUARDO LOS MOVIMIENTOS DE ENTRADA AFECTADOS
                    $movimientosEntrada = "";
                    $sqlMovimientoEntradaError = "SELECT DISTINCT MEL.ID_MOVIMIENTO_ENTRADA
                                                  FROM MOVIMIENTO_ENTRADA_LINEA MEL
                                                  JOIN MOVIMIENTO_ENTRADA ME ON MEL.ID_MOVIMIENTO_ENTRADA = ME.ID_MOVIMIENTO_ENTRADA
                                                  WHERE MEL.ID_UBICACION = $rowUbicacion->ID_UBICACION AND MEL.LINEA_ANULADA = 0 AND MEL.BAJA = 0 AND ME.ESTADO = 'En Proceso'";
                    $resMovimientoEntradaError = $bd->ExecSQL($sqlMovimientoEntradaError);
                    while ($rowMovimientoEntradaError = $bd->SigReg($resMovimientoEntradaError)):
                        $movimientosEntrada .=  $rowMovimientoEntradaError->ID_MOVIMIENTO_ENTRADA . ", ";
                    endwhile;
                    $movimientosEntrada = trim( (string)$movimientosEntrada, ", ");
                    $errorLinea = true;
                    $strKo      .= $auxiliar->traduce("La ubicacion que intenta dar de baja tiene una linea de un movimiento de entrada activa. Movimientos de entrada afectados:", $administrador->ID_IDIOMA) . " " . $movimientosEntrada . ". " . $mensajeError;

                endif;
            endwhile;
            //QUE NO ESTE INVOLUCRADA EN NINGUN CONTEO ACTIVO
            $sqlConteoUbicacion = "SELECT COUNT(*) AS NUM
                                     FROM INVENTARIO_ORDEN_CONTEO_LINEA IOCL
                                     INNER JOIN INVENTARIO_ORDEN_CONTEO IOC ON IOC.ID_INVENTARIO_ORDEN_CONTEO = IOCL.ID_INVENTARIO_ORDEN_CONTEO
                                     WHERE IOCL.ID_UBICACION = '" . $bd->escapeCondicional($idUbicacion) . "' AND IOCL.BAJA = 0 AND IOC.ESTADO <> 'Finalizado' AND IOC.BAJA";

            $resConteoUbicacion = $bd->ExecSQL($sqlConteoUbicacion);
            $rowConteoUbicacion = $bd->SigReg($resConteoUbicacion);
            if ($rowConteoUbicacion->NUM > 0):
                $errorLinea = true;
                $strKo      .= $auxiliar->traduce("La ubicación que intenta dar de baja esta asociada a una orden de conteo", $administrador->ID_IDIOMA) . ". " . $mensajeError;

            endif;
            //ACTUALIZO LA TABLA CONTENEDORES DE TIPO GAVETA
            if ($rowUbicacion->TIPO_UBICACION == 'Gaveta' && $errorLinea == false):
                //BUSCO EL CONTENEDOR
                $NotificaErrorPorEmail = "No";
                $rowContenedorGaveta   = $bd->VerRegRest("CONTENEDOR", "ID_UBICACION = $rowUbicacion->ID_UBICACION AND TIPO = 'Gaveta'", "No");
                unset($NotificaErrorPorEmail);

                //ELIMINO EL CONTENEDOR DE TIPO GAVETA
                echo "Elimino contenedor <br>";
                $sqlDelete = "DELETE FROM CONTENEDOR WHERE ID_UBICACION = $rowUbicacion->ID_UBICACION AND TIPO = 'Gaveta'";
                $bd->ExecSQL($sqlDelete);
            endif;


            //SI HAY ERROR LO QUITO DEL ARRAY PARA CONSULTAR
            if ($errorLinea == true):
                $filasKo++;
                unset($arrayUbicacion[$indice]);
                $arrDescartar[] = $row->ID_UBICACION;
            else:

                $filasOk++;

                $sqlUpdateUbicacion = "UPDATE UBICACION SET
                                        BAJA = 1
                                        WHERE ID_UBICACION = $rowUbicacion->ID_UBICACION ";
                $bd->ExecSQL($sqlUpdateUbicacion);
            endif;

        endif;

    endforeach;

endif;
?>

<? if ($accion == "bajaMasivaUbicaciones"): ?>
    <? if ($filasKo == 0): ?>
        <html>
        <head>
        </head>
        <body>
        <script type="text/javascript">
            window.parent.document.location.href = "index.php?recordar_busqueda=1&fraseAccionOk=Baja masiva de Ubicaciones realizada correctamente<?=($pantallaSolar == 1 ? "&pantallaSolar=1" : ($pantallaConstruccion == 1 ? "&pantallaConstruccion=1" : ""))?>";
        </script>
        </body>
        </html>
    <? endif; ?>
    <html>
    <head>
        <? require_once $pathClases . "lib/gral_js.php"; ?>

        <script language="javascript" type="text/javascript">


            function redirigir() {
                window.parent.document.FormSelect.action = "index.php?recordar_busqueda=1<?=($pantallaSolar == 1 ? "&pantallaSolar=1" : ($pantallaConstruccion == 1 ? "&pantallaConstruccion=1" : ""))?>";
                window.parent.document.FormSelect.submit();
                window.parent.jQuery.fancybox.close();
            }
        </script>
    </head>
    <body bgcolor="#FFFFFF" background="<? echo $pathRaiz ?>imagenes/fondo_pantalla.gif" leftmargin="0" topmargin="0"
          marginwidth="0" marginheight="0">

    <table width="100%" border="0" align="center" cellpadding="0" cellspacing="0" background="">

        <tr>
            <td align="center" valign="top">
                <? include $pathRaiz . "tabla_superior.php"; ?>
                <table width="100%" border="0" cellpadding="0" cellspacing="0">
                    <tr>
                        <td height="3" colspan="2" align="center" valign="top" bgcolor="#7A0A0A" class="linearriba"><img
                                    src="<? echo $pathRaiz ?>imagenes/transparente.gif" width="10" height="3"></td>
                    </tr>
                    <tr>
                        <? include $pathRaiz . "tabla_izqda.php"; ?>
                        <td align="left" valign="top" bgcolor="#FFFFFF"
                            background="<? echo $pathRaiz ?>imagenes/fondo_pantalla.gif">
                            <table width="100%" border="0" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td height="23">
                                        <table width="100%" height="23" border="0" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td width="25" class="linearriba"><img
                                                            src="<? echo $pathRaiz ?>imagenes/flechitas_01.gif"
                                                            width="35"
                                                            height="23"></td>
                                                <td width="469" align="left" valign="middle" bgcolor="#B3C7DA"
                                                    class="linearriba">
                                                    <table width="440" border="0" cellspacing="0" cellpadding="0">
                                                        <tr>
                                                            <td align="left" class="alertas"><? echo $tituloPag ?></td>
                                                        </tr>
                                                    </table>
                                                </td>
                                                <td width="25"><img src="<? echo $pathRaiz ?>imagenes/esquina.gif"
                                                                    width="25" height="24"></td>
                                                <td bgcolor="#7A0A0A">
                                                    <table width="235" height="23" border="0" cellpadding="0"
                                                           cellspacing="0">
                                                        <tr>
                                                            <td width="20">&nbsp;</td>
                                                            <td align="left" class="existalert">
                                                                <? include "$pathRaiz" . "control_alertas.php" ?>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td height="20" align="left" valign="top">
                                        <table width="100%" height="20" border="0" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td width="440" bgcolor="#D9E3EC">
                                                    <table width="440" border="0" cellspacing="0" cellpadding="0">
                                                        <tr>
                                                            <td width="35" bgcolor="#982a29" class="lineabajoarriba">
                                                                &nbsp;
                                                            </td>
                                                            <td width="224" bgcolor="#982a29" class="lineabajoarriba"
                                                                colspan="2">
                                                                <font class="tituloNav"><? echo $tituloNav ?>
                                                                </font></td>
                                                            <td valign=top width="20" bgcolor="#B3C7DA"
                                                                class="lineabajoarriba"><img
                                                                        src="<? echo $pathRaiz ?>imagenes/esquina_02.gif"
                                                                        width="20" height="20"></td>
                                                        </tr>
                                                    </table>
                                                </td>
                                                <td bgcolor="#B3C7DA" class="lineabajoarriba">&nbsp;</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="left" valign="top" bgcolor="#B3C7DA" class="lineabajo">
                                        <table width="100%" border="0" cellpadding="0" cellspacing="0"
                                               class="lineabajo">

                                            <tr class="lineabajo">
                                                <td colspan="2" align="center" bgcolor="#D9E3EC">

                                                    <? if ($filasKo > 0): ?>
                                                        <table width="98%" cellpadding="0" cellspacing="2">
                                                            <tr>
                                                                <td height="19" class="blanco" align="left">
                                                                    <div align="center"><span
                                                                                class="textorojo resaltado"><?= $auxiliar->traduce("LAS SIGUIENTES LINEAS NO HAN PODIDO SER PROCESADAS", $administrador->ID_IDIOMA) ?></span>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td height="19" class="blanco" align="left">
                                                                    <div align="center"><span
                                                                                class="textorojo resaltado"><?= $auxiliar->traduce("NUMERO DE ERRORES", $administrador->ID_IDIOMA) ?>
                                                                            : <?= $filasKo . "/" . ($filasOk + $filasKo) ?></span>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                        </table>
                                                        <table width="98%" cellpadding="0" cellspacing="2"
                                                               class="linealrededor">
                                                            <tr>
                                                                <td height="19" bgcolor="#2E8AF0" class="blanco"
                                                                    align="center">
                                                                    <?= $auxiliar->traduce("Errores", $administrador->ID_IDIOMA) ?>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td height="19" bgcolor="#FFF" align="center">
                                                                    <table cellpadding="0" cellspacing="0" width="100%">
                                                                        <tr>
                                                                            <td>
                                                                                <?
                                                                                $numeroFilas = 20;
                                                                                if ($filasKo < 20):
                                                                                    $numeroFilas = $filasKo + 1;
                                                                                endif;
                                                                                ?>
                                                                                <textarea name="txLineasError"
                                                                                          class="copyright"
                                                                                          style="resize:none; width:100%;"
                                                                                          rows="<? echo $numeroFilas + 1 ?>"
                                                                                          readonly="readonly"><? echo $strKo ?></textarea>
                                                                            </td>
                                                                        </tr>
                                                                    </table>
                                                                </td>
                                                            </tr>
                                                        </table>

                                                    <? endif; ?>

                                                    <? if ($filasOk > 0): ?>

                                                        <br/>
                                                        <table width="98%" cellpadding="0" cellspacing="2">
                                                            <tr>
                                                                <td height="19" class="blanco" align="left">
                                                                    <div align="center"><span
                                                                                class="textoazul resaltado"><?= $auxiliar->traduce("NUMERO DE REGISTROS PROCESADOS CORRECTAMENTE", $administrador->ID_IDIOMA) ?>
                                                                            : <?= $filasOk . "/" . ($filasOk + $indiceKo) ?></span>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td height="19" class="blanco" align="left">
                                                                    <div align="center"><span
                                                                                class="textoazul resaltado"><?= $mensajeExito; ?></span>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                        </table>

                                                    <? elseif ($filasOk == 0): ?>

                                                        <br/>
                                                        <table width="98%" cellpadding="0" cellspacing="2">
                                                            <tr>
                                                                <td height="19" class="blanco" align="left">
                                                                    <div align="center"><span
                                                                                class="textorojo resaltado"><?= $auxiliar->traduce("NO SE HA PROCESADO NINGUNA BAJA DE UBICACION", $administrador->ID_IDIOMA) ?></span>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                        </table>

                                                    <? endif; ?>

                                                    <br/>

                                                    <table width="100%" cellpadding="0" cellspacing="0">
                                                        <tr height="20px;">
                                                            <td>
                                                                <div align="center">
                                                                    <a id="continuar"
                                                                       href="index.php?recordar_busqueda=1<?= ($pantallaSolar == 1 ? "&pantallaSolar=1" : ($pantallaConstruccion == 1 ? "&pantallaConstruccion=1" : "")) ?>"
                                                                       class="senaladoazul"
                                                                       onclick="parent.jQuery.fancybox.close();">
                                                                        &nbsp;&nbsp;&nbsp;&nbsp;<?= $auxiliar->traduce("Continuar", $administrador->ID_IDIOMA) ?>
                                                                        &nbsp;&nbsp;&nbsp;&nbsp;</a>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    </table>

                                                    <br/>

                                                </td>
                                            </tr>

                                        </table>
                                        <br>
                                        <br>
                                    </td>
                                </tr>
                                <tr>
                                    <? include $pathRaiz . "copyright.php"; ?>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    </body>
    </html>
<? else: ?>
    <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
    <html>
    <head>
        <? require_once $pathClases . "lib/gral_js.php"; ?>
        <script language="javascript" type="text/javascript">
            $(document).ready(function () {
                $('#botonContinuar').focus();
            })
        </script>
    </head>

    <body bgcolor="#FFFFFF" background="<? echo $pathRaiz ?>imagenes/fondo_pantalla.gif" leftmargin="0" topmargin="0"
          marginwidth="0" marginheight="0">
    <FORM NAME="Form" METHOD="POST">
        <table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
            <tr>
                <td height="10" align="center" valign="top">
                    <? include $pathRaiz . "tabla_superior.php"; ?>

                </td>
            </tr>
            <tr>
                <td align="center" valign="top">
                    <table width="100%" border="0" cellpadding="0" cellspacing="0">
                        <tr>
                            <td height="3" colspan="2" align="center" valign="top" bgcolor="#7A0A0A" class="linearriba">
                                <img
                                        src="<? echo $pathRaiz ?>imagenes/transparente.gif" width="10" height="3"></td>
                        </tr>
                        <tr>
                            <? include $pathRaiz . "tabla_izqda.php"; ?>
                            <td align="left" valign="top" bgcolor="#FFFFFF"
                                background="<? echo $pathRaiz ?>imagenes/fondo_pantalla.gif">
                                <table width="100%" border="0" cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td height="23">
                                            <table width="100%" height="23" border="0" cellpadding="0" cellspacing="0">
                                                <tr>
                                                    <td width="25" class="linearriba"><img
                                                                src="<? echo $pathRaiz ?>imagenes/flechitas_01.gif"
                                                                width="35"
                                                                height="23"></td>
                                                    <td width="469" align="left" valign="middle" bgcolor="#B3C7DA"
                                                        class="linearriba">
                                                        <table width="440" border="0" cellspacing="0" cellpadding="0">
                                                            <tr>
                                                                <td align="left"
                                                                    class="alertas"><? echo $tituloPag ?></td>
                                                            </tr>
                                                        </table>
                                                    </td>
                                                    <td width="25"><img src="<? echo $pathRaiz ?>imagenes/esquina.gif"
                                                                        width="25" height="24"></td>
                                                    <td bgcolor="#7A0A0A">
                                                        <table width="235" height="23" border="0" cellpadding="0"
                                                               cellspacing="0">
                                                            <tr>
                                                                <td width="20">&nbsp;</td>
                                                                <td align="left" class="existalert">
                                                                </td>
                                                                <td width="60"></td>
                                                            </tr>
                                                        </table>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td height="20" align="left" valign="top">
                                            <table width="100%" height="20" border="0" cellpadding="0" cellspacing="0">
                                                <tr>
                                                    <td width="440" bgcolor="#D9E3EC">
                                                        <table width="440" border="0" cellspacing="0" cellpadding="0">

                                                            <tr>
                                                                <td width="35" bgcolor="#982a29"
                                                                    class="lineabajoarriba">
                                                                    &nbsp;
                                                                </td>
                                                                <td width="220" bgcolor="#982a29"
                                                                    class="lineabajoarriba"
                                                                    colspan=2><font
                                                                            class="tituloNav"><? echo $tituloNav ?></font>
                                                                </td>
                                                                <td width="20" valign=top bgcolor="#B3C7DA"
                                                                    class="lineabajoarriba"><img
                                                                            src="<? echo $pathRaiz ?>imagenes/esquina_02.gif"
                                                                            width="20" height="20"></td>
                                                            </tr>
                                                        </table>
                                                    </td>
                                                    <td bgcolor="#B3C7DA" class="lineabajoarriba">&nbsp;</td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                    <tr bgcolor="#D9E3EC">
                                        <td height="280" align="left" valign="top" bgcolor="#D9E3EC" class="lineabajo">
                                            <table width="100%" height="280" border="0" cellpadding="0" cellspacing="0">
                                                <tr>
                                                    <td align="center" valign="bottom">
                                                        <table width="100%" height="220" border="0" cellpadding="0"
                                                               cellspacing="0">
                                                            <tr align="center" valign="middle">
                                                                <td height="20">
                                                                    <table width="130" height="20" border="0"
                                                                           cellpadding="0" cellspacing="0">
                                                                        <tr>
                                                                            <td align="center" valign="middle"
                                                                                bgcolor="#B3C7DA"
                                                                                class="alertas2"><?= strtr( (string)strtoupper( (string)$auxiliar->traduce("Información", $administrador->ID_IDIOMA)), "àèìòùáéíóúçñäëïöü", "ÀÈÌÒÙÁÉÍÓÚÇÑÄËÏÖÜ") ?></td>
                                                                        </tr>
                                                                    </table>
                                                                </td>
                                                            </tr>
                                                            <tr align="center" valign="middle">
                                                                <td bgcolor="#B3C7DA" class="textoazul"><strong>
                                                                        <?
                                                                        if ($accion == "Modificar"):
                                                                            echo $auxiliar->traduce("Los datos de la Ubicación han sido modificados correctamente", $administrador->ID_IDIOMA);
                                                                        elseif ($accion == "Insertar"):
                                                                            echo $auxiliar->traduce("La Ubicación ha sido creada correctamente", $administrador->ID_IDIOMA);
                                                                        elseif ($accion == "Borrar"):
                                                                            echo $auxiliar->traduce("La Ubicación ha sido borrada correctamente", $administrador->ID_IDIOMA);
                                                                        endif;
                                                                        ?>
                                                                    </strong></td>
                                                            </tr>
                                                            <tr>
                                                                <td height="124" align="center" valign="middle"
                                                                    bgcolor="#B3C7DA">
                                                                    <table width="100%" height="124" border="0"
                                                                           cellpadding="0" cellspacing="0">
                                                                        <tr>
                                                                            <td align="right" valign="middle">
                                                                                <table width="100%" height="124"
                                                                                       border="0"
                                                                                       cellpadding="0" cellspacing="0">
                                                                                    <tr>
                                                                                        <td height="9"><img
                                                                                                    src="<? echo $pathRaiz ?>imagenes/transparente.gif"
                                                                                                    width="10"
                                                                                                    height="9">
                                                                                        </td>
                                                                                    </tr>
                                                                                    <tr>
                                                                                        <td height="115"
                                                                                            bgcolor="#90BC45">
                                                                                            &nbsp;
                                                                                        </td>
                                                                                    </tr>
                                                                                </table>
                                                                            </td>
                                                                            <td width="212" align="center"
                                                                                valign="middle"
                                                                                bgcolor="#90BC45">
                                                                                <table width="212" height="124"
                                                                                       border="0"
                                                                                       cellpadding="0" cellspacing="0"
                                                                                       background="<? echo $pathRaiz ?>imagenes/fondo_ok2.gif">
                                                                                    <tr>
                                                                                        <td>&nbsp;</td>
                                                                                    </tr>
                                                                                    <tr>
                                                                                        <td align="center"
                                                                                            valign="middle">
                                                                                            <a id="botonContinuar"
                                                                                               href="index.php?recordar_busqueda=1<?= ($pantallaSolar == 1 ? "&pantallaSolar=1" : ($pantallaConstruccion == 1 ? "&pantallaConstruccion=1" : "")) ?>"
                                                                                               class="senaladoazul">
                                                                                                &nbsp;&nbsp;&nbsp;&nbsp;<?= $auxiliar->traduce("Continuar", $administrador->ID_IDIOMA) ?>
                                                                                                &nbsp;&nbsp;&nbsp;&nbsp;</a>
                                                                                        </td>
                                                                                    </tr>
                                                                                </table>
                                                                            </td>
                                                                            <td align="left" valign="middle">
                                                                                <table width="100%" height="124"
                                                                                       border="0"
                                                                                       cellpadding="0" cellspacing="0">
                                                                                    <tr>
                                                                                        <td height="37"><img
                                                                                                    src="<? echo $pathRaiz ?>imagenes/transparente.gif"
                                                                                                    width="10"
                                                                                                    height="37">
                                                                                        </td>
                                                                                    </tr>
                                                                                    <tr>
                                                                                        <td height="87"
                                                                                            bgcolor="#90BC45">
                                                                                            &nbsp;
                                                                                        </td>
                                                                                    </tr>
                                                                                </table>
                                                                            </td>
                                                                        </tr>
                                                                    </table>
                                                                </td>
                                                            </tr>
                                                        </table>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td height="40" align="left" valign="top" bgcolor="#B3C7DA" class="lineabajo">
                                            &nbsp;
                                        </td>
                                    </tr>
                                    <tr>
                                        <? include $pathRaiz . "copyright.php"; ?>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
        <input type="submit" style="position:absolute; top:-999999px"/>
    </FORM>
    </body>
    </html>
<? endif; ?>