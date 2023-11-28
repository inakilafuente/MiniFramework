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

$tituloPag      = $auxiliar->traduce("Familias Material", $administrador->ID_IDIOMA);
$tituloNav      = $auxiliar->traduce("Maestros", $administrador->ID_IDIOMA) . " >> " . $auxiliar->traduce("Familias Material", $administrador->ID_IDIOMA);
$ZonaTablaPadre = "Maestros";
$ZonaTabla      = "MaestrosFamiliasMaterial";

//ESTABLECEMOS VARIABLE PARA BUSQUEDA RECORDAR
$BuscadorMultiple = "0";
$PaginaRecordar   = "BusquedaFamiliasMaterial";

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
    $navegar->maxfilasMaestroFamiliaMaterial = $selLimite;
endif;

// ORDENACION DE COLUMNAS
$columnas_ord["id"] = "ID_FAMILIA_MATERIAL";
$columna_defecto            = "id";
$sentido_defecto            = "0"; //ASCENDENTE
$navegar->DefinirColumnasOrdenacion($columnas_ord, $columna_defecto, $sentido_defecto);

//PARA ACOTAR LAS BUSQUEDAS (QUE NO ESTEN BORRADOS)
$sqlFamilia = "WHERE 1=1";
$whereFamilia = "";
//NOMBRE FAMILIA
if (trim( (string)$txNombreFamilia) != ""):
    $sqlFamilia = $sqlFamilia . ($bd->busquedaTexto($txNombreFamilia, 'NOMBRE_FAMILIA'));
    $whereFamilia = " AND (FM_1.NOMBRE_FAMILIA LIKE '%$txNombreFamilia%' OR FM_2.NOMBRE_FAMILIA LIKE '%$txNombreFamilia%' OR FM_3.NOMBRE_FAMILIA LIKE '%$txNombreFamilia%' OR FM_4.NOMBRE_FAMILIA LIKE '%$txNombreFamilia%' OR FM_5.NOMBRE_FAMILIA LIKE '%$txNombreFamilia%' OR FM_6.NOMBRE_FAMILIA LIKE '%$txNombreFamilia%' OR FM_7.NOMBRE_FAMILIA LIKE '%$txNombreFamilia%' OR FM_8.NOMBRE_FAMILIA LIKE '%$txNombreFamilia%' OR FM_9.NOMBRE_FAMILIA LIKE '%$txNombreFamilia%' OR FM_1.NOMBRE_FAMILIA_ENG LIKE '%$txNombreFamilia%' OR FM_2.NOMBRE_FAMILIA_ENG LIKE '%$txNombreFamilia%' OR FM_3.NOMBRE_FAMILIA_ENG LIKE '%$txNombreFamilia%' OR FM_4.NOMBRE_FAMILIA_ENG LIKE '%$txNombreFamilia%' OR FM_5.NOMBRE_FAMILIA_ENG LIKE '%$txNombreFamilia%' OR FM_6.NOMBRE_FAMILIA_ENG LIKE '%$txNombreFamilia%' OR FM_7.NOMBRE_FAMILIA_ENG LIKE '%$txNombreFamilia%' OR FM_8.NOMBRE_FAMILIA_ENG LIKE '%$txNombreFamilia%' OR FM_9.NOMBRE_FAMILIA_ENG LIKE '%$txNombreFamilia%')";
    $textoLista = $textoLista . "&" . $auxiliar->traduce("Familia", $administrador->ID_IDIOMA) . ": " . $txNombreFamilia;
endif;

// TEXTO LISTADO
if ($textoLista == ""):
    $textoLista = $auxiliar->traduce("Todas las familias", $administrador->ID_IDIOMA);
else:
    if (substr( (string) $textoLista, 0, 1) == "&") $textoLista = substr( (string) $textoLista, 1);
    $textoSustituir = "</font><font color='#EA62A2'> &gt;&gt; </font><font>";
    $textoLista     = preg_replace("/&/", $textoSustituir, $textoLista);
endif;

$error = "NO";
if ($limite == ""):
    //MANERA NORMAL
    //$mySql = "SELECT ID_FAMILIA_MATERIAL,NIVEL_FAMILIA,ID_FAMILIA_MATERIAL_PADRE,NOMBRE_FAMILIA,ID_FAMILIA_REPRO FROM FAMILIA_MATERIAL $sqlFamilia";

    //MANERA ARBOL ESTILO CONSTRUCCION SOLAR
    $mySql = "SELECT FM_1.ID_FAMILIA_MATERIAL,FM_1.NIVEL_FAMILIA,FM_1.ID_FAMILIA_MATERIAL_PADRE,FM_1.NOMBRE_FAMILIA AS PADRE, FM_2.NOMBRE_FAMILIA AS NIVEL2, FM_3.NOMBRE_FAMILIA AS NIVEL3, FM_4.NOMBRE_FAMILIA AS NIVEL4, FM_5.NOMBRE_FAMILIA AS NIVEL5, FM_6.NOMBRE_FAMILIA AS NIVEL6, FM_7.NOMBRE_FAMILIA AS NIVEL7, FM_8.NOMBRE_FAMILIA AS NIVEL8, FM_9.NOMBRE_FAMILIA AS NIVEL9,FM_1.NOMBRE_FAMILIA_ENG AS PADRE_ENG, FM_2.NOMBRE_FAMILIA_ENG AS NIVEL2_ENG, FM_3.NOMBRE_FAMILIA_ENG AS NIVEL3_ENG, FM_4.NOMBRE_FAMILIA_ENG AS NIVEL4_ENG, FM_5.NOMBRE_FAMILIA_ENG AS NIVEL5_ENG, FM_6.NOMBRE_FAMILIA_ENG AS NIVEL6_ENG, FM_7.NOMBRE_FAMILIA_ENG AS NIVEL7_ENG, FM_8.NOMBRE_FAMILIA_ENG AS NIVEL8_ENG, FM_9.NOMBRE_FAMILIA_ENG AS NIVEL9_ENG
FROM FAMILIA_MATERIAL FM_1 
	LEFT JOIN FAMILIA_MATERIAL FM_2 ON FM_1.ID_FAMILIA_MATERIAL = FM_2.ID_FAMILIA_MATERIAL_PADRE
	LEFT JOIN FAMILIA_MATERIAL FM_3 ON FM_2.ID_FAMILIA_MATERIAL = FM_3.ID_FAMILIA_MATERIAL_PADRE
	LEFT JOIN FAMILIA_MATERIAL FM_4 ON FM_3.ID_FAMILIA_MATERIAL = FM_4.ID_FAMILIA_MATERIAL_PADRE
	LEFT JOIN FAMILIA_MATERIAL FM_5 ON FM_4.ID_FAMILIA_MATERIAL = FM_5.ID_FAMILIA_MATERIAL_PADRE
	LEFT JOIN FAMILIA_MATERIAL FM_6 ON FM_5.ID_FAMILIA_MATERIAL = FM_6.ID_FAMILIA_MATERIAL_PADRE
	LEFT JOIN FAMILIA_MATERIAL FM_7 ON FM_6.ID_FAMILIA_MATERIAL = FM_7.ID_FAMILIA_MATERIAL_PADRE
	LEFT JOIN FAMILIA_MATERIAL FM_8 ON FM_7.ID_FAMILIA_MATERIAL = FM_8.ID_FAMILIA_MATERIAL_PADRE
	LEFT JOIN FAMILIA_MATERIAL FM_9 ON FM_8.ID_FAMILIA_MATERIAL = FM_9.ID_FAMILIA_MATERIAL_PADRE
	WHERE (FM_1.NIVEL_FAMILIA = '1')  AND (FM_2.NIVEL_FAMILIA = '2' OR FM_2.NIVEL_FAMILIA IS NULL) AND (FM_3.NIVEL_FAMILIA = '3' OR FM_3.NIVEL_FAMILIA IS NULL) AND (FM_4.NIVEL_FAMILIA = '4' OR FM_4.NIVEL_FAMILIA IS NULL) AND (FM_5.NIVEL_FAMILIA = '5' OR FM_5.NIVEL_FAMILIA IS NULL) AND (FM_6.NIVEL_FAMILIA = '6' OR FM_6.NIVEL_FAMILIA IS NULL) AND (FM_7.NIVEL_FAMILIA = '7' OR FM_7.NIVEL_FAMILIA IS NULL) AND (FM_8.NIVEL_FAMILIA = '8' OR FM_8.NIVEL_FAMILIA IS NULL) AND (FM_9.NIVEL_FAMILIA = '9' OR FM_9.NIVEL_FAMILIA IS NULL) $whereFamilia
	GROUP BY FM_1.NOMBRE_FAMILIA";

    $navegar->sqlAdminMaestroFamiliaMaterial = $mySql;
endif;

// REALIZO LA SENTENCIA SQL
$navegar->Sql($navegar->sqlAdminMaestroFamiliaMaterial, $navegar->maxfilasMaestroFamiliaMaterial, $navegar->numerofilasMaestroFamiliaMaterial);

// NUMERO DE REGISTROS
$numRegistros = $navegar->numerofilasMaestroFamiliaMaterial;

//echo $mySql;
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
                parent.jQuery('#id<?=$NombreCampo!=""?$NombreCampo:'FamiliaMaterial'?>').val(fila.children().eq(numInput).find('input').val());
                parent.jQuery('#tx<?=$NombreCampo!=""?$NombreCampo:'FamiliaMaterial'?>').val(fila.children().eq(numInput).find('a').text() + ' - ' + fila.children().eq(1).find('a').text());
            } else {
                parent.jQuery('#id<?=$NombreCampo!=""?$NombreCampo:'FamiliaMaterial'?>').val(fila.children().eq(numInput).find('input').val());
                parent.jQuery('#tx<?=$NombreCampo!=""?$NombreCampo:'FamiliaMaterial'?>').val(fila.children().eq(numInput).find('a').text());
            }
            parent.jQuery.fancybox.close();
            parent.jQuery('#tx<?=$NombreCampo!=""?$NombreCampo:'FamiliaRepro'?>').focus();
            return false;
        }
        function EstablecerValorNuevo(idFamilia,$nombre, almacenarId) {
            var numInput = 0;
            numInput = numInput + <?= ($seleccionMultiple == 1? 1:0)?>;
            if (almacenarId == '1') {
                parent.jQuery('#id<?=$NombreCampo!=""?$NombreCampo:'FamiliaMaterial'?>').val(idFamilia);
                if(parent.jQuery('#tx<?=$NombreCampo!=""?$NombreCampo:'FamiliaMaterial'?>')){
                    parent.jQuery('#tx<?=$NombreCampo!=""?$NombreCampo:'FamiliaMaterial'?>').val($nombre);
                    parent.jQuery('#tx<?=$NombreCampo!=""?$NombreCampo:'FamiliaMaterial'?>').text($nombre);
                }
            } else {
                parent.jQuery('#id<?=$NombreCampo!=""?$NombreCampo:'FamiliaMaterial'?>').val(idFamilia);
                if(parent.jQuery('#tx<?=$NombreCampo!=""?$NombreCampo:'FamiliaMaterial'?>')){
                    parent.jQuery('#tx<?=$NombreCampo!=""?$NombreCampo:'FamiliaMaterial'?>').val($nombre);
                    parent.jQuery('#tx<?=$NombreCampo!=""?$NombreCampo:'FamiliaMaterial'?>').text($nombre);
                }
            }
            parent.jQuery.fancybox.close();
            return false;
        }
    </script>
    <script language="JavaScript" type="text/JavaScript">

        var bandera = true;

        jQuery(document).ready(function () {


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
                            arrReferencias = jQuery("#idFamiliaMaterial" + id).val();
                        } else {
                            arrIds = arrIds + "<?= SEPARADOR_BUSQUEDA_MULTIPLE?>" + id;
                            arrReferencias = arrReferencias + "<?= SEPARADOR_BUSQUEDA_MULTIPLE?>" + jQuery("#idFamiliaMaterial" + id).val();

                        }
                    }
                }
                if (algunoMarcado > 1) {
                    jQuery('#id<?= $NombreCampo != "" ? $NombreCampo : 'FamiliaMaterial' ?>').val(arrIds);
                    parent.jQuery('#id<?= $NombreCampo != "" ? $NombreCampo : 'FamiliaMaterial' ?>').val(arrIds);
                    parent.jQuery.fancybox.close();
                    parent.jQuery('#tx<?= $NombreCampo != "" ? $NombreCampo : 'FamiliaMaterial' ?>').val("<?= $auxiliar->traduce("Seleccion Multiple", $administrador->ID_IDIOMA) ?>");
                    parent.jQuery('#tx<?= $NombreCampo != "" ? $NombreCampo : 'FamiliaMaterial' ?>').attr("onchange", "document.FormSelect.id<?= $NombreCampo != "" ? $NombreCampo : 'FamiliaMaterial' ?>.value='';jQuery('#tx<?= $NombreCampo != "" ? $NombreCampo : 'FamiliaMaterial' ?>').removeClass('textoazulElectrico');");
                    parent.jQuery('#tx<?= $NombreCampo != "" ? $NombreCampo : 'FamiliaMaterial' ?>').addClass('textoazulElectrico');
                    parent.jQuery('#tx<?= $NombreCampo != "" ? $NombreCampo : 'FamiliaMaterial' ?>').focus();
                    return false;
                } else if (algunoMarcado == 1) {
                    jQuery('#id<?= $NombreCampo != "" ? $NombreCampo : 'FamiliaMaterial' ?>').val(arrIds);
                    parent.jQuery('#id<?= $NombreCampo != "" ? $NombreCampo : 'FamiliaMaterial' ?>').val(arrIds);
                    parent.jQuery.fancybox.close();
                    parent.jQuery('#tx<?= $NombreCampo != "" ? $NombreCampo : 'FamiliaMaterial' ?>').val(arrReferencias);
                    parent.jQuery('#tx<?= $NombreCampo != "" ? $NombreCampo : 'FamiliaMaterial' ?>').focus();
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

        function colorearFilas(idTr) {
            jQuery("#" + idTr).find('td').each (function () {
                jQuery(this).not(".noColorear").css("background-color", "pink");
            });
        }
        function descolorearFilas(idTr) {
            jQuery("#" + idTr).find('td').each (function () {
                jQuery(this).css("background-color", "");
            });
        }

        function visualizacionFamilias(idPadreVisualizacion){
            document.FormSelect.idPadreVisualizacion.value = idPadreVisualizacion;
            document.FormSelect.submit();
        }

        function ocultarFamilias(){
            document.FormSelect.idPadreVisualizacion.value = "";
            document.FormSelect.submit();
        }

        //PARA CONTRAER Y DESPLEGAR UN NIVEL
        function visualizacionNivel(zonaTabla) {

            if (!jQuery("#Contraer" + zonaTabla).is(':visible')) {
                jQuery("." + zonaTabla).each(function () {
                    jQuery(this).show();
                });
                jQuery("#Contraer" + zonaTabla).show();
                jQuery("#Expandir" + zonaTabla).hide();

            } else {
                jQuery("." + zonaTabla).each(function () {
                    jQuery(this).hide();
                });
                jQuery("#Contraer" + zonaTabla).hide();
                jQuery("#Expandir" + zonaTabla).show();

            }
        }
    </script>
</head>
<body class="fancy" bgcolor="#FFFFFF" background="<? echo $pathRaiz ?>imagenes/fondo_pantalla.gif" leftmargin="0"
      topmargin="0" marginwidth="0" marginheight="0" onLoad="document.FormSelect.txNombreFamilia.focus()">
<FORM NAME="FormSelect" ACTION="busqueda_familia_material.php?recordar_busqueda_multiple=1" METHOD="POST"
      style="margin-bottom:0;">
    <input type=hidden name="AlmacenarId" id="AlmacenarId" value="<?= $AlmacenarId; ?>">
    <input type=hidden name="NombreCampo" id="NombreCampo" value="<?= $NombreCampo; ?>">
    <input type=hidden name="idPadreVisualizacion" id="idPadreVisualizacion" value="<?= $idPadreVisualizacion; ?>">
    <input type=hidden name="paginaReferer" id="paginaReferer" value="<?= $paginaReferer; ?>">
    <input type=hidden name="seleccionMultiple" id="seleccionMultiple" value="<?= $seleccionMultiple; ?>">
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
                                                                            class="textoazul"><?= $auxiliar->traduce("Nombre familia", $administrador->ID_IDIOMA) ?>
                                                                            :
                                                                        </td>
                                                                        <td width="24%" align="left" valign="middle">
                                                                            <?
                                                                            $TamanoText = "200px";
                                                                            $ClassText  = "copyright";
                                                                            $MaxLength  = "50";
                                                                            $html->TextBox("txNombreFamilia", $txNombreFamilia);
                                                                            ?></td>
                                                                        <td width="4%" align="center" valign="top">
                                                                            &nbsp;</td>
                                                                        <td width="24%" height="20" align="left"
                                                                            valign="middle"
                                                                            class="textoazul">
                                                                        </td>
                                                                        <td width="24%" align="left" valign="middle">
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
                                                               onClick="document.FormSelect.Buscar.value='Si';document.FormSelect.idPadreVisualizacion.value='';document.FormSelect.submit();return false">
                                                                &nbsp;&nbsp;&nbsp;&nbsp;<?= $auxiliar->traduce("Buscar", $administrador->ID_IDIOMA) ?>
                                                                &nbsp;&nbsp;&nbsp;&nbsp;
                                                            </a>&nbsp;
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
                                            <!-- MANERA ARBOL ESTILO ESTRUCTURA CONSTRUCCION SOLAR-->
                                            <? if ($numRegistros > 0):
                                                ?>
                                                <tr>
                                                    <td width="100%" align="center" valign="middle" bgcolor="#D9E3EC"
                                                        colspan="3" class="alertas3"
                                                        height="19px"><? echo $mensajeBuscar; ?></td>
                                                </tr>
                                                <tr class="lineabajo">
                                                    <td colspan="2" align="center" bgcolor="#D9E3EC">
                                                        <table width="98%" cellpadding="0" cellspacing="2"
                                                               class="linealrededor">

                                                            <tr>
                                                                <td height="19" bgcolor="#2E8AF0" width="2%"
                                                                    class="blanco"></td>
                                                                <td height="19" bgcolor="#2E8AF0" width="3%"
                                                                    class="blanco"><? $navegar->GenerarColumna($auxiliar->traduce("Id", $administrador->ID_IDIOMA), "enlaceCabecera", "id", $pathRaiz) ?></td>
                                                                <td height="19" bgcolor="#2E8AF0" class="blanco"
                                                                    width="95%">
                                                                    &nbsp;<? echo $auxiliar->traduce("Familia", $administrador->ID_IDIOMA) ?>
                                                                    &nbsp;</td>
                                                            </tr>
                                                            <? // MUESTRO LAS COINCIDENCIAS CON LA BUSQUEDA
                                                            $i = 0;
                                                            // PARA LA NUMERACION DE CADA URL
                                                            $numeracion = $mostradas + 1;
                                                            while ($i < $maxahora):
                                                                $row                   = $bd->SigReg($resultado);

                                                                //COLOR DE LA FILA
                                                                if ($i % 2 == 0) $myColor = "#B3C7DA";
                                                                else $myColor = "#AACFF9";
                                                                ?>

                                                                <tr align="center" valign="middle" bgcolor="#AACFF9">
                                                                    <td height="18" align="center"
                                                                        bgcolor="<? echo $myColor ?>"
                                                                        class="enlaceceldas">
                                                                        <? $sqlHijasNivel2 = "SELECT ID_FAMILIA_MATERIAL FROM FAMILIA_MATERIAL WHERE ID_FAMILIA_MATERIAL_PADRE = $row->ID_FAMILIA_MATERIAL";
                                                                        $resHijasNivel2 = $bd->ExecSQL($sqlHijasNivel2);
                                                                        if($bd->NumRegs($resHijasNivel2) > 0):
                                                                        ?>
                                                                        <?if ($idPadreVisualizacion == $row->ID_FAMILIA_MATERIAL || trim( (string)$txNombreFamilia) != ""):?>
                                                                            <img
                                                                                    src="<? echo $pathRaiz ?>imagenes/collapse.png"
                                                                                    width="15" height="11"
                                                                                    style="cursor: pointer"
                                                                                    title="<?=$auxiliar->traduce("Contraer", $administrador->ID_IDIOMA)?>"
                                                                                    onClick="ocultarFamilias(); return false;"/>
                                                                        <?else:?>
                                                                        <img
                                                                                src="<? echo $pathRaiz ?>imagenes/expand.png"
                                                                                width="15" height="11"
                                                                                style="cursor: pointer"
                                                                                title="<?=$auxiliar->traduce("Expandir", $administrador->ID_IDIOMA)?>"
                                                                                onClick="visualizacionFamilias('<?=$row->ID_FAMILIA_MATERIAL?>'); return false;"/>
                                                                        <?endif;?>
                                                                        <?endif;?>
                                                                    </td>
                                                                    <td height="18" align="left"
                                                                        bgcolor="<? echo $myColor ?>"
                                                                        class="enlaceceldas">
                                                                        <a href="#"
                                                                           onClick="EstablecerValorNuevo(<?= $row->ID_FAMILIA_MATERIAL ?>,'<?= $administrador->ID_IDIOMA=="ESP" ? $row->PADRE : $row->PADRE_ENG; ?>',<?= $AlmacenarId ?>')"
                                                                           class="enlaceceldas"
                                                                           style="white-space:pre;"><? echo $row->ID_FAMILIA_MATERIAL ?></a>
                                                                        <input type="hidden"
                                                                               value="<?= $row->ID_FAMILIA_MATERIAL ?>"/>

                                                                    </td>
                                                                    <td height="18" align="left"
                                                                        bgcolor="<? echo $myColor ?>"
                                                                        class="enlaceceldas">
                                                                        <a href="#"
                                                                           onClick="EstablecerValorNuevo(<?= $row->ID_FAMILIA_MATERIAL ?>,'<?= $administrador->ID_IDIOMA=="ESP" ? $row->PADRE : $row->PADRE_ENG; ?>','<?= $AlmacenarId ?>')"
                                                                           class="enlaceceldas"
                                                                           style="white-space:pre;"><? echo $administrador->ID_IDIOMA=="ESP" ? $row->PADRE : $row->PADRE_ENG; ?></a>
                                                                        &nbsp;
                                                                        &nbsp;</td>
                                                                </tr>

                                                                <!--BUSQUEDA A NIVEL DE INSTALACION-->
                                                                <?
                                                                if ($idPadreVisualizacion == $row->ID_FAMILIA_MATERIAL  || trim( (string)$txNombreFamilia) != ""):

                                                                    //BUSCAMOS LOS ALMACENES DE INSTALACION
                                                                    $sqlFamMatNivel2 = "SELECT FM_1.ID_FAMILIA_MATERIAL,FM_1.NIVEL_FAMILIA,FM_1.ID_FAMILIA_MATERIAL_PADRE,FM_1.NOMBRE_FAMILIA AS PADRE, FM_2.ID_FAMILIA_MATERIAL AS ID2, FM_2.NOMBRE_FAMILIA AS NIVEL2, FM_3.NOMBRE_FAMILIA AS NIVEL3, FM_4.NOMBRE_FAMILIA AS NIVEL4, FM_5.NOMBRE_FAMILIA AS NIVEL5, FM_6.NOMBRE_FAMILIA AS NIVEL6, FM_7.NOMBRE_FAMILIA AS NIVEL7, FM_8.NOMBRE_FAMILIA AS NIVEL8, FM_9.NOMBRE_FAMILIA AS NIVEL9,FM_1.NOMBRE_FAMILIA_ENG AS PADRE_ENG, FM_2.NOMBRE_FAMILIA_ENG AS NIVEL2_ENG, FM_3.NOMBRE_FAMILIA_ENG AS NIVEL3_ENG, FM_4.NOMBRE_FAMILIA_ENG AS NIVEL4_ENG, FM_5.NOMBRE_FAMILIA_ENG AS NIVEL5_ENG, FM_6.NOMBRE_FAMILIA_ENG AS NIVEL6_ENG, FM_7.NOMBRE_FAMILIA_ENG AS NIVEL7_ENG, FM_8.NOMBRE_FAMILIA_ENG AS NIVEL8_ENG, FM_9.NOMBRE_FAMILIA_ENG AS NIVEL9_ENG
FROM FAMILIA_MATERIAL FM_1 
	LEFT JOIN FAMILIA_MATERIAL FM_2 ON FM_1.ID_FAMILIA_MATERIAL = FM_2.ID_FAMILIA_MATERIAL_PADRE
	LEFT JOIN FAMILIA_MATERIAL FM_3 ON FM_2.ID_FAMILIA_MATERIAL = FM_3.ID_FAMILIA_MATERIAL_PADRE
	LEFT JOIN FAMILIA_MATERIAL FM_4 ON FM_3.ID_FAMILIA_MATERIAL = FM_4.ID_FAMILIA_MATERIAL_PADRE
	LEFT JOIN FAMILIA_MATERIAL FM_5 ON FM_4.ID_FAMILIA_MATERIAL = FM_5.ID_FAMILIA_MATERIAL_PADRE
	LEFT JOIN FAMILIA_MATERIAL FM_6 ON FM_5.ID_FAMILIA_MATERIAL = FM_6.ID_FAMILIA_MATERIAL_PADRE
	LEFT JOIN FAMILIA_MATERIAL FM_7 ON FM_6.ID_FAMILIA_MATERIAL = FM_7.ID_FAMILIA_MATERIAL_PADRE
	LEFT JOIN FAMILIA_MATERIAL FM_8 ON FM_7.ID_FAMILIA_MATERIAL = FM_8.ID_FAMILIA_MATERIAL_PADRE
	LEFT JOIN FAMILIA_MATERIAL FM_9 ON FM_8.ID_FAMILIA_MATERIAL = FM_9.ID_FAMILIA_MATERIAL_PADRE
	WHERE (FM_1.NIVEL_FAMILIA = '1')  AND (FM_2.NIVEL_FAMILIA = '2' OR FM_2.NIVEL_FAMILIA IS NULL) AND (FM_3.NIVEL_FAMILIA = '3' OR FM_3.NIVEL_FAMILIA IS NULL) AND (FM_4.NIVEL_FAMILIA = '4' OR FM_4.NIVEL_FAMILIA IS NULL) AND (FM_5.NIVEL_FAMILIA = '5' OR FM_5.NIVEL_FAMILIA IS NULL) AND (FM_6.NIVEL_FAMILIA = '6' OR FM_6.NIVEL_FAMILIA IS NULL) AND (FM_7.NIVEL_FAMILIA = '7' OR FM_7.NIVEL_FAMILIA IS NULL) AND (FM_8.NIVEL_FAMILIA = '8' OR FM_8.NIVEL_FAMILIA IS NULL) AND (FM_9.NIVEL_FAMILIA = '9' OR FM_9.NIVEL_FAMILIA IS NULL) AND FM_2.ID_FAMILIA_MATERIAL_PADRE = '".$row->ID_FAMILIA_MATERIAL."' $whereFamilia
	GROUP BY NIVEL2";

                                                                    $resFamMatNivel2 = $bd->ExecSQL($sqlFamMatNivel2);
                                                                    $numFamMatNivel2 = $bd->NumRegs($resFamMatNivel2);

                                                                    ?>
                                                                    <tr>
                                                                        <td colspan="10" bgcolor="#D9E3EC">
                                                                            <table border="0" cellpadding="0"
                                                                                   cellspacing="0" width="100%">
                                                                                <tr>
                                                                                    <td width="4%"></td>
                                                                                    <td>
                                                                                        <table width="100%" border="0"
                                                                                               cellspacing="0">
                                                                                            <?
                                                                                            $pintarCabeceraNivel2 = true;
                                                                                            while ($rowFamMatNivel2 = $bd->SigReg($resFamMatNivel2)):
                                                                                                ?>
                                                                                                <?
                                                                                                if ($pintarCabeceraNivel2 == true):
                                                                                                    //LO DESACTIVAMOS
                                                                                                    $pintarCabeceraNivel2 = false; ?>
                                                                                                    <tr>
                                                                                                        <td width="2%"
                                                                                                            align='left'
                                                                                                            valign="middle"
                                                                                                            bgcolor='#d9e3ec'
                                                                                                            class="copyright"
                                                                                                            style="white-space:nowrap">

                                                                                                        </td>
                                                                                                        <td width="5%"
                                                                                                            align='left'
                                                                                                            valign="middle"
                                                                                                            bgcolor='#d9e3ec'
                                                                                                            class="copyright"
                                                                                                            style="white-space:nowrap">
                                                                                                            &nbsp;<?= $auxiliar->traduce("Id", $administrador->ID_IDIOMA) ?>
                                                                                                            &nbsp;</td>
                                                                                                        <td width="93%"
                                                                                                            align='left'
                                                                                                            valign="middle"
                                                                                                            bgcolor='#d9e3ec'
                                                                                                            class="copyright">
                                                                                                            &nbsp;<?= $auxiliar->traduce("Familia", $administrador->ID_IDIOMA) ?>
                                                                                                            &nbsp;</td>

                                                                                                    </tr>
                                                                                                <? endif; ?>

                                                                                                <tr bgcolor='#d9e3ec'
                                                                                                    id="Familia_<?= $rowFamMatNivel2->ID2 ?>"
                                                                                                    onmouseover="colorearFilas('Familia_<?= $rowFamMatNivel2->ID2 ?>');return false;"
                                                                                                    onmouseout="descolorearFilas('Familia_<?= $rowFamMatNivel2->ID2 ?>');return false;">
                                                                                                    <td height="18"
                                                                                                        align='center'
                                                                                                        valign="middle"
                                                                                                        bgcolor='#FFFFFF'
                                                                                                        class="copyright">
                                                                                                        <? $sqlHijasNivel3 = "SELECT ID_FAMILIA_MATERIAL FROM FAMILIA_MATERIAL WHERE ID_FAMILIA_MATERIAL_PADRE = $rowFamMatNivel2->ID2";
                                                                                                        $resHijasNivel3 = $bd->ExecSQL($sqlHijasNivel3);
                                                                                                        if($bd->NumRegs($resHijasNivel3) > 0):
                                                                                                        ?>
                                                                                                        <img
                                                                                                                id="ContraerNivel3_<?=$rowFamMatNivel2->ID2?>"
                                                                                                                width="12"
                                                                                                                src='<?= $pathRaiz; ?>imagenes/collapse.png'
                                                                                                                title="<?= $auxiliar->traduce("Contraer", $administrador->ID_IDIOMA) ?>"
                                                                                                                style='vertical-align: middle; padding-top: 2px; <?=trim( (string)$txNombreFamilia) == "" ? 'display: none;' : ''?> cursor: pointer'
                                                                                                                onClick="visualizacionNivel('Nivel3_<?=$rowFamMatNivel2->ID2?>'); return false;">
                                                                                                        <img
                                                                                                                id="ExpandirNivel3_<?=$rowFamMatNivel2->ID2?>"
                                                                                                                width="12"
                                                                                                                src='<?= $pathRaiz; ?>imagenes/expand.png'
                                                                                                                title="<?= $auxiliar->traduce("Expandir", $administrador->ID_IDIOMA) ?>"
                                                                                                                style='vertical-align: middle; padding-top: 2px;<?=trim( (string)$txNombreFamilia) != "" ? 'display: none;' : ''?> cursor: pointer'
                                                                                                                onClick="visualizacionNivel('Nivel3_<?=$rowFamMatNivel2->ID2?>'); return false;">
                                                                                                        <?endif;?>
                                                                                                    </td>
                                                                                                    <td height="18"
                                                                                                        align='left'
                                                                                                        valign="middle"
                                                                                                        bgcolor='#FFFFFF'
                                                                                                        class="copyright">
                                                                                                        &nbsp;
                                                                                                        <a href="#"
                                                                                                           onClick="EstablecerValorNuevo(<?= $rowFamMatNivel2->ID2 ?>,'<? echo $administrador->ID_IDIOMA=="ESP" ? $rowFamMatNivel2->NIVEL2 : $rowFamMatNivel2->NIVEL2_ENG; ?>','<?= $AlmacenarId ?>')"
                                                                                                           class="enlaceceldas">
                                                                                                            <? echo $rowFamMatNivel2->ID2; ?></a>
                                                                                                    </td>
                                                                                                    <td height="18"
                                                                                                        align='left'
                                                                                                        valign="middle"
                                                                                                        bgcolor='#FFFFFF'
                                                                                                        class="copyright">
                                                                                                        <a href="#"
                                                                                                                             onClick="EstablecerValorNuevo(<?= $rowFamMatNivel2->ID2 ?>,'<? echo $administrador->ID_IDIOMA=="ESP" ? $rowFamMatNivel2->NIVEL2 : $rowFamMatNivel2->NIVEL2_ENG; ?>','<?= $AlmacenarId ?>')"
                                                                                                                             class="enlaceceldas">
                                                                                                            &nbsp;<? echo $administrador->ID_IDIOMA=="ESP" ? $rowFamMatNivel2->NIVEL2 : $rowFamMatNivel2->NIVEL2_ENG; ?></a>
                                                                                                        &nbsp;</td>
                                                                                                </tr>
                                                                                                <tr>
                                                                                                    <td colspan="30" bgcolor="#D9E3EC">
                                                                                                        <table border="0" cellpadding="0"
                                                                                                               cellspacing="0" width="100%">
                                                                                                            <tr>
                                                                                                                <td width="4%"></td>
                                                                                                                <td>
                                                                                                                    <table width="100%" border="0"
                                                                                                                           cellspacing="0">

                                                                                                                    </table>
                                                                                                                </td>
                                                                                                            </tr>
                                                                                                        </table>
                                                                                                    </td>
                                                                                                </tr>
                                                                                                <!--BUSQUEDA A NIVEL DE ETAPA-->
                                                                                                <?
                                                                                                if ($idPadreVisualizacion == $row->ID_FAMILIA_MATERIAL || trim( (string)$txNombreFamilia) != ""):

                                                                                                    //BUSCAMOS LOS ALMACENES DE INSTALACION
                                                                                                    $sqlFamMatNivel3 = "SELECT FM_1.ID_FAMILIA_MATERIAL,FM_1.NIVEL_FAMILIA,FM_1.ID_FAMILIA_MATERIAL_PADRE,FM_1.NOMBRE_FAMILIA AS PADRE, FM_2.ID_FAMILIA_MATERIAL AS ID2, FM_2.NOMBRE_FAMILIA AS NIVEL2, FM_3.ID_FAMILIA_MATERIAL AS ID3, FM_3.NOMBRE_FAMILIA AS NIVEL3, FM_4.NOMBRE_FAMILIA AS NIVEL4, FM_5.NOMBRE_FAMILIA AS NIVEL5, FM_6.NOMBRE_FAMILIA AS NIVEL6, FM_7.NOMBRE_FAMILIA AS NIVEL7, FM_8.NOMBRE_FAMILIA AS NIVEL8, FM_9.NOMBRE_FAMILIA AS NIVEL9,FM_1.NOMBRE_FAMILIA_ENG AS PADRE_ENG, FM_2.NOMBRE_FAMILIA_ENG AS NIVEL2_ENG, FM_3.NOMBRE_FAMILIA_ENG AS NIVEL3_ENG, FM_4.NOMBRE_FAMILIA_ENG AS NIVEL4_ENG, FM_5.NOMBRE_FAMILIA_ENG AS NIVEL5_ENG, FM_6.NOMBRE_FAMILIA_ENG AS NIVEL6_ENG, FM_7.NOMBRE_FAMILIA_ENG AS NIVEL7_ENG, FM_8.NOMBRE_FAMILIA_ENG AS NIVEL8_ENG, FM_9.NOMBRE_FAMILIA_ENG AS NIVEL9_ENG
FROM FAMILIA_MATERIAL FM_1 
	LEFT JOIN FAMILIA_MATERIAL FM_2 ON FM_1.ID_FAMILIA_MATERIAL = FM_2.ID_FAMILIA_MATERIAL_PADRE
	LEFT JOIN FAMILIA_MATERIAL FM_3 ON FM_2.ID_FAMILIA_MATERIAL = FM_3.ID_FAMILIA_MATERIAL_PADRE
	LEFT JOIN FAMILIA_MATERIAL FM_4 ON FM_3.ID_FAMILIA_MATERIAL = FM_4.ID_FAMILIA_MATERIAL_PADRE
	LEFT JOIN FAMILIA_MATERIAL FM_5 ON FM_4.ID_FAMILIA_MATERIAL = FM_5.ID_FAMILIA_MATERIAL_PADRE
	LEFT JOIN FAMILIA_MATERIAL FM_6 ON FM_5.ID_FAMILIA_MATERIAL = FM_6.ID_FAMILIA_MATERIAL_PADRE
	LEFT JOIN FAMILIA_MATERIAL FM_7 ON FM_6.ID_FAMILIA_MATERIAL = FM_7.ID_FAMILIA_MATERIAL_PADRE
	LEFT JOIN FAMILIA_MATERIAL FM_8 ON FM_7.ID_FAMILIA_MATERIAL = FM_8.ID_FAMILIA_MATERIAL_PADRE
	LEFT JOIN FAMILIA_MATERIAL FM_9 ON FM_8.ID_FAMILIA_MATERIAL = FM_9.ID_FAMILIA_MATERIAL_PADRE
	WHERE (FM_1.NIVEL_FAMILIA = '1')  AND (FM_2.NIVEL_FAMILIA = '2' OR FM_2.NIVEL_FAMILIA IS NULL) AND (FM_3.NIVEL_FAMILIA = '3' OR FM_3.NIVEL_FAMILIA IS NULL) AND (FM_4.NIVEL_FAMILIA = '4' OR FM_4.NIVEL_FAMILIA IS NULL) AND (FM_5.NIVEL_FAMILIA = '5' OR FM_5.NIVEL_FAMILIA IS NULL) AND (FM_6.NIVEL_FAMILIA = '6' OR FM_6.NIVEL_FAMILIA IS NULL) AND (FM_7.NIVEL_FAMILIA = '7' OR FM_7.NIVEL_FAMILIA IS NULL) AND (FM_8.NIVEL_FAMILIA = '8' OR FM_8.NIVEL_FAMILIA IS NULL) AND (FM_9.NIVEL_FAMILIA = '9' OR FM_9.NIVEL_FAMILIA IS NULL) AND FM_3.ID_FAMILIA_MATERIAL_PADRE = '".$rowFamMatNivel2->ID2."' $whereFamilia
	GROUP BY NIVEL3";

                                                                                                    $resFamMatNivel3 = $bd->ExecSQL($sqlFamMatNivel3);
                                                                                                    $numFamMatNivel3 = $bd->NumRegs($resFamMatNivel3);

                                                                                                    ?>
                                                                                                    <tr class="Nivel3_<?=$rowFamMatNivel2->ID2?>" style="<?=trim( (string)$txNombreFamilia) == "" ? 'display: none;' : ''?>">
                                                                                                        <td colspan="10"
                                                                                                            bgcolor="#D9E3EC">
                                                                                                            <table
                                                                                                                    border="0"
                                                                                                                    cellpadding="0"
                                                                                                                    cellspacing="0"
                                                                                                                    width="100%">
                                                                                                                <tr>
                                                                                                                    <td width="4%"></td>
                                                                                                                    <td>
                                                                                                                        <table
                                                                                                                                width="100%"
                                                                                                                                border="0"
                                                                                                                                cellspacing="0">
                                                                                                                            <?
                                                                                                                            $pintarCabeceraNivel3 = true;
                                                                                                                            while ($rowFamMatNivel3 = $bd->SigReg($resFamMatNivel3)):
                                                                                                                                //ACTIVAMOS EL PINTAR LA CABECERA DEL PADRE
                                                                                                                                //$pintarCabeceraNivel2 = true;
                                                                                                                                ?>
                                                                                                                                <?
                                                                                                                                if ($pintarCabeceraNivel3 == true):
                                                                                                                                    //LO DESACTIVAMOS
                                                                                                                                    $pintarCabeceraNivel3 = false; ?>
                                                                                                                                    <tr>
                                                                                                                                        <td width="2%"
                                                                                                                                            align='left'
                                                                                                                                            valign="middle"
                                                                                                                                            bgcolor='#d9e3ec'
                                                                                                                                            class="copyright"
                                                                                                                                            style="white-space:nowrap">

                                                                                                                                        </td>
                                                                                                                                        <td align='left'
                                                                                                                                            valign="middle"
                                                                                                                                            width="5%"
                                                                                                                                            bgcolor='#d9e3ec'
                                                                                                                                            class="copyright">
                                                                                                                                            &nbsp;<?= $auxiliar->traduce("Id", $administrador->ID_IDIOMA) ?>
                                                                                                                                            &nbsp;</td>
                                                                                                                                        <td align='left'
                                                                                                                                            valign="middle"
                                                                                                                                            width="93%"
                                                                                                                                            bgcolor='#d9e3ec'
                                                                                                                                            class="copyright"
                                                                                                                                            title="<?= $auxiliar->traduce("Familia", $administrador->ID_IDIOMA) ?>">
                                                                                                                                            &nbsp;<?= $auxiliar->traduce("Familia", $administrador->ID_IDIOMA) ?>
                                                                                                                                            &nbsp;</td>
                                                                                                                                    </tr>
                                                                                                                                <? endif; ?>
                                                                                                                                <tr bgcolor='#d9e3ec'
                                                                                                                                    id="Familia_<?= $rowFamMatNivel3->ID3 ?>"
                                                                                                                                    onmouseover="colorearFilas('Familia_<?= $rowFamMatNivel3->ID3 ?>');return false;"
                                                                                                                                    onmouseout="descolorearFilas('Familia_<?= $rowFamMatNivel3->ID3 ?>');return false;">
                                                                                                                                    <td height="18"
                                                                                                                                        align='center'
                                                                                                                                        valign="middle"
                                                                                                                                        bgcolor='#FFFFFF'
                                                                                                                                        class="copyright">
                                                                                                                                        <? $sqlHijasNivel4 = "SELECT ID_FAMILIA_MATERIAL FROM FAMILIA_MATERIAL WHERE ID_FAMILIA_MATERIAL_PADRE = $rowFamMatNivel3->ID3";
                                                                                                                                        $resHijasNivel4 = $bd->ExecSQL($sqlHijasNivel4);
                                                                                                                                        if($bd->NumRegs($resHijasNivel4) > 0):
                                                                                                                                        ?>
                                                                                                                                        <img
                                                                                                                                                id="ContraerNivel4_<?=$rowFamMatNivel3->ID3?>"
                                                                                                                                                width="12"
                                                                                                                                                src='<?= $pathRaiz; ?>imagenes/collapse.png'
                                                                                                                                                title="<?= $auxiliar->traduce("Contraer", $administrador->ID_IDIOMA) ?>"
                                                                                                                                                style='vertical-align: middle; padding-top: 2px; <?=trim( (string)$txNombreFamilia) == "" ? 'display: none;' : ''?>; cursor: pointer'
                                                                                                                                                onClick="visualizacionNivel('Nivel4_<?=$rowFamMatNivel3->ID3?>'); return false;">
                                                                                                                                        <img
                                                                                                                                                id="ExpandirNivel4_<?=$rowFamMatNivel3->ID3?>"
                                                                                                                                                width="12"
                                                                                                                                                src='<?= $pathRaiz; ?>imagenes/expand.png'
                                                                                                                                                title="<?= $auxiliar->traduce("Expandir", $administrador->ID_IDIOMA) ?>"
                                                                                                                                                style='vertical-align: middle; padding-top: 2px; <?=trim( (string)$txNombreFamilia) != "" ? 'display: none;' : ''?> cursor: pointer'
                                                                                                                                                onClick="visualizacionNivel('Nivel4_<?=$rowFamMatNivel3->ID3?>'); return false;">
                                                                                                                                        <?endif;?>
                                                                                                                                    </td>
                                                                                                                                    <td height="18"
                                                                                                                                        align='left'
                                                                                                                                        valign="middle"
                                                                                                                                        bgcolor='#FFFFFF'
                                                                                                                                        class="copyright">
                                                                                                                                        &nbsp;<a href="#"
                                                                                                                                                 onClick="EstablecerValorNuevo(<?= $rowFamMatNivel3->ID3 ?>,'<? echo $administrador->ID_IDIOMA=="ESP" ? $rowFamMatNivel3->NIVEL3 : $rowFamMatNivel3->NIVEL3_ENG;?>','<?= $AlmacenarId ?>')"
                                                                                                                                                 class="enlaceceldas">

                                                                                                                                            <? echo $rowFamMatNivel3->ID3; ?></a>
                                                                                                                                    </td>
                                                                                                                                    <td height="18"
                                                                                                                                        align='left'
                                                                                                                                        valign="middle"
                                                                                                                                        bgcolor='#FFFFFF'
                                                                                                                                        class="copyright">
                                                                                                                                        <a href="#"
                                                                                                                                           onClick="EstablecerValorNuevo(<?= $rowFamMatNivel3->ID3 ?>,'<? echo $administrador->ID_IDIOMA=="ESP" ? $rowFamMatNivel3->NIVEL3 : $rowFamMatNivel3->NIVEL3_ENG; ?>','<?= $AlmacenarId ?>')"
                                                                                                                                           class="enlaceceldas">
                                                                                                                                            &nbsp;<? echo $administrador->ID_IDIOMA=="ESP" ? $rowFamMatNivel3->NIVEL3 : $rowFamMatNivel3->NIVEL3_ENG; ?></a>
                                                                                                                                        &nbsp;</td>
                                                                                                                                </tr>
                                                                                                                                <tr>
                                                                                                                                    <td colspan="30" bgcolor="#D9E3EC">
                                                                                                                                        <table border="0" cellpadding="0"
                                                                                                                                               cellspacing="0" width="100%">
                                                                                                                                            <tr>
                                                                                                                                                <td width="4%"></td>
                                                                                                                                                <td>
                                                                                                                                                    <table width="100%" border="0"
                                                                                                                                                           cellspacing="0">

                                                                                                                                                    </table>
                                                                                                                                                </td>
                                                                                                                                            </tr>
                                                                                                                                        </table>
                                                                                                                                    </td>
                                                                                                                                </tr>
                                                                                                                                <!--BUSQUEDA SUbETAPA-->
                                                                                                                                <?
                                                                                                                                if ($idPadreVisualizacion == $row->ID_FAMILIA_MATERIAL || trim( (string)$txNombreFamilia) != ""):

                                                                                                                                    //BUSCAMOS LOS ALMACENES DE INSTALACION
                                                                                                                                    $sqlFamMatNivel4 = "SELECT FM_1.ID_FAMILIA_MATERIAL,FM_1.NIVEL_FAMILIA,FM_1.ID_FAMILIA_MATERIAL_PADRE,FM_1.NOMBRE_FAMILIA AS PADRE, FM_2.ID_FAMILIA_MATERIAL AS ID2, FM_2.NOMBRE_FAMILIA AS NIVEL2, FM_3.ID_FAMILIA_MATERIAL AS ID3, FM_3.NOMBRE_FAMILIA AS NIVEL3, FM_4.ID_FAMILIA_MATERIAL AS ID4, FM_4.NOMBRE_FAMILIA AS NIVEL4, FM_5.NOMBRE_FAMILIA AS NIVEL5, FM_6.NOMBRE_FAMILIA AS NIVEL6, FM_7.NOMBRE_FAMILIA AS NIVEL7, FM_8.NOMBRE_FAMILIA AS NIVEL8, FM_9.NOMBRE_FAMILIA AS NIVEL9,FM_1.NOMBRE_FAMILIA_ENG AS PADRE_ENG, FM_2.NOMBRE_FAMILIA_ENG AS NIVEL2_ENG, FM_3.NOMBRE_FAMILIA_ENG AS NIVEL3_ENG, FM_4.NOMBRE_FAMILIA_ENG AS NIVEL4_ENG, FM_5.NOMBRE_FAMILIA_ENG AS NIVEL5_ENG, FM_6.NOMBRE_FAMILIA_ENG AS NIVEL6_ENG, FM_7.NOMBRE_FAMILIA_ENG AS NIVEL7_ENG, FM_8.NOMBRE_FAMILIA_ENG AS NIVEL8_ENG, FM_9.NOMBRE_FAMILIA_ENG AS NIVEL9_ENG
FROM FAMILIA_MATERIAL FM_1 
	LEFT JOIN FAMILIA_MATERIAL FM_2 ON FM_1.ID_FAMILIA_MATERIAL = FM_2.ID_FAMILIA_MATERIAL_PADRE
	LEFT JOIN FAMILIA_MATERIAL FM_3 ON FM_2.ID_FAMILIA_MATERIAL = FM_3.ID_FAMILIA_MATERIAL_PADRE
	LEFT JOIN FAMILIA_MATERIAL FM_4 ON FM_3.ID_FAMILIA_MATERIAL = FM_4.ID_FAMILIA_MATERIAL_PADRE
	LEFT JOIN FAMILIA_MATERIAL FM_5 ON FM_4.ID_FAMILIA_MATERIAL = FM_5.ID_FAMILIA_MATERIAL_PADRE
	LEFT JOIN FAMILIA_MATERIAL FM_6 ON FM_5.ID_FAMILIA_MATERIAL = FM_6.ID_FAMILIA_MATERIAL_PADRE
	LEFT JOIN FAMILIA_MATERIAL FM_7 ON FM_6.ID_FAMILIA_MATERIAL = FM_7.ID_FAMILIA_MATERIAL_PADRE
	LEFT JOIN FAMILIA_MATERIAL FM_8 ON FM_7.ID_FAMILIA_MATERIAL = FM_8.ID_FAMILIA_MATERIAL_PADRE
	LEFT JOIN FAMILIA_MATERIAL FM_9 ON FM_8.ID_FAMILIA_MATERIAL = FM_9.ID_FAMILIA_MATERIAL_PADRE
	WHERE (FM_1.NIVEL_FAMILIA = '1')  AND (FM_2.NIVEL_FAMILIA = '2' OR FM_2.NIVEL_FAMILIA IS NULL) AND (FM_3.NIVEL_FAMILIA = '3' OR FM_3.NIVEL_FAMILIA IS NULL) AND (FM_4.NIVEL_FAMILIA = '4' OR FM_4.NIVEL_FAMILIA IS NULL) AND (FM_5.NIVEL_FAMILIA = '5' OR FM_5.NIVEL_FAMILIA IS NULL) AND (FM_6.NIVEL_FAMILIA = '6' OR FM_6.NIVEL_FAMILIA IS NULL) AND (FM_7.NIVEL_FAMILIA = '7' OR FM_7.NIVEL_FAMILIA IS NULL) AND (FM_8.NIVEL_FAMILIA = '8' OR FM_8.NIVEL_FAMILIA IS NULL) AND (FM_9.NIVEL_FAMILIA = '9' OR FM_9.NIVEL_FAMILIA IS NULL) AND FM_4.ID_FAMILIA_MATERIAL_PADRE = '".$rowFamMatNivel3->ID3."' $whereFamilia
	GROUP BY NIVEL4";

                                                                                                                                    $resFamMatNivel4 = $bd->ExecSQL($sqlFamMatNivel4);
                                                                                                                                    $numFamMatNivel4 = $bd->NumRegs($resFamMatNivel4);
                                                                                                                                    ?>
                                                                                                                                    <tr class="Nivel4_<?=$rowFamMatNivel3->ID3?>" style="<?=trim( (string)$txNombreFamilia) == "" ? 'display: none;' : ''?>">
                                                                                                                                        <td colspan="30"
                                                                                                                                            bgcolor="#D9E3EC">
                                                                                                                                            <table
                                                                                                                                                    border="0"
                                                                                                                                                    cellpadding="0"
                                                                                                                                                    cellspacing="0"
                                                                                                                                                    width="100%">
                                                                                                                                                <tr>
                                                                                                                                                    <td width="4%"></td>
                                                                                                                                                    <td>
                                                                                                                                                        <table
                                                                                                                                                                width="100%"
                                                                                                                                                                border="0"
                                                                                                                                                                cellspacing="0">
                                                                                                                                                            <?
                                                                                                                                                            $pintarCabeceraNivel4 = true;
                                                                                                                                                            while ($rowFamMatNivel4 = $bd->SigReg($resFamMatNivel4)):
                                                                                                                                                                //ACTIVAMOS EL PINTAR LA CABECERA DEL PADRE
                                                                                                                                                                //$pintarCabeceraNivel3 = true;
                                                                                                                                                                ?>

                                                                                                                                                                <?
                                                                                                                                                                if ($pintarCabeceraNivel4 == true):
                                                                                                                                                                    //LO DESACTIVAMOS
                                                                                                                                                                    $pintarCabeceraNivel4 = false; ?>
                                                                                                                                                                    <tr>
                                                                                                                                                                        <td width="2%"
                                                                                                                                                                            align='left'
                                                                                                                                                                            valign="middle"
                                                                                                                                                                            bgcolor='#d9e3ec'
                                                                                                                                                                            class="copyright"
                                                                                                                                                                            style="white-space:nowrap">

                                                                                                                                                                        </td>
                                                                                                                                                                        <td align='left'
                                                                                                                                                                            width="5%"
                                                                                                                                                                            valign="middle"
                                                                                                                                                                            bgcolor='#d9e3ec'
                                                                                                                                                                            class="copyright">
                                                                                                                                                                            &nbsp;<?= $auxiliar->traduce("Id", $administrador->ID_IDIOMA) ?>
                                                                                                                                                                            &nbsp;</td>
                                                                                                                                                                        <td align='left'
                                                                                                                                                                            valign="middle"
                                                                                                                                                                            width="93%"
                                                                                                                                                                            bgcolor='#d9e3ec'
                                                                                                                                                                            class="copyright">
                                                                                                                                                                            &nbsp;<?= $auxiliar->traduce("Familia", $administrador->ID_IDIOMA) ?>
                                                                                                                                                                            &nbsp;</td>
                                                                                                                                                                    </tr>
                                                                                                                                                                <? endif; ?>
                                                                                                                                                                <tr bgcolor='#d9e3ec'
                                                                                                                                                                    id="Familia_<?= $rowFamMatNivel4->ID4 ?>"
                                                                                                                                                                    onmouseover="colorearFilas('Familia_<?= $rowFamMatNivel4->ID4 ?>');return false;"
                                                                                                                                                                    onmouseout="descolorearFilas('Familia_<?= $rowFamMatNivel4->ID4 ?>');return false;">
                                                                                                                                                                    <td height="18"
                                                                                                                                                                        align='center'
                                                                                                                                                                        valign="middle"
                                                                                                                                                                        bgcolor='#FFFFFF'
                                                                                                                                                                        class="copyright">
                                                                                                                                                                        <? $sqlHijasNivel5 = "SELECT ID_FAMILIA_MATERIAL FROM FAMILIA_MATERIAL WHERE ID_FAMILIA_MATERIAL_PADRE = $rowFamMatNivel4->ID4";
                                                                                                                                                                        $resHijasNivel5 = $bd->ExecSQL($sqlHijasNivel5);
                                                                                                                                                                        if($bd->NumRegs($resHijasNivel5) > 0):
                                                                                                                                                                        ?>
                                                                                                                                                                        <img
                                                                                                                                                                                id="ContraerNivel5_<?=$rowFamMatNivel4->ID4?>"
                                                                                                                                                                                width="12"
                                                                                                                                                                                src='<?= $pathRaiz; ?>imagenes/collapse.png'
                                                                                                                                                                                title="<?= $auxiliar->traduce("Contraer", $administrador->ID_IDIOMA) ?>"
                                                                                                                                                                                style='vertical-align: middle; padding-top: 2px; <?=trim( (string)$txNombreFamilia) == "" ? 'display: none;' : ''?> cursor: pointer'
                                                                                                                                                                                onClick="visualizacionNivel('Nivel5_<?=$rowFamMatNivel4->ID4?>'); return false;">
                                                                                                                                                                        <img
                                                                                                                                                                                id="ExpandirNivel5_<?=$rowFamMatNivel4->ID4?>"
                                                                                                                                                                                width="12"
                                                                                                                                                                                src='<?= $pathRaiz; ?>imagenes/expand.png'
                                                                                                                                                                                title="<?= $auxiliar->traduce("Expandir", $administrador->ID_IDIOMA) ?>"
                                                                                                                                                                                style='vertical-align: middle; padding-top: 2px; <?=trim( (string)$txNombreFamilia) != "" ? 'display: none;' : ''?> cursor: pointer'
                                                                                                                                                                                onClick="visualizacionNivel('Nivel5_<?=$rowFamMatNivel4->ID4?>'); return false;">
                                                                                                                                                                        <?endif;?>
                                                                                                                                                                    </td>
                                                                                                                                                                    <td height="18"
                                                                                                                                                                        align='left'
                                                                                                                                                                        valign="middle"
                                                                                                                                                                        bgcolor='#FFFFFF'
                                                                                                                                                                        class="copyright">
                                                                                                                                                                        &nbsp;<a href="#"
                                                                                                                                                                                 onClick="EstablecerValorNuevo(<?= $rowFamMatNivel4->ID4 ?>,'<? echo $administrador->ID_IDIOMA=="ESP" ? $rowFamMatNivel4->NIVEL4 : $rowFamMatNivel4->NIVEL4_ENG; ?>','<?= $AlmacenarId ?>')"
                                                                                                                                                                                 class="enlaceceldas">
                                                                                                                                                                            <? echo $rowFamMatNivel4->ID4; ?></a>
                                                                                                                                                                    </td>
                                                                                                                                                                    <td height="18"
                                                                                                                                                                        align='left'
                                                                                                                                                                        valign="middle"
                                                                                                                                                                        bgcolor='#FFFFFF'
                                                                                                                                                                        class="copyright">
                                                                                                                                                                        <a href="#"
                                                                                                                                                                           onClick="EstablecerValorNuevo(<?= $rowFamMatNivel4->ID4 ?>,'<? echo $administrador->ID_IDIOMA=="ESP" ? $rowFamMatNivel4->NIVEL4 : $rowFamMatNivel4->NIVEL4_ENG; ?>','<?= $AlmacenarId ?>')"
                                                                                                                                                                           class="enlaceceldas">
                                                                                                                                                                            &nbsp;<? echo $administrador->ID_IDIOMA=="ESP" ? $rowFamMatNivel4->NIVEL4 : $rowFamMatNivel4->NIVEL4_ENG; ?></a>
                                                                                                                                                                        &nbsp;</td>
                                                                                                                                                                </tr>
                                                                                                                                                                <tr>
                                                                                                                                                                    <td colspan="30" bgcolor="#D9E3EC">
                                                                                                                                                                        <table border="0" cellpadding="0"
                                                                                                                                                                               cellspacing="0" width="100%">
                                                                                                                                                                            <tr>
                                                                                                                                                                                <td width="4%"></td>
                                                                                                                                                                                <td>
                                                                                                                                                                                    <table width="100%" border="0"
                                                                                                                                                                                           cellspacing="0">

                                                                                                                                                                                    </table>
                                                                                                                                                                                </td>
                                                                                                                                                                            </tr>
                                                                                                                                                                        </table>
                                                                                                                                                                    </td>
                                                                                                                                                                </tr>

                                                                                                                                                                <!--BUSQUEDA A NIVEL DE FASE-->
                                                                                                                                                                <?
                                                                                                                                                                if ($idPadreVisualizacion == $row->ID_FAMILIA_MATERIAL || trim( (string)$txNombreFamilia) != ""):

                                                                                                                                                                    //BUSCAMOS LOS ALMACENES DE INSTALACION
                                                                                                                                                                    $sqlFamMatNivel5 = "SELECT FM_1.ID_FAMILIA_MATERIAL,FM_1.NIVEL_FAMILIA,FM_1.ID_FAMILIA_MATERIAL_PADRE,FM_1.NOMBRE_FAMILIA AS PADRE, FM_2.ID_FAMILIA_MATERIAL AS ID2, FM_2.NOMBRE_FAMILIA AS NIVEL2, FM_3.ID_FAMILIA_MATERIAL AS ID3, FM_3.NOMBRE_FAMILIA AS NIVEL3, FM_4.ID_FAMILIA_MATERIAL AS ID4, FM_4.NOMBRE_FAMILIA AS NIVEL4, FM_5.ID_FAMILIA_MATERIAL AS ID5, FM_5.NOMBRE_FAMILIA AS NIVEL5, FM_6.NOMBRE_FAMILIA AS NIVEL6, FM_7.NOMBRE_FAMILIA AS NIVEL7, FM_8.NOMBRE_FAMILIA AS NIVEL8, FM_9.NOMBRE_FAMILIA AS NIVEL9,FM_1.NOMBRE_FAMILIA_ENG AS PADRE_ENG, FM_2.NOMBRE_FAMILIA_ENG AS NIVEL2_ENG, FM_3.NOMBRE_FAMILIA_ENG AS NIVEL3_ENG, FM_4.NOMBRE_FAMILIA_ENG AS NIVEL4_ENG, FM_5.NOMBRE_FAMILIA_ENG AS NIVEL5_ENG, FM_6.NOMBRE_FAMILIA_ENG AS NIVEL6_ENG, FM_7.NOMBRE_FAMILIA_ENG AS NIVEL7_ENG, FM_8.NOMBRE_FAMILIA_ENG AS NIVEL8_ENG, FM_9.NOMBRE_FAMILIA_ENG AS NIVEL9_ENG
FROM FAMILIA_MATERIAL FM_1 
	LEFT JOIN FAMILIA_MATERIAL FM_2 ON FM_1.ID_FAMILIA_MATERIAL = FM_2.ID_FAMILIA_MATERIAL_PADRE
	LEFT JOIN FAMILIA_MATERIAL FM_3 ON FM_2.ID_FAMILIA_MATERIAL = FM_3.ID_FAMILIA_MATERIAL_PADRE
	LEFT JOIN FAMILIA_MATERIAL FM_4 ON FM_3.ID_FAMILIA_MATERIAL = FM_4.ID_FAMILIA_MATERIAL_PADRE
	LEFT JOIN FAMILIA_MATERIAL FM_5 ON FM_4.ID_FAMILIA_MATERIAL = FM_5.ID_FAMILIA_MATERIAL_PADRE
	LEFT JOIN FAMILIA_MATERIAL FM_6 ON FM_5.ID_FAMILIA_MATERIAL = FM_6.ID_FAMILIA_MATERIAL_PADRE
	LEFT JOIN FAMILIA_MATERIAL FM_7 ON FM_6.ID_FAMILIA_MATERIAL = FM_7.ID_FAMILIA_MATERIAL_PADRE
	LEFT JOIN FAMILIA_MATERIAL FM_8 ON FM_7.ID_FAMILIA_MATERIAL = FM_8.ID_FAMILIA_MATERIAL_PADRE
	LEFT JOIN FAMILIA_MATERIAL FM_9 ON FM_8.ID_FAMILIA_MATERIAL = FM_9.ID_FAMILIA_MATERIAL_PADRE
	WHERE (FM_1.NIVEL_FAMILIA = '1')  AND (FM_2.NIVEL_FAMILIA = '2' OR FM_2.NIVEL_FAMILIA IS NULL) AND (FM_3.NIVEL_FAMILIA = '3' OR FM_3.NIVEL_FAMILIA IS NULL) AND (FM_4.NIVEL_FAMILIA = '4' OR FM_4.NIVEL_FAMILIA IS NULL) AND (FM_5.NIVEL_FAMILIA = '5' OR FM_5.NIVEL_FAMILIA IS NULL) AND (FM_6.NIVEL_FAMILIA = '6' OR FM_6.NIVEL_FAMILIA IS NULL) AND (FM_7.NIVEL_FAMILIA = '7' OR FM_7.NIVEL_FAMILIA IS NULL) AND (FM_8.NIVEL_FAMILIA = '8' OR FM_8.NIVEL_FAMILIA IS NULL) AND (FM_9.NIVEL_FAMILIA = '9' OR FM_9.NIVEL_FAMILIA IS NULL) AND FM_5.ID_FAMILIA_MATERIAL_PADRE = '".$rowFamMatNivel4->ID4."' $whereFamilia
	GROUP BY NIVEL5";

                                                                                                                                                                    $resFamMatNivel5 = $bd->ExecSQL($sqlFamMatNivel5);
                                                                                                                                                                    $numFamMatNivel5 = $bd->NumRegs($resFamMatNivel5); ?>

                                                                                                                                                                    <tr class="Nivel5_<?=$rowFamMatNivel4->ID4?>" style="<?=trim( (string)$txNombreFamilia) == "" ? 'display: none;' : ''?>">
                                                                                                                                                                        <td colspan="10"
                                                                                                                                                                            bgcolor="#D9E3EC">
                                                                                                                                                                            <table
                                                                                                                                                                                    border="0"
                                                                                                                                                                                    cellpadding="0"
                                                                                                                                                                                    cellspacing="0"
                                                                                                                                                                                    width="100%">
                                                                                                                                                                                <tr>
                                                                                                                                                                                    <td width="4%"></td>
                                                                                                                                                                                    <td>
                                                                                                                                                                                        <table
                                                                                                                                                                                                width="100%"
                                                                                                                                                                                                border="0"
                                                                                                                                                                                                cellspacing="0">
                                                                                                                                                                                            <?
                                                                                                                                                                                            $pintarCabeceraNivel5 = true;
                                                                                                                                                                                            while ($rowFamMatNivel5 = $bd->SigReg($resFamMatNivel5)):
                                                                                                                                                                                                //ACTIVAMOS EL PINTAR LA CABECERA DEL PADRE
                                                                                                                                                                                                //$pintarCabeceraNivel4 = true;
                                                                                                                                                                                                ?>

                                                                                                                                                                                                <? if ($pintarCabeceraNivel5 == true):
                                                                                                                                                                                                //LO DESACTIVAMOS
                                                                                                                                                                                                $pintarCabeceraNivel5 = false; ?>
                                                                                                                                                                                                <tr>
                                                                                                                                                                                                    <td width="2%"
                                                                                                                                                                                                        align='left'
                                                                                                                                                                                                        valign="middle"
                                                                                                                                                                                                        bgcolor='#d9e3ec'
                                                                                                                                                                                                        class="copyright"
                                                                                                                                                                                                        style="white-space:nowrap">
                                                                                                                                                                                                    <td align='left'
                                                                                                                                                                                                        valign="middle"
                                                                                                                                                                                                        width="5%"
                                                                                                                                                                                                        bgcolor='#d9e3ec'
                                                                                                                                                                                                        class="copyright">
                                                                                                                                                                                                        &nbsp;<?= $auxiliar->traduce("Id", $administrador->ID_IDIOMA) ?>
                                                                                                                                                                                                        &nbsp;</td>
                                                                                                                                                                                                    <td align='left'
                                                                                                                                                                                                        valign="middle"
                                                                                                                                                                                                        bgcolor='#d9e3ec'
                                                                                                                                                                                                        width="93%"
                                                                                                                                                                                                        class="copyright"
                                                                                                                                                                                                        title="<?= $auxiliar->traduce("Familia", $administrador->ID_IDIOMA) ?>">
                                                                                                                                                                                                        &nbsp;<?= $auxiliar->traduce("Familia", $administrador->ID_IDIOMA) ?>
                                                                                                                                                                                                        &nbsp;</td>
                                                                                                                                                                                                </tr>
                                                                                                                                                                                            <? endif; ?>
                                                                                                                                                                                                <tr bgcolor='#d9e3ec'
                                                                                                                                                                                                    id="Familia_<?= $rowFamMatNivel5->ID5 ?>"
                                                                                                                                                                                                    onmouseover="colorearFilas('Familia_<?= $rowFamMatNivel5->ID5 ?>');return false;"
                                                                                                                                                                                                    onmouseout="descolorearFilas('Familia_<?= $rowFamMatNivel5->ID5 ?>');return false;">
                                                                                                                                                                                                    <td height="18"
                                                                                                                                                                                                        align='center'
                                                                                                                                                                                                        valign="middle"
                                                                                                                                                                                                        bgcolor='#FFFFFF'
                                                                                                                                                                                                        class="copyright">
                                                                                                                                                                                                        <? $sqlHijasNivel6 = "SELECT ID_FAMILIA_MATERIAL FROM FAMILIA_MATERIAL WHERE ID_FAMILIA_MATERIAL_PADRE = $rowFamMatNivel5->ID5";
                                                                                                                                                                                                        $resHijasNivel6 = $bd->ExecSQL($sqlHijasNivel6);
                                                                                                                                                                                                        if($bd->NumRegs($resHijasNivel6) > 0):
                                                                                                                                                                                                        ?>
                                                                                                                                                                                                        <img
                                                                                                                                                                                                                id="ContraerNivel6_<?=$rowFamMatNivel5->ID5?>"
                                                                                                                                                                                                                width="12"
                                                                                                                                                                                                                src='<?= $pathRaiz; ?>imagenes/collapse.png'
                                                                                                                                                                                                                title="<?= $auxiliar->traduce("Contraer", $administrador->ID_IDIOMA) ?>"
                                                                                                                                                                                                                style='vertical-align: middle; padding-top: 2px; <?=trim( (string)$txNombreFamilia) == "" ? 'display: none;' : ''?> cursor: pointer'
                                                                                                                                                                                                                onClick="visualizacionNivel('Nivel6_<?=$rowFamMatNivel5->ID5?>'); return false;">
                                                                                                                                                                                                        <img
                                                                                                                                                                                                                id="ExpandirNivel6_<?=$rowFamMatNivel5->ID5?>"
                                                                                                                                                                                                                width="12"
                                                                                                                                                                                                                src='<?= $pathRaiz; ?>imagenes/expand.png'
                                                                                                                                                                                                                title="<?= $auxiliar->traduce("Expandir", $administrador->ID_IDIOMA) ?>"
                                                                                                                                                                                                                style='vertical-align: middle; padding-top: 2px; <?=trim( (string)$txNombreFamilia) != "" ? 'display: none;' : ''?> cursor: pointer'
                                                                                                                                                                                                                onClick="visualizacionNivel('Nivel6_<?=$rowFamMatNivel5->ID5?>'); return false;">
                                                                                                                                                                                                        <?endif;?>
                                                                                                                                                                                                    </td>
                                                                                                                                                                                                    <td height="18"
                                                                                                                                                                                                        align='left'
                                                                                                                                                                                                        valign="middle"
                                                                                                                                                                                                        bgcolor='#FFFFFF'
                                                                                                                                                                                                        class="copyright">
                                                                                                                                                                                                        &nbsp;<a href="#"
                                                                                                                                                                                                                 onClick="EstablecerValorNuevo(<?= $rowFamMatNivel5->ID5 ?>,'<? echo $administrador->ID_IDIOMA=="ESP" ? $rowFamMatNivel5->NIVEL5 : $rowFamMatNivel5->NIVEL5_ENG;?>','<?= $AlmacenarId ?>')"
                                                                                                                                                                                                                 class="enlaceceldas">
                                                                                                                                                                                                            <? echo $rowFamMatNivel5->ID5; ?></a>
                                                                                                                                                                                                    </td>
                                                                                                                                                                                                    <td height="18"
                                                                                                                                                                                                        align='left'
                                                                                                                                                                                                        valign="middle"
                                                                                                                                                                                                        bgcolor='#FFFFFF'
                                                                                                                                                                                                        class="copyright">
                                                                                                                                                                                                        <a href="#"
                                                                                                                                                                                                           onClick="EstablecerValorNuevo(<?= $rowFamMatNivel5->ID5 ?>,'<? echo $administrador->ID_IDIOMA=="ESP" ? $rowFamMatNivel5->NIVEL5 : $rowFamMatNivel5->NIVEL5_ENG;?>','<?= $AlmacenarId ?>')"
                                                                                                                                                                                                           class="enlaceceldas">
                                                                                                                                                                                                            &nbsp;<? echo $administrador->ID_IDIOMA=="ESP" ? $rowFamMatNivel5->NIVEL5 : $rowFamMatNivel5->NIVEL5_ENG;?></a>
                                                                                                                                                                                                        &nbsp;</td>
                                                                                                                                                                                                </tr>
                                                                                                                                                                                                <tr>
                                                                                                                                                                                                    <td colspan="30" bgcolor="#D9E3EC">
                                                                                                                                                                                                        <table border="0" cellpadding="0"
                                                                                                                                                                                                               cellspacing="0" width="100%">
                                                                                                                                                                                                            <tr>
                                                                                                                                                                                                                <td width="4%"></td>
                                                                                                                                                                                                                <td>
                                                                                                                                                                                                                    <table width="100%" border="0"
                                                                                                                                                                                                                           cellspacing="0">

                                                                                                                                                                                                                    </table>
                                                                                                                                                                                                                </td>
                                                                                                                                                                                                            </tr>
                                                                                                                                                                                                        </table>
                                                                                                                                                                                                    </td>
                                                                                                                                                                                                </tr>

                                                                                                                                                                                                <!--BUSQUEDA SUBFASE-->
                                                                                                                                                                                                <?
                                                                                                                                                                                                if ($idPadreVisualizacion == $row->ID_FAMILIA_MATERIAL || trim( (string)$txNombreFamilia) != ""):

                                                                                                                                                                                                    //BUSCAMOS LOS ALMACENES DE INSTALACION
                                                                                                                                                                                                    $sqlFamMatNivel6 = "SELECT FM_1.ID_FAMILIA_MATERIAL,FM_1.NIVEL_FAMILIA,FM_1.ID_FAMILIA_MATERIAL_PADRE,FM_1.NOMBRE_FAMILIA AS PADRE, FM_2.ID_FAMILIA_MATERIAL AS ID2, FM_2.NOMBRE_FAMILIA AS NIVEL2, FM_3.ID_FAMILIA_MATERIAL AS ID3, FM_3.NOMBRE_FAMILIA AS NIVEL3, FM_4.ID_FAMILIA_MATERIAL AS ID4, FM_4.NOMBRE_FAMILIA AS NIVEL4, FM_5.ID_FAMILIA_MATERIAL AS ID5, FM_5.NOMBRE_FAMILIA AS NIVEL5, FM_6.ID_FAMILIA_MATERIAL AS ID6, FM_6.NOMBRE_FAMILIA AS NIVEL6, FM_7.NOMBRE_FAMILIA AS NIVEL7, FM_8.NOMBRE_FAMILIA AS NIVEL8, FM_9.NOMBRE_FAMILIA AS NIVEL9,FM_1.NOMBRE_FAMILIA_ENG AS PADRE_ENG, FM_2.NOMBRE_FAMILIA_ENG AS NIVEL2_ENG, FM_3.NOMBRE_FAMILIA_ENG AS NIVEL3_ENG, FM_4.NOMBRE_FAMILIA_ENG AS NIVEL4_ENG, FM_5.NOMBRE_FAMILIA_ENG AS NIVEL5_ENG, FM_6.NOMBRE_FAMILIA_ENG AS NIVEL6_ENG, FM_7.NOMBRE_FAMILIA_ENG AS NIVEL7_ENG, FM_8.NOMBRE_FAMILIA_ENG AS NIVEL8_ENG, FM_9.NOMBRE_FAMILIA_ENG AS NIVEL9_ENG
FROM FAMILIA_MATERIAL FM_1 
	LEFT JOIN FAMILIA_MATERIAL FM_2 ON FM_1.ID_FAMILIA_MATERIAL = FM_2.ID_FAMILIA_MATERIAL_PADRE
	LEFT JOIN FAMILIA_MATERIAL FM_3 ON FM_2.ID_FAMILIA_MATERIAL = FM_3.ID_FAMILIA_MATERIAL_PADRE
	LEFT JOIN FAMILIA_MATERIAL FM_4 ON FM_3.ID_FAMILIA_MATERIAL = FM_4.ID_FAMILIA_MATERIAL_PADRE
	LEFT JOIN FAMILIA_MATERIAL FM_5 ON FM_4.ID_FAMILIA_MATERIAL = FM_5.ID_FAMILIA_MATERIAL_PADRE
	LEFT JOIN FAMILIA_MATERIAL FM_6 ON FM_5.ID_FAMILIA_MATERIAL = FM_6.ID_FAMILIA_MATERIAL_PADRE
	LEFT JOIN FAMILIA_MATERIAL FM_7 ON FM_6.ID_FAMILIA_MATERIAL = FM_7.ID_FAMILIA_MATERIAL_PADRE
	LEFT JOIN FAMILIA_MATERIAL FM_8 ON FM_7.ID_FAMILIA_MATERIAL = FM_8.ID_FAMILIA_MATERIAL_PADRE
	LEFT JOIN FAMILIA_MATERIAL FM_9 ON FM_8.ID_FAMILIA_MATERIAL = FM_9.ID_FAMILIA_MATERIAL_PADRE
	WHERE (FM_1.NIVEL_FAMILIA = '1')  AND (FM_2.NIVEL_FAMILIA = '2' OR FM_2.NIVEL_FAMILIA IS NULL) AND (FM_3.NIVEL_FAMILIA = '3' OR FM_3.NIVEL_FAMILIA IS NULL) AND (FM_4.NIVEL_FAMILIA = '4' OR FM_4.NIVEL_FAMILIA IS NULL) AND (FM_5.NIVEL_FAMILIA = '5' OR FM_5.NIVEL_FAMILIA IS NULL) AND (FM_6.NIVEL_FAMILIA = '6' OR FM_6.NIVEL_FAMILIA IS NULL) AND (FM_7.NIVEL_FAMILIA = '7' OR FM_7.NIVEL_FAMILIA IS NULL) AND (FM_8.NIVEL_FAMILIA = '8' OR FM_8.NIVEL_FAMILIA IS NULL) AND (FM_9.NIVEL_FAMILIA = '9' OR FM_9.NIVEL_FAMILIA IS NULL) AND FM_6.ID_FAMILIA_MATERIAL_PADRE = '".$rowFamMatNivel5->ID5."' $whereFamilia
	GROUP BY NIVEL6";

                                                                                                                                                                                                    $resFamMatNivel6 = $bd->ExecSQL($sqlFamMatNivel6);
                                                                                                                                                                                                    $numFamMatNivel6 = $bd->NumRegs($resFamMatNivel6);
                                                                                                                                                                                                    ?>
                                                                                                                                                                                                    <tr class="Nivel6_<?=$rowFamMatNivel5->ID5?>" style="<?=trim( (string)$txNombreFamilia) == "" ? 'display: none;' : ''?>">
                                                                                                                                                                                                        <td colspan="30"
                                                                                                                                                                                                            bgcolor="#D9E3EC">
                                                                                                                                                                                                            <table
                                                                                                                                                                                                                    border="0"
                                                                                                                                                                                                                    cellpadding="0"
                                                                                                                                                                                                                    cellspacing="0"
                                                                                                                                                                                                                    width="100%">
                                                                                                                                                                                                                <tr>
                                                                                                                                                                                                                    <td width="4%"></td>
                                                                                                                                                                                                                    <td>
                                                                                                                                                                                                                        <table
                                                                                                                                                                                                                                width="100%"
                                                                                                                                                                                                                                border="0"
                                                                                                                                                                                                                                cellspacing="0">
                                                                                                                                                                                                                            <?
                                                                                                                                                                                                                            $pintarCabeceraNivel6 = true;
                                                                                                                                                                                                                            while ($rowFamMatNivel6 = $bd->SigReg($resFamMatNivel6)):
                                                                                                                                                                                                                                //ACTIVAMOS EL PINTAR LA CABECERA DEL PADRE
                                                                                                                                                                                                                                //$pintarCabeceraNivel5 = true;
                                                                                                                                                                                                                                ?>
                                                                                                                                                                                                                                <?
                                                                                                                                                                                                                                if ($pintarCabeceraNivel6 == true):
                                                                                                                                                                                                                                    //LO DESACTIVAMOS
                                                                                                                                                                                                                                    $pintarCabeceraNivel6 = false; ?>
                                                                                                                                                                                                                                    <tr>
                                                                                                                                                                                                                                        <td width="2%"
                                                                                                                                                                                                                                            align='left'
                                                                                                                                                                                                                                            valign="middle"
                                                                                                                                                                                                                                            bgcolor='#d9e3ec'
                                                                                                                                                                                                                                            class="copyright"
                                                                                                                                                                                                                                            style="white-space:nowrap">
                                                                                                                                                                                                                                        <td align='left'
                                                                                                                                                                                                                                            width="5%"
                                                                                                                                                                                                                                            valign="middle"
                                                                                                                                                                                                                                            bgcolor='#d9e3ec'
                                                                                                                                                                                                                                            class="copyright">
                                                                                                                                                                                                                                            &nbsp;<?= $auxiliar->traduce("Id", $administrador->ID_IDIOMA) ?>
                                                                                                                                                                                                                                            &nbsp;</td>
                                                                                                                                                                                                                                        <td align='left'
                                                                                                                                                                                                                                            valign="middle"
                                                                                                                                                                                                                                            width="93%"
                                                                                                                                                                                                                                            bgcolor='#d9e3ec'
                                                                                                                                                                                                                                            class="copyright">
                                                                                                                                                                                                                                            &nbsp;<?= $auxiliar->traduce("Familia", $administrador->ID_IDIOMA) ?>
                                                                                                                                                                                                                                            &nbsp;</td>
                                                                                                                                                                                                                                    </tr>
                                                                                                                                                                                                                                <? endif; ?>
                                                                                                                                                                                                                                <tr bgcolor='#d9e3ec'
                                                                                                                                                                                                                                    id="Familia_<?= $rowFamMatNivel6->ID6 ?>"
                                                                                                                                                                                                                                    onmouseover="colorearFilas('Familia_<?= $rowFamMatNivel6->ID6 ?>');return false;"
                                                                                                                                                                                                                                    onmouseout="descolorearFilas('Familia_<?= $rowFamMatNivel6->ID6 ?>');return false;">
                                                                                                                                                                                                                                    <td height="18"
                                                                                                                                                                                                                                        align='center'
                                                                                                                                                                                                                                        valign="middle"
                                                                                                                                                                                                                                        bgcolor='#FFFFFF'
                                                                                                                                                                                                                                        class="copyright">
                                                                                                                                                                                                                                        <? $sqlHijasNivel7 = "SELECT ID_FAMILIA_MATERIAL FROM FAMILIA_MATERIAL WHERE ID_FAMILIA_MATERIAL_PADRE = $rowFamMatNivel6->ID6";
                                                                                                                                                                                                                                        $resHijasNivel7 = $bd->ExecSQL($sqlHijasNivel7);
                                                                                                                                                                                                                                        if($bd->NumRegs($resHijasNivel7) > 0):
                                                                                                                                                                                                                                        ?>
                                                                                                                                                                                                                                        <img
                                                                                                                                                                                                                                                id="ContraerNivel7_<?=$rowFamMatNivel6->ID6?>"
                                                                                                                                                                                                                                                width="12"
                                                                                                                                                                                                                                                src='<?= $pathRaiz; ?>imagenes/collapse.png'
                                                                                                                                                                                                                                                title="<?= $auxiliar->traduce("Contraer", $administrador->ID_IDIOMA) ?>"
                                                                                                                                                                                                                                                style='vertical-align: middle; padding-top: 2px; <?=trim( (string)$txNombreFamilia) == "" ? 'display: none;' : ''?> cursor: pointer'
                                                                                                                                                                                                                                                onClick="visualizacionNivel('Nivel7_<?=$rowFamMatNivel6->ID6?>'); return false;">
                                                                                                                                                                                                                                        <img
                                                                                                                                                                                                                                                id="ExpandirNivel7_<?=$rowFamMatNivel6->ID6?>"
                                                                                                                                                                                                                                                width="12"
                                                                                                                                                                                                                                                src='<?= $pathRaiz; ?>imagenes/expand.png'
                                                                                                                                                                                                                                                title="<?= $auxiliar->traduce("Expandir", $administrador->ID_IDIOMA) ?>"
                                                                                                                                                                                                                                                style='vertical-align: middle; padding-top: 2px; <?=trim( (string)$txNombreFamilia) != "" ? 'display: none;' : ''?> cursor: pointer'
                                                                                                                                                                                                                                                onClick="visualizacionNivel('Nivel7_<?=$rowFamMatNivel6->ID6?>'); return false;">
                                                                                                                                                                                                                                        <?endif;?>
                                                                                                                                                                                                                                    </td>
                                                                                                                                                                                                                                    <td height="18"
                                                                                                                                                                                                                                        align='left'
                                                                                                                                                                                                                                        valign="middle"
                                                                                                                                                                                                                                        bgcolor='#FFFFFF'
                                                                                                                                                                                                                                        class="copyright">
                                                                                                                                                                                                                                        &nbsp;<a href="#"
                                                                                                                                                                                                                                                 onClick="EstablecerValorNuevo(<?= $rowFamMatNivel6->ID6 ?>,'<? echo $administrador->ID_IDIOMA=="ESP" ? $rowFamMatNivel6->NIVEL6 : $rowFamMatNivel6->NIVEL6_ENG; ?>','<?= $AlmacenarId ?>')"
                                                                                                                                                                                                                                                 class="enlaceceldas">
                                                                                                                                                                                                                                            <? echo $rowFamMatNivel6->ID6; ?></a>
                                                                                                                                                                                                                                    </td>
                                                                                                                                                                                                                                    <td height="18"
                                                                                                                                                                                                                                        align='left'
                                                                                                                                                                                                                                        valign="middle"
                                                                                                                                                                                                                                        bgcolor='#FFFFFF'
                                                                                                                                                                                                                                        class="copyright">
                                                                                                                                                                                                                                        <a href="#"
                                                                                                                                                                                                                                           onClick="EstablecerValorNuevo(<?= $rowFamMatNivel6->ID6 ?>,'<? echo $administrador->ID_IDIOMA=="ESP" ? $rowFamMatNivel6->NIVEL6 : $rowFamMatNivel6->NIVEL6_ENG; ?>','<?= $AlmacenarId ?>')"
                                                                                                                                                                                                                                           class="enlaceceldas">
                                                                                                                                                                                                                                            &nbsp;<? echo $administrador->ID_IDIOMA=="ESP" ? $rowFamMatNivel6->NIVEL6 : $rowFamMatNivel6->NIVEL6_ENG; ?></a>
                                                                                                                                                                                                                                        &nbsp;</td>
                                                                                                                                                                                                                                </tr>
                                                                                                                                                                                                                                <tr>
                                                                                                                                                                                                                                    <td colspan="30" bgcolor="#D9E3EC">
                                                                                                                                                                                                                                        <table border="0" cellpadding="0"
                                                                                                                                                                                                                                               cellspacing="0" width="100%">
                                                                                                                                                                                                                                            <tr>
                                                                                                                                                                                                                                                <td width="4%"></td>
                                                                                                                                                                                                                                                <td>
                                                                                                                                                                                                                                                    <table width="100%" border="0"
                                                                                                                                                                                                                                                           cellspacing="0">

                                                                                                                                                                                                                                                    </table>
                                                                                                                                                                                                                                                </td>
                                                                                                                                                                                                                                            </tr>
                                                                                                                                                                                                                                        </table>
                                                                                                                                                                                                                                    </td>
                                                                                                                                                                                                                                </tr>
                                                                                                                                                                                                                                <!--BUSQUEDA ACTIVIDADES-->
                                                                                                                                                                                                                                <?
                                                                                                                                                                                                                                if ($idPadreVisualizacion == $row->ID_FAMILIA_MATERIAL || trim( (string)$txNombreFamilia) != ""):

                                                                                                                                                                                                                                    //BUSCAMOS LOS ALMACENES DE INSTALACION
                                                                                                                                                                                                                                    $sqlFamMatNivel7 = "SELECT FM_1.ID_FAMILIA_MATERIAL,FM_1.NIVEL_FAMILIA,FM_1.ID_FAMILIA_MATERIAL_PADRE,FM_1.NOMBRE_FAMILIA AS PADRE, FM_2.ID_FAMILIA_MATERIAL AS ID2, FM_2.NOMBRE_FAMILIA AS NIVEL2, FM_3.ID_FAMILIA_MATERIAL AS ID3, FM_3.NOMBRE_FAMILIA AS NIVEL3, FM_4.ID_FAMILIA_MATERIAL AS ID4, FM_4.NOMBRE_FAMILIA AS NIVEL4, FM_5.ID_FAMILIA_MATERIAL AS ID5, FM_5.NOMBRE_FAMILIA AS NIVEL5, FM_6.NOMBRE_FAMILIA AS NIVEL6, FM_7.ID_FAMILIA_MATERIAL AS ID7, FM_7.NOMBRE_FAMILIA AS NIVEL7, FM_8.NOMBRE_FAMILIA AS NIVEL8, FM_9.NOMBRE_FAMILIA AS NIVEL9,FM_1.NOMBRE_FAMILIA_ENG AS PADRE_ENG, FM_2.NOMBRE_FAMILIA_ENG AS NIVEL2_ENG, FM_3.NOMBRE_FAMILIA_ENG AS NIVEL3_ENG, FM_4.NOMBRE_FAMILIA_ENG AS NIVEL4_ENG, FM_5.NOMBRE_FAMILIA_ENG AS NIVEL5_ENG, FM_6.NOMBRE_FAMILIA_ENG AS NIVEL6_ENG, FM_7.NOMBRE_FAMILIA_ENG AS NIVEL7_ENG, FM_8.NOMBRE_FAMILIA_ENG AS NIVEL8_ENG, FM_9.NOMBRE_FAMILIA_ENG AS NIVEL9_ENG
FROM FAMILIA_MATERIAL FM_1 
	LEFT JOIN FAMILIA_MATERIAL FM_2 ON FM_1.ID_FAMILIA_MATERIAL = FM_2.ID_FAMILIA_MATERIAL_PADRE
	LEFT JOIN FAMILIA_MATERIAL FM_3 ON FM_2.ID_FAMILIA_MATERIAL = FM_3.ID_FAMILIA_MATERIAL_PADRE
	LEFT JOIN FAMILIA_MATERIAL FM_4 ON FM_3.ID_FAMILIA_MATERIAL = FM_4.ID_FAMILIA_MATERIAL_PADRE
	LEFT JOIN FAMILIA_MATERIAL FM_5 ON FM_4.ID_FAMILIA_MATERIAL = FM_5.ID_FAMILIA_MATERIAL_PADRE
	LEFT JOIN FAMILIA_MATERIAL FM_6 ON FM_5.ID_FAMILIA_MATERIAL = FM_6.ID_FAMILIA_MATERIAL_PADRE
	LEFT JOIN FAMILIA_MATERIAL FM_7 ON FM_6.ID_FAMILIA_MATERIAL = FM_7.ID_FAMILIA_MATERIAL_PADRE
	LEFT JOIN FAMILIA_MATERIAL FM_8 ON FM_7.ID_FAMILIA_MATERIAL = FM_8.ID_FAMILIA_MATERIAL_PADRE
	LEFT JOIN FAMILIA_MATERIAL FM_9 ON FM_8.ID_FAMILIA_MATERIAL = FM_9.ID_FAMILIA_MATERIAL_PADRE
	WHERE (FM_1.NIVEL_FAMILIA = '1')  AND (FM_2.NIVEL_FAMILIA = '2' OR FM_2.NIVEL_FAMILIA IS NULL) AND (FM_3.NIVEL_FAMILIA = '3' OR FM_3.NIVEL_FAMILIA IS NULL) AND (FM_4.NIVEL_FAMILIA = '4' OR FM_4.NIVEL_FAMILIA IS NULL) AND (FM_5.NIVEL_FAMILIA = '5' OR FM_5.NIVEL_FAMILIA IS NULL) AND (FM_6.NIVEL_FAMILIA = '6' OR FM_6.NIVEL_FAMILIA IS NULL) AND (FM_7.NIVEL_FAMILIA = '7' OR FM_7.NIVEL_FAMILIA IS NULL) AND (FM_8.NIVEL_FAMILIA = '8' OR FM_8.NIVEL_FAMILIA IS NULL) AND (FM_9.NIVEL_FAMILIA = '9' OR FM_9.NIVEL_FAMILIA IS NULL) AND FM_7.ID_FAMILIA_MATERIAL_PADRE = '".$rowFamMatNivel6->ID6."' $whereFamilia
	GROUP BY NIVEL7";

                                                                                                                                                                                                                                    $resFamMatNivel7 = $bd->ExecSQL($sqlFamMatNivel7);
                                                                                                                                                                                                                                    $numFamMatNivel7 = $bd->NumRegs($resFamMatNivel7);
                                                                                                                                                                                                                                    ?>
                                                                                                                                                                                                                                    <tr class="Nivel7_<?=$rowFamMatNivel6->ID6?>" style="<?=trim( (string)$txNombreFamilia) == "" ? 'display: none;' : ''?>">
                                                                                                                                                                                                                                        <td colspan="30"
                                                                                                                                                                                                                                            bgcolor="#D9E3EC">
                                                                                                                                                                                                                                            <table
                                                                                                                                                                                                                                                    border="0"
                                                                                                                                                                                                                                                    cellpadding="0"
                                                                                                                                                                                                                                                    cellspacing="0"
                                                                                                                                                                                                                                                    width="100%">
                                                                                                                                                                                                                                                <tr>
                                                                                                                                                                                                                                                    <td width="4%"></td>
                                                                                                                                                                                                                                                    <td>
                                                                                                                                                                                                                                                        <table
                                                                                                                                                                                                                                                                width="100%"
                                                                                                                                                                                                                                                                border="0"
                                                                                                                                                                                                                                                                cellspacing="0">
                                                                                                                                                                                                                                                            <?
                                                                                                                                                                                                                                                            $pintarCabeceraNivel7 = true;
                                                                                                                                                                                                                                                            while ($rowFamMatNivel7 = $bd->SigReg($resFamMatNivel7)):
                                                                                                                                                                                                                                                                //ACTIVAMOS EL PINTAR LA CABECERA DEL PADRE
                                                                                                                                                                                                                                                                //$pintarCabeceraNivel6 = true;

                                                                                                                                                                                                                                                                ?>
                                                                                                                                                                                                                                                                <?
                                                                                                                                                                                                                                                                if ($pintarCabeceraNivel7 == true):
                                                                                                                                                                                                                                                                    //LO DESACTIVAMOS
                                                                                                                                                                                                                                                                    $pintarCabeceraNivel7 = false; ?>
                                                                                                                                                                                                                                                                    <tr>
                                                                                                                                                                                                                                                                        <td width="2%"
                                                                                                                                                                                                                                                                            align='left'
                                                                                                                                                                                                                                                                            valign="middle"
                                                                                                                                                                                                                                                                            bgcolor='#d9e3ec'
                                                                                                                                                                                                                                                                            class="copyright"
                                                                                                                                                                                                                                                                            style="white-space:nowrap">
                                                                                                                                                                                                                                                                        <td align='left'
                                                                                                                                                                                                                                                                            width="5%"
                                                                                                                                                                                                                                                                            valign="middle"
                                                                                                                                                                                                                                                                            bgcolor='#d9e3ec'
                                                                                                                                                                                                                                                                            class="copyright">
                                                                                                                                                                                                                                                                            &nbsp;<?= $auxiliar->traduce("Id", $administrador->ID_IDIOMA) ?>
                                                                                                                                                                                                                                                                            &nbsp;</td>
                                                                                                                                                                                                                                                                        <td align='left'
                                                                                                                                                                                                                                                                            valign="middle"
                                                                                                                                                                                                                                                                            width="93%"
                                                                                                                                                                                                                                                                            bgcolor='#d9e3ec'
                                                                                                                                                                                                                                                                            class="copyright">
                                                                                                                                                                                                                                                                            &nbsp;<?= $auxiliar->traduce("Descripcion", $administrador->ID_IDIOMA) ?>
                                                                                                                                                                                                                                                                            &nbsp;</td>

                                                                                                                                                                                                                                                                    </tr>
                                                                                                                                                                                                                                                                <? endif; ?>

                                                                                                                                                                                                                                                                <tr bgcolor='#d9e3ec'
                                                                                                                                                                                                                                                                    id="Familia_<?= $rowFamMatNivel7->ID7 ?>"
                                                                                                                                                                                                                                                                    onmouseover="colorearFilas('Familia_<?= $rowFamMatNivel7->ID7 ?>');return false;"
                                                                                                                                                                                                                                                                    onmouseout="descolorearFilas('Familia_<?= $rowFamMatNivel7->ID7 ?>');return false;">
                                                                                                                                                                                                                                                                    <td height="18"
                                                                                                                                                                                                                                                                        align='center'
                                                                                                                                                                                                                                                                        valign="middle"
                                                                                                                                                                                                                                                                        bgcolor='#FFFFFF'
                                                                                                                                                                                                                                                                        class="copyright">
                                                                                                                                                                                                                                                                        <? $sqlHijasNivel8 = "SELECT ID_FAMILIA_MATERIAL FROM FAMILIA_MATERIAL WHERE ID_FAMILIA_MATERIAL_PADRE = $rowFamMatNivel7->ID7";
                                                                                                                                                                                                                                                                        $resHijasNivel8 = $bd->ExecSQL($sqlHijasNivel8);
                                                                                                                                                                                                                                                                        if($bd->NumRegs($resHijasNivel8) > 0):
                                                                                                                                                                                                                                                                        ?>
                                                                                                                                                                                                                                                                        <img
                                                                                                                                                                                                                                                                                id="ContraerNivel8_<?=$rowFamMatNivel7->ID7?>"
                                                                                                                                                                                                                                                                                width="12"
                                                                                                                                                                                                                                                                                src='<?= $pathRaiz; ?>imagenes/collapse.png'
                                                                                                                                                                                                                                                                                title="<?= $auxiliar->traduce("Contraer", $administrador->ID_IDIOMA) ?>"
                                                                                                                                                                                                                                                                                style='vertical-align: middle; padding-top: 2px; <?=trim( (string)$txNombreFamilia) == "" ? 'display: none;' : ''?> cursor: pointer'
                                                                                                                                                                                                                                                                                onClick="visualizacionNivel('Nivel8_<?=$rowFamMatNivel7->ID7?>'); return false;">
                                                                                                                                                                                                                                                                        <img
                                                                                                                                                                                                                                                                                id="ExpandirNivel8_<?=$rowFamMatNivel7->ID7?>"
                                                                                                                                                                                                                                                                                width="12"
                                                                                                                                                                                                                                                                                src='<?= $pathRaiz; ?>imagenes/expand.png'
                                                                                                                                                                                                                                                                                title="<?= $auxiliar->traduce("Expandir", $administrador->ID_IDIOMA) ?>"
                                                                                                                                                                                                                                                                                style='vertical-align: middle; padding-top: 2px; <?=trim( (string)$txNombreFamilia) != "" ? 'display: none;' : ''?> cursor: pointer'
                                                                                                                                                                                                                                                                                onClick="visualizacionNivel('Nivel8_<?=$rowFamMatNivel7->ID7?>'); return false;">
                                                                                                                                                                                                                                                                        <?endif;?>
                                                                                                                                                                                                                                                                    </td>
                                                                                                                                                                                                                                                                    <td height="18"
                                                                                                                                                                                                                                                                        align='left'
                                                                                                                                                                                                                                                                        valign="middle"
                                                                                                                                                                                                                                                                        bgcolor='#FFFFFF'
                                                                                                                                                                                                                                                                        class="copyright">
                                                                                                                                                                                                                                                                        <a href="#"
                                                                                                                                                                                                                                                                           onClick="EstablecerValorNuevo(<?= $rowFamMatNivel7->ID7 ?>,'<? echo $administrador->ID_IDIOMA=="ESP" ? $rowFamMatNivel7->NIVEL7 : $rowFamMatNivel7->NIVEL7_ENG; ?>','<?= $AlmacenarId ?>')"
                                                                                                                                                                                                                                                                           class="enlaceceldas">
                                                                                                                                                                                                                                                                            &nbsp;<? echo $rowFamMatNivel7->ID7; ?></a>
                                                                                                                                                                                                                                                                    </td>
                                                                                                                                                                                                                                                                    <td height="18"
                                                                                                                                                                                                                                                                        align='left'
                                                                                                                                                                                                                                                                        valign="middle"
                                                                                                                                                                                                                                                                        bgcolor='#FFFFFF'
                                                                                                                                                                                                                                                                        class="copyright">
                                                                                                                                                                                                                                                                        <a href="#"
                                                                                                                                                                                                                                                                           onClick="EstablecerValorNuevo(<?= $rowFamMatNivel7->ID7 ?>,'<? echo $administrador->ID_IDIOMA=="ESP" ? $rowFamMatNivel7->NIVEL7 : $rowFamMatNivel7->NIVEL7_ENG; ?>','<?= $AlmacenarId ?>')"
                                                                                                                                                                                                                                                                           class="enlaceceldas">
                                                                                                                                                                                                                                                                            &nbsp;<? echo $administrador->ID_IDIOMA=="ESP" ? $rowFamMatNivel7->NIVEL7 : $rowFamMatNivel7->NIVEL7_ENG; ?></a>
                                                                                                                                                                                                                                                                        &nbsp;</td>
                                                                                                                                                                                                                                                                </tr>
                                                                                                                                                                                                                                                                <tr>
                                                                                                                                                                                                                                                                    <td colspan="30" bgcolor="#D9E3EC">
                                                                                                                                                                                                                                                                        <table border="0" cellpadding="0"
                                                                                                                                                                                                                                                                               cellspacing="0" width="100%">
                                                                                                                                                                                                                                                                            <tr>
                                                                                                                                                                                                                                                                                <td width="4%"></td>
                                                                                                                                                                                                                                                                                <td>
                                                                                                                                                                                                                                                                                    <table width="100%" border="0"
                                                                                                                                                                                                                                                                                           cellspacing="0">

                                                                                                                                                                                                                                                                                    </table>
                                                                                                                                                                                                                                                                                </td>
                                                                                                                                                                                                                                                                            </tr>
                                                                                                                                                                                                                                                                        </table>
                                                                                                                                                                                                                                                                    </td>
                                                                                                                                                                                                                                                                </tr>

                                                                                                                                                                                                                                                                <!--BUSQUEDA OPERACION-->
                                                                                                                                                                                                                                                                <?
                                                                                                                                                                                                                                                                if ($idPadreVisualizacion == $row->ID_FAMILIA_MATERIAL || trim( (string)$txNombreFamilia) != ""):

                                                                                                                                                                                                                                                                    //BUSCAMOS LOS ALMACENES DE INSTALACION
                                                                                                                                                                                                                                                                    $sqlFamMatNivel8 = "SELECT FM_1.ID_FAMILIA_MATERIAL,FM_1.NIVEL_FAMILIA,FM_1.ID_FAMILIA_MATERIAL_PADRE,FM_1.NOMBRE_FAMILIA AS PADRE, FM_2.ID_FAMILIA_MATERIAL AS ID2, FM_2.NOMBRE_FAMILIA AS NIVEL2, FM_3.ID_FAMILIA_MATERIAL AS ID3, FM_3.NOMBRE_FAMILIA AS NIVEL3, FM_4.ID_FAMILIA_MATERIAL AS ID4, FM_4.NOMBRE_FAMILIA AS NIVEL4, FM_5.ID_FAMILIA_MATERIAL AS ID5, FM_5.NOMBRE_FAMILIA AS NIVEL5, FM_6.NOMBRE_FAMILIA AS NIVEL6, FM_7.ID_FAMILIA_MATERIAL AS ID7, FM_7.NOMBRE_FAMILIA AS NIVEL7,  FM_8.ID_FAMILIA_MATERIAL AS ID8, FM_8.NOMBRE_FAMILIA AS NIVEL8, FM_9.NOMBRE_FAMILIA AS NIVEL9,FM_1.NOMBRE_FAMILIA_ENG AS PADRE_ENG, FM_2.NOMBRE_FAMILIA_ENG AS NIVEL2_ENG, FM_3.NOMBRE_FAMILIA_ENG AS NIVEL3_ENG, FM_4.NOMBRE_FAMILIA_ENG AS NIVEL4_ENG, FM_5.NOMBRE_FAMILIA_ENG AS NIVEL5_ENG, FM_6.NOMBRE_FAMILIA_ENG AS NIVEL6_ENG, FM_7.NOMBRE_FAMILIA_ENG AS NIVEL7_ENG, FM_8.NOMBRE_FAMILIA_ENG AS NIVEL8_ENG, FM_9.NOMBRE_FAMILIA_ENG AS NIVEL9_ENG
FROM FAMILIA_MATERIAL FM_1 
	LEFT JOIN FAMILIA_MATERIAL FM_2 ON FM_1.ID_FAMILIA_MATERIAL = FM_2.ID_FAMILIA_MATERIAL_PADRE
	LEFT JOIN FAMILIA_MATERIAL FM_3 ON FM_2.ID_FAMILIA_MATERIAL = FM_3.ID_FAMILIA_MATERIAL_PADRE
	LEFT JOIN FAMILIA_MATERIAL FM_4 ON FM_3.ID_FAMILIA_MATERIAL = FM_4.ID_FAMILIA_MATERIAL_PADRE
	LEFT JOIN FAMILIA_MATERIAL FM_5 ON FM_4.ID_FAMILIA_MATERIAL = FM_5.ID_FAMILIA_MATERIAL_PADRE
	LEFT JOIN FAMILIA_MATERIAL FM_6 ON FM_5.ID_FAMILIA_MATERIAL = FM_6.ID_FAMILIA_MATERIAL_PADRE
	LEFT JOIN FAMILIA_MATERIAL FM_7 ON FM_6.ID_FAMILIA_MATERIAL = FM_7.ID_FAMILIA_MATERIAL_PADRE
	LEFT JOIN FAMILIA_MATERIAL FM_8 ON FM_7.ID_FAMILIA_MATERIAL = FM_8.ID_FAMILIA_MATERIAL_PADRE
	LEFT JOIN FAMILIA_MATERIAL FM_9 ON FM_8.ID_FAMILIA_MATERIAL = FM_9.ID_FAMILIA_MATERIAL_PADRE
	WHERE (FM_1.NIVEL_FAMILIA = '1')  AND (FM_2.NIVEL_FAMILIA = '2' OR FM_2.NIVEL_FAMILIA IS NULL) AND (FM_3.NIVEL_FAMILIA = '3' OR FM_3.NIVEL_FAMILIA IS NULL) AND (FM_4.NIVEL_FAMILIA = '4' OR FM_4.NIVEL_FAMILIA IS NULL) AND (FM_5.NIVEL_FAMILIA = '5' OR FM_5.NIVEL_FAMILIA IS NULL) AND (FM_6.NIVEL_FAMILIA = '6' OR FM_6.NIVEL_FAMILIA IS NULL) AND (FM_7.NIVEL_FAMILIA = '7' OR FM_7.NIVEL_FAMILIA IS NULL) AND (FM_8.NIVEL_FAMILIA = '8' OR FM_8.NIVEL_FAMILIA IS NULL) AND (FM_9.NIVEL_FAMILIA = '9' OR FM_9.NIVEL_FAMILIA IS NULL) AND FM_8.ID_FAMILIA_MATERIAL_PADRE = '".$rowFamMatNivel7->ID7."' $whereFamilia
	GROUP BY NIVEL8";

                                                                                                                                                                                                                                                                    $resFamMatNivel8 = $bd->ExecSQL($sqlFamMatNivel8);
                                                                                                                                                                                                                                                                    $numFamMatNivel8 = $bd->NumRegs($resFamMatNivel8);

                                                                                                                                                                                                                                                                    ?>
                                                                                                                                                                                                                                                                    <tr class="Nivel8_<?=$rowFamMatNivel7->ID7?>" style="<?=trim( (string)$txNombreFamilia) == "" ? 'display: none;' : ''?>">
                                                                                                                                                                                                                                                                        <td colspan="30"
                                                                                                                                                                                                                                                                            bgcolor="#D9E3EC">
                                                                                                                                                                                                                                                                            <table
                                                                                                                                                                                                                                                                                    border="0"
                                                                                                                                                                                                                                                                                    cellpadding="0"
                                                                                                                                                                                                                                                                                    cellspacing="0"
                                                                                                                                                                                                                                                                                    width="100%">
                                                                                                                                                                                                                                                                                <tr>
                                                                                                                                                                                                                                                                                    <td width="4%"></td>
                                                                                                                                                                                                                                                                                    <td>
                                                                                                                                                                                                                                                                                        <table
                                                                                                                                                                                                                                                                                                width="100%"
                                                                                                                                                                                                                                                                                                border="0"
                                                                                                                                                                                                                                                                                                cellspacing="0">
                                                                                                                                                                                                                                                                                            <?
                                                                                                                                                                                                                                                                                            $pintarCabeceraNivel8 = true;
                                                                                                                                                                                                                                                                                            while ($rowFamMatNivel8 = $bd->SigReg($resFamMatNivel8)):

                                                                                                                                                                                                                                                                                                //ACTIVAMOS EL PINTAR LA CABECERA DEL PADRE
                                                                                                                                                                                                                                                                                                //$pintarCabeceraNivel7 = true;
                                                                                                                                                                                                                                                                                                ?>
                                                                                                                                                                                                                                                                                                <?
                                                                                                                                                                                                                                                                                                if ($pintarCabeceraNivel8 == true):
                                                                                                                                                                                                                                                                                                    //LO DESACTIVAMOS
                                                                                                                                                                                                                                                                                                    $pintarCabeceraNivel8 = false; ?>
                                                                                                                                                                                                                                                                                                    <tr>
                                                                                                                                                                                                                                                                                                        <td width="2%"
                                                                                                                                                                                                                                                                                                            align='left'
                                                                                                                                                                                                                                                                                                            valign="middle"
                                                                                                                                                                                                                                                                                                            bgcolor='#d9e3ec'
                                                                                                                                                                                                                                                                                                            class="copyright"
                                                                                                                                                                                                                                                                                                            style="white-space:nowrap">
                                                                                                                                                                                                                                                                                                        <td align='left'
                                                                                                                                                                                                                                                                                                            width="5%"
                                                                                                                                                                                                                                                                                                            valign="middle"
                                                                                                                                                                                                                                                                                                            bgcolor='#d9e3ec'
                                                                                                                                                                                                                                                                                                            class="copyright">
                                                                                                                                                                                                                                                                                                            &nbsp;<?= $auxiliar->traduce("Id", $administrador->ID_IDIOMA) ?>
                                                                                                                                                                                                                                                                                                            &nbsp;</td>
                                                                                                                                                                                                                                                                                                        <td align='left'
                                                                                                                                                                                                                                                                                                            width="93%"
                                                                                                                                                                                                                                                                                                            valign="middle"
                                                                                                                                                                                                                                                                                                            bgcolor='#d9e3ec'
                                                                                                                                                                                                                                                                                                            class="copyright">
                                                                                                                                                                                                                                                                                                            &nbsp;<?= $auxiliar->traduce("Familia", $administrador->ID_IDIOMA) ?>
                                                                                                                                                                                                                                                                                                            &nbsp;</td>
                                                                                                                                                                                                                                                                                                    </tr>
                                                                                                                                                                                                                                                                                                <? endif; ?>

                                                                                                                                                                                                                                                                                                <tr bgcolor='#d9e3ec'
                                                                                                                                                                                                                                                                                                    id="Familia_<?= $rowFamMatNivel8->ID8 ?>"
                                                                                                                                                                                                                                                                                                    onmouseover="colorearFilas('Familia_<?= $rowFamMatNivel8->ID8 ?>');return false;"
                                                                                                                                                                                                                                                                                                    onmouseout="descolorearFilas('Familia_<?= $rowFamMatNivel8->ID8 ?>');return false;">
                                                                                                                                                                                                                                                                                                    <td height="18"
                                                                                                                                                                                                                                                                                                        align='center'
                                                                                                                                                                                                                                                                                                        valign="middle"
                                                                                                                                                                                                                                                                                                        bgcolor='#FFFFFF'
                                                                                                                                                                                                                                                                                                        class="copyright">
                                                                                                                                                                                                                                                                                                        <? $sqlHijasNivel9 = "SELECT ID_FAMILIA_MATERIAL FROM FAMILIA_MATERIAL WHERE ID_FAMILIA_MATERIAL_PADRE = $rowFamMatNivel8->ID8";
                                                                                                                                                                                                                                                                                                        $resHijasNivel9 = $bd->ExecSQL($sqlHijasNivel9);
                                                                                                                                                                                                                                                                                                        if($bd->NumRegs($resHijasNivel9) > 0):
                                                                                                                                                                                                                                                                                                        ?>
                                                                                                                                                                                                                                                                                                        <img
                                                                                                                                                                                                                                                                                                                id="ContraerNivel9_<?=$rowFamMatNivel8->ID8?>"
                                                                                                                                                                                                                                                                                                                width="12"
                                                                                                                                                                                                                                                                                                                src='<?= $pathRaiz; ?>imagenes/collapse.png'
                                                                                                                                                                                                                                                                                                                title="<?= $auxiliar->traduce("Contraer", $administrador->ID_IDIOMA) ?>"
                                                                                                                                                                                                                                                                                                                style='vertical-align: middle; padding-top: 2px; <?=trim( (string)$txNombreFamilia) == "" ? 'display: none;' : ''?> cursor: pointer'
                                                                                                                                                                                                                                                                                                                onClick="visualizacionNivel('Nivel9_<?=$rowFamMatNivel8->ID8?>'); return false;">
                                                                                                                                                                                                                                                                                                        <img
                                                                                                                                                                                                                                                                                                                id="ExpandirNivel9_<?=$rowFamMatNivel8->ID8?>"
                                                                                                                                                                                                                                                                                                                width="12"
                                                                                                                                                                                                                                                                                                                src='<?= $pathRaiz; ?>imagenes/expand.png'
                                                                                                                                                                                                                                                                                                                title="<?= $auxiliar->traduce("Expandir", $administrador->ID_IDIOMA) ?>"
                                                                                                                                                                                                                                                                                                                style='vertical-align: middle; padding-top: 2px; <?=trim( (string)$txNombreFamilia) != "" ? 'display: none;' : ''?> cursor: pointer'
                                                                                                                                                                                                                                                                                                                onClick="visualizacionNivel('Nivel9_<?=$rowFamMatNivel8->ID8?>'); return false;">
                                                                                                                                                                                                                                                                                                        <?endif;?>
                                                                                                                                                                                                                                                                                                    </td>
                                                                                                                                                                                                                                                                                                    <td height="18"
                                                                                                                                                                                                                                                                                                        align='left'
                                                                                                                                                                                                                                                                                                        valign="middle"
                                                                                                                                                                                                                                                                                                        bgcolor='#FFFFFF'
                                                                                                                                                                                                                                                                                                        class="copyright">
                                                                                                                                                                                                                                                                                                        <a href="#"
                                                                                                                                                                                                                                                                                                           onClick="EstablecerValorNuevo(<?= $rowFamMatNivel8->ID8 ?>,'<? echo $administrador->ID_IDIOMA=="ESP" ? $rowFamMatNivel8->NIVEL8 : $rowFamMatNivel8->NIVEL8_ENG; ?>','<?= $AlmacenarId ?>')"
                                                                                                                                                                                                                                                                                                           class="enlaceceldas">
                                                                                                                                                                                                                                                                                                            &nbsp;<? echo $rowFamMatNivel8->ID8; ?></a>
                                                                                                                                                                                                                                                                                                    </td>
                                                                                                                                                                                                                                                                                                    <td height="18"
                                                                                                                                                                                                                                                                                                        align='left'
                                                                                                                                                                                                                                                                                                        valign="middle"
                                                                                                                                                                                                                                                                                                        bgcolor='#FFFFFF'
                                                                                                                                                                                                                                                                                                        class="copyright">
                                                                                                                                                                                                                                                                                                        <a href="#"
                                                                                                                                                                                                                                                                                                           onClick="EstablecerValorNuevo(<?= $rowFamMatNivel8->ID8 ?>,'<? echo $administrador->ID_IDIOMA=="ESP" ? $rowFamMatNivel8->NIVEL8 : $rowFamMatNivel8->NIVEL8_ENG; ?>','<?= $AlmacenarId ?>')"
                                                                                                                                                                                                                                                                                                           class="enlaceceldas">
                                                                                                                                                                                                                                                                                                            &nbsp;<? echo $administrador->ID_IDIOMA=="ESP" ? $rowFamMatNivel8->NIVEL8 : $rowFamMatNivel8->NIVEL8_ENG; ?></a>
                                                                                                                                                                                                                                                                                                        &nbsp;</td>
                                                                                                                                                                                                                                                                                                </tr>
                                                                                                                                                                                                                                                                                                <tr>
                                                                                                                                                                                                                                                                                                    <td colspan="30" bgcolor="#D9E3EC">
                                                                                                                                                                                                                                                                                                        <table border="0" cellpadding="0"
                                                                                                                                                                                                                                                                                                               cellspacing="0" width="100%">
                                                                                                                                                                                                                                                                                                            <tr>
                                                                                                                                                                                                                                                                                                                <td width="4%"></td>
                                                                                                                                                                                                                                                                                                                <td>
                                                                                                                                                                                                                                                                                                                    <table width="100%" border="0"
                                                                                                                                                                                                                                                                                                                           cellspacing="0">

                                                                                                                                                                                                                                                                                                                    </table>
                                                                                                                                                                                                                                                                                                                </td>
                                                                                                                                                                                                                                                                                                            </tr>
                                                                                                                                                                                                                                                                                                        </table>
                                                                                                                                                                                                                                                                                                    </td>
                                                                                                                                                                                                                                                                                                </tr>
                                                                                                                                                                                                                                                                                                <!--BUSQUEDA OPERACION-->
                                                                                                                                                                                                                                                                                                <?
                                                                                                                                                                                                                                                                                                if ($idPadreVisualizacion == $row->ID_FAMILIA_MATERIAL || trim( (string)$txNombreFamilia) != ""):

                                                                                                                                                                                                                                                                                                    //BUSCAMOS LOS ALMACENES DE INSTALACION
                                                                                                                                                                                                                                                                                                    $sqlFamMatNivel9 = "SELECT FM_1.ID_FAMILIA_MATERIAL,FM_1.NIVEL_FAMILIA,FM_1.ID_FAMILIA_MATERIAL_PADRE,FM_1.NOMBRE_FAMILIA AS PADRE, FM_2.ID_FAMILIA_MATERIAL AS ID2, FM_2.NOMBRE_FAMILIA AS NIVEL2, FM_3.ID_FAMILIA_MATERIAL AS ID3, FM_3.NOMBRE_FAMILIA AS NIVEL3, FM_4.ID_FAMILIA_MATERIAL AS ID4, FM_4.NOMBRE_FAMILIA AS NIVEL4, FM_5.ID_FAMILIA_MATERIAL AS ID5, FM_5.NOMBRE_FAMILIA AS NIVEL5, FM_6.NOMBRE_FAMILIA AS NIVEL6, FM_7.ID_FAMILIA_MATERIAL AS ID7, FM_7.NOMBRE_FAMILIA AS NIVEL7, FM_8.ID_FAMILIA_MATERIAL AS ID8, FM_8.NOMBRE_FAMILIA AS NIVEL8, FM_9.ID_FAMILIA_MATERIAL AS ID9, FM_9.NOMBRE_FAMILIA AS NIVEL9,FM_1.NOMBRE_FAMILIA_ENG AS PADRE_ENG, FM_2.NOMBRE_FAMILIA_ENG AS NIVEL2_ENG, FM_3.NOMBRE_FAMILIA_ENG AS NIVEL3_ENG, FM_4.NOMBRE_FAMILIA_ENG AS NIVEL4_ENG, FM_5.NOMBRE_FAMILIA_ENG AS NIVEL5_ENG, FM_6.NOMBRE_FAMILIA_ENG AS NIVEL6_ENG, FM_7.NOMBRE_FAMILIA_ENG AS NIVEL7_ENG, FM_8.NOMBRE_FAMILIA_ENG AS NIVEL8_ENG, FM_9.NOMBRE_FAMILIA_ENG AS NIVEL9_ENG
FROM FAMILIA_MATERIAL FM_1 
	LEFT JOIN FAMILIA_MATERIAL FM_2 ON FM_1.ID_FAMILIA_MATERIAL = FM_2.ID_FAMILIA_MATERIAL_PADRE
	LEFT JOIN FAMILIA_MATERIAL FM_3 ON FM_2.ID_FAMILIA_MATERIAL = FM_3.ID_FAMILIA_MATERIAL_PADRE
	LEFT JOIN FAMILIA_MATERIAL FM_4 ON FM_3.ID_FAMILIA_MATERIAL = FM_4.ID_FAMILIA_MATERIAL_PADRE
	LEFT JOIN FAMILIA_MATERIAL FM_5 ON FM_4.ID_FAMILIA_MATERIAL = FM_5.ID_FAMILIA_MATERIAL_PADRE
	LEFT JOIN FAMILIA_MATERIAL FM_6 ON FM_5.ID_FAMILIA_MATERIAL = FM_6.ID_FAMILIA_MATERIAL_PADRE
	LEFT JOIN FAMILIA_MATERIAL FM_7 ON FM_6.ID_FAMILIA_MATERIAL = FM_7.ID_FAMILIA_MATERIAL_PADRE
	LEFT JOIN FAMILIA_MATERIAL FM_8 ON FM_7.ID_FAMILIA_MATERIAL = FM_8.ID_FAMILIA_MATERIAL_PADRE
	LEFT JOIN FAMILIA_MATERIAL FM_9 ON FM_8.ID_FAMILIA_MATERIAL = FM_9.ID_FAMILIA_MATERIAL_PADRE
	WHERE (FM_1.NIVEL_FAMILIA = '1')  AND (FM_2.NIVEL_FAMILIA = '2' OR FM_2.NIVEL_FAMILIA IS NULL) AND (FM_3.NIVEL_FAMILIA = '3' OR FM_3.NIVEL_FAMILIA IS NULL) AND (FM_4.NIVEL_FAMILIA = '4' OR FM_4.NIVEL_FAMILIA IS NULL) AND (FM_5.NIVEL_FAMILIA = '5' OR FM_5.NIVEL_FAMILIA IS NULL) AND (FM_6.NIVEL_FAMILIA = '6' OR FM_6.NIVEL_FAMILIA IS NULL) AND (FM_7.NIVEL_FAMILIA = '7' OR FM_7.NIVEL_FAMILIA IS NULL) AND (FM_8.NIVEL_FAMILIA = '8' OR FM_8.NIVEL_FAMILIA IS NULL) AND (FM_9.NIVEL_FAMILIA = '9' OR FM_9.NIVEL_FAMILIA IS NULL) AND FM_9.ID_FAMILIA_MATERIAL_PADRE = '".$rowFamMatNivel8->ID8."' $whereFamilia
	GROUP BY NIVEL9";

                                                                                                                                                                                                                                                                                                    $resFamMatNivel9 = $bd->ExecSQL($sqlFamMatNivel9);
                                                                                                                                                                                                                                                                                                    $numFamMatNivel9 = $bd->NumRegs($resFamMatNivel9);

                                                                                                                                                                                                                                                                                                    ?>
                                                                                                                                                                                                                                                                                                    <tr class="Nivel9_<?=$rowFamMatNivel8->ID8?>" style="<?=trim( (string)$txNombreFamilia) == "" ? 'display: none;' : ''?>">
                                                                                                                                                                                                                                                                                                        <td colspan="30"
                                                                                                                                                                                                                                                                                                            bgcolor="#D9E3EC">
                                                                                                                                                                                                                                                                                                            <table
                                                                                                                                                                                                                                                                                                                    border="0"
                                                                                                                                                                                                                                                                                                                    cellpadding="0"
                                                                                                                                                                                                                                                                                                                    cellspacing="0"
                                                                                                                                                                                                                                                                                                                    width="100%">
                                                                                                                                                                                                                                                                                                                <tr>
                                                                                                                                                                                                                                                                                                                    <td width="4%"></td>
                                                                                                                                                                                                                                                                                                                    <td>
                                                                                                                                                                                                                                                                                                                        <table
                                                                                                                                                                                                                                                                                                                                width="100%"
                                                                                                                                                                                                                                                                                                                                border="0"
                                                                                                                                                                                                                                                                                                                                cellspacing="0">
                                                                                                                                                                                                                                                                                                                            <?
                                                                                                                                                                                                                                                                                                                            $pintarCabeceraNivel9 = true;
                                                                                                                                                                                                                                                                                                                            while ($rowFamMatNivel9 = $bd->SigReg($resFamMatNivel9)):

                                                                                                                                                                                                                                                                                                                                //ACTIVAMOS EL PINTAR LA CABECERA DEL PADRE
                                                                                                                                                                                                                                                                                                                                //$pintarCabeceraNivel8 = true;
                                                                                                                                                                                                                                                                                                                                ?>
                                                                                                                                                                                                                                                                                                                                <?
                                                                                                                                                                                                                                                                                                                                if ($pintarCabeceraNivel9 == true):
                                                                                                                                                                                                                                                                                                                                    //LO DESACTIVAMOS
                                                                                                                                                                                                                                                                                                                                    $pintarCabeceraNivel9 = false; ?>
                                                                                                                                                                                                                                                                                                                                    <tr>
                                                                                                                                                                                                                                                                                                                                        <td align='left'
                                                                                                                                                                                                                                                                                                                                            width="5%"
                                                                                                                                                                                                                                                                                                                                            valign="middle"
                                                                                                                                                                                                                                                                                                                                            bgcolor='#d9e3ec'
                                                                                                                                                                                                                                                                                                                                            class="copyright">
                                                                                                                                                                                                                                                                                                                                            &nbsp;<?= $auxiliar->traduce("Id", $administrador->ID_IDIOMA) ?>
                                                                                                                                                                                                                                                                                                                                            &nbsp;</td>
                                                                                                                                                                                                                                                                                                                                        <td align='left'
                                                                                                                                                                                                                                                                                                                                            width="95%"
                                                                                                                                                                                                                                                                                                                                            valign="middle"
                                                                                                                                                                                                                                                                                                                                            bgcolor='#d9e3ec'
                                                                                                                                                                                                                                                                                                                                            class="copyright">
                                                                                                                                                                                                                                                                                                                                            &nbsp;<?= $auxiliar->traduce("Familia", $administrador->ID_IDIOMA) ?>
                                                                                                                                                                                                                                                                                                                                            &nbsp;</td>
                                                                                                                                                                                                                                                                                                                                    </tr>
                                                                                                                                                                                                                                                                                                                                <? endif; ?>

                                                                                                                                                                                                                                                                                                                                <tr bgcolor='#d9e3ec'
                                                                                                                                                                                                                                                                                                                                    id="Familia_<?= $rowFamMatNivel9->ID9 ?>"
                                                                                                                                                                                                                                                                                                                                    onmouseover="colorearFilas('Familia_<?= $rowFamMatNivel9->ID9 ?>');return false;"
                                                                                                                                                                                                                                                                                                                                    onmouseout="descolorearFilas('Familia_<?= $rowFamMatNivel9->ID9 ?>');return false;">
                                                                                                                                                                                                                                                                                                                                    <td height="18"
                                                                                                                                                                                                                                                                                                                                        align='left'
                                                                                                                                                                                                                                                                                                                                        valign="middle"
                                                                                                                                                                                                                                                                                                                                        bgcolor='#FFFFFF'
                                                                                                                                                                                                                                                                                                                                        class="copyright">
                                                                                                                                                                                                                                                                                                                                        <a href="#"
                                                                                                                                                                                                                                                                                                                                           onClick="EstablecerValorNuevo(<?= $rowFamMatNivel9->ID9 ?>,'<? echo $administrador->ID_IDIOMA=="ESP" ? $rowFamMatNivel9->NIVEL9 : $rowFamMatNivel9->NIVEL9_ENG; ?>','<?= $AlmacenarId ?>')"
                                                                                                                                                                                                                                                                                                                                           class="enlaceceldas">
                                                                                                                                                                                                                                                                                                                                            &nbsp;<? echo $rowFamMatNivel9->ID9; ?></a>
                                                                                                                                                                                                                                                                                                                                    </td>
                                                                                                                                                                                                                                                                                                                                    <td height="18"
                                                                                                                                                                                                                                                                                                                                        align='left'
                                                                                                                                                                                                                                                                                                                                        valign="middle"
                                                                                                                                                                                                                                                                                                                                        bgcolor='#FFFFFF'
                                                                                                                                                                                                                                                                                                                                        class="copyright">
                                                                                                                                                                                                                                                                                                                                        <a href="#"
                                                                                                                                                                                                                                                                                                                                           onClick="EstablecerValorNuevo(<?= $rowFamMatNivel9->ID9 ?>,'<? echo $administrador->ID_IDIOMA=="ESP" ? $rowFamMatNivel9->NIVEL9 : $rowFamMatNivel9->NIVEL9_ENG; ?>','<?= $AlmacenarId ?>')"
                                                                                                                                                                                                                                                                                                                                           class="enlaceceldas">
                                                                                                                                                                                                                                                                                                                                            &nbsp;<? echo $administrador->ID_IDIOMA=="ESP" ? $rowFamMatNivel9->NIVEL9 : $rowFamMatNivel9->NIVEL9_ENG; ?></a>
                                                                                                                                                                                                                                                                                                                                        &nbsp;</td>
                                                                                                                                                                                                                                                                                                                                </tr>
                                                                                                                                                                                                                                                                                                                                <tr>
                                                                                                                                                                                                                                                                                                                                    <td colspan="30" bgcolor="#D9E3EC">
                                                                                                                                                                                                                                                                                                                                        <table border="0" cellpadding="0"
                                                                                                                                                                                                                                                                                                                                               cellspacing="0" width="100%">
                                                                                                                                                                                                                                                                                                                                            <tr>
                                                                                                                                                                                                                                                                                                                                                <td width="4%"></td>
                                                                                                                                                                                                                                                                                                                                                <td>
                                                                                                                                                                                                                                                                                                                                                    <table width="100%" border="0"
                                                                                                                                                                                                                                                                                                                                                           cellspacing="0">

                                                                                                                                                                                                                                                                                                                                                    </table>
                                                                                                                                                                                                                                                                                                                                                </td>
                                                                                                                                                                                                                                                                                                                                            </tr>
                                                                                                                                                                                                                                                                                                                                        </table>
                                                                                                                                                                                                                                                                                                                                    </td>
                                                                                                                                                                                                                                                                                                                                </tr>
                                                                                                                                                                                                                                                                                                                            <?
                                                                                                                                                                                                                                                                                                                            endwhile; //FIN RECORRER OPERACIONES
                                                                                                                                                                                                                                                                                                                            ?>
                                                                                                                                                                                                                                                                                                                        </table>
                                                                                                                                                                                                                                                                                                                    </td>
                                                                                                                                                                                                                                                                                                                </tr>
                                                                                                                                                                                                                                                                                                            </table>
                                                                                                                                                                                                                                                                                                        </td>
                                                                                                                                                                                                                                                                                                    </tr>
                                                                                                                                                                                                                                                                                                <?
                                                                                                                                                                                                                                                                                                endif; ?>
                                                                                                                                                                                                                                                                                            <?
                                                                                                                                                                                                                                                                                            endwhile; //FIN RECORRER
                                                                                                                                                                                                                                                                                            ?>
                                                                                                                                                                                                                                                                                        </table>
                                                                                                                                                                                                                                                                                    </td>
                                                                                                                                                                                                                                                                                </tr>
                                                                                                                                                                                                                                                                            </table>
                                                                                                                                                                                                                                                                        </td>
                                                                                                                                                                                                                                                                    </tr>
                                                                                                                                                                                                                                                                <?
                                                                                                                                                                                                                                                                endif; ?>
                                                                                                                                                                                                                                                                <!--FIN BUSQUEDA -->
                                                                                                                                                                                                                                                            <?
                                                                                                                                                                                                                                                            endwhile; //FIN RECORRER
                                                                                                                                                                                                                                                            ?>
                                                                                                                                                                                                                                                        </table>
                                                                                                                                                                                                                                                    </td>
                                                                                                                                                                                                                                                </tr>
                                                                                                                                                                                                                                            </table>
                                                                                                                                                                                                                                        </td>
                                                                                                                                                                                                                                    </tr>
                                                                                                                                                                                                                                <?
                                                                                                                                                                                                                                endif; ?>
                                                                                                                                                                                                                                <!--FIN BUSQUEDA -->
                                                                                                                                                                                                                            <? endwhile; //FIN RECORRER
                                                                                                                                                                                                                            ?>
                                                                                                                                                                                                                        </table>
                                                                                                                                                                                                                    </td>
                                                                                                                                                                                                                </tr>
                                                                                                                                                                                                            </table>
                                                                                                                                                                                                        </td>
                                                                                                                                                                                                    </tr>
                                                                                                                                                                                                <?
                                                                                                                                                                                                endif; ?>
                                                                                                                                                                                                <!--FIN BUSQUEDA -->
                                                                                                                                                                                            <?
                                                                                                                                                                                            endwhile; //FIN RECORRER
                                                                                                                                                                                            ?>
                                                                                                                                                                                        </table>
                                                                                                                                                                                    </td>
                                                                                                                                                                                </tr>
                                                                                                                                                                            </table>
                                                                                                                                                                        </td>
                                                                                                                                                                    </tr>
                                                                                                                                                                <? endif; ?>
                                                                                                                                                                <!--FIN BUSQUEDA A NIVEL -->
                                                                                                                                                            <?
                                                                                                                                                            endwhile; //FIN RECORRER
                                                                                                                                                            ?>
                                                                                                                                                        </table>
                                                                                                                                                    </td>
                                                                                                                                                </tr>
                                                                                                                                            </table>
                                                                                                                                        </td>
                                                                                                                                    </tr>
                                                                                                                                <?
                                                                                                                                endif; ?>
                                                                                                                                <!--FIN BUSQUEDA -->
                                                                                                                            <?
                                                                                                                            endwhile;//FIN RECORRER
                                                                                                                            ?>
                                                                                                                        </table>
                                                                                                                    </td>
                                                                                                                </tr>
                                                                                                            </table>
                                                                                                        </td>
                                                                                                    </tr>

                                                                                                <?
                                                                                                endif; ?>
                                                                                                <!--FIN BUSQUEDA A NIVEL-->

                                                                                            <?
                                                                                            endwhile; //FIN RECORRER
                                                                                            ?>
                                                                                        </table>
                                                                                    </td>
                                                                                </tr>
                                                                            </table>
                                                                        </td>
                                                                    </tr>

                                                                <? endif; ?>
                                                                <!--FIN BUSQUEDA A NIVEL DE INSTALACION-->
                                                                <?
                                                                $i++;
                                                                $numeracion++;
                                                            endwhile;

                                                            ?>
                                                        </table>
                                                    </td>
                                                </tr>
                                            <? else: ?>
                                                <tr>
                                                    <td colspan="2" align="center" bgcolor="#D9E3EC" class="alertas3"
                                                        height="19px"><?= $auxiliar->traduce("No existen registros para la búsqueda realizada", $administrador->ID_IDIOMA) ?></td>
                                                </tr>
                                            <? endif; ?>
                                            <? if ($numRegistros > 0): ?>
                                                <tr bgcolor="#D9E3EC" class="lineaabajo" height="22">
                                                    <td width="30%" class="copyright">
                                                        &nbsp;&nbsp;&nbsp;<?= $auxiliar->traduce("Ver", $administrador->ID_IDIOMA) ?>
                                                        &nbsp;#
                                                        <? $navegar->GenerarComboNumRegs($navegar->maxfilasMaestroFamiliaMaterial); ?>
                                                        &nbsp;&nbsp;
                                                        <? $navegar->NumRegs($navegar->maxfilasMaestroFamiliaMaterial, $maxahora, $navegar->numerofilasMaestroFamiliaMaterial); ?>
                                                    </td>
                                                    <td width="73%" class="copyright">
                                                        <div
                                                                align="right"><? $navegar->Numeros($navegar->sqlAdminMaestroFamiliaMaterial, $navegar->maxfilasMaestroFamiliaMaterial, $navegar->numerofilasMaestroFamiliaMaterial, $i, "busqueda_familia_material.php", "#2E8AF0") ?>
                                                            &nbsp;&nbsp;&nbsp;</div>
                                                    </td>
                                                </tr>
                                            <? endif; ?>

                                            <!-- FIN MANERA ARBOL ESTILO ESTRUCTURA CONSTRUCCION SOLAR-->

                                            <!-- MANERA NORMAL-->
                                            <? if ($numRegistros > 0): /*?>
                                                <tr bgcolor="#D9E3EC" class="lineaabajo" height="22">
                                                    <td width="30%" class="copyright">
                                                        &nbsp;&nbsp;&nbsp;<?= $auxiliar->traduce("Ver", $administrador->ID_IDIOMA) ?>
                                                        &nbsp;#
                                                        <? $navegar->GenerarComboNumRegs($navegar->maxfilasMaestroFamiliaMaterial, "selLimiteSuperior"); ?>
                                                        &nbsp;&nbsp;
                                                        <? $navegar->NumRegs($navegar->maxfilasMaestroFamiliaMaterial, $maxahora, $navegar->numerofilasMaestroFamiliaMaterial); ?>
                                                    </td>
                                                    <td width="73%" class="copyright">
                                                        <div
                                                            align="right"><? $navegar->Numeros($navegar->sqlAdminMaestroFamiliaMaterial, $navegar->maxfilasMaestroFamiliaMaterial, $navegar->numerofilasMaestroFamiliaMaterial, $i, "busqueda_familia_material.php", "#2E8AF0") ?>
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
                                                                    class="blanco"><? $navegar->GenerarColumna($auxiliar->traduce("Id", $administrador->ID_IDIOMA), "enlaceCabecera", "id", $pathRaiz) ?></td>
                                                                <td height="19" bgcolor="#2E8AF0"
                                                                    class="blanco"><?= $auxiliar->traduce("Nº familia repro", $administrador->ID_IDIOMA) ?></td>
                                                                <td height="19" bgcolor="#2E8AF0"
                                                                    class="blanco"><?= $auxiliar->traduce("Nivel 1", $administrador->ID_IDIOMA) ?></td>
                                                                <td height="19" bgcolor="#2E8AF0"
                                                                    class="blanco"><?= $auxiliar->traduce("Nivel 2", $administrador->ID_IDIOMA) ?></td>
                                                                <td height="19" bgcolor="#2E8AF0"
                                                                    class="blanco"><?= $auxiliar->traduce("Nivel 3", $administrador->ID_IDIOMA) ?></td>
                                                                <td height="19" bgcolor="#2E8AF0"
                                                                    class="blanco"><?= $auxiliar->traduce("Nivel 4", $administrador->ID_IDIOMA) ?></td>
                                                                <td height="19" bgcolor="#2E8AF0"
                                                                    class="blanco"><?= $auxiliar->traduce("Nivel 5", $administrador->ID_IDIOMA) ?></td>
                                                                <td height="19" bgcolor="#2E8AF0"
                                                                    class="blanco"><?= $auxiliar->traduce("Nivel 6", $administrador->ID_IDIOMA) ?></td>
                                                                <td height="19" bgcolor="#2E8AF0"
                                                                    class="blanco"><?= $auxiliar->traduce("Nivel 7", $administrador->ID_IDIOMA) ?></td>
                                                                <td height="19" bgcolor="#2E8AF0"
                                                                    class="blanco"><?= $auxiliar->traduce("Nivel 8", $administrador->ID_IDIOMA) ?></td>
                                                                <td height="19" bgcolor="#2E8AF0"
                                                                    class="blanco"><?= $auxiliar->traduce("Nivel 9", $administrador->ID_IDIOMA) ?></td>
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

                                                                $idFamiliaPadre = $row->ID_FAMILIA_MATERIAL_PADRE;
                                                                //$raiz = $row->NOMBRE_FAMILIA;
                                                                $raiz = array(1=>'-',2=>'-',3=>'-',4=>'-',5=>'-',6=>'-',7=>'-',8=>'-',9=>'-');
                                                                $nivel = $row->NIVEL_FAMILIA;
                                                                $raiz[$row->NIVEL_FAMILIA] = $row->FAMILIA_MATERIAL;
                                                                //BUSCAMOS LA RAIZ DE LA FAMILIA
                                                                while($idFamiliaPadre != null):
                                                                    $nivel = $nivel-1;
                                                                    $sqlFamiliaPadre = "SELECT ID_FAMILIA_MATERIAL_PADRE, NOMBRE_FAMILIA FROM FAMILIA_MATERIAL WHERE ID_FAMILIA_MATERIAL = '$idFamiliaPadre'";
                                                                    $resultFamiliaPadre = $bd->ExecSQL($sqlFamiliaPadre);
                                                                    $rowFamiliaPadre = $bd->SigReg($resultFamiliaPadre);
                                                                    $idFamiliaPadre = $rowFamiliaPadre->ID_FAMILIA_MATERIAL_PADRE;
                                                                    $raiz[$nivel] = $rowFamiliaPadre->NOMBRE_FAMILIA;
                                                                endwhile;

                                                                ?>
                                                                <tr align="center" valign="middle" bgcolor="#AACFF9">

                                                                    <? if ($seleccionMultiple == 1): ?>
                                                                        <td height="18" align="center"
                                                                            bgcolor="<?= $myColor ?>"
                                                                            class="enlaceceldas">
                                                                            <input type="hidden"
                                                                                   id="idFamiliaRepro<? echo $row->ID_FAMILIA_MATERIAL ?>"
                                                                                   name="idFamiliaRepro<? echo $row->ID_FAMILIA_MATERIAL ?>"
                                                                                   value="<? echo $row->ID_FAMILIA_MATERIAL ?>">

                                                                            <? $html->Option("chLinea_" . $row->ID_FAMILIA_MATERIAL, "Check", "1", 0); ?>
                                                                        </td>
                                                                    <? endif; ?>
                                                                    <td height="18" align="left"
                                                                        bgcolor="<? echo $myColor ?>"
                                                                        class="enlaceceldas">
                                                                        &nbsp;
                                                                        <a href="#"
                                                                           onClick="EstablecerValor(jQuery(this).parent().parent(),'<?= $AlmacenarId ?>')"
                                                                           class="enlaceceldas"
                                                                           style="white-space:pre;"><? echo $row->ID_FAMILIA_MATERIAL ?></a>
                                                                        <input type="hidden"
                                                                               value="<?= $row->ID_FAMILIA_MATERIAL ?>"/>
                                                                    </td>
                                                                    <td height="18" align="left"
                                                                        bgcolor="<? echo $myColor ?>"
                                                                        class="enlaceceldas">
                                                                        &nbsp;
                                                                        <a href="#"
                                                                           onClick="EstablecerValor(jQuery(this).parent().parent(),'<?= $AlmacenarId ?>')"
                                                                           class="enlaceceldas"
                                                                           style="white-space:pre;"><?
                                                                            $rowFamiliaRepro = $bd->VerReg("FAMILIA_REPRO", "ID_FAMILIA_REPRO", $row->ID_FAMILIA_REPRO, "No");
                                                                            echo $rowFamiliaRepro->REFERENCIA ?></a>
                                                                    </td>
                                                                    <td height="18" align="left"
                                                                        bgcolor="<? echo $myColor ?>"
                                                                        class="enlaceceldas">
                                                                        &nbsp;
                                                                        <a href="#"
                                                                           onClick="EstablecerValor(jQuery(this).parent().parent(),'<?= $AlmacenarId ?>')"
                                                                           class="enlaceceldas"
                                                                           style="white-space:pre;"><? echo ($row->NIVEL_FAMILIA == '1' ? '<strong>'.$row->NOMBRE_FAMILIA.'</strong>' : $raiz[1])?></a>
                                                                    </td>
                                                                    <td height="18" align="left"
                                                                        bgcolor="<? echo $myColor ?>"
                                                                        class="enlaceceldas">
                                                                        &nbsp;
                                                                        <a href="#"
                                                                           onClick="EstablecerValor(jQuery(this).parent().parent(),'<?= $AlmacenarId ?>')"
                                                                           class="enlaceceldas"
                                                                           style="white-space:pre;"><? echo ($row->NIVEL_FAMILIA == '2' ? '<strong>'.$row->NOMBRE_FAMILIA.'</strong>' : $raiz[2])?></a>
                                                                    </td>
                                                                    <td height="18" align="left"
                                                                        bgcolor="<? echo $myColor ?>"
                                                                        class="enlaceceldas">
                                                                        &nbsp;
                                                                        <a href="#"
                                                                           onClick="EstablecerValor(jQuery(this).parent().parent(),'<?= $AlmacenarId ?>')"
                                                                           class="enlaceceldas"
                                                                           style="white-space:pre;"><? echo ($row->NIVEL_FAMILIA == '3' ? '<strong>'.$row->NOMBRE_FAMILIA.'</strong>' : $raiz[3])?></a>
                                                                    </td>
                                                                    <td height="18" align="left"
                                                                        bgcolor="<? echo $myColor ?>"
                                                                        class="enlaceceldas">
                                                                        &nbsp;
                                                                        <a href="#"
                                                                           onClick="EstablecerValor(jQuery(this).parent().parent(),'<?= $AlmacenarId ?>')"
                                                                           class="enlaceceldas"
                                                                           style="white-space:pre;"><? echo ($row->NIVEL_FAMILIA == '4' ? '<strong>'.$row->NOMBRE_FAMILIA.'</strong>' : $raiz[4])?></a>
                                                                    </td>
                                                                    <td height="18" align="left"
                                                                        bgcolor="<? echo $myColor ?>"
                                                                        class="enlaceceldas">
                                                                        &nbsp;
                                                                        <a href="#"
                                                                           onClick="EstablecerValor(jQuery(this).parent().parent(),'<?= $AlmacenarId ?>')"
                                                                           class="enlaceceldas"
                                                                           style="white-space:pre;"><? echo ($row->NIVEL_FAMILIA == '5' ? '<strong>'.$row->NOMBRE_FAMILIA.'</strong>' : $raiz[5])?></a>
                                                                    </td>
                                                                    <td height="18" align="left"
                                                                        bgcolor="<? echo $myColor ?>"
                                                                        class="enlaceceldas">
                                                                        &nbsp;
                                                                        <a href="#"
                                                                           onClick="EstablecerValor(jQuery(this).parent().parent(),'<?= $AlmacenarId ?>')"
                                                                           class="enlaceceldas"
                                                                           style="white-space:pre;"><? echo ($row->NIVEL_FAMILIA == '6' ? '<strong>'.$row->NOMBRE_FAMILIA.'</strong>' : $raiz[6])?></a>
                                                                    </td>
                                                                    <td height="18" align="left"
                                                                        bgcolor="<? echo $myColor ?>"
                                                                        class="enlaceceldas">
                                                                        &nbsp;
                                                                        <a href="#"
                                                                           onClick="EstablecerValor(jQuery(this).parent().parent(),'<?= $AlmacenarId ?>')"
                                                                           class="enlaceceldas"
                                                                           style="white-space:pre;"><? echo ($row->NIVEL_FAMILIA == '7' ? '<strong>'.$row->NOMBRE_FAMILIA.'</strong>' : $raiz[7])?></a>
                                                                    </td>
                                                                    <td height="18" align="left"
                                                                        bgcolor="<? echo $myColor ?>"
                                                                        class="enlaceceldas">
                                                                        &nbsp;
                                                                        <a href="#"
                                                                           onClick="EstablecerValor(jQuery(this).parent().parent(),'<?= $AlmacenarId ?>')"
                                                                           class="enlaceceldas"
                                                                           style="white-space:pre;"><? echo ($row->NIVEL_FAMILIA == '8' ? '<strong>'.$row->NOMBRE_FAMILIA.'</strong>' : $raiz[8])?></a>
                                                                    </td>
                                                                    <td height="18" align="left"
                                                                        bgcolor="<? echo $myColor ?>"
                                                                        class="enlaceceldas">
                                                                        &nbsp;
                                                                        <a href="#"
                                                                           onClick="EstablecerValor(jQuery(this).parent().parent(),'<?= $AlmacenarId ?>')"
                                                                           class="enlaceceldas"
                                                                           style="white-space:pre;"><? echo ($row->NIVEL_FAMILIA == '9' ? '<strong>'.$row->NOMBRE_FAMILIA.'</strong>' : $raiz[9])?></a>
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
                                                    <td width="30%" class="copyright">
                                                        &nbsp;&nbsp;&nbsp;<?= $auxiliar->traduce("Ver", $administrador->ID_IDIOMA) ?>
                                                        &nbsp;#
                                                        <? $navegar->GenerarComboNumRegs($navegar->maxfilasMaestroFamiliaMaterial); ?>
                                                        &nbsp;&nbsp;
                                                        <? $navegar->NumRegs($navegar->maxfilasMaestroFamiliaMaterial, $maxahora, $navegar->numerofilasMaestroFamiliaMaterial); ?>
                                                    </td>
                                                    <td width="73%" class="copyright">
                                                        <div
                                                            align="right"><? $navegar->Numeros($navegar->sqlAdminMaestroFamiliaMaterial, $navegar->maxfilasMaestroFamiliaMaterial, $navegar->numerofilasMaestroFamiliaMaterial, $i, "busqueda_familia_material.php", "#2E8AF0") ?>
                                                            &nbsp;&nbsp;&nbsp;</div>
                                                    </td>
                                                </tr>
                                            <? */endif; ?>
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