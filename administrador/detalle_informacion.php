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


session_start();
include $pathRaiz . "seguridad_admin.php";

$tituloPag = $auxiliar->traduce("Detalle Informacion", $administrador->ID_IDIOMA);
$tituloNav = ucfirst( (string)$auxiliar->traduce("Informacion", $administrador->ID_IDIOMA));
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <? require_once $pathClases . "lib/gral_js.php"; ?>
    <script type="text/javascript" language="javascript">
        jQuery(document).ready(function () {
            //DECLARO LAS VARIABLES A PINTAR
            var htmlTablaInsertar = "";

            //RECUPERO LOS VALORES A ESCRIBIR
            var arrayCampos = window.parent.jQuery("#<? echo $nombreArrayCampos; ?>").val();
            arrayCampos = arrayCampos.split("<? echo SEPARADOR_BUSQUEDA_MULTIPLE; ?>");
            var arrayValores = window.parent.jQuery("#<? echo $nombreArrayValores; ?>").val();
            arrayValores = arrayValores.split(";");
            <?if(isset($nombreArrayTipos)):?>
            var arrayTipos = window.parent.jQuery("#<? echo $nombreArrayTipos; ?>").val();
            arrayTipos = arrayTipos.split("<? echo SEPARADOR_BUSQUEDA_MULTIPLE; ?>");
            <?endif;?>

            //FILA CABECERA
            htmlTablaInsertar = "<tr align=\"center\" valign=\"middle\">";

            //COLUMNAS CABECERA
            for (i = 0; i < arrayCampos.length; i++) {
                htmlTablaInsertar = htmlTablaInsertar + "<td height=\"17\" bgcolor=\"#2E8AF0\" class=\"blanco\">" + arrayCampos[i] + "</td>";
            }

            //FIN CABECERA
            htmlTablaInsertar = htmlTablaInsertar + "</tr>";

            //COLUMNAS DATOS
            for (i = 0; i < arrayValores.length; i++) {
                //COLOR DE LA FILA
                if (i % 2 == 0) myColor = "#B3C7DA";
                else myColor = "#AACFF9";

                //LINEA DATOS
                htmlTablaInsertar = htmlTablaInsertar + "<tr align=\"center\" valign=\"middle\">";

                //EXTRAIGO EL ARRAY DE VALORES POR FILA A PINTAR
                var arrayValorColumnas = arrayValores[i].split("<? echo SEPARADOR_BUSQUEDA_MULTIPLE; ?>");

                //RECORRO LOS VALORES DE LAS COLUMNAS
                for (j = 0; j < arrayValorColumnas.length; j++) {
                    var alineacion = 'left';
                    if (typeof arrayTipos != "undefined" && arrayTipos != null && arrayTipos.length > 0) {
                        if (arrayTipos[j] == 'date') {
                            alineacion = 'center';
                        } else if (arrayTipos[j] == 'int') {
                            alineacion = 'right';
                        }

                    } else {
                        if (isNaN(arrayValorColumnas[j]) == false) {
                            alineacion = 'right';
                        }
                    }
                    htmlTablaInsertar = htmlTablaInsertar + "<td height=\"16\" align=\"" + alineacion + "\" bgcolor=\"" + myColor + "\" class=\"enlaceceldas espacioPequenoIzqDrch\">" + arrayValorColumnas[j] + "</td>";
                }

                //FIN LINEA DATOS
                htmlTablaInsertar = htmlTablaInsertar + "</tr>";
            }

            //INSERTO CODIGO HTML CREADO PARA AUTOMATIZAR LOS CHECKS
            jQuery("#InsertarTabla").append(htmlTablaInsertar);
        });

        function CerrarFancy() {
            window.parent.jQuery.fancybox.close();

            return false;
        }
    </script>
</head>
<body class="fancy" bgcolor="#FFFFFF" background="<? echo $pathRaiz ?>imagenes/fondo_pantalla.gif" leftmargin="0"
      topmargin="0" marginwidth="0" marginheight="0">
<form method="post" name="Form" action="detalle_informacion.php">
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
                                                <td width="100%" align="left" valign="middle" bgcolor="#B3C7DA"
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
                                                    <table width="100%" height="23" border="0" cellpadding="0"
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
                                                                class="lineabajoarriba">&nbsp;</td>
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
                                <tr>
                                    <td align="left" valign="top" bgcolor="#B3C7DA" class="lineabajo">
                                        <table width="100%" border="0" cellpadding="0" cellspacing="0"
                                               class="lineabajo">
                                            <tr class="lineabajo">
                                                <td align="center" bgcolor="#D9E3EC">
                                                    <img
                                                        src="<? echo $pathRaiz ?>imagenes/transparente.gif"
                                                        width="10" height="10">
                                                    <table width="98%" cellpadding="2" cellspacing="2"
                                                           class="linealrededor" id="InsertarTabla">
                                                    </table>
                                                    <img
                                                        src="<? echo $pathRaiz ?>imagenes/transparente.gif"
                                                        width="10" height="10">
                                                </td>
                                            </tr>
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