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
require_once $pathClases . "lib/pedido.php";
require_once $pathClases . "lib/material.php";


session_start();
include $pathRaiz . "seguridad_admin.php";

$tituloPag         = $auxiliar->traduce("Tipos de Incidencias", $administrador->ID_IDIOMA);
$tituloNav         = $auxiliar->traduce("Maestros", $administrador->ID_IDIOMA) . " >> " . $auxiliar->traduce("Tipos de Incidencias", $administrador->ID_IDIOMA);
$ZonaTablaPadre    = "Maestros";
$ZonaSubTablaPadre = "MaestrosSubmenuAplicacion";
$ZonaTabla         = "MaestrosIncidenciaSistemaTipo";

// COMPRUEBA SI TIENE PERMISOS
if ($administrador->Hayar_Permiso_Perfil('ADM_MAESTROS_INCIDENCIA_SISTEMA_TIPO') < 2):
    $html->PagError("SinPermisos");
endif;

//DECLARO LA VARIABLE GROBAL DE MENSAJE DE ERRORES
global $strError;

//CREO LA TRANSACCION
$bd->begin_transaction();

//VARIABLE PARA SABER SI SE HAN PRODUCIDO ERRORES
$errorImportacionDatos = false;
//RECORRO LAS LINEAS
foreach ($_POST as $clave => $valor):
    if ((substr( (string) $clave, 0, 8) == 'chLinea_') && ($valor == 1)):

        //CALCULO EL NUMERO DE LINEA
        $linea = substr( (string) $clave, 8);
        //OBTENGO LOS DATOS DE LA FILA

        $NumMaterial_=${"NumMaterial_" . $linea};
        $Cantidad=${"Cantidad_" . $linea};
        var_dump($NumMaterial_,$Cantidad);
        /*
        //COMPROBACIONES DE DATOS OBLIGATORIOS RELLENADOS
        //INCIDENCIA SISTEMA TIPO
        if ($incidenciaSistemaTipo == ""):
            $html->PagError("ErrorIncidenciaSistemaTipoVacio");
        endif;

        //INCIDENCIA SISTEMA TIPO ENG
        if ($incidenciaSistemaTipoEng == ""):
            $html->PagError("ErrorIncidenciaSistemaTipoEngVacio");
        endif;

        //FIN COMPROBACIONES DE DATOS RELLENADOS
*/
        //COMPROBACION VALORES DUPLICADOS
        $rowRepetido = false;

        //PARSEO DEL CAMPO 'BAJA' A ENTERO
    /*
        if ($Baja_ == 'Y' || $Baja_=='y' || $Baja_=='1'):
            $Baja_ = 1;
        else:
            $Baja_ = 0;
        endif;
*/
        //COMPRUEBO NO CREADO OTRO CON IGUAL CAMPO
        $sql          = "SELECT REFERENCIA_SCS FROM MATERIAL WHERE REFERENCIA_SCS=" . trim( (string)$bd->escapeCondicional($NumMaterial_)) ;
        $resultNumero = $bd->ExecSQL($sql);
        $rowNumero    = $bd->SigReg($resultNumero);

        if ($rowNumero!=null):
            $rowRepetido = true;

            //SE OBTIENE EL CAMPO ANTIGUO
            $rowTipo = $bd->VerReg("MATERIAL", "REFERENCIA_SCS", $NumMaterial_);


            $sql_AGM          = "SELECT ID_MATERIAL_COMPONENTE_AGM FROM MATERIAL_COMPONENTE_AGM 
                      WHERE MATERIAL_AGM='" . $bd->escapeCondicional($idMaterial) . "' AND MATERIAL_COMPONENTE='" . $bd->escapeCondicional($NumMaterial_) . "'";

            $resultNumero_AGM = $bd->ExecSQL($sql_AGM);
            $rowNumero_AGM    = $bd->SigReg($resultNumero_AGM);
            if ($rowNumero_AGM!=null):
                $rowRepetido_AGM=true;
            endif;
        endif;

        //FIN COMPROBACION VALORES DUPLICADOS
        if ($rowRepetido == true && $rowRepetido_AGM == false):

                $sqlInsert = "INSERT INTO MATERIAL_COMPONENTE_AGM SET
                MATERIAL_AGM='" . trim( (string)$bd->escapeCondicional($idMaterial)) . "'
                ,MATERIAL_COMPONENTE='" . trim( (string)$bd->escapeCondicional($NumMaterial_)) . "'
                ,CANTIDAD='" . trim( (string)$bd->escapeCondicional($Cantidad)) . "'
                ,BAJA='" . 0 . "'";

            $bd->ExecSQL($sqlInsert);
var_dump($sqlInsert);
            //OBTENGO ID CREADO
            $idTipo = $bd->IdAsignado();

            // LOG MOVIMIENTOS
           // $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Creación", "Maestro", $idTipo, "Incidencia Sistema Tipo", "INCIDENCIA_SISTEMA_TIPO");
        elseif($rowRepetido == true && $rowRepetido_AGM == true):
            //ESTE CONDICIONAL SIRVE PARA DETERMINAR SI SE HA UTILIZADO LA IMPORTACIÓN 'COPIAR Y PEGAR'
            $sqlUpdate = "UPDATE MATERIAL_COMPONENTE_AGM  SET
                CANTIDAD='" . trim( (string)$bd->escapeCondicional($Cantidad)) . "'
                WHERE MATERIAL_AGM='" . $bd->escapeCondicional($idMaterial) . "' AND MATERIAL_COMPONENTE='" . $bd->escapeCondicional($NumMaterial_) . "'";
        $bd->ExecSQL($sqlUpdate);

            //SE OBTIENE EL CAMPO ACTUALIZADO
           // $rowTipoActualizado = $bd->VerReg("INCIDENCIA_SISTEMA_TIPO", "ID_INCIDENCIA_SISTEMA_TIPO", $rowNumero->ID_INCIDENCIA_SISTEMA_TIPO);

            // LOG MOVIMIENTOS
           // $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Maestro", $rowNumero->ID_INCIDENCIA_SISTEMA_TIPO, "Incidencia Sistema Tipo", "INCIDENCIA_SISTEMA_TIPO", $rowTipo, $rowTipoActualizado);
        else:
            $errorImportacionDatos=true;
        endif; //FIN SI CAMPO YA EXISTE/NO EXISTE
    endif; //FIN CHECK MARCADO
endforeach; //BUCLE CHECKS MARCADOS
//SI SE HAN PRODUCIDO ERRORES, DESHAGO LA TRANSACCION Y MUESTRO LOS ERRORES
if ($errorImportacionDatos == true):
    $bd->rollback_transaction();
    $html->PagError("ErrorArchivoImportado");
endif;

//SI LAS OPERACIONES SE HAN REALIZADO DE FORMA CORRECTA, HAGO EL COMMIT DE LA TRANSACCION Y REDIRECCIONN

if ($errorImportacionDatos == false):
    $bd->commit_transaction();
endif;
?>
<script>
    //RECARGO LA PÁGINA Y CIERRO EL FANCYBOX
    window.parent.location.href = 'index.php?recordar_busqueda=1';
    window.parent.jQuery.fancybox.close();
</script>