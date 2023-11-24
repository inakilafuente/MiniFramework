<?
// PATHS DE LA WEB
$pathRaiz   = "./";
$pathClases = "../";


//CARPETA IMAGENES SEGUN IDIOMA
$carpeta_imagenes = "imagenes";

require_once $pathClases . "lib/globales.php";
require_once $pathClases . "lib/basedatos.php";
require_once $pathClases . "lib/auxiliar.php";
require_once $pathClases . "lib/administrador.php";

// NOS CONECTAMOS
$bd = new basedatos($host, $nombrebd, $usuario, $password);
$bd->conectar();

$auxiliar      = new auxiliar();
$administrador = new administrador();

$_SESSION['auxiliar']      = $auxiliar;
$_SESSION['administrador'] = $administrador;
$_SESSION['bd']            = $bd;

// TITULO Y TEXTO EN LA PAGINA ERROR
$tituloErr = "Error";
if ($TipoError == "NoEncontrado"):
    $textoErr = $auxiliar->traduce("Se esta intentando acceder a un registro que no existe en la base de datos", "ESP");
    $textoErr .= "<br>" . $auxiliar->traduce("Se esta intentando acceder a un registro que no existe en la base de datos", "ENG");
elseif ($TipoError == "DatosErroneos"):
    $textoErr = $auxiliar->traduce("Datos de acceso a la Herramienta Web incorrectos", "ESP");
    $textoErr .= "<br>" . $auxiliar->traduce("Datos de acceso a la Herramienta Web incorrectos", "ENG");
elseif ($TipoError == "CampoCodigoObligatorio"):
    $textoErr = $auxiliar->traduce("Se esta intentando acceder a un registro que no existe en la base de datos", "ESP");
    $textoErr .= "<br>" . $auxiliar->traduce("Se esta intentando acceder a un registro que no existe en la base de datos", "ENG");
elseif ($TipoError == "CodigoErroneo"):
    $textoErr = $auxiliar->traduce("El Código Almacén no existe en la base de datos", "ESP");
    $textoErr .= "<br>" . $auxiliar->traduce("El Código Almacén no existe en la base de datos", "ENG");
elseif ($TipoError == "UsuarioBloqueado"):
    $textoErr = $auxiliar->traduce("En estos momentos su cuenta de usuario esta bloqueada", "ESP");
    $textoErr .= "<br>" . $auxiliar->traduce("En estos momentos su cuenta de usuario esta bloqueada", "ENG");
elseif ($TipoError == "UsuarioBloqueadoAlerta"):
    $textoErr = $auxiliar->traduce("Se ha superado el límite de intentos permitidos", "ESP") . "." . "<br>" . $auxiliar->traduce("Por razones de seguridad la cuenta quedará bloqueada temporalmente", "ESP");
    $textoErr .= "<br>" . $auxiliar->traduce("Se ha superado el límite de intentos permitidos", "ESP") . "." . "<br>" . $auxiliar->traduce("Por razones de seguridad la cuenta quedará bloqueada temporalmente", "ENG");
elseif ($TipoError == "NavegadorNoCompatible"):
    $textoErr = $auxiliar->traduce("Su navegador no es compatible con esta aplicacion. Utilice Internet Explorer version 11 o superior", "ESP");
    $textoErr .= "<br>" . $auxiliar->traduce("Su navegador no es compatible con esta aplicacion. Utilice Internet Explorer version 11 o superior", "ENG");
elseif ($TipoError == "PerfilNoAdmitido"):
    $textoErr = $auxiliar->traduce("TIC Informa: 14 y 15 de agosto no habrá acceso a los sistemas y comunicaciones corporativas", "ESP");
    $textoErr .= "<br>" . $textoErr = $auxiliar->traduce("TIC Informa: 14 y 15 de agosto no habrá acceso a los sistemas y comunicaciones corporativas", "ENG");
elseif ($TipoError == "DatosErroneosLoginAcciona"):
    $textoErr = $auxiliar->traduce("Los datos de login obtenidos de Acciona son incorrectos", $administrador->ID_IDIOMA) . ".";
elseif ($TipoError == "ErrorEjecutarSql"):
    $textoErr = $this->msje_error;
elseif ($TipoError == "SistemaBloqueado"):
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
                                                        <td align="center" valign="middle"><a href="#" id="botonVolver"
                                                                                              onclick="javascript:history.go(-1);return false;"
                                                                                              class="senaladoazul">
                                                                &nbsp;&nbsp;&nbsp;&nbsp;<?= $auxiliar->traduce("Volver", $administrador->ID_IDIOMA); ?>
                                                                &nbsp;&nbsp;&nbsp;&nbsp;</a></td>
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
