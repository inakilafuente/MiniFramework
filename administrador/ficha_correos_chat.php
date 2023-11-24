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

//OBTENEMOS LAS CUENTAS QUE SE HAN AÑADIDO AL CORREO
$sqlCuentasCorreo    = "SELECT *
                        FROM CHAT_COMUNICACION
                        WHERE SUBTIPO_OBSERVACION = '" . $subTipoObservacion . "' AND OBJETO = '" . $tipoObjeto . "' AND ID_OBJETO = " . $idObjeto . " AND BAJA = 0
                        ORDER BY ID_CHAT_COMUNICACION";
$resultCuentasCorreo = $bd->ExecSQL($sqlCuentasCorreo);

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <? require_once $pathClases . "lib/gral_js.php"; ?>
    <script type="text/javascript" language="javascript">

        function buscar() {
            document.Form.submit();
        }

        function AnadirDestinatario(subTipoObservacion, tipoObjeto, idObjeto) {

            jQuery.fancybox
            (
                {
                    'type': 'iframe',
                    'width': '100%',
                    'height': '100%',
                    'hideOnOverlayClick': false,
                    'href': '<?php echo $pathRaiz ?>anadir_destinatario_chat.php?subTipoObservacion=' + subTipoObservacion + '&tipoObjeto=' + tipoObjeto + '&idObjeto=' + idObjeto,
                    'onClosed': function () {
                        buscar();
                    }
                }
            );

        }

        function QuitarContacto(idChatComunicacion) {
            document.Form.idChatComunicacion.value = idChatComunicacion;
            document.Form.accion.value = 'borrarContacto';
            document.Form.submit();
        }

        function EnviarNotificacion() {
            document.Form.accion.value = 'enviarNotificacion';
            document.Form.submit();
        }

    </script>

</head>
<body class="fancy" bgcolor="#FFFFFF" background="<? echo $pathRaiz ?>imagenes/fondo_pantalla.gif" leftmargin="0"
      topmargin="0" marginwidth="0" marginheight="0">
<form method="post" name="Form" action="ficha_correos_chat.php">
    <input type="hidden" name="accion" value="">
    <input type="hidden" name="tipoObjeto" value="<?= $tipoObjeto ?>">
    <input type="hidden" name="idObjeto" value="<?= $idObjeto ?>">
    <input type="hidden" name="subTipoObservacion" value="<?= $subTipoObservacion ?>">
    <input type="hidden" name="idChatComunicacion" value="">
    <input type="hidden" name="rolAdmin" value="<?= $rolAdmin ?>">
    <input type="hidden" name="desdeCorreo" value="<?= $desdeCorreo ?>">
    <input type="hidden" name="origen" value="<?= $origen ?>">

    <?

    if ($accion == "borrarContacto"):

        //COMPROBAMOS SI EXISTE EL CONTACTO A BORRAR
        $NotificaErrorPorEmail = "No";
        $rowChatComunicacion   = $bd->VerReg("CHAT_COMUNICACION", "ID_CHAT_COMUNICACION", $idChatComunicacion, "No");

        $Pagina_Error = "ficha_observaciones_error.php";
        $html->PagErrorCondicionado($rowChatComunicacion, "==", false, "ContactoNoExiste");

        //INICIALIZAMOS TRANSACCION
        $bd->begin_transaction();

        //DAMOS DE BAJA LAS PERSONAS DE CONTACTO
        $sqlUpdate = "UPDATE CHAT_COMUNICACION SET
                    BAJA = 1
                  WHERE ID_CHAT_COMUNICACION = $idChatComunicacion";
        $bd->ExecSQL($sqlUpdate);

        //COMMIT
        $bd->commit_transaction();

        //RECARGAMOS LA PÁGINA
        echo "<script type='text/javascript'>document.Form.submit();</script>";

    elseif ($accion == "enviarNotificacion"):

        //INICIALIZAMOS TRANSACCION
        $bd->begin_transaction();

        //CREAMOS EL CORREO DE NOTIFICACIÓN
        //OBTENGO EL TIPO DE CHAT
        $resultTipoChat = $observaciones_sistema->getTipoChatPorSubtipoObservacion($subTipoObservacion);
        $rowTipoChat    = $bd->SigReg($resultTipoChat);
        $chat           = $rowTipoChat->TIPO;

        //OBTENGO LOS DESTINATARIOS DEL CORREO
        $sqlChatsComunicacion    = "SELECT ID_CHAT_COMUNICACION, EMAIL, ID_IDIOMA
                                    FROM CHAT_COMUNICACION
                                    WHERE SUBTIPO_OBSERVACION = '" . $subTipoObservacion . "' AND OBJETO = '" . $tipoObjeto . "' AND ID_OBJETO = '" . $idObjeto . "' AND BAJA = 0
                                    ORDER BY ID_CHAT_COMUNICACION";
        $resultChatsComunicacion = $bd->ExecSQL($sqlChatsComunicacion);

        while ($rowChatsComunicacion = $bd->SigReg($resultChatsComunicacion)):

            //OBTENEMOS EL IDIOMA DE NOTIFICACIÓN
            $NotificaErrorPorEmail = "No";
            $rowIdioma             = $bd->VerReg("IDIOMA", "ID_IDIOMA", $rowChatsComunicacion->ID_IDIOMA);

            // CREAMOS EL CUERPO DEL CORREO
            $cuerpo = "<html><head></head><body><p style='font-family: Calibri, sans-serif;font-size: 14px;text-align: left;'>";
            $cuerpo .= "<br>" . $auxiliar->traduce("Buenas", $rowIdioma->SIGLAS) . "," . "<br><br>";
            $cuerpo .= $auxiliar->traduce("Se ha escrito un nuevo mensaje en el chat", $rowIdioma->SIGLAS) . ": " . $auxiliar->traduce($chat, $rowIdioma->SIGLAS) . ". " . "<br><br>" . $auxiliar->traduce("Puede ver el chat relacionado a traves del siguiente link", $rowIdioma->SIGLAS) . ": " . "<br><br>" . "<a href='" . HOST . "administrador/ficha_observaciones_chat.php?idObjeto=" . $idObjeto . "&tipoObjeto=" . $tipoObjeto . "&subTipoObservacion=" . $subTipoObservacion . "&rolAdmin=" . $rolAdmin . "&accion=ver&mostrarFechaHora=1&mostrarCategoria=1&mostrarUsuario=1&desdeCorreo=1&mostrarEstado=1' class='enlaceceldasacceso'>" . $idObjeto . "</a>";
            $cuerpo .= "<br><br>" . $auxiliar->traduce("Cualquier duda adicional preguntar a su persona de contacto de Operacion Logistica de Acciona", $rowIdioma->SIGLAS) . ".";
            $cuerpo .= "<br><br>" . $auxiliar->traduce("Saludos Cordiales", $rowIdioma->SIGLAS) . "." . "<br><br>";

            $cuerpo .= "</p><br></body></html>";

            //OBTENEMOS LOS CAMPOS A MOSTRAR EN EL ASUNTO DEL CORREO
            $proyecto = "";
            $entidad  = "";

            if ($tipoObjeto == "ORDEN_TRANSPORTE"):

                //OBTENEMOS LA INFORMACIÓN DE LA OT
                $sqlOrdenTransporte    = "SELECT OTC.ID_ORDEN_TRANSPORTE, OTC.NUMERO_CONTENEDOR, CF.DENOMINACION_CENTRO_FISICO
                                        FROM ORDEN_TRANSPORTE OT
                                            INNER JOIN ORDEN_TRANSPORTE_CONSTRUCCION OTC ON OTC.ID_ORDEN_TRANSPORTE
                                            INNER JOIN CENTRO_FISICO CF ON CF.ID_CENTRO_FISICO = OTC.ID_CENTRO_FISICO_PROYECTO
                                        WHERE OT.ID_ORDEN_TRANSPORTE = '" . $idObjeto . "' AND OT.BAJA = 0
                                        ORDER BY OTC.ID_ORDEN_TRANSPORTE";
                $resultOrdenTransporte = $bd->ExecSQL($sqlOrdenTransporte);
                $rowOrdenTransporte    = $bd->SigReg($resultOrdenTransporte);

                $proyecto = $rowOrdenTransporte->DENOMINACION_CENTRO_FISICO;
                $entidad  = $rowOrdenTransporte->NUMERO_CONTENEDOR;

            elseif ($tipoObjeto == "EMBARQUE"):

                //OBTENEMOS LA INFORMACIÓN DEL EMBARQUE
                $sqlEmbarque    = "SELECT E.ID_EMBARQUE, E.REFERENCIA, CF.DENOMINACION_CENTRO_FISICO
                                FROM EMBARQUE E
                                    INNER JOIN CENTRO_FISICO CF ON CF.ID_CENTRO_FISICO = E.ID_PROYECTO
                                WHERE E.ID_EMBARQUE = '" . $idObjeto . "' AND E.BAJA = 0
                                ORDER BY E.ID_EMBARQUE";
                $resultEmbarque = $bd->ExecSQL($sqlEmbarque);
                $rowEmbarque    = $bd->SigReg($resultEmbarque);

                $proyecto = $rowEmbarque->DENOMINACION_CENTRO_FISICO;
                $entidad  = $rowEmbarque->REFERENCIA;

            elseif ($tipoObjeto == "ORDEN_TRANSPORTE_ACCION_AVISO"):

                //OBTENEMOS LA INFORMACIÓN DE LA ACCIÓN DE LA OT
                $sqlOrdenTransporte    = "SELECT OTC.ID_ORDEN_TRANSPORTE, OTC.NUMERO_CONTENEDOR, CF.DENOMINACION_CENTRO_FISICO
                                        FROM ORDEN_TRANSPORTE_ACCION_AVISO OTAA
                                            INNER JOIN ORDEN_TRANSPORTE OT ON OT.ID_ORDEN_TRANSPORTE = OTAA.ID_ORDEN_TRANSPORTE
                                            INNER JOIN ORDEN_TRANSPORTE_CONSTRUCCION OTC ON OTC.ID_ORDEN_TRANSPORTE
                                            INNER JOIN CENTRO_FISICO CF ON CF.ID_CENTRO_FISICO = OTC.ID_CENTRO_FISICO_PROYECTO
                                        WHERE OTAA.ID_ORDEN_TRANSPORTE_ACCION_AVISO = '" . $idObjeto . "' AND OT.BAJA = 0
                                        ORDER BY OTC.ID_ORDEN_TRANSPORTE";
                $resultOrdenTransporte = $bd->ExecSQL($sqlOrdenTransporte);
                $rowOrdenTransporte    = $bd->SigReg($resultOrdenTransporte);

                $proyecto = $rowOrdenTransporte->DENOMINACION_CENTRO_FISICO;
                $entidad  = $rowOrdenTransporte->NUMERO_CONTENEDOR;

            endif;

            $subject = $proyecto . "/" . $entidad . "/" . $auxiliar->traduce($chat, $rowIdioma->SIGLAS) . "/" . $auxiliar->traduce("Nuevo mensaje", $rowIdioma->SIGLAS) . ": " . $auxiliar->traduce($tipoObjeto, $rowIdioma->SIGLAS) . " - " . $idObjeto;

            //ENVIO DEL CORREO
            $auxiliar->enviarCorreoSistema($subject, $cuerpo, OUTLOOK_USER, SENDER_EMAIL, $rowChatsComunicacion->EMAIL); //CORREO INTERNO

        endwhile;

        //COMMIT
        $bd->commit_transaction();

    endif;

    ?>

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
                                                                <? if ($accion == "enviarNotificacion"): ?>
                                                                    <table width="100%" height="220" border="0"
                                                                           cellpadding="0"
                                                                           cellspacing="0">
                                                                        <tr align="center" valign="middle">
                                                                            <td height="20">
                                                                                <table width="130" height="20"
                                                                                       border="0"
                                                                                       cellpadding="0" cellspacing="0">
                                                                                    <tr>
                                                                                        <td align="center"
                                                                                            valign="middle"
                                                                                            bgcolor="#B3C7DA"
                                                                                            class="alertas2"><?= strtr( (string)strtoupper( (string)$auxiliar->traduce("Información", $administrador->ID_IDIOMA)), "àèìòùáéíóúçñäëïöü", "ÀÈÌÒÙÁÉÍÓÚÇÑÄËÏÖÜ") ?></td>
                                                                                    </tr>
                                                                                </table>
                                                                            </td>
                                                                        </tr>
                                                                        <tr align="center" valign="middle">
                                                                            <td bgcolor="#B3C7DA" class="textoazul">
                                                                                <strong>
                                                                                    <?
                                                                                    //SI SOLO RESUELVO UNA SOLICITUD, MUESTRO EL MENSAJE DE ERROR
                                                                                    echo $auxiliar->traduce("El envio de la comunicacion se ha realizado correctamente", $administrador->ID_IDIOMA) . ".";

                                                                                    ?>
                                                                                </strong>
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td height="124" align="center"
                                                                                valign="middle"
                                                                                bgcolor="#B3C7DA">
                                                                                <table width="100%" border="0"
                                                                                       cellpadding="0" cellspacing="0">
                                                                                    <tr>
                                                                                        <td align="right"
                                                                                            valign="middle">
                                                                                            <table width="100%"
                                                                                                   height="124"
                                                                                                   border="0"
                                                                                                   cellpadding="0"
                                                                                                   cellspacing="0">
                                                                                                <tr>
                                                                                                    <td height="8"><img
                                                                                                                src="<? echo $pathRaiz ?>imagenes/transparente.gif"
                                                                                                                width="10"
                                                                                                                height="9">
                                                                                                    </td>
                                                                                                </tr>
                                                                                                <tr>
                                                                                                    <td height="98"
                                                                                                        bgcolor="#90BC45">
                                                                                                        &nbsp;
                                                                                                    </td>
                                                                                                </tr>
                                                                                            </table>
                                                                                        </td>
                                                                                        <td width="212" align="center"
                                                                                            valign="bottom"
                                                                                            bgcolor="#B3C7DA">
                                                                                            <table width="212"
                                                                                                   height="111"
                                                                                                   border="0"
                                                                                                   cellpadding="0"
                                                                                                   cellspacing="0"
                                                                                                   background="<? echo $pathRaiz ?>imagenes/fondo_ok2.gif">
                                                                                                <tr>
                                                                                                    <td>&nbsp;</td>
                                                                                                </tr>
                                                                                                <tr>
                                                                                                    <td align="center"
                                                                                                        valign="middle"
                                                                                                        bgcolor="#90BC45">
                                                                                                        <a href="<?= ($accionMasiva == "1" ? "transporte_construccion/avisos_construccion/index.php?recordar_busqueda=1" : "#") ?>"
                                                                                                           onClick="<?= ($desdeCorreo == "1" ? "window.parent.jQuery.fancybox.close();window.parent.Form.submit();" : "window.parent.parent.jQuery.fancybox.close();" . ($origen == "AccionMasiva" ? "window.parent.location.href = '" . $pathRaiz . "transporte_construccion/avisos_construccion/index.php'" : "")) ?>"
                                                                                                           class="senaladoazul">&nbsp;&nbsp;&nbsp;&nbsp;<?= $auxiliar->traduce("Continuar", $administrador->ID_IDIOMA) ?>
                                                                                                            &nbsp;&nbsp;&nbsp;&nbsp;</a>
                                                                                                    </td>
                                                                                                </tr>
                                                                                            </table>
                                                                                        </td>
                                                                                        <td align="left"
                                                                                            valign="middle">
                                                                                            <table width="100%"
                                                                                                   height="124"
                                                                                                   border="0"
                                                                                                   cellpadding="0"
                                                                                                   cellspacing="0">
                                                                                                <tr>
                                                                                                    <td height="50"><img
                                                                                                                src="<? echo $pathRaiz ?>imagenes/transparente.gif"
                                                                                                                width="10"
                                                                                                                height="37">
                                                                                                    </td>
                                                                                                </tr>
                                                                                                <tr>
                                                                                                    <td height="74"
                                                                                                        bgcolor="#90BC45">
                                                                                                        &nbsp;
                                                                                                    </td>
                                                                                                </tr>
                                                                                            </table>
                                                                                        </td>
                                                                                    </tr>
                                                                                </table>
                                                                            </td>
                                                                        </tr>
                                                                    </table>
                                                                <? else: ?>
                                                                    <table width="97%" border="0" cellpadding="0"
                                                                           cellspacing="0">
                                                                        <tr>
                                                                            <td width="1%"></td>
                                                                            <td>
                                                                                <table width="100%" border="0"
                                                                                       cellspacing="0"
                                                                                       style="margin-bottom: 10px; border:solid 1px white;">
                                                                                    <tr class="copyright"
                                                                                        style="font-weight:bold; padding: 1px;">
                                                                                        <td height="19"
                                                                                            bgcolor="#2E8AF0"
                                                                                            class="blanco"
                                                                                            style="margin-bottom: 10px; border:solid 1px white;">
                                                                                            <?= $auxiliar->traduce("Nombre", $administrador->ID_IDIOMA) ?>
                                                                                        </td>
                                                                                        <td height="19"
                                                                                            bgcolor="#2E8AF0"
                                                                                            class="blanco"
                                                                                            style="margin-bottom: 10px; border:solid 1px white;">
                                                                                            <?= $auxiliar->traduce("Email", $administrador->ID_IDIOMA) ?>
                                                                                        </td>
                                                                                        <td height="19"
                                                                                            bgcolor="#2E8AF0"
                                                                                            class="blanco"
                                                                                            style="margin-bottom: 10px; border:solid 1px white;">
                                                                                        </td>
                                                                                    </tr>
                                                                                    <?
                                                                                    if ($bd->NumRegs($resultCuentasCorreo) > 0):

                                                                                        $i = 0;

                                                                                        while ($rowCuentasCorreo = $bd->SigReg($resultCuentasCorreo)):

                                                                                            if ($i % 2 == 0) $myColor = "#B3C7DA";
                                                                                            else $myColor = "#AACFF9";

                                                                                            ?>
                                                                                            <tr>
                                                                                                <td height="19"
                                                                                                    align="left"
                                                                                                    bgcolor="<?= $myColor ?>"
                                                                                                    class="enlaceceldas"
                                                                                                    style="margin-bottom: 10px; border:solid 1px white;">
                                                                                                    &nbsp;<?= ($rowCuentasCorreo->NOMBRE == "" ? "-" : $rowCuentasCorreo->NOMBRE) ?>
                                                                                                </td>
                                                                                                <td height="19"
                                                                                                    align="left"
                                                                                                    bgcolor="<?= $myColor ?>"
                                                                                                    class="enlaceceldas"
                                                                                                    style="margin-bottom: 10px; border:solid 1px white;">
                                                                                                    &nbsp;<?= ($rowCuentasCorreo->EMAIL == "" ? "-" : $rowCuentasCorreo->EMAIL) ?>
                                                                                                </td>
                                                                                                <td height="19"
                                                                                                    align="center"
                                                                                                    bgcolor="<?= $myColor ?>"
                                                                                                    class="enlaceceldas"
                                                                                                    style="margin-bottom: 10px; border:solid 1px white;">
                                                                                                    <a class="copyright botones"
                                                                                                       href="#"
                                                                                                       onclick="return QuitarContacto('<?= $rowCuentasCorreo->ID_CHAT_COMUNICACION ?>')"
                                                                                                       title="<?= $auxiliar->traduce("Borrar Contacto", $administrador->ID_IDIOMA) ?>">
                                                                                                        <img src="<? echo $pathRaiz ?>imagenes/borrar.gif"
                                                                                                             class="image"
                                                                                                             alt="Herramientas"
                                                                                                             height="16px"
                                                                                                             width="16px"
                                                                                                             style="vertical-align: middle;padding-bottom:2px;"/>
                                                                                                    </a>
                                                                                                </td>
                                                                                            </tr>
                                                                                            <?

                                                                                            $i++;

                                                                                        endwhile;
                                                                                        ?>
                                                                                    <?
                                                                                    else:

                                                                                        $myColor = "#B3C7DA";

                                                                                        ?>
                                                                                        <tr>
                                                                                            <td height="19"
                                                                                                align="center"
                                                                                                bgcolor="<?= $myColor ?>"
                                                                                                colspan="3"
                                                                                                class="textoazul resaltado">
                                                                                                &nbsp;<?= $auxiliar->traduce("No se han añadido destinatarios", $administrador->ID_IDIOMA) ?>
                                                                                            </td>
                                                                                        </tr>
                                                                                    <? endif; ?>
                                                                                </table>
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td height="1" bgcolor="#D9E3EC"
                                                                                colspan="2">
                                                                                <table width="100%" cellpadding="0"
                                                                                       cellspacing="2">
                                                                                    <tr>
                                                                                        <td align="right">
                                                                                        <span class="textoverde">
                                                                                            <a class="senaladoverde"
                                                                                               href="#"
                                                                                               onClick="AnadirDestinatario('<?= $subTipoObservacion ?>', '<?= $tipoObjeto ?>', '<?= $idObjeto ?>');">&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $auxiliar->traduce("Añadir Destinatario", $administrador->ID_IDIOMA); ?>&nbsp;&nbsp;&nbsp;&nbsp;
                                                                                            </a>
                                                                                        </span>
                                                                                        </td>
                                                                                    </tr>
                                                                                </table>
                                                                            </td>
                                                                        </tr>
                                                                    </table>
                                                                <? endif; ?>
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
                                                <td align="left" width="50%" height="25">
                                                    <? if ($accion != "enviarNotificacion"): ?>
                                                        <span class="textoazul">
                                                            &nbsp;
                                                            <a class="senaladoazul" href="#"
                                                               onClick="<?= ($accionMasiva == "1" ? "history.back();return false;" : "parent.jQuery.fancybox.close();") ?>">&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $auxiliar->traduce("Volver", $administrador->ID_IDIOMA); ?>&nbsp;&nbsp;&nbsp;&nbsp;
                                                            </a>
                                                        </span>
                                                    <? endif; ?>
                                                </td>
                                                <td align="right" width="50%" height="25">
                                                    <? if ($accion != "enviarNotificacion"): ?>
                                                        <span class="textoverde">
                                                            <a class="senaladoverde" href="#"
                                                               onClick="EnviarNotificacion();">&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $auxiliar->traduce("Enviar Notificacion", $administrador->ID_IDIOMA); ?>&nbsp;&nbsp;&nbsp;&nbsp;
                                                            </a>
                                                            &nbsp;&nbsp;
                                                        </span>
                                                    <? endif; ?>
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