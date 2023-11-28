<?
$pathRaiz   = "../";
$pathClases = "../../";

// INCLUDES DE LIBRERIAS PROPIAS
require_once $pathClases . "lib/basedatos.php";
require_once $pathClases . "lib/administrador.php";
require_once $pathClases . "lib/html.php";
require_once $pathClases . "lib/navegar.php";
require_once $pathClases . "lib/comprobar.php";
require_once $pathClases . "lib/auxiliar.php";

session_start();
include $pathRaiz . "seguridad_admin.php";

$sqlWhere = "1=1";

$nombreTextoBox = "tx" . ($NombreCampo != "" ? $NombreCampo : 'Unidad');
$sqlWhere .= ($UnidadMedida == 1)? " AND ES_UNIDAD_MEDIDA = 1 ": "";
$sqlWhere .= ($UnidadCompra == 1)? " AND ES_UNIDAD_COMPRA = 1 ": "";
$sqlWhere .= ($UnidadCompra == 1 && $UnidadCompraAdmin != 1) ? " AND ES_UNIDAD_COMPRA_ADMIN = 0 ": "";


if (trim( (string)$$nombreTextoBox) != ""):

    //FILTRAMOS SEGUN SEA EL IDIOMA:
    global $administrador;
    $idIdioma = $administrador->ID_IDIOMA;
    if ($idIdioma != "ESP" && $idIdioma != "ENG"): $idIdioma = "ESP"; endif;

    //$camposBD = array('DESCRIPCION', 'UNIDAD');
    $camposBD = array('DESCRIPCION_' . $idIdioma, 'UNIDAD_' . $idIdioma);
    $sqlWhere = $sqlWhere . ($bd->busquedaTextoArray($auxiliar->to_iso88591($$nombreTextoBox), $camposBD));

    //$sql = "SELECT ID_UNIDAD, UNIDAD, DESCRIPCION FROM UNIDAD WHERE $sqlWhere AND BAJA=0 ORDER BY UNIDAD LIMIT 0,25";
    $sql    = "SELECT * FROM UNIDAD WHERE $sqlWhere AND BAJA=0 ORDER BY UNIDAD_" . $idIdioma . " LIMIT 0,25";
    $result = $bd->ExecSQL($sql);

    $total = $bd->NumRegs($result);

    if ($total > 0):
        echo '<ul class="texton" style="width:400px;">';
        while ($row = $bd->SigReg($result)):
            $idUnidad = $row->ID_UNIDAD;
            //$refUnidad = $row->UNIDAD;
            $refUnidad = $row->{'UNIDAD_' . $idIdioma};
            //$descUnidad = $row->DESCRIPCION;
            $descUnidad = $row->{'DESCRIPCION_' . $idIdioma};
            echo '<li class="texton">';
            if ($AlmacenarId == '1'):        //ficha.php
                echo '<a class="texton" href="#" onclick="return false;" alt="' . $idUnidad . '" style="white-space:pre;">';
            elseif ($AlmacenarId == '0'): //index.php (listado)
                echo '<a class="texton" href="#" onclick="return false;" alt="' . $refUnidad . '" rev="' . $idUnidad . '" style="white-space:pre;">';
            endif;
            echo $refUnidad . " - " . $descUnidad;
            echo '</a>';
            echo '</li>';
        endwhile;
        echo '</ul>';
    endif;
endif;
?>