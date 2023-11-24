<?
// PATHS DE LA WEB
$pathRaiz   = "../../";
$pathClases = "../../../";

// INCLUDES DE LIBRERIAS PROPIAS
require_once $pathClases . "lib/basedatos.php";
require_once $pathClases . "lib/administrador.php";
require_once $pathClases . "lib/html.php";
require_once $pathClases . "lib/navegar.php";
require_once $pathClases . "lib/comprobar.php";
require_once $pathClases . "lib/auxiliar.php";

session_start();
include $pathRaiz . "seguridad_admin.php";

$tituloPag         = $auxiliar->traduce("Tipos de Incidencias", $administrador->ID_IDIOMA);
$tituloNav         = $auxiliar->traduce("Maestros", $administrador->ID_IDIOMA) . " >> " . $auxiliar->traduce("Tipos de Incidencias", $administrador->ID_IDIOMA);
$ZonaTablaPadre    = "Maestros";
$ZonaTabla         = "MaestrosIncidenciaSistemaTipo";
$ZonaSubTablaPadre = "MaestrosSubmenuAplicacion";
$PaginaRecordar    = "ListadoMaestrosIncidenciaSistemaTipo";

// COMPRUEBA SI TIENE PERMISOS
if ($administrador->Hayar_Permiso_Perfil('ADM_MAESTROS_INCIDENCIA_SISTEMA_TIPO') < 1):
    $html->PagError("SinPermisos");
endif;

// RECUERDO DE BUSQUEDAS REALIZADAS
include $pathRaiz . "busqueda_recordar.php";

// CONTROLO EL CAMBIO DEL LIMITE
if (!Empty($CambiarLimite)):
    $navegar->maxfilasMaestroIncidenciaSistemaTipo = $selLimite;
endif;

// ORDENACION DE COLUMNAS
$columnas_ord["id_incidencia_sistema_tipo"]    = "ID_INCIDENCIA_SISTEMA_TIPO";
$columnas_ord["incidencia_sistema_tipo"]    = "INCIDENCIA_SISTEMA_TIPO";
$columnas_ord["incidencia_sistema_tipo_eng"]  = "INCIDENCIA_SISTEMA_TIPO_ENG";
$columnas_ord["baja"]   = "BAJA";

$columna_defecto = "id_incidencia_sistema_tipo";
$sentido_defecto = "0"; //ASCENDENTE
$navegar->DefinirColumnasOrdenacion($columnas_ord, $columna_defecto, $sentido_defecto);

//PARA ACOTAR LAS BUSQUEDAS (QUE NO ESTEN BORRADOS)
$sqlTipos = "WHERE 1=1";

//INCIDENCIA_SISTEMA_TIPO
if (trim( (string)$txIncidenciaSistemaTipo) != ""):
    $camposBD   = array('INCIDENCIA_SISTEMA_TIPO');
    $sqlTipos  = $sqlTipos . ($bd->busquedaTextoArray($txIncidenciaSistemaTipo, $camposBD));
    $textoLista = $textoLista . "&" . $auxiliar->traduce("Incidencia Sistema Tipo", $administrador->ID_IDIOMA) . ": " . $txIncidenciaSistemaTipo;
endif;

//INCIDENCIA_SISTEMA_TIPO_ENG
if (trim( (string)$txIncidenciaSistemaTipoEng) != ""):
    $camposBD   = array('INCIDENCIA_SISTEMA_TIPO_ENG');
    $sqlTipos  = $sqlTipos . ($bd->busquedaTextoArray($txIncidenciaSistemaTipoEng, $camposBD));
    $textoLista = $textoLista . "&" . $auxiliar->traduce("Incidencia Sistema Tipo Eng.", $administrador->ID_IDIOMA) . ": " . $txIncidenciaSistemaTipoEng;
endif;

//BAJA
if(!isset($selBaja)):
    $selBaja = 'No';
endif;
if($selBaja == 'Si'):
    $sqlTipos .= " AND (BAJA='1')";
    $textoLista = $textoLista."&".$auxiliar->traduce("Baja",$administrador->ID_IDIOMA).": ".$auxiliar->traduce($selBaja,$administrador->ID_IDIOMA);
elseif($selBaja == 'No'):
    $sqlTipos .= " AND (BAJA='0')";
    $textoLista = $textoLista."&".$auxiliar->traduce("Baja",$administrador->ID_IDIOMA).": ".$auxiliar->traduce($selBaja,$administrador->ID_IDIOMA);
endif;

// TEXTO LISTADO
if ($textoLista == ""):
    $textoLista = $auxiliar->traduce("Todas las incidencias", $administrador->ID_IDIOMA);
else:
    if (substr( (string) $textoLista, 0, 1) == "&") $textoLista = substr( (string) $textoLista, 1);
    $textoSustituir = "</font><font color='#EA62A2'> &gt;&gt; </font><font>";
    $textoLista     = preg_replace("/&/", $textoSustituir, $textoLista);
endif;

$error = "NO";
if ($limite == ""):
    $mySql                          = "SELECT * FROM INCIDENCIA_SISTEMA_TIPO $sqlTipos";
    $navegar->sqlAdminMaestroIncidenciaSistemaTipo = $mySql;
endif;

// REALIZO LA SENTENCIA SQL
$navegar->Sql($navegar->sqlAdminMaestroIncidenciaSistemaTipo, $navegar->maxfilasMaestroIncidenciaSistemaTipo, $navegar->numerofilasMaestroIncidenciaSistemaTipo);

// NUMERO DE REGISTROS
$numRegistros = $navegar->numerofilasMaestroIncidenciaSistemaTipo;

// EXPORTAR A EXCEL
if ($exportar_excel == "1"):
    $sql = $navegar->copiaExport;
    include("exportar_excel.php");
    exit;
endif;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <? require_once $pathClases . "lib/gral_js.php"; ?>
    <script type="text/javascript">
        jQuery(document).ready(function () {
            jQuery("a.fancyboxImportacion").fancybox({
                'type': 'iframe',
                'width': '100%',
                'height': '100%',
                'hideOnOverlayClick': false
            });
        });

        function exportarExcel() {
            document.FormSelect.exportar_excel.value = '1';
            document.FormSelect.submit();
            document.FormSelect.exportar_excel.value = '0';
            return false;
        }
    </script>
</head>
<body bgcolor="#FFFFFF" background="<? echo $pathRaiz ?>imagenes/fondo_pantalla.gif" leftmargin="0" topmargin="0"
      marginwidth="0" marginheight="0" onLoad="document.FormSelect.txIncidenciaSistemaTipo.focus()">
<FORM NAME="FormSelect" ACTION="index.php" METHOD="POST">
    <INPUT TYPE="HIDDEN" NAME="nombre_fichero" VALUE="<?= $tituloPag ?>">
    <INPUT TYPE="HIDDEN" NAME="nombre_hoja" VALUE="Hoja1">
    <INPUT TYPE="HIDDEN" NAME="exportar_excel" VALUE="0">
    <? $navegar->GenerarCamposOcultosForm(); ?>
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
                                                    &nbsp;
                                                </td>
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
                                                                &nbsp;
                                                            </td>
                                                            <td width="100%" align="left" bgcolor="#D9E3EC">
                                                                <table width="97%" border="0" cellpadding="0"
                                                                       cellspacing="0" class="tablaFiltros">
                                                                    <tr>
                                                                        <td width="24%" height="20" align="left"
                                                                            valign="middle"
                                                                            class="textoazul"><?= $auxiliar->traduce("Incidencia Sistema Tipo", $administrador->ID_IDIOMA) . ":" ?>
                                                                        </td>
                                                                        <td width="24%" align="left" valign="middle">
                                                                            <?
                                                                            $TamanoText = "200px";
                                                                            $ClassText  = "copyright";
                                                                            $MaxLength  = "50";
                                                                            $html->TextBox("txIncidenciaSistemaTipo", $txIncidenciaSistemaTipo);
                                                                            ?>
                                                                        </td>
                                                                        <td width="4%" align="center" valign="top">
                                                                            &nbsp;
                                                                        </td>
                                                                        <td width="24%" height="20" align="left"
                                                                            valign="middle"
                                                                            class="textoazul"><?= $auxiliar->traduce("Incidencia Sistema Tipo Eng.", $administrador->ID_IDIOMA) . ":" ?>
                                                                        </td>
                                                                        <td width="24%" align="left" valign="middle"><?
                                                                            $TamanoText = "200px";
                                                                            $ClassText  = "copyright";
                                                                            $MaxLength  = "250";
                                                                            $html->TextBox("txIncidenciaSistemaTipoEng", $txIncidenciaSistemaTipoEng);
                                                                            ?>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td height="20" align="left" valign="middle"
                                                                            class="textoazul">&nbsp;</td>
                                                                        <td align="left" valign="middle"></td>
                                                                        <td width="4%" align="center" valign="top">
                                                                            &nbsp;</td>
                                                                        <td width="24%" height="20" align="left"
                                                                            valign="middle"
                                                                            class="textoazul"><?= $auxiliar->traduce("Baja", $administrador->ID_IDIOMA) . ":" ?>
                                                                        </td>
                                                                        <td width="24%" align="left" valign="middle">
                                                                            <?
                                                                            $NombreSelect = 'selBaja';
                                                                            $Elementos_baja[0]['text'] = $auxiliar->traduce("Todos", $administrador->ID_IDIOMA);
                                                                            $Elementos_baja[0]['valor'] = 'Todos';
                                                                            $Elementos_baja[1]['text'] = $auxiliar->traduce("Si", $administrador->ID_IDIOMA);
                                                                            $Elementos_baja[1]['valor'] = 'Si';
                                                                            $Elementos_baja[2]['text'] = $auxiliar->traduce("No", $administrador->ID_IDIOMA);
                                                                            $Elementos_baja[2]['valor'] = 'No';
                                                                            $Tamano = "205px";
                                                                            $Estilo = "copyright";
                                                                            if (!isset($selBaja)):
                                                                                $selBaja = "No";
                                                                            else:
                                                                                $selBaja = $selBaja;
                                                                            endif;
                                                                            $html->SelectArr($NombreSelect, $Elementos_baja, $selBaja, $selBaja);
                                                                            ?>
                                                                        </td>
                                                                    </tr>
                                                                </table>
                                                            </td>
                                                            <td width="4" bgcolor="#D9E3EC" class="lineaderecha">
                                                                &nbsp;
                                                            </td>
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
                                                    &nbsp;
                                                </td>
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
                                                    <div align="right"><span class="textoazul">

                                                             <!--VENTANA OPCIONES EMERGENTE IMPORTACION MASIVA -->
                                                                <div class="menu_herramientas"
                                                                     style="display: inline-block; ">
                                                                    <a href="#" id="btnAccionesLinea"
                                                                       onmouseenter="ventana_opciones(this,event);return false;"
                                                                       class="senaladoverde botones"
                                                                       style="white-space: nowrap;">
                                                                        &nbsp;&nbsp;
                                                                        <img src="<?= $pathRaiz ?>imagenes/wheel.png"
                                                                             alt="Herramientas"
                                                                             height="16px" width="16px"
                                                                             style="vertical-align: middle;padding-bottom:2px;"/>
                                                                        <? echo $auxiliar->traduce("Importacion Masiva", $administrador->ID_IDIOMA) ?>
                                                                        &nbsp;&nbsp;&nbsp;&nbsp;
                                                                    </a>&nbsp;
                                                                    <ul>
                                                                        <li>
                                                                            <a href="ficha_importancion_excel_paso1.php"
                                                                               class="copyright botones fancyboxImportacion">
                                                                                <img
                                                                                        src="<?= $pathRaiz ?>imagenes/excel.png"
                                                                                        border="0"/>
                                                                                &nbsp;&nbsp;&nbsp;
                                                                                <?= $auxiliar->traduce("Importacion Masiva", $administrador->ID_IDIOMA) . "(Excel)" ?>
                                                                                &nbsp;&nbsp;&nbsp;
                                                                            </a>
                                                                        </li>
                                                                        <li>
                                                                            <a href="ficha_importacion_csv_paso1.php"
                                                                               class="copyright botones fancyboxImportacion">
                                                                                <img
                                                                                        src="<?= $pathRaiz ?>imagenes/add_document.png"
                                                                                        border="0"/>
                                                                                &nbsp;&nbsp;&nbsp;
                                                                                <?= $auxiliar->traduce("Importacion Masiva", $administrador->ID_IDIOMA) . "(CSV)" ?>
                                                                                &nbsp;&nbsp;&nbsp;
                                                                            </a>
                                                                        </li>
                                                                        <li>
                                                                            <a href="ficha_importacion_copiar_pegar_paso1.php"
                                                                               class="copyright botones fancyboxImportacion">
                                                                                <img
                                                                                        src="<?= $pathRaiz ?>imagenes/edit_form.png"
                                                                                        name="DeshacerAnulaciones"
                                                                                        border="0"/>
                                                                                &nbsp;&nbsp;&nbsp;
                                                                                <?= $auxiliar->traduce("Copiar y Pegar", $administrador->ID_IDIOMA) ?>
                                                                                &nbsp;&nbsp;&nbsp;
                                                                            </a>
                                                                        </li>
                                                                    </ul>
                                                                </div>
                                                            <!--FIN VENTANA OPCIONES EMERGENTE IMPORTACION MASIVA -->

                                                            <a href="ficha.php" class="senaladoazul">&nbsp;&nbsp;&nbsp;&nbsp;<?= $auxiliar->traduce("Crear", $administrador->ID_IDIOMA) ?>&nbsp;&nbsp;&nbsp;&nbsp;</a>&nbsp;
                                                            <a href="#" class="senaladoazul"
                                                               onClick="return exportarExcel();">&nbsp;&nbsp;&nbsp;<?= $auxiliar->traduce("Excel", $administrador->ID_IDIOMA) ?>&nbsp;&nbsp;&nbsp;</a>&nbsp;
                                                            <a href="#" class="senaladoamarillo"
                                                               onClick="document.FormSelect.Buscar.value='Si';document.FormSelect.submit();return false">&nbsp;&nbsp;&nbsp;&nbsp;<?= $auxiliar->traduce("Buscar", $administrador->ID_IDIOMA) ?>&nbsp;&nbsp;&nbsp;&nbsp;</a>&nbsp;
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
                                            <? endif ?>
                                            <? if ($numRegistros > 0): ?>
                                                <tr bgcolor="#D9E3EC" class="lineaabajo" height="22">
                                                    <td width="27%" class="copyright">
                                                        &nbsp;&nbsp;&nbsp;<?= $auxiliar->traduce("Ver", $administrador->ID_IDIOMA) ?>
                                                        #
                                                        <? $navegar->GenerarComboNumRegs($navegar->maxfilasMaestroIncidenciaSistemaTipo, "selLimiteSuperior"); ?>
                                                        &nbsp;&nbsp;
                                                        <? $navegar->NumRegs($navegar->maxfilasMaestroIncidenciaSistemaTipo, $maxahora, $navegar->numerofilasMaestroIncidenciaSistemaTipo); ?>
                                                    </td>
                                                    <td width="73%" class="copyright">
                                                        <div align="right"><? $navegar->Numeros($navegar->sqlAdminMaestroIncidenciaSistemaTipo, $navegar->maxfilasMaestroIncidenciaSistemaTipo, $navegar->numerofilasMaestroIncidenciaSistemaTipo, $i, "index.php", "#2E8AF0"); ?>
                                                            &nbsp;&nbsp;&nbsp;
                                                        </div>
                                                    </td>
                                                </tr>
                                                <tr class="lineabajo">
                                                    <td colspan="2" align="center" bgcolor="#D9E3EC">
                                                        <table width="98%" cellpadding="0" cellspacing="2"
                                                               class="linealrededor">

                                                            <tr>
                                                                <td height="19" bgcolor="#2E8AF0"
                                                                    class="blanco"><? $navegar->GenerarColumna($auxiliar->traduce("Id Tipo Incidencia", $administrador->ID_IDIOMA), "enlaceCabecera", "id_incidencia_sistema_tipo", $pathRaiz) ?></td>
                                                                <td height="19" bgcolor="#2E8AF0"
                                                                    class="blanco"><? $navegar->GenerarColumna($auxiliar->traduce("Incidencia Sistema Tipo", $administrador->ID_IDIOMA), "enlaceCabecera", "incidencia_sistema_tipo", $pathRaiz) ?></td>
                                                                <td height="19" bgcolor="#2E8AF0"
                                                                    class="blanco"><? $navegar->GenerarColumna($auxiliar->traduce("Incidencia Sistema Tipo Eng.", $administrador->ID_IDIOMA), "enlaceCabecera", "incidencia_sistema_tipo_eng", $pathRaiz) ?></td>
                                                                <td height="19" bgcolor="#2E8AF0"
                                                                    class="blanco"><? $navegar->GenerarColumna($auxiliar->traduce("Baja", $administrador->ID_IDIOMA), "enlaceCabecera", "baja", $pathRaiz); ?></td>
                                                            </tr>
                                                            <? // MUESTRO LAS COINCIDENCIAS CON LA BUSQUEDA
                                                            $i = 0;
                                                            // PARA LA NUMERACION DE CADA URL
                                                            $numeracion = $mostradas + 1;
                                                            while ($i < $maxahora):
                                                                $row = $bd->SigReg($resultado);
                                                                
                                                                //COLOR DE LA FILA
                                                                if ($i % 2 == 0) $myColor = "#B3C7DA";
                                                                else $myColor = "#AACFF9";
                                                                ?>
                                                                <tr align="center" valign="middle" bgcolor="#AACFF9">
                                                                    <td height="18" align="left"
                                                                        bgcolor="<? echo $myColor ?>"
                                                                        class="enlaceceldas">&nbsp;<a
                                                                                href="ficha.php?idIncidenciaSistemaTipo=<?= $row->ID_INCIDENCIA_SISTEMA_TIPO; ?>"
                                                                                class="enlaceceldasacceso"><? echo $row->ID_INCIDENCIA_SISTEMA_TIPO ?></a>&nbsp;
                                                                    </td>
                                                                    <td height="18" align="left"
                                                                        bgcolor="<? echo $myColor ?>"
                                                                        class="enlaceceldas">&nbsp;<? echo $row->INCIDENCIA_SISTEMA_TIPO ?></a>&nbsp;
                                                                    </td>
                                                                    <td height="18" align="left"
                                                                        bgcolor="<? echo $myColor ?>"
                                                                        class="enlaceceldas">
                                                                        &nbsp;<? echo (!empty($row->INCIDENCIA_SISTEMA_TIPO_ENG)) ? $row->INCIDENCIA_SISTEMA_TIPO_ENG : '-' ?>
                                                                    </td>
                                                                    <td height="18" align="center"
                                                                        bgcolor="<? echo $myColor ?>"
                                                                        class="enlaceceldas">
                                                                        <?
                                                                        if($row->BAJA == 0 || $row->BAJA == "0") echo $auxiliar->traduce("No", $administrador->ID_IDIOMA);
                                                                        else echo $auxiliar->traduce("Si", $administrador->ID_IDIOMA);
                                                                        ?>
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
                                                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?= $auxiliar->traduce("Ver", $administrador->ID_IDIOMA) ?>
                                                        &nbsp;#
                                                        <? $navegar->GenerarComboNumRegs($navegar->maxfilasMaestroIncidenciaSistemaTipo); ?>
                                                        &nbsp;&nbsp;
                                                        <? $navegar->NumRegs($navegar->maxfilasMaestroIncidenciaSistemaTipo, $maxahora, $navegar->numerofilasMaestroIncidenciaSistemaTipo); ?>
                                                    </td>
                                                    <td width="73%" class="copyright">
                                                        <div align="right"><? $navegar->Numeros($navegar->sqlAdminMaestroIncidenciaSistemaTipo, $navegar->maxfilasMaestroIncidenciaSistemaTipo, $navegar->numerofilasMaestroIncidenciaSistemaTipo, $i, "index.php", "#2E8AF0"); ?>
                                                            &nbsp;&nbsp;&nbsp;
                                                            &nbsp;&nbsp;&nbsp;
                                                        </div>
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