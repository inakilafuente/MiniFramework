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


$formatot_AGM =& $workbook->add_format();
$formatot_AGM->set_size(9);
$formatot_AGM->set_align('center');
$formatot_AGM->set_color('black');
$formatot_AGM->set_pattern();
$formatot_AGM->set_fg_color('41');
$formatot_AGM->set_bold();
$formatot_AGM->set_border(1);
$formatot_AGM->set_bg_color('red');

$formato_center =& $workbook->add_format();
$formato_center->set_align('center');

//CONSTRUYE SELECT
$sqlFinal = stripslashes( (string)$sql);
$result   = $bd->ExecSQL($sqlFinal);

//ESCRIBE TITULOS
$i = 0;
$col = 0;

$worksheet1->write_string($i, $col, $auxiliar->traduce("Nº material AGM", $administrador->ID_IDIOMA), $formatot);
$worksheet1->set_column(0, $col, 20);
$col = $col + 1;
$worksheet1->write_string($i, $col, $auxiliar->traduce("Descripcion Material", $administrador->ID_IDIOMA), $formatot);
$worksheet1->set_column(0, $col, 20);
$col = $col + 1;
$worksheet1->write_string($i, $col, $auxiliar->traduce("Estatus Material", $administrador->ID_IDIOMA), $formatot);
$worksheet1->set_column(0, $col, 30);
$col = $col + 1;
$worksheet1->write_string($i, $col, $auxiliar->traduce("Tipo Material", $administrador->ID_IDIOMA), $formatot);
$worksheet1->set_column(0, $col, 20);
$col = $col + 1;
$worksheet1->write_string($i, $col, $auxiliar->traduce("Familia Material", $administrador->ID_IDIOMA), $formatot);
$worksheet1->set_column(0, $col, 30);
$col = $col + 1;
$worksheet1->write_string($i, $col, $auxiliar->traduce(" Familia Repro", $administrador->ID_IDIOMA), $formatot);
$worksheet1->set_column(0, $col, 20);
$col = $col + 1;
$worksheet1->write_string($i, $col, $auxiliar->traduce("Marca", $administrador->ID_IDIOMA), $formatot);
$worksheet1->set_column(0, $col, 30);
$col = $col + 1;
$worksheet1->write_string($i, $col, $auxiliar->traduce("Modelo", $administrador->ID_IDIOMA), $formatot);
$worksheet1->set_column(0, $col, 30);
$col = $col + 1;
$worksheet1->write_string($i, $col, $auxiliar->traduce("Unidad Manipulación", $administrador->ID_IDIOMA), $formatot);
$worksheet1->set_column(0, $col, 30);
$col = $col + 1;
$worksheet1->write_string($i, $col, $auxiliar->traduce("Divisibilidad", $administrador->ID_IDIOMA), $formatot);
$worksheet1->set_column(0, $col, 10);
$col = $col + 1;
$worksheet1->write_string($i, $col, $auxiliar->traduce("Observaciones", $administrador->ID_IDIOMA), $formatot);
$worksheet1->set_column(0, $col, 15);
$col = $col + 1;
$worksheet1->write_string($i, $col, $auxiliar->traduce("Baja", $administrador->ID_IDIOMA), $formatot);
$worksheet1->set_column(0, $col, 10);

$col=$col+1;
//MATERIAL AGM

$worksheet1->write_string($i, $col, $auxiliar->traduce("Nº material", $administrador->ID_IDIOMA), $formatot_AGM);
$worksheet1->set_column(0, $col, 20);
$col = $col + 1;
$worksheet1->write_string($i, $col, $auxiliar->traduce("Descripcion Material", $administrador->ID_IDIOMA), $formatot_AGM);
$worksheet1->set_column(0, $col, 20);
$col = $col + 1;
$worksheet1->write_string($i, $col, $auxiliar->traduce("Estatus Material", $administrador->ID_IDIOMA), $formatot_AGM);
$worksheet1->set_column(0, $col, 30);
$col = $col + 1;
$worksheet1->write_string($i, $col, $auxiliar->traduce("Tipo Material", $administrador->ID_IDIOMA), $formatot_AGM);
$worksheet1->set_column(0, $col, 20);
$col = $col + 1;
$worksheet1->write_string($i, $col, $auxiliar->traduce("Familia Material", $administrador->ID_IDIOMA), $formatot_AGM);
$worksheet1->set_column(0, $col, 30);
$col = $col + 1;
$worksheet1->write_string($i, $col, $auxiliar->traduce(" Familia Repro", $administrador->ID_IDIOMA), $formatot_AGM);
$worksheet1->set_column(0, $col, 20);
$col = $col + 1;
$worksheet1->write_string($i, $col, $auxiliar->traduce("Marca", $administrador->ID_IDIOMA), $formatot_AGM);
$worksheet1->set_column(0, $col, 30);
$col = $col + 1;
$worksheet1->write_string($i, $col, $auxiliar->traduce("Modelo", $administrador->ID_IDIOMA), $formatot_AGM);
$worksheet1->set_column(0, $col, 30);
$col = $col + 1;
$worksheet1->write_string($i, $col, $auxiliar->traduce("Unidad Manipulación", $administrador->ID_IDIOMA), $formatot_AGM);
$worksheet1->set_column(0, $col, 30);
$col = $col + 1;
$worksheet1->write_string($i, $col, $auxiliar->traduce("Divisibilidad", $administrador->ID_IDIOMA), $formatot_AGM);
$worksheet1->set_column(0, $col, 10);
$col = $col + 1;
$worksheet1->write_string($i, $col, $auxiliar->traduce("Observaciones", $administrador->ID_IDIOMA), $formatot_AGM);
$worksheet1->set_column(0, $col, 15);
$col = $col + 1;
$worksheet1->write_string($i, $col, $auxiliar->traduce("Baja", $administrador->ID_IDIOMA), $formatot_AGM);
$worksheet1->set_column(0, $col, 10);



$sql = "SELECT ID_MATERIAL  FROM MATERIAL M where MATERIAL_AGM =1";
$result=$bd->ExecSQL($sql);

//ESCRIBE RESULTADOS
$i = 1;
while ($row = $bd->SigReg($result)):
    $sql_AGM = "SELECT MATERIAL_COMPONENTE  FROM MATERIAL_COMPONENTE_AGM where MATERIAL_AGM ='".$row->ID_MATERIAL."'";
    $result_AGM=$bd->ExecSQL($sql_AGM);
    while($row_agm=$bd->SigReg($result_AGM)):
        $col = 0;
        $rowInicial = $bd->VerReg("MATERIAL", "ID_MATERIAL", $row->ID_MATERIAL);
        $worksheet1->write_string($i, $col, $rowInicial->REFERENCIA_SCS);
        $col = $col + 1;
        $row_desc = $bd->VerReg("MATERIAL", "ID_MATERIAL", $row->ID_MATERIAL);
        if(($administrador->ID_IDIOMA)=='ESP'):
            $worksheet1->write_string($i, $col, $row_desc->DESCRIPCION_ESP);
        elseif(($administrador->ID_IDIOMA)=='ENG'):
            $worksheet1->write_string($i, $col, $row_desc->DESCRIPCION_ENG);
        endif;
        $col = $col + 1;
        $worksheet1->write_string($i, $col, $rowInicial->ESTATUS_MATERIAL);
        $col = $col + 1;
        $worksheet1->write_string($i, $col, $rowInicial->TIPO_MATERIAL);
        $col = $col + 1;
        $rowFinal = $bd->VerReg("FAMILIA_MATERIAL", "ID_FAMILIA_MATERIAL", $rowInicial->ID_FAMILIA_REPRO);
        $worksheet1->write_string($i, $col, $rowFinal->NOMBRE_FAMILIA);
        $col = $col + 1;
        $rowFinal = $bd->VerReg("FAMILIA_REPRO", "ID_FAMILIA_REPRO", $rowInicial->ID_FAMILIA_REPRO);
        $worksheet1->write_string($i, $col, $rowFinal->REFERENCIA . "- ".$rowFinal->FAMILIA_REPRO);
        $col = $col + 1;
        $worksheet1->write_string($i, $col, $rowInicial->MARCA);
        $col = $col + 1;
        $worksheet1->write_string($i, $col, $rowInicial->MODELO);
        $col = $col + 1;
        $rowFinal = $bd->VerReg("UNIDAD", "ID_UNIDAD", $rowInicial->ID_UNIDAD_COMPRA);
        $worksheet1->write_string($i, $col, $rowFinal->UNIDAD ." ".$rowFinal->DESCRIPCION);
        $col = $col + 1;
        $worksheet1->write_string($i, $col, $rowInicial->DIVISIBILIDAD);
        $col = $col + 1;
        $worksheet1->write_string($i, $col, $rowInicial->OBSERVACIONES);
        $col = $col + 1;
        $worksheet1->write_string($i, $col, $rowInicial->BAJA);
        $col = $col + 1;
        //$i++;

        $rowInicial = $bd->VerReg("MATERIAL", "ID_MATERIAL", $row_agm->MATERIAL_COMPONENTE);
        $worksheet1->write_string($i, $col, $rowInicial->REFERENCIA_SCS);
        $col = $col + 1;
        $row_desc = $bd->VerReg("MATERIAL", "ID_MATERIAL", $row_agm->MATERIAL_COMPONENTE);
        if(($administrador->ID_IDIOMA)=='ESP'):
            $worksheet1->write_string($i, $col, $row_desc->DESCRIPCION_ESP);
        elseif(($administrador->ID_IDIOMA)=='ENG'):
            $worksheet1->write_string($i, $col, $row_desc->DESCRIPCION_ENG);
        endif;
        $col = $col + 1;
        $worksheet1->write_string($i, $col, $rowInicial->ESTATUS_MATERIAL);
        $col = $col + 1;
        $worksheet1->write_string($i, $col, $rowInicial->TIPO_MATERIAL);
        $col = $col + 1;
        $rowFinal = $bd->VerReg("FAMILIA_MATERIAL", "ID_FAMILIA_MATERIAL", $rowInicial->ID_FAMILIA_REPRO);
        $worksheet1->write_string($i, $col, $rowFinal->NOMBRE_FAMILIA);
        $col = $col + 1;
        $rowFinal = $bd->VerReg("FAMILIA_REPRO", "ID_FAMILIA_REPRO", $rowInicial->ID_FAMILIA_REPRO);
        $worksheet1->write_string($i, $col, $rowFinal->REFERENCIA . "- ".$rowFinal->FAMILIA_REPRO);
        $col = $col + 1;
        $worksheet1->write_string($i, $col, $rowInicial->MARCA);
        $col = $col + 1;
        $worksheet1->write_string($i, $col, $rowInicial->MODELO);
        $col = $col + 1;
        $rowFinal = $bd->VerReg("UNIDAD", "ID_UNIDAD", $rowInicial->ID_UNIDAD_COMPRA);
        $worksheet1->write_string($i, $col, $rowFinal->UNIDAD ." ".$rowFinal->DESCRIPCION);
        $col = $col + 1;
        $worksheet1->write_string($i, $col, $rowInicial->DIVISIBILIDAD);
        $col = $col + 1;
        $worksheet1->write_string($i, $col, $rowInicial->OBSERVACIONES);
        $col = $col + 1;
        $worksheet1->write_string($i, $col, $rowInicial->BAJA);
        $col = $col + 1;
        $i++;
        endwhile;
    endwhile;

//CIERRA HOJA EXCEL Y FIN
$workbook->close();
?>