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

$nombreTextoBox = "tx" . ($NombreCampo != "" ? $NombreCampo : 'Menu');

if (trim( (string)$$nombreTextoBox) != ""):

    $camposBD = array('NOMBRE', 'ID_PADRE');
    $sqlWhere = $sqlWhere . ($bd->busquedaTextoArray($auxiliar->to_iso88591($$nombreTextoBox), $camposBD));

    $sql    = "SELECT * FROM MENU WHERE $sqlWhere ORDER BY NOMBRE LIMIT 0,25";
    $result = $bd->ExecSQL($sql);

    $total = $bd->NumRegs($result);

    if ($total > 0):
        echo '<ul class="texton" style="width:400px;">';
        while ($row = $bd->SigReg($result)):
            $idMenu  = $row->ID_MENU;
            $txNombre    = $row->NOMBRE;
            $idPadre = $row->ID_PADRE;
            echo '<li class="texton">';
            if ($AlmacenarId == '1'):        //ficha.php
                echo '<a class="texton" href="#" onclick="return false;" alt="' . $idMenu . '" style="white-space:pre;">';
            elseif ($AlmacenarId == '0'): //index.php (listado)
                echo '<a class="texton" href="#" onclick="return false;" alt="' . $txNombre . '" rev="' . $idMenu . '" style="white-space:pre;">';
            endif;
            echo "$txNombre";
            echo '</a>';
            echo '</li>';
        endwhile;
        echo '</ul>';
    endif;

endif;
?>