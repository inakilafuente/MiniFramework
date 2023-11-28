<?
// PATHS DE LA WEB
$pathRaiz   = "../";
$pathClases = "../../";

// INCLUDES DE LIBRERIAS PROPIAS
require_once $pathClases . "lib/basedatos.php";
require_once $pathClases . "lib/administrador.php";
require_once $pathClases . "lib/html.php";
require_once $pathClases . "lib/navegar.php";
require_once $pathClases . "lib/comprobar.php";
require_once $pathClases . "lib/auxiliar.php";

session_start();
include $pathRaiz . "seguridad_admin.php";

$tituloPag      = $auxiliar->traduce("Unidades", $administrador->ID_IDIOMA);
$tituloNav      = $auxiliar->traduce("Maestros", $administrador->ID_IDIOMA) . " >> " . $auxiliar->traduce("Unidades", $administrador->ID_IDIOMA);
$ZonaTablaPadre = "Maestros";
$ZonaTabla      = "MaestrosUnidades";

//ESTABLECEMOS VARIABLE PARA BUSQUEDA RECORDAR
$BuscadorMultiple = "1";
$PaginaRecordar   = "BusquedaUnidades$NombreCampo";

//GUARDAMOS LA PAGINA PADRE DESDE DONDE SE LLAMA AL BUSCADOR
if (!isset($paginaReferer)):
    $paginaReferer = $_SERVER['HTTP_REFERER'];
endif;

// RECUERDO BUSQUEDAS REALIZADAS
include $pathRaiz . "busqueda_recordar.php";

//SI ES FICHA NO DEJAMOS SELECCION MULTIPLE
if (!isset($seleccionMultiple)):
    if ($AlmacenarId == 0):
        $seleccionMultiple = 1;
    elseif ($AlmacenarId == 1):
        $seleccionMultiple = 0;
    endif;
endif;

// CONTROLO EL CAMBIO DEL LIMITE
if (!Empty($CambiarLimite)):
    $navegar->maxfilasMaestroUnidades = $selLimite;
endif;

//FILTRAMOS SEGUN SEA EL IDIOMA:
$idIdioma = $administrador->ID_IDIOMA;
if ($idIdioma != "ESP" && $idIdioma != "ENG"): $idIdioma = "ESP"; endif;

// ORDENACION DE COLUMNAS
//$columnas_ord["unidad"]="UNIDAD";
//$columnas_ord["descripcion"]="DESCRIPCION";
$columnas_ord["unidad"]      = "UNIDAD_" . $idIdioma;
$columnas_ord["descripcion"] = "DESCRIPCION_" . $idIdioma;
$columna_defecto             = "unidad";
$sentido_defecto             = "0"; //ASCENDENTE
$navegar->DefinirColumnasOrdenacion($columnas_ord, $columna_defecto, $sentido_defecto);

//PARA ACOTAR LAS BUSQUEDAS (QUE NO ESTEN BORRADOS)
$sqlUnidad = "WHERE 1=1";

$sqlUnidad .= ($UnidadMedida == 1)? " AND ES_UNIDAD_MEDIDA = 1 ": "";
$sqlUnidad .= ($UnidadCompra == 1)? " AND ES_UNIDAD_COMPRA = 1 ": "";
$sqlUnidad .= ($UnidadCompra == 1 && $UnidadCompraAdmin != 1) ? " AND ES_UNIDAD_COMPRA_ADMIN = 0 ": "";

//UNIDAD
if (trim( (string)$txUnidad) != ""):
    $camposBD   = array('DESCRIPCION_' . $idIdioma, 'UNIDAD_' . $idIdioma);
    $sqlUnidad  = $sqlUnidad . ($bd->busquedaTextoArray($txUnidad, $camposBD));
    $textoLista = $textoLista . "&" . $auxiliar->traduce("Unidad", $administrador->ID_IDIOMA) . ": " . $txUnidad;
endif;


// TEXTO LISTADO
if ($textoLista == ""):
    $textoLista = $auxiliar->traduce("Todas las unidades", $administrador->ID_IDIOMA);
else:
    if (substr( (string) $textoLista, 0, 1) == "&") $textoLista = substr( (string) $textoLista, 1);
    $textoSustituir = "</font><font color='#EA62A2'> &gt;&gt; </font><font size='0px'>";
    $textoLista     = preg_replace("/&/", $textoSustituir, $textoLista);
endif;

$error = "NO";
if ($limite == ""):
    $mySql = "SELECT * FROM UNIDAD ";
    $mySql .= "$sqlUnidad";
    $navegar->sqlAdminMaestroUnidades = $mySql;
endif;

// REALIZO LA SENTENCIA SQL
$navegar->Sql($navegar->sqlAdminMaestroUnidades, $navegar->maxfilasMaestroUnidades, $navegar->numerofilasMaestroUnidades);

// NUMERO DE REGISTROS
$numRegistros = $navegar->numerofilasMaestroUnidades;
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

    <script type="text/javascript" language="javascript">
        function EstablecerValor(fila, almacenarId) {
            var numInput = 0;
            numInput = numInput + <?= ($seleccionMultiple == 1? 1:0)?>;
            if (almacenarId == '1') {
                parent.jQuery('#id<?=$NombreCampo!=""?$NombreCampo:'Unidad'?>').val(fila.children().eq(numInput).find('input').val());
                parent.jQuery('#tx<?=$NombreCampo!=""?$NombreCampo:'Unidad'?>').val(fila.children().eq(numInput).find('a').text() + ' - ' + fila.children().eq(1).find('a').text());
            } else {
                parent.jQuery('#id<?=$NombreCampo!=""?$NombreCampo:'Unidad'?>').val(fila.children().eq(numInput).find('input').val());
                parent.jQuery('#tx<?=$NombreCampo!=""?$NombreCampo:'Unidad'?>').val(fila.children().eq(numInput).find('a').text());
            }
            parent.jQuery.fancybox.close();
            parent.jQuery('#tx<?=$NombreCampo!=""?$NombreCampo:'Unidad'?>').focus();
            return false;
        }
    </script>

    <script language="JavaScript" type="text/JavaScript">

        var bandera = true;

        jQuery(document).ready(function () {

            //MARCAR LOS CHECKS EN CASO DE QUE HAYA UNA BUSQUEDA ANTERIOR
            var ids = parent.jQuery("#id<?=($NombreCampo != "" ? $NombreCampo : 'Unidad')?>").val().split("<?= SEPARADOR_BUSQUEDA_MULTIPLE?>");
            //for (var id of ids){
            for (var i = 0; i < ids.length; i++) {
                jQuery("input[name='chLinea_" + ids[i] + "'").prop("checked", true);
            }
            <? if ($filasOk > 0): ?>

            jQuery('#btnProcesar').attr('onclick', 'continuar();return false');
            jQuery('#btnProcesar').css('color', '');
            jQuery('#btnProcesar2').attr('onclick', 'continuar();return false');
            jQuery('#btnProcesar2').css('color', '');

            <? endif; ?>
        });

        function continuar() {

            algunoMarcado = 0;
            arrReferencias = "";
            arrIds = "";
            if (bandera == true) {
                bandera = false;

                jQuery('#btnProcesar').attr('onclick', 'return false');
                jQuery('#btnProcesar').css('color', '#CCCCCC');
                jQuery('#btnProcesar2').attr('onclick', 'return false');
                jQuery('#btnProcesar2').css('color', '#CCCCCC');

                //RECORREMOS ELEMENTOS MARCADOS PARA BUSCAR
                for (i = 0; i < document.FormSelect.elements.length; i++) {
                    var nombreElemento = document.FormSelect.elements[i].name;
                    if ((document.FormSelect.elements[i].type == "checkbox") && (nombreElemento.substr(0, 8) == "chLinea_") && document.FormSelect.elements[i].checked == 1) {
                        algunoMarcado++;
                        id = nombreElemento.substr(8);
                        if (arrIds == "") {
                            arrIds = id;
                            arrReferencias = jQuery("#idUnidad" + id).val();
                        } else {
                            arrIds = arrIds + "<?= SEPARADOR_BUSQUEDA_MULTIPLE?>" + id;
                            arrReferencias = arrReferencias + "<?= SEPARADOR_BUSQUEDA_MULTIPLE?>" + jQuery("#idUnidad" + id).val();

                        }
                    }
                }
                if (algunoMarcado > 1) {
                    jQuery('#id<?= $NombreCampo != "" ? $NombreCampo : 'Unidad' ?>').val(arrIds);
                    parent.jQuery('#id<?= $NombreCampo != "" ? $NombreCampo : 'Unidad' ?>').val(arrIds);
                    parent.jQuery.fancybox.close();
                    parent.jQuery('#tx<?= $NombreCampo != "" ? $NombreCampo : 'Unidad' ?>').val("<?= $auxiliar->traduce("Seleccion Multiple", $administrador->ID_IDIOMA) ?>");
                    parent.jQuery('#tx<?= $NombreCampo != "" ? $NombreCampo : 'Unidad' ?>').attr("onchange", "document.FormSelect.id<?= $NombreCampo != "" ? $NombreCampo : 'Unidad' ?>.value='';jQuery('#tx<?= $NombreCampo != "" ? $NombreCampo : 'Unidad' ?>').removeClass('textoazulElectrico');");
                    parent.jQuery('#tx<?= $NombreCampo != "" ? $NombreCampo : 'Unidad' ?>').addClass('textoazulElectrico');
                    parent.jQuery('#tx<?= $NombreCampo != "" ? $NombreCampo : 'Unidad' ?>').focus();
                    return false;
                } else if (algunoMarcado == 1) {
                    jQuery('#id<?= $NombreCampo != "" ? $NombreCampo : 'Unidad' ?>').val(arrIds);
                    parent.jQuery('#id<?= $NombreCampo != "" ? $NombreCampo : 'Unidad' ?>').val(arrIds);
                    parent.jQuery.fancybox.close();
                    parent.jQuery('#tx<?= $NombreCampo != "" ? $NombreCampo : 'Unidad' ?>').val(arrReferencias);
                    parent.jQuery('#tx<?= $NombreCampo != "" ? $NombreCampo : 'Unidad' ?>').focus();
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
                    }
                    else {
                        document.FormSelect.elements[i].checked = 0;
                    }
                }
            }
        }
    </script>
</head>
<body class="fancy" bgcolor="#FFFFFF" background="<? echo $pathRaiz ?>imagenes/fondo_pantalla.gif" leftmargin="0"
      topmargin="0" marginwidth="0" marginheight="0" onLoad="document.FormSelect.txUnidad.focus()">
<FORM NAME="FormSelect" ACTION="busqueda_unidad.php?recordar_busqueda_multiple=1" METHOD="POST"
      style="margin-bottom:0;">
    <input type=hidden name="AlmacenarId" id="AlmacenarId" value="<?= $AlmacenarId; ?>">
    <input type=hidden name="NombreCampo" id="NombreCampo" value="<?= $NombreCampo; ?>">
    <input type=hidden name="paginaReferer" id="paginaReferer" value="<?= $paginaReferer; ?>">
    <input type=hidden name="seleccionMultiple" id="seleccionMultiple" value="<?= $seleccionMultiple; ?>">
    <input type=hidden name="UnidadMedida" id="UnidadMedida" value="<?= $UnidadMedida; ?>">
    <input type=hidden name="UnidadCompra" id="UnidadCompra" value="<?= $UnidadCompra; ?>">
    <? $navegar->GenerarCamposOcultosForm(); ?>
    <table width="100%" border="0" align="center" cellpadding="0" cellspacing="0" background="">
        <tr>
            <td align="center" valign="top">
                <table width="100%" border="0" cellpadding="0" cellspacing="0">
                    <tr>
                        <td height="3" colspan="2" align="center" valign="top" bgcolor="#7A0A0A" class="linearriba"><img
                                src="<? echo $pathRaiz ?>imagenes/transparente.gif" width="10" height="3"></td>
                    </tr>
                    <tr>
                        <td align="left" valign="top" bgcolor="#FFFFFF"
                            background="<? echo $pathRaiz ?>imagenes/fondo_pantalla.gif">
                            <table width="100%" border="0" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td height="23">
                                        <table width="100%" height="23" border="0" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td width="25" class="linearriba"><img
                                                        src="<? echo $pathRaiz ?>imagenes/flechitas_01.gif" width="35"
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
                                                                &nbsp;</td>
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
                                <tr bgcolor="#D9E3EC">
                                    <td height="13" align="center" valign="top" bgcolor="#AACFF9" class="lineabajo">
                                        <table width="100%" height="13" border="0" align="center" cellpadding="0"
                                               cellspacing="0">
                                            <tr>
                                                <td width="20" align="center" valign="bottom" class="lineaderecha">
                                                    &nbsp;</td>
                                                <td align="center" valign="middle">
                                                    <table width="97%" height="11" border="0" align="center"
                                                           cellpadding="0" cellspacing="0" style="margin-top:5px;">
                                                        <tr>
                                                            <td height="1" colspan="3" bgcolor="#D9E3EC"
                                                                class="linearribadereizq"><img
                                                                    src="<? echo $pathRaiz ?>imagenes/transparente.gif"
                                                                    width="10" height="5"></td>
                                                        </tr>
                                                        <tr bgcolor="#D9E3EC">
                                                            <td width="10" bgcolor="#D9E3EC" class="lineaizquierda">
                                                                &nbsp;</td>
                                                            <td width="100%" align="left" bgcolor="#D9E3EC">
                                                                <table width="97%" border="0" cellpadding="0"
                                                                       cellspacing="0">
                                                                    <tr>
                                                                        <td width="24%" height="20" align="left"
                                                                            valign="middle"
                                                                            class="textoazul"><?= $auxiliar->traduce("Unidad", $administrador->ID_IDIOMA) ?>
                                                                            :
                                                                        </td>
                                                                        <td width="24%" align="left" valign="middle">
                                                                            <?
                                                                            $TamanoText = "200px";
                                                                            $ClassText  = "copyright";
                                                                            $MaxLength  = "50";
                                                                            $html->TextBox("txUnidad", $txUnidad);
                                                                            ?>
                                                                        </td>
                                                                        <td width="4%" align="center" valign="top">
                                                                            &nbsp;</td>
                                                                        <td width="24%" height="20" align="left"
                                                                            valign="middle" class="textoazul">
                                                                            &nbsp;</td>
                                                                        <td width="24%" align="left" valign="middle">
                                                                            &nbsp;

                                                                        </td>
                                                                    </tr>

                                                                </table>
                                                            </td>
                                                            <td width="4" bgcolor="#D9E3EC" class="lineaderecha">
                                                                &nbsp;</td>
                                                        </tr>

                                                        <tr bgcolor="#D9E3EC">
                                                            <td height="5" colspan="3" bgcolor="#D9E3EC"
                                                                class="lineabajodereizq"><img
                                                                    src="<? echo $pathRaiz ?>imagenes/transparente.gif"
                                                                    width="10" height="5"></td>
                                                        </tr>
                                                    </table>
                                                    <img src="<? echo $pathRaiz ?>imagenes/transparente.gif" width="10"
                                                         height="5"></td>
                                                <td width="20" align="center" valign="bottom" class="lineaizquierda">
                                                    &nbsp;</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="left" valign="top" bgcolor="#B3C7DA" class="lineabajo">
                                        <table width="100%" border="0" cellpadding="0" cellspacing="0"
                                               class="lineabajo">
                                            <tr>
                                                <td height="25" colspan="2" align="center" valign="middle"
                                                    class="lineabajo">
                                                    <div align="right">
                                                        <span class="textoazul">
                                                            <? if ($seleccionMultiple == 1): ?>
                                                                <a href="#" class="senaladoverde"
                                                                   onClick="continuar();return false">
                                                                    &nbsp;&nbsp;&nbsp;&nbsp;
                                                                    <?= $auxiliar->traduce("Seleccionar", $administrador->ID_IDIOMA) ?>
                                                                    &nbsp;&nbsp;&nbsp;&nbsp;
                                                                </a>
                                                                &nbsp;
                                                            <? endif; ?>
                                                            <a href="#" class="senaladoamarillo"
                                                               onClick="document.FormSelect.Buscar.value='Si';document.FormSelect.submit();return false">
                                                                &nbsp;&nbsp;&nbsp;&nbsp;<?= $auxiliar->traduce("Buscar", $administrador->ID_IDIOMA) ?>
                                                                &nbsp;&nbsp;&nbsp;&nbsp;
                                                            </a>
                                                          &nbsp;
                                                        </span>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td colspan="2" bgcolor="#d9e3ec">
                                                    <table border="0" cellpadding="0" cellspacing="0" height="10">
                                                        <tbody>
                                                        <? if ($numRegistros > 0): ?>
                                                            <tr>
                                                                <td width="515" height="20" colspan="2"
                                                                    class="alertas4">
                                                                    &nbsp;&nbsp;&nbsp;<? echo "$textoLista" ?></td>
                                                            </tr>
                                                        <? endif ?>
                                                        </tbody>
                                                    </table>
                                                </td>
                                            </tr>
                                            <? if ($numRegistros > 0): ?>
                                                <tr bgcolor="#D9E3EC" class="lineaabajo" height="22">
                                                    <td width="27%" class="copyright">
                                                        &nbsp;&nbsp;&nbsp;<?= $auxiliar->traduce("Ver", $administrador->ID_IDIOMA) ?>
                                                        &nbsp;#
                                                        <? $navegar->GenerarComboNumRegs($navegar->maxfilasMaestroUnidades, "selLimiteSuperior"); ?>
                                                        &nbsp;&nbsp;
                                                        <? $navegar->NumRegs($navegar->maxfilasMaestroUnidades, $maxahora, $navegar->numerofilasMaestroUnidades); ?>
                                                    </td>
                                                    <td width="73%" class="copyright">
                                                        <div
                                                            align="right"><? $navegar->Numeros($navegar->sqlAdminMaestroUnidades, $navegar->maxfilasMaestroUnidades, $navegar->numerofilasMaestroUnidades, $i, "index.php", "#2E8AF0") ?>
                                                            &nbsp;&nbsp;&nbsp;</div>
                                                    </td>
                                                </tr>
                                                <tr class="lineabajo">
                                                    <td colspan="2" align="center" bgcolor="#D9E3EC">
                                                        <table width="98%" cellpadding="0" cellspacing="2"
                                                               class="linealrededor">

                                                            <tr>
                                                                <? if ($seleccionMultiple == 1): ?>
                                                                    <td width="2%" height="19"
                                                                        bgcolor="#2E8AF0" class="blanco">
                                                                        <?
                                                                        $jscript = " onClick=\"seleccionarTodas(this)\" ";
                                                                        $Nombre  = 'chSelecTodas';
                                                                        $html->Option("chSelecTodas", "Check", "1", 0);
                                                                        $jscript = "";
                                                                        ?>
                                                                    </td>

                                                                <? endif; ?>
                                                                <td height="19" bgcolor="#2E8AF0"
                                                                    class="blanco"><? $navegar->GenerarColumna($auxiliar->traduce("Código unidad", $administrador->ID_IDIOMA), "enlaceCabecera", "unidad", $pathRaiz) ?></td>
                                                                <td height="19" bgcolor="#2E8AF0"
                                                                    class="blanco"><? $navegar->GenerarColumna($auxiliar->traduce("Descripción unidad", $administrador->ID_IDIOMA), "enlaceCabecera", "descripcion", $pathRaiz) ?></td>
                                                            </tr>
                                                            <? // MUESTRO LAS COINCIDENCIAS CON LA BUSQUEDA
                                                            $i = 0;
                                                            // PARA LA NUMERACION DE CADA URL
                                                            $numeracion = $mostradas + 1;
                                                            while ($i < $maxahora):
                                                                $row = $bd->SigReg($resultado);

                                                                //COLOR DE LA FILA
                                                                if ($i % 2 == 0) $myColor = "#B3C7DA";
                                                                else $myColor = "#AACFF9";


                                                                ?>
                                                                <tr align="center" valign="middle" bgcolor="#AACFF9">
                                                                    <? if ($seleccionMultiple == 1): ?>
                                                                        <td height="18" align="center"
                                                                            bgcolor="<?= $myColor ?>"
                                                                            class="enlaceceldas">
                                                                            <input type="hidden"
                                                                                   id="idMoneda<? echo $row->ID_UNIDAD ?>"
                                                                                   name="idMoneda<? echo $row->ID_UNIDAD ?>"
                                                                                   value="<? echo $row->{'UNIDAD_' . $idIdioma} ?>">

                                                                            <? $html->Option("chLinea_" . $row->ID_UNIDAD, "Check", "1", 0); ?>
                                                                        </td>
                                                                    <? endif; ?>
                                                                    <td height="18" align="left"
                                                                        bgcolor="<? echo $myColor ?>"
                                                                        class="enlaceceldas">
                                                                        &nbsp;<a href="#"
                                                                                 onClick="EstablecerValor(jQuery(this).parent().parent(),'<?= $AlmacenarId ?>','<?= $establecerNombreCentro ?>')"
                                                                                 class="enlaceceldas"
                                                                                 style="white-space:pre;"><? echo $row->{'UNIDAD_' . $idIdioma} ?></a>
                                                                        <input type="hidden"
                                                                               value="<?= $row->ID_UNIDAD ?>"/>
                                                                    </td>
                                                                    <td height="18" align="left"
                                                                        bgcolor="<? echo $myColor ?>"
                                                                        class="enlaceceldas">
                                                                        &nbsp;<a href="#"
                                                                                 onClick="EstablecerValor(jQuery(this).parent().parent(),'<?= $AlmacenarId ?>','<?= $establecerNombreCentro ?>')"
                                                                                 class="enlaceceldas"
                                                                                 style="white-space:pre;"><? echo $row->{'DESCRIPCION_' . $idIdioma} ?></a>
                                                                    </td>

                                                                </tr>
                                                                <?

                                                                $i++;
                                                                $numeracion++;
                                                            endwhile; ?>
                                                        </table>
                                                    </td>
                                                </tr>
                                            <? else: ?>
                                                <tr>
                                                    <td colspan="2" align="center" valign="top" bgcolor="#D9E3EC"
                                                        class="alertas3">
                                                        <br><?= $auxiliar->traduce("No existen registros para la búsqueda realizada", $administrador->ID_IDIOMA) ?>
                                                        <br><br></td>
                                                </tr>
                                            <? endif; ?>
                                            <? if ($numRegistros > 0): ?>
                                                <tr bgcolor="#D9E3EC" class="lineaabajo" height="22">
                                                    <td width="27%" class="copyright">
                                                        &nbsp;&nbsp;&nbsp;<?= $auxiliar->traduce("Ver", $administrador->ID_IDIOMA) ?>
                                                        &nbsp;#
                                                        <? $navegar->GenerarComboNumRegs($navegar->maxfilasMaestroUnidades); ?>
                                                        &nbsp;&nbsp;
                                                        <? $navegar->NumRegs($navegar->maxfilasMaestroUnidades, $maxahora, $navegar->numerofilasMaestroUnidades); ?>
                                                    </td>
                                                    <td width="73%" class="copyright">
                                                        <div
                                                            align="right"><? $navegar->Numeros($navegar->sqlAdminMaestroUnidades, $navegar->maxfilasMaestroUnidades, $navegar->numerofilasMaestroUnidades, $i, "index.php", "#2E8AF0") ?>
                                                            &nbsp;&nbsp;&nbsp;</div>
                                                    </td>
                                                </tr>
                                            <? endif; ?>
                                        </table>
                                        <br><br></td>
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
