<? //print_r($_REQUEST); //die;
// PATHS DE LA WEB
$pathRaiz   = "./";
$pathClases = "../";

// INCLUDES DE LIBRERIAS PROPIAS
require_once $pathClases . "lib/basedatos.php";
require_once $pathClases . "lib/administrador.php";
require_once $pathClases . "lib/html.php";
require_once $pathClases . "lib/auxiliar.php";
require_once $pathClases . "lib/navegar.php";
require_once $pathClases . "lib/comprobar.php";
require_once $pathClases . "lib/observaciones_sistema.php";
require_once $pathClases . "lib/necesidad.php";
require_once $pathClases . "lib/aviso.php";
require_once $pathClases . "lib/orden_transporte.php";


session_start();
include $pathRaiz . "seguridad_admin.php";

$tituloPag = $auxiliar->traduce("Observaciones", $administrador->ID_IDIOMA);
$tituloNav = ucfirst( (string)$auxiliar->traduce($tipoObjeto, $administrador->ID_IDIOMA)) . " >> " . $auxiliar->traduce("Observaciones", $administrador->ID_IDIOMA) . ($subcategoria != "" ? " >> " . $auxiliar->traduce($subcategoria, $administrador->ID_IDIOMA) : "");

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <? require_once $pathClases . "lib/gral_js.php"; ?>
    <script type="text/javascript" language="javascript">

        function Continuar() {
            document.Form.submit();
        }

    </script>

</head>
<body class="fancy" bgcolor="#FFFFFF" background="<? echo $pathRaiz ?>imagenes/fondo_pantalla.gif" leftmargin="0"
      topmargin="0" marginwidth="0" marginheight="0">
<form method="post" name="Form" action="ficha_destinatario_chat.php">
    <input type="hidden" name="tipoObjeto" value="<?= $tipoObjeto ?>">
    <input type="hidden" name="idObjeto" value="<?= $idObjeto ?>">
    <input type="hidden" name="subTipoObservacion" value="<?= $subTipoObservacion ?>">

    <? $navegar->GenerarCamposOcultosForm(); ?>
    <table width="100%" border="0" align="center" cellpadding="0" cellspacing="0" background="">
        <tr>
            <td align="center" valign="top">
                <table width="100%" border="0" cellpadding="0" cellspacing="0">
                    <tr>
                        <td height="3" colspan="2" align="center" valign="top" bgcolor="#7A0A0A" class="linearriba">
                            <img src="<? echo $pathRaiz ?>imagenes/transparente.gif" width="10" height="3"></td>
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
                                                            width="35" height="23"></td>
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
                                                            <td width="35" bgcolor="#982a29"
                                                                class="lineabajoarriba">&nbsp;
                                                            </td>
                                                            <td width="224" bgcolor="#982a29"
                                                                class="lineabajoarriba" colspan="2">
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
                                                    &nbsp;
                                                </td>
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
                                                                &nbsp;
                                                            </td>
                                                            <td width="100%" align="left" bgcolor="#D9E3EC">
                                                                <table width="97%" border="0" cellpadding="0"
                                                                       cellspacing="0">

                                                                    <tr>
                                                                        <td>
                                                                            <table width="800" align="center" border="0"
                                                                                   cellspacing="0"
                                                                                   cellpadding="1">
                                                                                <tr>
                                                                                    <td align="left" width="41%"></td>
                                                                                    <td class="copyright"
                                                                                        valign="middle">
                                                                                        <input class="textoazul"
                                                                                               type="radio"
                                                                                               name="chNuevoUsuario"
                                                                                               id="chUsuarioSGA"
                                                                                               value="usuario_sga"
                                                                                               checked="checked"/>
                                                                                        <label class="copyright"
                                                                                               title="<?= $auxiliar->traduce("Tomar Usuario SGA", $administrador->ID_IDIOMA) ?>"><?= $auxiliar->traduce("Tomar Usuario SGA", $administrador->ID_IDIOMA) ?>
                                                                                            <br></label>
                                                                                        <br>
                                                                                        <input class="textoazul"
                                                                                               type="radio"
                                                                                               name="chNuevoUsuario"
                                                                                               id="chNuevoUsuario"
                                                                                               value="nuevo_contacto"
                                                                                               checked="checked"/>
                                                                                        <label class="copyright"
                                                                                               title="<?= $auxiliar->traduce("Introducir Manualmente", $administrador->ID_IDIOMA) ?>"><?= $auxiliar->traduce("Introducir Manualmente", $administrador->ID_IDIOMA) ?>
                                                                                            <br></label>
                                                                                    </td>
                                                                                    <td align="left"
                                                                                        class="textoazul">
                                                                                    </td>
                                                                                </tr>
                                                                            </table>
                                                                        </td>
                                                                    </tr>

                                                                </table>
                                                            </td>
                                                            <td width="4" bgcolor="#D9E3EC" class="lineaderecha">
                                                                &nbsp;
                                                            </td>
                                                        </tr>

                                                        <tr bgcolor="#D9E3EC">
                                                            <td height="5" colspan="3" bgcolor="#D9E3EC"
                                                                class="lineabajodereizq"><img
                                                                        src="<? echo $pathRaiz ?>imagenes/transparente.gif"
                                                                        width="10" height="5"></td>
                                                        </tr>
                                                    </table>
                                                    <img src="<? echo $pathRaiz ?>imagenes/transparente.gif"
                                                         width="10" height="5"></td>
                                                <td width="20" align="center" valign="bottom"
                                                    class="lineaizquierda">&nbsp;
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="left" valign="top" bgcolor="#B3C7DA">
                                        <table width="100%" border="0" cellpadding="0" cellspacing="0"
                                               class="lineabajo">
                                            <tr>
                                                <td valign="middle" height="25" align="left"
                                                    class="textoazul">
                                                        <span class="textoazul">
                                                            &nbsp;
                                                            <a class="senaladoazul" href="#"
                                                               onClick="parent.jQuery.fancybox.close();">&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $auxiliar->traduce("Volver", $administrador->ID_IDIOMA); ?>&nbsp;&nbsp;&nbsp;&nbsp;
                                                            </a>
                                                        </span>
                                                </td>
                                                <td align="right" width="50%">
                                                        <span class="textoverde">
                                                            <a class="senaladoverde" href="#"
                                                               onClick="Continuar();">&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $auxiliar->traduce("Continuar", $administrador->ID_IDIOMA); ?>&nbsp;&nbsp;&nbsp;&nbsp;
                                                            </a>
                                                            &nbsp;&nbsp;
                                                        </span>
                                                </td>
                                            </tr>
                                        </table>
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