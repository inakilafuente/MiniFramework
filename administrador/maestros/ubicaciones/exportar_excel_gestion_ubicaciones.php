<?php
// PATHS DE LA WEB
$pathRaiz   = "../../";
$pathClases = "../../../";

// INCLUDES DE LIBRERIAS PROPIAS
require_once $pathClases . "lib/basedatos.php";
require_once $pathClases . "lib/administrador.php";
require_once $pathClases . "lib/html.php";
require_once $pathClases . "lib/auxiliar.php";

session_start();
//include $pathRaiz . "seguridad_admin.php";

//COMPROBAMOS QUE LA SQL TRAIGA DATOS
$html->PagErrorCondicionado($sql, "==", "", "ConsultaSQLNoEjecutadaExportarExcel");

// LIBRERIAS RELATIVAS A LA EXPORTACION A EXCEL
require_once($pathClases . "lib/exportar_excel/OLEwriter.php");
require_once($pathClases . "lib/exportar_excel/BIFFwriter.php");
require_once($pathClases . "lib/exportar_excel/Worksheet.php");
require_once($pathClases . "lib/exportar_excel/Workbook.php");

//FUNCIONES
function HeaderingExcel($filename)
{
    header("Content-type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=$filename");
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0,pre-check=0");
    header("Pragma: public");
}

//HTTP HEADERS
HeaderingExcel($nombre_fichero);

//CREA LIBRO
$workbook = new Workbook("-");

//CREA LA PAGINA
$worksheet1 =& $workbook->add_worksheet($nombre_hoja);

$formatot =& $workbook->add_format();
$formatot->set_size(9);
$formatot->set_align('center');
$formatot->set_color('black');
$formatot->set_pattern();
$formatot->set_fg_color('41');
$formatot->set_bold();
$formatot->set_border(1);

$formatot2 =& $workbook->add_format();
$formatot2->set_size(9);
$formatot2->set_align('center');
$formatot2->set_color('black');
$formatot2->set_pattern();
$formatot2->set_fg_color('31');
$formatot2->set_border(1);

$formatot3 =& $workbook->add_format();
$formatot3->set_size(9);
$formatot3->set_align('center');
$formatot3->set_color('black');
$formatot3->set_pattern();
$formatot3->set_bold();
$formatot3->set_fg_color('51');
$formatot3->set_border(1);

$formato_center =& $workbook->add_format();
$formato_center->set_align('center');

//CONSTRUYE SELECT
$sqlFinal = stripslashes( (string)$sql);
$result   = $bd->ExecSQL($sqlFinal);

//ESCRIBE TITULOS
$i = 0;

$col = 0;
$worksheet1->write_string($i, $col, $auxiliar->traduce("Ubicación", $administrador->ID_IDIOMA), $formatot);
$worksheet1->set_column(0, $col, 20);
$col = $col + 1;
$worksheet1->write_string($i, $col, $auxiliar->traduce("Ref. Centro", $administrador->ID_IDIOMA), $formatot);
$worksheet1->set_column(0, $col, 20);
$col = $col + 1;
$worksheet1->write_string($i, $col, $auxiliar->traduce("Ref. Almacén", $administrador->ID_IDIOMA), $formatot);
$worksheet1->set_column(0, $col, 20);
$col = $col + 1;
$worksheet1->write_string($i, $col, $auxiliar->traduce("Categoría Ubicación", $administrador->ID_IDIOMA), $formatot);
$worksheet1->set_column(0, $col, 30);
$col = $col + 1;
$worksheet1->write_string($i, $col, $auxiliar->traduce("Descripcion", $administrador->ID_IDIOMA), $formatot);
$worksheet1->set_column(0, $col, 30);
$col = $col + 1;
$worksheet1->write_string($i, $col, $auxiliar->traduce("Precio Fijo", $administrador->ID_IDIOMA), $formatot);
$worksheet1->set_column(0, $col, 10);
$col = $col + 1;
$worksheet1->write_string($i, $col, $auxiliar->traduce("Autostore", $administrador->ID_IDIOMA), $formatot);
$worksheet1->set_column(0, $col, 10);
$col = $col + 1;
$worksheet1->write_string($i, $col, $auxiliar->traduce("Baja", $administrador->ID_IDIOMA), $formatot);
$worksheet1->set_column(0, $col, 10);
$col = $col + 1;
if ($selTipoUbicacion == 'Sector'):
    $worksheet1->write_string($i, $col, $auxiliar->traduce("Tipo Sector", $administrador->ID_IDIOMA), $formatot);
    $worksheet1->set_column(0, $col, 15);
    $col = $col + 1;
    $worksheet1->write_string($i, $col, $auxiliar->traduce("Cantidad Paneles", $administrador->ID_IDIOMA), $formatot);
    $worksheet1->set_column(0, $col, 20);
    $col = $col + 1;
    $worksheet1->write_string($i, $col, $auxiliar->traduce("Cantidad Paneles Asignados", $administrador->ID_IDIOMA), $formatot);
    $worksheet1->set_column(0, $col, 25);
    $col = $col + 1;
endif;

//ESCRIBE RESULTADOS
$i = 1;
while ($row = $bd->SigReg($result)):

    //CALCULO EL NUMERO DE PANELES ASIGNADOS
    $sqlNumPanelesAsignados    = "SELECT IF(SUM(STOCK_TOTAL) IS NULL, 0, SUM(STOCK_TOTAL)) AS NUM_PANELES_ASIGNADOS
                                FROM MATERIAL_UBICACION MU
                                INNER JOIN MATERIAL_FISICO MF ON MF.ID_MATERIAL_FISICO = MU.ID_MATERIAL_FISICO
                                WHERE MU.ACTIVO = 1 AND MU.ID_UBICACION = $row->ID_UBICACION";
    $resultNumPanelesAsignados = $bd->ExecSQL($sqlNumPanelesAsignados);
    $rowNumPanelesAsignados    = $bd->SigReg($resultNumPanelesAsignados);


    $col = 0;
    $worksheet1->write_string($i, $col, $row->UBICACION);
    $col = $col + 1;
    $worksheet1->write_string($i, $col, $row->REF_CENTRO);
    $col = $col + 1;
    $worksheet1->write_string($i, $col, $row->REF_ALMACEN);
    $col = $col + 1;

    //BUSCO LA CATEGORIA UBICACION
    if ($row->ID_UBICACION_CATEGORIA != NULL):
        $worksheet1->write_string($i, $col, $row->ID_UBICACION_CATEGORIA);
    else:
        $worksheet1->write_string($i, $col, "");
    endif;
    $col = $col + 1;
    $worksheet1->write_string($i, $col, $row->DESCRIPCION);
    $col = $col + 1;
    if ($row->PRECIO_FIJO == '0'):
        $worksheet1->write_string($i, $col, $auxiliar->traduce("No", $administrador->ID_IDIOMA), $formato_center);
    elseif ($row->PRECIO_FIJO == '1'):
        $worksheet1->write_string($i, $col, $auxiliar->traduce("Si", $administrador->ID_IDIOMA), $formato_center);
    endif;
    $col = $col + 1;
    if ($row->AUTOSTORE == '0'):
        $worksheet1->write_string($i, $col, $auxiliar->traduce("No", $administrador->ID_IDIOMA), $formato_center);
    elseif ($row->AUTOSTORE == '1'):
        $worksheet1->write_string($i, $col, $auxiliar->traduce("Si", $administrador->ID_IDIOMA), $formato_center);
    endif;
    $col = $col + 1;
    if ($row->BAJA == '0'):
        $worksheet1->write_string($i, $col, $auxiliar->traduce("No", $administrador->ID_IDIOMA), $formato_center);
    elseif ($row->BAJA == '1'):
        $worksheet1->write_string($i, $col, $auxiliar->traduce("Si", $administrador->ID_IDIOMA), $formato_center);
    endif;
    $col = $col + 1;
    if ($selTipoUbicacion == 'Sector'):
        $worksheet1->write_string($i, $col, $auxiliar->traduce("Si", $administrador->ID_IDIOMA), $formato_center);
        $col = $col + 1;
        $worksheet1->write_number($i, $col, $row->CANTIDAD_PANELES);
        $col = $col + 1;
        $worksheet1->write_number($i, $col, $rowNumPanelesAsignados->NUM_PANELES_ASIGNADOS);
        $col = $col + 1;
    endif;

    //INCREMENTO EN 1 EL NUMERO DE FILAS
    $i++;

endwhile;

//CIERRA HOJA EXCEL Y FIN
$workbook->close();
?>