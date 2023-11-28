<?
//global $cli;
global $administrador;
global $bd;
global $auxiliar;

// PATHS DE LA WEB
$pathRaiz          = "../../";
$pathClases        = "../../../";
$tituloPag         = $auxiliar->traduce("Ubicaciones", $administrador->ID_IDIOMA);
$tituloNav         = $auxiliar->traduce("Maestros", $administrador->ID_IDIOMA) . " >> " . $auxiliar->traduce("Ubicaciones", $administrador->ID_IDIOMA);
$ZonaTablaPadre    = "Maestros";
$ZonaSubTablaPadre = "MaestrosSubmenuAlmacen";
$ZonaTabla         = "MaestrosUbicaciones";

$tituloErrArriba = strtr( (string)strtoupper( (string)$auxiliar->traduce("Error Ubicación", $administrador->ID_IDIOMA)), "àèìòùáéíóúçñäëïöü", "ÀÈÌÒÙÁÉÍÓÚÇÑÄËÏÖÜ");
$tituloErr       = "ERROR";

$textoErr == "";
include $pathRaiz . "errores_genericos.php";

if ($TipoError == "UbicacionExistente"):
    $textoErr = $auxiliar->traduce("Ya existe otra ubicación en el mismo almacén con estos datos", $administrador->ID_IDIOMA);
elseif ($TipoError == "ErrorDatosAlmacen"):
    $textoErr = $auxiliar->traduce("Error de datos en el almacén seleccionado", $administrador->ID_IDIOMA);
elseif ($TipoError == "ErrorDatosAlmacenDestinoGaveta"):
    $textoErr = $auxiliar->traduce("Error de datos en el almacén seleccionado como destino de la gaveta", $administrador->ID_IDIOMA);
elseif ($TipoError == "AlmacenDestinoGavetaIgualAlmacenOrigen"):
    $textoErr = $auxiliar->traduce("El almacén de destino de la gaveta no puede ser el mismo que almacén al que pertenece la gaveta", $administrador->ID_IDIOMA);
elseif ($TipoError == "GavetaConMaterial"):
    $textoErr = $auxiliar->traduce("No se puede modificar el almacén de destino de la gaveta porque contiene materiales", $administrador->ID_IDIOMA);
elseif ($TipoError == "UbicacionConMaterialUbicado"):
    $textoErr = $auxiliar->traduce("La ubicación que intenta dar de baja tiene material ubicado", $administrador->ID_IDIOMA);
elseif ($TipoError == "UbicacionEnInventario"):
    $textoErr = $auxiliar->traduce("La ubicación que intenta dar de baja está asociada a una orden de conteo", $administrador->ID_IDIOMA);
elseif ($TipoError == "ErrorLongitudPasilloIncorrecta"):
    $textoErr = $auxiliar->traduce("La longitud para el campo pasillo gaveta es incorrecta", $administrador->ID_IDIOMA);
elseif ($TipoError == "ErrorLongitudProfundidadIncorrecta"):
    $textoErr = $auxiliar->traduce("La longitud para el campo profundidad gaveta es incorrecta", $administrador->ID_IDIOMA);
elseif ($TipoError == "GavetaAlmacenRepetida"):
    $textoErr = $auxiliar->traduce("Ya existe otra gaveta con el mismo almacén de origen y almacén de destino", $administrador->ID_IDIOMA);
elseif ($TipoError == "AlmacenDestinoGavetaSinRuta"):
    $textoErr = $auxiliar->traduce("El almacén de destino de la gaveta no tiene ruta asignada", $administrador->ID_IDIOMA);
elseif ($TipoError == "AlmacenDestinoGavetaSinSubRuta"):
    $textoErr = $auxiliar->traduce("El almacén de destino de la gaveta no tiene subruta asignada", $administrador->ID_IDIOMA);
elseif ($TipoError == "UbicacionConMaterial"):
    $textoErr = $auxiliar->traduce("El tipo de la ubicación no puede ser modificado ya que esta contiene material", $administrador->ID_IDIOMA);
elseif ($TipoError == "CantidadPenelesCambiadoExistiendoStock"):
    $textoErr = $auxiliar->traduce("No puede ser modificada la cantidad de paneles ya que esta ubicacion contiene material", $administrador->ID_IDIOMA);
elseif ($TipoError == "AlmacenNoInstalacion"):
    $textoErr = $auxiliar->traduce("El Almacen no es de tipo Contruccion:Instalacion", $administrador->ID_IDIOMA);
elseif ($TipoError == "ErrorDatosCategoriaUbicacion"):
    $textoErr = $auxiliar->traduce("Error de datos en la categoría seleccionada de la ubicación", $administrador->ID_IDIOMA);
elseif ($TipoError == "ErrorVariasUbicacionEntradaPorAlmacen"):
    $textoErr = $auxiliar->traduce("No puede haber mas de una ubicacion de entrada por almacen", $administrador->ID_IDIOMA);
elseif ($TipoError == "ErrorVariasUbicacionSalidaPorAlmacen"):
    $textoErr = $auxiliar->traduce("No puede haber mas de una ubicacion de salida por almacen", $administrador->ID_IDIOMA);
elseif ($TipoError == "ErrorVariasUbicacionEmbarquePorAlmacen"):
    $textoErr = $auxiliar->traduce("No puede haber mas de una ubicacion de embarque por almacen");
elseif ($TipoError == "ErrorVariasUbicacionConsumosMasivosPorAlmacen"):
    $textoErr = $auxiliar->traduce("No puede haber mas de una ubicacion de consumos masivos por almacen");
elseif ($TipoError == "ErrorVariasUbicacionRetornosMasivosPorAlmacen"):
    $textoErr = $auxiliar->traduce("No puede haber mas de una ubicacion de retornos masivos por almacen");
elseif ($TipoError == "ErrorRefUbicacionVacia"):
    $textoErr = $auxiliar->traduce("La referencia de ubicación es vacía");
elseif ($TipoError == "ErrorRefCentroVacia"):
    $textoErr = $auxiliar->traduce("La referencia de centro es vacía");
elseif ($TipoError == "ErrorRefAlmacenVacia"):
    $textoErr = $auxiliar->traduce("La referencia de almacén es vacía");
elseif ($TipoError == "ErrorCategoriaVacia"):
    $textoErr = $auxiliar->traduce("La categoría es vacía");
elseif ($TipoError == "ErrorProveedorNoEncontrado"):
    $textoErr = $auxiliar->traduce("Proveedor no encontrado para esa referencia");
elseif ($TipoError == "ErrorCentroNoEncontrado"):
    $textoErr = $auxiliar->traduce("Centro no encontrado para esa referencia");
elseif ($TipoError == "ErrorAlmacenNoEncontrado"):
    $textoErr = $auxiliar->traduce("Almacen de tipo 'acciona' no encontrado para ese centro");
elseif ($TipoError == "ErrorCategoriaNoEncontrado"):
    $textoErr = $auxiliar->traduce("Categoría no encontrada para esa referencia");
elseif ($TipoError == "ErrorUbicacionNoEstandar"):
    $textoErr = $auxiliar->traduce("No se puede modificar la ubicación porque no es de tipo 'Estandar'");
elseif ($TipoError == "ErrorUbicacionConStock"):
    $textoErr = $auxiliar->traduce("No se puede dar de baja la ubicación porque contiene stock");
elseif ($TipoError == "ErrorPrecioFijoNoValido"):
    $textoErr = $auxiliar->traduce("Valor no permitido para la columna 'Precio Fijo'");
elseif ($TipoError == "ErrorBajaNoValido"):
    $textoErr = $auxiliar->traduce("Valor no permitido para la columna 'Baja'");
elseif ($TipoError == "ErrorUOP"):
    $textoErr = $auxiliar->traduce("La Unidad Organizativa de Proceso no existe");
elseif ($TipoError == "TipoArchivoNoCSV"):
    $textoErr = $auxiliar->traduce("El archivo a importar no tiene extensión CSV", $administrador->ID_IDIOMA);
elseif ($TipoError == "ModificacionUbicacionStockExternalizado"):
    $textoErr = $auxiliar->traduce("No se pueden modificar ubicaciones de tipo stock externalizado", $administrador->ID_IDIOMA);
elseif ($TipoError == "InsercionUbicacionStockExternalizado"):
    $textoErr = $auxiliar->traduce("No se pueden crear manualmente ubicaciones de tipo stock externalizado", $administrador->ID_IDIOMA);
elseif ($TipoError == "ErrorDatosUbicacionCentroFisico"):
    $textoErr = $auxiliar->traduce("Ubicacion Centro Fisico no encontrado", $administrador->ID_IDIOMA);
elseif ($TipoError == "CentroFisicoNoCoincide"):
    $textoErr = $auxiliar->traduce("El Centro Fisico del Almacen de la ubicacion no coincide con el de la Ubicacion-Centro Fisico seleccionada", $administrador->ID_IDIOMA);
elseif ($TipoError == "TipoUbicacionDiferenteCF"):
    $textoErr = $auxiliar->traduce("El tipo de ubicacion de la ubicacion centro fisico no coincide con el de la ubicacion", $administrador->ID_IDIOMA);
elseif ($TipoError == "UbicacionEnMovimientoEntrada"):
    $movimientosEntrada = $_SESSION["movimientosEntradaBajaUbicacion"];
    unset($_SESSION["movimientosEntradaBajaUbicacion"]);
    $textoErr = $auxiliar->traduce("La ubicacion que intenta dar de baja está asociada a una linea de un movimiento de entrada activa. Movimientos de entrada afectados: ", $administrador->ID_IDIOMA) . $movimientosEntrada;
endif;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <? require_once $pathClases . "lib/gral_js.php"; ?>
    <script language="javascript" type="text/javascript">
        $(document).ready(function () {
            $('#botonVolver').focus();
        })
    </script>
</head>

<body bgcolor="#FFFFFF" background="<? echo $pathRaiz ?>imagenes/fondo_pantalla.gif" leftmargin="0" topmargin="0"
      marginwidth="0" marginheight="0">
<FORM NAME="Form" METHOD="POST">
    <table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
        <tr>
            <td height="10" align="center" valign="top">
                <? //include $pathRaiz."tabla_superior.php"; ?>

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
                        <? //include $pathRaiz."tabla_izqda.php"; ?>
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
                                                            </td>
                                                            <td width="60"></td>
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
                                                                &nbsp;</td>
                                                            <td width="220" bgcolor="#982a29" class="lineabajoarriba"
                                                                colspan=2><font
                                                                    class="tituloNav"><? echo $tituloNav ?></font></td>
                                                            <td bgcolor="#B3C7DA" valign=top width="20"
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
                                    <td height="280" align="left" valign="top" bgcolor="#D9E3EC" class="lineabajo">
                                        <table width="100%" height="280" border="0" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td align="center" valign="bottom">
                                                    <table width="100%" height="220" border="0" cellpadding="0"
                                                           cellspacing="0">
                                                        <tr align="center" valign="middle">
                                                            <td height="20">
                                                                <table width="100" height="20" border="0"
                                                                       cellpadding="0" cellspacing="0">
                                                                    <tr>
                                                                        <td align="center" valign="middle"
                                                                            bgcolor="#B3C7DA"
                                                                            class="alertas2"><? echo $tituloErr ?></td>
                                                                    </tr>
                                                                </table>
                                                            </td>
                                                        </tr>
                                                        <tr align="center" valign="middle">
                                                            <td bgcolor="#B3C7DA" class="textoazul">
                                                                <strong> <? echo $textoErr ?></strong></td>
                                                        </tr>
                                                        <tr>
                                                            <td height="124" align="center" valign="middle"
                                                                bgcolor="#B3C7DA">
                                                                <table width="100%" height="124" border="0"
                                                                       cellpadding="0" cellspacing="0">
                                                                    <tr>
                                                                        <td align="right" valign="middle">
                                                                            <table width="100%" height="124" border="0"
                                                                                   cellpadding="0" cellspacing="0">
                                                                                <tr>
                                                                                    <td height="9"><img
                                                                                            src="<? echo $pathRaiz ?>imagenes/transparente.gif"
                                                                                            width="10" height="9"></td>
                                                                                </tr>
                                                                                <tr>
                                                                                    <td height="115" bgcolor="#A80D0D">
                                                                                        &nbsp;</td>
                                                                                </tr>
                                                                            </table>
                                                                        </td>
                                                                        <td width="212" align="center" valign="middle"
                                                                            bgcolor="#A80D0D">
                                                                            <table width="212" height="124" border="0"
                                                                                   cellpadding="0" cellspacing="0"
                                                                                   background="<? echo $pathRaiz ?>imagenes/fondo_error2.gif">
                                                                                <tr>
                                                                                    <td>&nbsp;</td>
                                                                                </tr>
                                                                                <tr>
                                                                                    <td align="center" valign="middle">
                                                                                        <a id="botonVolver"
                                                                                           href="javascript:history.back()"
                                                                                           class="senaladoazul">&nbsp;&nbsp;&nbsp;&nbsp;<?= $auxiliar->traduce("Volver", $administrador->ID_IDIOMA) ?>
                                                                                            &nbsp;&nbsp;&nbsp;&nbsp;</a>
                                                                                    </td>
                                                                                </tr>
                                                                            </table>
                                                                        </td>
                                                                        <td align="left" valign="middle">
                                                                            <table width="100%" height="124" border="0"
                                                                                   cellpadding="0" cellspacing="0">
                                                                                <tr>
                                                                                    <td height="37"><img
                                                                                            src="<? echo $pathRaiz ?>imagenes/transparente.gif"
                                                                                            width="10" height="37"></td>
                                                                                </tr>
                                                                                <tr>
                                                                                    <td height="87" bgcolor="#A80D0D">
                                                                                        &nbsp;</td>
                                                                                </tr>
                                                                            </table>
                                                                        </td>
                                                                    </tr>
                                                                </table>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="left" height="40" valign="top" bgcolor="#B3C7DA" class="lineabajo">
                                        &nbsp;</td>
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
