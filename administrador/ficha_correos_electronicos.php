<? //print_r($_REQUEST);//exit;
// PATHS DE LA WEB
$pathRaiz   = "./";
$pathClases = "../";

// INCLUDES DE LIBRERIAS PROPIAS
require_once $pathClases . "lib/basedatos.php";
require_once $pathClases . "lib/administrador.php";
require_once $pathClases . "lib/html.php";
require_once $pathClases . "lib/navegar.php";
require_once $pathClases . "lib/comprobar.php";
require_once $pathClases . "lib/auxiliar.php";
require_once $pathClases . "lib/material.php";
require_once $pathClases . "lib/orden_transporte.php";
require_once $pathClases . "lib/aviso.php";
require_once $pathClases . "lib/necesidad.php";
require_once $pathClases . "lib/xajax25_php7/xajax_core/xajax.inc.php";

session_start();
include $pathRaiz . "seguridad_admin.php";

$tituloPag = $auxiliar->traduce("Emails", $administrador->ID_IDIOMA);
$tituloNav = $auxiliar->traduce("Correos electronicos", $administrador->ID_IDIOMA) . " >> " . $tituloPag;
//$ZonaTablaPadre = "TransporteConstruccion";
//$ZonaTabla      = "TransporteConstruccionAvisos";


//CARGO LA FILA DE LAS OBSERVACIONES
$NotificaErrorPorEmail = "No";
$sql                   = "SELECT * FROM AVISO WHERE ID_OBJETO = $idObjeto AND TIPO_OBJETO = '$tipoObjeto'";
$result                = $bd->ExecSQL($sql, "No");

//COMPROBAMOS QUE EXISTE
$html->PagErrorCondicionado($bd->NumRegs($result), "==", 0, "AvisoNoExiste");
unset($NotificaErrorPorEmail);


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
    <!-- CALENDARIO -->
    <script language="JavaScript" src="<?= $pathClases ?>recursos/calendar/calendar_us.js"></script>
    <link rel="stylesheet" href="<?= $pathClases ?>recursos/calendar/calendar.css">
    <!-- FIN CALENDARIO -->
    <script type="text/javascript">
        jQuery(document).ready(function () {

            jQuery("a.fancyboxProveedores").fancybox({
                'type': 'iframe',
                'width': '100%',
                'height': '100%',
                'hideOnOverlayClick': false

            });

            //SI EL CUERPO DEL MENSAJE TIENE ENLACES HAGO QUE LOS ABRÁ EN UNA PESTAÑA NUEVA
            jQuery("a").not(".detalleCorreo").each(function (i) {

                jQuery(this).attr("target", "_blank");
            })
            jQuery("a").not(".enlace_copyright").not(".detalleCorreo").each(function (i) {

                jQuery(this).attr("target", "_blank");
                jQuery(this).attr("class", "enlaceceldasacceso");
            })

        });
    </script>
    <script type="text/javascript" language="javascript">
        function CerrarFancy() {

            window.parent.jQuery.fancybox.close();
        }
        function mostrarDetalle(indice_correo) {
            if (!jQuery("#detalle_correo" + indice_correo).is(':visible')) {
                jQuery("#detalle_correo" + indice_correo).show();
                jQuery("#enlace_correo" + indice_correo).text("<?=$auxiliar->traduce("Ocultar Detalle",$administrador->ID_IDIOMA)?>");
            }
            else if (jQuery("#detalle_correo" + indice_correo).is(':visible')) {
                jQuery("#detalle_correo" + indice_correo).hide();
                jQuery("#enlace_correo" + indice_correo).text("<?=$auxiliar->traduce("Ver Detalle",$administrador->ID_IDIOMA)?>");
            }
        }
    </script>
    <?
    // XAJAX
    //    if (!$disableAjax):
    //        $xajax->printJavascript($pathClases . "lib/xajax25_php7/");
    //    endif;
    ?>
</head>
<body bgcolor="#FFFFFF" background="<? echo "$pathRaiz" ?>imagenes/fondo_pantalla.gif" leftmargin="0" topmargin="0"
      marginwidth="0" marginheight="0" onLoad="">
<FORM NAME="FormSelect" METHOD="POST" action="" enctype="multipart/form-data">

    <table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
        <tr>
            <td height="10" align="center" valign="top">
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
                                                                &nbsp;</td>
                                                            <td width="220" bgcolor="#982a29" class="lineabajoarriba"
                                                                colspan=2>
                                                                <font class="tituloNav"><? echo $tituloNav ?></font>
                                                            </td>
                                                            <td width="20" valign=top bgcolor="#B3C7DA"
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
                                    <td align="center" valign="top" bgcolor="#AACFF9" class="lineabajo">
                                        <table width="100%" border="0" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td width="20" align="center" valign="middle" class="lineaderecha">
                                                    &nbsp;</td>
                                                <td align="center" valign="middle">
                                                    <table width="97%" border="0" align="center" cellpadding="0"
                                                           cellspacing="0">

                                                        <tr>
                                                            <td colspan="3" height="1" bgcolor="#AACFF9">
                                                                <img
                                                                    src="<? echo $pathRaiz ?>imagenes/transparente.gif"
                                                                    width="10" height="5">
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td colspan="3" align="center" height="20"
                                                                bgcolor="#FFFFFF">
                                                                    <span
                                                                        class="textoazul resaltado"><?= $auxiliar->traduce("Listado Emails", $administrador->ID_IDIOMA) ?>
                                                                    </span>
                                                            </td>
                                                        </tr>
                                                        <? $indice_correo = 0;
                                                        while ($row = $bd->SigReg($result)):
                                                            $tipoObjeto  = $row->TIPO_OBJETO;
                                                            $idObjeto    = $row->ID_OBJETO;
                                                            $asunto      = $row->ASUNTO;
                                                            $cuerpo      = $row->CUERPO;
                                                            $idRemitente = $row->ID_ADMINISTRADOR_REMITENTE;

                                                            //ADJUNTO
                                                            $adjunto1 = "";
                                                            if ($row->NOMBRE_ADJUNTO != ""):
                                                                $adjunto1 = $pathRaiz . "descargarDocumento.php?ruta=" . $row->RUTA_ADJUNTO . $row->NOMBRE_ADJUNTO;

                                                            endif;
                                                            //ADJUNTO 2
                                                            $adjunto2 = "";
                                                            if ($row->NOMBRE_ADJUNTO_2 != ""):
                                                                $adjunto2 = $pathRaiz . "descargarDocumento.php?ruta=" . $row->RUTA_ADJUNTO . $row->NOMBRE_ADJUNTO_2;
                                                            endif;

                                                            //OBTENEMOS EL REMITENTE SI TIENE
                                                            $nombreRemitente = "-";
                                                            if ($idRemitente != NULL && $idRemitente != ""):
                                                                $rowRemitente = $bd->VerReg("ADMINISTRADOR", "ID_ADMINISTRADOR", $idRemitente, "No");
                                                                if (strtoupper( (string)$rowRemitente->LOGIN) == "PROCESOAUTOMATICO"):
                                                                    $nombreRemitente = $auxiliar->traduce("Sistema", $administrador->ID_IDIOMA);
                                                                else:
                                                                    $nombreRemitente = $rowRemitente->NOMBRE;
                                                                endif;
                                                            endif;

                                                            $fechaCreacion    = $row->FECHA_CREACION;
                                                            $fechaUltimoEnvio = $row->FECHA_ULTIMO_ENVIO;
                                                            $enviado          = $row->ENVIADO;
                                                            $nombreAdjunto    = $row->NOMBRE_ADJUNTO;
                                                            $nombreAdjunto2   = $row->NOMBRE_ADJUNTO2;

                                                            //BUSCAMOS LOS DESTINATARIOS POR EMAILS
                                                            $destinatarios = $aviso->obtenerDestinatariosEnvio($row->ID_AVISO);
                                                            $arrDestinatarios = explode(',', (string)$destinatarios);

                                                            //BUSCAMOS LOS DESTINATARIOS POR USUARIOS
                                                            $sqlDestinatarios = "SELECT *
                                                                                    FROM AVISO_ADMINISTRADOR
                                                                                    WHERE ID_AVISO = $row->ID_AVISO";
                                                            $resultDestinatarios = $bd->ExecSQL($sqlDestinatarios);
                                                            while ($rowDestinatarios = $bd->SigReg($resultDestinatarios)):
                                                                if ($rowDestinatarios->ID_ADMINISTRADOR != NULL):
                                                                    $rowAdministradorCorreo = $bd->VerReg("ADMINISTRADOR", "ID_ADMINISTRADOR", $rowDestinatarios->ID_ADMINISTRADOR, "No");
                                                                    $arrDestinatarios[] = $rowAdministradorCorreo->EMAIL;
                                                                endif;

                                                                if ($rowDestinatarios->ID_PROVEEDOR != NULL):
                                                                    $rowProveedorCorreo = $bd->VerReg("PROVEEDOR", "ID_PROVEEDOR", $rowDestinatarios->ID_PROVEEDOR, "No");
                                                                    if ($tipoObjeto == "FICHA_SEGURIDAD_MATERIAL"):
                                                                        $arrDestinatarios[] = $rowProveedorCorreo->EMAIL_FICHAS_SEGURIDAD;
                                                                    elseif ($tipoObjeto == "ORDEN_CONTRATACION"):
                                                                        $arrDestinatarios[] = $rowProveedorCorreo->EMAIL_CONTRATACION;
                                                                    elseif ($tipoObjeto == "AUTOFACTURA"):
                                                                        $arrDestinatarios[] = $rowProveedorCorreo->EMAIL_CERTIFICACION;
                                                                    else:
                                                                        $arrDestinatarios[] = $rowProveedorCorreo->EMAIL;
                                                                    endif;
                                                                endif;

                                                                if ($rowDestinatarios->ID_SOLICITUD_TRANSPORTE != NULL):
                                                                    $rowSolicitanteCorreo = $bd->VerReg("SOLICITUD_TRANSPORTE", "ID_SOLICITUD_TRANSPORTE", $rowDestinatarios->ID_SOLICITUD_TRANSPORTE, "No");
                                                                    $arrDestinatarios[] = $rowSolicitanteCorreo->EMAIL_SOLICITANTE;
                                                                endif;

                                                                if ($rowDestinatarios->ID_ORDEN_CONTRATACION_COMUNICACION != NULL):
                                                                    $rowContratacionComunicacion = $bd->VerReg("ORDEN_CONTRATACION_COMUNICACION", "ID_ORDEN_CONTRATACION_COMUNICACION", $rowDestinatarios->ID_ORDEN_CONTRATACION_COMUNICACION, "No");
                                                                    $arrDestinatarios[] =  $rowContratacionComunicacion->EMAIL;
                                                                endif;
                                                            endwhile;

                                                            //UNIFICAMOS
                                                            $arrDestinatarios = array_unique( (array)$arrDestinatarios);
                                                            $arrDestinatarios = array_filter($arrDestinatarios);
                                                            $destinatarios = implode(',', (array) $arrDestinatarios);
                                                            ?>
                                                            <tr>
                                                                <td colspan="3" height="1" bgcolor="#AACFF9"
                                                                    class="lineabajo">
                                                                    <img
                                                                        src="<? echo $pathRaiz ?>imagenes/transparente.gif"
                                                                        width="10" height="5">
                                                                </td>
                                                            </tr>

                                                            <tr bgcolor="white" class="lineabajodereizq">
                                                                <td width="10" bgcolor="white"
                                                                    class="lineabajoizquierda">
                                                                    &nbsp;</td>
                                                                <td width="100%" align="left" bgcolor="white"
                                                                    class="lineabajoderecha">
                                                                    <table border="0" cellspacing="0" cellpadding="1"
                                                                           class="tablaFiltros"
                                                                           style="margin-top: 5px;">

                                                                        <tbody>
                                                                        <tr>
                                                                            <td>&nbsp;</td>

                                                                            <td height="20" width="20%"
                                                                                align="left"
                                                                                valign="middle"
                                                                                class="textoazul">
                                                                                &nbsp;<?= $auxiliar->traduce("Enviado", $administrador->ID_IDIOMA) . ":" ?>
                                                                            </td>
                                                                            <td align="left" valign="middle"
                                                                                class="textoazul" width="80%">
                                                                                <?
                                                                                if ($enviado == 1):
                                                                                    if ($fechaUltimoEnvio != ""):
                                                                                        echo $auxiliar->fechaFmtoEspHora($fechaUltimoEnvio);
                                                                                    else:
                                                                                        echo $auxiliar->fechaFmtoEspHora($fechaCreacion);
                                                                                    endif;
                                                                                else:
                                                                                    echo $auxiliar->traduce("No Enviado", $administrador->ID_IDIOMA);
                                                                                endif;
                                                                                ?>
                                                                            </td>
                                                                            <td>&nbsp;</td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td>&nbsp;</td>

                                                                            <td height="20" width="20%"
                                                                                align="left"
                                                                                valign="middle"
                                                                                class="textoazul">
                                                                                &nbsp;<?= $auxiliar->traduce("Asunto", $administrador->ID_IDIOMA) . ":" ?>
                                                                            </td>
                                                                            <td align="left" valign="middle"
                                                                                class="textoazul" width="80%">
                                                                                <? echo $asunto; ?>
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td>&nbsp;</td>

                                                                            <td height="20" width="20%"
                                                                                align="left"
                                                                                valign="middle"
                                                                                class="textoazul">
                                                                                &nbsp;<?= ucfirst( (string)$auxiliar->traduce("De", $administrador->ID_IDIOMA)) . ":" ?>
                                                                            </td>
                                                                            <td align="left" valign="middle"
                                                                                class="textoazul" width="80%">
                                                                                <? echo $nombreRemitente; ?>
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td>&nbsp;</td>

                                                                            <td height="20" width="20%"
                                                                                align="left"
                                                                                valign="middle"
                                                                                class="textoazul">
                                                                                &nbsp;<?= $auxiliar->traduce("Para", $administrador->ID_IDIOMA) . ":" ?>
                                                                            </td>
                                                                            <td align="left" valign="middle"
                                                                                class="textoazul" width="80%">
                                                                                <u> <? echo str_replace( ",", ";",(string) $destinatarios); ?></u>
                                                                            </td>
                                                                        </tr>
                                                                        <? if ($adjunto1 != ""): ?>
                                                                            <tr>
                                                                                <td>&nbsp;</td>

                                                                                <td height="20" width="20%"
                                                                                    align="left"
                                                                                    valign="middle"
                                                                                    class="textoazul">
                                                                                    &nbsp;<?= $auxiliar->traduce("Adjuntos", $administrador->ID_IDIOMA) . ":" ?>
                                                                                </td>
                                                                                <td align="left" valign="middle"
                                                                                    class="textoazul" width="80%">
                                                                                    <a href="<?= $adjunto1; ?>"
                                                                                       class="enlaceceldasacceso"
                                                                                       target="_blank">
                                                                                        <? $html->imgExtension($row->NOMBRE_ADJUNTO) ?>
                                                                                        <?= $row->NOMBRE_ADJUNTO ?>
                                                                                    </a>

                                                                                    <? if ($adjunto2 != ""): ?>
                                                                                        , <a href="<?= $adjunto2; ?>"
                                                                                             class="enlaceceldasacceso"
                                                                                             target="_blank">
                                                                                            <? $html->imgExtension($row->NOMBRE_ADJUNTO_2) ?>
                                                                                            <?= $row->NOMBRE_ADJUNTO_2 ?>
                                                                                        </a>
                                                                                    <?endif; ?>

                                                                                </td>
                                                                            </tr>
                                                                        <?endif; ?>

                                                                        <tr>
                                                                            <td>&nbsp;</td>

                                                                            <td height="20" width="20%"
                                                                                align="left"
                                                                                valign="middle"
                                                                                class="textoazul">
                                                                                &nbsp;
                                                                            </td>
                                                                            <td align="left" valign="middle"
                                                                                class="textoazul" width="80%">
                                                                                <a href="#"
                                                                                   class="detalleCorreo enlaceAyuda"
                                                                                   onclick="mostrarDetalle('<?= $indice_correo ?>');"
                                                                                   id="enlace_correo<?= $indice_correo ?>"
                                                                                   title="">
                                                                                    <strong>
                                                                                        <? echo $auxiliar->traduce("Ver Detalle", $administrador->ID_IDIOMA); ?>
                                                                                    </strong>
                                                                                </a>
                                                                            </td>
                                                                        </tr>

                                                                        <tr id="detalle_correo<?= $indice_correo ?>"
                                                                            style="display: none">
                                                                            <!--                                                                            <div id="detalle_correo_-->
                                                                            <?//=$indice_correo
                                                                            ?><!--" style="display: none">-->
                                                                            <td style="padding-top: 15px; border-top: 0.5px solid #B3C7DA;">
                                                                                &nbsp;</td>

                                                                            <td height="20" width="20%"
                                                                                align="left"
                                                                                valign="middle"
                                                                                class="textoazul"
                                                                                style="padding-top: 15px; border-top: 0.5px solid #B3C7DA;">
                                                                                &nbsp;<?= $auxiliar->traduce("Cuerpo Correo", $administrador->ID_IDIOMA) . ":" ?>
                                                                            </td>
                                                                            <td align="left" valign="middle"
                                                                                class="textoazul" width="80%"
                                                                                style="background-color: white;padding-top: 15px;border-top:0.5px solid #B3C7DA; ">

                                                                                <? echo $cuerpo; ?>
                                                                            </td>

                                                                            <!--                                                                            </div>-->
                                                                        </tr>

                                                                        </tbody>
                                                                    </table>
                                                                </td>
                                                            </tr>

                                                            <? $indice_correo++;
                                                        endwhile; ?>
                                                        <tr>
                                                            <td colspan="3" height="1" bgcolor="#AACFF9"
                                                                class="">
                                                                <img
                                                                    src="<? echo $pathRaiz ?>imagenes/transparente.gif"
                                                                    width="10" height="5">
                                                            </td>
                                                        </tr>

                                                    </table>
                                                </td>
                                                <td width="20" align="center" valign="middle" class="lineaizquierda">
                                                    &nbsp;</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="left" valign="top" bgcolor="#B3C7DA" class="lineabajo">

                                        <table width="100%" border="0" cellspacing="0" cellpadding="0"
                                               bgcolor="#D9E3EC">
                                            <tr height="25">
                                                <td class="lineabajo" width="50%" align="left">
                                                    <span class="textoazul">
                                                        &nbsp;
                                                        <a href="#" onclick="window.parent.jQuery.fancybox.close();"
                                                           class="senaladoazul detalleCorreo">
                                                            &nbsp;&nbsp;&nbsp;&nbsp;
                                                            <?= $auxiliar->traduce("Cerrar", $administrador->ID_IDIOMA) ?>
                                                            &nbsp;&nbsp;&nbsp;&nbsp;
                                                        </a>
                                                    </span>
                                                </td>

                                            </tr>
                                        </table>
                                        <br><br>
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
