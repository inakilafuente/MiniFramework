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

//$sqlWhere = "1=1";

$nombreTextoBox = "tx" . ($NombreCampo != "" ? $NombreCampo : 'MenuIzq');

if (trim( (string)$$nombreTextoBox) != ""):

    $camposBD = array(($administrador->ID_IDIOMA == 'ENG' ? 'D.ENG' : 'D.ESP'));
//    $camposBD = array('D.ENG','D.ESP');
    $sqlWhere = $sqlWhere . ($bd->busquedaTextoArray($auxiliar->to_iso88591($$nombreTextoBox), $camposBD));

    $sql    = "SELECT M.NOMBRE AS NOMBRE_HIJO, M.NIVEL_MENU,M.LINK, MP.NOMBRE AS NOMBRE_PADRE, MP.ID_PADRE, M.COLUMNA_PERFIL, M.ENTORNO_CODEIGNITER, M.ID_MENU FROM MENU M
            INNER JOIN MENU MP ON M.ID_PADRE = MP.ID_MENU
            INNER JOIN DICCIONARIO D ON M.NOMBRE = D.CLAVE
            WHERE (M.NIVEL_MENU = 1 OR M.NIVEL_MENU =3) AND M.LINK <> 'SUBMENU' AND M.MOSTRAR_EN_MENU = 1 $sqlWhere   ORDER BY MP.ID_MENU,MP.ORDEN LIMIT 0,25";
    $result = $bd->ExecSQL($sql);

    $total = $bd->NumRegs($result);

    if ($total > 0):
        echo '<ul class="texton" style="width:350px;">';
        while ($row = $bd->SigReg($result)):
            $nombreTexto = $auxiliar->traduce($row->NOMBRE_HIJO, $administrador->ID_IDIOMA);
            $nombrePadre = strtr( (string)strtoupper( (string)$auxiliar->traduce($row->NOMBRE_PADRE, $administrador->ID_IDIOMA)), "àèìòùáéíóúçñäëïöü", "ÀÈÌÒÙÁÉÍÓÚÇÑÄËÏÖÜ");
            $link        = $row->LINK;

            //SI ES DE TERCER NIVEL, MOSTRAMOS EL NOMBRE DEL SUBMENU
            if ($row->NIVEL_MENU == 3):
                $rowPrimverNivel = $bd->VerReg("MENU", "ID_MENU", $row->ID_PADRE, "No");
                $nombrePadre     = strtoupper( (string)$auxiliar->traduce($rowPrimverNivel->NOMBRE, $administrador->ID_IDIOMA)) . "/" . $nombrePadre;
            endif;
            if ($row->ENTORNO_CODEIGNITER == 1):
                //https://test.vicarli.com/acciona_sga_ci/es/
                $url = $url_web_adm . "tunel_codeigniter.php?idMenu=" . $row->ID_MENU;
            elseif ($row->ENTORNO_CODEIGNITER == 0):
                $url = $url_web_adm . $link;
            endif;
            if ($administrador->Hayar_Permiso_Perfil($row->COLUMNA_PERFIL) >= 1):
                echo '<li class="texton">';
                //echo "<div  width='100%' >";
                    echo '<a class="texton" href="' . $url . '"  alt="' . $url . '" style="white-space:pre;">';


                //ALINEAR Y TABULAR LOS RESULTADOS MOSTRADOS EN LA LISTA
                //echo '<div style="float:left;width:40%;" > &nbsp;'. $nombrePadre .'</div><div style="text-align:center;float:left;width:20%;"> &nbsp;'. ' - ' .'&nbsp;</div><div style="float:left;width:40%;">&nbsp;'. $nombreTexto .'</div>  ';
                echo "&nbsp;" . $nombrePadre . "/" . $nombreTexto;

                echo '</a>';
                // echo "</div>";
                echo '</li>';
            endif;
        endwhile;
        echo '</ul>';
    endif;
endif;
?>