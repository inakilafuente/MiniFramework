<?
// PATHS DE LA WEB
$pathRaiz   = "./";
$pathClases = "../";


//CARPETA IMAGENES SEGUN IDIOMA
$carpeta_imagenes = "imagenes";

require_once $pathClases . "lib/globales.php";
require_once $pathClases . "lib/auxiliar.php";
require_once $pathClases . "lib/administrador.php";
require_once $pathClases . "lib/basedatos.php";

//EXTRAEMOS VARIABLES
if (!empty($_SESSION)) extract($_SESSION);
if (!empty($_REQUEST)) extract($_REQUEST);
if (!empty($_FILES)) extract($_FILES);


$auxiliar = new auxiliar();
global $administrador;
$bd = new basedatos();

$_SESSION['bd']            = $bd;
$_SESSION['auxiliar']      = $auxiliar;
$_SESSION['administrador'] = $administrador;

$bd->conectar();

$sqlBloqueoSistema    = "SELECT * FROM AVISO_SISTEMA_BLOQUEADO";
$resultBloqueoSistema = $bd->ExecSQL($sqlBloqueoSistema);
$rowBloqueoSistema    = $bd->SigReg($resultBloqueoSistema);

if ($administrador->ID_IDIOMA == 'ENG'):
    $descripcion = $rowBloqueoSistema->TEXTO_ENG;
else:
    $descripcion = $rowBloqueoSistema->TEXTO;
endif;

$mensaje .= '<br/><br/>' . $auxiliar->traduce('El sistema permanecerá bloqueado', $administrador->ID_IDIOMA);
$mensaje .= '<br/><br/>' . $auxiliar->traduce('Desde', $administrador->ID_IDIOMA) . ': ' . ($auxiliar->fechaFmtoEspHora($rowBloqueoSistema->FECHA_INICIO_BLOQUEO . ' ' . $rowBloqueoSistema->HORA_INICIO_BLOQUEO, true, false, false));
$mensaje .= '<br/>' . $auxiliar->traduce('Hasta', $administrador->ID_IDIOMA) . ': ' . ($auxiliar->fechaFmtoEspHora($rowBloqueoSistema->FECHA_FIN_BLOQUEO . ' ' . $rowBloqueoSistema->HORA_FIN_BLOQUEO, true, false, false));
$mensaje .= '<br/><br/><span style="white-space: break-spaces;">' . '<br/><br/>' . $descripcion . '</span><br/><br/>';


// TITULO Y TEXTO EN LA PAGINA ERROR
$tituloErr = "Error";
if ($TipoError == "SistemaBloqueado"):
    $textoErr = $mensaje;
endif;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <? require_once $pathClases . "lib/gral_js.php"; ?>
    <script language="javascript" type="text/javascript">
        $(document).ready(function () {
            $('#botonVolver').focus();
        })
    </script>
</head>
<body bgcolor="#FFFFFF" background="imagenes/fondo_pantalla.gif" leftmargin="0" topmargin="0" marginwidth="0"
      marginheight="0">
<table width="100%" height="100%" border="0" align="center" cellpadding="0" cellspacing="0">
    <tr>
        <td align="center" valign="middle">
            <table width="100%" height="446" border="0" align="center" cellpadding="0" cellspacing="0">
                <tr>
                    <td rowspan="2">&nbsp;</td>
                    <td height="36" align="right" bgcolor="#FFFFFF" class="copyright">&nbsp;</td>
                    <td rowspan="2">&nbsp;</td>
                </tr>
                <tr>
                    <td height="23" align="center" bgcolor="#FFFFFF">
                        <table width="936" height="23" border="0" cellpadding="0" cellspacing="0">
                            <tr>
                                <td width="292">&nbsp;</td>
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
                               background="<? echo $carpeta_imagenes ?>/<?= FONDO_CABECERA; ?>">
                            <tr>
                                <td height="15" align="right">&nbsp;</td>
                                <td width="24" rowspan="2"><img src="imagenes/transparente.gif" width="24" height="10">
                                </td>
                            </tr>
                            <tr>
                                <td align="right" valign="top">&nbsp;</td>
                            </tr>
                        </table>
                    </td>
                    <td width="33%" align="center" bgcolor="#000000">&nbsp;</td>
                </tr>
                <tr align="center" valign="middle">
                    <td colspan="3" bgcolor="#FFFFFF">
                        <table width="100%" height="220" border="0" cellpadding="0" cellspacing="0">
                            <tr align="center" valign="middle">
                                <td height="20">
                                    <table width="100" height="20" border="0" cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td align="center" valign="middle" bgcolor="#B3C7DA"
                                                class="alertas2"><? echo $tituloErr ?></td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <tr align="center" valign="middle">
                                <td bgcolor="#B3C7DA" class="textoazul"><strong>
                                        <? echo $textoErr ?>
                                    </strong></td>
                            </tr>
                            <tr>
                                <td height="124" align="center" valign="middle" bgcolor="#B3C7DA">
                                    <table width="100%" height="124" border="0" cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td align="right" valign="middle">
                                                <table width="100%" height="124" border="0" cellpadding="0"
                                                       cellspacing="0">
                                                    <tr>
                                                        <td height="9"><img src="imagenes/transparente.gif" width="10"
                                                                            height="9"></td>
                                                    </tr>
                                                    <tr>
                                                        <td height="115" bgcolor="#A80D0D">&nbsp;</td>
                                                    </tr>
                                                </table>
                                            </td>
                                            <td width="212" align="center" valign="middle">
                                                <table width="212" height="124" border="0" cellpadding="0"
                                                       cellspacing="0" background="imagenes/fondo_error2.gif">
                                                    <tr>
                                                        <td>&nbsp;</td>
                                                    </tr>
                                                    <tr>
                                                        <td align="center" valign="middle">&nbsp;</td>
                                                    </tr>
                                                </table>
                                            </td>
                                            <td align="left" valign="middle">
                                                <table width="100%" height="124" border="0" cellpadding="0"
                                                       cellspacing="0">
                                                    <tr>
                                                        <td height="37"><img src="imagenes/transparente.gif" width="10"
                                                                             height="37"></td>
                                                    </tr>
                                                    <tr>
                                                        <td height="87" bgcolor="#A80D0D">&nbsp;</td>
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
