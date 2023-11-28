<?
//echo '<pre>' , var_dump($_POST) , '</pre>';
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
require_once $pathClases . "lib/pedido.php";
require_once $pathClases . "lib/material.php";

session_start();
include $pathRaiz . "seguridad_admin.php";

$tituloPag         = $auxiliar->traduce("Ubicaciones", $administrador->ID_IDIOMA);
$tituloNav         = $auxiliar->traduce("Maestros", $administrador->ID_IDIOMA) . " >> " . $auxiliar->traduce("Ubicaciones", $administrador->ID_IDIOMA);
$ZonaTablaPadre    = "Maestros";
$ZonaSubTablaPadre = "MaestrosSubmenuAlmacen";
$ZonaTabla         = "MaestrosUbicaciones";

// COMPRUEBA SI TIENE PERMISOS
if ($administrador->Hayar_Permiso_Perfil('ADM_MAESTROS_UBICACIONES') < 2):
    $html->PagError("SinPermisos");
endif;

//COMPRUEBO QUE SE HAYA RELLENADO EL TEXTAREA
$html->PagErrorCondicionado($txLineasUbicacionPreventivo, "==", "", "LineasIntroducidasVacio");

// OBTENEMOS LA CADENA DE TEXTO CON LAS LINEAS Y METEMOS CADA LINEA EN UNA ARRAY
$arrLineas = explode("\n", $txLineasUbicacionPreventivo);

$arrValoresUbicaciones = array();
$k                     = 0;

// NOS CREAMOS EL ARRAY DEFINITIVO
if (count($arrLineas) > 0):    //HAY LINEAS
    foreach ($arrLineas as $linea):    //BUCLE LINEAS

        if ($linea != ""):    //LINEA NO VACIA

            //FORMATEMAOS LOS DATOS INTRODUCIDOS
            $linea = trim($linea);

            if ($linea == ""):
                continue;
            endif;

            //SACO LOS VALORES DE LA LINEA
            $arrValores = explode("|", $linea);

            //VARIABLE PARA SABER SI HAY ERROR EN LINEA
            $errorLinea = false;

            //COMPROBAMOS EL NÚMERO DE CAMPOS
            $numeroCampos = count($arrValores);

            if ($numeroCampos < 3):
                $strKo .= $auxiliar->traduce("Línea", $administrador->ID_IDIOMA) . " $indice. ";
                $strKo .= $auxiliar->traduce("Número de campos introducidos incorrecto", $administrador->ID_IDIOMA) . ".\n";
                $filasKo++;
                $errorLinea = true;
            endif;

            //OBTENENGO VALORES
            $refUbicacion = trim($arrValores[0]);
            $refCentro    = trim($arrValores[1]);
            $refAlmacen   = trim($arrValores[2]);

            //PREPARACION DE ALGUNOS DATOS
            $precioFijo   = "No";
            $baja         = "No";
            $esTipoSector = "No";

            $arrValoresUbicaciones[$k]['REF_UBICACION']  = strtoupper($refUbicacion);
            $arrValoresUbicaciones[$k]['REF_CENTRO']     = $refCentro;
            $arrValoresUbicaciones[$k]['REF_ALMACEN']    = $refAlmacen;
            $arrValoresUbicaciones[$k]['PRECIO_FIJO']    = $precioFijo;
            $arrValoresUbicaciones[$k]['BAJA']           = $baja;
            $arrValoresUbicaciones[$k]['ES_TIPO_SECTOR'] = $esTipoSector;
            $k++;

        endif;    //FIN LINEA NO VACIA

    endforeach;    //FIN BUCLE LINEAS

    $arrValoresUbicaciones = urlencode(serialize($arrValoresUbicaciones));
    header("location: ficha_importacion_preventivo_paso2.php?valores=$arrValoresUbicaciones");

endif;    //FIN HAY LINEAS
?>