<?

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

session_start();
include $pathRaiz . "seguridad_admin.php";

$tituloPag = $auxiliar->traduce("Ayuda", $administrador->ID_IDIOMA);
$tituloNav = $auxiliar->traduce("Manuales de Ayuda", $administrador->ID_IDIOMA);

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <? require_once $pathClases . "lib/gral_js.php"; ?>
    <script type="text/javascript">
        function desplegar(hijos) {
            var table = document.getElementById(hijos);

            switch(table.style.display) {
                case "none":
                    table.style.display = "";
                    break;
                default:
                    table.style.display = "none";
                    break;
            }
        }
    </script>
</head>
<body bgcolor="#FFFFFF" background="<? echo $pathRaiz ?>imagenes/fondo_pantalla.gif" bottommargin="0" leftmargin="0"
      topmargin="0" marginwidth="0" marginheight="0">
<FORM NAME="FormSelect" ACTION="index.php" METHOD="POST">
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
                                                                &nbsp;</td>
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
                                                <td bgcolor="#B3C7DA" class="lineabajoarriba">&nbsp;</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr bgcolor="#D9E3EC">
                                    <td height="13" align="center" valign="top" bgcolor="#AACFF9"
                                        class="lineabajo textoazul">
                                        <br/><strong><?= $auxiliar->traduce("A continuación podrá visualizar el listado de manuales de ayuda de la herramienta. Por favor, seleccione el fichero deseado para visualizarlo", $administrador->ID_IDIOMA) ?>
                                            .</strong><br/><br/>

                                    </td>
                                </tr>
                                <tr>
                                    <td align="left" valign="top" bgcolor="#B3C7DA" class="lineabajo">
                                        <table width="100%" border="0" cellpadding="0" cellspacing="0"
                                               class="lineabajo">

                                            <tr class="lineabajo">
                                                <td colspan="2" align="center" bgcolor="#D9E3EC">&nbsp;</td>
                                            </tr>

                                            <tr class="lineabajo">
                                                <td colspan="2" align="center" bgcolor="#D9E3EC">
                                                    <table width="75%" cellpadding="0" cellspacing="2"
                                                           class="linealrededor">
                                                        <tr>
                                                            <td height="19" bgcolor="#2E8AF0"
                                                                class="blanco"><?= $auxiliar->traduce("Manuales", $administrador->ID_IDIOMA) ?></td>
                                                            <td height="19" bgcolor="#2E8AF0" width="200px"
                                                                class="blanco"><?= $auxiliar->traduce("Versión", $administrador->ID_IDIOMA) ?></td>
                                                        </tr>
                                                        <tr align="center" valign="middle" bgcolor="#B3C7DA">
                                                            <td height="18" align="left" class="enlaceceldas">&nbsp;<a
                                                                    href="<?= $pathRaiz ?>descargarDocumento.php?key=manual&ruta=<? echo($administrador->ID_IDIOMA == 'ENG' ? "01_01_DIRECT LOGISTICS HANDBOOK_ INBOUND_v4.00 En.pdf" : "01_01_MANUAL_LOGISTICA DIRECTA_ENTRADAS_v4 00 Es.pdf") ?>"
                                                                    class="enlaceceldas"
                                                                    target="_blank"><?= strtoupper( (string)$auxiliar->traduce("Logística Directa - Entradas", $administrador->ID_IDIOMA)) ?></a>&nbsp;
                                                            </td>
                                                            <td height="18" align="center" class="enlaceceldas">&nbsp;4.00&nbsp;</td>
                                                        </tr>
                                                        <tr align="center" valign="middle" bgcolor="#AACFF9">
                                                            <td height="18" align="left" class="enlaceceldas">&nbsp;<a
                                                                    href="<?= $pathRaiz ?>descargarDocumento.php?key=manual&ruta=<? echo($administrador->ID_IDIOMA == 'ENG' ? "01_02_DIRECT LOGISTICS HANDBOOK_OUTBOUND_v5.00 En.pdf" : "01_02_MANUAL_LOGISTICA DIRECTA_SALIDAS_v6 00 Es.pdf") ?>"
                                                                    class="enlaceceldas"
                                                                    target="_blank"><?= strtoupper( (string)$auxiliar->traduce("Logística Directa - Salidas", $administrador->ID_IDIOMA)) ?></a>&nbsp;
                                                            </td>
                                                            <td height="18" align="center" class="enlaceceldas">
                                                                &nbsp;<? echo($administrador->ID_IDIOMA == 'ENG' ? "5.00" : "6.00"); ?>
                                                                &nbsp;</td>
                                                        </tr>
                                                        <? if ($administrador->ID_IDIOMA == 'ESP'): ?>
                                                            <tr align="center" valign="middle" bgcolor="#AACFF9">
                                                                <td height="18" align="left" class="enlaceceldas">&nbsp;<a
                                                                        href="<?= $pathRaiz ?>descargarDocumento.php?key=manual&ruta=<? echo($administrador->ID_IDIOMA == 'ENG' ? "01_04_HANDBOOK_v01_En.pdf" : "01_04_MANUAL_TRANSPORTE_v01_Es.pdf") ?>"
                                                                        class="enlaceceldas"
                                                                        target="_blank"><?= strtoupper( (string)$auxiliar->traduce("Transporte", $administrador->ID_IDIOMA)) ?></a>&nbsp;
                                                                </td>
                                                                <td height="18" align="center" class="enlaceceldas">
                                                                    &nbsp;1.00&nbsp;</td>
                                                            </tr>
                                                        <? endif; ?>
                                                        <tr align="center" valign="middle" bgcolor="#B3C7DA">
                                                            <td height="18" align="left" class="enlaceceldas">&nbsp;<a
                                                                    href="<?= $pathRaiz ?>descargarDocumento.php?key=manual&ruta=<? echo($administrador->ID_IDIOMA == 'ENG' ? "01_03_DIRECT LOGISTICS HANDBOOK_WAREHOUSE MANAGEMENT_v4 00 En.pdf" : "01_03_MANUAL_LOGISTICA DIRECTA_GESTION ALMACEN_v4 00 Es.pdf") ?>"
                                                                    class="enlaceceldas"
                                                                    target="_blank"><?= strtoupper( (string)$auxiliar->traduce("Logística Directa - Gestión Almacén", $administrador->ID_IDIOMA)) ?></a>&nbsp;
                                                            </td>
                                                            <td height="18" align="center" class="enlaceceldas">&nbsp;4.00&nbsp;</td>
                                                        </tr>
                                                        <tr align="center" valign="middle" bgcolor="#AACFF9">
                                                            <td height="18" align="left" class="enlaceceldas">&nbsp;<a
                                                                    href="<?= $pathRaiz ?>descargarDocumento.php?key=manual&ruta=<? echo($administrador->ID_IDIOMA == 'ENG' ? "02_MANUAL_CONSUMPTIONS_RETURNS_v5 00 En.pdf" : "02_MANUAL_CONSUMOS_RETORNOS_v5. 00 Es.pdf") ?>"
                                                                    class="enlaceceldas"
                                                                    target="_blank"><?= strtoupper( (string)$auxiliar->traduce("Gestion de Ordenes de Trabajo", $administrador->ID_IDIOMA)) ?></a>&nbsp;
                                                            </td>
                                                            <td height="18" align="center" class="enlaceceldas">&nbsp;5.00&nbsp;</td>
                                                        </tr>
                                                        <tr align="center" valign="middle" bgcolor="#B3C7DA">
                                                            <td height="18" align="left" class="enlaceceldas">&nbsp;<a
                                                                    href="<?= $pathRaiz ?>descargarDocumento.php?key=manual&ruta=<? echo($administrador->ID_IDIOMA == 'ENG' ? "03_REVERSE_LOGISTICS_MANUAL_v2 00_En.pdf" : "03_MANUAL_LOGISTICA INVERSA_v2.00 Es.pdf") ?>"
                                                                    class="enlaceceldas"
                                                                    target="_blank"><?= strtoupper( (string)$auxiliar->traduce("Logística Inversa", $administrador->ID_IDIOMA)) ?></a>&nbsp;
                                                            </td>
                                                            <td height="18" align="center" class="enlaceceldas">&nbsp;2.00&nbsp;</td>
                                                        <tr align="center" valign="middle" bgcolor="#AACFF9">
                                                            <td height="18" align="left" class="enlaceceldas">&nbsp;<a
                                                                    href="<?= $pathRaiz ?>descargarDocumento.php?key=manual&ruta=<? echo($administrador->ID_IDIOMA == 'ENG' ? "04_DIRECT LOGISTICS_HANDBOOK_INVENTORIES_v03_En.pdf" : "04_MANUAL_LOGISTICA DIRECTA_REALIZACION INVENTARIOS_v03_Es.pdf") ?>"
                                                                    class="enlaceceldas"
                                                                    target="_blank"><?= strtoupper( (string)$auxiliar->traduce("Inventarios", $administrador->ID_IDIOMA)) ?></a>&nbsp;
                                                            </td>
                                                            <td height="18" align="center" class="enlaceceldas">&nbsp;3.00&nbsp;</td>
                                                        </tr>
                                                        <tr align="center" valign="middle" bgcolor="#B3C7DA">
                                                            <td height="18" align="left" class="enlaceceldas">&nbsp;<a
                                                                    href="<?= $pathRaiz ?>descargarDocumento.php?key=manual&ruta=<? echo($administrador->ID_IDIOMA == 'ENG' ? "07_NEEDS MANAGEMENT HANDBOOK EN V2.pdf" : "07_MANUAL_GESTION DE NECESIDADES V2.pdf") ?>"
                                                                    class="enlaceceldas"
                                                                    target="_blank"><?= strtoupper( (string)$auxiliar->traduce("Necesidades", $administrador->ID_IDIOMA)) ?></a>&nbsp;
                                                            </td>
                                                            <td height="18" align="center" class="enlaceceldas">&nbsp;2.00&nbsp;</td>
                                                        </tr>
                                                        <tr align="center" valign="middle" bgcolor="#AACFF9">
                                                            <td height="18" align="left" class="enlaceceldas">&nbsp;<a
                                                                    href="<?= $pathRaiz ?>descargarDocumento.php?key=manual&ruta=<? echo($administrador->ID_IDIOMA == 'ENG' ? "05_MASTERS_HANDBOOK_v1.00 En.pdf" : "05_MANUAL_MAESTROS_v1 00.pdf") ?>"
                                                                    class="enlaceceldas"
                                                                    target="_blank"><?= strtoupper( (string)$auxiliar->traduce("Maestros", $administrador->ID_IDIOMA)) ?></a>&nbsp;
                                                            </td>
                                                            <td height="18" align="center" class="enlaceceldas">&nbsp;1.00&nbsp;</td>
                                                        </tr>
                                                        <tr align="center" valign="middle" bgcolor="#AACFF9">
                                                            <td height="18" align="left" class="enlaceceldas">&nbsp;<a
                                                                        href="<?= $pathRaiz ?>descargarDocumento.php?key=manual&ruta=<? echo($administrador->ID_IDIOMA == 'ENG' ? "Manual config and edit plants and warehouses r01 en.pdf" : "Manual config y ediciones centros y alm r01 es.pdf") ?>"
                                                                        class="enlaceceldas"
                                                                        target="_blank"><?= strtoupper( (string)$auxiliar->traduce("Manual Config. y ediciones centros y alm.", $administrador->ID_IDIOMA)) ?></a>&nbsp;
                                                            </td>
                                                            <td height="18" align="center" class="enlaceceldas">&nbsp;1.00&nbsp;</td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                            <tr class="lineabajo">
                                                <td colspan="2" align="center" bgcolor="#D9E3EC" height="25px">
                                                </td>
                                            </tr>
                                            <tr class="lineabajo">
                                                <td colspan="2" align="center" bgcolor="#D9E3EC">
                                                    <table width="75%" cellpadding="0" cellspacing="2"
                                                           class="linealrededor">
                                                        <tr>
                                                            <td height="19" bgcolor="#2E8AF0"
                                                                class="blanco"><?= $auxiliar->traduce("Construccion Servicio", $administrador->ID_IDIOMA) ?></td>
                                                            <td height="19" bgcolor="#2E8AF0" width="200px"
                                                                class="blanco"><?= $auxiliar->traduce("Versión", $administrador->ID_IDIOMA) ?></td>
                                                        </tr>
                                                        <tr align="center" valign="middle" bgcolor="#B3C7DA">
                                                            <td height="18" align="left" class="enlaceceldas">&nbsp;
                                                                <a href="<?= $pathRaiz ?>descargarDocumento.php?key=manual&ruta=<? echo($administrador->ID_IDIOMA == 'ENG' ? "en_MANUAL supplier SCS.rev1.pdf" : "MANUAL PROVEEDOR SCS.rev1.pdf") ?>"
                                                                   class="enlaceceldas"
                                                                   target="_blank">
                                                                    <?= strtoupper( (string)$administrador->ID_IDIOMA == 'ENG' ? "en_MANUAL supplier SCS.rev1.pdf" : "MANUAL PROVEEDOR SCS.rev1.pdf") ?>
                                                                </a>&nbsp;
                                                            </td>
                                                            <td height="18" align="center" class="enlaceceldas">&nbsp;1.00&nbsp;</td>
                                                        </tr>
                                                        <tr align="center" valign="middle" bgcolor="#AACFF9">
                                                            <td height="18" align="left" class="enlaceceldas">&nbsp;
                                                                <a href="<?= $pathRaiz ?>descargarDocumento.php?key=manual&ruta=<? echo ($administrador->ID_IDIOMA == 'ENG' ? "SCS-CUSTOMS BROKER.pdf" : "ROL - Agente de Aduanas.pdf") ?>"
                                                                   class="enlaceceldas"
                                                                   target="_blank">
                                                                    <?= strtoupper( (string)$administrador->ID_IDIOMA == 'ENG' ? "SCS-CUSTOMS BROKER.pdf" : "ROL - Agente de Aduanas") ?>
                                                                </a>&nbsp;
                                                            </td>
                                                            <td height="18" align="center" class="enlaceceldas">&nbsp;1.00&nbsp;</td>
                                                        </tr>
                                                        <tr align="center" valign="middle" bgcolor="#B3C7DA">
                                                            <td height="18" align="left" class="enlaceceldas">&nbsp;
                                                                <a href="<?= $pathRaiz ?>descargarDocumento.php?key=manual&ruta=<? echo ($administrador->ID_IDIOMA == 'ENG' ? "en_MANUAL forwarder SCS.rev1.pdf" : "ROL-FORWARDER.pdf") ?>"
                                                                   class="enlaceceldas"
                                                                   target="_blank">
                                                                    <?= strtoupper( (string)$administrador->ID_IDIOMA == 'ENG' ? "en_MANUAL forwarder SCS.rev1.pdf" : "ROL-FORWARDER") ?>
                                                                </a>&nbsp;
                                                            </td>
                                                            <td height="18" align="center" class="enlaceceldas">&nbsp;1.00&nbsp;</td>
                                                        </tr>
                                                        <tr align="center" valign="middle" bgcolor="#AACFF9">
                                                            <td height="18" align="left" class="enlaceceldas">&nbsp;
                                                                <a href="<?= $pathRaiz ?>descargarDocumento.php?key=manual&ruta=<? echo ($administrador->ID_IDIOMA == 'ENG' ? "Delivery At Site Process.pdf" : "SGA - ENTREGAS EN OBRA.pdf") ?>"
                                                                   class="enlaceceldas"
                                                                   target="_blank">
                                                                    <?= strtoupper( (string)$administrador->ID_IDIOMA == 'ENG' ? "Delivery At Site Process.pdf" : "SGA - ENTREGAS EN OBRA") ?>
                                                                </a>&nbsp;
                                                            </td>
                                                            <td height="18" align="center" class="enlaceceldas">&nbsp;1.00&nbsp;</td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                            <tr class="lineabajo">
                                                <td colspan="2" align="center" bgcolor="#D9E3EC" height="25px">
                                                </td>
                                            </tr>
                                            <tr class="lineabajo">
                                                <td colspan="2" align="center" bgcolor="#D9E3EC">
                                                    <table width="75%" cellpadding="0" cellspacing="2"
                                                           class="linealrededor">
                                                        <tr>
                                                            <td height="19" bgcolor="#2E8AF0"
                                                                class="blanco"><?= ucfirst(strtolower((string)$auxiliar->traduce("CODIFICACIONES", $administrador->ID_IDIOMA))) ?></td>
                                                            <td height="19" bgcolor="#2E8AF0" width="200px"
                                                                class="blanco"><?= $auxiliar->traduce("Versión", $administrador->ID_IDIOMA) ?></td>
                                                        </tr>
                                                        <tr align="center" valign="middle" bgcolor="#B3C7DA">
                                                            <td height="18" align="left" class="enlaceceldas">&nbsp;
                                                                <a href="<?= $pathRaiz ?>descargarDocumento.php?key=manual&ruta=<? echo($administrador->ID_IDIOMA == 'ENG' ? "10_Spare parts code request manual r01 en.pdf" : "Manual solicitud codigo repuestos r01 es.pdf") ?>"
                                                                   class="enlaceceldas"
                                                                   target="_blank">
                                                                    <?= strtoupper( (string)$auxiliar->traduce("Solicitud código de repuestos", $administrador->ID_IDIOMA)) ?>
                                                                </a>&nbsp;
                                                            </td>
                                                            <td height="18" align="center" class="enlaceceldas">&nbsp;1.00&nbsp;</td>
                                                        </tr>
                                                        <tr align="center" valign="middle" bgcolor="#AACFF9">
                                                            <td height="18" align="left" class="enlaceceldas">&nbsp;
                                                                <a href="<?= $pathRaiz ?>descargarDocumento.php?key=manual&ruta=<? echo($administrador->ID_IDIOMA == 'ENG' ? "Center and warehouse expansions r01 en - Manual.pdf" : "Ampliaciones en centros y almacenes r01 es - Manual.pdf") ?>"
                                                                   class="enlaceceldas"
                                                                   target="_blank">
                                                                    <?= strtoupper( (string)$auxiliar->traduce("Ampliación de códigos en centros y almacenes", $administrador->ID_IDIOMA)) ?>
                                                                </a>&nbsp;
                                                            </td>
                                                            <td height="18" align="center" class="enlaceceldas">&nbsp;1.00&nbsp;</td>
                                                        </tr>
                                                        <tr align="center" valign="middle" bgcolor="#B3C7DA">
                                                            <td height="18" align="left" class="enlaceceldas">&nbsp;
                                                                <a href="<?= $pathRaiz ?>descargarDocumento.php?key=manual&ruta=<? echo($administrador->ID_IDIOMA == 'ENG' ? "Code search  r01 en - Manual.pdf" : "Busqueda de codigos r01 es - Manual.pdf") ?>"
                                                                   class="enlaceceldas"
                                                                   target="_blank">
                                                                    <?= strtoupper( (string)$auxiliar->traduce("Busqueda de códigos", $administrador->ID_IDIOMA)) ?>
                                                                </a>&nbsp;
                                                            </td>
                                                            <td height="18" align="center" class="enlaceceldas">&nbsp;1.00&nbsp;</td>
                                                        </tr>
                                                        <tr align="center" valign="middle" bgcolor="#AACFF9">
                                                            <td height="18" align="left" class="enlaceceldas">&nbsp;
                                                                <a href="<?= $pathRaiz ?>descargarDocumento.php?key=manual&ruta=<? echo($administrador->ID_IDIOMA == 'ENG' ? "Views configuration on SCS screens  r01 en - Manual.pdf" : "Configuracion de vistas en pantallas de SCS r01 es - Manual.pdf") ?>"
                                                                   class="enlaceceldas"
                                                                   target="_blank">
                                                                    <?= strtoupper( (string)$auxiliar->traduce("Configuración de vistas en pantallas de SCS", $administrador->ID_IDIOMA)) ?>
                                                                </a>&nbsp;
                                                            </td>
                                                            <td height="18" align="center" class="enlaceceldas">&nbsp;1.00&nbsp;</td>
                                                        </tr>
                                                        <tr align="center" valign="middle" bgcolor="#B3C7DA">
                                                            <td height="18" align="left" class="enlaceceldas">&nbsp;
                                                                <a href="<?= $pathRaiz ?>descargarDocumento.php?key=manual&ruta=<? echo($administrador->ID_IDIOMA == 'ENG' ? "20_Evaluation of spare parts code request  r01 en.pdf" : "Evaluacion solicitud codigo repuestos r01 es.pdf") ?>"
                                                                   class="enlaceceldas"
                                                                   target="_blank">
                                                                    <?= strtoupper( (string)$auxiliar->traduce("Evaluación solicitudes código repuestos", $administrador->ID_IDIOMA)) ?>
                                                                </a>&nbsp;
                                                            </td>
                                                            <td height="18" align="center" class="enlaceceldas">&nbsp;1.00&nbsp;</td>
                                                        </tr>
                                                        <tr align="center" valign="middle" bgcolor="#AACFF9">
                                                            <td height="18" align="left" class="enlaceceldas">&nbsp;
                                                                <a href="<?= $pathRaiz ?>descargarDocumento.php?key=manual&ruta=<? echo($administrador->ID_IDIOMA == 'ENG' ? "Spare parts request history query  r01 en - Manual.pdf" : "Consulta de historial de solicitud de repuestos r01 es - Manual.pdf") ?>"
                                                                   class="enlaceceldas"
                                                                   target="_blank">
                                                                    <?= strtoupper( (string)$auxiliar->traduce("Consulta de historial de solicitud de repuestos", $administrador->ID_IDIOMA)) ?>
                                                                </a>&nbsp;
                                                            </td>
                                                            <td height="18" align="center" class="enlaceceldas">&nbsp;1.00&nbsp;</td>
                                                        </tr>
                                                        <tr align="center" valign="middle" bgcolor="#B3C7DA">
                                                            <td height="18" align="left" class="enlaceceldas">&nbsp;
                                                                <a href="<?= $pathRaiz ?>descargarDocumento.php?key=manual&ruta=<? echo($administrador->ID_IDIOMA == 'ENG' ? "Management of rejected replacement requests r01 en - Manual.pdf" : "Gestion de solicitudes de repuestos rechazadas r01 es - Manual.pdf") ?>"
                                                                   class="enlaceceldas"
                                                                   target="_blank">
                                                                    <?= strtoupper( (string)$auxiliar->traduce("Solicitudes de repuesto rechazadas", $administrador->ID_IDIOMA)) ?>
                                                                </a>&nbsp;
                                                            </td>
                                                            <td height="18" align="center" class="enlaceceldas">&nbsp;1.00&nbsp;</td>
                                                        </tr>
                                                        <tr align="center" valign="middle" bgcolor="#AACFF9">
                                                            <td height="18" align="left" class="enlaceceldas">&nbsp;
                                                                <a href="<?= $pathRaiz ?>descargarDocumento.php?key=manual&ruta=<? echo($administrador->ID_IDIOMA == 'ENG' ? "Request for interchangeable or substitute code r01 en - Manual.pdf" : "Solicitud de codigo intercambiable o sustituto r01 es - Manual.pdf") ?>"
                                                                   class="enlaceceldas"
                                                                   target="_blank">
                                                                    <?= strtoupper( (string)$auxiliar->traduce("Alta código intercambiable o sustituto", $administrador->ID_IDIOMA)) ?>
                                                                </a>&nbsp;
                                                            </td>
                                                            <td height="18" align="center" class="enlaceceldas">&nbsp;1.00&nbsp;</td>
                                                        </tr>
                                                        <tr align="center" valign="middle" bgcolor="#B3C7DA">
                                                            <td height="18" align="left" class="enlaceceldas">&nbsp;
                                                                <a href="<?= $pathRaiz ?>descargarDocumento.php?key=manual&ruta=<? echo($administrador->ID_IDIOMA == 'ENG' ? "Relation establishment between codes r01 en - Manual.pdf" : "Establecimiento de relaciones entre codigos r01 es - Manual.pdf") ?>"
                                                                   class="enlaceceldas"
                                                                   target="_blank">
                                                                    <?= strtoupper( (string)$auxiliar->traduce("Solicitud de relación entre códigos existentes", $administrador->ID_IDIOMA)) ?>
                                                                </a>&nbsp;
                                                            </td>
                                                            <td height="18" align="center" class="enlaceceldas">&nbsp;1.00&nbsp;</td>
                                                        </tr>
                                                        <tr align="center" valign="middle" bgcolor="#AACFF9">
                                                            <td height="18" align="left" class="enlaceceldas">&nbsp;
                                                                <a href="<?= $pathRaiz ?>descargarDocumento.php?key=manual&ruta=<? echo($administrador->ID_IDIOMA == 'ENG' ? "60_Evaluation of interchangeable substitutes Reverse relations en - Manual.pdf" : "Evaluacion intercambiables sustitutos Reversion relaciones r01 es Manual.pdf") ?>"
                                                                   class="enlaceceldas"
                                                                   target="_blank">
                                                                    <?= strtoupper( (string)$auxiliar->traduce("Evaluación intercambiable sustituto", $administrador->ID_IDIOMA)) ?>
                                                                </a>&nbsp;
                                                            </td>
                                                            <td height="18" align="center" class="enlaceceldas">&nbsp;1.00&nbsp;</td>
                                                        </tr>
                                                        <tr align="center" valign="middle" bgcolor="#B3C7DA">
                                                            <td height="18" align="left" class="enlaceceldas">&nbsp;
                                                                <a href="<?= $pathRaiz ?>descargarDocumento.php?key=manual&ruta=<? echo($administrador->ID_IDIOMA == 'ENG' ? "Material detail request r01 en - Manual.pdf" : "Consulta de detalles del material r01 es - Manual.pdf") ?>"
                                                                   class="enlaceceldas"
                                                                   target="_blank">
                                                                    <?= strtoupper( (string)$auxiliar->traduce("Consulta detalles del material", $administrador->ID_IDIOMA)) ?>
                                                                </a>&nbsp;
                                                            </td>
                                                            <td height="18" align="center" class="enlaceceldas">&nbsp;1.00&nbsp;</td>
                                                        </tr>
                                                        <tr align="center" valign="middle" bgcolor="#AACFF9">
                                                            <td height="18" align="left" class="enlaceceldas">&nbsp;
                                                                <a href="<?= $pathRaiz ?>descargarDocumento.php?key=manual&ruta=<? echo($administrador->ID_IDIOMA == 'ENG' ? "Cross-references 01 en - Manual.pdf" : "Referencias cruzadas 01 es - Manual.pdf") ?>"
                                                                   class="enlaceceldas"
                                                                   target="_blank">
                                                                    <?= strtoupper( (string)$auxiliar->traduce("Referencias cruzadas", $administrador->ID_IDIOMA)) ?>
                                                                </a>&nbsp;
                                                            </td>
                                                            <td height="18" align="center" class="enlaceceldas">&nbsp;1.00&nbsp;</td>
                                                        </tr>
                                                        <tr align="center" valign="middle" bgcolor="#B3C7DA">
                                                            <td height="18" align="left" class="enlaceceldas">&nbsp;
                                                                <a href="<?= $pathRaiz ?>descargarDocumento.php?key=manual&ruta=<? echo($administrador->ID_IDIOMA == 'ENG' ? "Spare part code editing and enrichment r01 en - Manual.pdf" : "Edicion y enriquecimiento de codigos de repuestos r01 es - Manual.pdf") ?>"
                                                                   class="enlaceceldas"
                                                                   target="_blank">
                                                                    <?= strtoupper( (string)$auxiliar->traduce("Edición de códigos", $administrador->ID_IDIOMA) . ". " . $auxiliar->traduce("Enriquecimientos", $administrador->ID_IDIOMA)) ?>
                                                                </a>&nbsp;
                                                            </td>
                                                            <td height="18" align="center" class="enlaceceldas">&nbsp;1.00&nbsp;</td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>

                                            <tr class="lineabajo">
                                                <td colspan="2" align="center" bgcolor="#D9E3EC">&nbsp;</td>
                                            </tr>

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