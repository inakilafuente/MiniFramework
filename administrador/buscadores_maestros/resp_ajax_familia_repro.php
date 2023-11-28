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

$nombreTextoBox = "tx" . ($NombreCampo != "" ? $NombreCampo : 'FamiliaRepro');

if (trim( (string)$$nombreTextoBox) != ""):
    if ($administrador->ID_IDIOMA == "ESP"):
        $camposBD = array('FAMILIA_REPRO', 'REFERENCIA');
    else:
        $camposBD = array('FAMILIA_REPRO_INGLES', 'REFERENCIA');
    endif;
    $sqlWhere = $sqlWhere . ($bd->busquedaTextoArray($auxiliar->to_iso88591($$nombreTextoBox), $camposBD));

    $sql    = "SELECT ID_FAMILIA_REPRO,REFERENCIA,FAMILIA_REPRO,FAMILIA_REPRO_INGLES FROM FAMILIA_REPRO WHERE $sqlWhere AND BAJA=0 ORDER BY REFERENCIA LIMIT 0,25";
    $result = $bd->ExecSQL($sql);

    $total = $bd->NumRegs($result);

    if ($total > 0):
        echo '<ul class="texton" style="width:400px;">';
        while ($row = $bd->SigReg($result)):
            $identFamiliaRepro  = $row->ID_FAMILIA_REPRO;
            $refFamiliaRepro    = $row->REFERENCIA;
            $nombreFamiliaRepro = $administrador->ID_IDIOMA == "ESP" ? $row->FAMILIA_REPRO : $row->FAMILIA_REPRO_INGLES;
            echo '<li class="texton">';
            if ($AlmacenarId == '1'):            //ficha.php
                echo '<a class="texton" href="#" onclick="return false;" alt="' . $identFamiliaRepro . '" style="white-space:pre;">';
            elseif ($AlmacenarId == '0'): //index.php (listado)
                echo '<a class="texton" href="#" onclick="return false;" alt="' . $refFamiliaRepro . '" rev="' . $identFamiliaRepro . '" style="white-space:pre;">';
            endif;
            echo "$refFamiliaRepro - $nombreFamiliaRepro";
            echo '</a>';
            echo '</li>';
        endwhile;
        echo '</ul>';

    endif;
endif;
?>