<?php

# generarPDF_pdfb
# Clase generarPDF_pdfb contiene todas las funciones necesarias para la interaccion con la clase generarPDF_pdfb -> Generacion de impresos
# Se incluira en las sesiones
# Diciembre 2015 David Del Río Faces

// PATHS DE LA WEB
$pathArchivo         = realpath(dirname(__FILE__));
$pathImagenesParaPDF = $pathArchivo . "/../administrador/imagenes/";

// INCLUDES DE LIBRERIAS
define('FPDF_FONTPATH', $pathClases . 'lib/pdfb/pdfb/fpdf_fpdi/font/');
require_once $pathClases . "lib/pdfb/pdfb/pdfb.php";

class generarPDF_pdfb
{

    function __construct()
    {
    } // Fin generarPDF_pdfb

    /**
     * @param $idAutofactura AUTOFACTURA PARA LA QUE GENERAR UN PDF
     * @return PDF GENERARADO AUN SIN FORMATO
     */
    function dibujaAutofactura($idAutofactura)
    {
        global $pathImagenesParaPDF;

        //CREO EL PDF
        $fpdf = new PDFB("P", "mm");

        //Margenes de las etiquetas
        $margenIzd   = 10;
        $margenTop   = 10;
        $margenDrcho = 10;
        $fpdf->SetMargins($margenIzd, $margenTop, $margenDrcho);

        //ESTABLEZCO LAS HOJAS
        $pag          = 1;
        $totalPaginas = 1;

        // AÑADO 1 PAGINA
        $fpdf->AddPage();

        // AÑADO LA IMAGEN LOGO VICARLI
        $fpdf->Image($pathImagenesParaPDF . 'logo_vicarli.jpg', 10, $alturaInicial, 40, 0, 'jpg'); //IMAGEN VICARLI

        $fpdf->SetFont('Arial', 'B', 16);
        $fpdf->Text(136, $alturaInicial + 13, "AUTOFACTURA: " . $idAutofactura);

        // AÑADO LA IMAGEN LOGO GAMESA
        $fpdf->Image($pathImagenesParaPDF . 'acciona_impresos.jpg', 245, $alturaInicial, 40, 0, 'jpg'); //IMAGEN ACCIONA

        //DEVUELVO EL PDF A FALTA DE DEVOLVERLO EN UN FORMATO U OTRO
        return $fpdf;
    }

    /**
     * @param $idAutofactura AUTOFACTURA PARA LA QUE GENERAR UN PDF
     * @param $formato FORMATO EN EL QUE GENERAR EL PDF
     * @return bool|string PDF FORMATEADO
     */
    function getImpresoAutofactura($idAutofactura, $formato)
    {
        //RECUPERO EL PDF GENERADO A FALTA DE FORMATEAR
        $impreso = $this->dibujaAutofactura($idAutofactura);

        if ($formato == 'Texto'):
            //GENERO EL FICHERO EN FORMATO TEXTO
            $ficheroTexto = $impreso->Output('NombreNoSirveParaNada', 'S');
            $impreso->closeParsers(); //CIERRO LOS PARSERS

            //DEVUELVO EL FICHERO EN FORMATO TEXTO
            return $ficheroTexto;
        elseif ($formato == 'Web'):
            //GENERO EL FICHERO EN FORMATO ESTANDAR
            $ficheroTexto = $impreso->Output('NombreNoSirveParaNada', 'I');
            $impreso->closeParsers(); //CIERRO LOS PARSERS

            //DEVUELVO EL FICHERO EN FORMATO TEXTO
            return $ficheroTexto;
        else:
            return false;
        endif;
    }
} // FIN CLASE