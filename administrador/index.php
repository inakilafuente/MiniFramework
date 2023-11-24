<?
header('Content-Type: text/html;charset=windows-1252');

// PATHS DE LA WEB//
$pathRaiz   = "./";
$pathClases = "../";
require_once $pathClases . "lib/globales.php";
require_once $pathClases . "lib/basedatos.php";
require_once $pathClases . "lib/auxiliar.php";
require_once $pathClases . "lib/administrador.php";
require_once $pathClases . "lib/xajax25_php7/xajax_core/xajax.inc.php";

session_start();

$paginaActual = "index.php";

$bd       = new basedatos();
$auxiliar = new auxiliar();

if ($administrador == NULL):
    $administrador = new administrador();
endif;

$_SESSION["administrador"] = $administrador;

$bd->pagina_inicial = "Si"; // PARA QUE NO DE ERROR DE NO CADUCADA, SOLO EN LAS PAG INCIAL
$bd->conectar();

$carpeta_imagenes = "imagenes";

// IDIOMA_USER --> COOKIE DONDE SE QUEDA REGISTRADO EL ANTERIOR IDIOMA UTILIADO POR EL USUARIO


// SE ESTABLECE EL IDIOMA DE LA COOKIE EN EL CASO DE EXISTIR, SI NO EL SELECCIONADO POR EL USUARIO Y SI NO
// EL DEL NAVEGADOR
if ($IDIOMA_USER != "" && $idIdioma == ""): $idIdioma = $IDIOMA_USER; endif;
if ($idIdioma == "" && $txLogin == ""):
    $idIdioma = "ESP";
endif;

if ($idIdioma == 'ESP'):
    $administrador->setIdioma("ESP");
elseif ($idIdioma == 'ENG'):
    $administrador->setIdioma("ENG");
endif;

$sqlBloqueoSistema    = "SELECT * FROM AVISO_SISTEMA_BLOQUEADO";
$resultBloqueoSistema = $bd->ExecSQL($sqlBloqueoSistema);
$rowBloqueoSistema    = $bd->SigReg($resultBloqueoSistema);

//IDIOMAS TEXTOS ENG ESP

$introduceESP  = $auxiliar->traduce("Introduzca sus datos de acceso", "ESP") . ":";
$introduceENG  = $auxiliar->traduce("Introduzca sus datos de acceso", "ENG") . ":";
$usuarioESP    = $auxiliar->traduce("Usuario", "ESP") . ":";
$usuarioENG    = $auxiliar->traduce("Usuario", "ENG") . ":";
$contraseñaESP = $auxiliar->traduce("Contraseña", "ESP") . ":";
$contraseñaENG = $auxiliar->traduce("Contraseña", "ENG") . ":";
$idiomaESP     = $auxiliar->traduce("Idioma", "ESP") . ":";
$idiomaENG     = $auxiliar->traduce("Idioma", "ENG") . ":";
$aceptarESP    = " &nbsp;&nbsp;&nbsp;" . $auxiliar->traduce("Aceptar", "ESP") . " &nbsp;&nbsp;&nbsp;";
$aceptarENG    = " &nbsp;&nbsp;&nbsp;" . $auxiliar->traduce("Aceptar", "ENG") . " &nbsp;&nbsp;&nbsp;";
$borrarESP     = " &nbsp;&nbsp;&nbsp;" . $auxiliar->traduce("Borrar", "ESP") . " &nbsp;&nbsp;&nbsp;";
$borrarENG     = " &nbsp;&nbsp;&nbsp;" . $auxiliar->traduce("Borrar", "ENG") . " &nbsp;&nbsp;&nbsp;";

$mensajeAvisoESP = $auxiliar->traduce('El sistema permanecerá bloqueado', "ESP");
$mensajeAvisoENG = $auxiliar->traduce('El sistema permanecerá bloqueado', "ENG");

$mensajeBloqueoESP = "¡".$auxiliar->traduce("Sistema Bloqueado", "ESP")."!";
$mensajeBloqueoENG = "¡".$auxiliar->traduce("Sistema Bloqueado", "ENG")."!";

$desdeESP = $auxiliar->traduce('Desde', "ESP");
$desdeENG = $auxiliar->traduce('Desde', "ENG");

$hastaESP = $auxiliar->traduce('Hasta', "ESP");
$hastaENG = $auxiliar->traduce('Hasta', "ENG");

$textoResumenESP = $rowBloqueoSistema->TEXTO_RESUMEN;
$textoResumenENG = $rowBloqueoSistema->TEXTO_RESUMEN_ENG;


// XAJAX
//instanciamos el objeto de la clase xajax
$xajax = new xajax();
$xajax->setCharEncoding('ISO-8859-1');
$xajax->configure('defaultMethod', 'POST');
//$xajax->configure('debug',true);
//$xajax->decodeUTF8InputOn();


/***
 * Funcion para cambiar las traducciones en los elementos html
 *
 * @param $respuesta
 * @param $auxiliar
 * @param $administrador
 */
function cambiarLenguaje($respuesta, $auxiliar, $administrador, $rowBloqueoSistema)
{

    $respuesta->clear("intro", "innerHTML");
    $respuesta->prepend("intro", "innerHTML", $auxiliar->traduce("Introduzca sus datos de acceso", $administrador->ID_IDIOMA) . ":");
    $respuesta->clear("txUser", "innerHTML");
    $respuesta->prepend("txUser", "innerHTML", $auxiliar->traduce("Usuario", $administrador->ID_IDIOMA) . ":");
    $respuesta->clear("txPass", "innerHTML");
    $respuesta->prepend("txPass", "innerHTML", $auxiliar->traduce("Contraseña", $administrador->ID_IDIOMA) . ":");
    $respuesta->clear("txIdioma", "innerHTML");
    $respuesta->prepend("txIdioma", "innerHTML", $auxiliar->traduce("Idioma", $administrador->ID_IDIOMA) . ":");
    $respuesta->clear("aceptar", "innerHTML");
    $respuesta->prepend("aceptar", "innerHTML", " &nbsp;&nbsp;&nbsp;" . $auxiliar->traduce("Aceptar", $administrador->ID_IDIOMA) . " &nbsp;&nbsp;&nbsp;");
    $respuesta->clear("borrar", "innerHTML");
    $respuesta->prepend("borrar", "innerHTML", "&nbsp;&nbsp;&nbsp;" . $auxiliar->traduce("Borrar", $administrador->ID_IDIOMA) . "&nbsp;&nbsp;&nbsp;");
    $respuesta->clear("txMensajeAviso", "innerHTML");
    $respuesta->prepend("txMensajeAviso", "innerHTML", $auxiliar->traduce("El sistema permanecerá bloqueado", $administrador->ID_IDIOMA));
    $respuesta->clear("txMensajeBloqueo", "innerHTML");
    $respuesta->prepend("txMensajeBloqueo", "innerHTML", "¡ " . $auxiliar->traduce("Sistema Bloqueado", $administrador->ID_IDIOMA) . " !");
    $respuesta->clear("txDesde", "innerHTML");
    $respuesta->prepend("txDesde", "innerHTML", $auxiliar->traduce("Desde", $administrador->ID_IDIOMA));
    $respuesta->clear("txHasta", "innerHTML");
    $respuesta->prepend("txHasta", "innerHTML", $auxiliar->traduce("Hasta", $administrador->ID_IDIOMA));
    $respuesta->clear("txTextoResumen", "innerHTML");
    $respuesta->prepend("txTextoResumen", "innerHTML", ($administrador->ID_IDIOMA == "ESP" ? $rowBloqueoSistema->TEXTO_RESUMEN : $rowBloqueoSistema->TEXTO_RESUMEN_ENG));

}

/// FUNCIONES AJAX  /////////////////////////////////////////////////////////
function RecuperarUltimoIdiomaUtilizado($login)
{
    global $bd;
    global $administrador;
    global $NotificaErrorPorEmail, $auxiliar;

    $respuesta = new xajaxResponse();

    $NotificaErrorPorEmail    = "No";
    $sqlBloqueoSistema    = "SELECT * FROM AVISO_SISTEMA_BLOQUEADO";
    $resultBloqueoSistema = $bd->ExecSQL($sqlBloqueoSistema);
    $rowBloqueoSistema    = $bd->SigReg($resultBloqueoSistema);
    unset($NotificaErrorPorEmail);

    $NotificaErrorPorEmail    = "No";
    $sqlBuscaAdministrador    = "SELECT * FROM ADMINISTRADOR WHERE LOGIN = '" . $login . "' AND BAJA = 0";
    $resultBuscaAdministrador = $bd->ExecSQL($sqlBuscaAdministrador, "No");
    unset($NotificaErrorPorEmail);


    if ($bd->NumRegs($resultBuscaAdministrador) > 0):

        $rowBuscaAdministrador = $bd->SigReg($resultBuscaAdministrador);

        if ($rowBuscaAdministrador != false):

            if ($rowBuscaAdministrador->ULTIMO_IDIOMA == 'ESP'):

                $respuesta->assign("RbtnIdioma_ESP", "checked", 'checked');
                $respuesta->assign("RbtnIdioma_ENG", "checked", '');
                $respuesta->assign("idIdioma", "value", 'ESP');
                $administrador->setIdioma("ESP");
                cambiarLenguaje($respuesta, $auxiliar, $administrador, $rowBloqueoSistema);

            elseif ($rowBuscaAdministrador->ULTIMO_IDIOMA == 'ENG'):

                $respuesta->assign("RbtnIdioma_ESP", "checked", '');
                $respuesta->assign("RbtnIdioma_ENG", "checked", 'checked');
                $respuesta->assign("idIdioma", "value", 'ENG');
                $administrador->setIdioma("ENG");

                cambiarLenguaje($respuesta, $auxiliar, $administrador, $rowBloqueoSistema);


            endif;
        endif;
    endif;

    //tenemos que devolver la instanciación del objeto xajaxResponse
    return $respuesta;
}

function ActualizarIdiomaAdministrador($idioma)
{
    global $administrador;

    $administrador->setIdioma($idioma);
    $respuesta = new xajaxResponse();


    //tenemos que devolver la instanciación del objeto xajaxResponse
    return $respuesta;
}

//Asociamos la función creada anteriormente al objeto xajax
$xajax->registerFunction("RecuperarUltimoIdiomaUtilizado");
$xajax->registerFunction("ActualizarIdiomaAdministrador");
//El objeto xajax tiene que procesar cualquier petición

$xajax->processRequest();
//FIN AJAX////////////////////////////////////////////////////////////////////


$fechaHoraActual = date("Y-m-d H:i:s");

$sqlBloqueoSistema    = "SELECT * FROM AVISO_SISTEMA_BLOQUEADO";
$resultBloqueoSistema = $bd->ExecSQL($sqlBloqueoSistema);
$rowBloqueoSistema    = $bd->SigReg($resultBloqueoSistema);

$fechaHoraInicialBloqueoSistema = $rowBloqueoSistema->FECHA_INICIO_BLOQUEO . " " . $rowBloqueoSistema->HORA_INICIO_BLOQUEO;
$fechaHoraFinalBloqueoSistema   = $rowBloqueoSistema->FECHA_FIN_BLOQUEO . " " . $rowBloqueoSistema->HORA_FIN_BLOQUEO;

if (($fechaHoraActual > $fechaHoraInicialBloqueoSistema) && ($fechaHoraActual < $fechaHoraFinalBloqueoSistema)): //ESTAMOS EN EL RANGO DEFINIDO POR EL BLOQUEO Y ACTIVADO
    $sistemaBloqueado = true;
endif;

$sistemaPreaviso = false;
$fechaHoraInicioAviso = $rowBloqueoSistema->FECHA_INICIO_AVISO . " " . $rowBloqueoSistema->HORA_INICIO_AVISO;
if($fechaHoraActual >= $fechaHoraInicioAviso){
    $sistemaPreaviso = true;
}

if($fechaHoraFinalBloqueoSistema < $fechaHoraActual){
    $sistemaBloqueado = false;
    $sistemaPreaviso = false;
}

$mostrarEnPantallaLogin = $rowBloqueoSistema->MOSTRAR_LOGIN == '1';
$avisoActivo = $rowBloqueoSistema->ACTIVO == '1';

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <? require_once $pathClases . "lib/gral_js.php"; ?>
    <script language="JavaScript">
        function getCookie(cname) {
            var name = cname + "=";
            var ca = document.cookie.split(';');
            for(var i = 0; i < ca.length; i++) {
                var c = ca[i];
                while (c.charAt(0) == ' ') {
                    c = c.substring(1);
                }
                if (c.indexOf(name) == 0) {
                    return c.substring(name.length, c.length);
                }
            }
            return "";
        }
        function Borrar(form) {
            form.txLogin.value = '';
            form.txPassword.value = '';
            form.RbtnIdioma_ESP.checked = '';
            form.RbtnIdioma_ENG.checked = '';
        }

        function CargarIdioma(idioma) {
            jQuery("#RbtnIdioma_ESP").removeAttr("checked");
            jQuery("#RbtnIdioma_ENG").removeAttr("checked");
            if (idioma == 'ESP') {
                jQuery("#txUser").html('<?=$usuarioESP?>');
                jQuery("#txPass").html('<?=$contraseñaESP?>');
                jQuery("#txIdioma").html('<?=$idiomaESP?>');
                jQuery("#intro").html('<?=$introduceESP?>');
                jQuery("#aceptar").html('<?=$aceptarESP?>');
                jQuery("#borrar").html('<?=$borrarESP?>');
                jQuery("#RbtnIdioma_ESP").prop("checked", true);
                jQuery("#RbtnIdioma_ENG").prop("checked", false);
                jQuery("#txMensajeAviso").html('<?=$mensajeAvisoESP?>');
                jQuery("#txMensajeBloqueo").html('<?=$mensajeBloqueoESP?>');
                jQuery("#txDesde").html('<?=$desdeESP?>');
                jQuery("#txHasta").html('<?=$hastaESP?>');
                jQuery("#txTextoResumen").html('<?=$textoResumenESP?>');

            } else if (idioma == 'ENG') {
                jQuery("#txUser").html('<?=$usuarioENG?>');
                jQuery("#txPass").html('<?=$contraseñaENG?>');
                jQuery("#txIdioma").html('<?=$idiomaENG?>');
                jQuery("#intro").html('<?=$introduceENG?>');
                jQuery("#aceptar").html('<?=$aceptarENG?>');
                jQuery("#borrar").html('<?=$borrarENG?>');

                jQuery("#RbtnIdioma_ESP").prop("checked", false);
                jQuery("#RbtnIdioma_ENG").prop("checked", true);
                jQuery("#txMensajeAviso").html('<?=$mensajeAvisoENG?>');
                jQuery("#txMensajeBloqueo").html('<?=$mensajeBloqueoENG?>');
                jQuery("#txDesde").html('<?=$desdeENG?>');
                jQuery("#txHasta").html('<?=$hastaENG?>');
                jQuery("#txTextoResumen").html('<?=$textoResumenENG?>');
            }
            document.FormDatos.idIdioma.value = idioma;
        }

        /**
         * LLAMADA A FUNCION AJAX
         * @constructor
         */
        function LanzarRecuperarUltimoIdiomaUtilizado() {
            if ((jQuery('#login').val() != '')) {
                var login = jQuery('#login').val();
                xajax_RecuperarUltimoIdiomaUtilizado(login);
            }

        }

        jQuery(document).ready(function () {
            <?
                        if($_SESSION['LOGIN_PEDIR_ALMACEN'] == true):
                            $idUsuario = $_SESSION['ID_USUARIO_PEDIR_ALMACEN'];
                $RbtnIdioma = $_SESSION["IDIOMA_ADMINISTRADOR"];
                            unset($_SESSION['LOGIN_PEDIR_ALMACEN']);
                            unset($_SESSION['ID_USUARIO_PEDIR_ALMACEN']);
                unset($_SESSION['IDIOMA_ADMINISTRADOR']);
            ?>
            jQuery.fancybox(
                {
                    //'autoDimensions'	: false,
                    'type': 'ajax',
                    'width': 600,
                    'height': 132,
                    'href': 'almacen_tecnico.php?idUsuario=<?=$idUsuario?>&RbtnIdioma=<?=$RbtnIdioma?>',
                    'hideOnOverlayClick': false,
                    afterShow: function () {
                        document.Form.txCodigoAlmacen.focus();
                    }
                }
            );
            <?
            endif;
            ?>
            if(getCookie("IDIOMA_USER")){
            CargarIdioma(getCookie("IDIOMA_USER"));}
        });


    </script>
    <? $xajax->printJavascript($pathClases . 'lib/xajax25_php7/'); ?>
</head>
<body bgcolor="#FFFFFF" background="imagenes/fondo_pantalla.gif" leftmargin="0" topmargin="0" marginwidth="0"
      marginheight="0" style="height:100%; overflow:hidden" onLoad="document.FormDatos.txLogin.focus()">
<table width="100%" height="100%" border="0" align="center" cellpadding="0" cellspacing="0">
    <tr>
        <td align="center" valign="middle">
            <table width="100%" height="416" border="0" align="center" cellpadding="0" cellspacing="0">
                <tr>
                    <td>&nbsp;</td>
                    <td height="59" align="right" bgcolor="#FFFFFF" class="copyright">

                        <table width="152" height="1" border="0" cellpadding="0" cellspacing="0">
                            <tr>
                                <td width="50" height="1"></td>
                                <td width="15" height="1" align="center" valign="bottom"></td>
                                <td height="1"></td>
                                <td width="24" height="1" align="center" valign="middle">&nbsp;</td>
                                <td width="24" height="1">&nbsp;</td>
                            </tr>
                        </table>

                    </td>
                    <td>&nbsp;</td>
                </tr>
                <?if($avisoActivo == true && $mostrarEnPantallaLogin == true && ($sistemaBloqueado || $sistemaPreaviso)):?>
                    <tr bgcolor="#000000">
                        <td>&nbsp;</td>
                        <td height="23" align="center" bgcolor="#000000">
                            <!-- EL SISTEMA ESTÁ BLOQUEADO -->
                            <table width="936" height="32" border="0" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center" valign="middle">
                                        <br>
                                        <strong style="font-weight:bold;color:red">
                                            <?if($sistemaBloqueado == true):?>
                                                <span id="txMensajeBloqueo" <?=($sistemaBloqueado ? 'class="parpadeante"' : '')?>><? echo "¡ " . $auxiliar->traduce("Sistema Bloqueado", $administrador->ID_IDIOMA) . " !"; ?></span>
                                            <?endif;?>

                                            <!--    EL SISTEMA NO ESTÁ BLOQUEADO SINO QUE ESTÁ EN ESTADO DE AVISO DE QUE VA A SER BLOQUEADO-->
                                            <?if(!$sistemaBloqueado && $sistemaPreaviso):?>
                                                <span id="txMensajeAviso" style="font-weight:bold;color:red"><? echo $auxiliar->traduce('El sistema permanecerá bloqueado', $administrador->ID_IDIOMA)  ?></span>
                                            <?endif;?>
                                            <br/>
                                            <br/>
                                            <span id="txDesde"><?=$auxiliar->traduce('Desde', $administrador->ID_IDIOMA)?></span><?=': ' . date('d-m-Y H:i', strtotime( (string)$auxiliar->fecha_tz_to_tz($rowBloqueoSistema->FECHA_INICIO_BLOQUEO . ' ' . $rowBloqueoSistema->HORA_INICIO_BLOQUEO, '', 'CET'))) . " CET"?>
                                            <br/>
                                            <span id="txHasta"><?=$auxiliar->traduce('Hasta', $administrador->ID_IDIOMA)?></span><?=': ' . date('d-m-Y H:i', strtotime( (string)$auxiliar->fecha_tz_to_tz($rowBloqueoSistema->FECHA_FIN_BLOQUEO . ' ' . $rowBloqueoSistema->HORA_FIN_BLOQUEO, '', 'CET'))) . " CET"?>

                                            <br>
                                            <?if($rowBloqueoSistema->MOSTRAR_TEXTO_RESUMEN): ?>
                                                <br>
                                                <span id="txTextoResumen"><?=($administrador->ID_IDIOMA == "ESP" ? $rowBloqueoSistema->TEXTO_RESUMEN : $rowBloqueoSistema->TEXTO_RESUMEN_ENG)?></span>
                                                <br>
                                            <?endif;?>
                                        </strong>
                                        <br/>
                                    </td>
                                </tr>
                            </table>

                        </td>
                        <td>&nbsp;</td>
                    </tr>
                <?endif;?>
                <tr>
                    <td width="33%" height="113" align="center" bgcolor="#000000">&nbsp;</td>
                    <td align="center" bgcolor="#FFFFFF">
                        <table width="936" height="113" border="0" cellpadding="0" cellspacing="0"
                               background="<? echo $carpeta_imagenes ?>/<?= FONDO_CABECERA; ?>">
                            <tr>
                                <td height="15" align="right">&nbsp;</td>
                                <td width="24" rowspan="2"><img src="imagenes/transparente.gif" width="24" height="10">
                                </td>
                            </tr>
                            <tr>
                                <td align="right" valign="top"></td>
                            </tr>
                        </table>
                    </td>
                    <td width="33%" align="center" bgcolor="#000000">&nbsp;</td>
                </tr>
                <tr align="center" valign="middle">
                    <td colspan="3" bgcolor="#FFFFFF" class="textoazul">
                        <?
                        // VEO SI LA HERRAMIENTA ESTA BLOQUEADA A TODOS LOS CLIENTES
                        if (BLOQUEAR_TODOS_CLIENTES == '1'): ?>
                            <font size=2 color="RED">
                                <blink><?= $auxiliar->traduce("Acceso a la Herramienta Bloqueado Temporalmente", $administrador->ID_IDIOMA) ?></blink>
                            </font>
                            <br>
                            <? echo $rowGral->MOTIVO_BLOQUEO_PROVS_CAST ?>
                        <? endif; ?>
                        <table width="100%" height="179" border="0" cellpadding="0" cellspacing="0">
                            <tr>
                                <td height="156" align="center" valign="middle">
                                    <table width="100%" height="156" border="0" cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td height="10" bgcolor="#FFFFFF"><img src="imagenes/transparente.gif"
                                                                                   width="10" height="10"></td>
                                        </tr>
                                        <tr>
                                            <td height="68" bgcolor="#B3C7DA">&nbsp;</td>
                                        </tr>
                                        <tr>
                                            <td bgcolor="#A7B9CB">&nbsp;</td>
                                        </tr>
                                    </table>
                                </td>
                                <td width="289" rowspan="2" align="center" valign="middle">
                                    <table width="289" height="179" border="0" cellpadding="0" cellspacing="0"
                                           background="imagenes/fondo_login.gif">
                                        <tr>
                                            <td width="36" rowspan="3">&nbsp;</td>
                                            <td width="199" height="10">&nbsp;</td>
                                            <td width="35" rowspan="3">&nbsp;</td>
                                        </tr>
                                        <tr>
                                            <td height="99" align="center" valign="middle">
                                                <table width="218" height="99" border="0" cellpadding="0" cellspacing="0">
                                                    <form name="FormDatos" method="post" action="comprobacion.php">
                                                        <input type="hidden" name="idIdioma" id="idIdioma"
                                                               value="<? echo $idIdioma ?>">
                                                        <tr>
                                                            <td height="15" id="intro" colspan="2" class="textoazul">
                                                                <?= $auxiliar->traduce("Introduzca sus datos de acceso", $administrador->ID_IDIOMA) . ":" ?>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td height="5" colspan="2">
                                                                <img src="imagenes/transparente.gif"
                                                                     width="10" height="5">
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td align="left" width="102" class="textoazul">
                                                                <strong id="txUser">
                                                                    <?= $auxiliar->traduce("Usuario", $administrador->ID_IDIOMA) . ":" ?>
                                                                </strong>
                                                            </td>
                                                            <td class="textoazul">
                                                                <input type="text" name="txLogin"
                                                                       class="copyright" id="login"
                                                                       size="23" maxlength="23"
                                                                       style="width: 140px"
                                                                    <?= ($txLogin != "") ? "" : "onChange=\"return LanzarRecuperarUltimoIdiomaUtilizado();\""; ?>>
                                                            </td>
                                                            <input value="<?= $txLogin ?>" type="hidden" name="idLogin" id="idLogin"/>
                                                        </tr>
                                                        <tr>
                                                            <td height="5" colspan="2">
                                                                <img src="imagenes/transparente.gif"
                                                                     width="10" height="5">
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td align="left" class="textoazul">
                                                                <strong id="txPass">
                                                                    <?= $auxiliar->traduce("Contraseña", $administrador->ID_IDIOMA) . ":" ?>
                                                                </strong>
                                                            </td>
                                                            <td class="textoazul">
                                                                <input type="password" name="txPassword"
                                                                       class="copyright" id="password"
                                                                       size="23" maxlength="23"
                                                                       style="width: 140px">
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td height="5" colspan="2">
                                                                <img src="imagenes/transparente.gif"
                                                                     width="10" height="5">
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td align="left" class="textoazul">
                                                                <strong id="txIdioma">
                                                                    <?= $auxiliar->traduce("Idioma", $administrador->ID_IDIOMA) . ":" ?>
                                                                </strong>
                                                            </td>
                                                            <td class="textoazul">
                                                                <label>
                                                                    <input type="radio" name="RbtnIdioma" value="ESP" id="RbtnIdioma_ESP"
                                                                           <? if ($idIdioma == 'ESP'): ?>checked="checked"<? else: ?><? endif; ?>
                                                                           onclick="return CargarIdioma(this.value);"/>
                                                                    Español
                                                                </label>
                                                                <label>
                                                                    <input type="radio" name="RbtnIdioma" value="ENG" id="RbtnIdioma_ENG"
                                                                           <? if ($idIdioma == 'ENG'): ?>checked="checked"<? else: ?><? endif; ?>
                                                                           onclick="return CargarIdioma(this.value);"/>
                                                                    English
                                                                </label>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td height="5" colspan="2">
                                                                <img src="imagenes/transparente.gif"
                                                                     width="10" height="5">
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td align="center" colspan="2" class="textoazul">
                                                                <a href="#" id="aceptar" class="senaladoazul almacenTecnico"
                                                                   onClick="document.FormDatos.submit();return false">
                                                                    &nbsp;&nbsp;<?= $auxiliar->traduce("Aceptar", $administrador->ID_IDIOMA) ?>&nbsp;&nbsp;&nbsp;
                                                                </a>&nbsp;
                                                                <a href="#" id="borrar" class="senaladoazul"
                                                                   onClick="Borrar(document.FormDatos);return false">
                                                                    &nbsp;&nbsp;&nbsp;<?= $auxiliar->traduce("Borrar", $administrador->ID_IDIOMA) ?>&nbsp;&nbsp;&nbsp;
                                                                </a>
                                                            </td>
                                                        </tr>
                                                        <input type="submit" style="position:absolute; top:-999999px"/>
                                                    </form>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                                <td height="156" align="center" valign="middle">
                                    <table width="100%" height="156" border="0" cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td height="10" bgcolor="#FFFFFF">
                                                <img src="imagenes/transparente.gif"
                                                     width="10" height="10">
                                            </td>
                                        </tr>
                                        <tr>
                                            <td height="95" bgcolor="#B3C7DA">&nbsp;</td>
                                        </tr>
                                        <tr>
                                            <td bgcolor="#A7B9CB">&nbsp;</td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td height="23" align="center" valign="middle">&nbsp;</td>
                                <td height="23" align="center" valign="middle">&nbsp;</td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr align="center" valign="middle">
                    <td height="23" colspan="3" align="center" valign="middle" class="copyright">
                        <?
                        include "copyright_texto.php";

                        //PINTAMOS VERSION
                        $RutaVersion = $pathRaiz . "version.txt";
                        $numVersion  = "-";
                        if (file_exists($RutaVersion) == 1): // HAY FICHERO
                            $numVersion = trim( (string)file_get_contents($RutaVersion));
                        endif;
                        echo ". (Build $numVersion)";
                        ?>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>