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
function acortar($cadena){
    $valores=explode(" ",$cadena);
    if(intval($valores[0][0])>=0&&intval($valores[0][1])>0){
        return $valores[0][0].$valores[0][1];
    }else{
        return $valores[0][0].$valores[1][0];
    }
}
//Funcion pintar arbol


function pintar_arbol($vector){
    echo "<ul>";
    for ($i=count($vector)-1;$i>=0;$i--){
        echo "<li>".$vector[$i];
        echo "<ul>";
        echo "</li>";
    }
    echo "</ul>";
}


//Funcion rellenar vector con sus respectivos padres

function obtenerPadresFamilia($id,$bd,&$vector){
    $sqlPadres = "SELECT ID_FAMILIA_MATERIAL_PADRE, NOMBRE_FAMILIA FROM FAMILIA_MATERIAL WHERE ID_FAMILIA_MATERIAL= ".$id;
    $resPadres = $bd->ExecSQL($sqlPadres);
    $reg=$bd->SigReg($resPadres);
        if($reg){
        $vector[]=$reg->NOMBRE_FAMILIA;
        if($reg->ID_FAMILIA_MATERIAL_PADRE!=NULL){
            obtenerPadresFamilia($reg->ID_FAMILIA_MATERIAL_PADRE,$bd,$vector);
        }
    }
}

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
$nuevo=true;
if ($idMaterial != ""):

    // OBTENGO REGISTRO
    $nuevo=false;
    $sqlTipo = "SELECT * FROM MATERIALES M 
    JOIN FAMILIA_MATERIAL ON M.FK_FAMILIA_MATERIAL=FAMILIA_MATERIAL.ID_FAMILIA_MATERIAL
    JOIN FAMILIA_REPRO ON M.FK_FAMILIA_REPRO=FAMILIA_REPRO.ID_FAMILIA_REPRO
    JOIN  UNIDAD U ON U.ID_UNIDAD=M.FK_UNIDAD_COMPRA
    JOIN ADMINISTRADOR A ON M.FK_USUARIO_CREACION=A.ID_ADMINISTRADOR 
    JOIN ADMINISTRADOR AA ON M.FK_USUARIO_ULTIMA_MODIFICACION=AA.ID_ADMINISTRADOR WHERE REFERENCIA_SCS= '" . $bd->escapeCondicional($idMaterial) . "'";
    var_dump($sqlTipo);
    $resTipo = $bd->ExecSQL($sqlTipo);
    $rowTipo = $bd->SigReg($resTipo);

    $txUnidadManipulacion=$rowTipo->UNIDAD ." ".$rowTipo->DESCRIPCION;

    $txMaterial=$rowTipo->REFERENCIA_SCS;
    $txDesc_esp=$rowTipo->DESCRIPCION_ESP;
    $txDesc_eng=$rowTipo->DESCRIPCION_ENG;
    $txFecha_creacion=$rowTipo->FECHA_CREACION;
    $idUsuario_creacion=$rowTipo->ID_ADMINISTRADOR;
    $txUsuario_creacion=$rowTipo->NOMBRE;
    $txFecha_ultima=$rowTipo->FECHA_ULTIMA_MODIFICACION;
    $txUsuario_ultimo=$rowTipo->NOMBRE;
    $txMarca=$rowTipo->MARCA;
    $txModelo=$rowTipo->MODELO;
    $txEstatus=$rowTipo->ESTATUS_MATERIAL;
    $txTipo=$rowTipo->TIPO_MATERIAL;
    $txObservaciones=$rowTipo->OBSERVACIONES;
    $txDivisibilidad=$rowTipo->DIVISIBILIDAD;
    $txDenominador=$rowTipo->DENOMINADOR;
    $txNumerador=$rowTipo->NUMERADOR;

    $idUnidadCompra=$rowTipo->FK_UNIDAD_COMPRA;
    $txUnidadCompra_ESP=$rowTipo->UNIDAD_ESP.' - '.$rowTipo->UNIDAD;
    $txUnidadCompra_ENG=$rowTipo->UNIDAD_ENG.' - '.$rowTipo->UNIDAD;

    $idUnidadMedida=$rowTipo->FK_UNIDAD_MEDIDA;
    $txUnidadMedida_ESP=$rowTipo->UNIDAD_ESP.' - '.$rowTipo->UNIDAD;
    $txUnidadMedida_ENG=$rowTipo->UNIDAD_ENG.' - '.$rowTipo->UNIDAD;

    $idFamiliaRepro=$rowTipo->FK_FAMILIA_REPRO;
    $txFamiliaRepro=$rowTipo->REFERENCIA . "- ".$rowTipo->FAMILIA_REPRO;
    $vector=array();
    var_dump($idMaterial);
    obtenerPadresFamilia($rowTipo->ID_FAMILIA_MATERIAL,$bd,$vector);

    $idFamiliaMaterial=$rowTipo->FK_FAMILIA_MATERIAL;
    $txFamiliaMaterial=$rowTipo->NOMBRE_FAMILIA;
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
            if (document.FormSelect.idMaterial.value != '') {
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
    <input type="hidden" name="idMaterial" value="<? echo $idMaterial?>">
    <input type="hidden" name="idFamiliaMaterial" value="<? echo $idFamiliaMaterial ?>">
    <input type="hidden" name="idFamiliaRepro" value="<? echo $idFamiliaRepro ?>">
    <input type="hidden" name="idUnidadMedida" value="<? echo $idUnidadMedida ?>">
    <input type="hidden" name="idUnidadCompra" value="<? echo $idUnidadCompra ?>">
    <input type="hidden" name="idUsuario_creacion" value="<? echo $idUsuario_creacion ?>">
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
                                            <table width="97%" border="0" align="center" cellpadding="0"
                                                   cellspacing="0">
                                                <caption></caption>
                                            <tr>
                                                <td width="20" align="center" valign="middle" class="lineaderecha">
                                                </td>
                                                <td align="center" valign="middle">
                                                    <table width="97%" border="0" align="center" cellpadding="0"
                                                           cellspacing="0">
                                                        <tr bgcolor="#D9E3EC">
                                                            <td height="10" colspan="3" bgcolor="d9e3ec"
                                                                class="linearribadereizq"><img
                                                                    src="<? echo $pathRaiz ?>imagenes/transparente.gif"
                                                                    width="10" height="10"></td>
                                                        </tr>
                                                        <tr bgcolor="#D9E3EC" class="lineabajodereizq">
                                                            <td width="5" bgcolor="d9e3ec" class="lineaizquierda">
                                                                &nbsp;
                                                            </td>
                                                            <td width="640" align="left" bgcolor="d9e3ec"> DATOS ESENCIALES
                                                                <table width="750" border="0" cellspacing="0"
                                                                       cellpadding="1" class="tablaFiltros">

                                                                    <tr>
                                                                        <td align="center" width="5%"><img
                                                                                src="<? echo $pathRaiz ?>imagenes/diamante.gif"
                                                                                width="7" height="7"></td>
                                                                        <td align="left" class="textoazul"
                                                                            width="35%"><?= $auxiliar->traduce("Nº Material", $administrador->ID_IDIOMA) . ":" ?>
                                                                        </td>
                                                                        <td class="textoazul" width="60%">
                                                                            <?
                                                                            $TamanoText = "420px";
                                                                            $ClassText  = "copyright ObligatorioRellenar";
                                                                            $MaxLength  = "80";
                                                                            $html->TextBox("txMaterial", $txMaterial);
                                                                            ?>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td align="center" width="5%"><img
                                                                                src="<? echo $pathRaiz ?>imagenes/diamante.gif"
                                                                                width="7" height="7"></td>
                                                                        <td align="left" class="textoazul"
                                                                            width="35%"><?= $auxiliar->traduce("Descripción material", $administrador->ID_IDIOMA) . ":" ?>
                                                                        </td>
                                                                        <td class="textoazul" width="60%">
                                                                            <?
                                                                            $TamanoText = "420px";
                                                                            $ClassText  = "copyright ObligatorioRellenar";
                                                                            $MaxLength  = "255";
                                                                            $html->TextBox("txDesc_esp", $txDesc_esp);
                                                                            ?>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td align="center" width="5%"><img
                                                                                    src="<? echo $pathRaiz ?>imagenes/diamante.gif"
                                                                                    width="7" height="7"></td>
                                                                        <td align="left" class="textoazul"
                                                                            width="35%"><?= $auxiliar->traduce("Descripción material ingles", $administrador->ID_IDIOMA) . ":" ?>
                                                                        </td>
                                                                        <td class="textoazul" width="60%">
                                                                            <?
                                                                            $TamanoText = "420px";
                                                                            $ClassText  = "copyright ObligatorioRellenar";
                                                                            $MaxLength  = "255";
                                                                            $html->TextBox("txDesc_eng", $txDesc_eng);
                                                                            ?>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td align="center" width="5%"><img
                                                                                    src="<? echo $pathRaiz ?>imagenes/diamante.gif"
                                                                                    width="7" height="7"></td>
                                                                        <td align="left" class="textoazul"
                                                                            width="35%"><?= $auxiliar->traduce("Estatus material", $administrador->ID_IDIOMA) . ":" ?>
                                                                        </td>
                                                                        <td class="textoazul" width="60%">
                                                                            <?
                                                                            $TamanoText = "20px";
                                                                            $ClassText  = "copyright";
                                                                            $MaxLength  = "255";
                                                                            $readonly='readonly';
                                                                            $html->TextBox(acortar($txEstatus), acortar($txEstatus));
                                                                            unset($readonly);
                                                                            $NombreSelect = 'selEstatus';
                                                                            $Elementos_estatus[0]['text'] = $auxiliar->traduce("01-Bloqueo General", $administrador->ID_IDIOMA);
                                                                            $Elementos_estatus[0]['valor'] = '01-Bloqueo General';
                                                                            $Elementos_estatus[1]['text'] = $auxiliar->traduce("02-Obsoleto Fin Existencias (Error)", $administrador->ID_IDIOMA);
                                                                            $Elementos_estatus[1]['valor'] = '02-Obsoleto Fin Existencias (Error)';
                                                                            $Elementos_estatus[2]['text'] = $auxiliar->traduce("03-Código duplicado", $administrador->ID_IDIOMA);
                                                                            $Elementos_estatus[2]['valor'] = '03-Código duplicado';
                                                                            $Elementos_estatus[3]['text'] = $auxiliar->traduce("04-Código inutilizable", $administrador->ID_IDIOMA);
                                                                            $Elementos_estatus[3]['valor'] = '04-Código inutilizable';
                                                                            $Elementos_estatus[4]['text'] = $auxiliar->traduce("05-Obsoleto Fin Existencias (Aviso)", $administrador->ID_IDIOMA);
                                                                            $Elementos_estatus[4]['valor'] = '05-Obsoleto Fin Existencias (Aviso)';
                                                                            $Elementos_estatus[5]['text'] = $auxiliar->traduce("06-Código Solo Fines Logísticos", $administrador->ID_IDIOMA);
                                                                            $Elementos_estatus[5]['valor'] = '06-Código Solo Fines Logístico';
                                                                            $Elementos_estatus[6]['text'] = $auxiliar->traduce("07-Solo para Refer.Prov", $administrador->ID_IDIOMA);
                                                                            $Elementos_estatus[6]['valor'] = '07-Solo para Refer.Prov';
                                                                            $Elementos_estatus[7]['text'] = $auxiliar->traduce("No bloqueado", $administrador->ID_IDIOMA);
                                                                            $Elementos_estatus[7]['valor'] = 'No bloqueado';
                                                                            $Tamano = "205px";
                                                                            $Estilo = "copyright";
                                                                            $html->SelectArr($NombreSelect, $Elementos_estatus,$txEstatus);
                                                                            ?>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td align="center" width="5%"><img
                                                                                    src="<? echo $pathRaiz ?>imagenes/diamante.gif"
                                                                                    width="7" height="7"></td>
                                                                        <td align="left" class="textoazul"
                                                                            width="35%"><?= $auxiliar->traduce("Tipo material", $administrador->ID_IDIOMA) . ":" ?>
                                                                        </td>
                                                                        <td class="textoazul" width="60%">
                                                                            <?
                                                                            $NombreSelect = 'selTipo';
                                                                            $Elementos_tipo[0]['text'] = $auxiliar->traduce("Pequeño Repuesto", $administrador->ID_IDIOMA);
                                                                            $Elementos_tipo[0]['valor'] = 'Pequeño Repuesto';
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
                                                                            $Elementos_tipo[8]['text'] = $auxiliar->traduce("Pruebas logísticas", $administrador->ID_IDIOMA);
                                                                            $Elementos_tipo[8]['valor'] = 'Pruebas logísticas';
                                                                            $Elementos_tipo[9]['text'] = $auxiliar->traduce("Código I&C", $administrador->ID_IDIOMA);
                                                                            $Elementos_tipo[9]['valor'] = 'Código I&C';
                                                                            $Tamano = "205px";
                                                                            $Estilo = "copyright";
                                                                            $html->SelectArr($NombreSelect, $Elementos_tipo, $txTipo);
                                                                            ?>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <? if(!$nuevo): ?>
                                                                        <td align="center" width="5%"><img
                                                                                    src="<? echo $pathRaiz ?>imagenes/diamante.gif"
                                                                                    width="7" height="7"></td>
                                                                        <td align="left" class="textoazul"
                                                                            width="35%"><?= $auxiliar->traduce("Fecha Creación", $administrador->ID_IDIOMA) . ":" ?>
                                                                        </td>
                                                                        <td class="textoazul" width="60%">
                                                                            <label><?echo $txFecha_creacion?></label>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td align="center" width="5%"><img
                                                                                    src="<? echo $pathRaiz ?>imagenes/diamante.gif"
                                                                                    width="7" height="7"></td>
                                                                        <td align="left" class="textoazul"
                                                                            width="35%"><?= $auxiliar->traduce("Usuario Creación", $administrador->ID_IDIOMA) . ":" ?>
                                                                        </td>
                                                                        <td class="textoazul" width="60%">
                                                                        <label><?echo $txUsuario_creacion?></label>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td align="center" width="5%"><img
                                                                                    src="<? echo $pathRaiz ?>imagenes/diamante.gif"
                                                                                    width="7" height="7"></td>
                                                                        <td align="left" class="textoazul"
                                                                            width="35%"><?= $auxiliar->traduce("Fecha Última Modificación", $administrador->ID_IDIOMA) . ":" ?>
                                                                        </td>
                                                                        <td class="textoazul" width="60%">
                                                                           <label><?echo $txFecha_ultima?></label>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td align="center" width="5%"><img
                                                                                    src="<? echo $pathRaiz ?>imagenes/diamante.gif"
                                                                                    width="7" height="7"></td>
                                                                        <td align="left" class="textoazul"
                                                                            width="35%"><?= $auxiliar->traduce("Usuario Última Modificación", $administrador->ID_IDIOMA) . ":" ?>
                                                                        </td>
                                                                        <td class="textoazul" width="60%">
                                                                            <label><?echo $txUsuario_ultimo?></label>
                                                                        </td>
                                                                        <?endif;?>
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
                                                            <td width="640" align="left" bgcolor="d9e3ec">
                                                                <table width="750" border="0" cellspacing="0"
                                                                       cellpadding="1" class="tablaFiltros">

                                                                    <tr> TAXONOMIA DE MATERIALES

                                                                        <td align="center" width="5%"><img
                                                                                    src="<? echo $pathRaiz ?>imagenes/diamante.gif"
                                                                                    width="7" height="7"></td>


                                                                        <? if(!$nuevo):
                                                                        pintar_arbol($vector);
                                                                        endif;?>
                                                                    </tr>
                                                                    <td align="center" width="5%"><img
                                                                                src="<? echo $pathRaiz ?>imagenes/diamante.gif"
                                                                                width="7" height="7"></td>
                                                                    <td align="left" class="textoazul"
                                                                        width="35%"><?= $auxiliar->traduce("Familia Material", $administrador->ID_IDIOMA) . ":" ?>
                                                                    </td>
                                                                    <td class="textoazul" width="60%">
                                                                        <?
                                                                        $TamanoText = "420px";
                                                                        $ClassText  = "copyright ObligatorioRellenar";
                                                                        $MaxLength  = "80";
                                                                        $html->TextBox("txFamiliaMaterial", $txFamiliaMaterial);
                                                                        ?>
                                                                    </td>
                                                                    <tr>
                                                                        <td align="center" width="5%"><img
                                                                                    src="<? echo $pathRaiz ?>imagenes/diamante.gif"
                                                                                    width="7" height="7"></td>
                                                                        <td align="left" class="textoazul"
                                                                            width="35%"><?= $auxiliar->traduce("Familia Repro", $administrador->ID_IDIOMA) . ":" ?>
                                                                        </td>
                                                                        <td class="textoazul" width="60%">
                                                                            <?
                                                                            $TamanoText = "420px";
                                                                            $ClassText  = "copyright ObligatorioRellenar";
                                                                            $MaxLength  = "255";
                                                                            $html->TextBox("txFamiliaRepro", $txFamiliaRepro);
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
                                            <td width="640" align="left" bgcolor="d9e3ec">FICHA MATERIAL
                                                <table width="750" border="0" cellspacing="0"
                                                       cellpadding="1" class="tablaFiltros">
                                                    <tr>
                                                        <td align="center" width="5%"></td>
                                                        <td align="left" class="textoazul"
                                                            width="35%"><?= $auxiliar->traduce("Marca", $administrador->ID_IDIOMA) . ":" ?>
                                                        </td>
                                                        <td class="textoazul" width="60%">
                                                            <?
                                                            $TamanoText = "420px";
                                                            $ClassText  = "copyright";
                                                            $MaxLength  = "80";
                                                            $html->TextBox("txMarca", $txMarca);
                                                            ?>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td align="center" width="5%"></td>
                                                        <td align="left" class="textoazul"
                                                            width="35%"><?= $auxiliar->traduce("Modelo", $administrador->ID_IDIOMA) . ":" ?>
                                                        </td>
                                                        <td class="textoazul" width="60%">
                                                            <?
                                                            $TamanoText = "420px";
                                                            $ClassText  = "copyright";
                                                            $MaxLength  = "255";
                                                            $html->TextBox("txModelo", $txModelo);
                                                            ?>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td align="center" width="5%"><img
                                                                    src="<? echo $pathRaiz ?>imagenes/diamante.gif"
                                                                    width="7" height="7"></td>
                                                        <td align="left" class="textoazul"
                                                            width="35%"><?= $auxiliar->traduce("Tecnología", $administrador->ID_IDIOMA) . ":" ?>
                                                        </td>
                                                        <td class="textoazul" width="60%">
                                                            <?
                                                            $TamanoText = "420px";
                                                            $ClassText  = "copyright";
                                                            $MaxLength  = "255";
                                                            $html->TextBox("txIncidenciaSistemaTipoEng", $txIncidenciaSistemaTipoEng);
                                                            ?>
                                                        </td>
                                                    <tr>
                                                        <td align="center" width="5%"><img
                                                                    src="<? echo $pathRaiz ?>imagenes/diamante.gif"
                                                                    width="7" height="7"></td>
                                                        <td align="left" class="textoazul"
                                                            width="35%"><?= $auxiliar->traduce("Tecnólogo Eólica", $administrador->ID_IDIOMA) . ":" ?>
                                                        </td>
                                                        <td class="textoazul" width="60%">
                                                            <?
                                                            $TamanoText = "420px";
                                                            $ClassText  = "copyright";
                                                            $MaxLength  = "255";
                                                            $html->TextBox("txIncidenciaSistemaTipoEng", $txIncidenciaSistemaTipoEng);
                                                            ?>
                                                        </td>
                                                    </tr>
                                                    </tr>

                                                    <tr>
                                                        <td align="center" width="5%"><img
                                                                    src="<? echo $pathRaiz ?>imagenes/diamante.gif"
                                                                    width="7" height="7"></td>
                                                        <td align="left" class="textoazul"
                                                            width="35%"><?= $auxiliar->traduce("Tipo Eólica", $administrador->ID_IDIOMA) . ":" ?>
                                                        </td>
                                                        <td class="textoazul" width="60%">
                                                            <?
                                                            $TamanoText = "420px";
                                                            $ClassText  = "copyright";
                                                            $MaxLength  = "255";
                                                            $html->TextBox("txIncidenciaSistemaTipoEng", $txIncidenciaSistemaTipoEng);
                                                            ?>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td align="center" width="5%"></td>
                                                        <td align="left" class="textoazul"
                                                            width="35%"><?= $auxiliar->traduce("Subcojunto Eólica", $administrador->ID_IDIOMA) . ":" ?>
                                                        </td>
                                                        <td class="textoazul" width="60%">
                                                            <?
                                                            $TamanoText = "420px";
                                                            $ClassText  = "copyright";
                                                            $MaxLength  = "255";
                                                            $html->TextBox("txIncidenciaSistemaTipoEng", $txIncidenciaSistemaTipoEng);
                                                            ?>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                            <td width="640" align="left" bgcolor="d9e3ec">
                                                <table width="750" border="0" cellspacing="0"
                                                       cellpadding="1" class="tablaFiltros">
                                                    <tr>
                                                        <td align="center" width="5%"><img
                                                                    src="<? echo $pathRaiz ?>imagenes/diamante.gif"
                                                                    width="7" height="7"></td>
                                                        <td align="left" class="textoazul"
                                                            width="35%"><?= $auxiliar->traduce("Unidad de medida", $administrador->ID_IDIOMA) . ":" ?>
                                                        </td>
                                                        <td class="textoazul" width="60%">
                                                            <?
                                                            $TamanoText = "420px";
                                                            $ClassText  = "copyright ObligatorioRellenar";
                                                            $MaxLength  = "80";
                                                            if($administrador->ID_IDIOMA=='ESP'){
                                                                $html->TextBox("txUnidadMedida_ESP", $txUnidadMedida_ESP);
                                                            }
                                                            elseif($administrador->ID_IDIOMA=='ENG'){
                                                                $html->TextBox("txUnidadMedida_ENG", $txUnidadMedida_ENG);
                                                            }
                                                            ?>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td align="center" width="5%"><img
                                                                    src="<? echo $pathRaiz ?>imagenes/diamante.gif"
                                                                    width="7" height="7"></td>
                                                        <td align="left" class="textoazul"
                                                            width="35%"><?= $auxiliar->traduce("Unidad de compra", $administrador->ID_IDIOMA) . ":" ?>
                                                        </td>
                                                        <td class="textoazul" width="60%">
                                                            <?
                                                            $TamanoText = "420px";
                                                            $ClassText  = "copyright ObligatorioRellenar";
                                                            $MaxLength  = "255";
                                                            if($administrador->ID_IDIOMA=='ESP'){
                                                                $html->TextBox("txUnidadCompra_ESP", $txUnidadCompra_ESP);
                                                            }
                                                            elseif($administrador->ID_IDIOMA=='ENG'){
                                                                $html->TextBox("txUnidadCompra_ENG", $txUnidadCompra_ENG);
                                                            }
                                                            ?>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td align="center" width="5%"><img
                                                                    src="<? echo $pathRaiz ?>imagenes/diamante.gif"
                                                                    width="7" height="7"></td>
                                                        <td align="left" class="textoazul"
                                                            width="35%"><?= $auxiliar->traduce("Numerador conversión", $administrador->ID_IDIOMA) . ":" ?>
                                                        </td>
                                                        <td class="textoazul" width="60%">
                                                            <?
                                                            $TamanoText = "420px";
                                                            $ClassText  = "copyright ObligatorioRellenar";
                                                            $MaxLength  = "255";
                                                            $html->TextBox("txNumerador", $txNumerador);
                                                            ?>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td align="center" width="5%"><img
                                                                    src="<? echo $pathRaiz ?>imagenes/diamante.gif"
                                                                    width="7" height="7"></td>
                                                        <td align="left" class="textoazul"
                                                            width="35%"><?= $auxiliar->traduce("Denominador conversión", $administrador->ID_IDIOMA) . ":" ?>
                                                        </td>
                                                        <td class="textoazul" width="60%">
                                                            <?
                                                            $TamanoText = "420px";
                                                            $ClassText  = "copyright ObligatorioRellenar";
                                                            $MaxLength  = "255";
                                                            $html->TextBox("txDenominador", $txDenominador);
                                                            ?>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td align="center" width="5%"></td>
                                                        <td align="left" class="textoazul"
                                                            width="35%"><?= $auxiliar->traduce("Unidades de manipulacion", $administrador->ID_IDIOMA) . ":" ?>
                                                        </td>
                                                        <td class="textoazul" width="60%">
                                                            <?
                                                            $TamanoText = "100px";
                                                            $ClassText  = "copyright";

                                                            $html->TextBox("txUnidadManipulacion", $txUnidadManipulacion);
                                                            ?>
                                                            <?= $auxiliar->traduce("Divisibilidad", $administrador->ID_IDIOMA) . ":" ?>

                                                                <?
                                                            $NombreSelect = 'selDivisibilidad';
                                                            $Elementos_divisibilidad[0]['text'] = $auxiliar->traduce("Si", $administrador->ID_IDIOMA);
                                                            $Elementos_divisibilidad[0]['valor'] = 'Si';
                                                            $Elementos_divisibilidad[1]['text'] = $auxiliar->traduce("No", $administrador->ID_IDIOMA);
                                                            $Elementos_divisibilidad[1]['valor'] = 'No';
                                                            $Elementos_divisibilidad[2]['text'] = $auxiliar->traduce("Pendiente decisión", $administrador->ID_IDIOMA);
                                                            $Elementos_divisibilidad[2]['valor'] = 'Pendiente decisión';
                                                            $Elementos_divisibilidad[3]['text'] = $auxiliar->traduce("No Aplica", $administrador->ID_IDIOMA);
                                                            $Elementos_divisibilidad[3]['valor'] = 'No Aplica';
                                                            $Tamano = "205px";
                                                            $Estilo = "copyright";

                                                            $html->SelectArr($NombreSelect, $Elementos_divisibilidad, $txDivisibilidad);
                                                                ?>
                                                        </td>

                                                    </tr>
                                                    <tr>
                                                        <td align="center" width="5%"></td>
                                                        <td align="left" class="textoazul"
                                                            width="35%"><?= $auxiliar->traduce("Observaciones Material", $administrador->ID_IDIOMA) . ":" ?>
                                                        </td>
                                                        <td class="textoazul" width="60%">
                                                            <?
                                                            $TamanoText = "420px";
                                                            $ClassText  = "copyright";
                                                            $MaxLength  = "255";
                                                            $html->TextArea("txObservaciones", $txObservaciones);
                                                            ?>
                                                        </td>
                                                    </tr>

                                                </table>
                                            </td>
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
