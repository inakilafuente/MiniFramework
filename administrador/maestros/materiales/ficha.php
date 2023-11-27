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

$tituloPag         = $auxiliar->traduce("Tipos de Incidencias", $administrador->ID_IDIOMA);
$tituloNav         = $auxiliar->traduce("Maestros", $administrador->ID_IDIOMA) . " >> " . $auxiliar->traduce("Tipos de Incidencias", $administrador->ID_IDIOMA);
$ZonaTablaPadre    = "Maestros";
$ZonaTabla         = "MaestrosIncidenciaSistemaTipo";
$ZonaSubTablaPadre = "MaestrosSubmenuAplicacion";

// COMPRUEBA SI TIENE PERMISOS
if ($administrador->Hayar_Permiso_Perfil('ADM_MAESTROS_INCIDENCIA_SISTEMA_TIPO') < 1):
    $html->PagError("SinPermisos");
endif;

//OBTENGO EL REGISTRO E INICIALIZO VALORES DE LAS VARIABLES CON LO QUE HAY EN BASE DE DATOS
if ($idIncidenciaSistemaTipo != ""):
    // OBTENGO REGISTRO
    $sqlTipo = "SELECT * FROM INCIDENCIA_SISTEMA_TIPO WHERE ID_INCIDENCIA_SISTEMA_TIPO = '" . $bd->escapeCondicional($idIncidenciaSistemaTipo) . "'";
    $resTipo = $bd->ExecSQL($sqlTipo);
    $rowTipo = $bd->SigReg($resTipo);

    // INICIALIZO VARIABLES
    $txIncidenciaSistemaTipo    = $rowTipo->INCIDENCIA_SISTEMA_TIPO;
    $txIncidenciaSistemaTipoEng  = $rowTipo->INCIDENCIA_SISTEMA_TIPO_ENG;
    $chBaja   = $rowTipo->BAJA;

    $accion = 'Modificar';
else:
    $accion = 'Insertar';
endif;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <? require_once $pathClases . "lib/gral_js.php"; ?>
    <!-- BUSQUEDA AJAX -->
    <script src="<?= $pathClases; ?>lib/ajax_script/lib/prototype.js" type="text/javascript"></script>
    <script src="<?= $pathClases; ?>lib/ajax_script/src/scriptaculous.js" type="text/javascript"></script>
    <link rel="stylesheet" href="<?= $pathClases; ?>lib/ajax_script/style_ajax.css" type="text/css"/>
    <!-- FIN BUSQUEDA AJAX -->

    <script type="text/javascript">
        jQuery(document).ready(function () {
            jQuery('#botonVolver').focus();
        });
    </script>

    <script language="JavaScript" type="text/javascript">
        function grabar() {
            if (document.FormSelect.idIncidenciaSistemaTipo.value != '') {
                document.FormSelect.accion.value = 'Modificar';
            } else {
                document.FormSelect.accion.value = 'Insertar';
            }

            this.disabled = true;

            document.FormSelect.submit();

            return false;
        }
    </script>
</head>
<body bgcolor="#FFFFFF" background="<? echo "$pathRaiz" ?>imagenes/fondo_pantalla.gif" leftmargin="0" topmargin="0"
      marginwidth="0" marginheight="0">
<FORM NAME="FormSelect" ACTION="accion.php" METHOD="POST">
    <input type=hidden name="accion" value="<?= $accion ?>">
    <input type="hidden" name="idIncidenciaSistemaTipo" value="<? echo $idIncidenciaSistemaTipo ?>">
    <input type="hidden" name="incidenciaSistemaTipo" value="<? echo $txIncidenciaSistemaTipo ?>">
    <input type="hidden" name="incidenciaSistemaTipoEng" value="<? echo $txIncidenciaSistemaTipoEng ?>">
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
                                                        src="<? echo $pathRaiz ?>imagenes/flechitas_01.gif"
                                                        width="35" height="23"></td>
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
                                                                &nbsp;
                                                            </td>
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
                                    <td align="center" valign="top" bgcolor="#AACFF9" class="lineabajo">
                                        <table width="100%" border="0" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td width="20" align="center" valign="middle" class="lineaderecha">
                                                    &nbsp;
                                                </td>
                                                <td align="center" valign="middle">&nbsp;
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
                                                                &nbsp;
                                                            </td>
                                                            <td width="540" align="left" bgcolor="#D9E3EC">
                                                                <table width="750" border="0" cellspacing="0"
                                                                       cellpadding="1" class="tablaFiltros">
                                                                    <tr>
                                                                        <td align="center" width="5%"><img
                                                                                src="<? echo $pathRaiz ?>imagenes/diamante.gif"
                                                                                width="7" height="7"></td>
                                                                        <td align="left" class="textoazul"
                                                                            width="35%"><?= $auxiliar->traduce("Incidencia Sistema Tipo", $administrador->ID_IDIOMA) . ":" ?>
                                                                        </td>
                                                                        <td class="textoazul" width="60%">
                                                                            <?
                                                                            $TamanoText = "420px";
                                                                            $ClassText  = "copyright ObligatorioRellenar";
                                                                            $MaxLength  = "80";
                                                                            $html->TextBox("txIncidenciaSistemaTipo", $txIncidenciaSistemaTipo);
                                                                            ?>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td align="center" width="5%"><img
                                                                                src="<? echo $pathRaiz ?>imagenes/diamante.gif"
                                                                                width="7" height="7"></td>
                                                                        <td align="left" class="textoazul"
                                                                            width="35%"><?= $auxiliar->traduce("Incidencia Sistema Tipo Eng.", $administrador->ID_IDIOMA) . ":" ?>
                                                                        </td>
                                                                        <td class="textoazul" width="60%">
                                                                            <?
                                                                            $TamanoText = "420px";
                                                                            $ClassText  = "copyright ObligatorioRellenar";
                                                                            $MaxLength  = "255";
                                                                            $html->TextBox("txIncidenciaSistemaTipoEng", $txIncidenciaSistemaTipoEng);
                                                                            ?>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td align="center" width="5%"><img
                                                                                src="<? echo $pathRaiz ?>imagenes/diamante.gif"
                                                                                width="7" height="7"></td>
                                                                        <td align="left" class="textoazul"
                                                                            width="35%"><?= $auxiliar->traduce("Baja", $administrador->ID_IDIOMA) . ":" ?>
                                                                        </td>

                                                                        <td class="textoazul" width="60%">
                                                                            <?
                                                                            $Estilo = 'check_estilo';
                                                                            $html->Option("chBaja","Check","1",$chBaja);
                                                                            ?>
                                                                        </td>
                                                                    </tr>

                                                                </table>
                                                            </td>
                                                            <td class=lineaderecha width="3%" bgcolor=#d9e3ec
                                                                align="right" valign="top">
                                                                <?
                                                                if ($rowTipo->ID_INCIDENCIA_SISTEMA_TIPO != ""):
                                                                    $jscript = "style='margin-right:5px;'";
                                                                    $html->VerHistorial('Maestro', $rowTipo->ID_INCIDENCIA_SISTEMA_TIPO, "Incidencia Sistema Tipo", "INCIDENCIA_SISTEMA_TIPO");
                                                                    unset($jscript);
                                                                endif;
                                                                ?>
                                                            </td>
                                                        <tr bgcolor="#D9E3EC">
                                                            <td height="10" colspan="3" bgcolor="#D9E3EC"
                                                                class="lineabajodereizq"><img
                                                                    src="<? echo $pathRaiz ?>imagenes/transparente.gif"
                                                                    width="10" height="10"></td>
                                                        </tr>
                                                    </table>
                                                     
                                                </td>
                                                <td width="20" align="center" valign="middle" class="lineaizquierda">
                                                    &nbsp;
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="left" valign="top" bgcolor="#B3C7DA" class="lineabajo">
                                        <table width="100%" border="0" cellspacing="0" cellpadding="0"
                                               bgcolor="#D9E3EC">
                                            <tr height="25">
                                                <td class="lineabajo" width="50%" align="left"><span class="textoazul">&nbsp;<a
                                                            href="index.php?recordar_busqueda=1"
                                                            class="senaladoazul">&nbsp;&nbsp;&nbsp;&nbsp;<?= $auxiliar->traduce("Volver", $administrador->ID_IDIOMA) ?>&nbsp;&nbsp;&nbsp;&nbsp;</a></span>
                                                </td>
                                                <td align="right" class="lineabajo"><span class="textoazul">
  							&nbsp;<a href="#" id="botonGrabar" class="senalado6" onclick="grabar()">&nbsp;&nbsp;&nbsp;&nbsp;<?= $auxiliar->traduce("Grabar", $administrador->ID_IDIOMA) ?>&nbsp;&nbsp;&nbsp;&nbsp;</a>&nbsp;</span>
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
