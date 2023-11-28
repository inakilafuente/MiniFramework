<? //print_r($_FILES);
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

//******************************************************* CARGAMOS EL ARCHIVO *******************************************************//
$nombreFichero = $_FILES['adjunto_archivo_importacion']['name'];

//VARIABLE PARA SABER EL TIPO DE ARCHIVO Y NUMERO DE CONTENEDOR/ALBARAN
$arrArchivoXLSX = explode(".", $nombreFichero);
$tipoArchivo    = $arrArchivoXLSX[count($arrArchivoXLSX) - 1];
$html->PagErrorCondicionado($tipoArchivo, "!=", "xlsx", "TipoArchivoNoXLSX");
$nombreArchivoXLS = $arrArchivoXLS[0];

//GRABO EL ARCHIVO A IMPORTAR
$RutayFichArchivo = $path_raiz . "documentos/ubicacion/" . "TEMP_" . $claveTiempo . "_ARCHIVO_IMPORTADO_" . $nombreFichero;
$resultFoto       = $html->CopiarAdjunto($adjunto_archivo_importacion, $RutayFichArchivo);
$html->PagErrorCondicionado($resultFoto, "==", "Error", "ErrorCopiarFichero");

// 1. COMPROBAMOS QUE EL FICHERO EXCEL EXISTE
$nombreFichero = $path_raiz . "documentos/ubicacion/TEMP_" . $claveTiempo . "_ARCHIVO_IMPORTADO_" . $nombreFichero;

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

    //OBTENEMOS LA ULTIMA FILA
    $ultimaFila = $sheet->getHighestRow();

    $arrValores = array();
    $k          = 0;

    //INICIALIZO $i a 2 porque la primera fila son las cabeceras y se empieza en 1 y no en 0 como el resto de lenguajes
    for ($numFila = 2; $numFila <= $ultimaFila; $numFila++):

        //OBTENGO LOS DATOS DE LA FILA
        $refUbicacion = trim($auxiliar->to_iso88591($sheet->getCell('A' . $numFila)->getValue()));
        $refCentro    = trim($auxiliar->to_iso88591($sheet->getCell('B' . $numFila)->getValue()));
        $refAlmacen   = trim($auxiliar->to_iso88591($sheet->getCell('C' . $numFila)->getValue()));

        //PREPARACION DE ALGUNOS DATOS
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

    endfor;

    $arrValores = urlencode(serialize($arrValores));

    header("location: ficha_importacion_preventivo_paso2.php?valores=$arrValores");

endif;
?>
