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

$tituloPag         = $auxiliar->traduce("Ubicaciones", $administrador->ID_IDIOMA);
$tituloNav         = $auxiliar->traduce("Maestros", $administrador->ID_IDIOMA) . " >> " . $auxiliar->traduce("Ubicaciones", $administrador->ID_IDIOMA);
$ZonaTablaPadre    = "Maestros";
$ZonaSubTablaPadre = "MaestrosSubmenuAlmacen";
$ZonaTabla         = "MaestrosUbicaciones";

// COMPRUEBA SI TIENE PERMISOS
if ($administrador->Hayar_Permiso_Perfil('ADM_MAESTROS_UBICACIONES') < 2):
    $html->PagError("SinPermisos");
endif;

//COMPROBACIONES
$arr_tx              = array();
$i                   = 0;
$arr_tx[$i]["err"]   = $auxiliar->traduce("Archivo a Importar", $administrador->ID_IDIOMA);
$arr_tx[$i]["valor"] = $_FILES['adjunto_archivo_importacion']['name'];
$comp->ComprobarTexto($arr_tx, "CampoSinRellenar");
//FIN COMPROBACIONES

//ME CREO UNA VARIABLE CLAVE PARA QUE NO HAYA DOS ARCHIVOS CON LA MISMA CLAVE
$claveTiempo = date("YmdHis");

/******************************************************* CARGAMOS EL ARCHIVO *******************************************************/
$nombreFichero = $_FILES['adjunto_archivo_importacion']['name'];

//VARIABLE PARA SABER EL TIPO DE ARCHIVO
$arrArchivoCSV = explode(".", $nombreFichero);
$tipoArchivo   = $arrArchivoCSV[count($arrArchivoCSV) - 1];
$html->PagErrorCondicionado($tipoArchivo, "!=", "csv", "TipoArchivoNoCSV");

//CREO LA TABLA TEMPORAL PARA CARGAR EL ARCHIVO CSV
$sqlTemporal    = "CREATE TEMPORARY TABLE TMP_UBICACION_A_IMPORTAR_CSV
                                        (
                                             REF_UBICACION VARCHAR(255) NOT NULL
                                            , REF_CENTRO VARCHAR(255) NOT NULL
                                            , REF_ALMACEN VARCHAR(255) NOT NULL
                                            , ID INT(11) UNSIGNED NOT NULL AUTO_INCREMENT
                                            , PRIMARY KEY (ID)
                                        )";
$resultTemporal = $bd->ExecSQL($sqlTemporal);

//GRABO EL ARCHIVO A IMPORTAR

$RutayFichArchivo = $path_raiz . "documentos/ubicacion/" . "TEMP_" . $claveTiempo . "_ARCHIVO_IMPORTADO_" . $nombreFichero;
$resultFoto       = $html->CopiarAdjunto($adjunto_archivo_importacion, $RutayFichArchivo);
$html->PagErrorCondicionado($resultFoto, "==", "Error", "ErrorCopiarFichero");

//CARGO LOS DATOS EN LA TABLA TEMPORAL

if ($administrador->FMTO_CSV == "EUROPEO"):
    $csv_sep = ";";
else:
    $csv_sep = ",";
endif;

$sqlCargarDatos = "LOAD DATA " . (MAQUINA_CODIGO_MISMA_MAQUINA_BD == false ? "LOCAL" : "") . " INFILE '$RutayFichArchivo' INTO TABLE TMP_UBICACION_A_IMPORTAR_CSV FIELDS TERMINATED BY '$csv_sep' ENCLOSED BY '\"' LINES TERMINATED BY '\r\n' IGNORE 1 LINES";
$bd->ExecSQL($sqlCargarDatos);

$arrValores = array();
$k          = 0;

//COMPRUEBO LOS DATOS IMPORTADOS
$sql    = "SELECT * FROM TMP_UBICACION_A_IMPORTAR_CSV";
$result = $bd->ExecSQL($sql);

while ($row = $bd->SigReg($result)):

    //VARIABLE PARA SABER SI HAY ERROR EN LINEA
    $errorLinea = false;

    //OBTENENGO VALORES
    $refUbicacion = trim($row->REF_UBICACION);
    $refCentro    = trim($row->REF_CENTRO);
    $refAlmacen   = trim($row->REF_ALMACEN);
    $precioFijo   = "No";
    $baja         = "No";
    $esTipoSector = "No";

    $arrValores[$k]['REF_UBICACION']  = strtoupper($refUbicacion);
    $arrValores[$k]['REF_CENTRO']     = $refCentro;
    $arrValores[$k]['REF_ALMACEN']    = $refAlmacen;
    $arrValores[$k]['PRECIO_FIJO']    = $precioFijo;
    $arrValores[$k]['BAJA']           = $baja;
    $arrValores[$k]['ES_TIPO_SECTOR'] = $esTipoSector;
    $k++;

endwhile;

$arrValores = urlencode(serialize($arrValores));
header("location: ficha_importacion_preventivo_paso2.php?valores=$arrValores");
