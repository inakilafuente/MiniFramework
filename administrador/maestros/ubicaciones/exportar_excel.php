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
$worksheet1->write_string($i, $col, $auxiliar->traduce("Nombre Centro", $administrador->ID_IDIOMA), $formatot);
$worksheet1->set_column(0, $col, 30);
$col = $col + 1;
$worksheet1->write_string($i, $col, $auxiliar->traduce("Ref. Almacén", $administrador->ID_IDIOMA), $formatot);
$worksheet1->set_column(0, $col, 20);
$col = $col + 1;
$worksheet1->write_string($i, $col, $auxiliar->traduce("Nombre Almacén", $administrador->ID_IDIOMA), $formatot);
$worksheet1->set_column(0, $col, 30);
$col = $col + 1;
$worksheet1->write_string($i, $col, $auxiliar->traduce("Centro Físico", $administrador->ID_IDIOMA), $formatot);
$worksheet1->set_column(0, $col, 20);
$col = $col + 1;
$worksheet1->write_string($i, $col, $auxiliar->traduce("Tipo Ubicación", $administrador->ID_IDIOMA), $formatot);
$worksheet1->set_column(0, $col, 30);
$col = $col + 1;
$worksheet1->write_string($i, $col, $auxiliar->traduce("Número Categoría Ubicación", $administrador->ID_IDIOMA), $formatot);
$worksheet1->set_column(0, $col, 30);
$col = $col + 1;
$worksheet1->write_string($i, $col, $auxiliar->traduce("Categoría Ubicación", $administrador->ID_IDIOMA), $formatot);
$worksheet1->set_column(0, $col, 30);
$col = $col + 1;
$worksheet1->write_string($i, $col, $auxiliar->traduce("Clase APQ", $administrador->ID_IDIOMA), $formatot);
$worksheet1->set_column(0, $col, 10);
$col = $col + 1;
$worksheet1->write_string($i, $col, $auxiliar->traduce("Precio Fijo", $administrador->ID_IDIOMA), $formatot);
$worksheet1->set_column(0, $col, 10);
$col = $col + 1;
$worksheet1->write_string($i, $col, $auxiliar->traduce("Id. Ubicación", $administrador->ID_IDIOMA), $formatot);
$worksheet1->set_column(0, $col, 15);
$col = $col + 1;
$worksheet1->write_string($i, $col, $auxiliar->traduce("Autostore", $administrador->ID_IDIOMA), $formatot);
$worksheet1->set_column(0, $col, 10);
$col = $col + 1;
$worksheet1->write_string($i, $col, $auxiliar->traduce("Baja", $administrador->ID_IDIOMA), $formatot);
$worksheet1->set_column(0, $col, 10);

//ESCRIBE RESULTADOS
$i = 1;
while ($row = $bd->SigReg($result)):

    $col = 0;
    $worksheet1->write_string($i, $col, $row->UBICACION);
    $col = $col + 1;
    $worksheet1->write_string($i, $col, $row->REF_CENTRO);
    $col = $col + 1;
    $worksheet1->write_string($i, $col, $row->NOM_CENTRO);
    $col = $col + 1;
    $worksheet1->write_string($i, $col, $row->REF_ALMACEN);
    $col = $col + 1;
    $worksheet1->write_string($i, $col, $row->NOM_ALMACEN);
    $col = $col + 1;
    $worksheet1->write_string($i, $col, $row->REFERENCIA_CENTRO_FISICO);
    $col = $col + 1;
    $worksheet1->write_string($i, $col, ($row->TIPO_UBICACION == "" ? $auxiliar->traduce("Estándar", $administrador->ID_IDIOMA) : $auxiliar->traduce($row->TIPO_UBICACION, $administrador->ID_IDIOMA)));
    $col = $col + 1;

    //BUSCO LA CATEGORIA UBICACION
    if ($row->ID_UBICACION_CATEGORIA != NULL):
        $rowCategoriaUbicacion = $bd->VerReg("UBICACION_CATEGORIA", "ID_UBICACION_CATEGORIA", $row->ID_UBICACION_CATEGORIA);
        $worksheet1->write_string($i, $col, $row->ID_UBICACION_CATEGORIA);
        $col = $col + 1;
        $worksheet1->write_string($i, $col, $auxiliar->traduce($rowCategoriaUbicacion->NOMBRE, $administrador->ID_IDIOMA));
    else:
        $worksheet1->write_string($i, $col, "-");
        $col = $col + 1;
        $worksheet1->write_string($i, $col, "-");
    endif;
    $col = $col + 1;

    $worksheet1->write_string($i, $col, $row->CLASE_APQ);
    $col = $col + 1;

    if ($row->PRECIO_FIJO == '0'):
        $worksheet1->write_string($i, $col, $auxiliar->traduce("No", $administrador->ID_IDIOMA), $formato_center);
    elseif ($row->PRECIO_FIJO == '1'):
        $worksheet1->write_string($i, $col, $auxiliar->traduce("Si", $administrador->ID_IDIOMA), $formato_center);
    endif;
    $col = $col + 1;
    $worksheet1->write_string($i, $col, $row->ID_UBICACION);
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
    $i++;

endwhile;

//CIERRA HOJA EXCEL Y FIN
$workbook->close();
?>