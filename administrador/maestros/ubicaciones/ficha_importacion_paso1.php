<?
// PATHS DE LA WEB
$pathRaiz   = "../../";
$pathClases = "../../../";

// INCLUDES DE LIBRERIAS PROPIAS
require_once $pathClases . "lib/basedatos.php";
require_once $pathClases . "lib/administrador.php";
require_once $pathClases . "lib/html.php";
require_once $pathClases . "lib/navegar.php";
require_once $pathClases . "lib/auxiliar.php";

session_start();
include $pathRaiz . "seguridad_admin.php";

$tituloPag         = $auxiliar->traduce("Ubicaciones", $administrador->ID_IDIOMA);
$tituloNav         = $auxiliar->traduce("Maestros", $administrador->ID_IDIOMA) . " >> " . $auxiliar->traduce("Ubicaciones", $administrador->ID_IDIOMA);
$ZonaTablaPadre    = "Maestros";
$ZonaSubTablaPadre = "MaestrosSubmenuAlmacen";
$ZonaTabla         = "MaestrosUbicaciones";

// COMPRUEBA SI TIENE PERMISOS
if ($administrador->Hayar_Permiso_Perfil('ADM_MAESTROS_UBICACIONES') < 2):
    $html->PagError("SinPermisos");
endif;

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<? require_once $pathClases . "lib/gral_js.php"; ?>
<script language="JavaScript">
    $(document).ready(function () {
        jQuery('#botonVolver').focus();
    })
</script>

</head>
<body bgcolor="#FFFFFF" background="<? echo "$pathRaiz" ?>imagenes/fondo_pantalla.gif" leftmargin="0" topmargin="0"
      marginwidth="0" marginheight="0">
<FORM NAME="FormSelect" ACTION="ficha_importacion_paso2.php" METHOD="POST" enctype="multipart/form-data">
    <input type=hidden name="accion" value="importar_material_almacen">
    <table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
        <tr>
            <td height="10" align="center" valign="top">
                <? include $pathRaiz . "tabla_superior.php"; ?>
            </td>
        </tr>
        <tr>
            <td align="center" valign="top">
                <table width="100%" border="0" cellpadding="0" cellspacing="0">
                    <tr>
                        <td height="3" colspan="2" align="center" valign="top" bgcolor="#7A0A0A" class="linearriba"><img
                                src="<? echo $pathRaiz ?>imagenes/transparente.gif" width="10" height="3"></td>
                    </tr>
                    <tr>
                        <? include $pathRaiz . "tabla_izqda.php"; ?>
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
                                                            <td width="220" bgcolor="#982a29" class="lineabajoarriba"
                                                                colspan=2><font class="tituloNav"><? echo $tituloNav ?>
                                                                </font></td>
                                                            <td width="20" valign=top bgcolor="#B3C7DA"
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
                                    <td height="400" align="center" valign="top" bgcolor="#AACFF9" class="lineabajo">
                                        <table width="100%" height="400" border="0" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td width="20" height="370" align="center" valign="middle"
                                                    class="lineaderecha">&nbsp;</td>
                                                <td align="center" valign="middle">
                                                    <table width="97%" border="0" align="center" cellpadding="0"
                                                           cellspacing="0">
                                                        <tr bgcolor="#D9E3EC">
                                                            <td height="10" colspan="3" bgcolor="#D9E3EC"
                                                                class="linearribadereizq"><img
                                                                    src="<? echo $pathRaiz ?>imagenes/transparente.gif"
                                                                    width="10" height="10"></td>
                                                        </tr>
                                                        <tr bgcolor="#D9E3EC" class="lineabajodereizq">
                                                            <td width="5" bgcolor="#D9E3EC" class="lineaizquierda">
                                                                &nbsp;</td>
                                                            <td width="100%" align="left" bgcolor="#D9E3EC">
                                                                <table width="100%" border="0" cellspacing="0"
                                                                       cellpadding="1" class="tablaFiltros">

                                                                    <tr>
                                                                        <td width="24" height=20>&nbsp;
                                                                        </td>
                                                                        <td width="31" height=20>
                                                                            <img height=7
                                                                                 src="<?= $pathRaiz ?>imagenes/diamante.gif"
                                                                                 width=7>
                                                                        </td>
                                                                        <td width="150" height=20 class=textoazul>
                                                                            <?= $auxiliar->traduce("Archivo a Importar (xls)", $administrador->ID_IDIOMA) . ":"; ?>
                                                                        </td>
                                                                        <td width="1315" height=20>
                                                                            <input type="file"
                                                                                   name="adjunto_archivo_importacion"
                                                                                   size="66" class="copyright"
                                                                                   style="height:18px;" value="">
                                                                        </td>
                                                                    </tr>

                                                                    <tr>
                                                                        <td height=20>&nbsp;
                                                                        </td>
                                                                        <td height=20>&nbsp;</td>
                                                                        <td colspan="2" class=textoazul height=20>
                                                                            <br/>

                                                                            <p><?= $auxiliar->traduce("Instrucciones", $administrador->ID_IDIOMA) . ":"; ?>
                                                                            </p>

                                                                            <p><?= $auxiliar->traduce("Seleccione del equipo un archivo Excel.", $administrador->ID_IDIOMA) ?>
                                                                                <br/>
                                                                                <?= $auxiliar->traduce("Se mostrara una simulacion de resultados previa a la carga definitiva.", $administrador->ID_IDIOMA) ?>
                                                                            </p>

                                                                            <br/>

                                                                            <p><?= $auxiliar->traduce("Formato de fichero", $administrador->ID_IDIOMA) . ":"; ?>
                                                                            </p>

                                                                            <p><?= $auxiliar->traduce("La primera linea del fichero contiene los titulos. Esta linea no se tendra en cuenta al importar.", $administrador->ID_IDIOMA) ?>
                                                                                <br/>
                                                                                <?= $auxiliar->traduce("El orden de las columnas es el siguiente:", $administrador->ID_IDIOMA) ?>
                                                                                <br/>
                                                                                - <?= $auxiliar->traduce("Ref. ubicación", $administrador->ID_IDIOMA) ?>
                                                                                <br/>
                                                                                - <?= $auxiliar->traduce("Ref. centro", $administrador->ID_IDIOMA) ?>
                                                                                <br/>
                                                                                - <?= $auxiliar->traduce("Ref. almacén", $administrador->ID_IDIOMA) ?>
                                                                                <br/>
                                                                                - <?= $auxiliar->traduce("Categoria ubicacion", $administrador->ID_IDIOMA) ?>
                                                                                <br/>
                                                                                - <?= $auxiliar->traduce("Descripción", $administrador->ID_IDIOMA) ?>
                                                                                <br/>
                                                                                - <?= $auxiliar->traduce("Precio Fijo", $administrador->ID_IDIOMA) . " (" . $auxiliar->traduce("Si", $administrador->ID_IDIOMA) . "/" . $auxiliar->traduce("No", $administrador->ID_IDIOMA) . ")" ?>
                                                                                (<?= $auxiliar->traduce("Si no se indica el sistema aplicará 'No'", $administrador->ID_IDIOMA) . ")" ?>
                                                                                .<br/>
                                                                                - <?= $auxiliar->traduce("Autostore", $administrador->ID_IDIOMA) . " (" . $auxiliar->traduce("Si", $administrador->ID_IDIOMA) . "/" . $auxiliar->traduce("No", $administrador->ID_IDIOMA) . ")" ?>
                                                                                (<?= $auxiliar->traduce("Si no se indica el sistema aplicará 'No'", $administrador->ID_IDIOMA) . ")" ?>
                                                                                .<br/>
                                                                                - <?= $auxiliar->traduce("Baja", $administrador->ID_IDIOMA) . " (" . $auxiliar->traduce("Si", $administrador->ID_IDIOMA) . "/" . $auxiliar->traduce("No", $administrador->ID_IDIOMA) . ")" ?>
                                                                                (<?= $auxiliar->traduce("Si no se indica el sistema aplicará 'No'", $administrador->ID_IDIOMA) . ")" ?>
                                                                                <br/>
                                                                                - <?= $auxiliar->traduce("Es tipo sector", $administrador->ID_IDIOMA) . " (" . $auxiliar->traduce("Si", $administrador->ID_IDIOMA) . "/" . $auxiliar->traduce("No", $administrador->ID_IDIOMA) . ")" ?>
                                                                                (<?= $auxiliar->traduce("Si no se indica el sistema aplicará 'No'", $administrador->ID_IDIOMA) . ")" ?>
                                                                                (<?= $auxiliar->traduce("Columna opcional", $administrador->ID_IDIOMA) . ")" ?>
                                                                                <br/>
                                                                                - <?= $auxiliar->traduce("Referencia", $administrador->ID_IDIOMA) . " " . $auxiliar->traduce("Tipo Sector", $administrador->ID_IDIOMA) ?>
                                                                                <br/>
                                                                            </p>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td height=20>&nbsp;
                                                                        </td>
                                                                        <td height=20>&nbsp;</td>
                                                                        <td class=textoazul height=20>
                                                                            <p><?= $auxiliar->traduce("Fichero de ejemplo", $administrador->ID_IDIOMA) ?>
                                                                                :</p>

                                                                            <p>
                                                                                <a href="<?= $pathRaiz . "descargarDocumento.php?key=ejemplos&ruta=ficheros_ejemplo/Ubicaciones_Ejemplo.xls"; ?>">
                                                                                    <?= $auxiliar->traduce("Ver Excel ejemplo", $administrador->ID_IDIOMA) ?></a>
                                                                            </p>

                                                                        </td>
                                                                        <td class=textoazul height=20>
                                                                            <p><?= $auxiliar->traduce("Plantilla", $administrador->ID_IDIOMA) ?>
                                                                                :</p>

                                                                            <p>
                                                                                <a href="<?= $pathRaiz . "descargarDocumento.php?key=ejemplos&ruta=ficheros_ejemplo/Ubicaciones_Plantilla.csv"; ?>">
                                                                                    <?= $auxiliar->traduce("Ver Plantilla", $administrador->ID_IDIOMA) ?></a>
                                                                            </p>
                                                                        </td>
                                                                    </tr>


                                                                </table>

                                                            </td>
                                                            <td width="5" bgcolor="#D9E3EC" class="lineaderecha">
                                                                &nbsp;</td>
                                                        </tr>
                                                        <tr bgcolor="#D9E3EC">
                                                            <td height="10" colspan="3" bgcolor="#D9E3EC"
                                                                class="lineabajodereizq"><img
                                                                    src="<? echo $pathRaiz ?>imagenes/transparente.gif"
                                                                    width="10" height="10"></td>
                                                        </tr>
                                                    </table>
                                                </td>
                                                <td width="20" align="center" valign="middle" class="lineaizquierda">
                                                    &nbsp;</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="left" valign="top" bgcolor="#B3C7DA" class="lineabajo">
                                        <table width="100%" border="0" cellspacing="0" cellpadding="0"
                                               bgcolor="#D9E3EC">
                                            <tr height="25">
                                                <td class="lineabajo" width="50%" align="left"><span class="textoazul">
                                                        &nbsp;&nbsp;<a id="botonVolver"
                                                                       href="index.php?recordar_busqueda=1"
                                                                       class="senaladoazul">
                                                            &nbsp;&nbsp;&nbsp;&nbsp;<?= $auxiliar->traduce("Volver", $administrador->ID_IDIOMA) ?>
                                                            &nbsp;&nbsp;&nbsp;&nbsp;</a></span></td>

                                                <td align="right" class="lineabajo"><span class="textoazul">
                                                        &nbsp;<a href="#" class="senalado6"
                                                                 onClick="this.disabled=true;jQuery('#txCentro').attr('autocomplete','on');jQuery('#idCentro').attr('autocomplete','on');	document.FormSelect.submit();return false">
                                                            &nbsp;&nbsp;&nbsp;&nbsp;<?= $auxiliar->traduce("Importar", $administrador->ID_IDIOMA) ?>
                                                            &nbsp;&nbsp;&nbsp;&nbsp;</a>&nbsp;&nbsp;
                                                    </span>
                                                </td>
                                            </tr>
                                        </table>
                                        <br><br>
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
