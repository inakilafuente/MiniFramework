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

//IMPORTAMOS LA CLASE PARA LEER EXCEL
require_once $pathClases . "lib/PHPExcel/Classes/PHPExcel.php";

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

//COMPROBACIONES
$arr_tx              = array();
$i                   = 0;
$arr_tx[$i]["err"]   = $auxiliar->traduce("Archivo a Importar (xlsx)", $administrador->ID_IDIOMA);
$arr_tx[$i]["valor"] = $_FILES['adjunto_archivo_importacion']['name'];
$comp->ComprobarTexto($arr_tx, "CampoSinRellenar");
//FIN COMPROBACIONES

//ME CREO UNA VARIABLE CLAVE PARA QUE NO HAYA DOS ARCHIVOS CON LA MISMA CLAVE
$claveTiempo = date("YmdHis");

//******************************************************* CARGAMOS EL ARCHIVO *******************************************************//
$nombreFichero = $_FILES['adjunto_archivo_importacion']['name'];

//VARIABLE PARA SABER EL TIPO DE ARCHIVO
$arrArchivoXLSX = explode(".", (string)$nombreFichero);
$tipoArchivo    = $arrArchivoXLSX[count( (array)$arrArchivoXLSX) - 1];

$html->PagErrorCondicionado($tipoArchivo, "!=", "xlsx", "TipoArchivoNoXLSX");

//GRABO EL ARCHIVO A IMPORTAR
//$nombreDoc        = "TEMP_" . $claveTiempo . "_ARCHIVO_IMPORTADO_" . $nombreFichero;
//$RutayFichArchivo = $path_raiz . "documentos/materiales/" . $nombreDoc;
/*
$resultFoto       = $html->CopiarAdjunto($adjunto_archivo_importacion, $RutayFichArchivo);
$html->PagErrorCondicionado($resultFoto, "==", "Error", "ErrorCopiarFichero");
*/

// 1. COMPROBAMOS QUE EL FICHERO EXCEL EXISTE
$RutayFichArchivo = $_FILES["adjunto_archivo_importacion"]["tmp_name"];
$nombreFichero = $RutayFichArchivo;

if (!(file_exists($nombreFichero))):
    $strError = $nombreFichero;
    $html->PagError("ArchivoNoExiste");
else:
    //AMPLIAMOS MEMORIA
    ini_set('memory_limit', '256M');

    //LEEMOS EL XLSX
    $excelReader = PHPExcel_IOFactory::createReaderForFile($nombreFichero);
    $excelObj    = $excelReader->load($nombreFichero);
    $sheet       = $excelObj->getActiveSheet();

    //VARIABLE PARA GUARDAR LAS LINEAS A GRABAR
    $arrLineasValidas = array();

    //OBTENEMOS LA ULTIMA FILA
    $ultimaFila = $sheet->getHighestRow();

    //VARIABLES PARA CONTROLAR LO MOSTRADO
    $strKo      = "";
    $indiceOK   = 0; //N�MERO DE L�NEAS CORRECTAS
    $indiceKo   = 0; //N�MERO DE L�NEAS ERR�NEAS
    $filasKo    = 0; //N�MERO TOTAL DE ERRORES
    $strAviso   = ""; //N�MERO DE L�NEAS DE AVISOS
    $filasAviso = 0; //N�MERO TOTAL DE AVISOS

    //VARIABLE PARA CONTROLAR LOS INDICES DE LAS LINEAS
    $indice = 1;

    //RECORREMOS LAS FILAS A PARTIR DE LA SEGUNDA
    for ($numFila = 2; $numFila <= $ultimaFila; $numFila++):

        //VARIABLE PARA SABER SI HAY ALGUN CAMPO REPETIDO
        $campo_repetido = false;

        //VARIABLE PARA SABER SI HAY ERROR EN LINEA
        $errorLinea = false;

        //OBTENEMOS VALORES
        $NumMaterial    = trim( (string)$sheet->getCell('A' . $numFila)->getValue());
        $Cantidad  = trim( (string)$sheet->getCell('B' . $numFila)->getValue());


        // COMPRUEBO QUE N� Material NO EST� VAC�O
        if ($NumMaterial == ""):
            $strKo .= $auxiliar->traduce("L�nea", $administrador->ID_IDIOMA) . " $indice. ";
            $strKo .= $auxiliar->traduce("El campo", $administrador->ID_IDIOMA) . " " . $auxiliar->traduce("N� Material", $administrador->ID_IDIOMA) . " " . $auxiliar->traduce("esta vac�o", $administrador->ID_IDIOMA) . ".\n";
            $filasKo++;
            $errorLinea = true;
        endif;

        // COMPRUEBO QUE CANTIDAD NO EST� VAC�O
        if ($Cantidad == ""):
            $strKo .= $auxiliar->traduce("L�nea", $administrador->ID_IDIOMA) . " $indice. ";
            $strKo .= $auxiliar->traduce("El campo", $administrador->ID_IDIOMA) . " " . $auxiliar->traduce("Cantidad", $administrador->ID_IDIOMA) . " " . $auxiliar->traduce("esta vac�o", $administrador->ID_IDIOMA) . ".\n";
            $filasKo++;
            $errorLinea = true;
        endif;

        //foreach ($arrLineasValidas as $linea):
            /*if (strtoupper( (string)$linea['INCIDENCIA_SISTEMA_TIPO']) == strtoupper( (string)$incidenciaSistemaTipo) && strtoupper( (string)$linea['INCIDENCIA_SISTEMA_TIPO_ENG']) == strtoupper( (string)$incidenciaSistemaTipoEng) && strtoupper( (string)$linea['BAJA']) == strtoupper( (string)$baja)):
                $strKo .= $auxiliar->traduce("L�nea", $administrador->ID_IDIOMA) . " $indice. ";
                $strKo .= $auxiliar->traduce("El registro est� duplicado en los datos introducidos", $administrador->ID_IDIOMA) . ".\n";
                $filasKo++;
                $campo_repetido = true;
            endif;*/

        //endforeach;

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
            $arrLineasValidas[$numFila]['NumMaterial']   = $NumMaterial;
            $arrLineasValidas[$numFila]['Cantidad']   = $Cantidad;

            //A�ADIMOS 1 AL N�MERO DE L�NEAS V�LIDAS
            $indiceOK++;

            //DATOS CORRECTOS
            if ($rowRepetido == true):
                $strAviso .= $auxiliar->traduce("L�nea", $administrador->ID_IDIOMA) . " $indice. ";
                $strAviso .= $auxiliar->traduce("El registro", $administrador->ID_IDIOMA) . " " . $auxiliar->traduce("ya existe y se actualizar�", $administrador->ID_IDIOMA) . ".\n";
                $filasAviso++;
            endif;
        else:
            //A�ADIMOS 1 AL N�MERO DE L�NEAS ERR�NEAS
            $indiceKo++;
        endif;

        //INCREMENTO LA LINEA
        $indice = $indice + 1;
        //var_dump($NumMaterial,$Cantidad);
    endfor;
endif;
$html->PagErrorCondicionado($ultimaFila, "==", "1", "ArchivoVacio");
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
<FORM NAME="FormSelect" ACTION="ficha_importacion_AGM_accion.php" METHOD="POST" style="margin-bottom:0;"
      enctype="multipart/form-data">
    <input type="hidden" name="claveTiempo" value="<? echo $claveTiempo; ?>">
    <input type="hidden" name="idMaterial" value="<? echo $idMaterial; ?>">
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
                                                                                    title="<?= $auxiliar->traduce("N� Material", $administrador->ID_IDIOMA) ?>"><? echo $auxiliar->traduce("N� Material", $administrador->ID_IDIOMA) ?></td>
                                                                                <td height="19" bgcolor="#2E8AF0"
                                                                                    class="blanco"
                                                                                    title="<?= $auxiliar->traduce("Cantidad", $administrador->ID_IDIOMA) ?>"><? echo $auxiliar->traduce("Cantidad", $administrador->ID_IDIOMA) ?></td>
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
                                                                            <??>
                                                                                <? foreach ($arrLineasValidas as $indice => $arrValores): ?>
                                                                                    <?
                                                                                    //COLOR DE LA FILA
                                                                                    if ($i % 2 == 0) $myColor = "#B3C7DA";
                                                                                    else $myColor = "#AACFF9";

                                                                                    //RECUPERO LOS VALORES IMPORTADOS
                                                                                    $NumMaterial   = $arrValores['NumMaterial'];
                                                                                    $Cantidad   = $arrValores['Cantidad'];
                                                                                    //var_dump($NumMaterial);
                                                                                    //var_dump($Cantidad);
                                                                                    $incidenciaSistemaTipo   = $arrValores['INCIDENCIA_SISTEMA_TIPO'];
                                                                                    $incidenciaSistemaTipoEng = $arrValores['INCIDENCIA_SISTEMA_TIPO_ENG'];
                                                                                    $baja  = $arrValores['BAJA'];
                                                                                    ?>

                                                                                    <tr>
                                                                                        <td height="18" align="center"
                                                                                            bgcolor="<?= $myColor ?>"
                                                                                            class="enlaceceldas">
                                                                                            <input type="hidden"
                                                                                                   id="txCopiarPegar"
                                                                                                   name="txCopiarPegar"
                                                                                                   value="0">
                                                                                            <input type="hidden"
                                                                                                   id="NumMaterial_<? echo $NumMaterial ?>"
                                                                                                   name="NumMaterial_<? echo $NumMaterial ?>"
                                                                                                   value="<? echo htmlentities( (string)$NumMaterial) ?>">
                                                                                            <input type="hidden"
                                                                                                   id="<Cantidad_><? echo $NumMaterial ?>"
                                                                                                   name="Cantidad_<? echo $NumMaterial ?>"
                                                                                                   value="<? echo htmlentities( (string)$Cantidad) ?>">

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
                                                                                            &nbsp;<? echo $Cantidad ?>
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