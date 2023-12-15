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

            if ($numeroCampos > 15):
                $strKo .= $auxiliar->traduce("Línea", $administrador->ID_IDIOMA) . " $indice. ";
                $strKo .= $auxiliar->traduce("Número de campos introducidos incorrecto", $administrador->ID_IDIOMA) . ".\n";
                $filasKo++;
                $errorLinea = true;
            endif;
            //OBTENENGO VALORES
            $NumMaterial    = trim( (string)$arrValores[0]);
            $Desc_ESP  = trim( (string)$arrValores[1]);
            $Desc_ENG   = trim( (string)$arrValores[2]);
            $Estatus_Material   = trim( (string)$arrValores[3]);
            $Tipo_Material=trim( (string)$arrValores[4]);
            $Baja   = trim( (string)$arrValores[5]);
            $Familia_Material   = trim( (string)$arrValores[6]);
            $Familia_Repro   = trim( (string)$arrValores[7]);
            $Marca   = trim( (string)$arrValores[8]);
            if($Marca==""){
                $Marca="-";
            }
            $Modelo=trim( (string)$arrValores[9]);
            if($Modelo==""){
                $Modelo="-";
            }
            $Unidad_Medida   = trim( (string)$arrValores[10]);
            $Unidad_Compra   = trim( (string)$arrValores[11]);
            $Numerador_Conversion   = trim( (string)$arrValores[12]);
            $Denominador_Conversion   = trim( (string)$arrValores[13]);
            $Observaciones= trim( (string)$arrValores[14]);

            // COMPRUEBO QUE Nº Material NO ESTÉ VACÍO
            if ($NumMaterial == ""):
                $strKo .= $auxiliar->traduce("Línea", $administrador->ID_IDIOMA) . " $indice. ";
                $strKo .= $auxiliar->traduce("El campo", $administrador->ID_IDIOMA) . " " . $auxiliar->traduce("Nº Material", $administrador->ID_IDIOMA) . " " . $auxiliar->traduce("esta vacío", $administrador->ID_IDIOMA) . ".\n";
                $filasKo++;
                $errorLinea = true;
            endif;

            // COMPRUEBO QUE DESC_ESP NO ESTÉ VACÍO
            if ($Desc_ESP == ""):
                $strKo .= $auxiliar->traduce("Línea", $administrador->ID_IDIOMA) . " $indice. ";
                $strKo .= $auxiliar->traduce("El campo", $administrador->ID_IDIOMA) . " " . $auxiliar->traduce("Descripcion ESP", $administrador->ID_IDIOMA) . " " . $auxiliar->traduce("esta vacío", $administrador->ID_IDIOMA) . ".\n";
                $filasKo++;
                $errorLinea = true;
            endif;

            // COMPRUEBO QUE DESC_ENG NO ESTÉ VACÍO
            if ($Desc_ENG == ""):
                $strKo .= $auxiliar->traduce("Línea", $administrador->ID_IDIOMA) . " $indice. ";
                $strKo .= $auxiliar->traduce("El campo", $administrador->ID_IDIOMA) . " " . $auxiliar->traduce("Descripcion ENG", $administrador->ID_IDIOMA) . " " . $auxiliar->traduce("esta vacío", $administrador->ID_IDIOMA) . ".\n";
                $filasKo++;
                $errorLinea = true;
            endif;

            // COMPRUEBO QUE ESTATUS MATERIAL NO ESTÉ VACÍO
            if ($Estatus_Material == ""):
                $strKo .= $auxiliar->traduce("Línea", $administrador->ID_IDIOMA) . " $indice. ";
                $strKo .= $auxiliar->traduce("El campo", $administrador->ID_IDIOMA) . " " . $auxiliar->traduce("Estatus material", $administrador->ID_IDIOMA) . " " . $auxiliar->traduce("esta vacío", $administrador->ID_IDIOMA) . ".\n";
                $filasKo++;
                $errorLinea = true;
            endif;

            // COMPRUEBO QUE TIPO NO ESTÉ VACÍO
            if ($Tipo_Material == ""):
                $strKo .= $auxiliar->traduce("Línea", $administrador->ID_IDIOMA) . " $indice. ";
                $strKo .= $auxiliar->traduce("El campo", $administrador->ID_IDIOMA) . " " . $auxiliar->traduce("Tipo material", $administrador->ID_IDIOMA) . " " . $auxiliar->traduce("esta vacío", $administrador->ID_IDIOMA) . ".\n";
                $filasKo++;
                $errorLinea = true;
            endif;

            // COMPRUEBO QUE FAMILIA MATERIAL NO ESTÉ VACÍO
            if ($Familia_Material == ""):
                $strKo .= $auxiliar->traduce("Línea", $administrador->ID_IDIOMA) . " $indice. ";
                $strKo .= $auxiliar->traduce("El campo", $administrador->ID_IDIOMA) . " " . $auxiliar->traduce("Familia material", $administrador->ID_IDIOMA) . " " . $auxiliar->traduce("esta vacío", $administrador->ID_IDIOMA) . ".\n";
                $filasKo++;
                $errorLinea = true;
            endif;

            // COMPRUEBO QUE FAMILIA REPRO NO ESTÉ VACÍO
            if ($Familia_Repro == ""):
                $strKo .= $auxiliar->traduce("Línea", $administrador->ID_IDIOMA) . " $indice. ";
                $strKo .= $auxiliar->traduce("El campo", $administrador->ID_IDIOMA) . " " . $auxiliar->traduce("Familia repro", $administrador->ID_IDIOMA) . " " . $auxiliar->traduce("esta vacío", $administrador->ID_IDIOMA) . ".\n";
                $filasKo++;
                $errorLinea = true;
            endif;

            // COMPRUEBO QUE UNIDAD MEDIDA NO ESTÉ VACÍO
            if ($Unidad_Medida == ""):
                $strKo .= $auxiliar->traduce("Línea", $administrador->ID_IDIOMA) . " $indice. ";
                $strKo .= $auxiliar->traduce("El campo", $administrador->ID_IDIOMA) . " " . $auxiliar->traduce("Unidad de medida", $administrador->ID_IDIOMA) . " " . $auxiliar->traduce("esta vacío", $administrador->ID_IDIOMA) . ".\n";
                $filasKo++;
                $errorLinea = true;
            endif;

            // COMPRUEBO QUE UNIDAD COMPRA NO ESTÉ VACÍO
            if ($Unidad_Compra == ""):
                $strKo .= $auxiliar->traduce("Línea", $administrador->ID_IDIOMA) . " $indice. ";
                $strKo .= $auxiliar->traduce("El campo", $administrador->ID_IDIOMA) . " " . $auxiliar->traduce("Unidad de compra", $administrador->ID_IDIOMA) . " " . $auxiliar->traduce("esta vacío", $administrador->ID_IDIOMA) . ".\n";
                $filasKo++;
                $errorLinea = true;
            endif;

            // COMPRUEBO QUE NUMERADOR CONVERSION NO ESTÉ VACÍO
            if ($Numerador_Conversion == ""):
                $strKo .= $auxiliar->traduce("Línea", $administrador->ID_IDIOMA) . " $indice. ";
                $strKo .= $auxiliar->traduce("El campo", $administrador->ID_IDIOMA) . " " . $auxiliar->traduce("Numerador conversión", $administrador->ID_IDIOMA) . " " . $auxiliar->traduce("esta vacío", $administrador->ID_IDIOMA) . ".\n";
                $filasKo++;
                $errorLinea = true;
            endif;

            // COMPRUEBO QUE DENOMINADOR CONVERSION NO ESTÉ VACÍO
            if ($Denominador_Conversion == ""):
                $strKo .= $auxiliar->traduce("Línea", $administrador->ID_IDIOMA) . " $indice. ";
                $strKo .= $auxiliar->traduce("El campo", $administrador->ID_IDIOMA) . " " . $auxiliar->traduce("Denominador conversion", $administrador->ID_IDIOMA) . " " . $auxiliar->traduce("esta vacío", $administrador->ID_IDIOMA) . ".\n";
                $filasKo++;
                $errorLinea = true;
            endif;


            // COMPRUEBO QUE BAJA NO ESTÉ VACÍO
            if ($baja != "") {
                if (is_numeric($baja)) {
                    if (($baja != 0) && ($baja != 1)) {
                        $strKo .= $auxiliar->traduce("Línea", $administrador->ID_IDIOMA) . " $indice. ";
                        $strKo .= $auxiliar->traduce("El campo baja no tiene el formato correcto", $administrador->ID_IDIOMA) . ".\n";
                        $filasKo++;
                        $errorLinea = true;
                    }
                }
                if (is_string($baja)) {
                    if (($baja != 'Y') && ($baja != 'y') && ($baja != 'n') && ($baja != 'N')) {
                        $strKo .= $auxiliar->traduce("Línea", $administrador->ID_IDIOMA) . " $indice. ";
                        $strKo .= $auxiliar->traduce("El campo baja no tiene el formato correcto", $administrador->ID_IDIOMA) . ".\n";
                        $filasKo++;
                        $errorLinea = true;
                    }
                }
            }
/*
            foreach ($arrLineasValidas as $linea):

                if (strtoupper( (string)$linea['INCIDENCIA_SISTEMA_TIPO']) == strtoupper( (string)$incidenciaSistemaTipo) && strtoupper( (string)$linea['INCIDENCIA_SISTEMA_TIPO_ENG']) == strtoupper( (string)$incidenciaSistemaTipoEng) && $linea['BAJA'] == $baja):
                    $strKo .= $auxiliar->traduce("Línea", $administrador->ID_IDIOMA) . " $indice. ";
                    $strKo .= $auxiliar->traduce("El registro está duplicado en los datos introducidos", $administrador->ID_IDIOMA) . ".\n";
                    $filasKo++;
                    $campo_repetido = true;
                endif;
            endforeach;
*/
            if ($campo_repetido):
                //INCREMENTO LA LINEA
                $indice = $indice + 1;

                continue;
            endif;

            //REPETIDO EN BBDD
            if ($errorLinea == false):
                $NotificaErrorPorEmail = "No";
                //$rowRepetido           = $bd->VerRegRest("MATERIALES", "REFERENCIA_SCS='" . $bd->escapeCondicional($NumMaterial) . "' AND INCIDENCIA_SISTEMA_TIPO_ENG='" . $bd->escapeCondicional($incidenciaSistemaTipoEng) . "' AND BAJA=" . $baja, "No");
                $rowRepetido           = $bd->VerRegRest("MATERIAL", "REFERENCIA_SCS=" . $bd->escapeCondicional($NumMaterial),'No');
                $arrLineasValidas[$numFila]['INCIDENCIA_SISTEMA_TIPO']    = $incidenciaSistemaTipo;
                $arrLineasValidas[$numFila]['INCIDENCIA_SISTEMA_TIPO_ENG']  = $incidenciaSistemaTipoEng;
                $arrLineasValidas[$numFila]['BAJA']   = $baja;

                $arrLineasValidas[$numFila]['NumMaterial']=$NumMaterial;
                $arrLineasValidas[$numFila]['Desc_ESP']=$Desc_ESP;
                $arrLineasValidas[$numFila]['Desc_ENG']=$Desc_ENG;
                $arrLineasValidas[$numFila]['Estatus_Material']=$Estatus_Material;
                $arrLineasValidas[$numFila]['Tipo_Material']=$Tipo_Material;
                $arrLineasValidas[$numFila]['Baja']=$Baja;
                $arrLineasValidas[$numFila]['Familia_Material']=$Familia_Material;
                $arrLineasValidas[$numFila]['Familia_Repro']=$Familia_Repro;
                $arrLineasValidas[$numFila]['Marca']=$Marca;
                $arrLineasValidas[$numFila]['Modelo']=$Modelo;
                $arrLineasValidas[$numFila]['Unidad_Medida']=$Unidad_Medida;
                $arrLineasValidas[$numFila]['Unidad_Compra']=$Unidad_Compra;
                $arrLineasValidas[$numFila]['Numerador_Conversion']=$Numerador_Conversion;
                $arrLineasValidas[$numFila]['Denominador_Conversion']=$Denominador_Conversion;
                $arrLineasValidas[$numFila]['Observaciones']=$Observaciones;



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
                                                                                    title="<?= $auxiliar->traduce("Nº Material", $administrador->ID_IDIOMA) ?>"><? echo $auxiliar->traduce("Nº Material", $administrador->ID_IDIOMA) ?></td>
                                                                                <td height="19" bgcolor="#2E8AF0"
                                                                                    class="blanco"
                                                                                    title="<?= $auxiliar->traduce("Descripcion Material", $administrador->ID_IDIOMA) ?>"><? echo $auxiliar->traduce("Descripcion Material", $administrador->ID_IDIOMA) ?></td>
                                                                                <td height="19" bgcolor="#2E8AF0"
                                                                                    class="blanco"
                                                                                    title="<?= $auxiliar->traduce("Descripcion Material Ingles", $administrador->ID_IDIOMA) ?>"><? echo $auxiliar->traduce("Descripcion Material Ingles", $administrador->ID_IDIOMA) ?></td>
                                                                                <td height="19" bgcolor="#2E8AF0"
                                                                                    class="blanco"
                                                                                    title="<?= $auxiliar->traduce("Estatus Material", $administrador->ID_IDIOMA) ?>"><? echo $auxiliar->traduce("Estatus Material", $administrador->ID_IDIOMA) ?></td>
                                                                                <td height="19" bgcolor="#2E8AF0"
                                                                                    class="blanco"
                                                                                    title="<?= $auxiliar->traduce("Tipo Material", $administrador->ID_IDIOMA) ?>"><? echo $auxiliar->traduce("Tipo Material", $administrador->ID_IDIOMA) ?></td>
                                                                                <td height="19" bgcolor="#2E8AF0"
                                                                                    class="blanco"
                                                                                    title="<?= $auxiliar->traduce("Baja", $administrador->ID_IDIOMA) ?>"><? echo $auxiliar->traduce("Baja", $administrador->ID_IDIOMA) ?></td>
                                                                                <td height="19" bgcolor="#2E8AF0"
                                                                                    class="blanco"
                                                                                    title="<?= $auxiliar->traduce("Familia Material", $administrador->ID_IDIOMA) ?>"><? echo $auxiliar->traduce("Familia Material", $administrador->ID_IDIOMA) ?></td>
                                                                                <td height="19" bgcolor="#2E8AF0"
                                                                                    class="blanco"
                                                                                    title="<?= $auxiliar->traduce("Familia Repro", $administrador->ID_IDIOMA) ?>"><? echo $auxiliar->traduce("Familia Repro", $administrador->ID_IDIOMA) ?></td>
                                                                                <td height="19" bgcolor="#2E8AF0"
                                                                                    class="blanco"
                                                                                    title="<?= $auxiliar->traduce("Marca", $administrador->ID_IDIOMA) ?>"><? echo $auxiliar->traduce("Marca", $administrador->ID_IDIOMA) ?></td>
                                                                                <td height="19" bgcolor="#2E8AF0"
                                                                                    class="blanco"
                                                                                    title="<?= $auxiliar->traduce("Modelo", $administrador->ID_IDIOMA) ?>"><? echo $auxiliar->traduce("Modelo", $administrador->ID_IDIOMA) ?></td>
                                                                                <td height="19" bgcolor="#2E8AF0"
                                                                                    class="blanco"
                                                                                    title="<?= $auxiliar->traduce("Unidad de medida", $administrador->ID_IDIOMA) ?>"><? echo $auxiliar->traduce("Unidad de medida", $administrador->ID_IDIOMA) ?></td>
                                                                                <td height="19" bgcolor="#2E8AF0"
                                                                                    class="blanco"
                                                                                    title="<?= $auxiliar->traduce("Unidad de compra", $administrador->ID_IDIOMA) ?>"><? echo $auxiliar->traduce("Unidad de compra", $administrador->ID_IDIOMA) ?></td>
                                                                                <td height="19" bgcolor="#2E8AF0"
                                                                                    class="blanco"
                                                                                    title="<?= $auxiliar->traduce("Numerador conversion", $administrador->ID_IDIOMA) ?>"><? echo $auxiliar->traduce("Numerador conversion", $administrador->ID_IDIOMA) ?></td>
                                                                                <td height="19" bgcolor="#2E8AF0"
                                                                                    class="blanco"
                                                                                    title="<?= $auxiliar->traduce("Denominador conversión", $administrador->ID_IDIOMA) ?>"><? echo $auxiliar->traduce("Denominador conversion", $administrador->ID_IDIOMA) ?></td>
                                                                                <td height="19" bgcolor="#2E8AF0"
                                                                                    class="blanco"
                                                                                    title="<?= $auxiliar->traduce("Observaciones", $administrador->ID_IDIOMA) ?>"><? echo $auxiliar->traduce("Observaciones", $administrador->ID_IDIOMA) ?></td>
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
                                                                                    $NumMaterial=$arrValores['NumMaterial'];
                                                                                    $Desc_ESP=$arrValores['Desc_ESP'];
                                                                                    $Desc_ENG=$arrValores['Desc_ENG'];
                                                                                    $Estatus_Material=$arrValores['Estatus_Material'];
                                                                                    $Tipo_Material=$arrValores['Tipo_Material'];
                                                                                    $Baja=$arrValores['Baja'];
                                                                                    $Familia_Material=$arrValores['Familia_Material'];
                                                                                    $Familia_Repro=$arrValores['Familia_Repro'];
                                                                                    $Marca=$arrValores['Marca'];
                                                                                    $Modelo=$arrValores['Modelo'];
                                                                                    $Unidad_Medida=$arrValores['Unidad_Medida'];
                                                                                    $Unidad_Compra=$arrValores['Unidad_Compra'];
                                                                                    $Numerador_Conversion=$arrValores['Numerador_Conversion'];
                                                                                    $Denominador_Conversion=$arrValores['Denominador_Conversion'];
                                                                                    $Observaciones=$arrValores['Observaciones'];

                                                                                    ?>

                                                                                    <tr>
                                                                                        <td height="18" align="center"
                                                                                            bgcolor="<?= $myColor ?>"
                                                                                            class="enlaceceldas">
                                                                                            <input type="hidden"
                                                                                                   id="NumMaterial_<? echo $NumMaterial ?>"
                                                                                                   name="NumMaterial_<? echo $NumMaterial ?>"
                                                                                                   value="<? echo htmlentities( (string)$NumMaterial) ?>">
                                                                                            <input type="hidden"
                                                                                                   id="Desc_ESP_<? echo $NumMaterial ?>"
                                                                                                   name="Desc_ESP_<? echo $NumMaterial ?>"
                                                                                                   value="<? echo htmlentities( (string)$Desc_ESP) ?>">
                                                                                            <input type="hidden"
                                                                                                   id="Desc_ENG_<? echo $NumMaterial ?>"
                                                                                                   name="Desc_ENG_<? echo $NumMaterial ?>"
                                                                                                   value="<? echo $Desc_ENG ?>">
                                                                                            <input type="hidden"
                                                                                                   id="Estatus_Material_<? echo $NumMaterial ?>"
                                                                                                   name="Estatus_Material_<? echo $NumMaterial ?>"
                                                                                                   value="<? echo htmlentities( (string)$Estatus_Material) ?>">
                                                                                            <input type="hidden"
                                                                                                   id="Tipo_Material_<? echo $NumMaterial ?>"
                                                                                                   name="Tipo_Material_<? echo $NumMaterial ?>"
                                                                                                   value="<? echo htmlentities( (string)$Tipo_Material) ?>">
                                                                                            <input type="hidden"
                                                                                                   id="Baja_<? echo $NumMaterial ?>"
                                                                                                   name="Baja_<? echo $NumMaterial ?>"
                                                                                                   value="<? echo htmlentities( (string)$Baja) ?>">
                                                                                            <input type="hidden"
                                                                                                   id="Familia_Material_<? echo $NumMaterial ?>"
                                                                                                   name="Familia_Material_<? echo $NumMaterial ?>"
                                                                                                   value="<? echo htmlentities( (string)$Familia_Material) ?>">
                                                                                            <input type="hidden"
                                                                                                   id="Familia_Repro_<? echo $NumMaterial ?>"
                                                                                                   name="Familia_Repro_<? echo $NumMaterial ?>"
                                                                                                   value="<? echo htmlentities( (string)$Familia_Repro) ?>">
                                                                                            <input type="hidden"
                                                                                                   id="Marca_<? echo $NumMaterial ?>"
                                                                                                   name="Marca_<? echo $NumMaterial ?>"
                                                                                                   value="<? echo htmlentities( (string)$Marca) ?>">
                                                                                            <input type="hidden"
                                                                                                   id="Modelo_<? echo $NumMaterial ?>"
                                                                                                   name="Modelo_<? echo $NumMaterial ?>"
                                                                                                   value="<? echo htmlentities( (string)$Modelo) ?>">
                                                                                            <input type="hidden"
                                                                                                   id="Unidad_Medida_<? echo $NumMaterial ?>"
                                                                                                   name="Unidad_Medida_<? echo $NumMaterial ?>"
                                                                                                   value="<? echo htmlentities( (string)$Unidad_Medida) ?>">
                                                                                            <input type="hidden"
                                                                                                   id="Unidad_Compra_<? echo $NumMaterial ?>"
                                                                                                   name="Unidad_Compra_<? echo $NumMaterial ?>"
                                                                                                   value="<? echo htmlentities( (string)$Unidad_Compra) ?>">
                                                                                            <input type="hidden"
                                                                                                   id="Numerador_Conversion_<? echo $NumMaterial ?>"
                                                                                                   name="Numerador_Conversion_<? echo $NumMaterial ?>"
                                                                                                   value="<? echo htmlentities( (string)$Numerador_Conversion) ?>">
                                                                                            <input type="hidden"
                                                                                                   id="Denominador_Conversion_<? echo $NumMaterial ?>"
                                                                                                   name="Denominador_Conversion_<? echo $NumMaterial ?>"
                                                                                                   value="<? echo htmlentities( (string)$Denominador_Conversion) ?>">
                                                                                            <input type="hidden"
                                                                                                   id="Observaciones_<? echo $NumMaterial ?>"
                                                                                                   name="Observaciones_<? echo $NumMaterial ?>"
                                                                                                   value="<? echo htmlentities( (string)$Observaciones) ?>">

                                                                                            <? $html->Option("chLinea_" . $NumMaterial, "Check", "1", 1); ?>
                                                                                        </td>
                                                                                        <td align="right"
                                                                                            bgcolor="<?= $myColor ?>"
                                                                                            class="enlaceceldas">
                                                                                            &nbsp;<? echo $NumMaterial ?>
                                                                                            &nbsp;
                                                                                        </td>
                                                                                        <td align="left"
                                                                                            bgcolor="<?= $myColor ?>"
                                                                                            class="enlaceceldas">
                                                                                            &nbsp;<? echo $Desc_ESP ?>
                                                                                            &nbsp;
                                                                                        </td>
                                                                                        <td align="left"
                                                                                            bgcolor="<?= $myColor ?>"
                                                                                            class="enlaceceldas">
                                                                                            &nbsp;<? echo $Desc_ESP ?>
                                                                                            &nbsp;
                                                                                        </td>
                                                                                        <td align="center"
                                                                                            bgcolor="<?= $myColor ?>"
                                                                                            class="enlaceceldas">
                                                                                            &nbsp;<? echo $Estatus_Material ?>
                                                                                            &nbsp;
                                                                                        </td>
                                                                                        <td align="center"
                                                                                            bgcolor="<?= $myColor ?>"
                                                                                            class="enlaceceldas">
                                                                                            &nbsp;<? echo $Tipo_Material ?>
                                                                                            &nbsp;
                                                                                        </td>
                                                                                        <td align="center"
                                                                                            bgcolor="<?= $myColor ?>"
                                                                                            class="enlaceceldas">
                                                                                            &nbsp;<? echo $Baja ?>
                                                                                            &nbsp;
                                                                                        </td>
                                                                                        <td align="center"
                                                                                            bgcolor="<?= $myColor ?>"
                                                                                            class="enlaceceldas">
                                                                                            &nbsp;<? echo $Familia_Material ?>
                                                                                            &nbsp;
                                                                                        </td>
                                                                                        <td align="center"
                                                                                            bgcolor="<?= $myColor ?>"
                                                                                            class="enlaceceldas">
                                                                                            &nbsp;<? echo $Familia_Repro ?>
                                                                                            &nbsp;
                                                                                        </td>
                                                                                        <td align="center"
                                                                                            bgcolor="<?= $myColor ?>"
                                                                                            class="enlaceceldas">
                                                                                            &nbsp;<? echo $Marca ?>
                                                                                            &nbsp;
                                                                                        </td>
                                                                                        <td align="center"
                                                                                            bgcolor="<?= $myColor ?>"
                                                                                            class="enlaceceldas">
                                                                                            &nbsp;<? echo $Modelo ?>
                                                                                            &nbsp;
                                                                                        </td>
                                                                                        <td align="center"
                                                                                            bgcolor="<?= $myColor ?>"
                                                                                            class="enlaceceldas">
                                                                                            &nbsp;<? echo $Unidad_Medida ?>
                                                                                            &nbsp;
                                                                                        </td>
                                                                                        <td align="center"
                                                                                            bgcolor="<?= $myColor ?>"
                                                                                            class="enlaceceldas">
                                                                                            &nbsp;<? echo $Unidad_Compra ?>
                                                                                            &nbsp;
                                                                                        </td>
                                                                                        <td align="center"
                                                                                            bgcolor="<?= $myColor ?>"
                                                                                            class="enlaceceldas">
                                                                                            &nbsp;<? echo $Numerador_Conversion ?>
                                                                                            &nbsp;
                                                                                        </td>
                                                                                        <td align="center"
                                                                                            bgcolor="<?= $myColor ?>"
                                                                                            class="enlaceceldas">
                                                                                            &nbsp;<? echo $Denominador_Conversion ?>
                                                                                            &nbsp;
                                                                                        </td>
                                                                                        <td align="center"
                                                                                            bgcolor="<?= $myColor ?>"
                                                                                            class="enlaceceldas">
                                                                                            &nbsp;<? echo $Observaciones ?>
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
