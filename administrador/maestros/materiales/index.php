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


//icono listado observaciones form.png

session_start();
include $pathRaiz . "seguridad_admin.php";
function acortar($cadena){
    $valores=explode(" ",$cadena);
    if(intval($valores[0][0])>=0&&intval($valores[0][1])>0){
        return $valores[0][0].$valores[0][1];
    }else{
        return $valores[0][0].$valores[1][0];
    }
}
function obtenerHijosMateriales($id,$bd,&$vector){
    $sqlHijos = "SELECT M.ID_MATERIALES ,M.REFERENCIA_SCS , M.DESCRIPCION_ESP , M.DESCRIPCION_ENG ,M.ESTATUS_MATERIAL,M.TIPO_MATERIAL,fr.REFERENCIA, fr.FAMILIA_REPRO , fm.NOMBRE_FAMILIA ,MCA.CANTIDAD ,M.FK_UNIDAD_MEDIDA ,M.AGM ,MCA.BAJA,MCA.MATERIAL_AGM ,MCA.MATERIAL_COMPONENTE, UNIDAD.UNIDAD,UNIDAD.DESCRIPCION 
    FROM MATERIALES M JOIN MATERIAL_COMPONENTE_AGM MCA ON M.ID_MATERIALES=MCA.MATERIAL_AGM 
    JOIN FAMILIA_MATERIAL fm ON M.FK_FAMILIA_MATERIAL = fm.ID_FAMILIA_MATERIAL 
    JOIN FAMILIA_REPRO fr ON M.FK_FAMILIA_REPRO = fr.ID_FAMILIA_REPRO 
    JOIN UNIDAD ON M.FK_UNIDAD_MEDIDA=UNIDAD.ID_UNIDAD 
    WHERE M.ID_MATERIALES=".$id;

    $resHijos = $bd->ExecSQL($sqlHijos);
    while($reg=$bd->SigReg($resHijos)) {

        $reg->ID_PARENT=$id;
        $vector[] = $reg;
        obtenerHijosMateriales($reg->MATERIAL_COMPONENTE, $bd, $vector);
    }
}

function pintar_tabla_hijos($vector,$myColor){
    if(!empty($vector)){
        echo"<tr><td>";
        echo "<tr><td height='19' bgcolor='#2f4f4f'class='blanco'>Nivel</td>";
        echo "<td height='19' bgcolor='#2f4f4f'class='blanco'>Referencia</td>";
        echo "<td height='19' bgcolor='#2f4f4f'class='blanco' colspan='2'>Material</td>";
        echo "<td height='19' bgcolor='#2f4f4f'class='blanco'>EM</td>";
        echo "<td height='19' bgcolor='#2f4f4f'class='blanco' colspan='2'>Tipo Material</td>";
        echo "<td height='19' bgcolor='#2f4f4f'class='blanco' colspan='2'>Familia Material</td>";
        echo "<td height='19' bgcolor='#2f4f4f'class='blanco' colspan='2'>Familia Repro</td>";
        echo "<td height='19' bgcolor='#2f4f4f'class='blanco'>Cantidad</td>";
        echo "<td height='19' bgcolor='#2f4f4f'class='blanco'>UB</td>";
        echo "<td height='19' bgcolor='#2f4f4f'class='blanco'>AGM</td>";
        echo "<td height='19' bgcolor='#2f4f4f'class='blanco'>B</td></tr>";
        $nivel=0;
        $referencias=array();
        foreach ($vector as $material){
            echo "<tr>";
            if(!in_array($material->ID_MATERIALES,$referencias)){
                $referencias[]=$material->ID_MATERIALES;
                $nivel++;
            }
            switch ($nivel) {
                case 1:
                    $myColor = '#d3f3ff';
                    break;
                case 2:
                    $myColor = '#e2ffe4';
                    break;
                case 3:
                    $myColor = '#fbfbd2';
                    break;
                case 4:
                    $myColor = '#eed9c9';
                    break;
                case 5:
                    $myColor = '#e6cff0';
                    break;
                case 6:
                    $myColor = '#e3c0c0';
                    break;
            };

            echo "<td height='18' align='left'bgcolor='$myColor' class='enlaceceldas'>". $nivel."</td>";
            echo "<td height='18' align='left'bgcolor='$myColor' class='enlaceceldas'><a href='ficha.php?idMaterial=$material->ID_MATERIALES' class='enlaceceldasacceso'>$material->ID_MATERIALES</a></td>";
            //echo "<td>". $material->ID_MATERIALES."</td>";
            echo "<td height='18' align='left'bgcolor='$myColor' class='enlaceceldas' colspan='2'>". $material->DESCRIPCION_ESP."</td>";




            if ($material->ESTATUS_MATERIAL == '01-Bloqueo General'):
                $eb_bgcolor = "#F04343";
                $eb_color   = "#FFFFFF";
            elseif ($material->ESTATUS_MATERIAL == '02-Obsoleto Fin Existencias (Error)'):
                $eb_bgcolor = "#F04343";
                $eb_color   = "#FFFFFF";
            elseif ($material->ESTATUS_MATERIAL == '03-Código duplicado'):
                $eb_bgcolor = "#F04343";
                $eb_color   = "#FFFFFF";
            elseif ($material->ESTATUS_MATERIAL == '04-Código inutilizable'):
                $eb_bgcolor = "#F04343";
                $eb_color   = "#FFFFFF";
            elseif ($material->ESTATUS_MATERIAL == '05-Obsoleto Fin Existencias (Aviso)'):
                $eb_bgcolor = "#E9E238";
            elseif ($material->ESTATUS_MATERIAL == '06-Código Solo Fines Logísticos'):
                $eb_bgcolor = "#F04343";
                $eb_color   = "#FFFFFF";
            elseif ($material->ESTATUS_MATERIAL == '07-Solo para Refer. Prov'):
                ////////////////////////
            elseif ($material->ESTATUS_MATERIAL == 'No bloqueado'):
                $eb_bgcolor = "#B3C7DA";
            endif;






            echo "<td height='18' align='left' style='color: $eb_color' bgcolor='$eb_bgcolor' class='enlaceceldas'>". acortar($material->ESTATUS_MATERIAL)."</td>";
            echo "<td height='18' align='left'bgcolor='$myColor' class='enlaceceldas' colspan='2'>". $material->TIPO_MATERIAL."</td>";
            echo "<td height='18' align='left'bgcolor='$myColor' class='enlaceceldas' colspan='2'>". $material->NOMBRE_FAMILIA."</td>";
            echo "<td height='18' align='left'bgcolor='$myColor' class='enlaceceldas' colspan='2'>". $material->REFERENCIA."-".$material->FAMILIA_REPRO."</td>";
            echo "<td height='18' align='left'bgcolor='$myColor' class='enlaceceldas'>". $material->CANTIDAD."</td>";
            echo "<td height='18' align='left'bgcolor='$myColor' class='enlaceceldas'>". $material->UNIDAD." ".$material->DESCRIPCION."</td>";
            if($material->AGM=='1'){
                $valor_AGM='Si';
            }
            else{
                $valor_AGM='No';
            }
            echo "<td height='18' align='left'bgcolor='$myColor' class='enlaceceldas'>". $valor_AGM."</td>";
            echo "<td height='18' align='left'bgcolor='$myColor' class='enlaceceldas'>". $material->BAJA."</td>";
            echo "</tr>";
        }
        echo "</td></tr>";
}
}
if(isset($_POST['cboxAGM'])){
    $checkboxcheckedAGM=true;
}else{
    $checkboxcheckedAGM=false;
}
if(isset($_POST['cboxObservaciones'])){
    $checkboxchecked=true;
}else{
    $checkboxchecked=false;
}
$tituloPag         = $auxiliar->traduce("Tipos de Incidencias", $administrador->ID_IDIOMA);
$tituloNav         = $auxiliar->traduce("Maestros", $administrador->ID_IDIOMA) . " >> " . $auxiliar->traduce("Tipos de Incidencias", $administrador->ID_IDIOMA);
$ZonaTablaPadre    = "Maestros";
$ZonaTabla         = "MaestrosMateriales";
$ZonaSubTablaPadre = "MaestrosSubmenuMateriales";
$PaginaRecordar    = "ListadoMaestrosMateriales";

// COMPRUEBA SI TIENE PERMISOS
if ($administrador->Hayar_Permiso_Perfil('ADM_MAESTROS_MATERIALES') < 1):
    $html->PagError("SinPermisos");
endif;

// RECUERDO DE BUSQUEDAS REALIZADAS
include $pathRaiz . "busqueda_recordar.php";

// CONTROLO EL CAMBIO DEL LIMITE
if (!Empty($CambiarLimite)):
    $navegar->maxfilasMaestroMaterial = $selLimite;
endif;

// ORDENACION DE COLUMNAS
$columnas_ord["id_incidencia_sistema_tipo"]    = "ID_INCIDENCIA_SISTEMA_TIPO";
$columnas_ord["incidencia_sistema_tipo"]    = "INCIDENCIA_SISTEMA_TIPO";
$columnas_ord["incidencia_sistema_tipo_eng"]  = "INCIDENCIA_SISTEMA_TIPO_ENG";
$columnas_ord["baja"]   = "BAJA";

$columna_defecto = "id_incidencia_sistema_tipo";
$sentido_defecto = "0"; //ASCENDENTE
//$navegar->DefinirColumnasOrdenacion($columnas_ord, $columna_defecto, $sentido_defecto);

//PARA ACOTAR LAS BUSQUEDAS (QUE NO ESTEN BORRADOS)
$sqlTipos = "WHERE 1=1";

//Nº Material
if (trim( (string)$txMaterial) != ""):
    $camposBD   = array('REFERENCIA_SCS');
    $sqlTipos  = $sqlTipos . ($bd->busquedaTextoArray($txMaterial, $camposBD));
    $textoLista = $textoLista . "&" . $auxiliar->traduce("ID Material", $administrador->ID_IDIOMA) . ": " . $txMaterial;
endif;

//DESC MATERIAL ESP
if($administrador->ID_IDIOMA=='ESP'):
    if (trim( (string)$txDesc) != ""):
    $camposBD   = array('MATERIALES.DESCRIPCION_ESP');
    $sqlTipos  = $sqlTipos . ($bd->busquedaTextoArray($txDesc, $camposBD));
    $textoLista = $textoLista . "&" . $auxiliar->traduce("Desc. Mat", $administrador->ID_IDIOMA) . ": " . $txDesc;
    endif;
endif;
//DESC MATERIAL ENG
if($administrador->ID_IDIOMA=='ENG'):
    if (trim( (string)$txDesc) != ""):
    $camposBD   = array('MATERIALES.DESCRIPCION_ENG');
    $sqlTipos  = $sqlTipos . ($bd->busquedaTextoArray($txDesc, $camposBD));
    $textoLista = $textoLista . "&" . $auxiliar->traduce("Desc. Mat.", $administrador->ID_IDIOMA) . ": " . $txDesc;
    endif;
endif;


//FAMILIA REPRO
/*
if (trim( (string)$txFamiliaRepro) != ""):
    $camposBD   = array('FAMILIA_REPRO');
    $sqlTipos  = $sqlTipos . ($bd->busquedaTextoArray($txFamiliaRepro, $camposBD));
    $textoLista = $textoLista . "&" . $auxiliar->traduce("Familia Repro", $administrador->ID_IDIOMA) . ": " . $txFamiliaRepro;
endif;
*/




if ($idFamiliaRepro != "" || trim( (string)$txFamiliaRepro) != ""):
    if ($idFamiliaRepro != ""):
        $sqlTipos = $sqlTipos . ($bd->busquedaNumero($idFamiliaRepro, 'FAMILIA_REPRO.ID_FAMILIA_REPRO'));
    else:
        $camposBD     = array('FAMILIA_REPRO.FAMILIA_REPRO');
        $sqlTipos = $sqlTipos . ($bd->busquedaTextoArray($txFamiliaRepro, $camposBD));
    endif;
    $textoLista = $textoLista . "&" . $auxiliar->traduce("Familia Repro", $administrador->ID_IDIOMA) . ": " . $txFamiliaRepro;
endif;





//FAMILIA MATERIAL
/*
if (trim( (string)$txFamiliaMaterial) != ""):
    $camposBD   = array('NOMBRE_FAMILIA');
    $sqlTipos  = $sqlTipos . ($bd->busquedaTextoArray($txFamiliaMaterial, $camposBD));
    $textoLista = $textoLista . "&" . $auxiliar->traduce("Familia Material", $administrador->ID_IDIOMA) . ": " . $txFamiliaMaterial;
endif
*/


if ($idFamiliaMaterial != "" || trim( (string)$txFamiliaMaterial) != ""):
    if ($idFamiliaMaterial != ""):
        $sqlTipos = $sqlTipos . ($bd->busquedaNumero($idFamiliaMaterial, 'ID_FAMILIA_MATERIAL'));
    else:
        $camposBD     = array('NOMBRE_FAMILIA');
        $sqlTipos = $sqlTipos . ($bd->busquedaTextoArray($txFamiliaMaterial, $camposBD));
    endif;
    $textoLista = $textoLista . "&" . $auxiliar->traduce("Familia Material", $administrador->ID_IDIOMA) . ": " . $txFamiliaMaterial;
endif;






//MARCA
if (trim( (string)$txMarca) != ""):
    $camposBD   = array('MARCA');
    $sqlTipos  = $sqlTipos . ($bd->busquedaTextoArray($txMarca, $camposBD));
    $textoLista = $textoLista . "&" . $auxiliar->traduce("Marca", $administrador->ID_IDIOMA) . ": " . $txMarca;
endif;
//MODELO
if (trim( (string)$txModelo) != ""):
    $camposBD   = array('MODELO');
    $sqlTipos  = $sqlTipos . ($bd->busquedaTextoArray($txModelo, $camposBD));
    $textoLista = $textoLista . "&" . $auxiliar->traduce("Modelo", $administrador->ID_IDIOMA) . ": " . $txModelo;
endif;

//OBSERVACIONES
if (trim( (string)$txObservaciones) != ""):
    $camposBD   = array('OBSERVACIONES');
    $sqlTipos  = $sqlTipos . ($bd->busquedaTextoArray($txObservaciones, $camposBD));
    $textoLista = $textoLista . "&" . $auxiliar->traduce("Observaciones", $administrador->ID_IDIOMA) . ": " . $txObservaciones;
endif;
//UNIDAD BASE
/*
if (trim( (string)$txUnidadBase) != ""):
    $camposBD   = array('UNIDAD');
    $sqlTipos  = $sqlTipos . ($bd->busquedaTextoArray($txUnidadBase, $camposBD));
    $textoLista = $textoLista . "&" . $auxiliar->traduce("Familia Material", $administrador->ID_IDIOMA) . ": " . $txUnidadBase;
endif;
*/

if ($idUnidadBase != "" || trim( (string)$txUnidadBase) != ""):
    if ($idUnidadBase != ""):
        $sqlTipos = $sqlTipos . ($bd->busquedaNumero($idUnidadBase, 'ID_UNIDAD'));
    else:
        $camposBD     = array('UNIDAD');
        $sqlTipos = $sqlTipos . ($bd->busquedaTextoArray($txUnidadBase, $camposBD));
    endif;
    $textoLista = $textoLista . "&" . $auxiliar->traduce("Unidad Base", $administrador->ID_IDIOMA) . ": " . $txUnidadBase;
endif;



//UNIDAD COMPRA
/*
if (trim( (string)$txUnidadCompra) != ""):
    $camposBD   = array('UNIDAD');
    $sqlTipos  = $sqlTipos . ($bd->busquedaTextoArray($txUnidadCompra, $camposBD));
    $textoLista = $textoLista . "&" . $auxiliar->traduce("Familia Material", $administrador->ID_IDIOMA) . ": " . $txUnidadCompra;
*/




if ($idUnidadCompra != "" || trim( (string)$txUnidadCompra) != ""):
    if ($idUnidadCompra != ""):
        $sqlTipos = $sqlTipos . ($bd->busquedaNumero($idUnidadCompra, 'ID_UNIDAD'));
    else:
        $camposBD     = array('UNIDAD');
        $sqlTipos = $sqlTipos . ($bd->busquedaTextoArray($txUnidadCompra, $camposBD));
    endif;
    $textoLista = $textoLista . "&" . $auxiliar->traduce("Unidad Compra", $administrador->ID_IDIOMA) . ": " . $txUnidadCompra;
endif;

//ESTATUS MATERIAL
if(!isset($selEstatus)):
    $selEstatus = 'Todos';
endif;
if($selEstatus == '01-Bloqueo General'):
    $sqlTipos .= " AND (MATERIALES.ESTATUS_MATERIAL='01-Bloqueo General')";
    $textoLista = $textoLista."&".$auxiliar->traduce("EM",$administrador->ID_IDIOMA).": ".$auxiliar->traduce($selEstatus,$administrador->ID_IDIOMA);
elseif($selEstatus == '02-Obsoleto Fin Existencias (Error)'):
    $sqlTipos .= " AND (MATERIALES.ESTATUS_MATERIAL='2-Obsoleto Fin Existencias (Error)')";
    $textoLista = $textoLista."&".$auxiliar->traduce("EM",$administrador->ID_IDIOMA).": ".$auxiliar->traduce($selEstatus,$administrador->ID_IDIOMA);
elseif($selEstatus == '03-Código Duplicado'):
    $sqlTipos .= " AND (MATERIALES.ESTATUS_MATERIAL='03-Código Duplicado')";
    $textoLista = $textoLista."&".$auxiliar->traduce("EM",$administrador->ID_IDIOMA).": ".$auxiliar->traduce($selEstatus,$administrador->ID_IDIOMA);
elseif($selEstatus == '04-Código inutilizable'):
    $sqlTipos .= " AND (MATERIALES.ESTATUS_MATERIAL='04-Código inutilizable')";
    $textoLista = $textoLista."&".$auxiliar->traduce("EM",$administrador->ID_IDIOMA).": ".$auxiliar->traduce($selEstatus,$administrador->ID_IDIOMA);
elseif($selEstatus == '05-Obsoleto Fin Existencias (Aviso)'):
    $sqlTipos .= " AND (MATERIALES.ESTATUS_MATERIAL='05-Obsoleto Fin Existencias (Aviso)')";
    $textoLista = $textoLista."&".$auxiliar->traduce("EM",$administrador->ID_IDIOMA).": ".$auxiliar->traduce($selEstatus,$administrador->ID_IDIOMA);
elseif($selEstatus == '06-Código Solo Fines Logísticos'):
    $sqlTipos .= " AND (MATERIALES.ESTATUS_MATERIAL='06-Código Solo Fines Logísticos')";
    $textoLista = $textoLista."&".$auxiliar->traduce("EM",$administrador->ID_IDIOMA).": ".$auxiliar->traduce($selEstatus,$administrador->ID_IDIOMA);
elseif($selEstatus == 'No bloqueado'):
    $sqlTipos .= " AND (MATERIALES.ESTATUS_MATERIAL='No bloqueado')";
    $textoLista = $textoLista."&".$auxiliar->traduce("EM",$administrador->ID_IDIOMA).": ".$auxiliar->traduce($selEstatus,$administrador->ID_IDIOMA);
endif;

//RA
if(!isset($selRA)):
    $selRA = 'Todos';
endif;
if($selRA == 'Si'):
    $sqlTipos .= " AND (MATERIALES.REFERENCIA_AUTOMATICA='1')";
    $textoLista = $textoLista."&".$auxiliar->traduce("RA",$administrador->ID_IDIOMA).": ".$auxiliar->traduce($selRA,$administrador->ID_IDIOMA);
elseif($selRA == 'No'):
    $sqlTipos .= " AND (MATERIALES.REFERENCIA_AUTOMATICA='0')";
    $textoLista = $textoLista."&".$auxiliar->traduce("RA",$administrador->ID_IDIOMA).": ".$auxiliar->traduce($selRA,$administrador->ID_IDIOMA);
endif;


//BAJA
if(!isset($selBaja)):
    $selBaja = 'Todos';
endif;
if($selBaja == 'Si'):
    $sqlTipos .= " AND (MATERIALES.BAJA='1')";
    $textoLista = $textoLista."&".$auxiliar->traduce("Baja",$administrador->ID_IDIOMA).": ".$auxiliar->traduce($selBaja,$administrador->ID_IDIOMA);
elseif($selBaja == 'No'):
    $sqlTipos .= " AND (MATERIALES.BAJA='0')";
    $textoLista = $textoLista."&".$auxiliar->traduce("Baja",$administrador->ID_IDIOMA).": ".$auxiliar->traduce($selBaja,$administrador->ID_IDIOMA);
endif;

//MATERIAL AGM
if(!isset($selMaterialAGM)):
    $selMaterialAGM = 'Todos';
endif;
if($selMaterialAGM == 'Si'):
    $sqlTipos .= " AND (MATERIALES.AGM='1')";
    $textoLista = $textoLista."&".$auxiliar->traduce("AGM",$administrador->ID_IDIOMA).": ".$auxiliar->traduce($selBaja,$administrador->ID_IDIOMA);
elseif($selMaterialAGM == 'No'):
    $sqlTipos .= " AND (MATERIALES.AGM='0')";
    $textoLista = $textoLista."&".$auxiliar->traduce("AGM",$administrador->ID_IDIOMA).": ".$auxiliar->traduce($selBaja,$administrador->ID_IDIOMA);
endif;




//COMPONENTE AGM
if ($txComponenteAGM!= ""):
    var_dump($txComponenteAGM);
    if(is_numeric($txComponenteAGM)){
        $camposBD   = array('REFERENCIA_SCS');
        $sqlTipos  = $sqlTipos . ($bd->busquedaTextoArray($txComponenteAGM, $camposBD));

        $textoLista = $textoLista . "&" . $auxiliar->traduce("Componente AGM", $administrador->ID_IDIOMA) . ": " . $txComponenteAGM;
    }elseif($administrador->ID_IDIOMA=='ESP') {
            $camposBD = 'MATERIALES.DESCRIPCION_ESP';
            $sqlTipos = $sqlTipos . ($bd->busquedaTextoExacta($txComponenteAGM, $camposBD));
            $textoLista = $textoLista . "&" . $auxiliar->traduce("Componente AGM ESP", $administrador->ID_IDIOMA) . ": " . $txComponenteAGM;
    }elseif($administrador->ID_IDIOMA == 'ENG') {
            $camposBD = 'MATERIALES.DESCRIPCION_ENG';
            $sqlTipos = $sqlTipos . ($bd->busquedaTextoExacta($txComponenteAGM, $camposBD));
            $textoLista = $textoLista . "&" . $auxiliar->traduce("Componente AGM ENG", $administrador->ID_IDIOMA) . ": " . $txComponenteAGM;
        }


endif;

//DESC ESP COMPONENTE AGM

//DESC ENG COMPONENTE AGM





// TEXTO LISTADO
if ($textoLista == ""):
    $textoLista = $auxiliar->traduce("Todas las incidencias", $administrador->ID_IDIOMA);
else:
    if (substr( (string) $textoLista, 0, 1) == "&") $textoLista = substr( (string) $textoLista, 1);
    $textoSustituir = "</font><font color='#EA62A2'> &gt;&gt; </font><font>";
    $textoLista     = preg_replace("/&/", $textoSustituir, $textoLista);
endif;

$error = "NO";
if ($limite == ""):
    $mySql                          = "SELECT ID_MATERIALES FROM MATERIALES 
    JOIN FAMILIA_MATERIAL ON MATERIALES.FK_FAMILIA_MATERIAL=FAMILIA_MATERIAL.ID_FAMILIA_MATERIAL
    JOIN FAMILIA_REPRO ON MATERIALES.FK_FAMILIA_REPRO=FAMILIA_REPRO.ID_FAMILIA_REPRO
    JOIN UNIDAD ON UNIDAD.ID_UNIDAD=MATERIALES.FK_UNIDAD_COMPRA ".$sqlTipos
    ;
    $navegar->sqlAdminMaestroMaterial = $mySql;
endif;

// REALIZO LA SENTENCIA SQL
$navegar->Sql($navegar->sqlAdminMaestroMaterial, $navegar->maxfilasMaestroMaterial, $navegar->numerofilasMaestroMaterial);

// NUMERO DE REGISTROS
$numRegistros = $navegar->numerofilasMaestroMaterial;

// EXPORTAR A EXCEL
if ($exportar_excel == "1"):
    $sql = $navegar->copiaExport;
    include("exportar_excel.php");
    exit;
endif;
/*
$vector=array();
obtenerHijosMateriales(2,$bd,$vector);
var_dump($vector);
*/
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <? require_once $pathClases . "lib/gral_js.php"; ?>
    <script type="text/javascript">
        jQuery(document).ready(function () {
            jQuery("a.copyrightbotonesfancyboxImportacion").fancybox({
                'type': 'iframe',
                'width': '100%',
                'height': '100%',
                'hideOnOverlayClick': false
            });
            jQuery("a.fancyboxFamiliaMaterial").fancybox({
                'type': 'iframe',
                'width': '100%',
                'height': '100%',
                'hideOnOverlayClick': false
            });
            jQuery("a.fancyboxFamiliaRepro").fancybox({
                'type': 'iframe',
                'width': '100%',
                'height': '100%',
                'hideOnOverlayClick': false
            });
            jQuery("a.fancyboxUnidad").fancybox({
                'type': 'iframe',
                'width': '100%',
                'height': '100%',
                'hideOnOverlayClick': false
            });
            jQuery("a.fancyboxEtiqueta").fancybox({
                'type': 'iframe',
                'width': '100%',
                'height': '100%',
                'hideOnOverlayClick': false
            });
        });

        function exportarExcel() {
            document.FormSelect.exportar_excel.value = '1';
            document.FormSelect.submit();
            document.FormSelect.exportar_excel.value = '0';
            return false;
        }
    </script>
</head>
<body bgcolor="#FFFFFF" background="<? echo $pathRaiz ?>imagenes/fondo_pantalla.gif" leftmargin="0" topmargin="0"
      marginwidth="0" marginheight="0" onLoad="document.FormSelect.txIncidenciaSistemaTipo.focus()">
<FORM NAME="FormSelect" ACTION="index.php" METHOD="POST">
    <INPUT TYPE="HIDDEN" NAME="nombre_fichero" VALUE="<?= $tituloPag ?>.xls">
    <INPUT TYPE="HIDDEN" NAME="nombre_hoja" VALUE="Hoja1">
    <INPUT TYPE="HIDDEN" NAME="exportar_excel" VALUE="0">
    <? $navegar->GenerarCamposOcultosForm(); ?>
    <table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
        <tr>
            <td height="10" align="center" valign="top">
                <? include $pathRaiz . "tabla_superior.php"; ?>
            </td>
        </tr>
        <tr>
            <td align="center" valign="top">
                <table width="100%" border="0" cellpadding="0" cellspacing="0">
                    <tr>
                        <td height="3" colspan="2" align="center" valign="top" bgcolor="#7A0A0A" class="linearriba"><img
                                    src="<? echo $pathRaiz ?>imagenes/transparente.gif" width="10" height="3"></td>
                    </tr>
                    <tr>
                        <? include $pathRaiz . "tabla_izqda.php"; ?>
                        <td align="left" valign="top" bgcolor="#FFFFFF"
                            background="<? echo $pathRaiz ?>imagenes/fondo_pantalla.gif">
                            <table width="100%" border="0" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td height="23">
                                        <table width="100%" height="23" border="0" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td width="25" class="linearriba"><img
                                                            src="<? echo $pathRaiz ?>imagenes/flechitas_01.gif"
                                                            width="35" height="23"></td>
                                                <td width="469" align="left" valign="middle" bgcolor="#B3C7DA"
                                                    class="linearriba">
                                                    <table width="440" border="0" cellspacing="0" cellpadding="0">
                                                        <tr>
                                                            <td align="left" class="alertas"><? echo $tituloPag ?></td>
                                                        </tr>
                                                    </table>
                                                </td>
                                                <td width="25"><img src="<? echo $pathRaiz ?>imagenes/esquina.gif"
                                                                    width="25" height="24"></td>
                                                <td bgcolor="#7A0A0A">
                                                    <table width="235" height="23" border="0" cellpadding="0"
                                                           cellspacing="0">
                                                        <tr>
                                                            <td width="20">&nbsp;</td>
                                                            <td align="left" class="existalert">
                                                                <? include "$pathRaiz" . "control_alertas.php" ?>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td height="20" align="left" valign="top">
                                        <table width="100%" height="20" border="0" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td width="440" bgcolor="#D9E3EC">
                                                    <table width="440" border="0" cellspacing="0" cellpadding="0">
                                                        <tr>
                                                            <td width="35" bgcolor="#982a29" class="lineabajoarriba">
                                                                &nbsp;
                                                            </td>
                                                            <td width="224" bgcolor="#982a29" class="lineabajoarriba"
                                                                colspan="2">
                                                                <font class="tituloNav"><? echo $tituloNav ?>
                                                                </font></td>
                                                            <td valign=top width="20" bgcolor="#B3C7DA"
                                                                class="lineabajoarriba"><img
                                                                        src="<? echo $pathRaiz ?>imagenes/esquina_02.gif"
                                                                        width="20" height="20"></td>
                                                        </tr>
                                                    </table>
                                                </td>
                                                <td bgcolor="#B3C7DA" class="lineabajoarriba">&nbsp;</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr bgcolor="#D9E3EC">
                                    <td height="13" align="center" valign="top" bgcolor="#AACFF9" class="lineabajo">
                                        <table width="100%" height="13" border="0" align="center" cellpadding="0"
                                               cellspacing="0">
                                            <tr>
                                                <td width="20" align="center" valign="bottom" class="lineaderecha">
                                                    &nbsp;
                                                </td>
                                                <td align="center" valign="middle">
                                                    <table width="97%" height="11" border="0" align="center"
                                                           cellpadding="0" cellspacing="0" style="margin-top:5px;">
                                                        <tr>
                                                            <td height="1" colspan="3" bgcolor="#D9E3EC"
                                                                class="linearribadereizq"><img
                                                                        src="<? echo $pathRaiz ?>imagenes/transparente.gif"
                                                                        width="10" height="5"></td>
                                                        </tr>
                                                        <tr bgcolor="#D9E3EC">
                                                            <td width="10" bgcolor="#D9E3EC" class="lineaizquierda">
                                                                &nbsp;
                                                            </td>
                                                            <td width="100%" align="left" bgcolor="#D9E3EC">
                                                                <table width="97%" border="0" cellpadding="0"
                                                                       cellspacing="0" class="tablaFiltros">
                                                                    <tr>
                                                                        <td width="24%" height="20" align="left"
                                                                            valign="middle"
                                                                            class="textoazul"><?= $auxiliar->traduce("Material", $administrador->ID_IDIOMA) . ":" ?>
                                                                        </td>
                                                                        <td width="24%" align="left" valign="middle">
                                                                            <?
                                                                            $TamanoText = "200px";
                                                                            $ClassText  = "copyright";
                                                                            $MaxLength  = "50";
                                                                            $html->TextBox("txMaterial", $txMaterial);
                                                                            ?>
                                                                        </td>
                                                                        <td width="24%" height="20" align="left"
                                                                            valign="middle"
                                                                            class="textoazul"><?= $auxiliar->traduce("Estatus Material", $administrador->ID_IDIOMA) . ":" ?>
                                                                        </td>
                                                                        <td width="24%" align="left" valign="middle">
                                                                            <?
                                                                            $NombreSelect = 'selEstatus';
                                                                            $SeleccionMultiple='Si';
                                                                            $Elementos_estatus[0]['text'] = $auxiliar->traduce("01-Bloqueo General", $administrador->ID_IDIOMA);
                                                                            $Elementos_estatus[0]['valor'] = '01-Bloqueo General';
                                                                            $Elementos_estatus[1]['text'] = $auxiliar->traduce("02-Obsoleto Fin Existencias (Error)", $administrador->ID_IDIOMA);
                                                                            $Elementos_estatus[1]['valor'] = '02-Obsoleto Fin Existencias (Error)';
                                                                            $Elementos_estatus[2]['text'] = $auxiliar->traduce("03-Código duplicado", $administrador->ID_IDIOMA);
                                                                            $Elementos_estatus[2]['valor'] = '03-Código duplicado';
                                                                            $Elementos_estatus[3]['text'] = $auxiliar->traduce("04-Código inutilizable", $administrador->ID_IDIOMA);
                                                                            $Elementos_estatus[3]['valor'] = '04-Código inutilizable';
                                                                            $Elementos_estatus[4]['text'] = $auxiliar->traduce("05-Obsoleto Fin Existencias (Aviso)", $administrador->ID_IDIOMA);
                                                                            $Elementos_estatus[4]['valor'] = '05-Obsoleto Fin Existencias (Aviso)';
                                                                            $Elementos_estatus[5]['text'] = $auxiliar->traduce("06-Código Solo Fines Logísticos", $administrador->ID_IDIOMA);
                                                                            $Elementos_estatus[5]['valor'] = '06-Código Solo Fines Logístico';
                                                                            $Elementos_estatus[6]['text'] = $auxiliar->traduce("07-Solo para Refer.Prov", $administrador->ID_IDIOMA);
                                                                            $Elementos_estatus[6]['valor'] = '07-Solo para Refer.Prov';
                                                                            $Elementos_estatus[7]['text'] = $auxiliar->traduce("No bloqueado", $administrador->ID_IDIOMA);
                                                                            $Elementos_estatus[7]['valor'] = 'No bloqueado';
                                                                            $Elementos_estatus[8]['text'] = $auxiliar->traduce("Todos", $administrador->ID_IDIOMA);
                                                                            $Elementos_estatus[8]['valor'] = 'Todos';
                                                                            $Tamano = "205px";
                                                                            $Estilo = "copyright";

                                                                            $html->SelectArr($NombreSelect, $Elementos_estatus,'Todos');
                                                                            unset( $SeleccionMultiple);
                                                                            ?>
                                                                        </td>
                                                                        <td width="4%" align="center" valign="top">
                                                                            &nbsp;
                                                                        </td>

                                                                    </tr>
                                                                    <tr>
                                                                        <td width="24%" height="20" align="left"
                                                                            valign="middle"
                                                                            class="textoazul"><?= $auxiliar->traduce("RA", $administrador->ID_IDIOMA) . ":" ?>
                                                                        </td>
                                                                        <td width="24%" align="left" valign="middle">
                                                                            <?
                                                                            $NombreSelect = 'selRA';
                                                                            $Elementos_RA[0]['text'] = $auxiliar->traduce("Si", $administrador->ID_IDIOMA);
                                                                            $Elementos_RA[0]['valor'] = 'Si';
                                                                            $Elementos_RA[1]['text'] = $auxiliar->traduce("No", $administrador->ID_IDIOMA);
                                                                            $Elementos_RA[1]['valor'] = 'No';
                                                                            $Elementos_RA[2]['text'] = $auxiliar->traduce("Todos", $administrador->ID_IDIOMA);
                                                                            $Elementos_RA[2]['valor'] = 'Todos';
                                                                            $Tamano = "205px";
                                                                            $Estilo = "copyright";

                                                                            $html->SelectArr($NombreSelect, $Elementos_RA, 'Todos');
                                                                            ?>
                                                                        </td>
                                                                        <td width="24%" height="20" align="left"
                                                                            valign="middle"
                                                                            class="textoazul"><?= $auxiliar->traduce("Familia Material", $administrador->ID_IDIOMA) . ":" ?>

                                                                        </td>
                                                                        <td width="24%" align="left" valign="middle">
                                                                            <?
                                                                            $TamanoText = "200px";
                                                                            $ClassText  = "copyright";
                                                                            $MaxLength  = "50";
                                                                            $jscript    = "onchange=\"document.FormSelect.idFamiliaMaterial.value=''\"";
                                                                            $idTextBox  = 'txFamiliaMaterial';
                                                                            $html->TextBox("txFamiliaMaterial", $txFamiliaMaterial);
                                                                            unset($jscript);
                                                                            unset($idTextBox);

                                                                            ?>
                                                                            <input type="hidden"
                                                                                   name="idFamiliaMaterial"
                                                                                   id="idFamiliaMaterial"
                                                                                   value="<?= $txFamiliaMaterial ?>"/>
                                                                            <a href="<?= $pathRaiz ?>buscadores_maestros/busqueda_familia_material.php?AlmacenarId=0"
                                                                               class="fancyboxFamiliaMaterial"
                                                                               id="categoriasUbicacion"> <img
                                                                                        src="<?= $pathRaiz ?>imagenes/botones/listado.png"
                                                                                        alt="<?= $auxiliar->traduce("Buscar Familia Material", $administrador->ID_IDIOMA) ?>"
                                                                                        name="Listado"
                                                                                        border="0" align="absbottom"
                                                                                        id="Listado"/> </a>
                                                                            <span id="desplegable_familia_material"
                                                                                  style="display: none;">
                                                                                <img
                                                                                        src="<?= $pathClases; ?>lib/ajax_script/img/esperando.gif"
                                                                                        width="15"
                                                                                        height="11"
                                                                                        alt="<?= $auxiliar->traduce("Buscando...", $administrador->ID_IDIOMA) ?>"/>
                                                                            </span>

                                                                            <div class="entry" align="left"
                                                                                 id="actualizador_familia_material"></div>
                                                                            <script type="text/javascript"
                                                                                    language="JavaScript">
                                                                                new Ajax.Autocompleter('txFamiliaMaterial', 'actualizador_familia_material', '<?=$pathRaiz?>buscadores_maestros/resp_ajax_familia_material.php?AlmacenarId=0',
                                                                                    {
                                                                                        method: 'post',
                                                                                        indicator: 'desplegable_familia_material',
                                                                                        minChars: '1',
                                                                                        afterUpdateElement: function (textbox, valor) {
                                                                                            siguiente_control(jQuery('#' + this.paramName));
                                                                                            jQuery(textbox).val(jQuery(valor).children('a').attr('alt'));//VALOR DEL PAR&Aacute;METRO ALT DEL ENLACE <a>
                                                                                            jQuery('#idFamiliaMaterial').val(jQuery(valor).children('a').attr('rev'));
                                                                                        }
                                                                                    }
                                                                                );
                                                                            </script>

                                                                        </td>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td width="24%" height="20" align="left"
                                                                            valign="middle"
                                                                            class="textoazul"><?= $auxiliar->traduce("Desc. Material", $administrador->ID_IDIOMA) . ":" ?>
                                                                        </td>
                                                                        <td width="24%" align="left" valign="middle">
                                                                            <?
                                                                            $TamanoText = "200px";
                                                                            $ClassText  = "copyright";
                                                                            $MaxLength  = "50";
                                                                            $html->TextBox("txDesc", $txDesc);
                                                                            ?>
                                                                        </td>
                                                                        <td width="24%" height="20" align="left"
                                                                            valign="middle"
                                                                            class="textoazul"><?= $auxiliar->traduce("Familia Repro", $administrador->ID_IDIOMA) . ":" ?>
                                                                        </td>
                                                                        <td width="24%" align="left" valign="middle">
                                                                            <?
                                                                            $TamanoText = "200px";
                                                                            $ClassText  = "copyright";
                                                                            $MaxLength  = "50";
                                                                            $jscript    = "onchange=\"document.FormSelect.idFamiliaRepro.value=''\"";
                                                                            $idTextBox  = 'txFamiliaRepro';
                                                                            $html->TextBox("txFamiliaRepro", $txFamiliaRepro);
                                                                            unset($jscript);
                                                                            unset($idTextBox);

                                                                            ?>
                                                                            <input type="hidden"
                                                                                   name="idFamiliaRepro"
                                                                                   id="idFamiliaRepro"
                                                                                   value="<?= $idFamiliaRepro ?>"/>
                                                                            <a href="<?= $pathRaiz ?>buscadores_maestros/busqueda_familia_repro.php?AlmacenarId=0"
                                                                               class="fancyboxFamiliaRepro"
                                                                               id="categoriasUbicacion"> <img
                                                                                        src="<?= $pathRaiz ?>imagenes/botones/listado.png"
                                                                                        alt="<?= $auxiliar->traduce("Buscar Familia Repro", $administrador->ID_IDIOMA) ?>"
                                                                                        name="Listado"
                                                                                        border="0" align="absbottom"
                                                                                        id="Listado"/> </a>
                                                                            <span id="desplegable_familia_repro"
                                                                                  style="display: none;">
                                                                                <img
                                                                                        src="<?= $pathClases; ?>lib/ajax_script/img/esperando.gif"
                                                                                        width="15"
                                                                                        height="11"
                                                                                        alt="<?= $auxiliar->traduce("Buscando...", $administrador->ID_IDIOMA) ?>"/>
                                                                            </span>

                                                                            <div class="entry" align="left"
                                                                                 id="actualizador_familia_repro"></div>
                                                                            <script type="text/javascript"
                                                                                    language="JavaScript">
                                                                                new Ajax.Autocompleter('txFamiliaRepro', 'actualizador_familia_repro', '<?=$pathRaiz?>buscadores_maestros/resp_ajax_familia_repro.php?AlmacenarId=0',
                                                                                    {
                                                                                        method: 'post',
                                                                                        indicator: 'desplegable_familia_repro',
                                                                                        minChars: '1',
                                                                                        afterUpdateElement: function (textbox, valor) {
                                                                                            siguiente_control(jQuery('#' + this.paramName));
                                                                                            jQuery(textbox).val(jQuery(valor).children('a').attr('alt'));//VALOR DEL PAR&Aacute;METRO ALT DEL ENLACE <a>
                                                                                            jQuery('#idFamiliaRepro').val(jQuery(valor).children('a').attr('rev'));
                                                                                        }
                                                                                    }
                                                                                );
                                                                            </script>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td width="24%" height="20" align="left"
                                                                            valign="middle"
                                                                            class="textoazul"><?= $auxiliar->traduce("Marca", $administrador->ID_IDIOMA) . ":" ?>
                                                                        </td>
                                                                        <td width="24%" align="left" valign="middle">
                                                                            <?
                                                                            $TamanoText = "200px";
                                                                            $ClassText  = "copyright";
                                                                            $MaxLength  = "50";
                                                                            $html->TextBox("txMarca", $txMarca);
                                                                            ?>
                                                                        </td>
                                                                        <td width="24%" height="20" align="left"
                                                                            valign="middle"
                                                                            class="textoazul"><?= $auxiliar->traduce("Con unidad de manipulación", $administrador->ID_IDIOMA) . ":" ?>
                                                                        </td>
                                                                        <td width="24%" align="left" valign="middle">
                                                                            <?
                                                                            $NombreSelect = 'selManipulacion';
                                                                            $Elementos_manipulacion[0]['text'] = $auxiliar->traduce("Si", $administrador->ID_IDIOMA);
                                                                            $Elementos_manipulacion[0]['valor'] = 'Si';
                                                                            $Elementos_manipulacion[1]['text'] = $auxiliar->traduce("No", $administrador->ID_IDIOMA);
                                                                            $Elementos_manipulacion[1]['valor'] = 'No';
                                                                            $Elementos_manipulacion[2]['text'] = $auxiliar->traduce("Pendiente decisión", $administrador->ID_IDIOMA);
                                                                            $Elementos_manipulacion[2]['valor'] = 'Pendiente decisión';
                                                                            $Elementos_manipulacion[3]['text'] = $auxiliar->traduce("No Aplica", $administrador->ID_IDIOMA);
                                                                            $Elementos_manipulacion[3]['valor'] = 'No Aplica';
                                                                            $Tamano = "205px";
                                                                            $Estilo = "copyright";

                                                                            $html->SelectArr($NombreSelect, $Elementos_manipulacion, 'No Aplica');
                                                                            ?>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td width="24%" height="20" align="left"
                                                                            valign="middle"
                                                                            class="textoazul"><?= $auxiliar->traduce("Modelo", $administrador->ID_IDIOMA) . ":" ?>
                                                                        </td>
                                                                        <td width="24%" align="left" valign="middle">
                                                                            <?
                                                                            $TamanoText = "200px";
                                                                            $ClassText  = "copyright";
                                                                            $MaxLength  = "50";
                                                                            $html->TextBox("txModelo", $txModelo);
                                                                            ?>
                                                                        </td>
                                                                        <td width="24%" height="20" align="left"
                                                                            valign="middle"
                                                                            class="textoazul"><?= $auxiliar->traduce("Unidad Base", $administrador->ID_IDIOMA) . ":" ?>
                                                                        </td>
                                                                        <td width="24%" align="left" valign="middle">
                                                                            <?
                                                                            $TamanoText = "200px";
                                                                            $ClassText  = "copyright";
                                                                            $MaxLength  = "50";
                                                                            $jscript    = "onchange=\"document.FormSelect.idUnidadBase.value=''\"";
                                                                            $idTextBox  = 'txUnidadBase';
                                                                            $html->TextBox("txUnidadBase", $txUnidadBase);
                                                                            unset($jscript);
                                                                            unset($idTextBox);
                                                                            ?>
                                                                            <input type="hidden"
                                                                                   name="idUnidadBase"
                                                                                   id="idUnidadBase"
                                                                                   value="<?= $idUnidadBase ?>"/>
                                                                            <a href="<?= $pathRaiz ?>buscadores_maestros/busqueda_unidad.php?AlmacenarId=0&NombreCampo=UnidadBase"
                                                                               class="fancyboxUnidad"
                                                                               id="categoriasUbicacion"> <img
                                                                                        src="<?= $pathRaiz ?>imagenes/botones/listado.png"
                                                                                        alt="<?= $auxiliar->traduce("Buscar Unidad Base", $administrador->ID_IDIOMA) ?>"
                                                                                        name="Listado"
                                                                                        border="0" align="absbottom"
                                                                                        id="Listado"/> </a>
                                                                            <span id="desplegable_unidad_base"
                                                                                  style="display: none;">
                                                                                <img
                                                                                        src="<?= $pathClases; ?>lib/ajax_script/img/esperando.gif"
                                                                                        width="15"
                                                                                        height="11"
                                                                                        alt="<?= $auxiliar->traduce("Buscando...", $administrador->ID_IDIOMA) ?>"/>
                                                                            </span>

                                                                            <div class="entry" align="left"
                                                                                 id="actualizador_unidad_base"></div>
                                                                            <script type="text/javascript"
                                                                                    language="JavaScript">
                                                                                new Ajax.Autocompleter('txUnidadBase', 'actualizador_unidad_base', '<?=$pathRaiz?>buscadores_maestros/resp_ajax_unidad.php?AlmacenarId=0&NombreCampo=UnidadBase',
                                                                                    {
                                                                                        method: 'post',
                                                                                        indicator: 'desplegable_unidad_base',
                                                                                        minChars: '1',
                                                                                        afterUpdateElement: function (textbox, valor) {
                                                                                            siguiente_control(jQuery('#' + this.paramName));
                                                                                            jQuery(textbox).val(jQuery(valor).children('a').attr('alt'));//VALOR DEL PAR&Aacute;METRO ALT DEL ENLACE <a>
                                                                                            jQuery('#idUnidadBase').val(jQuery(valor).children('a').attr('rev'));
                                                                                        }
                                                                                    }
                                                                                );
                                                                            </script>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td width="24%" height="20" align="left"
                                                                            valign="middle"
                                                                            class="textoazul"><?= $auxiliar->traduce("Tipo Material", $administrador->ID_IDIOMA) . ":" ?>
                                                                        </td>
                                                                        <td width="24%" align="left" valign="middle">
                                                                            <?
                                                                            $SeleccionMultiple='Si';
                                                                            $NombreSelect = 'selTipo';
                                                                            $Elementos_tipo[0]['text'] = $auxiliar->traduce("Pequeño Repuesto", $administrador->ID_IDIOMA);
                                                                            $Elementos_tipo[0]['valor'] = 'Pequeño Repuesto';
                                                                            $Elementos_tipo[1]['text'] = $auxiliar->traduce("Gran Componente", $administrador->ID_IDIOMA);
                                                                            $Elementos_tipo[1]['valor'] = 'Gran Componente';
                                                                            $Elementos_tipo[2]['text'] = $auxiliar->traduce("Consumibles", $administrador->ID_IDIOMA);
                                                                            $Elementos_tipo[2]['valor'] = 'Consumibles';
                                                                            $Elementos_tipo[3]['text'] = $auxiliar->traduce("Heramienta", $administrador->ID_IDIOMA);
                                                                            $Elementos_tipo[3]['valor'] = 'Heramienta';
                                                                            $Elementos_tipo[4]['text'] = $auxiliar->traduce("Materias Primas", $administrador->ID_IDIOMA);
                                                                            $Elementos_tipo[4]['valor'] = 'Materias Primas';
                                                                            $Elementos_tipo[5]['text'] = $auxiliar->traduce("Material de Oficina", $administrador->ID_IDIOMA);
                                                                            $Elementos_tipo[5]['valor'] = 'Material de Oficina';
                                                                            $Elementos_tipo[6]['text'] = $auxiliar->traduce("Otros Materiales", $administrador->ID_IDIOMA);
                                                                            $Elementos_tipo[6]['valor'] = 'Otros Materiales';
                                                                            $Elementos_tipo[7]['text'] = $auxiliar->traduce("Servicios Acciona", $administrador->ID_IDIOMA);
                                                                            $Elementos_tipo[7]['valor'] = 'Servicios Acciona';
                                                                            $Elementos_tipo[8]['text'] = $auxiliar->traduce("Pruebas logísticas", $administrador->ID_IDIOMA);
                                                                            $Elementos_tipo[8]['valor'] = 'Pruebas logísticas';
                                                                            $Elementos_tipo[9]['text'] = $auxiliar->traduce("Código I&C", $administrador->ID_IDIOMA);
                                                                            $Elementos_tipo[9]['valor'] = 'Código I&C';
                                                                            $Tamano = "205px";
                                                                            $Estilo = "copyright";

                                                                            $html->SelectArr($NombreSelect, $Elementos_tipo, 'Pequeño Repuesto');
                                                                            unset( $SeleccionMultiple);
                                                                            ?>
                                                                        </td>
                                                                        <td width="24%" height="20" align="left"
                                                                            valign="middle"
                                                                            class="textoazul"><?= $auxiliar->traduce("Unidad Compra", $administrador->ID_IDIOMA) . ":" ?>
                                                                        </td>
                                                                        <td width="24%" align="left" valign="middle">
                                                                            <?
                                                                            $TamanoText = "200px";
                                                                            $ClassText  = "copyright";
                                                                            $MaxLength  = "50";
                                                                            $jscript    = "onchange=\"document.FormSelect.idUnidadCompra.value=''\"";
                                                                            $idTextBox  = 'txUnidadCompra';
                                                                            $html->TextBox("txUnidadCompra", $txUnidadCompra);
                                                                            unset($jscript);
                                                                            unset($idTextBox);
                                                                            ?>
                                                                            <input type="hidden"
                                                                                   name="idUnidadCompra"
                                                                                   id="idUnidadCompra"
                                                                                   value="<?= $idUnidadCompra ?>"/>
                                                                            <a href="<?= $pathRaiz ?>buscadores_maestros/busqueda_unidad.php?AlmacenarId=0&NombreCampo=UnidadCompra"
                                                                               class="fancyboxUnidad"
                                                                               id="categoriasUbicacion"> <img
                                                                                        src="<?= $pathRaiz ?>imagenes/botones/listado.png"
                                                                                        alt="<?= $auxiliar->traduce("Buscar Unidad Compra", $administrador->ID_IDIOMA) ?>"
                                                                                        name="Listado"
                                                                                        border="0" align="absbottom"
                                                                                        id="Listado"/> </a>
                                                                            <span id="desplegable_unidad_compra"
                                                                                  style="display: none;">
                                                                                <img
                                                                                        src="<?= $pathClases; ?>lib/ajax_script/img/esperando.gif"
                                                                                        width="15"
                                                                                        height="11"
                                                                                        alt="<?= $auxiliar->traduce("Buscando...", $administrador->ID_IDIOMA) ?>"/>
                                                                            </span>

                                                                            <div class="entry" align="left"
                                                                                 id="actualizador_unidad_compra"></div>
                                                                            <script type="text/javascript"
                                                                                    language="JavaScript">
                                                                                new Ajax.Autocompleter('txUnidadCompra', 'actualizador_unidad_compra', '<?=$pathRaiz?>buscadores_maestros/resp_ajax_unidad.php?AlmacenarId=0&NombreCampo=UnidadCompra',
                                                                                    {
                                                                                        method: 'post',
                                                                                        indicator: 'desplegable_unidad_compra',
                                                                                        minChars: '1',
                                                                                        afterUpdateElement: function (textbox, valor) {
                                                                                            siguiente_control(jQuery('#' + this.paramName));
                                                                                            jQuery(textbox).val(jQuery(valor).children('a').attr('alt'));//VALOR DEL PAR&Aacute;METRO ALT DEL ENLACE <a>
                                                                                            jQuery('#idUnidadCompra').val(jQuery(valor).children('a').attr('rev'));
                                                                                        }
                                                                                    }
                                                                                );
                                                                            </script>

                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td width="24%" height="20" align="left"
                                                                            valign="middle"
                                                                            class="textoazul"><?= $auxiliar->traduce("Observaciones", $administrador->ID_IDIOMA) . ":" ?>
                                                                        </td>
                                                                        <td width="24%" align="left" valign="middle">
                                                                            <?
                                                                            $TamanoText = "200px";
                                                                            $ClassText  = "copyright";
                                                                            $MaxLength  = "50";
                                                                            $html->TextBox("txObservaciones", $txtxObservaciones);
                                                                            ?>
                                                                        </td>
                                                                        <td width="24%" height="20" align="left"
                                                                            valign="middle"
                                                                            class="textoazul"><?= $auxiliar->traduce("Divisibilidad", $administrador->ID_IDIOMA) . ":" ?>
                                                                        </td>
                                                                        <td width="24%" align="left" valign="middle">
                                                                            <?
                                                                            $NombreSelect = 'selDivisibilidad';
                                                                            $Elementos_divisibilidad[0]['text'] = $auxiliar->traduce("Si", $administrador->ID_IDIOMA);
                                                                            $Elementos_divisibilidad[0]['valor'] = 'Si';
                                                                            $Elementos_divisibilidad[1]['text'] = $auxiliar->traduce("No", $administrador->ID_IDIOMA);
                                                                            $Elementos_divisibilidad[1]['valor'] = 'No';
                                                                            $Elementos_divisibilidad[2]['text'] = $auxiliar->traduce("Pendiente decisión", $administrador->ID_IDIOMA);
                                                                            $Elementos_divisibilidad[2]['valor'] = 'Pendiente decisión';
                                                                            $Elementos_divisibilidad[3]['text'] = $auxiliar->traduce("No Aplica", $administrador->ID_IDIOMA);
                                                                            $Elementos_divisibilidad[3]['valor'] = 'No Aplica';
                                                                            $Tamano = "205px";
                                                                            $Estilo = "copyright";

                                                                            $html->SelectArr($NombreSelect, $Elementos_divisibilidad, 'No Aplica');
                                                                            ?>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>


                                                                    </tr>
                                                                    <tr>
                                                                        <td width="24%" height="20" align="left"
                                                                            valign="middle"
                                                                            class="textoazul"><?= $auxiliar->traduce("Ver Observaciones", $administrador->ID_IDIOMA) . ":" ?>
                                                                        </td>
                                                                        <td width="24%" align="left" valign="middle">
                                                                            <?
                                                                            $TamanoText = "200px";
                                                                            $ClassText  = "copyright";
                                                                            $MaxLength  = "50";
                                                                            $html->Option('cboxObservaciones','Check',$checkboxchecked,true);
                                                                            ?>

                                                                        </td>

                                                                        <td width="24%" height="20" align="left"
                                                                            valign="middle"
                                                                            class="textoazul"><?= $auxiliar->traduce("Baja", $administrador->ID_IDIOMA) . ":" ?>
                                                                        </td>
                                                                        <td width="24%" align="left" valign="middle">
                                                                            <?
                                                                            $NombreSelect = 'selBaja';
                                                                            $Elementos_baja[0]['text'] = $auxiliar->traduce("Todos", $administrador->ID_IDIOMA);
                                                                            $Elementos_baja[0]['valor'] = 'Todos';
                                                                            $Elementos_baja[1]['text'] = $auxiliar->traduce("Si", $administrador->ID_IDIOMA);
                                                                            $Elementos_baja[1]['valor'] = 'Si';
                                                                            $Elementos_baja[2]['text'] = $auxiliar->traduce("No", $administrador->ID_IDIOMA);
                                                                            $Elementos_baja[2]['valor'] = 'No';
                                                                            $Tamano = "205px";
                                                                            $Estilo = "copyright";
                                                                            if (!isset($selBaja)):
                                                                                $selBaja = "No";
                                                                            else:
                                                                                $selBaja = $selBaja;
                                                                            endif;
                                                                            $html->SelectArr($NombreSelect, $Elementos_baja, $selBaja, $selBaja);
                                                                            ?>

                                                                        </td>

                                                                        <td height="20" align="left" valign="middle"
                                                                            class="textoazul">&nbsp;</td>
                                                                        <td align="left" valign="middle"></td>
                                                                        <td width="4%" align="center" valign="top">
                                                                            &nbsp;</td>



                                                                    </tr>
                                <td width="24%" height="20" align="left"
                                    valign="middle"
                                    class="textoazul"><?= $auxiliar->traduce("Ver Detalle AGM", $administrador->ID_IDIOMA) . ":" ?>
                                </td>
                                <td width="24%" align="left" valign="middle">
                                    <?
                                    $TamanoText = "200px";
                                    $ClassText  = "copyright";
                                    $MaxLength  = "50";
                                    $html->Option('cboxAGM','Check',$checkboxcheckedAGM,true);
                                    ?>
                                </td>
                                                        <td width="24%" height="20" align="left"
                                                            valign="middle"
                                                            class="textoazul"><?= $auxiliar->traduce("Material AGM", $administrador->ID_IDIOMA) . ":" ?>
                                                        </td>
                                                        <td width="24%" align="left" valign="middle">
                                                            <?
                                                            $NombreSelect = 'selMaterialAGM';
                                                            $Elementos_AGM[0]['text'] = $auxiliar->traduce("Todos", $administrador->ID_IDIOMA);
                                                            $Elementos_AGM[0]['valor'] = 'Todos';
                                                            $Elementos_AGM[1]['text'] = $auxiliar->traduce("Si", $administrador->ID_IDIOMA);
                                                            $Elementos_AGM[1]['valor'] = 'Si';
                                                            $Elementos_AGM[2]['text'] = $auxiliar->traduce("No", $administrador->ID_IDIOMA);
                                                            $Elementos_AGM[2]['valor'] = 'No';
                                                            $Tamano = "205px";
                                                            $Estilo = "copyright";
                                                            if (!isset($selAGM)):
                                                                $selAGM = "No";
                                                            endif;
                                                            $html->SelectArr($NombreSelect, $Elementos_AGM, $selAGM, 'Todos');
                                                            ?>

                                                        </td>
                                                        <tr>
                                                            <td width="24%" height="20" align="left"
                                                                valign="middle"
                                                                class="textoazul">
                                                            </td>
                                                            <td width="24%" height="20" align="left"
                                                                valign="middle"
                                                                class="textoazul">
                                                            </td>
                                                            <td width="24%" height="20" align="left"
                                                                valign="middle"
                                                                class="textoazul"><?= $auxiliar->traduce("Componente AGM", $administrador->ID_IDIOMA) . ":" ?>
                                                            </td>
                                                            <td width="24%" align="left" valign="middle">
                                                                <?
                                                                $TamanoText = "200px";
                                                                $ClassText  = "copyright";
                                                                $MaxLength  = "50";
                                                                $html->TextBox("txComponenteAGM", $txComponenteAGM);
                                                                ?>
                                                            </td>
                                                        </tr>
                                                                </table>
                                                            </td>
                                                            <td width="4" bgcolor="#D9E3EC" class="lineaderecha">
                                                                &nbsp;
                                                            </td>
                                                        </tr>

                                                        <tr bgcolor="#D9E3EC">
                                                            <td height="5" colspan="3" bgcolor="#D9E3EC"
                                                                class="lineabajodereizq"><img
                                                                        src="<? echo $pathRaiz ?>imagenes/transparente.gif"
                                                                        width="10" height="5"></td>
                                                        </tr>
                                                    </table>
                                                    <img src="<? echo $pathRaiz ?>imagenes/transparente.gif" width="10"
                                                         height="5"></td>
                                                <td width="20" align="center" valign="bottom" class="lineaizquierda">
                                                    &nbsp;
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="left" valign="top" bgcolor="#B3C7DA" class="lineabajo">
                                        <table width="100%" border="0" cellpadding="0" cellspacing="0"
                                               class="lineabajo">
                                            <tr>
                                                <td height="25" colspan="2" align="center" valign="middle"
                                                    class="lineabajo">
                                                    <div align="right"><span class="textoazul">

                                                             <!--VENTANA OPCIONES EMERGENTE IMPORTACION MASIVA -->
                                                                <div class="menu_herramientas"
                                                                     style="display: inline-block; ">
                                                                    <a href="#" id="btnAccionesLinea"
                                                                       onmouseenter="ventana_opciones(this,event);return false;"
                                                                       class="senaladoverde botones"
                                                                       style="white-space: nowrap;">
                                                                        &nbsp;&nbsp;
                                                                        <img src="<?= $pathRaiz ?>imagenes/wheel.png"
                                                                             alt="Herramientas"
                                                                             height="16px" width="16px"
                                                                             style="vertical-align: middle;padding-bottom:2px;"/>
                                                                        <? echo $auxiliar->traduce("Importacion Masiva", $administrador->ID_IDIOMA) ?>
                                                                        &nbsp;&nbsp;&nbsp;&nbsp;
                                                                    </a>&nbsp;
                                                                    <ul>
                                                                        <li>
                                                                            <a href="ficha_importancion_excel_paso1.php"
                                                                               class="copyrightbotonesfancyboxImportacion">
                                                                                <img
                                                                                        src="<?= $pathRaiz ?>imagenes/excel.png"
                                                                                        border="0"/>
                                                                                &nbsp;&nbsp;&nbsp;
                                                                                <?= $auxiliar->traduce("Importacion Masiva", $administrador->ID_IDIOMA) . "(Excel)" ?>
                                                                                &nbsp;&nbsp;&nbsp;
                                                                            </a>
                                                                        </li>
                                                                        <li>
                                                                            <a href="ficha_importacion_csv_paso1.php"
                                                                               class="copyrightbotonesfancyboxImportacion">
                                                                                <img
                                                                                        src="<?= $pathRaiz ?>imagenes/add_document.png"
                                                                                        border="0"/>
                                                                                &nbsp;&nbsp;&nbsp;
                                                                                <?= $auxiliar->traduce("Importacion Masiva", $administrador->ID_IDIOMA) . "(CSV)" ?>
                                                                                &nbsp;&nbsp;&nbsp;
                                                                            </a>
                                                                        </li>
                                                                        <li>
                                                                            <a href="ficha_importacion_copiar_pegar_paso1.php"
                                                                               class="copyrightbotonesfancyboxImportacion">
                                                                                <img
                                                                                        src="<?= $pathRaiz ?>imagenes/edit_form.png"
                                                                                        name="DeshacerAnulaciones"
                                                                                        border="0"/>
                                                                                &nbsp;&nbsp;&nbsp;
                                                                                <?= $auxiliar->traduce("Copiar y Pegar", $administrador->ID_IDIOMA) ?>
                                                                                &nbsp;&nbsp;&nbsp;
                                                                            </a>
                                                                        </li>
                                                                    </ul>
                                                                </div>
                                                            <!--FIN VENTANA OPCIONES EMERGENTE IMPORTACION MASIVA -->

                                                            <a href="ficha.php" class="senaladoazul">&nbsp;&nbsp;&nbsp;&nbsp;<?= $auxiliar->traduce("Crear", $administrador->ID_IDIOMA) ?>&nbsp;&nbsp;&nbsp;&nbsp;</a>&nbsp;
                                                            <a href="#" class="senaladoazul"
                                                               onClick="return exportarExcel();">&nbsp;&nbsp;&nbsp;<?= $auxiliar->traduce("Excel", $administrador->ID_IDIOMA) ?>&nbsp;&nbsp;&nbsp;</a>&nbsp;
                                                            <a href="#" class="senaladoamarillo"
                                                               onClick="document.FormSelect.Buscar.value='Si';document.FormSelect.submit();return false">&nbsp;&nbsp;&nbsp;&nbsp;<?= $auxiliar->traduce("Buscar", $administrador->ID_IDIOMA) ?>&nbsp;&nbsp;&nbsp;&nbsp;</a>&nbsp;
                                                        </span>
                                                    </div>
                                                </td>
                                            </tr>
                                            <? if ($numRegistros > 0): ?>
                                                <tr>
                                                    <td colspan="2" bgcolor="#d9e3ec">
                                                        <table border="0" cellpadding="0" cellspacing="0" height="10">
                                                            <tbody>

                                                            <tr>
                                                                <td width="100%" height="20" colspan="2"
                                                                    class="alertas4">
                                                                    &nbsp;&nbsp;&nbsp;<? echo "$textoLista" ?></td>
                                                            </tr>

                                                            </tbody>
                                                        </table>
                                                    </td>
                                                </tr>
                                            <? endif ?>
                                            <? if ($numRegistros > 0): ?>
                                                <tr bgcolor="#D9E3EC" class="lineaabajo" height="22">
                                                    <td width="27%" class="copyright">
                                                        &nbsp;&nbsp;&nbsp;<?= $auxiliar->traduce("Ver", $administrador->ID_IDIOMA) ?>
                                                        #
                                                        <? $navegar->GenerarComboNumRegs($navegar->maxfilasMaestroMaterial, "selLimiteSuperior"); ?>
                                                        &nbsp;&nbsp;
                                                        <? $navegar->NumRegs($navegar->maxfilasMaestroMaterial, $maxahora, $navegar->numerofilasMaestroMaterial); ?>
                                                    </td>
                                                    <td width="73%" class="copyright">
                                                        <div align="right"><? $navegar->Numeros($navegar->sqlAdminMaestroMaterial, $navegar->maxfilasMaestroMaterial, $navegar->numerofilasMaestroMaterial, $i, "index.php", "#2E8AF0"); ?>
                                                            &nbsp;&nbsp;&nbsp;
                                                        </div>
                                                    </td>
                                                </tr>

                                                <tr class="lineabajo">
                                                    <td colspan="2" align="center" bgcolor="#D9E3EC">
                                                        <table width="98%" cellpadding="0" cellspacing="2"
                                                               class="linealrededor">

                                                            <tr>
                                                                <td height="19" bgcolor="#2E8AF0"
                                                                    class="blanco"><? $navegar->GenerarColumna($auxiliar->traduce("Nº Material", $administrador->ID_IDIOMA), "enlaceCabecera", "id_incidencia_sistema_tipo", $pathRaiz) ?></td>
                                                               <td height="19" bgcolor="#2E8AF0"
                                                                    class="blanco"><? $navegar->GenerarColumna($auxiliar->traduce("Descripcion Material", $administrador->ID_IDIOMA), "enlaceCabecera", "incidencia_sistema_tipo_eng", $pathRaiz) ?></td>
                                                                <td height="19" bgcolor="#2E8AF0"
                                                                    class="blanco"><? $navegar->GenerarColumna($auxiliar->traduce("EM", $administrador->ID_IDIOMA), "enlaceCabecera", "incidencia_sistema_tipo_eng", $pathRaiz) ?></td>
                                                                <td height="19" bgcolor="#2E8AF0"
                                                                    class="blanco"><? $navegar->GenerarColumna($auxiliar->traduce("Tipo Material", $administrador->ID_IDIOMA), "enlaceCabecera", "incidencia_sistema_tipo_eng", $pathRaiz) ?></td>
                                                                <td height="19" bgcolor="#2E8AF0"
                                                                    class="blanco"><? $navegar->GenerarColumna($auxiliar->traduce("Familia material", $administrador->ID_IDIOMA), "enlaceCabecera", "incidencia_sistema_tipo_eng", $pathRaiz) ?></td>
                                                                <td height="19" bgcolor="#2E8AF0"
                                                                    class="blanco"><? $navegar->GenerarColumna($auxiliar->traduce("Familia repro", $administrador->ID_IDIOMA), "enlaceCabecera", "incidencia_sistema_tipo_eng", $pathRaiz) ?></td>
                                                                <td height="19" bgcolor="#2E8AF0"
                                                                    class="blanco"><? $navegar->GenerarColumna($auxiliar->traduce("Marca", $administrador->ID_IDIOMA), "enlaceCabecera", "incidencia_sistema_tipo_eng", $pathRaiz) ?></td>
                                                                <td height="19" bgcolor="#2E8AF0"
                                                                    class="blanco"><? $navegar->GenerarColumna($auxiliar->traduce("Modelo", $administrador->ID_IDIOMA), "enlaceCabecera", "incidencia_sistema_tipo_eng", $pathRaiz) ?></td>
                                                                <td height="19" bgcolor="#2E8AF0"
                                                                    class="blanco"><? $navegar->GenerarColumna($auxiliar->traduce("UM", $administrador->ID_IDIOMA), "enlaceCabecera", "incidencia_sistema_tipo_eng", $pathRaiz) ?></td>
                                                                <td height="19" bgcolor="#2E8AF0"
                                                                    class="blanco"><? $navegar->GenerarColumna($auxiliar->traduce("Div", $administrador->ID_IDIOMA), "enlaceCabecera", "incidencia_sistema_tipo_eng", $pathRaiz) ?></td>
                                                                <td height="19" bgcolor="#2E8AF0"
                                                                    class="blanco"><? $navegar->GenerarColumna($auxiliar->traduce("RA", $administrador->ID_IDIOMA), "enlaceCabecera", "incidencia_sistema_tipo_eng", $pathRaiz) ?></td>
                                                                <td height="19" bgcolor="#2E8AF0"
                                                                    class="blanco"><? $navegar->GenerarColumna($auxiliar->traduce("O", $administrador->ID_IDIOMA), "enlaceCabecera", "incidencia_sistema_tipo_eng", $pathRaiz) ?></td>
                                                                <td height="19" bgcolor="#2E8AF0"
                                                                    class="blanco"><? $navegar->GenerarColumna($auxiliar->traduce("Baja", $administrador->ID_IDIOMA), "enlaceCabecera", "baja", $pathRaiz); ?></td>
                                                                <td height="19" bgcolor="#2E8AF0"
                                                                    class="blanco"><? $navegar->GenerarColumna($auxiliar->traduce("Etiqueta", $administrador->ID_IDIOMA), "enlaceCabecera", "etiqueta", $pathRaiz); ?></td>
                                                                <td height="19" bgcolor="#2E8AF0"
                                                                    class="blanco"><? $navegar->GenerarColumna($auxiliar->traduce("AGM", $administrador->ID_IDIOMA), "enlaceCabecera", "agm", $pathRaiz); ?></td>
                                                            </tr>
                                                            <? // MUESTRO LAS COINCIDENCIAS CON LA BUSQUEDA
                                                            $i = 0;
                                                            // PARA LA NUMERACION DE CADA URL
                                                            $numeracion = $mostradas + 1;
                                                            while ($i < $maxahora):
                                                                $row = $bd->SigReg($resultado);
                                                                
                                                                //COLOR DE LA FILA
                                                                if ($i % 2 == 0) $myColor = "#B3C7DA";
                                                                else $myColor = "#AACFF9";
                                                                ?>
                                                                <tr align="center" valign="middle" bgcolor="#AACFF9">
                                                                    <td height="18" align="left"
                                                                        bgcolor="<? echo $myColor ?>"
                                                                        class="enlaceceldas">&nbsp;<a
                                                                            <?$row = $bd->VerReg("MATERIALES", "ID_MATERIALES", $row->ID_MATERIALES);?>
                                                                                href="ficha.php?idMaterial=<?= $row->REFERENCIA_SCS; ?>"
                                                                                class="enlaceceldasacceso"><? echo $row->REFERENCIA_SCS ?></a>&nbsp;
                                                                    </td>
                                                                    <?php
                                                                    if(($administrador->ID_IDIOMA)=='ESP'){?>
                                                                    <td height="18" align="left"
                                                                        bgcolor="<? echo $myColor ?>"
                                                                        <?$row = $bd->VerReg("MATERIALES", "ID_MATERIALES", $row->ID_MATERIALES);?>
                                                                        class="enlaceceldas">&nbsp;<? echo $row->DESCRIPCION_ESP ?></a>&nbsp;
                                                                    </td>
                                                                    <?php }else{ ?>
                                                                    <td height="18" align="left"
                                                                        bgcolor="<? echo $myColor ?>"
                                                                        class="enlaceceldas">
                                                                        <?$row = $bd->VerReg("MATERIALES", "ID_MATERIALES", $row->ID_MATERIALES);?>
                                                                        &nbsp;<? echo (!empty($row->DESCRIPCION_ENG)) ? $row->DESCRIPCION_ENG : '-' ?>
                                                                    </td>
                                                                    <?php }?>

                                                                    <?
                                                                    $row = $bd->VerReg("MATERIALES", "ID_MATERIALES", $row->ID_MATERIALES);
                                                                    $eb_bgcolor = "#FFFFFF";
                                                                    $eb_color   = "";
                                                                    if ($row->ESTATUS_MATERIAL == '01-Bloqueo General'):
                                                                        $eb_bgcolor = "#F04343";
                                                                        $eb_color   = "#FFFFFF";
                                                                    elseif ($row->ESTATUS_MATERIAL == '02-Obsoleto Fin Existencias (Error)'):
                                                                        $eb_bgcolor = "#F04343";
                                                                        $eb_color   = "#FFFFFF";
                                                                    elseif ($row->ESTATUS_MATERIAL == '03-Código duplicado'):
                                                                        $eb_bgcolor = "#F04343";
                                                                        $eb_color   = "#FFFFFF";
                                                                    elseif ($row->ESTATUS_MATERIAL == '04-Código inutilizable'):
                                                                        $eb_bgcolor = "#F04343";
                                                                        $eb_color   = "#FFFFFF";
                                                                    elseif ($row->ESTATUS_MATERIAL == '05-Obsoleto Fin Existencias (Aviso)'):
                                                                        $eb_bgcolor = "#E9E238";
                                                                    elseif ($row->ESTATUS_MATERIAL == '06-Código Solo Fines Logísticos'):
                                                                        $eb_bgcolor = "#F04343";
                                                                        $eb_color   = "#FFFFFF";
                                                                    elseif ($row->ESTATUS_MATERIAL == '07-Solo para Refer. Prov'):
                                                                        ////////////////////////
                                                                    elseif ($row->ESTATUS_MATERIAL == 'No bloqueado'):
                                                                        $eb_bgcolor = "#B3C7DA";
                                                                    endif;?>


                                                                    <td height="18" align="left"
                                                                        bgcolor="<? echo $eb_bgcolor ?>"
                                                                        class="enlaceceldas"
                                                                        style="color: <?echo $eb_color?>">
                                                                        &nbsp;<? echo (!empty($row->ESTATUS_MATERIAL)) ? acortar($row->ESTATUS_MATERIAL) : '-' ?>
                                                                    </td>
                                                                    <td height="18" align="left"
                                                                        bgcolor="<? echo $myColor ?>"
                                                                        class="enlaceceldas">
                                                                        &nbsp;<? echo (!empty($row->TIPO_MATERIAL)) ? $row->TIPO_MATERIAL : '-' ?>
                                                                    </td>
                                                                    <td height="18" align="left"
                                                                        bgcolor="<? echo $myColor ?>"
                                                                        class="enlaceceldas">
                                                                        <?$rowInicial = $bd->VerReg("MATERIALES", "ID_MATERIALES", $row->ID_MATERIALES);?>
                                                                        &nbsp;<?$rowFinal = $bd->VerReg("FAMILIA_MATERIAL", "ID_FAMILIA_MATERIAL", $rowInicial->FK_FAMILIA_MATERIAL);

                                                                        echo (!empty($rowFinal->NOMBRE_FAMILIA)) ? $rowFinal->NOMBRE_FAMILIA : '-' ?>
                                                                    </td>
                                                                    <td height="18" align="left"
                                                                        bgcolor="<? echo $myColor ?>"
                                                                        class="enlaceceldas">
                                                                        <?$rowInicial = $bd->VerReg("MATERIALES", "ID_MATERIALES", $row->ID_MATERIALES);?>
                                                                        &nbsp;<?$rowFinal = $bd->VerReg("FAMILIA_REPRO", "ID_FAMILIA_REPRO", $rowInicial->FK_FAMILIA_REPRO);
                                                                        echo (!empty($rowFinal->REFERENCIA)) ? $rowFinal->REFERENCIA . "- ".$rowFinal->FAMILIA_REPRO : '-' ?>
                                                                    </td>
                                                                    <td height="18" align="left"
                                                                        bgcolor="<? echo $myColor ?>"
                                                                        class="enlaceceldas">
                                                                        &nbsp;<? echo (!empty($row->MARCA)) ? $row->MARCA : '-' ?>
                                                                    </td>
                                                                    <td height="18" align="left"
                                                                        bgcolor="<? echo $myColor ?>"
                                                                        class="enlaceceldas">
                                                                        &nbsp;<? echo (!empty($row->MODELO)) ? $row->MODELO : '-' ?>
                                                                    </td>
                                                                    <td height="18" align="left"
                                                                        bgcolor="<? echo $myColor ?>"
                                                                        class="enlaceceldas">
                                                                        <?$rowInicial = $bd->VerReg("MATERIALES", "ID_MATERIALES", $row->ID_MATERIALES);?>
                                                                        &nbsp;<?$rowFinal = $bd->VerReg("UNIDAD", "ID_UNIDAD", $rowInicial->FK_UNIDAD_COMPRA);
                                                                        echo (!empty($rowFinal->UNIDAD)) ? $rowFinal->UNIDAD ." ".$rowFinal->DESCRIPCION : '-' ?>
                                                                    </td>
                                                                    <td height="18" align="left"
                                                                        bgcolor="<? echo $myColor ?>"
                                                                        class="enlaceceldas">
                                                                        &nbsp;<? echo (!empty($row->DIVISIBILIDAD)) ? $row->DIVISIBILIDAD : '-' ?>
                                                                    </td>
                                                                    <td height="18" align="left"
                                                                        bgcolor="<? echo $myColor ?>"
                                                                        class="enlaceceldas">
                                                                        &nbsp;<? echo (!empty($row->REFERENCIA_AUTOMATICA)) ? $row->REFERENCIA_AUTOMATICA : '-' ?>
                                                                    </td>
                                                                    <?if (!$checkboxchecked){?>
                                                                    <td height="18" align="left"
                                                                        bgcolor="<? echo $myColor ?>"
                                                                        class="enlaceceldas">
                                                                        &nbsp;<? if(!empty($row->OBSERVACIONES)){
                                                                            ?>

                                                                            <a name="referencia" href="ficha_observaciones.php?referencia=<?= $row->REFERENCIA_SCS; ?>"
                                                                               class="fancyboxUnidad">
                                                                                <img
                                                                                        src="<?= $pathRaiz ?>imagenes/form.png"
                                                                                        name="DeshacerAnulaciones"
                                                                                        border="0"/>
                                                                            </a>
                                                                            <?}else{
                                                                            echo('-');
                                                                            }?>
                                                                    </td>
                                                                    <?}else{?>
                                                                    <td height="18" align="left"
                                                                        bgcolor="<? echo $myColor ?>"
                                                                        class="enlaceceldas">
                                                                        &nbsp;<? echo (!empty($row->OBSERVACIONES)) ? 'Si' : '-' ?>
                                                                    </td>
                                                                    <?};?>
                                                                    <td height="18" align="center"
                                                                        bgcolor="<? echo $myColor ?>"
                                                                        class="enlaceceldas">
                                                                        <?
                                                                        if($row->BAJA == 0 || $row->BAJA == "0") echo $auxiliar->traduce("No", $administrador->ID_IDIOMA);
                                                                        else echo $auxiliar->traduce("Si", $administrador->ID_IDIOMA);
                                                                        ?>
                                                                    </td>
                                                                    <td height="18" align="center"
                                                                        bgcolor="<? echo $myColor ?>"
                                                                        class="enlaceceldas">

                                                                        <a name="referencia" href="impEtiquetaPDF.php?referencia=<?= $row->REFERENCIA_SCS; ?>"
                                                                               class="fancybox">
                                                                                <img
                                                                                        src="<?= $pathRaiz ?>imagenes/botones/etiqueta.png"
                                                                                        name="Etiqueta"
                                                                                        border="0"/>
                                                                            </a>

                                                                    </td>
                                                                    <td height="18" align="center"
                                                                        bgcolor="<? echo $myColor ?>"
                                                                        class="enlaceceldas">
                                                                        <?
                                                                        if($row->AGM == 0 || $row->AGM == "0") echo $auxiliar->traduce("No", $administrador->ID_IDIOMA);
                                                                        else echo $auxiliar->traduce("Si", $administrador->ID_IDIOMA);
                                                                        ?>
                                                                    </td>
                                                                </tr>
                                                            <?
                                                            if ($checkboxchecked): ?>
                                                            <tr>
                                                                <td colspan="15" height="18" align="left"
                                                                    bgcolor="#FF9999"
                                                                    class="copyright">
                                                                    &nbsp;<? echo (!empty($row->OBSERVACIONES)) ? $row->OBSERVACIONES : '-' ?>
                                                                </td>
                                                            </tr>
                                                                <?endif;?>
                                                                <?if ($checkboxcheckedAGM): ?>
                                                            <!--
                                                                    <tr>
                                                                        <td height="19" bgcolor="#808080"><? $navegar->GenerarColumna($auxiliar->traduce("Nivel", $administrador->ID_IDIOMA), "enlaceCabecera", "id_incidencia_sistema_tipo", $pathRaiz) ?></td>
                                                                        <td height="19" bgcolor="#808080"><? $navegar->GenerarColumna($auxiliar->traduce("Ref. Material", $administrador->ID_IDIOMA), "enlaceCabecera", "id_incidencia_sistema_tipo", $pathRaiz) ?></td>
                                                                        <td height="19" bgcolor="#808080"><? $navegar->GenerarColumna($auxiliar->traduce("Material", $administrador->ID_IDIOMA), "enlaceCabecera", "id_incidencia_sistema_tipo", $pathRaiz) ?></td>
                                                                        <td height="19" bgcolor="#808080"><? $navegar->GenerarColumna($auxiliar->traduce("EM", $administrador->ID_IDIOMA), "enlaceCabecera", "id_incidencia_sistema_tipo", $pathRaiz) ?></td>
                                                                        <td height="19" bgcolor="#808080"><? $navegar->GenerarColumna($auxiliar->traduce("Tipo Material", $administrador->ID_IDIOMA), "enlaceCabecera", "id_incidencia_sistema_tipo", $pathRaiz) ?></td>
                                                                        <td height="19" bgcolor="#808080"><? $navegar->GenerarColumna($auxiliar->traduce("Familia Material", $administrador->ID_IDIOMA), "enlaceCabecera", "id_incidencia_sistema_tipo", $pathRaiz) ?></td>
                                                                        <td height="19" bgcolor="#808080"><? $navegar->GenerarColumna($auxiliar->traduce("Familia Repro", $administrador->ID_IDIOMA), "enlaceCabecera", "id_incidencia_sistema_tipo", $pathRaiz) ?></td>
                                                                        <td height="19" bgcolor="#808080"><? $navegar->GenerarColumna($auxiliar->traduce("Cantidad", $administrador->ID_IDIOMA), "enlaceCabecera", "id_incidencia_sistema_tipo", $pathRaiz) ?></td>
                                                                        <td height="19" bgcolor="#808080"><? $navegar->GenerarColumna($auxiliar->traduce("UB", $administrador->ID_IDIOMA), "enlaceCabecera", "id_incidencia_sistema_tipo", $pathRaiz) ?></td>
                                                                        <td height="19" bgcolor="#808080"><? $navegar->GenerarColumna($auxiliar->traduce("AGM", $administrador->ID_IDIOMA), "enlaceCabecera", "id_incidencia_sistema_tipo", $pathRaiz) ?></td>
                                                                        <td height="19" bgcolor="#808080"><? $navegar->GenerarColumna($auxiliar->traduce("B", $administrador->ID_IDIOMA), "enlaceCabecera", "id_incidencia_sistema_tipo", $pathRaiz) ?></td>
                                                                    </tr>
                                                                    -->
                                                                    <?
                                                                $row = $bd->VerReg("MATERIALES", "ID_MATERIALES", $row->ID_MATERIALES);
                                                                $vector=array();
                                                                obtenerHijosMateriales($row->REFERENCIA_SCS,$bd,$vector);
                                                                pintar_tabla_hijos($vector,$myColor);
                                                                endif;?>
                                                                <? $i++;
                                                                $numeracion++;
                                                            endwhile; ?>
                                                        </table>
                                                    </td>
                                                </tr>
                                            <? else: ?>
                                                <tr>
                                                    <td colspan="2" align="center" bgcolor="#D9E3EC" class="alertas3"
                                                        height="19px"><?= $auxiliar->traduce("No existen registros para la búsqueda realizada", $administrador->ID_IDIOMA) ?></td>
                                                </tr>
                                            <? endif; ?>
                                            <? if ($numRegistros > 0): ?>
                                                <tr bgcolor="#D9E3EC" class="lineaabajo" height="22">
                                                    <td width="27%" class="copyright">
                                                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?= $auxiliar->traduce("Ver", $administrador->ID_IDIOMA) ?>
                                                        &nbsp;#
                                                        <? $navegar->GenerarComboNumRegs($navegar->maxfilasMaestroMaterial); ?>
                                                        &nbsp;&nbsp;
                                                        <? $navegar->NumRegs($navegar->maxfilasMaestroMaterial, $maxahora, $navegar->numerofilasMaestroMaterial); ?>
                                                    </td>
                                                    <td width="73%" class="copyright">
                                                        <div align="right"><? $navegar->Numeros($navegar->sqlAdminMaestroMaterial, $navegar->maxfilasMaestroMaterial, $navegar->numerofilasMaestroMaterial, $i, "index.php", "#2E8AF0"); ?>
                                                            &nbsp;&nbsp;&nbsp;
                                                            &nbsp;&nbsp;&nbsp;
                                                        </div>
                                                    </td>
                                                </tr>
                                            <? endif; ?>
                                        </table>
                                        <br><br></td>
                                </tr>
                                <tr>
                                    <? include $pathRaiz . "copyright.php"; ?>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    <input type="submit" style="position:absolute; top:-999999px"/>
    <input type="hidden" name="Buscar" value="Si"/>
</FORM>
</body>
</html>