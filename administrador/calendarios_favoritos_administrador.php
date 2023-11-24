<? //print_r($_REQUEST);
$pathRaiz   = "./";
$pathClases = "../";
// INCLUDES DE LIBRERIAS PROPIAS
require_once $pathClases . "lib/basedatos.php";
require_once $pathClases . "lib/administrador.php";
require_once $pathClases . "lib/html.php";
require_once $pathClases . "lib/navegar.php";
require_once $pathClases . "lib/auxiliar.php";
require_once $pathClases . "lib/calendario.php";

session_start();
include $pathRaiz . "seguridad_admin.php";

$tituloPag         = $auxiliar->traduce("Calendarios Favoritos", $administrador->ID_IDIOMA);
$tituloNav         = $auxiliar->traduce("Maestros", $administrador->ID_IDIOMA) . " >> " . $tituloPag;
$ZonaTablaPadre    = "Maestros";
$ZonaSubTablaPadre = "MaestrosSubmenuEstructura";
$ZonaTabla         = "MaestrosCalendarioFestivos";

// COMPRUEBA SI TIENE PERMISOS
//if ($administrador->Hayar_Permiso_Perfil('ADM_MAESTROS_PLANTILLA_CALENDARIO_FESTIVOS') < 1):
//    $html->PagError("SinPermisos");
//endif;


if ($accion == "EliminarFavorito"):
    Calendario::eliminarCalendarioFavoritoAdministrador($idCalendarioFavoritoEliminar);
endif;


//OBTENGO LOS CALENDARIOS DEL ADMINISTRADOR
$arrCalendariosFavoritos = Calendario::obtenerCalendariosFavoritosAdministrador();


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
    <script language="JavaScript" type="text/JavaScript">

        var bandera = true;

        jQuery(document).ready(function () {
            <? if ($filasOk > 0): ?>

            jQuery('#btnProcesar').attr('onclick', 'continuar();return false');
            jQuery('#btnProcesar').css('color', '');
            jQuery('#btnProcesar2').attr('onclick', 'continuar();return false');
            jQuery('#btnProcesar2').css('color', '');

            <? endif; ?>
        });

        function continuar() {
            if (bandera == true) {
                bandera = false;
                listaCalendarios = comprobarSiSeleccionada('chSelec');
                document.FormSelect.listaCalendarios.value = listaCalendarios;
                document.FormSelect.submit();
                return false;
            }
        }
        function eliminarCalendarioFavorito(idCalendarioFavorito) {
            document.FormSelect.idCalendarioFavoritoEliminar.value = idCalendarioFavorito;
            document.FormSelect.action = "calendarios_favoritos_administrador.php";
            document.FormSelect.accion.value = "EliminarFavorito";
            document.FormSelect.submit();
            return false;

        }

        function CerrarVentana() {
            window.parent.jQuery.fancybox.close();

            return false;
        }
    </script>
</head>
<body class="fancy" bgcolor="#FFFFFF" background="<?= $pathRaiz ?>imagenes/fondo_pantalla.gif" leftmargin="0"
      topmargin="0" marginwidth="0" marginheight="0">
<FORM NAME="FormSelect" ACTION="ficha_crear_calendario_accion.php" METHOD="POST" style="margin-bottom:0;"
      enctype="multipart/form-data">
    <input type="hidden" name="listaElementos" value="<? echo $listaElementos; ?>">
    <input type="hidden" name="listaCalendarios" value="">
    <input type="hidden" name="idCalendario" value="<? echo $idPlantillaCalendarioFestivos ?>">
    <input type="hidden" name="selTipoCalendario" value="<? echo $selTipoCalendario ?>">
    <input type="hidden" name="idCalendarioFavoritoEliminar" value="<? echo $idCalendarioFavoritoEliminar ?>">
    <input type="hidden" name="accion" value="">


    <? $navegar->GenerarCamposOcultosForm(); ?>
    <table width="100%" border="0" align="center" cellpadding="0" cellspacing="0" background="">
        <tr>
            <td valign=top align=middle height=10>
                <!--                --><? // include $pathRaiz . "tabla_superior.php"; ?>
            </td>
        </tr>
        <tr>
            <td align="center" valign="top">
                <table width="100%" border="0" cellpadding="0" cellspacing="0">
                    <tr>
                        <td height="3" colspan="2" align="center" valign="top" bgcolor="#7A0A0A" class="linearriba">
                            <img src="<?= $pathRaiz ?>imagenes/transparente.gif" width="10" height="3">
                        </td>
                    </tr>
                    <tr>
                        <!--                        --><? // include $pathRaiz . "tabla_izqda.php"; ?>
                        <td align="left" valign="top" bgcolor="#FFFFFF"
                            background="<?= $pathRaiz ?>imagenes/fondo_pantalla.gif">
                            <table width="100%" border="0" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td height="23">
                                        <table width="100%" height="23" border="0" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td width="25" class="linearriba">
                                                    <img src="<?= $pathRaiz ?>imagenes/flechitas_01.gif" width="35"
                                                         height="23">
                                                </td>
                                                <td width="469" align="left" valign="middle" bgcolor="#B3C7DA"
                                                    class="linearriba">
                                                    <table width="440" border="0" cellspacing="0" cellpadding="0">
                                                        <tr>
                                                            <td align="left" class="alertas">
                                                                <?= $tituloPag ?>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                                <td width="25">
                                                    <img src="<?= $pathRaiz ?>imagenes/esquina.gif" width="25"
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
                                                            <td width="224" bgcolor="#982a29" class="lineabajoarriba"
                                                                colspan="2">
                                                                <font class="tituloNav" style="white-space:nowrap;">
                                                                    <?= $tituloNav ?>
                                                                </font>
                                                            </td>
                                                            <td valign=top width="20" bgcolor="#B3C7DA"
                                                                class="lineabajoarriba">
                                                                <img src="<?= $pathRaiz ?>imagenes/esquina_02.gif"
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
                                <tr>
                                    <td align="left" valign="top" bgcolor="#B3C7DA" class="lineabajo">
                                        <table width="100%" border="0" cellpadding="0" cellspacing="0"
                                               class="lineabajo">

                                            <tr class="lineabajo">
                                                <td colspan="2" align="center" bgcolor="#D9E3EC">


                                                    <table width="98%" cellpadding="0" cellspacing="2"
                                                           class="linealrededor">
                                                        <tr>
                                                            <td height="19" bgcolor="#2E8AF0" class="blanco"
                                                                align="center">
                                                                <?= $auxiliar->traduce("Calendarios Favoritos", $administrador->ID_IDIOMA) ?>
                                                            </td>
                                                        </tr>
                                                        <? if (count( (array)$arrCalendariosFavoritos) > 0): ?>
                                                            <tr>
                                                                <?
                                                                $numeroFilas = 20;
                                                                if ($filasKo < 20):
                                                                    $numeroFilas = $filasKo + 1;
                                                                elseif ($filasKo > 20):
                                                                    $numeroFilas = 40 - $filasKo + 1;
                                                                endif;
                                                                ?>

                                                                <td height="<? echo($numeroFilas * 15); ?>"
                                                                    bgcolor="#FFF" align="center">

                                                                    <div>

                                                                        <table cellpadding="0" cellspacing="2"
                                                                               width="100%">

                                                                            <tr>
                                                                                <td height="19" bgcolor="#2E8AF0"
                                                                                    class="blanco"><? echo $auxiliar->traduce("Tipo", $administrador->ID_IDIOMA) ?></td>
                                                                                <td height="19" bgcolor="#2E8AF0"
                                                                                    class="blanco"><? echo $auxiliar->traduce("Centro Fisico", $administrador->ID_IDIOMA) ?></td>
                                                                                <td height="19" bgcolor="#2E8AF0"
                                                                                    class="blanco"><? echo $auxiliar->traduce("Denominacion Centro Fisico", $administrador->ID_IDIOMA) ?></td>
                                                                                <td height="19" bgcolor="#2E8AF0"
                                                                                    class="blanco"><? echo $auxiliar->traduce("Pais", $administrador->ID_IDIOMA) ?></td>
                                                                                <td height="19" bgcolor="#2E8AF0"
                                                                                    class="blanco"><? echo date('Y') ?></td>
                                                                                <td height="19" bgcolor="#2E8AF0"
                                                                                    class="blanco"><? echo date('Y') + 1 ?></td>
                                                                                <td height="19" bgcolor="#2E8AF0"
                                                                                    class="blanco"><? echo $auxiliar->traduce("Acciones", $administrador->ID_IDIOMA) ?></td>

                                                                            </tr>


                                                                            <?
                                                                            //VARIABLE PARA PINTAR EL COLOR DE LA FILA
                                                                            $i = 0;
                                                                            ?>

                                                                            <!-- PINTO LAS LINEAS -->
                                                                            <?

                                                                            foreach ($arrCalendariosFavoritos as $idCalendarioFavorito): ?>
                                                                                <?
                                                                                //COLOR DE LA FILA
                                                                                if ($i % 2 == 0) $myColor = "#B3C7DA";
                                                                                else $myColor = "#AACFF9";

                                                                                //OBTENEMOS EL REGISTRO FAVORITO
                                                                                $rowCalendarioFavorito = $bd->VerReg("CALENDARIO_FESTIVOS_ADMINISTRADOR", "ID_CALENDARIO_FESTIVOS_ADMINISTRADOR", $idCalendarioFavorito);

                                                                                //OBTENEMOS CALENDARIOS
                                                                                $rowCalendario = Calendario::obtenerCalendarioObjetoPorAno($rowCalendarioFavorito->ID_OBJETO, $rowCalendarioFavorito->TIPO_OBJETO, date('Y'));

                                                                                $refCentroFisico          = "-";
                                                                                $denominacionCentroFisico = "-";
                                                                                $nombrePais               = "-";
                                                                                if ($rowCalendario->ID_CENTRO_FISICO != ""):
                                                                                    $rowCF                    = $bd->VerReg("CENTRO_FISICO", "ID_CENTRO_FISICO", $rowCalendario->ID_CENTRO_FISICO);
                                                                                    $refCentroFisico          = $rowCF->REFERENCIA;
                                                                                    $denominacionCentroFisico = $rowCF->DENOMINACION_CENTRO_FISICO;
                                                                                    $rowPais                  = $bd->VerReg("PAIS", "ID_PAIS", $rowCF->ID_PAIS);


                                                                                endif;
                                                                                if ($rowCalendario->ID_PAIS != ""):
                                                                                    $rowPais = $bd->VerReg("PAIS", "ID_PAIS", $rowCalendario->ID_PAIS);
                                                                                endif;
                                                                                $nombrePais = ($administrador->ID_IDIOMA ? $rowPais->DESCRIPCION_ESP : $rowPais->DESCRIPCION_ENG);
                                                                                ?>

                                                                                <tr>

                                                                                    <td align="left"
                                                                                        bgcolor="<?= $myColor ?>"
                                                                                        class="enlaceceldas">
                                                                                        &nbsp;<?= $auxiliar->traduce($rowCalendario->TIPO_CALENDARIO, $administrador->ID_IDIOMA); ?>
                                                                                        &nbsp;
                                                                                    </td>
                                                                                    <td align="left"
                                                                                        bgcolor="<?= $myColor ?>"
                                                                                        class="enlaceceldas">
                                                                                        &nbsp;<?= $refCentroFisico ?>
                                                                                        &nbsp;</td>
                                                                                    <td align="left"
                                                                                        bgcolor="<?= $myColor ?>"
                                                                                        class="enlaceceldas">
                                                                                        &nbsp;<?= $denominacionCentroFisico ?>
                                                                                        &nbsp;</td>

                                                                                    <td align="left"
                                                                                        bgcolor="<?= $myColor ?>"
                                                                                        class="enlaceceldas">
                                                                                        &nbsp;<?= $nombrePais ?>
                                                                                        &nbsp;</td>

                                                                                    <td height="18" align="center"
                                                                                        bgcolor="<? echo $myColor ?>"
                                                                                        class="enlaceceldas">
                                                                                        &nbsp;
                                                                                        <? if ($rowCalendario != NULL): ?>
                                                                                            <a target="_blank"
                                                                                               href="<? echo $pathRaiz; ?>maestros/calendario_festivos/ficha.php?idCalendarioFestivos=<?= $rowCalendario->ID_CALENDARIO_FESTIVOS ?>">
                                                                                                <img
                                                                                                    src="<? echo $pathRaiz; ?>imagenes/calendario1.png"
                                                                                                    border="0"
                                                                                                    align="absbottom"
                                                                                                    width="15"
                                                                                                    height="15"/>
                                                                                            </a>
                                                                                            <?
                                                                                        else:
                                                                                            echo "-";
                                                                                        endif;
                                                                                        ?>
                                                                                    </td>
                                                                                    <?
                                                                                    //OBTENEMOS CALENDARIOS
                                                                                    $rowCalendario = Calendario::obtenerCalendarioObjetoPorAno($rowCalendarioFavorito->ID_OBJETO, $rowCalendarioFavorito->TIPO_OBJETO, date('Y') + 1); ?>
                                                                                    <td height="18" align="center"
                                                                                        bgcolor="<? echo $myColor ?>"
                                                                                        class="enlaceceldas">
                                                                                        &nbsp;
                                                                                        <? if ($rowCalendario != NULL): ?>
                                                                                            <a target="_blank"
                                                                                               href="<? echo $pathRaiz; ?>maestros/calendario_festivos/ficha.php?idCalendarioFestivos=<?= $rowCalendario->ID_CALENDARIO_FESTIVOS ?>">
                                                                                                <img
                                                                                                    src="<? echo $pathRaiz; ?>imagenes/calendario1.png"
                                                                                                    border="0"
                                                                                                    align="absbottom"
                                                                                                    width="15"
                                                                                                    height="15"/>
                                                                                            </a>
                                                                                            <?
                                                                                        else:
                                                                                            echo "-";
                                                                                        endif;
                                                                                        ?>
                                                                                    </td>
                                                                                    <td height="18" align="center"
                                                                                        bgcolor="<? echo $myColor ?>"
                                                                                        class="enlaceceldas">
                                                                                        &nbsp;
                                                                                        <a class="copyright"
                                                                                           href="#"
                                                                                           onclick="eliminarCalendarioFavorito('<? echo $rowCalendarioFavorito->ID_CALENDARIO_FESTIVOS_ADMINISTRADOR ?>');return false;">
                                                                                            <img
                                                                                                src="<?= $pathRaiz ?>imagenes/borrar.gif"
                                                                                                alt="<?= $auxiliar->traduce("Eliminar Favorito", $administrador->ID_IDIOMA) ?>"
                                                                                                name="EliminarFavorito"
                                                                                                border="0"
                                                                                                id="idEliminarFavorito"/>
                                                                                        </a>
                                                                                    </td>

                                                                                </tr>
                                                                                <?
                                                                                //INCREMENTO EL VALOR DE LA VARIABLE
                                                                                $i++;
                                                                                ?>
                                                                            <? endforeach; ?>
                                                                            <!-- FIN PINTO LAS LINEAS -->


                                                                        </table>

                                                                    </div>

                                                                </td>
                                                            </tr>
                                                        <? else: ?>
                                                            <tr>
                                                                <td hheight="19" bgcolor="#B3C7DA" align="center"
                                                                    class="enlaceceldas">
                                                                    <?= $auxiliar->traduce("No tiene calendarios favoritos", $administrador->ID_IDIOMA) ?>
                                                                </td>
                                                            </tr>
                                                        <? endif; ?>
                                                    </table>

                                                    <br/>

                                                    <table width="100%" cellpadding="0" cellspacing="0">
                                                        <tr height="20px;">
                                                            <td>
                                                                <div align="left">
                                  <span class="textoazul">
                                    &nbsp;&nbsp;<a onclick="CerrarVentana();return false;" class="senaladoazul"
                                                   href="#">
                                          &nbsp;&nbsp;&nbsp;&nbsp;<?= $auxiliar->traduce("Cerrar", $administrador->ID_IDIOMA) ?>
                                          &nbsp;&nbsp;&nbsp;&nbsp;</a>&nbsp;&nbsp;
                                  </span>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div align="right">
                                  <span class="textoazul">
                                    <a href="anadir_calendario_favoritos.php"
                                       class="senaladoverde">
                                        &nbsp;&nbsp;&nbsp;&nbsp;<?= $auxiliar->traduce("Añadir Calendario a Favoritos", $administrador->ID_IDIOMA) ?>
                                        &nbsp;&nbsp;&nbsp;&nbsp;</a>&nbsp;&nbsp;
                                  </span>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    </table>
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
    <input type="submit" style="position:absolute; top:-999999px"/>
</FORM>
</body>
</html>
