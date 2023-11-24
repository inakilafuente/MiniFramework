<?php
/**
 * ARCHIVO DE ACCIÓN GENERAL PARA:
 * - IMPORTACIÓN MASIVA (CSV)
 * - IMPORTACIÓN MASIVA (EXCEL)
 * - INSERCIÓN MASIVA (COPY-PASTE)
 *
 * Created by PhpStorm.
 * User: cristian.tellez
 * Date: 19/10/2018
 * Time: 14:58
 */

//echo '<pre>', var_dump($_POST), '</pre>';
//exit;
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
require_once $pathClases . "lib/pedido.php";
require_once $pathClases . "lib/material.php";

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

//DECLARO LA VARIABLE GROBAL DE MENSAJE DE ERRORES
global $strError;

//VARIABLE PARA SABER SI SE HAN PRODUCIDO ERRORES
$indiceKo     = 0;
$filasKo      = 0;
$filasOk      = 0;
$mensajeExito = "";
$textoError   = "";

//RECORRO LAS LINEAS
foreach ($_POST as $clave => $valor):
    if ((substr( (string) $clave, 0, 8) == 'chLinea_') && ($valor == 1)):
        //CALCULO EL NUMERO DE LINEA
        $linea = substr( (string) $clave, 8);

        //BUSCO EL REGISTRO ANTIGUO
        $NotificaErrorPorEmail = "No";
        $rowRegistro           = $bd->VerReg("PEP_CONSTRUCCION", "DESCRIPCION_PEP", ${"txDescripcionPep_" . $linea}, "No");
        unset($NotificaErrorPorEmail);

        //FECHA ACTUAL (PARA LOS CAMPOS FECHA_MODIFICACIÓN Y FECHA_CREACION
        $fechaAct = date("Y-m-d H:i:s");

        //CAMPOS
        $txDescripcionPep = ${"txDescripcionPep_" . $linea};
        $chBaja           = ${"chBaja_" . $linea};

        $errorLinea = false;

        //COMPROBAR QUE NO ESTE VACIO
        if ($txDescripcionPep == ""):
            $strKo .= $auxiliar->traduce("Línea", $administrador->ID_IDIOMA) . " $indice. ";
            $strKo .= $auxiliar->traduce("El campo", $administrador->ID_IDIOMA);
            $strKo .= " " . $proveedorTraduccion . " ";
            $strKo .= $auxiliar->traduce("esta vacio", $administrador->ID_IDIOMA) . ".\n";
            $filasKo++;
            $errorLinea = true;
        endif;

        //COMPROBAR QUE NO ESTE VACIO
        if ($chBaja == ""):
            $strKo .= $auxiliar->traduce("Línea", $administrador->ID_IDIOMA) . " $indice. ";
            $strKo .= $auxiliar->traduce("El campo", $administrador->ID_IDIOMA);
            $strKo .= " " . $chBaja . " ";
            $strKo .= $auxiliar->traduce("esta vacio", $administrador->ID_IDIOMA) . ".\n";
            $filasKo++;
            $errorLinea = true;
        endif;

        if ($errorLinea == false):
            //INICIO LA TRANSACCION
            $bd->begin_transaction();

            //COMPROBAMOS SI LA CLAVE YA EXISTE (MODIFICACIÓN) O SI NO EXISTE (INSERCIÓN)
            if ($rowRegistro != false):
                //GUARDO EL REGISTRO PARA GENERAR EL CSV
                $arrayRowPepConstruccion[$linea]['id']      = $rowRegistro->ID_PEP_CONSTRUCCION;
                $arrayRowPepConstruccion[$linea]['antiguo'] = $rowRegistro;
                //MODIFICO EL REGISTRO EN BD
                $sql       = "UPDATE PEP_CONSTRUCCION SET
                            DESCRIPCION_PEP='" . $bd->escapeCondicional(${"txDescripcionPep_" . $linea}) . "'
                            ,BAJA='" . (${"chBaja_" . $linea} == 0 ? "0" : "1") . "'
                             WHERE ID_PEP_CONSTRUCCION=" . $rowRegistro->ID_PEP_CONSTRUCCION;
                $TipoError = "ErrorEjecutarSql";
                $bd->ExecSQL($sql);

                // GUARDO LOS CAMBIOS
                $rowRegistroNuevo                         = $bd->VerReg("PEP_CONSTRUCCION", "ID_PEP_CONSTRUCCION", $rowRegistro->ID_PEP_CONSTRUCCION);
                $arrayRowPepConstruccion[$linea]['nuevo'] = $rowRegistroNuevo; //GUARDO EL REGISTRO PARA GENERAR EL CSV
                $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, 'Modificación', "Maestro", $rowRegistro->ID_PEP_CONSTRUCCION, "PEP Construccion", "PEP_CONSTRUCCION", $rowRegistro, $rowRegistroNuevo);

                $mensajeExito .= $auxiliar->traduce("PEP Construccion modificada Correctamente", $administrador->ID_IDIOMA) . ": $rowRegistro->ID_PEP_CONSTRUCCION " . ". \n";
            else:
                //GUARDO EL REGISTRO PARA GENERAR EL CSV
                $arrayRowPepConstruccion[$linea]['antiguo'] = "";
                //INSERTO EL REGISTRO EN BD
                $sql       = "INSERT INTO PEP_CONSTRUCCION SET
                            DESCRIPCION_PEP='" . $bd->escapeCondicional(${"txDescripcionPep_" . $linea}) . "'
                            ,BAJA='" . (${"chBaja_" . $linea} == 0 ? "0" : "1") . "'";
                $TipoError = "ErrorEjecutarSql";
                $bd->ExecSQL($sql);

                // OBTENGO EL ID DE LA ÚLTIMA CONSULTA
                $idEntrada                             = $bd->IdAsignado();
                $arrayRowPepConstruccion[$linea]['id'] = $idEntrada;

                // GUARDO LOS CAMBIOS
                $rowRegistroNuevo                         = $bd->VerReg("PEP_CONSTRUCCION", "ID_PEP_CONSTRUCCION", $idEntrada);
                $arrayRowPepConstruccion[$linea]['nuevo'] = $rowRegistroNuevo; //GUARDO EL REGISTRO PARA GENERAR EL CSV
                $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, 'Creacion', "Maestro", $idEntrada, "PEP Construccion");

                $mensajeExito .= $auxiliar->traduce("PEP Construccion creada Correctamente", $administrador->ID_IDIOMA) . ": $idEntrada " . ". \n";
            endif; //FIN SI EXISTE/NO EXISTE

            //SI NO HAY FALLOS, COMMIT DE LA TRANSACCION
            $bd->commit_transaction();
            $filasOk++;
        else://ERROR EN LOS DATOS DE LA LINEA
            $indiceKo++;
            //DESHAGO LA TRANSACCION
            $bd->rollback_transaction();
        endif;
    endif; //FIN CHECK MARCADO
endforeach; //BUCLE CHECKS MARCADOS
?>
<html>
<head>
    <? require_once $pathClases . "lib/gral_js.php"; ?>

    <script language="javascript" type="text/javascript">
        function redirigir() {
            window.document.location.href = 'index.php?recordar_busqueda=1';
        }
    </script>
</head>
<body bgcolor="#FFFFFF" background="<? echo $pathRaiz ?>imagenes/fondo_pantalla.gif" leftmargin="0" topmargin="0"
      marginwidth="0" marginheight="0">

<table width="100%" border="0" align="center" cellpadding="0" cellspacing="0" background="">
    <tr>
        <td valign=top align=middle height=10>
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
                                <td align="left" valign="top" bgcolor="#B3C7DA" class="lineabajo">
                                    <table width="100%" border="0" cellpadding="0" cellspacing="0"
                                           class="lineabajo">

                                        <tr class="lineabajo">
                                            <td colspan="2" align="center" bgcolor="#D9E3EC">

                                                <? if ($filasKo > 0): ?>
                                                    <table width="98%" cellpadding="0" cellspacing="2">
                                                        <tr>
                                                            <td height="19" class="blanco" align="left">
                                                                <div align="center"><span
                                                                        class="textorojo resaltado"><?= $auxiliar->traduce("LAS SIGUIENTES LINEAS NO HAN PODIDO SER PROCESADAS", $administrador->ID_IDIOMA) ?></span>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td height="19" class="blanco" align="left">
                                                                <div align="center"><span
                                                                        class="textorojo resaltado"><?= $auxiliar->traduce("NUMERO DE ERRORES", $administrador->ID_IDIOMA) ?>
                                                                        : <?= $filasKo . "/" . ($filasOk + $filasKo) ?></span>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                    <table width="98%" cellpadding="0" cellspacing="2"
                                                           class="linealrededor">
                                                        <tr>
                                                            <td height="19" bgcolor="#2E8AF0" class="blanco"
                                                                align="center">
                                                                <?= $auxiliar->traduce("Errores", $administrador->ID_IDIOMA) ?>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td height="19" bgcolor="#FFF" align="center">
                                                                <table cellpadding="0" cellspacing="0" width="100%">
                                                                    <tr>
                                                                        <td>
                                                                            <?
                                                                            $numeroFilas = 20;
                                                                            if ($filasKo < 20):
                                                                                $numeroFilas = $filasKo + 1;
                                                                            endif;
                                                                            ?>
                                                                            <textarea name="txLineasError"
                                                                                      class="copyright"
                                                                                      style="resize:none; width:100%;"
                                                                                      rows="<? echo $numeroFilas + 1 ?>"
                                                                                      readonly="readonly"><? echo $textoError ?></textarea>
                                                                        </td>
                                                                    </tr>
                                                                </table>
                                                            </td>
                                                        </tr>
                                                    </table>

                                                <? endif; ?>

                                                <? if ($filasOk > 0): ?>

                                                    <br/>


                                                    <table width="98%" cellpadding="0" cellspacing="2">
                                                        <tr>
                                                            <td height="19" class="blanco" align="left">
                                                                <div align="center"><span
                                                                        class="textoazul resaltado"><?= $auxiliar->traduce("NUMERO DE REGISTROS PROCESADOS CORRECTAMENTE", $administrador->ID_IDIOMA) ?>
                                                                        : <?= $filasOk . "/" . ($filasOk + $filasKo) ?></span>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                    <table width="98%" cellpadding="0" cellspacing="2"
                                                           class="linealrededor">
                                                        <tr>
                                                            <td height="19" bgcolor="#2E8AF0" class="blanco"
                                                                align="center">
                                                                <?= $auxiliar->traduce("Errores", $administrador->ID_IDIOMA) ?>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td height="19" bgcolor="#FFF" align="center">
                                                                <table cellpadding="0" cellspacing="0" width="100%">
                                                                    <tr>
                                                                        <td>
                                                                            <?
                                                                            $numeroFilas = 20;
                                                                            if ($filasOk < 20):
                                                                                $numeroFilas = $filasOk + 1;
                                                                            endif;
                                                                            ?>
                                                                            <textarea name="txLineasOK"
                                                                                      class="copyright"
                                                                                      style="resize:none; width:100%;"
                                                                                      rows="<? echo $numeroFilas + 1 ?>"
                                                                                      readonly="readonly"><? echo $mensajeExito ?></textarea>
                                                                        </td>
                                                                    </tr>
                                                                </table>
                                                            </td>
                                                        </tr>
                                                    </table>

                                                <? elseif ($filasOk == 0): ?>

                                                    <br/>
                                                    <table width="98%" cellpadding="0" cellspacing="2">
                                                        <tr>
                                                            <td height="19" class="blanco" align="left">
                                                                <div align="center"><span
                                                                        class="textorojo resaltado"><?= $auxiliar->traduce("NO SE HA PROCESADO NINGUN REGISTRO", $administrador->ID_IDIOMA) ?></span>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    </table>

                                                <? endif; ?>

                                                <br/>

                                                <table width="100%" cellpadding="0" cellspacing="0">
                                                    <tr height="20px;">
                                                        <td>
                                                            <div align="center">
                                                                <a id="continuar" href="#" onclick="redirigir()"
                                                                   class="senaladoazul">
                                                                    &nbsp;&nbsp;&nbsp;&nbsp;<?= $auxiliar->traduce("Continuar", $administrador->ID_IDIOMA) ?>
                                                                    &nbsp;&nbsp;&nbsp;&nbsp;</a>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                </table>

                                                <br/>

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
</body>
</html>