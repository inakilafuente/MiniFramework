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

if ($accion == "insertarEmail"):

    //COMPRUEBO DATOS OBLIGATORIOS
    $i = 0;
    if ($chNuevoUsuario == "usuario_sga"):

        $arr_tx[$i]["err"]   = $auxiliar->traduce("Usuario", $administrador->ID_IDIOMA);
        $arr_tx[$i]["valor"] = trim( (string)$txUsuario);

    elseif ($chNuevoUsuario == "nuevo_contacto"):

        $arr_tx[$i]["err"]   = $auxiliar->traduce("Nombre", $administrador->ID_IDIOMA);
        $arr_tx[$i]["valor"] = trim( (string)$txNombre);
        $i++;
        $arr_tx[$i]["err"]   = $auxiliar->traduce("Email", $administrador->ID_IDIOMA);
        $arr_tx[$i]["valor"] = trim( (string)$txEmail);
        $i++;
        $arr_tx[$i]["err"]   = $auxiliar->traduce("Idioma notificaciones", $administrador->ID_IDIOMA);
        $arr_tx[$i]["valor"] = trim( (string)$selIdiomaNotificacion);

    endif;

    $Pagina_Error = "ficha_observaciones_error.php";

    //COMPROBAR LA NO EXISTENCIA DE CAMPOS OBLIGATORIOS VACÍOS
    $comp->ComprobarTexto($arr_tx, "CampoSinRellenar");

    //SI SE TRATA DE UN USUARIO DE SCS, OBTENEMOS SU INFORMACIÓN
    if ($idUsuario != ""):

        //OBTENEMOS EL USUARIO
        $NotificaErrorPorEmail = "No";
        $rowAdministrador      = $bd->VerReg("ADMINISTRADOR", "ID_ADMINISTRADOR", $idUsuario, "No");

        $html->PagErrorCondicionado($rowAdministrador, "==", false, "UsuarioNoExiste");

    elseif ($txUsuario != ""):

        //OBTENEMOS EL USUARIO
        $NotificaErrorPorEmail = "No";
        $rowAdministrador      = $bd->VerRegRest("ADMINISTRADOR", "NOMBRE = $txUsuario AND BAJA = 0", "No");

        $html->PagErrorCondicionado($rowAdministrador, "==", false, "UsuarioNoExiste");

    endif;

    //INICIALIZAMOS TRANSACCION
    $bd->begin_transaction();

    //AÑADIMOS EL NUEVO EMAIL EN EL CHAT
    if ($chNuevoUsuario == "usuario_sga"):

        $NotificaErrorPorEmail = "No";
        $rowIdioma             = $bd->VerReg("IDIOMA", "SIGLAS", $rowAdministrador->IDIOMA_NOTIFICACIONES, "No");

        $sqlInsertChat = "INSERT INTO CHAT_COMUNICACION SET 
                                SUBTIPO_OBSERVACION = '$subTipoObservacion'
                                , OBJETO = '$tipoObjeto'
                                , ID_OBJETO = $idObjeto
                                , NOMBRE = '" . $bd->escapeCondicional($rowAdministrador->NOMBRE) . "'
                                , EMAIL = '" . $bd->escapeCondicional($rowAdministrador->EMAIL) . "'
                                , ID_IDIOMA = $rowIdioma->ID_IDIOMA";

    elseif ($chNuevoUsuario == "nuevo_contacto"):

        $sqlInsertChat = "INSERT INTO CHAT_COMUNICACION SET 
                                SUBTIPO_OBSERVACION = '$subTipoObservacion'
                                , OBJETO = '$tipoObjeto'
                                , ID_OBJETO = $idObjeto
                                , NOMBRE = '" . $bd->escapeCondicional($txNombre) . "'
                                , EMAIL = '" . $bd->escapeCondicional($txEmail) . "'
                                , ID_IDIOMA = $selIdiomaNotificacion";

    endif;

    $bd->ExecSQL($sqlInsertChat);

    //COMMIT
    $bd->commit_transaction();

    //CIERRO EL FANCYBOX
    echo "<script type='text/javascript'>parent.jQuery.fancybox.close();</script>";

else: ?>

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
        <script type="text/javascript" language="javascript">

            jQuery(document).ready(function () {

                jQuery("a.fancyboxUsuarios").fancybox({
                    'type': 'iframe',
                    'width': '100%',
                    'height': '100%',
                    'hideOnOverlayClick': false,
                    'onClosed': function () {
                        ComprobarInputObligatorio(jQuery('#txUsuario'));
                    }
                });

            });

            function Continuar() {
                document.Form.accion.value = "insertarEmail";
                document.Form.submit();
            }

        </script>

    </head>
    <body class="fancy" bgcolor="#FFFFFF" background="<? echo $pathRaiz ?>imagenes/fondo_pantalla.gif" leftmargin="0"
          topmargin="0" marginwidth="0" marginheight="0">
    <form method="post" name="Form" action="ficha_destinatario_chat.php">
        <input type="hidden" name="accion" value="">
        <input type="hidden" name="chNuevoUsuario" value="<?= $chNuevoUsuario ?>">
        <input type="hidden" name="tipoObjeto" value="<?= $tipoObjeto ?>">
        <input type="hidden" name="idObjeto" value="<?= $idObjeto ?>">
        <input type="hidden" name="subTipoObservacion" value="<?= $subTipoObservacion ?>">

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

                                                                        <tr>
                                                                            <td>
                                                                                <table width="530" align="center"
                                                                                       border="0" cellspacing="0"
                                                                                       cellpadding="1">
                                                                                    <? if ($chNuevoUsuario == "usuario_sga"): ?>
                                                                                        <tr>
                                                                                            <td align="center"
                                                                                                width="10%">
                                                                                                <img height=7
                                                                                                     src="<? echo $pathRaiz ?>imagenes/diamante.gif"
                                                                                                     width=7>
                                                                                            </td>
                                                                                            <td align="left"
                                                                                                class="textoazul"><?php echo $auxiliar->traduce("Usuario", $administrador->ID_IDIOMA) . ":"; ?>
                                                                                            </td>
                                                                                            <td class="textoazul">
                                                                                                <?
                                                                                                $idTextBox  = "txUsuario";
                                                                                                $TamanoText = "180px";
                                                                                                $jscript    = "onchange=\"document.Form.idUsuario.value=''\"";
                                                                                                $ClassText  = "copyright ObligatorioRellenar";
                                                                                                $html->TextBox("txUsuario", $txUsuario);
                                                                                                unset($idTextBox);
                                                                                                unset($jscript); ?>
                                                                                                <input type="hidden"
                                                                                                       name="idUsuario"
                                                                                                       id="idUsuario"
                                                                                                       value="<?= $idUsuario ?>"/>
                                                                                                <a href="<?= $pathRaiz; ?>buscadores_maestros/busqueda_usuario.php?AlmacenarId=1"
                                                                                                   class="fancyboxUsuarios"
                                                                                                   id="usuarios">
                                                                                                    <img
                                                                                                            src="<?= $pathRaiz ?>imagenes/botones/listado.png"
                                                                                                            alt="<?= $auxiliar->traduce("Buscar Usuarios", $administrador->ID_IDIOMA) ?>"
                                                                                                            name="Listado"
                                                                                                            border="0"
                                                                                                            align="absbottom"
                                                                                                            id="Listado"/>
                                                                                                </a>
                                                                                                <span id="desplegable_usuarios"
                                                                                                      style="display: none;">
                                                                                                <img
                                                                                                        src="<?= $pathClases; ?>lib/ajax_script/img/esperando.gif"
                                                                                                        width="15"
                                                                                                        height="11"
                                                                                                        alt="<?= $auxiliar->traduce("Buscando...", $administrador->ID_IDIOMA) ?>"/>
                                                                                            </span>

                                                                                                <div class="entry"
                                                                                                     align="left"
                                                                                                     id="actualizador_usuarios">
                                                                                                </div>
                                                                                                <script type="text/javascript"
                                                                                                        language="JavaScript">
                                                                                                    new Ajax.Autocompleter('txUsuario', 'actualizador_usuarios', '<?=$pathRaiz;?>buscadores_maestros/resp_ajax_usuario.php?AlmacenarId=1',
                                                                                                        {
                                                                                                            method: 'post',
                                                                                                            indicator: 'desplegable_usuarios',
                                                                                                            minChars: '2',
                                                                                                            afterUpdateElement: function (textbox, valor) {
                                                                                                                jQuery('#idUsuario').val(jQuery(valor).children('a').attr('alt'));
                                                                                                            }
                                                                                                        }
                                                                                                    );
                                                                                                </script>
                                                                                            </td>
                                                                                        </tr>
                                                                                    <? else: ?>
                                                                                        <tr>
                                                                                            <td align="center"
                                                                                                width="10%">
                                                                                                <img height=7
                                                                                                     src="<? echo $pathRaiz ?>imagenes/diamante.gif"
                                                                                                     width=7>
                                                                                            </td>
                                                                                            <td align="left"
                                                                                                class="textoazul"><?php echo $auxiliar->traduce("Nombre", $administrador->ID_IDIOMA) . ":"; ?>
                                                                                            </td>
                                                                                            <td class="textoazul">
                                                                                                <?
                                                                                                $TamanoText = '200px';
                                                                                                $ClassText  = "copyright ObligatorioRellenar";
                                                                                                $idTextBox  = 'txNombre';
                                                                                                $html->TextBox("txNombre", $txNombre);
                                                                                                unset($idTextBox);
                                                                                                ?>
                                                                                            </td>
                                                                                        </tr>
                                                                                        <tr>
                                                                                            <td align="center"
                                                                                                width="10%">
                                                                                                <img height=7
                                                                                                     src="<? echo $pathRaiz ?>imagenes/diamante.gif"
                                                                                                     width=7>
                                                                                            </td>
                                                                                            <td align="left"
                                                                                                class="textoazul"><?php echo $auxiliar->traduce("Email", $administrador->ID_IDIOMA) . ":"; ?>
                                                                                            </td>
                                                                                            <td class="textoazul">
                                                                                                <?
                                                                                                $TamanoText = '200px';
                                                                                                $ClassText  = "copyright ObligatorioRellenar";
                                                                                                $idTextBox  = 'txEmail';
                                                                                                $html->TextBox("txEmail", $txEmail);
                                                                                                unset($idTextBox);
                                                                                                ?>
                                                                                            </td>
                                                                                        </tr>
                                                                                        <tr>
                                                                                            <td align="center"
                                                                                                width="10%">
                                                                                                <img height=7
                                                                                                     src="<? echo $pathRaiz ?>imagenes/diamante.gif"
                                                                                                     width=7>
                                                                                            </td>
                                                                                            <td align="left"
                                                                                                class="textoazul"><?php echo $auxiliar->traduce("Idioma notificaciones", $administrador->ID_IDIOMA) . ":"; ?>
                                                                                            </td>
                                                                                            <td class="textoazul">
                                                                                                <?
                                                                                                $NombreSelect           = 'selIdiomaNotificacion';
                                                                                                $i                      = 0;
                                                                                                $arrIdioma              = array();

                                                                                                //OBTENEMOS EL ID DEL ESPAÑOL Y EL INGLÉS
                                                                                                $NotificaErrorPorEmail = "No";
                                                                                                $rowEspañol = $bd->VerReg("IDIOMA", "IDIOMA_ESP", "Español", "No");
                                                                                                unset($NotificaErrorPorEmail);

                                                                                                $NotificaErrorPorEmail = "No";
                                                                                                $rowIngles = $bd->VerReg("IDIOMA", "IDIOMA_ESP", "Inglés", "No");
                                                                                                unset($NotificaErrorPorEmail);

                                                                                                $arrIdioma[$i]["text"]  = $auxiliar->traduce("Seleccione alguno", $administrador->ID_IDIOMA);
                                                                                                $arrIdioma[$i]["valor"] = "";
                                                                                                $i                      = $i + 1;
                                                                                                $arrIdioma[$i]["text"]  = $auxiliar->traduce("Español", $administrador->ID_IDIOMA);
                                                                                                $arrIdioma[$i]["valor"] = $rowEspañol->ID_IDIOMA;
                                                                                                $i                      = $i + 1;
                                                                                                $arrIdioma[$i]["text"]  = $auxiliar->traduce("Ingles", $administrador->ID_IDIOMA);
                                                                                                $arrIdioma[$i]["valor"] = $rowIngles->ID_IDIOMA;


                                                                                                $Estilo  = "copyright ObligatorioRellenar";
                                                                                                $Tamano  = "200px";

                                                                                                $html->SelectArr($NombreSelect, $arrIdioma, $selIdiomaNotificacion, $selIdiomaNotificacion);
                                                                                                ?>
                                                                                            </td>
                                                                                        </tr>
                                                                                    <? endif; ?>
                                                                                </table>
                                                                            </td>
                                                                        </tr>

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
                                                    <td valign="middle" height="25" align="left"
                                                        class="textoazul">
                                                        <span class="textoazul">
                                                            &nbsp;
                                                            <a class="senaladoazul" href="#"
                                                               onClick="history.back();">&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $auxiliar->traduce("Volver", $administrador->ID_IDIOMA); ?>&nbsp;&nbsp;&nbsp;&nbsp;
                                                            </a>
                                                        </span>
                                                    </td>
                                                    <td align="right" width="50%">
                                                        <span class="textoverde">
                                                            <a class="senaladoverde" href="#"
                                                               onClick="Continuar();">&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $auxiliar->traduce("Continuar", $administrador->ID_IDIOMA); ?>&nbsp;&nbsp;&nbsp;&nbsp;
                                                            </a>
                                                            &nbsp;&nbsp;
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
<? endif; ?>