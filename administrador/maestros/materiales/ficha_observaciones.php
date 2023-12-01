<? //print_r($_REQUEST); //die;
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
//CARGO LA FILA DE LAS OBSERVACIONES
    //MUESTRO TODAS LAS OBSERVACIONES DE LA OT

    $sql   = "SELECT OBSERVACIONES
                            FROM MATERIALES WHERE REFERENCIA_SCS=".$referencia;
    $result = $bd->ExecSQL($sql);

$row = $bd->SigReg($result);
$txObservaciones=$row->OBSERVACIONES;

    while ($rowObservacionesOT = $bd->SigReg($resultObservacionesOT)):

        //OBTENEMOS LA INFORMACIÓN DEL USUARIO QUE REALIZÓ EL COMENTARIO
        $NotificaErrorPorEmail = "No";
        $rowAdministrador      = $bd->VerReg("ADMINISTRADOR", "ID_ADMINISTRADOR", $rowObservacionesOT->ID_ADMINISTRADOR, "No");

        $txObservaciones .= "\r\n" . ">> " . $auxiliar->fechaFmtoEspHora($rowObservacionesOT->FECHA, true, false, true, true) . " - " . trim( (string)$rowAdministrador->NOMBRE) . ": " . trim( (string)$rowObservacionesOT->TEXTO_OBSERVACION);

    endwhile;

unset($NotificaErrorPorEmail);
$tituloPag = $auxiliar->traduce("Observaciones", $administrador->ID_IDIOMA);
$tituloNav = ucfirst( (string)$auxiliar->traduce(($tipoObjeto != "ORDEN_TRANSPORTE" ? $tipoObjeto : "Orden de Transporte"), $administrador->ID_IDIOMA)) . " >> " . $auxiliar->traduce("Observaciones", $administrador->ID_IDIOMA) . ($subcategoria != "" ? " >> " . $auxiliar->traduce($subcategoria, $administrador->ID_IDIOMA) : "");

if ($accion == 'GrabarObservaciones'):

    $i = 0;
    //EN EL CASO DE QUE VENGAN DE INCIDENCIAS DE SISTEMA, NO NECESITA TIPO (CATEGORIA)
    if ($verTipoObservaciones == 1):
        $arr_tx[$i]["err"]   = $auxiliar->traduce("Categoria", $administrador->ID_IDIOMA);
        $arr_tx[$i]["valor"] = $selTipoObservacion;
        $i                   = $i + 1;
    endif;
    $arr_tx[$i]["err"]   = $auxiliar->traduce("Observaciones", $administrador->ID_IDIOMA);
    $arr_tx[$i]["valor"] = $txObservaciones;

    $Pagina_Error = "ficha_observaciones_error.php";
    $comp->ComprobarTexto($arr_tx, "CampoSinRellenar");

    if (($tipoObjeto == 'INCIDENCIA_FICHA_SEGURIDAD_MATERIAL') && ($subcategoria == "rechazarFicha")):
        $observaciones_sistema->Grabar($tipoObjeto, $idObjeto, $auxiliar->traduce("Ficha rechazada", $administrador->ID_ADMINISTRADOR) . ". " . $txObservaciones, $selTipoObservacion, $subcategoria);
    else:
        $observaciones_sistema->Grabar($tipoObjeto, $idObjeto, $txObservaciones, $selTipoObservacion, $subcategoria);
    endif;

    //SI ES DE MOVIMIENTO_RECEPCION, MARCAMOS QUE ES UNA RECEPCION CON PROBLEMAS
    if ($tipoObjeto == 'MOVIMIENTO_RECEPCION'):
        $sql = "UPDATE MOVIMIENTO_RECEPCION SET RECEPCION_CON_PROBLEMAS=1,ESTADO_INCIDENCIA='Abierta' WHERE ID_MOVIMIENTO_RECEPCION=" . $idObjeto;
        $bd->ExecSQL($sql);
    endif;
    if ($tipoObjeto == 'ORDEN_TRANSPORTE'):
        $sql = "UPDATE ORDEN_TRANSPORTE SET ESTADO_INCIDENCIA='Abierta' WHERE ID_ORDEN_TRANSPORTE=" . $idObjeto;
        $bd->ExecSQL($sql);
    endif;
    if ($tipoObjeto == 'AUTOFACTURA'):
        $sql = "UPDATE AUTOFACTURA SET ESTADO_INCIDENCIA='Abierta' WHERE ID_AUTOFACTURA=" . $idObjeto;
        $bd->ExecSQL($sql);
    endif;
    if ($tipoObjeto == 'ORDEN_CONTRATACION_INCIDENCIA'):
        $sql = "UPDATE ORDEN_CONTRATACION SET ESTADO_INCIDENCIA='Abierta' WHERE ID_ORDEN_CONTRATACION=" . $idObjeto;
        $bd->ExecSQL($sql);
    endif;
    if (($tipoObjeto == 'NECESIDAD') && ($subcategoria == "Reclamacion")):
        //ACTUALIZO LA NECESIDAD
        $sqlUpdate = "UPDATE NECESIDAD SET
                  ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR,
                  FECHA_ULTIMA_RECLAMACION = '" . date("Y-m-d") . "'
                  WHERE ID_NECESIDAD = $idObjeto";
        $bd->ExecSQL($sqlUpdate);

        //LOG MOVIMIENTOS
        $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, 'Modificación', "Necesidad", $idObjeto, "Reclamacion Necesidad: " . $bd->escapeCondicional($txObservaciones));

        $necesidad->EnviarNotificacionEmail_NecesidadReclamada($idObjeto);
    endif;
    if (($tipoObjeto == 'INCIDENCIA_FICHA_SEGURIDAD_MATERIAL') && ($subcategoria == "rechazarFicha")):
        //COMPRUEBO QUE LA ficha exsita
        if ((trim( (string)$idObjeto) == false) || (trim( (string)$idObjeto) == "")):
            $html->PagError("ErrorSQL");
        endif;
        //FICHA SEGURIDAD
        $rowFichaSeguridad = $bd->VerReg("INCIDENCIA_FICHA_SEGURIDAD_MATERIAL_IDIOMA", "ID_INCIDENCIA_FICHA_SEGURIDAD_MATERIAL_IDIOMA", $idObjeto, "No");
        //MATERIAL
        $rowMaterial = $bd->VerReg("MATERIAL", "ID_MATERIAL", $rowFichaSeguridad->ID_MATERIAL, "No");
        //PROVEEDOR
        $rowProveedor = $bd->VerReg("PROVEEDOR", "ID_PROVEEDOR", $rowFichaSeguridad->ID_PROVEEDOR, "No");
        $html->PagErrorCondicionado($rowFichaSeguridad, "==", false, "NoExisteFichaSeguridadMaterial");

        $rowIdiomaPais = $bd->VerReg("IDIOMA", "ID_IDIOMA", $rowFichaSeguridad->ID_IDIOMA, "No");

        //INICIO LA TRANSACCION
        $bd->begin_transaction();
        $keyCorreo = $auxiliar->generarKey();
        //ACTUALIZO REGISTRO
        $sql       = "UPDATE INCIDENCIA_FICHA_SEGURIDAD_MATERIAL_IDIOMA SET
                ESTADO_COMUNICACION_PROVEEDOR ='Comunicada a Proveedor-Pdte. Respuesta'
                ,FECHA_MODIFICACION = '" . date("Y-m-d H:i:s") . "'
                ,KEY_CORREO = '$keyCorreo'
                WHERE ID_INCIDENCIA_FICHA_SEGURIDAD_MATERIAL_IDIOMA = $idObjeto";
        $TipoError = "ErrorEjecutarSql";

        $bd->ExecSQL($sql);

        //FICHA SEGURIDAD ACTUALIZADA
        $rowFichaSeguridadActualizada = $bd->VerReg("INCIDENCIA_FICHA_SEGURIDAD_MATERIAL_IDIOMA", "ID_INCIDENCIA_FICHA_SEGURIDAD_MATERIAL_IDIOMA", $idObjeto, "No");

        $aviso->envioAvisoFichaSeguridadMaterial($rowFichaSeguridad->ID_PROVEEDOR, $rowFichaSeguridad->ID_MATERIAL, $idObjeto, $rowFichaSeguridad->ID_IDIOMA, "Si", $txObservaciones);


        // LOG MOVIMIENTOS
        if (ENTORNO != "PRODUCCION"):
            $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Incidencia Ficha Seguridad Material", $idObjeto, "Rechazar Ficha", "INCIDENCIA_FICHA_SEGURIDAD_MATERIAL_IDIOMA", $rowFichaSeguridad, $rowFichaSeguridadActualizada);
        else:
            $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Incidencia Ficha Seguridad Material", $idObjeto, "Rechazar Ficha", "INCIDENCIA_FICHA_SEGURIDAD_MATERIAL_IDIOMA");
        endif;
//FINALIZO LA TRANSACCION
        $bd->commit_transaction();

    endif;


endif;
    ?>

    <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
    <html>
    <head>
        <? require_once $pathClases . "lib/gral_js.php"; ?>
        <script type="text/javascript" language="javascript">
            function CerrarFancy() {
                window.parent.jQuery.fancybox.close();

                return false;
            }

            function EnviarObservaciones() {
                jQuery('#btnEnviarObservaciones').css('color', '#CCCCCC');
                jQuery('#btnEnviarObservaciones').attr('onclick', 'return false;');
                jQuery('#btnEnviarObservaciones').attr('title', '<?= $auxiliar->traduce("Se esta generando la notificacion, espere por favor.", $administrador->ID_ADMINISTRADOR)?>');
                document.Form.accion.value = 'EnviarNotificaciones';
                document.Form.submit();
            }
        </script>
    </head>
    <body class="fancy" bgcolor="#FFFFFF" background="<? echo $pathRaiz ?>imagenes/fondo_pantalla.gif" leftmargin="0"
          topmargin="0" marginwidth="0" marginheight="0">

    <form method="post" name="Form" action="ficha_observaciones.php">
        <input type="hidden" name="accion" value="">
        <input type="hidden" name="idRegistroTexto" value="<?= $row->ID_OBSERVACION_SISTEMA ?>">
        <input type="hidden" name="tipoObjeto" value="<?= $tipoObjeto ?>">
        <input type="hidden" name="idObjeto" value="<?= $idObjeto ?>">
        <input type="hidden" name="txObservaciones" value="<?= $txObservaciones ?>">

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
                                                                        <? if ($tipoObjeto == 'NECESIDAD'): ?>

                                                                            <tr>
                                                                                <td width="24%" height="20" align="left"
                                                                                    rowspan="3"
                                                                                    valign="middle"
                                                                                    class="textoazul"><?= $auxiliar->traduce("Enviar A", $administrador->ID_IDIOMA) ?>
                                                                                    :
                                                                                </td>
                                                                                <td width="24%" align="left"
                                                                                    valign="middle" class="textoazul">
                                                                                    <?
                                                                                    $Estilo = 'check_estilo';
                                                                                    $html->Option("chDestinatarios[]", "Check", "Logistico", "");
                                                                                    unset($jscript); ?>
                                                                                    <?= $auxiliar->traduce("Perfil Logistico", $administrador->ID_IDIOMA);
                                                                                    ?>
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td width="24%" align="left"
                                                                                    valign="middle" class="textoazul">
                                                                                    <?
                                                                                    $Estilo = 'check_estilo';
                                                                                    $html->Option("chDestinatarios[]", "Check", "Tecnico", "");
                                                                                    unset($jscript); ?>
                                                                                    <?= $auxiliar->traduce("Perfil Tecnico", $administrador->ID_IDIOMA);
                                                                                    ?>
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td width="24%" align="left"
                                                                                    valign="middle" class="textoazul">
                                                                                    <?
                                                                                    $Estilo = 'check_estilo';
                                                                                    $html->Option("chDestinatarios[]", "Check", "Comprador", "");

                                                                                    unset($jscript); ?>
                                                                                    <?= $auxiliar->traduce("Comprador", $administrador->ID_IDIOMA);
                                                                                    ?>
                                                                                </td>
                                                                            </tr>
                                                                        <? endif; ?>
                                                                    </table>
                                                                </td>
                                                                <td width="4" bgcolor="#D9E3EC" class="lineaderecha">

                                                                </td>
                                                            </tr>

                                                            <tr bgcolor="#D9E3EC">
                                                                <td height="5" colspan="3" bgcolor="#D9E3EC"
                                                                    class="lineabajodereizq"><img
                                                                            src="<? echo $pathRaiz ?>imagenes/transparente.gif"
                                                                            width="10" height="5"></td>
                                                            </tr>

                                                            <!--  "----------" -->

                                                            <textarea rows="14" class="copyright"
                                                                      style="width: 420px; resize: none;"
                                                                      name="txObservaciones" <? echo($editable == 1 ? '' : "disabled='disabled'"); ?>
              id="txObservaciones"><?
                                                                echo $txObservaciones;
                                                                ?></textarea>
                                                            <!--  "----------" -->
                                                        </table>
                                                        <img src="<? echo $pathRaiz ?>imagenes/transparente.gif"
                                                             width="10" height="5"></td>
                                                    <td width="20" align="center" valign="bottom"
                                                        class="lineaizquierda">
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
                                                            &nbsp;<a class="senaladoazul" onclick="return CerrarFancy();" href="#">
                                                                &nbsp;&nbsp;&nbsp;&nbsp;<?= $auxiliar->traduce("Cerrar", $administrador->ID_IDIOMA) ?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                                            </a>
                                                        </span>
                                                    </td>
                                                    <td valign="middle" height="25" align="right" colspan="2">
                                                        <span class="textoazul">
                                                            <a class="senaladoverde" onclick="return EnviarObservaciones();" href="#" id="btnEnviarObservaciones">
                                                                &nbsp;&nbsp;&nbsp;&nbsp;<?= $auxiliar->traduce("Enviar", $administrador->ID_IDIOMA) ?>&nbsp;&nbsp;&nbsp;&nbsp;
                                                            </a>&nbsp;
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