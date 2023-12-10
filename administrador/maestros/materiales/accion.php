<?
// PATHS DE LA WEB
$pathRaiz   = "../../";
$pathClases = "../../../";

// INCLUDES DE LIBRERIAS PROPIAS
require_once $pathClases . "lib/basedatos.php";
require_once $pathClases . "lib/administrador.php";
require_once $pathClases . "lib/comprobar.php";
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
if ($administrador->Hayar_Permiso_Perfil('ADM_MAESTROS_INCIDENCIA_SISTEMA_TIPO') < 2):
    $html->PagError("SinPermisos");
endif;

// RECUERDO DE BUSQUEDAS REALIZADAS
include $pathRaiz . "busqueda_recordar.php";

//COMPRUEBO DATOS OBLIGATORIOS
//COMPROBAR LA NO EXISTENCIA DE CAMPOS OBLIGATORIOS VACÍOS
unset($arr_tx);
$i                   = 0;
$arr_tx[$i]["err"]   = $auxiliar->traduce("Nº Material", $administrador->ID_IDIOMA);
$arr_tx[$i]["valor"] = $txMaterial;
$i++;
$arr_tx[$i]["err"]   = $auxiliar->traduce("Descripcion Material", $administrador->ID_IDIOMA);
$arr_tx[$i]["valor"] = $txDesc_esp;
$i++;
$arr_tx[$i]["err"]   = $auxiliar->traduce("Descripcion material ingles", $administrador->ID_IDIOMA);
$arr_tx[$i]["valor"] = $txDesc_eng;
$i++;
$arr_tx[$i]["err"]   = $auxiliar->traduce("Familia Material", $administrador->ID_IDIOMA);
$arr_tx[$i]["valor"] = $txFamiliaMaterial;
$i++;
$arr_tx[$i]["err"]   = $auxiliar->traduce("Familia Repro", $administrador->ID_IDIOMA);
$arr_tx[$i]["valor"] = $txFamiliaRepro;
$i++;
$arr_tx[$i]["err"]   = $auxiliar->traduce("Numerador conversion", $administrador->ID_IDIOMA);
$arr_tx[$i]["valor"] = $txNumerador;
$i++;
$arr_tx[$i]["err"]   = $auxiliar->traduce("Denominador conversion", $administrador->ID_IDIOMA);
$arr_tx[$i]["valor"] = $txDenominador;
$i++;
$comp->ComprobarTexto($arr_tx, "CampoSinRellenar");

unset($arr_tx);
if($accion == "Modificar_AGM"):
    $sql       = "UPDATE MATERIAL_COMPONENTE_AGM  SET
                CANTIDAD='" . trim( (string)$bd->escapeCondicional($cantidad_agm)) . "'
                WHERE MATERIAL_AGM='" . $bd->escapeCondicional($txMaterial) . "' AND MATERIAL_COMPONENTE='" . $bd->escapeCondicional($id_componente_agm_grabar) . "'";
    $TipoError = "ErrorEjecutarSql";
    $bd->ExecSQL($sql);

elseif($accion == "Borrar_AGM"):
    $sql       = "UPDATE MATERIAL_COMPONENTE_AGM  SET
                CANTIDAD='" . trim( (string)$bd->escapeCondicional($cantidad_agm)) . "'
                ,BAJA=TRUE
                WHERE MATERIAL_AGM='" . $bd->escapeCondicional($txMaterial) . "' AND MATERIAL_COMPONENTE='" . $bd->escapeCondicional($id_componente_agm_grabar) . "'";

$TipoError = "ErrorEjecutarSql";
    $bd->ExecSQL($sql);

elseif ($accion == "Modificar"):

    //$rowTipo = $bd->VerReg("INCIDENCIA_SISTEMA_TIPO", "ID_INCIDENCIA_SISTEMA_TIPO", trim( (string)$bd->escapeCondicional($idIncidenciaSistemaTipo)));

    // COMPRUEBO NO CREADO OTRO CON IGUAL CAMPO
    //$sql          = "SELECT COUNT(ID_INCIDENCIA_SISTEMA_TIPO) as NUM_REGS FROM INCIDENCIA_SISTEMA_TIPO WHERE INCIDENCIA_SISTEMA_TIPO='" . trim( (string)$bd->escapeCondicional($txIncidenciaSistemaTipo)) . "' AND INCIDENCIA_SISTEMA_TIPO_ENG='" . trim( (string)$bd->escapeCondicional($txIncidenciaSistemaTipoEng)) . "' AND ID_INCIDENCIA_SISTEMA_TIPO<>'" . $bd->escapeCondicional($idIncidenciaSistemaTipo) . "'";
    //$resultNumero = $bd->ExecSQL($sql);
    //$rowNumero    = $bd->SigReg($resultNumero);
    //if ($rowNumero->NUM_REGS > 0) $html->PagErrorCond("Error", "Error", "CampoExistente", "error.php");

    // MODIFICO EL REGISTRO DE LA BD
    $sql       = "UPDATE MATERIAL SET
                ,REFERENCIA_SCS='" . trim( (string)$bd->escapeCondicional($txMaterial)) . "'
                ,DESCRIPCION_ESP='" . trim( (string)$bd->escapeCondicional($txDesc_esp)) . "'
                ,DESCRIPCION_ENG='" . trim( (string)$bd->escapeCondicional($txDesc_eng)) . "'
                ,ESTATUS_MATERIAL='" . trim( (string)$bd->escapeCondicional($selEstatus)) . "'
                ,TIPO_MATERIAL='" . trim( (string)$bd->escapeCondicional($selTipo)) . "'
                ,MARCA='" . trim( (string)$bd->escapeCondicional($txMarca)) . "'
                ,MODELO='" . trim( (string)$bd->escapeCondicional($txModelo)) . "'
                ,ID_FAMILIA_MATERIAL='" . trim( (string)$bd->escapeCondicional($idFamiliaMaterial)) . "'
                ,ID_FAMILIA_REPRO='" . trim( (string)$bd->escapeCondicional($idFamiliaRepro)) . "'
                ,MODELO='" . trim( (string)$bd->escapeCondicional($txModelo)) . "'
                ,ID_UNIDAD_MEDIDA='" . trim( (string)$bd->escapeCondicional($idUnidadMedida)) . "'
                ,ID_UNIDAD_COMPRA='" . trim( (string)$bd->escapeCondicional($idUnidadCompra)) . "'
                ,NUMERADOR='" . trim( (string)$bd->escapeCondicional($txNumerador)) . "'
                ,DENOMINADOR='" . trim( (string)$bd->escapeCondicional($txDenominador)) . "'
                ,BAJA='" . $chBaja . "'
                WHERE ID_MATERIAL='" . $bd->escapeCondicional($txMaterial) . "'";
    $TipoError = "ErrorEjecutarSql";
    $bd->ExecSQL($sql);

    // GUARDO LOS DATOS ACTUALIZADOS
    $rowTipoActualizado = $bd->VerReg("INCIDENCIA_SISTEMA_TIPO", "ID_INCIDENCIA_SISTEMA_TIPO", $idIncidenciaSistemaTipo, "No");

    // LOG MOVIMIENTOS
    $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Maestro", $idIncidenciaSistemaTipo, "Incidencia Sistema Tipo", "INCIDENCIA_SISTEMA_TIPO", $rowTipo, $rowTipoActualizado);


elseif ($accion == "Insertar"):

    // COMPRUEBO NO CREADO OTRO CON IGUAL TIPO
    //$sql          = "SELECT COUNT(ID_INCIDENCIA_SISTEMA_TIPO) as NUM_REGS FROM INCIDENCIA_SISTEMA_TIPO WHERE INCIDENCIA_SISTEMA_TIPO='" . trim( (string)$bd->escapeCondicional($txIncidenciaSistemaTipo)) . "' AND INCIDENCIA_SISTEMA_TIPO_ENG='" . trim( (string)$bd->escapeCondicional($txIncidenciaSistemaTipoEng)) . "'";
    //$resultNumero = $bd->ExecSQL($sql);
    //$rowNumero    = $bd->SigReg($resultNumero);
    //if ($rowNumero->NUM_REGS > 0) $html->PagErrorCond("Error", "Error", "CampoExistente", "error.php");

if($chBaja!=1){
$chBaja=0;
}   // INSERTO EL REGISTRO EN LA BD
    $sql       = "INSERT INTO MATERIAL SET
                ,REFERENCIA_SCS='" . trim( (string)$bd->escapeCondicional($txMaterial)) . "'
                ,DESCRIPCION_ESP='" . trim( (string)$bd->escapeCondicional($txDesc_esp)) . "'
                ,DESCRIPCION_ENG='" . trim( (string)$bd->escapeCondicional($txDesc_eng)) . "'
                ,ESTATUS_MATERIAL='" . trim( (string)$bd->escapeCondicional($selEstatus)) . "'
                ,TIPO_MATERIAL='" . trim( (string)$bd->escapeCondicional($selTipo)) . "'
                ,MARCA='" . trim( (string)$bd->escapeCondicional($txMarca)) . "'
                ,MODELO='" . trim( (string)$bd->escapeCondicional($txModelo)) . "'
                ,FECHA_CREACION='" . date('Y-m-d H:i:s'). "'
                ,ID_USUARIO_CREACION='" . $administrador->ID_ADMINISTRADOR ."'
                ,ID_USUARIO_ULTIMA_MODIFICACION='" . $administrador->ID_ADMINISTRADOR ."'
                ,FECHA_ULTIMA_MODIFICACION='" . date('Y-m-d H:i:s'). "'
                ,ID_FAMILIA_MATERIAL='" . trim( (string)$bd->escapeCondicional($txFamiliaMaterial)) . "'
                ,ID_FAMILIA_REPRO='" . trim( (string)$bd->escapeCondicional($txFamiliaRepro)) . "'
                ,ID_UNIDAD_MEDIDA='" . trim( (string)$bd->escapeCondicional($txUnidadMedida_ESP)) . "'
                ,ID_UNIDAD_COMPRA='" . trim( (string)$bd->escapeCondicional($txUnidadCompra_ESP)) . "'
                ,NUMERADOR='" . trim( (string)$bd->escapeCondicional($txNumerador)) . "'
                ,DENOMINADOR='" . trim( (string)$bd->escapeCondicional($txDenominador)) . "'
                ,BAJA='" . $chBaja . "'";
    $TipoError = "ErrorEjecutarSql";
    $bd->ExecSQL($sql);

    //OBTENGO ID CREADO
    $idCampo = $bd->IdAsignado();

    // LOG MOVIMIENTOS
    $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Creación", "Maestro", $idIncidenciaSistemaTipo, "Incidencia Sistema Tipo", "INCIDENCIA_SISTEMA_TIPO");

endif;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <? require_once $pathClases . "lib/gral_js.php"; ?>
    <script language="javascript" type="text/javascript">
        $(document).ready(function () {
            $('#botonContinuar').focus();
        })
    </script>
</head>
<body bgcolor="#FFFFFF" background="<? echo $pathRaiz ?>imagenes/fondo_pantalla.gif" leftmargin="0" topmargin="0"
      marginwidth="0" marginheight="0">
<FORM NAME="Form" METHOD="POST">
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
                                                            </td>
                                                            <td width="60"></td>
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
                                                                colspan=2><font
                                                                    class="tituloNav"><? echo $tituloNav ?></font>
                                                            </td>
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
                                    <td height="280" align="left" valign="top" bgcolor="#D9E3EC" class="lineabajo">
                                        <table width="100%" height="280" border="0" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td align="center" valign="bottom">
                                                    <table width="100%" height="220" border="0" cellpadding="0"
                                                           cellspacing="0">
                                                        <tr align="center" valign="middle">
                                                            <td height="20">
                                                                <table width="130" height="20" border="0"
                                                                       cellpadding="0" cellspacing="0">
                                                                    <tr>
                                                                        <td align="center" valign="middle"
                                                                            bgcolor="#B3C7DA"
                                                                            class="alertas2"><?= strtr( (string)strtoupper( (string)$auxiliar->traduce("Información", $administrador->ID_IDIOMA)), "àèìòùáéíóúçñäëïöü", "ÀÈÌÒÙÁÉÍÓÚÇÑÄËÏÖÜ") ?></td>
                                                                    </tr>
                                                                </table>
                                                            </td>
                                                        </tr>
                                                        <tr align="center" valign="middle">
                                                            <td bgcolor="#B3C7DA" class="textoazul">
                                                                <strong><? if ($accion == "Modificar"): echo $auxiliar->traduce("Los datos del tipo han sido modificados correctamente", $administrador->ID_IDIOMA); elseif ($accion == "Insertar"): echo $auxiliar->traduce("El tipo ha sido creado correctamente", $administrador->ID_IDIOMA); endif ?></strong>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td height="124" align="center" valign="middle"
                                                                bgcolor="#B3C7DA">
                                                                <table width="100%" height="124" border="0"
                                                                       cellpadding="0" cellspacing="0">
                                                                    <tr>
                                                                        <td align="right" valign="middle">
                                                                            <table width="100%" height="124" border="0"
                                                                                   cellpadding="0" cellspacing="0">
                                                                                <tr>
                                                                                    <td height="9"><img
                                                                                            src="<? echo $pathRaiz ?>imagenes/transparente.gif"
                                                                                            width="10" height="9">
                                                                                    </td>
                                                                                </tr>
                                                                                <tr>
                                                                                    <td height="115" bgcolor="#90BC45">
                                                                                        &nbsp;
                                                                                    </td>
                                                                                </tr>
                                                                            </table>
                                                                        </td>
                                                                        <td width="212" align="center" valign="middle"
                                                                            bgcolor="#90BC45">
                                                                            <table width="212" height="124" border="0"
                                                                                   cellpadding="0" cellspacing="0"
                                                                                   background="<? echo $pathRaiz ?>imagenes/fondo_ok2.gif">
                                                                                <tr>
                                                                                    <td>&nbsp;</td>
                                                                                </tr>
                                                                                <tr>
                                                                                    <td align="center" valign="middle">
                                                                                        <a id="botonContinuar"
                                                                                           href="index.php?recordar_busqueda=1"
                                                                                           class="senaladoazul">&nbsp;&nbsp;&nbsp;&nbsp;<?= $auxiliar->traduce("Continuar", $administrador->ID_IDIOMA) ?>
                                                                                            &nbsp;&nbsp;&nbsp;&nbsp;</a>
                                                                                    </td>
                                                                                </tr>
                                                                            </table>
                                                                        </td>
                                                                        <td align="left" valign="middle">
                                                                            <table width="100%" height="124" border="0"
                                                                                   cellpadding="0" cellspacing="0">
                                                                                <tr>
                                                                                    <td height="37"><img
                                                                                            src="<? echo $pathRaiz ?>imagenes/transparente.gif"
                                                                                            width="10" height="37">
                                                                                    </td>
                                                                                </tr>
                                                                                <tr>
                                                                                    <td height="87" bgcolor="#90BC45">
                                                                                        &nbsp;
                                                                                    </td>
                                                                                </tr>
                                                                            </table>
                                                                        </td>
                                                                    </tr>
                                                                </table>
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
                                        &nbsp;
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
