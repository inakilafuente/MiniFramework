<? //print_r($_FILES);
// PATHS DE LA WEB
$pathRaiz   = "../../";
$pathClases = "../../../";

// INCLUDES DE LIBRERIAS PROPIAS
require_once $pathClases . "lib/basedatos.php";
require_once $pathClases . "lib/administrador.php";
require_once $pathClases . "lib/html.php";
require_once $pathClases . "lib/navegar.php";
require_once $pathClases . "lib/comprobar.php";
require_once $pathClases . "lib/auxiliar.php";
require_once $pathClases . "lib/pedido.php";
require_once $pathClases . "lib/material.php";

//IMPORTAMOS LA CLASE PARA LEER EXCEL
require_once $pathClases . "lib/importar_excel/reader.php";

session_start();
include $pathRaiz . "seguridad_admin.php";

$tituloPag         = $auxiliar->traduce("Ubicaciones", $administrador->ID_IDIOMA);
$tituloNav         = $auxiliar->traduce("Maestros", $administrador->ID_IDIOMA) . " >> " . $auxiliar->traduce("Ubicaciones", $administrador->ID_IDIOMA);
$ZonaTablaPadre    = "Maestros";
$ZonaSubTablaPadre = "MaestrosSubmenuAlmacen";
$ZonaTabla         = "MaestrosUbicaciones";

// COMPRUEBA SI TIENE PERMISOS
if ($administrador->Hayar_Permiso_Perfil('ADM_MAESTROS_UBICACIONES') < 2):
    $html->PagError("SinPermisos");
endif;

//COMPROBACIONES
$arr_tx              = array();
$i                   = 0;
$arr_tx[$i]["err"]   = $auxiliar->traduce("Archivo a Importar", $administrador->ID_IDIOMA);
$arr_tx[$i]["valor"] = $_FILES['adjunto_archivo_importacion']['name'];
$comp->ComprobarTexto($arr_tx, "CampoSinRellenar");
//FIN COMPROBACIONES

//ME CREO UNA VARIABLE CLAVE PARA QUE NO HAYA DOS ARCHIVOS CON LA MISMA CLAVE
$claveTiempo = date("YmdHis");

//******************************************************* CARGAMOS EL ARCHIVO *******************************************************//
$nombreFichero = $_FILES['adjunto_archivo_importacion']['name'];

//VARIABLE PARA SABER EL TIPO DE ARCHIVO Y NUMERO DE CONTENEDOR/ALBARAN
$arrArchivoXLS = explode(".", (string)$nombreFichero);
$tipoArchivo   = $arrArchivoXLS[count( (array)$arrArchivoXLS) - 1];
$html->PagErrorCondicionado($tipoArchivo, "!=", "xls", "TipoArchivoNoXLS");
$nombreArchivoXLS = $arrArchivoXLS[0];

//GRABO EL ARCHIVO A IMPORTAR
$RutayFichArchivo = $path_raiz . "documentos/maquinas/" . "TEMP_" . $claveTiempo . "_ARCHIVO_IMPORTADO_" . $nombreFichero;
$resultFoto       = $html->CopiarAdjunto($adjunto_archivo_importacion, $RutayFichArchivo);
$html->PagErrorCondicionado($resultFoto, "==", "Error", "ErrorCopiarFichero");

// 1. COMPROBAMOS QUE EL FICHERO EXCEL EXISTE
$nombreFichero = $path_raiz . "documentos/maquinas/TEMP_" . $claveTiempo . "_ARCHIVO_IMPORTADO_" . $nombreFichero;

if (!(file_exists($nombreFichero))):
    echo "El archivo $nombreFichero no existe.";
else:
    //INICIAMOS EL OBJETO DE LA CLASE
    $xl_reader = new Spreadsheet_Excel_Reader();

    //DEFINIMOS EL ARCHIVO A LEER
    $xl_reader->read($nombreFichero);

    $rows = $xl_reader->sheets[0]['numRows'];
    $rows = (int)$rows;

    //BUSCO LAS CELDAS DE LA HOJA CERO
    $cells = $xl_reader->sheets[0]['cells'];

    //VARIABLES PARA CONTROLAR LO MOSTRADO
    $strOk   = "";
    $filasOk = 0;
    $strKo   = "";
    $filasKo = 0;

    //VARIABLE PARA GUARDAR LOS VALORES A GRABAR
    $arrLineasValidas = array();

    //EXISTEN UBICACIONES DE TIPO SECTOR
    $existenUbicacionesTiposector = false;

    //VARIABLE PARA CONTROLAR LOS INDICES DE LAS LINEAS
    $indice = 1;

    //INICIALIZO $i a 2 porque la primera fila son las cabeceras y se empieza en 1 y no en 0 como el resto de lenguajes
    for ($i = 2; $i < $rows + 1; $i++):

        //VARIABLE PARA SABER SI HAY ERROR EN LINEA
        $errorLinea = false;

        //OBTENGO LOS DATOS DE LA FILA
        $refUbicacion  = trim( (string)$cells[$i][1]);
        $refCentro     = trim( (string)$cells[$i][2]);
        $refAlmacen    = trim( (string)$cells[$i][3]);
        $categoria     = trim( (string)$cells[$i][4]);
        $descripcion   = trim( (string)$cells[$i][5]);
        $precioFijo    = strtoupper( (string)trim( (string)$cells[$i][6]));
        $autostore     = strtoupper( (string)trim( (string)$cells[$i][7]));
        $baja          = strtoupper( (string)trim( (string)$cells[$i][8]));
        $esTipoSector  = strtoupper( (string)trim( (string)$cells[$i][9]));
        $refTipoSector = trim( (string)$cells[$i][9]);
//        $cantidadPaneles = trim($cells[$i][10]);

        //PREPARACION DE ALGUNOS DATOS
        if ($precioFijo == ""): $precioFijo = "NO"; endif;
        if ($autostore == ""): $autostore = "NO"; endif;
        if ($baja == ""): $baja = "NO"; endif;
        if ($esTipoSector == ""): $esTipoSector = "NO"; endif;

        //COMPROBACIONES DE DATOS RELLENADOS
        //ref ubicacion
        if ($refUbicacion == ""):
            $strKo .= $auxiliar->traduce("Línea", $administrador->ID_IDIOMA) . " $indice. ";
            $strKo .= $auxiliar->traduce("La referencia de ubicación es vacía", $administrador->ID_IDIOMA) . ".\n";
            $filasKo++;
            $errorLinea = true;
        endif;

        //ref centro
        if ($refCentro == ""):
            $strKo .= $auxiliar->traduce("Línea", $administrador->ID_IDIOMA) . " $indice. ";
            $strKo .= $auxiliar->traduce("La referencia de centro es vacía", $administrador->ID_IDIOMA) . ".\n";
            $filasKo++;
            $errorLinea = true;
        endif;

        //ref albacen
        if ($refAlmacen == ""):
            $strKo .= $auxiliar->traduce("Línea", $administrador->ID_IDIOMA) . " $indice. ";
            $strKo .= $auxiliar->traduce("La referencia de almacén es vacía", $administrador->ID_IDIOMA) . ".\n";
            $filasKo++;
            $errorLinea = true;
        endif;

        $filas    = 0;
        $columnas = 0;
        //SI ES_TIPO_SECTOR == "SI", EL CAMPO ID_TIPO_SECTOR DEBE ESTAR RELLENADO
        if ($esTipoSector == "SI"):
            if ($refTipoSector == ""):
                $strKo .= $auxiliar->traduce("Línea", $administrador->ID_IDIOMA) . " $indice. ";
                $strKo .= $auxiliar->traduce("La Referencia Tipo Sector no puede ser vacío", $administrador->ID_IDIOMA) . ".\n";
                $filasKo++;
                $errorLinea = true;
            else:
                //SE COMPRUEBA SI EL ID_TIPO_SECTOR PERTENECE A UN SECTOR
                $NotificaErrorPorEmail = "No";
                $rowTipoSector         = $bd->VerReg("TIPO_SECTOR", "ID_TIPO_SECTOR", $refTipoSector, "No");
                unset($NotificaErrorPorEmail);
                if ($rowTipoSector == false):
                    $strKo .= $auxiliar->traduce("Línea", $administrador->ID_IDIOMA) . " $indice. ";
                    $strKo .= $auxiliar->traduce("El campo Referencia Tipo Sector es incorrecto", $administrador->ID_IDIOMA) . ".\n";
                    $filasKo++;
                    $errorLinea = true;
                else:
                    $filas    = $rowTipoSector->FILAS;
                    $columnas = $rowTipoSector->COLUMNAS;
                endif;
            endif;
        endif;

        //FIN COMPROBACIONES DE DATOS RELLENADOS

        //OBTENGO DATOS NECESARIOS
        //centro
        $rowCentro = false;
        if ($refCentro != ""):
            $NotificaErrorPorEmail = "No";
            $rowCentro             = $bd->VerReg("CENTRO", "REFERENCIA", $refCentro, "No");
            unset($NotificaErrorPorEmail);
            if (!$rowCentro):
                $strKo .= $auxiliar->traduce("Línea", $administrador->ID_IDIOMA) . " $indice. ";
                $strKo .= $auxiliar->traduce("Centro no encontrado para esa referencia", $administrador->ID_IDIOMA) . ': ' . $refCentro . ".\n";
                $filasKo++;
                $errorLinea = true;
            endif;
        endif;

        //almacen
        $rowAlmacen = false;
        if ($refAlmacen != "" && $rowCentro):
            $NotificaErrorPorEmail = "No";
            $rowAlmacen            = $bd->VerRegRest("ALMACEN", "REFERENCIA = '" . $refAlmacen . "' AND ID_CENTRO = '" . $rowCentro->ID_CENTRO . "' AND TIPO_ALMACEN='acciona' ", "No");
            unset($NotificaErrorPorEmail);
            if (!$rowAlmacen):
                $strKo .= $auxiliar->traduce("Línea", $administrador->ID_IDIOMA) . " $indice. ";
                $strKo .= $auxiliar->traduce("Almacen de tipo 'acciona' no encontrado para ese centro", $administrador->ID_IDIOMA) . ".\n";
                $filasKo++;
                $errorLinea = true;
            endif;
        endif;

        //ubicacion
        $rowUbicacion = false;
        if ($refUbicacion != "" && $rowAlmacen):
            $NotificaErrorPorEmail = "No";
            $rowUbicacion          = $bd->VerRegRest("UBICACION", "UBICACION = '" . $refUbicacion . "' AND ID_ALMACEN = '" . $rowAlmacen->ID_ALMACEN . "'", "No");
            unset($NotificaErrorPorEmail);
        endif;

        //categoria
        $rowCategoria = false;
        if ($categoria != ""):
            $NotificaErrorPorEmail = "No";
            $rowCategoria          = $bd->VerReg("UBICACION_CATEGORIA", "ID_UBICACION_CATEGORIA", (int)$categoria, "No");
            unset($NotificaErrorPorEmail);
            if (!$rowCategoria):
                $strKo .= $auxiliar->traduce("Línea", $administrador->ID_IDIOMA) . " $indice. ";
                $strKo .= $auxiliar->traduce("Categoría no encontrada para esa referencia", $administrador->ID_IDIOMA) . ': ' . $categoria . ".\n";
                $filasKo++;
                $errorLinea = true;
            endif;
        endif;
        //FIN OBTENGO DATOS NECESARIOS

        //COMPROBACION DE DATOS VALIDOS

        //ubicacion
        //si existe la ubicacion, el tipo ha de ser NULL o de tipo Sector
        //si se quiere dar de baja, ha de estar vacía
        if ($rowUbicacion):
            //tipo
            if (($rowUbicacion->TIPO_UBICACION != NULL) && ($tipoSector == 'NO')):
                $strKo .= $auxiliar->traduce("Línea", $administrador->ID_IDIOMA) . " $indice. ";
                $strKo .= $auxiliar->traduce("No se puede modificar la ubicación porque no es de tipo 'Estandar'", $administrador->ID_IDIOMA) . ".\n";
                $filasKo++;
                $errorLinea = true;
            endif;
            if (($rowUbicacion->TIPO_UBICACION != 'Sector') && ($tipoSector == 'SI')):
                $strKo .= $auxiliar->traduce("Línea", $administrador->ID_IDIOMA) . " $indice. ";
                $strKo .= $auxiliar->traduce("No se puede modificar la ubicación porque no es de tipo 'Sector'", $administrador->ID_IDIOMA) . ".\n";
                $filasKo++;
                $errorLinea = true;
            endif;
            //stock
            if ($baja == "SI"):
                $NotificaErrorPorEmail = "No";
                $rowStock              = $bd->VerRegRest("MATERIAL_UBICACION", "ID_UBICACION = '" . $rowUbicacion->ID_UBICACION . "' AND STOCK_TOTAL >= 0", "No");
                unset($NotificaErrorPorEmail);
                if ($rowStock):
                    $strKo .= $auxiliar->traduce("Línea", $administrador->ID_IDIOMA) . " $indice. ";
                    $strKo .= $auxiliar->traduce("No se puede dar de baja la ubicación porque contiene stock", $administrador->ID_IDIOMA) . ".\n";
                    $filasKo++;
                    $errorLinea = true;
                endif;
            endif;
        endif;

        //almacen
        //debe tener permisos sobre el almacen
        if ($rowAlmacen && !$administrador->comprobarAlmacenPermiso($rowAlmacen->ID_ALMACEN, 'Escritura')):
            $strKo .= $auxiliar->traduce("Línea", $administrador->ID_IDIOMA) . " $indice. ";
            $strKo .= $auxiliar->traduce("No tiene permisos para realizar esta operación en esta subzona", $administrador->ID_IDIOMA) . ".\n";
            $filasKo++;
            $errorLinea = true;
        endif;

        //precio fijo
        if ($precioFijo != "SI" && $precioFijo != "NO"):
            $strKo .= $auxiliar->traduce("Línea", $administrador->ID_IDIOMA) . " $indice. ";
            $strKo .= $auxiliar->traduce("Valor no permitido para la columna 'Precio Fijo'", $administrador->ID_IDIOMA) . ".\n";
            $filasKo++;
            $errorLinea = true;
        endif;

        //autostore
        if ($autostore != "SI" && $autostore != "NO"):
            $strKo .= $auxiliar->traduce("Línea", $administrador->ID_IDIOMA) . " $indice. ";
            $strKo .= $auxiliar->traduce("Valor no permitido para la columna 'Autostore'", $administrador->ID_IDIOMA) . ".\n";
            $filasKo++;
            $errorLinea = true;
        endif;

        //baja
        if ($baja != "SI" && $baja != "NO"):
            $strKo .= $auxiliar->traduce("Línea", $administrador->ID_IDIOMA) . " $indice. ";
            $strKo .= $auxiliar->traduce("Valor no permitido para la columna 'Baja'", $administrador->ID_IDIOMA) . ".\n";
            $filasKo++;
            $errorLinea = true;
        endif;

        //tipo Sector
        if ($esTipoSector != "SI" && $esTipoSector != "NO"):
            $strKo .= $auxiliar->traduce("Línea", $administrador->ID_IDIOMA) . " $indice. ";
            $strKo .= $auxiliar->traduce("Valor no permitido para la columna 'Tipo Sector'", $administrador->ID_IDIOMA) . ".\n";
            $filasKo++;
            $errorLinea = true;
        endif;

        //cantidad paneles
        if ($esTipoSector == "SI"):
            //ACTUALIZO LA VARIABLE EXISTEN UBICACIONES DE TIPO SECTOR
            $existenUbicacionesTiposector = true;
            //SE CALCULA LA CANTIDAD DE PANELES EN BASE AL TIPO DE SECTOR
            $cantidadPaneles = $filas * $columnas;
//            $esNumerico = is_numeric($cantidadPaneles)?intval(0 + $cantidadPaneles) == $cantidadPaneles:false;
//            if ($esNumerico == false):
//                $strKo .= $auxiliar->traduce("Línea", $administrador->ID_IDIOMA) . " $indice. ";
//                $strKo .= $auxiliar->traduce("Valor no permitido para la columna 'Cantidad Paneles'", $administrador->ID_IDIOMA) . ".\n";
//                $filasKo++;
//                $errorLinea = true;
//            endif;
        endif;
        //FIN COMPROBACION DE DATOS VALIDOS

        //DATOS CORRECTOS
        if ($errorLinea == false):
            $filasOk++;

            $arrLineasValidas[$indice]['REFERENCIA_UBICACION'] = $refUbicacion;
            $arrLineasValidas[$indice]['REFERENCIA_CENTRO']    = $refCentro;
            $arrLineasValidas[$indice]['REFERENCIA_ALMACEN']   = $refAlmacen;
            $arrLineasValidas[$indice]['CATEGORIA']            = $categoria;
            $arrLineasValidas[$indice]['DESCRIPCION']          = $descripcion;
            $arrLineasValidas[$indice]['PRECIO_FIJO']          = $precioFijo;
            $arrLineasValidas[$indice]['AUTOSTORE']            = $autostore;
            $arrLineasValidas[$indice]['BAJA']                 = $baja;
            $arrLineasValidas[$indice]['ES_TIPO_SECTOR']       = $esTipoSector;
            $arrLineasValidas[$indice]['REF_TIPO_SECTOR']      = $refTipoSector;
            $arrLineasValidas[$indice]['CANTIDAD_PANELES']     = $cantidadPaneles;

        endif;

        //INCREMENTO LA LINEA
        $indice = $indice + 1;

    endfor;

endif;

//***************************************************** FIN CARGAMOS EL ARCHIVO *****************************************************//

if (($strKo == "") && ($strOk == "")):
    $strKo = $auxiliar->traduce("Debe indicar al menos una referencia", $administrador->ID_IDIOMA) . ".";
endif;

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <? require_once $pathClases . "lib/gral_js.php"; ?>
    <!-- BUSQUEDA AJAX -->
    <script src="<?= $pathClases; ?>lib/ajax_script/lib/prototype.js" type="text/javascript"></script>
    <script src="<?= $pathClases; ?>lib/ajax_script/src/scriptaculous.js" type="text/javascript"></script>
    <link rel="stylesheet" href="<?= $pathClases; ?>lib/ajax_script/style_ajax.css" type="text/css"/>
    <!-- FIN BUSQUEDA AJAX -->
    <script language="JavaScript" type="text/JavaScript">

        var bandera = true;

        jQuery(document).ready(function () {
            <? if ($filasOk > 0): ?>

            jQuery('#btnProcesar').attr('onclick', 'continuar();return false');
            jQuery('#btnProcesar').css('color', '');
            jQuery('#btnProcesar2').attr('onclick', 'continuar();return false');
            jQuery('#btnProcesar2').css('color', '');

            <? endif; ?>
        });

        function continuar() {
            if (bandera == true) {
                bandera = false;

                document.FormSelect.submit();

                return false;
            }
        }

        function seleccionarTodas(chSel) {
            for (i = 0; i < document.FormSelect.elements.length; i++) {
                if ((document.FormSelect.elements[i].type == "checkbox") && (document.FormSelect.elements[i].name.substr(0, 8) == "chLinea_")) {
                    if (chSel.checked == 1) {
                        document.FormSelect.elements[i].checked = 1;
                    }
                    else {
                        document.FormSelect.elements[i].checked = 0;
                    }
                }
            }
        }

    </script>
</head>
<body class="fancy" bgcolor="#FFFFFF" background="<?= $pathRaiz ?>imagenes/fondo_pantalla.gif" leftmargin="0"
      topmargin="0" marginwidth="0" marginheight="0">
<FORM NAME="FormSelect" ACTION="ficha_importacion_accion.php" METHOD="POST" style="margin-bottom:0;"
      enctype="multipart/form-data">
    <input type="hidden" name="claveTiempo" value="<?= $claveTiempo; ?>">

    <?
    $ArchivoImportado = $RutayFichArchivo;
    if (file_exists($ArchivoImportado) == 1): // HAY ARCHIVO IMPORTADO
        ?>
        <input type="hidden" name="adjunto_archivo_importacion_material_almacen" value="<?= $nombreFichero; ?>">
        <?
    endif;
    ?>

    <? $navegar->GenerarCamposOcultosForm(); ?>
    <table width="100%" border="0" align="center" cellpadding="0" cellspacing="0" background="">
        <tr>
            <td valign=top align=middle height=10>
                <? include $pathRaiz . "tabla_superior.php"; ?>
            </td>
        </tr>
        <tr>
            <td align="center" valign="top">
                <table width="100%" border="0" cellpadding="0" cellspacing="0">
                    <tr>
                        <td height="3" colspan="2" align="center" valign="top" bgcolor="#7A0A0A" class="linearriba">
                            <img src="<?= $pathRaiz ?>imagenes/transparente.gif" width="10" height="3">
                        </td>
                    </tr>
                    <tr>
                        <? include $pathRaiz . "tabla_izqda.php"; ?>
                        <td align="left" valign="top" bgcolor="#FFFFFF"
                            background="<?= $pathRaiz ?>imagenes/fondo_pantalla.gif">
                            <table width="100%" border="0" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td height="23">
                                        <table width="100%" height="23" border="0" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td width="25" class="linearriba">
                                                    <img src="<?= $pathRaiz ?>imagenes/flechitas_01.gif" width="35"
                                                         height="23">
                                                </td>
                                                <td width="469" align="left" valign="middle" bgcolor="#B3C7DA"
                                                    class="linearriba">
                                                    <table width="440" border="0" cellspacing="0" cellpadding="0">
                                                        <tr>
                                                            <td align="left" class="alertas">
                                                                <?= $tituloPag ?>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                                <td width="25">
                                                    <img src="<?= $pathRaiz ?>imagenes/esquina.gif" width="25"
                                                         height="24">
                                                </td>
                                                <td bgcolor="#7A0A0A">
                                                    <table width="235" height="23" border="0" cellpadding="0"
                                                           cellspacing="0">
                                                        <tr>
                                                            <td width="20">&nbsp;
                                                            </td>
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
                                                                <font class="tituloNav" style="white-space:nowrap;">
                                                                    <?= $tituloNav ?>
                                                                </font>
                                                            </td>
                                                            <td valign=top width="20" bgcolor="#B3C7DA"
                                                                class="lineabajoarriba">
                                                                <img src="<?= $pathRaiz ?>imagenes/esquina_02.gif"
                                                                     width="20" height="20">
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                                <td bgcolor="#B3C7DA" class="lineabajoarriba">&nbsp;
                                                </td>
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

                                                    <table width="100%" cellpadding="0" cellspacing="0">
                                                        <tr height="20px;">
                                                            <td>
                                                                <div align="left">
                                                                    <span class="textoazul">
                                                                        &nbsp;&nbsp;<a
                                                                            onclick="history.back();return false;"
                                                                            class="senaladoazul" href="#">&nbsp;&nbsp;&nbsp;&nbsp;<?= $auxiliar->traduce("Volver", $administrador->ID_IDIOMA) ?>
                                                                            &nbsp;&nbsp;&nbsp;&nbsp;</a>&nbsp;&nbsp;
                                                                    </span>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div align="right">
                                                                    <span class="textoazul">
                                                                        <a href="#" style='color:#CCCCCC'
                                                                           onClick='return false;' id='btnProcesar'
                                                                           class="senaladoverde">&nbsp;&nbsp;&nbsp;&nbsp;<?= $auxiliar->traduce("Procesar", $administrador->ID_IDIOMA) ?>
                                                                            &nbsp;&nbsp;&nbsp;&nbsp;</a>&nbsp;&nbsp;
                                                                    </span>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    </table>

                                                    <br/>

                                                    <? if ($filasKo > 0): ?>
                                                        <table width="98%" cellpadding="0" cellspacing="2">
                                                            <tr>
                                                                <td height="19" class="blanco" align="left">
                                                                    <div align="left"><span
                                                                            class="textorojo resaltado"><?= $auxiliar->traduce("LAS SIGUIENTES LINEAS NO SE CARGARAN POR CONTENER ERRORES", $administrador->ID_IDIOMA) . ":"; ?>
                                                                            </span></div>
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
                                                                            <td style="padding-right:4px;">
                                                                                <?
                                                                                $numeroFilas = 20;
                                                                                if ($filasKo < 20):
                                                                                    $numeroFilas = $filasKo + 1;
                                                                                endif;
                                                                                ?>
                                                                                <textarea name="txLineasError"
                                                                                          class="copyright"
                                                                                          style="resize:none; width:100%;"
                                                                                          rows="<?= $numeroFilas ?>"
                                                                                          readonly="readonly"><?= $strKo ?></textarea>
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
                                                                    <div align="left"><span
                                                                            class="textoazul resaltado"><?= $auxiliar->traduce("LAS LINEAS SELECCIONADAS SERAN IMPORTADAS", $administrador->ID_IDIOMA) . ":"; ?>
                                                                            </span></div>
                                                                </td>
                                                            </tr>
                                                        </table>
                                                        <table width="98%" cellpadding="0" cellspacing="2"
                                                               class="linealrededor">
                                                            <tr>
                                                                <td height="19" bgcolor="#2E8AF0" class="blanco"
                                                                    align="center">
                                                                    <?= $auxiliar->traduce("Correcto a Procesar", $administrador->ID_IDIOMA) ?>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <?
                                                                $numeroFilas = 20;
                                                                if ($filasOk < 20):
                                                                    $numeroFilas = $filasOk + 1;
                                                                elseif ($filasOk > 20):
                                                                    $numeroFilas = 40 - $filasKo + 1;
                                                                endif;
                                                                ?>

                                                                <td height="<?= ($numeroFilas * 15); ?>"
                                                                    bgcolor="#FFF" align="center">

                                                                    <div>

                                                                        <table cellpadding="0" cellspacing="2"
                                                                               width="100%">

                                                                            <tr>
                                                                                <td width="2%" height="19"
                                                                                    bgcolor="#2E8AF0" class="blanco">
                                                                                    <?
                                                                                    $valorCheck = '1';
                                                                                    $jscript    = " onClick=\"seleccionarTodas(this)\" ";
                                                                                    $Nombre     = 'chSelecTodas';
                                                                                    $html->Option("chSelecTodas", "Check", "1", $valorCheck);
                                                                                    $jscript = "";
                                                                                    ?>
                                                                                </td>
                                                                                <td height="19" bgcolor="#2E8AF0"
                                                                                    class="blanco"><?= $auxiliar->traduce("Ref. ubicacion", $administrador->ID_IDIOMA) ?></td>
                                                                                <td height="19" bgcolor="#2E8AF0"
                                                                                    class="blanco"><?= $auxiliar->traduce("Ref. centro", $administrador->ID_IDIOMA) ?></td>
                                                                                <td height="19" bgcolor="#2E8AF0"
                                                                                    class="blanco"><?= $auxiliar->traduce("Ref. almacen", $administrador->ID_IDIOMA) ?></td>
                                                                                <td height="19" bgcolor="#2E8AF0"
                                                                                    class="blanco"><?= $auxiliar->traduce("Categoria", $administrador->ID_IDIOMA) ?></td>
                                                                                <td height="19" bgcolor="#2E8AF0"
                                                                                    class="blanco"><?= $auxiliar->traduce("Descripcion", $administrador->ID_IDIOMA) ?></td>
                                                                                <td height="19" bgcolor="#2E8AF0"
                                                                                    class="blanco"><?= $auxiliar->traduce("Precio Fijo", $administrador->ID_IDIOMA) ?></td>
                                                                                <td height="19" bgcolor="#2E8AF0"
                                                                                    class="blanco"><?= $auxiliar->traduce("Autostore", $administrador->ID_IDIOMA) ?></td>
                                                                                <td height="19" bgcolor="#2E8AF0"
                                                                                    class="blanco"><?= $auxiliar->traduce("Baja", $administrador->ID_IDIOMA) ?></td>
                                                                                <? if ($existenUbicacionesTiposector == true): ?>
                                                                                    <td height="19" bgcolor="#2E8AF0"
                                                                                        class="blanco"><?= $auxiliar->traduce("Es Tipo Sector", $administrador->ID_IDIOMA) ?></td>
                                                                                    <td height="19" bgcolor="#2E8AF0"
                                                                                        class="blanco"><?= $auxiliar->traduce("Tipo Sector", $administrador->ID_IDIOMA) ?></td>
                                                                                    <td height="19" bgcolor="#2E8AF0"
                                                                                        class="blanco"><?= $auxiliar->traduce("Cantidad Paneles", $administrador->ID_IDIOMA) ?></td>
                                                                                <? endif; ?>
                                                                            </tr>

                                                                            <? if (count( (array)$arrLineasValidas) == 0): //NO HAY LINEAS VALIDAS PARA IMPORTAR DATOS ?>

                                                                                <tr>
                                                                                    <td colspan="11"
                                                                                        align="center"><?= $auxiliar->traduce("No existen datos correctos a importar", $administrador->ID_IDIOMA) ?></td>
                                                                                </tr>

                                                                            <? elseif (count( (array)$arrLineasValidas) > 0): //HAY LINEAS VALIDAS PARA IMPORTAR DATOS?>

                                                                                <?
                                                                                //VARIABLE PARA PINTAR EL COLOR DE LA FILA
                                                                                $i = 0;
                                                                                ?>

                                                                                <!-- PINTO LAS LINEAS -->
                                                                                <? foreach ($arrLineasValidas as $indice => $arrValores): ?>
                                                                                    <?
                                                                                    //COLOR DE LA FILA
                                                                                    if ($i % 2 == 0) $myColor = "#B3C7DA";
                                                                                    else $myColor = "#AACFF9";
                                                                                    ?>

                                                                                    <tr>
                                                                                        <td height="18" align="center"
                                                                                            bgcolor="<?= $myColor ?>"
                                                                                            class="enlaceceldas">
                                                                                            <input type="hidden"
                                                                                                   id="txReferenciaUbicacion_<?= $indice ?>"
                                                                                                   name="txReferenciaUbicacion_<?= $indice ?>"
                                                                                                   value="<?= $arrValores['REFERENCIA_UBICACION'] ?>">
                                                                                            <input type="hidden"
                                                                                                   id="txReferenciaCentro_<?= $indice ?>"
                                                                                                   name="txReferenciaCentro_<?= $indice ?>"
                                                                                                   value="<?= $arrValores['REFERENCIA_CENTRO'] ?>">
                                                                                            <input type="hidden"
                                                                                                   id="txReferenciaAlmacen_<?= $indice ?>"
                                                                                                   name="txReferenciaAlmacen_<?= $indice ?>"
                                                                                                   value="<?= $arrValores['REFERENCIA_ALMACEN'] ?>">
                                                                                            <input type="hidden"
                                                                                                   id="txCategoria_<?= $indice ?>"
                                                                                                   name="txCategoria_<?= $indice ?>"
                                                                                                   value="<?= $arrValores['CATEGORIA'] ?>">
                                                                                            <input type="hidden"
                                                                                                   id="txDescripcion_<?= $indice ?>"
                                                                                                   name="txDescripcion_<?= $indice ?>"
                                                                                                   value="<?= $arrValores['DESCRIPCION'] ?>">
                                                                                            <input type="hidden"
                                                                                                   id="txPrecioFijo_<?= $indice ?>"
                                                                                                   name="txPrecioFijo_<?= $indice ?>"
                                                                                                   value="<?= $arrValores['PRECIO_FIJO'] ?>">
                                                                                            <input type="hidden"
                                                                                                   id="txAutostore_<?= $indice ?>"
                                                                                                   name="txAutostore_<?= $indice ?>"
                                                                                                   value="<?= $arrValores['AUTOSTORE'] ?>">
                                                                                            <input type="hidden"
                                                                                                   id="txBaja_<?= $indice ?>"
                                                                                                   name="txBaja_<?= $indice ?>"
                                                                                                   value="<?= $arrValores['BAJA'] ?>">
                                                                                            <input type="hidden"
                                                                                                   id="txEsTipoSector_<?= $indice ?>"
                                                                                                   name="txEsTipoSector_<?= $indice ?>"
                                                                                                   value="<?= $arrValores['ES_TIPO_SECTOR'] ?>">
                                                                                            <input type="hidden"
                                                                                                   id="txRefTipoSector_<?= $indice ?>"
                                                                                                   name="txRefTipoSector_<?= $indice ?>"
                                                                                                   value="<?= $arrValores['REF_TIPO_SECTOR'] ?>">
                                                                                            <input type="hidden"
                                                                                                   id="txCantidadPaneles_<?= $indice ?>"
                                                                                                   name="txCantidadPaneles_<?= $indice ?>"
                                                                                                   value="<?= $arrValores['CANTIDAD_PANELES'] ?>">

                                                                                            <? $html->Option("chLinea_" . $indice, "Check", "1", 1); ?>
                                                                                        </td>
                                                                                        <td align="left"
                                                                                            bgcolor="<?= $myColor ?>"
                                                                                            class="enlaceceldas">
                                                                                            &nbsp;<?= $arrValores['REFERENCIA_UBICACION']; ?>
                                                                                            &nbsp;</td>
                                                                                        <td align="left"
                                                                                            bgcolor="<?= $myColor ?>"
                                                                                            class="enlaceceldas">
                                                                                            &nbsp;<?= $arrValores['REFERENCIA_CENTRO']; ?>
                                                                                            &nbsp;</td>
                                                                                        <td align="left"
                                                                                            bgcolor="<?= $myColor ?>"
                                                                                            class="enlaceceldas">
                                                                                            &nbsp;<?= $arrValores['REFERENCIA_ALMACEN']; ?>
                                                                                            &nbsp;</td>
                                                                                        <td align="left"
                                                                                            bgcolor="<?= $myColor ?>"
                                                                                            class="enlaceceldas">
                                                                                            &nbsp;<?= $arrValores['CATEGORIA']; ?>
                                                                                            &nbsp;</td>
                                                                                        <td align="left"
                                                                                            bgcolor="<?= $myColor ?>"
                                                                                            class="enlaceceldas">
                                                                                            &nbsp;<?= $arrValores['DESCRIPCION']; ?>
                                                                                            &nbsp;</td>
                                                                                        <td align="left"
                                                                                            bgcolor="<?= $myColor ?>"
                                                                                            class="enlaceceldas">
                                                                                            &nbsp;<?= $arrValores['PRECIO_FIJO']; ?>
                                                                                            &nbsp;</td>
                                                                                        <td align="left"
                                                                                            bgcolor="<?= $myColor ?>"
                                                                                            class="enlaceceldas">
                                                                                            &nbsp;<?= $arrValores['AUTOSTORE']; ?>
                                                                                            &nbsp;</td>
                                                                                        <td align="left"
                                                                                            bgcolor="<?= $myColor ?>"
                                                                                            class="enlaceceldas">
                                                                                            &nbsp;<?= $arrValores['BAJA']; ?>
                                                                                            &nbsp;</td>
                                                                                        <? if ($existenUbicacionesTiposector == true): ?>
                                                                                            <td align="left"
                                                                                                bgcolor="<?= $myColor ?>"
                                                                                                class="enlaceceldas">
                                                                                                &nbsp;<?= $arrValores['ES_TIPO_SECTOR']; ?>
                                                                                                &nbsp;</td>
                                                                                            <td align="left"
                                                                                                bgcolor="<?= $myColor ?>"
                                                                                                class="enlaceceldas">
                                                                                                &nbsp;<?= $arrValores['REF_TIPO_SECTOR']; ?>
                                                                                                &nbsp;</td>
                                                                                            <td align="right"
                                                                                                bgcolor="<?= $myColor ?>"
                                                                                                class="enlaceceldas">
                                                                                                &nbsp;<?= $arrValores['CANTIDAD_PANELES']; ?>
                                                                                                &nbsp;</td>
                                                                                        <? endif; ?>
                                                                                    </tr>
                                                                                    <?
                                                                                    //INCREMENTO EL VALOR DE LA VARIABLE
                                                                                    $i++;
                                                                                    ?>
                                                                                <? endforeach; ?>
                                                                                <!-- FIN PINTO LAS LINEAS -->

                                                                            <? endif; //FIN NO HAY/HAY LINEAS VALIDAS PARA IMPORTAR DATOS ?>

                                                                        </table>

                                                                    </div>

                                                                </td>
                                                            </tr>
                                                        </table>

                                                    <? endif; ?>

                                                    <br/>

                                                    <table width="100%" cellpadding="0" cellspacing="0">
                                                        <tr height="20px;">
                                                            <td>
                                                                <div align="left">
                                                                    <span class="textoazul">
                                                                        &nbsp;&nbsp;<a
                                                                            onclick="history.back();return false;"
                                                                            class="senaladoazul" href="#">&nbsp;&nbsp;&nbsp;&nbsp;<?= $auxiliar->traduce("Volver", $administrador->ID_IDIOMA) ?>
                                                                            &nbsp;&nbsp;&nbsp;&nbsp;</a>&nbsp;&nbsp;
                                                                    </span>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div align="right">
                                                                    <span class="textoazul">
                                                                        <a href="#" style='color:#CCCCCC'
                                                                           onClick='return false;' id='btnProcesar2'
                                                                           class="senaladoverde">&nbsp;&nbsp;&nbsp;&nbsp;<?= $auxiliar->traduce("Procesar", $administrador->ID_IDIOMA) ?>
                                                                            &nbsp;&nbsp;&nbsp;&nbsp;</a>&nbsp;&nbsp;
                                                                    </span>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    </table>
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
    <input type="submit" style="position:absolute; top:-999999px"/>
</FORM>
</body>
</html>