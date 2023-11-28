<? //print_r($_REQUEST);echo("<hr>");
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

session_start();
include $pathRaiz . "seguridad_admin.php";

//VARIABLE SEGUN DESDE DONDE SE ACCEDA
if ($pantallaSolar == 1):
    $tituloPag = $auxiliar->traduce("Unidad Organizativa", $administrador->ID_IDIOMA);
    $tituloNav = $auxiliar->traduce("Construccion Solar", $administrador->ID_IDIOMA) . " >> " . $tituloPag;

    $ZonaTablaPadre    = "ConstruccionSolar";
    $ZonaSubTablaPadre = "ConstruccionSolarMenuEstructura";
    $ZonaTabla         = "ConstruccionSolarUnidadOrganizativa";
    $PaginaRecordar    = "ListadoConstruccionSolarUnidadOrganizativa";

    // COMPRUEBA SI TIENE PERMISOS
    if ($administrador->Hayar_Permiso_Perfil('ADM_CONSTRUCCION_SOLAR_UNIDAD_ORGANIZATIVA') < 1):
        $html->PagError("SinPermisos");
    endif;

elseif ($pantallaConstruccion == 1):

    $tituloPag = $auxiliar->traduce("Unidad Organizativa", $administrador->ID_IDIOMA);
    $tituloNav = $auxiliar->traduce("Construccion", $administrador->ID_IDIOMA) . " >> " . $tituloPag;

    $ZonaTablaPadre    = "Construccion";
    $ZonaSubTablaPadre = "ConstruccionMenuEstructura";
    $ZonaTabla         = "ConstruccionUnidadOrganizativa";
    $PaginaRecordar    = "ListadoConstruccionUnidadOrganizativa";

    // COMPRUEBA SI TIENE PERMISOS
    if ($administrador->Hayar_Permiso_Perfil('ADM_CONSTRUCCION_UNIDAD_ORGANIZATIVA') < 1):
        $html->PagError("SinPermisos");
    endif;

else:
    $tituloPag         = $auxiliar->traduce("Ubicaciones", $administrador->ID_IDIOMA);
    $tituloNav         = $auxiliar->traduce("Maestros", $administrador->ID_IDIOMA) . " >> " . $auxiliar->traduce("Ubicaciones", $administrador->ID_IDIOMA);
    $ZonaTablaPadre    = "Maestros";
    $ZonaSubTablaPadre = "MaestrosSubmenuAlmacen";
    $ZonaTabla         = "MaestrosUbicaciones";
    $PaginaRecordar    = "ListadoMaestrosUbicaciones";
    // COMPRUEBA SI TIENE PERMISOS
    if ($administrador->Hayar_Permiso_Perfil('ADM_MAESTROS_UBICACIONES') < 1):
        $html->PagError("SinPermisos");
    endif;
endif;


// RECUERDO DE BUSQUEDAS REALIZADAS
include $pathRaiz . "busqueda_recordar.php";

// CONTROLO EL CAMBIO DEL LIMITE
if (!Empty($CambiarLimite)):
    $navegar->maxfilasMaestroUbicaciones = $selLimite;
endif;

// ORDENACION DE COLUMNAS
$columnas_ord["almacen"]        = "REF_ALMACEN";
$columnas_ord["centro_fisico"]  = "REFERENCIA_CENTRO_FISICO";
$columnas_ord["centro"]         = "REF_CENTRO";
$columnas_ord["id_ubicacion"]   = "U.ID_UBICACION";
$columnas_ord["ubicacion"]      = "U.UBICACION";
$columnas_ord["tipo_ubicacion"] = "U.TIPO_UBICACION";
$columnas_ord["clase_apq"]      = "U.CLASE_APQ";
$columnas_ord["precio_fijo"]    = "U.PRECIO_FIJO";
$columnas_ord["baja"]           = "U.BAJA";
$columnas_ord["descripcion"]    = "U.DESCRIPCION";
$columnas_ord["autostore"]    = "U.AUTOSTORE";
$columna_defecto                = "material";
$sentido_defecto                = "0"; //ASCENDENTE
$navegar->DefinirColumnasOrdenacion($columnas_ord, $columna_defecto, $sentido_defecto);

//PARA INICIAR EL WHERE
$sqlUbicacion = "WHERE 1=1 AND (TIPO_UBICACION<>'Vehículo' OR TIPO_UBICACION IS NULL) ";

//MOSTRAMOS DEPENDIENDO DE LA PANTALLA

if ($pantallaSolar == 1):
    //PONEMOS EL FILTRO
    $sqlUbicacion .= " AND CF.TIPO_CONSTRUCCION = 'Fotovoltaico'";
    $selTipoUbicacion = "Power Block";

    //BUSCAMOS AL ENTRAR EN LA PANTALLA
    $Buscar = "Si";

elseif ($pantallaConstruccion == 1):
    //PONEMOS EL FILTRO
    $sqlUbicacion .= " AND CF.TIPO_CONSTRUCCION = 'Eolico'";
    $selTipoUbicacion = "Maquina";

    //BUSCAMOS AL ENTRAR EN LA PANTALLA
    $Buscar = "Si";
endif;

//POSIBLES JOIN NECESARIOS
$joinContenedor = "";
$joinRuta       = "";
//$joinSubruta    = "";

if ((trim( (string)$txRuta) != "") || (trim( (string)$txSubruta) != "")):
    $joinContenedor = "INNER JOIN CONTENEDOR CONT ON CONT.ID_ALMACEN = U.ID_ALMACEN AND CONT.ID_UBICACION = U.ID_UBICACION";
endif;

//UBICACION
if (trim( (string)$txUbicacion) != ""):
    $camposBD     = array('U.UBICACION');
    $sqlUbicacion = $sqlUbicacion . ($bd->busquedaTextoArrayExacta($txUbicacion, $camposBD));
    $textoLista   = $textoLista . "&" . $auxiliar->traduce("Ubicación", $administrador->ID_IDIOMA) . ": " . $txUbicacion;
endif;

//ID UBICACION
if (trim( (string)$txIdUbicacion) != ""):
    $camposBD     = array('U.ID_UBICACION');
    $sqlUbicacion = $sqlUbicacion . ($bd->busquedaNumero($txIdUbicacion, "U.ID_UBICACION"));
    $textoLista   = $textoLista . "&" . $auxiliar->traduce("Id. ubicación", $administrador->ID_IDIOMA) . ": " . $txIdUbicacion;
endif;

//DESCRIPCION
if (trim( (string)$txDescripcion) != ""):
    $sqlUbicacion = $sqlUbicacion . ($bd->busquedaTexto($txDescripcion, 'U.DESCRIPCION'));
    $textoLista   = $textoLista . "&" . $auxiliar->traduce("Descripción", $administrador->ID_IDIOMA . ": ") . $txDescripcion;
endif;

//ALMACÉN
$administrador->precargarValorDefectoSiNecesario("ALMACEN", $idAlmacen, $txAlmacen);
if ($idAlmacen != "" || trim( (string)$txAlmacen) != ""):
    if ($idAlmacen != ""):
        $sqlUbicacion = $sqlUbicacion . ($bd->busquedaNumero($idAlmacen, 'A.ID_ALMACEN'));
    else:
        $camposBD     = array('A.REFERENCIA', 'A.NOMBRE');
        $sqlUbicacion = $sqlUbicacion . ($bd->busquedaTextoArray($txAlmacen, $camposBD));
    endif;
    $textoLista = $textoLista . "&" . $auxiliar->traduce("Almacén", $administrador->ID_IDIOMA) . ": " . $txAlmacen;
endif;

//CENTRO_FISICO
$administrador->precargarValorDefectoSiNecesario("CENTRO_FISICO", $idCentroFisico, $txCentroFisico);
if ($idCentroFisico != "" || trim( (string)$txCentroFisico) != ""):
    if ($idCentroFisico != ""):
        $sqlUbicacion = $sqlUbicacion . ($bd->busquedaNumero($idCentroFisico, 'CF.ID_CENTRO_FISICO'));
    else:
        $camposBD     = array('CF.REFERENCIA');
        $sqlUbicacion = $sqlUbicacion . ($bd->busquedaTextoArray($txCentroFisico, $camposBD));
    endif;
    $textoLista = $textoLista . "&" . $auxiliar->traduce("Centro Físico", $administrador->ID_IDIOMA) . ": " . $txCentroFisico;
endif;

//CENTRO
if ($idCentro != "" || trim( (string)$txCentro) != ""):
    if ($idCentro != ""):
        $sqlUbicacion = $sqlUbicacion . ($bd->busquedaNumero($idCentro, 'C.ID_CENTRO'));
    else:
        $camposBD     = array('C.CENTRO', 'C.REFERENCIA');
        $sqlUbicacion = $sqlUbicacion . ($bd->busquedaTextoArray($txCentro, $camposBD));
    endif;
    $textoLista = $textoLista . "&" . $auxiliar->traduce("Centro", $administrador->ID_IDIOMA) . ": " . $txCentro;
endif;

if ($idUnidadOrganizativaProceso != "" || trim( (string)$txUnidadOrganizativaProceso) != ""):
    if ($idUnidadOrganizativaProceso != ""):
        $sqlUbicacion = $sqlUbicacion . ($bd->busquedaNumero($idUnidadOrganizativaProceso, 'U.ID_UNIDAD_ORGANIZATIVA_PROCESO'));
    else:
        $joinUnidadOrganizaztiva = " INNER JOIN UNIDAD_ORGANIZATIVA_PROCESO UOP ON UOP.ID_UNIDAD_ORGANIZATIVA_PROCESO = U.ID_UNIDAD_ORGANIZATIVA_PROCESO ";
        $camposBD                = array('UOP.TIPO_UOP_ESP', 'UOP.TIPO_UOP_ENG');
        $sqlUbicacion            = $sqlUbicacion . ($bd->busquedaTextoArray($txUnidadOrganizativaProceso, $camposBD));
    endif;
    $textoLista = $textoLista . "&" . $auxiliar->traduce("Tipo Unidad Organizativa", $administrador->ID_IDIOMA) . ": " . $txUnidadOrganizativaProceso;
endif;

//TIPO DE UBICACION
//if ($selTipoUbicacion == "") $selTipoUbicacion = "Todos";
if ($selTipoUbicacion != ""):
    //CASO ES PECIAL, BLOQUEADO, CONTROLADO CON VARIABLE $SQLOR
    $selTipoUbicacion2      = $selTipoUbicacion;
    $sqlOR                  = "";
    $sqlAND                 = "";
    $elementosSeleccionados = explode(SEPARADOR_BUSQUEDA_MULTIPLE, (string)$selTipoUbicacion);
    if (in_array('Estándar', (array) $elementosSeleccionados)):
        $clave = array_search('Estándar', $elementosSeleccionados);
        unset($elementosSeleccionados[$clave]);
        $selTipoUbicacion2 = implode(SEPARADOR_BUSQUEDA_MULTIPLE, (array) $elementosSeleccionados);
        $sqlOR             = " OR (U.TIPO_UBICACION IS NULL)";
        $sqlAND            = " AND (U.TIPO_UBICACION IS NULL)";
    endif;
    if ($selTipoUbicacion2 != ""):
        $sqlUbicacion .= ($bd->busquedaTextoDesplegable($selTipoUbicacion2, 'U.TIPO_UBICACION', $sqlOR));
    else:
        $sqlUbicacion .= " $sqlAND ";
    endif;
    $textoLista = $textoLista . "&" . $auxiliar->traduce("Tipo Ubicacion", $administrador->ID_IDIOMA) . ": " . $auxiliar->traducirElementosSeleccionadosDesplegable($selTipoUbicacion);
endif;

//APQ
if ($selAPQ != ""):
    //CASO ESPECIAL, CONTROLADO CON VARIABLE $SQLOR
    $selAPQ2                = $selAPQ;
    $sqlOR                  = "";
    $sqlAND                 = "";
    $elementosSeleccionados = explode(SEPARADOR_BUSQUEDA_MULTIPLE, (string)$selAPQ);
    if (in_array('5+6+7', (array) $elementosSeleccionados)):
        $clave = array_search('5+6+7', $elementosSeleccionados);
        unset($elementosSeleccionados[$clave]);
        $selAPQ2 = implode(SEPARADOR_BUSQUEDA_MULTIPLE, (array) $elementosSeleccionados);
        $sqlOR   = " OR (U.CLASE_APQ IN ('5','6','7'))";
        $sqlAND  = " AND (U.CLASE_APQ IN ('5','6','7'))";
    endif;
    if ($selAPQ2 != ""):
        $sqlUbicacion .= ($bd->busquedaTextoDesplegable($selAPQ2, 'U.CLASE_APQ', $sqlOR));
    else:
        $sqlUbicacion .= " $sqlAND ";
    endif;
    $textoLista = $textoLista . "&" . $auxiliar->traduce("Clase APQ", $administrador->ID_IDIOMA) . ": " . $auxiliar->traducirElementosSeleccionadosDesplegable($selAPQ);
endif;

//ESTADO TIPO DE UBICACION SECTOR
if ($selEstadoSector != ""):
    //CASO ESPECIAL, CONTROLADO CON VARIABLE $SQLOR
    $selEstadoSector2       = $selEstadoSector;
    $sqlOR                  = "";
    $sqlAND                 = "";
    $elementosSeleccionados = explode(SEPARADOR_BUSQUEDA_MULTIPLE, (string)$selEstadoSector);
    if (in_array('Incompleto+Vacio', (array) $elementosSeleccionados)):
        $clave = array_search('Incompleto+Vacio', $elementosSeleccionados);
        unset($elementosSeleccionados[$clave]);
        $selEstadoSector2 = implode(SEPARADOR_BUSQUEDA_MULTIPLE, (array) $elementosSeleccionados);
        $sqlOR            = " OR (U.ESTADO_SECTOR IN ('Incompleto', 'Vacio'))";
        $sqlAND           = " AND (U.TIPO_UBICACION = 'Sector' AND U.ESTADO_SECTOR IN ('Incompleto', 'Vacio'))";
    endif;
    if ($selEstadoSector2 != ""):
        $sqlUbicacion .= ($bd->busquedaTextoDesplegable($selEstadoSector2, 'U.ESTADO_SECTOR', $sqlOR) . " AND U.TIPO_UBICACION = 'Sector' ");
    else:
        $sqlUbicacion .= " $sqlAND ";
    endif;
    $textoLista = $textoLista . "&" . $auxiliar->traduce("Estado Sector", $administrador->ID_IDIOMA) . ": " . $auxiliar->traducirElementosSeleccionadosDesplegable($selEstadoSector);
endif;

//CATEGORIA UBICACION
$joinCategoriaUbicacion = "";
if ($idCategoriaUbicacion != "" || trim( (string)$txCategoriaUbicacion) != ""):
    $joinCategoriaUbicacion = "INNER JOIN UBICACION_CATEGORIA UC ON U.ID_UBICACION_CATEGORIA = UC.ID_UBICACION_CATEGORIA";
    if ($idCategoriaUbicacion != ""):
        $sqlUbicacion = $sqlUbicacion . ($bd->busquedaNumero($idCategoriaUbicacion, 'U.ID_UBICACION_CATEGORIA'));
    else:
        $camposBD     = array('UC.NOMBRE');
        $sqlUbicacion = $sqlUbicacion . ($bd->busquedaTextoArray($txCategoriaUbicacion, $camposBD));
    endif;
    $textoLista = $textoLista . "&" . $auxiliar->traduce("Categoría Ubicación", $administrador->ID_IDIOMA) . ": " . $txCategoriaUbicacion;
endif;

// PRECIO FIJO
if ($selPrecioFijo == 'Si'):
    $sqlUbicacion .= " AND (U.PRECIO_FIJO='1')";
    $textoLista = $textoLista . "&" . $auxiliar->traduce("Precio Fijo", $administrador->ID_IDIOMA) . ": " . $auxiliar->traduce($selPrecioFijo, $administrador->ID_IDIOMA);
elseif ($selPrecioFijo == 'No'):
    $sqlUbicacion .= " AND (U.PRECIO_FIJO='0')";
    $textoLista = $textoLista . "&" . $auxiliar->traduce("Precio Fijo", $administrador->ID_IDIOMA) . ": " . $auxiliar->traduce($selPrecioFijo, $administrador->ID_IDIOMA);
elseif ($selPrecioFijo == 'Todos'):
    $textoLista = $textoLista . "&" . $auxiliar->traduce("Precio Fijo", $administrador->ID_IDIOMA) . ": " . $auxiliar->traduce($selPrecioFijo, $administrador->ID_IDIOMA);
endif;

//UBICACIONES VACIAS
if ($selMostrarUbicacionesVacias == 'Si' || $selMostrarUbicacionesVacias == 'No'):
    $ubicacionesConStock     = "";
    $sqlUbicacionConStock    = "SELECT DISTINCT ID_UBICACION
                             FROM MATERIAL_UBICACION
                             WHERE ACTIVO = 1
                             GROUP BY ID_UBICACION
                             HAVING COUNT(ID_UBICACION) > 0";
    $resultUbicacionConStock = $bd->ExecSQL($sqlUbicacionConStock);
    while ($rowUbicacionConStock = $bd->SigReg($resultUbicacionConStock)):
        $ubicacionesConStock .= $rowUbicacionConStock->ID_UBICACION . ",";
    endwhile;
    if ($ubicacionesConStock != ''):
        $ubicacionesConStock = substr( (string) $ubicacionesConStock, 0, strlen( (string)$ubicacionesConStock) - 1);
        if ($selMostrarUbicacionesVacias == 'Si'):
            $sqlUbicacion .= " AND U.ID_UBICACION NOT IN ($ubicacionesConStock)";
        elseif ($selMostrarUbicacionesVacias == 'No'):
            $sqlUbicacion .= " AND U.ID_UBICACION IN ($ubicacionesConStock)";
        endif;
    endif;
    $textoLista = $textoLista . "&" . $auxiliar->traduce("Mostrar Ubicaciones Vacias", $administrador->ID_IDIOMA) . ": " . $auxiliar->traduce($selMostrarUbicacionesVacias, $administrador->ID_IDIOMA);
endif;

//UBICACIONES DE PENDIENTES
if ($selMostrarUbicacionesDePendientes == 'Si'):
    $sqlUbicacion .= " AND (U.TIPO_UBICACION = 'Preventivo' AND U.TIPO_PREVENTIVO = 'Pendientes')";
elseif ($selMostrarUbicacionesDePendientes == 'No'):
    $sqlUbicacion .= " AND ((U.TIPO_UBICACION <> 'Preventivo' OR U.TIPO_UBICACION IS NULL) OR (U.TIPO_PREVENTIVO <> 'Pendientes' OR U.TIPO_PREVENTIVO IS NULL))";
endif;
    $textoLista = $textoLista . "&" . $auxiliar->traduce("Mostrar Ubicaciones de pendientes", $administrador->ID_IDIOMA) . ": " . $auxiliar->traduce($selMostrarUbicacionesDePendientes,$administrador->ID_IDIOMA);

//RUTA
if ($idRuta != "" || trim( (string)$txRuta) != ""):
    $joinRuta = "INNER JOIN RUTA R ON R.ID_RUTA = CONT.ID_RUTA";
    if ($idRuta != ""):
        $sqlUbicacion = $sqlUbicacion . ($bd->busquedaNumero($idRuta, 'R.ID_RUTA'));
    else:
        $sqlUbicacion = $sqlUbicacion . ($bd->busquedaTexto($txRuta, 'R.RUTA'));
    endif;
    $textoLista = $textoLista . "&" . $auxiliar->traduce("Ruta", $administrador->ID_IDIOMA) . ": " . $txRuta;
endif;

/*//SUBRUTA
if ($idSubruta != "" || trim($txSubruta) != ""):
    $joinSubruta = "INNER JOIN SUBRUTA SR ON SR.ID_SUBRUTA = CONT.ID_SUBRUTA";
    if ($idSubruta != ""):
        $sqlUbicacion = $sqlUbicacion . ($bd->busquedaNumero($idSubruta, 'SR.ID_SUBRUTA'));
    else:
        $sqlUbicacion = $sqlUbicacion . ($bd->busquedaTexto($txSubruta, 'SR.SUBRUTA'));
    endif;
    $textoLista = $textoLista . "&" . $auxiliar->traduce("Subruta", $administrador->ID_IDIOMA) . ": " . $txSubruta;
endif;*/

//AUTOSTORE
if ($selAutostore == 'Si'):
    $sqlUbicacion .= " AND (U.AUTOSTORE='1')";
    $textoLista = $textoLista . "&" . $auxiliar->traduce("Autostore", $administrador->ID_IDIOMA) . ": " . $auxiliar->traduce($selAutostore, $administrador->ID_IDIOMA);
elseif ($selAutostore == 'No'):
    $sqlUbicacion .= " AND (U.AUTOSTORE='0')";
    $textoLista = $textoLista . "&" . $auxiliar->traduce("Autostore", $administrador->ID_IDIOMA) . ": " . $auxiliar->traduce($selAutostore, $administrador->ID_IDIOMA);
endif;

//BAJA
if (!isset($selBaja)):
    $selBaja = 'No';
endif;
if ($selBaja == 'Si'):
    $sqlUbicacion .= " AND (U.BAJA='1')";
    $textoLista = $textoLista . "&" . $auxiliar->traduce("Baja", $administrador->ID_IDIOMA) . ": " . $auxiliar->traduce($selBaja, $administrador->ID_IDIOMA);
elseif ($selBaja == 'No'):
    $sqlUbicacion .= " AND (U.BAJA='0')";
    $textoLista = $textoLista . "&" . $auxiliar->traduce("Baja", $administrador->ID_IDIOMA) . ": " . $auxiliar->traduce($selBaja, $administrador->ID_IDIOMA);
endif;

$mensajeBuscar = $auxiliar->traduce("Introduzca criterio de búsqueda y pulse el botón Buscar", $administrador->ID_IDIOMA);

// TEXTO LISTADO
if ($textoLista == ""):
    $textoLista = $auxiliar->traduce("Todas las ubicaciones", $administrador->ID_IDIOMA);
else:
    if (substr( (string) $textoLista, 0, 1) == "&") $textoLista = substr( (string) $textoLista, 1);
    $textoSustituir = "</font><font color='#EA62A2'> &gt;&gt; </font><font>";
    $textoLista     = preg_replace("/&/", $textoSustituir, $textoLista);
endif;

//AÑADO SEGURIDAD DE ACCESO DE ALMACENES
if ($administrador->esRestringidoPorZonas()):
    $sqlUbicacion .= " AND A.ID_ALMACEN IN " . ($administrador->listadoAlmacenesPermiso("Lectura", "STRING")) . " ";
endif;


$error = "NO";
if ($limite == ""):
    $mySql                               = "SELECT C.REFERENCIA as REF_CENTRO, C.CENTRO as NOM_CENTRO, A.REFERENCIA AS REF_ALMACEN,A.NOMBRE AS NOM_ALMACEN, CF.REFERENCIA AS REFERENCIA_CENTRO_FISICO,
                      U.ID_UBICACION, U.UBICACION, U.TIPO_UBICACION, U.ID_UBICACION_CATEGORIA,U.CLASE_APQ, U.PRECIO_FIJO, U.BAJA, U.DESCRIPCION, U.CANTIDAD_PANELES,
                      ID_UNIDAD_ORGANIZATIVA_PROCESO, U.CANTIDAD_PANELES_POWERBLOCK, POTENCIA_PMW_POWERBLOCK, U.AUTOSTORE
					FROM UBICACION U
					INNER JOIN ALMACEN A ON A.ID_ALMACEN = U.ID_ALMACEN
					INNER JOIN CENTRO_FISICO CF ON CF.ID_CENTRO_FISICO = A.ID_CENTRO_FISICO 
					INNER JOIN CENTRO C ON C.ID_CENTRO = A.ID_CENTRO
					$joinCentroFisico
					$joinCategoriaUbicacion
					$joinContenedor $joinRuta $joinUnidadOrganizaztiva
					$sqlUbicacion";
    $navegar->sqlAdminMaestroUbicaciones = $mySql;
endif;

if ($Buscar == "Si"):
    // REALIZO LA SENTENCIA SQL
    //$navegar->Sql($navegar->sqlAdminMaestroUbicaciones, $navegar->maxfilasMaestroUbicaciones, $navegar->numerofilasMaestroUbicaciones);

    // NUMERO DE REGISTROS
    //$numRegistros = $navegar->numerofilasMaestroUbicaciones;

    // EXPORTAR A EXCEL GESTION UBICACIONES
    if ($exportar_excel_gestion_ubicaciones == "1"):
        $exportar_excel_gestion_ubicaciones = 0;
        $sql                                = $navegar->copiaExport;
        include("exportar_excel_gestion_ubicaciones.php");
        exit;
    endif;

    // EXPORTAR A EXCEL
    if ($exportar_excel == "1"):
        $exportar_excel = 0;
        $sql            = $navegar->copiaExport;
        include("exportar_excel.php");
        exit;
    endif;
endif;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <? require_once $pathClases . "lib/gral_js.php"; ?>
    <!-- BUSQUEDA AJAX -->
    <script src="<?= $pathClases; ?>lib/ajax_script/lib/prototype.js" type="text/javascript"></script>
    <script src="<?= $pathClases; ?>lib/ajax_script/src/scriptaculous.js" type="text/javascript"></script>
    <link rel="stylesheet" href="<?= $pathClases; ?>lib/ajax_script/style_ajax.css" type="text/css"/>
    <!-- FIN BUSQUEDA AJAX -->
    <script type="text/javascript">
        jQuery(document).ready(function () {
            //FANCYBOX CON IFRAME PARA EL BUSCADOR
            jQuery("a.fancyboxAlmacenes").fancybox({
                'type': 'iframe',
                'width': '100%',
                'height': '100%',
                'hideOnOverlayClick': false
            });
            jQuery("a.fancyboxCentroFisico").fancybox({
                'type': 'iframe',
                'width': '100%',
                'height': '100%',
                'hideOnOverlayClick': false
            });
            jQuery("a.fancyboxCentros").fancybox({
                'type': 'iframe',
                'width': '100%',
                'height': '100%',
                'hideOnOverlayClick': false
            });
            jQuery("a.fancyboxCategoriasUbicacion").fancybox({
                'type': 'iframe',
                'width': '100%',
                'height': '100%',
                'hideOnOverlayClick': false
            });
            jQuery("a.fancyboxRutas").fancybox({
                'type': 'iframe',
                'width': '100%',
                'height': '100%',
                'hideOnOverlayClick': false
            });
            /*jQuery("a.fancyboxSubrutas").fancybox({
                'type': 'iframe',
                'width': '100%',
                'height': '100%',
                'hideOnOverlayClick': false
            });*/
            jQuery("a.fancyboxUOP").fancybox({
                'type': 'iframe',
                'width': '100%',
                'height': '100%',
                'hideOnOverlayClick': false
            });


        });
    </script>
    <!-- FIN BUSQUEDA FANCYBOX -->

    <script language="javascript" type="text/javascript">

        function seleccionarTodas(chSel) {
            for (i = 0; i < document.FormSelect.elements.length; i++) {
                if ((document.FormSelect.elements[i].type == "checkbox") && (document.FormSelect.elements[i].name.substr(0, 7) == 'chSelec')) {
                    if (chSel.checked == 1) {
                        document.FormSelect.elements[i].checked = 1;
                    }
                    else {
                        document.FormSelect.elements[i].checked = 0;
                    }
                }
            }
        }

        function bloquearTodas() {
            for (i = 0; i < document.FormSelect.elements.length; i++) {
                if ((document.FormSelect.elements[i].type == "checkbox") && (document.FormSelect.elements[i].name.substr(0, 7) == 'chSelec')) {
                    if (document.FormSelect.chTodos.checked == 1) {
                        document.FormSelect.elements[i].checked = 1;
                        document.FormSelect.elements[i].disabled = 1;
                        document.FormSelect.chSelTodos.value = 1;
                    }
                    else {
                        document.FormSelect.elements[i].checked = 0;
                        document.FormSelect.elements[i].disabled = 0;
                        document.FormSelect.chSelTodos.value = 0;
                    }
                }
            }
        }

        function buscar() {
            document.FormSelect.action = 'index.php';
            document.FormSelect.target = '_self';
            document.FormSelect.Buscar.value = 'Si';

            document.FormSelect.submit();

            return false;
        }

        function imprimirEtiquetasGavetas() {
            algunoMarcado = 0;
            for (i = 0; i < document.FormSelect.elements.length; i++) {
                var nombreElemento = document.FormSelect.elements[i].name;
                if ((document.FormSelect.elements[i].type == "checkbox") && (nombreElemento.substr(0, 8) == "chSelec_") && document.FormSelect.elements[i].checked == 1) {
                    algunoMarcado = 1;
                    break;
                }
            }
            if (algunoMarcado == 1) {
                document.FormSelect.action = 'impEtiquetaUbicacionGavetaPDF.php';
                document.FormSelect.target = '_blank';

                document.FormSelect.submit();

                document.FormSelect.action = 'index.php';
                document.FormSelect.target = '_self';
            } else {

                alert("<?= $auxiliar->traduce("Primero debe seleccionar alguno de los elementos", $administrador->ID_ADMINISTRADOR)?>");
            }
            return false;
        }

        function imprimirEtiquetaCarro() {
            algunoMarcado = 0;
            for (i = 0; i < document.FormSelect.elements.length; i++) {
                var nombreElemento = document.FormSelect.elements[i].name;
                if ((document.FormSelect.elements[i].type == "checkbox") && (nombreElemento.substr(0, 8) == "chSelec_") && document.FormSelect.elements[i].checked == 1) {
                    algunoMarcado = 1;
                    break;
                }
            }
            if (algunoMarcado == 1) {
                document.FormSelect.action = 'impEtiquetaUbicacionCarroPDF.php';
                document.FormSelect.target = '_blank';

                document.FormSelect.submit();

                document.FormSelect.action = 'index.php';
                document.FormSelect.target = '_self';
            } else {

                alert("<?= $auxiliar->traduce("Primero debe seleccionar alguno de los elementos", $administrador->ID_ADMINISTRADOR)?>");
            }
            return false;
        }


        function darBajaUbicaciones() {
            var listaIdUbicaciones = '';
            var coma = '';

            for (i = 0; i < document.FormSelect.elements.length; i++) {
                if ((document.FormSelect.elements[i].type == "checkbox")
                    && (document.FormSelect.elements[i].name.substr(0, 8) == 'chSelec_')
                    && document.FormSelect.elements[i] != document.FormSelect.chSelecTodas) {
                    if (document.FormSelect.elements[i].checked == 1) {
                        listaIdUbicaciones = listaIdUbicaciones + coma + document.FormSelect.elements[i].name.substr(8);
                        coma = ',';
                    }
                }
            }

            if (listaIdUbicaciones == '') {
                alert('<?=$auxiliar->traduce("Primero debe seleccionar alguno de los elementos",$administrador->ID_IDIOMA)?>');
                return false;
            }
            else {

                jQuery.fancybox
                (
                    {
                        'type': 'iframe',
                        'width': '100%',
                        'height': '100%',
                        'hideOnOverlayClick': false,
                        'href': 'ficha_bajaMasiva.php?listaIdUbicaciones=' + listaIdUbicaciones
                    }
                );

            }
        }

        function imprimirEtiquetas() {
            algunoMarcado = 0;
            for (i = 0; i < document.FormSelect.elements.length; i++) {
                var nombreElemento = document.FormSelect.elements[i].name;
                if ((document.FormSelect.elements[i].type == "checkbox") && (nombreElemento.substr(0, 8) == "chSelec_") && document.FormSelect.elements[i].checked == 1) {
                    algunoMarcado = 1;
                    break;
                }
            }
            if (algunoMarcado == 1) {
                document.FormSelect.action = 'impEtiquetaUbicacionPDF.php';
                document.FormSelect.target = '_blank';

                document.FormSelect.submit();

                document.FormSelect.action = 'index.php';
                document.FormSelect.target = '_self';

            } else {

                alert("<?= $auxiliar->traduce("Primero debe seleccionar alguno de los elementos", $administrador->ID_ADMINISTRADOR)?>");
            }
            return false;
        }

        function imprimirEtiquetasCode39() {
            algunoMarcado = 0;
            for (i = 0; i < document.FormSelect.elements.length; i++) {
                var nombreElemento = document.FormSelect.elements[i].name;
                if ((document.FormSelect.elements[i].type == "checkbox") && (nombreElemento.substr(0, 8) == "chSelec_") && document.FormSelect.elements[i].checked == 1) {
                    algunoMarcado = 1;
                    break;
                }
            }
            if (algunoMarcado == 1) {
                document.FormSelect.action = 'impEtiquetaUbicacionPDF_code39.php';
                document.FormSelect.target = '_blank';

                document.FormSelect.submit();

                document.FormSelect.action = 'index.php';
                document.FormSelect.target = '_self';

            } else {

                alert("<?= $auxiliar->traduce("Primero debe seleccionar alguno de los elementos", $administrador->ID_ADMINISTRADOR)?>");
            }
            return false;
        }

        function exportarExcel() {
            document.FormSelect.exportar_excel.value = '1';
            document.FormSelect.submit();
            document.FormSelect.exportar_excel.value = '0';
            return false;
        }

        function exportarExcelGestionUbicaciones() {
            document.FormSelect.exportar_excel_gestion_ubicaciones.value = '1';
            document.FormSelect.submit();
            document.FormSelect.exportar_excel_gestion_ubicaciones.value = '0';
            return false;
        }


    </script>

</head>
<body bgcolor="#FFFFFF" background="<? echo $pathRaiz ?>imagenes/fondo_pantalla.gif" bottommargin="0" leftmargin="0"
      topmargin="0" marginwidth="0" marginheight="0" onLoad="document.FormSelect.txUbicacion.focus()">
<FORM NAME="FormSelect" ACTION="index.php" METHOD="POST">
    <input type=hidden name="chSelTodos" value="">

    <INPUT TYPE="HIDDEN" NAME="nombre_fichero" VALUE="<? echo $tituloPag ?>.xls">
    <INPUT TYPE="HIDDEN" NAME="nombre_hoja" VALUE="Hoja1">
    <INPUT TYPE="HIDDEN" NAME="exportar_excel" VALUE="0">
    <INPUT TYPE="HIDDEN" NAME="accion" VALUE="">
    <INPUT TYPE="HIDDEN" NAME="exportar_excel_gestion_ubicaciones" VALUE="0">
    <input type="hidden" name="pantallaSolar" id="pantallaSolar" value="<?= $pantallaSolar ?>"/>
    <input type="hidden" name="pantallaConstruccion" id="pantallaConstruccion" value="<?= $pantallaConstruccion ?>"/>

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
                                                        src="<? echo $pathRaiz ?>imagenes/flechitas_01.gif" width="35"
                                                        height="23"></td>
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
                                                <td bgcolor="#B3C7DA" class="lineabajoarriba">&nbsp;
                                                    <? if ($fraseAccionOk != ""): ?>
                                                        &nbsp;&nbsp;&nbsp;
                                                        <img class='parpadeante'
                                                             src='<? echo $pathRaiz ?>imagenes/informacion.png'
                                                             align='top' width="17px" height="17px" valing="'middle"/>
                                                        <font color='green'
                                                              class="textoazul"><b><? echo $auxiliar->traduce($fraseAccionOk, $administrador->ID_IDIOMA); ?></b></font>
                                                    <? endif; ?>
                                                </td>
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
                                                                    width="10" height="5"/></td>
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
                                                                            class="textoazul">
                                                                            <? if ((($pantallaSolar != 1) && ($pantallaConstruccion != 1))): //LAS PANTALLAS DE CONSTRUCCION SON INFORMATIVAS
                                                                                echo $auxiliar->traduce("Ubicación", $administrador->ID_IDIOMA) . ":";
                                                                            else:
                                                                                echo $auxiliar->traduce("Unidad Organizativa", $administrador->ID_IDIOMA) . ":";
                                                                            endif;
                                                                            ?>
                                                                        </td>
                                                                        <td width="24%" align="left" valign="middle"><?
                                                                            $TamanoText = "180px";
                                                                            $ClassText  = "copyright";
                                                                            $MaxLength  = "50";
                                                                            $html->TextBox("txUbicacion", $txUbicacion);
                                                                            ?> <img
                                                                                src="<?= $pathRaiz ?>imagenes/alert1.png"
                                                                                alt="<? echo $auxiliar->traduce("Informacion Filtro", $administrador->ID_IDIOMA); ?>"
                                                                                border="0"
                                                                                valign="middle"
                                                                                title="<?= $auxiliar->traduce("Filtro de búsqueda exacta. Para búsquedas condicionales utilice los caracteres * y ?", $administrador->ID_IDIOMA) ?>"/>
                                                                        </td>
                                                                        <td width="4%" align="center" valign="top">
                                                                            &nbsp;
                                                                        </td>
                                                                        <td height="20" align="left" valign="middle"
                                                                            class="textoazul">
                                                                            <? if ((($pantallaSolar != 1) && ($pantallaConstruccion != 1))): //LAS PANTALLAS DE CONSTRUCCION SON INFORMATIVAS
                                                                                echo $auxiliar->traduce("Tipo Ubicación", $administrador->ID_IDIOMA) . ":";
                                                                            else:
                                                                                echo $auxiliar->traduce("Tipo Unidad Organizativa", $administrador->ID_IDIOMA) . ":";
                                                                            endif; ?>
                                                                        </td>
                                                                        <td align="left" valign="middle">
                                                                            <?
                                                                            if ((($pantallaSolar != 1) && ($pantallaConstruccion != 1))): //LAS PANTALLAS DE CONSTRUCCION SON INFORMATIVAS
                                                                                $i = 0;
                                                                                unset($opciones);
                                                                                $opciones[$i]['text']  = $auxiliar->traduce("Todos", $administrador->ID_IDIOMA);
                                                                                $opciones[$i]['valor'] = '';
                                                                                $i                     = $i + 1;

                                                                                $opciones[$i]['text']  = $auxiliar->traduce("Estándar", $administrador->ID_IDIOMA);
                                                                                $opciones[$i]['valor'] = 'Estándar';
                                                                                $i                     = $i + 1;

                                                                                //BUSCO LOS POSIBLES VALORES DE TIPO UBICACION
                                                                                $sqlTiposUbicacion    = "SHOW COLUMNS FROM UBICACION LIKE 'TIPO_UBICACION'";
                                                                               // $resultTiposUbicacion = $bd->ExecSQL($sqlTiposUbicacion);
                                                                                if ($resultTiposUbicacion != false):
                                                                                    $rowTiposUbicacion = mysqli_fetch_array($resultTiposUbicacion);

                                                                                    $cadenavalor = $rowTiposUbicacion[1];
                                                                                    $cadenavalor = str_replace( "enum", "",(string) $cadenavalor);
                                                                                    $cadenavalor = str_replace( "(", "",(string) $cadenavalor);
                                                                                    $cadenavalor = str_replace( ")", "",(string) $cadenavalor);
                                                                                    $cadenavalor = str_replace( "'", "",(string) $cadenavalor);
                                                                                    $valores     = explode(",", $cadenavalor);

                                                                                    //RECORRO EL ARRAY PARA AÑADIR LOS VALORES EXTRAIDOS
                                                                                    foreach ($valores as $tipoUbicacion):
                                                                                        $opciones[$i]['text']  = $auxiliar->traduce($tipoUbicacion, $administrador->ID_IDIOMA);
                                                                                        $opciones[$i]['valor'] = $tipoUbicacion;
                                                                                        $i                     = $i + 1;
                                                                                    endforeach;
                                                                                endif;

                                                                                $NombreSelect      = 'selTipoUbicacion';
                                                                                $Tamano            = '205px';
                                                                                $Estilo            = "copyright";
                                                                                $onChange          = "onChange = 'VerAlmacenDestinoGaveta(this.value);VerCantidadPanelesSector(this.value);'";
                                                                                $SeleccionMultiple = "Si";
                                                                                $html->SelectArr($NombreSelect, $opciones, $selTipoUbicacion, $selTipoUbicacion);
                                                                                unset($SeleccionMultiple);
                                                                                unset($disabled);
                                                                                unset($onChange);
                                                                            else: ?>
                                                                                <?
                                                                                $idTextBox  = "txUnidadOrganizativaProceso";
                                                                                $TamanoText = "180px";
                                                                                $ClassText  = "copyright";
                                                                                $jscript    = "onchange=\" document.FormSelect.idUnidadOrganizativaProceso.value='';\"";
                                                                                $html->TextBox("txUnidadOrganizativaProceso", $txUnidadOrganizativaProceso);
                                                                                unset($idTextBox);
                                                                                unset($jscript);
                                                                                unset($MaxLength);

                                                                                ?>
                                                                                <input type="hidden"
                                                                                       name="idUnidadOrganizativaProceso"
                                                                                       id="idUnidadOrganizativaProceso"
                                                                                       value="<?= $idUnidadOrganizativaProceso ?>"/>
                                                                                <a href="<?= $pathRaiz; ?>buscadores_maestros/busqueda_unidad_organizativa.php?AlmacenarId=0<?= ($pantallaSolar == 1 ? "&soloFotovoltaico=1" : ($pantallaConstruccion == 1 ? "&soloEolico=1" : "")); ?><?= ($rowCentroFisico->TIPO_CONSTRUCCION == "Fotovoltaico" ? "&soloFotovoltaico=1" : ($rowCentroFisico->TIPO_CONSTRUCCION == "Eolico" ? "&soloEolico=1" : "")); ?>"
                                                                                   class="fancyboxUOP"
                                                                                   id="UOP">
                                                                                    <img border="0"
                                                                                         align="absbottom"
                                                                                         alt="Buscar"
                                                                                         src="<?= $pathRaiz ?>imagenes/botones/listado.png"
                                                                                         name="Listado">
                                                                                </a>
                                                                                <span
                                                                                    id="c"
                                                                                    style="display: none;"><img
                                                                                        src="<?= $pathClases; ?>lib/ajax_script/img/esperando.gif"
                                                                                        width="15"
                                                                                        height="11"
                                                                                        alt="Buscando..."/></span>

                                                                                <div class="entry"
                                                                                     align="left"
                                                                                     id="actualizador_UnidadOrganizativaProcesos"></div>
                                                                                <script
                                                                                    type="text/javascript"
                                                                                    language="javascript">
                                                                                    new Ajax.Autocompleter('txUnidadOrganizativaProceso', 'actualizador_UnidadOrganizativaProcesos', '<?=$pathRaiz;?>buscadores_maestros/resp_ajax_unidad_organizativa.php?AlmacenarId=0<?= ($pantallSolar == "1" ? "&soloFotovoltaico=1" : ( $pantallaConstruccion == "1" ? "&soloEolico=1" : "")); ?>',
                                                                                        {
                                                                                            method: 'post',
                                                                                            indicator: 'actualizador_UnidadOrganizativaProcesos',
                                                                                            minChars: '2',
                                                                                            afterUpdateElement: function (textbox, valor) {
                                                                                                jQuery('#idUnidadOrganizativaProceso').val(jQuery(valor).children('a').attr('alt'));
                                                                                            }
                                                                                        }
                                                                                    );
                                                                                </script>

                                                                            <? endif; ?>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td height="20" align="left" valign="middle"
                                                                            class="textoazul"><?= $auxiliar->traduce("Centro", $administrador->ID_IDIOMA) . ":"; ?>
                                                                        </td>
                                                                        <td align="left" valign="middle"><?
                                                                            $TamanoText = '180px';
                                                                            $ClassText  = "copyright";
                                                                            $MaxLength  = "50";
                                                                            $idTextBox  = 'txCentro';
                                                                            $jscript    = "onchange=\"document.FormSelect.idCentro.value=''\"";
                                                                            $html->TextBox("txCentro", $txCentro);
                                                                            unset($jscript);
                                                                            unset($idTextBox);
                                                                            ?>
                                                                            <input type="hidden" name="idCentro"
                                                                                   id="idCentro"
                                                                                   value="<?= $idCentro ?>"/>
                                                                            <a href="<? echo $pathRaiz ?>buscadores_maestros/busqueda_centro.php?AlmacenarId=0"
                                                                               class="fancyboxCentros"
                                                                               id="centros"> <img
                                                                                    src="<?= $pathRaiz ?>imagenes/botones/listado.png"
                                                                                    alt="<?= $auxiliar->traduce("Buscar Centro", $administrador->ID_IDIOMA) ?>"
                                                                                    name="Listado"
                                                                                    border="0" align="absbottom"
                                                                                    id="Listado"/> </a> <span
                                                                                id="desplegable_centros"
                                                                                style="display: none;"> <img
                                                                                    src="<?= $pathClases; ?>lib/ajax_script/img/esperando.gif"
                                                                                    width="15" height="11"
                                                                                    alt="<?= $auxiliar->traduce("Buscando...", $administrador->ID_IDIOMA) ?>"/>
                                                                            </span>

                                                                            <div class="entry" align="left"
                                                                                 id="actualizador_centros"></div>
                                                                            <script type="text/javascript"
                                                                                    language="JavaScript">
                                                                                new Ajax.Autocompleter('txCentro', 'actualizador_centros', '<?=$pathRaiz?>buscadores_maestros/resp_ajax_centro.php?AlmacenarId=0',
                                                                                    {
                                                                                        method: 'post',
                                                                                        indicator: 'desplegable_centros',
                                                                                        minChars: '2',
                                                                                        afterUpdateElement: function (textbox, valor) {
                                                                                            siguiente_control(jQuery('#' + this.paramName));
                                                                                            jQuery(textbox).val(jQuery(valor).children('a').attr('alt'));//VALOR DEL PAR&Aacute;METRO ALT DEL ENLACE <a>
                                                                                            jQuery('#idCentro').val(jQuery(valor).children('a').attr('rev'));
                                                                                        }
                                                                                    }
                                                                                );
                                                                            </script>
                                                                        </td>
                                                                        <td width="4%" align="center" valign="top">
                                                                            &nbsp;
                                                                        </td>
                                                                        <td height="20" align="left" valign="middle"
                                                                            class="textoazul"><?= $auxiliar->traduce("Estado Sector", $administrador->ID_IDIOMA) . ":"; ?>
                                                                        </td>
                                                                        <td align="left" valign="middle">
                                                                            <?
                                                                            $i                            = 0;
                                                                            $arrEstadoSector[$i]["text"]  = $auxiliar->traduce("Todos", $administrador->ID_IDIOMA);
                                                                            $arrEstadoSector[$i]["valor"] = "";
                                                                            $i                            = $i + 1;
                                                                            $arrEstadoSector[$i]["text"]  = $auxiliar->traduce("Completo", $administrador->ID_IDIOMA);
                                                                            $arrEstadoSector[$i]["valor"] = 'Completo';
                                                                            $i                            = $i + 1;
                                                                            $arrEstadoSector[$i]["text"]  = $auxiliar->traduce("Incompleto", $administrador->ID_IDIOMA);
                                                                            $arrEstadoSector[$i]["valor"] = 'Incompleto';
                                                                            $i                            = $i + 1;
                                                                            $arrEstadoSector[$i]["text"]  = $auxiliar->traduce("Vacio", $administrador->ID_IDIOMA);
                                                                            $arrEstadoSector[$i]["valor"] = 'Vacio';
                                                                            $i                            = $i + 1;
                                                                            $arrEstadoSector[$i]["text"]  = $auxiliar->traduce("Incompleto+Vacio", $administrador->ID_IDIOMA);
                                                                            $arrEstadoSector[$i]["valor"] = 'Incompleto+Vacio';
                                                                            $i                            = $i + 1;
                                                                            $Tamano                       = "205px";
                                                                            $Estilo                       = "copyright";
                                                                            $SeleccionMultiple            = "Si";
                                                                            $html->SelectArr("selEstadoSector", $arrEstadoSector, $selEstadoSector, "No");
                                                                            unset($SeleccionMultiple);
                                                                            ?>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td height="20" align="left" valign="middle"
                                                                            class="textoazul">
                                                                            <? if ((($pantallaSolar != 1) && ($pantallaConstruccion != 1))): //LAS PANTALLAS DE CONSTRUCCION SON INFORMATIVAS
                                                                                echo $auxiliar->traduce("Almacén", $administrador->ID_IDIOMA) . ":";
                                                                            else:
                                                                                echo $auxiliar->traduce("Instalacion", $administrador->ID_IDIOMA) . ":";
                                                                            endif;
                                                                            ?>
                                                                        </td>
                                                                        <td align="left" valign="middle"><?
                                                                            $TamanoText = '180px';
                                                                            $ClassText  = "copyright";
                                                                            $MaxLength  = "50";
                                                                            $idTextBox  = 'txAlmacen';
                                                                            $jscript    = "onchange=\"document.FormSelect.idAlmacen.value=''\"";
                                                                            $html->TextBox("txAlmacen", $txAlmacen);
                                                                            unset($jscript);
                                                                            unset($idTextBox);
                                                                            ?>
                                                                            <input type="hidden" name="idAlmacen"
                                                                                   id="idAlmacen"
                                                                                   value="<?= $idAlmacen ?>"/>
                                                                            <a href="<?= $pathRaiz ?>buscadores_maestros_restringidos/busqueda_almacen.php?AlmacenarId=0&tipoAcceso=Lectura&establecerIdCentro=1&establecerNombreCentro=1"
                                                                               class="fancyboxAlmacenes" id="almacenes">
                                                                                <img
                                                                                    src="<?= $pathRaiz ?>imagenes/botones/listado.png"
                                                                                    alt="<?= $auxiliar->traduce("Buscar Almacén", $administrador->ID_IDIOMA) ?>"
                                                                                    name="Listado" border="0"
                                                                                    align="absbottom" id="Listado"/>
                                                                            </a> <span id="desplegable_almacenes"
                                                                                       style="display: none;"> <img
                                                                                    src="<?= $pathClases; ?>lib/ajax_script/img/esperando.gif"
                                                                                    width="15" height="11"
                                                                                    alt="<?= $auxiliar->traduce("Buscando...", $administrador->ID_IDIOMA) ?>"/>
                                                                            </span>

                                                                            <div class="entry" align="left"
                                                                                 id="actualizador_almacenes"></div>
                                                                            <script type="text/javascript"
                                                                                    language="JavaScript">
                                                                                new Ajax.Autocompleter('txAlmacen', 'actualizador_almacenes', '<?=$pathRaiz?>buscadores_maestros_restringidos/resp_ajax_almacen.php?AlmacenarId=0&tipoAcceso=Lectura',
                                                                                    {
                                                                                        method: 'post',
                                                                                        indicator: 'desplegable_almacenes',
                                                                                        minChars: '2',
                                                                                        afterUpdateElement: function (textbox, valor) {
                                                                                            jQuery('#btnBuscar').focus();
                                                                                            jQuery(textbox).val(jQuery(valor).children('a').attr('alt'));//VALOR DEL PAR&Aacute;METRO ALT DEL ENLACE <a>
                                                                                            jQuery('#idAlmacen').val(jQuery(valor).children('a').attr('rev'));

                                                                                            jQuery('#txCentro').val(jQuery(valor).children('a').attr('refCentro'));
                                                                                            jQuery('#idCentro').val(jQuery(valor).children('a').attr('idCentro'));

                                                                                        }
                                                                                    }
                                                                                );
                                                                            </script>
                                                                        </td>
                                                                        <td align="center" valign="top">&nbsp;</td>
                                                                        <td height="20" align="left" valign="middle"
                                                                            class="textoazul">
                                                                            <?
                                                                            if ((($pantallaSolar != 1) && ($pantallaConstruccion != 1))): //LAS PANTALLAS DE CONSTRUCCION SON INFORMATIVAS
                                                                                echo $auxiliar->traduce("Id. Ubicación", $administrador->ID_IDIOMA) . ":";
                                                                            else:
                                                                                echo $auxiliar->traduce("Id. Unidad Organizativa", $administrador->ID_IDIOMA) . ":";
                                                                            endif;
                                                                            ?>
                                                                        </td>
                                                                        <td align="left" valign="middle">
                                                                            <?
                                                                            $TamanoText = "200px";
                                                                            $ClassText  = "copyright";
                                                                            $MaxLength  = "50";
                                                                            $html->TextBox("txIdUbicacion", $txIdUbicacion);
                                                                            ?>

                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td height="20" align="left" valign="middle"
                                                                            class="textoazul"><?= $auxiliar->traduce("Centro Físico", $administrador->ID_IDIOMA) . ":"; ?>
                                                                        </td>
                                                                        <td align="left" valign="middle"><?
                                                                            $TamanoText = '180px';
                                                                            $ClassText  = "copyright";
                                                                            $MaxLength  = "150";
                                                                            $jscript    = "onchange=\"document.FormSelect.idCentroFisico.value=''\"";
                                                                            $idTextBox  = 'txCentroFisico';
                                                                            $html->TextBox("txCentroFisico", $txCentroFisico);
                                                                            unset($idTextBox);
                                                                            unset($jscript); ?>
                                                                            <input type="hidden" name="idCentroFisico"
                                                                                   id="idCentroFisico"
                                                                                   value="<?= $idCentroFisico ?>"/>
                                                                            <a href="<?= $pathRaiz ?>buscadores_maestros/busqueda_centro_fisico.php?AlmacenarId=0"
                                                                               class="fancyboxCentroFisico"
                                                                               id="centroFisico">
                                                                                <img
                                                                                    src="<?= $pathRaiz ?>imagenes/botones/listado.png"
                                                                                    alt="<?= $auxiliar->traduce("Buscar Centro Físico", $administrador->ID_IDIOMA) ?>"
                                                                                    name="Listado"
                                                                                    border="0" align="absbottom"
                                                                                    id="Listado"/>
                                                                            </a>
                                                                            <span id="desplegable_centro_fisico"
                                                                                  style="display: none;">
                                                                                <img
                                                                                    src="<?= $pathClases; ?>lib/ajax_script/img/esperando.gif"
                                                                                    width="15"
                                                                                    height="11"
                                                                                    alt="<?= $auxiliar->traduce("Buscando...", $administrador->ID_IDIOMA) ?>"/>
                                                                            </span>

                                                                            <div class="entry" align="left"
                                                                                 id="actualizador_centro_fisico">
                                                                            </div>
                                                                            <script type="text/javascript"
                                                                                    language="JavaScript">
                                                                                new Ajax.Autocompleter('txCentroFisico', 'actualizador_centro_fisico', '<?=$pathRaiz?>buscadores_maestros/resp_ajax_centro_fisico.php?AlmacenarId=0',
                                                                                    {
                                                                                        method: 'post',
                                                                                        indicator: 'desplegable_centro_fisico',
                                                                                        minChars: '2',
                                                                                        afterUpdateElement: function (textbox, valor) {
                                                                                            siguiente_control(jQuery('#' + this.paramName));
                                                                                            jQuery(textbox).val(jQuery(valor).children('a').attr('alt'));//VALOR DEL PAR&Aacute;METRO ALT DEL ENLACE <a>
                                                                                            jQuery('#idCentroFisico').val(jQuery(valor).children('a').attr('rev'));
                                                                                        }
                                                                                    }
                                                                                );
                                                                            </script>
                                                                        </td>
                                                                        <td align="center" valign="top">&nbsp;</td>
                                                                        <td height="20" align="left" valign="middle"
                                                                            class="textoazul"><?= $auxiliar->traduce("Descripción", $administrador->ID_IDIOMA) . ":"; ?>
                                                                        </td>
                                                                        <td align="left" valign="middle">
                                                                            <?
                                                                            $TamanoText = "200px";
                                                                            $ClassText  = "copyright";
                                                                            $MaxLength  = "50";
                                                                            $html->TextBox("txDescripcion", $txDescripcion);
                                                                            ?>
                                                                        </td>
                                                                    </tr>
                                                                    <?
                                                                    if ((($pantallaSolar != 1) && ($pantallaConstruccion != 1))): //LAS PANTALLAS DE CONSTRUCCION SON INFORMATIVAS
                                                                        ?>
                                                                        <tr>
                                                                            <td height="20" align="left" valign="middle"
                                                                                class="textoazul">
                                                                                <?= $auxiliar->traduce("Categoría Ubicación", $administrador->ID_IDIOMA) . ":"; ?>
                                                                            </td>
                                                                            <td align="left" valign="middle">
                                                                                <?
                                                                                $TamanoText = '180px';
                                                                                $ClassText  = "copyright";
                                                                                $MaxLength  = "50";
                                                                                $idTextBox  = 'txCategoriaUbicacion';
                                                                                $jscript    = "onchange=\"document.FormSelect.idCategoriaUbicacion.value=''\"";
                                                                                $html->TextBox("txCategoriaUbicacion", $txCategoriaUbicacion);
                                                                                unset($jscript);
                                                                                unset($idTextBox);
                                                                                ?>
                                                                                <input type="hidden"
                                                                                       name="idCategoriaUbicacion"
                                                                                       id="idCategoriaUbicacion"
                                                                                       value="<?= $idCategoriaUbicacion ?>"/>
                                                                                <a href="<?= $pathRaiz ?>buscadores_maestros/busqueda_categoria_ubicacion.php?AlmacenarId=0"
                                                                                   class="fancyboxCategoriasUbicacion"
                                                                                   id="categoriasUbicacion"> <img
                                                                                        src="<?= $pathRaiz ?>imagenes/botones/listado.png"
                                                                                        alt="<?= $auxiliar->traduce("Buscar Categoría Ubicación", $administrador->ID_IDIOMA) ?>"
                                                                                        name="Listado"
                                                                                        border="0" align="absbottom"
                                                                                        id="Listado"/> </a>
                                                                            <span id="desplegable_categorias_ubicacion"
                                                                                  style="display: none;">
                                                                                <img
                                                                                    src="<?= $pathClases; ?>lib/ajax_script/img/esperando.gif"
                                                                                    width="15"
                                                                                    height="11"
                                                                                    alt="<?= $auxiliar->traduce("Buscando...", $administrador->ID_IDIOMA) ?>"/>
                                                                            </span>

                                                                                <div class="entry" align="left"
                                                                                     id="actualizador_categorias_ubicacion"></div>
                                                                                <script type="text/javascript"
                                                                                        language="JavaScript">
                                                                                    new Ajax.Autocompleter('txCategoriaUbicacion', 'actualizador_categorias_ubicacion', '<?=$pathRaiz?>buscadores_maestros/resp_ajax_categoria_ubicacion.php?AlmacenarId=0',
                                                                                        {
                                                                                            method: 'post',
                                                                                            indicator: 'desplegable_categorias_ubicacion',
                                                                                            minChars: '1',
                                                                                            afterUpdateElement: function (textbox, valor) {
                                                                                                siguiente_control(jQuery('#' + this.paramName));
                                                                                                jQuery(textbox).val(jQuery(valor).children('a').attr('alt'));//VALOR DEL PAR&Aacute;METRO ALT DEL ENLACE <a>
                                                                                                jQuery('#idCategoriaUbicacion').val(jQuery(valor).children('a').attr('rev'));
                                                                                            }
                                                                                        }
                                                                                    );
                                                                                </script>

                                                                            </td>

                                                                            <td align="center" valign="top">&nbsp;</td>
                                                                            <td height="20" align="left" valign="middle"
                                                                                class="textoazul"><?= $auxiliar->traduce("Clase APQ", $administrador->ID_IDIOMA) . ":"; ?>
                                                                            </td>
                                                                            <td align="left" valign="middle"><?
                                                                                $NombreSelect               = 'selAPQ';
                                                                                $i                          = 0;
                                                                                $Elementos_APQ[$i]['text']  = $auxiliar->traduce("Todos", $administrador->ID_IDIOMA);
                                                                                $Elementos_APQ[$i]['valor'] = '';
                                                                                $i                          = $i + 1;
                                                                                $Elementos_APQ[$i]['text']  = "1 - " . $auxiliar->traduce("Liquidos inflamables y combustible", $administrador->ID_IDIOMA);
                                                                                $Elementos_APQ[$i]['valor'] = '1';
                                                                                $i                          = $i + 1;
                                                                                $Elementos_APQ[$i]['text']  = "5 - " . $auxiliar->traduce("Botellas y botellones de gases", $administrador->ID_IDIOMA);
                                                                                $Elementos_APQ[$i]['valor'] = '5';
                                                                                $i                          = $i + 1;
                                                                                $Elementos_APQ[$i]['text']  = "6 - " . $auxiliar->traduce("Liquidos corrosivos", $administrador->ID_IDIOMA);
                                                                                $Elementos_APQ[$i]['valor'] = '6';
                                                                                $i                          = $i + 1;
                                                                                $Elementos_APQ[$i]['text']  = "7 - " . $auxiliar->traduce("Liquidos toxicos", $administrador->ID_IDIOMA);
                                                                                $Elementos_APQ[$i]['valor'] = '7';
                                                                                $i                          = $i + 1;
                                                                                $Elementos_APQ[$i]['text']  = $auxiliar->traduce("5+6+7", $administrador->ID_IDIOMA);
                                                                                $Elementos_APQ[$i]['valor'] = '5+6+7';
                                                                                $i                          = $i + 1;
                                                                                $Elementos_APQ[$i]['text']  = "9 - " . $auxiliar->traduce("Peroxidos organicos", $administrador->ID_IDIOMA);
                                                                                $Elementos_APQ[$i]['valor'] = '9';
                                                                                $i                          = $i + 1;
                                                                                $Elementos_APQ[$i]['text']  = "RG - " . $auxiliar->traduce("", $administrador->ID_IDIOMA);
                                                                                $Elementos_APQ[$i]['valor'] = 'RG';
                                                                                $Tamano                     = "205px";
                                                                                $Estilo                     = "copyright";
                                                                                $SeleccionMultiple          = "Si";
                                                                                $html->SelectArr($NombreSelect, $Elementos_APQ, $selAPQ, $selAPQ);
                                                                                unset($SeleccionMultiple);
                                                                                ?>
                                                                            </td>
                                                                        </tr>
                                                                    <? endif; ?>
                                                                    <tr>
                                                                        <td height="20" align="left" valign="middle"
                                                                            class="textoazul"><?= $auxiliar->traduce("Ruta", $administrador->ID_IDIOMA) . ":"; ?>
                                                                        </td>
                                                                        <td align="left" valign="middle">
                                                                            <?
                                                                            $TamanoText = '180px';
                                                                            $ClassText  = "copyright";
                                                                            $MaxLength  = "150";
                                                                            $idTextBox  = 'txRuta';
                                                                            $jscript    = "onchange=\"document.FormSelect.idRuta.value=''\"";
                                                                            $html->TextBox("txRuta", $txRuta);
                                                                            unset($jscript);
                                                                            unset($idTextBox);
                                                                            ?>
                                                                            <input type="hidden" name="idRuta"
                                                                                   id="idRuta" value="<?= $idRuta ?>"/>
                                                                            <a href="<?= $pathRaiz ?>buscadores_maestros/busqueda_ruta.php?AlmacenarId=0"
                                                                               class="fancyboxRutas" id="rutas">
                                                                                <img
                                                                                    src="<?= $pathRaiz ?>imagenes/botones/listado.png"
                                                                                    alt="<?= $auxiliar->traduce("Buscar Ruta", $administrador->ID_IDIOMA) ?>"
                                                                                    name="Listado" border="0"
                                                                                    align="absbottom" id="Listado"/>
                                                                            </a> <span id="desplegable_rutas"
                                                                                       style="display: none;"> <img
                                                                                    src="<?= $pathClases; ?>lib/ajax_script/img/esperando.gif"
                                                                                    width="15" height="11"
                                                                                    alt="<?= $auxiliar->traduce("Buscando...", $administrador->ID_IDIOMA) ?>"/>
                                                                            </span>

                                                                            <div class="entry" align="left"
                                                                                 id="actualizador_rutas"></div>
                                                                            <script type="text/javascript"
                                                                                    language="JavaScript">
                                                                                new Ajax.Autocompleter('txRuta', 'actualizador_rutas', '<?=$pathRaiz?>buscadores_maestros/resp_ajax_ruta.php?AlmacenarId=0',
                                                                                    {
                                                                                        method: 'post',
                                                                                        indicator: 'desplegable_rutas',
                                                                                        minChars: '2',
                                                                                        afterUpdateElement: function (textbox, valor) {
                                                                                            siguiente_control(jQuery('#' + this.paramName));
                                                                                            jQuery(textbox).val(jQuery(valor).children('a').attr('alt'));//VALOR DEL PAR&Aacute;METRO ALT DEL ENLACE <a>
                                                                                            jQuery('#idRuta').val(jQuery(valor).children('a').attr('rev'));
                                                                                        }
                                                                                    }
                                                                                );
                                                                            </script>
                                                                        </td>
                                                                        <td align="center" valign="top">&nbsp;</td>
                                                                        <!--<td height="20" align="left" valign="middle"
                                                                            class="textoazul"><?= $auxiliar->traduce("Subruta", $administrador->ID_IDIOMA) . ":"; ?>
                                                                        </td>
                                                                        <td align="left" valign="middle">
                                                                            <?
                                                                            $TamanoText = '180px';
                                                                            $ClassText  = "copyright";
                                                                            $MaxLength  = "150";
                                                                            $idTextBox  = 'txSubruta';
                                                                            $jscript    = "onchange=\"document.FormSelect.idSubruta.value=''\"";
                                                                            $html->TextBox("txSubruta", $txSubruta);
                                                                            unset($jscript);
                                                                            unset($idTextBox);
                                                                            ?>
                                                                            <input type="hidden" name="idSubruta"
                                                                                   id="idSubruta"
                                                                                   value="<?= $idSubruta ?>"/>
                                                                            <a href="<?= $pathRaiz ?>buscadores_maestros/busqueda_subruta.php?AlmacenarId=0"
                                                                               class="fancyboxSubrutas"
                                                                               id="subrutas"> <img
                                                                                    src="<?= $pathRaiz ?>imagenes/botones/listado.png"
                                                                                    alt="<?= $auxiliar->traduce("Buscar Subruta", $administrador->ID_IDIOMA) ?>"
                                                                                    name="Listado" border="0"
                                                                                    align="absbottom" id="Listado"/>
                                                                            </a> <span
                                                                                id="desplegable_subrutas"
                                                                                style="display: none;"> <img
                                                                                    src="<?= $pathClases; ?>lib/ajax_script/img/esperando.gif"
                                                                                    width="15" height="11"
                                                                                    alt="<?= $auxiliar->traduce("Buscando...", $administrador->ID_IDIOMA) ?>"/>
                                                                            </span>

                                                                            <div class="entry" align="left"
                                                                                 id="actualizador_subrutas"></div>
                                                                            <script type="text/javascript"
                                                                                    language="JavaScript">
                                                                                new Ajax.Autocompleter('txSubruta', 'actualizador_subrutas', '<?=$pathRaiz?>buscadores_maestros/resp_ajax_subruta.php?AlmacenarId=0',
                                                                                    {
                                                                                        method: 'post',
                                                                                        indicator: 'desplegable_subrutas',
                                                                                        minChars: '2',
                                                                                        afterUpdateElement: function (textbox, valor) {
                                                                                            siguiente_control(jQuery('#' + this.paramName));
                                                                                            jQuery(textbox).val(jQuery(valor).children('a').attr('alt'));//VALOR DEL PAR&Aacute;METRO ALT DEL ENLACE <a>
                                                                                            jQuery('#idSubruta').val(jQuery(valor).children('a').attr('rev'));
                                                                                        }
                                                                                    }
                                                                                );
                                                                            </script>
                                                                        </td>-->
                                                                        <td height="20" align="left" valign="middle"
                                                                            class="textoazul"><?= $auxiliar->traduce("Precio Fijo", $administrador->ID_IDIOMA) . ":"; ?>
                                                                        </td>
                                                                        <td align="left" valign="middle">
                                                                            <?
                                                                            $NombreSelect                 = 'selPrecioFijo';
                                                                            $Elementos_precio[0]['text']  = $auxiliar->traduce("Todos", $administrador->ID_IDIOMA);
                                                                            $Elementos_precio[0]['valor'] = 'Todos';
                                                                            $Elementos_precio[1]['text']  = $auxiliar->traduce("Si", $administrador->ID_IDIOMA);
                                                                            $Elementos_precio[1]['valor'] = 'Si';
                                                                            $Elementos_precio[2]['text']  = $auxiliar->traduce("No", $administrador->ID_IDIOMA);
                                                                            $Elementos_precio[2]['valor'] = 'No';
                                                                            $Tamano                       = '205px';
                                                                            $Estilo                       = "copyright";
                                                                            if (!isset($selPrecioFijo)):
                                                                                $selPrecioFijo = "Todos";
                                                                            else:
                                                                                $selPrecioFijo = $selPrecioFijo;
                                                                            endif;
                                                                            $html->SelectArr($NombreSelect, $Elementos_precio, $selPrecioFijo, $selPrecioFijo);
                                                                            ?>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td height="20" align="left" valign="middle"
                                                                            class="textoazul"><?= $auxiliar->traduce("Mostrar Ubicaciones Vacias", $administrador->ID_IDIOMA) . ":"; ?>
                                                                        </td>
                                                                        <td align="left" valign="middle">
                                                                            <?
                                                                            $NombreSelect                             = 'selMostrarUbicacionesVacias';
                                                                            $Elementos_ubicaciones_vacias[0]['text']  = $auxiliar->traduce("Todas", $administrador->ID_IDIOMA);
                                                                            $Elementos_ubicaciones_vacias[0]['valor'] = 'Todos';
                                                                            $Elementos_ubicaciones_vacias[1]['text']  = $auxiliar->traduce("Si", $administrador->ID_IDIOMA);
                                                                            $Elementos_ubicaciones_vacias[1]['valor'] = 'Si';
                                                                            $Elementos_ubicaciones_vacias[2]['text']  = $auxiliar->traduce("No", $administrador->ID_IDIOMA);
                                                                            $Elementos_ubicaciones_vacias[2]['valor'] = 'No';
                                                                            $Tamano                                   = '205px';
                                                                            $Estilo                                   = "copyright";
                                                                            if (!isset($selMostrarUbicacionesVacias)):
                                                                                $selMostrarUbicacionesVacias = "Todos";
                                                                            else:
                                                                                $selMostrarUbicacionesVacias = $selMostrarUbicacionesVacias;
                                                                            endif;
                                                                            $html->SelectArr($NombreSelect, $Elementos_ubicaciones_vacias, $selMostrarUbicacionesVacias, $selMostrarUbicacionesVacias);
                                                                            ?>
                                                                        </td>
                                                                        <td align="center" valign="top">&nbsp;</td>
                                                                        <td height="20" align="left" valign="middle"
                                                                            class="textoazul"><?= $auxiliar->traduce("Ubicaciones de Pendientes", $administrador->ID_IDIOMA) . ":"; ?>
                                                                        </td>
                                                                        <td align="left" valign="middle">
                                                                            <?
                                                                            $NombreSelect                                    = 'selMostrarUbicacionesDePendientes';
                                                                            $Elementos_ubicaciones_de_pendientes[0]['text']  = $auxiliar->traduce("Todas", $administrador->ID_IDIOMA);
                                                                            $Elementos_ubicaciones_de_pendientes[0]['valor'] = 'Todos';
                                                                            $Elementos_ubicaciones_de_pendientes[1]['text']  = $auxiliar->traduce("Si", $administrador->ID_IDIOMA);
                                                                            $Elementos_ubicaciones_de_pendientes[1]['valor'] = 'Si';
                                                                            $Elementos_ubicaciones_de_pendientes[2]['text']  = $auxiliar->traduce("No", $administrador->ID_IDIOMA);
                                                                            $Elementos_ubicaciones_de_pendientes[2]['valor'] = 'No';
                                                                            $Tamano                                          = '205px';
                                                                            $Estilo                                          = "copyright";
                                                                            if (!isset($selMostrarUbicacionesDePendientes)):
                                                                                $selMostrarUbicacionesDePendientes = "Todos";
                                                                            else:
                                                                                $selMostrarUbicacionesDePendientes = $selMostrarUbicacionesDePendientes;
                                                                            endif;
                                                                            $html->SelectArr($NombreSelect, $Elementos_ubicaciones_de_pendientes, $selMostrarUbicacionesDePendientes, $selMostrarUbicacionesDePendientes);
                                                                            ?>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td height="20" align="left" valign="middle"
                                                                            class="textoazul"><?= $auxiliar->traduce("Autostore", $administrador->ID_IDIOMA) . ":"; ?>
                                                                        </td>
                                                                        <td align="left" valign="middle"><?
                                                                            $NombreSelect               = 'selAutostore';
                                                                            $Elementos_autostore[0]['text']  = $auxiliar->traduce("Todos", $administrador->ID_IDIOMA);
                                                                            $Elementos_autostore[0]['valor'] = 'Todos';
                                                                            $Elementos_autostore[1]['text']  = $auxiliar->traduce("Si", $administrador->ID_IDIOMA);
                                                                            $Elementos_autostore[1]['valor'] = 'Si';
                                                                            $Elementos_autostore[2]['text']  = $auxiliar->traduce("No", $administrador->ID_IDIOMA);
                                                                            $Elementos_autostore[2]['valor'] = 'No';
                                                                            $Tamano                     = '205px';
                                                                            $Estilo                     = "copyright";

                                                                            $html->SelectArr($NombreSelect, $Elementos_autostore, $selAutostore, $selAutostore);
                                                                            ?>
                                                                        </td>
                                                                        <td align="center" valign="top">&nbsp;</td>
                                                                        <td height="20" align="left" valign="middle"
                                                                            class="textoazul">

                                                                        </td>
                                                                        <td align="left" valign="middle">

                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td height="20" align="left" valign="middle"
                                                                            class="textoazul"><?= $auxiliar->traduce("Baja", $administrador->ID_IDIOMA) . ":"; ?>
                                                                        </td>
                                                                        <td align="left" valign="middle"><?
                                                                            $NombreSelect               = 'selBaja';
                                                                            $Elementos_baja[0]['text']  = $auxiliar->traduce("Todos", $administrador->ID_IDIOMA);
                                                                            $Elementos_baja[0]['valor'] = 'Todos';
                                                                            $Elementos_baja[1]['text']  = $auxiliar->traduce("Si", $administrador->ID_IDIOMA);
                                                                            $Elementos_baja[1]['valor'] = 'Si';
                                                                            $Elementos_baja[2]['text']  = $auxiliar->traduce("No", $administrador->ID_IDIOMA);
                                                                            $Elementos_baja[2]['valor'] = 'No';
                                                                            $Tamano                     = '205px';
                                                                            $Estilo                     = "copyright";
                                                                            if (!isset($selBaja)):
                                                                                $selBaja = "No";
                                                                            else:
                                                                                $selBaja = $selBaja;
                                                                            endif;
                                                                            $html->SelectArr($NombreSelect, $Elementos_baja, $selBaja, $selBaja);
                                                                            ?>
                                                                        </td>
                                                                        <td align="center" valign="top">&nbsp;</td>
                                                                    </tr>

                                                                </table>
                                                            </td>
                                                            <td width="4" bgcolor="#D9E3EC" class="lineaderecha">&nbsp;
                                                            </td>
                                                        </tr>
                                                        <tr bgcolor="#D9E3EC">
                                                            <td height="5" colspan="3" bgcolor="#D9E3EC"
                                                                class="lineabajodereizq"><img
                                                                    src="<? echo $pathRaiz ?>imagenes/transparente.gif"
                                                                    width="10" height="5"/></td>
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
                                                    <div align="right">
                                                        <span class="senaladoazul">
                                                            <? if ((($pantallaSolar != 1) && ($pantallaConstruccion != 1))): //LAS PANTALLAS DE CONSTRUCCION SON INFORMATIVAS?>
                                                                <!--VENTANA OPCIONES EMERGENTE CREAR UBICACION PREVENTIVO PENDIENTES -->
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
                                                                        <? echo $auxiliar->traduce("Crear Ubicaciones Preventivo Pendientes", $administrador->ID_IDIOMA) ?>
                                                                        &nbsp;&nbsp;&nbsp;&nbsp;
                                                                    </a>
                                                                    <ul>
                                                                        <li>
                                                                            <a href="ficha_importacion_preventivo_excel_paso1.php"
                                                                               class="copyright botones">
                                                                                <img
                                                                                    src="<?= $pathRaiz ?>imagenes/excel.png"
                                                                                    border="0"/>
                                                                                &nbsp;&nbsp;&nbsp;
                                                                                <?= $auxiliar->traduce("Importacion Masiva", $administrador->ID_IDIOMA) . "(Excel)" ?>
                                                                                &nbsp;&nbsp;&nbsp;
                                                                            </a>
                                                                        </li>
                                                                        <li>
                                                                            <a href="ficha_importacion_preventivo_csv_paso1.php"
                                                                               class="copyright botones">
                                                                                <img
                                                                                    src="<?= $pathRaiz ?>imagenes/add_document.png"
                                                                                    border="0"/>
                                                                                &nbsp;&nbsp;&nbsp;
                                                                                <?= $auxiliar->traduce("Importacion Masiva", $administrador->ID_IDIOMA) . "(CSV)" ?>
                                                                                &nbsp;&nbsp;&nbsp;
                                                                            </a>
                                                                        </li>
                                                                        <li>
                                                                            <a href="ficha_importacion_preventivo_copiar_pegar_paso1.php"
                                                                               class="copyright botones">
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
                                                            <!--FIN VENTANA OPCIONES EMERGENTE CREAR UBICACION PREVENTIVO PENDIENTES -->

                                                                <a href="ficha_importacion_paso1.php"
                                                                   class="senaladoverde">

                                                                    &nbsp;&nbsp;&nbsp;<?= $auxiliar->traduce("Importacion masiva", $administrador->ID_IDIOMA) ?>
                                                                    &nbsp;&nbsp;&nbsp;</a>
                                                             <!--VENTANA OPCIONES EMERGENTE DE LINEAS -->


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
                                                                        <? echo $auxiliar->traduce("Acciones de linea", $administrador->ID_IDIOMA) ?>
                                                                        &nbsp;&nbsp;&nbsp;&nbsp;
                                                                    </a>
                                                                    <ul>
                                                                        <li>
                                                                            <? if ($txAlmacen != ""): ?>
                                                                                <a href="#" class="copyright botones"
                                                                                   onClick="return darBajaUbicaciones();">
                                                                                    <img
                                                                                        src="<?= $pathRaiz ?>imagenes/menos.png"
                                                                                        name="DeshacerAnulaciones"
                                                                                        border="0"
                                                                                        id="DeshacerAnulaciones"/>
                                                                                    &nbsp;&nbsp;&nbsp;<?= $auxiliar->traduce("Baja masiva", $administrador->ID_IDIOMA) ?>
                                                                                    &nbsp;&nbsp;&nbsp;</a>
                                                                            <? else: ?>
                                                                                <a href="#"
                                                                                   class="blancoDisabled botones"
                                                                                   style="color:#CCCCCC;"
                                                                                   title="<?= $auxiliar->traduce("Filtre por Almacen para poder hacer la Baja Masiva", $administrador->ID_IDIOMA) ?>"
                                                                                   onClick="return false;">
                                                                                    <img
                                                                                        src="<?= $pathRaiz ?>imagenes/menos.png"
                                                                                        name="DeshacerAnulaciones"
                                                                                        border="0"
                                                                                        id="DeshacerAnulaciones"/>
                                                                                    &nbsp;&nbsp;&nbsp;<?= $auxiliar->traduce("Baja masiva", $administrador->ID_IDIOMA) ?>
                                                                                    &nbsp;&nbsp;&nbsp;</a>
                                                                            <? endif; ?>
                                                                        </li>

                                                                    </ul>
                                                                </div>
                                                            <!--FIN VENTANA OPCIONES EMERGENTE DE LINEAS -->

                                                            <!--VENTANA OPCIONES IMPRESION -->


                                                                <div class="menu_herramientas"
                                                                     style="display: inline-block; ">
                                                                    <a href="#" id="btnAccionesLinea"
                                                                       onmouseenter="ventana_opciones(this,event);return false;"
                                                                       class="senaladoverde botones"
                                                                       style="white-space: nowrap;">
                                                                        &nbsp;&nbsp;
                                                                        <img src="<?= $pathRaiz ?>imagenes/print.gif"
                                                                             alt="Herramientas"
                                                                             height="16px" width="16px"
                                                                             style="vertical-align: middle;padding-bottom:2px;"/>
                                                                        <? echo $auxiliar->traduce("Opciones de impresion", $administrador->ID_IDIOMA) ?>
                                                                        &nbsp;&nbsp;&nbsp;&nbsp;
                                                                    </a>
                                                                    <ul>
                                                                        <li>
                                                                            <? if ($selTipoUbicacion == 'Gaveta'): ?>
                                                                                <a href="#" class="copyright botones"
                                                                                   onClick="return imprimirEtiquetasGavetas();">
                                                                                    &nbsp;&nbsp;&nbsp;<?= $auxiliar->traduce("Imprimir etiquetas Gavetas", $administrador->ID_IDIOMA) ?>
                                                                                    &nbsp;&nbsp;&nbsp;</a>
                                                                            <? else: ?>
                                                                                <a href="#"
                                                                                   class="blancoDisabled botones"
                                                                                   style="color:#CCCCCC;"
                                                                                   title="<?= $auxiliar->traduce("Filtre por Tipo de Ubicación 'Gaveta' para imprimir", $administrador->ID_IDIOMA) ?>"
                                                                                   onClick="return false;">
                                                                                    &nbsp;&nbsp;&nbsp;<?= $auxiliar->traduce("Imprimir etiquetas Gavetas", $administrador->ID_IDIOMA) ?>
                                                                                    &nbsp;&nbsp;&nbsp;</a>
                                                                            <? endif; ?>
                                                                        </li>
                                                                        <li>
                                                                            <? if ($selTipoUbicacion == 'Carro'): ?>
                                                                                <a href="#" class="copyright botones"
                                                                                   onClick="return imprimirEtiquetaCarro();">
                                                                                    &nbsp;&nbsp;&nbsp;<?= $auxiliar->traduce("Imprimir etiquetas Carro", $administrador->ID_IDIOMA) ?>
                                                                                    &nbsp;&nbsp;&nbsp;</a>
                                                                            <? else: ?>
                                                                                <a href="#"
                                                                                   class="blancoDisabled botones"
                                                                                   style="color:#CCCCCC;"
                                                                                   title="<?= $auxiliar->traduce("Filtre por Tipo de Ubicacion 'Carro' para imprimir", $administrador->ID_IDIOMA) ?>"
                                                                                   onClick="return false;">
                                                                                    &nbsp;&nbsp;&nbsp;<?= $auxiliar->traduce("Imprimir etiquetas Carro", $administrador->ID_IDIOMA) ?>
                                                                                    &nbsp;&nbsp;&nbsp;</a>
                                                                            <? endif; ?>
                                                                        </li>
                                                                        <li>
                                                                            <a href="#" class="copyright botones"
                                                                               onClick="return imprimirEtiquetas();">
                                                                                &nbsp;&nbsp;&nbsp;<?= $auxiliar->traduce("Imprimir etiquetas", $administrador->ID_IDIOMA) ?>
                                                                                &nbsp;&nbsp;&nbsp;</a>
                                                                        </li>
                                                                        <li>
                                                                            <a href="#" class="copyright botones"
                                                                               onClick="return imprimirEtiquetasCode39();">
                                                                                &nbsp;&nbsp;&nbsp;<?= $auxiliar->traduce("Imprimir etiquetas Code39", $administrador->ID_IDIOMA) ?>
                                                                                &nbsp;&nbsp;&nbsp;</a>
                                                                        </li>
                                                                    </ul>
                                                                </div>
                                                                <!--FIN VENTANA OPCIONES IMPRESION -->

                                                            <? endif; ?>
                                                            <a href="ficha.php<?= ($pantallaSolar == 1 ? "?pantallaSolar=1" : ($pantallaConstruccion == 1 ? "?pantallaConstruccion=1" : "")); ?>"
                                                               class="senaladoverde">
                                                                &nbsp;&nbsp;&nbsp;&nbsp;<?= $auxiliar->traduce("Crear", $administrador->ID_IDIOMA) ?>
                                                                &nbsp;&nbsp;&nbsp;&nbsp;</a>

                                                            <? if ($Buscar == "Si"): ?>
                                                                <? if ((($pantallaSolar != 1) && ($pantallaConstruccion != 1))): //LAS PANTALLAS DE CONSTRUCCION SON INFORMATIVAS?>
                                                                    <a href="#" class="senaladoazul"
                                                                       onClick="exportarExcelGestionUbicaciones();return false">
                                                                        &nbsp;&nbsp;&nbsp;&nbsp;<?= $auxiliar->traduce("Excel Gestion Ubicaciones", $administrador->ID_IDIOMA) ?>
                                                                        &nbsp;&nbsp;&nbsp;&nbsp;</a>
                                                                <? endif; ?>
                                                                <a href="#" class="senaladoazul"
                                                                   onClick="exportarExcel();return false">&nbsp;&nbsp;&nbsp;&nbsp;<?= $auxiliar->traduce("Excel", $administrador->ID_IDIOMA) ?>
                                                                    &nbsp;&nbsp;&nbsp;&nbsp;</a>
                                                            <? else: ?>
                                                                <a href="#" class="senaladoazul" style="color:#CCCCCC;"
                                                                   title="<?= $auxiliar->traduce("Para poder exportar a excel gestion de ubicaciones primero realice una búsqueda", $administrador->ID_IDIOMA) ?>">
                                                                    &nbsp;&nbsp;&nbsp;&nbsp;<?= $auxiliar->traduce("Excel Gestion Ubicaciones", $administrador->ID_IDIOMA) ?>
                                                                    &nbsp;&nbsp;&nbsp;&nbsp;</a>
                                                                <a href="#" class="senaladoazul" style="color:#CCCCCC;"
                                                                   title="<?= $auxiliar->traduce("Para poder exportar a excel primero realice una búsqueda", $administrador->ID_IDIOMA) ?>">
                                                                    &nbsp;&nbsp;&nbsp;&nbsp;<?= $auxiliar->traduce("Excel", $administrador->ID_IDIOMA) ?>
                                                                    &nbsp;&nbsp;&nbsp;&nbsp;</a>
                                                            <? endif; ?>
                                                            <a id="btnBuscar" href="#" class="senaladoamarillo"
                                                               onClick="return buscar();">
                                                                &nbsp;&nbsp;&nbsp;&nbsp;<?= $auxiliar->traduce("Buscar", $administrador->ID_IDIOMA) ?>
                                                                &nbsp;&nbsp;&nbsp;&nbsp;</a>&nbsp;&nbsp;
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
                                                <!-- COMBO SUPERIOR REGISTROS-->
                                                <tr bgcolor="#D9E3EC" class="lineaabajo" height="22">
                                                    <td width="27%" class="copyright">
                                                        &nbsp;&nbsp;&nbsp;<?= $auxiliar->traduce("Ver", $administrador->ID_IDIOMA) ?>
                                                        &nbsp;#
                                                        <? $navegar->GenerarComboNumRegs($navegar->maxfilasMaestroUbicaciones, "selLimiteSuperior"); ?>
                                                        &nbsp;&nbsp;
                                                        <? $navegar->NumRegs($navegar->maxfilasMaestroUbicaciones, $maxahora, $navegar->numerofilasMaestroUbicaciones); ?>
                                                    </td>
                                                    <td width="73%" class="copyright">
                                                        <div
                                                            align="right"><? $navegar->Numeros($navegar->sqlAdminMaestroUbicaciones, $navegar->maxfilasMaestroUbicaciones, $navegar->numerofilasMaestroUbicaciones, $i, "index.php", "#2E8AF0") ?>
                                                            &nbsp;&nbsp;&nbsp;</div>
                                                    </td>
                                                </tr>
                                                <!--FIN COMBO -->
                                            <? endif ?>
                                            <? if ($Buscar != "Si"): ?>
                                                <tr>
                                                    <td height="19" align="center" valign="middle" bgcolor="#D9E3EC"
                                                        class="alertas3"><? echo $mensajeBuscar; ?></td>
                                                </tr>
                                                <?
                                            elseif ($numRegistros > 0): ?>
                                                <tr class="lineabajo">
                                                    <td colspan="2" align="center" bgcolor="#D9E3EC">
                                                        <table width="98%" cellpadding="0" cellspacing="2"
                                                               class="linealrededor">

                                                            <tr>
                                                                <td height="19" bgcolor="#2E8AF0" class="blanco">
                                                                    <div align="center">
                                                                        <?
                                                                        $valorCheck = '0';
                                                                        $jscript    = " onClick=\"seleccionarTodas(this)\" ";
                                                                        $Nombre     = 'chSelecTodas';
                                                                        $html->Option("chSelecTodas", "Check", "1", $valorCheck);
                                                                        $jscript = "";
                                                                        ?>
                                                                    </div>
                                                                </td>
                                                                <td height="19" bgcolor="#2E8AF0"
                                                                    class="blanco">
                                                                    <?
                                                                    if ((($pantallaSolar != 1) && ($pantallaConstruccion != 1))): //LAS PANTALLAS DE CONSTRUCCION SON INFORMATIVAS
                                                                        $navegar->GenerarColumna($auxiliar->traduce("Ubicación", $administrador->ID_IDIOMA), "enlaceCabecera", "ubicacion", $pathRaiz);
                                                                    else:
                                                                        $navegar->GenerarColumna($auxiliar->traduce("Unidad Organizativa", $administrador->ID_IDIOMA), "enlaceCabecera", "ubicacion", $pathRaiz);
                                                                    endif;
                                                                    ?>
                                                                </td>
                                                                <td height="19" bgcolor="#2E8AF0"
                                                                    class="blanco">
                                                                    <? $navegar->GenerarColumna($auxiliar->traduce("Centro", $administrador->ID_IDIOMA), "enlaceCabecera", "centro", $pathRaiz); ?>
                                                                </td>
                                                                <td height="19" bgcolor="#2E8AF0"
                                                                    class="blanco">
                                                                    <?
                                                                    if ((($pantallaSolar != 1) && ($pantallaConstruccion != 1))): //LAS PANTALLAS DE CONSTRUCCION SON INFORMATIVAS
                                                                        $navegar->GenerarColumna($auxiliar->traduce("Almacén", $administrador->ID_IDIOMA), "enlaceCabecera", "almacen", $pathRaiz);
                                                                    else:
                                                                        $navegar->GenerarColumna($auxiliar->traduce("Instalacion", $administrador->ID_IDIOMA), "enlaceCabecera", "almacen", $pathRaiz);
                                                                    endif;
                                                                    ?>
                                                                </td>
                                                                <td height="19" bgcolor="#2E8AF0"
                                                                    class="blanco"><? $navegar->GenerarColumna($auxiliar->traduce("Centro Físico", $administrador->ID_IDIOMA), "enlaceCabecera", "centro_fisico", $pathRaiz) ?></td>
                                                                <td height="19" bgcolor="#2E8AF0"
                                                                    class="blanco">
                                                                    <?
                                                                    if ((($pantallaSolar != 1) && ($pantallaConstruccion != 1))): //LAS PANTALLAS DE CONSTRUCCION SON INFORMATIVAS
                                                                        $navegar->GenerarColumna($auxiliar->traduce("Tipo ubicación", $administrador->ID_IDIOMA), "enlaceCabecera", "tipo_ubicacion", $pathRaiz);
                                                                    else:
                                                                        echo $auxiliar->traduce("Tipo Unidad Organizativa", $administrador->ID_IDIOMA);
                                                                    endif;
                                                                    ?>
                                                                </td>
                                                                <? if ($selTipoUbicacion == 'Sector'): ?>
                                                                    <td height="19" bgcolor="#2E8AF0"
                                                                        class="blanco"><? echo $auxiliar->traduce("Cantidad Paneles", $administrador->ID_IDIOMA); ?></td>
                                                                    <td height="19" bgcolor="#2E8AF0"
                                                                        class="blanco"><? echo $auxiliar->traduce("Cantidad Paneles Asignados", $administrador->ID_IDIOMA); ?></td>
                                                                <? endif; ?>
                                                                <? if ((($pantallaSolar == 1) || ($pantallaConstruccion == 1))): //LAS PANTALLAS DE CONSTRUCCION SON INFORMATIVAS?>
                                                                    <td height="19" bgcolor="#2E8AF0"
                                                                        class="blanco"><? echo $auxiliar->traduce("Potencia Megavatio Pico", $administrador->ID_IDIOMA) ?>
                                                                    </td>
                                                                    <td height="19" bgcolor="#2E8AF0"
                                                                        class="blanco"><? echo $auxiliar->traduce("Nº Paneles PB", $administrador->ID_IDIOMA) ?>
                                                                    </td>
                                                                    <td height="19" bgcolor="#2E8AF0"
                                                                        class="blanco"><? echo $auxiliar->traduce("Potencia PB", $administrador->ID_IDIOMA) ?>
                                                                    </td>
                                                                <? endif; ?>
                                                                <td height="19" bgcolor="#2E8AF0"
                                                                    class="blanco"><? echo $auxiliar->traduce("Categoría ubicación", $administrador->ID_IDIOMA) ?></td>
                                                                <td height="19" bgcolor="#2E8AF0"
                                                                    class="blanco"><? $navegar->GenerarColumna($auxiliar->traduce("Clase APQ", $administrador->ID_IDIOMA), "enlaceCabecera", "clase_apq", $pathRaiz) ?></td>
                                                                <td height="19" bgcolor="#2E8AF0"
                                                                    class="blanco"><? $navegar->GenerarColumna($auxiliar->traduce("Precio Fijo", $administrador->ID_IDIOMA), "enlaceCabecera", "precio_fijo", $pathRaiz) ?></td>
                                                                <td height="19" bgcolor="#2E8AF0"
                                                                    class="blanco">
                                                                    <?
                                                                    if ((($pantallaSolar != 1) && ($pantallaConstruccion != 1))): //LAS PANTALLAS DE CONSTRUCCION SON INFORMATIVAS
                                                                        $navegar->GenerarColumna($auxiliar->traduce("Id ubicación", $administrador->ID_IDIOMA), "enlaceCabecera", "id_ubicacion", $pathRaiz);
                                                                    else:
                                                                        $navegar->GenerarColumna($auxiliar->traduce("Id. Unidad Organizativa", $administrador->ID_IDIOMA), "enlaceCabecera", "id_ubicacion", $pathRaiz);
                                                                    endif;
                                                                    ?>
                                                                </td>
                                                                <td height="19" bgcolor="#2E8AF0"
                                                                    class="blanco"><? $navegar->GenerarColumna($auxiliar->traduce("Descripción", $administrador->ID_IDIOMA), "enlaceCabecera", "descripcion", $pathRaiz) ?></td>
                                                                <td height="19" bgcolor="#2E8AF0"
                                                                    class="blanco"><? $navegar->GenerarColumna($auxiliar->traduce("Autostore", $administrador->ID_IDIOMA), "enlaceCabecera", "autostore", $pathRaiz) ?></td>
                                                                <td height="19" bgcolor="#2E8AF0"
                                                                    class="blanco"><? $navegar->GenerarColumna($auxiliar->traduce("Baja", $administrador->ID_IDIOMA), "enlaceCabecera", "baja", $pathRaiz) ?></td>
                                                            </tr>
                                                            <? // MUESTRO LAS COINCIDENCIAS CON LA BUSQUEDA
                                                            $i = 0;
                                                            // PARA LA NUMERACION DE CADA URL
                                                            $numeracion = $mostradas + 1;
                                                            while ($i < $maxahora):
                                                                $row                         = $bd->SigReg($resultado);
                                                                //COLOR DE LA FILA
                                                                if ($i % 2 == 0) $myColor = "#B3C7DA";
                                                                else $myColor = "#AACFF9";

                                                                //CALCULO EL NUMERO DE PANELES ASIGNADOS
                                                                $sqlNumPanelesAsignados    = "SELECT IF(SUM(STOCK_TOTAL) IS NULL, 0, SUM(STOCK_TOTAL)) AS NUM_PANELES_ASIGNADOS
                                                                                            FROM MATERIAL_UBICACION MU
                                                                                            INNER JOIN MATERIAL_FISICO MF ON MF.ID_MATERIAL_FISICO = MU.ID_MATERIAL_FISICO
                                                                                            WHERE MU.ACTIVO = 1 AND MU.ID_UBICACION = $row->ID_UBICACION";
                                                                $resultNumPanelesAsignados = $bd->ExecSQL($sqlNumPanelesAsignados);
                                                                $rowNumPanelesAsignados    = $bd->SigReg($resultNumPanelesAsignados);

                                                                //SI TIENE TIPO UOP
                                                                $idUnidadOrganizativaProceso = $row->ID_UNIDAD_ORGANIZATIVA_PROCESO;
                                                                $txUnidadOrganizativaProceso = "-";
                                                                if ($row->ID_UNIDAD_ORGANIZATIVA_PROCESO != NULL):
                                                                    //BUSCAMOS LA UOP
                                                                    $rowUOP = $bd->VerReg("UNIDAD_ORGANIZATIVA_PROCESO", "ID_UNIDAD_ORGANIZATIVA_PROCESO", $row->ID_UNIDAD_ORGANIZATIVA_PROCESO);

                                                                    //ASIGNAMOS VALOR
                                                                    $txUnidadOrganizativaProceso = ($administrador->ID_IDIOMA == "ESP" ? $rowUOP->TIPO_UOP_ESP : $rowUOP->TIPO_UOP_ENG);
                                                                endif;
                                                                ?>
                                                                <tr align="center" valign="middle" bgcolor="#AACFF9">
                                                                    <td height="18" align="left"
                                                                        bgcolor="<? echo $myColor ?>"
                                                                        class="enlaceceldas">
                                                                        <div align="center">
                                                                            <?
                                                                            $valorCheck = $chSelec[$row->ID_UBICACION];
                                                                            if ($chTodos):
                                                                                $disabled   = 'disabled="disabled"';
                                                                                $valorCheck = 1;
                                                                            endif;
                                                                            $html->Option('chSelec_' . $row->ID_UBICACION, "Check", "1", $valorCheck);
                                                                            unset($disabled);
                                                                            ?>
                                                                        </div>
                                                                    </td>
                                                                    <td height="18" align="left"
                                                                        bgcolor="<? echo $myColor ?>"
                                                                        class="enlaceceldas">
                                                                        &nbsp;<a
                                                                            href="ficha.php?idUbicacion=<? echo $row->ID_UBICACION; ?><?= ($pantallaSolar == 1 ? "&pantallaSolar=1" : ($pantallaConstruccion == 1 ? "&pantallaConstruccion=1" : "")); ?>"
                                                                            class="enlaceceldasacceso"><? echo $row->UBICACION ?></a>&nbsp;
                                                                    </td>
                                                                    <td height="18" align="left"
                                                                        bgcolor="<? echo $myColor ?>"
                                                                        class="enlaceceldas"
                                                                        title="<?= $row->REF_CENTRO . ' - ' . $row->NOM_CENTRO ?>">
                                                                        &nbsp;<?= $row->REF_CENTRO ?></td>
                                                                    <td height="18" align="left"
                                                                        bgcolor="<? echo $myColor ?>"
                                                                        class="enlaceceldas"
                                                                        title="<?= $row->REF_ALMACEN . ' - ' . $row->NOM_ALMACEN ?>">
                                                                        &nbsp;<?= $row->REF_ALMACEN ?></td>
                                                                    <td height="18" align="left"
                                                                        bgcolor="<? echo $myColor ?>"
                                                                        class="enlaceceldas">
                                                                        &nbsp;<?= $row->REFERENCIA_CENTRO_FISICO ?></td>
                                                                    <td height="18" align="left"
                                                                        bgcolor="<? echo $myColor ?>"
                                                                        class="enlaceceldas">
                                                                        &nbsp;
                                                                        <?
                                                                        if ((($pantallaSolar != 1) && ($pantallaConstruccion != 1))): //LAS PANTALLAS DE CONSTRUCCION SON INFORMATIVAS
                                                                            echo($row->TIPO_UBICACION == "" ? $auxiliar->traduce("Estándar", $administrador->ID_IDIOMA) : $auxiliar->traduce($row->TIPO_UBICACION, $administrador->ID_IDIOMA));
                                                                        else:
                                                                            echo $txUnidadOrganizativaProceso;
                                                                        endif;
                                                                        ?>
                                                                    </td>
                                                                    <? if ($selTipoUbicacion == 'Sector'): ?>
                                                                        <td height="18" align="left"
                                                                            bgcolor="<? echo $myColor ?>"
                                                                            class="enlaceceldas">
                                                                            &nbsp;<? echo $auxiliar->formatoNumero($row->CANTIDAD_PANELES); ?>
                                                                            &nbsp;</td>
                                                                        <td height="18" align="left"
                                                                            bgcolor="<? echo $myColor ?>"
                                                                            class="enlaceceldas">
                                                                            &nbsp;<? echo $auxiliar->formatoNumero($rowNumPanelesAsignados->NUM_PANELES_ASIGNADOS); ?>
                                                                            &nbsp;</td>
                                                                    <? endif; ?>
                                                                    <? if ((($pantallaSolar == 1) || ($pantallaConstruccion == 1))): //LAS PANTALLAS DE CONSTRUCCION SON INFORMATIVAS
                                                                        ?>
                                                                        <td height="18" align="left"
                                                                            bgcolor="<? echo $myColor ?>"
                                                                            class="enlaceceldas">
                                                                            &nbsp;<? echo $auxiliar->formatoNumero($row->POTENCIA_PMW_POWERBLOCK); ?>
                                                                            &nbsp;</td>
                                                                        <td height="18" align="left"
                                                                            bgcolor="<? echo $myColor ?>"
                                                                            class="enlaceceldas">
                                                                            &nbsp;<? echo $auxiliar->formatoNumero($row->CANTIDAD_PANELES_POWERBLOCK); ?>
                                                                            &nbsp;</td>
                                                                        <td height="18" align="left"
                                                                            bgcolor="<? echo $myColor ?>"
                                                                            class="enlaceceldas">
                                                                            &nbsp;<? echo $auxiliar->formatoNumero($row->POTENCIA_PMW_POWERBLOCK * $row->CANTIDAD_PANELES_POWERBLOCK); ?>
                                                                            &nbsp;</td>
                                                                    <? endif; ?>
                                                                    <?
                                                                    //BUSCO EL TIPO DE CATEGORIA UBICACION
                                                                    $NotificaErrorPorEmail = "No";
                                                                    $rowCategoriaUbicacion = $bd->VerReg("UBICACION_CATEGORIA", "ID_UBICACION_CATEGORIA", $row->ID_UBICACION_CATEGORIA, "No");
                                                                    unset($NotificaErrorPorEmail);
                                                                    ?>
                                                                    <td height="18" align="left"
                                                                        bgcolor="<? echo $myColor ?>"
                                                                        class="enlaceceldas">
                                                                        &nbsp;<?= ($row->ID_UBICACION_CATEGORIA == NULL ? "-" : ($row->ID_UBICACION_CATEGORIA . ' - ' . $rowCategoriaUbicacion->NOMBRE)) ?></td>
                                                                    <td height="18" align="left"
                                                                        bgcolor="<? echo $myColor ?>"
                                                                        class="enlaceceldas">
                                                                        &nbsp;<?= ($row->CLASE_APQ == NULL ? "-" : $row->CLASE_APQ) ?></td>
                                                                    <td height="18" align="center"
                                                                        bgcolor="<? echo $myColor ?>"
                                                                        class="enlaceceldas">
                                                                        &nbsp;
                                                                        <?
                                                                        if ($row->PRECIO_FIJO == '0'):
                                                                            echo $auxiliar->traduce("No", $administrador->ID_IDIOMA);
                                                                        elseif ($row->PRECIO_FIJO == '1'):
                                                                            echo $auxiliar->traduce("Si", $administrador->ID_IDIOMA);
                                                                        endif;
                                                                        ?>
                                                                    </td>
                                                                    <td height="18" align="left"
                                                                        bgcolor="<? echo $myColor ?>"
                                                                        class="enlaceceldas">
                                                                        &nbsp;<? echo $row->ID_UBICACION ?>&nbsp;</td>
                                                                    <td height="18" align="center"
                                                                        bgcolor="<? echo $myColor ?>"
                                                                        class="enlaceceldas"
                                                                        title="<? echo $row->DESCRIPCION ?>">&nbsp;
                                                                        <? if (strlen( (string)trim( (string)$row->DESCRIPCION)) > 0): ?>
                                                                            <img src="../../imagenes/form.png"
                                                                                 border="0" align="absbottom" width="15"
                                                                                 height="15"
                                                                                 title="<? echo $row->DESCRIPCION ?>"/>
                                                                            <?
                                                                        else:
                                                                            echo "-";
                                                                        endif;
                                                                        ?>
                                                                    </td>
                                                                    <td height="18" align="center"
                                                                        bgcolor="<? echo $myColor ?>"
                                                                        class="enlaceceldas">
                                                                        &nbsp;
                                                                        <?
                                                                        if ($row->AUTOSTORE == '0'):
                                                                            echo $auxiliar->traduce("No", $administrador->ID_IDIOMA);
                                                                        elseif ($row->AUTOSTORE == '1'):
                                                                            echo $auxiliar->traduce("Si", $administrador->ID_IDIOMA);
                                                                        endif;
                                                                        ?>
                                                                    </td>
                                                                    <td height="18" align="center"
                                                                        bgcolor="<? echo $myColor ?>"
                                                                        class="enlaceceldas">
                                                                        &nbsp;
                                                                        <?
                                                                        if ($row->BAJA == '0'):
                                                                            echo $auxiliar->traduce("No", $administrador->ID_IDIOMA);
                                                                        elseif ($row->BAJA == '1'):
                                                                            echo $auxiliar->traduce("Si", $administrador->ID_IDIOMA);
                                                                        endif;
                                                                        ?>
                                                                    </td>
                                                                </tr>
                                                                <? $i++;
                                                                $numeracion++;
                                                            endwhile; ?>
                                                        </table>
                                                    </td>
                                                </tr>
                                                <?
                                            else: ?>
                                                <tr>
                                                    <td colspan="2" align="center" bgcolor="#D9E3EC" class="alertas3"
                                                        height="19px"><?= $auxiliar->traduce("No existen registros para la búsqueda realizada", $administrador->ID_IDIOMA) ?></td>
                                                </tr>
                                            <? endif; ?>
                                            <? if ($numRegistros > 0): ?>
                                                <tr bgcolor="#D9E3EC" class="lineaabajo" height="22">
                                                    <td width="27%" class="copyright">
                                                        &nbsp;&nbsp;&nbsp;<?= $auxiliar->traduce("Ver", $administrador->ID_IDIOMA) ?>
                                                        &nbsp;#
                                                        <? $navegar->GenerarComboNumRegs($navegar->maxfilasMaestroUbicaciones); ?>
                                                        &nbsp;&nbsp;
                                                        <? $navegar->NumRegs($navegar->maxfilasMaestroUbicaciones, $maxahora, $navegar->numerofilasMaestroUbicaciones); ?>
                                                    </td>
                                                    <td width="73%" class="copyright">
                                                        <div
                                                            align="right"><? $navegar->Numeros($navegar->sqlAdminMaestroUbicaciones, $navegar->maxfilasMaestroUbicaciones, $navegar->numerofilasMaestroUbicaciones, $i, "index.php", "#2E8AF0") ?>
                                                            &nbsp;&nbsp;&nbsp;</div>
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