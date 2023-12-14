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
        $Desc_ESP_=${"Desc_ESP_" . $linea};
        $Desc_ENG_=${"Desc_ENG_" . $linea};
        $Marca_=${"Marca_" . $linea};
        $Modelo_=${"Modelo_" . $linea};
        $Estatus_Material_=${"Estatus_Material_" . $linea};
        $Tipo_Material_=${"Tipo_Material_" . $linea};
        $Familia_Material_=${"Familia_Material_". $linea};
        $Familia_Repro_=${"Familia_Material_". $linea};
        $Unidad_Medida=${"Unidad_Medida_". $linea};
        $Unidad_Compra=${"Unidad_Compra_". $linea};
        $Baja_=${"Baja_" . $linea};
        $Numerador_Conversion_=${"Numerador_Conversion_" . $linea};
        $Denominador_Conversion_=${"Denominador_Conversion_" . $linea};
        $Observaciones_=${"Observaciones_" . $linea};

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
        if ($Baja_ == 'Y' || $Baja_=='y' || $Baja_=='1'):
            $Baja_ = 1;
        else:
            $Baja_ = 0;
        endif;

        //COMPRUEBO NO CREADO OTRO CON IGUAL CAMPO
        $sql          = "SELECT REFERENCIA_SCS FROM MATERIAL WHERE REFERENCIA_SCS=" . trim( (string)$bd->escapeCondicional($NumMaterial_)) ;
        $resultNumero = $bd->ExecSQL($sql);
        $rowNumero    = $bd->SigReg($resultNumero);
        // COMPRUEBO ESTATUS EXISTE
        $sqlEstatus_Material          = "SELECT COLUMN_TYPE
                                            FROM INFORMATION_SCHEMA.COLUMNS
                                            WHERE TABLE_NAME='MATERIAL'
                                            AND COLUMN_NAME ='ESTATUS_MATERIAL'";
        $resultEstatus_Material = $bd->ExecSQL($sqlEstatus_Material);
        if($resultEstatus_Material->num_rows>0){
            $rowEstatus_Material   = $resultEstatus_Material->fetch_assoc();
            $lista_enum=explode(",",str_replace("'","",substr($rowEstatus_Material['COLUMN_TYPE'], 5,(strlen($rowEstatus_Material['COLUMN_TYPE'])-6))));
            $result = array_filter($lista_enum, function ($item) use ($Estatus_Material_) {
                if (stripos($item, $Estatus_Material_) !== false) {
                    return true;
                }
                return false;
            });
            if(count($result)<1){
                echo("Error el Estatus de Materiral no existe");
                die;
            }
        }else{
            echo("Error el Estatus de Materiral no existe");
            die;
        }

        //FIN COMPROBACION ESTATUS EXISTE

        // COMPRUEBO FAMILIA MATERIAL EXISTE
        $sqlFamilia_Material          = "SELECT ID_FAMILIA_MATERIAL
                                            FROM FAMILIA_MATERIAL
                                            WHERE ID_FAMILIA_MATERIAL like ".$Familia_Material_;

        $resultFamilia_Material = $bd->ExecSQL($sqlFamilia_Material);
        $rowFamilia_Material    = $bd->SigReg($resultFamilia_Material);
        if($rowFamilia_Material<1){
            $sqlFamilia_Material          = "SELECT ID_FAMILIA_MATERIAL
                                            FROM FAMILIA_MATERIAL
                                            WHERE NOMBRE_FAMILIA like ".$Familia_Material_;

            $resultFamilia_Material = $bd->ExecSQL($sqlFamilia_Material);
            $rowFamilia_Material    = $bd->SigReg($resultFamilia_Material);
            if($rowFamilia_Material<1){
                echo ("Error la Familia introducida no existe");
            }
        }

        //FIN COMPROBACION FAMILIA MATERIAL EXISTE

        // COMPRUEBO FAMILIA REPRO EXISTE
        $sqlFamilia_Material          = "SELECT ID_FAMILIA_REPRO
                                            FROM FAMILIA_REPRO
                                            WHERE ID_FAMILIA_REPRO like ".$Familia_Repro_;

        $resultFamilia_Material = $bd->ExecSQL($sqlFamilia_Material);
        $rowFamilia_Material    = $bd->SigReg($resultFamilia_Material);
        if($rowFamilia_Material<1){
            $sqlFamilia_Material          = "SELECT ID_FAMILIA_REPRO
                                            FROM FAMILIA_REPRO
                                            WHERE FAMILIA_REPRO like ".$Familia_Repro_;

            $resultFamilia_Material = $bd->ExecSQL($sqlFamilia_Material);
            $rowFamilia_Material    = $bd->SigReg($resultFamilia_Material);
            if($rowFamilia_Material<1){
                echo ("Error la Familia introducida no existe");
            }
        }

        //FIN COMPROBACION FAMILIA REPRO EXISTE


        // COMPRUEBO UNIDAD COMPRA EXISTE
        $sqlUnidadCompra          = "SELECT ID_UNIDAD
                                            FROM UNIDAD
                                            WHERE ES_UNIDAD_COMPRA =1 AND UNIDAD LIKE '".$Unidad_Compra."'";

        $resultUnidadCompra = $bd->ExecSQL($sqlUnidadCompra);
        $rowUnidadCompra    = $bd->SigReg($resultUnidadCompra);
        $Unidad_Compra=$rowUnidadCompra->ID_UNIDAD;
        if($rowUnidadCompra<1){
                echo ("Error la Unidad introducida no existe");

        }

        //FIN COMPROBACION UNIDAD MEDIDA EXISTE

        // COMPRUEBO UNIDAD COMPRA EXISTE
        $sqlUnidadMedida         = "SELECT ID_UNIDAD
                                            FROM UNIDAD
                                            WHERE ES_UNIDAD_MEDIDA =1 AND UNIDAD LIKE '".$Unidad_Medida."'";

        $resultUnidadMedida = $bd->ExecSQL($sqlUnidadMedida);
        $rowUnidadMedida    = $bd->SigReg($resultUnidadMedida);
        $Unidad_Medida=$rowUnidadMedida->ID_UNIDAD;
        if($rowUnidadMedida<1){
            echo ("Error la Unidad introducida no existe");

        }
        //FIN COMPROBACION UNIDAD MEDIDA EXISTE


        // COMPRUEBO TIPO MATERIAL EXISTE
        $sqlTipo_Material          = "SELECT COLUMN_TYPE
                                            FROM INFORMATION_SCHEMA.COLUMNS
                                            WHERE TABLE_NAME='MATERIAL'
                                            AND COLUMN_NAME ='TIPO_MATERIAL'";
        $resultTipo_Material = $bd->ExecSQL($sqlTipo_Material);
        if($resultTipo_Material->num_rows>0){
            $rowTipo_Material   = $resultTipo_Material->fetch_assoc();
            $lista_enum=explode(",",str_replace("'","",substr($rowTipo_Material['COLUMN_TYPE'], 5,(strlen($rowTipo_Material['COLUMN_TYPE'])-6))));
            if(!in_array($Tipo_Material_,$lista_enum)){
                echo("Error el Tipo de Materiral no existe");
                die;
            }
        }else{
            echo("Error el Tipo de Materiral no existe");
            die;
        }
        //FIN COMPROBACION TIPO MATERIAL EXISTE

        if ($rowNumero!=null):
            $rowRepetido = true;

            //SE OBTIENE EL CAMPO ANTIGUO
            $rowTipo = $bd->VerReg("MATERIAL", "REFERENCIA_SCS", $NumMaterial_);
        endif;
        //FIN COMPROBACION VALORES DUPLICADOS
        if ($rowRepetido == false):
                $sqlInsert = "INSERT INTO MATERIAL SET
                REFERENCIA_SAP='" . trim( (string)$bd->escapeCondicional($NumMaterial_)) . "'
                ,REFERENCIA_SCS='" . trim( (string)$bd->escapeCondicional($NumMaterial_)) . "'
                ,DESCRIPCION_ESP='" . trim( (string)$bd->escapeCondicional($Desc_ESP_)) . "'
                ,DESCRIPCION_ENG='" . trim( (string)$bd->escapeCondicional($Desc_ENG_)) . "'
                ,ESTATUS_MATERIAL='" . trim( (string)$bd->escapeCondicional($Estatus_Material_)) . "'
                ,TIPO_MATERIAL='" . trim( (string)$bd->escapeCondicional($Tipo_Material_)) . "'
                ,MARCA='" . trim( (string)$bd->escapeCondicional($Marca_)) . "'
                ,MODELO='" . trim( (string)$bd->escapeCondicional($Modelo_)) . "'
                ,FECHA_CREACION='" . date('Y-m-d H:i:s'). "'
                ,ID_USUARIO_CREACION='" . $administrador->ID_ADMINISTRADOR ."'
                ,ID_USUARIO_ULTIMA_MODIFICACION='" . $administrador->ID_ADMINISTRADOR ."'
                ,FECHA_ULTIMA_MODIFICACION='" . date('Y-m-d H:i:s'). "'
                ,ID_FAMILIA_MATERIAL='" . trim( (string)$bd->escapeCondicional($Familia_Material_)) . "'
                ,ID_FAMILIA_REPRO='" . trim( (string)$bd->escapeCondicional($Familia_Repro_)) . "'
                ,ID_UNIDAD_MEDIDA='" . trim( (string)$bd->escapeCondicional($Unidad_Medida)) . "'
                ,ID_UNIDAD_COMPRA='" . trim( (string)$bd->escapeCondicional($Unidad_Compra)) . "'
                ,NUMERADOR='" . trim( (string)$bd->escapeCondicional($Numerador_Conversion_)) . "'
                ,DENOMINADOR='" . trim( (string)$bd->escapeCondicional($Denominador_Conversion_)) . "'
                ,BAJA='" . $Baja_ . "'";
            $bd->ExecSQL($sqlInsert);

            //OBTENGO ID CREADO
            $idTipo = $bd->IdAsignado();

            // LOG MOVIMIENTOS
           // $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Creación", "Maestro", $idTipo, "Incidencia Sistema Tipo", "INCIDENCIA_SISTEMA_TIPO");
        else:
            //ESTE CONDICIONAL SIRVE PARA DETERMINAR SI SE HA UTILIZADO LA IMPORTACIÓN 'COPIAR Y PEGAR'
            $sqlUpdate = "UPDATE MATERIAL SET
                REFERENCIA_SAP='" . trim( (string)$bd->escapeCondicional($NumMaterial_)) . "'
                ,REFERENCIA_SCS='" . trim( (string)$bd->escapeCondicional($NumMaterial_)) . "'
                ,DESCRIPCION_ESP='" . trim( (string)$bd->escapeCondicional($Desc_ESP_)) . "'
                ,DESCRIPCION_ENG='" . trim( (string)$bd->escapeCondicional($Desc_ENG_)) . "'
                ,ESTATUS_MATERIAL='" . trim( (string)$bd->escapeCondicional($Estatus_Material_)) . "'
                ,TIPO_MATERIAL='" . trim( (string)$bd->escapeCondicional($Tipo_Material_)) . "'
                ,MARCA='" . trim( (string)$bd->escapeCondicional($Marca_)) . "'
                ,MODELO='" . trim( (string)$bd->escapeCondicional($Modelo_)) . "'
                ,FECHA_CREACION='" . date('Y-m-d H:i:s'). "'
                ,ID_USUARIO_CREACION='" . $administrador->ID_ADMINISTRADOR ."'
                ,ID_USUARIO_ULTIMA_MODIFICACION='" . $administrador->ID_ADMINISTRADOR ."'
                ,FECHA_ULTIMA_MODIFICACION='" . date('Y-m-d H:i:s'). "'
                ,ID_FAMILIA_MATERIAL='" . trim( (string)$bd->escapeCondicional($Familia_Material_)) . "'
                ,ID_FAMILIA_REPRO='" . trim( (string)$bd->escapeCondicional($Familia_Repro_)) . "'
                ,ID_UNIDAD_MEDIDA='" . trim( (string)$bd->escapeCondicional($Unidad_Medida)) . "'
                ,ID_UNIDAD_COMPRA='" . trim( (string)$bd->escapeCondicional($Unidad_Compra)) . "'
                ,NUMERADOR='" . trim( (string)$bd->escapeCondicional($Numerador_Conversion_)) . "'
                ,DENOMINADOR='" . trim( (string)$bd->escapeCondicional($Denominador_Conversion_)) . "'
                ,BAJA='" . $Baja_ . "' WHERE REFERENCIA_SCS=".$NumMaterial_;
            $bd->ExecSQL($sqlUpdate);

            //SE OBTIENE EL CAMPO ACTUALIZADO
           // $rowTipoActualizado = $bd->VerReg("INCIDENCIA_SISTEMA_TIPO", "ID_INCIDENCIA_SISTEMA_TIPO", $rowNumero->ID_INCIDENCIA_SISTEMA_TIPO);

            // LOG MOVIMIENTOS
           // $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Maestro", $rowNumero->ID_INCIDENCIA_SISTEMA_TIPO, "Incidencia Sistema Tipo", "INCIDENCIA_SISTEMA_TIPO", $rowTipo, $rowTipoActualizado);

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