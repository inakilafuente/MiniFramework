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
$ZonaTabla         = "MaestrosMateriales";
$ZonaSubTablaPadre = "MaestrosSubmenuMateriales";
$PaginaRecordar    = "ListadoMaestrosMateriales";

// COMPRUEBA SI TIENE PERMISOS
if ($administrador->Hayar_Permiso_Perfil('ADM_MAESTROS_MATERIALES') < 1):
    $html->PagError("SinPermisos");
endif;

// RECUERDO DE BUSQUEDAS REALIZADAS
include $pathRaiz . "busqueda_recordar.php";

// CONTROLO EL CAMBIO DEL LIMITE
if (!Empty($CambiarLimite)):
    $navegar->maxfilasMaestroMaterial = $selLimite;
endif;

// ORDENACION DE COLUMNAS
$columnas_ord["id_incidencia_sistema_tipo"]    = "ID_INCIDENCIA_SISTEMA_TIPO";
$columnas_ord["incidencia_sistema_tipo"]    = "INCIDENCIA_SISTEMA_TIPO";
$columnas_ord["incidencia_sistema_tipo_eng"]  = "INCIDENCIA_SISTEMA_TIPO_ENG";
$columnas_ord["baja"]   = "BAJA";

$columna_defecto = "id_incidencia_sistema_tipo";
$sentido_defecto = "0"; //ASCENDENTE
//$navegar->DefinirColumnasOrdenacion($columnas_ord, $columna_defecto, $sentido_defecto);

//PARA ACOTAR LAS BUSQUEDAS (QUE NO ESTEN BORRADOS)
$sqlTipos = "WHERE 1=1";

//N� Material
if (trim( (string)$txMaterial) != ""):
    $camposBD   = array('REFERENCIA_SCS');
    $sqlTipos  = $sqlTipos . ($bd->busquedaTextoArray($txMaterial, $camposBD));
    $textoLista = $textoLista . "&" . $auxiliar->traduce("ID Material", $administrador->ID_IDIOMA) . ": " . $txMaterial;
endif;

//INCIDENCIA_SISTEMA_TIPO_ENG
if (trim( (string)$txIncidenciaSistemaTipoEng) != ""):
    $camposBD   = array('MATERIALES_ENG');
    $sqlTipos  = $sqlTipos . ($bd->busquedaTextoArray($txIncidenciaSistemaTipoEng, $camposBD));
    $textoLista = $textoLista . "&" . $auxiliar->traduce("Incidencia Sistema Tipo Eng.", $administrador->ID_IDIOMA) . ": " . $txIncidenciaSistemaTipoEng;
endif;

//BAJA
if(!isset($selBaja)):
    $selBaja = 'No';
endif;
if($selBaja == 'Si'):
    $sqlTipos .= " AND (MATERIALES.BAJA='1')";
    $textoLista = $textoLista."&".$auxiliar->traduce("Baja",$administrador->ID_IDIOMA).": ".$auxiliar->traduce($selBaja,$administrador->ID_IDIOMA);
elseif($selBaja == 'No'):
    $sqlTipos .= " AND (MATERIALES.BAJA='0')";
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
    $mySql                          = "SELECT * FROM MATERIALES 
    JOIN FAMILIA_MATERIAL ON MATERIALES.FK_FAMILIA_MATERIAL=FAMILIA_MATERIAL.ID_FAMILIA_MATERIAL
    JOIN FAMILIA_REPRO ON MATERIALES.FK_FAMILIA_REPRO=FAMILIA_REPRO.ID_FAMILIA_REPRO
    JOIN UNIDAD ON UNIDAD.ID_UNIDAD=MATERIALES.FK_UNIDAD_COMPRA ".$sqlTipos
    ;
    $navegar->sqlAdminMaestroMaterial = $mySql;
endif;

// REALIZO LA SENTENCIA SQL
$navegar->Sql($navegar->sqlAdminMaestroMaterial, $navegar->maxfilasMaestroMaterial, $navegar->numerofilasMaestroMaterial);

// NUMERO DE REGISTROS
$numRegistros = $navegar->numerofilasMaestroMaterial;

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
                                                                            class="textoazul"><?= $auxiliar->traduce("Material", $administrador->ID_IDIOMA) . ":" ?>
                                                                        </td>
                                                                        <td width="24%" align="left" valign="middle">
                                                                            <?
                                                                            $TamanoText = "200px";
                                                                            $ClassText  = "copyright";
                                                                            $MaxLength  = "50";
                                                                            $html->TextBox("txMaterial", $txMaterial);
                                                                            ?>
                                                                        </td>
                                                                        <td width="24%" height="20" align="left"
                                                                            valign="middle"
                                                                            class="textoazul"><?= $auxiliar->traduce("Estatus Material", $administrador->ID_IDIOMA) . ":" ?>
                                                                        </td>
                                                                        <td width="24%" align="left" valign="middle">
                                                                            <?
                                                                            $NombreSelect = 'selEstatus';
                                                                            $Elementos_estatus[0]['text'] = $auxiliar->traduce("01-Bloqueo General", $administrador->ID_IDIOMA);
                                                                            $Elementos_estatus[0]['valor'] = '01-Bloqueo General';
                                                                            $Elementos_estatus[1]['text'] = $auxiliar->traduce("02-Obsoleto Fin Existencias (Error)", $administrador->ID_IDIOMA);
                                                                            $Elementos_estatus[1]['valor'] = '02-Obsoleto Fin Existencias (Error)';
                                                                            $Elementos_estatus[2]['text'] = $auxiliar->traduce("03-C�digo duplicado", $administrador->ID_IDIOMA);
                                                                            $Elementos_estatus[2]['valor'] = '03-C�digo duplicado';
                                                                            $Elementos_estatus[3]['text'] = $auxiliar->traduce("04-C�digo inutilizable", $administrador->ID_IDIOMA);
                                                                            $Elementos_estatus[3]['valor'] = '04-C�digo inutilizable';
                                                                            $Elementos_estatus[4]['text'] = $auxiliar->traduce("05-Obsoleto Fin Existencias (Aviso)", $administrador->ID_IDIOMA);
                                                                            $Elementos_estatus[4]['valor'] = '05-Obsoleto Fin Existencias (Aviso)';
                                                                            $Elementos_estatus[5]['text'] = $auxiliar->traduce("06-C�digo Solo Fines Log�sticos", $administrador->ID_IDIOMA);
                                                                            $Elementos_estatus[5]['valor'] = '06-C�digo Solo Fines Log�stico';
                                                                            $Elementos_estatus[6]['text'] = $auxiliar->traduce("07-Solo para Refer.Prov", $administrador->ID_IDIOMA);
                                                                            $Elementos_estatus[6]['valor'] = '07-Solo para Refer.Prov';
                                                                            $Elementos_estatus[7]['text'] = $auxiliar->traduce("No bloqueado", $administrador->ID_IDIOMA);
                                                                            $Elementos_estatus[7]['valor'] = 'No bloqueado';
                                                                            $Tamano = "205px";
                                                                            $Estilo = "copyright";

                                                                            $html->SelectArr($NombreSelect, $Elementos_estatus, 'No bloqueado');
                                                                            ?>
                                                                        </td>
                                                                        <td width="4%" align="center" valign="top">
                                                                            &nbsp;
                                                                        </td>

                                                                    </tr>
                                                                    <tr>
                                                                        <td width="24%" height="20" align="left"
                                                                            valign="middle"
                                                                            class="textoazul"><?= $auxiliar->traduce("RA", $administrador->ID_IDIOMA) . ":" ?>
                                                                        </td>
                                                                        <td width="24%" align="left" valign="middle">
                                                                            <?
                                                                            $NombreSelect = 'selRA';
                                                                            $Elementos_RA[0]['text'] = $auxiliar->traduce("Si", $administrador->ID_IDIOMA);
                                                                            $Elementos_RA[0]['valor'] = 'Si';
                                                                            $Elementos_RA[1]['text'] = $auxiliar->traduce("No", $administrador->ID_IDIOMA);
                                                                            $Elementos_RA[1]['valor'] = 'No';
                                                                            $Tamano = "205px";
                                                                            $Estilo = "copyright";

                                                                            $html->SelectArr($NombreSelect, $Elementos_RA, 'Si');
                                                                            ?>
                                                                        </td>
                                                                        <td width="24%" height="20" align="left"
                                                                            valign="middle"
                                                                            class="textoazul"><?= $auxiliar->traduce("Familia Material", $administrador->ID_IDIOMA) . ":" ?>
                                                                        </td>
                                                                        <td width="24%" align="left" valign="middle">
                                                                            <?
                                                                            $TamanoText = "200px";
                                                                            $ClassText  = "copyright";
                                                                            $MaxLength  = "50";
                                                                            $html->TextBox("txIncidenciaSistemaTipo", $txIncidenciaSistemaTipo);
                                                                            ?>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td width="24%" height="20" align="left"
                                                                            valign="middle"
                                                                            class="textoazul"><?= $auxiliar->traduce("Desc. Material", $administrador->ID_IDIOMA) . ":" ?>
                                                                        </td>
                                                                        <td width="24%" align="left" valign="middle">
                                                                            <?
                                                                            $TamanoText = "200px";
                                                                            $ClassText  = "copyright";
                                                                            $MaxLength  = "50";
                                                                            $html->TextBox("txIncidenciaSistemaTipo", $txIncidenciaSistemaTipo);
                                                                            ?>
                                                                        </td>
                                                                        <td width="24%" height="20" align="left"
                                                                            valign="middle"
                                                                            class="textoazul"><?= $auxiliar->traduce("Familia Repro", $administrador->ID_IDIOMA) . ":" ?>
                                                                        </td>
                                                                        <td width="24%" align="left" valign="middle">
                                                                            <?
                                                                            $TamanoText = "200px";
                                                                            $ClassText  = "copyright";
                                                                            $MaxLength  = "50";
                                                                            $html->TextBox("txIncidenciaSistemaTipo", $txIncidenciaSistemaTipo);
                                                                            ?>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td width="24%" height="20" align="left"
                                                                            valign="middle"
                                                                            class="textoazul"><?= $auxiliar->traduce("Marca", $administrador->ID_IDIOMA) . ":" ?>
                                                                        </td>
                                                                        <td width="24%" align="left" valign="middle">
                                                                            <?
                                                                            $TamanoText = "200px";
                                                                            $ClassText  = "copyright";
                                                                            $MaxLength  = "50";
                                                                            $html->TextBox("txIncidenciaSistemaTipo", $txIncidenciaSistemaTipo);
                                                                            ?>
                                                                        </td>
                                                                        <td width="24%" height="20" align="left"
                                                                            valign="middle"
                                                                            class="textoazul"><?= $auxiliar->traduce("Con unidad de manipulaci�n", $administrador->ID_IDIOMA) . ":" ?>
                                                                        </td>
                                                                        <td width="24%" align="left" valign="middle">
                                                                            <?
                                                                            $NombreSelect = 'selManipulacion';
                                                                            $Elementos_manipulacion[0]['text'] = $auxiliar->traduce("Si", $administrador->ID_IDIOMA);
                                                                            $Elementos_manipulacion[0]['valor'] = 'Si';
                                                                            $Elementos_manipulacion[1]['text'] = $auxiliar->traduce("No", $administrador->ID_IDIOMA);
                                                                            $Elementos_manipulacion[1]['valor'] = 'No';
                                                                            $Elementos_manipulacion[2]['text'] = $auxiliar->traduce("Pendiente decisi�n", $administrador->ID_IDIOMA);
                                                                            $Elementos_manipulacion[2]['valor'] = 'Pendiente decisi�n';
                                                                            $Elementos_manipulacion[3]['text'] = $auxiliar->traduce("No Aplica", $administrador->ID_IDIOMA);
                                                                            $Elementos_manipulacion[3]['valor'] = 'No Aplica';
                                                                            $Tamano = "205px";
                                                                            $Estilo = "copyright";

                                                                            $html->SelectArr($NombreSelect, $Elementos_manipulacion, 'No Aplica');
                                                                            ?>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td width="24%" height="20" align="left"
                                                                            valign="middle"
                                                                            class="textoazul"><?= $auxiliar->traduce("Modelo", $administrador->ID_IDIOMA) . ":" ?>
                                                                        </td>
                                                                        <td width="24%" align="left" valign="middle">
                                                                            <?
                                                                            $TamanoText = "200px";
                                                                            $ClassText  = "copyright";
                                                                            $MaxLength  = "50";
                                                                            $html->TextBox("txIncidenciaSistemaTipo", $txIncidenciaSistemaTipo);
                                                                            ?>
                                                                        </td>
                                                                        <td width="24%" height="20" align="left"
                                                                            valign="middle"
                                                                            class="textoazul"><?= $auxiliar->traduce("Unidad Base", $administrador->ID_IDIOMA) . ":" ?>
                                                                        </td>
                                                                        <td width="24%" align="left" valign="middle">
                                                                            <?
                                                                            $TamanoText = "200px";
                                                                            $ClassText  = "copyright";
                                                                            $MaxLength  = "50";
                                                                            $html->TextBox("txIncidenciaSistemaTipo", $txIncidenciaSistemaTipo);
                                                                            ?>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td width="24%" height="20" align="left"
                                                                            valign="middle"
                                                                            class="textoazul"><?= $auxiliar->traduce("Tipo Material", $administrador->ID_IDIOMA) . ":" ?>
                                                                        </td>
                                                                        <td width="24%" align="left" valign="middle">
                                                                            <?
                                                                            $NombreSelect = 'selTipo';
                                                                            $Elementos_tipo[0]['text'] = $auxiliar->traduce("Peque�o Repuesto", $administrador->ID_IDIOMA);
                                                                            $Elementos_tipo[0]['valor'] = 'Peque�o Repuesto';
                                                                            $Elementos_tipo[1]['text'] = $auxiliar->traduce("Gran Componente", $administrador->ID_IDIOMA);
                                                                            $Elementos_tipo[1]['valor'] = 'Gran Componente';
                                                                            $Elementos_tipo[2]['text'] = $auxiliar->traduce("Consumibles", $administrador->ID_IDIOMA);
                                                                            $Elementos_tipo[2]['valor'] = 'Consumibles';
                                                                            $Elementos_tipo[3]['text'] = $auxiliar->traduce("Heramienta", $administrador->ID_IDIOMA);
                                                                            $Elementos_tipo[3]['valor'] = 'Heramienta';
                                                                            $Elementos_tipo[4]['text'] = $auxiliar->traduce("Materias Primas", $administrador->ID_IDIOMA);
                                                                            $Elementos_tipo[4]['valor'] = 'Materias Primas';
                                                                            $Elementos_tipo[5]['text'] = $auxiliar->traduce("Material de Oficina", $administrador->ID_IDIOMA);
                                                                            $Elementos_tipo[5]['valor'] = 'Material de Oficina';
                                                                            $Elementos_tipo[6]['text'] = $auxiliar->traduce("Otros Materiales", $administrador->ID_IDIOMA);
                                                                            $Elementos_tipo[6]['valor'] = 'Otros Materiales';
                                                                            $Elementos_tipo[7]['text'] = $auxiliar->traduce("Servicios Acciona", $administrador->ID_IDIOMA);
                                                                            $Elementos_tipo[7]['valor'] = 'Servicios Acciona';
                                                                            $Elementos_tipo[8]['text'] = $auxiliar->traduce("Pruebas log�sticas", $administrador->ID_IDIOMA);
                                                                            $Elementos_tipo[8]['valor'] = 'Pruebas log�sticas';
                                                                            $Elementos_tipo[9]['text'] = $auxiliar->traduce("C�digo I&C", $administrador->ID_IDIOMA);
                                                                            $Elementos_tipo[9]['valor'] = 'C�digo I&C';
                                                                            $Tamano = "205px";
                                                                            $Estilo = "copyright";

                                                                            $html->SelectArr($NombreSelect, $Elementos_tipo, 'Peque�o Repuesto');
                                                                            ?>
                                                                        </td>
                                                                        <td width="24%" height="20" align="left"
                                                                            valign="middle"
                                                                            class="textoazul"><?= $auxiliar->traduce("Unidad Compra", $administrador->ID_IDIOMA) . ":" ?>
                                                                        </td>
                                                                        <td width="24%" align="left" valign="middle">
                                                                            <?
                                                                            $TamanoText = "200px";
                                                                            $ClassText  = "copyright";
                                                                            $MaxLength  = "50";
                                                                            $html->TextBox("txIncidenciaSistemaTipo", $txIncidenciaSistemaTipo);
                                                                            ?>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td width="24%" height="20" align="left"
                                                                            valign="middle"
                                                                            class="textoazul"><?= $auxiliar->traduce("Observaciones", $administrador->ID_IDIOMA) . ":" ?>
                                                                        </td>
                                                                        <td width="24%" align="left" valign="middle">
                                                                            <?
                                                                            $TamanoText = "200px";
                                                                            $ClassText  = "copyright";
                                                                            $MaxLength  = "50";
                                                                            $html->TextBox("txIncidenciaSistemaTipo", $txIncidenciaSistemaTipo);
                                                                            ?>
                                                                        </td>
                                                                        <td width="24%" height="20" align="left"
                                                                            valign="middle"
                                                                            class="textoazul"><?= $auxiliar->traduce("Divisibilidad", $administrador->ID_IDIOMA) . ":" ?>
                                                                        </td>
                                                                        <td width="24%" align="left" valign="middle">
                                                                            <?
                                                                            $NombreSelect = 'selDivisibilidad';
                                                                            $Elementos_divisibilidad[0]['text'] = $auxiliar->traduce("Si", $administrador->ID_IDIOMA);
                                                                            $Elementos_divisibilidad[0]['valor'] = 'Si';
                                                                            $Elementos_divisibilidad[1]['text'] = $auxiliar->traduce("No", $administrador->ID_IDIOMA);
                                                                            $Elementos_divisibilidad[1]['valor'] = 'No';
                                                                            $Elementos_divisibilidad[2]['text'] = $auxiliar->traduce("Pendiente decisi�n", $administrador->ID_IDIOMA);
                                                                            $Elementos_divisibilidad[2]['valor'] = 'Pendiente decisi�n';
                                                                            $Elementos_divisibilidad[3]['text'] = $auxiliar->traduce("No Aplica", $administrador->ID_IDIOMA);
                                                                            $Elementos_divisibilidad[3]['valor'] = 'No Aplica';
                                                                            $Tamano = "205px";
                                                                            $Estilo = "copyright";

                                                                            $html->SelectArr($NombreSelect, $Elementos_divisibilidad, 'No Aplica');
                                                                            ?>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td width="24%" height="20" align="left"
                                                                            valign="middle"
                                                                            class="textoazul"><?= $auxiliar->traduce("Tecnolog�a", $administrador->ID_IDIOMA) . ":" ?>
                                                                        </td>
                                                                        <td width="24%" align="left" valign="middle">
                                                                            <?
                                                                            $TamanoText = "200px";
                                                                            $ClassText  = "copyright";
                                                                            $MaxLength  = "50";
                                                                            $html->TextBox("txIncidenciaSistemaTipo", $txIncidenciaSistemaTipo);
                                                                            ?>
                                                                        </td>
                                                                        <td width="24%" height="20" align="left"
                                                                            valign="middle"
                                                                            class="textoazul"><?= $auxiliar->traduce("Tipo E�lica", $administrador->ID_IDIOMA) . ":" ?>
                                                                        </td>
                                                                        <td width="24%" align="left" valign="middle">
                                                                            <?
                                                                            $TamanoText = "200px";
                                                                            $ClassText  = "copyright";
                                                                            $MaxLength  = "50";
                                                                            $html->TextBox("txIncidenciaSistemaTipo", $txIncidenciaSistemaTipo);
                                                                            ?>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td width="24%" height="20" align="left"
                                                                            valign="middle"
                                                                            class="textoazul"><?= $auxiliar->traduce("Ver Observaciones", $administrador->ID_IDIOMA) . ":" ?>
                                                                        </td>
                                                                        <td width="24%" align="left" valign="middle">
                                                                            <?
                                                                            $TamanoText = "200px";
                                                                            $ClassText  = "copyright";
                                                                            $MaxLength  = "50";

                                                                            ?>
                                                                            <input type="checkbox" id="cboxObservaciones" value="Ver_Observacioens" />
                                                                        </td>
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
                                                                        <td height="20" align="left" valign="middle"
                                                                            class="textoazul">&nbsp;</td>
                                                                        <td align="left" valign="middle"></td>
                                                                        <td width="4%" align="center" valign="top">
                                                                            &nbsp;</td>



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
                                                        <? $navegar->GenerarComboNumRegs($navegar->maxfilasMaestroMaterial, "selLimiteSuperior"); ?>
                                                        &nbsp;&nbsp;
                                                        <? $navegar->NumRegs($navegar->maxfilasMaestroMaterial, $maxahora, $navegar->numerofilasMaestroMaterial); ?>
                                                    </td>
                                                    <td width="73%" class="copyright">
                                                        <div align="right"><? $navegar->Numeros($navegar->sqlAdminMaestroMaterial, $navegar->maxfilasMaestroMaterial, $navegar->numerofilasMaestroMaterial, $i, "index.php", "#2E8AF0"); ?>
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
                                                                    class="blanco"><? $navegar->GenerarColumna($auxiliar->traduce("N� Material", $administrador->ID_IDIOMA), "enlaceCabecera", "id_incidencia_sistema_tipo", $pathRaiz) ?></td>
                                                               <td height="19" bgcolor="#2E8AF0"
                                                                    class="blanco"><? $navegar->GenerarColumna($auxiliar->traduce("Descripcion Material", $administrador->ID_IDIOMA), "enlaceCabecera", "incidencia_sistema_tipo_eng", $pathRaiz) ?></td>
                                                                <td height="19" bgcolor="#2E8AF0"
                                                                    class="blanco"><? $navegar->GenerarColumna($auxiliar->traduce("Tipo Material", $administrador->ID_IDIOMA), "enlaceCabecera", "incidencia_sistema_tipo_eng", $pathRaiz) ?></td>
                                                                <td height="19" bgcolor="#2E8AF0"
                                                                    class="blanco"><? $navegar->GenerarColumna($auxiliar->traduce("Familia material", $administrador->ID_IDIOMA), "enlaceCabecera", "incidencia_sistema_tipo_eng", $pathRaiz) ?></td>
                                                                <td height="19" bgcolor="#2E8AF0"
                                                                    class="blanco"><? $navegar->GenerarColumna($auxiliar->traduce("Familia repro", $administrador->ID_IDIOMA), "enlaceCabecera", "incidencia_sistema_tipo_eng", $pathRaiz) ?></td>
                                                                <td height="19" bgcolor="#2E8AF0"
                                                                    class="blanco"><? $navegar->GenerarColumna($auxiliar->traduce("Marca", $administrador->ID_IDIOMA), "enlaceCabecera", "incidencia_sistema_tipo_eng", $pathRaiz) ?></td>
                                                                <td height="19" bgcolor="#2E8AF0"
                                                                    class="blanco"><? $navegar->GenerarColumna($auxiliar->traduce("Modelo", $administrador->ID_IDIOMA), "enlaceCabecera", "incidencia_sistema_tipo_eng", $pathRaiz) ?></td>
                                                                <td height="19" bgcolor="#2E8AF0"
                                                                    class="blanco"><? $navegar->GenerarColumna($auxiliar->traduce("UM", $administrador->ID_IDIOMA), "enlaceCabecera", "incidencia_sistema_tipo_eng", $pathRaiz) ?></td>
                                                                <td height="19" bgcolor="#2E8AF0"
                                                                    class="blanco"><? $navegar->GenerarColumna($auxiliar->traduce("Div", $administrador->ID_IDIOMA), "enlaceCabecera", "incidencia_sistema_tipo_eng", $pathRaiz) ?></td>
                                                                <td height="19" bgcolor="#2E8AF0"
                                                                    class="blanco"><? $navegar->GenerarColumna($auxiliar->traduce("RA", $administrador->ID_IDIOMA), "enlaceCabecera", "incidencia_sistema_tipo_eng", $pathRaiz) ?></td>
                                                                <td height="19" bgcolor="#2E8AF0"
                                                                    class="blanco"><? $navegar->GenerarColumna($auxiliar->traduce("O", $administrador->ID_IDIOMA), "enlaceCabecera", "incidencia_sistema_tipo_eng", $pathRaiz) ?></td>
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
                                                                                href="ficha.php?idIncidenciaSistemaTipo=<?= $row->REFERENCIA_SCS; ?>"
                                                                                class="enlaceceldasacceso"><? echo $row->REFERENCIA_SCS ?></a>&nbsp;
                                                                    </td>
                                                                    <?php if(($administrador->ID_IDIOMA)=='ESP'){?>
                                                                    <td height="18" align="left"
                                                                        bgcolor="<? echo $myColor ?>"
                                                                        class="enlaceceldas">&nbsp;<? echo $row->DESCRIPCION_ESP ?></a>&nbsp;
                                                                    </td>
                                                                    <?php }else{ ?>
                                                                    <td height="18" align="left"
                                                                        bgcolor="<? echo $myColor ?>"
                                                                        class="enlaceceldas">
                                                                        &nbsp;<? echo (!empty($row->DESCRIPCION_ENG)) ? $row->DESCRIPCION_ENG : '-' ?>
                                                                    </td>
                                                                    <?php }?>
                                                                    <td height="18" align="left"
                                                                        bgcolor="<? echo $myColor ?>"
                                                                        class="enlaceceldas">
                                                                        &nbsp;<? echo (!empty($row->TIPO_MATERIAL)) ? $row->TIPO_MATERIAL : '-' ?>
                                                                    </td>
                                                                    <td height="18" align="left"
                                                                        bgcolor="<? echo $myColor ?>"
                                                                        class="enlaceceldas">
                                                                        &nbsp;<? echo (!empty($row->FK_FAMILIA_MATERIAL)) ? $row->NOMBRE_FAMILIA : '-' ?>
                                                                    </td>
                                                                    <td height="18" align="left"
                                                                        bgcolor="<? echo $myColor ?>"
                                                                        class="enlaceceldas">
                                                                        &nbsp;<? echo (!empty($row->FK_FAMILIA_REPRO)) ? $row->FAMILIA_REPRO : '-' ?>
                                                                    </td>
                                                                    <td height="18" align="left"
                                                                        bgcolor="<? echo $myColor ?>"
                                                                        class="enlaceceldas">
                                                                        &nbsp;<? echo (!empty($row->MARCA)) ? $row->MARCA : '-' ?>
                                                                    </td>
                                                                    <td height="18" align="left"
                                                                        bgcolor="<? echo $myColor ?>"
                                                                        class="enlaceceldas">
                                                                        &nbsp;<? echo (!empty($row->MODELO)) ? $row->MODELO : '-' ?>
                                                                    </td>
                                                                    <td height="18" align="left"
                                                                        bgcolor="<? echo $myColor ?>"
                                                                        class="enlaceceldas">
                                                                        &nbsp;<? echo (!empty($row->MODELO)) ? $row->UNIDAD . $row->DESCRIPCION : '-' ?>
                                                                    </td>
                                                                    <td height="18" align="left"
                                                                        bgcolor="<? echo $myColor ?>"
                                                                        class="enlaceceldas">
                                                                        &nbsp;<? echo (!empty($row->MODELO)) ? $row->DIVISIBILIDAD : '-' ?>
                                                                    </td>
                                                                    <td height="18" align="left"
                                                                        bgcolor="<? echo $myColor ?>"
                                                                        class="enlaceceldas">
                                                                        &nbsp;<? echo (!empty($row->MODELO)) ? $row->REFERENCIA_AUTOMATICA : '-' ?>
                                                                    </td>
                                                                    <td height="18" align="left"
                                                                        bgcolor="<? echo $myColor ?>"
                                                                        class="enlaceceldas">
                                                                        &nbsp;<? echo (!empty($row->MODELO)) ? $row->OBSERVACIONES : '-' ?>
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
                                                        height="19px"><?= $auxiliar->traduce("No existen registros para la b�squeda realizada", $administrador->ID_IDIOMA) ?></td>
                                                </tr>
                                            <? endif; ?>
                                            <? if ($numRegistros > 0): ?>
                                                <tr bgcolor="#D9E3EC" class="lineaabajo" height="22">
                                                    <td width="27%" class="copyright">
                                                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?= $auxiliar->traduce("Ver", $administrador->ID_IDIOMA) ?>
                                                        &nbsp;#
                                                        <? $navegar->GenerarComboNumRegs($navegar->maxfilasMaestroMaterial); ?>
                                                        &nbsp;&nbsp;
                                                        <? $navegar->NumRegs($navegar->maxfilasMaestroMaterial, $maxahora, $navegar->numerofilasMaestroMaterial); ?>
                                                    </td>
                                                    <td width="73%" class="copyright">
                                                        <div align="right"><? $navegar->Numeros($navegar->sqlAdminMaestroMaterial, $navegar->maxfilasMaestroMaterial, $navegar->numerofilasMaestroMaterial, $i, "index.php", "#2E8AF0"); ?>
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