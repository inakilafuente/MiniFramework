<?php
// PATHS DE LA WEB
$pathRaiz   = "../../";
$pathClases = "../../../";

// INCLUDES DE LIBRERIAS PROPIAS
require_once $pathClases . "lib/basedatos.php";
require_once $pathClases . "lib/gestor.php";
require_once $pathClases . "lib/administrador.php";
require_once $pathClases . "lib/material.php";
require_once $pathClases . "lib/html.php";
require_once $pathClases . "lib/navegar.php";
require_once $pathClases . "lib/comprobar.php";
require_once $pathClases . "lib/auxiliar.php";
require_once $pathClases . "lib/ubicacion.php";
require_once $pathClases . "lib/xajax25_php7/xajax_core/xajax.inc.php";

session_start();
include $pathRaiz . "seguridad_admin.php";

$tituloPag         = $auxiliar->traduce("Ubicaciones", $administrador->ID_IDIOMA);
$tituloNav         = $auxiliar->traduce("Maestros", $administrador->ID_IDIOMA) . " >> " . $auxiliar->traduce($tituloPag, $administrador->ID_IDIOMA);
$ZonaTablaPadre    = "Maestros";
$ZonaSubTablaPadre = "MaestrosSubmenuAlmacen";
$ZonaTabla         = "MaestrosUbicaciones";
$PaginaRecordar    = "ListadoMaestrosUbicaciones";

//DEFINIMOS PAGINA DE ERROR
$Pagina_Error = "error_pequeno.php";

// COMPRUEBA SI TIENE PERMISOS
if ($administrador->Hayar_Permiso_Perfil('ADM_MAESTROS_UBICACIONES_BAJA_MASIVA') < 2):
    $html->PagError("SinPermisos");
endif;

//ARRAY DE MATERIALES UBICACION SELECCIONADOS
$arrayUbicacion = explode(",", (string)$listaIdUbicaciones);

$arrDescartar           = array();
$errorMaterialUbicacion = false;
$linea                  = 1;

//RECORRO MATERIALES UBICACION QUE ME LLEG
foreach ($arrayUbicacion as $indice => $idUbicacion):

    $strKoo     = "";
    $errorLinea = false;

    $row                   = $bd->VerReg("UBICACION", "ID_UBICACION", $idUbicacion, "No");
    $NotificaErrorPorEmail = "No";

    //OBTENGO EL ALMACÉN
    $rowAlmacen = $bd->VerReg("ALMACEN", "ID_ALMACEN", $row->ID_ALMACEN, "No");

    //CUPERO MENSAJE ERROR
    $mensajeError = $linea . ". " . $auxiliar->traduce("Ubicacion", $administrador->ID_IDIOMA) . ":" . $row->UBICACION;

    if ($rowAlmacen->TIPO_ALMACEN == "externalizado"):
        //SI LA UBICACIÓN ES DE STOCK EXTERNALIZADO, NO DEJAMOS DARLA DE BAJA
        $errorLinea = true;
        $strKoo     .= $auxiliar->traduce("La ubicacion que intenta dar de baja es de tipo stock externalizado", $administrador->ID_IDIOMA) . ". " . $mensajeError;
    endif;

    //COMPROBAR QUE NO CONTENGA MATERIAL
    $sqlMaterialUbicacion = "SELECT SUM(STOCK_TOTAL) AS CANTIDAD
															 FROM MATERIAL_UBICACION
															 WHERE ID_UBICACION =  $row->ID_UBICACION";

    $resMaterialUbicacion = $bd->ExecSQL($sqlMaterialUbicacion);
    while ($rowMaterialUbicacion = $bd->SigReg($resMaterialUbicacion)):
        if (($rowMaterialUbicacion->CANTIDAD != NULL) && ($rowMaterialUbicacion->CANTIDAD != 0)):
            $errorLinea = true;
            $strKoo     .= $auxiliar->traduce("La ubicación que intenta dar de baja tiene material ubicado", $administrador->ID_IDIOMA) . ". ";

        endif;
    endwhile;
    //QUE NO ESTE INVOLUCRADA EN NINGUN CONTEO ACTIVO
    $sqlConteoUbicacion = "SELECT COUNT(*) AS NUM
                                     FROM INVENTARIO_ORDEN_CONTEO_LINEA IOCL
                                     INNER JOIN INVENTARIO_ORDEN_CONTEO IOC ON IOC.ID_INVENTARIO_ORDEN_CONTEO = IOCL.ID_INVENTARIO_ORDEN_CONTEO
                                     WHERE IOCL.ID_UBICACION = '" . $bd->escapeCondicional($idUbicacion) . "' AND IOCL.BAJA = 0 AND IOC.ESTADO <> 'Finalizado' AND IOC.BAJA = 0";

    $resConteoUbicacion = $bd->ExecSQL($sqlConteoUbicacion);
    $rowConteoUbicacion = $bd->SigReg($resConteoUbicacion);
    if ($rowConteoUbicacion->NUM > 0):
        $errorLinea = true;
        $strKoo     .= $auxiliar->traduce("La ubicación que intenta dar de baja está asociada a una orden de conteo", $administrador->ID_IDIOMA) . ". ";

    endif;

    //ACTUALIZO LA TABLA CONTENEDORES DE TIPO GAVETA
    /* if ($row->TIPO_UBICACION == 'Gaveta'):
         //BUSCO EL CONTENEDOR
         $NotificaErrorPorEmail = "No";
         $rowContenedorGaveta = $bd->VerRegRest("CONTENEDOR", "ID_UBICACION = $row->ID_UBICACION AND TIPO = 'Gaveta'", "No");
         unset($NotificaErrorPorEmail);

         //ELIMINO EL CONTENEDOR DE TIPO GAVETA
         $sqlDelete = "DELETE FROM CONTENEDOR WHERE ID_UBICACION = $row->ID_UBICACION AND TIPO = 'Gaveta'";
         $bd->ExecSQL($sqlDelete);
     endif;*/


    //SI HAY ERROR LO QUITO DEL ARRAY PARA CONSULTAR
    if ($errorLinea == true):
        $filasKo++;
        $strKo .= $mensajeError . ". " . $strKoo . " \n";
        unset($arrayUbicacion[$indice]);
        $arrDescartar[] = $row->ID_UBICACION;
    else:
        $filasOk++;
    endif;
    $linea++;
endforeach;
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
            algunoMarcado = 0;
            if (bandera == true) {
                bandera = false;

                jQuery('#btnProcesar').attr('onclick', 'return false');
                jQuery('#btnProcesar').css('color', '#CCCCCC');
                jQuery('#btnProcesar2').attr('onclick', 'return false');
                jQuery('#btnProcesar2').css('color', '#CCCCCC');

                for (i = 0; i < document.FormSelect.elements.length; i++) {
                    var nombreElemento = document.FormSelect.elements[i].name;
                    if ((document.FormSelect.elements[i].type == "checkbox") && (nombreElemento.substr(0, 8) == "chLinea_") && document.FormSelect.elements[i].checked == 1) {
                        algunoMarcado = 1;
                    }
                }
                if (algunoMarcado == 1) {
                    document.FormSelect.accion.value = 'bajaMasivaUbicaciones';
                    document.FormSelect.submit();
                    return false;

                } else {

                    alert("<?= $auxiliar->traduce("Primero debe seleccionar alguno de los elementos", $administrador->ID_ADMINISTRADOR)?>");
                    jQuery('#btnProcesar').attr('onclick', 'continuar();return false');
                    jQuery('#btnProcesar').css('color', '');
                    jQuery('#btnProcesar2').attr('onclick', 'continuar();return false');
                    jQuery('#btnProcesar2').css('color', '');
                    bandera = true;
                }

                return false;
            }
        }

        function seleccionarTodas(chSel) {
            for (i = 0; i < document.FormSelect.elements.length; i++) {
                if ((document.FormSelect.elements[i].type == "checkbox") && (document.FormSelect.elements[i].name.substr(0, 8) == "chLinea_")) {
                    if (chSel.checked == 1) {
                        document.FormSelect.elements[i].checked = 1;
                    } else {
                        document.FormSelect.elements[i].checked = 0;
                    }
                }
            }
        }

        function seleccionarTodas(chSel) {
            for (i = 0; i < document.FormSelect.elements.length; i++) {
                if ((document.FormSelect.elements[i].type == "checkbox") && (document.FormSelect.elements[i].name.substr(0, 8) == "chLinea_")) {
                    if (chSel.checked == 1) {
                        document.FormSelect.elements[i].checked = 1;
                    } else {
                        document.FormSelect.elements[i].checked = 0;
                    }
                }
            }
        }

    </script>
</head>
<body class="fancy" bgcolor="#FFFFFF" background="<?= $pathRaiz ?>imagenes/fondo_pantalla.gif" leftmargin="0"
      topmargin="0" marginwidth="0" marginheight="0">
<FORM NAME="FormSelect" ACTION="accion.php" METHOD="POST" style="margin-bottom:0;">

    <input type="hidden" name="accion" value="">
    <input type="hidden" name="listaIdUbicaciones" value="<?= $listaIdUbicaciones ?>">
    <input type="hidden" name="indice" value="">

    <? $navegar->GenerarCamposOcultosForm(); ?>
    <table width="100%" border="0" align="center" cellpadding="0" cellspacing="0" background="">

        <tr>
            <td align="center" valign="top">
                <? //include $pathRaiz . "tabla_superior.php"; ?>
                <table width="100%" border="0" cellpadding="0" cellspacing="0">
                    <tr>
                        <td height="3" colspan="2" align="center" valign="top" bgcolor="#7A0A0A" class="linearriba">
                            <img src="<?= $pathRaiz ?>imagenes/transparente.gif" width="10" height="3">
                        </td>
                    </tr>
                    <tr>
                        <? //include $pathRaiz . "tabla_izqda.php"; ?>
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

                                                    <!-- <table width="100%" cellpadding="0" cellspacing="0">
                                                        <tr height="20px;" bgcolor="#B3C7DA">
                                                            <td>
                                                                <div align="left">
                                                                      <span class="textoazul">
                                                                        &nbsp;&nbsp;<a
                                                                              onclick="history.back();return false;"
                                                                              class="senaladoazul"
                                                                              href="#">
                                                                              &nbsp;&nbsp;&nbsp;&nbsp;<?= $auxiliar->traduce("Volver", $administrador->ID_IDIOMA) ?>
                                                                              &nbsp;&nbsp;&nbsp;&nbsp;</a>&nbsp;&nbsp;
                                                                      </span>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div align="right">
                                                                    <span class="textoazul">
                                                                        <a href="#" style='color:#CCCCCC'
                                                                           onClick='return false;' id='btnProcesar'
                                                                           class="senaladoverde">
                                                                            &nbsp;&nbsp;&nbsp;&nbsp;<?= $auxiliar->traduce("Procesar", $administrador->ID_IDIOMA) ?>
                                                                            &nbsp;&nbsp;&nbsp;&nbsp;</a>&nbsp;&nbsp;
                                                                    </span>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    </table>-->

                                                    <br/>
                                                    <? if ($filasKo > 0): ?>
                                                        <table width="98%" cellpadding="0" cellspacing="2">
                                                            <tr>
                                                                <td height="19" class="blanco" align="left">
                                                                    <div align="left"><span
                                                                                class="textorojo resaltado"><?= strtoupper( (string)$auxiliar->traduce("Los siguientes registros tienen errores", $administrador->ID_IDIOMA)) . " ($filasKo)" ?>
                                                                            :</span></div>
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
                                                                                          rows="<? echo $numeroFilas ?>"
                                                                                          readonly="readonly"><? echo $strKo ?></textarea>
                                                                            </td>
                                                                        </tr>
                                                                    </table>
                                                                </td>
                                                            </tr>
                                                        </table>


                                                    <? endif; ?>
                                                    <br/>
                                                    <table width="98%" cellpadding="0" cellspacing="2">
                                                        <tr>
                                                            <td height="19" class="blanco" align="left">
                                                                <div align="left">
                                                                        <span
                                                                                class="textoazul resaltado"><?= $auxiliar->traduce("LAS LINEAS SELECCIONADAS SERAN PROCESADAS", $administrador->ID_IDIOMA) . " ($filasOk):"; ?>
                                                                        </span>
                                                                </div>
                                                                <input type="hidden" id="selIntegracionConSAP"
                                                                       name="selIntegracionConSAP"
                                                                       value="<? echo $selIntegracionConSAP; ?>">
                                                                <input type="hidden" id="selMotivo"
                                                                       name="selMotivo"
                                                                       value="<? echo $selMotivo; ?>">
                                                                <input type="hidden" id="nombreFichero"
                                                                       name="nombreFichero"
                                                                       value="<? echo "TEMP_" . $claveTiempo . "_ARCHIVO_IMPORTADO_" . $nombreArchivoImportar . "." . $arrArchivoImportar[1]; ?>">
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


                                                            <td height="<? echo($numeroFilas); ?>"
                                                                bgcolor="#D9E3EC" align="center">

                                                                <div>

                                                                    <table cellpadding="0" cellspacing="2"
                                                                           width="100%" class="linealrededor">

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
                                                                                class="blanco"
                                                                                title="<?= $auxiliar->traduce("Ubicacion", $administrador->ID_IDIOMA) ?>"><? echo $auxiliar->traduce("Ubicacion", $administrador->ID_IDIOMA) ?></td>
                                                                            <td height="19" bgcolor="#2E8AF0"
                                                                                class="blanco"
                                                                                title="<?= $auxiliar->traduce("Centro", $administrador->ID_IDIOMA) ?>"><? echo $auxiliar->traduce("Centro", $administrador->ID_IDIOMA) ?></td>
                                                                            <td height="19" bgcolor="#2E8AF0"
                                                                                class="blanco"
                                                                                title="<?= $auxiliar->traduce("Almacén", $administrador->ID_IDIOMA) ?>"><? echo $auxiliar->traduce("Almacén", $administrador->ID_IDIOMA) ?></td>
                                                                            <td height="19" bgcolor="#2E8AF0"
                                                                                class="blanco"
                                                                                title="<?= $auxiliar->traduce("Centro Fisico", $administrador->ID_IDIOMA) ?>"><? echo $auxiliar->traduce("Centro Fisico", $administrador->ID_IDIOMA) ?></td>

                                                                            <td height="19" bgcolor="#2E8AF0"
                                                                                class="blanco"
                                                                                title="<?= $auxiliar->traduce("Tipo Ubicacion", $administrador->ID_IDIOMA) ?>"><? echo $auxiliar->traduce("Tipo Ubicacion", $administrador->ID_IDIOMA) ?></td>

                                                                            <td height="19" bgcolor="#2E8AF0"
                                                                                class="blanco"
                                                                                title="<?= $auxiliar->traduce("Categoria Ubicacion", $administrador->ID_IDIOMA) ?>"><? echo $auxiliar->traduce("Categoria Ubicacion", $administrador->ID_IDIOMA) ?></td>
                                                                            <td height="19" bgcolor="#2E8AF0"
                                                                                class="blanco"
                                                                                title="<?= $auxiliar->traduce("Clase APQ", $administrador->ID_IDIOMA) ?>"><? echo $auxiliar->traduce("Clase APQ", $administrador->ID_IDIOMA) ?></td>
                                                                            <td height="19" bgcolor="#2E8AF0"
                                                                                class="blanco"
                                                                                title="<?= $auxiliar->traduce("Precio Fijo", $administrador->ID_IDIOMA) ?>"><?= $auxiliar->traduce("Precio Fijo", $administrador->ID_IDIOMA) ?></td>
                                                                            <td height="19" bgcolor="#2E8AF0"
                                                                                class="blanco"
                                                                                title="<?= $auxiliar->traduce("Id Ubicacion", $administrador->ID_IDIOMA) ?>"><?= $auxiliar->traduce("Id Ubicacion", $administrador->ID_IDIOMA) ?></td>
                                                                            <td height="19" bgcolor="#2E8AF0"
                                                                                class="blanco"
                                                                                title="<?= $auxiliar->traduce("Descripcion", $administrador->ID_IDIOMA) ?>"><? echo $auxiliar->traduce("Descripcion", $administrador->ID_IDIOMA) ?></td>
                                                                            <td height="19" bgcolor="#2E8AF0"
                                                                                class="blanco"
                                                                                title="<?= $auxiliar->traduce("Baja", $administrador->ID_IDIOMA) ?>"><? echo $auxiliar->traduce("Baja", $administrador->ID_IDIOMA) ?></td>
                                                                        </tr>

                                                                        <?

                                                                        $i      = 0;
                                                                        $indice = 0;
                                                                        foreach ($arrayUbicacion as $idUbicacion):
                                                                            $listaUbicacionesUtilizadas = "";
                                                                            //BUSCO LA UBICACION
                                                                            $rowUbicacion = $bd->VerReg("UBICACION", "ID_UBICACION", $idUbicacion, "No");
                                                                            //ALMACEN
                                                                            $rowAlmacen = $bd->VerReg("ALMACEN", "ID_ALMACEN", $rowUbicacion->ID_ALMACEN);
                                                                            //CENTRO
                                                                            $rowCentro = $bd->VerReg("CENTRO", "ID_CENTRO", $rowAlmacen->ID_CENTRO);
                                                                            //CENTRO FISICO
                                                                            $rowCentroFisico = $bd->VerReg("CENTRO_FISICO", "ID_CENTRO_FISICO", $rowAlmacen->ID_CENTRO_FISICO);

                                                                            $cantidadRestante = $rowMaterialUbicacion->STOCK_TOTAL;
                                                                            $myColor          = "#B3C7DA";
                                                                            ?>

                                                                            <tr>
                                                                                <td height="18" align="left"
                                                                                    bgcolor="<?= $myColor ?>"
                                                                                    class="enlaceceldas">
                                                                                    <input type="hidden"
                                                                                           id="idUbicacion<? echo $indice ?>"
                                                                                           name="idUbicacion<? echo $indice ?>"
                                                                                           value="<? echo $idUbicacion ?>">

                                                                                    <? $html->Option("chLinea_" . $indice, "Check", "1", 1); ?>
                                                                                </td>

                                                                                <td align="left"
                                                                                    bgcolor="<?= $myColor ?>"
                                                                                    class="enlaceceldas">
                                                                                    &nbsp;<? echo $rowUbicacion->UBICACION; ?>
                                                                                    &nbsp;
                                                                                </td>
                                                                                <td align="left"
                                                                                    bgcolor="<?= $myColor ?>"
                                                                                    class="enlaceceldas"
                                                                                    title="<?= $rowCentro->CENTRO ?>">
                                                                                    &nbsp;<? echo $rowCentro->REFERENCIA; ?>
                                                                                    &nbsp;
                                                                                </td>
                                                                                <td align="left"
                                                                                    bgcolor="<?= $myColor ?>"
                                                                                    class="enlaceceldas"
                                                                                    title="<?= $rowAlmacen->NOMBRE ?>">
                                                                                    &nbsp;<? echo $rowAlmacen->REFERENCIA; ?>
                                                                                    &nbsp;
                                                                                </td>
                                                                                <td align="right"
                                                                                    bgcolor="<?= $myColor ?>"
                                                                                    class="enlaceceldas">
                                                                                    &nbsp;<?= $rowCentroFisico->REFERENCIA ?>
                                                                                    &nbsp;
                                                                                </td>
                                                                                <td align="right"
                                                                                    bgcolor="<?= $myColor ?>"
                                                                                    class="enlaceceldas">
                                                                                    &nbsp;<?= ($rowUbicacion->TIPO_UBICACION == "" ? $auxiliar->traduce("Estándar", $administrador->ID_IDIOMA) : $auxiliar->traduce($rowUbicacion->TIPO_UBICACION, $administrador->ID_IDIOMA)) ?>
                                                                                    &nbsp;
                                                                                </td>
                                                                                <?
                                                                                //BUSCO EL TIPO DE CATEGORIA UBICACION
                                                                                $NotificaErrorPorEmail = "No";
                                                                                $rowCategoriaUbicacion = $bd->VerReg("UBICACION_CATEGORIA", "ID_UBICACION_CATEGORIA", $rowUbicacion->ID_UBICACION_CATEGORIA, "No");
                                                                                unset($NotificaErrorPorEmail);
                                                                                ?>
                                                                                <td align="right"
                                                                                    bgcolor="<?= $myColor ?>"
                                                                                    class="enlaceceldas">
                                                                                    &nbsp;<?= ($rowUbicacion->ID_UBICACION_CATEGORIA == NULL ? "-" : ($rowUbicacion->ID_UBICACION_CATEGORIA . ' - ' . $rowCategoriaUbicacion->NOMBRE)) ?>
                                                                                    &nbsp;
                                                                                </td>

                                                                                <td align="right"
                                                                                    bgcolor="<?= $myColor ?>"
                                                                                    class="enlaceceldas">
                                                                                    &nbsp;<?= ($rowUbicacion->CLASE_APQ == NULL ? "-" : $rowUbicacion->CLASE_APQ) ?>
                                                                                    &nbsp;
                                                                                </td>
                                                                                <td align="right"
                                                                                    bgcolor="<?= $myColor ?>"
                                                                                    class="enlaceceldas">
                                                                                    &nbsp; <?
                                                                                    if ($rowUbicacion->PRECIO_FIJO == '0'):
                                                                                        echo $auxiliar->traduce("No", $administrador->ID_IDIOMA);
                                                                                    elseif ($rowUbicacion->PRECIO_FIJO == '1'):
                                                                                        echo $auxiliar->traduce("Si", $administrador->ID_IDIOMA);
                                                                                    endif;
                                                                                    ?>
                                                                                    &nbsp;
                                                                                </td>
                                                                                <td align="right"
                                                                                    bgcolor="<?= $myColor ?>"
                                                                                    class="enlaceceldas">
                                                                                    &nbsp;<? echo $rowUbicacion->ID_UBICACION ?>
                                                                                    &nbsp;
                                                                                </td>
                                                                                <td align="right"
                                                                                    bgcolor="<?= $myColor ?>"
                                                                                    class="enlaceceldas">
                                                                                    &nbsp; <? if (strlen( (string)trim( (string)$rowUbicacion->DESCRIPCION)) > 0): ?>
                                                                                        <img
                                                                                                src="../../imagenes/form.png"
                                                                                                border="0"
                                                                                                align="absbottom"
                                                                                                width="15"
                                                                                                height="15"
                                                                                                title="<? echo $rowUbicacion->DESCRIPCION ?>"/>
                                                                                    <?
                                                                                    else:
                                                                                        echo "-";
                                                                                    endif;
                                                                                    ?>
                                                                                    &nbsp;
                                                                                </td>
                                                                                <td align="right"
                                                                                    bgcolor="<?= $myColor ?>"
                                                                                    class="enlaceceldas">
                                                                                    &nbsp; <?
                                                                                    if ($rowUbicacion->BAJA == '0'):
                                                                                        echo $auxiliar->traduce("No", $administrador->ID_IDIOMA);
                                                                                    elseif ($rowUbicacion->BAJA == '1'):
                                                                                        echo $auxiliar->traduce("Si", $administrador->ID_IDIOMA);
                                                                                    endif;
                                                                                    ?>
                                                                                    &nbsp;
                                                                                </td>

                                                                            </tr>
                                                                            <?
                                                                            $i++;
                                                                            $indice++;
                                                                        endforeach; ?>


                                                                    </table>

                                                                </div>

                                                            </td>
                                                        </tr>
                                                    </table>


                                                    <br/>

                                                    <table width="100%" border="0" cellspacing="0" cellpadding="0"
                                                           bgcolor="#D9E3EC">
                                                        <tr height="25">
                                                            <td>
                                                                <div align="left">
                                                  <span class="textoazul">
                                                    &nbsp;&nbsp;<a
                                                              onclick="window.parent.jQuery.fancybox.close();return false;"
                                                              class="senaladoazul" href="#">
                                                          &nbsp;&nbsp;&nbsp;&nbsp;<?= $auxiliar->traduce("Volver", $administrador->ID_IDIOMA) ?>
                                                          &nbsp;&nbsp;&nbsp;&nbsp;</a>&nbsp;&nbsp;
                                                  </span>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div align="right">
                                                  <span class="textoazul">
                                                      <? if ($filasOk > 0): ?>
                                                          <a href="#"
                                                             onClick='continuar();return false;'
                                                             id='btnProcesar2' class="senaladoverde">
                                                              &nbsp;&nbsp;&nbsp;&nbsp;<?= $auxiliar->traduce("Continuar", $administrador->ID_IDIOMA) ?>
                                                              &nbsp;&nbsp;&nbsp;&nbsp;</a>&nbsp;&nbsp;

                                                      <? else: ?>

                                                          <a href="#"
                                                             onClick=';return false;' style="color:#CCCCCC;"
                                                             id='btnProcesar2' class="blancoDisabled senaladoverde">
                                                              &nbsp;&nbsp;&nbsp;&nbsp;<?= $auxiliar->traduce("Continuar", $administrador->ID_IDIOMA) ?>
                                                              &nbsp;&nbsp;&nbsp;&nbsp;</a>&nbsp;&nbsp;
                                                      <? endif; ?>
                                                  </span>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>

                                        </table> <!-- fin table lineabajo-->
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
