<? //print_r($_REQUEST);
// PATHS DE LA WEB
$pathRaiz   = "../../";
$pathClases = "../../../";

// INCLUDES DE LIBRERIAS PROPIAS
require_once $pathClases . "lib/basedatos.php";
require_once $pathClases . "lib/administrador.php";
require_once $pathClases . "lib/material.php";
require_once $pathClases . "lib/lineas_etiqueta.php";
require_once $pathClases . "lib/html.php";
require_once $pathClases . "lib/navegar.php";
require_once $pathClases . "lib/comprobar.php";
require_once $pathClases . "lib/auxiliar.php";

session_start();
include $pathRaiz . "seguridad_admin.php";

$tituloPag         = $auxiliar->traduce("PEP Construccion", $administrador->ID_IDIOMA);
$tituloNav         = $auxiliar->traduce("Maestros", $administrador->ID_IDIOMA) . " >> " . $tituloPag;
$ZonaTablaPadre    = "Maestros";
$ZonaSubTablaPadre = "MaestrosSubmenuTransporte";
$ZonaTabla         = "MaestrosPepConstruccion";

// COMPRUEBA SI TIENE PERMISOS
if ($administrador->Hayar_Permiso_Perfil('ADM_PEP_CONSTRUCCION') < 1):
    $html->PagError("SinPermisos");
endif;

//BUSCAMOS EL DESTINATARIO
$NotificaErrorPorEmail = "No";
$rowPepConstruccion    = $bd->VerReg("PEP_CONSTRUCCION", "ID_PEP_CONSTRUCCION", $idPepConstruccion, "No");
unset($NotificaErrorPorEmail);

//CAMPO DESCRIPCION
$txDescripcionPep = $rowPepConstruccion->DESCRIPCION_PEP;
?>
<html>
<head>
    <? require_once $pathClases . "lib/gral_js.php"; ?>
    <script language="JavaScript">
        function grabar(boton) {
            if (document.FormSelect.idPepConstruccion.value != '') {
                document.FormSelect.action = 'accion.php';
                document.FormSelect.accion.value = 'Modificar';
                boton.disabled = true;
                document.FormSelect.submit();
                return false;
            } else {
                document.FormSelect.action = 'accion.php';
                document.FormSelect.accion.value = 'Insertar';
                boton.disabled = true;
                document.FormSelect.submit();
                return false;
            }
        }
    </script>
    <!-- BUSQUEDA AJAX -->
    <script src="<?= $pathClases; ?>lib/ajax_script/lib/prototype.js" type="text/javascript"></script>
    <script src="<?= $pathClases; ?>lib/ajax_script/src/scriptaculous.js" type="text/javascript"></script>
    <link rel="stylesheet" href="<?= $pathClases; ?>lib/ajax_script/style_ajax.css" type="text/css"/>
</head>
<body background="<? echo $pathRaiz ?>imagenes/fondo_pantalla.gif" leftmargin="0" topmargin="0" marginwidth="0"
      marginheight="0">
<FORM NAME="FormSelect" ACTION="accion.php" METHOD="POST">
    <input type=hidden name="accion" value="">
    <input type="hidden" name="idPepConstruccion" id="idPepConstruccion"
           value="<? echo $idPepConstruccion ?>">
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
                        <td height="3" colspan="2" align="center" valign="top" bgcolor="#7A0A0A" class="linearriba">
                            <img src="<? echo $pathRaiz ?>imagenes/transparente.gif" width="10" height="3">
                        </td>
                    </tr>
                    <tr>
                        <? include $pathRaiz . "tabla_izqda.php"; ?>
                        <td align="left" valign="top">
                            <table width="100%" height="100%" border="0" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td height="23">
                                        <table width="100%" height="23" border="0" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td width="25" class="linearriba">
                                                    <img src="<? echo $pathRaiz ?>imagenes/flechitas_01.gif" width="35"
                                                         height="23">
                                                </td>
                                                <td width="469" align="left" valign="middle" bgcolor="#B3C7DA"
                                                    class="linearriba">
                                                    <table width="440" border="0" cellspacing="0" cellpadding="0">
                                                        <tr>
                                                            <td align="left" class="alertas">
                                                                <? echo $tituloPag ?>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                                <td width="25">
                                                    <img src="<? echo $pathRaiz ?>imagenes/esquina.gif" width="25"
                                                         height="24">
                                                </td>
                                                <td bgcolor="#7A0A0A">
                                                    <table width="235" height="23" border="0" cellpadding="0"
                                                           cellspacing="0">
                                                        <tr>
                                                            <td width="20">&nbsp;
                                                            </td>
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
                                                            <td width="294" bgcolor="#982a29" class="lineabajoarriba"
                                                                colspan=2>
                                                                <font class="tituloNav"
                                                                      style="white-space:nowrap;"><? echo $tituloNav ?>
                                                                </font>
                                                            </td>
                                                            <td width="20" valign=top bgcolor="#B3C7DA"
                                                                class="lineabajoarriba">
                                                                <img src="<? echo $pathRaiz ?>imagenes/esquina_02.gif"
                                                                     width="20" height="20">
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                                <td bgcolor="#B3C7DA" class="lineabajoarriba">&nbsp;
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr bgcolor="#D9E3EC">
                                    <td height="220" align="left" valign="top" bgcolor="#AACFF9" class="lineabajo">
                                        <table width="100%" height="281" border="0" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td width="20px" align="center" valign="middle" class="lineaderecha">
                                                    &nbsp;
                                                </td>
                                                <td align="center" valign="middle">
                                                    &nbsp;
                                                    <table width="97%" height="130" border="0" align="center"
                                                           cellpadding="0" cellspacing="0">
                                                        <tr bgcolor="#D9E3EC">
                                                            <td height="10" colspan="3" bgcolor="#D9E3EC"
                                                                class="linearribadereizq">
                                                                <img src="<? echo $pathRaiz ?>imagenes/transparente.gif"
                                                                     width="10" height="10">
                                                            </td>
                                                        </tr>
                                                        <tr bgcolor="#D9E3EC">
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
                                                                        <td width="35%" align="left" class="textoazul">
                                                                            <?= $auxiliar->traduce("Descripcion", $administrador->ID_IDIOMA) ?>
                                                                            :
                                                                        </td>
                                                                        <td width="60%" class="textoazul">
                                                                            <textarea name="txDescripcionPep" rows="4"
                                                                                      style="width:420px; resize:none;"
                                                                                      class="copyright ObligatorioRellenar"><?= $txDescripcionPep ?></textarea>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td align="center" width="5%"></td>
                                                                        <td align="left" class="textoazul"
                                                                            width="35%"><?= $auxiliar->traduce("Baja", $administrador->ID_IDIOMA) . ":" ?>
                                                                        </td>
                                                                        <td class="textoazul" width="60%">
                                                                            <?
                                                                            $Estilo = 'check_estilo';
                                                                            $html->Option("chBaja", "Check", "1", $chBaja);
                                                                            ?>
                                                                        </td>
                                                                    </tr>
                                                                </table>
                                                            </td>

                                                            <td class=lineaderecha width=20 bgcolor=#d9e3ec
                                                                align="right" valign="top">
                                                                <?
                                                                if ((isset($rowPepConstruccion->ID_PEP_CONSTRUCCION)) && ($rowPepConstruccion->ID_PEP_CONSTRUCCION != "")):
                                                                    $jscript = "style='margin-right:5px;'";
                                                                    $html->VerHistorial('Maestro', $rowPepConstruccion->ID_PEP_CONSTRUCCION, "", $nombreTabla = "PEP_CONSTRUCCION");
                                                                    unset($jscript);
                                                                endif;
                                                                ?>
                                                            </td>
                                                        </tr>
                                                        <tr bgcolor="#D9E3EC">
                                                            <td height="10" colspan="3" bgcolor="#D9E3EC"
                                                                class="lineabajodereizq">
                                                                <img src="<? echo $pathRaiz ?>imagenes/transparente.gif"
                                                                     width="10" height="10">
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                                <td width="20px" align="center" valign="middle" class="lineaizquierda">
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
                                                <td class="lineabajo" width="50%" align="left">
                                                  <span class="textoazul">
                                                    &nbsp;
                                                    <a id="botonVolver" href="index.php?recordar_busqueda=1"
                                                       class="senaladoazul">
                                                        &nbsp;&nbsp;&nbsp;&nbsp;<?= $auxiliar->traduce("Volver", $administrador->ID_IDIOMA) ?>
                                                        &nbsp;&nbsp;&nbsp;&nbsp;</a>
                                                  </span>
                                                </td>
                                                <td align="right" class="lineabajo">
                                                  <span class="textoazul">
                                                          <a href="#" class="senaladoverde"
                                                             onClick="grabar(this)"
                                                             id="botonGrabar" tabindex="4">
                                                              &nbsp;&nbsp;&nbsp;&nbsp;<?= $auxiliar->traduce("Grabar", $administrador->ID_IDIOMA) ?>
                                                              &nbsp;&nbsp;&nbsp;&nbsp;</a>
                                                      &nbsp;
                                                  </span>
                                                </td>
                                            </tr>
                                        </table>
                                        <br>
                                        <br>
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
</FORM>
</body>
</html>
