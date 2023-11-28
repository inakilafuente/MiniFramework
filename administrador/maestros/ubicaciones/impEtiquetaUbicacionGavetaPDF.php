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

        if (strlen( (string)$row->UBICACION) > 20):
            $fpdf->Text(10, 10, $auxiliar->traduce("ERROR: No se puede generar el codigo de barras para referencias de más de 20 caracteres.", $administrador->ID_IDIOMA));
            continue;
        endif;

        //BUSCO EL ALMACEN DE ORIGEN
        $NotificaErrorPorEmail = "No";
        $rowAlmacenOrigen      = $bd->VerReg("ALMACEN", "ID_ALMACEN", $row->ID_ALMACEN, "No");
        unset($NotificaErrorPorEmail);

        //BUSCO EL CONTENEDOR DE LA GAVETA
        $NotificaErrorPorEmail = "No";
        $rowContenedor         = $bd->VerRegRest("CONTENEDOR", "ID_ALMACEN = $rowAlmacenOrigen->ID_ALMACEN AND ID_UBICACION = $row->ID_UBICACION AND TIPO = 'Gaveta'", "No");
        unset($NotificaErrorPorEmail);

        //BUSCO EL ALMACEN DE DESTINO
        $NotificaErrorPorEmail = "No";
        $rowAlmacenDestino     = $bd->VerReg("ALMACEN", "ID_ALMACEN", $rowContenedor->ID_ALMACEN_DESTINO_GAVETA, "No");
        unset($NotificaErrorPorEmail);

        //BUSCO LA RUTA EN CASO DE TENERLA
        $NotificaErrorPorEmail = "No";
        $rowRuta               = $bd->VerReg("RUTA", "ID_RUTA", $rowContenedor->ID_RUTA, "No");
        unset($NotificaErrorPorEmail);

        //BUSCO LA SUBRUTA EN CASO DE TENERLA
        /*$NotificaErrorPorEmail = "No";
        $rowSubRuta            = $bd->VerReg("SUBRUTA", "ID_SUBRUTA", $rowContenedor->ID_SUBRUTA, "No");
        unset($NotificaErrorPorEmail);*/

        if (
            ($rowAlmacenOrigen == false) ||
            ($rowContenedor == false) ||
            ($rowAlmacenDestino == false)
        ):
            $fpdf->Text(10, 10, $auxiliar->traduce("ERROR: No se pueden obtener los datos necesarios para la impresion de etiquetas", $administrador->ID_IDIOMA));
            continue;
        endif;

        //IMPRIMO EL CODIGO DE BARRAS
        //$fpdf->BarCode($row->UBICACION, "", 88, 10, 400, 100, 0.3, 0.3, 3, 5);

        //PINTAMOS EL CODIGO QR
        $qrcode = new QRcode($row->UBICACION, 'H'); // error level : L, M, Q, H
        $qrcode->disableBorder();
        $qrcode->displayFPDF($fpdf, 125, 7, 45);

        //IMPRIMO LA IMAGEN DE ACCIONA
        $fpdf->Image('../../imagenes/logo_acciona_energia.jpg', 10, 3, 40, 0, 'jpg'); //IMAGEN ACCIONA

        //IMPRIMO LA IMAGEN DE VICARLI
        //$fpdf->Image('../../imagenes/logo_vicarli.jpg', 250, 3, 40, 0, 'jpg'); //IMAGEN VICARLI


        //ESTABLEZCO EL TAMAÑO DE LETRA
        $fpdf->SetFont('Times', '', 100);

        //IMPRIMO EL ALMACEN DE ORIGEN Y DESTINO
        $nombresAlmacenes = $rowAlmacenOrigen->REFERENCIA . "-" . $rowAlmacenDestino->REFERENCIA;

        //ESTABLEZCO EL TAMAÑO DE LETRA
        $tamañoLetra = 100;
        $fpdf->SetFont('Times', '', $tamañoLetra);
        //SI NO VA A CABER
        do {
            $tamañoLetra = $tamañoLetra - 10;
            $fpdf->SetFont('Times', '', $tamañoLetra);
        } while ((int)$fpdf->GetStringWidth($nombresAlmacenes) > 284);

        //IMPRIMO EL ALMACEN DE ORIGEN Y DESTINO
        $fpdf->Text(142 - ($fpdf->GetStringWidth($nombresAlmacenes) / 2), 80, $nombresAlmacenes);

        //ESTABLEZCO EL TAMAÑO DE LETRA
        $tamañoLetra = 100;
        $fpdf->SetFont('Times', '', $tamañoLetra);
        //SI NO VA A CABER
        do {
            $tamañoLetra = $tamañoLetra - 10;
            $fpdf->SetFont('Times', '', $tamañoLetra);
        } while ((int)$fpdf->GetStringWidth($rowAlmacenDestino->NOMBRE) > 284);

        //IMPRIMO EL ALMACEN DESTINO
        $fpdf->Text(142 - ($fpdf->GetStringWidth($rowAlmacenDestino->NOMBRE) / 2), 110, $rowAlmacenDestino->NOMBRE);


        //ESTABLEZCO EL TAMAÑO DE LETRA
        $fpdf->SetFont('Times', '', 40);

        //IMPRIMO EL TEXTO PASILLO
        $fpdf->Text(57, 135, strtoupper( (string)$auxiliar->traduce("Pasillo", $administrador->ID_IDIOMA)) . ":");

        //ESTABLEZCO EL TAMAÑO DE LETRA
        $fpdf->SetFont('Times', '', 60);

        //IMPRIMO EL PASILLO
        $fpdf->Text(122, 135, $rowContenedor->GAVETA_PASILLO);

        //ESTABLEZCO EL TAMAÑO DE LETRA
        $fpdf->SetFont('Times', '', 40);

        //IMPRIMO EL TEXTO NIVEL
        $fpdf->Text(170, 135, strtoupper( (string)$auxiliar->traduce("Nivel", $administrador->ID_IDIOMA)) . ":");

        //ESTABLEZCO EL TAMAÑO DE LETRA
        $fpdf->SetFont('Times', '', 60);

        //IMPRIMO EL NIVEL
        $fpdf->Text(219, 135, $rowContenedor->GAVETA_PROFUNDIDAD);


        //ESTABLEZCO EL TAMAÑO DE LETRA
        $fpdf->SetFont('Times', '', 20);

        //IMPRIMO EL TEXTO RUTA
        $fpdf->Text(25, 160, strtoupper( (string)$auxiliar->traduce("Ruta", $administrador->ID_IDIOMA)) . ":");

        //ESTABLEZCO EL TAMAÑO DE LETRA
        $fpdf->SetFont('Times', '', 40);

        //IMPRIMO EL TEXTO RUTA
        $fpdf->Text(95, 160, ($rowRuta == false ? '' : $rowRuta->RUTA));

        //ESTABLEZCO EL TAMAÑO DE LETRA
        $fpdf->SetFont('Times', '', 20);

        //IMPRIMO EL TEXTO SUBRUTA
        //$fpdf->Text(25, 180, strtoupper($auxiliar->traduce("Subruta", $administrador->ID_IDIOMA)) . ":");

        //ESTABLEZCO EL TAMAÑO DE LETRA
        $fpdf->SetFont('Times', '', 40);

        //IMPRIMO EL TEXTO RUTA
        //$fpdf->Text(95, 180, ($rowSubRuta == false ? '' : $rowSubRuta->SUBRUTA));

        //BUSCAMOS SI TIENE CF CROSSDOCKING
        $sqlCFCrossDocking    = "SELECT CF.ID_CENTRO_FISICO, CF.REFERENCIA, CF.DENOMINACION_CENTRO_FISICO FROM
                                CENTRO_FISICO_SECUNDARIO CFS
                                INNER JOIN CENTRO_FISICO CF ON CF.ID_CENTRO_FISICO = CFS.ID_CENTRO_FISICO
                                WHERE CF.CROSS_DOCKING = 1 AND CFS.BAJA = 0 AND CFS.ID_CENTRO_FISICO_SECUNDARIO = $rowAlmacenDestino->ID_CENTRO_FISICO";
        $resultCFCrossDocking = $bd->ExecSQL($sqlCFCrossDocking);
        if ($bd->NumRegs($resultCFCrossDocking) > 0):
            //OBTENEMOS LA ROW
            $rowCFCrossDocking = $bd->SigReg($resultCFCrossDocking);

            //ESTABLEZCO EL TAMAÑO DE LETRA
            $fpdf->SetFont('Times', '', 20);

            //IMPRIMO EL TEXTO SUBRUTA
            $fpdf->Text(25, 200, strtoupper( (string)$auxiliar->traduce("CF CrossDocking", $administrador->ID_IDIOMA)) . ":");

            //ESTABLEZCO EL TAMAÑO DE LETRA
            $fpdf->SetFont('Times', '', 40);

            //IMPRIMO EL TEXTO RUTA
            $fpdf->Text(95, 200, $rowCFCrossDocking->REFERENCIA);
        endif;


        $pag++;
    } while ($row = $bd->SigReg($resultLista));

    $fpdf->Output();
    $fpdf->closeParsers();//AÑADIDO

else:
//		$fpdf = new FPDF();
//		$fpdf->SetMargins(15,15);
//		$fpdf->AddPage();
//		$fpdf->SetFont('Arial','B',12);
//		$fpdf->Write(12,"ERROR: Debe seleccionar alguna ubicación para generar la etiqueta");
//		$fpdf->Output();

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