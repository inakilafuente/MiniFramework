<?
//PATHS DE LA WEB
$pathRaiz = "./";
$pathClases = "../";
$paginaActual = "bienvenida.php";

//INCLUDES DE LIBRERIAS PROPIAS
require_once $pathClases . "lib/basedatos.php";
require_once $pathClases . "lib/html.php";
require_once $pathClases . "lib/administrador.php";
require_once $pathClases . "lib/auxiliar.php";

session_start();
include "seguridad_admin.php";

$_SESSION['estado_menu'] = '1';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <? require_once $pathClases . "lib/gral_js.php"; ?>
    <link href="alerta" rel="stylesheet" type="text/css">
</head>
<body bgcolor="#FFFFFF" background="imagenes/fondo_pantalla.gif" leftmargin="0" topmargin="0" marginwidth="0"
      marginheight="0" style="height:100%">
<table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
    <tr>
        <td height="10" align="center" valign="top">
            <? include "tabla_superior.php"; ?>
        </td>
    </tr>
    <tr>
        <td align="center" valign="top">
            <table width="100%" border="0" cellpadding="0" cellspacing="0">
                <tr>
                    <td height="3" colspan="2" align="center" valign="top" bgcolor="#7A0A0A" class="linearriba"><img
                            src="imagenes/transparente.gif" width="10" height="3"></td>
                </tr>
                <tr>
                    <? $ZonaTabla = $auxiliar->traduce("Bienvenida", $administrador->ID_IDIOMA);
                    include "tabla_izqda.php"; ?>
                    <td align="left" valign="top">
                        <table width="100%" border="0" cellpadding="0" cellspacing="0">
                            <tr>
                                <td height="23">
                                    <table width="100%" height="23" border="0" cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td width="25" class="linearriba">
                                                <img src="imagenes/flechitas_01.gif"
                                                     width="35" height="23">
                                            </td>
                                            <td width="469" align="left" valign="middle" bgcolor="#B3C7DA"
                                                class="linearriba">
                                                <table width="440" border="0" cellspacing="0" cellpadding="0">
                                                    <tr>
                                                        <td align="left"
                                                            class="alertas">
                                                            <?= strtoupper( (string)$auxiliar->traduce("Acceso Correcto", $administrador->ID_IDIOMA)) ?>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                            <td width="25">
                                                <img src="imagenes/esquina.gif" width="25" height="24">
                                            </td>
                                            <td bgcolor="#7A0A0A">
                                                <table width="235" height="23" border="0" cellpadding="0"
                                                       cellspacing="0">
                                                    <tr>
                                                        <td width="20">&nbsp;</td>
                                                        <td align="left" class="existalert" width="190" colspan=2>
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
                                <td height="163" align="left" valign="top" bgcolor="#2E8AF0" class="linearriba">
                                    <table width="100%" height="163" border="0" cellpadding="0" cellspacing="0"
                                           background="imagenes/fondo_proveedor4.jpg">
                                        <tr>
                                            <td height="85" align="center" valign="middle" bgcolor="#B3C7DA"></td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td width="60%" height="179" align="left" valign="top" class="linearriba lineabajo"
                                    style="background:#B3C7DA; padding:0px !important">
                                    <table align="center" height="179" border="0" cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td rowspan="2" width="20%" bgcolor="#B3C7DA">&nbsp;</td>
                                            <td width="464" height="55" class="lineaderecha lineaizquierda">
                                                <img src="imagenes/fondo_esquina3.gif" width="464" height="55">
                                            </td>
                                            <td rowspan="2" width="20%" bgcolor="#B3C7DA">&nbsp;</td>
                                            <!--<td rowspan="2">&nbsp;</td>-->
                                        </tr>
                                        <tr>
                                            <td width="464" class="lineaderecha lineaizquierda">
                                                <table width="464" height="124" border="0" cellpadding="0"
                                                       cellspacing="0" background="imagenes/fondo_proveedor3.jpg">
                                                    <tr>
                                                        <td width="194" rowspan="2">&nbsp;</td>
                                                        <td height="50" colspan="2" valign="top" class="textoazul">
                                                            <center>
                                                                <strong>
                                                                    <font color="#333333">
                                                                        <? echo "$administrador->NOMBRE" ?>
                                                                    </font>
                                                                </strong>
                                                            </center>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td width="104" valign="top" class="textoazul">
                                                            <strong>
                                                                <?= $auxiliar->traduce("Último Acceso", $administrador->ID_IDIOMA) ?>
                                                            </strong>
                                                        </td>
                                                        <td width="166" valign="top" class="textoazul">
                                                            <p>
                                                                <?= $auxiliar->traduce("Desde", $administrador->ID_IDIOMA) ?>
                                                                : <font color="#333333">
                                                                    <strong>
                                                                        <? echo $administrador->ULTIMA_IP ?>
                                                                    </strong>
                                                                </font>
                                                                <br>
                                                                <?= $auxiliar->traduce("Fecha", $administrador->ID_IDIOMA) ?>
                                                                : <img width="2" src="imagenes/transparente.gif">
                                                                <font color="#333333">
                                                                    <strong>
                                                                        <? echo $auxiliar->fechaFmtoEsp($administrador->ULTIMA_FECHA); ?>
                                                                    </strong>
                                                                </font>
                                                                <br>
                                                                <?= $auxiliar->traduce("Hora", $administrador->ID_IDIOMA) ?>
                                                                : <img width="8" src="imagenes/transparente.gif">
                                                                <font color="#333333">
                                                                    <strong>
                                                                        <? echo $auxiliar->fechaFmtoEspHora($administrador->ULTIMA_FECHA, true, true); ?>
                                                                    </strong>
                                                                </font>
                                                            </p>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td height="40" align="left" valign="top" bgcolor="#B3C7DA" class="lineabajo">
                                    &nbsp;</td>
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