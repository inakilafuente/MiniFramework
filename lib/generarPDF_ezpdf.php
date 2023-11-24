<?php

# generarPDF_ezpdf
# Clase generarPDF_ezpdf contiene todas las funciones necesarias para la interaccion con la clase generarPDF_ezpdf -> Generacion de impresos
# Se incluira en las sesiones
# Diciembre 2015 David Del Río Faces

// PATHS DE LA WEB
$pathArchivo         = realpath(dirname(__FILE__));
$pathImagenesParaPDF = $pathArchivo . "/../administrador/imagenes/";

// INCLUDES DE LIBRERIAS
//require_once $pathClases . "lib/pdfClasses/class.ezpdf.php";

class generarPDF_ezpdf
{

    function __construct()
    {
    } // Fin generarPDF_ezpdf

    //AUTOFACTURA
    /**
     * @param $idAutofactura AUTOFACTURA PARA LA QUE GENERAR UN PDF
     * @return PDF GENERARADO AUN SIN FORMATO
     */
    function dibujaAutofactura($idAutofactura)
    {
        global $pathImagenesParaPDF;
        global $auxiliar;
        global $administrador;
        global $bd;
        global $pdf;
        global $idIdiomaAutofactura;

        //BUSCO LA AUTOFACTURA
        $rowAutofactura = $bd->VerReg("AUTOFACTURA", "ID_AUTOFACTURA", $idAutofactura);

        //CREO EL PDF
        $pdf = new Cezpdf('a4', 'portrait'); //A4 y portrait por defecto  Tamaño: 595.28 x 841.89
        $pdf->selectFont($pathClases . 'lib/pdfClasses/fonts/Helvetica.afm');

        //ESTABLEZCO LOS DATOS COMUNES A TODAS LA PAGINAS
        $all = $pdf->openObject();
        $pdf->saveState();

        $pdf->setLineStyle(3);
        $pdf->line(100, 828, 575, 827); //PRIMERA LINEA NEGRA
        $pdf->addJpegFromFile($pathImagenesParaPDF . 'acciona_impresos.jpg', 20, 801, 70, 0); //LOGO ACCIONA
        $pdf->setLineStyle(3);
        $pdf->line(20, 798, 575, 798); //SEGUNDA LINEA NEGRA

        $pdf->setLineStyle(1);
        $pdf->line(20, 30, 575, 30);
        $pdfEnGeneracion->addText(20, 24, 6, $this->armoFecha());
        $pdfEnGeneracion->restoreState();
        $pdfEnGeneracion->closeObject();
        $pdfEnGeneracion->addObject($all, 'all');

        //ESTABLEZCO LA VARIABLE GLOBAL DEL PDF
        $pdf = $pdfEnGeneracion;

        //BUSCO EL PROVEEDOR
        $rowProveedor = $bd->VerReg("PROVEEDOR", "ID_PROVEEDOR", $rowAutofactura->ID_PROVEEDOR);

        //BUSCO EL PAIS DEL PROVEEDOR
        $rowPaisProveedor = $bd->VerReg("PAIS", "ID_PAIS", $rowProveedor->ID_PAIS);

        //BUSCO EL IDIOMA DEL PROVEEDOR EN FUNCION DEL PAIS DE ESTE
        $idIdiomaAutofactura = $rowPaisProveedor->IDIOMA;

        //GENERO LA CABECERA
        $this->crearCabeceraAutofactura($rowAutofactura->ID_AUTOFACTURA);

        //FINALIZO EL CONTADOR DE HOJAS
        $pdfEnGeneracion->ezStopPageNumbers(1, 1, $i);

        //DEVUELVO EL PDF A FALTA DE DEVOLVERLO EN UN FORMATO U OTRO
        return $pdfEnGeneracion;
    }

    function crearCabeceraAutofactura($idAutofactura)
    {
        global $auxiliar;
        global $bd;
        global $pdf;
        global $idIdiomaAutofactura;

        //BUSCO LA AUTOFACTURA
        $rowAutofactura = $bd->VerReg("AUTOFACTURA", "ID_AUTOFACTURA", $idAutofactura);

        $pdf->setColor(0, 0, 100); //COLOR AZUL
        $pdf->addTextWrap(80, 807, 400, 13, '<b>' . strtr(strtoupper($auxiliar->traduce("Informe Certificacion", $idIdiomaAutofactura)), 'ó', 'Ó') . '</b>', 'center');
        $pdf->setColor(0, 0, 0); //COLOR NEGRO

        //PARA IMPRIMIR EL CODIGO DE BARRAS
        $rutaImagen = "/tmp/" . $rowAutofactura->ID_AUTOFACTURA . ".png";

        $a  = generateBarCodePNG($rowAutofactura->ID_AUTOFACTURA, "", 150, 52, 500, 1, 1);
        $fp = fopen($rutaImagen, "w"); //Crear el manejador
        fwrite($fp, $a); //Escribir el fichero
        fclose($fp); //Cerrar el manejador
        $pdf->addPngFromFile($rutaImagen, 420, 782, 150, 50); //Llamar a la función correspondiente de la clase pdf para añadir imagenes
        unlink($rutaImagen); //Borrar la imagen
        //FIN PARA IMPRIMIR EL CODIGO DE BARRAS


        //DEFINO LA ALTURA
        $fila = 780;

        //DEFINO EL TAMAÑO LETRA POR DEFECTO
        $tamano_letra                   = 8;
        $tamano_letra_titulo_matriculas = 9;
        $tamano_letra_titulo_texto      = 10;


        //BUSCO EL PROVEEDOR
        $rowProveedor = $bd->VerReg("PROVEEDOR", "ID_PROVEEDOR", $rowAutofactura->ID_PROVEEDOR);

        //BUSCO LA DIRECCION DEL PROVEEDOR
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowDireccionProveedor            = $bd->VerReg("DIRECCION", "ID_PROVEEDOR", $rowProveedor->ID_PROVEEDOR, "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);


        //BUSCO LA SOCIEDAD CONTRATANTE
        if ($rowAutofactura->REFERENCIA_FACTURACION != ""):
            if ($rowAutofactura->TIPO_TRANSPORTE_FACTURACION == "OT"):
                $sqlSociedadContratante    = "SELECT S.ID_SOCIEDAD,S.CIF
                                        FROM ORDEN_CONTRATACION OC
                                        INNER JOIN ORDEN_TRANSPORTE OT ON OT.ID_ORDEN_TRANSPORTE = OC.ID_ORDEN_TRANSPORTE
                                        INNER JOIN CENTRO C ON C.ID_CENTRO = OT.ID_CENTRO_CONTRATANTE
                                        INNER JOIN SOCIEDAD S ON S.ID_SOCIEDAD = C.ID_SOCIEDAD
                                        WHERE OC.REFERENCIA_FACTURACION='$rowAutofactura->REFERENCIA_FACTURACION' AND OC.BAJA = 0";
                $resultSociedadContratante = $bd->ExecSQL($sqlSociedadContratante);
            else:
                $sqlSociedadContratante    = "SELECT S.ID_SOCIEDAD,S.CIF
                                        FROM ORDEN_CONTRATACION OC
                                        INNER JOIN ORDEN_TRANSPORTE OT ON OT.ID_ORDEN_TRANSPORTE = OC.ID_ORDEN_TRANSPORTE
                                        INNER JOIN ORDEN_TRANSPORTE_CONSTRUCCION_AMPLIACION OTCA ON OTCA.ID_ORDEN_TRANSPORTE = OT.ID_ORDEN_TRANSPORTE
                                        INNER JOIN SOCIEDAD S ON S.ID_SOCIEDAD = OTCA.ID_SOCIEDAD_CONTRATANTE
                                        WHERE OC.REFERENCIA_FACTURACION='$rowAutofactura->REFERENCIA_FACTURACION' AND OC.BAJA = 0 AND OT.BAJA = 0";
                $resultSociedadContratante = $bd->ExecSQL($sqlSociedadContratante);
            endif;

            $rowSociedadContratante = $bd->SigReg($resultSociedadContratante);

            //BUSCO LA DIRECCION DE LA SOCIEDAD
            if ($rowSociedadContratante->ID_SOCIEDAD != ""):
                $GLOBALS["NotificaErrorPorEmail"] = "No";
                $rowDireccionSociedad             = $bd->VerReg("DIRECCION", "ID_SOCIEDAD", $rowSociedadContratante->ID_SOCIEDAD, "No");
            endif;
        endif;

        //DEFINO LAS COORDENADAS DE LAS COLUMNAS (EJE X)
        $columna_1              = 23;
        $columna_1_ancho_maximo = 180;

        $columna_2              = 393;
        $columna_2_ancho_maximo = 180;


        //DEFINO LAS COORDENADAS DE LAS FILAS (EJE Y)
        $altura_superior_cuadro_1 = $fila;
        $altura_cuadro_1          = 81;
        $altura_posicion_cuadro_1 = $fila - 7;

        $altura_superior_cuadro_2 = $fila;
        $altura_cuadro_2          = 81;
        $altura_posicion_cuadro_2 = $fila - 7;


        //DEFINO LOS SALTOS ENTRE LINEAS
        $salto_titulo = 13;
        $salto_altura = 11;

        //EMPRESA CONTRANTE
        //TITULO DEL CUADRO
        $pdf->setStrokeColor(0.3, 0.3, 0.3); //GRIS
        $pdf->setLineStyle(15);
        //PINTAMOS LINEA Y BAJAMOS DE ALTURA
        $pdf->line($columna_1 - 3, $altura_posicion_cuadro_1, $columna_1_ancho_maximo + $columna_1, $altura_posicion_cuadro_1);
        $altura_posicion_cuadro_1 = $altura_posicion_cuadro_1 - 3;

        $pdf->setColor(1, 1, 1);
        //PINTAMOS EL TITULO Y BAJAMOS DE ALTURA
        $pdf->addText($columna_1 + 2, $altura_posicion_cuadro_1, $tamano_letra_titulo_texto, ucfirst($auxiliar->traduce("Empresa Contratante", $idIdiomaAutofactura)));
        $altura_posicion_cuadro_1 = $altura_posicion_cuadro_1 - $salto_titulo;

        //CUADRO DE DATOS
        $pdf->setColor(0, 0, 0);
        $pdf->setLineStyle(1);
        $pdf->rectangle($columna_1 - 3, $altura_superior_cuadro_1 - $altura_cuadro_1, $columna_1_ancho_maximo + 3, $altura_cuadro_1);

        //DATOS
        //PINTAMOS NOMBRE Y BAJAMOS DE ALTURA
        $pdf->addTextWrap($columna_1, $altura_posicion_cuadro_1, $columna_1_ancho_maximo, $tamano_letra, $rowDireccionSociedad->DENOMINACION, 'left', 0, 0, 'b');
        $altura_posicion_cuadro_1 = $altura_posicion_cuadro_1 - $salto_altura;

        //PINTAMOS CIF Y BAJAMOS DE ALTURA
        $pdf->addTextWrap($columna_1, $altura_posicion_cuadro_1, $columna_1_ancho_maximo, $tamano_letra, "CIF " . $rowSociedadContratante->CIF);
        $altura_posicion_cuadro_1 = $altura_posicion_cuadro_1 - $salto_altura;

        //PINTAMOS DIRECCION Y BAJAMOS DE ALTURA
        $numLineasPintadas = $this->pintaTextoEnVariasLineas($rowDireccionSociedad->DIRECCION, $columna_1_ancho_maximo, $columna_1, $altura_posicion_cuadro_1, $tamano_letra, 11, 2);
        if ($numLineasPintadas == 1):
            $altura_posicion_cuadro_1 = $altura_posicion_cuadro_1 - $salto_altura;
        elseif ($numLineasPintadas == 2):
            $altura_posicion_cuadro_1 = $altura_posicion_cuadro_1 - $salto_altura - 11;
        endif;

        //PINTAMOS CP Y POBLACION Y BAJAMOS DE ALTURA
        $pdf->addTextWrap($columna_1, $altura_posicion_cuadro_1, $columna_1_ancho_maximo, $tamano_letra, $rowDireccionSociedad->CODIGO_POSTAL . ' - ' . $rowDireccionSociedad->POBLACION);
        $altura_posicion_cuadro_1 = $altura_posicion_cuadro_1 - $salto_altura;

        //PINTAMOS PAIS
        $pdf->addTextWrap($columna_1, $altura_posicion_cuadro_1, $columna_1_ancho_maximo, $tamano_letra, ($rowDireccionSociedad->REGION != "" ? $rowDireccionSociedad->REGION . " " : "") . $auxiliar->obtenerDescripcionPais($rowDireccionSociedad->ID_PAIS, $idIdiomaAutofactura));


        //PROVEEDOR
        //TITULO DEL CUADRO
        $pdf->setStrokeColor(0.3, 0.3, 0.3); //GRIS
        $pdf->setLineStyle(15);
        //PINTAMOS LINEA Y BAJAMOS DE ALTURA
        $pdf->line($columna_2 - 3, $altura_posicion_cuadro_2, $columna_2_ancho_maximo + $columna_2, $altura_posicion_cuadro_2);
        $altura_posicion_cuadro_2 = $altura_posicion_cuadro_2 - 3;

        $pdf->setColor(1, 1, 1);
        //PINTAMOS EL TITULO Y BAJAMOS DE ALTURA
        $pdf->addText($columna_2 + 2, $altura_posicion_cuadro_2, $tamano_letra_titulo_texto, ucfirst($auxiliar->traduce("Proveedor", $idIdiomaAutofactura)));
        $altura_posicion_cuadro_2 = $altura_posicion_cuadro_2 - $salto_titulo;

        //CUADRO DE DATOS
        $pdf->setColor(0, 0, 0);
        $pdf->setLineStyle(1);
        $pdf->rectangle($columna_2 - 3, $altura_superior_cuadro_2 - $altura_cuadro_2, $columna_2_ancho_maximo + 3, $altura_cuadro_2);

        //PINTAMOS NOMBRE
        $pdf->addTextWrap($columna_2, $altura_posicion_cuadro_2, $columna_2_ancho_maximo, $tamano_letra, $rowDireccionProveedor->DENOMINACION, 'left', 0, 0, 'b');
        $altura_posicion_cuadro_2 = $altura_posicion_cuadro_2 - $salto_altura;

        //PINTAMOS CIF Y BAJAMOS DE ALTURA
        $pdf->addTextWrap($columna_2, $altura_posicion_cuadro_2, $columna_2_ancho_maximo, $tamano_letra, "CIF " . $rowProveedor->NIF);
        $altura_posicion_cuadro_2 = $altura_posicion_cuadro_2 - $salto_altura;

        //PINTAMOS DIRECCION Y BAJAMOS DE ALTURA
        $numLineasPintadas = $this->pintaTextoEnVariasLineas($rowDireccionProveedor->DIRECCION, $columna_2_ancho_maximo, $columna_2, $altura_posicion_cuadro_2, $tamano_letra, 11, 2);
        if ($numLineasPintadas == 1):
            $altura_posicion_cuadro_2 = $altura_posicion_cuadro_2 - $salto_altura;
        elseif ($numLineasPintadas == 2):
            $altura_posicion_cuadro_2 = $altura_posicion_cuadro_2 - $salto_altura - 11;
        endif;

        //PINTAMOS CP Y POBLACION Y BAJAMOS DE ALTURA
        $pdf->addTextWrap($columna_2, $altura_posicion_cuadro_2, $columna_2_ancho_maximo, $tamano_letra, $rowDireccionProveedor->CODIGO_POSTAL . ' - ' . $rowDireccionProveedor->POBLACION);
        $altura_posicion_cuadro_2 = $altura_posicion_cuadro_2 - $salto_altura;

        //PINTAMOS PAIS
        $pdf->addTextWrap($columna_2, $altura_posicion_cuadro_2, $columna_2_ancho_maximo, $tamano_letra, ($rowDireccionProveedor->REGION != "" ? $rowDireccionProveedor->REGION . " " : "") . $auxiliar->obtenerDescripcionPais($rowDireccionProveedor->ID_PAIS, $idIdiomaAutofactura));


        //DEFINO LAS COORDENADAS DE LAS FILAS (EJE Y)
        $altura_posicion_cuadro   = $altura_posicion_cuadro_1;
        $altura_superior_cuadro_1 = $altura_posicion_cuadro_1 - 25;
        $altura_cuadro_1          = 30;
        $altura_posicion_cuadro_1 = $altura_posicion_cuadro_1 - 32;

        $altura_superior_cuadro_2 = $altura_posicion_cuadro - 25;
        $altura_cuadro_2          = 30;
        $altura_posicion_cuadro_2 = $altura_posicion_cuadro - 32;

        $columna_1              = 23;
        $columna_1_ancho_maximo = 140;
        $columna_2              = $columna_1_ancho_maximo + $columna_1 + 3;
        $columna_2_ancho_maximo = 120;


        //CUADRO DEL PERIODO DE CERTIFICACION

        //TITULO DEL CUADRO
        $pdf->setStrokeColor(0.3, 0.3, 0.3); //GRIS
        $pdf->setLineStyle(15);
        //PINTAMOS LINEA Y BAJAMOS DE ALTURA
        $pdf->line($columna_1 - 3, $altura_posicion_cuadro_1, $columna_1_ancho_maximo + $columna_1, $altura_posicion_cuadro_1);
        $altura_posicion_cuadro_1 = $altura_posicion_cuadro_1 - 3;

        $pdf->setColor(1, 1, 1);
        //PINTAMOS EL TITULO Y BAJAMOS DE ALTURA
        $pdf->addText($columna_1 + 2, $altura_posicion_cuadro_1, $tamano_letra_titulo_texto, ucfirst($auxiliar->traduce("Periodo de Certificacion", $idIdiomaAutofactura)));
        $altura_posicion_cuadro_1 = $altura_posicion_cuadro_1 - $salto_titulo - 2;


        //CALCULO EL PERIODO DE FACTURACION EN FUNCION DE LA REFERENCIA DE FACTURACION
        $fechaInicioPeriodo = "-";
        $fechaFinPeriodo    = "-";
        if (strpos($rowAutofactura->REFERENCIA_FACTURACION, 'TT') === 0):
            $GLOBALS["NotificaErrorPorEmail"]  = "No";
            $rowProveedorReferenciaFacturacion = $bd->VerReg("PROVEEDOR_REFERENCIA_FACTURACION", "REFERENCIA_FACTURACION_NUEVO_MODELO", $rowAutofactura->REFERENCIA_FACTURACION, "No");
        else:
            $GLOBALS["NotificaErrorPorEmail"]  = "No";
            $rowProveedorReferenciaFacturacion = $bd->VerReg("PROVEEDOR_REFERENCIA_FACTURACION", "REFERENCIA_FACTURACION", $rowAutofactura->REFERENCIA_FACTURACION, "No");
        endif;
        if ($rowProveedorReferenciaFacturacion != false):
            $fechaInicioPeriodo = $auxiliar->fechaFmtoEsp($rowProveedorReferenciaFacturacion->FECHA_INICIO_PERIODO);
            $fechaFinPeriodo    = $auxiliar->fechaFmtoEsp($rowProveedorReferenciaFacturacion->FECHA_FIN_PERIODO);
        endif;

        //CUADRO DE DATOS
        $pdf->setColor(0, 0, 0);
        $pdf->setLineStyle(1);
        $pdf->rectangle($columna_1 - 3, $altura_superior_cuadro_1 - $altura_cuadro_1, $columna_1_ancho_maximo + 3, $altura_cuadro_1);

        //DATOS
        //PINTAMOS  Y BAJAMOS DE ALTURA
        $pdf->addTextWrap($columna_1, $altura_posicion_cuadro_1, $columna_1_ancho_maximo, $tamano_letra, ucfirst($auxiliar->traduce("de", $idIdiomaAutofactura)) . " " . $fechaInicioPeriodo . " " . strtolower((string)$auxiliar->traduce("a", $idIdiomaAutofactura)) . " " . $fechaFinPeriodo, 'center');


        //CUADRO DE REFERENCIA FACTURACION


        //TITULO DEL CUADRO
        $pdf->setStrokeColor(0.3, 0.3, 0.3); //GRIS
        $pdf->setLineStyle(15);
        //PINTAMOS LINEA Y BAJAMOS DE ALTURA
        $pdf->line($columna_2 - 3, $altura_posicion_cuadro_2, $columna_2_ancho_maximo + $columna_2, $altura_posicion_cuadro_2);
        $altura_posicion_cuadro_2 = $altura_posicion_cuadro_2 - 3;

        $pdf->setColor(1, 1, 1);
        //PINTAMOS EL TITULO Y BAJAMOS DE ALTURA
        $pdf->addText($columna_2 + 2, $altura_posicion_cuadro_2, $tamano_letra_titulo_texto, ucfirst($auxiliar->traduce("Referencia Facturacion", $idIdiomaAutofactura)));
        $altura_posicion_cuadro_2 = $altura_posicion_cuadro_2 - $salto_titulo - 2;


        //CUADRO DE DATOS
        $pdf->setColor(0, 0, 0);
        $pdf->setLineStyle(1);
        $pdf->rectangle($columna_2 - 3, $altura_superior_cuadro_2 - $altura_cuadro_2, $columna_2_ancho_maximo + 3, $altura_cuadro_2);

        //DATOS
        //PINTAMOS  Y BAJAMOS DE ALTURA
        $pdf->addTextWrap($columna_2, $altura_posicion_cuadro_2, $columna_2_ancho_maximo, $tamano_letra, $rowAutofactura->REFERENCIA_FACTURACION, 'center');


        $altura_posicion_cuadro_2 = $altura_posicion_cuadro_2 - $salto_altura;


        //REDEFINO LA ALTURA DE LA FILA
        $fila = $altura_posicion_cuadro_2;


        //SI LA AUTOFACTURA ES "MANUAL" MOSTRAMOS LAS OBSERVACIONES DE FILTRADO
        $numeroLineas = 0;
        if ($rowAutofactura->TIPO_FACTURACION == "Manual"):
            $numeroLineas = $this->pintaTextoEnVariasLineas('<b><i>' . $auxiliar->traduce("Observaciones Filtrado", $idIdiomaAutofactura) . ": " . '</i></b>' . $rowAutofactura->OBSERVACIONES_FILTROS_APLICADOS, 545, $columna_1 - 3, $fila - 4, 8, 10, '', '');

            //METEMOS EL NUMERO DE LINEAS EN SESION
            $_SESSION['numeroLineas'] = $numeroLineas;

            $fila -= 12 * $numeroLineas;
        endif;


        //MUESTRO LA ADVERTENCIA DE UNA FACTURA POR REFERENCIA DE FACTURACION
        $pdf->setColor(100, 0, 0); //COLOR ROJO
        $pdf->addTextWrap(20, $fila - 7, 545, 12, $auxiliar->traduce("Solo se aceptara una unica factura por Referencia de Facturacion", $idIdiomaAutofactura), 'center', 0, 0, 'b');
        $pdf->setColor(0, 0, 0); //COLOR NEGRO

        //FILA AZUL PARA SEPARAR DATOS DE CABECERA DE CABECERA DE LINEAS
        $pdf->setStrokeColor(0, 0, 100); //AZUL
        $pdf->setLineStyle(3);
        $pdf->line(20, $fila - 14, 575, $fila - 14); //LINEA AZUL


        //REDEFINO LA ALTURA DE LA FILA
        $fila = $fila - 30;


        //CABECERAS DE LINEAS
        //CABECERAS DE LINEAS
        $columna = 20;
        $pdf->addText($columna, $fila, 8, '<b><i>' . $auxiliar->traduce("Contratacion", $idIdiomaAutofactura) . '</i></b>');
        $columna += 408;
        $pdf->addText($columna, $fila, 8, '<b><i>' . $auxiliar->traduce("Fecha Ejecucion", $idIdiomaAutofactura) . '</i></b>');
        $columna += 90;
        $pdf->addText($columna, $fila, 8, '<b><i>' . $auxiliar->traduce("Importe", $idIdiomaAutofactura) . '</i></b>');


        //REDEFINO LA ALTURA DE LA FILA
        $fila = $fila - 5;

        //FILA PARA SEPARAR LAS CABECERA DE LINEAS
        $pdf->setStrokeColor(0, 0, 0);
        $pdf->setLineStyle(1);
        $pdf->line(20, $fila, 575, $fila); //LINEA NEGRA

        //REDEFINO LA ALTURA DE LA FILA
        $fila = $fila - 10;
    }

    function crearCabeceraAutofacturaSinCuadrosCabecera($idAutofactura)
    {
        global $auxiliar;
        global $bd;
        global $pdf;
        global $idIdiomaAutofactura;

        //BUSCO LA AUTOFACTURA
        $rowAutofactura = $bd->VerReg("AUTOFACTURA", "ID_AUTOFACTURA", $idAutofactura);

        $pdf->setColor(0, 0, 100); //COLOR AZUL
        $pdf->addTextWrap(90, 807, 400, 13, '<b>' . strtr(strtoupper($auxiliar->traduce("Informe Certificacion", $idIdiomaAutofactura)), 'ó', 'Ó') . '</b>', 'center');;

        $pdf->setColor(0, 0, 0); //COLOR NEGRO

        //PARA IMPRIMIR EL CODIGO DE BARRAS
        $rutaImagen = "/tmp/" . $rowAutofactura->ID_AUTOFACTURA . ".png";

        $a  = generateBarCodePNG($rowAutofactura->ID_AUTOFACTURA, "", 150, 52, 500, 1, 1);
        $fp = fopen($rutaImagen, "w"); //Crear el manejador
        fwrite($fp, $a); //Escribir el fichero
        fclose($fp); //Cerrar el manejador
        $pdf->addPngFromFile($rutaImagen, 420, 782); //Llamar a la función correspondiente de la clase pdf para añadir imagenes
        unlink($rutaImagen); //Borrar la imagen
        //FIN PARA IMPRIMIR EL CODIGO DE BARRAS

        //DEFINO LA ALTURA DE LA FILA
        $fila = 780;

        //CABECERAS DE LINEAS
        $columna = 20;
        $pdf->addText($columna, $fila, 8, '<b><i>' . $auxiliar->traduce("Contratacion", $idIdiomaAutofactura) . '</i></b>');
        $columna += 408;
        $pdf->addText($columna, $fila, 8, '<b><i>' . $auxiliar->traduce("Fecha Ejecucion", $idIdiomaAutofactura) . '</i></b>');
        $columna += 90;
        $pdf->addText($columna, $fila, 8, '<b><i>' . $auxiliar->traduce("Importe", $idIdiomaAutofactura) . '</i></b>');


        //REDEFINO LA ALTURA DE LA FILA
        $fila = $fila - 5;

        //FILA PARA SEPARAR LAS CABECERA DE LINEAS
        $pdf->setStrokeColor(0, 0, 0);
        $pdf->setLineStyle(1);
        $pdf->line(20, $fila, 575, $fila); //LINEA NEGRA

        //REDEFINO LA ALTURA DE LA FILA
        $fila = $fila - 10;
    }

    function crearCuerpoAutofactura($idAutofactura)
    {
        global $auxiliar;
        global $bd;
        global $importe;
        global $pdf;
        global $idIdiomaAutofactura;

        //BUSCO LA AUTOFACTURA
        $rowAutofactura = $bd->VerReg("AUTOFACTURA", "ID_AUTOFACTURA", $idAutofactura);

        //DECLARO LA ALTURA DE LA FILA
        $fila = 595 - (($_SESSION['numeroLineas'] > 0) ? (15 * ($_SESSION['numeroLineas'] - 1)) : 0);

        //VARIABLE PARA SABER EL NUMERO DE REGISTROS
        $numContrataciones = 0;

        //LINEAS POR PAGINA
        $cantXpag = 11;
        //BUSCO LAS LINEAS DE LA AUTOFACTURAç


        $sqlLineas    = "SELECT AL.*
                      FROM AUTOFACTURA_LINEA AL
                      WHERE AL.ID_AUTOFACTURA = $rowAutofactura->ID_AUTOFACTURA AND AL.BAJA = 0
                      ORDER BY AL.FECHA_EJECUCION ASC, AL.ID_ORDEN_CONTRATACION ASC";//exit($sqlLineas);
        $resultLineas = $bd->ExecSQL($sqlLineas);
        while ($rowLinea = $bd->SigReg($resultLineas)):

            if ($cantXpag <= $numContrataciones):
                $pdf->ezNewPage();
                $this->crearCabeceraAutofacturaSinCuadrosCabecera($rowAutofactura->ID_AUTOFACTURA);
                $numContrataciones = 0;
                $cantXpag          = 14;
                $fila              = 760;
            endif;


            //BUSCO LA MONEDA DE LA SOCIEDAD
            $nombreMonedaSociedad = "";
            $idMonedaSociedad     = $rowLinea->ID_MONEDA;
            if ($rowLinea->ID_MONEDA != ""):
                $nombreMonedaSociedad = $importe->getNombreMoneda($rowLinea->ID_MONEDA);
            endif;

            //BUSCO LA ORDEN DE CONTRATACION
            $rowOrdenContratacion = $bd->VerReg("ORDEN_CONTRATACION", "ID_ORDEN_CONTRATACION", $rowLinea->ID_ORDEN_CONTRATACION);

            //BUSCO EL SERVICIO
            $rowServicio = $bd->VerReg("SERVICIO", "ID_SERVICIO", $rowOrdenContratacion->ID_SERVICIO);

            //BUSCO LA ORDEN DE TRANSPORTE
            $rowOrdenTransporte = null;
            if ($rowOrdenContratacion->ID_ORDEN_TRANSPORTE != ''):
                $rowOrdenTransporte = $bd->VerReg("ORDEN_TRANSPORTE", "ID_ORDEN_TRANSPORTE", $rowOrdenContratacion->ID_ORDEN_TRANSPORTE, "No");
            endif;
            //DATOS DE LINEAS

            $columna = 40;
            $pdf->addText($columna, $fila, 8, '' . $rowOrdenContratacion->ID_ORDEN_CONTRATACION . '');
            $columna      += 40;
            $numeroLineas = $this->pintaTextoEnVariasLineas('<b><i>' . $auxiliar->traduce("Tipo Servicio", $idIdiomaAutofactura) . ": " . '</i></b>' . (($idIdiomaAutofactura == "ESP") ? $rowServicio->NOMBRE : $rowServicio->NOMBRE_ENG), 350, $columna, $fila, 8, 10, '', '');
            $columna      += 348;
            $pdf->addText($columna, $fila, 8, '' . $rowOrdenContratacion->FECHA_EJECUCION . '');
            $columna += 90;

            $pdf->addTextWrap($columna, $fila, 65, 8, $auxiliar->formatoMoneda($rowLinea->IMPORTE, $rowLinea->ID_MONEDA) . " " . $nombreMonedaSociedad, 'left');


            //REDEFINO LA ALTURA DE LA FILA
            $fila    = $fila - 16 * $numeroLineas;
            $columna = 80;
            $pdf->addText($columna, $fila, 8, '<b><i>' . $auxiliar->traduce("Descripcion", $idIdiomaAutofactura) . ":" . '</i></b>');
            $columna += 60;
            $pdf->addTextWrap($columna, $fila, 120, 8, '' . $rowOrdenContratacion->DESCRIPCION . '');

            //REDEFINO LA ALTURA DE LA FILA
            $fila    = $fila - 16;
            $columna = 80;
            $pdf->addText($columna, $fila, 8, '<b><i>' . $auxiliar->traduce("Nº Alb. Prov.", $idIdiomaAutofactura) . '</i></b>');
            $columna += 60;

            $albaranTransporte = '';
            if ($rowOrdenTransporte->ALBARAN_TRANSPORTE != ''):
                $albaranTransporte = $rowOrdenTransporte->ALBARAN_TRANSPORTE;
            endif;
            $pdf->addText($columna, $fila, 8, '' . $albaranTransporte . '');
            $columna += 95;
            $pdf->addText($columna, $fila, 8, '<b><i>' . $auxiliar->traduce("RFQ", $idIdiomaAutofactura) . '</i></b>');
            $columna += 30;
            $pdf->addText($columna, $fila, 8, '' . $rowOrdenContratacion->RFQ_LICITACION . '');

            $pdf->setLineStyle(0.5);
            $pdf->line(20, $fila - 6, 575, $fila - 6); //LINEA NEGRA
            //REDEFINO LA ALTURA DE LA FILA
            $fila = $fila - 19;

            $numContrataciones = $numContrataciones + 1;

        endwhile;


        //MOSTRAMOS ABAJO EL SUBTOTAL POR MONEDA
        $sqlSubtotal    = "SELECT SUM(IMPORTE) AS IMPORTE_POR_MONEDA, ID_MONEDA
                      FROM AUTOFACTURA_LINEA AL
                      WHERE AL.ID_AUTOFACTURA = $rowAutofactura->ID_AUTOFACTURA AND AL.BAJA = 0
                      GROUP BY ID_MONEDA
                      ORDER BY AL.FECHA_EJECUCION ASC, AL.ID_ORDEN_CONTRATACION ASC
                     ";//exit($sqlLineas);
        $resultSubtotal = $bd->ExecSQL($sqlSubtotal);

        $numSubtotales = $bd->NumRegs($resultSubtotal);

        if ($cantXpag < $numContrataciones + $numSubtotales):
            $pdf->ezNewPage();
            $this->crearCabeceraAutofacturaSinCuadrosCabecera($rowAutofactura->ID_AUTOFACTURA);
            //$numContrataciones = 0;
            //$cantXpag = 61;
            //$fila = 760;
        endif;

        $columna_1                       = 453;
        $altura_superior_cuadro_subtotal = 55 + 12 * $numSubtotales;
        $altura_cuadro_subtotal          = 20 + 12 * $numSubtotales;
        $altura_posicion_cuadro_subtotal = $altura_superior_cuadro_subtotal - 7;

        $salto_altura = 12;

        //TITULO DEL CUADRO
        $pdf->setStrokeColor(0.3, 0.3, 0.3); //GRIS
        $pdf->setLineStyle(15);
        //PINTAMOS LINEA Y BAJAMOS DE ALTURA
        $pdf->line($columna_1 - 3, $altura_posicion_cuadro_subtotal, 120 + $columna_1, $altura_posicion_cuadro_subtotal);
        $altura_posicion_cuadro_subtotal = $altura_posicion_cuadro_subtotal - 3;

        $pdf->setColor(1, 1, 1);
        //PINTAMOS EL TITULO Y BAJAMOS DE ALTURA
        $pdf->addText($columna_1 + 2, $altura_posicion_cuadro_subtotal, 10, ucfirst($auxiliar->traduce("Subtotal por Moneda", $idIdiomaAutofactura)));
        $altura_posicion_cuadro_subtotal = $altura_posicion_cuadro_subtotal - 15;


        //CUADRO DE DATOS
        $pdf->setColor(0, 0, 0);
        $pdf->setLineStyle(1);
        $pdf->rectangle($columna_1 - 3, $altura_superior_cuadro_subtotal - $altura_cuadro_subtotal, 120 + 3, $altura_cuadro_subtotal);

        while ($rowSubtotales = $bd->SigReg($resultSubtotal)):

            //BUSCO LA MONEDA DE LA SOCIEDAD
            $nombreMonedaSociedad = "";
            if ($rowSubtotales->ID_MONEDA != ""):
                $nombreMonedaSociedad = $importe->getNombreMoneda($rowSubtotales->ID_MONEDA);
            endif;

            //PINTAMOS EL DATO Y BAJAMOS DE ALTURA
            $pdf->addTextWrap($columna_1, $altura_posicion_cuadro_subtotal, 115, 8, $auxiliar->formatoMoneda($rowSubtotales->IMPORTE_POR_MONEDA, $rowSubtotales->ID_MONEDA) . " " . $nombreMonedaSociedad, 'right', 0, 0, 'b');
            $altura_posicion_cuadro_subtotal = $altura_posicion_cuadro_subtotal - $salto_altura;

        endwhile;
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
            //GENERO EL FICHERO EN FORMATO ESTANDAR
            $impreso->openHere('Fit');
            $impreso->ezStream();

            //DEVUELVO EL FICHERO EN FORMATO TEXTO
            return $impreso;

        elseif ($formato == 'Web'):
            //GENERO EL FICHERO EN FORMATO ESTANDAR
            $impreso->openHere('Fit');
            $impreso->ezStream();

            //DEVUELVO EL FICHERO EN FORMATO WEB
            return $impreso;
        else:
            return false;
        endif;
    }


    function getAutoFactura($idAutofactura)
    {
        global $bd, $pathClases, $pathRaiz, $auxiliar, $pdf;

        global $idIdiomaAutofactura;

        //BUSCO LA AUTOFACTURA
        $rowAutofactura = $bd->VerReg("AUTOFACTURA", "ID_AUTOFACTURA", $idAutofactura);

        //CREO EL PDF

        $pdf->selectFont($pathClases . 'lib/pdfClasses/fonts/Helvetica.afm');

        //ESTABLEZCO LOS DATOS COMUNES A TODAS LA PAGINAS
        $all = $pdf->openObject();
        $pdf->saveState();

        $pdf->setLineStyle(3);
        $pdf->line(100, 828, 575, 827); //PRIMERA LINEA NEGRA
        $pdf->addJpegFromFile($pathRaiz . 'imagenes/acciona_impresos.jpg', 20, 801, 70, 0); //LOGO ACCIONA
        $pdf->setLineStyle(3);
        $pdf->line(20, 798, 575, 798); //SEGUNDA LINEA NEGRA

        $pdf->setLineStyle(1);
        $pdf->line(20, 30, 575, 30);
        $pdf->addText(20, 24, 6, $this->armoFecha($idIdiomaAutofactura));
        $pdf->restoreState();
        $pdf->closeObject();
        $pdf->addObject($all, 'all');

        //BUSCO EL PROVEEDOR
        $rowProveedor = $bd->VerReg("PROVEEDOR", "ID_PROVEEDOR", $rowAutofactura->ID_PROVEEDOR);

        //BUSCO EL PAIS DEL PROVEEDOR
        $rowPaisProveedor = $bd->VerReg("PAIS", "ID_PAIS", $rowProveedor->ID_PAIS);

        //BUSCO EL IDIOMA DEL PROVEEDOR EN FUNCION DEL PAIS DE ESTE
        //$idIdiomaAutofactura = $rowPaisProveedor->IDIOMA;

        //INICIALIZO EL CONTADOR DE HOJAS
        $i = $pdf->ezStartPageNumbers(575, 23, 6, '', $auxiliar->traduce("Página", $idIdiomaAutofactura) . ' {PAGENUM} ' . $auxiliar->traduce("de", $idIdiomaAutofactura) . ' {TOTALPAGENUM}', 1);

        //GENERO LA CABECERA
        $this->crearCabeceraAutofactura($rowAutofactura->ID_AUTOFACTURA);

        //GENERO EL CUERPO (LINEAS)
        $this->crearCuerpoAutofactura($rowAutofactura->ID_AUTOFACTURA);

        //FINALIZO EL CONTADOR DE HOJAS
        $pdf->ezStopPageNumbers(1, 1, $i);
    }
    //FIN AUTOFACTURA

    //CONTRATACIONES

    function crearCabeceraContratacion($idOrdenContratacion)
    {
        global $auxiliar;
        global $bd;
        global $pdf;

        global $fila;
        global $total;

        global $tamano_letra;
        global $tamanoLetraCuadros;

        global $idIdiomaImpresion;

        //BUSCO LA ORDEN DE CONTRATACION
        $rowOrdenContratacion = $bd->VerReg("ORDEN_CONTRATACION", "ID_ORDEN_CONTRATACION", $idOrdenContratacion);

        //BUSCO EL SERVICIO CONTRATADO
        if ($rowOrdenContratacion->ID_SERVICIO):
            $rowServicio = $bd->VerReg("SERVICIO", "ID_SERVICIO", $rowOrdenContratacion->ID_SERVICIO);
        endif;

        //BUSCO LA SOCIEDAD CONTRATANTE
        $sqlSociedad    = "SELECT S.*
                      FROM ORDEN_TRANSPORTE OT
                      INNER JOIN CENTRO C ON C.ID_CENTRO = OT.ID_CENTRO_CONTRATANTE
                      INNER JOIN SOCIEDAD S ON S.ID_SOCIEDAD = C.ID_SOCIEDAD
                      WHERE OT.ID_ORDEN_TRANSPORTE = $rowOrdenContratacion->ID_ORDEN_TRANSPORTE";
        $resultSociedad = $bd->ExecSQL($sqlSociedad);

        $rowSociedad = $bd->SigReg($resultSociedad);

        //BUSCO LA DIRECCION DE LA SOCIEDAD
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowDireccionSociedad             = $bd->VerReg("DIRECCION", "ID_SOCIEDAD", $rowSociedad->ID_SOCIEDAD, "No");

        //BUSCO EL PROVEEDOR
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowProveedor                     = $bd->VerReg("PROVEEDOR", "ID_PROVEEDOR", $rowOrdenContratacion->ID_PROVEEDOR, "No");

        //BUSCO LA DIRECCION DEL PROVEEDOR
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowDireccionProveedor            = $bd->VerReg("DIRECCION", "ID_PROVEEDOR", $rowProveedor->ID_PROVEEDOR, "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);

        $pdf->setColor(0, 0, 100); //COLOR AZUL
        $pdf->addTextWrap(100, 807, 350, 15, '<b>' . strtr(strtoupper($auxiliar->traduce("Nº Contratacion", $idIdiomaImpresion)), 'ó', 'Ó') . ': ' . $rowOrdenContratacion->ID_ORDEN_CONTRATACION . '</b>', 'center');


        $pdf->setColor(0, 0, 0); //COLOR NEGRO
        $pdf->addTextWrap(375, 803, 200, 12, '<b>' . strtr(strtoupper($auxiliar->traduce("Detalle Servicio", $idIdiomaImpresion)), 'ó', 'Ó') . '</b>', 'right');

        //DEFINO LA ALTURA
        $fila = 780;


        //DEFINO LAS COORDENADAS DE LAS COLUMNAS (EJE X)
        $columna_1              = 23;
        $columna_1_ancho_maximo = 180;

        $columna_2              = 393;
        $columna_2_ancho_maximo = 180;


        //DEFINO LAS COORDENADAS DE LAS FILAS (EJE Y)
        $altura_superior_cuadro_1 = $fila;
        $altura_cuadro_1          = 81;
        $altura_posicion_cuadro_1 = $fila - 7;

        $altura_superior_cuadro_2 = $fila;
        $altura_cuadro_2          = 81;
        $altura_posicion_cuadro_2 = $fila - 7;


        $salto_titulo = 13;
        $salto_altura = 11;


        //EMPRESA CONTRANTE
        //TITULO DEL CUADRO
        $pdf->setStrokeColor(0.3, 0.3, 0.3); //GRIS
        $pdf->setLineStyle(15);
        //PINTAMOS LINEA Y BAJAMOS DE ALTURA
        $pdf->line($columna_1 - 3, $altura_posicion_cuadro_1, $columna_1_ancho_maximo + $columna_1, $altura_posicion_cuadro_1);
        $altura_posicion_cuadro_1 = $altura_posicion_cuadro_1 - 3;

        $pdf->setColor(1, 1, 1);
        //PINTAMOS EL TITULO Y BAJAMOS DE ALTURA
        $pdf->addText($columna_1 + 2, $altura_posicion_cuadro_1, $tamanoLetraCuadros, ucfirst($auxiliar->traduce("Empresa Contratante", $idIdiomaImpresion)));
        $altura_posicion_cuadro_1 = $altura_posicion_cuadro_1 - $salto_titulo;

        //CUADRO DE DATOS
        $pdf->setColor(0, 0, 0);
        $pdf->setLineStyle(1);
        $pdf->rectangle($columna_1 - 3, $altura_superior_cuadro_1 - $altura_cuadro_1, $columna_1_ancho_maximo + 3, $altura_cuadro_1);

        //DATOS
        //PINTAMOS NOMBRE Y BAJAMOS DE ALTURA
        $pdf->addTextWrap($columna_1, $altura_posicion_cuadro_1, $columna_1_ancho_maximo, $tamano_letra, $rowDireccionSociedad->DENOMINACION, 'left', 0, 0, 'b');
        $altura_posicion_cuadro_1 = $altura_posicion_cuadro_1 - $salto_altura;

        //PINTAMOS CIF Y BAJAMOS DE ALTURA
        $pdf->addTextWrap($columna_1, $altura_posicion_cuadro_1, $columna_1_ancho_maximo, $tamano_letra, "CIF " . $rowSociedad->CIF);
        $altura_posicion_cuadro_1 = $altura_posicion_cuadro_1 - $salto_altura;

        //PINTAMOS DIRECCION Y BAJAMOS DE ALTURA
        $numLineasPintadas = $this->pintaTextoEnVariasLineas($rowDireccionSociedad->DIRECCION, $columna_1_ancho_maximo, $columna_1, $altura_posicion_cuadro_1, $tamano_letra, 11, 2);
        if ($numLineasPintadas == 1):
            $altura_posicion_cuadro_1 = $altura_posicion_cuadro_1 - $salto_altura;
        elseif ($numLineasPintadas == 2):
            $altura_posicion_cuadro_1 = $altura_posicion_cuadro_1 - $salto_altura - 11;
        endif;

        //PINTAMOS CP Y POBLACION Y BAJAMOS DE ALTURA
        $pdf->addTextWrap($columna_1, $altura_posicion_cuadro_1, $columna_1_ancho_maximo, $tamano_letra, $rowDireccionSociedad->CODIGO_POSTAL . ' - ' . $rowDireccionSociedad->POBLACION);
        $altura_posicion_cuadro_1 = $altura_posicion_cuadro_1 - $salto_altura;

        //PINTAMOS PAIS
        $pdf->addTextWrap($columna_1, $altura_posicion_cuadro_1, $columna_1_ancho_maximo, $tamano_letra, ($rowDireccionSociedad->REGION != "" ? $rowDireccionSociedad->REGION . " " : "") . $auxiliar->obtenerDescripcionPais($rowDireccionSociedad->ID_PAIS, $idIdiomaImpresion));

        if ($numLineasPintadas == 2):
            $altura_posicion_cuadro_1 = $altura_posicion_cuadro_1 + 11;
        endif;

        //PROVEEDOR
        //TITULO DEL CUADRO
        $pdf->setStrokeColor(0.3, 0.3, 0.3); //GRIS
        $pdf->setLineStyle(15);
        //PINTAMOS LINEA Y BAJAMOS DE ALTURA
        $pdf->line($columna_2 - 3, $altura_posicion_cuadro_2, $columna_2_ancho_maximo + $columna_2, $altura_posicion_cuadro_2);
        $altura_posicion_cuadro_2 = $altura_posicion_cuadro_2 - 3;

        $pdf->setColor(1, 1, 1);
        //PINTAMOS EL TITULO Y BAJAMOS DE ALTURA
        $pdf->addText($columna_2 + 2, $altura_posicion_cuadro_2, $tamanoLetraCuadros, ucfirst($auxiliar->traduce("Proveedor", $idIdiomaImpresion)));
        $altura_posicion_cuadro_2 = $altura_posicion_cuadro_2 - $salto_titulo;

        //CUADRO DE DATOS
        $pdf->setColor(0, 0, 0);
        $pdf->setLineStyle(1);
        $pdf->rectangle($columna_2 - 3, $altura_superior_cuadro_2 - $altura_cuadro_2, $columna_2_ancho_maximo + 3, $altura_cuadro_2);

        //PINTAMOS NOMBRE
        $pdf->addTextWrap($columna_2, $altura_posicion_cuadro_2, $columna_2_ancho_maximo, $tamano_letra, $rowDireccionProveedor->DENOMINACION, 'left', 0, 0, 'b');
        $altura_posicion_cuadro_2 = $altura_posicion_cuadro_2 - $salto_altura;

        //PINTAMOS CIF Y BAJAMOS DE ALTURA
        $pdf->addTextWrap($columna_2, $altura_posicion_cuadro_2, $columna_2_ancho_maximo, $tamano_letra, "CIF " . $rowProveedor->NIF);
        $altura_posicion_cuadro_2 = $altura_posicion_cuadro_2 - $salto_altura;

        //PINTAMOS DIRECCION Y BAJAMOS DE ALTURA
        $numLineasPintadas = $this->pintaTextoEnVariasLineas($rowDireccionProveedor->DIRECCION, $columna_2_ancho_maximo, $columna_2, $altura_posicion_cuadro_2, $tamano_letra, 11, 2);
        if ($numLineasPintadas == 1):
            $altura_posicion_cuadro_2 = $altura_posicion_cuadro_2 - $salto_altura;
        elseif ($numLineasPintadas == 2):
            $altura_posicion_cuadro_2 = $altura_posicion_cuadro_2 - $salto_altura - 11;
        endif;

        //PINTAMOS CP Y POBLACION Y BAJAMOS DE ALTURA
        $pdf->addTextWrap($columna_2, $altura_posicion_cuadro_2, $columna_2_ancho_maximo, $tamano_letra, $rowDireccionProveedor->CODIGO_POSTAL . ' - ' . $rowDireccionProveedor->POBLACION);
        $altura_posicion_cuadro_2 = $altura_posicion_cuadro_2 - $salto_altura;

        //PINTAMOS PAIS
        $pdf->addTextWrap($columna_2, $altura_posicion_cuadro_2, $columna_2_ancho_maximo, $tamano_letra, ($rowDireccionProveedor->REGION != "" ? $rowDireccionProveedor->REGION . " " : "") . $auxiliar->obtenerDescripcionPais($rowDireccionProveedor->ID_PAIS, $idIdiomaImpresion));

        //DEFINO LAS COORDENADAS DE LA SEGUNDA FILAS (EJE Y)
        $altura_superior_cuadro_1 = $altura_posicion_cuadro_1 - 25;
        $altura_cuadro_1          = 32;
        $altura_posicion_cuadro_1 = $altura_posicion_cuadro_1 - 32;


        $ancho_linea = $columna_1 - 3;

        //CUADRO DE LA FECHA
        //TITULO DEL CUADRO
        $pdf->setStrokeColor(0.3, 0.3, 0.3); //GRIS
        $pdf->setLineStyle(15);
        //PINTAMOS LINEA
        $pdf->line($ancho_linea, $altura_posicion_cuadro_1, $ancho_linea + 102, $altura_posicion_cuadro_1);
        $pdf->setColor(1, 1, 1);
        //PINTAMOS EL TITULO
        $pdf->addTextWrap($ancho_linea, $altura_posicion_cuadro_1 - 3, 102, $tamanoLetraCuadros, ucfirst($auxiliar->traduce("Fecha", $idIdiomaImpresion)), 'center');


        //CUADRO DE DATOS
        $pdf->setColor(0, 0, 0);
        $pdf->setLineStyle(1);
        $pdf->rectangle($ancho_linea, $altura_superior_cuadro_1 - $altura_cuadro_1, 102, $altura_cuadro_1);

        //DATOS
        //PINTAMOS  Y BAJAMOS DE ALTURA
        $pdf->addTextWrap($ancho_linea, $altura_posicion_cuadro_1 - 20, 102, $tamano_letra, $auxiliar->fechaFmtoEsp(date('Y-m-d')), 'center');

        $ancho_linea = $ancho_linea + 102;

        //CUADRO DE LA ORDEN TRANSPORTE
        //TITULO DEL CUADRO
        $pdf->setStrokeColor(0.3, 0.3, 0.3); //GRIS
        $pdf->setLineStyle(15);
        //PINTAMOS LINEA
        $pdf->line($ancho_linea, $altura_posicion_cuadro_1, $ancho_linea + 110, $altura_posicion_cuadro_1);
        $pdf->setColor(1, 1, 1);
        //PINTAMOS EL TITULO
        $pdf->addTextWrap($ancho_linea, $altura_posicion_cuadro_1 - 3, 110, $tamanoLetraCuadros, ucfirst($auxiliar->traduce("Orden de Transporte", $idIdiomaImpresion)), 'center');


        //CUADRO DE DATOS
        $pdf->setColor(0, 0, 0);
        $pdf->setLineStyle(1);
        $pdf->rectangle($ancho_linea, $altura_superior_cuadro_1 - $altura_cuadro_1, 110, $altura_cuadro_1);

        //DATOS
        $pdf->addTextWrap($ancho_linea, $altura_posicion_cuadro_1 - 20, 110, $tamano_letra, $rowOrdenContratacion->ID_ORDEN_TRANSPORTE, 'center');


        $ancho_linea = $ancho_linea + 110;

        //CUADRO DEL SERVICIO
        //TITULO DEL CUADRO
        $pdf->setStrokeColor(0.3, 0.3, 0.3); //GRIS
        $pdf->setLineStyle(15);
        //PINTAMOS LINEA
        $pdf->line($ancho_linea, $altura_posicion_cuadro_1, $ancho_linea + 250, $altura_posicion_cuadro_1);
        $pdf->setColor(1, 1, 1);
        //PINTAMOS EL TITULO
        $pdf->addTextWrap($ancho_linea, $altura_posicion_cuadro_1 - 3, 250, $tamanoLetraCuadros, ucfirst($auxiliar->traduce("Tipo Servicio", $idIdiomaImpresion)), 'center');


        //CUADRO DE DATOS
        $pdf->setColor(0, 0, 0);
        $pdf->setLineStyle(1);
        $pdf->rectangle($ancho_linea, $altura_superior_cuadro_1 - $altura_cuadro_1, 250, $altura_cuadro_1);

        //DATOS
        //PINTAMOS  Y BAJAMOS DE ALTURA
        $pdf->addTextWrap($ancho_linea, $altura_posicion_cuadro_1 - 20, 250, $tamano_letra, (($idIdiomaImpresion == "ESP") ? $rowServicio->NOMBRE : $rowServicio->NOMBRE_ENG), 'center');


        //FILA AZUL PARA SEPARAR DATOS DE CABECERA DE CABECERA DE LINEAS
        $pdf->setStrokeColor(0, 0, 100); //AZUL
        $pdf->setLineStyle(3);
        $pdf->line(20, $altura_posicion_cuadro_1 - 40, 575, $altura_posicion_cuadro_1 - 40); //LINEA AZUL


        //GESTOR TRANSPORTE
        //DEFINO LAS COORDENADAS DE LA SEGUNDA FILAS (EJE Y)
        $altura_posicion_cuadro_1 = $altura_posicion_cuadro_1 - 60;
        $ancho_linea              = $columna_1 - 3;

        //BUSCAMOS EL GESTOR DE TRANSPORTE (USUARIO CREACION CONTRATACION)
        $rowGestorTransporte = $bd->VerReg("ADMINISTRADOR", "ID_ADMINISTRADOR", $rowOrdenContratacion->ID_ADMINISTRADOR_CREACION);

        //DESCRIPCION ORDEN CONTRATACION
        $pdf->setColor(0, 0, 0);
        $pdf->addText($ancho_linea, $altura_posicion_cuadro_1 - 3, $tamanoLetraCuadros, '<b>' . $auxiliar->traduce("Gestor Servicio", $idIdiomaImpresion) . ':</b>');

        //PINTAMOS  Y BAJAMOS DE ALTURA
        $txTelefonos = ($rowGestorTransporte->TELEFONO_FIJO != "" ? $rowGestorTransporte->TELEFONO_FIJO . ($rowGestorTransporte->TELEFONO_MOVIL != "" ? " / " : "") : "") . ($rowGestorTransporte->TELEFONO_MOVIL != "" ? $rowGestorTransporte->TELEFONO_MOVIL : "");
        $pdf->addTextWrap($ancho_linea + 10, $altura_posicion_cuadro_1 - 17, 550, $tamano_letra, '<b>' . $rowGestorTransporte->NOMBRE . '</b> - @: ' . $rowGestorTransporte->EMAIL . ' - ' . $auxiliar->traduce("Telefono", $idIdiomaImpresion) . ": " . $txTelefonos, 'left');

        if ($rowOrdenContratacion->DESCRIPCION != null && $rowOrdenContratacion->DESCRIPCION != ''):
            //POBLACIONES
            $pdf->setColor(0, 0, 0);
            $pdf->addText($ancho_linea, $altura_posicion_cuadro_1 - 34, $tamanoLetraCuadros, '<b>' . $auxiliar->traduce("Poblaciones", $idIdiomaImpresion) . ':</b>');

            //PINTAMOS  Y BAJAMOS DE ALTURA
            $numLineasPintadas = $this->pintaTextoEnVariasLineas("<b>" . $rowOrdenContratacion->DESCRIPCION . " </b>", 550, $ancho_linea + 10, $altura_posicion_cuadro_1 - 48, $tamano_letra, 10, '', '');

            //ACTUALIZO LA FILA DONDE IMPRIMIR
            $fila = $fila - (200 + ($numLineasPintadas * 10));
        else:
            //ACTUALIZO LA FILA DONDE IMPRIMIR
            $fila = $fila - 185;
        endif;

        //FILA NEGRO PARA SEPARAR DATOS DE CABECERA DE CABECERA DE LINEAS
        $pdf->setStrokeColor(0, 0, 0); //NEGRO
        $pdf->setLineStyle(1);
        $pdf->line(20, $fila - 6, 575, $fila - 6); //LINEA NEGRO

        //REDEFINO LA ALTURA DE LA FILA
        $fila = $fila - 25;

        //NEGRO
        $pdf->setStrokeColor(0, 0, 0);
        $pdf->setLineStyle(1);


        //ACTUALIZO TOTAL A CERO LINEAS IMPRESAS
        $total = 0;
    }

    //SI SE PASA UNA DIRECCION ESPECIFICA SOLO PINTA LOS DATOS RELACIONADOS CON ESA DIRECCION, SIN MOSTRAR EL ORDEN
    function crearCuerpoContratacion($idOrdenContratacion, $idDireccionEspecifica = "")
    {
        global $auxiliar;
        global $bd;
        global $pdf;

        global $fila;
        global $total;

        global $tamano_letra;
        global $tamanoLetraCuadros;

        global $idIdiomaImpresion;
        global $cantXpag;


        //BUSCO LA ORDEN DE CONTRATACION
        $rowOrdenContratacion = $bd->VerReg("ORDEN_CONTRATACION", "ID_ORDEN_CONTRATACION", $idOrdenContratacion);

        //BUSCO LAS DIRECCIONES A IMPRIMIR
        $sqlLineas = " SELECT D.*, CD.*, D.OBSERVACIONES AS OBSERVACIONES_DIRECCION, CD.OBSERVACIONES AS OBSERVACIONES_DESTINO
                      FROM ORDEN_CONTRATACION_DESTINO CD
                      INNER JOIN DIRECCION D ON D.ID_DIRECCION = CD.ID_DIRECCION
                      WHERE CD.ID_ORDEN_CONTRATACION = $rowOrdenContratacion->ID_ORDEN_CONTRATACION AND CD.BAJA=0 ";

        if ($idDireccionEspecifica != "")://SI TRAE DIRECCION ESPECIFICA, FILTRAMOS
            $sqlLineas .= " AND D.ID_DIRECCION = $idDireccionEspecifica ";
        endif;

        $sqlLineas .= " ORDER BY CD.ORDEN ASC";

        $resultLineas = $bd->ExecSQL($sqlLineas);

        //RECORRO LAS LINEAS DE ALBARAN AGRUPADAS
        while ($rowLinea = $bd->SigReg($resultLineas)):

            //NUMERO DE LINEAS A IMPRIMIR
            $numLineas = 6; //4 LINEAS DIRECCION + LINEA FECHA Y TIPO SERVICIO  + LINEA EN BLANCO

            //OBTENEMOS EL NUMERO DE LINEAS DE LAS OBSERVACIONES DE DESTINO
            $numLineasObservaciones = 0;
            if ($rowLinea->OBSERVACIONES_DESTINO != ""):
                $numLineasObservaciones = $this->numeroLineasTexto($rowLinea->OBSERVACIONES_DESTINO, 400, $tamano_letra);
            endif;

            //OBTENEMOS EL NUMERO DE LINEAS DE LAS OBSERVACIONES DE DESTINO
            //EN LA NUEVA TABLA BULTO_FICTICIO_CONTRATACION
            $sqlBultosFicticios = "SELECT * FROM BULTO_FICTICIO_CONTRATACION WHERE BAJA = 0 ";
            if ($rowLinea->TIPO == "Entrega"):
                $sqlBultosFicticios .= " AND ID_ORDEN_CONTRATACION_DESTINO_ENTREGA = $rowLinea->ID_ORDEN_CONTRATACION_DESTINO ";
            elseif ($rowLinea->TIPO == "Recogida"):
                $sqlBultosFicticios .= " AND ID_ORDEN_CONTRATACION_DESTINO_RECOGIDA = $rowLinea->ID_ORDEN_CONTRATACION_DESTINO ";
            elseif ($rowLinea->TIPO == "Recogida y Entrega"):
                $sqlBultosFicticios .= " AND (ID_ORDEN_CONTRATACION_DESTINO_ENTREGA = $rowLinea->ID_ORDEN_CONTRATACION_DESTINO OR ID_ORDEN_CONTRATACION_DESTINO_RECOGIDA = $rowLinea->ID_ORDEN_CONTRATACION_DESTINO) ";
            endif;
            $resultBultosFicticios = $bd->ExecSQL($sqlBultosFicticios, "No");
            /*$numLineasBultosFicticios = $bd->NumRegs($resultBultosFicticios);

            if ($numLineasBultosFicticios > 0):
                //CONTAMOS LOS SALTOS DE LINEA Y SUMAMOS DOS (LA LINEA GRIS Y EL TITULO DE LOS BULTOS)
                $numLineasBultosFicticios = 2 + $numLineasBultosFicticios;
            endif;*/

            //BUSCO SI TIENE PERSONA DE CONTACTO
            $numLineasPersonasContacto    = 0;
            $rowPersonaContacto           = false;
            $sqlDestinoPersonaContacto    = "SELECT * FROM ORDEN_CONTRATACION_DESTINO_DIRECCION_PERSONA_CONTACTO WHERE BAJA = 0 AND ID_ORDEN_CONTRATACION_DESTINO = $rowLinea->ID_ORDEN_CONTRATACION_DESTINO ";
            $resultDestinoPersonaContacto = $bd->ExecSQL($sqlDestinoPersonaContacto, "No");
            $numLineasPersonasContacto    = $bd->NumRegs($resultDestinoPersonaContacto);

            //BUSCO SI TIENE HORARIO DEFINIDO
            $numLineasHorario = 0;
            $textoHorario     = "";
            if (($rowLinea->HORARIO_DESDE_1 != NULL) || ($rowLinea->HORARIO_HASTA_1 != NULL) || ($rowLinea->HORARIO_DESDE_2 != NULL) || ($rowLinea->HORARIO_HASTA_2 != NULL)):
                //ESTABLEZCO EL TEXTO DEL HORARIO
                $textoHorario = ucfirst($auxiliar->traduce("de", $idIdiomaImpresion)) . " " . $auxiliar->fechaFmtoEspHora($rowLinea->HORARIO_DESDE_1, true, true, false) . " " . $auxiliar->traduce("A_TO", $idIdiomaImpresion) . " " . $auxiliar->fechaFmtoEspHora($rowLinea->HORARIO_HASTA_1, true, true, false);
                if (($rowLinea->HORARIO_DESDE_2 != NULL) || ($rowLinea->HORARIO_HASTA_2 != NULL)):
                    $textoHorario = $textoHorario . " " . $auxiliar->traduce("Y_DE_HORARIO", $idIdiomaImpresion) . " " . $auxiliar->fechaFmtoEspHora($rowLinea->HORARIO_DESDE_2, true, true, false) . " " . $auxiliar->traduce("A_TO", $idIdiomaImpresion) . " " . $auxiliar->fechaFmtoEspHora($rowLinea->HORARIO_HASTA_2, true, true, false);
                endif;

                if ($textoHorario != ""):
                    $numLineasHorario = 1;
                endif;
            endif;

            //BUSCO SI TIENE OBSERVACIONES DE HORARIO
            $numLineasObservacionesHorario = 0;
            $observacionesHorario          = "";
            if ($rowLinea->HORARIO_OBSERVACIONES != ""):
                //ESTABLEZCO LAS OBSERVACIONES DEL HORARIO
                $observacionesHorario = $rowLinea->HORARIO_OBSERVACIONES;

                if ($observacionesHorario != ""):
                    $numLineasObservacionesHorario = 1;
                endif;
            endif;


            //CALCULO EL NUMERO DE LINEAS A USAR
            $numLineas = $numLineas + $numLineasObservaciones + $numLineasPersonasContacto + $numLineasHorario + $numLineasObservacionesHorario;

            if ($cantXpag <= $total + $numLineas):
                $pdf->ezNewPage();
                $this->crearCabeceraContratacion($rowOrdenContratacion->ID_ORDEN_CONTRATACION);

                //$cantXpag = 61;
                //$fila = 760;
            endif;

            //OBTENGO FECHA Y HORA
            $fechaEjecucion = $auxiliar->fechaFmtoEsp($rowLinea->FECHA_SERVICIO);
            $horaEjecucion  = "";
            if ($rowLinea->HORA_SERVICIO != ""):
                $horaEjecucion = $auxiliar->fechaFmtoEspHora($rowLinea->FECHA_SERVICIO . " " . $rowLinea->HORA_SERVICIO, true, true, false); //CONVERTIR, RETORNAR SOLO HORA Y SIN SEGUNDOS
            endif;


            //DIRECCION
            if ($idDireccionEspecifica == "")://SOLO MOSTRAMOS EL Nº DE DIRECCION SI NO ES UN IMPRESO ESPECIFICO
                $pdf->addText(40, $fila, $tamano_letra, "<b>" . $auxiliar->traduce("Direccion", $idIdiomaImpresion) . " Nº " . $rowLinea->ORDEN . "</b>");
            endif;


            //ESCRIBO LOS DATOS DE LA DIRECCION
            $pdf->addText(150, $fila, $tamano_letra, "<b>" . $rowLinea->DENOMINACION . "</b>");

            $pdf->addText(150, $fila - 10, $tamano_letra, $rowLinea->DIRECCION);
            $pdf->addText(150, $fila - 20, $tamano_letra, $rowLinea->CODIGO_POSTAL . " " . $rowLinea->POBLACION);
            $pdf->addText(150, $fila - 30, $tamano_letra, $rowLinea->REGION . " " . $auxiliar->obtenerDescripcionPais($rowLinea->ID_PAIS, $idIdiomaImpresion));

            //ACTUALIZO EL VALOR DE FILA
            $fila = $fila - 43;

            //FECHA Y TIPO SERVICIO
            $pdf->addText(40, $fila, $tamano_letra, "<b>" . $auxiliar->traduce("Fecha y Hora", $idIdiomaImpresion) . ": </b>");
            $pdf->setColor(1, 0, 0);
            $pdf->addText(150, $fila, $tamano_letra, $fechaEjecucion . ($horaEjecucion != "" ? " $horaEjecucion" : ""));
            $pdf->setColor(0, 0, 0);

            $pdf->addText(380, $fila, $tamano_letra, "<b>" . $auxiliar->traduce("Tipo Servicio", $idIdiomaImpresion) . ": </b>" . $auxiliar->traduce("$rowLinea->TIPO", $idIdiomaImpresion));

            //ACTUALIZO EL VALOR DE FILA
            $fila = $fila - 13;


            //PINTAMOS LA PERSONA DE CONTACTO
            if ($numLineasPersonasContacto > 0):
                $k = 1;
                while ($rowDireccionPersonaContacto = $bd->SigReg($resultDestinoPersonaContacto)):
                    $rowPersonaContacto = $bd->VerReg("DIRECCION_PERSONA_CONTACTO", "ID_DIRECCION_PERSONA_CONTACTO", $rowDireccionPersonaContacto->ID_DIRECCION_PERSONA_CONTACTO, "No");

                    $txTelefonos = ($rowPersonaContacto->TELEFONO_FIJO != "" ? " " . $rowPersonaContacto->TELEFONO_FIJO . ($rowPersonaContacto->TELEFONO_MOVIL != "" ? " /" : "") : "") . ($rowPersonaContacto->TELEFONO_MOVIL != "" ? " " . $rowPersonaContacto->TELEFONO_MOVIL : "");
                    $pdf->addText(40, $fila, $tamano_letra, "<b>" . $auxiliar->traduce("Persona de Contacto", $idIdiomaImpresion) . " $k: </b>");
                    $pdf->addText(150, $fila, $tamano_letra, $rowPersonaContacto->NOMBRE . " $txTelefonos");

                    //ACTUALIZO EL VALOR DE FILA
                    $fila = $fila - 13;
                    $k++;
                endwhile;
            endif;


            //PINTAMOS EL HORARIO
            if ($textoHorario != ""):
                $pdf->addText(40, $fila, $tamano_letra, "<b>" . $auxiliar->traduce("Horario", $idIdiomaImpresion) . ": </b>");
                $pdf->addText(150, $fila, $tamano_letra, $textoHorario);

                //ACTUALIZO EL VALOR DE FILA
//                $fila = $fila - 13;
            endif;

            $numLineasObservacionesHorarioN = 1;
            //PINTAMOS LAS OBSERVACIONES DEL HORARIO
            if ($observacionesHorario != ""):
//                $pdf->addText(380, $fila, $tamano_letra, "<b>" . $auxiliar->traduce("Observaciones Horario", $idIdiomaImpresion) . ": </b>" . $observacionesHorario);
                $numLineasObservacionesHorarioN = $this->numeroLineasTexto("<b>" . $auxiliar->traduce("Observaciones Horario", $idIdiomaImpresion) . ": </b>" . $observacionesHorario, 160, $tamano_letra);
                $this->pintaTextoEnVariasLineas("<b>" . $auxiliar->traduce("Observaciones Horario", $idIdiomaImpresion) . ": </b>" . $observacionesHorario, 200, 380, $fila, $tamano_letra, 10, '', '');
//                $pdf->addText(40, $fila, $tamano_letra, "<b>" . $auxiliar->traduce("Observaciones Horario", $idIdiomaImpresion) . ": </b>");
//                $pdf->addText(150, $fila, $tamano_letra, $observacionesHorario);

                //ACTUALIZO EL VALOR DE FILA
//                $fila = $fila - 13;
            endif;
            if ($textoHorario != "" || $observacionesHorario != ""):
                $fila = $fila - (($numLineasObservacionesHorarioN) * 13);
            endif;


            //PINTAMOS LAS OBSERVACIONES EN ROJO
            if ($rowLinea->OBSERVACIONES_DESTINO != ""):
                $pdf->addText(40, $fila, $tamano_letra, "<b>" . $auxiliar->traduce("Observaciones", $idIdiomaImpresion) . ": </b>");
                $pdf->setColor(1, 0, 0);

                $this->pintaTextoEnVariasLineas($rowLinea->OBSERVACIONES_DESTINO, 400, 150, $fila, $tamano_letra, 10, '', '');

                $pdf->setColor(0, 0, 0);
            endif;

            //ACTUALIZO EL VALOR DE FILA
            $fila = $fila - (($numLineasObservaciones) * 10);

            //ACTUALIZO LA VARIABLE TOTAL
            $total = $total + $numLineas;

            //PINTAMOS LOS BULTOS FICTICIOS EN ROJO
            if ($bd->NumRegs($resultBultosFicticios) > 0):
                //ACTUALIZO LA VARIABLE TOTAL
                //$total += $bd->NumRegs($resultBultosFicticios);
                if ($cantXpag <= $total):
                    $pdf->ezNewPage();
                    $this->crearCabeceraContratacion($rowOrdenContratacion->ID_ORDEN_CONTRATACION);
                    //$total += $bd->NumRegs($resultBultosFicticios) + 3;
                    $total += 3;
                endif;
                $fila = $fila - 45;

                //TITULO DEL CUADRO
                $pdf->setStrokeColor(0.7, 0.7, 0.7); //GRIS
                $pdf->setLineStyle(1);
                //PINTAMOS LINEA
                $pdf->rectangle(30, $fila + 10, 545, 30);
                $pdf->setStrokeColor(0, 0, 0);
                $pdf->line(30, $fila + 10, 575, $fila + 10);

                //PINTAMOS COLUMNA DEL TITULO
                $pdf->addTextWrap(20, $fila + 20, 80, $tamanoLetraCuadros, "<b>" . ucfirst($auxiliar->traduce("Nº Bulto", $idIdiomaImpresion)) . "</b>", 'center');
                //PINTAMOS LINEA
                $pdf->setStrokeColor(0.7, 0.7, 0.7); //GRIS
                $pdf->setLineStyle(1);
                $pdf->line(90, $fila + 40, 90, $fila + 10);

                //PINTAMOS COLUMNA DEL TITULO
                $pdf->addTextWrap(95, $fila + 20, 80, $tamanoLetraCuadros, "<b>" . ucfirst($auxiliar->traduce("Remontable (X)", $idIdiomaImpresion)) . "</b>", 'center');
                //PINTAMOS LINEA
                $pdf->setStrokeColor(0.7, 0.7, 0.7); //GRIS
                $pdf->setLineStyle(1);
                $pdf->line(180, $fila + 40, 180, $fila + 10);

                //PINTAMOS COLUMNA DEL TITULO
                $pdf->addTextWrap(185, $fila + 20, 100, $tamanoLetraCuadros, "<b>" . ucfirst($auxiliar->traduce("Contenido", $idIdiomaImpresion)) . "</b>", 'center');
                //PINTAMOS LINEA
                $pdf->setStrokeColor(0.7, 0.7, 0.7); //GRIS
                $pdf->setLineStyle(1);
                $pdf->line(285, $fila + 40, 285, $fila + 10);

                //PINTAMOS COLUMNA DEL TITULO
                $pdf->addTextWrap(285, $fila + 15, 125, $tamanoLetraCuadros, "<b>" . ucfirst($auxiliar->traduce("Dimensiones", $idIdiomaImpresion)) . "</b>", 'center');
                $pdf->addTextWrap(285, $fila + 25, 125, $tamanoLetraCuadros, "<b>" . ucfirst($auxiliar->traduce("(Largo x Ancho x Alto)", $idIdiomaImpresion)) . "</b>", 'center');
                //PINTAMOS LINEA
                $pdf->setStrokeColor(0.7, 0.7, 0.7); //GRIS
                $pdf->setLineStyle(1);
                $pdf->line(410, $fila + 40, 410, $fila + 10);

                //PINTAMOS COLUMNA DEL TITULO
                $pdf->addTextWrap(410, $fila + 20, 45, $tamanoLetraCuadros, "<b>" . ucfirst($auxiliar->traduce("Peso", $idIdiomaImpresion)) . "</b>", 'center');
                //PINTAMOS LINEA
                $pdf->setStrokeColor(0.7, 0.7, 0.7); //GRIS
                $pdf->setLineStyle(1);
                $pdf->line(455, $fila + 40, 455, $fila + 10);

                //PINTAMOS COLUMNA DEL TITULO
                $pdf->addTextWrap(455, $fila + 20, 60, $tamanoLetraCuadros, "<b>" . ucfirst($auxiliar->traduce("Volumen", $idIdiomaImpresion)) . "</b>", 'center');
                //PINTAMOS LINEA
                $pdf->setStrokeColor(0.7, 0.7, 0.7); //GRIS
                $pdf->setLineStyle(1);
                $pdf->line(515, $fila + 40, 515, $fila + 10);

                //PINTAMOS COLUMNA DEL TITULO
                $pdf->addTextWrap(515, $fila + 20, 60, $tamanoLetraCuadros, "<b>" . ucfirst($auxiliar->traduce("Accion", $idIdiomaImpresion)) . "</b>", 'center');
                //PINTAMOS LINEA
                $pdf->setStrokeColor(0.7, 0.7, 0.7); //GRIS
                $pdf->setLineStyle(1);
                $pdf->line(575, $fila + 40, 575, $fila + 10);

                $fila = $fila - 30;

                $i = 0;
                while ($rowBultosFicticio = $bd->SigReg($resultBultosFicticios)):
                    $total++;

                    if ($cantXpag <= $total):
                        $pdf->ezNewPage();
                        $this->crearCabeceraContratacion($rowOrdenContratacion->ID_ORDEN_CONTRATACION);
                        $total += 3;
                        $fila  = $fila - 30;
                    endif;

                    //DESCRIPCION BULTO
                    if (strlen($rowBultosFicticio->DESCRIPCION) > 25):
                        $descBulto = substr($rowBultosFicticio->DESCRIPCION, 0, 22) . "...";
                    elseif ($rowBultosFicticio->DESCRIPCION == "")://SI NO TIENE(PARA BULTOS PDA PUEDE PASAR), MOSTRAMOS LA POR DEFECTO
                        $descBulto = $auxiliar->traduce("Repuestos E. renovables", $idIdiomaImpresion);
                    else:
                        $descBulto = $rowBultosFicticio->DESCRIPCION;
                    endif;

                    //TITULO DEL CUADRO
                    $pdf->setStrokeColor(0.7, 0.7, 0.7); //GRIS
                    $pdf->setLineStyle(1);
                    //PINTAMOS LINEA
                    $pdf->rectangle(30, $fila + 25, 545, 15);

                    if ($i == 0):
                        $pdf->setStrokeColor(0, 0, 0);
                        $pdf->line(30, $fila + 40, 575, $fila + 40);
                    endif;

                    //PINTAMOS COLUMNA DEL TITULO
                    $pdf->addTextWrap(20, $fila + 30, 80, $tamano_letra, $rowBultosFicticio->REFERENCIA, 'center');
                    //PINTAMOS LINEA
                    $pdf->setStrokeColor(0.7, 0.7, 0.7); //GRIS
                    $pdf->setLineStyle(1);
                    $pdf->line(90, $fila + 40, 90, $fila + 25);

                    //PINTAMOS COLUMNA DEL TITULO
                    $pdf->addTextWrap(95, $fila + 30, 80, $tamano_letra, ($rowBultosFicticio->PERMITE_REMONTABILIDAD == '1' ? strtoupper($auxiliar->traduce('Si', $idIdiomaImpresion)) : strtoupper($auxiliar->traduce('No', $idIdiomaImpresion))), 'center');
                    //PINTAMOS LINEA
                    $pdf->setStrokeColor(0.7, 0.7, 0.7); //GRIS
                    $pdf->setLineStyle(1);
                    $pdf->line(180, $fila + 40, 180, $fila + 25);

                    //PINTAMOS COLUMNA DEL TITULO
                    $pdf->addTextWrap(185, $fila + 30, 100, $tamano_letra, $descBulto, 'center');
                    //PINTAMOS LINEA
                    $pdf->setStrokeColor(0.7, 0.7, 0.7); //GRIS
                    $pdf->setLineStyle(1);
                    $pdf->line(285, $fila + 40, 285, $fila + 25);

                    //PINTAMOS COLUMNA DEL TITULO
                    $pdf->addTextWrap(285, $fila + 30, 125, $tamano_letra, "$rowBultosFicticio->LARGO X $rowBultosFicticio->ANCHO X $rowBultosFicticio->ALTO mm", 'center');
                    //PINTAMOS LINEA
                    $pdf->setStrokeColor(0.7, 0.7, 0.7); //GRIS
                    $pdf->setLineStyle(1);
                    $pdf->line(410, $fila + 40, 410, $fila + 25);

                    //PINTAMOS COLUMNA DEL TITULO
                    $pdf->addTextWrap(410, $fila + 30, 45, $tamano_letra, $auxiliar->formatoNumero($rowBultosFicticio->PESO) . " Kg", 'center');
                    //PINTAMOS LINEA
                    $pdf->setStrokeColor(0.7, 0.7, 0.7); //GRIS
                    $pdf->setLineStyle(1);
                    $pdf->line(455, $fila + 40, 455, $fila + 25);

                    //PINTAMOS COLUMNA DEL TITULO
                    $pdf->addTextWrap(455, $fila + 30, 60, $tamano_letra, $auxiliar->formatoNumero(($rowBultosFicticio->LARGO * $rowBultosFicticio->ANCHO * $rowBultosFicticio->ALTO) / (1000 * 1000 * 1000)) . " m3", 'center');
                    //PINTAMOS LINEA
                    $pdf->setStrokeColor(0.7, 0.7, 0.7); //GRIS
                    $pdf->setLineStyle(1);
                    $pdf->line(515, $fila + 40, 515, $fila + 25);

                    //PINTAMOS COLUMNA DEL TITULO
                    $pdf->addTextWrap(515, $fila + 30, 60, $tamano_letra, $auxiliar->traduce($rowBultosFicticio->TIPO_DESTINO_BULTO, $idIdiomaImpresion), 'center');
                    //PINTAMOS LINEA
                    $pdf->setStrokeColor(0.7, 0.7, 0.7); //GRIS
                    $pdf->setLineStyle(1);
                    $pdf->line(575, $fila + 40, 575, $fila + 25);

                    //ACTUALIZO EL VALOR DE FILA
                    $fila = $fila - 15;
                    $i++;
                endwhile;

                //AÑADO LA FILA DE INFORMACION DE REMONTABILIDAD
                $total++;

                if ($cantXpag <= ($total - 1)):
                    $pdf->ezNewPage();
                    $this->crearCabeceraContratacion($rowOrdenContratacion->ID_ORDEN_CONTRATACION);
                    $total += 2;
                endif;
                //TITULO DEL CUADRO
                $pdf->setStrokeColor(0.7, 0.7, 0.7); //GRIS
                $pdf->setLineStyle(1);
                //PINTAMOS LINEA
                $pdf->rectangle(30, $fila + 25, 545, 15);

                //PINTAMOS LINEA
                $pdf->setStrokeColor(0.7, 0.7, 0.7); //GRIS
                $pdf->setLineStyle(1);
                $pdf->line(90, $fila + 40, 90, $fila + 25);

                //PINTAMOS COLUMNA DEL TITULO
                $pdf->addTextWrap(95, $fila + 30, 80, $tamano_letra, "(X)", 'center');
                //PINTAMOS LINEA
                $pdf->setStrokeColor(0.7, 0.7, 0.7); //GRIS
                $pdf->setLineStyle(1);
                $pdf->line(180, $fila + 40, 180, $fila + 25);

                //PINTAMOS COLUMNA DEL TITULO
                $pdf->addTextWrap(185, $fila + 30, 390, $tamano_letra, $auxiliar->traduce('texto_informativo_mercancia_remontable', $idIdiomaImpresion), 'left');
                //PINTAMOS LINEA
                $pdf->setStrokeColor(0.7, 0.7, 0.7); //GRIS
                $pdf->setLineStyle(1);
                $pdf->line(575, $fila + 40, 575, $fila + 25);


                $fila = $fila + 20;
            endif;

            //REALIZAMOS LA BUSQUEDA
            $sqlBultosDireccion    = "SELECT DISTINCT B.*, CONCAT(B.LARGO, ' X ', B.ANCHO, ' X ', B.ALTO) AS DIMENSIONES, ((B.LARGO / 1000) * (B.ANCHO / 1000) * (B.ALTO / 1000)) AS VOLUMEN, BCD.TIPO_DESTINO_BULTO
                                      FROM EXPEDICION E
                                      INNER JOIN ORDEN_TRANSPORTE OT ON OT.ID_ORDEN_TRANSPORTE = E.ID_ORDEN_TRANSPORTE
                                      INNER JOIN BULTO B ON B.ID_EXPEDICION = E.ID_EXPEDICION
                                      INNER JOIN BULTO_CONTRATACION_DESTINO BCD ON BCD.ID_BULTO = B.ID_BULTO
                                      WHERE E.BAJA = 0 AND BCD.BAJA = 0 AND OT.ID_ORDEN_TRANSPORTE = $rowOrdenContratacion->ID_ORDEN_TRANSPORTE AND BCD.ID_ORDEN_CONTRATACION_DESTINO = $rowLinea->ID_ORDEN_CONTRATACION_DESTINO
                                      ORDER BY BCD.TIPO_DESTINO_BULTO";
            $resultBultosDireccion = $bd->ExecSQL($sqlBultosDireccion);

            //PINTAMOS EL TITULO SI HAY LINEAS Y SI NO LO HABIAMOS PINTADO PARA LOS FICTICIOS
            if ($bd->NumRegs($resultBultosDireccion) > 0 && $bd->NumRegs($resultBultosFicticios) == 0):
                if ($cantXpag <= $total):
                    $pdf->ezNewPage();
                    $this->crearCabeceraContratacion($rowOrdenContratacion->ID_ORDEN_CONTRATACION);
                    //$total += $bd->NumRegs($resultBultosDireccion);
                    $total += 3;
                endif;

                $pdf->addText(40, $fila, $tamano_letra, "<b>" . $auxiliar->traduce("Contenido", $idIdiomaImpresion) . ": </b>");

                //ACTUALIZO LA VARIABLE TOTAL
                $total += 2;
            endif;


            if ($bd->NumRegs($resultBultosDireccion) > 0):
                if ($cantXpag <= $total):
                    $pdf->ezNewPage();
                    $this->crearCabeceraContratacion($rowOrdenContratacion->ID_ORDEN_CONTRATACION);
                    $total += $bd->NumRegs($resultBultosDireccion) + 3;
                endif;
                $fila = $fila - 45;

                //TITULO DEL CUADRO
                $pdf->setStrokeColor(0.7, 0.7, 0.7); //GRIS
                $pdf->setLineStyle(1);
                //PINTAMOS LINEA
                $pdf->rectangle(30, $fila + 10, 545, 30);
                $pdf->setStrokeColor(0, 0, 0);
                $pdf->line(30, $fila + 10, 575, $fila + 10);

                //PINTAMOS COLUMNA DEL TITULO
                $pdf->addTextWrap(20, $fila + 20, 80, $tamanoLetraCuadros, "<b>" . ucfirst($auxiliar->traduce("Nº Bulto", $idIdiomaImpresion)) . "</b>", 'center');
                //PINTAMOS LINEA
                $pdf->setStrokeColor(0.7, 0.7, 0.7); //GRIS
                $pdf->setLineStyle(1);
                $pdf->line(90, $fila + 40, 90, $fila + 10);

                //PINTAMOS COLUMNA DEL TITULO
                $pdf->addTextWrap(95, $fila + 20, 80, $tamanoLetraCuadros, "<b>" . ucfirst($auxiliar->traduce("Remontable (X)", $idIdiomaImpresion)) . "</b>", 'center');
                //PINTAMOS LINEA
                $pdf->setStrokeColor(0.7, 0.7, 0.7); //GRIS
                $pdf->setLineStyle(1);
                $pdf->line(180, $fila + 40, 180, $fila + 10);

                //PINTAMOS COLUMNA DEL TITULO
                $pdf->addTextWrap(185, $fila + 20, 100, $tamanoLetraCuadros, "<b>" . ucfirst($auxiliar->traduce("Contenido", $idIdiomaImpresion)) . "</b>", 'center');
                //PINTAMOS LINEA
                $pdf->setStrokeColor(0.7, 0.7, 0.7); //GRIS
                $pdf->setLineStyle(1);
                $pdf->line(285, $fila + 40, 285, $fila + 10);

                //PINTAMOS COLUMNA DEL TITULO
                $pdf->addTextWrap(285, $fila + 15, 125, $tamanoLetraCuadros, "<b>" . ucfirst($auxiliar->traduce("Dimensiones", $idIdiomaImpresion)) . "</b>", 'center');
                $pdf->addTextWrap(285, $fila + 25, 125, $tamanoLetraCuadros, "<b>" . ucfirst($auxiliar->traduce("(Largo x Ancho x Alto)", $idIdiomaImpresion)) . "</b>", 'center');
                //PINTAMOS LINEA
                $pdf->setStrokeColor(0.7, 0.7, 0.7); //GRIS
                $pdf->setLineStyle(1);
                $pdf->line(410, $fila + 40, 410, $fila + 10);

                //PINTAMOS COLUMNA DEL TITULO
                $pdf->addTextWrap(410, $fila + 20, 45, $tamanoLetraCuadros, "<b>" . ucfirst($auxiliar->traduce("Peso", $idIdiomaImpresion)) . "</b>", 'center');
                //PINTAMOS LINEA
                $pdf->setStrokeColor(0.7, 0.7, 0.7); //GRIS
                $pdf->setLineStyle(1);
                $pdf->line(455, $fila + 40, 455, $fila + 10);

                //PINTAMOS COLUMNA DEL TITULO
                $pdf->addTextWrap(455, $fila + 20, 60, $tamanoLetraCuadros, "<b>" . ucfirst($auxiliar->traduce("Volumen", $idIdiomaImpresion)) . "</b>", 'center');
                //PINTAMOS LINEA
                $pdf->setStrokeColor(0.7, 0.7, 0.7); //GRIS
                $pdf->setLineStyle(1);
                $pdf->line(515, $fila + 40, 515, $fila + 10);

                //PINTAMOS COLUMNA DEL TITULO
                $pdf->addTextWrap(515, $fila + 20, 60, $tamanoLetraCuadros, "<b>" . ucfirst($auxiliar->traduce("Accion", $idIdiomaImpresion)) . "</b>", 'center');
                //PINTAMOS LINEA
                $pdf->setStrokeColor(0.7, 0.7, 0.7); //GRIS
                $pdf->setLineStyle(1);
                $pdf->line(575, $fila + 40, 575, $fila + 10);

                $fila = $fila - 30;

                $i = 0;
                while ($rowBultosDireccion = $bd->SigReg($resultBultosDireccion)):
                    $total++;

                    if ($cantXpag <= $total):
                        $pdf->ezNewPage();
                        $this->crearCabeceraContratacion($rowOrdenContratacion->ID_ORDEN_CONTRATACION);
                        $total += 3;
                        $fila  = $fila - 30;
                    endif;

                    //DESCRIPCION BULTO
                    if (strlen($rowBultosDireccion->DESCRIPCION) > 25):
                        $descBulto = substr($rowBultosDireccion->DESCRIPCION, 0, 22) . "...";
                    elseif ($rowBultosDireccion->DESCRIPCION == "")://SI NO TIENE(PARA BULTOS PDA PUEDE PASAR), MOSTRAMOS LA POR DEFECTO
                        $descBulto = $auxiliar->traduce("Repuestos E. renovables", $idIdiomaImpresion);
                    else:
                        $descBulto = $rowBultosDireccion->DESCRIPCION;
                    endif;

                    //TITULO DEL CUADRO
                    $pdf->setStrokeColor(0.7, 0.7, 0.7); //GRIS
                    $pdf->setLineStyle(1);
                    //PINTAMOS LINEA
                    $pdf->rectangle(30, $fila + 25, 545, 15);

                    if ($i == 0):
                        $pdf->setStrokeColor(0, 0, 0);
                        $pdf->line(30, $fila + 40, 575, $fila + 40);
                    endif;

                    //PINTAMOS COLUMNA DEL TITULO
                    $pdf->addTextWrap(20, $fila + 30, 80, $tamano_letra, $rowBultosDireccion->REFERENCIA, 'center');
                    //PINTAMOS LINEA
                    $pdf->setStrokeColor(0.7, 0.7, 0.7); //GRIS
                    $pdf->setLineStyle(1);
                    $pdf->line(90, $fila + 40, 90, $fila + 25);

                    //PINTAMOS COLUMNA DEL TITULO
                    $pdf->addTextWrap(95, $fila + 30, 80, $tamano_letra, ($rowBultosDireccion->PERMITE_REMONTABILIDAD == '1' ? strtoupper($auxiliar->traduce('Si', $idIdiomaImpresion)) : strtoupper($auxiliar->traduce('No', $idIdiomaImpresion))), 'center');
                    //PINTAMOS LINEA
                    $pdf->setStrokeColor(0.7, 0.7, 0.7); //GRIS
                    $pdf->setLineStyle(1);
                    $pdf->line(180, $fila + 40, 180, $fila + 25);

                    //PINTAMOS COLUMNA DEL TITULO
                    $pdf->addTextWrap(185, $fila + 30, 100, $tamano_letra, $descBulto, 'center');
                    //PINTAMOS LINEA
                    $pdf->setStrokeColor(0.7, 0.7, 0.7); //GRIS
                    $pdf->setLineStyle(1);
                    $pdf->line(285, $fila + 40, 285, $fila + 25);

                    //PINTAMOS COLUMNA DEL TITULO
                    $pdf->addTextWrap(285, $fila + 30, 125, $tamano_letra, $rowBultosDireccion->DIMENSIONES . " mm", 'center');
                    //PINTAMOS LINEA
                    $pdf->setStrokeColor(0.7, 0.7, 0.7); //GRIS
                    $pdf->setLineStyle(1);
                    $pdf->line(410, $fila + 40, 410, $fila + 25);

                    //PINTAMOS COLUMNA DEL TITULO
                    $pdf->addTextWrap(410, $fila + 30, 45, $tamano_letra, $auxiliar->formatoNumero($rowBultosDireccion->PESO) . " Kg", 'center');
                    //PINTAMOS LINEA
                    $pdf->setStrokeColor(0.7, 0.7, 0.7); //GRIS
                    $pdf->setLineStyle(1);
                    $pdf->line(455, $fila + 40, 455, $fila + 25);

                    //PINTAMOS COLUMNA DEL TITULO
                    $pdf->addTextWrap(455, $fila + 30, 60, $tamano_letra, $auxiliar->formatoNumero($rowBultosDireccion->VOLUMEN) . " m3", 'center');
                    //PINTAMOS LINEA
                    $pdf->setStrokeColor(0.7, 0.7, 0.7); //GRIS
                    $pdf->setLineStyle(1);
                    $pdf->line(515, $fila + 40, 515, $fila + 25);

                    //PINTAMOS COLUMNA DEL TITULO
                    $pdf->addTextWrap(515, $fila + 30, 60, $tamano_letra, $auxiliar->traduce($rowBultosDireccion->TIPO_DESTINO_BULTO, $idIdiomaImpresion), 'center');
                    //PINTAMOS LINEA
                    $pdf->setStrokeColor(0.7, 0.7, 0.7); //GRIS
                    $pdf->setLineStyle(1);
                    $pdf->line(575, $fila + 40, 575, $fila + 25);

                    //ACTUALIZO EL VALOR DE FILA
                    $fila = $fila - 15;
                    $i++;
                endwhile;

                //AÑADO LA FILA DE INFORMACION DE REMONTABILIDAD
                $total++;

                if ($cantXpag <= ($total - 1)):
                    $pdf->ezNewPage();
                    $this->crearCabeceraContratacion($rowOrdenContratacion->ID_ORDEN_CONTRATACION);
                    $total += 3;
                    $fila  = $fila - 30;
                endif;
                //TITULO DEL CUADRO
                $pdf->setStrokeColor(0.7, 0.7, 0.7); //GRIS
                $pdf->setLineStyle(1);
                //PINTAMOS LINEA
                $pdf->rectangle(30, $fila + 25, 545, 15);

                //PINTAMOS LINEA
                $pdf->setStrokeColor(0.7, 0.7, 0.7); //GRIS
                $pdf->setLineStyle(1);
                $pdf->line(90, $fila + 40, 90, $fila + 25);

                //PINTAMOS COLUMNA DEL TITULO
                $pdf->addTextWrap(95, $fila + 30, 80, $tamano_letra, "(X)", 'center');
                //PINTAMOS LINEA
                $pdf->setStrokeColor(0.7, 0.7, 0.7); //GRIS
                $pdf->setLineStyle(1);
                $pdf->line(180, $fila + 40, 180, $fila + 25);

                //PINTAMOS COLUMNA DEL TITULO
                $pdf->addTextWrap(185, $fila + 30, 390, $tamano_letra, $auxiliar->traduce('texto_informativo_mercancia_remontable', $idIdiomaImpresion), 'left');
                //PINTAMOS LINEA
                $pdf->setStrokeColor(0.7, 0.7, 0.7); //GRIS
                $pdf->setLineStyle(1);
                $pdf->line(575, $fila + 40, 575, $fila + 25);


                $fila = $fila + 20;
            endif;


            //FIN PINTAMOS EL BULTO


            //LINEA DE SEPARACION
            $pdf->setStrokeColor(0, 0, 0); //NEGRO
            $pdf->setLineStyle(1);
            $pdf->line(20, $fila + 3, 575, $fila + 3); //LINEA


            //CALCULAMOS LA NUEVA FILA
            $fila = $fila - 10;
        endwhile;
    }

    function crearCabeceraCondicionesServicio($idOrdenContratacion)
    {
        global $auxiliar;
        global $bd;
        global $pdf;

        global $fila;
        global $total;

        global $tamano_letra;
        global $tamanoLetraCuadros;

        global $idIdiomaImpresion;
        global $archivoUnicoContratacion;


        //SI SE VA A PINTAR AJUNTO AL OTRO DOCUMENTO, HACEMOS UN SALTO DE PAGINA
        if ($archivoUnicoContratacion == "Si"):
            $pdf->ezNewPage();
            $archivoUnicoContratacion = "No";
        endif;

        //BUSCO LA ORDEN DE CONTRATACION
        $rowOrdenContratacion = $bd->VerReg("ORDEN_CONTRATACION", "ID_ORDEN_CONTRATACION", $idOrdenContratacion);

        //BUSCO LA SOCIEDAD CONTRATANTE
        $sqlSociedad    = "SELECT S.*
                      FROM ORDEN_TRANSPORTE OT
                      INNER JOIN CENTRO C ON C.ID_CENTRO = OT.ID_CENTRO_CONTRATANTE
                      INNER JOIN SOCIEDAD S ON S.ID_SOCIEDAD = C.ID_SOCIEDAD
                      WHERE OT.ID_ORDEN_TRANSPORTE = $rowOrdenContratacion->ID_ORDEN_TRANSPORTE";
        $resultSociedad = $bd->ExecSQL($sqlSociedad);

        $rowSociedad = $bd->SigReg($resultSociedad);

        //BUSCO LA DIRECCION DE LA SOCIEDAD
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowDireccionSociedad             = $bd->VerReg("DIRECCION", "ID_SOCIEDAD", $rowSociedad->ID_SOCIEDAD, "No");

        //BUSCO EL PROVEEDOR
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowProveedor                     = $bd->VerReg("PROVEEDOR", "ID_PROVEEDOR", $rowOrdenContratacion->ID_PROVEEDOR, "No");

        //BUSCO LA DIRECCION DEL PROVEEDOR
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowDireccionProveedor            = $bd->VerReg("DIRECCION", "ID_PROVEEDOR", $rowProveedor->ID_PROVEEDOR, "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);

        $pdf->setColor(0, 0, 100); //COLOR AZUL
        $pdf->addTextWrap(100, 807, 350, 15, '<b>' . strtr(strtoupper($auxiliar->traduce("Nº Contratacion", $idIdiomaImpresion)), 'ó', 'Ó') . ': ' . $rowOrdenContratacion->ID_ORDEN_CONTRATACION . '</b>', 'center');

        $pdf->setColor(0, 0, 0); //COLOR NEGRO
        $pdf->addTextWrap(375, 803, 200, 12, '<b>' . strtr(strtoupper($auxiliar->traduce("Condiciones Servicio", $idIdiomaImpresion)), 'ó', 'Ó') . '</b>', 'right');


        //DEFINO LA ALTURA
        $fila = 780;


        //DEFINO LAS COORDENADAS DE LAS COLUMNAS (EJE X)
        $columna_1              = 23;
        $columna_1_ancho_maximo = 180;

        $columna_2              = 393;
        $columna_2_ancho_maximo = 180;


        //DEFINO LAS COORDENADAS DE LAS FILAS (EJE Y)
        $altura_superior_cuadro_1 = $fila;
        $altura_cuadro_1          = 81;
        $altura_posicion_cuadro_1 = $fila - 7;

        $altura_superior_cuadro_2 = $fila;
        $altura_cuadro_2          = 81;
        $altura_posicion_cuadro_2 = $fila - 7;


        $salto_titulo = 13;
        $salto_altura = 11;


        //EMPRESA CONTRANTE
        //TITULO DEL CUADRO
        $pdf->setStrokeColor(0.3, 0.3, 0.3); //GRIS
        $pdf->setLineStyle(15);
        //PINTAMOS LINEA Y BAJAMOS DE ALTURA
        $pdf->line($columna_1 - 3, $altura_posicion_cuadro_1, $columna_1_ancho_maximo + $columna_1, $altura_posicion_cuadro_1);
        $altura_posicion_cuadro_1 = $altura_posicion_cuadro_1 - 3;

        $pdf->setColor(1, 1, 1);
        //PINTAMOS EL TITULO Y BAJAMOS DE ALTURA
        $pdf->addText($columna_1 + 2, $altura_posicion_cuadro_1, $tamanoLetraCuadros, ucfirst($auxiliar->traduce("Empresa Contratante", $idIdiomaImpresion)));
        $altura_posicion_cuadro_1 = $altura_posicion_cuadro_1 - $salto_titulo;

        //CUADRO DE DATOS
        $pdf->setColor(0, 0, 0);
        $pdf->setLineStyle(1);
        $pdf->rectangle($columna_1 - 3, $altura_superior_cuadro_1 - $altura_cuadro_1, $columna_1_ancho_maximo + 3, $altura_cuadro_1);

        //DATOS
        //PINTAMOS NOMBRE Y BAJAMOS DE ALTURA
        $pdf->addTextWrap($columna_1, $altura_posicion_cuadro_1, $columna_1_ancho_maximo, $tamano_letra, $rowDireccionSociedad->DENOMINACION, 'left', 0, 0, 'b');
        $altura_posicion_cuadro_1 = $altura_posicion_cuadro_1 - $salto_altura;

        //PINTAMOS CIF Y BAJAMOS DE ALTURA
        $pdf->addTextWrap($columna_1, $altura_posicion_cuadro_1, $columna_1_ancho_maximo, $tamano_letra, "CIF " . $rowSociedad->CIF);
        $altura_posicion_cuadro_1 = $altura_posicion_cuadro_1 - $salto_altura;

        //PINTAMOS DIRECCION Y BAJAMOS DE ALTURA
        $numLineasPintadas = $this->pintaTextoEnVariasLineas($rowDireccionSociedad->DIRECCION, $columna_1_ancho_maximo, $columna_1, $altura_posicion_cuadro_1, $tamano_letra, 11, 2);
        if ($numLineasPintadas == 1):
            $altura_posicion_cuadro_1 = $altura_posicion_cuadro_1 - $salto_altura;
        elseif ($numLineasPintadas == 2):
            $altura_posicion_cuadro_1 = $altura_posicion_cuadro_1 - $salto_altura - 11;
        endif;

        //PINTAMOS CP Y POBLACION Y BAJAMOS DE ALTURA
        $pdf->addTextWrap($columna_1, $altura_posicion_cuadro_1, $columna_1_ancho_maximo, $tamano_letra, $rowDireccionSociedad->CODIGO_POSTAL . ' - ' . $rowDireccionSociedad->POBLACION);
        $altura_posicion_cuadro_1 = $altura_posicion_cuadro_1 - $salto_altura;

        //PINTAMOS PAIS
        $pdf->addTextWrap($columna_1, $altura_posicion_cuadro_1, $columna_1_ancho_maximo, $tamano_letra, ($rowDireccionSociedad->REGION != "" ? $rowDireccionSociedad->REGION . " " : "") . $auxiliar->obtenerDescripcionPais($rowDireccionSociedad->ID_PAIS, $idIdiomaImpresion));


        //PROVEEDOR
        //TITULO DEL CUADRO
        $pdf->setStrokeColor(0.3, 0.3, 0.3); //GRIS
        $pdf->setLineStyle(15);
        //PINTAMOS LINEA Y BAJAMOS DE ALTURA
        $pdf->line($columna_2 - 3, $altura_posicion_cuadro_2, $columna_2_ancho_maximo + $columna_2, $altura_posicion_cuadro_2);
        $altura_posicion_cuadro_2 = $altura_posicion_cuadro_2 - 3;

        $pdf->setColor(1, 1, 1);
        //PINTAMOS EL TITULO Y BAJAMOS DE ALTURA
        $pdf->addText($columna_2 + 2, $altura_posicion_cuadro_2, $tamanoLetraCuadros, ucfirst($auxiliar->traduce("Proveedor", $idIdiomaImpresion)));
        $altura_posicion_cuadro_2 = $altura_posicion_cuadro_2 - $salto_titulo;

        //CUADRO DE DATOS
        $pdf->setColor(0, 0, 0);
        $pdf->setLineStyle(1);
        $pdf->rectangle($columna_2 - 3, $altura_superior_cuadro_2 - $altura_cuadro_2, $columna_2_ancho_maximo + 3, $altura_cuadro_2);

        //PINTAMOS NOMBRE
        $pdf->addTextWrap($columna_2, $altura_posicion_cuadro_2, $columna_2_ancho_maximo, $tamano_letra, $rowDireccionProveedor->DENOMINACION, 'left', 0, 0, 'b');
        $altura_posicion_cuadro_2 = $altura_posicion_cuadro_2 - $salto_altura;

        //PINTAMOS CIF Y BAJAMOS DE ALTURA
        $pdf->addTextWrap($columna_2, $altura_posicion_cuadro_2, $columna_2_ancho_maximo, $tamano_letra, "CIF " . $rowProveedor->NIF);
        $altura_posicion_cuadro_2 = $altura_posicion_cuadro_2 - $salto_altura;

        //PINTAMOS DIRECCION Y BAJAMOS DE ALTURA
        $numLineasPintadas = $this->pintaTextoEnVariasLineas($rowDireccionProveedor->DIRECCION, $columna_2_ancho_maximo, $columna_2, $altura_posicion_cuadro_2, $tamano_letra, 11, 2);
        if ($numLineasPintadas == 1):
            $altura_posicion_cuadro_2 = $altura_posicion_cuadro_2 - $salto_altura;
        elseif ($numLineasPintadas == 2):
            $altura_posicion_cuadro_2 = $altura_posicion_cuadro_2 - $salto_altura - 11;
        endif;

        //PINTAMOS CP Y POBLACION Y BAJAMOS DE ALTURA
        $pdf->addTextWrap($columna_2, $altura_posicion_cuadro_2, $columna_2_ancho_maximo, $tamano_letra, $rowDireccionProveedor->CODIGO_POSTAL . ' - ' . $rowDireccionProveedor->POBLACION);
        $altura_posicion_cuadro_2 = $altura_posicion_cuadro_2 - $salto_altura;

        //PINTAMOS PAIS
        $pdf->addTextWrap($columna_2, $altura_posicion_cuadro_2, $columna_2_ancho_maximo, $tamano_letra, ($rowDireccionProveedor->REGION != "" ? $rowDireccionProveedor->REGION . " " : "") . $auxiliar->obtenerDescripcionPais($rowDireccionProveedor->ID_PAIS, $idIdiomaImpresion));

        //DEFINO LAS COORDENADAS DE LA SEGUNDA FILAS (EJE Y)
        $altura_superior_cuadro_1 = $altura_posicion_cuadro_1 - 25;
        $altura_cuadro_1          = 32;
        $altura_posicion_cuadro_1 = $altura_posicion_cuadro_1 - 32;


        $ancho_linea = $columna_1 - 3;

        //CUADRO DE LA FECHA
        //TITULO DEL CUADRO
        $pdf->setStrokeColor(0.3, 0.3, 0.3); //GRIS
        $pdf->setLineStyle(15);
        //PINTAMOS LINEA
        $pdf->line($ancho_linea, $altura_posicion_cuadro_1, $ancho_linea + 120, $altura_posicion_cuadro_1);
        $pdf->setColor(1, 1, 1);
        //PINTAMOS EL TITULO
        $pdf->addTextWrap($ancho_linea, $altura_posicion_cuadro_1 - 3, 120, $tamanoLetraCuadros, ucfirst($auxiliar->traduce("Orden de Transporte", $idIdiomaImpresion)), 'center');


        //CUADRO DE DATOS
        $pdf->setColor(0, 0, 0);
        $pdf->setLineStyle(1);
        $pdf->rectangle($ancho_linea, $altura_superior_cuadro_1 - $altura_cuadro_1, 120, $altura_cuadro_1);

        //DATOS
        //PINTAMOS  Y BAJAMOS DE ALTURA
        $pdf->addTextWrap($ancho_linea, $altura_posicion_cuadro_1 - 20, 120, $tamano_letra, $rowOrdenContratacion->ID_ORDEN_TRANSPORTE, 'center');

        $ancho_linea = $ancho_linea + 120;

        //CUADRO DE LA ORDEN TRANSPORTE
        //TITULO DEL CUADRO
        $pdf->setStrokeColor(0.3, 0.3, 0.3); //GRIS
        $pdf->setLineStyle(15);
        //PINTAMOS LINEA
        $pdf->line($ancho_linea, $altura_posicion_cuadro_1, $ancho_linea + 120, $altura_posicion_cuadro_1);
        $pdf->setColor(1, 1, 1);
        //PINTAMOS EL TITULO
        $pdf->addTextWrap($ancho_linea, $altura_posicion_cuadro_1 - 3, 120, $tamanoLetraCuadros, ucfirst($auxiliar->traduce("Fecha Ejecucion", $idIdiomaImpresion)), 'center');


        //CUADRO DE DATOS
        $pdf->setColor(0, 0, 0);
        $pdf->setLineStyle(1);
        $pdf->rectangle($ancho_linea, $altura_superior_cuadro_1 - $altura_cuadro_1, 120, $altura_cuadro_1);

        //DATOS
        $pdf->addTextWrap($ancho_linea, $altura_posicion_cuadro_1 - 20, 120, $tamano_letra, $auxiliar->fechaFmtoEsp($rowOrdenContratacion->FECHA_EJECUCION), 'center');


        $ancho_linea = $ancho_linea + 120;

        //CUADRO DEL SERVICIO
        //TITULO DEL CUADRO
        $pdf->setStrokeColor(0.3, 0.3, 0.3); //GRIS
        $pdf->setLineStyle(15);
        //PINTAMOS LINEA
        $pdf->line($ancho_linea, $altura_posicion_cuadro_1, $ancho_linea + 120, $altura_posicion_cuadro_1);
        $pdf->setColor(1, 1, 1);
        //PINTAMOS EL TITULO
        $pdf->addTextWrap($ancho_linea, $altura_posicion_cuadro_1 - 3, 120, $tamanoLetraCuadros, ucfirst($auxiliar->traduce("Importe", $idIdiomaImpresion)), 'center');


        //CUADRO DE DATOS
        $pdf->setColor(0, 0, 0);
        $pdf->setLineStyle(1);
        $pdf->rectangle($ancho_linea, $altura_superior_cuadro_1 - $altura_cuadro_1, 120, $altura_cuadro_1);

        //DATOS
        //PINTAMOS  Y BAJAMOS DE ALTURA
        $idMoneda = "";
        if ($rowOrdenContratacion->ID_MONEDA != ""):
            $NotificaErrorPorEmail = "No";
            $rowMoneda             = $bd->VerReg("MONEDA", "ID_MONEDA", $rowOrdenContratacion->ID_MONEDA, "No");
            $idMoneda              = $rowOrdenContratacion->ID_MONEDA;
        endif;

        $pdf->addTextWrap($ancho_linea, $altura_posicion_cuadro_1 - 20, 120, $tamano_letra, $auxiliar->formatoMoneda($rowOrdenContratacion->IMPORTE_MODIFICADO, $idMoneda) . " " . $rowMoneda->MONEDA, 'center');

        //EN CASO DE TENER LICITACION, PINTAMOS EL RFQ
        if ($rowOrdenContratacion->ESTADO_LICITACION != "No Aplica"):
            $ancho_linea = $ancho_linea + 120;

            //CUADRO DEL SERVICIO
            //TITULO DEL CUADRO
            $pdf->setStrokeColor(0.3, 0.3, 0.3); //GRIS
            $pdf->setLineStyle(15);
            //PINTAMOS LINEA
            $pdf->line($ancho_linea, $altura_posicion_cuadro_1, $ancho_linea + 120, $altura_posicion_cuadro_1);
            $pdf->setColor(1, 1, 1);
            //PINTAMOS EL TITULO
            $pdf->addTextWrap($ancho_linea, $altura_posicion_cuadro_1 - 3, 120, $tamanoLetraCuadros, ucfirst($auxiliar->traduce("RFQ", $idIdiomaImpresion)), 'center');


            //CUADRO DE DATOS
            $pdf->setColor(0, 0, 0);
            $pdf->setLineStyle(1);
            $pdf->rectangle($ancho_linea, $altura_superior_cuadro_1 - $altura_cuadro_1, 120, $altura_cuadro_1);

            //DATOS
            //PINTAMOS  Y BAJAMOS DE ALTURA
            $pdf->addTextWrap($ancho_linea, $altura_posicion_cuadro_1 - 20, 120, $tamano_letra, $rowOrdenContratacion->RFQ_LICITACION, 'center');
        endif;
        //FIN RFQ SI TIENE LICITACION

        //FILA AZUL PARA SEPARAR DATOS DE CABECERA DE CABECERA DE LINEAS
        $pdf->setStrokeColor(0, 0, 100); //AZUL
        $pdf->setLineStyle(3);
        $pdf->line(20, $altura_posicion_cuadro_1 - 40, 575, $altura_posicion_cuadro_1 - 40); //LINEA AZUL


        //GESTOR TRANSPORTE
        //DEFINO LAS COORDENADAS DE LA SEGUNDA FILAS (EJE Y)
        $altura_posicion_cuadro_1 = $altura_posicion_cuadro_1 - 60;
        $ancho_linea              = $columna_1 - 3;

        //BUSCAMOS EL GESTOR DE TRANSPORTE (USUARIO CREACION CONTRATACION)
        $rowGestorTransporte = $bd->VerReg("ADMINISTRADOR", "ID_ADMINISTRADOR", $rowOrdenContratacion->ID_ADMINISTRADOR_CREACION);

        //DESCRIPCION ORDEN CONTRATACION
        $pdf->setColor(0, 0, 0);
        $pdf->addText($ancho_linea, $altura_posicion_cuadro_1 - 3, $tamanoLetraCuadros, '<b>' . $auxiliar->traduce("Gestor Servicio", $idIdiomaImpresion) . ':</b>');

        //PINTAMOS  Y BAJAMOS DE ALTURA
        $txTelefonos = ($rowGestorTransporte->TELEFONO_FIJO != "" ? $rowGestorTransporte->TELEFONO_FIJO . ($rowGestorTransporte->TELEFONO_MOVIL != "" ? " / " : "") : "") . ($rowGestorTransporte->TELEFONO_MOVIL != "" ? $rowGestorTransporte->TELEFONO_MOVIL : "");
        $pdf->addTextWrap($ancho_linea + 10, $altura_posicion_cuadro_1 - 17, 550, $tamano_letra, '<b>' . $rowGestorTransporte->NOMBRE . '</b> - @: ' . $rowGestorTransporte->EMAIL . ' - ' . $auxiliar->traduce("Telefono", $idIdiomaImpresion) . ": " . $txTelefonos, 'left');


        //ACTUALIZO LA FILA DONDE IMPRIMIR
        $fila = $fila - 185;

        //FILA NEGRA PARA SEPARAR DATOS DE CABECERA DE CABECERA DE LINEAS
        $pdf->setStrokeColor(0, 0, 0); //AZUL
        $pdf->setLineStyle(1);
        $pdf->line(20, $fila - 6, 575, $fila - 6); //LINEA NEGRA

        //REDEFINO LA ALTURA DE LA FILA
        $fila = $fila - 25;

        //NEGRO
        $pdf->setStrokeColor(0, 0, 0);
        $pdf->setLineStyle(1);


        //ACTUALIZO TOTAL A CERO LINEAS IMPRESAS
        $total = 0;
    }

    function crearCuerpoCondicionesServicio($idOrdenContratacion)
    {
        global $auxiliar;
        global $bd;
        global $pdf;
        global $administrador;
        global $fila;
        global $total;

        global $tamano_letra;
        global $tamanoLetraCuadros;

        global $idIdiomaImpresion;
        global $cantXpag;


        //BUSCO LA ORDEN DE CONTRATACION
        $rowOrdenContratacion = $bd->VerReg("ORDEN_CONTRATACION", "ID_ORDEN_CONTRATACION", $idOrdenContratacion);

        //BUSCO LA ORDEN TRANSPORTE
        $rowOrdenTransporte = $bd->VerReg("ORDEN_TRANSPORTE", "ID_ORDEN_TRANSPORTE", $rowOrdenContratacion->ID_ORDEN_TRANSPORTE);

        //BUSCO EL SERVICIO CONTRATADO
        if ($rowOrdenContratacion->ID_SERVICIO != NULL):
            $rowServicio = $bd->VerReg("SERVICIO", "ID_SERVICIO", $rowOrdenContratacion->ID_SERVICIO);
        endif;

        //NUMERO CONTRATO
        $pdf->setColor(0, 0, 0);
        $pdf->addTextWrap(20, $fila, 575, $tamanoLetraCuadros, "<b>" . $auxiliar->traduce("Numero Contrato", $idIdiomaImpresion) . ":</b>", 'left');

        //PINTAMOS  Y BAJAMOS DE ALTURA
        $pdf->addTextWrap(40, $fila - 15, 555, $tamano_letra, ($rowOrdenContratacion->NUMERO_CONTRATO != NULL ? $rowOrdenContratacion->NUMERO_CONTRATO : "-"), 'left');

        //ACTUALIZO EL VALOR DE FILA
        $fila  = $fila - 35;
        $total = $total + 4;

        //DESCRIPCION ORDEN CONTRATACION
        $pdf->setColor(0, 0, 0);
        $pdf->addTextWrap(20, $fila, 575, $tamanoLetraCuadros, "<b>" . $auxiliar->traduce("Tipo Servicio", $idIdiomaImpresion) . ":</b>", 'left');

        //PINTAMOS  Y BAJAMOS DE ALTURA
        $pdf->addTextWrap(40, $fila - 15, 555, $tamano_letra, (($idIdiomaImpresion == "ESP") ? $rowServicio->NOMBRE : $rowServicio->NOMBRE_ENG), 'left');

        //ACTUALIZO EL VALOR DE FILA
        $fila  = $fila - 35;
        $total = $total + 4;

        //SI TIENE LICITACION
        if ($rowOrdenContratacion->ESTADO_LICITACION != "No Aplica"):

            //TARIFA
            $pdf->addTextWrap(20, $fila, 575, $tamanoLetraCuadros, "<b>" . $auxiliar->traduce("Especificacion del Servicio", $idIdiomaImpresion) . ":</b>", 'left');

            //ACTUALIZO EL VALOR DE FILA
            $fila = $fila - 25;

            //PINTAMOS DATOS CONTROLANDO LA POSICION
            $pdf->addText(40, $fila, $tamano_letra, "<b>" . $auxiliar->traduce("Medio Transporte", $idIdiomaImpresion) . ": </b>" . $rowOrdenTransporte->TIPO_TRANSPORTE);

            //ACTUALIZO EL VALOR DE FILA
            $fila  = $fila - 13;
            $total = $total + 1;

            if ($rowOrdenTransporte->TIPO_TRANSPORTE == "Maritimo")://SI ES MARITIMO AÑADIMOS TIPO CARGA Y CONTENEDOR
                //TIPO CARGA
                $pdf->addText(40, $fila, $tamano_letra, "<b>" . $auxiliar->traduce("Carga", $idIdiomaImpresion) . ": </b>" . $rowOrdenTransporte->CARGA);

                if ($rowOrdenTransporte->ID_CONTENEDOR_EXPORTACION != NULL):
                    $rowContenedor = $bd->VerReg("CONTENEDOR_EXPORTACION", "ID_CONTENEDOR_EXPORTACION", $rowOrdenTransporte->ID_CONTENEDOR_EXPORTACION);
                    //PINTAMOS CONTENEDORES
                    $pdf->addText(300, $fila, $tamano_letra, "<b>" . $auxiliar->traduce("Tipo Contenedor", $idIdiomaImpresion) . ": </b>" . $rowContenedor->NOMBRE);
                endif;

                //ACTUALIZO EL VALOR DE FILA
                $fila  = $fila - 13;
                $total = $total + 1;

                //PUERTOS
                if ($rowOrdenTransporte->ID_PUERTO_ORIGEN != NULL):
                    $rowPuertoOrigen = $bd->VerReg("PUERTO_EXPORTACION", "ID_PUERTO_EXPORTACION", $rowOrdenTransporte->ID_PUERTO_ORIGEN);
                    $pdf->addText(40, $fila, $tamano_letra, "<b>" . $auxiliar->traduce("Puerto Origen", $idIdiomaImpresion) . ": </b>" . $rowPuertoOrigen->NOMBRE);
                endif;
                if ($rowOrdenTransporte->ID_PUERTO_DESTINO != NULL):
                    $rowPuertoDestino = $bd->VerReg("PUERTO_EXPORTACION", "ID_PUERTO_EXPORTACION", $rowOrdenTransporte->ID_PUERTO_DESTINO);
                    $pdf->addText(300, $fila, $tamano_letra, "<b>" . $auxiliar->traduce("Puerto Destino", $idIdiomaImpresion) . ": </b>" . $rowPuertoDestino->NOMBRE);
                endif;
                //ACTUALIZO EL VALOR DE FILA
                $fila  = $fila - 13;
                $total = $total + 1;

            elseif ($rowOrdenTransporte->TIPO_TRANSPORTE == "Aereo")://SI ES AEREO AÑADIMOS AEROPUERTOS

                if ($rowOrdenTransporte->ID_PUERTO_ORIGEN != NULL):
                    $rowPuertoOrigen = $bd->VerReg("PUERTO_EXPORTACION", "ID_PUERTO_EXPORTACION", $rowOrdenTransporte->ID_PUERTO_ORIGEN);
                    $pdf->addText(40, $fila, $tamano_letra, "<b>" . $auxiliar->traduce("Aeropuerto Origen", $idIdiomaImpresion) . ": </b>" . $rowPuertoOrigen->NOMBRE);
                endif;
                if ($rowOrdenTransporte->ID_PUERTO_DESTINO != NULL):
                    $rowPuertoDestino = $bd->VerReg("PUERTO_EXPORTACION", "ID_PUERTO_EXPORTACION", $rowOrdenTransporte->ID_PUERTO_DESTINO);
                    $pdf->addText(300, $fila, $tamano_letra, "<b>" . $auxiliar->traduce("Aeropuerto Destino", $idIdiomaImpresion) . ": </b>" . $rowPuertoDestino->NOMBRE);
                endif;
                //ACTUALIZO EL VALOR DE FILA
                $fila  = $fila - 13;
                $total = $total + 1;
            endif;

            //BUSCAMOS EL NUMERO DE BULTOS
            $pesoBultos   = 0;
            $numBultos    = 0;
            $sqlNumBultos = "SELECT DISTINCT BCD.ID_BULTO, B.PESO
                                FROM BULTO_CONTRATACION_DESTINO BCD
                                INNER JOIN ORDEN_CONTRATACION_DESTINO OCD ON BCD.ID_ORDEN_CONTRATACION_DESTINO = OCD.ID_ORDEN_CONTRATACION_DESTINO
                                INNER JOIN BULTO B ON B.ID_BULTO = BCD.ID_BULTO
                                WHERE OCD.ID_ORDEN_CONTRATACION = $rowOrdenContratacion->ID_ORDEN_CONTRATACION AND OCD.BAJA = 0 AND BCD.BAJA = 0";
            $resultBultos = $bd->ExecSQL($sqlNumBultos);
            //SI TIENE BULTOS, CALCULAMOS CUANTOS Y SU PESO
            if ($bd->NumRegs($resultBultos) > 0):
                while ($rowNumBultos = $bd->SigReg($resultBultos)):
                    $numBultos++;
                    $pesoBultos = $pesoBultos + $rowNumBultos->PESO;
                endwhile;
            endif;

            //SUMAMOS TAMBIEN EL DE LOS BULTOS FICTICIOS
            $sqlBultosFicticios    = "SELECT DISTINCT BFC.ID_BULTO_FICTICIO_CONTRATACION, BFC.PESO
                                    FROM BULTO_FICTICIO_CONTRATACION BFC
                                     INNER JOIN ORDEN_CONTRATACION_DESTINO OCDE ON OCDE.ID_ORDEN_CONTRATACION_DESTINO = BFC.ID_ORDEN_CONTRATACION_DESTINO_ENTREGA
                                     INNER JOIN ORDEN_CONTRATACION_DESTINO OCDR ON OCDR.ID_ORDEN_CONTRATACION_DESTINO = BFC.ID_ORDEN_CONTRATACION_DESTINO_RECOGIDA
                                     WHERE BFC.BAJA = 0 AND OCDE.BAJA = 0 AND OCDR.BAJA = 0 AND OCDE.ID_ORDEN_CONTRATACION = $rowOrdenContratacion->ID_ORDEN_CONTRATACION ";
            $resultBultosFicticios = $bd->ExecSQL($sqlBultosFicticios);
            //SI TIENE BULTOS, CALCULAMOS CUANTOS Y SU PESO
            if ($bd->NumRegs($resultBultosFicticios) > 0):
                while ($rowNumBultos = $bd->SigReg($resultBultosFicticios)):
                    $numBultos++;
                    $pesoBultos = $pesoBultos + $rowNumBultos->PESO;
                endwhile;
            endif;

            //PINTAMOS NUMERO BULTOS Y PESO
            $pdf->addText(40, $fila, $tamano_letra, "<b>" . $auxiliar->traduce("Bultos", $idIdiomaImpresion) . ": </b>" . $numBultos);
            $pdf->addText(300, $fila, $tamano_letra, "<b>" . $auxiliar->traduce("Peso", $idIdiomaImpresion) . " (Kg): </b>" . $auxiliar->formatoNumero($pesoBultos));

            //ACTUALIZO EL VALOR DE FILA
            $fila  = $fila - 13;
            $total = $total + 1;

            //PINTAMOS NUMERO BULTOS Y PESO
            $pdf->addText(40, $fila, $tamano_letra, "<b>" . $auxiliar->traduce("Observaciones", $idIdiomaImpresion) . ": </b>" . $rowOrdenContratacion->OBSERVACIONES_LICITACION);

            //ACTUALIZO EL VALOR DE FILA
            $fila  = $fila - 13;
            $total = $total + 1;

        elseif ($rowOrdenContratacion->ID_TARIFA_SERVICIO_PARAMETROS != "")://SI TIENE TARIFA

            //BUSCAMOS LA CONFIGURACION DE PARAMETROS
            $rowTarifaParametros = $bd->VerReg("TARIFA_SERVICIO_PARAMETROS", "ID_TARIFA_SERVICIO_PARAMETROS", $rowOrdenContratacion->ID_TARIFA_SERVICIO_PARAMETROS);

            //BUSCAMOS LA TARIFA
            $rowTarifa = $bd->VerReg("TARIFA", "ID_TARIFA", $rowTarifaParametros->ID_TARIFA);

            //TARIFA
            $pdf->addTextWrap(20, $fila, 575, $tamanoLetraCuadros, "<b>" . $auxiliar->traduce("Tarifa", $idIdiomaImpresion) . " " . $rowTarifa->NOMBRE . ":</b>", 'left');

            //ACTUALIZO EL VALOR DE FILA
            $fila = $fila - 20;


            //VARIABLE PARA SABER EN QUE LUGAR PINTAR EL CAMPO (par - izquierda, impar - derecha)
            $posicionParametro       = 0;
            $posicionTituloParametro = 40;
            $posicionValorParametro  = 42;
            $distanciaParametros     = 260;

            //TRAMO
            if ($rowTarifaParametros->PEDIR_KM_TRAMO == 1):
                //CONTROLAMOS EL TOTAL DE LINEAS PARA NUEVA PAGINA
                if ($cantXpag <= $total):
                    $pdf->ezNewPage();
                    $this->crearCabeceraCondicionesServicio($rowOrdenContratacion->ID_ORDEN_CONTRATACION);
                endif;
                //PINTAMOS DATOS CONTROLANDO LA POSICION
                $pdf->addText($posicionTituloParametro + ($posicionParametro % 2) * $distanciaParametros, $fila, $tamano_letra, "<b>" . $auxiliar->traduce("Tramo", $idIdiomaImpresion) . "(Km): </b>");
                $pdf->addText($posicionValorParametro + ($posicionParametro % 2) * $distanciaParametros, $fila - 13, $tamano_letra, $auxiliar->formatoNumero($rowOrdenContratacion->NUMERO_KM));

                //ACTUALIZO EL VALOR DE FILA(EN CASO DE ESTAR PINTANDO EL DE LA DERECHA)
                if ($posicionParametro % 2 == 1):
                    $fila  = $fila - 26;
                    $total = $total + 2;
                endif;

                //CAMBIAMOS EL LUGAR
                $posicionParametro = $posicionParametro + 1;
            endif;

            //TRAMO KM IRUÑA EXPRESS
            if ($rowTarifaParametros->PEDIR_KM_IRUÑA == 1):
                //CONTROLAMOS EL TOTAL DE LINEAS PARA NUEVA PAGINA
                if ($cantXpag <= $total):
                    $pdf->ezNewPage();
                    $this->crearCabeceraCondicionesServicio($rowOrdenContratacion->ID_ORDEN_CONTRATACION);
                endif;
                //PINTAMOS DATOS CONTROLANDO LA POSICION
                $pdf->addText($posicionTituloParametro + ($posicionParametro % 2) * $distanciaParametros, $fila, $tamano_letra, "<b>" . $auxiliar->traduce("Km Ida y Vuelta desde Sede Iruña Express", $idIdiomaImpresion) . ": </b>");
                $pdf->addText($posicionValorParametro + ($posicionParametro % 2) * $distanciaParametros, $fila - 13, $tamano_letra, $auxiliar->formatoNumero($rowOrdenContratacion->NUMERO_KM_IRUÑA));

                //ACTUALIZO EL VALOR DE FILA (EN CASO DE ESTAR PINTANDO EL DE LA DERECHA)
                if ($posicionParametro % 2 == 1):
                    $fila  = $fila - 26;
                    $total = $total + 2;
                endif;

                //CAMBIAMOS EL LUGAR
                $posicionParametro = $posicionParametro + 1;
            endif;

            //TRAMO KM ADER
            if ($rowTarifaParametros->PEDIR_KM_ADER == 1):
                //CONTROLAMOS EL TOTAL DE LINEAS PARA NUEVA PAGINA
                if ($cantXpag <= $total):
                    $pdf->ezNewPage();
                    $this->crearCabeceraCondicionesServicio($rowOrdenContratacion->ID_ORDEN_CONTRATACION);
                endif;
                //PINTAMOS DATOS CONTROLANDO LA POSICION
                $pdf->addText($posicionTituloParametro + ($posicionParametro % 2) * $distanciaParametros, $fila, $tamano_letra, "<b>" . $auxiliar->traduce("Km Ida y Vuelta desde Sede Ader", $idIdiomaImpresion) . ": </b>");
                $pdf->addText($posicionValorParametro + ($posicionParametro % 2) * $distanciaParametros, $fila - 13, $tamano_letra, $auxiliar->formatoNumero($rowOrdenContratacion->NUMERO_KM_ADER));

                //ACTUALIZO EL VALOR DE FILA (EN CASO DE ESTAR PINTANDO EL DE LA DERECHA)
                if ($posicionParametro % 2 == 1):
                    $fila  = $fila - 26;
                    $total = $total + 2;
                endif;

                //CAMBIAMOS EL LUGAR
                $posicionParametro = $posicionParametro + 1;
            endif;

            //TRAMO KM MRW
            if ($rowTarifaParametros->PEDIR_KM_MRW == 1):
                //CONTROLAMOS EL TOTAL DE LINEAS PARA NUEVA PAGINA
                if ($cantXpag <= $total):
                    $pdf->ezNewPage();
                    $this->crearCabeceraCondicionesServicio($rowOrdenContratacion->ID_ORDEN_CONTRATACION);
                endif;
                //PINTAMOS DATOS CONTROLANDO LA POSICION
                $pdf->addText($posicionTituloParametro + ($posicionParametro % 2) * $distanciaParametros, $fila, $tamano_letra, "<b>" . $auxiliar->traduce("Km distancia desde Sede MRW", $idIdiomaImpresion) . ": </b>");
                $pdf->addText($posicionValorParametro + ($posicionParametro % 2) * $distanciaParametros, $fila - 13, $tamano_letra, $auxiliar->formatoNumero($rowOrdenContratacion->NUMERO_KM_MRW));

                //ACTUALIZO EL VALOR DE FILA (EN CASO DE ESTAR PINTANDO EL DE LA DERECHA)
                if ($posicionParametro % 2 == 1):
                    $fila  = $fila - 26;
                    $total = $total + 2;
                endif;

                //CAMBIAMOS EL LUGAR
                $posicionParametro = $posicionParametro + 1;
            endif;

            //PARADA ADICIONAL
            if ($rowTarifaParametros->PEDIR_PARADA_ADICIONAL == 1):
                //CONTROLAMOS EL TOTAL DE LINEAS PARA NUEVA PAGINA
                if ($cantXpag <= $total):
                    $pdf->ezNewPage();
                    $this->crearCabeceraCondicionesServicio($rowOrdenContratacion->ID_ORDEN_CONTRATACION);
                endif;
                //PINTAMOS DATOS CONTROLANDO LA POSICION
                $pdf->addText($posicionTituloParametro + ($posicionParametro % 2) * $distanciaParametros, $fila, $tamano_letra, "<b>" . $auxiliar->traduce("Parada Adicional", $idIdiomaImpresion) . ": </b>");
                $pdf->addText($posicionValorParametro + ($posicionParametro % 2) * $distanciaParametros, $fila - 13, $tamano_letra, ($rowOrdenContratacion->PARADA_ADICIONAL == 1 ? $auxiliar->traduce("Si", $idIdiomaImpresion) : $auxiliar->traduce("No", $idIdiomaImpresion)));

                //ACTUALIZO EL VALOR DE FILA (EN CASO DE ESTAR PINTANDO EL DE LA DERECHA)
                if ($posicionParametro % 2 == 1):
                    $fila  = $fila - 26;
                    $total = $total + 2;
                endif;

                //CAMBIAMOS EL LUGAR
                $posicionParametro = $posicionParametro + 1;
            endif;

            //Nº PARADA ADICIONAL
            if ($rowTarifaParametros->PEDIR_NUM_PARADAS_ADICIONALES == 1 && $rowOrdenContratacion->PARADA_ADICIONAL == 1):
                //CONTROLAMOS EL TOTAL DE LINEAS PARA NUEVA PAGINA
                if ($cantXpag <= $total):
                    $pdf->ezNewPage();
                    $this->crearCabeceraCondicionesServicio($rowOrdenContratacion->ID_ORDEN_CONTRATACION);
                endif;
                //PINTAMOS DATOS CONTROLANDO LA POSICION
                $pdf->addText($posicionTituloParametro + ($posicionParametro % 2) * $distanciaParametros, $fila, $tamano_letra, "<b>" . $auxiliar->traduce("Nº Paradas Adicionales", $idIdiomaImpresion) . ": </b>");
                $pdf->addText($posicionValorParametro + ($posicionParametro % 2) * $distanciaParametros, $fila - 13, $tamano_letra, $auxiliar->formatoNumero($rowOrdenContratacion->NUMERO_PARADAS_ADICIONALES));

                //ACTUALIZO EL VALOR DE FILA (EN CASO DE ESTAR PINTANDO EL DE LA DERECHA)
                if ($posicionParametro % 2 == 1):
                    $fila  = $fila - 26;
                    $total = $total + 2;
                endif;

                //CAMBIAMOS EL LUGAR
                $posicionParametro = $posicionParametro + 1;
            endif;

            //Nº PARADA ADICIONAL
            if ($rowTarifaParametros->PEDIR_HORAS_PARADA_ADICIONAL == 1 && $rowOrdenContratacion->PARADA_ADICIONAL == 1):
                //CONTROLAMOS EL TOTAL DE LINEAS PARA NUEVA PAGINA
                if ($cantXpag <= $total):
                    $pdf->ezNewPage();
                    $this->crearCabeceraCondicionesServicio($rowOrdenContratacion->ID_ORDEN_CONTRATACION);
                endif;
                //PINTAMOS DATOS CONTROLANDO LA POSICION
                $pdf->addText($posicionTituloParametro + ($posicionParametro % 2) * $distanciaParametros, $fila, $tamano_letra, "<b>" . $auxiliar->traduce("Nº horas parada adicional", $idIdiomaImpresion) . ": </b>");
                $pdf->addText($posicionValorParametro + ($posicionParametro % 2) * $distanciaParametros, $fila - 13, $tamano_letra, $auxiliar->formatoNumero($rowOrdenContratacion->NUMERO_HORAS_PARADA_ADICIONAL));

                //ACTUALIZO EL VALOR DE FILA (EN CASO DE ESTAR PINTANDO EL DE LA DERECHA)
                if ($posicionParametro % 2 == 1):
                    $fila  = $fila - 26;
                    $total = $total + 2;
                endif;

                //CAMBIAMOS EL LUGAR
                $posicionParametro = $posicionParametro + 1;
            endif;

            //KM PARADA ADICIONAL
            if ($rowTarifaParametros->PEDIR_KM_PARADA_ADICIONAL == 1 && $rowOrdenContratacion->PARADA_ADICIONAL == 1):
                //CONTROLAMOS EL TOTAL DE LINEAS PARA NUEVA PAGINA
                if ($cantXpag <= $total):
                    $pdf->ezNewPage();
                    $this->crearCabeceraCondicionesServicio($rowOrdenContratacion->ID_ORDEN_CONTRATACION);
                endif;
                //PINTAMOS DATOS CONTROLANDO LA POSICION
                $pdf->addText($posicionTituloParametro + ($posicionParametro % 2) * $distanciaParametros, $fila, $tamano_letra, "<b>" . $auxiliar->traduce("Nº Km Parada Adicional", $idIdiomaImpresion) . ": </b>");
                $pdf->addText($posicionValorParametro + ($posicionParametro % 2) * $distanciaParametros, $fila - 13, $tamano_letra, $auxiliar->formatoNumero($rowOrdenContratacion->NUMERO_KM_PARADA_ADICIONAL));

                //ACTUALIZO EL VALOR DE FILA (EN CASO DE ESTAR PINTANDO EL DE LA DERECHA)
                if ($posicionParametro % 2 == 1):
                    $fila  = $fila - 26;
                    $total = $total + 2;
                endif;

                //CAMBIAMOS EL LUGAR
                $posicionParametro = $posicionParametro + 1;
            endif;            //KM PARADA ADICIONAL
            //TARA
            if ($rowTarifaParametros->PEDIR_TARA == 1):
                //CONTROLAMOS EL TOTAL DE LINEAS PARA NUEVA PAGINA
                if ($cantXpag <= $total):
                    $pdf->ezNewPage();
                    $this->crearCabeceraCondicionesServicio($rowOrdenContratacion->ID_ORDEN_CONTRATACION);
                endif;
                //PINTAMOS DATOS CONTROLANDO LA POSICION
                $pdf->addText($posicionTituloParametro + ($posicionParametro % 2) * $distanciaParametros, $fila, $tamano_letra, "<b>" . $auxiliar->traduce("Tara", $idIdiomaImpresion) . "(Kg): </b>");
                $pdf->addText($posicionValorParametro + ($posicionParametro % 2) * $distanciaParametros, $fila - 13, $tamano_letra, $auxiliar->formatoNumero($rowOrdenContratacion->TARA_VEHICULO));

                //ACTUALIZO EL VALOR DE FILA (EN CASO DE ESTAR PINTANDO EL DE LA DERECHA)
                if ($posicionParametro % 2 == 1):
                    $fila  = $fila - 26;
                    $total = $total + 2;
                endif;

                //CAMBIAMOS EL LUGAR
                $posicionParametro = $posicionParametro + 1;
            endif;
            //DIAS ADICIONALES
            if ($rowTarifaParametros->PEDIR_DIA_ADICIONAL == 1):
                //CONTROLAMOS EL TOTAL DE LINEAS PARA NUEVA PAGINA
                if ($cantXpag <= $total):
                    $pdf->ezNewPage();
                    $this->crearCabeceraCondicionesServicio($rowOrdenContratacion->ID_ORDEN_CONTRATACION);
                endif;
                //PINTAMOS DATOS CONTROLANDO LA POSICION
                $pdf->addText($posicionTituloParametro + ($posicionParametro % 2) * $distanciaParametros, $fila, $tamano_letra, "<b>" . $auxiliar->traduce("Dias Adicionales", $idIdiomaImpresion) . ": </b>");
                $pdf->addText($posicionValorParametro + ($posicionParametro % 2) * $distanciaParametros, $fila - 13, $tamano_letra, $auxiliar->formatoNumero($rowOrdenContratacion->NUMERO_DIAS_ADICIONALES));

                //ACTUALIZO EL VALOR DE FILA (EN CASO DE ESTAR PINTANDO EL DE LA DERECHA)
                if ($posicionParametro % 2 == 1):
                    $fila  = $fila - 26;
                    $total = $total + 2;
                endif;

                //CAMBIAMOS EL LUGAR
                $posicionParametro = $posicionParametro + 1;
            endif;
            //DIAS ADICIONALES SOLO PLATAFORMA
            if ($rowTarifaParametros->PEDIR_DIA_ADICIONAL_SOLO_PLATAFORMA == 1):
                //CONTROLAMOS EL TOTAL DE LINEAS PARA NUEVA PAGINA
                if ($cantXpag <= $total):
                    $pdf->ezNewPage();
                    $this->crearCabeceraCondicionesServicio($rowOrdenContratacion->ID_ORDEN_CONTRATACION);
                endif;
                //PINTAMOS DATOS CONTROLANDO LA POSICION
                $pdf->addText($posicionTituloParametro + ($posicionParametro % 2) * $distanciaParametros, $fila, $tamano_letra, "<b>" . $auxiliar->traduce("Dias Adicionales Solo Plataforma", $idIdiomaImpresion) . ": </b>");
                $pdf->addText($posicionValorParametro + ($posicionParametro % 2) * $distanciaParametros, $fila - 13, $tamano_letra, $auxiliar->formatoNumero($rowOrdenContratacion->NUMERO_DIAS_ADICIONALES_SOLO_PLATAFORMA));

                //ACTUALIZO EL VALOR DE FILA (EN CASO DE ESTAR PINTANDO EL DE LA DERECHA)
                if ($posicionParametro % 2 == 1):
                    $fila  = $fila - 26;
                    $total = $total + 2;
                endif;

                //CAMBIAMOS EL LUGAR
                $posicionParametro = $posicionParametro + 1;
            endif;
            //NUMERO AYUDANTES
            if ($rowTarifaParametros->PEDIR_AYUDANTE_NUMERO == 1):
                //CONTROLAMOS EL TOTAL DE LINEAS PARA NUEVA PAGINA
                if ($cantXpag <= $total):
                    $pdf->ezNewPage();
                    $this->crearCabeceraCondicionesServicio($rowOrdenContratacion->ID_ORDEN_CONTRATACION);
                endif;
                //PINTAMOS DATOS CONTROLANDO LA POSICION
                $pdf->addText($posicionTituloParametro + ($posicionParametro % 2) * $distanciaParametros, $fila, $tamano_letra, "<b>" . $auxiliar->traduce("Numero Ayudantes", $idIdiomaImpresion) . ": </b>");
                $pdf->addText($posicionValorParametro + ($posicionParametro % 2) * $distanciaParametros, $fila - 13, $tamano_letra, $auxiliar->formatoNumero($rowOrdenContratacion->NUMERO_AYUDANTES));

                //ACTUALIZO EL VALOR DE FILA (EN CASO DE ESTAR PINTANDO EL DE LA DERECHA)
                if ($posicionParametro % 2 == 1):
                    $fila  = $fila - 26;
                    $total = $total + 2;
                endif;

                //CAMBIAMOS EL LUGAR
                $posicionParametro = $posicionParametro + 1;
            endif;
            //NUMERO DIAS AYUDANTES
            if ($rowTarifaParametros->PEDIR_AYUDANTE_DIA_ADICIONAL == 1):
                //CONTROLAMOS EL TOTAL DE LINEAS PARA NUEVA PAGINA
                if ($cantXpag <= $total):
                    $pdf->ezNewPage();
                    $this->crearCabeceraCondicionesServicio($rowOrdenContratacion->ID_ORDEN_CONTRATACION);
                endif;
                //PINTAMOS DATOS CONTROLANDO LA POSICION
                $pdf->addText($posicionTituloParametro + ($posicionParametro % 2) * $distanciaParametros, $fila, $tamano_letra, "<b>" . $auxiliar->traduce("Numero Dias Ayudantes", $idIdiomaImpresion) . ": </b>");
                $pdf->addText($posicionValorParametro + ($posicionParametro % 2) * $distanciaParametros, $fila - 13, $tamano_letra, $auxiliar->formatoNumero($rowOrdenContratacion->NUMERO_AYUDANTES_DIAS));

                //ACTUALIZO EL VALOR DE FILA (EN CASO DE ESTAR PINTANDO EL DE LA DERECHA)
                if ($posicionParametro % 2 == 1):
                    $fila  = $fila - 26;
                    $total = $total + 2;
                endif;

                //CAMBIAMOS EL LUGAR
                $posicionParametro = $posicionParametro + 1;
            endif;
            //HORAS DE ESPERA
            if ($rowTarifaParametros->PEDIR_HORA_ESPERA_DESTINO == 1):
                //CONTROLAMOS EL TOTAL DE LINEAS PARA NUEVA PAGINA
                if ($cantXpag <= $total):
                    $pdf->ezNewPage();
                    $this->crearCabeceraCondicionesServicio($rowOrdenContratacion->ID_ORDEN_CONTRATACION);
                endif;
                //PINTAMOS DATOS CONTROLANDO LA POSICION
                $pdf->addText($posicionTituloParametro + ($posicionParametro % 2) * $distanciaParametros, $fila, $tamano_letra, "<b>" . $auxiliar->traduce("Horas de Espera en Destino a partir de la 2º hora", $idIdiomaImpresion) . ": </b>");
                $pdf->addText($posicionValorParametro + ($posicionParametro % 2) * $distanciaParametros, $fila - 13, $tamano_letra, $auxiliar->formatoNumero($rowOrdenContratacion->NUMERO_HORAS_EN_DESTINO));

                //ACTUALIZO EL VALOR DE FILA (EN CASO DE ESTAR PINTANDO EL DE LA DERECHA)
                if ($posicionParametro % 2 == 1):
                    $fila  = $fila - 26;
                    $total = $total + 2;
                endif;

                //CAMBIAMOS EL LUGAR
                $posicionParametro = $posicionParametro + 1;
            endif;
            //TONELAJE GRUA
            if ($rowTarifaParametros->PEDIR_TONELAJE_GRUA == 1):
                //CONTROLAMOS EL TOTAL DE LINEAS PARA NUEVA PAGINA
                if ($cantXpag <= $total):
                    $pdf->ezNewPage();
                    $this->crearCabeceraCondicionesServicio($rowOrdenContratacion->ID_ORDEN_CONTRATACION);
                endif;
                //PINTAMOS DATOS CONTROLANDO LA POSICION
                $pdf->addText($posicionTituloParametro + ($posicionParametro % 2) * $distanciaParametros, $fila, $tamano_letra, "<b>" . $auxiliar->traduce("Tonelaje Grua", $idIdiomaImpresion) . ": </b>");
                $pdf->addText($posicionValorParametro + ($posicionParametro % 2) * $distanciaParametros, $fila - 13, $tamano_letra, $auxiliar->formatoNumero($rowOrdenContratacion->TONELAJE_GRUA));

                //ACTUALIZO EL VALOR DE FILA (EN CASO DE ESTAR PINTANDO EL DE LA DERECHA)
                if ($posicionParametro % 2 == 1):
                    $fila  = $fila - 26;
                    $total = $total + 2;
                endif;

                //CAMBIAMOS EL LUGAR
                $posicionParametro = $posicionParametro + 1;
            endif;
            //HORAS USO
            if ($rowTarifaParametros->PEDIR_HORA_USO_GRUA == 1):
                //CONTROLAMOS EL TOTAL DE LINEAS PARA NUEVA PAGINA
                if ($cantXpag <= $total):
                    $pdf->ezNewPage();
                    $this->crearCabeceraCondicionesServicio($rowOrdenContratacion->ID_ORDEN_CONTRATACION);
                endif;
                //PINTAMOS DATOS CONTROLANDO LA POSICION
                $pdf->addText($posicionTituloParametro + ($posicionParametro % 2) * $distanciaParametros, $fila, $tamano_letra, "<b>" . $auxiliar->traduce("Horas de uso", $idIdiomaImpresion) . ": </b>");
                $pdf->addText($posicionValorParametro + ($posicionParametro % 2) * $distanciaParametros, $fila - 13, $tamano_letra, $auxiliar->formatoNumero($rowOrdenContratacion->NUMERO_HORAS_GRUA));

                //ACTUALIZO EL VALOR DE FILA (EN CASO DE ESTAR PINTANDO EL DE LA DERECHA)
                if ($posicionParametro % 2 == 1):
                    $fila  = $fila - 26;
                    $total = $total + 2;
                endif;

                //CAMBIAMOS EL LUGAR
                $posicionParametro = $posicionParametro + 1;
            endif;
            //HORAS HORARIO ESPECIAL
            if ($rowTarifaParametros->PEDIR_HORAS_PRECIO_ESPECIAL == 1):
                //CONTROLAMOS EL TOTAL DE LINEAS PARA NUEVA PAGINA
                if ($cantXpag <= $total):
                    $pdf->ezNewPage();
                    $this->crearCabeceraCondicionesServicio($rowOrdenContratacion->ID_ORDEN_CONTRATACION);
                endif;
                //PINTAMOS DATOS CONTROLANDO LA POSICION
                $pdf->addText($posicionTituloParametro + ($posicionParametro % 2) * $distanciaParametros, $fila, $tamano_letra, "<b>..." . $auxiliar->traduce("de las cuales, se realizaran en horario especial", $idIdiomaImpresion) . ": </b>");
                $pdf->addText($posicionValorParametro + ($posicionParametro % 2) * $distanciaParametros, $fila - 13, $tamano_letra, $auxiliar->formatoNumero($rowOrdenContratacion->NUMERO_HORAS_PRECIO_ESPECIAL));

                //ACTUALIZO EL VALOR DE FILA (EN CASO DE ESTAR PINTANDO EL DE LA DERECHA)
                if ($posicionParametro % 2 == 1):
                    $fila  = $fila - 26;
                    $total = $total + 2;
                endif;

                //CAMBIAMOS EL LUGAR
                $posicionParametro = $posicionParametro + 1;
            endif;
            //HORA ENTREGA
            if ($rowTarifaParametros->PEDIR_HORA_ENTREGA == 1):
                //CONTROLAMOS EL TOTAL DE LINEAS PARA NUEVA PAGINA
                if ($cantXpag <= $total):
                    $pdf->ezNewPage();
                    $this->crearCabeceraCondicionesServicio($rowOrdenContratacion->ID_ORDEN_CONTRATACION);
                endif;
                //PINTAMOS DATOS CONTROLANDO LA POSICION
                $pdf->addText($posicionTituloParametro + ($posicionParametro % 2) * $distanciaParametros, $fila, $tamano_letra, "<b>" . $auxiliar->traduce("Hora Limite Entrega", $idIdiomaImpresion) . ": </b>");
                $pdf->addText($posicionValorParametro + ($posicionParametro % 2) * $distanciaParametros, $fila - 13, $tamano_letra, ($rowOrdenContratacion->HORA_ENTREGA == "00:00:00" ? $auxiliar->traduce("Cualquier Momento") : ($rowOrdenContratacion->HORA_ENTREGA != '' ? $auxiliar->fechaFmtoEspHora(date('Y-m-d') . " " . $rowOrdenContratacion->HORA_ENTREGA, false, true, false, false) : "-")));

                //ACTUALIZO EL VALOR DE FILA (EN CASO DE ESTAR PINTANDO EL DE LA DERECHA)
                if ($posicionParametro % 2 == 1):
                    $fila  = $fila - 26;
                    $total = $total + 2;
                endif;

                //CAMBIAMOS EL LUGAR
                $posicionParametro = $posicionParametro + 1;
            endif;

            //SOLICITUD SERVICIO ASM POSTERIOR 10:00
            if ($rowTarifaParametros->PEDIR_SUPLEMENTO_ASM_IMARCOAIN == 1):
                //CONTROLAMOS EL TOTAL DE LINEAS PARA NUEVA PAGINA
                if ($cantXpag <= $total):
                    $pdf->ezNewPage();
                    $this->crearCabeceraCondicionesServicio($rowOrdenContratacion->ID_ORDEN_CONTRATACION);
                endif;
                //PINTAMOS DATOS CONTROLANDO LA POSICION
                $pdf->addText($posicionTituloParametro + ($posicionParametro % 2) * $distanciaParametros, $fila, $tamano_letra, "<b>" . $auxiliar->traduce("Solicitud posterior a las 10:00 AM", $idIdiomaImpresion) . ": </b>");
                $pdf->addText($posicionValorParametro + ($posicionParametro % 2) * $distanciaParametros, $fila - 13, $tamano_letra, ($rowOrdenContratacion->SOLICITUD_ASM_POSTERIOR_10 == 1 ? $auxiliar->traduce("Si", $idIdiomaImpresion) : $auxiliar->traduce("No", $idIdiomaImpresion)));

                //ACTUALIZO EL VALOR DE FILA (EN CASO DE ESTAR PINTANDO EL DE LA DERECHA)
                if ($posicionParametro % 2 == 1):
                    $fila  = $fila - 26;
                    $total = $total + 2;
                endif;

                //CAMBIAMOS EL LUGAR
                $posicionParametro = $posicionParametro + 1;
            endif;

            //TRAMO ASM ORIGEN PTO RECOGIDA (CUANDO ES POSTERIOR A LAS 10 AM O ASM10)
            if ($rowTarifaParametros->PEDIR_KM_ASM_ORIGEN == 1 && ($rowOrdenContratacion->SOLICITUD_ASM_POSTERIOR_10 == 1 || $rowOrdenContratacion->HORA_ENTREGA == "10:00:00")):
                //CONTROLAMOS EL TOTAL DE LINEAS PARA NUEVA PAGINA
                if ($cantXpag <= $total):
                    $pdf->ezNewPage();
                    $this->crearCabeceraCondicionesServicio($rowOrdenContratacion->ID_ORDEN_CONTRATACION);
                endif;
                //PINTAMOS DATOS CONTROLANDO LA POSICION
                $pdf->addText($posicionTituloParametro + ($posicionParametro % 2) * $distanciaParametros, $fila, $tamano_letra, "<b>" . $auxiliar->traduce("Km ida y vuelta sede ASM Origen-Punto Recogida", $idIdiomaImpresion) . ": </b>");
                $pdf->addText($posicionValorParametro + ($posicionParametro % 2) * $distanciaParametros, $fila - 13, $tamano_letra, $auxiliar->formatoNumero($rowOrdenContratacion->NUMERO_KM_ASM_ORIGEN));

                //ACTUALIZO EL VALOR DE FILA (EN CASO DE ESTAR PINTANDO EL DE LA DERECHA)
                if ($posicionParametro % 2 == 1):
                    $fila  = $fila - 26;
                    $total = $total + 2;
                endif;

                //CAMBIAMOS EL LUGAR
                $posicionParametro = $posicionParametro + 1;
            endif;

            //TRAMO ASM DESTINO PTO ENTREGA (CUANDO ES ASM10)
            if ($rowTarifaParametros->PEDIR_KM_ASM_DESTINO == 1 && $rowOrdenContratacion->HORA_ENTREGA == "10:00:00"):
                //CONTROLAMOS EL TOTAL DE LINEAS PARA NUEVA PAGINA
                if ($cantXpag <= $total):
                    $pdf->ezNewPage();
                    $this->crearCabeceraCondicionesServicio($rowOrdenContratacion->ID_ORDEN_CONTRATACION);
                endif;
                //PINTAMOS DATOS CONTROLANDO LA POSICION
                $pdf->addText($posicionTituloParametro + ($posicionParametro % 2) * $distanciaParametros, $fila, $tamano_letra, "<b>" . $auxiliar->traduce("Km ida y vuelta sede ASM Destino-Punto Entrega", $idIdiomaImpresion) . ": </b>");
                $pdf->addText($posicionValorParametro + ($posicionParametro % 2) * $distanciaParametros, $fila - 13, $tamano_letra, $auxiliar->formatoNumero($rowOrdenContratacion->NUMERO_KM_ASM_DESTINO));

                //ACTUALIZO EL VALOR DE FILA (EN CASO DE ESTAR PINTANDO EL DE LA DERECHA)
                if ($posicionParametro % 2 == 1):
                    $fila  = $fila - 26;
                    $total = $total + 2;
                endif;

                //CAMBIAMOS EL LUGAR
                $posicionParametro = $posicionParametro + 1;
            endif;

            //ZONA DESTINO
            if ($rowTarifaParametros->PEDIR_ZONA_DESTINO == 1):
                //CONTROLAMOS EL TOTAL DE LINEAS PARA NUEVA PAGINA
                if ($cantXpag <= $total):
                    $pdf->ezNewPage();
                    $this->crearCabeceraCondicionesServicio($rowOrdenContratacion->ID_ORDEN_CONTRATACION);
                endif;
                //PINTAMOS DATOS CONTROLANDO LA POSICION
                $pdf->addText($posicionTituloParametro + ($posicionParametro % 2) * $distanciaParametros, $fila, $tamano_letra, "<b>" . $auxiliar->traduce("Zona Destino", $idIdiomaImpresion) . ": </b>");
                $pdf->addText($posicionValorParametro + ($posicionParametro % 2) * $distanciaParametros, $fila - 13, $tamano_letra, $rowOrdenContratacion->ZONA_DESTINO);

                //ACTUALIZO EL VALOR DE FILA (EN CASO DE ESTAR PINTANDO EL DE LA DERECHA)
                if ($posicionParametro % 2 == 1):
                    $fila  = $fila - 26;
                    $total = $total + 2;
                endif;

                //CAMBIAMOS EL LUGAR
                $posicionParametro = $posicionParametro + 1;
            endif;
            //CARROZADO
            if ($rowTarifaParametros->PEDIR_FURGON_CARROZADO == 1):
                //CONTROLAMOS EL TOTAL DE LINEAS PARA NUEVA PAGINA
                if ($cantXpag <= $total):
                    $pdf->ezNewPage();
                    $this->crearCabeceraCondicionesServicio($rowOrdenContratacion->ID_ORDEN_CONTRATACION);
                endif;
                //PINTAMOS DATOS CONTROLANDO LA POSICION
                $pdf->addText($posicionTituloParametro + ($posicionParametro % 2) * $distanciaParametros, $fila, $tamano_letra, "<b>" . $auxiliar->traduce("Vehiculo Carrozado", $idIdiomaImpresion) . ": </b>");
                $pdf->addText($posicionValorParametro + ($posicionParametro % 2) * $distanciaParametros, $fila - 13, $tamano_letra, ($rowOrdenContratacion->VEHICULO_CARROZADO == 1 ? $auxiliar->traduce("Si", $idIdiomaImpresion) : $auxiliar->traduce("No", $idIdiomaImpresion)));

                //ACTUALIZO EL VALOR DE FILA (EN CASO DE ESTAR PINTANDO EL DE LA DERECHA)
                if ($posicionParametro % 2 == 1):
                    $fila  = $fila - 26;
                    $total = $total + 2;
                endif;

                //CAMBIAMOS EL LUGAR
                $posicionParametro = $posicionParametro + 1;
            endif;
            //COMARCAL
            if ($rowTarifaParametros->PEDIR_COMARCAL == 1):
                //CONTROLAMOS EL TOTAL DE LINEAS PARA NUEVA PAGINA
                if ($cantXpag <= $total):
                    $pdf->ezNewPage();
                    $this->crearCabeceraCondicionesServicio($rowOrdenContratacion->ID_ORDEN_CONTRATACION);
                endif;
                //PINTAMOS DATOS CONTROLANDO LA POSICION
                $pdf->addText($posicionTituloParametro + ($posicionParametro % 2) * $distanciaParametros, $fila, $tamano_letra, "<b>" . $auxiliar->traduce("Comarcal", $idIdiomaImpresion) . ": </b>");
                $pdf->addText($posicionValorParametro + ($posicionParametro % 2) * $distanciaParametros, $fila - 13, $tamano_letra, ($rowOrdenContratacion->SERVICIO_COMARCAL == 1 ? $auxiliar->traduce("Si", $idIdiomaImpresion) : $auxiliar->traduce("No", $idIdiomaImpresion)));

                //ACTUALIZO EL VALOR DE FILA (EN CASO DE ESTAR PINTANDO EL DE LA DERECHA)
                if ($posicionParametro % 2 == 1):
                    $fila  = $fila - 26;
                    $total = $total + 2;
                endif;

                //CAMBIAMOS EL LUGAR
                $posicionParametro = $posicionParametro + 1;
            endif;
            //NOCTURNIDAD
            if ($rowTarifaParametros->PEDIR_NOCTURNIDAD_FESTIVIDAD == 1):
                //CONTROLAMOS EL TOTAL DE LINEAS PARA NUEVA PAGINA
                if ($cantXpag <= $total):
                    $pdf->ezNewPage();
                    $this->crearCabeceraCondicionesServicio($rowOrdenContratacion->ID_ORDEN_CONTRATACION);
                endif;
                //PINTAMOS DATOS CONTROLANDO LA POSICION
                $pdf->addText($posicionTituloParametro + ($posicionParametro % 2) * $distanciaParametros, $fila, $tamano_letra, "<b>" . $auxiliar->traduce("Servicio Nocturno o Festivo", $idIdiomaImpresion) . ": </b>");
                $pdf->addText($posicionValorParametro + ($posicionParametro % 2) * $distanciaParametros, $fila - 13, $tamano_letra, ($rowOrdenContratacion->SERVICIO_NOCTURNO_FESTIVO == 1 ? $auxiliar->traduce("Si", $idIdiomaImpresion) : $auxiliar->traduce("No", $idIdiomaImpresion)));

                //ACTUALIZO EL VALOR DE FILA (EN CASO DE ESTAR PINTANDO EL DE LA DERECHA)
                if ($posicionParametro % 2 == 1):
                    $fila  = $fila - 26;
                    $total = $total + 2;
                endif;

                //CAMBIAMOS EL LUGAR
                $posicionParametro = $posicionParametro + 1;
            endif;
            //RETORNO CARGADO
            if ($rowTarifaParametros->PEDIR_RETORNO_CARGADO == 1):
                //CONTROLAMOS EL TOTAL DE LINEAS PARA NUEVA PAGINA
                if ($cantXpag <= $total):
                    $pdf->ezNewPage();
                    $this->crearCabeceraCondicionesServicio($rowOrdenContratacion->ID_ORDEN_CONTRATACION);
                endif;
                //PINTAMOS DATOS CONTROLANDO LA POSICION
                $pdf->addText($posicionTituloParametro + ($posicionParametro % 2) * $distanciaParametros, $fila, $tamano_letra, "<b>" . $auxiliar->traduce("Retorno Cargado", $idIdiomaImpresion) . ": </b>");
                $pdf->addText($posicionValorParametro + ($posicionParametro % 2) * $distanciaParametros, $fila - 13, $tamano_letra, ($rowOrdenContratacion->TIENE_RETORNO_CARGADO == 1 ? $auxiliar->traduce("Si", $idIdiomaImpresion) : $auxiliar->traduce("No", $idIdiomaImpresion)));

                //ACTUALIZO EL VALOR DE FILA (EN CASO DE ESTAR PINTANDO EL DE LA DERECHA)
                if ($posicionParametro % 2 == 1):
                    $fila  = $fila - 26;
                    $total = $total + 2;
                endif;

                //CAMBIAMOS EL LUGAR
                $posicionParametro = $posicionParametro + 1;
            endif;
            //RECOGIDA CRUZADA
            if ($rowTarifaParametros->PEDIR_RECOGIDA_CRUZADA == 1):
                //CONTROLAMOS EL TOTAL DE LINEAS PARA NUEVA PAGINA
                if ($cantXpag <= $total):
                    $pdf->ezNewPage();
                    $this->crearCabeceraCondicionesServicio($rowOrdenContratacion->ID_ORDEN_CONTRATACION);
                endif;
                //PINTAMOS DATOS CONTROLANDO LA POSICION
                $pdf->addText($posicionTituloParametro + ($posicionParametro % 2) * $distanciaParametros, $fila, $tamano_letra, "<b>" . $auxiliar->traduce("Recogida Cruzada", $idIdiomaImpresion) . ": </b>");
                $pdf->addText($posicionValorParametro + ($posicionParametro % 2) * $distanciaParametros, $fila - 13, $tamano_letra, ($rowOrdenContratacion->SUPLEMENTO_RECOGIDAS_CRUZADAS == 1 ? $auxiliar->traduce("Si", $idIdiomaImpresion) : $auxiliar->traduce("No", $idIdiomaImpresion)));

                //ACTUALIZO EL VALOR DE FILA (EN CASO DE ESTAR PINTANDO EL DE LA DERECHA)
                if ($posicionParametro % 2 == 1):
                    $fila  = $fila - 26;
                    $total = $total + 2;
                endif;

                //CAMBIAMOS EL LUGAR
                $posicionParametro = $posicionParametro + 1;
            endif;
            //SABADO MAÑANA
            if ($rowTarifaParametros->PEDIR_SABADO_MAÑANA == 1):
                //CONTROLAMOS EL TOTAL DE LINEAS PARA NUEVA PAGINA
                if ($cantXpag <= $total):
                    $pdf->ezNewPage();
                    $this->crearCabeceraCondicionesServicio($rowOrdenContratacion->ID_ORDEN_CONTRATACION);
                endif;
                //PINTAMOS DATOS CONTROLANDO LA POSICION
                $pdf->addText($posicionTituloParametro + ($posicionParametro % 2) * $distanciaParametros, $fila, $tamano_letra, "<b>" . $auxiliar->traduce("Servicio Sabado Mañana", $idIdiomaImpresion) . ": </b>");
                $pdf->addText($posicionValorParametro + ($posicionParametro % 2) * $distanciaParametros, $fila - 13, $tamano_letra, ($rowOrdenContratacion->SERVICIO_SABADO_MAÑANA == 1 ? $auxiliar->traduce("Si", $idIdiomaImpresion) : $auxiliar->traduce("No", $idIdiomaImpresion)));

                //ACTUALIZO EL VALOR DE FILA (EN CASO DE ESTAR PINTANDO EL DE LA DERECHA)
                if ($posicionParametro % 2 == 1):
                    $fila  = $fila - 26;
                    $total = $total + 2;
                endif;

                //CAMBIAMOS EL LUGAR
                $posicionParametro = $posicionParametro + 1;
            endif;
            //CAMINO RURAL
            if ($rowTarifaParametros->PEDIR_CAMINO_RURAL == 1):
                //CONTROLAMOS EL TOTAL DE LINEAS PARA NUEVA PAGINA
                if ($cantXpag <= $total):
                    $pdf->ezNewPage();
                    $this->crearCabeceraCondicionesServicio($rowOrdenContratacion->ID_ORDEN_CONTRATACION);
                endif;
                //PINTAMOS DATOS CONTROLANDO LA POSICION
                $pdf->addText($posicionTituloParametro + ($posicionParametro % 2) * $distanciaParametros, $fila, $tamano_letra, "<b>" . $auxiliar->traduce("Camino Rural", $idIdiomaImpresion) . ": </b>");
                $pdf->addText($posicionValorParametro + ($posicionParametro % 2) * $distanciaParametros, $fila - 13, $tamano_letra, ($rowOrdenContratacion->CAMINO_RURAL == 1 ? $auxiliar->traduce("Si", $idIdiomaImpresion) : $auxiliar->traduce("No", $idIdiomaImpresion)));

                //ACTUALIZO EL VALOR DE FILA (EN CASO DE ESTAR PINTANDO EL DE LA DERECHA)
                if ($posicionParametro % 2 == 1):
                    $fila  = $fila - 26;
                    $total = $total + 2;
                endif;

                //CAMBIAMOS EL LUGAR
                $posicionParametro = $posicionParametro + 1;
            endif;

            //SERVICIO ASM EN DELEGACION ASM IMARCOAIN
            if ($rowTarifaParametros->PEDIR_SOLICITUD_POSTERIOR_10 == 1):
                //CONTROLAMOS EL TOTAL DE LINEAS PARA NUEVA PAGINA
                if ($cantXpag <= $total):
                    $pdf->ezNewPage();
                    $this->crearCabeceraCondicionesServicio($rowOrdenContratacion->ID_ORDEN_CONTRATACION);
                endif;
                //PINTAMOS DATOS CONTROLANDO LA POSICION
                $pdf->addText($posicionTituloParametro + ($posicionParametro % 2) * $distanciaParametros, $fila, $tamano_letra, "<b>" . $auxiliar->traduce("Contratado mediante delegacion ASM-Imarcoain", $idIdiomaImpresion) . ": </b>");
                $pdf->addText($posicionValorParametro + ($posicionParametro % 2) * $distanciaParametros, $fila - 13, $tamano_letra, ($rowOrdenContratacion->SERVICIO_DELEGACION_ASM == 1 ? $auxiliar->traduce("Si", $idIdiomaImpresion) : $auxiliar->traduce("No", $idIdiomaImpresion)));

                //ACTUALIZO EL VALOR DE FILA (EN CASO DE ESTAR PINTANDO EL DE LA DERECHA)
                if ($posicionParametro % 2 == 1):
                    $fila  = $fila - 26;
                    $total = $total + 2;
                endif;

                //CAMBIAMOS EL LUGAR
                $posicionParametro = $posicionParametro + 1;
            endif;

            //PORCENTAJE DE VARIACION DE COMBUSTIBLE
            if ($rowOrdenContratacion->PORCENTAJE_FLUCTUACION_SERVICIO != 0):
                //CONTROLAMOS EL TOTAL DE LINEAS PARA NUEVA PAGINA
                if ($cantXpag <= $total):
                    $pdf->ezNewPage();
                    $this->crearCabeceraCondicionesServicio($rowOrdenContratacion->ID_ORDEN_CONTRATACION);
                endif;
                //PINTAMOS DATOS CONTROLANDO LA POSICION
                $pdf->addText($posicionTituloParametro + ($posicionParametro % 2) * $distanciaParametros, $fila, $tamano_letra, "<b>" . $auxiliar->traduce("Porcentaje de fluctuacion del combustible", $idIdiomaImpresion) . ": </b>");
                $pdf->addText($posicionValorParametro + ($posicionParametro % 2) * $distanciaParametros, $fila - 13, $tamano_letra, $auxiliar->formatoNumero($rowOrdenContratacion->PORCENTAJE_FLUCTUACION_SERVICIO) . "%");

                //ACTUALIZO EL VALOR DE FILA (EN CASO DE ESTAR PINTANDO EL DE LA DERECHA)
                if ($posicionParametro % 2 == 1):
                    $fila  = $fila - 26;
                    $total = $total + 2;
                endif;
            endif;

            //SI HA ACABADO IMPAR, ACTUALIZAMOS EL VALOR DE LA FILA
            if ($posicionParametro % 2 == 1):
                $fila  = $fila - 26;
                $total = $total + 2;
            endif;


            //BULTOS DE PAQUETERIA
            if ($rowTarifaParametros->PEDIR_PESO_PAQUETE == 1):
                $sqlPaquetes    = "SELECT * FROM ORDEN_CONTRATACION_PAQUETE WHERE BAJA = 0 AND ID_ORDEN_CONTRATACION = $rowOrdenContratacion->ID_ORDEN_CONTRATACION";
                $resultPaquetes = $bd->ExecSQL($sqlPaquetes);
                if ($bd->NumRegs($resultPaquetes) > 0):

                    //CONTROLAMOS EL TOTAL DE LINEAS PARA NUEVA PAGINA
                    if ($cantXpag <= $total):
                        $pdf->ezNewPage();
                        $this->crearCabeceraCondicionesServicio($rowOrdenContratacion->ID_ORDEN_CONTRATACION);
                    endif;

                    //SALTAMOS DE FILA
                    $fila = $fila - 5;

                    $pdf->addText(40, $fila, $tamano_letra, "<b>" . $auxiliar->traduce("Nº Bultos", $idIdiomaImpresion) . ": </b>");
                    $pdf->addText(140, $fila, $tamano_letra, "<b>" . $auxiliar->traduce("Peso (kg)", $idIdiomaImpresion) . ": </b>");
                    $pdf->addText(240, $fila, $tamano_letra, "<b>" . $auxiliar->traduce("Peso Calculado", $idIdiomaImpresion) . " (Kg): </b>");
                    $pdf->addText(380, $fila, $tamano_letra, "<b>" . $auxiliar->traduce("Dim. (L x An x Al en mm)", $idIdiomaImpresion) . ": </b>");

                    //SALTAMOS DE FILA
                    $fila  = $fila - 13;
                    $total = $total + 1;

                    //PINTAMOS LOS DATOS DE LOS PAQUETES
                    while ($rowPaquete = $bd->SigReg($resultPaquetes)):

                        //CALCULAMOS EL PESO CUBICAJE PARA VER SI APLICAR SUPLEMENTO
                        $pesoCalculado = 0;
                        if ($rowTarifaParametros->PROVEEDOR_PAQUETERIA == "MRW")://MRW (DIMENSIONES EN CM ENTRE 5000)
                            $pesoCalculado = ($rowPaquete->LARGO_PAQUETE * $rowPaquete->ANCHO_PAQUETE * $rowPaquete->ALTO_PAQUETE) / (5000 * 10 * 10 * 10);

                        elseif ($rowTarifaParametros->PROVEEDOR_PAQUETERIA == "ASM"): //ASM (DIMENSIONES EN METROS POR 250)
                            $pesoCalculado = (250 * $rowPaquete->LARGO_PAQUETE * $rowPaquete->ANCHO_PAQUETE * $rowPaquete->ALTO_PAQUETE) / (1000 * 1000 * 1000);
                        endif;

                        //CONTROLAMOS EL TOTAL DE LINEAS PARA NUEVA PAGINA
                        if ($cantXpag <= $total):
                            $pdf->ezNewPage();
                            $this->crearCabeceraCondicionesServicio($rowOrdenContratacion->ID_ORDEN_CONTRATACION);

                            //PINTAMOS LA CABECERA DE LOS BULTOS EN CASO DE SALTAR DE PAGINA
                            $pdf->addText(40, $fila, $tamano_letra, "<b>" . $auxiliar->traduce("Nº Bultos", $idIdiomaImpresion) . ": </b>");
                            $pdf->addText(140, $fila, $tamano_letra, "<b>" . $auxiliar->traduce("Peso (kg)", $idIdiomaImpresion) . ": </b>");
                            $pdf->addText(240, $fila, $tamano_letra, "<b>" . $auxiliar->traduce("Peso Calculado", $idIdiomaImpresion) . " (Kg): </b>");
                            $pdf->addText(380, $fila, $tamano_letra, "<b>" . $auxiliar->traduce("Dim. (L x An x Al en mm)", $idIdiomaImpresion) . ": </b>");

                            //SALTAMOS DE FILA
                            $fila  = $fila - 13;
                            $total = $total + 1;
                        endif;

                        //PINTAMOS LOS DATOS DE LOS BULTOS
                        $pdf->addText(40, $fila, $tamano_letra, $auxiliar->formatoNumero($rowPaquete->NUMERO_BULTOS));
                        $pdf->addText(140, $fila, $tamano_letra, $auxiliar->formatoNumero($rowPaquete->PESO_PAQUETE));
                        $pdf->addText(240, $fila, $tamano_letra, $auxiliar->formatoNumero($pesoCalculado));
                        $pdf->addText(380, $fila, $tamano_letra, $auxiliar->formatoNumero($rowPaquete->LARGO_PAQUETE) . " x " . $auxiliar->formatoNumero($rowPaquete->ANCHO_PAQUETE) . " x " . $auxiliar->formatoNumero($rowPaquete->ALTO_PAQUETE));

                        //SALTAMOS DE FILA
                        $fila  = $fila - 13;
                        $total = $total + 1;

                    endwhile;
                endif;//FIN HAY PAQUETES
            endif;//FIN GESTIONA BULTOS


        endif;//FIN SI TIENE LICITACION/TARIFA


        //BUSCAMOS SI EL SERVICIO TIENE CLAUSULAS
        $sqlClausulas    = "SELECT DISTINCT C.ID_CLAUSULA_CONTRATACION, C.DESCRIPCION_CLAUSULA, C.DESCRIPCION_CLAUSULA_ENG, SC.OBLIGATORIA
                                                    FROM CLAUSULA_CONTRATACION C
                                                    INNER JOIN SERVICIO_CLAUSULA_CONTRATACION SC ON SC.ID_CLAUSULA_CONTRATACION = C.ID_CLAUSULA_CONTRATACION
                                                    INNER JOIN ORDEN_CONTRATACION_SERVICIO_CLAUSULA OCSC ON OCSC. ID_SERVICIO_CLAUSULA_CONTRATACION = SC.ID_SERVICIO_CLAUSULA_CONTRATACION
                                                    WHERE C.TIPO_CLAUSULA = 'Clausula' AND C.BAJA = 0 AND SC.BAJA = 0 AND OCSC.BAJA = 0 AND SC.ID_SERVICIO = $rowOrdenContratacion->ID_SERVICIO AND OCSC.ID_ORDEN_CONTRATACION = $rowOrdenContratacion->ID_ORDEN_CONTRATACION";
        $resultClausulas = $bd->ExecSQL($sqlClausulas);
        if ($bd->NumRegs($resultClausulas) > 0):

            //COMPROBAMOS SI VAN A CABER LAS CLAUSULAS EN LA PAGINA ACTUAL
            if ($cantXpag <= $total + 3 + $bd->NumRegs($resultClausulas)):
                $pdf->ezNewPage();
                $this->crearCabeceraCondicionesServicio($rowOrdenContratacion->ID_ORDEN_CONTRATACION);

            else://LINEA AZUL PARA SEPARAR DE LA TARIFA
                //FILA AZUL PARA SEPARAR DATOS DE CABECERA DE CABECERA DE LINEAS
                $pdf->setStrokeColor(0, 0, 0); //AZUL
                $pdf->setLineStyle(1);
                $pdf->line(20, $fila, 575, $fila); //LINEA AZUL
            endif;

            //ACTUALIZAMOS EL TOTAL
            $total = $total + 3;

            //CALCULAMOS LA NUEVA FILA
            $fila = $fila - 20;

            $pdf->addText(20, $fila, $tamanoLetraCuadros, "<b>" . $auxiliar->traduce("Condiciones de Contratacion", $idIdiomaImpresion) . ": </b>");

            //CALCULAMOS LA NUEVA FILA
            $fila = $fila - 22;

            while ($rowClausulas = $bd->SigReg($resultClausulas)):
                $total = $total + 1;

                if ($cantXpag <= $total):
                    $pdf->ezNewPage();
                    $this->crearCabeceraCondicionesServicio($rowOrdenContratacion->ID_ORDEN_CONTRATACION);
                endif;

                $pdf->addText(40, $fila, $tamano_letra, "<b>-" . ($idIdiomaImpresion == "ESP" ? $rowClausulas->DESCRIPCION_CLAUSULA : $rowClausulas->DESCRIPCION_CLAUSULA_ENG) . "</b>");

                //CALCULAMOS LA NUEVA FILA
                $fila = $fila - 14;

            endwhile;
        endif;


        //BUSCAMOS SI EL SERVICIO TIENE CLAUSULAS
        $sqlRequisitos    = "SELECT DISTINCT C.ID_CLAUSULA_CONTRATACION, C.DESCRIPCION_CLAUSULA, C.DESCRIPCION_CLAUSULA_ENG, SC.OBLIGATORIA
                                                    FROM CLAUSULA_CONTRATACION C
                                                    INNER JOIN SERVICIO_CLAUSULA_CONTRATACION SC ON SC.ID_CLAUSULA_CONTRATACION = C.ID_CLAUSULA_CONTRATACION
                                                    INNER JOIN ORDEN_CONTRATACION_SERVICIO_CLAUSULA OCSC ON OCSC. ID_SERVICIO_CLAUSULA_CONTRATACION = SC.ID_SERVICIO_CLAUSULA_CONTRATACION
                                                    WHERE C.TIPO_CLAUSULA = 'Requisito' AND C.BAJA = 0 AND SC.BAJA = 0 AND OCSC.BAJA = 0 AND SC.ID_SERVICIO = $rowOrdenContratacion->ID_SERVICIO AND OCSC.ID_ORDEN_CONTRATACION = $rowOrdenContratacion->ID_ORDEN_CONTRATACION";
        $resultRequisitos = $bd->ExecSQL($sqlRequisitos);
        if ($bd->NumRegs($resultRequisitos) > 0):

            //COMPROBAMOS SI VAN A CABER LAS CLAUSULAS EN LA PAGINA ACTUAL
            if ($cantXpag <= $total + 3 + $bd->NumRegs($resultRequisitos)):
                $pdf->ezNewPage();
                $this->crearCabeceraCondicionesServicio($rowOrdenContratacion->ID_ORDEN_CONTRATACION);

            elseif ($bd->NumRegs($resultClausulas) == 0)://EN CASO DE NO HABERSE PINTADO CON LAS CLAULAS, LINEA AZUL PARA SEPARAR DE LA TARIFA
                //FILA AZUL PARA SEPARAR DATOS DE CABECERA DE CABECERA DE LINEAS
                $pdf->setStrokeColor(0, 0, 0); //AZUL
                $pdf->setLineStyle(1);
                $pdf->line(20, $fila, 575, $fila); //LINEA AZUL

                //CALCULAMOS LA NUEVA FILA
                $fila = $fila - 5;
            endif;

            //ACTUALIZAMOS EL TOTAL
            $total = $total + 3;

            //CALCULAMOS LA NUEVA FILA
            $fila = $fila - 15;

            $pdf->addText(20, $fila, $tamanoLetraCuadros, "<b>" . $auxiliar->traduce("Requisitos de Contratacion", $idIdiomaImpresion) . ": </b>");

            //CALCULAMOS LA NUEVA FILA
            $fila = $fila - 22;

            while ($rowClausulas = $bd->SigReg($resultRequisitos)):
                $numLineas = $this->numeroLineasTexto("-" . ($idIdiomaImpresion == "ESP" ? $rowClausulas->DESCRIPCION_CLAUSULA : $rowClausulas->DESCRIPCION_CLAUSULA_ENG), 500, $tamano_letra);

                $total = $total + $numLineas;

                if ($cantXpag <= $total):
                    $pdf->ezNewPage();
                    $this->crearCabeceraCondicionesServicio($rowOrdenContratacion->ID_ORDEN_CONTRATACION);
                endif;

                $this->pintaTextoEnVariasLineas("-" . ($idIdiomaImpresion == "ESP" ? $rowClausulas->DESCRIPCION_CLAUSULA : $rowClausulas->DESCRIPCION_CLAUSULA_ENG), 500, 40, $fila, $tamano_letra, 14, $numLineas, 'negrita');

                //CALCULAMOS LA NUEVA FILA
                $fila = $fila - (14 * $numLineas);

            endwhile;
        endif;


        //MOSTRAMOS EL MENSAJE FINAL
        $total = $total + 3;
        if ($cantXpag <= $total):
            $pdf->ezNewPage();
            $this->crearCabeceraContratacion($rowOrdenContratacion->ID_ORDEN_CONTRATACION);
        endif;

        $pdf->addText(20, 55, $tamano_letra, '<b>*' . $auxiliar->traduce("Para la presentacion de las facturas al cobro sera necesario indicar en la factura el nº de contratacion y la referencia de facturacion.", $idIdiomaImpresion) . '</b>');
        $pdf->addText(20, 42, $tamano_letra, '<b>' . $auxiliar->traduce("La referencia de facturacion sera entregada por Acciona al vencimiento del periodo de facturacion.", $idIdiomaImpresion) . '</b>');

    }


    //CHECKLIST DE CONTRUCCION
    function imprimirCabeceraChecklistConstruccion($idOrdenMontajeParametroAccion)
    {

        global $auxiliar;
        global $bd;
        global $pdf;

        global $fila;
        global $total;

        global $tamanoTitulos;
        global $tamanoValores;
        global $pathClases;

        global $idIdiomaImpresion;
        global $cantXpag;
        global $altura;

        //BUSCO LA CHECKLIST ACCION
        $rowChecklistAccion = $bd->VerReg("ORDEN_MONTAJE_PARAMETROS_ACCION", "ID_ORDEN_MONTAJE_PARAMETROS_ACCION", $idOrdenMontajeParametroAccion);

        //BUSCO LA CHECKLIST
        $rowChecklist = $bd->VerReg("PARAMETROS_MONTAJE", "ID_PARAMETROS_MONTAJE", $rowChecklistAccion->ID_PARAMETROS_MONTAJE);

        //BUSCAMOS LA ORDEN DE MONTAJE Y LA OPERACION
        $sqlDatos = "SELECT OM.NUMERO_ORDEN_MONTAJE, O.CODIGO_OPERACION, O.DESCRIPCION_OPERACION, O.DESCRIPCION_OPERACION_ENG,
                            U.UBICACION, U.NOMBRE_MAQUINA, UOP.TIPO_UOP_ESP, UOP.TIPO_UOP_ENG, A.REFERENCIA, CF.DENOMINACION_CENTRO_FISICO, OM.ID_PROVEEDOR_CONTRATA
                            FROM ORDEN_MONTAJE OM
                            INNER JOIN OPERACION_MONTAJE_INSTALACION OI ON OI.ID_OPERACION_MONTAJE_INSTALACION = OM.ID_OPERACION_MONTAJE_INSTALACION
                            INNER JOIN OPERACION_MONTAJE O ON O.ID_OPERACION_MONTAJE = OI.ID_OPERACION_MONTAJE
                            INNER JOIN UBICACION U ON U.ID_UBICACION = OM.ID_UBICACION_MAQUINA
                            INNER JOIN UNIDAD_ORGANIZATIVA_PROCESO UOP ON UOP.ID_UNIDAD_ORGANIZATIVA_PROCESO = U.ID_UNIDAD_ORGANIZATIVA_PROCESO
                            INNER JOIN ALMACEN A ON A.ID_ALMACEN = OM.ID_ALMACEN_INSTALACION
                            INNER JOIN CENTRO_FISICO CF ON CF.ID_CENTRO_FISICO = A.ID_CENTRO_FISICO
                            WHERE OM.ID_ORDEN_MONTAJE = " . $rowChecklistAccion->ID_ORDEN_MONTAJE;

        $resultDatos = $bd->ExecSQL($sqlDatos);
        $rowDatos    = $bd->SigReg($resultDatos);

        //BUSCAMOS SUPERVISOR CALIDAD
        $sqlSupervisoresCalidad    = "SELECT DISTINCT OMLP.ID_ORDEN_MONTAJE_LEAD_PERSON, A.NOMBRE
                                        FROM ORDEN_MONTAJE_LEAD_PERSON OMLP
                                        INNER JOIN ADMINISTRADOR A ON A.ID_ADMINISTRADOR = OMLP.ID_ADMINISTRADOR
                                        WHERE OMLP.ID_ORDEN_MONTAJE = $rowChecklistAccion->ID_ORDEN_MONTAJE  AND OMLP.TIPO_LEAD_PERSON = 'Calidad' AND OMLP.BAJA = 0";
        $resultSupervisoresCalidad = $bd->ExecSQL($sqlSupervisoresCalidad);
        $numSupervisoresCalidad    = $bd->NumRegs($resultSupervisoresCalidad);


        //DEFINO LA ALTURA Y LA ANCHURA
        $altura     = 750;
        $anchura    = 50;
        $total      = 0;
        $saltoLinea = 17;

        //PINTAMOS FECHA E INSTALACION
        $pdf->addText($anchura, $altura, $tamanoTitulos, "<b>" . strtoupper($auxiliar->traduce("Instalacion", $idIdiomaImpresion)) . ":</b> " . $rowDatos->DENOMINACION_CENTRO_FISICO);

        //PINTAMOS FECHA
        $pdf->addText($anchura + 360, $altura, $tamanoTitulos, "<b>" . strtoupper($auxiliar->traduce("Fecha", $idIdiomaImpresion)) . ":</b> " . $auxiliar->fechaFmtoEsp($rowChecklistAccion->FECHA_RELLENADO));

        //REDEFINIMOS LA ALTURA
        $altura = $altura - $saltoLinea;

        //UOP
        $textoUOP          = "<b>" . strtoupper($auxiliar->traduce("Unidad Organizativa", $idIdiomaImpresion)) . ":</b> " . $rowDatos->UBICACION . " - " . $rowDatos->NOMBRE_MAQUINA . " (" . ($idIdiomaImpresion == "ESP" ? $rowDatos->TIPO_UOP_ESP : $rowDatos->TIPO_UOP_ENG) . ")";
        $numLineasPintadas = $this->pintaTextoEnVariasLineas($textoUOP, 550, $anchura, $altura, $tamanoTitulos, $saltoLinea, 2);

        //REDEFINIMOS LA ALTURA
        $altura = $altura - ($saltoLinea * $numLineasPintadas);

        //PINTAMOS OPERACION Y BAJAMOS DE ALTURA
        $textoOperacion    = "<b>" . strtr(strtoupper($auxiliar->traduce("Operacion", $idIdiomaImpresion)), "ó", "Ó") . ":</b> " . $rowDatos->CODIGO_OPERACION . " - " . ($idIdiomaImpresion == "ESP" ? $rowDatos->DESCRIPCION_OPERACION : $rowDatos->DESCRIPCION_OPERACION_ENG);
        $numLineasPintadas = $this->pintaTextoEnVariasLineas($textoOperacion, 550, $anchura, $altura, $tamanoTitulos, $saltoLinea, 2);

        //REDEFINIMOS LA ALTURA
        $altura = $altura - ($saltoLinea * $numLineasPintadas);

        //PINTAMOS LA CONTRATA Y EL PEDIDO SAP
        if (strcmp((string)$rowDatos->ID_PROVEEDOR_CONTRATA, "") != 0):
            $sqlContrato    = "SELECT NOMBRE FROM PROVEEDOR WHERE ID_PROVEEDOR = " . $rowDatos->ID_PROVEEDOR_CONTRATA;
            $resultContrato = $bd->ExecSQL($sqlContrato);
            $rowContrato    = $bd->SigReg($resultContrato);
            $pdf->addText($anchura, $altura, $tamanoTitulos, "<b>" . strtoupper($auxiliar->traduce("Contrata", $idIdiomaImpresion)) . ":</b> " . (strlen($rowContrato->NOMBRE) > 50 ? substr($rowContrato->NOMBRE, 0, 47) . "..." : $rowContrato->NOMBRE));
        else:
            $pdf->addText($anchura, $altura, $tamanoTitulos, "<b>" . strtoupper($auxiliar->traduce("Contrata", $idIdiomaImpresion)) . ":</b> -");
        endif;
        $pdf->addText($anchura + 360, $altura, $tamanoTitulos, "<b>" . strtoupper($auxiliar->traduce("Contrato", $idIdiomaImpresion)) . ":</b> " . $rowChecklist->PEDIDO_SAP_PARAMETRO);

        //REDEFINIMOS LA ALTURA
        $altura = $altura - ($saltoLinea * $numLineasPintadas);

        //PINTAMOS INSPECTOR
        if ($numSupervisoresCalidad > 0):
            $textoInspector = "<b>" . strtoupper($auxiliar->traduce("Inspector", $idIdiomaImpresion)) . ":</b> ";
            $comaInspector  = "";
            while ($rowSupervisorCalidad = $bd->SigReg($resultSupervisoresCalidad)):
                $textoInspector .= $comaInspector . $rowSupervisorCalidad->NOMBRE;
                $comaInspector  = ", ";
            endwhile;
        else:
            $textoInspector = "<b>" . strtoupper($auxiliar->traduce("Inspector", $idIdiomaImpresion)) . ":</b> -";
        endif;
        $numLineasPintadas = $this->pintaTextoEnVariasLineas($textoInspector, 540, $anchura, $altura, $tamanoTitulos, $saltoLinea, 2);

        //REDEFINIMOS LA ALTURA
        $altura = $altura - ($saltoLinea * $numLineasPintadas) - 12;

    }

    //CHECKLIST DE CONTRUCCION
    function imprimirChecklistConstruccion($idOrdenMontajeParametroAccion)
    {

        global $auxiliar;
        global $bd;
        global $pdf;
        global $orden_montaje;

        global $fila;
        global $total;

        global $tamanoTitulos;
        global $tamanoValores;

        global $idIdiomaImpresion;
        global $cantXpag;
        global $altura;
        global $pathClases;


        //IMPRIMIMOS LA CABECERA
        $this->imprimirCabeceraChecklistConstruccion($idOrdenMontajeParametroAccion);

        //BUSCO LA CHECKLIST ACCION
        $rowChecklistAccion = $bd->VerReg("ORDEN_MONTAJE_PARAMETROS_ACCION", "ID_ORDEN_MONTAJE_PARAMETROS_ACCION", $idOrdenMontajeParametroAccion);

        //BUSCO LA CHECKLIST
        $rowChecklist = $bd->VerReg("PARAMETROS_MONTAJE", "ID_PARAMETROS_MONTAJE", $rowChecklistAccion->ID_PARAMETROS_MONTAJE);

        //DEFINIMOS ANCHO Y SALTO LINEA
        $anchura     = 50;
        $alturaLinea = 20;

        //DEFINIMOS MARGEN IZQ PARA LAS TABLAS
        $margenIzq = 80;

        //PINTO RECUADRO GRIS CON EL TITULO
        $pdf->setStrokeColor(0.3, 0.3, 0.3);
        $pdf->setLineStyle(20);
        $pdf->line($anchura, $altura, 540, $altura);
        $pdf->setColor(1, 1, 1);
        $pdf->addText($anchura + 3, $altura - 3, 10, ($idIdiomaImpresion == "ESP" ? $rowChecklist->NOMBRE_PARAMETRO_ESP : $rowChecklist->NOMBRE_PARAMETRO_ENG));
        //$pdf->addText($anchura + 410, $altura - 3, 10, $auxiliar->traduce("OK", $idIdiomaImpresion));
        //$pdf->addText($anchura + 437, $altura - 3, 10, $auxiliar->traduce("NOK", $idIdiomaImpresion));
        //$pdf->addText($anchura + 468, $altura - 3, 10, $auxiliar->traduce("NA", $idIdiomaImpresion));
        //CUADRO DE DATOS
        $pdf->setColor(0, 0, 0);
        $pdf->setLineStyle(1);
        $pdf->rectangle($anchura, $altura - 7, 540 - $anchura, 15);

        //REDEFINIMOS ALTURA
        $altura = $altura - $alturaLinea;

        //BUSCAMOS LAS LINEAS DE LA CHECKLIST
        $resultPosiciones = $orden_montaje->getPosicionesChecklistOrdenMontaje($rowChecklistAccion->ID_ORDEN_MONTAJE_PARAMETROS_ACCION);


        //RECORREMOS  LAS POSICIONES
        $numOK  = 0;
        $numNOK = 0;
        $numNA  = 0;
        while ($rowPosicion = $bd->SigReg($resultPosiciones)):

            //SI LA POSICION TIENE PARAMETROS LOS OBTENEMOS
            $numParametrosPosiciones = 0;
            if (($rowPosicion->TIPO_POSICION == "Parametro") || ($rowPosicion->TIPO_POSICION == "Combinada")):
                $resultParametrosPosiciones = $orden_montaje->getParametrosPosicionesChecklistOrdenMontaje($rowPosicion->ID_ORDEN_MONTAJE_PARAMETROS_ACCION_CHECKLIST);
                $numParametrosPosiciones    = $bd->NumRegs($resultParametrosPosiciones);
            endif;

            //SI LA POSICION ES TABLA, LA OBTENEMOS
            $numFilasTablaPosicion = 0;
            if ($rowPosicion->TIPO_POSICION == "Tabla"):
                //BUSCAMOS LAS COLUMNAS DE LA POSICION
                $resultColumnasPosicion = $orden_montaje->getTitulosTablaPosicionesChecklistOrdenMontaje($rowPosicion->ID_ORDEN_MONTAJE_PARAMETROS_ACCION_CHECKLIST, "Columna");

                //BUSCAMOS LAS FILAS DE LA POSICION
                $resultFilasPosicion = $orden_montaje->getTitulosTablaPosicionesChecklistOrdenMontaje($rowPosicion->ID_ORDEN_MONTAJE_PARAMETROS_ACCION_CHECKLIST, "Fila");

                $numFilasTablaPosicion = $bd->NumRegs($resultFilasPosicion) + 1;
            endif;

            //CALCULAMOS EL NUMERO DE LINEAS QUE NECESITAMOS
            $numLineasCondicion = $this->numeroLineasTexto(($idIdiomaImpresion == "ESP" ? $rowPosicion->NOMBRE_CONDICION_ESP : $rowPosicion->NOMBRE_CONDICION_ENG), 350, $tamanoValores);
            $total              = $total + $numLineasCondicion + $numParametrosPosiciones + $numFilasTablaPosicion + ($rowPosicion->OBSERVACIONES != "" ? 1 : 0);

            if ($cantXpag <= $total):
                $pdf->ezNewPage();
                $altura   = 750;
                $total    = $numLineasCondicion;
                $cantXpag = 35; //ACTUALIZAMOS LA CANTIDAD POR PAGINA YA QUE NO HAY CABECERA Y CABEN MAS
            endif;

            //PINTO LAS CARACTERISTICAS DE LA DOCUMENTACION
            $this->pintaTextoEnVariasLineas(($idIdiomaImpresion == "ESP" ? $rowPosicion->NOMBRE_CONDICION_ESP : $rowPosicion->NOMBRE_CONDICION_ENG), 350, $anchura + 3, $altura - 3, $tamanoValores, $alturaLinea - 3);

            $pdf->rectangle($anchura, $altura - 7 - (($numLineasCondicion - 1) * $alturaLinea), 540 - $anchura, ($numLineasCondicion * $alturaLinea));

            //SI ES TIPO TEXTO MOSTRAMOS SOLO UN RECTANGULO
            if ($rowPosicion->TIPO_POSICION == "Texto"):
                $pdf->rectangle($anchura + 370, $altura - 7 - (($numLineasCondicion - 1) * $alturaLinea), 120, ($numLineasCondicion * $alturaLinea));

                $pdf->addText($anchura + 372, $altura - 3, 10, (strlen($rowPosicion->TEXTO_POSICION) > 20 ? substr($rowPosicion->TEXTO_POSICION, 0, 18) . "..." : $rowPosicion->TEXTO_POSICION));

            else:
                $pdf->rectangle($anchura + 370, $altura - 7 - (($numLineasCondicion - 1) * $alturaLinea), 40, ($numLineasCondicion * $alturaLinea));
                $pdf->rectangle($anchura + 410, $altura - 7 - (($numLineasCondicion - 1) * $alturaLinea), 40, ($numLineasCondicion * $alturaLinea));
                $pdf->rectangle($anchura + 450, $altura - 7 - (($numLineasCondicion - 1) * $alturaLinea), 40, ($numLineasCondicion * $alturaLinea));

                $pdf->addText($anchura + 372, $altura - 3, 10, $auxiliar->traduce(($rowPosicion->TIPO_POSICION == "Si/No" ? "Si" : "OK"), $idIdiomaImpresion) . ":");
                $pdf->addText($anchura + 412, $altura - 3, 10, $auxiliar->traduce(($rowPosicion->TIPO_POSICION == "Si/No" ? "No" : "NOK"), $idIdiomaImpresion) . ":");
                $pdf->addText($anchura + 452, $altura - 3, 10, $auxiliar->traduce("NA", $idIdiomaImpresion) . ":");

                if ($rowPosicion->DECISION_POSICION == "OK"):
                    //PINTO EL LOGO
                    $pdf->setColor(0, 0.7, 0);
                    //CARGAMOS LA LIBRERIA DE SIMBOLOS PARA PINTAR EL TICK
                    $pdf->selectFont($pathClases . 'lib/pdfClasses/fonts/Symbol.afm', 'none');
                    $pdf->addText($anchura + 400, $altura - 3, $tamanoValores, "<b>" . "\xD6" . "</b>");
                    //VOLVEMOS A CARGAR LA FUENTE NORMAL
                    $pdf->selectFont($pathClases . 'lib/pdfClasses/fonts/Helvetica.afm');

                    $numOK++;
                elseif ($rowPosicion->DECISION_POSICION == "NOK"):
                    $pdf->setColor(1, 0, 0);
                    $pdf->addText($anchura + 440, $altura - 3, $tamanoValores, "<b>" . 'X' . "</b>");
                    $numNOK++;
                elseif ($rowPosicion->DECISION_POSICION == "No Aplica"):
                    $pdf->setColor(1, 0, 0);
                    $pdf->addText($anchura + 475, $altura - 3, $tamanoValores, "<b>" . '-' . "</b>");
                    $numNA++;
                endif;
                $pdf->setColor(0, 0, 0);
            endif;

            //REDEFINIMOS ALTURA
            $altura = $altura - ($numLineasCondicion * $alturaLinea);

            //SI TIENE PARAMETROS LOS PINTAMOS
            if ($numParametrosPosiciones > 0):
                while ($rowParametrosPosicion = $bd->SigReg($resultParametrosPosiciones)):

                    $nombreParametro = ($idIdiomaImpresion == "ESP" ? $rowParametrosPosicion->NOMBRE_PARAMETRO_ESP : $rowParametrosPosicion->NOMBRE_PARAMETRO_ENG);
                    $nombreParametro = (strlen($nombreParametro) > 85 ? substr($nombreParametro, 0, 85) . "..." : $nombreParametro);

                    //PINTO EL PARAMETRO
                    $pdf->rectangle($anchura + 5, $altura - 2, 535 - $anchura, $alturaLinea - 5);
                    $pdf->addText($anchura + 8, $altura + 2, $tamanoValores - 2, $nombreParametro);
                    $pdf->rectangle($anchura + 370, $altura - 2, 120, $alturaLinea - 5);
                    $pdf->addText($anchura + 373, $altura + 2, $tamanoValores, $rowParametrosPosicion->VALOR_REAL);

                    //REDEFINIMOS ALTURA
                    $altura = $altura - ($alturaLinea - 5);
                endwhile;
            endif;

            //SI TIENE, PINTAMOS LAS OBSERVACIONES
            if ($rowPosicion->OBSERVACIONES != ""):
                //PINTO LAS CARACTERISTICAS DE LA DOCUMENTACION
                $pdf->addText($anchura + 8, $altura - 3, $tamanoValores - 2, "<b>" . $auxiliar->traduce("Obs.", $idIdiomaImpresion) . ": </b>" . (strlen($rowPosicion->OBSERVACIONES) > 100 ? substr($rowPosicion->OBSERVACIONES, 0, 100) . "..." : $rowPosicion->OBSERVACIONES));
                $pdf->rectangle($anchura + 5, $altura - 7, 535 - $anchura, $alturaLinea);

                //REDEFINIMOS ALTURA
                $altura = $altura - $alturaLinea;
            endif;

            //SI TIENE TABLA LA PINTAMOS
            if ($rowPosicion->TIPO_POSICION == "Tabla"):
                if (($bd->NumRegs($resultColumnasPosicion) > 0) && ($bd->NumRegs($resultFilasPosicion) > 0)):

                    //AGREGAMOS UNA LÍNEA DE SEPARACIÓN
                    $altura = $altura - $alturaLinea;

                    //CALCULAMOS EL WIDTH DE CADA COLUMNA
                    $widthCol = 500 / $bd->NumRegs($resultColumnasPosicion);

                    //CREO UNA VARIABLE PARA SABER DÓNDE TENGO QUE PINTAR LOS REGISTROS DE LA TABLA
                    $ejeX = $margenIzq;

                    //RECORRO LAS COLUMNAS PARA ESCRIBIR LOS NOMBRES
                    while ($rowColumnasPosicion = $bd->SigReg($resultColumnasPosicion)):
                        $pdf->rectangle($ejeX, $altura - 7 - (($numLineasCondicion - 1) * $alturaLinea), $widthCol, ($numLineasCondicion * $alturaLinea));
                        $pdf->addText($ejeX + 3, $altura - 3, $tamanoValores, "<b>" . (strlen($rowColumnasPosicion->NOMBRE_REGISTRO_ESP) > 10 ? substr($rowColumnasPosicion->NOMBRE_REGISTRO_ESP, 0, 13) . "..." : $rowColumnasPosicion->NOMBRE_REGISTRO_ESP) . "</b>");
                        //ACTUALIZO LA VARIABLE SUMÁNDOLE EL TAMAÑO DE LA COLUMNA
                        $ejeX += $widthCol;
                    endwhile; //FIN RECORRO LAS COLUMNAS PARA ESCRIBIR LOS NOMBRES

                    //CREO UNA NUEVA FILA
                    $altura = $altura - $alturaLinea;

                    //VUELVO A INDICAR A LA VARIABLE DE LA POSICIÓN EL PUNTO INICIAL
                    $ejeX = $margenIzq;

                    //ANCHO PARA LOS TÍTULOS DE LAS FILAS
                    $anchoTituloFila = 500 / 7;

                    //RECORRO LAS FILAS
                    while ($rowFilasPosicion = $bd->SigReg($resultFilasPosicion)):
                        //LOS TÍTULOS DE LAS FILAS SE PINTAN CON UN MARGEN INFERIOR A LOS DE LAS COLUMNAS
                        $pdf->rectangle(10, $altura - 7 - (($numLineasCondicion - 1) * $alturaLinea), $anchoTituloFila - 1, ($numLineasCondicion * $alturaLinea));
                        $pdf->addText(13, $altura - 3, $tamanoValores, "<b>" . (strlen($rowFilasPosicion->NOMBRE_REGISTRO_ESP) > 13 ? substr($rowFilasPosicion->NOMBRE_REGISTRO_ESP, 0, 10) . "..." : $rowFilasPosicion->NOMBRE_REGISTRO_ESP) . "</b>");

                        //MOVEMOS EL CURSOR DE LAS COLUMNAS
                        $bd->Mover($resultColumnasPosicion, 0);
                        //RECORRO LAS COLUMNAS PARA PINTAR SUS VALORES
                        while ($rowColumnasPosicion = $bd->SigReg($resultColumnasPosicion)):
                            //BUSCAMOS LA POSICION
                            $resultPosicionTabla = $orden_montaje->getRegistroTablaPosicionesChecklistOrdenMontaje($rowPosicion->ID_ORDEN_MONTAJE_PARAMETROS_ACCION_CHECKLIST, $rowColumnasPosicion->ID_PARAMETROS_MONTAJE_CHECKLIST_TABLA, $rowFilasPosicion->ID_PARAMETROS_MONTAJE_CHECKLIST_TABLA);
                            $rowPosicionTabla    = $bd->SigReg($resultPosicionTabla);
                            $pdf->rectangle($ejeX, $altura - 7 - (($numLineasCondicion - 1) * $alturaLinea), $widthCol, ($numLineasCondicion * $alturaLinea));
                            if ($rowPosicionTabla->TIPO_OBJETO_COLUMNA == "Texto" || $rowPosicionTabla->TIPO_OBJETO_COLUMNA == "Numero"):
                                $pdf->addText($ejeX + 3, $altura - 3, $tamanoValores, (strlen($rowPosicionTabla->VALOR_TABLA) > 10 ? substr($rowPosicionTabla->VALOR_TABLA, 0, 13) . "..." : $rowPosicionTabla->VALOR_TABLA));
                            elseif ($rowPosicionTabla->TIPO_OBJETO_COLUMNA == "Si/No"):
                                $pdf->addText($ejeX + 3, $altura - 3, $tamanoValores, $rowPosicionTabla->DECISION_TABLA != "" ? $auxiliar->traduce($rowPosicionTabla->DECISION_TABLA, $idIdiomaImpresion) : "-");
                            endif;
                            $ejeX += $widthCol;
                        endwhile; //FIN RECORRO LAS COLUMNAS PARA PINTAR SUS VALORES
                        $ejeX   = $margenIzq;
                        $altura = $altura - $alturaLinea;
                    endwhile; //FIN RECORRO LAS FILAS
                endif;

                //REDEFINIMOS ALTURA
                $altura = $altura - $alturaLinea;
            endif;
            //FIN SI TIENE TABLA LA PINTAMOS

        endwhile;

        //MOSTRAMOS USUARIO y RESUMEN
        $pdf->addText($anchura + 3, $altura - 3, $tamanoValores, "<b>" . $auxiliar->traduce("Totales", $idIdiomaImpresion) . "</b> ");
        $pdf->addText($anchura + 412, $altura - 3, $tamanoValores, "<b>" . $numOK . "</b>");
        $pdf->addText($anchura + 442, $altura - 3, $tamanoValores, "<b>" . $numNOK . "</b>");
        $pdf->addText($anchura + 472, $altura - 3, $tamanoValores, "<b>" . $numNA . "</b>");

        //REDEFINIMOS ALTURA
        $altura = $altura - $alturaLinea - 10;

        //INCLUYO LAS FIRMAS
        $sqlFirmas    = "SELECT DISTINCT PMF.ID_PARAMETROS_MONTAJE_FIRMA, PMF.ID_PARAMETROS_MONTAJE, PMF.ROL_FIRMA,
                                      OMPAF.ID_ORDEN_MONTAJE_PARAMETROS_ACCION_FIRMA, OMPAF.ARCHIVO_FIRMA
                      FROM PARAMETROS_MONTAJE_FIRMA PMF
                      INNER JOIN ORDEN_MONTAJE_PARAMETROS_ACCION_FIRMA OMPAF ON OMPAF.ID_PARAMETROS_MONTAJE_FIRMA = PMF.ID_PARAMETROS_MONTAJE_FIRMA
                      WHERE PMF.ID_PARAMETROS_MONTAJE = " . $rowChecklist->ID_PARAMETROS_MONTAJE . " AND OMPAF.ID_ORDEN_MONTAJE_PARAMETROS_ACCION = " . $idOrdenMontajeParametroAccion;
        $resultFirmas = $bd->ExecSQL($sqlFirmas);
        $ejeY         = 0;
        while ($rowFirmas = $bd->SigReg($resultFirmas)):
            if (strcmp((string)$rowFirmas->ARCHIVO_FIRMA, "") != 0):
                $pdf->addText($anchura + 3, $altura - 3, $tamanoValores, "<b>" . $auxiliar->traduce("Firma", $idIdiomaImpresion) . ":</b> " . $rowFirmas->ROL_FIRMA);
                $pdf->addJpegFromFile($pathClases . "documentos/orden_montaje_firma/FIRMA_" . $rowFirmas->ID_ORDEN_MONTAJE_PARAMETROS_ACCION_FIRMA . "_" . $rowFirmas->ARCHIVO_FIRMA, 150, $altura - 30, 95, 0);
                $ejeY   += 40;
                $altura = $altura - $alturaLinea - $ejeY;
            endif;
        endwhile;
    }


    //FUNCIONES GENERALES
    function armoFecha($idIdiomaFecha = '')
    {
        global $auxiliar;
        global $administrador;

        //SI NO VIENE IDIOMA, TOMAMOS EL DEL ADMINISTRADOR
        if ($idIdiomaFecha == ''):
            $idIdiomaFecha = $administrador->ID_IDIOMA;
        endif;

        $dias  = array($auxiliar->traduce("Domingo", $idIdiomaFecha), $auxiliar->traduce("Lunes", $idIdiomaFecha), $auxiliar->traduce("Martes", $idIdiomaFecha), $auxiliar->traduce("Miercoles", $idIdiomaFecha), $auxiliar->traduce("Jueves", $idIdiomaFecha), $auxiliar->traduce("Viernes", $idIdiomaFecha), $auxiliar->traduce("Sabado", $idIdiomaFecha));
        $meses = array($auxiliar->traduce("Enero", $idIdiomaFecha), $auxiliar->traduce("Febrero", $idIdiomaFecha), $auxiliar->traduce("Marzo", $idIdiomaFecha), $auxiliar->traduce("Abril", $idIdiomaFecha), $auxiliar->traduce("Mayo", $idIdiomaFecha), $auxiliar->traduce("Junio", $idIdiomaFecha), $auxiliar->traduce("Julio", $idIdiomaFecha), $auxiliar->traduce("Agosto", $idIdiomaFecha), $auxiliar->traduce("Septiembre", $idIdiomaFecha), $auxiliar->traduce("Octubre", $idIdiomaFecha), $auxiliar->traduce("Noviembre", $idIdiomaFecha), $auxiliar->traduce("Diciembre", $idIdiomaFecha));

        return "" . $dias[date("w")] . ", " . date("d") . " " . ($idIdiomaFecha == "ESP" ? "de " : "") . $meses[date("n") - 1] . " " . ($idIdiomaFecha == "ESP" ? "de " : "") . date("Y");
    }

    function numeroLineasTexto($texto, $anchuraMaxima, $tamanoLetra)
    {

        global $pdf;

        $numeroLineasPintadas = 0;
        $arrTexto             = explode(" ", (string)$texto);
        $arrTextos            = array();

        foreach ($arrTexto as $valor):
            if ((isset($arrTextos[$numeroLineasPintadas])) && ($pdf->getTextWidth($tamanoLetra, $arrTextos[$numeroLineasPintadas] . " " . $valor) > $anchuraMaxima)):
                $numeroLineasPintadas = $numeroLineasPintadas + 1;
                //SI LA PALABRA POR SI SOLA NO CABE EN UNA LINEA (EJ: MUCHAS REFERENCIAS JUNTAS)
                if ($pdf->getTextWidth($tamanoLetra, $arrTextos[$numeroLineasPintadas] . " " . $valor) > $anchuraMaxima):
                    //TROCEAMOS LA PALABRA Y CONTAMOS EL Nº DE LINEAS QUE SE VAN A PINTAR
                    $this->devuelveVariasLineasPalabra($valor, $anchuraMaxima, $tamanoLetra, $arrTextos, $numeroLineasPintadas);
                else:
                    $arrTextos[$numeroLineasPintadas] = $valor;
                endif;
            else:
                if ($pdf->getTextWidth($tamanoLetra, $arrTextos[$numeroLineasPintadas] . " " . $valor) > $anchuraMaxima):
                    //TROCEAMOS LA PALABRA Y CONTAMOS EL Nº DE LINEAS QUE SE VAN A PINTAR
                    $this->devuelveVariasLineasPalabra($valor, $anchuraMaxima, $tamanoLetra, $arrTextos, $numeroLineasPintadas);
                else:
                    $arrTextos[$numeroLineasPintadas] = $arrTextos[$numeroLineasPintadas] . " " . $valor;
                endif;
            endif;
        endforeach;

        return $numeroLineasPintadas + 1;
    }

    function devuelveVariasLineasPalabra($texto, $anchuraMaxima, $tamanoLetra, &$arrTextos, &$numeroLineasPintadas)
    {
        global $pdf;

        $largoTexto = strlen($texto);
        for ($i = 0; $i < $largoTexto; $i++):
            if ($pdf->getTextWidth($tamanoLetra, $arrTextos[$numeroLineasPintadas] . substr($texto, $i, 1) . " - ") < $anchuraMaxima):
                $arrTextos[$numeroLineasPintadas] = $arrTextos[$numeroLineasPintadas] . substr($texto, $i, 1);
            else:
                $numeroLineasPintadas++;
                $arrTextos[$numeroLineasPintadas] = $arrTextos[$numeroLineasPintadas] . substr($texto, $i, 1);
            endif;
        endfor;
    }

    function pintaTextoEnVariasLineas($texto, $anchuraMaxima, $coordenadaX, $coordenadaY, $tamanoLetra, $saltofila, $numeroLineas = "", $estilo = "")
    {

        global $pdf;

        $numeroLineasPintadas = 0;
        $index                = 0;
        $arrTexto             = explode(" ", (string)$texto);
        $arrTextos            = array();

        foreach ($arrTexto as $valor):
            if ((isset($arrTextos[$index])) && ($pdf->getTextWidth($tamanoLetra, $arrTextos[$index] . " " . $valor) > $anchuraMaxima)):
                $index = $index + 1;
                //SI LA PALABRA POR SI SOLA NO CABE EN UNA LINEA (EJ: MUCHAS REFERENCIAS JUNTAS)
                if ($pdf->getTextWidth($tamanoLetra, $arrTextos[$index] . " " . $valor) > $anchuraMaxima):
                    //TROCEAMOS LA PALABRA
                    $this->devuelveVariasLineasPalabra($valor, $anchuraMaxima, $tamanoLetra, $arrTextos, $index);
                else:
                    $arrTextos[$index] = $valor;
                endif;
            elseif (isset($arrTextos[$numeroLineasPintadas]) == false):
                //SI LA PALABRA POR SI SOLA NO CABE EN UNA LINEA (EJ: MUCHAS REFERENCIAS JUNTAS)
                if ($pdf->getTextWidth($tamanoLetra, $arrTextos[$index] . " " . $valor) > $anchuraMaxima):
                    //TROCEAMOS LA PALABRA
                    $this->devuelveVariasLineasPalabra($valor, $anchuraMaxima, $tamanoLetra, $arrTextos, $index);
                else:
                    $arrTextos[$index] = $valor;
                endif;
            else:
                //SI LA PALABRA POR SI SOLA NO CABE EN UNA LINEA (EJ: MUCHAS REFERENCIAS JUNTAS)
                if ($pdf->getTextWidth($tamanoLetra, $arrTextos[$index] . " " . $valor) > $anchuraMaxima):
                    //TROCEAMOS LA PALABRA
                    $this->devuelveVariasLineasPalabra($valor, $anchuraMaxima, $tamanoLetra, $arrTextos, $index);
                else:
                    $arrTextos[$index] = $arrTextos[$index] . " " . $valor;
                endif;
            endif;
        endforeach;

        $textoInicioEstilo = "";
        $textoFinEstilo    = "";

        //ALMACENO LOS ESTILOS
        if ($estilo == 'negrita'):
            $textoInicioEstilo = "<b>";
            $textoFinEstilo    = "</b>";
        endif;


        if ($numeroLineas == ""):
            foreach ($arrTextos as $textoAlmacenado):
                $pdf->addText($coordenadaX, $coordenadaY, $tamanoLetra, $textoInicioEstilo . $textoAlmacenado . $textoFinEstilo);
                $coordenadaY          = $coordenadaY - $saltofila;
                $numeroLineasPintadas = $numeroLineasPintadas + 1;
            endforeach;
        else:
            $numeroLineas = min(array($numeroLineas, count($arrTextos)));
            for ($i = 0; $i < $numeroLineas; $i++):
                $pdf->addText($coordenadaX, $coordenadaY, $tamanoLetra, $textoInicioEstilo . $arrTextos[$i] . $textoFinEstilo);
                $coordenadaY          = $coordenadaY - $saltofila;
                $numeroLineasPintadas = $numeroLineasPintadas + 1;
            endfor;
        endif;

        return $numeroLineasPintadas;
    }

} // FIN CLASE