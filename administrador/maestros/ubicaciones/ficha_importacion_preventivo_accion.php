<?
//print_r($_REQUEST);
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

$tituloPag         = $auxiliar->traduce("Ubicaciones", $administrador->ID_IDIOMA);
$tituloNav         = $auxiliar->traduce("Maestros", $administrador->ID_IDIOMA) . " >> " . $auxiliar->traduce("Ubicaciones", $administrador->ID_IDIOMA);
$ZonaTablaPadre    = "Maestros";
$ZonaSubTablaPadre = "MaestrosSubmenuAlmacen";
$ZonaTabla         = "MaestrosUbicaciones";

// COMPRUEBA SI TIENE PERMISOS
if ($administrador->Hayar_Permiso_Perfil('ADM_MAESTROS_UBICACIONES') < 2):
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

    if ((substr($clave, 0, 8) == 'chLinea_') && ($valor == 1)):

        //CALCULO EL NUMERO DE LINEA
        $linea = substr($clave, 8);

        //OBTENGO LOS DATOS DE LA FILA
        $refUbicacion = ${"txReferenciaUbicacion_" . $linea};
        $refCentro    = ${"txReferenciaCentro_" . $linea};
        $refAlmacen   = ${"txReferenciaAlmacen_" . $linea};

        //PREPARACION DE ALGUNOS DATOS
        $precioFijo   = "NO";
        $baja         = "NO";
        $esTipoSector = "NO";

        //COMPROBACIONES DE DATOS RELLENADOS
        //ref ubicacion
        if ($refUbicacion == ""):
            $html->PagError("ErrorRefUbicacionVacia");
        endif;

        //ref centro
        if ($refCentro == ""):
            $html->PagError("ErrorRefCentroVacia");
        endif;

        //ref almacen
        if ($refAlmacen == ""):
            $html->PagError("ErrorRefAlmacenVacia");
        endif;
        //FIN COMPROBACIONES DE DATOS RELLENADOS

        //OBTENGO DATOS NECESARIOS
        //centro
        $rowCentro = false;
        if ($refCentro != ""):
            $NotificaErrorPorEmail = "No";
            $rowCentro             = $bd->VerReg("CENTRO", "REFERENCIA", $refCentro, "No");
            unset($NotificaErrorPorEmail);
            if (!$rowCentro):
                $html->PagError("ErrorCentroNoEncontrado");
            endif;
        endif;

        //almacen
        $rowAlmacen = false;
        if ($refAlmacen != "" && $rowCentro):
            $NotificaErrorPorEmail = "No";
            $rowAlmacen            = $bd->VerRegRest("ALMACEN", "REFERENCIA = '" . $refAlmacen . "' AND ID_CENTRO = '" . $rowCentro->ID_CENTRO . "' AND TIPO_ALMACEN='acciona' ", "No");
            unset($NotificaErrorPorEmail);
            if (!$rowAlmacen):
                $html->PagError("ErrorAlmacenNoEncontrado");
            endif;
        endif;

        //ubicacion
        $rowUbicacion = false;
        if ($refUbicacion != "" && $rowAlmacen):
            $NotificaErrorPorEmail = "No";
            $rowUbicacion          = $bd->VerRegRest("UBICACION", "UBICACION = '" . $refUbicacion . "' AND ID_ALMACEN = '" . $rowAlmacen->ID_ALMACEN . "' AND TIPO_UBICACION = 'Preventivo'", "No");
            unset($NotificaErrorPorEmail);
        endif;
        //FIN OBTENGO DATOS NECESARIOS

        //COMPROBACION DE DATOS VALIDOS

        //almacen
        //debe tener permisos sobre el almacen
        if ($rowAlmacen && !$administrador->comprobarAlmacenPermiso($rowAlmacen->ID_ALMACEN, 'Escritura')):
            $html->PagError("SinPermisosSubzona");
        endif;

        //FIN COMPROBACION DE DATOS VALIDOS

        if ($rowUbicacion == false):
            $sqlInsert = "INSERT INTO UBICACION
                          SET UBICACION = '" . ($bd->escapeCondicional($refUbicacion)) . "' ,
                              ID_ALMACEN  = $rowAlmacen->ID_ALMACEN,
                              TIPO_UBICACION = 'Preventivo',
                              TIPO_PREVENTIVO = 'Pendientes'";
            $bd->ExecSQL($sqlInsert);
            $idUbicacion = $bd->IdAsignado();

            // LOG MOVIMIENTOS
            $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Creación", "Maestro", $idUbicacion, "Ubicación", "UBICACION");
        else:
            //SE COMPRUEBA SI EL TIPO DE UBICACION A INSERTAR ES EL MISMO QUE EL TIPO DE UBICACION ALMACENADA
            if ($rowUbicacion->TIPO_UBICACION == 'Preventivo'):
                $sql = "UPDATE UBICACION
                        SET ID_ALMACEN  = $rowAlmacen->ID_ALMACEN,
                            TIPO_PREVENTIVO = 'Pendientes',
                            BAJA = 0
                        WHERE ID_UBICACION = '" . $rowUbicacion->ID_UBICACION . "'";
            else:
                $sql = "INSERT INTO UBICACION
                        SET UBICACION = '" . ($bd->escapeCondicional($refUbicacion)) . "' ,
                            ID_ALMACEN  = $rowAlmacen->ID_ALMACEN,
                            TIPO_UBICACION = 'Preventivo',
                            TIPO_PREVENTIVO = 'Pendientes'";
            endif;

            $bd->ExecSQL($sql);

            //SE OBTIENE LA UBICACION ACTUALIZADA
            $rowUbicacionActualizada = $bd->VerReg("UBICACION", "ID_UBICACION", $rowUbicacion->ID_UBICACION);

            // LOG MOVIMIENTOS
            $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Maestro", $rowUbicacion->ID_UBICACION, "Ubicación", "UBICACION", $rowUbicacion, $rowUbicacionActualizada);

        endif;
        //FIN SI MAQUINA YA EXISTE/NO EXISTE

    endif; //FIN CHECK MARCADO
endforeach; //BUCLE CHECKS MARCADOS


//SI SE HAN PRODUCIDO ERRORES, DESHAGO LA TRANSACCION Y MUESTRO LOS ERRORES
if ($errorImportacionDatos == true):
    $bd->rollback_transaction();
    $html->PagError("ErrorCrearRecepcionArchivoImportado");
endif;

//SI LAS OPERACIONES SE HAN REALIZADO DE FORMA CORRECTA, HAGO EL COMMIT DE LA TRANSACCION Y REDIRECCIONN
if ($errorImportacionDatos == false):
    $bd->commit_transaction();

    header("location: index.php?recordar_busqueda=1");
    exit;
endif;