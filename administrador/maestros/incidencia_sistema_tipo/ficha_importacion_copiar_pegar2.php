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
require_once $pathClases . "lib/pedido.php";
require_once $pathClases . "lib/material.php";

session_start();
include $pathRaiz . "seguridad_admin.php";

$tituloPag         = $auxiliar->traduce("Tipos de Incidencias", $administrador->ID_IDIOMA);
$tituloNav         = $auxiliar->traduce("Maestros", $administrador->ID_IDIOMA) . " >> " . $auxiliar->traduce("Tipos de Incidencias", $administrador->ID_IDIOMA);
$ZonaTablaPadre    = "Maestros";
$ZonaSubTablaPadre = "MaestrosSubmenuAplicacion";
$ZonaTabla         = "MaestrosIncidenciaSistemaTipo";

// COMPRUEBA SI TIENE PERMISOS
if ($administrador->Hayar_Permiso_Perfil('ADM_MAESTROS_INCIDENCIA_SISTEMA_TIPO') < 2):
    $html->PagError("SinPermisos");
endif;

//COMPRUEBO QUE SE HAYA RELLENADO EL TEXTAREA
$html->PagErrorCondicionado($txLineasCampos, "==", "", "LineasIntroducidasVacio");

// OBTENEMOS LA CADENA DE TEXTO CON LAS LINEAS Y METEMOS CADA LINEA EN UNA ARRAY
$arrLineas = explode("\n", (string)$txLineasCampos);

//VARIABLE PARA GUARDAR LAS LINEAS A GRABAR
$arrLineasValidas = array();
$k                = 0;

//VARIABLES PARA CONTROLAR LO MOSTRADO
$strKo      = "";
$indiceOK   = 0; //NÚMERO DE LÍNEAS CORRECTAS
$indiceKo   = 0; //NÚMERO DE LÍNEAS ERRÓNEAS
$filasKo    = 0; //NÚMERO TOTAL DE ERRORES
$strAviso   = ""; //NÚMERO DE LÍNEAS DE AVISOS
$filasAviso = 0; //NÚMERO TOTAL DE AVISOS

//VARIABLES PARA CONTROLAR LOS INDICES DE LAS LINEAS
$indice  = 1;
$numFila = 1;

// NOS CREAMOS EL ARRAY DEFINITIVO
if (count( (array)$arrLineas) > 0):    //HAY LINEAS
    foreach ($arrLineas as $linea):    //BUCLE LINEAS
        $numFila++;

        if ($linea != ""):    //LINEA NO VACIA
            //FORMATEAMOS LOS DATOS INTRODUCIDOS
            $linea = trim( (string)$linea);

            if ($linea == ""):
                continue;
            endif;

            //SACO LOS VALORES DE LA LINEA
            $arrValores = explode("|", (string)$linea);

            //VARIABLE PARA SABER SI HAY ALGUN CAMPO REPETIDO
            $campo_repetido = false;

            //VARIABLE PARA SABER SI HAY ERROR EN LINEA
            $errorLinea = false;

            //COMPROBAMOS EL NÚMERO DE CAMPOS
            $numeroCampos = count( (array)$arrValores);

            if ($numeroCampos > 7):
                $strKo .= $auxiliar->traduce("Línea", $administrador->ID_IDIOMA) . " $indice. ";
                $strKo .= $auxiliar->traduce("Número de campos introducidos incorrecto", $administrador->ID_IDIOMA) . ".\n";
                $filasKo++;
                $errorLinea = true;
            endif;

            //OBTENENGO VALORES
            $incidenciaSistemaTipo    = trim( (string)$arrValores[0]);
            $incidenciaSistemaTipoEng  = trim( (string)$arrValores[1]);
            $baja   = trim( (string)$arrValores[2]);

            // COMPRUEBO QUE INCIDENCIA SISTEMA TIPO NO ESTÉ VACÍO
            if ($incidenciaSistemaTipo == ""):
                $strKo .= $auxiliar->traduce("Línea", $administrador->ID_IDIOMA) . " $indice. ";
                $strKo .= $auxiliar->traduce("El campo", $administrador->ID_IDIOMA) . " " . $auxiliar->traduce("incidencia sistema tipo", $administrador->ID_IDIOMA) . " " . $auxiliar->traduce("esta vacío", $administrador->ID_IDIOMA) . ".\n";
                $filasKo++;
                $errorLinea = true;
            endif;

            // COMPRUEBO QUE INCIDENCIA SISTEMA TIPO ENG NO ESTÉ VACÍO
            if ($incidenciaSistemaTipoEng == ""):
                $strKo .= $auxiliar->traduce("Línea", $administrador->ID_IDIOMA) . " $indice. ";
                $strKo .= $auxiliar->traduce("El campo", $administrador->ID_IDIOMA) . " " . $auxiliar->traduce("incidencia sistema tipo Eng.", $administrador->ID_IDIOMA) . " " . $auxiliar->traduce("esta vacío", $administrador->ID_IDIOMA) . ".\n";
                $filasKo++;
                $errorLinea = true;
            endif;

            // COMPRUEBO QUE BAJA NO ESTÉ VACÍO
            if ($baja != ""):
                if (is_numeric($baja)):
                    if (preg_match('/^[0-9]+$/', (string) $baja)):
                        $baja = strval(intval($baja));
                    else:
                        $strKo .= $auxiliar->traduce("Línea", $administrador->ID_IDIOMA) . " $indice. ";
                        $strKo .= $auxiliar->traduce("El campo baja no tiene el formato correcto", $administrador->ID_IDIOMA) . ".\n";
                        $filasKo++;
                        $errorLinea = true;
                    endif;
                else:
                    $strKo .= $auxiliar->traduce("Línea", $administrador->ID_IDIOMA) . " $indice. ";
                    $strKo .= $auxiliar->traduce("El campo baja debe ser de tipo numerico", $administrador->ID_IDIOMA) . ".\n";
                    $filasKo++;
                    $errorLinea = true;

                endif;
            else:
                $strKo .= $auxiliar->traduce("Línea", $administrador->ID_IDIOMA) . " $indice. ";
                $strKo .= $auxiliar->traduce("El campo", $administrador->ID_IDIOMA) . " " . $auxiliar->traduce("baja", $administrador->ID_IDIOMA) . " " . $auxiliar->traduce("esta vacío", $administrador->ID_IDIOMA) . ".\n";
                $filasKo++;
                $errorLinea = true;
            endif;

            foreach ($arrLineasValidas as $linea):
                if (strtoupper( (string)$linea['INCIDENCIA_SISTEMA_TIPO']) == strtoupper( (string)$incidenciaSistemaTipo) && strtoupper( (string)$linea['INCIDENCIA_SISTEMA_TIPO_ENG']) == strtoupper( (string)$incidenciaSistemaTipoEng) && $linea['BAJA'] == $baja):
                    $strKo .= $auxiliar->traduce("Línea", $administrador->ID_IDIOMA) . " $indice. ";
                    $strKo .= $auxiliar->traduce("El registro está duplicado en los datos introducidos", $administrador->ID_IDIOMA) . ".\n";
                    $filasKo++;
                    $campo_repetido = true;
                endif;
            endforeach;

            if ($campo_repetido):
                //INCREMENTO LA LINEA
                $indice = $indice + 1;

                continue;
            endif;

            //REPETIDO EN BBDD
            if ($errorLinea == false):
                $NotificaErrorPorEmail = "No";
                $rowRepetido           = $bd->VerRegRest("INCIDENCIA_SISTEMA_TIPO", "INCIDENCIA_SISTEMA_TIPO='" . $bd->escapeCondicional($incidenciaSistemaTipo) . "' AND INCIDENCIA_SISTEMA_TIPO_ENG='" . $bd->escapeCondicional($incidenciaSistemaTipoEng) . "' AND BAJA=" . $baja, "No");

                $arrLineasValidas[$numFila]['INCIDENCIA_SISTEMA_TIPO']    = strtoupper( (string)$incidenciaSistemaTipo);
                $arrLineasValidas[$numFila]['INCIDENCIA_SISTEMA_TIPO']  = $incidenciaSistemaTipoEng;
                $arrLineasValidas[$numFila]['BAJA']   = $baja;

                //AÑADIMOS 1 AL NÚMERO DE LÍNEAS VÁLIDAS
                $indiceOK++;

                //DATOS CORRECTOS
                if ($rowRepetido == true):
                    $strAviso .= $auxiliar->traduce("Línea", $administrador->ID_IDIOMA) . " $indice. ";
                    $strAviso .= $auxiliar->traduce("El registro", $administrador->ID_IDIOMA) . " " . $auxiliar->traduce("ya existe y se actualizará", $administrador->ID_IDIOMA) . ".\n";
                    $filasAviso++;
                endif;
            else:
                //AÑADIMOS 1 AL NÚMERO DE LÍNEAS ERRÓNEAS
                $indiceKo++;
            endif;

            //INCREMENTO LA LINEA
            $indice = $indice + 1;

            //INCREMENTO EL INDICE DEL ARRAY
            $k++;
        endif;    //FIN LINEA NO VACIA
    endforeach;    //FIN BUCLE LINEAS
endif;    //FIN HAY LINEAS
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <? require_once $pathClases . "lib/gral_js.php"; ?>
    <script language="JavaScript" type="text/JavaScript">

        var bandera = true;

        jQuery(document).ready(function () {
            <? if ($indiceOK > 0): ?>

            jQuery('#btnProcesar').attr('onclick', 'continuar();return false');
            jQuery('#btnProcesar').css('color', '');
            jQuery('#btnProcesar2').attr('onclick', 'continuar();return false');
            jQuery('#btnProcesar2').css('color', '');

            <? endif; ?>
        });

        function continuar() {
            if (bandera == true) {
                bandera = false;

                jQuery('#btnProcesar').attr('onclick', 'return false');
                jQuery('#btnProcesar').css('color', '#CCCCCC');
                jQuery('#btnProcesar2').attr('onclick', 'return false');
                jQuery('#btnProcesar2').css('color', '#CCCCCC');

                document.FormSelect.submit();

                return false;
            }
        }

        function seleccionarTodas(chSel) {
            for (i = 0; i < document.FormSelect.elements.length; i++) {
                if ((document.FormSelect.elements[i].type == "checkbox") && (document.FormSelect.elements[i].name.substr(0, 8) == "chLinea_")) {
                    if (chSel.checked == 1) {
                        document.FormSelect.elements[i].checked = 1;
                    } else {
                        document.FormSelect.elements[i].checked = 0;
                    }
                }
            }
        }

    </script>
</head>
<body class="fancy" bgcolor="#FFFFFF" background="<?= $pathRaiz ?>imagenes/fondo_pantalla.gif" leftmargin="0"
      topmargin="0" marginwidth="0" marginheight="0">
<FORM NAME="FormSelect" ACTION="ficha_importacion_accion.php" METHOD="POST" style="margin-bottom:0;"
      enctype="multipart/form-data">
    <input type="hidden" name="claveTiempo" value="<? echo $claveTiempo; ?>">
    <? $navegar->GenerarCamposOcultosForm(); ?>
    <table width="100%" border="0" align="center" cellpadding="0" cellspacing="0" background="">
        <tr>
            <td align="center" valign="top">
                <table width="100%" border="0" cellpadding="0" cellspacing="0">
                    <tr>
                        <td height="3" colspan="2" align="center" valign="top" bgcolor="#7A0A0A" class="linearriba">
                            <img src="<?= $pathRaiz ?>imagenes/transparente.gif" width="10" height="3">
                        </td>
                    </tr>
                    <tr>
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

                                                    <table width="100%" cellpadding="0" cellspacing="0">
                                                        <tr height="20px;" bgcolor="#B3C7DA">
                                                            <td>
                                                                <div align="left">
                                      <span class="textoazul">
                                        &nbsp;&nbsp;<a onclick="history.back();return false;" class="senaladoazul"
                                                       href="#">
                                              &nbsp;&nbsp;&nbsp;&nbsp;<?= $auxiliar->traduce("Volver", $administrador->ID_IDIOMA) ?>
                                              &nbsp;&nbsp;&nbsp;&nbsp;</a>&nbsp;&nbsp;
                                      </span>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div align="right">
                                <span class="textoazul">
                                    <a href="#" style='color:#CCCCCC' onClick='return false;' id='btnProcesar'
                                       class="senaladoverde">
                                        &nbsp;&nbsp;&nbsp;&nbsp;<?= $auxiliar->traduce("Procesar", $administrador->ID_IDIOMA) ?>
                                        &nbsp;&nbsp;&nbsp;&nbsp;</a>&nbsp;&nbsp;
                                </span>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    </table>

                                                    <br/>

                                                    <? if ($filasKo > 0): ?>
                                                        <table width="98%" cellpadding="0" cellspacing="2">
                                                            <tr>
                                                                <td height="19" class="blanco" align="left">
                                                                    <div align="left"><span
                                                                            class="textorojo resaltado"><?= $auxiliar->traduce("LAS SIGUIENTES LINEAS NO SE CARGARAN POR CONTENER ERRORES", $administrador->ID_IDIOMA) ?>
                                                                            :</span></div>
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
                                                                            <td style="padding-right:4px;">
                                                                                <?
                                                                                $numeroFilas = 20;
                                                                                if ($filasKo < 20):
                                                                                    $numeroFilas = $filasKo + 1;
                                                                                endif;
                                                                                ?>
                                                                                <textarea name="txLineasError"
                                                                                          class="copyright"
                                                                                          style="resize:none; width:100%;"
                                                                                          rows="<? echo $numeroFilas ?>"
                                                                                          readonly="readonly"><? echo $strKo ?></textarea>
                                                                            </td>
                                                                        </tr>
                                                                    </table>
                                                                </td>
                                                            </tr>
                                                        </table>
                                                        <br/>
                                                    <? endif; ?>

                                                    <? if ($filasAviso > 0): ?>
                                                        <table width="98%" cellpadding="0" cellspacing="2">
                                                            <tr>
                                                                <td height="19" class="blanco" align="left">
                                                                    <div align="left"><span
                                                                            class="textorojo resaltado"><?= $auxiliar->traduce("LAS SIGUIENTES LINEAS YA EXISTEN EN LA BD", $administrador->ID_IDIOMA) . ":"; ?>
                                                                            </span></div>
                                                                </td>
                                                            </tr>
                                                        </table>
                                                        <table width="98%" cellpadding="0" cellspacing="2"
                                                               class="linealrededor">
                                                            <tr>
                                                                <td height="19" bgcolor="#2E8AF0" class="blanco"
                                                                    align="center">
                                                                    <?= $auxiliar->traduce("Avisos", $administrador->ID_IDIOMA) ?>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td height="19" bgcolor="#FFF" align="center">
                                                                    <table cellpadding="0" cellspacing="0" width="100%">
                                                                        <tr>
                                                                            <td style="padding-right:4px;">
                                                                                <?
                                                                                $numeroFilas = 20;
                                                                                if ($filasAviso < 20):
                                                                                    $numeroFilas = $filasAviso + 1;
                                                                                endif;
                                                                                ?>
                                                                                <textarea name="txLineasError"
                                                                                          class="copyright"
                                                                                          style="resize:none; width:100%;"
                                                                                          rows="<?= $numeroFilas ?>"
                                                                                          readonly="readonly"><?= $strAviso ?></textarea>
                                                                            </td>
                                                                        </tr>
                                                                    </table>
                                                                </td>
                                                            </tr>
                                                        </table>
                                                        <br/>
                                                    <? endif; ?>

                                                    <? if ($indiceOK > 0): ?>
                                                        <table width="98%" cellpadding="0" cellspacing="2">
                                                            <tr>
                                                                <td height="19" class="blanco" align="left">
                                                                    <div align="left"><span
                                                                            class="textoazul resaltado"><?= $auxiliar->traduce("LAS LINEAS SELECCIONADAS SERAN IMPORTADAS", $administrador->ID_IDIOMA) ?>
                                                                            :</span></div>
                                                                </td>
                                                            </tr>
                                                        </table>
                                                        <table width="98%" cellpadding="0" cellspacing="2"
                                                               class="linealrededor">
                                                            <tr>
                                                                <td height="19" bgcolor="#2E8AF0" class="blanco"
                                                                    align="center">
                                                                    <?= $auxiliar->traduce("Correcto a Procesar", $administrador->ID_IDIOMA) ?>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <?
                                                                $numeroFilas = 20;
                                                                if ($indiceOK < 20):
                                                                    $numeroFilas = $indiceOK + 1;
                                                                elseif ($indiceOK > 20):
                                                                    $numeroFilas = 40 - $indiceKo + 1;
                                                                endif;
                                                                ?>

                                                                <td height="<? echo($numeroFilas * 15); ?>"
                                                                    bgcolor="#FFF" align="center">

                                                                    <div>

                                                                        <table cellpadding="0" cellspacing="2"
                                                                               width="100%">

                                                                            <tr>
                                                                                <td width="2%" height="19"
                                                                                    bgcolor="#2E8AF0" class="blanco">
                                                                                    <?
                                                                                    $valorCheck = '1';
                                                                                    $jscript    = " onClick=\"seleccionarTodas(this)\" ";
                                                                                    $Nombre     = 'chSelecTodas';
                                                                                    $html->Option("chSelecTodas", "Check", "1", $valorCheck);
                                                                                    $jscript = "";
                                                                                    ?>
                                                                                </td>
                                                                                <td height="19" bgcolor="#2E8AF0"
                                                                                    class="blanco"
                                                                                    title="<?= $auxiliar->traduce("Incidencia Sistema Tipo", $administrador->ID_IDIOMA) ?>"><? echo $auxiliar->traduce("Incidencia Sistema Tipo", $administrador->ID_IDIOMA) ?></td>
                                                                                <td height="19" bgcolor="#2E8AF0"
                                                                                    class="blanco"
                                                                                    title="<?= $auxiliar->traduce("Incidencia Sistema Tipo Eng.", $administrador->ID_IDIOMA) ?>"><? echo $auxiliar->traduce("Incidencia Sistema Tipo Eng.", $administrador->ID_IDIOMA) ?></td>
                                                                                <td height="19" bgcolor="#2E8AF0"
                                                                                    class="blanco"
                                                                                    title="<?= $auxiliar->traduce("Baja", $administrador->ID_IDIOMA) ?>"><? echo $auxiliar->traduce("Baja", $administrador->ID_IDIOMA) ?></td>
                                                                            </tr>

                                                                            <? if (count( (array)$arrLineasValidas) == 0): //NO HAY LINEAS VALIDAS PARA IMPORTAR DATOS ?>

                                                                                <tr>
                                                                                    <td colspan="11"
                                                                                        align="center"><?= $auxiliar->traduce("No existen datos correctos a importar", $administrador->ID_IDIOMA) ?></td>
                                                                                </tr>

                                                                            <? elseif (count( (array)$arrLineasValidas) > 0): //HAY LINEAS VALIDAS PARA IMPORTAR DATOS?>

                                                                                <?
                                                                                //VARIABLE PARA PINTAR EL COLOR DE LA FILA
                                                                                $i = 0;
                                                                                ?>

                                                                                <!-- PINTO LAS LINEAS -->
                                                                                <? foreach ($arrLineasValidas as $indice => $arrValores): ?>
                                                                                    <?
                                                                                    //COLOR DE LA FILA
                                                                                    if ($i % 2 == 0) $myColor = "#B3C7DA";
                                                                                    else $myColor = "#AACFF9";

                                                                                    //RECUPERO LOS VALORES IMPORTADOS

                                                                                    $incidenciaSistemaTipo   = $arrValores['INCIDENCIA_SISTEMA_TIPO'];
                                                                                    $incidenciaSistemaTipoEng = $arrValores['INCIDENCIA_SISTEMA_TIPO_ENG'];
                                                                                    $baja = $arrValores['BAJA'];
                                                                                    ?>

                                                                                    <tr>
                                                                                        <td height="18" align="center"
                                                                                            bgcolor="<?= $myColor ?>"
                                                                                            class="enlaceceldas">
                                                                                            <input type="hidden"
                                                                                                   id="txIncidenciaSistemaTipo_<? echo $indice ?>"
                                                                                                   name="txIncidenciaSistemaTipo_<? echo $indice ?>"
                                                                                                   value="<? echo htmlentities( (string)$incidenciaSistemaTipo) ?>">
                                                                                            <input type="hidden"
                                                                                                   id="txIncidenciaSistemaTipoEng_<? echo $indice ?>"
                                                                                                   name="txIncidenciaSistemaTipoEng_<? echo $indice ?>"
                                                                                                   value="<? echo htmlentities( (string)$incidenciaSistemaTipoEng) ?>">
                                                                                            <input type="hidden"
                                                                                                   id="txBaja_<? echo $indice ?>"
                                                                                                   name="txBaja_<? echo $indice ?>"
                                                                                                   value="<? echo $baja ?>">

                                                                                            <? $html->Option("chLinea_" . $indice, "Check", "1", 1); ?>
                                                                                        </td>
                                                                                        <td align="left"
                                                                                            bgcolor="<?= $myColor ?>"
                                                                                            class="enlaceceldas">
                                                                                            &nbsp;<? echo $incidenciaSistemaTipo; ?>
                                                                                            &nbsp;
                                                                                        </td>
                                                                                        <td align="left"
                                                                                            bgcolor="<?= $myColor ?>"
                                                                                            class="enlaceceldas">
                                                                                            &nbsp;<? echo $incidenciaSistemaTipoEng; ?>
                                                                                            &nbsp;
                                                                                        </td>
                                                                                        <td align="left"
                                                                                            bgcolor="<?= $myColor ?>"
                                                                                            class="enlaceceldas">
                                                                                            &nbsp;<? echo $baja; ?>
                                                                                            &nbsp;
                                                                                        </td>
                                                                                    </tr>

                                                                                    <?
                                                                                    //INCREMENTO EL VALOR DE LA VARIABLE
                                                                                    $i++;
                                                                                    ?>
                                                                                <? endforeach; ?>
                                                                                <!-- FIN PINTO LAS LINEAS -->

                                                                            <? endif; //FIN NO HAY/HAY LINEAS VALIDAS PARA IMPORTAR DATOS ?>

                                                                        </table>

                                                                    </div>

                                                                </td>
                                                            </tr>
                                                        </table>

                                                    <? endif; ?>

                                                    <br/>

                                                    <table width="100%" cellpadding="0" cellspacing="0">
                                                        <tr height="20px;" bgcolor="#B3C7DA">
                                                            <td>
                                                                <div align="left">
                                                  <span class="textoazul">
                                                    &nbsp;&nbsp;<a onclick="history.back();return false;"
                                                                   class="senaladoazul" href="#">&nbsp;&nbsp;&nbsp;&nbsp;<?= $auxiliar->traduce("Volver", $administrador->ID_IDIOMA) ?>
                                                          &nbsp;&nbsp;&nbsp;&nbsp;</a>&nbsp;&nbsp;
                                                  </span>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div align="right">
                                                  <span class="textoazul">
                                                    <a href="#" style='color:#CCCCCC' onClick='return false;'
                                                       id='btnProcesar2' class="senaladoverde">
                                                        &nbsp;&nbsp;&nbsp;&nbsp;<?= $auxiliar->traduce("Procesar", $administrador->ID_IDIOMA) ?>
                                                        &nbsp;&nbsp;&nbsp;&nbsp;</a>&nbsp;&nbsp;
                                                  </span>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>

                                        </table> <!-- fin table lineabajo-->
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
