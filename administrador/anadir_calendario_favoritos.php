<?
// PATHS DE LA WEB
$pathRaiz   = "./";
$pathClases = "../";

// INCLUDES DE LIBRERIAS PROPIAS
require_once $pathClases . "lib/basedatos.php";
require_once $pathClases . "lib/administrador.php";
require_once $pathClases . "lib/html.php";
require_once $pathClases . "lib/navegar.php";
require_once $pathClases . "lib/comprobar.php";
require_once $pathClases . "lib/auxiliar.php";
require_once $pathClases . "lib/calendario.php";

session_start();
include $pathRaiz . "seguridad_admin.php";

$tituloPag         = $auxiliar->traduce("Calendarios", $administrador->ID_IDIOMA);
$tituloNav         = $auxiliar->traduce("Maestros", $administrador->ID_IDIOMA) . " >> " . $tituloPag;
$ZonaTablaPadre    = "Maestros";
$ZonaSubTablaPadre = "MaestrosSubmenuEstructura";
$ZonaTabla         = "MaestrosCalendarioFestivos";
$PaginaRecordar    = "ListadoMaestrosCalendarioFestivos";

// COMPRUEBA SI TIENE PERMISOS
if ($administrador->Hayar_Permiso_Perfil('ADM_MAESTROS_CALENDARIO_FESTIVOS') < 1):
    $html->PagError("SinPermisos");
endif;

// RECUERDO DE BUSQUEDAS REALIZADAS
include $pathRaiz . "busqueda_recordar.php";

// CONTROLO EL CAMBIO DEL LIMITE
if (!Empty($CambiarLimite)):
    $navegar->maxfilasMaestroCalendarioFestivos = $selLimite;
endif;


//HACEMOS DE SEFVIDOR EN EL MISMO FICHERO

if ($accion == "AnadirCalendarios"):

    //OBTENEMOS LSITA DE CALENDARIOS
    $arrCaelndarios = explode(",", (string)$listaCalendarios);

    //RECORREMOS CALENDARIOS
    foreach ($arrCaelndarios as $idCalendario):
        if ($idCalendario != ""):
            //OBTENEMOS CALENDARIO
            $rowCalendario = $bd->VerReg("CALENDARIO_FESTIVOS", "ID_CALENDARIO_FESTIVOS", $idCalendario, "No");

            if ($rowCalendario->ID_CENTRO_FISICO != ""):
                Calendario::guardarCalendarioFavoritoAdministrador("CENTRO_FISICO", $rowCalendario->ID_CENTRO_FISICO);
            endif;
            if ($rowCalendario->ID_PAIS != ""):
                Calendario::guardarCalendarioFavoritoAdministrador("PAIS", $rowCalendario->ID_PAIS);
            endif;
        endif;

    endforeach;

    header("location: calendarios_favoritos_administrador.php");
    exit;

endif;


// ORDENACION DE COLUMNAS
$columnas_ord["id"]   = "ID_CALENDARIO_FESTIVOS";
$columnas_ord["tipo"] = "TIPO_CALENDARIO";
$columnas_ord["year"] = "YEAR";
$columnas_ord["baja"] = "BAJA";
$columna_defecto      = "year";
$sentido_defecto      = "1"; //DESCENDENTE
$navegar->DefinirColumnasOrdenacion($columnas_ord, $columna_defecto, $sentido_defecto);

//PARA ACOTAR LAS BUSQUEDAS (QUE NO ESTEN BORRADOS)
$sqlCalendarioFestivos = "WHERE 1=1";

//REGION
if (trim( (string)$txRegion) != ""):
    $camposBD         = array('CF.REGION');
    $joinCentroFisico = " INNER JOIN CENTRO_FISICO CF ON CF.ID_CENTRO_FISICO = PF.ID_CENTRO_FISICO ";
    $sqlCalendarioFestivos .= ($bd->busquedaTextoArray($txRegion, $camposBD));
    $textoLista = $textoLista . "&" . $auxiliar->traduce("Region", $administrador->ID_IDIOMA) . ": " . $txRegion;
endif;


//FILTRO CENTRO FISICO
if ($idCentroFisico != "" || trim( (string)$txCentroFisico) != ""):
    if ($idCentroFisico != ""):
        $sqlCalendarioFestivos .= ($bd->busquedaNumero($idCentroFisico, 'PF.ID_CENTRO_FISICO'));
    else:
        $joinCentroFisico = " INNER JOIN CENTRO_FISICO CF ON CF.ID_CENTRO_FISICO = PF.ID_CENTRO_FISICO ";
        $sqlCalendarioFestivos .= ($bd->busquedaTexto($txCentroFisico, 'CF.REFERENCIA'));
    endif;
    $textoLista = $textoLista . "&" . $auxiliar->traduce("Centro Físico", $administrador->ID_IDIOMA) . ": " . $txCentroFisico;
endif;


//FILTRO TIPO
if (trim( (string)$selTipoCalendario) != ""):
    $camposBD = array('PF.TIPO_CALENDARIO');
    $sqlCalendarioFestivos .= ($bd->busquedaTextoArray($selTipoCalendario, $camposBD));
    $textoLista = $textoLista . "&" . $auxiliar->traduce("Tipo", $administrador->ID_IDIOMA) . ": " . $selTipoCalendario;
endif;

//FILTRO PAIS
$sqlUnionCentroFisicoPaises = "";
if ($idPais != "" || trim( (string)$txPais) != ""):
    $joinPais = " INNER JOIN PAIS P ON P.ID_PAIS= PF.ID_PAIS";
    if ($idPais != ""):
        $sqlCalendarioFestivos .= ($bd->busquedaNumero($idPais, 'P.ID_PAIS'));
    else:

        $camposBD = array('P.DESCRIPCION_ENG', 'P.DESCRIPCION_ESP', 'P.PAIS');
        $sqlCalendarioFestivos .= ($bd->busquedaTextoArray($txPais, $camposBD));
    endif;

    //PAISES CENTRO FISICO
    $sqlUnionCentroFisicoPaises = "UNION
                                    SELECT PF.* FROM CALENDARIO_FESTIVOS PF
                                    INNER JOIN CENTRO_FISICO CF ON CF.ID_CENTRO_FISICO = PF.ID_CENTRO_FISICO
                                    INNER JOIN PAIS P ON P.ID_PAIS = CF.ID_PAIS
                                    $sqlCalendarioFestivos  AND PF.YEAR >= '" . date('Y') . "' AND PF.BAJA = 0
                                    GROUP BY PF.ID_CENTRO_FISICO, PF.ID_PAIS, PF.YEAR ";


    $textoLista = $textoLista . "&" . $auxiliar->traduce("Pais", $administrador->ID_IDIOMA) . ": " . $txPais;
endif;


// TEXTO LISTADO
if ($textoLista == ""):
    $textoLista = $auxiliar->traduce("Todos los Calendarios", $administrador->ID_IDIOMA);
else:
    if (substr( (string) $textoLista, 0, 1) == "&") $textoLista = substr( (string) $textoLista, 1);
    $textoSustituir = "</font><font color='#EA62A2'> &gt;&gt; </font><font size='0px'>";
    $textoLista     = preg_replace("/&/", $textoSustituir, $textoLista);
endif;

$error = "NO";
if ($limite == ""):
    $mySql = "SELECT PF.* FROM CALENDARIO_FESTIVOS PF
              $joinCentroFisico $joinPais
              $sqlCalendarioFestivos  AND PF.YEAR >= '" . date('Y') . "' AND PF.BAJA = 0
              GROUP BY PF.ID_CENTRO_FISICO, PF.ID_PAIS, PF.YEAR
              $sqlUnionCentroFisicoPaises
              "; //echo $mySql;

    $navegar->sqlAdminMaestroCalendarioFestivos = $mySql;
endif;

// EXPORTAR A EXCEL
if ($exportar_excel == "1"):
    $sql = $navegar->copiaExport;
    include("exportar_excel.php");
    exit;
endif;

// REALIZO LA SENTENCIA SQL
$navegar->Sql($navegar->sqlAdminMaestroCalendarioFestivos, $navegar->maxfilasMaestroCalendarioFestivos, $navegar->numerofilasMaestroCalendarioFestivos);

// NUMERO DE REGISTROS
$numRegistros = $navegar->numerofilasMaestroCalendarioFestivos;

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

        function exportarExcel() {
            document.FormSelect.exportar_excel.value = '1';
            document.FormSelect.submit();
            document.FormSelect.exportar_excel.value = '0';
            return false;
        }

        function anadirCalendarios() {
            listaCalendarios = comprobarSiSeleccionada('chSelec');
            if (listaCalendarios == "") {
                alert('<?=$auxiliar->traduce("Seleccione alguno",$administrador->ID_IDIOMA)?>');
                return false;
            }
            else {
                document.FormSelect.listaCalendarios.value = listaCalendarios;
                document.FormSelect.accion.value = "AnadirCalendarios";
                document.FormSelect.submit();
                return false;
            }
        }
    </script>

    <script type="text/javascript">
        jQuery(document).ready(function () {


            jQuery("a.fancyboxCentrosFisicos").fancybox({
                'type': 'iframe',
                'width': '100%',
                'height': '100%',
                'hideOnOverlayClick': false
            });
            jQuery("a.fancyboxPaises").fancybox({
                'type': 'iframe',
                'width': '100%',
                'height': '100%',
                'hideOnOverlayClick': false
            });

        });
    </script>
    <!-- FIN BUSQUEDA FANCYBOX -->
</head>
<body bgcolor="#FFFFFF" background="<? echo $pathRaiz ?>imagenes/fondo_pantalla.gif" leftmargin="0" topmargin="0"
      marginwidth="0" marginheight="0">
<FORM NAME="FormSelect" ACTION="anadir_calendario_favoritos.php" METHOD="POST">
    <INPUT TYPE="HIDDEN" NAME="accion" VALUE="">
    <INPUT TYPE="HIDDEN" NAME="listaCalendarios" VALUE="">
    <table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">

        <? $navegar->GenerarCamposOcultosForm(); ?>
        <tr>
            <td height="10" align="center" valign="top">
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
                                                            <td width="224" bgcolor="#982a29" class="lineabajoarriba"
                                                                colspan="2">
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
                                <tr bgcolor="#D9E3EC">
                                    <td height="13" align="center" valign="top" bgcolor="#AACFF9" class="lineabajo">
                                        <table width="100%" height="13" border="0" align="center" cellpadding="0"
                                               cellspacing="0">
                                            <tr>
                                                <td width="20" align="center" valign="bottom" class="lineaderecha">
                                                    &nbsp;</td>
                                                <td align="center" valign="middle">
                                                    <table width="97%" height="11" border="0" align="center"
                                                           cellpadding="0" cellspacing="0" style="margin-top:5px;">
                                                        <tr>
                                                            <td height="1" colspan="3" bgcolor="#D9E3EC"
                                                                class="linearribadereizq"><img
                                                                    src="<? echo $pathRaiz ?>imagenes/transparente.gif"
                                                                    width="10" height="5"></td>
                                                        </tr>
                                                        <tr bgcolor="#D9E3EC">
                                                            <td width="10" bgcolor="#D9E3EC" class="lineaizquierda">
                                                                &nbsp;</td>
                                                            <td width="100%" align="left" bgcolor="#D9E3EC">
                                                                <table width="97%" border="0" cellpadding="0"
                                                                       cellspacing="0" class="tablaFiltros">
                                                                    <tr>
                                                                        <td height="20" align="left" valign="middle"
                                                                            class="textoazul"><?= $auxiliar->traduce("Centro Fisico", $administrador->ID_IDIOMA) . ":" ?>
                                                                        </td>
                                                                        <td align="left" valign="middle"><?
                                                                            $TamanoText = '179px';
                                                                            $ClassText  = "copyright";
                                                                            $MaxLength  = "50";
                                                                            $idTextBox  = 'txCentroFisico';
                                                                            $jscript    = "onchange=\"document.FormSelect.idCentroFisico.value=''\"";
                                                                            $html->TextBox("txCentroFisico", $txCentroFisico);
                                                                            unset($jscript);
                                                                            unset($idTextBox);
                                                                            ?>
                                                                            <input type="hidden" name="idCentroFisico"
                                                                                   id="idCentroFisico"
                                                                                   value="<?= $idCentroFisico ?>"/>
                                                                            <a href="<? echo $pathRaiz ?>buscadores_maestros/busqueda_centro_fisico.php?AlmacenarId=0"
                                                                               class="fancyboxCentrosFisicos"
                                                                               id="centros_fisicos"> <img
                                                                                    src="<?= $pathRaiz ?>imagenes/botones/listado.png"
                                                                                    alt="<?= $auxiliar->traduce("Buscar Centro Físico", $administrador->ID_IDIOMA) ?>"
                                                                                    name="Listado" border="0"
                                                                                    align="absbottom"
                                                                                    id="Listado"/> </a> <span
                                                                                id="desplegable_centros_fisicos"
                                                                                style="display: none;"> <img
                                                                                    src="<?= $pathClases; ?>lib/ajax_script/img/esperando.gif"
                                                                                    width="15" height="11"
                                                                                    alt="<?= $auxiliar->traduce("Buscando...", $administrador->ID_IDIOMA) ?>"/>
                                                                            </span>

                                                                            <div class="entry" align="left"
                                                                                 id="actualizador_centros_fisicos"></div>
                                                                            <script type="text/javascript"
                                                                                    language="JavaScript">
                                                                                new Ajax.Autocompleter('txCentroFisico', 'actualizador_centros_fisicos', '<?=$pathRaiz?>buscadores_maestros/resp_ajax_centro_fisico.php?AlmacenarId=0',
                                                                                    {
                                                                                        method: 'post',
                                                                                        indicator: 'desplegable_centros_fisicos',
                                                                                        minChars: '2',
                                                                                        afterUpdateElement: function (textbox, valor) {
                                                                                            siguiente_control(jQuery('#' + this.paramName));
                                                                                            jQuery(textbox).val(jQuery(valor).children('a').attr('alt'));//VALOR DEL PAR&Aacute;METRO ALT DEL ENLACE <a>
                                                                                            jQuery('#idCentroFisico').val(jQuery(valor).children('a').attr('rev'));
                                                                                        }
                                                                                    }
                                                                                );
                                                                            </script>
                                                                        </td>
                                                                        <td width="4%" align="center" valign="top">
                                                                            &nbsp;</td>
                                                                        <td height="20" align="left" valign="middle"
                                                                            class="textoazul"><?= $auxiliar->traduce("Pais", $administrador->ID_IDIOMA) . ":" ?>
                                                                        </td>
                                                                        <td align="left" valign="middle">
                                                                            <?
                                                                            $TamanoText = '179px';
                                                                            $ClassText  = "copyright";
                                                                            $MaxLength  = "50";
                                                                            $idTextBox  = 'txPais';
                                                                            $jscript    = "onchange=\"document.FormSelect.idPais.value=''\"";
                                                                            $html->TextBox("txPais", $txPais);
                                                                            unset($jscript);
                                                                            unset($idTextBox);
                                                                            ?>
                                                                            <input type="hidden" name="idPais"
                                                                                   id="idPais"
                                                                                   value="<?= $idPais ?>"/>

                                                                            <a href="<?= $pathRaiz ?>buscadores_maestros/busqueda_pais.php?AlmacenarId=0"
                                                                               class="fancyboxPaises"
                                                                               id="paises"> <img
                                                                                    src="<?= $pathRaiz ?>imagenes/botones/listado.png"
                                                                                    alt="<?= $auxiliar->traduce("Buscar Pais", $administrador->ID_IDIOMA) ?>"
                                                                                    name="Listado" border="0"
                                                                                    align="absbottom" id="Listado"/>
                                                                            </a> <span id="desplegable_paises"
                                                                                       style="display: none;"> <img
                                                                                    src="<?= $pathClases; ?>lib/ajax_script/img/esperando.gif"
                                                                                    width="15" height="11"
                                                                                    alt="<?= $auxiliar->traduce("Buscando...", $administrador->ID_IDIOMA) ?>"/>
                                                                            </span>

                                                                            <div class="entry" align="left"
                                                                                 id="actualizador_paises"></div>
                                                                            <script type="text/javascript"
                                                                                    language="JavaScript">
                                                                                new Ajax.Autocompleter('txPais', 'actualizador_paises', '<?=$pathRaiz?>buscadores_maestros/resp_ajax_pais.php?AlmacenarId=0',
                                                                                    {
                                                                                        method: 'post',
                                                                                        indicator: 'desplegable_paises',
                                                                                        minChars: '2',
                                                                                        afterUpdateElement: function (textbox, valor) {

                                                                                            siguiente_control(jQuery('#' + this.paramName));
                                                                                            jQuery(textbox).val(jQuery(valor).children('a').attr('alt'));//VALOR DEL PAR&Aacute;METRO ALT DEL ENLACE <a>

                                                                                            jQuery('#idPais').val(jQuery(valor).children('a').attr('rev'));
                                                                                        }
                                                                                    }
                                                                                );
                                                                            </script>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td height="20" align="left" valign="middle"
                                                                            class="textoazul">
                                                                            <?= $auxiliar->traduce("Region", $administrador->ID_IDIOMA) . ":" ?>
                                                                        </td>
                                                                        <td align="left" valign="middle">
                                                                            <?
                                                                            $TamanoText = "200px";
                                                                            $ClassText  = "copyright";
                                                                            $MaxLength  = "255";
                                                                            $html->TextBox("txRegion", $txRegion);
                                                                            unset($readonly);
                                                                            ?>
                                                                        </td>
                                                                        <td width="4%" align="center" valign="top">
                                                                            &nbsp;</td>
                                                                        <td height="20" align="left" valign="middle"
                                                                            class="textoazul"><?= $auxiliar->traduce("Tipo", $administrador->ID_IDIOMA) . ":" ?>
                                                                        </td>
                                                                        <td align="left" valign="middle">
                                                                            <?
                                                                            $arrTipoCalendario = $bd->valoresEnums("CALENDARIO_FESTIVOS", "TIPO_CALENDARIO");
                                                                            $NombreSelect      = 'selTipoCalendario';
                                                                            $Elementos_rest    = array();

                                                                            $i                           = 0;
                                                                            $Elementos_rest[$i]['text']  = $auxiliar->traduce("Todos", $administrador->ID_IDIOMA);
                                                                            $Elementos_rest[$i]['valor'] = "Todos";
                                                                            $i++;
                                                                            foreach ($arrTipoCalendario as $tipo):
                                                                                $Elementos_rest[$i]['text']  = $auxiliar->traduce($tipo, $administrador->ID_IDIOMA);
                                                                                $Elementos_rest[$i]['valor'] = $tipo;
                                                                                $i++;
                                                                            endforeach;
                                                                            $Tamano = "205px";
                                                                            $Estilo = "copyright";
                                                                            $html->SelectArr($NombreSelect, $Elementos_rest, $selTipoCalendario, "No");
                                                                            unset($disabled);
                                                                            unset($jscript);
                                                                            unset($Estilo);

                                                                            ?>

                                                                        </td>
                                                                    </tr>

                                                                </table>
                                                            </td>
                                                            <td width="4" bgcolor="#D9E3EC" class="lineaderecha">
                                                                &nbsp;</td>
                                                        </tr>
                                                        <tr bgcolor="#D9E3EC">
                                                            <td height="5" colspan="3" bgcolor="#D9E3EC"
                                                                class="lineabajodereizq"><img
                                                                    src="<? echo $pathRaiz ?>imagenes/transparente.gif"
                                                                    width="10" height="5"></td>
                                                        </tr>
                                                    </table>
                                                    <img src="<? echo $pathRaiz ?>imagenes/transparente.gif" width="10"
                                                         height="5"></td>
                                                <td width="20" align="center" valign="bottom" class="lineaizquierda">
                                                    &nbsp;</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="left" valign="top" bgcolor="#B3C7DA" class="lineabajo">
                                        <table width="100%" border="0" cellpadding="0" cellspacing="0"
                                               class="lineabajo">
                                            <tr>
                                                <td height="25" colspan="2" align="center" valign="middle"
                                                    class="lineabajo">
                                                    <div align="right">
                                                        <span class="textoazul">

                                                            <a href="#"
                                                               class="senaladoazul"
                                                               onClick="return anadirCalendarios();">
                                                                &nbsp;&nbsp;&nbsp;<?= $auxiliar->traduce("Añadir a Favoritos", $administrador->ID_IDIOMA) ?>
                                                                &nbsp;&nbsp;&nbsp;</a>
                                                            <a href="#"
                                                               class="senaladoamarillo"
                                                               onClick="document.FormSelect.Buscar.value='Si';document.FormSelect.submit();return false">
                                                                &nbsp;&nbsp;&nbsp;&nbsp;<?= $auxiliar->traduce("Buscar", $administrador->ID_IDIOMA) ?>
                                                                &nbsp;&nbsp;&nbsp;&nbsp;</a>
                                                            &nbsp;
                                                        </span>
                                                    </div>
                                                </td>
                                            </tr>
                                            <? if ($numRegistros > 0): ?>
                                                <tr>
                                                    <td colspan="2" bgcolor="#d9e3ec">
                                                        <table border="0" cellpadding="0" cellspacing="0" height="10">
                                                            <tbody>

                                                            <tr>
                                                                <td width="100%" height="20" colspan="2"
                                                                    class="alertas4">
                                                                    &nbsp;&nbsp;&nbsp;<? echo "$textoLista" ?></td>
                                                            </tr>

                                                            </tbody>
                                                        </table>
                                                    </td>
                                                </tr>
                                                <tr bgcolor="#D9E3EC" class="lineaabajo" height="22">
                                                    <td width="27%" class="copyright">
                                                        &nbsp;&nbsp;&nbsp;<?= $auxiliar->traduce("Ver", $administrador->ID_IDIOMA) ?>
                                                        &nbsp;#
                                                        <? $navegar->GenerarComboNumRegs($navegar->maxfilasMaestroCalendarioFestivos, "selSuperior"); ?>
                                                        &nbsp;&nbsp;
                                                        <? $navegar->NumRegs($navegar->maxfilasMaestroCalendarioFestivos, $maxahora, $navegar->numerofilasMaestroCalendarioFestivos); ?>
                                                    </td>
                                                    <td width="73%" class="copyright">
                                                        <div
                                                            align="right"><? $navegar->Numeros($navegar->sqlAdminMaestroCalendarioFestivos, $navegar->maxfilasMaestroCalendarioFestivos, $navegar->numerofilasMaestroCalendarioFestivos, $i, "index.php", "#2E8AF0") ?>
                                                            &nbsp;&nbsp;&nbsp;</div>
                                                    </td>
                                                </tr>
                                            <? endif ?>
                                            <? if ($numRegistros > 0): ?>
                                                <tr class="lineabajo">
                                                    <td colspan="2" align="center" bgcolor="#D9E3EC">
                                                        <table width="98%" cellpadding="0" cellspacing="2"
                                                               class="linealrededor">

                                                            <tr>
                                                                <td height="19" bgcolor="#2E8AF0" class="blanco"
                                                                    width="2%">
                                                                    <div align="center">
                                                                        <?
                                                                        $valorCheck = '0';
                                                                        if ($chTodos):
                                                                            $disabled   = 'disabled="disabled"';
                                                                            $valorCheck = 1;
                                                                        endif;
                                                                        $jscript = " onClick=\"seleccionarTodasListados(this,'chSelec')\" id='chSelecTodas'";
                                                                        $Nombre  = 'chSelecTodas';
                                                                        $html->Option("chSelecTodas", "Check", "1", $valorCheck);
                                                                        $jscript  = "";
                                                                        $disabled = "";
                                                                        ?>
                                                                    </div>
                                                                </td>
                                                                <td height="19" bgcolor="#2E8AF0"
                                                                    class="blanco"
                                                                    width='5%'><? $navegar->GenerarColumna($auxiliar->traduce("Id", $administrador->ID_IDIOMA), "enlaceCabecera", "id", $pathRaiz) ?></td>
                                                                <td height="19" bgcolor="#2E8AF0"
                                                                    class="blanco"
                                                                    width='10%'><? $navegar->GenerarColumna($auxiliar->traduce("Tipo", $administrador->ID_IDIOMA), "enlaceCabecera", "tipo", $pathRaiz) ?></td>
                                                                <td height="19" bgcolor="#2E8AF0"
                                                                    class="blanco"
                                                                    width='25%'><?= $auxiliar->traduce("Centro Fisico", $administrador->ID_IDIOMA) ?></td>
                                                                <td height="19" bgcolor="#2E8AF0"
                                                                    title="<?= $auxiliar->traduce("Denominacion Centro Fisico", $administrador->ID_IDIOMA) ?>"
                                                                    class="blanco"
                                                                    width='25%'><?= $auxiliar->traduce("Denominacion Centro Fisico", $administrador->ID_IDIOMA) ?></td>
                                                                <td height="19" bgcolor="#2E8AF0"
                                                                    class="blanco"
                                                                    width='10%'><?= $auxiliar->traduce("Region", $administrador->ID_IDIOMA) ?></td>
                                                                <td height="19" bgcolor="#2E8AF0"
                                                                    class="blanco"
                                                                    width='10%'><?= $auxiliar->traduce("Pais", $administrador->ID_IDIOMA) ?></td>
                                                            </tr>
                                                            <? // MUESTRO LAS COINCIDENCIAS CON LA BUSQUEDA
                                                            $i = 0;
                                                            // PARA LA NUMERACION DE CADA URL
                                                            $numeracion = $mostradas + 1;
                                                            while ($i < $maxahora):
                                                                $row            = $bd->SigReg($resultado);

                                                                $nombrePais = "-";
                                                                //CENTRO FISICO
                                                                $referenciaCF   = "-";
                                                                $denominacionCF = "-";
                                                                $region         = "-";
                                                                if ($row->ID_CENTRO_FISICO != ""):
                                                                    $rowCentroFisico = $bd->VerReg("CENTRO_FISICO", "ID_CENTRO_FISICO", $row->ID_CENTRO_FISICO, "No");
                                                                    $denominacionCF  = $rowCentroFisico->DENOMINACION_CENTRO_FISICO != "" ? $rowCentroFisico->DENOMINACION_CENTRO_FISICO : "-";
                                                                    $referenciaCF    = $rowCentroFisico->REFERENCIA;
                                                                    $regionCF        = ucfirst(strtolower((string)$rowCentroFisico->REGION));
                                                                    $rowPais         = $bd->VerReg("PAIS", "ID_PAIS", $rowCentroFisico->ID_PAIS, "No");
                                                                    $nombrePais      = ($administrador->ID_IDIOMA != "" ? $rowPais->DESCRIPCION_ESP : $rowPais->DESCRIPCION_ENG);
                                                                endif;

                                                                //PAIS
                                                                if ($row->ID_PAIS != ""):
                                                                    $rowPais    = $bd->VerReg("PAIS", "ID_PAIS", $row->ID_PAIS, "No");
                                                                    $nombrePais = ($administrador->ID_IDIOMA != "" ? $rowPais->DESCRIPCION_ESP : $rowPais->DESCRIPCION_ENG);
                                                                endif;


                                                                //COLOR DE LA FILA
                                                                if ($i % 2 == 0) $myColor = "#B3C7DA";
                                                                else $myColor = "#AACFF9";
                                                                ?>
                                                                <tr align="center" valign="middle" bgcolor="#AACFF9">
                                                                    <td height="18" align="center"
                                                                        style="white-space: nowrap;"
                                                                        bgcolor="<? echo $myColor ?>"
                                                                        class="enlaceceldas">
                                                                        <?
                                                                        $valorCheck = $chSelec[$row->ID_CALENDARIO_FESTIVOS];
                                                                        if ($chTodos):
                                                                            $disabled   = 'disabled="disabled"';
                                                                            $valorCheck = 1;
                                                                        endif;
                                                                        $html->Option('chSelec[' . $row->ID_CALENDARIO_FESTIVOS . ']', "Check", "1", $valorCheck);
                                                                        unset($disabled);
                                                                        unset($jscript);
                                                                        ?>
                                                                    </td>
                                                                    <td height="18" align="left"
                                                                        bgcolor="<? echo $myColor ?>"
                                                                        class="enlaceceldas">&nbsp;<a target="_blank"
                                                                                                      href="<?= $pathRaiz ?>maestros/calendario_festivos/ficha.php?idCalendarioFestivos=<?= $row->ID_CALENDARIO_FESTIVOS; ?>"
                                                                                                      class="enlaceceldasacceso"><? echo $row->ID_CALENDARIO_FESTIVOS ?></a>&nbsp;
                                                                    </td>
                                                                    <td height="18" align="left"
                                                                        bgcolor="<? echo $myColor ?>"
                                                                        class="enlaceceldas">
                                                                        &nbsp;<? echo $row->TIPO_CALENDARIO ?>&nbsp;
                                                                    </td>
                                                                    <td height="18" align="left"
                                                                        bgcolor="<? echo $myColor ?>"
                                                                        class="enlaceceldas">
                                                                        &nbsp;<? echo $referenciaCF ?>&nbsp;
                                                                    </td>
                                                                    <td height="18" align="left"
                                                                        bgcolor="<? echo $myColor ?>"
                                                                        class="enlaceceldas">
                                                                        &nbsp;<? echo $denominacionCF ?>&nbsp;
                                                                    </td>
                                                                    <td height="18" align="left"
                                                                        bgcolor="<? echo $myColor ?>"
                                                                        class="enlaceceldas">&nbsp;<? echo $regionCF ?>
                                                                        &nbsp;
                                                                    </td>
                                                                    <td height="18" align="left"
                                                                        bgcolor="<? echo $myColor ?>"
                                                                        class="enlaceceldas">
                                                                        &nbsp; <? echo $nombrePais ?>&nbsp;
                                                                    </td>
                                                                </tr>
                                                                <? $i++;
                                                                $numeracion++;
                                                            endwhile; ?>
                                                        </table>
                                                    </td>
                                                </tr>
                                            <? else: ?>
                                                <tr>
                                                    <td colspan="2" align="center" bgcolor="#D9E3EC" class="alertas3"
                                                        height="19px"><?= $auxiliar->traduce("No existen registros para la búsqueda realizada", $administrador->ID_IDIOMA) ?></td>
                                                </tr>
                                            <? endif; ?>
                                            <? if ($numRegistros > 0): ?>
                                                <tr bgcolor="#D9E3EC" class="lineaabajo" height="22">
                                                    <td width="27%" class="copyright">
                                                        &nbsp;&nbsp;&nbsp;<?= $auxiliar->traduce("Ver", $administrador->ID_IDIOMA) ?>
                                                        &nbsp;#
                                                        <? $navegar->GenerarComboNumRegs($navegar->maxfilasMaestroCalendarioFestivos); ?>
                                                        &nbsp;&nbsp;
                                                        <? $navegar->NumRegs($navegar->maxfilasMaestroCalendarioFestivos, $maxahora, $navegar->numerofilasMaestroCalendarioFestivos); ?>
                                                    </td>
                                                    <td width="73%" class="copyright">
                                                        <div
                                                            align="right"><? $navegar->Numeros($navegar->sqlAdminMaestroCalendarioFestivos, $navegar->maxfilasMaestroCalendarioFestivos, $navegar->numerofilasMaestroCalendarioFestivos, $i, "index.php", "#2E8AF0") ?>
                                                            &nbsp;&nbsp;&nbsp;</div>
                                                    </td>
                                                </tr>
                                            <? endif; ?>
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
    <input type="hidden" name="Buscar" value="Si"/>
</FORM>
</body>
</html>