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

$tituloPag         = $auxiliar->traduce("PEP Construccion", $administrador->ID_IDIOMA);
$tituloNav         = $auxiliar->traduce("Maestros", $administrador->ID_IDIOMA) . " >> " . $tituloPag;
$ZonaTablaPadre    = "Maestros";
$ZonaSubTablaPadre = "MaestrosSubmenuTransporte";
$ZonaTabla         = "MaestrosPepConstruccion";

// COMPRUEBA SI TIENE PERMISOS
if ($administrador->Hayar_Permiso_Perfil('ADM_PEP_CONSTRUCCION') < 2):
    $html->PagError("SinPermisos");
endif;

//CAMPOS OBLIGATORIOS
$i                   = 0;
$arr_tx[$i]["err"]   = $auxiliar->traduce("Descripcion PEP", $administrador->ID_IDIOMA);
$arr_tx[$i]["valor"] = trim( (string)$txDescripcionPep);
$comp->ComprobarTexto($arr_tx, "CampoSinRellenar");

//VALIDAMOS LAS LONGITUDES DE LOS CAMPOS
$arr_long[$i]["err"]      = $auxiliar->traduce("Descripcion PEP", $administrador->ID_IDIOMA);
$arr_long[$i]["valor"]    = $txDescripcionPep;
$arr_long[$i]["longitud"] = 255;
$comp->ComprobarLongitud($arr_long, "LongitudCampoIncorrecta");

// RECUERDO DE BUSQUEDAS REALIZADAS
include $pathRaiz . "busqueda_recordar.php";

if (trim( (string)$accion) == "Insertar"): // MODIFICAR OBSERVACIONES
    $txDescripcionPep = $bd->escapeCondicional($txDescripcionPep);

    //INICIAMOS UNA TRANSACCION
    $bd->begin_transaction();

    // INSERTAMOS
    $sqlInsert = "INSERT INTO PEP_CONSTRUCCION SET
                  DESCRIPCION_PEP = '" . $bd->escapeCondicional($txDescripcionPep) . "'
                  , BAJA = " . ($chBaja == 1 ? 1 : 0);
    $bd->ExecSQL($sqlInsert);
    $idPepConstruccion = $bd->IdAsignado();

    $NotificaErrorPorEmail = "No";
    $rowPepConstruccion    = $bd->VerReg("PEP_CONSTRUCCION", "ID_PEP_CONSTRUCCION", $idPepConstruccion, "No");
    unset($NotificaErrorPorEmail);

    // LOG MOVIMIENTOS
    $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, 'Creacion', "Maestro", $idPepConstruccion, "Creacion de PEP Construccion", "PEP_CONSTRUCCION", $rowPepConstruccion, "");
    //FINALIZAMOS UNA TRANSACCION
    $bd->commit_transaction();

elseif (trim( (string)$accion) == "Modificar"): // MODIFICAR OBSERVACIONES
    //NOS ASEGURAMOS DE OBTENER LA OBSERVACION
    $NotificaErrorPorEmail     = "No";
    $rowPepConstruccionAntiguo = $bd->VerReg("PEP_CONSTRUCCION", "ID_PEP_CONSTRUCCION", $idPepConstruccion, "No");
    unset($NotificaErrorPorEmail);

    //COMPROBACIONES DIRECCION
    $html->PagErrorCondicionado($rowPepConstruccionAntiguo, "==", false, "PepConstruccionNoExiste");

    //INICIAMOS UNA TRANSACCION
    $bd->begin_transaction();

    $txDescripcionPep = $bd->escapeCondicional($txDescripcionPep);

    // ACTUALIZAMOS
    $sqlUpdate = "UPDATE PEP_CONSTRUCCION
						  SET DESCRIPCION_PEP = '$txDescripcionPep'
						  , BAJA = " . ($chBaja == 1 ? 1 : 0) . "
						  WHERE ID_PEP_CONSTRUCCION = $idPepConstruccion";
    $bd->ExecSQL($sqlUpdate);

    // LOG MOVIMIENTOS SI HA HABIDO CAMBIOS
    $NotificaErrorPorEmail         = "No";
    $rowPepConstruccionActualizado = $bd->VerReg("PEP_CONSTRUCCION", "ID_PEP_CONSTRUCCION", $idPepConstruccion, "No");
    unset($NotificaErrorPorEmail);

    if ($rowPepConstruccionAntiguo != false):
        $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, 'Modificacion', "Maestro", $idPepConstruccion, "Modificacion Pep Construccion", "PEP_CONSTRUCCION", $rowPepConstruccionAntiguo, $rowPepConstruccionActualizado);
    endif;

    //FINALIZAMOS UNA TRANSACCION
    $bd->commit_transaction();

endif;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <? require_once $pathClases . "lib/gral_js.php"; ?>
    <script language="javascript" type="text/javascript">
        jQuery(document).ready(function () {
            jQuery('#botonContinuar').focus();
        })
    </script>
</head>
<body bgcolor="#FFFFFF" background="<? echo $pathRaiz ?>imagenes/fondo_pantalla.gif" leftmargin="0" topmargin="0"
      marginwidth="0" marginheight="0">
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
                                                            &nbsp;</td>
                                                        <td width="220" bgcolor="#982a29" class="lineabajoarriba"
                                                            colspan=2><font
                                                                class="tituloNav"><? echo $tituloNav ?></font></td>
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
                                                            <? if ($accion == "Insertar"): ?>
                                                                <strong><?= $auxiliar->traduce("PEP Construccion creada Correctamente", $administrador->ID_IDIOMA); ?>
                                                                </strong>
                                                            <? elseif ($accion == "Modificar"): ?>
                                                                <strong><?= $auxiliar->traduce("PEP Construccion modificada Correctamente", $administrador->ID_IDIOMA); ?>
                                                                </strong>
                                                            <? endif; ?>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td height="104" align="center" valign="middle"
                                                            bgcolor="#B3C7DA">
                                                            <table width="100%" border="0"
                                                                   cellpadding="0" cellspacing="0">
                                                                <tr>
                                                                    <td align="right" valign="middle">
                                                                        <table width="100%" border="0"
                                                                               cellpadding="0" cellspacing="0">
                                                                            <tr>
                                                                                <td height="9"><img
                                                                                        src="<? echo $pathRaiz ?>imagenes/transparente.gif"
                                                                                        width="10" height="9"></td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td height="95" bgcolor="#90BC45">
                                                                                    &nbsp;</td>
                                                                            </tr>
                                                                        </table>
                                                                    </td>
                                                                    <td width="212" align="center" valign="middle"
                                                                        bgcolor="#90BC45">
                                                                        <table width="100%" height="103" border="0"
                                                                               cellpadding="0" cellspacing="0"
                                                                               background="<? echo $pathRaiz ?>imagenes/fondo_ok2.gif">
                                                                            <tr>
                                                                                <td>&nbsp;</td>
                                                                            </tr>
                                                                        </table>
                                                                    </td>
                                                                    <td align="left" valign="middle">
                                                                        <table width="100%" border="0"
                                                                               cellpadding="0" cellspacing="0">
                                                                            <tr>
                                                                                <td height="17"><img
                                                                                        src="<? echo $pathRaiz ?>imagenes/transparente.gif"
                                                                                        width="10" height="37"></td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td height="67" bgcolor="#90BC45">
                                                                                    &nbsp;</td>
                                                                            </tr>
                                                                        </table>
                                                                    </td>
                                                                </tr>
                                                            </table>
                                                            <table width="100%" height="30" border="0"
                                                                   cellpadding="0" cellspacing="0">
                                                                <tr bgcolor="#90BC45">
                                                                    <td align="center" valign="middle">
                                                                        <? if ($accion == "Insertar"): ?>
                                                                            <a href="<? echo $pathRaiz ?>maestros/pep_construccion/index.php"
                                                                               class="senaladoverde">&nbsp;&nbsp;&nbsp;&nbsp;<?= $auxiliar->traduce("Ir al listado de PEP Construccion", $administrador->ID_IDIOMA) ?>
                                                                                &nbsp;&nbsp;&nbsp;&nbsp;</a>
                                                                        <? elseif ($accion == "Modificar"): ?>
                                                                            <a href="<? echo $pathRaiz ?>maestros/pep_construccion/ficha.php?idPepConstruccion=<?= $idPepConstruccion ?>"
                                                                               class="senaladoverde">&nbsp;&nbsp;&nbsp;&nbsp;<?= $auxiliar->traduce("Ir al PEP Construccion", $administrador->ID_IDIOMA) ?>
                                                                                &nbsp;&nbsp;&nbsp;&nbsp;</a>
                                                                        <? endif; ?>
                                                                        <a id="irVolver"
                                                                           href="index.php?recordar_busqueda=1"
                                                                           class="senaladoazul">
                                                                            &nbsp;&nbsp;&nbsp;&nbsp;<?= $auxiliar->traduce("Volver", $administrador->ID_IDIOMA) ?>
                                                                            &nbsp;&nbsp;&nbsp;&nbsp;</a>
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
                                    &nbsp;</td>
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
</body>
</html>

