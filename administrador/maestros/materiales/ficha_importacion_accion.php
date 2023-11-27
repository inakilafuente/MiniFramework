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
        $incidenciaSistemaTipo    = ${"txIncidenciaSistemaTipo_" . $linea};
        $incidenciaSistemaTipoEng  = ${"txIncidenciaSistemaTipoEng_" . $linea};
        $baja   = ${"txBaja_" . $linea};

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

        //COMPROBACION VALORES DUPLICADOS
        $rowRepetido = false;

        //PARSEO DEL CAMPO 'BAJA' A ENTERO
        if ($baja == '1'):
            $baja = 1;
        else:
            $baja = 0;
        endif;

        // COMPRUEBO NO CREADO OTRO CON IGUAL CAMPO
        $sql          = "SELECT ID_INCIDENCIA_SISTEMA_TIPO, COUNT(ID_INCIDENCIA_SISTEMA_TIPO) as NUM_REGS FROM INCIDENCIA_SISTEMA_TIPO WHERE INCIDENCIA_SISTEMA_TIPO='" . trim( (string)$bd->escapeCondicional($incidenciaSistemaTipo)) . "' AND INCIDENCIA_SISTEMA_TIPO_ENG='" . trim( (string)$bd->escapeCondicional($incidenciaSistemaTipoEng)) . "' AND BAJA='" . trim( (string)$bd->escapeCondicional($baja)) . "'";
        $resultNumero = $bd->ExecSQL($sql);
        $rowNumero    = $bd->SigReg($resultNumero);

        if ($rowNumero->NUM_REGS == 1):
            $rowRepetido = true;

            //SE OBTIENE EL CAMPO ANTIGUO
            $rowTipo = $bd->VerReg("INCIDENCIA_SISTEMA_TIPO", "ID_INCIDENCIA_SISTEMA_TIPO", $rowNumero->ID_INCIDENCIA_SISTEMA_TIPO);
        endif;
        //FIN COMPROBACION VALORES DUPLICADOS

        if ($rowRepetido == false):
            //ESTE CONDICIONAL SIRVE PARA DETERMINAR SI SE HA UTILIZADO LA IMPORTACIÓN 'COPIAR Y PEGAR'
            if (${"txCopiarPegar"} == '0'):
                $sqlInsert = "INSERT INTO INCIDENCIA_SISTEMA_TIPO
                              SET INCIDENCIA_SISTEMA_TIPO = '" . ($auxiliar->to_iso88591($bd->escapeCondicional($incidenciaSistemaTipo))) . "' ,
                                  INCIDENCIA_SISTEMA_TIPO_ENG  = '" . ($auxiliar->to_iso88591($bd->escapeCondicional($incidenciaSistemaTipoEng))) . "' ,
                                  BAJA = '" . ($bd->escapeCondicional($baja)) . "'";
            else:
                $sqlInsert = "INSERT INTO INCIDENCIA_SISTEMA_TIPO
                              SET INCIDENCIA_SISTEMA_TIPO = '" . ($bd->escapeCondicional($incidenciaSistemaTipo)) . "' ,
                                  INCIDENCIA_SISTEMA_TIPO_ENG  = '" . ($bd->escapeCondicional($incidenciaSistemaTipoEng)) . "' ,
                                  BAJA = '" . ($bd->escapeCondicional($baja)) . "'";
            endif;
            $bd->ExecSQL($sqlInsert);

            //OBTENGO ID CREADO
            $idTipo = $bd->IdAsignado();

            // LOG MOVIMIENTOS
            $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Creación", "Maestro", $idTipo, "Incidencia Sistema Tipo", "INCIDENCIA_SISTEMA_TIPO");
        else:
            //ESTE CONDICIONAL SIRVE PARA DETERMINAR SI SE HA UTILIZADO LA IMPORTACIÓN 'COPIAR Y PEGAR'
            if (${"txCopiarPegar"} == '0'):
            $sqlUpdate = "UPDATE INCIDENCIA_SISTEMA_TIPO
                          SET INCIDENCIA_SISTEMA_TIPO = '" . ($auxiliar->to_iso88591($bd->escapeCondicional($incidenciaSistemaTipo))) . "' ,
                              INCIDENCIA_SISTEMA_TIPO_ENG  = '" . ($auxiliar->to_iso88591($bd->escapeCondicional($incidenciaSistemaTipoEng))) . "' ,
                              BAJA = '" . ($bd->escapeCondicional($baja)) . "'
                          WHERE ID_INCIDENCIA_SISTEMA_TIPO = '" . $rowNumero->ID_INCIDENCIA_SISTEMA_TIPO . "'";
            else:
                $sqlUpdate = "UPDATE INCIDENCIA_SISTEMA_TIPO
                          SET INCIDENCIA_SISTEMA_TIPO = '" . ($bd->escapeCondicional($incidenciaSistemaTipo)) . "' ,
                              INCIDENCIA_SISTEMA_TIPO_ENG  = '" . ($bd->escapeCondicional($incidenciaSistemaTipoEng)) . "' ,
                              BAJA = '" . ($bd->escapeCondicional($baja)) . "'
                          WHERE ID_INCIDENCIA_SISTEMA_TIPO = '" . $rowNumero->ID_INCIDENCIA_SISTEMA_TIPO . "'";
            endif;
            $bd->ExecSQL($sqlUpdate);

            //SE OBTIENE EL CAMPO ACTUALIZADO
            $rowTipoActualizado = $bd->VerReg("INCIDENCIA_SISTEMA_TIPO", "ID_INCIDENCIA_SISTEMA_TIPO", $rowNumero->ID_INCIDENCIA_SISTEMA_TIPO);

            // LOG MOVIMIENTOS
            $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Maestro", $rowNumero->ID_INCIDENCIA_SISTEMA_TIPO, "Incidencia Sistema Tipo", "INCIDENCIA_SISTEMA_TIPO", $rowTipo, $rowTipoActualizado);

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