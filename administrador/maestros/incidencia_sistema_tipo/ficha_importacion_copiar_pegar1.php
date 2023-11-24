<?
// PATHS DE LA WEB
$pathRaiz   = "../../";
$pathClases = "../../../";

// INCLUDES DE LIBRERIAS PROPIAS
require_once $pathClases . "lib/basedatos.php";
require_once $pathClases . "lib/administrador.php";
require_once $pathClases . "lib/material.php";
require_once $pathClases . "lib/html.php";
require_once $pathClases . "lib/navegar.php";
require_once $pathClases . "lib/comprobar.php";
require_once $pathClases . "lib/auxiliar.php";

session_start();
include $pathRaiz . "seguridad_admin.php";

$tituloPag         = $auxiliar->traduce("Tipos de Incidencias", $administrador->ID_IDIOMA);
$tituloNav         = $auxiliar->traduce("Maestros", $administrador->ID_IDIOMA) . " >> " . $auxiliar->traduce("Tipos de Incidencias", $administrador->ID_IDIOMA);
$ZonaTablaPadre    = "Maestros";
$ZonaSubTablaPadre = "MaestrosSubmenuAplicacion";
$ZonaTabla         = "MaestrosIncidenciaSistemaTipo";

// COMPRUEBA SI TIENE PERMISOS
if ($administrador->Hayar_Permiso_Perfil('ADM_MAESTROS_INCIDENCIA_SISTEMA_TIPO') < 2):
    $html->PagError("SinPermisos");
endif;

//EJEMPLO DE LINEA PARA INTRODUCIR UN NUEVO CAMPO
$txLineasCamposEjemplo = $auxiliar->traduce("Ejemplo", $administrador->ID_IDIOMA) . ": MARCA|Marca|txMarca|txMarca|MaestrosMateriales|1|0";

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN""http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <? require_once $pathClases . "lib/gral_js.php"; ?>
    <!-- BUSQUEDA AJAX -->
    <script src="<?= $pathClases; ?>lib/ajax_script/lib/prototype.js" type="text/javascript"></script>
    <script src="<?= $pathClases; ?>lib/ajax_script/src/scriptaculous.js" type="text/javascript"></script>
    <link rel="stylesheet" href="<?= $pathClases; ?>lib/ajax_script/style_ajax.css" type="text/css"/>
    <!-- FIN BUSQUEDA AJAX -->

    <script language="JavaScript" type="text/JavaScript">

        function Continuar() {
            jQuery('#botonContinuarSuperior').css('color', '#CCCCCC');
            jQuery('#botonContinuarSuperior').attr('onclick', 'return false;');
            jQuery('#botonContinuarInferior').css('color', '#CCCCCC');
            jQuery('#botonContinuarInferior').attr('onclick', 'return false;');

            document.FormSelect.submit();

            return false;
        }

    </script>
</head>
<body class="fancy" bgcolor="#FFFFFF" background="<?= $pathRaiz ?>imagenes/fondo_pantalla.gif" leftmargin="0"
      topmargin="0" marginwidth="0" marginheight="0" onload="jQuery('#txLineasDiccionario').focus()">
<FORM NAME="FormSelect" ACTION="ficha_importacion_copiar_pegar_paso2.php" METHOD="POST"
      style="margin-bottom:0;">
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
                                                        src="<? echo $pathRaiz ?>imagenes/flechitas_01.gif"
                                                        width="35"
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

                                                    <table width="100%" cellpadding="0" cellspacing="2">
                                                        <tr>
                                                            <td>
                                                                <span class="textoazul">
                                                                    &nbsp;- <?= $auxiliar->traduce("Cada nuevo registro en una linea de texto diferente", $administrador->ID_IDIOMA) . "." ?>
                                                                    <br><br>
                                                                    &nbsp;- <?= $auxiliar->traduce("Debe añadir los siguientes campos en orden y separados por una", $administrador->ID_IDIOMA) . " |:" ?>
                                                                    <br><br>
                                                                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;- <?= $auxiliar->traduce("Incidencia Sistema Tipo", $administrador->ID_IDIOMA) ?>
                                                                    <br>
                                                                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;- <?= $auxiliar->traduce("Incidencia Sistema Tipo Eng.", $administrador->ID_IDIOMA) . " (" . $auxiliar->traduce("Puede estar vacio", $administrador->ID_IDIOMA) . ")" ?>
                                                                    <br>
                                                                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;- <?= $auxiliar->traduce("Baja", $administrador->ID_IDIOMA) ?>
                                                                    <br><br>
                                                                    &nbsp;- <?= $auxiliar->traduce("Pulse intro para generar una linea nueva.", $administrador->ID_IDIOMA) ?>
                                                                    <br><br>
                                                                </span>
                                                            </td>
                                                        </tr>
                                                    </table>

                                                    <table width="100%" cellpadding="0" cellspacing="0">
                                                        <tr height="20px;">
                                                            <td>
                                                                <div align="left">
                                                                    <span class="textoazul">
                                                                        &nbsp;&nbsp;<a
                                                                            onClick="window.parent.jQuery.fancybox.close();"
                                                                            href="index.php?recordar_busqueda=1"
                                                                            class="senaladoazul">
                                                                            &nbsp;&nbsp;&nbsp;&nbsp;<?= $auxiliar->traduce("Volver", $administrador->ID_IDIOMA) ?>
                                                                            &nbsp;&nbsp;&nbsp;&nbsp;</a>
                                                                    </span>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div align="right">
                                                                    <span class="textoazul">
                                                                        <a href='#' class='senaladoverde'
                                                                           id="botonContinuarSuperior"
                                                                           onclick="return Continuar();">&nbsp;&nbsp;&nbsp;&nbsp;<?= $auxiliar->traduce("Continuar", $administrador->ID_IDIOMA) ?>
                                                                            &nbsp;&nbsp;&nbsp;&nbsp;</a>&nbsp;&nbsp;
                                                                    </span>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    </table>

                                                    <table width="98%" cellpadding="0" cellspacing="2"
                                                           class="linealrededor">
                                                        <tr>
                                                            <td height="19" bgcolor="#FFF" align="center"
                                                                style="padding-right:4px;">
                                                                <textarea name="txLineasCampos"
                                                                          id="txLineasDiccionario"
                                                                          class="copyright" rows="25"
                                                                          style="resize:none; width:100%;" placeholder="<?= $txLineasCamposEjemplo ?>"><?= $txLineasCampos ?></textarea>
                                                            </td>
                                                        </tr>
                                                    </table>

                                                    <table width="100%" cellpadding="0" cellspacing="0">
                                                        <tr>
                                                            <td>
                                                                <table width="100%" cellpadding="0" cellspacing="0">
                                                                    <tr height="20px;">
                                                                        <td>
                                                                            <div align="left">
                                                                                <span class="textoazul">
                                                                                    &nbsp;&nbsp;<a
                                                                                        onClick="window.parent.jQuery.fancybox.close();"
                                                                                        href="index.php?recordar_busqueda=1"
                                                                                        class="senaladoazul">
                                                                                        &nbsp;&nbsp;&nbsp;&nbsp;<?= $auxiliar->traduce("Volver", $administrador->ID_IDIOMA) ?>
                                                                                        &nbsp;&nbsp;&nbsp;&nbsp;</a>
                                                                                </span>
                                                                            </div>
                                                                        </td>
                                                                        <td>
                                                                            <div align="right">
                                                                                <span class="textoazul">
                                                                                    <a href='#' class='senaladoverde'
                                                                                       id="botonContinuarInferior"
                                                                                       onclick="return Continuar();">
                                                                                        &nbsp;&nbsp;&nbsp;&nbsp;<?= $auxiliar->traduce("Continuar", $administrador->ID_IDIOMA) ?>
                                                                                        &nbsp;&nbsp;&nbsp;&nbsp;</a>&nbsp;&nbsp;
                                                                                </span>
                                                                            </div>
                                                                        </td>
                                                                    </tr>
                                                                </table>
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
