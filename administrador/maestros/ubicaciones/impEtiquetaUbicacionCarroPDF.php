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

//LISTA DE UBICACIONES MARCADAS
$lista_id_ubicacion = "";

if ($chSelTodos == 1): //CHECK TODOS MARCADO
    $result = $bd->ExecSQL($sql);
    while ($row = $bd->SigReg($result)):
        $lista_id_ubicacion = $lista_id_ubicacion . $coma . $row->ID_UBICACION;
        $coma               = ",";
    endwhile;
else: //SELECCIONO LOS CHECK MARCADO
    foreach ($_POST as $key => $valor):
        if ((substr( (string) $key, 0, 8) == "chSelec_") && ($valor == 1)):
            $lista_id_ubicacion = $lista_id_ubicacion . $coma . substr( (string) $key, 8);
            $coma               = ",";
        endif;
    endforeach;
endif;

if ($lista_id_ubicacion != ""):
    $pag  = 0;
    $fpdf = new PDFB('L', 'mm', 'A4');

    $margenIzd   = 35;
    $margenTop   = 2;
    $margenDrcho = 0;
    $fpdf->SetMargins($margenIzd, $margenTop, $margenDrcho);

    $pag = 0;

    $sql         = "SELECT * FROM UBICACION WHERE ID_UBICACION IN ($lista_id_ubicacion)";
    $resultLista = $bd->ExecSQL($sql);
    $row         = $bd->SigReg($resultLista);

    do {
        $fpdf->AddPage();
        $fpdf->SetFont('Arial', '', 20);

        //COMPRUEBO QUE TENGA PERMISOS SOBRE LA UBICACION
        if ($administrador->comprobarUbicacionPermiso($row->ID_UBICACION, "Lectura") == false):
            $fpdf->SetFont('Arial', '', 15);
            $fpdf->Text(10, 10, $auxiliar->traduce("Error", $administrador->ID_IDIOMA) . ": " . $auxiliar->traduce("No tiene permisos para realizar esta operación en esta subzona", $administrador->ID_IDIOMA));
            continue;
        endif;


        //BUSCO EL ALMACEN DE ORIGEN
        $NotificaErrorPorEmail = "No";
        $rowAlmacenOrigen      = $bd->VerReg("ALMACEN", "ID_ALMACEN", $row->ID_ALMACEN, "No");
        unset($NotificaErrorPorEmail);

        if ($rowAlmacenOrigen == false):
            $fpdf->Text(10, 10, $auxiliar->traduce("ERROR: No se pueden obtener los datos necesarios para la impresion de etiquetas", $administrador->ID_IDIOMA));
            continue;
        endif;

        //PINTAMOS EL CODIGO QR
        $qrcode = new QRcode($row->UBICACION, 'H'); // error level : L, M, Q, H
        $qrcode->disableBorder();
        $qrcode->displayFPDF($fpdf, 125, 10, 45);


        //IMPRIMO LA IMAGEN DE ACCIONA
        $fpdf->Image('../../imagenes/logo_acciona_energia.jpg', 10, 3, 40, 0, 'jpg'); //IMAGEN ACCIONA

        //ESTABLEZCO EL TAMAÑO DE LETRA
        $tamañoLetra = 100;
        $fpdf->SetFont('Times', '', $tamañoLetra);
        //SI NO VA A CABER
        do {
            $tamañoLetra = $tamañoLetra - 10;
            $fpdf->SetFont('Times', '', $tamañoLetra);
        } while ((int)$fpdf->GetStringWidth($row->UBICACION) > 284);

        //IMPRIMO EL NOMBRE DEL CARRO
        $fpdf->Text(142 - ($fpdf->GetStringWidth($row->UBICACION) / 2), 85, $row->UBICACION);

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