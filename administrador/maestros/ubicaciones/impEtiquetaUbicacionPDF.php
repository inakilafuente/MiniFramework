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
require_once $pathClases . "lib/ubicacion.php";
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

    $sql         = "SELECT * FROM UBICACION WHERE ID_UBICACION IN ($lista_id_ubicacion)";
    $resultLista = $bd->ExecSQL($sql);
    $row         = $bd->SigReg($resultLista);

    $margenIzd   = 35;
    $margenTop   = 2;
    $margenDrcho = 0;

    if ($row->TIPO_UBICACION == 'Sector'):
        $fpdf = new PDFB('P', 'mm', array(50, 40));
    else:
        $fpdf = new PDFB('P', 'mm', array(90, 50));
    endif;

    $fpdf->SetMargins($margenIzd, $margenTop, $margenDrcho);

    do {
        $pag = 0;

        if ($row->TIPO_UBICACION == 'Sector'):
            $fpdf->AddPage();
            $fpdf->SetFont('Arial', '', 20);

            //COMPRUEBO QUE TENGA PERMISOS SOBRE LA UBICACION
            if ($administrador->comprobarUbicacionPermiso($row->ID_UBICACION, "Lectura") == false):
                $fpdf->SetFont('Arial', '', 15);
                $fpdf->Text(10, 10, $auxiliar->traduce("Error", $administrador->ID_IDIOMA) . ": " . $auxiliar->traduce("No tiene permisos para realizar esta operación en esta subzona", $administrador->ID_IDIOMA));
                continue;
            endif;

            //COMPRUEBO SI LA UBICACION SIGUE EL PATRON GENERAL
            $siguePatron = fnmatch("[A-Z][0-9][0-9][0-9][0-9][0-Z]", $row->UBICACION);

            if ($siguePatron == true): //SUSTITUYO LOS " ULTIMOS DIGITOS POR $$
                $textoEtiqueta = substr( (string) $row->UBICACION, 0, 3) . "$$" . substr( (string) $row->UBICACION, -1);
            else: //LA ETIQUETA ES LA UBICACION
                $textoEtiqueta = $row->UBICACION;
            endif;

            if ($row->ID_TIPO_SECTOR != ""):
                //SE CREA UN CÓDIGO QR CON LA UBICACION Y SU TIPO DE SECTOR
                $codQR = $ubicacion->ObtenerCodigoUbicacionQR($row->UBICACION, $row->ID_TIPO_SECTOR);

                if (strlen( (string)$textoEtiqueta) < 7):
                    $fpdf->SetFont('Times', '', 20);
                elseif ((strlen( (string)$textoEtiqueta) > 6) && (strlen( (string)$textoEtiqueta) < 12)):
                    $fpdf->SetFont('Times', '', 15);
                else:
                    $fpdf->SetFont('Times', '', 10);
                endif;

                //SE IMPRIME LA UBICACION
                $fpdf->Text(25 - ($fpdf->GetStringWidth($textoEtiqueta) / 2), 6, $textoEtiqueta);
                $fpdf->SetFont('Arial', '', 10);

                //PINTAMOS EL CODIGO QR
                $qrcode = new QRcode($codQR, 'H'); // error level : L, M, Q, H
                $qrcode->disableBorder();
                $qrcode->displayFPDF($fpdf, 14, 9, 22);

                //SE IMPRIME EL TIPO SECTOR (SI LO TIENE)
                $fpdf->Text(25 - ($fpdf->GetStringWidth("Tipo Sector: " . $row->ID_TIPO_SECTOR) / 2), 36, "Tipo Sector: " . $row->ID_TIPO_SECTOR);
                $fpdf->SetFont('Arial', '', 10);
            else:
                if (strlen( (string)$textoEtiqueta) < 7):
                    $fpdf->SetFont('Times', '', 20);
                elseif ((strlen( (string)$textoEtiqueta) > 6) && (strlen( (string)$textoEtiqueta) < 12)):
                    $fpdf->SetFont('Times', '', 15);
                else:
                    $fpdf->SetFont('Times', '', 10);
                endif;

                //SE IMPRIME LA UBICACION
                $fpdf->Text(25 - ($fpdf->GetStringWidth($textoEtiqueta) / 2), 8, $textoEtiqueta);
                $fpdf->SetFont('Arial', '', 10);

                //PINTAMOS EL CODIGO QR
                $qrcode = new QRcode($textoEtiqueta, 'H'); // error level : L, M, Q, H
                $qrcode->disableBorder();
                $qrcode->displayFPDF($fpdf, 14, 12, 22);
            endif;

        else:

            $fpdf->AddPage();
            $fpdf->SetFont('Arial', '', 20);

            //COMPRUEBO QUE TENGA PERMISOS SOBRE LA UBICACION
            if ($administrador->comprobarUbicacionPermiso($row->ID_UBICACION, "Lectura") == false):
                $fpdf->SetFont('Arial', '', 15);
                $fpdf->Text(10, 10, $auxiliar->traduce("Error", $administrador->ID_IDIOMA) . ": " . $auxiliar->traduce("No tiene permisos para realizar esta operación en esta subzona", $administrador->ID_IDIOMA));
                continue;
            endif;

            if (strlen( (string)$row->UBICACION) > 20):
                $fpdf->Text(10, 10, $auxiliar->traduce("ERROR: No se puede generar el codigo de barras para referencias de más de 14 caracteres.", $administrador->ID_IDIOMA));
                continue;
            endif;

            //COMPRUEBO SI LA UBICACION SIGUE EL PATRON GENERAL
            $siguePatron = fnmatch("[A-Z][0-9][0-9][0-9][0-9][0-Z]", $row->UBICACION);

            if ($siguePatron == true): //SUSTITUYO LOS " ULTIMOS DIGITOS POR $$
                $textoEtiqueta = substr( (string) $row->UBICACION, 0, 3) . "$$" . substr( (string) $row->UBICACION, -1);
            else: //LA ETIQUETA ES LA UBICACION
                $textoEtiqueta = $row->UBICACION;
            endif;

            $fpdf->SetFont('Times', '', 10);
            $fpdf->Text(8, 16, $auxiliar->traduce("UBICACIÓN", $administrador->ID_IDIOMA) . ": ");

            if (strlen( (string)$textoEtiqueta) < 7):
                $fpdf->SetFont('Times', '', 30);
            elseif ((strlen( (string)$textoEtiqueta) > 6) && (strlen( (string)$textoEtiqueta) < 12)):
                $fpdf->SetFont('Times', '', 20);
            else:
                $fpdf->SetFont('Times', '', 15);
            endif;

            $fpdf->Text(30, 16, "$textoEtiqueta");
            $fpdf->SetFont('Arial', '', 10);

            //IMPRIMO EL CODIGO DE BARRAS
            if ($row->TIPO_UBICACION == 'Sector'):
                $textoEtiqueta = '.SCTR.' . $textoEtiqueta;
            endif;

            if (strlen( (string)$textoEtiqueta) < 23):
                $fpdf->BarCode($textoEtiqueta, "", 5, 22, 300, 80, 0.26, 0.26, 3, 5);
            else:
                //$fpdf->BarCode($textoEtiqueta, "", 5, 22, 270, 50, 0.2, 0.2, 3, 5);
                $fpdf->BarCode($textoEtiqueta, "", 5, 22, 400, 80, 0.2, 0.2, 3, 5);
            endif;

        endif;

        $pag++;
    } while ($row = $bd->SigReg($resultLista));

    $fpdf->Output();
    $fpdf->closeParsers();//AÑADIDO

else:

    $pag  = 0;
    $fpdf = new FPDF('P', 'mm', array(100, 38));

    $fpdf->SetFont('Arial', '', 30);

    $fpdf->AddPage();

    $fpdf->SetFont('Arial', 'B', 12);
    $fpdf->Text(10, 120, "ERROR: Debe seleccionar alguna ubicación para generar la etiqueta");
endif;
?>