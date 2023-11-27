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
require_once $pathClases . "lib/cliente.php";
require_once $pathClases . "lib/pedido.php";

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
$worksheet1->setTitle($nombre_fichero);

/**
 * DEFINE ESTILOS PARA LAS DISTINTAS PARTES DEL EXCEL.
 */
function defineStyles()
{
    // ESTILOS PARA LA CABECERA (PRIMERA FILA) DEL EXCEL
    $cabecera = array(
        'borders'   => array(
            'allborders' => array(
                'style' => PHPExcel_Style_Border::BORDER_THIN,
            ),
        ),
        'fill'      => array(
            'type'  => PHPExcel_Style_Fill::FILL_SOLID,
            'color' => array('rgb' => 'ee5b4a')
        ),
        "font"      => array(
            "bold"  => true,
            'color' => array('rgb' => 'ffffff')
        ),
        'alignment' => array(
            'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
        ),
    );

    return array(
        'header' => $cabecera
    );
}

//ESCRIBE TITULOS
//LINEA - ROW
$i = 1;
//COLUMNA
$col = 0;

// DATOS CABECERA
$worksheet1->setCellValueByColumnAndRow($col, $i, escribirTexto($auxiliar->traduce("ID INCIDENCIA SISTEMA TIPO", $administrador->ID_IDIOMA)));
$col++;
$worksheet1->setCellValueByColumnAndRow($col, $i, escribirTexto($auxiliar->traduce("INCIDENCIA SISTEMA TIPO", $administrador->ID_IDIOMA)));
$col++;
$worksheet1->setCellValueByColumnAndRow($col, $i, escribirTexto($auxiliar->traduce("INCIDENCIA SISTEMA TIPO ENG.", $administrador->ID_IDIOMA)));
$col++;
$worksheet1->setCellValueByColumnAndRow($col, $i, escribirTexto($auxiliar->traduce("BAJA", $administrador->ID_IDIOMA)));
$col++;

$i++; // SIGUIENTE FILA

$styles = defineStyles();

// PRIMERA FILA / CABECERA
$worksheet1->getStyle('A1:D1')->applyFromArray($styles['header']);

$nCols = 4;
foreach (range(0, $nCols) as $col) {
    $objPHPExcel->getActiveSheet()->getColumnDimensionByColumn($col)->setAutoSize(true);
}

//CONSTRUYE SELECT
$sqlFinal = stripslashes( (string)$sql);
$result   = $bd->ExecSQL($sqlFinal);

// DATOS CUERPO
while ($row = $bd->SigReg($result)):
    $col = 0;
    $worksheet1->setCellValueByColumnAndRow($col, $i, escribirTexto($row->ID_INCIDENCIA_SISTEMA_TIPO));
    $col = $col + 1;
    $worksheet1->setCellValueByColumnAndRow($col, $i, escribirTexto($row->INCIDENCIA_SISTEMA_TIPO));
    $col = $col + 1;
    $worksheet1->setCellValueByColumnAndRow($col, $i, escribirTexto($row->INCIDENCIA_SISTEMA_TIPO_ENG));
    $col = $col + 1;
    if ($row->BAJA == 0) $worksheet1->setCellValueByColumnAndRow($col, $i, escribirTexto($auxiliar->traduce("No", $administrador->ID_IDIOMA)));
    else $worksheet1->setCellValueByColumnAndRow($col, $i, escribirTexto($auxiliar->traduce("Si", $administrador->ID_IDIOMA)));

    $i++;
endwhile;

// CREO EL WRITER Y EXPORTO EL ARCHIVO CREADO
$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
$objWriter->save('php://output');
?>