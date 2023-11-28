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

if (trim($txFamiliaMaterial) != ""):
    if ($administrador->ID_IDIOMA == "ESP"):
        $camposBD = array( 'NOMBRE_FAMILIA');
    else:
        $camposBD = array( 'NOMBRE_FAMILIA');
    endif;
    $sqlWhere = $sqlWhere . ($bd->busquedaTextoArray($auxiliar->to_iso88591($txFamiliaMaterial), $camposBD));

    $sql    = "SELECT ID_FAMILIA_MATERIAL,NOMBRE_FAMILIA FROM FAMILIA_MATERIAL WHERE $sqlWhere ORDER BY NOMBRE_FAMILIA LIMIT 0,25";
    $result = $bd->ExecSQL($sql);

    $total = $bd->NumRegs($result);

    if ($total > 0):
        echo '<ul class="texton" style="width:400px;">';
        while ($row = $bd->SigReg($result)):
            $identFamiliaMaterial  = $row->ID_FAMILIA_MATERIAL;
            $nombreFamiliaMaterial = $administrador->ID_IDIOMA == "ESP" ? $row->NOMBRE_FAMILIA : $row->NOMBRE_FAMILIA;
            echo '<li class="texton">';
            if ($AlmacenarId == '1'):            //ficha.php
                echo '<a class="texton" href="#" onclick="return false;" alt="' . $identFamiliaMaterial . '" style="white-space:pre;">';
            elseif ($AlmacenarId == '0'): //index.php (listado)
                echo '<a class="texton" href="#" onclick="return false;" alt="' . $nombreFamiliaMaterial . '" rev="' . $identFamiliaMaterial . '" style="white-space:pre;">';
            endif;
            echo "$nombreFamiliaMaterial";
            echo '</a>';
            echo '</li>';
        endwhile;
        echo '</ul>';

    endif;
endif;
?>