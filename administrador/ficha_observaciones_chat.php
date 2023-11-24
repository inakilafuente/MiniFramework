<? //print_r($_REQUEST); //die;
// PATHS DE LA WEB
$pathRaiz   = "./";
$pathClases = "../";

// INCLUDES DE LIBRERIAS PROPIAS
require_once $pathClases . "lib/basedatos.php";
require_once $pathClases . "lib/administrador.php";
require_once $pathClases . "lib/html.php";
require_once $pathClases . "lib/auxiliar.php";
require_once $pathClases . "lib/navegar.php";
require_once $pathClases . "lib/comprobar.php";
require_once $pathClases . "lib/observaciones_sistema.php";
require_once $pathClases . "lib/necesidad.php";
require_once $pathClases . "lib/aviso.php";
require_once $pathClases . "lib/orden_transporte.php";


session_start();
include $pathRaiz . "seguridad_admin.php";

$tituloPag = $auxiliar->traduce("Observaciones", $administrador->ID_IDIOMA);
$tituloNav = ucfirst( (string)$auxiliar->traduce($tipoObjeto, $administrador->ID_IDIOMA)) . " >> " . $auxiliar->traduce("Observaciones", $administrador->ID_IDIOMA) . ($subcategoria != "" ? " >> " . $auxiliar->traduce($subcategoria, $administrador->ID_IDIOMA) : "");

$rutaListado = "";
//RECOGEMOS EL LISTADO AL QUE VOLVER SEGUN EL TIPO PROVEEDOR SI VOLVEMOS DESDE EL CORREO
if ($desdeCorreo == 1):
    if ($tipoObjeto == 'EMBARQUE'):

        if ($administrador->esProveedor()):

            //OBTENEMOS EL PERFIL
            $row_administrador_perfil = $administrador->ObtenerPerfilAdministrador($administrador->ID_ADMINISTRADOR);

            if ($row_administrador_perfil->ES_FORWARDER_CONSTRUCCION == 1):
                $rutaListado = "transportes_gc/listado_forwarder";
            elseif ($row_administrador_perfil->ES_PROVEEDOR_CONSTRUCCION == 1):
                $rutaListado = "transportes_gc/listado_proveedor_material";
            elseif ($row_administrador_perfil->ES_AGENTE_ADUANAL_CONSTRUCCION == 1):
                $rutaListado = "transportes_gc/listado_agente_aduanal";
            elseif ($row_administrador_perfil->ES_SURVEYOR == 1):
                $rutaListado = "transportes_gc/listado_surveyor";
            else:
                //MOSTRAMOS ERROR POR SER UN PROVEEDOR SIN TENER PROVEEDOR ASIGNADO
                $html->PagError("UsuarioProveedorSinProveedorAsignado");
            endif;
        else:
            $rutaListado = "transportes_gc/listado";
        endif;
    elseif ($tipoObjeto == 'ORDEN_TRANSPORTE'):
        if ($administrador->esProveedor()):

            //OBTENEMOS EL PERFIL
            $row_administrador_perfil = $administrador->ObtenerPerfilAdministrador($administrador->ID_ADMINISTRADOR);

            if ($row_administrador_perfil->ES_AGENTE_ADUANAL_CONSTRUCCION == 1):
                $rutaListado = "ciclo_entrega_obra/listado_agente";
            elseif ($row_administrador_perfil->ES_TRANSPORTISTA_INLAND_CONSTRUCCION == 1):
                $rutaListado = "ciclo_entrega_obra/listado_transportista";
            else:
                //MOSTRAMOS ERROR POR SER UN PROVEEDOR SIN TENER PROVEEDOR ASIGNADO
                $html->PagError("UsuarioProveedorSinProveedorAsignado");
            endif;
        else:
            $rutaListado = "ciclo_entrega_obra/listado";
        endif;
    elseif ($tipoObjeto == 'ORDEN_TRANSPORTE_ACCION_AVISO'):
        $rutaListado = "transporte_construccion/avisos_construccion/index.php";
    endif;
endif;

//SI ES VER, OBTENEMOS LAS OBSERVACIONES
if ($accion == "ver"):

    //BUSCAMOS OBSERVACIONES
    if ($mostrarHistorial == "1"):
        //MOSTRAMOS TODO EL HISTORIAL DE CONVERSACIONES
        //OBTENEMOS LA INFORMACIÓN DE LA ACCIÓN
        $rowAvisoAccion = false;
        if ($idObjeto != ""):
            $sqlAvisoAccion    = "SELECT OTA.TIPO_ACCION, OTAA.ID_ORDEN_TRANSPORTE_ACCION_AVISO, OTAA.ID_ORDEN_TRANSPORTE, OTA.HITO_CAMBIO_ESTADO, OTAA.OBSERVACION, OTA.TIPO_DESTINATARIO
                                FROM ORDEN_TRANSPORTE_ACCION OTA
                                INNER JOIN ORDEN_TRANSPORTE_ACCION_AVISO OTAA ON OTAA.ID_ORDEN_TRANSPORTE_ACCION = OTA.ID_ORDEN_TRANSPORTE_ACCION
                                WHERE OTAA.BAJA = 0 AND OTAA.ID_ORDEN_TRANSPORTE_ACCION_AVISO = $idObjeto";
            $resultAvisoAccion = $bd->ExecSQL($sqlAvisoAccion);
            $rowAvisoAccion    = $bd->SigReg($resultAvisoAccion);
        endif;

        //BUSCAMOS LAS OBSERVACIONES DEL RECHAZO
        $tipoAccionPrevia = "";
        $subtipoAccion    = "";
        if (($rowAvisoAccion->TIPO_ACCION == "Aceptar/Rechazar Borrador BL") || ($rowAvisoAccion->TIPO_ACCION == "Revisar Borrador BL")):
            $tipoAccionPrevia = " AND (OTA.TIPO_ACCION = 'Añadir Borrador BL' OR OTA.TIPO_ACCION = 'Aceptar/Rechazar Borrador BL' OR OTA.TIPO_ACCION = 'Revisar Borrador BL')";
            $subtipoAccion    = "AccionOrdenTransporteAAForwarder";

        elseif (($rowAvisoAccion->TIPO_ACCION == "Aceptar/Rechazar BL Definitivo") || ($rowAvisoAccion->TIPO_ACCION == "Revisar BL Definitivo")):
            $tipoAccionPrevia = " AND (OTA.TIPO_ACCION = 'Añadir BL Definitivo' OR OTA.TIPO_ACCION = 'Aceptar/Rechazar BL Definitivo' OR OTA.TIPO_ACCION = 'Revisar BL Definitivo')";
            $subtipoAccion    = "AccionOrdenTransporteAAForwarder";

        elseif (($rowAvisoAccion->TIPO_ACCION == "Aceptar/Rechazar CMR") || ($rowAvisoAccion->TIPO_ACCION == "Revisar CMR")):
            $tipoAccionPrevia = " AND (OTA.TIPO_ACCION = 'Añadir CMR' OR OTA.TIPO_ACCION = 'Aceptar/Rechazar CMR' OR OTA.TIPO_ACCION = 'Revisar CMR')";
            $subtipoAccion    = "AccionOrdenTransporteAAForwarder";

        elseif (($rowAvisoAccion->TIPO_ACCION == "Aceptar/Rechazar Documentacion Proveedor") || ($rowAvisoAccion->TIPO_ACCION == "Modificar Documentacion")):
            $tipoAccionPrevia = " AND (OTA.TIPO_ACCION = 'Confirmar Entrega a Forwarder' OR OTA.TIPO_ACCION = 'Modificar Documentacion' OR OTA.TIPO_ACCION = 'Aceptar/Rechazar Documentacion Proveedor') ";
            $subtipoAccion    = "AccionOrdenTransporteAAProveedor";

        elseif (($rowAvisoAccion->TIPO_ACCION == "Añadir Borrador BL") || ($rowAvisoAccion->TIPO_ACCION == "Añadir BL Definitivo") || ($rowAvisoAccion->TIPO_ACCION == "Añadir CMR")):
            $subtipoAccion = "AccionOrdenTransporteAAForwarder";

        else:
            $subtipoAccion = "AccionOrdenTransporte";

        endif;

        //OBTENEMOS LAS OBSERVACIONES DE LA ACCIÓN DE LA OT
        $sqlObservaciones    = "SELECT OS.ID_ADMINISTRADOR, OS.FECHA, OS.TEXTO_OBSERVACION, OS.TIPO_OBSERVACION, OS.SUBTIPO_OBSERVACION, OS.ID_OBSERVACION_SISTEMA, OS.ESTADO
                                FROM OBSERVACION_SISTEMA OS
                                    INNER JOIN ORDEN_TRANSPORTE_ACCION_AVISO OTAA ON OTAA.ID_ORDEN_TRANSPORTE_ACCION_AVISO = OS.ID_OBJETO
                                    INNER JOIN ORDEN_TRANSPORTE_ACCION OTA ON OTA.ID_ORDEN_TRANSPORTE_ACCION = OTAA.ID_ORDEN_TRANSPORTE_ACCION
                                WHERE OS.SUBTIPO_OBSERVACION = '$subtipoAccion' AND OS.TIPO_OBJETO = 'ORDEN_TRANSPORTE_ACCION_AVISO' AND OTAA.ID_ORDEN_TRANSPORTE = " . $rowAvisoAccion->ID_ORDEN_TRANSPORTE . $tipoAccionPrevia . "
                                ORDER BY ID_OBSERVACION_SISTEMA DESC";
        $resultObservaciones = $bd->ExecSQL($sqlObservaciones);
    else:
        $resultObservaciones = $observaciones_sistema->getObservaciones($tipoObjeto, $idObjeto, array($rolAdmin), array($subTipoObservacion), "");
    endif;

    $texto         = "";
    $observaciones = array();
    if ($bd->NumRegs($resultObservaciones) > 0):
        while ($rowObservaciones = $bd->SigReg($resultObservaciones)):
            $chat = array();

            //OBTENGO EL USUARIO QUE REALIZÓ EL COMENTARIO
            $rowAdministrador = $bd->VerReg("ADMINISTRADOR", "ID_ADMINISTRADOR", $rowObservaciones->ID_ADMINISTRADOR, "No");

            //GUARDO LA INFORMACION A MOSTRAR
            $chat['id']           = $rowObservaciones->ID_OBSERVACION_SISTEMA;
            $chat['fecha']        = ($mostrarFechaHora != true ? '' : $auxiliar->fechaFmtoEspHora($rowObservaciones->FECHA));
            $rol                  = (($mostrarCategoria == true && $rowObservaciones->TIPO_OBSERVACION != NULL) ? " (" . $auxiliar->traduce($rowObservaciones->TIPO_OBSERVACION, $administrador->ID_IDIOMA) . ")" : '');
            $chat['usuario']      = ($mostrarUsuario != true ? '' : $rowAdministrador->NOMBRE . $rol);
            $chat['subcategoria'] = (($mostrarSubCategoria == true && $rowObservaciones->SUBTIPO_OBSERVACION != "") ? $auxiliar->traduce($rowObservaciones->SUBTIPO_OBSERVACION, $administrador->ID_IDIOMA) : "");
            $chat['texto']        = $rowObservaciones->TEXTO_OBSERVACION;

            //SI SE TRATA DE LA PANTALLA DE ACCIONES MOSTRAMOS EL ESTADO
            if ($mostrarEstado == 1):
                $chat['estado'] = ($rowObservaciones->ESTADO != "" ? $rowObservaciones->ESTADO : "-");
            endif;

            $observaciones[] = $chat;
        endwhile;
        if ($subTipoObservacion != ""):
            //BUSCAMOS TIPO CHAT
            $resultTipoChat = $observaciones_sistema->getTipoChatPorSubtipoObservacion($subTipoObservacion);
            $rowTipoChat    = $bd->SigReg($resultTipoChat);
        endif;
    endif;

endif;

//RECOGEMOS LA LISTA DE DISTRIBUCION PARA ESTE CHAT
$sqlListaDistribucion    = "SELECT CC.*
                            FROM CHAT_COMUNICACION CC
                            WHERE CC.SUBTIPO_OBSERVACION = '" . $subTipoObservacion . "' AND CC.OBJETO = '" . $tipoObjeto . "' AND CC.ID_OBJETO = '" . $idObjeto . "' AND CC.BAJA = 0
                            ORDER BY CC.ID_CHAT_COMUNICACION";
$resultListaDistribucion = $bd->ExecSQL($sqlListaDistribucion);
$contactosChat           = array();
if ($bd->NumRegs($resultListaDistribucion) > 0):
    while ($rowListaDistribucion = $bd->SigReg($resultListaDistribucion)):
        $contacto = array();

        $contacto['nombre'] = $rowListaDistribucion->NOMBRE;
        $contacto['email']  = $rowListaDistribucion->EMAIL;

        $contactosChat[] = $contacto;

    endwhile;
endif;

?>

    <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
            "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
    <html>
    <head>
        <? require_once $pathClases . "lib/gral_js.php"; ?>
        <script type="text/javascript" language="javascript">

            function mostrarDetalle(indice) {
                if (!jQuery("#detalleObs" + indice).is(':visible')) {
                    jQuery("#detalleObs" + indice).show();
                    jQuery("#reducidoObs" + indice).hide();
                } else if (jQuery("#detalleObs" + indice).is(':visible')) {
                    jQuery("#detalleObs" + indice).hide();
                    jQuery("#reducidoObs" + indice).show();
                }
            }

            function GrabarObservaciones() {
                document.Form.submit();
                return false;
            }

        </script>
    </head>
    <body class="fancy" bgcolor="#FFFFFF" background="<? echo $pathRaiz ?>imagenes/fondo_pantalla.gif" leftmargin="0"
          topmargin="0" marginwidth="0" marginheight="0"
          onLoad="<? if ($editable == 1): ?>document.Form.txObservaciones.focus(); <? endif; ?>">
    <form method="post" name="Form" action="ficha_observaciones_chat.php">
        <input type="hidden" name="accion" value="<?= $accion ?>">
        <input type="hidden" name="operar" value="1">
        <input type="hidden" name="tipoObjeto" value="<?= $tipoObjeto ?>">
        <input type="hidden" name="idObjeto" value="<?= $idObjeto ?>">
        <input type="hidden" name="subTipoObservacion" value="<?= $subTipoObservacion ?>">
        <input type="hidden" name="rolAdmin" value="<?= $rolAdmin ?>">
        <input type="hidden" name="desdeCorreo" value="<?= $desdeCorreo ?>">

        <? $navegar->GenerarCamposOcultosForm(); ?>
        <table width="100%" border="0" align="center" cellpadding="0" cellspacing="0" background="">
            <tr>
                <td align="center" valign="top">
                    <table width="100%" border="0" cellpadding="0" cellspacing="0">
                        <tr>
                            <td height="3" colspan="2" align="center" valign="top" bgcolor="#7A0A0A" class="linearriba">
                                <img src="<? echo $pathRaiz ?>imagenes/transparente.gif" width="10" height="3"></td>
                        </tr>
                        <tr>
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
                                                                <td align="left"
                                                                    class="alertas"><? echo $tituloPag ?></td>
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
                                                                <td width="35" bgcolor="#982a29"
                                                                    class="lineabajoarriba">&nbsp;
                                                                </td>
                                                                <td width="224" bgcolor="#982a29"
                                                                    class="lineabajoarriba" colspan="2">
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
                                                                           cellspacing="0">

                                                                        <?php
                                                                        //EN LA ACCION DE VER LAS MOSTRAMOS Y DESHABILITAMOS
                                                                        if ($accion == "ver"): ?>
                                                                            <tr>
                                                                                <td align="left" width="2%"></td>
                                                                                <td align="left" width="5%"
                                                                                    class="textoazul"
                                                                                    style="vertical-align: top;"><?php echo $auxiliar->traduce("Observaciones", $administrador->ID_IDIOMA) . ":"; ?>
                                                                                </td>
                                                                                <td class="textoazul">

                                                                                    <div style="float: left">
                                                                                        <img align="right"
                                                                                             src="<?= $pathRaiz ?>imagenes/informacion.gif"
                                                                                             style="cursor: help; width: 15px;padding-right: 5px; padding-top: 2px;"
                                                                                             title=""
                                                                                             onmouseover="jQuery('#parrafo_rolesImplicados').show();"
                                                                                             onmouseout="jQuery('#parrafo_rolesImplicados').hide();"/>
                                                                                        <br/>

                                                                                        <p id="parrafo_rolesImplicados"
                                                                                           style="font-weight: normal;max-width: 800px;display:none;position: absolute;background-color: white; padding: 5px;border:1px solid #444444;color: #444444;border-radius: 2px;box-shadow: 10px 10px 5px #888888;margin-right: 25px;">
                                                                                            <?= $auxiliar->traduce('Roles implicados en este chat', $administrador->ID_IDIOMA) . ":" ?>
                                                                                            <? foreach (explode("|", (string)$rowTipoChat->ROLES_INTERVINIENTES) as $rol): ?>
                                                                                                <br/>
                                                                                                - <?= $auxiliar->traduce($rol, $administrador->ID_IDIOMA) ?>
                                                                                            <? endforeach; ?>
                                                                                        </p>
                                                                                    </div>
                                                                                    <div style='overflow-y: auto; max-height: 500px; max-width: 90vw'>
                                                                                        <table style='width:100%;'
                                                                                               cellspacing='6'>
                                                                                            <? foreach ($observaciones as $obs):
                                                                                                $textoMostrar = "";
                                                                                                if (strlen( (string)$obs['texto']) > 190):
                                                                                                    $textoMostrar = substr( (string) $obs['texto'], 0, 190) . "...<a href='#' class='enlaceAyuda' onclick='mostrarDetalle(" . $obs['id'] . ")'><strong>" . $auxiliar->traduce('Ver Detalle', $administrador->ID_IDIOMA) . "</strong></a>";

                                                                                                else:
                                                                                                    $textoMostrar = $obs['texto'];
                                                                                                endif; ?>

                                                                                                <tr bgcolor='white'
                                                                                                    class='lineabajoizquierda'>
                                                                                                    <td width='100%'
                                                                                                        align='left'
                                                                                                        bgcolor='white'
                                                                                                        class='lineabajoizquierda'>
                                                                                                        <table border='0'
                                                                                                               cellspacing='0'
                                                                                                               cellpadding='1'
                                                                                                               class='tablaFiltros'
                                                                                                               style=''>
                                                                                                            <tbody>

                                                                                                            <!-- fecha -->
                                                                                                            <tr>
                                                                                                                <td>
                                                                                                                    &nbsp;
                                                                                                                </td>
                                                                                                                <td height='20'
                                                                                                                    width='6%'
                                                                                                                    align='left'
                                                                                                                    valign='middle'
                                                                                                                    class='textoazul'>
                                                                                                                    <strong> <?= $auxiliar->traduce('Fecha', $administrador->ID_IDIOMA) ?></strong>
                                                                                                                </td>
                                                                                                                <td align='left'
                                                                                                                    valign='middle'
                                                                                                                    class='textoazul'
                                                                                                                    width='94%'>
                                                                                                                    <?= $obs['fecha'] ?>
                                                                                                                </td>
                                                                                                                <td>
                                                                                                                    &nbsp;
                                                                                                                </td>
                                                                                                            </tr>
                                                                                                            <!-- usuario -->
                                                                                                            <tr>
                                                                                                                <td>
                                                                                                                    &nbsp;
                                                                                                                </td>
                                                                                                                <td height='20'
                                                                                                                    width='6%'
                                                                                                                    align='left'
                                                                                                                    valign='middle'
                                                                                                                    class='textoazul'>
                                                                                                                    <strong><?= $auxiliar->traduce('Usuario', $administrador->ID_IDIOMA) ?></strong>
                                                                                                                </td>
                                                                                                                <td align='left'
                                                                                                                    valign='middle'
                                                                                                                    class='textoazul'
                                                                                                                    width='94%'>
                                                                                                                    <?= $obs['usuario'] ?>
                                                                                                                </td>
                                                                                                                <td>
                                                                                                                    &nbsp;
                                                                                                                </td>
                                                                                                            </tr>
                                                                                                            <!-- texto -->
                                                                                                            <tr id='reducidoObs<?= $obs['id'] ?>'>
                                                                                                                <td>
                                                                                                                    &nbsp;
                                                                                                                </td>
                                                                                                                <td height='20'
                                                                                                                    width='6%'
                                                                                                                    align='left'
                                                                                                                    valign='middle'
                                                                                                                    class='textoazul'>
                                                                                                                    <strong><?= $auxiliar->traduce('Texto', $administrador->ID_IDIOMA) ?></strong>
                                                                                                                </td>
                                                                                                                <td align='left'
                                                                                                                    valign='middle'
                                                                                                                    class='textoazul'
                                                                                                                    width='94%'
                                                                                                                    style="word-wrap: break-word; max-width: 40vw">
                                                                                                                    <?= $textoMostrar ?>
                                                                                                                </td>
                                                                                                                <td>
                                                                                                                    &nbsp;
                                                                                                                </td>
                                                                                                            </tr>

                                                                                                            <!-- texxto ampliado -->
                                                                                                            <tr id='detalleObs<?= $obs['id'] ?>'
                                                                                                                style='display: none'>
                                                                                                                <td>
                                                                                                                    &nbsp;
                                                                                                                </td>
                                                                                                                <td height='20'
                                                                                                                    width='6%'
                                                                                                                    align='left'
                                                                                                                    valign='middle'
                                                                                                                    class='textoazul'>
                                                                                                                    <strong><?= $auxiliar->traduce('Texto', $administrador->ID_IDIOMA) ?></strong>
                                                                                                                </td>
                                                                                                                <td align='left'
                                                                                                                    valign='middle'
                                                                                                                    class='textoazul'
                                                                                                                    width='94%'
                                                                                                                    style="word-wrap: break-word; max-width: 40vw">
                                                                                                                    <?= $obs['texto'] ?>
                                                                                                                    <a href='#'
                                                                                                                       class='enlaceAyuda'
                                                                                                                       onclick='mostrarDetalle(<?= $obs['id'] ?>)'>
                                                                                                                        <strong><?= $auxiliar->traduce('Ocultar Detalle', $administrador->ID_IDIOMA) ?></strong>
                                                                                                                    </a>
                                                                                                                </td>
                                                                                                                <td>
                                                                                                                    &nbsp;
                                                                                                                </td>
                                                                                                            </tr>

                                                                                                            <!-- Estado -->
                                                                                                            <? if ($mostrarEstado == 1): ?>
                                                                                                                <tr>
                                                                                                                    <td>
                                                                                                                        &nbsp;
                                                                                                                    </td>
                                                                                                                    <td height='20'
                                                                                                                        width='6%'
                                                                                                                        align='left'
                                                                                                                        valign='middle'
                                                                                                                        class='textoazul'>
                                                                                                                        <strong><?= $auxiliar->traduce('Estado', $administrador->ID_IDIOMA) ?></strong>
                                                                                                                    </td>
                                                                                                                    <td align='left'
                                                                                                                        valign='middle'
                                                                                                                        class='textoazul'
                                                                                                                        width='94%'>
                                                                                                                        <?= $obs['estado'] ?>
                                                                                                                    </td>
                                                                                                                    <td>
                                                                                                                        &nbsp;
                                                                                                                    </td>
                                                                                                                </tr>
                                                                                                            <? endif; ?>

                                                                                                            </tbody>
                                                                                                        </table>
                                                                                                    </td>
                                                                                                </tr>
                                                                                            <? endforeach; ?>
                                                                                        </table>
                                                                                    </div>
                                                                                </td>
                                                                            </tr>
                                                                        <? else: ?>
                                                                            <tr>
                                                                                <td align="left" width="22%"></td>
                                                                                <td align="left" width="8%"
                                                                                    class="textoazul"><?php echo $auxiliar->traduce("Observaciones", $administrador->ID_IDIOMA) . ":"; ?>
                                                                                </td>
                                                                                <td class="textoazul">
                                                                                    <?
                                                                                    $TamanoText = '600px';
                                                                                    $ClassText  = "copyright ObligatorioRellenar";
                                                                                    $Filas      = 8;
                                                                                    $html->TextArea("txObservaciones", $txObservaciones);
                                                                                    unset($Filas);
                                                                                    ?>
                                                                                </td>
                                                                            </tr>

                                                                        <? endif; ?>

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
                                                        <img src="<? echo $pathRaiz ?>imagenes/transparente.gif"
                                                             width="10" height="5"></td>
                                                    <td width="20" align="center" valign="bottom"
                                                        class="lineaizquierda">&nbsp;
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td align="left" valign="top" bgcolor="#B3C7DA">
                                            <table width="100%" border="0" cellpadding="0" cellspacing="0"
                                                   class="lineabajo">
                                                <tr>
                                                    <td valign="middle" height="25" align="left" colspan="2"
                                                        class="textoazul">
                                                        <span class="textoazul">
                                                            &nbsp;<? if ($desdeCorreo == 1): ?>
                                                                <a class="senaladoazul"
                                                                   onclick="window.parent.jQuery.fancybox.close();"
                                                                   href="#">
                                                                &nbsp;&nbsp;&nbsp;&nbsp;<?= $auxiliar->traduce("Volver", $administrador->ID_IDIOMA) ?>
                                                                &nbsp;&nbsp;&nbsp;&nbsp;</a>
                                                            <? else: ?>
                                                                <a class="senaladoazul"
                                                                   onclick="window.parent.jQuery.fancybox.close();"
                                                                   href="#">
                                                                &nbsp;&nbsp;&nbsp;&nbsp;<?= $auxiliar->traduce("Volver", $administrador->ID_IDIOMA) ?>
                                                                &nbsp;&nbsp;&nbsp;&nbsp;</a>
                                                            <? endif; ?>
                                                            <? if (($accion == "ver") && ($anadirComentario == "1")): ?>
                                                                &nbsp;
                                                                <span class="textoverde">
                                                                    <a href="<?= $pathRaiz . "ficha_observaciones_chat.php?idObjeto=$idObjeto&tipoObjeto=$tipoObjeto&subTipoObservacion=$subTipoObservacion&rolAdmin=$rolAdmin&accion=crear&desdeCorreo=$desdeCorreo"; ?>"
                                                                       class="senaladoverde botones">
                                                                        &nbsp;&nbsp;&nbsp;&nbsp;<?= $auxiliar->traduce("Añadir comentario", $administrador->ID_IDIOMA); ?>&nbsp;&nbsp;&nbsp;&nbsp;
                                                                    </a>
                                                                </span>
                                                            <? endif; ?>
                                                        </span>
                                                    </td>
                                                    <td valign="middle" height="25" align="right" colspan="2">
                                                        <span class="textoazul">
                                                            <? if ($accion == "crear"): ?>
                                                                <span class="textoazul">
                                                                    <a class='senaladoverde botones' href='#'
                                                                       onclick="return GrabarObservaciones();">&nbsp;&nbsp;&nbsp;&nbsp;<?= $auxiliar->traduce("Grabar", $administrador->ID_IDIOMA) ?>&nbsp;&nbsp;&nbsp;&nbsp;</a>
                                                                    &nbsp;
                                                                </span>
                                                            <? endif; ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            </table>
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
<?
if (($accion == "crear") && ($operar == 1)):

    $i = 0;
    //COMPROBAMOS CAMPOS OBLIGATORIOS
    $arr_tx[$i]["err"]   = $auxiliar->traduce("Observaciones", $administrador->ID_IDIOMA);
    $arr_tx[$i]["valor"] = $txObservaciones;

    $Pagina_Error = "ficha_observaciones_error.php";
    $comp->ComprobarTexto($arr_tx, "CampoSinRellenar");


    //INICIO LA TRANSACCION
    $bd->begin_transaction();


    //GUARDAMOS LOS DATOS
    $observaciones_sistema->Grabar($tipoObjeto, $idObjeto, $txObservaciones, $rolAdmin, $subTipoObservacion, date('Y-m-d H:i:s'));

    //GUARDAMOS LA INFORMACION DE CONTACTO PARA ESTE CHAT
    $sqlCorreosChat    = "SELECT *
                            FROM CHAT_COMUNICACION
                            WHERE SUBTIPO_OBSERVACION = '" . $subTipoObservacion . "' AND OBJETO = '" . $tipoObjeto . "' AND ID_OBJETO = '" . $idObjeto . "'AND EMAIL = '" . $administrador->getEmailAdministrador($administrador->ID_ADMINISTRADOR) . "' AND BAJA = 0";
    $resultCorreosChat = $bd->ExecSQL($sqlCorreosChat);

    if ($bd->NumRegs($resultCorreosChat) == 0):

        $NotificaErrorPorEmail = "No";
        $rowIdioma             = $bd->VerReg("IDIOMA", "SIGLAS", $administrador->IDIOMA_NOTIFICACIONES, "No");

        $sqlInsertCorreo = "INSERT INTO CHAT_COMUNICACION SET
                                SUBTIPO_OBSERVACION = '$subTipoObservacion'
                                , OBJETO = '$tipoObjeto'
                                , ID_OBJETO = $idObjeto
                                , NOMBRE = '" . $bd->escapeCondicional($administrador->NOMBRE) . "'
                                , EMAIL = '" . $administrador->getEmailAdministrador($administrador->ID_ADMINISTRADOR) . "'
                                , ID_IDIOMA = $rowIdioma->ID_IDIOMA";
        $bd->ExecSQL($sqlInsertCorreo);
    endif;

    //FINALIZO LA TRANSACCION
    $bd->commit_transaction();

    //REDIRIGIMOS A LA MISMA PANTALLA, PERO CON DISTINTA ACCION
    echo "<script type='text/javascript' language='javascript'>document.Form.action = 'posibilidad_comunicacion_chat.php';document.Form.submit();</script>";
endif;
?>