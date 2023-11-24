<?
// PATHS DE LA WEB
$pathRaiz   = "../../";
$pathClases = "../../../";

// INCLUDES DE LIBRERIAS PROPIAS
require_once $pathClases . "lib/basedatos.php";
require_once $pathClases . "lib/administrador.php";
require_once $pathClases . "lib/material.php";
require_once $pathClases . "lib/auxiliar.php";
require_once $pathClases . "lib/html.php";

session_start();
//include $pathRaiz . "seguridad_admin.php";

//COMPROBAMOS QUE LA SQL TRAIGA DATOS
$html->PagErrorCondicionado($sql, "==", "", "ConsultaSQLNoEjecutadaExportarExcel");

// LIBRERIAS RELATIVAS A LA EXPORTACION A EXCEL
require_once($pathClases . "lib/PHPExcel/Classes/PHPExcel.php");

//FUNCIONES
function HeaderingExcel($filename)
{
    header("Content-type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
    header("Content-Disposition: attachment; filename=$filename.xlsx");
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0,pre-check=0");
    header("Pragma: public");
}

//HTTP HEADERS
HeaderingExcel($nombre_fichero);

//CACHEADO DE CELDAS
$cacheMethod = PHPExcel_CachedObjectStorageFactory::cache_in_memory;
PHPExcel_Settings::setCacheStorageMethod($cacheMethod);
$cacheSettings = array('memoryCacheSize' => '8MB');
ini_set('memory_limit', '2048M');
set_time_limit(1200);

//PARA LIMPIAR EL TEXTO
function escribirTexto($string)
{
    global $auxiliar;
    return $auxiliar->to_utf8($string);
}

//CREA LIBRO
$objPHPExcel = new PHPExcel();

$worksheet1 = $objPHPExcel->getActiveSheet();
$worksheet1->setTitle("PEP Construccion");

//ESCRIBE TITULOS
//LINEA - ROW
$i = 1;
//COLUMNA
$col = 0;

//PRIMERA FILA DE COLUMNAS RELLENO BLANCO Y BORDE NEGRITA (A1-J1)
$primeraFila = array(
    'borders' => array(
        'allborders' => array(
            'style' => PHPExcel_Style_Border::BORDER_THIN,
        ),
    ),
    'fill'    => array(
        'type'  => PHPExcel_Style_Fill::FILL_SOLID,
        'color' => array('rgb' => 'ee5b4a')
    ),
    "font"    => array(
        "bold"  => true,
        'color' => array('rgb' => 'ffffff')
    ),
);

$worksheet1->getStyle('A1:C1')->applyFromArray($primeraFila);

//DATOS CABECERA
$worksheet1->setCellValueByColumnAndRow($col, $i, escribirTexto($auxiliar->traduce("Id", $administrador->ID_IDIOMA)));
$col++;
$worksheet1->setCellValueByColumnAndRow($col, $i, escribirTexto($auxiliar->traduce("Descripcion PEP", $administrador->ID_IDIOMA)));
$col++;
$worksheet1->setCellValueByColumnAndRow($col, $i, escribirTexto($auxiliar->traduce("Baja", $administrador->ID_IDIOMA)));

//EJECUTAMOS LA SQL
$result = $bd->ExecSQL($sql);

//DEFINIMOS EL NUMERO DE COLUMNAS AL IGUAL QUE PINTAREMOS
$nCols = 3;
foreach (range(0, $nCols) as $col) {
    $objPHPExcel->getActiveSheet()->getColumnDimensionByColumn($col)->setAutoSize(true);
}
//AUTOAJUSTA LAS COLUMNAS

//AÑADIMOS LAS FILAS DEL CSV
$i = 2;
while ($row = $bd->SigReg($result)):
    $col = 0;

    $worksheet1->setCellValueByColumnAndRow($col, $i, escribirTexto($row->ID_PEP_CONSTRUCCION));
    $col++;
    $worksheet1->setCellValueByColumnAndRow($col, $i, escribirTexto($row->DESCRIPCION_PEP));
    $col++;
    $worksheet1->setCellValueByColumnAndRow($col, $i, escribirTexto($row->BAJA == '1' ? $auxiliar->traduce("Si", $administrador->ID_IDIOMA) : $auxiliar->traduce("No", $administrador->ID_IDIOMA)));
    $i++;
endwhile;

// CREO EL WRITER Y EXPORTO EL ARCHIVO CREADO
$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
$objWriter->save('php://output');
?>