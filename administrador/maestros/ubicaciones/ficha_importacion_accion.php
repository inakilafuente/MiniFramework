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

    if ((substr( (string) $clave, 0, 8) == 'chLinea_') && ($valor == 1)):

        //CALCULO EL NUMERO DE LINEA
        $linea = substr( (string) $clave, 8);

        //OBTENGO LOS DATOS DE LA FILA
        $refUbicacion    = ${"txReferenciaUbicacion_" . $linea};
        $refCentro       = ${"txReferenciaCentro_" . $linea};
        $refAlmacen      = ${"txReferenciaAlmacen_" . $linea};
        $categoria       = ${"txCategoria_" . $linea};
        $descripcion     = ${"txDescripcion_" . $linea};
        $precioFijo      = ${"txPrecioFijo_" . $linea};
        $autostore       = ${"txAutostore_" . $linea};
        $baja            = ${"txBaja_" . $linea};
        $esTipoSector    = ${"txEsTipoSector_" . $linea};
        $refTipoSector   = ${"txRefTipoSector_" . $linea};
        $cantidadPaneles = ${"txCantidadPaneles_" . $linea};

        //PREPARACION DE ALGUNOS DATOS
        if ($precioFijo == ""): $precioFijo = "NO"; endif;
        if ($autostore == ""): $autostore = "NO"; endif;
        if ($baja == ""): $baja = "NO"; endif;
        if ($esTipoSector == ""): $esTipoSector = "NO"; endif;

        //COMPROBACIONES DE DATOS RELLENADOS
        //ref ubicacion
        if ($refUbicacion == ""):
            $html->PagError("ErrorRefUbicacionVacia");
        endif;

        //ref centro
        if ($refCentro == ""):
            $html->PagError("ErrorRefCentroVacia");
        endif;

        //ref albacen
        if ($refAlmacen == ""):
            $html->PagError("ErrorRefAlmacenVacia");
        endif;

        $idTipoSector = "";
        $filas        = 0;
        $columnas     = 0;
        //SI ES_TIPO_SECTOR == "SI", EL CAMPO ID_TIPO_SECTOR DEBE ESTAR RELLENADO
        if ($esTipoSector == "SI"):
            if ($refTipoSector == ""):
                $html->PagError("ErrorRefTipoSectorVacia");
            else:
                //SE COMPRUEBA SI EL ID_TIPO_SECTOR PERTENECE A UN SECTOR
                $NotificaErrorPorEmail = "No";
                $rowTipoSector         = $bd->VerReg("TIPO_SECTOR", "ID_TIPO_SECTOR", $refTipoSector, "No");
                unset($NotificaErrorPorEmail);
                if ($rowTipoSector == false):
                    $html->PagError("ErrorRefTipoSectorIncorrecta");
                else:
                    $idTipoSector = $rowTipoSector->ID_TIPO_SECTOR;
                    $filas        = $rowTipoSector->FILAS;
                    $columnas     = $rowTipoSector->COLUMNAS;
                endif;
            endif;
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
            $rowUbicacion          = $bd->VerRegRest("UBICACION", "UBICACION = '" . $refUbicacion . "' AND ID_ALMACEN = '" . $rowAlmacen->ID_ALMACEN . "'", "No");
            unset($NotificaErrorPorEmail);
        endif;

        //categoria
        $rowCategoria = false;
        if ($categoria != ""):
            $NotificaErrorPorEmail = "No";
            $rowCategoria          = $bd->VerReg("UBICACION_CATEGORIA", "ID_UBICACION_CATEGORIA", (int)$categoria, "No");
            unset($NotificaErrorPorEmail);
            if (!$rowCategoria):
                $html->PagError("ErrorCategoriaNoEncontrado");
            endif;
        endif;
        //FIN OBTENGO DATOS NECESARIOS

        //COMPROBACION DE DATOS VALIDOS
        //ubicacion
        //si existe la ubicacion, el tipo ha de ser NULL
        //si se quiere dar de baja, ha de estar vacía
        if ($rowUbicacion):
            //tipo
//            if (($rowUbicacion->TIPO_UBICACION != NULL) && ($esTipoSector == 'NO')):
//                $html->PagError("ErrorUbicacionNoEstandar");
//            endif;
            if (($rowUbicacion->TIPO_UBICACION != 'Sector') && ($esTipoSector == 'SI')):
                $html->PagError("ErrorUbicacionNoSector");
            endif;
            //stock
            if ($baja == "SI"):
                $NotificaErrorPorEmail = "No";
                $rowStock              = $bd->VerRegRest("MATERIAL_UBICACION", "ID_UBICACION = '" . $rowUbicacion->ID_UBICACION . "' AND STOCK_TOTAL >= 0", "No");
                unset($NotificaErrorPorEmail);
                if ($rowStock):
                    $html->PagError("ErrorUbicacionConStock");
                endif;
            endif;
        endif;

        //almacen
        //debe tener permisos sobre el almacen
        if ($rowAlmacen && !$administrador->comprobarAlmacenPermiso($rowAlmacen->ID_ALMACEN, 'Escritura')):
            $html->PagError("SinPermisosSubzona");
        endif;

        //precio fijo
        if ($precioFijo != "SI" && $precioFijo != "NO"):
            $html->PagError("ErrorPrecioFijoNoValido");
        endif;

        //autostore
        if ($autostore != "SI" && $autostore != "NO"):
            $html->PagError("ErrorAutostoreNoValido");
        endif;

        //baja
        if ($baja != "SI" && $baja != "NO"):
            $html->PagError("ErrorBajaNoValido");
        endif;

        //tipo sector
        if ($esTipoSector != "SI" && $esTipoSector != "NO"):
            $html->PagError("ErrorTipoSectorNoValido");
        endif;

        //Cantidad Paneles
        if ($esTipoSector == 'SI'):
            $esNumerico = is_numeric($cantidadPaneles) ? intval(0 + $cantidadPaneles) == $cantidadPaneles : false;
            $html->PagErrorCondicionado($esNumerico, "==", false, "ErrorCantidadPanelesNoValido");
        endif;
        //FIN COMPROBACION DE DATOS VALIDOS


        //SI UBICACION EXISTE/NO EXISTE
        if ($rowUbicacion): //EXISTE, LO MODIFICO
            $sqlUpdate = "UPDATE UBICACION SET
                              ID_UBICACION_CATEGORIA = " . ($bd->escapeCondicional($categoria == '' ? 'NULL' : $categoria)) . "
                              , DESCRIPCION = '" . $bd->escapeCondicional($descripcion) . "'
                              , PRECIO_FIJO = '" . ($precioFijo == 'SI' ? '1' : '0') . "'
                              , AUTOSTORE = '" . ($autostore == 'SI' ? '1' : '0') . "'
                              , BAJA = '" . ($baja == 'SI' ? '1' : '0') . "'
                              , TIPO_UBICACION = " . ($esTipoSector == 'SI' ? "'Sector'" : "NULL") . "
                              , ID_TIPO_SECTOR = " . ($esTipoSector == 'SI' ? $idTipoSector : "NULL") . "
                              , CANTIDAD_PANELES = " . ($esTipoSector == 'SI' ? $cantidadPaneles : 0) . "
                              WHERE ID_UBICACION = '" . $rowUbicacion->ID_UBICACION . "'";
            $bd->ExecSQL($sqlUpdate);

            // LOG MOVIMIENTOS
            $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Maestro", $rowUbicacion->ID_UBICACION, "UBICACION");

        else: //NO EXISTE LO CREO
            $sqlInsert = "INSERT INTO UBICACION SET
                               UBICACION = '" . ($bd->escapeCondicional($refUbicacion)) . "'
                              , ID_ALMACEN  = " . $rowAlmacen->ID_ALMACEN . "
                              , ID_UBICACION_CATEGORIA = " . ($bd->escapeCondicional($categoria == '' ? 'NULL' : $categoria)) . "
                              , DESCRIPCION = '" . $bd->escapeCondicional($descripcion) . "'
                              , PRECIO_FIJO = '" . ($precioFijo == 'SI' ? '1' : '0') . "'
                              , AUTOSTORE = '" . ($autostore == 'SI' ? '1' : '0') . "'
                              , BAJA = '" . ($baja == 'SI' ? '1' : '0') . "'
                              , TIPO_UBICACION = " . ($esTipoSector == 'SI' ? "'Sector'" : "NULL") . "
                              , ID_TIPO_SECTOR = " . ($esTipoSector == 'SI' ? $idTipoSector : "NULL") . "
                              , CANTIDAD_PANELES = " . ($esTipoSector == 'SI' ? $cantidadPaneles : 0);
            $bd->ExecSQL($sqlInsert);
            $idUbicacion = $bd->IdAsignado();

            // LOG MOVIMIENTOS
            $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Creación", "Maestro", $idUbicacion, "UBICACION");
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