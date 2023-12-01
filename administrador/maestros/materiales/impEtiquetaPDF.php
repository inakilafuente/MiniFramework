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
//require_once $pathClases."lib/exportar_pdf/fpdf.php";

define('FPDF_FONTPATH', $pathClases . 'lib/pdfb/pdfb/fpdf_fpdi/font/');
require_once $pathClases . "lib/pdfb/pdfb/pdfb.php";

require_once($pathClases . 'lib/qrcode/qrcode.class.php');
function acortar($cadena){
    $valores=explode(" ",$cadena);
    if(intval($valores[0][0])>=0&&intval($valores[0][1])>0){
        return $valores[0][0].$valores[0][1];
    }else{
        return $valores[0][0].$valores[1][0];
    }
}

session_start();
include $pathRaiz . "seguridad_admin.php";

$tituloPag         = $auxiliar->traduce("Ubicaciones", $administrador->ID_IDIOMA);
$tituloNav         = $auxiliar->traduce("Maestros", $administrador->ID_IDIOMA) . " >> " . $auxiliar->traduce("Ubicaciones", $administrador->ID_IDIOMA);
$ZonaTablaPadre    = "Maestros";
$ZonaTabla         = "MaestrosUbicaciones";
$ZonaSubTablaPadre = "MaestrosSubmenuAlmacen";
$PaginaRecordar    = "ListadoMaestrosUbicaciones";

// COMPRUEBA SI TIENE PERMISOS
if ($administrador->Hayar_Permiso_Perfil('ADM_MAESTROS_UBICACIONES') < 1):
    $html->PagError("SinPermisos");
endif;

if ($referencia != ""):
    $pag  = 0;
    $fpdf = new PDFB('L', 'mm', 'A4');

    $margenIzd   = 35;
    $margenTop   = 2;
    $margenDrcho = 0;
    $fpdf->SetMargins($margenIzd, $margenTop, $margenDrcho);

    $pag = 0;
    $sql_desc="SELECT * FROM MATERIALES M WHERE REFERENCIA_SCS=$referencia";
    $result_desc = $bd->ExecSQL($sql_desc);
    $row_desc        = $bd->SigReg($result_desc);
    $sql         = "SELECT * FROM MATERIALES M JOIN FAMILIA_REPRO FR ON M.FK_FAMILIA_REPRO=FR.ID_FAMILIA_REPRO JOIN FAMILIA_MATERIAL FM ON M.FK_FAMILIA_MATERIAL=FM.ID_FAMILIA_MATERIAL WHERE REFERENCIA_SCS=$referencia";
    $resultLista = $bd->ExecSQL($sql);
    $row         = $bd->SigReg($resultLista);

    do {
        $fpdf->AddPage();
        $fpdf->SetFont('Arial', '', 20);

        //COMPRUEBO QUE TENGA PERMISOS SOBRE LA UBICACION



        //BUSCO EL ALMACEN DE ORIGEN
        /*
        $NotificaErrorPorEmail = "No";
        $rowAlmacenOrigen      = $bd->VerReg("ALMACEN", "ID_ALMACEN", $row->ID_ALMACEN, "No");
        unset($NotificaErrorPorEmail);

        if ($rowAlmacenOrigen == false):
            $fpdf->Text(10, 10, $auxiliar->traduce("ERROR: No se pueden obtener los datos necesarios para la impresion de etiquetas", $administrador->ID_IDIOMA));
            continue;
        endif;
        */

        //ESTABLEZCO EL TAMAÑO DE LETRA
        $tamañoLetra = 40;
        $fpdf->SetFont('Times', '', $tamañoLetra);
        //SI NO VA A CABER
        do {
            $tamañoLetra = $tamañoLetra - 10;
            $fpdf->SetFont('Times', '', $tamañoLetra);
        } while ((int)$fpdf->GetStringWidth($row->REFERENCIA_SCS) > 284);
        $valueQR=$row->REFERENCIA_SCS."/".$row->FK_FAMILIA_REPRO."/".acortar($row->ESTATUS_MATERIAL);
        //PINTAMOS EL CODIGO QR
        $qrcode = new QRcode($valueQR, 'H'); // error level : L, M, Q, H
        $qrcode->disableBorder();
        $qrcode->displayFPDF($fpdf, 180, 120, 75);

        //IMPRIMO LA REFERENCIA
        $fpdf->Text(20, 20, "REF.:");
        $fpdf->Text(55, 20, $row->REFERENCIA_SCS);
        $fpdf->Text(20, 40, "DESC:");
        $fpdf->Text(55, 40, $row_desc->DESCRIPCION_ESP);
        $fpdf->Text(55, 60, $row_desc->DESCRIPCION_ENG);
        $tamañoLetra = 30;
        $fpdf->SetFont('Times', '', $tamañoLetra);
        //SI NO VA A CABER
        do {
            $tamañoLetra = $tamañoLetra - 10;
            $fpdf->SetFont('Times', '', $tamañoLetra);
        } while ((int)$fpdf->GetStringWidth($row->NOMBRE_FAMILIA." / ".$row->NOMBRE_FAMILIA_ENG) > 284);
        $fpdf->Text(20, 90, "Familia Material / Material Family:");
        $fpdf->Text(20, 100, $row->NOMBRE_FAMILIA." / ".$row->NOMBRE_FAMILIA_ENG);
        //var_dump($tamañoLetra);
        //SI NO VA A CABER
        $tamañoLetra = 30;
        $fpdf->SetFont('Times', '', $tamañoLetra);
        do {
            $tamañoLetra = $tamañoLetra - 10;
            $fpdf->SetFont('Times', '', $tamañoLetra);
        } while ((int)$fpdf->GetStringWidth($row->REFERENCIA." - ".$row->FAMILIA_REPRO) > 284);
        //var_dump($tamañoLetra);
        $fpdf->Text(20, 110, "Familia Repro / Repro Family:");
        $fpdf->Text(20, 120, $row->REFERENCIA." - ".$row->FAMILIA_REPRO);
        $fpdf->Text(20, 130, "Estatus Material / Material Status:");
        if($row->ESTATUS_MATERIAL==null){
            $fpdf->Text(20, 140, "-"." / "."-");
        }else{
            $fpdf->Text(20, 140, $row->ESTATUS_MATERIAL." / ".$auxiliar->traduce($row->ESTATUS_MATERIAL, 'ENG'));
        }

        $fpdf->Text(20, 150, "Marca / Brand:");
        if(($row->MARCA)==null){
            $fpdf->Text(20, 160, "- / -");
        }else{
            $fpdf->Text(20, 160, $row->MARCA);
        }
        $fpdf->Text(20, 170, "Modelo / Model:");
        if(($row->MODELO)==null){
            $fpdf->Text(20, 180, "- / -");
        }else{
            $fpdf->Text(20, 180, $row->MODELO);
        }



        //IMPRIMO LA IMAGEN DE ACCIONA
        $fpdf->Image('../../imagenes/logo_acciona_energia.jpg', 180, 8, 90, 0, 'jpg'); //IMAGEN ACCIONA



        ;
        //IMPRIMO EL NOMBRE DEL CARRO

        //ESTABLEZCO EL TAMAÑO DE LETRA
        $tamañoLetra = 100;
        $fpdf->SetFont('Times', '', $tamañoLetra);
        //SI NO VA A CABER
        do {
            $tamañoLetra = $tamañoLetra - 10;
            $fpdf->SetFont('Times', '', $tamañoLetra);
        } while ((int)$fpdf->GetStringWidth($rowAlmacenOrigen->REFERENCIA) > 284);

        //IMPRIMO EL ALMACEN DE ORIGEN
        $fpdf->Text(142 - ($fpdf->GetStringWidth($rowAlmacenOrigen->REFERENCIA) / 2), 125, $rowAlmacenOrigen->REFERENCIA);

        //ESTABLEZCO EL TAMAÑO DE LETRA
        $tamañoLetra = 100;
        $fpdf->SetFont('Times', '', $tamañoLetra);
        //SI NO VA A CABER
        do {
            $tamañoLetra = $tamañoLetra - 10;
            $fpdf->SetFont('Times', '', $tamañoLetra);
        } while ((int)$fpdf->GetStringWidth($rowAlmacenOrigen->NOMBRE) > 284);

        //IMPRIMO EL NOMBRE DEL ALMACEN DE DESTINO
        $fpdf->Text(142 - ($fpdf->GetStringWidth($rowAlmacenOrigen->NOMBRE) / 2), 160, $rowAlmacenOrigen->NOMBRE);

        $pag++;
    } while ($row = $bd->SigReg($resultLista));

    $fpdf->Output();
    $fpdf->closeParsers();//AÑADIDO

else:

    $pag  = 0;
    $fpdf = new FPDF('P', 'mm', array(100, 38));

    $fpdf->SetFont('Arial', '', 30);

    $fpdf->AddPage();
//        $fpdf->Text(160, 90, "ERROR:");
//        $fpdf->Line(10,100,406,100); //LINEA

    $fpdf->SetFont('Arial', 'B', 12);
    $fpdf->Text(10, 120, "ERROR: Debe seleccionar alguna ubicación para generar la etiqueta");
endif;


?>