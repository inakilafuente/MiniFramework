<?
// PATHS DE LA WEB
$pathRaiz   = "../../";
$pathClases = "../../../";

// INCLUDES DE LIBRERIAS PROPIAS
require_once $pathClases . "lib/basedatos.php";
require_once $pathClases . "lib/administrador.php";
require_once $pathClases . "lib/html.php";
require_once $pathClases . "lib/navegar.php";
require_once $pathClases . "lib/auxiliar.php";

session_start();


function addMaterial($idpadre,$idhijo,$bd){
    $sql       = "INSERT INTO MATERIAL_COMPONENTE_AGM SET
                MATERIAL_AGM='" . trim( (string)$bd->escapeCondicional($idpadre)) . "'
                ,MATERIAL_COMPONENTE='" . trim( (string)$bd->escapeCondicional($idhijo)) . "'
                ,CANTIDAD='" . trim( (string)$bd->escapeCondicional($txCantidad)) . "'
                ,BAJA='" . $chBaja . "'";
    $TipoError = "ErrorEjecutarSql";
    $bd->ExecSQL($sql);
}

function cancelarLinea($idpadre,$idhijo,$bd){
    $sql       = "UPDATE MATERIAL_COMPONENTE_AGM SET
                    BAJA=false 
                    WHERE MATERIAL_AGM='" . trim( (string)$bd->escapeCondicional($idpadre)) . "' AND MATERIAL_COMPONENTE='" . trim( (string)$bd->escapeCondicional($idhijo)) . "'";
    $TipoError = "ErrorEjecutarSql";
    //var_dump($sql);
   // $bd->ExecSQL($sql);
}


function obtenerHijosMateriales($id,$bd,&$vector){
    $sqlHijos = "SELECT M.ID_MATERIAL ,M.REFERENCIA_SCS , M.DESCRIPCION_ESP , M.DESCRIPCION_ENG ,M.ESTATUS_MATERIAL,M.TIPO_MATERIAL,fr.REFERENCIA, fr.FAMILIA_REPRO , fm.NOMBRE_FAMILIA ,MCA.CANTIDAD ,M.ID_UNIDAD_MEDIDA ,M.MATERIAL_AGM as isAGM ,MCA.BAJA,MCA.MATERIAL_AGM ,MCA.MATERIAL_COMPONENTE, UNIDAD.UNIDAD,UNIDAD.DESCRIPCION 
    FROM MATERIAL M JOIN MATERIAL_COMPONENTE_AGM MCA ON M.ID_MATERIAL=MCA.MATERIAL_AGM 
    JOIN FAMILIA_MATERIAL fm ON M.ID_FAMILIA_MATERIAL = fm.ID_FAMILIA_MATERIAL 
    JOIN FAMILIA_REPRO fr ON M.ID_FAMILIA_REPRO = fr.ID_FAMILIA_REPRO 
    JOIN UNIDAD ON M.ID_UNIDAD_MEDIDA=UNIDAD.ID_UNIDAD 
    WHERE M.ID_MATERIAL=".$id." AND MCA.BAJA=FALSE";

    $resHijos = $bd->ExecSQL($sqlHijos);
    while($reg=$bd->SigReg($resHijos)) {

        $reg->ID_PARENT=$id;
        $vector[] = $reg;
        obtenerHijosMateriales($reg->MATERIAL_COMPONENTE, $bd, $vector);
    }
}
function pintar_tabla_hijos($vector,$myColor,$bd,$idioma){
    if(!empty($vector)){
        echo"<tr>";
        echo "<td height='19' bgcolor='#2f4f4f'class='blanco'><input type='checkbox' id='chboxAllAGM' onchange='marcarDesmarcarCbox()'></td>";
        echo "<td height='19' bgcolor='#2f4f4f'class='blanco'>Nivel</td>";
        echo "<td height='19' bgcolor='#2f4f4f'class='blanco'>Ref.</td>";
        echo "<td height='19' bgcolor='#2f4f4f'class='blanco' colspan='2'>Material</td>";
        echo "<td height='19' bgcolor='#2f4f4f'class='blanco'>Cantidad</td>";
        echo "<td height='19' bgcolor='#2f4f4f'class='blanco' colspan='2'>Acciones</td>";
        echo "<input id='idMaterialTabla' type='hidden' value='$material->MATERIAL_COMPONENTE'>";
        $nivel=0;
        $referencias=array();
        foreach ($vector as $material){
            echo "<tr>";
            if(!array_key_exists($material->MATERIAL_AGM,$referencias)){
                $nivel++;
                $referencias[$material->MATERIAL_AGM]=$nivel;

            }else{
                $nivel=$referencias[$material->MATERIAL_AGM];
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


            $sqlagm = "SELECT M.ID_MATERIAL , M.DESCRIPCION_ESP , M.DESCRIPCION_ENG ,
       MCA.MATERIAL_AGM ,MCA.MATERIAL_COMPONENTE, MCA.CANTIDAD
    FROM MATERIAL M LEFT JOIN MATERIAL_COMPONENTE_AGM MCA ON M.ID_MATERIAL=MCA.MATERIAL_COMPONENTE
            WHERE ID_MATERIAL=".$material->MATERIAL_COMPONENTE." AND MCA.BAJA=FALSE";
//var_dump($sqlagm);
//die;
            $resagm = $bd->ExecSQL($sqlagm);
            $valAGM=$bd->sigReg($resagm);
            echo "<td height='18' align='left'bgcolor='$myColor' class='enlaceceldas'><input type='checkbox' id='chboxAGMtable/$valAGM->MATERIAL_COMPONENTE/$material->ID_MATERIAL'></td>";
            //echo "<input id='idMaterialTabla' type='hidden' value='$material->MATERIAL_COMPONENTE'";
            echo "<td height='18' align='left'bgcolor='$myColor' class='enlaceceldas'>". $nivel."</td>";


            echo "<input type='hidden' id='txComponente' value='$valAGM->MATERIAL_COMPONENTE'>";
            echo "<td height='18' align='left'bgcolor='$myColor' class='enlaceceldas'><a href='ficha.php?idMaterial=$valAGM->MATERIAL_COMPONENTE' class='enlaceceldasacceso'>$valAGM->MATERIAL_COMPONENTE</a></td>";

            if($idioma=='ESP'){
                echo "<td height='18' align='left'bgcolor='$myColor' class='enlaceceldas' colspan='2'>". $valAGM->DESCRIPCION_ESP."</td>";
            }
            if($idioma=='ENG'){
                echo "<td height='18' align='left'bgcolor='$myColor' class='enlaceceldas' colspan='2'>". $valAGM->DESCRIPCION_ENG."</td>";
            }
            echo "<td height='18' align='left'bgcolor='$myColor' class='enlaceceldas'><input type='text' id='txCantidad_$valAGM->MATERIAL_COMPONENTE' value='$valAGM->CANTIDAD'></td>";
            echo "<td><button style='background-color: #1b6d85; color: whitesmoke' type='button' onclick='grabar_agm($valAGM->MATERIAL_COMPONENTE)'>Grabar</button></td>";
            echo "<td><button style='background-color: #ac2925; color: whitesmoke' type='button'onclick='borrar_agm($valAGM->MATERIAL_COMPONENTE)'>Borrar</button></td>";
            echo "</tr>";
        }
        echo "</tr>";
    }
}


function acortar($cadena){
    $valores=explode(" ",$cadena);
    if(intval($valores[0][0])>=0&&intval($valores[0][1])>0){
        return $valores[0][0].$valores[0][1];
    }else{
        return $valores[0][0].$valores[1][0];
    }
}
//Funcion pintar arbol


function pintar_arbol($vector){
    echo "<ul>";
    for ($i=count($vector)-1;$i>=0;$i--){

        echo "<li style='list-style: none'>".$vector[$i];

        echo "<ul style='list-style: none'>";
        if($i>0){
            echo "<span style='background-color: red; color: red' >.</span>";
        }
        echo "</li>";

    }
    echo "</ul>";
}


//Funcion rellenar vector con sus respectivos padres

function obtenerPadresFamilia($id,$bd,&$vector){
    $sqlPadres = "SELECT ID_FAMILIA_MATERIAL_PADRE, NOMBRE_FAMILIA FROM FAMILIA_MATERIAL WHERE ID_FAMILIA_MATERIAL= ".$id;
    $resPadres = $bd->ExecSQL($sqlPadres);
    $reg=$bd->SigReg($resPadres);
        if($reg){
        $vector[]=$reg->NOMBRE_FAMILIA;
        if($reg->ID_FAMILIA_MATERIAL_PADRE!=NULL){
            obtenerPadresFamilia($reg->ID_FAMILIA_MATERIAL_PADRE,$bd,$vector);
        }
    }
}

include $pathRaiz . "seguridad_admin.php";

$tituloPag         = $auxiliar->traduce("Tipos de Incidencias", $administrador->ID_IDIOMA);
$tituloNav         = $auxiliar->traduce("Maestros", $administrador->ID_IDIOMA) . " >> " . $auxiliar->traduce("Tipos de Incidencias", $administrador->ID_IDIOMA);
$ZonaTablaPadre    = "Maestros";
$ZonaTabla         = "MaestrosIncidenciaSistemaTipo";
$ZonaSubTablaPadre = "MaestrosSubmenuAplicacion";

// COMPRUEBA SI TIENE PERMISOS
if ($administrador->Hayar_Permiso_Perfil('ADM_MAESTROS_INCIDENCIA_SISTEMA_TIPO') < 1):
    $html->PagError("SinPermisos");
endif;

//OBTENGO EL REGISTRO E INICIALIZO VALORES DE LAS VARIABLES CON LO QUE HAY EN BASE DE DATOS
$nuevo=true;
if ($idMaterial != ""):

    // OBTENGO REGISTRO
    $nuevo=false;
    $sqlTipo = "SELECT * FROM MATERIAL M 
    JOIN FAMILIA_MATERIAL ON M.ID_FAMILIA_MATERIAL=FAMILIA_MATERIAL.ID_FAMILIA_MATERIAL
    JOIN FAMILIA_REPRO ON M.ID_FAMILIA_REPRO=FAMILIA_REPRO.ID_FAMILIA_REPRO
    JOIN  UNIDAD U ON U.ID_UNIDAD=M.ID_UNIDAD_COMPRA
    JOIN ADMINISTRADOR A ON M.ID_USUARIO_CREACION=A.ID_ADMINISTRADOR 
    JOIN ADMINISTRADOR AA ON M.ID_USUARIO_ULTIMA_MODIFICACION=AA.ID_ADMINISTRADOR WHERE REFERENCIA_SCS= '" . $bd->escapeCondicional($idMaterial) . "'";
    //var_dump($sqlTipo);

    $resTipo = $bd->ExecSQL($sqlTipo);
    $rowTipo = $bd->SigReg($resTipo);
    $isAGM=$rowTipo->MATERIAL_AGM;
    if($isAGM==1){
        $isAGM=true;
    }else{
        $isAGM=false;
    }

    $sqlBasico = "SELECT * FROM MATERIAL 
    WHERE REFERENCIA_SCS= '" . $bd->escapeCondicional($idMaterial) . "'";

    $resBasico = $bd->ExecSQL($sqlBasico);
    $rowBasico = $bd->SigReg($resBasico);


    $sqlUC = "SELECT * FROM UNIDAD U
    WHERE ID_UNIDAD= '" . $bd->escapeCondicional($rowBasico->ID_UNIDAD_COMPRA) . "'";

    $resUC = $bd->ExecSQL($sqlUC);
    $rowUC = $bd->SigReg($resUC);
   // var_dump($rowUC);
    //die;

    $sqlUM = "SELECT * FROM UNIDAD U
    WHERE ID_UNIDAD= '" . $bd->escapeCondicional($rowBasico->ID_UNIDAD_MEDIDA) . "'";

    $resUM = $bd->ExecSQL($sqlUM);
    $rowUM = $bd->SigReg($resUM);
    //var_dump($rowUM);
    //die;


    $txUnidadManipulacion=$rowTipo->UNIDAD ." ".$rowTipo->DESCRIPCION;

    $txMaterial=$rowTipo->REFERENCIA_SCS;
    $txDesc_esp=$rowBasico->DESCRIPCION_ESP;
    $txDesc_eng=$rowBasico->DESCRIPCION_ENG;
    $txFecha_creacion=$rowTipo->FECHA_CREACION;
    $idUsuario_creacion=$rowTipo->ID_ADMINISTRADOR;
    $txUsuario_creacion=$rowTipo->NOMBRE;
    $txFecha_ultima=$rowTipo->FECHA_ULTIMA_MODIFICACION;
    $txUsuario_ultimo=$rowTipo->NOMBRE;
    $txMarca=$rowTipo->MARCA;
    $txModelo=$rowTipo->MODELO;
    $txEstatus=$rowTipo->ESTATUS_MATERIAL;
    $txTipo=$rowTipo->TIPO_MATERIAL;
    $txObservaciones=$rowTipo->OBSERVACIONES;
    $txDivisibilidad=$rowTipo->DIVISIBILIDAD;
    $txDenominador=$rowTipo->DENOMINADOR;
    $txNumerador=$rowTipo->NUMERADOR;

    $idUnidadCompra=$rowTipo->ID_UNIDAD_COMPRA;

    $txUnidadCompra_ESP=$rowUC->UNIDAD_ESP.' - '.$rowUC->UNIDAD;
    //var_dump($txUnidadCompra_ESP);
    $txUnidadCompra_ENG=$rowUC->UNIDAD_ENG.' - '.$rowUC->UNIDAD;

    $idUnidadMedida=$rowTipo->ID_UNIDAD_MEDIDA;
    $txUnidadMedida_ESP=$rowUM->UNIDAD_ESP.' - '.$rowUM->UNIDAD;
    //var_dump($txUnidadMedida_ESP);
    $txUnidadMedida_ENG=$rowUM->UNIDAD_ENG.' - '.$rowUM->UNIDAD;

    $idFamiliaRepro=$rowTipo->ID_FAMILIA_REPRO;
    $txFamiliaRepro=$rowTipo->REFERENCIA . "- ".$rowTipo->FAMILIA_REPRO;
    $vector=array();
    //var_dump($idMaterial);
    obtenerPadresFamilia($rowTipo->ID_FAMILIA_MATERIAL,$bd,$vector);

    $idFamiliaMaterial=$rowTipo->ID_FAMILIA_MATERIAL;
    $txFamiliaMaterial=$rowTipo->NOMBRE_FAMILIA;
    $chBaja   = $rowTipo->BAJA;
    $accion = 'Modificar';
else:
    $accion = 'Insertar';
endif;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>

    <? require_once $pathClases . "lib/gral_js.php"; ?>
    <!-- BUSQUEDA AJAX -->
    <script src="<?= $pathClases; ?>lib/ajax_script/lib/prototype.js" type="text/javascript"></script>
    <script src="<?= $pathClases; ?>lib/ajax_script/src/scriptaculous.js" type="text/javascript"></script>
    <link rel="stylesheet" href="<?= $pathClases; ?>lib/ajax_script/style_ajax.css" type="text/css"/>
    <!-- FIN BUSQUEDA AJAX -->

    <script type="text/javascript">
        jQuery(document).ready(function () {
            jQuery('#botonVolver').focus();
        });
    </script>

    <script language="JavaScript" type="text/javascript">
        function grabar() {
            if (document.FormSelect.idMaterial.value != '') {
                document.FormSelect.accion.value = 'Modificar';
            } else {
                document.FormSelect.accion.value = 'Insertar';
            }

            this.disabled = true;

            document.FormSelect.submit();

            return false;
        }
    </script>




    <script language="JavaScript" type="text/javascript">
        function grabar_agm(idElemento) {
            if (document.FormSelect.idMaterial.value != '') {
                document.FormSelect.accion.value = 'Modificar_AGM';
                let form=document.getElementById('FormSelect');
                let cantidad=document.getElementById('txCantidad_'+idElemento).value;
                let inputId=document.createElement('input');
                inputId.type='hidden';
                inputId.name='id_componente_agm_grabar';
                inputId.value=idElemento;

                let inputCantidad=document.createElement('input');
                inputCantidad.type='hidden';
                inputCantidad.name='cantidad_agm';
                inputCantidad.value=cantidad;

                form.appendChild(inputId);
                form.appendChild(inputCantidad);

                //this.disabled = true;

                document.FormSelect.submit();

                return false;
            }
        }
    </script>



    <script language="JavaScript" type="text/javascript">
        function borrar_agm(idElemento) {
            if (document.FormSelect.idMaterial.value != '') {
                document.FormSelect.accion.value = 'Borrar_AGM';
                let form=document.getElementById('FormSelect');
                let cantidad=document.getElementById('txCantidad_'+idElemento).value;
                let inputId=document.createElement('input');
                inputId.type='hidden';
                inputId.name='id_componente_agm_grabar';
                inputId.value=idElemento;

                let inputCantidad=document.createElement('input');
                inputCantidad.type='hidden';
                inputCantidad.name='cantidad_agm';
                inputCantidad.value=cantidad;

                form.appendChild(inputId);
                form.appendChild(inputCantidad);

                //this.disabled = true;

                document.FormSelect.submit();

                return false;
            }
        }
    </script>

<!--
    <script language="JavaScript" type="text/javascript">
        function borrar() {
            if (document.FormSelect.idMaterial.value != '') {
                document.FormSelect.accion.value = 'Modificar';
            } else {
                document.FormSelect.accion.value = 'Insertar';
            }

            this.disabled = true;

            document.FormSelect.submit();

            return false;
        }
    </script>
    -->

<script>

    function mostrarAGMfunction () {
        let cboxAGM = document.getElementsByName('isAGM');
        if (cboxAGM[0].checked) {
            document.getElementById('btnAccionesLinea').style.display='block';
            document.getElementById('tablaAGM').style.display='table';
        }else{
            document.getElementById('btnAccionesLinea').style.display='none';
            document.getElementById('tablaAGM').style.display='none';
        }
    }
</script>
    <script>

        function acortar(cadena){
            let tieneNum=/\d/.test(cadena);
            if(tieneNum){
                return cadena.replace(/\D/g, '');
            }else{
                let palabras=cadena.split(' ');
                if(palabras.length>=2){
                    return palabras[0].charAt(0)+palabras[1].charAt(0);
                }else{
                    return '';
                }
            }
        }


        function cambiarValor(valorSelected){
            let valorAcortado=acortar(valorSelected);
            document.getElementsByName('acortarEstatus')[0].value=valorAcortado
        }



    </script>
    <script>
        function mostrarTablas(idboton){
            let tables=document.getElementsByTagName('table');
            let button=document.getElementById(idboton);
            for(let i=0;i<tables.length;i++){
                if(tables[i].id=='table1' || tables[i].id=='table2' ||tables[i].id=='table3'){
                    let display=tables[i].style.display;
                    if(tables[i].style.display==='none'){
                        tables[i].style.display='table';
                        button.style.backgroundColor='#1b6d85';
                    }else{
                        tables[i].style.display='none';
                        button.style.backgroundColor='grey';
                    }
                }

            }
        }

        function mostrarTabla(idtabla,idboton){
            let table=document.getElementById(idtabla);
            let button=document.getElementById(idboton);
            if(table.style.display==='none'){
                table.style.display='table';
                button.style.backgroundColor='#1b6d85';
            }else{
                table.style.display='none';
                button.style.backgroundColor='grey';
            }

        }



        function marcarDesmarcarCbox(){
            let checkboxes=document.querySelectorAll('input[type="checkbox"][id^="chboxAGMtable"]');
            let headerCheckbox=document.getElementById('chboxAllAGM');
            checkboxes.forEach(checkbox=>{
                checkbox.checked=headerCheckbox.checked;
            });
        }

        function borrar_agm_checked(id_hijo,id_padre){
            if (document.FormSelect.idMaterial.value != '') {
                document.FormSelect.accion.value = 'Borrar_AGM_checked';
                let form=document.getElementById('FormSelect');

                let inputIdHijo=document.createElement('input');
                inputIdHijo.type='hidden';
                inputIdHijo.name='id_hijo_checked';
                inputIdHijo.value=id_hijo;

                let inputIdPadre=document.createElement('input');
                inputIdPadre.type='hidden';
                inputIdPadre.name='id_padre_checked';
                inputIdPadre.value=id_padre;

                form.appendChild(inputIdHijo);
                form.appendChild(inputIdPadre);

                //this.disabled = true;

                document.FormSelect.submit();

                return false;
            }
        }

        function borrarChecked(){
            let checkboxes=document.querySelectorAll('input[type="checkbox"][id^="chboxAGMtable"]:checked');
            document.FormSelect.accion.value = 'Borrar_AGM_checked';
            let valores=[];
            checkboxes.forEach(checkbox=>{
                let partes=checkbox.id.split("/");
                let id=partes[0];
                let id_hijo=partes[1];
                let id_padre=partes[2];
                //borrar_agm_checked(partes[1],partes[2]);


                let form=document.getElementById('FormSelect');

                let inputIdHijo=document.createElement('input');
                inputIdHijo.type='hidden';
                inputIdHijo.name='id_hijo_checked'+id_hijo;
                inputIdHijo.value=id_hijo;

                let inputIdPadre=document.createElement('input');
                inputIdPadre.type='hidden';
                inputIdPadre.name='id_padre_checked'+id_hijo;
                inputIdPadre.value=id_padre;

                form.appendChild(inputIdHijo);
                form.appendChild(inputIdPadre);


            })
            document.FormSelect.submit();
        }
        function add_AGM(){
            if (document.FormSelect.idMaterial.value != '') {
                document.FormSelect.accion.value = 'Add_AGM';
                document.FormSelect.submit();
            }
        }


    </script>

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
    </script>

</head>
<body bgcolor="#FFFFFF" background="<? echo "$pathRaiz" ?>imagenes/fondo_pantalla.gif" leftmargin="0" topmargin="0"
      marginwidth="0" marginheight="0">
<FORM id="FormSelect" NAME="FormSelect" ACTION="accion.php" METHOD="POST">
    <input type=hidden name="accion" value="<?= $accion ?>">
    <input type="hidden" name="idMaterial" value="<? echo $idMaterial?>">
    <input type="hidden" name="idFamiliaMaterial" value="<? echo $idFamiliaMaterial ?>">
    <input type="hidden" name="idFamiliaRepro" value="<? echo $idFamiliaRepro ?>">
    <input type="hidden" name="idUnidadMedida" value="<? echo $idUnidadMedida ?>">
    <input type="hidden" name="idUnidadCompra" value="<? echo $idUnidadCompra ?>">
    <input type="hidden" name="idUsuario_creacion" value="<? echo $idUsuario_creacion ?>">
    <input type="hidden" name="incidenciaSistemaTipoEng" value="<? echo $txIncidenciaSistemaTipoEng ?>">
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
                                                            <td width="220" bgcolor="#982a29" class="lineabajoarriba"
                                                                colspan=2><font class="tituloNav"><? echo $tituloNav ?>
                                                                </font></td>
                                                            <td width="20" valign=top bgcolor="#B3C7DA"
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
                                    <td align="center" valign="top" bgcolor="#AACFF9" class="lineabajo">
                                        <button id="mostrar/ocultar" type="button" onclick="mostrarTablas('mostrar/ocultar')" style="background-color: #1b6d85; color: white">Mostrar/Ocultar Fichas</button>
                                        <button id="mostrar/ocultar1" type="button" onclick="mostrarTabla('table1','mostrar/ocultar1')" style="background-color: #1b6d85; color: white">Datos Esenciales</button>
                                        <button id="mostrar/ocultar2" type="button" onclick="mostrarTabla('table2','mostrar/ocultar2')" style="background-color: #1b6d85; color: white">Material</button>
                                        <button id="mostrar/ocultar3" type="button" onclick="mostrarTabla('table3','mostrar/ocultar3')" style="background-color: #1b6d85; color: white">AGM</button>
                                        <table width="100%" border="0" cellpadding="0" cellspacing="0">
                                            <table width="97%" border="0" align="center" cellpadding="0"
                                                   cellspacing="0">
                                                <caption></caption>
                                            <tr>
                                                <td width="20" align="center" valign="middle" class="lineaderecha">


                                                </td>
                                                <td align="center" valign="middle">
                                                    <table id="table1" width="97%" border="0" align="center" cellpadding="0"
                                                           cellspacing="0">
                                                        <tr bgcolor="#D9E3EC">
                                                            <td height="10" colspan="3" bgcolor="AACFF9"
                                                                class="linearribadereizq"><img
                                                                    src="<? echo $pathRaiz ?>imagenes/transparente.gif"
                                                                    width="10" height="10"></td>
                                                        </tr>
                                                        <tr bgcolor="#D9E3EC" class="lineabajodereizq">
                                                            <td width="5" bgcolor="d9e3ec" class="lineaizquierda">
                                                                &nbsp;
                                                            </td>
                                                            <td width="640" align="left" bgcolor="d9e3ec"> DATOS ESENCIALES
                                                                <table  width="750" border="0" cellspacing="0"
                                                                       cellpadding="1" class="tablaFiltros">

                                                                    <tr>
                                                                        <td align="center" width="5%"><img
                                                                                src="<? echo $pathRaiz ?>imagenes/diamante.gif"
                                                                                width="7" height="7"></td>
                                                                        <td align="left" class="textoazul"
                                                                            width="35%"><?= $auxiliar->traduce("Nº Material", $administrador->ID_IDIOMA) . ":" ?>
                                                                        </td>
                                                                        <td class="textoazul" width="60%">
                                                                            <?
                                                                            $TamanoText = "420px";
                                                                            $ClassText  = "copyright ObligatorioRellenar";
                                                                            $MaxLength  = "80";
                                                                            $html->TextBox("txMaterial", $txMaterial);
                                                                            ?>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td align="center" width="5%"><img
                                                                                src="<? echo $pathRaiz ?>imagenes/diamante.gif"
                                                                                width="7" height="7"></td>
                                                                        <td align="left" class="textoazul"
                                                                            width="35%"><?= $auxiliar->traduce("Descripción material", $administrador->ID_IDIOMA) . ":" ?>
                                                                        </td>
                                                                        <td class="textoazul" width="60%">
                                                                            <?
                                                                            $TamanoText = "420px";
                                                                            $ClassText  = "copyright ObligatorioRellenar";
                                                                            $MaxLength  = "255";
                                                                            $html->TextBox("txDesc_esp", $txDesc_esp);
                                                                            ?>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td align="center" width="5%"><img
                                                                                    src="<? echo $pathRaiz ?>imagenes/diamante.gif"
                                                                                    width="7" height="7"></td>
                                                                        <td align="left" class="textoazul"
                                                                            width="35%"><?= $auxiliar->traduce("Descripción material ingles", $administrador->ID_IDIOMA) . ":" ?>
                                                                        </td>
                                                                        <td class="textoazul" width="60%">
                                                                            <?
                                                                            $TamanoText = "420px";
                                                                            $ClassText  = "copyright ObligatorioRellenar";
                                                                            $MaxLength  = "255";
                                                                            $html->TextBox("txDesc_eng", $txDesc_eng);
                                                                            ?>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td align="center" width="5%"><img
                                                                                    src="<? echo $pathRaiz ?>imagenes/diamante.gif"
                                                                                    width="7" height="7"></td>
                                                                        <td align="left" class="textoazul"
                                                                            width="35%"><?= $auxiliar->traduce("Estatus material", $administrador->ID_IDIOMA) . ":" ?>
                                                                        </td>
                                                                        <td class="textoazul" width="60%">
                                                                            <?
                                                                            $TamanoText = "20px";
                                                                            $ClassText  = "copyright";
                                                                            $MaxLength  = "255";
                                                                            $readonly='readonly';
                                                                            $html->TextBox("acortarEstatus", acortar($txEstatus));
                                                                            unset($readonly);

                                                                            $NombreSelect = 'selEstatus';
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
                                                                            $Tamano = "205px";
                                                                            $Estilo = "copyright";

                                                                            $jscript = "onchange=cambiarValor(this.value)";
                                                                            $html->SelectArr($NombreSelect, $Elementos_estatus,$txEstatus);
                                                                            unset($jscript);
                                                                            ?>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td align="center" width="5%"><img
                                                                                    src="<? echo $pathRaiz ?>imagenes/diamante.gif"
                                                                                    width="7" height="7"></td>
                                                                        <td align="left" class="textoazul"
                                                                            width="35%"><?= $auxiliar->traduce("Tipo material", $administrador->ID_IDIOMA) . ":" ?>
                                                                        </td>
                                                                        <td class="textoazul" width="60%">
                                                                            <?
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
                                                                            $html->SelectArr($NombreSelect, $Elementos_tipo, $txTipo);
                                                                            ?>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <? if(!$nuevo): ?>
                                                                        <td align="center" width="5%"><img
                                                                                    src="<? echo $pathRaiz ?>imagenes/diamante.gif"
                                                                                    width="7" height="7"></td>
                                                                        <td align="left" class="textoazul"
                                                                            width="35%"><?= $auxiliar->traduce("Fecha Creación", $administrador->ID_IDIOMA) . ":" ?>
                                                                        </td>
                                                                        <td class="textoazul" width="60%">
                                                                            <label><?echo $txFecha_creacion?></label>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td align="center" width="5%"><img
                                                                                    src="<? echo $pathRaiz ?>imagenes/diamante.gif"
                                                                                    width="7" height="7"></td>
                                                                        <td align="left" class="textoazul"
                                                                            width="35%"><?= $auxiliar->traduce("Usuario Creación", $administrador->ID_IDIOMA) . ":" ?>
                                                                        </td>
                                                                        <td class="textoazul" width="60%">
                                                                        <label><?echo $txUsuario_creacion?></label>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td align="center" width="5%"><img
                                                                                    src="<? echo $pathRaiz ?>imagenes/diamante.gif"
                                                                                    width="7" height="7"></td>
                                                                        <td align="left" class="textoazul"
                                                                            width="35%"><?= $auxiliar->traduce("Fecha Última Modificación", $administrador->ID_IDIOMA) . ":" ?>
                                                                        </td>
                                                                        <td class="textoazul" width="60%">
                                                                           <label><?echo $txFecha_ultima?></label>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td align="center" width="5%"><img
                                                                                    src="<? echo $pathRaiz ?>imagenes/diamante.gif"
                                                                                    width="7" height="7"></td>
                                                                        <td align="left" class="textoazul"
                                                                            width="35%"><?= $auxiliar->traduce("Usuario Última Modificación", $administrador->ID_IDIOMA) . ":" ?>
                                                                        </td>
                                                                        <td class="textoazul" width="60%">
                                                                            <label><?echo $txUsuario_ultimo?></label>
                                                                        </td>
                                                                        <?endif;?>
                                                                    </tr>
                                                                    <tr>
                                                                        <td align="center" width="5%"><img
                                                                                src="<? echo $pathRaiz ?>imagenes/diamante.gif"
                                                                                width="7" height="7"></td>
                                                                        <td align="left" class="textoazul"
                                                                            width="35%"><?= $auxiliar->traduce("Baja", $administrador->ID_IDIOMA) . ":" ?>
                                                                        </td>

                                                                        <td class="textoazul" width="60%">
                                                                            <?
                                                                            $Estilo = 'check_estilo';
                                                                            $html->Option("chBaja","Check","1",$chBaja);
                                                                            ?>
                                                                        </td>
                                                                    </tr>
                                                                </table>
                                                            </td>
                                                            <td width="640" align="left" bgcolor="d9e3ec">
                                                                <table width="750" border="0" cellspacing="0"
                                                                       cellpadding="1" class="tablaFiltros">

                                                                    <tr> TAXONOMIA DE MATERIALES
                                                                        <? if(!$nuevo):
                                                                        pintar_arbol($vector);
                                                                        endif;?>
                                                                    </tr>
                                                                    <td align="center" width="5%"><img
                                                                                src="<? echo $pathRaiz ?>imagenes/diamante.gif"
                                                                                width="7" height="7"></td>
                                                                    <td align="left" class="textoazul"
                                                                        width="35%"><?= $auxiliar->traduce("Familia Material", $administrador->ID_IDIOMA) . ":" ?>
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
                                                                    <tr>
                                                                        <td align="center" width="5%"><img
                                                                                    src="<? echo $pathRaiz ?>imagenes/diamante.gif"
                                                                                    width="7" height="7"></td>
                                                                        <td align="left" class="textoazul"
                                                                            width="35%"><?= $auxiliar->traduce("Familia Repro", $administrador->ID_IDIOMA) . ":" ?>
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

                                                                </table>

                                                            </td>

                                                            <td class=lineaderecha width="3%" bgcolor=#AACFF9
                                                                align="right" valign="top">
                                                                <?
                                                                if ($rowTipo->ID_INCIDENCIA_SISTEMA_TIPO != ""):
                                                                    $jscript = "style='margin-right:5px;'";
                                                                    $html->VerHistorial('Maestro', $rowTipo->ID_INCIDENCIA_SISTEMA_TIPO, "Incidencia Sistema Tipo", "INCIDENCIA_SISTEMA_TIPO");
                                                                    unset($jscript);
                                                                endif;
                                                                ?>
                                                            </td>

                                                        <tr bgcolor="#D9E3EC">
                                                            <td height="10" colspan="3" bgcolor="#AACFF9"
                                                                class="lineabajodereizq"><img
                                                                    src="<? echo $pathRaiz ?>imagenes/transparente.gif"
                                                                    width="10" height="10"></td>
                                                        </tr>
                                                        <table id="table2" width="97%" align="center" border="0" cellspacing="0" cellpadding="0">
                                                            <tr bgcolor="#D9E3EC">
                                                                <td height="10" colspan="3" bgcolor="#AACFF9"
                                                                    class="lineabajodereizq"><img
                                                                            src="<? echo $pathRaiz ?>imagenes/transparente.gif"
                                                                            width="10" height="10"></td>
                                                            </tr>
                                                            <td width="5" bgcolor="d9e3ec" class="lineaizquierda">
                                                            </td>
                                                            <td width="640" align="left" bgcolor="d9e3ec">FICHA MATERIAL
                                                                <table  width="750" border="0" cellspacing="0"
                                                                       cellpadding="1" class="tablaFiltros">
                                                                    <tr>
                                                                        <td align="center" width="5%"></td>
                                                                        <td align="left" class="textoazul"
                                                                            width="35%"><?= $auxiliar->traduce("Marca", $administrador->ID_IDIOMA) . ":" ?>
                                                                        </td>
                                                                        <td class="textoazul" width="60%">
                                                                            <?
                                                                            $TamanoText = "420px";
                                                                            $ClassText  = "copyright";
                                                                            $MaxLength  = "80";
                                                                            $html->TextBox("txMarca", $txMarca);
                                                                            ?>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td align="center" width="5%"></td>
                                                                        <td align="left" class="textoazul"
                                                                            width="35%"><?= $auxiliar->traduce("Modelo", $administrador->ID_IDIOMA) . ":" ?>
                                                                        </td>
                                                                        <td class="textoazul" width="60%">
                                                                            <?
                                                                            $TamanoText = "420px";
                                                                            $ClassText  = "copyright";
                                                                            $MaxLength  = "255";
                                                                            $html->TextBox("txModelo", $txModelo);
                                                                            ?>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td align="center" width="5%"></td>
                                                                        <td align="left" class="textoazul"
                                                                            width="35%"><?= $auxiliar->traduce("Observaciones Material", $administrador->ID_IDIOMA) . ":" ?>
                                                                        </td>
                                                                        <td class="textoazul" width="60%">
                                                                            <?
                                                                            $TamanoText = "420px";
                                                                            $ClassText  = "copyright";
                                                                            $MaxLength  = "255";
                                                                            $html->TextArea("txObservaciones", $txObservaciones);
                                                                            ?>
                                                                        </td>
                                                                    <tr>

                                                                    </tr>
                                            </tr>

                                                <tr>

                                                </tr>
                                                <tr>

                                                </tr>
                                            </table>
                                    </td>
                                    <td width="640" align="left" bgcolor="d9e3ec">
                                        <table width="750" border="0" cellspacing="0"
                                               cellpadding="1" class="tablaFiltros">
                                            <tr>
                                                <td align="center" width="5%"><img
                                                            src="<? echo $pathRaiz ?>imagenes/diamante.gif"
                                                            width="7" height="7"></td>
                                                <td align="left" class="textoazul"
                                                    width="35%"><?= $auxiliar->traduce("Unidad de medida", $administrador->ID_IDIOMA) . ":" ?>
                                                </td>
                                                <td width="24%" align="left" valign="middle">
                                                    <?
                                                    $TamanoText = "200px";
                                                    $ClassText  = "copyright";
                                                    $MaxLength  = "50";
                                                    $jscript    = "onchange=\"document.FormSelect.idUnidadBase.value=''\"";
                                                    $idTextBox  = 'txUnidadBase';
                                                    if($administrador->ID_IDIOMA=='ESP'){
                                                        $html->TextBox("txUnidadBase", $txUnidadMedida_ESP);
                                                    }elseif($administrador->ID_IDIOMA=='ENG'){
                                                        $html->TextBox("txUnidadBase", $txUnidadMedida_ENG);
                                                    }
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
                                                <td align="center" width="5%"><img
                                                            src="<? echo $pathRaiz ?>imagenes/diamante.gif"
                                                            width="7" height="7"></td>
                                                <td align="left" class="textoazul"
                                                    width="35%"><?= $auxiliar->traduce("Unidad de compra", $administrador->ID_IDIOMA) . ":" ?>
                                                </td>
                                                <td width="24%" align="left" valign="middle">
                                                    <?
                                                    $TamanoText = "200px";
                                                    $ClassText  = "copyright";
                                                    $MaxLength  = "50";
                                                    $jscript    = "onchange=\"document.FormSelect.idUnidadCompra.value=''\"";
                                                    $idTextBox  = 'txUnidadCompra';
                                                    if($administrador->ID_IDIOMA=='ESP'){
                                                        $html->TextBox("txUnidadCompra", $txUnidadCompra_ESP);
                                                    }elseif($administrador->ID_IDIOMA=='ENG'){
                                                        $html->TextBox("txUnidadCompra", $txUnidadCompra_ENG);
                                                    }
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
                                                <td align="center" width="5%"><img
                                                            src="<? echo $pathRaiz ?>imagenes/diamante.gif"
                                                            width="7" height="7"></td>
                                                <td align="left" class="textoazul"
                                                    width="35%"><?= $auxiliar->traduce("Numerador conversión", $administrador->ID_IDIOMA) . ":" ?>
                                                </td>
                                                <td class="textoazul" width="60%">
                                                    <?
                                                    $TamanoText = "420px";
                                                    $ClassText  = "copyright ObligatorioRellenar";
                                                    $MaxLength  = "255";
                                                    $html->TextBox("txNumerador", $txNumerador);
                                                    ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td align="center" width="5%"><img
                                                            src="<? echo $pathRaiz ?>imagenes/diamante.gif"
                                                            width="7" height="7"></td>
                                                <td align="left" class="textoazul"
                                                    width="35%"><?= $auxiliar->traduce("Denominador conversión", $administrador->ID_IDIOMA) . ":" ?>
                                                </td>
                                                <td class="textoazul" width="60%">
                                                    <?
                                                    $TamanoText = "420px";
                                                    $ClassText  = "copyright ObligatorioRellenar";
                                                    $MaxLength  = "255";
                                                    $html->TextBox("txDenominador", $txDenominador);
                                                    ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td align="center" width="5%"></td>
                                                <td align="left" class="textoazul"
                                                    width="35%"><?= $auxiliar->traduce("Unidades de manipulacion", $administrador->ID_IDIOMA) . ":" ?>
                                                </td>
                                                <td class="textoazul" width="60%">
                                                    <?
                                                    $TamanoText = "100px";
                                                    $ClassText  = "copyright";
                                                    $readonly='readonly';
                                                    $html->TextBox("txUnidadManipulacion", $txUnidadManipulacion);
                                                    unset($readonly);
                                                    ?>
                                                    <?= $auxiliar->traduce("Divisibilidad", $administrador->ID_IDIOMA) . ":" ?>

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

                                                    $html->SelectArr($NombreSelect, $Elementos_divisibilidad, $txDivisibilidad);
                                                    ?>
                                                </td>

                                            </tr>
                                            <tr>

                                            </tr>


                                        </table>
                                    </td>
                                <tr bgcolor="#D9E3EC">
                                    <td height="10" colspan="3" bgcolor="#AACFF9"
                                        class="lineabajodereizq"><img
                                                src="<? echo $pathRaiz ?>imagenes/transparente.gif"
                                                width="10" height="10"></td>
                                </tr>


                                <table id="table3" width="97%" border="0" align="center" cellpadding="0"
                                       cellspacing="0">
                                    <tr bgcolor="#D9E3EC">
                                        <td height="10" colspan="3" bgcolor="AACFF9"
                                            class="linearribadereizq"><img
                                                    src="<? echo $pathRaiz ?>imagenes/transparente.gif"
                                                    width="10" height="10"></td>
                                    </tr>
                                    <tr bgcolor="#D9E3EC" class="lineabajodereizq">
                                        <tr></tr>
                                        <td align="left" class="textoazul"
                                            width="35%"><?= $auxiliar->traduce("Material AGM", $administrador->ID_IDIOMA) . ":" ?>

                                            <?
                                            $TamanoText = "420px";
                                            $ClassText  = "copyright ObligatorioRellenar";
                                            $MaxLength  = "80";

                                            // $jscript = "onchange=mostrar_tabla_agm()";
                                            $jscript = "onchange=mostrarAGMfunction()";
                                            $html->Option("isAGM",'Check',1, $isAGM,);
                                            unset($jscript)
                                            ?>
                                        <td> </td>
                                        <td> </td>
                                        <td> </td>
                                        <td> </td>
                                        <td> </td>
                                        <td> </td>
                                        <td> </td>
                                        <td> </td>
                                        <td> </td>
                                        <td> </td>
                                        <td> </td>
                                        <td> </td>
                                        <td> </td>
                                        <td> </td>
                                        <td> </td>
                                        <td> </td>
                                        <td>
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
                                                    <? echo $auxiliar->traduce("Carga Masiva AGM", $administrador->ID_IDIOMA) ?>
                                                    &nbsp;&nbsp;&nbsp;&nbsp;
                                                </a>&nbsp;
                                                <ul>
                                                    <li>
                                                        <a href="ficha_importacion_AGM_excel_paso1.php?idMaterial=<?= $txMaterial?>"
                                                           class="copyrightbotonesfancyboxImportacion">
                                                            <img
                                                                    src="<?= $pathRaiz ?>imagenes/edit_form.png"
                                                                    name="DeshacerAnulaciones"
                                                                    border="0"/>
                                                            &nbsp;&nbsp;&nbsp;
                                                            <?= $auxiliar->traduce("Importacion Masiva", $administrador->ID_IDIOMA) . "(Excel)" ?>
                                                            &nbsp;&nbsp;&nbsp;
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    <tr>

                                            <table  id="tablaAGM" width="100%" border="0" cellspacing="0"
                                                    bgcolor="d9e3ec" cellpadding="1" class="tablaFiltros">
                                                <tr><td width="640" align="left" bgcolor="d9e3ec"> FICHA AGM</td>
                                                    <td width="640" align="left" bgcolor="d9e3ec"></td>

                                                    <tr>

                                                            <tr> </tr>
                                                            <tr>
                                                                <tr>
                                                                <td bgcolor="d9e3ec"><label>Material:</label></td>

                                                                <td bgcolor="d9e3ec"><label>Cantidad:</label></td>
                                                                </tr>
                                                                <td bgcolor="d9e3ec"><input type="text" id="material" name="material"></td>
                                                                <td bgcolor="d9e3ec"><input type="text" id="cantidad" name="cantidad"></td>


                                                                <td bgcolor="d9e3ec"><button style="background-color: #1b6d85; color: whitesmoke" type="button" onclick="add_AGM()">Añadir material</button></td>
                                                                <td bgcolor="d9e3ec"><button style="background-color: #ac2925; color: whitesmoke" type="button" onclick="borrarChecked()">Anular líneas seleccionadas</button></td>

                                    </tr>
                                                </tr>

                                                </tr>
        <?if($isAGM):?>
                                                    <?
                                                    $vector=array();
                                                    obtenerHijosMateriales($txMaterial,$bd,$vector);
                                                    pintar_tabla_hijos($vector,"red",$bd,$administrador->ID_IDIOMA);
                                                    ?>
                                                </tr>
                                                <tr>

                                                </tr>
                                            </table>
    <?endif;?>
                            </table>

                    <tr height="25">
                        <td class="lineabajo" width="50%" align="left"><span class="textoazul">&nbsp;<a
                                        href="index.php?recordar_busqueda=1"
                                        class="senaladoazul">&nbsp;&nbsp;&nbsp;&nbsp;<?= $auxiliar->traduce("Volver", $administrador->ID_IDIOMA) ?>&nbsp;&nbsp;&nbsp;&nbsp;</a></span>
                        </td>
                        <td align="right" class="lineabajo"><span class="textoazul">
  							&nbsp;<a href="#" id="botonGrabar" class="senalado6" onclick="grabar()">&nbsp;&nbsp;&nbsp;&nbsp;<?= $auxiliar->traduce("Grabar", $administrador->ID_IDIOMA) ?>&nbsp;&nbsp;&nbsp;&nbsp;</a>&nbsp;</span>
                        </td>

                    </tr>
                                                    </table>
                                                </td>

                                                <td width="20" align="center" valign="middle" class="lineaizquierda">
                                                    &nbsp;
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>

                                    <td align="left" valign="top" bgcolor="#B3C7DA" class="lineabajo">


                                        <br><br>
                                    </td>
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
</FORM>
</body>
</html>
