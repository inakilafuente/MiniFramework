<?
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

//VARIABLES PARA CONTROLAR LO MOSTRADO
$strKo    = "";
$indiceOK = 0; //NÚMERO DE LÍNEAS CORRECTAS

$indiceKo = 0; //NÚMERO DE LÍNEAS ERRÓNEAS
$filasKo  = 0; //NÚMERO TOTAL DE ERRORES

$strAviso   = "";
$filasAviso = 0;

//VARIABLE PARA GUARDAR LOS VALORES A GRABAR
$arrLineasValidas = array();

//VARIABLE PARA CONTROLAR LOS INDICES DE LAS LINEAS
$indice = 2;

$arrValores = unserialize(urldecode($valores));

foreach ($arrValores as $valor):

    $ubicacion_repetida = false;

    //VARIABLE PARA SABER SI HAY ERROR EN LINEA
    $errorLinea = false;

    //OBTENENGO VALORES
    $refUbicacion = $valor['REF_UBICACION'];
    $refCentro    = $valor['REF_CENTRO'];
    $refAlmacen   = $valor['REF_ALMACEN'];
    $precioFijo   = $valor['PRECIO_FIJO'];
    $baja         = $valor['BAJA'];
    $esTipoSector = $valor['ES_TIPO_SECTOR'];

    //COMPROBACIONES DE DATOS RELLENADOS
    //ref ubicacion
    if ($refUbicacion == ""):
        $html->PagError("ErrorRefUbicacionVacia");
    else: //SE COMPRUEBA SI EL USUARIO ESTÁ IMPORTANDO DOS UBICACIONES CON LA MISMA REFERENCIA
        foreach ($arrLineasValidas as $linea):
            if (strtoupper($linea['REFERENCIA_UBICACION']) == strtoupper($refUbicacion)):
                $strKo .= $auxiliar->traduce("Línea", $administrador->ID_IDIOMA) . " $indice. ";
                $strKo .= $auxiliar->traduce("La ubicacion", $administrador->ID_IDIOMA) . " " . $refUbicacion . " " . $auxiliar->traduce("esta repetida", $administrador->ID_IDIOMA) . ".\n";
                $filasKo++;
                $errorLinea = true;
            endif;
        endforeach;
    endif;

    //ref centro
    if ($refCentro == ""):
        $html->PagError("ErrorRefCentroVacia");
    endif;

    //ref almacen
    if ($refAlmacen == ""):
        $html->PagError("ErrorRefAlmacenVacia");
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
            $strKo .= $auxiliar->traduce("Almacen", $administrador->ID_IDIOMA) . " " . $refAlmacen . " " . $auxiliar->traduce("de tipo 'acciona' no encontrado para el centro", $administrador->ID_IDIOMA) . " " . $refCentro . ".\n";
            $filasKo++;
            $errorLinea = true;
        endif;
    endif;

    //ubicacion
    $rowUbicacion = false;
    if ($refUbicacion != "" && $rowAlmacen):
        $NotificaErrorPorEmail = "No";
        $rowUbicacion          = $bd->VerRegRest("UBICACION", "UBICACION = '" . $refUbicacion . "' AND ID_ALMACEN = '" . $rowAlmacen->ID_ALMACEN . "' AND TIPO_UBICACION = 'Preventivo'", "No");
        unset($NotificaErrorPorEmail);
    endif;
    //FIN OBTENGO DATOS NECESARIOS

    //COMPROBACION DE DATOS VALIDOS
    //ubicacion
    //si existe la ubicacion, el tipo ha de ser Preventivo
    //si se quiere dar de baja, ha de estar vacía
    if ($rowUbicacion):
        if ($rowUbicacion->TIPO_UBICACION == 'Preventivo'):
            $strAviso .= $auxiliar->traduce("Línea", $administrador->ID_IDIOMA) . " $indice. ";
            $strAviso .= $auxiliar->traduce("La ubicación", $administrador->ID_IDIOMA) . " " . $refUbicacion . " " . $auxiliar->traduce("ya existe en la BD", $administrador->ID_IDIOMA) . ".\n";
            $filasAviso++;
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
    //FIN COMPROBACION DE DATOS VALIDOS

    //DATOS CORRECTOS
    if ($errorLinea == false):
        $arrLineasValidas[$indice]['REFERENCIA_UBICACION'] = $refUbicacion;
        $arrLineasValidas[$indice]['REFERENCIA_CENTRO']    = $refCentro;
        $arrLineasValidas[$indice]['REFERENCIA_ALMACEN']   = $refAlmacen;
        $arrLineasValidas[$indice]['PRECIO_FIJO']          = $precioFijo;
        $arrLineasValidas[$indice]['BAJA']                 = $baja;
        $arrLineasValidas[$indice]['ES_TIPO_SECTOR']       = $esTipoSector;

        //AÑADIMOS 1 AL NÚMERO DE LÍNEAS VÁLIDAS
        $indiceOK++;
    else:
        //AÑADIMOS 1 AL NÚMERO DE LÍNEAS ERRÓNEAS
        $indiceKo++;
    endif;

    //INCREMENTO LA LINEA
    $indice = $indice + 1;

endforeach;

if (sizeof($arrValores) == 0 || ($indiceOK == 0 && $indiceKo == 0)):
    $strKo = $auxiliar->traduce("Debe indicar al menos una referencia", $administrador->ID_IDIOMA) . ".";
endif;
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <? require_once $pathClases . "lib/gral_js.php"; ?>
    <script language="JavaScript" type="text/JavaScript">

        var bandera = true;

        jQuery(document).ready(function () {
            <? if ($indiceOK > 0): ?>

            jQuery('#btnProcesar').attr('onclick', 'continuar();return false');
            jQuery('#btnProcesar').css('color', '');
            jQuery('#btnProcesar2').attr('onclick', 'continuar();return false');
            jQuery('#btnProcesar2').css('color', '');

            <? endif; ?>
        });

        function continuar() {
            if (bandera == true) {
                bandera = false;

                jQuery('#btnProcesar').attr('onclick', 'return false');
                jQuery('#btnProcesar').css('color', '#CCCCCC');
                jQuery('#btnProcesar2').attr('onclick', 'return false');
                jQuery('#btnProcesar2').css('color', '#CCCCCC');

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
<FORM NAME="FormSelect" ACTION="ficha_importacion_preventivo_accion.php" METHOD="POST" style="margin-bottom:0;"
      enctype="multipart/form-data">
    <input type="hidden" name="claveTiempo" value="<? echo $claveTiempo; ?>">
    <?
    $ArchivoImportado = $RutayFichArchivo;
    if (file_exists($ArchivoImportado) == 1): // HAY ARCHIVO IMPORTADO
        ?>
        <input type="hidden" name="adjunto_archivo_importacion_material_almacen" value="<? echo $nombreFichero; ?>">
        <?
    endif;
    ?>

    <? $navegar->GenerarCamposOcultosForm(); ?>
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
                                                        src="<? echo $pathRaiz ?>imagenes/flechitas_01.gif" width="35"
                                                        height="23">
                                                </td>
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
                                                            <td width="220" bgcolor="#982a29" class="lineabajoarriba"
                                                                colspan=2><font class="tituloNav"><? echo $tituloNav ?>
                                                                </font></td>
                                                            <td width="20" valign=top bgcolor="#B3C7DA"
                                                                class="lineabajoarriba"><img
                                                                    src="<? echo $pathRaiz ?>imagenes/esquina_02.gif"
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
                                                                        &nbsp;&nbsp;
                                                                        <a onclick="history.back();return false;"
                                                                           class="senaladoazul" href="#">
                                                                            &nbsp;&nbsp;&nbsp;&nbsp;
                                                                            <?= $auxiliar->traduce("Volver", $administrador->ID_IDIOMA) ?>
                                                                            &nbsp;&nbsp;&nbsp;&nbsp;
                                                                        </a>
                                                                        &nbsp;&nbsp;
                                                                    </span>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div align="right">
                                                                    <span class="textoazul">
                                                                        <a href="#" style='color:#CCCCCC'
                                                                           onClick='return false;' id='btnProcesar'
                                                                           class="senaladoverde">
                                                                            &nbsp;&nbsp;&nbsp;&nbsp;
                                                                            <?= $auxiliar->traduce("Procesar", $administrador->ID_IDIOMA) ?>
                                                                            &nbsp;&nbsp;&nbsp;&nbsp;
                                                                        </a>
                                                                        &nbsp;&nbsp;
                                                                    </span>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    </table>

                                                    <br/>

                                                    <? if ($filasAviso > 0): ?>
                                                        <table width="98%" cellpadding="0" cellspacing="2">
                                                            <tr>
                                                                <td height="19" class="blanco" align="left">
                                                                    <div align="left"><span
                                                                            class="textorojo resaltado"><?= $auxiliar->traduce("LAS SIGUIENTES UBICACIONES YA EXISTEN EN LA BD", $administrador->ID_IDIOMA) . ":"; ?>
                                                                            </span></div>
                                                                </td>
                                                            </tr>
                                                        </table>
                                                        <table width="98%" cellpadding="0" cellspacing="2"
                                                               class="linealrededor">
                                                            <tr>
                                                                <td height="19" bgcolor="#2E8AF0" class="blanco"
                                                                    align="center">
                                                                    <?= $auxiliar->traduce("Avisos", $administrador->ID_IDIOMA) ?>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td height="19" bgcolor="#FFF" align="center">
                                                                    <table cellpadding="0" cellspacing="0" width="100%">
                                                                        <tr>
                                                                            <td style="padding-right:4px;">
                                                                                <?
                                                                                $numeroFilas = 20;
                                                                                if ($filasAviso < 20):
                                                                                    $numeroFilas = $filasAviso + 1;
                                                                                endif;
                                                                                ?>
                                                                                <textarea name="txLineasError"
                                                                                          class="copyright"
                                                                                          style="resize:none; width:100%;"
                                                                                          rows="<?= $numeroFilas ?>"
                                                                                          readonly="readonly"><?= $strAviso ?></textarea>
                                                                            </td>
                                                                        </tr>
                                                                    </table>
                                                                </td>
                                                            </tr>
                                                        </table>

                                                    <? endif; ?>

                                                    <? if ($filasKo > 0 || sizeof($arrValores) == 0): ?>
                                                        <table width="98%" cellpadding="0" cellspacing="2">
                                                            <tr>
                                                                <td height="19" class="blanco" align="left">
                                                                    <div align="left"><span
                                                                            class="textorojo resaltado"><?= $auxiliar->traduce("LAS SIGUIENTES LINEAS NO SE CARGARAN POR CONTENER ERRORES", $administrador->ID_IDIOMA) ?>
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

                                                    <? if ($indiceOK > 0): ?>

                                                        <br/>
                                                        <table width="98%" cellpadding="0" cellspacing="2">
                                                            <tr>
                                                                <td height="19" class="blanco" align="left">
                                                                    <div align="left">
                                                                        <span class="textoazul resaltado">
                                                                            <?= $auxiliar->traduce("LAS LINEAS SELECCIONADAS SERAN IMPORTADAS", $administrador->ID_IDIOMA) . ":" ?>
                                                                        </span>
                                                                    </div>
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
                                                                if ($indiceOK < 20):
                                                                    $numeroFilas = $indiceOK + 1;
                                                                elseif ($indiceOK > 20):
                                                                    $numeroFilas = 40 - $indiceKo + 1;
                                                                endif;
                                                                ?>

                                                                <td height="<? echo($numeroFilas * 15); ?>"
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
                                                                                    class="blanco"><?= $auxiliar->traduce("Tipo Ubicacion", $administrador->ID_IDIOMA) ?></td>
                                                                                <td height="19" bgcolor="#2E8AF0"
                                                                                    class="blanco"><?= $auxiliar->traduce("Tipo Preventivo", $administrador->ID_IDIOMA) ?></td>
                                                                                <td height="19" bgcolor="#2E8AF0"
                                                                                    class="blanco"><?= $auxiliar->traduce("Baja", $administrador->ID_IDIOMA) ?></td>
                                                                            </tr>

                                                                            <? if (count($arrLineasValidas) == 0): //NO HAY LINEAS VALIDAS PARA IMPORTAR DATOS ?>

                                                                                <tr>
                                                                                    <td colspan="11"
                                                                                        align="center"><?= $auxiliar->traduce("No existen datos correctos a importar", $administrador->ID_IDIOMA) ?></td>
                                                                                </tr>

                                                                            <? elseif (count($arrLineasValidas) > 0): //HAY LINEAS VALIDAS PARA IMPORTAR DATOS?>

                                                                                <?
                                                                                //VARIABLE PARA PINTAR EL COLOR DE LA FILA
                                                                                $i = 0;
                                                                                ?>

                                                                                <!-- PINTO LAS LINEAS -->
                                                                                <? foreach ($arrLineasValidas as $indice => $arrValores): ?>
                                                                                    <?
                                                                                    //COLOR DE LA FILA
                                                                                    if ($i % 2 == 0) $myColor = "#B3C7DA"; else $myColor = "#AACFF9";
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
                                                                                                   id="txBaja_<?= $indice ?>"
                                                                                                   name="txBaja_<?= $indice ?>"
                                                                                                   value="<?= $arrValores['BAJA'] ?>">
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
                                                                                            &nbsp;Preventivo
                                                                                            &nbsp;</td>
                                                                                        <td align="left"
                                                                                            bgcolor="<?= $myColor ?>"
                                                                                            class="enlaceceldas">
                                                                                            &nbsp;Pendientes
                                                                                            &nbsp;</td>
                                                                                        <td align="left"
                                                                                            bgcolor="<?= $myColor ?>"
                                                                                            class="enlaceceldas">
                                                                                            &nbsp;<?= $arrValores['BAJA']; ?>
                                                                                            &nbsp;</td>
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
                                                        <tr height="20px;" bgcolor="#B3C7DA">
                                                            <td>
                                                                <div align="left">
                                                                    <span class="textoazul">
                                                                        &nbsp;&nbsp;
                                                                        <a onclick="history.back();return false;"
                                                                           class="senaladoazul" href="#">
                                                                            &nbsp;&nbsp;&nbsp;&nbsp;
                                                                            <?= $auxiliar->traduce("Volver", $administrador->ID_IDIOMA) ?>
                                                                            &nbsp;&nbsp;&nbsp;&nbsp;
                                                                        </a>
                                                                        &nbsp;&nbsp;
                                                                  </span>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div align="right">
                                                                    <span class="textoazul">
                                                                        <a href="#" style='color:#CCCCCC'
                                                                           onClick='return false;' id='btnProcesar2'
                                                                           class="senaladoverde">
                                                                            &nbsp;&nbsp;&nbsp;&nbsp;
                                                                            <?= $auxiliar->traduce("Procesar", $administrador->ID_IDIOMA) ?>
                                                                            &nbsp;&nbsp;&nbsp;&nbsp;
                                                                        </a>
                                                                        &nbsp;&nbsp;
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
