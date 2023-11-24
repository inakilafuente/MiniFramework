<?
header('Content-Type: text/html;charset=windows-1252');

// PATHS DE LA WEB
$pathRaiz   = "./";
$pathClases = "../";
require_once $pathClases . "lib/globales.php";
require_once $pathClases . "lib/basedatos.php";
require_once $pathClases . "lib/auxiliar.php";
require_once $pathClases . "lib/administrador.php";
require_once $pathClases . "lib/xajax25_php7/xajax_core/xajax.inc.php";

session_start();

$paginaActual = "index.php";

$bd       = new basedatos();
$auxiliar = new auxiliar();

if ($administrador == NULL):
    $administrador = new administrador();
endif;

$_SESSION["administrador"] = $administrador;

$url_azure = "https://login.microsoftonline.com/" . AAD_ID_TENANT . "/oauth2/v2.0/authorize?client_id=" . AAD_ID_CLIENT . "&response_type=code&redirect_uri=" . AAD_REDIRECT_URI . "&response_mode=query&scope=https://graph.microsoft.com/mail.read&state=12345"

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <? require_once $pathClases . "lib/gral_js.php"; ?>
    <script language="JavaScript">

        function loginAcciona(url_azure) {
            window.location.href = url_azure;
        }

        function loginSCS() {
            window.location.href = "index.php";
        }

    </script>
</head>
<body bgcolor="#FFFFFF" background="imagenes/fondo_pantalla.gif" leftmargin="0" topmargin="0" marginwidth="0"
      marginheight="0" style="height:100%; overflow:hidden">
<table width="100%" height="100%" border="0" align="center" cellpadding="0" cellspacing="0">
    <tr>
        <td align="center" valign="middle">
            <table width="100%" height="416" border="0" align="center" cellpadding="0" cellspacing="0">
                <tr>
                    <td rowspan="2">&nbsp;</td>
                    <td height="36" align="right" bgcolor="#FFFFFF" class="copyright">

                        <table width="152" height="1" border="0" cellpadding="0" cellspacing="0">
                            <tr>
                                <td width="50" height="1"></td>
                                <td width="15" height="1" align="center" valign="bottom"></td>
                                <td height="1"></td>
                                <td width="24" height="1" align="center" valign="middle">&nbsp;</td>
                                <td width="24" height="1">&nbsp;</td>
                            </tr>
                        </table>

                    </td>
                    <td rowspan="2">&nbsp;</td>
                </tr>
                <tr>
                    <td height="23" align="center" bgcolor="#FFFFFF">
                        <table width="936" height="23" border="0" cellpadding="0" cellspacing="0">
                            <tr>
                                <td width="292"></td>
                                <td align="right">
                                    <table width="152" height="23" border="0" cellpadding="0" cellspacing="0">
                                        <tr>
                                            <? /*<td align="right" width="110"><a href="mailto:<? echo $email_soporte ?>" class="enlace">Contacto</a></td>*/ ?>
                                            <td align="right" width="110">&nbsp;</td>
                                            <td width="24"><img src="imagenes/transparente.gif" width="24" height="10">
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td width="33%" height="113" align="center" bgcolor="#000000">&nbsp;</td>
                    <td align="center" bgcolor="#FFFFFF">
                        <table width="936" height="113" border="0" cellpadding="0" cellspacing="0"
                               background="imagenes/<?= FONDO_CABECERA; ?>">
                            <tr>
                                <td height="15" align="right">&nbsp;</td>
                                <td width="24" rowspan="2"><img src="imagenes/transparente.gif" width="24" height="10">
                                </td>
                            </tr>
                            <tr>
                                <td align="right" valign="top"></td>
                            </tr>
                        </table>
                    </td>
                    <td width="33%" align="center" bgcolor="#000000">&nbsp;</td>
                </tr>
                <tr align="center" valign="middle">
                    <td colspan="3" bgcolor="#FFFFFF" class="textoazul">
                        <?
                        // VEO SI LA HERRAMIENTA ESTA BLOQUEADA A TODOS LOS CLIENTES
                        if (BLOQUEAR_TODOS_CLIENTES == '1'): ?>
                            <font size=2 color="RED">
                                <blink><?= $auxiliar->traduce("Acceso a la Herramienta Bloqueado Temporalmente", $administrador->ID_IDIOMA) ?></blink>
                            </font>
                            <br>
                            <? echo $rowGral->MOTIVO_BLOQUEO_PROVS_CAST ?>
                        <? endif; ?>
                        <table width="100%" height="179" border="0" cellpadding="0" cellspacing="0">
                            <tr>
                                <td height="156" align="center" valign="middle">
                                    <table width="100%" height="156" border="0" cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td height="10" bgcolor="#FFFFFF"><img src="imagenes/transparente.gif"
                                                                                   width="10" height="10"></td>
                                        </tr>
                                        <tr>
                                            <td height="68" bgcolor="#B3C7DA">&nbsp;</td>
                                        </tr>
                                        <tr>
                                            <td bgcolor="#A7B9CB">&nbsp;</td>
                                        </tr>
                                    </table>
                                </td>
                                <td width="289" rowspan="2" align="center" valign="middle">
                                    <table width="289" height="179" border="0" cellpadding="0" cellspacing="0" background="imagenes/fondo_login.gif">
                                        <tr>
                                            <td height="99" align="center" valign="middle">
                                                <table width="218" height="99" border="0" cellpadding="0"
                                                       cellspacing="0">
                                                    <FORM name="FormDatos" method="post" action="comprobacion.php">
                                                        <tr>
                                                            <td height="15" colspan="2"><img
                                                                    src="imagenes/transparente.gif" width="10"
                                                                    height="15"></td>
                                                        </tr>
                                                        <tr>
                                                            <td align="center" colspan="2" class="textoazul">
                                                                <a href="#" id="aceptar"
                                                                   class="senaladoazul almacenTecnico"
                                                                   onClick="loginAcciona('<?= $url_azure ?>');return false">
                                                                    &nbsp;&nbsp;<?= $auxiliar->traduce("Login Acciona", $administrador->ID_IDIOMA) ?>
                                                                    &nbsp;&nbsp;&nbsp;
                                                                </a>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td align="center" colspan="2" class="textoazul">
                                                                <a href="#" id="borrar" class="senaladoazul"
                                                                   onClick="loginSCS();return false">
                                                                    &nbsp;&nbsp;&nbsp;<?= $auxiliar->traduce("Login SCS", $administrador->ID_IDIOMA) ?>
                                                                    &nbsp;&nbsp;&nbsp;
                                                                </a>
                                                            </td>
                                                        </tr>
                                                        <input type="submit" style="position:absolute; top:-999999px"/>
                                                    </FORM>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                                <td height="156" align="center" valign="middle">
                                    <table width="100%" height="156" border="0" cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td height="10" bgcolor="#FFFFFF"><img src="imagenes/transparente.gif"
                                                                                   width="10" height="10"></td>
                                        </tr>
                                        <tr>
                                            <td height="95" bgcolor="#B3C7DA">&nbsp;</td>
                                        </tr>
                                        <tr>
                                            <td bgcolor="#A7B9CB">&nbsp;</td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td height="23" align="center" valign="middle">&nbsp;</td>
                                <td height="23" align="center" valign="middle">&nbsp;</td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr align="center" valign="middle">
                    <td height="23" colspan="3" align="center" valign="middle" class="copyright">
                        <?
                        include "copyright_texto.php";
                        ?>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>