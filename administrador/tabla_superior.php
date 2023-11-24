<?

/// FUNCIONES AJAX  /////////////////////////////////////////////////////////
/// NOTA: lo hacemos asi y no usamos xajax porque si usamos aqui xajax se 'pega' con otro que definamos en el contenido de las páginas... por eso en este caso lo hacemos con jQuery
/* if($opcion_ajax == 'GetHoraEnHusoHorarioUsuario')
{
    // PATHS DE LA WEB
    $pathRaiz = "";
    $pathClases = "../";

    // INCLUDES DE LIBRERIAS PROPIAS
    require_once $pathClases."lib/basedatos.php";
    require_once $pathClases."lib/administrador.php";
    require_once $pathClases."lib/html.php";
    require_once $pathClases."lib/navegar.php";
    require_once $pathClases."lib/auxiliar.php";
    session_start();

    if(!$administrador || !$administrador->ID_HUSO_HORARIO_PHP){
        return '-';
    }


    include $pathRaiz."seguridad_admin.php";

    global $bd;
    global $administrador;
    global $NotificaErrorPorEmail;
    global $auxiliar;

    $respuesta = '-';

    //busco la hora en el huso del administrador
    if ($administrador && $administrador->ID_HUSO_HORARIO_PHP):
        $hora_de_huso = $auxiliar->fechaFmtoEspHora($auxiliar->fechaToTimezoneUser(date("Y-m-d H:i:s")),false);

        //le quito los segundos
        $hora_de_huso = substr($hora_de_huso,0,-3);
        $hora_de_huso = str_replace(' ','     ',$hora_de_huso);
        $hora_de_huso = str_replace(':','<span style="width: 4px;text-align: center;display: inline-block;"><span class="blink">:</span></span>',$hora_de_huso);
        $hora_de_huso = str_replace('     ','<span style="width: 15px;display: inline-block;"></span>',$hora_de_huso);
        $respuesta = '<strong style="color: #000;">'.$hora_de_huso.'</strong>';
    endif;

    echo $respuesta;
    exit;
}*/


$URL = $auxiliar->dameURL();

if (substr( (string) $URL, -4) == '.php'):
    $URL = $URL . '?idIdioma=';
else:
    $URL = $URL . '&idIdioma=';
endif;
$sqlAdministradorHusoHorario    = "SELECT AH.* FROM ADMINISTRADOR_HUSO_HORARIO AH
                                    INNER JOIN HUSO_HORARIO_ HS ON HS.ID_HUSO_HORARIO = AH.ID_HUSO_HORARIO
                                    WHERE ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                    ORDER BY HS.GMT DESC ";
$resultAdministradorHusoHorario = $bd->ExecSQL($sqlAdministradorHusoHorario);

//SACAMOS EL NUMERO DE SEMANAS ACTUAL
$tiempo_actual = time();
$semanas_total = idate('W', (int)$tiempo_actual);

if (ENTORNO == 'PRODUCCION'):
    $colorCabecera = "#000000";
elseif (ENTORNO == 'INTEGRACION'):
    $colorCabecera = "#d4ff00";
else:
    $colorCabecera = "#5d00ff";
endif;
?>
<? //FUNCION PARA ACTUALIZAR LA HORA EN LA PARTE SUPERIOR?>
<script type="text/javascript" language="JavaScript">
    <? if($resultAdministradorHusoHorario != false):?>
    function mostrarHusosHorarios() {
        marcado = (jQuery('#husosHorarios').is(':visible') ? '0' : '1');
        jQuery.ajax({
            cache: false,
            url: '<?=$pathRaiz?>guardar_estado_husos_horarios.php?chHusosHorariosVisible=' + marcado
        });
        if (!jQuery("#husosHorarios").is (':visible')) {
            jQuery("#husosHorarios").show();

        } else if (jQuery("#husosHorarios").is (':visible')) {
            jQuery("#husosHorarios").hide();
        }

    }

    <?
    $sqlAdministradorHusoHorarioC = "SELECT AH.* FROM ADMINISTRADOR_HUSO_HORARIO AH
                                    INNER JOIN HUSO_HORARIO_ HS ON HS.ID_HUSO_HORARIO = AH.ID_HUSO_HORARIO
                                    WHERE ID_ADMINISTRADOR = $administrador->ID_ADMINISTRADOR
                                    ORDER BY HS.GMT DESC";
    $resultAdministradorHusoHorarioC = $bd->ExecSQL($sqlAdministradorHusoHorarioC);
    $numRegHusosHorariosAdministradorC = $bd->NumRegs($resultAdministradorHusoHorarioC);
    $i= 0;
    $arrayFechasC = array();


    while($rowAdminHusoHorarioTSC = $bd->SigReg($resultAdministradorHusoHorarioC)):
        $rowHusoHorarioTSC = $bd->VerReg("HUSO_HORARIO_", "ID_HUSO_HORARIO", $rowAdminHusoHorarioTSC->ID_HUSO_HORARIO, "No");
        $arrayFechasC[] = array('fecha'=> str_replace( '-','/',(string)$auxiliar->fechaToTimezoneUserParam($rowHusoHorarioTSC->ID_HUSO_HORARIO_PHP,date("Y-m-d H:i:s"))),'nombre'=>$rowAdminHusoHorarioTSC->NOMBRE);
    endwhile;

    //ORDENO LOS HUSOS HORARIOS POR FECHA
    function ordenar( $b, $a ) {
        return strtotime( (string)$a['fecha']) - strtotime( (string)$b['fecha']);
    }

    usort($arrayFechasC,'ordenar');
    foreach($arrayFechasC as $elementoC):?>

    //CREO VARIABLES DINAMICAS GEGNERALES PARA QUE SE ACTUALICEN CORRECTAMENTE
    eval("var d" + <?=$i?> +"date ='" + new Date('<?=$elementoC['fecha'];?>') + "'");
    <? $i++;
    endforeach; ?>

    function actualizarHoraEnHusoHorario_cabecera_lista(k, nombre) {
        //sumo 5 segundos (los segundos que he puesto para actualizar el reloj)

        //RECOGO LA VARIABLE DINAMICA
        fechaD = new Date(eval('d' + k + 'date'));

        //ACTUALIZO 5 SEGUNDOS LA VARIABLE DINAMICA
        eval("d" + k + "date ='" + new Date(fechaD.setSeconds(fechaD.getSeconds() + 5)) + "'");

        var anno = fechaD.getFullYear();
        var mes = padLeft((fechaD.getMonth() + 1), 2, '0');
        var dia = padLeft(fechaD.getDate(), 2, '0');
        var horas = padLeft(fechaD.getHours(), 2, '0');
        var minutos = padLeft(fechaD.getMinutes(), 2, '0');
        var segundos = padLeft(fechaD.getSeconds(), 2, '0');

        var string_fecha = '';
        switch ('<?=$administrador->FMTO_FECHA?>') {
            case 'yyyy-mm-dd':
                string_fecha = anno + '-' + mes + '-' + dia;
                break;
            case 'mm-dd-yyyy':
                string_fecha = mes + '-' + dia + '-' + anno;
                break;
            case 'dd-mm-yyyy':
            default:
                string_fecha = dia + '-' + mes + '-' + anno;
                break;
        }

        jQuery("#InfoHoraEnHusoHorario_cabecera_nombre_" + k).html(nombre + ":");
        jQuery("#InfoHoraEnHusoHorario_cabecera_fecha_" + k).html(string_fecha);
        jQuery("#InfoHoraEnHusoHorario_cabecera_hora_" + k).html(horas);
        jQuery("#InfoHoraEnHusoHorario_cabecera_minutos_" + k).html(minutos);
    }

    function parpadeo_lista(k) {
        if (document.getElementById('texto_parpadeo_' + k)) {
            if (document.getElementById('texto_parpadeo_' + k).style.visibility == "hidden") {
                document.getElementById('texto_parpadeo_' + k).style.visibility = "visible"
            }
            else {
                document.getElementById('texto_parpadeo_' + k).style.visibility = "hidden"
            }
        }
    }

    //actualizamos el tiempo cada X segundos
    jQuery(document).ready(function () {
        <?
        $i= 0;
        foreach($arrayFechasC as $elemento):?>

        actualizarHoraEnHusoHorario_cabecera_lista('<?=$i?>', '<?=$elemento['nombre']?>');
        setInterval(function () {
            actualizarHoraEnHusoHorario_cabecera_lista('<?=$i?>', '<?=$elemento['nombre']?>');
        }, 5000);

        <? $i++;
        endforeach;?>


    });

    function padLeft(nr, n, str) {
        return Array(n - String(nr).length + 1).join(str || '0') + nr;
    }
    <?endif;?>
</script>
<table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
    <tr>
        <!--        <td>&nbsp;</td>-->
        <td colspan="3" height="1" align="center">
            <table width="936" height="1" border="0" cellpadding="0" cellspacing="0" bgcolor="#FFFFFF">
                <tr>
                    <td width="750" align="left" valign="middle" class="textoazul">

                        <?php /*?><? if( ($administrador->ID_ADMINISTRADOR == $idJavier) || ($administrador->ID_ADMINISTRADOR == $idCarlos) || ($administrador->ID_ADMINISTRADOR == $idDavid) || ($administrador->ID_ADMINISTRADOR == $idIbarrula) || ($administrador->ID_ADMINISTRADOR == $idCesar)): ?>

					<table width="152" height="1" border="0" cellpadding="0" cellspacing="0">
						<tr>
							<td width="24" height="1">&nbsp;</td>
							<td width="24" height="1" align="center" valign="middle">&nbsp;</td>
							<td width="50" height="1"><a href="<?=$URL?>ESP" class="enlace_contacto_ayuda">Español</a></td>
							<td width="15" height="1" align="center" valign="bottom"><img src="<? echo $pathRaiz ?>imagenes/separacion.gif" width="15" height="23" /></td>
							<td height="1">&nbsp;<a href="<?=$URL?>ENG" class="enlace_contacto_ayuda">English</a></td>
						</tr>
					</table>

				<? endif; ?><?php */ ?>


                        <script type="text/javascript">

                            //FUNCION QUE INCLUYE POR JAVASCRIPT UN JS O UN CSS
                            function loadjscssfile(filename, filetype) {
                                if (filetype == "js") { //if filename is a external JavaScript file
                                    var fileref = document.createElement('script')
                                    fileref.setAttribute("type", "text/javascript")
                                    fileref.setAttribute("src", filename)
                                }
                                else if (filetype == "css") { //if filename is an external CSS file
                                    var fileref = document.createElement("link")
                                    fileref.setAttribute("rel", "stylesheet")
                                    fileref.setAttribute("type", "text/css")
                                    fileref.setAttribute("href", filename)
                                }
                                if (typeof fileref != "undefined")
                                    document.getElementsByTagName("head")[0].appendChild(fileref)
                            }


                            //COMPRUEBO SI TENGO INCLUIDO JQUERY
                            if (typeof jQuery == 'undefined') {
                                loadjscssfile("<?=$pathClases?>recursos/js/fancybox/jquery-1.6.2.min.js", "js");
                            }

                            //COMPRUEBO SI TENGO INCLUIDO FANCYBOX
                            if (typeof jQuery.fancybox == 'undefined') {
                                loadjscssfile("<?=$pathClases?>recursos/js/fancybox/jquery.fancybox-1.3.4.js", "js");
                                loadjscssfile("<?=$pathClases?>recursos/js/fancybox/jquery.fancybox-1.3.4.css", "css");
                            }


                            jQuery(document).ready(function () {
                                //FANCYBOX CON IFRAME PARA EL BUSCADOR
                                jQuery("a.fancyboxDatosDefecto").fancybox({
                                    'type': 'iframe',
                                    'width': '100%',
                                    'height': '100%',
                                    'hideOnOverlayClick': false,
                                    'onClosed': function () {
                                        window.location.reload();
                                    }
                                });
                                jQuery("a.fancyboxCalendario").fancybox({
                                    'type': 'iframe',
                                    'width': '100%',
                                    'height': '100%',
                                    'hideOnOverlayClick': false
                                });
                            });


                        </script>


                        <?

                        //OBTENGO DATOS DE ALMACEN DEL ALMACEN POR DEFECTO DEL ADMINISTRADOR
                        if ($administrador->ID_ALMACEN_POR_DEFECTO != NULL):
                            $NotificaErrorPorEmail          = "No";
                            $rowAlmacenDefectoAdministrador = $bd->VerReg("ALMACEN", "ID_ALMACEN", $administrador->ID_ALMACEN_POR_DEFECTO, "No");
                            unset($NotificaErrorPorEmail);
                        endif;

                        //OBTENGO DATOS DE CENTRO FISICO POR DEFECTO DEL ADMINISTRADOR
                        if ($administrador->ID_CENTRO_FISICO_POR_DEFECTO != NULL):
                            $NotificaErrorPorEmail               = "No";
                            $rowCentroFisicoDefectoAdministrador = $bd->VerReg("CENTRO_FISICO", "ID_CENTRO_FISICO", $administrador->ID_CENTRO_FISICO_POR_DEFECTO, "No");
                            unset($NotificaErrorPorEmail);
                        endif;

                        echo "&nbsp;&nbsp;<strong>" . $auxiliar->traduce("Centro Físico por defecto", $administrador->ID_IDIOMA) . "</strong>:&nbsp;" . ($rowCentroFisicoDefectoAdministrador != false ? ($rowCentroFisicoDefectoAdministrador->REFERENCIA) : ' - ');

                        echo "&nbsp;&nbsp;<strong>" . $auxiliar->traduce("Almacén por defecto", $administrador->ID_IDIOMA) . "</strong>:&nbsp;" . ($rowAlmacenDefectoAdministrador != false ? ($rowAlmacenDefectoAdministrador->REFERENCIA . ' - ' . $rowAlmacenDefectoAdministrador->NOMBRE) : ' - ');

                        ?>

                        &nbsp;<a href="<?= $pathRaiz ?>administracion/datos_usuario_defecto/index.php"
                                 class="fancyboxDatosDefecto senaladoazul" onclick="return false;">&nbsp;&nbsp;&nbsp;&nbsp;<?= $auxiliar->traduce("Modificar", $administrador->ID_IDIOMA) ?>
                            &nbsp;&nbsp;&nbsp;&nbsp;</a>&nbsp;

                    </td>


                    <? //FUNCION PARA ACTUALIZAR LA HORA EN LA PARTE SUPERIOR?>
                    <script type="text/javascript" language="JavaScript">
                        // function actualizarHoraEnHusoHorario_cabecera(){
                        //jQuery("#InfoHoraEnHusoHorario_cabecera").load("<?//=$url_raiz?>//administrador/tabla_superior.php?opcion_ajax=GetHoraEnHusoHorarioUsuario",function(){
                        //jQuery('.blink').each(function() {
                        //var elem = jQuery(this);
                        //setInterval(function() {
                        ////elem.toggle();
                        //elem.fadeTo(300, 0.1).fadeTo(300, 1.0);
                        //}, 2000);
                        //});
                        //});
                        //}

                        //Defino la hora incial, la del usuario en su uso
                        var d = new Date('<?=str_replace( '-','/',(string)$auxiliar->fechaToTimezoneUser(date("Y-m-d H:i:s")));?>');

                        function actualizarHoraEnHusoHorario_cabecera() {

                            //sumo 5 segundos (los segundos que he puesto para actualizar el reloj)
                            d.setSeconds(d.getSeconds() + 5);

                            var anno = d.getFullYear();
                            var mes = padLeft((d.getMonth() + 1), 2, '0');
                            var dia = padLeft(d.getDate(), 2, '0');
                            var horas = padLeft(d.getHours(), 2, '0');
                            var minutos = padLeft(d.getMinutes(), 2, '0');
                            var segundos = padLeft(d.getSeconds(), 2, '0');

                            var string_fecha = '';
                            switch ('<?=$administrador->FMTO_FECHA?>') {
                                case 'yyyy-mm-dd':
                                    string_fecha = anno + '-' + mes + '-' + dia;
                                    break;
                                case 'mm-dd-yyyy':
                                    string_fecha = mes + '-' + dia + '-' + anno;
                                    break;
                                case 'dd-mm-yyyy':
                                default:
                                    string_fecha = dia + '-' + mes + '-' + anno;
                                    break;
                            }

                            jQuery("#InfoHoraEnHusoHorario_cabecera_fecha").html(string_fecha);
                            jQuery("#InfoHoraEnHusoHorario_cabecera_hora").html(horas);
                            jQuery("#InfoHoraEnHusoHorario_cabecera_minutos").html(minutos);
                        }

                        function parpadeo() {
                            if (document.getElementById('texto_parpadeo')) {
                                if (document.getElementById('texto_parpadeo').style.visibility == "hidden") {
                                    document.getElementById('texto_parpadeo').style.visibility = "visible"
                                }
                                else {
                                    document.getElementById('texto_parpadeo').style.visibility = "hidden"
                                }
                            }
                        }

                        //actualizamos el tiempo cada X segundos
                        jQuery(document).ready(function () {
                            actualizarHoraEnHusoHorario_cabecera();
                            setInterval(function () {
                                actualizarHoraEnHusoHorario_cabecera();
                            }, 5000);

                            jQuery('.blink').each(function () {
                                var elem = jQuery(this);
                                setInterval(function () {
                                    //elem.toggle();
                                    elem.fadeTo(300, 0.1).fadeTo(300, 1.0);
                                }, 2000);
                            });

                            setInterval(function () {
                                parpadeo();
                            }, 200);
                        });

                        function padLeft(nr, n, str) {
                            return Array(n - String(nr).length + 1).join(str || '0') + nr;
                        }
                    </script>

                    <td id="InfoHoraEnHusoHorario_cabecera" class="textoazul" width="200px" align="center">
                        <? //AQUI METEREMOS POR JS ?>
                        <strong style="color: #000;">
                            <span id="InfoHoraEnHusoHorario_cabecera_fecha" style="margin-right: 15px;"></span>
                            <span id="InfoHoraEnHusoHorario_numero_semanas" style="margin-right: 15px;"
                                  title="<?= $auxiliar->traduce("Semana", $administrador->ID_IDIOMA) ?>"><? echo $auxiliar->traduce("SIGLA_S_SEMANA", $administrador->ID_IDIOMA) . $semanas_total ?></span>
                            <span id="InfoHoraEnHusoHorario_cabecera_hora" style="margin-right: -2px;"></span>
                            <span class="blink">:</span>
                            <span id="InfoHoraEnHusoHorario_cabecera_minutos" style="margin-left: -2px;"></span>
                        </strong>
                    </td>

                    <td height="1" align="right" valign="middle">
                        <table width="<?= (ENTORNO == 'PRODUCCION' ? '170' : '152');?>" height="1" border="0" cellpadding="0" cellspacing="0">
                            <tr>
                                <td>
                                    <? if ($numRegHusosHorariosAdministradorC > 0): ?>
                                        <a href="#" onClick="mostrarHusosHorarios();"><img
                                                src="<? echo $pathRaiz ?>imagenes/reloj-mundial.png" align="top"
                                                width="20" height="20"></a>&nbsp;
                                    <? endif; ?>
                                </td>
                                <td>

                                    <a href="<? echo $pathRaiz ?>calendarios_favoritos_administrador.php"
                                       class="fancyboxCalendario" onclick="return false"><img
                                            src="<? echo $pathRaiz ?>imagenes/calendario1.png" align="top"
                                            width="20" height="20"></a>&nbsp;
                                </td>
                                <td width="50" height="1"><a href="<?= $pathRaiz ?>contacto/ficha.php"
                                                             class="enlace_contacto_ayuda"><?= $auxiliar->traduce("Contacto", $administrador->ID_IDIOMA) ?></a>
                                </td>
                                <td width="15" height="1" align="center" valign="bottom"><img
                                        src="<? echo $pathRaiz ?>imagenes/separacion.gif" width="15" height="23"/>
                                </td>
                                <td height="1"><a href="<?= $pathRaiz ?>ayuda.php"
                                                  class="enlace_contacto_ayuda"><?= $auxiliar->traduce("Ayuda", $administrador->ID_IDIOMA) ?></a>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
            <table width="100%" align="center" height="1" border="0" cellpadding="0" cellspacing="0" bgcolor="#E3EEF7"
                   id="husosHorarios" style="display:<?= $_SESSION['estado_husos_horarios'] == '1' ? '' : 'none'; ?>">
                <tr>
                    <?
                    $numReg = $bd->NumRegs($resultAdministradorHusoHorario);
                    for ($k = 0; $k < $numReg; $k++):
                        ?>


                        <td id="InfoHoraEnHusoHorario_cabecera_<?= $k ?>" class="textoazul" width="200px"
                            align="center">
                            <? //AQUI METEREMOS POR JS
                            ?>
                            <strong class="textoverde">
                                <span id="InfoHoraEnHusoHorario_cabecera_nombre_<?= $k ?>"
                                      style="margin-right: 5px;"></span>
                                <span id="InfoHoraEnHusoHorario_cabecera_fecha_<?= $k ?>"
                                      style="margin-right: 5px;"></span>
                                <span id="InfoHoraEnHusoHorario_cabecera_hora_<?= $k ?>"
                                      style="margin-right: -2px;"></span>
                                <span class="blink">:</span>
                                <span id="InfoHoraEnHusoHorario_cabecera_minutos_<?= $k ?>"
                                      style="margin-left: -2px;"></span>
                            </strong>
                        </td>

                    <?
                    endfor; ?>
                </tr>
            </table>
        </td>
        <!--        <td>&nbsp;</td>-->
    </tr>

    <?
    //COMPRUEBO SI EL SISTEMA ESTA BLOQUEADO
    $sistemaBloqueado = false;

    //HALLO LA FECHA Y HORA ACTUAL
    $fechaActualBloqueoSistema = date("Y-m-d H:i:s");

    $sqlBloqueoSistema    = "SELECT * FROM AVISO_SISTEMA_BLOQUEADO";
    $resultBloqueoSistema = $bd->ExecSQL($sqlBloqueoSistema);
    $rowBloqueoSistema    = $bd->SigReg($resultBloqueoSistema);

    $fechaHoraInicialBloqueoSistema = $rowBloqueoSistema->FECHA_INICIO_BLOQUEO . " " . $rowBloqueoSistema->HORA_INICIO_BLOQUEO;
    $fechaHoraFinalBloqueoSistema   = $rowBloqueoSistema->FECHA_FIN_BLOQUEO . " " . $rowBloqueoSistema->HORA_FIN_BLOQUEO;

    if (($fechaActualBloqueoSistema > $fechaHoraInicialBloqueoSistema) && ($fechaActualBloqueoSistema < $fechaHoraFinalBloqueoSistema)): //ESTAMOS EN EL RANGO DEFINIDO POR EL BLOQUEO Y ACTIVADO
        $sistemaBloqueado = true;
    endif;

    $sistemaPreaviso = false;
    $fechaHoraInicioAviso = $rowBloqueoSistema->FECHA_INICIO_AVISO . " " . $rowBloqueoSistema->HORA_INICIO_AVISO;
    if($fechaActualBloqueoSistema >= $fechaHoraInicioAviso){
        $sistemaPreaviso = true;
    }

    if($fechaHoraFinalBloqueoSistema < $fechaActualBloqueoSistema){
        $sistemaBloqueado = false;
        $sistemaPreaviso = false;
    }

    $avisoActivo = $rowBloqueoSistema->ACTIVO == '1';

    ?>

    <?if($avisoActivo == true):?>
        <? if ($sistemaBloqueado == true): ?>
            <tr>
                <td width="33%" height="1" align="center"
                    bgcolor="<?= $colorCabecera ?>">&nbsp;
                </td>
                <td align="center" bgcolor="<?= $colorCabecera ?>">
                    <table width="936" height="32" border="0" cellpadding="0" cellspacing="0">
                        <tr>
                            <td align="center" valign="middle" style="padding: 5px">
                                <font style="font-weight:bold;color:red">
                                    <span class="parpadeante"><? echo "¡ " . $auxiliar->traduce("Sistema Bloqueado", $administrador->ID_IDIOMA) . " ! ";?></span><br>
                                    <?=$auxiliar->traduce('Desde', $administrador->ID_IDIOMA) . ": " . ($auxiliar->fechaFmtoEspHora($rowBloqueoSistema->FECHA_INICIO_BLOQUEO . ' ' . $rowBloqueoSistema->HORA_INICIO_BLOQUEO, true, false, false)) . " - " . $auxiliar->traduce('Hasta', $administrador->ID_IDIOMA) . ': ' . ($auxiliar->fechaFmtoEspHora($rowBloqueoSistema->FECHA_FIN_BLOQUEO . ' ' . $rowBloqueoSistema->HORA_FIN_BLOQUEO, true, false, false)) ?>
                                    <?if($rowBloqueoSistema->MOSTRAR_TEXTO_RESUMEN):?>
                                        <br>
                                        <?= ($administrador->ID_IDIOMA == "ESP" ? $rowBloqueoSistema->TEXTO_RESUMEN : $rowBloqueoSistema->TEXTO_RESUMEN_ENG)?>
                                    <?endif;?>
                                </font>
                            </td>
                        </tr>
                    </table>
                </td>
                <td width="33%" align="center" bgcolor="<?= $colorCabecera ?>">
                    &nbsp;</td>
            </tr>
        <? endif; ?>

        <!--    EL SISTEMA NO ESTÁ BLOQUEADO SINO QUE ESTÁ EN ESTADO DE AVISO DE QUE VA A SER BLOQUEADO-->
        <?if(!$sistemaBloqueado && $sistemaPreaviso):?>
            <tr>
                <td width="33%" height="1" align="center"
                    bgcolor="<?= $colorCabecera ?>">&nbsp;
                </td>
                <td align="center" bgcolor="<?= $colorCabecera ?>">
                    <table width="936" height="32" border="0" cellpadding="0" cellspacing="0">
                        <tr>
                            <td align="center" valign="middle"><font
                                    style="font-weight:bold;color:red"><?= $auxiliar->traduce('El sistema permanecerá bloqueado', $administrador->ID_IDIOMA) . ". <br>" . $auxiliar->traduce('Desde', $administrador->ID_IDIOMA) . ": " . ($auxiliar->fechaFmtoEspHora($rowBloqueoSistema->FECHA_INICIO_BLOQUEO . ' ' . $rowBloqueoSistema->HORA_INICIO_BLOQUEO, true, false, false)) . " - " . $auxiliar->traduce('Hasta', $administrador->ID_IDIOMA) . ': ' . ($auxiliar->fechaFmtoEspHora($rowBloqueoSistema->FECHA_FIN_BLOQUEO . ' ' . $rowBloqueoSistema->HORA_FIN_BLOQUEO, true, false, false)) ?>
                                    <?if($rowBloqueoSistema->MOSTRAR_TEXTO_RESUMEN):?>
                                        <br>
                                        <?= ($administrador->ID_IDIOMA == "ESP" ? $rowBloqueoSistema->TEXTO_RESUMEN : $rowBloqueoSistema->TEXTO_RESUMEN_ENG)?>
                                    <?endif;?>
                                </font>
                            </td>
                        </tr>
                    </table>
                </td>
                <td width="33%" align="center" bgcolor="<?= $colorCabecera ?>">
                    &nbsp;</td>
            </tr>
        <?endif;?>
    <?endif;?>

    <tr>
        <td width="33%" height="1" align="center" bgcolor="<?= $colorCabecera ?>">
            &nbsp;</td>
        <td align="center" bgcolor="<?= $colorCabecera ?>">
            <table width="936" height="32" border="0" cellpadding="0" cellspacing="0">
                <tr>
                    <td align="right" valign="middle"><img
                            src="<? echo $pathRaiz ?>imagenes/<?= FONDO_CABECERA_DENTRO; ?>"/></td>
                </tr>
            </table>
        </td>
        <td width="33%" align="center" bgcolor="<?= $colorCabecera ?>">&nbsp;</td>
    </tr>
</table>
