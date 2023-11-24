<?
global $administrador;
global $bd;
global $strError;
global $auxiliar;
global $idMovimiento;

// PATHS DE LA WEB
$pathRaiz   = "./";
$pathClases = "../";

$tituloPag = $auxiliar->traduce("Error", $administrador->ID_IDIOMA);
$tituloNav = $auxiliar->traduce("Error", $administrador->ID_IDIOMA) . " >> " . $auxiliar->traduce("Error", $administrador->ID_IDIOMA);

$tituloErr = "ERROR";

$textoErr == "";
include $pathRaiz . "errores_genericos.php";

if ($TipoError == "UsuarioProveedorSinProveedorAsignado"):
    $textoErr = $auxiliar->traduce("Su Perfil de usuario es de Proveedor pero no tiene un proveedor asignado", $administrador->ID_IDIOMA) . ".";
elseif ($TipoError == "UsuarioNoExiste"):
    $textoErr = $auxiliar->traduce("El usuario seleccionado no existe", $administrador->ID_IDIOMA) . ".";
elseif ($TipoError == "ContactoNoExiste"):
    $textoErr = $auxiliar->traduce("La persona de contacto no existe", $administrador->ID_IDIOMA) . ".";
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

<body bgcolor="#FFFFFF" background="<? echo $pathRaiz ?>imagenes/fondo_pantalla.gif" leftmargin="0" topmargin="0"
      marginwidth="0" marginheight="0">
<FORM NAME="Form" METHOD="POST">
    <table width="100%" height="100%" border="0" align="center" cellpadding="0" cellspacing="0">
        <tr>
            <td align="center" valign="top">
                <table width="100%" height="100%" border="0" cellpadding="0" cellspacing="0">
                    <tr>
                        <td height="3" colspan="2" align="center" valign="top" bgcolor="#7A0A0A" class="linearriba"><img
                                    src="<? echo $pathRaiz ?>imagenes/transparente.gif" width="10" height="3"></td>
                    </tr>
                    <tr>
                        <td align="left" valign="top" bgcolor="#FFFFFF"
                            background="<? echo $pathRaiz ?>imagenes/fondo_pantalla.gif">
                            <table width="100%" height="100%" border="0" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td height="23">
                                        <table width="100%" height="23" border="0" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td width="25" class="linearriba"><img
                                                            src="<? echo $pathRaiz ?>imagenes/flechitas_01.gif"
                                                            width="35"
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
                                                                &nbsp;
                                                            </td>
                                                            <td width="220" bgcolor="#982a29" class="lineabajoarriba"
                                                                colspan=2><font
                                                                        class="tituloNav"><? echo $tituloNav ?></font>
                                                            </td>
                                                            <td bgcolor="#B3C7DA" valign=top width="20"
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
                                    <td height="280" align="left" valign="top" bgcolor="#D9E3EC" class="lineabajo">
                                        <table width="100%" height="280" border="0" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td align="center" valign="bottom">
                                                    <table width="100%" height="220" border="0" cellpadding="0"
                                                           cellspacing="0">
                                                        <tr align="center" valign="middle">
                                                            <td height="20">
                                                                <table width="100" height="20" border="0"
                                                                       cellpadding="0" cellspacing="0">
                                                                    <tr>
                                                                        <td align="center" valign="middle"
                                                                            bgcolor="#B3C7DA"
                                                                            class="alertas2"><? echo $tituloErr ?></td>
                                                                    </tr>
                                                                </table>
                                                            </td>
                                                        </tr>
                                                        <tr align="center" valign="middle">
                                                            <td bgcolor="#B3C7DA" class="textoazul">
                                                                <strong> <? echo $textoErr; ?></strong></td>
                                                        </tr>
                                                        <tr>
                                                            <td height="124" align="center" valign="middle"
                                                                bgcolor="#B3C7DA">
                                                                <table width="100%" height="124" border="0"
                                                                       cellpadding="0" cellspacing="0">
                                                                    <tr>
                                                                        <td align="right" valign="middle">
                                                                            <table width="100%" height="124" border="0"
                                                                                   cellpadding="0" cellspacing="0">
                                                                                <tr>
                                                                                    <td height="9"><img
                                                                                                src="<? echo $pathRaiz ?>imagenes/transparente.gif"
                                                                                                width="10" height="9">
                                                                                    </td>
                                                                                </tr>
                                                                                <tr>
                                                                                    <td height="115" bgcolor="#A80D0D">
                                                                                        &nbsp;
                                                                                    </td>
                                                                                </tr>
                                                                            </table>
                                                                        </td>
                                                                        <td width="212" align="center" valign="middle"
                                                                            bgcolor="#A80D0D">
                                                                            <table width="212" height="124" border="0"
                                                                                   cellpadding="0" cellspacing="0"
                                                                                   background="<? echo $pathRaiz ?>imagenes/fondo_error2.gif">
                                                                                <tr>
                                                                                    <td>&nbsp;</td>
                                                                                </tr>
                                                                                <tr>
                                                                                    <td align="center" valign="middle">
                                                                                        <a id="botonVolver"
                                                                                           href="javascript:history.back()"
                                                                                           class="senaladoazul">&nbsp;&nbsp;&nbsp;&nbsp;<?= $auxiliar->traduce("Volver", $administrador->ID_IDIOMA) ?>
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
                                                                                                src="<? echo $pathRaiz ?>imagenes/transparente.gif"
                                                                                                width="10" height="37">
                                                                                    </td>
                                                                                </tr>
                                                                                <tr>
                                                                                    <td height="87" bgcolor="#A80D0D">
                                                                                        &nbsp;
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
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="left" height="40" valign="top" bgcolor="#B3C7DA" class="lineabajo">
                                        &nbsp;
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