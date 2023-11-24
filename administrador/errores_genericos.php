<?
if ($TipoError == "NoEncontrado"):
    $textoErr = $auxiliar->traduce("Se esta intentando acceder a un registro que no existe en la base de datos", $administrador->ID_IDIOMA) . ".";
elseif ($TipoError == "CampoSinRellenar"):
    $textoErr = $auxiliar->traduce("No se ha rellenado correctamente el campo", $administrador->ID_IDIOMA) . ": " . $CampoError;
elseif ($TipoError == "DatosErroneos"):
    $textoErr = $auxiliar->traduce("Datos de acceso a la Herramienta Web incorrectos", $administrador->ID_IDIOMA) . ".";
elseif ($TipoError == "ErrorEjecutarSql"):
    $textoErr = $this->msje_error;
elseif ($TipoError == "ErrorSQL"):
    $textoErr = $auxiliar->traduce("Se ha producido un error en una consulta SQL, intentelo de nuevo", $administrador->ID_IDIOMA) . ".";
elseif ($TipoError == "FechaIncorrecta"):
    //$textoErr = $auxiliar->traduce("La fecha introducida no es correcta",$administrador->ID_IDIOMA).".".'<br>'.$auxiliar->traduce("Debe ser válida y tener el formato dd-mm-yyyy",$administrador->ID_IDIOMA).".";
    $textoErr = $auxiliar->traduce("La fecha introducida no es correcta", $administrador->ID_IDIOMA) . "." . $CampoError . '<br>' . $auxiliar->traduce("Debe ser válida y tener el formato ", $administrador->ID_IDIOMA) . " " . $administrador->FMTO_FECHA . ".";
elseif ($TipoError == "HoraIncorrecta"):
    $textoErr = $auxiliar->traduce("La hora introducida no es correcta", $administrador->ID_IDIOMA) . "." . $CampoError . '<br>' . $auxiliar->traduce("Debe ser válida y tener el formato hh:mm", $administrador->ID_IDIOMA) . ".";
elseif ($TipoError == "OrdenFechasIncorrecto"):
    $textoErr = $auxiliar->traduce("La fechas no siguen un orden cronológico", $administrador->ID_IDIOMA) . ".";
elseif ($TipoError == "SinPermisos"):
    $textoErr = $auxiliar->traduce("No tiene permisos para realizar esta operación", $administrador->ID_IDIOMA) . ".";
elseif ($TipoError == "SinPermisosSubzona"):
    $textoErr = $auxiliar->traduce("No tiene permisos para realizar esta operación en esta subzona", $administrador->ID_IDIOMA) . ".";
elseif ($TipoError == "NoEntero"):
    $textoErr = $CampoError . " " . $auxiliar->traduce("ha de ser un campo entero", $administrador->ID_IDIOMA) . ".";
elseif ($TipoError == "NoDecimal"):
    $textoErr = $CampoError . " " . $auxiliar->traduce("ha de ser un campo numérico", $administrador->ID_IDIOMA) . ".";
elseif ($TipoError == "ExcedeDecimales"):
    $textoErr = $CampoError . " " . $auxiliar->traduce("excede el numero de decimales permitidos", $administrador->ID_IDIOMA) . " ($valorError).";
elseif ($TipoError == "EmailIncorrecto"):
    $textoErr = $auxiliar->traduce("El email introducido no es correcto", $administrador->ID_IDIOMA) . ".";
elseif ($TipoError == "ErrorTipoPedido"):
    $textoErr = $auxiliar->traduce("El tipo de pedido es incorrecto", $administrador->ID_IDIOMA) . ".";
elseif ($TipoError == "ErrorSAP"):
    $textoErr = "We have received the following errors from SAP/Se han producido los siguientes errores en el intercambio de información con SAP" . ": " . '<br>' . $strError;
elseif ($TipoError == "ConsultaSQLNoEjecutadaExportarExcel"):
    $textoErr = $auxiliar->traduce("Es necesario realizar una búsqueda para poder exportar datos a Excel", $administrador->ID_IDIOMA);
elseif ($TipoError == "ErrorDatosNoActualizados"):
    $textoErr = $auxiliar->traduce("Los datos mostrados en pantalla han sido modificados. Actualice para visualizar datos correctos.", $administrador->ID_IDIOMA);
elseif ($TipoError == "FiltrosSinRellenarNombres"):
    $textoErr = $auxiliar->traduce("Seleccione alguno de estos filtros", $administrador->ID_IDIOMA) . ": <br/><br/>" . $ListadoNombresError;
elseif ($TipoError == "FiltrosSinRellenar"):
    $textoErr = $auxiliar->traduce("Seleccione alguno de los filtros", $administrador->ID_IDIOMA);
elseif ($TipoError == "BusquedaVaciaNoPermitida"):
    $textoErr = $auxiliar->traduce("No es posible filtrar únicamente por caracteres especiales : (*) y (?) deben estar contenidos en una palabra para realizar el filtrado.", $administrador->ID_IDIOMA);
elseif ($TipoError == "ErrorCopiarFichero"):
    $textoErr = $auxiliar->traduce("Se ha producido un error al grabar el archivo", $administrador->ID_IDIOMA);
elseif ($TipoError == "NoLineasSeleccionadas"):
    $textoErr = $auxiliar->traduce("No ha seleccionado lineas", $administrador->ID_IDIOMA);

elseif ($TipoError == "ErrorLineaMovimientoTratamientoParcialCC"):
    $textoErr = $auxiliar->traduce("Para materiales indivisibles, el usuario no tiene permitido generar lineas de movimiento con cantidades de compra no enteras", $administrador->ID_IDIOMA);
elseif ($TipoError == "ErrorENABLON"):
    $textoErr = "We have received the following errors from Enablon/Se han producido los siguientes errores en el intercambio de información con ENABLON" . ": " . '<br>' . $strError;
else:
    $textoErr = $TipoError;
endif;
?>