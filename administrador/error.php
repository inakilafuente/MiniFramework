<?
global $administrador;
global $bd;
global $auxiliar;

// PATHS DE LA WEB
$pathRaiz   = "./";
$pathClases = "../";

$tituloErrArriba = strtoupper( (string)$auxiliar->traduce("Error Acceso Herramienta", $administrador->ID_IDIOMA)) . ".";
$tituloErr       = "ERROR";
if ($TipoError == "NoEncontrado"):
    $textoErr = $auxiliar->traduce("Se esta intentando acceder a un registro que no existe en la base de datos", $administrador->ID_IDIOMA) . ".";
elseif ($TipoError == "DatosErroneos"):
    $textoErr = $auxiliar->traduce("Datos de acceso a la Herramienta Web incorrectos", $administrador->ID_IDIOMA) . ".";
elseif ($TipoError == "UsuarioBloqueado"):
    $textoErr = $auxiliar->traduce("En estos momentos su cuenta de usuario esta bloqueada", $administrador->ID_IDIOMA) . ".";
elseif ($TipoError == "UsuarioBloqueadoAlerta"):
    $textoErr = $auxiliar->traduce("Se ha superado el límite de intentos permitidos", $administrador->ID_IDIOMA) . "." . "<br>" . $auxiliar->traduce("Por razones de seguridad la cuenta quedará bloqueada temporalmente", $administrador->ID_IDIOMA) . ".";
elseif ($TipoError == "ErrorEjecutarSql"):
    $textoErr = $this->msje_error;
elseif ($TipoError == "NoEncuentraDocumento"):
    $textoErr = $auxiliar->traduce("No se encuentra el documento" . $msjeError, $administrador->ID_IDIOMA) . ".";
elseif ($TipoError == "DatosErroneosLoginAcciona"):
    $textoErr = $auxiliar->traduce("Los datos de login obtenidos de Acciona son incorrectos", $administrador->ID_IDIOMA) . ".";
endif;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <? require_once $pathClases . "lib/gral_js.php"; ?>
    <link href="alerta" rel="stylesheet" type="text/css">
</head>
<body bgcolor="#FFFFFF" background="imagenes/fondo_pantalla.gif" leftmargin="0" topmargin="0" marginwidth="0"
      marginheight="0">
<table width="100%" height="100%" border="0" align="center" cellpadding="0" cellspacing="0">
    <tr>
        <td height="10" align="center" valign="top">
        </td>
    </tr>
    <tr>
        <td align="center" valign="top">
            <table width="100%" height="100%" border="0" cellpadding="0" cellspacing="0">
                <tr>
                    <td height="3" colspan="2" align="center" valign="top" bgcolor="#7A0A0A" class="linearriba"><img
                            src="imagenes/transparente.gif" width="10" height="3"></td>
                </tr>
                <tr>
                    <? $ZonaTabla = $auxiliar->traduce("Bienvenida", $administrador->ID_IDIOMA);
                    //   include "tabla_izqda.php"; ?>
                    <td align="left" valign="top" bgcolor="#FFFFFF">
                        <table width="100%" height="100%" border="0" cellpadding="0" cellspacing="0">
                            <tr>
                                <td height="23">
                                    <table width="100%" height="23" border="0" cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td width="25" class="linearriba"><img src="imagenes/flechitas_01.gif"
                                                                                   width="35" height="23"></td>
                                            <td width="469" align="left" valign="middle" bgcolor="#B3C7DA"
                                                class="linearriba">
                                                <table width="440" border="0" cellspacing="0" cellpadding="0">
                                                    <tr>
                                                        <td align="left"
                                                            class="alertas"><? echo $tituloErrArriba ?></td>
                                                    </tr>
                                                </table>
                                            </td>
                                            <td width="25"><img src="imagenes/esquina.gif" width="25" height="24"></td>
                                            <td bgcolor="#7A0A0A">
                                                <table width="235" height="23" border="0" cellpadding="0"
                                                       cellspacing="0">
                                                    <tr>
                                                        <td width="20">&nbsp;</td>
                                                        <td align="left" class="existalert">
                                                        </td>
                                                        <td width="60"></td>
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
                                                        <td width="120" bgcolor="#982a29" class="lineabajoarriba"
                                                            colspan=2>
                                                            </font></td>
                                                        <td width="20" class="lineabajoarriba"><img
                                                                src="imagenes/esquina_02.gif" width="20" height="20">
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                            <td bgcolor="#B3C7DA" class="lineabajoarriba">&nbsp;</td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <tr bgcolor="#D9E3EC">
                                <td height="280" align="left" valign="top" bgcolor="#D9E3EC" class="lineabajo">
                                    <table width="100%" height="280" border="0" cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td align="center" valign="bottom">
                                                <table width="100%" height="220" border="0" cellpadding="0"
                                                       cellspacing="0">
                                                    <tr align="center" valign="middle">
                                                        <td height="20">
                                                            <table width="100" height="20" border="0" cellpadding="0"
                                                                   cellspacing="0">
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
                                                        <td height="124" align="center" valign="middle"
                                                            bgcolor="#B3C7DA">
                                                            <table width="100%" height="124" border="0" cellpadding="0"
                                                                   cellspacing="0">
                                                                <tr>
                                                                    <td align="right" valign="middle">
                                                                        <table width="100%" height="124" border="0"
                                                                               cellpadding="0" cellspacing="0">
                                                                            <tr>
                                                                                <td height="9"><img
                                                                                        src="imagenes/transparente.gif"
                                                                                        width="10" height="9"></td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td height="115" bgcolor="#A80D0D">
                                                                                    &nbsp;</td>
                                                                            </tr>
                                                                        </table>
                                                                    </td>
                                                                    <td width="212" align="center" valign="middle"
                                                                        bgcolor="#A80D0D">
                                                                        <table width="212" height="124" border="0"
                                                                               cellpadding="0" cellspacing="0"
                                                                               background="imagenes/fondo_error2.gif">
                                                                            <tr>
                                                                                <td>&nbsp;</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td align="center" valign="middle"><a
                                                                                        href="#" class="senaladoazul"
                                                                                        onClick="history.back(); return false;">
                                                                                        &nbsp;&nbsp;&nbsp;&nbsp;<?= $auxiliar->traduce("Volver", $administrador->ID_IDIOMA) ?>
                                                                                        &nbsp;&nbsp;&nbsp;&nbsp;</a>
                                                                                </td>
                                                                            </tr>
                                                                        </table>
                                                                    </td>
                                                                    <td align="left" valign="middle">
                                                                        <table width="100%" height="124" border="0"
                                                                               cellpadding="0" cellspacing="0">
                                                                            <tr>
                                                                                <td height="37"><img
                                                                                        src="imagenes/transparente.gif"
                                                                                        width="10" height="37"></td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td height="87" bgcolor="#A80D0D">
                                                                                    &nbsp;</td>
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
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td align="left" valign="top" bgcolor="#B3C7DA" class="lineabajo">&nbsp;</td>
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
</body>
</html>
