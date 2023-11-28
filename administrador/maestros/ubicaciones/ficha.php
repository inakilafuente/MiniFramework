<?
// PATHS DE LA WEB
$pathRaiz   = "../../";
$pathClases = "../../../";

// INCLUDES DE LIBRERIAS PROPIAS
require_once $pathClases . "lib/basedatos.php";
require_once $pathClases . "lib/administrador.php";
require_once $pathClases . "lib/html.php";
require_once $pathClases . "lib/navegar.php";
require_once $pathClases . "lib/auxiliar.php";

session_start();
include $pathRaiz . "seguridad_admin.php";

//VARIABLE SEGUN DESDE DONDE SE ACCEDA
if ($pantallaSolar == 1):
    $tituloPag = $auxiliar->traduce("Unidad Organizativa", $administrador->ID_IDIOMA);
    $tituloNav = $auxiliar->traduce("Construccion Solar", $administrador->ID_IDIOMA) . " >> " . $tituloPag;

    $ZonaTablaPadre    = "ConstruccionSolar";
    $ZonaSubTablaPadre = "ConstruccionSolarMenuEstructura";
    $ZonaTabla         = "ConstruccionSolarUnidadOrganizativa";

    // COMPRUEBA SI TIENE PERMISOS
    if ($administrador->Hayar_Permiso_Perfil('ADM_CONSTRUCCION_SOLAR_UNIDAD_ORGANIZATIVA') < 1):
        $html->PagError("SinPermisos");
    endif;

elseif ($pantallaConstruccion == 1):

    $tituloPag = $auxiliar->traduce("Unidad Organizativa", $administrador->ID_IDIOMA);
    $tituloNav = $auxiliar->traduce("Construccion", $administrador->ID_IDIOMA) . " >> " . $tituloPag;

    $ZonaTablaPadre    = "Construccion";
    $ZonaSubTablaPadre = "ConstruccionMenuEstructura";
    $ZonaTabla         = "ConstruccionUnidadOrganizativa";

    // COMPRUEBA SI TIENE PERMISOS
    if ($administrador->Hayar_Permiso_Perfil('ADM_CONSTRUCCION_UNIDAD_ORGANIZATIVA') < 1):
        $html->PagError("SinPermisos");
    endif;

else:
    $tituloPag         = $auxiliar->traduce("Ubicaciones", $administrador->ID_IDIOMA);
    $tituloNav         = $auxiliar->traduce("Maestros", $administrador->ID_IDIOMA) . " >> " . $auxiliar->traduce("Ubicaciones", $administrador->ID_IDIOMA);
    $ZonaTablaPadre    = "Maestros";
    $ZonaSubTablaPadre = "MaestrosSubmenuAlmacen";
    $ZonaTabla         = "MaestrosUbicaciones";

// COMPRUEBA SI TIENE PERMISOS
    if ($administrador->Hayar_Permiso_Perfil('ADM_MAESTROS_UBICACIONES') < 1):
        $html->PagError("SinPermisos");
    endif;
endif;

//OBTENGO EL REGISTRO E INICIALIZO VALORES DE LAS VARIABLES CON LO QUE HAY EN BASE DE DATOS
if ($idUbicacion != ""):
    // OBTENGO REGISTRO
    $sql    = "SELECT C.REFERENCIA as REF_CENTRO, C.CENTRO as NOM_CENTRO, A.REFERENCIA AS REF_ALMACEN,A.NOMBRE AS NOM_ALMACEN,A.ID_ALMACEN, A.TIPO_ALMACEN,
                      C.ID_CENTRO,U.ID_UBICACION,U.UBICACION,U.TIPO_UBICACION,U.ID_UBICACION_CATEGORIA,U.CLASE_APQ,U.PRECIO_FIJO,U.BAJA,U.DESCRIPCION,
                      U.CANTIDAD_PANELES, U.ESTADO_SECTOR, U.NOMBRE_MAQUINA, U.ID_UNIDAD_ORGANIZATIVA_PROCESO, A.ID_CENTRO_FISICO,
                      U.CANTIDAD_PANELES_POWERBLOCK, U.POTENCIA_PMW_POWERBLOCK, U.TIPO_PREVENTIVO, U.ID_TIPO_SECTOR, U.ID_UBICACION_CENTRO_FISICO, U.AUTOSTORE
					FROM UBICACION U
					INNER JOIN ALMACEN A ON A.ID_ALMACEN = U.ID_ALMACEN
					INNER JOIN CENTRO C ON C.ID_CENTRO = A.ID_CENTRO
					WHERE ID_UBICACION = $idUbicacion";
    $result = $bd->ExecSQL($sql);
    $row    = $bd->SigReg($result);

    //COMPRUEBO QUE TENGA PERMISOS DE LECTURA EN LA UBICACION
    $html->PagErrorCondicionado($administrador->comprobarUbicacionPermiso($row->ID_UBICACION, "Lectura"), "==", false, "SinPermisosSubzona");

    // INICIALIZO VARIABLES
    $txUbicacion                   = $row->UBICACION;
    $txIdUbicacion                 = $row->ID_UBICACION;
    $idAlmacen                     = $row->ID_ALMACEN;
    $txRefAlmacen                  = $row->REF_ALMACEN;
    $txAlmacen                     = $row->NOM_ALMACEN;
    $idCentro                      = $row->ID_CENTRO;
    $txRefCentro                   = $row->REF_CENTRO;
    $txCentro                      = $row->NOM_CENTRO;
    $selTipoUbicacion              = $row->TIPO_UBICACION;
    $selAPQ                        = $row->CLASE_APQ;
    $txNombreMaquina               = $row->NOMBRE_MAQUINA;
    $txPanelesPowerblock           = $row->CANTIDAD_PANELES_POWERBLOCK;
    $txPotenciaPowerblock          = $row->POTENCIA_PMW_POWERBLOCK;
    $txPotenciaPowerblockCalculada = $row->POTENCIA_PMW_POWERBLOCK * $row->POTENCIA_PMW_POWERBLOCK;
    $chPreventivoDePendientes      = ($row->TIPO_PREVENTIVO == 'Pendientes' ? 1 : 0);
    $idTipoSector                  = $row->ID_TIPO_SECTOR;

    $editableSoloEnCF = false;
    if ($row->ID_UBICACION_CENTRO_FISICO != NULL):
        $rowUbicacionCentroFisico = $bd->VerReg("UBICACION_CENTRO_FISICO", "ID_UBICACION_CENTRO_FISICO", $row->ID_UBICACION_CENTRO_FISICO);
        $idUbicacionCentroFisico  = $rowUbicacionCentroFisico->ID_UBICACION_CENTRO_FISICO;
        $txUbicacionCentroFisico  = $rowUbicacionCentroFisico->REFERENCIA_UBICACION;
        $editableSoloEnCF         = true;
    endif;

    //COMPROBAMOS SI ES DE STOCK EXTERNALIZADO
    $stockExternalizado = ($row->TIPO_ALMACEN == "externalizado" ? true : false);

    //SI TIENE TIPO UOP
    $idUnidadOrganizativaProceso   = $row->ID_UNIDAD_ORGANIZATIVA_PROCESO;
    $tipoInternoUnidadOrganizativa = "";
    if ($row->ID_UNIDAD_ORGANIZATIVA_PROCESO != NULL):
        //BUSCAMOS LA UOP
        $rowUOP = $bd->VerReg("UNIDAD_ORGANIZATIVA_PROCESO", "ID_UNIDAD_ORGANIZATIVA_PROCESO", $row->ID_UNIDAD_ORGANIZATIVA_PROCESO);

        //ASIGNAMOS VALOR
        $txUnidadOrganizativaProceso   = ($administrador->ID_IDIOMA == "ESP" ? $rowUOP->TIPO_UOP_ESP : $rowUOP->TIPO_UOP_ENG);
        $tipoInternoUnidadOrganizativa = $rowUOP->TIPO_INTERNO;
    endif;

    //BUSCAMOS EL CENTRO FISICO
    if ($row->ID_CENTRO_FISICO != NULL):
        $rowCentroFisico = $bd->VerReg("CENTRO_FISICO", "ID_CENTRO_FISICO", $row->ID_CENTRO_FISICO);
    endif;

    //BUSCO LA CATEGORIA UBICACION
    $NotificaErrorPorEmail = "No";
    $rowCategoriaUbicacion = $bd->VerRegRest("UBICACION_CATEGORIA", "ID_UBICACION_CATEGORIA = '$row->ID_UBICACION_CATEGORIA'", "No");
    unset($NotificaErrorPorEmail);
    if ($rowCategoriaUbicacion != false):
        $idCategoriaUbicacion = $rowCategoriaUbicacion->ID_UBICACION_CATEGORIA;
        $txCategoriaUbicacion = $rowCategoriaUbicacion->ID_UBICACION_CATEGORIA . " - " . $rowCategoriaUbicacion->NOMBRE;
    endif;

    $txDescripcion = $row->DESCRIPCION;
    $chAutostore   = $row->AUTOSTORE;
    $chBaja        = $row->BAJA;
    $accion        = 'Modificar';

    if ($row->TIPO_UBICACION == 'Gaveta'):
        //BUSCO EL CONTENEDOR
        $NotificaErrorPorEmail = "No";
        $rowContenedorGaveta   = $bd->VerRegRest("CONTENEDOR", "ID_UBICACION = $row->ID_UBICACION AND TIPO = 'Gaveta'", "No");
        unset($NotificaErrorPorEmail);
        //BUSCO EL ALMACEN DESTINO DE LA GAVETA
        $NotificaErrorPorEmail   = "No";
        $rowAlmacenDestinoGaveta = $bd->VerReg("ALMACEN", "ID_ALMACEN", $rowContenedorGaveta->ID_ALMACEN_DESTINO_GAVETA, "No");
        unset($NotificaErrorPorEmail);
        $idAlmacenDestinoGaveta    = $rowAlmacenDestinoGaveta->ID_ALMACEN;
        $txRefAlmacenDestinoGaveta = $rowAlmacenDestinoGaveta->REFERENCIA;
        $txAlmacenDestinoGaveta    = $rowAlmacenDestinoGaveta->NOMBRE;
        $txPasilloGaveta           = $rowContenedorGaveta->GAVETA_PASILLO;
        $txProfundidadGaveta       = $rowContenedorGaveta->GAVETA_PROFUNDIDAD;
    endif;

    $txCantidadPanelesSector = 0;
    $txTipoSector            = "";
    if ($row->TIPO_UBICACION == 'Sector'):
        //SE OBTIENE EL TIPO_SECTOR
        $NotificaErrorPorEmail = "No";
        $rowTipoSector         = $bd->VerReg("TIPO_SECTOR", "ID_TIPO_SECTOR", $idTipoSector, "No");
        unset($NotificaErrorPorEmail);
        if ($rowTipoSector != false):
            $txTipoSector            = $rowTipoSector->ID_TIPO_SECTOR . " - " . ($administrador->ID_IDIOMA == "ESP" ? $rowTipoSector->DESCRIPCION_ESP : $rowTipoSector->DESCRIPCION_ESP);
            $txCantidadPanelesSector = $rowTipoSector->FILAS * $rowTipoSector->COLUMNAS;
        endif;
    endif;

else:
    if ($idAlmacen != ""):

        //BUSCO EL ALMACEN
        $NotificaErrorPorEmail = "No";
        $rowAlmacen            = $bd->VerReg("ALMACEN", "ID_ALMACEN", $idAlmacen, "No");
        unset($NotificaErrorPorEmail);
        $idAlmacen        = $rowAlmacen->ID_ALMACEN;
        $txRefAlmacen     = $rowAlmacen->REFERENCIA;
        $txAlmacen        = $rowAlmacen->NOMBRE;
        $selTipoUbicacion = 'Maquina';

    endif;
    $accion = 'Insertar';
endif;
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
    <script type="text/javascript">
        jQuery(document).ready(function () {
            //FANCYBOX CON IFRAME PARA EL BUSCADOR
            jQuery("a.fancyboxCentros").fancybox({
                'type': 'iframe',
                'width': '100%',
                'height': '100%',
                'hideOnOverlayClick': false
            });

            jQuery("a.fancyboxAlmacenes").fancybox({
                'type': 'iframe',
                'width': '100%',
                'height': '100%',
                'hideOnOverlayClick': false
            });

            jQuery("a.fancyboxCategoriaUbicacion").fancybox({
                'type': 'iframe',
                'width': '100%',
                'height': '100%',
                'hideOnOverlayClick': false
            });

            jQuery("a.fancyboxUbicacionCF").fancybox({
                'type': 'iframe',
                'width': '100%',
                'height': '100%',
                'hideOnOverlayClick': false
            });

            jQuery("a.fancyboxAlmacenesDestinoGaveta").fancybox({
                'type': 'iframe',
                'width': '100%',
                'height': '100%',
                'hideOnOverlayClick': false
            });

            jQuery("a.fancyboxTipoSector").fancybox({
                'type': 'iframe',
                'width': '100%',
                'height': '100%',
                'hideOnOverlayClick': false
            });

            jQuery("a.fancyboxUOP").fancybox({
                'type': 'iframe',
                'width': '100%',
                'height': '100%',
                'hideOnOverlayClick': false,
                'onClosed': function () {
                    ComprobarInputObligatorio(jQuery('#txUnidadOrganizativaProceso'));
                    VerPotenciaPanelesPWB(jQuery('#tipoInternoUnidadOrganizativaProceso').val());

                }
            });

            VerAlmacenDestinoGaveta('<? echo $row->TIPO_UBICACION; ?>');
            VerTipoPreventivo('<? echo $row->TIPO_UBICACION; ?>');
            VerCantidadPanelesSector('<? echo $row->TIPO_UBICACION; ?>');
            VerTiposSector('<? echo $row->TIPO_UBICACION; ?>');
            VerNombreMaquina('<? echo $row->TIPO_UBICACION; ?>');
            VerPotenciaPanelesPWB('<? echo $tipoInternoUnidadOrganizativa; ?>');
        });
    </script>

    <script language="JavaScript" type="text/javascript">
        function confirmar() {
            if (confirm('<?=$auxiliar->traduce("¿Esta seguro de querer borrar este registro?", $administrador->ID_IDIOMA)?>')) {
                document.FormSelect.action = 'accion.php';
                document.FormSelect.accion.value = 'BorrarAlmacen';
                document.FormSelect.submit();
                return false
            } else {
                return false;
            }
        }

        function VerAlmacenDestinoGaveta(tipoUbicacion) {
            if (tipoUbicacion == 'Gaveta') {
                jQuery("#AlmacenDestinoGaveta").show("slow");
                jQuery("#PasilloGaveta").show("slow");
                jQuery("#ProfundidadGaveta").show("slow");
            } else {
                jQuery("#AlmacenDestinoGaveta").hide("slow");
                jQuery("#PasilloGaveta").hide("slow");
                jQuery("#ProfundidadGaveta").hide("slow");
            }
        }

        function VerTipoPreventivo(tipoUbicacion) {
            if (tipoUbicacion == 'Preventivo') {
                jQuery("#TipoPreventivo").show("slow");
            } else {
                jQuery("#TipoPreventivo").hide("slow");
            }
        }

        function VerCantidadPanelesSector(tipoUbicacion) {
            if (tipoUbicacion == 'Sector') {
                jQuery("#CantidadPanelesSector").show("slow");
                jQuery("#EstadoSector").show("slow");
            } else {
                jQuery("#CantidadPanelesSector").hide("slow");
                jQuery("#EstadoSector").hide("slow");
            }
        }

        function VerTiposSector(tipoUbicacion) {
            if (tipoUbicacion == 'Sector') {
                jQuery("#TipoSector").show("slow");
                jQuery("#EstadoSector").show("slow");
            } else {
                jQuery("#TipoSector").hide("slow");
                jQuery("#EstadoSector").hide("slow");
            }
        }

        function VerNombreMaquina(tipoUbicacion) {
            <?if($pantallaSolar != 1 && $pantallaConstruccion != 1):?>
            if (tipoUbicacion == 'Maquina' || tipoUbicacion == 'Power Block') {
                jQuery("#CampoNombreMaquina").show("slow");
                jQuery("#CampoTipoUOP").show("slow");
            } else {
                jQuery("#CampoNombreMaquina").hide("slow");
                jQuery("#CampoTipoUOP").hide("slow");
            }
            <?endif;?>
        }

        function VerPotenciaPanelesPWB(tipoInterno) {
            if (tipoInterno == "Power Block") {
                jQuery("#CampoPotenciaPWB").show("slow");
                jQuery("#CampoPanelesPWB").show("slow");
                jQuery("#CampoPotenciaCalculada").show("slow");
            } else {
                jQuery("#CampoPotenciaPWB").hide("slow");
                jQuery("#CampoPanelesPWB").hide("slow");
                jQuery("#CampoPotenciaCalculada").hide("slow");
            }
        }

        function cambiarPotenciaCalculada() {
            var potencia = jQuery("#txPotenciaPowerblock").val();
            var paneles = jQuery("#txPanelesPowerblock").val();
            jQuery("#txPotenciaPowerblockCalculada").val(potencia * paneles);
        }
    </script>
</head>
<body bgcolor="#FFFFFF" background="<?= "$pathRaiz" ?>imagenes/fondo_pantalla.gif" leftmargin="0" topmargin="0"
      marginwidth="0" marginheight="0" onLoad="document.FormSelect.txUbicacion.focus()">
<FORM NAME="FormSelect" ACTION="accion.php" METHOD="POST">
    <input type=hidden name="accion" value="<?= $accion ?>">
    <input type=hidden name="idUbicacion" value="<?= $idUbicacion ?>">
    <input type=hidden name="selTipoUbicacion" value="<?= $selTipoUbicacion ?>">
    <input type="hidden" name="pantallaSolar" id="pantallaSolar" value="<?= $pantallaSolar ?>"/>
    <input type="hidden" name="pantallaConstruccion" id="pantallaConstruccion" value="<?= $pantallaConstruccion ?>"/>
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
                                    src="<?= $pathRaiz ?>imagenes/transparente.gif" width="10" height="3"></td>
                    </tr>
                    <tr>
                        <? include $pathRaiz . "tabla_izqda.php"; ?>
                        <td align="left" valign="top" bgcolor="#FFFFFF"
                            background="<?= $pathRaiz ?>imagenes/fondo_pantalla.gif">
                            <table width="100%" border="0" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td height="23">
                                        <table width="100%" height="23" border="0" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td width="25" class="linearriba"><img
                                                            src="<?= $pathRaiz ?>imagenes/flechitas_01.gif" width="35"
                                                            height="23"></td>
                                                <td width="469" align="left" valign="middle" bgcolor="#B3C7DA"
                                                    class="linearriba">
                                                    <table width="440" border="0" cellspacing="0" cellpadding="0">
                                                        <tr>
                                                            <td align="left" class="alertas"><?= $tituloPag ?></td>
                                                        </tr>
                                                    </table>
                                                </td>
                                                <td width="25"><img src="<?= $pathRaiz ?>imagenes/esquina.gif"
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
                                                                &nbsp;
                                                            </td>
                                                            <td width="220" bgcolor="#982a29" class="lineabajoarriba"
                                                                colspan=2><font class="tituloNav"><? echo $tituloNav ?>
                                                                </font></td>
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
                                    <td height="220" align="center" valign="top" bgcolor="#AACFF9" class="lineabajo">
                                        <table width="100%" height="281" border="0" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td width="20" height="281" align="center" valign="middle"
                                                    class="lineaderecha">&nbsp;
                                                </td>
                                                <td align="center" valign="middle">
                                                    <table width="97%" border="0" align="center" cellpadding="0"
                                                           cellspacing="0">
                                                        <tr bgcolor="#D9E3EC">
                                                            <td height="10" colspan="3" bgcolor="#D9E3EC"
                                                                class="linearribadereizq"><img
                                                                        src="<? echo $pathRaiz ?>imagenes/transparente.gif"
                                                                        width="10" height="10"></td>
                                                        </tr>
                                                        <tr bgcolor="#D9E3EC" class="lineabajodereizq">
                                                            <td width="5" bgcolor="#D9E3EC" class="lineaizquierda">
                                                                &nbsp;
                                                            </td>
                                                            <td width="540" align="left" bgcolor="#D9E3EC">
                                                                <table width="750" border="0" cellspacing="0"
                                                                       cellpadding="1" class="tablaFiltros">
                                                                    <? if ($idUbicacion != ""): ?>
                                                                        <tr>
                                                                            <td align="center">&nbsp;</td>
                                                                            <td align="left"
                                                                                class="textoazul"><?= $auxiliar->traduce("Id ubicación", $administrador->ID_IDIOMA) . ":"; ?>
                                                                            </td>
                                                                            <td class="textoazul">
                                                                                <?
                                                                                $TamanoText = "420px";
                                                                                $MaxLength  = "10";

                                                                                $ClassText = "copyright ObligatorioRellenar";
                                                                                $readonly  = "disabled";
                                                                                $html->TextBox("txIdUbicacion", $txIdUbicacion);
                                                                                unset($readonly);
                                                                                ?>
                                                                            </td>
                                                                        </tr>
                                                                    <? endif ?>
                                                                    <tr>
                                                                        <td align="center"><img
                                                                                    src="<? echo $pathRaiz ?>imagenes/diamante.gif"
                                                                                    width="7" height="7"/></td>
                                                                        <td align="left"
                                                                            class="textoazul">
                                                                            <? if ((($pantallaSolar != 1) && ($pantallaConstruccion != 1))): //LAS PANTALLAS DE CONSTRUCCION SON INFORMATIVAS
                                                                                echo $auxiliar->traduce("Ubicación", $administrador->ID_IDIOMA) . ":";
                                                                            else:
                                                                                echo $auxiliar->traduce("Unidad Organizativa", $administrador->ID_IDIOMA) . ":";
                                                                            endif;
                                                                            ?>
                                                                        </td>
                                                                        <td class="textoazul"><? $TamanoText = "420px";
                                                                            $MaxLength                       = "100";

                                                                            $ClassText = "copyright ObligatorioRellenar";

                                                                            if ($stockExternalizado):
                                                                                $readonly = "disabled";
                                                                            endif;

                                                                            $html->TextBox("txUbicacion", $txUbicacion);
                                                                            unset($readonly);
                                                                            ?></td>
                                                                    </tr>
                                                                    <tr id="CampoTipoUOP"
                                                                        style="<?= (($pantallaSolar != 1 && $pantallaConstruccion != 1) ? "display:none;" : "") ?>">
                                                                        <td align="center"><img
                                                                                    src="<? echo $pathRaiz ?>imagenes/diamante.gif"
                                                                                    width="7" height="7"/></td>
                                                                        <td align="left"
                                                                            class="textoazul"><?= $auxiliar->traduce("Tipo Unidad Organizativa Proceso", $administrador->ID_IDIOMA) . ":"; ?>
                                                                        </td>
                                                                        <td class="textoazul">
                                                                            <?
                                                                            $idTextBox  = "txUnidadOrganizativaProceso";
                                                                            $TamanoText = "400px";
                                                                            $MaxLength  = "150";
                                                                            $ClassText  = "copyright ObligatorioRellenar";
                                                                            $jscript    = "onchange=\" document.FormSelect.idUnidadOrganizativaProceso.value='';\"";
                                                                            $html->TextBox("txUnidadOrganizativaProceso", $txUnidadOrganizativaProceso);
                                                                            unset($idTextBox);
                                                                            unset($jscript);
                                                                            ?>
                                                                            <input type="hidden"
                                                                                   name="idUnidadOrganizativaProceso"
                                                                                   id="idUnidadOrganizativaProceso"
                                                                                   value="<?= $idUnidadOrganizativaProceso ?>"/>
                                                                            <input type="hidden"
                                                                                   name="tipoInternoUnidadOrganizativaProceso"
                                                                                   id="tipoInternoUnidadOrganizativaProceso"
                                                                                   value="<?= $idUnidadOrganizativaProceso ?>"/>
                                                                            <a href="<?= $pathRaiz; ?>buscadores_maestros/busqueda_unidad_organizativa.php?AlmacenarId=1<?= ($pantallaSolar == 1 ? "&soloFotovoltaico=1" : ($pantallaConstruccion == 1 ? "&soloEolico=1" : "")); ?><?= ($rowCentroFisico->TIPO_CONSTRUCCION == "Fotovoltaico" ? "&soloFotovoltaico=1" : ($rowCentroFisico->TIPO_CONSTRUCCION == "Eolico" ? "&soloEolico=1" : "")); ?>"
                                                                               class="fancyboxUOP"
                                                                               id="UOP">
                                                                                <img border="0"
                                                                                     align="absbottom"
                                                                                     alt="Buscar"
                                                                                     src="<?= $pathRaiz ?>imagenes/botones/listado.png"
                                                                                     name="Listado">
                                                                            </a>
                                                                            <span
                                                                                    id="c"
                                                                                    style="display: none;"><img
                                                                                        src="<?= $pathClases; ?>lib/ajax_script/img/esperando.gif"
                                                                                        width="15"
                                                                                        height="11"
                                                                                        alt="Buscando..."/></span>

                                                                            <div class="entry"
                                                                                 align="left"
                                                                                 id="actualizador_UnidadOrganizativaProcesos"></div>
                                                                            <script
                                                                                    type="text/javascript"
                                                                                    language="javascript">
                                                                                new Ajax.Autocompleter('txUnidadOrganizativaProceso', 'actualizador_UnidadOrganizativaProcesos', '<?=$pathRaiz;?>buscadores_maestros/resp_ajax_unidad_organizativa.php?AlmacenarId=1<?= ($rowCentroFisico->TIPO_CONSTRUCCION == "Fotovoltaico" ? "&soloFotovoltaico=1" : ($rowCentroFisico->TIPO_CONSTRUCCION == "Eolico" ? "&soloEolico=1" : "")); ?>',
                                                                                    {
                                                                                        method: 'post',
                                                                                        indicator: 'actualizador_UnidadOrganizativaProcesos',
                                                                                        minChars: '2',
                                                                                        afterUpdateElement: function (textbox, valor) {
                                                                                            jQuery('#idUnidadOrganizativaProceso').val(jQuery(valor).children('a').attr('alt'));
                                                                                            jQuery('#tipoInternoUnidadOrganizativaProceso').val(jQuery(valor).children('a').attr('interno'));
                                                                                            VerPotenciaPanelesPWB(jQuery('#tipoInternoUnidadOrganizativaProceso').val());
                                                                                        }
                                                                                    }
                                                                                );
                                                                            </script>
                                                                        </td>
                                                                    </tr>
                                                                    <tr id="CampoNombreMaquina"
                                                                        style="<?= (($pantallaSolar != 1 && $pantallaConstruccion != 1) ? "display:none;" : "") ?>">
                                                                        <td align="center"><img
                                                                                    src="<? echo $pathRaiz ?>imagenes/diamante.gif"
                                                                                    width="7" height="7"/></td>
                                                                        <td align="left"
                                                                            class="textoazul"><?= $auxiliar->traduce("Denominacion", $administrador->ID_IDIOMA) . ":"; ?>
                                                                        </td>
                                                                        <td class="textoazul"><?
                                                                            $TamanoText = "420px";
                                                                            $MaxLength  = "100";

                                                                            $ClassText = "copyright ObligatorioRellenar";
                                                                            $html->TextBox("txNombreMaquina", $txNombreMaquina);
                                                                            ?></td>
                                                                    </tr>
                                                                    <? if ($idUbicacion != ''): ?>
                                                                        <tr>
                                                                            <td align="center"></td>
                                                                            <td height="20" align="left" valign="middle"
                                                                                class="textoazul"><?= $auxiliar->traduce("Centro", $administrador->ID_IDIOMA) . ":"; ?>
                                                                            </td>
                                                                            <td align="left" valign="middle">
                                                                                <?
                                                                                $TamanoText = '420px';
                                                                                $ClassText  = "copyright";
                                                                                $MaxLength  = "50";
                                                                                $idTextBox  = 'txCentro';
                                                                                $readonly   = "disabled";
                                                                                $html->TextBox("txCentro", $txRefCentro . " - " . $txCentro);
                                                                                unset($jscript);
                                                                                unset($readonly);
                                                                                unset($idTextBox);
                                                                                ?>
                                                                                <input type="hidden" name="idCentro"
                                                                                       id="idCentro"
                                                                                       value="<?= $idCentro ?>"/>
                                                                            </td>
                                                                        </tr>
                                                                    <? endif; ?>
                                                                    <tr>
                                                                        <td align="center" width="5%"><img
                                                                                    src="<? echo $pathRaiz ?>imagenes/diamante.gif"
                                                                                    width="7" height="7"></td>
                                                                        <td align="left" class="textoazul"
                                                                            width="35%">
                                                                            <? if ((($pantallaSolar != 1) && ($pantallaConstruccion != 1))): //LAS PANTALLAS DE CONSTRUCCION SON INFORMATIVAS
                                                                                echo $auxiliar->traduce("Almacén", $administrador->ID_IDIOMA) . ":";
                                                                            else:
                                                                                echo $auxiliar->traduce("Instalacion", $administrador->ID_IDIOMA) . ":";
                                                                            endif;
                                                                            ?>
                                                                        </td>
                                                                        <td class="textoazul" width="60%">
                                                                            <?
                                                                            $TamanoText = "400px";
                                                                            $ClassText  = "copyright ObligatorioRellenar";
                                                                            $MaxLength  = "50";
                                                                            $idTextBox  = 'txAlmacen';
                                                                            if ($idUbicacion != ''):
                                                                                $readonly = "disabled";
                                                                            endif;

                                                                            if ($accion != "Insertar"):
                                                                                $jscript = "onchange=\"document.FormSelect.idAlmacen.value=''\"";
                                                                                if (($txAlmacen != "") && ($txRefAlmacen != "")):
                                                                                    $html->TextBox("txAlmacen", $txRefAlmacen . " - " . $txAlmacen);
                                                                                else:
                                                                                    $html->TextBox("txAlmacen", "");
                                                                                endif;
                                                                            else:
                                                                                $administrador->precargarValorDefectoSiNecesario("ALMACEN", $idAlmacen, $txAlmacen, false);
                                                                                $html->TextBox("txAlmacen", $txAlmacen);
                                                                            endif;

                                                                            unset($jscript);
                                                                            unset($readonly);
                                                                            unset($idTextBox);
                                                                            ?>
                                                                            <input type="hidden" name="idAlmacen"
                                                                                   id="idAlmacen"
                                                                                   value="<? echo $idAlmacen ?>">
                                                                            <? if ($idUbicacion == ''): ?>
                                                                            <a href="<?= $pathRaiz ?>buscadores_maestros_restringidos/busqueda_almacen.php?AlmacenarId=1&tipoConstruccion=<?= ($pantallaSolar == 1 ? "Fotovoltaico" : ($pantallaConstruccion == 1 ? "Eolico" : "")) ?>"
                                                                               class="fancyboxAlmacenes" id="almacenes">
                                                                                <? endif; ?>
                                                                                <img border="0" align="absmiddle"
                                                                                     alt="<?= $auxiliar->traduce("Buscar Almacén", $administrador->ID_IDIOMA) ?>"
                                                                                     src="<?= $pathRaiz ?>imagenes/botones/listado.png"
                                                                                     name="Listado">
                                                                                <? if ($idUbicacion == ''): ?>
                                                                            </a>
                                                                        <? endif; ?>
                                                                            <? if ($idUbicacion == ''): ?>
                                                                                <span id="desplegable_almacenes"
                                                                                      style="display: none;">
                                                                                    <img
                                                                                            src="<?= $pathClases; ?>lib/ajax_script/img/esperando.gif"
                                                                                            width="15"
                                                                                            height="11"
                                                                                            alt="<?= $auxiliar->traduce("Buscando...", $administrador->ID_IDIOMA) ?>"/>
                                                                                </span>
                                                                                <div class="entry" align="left"
                                                                                     id="actualizador_almacenes"></div>
                                                                                <script type="text/javascript"
                                                                                        language="javascript">
                                                                                    new Ajax.Autocompleter('txAlmacen', 'actualizador_almacenes', '<?=$pathRaiz?>buscadores_maestros_restringidos/resp_ajax_almacen.php?AlmacenarId=1&tipoConstruccion=<?= ($pantallaSolar == 1 ? "Fotovoltaico" : ($pantallaConstruccion == 1 ? "Eolico" : ""))?>',
                                                                                        {
                                                                                            method: 'post',
                                                                                            indicator: 'desplegable_almacenes',
                                                                                            minChars: '2',
                                                                                            afterUpdateElement: function (textbox, valor) {
                                                                                                siguiente_control(jQuery('#' + this.paramName));
                                                                                                jQuery('#idAlmacen').val(jQuery(valor).children('a').attr('alt'));
                                                                                            }
                                                                                        }
                                                                                    );
                                                                                </script>
                                                                            <? endif; ?>
                                                                        </td>
                                                                    </tr>

                                                                    <? if ($pantallaSolar != 1 && $pantallaConstruccion != 1): ?>
                                                                        <tr>
                                                                            <td align="center" width="5%">&nbsp;</td>
                                                                            <td align="left" class="textoazul"
                                                                                width="35%"><?= $auxiliar->traduce("Tipo Ubicación", $administrador->ID_IDIOMA) . ":"; ?>
                                                                            </td>
                                                                            <td class="textoazul" width="60%">
                                                                                <?
                                                                                $i = 0;
                                                                                unset($opciones);
                                                                                $opciones[$i]['text']  = $auxiliar->traduce("Estándar", $administrador->ID_IDIOMA);
                                                                                $opciones[$i]['valor'] = '';
                                                                                $i                     = $i + 1;

                                                                                //BUSCO LOS POSIBLES VALORES DE TIPO UBICACION
                                                                                $sqlTiposUbicacion    = "SHOW COLUMNS FROM UBICACION LIKE 'TIPO_UBICACION'";
                                                                                $resultTiposUbicacion = $bd->ExecSQL($sqlTiposUbicacion);
                                                                                if ($resultTiposUbicacion != false):
                                                                                    $rowTiposUbicacion = mysqli_fetch_array($resultTiposUbicacion);

                                                                                    $cadenavalor = $rowTiposUbicacion[1];
                                                                                    $cadenavalor = str_replace("enum", "", (string)$cadenavalor);
                                                                                    $cadenavalor = str_replace("(", "", (string)$cadenavalor);
                                                                                    $cadenavalor = str_replace(")", "", (string)$cadenavalor);
                                                                                    $cadenavalor = str_replace("'", "", (string)$cadenavalor);
                                                                                    $valores     = explode(",", $cadenavalor);

                                                                                    //RECORRO EL ARRAY PARA AÑADIR LOS VALORES EXTRAIDOS
                                                                                    foreach ($valores as $tipoUbicacion):
                                                                                        $opciones[$i]['text']  = $auxiliar->traduce($tipoUbicacion, $administrador->ID_IDIOMA);
                                                                                        $opciones[$i]['valor'] = $tipoUbicacion;
                                                                                        $i                     = $i + 1;
                                                                                    endforeach;
                                                                                endif;

                                                                                $NombreSelect = 'selTipoUbicacion';
                                                                                $Tamano       = '425px';
                                                                                $Estilo       = "copyright";
                                                                                $onChange     = "onChange = 'VerAlmacenDestinoGaveta(this.value);VerCantidadPanelesSector(this.value);VerTiposSector(this.value);VerNombreMaquina(this.value);VerTipoPreventivo(this.value);'";

                                                                                if ($stockExternalizado || $editableSoloEnCF):
                                                                                    $disabled = "disabled";
                                                                                endif;

                                                                                $html->SelectArr($NombreSelect, $opciones, $selTipoUbicacion, $selTipoUbicacion);
                                                                                unset($disabled);
                                                                                unset($onChange);
                                                                                ?>
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td align="center" width="5%">&nbsp;</td>
                                                                            <td align="left" class="textoazul"
                                                                                width="35%"><?= $auxiliar->traduce("Ubicacion Centro Fisico", $administrador->ID_IDIOMA) . ":"; ?>
                                                                            </td>
                                                                            <td class="textoazul" width="60%">
                                                                                <?
                                                                                $ClassText  = "copyright";
                                                                                $MaxLength  = "50";
                                                                                $idTextBox  = 'txUbicacionCentroFisico';
                                                                                $TamanoText = "400px";

                                                                                $jscript = "onchange=\"document.FormSelect.idUbicacionCentroFisico.value=''\"";
                                                                                $html->TextBox("txUbicacionCentroFisico", $txUbicacionCentroFisico);
                                                                                unset($jscript);
                                                                                unset($idTextBox);
                                                                                unset($readonly);
                                                                                ?>
                                                                                <input type="hidden"
                                                                                       name="idUbicacionCentroFisico"
                                                                                       id="idUbicacionCentroFisico"
                                                                                       value="<? echo $idUbicacionCentroFisico ?>">
                                                                                <a href="<?= $pathRaiz ?>buscadores_maestros/busqueda_ubicacion_centro_fisico.php?AlmacenarId=1"
                                                                                   class="fancyboxUbicacionCF"
                                                                                   id="ubicacionCF">
                                                                                    <img border="0" align="absmiddle"
                                                                                         alt="<?= $auxiliar->traduce("Buscar", $administrador->ID_IDIOMA) ?>"
                                                                                         src="<?= $pathRaiz ?>imagenes/botones/listado.png"
                                                                                         name="Listado">
                                                                                </a>
                                                                                <span id="desplegable_ubicaciones_cf"
                                                                                      style="display: none;">
                                                                                    <img
                                                                                            src="<?= $pathClases; ?>lib/ajax_script/img/esperando.gif"
                                                                                            width="15"
                                                                                            height="11"
                                                                                            alt="<?= $auxiliar->traduce("Buscando...", $administrador->ID_IDIOMA) ?>"/>
                                                                                </span>
                                                                                <div class="entry" align="left"
                                                                                     id="actualizador_ubicaciones_cf"></div>
                                                                                <script type="text/javascript"
                                                                                        language="javascript">
                                                                                    new Ajax.Autocompleter('txUbicacionCentroFisico', 'actualizador_ubicaciones_cf', '<?=$pathRaiz?>buscadores_maestros/resp_ajax_ubicacion_centro_fisico.php?AlmacenarId=1',
                                                                                        {
                                                                                            method: 'post',
                                                                                            indicator: 'desplegable_ubicaciones_cf',
                                                                                            minChars: '2',
                                                                                            afterUpdateElement: function (textbox, valor) {
                                                                                                jQuery('#idUbicacionCentroFisico').val(jQuery(valor).children('a').attr('alt'));
                                                                                            }
                                                                                        }
                                                                                    );
                                                                                </script>
                                                                            </td>
                                                                        </tr>
                                                                    <? endif; ?>
                                                                    <tr id="TipoPreventivo" style="display:none;">
                                                                        <td align="center" width="5%"></td>
                                                                        <td align="left"
                                                                            class="textoazul"><?= $auxiliar->traduce("Preventivo de Pendientes", $administrador->ID_IDIOMA) . ":"; ?>
                                                                        </td>
                                                                        <td class="textoazul">
                                                                            <?
                                                                            $Estilo = 'check_estilo';
                                                                            $html->Option("chPreventivoDePendientes", "Check", "1", $chPreventivoDePendientes);
                                                                            ?>
                                                                        </td>
                                                                    </tr>
                                                                    <tr id="CampoPotenciaPWB"
                                                                        style="<?= (($pantallaSolar != 1 && $pantallaConstruccion != 1) ? "display:none;" : "") ?>">
                                                                        <td align="center"></td>
                                                                        <td align="left"
                                                                            class="textoazul"><?= $auxiliar->traduce("Potencia Megavatio Pico", $administrador->ID_IDIOMA) . ":"; ?>
                                                                        </td>
                                                                        <td class="textoazul"><?
                                                                            $TamanoText = "420px";
                                                                            $MaxLength  = "100";

                                                                            $ClassText = "copyright ";
                                                                            $jscript   = "id='txPotenciaPowerblock'  onchange='cambiarPotenciaCalculada();'";
                                                                            $html->TextBox("txPotenciaPowerblock", $txPotenciaPowerblock);
                                                                            unset($jscript);
                                                                            ?></td>
                                                                    </tr>
                                                                    <tr id="CampoPanelesPWB"
                                                                        style="<?= (($pantallaSolar != 1 && $pantallaConstruccion != 1) ? "display:none;" : "") ?>">
                                                                        <td align="center">
                                                                        </td>
                                                                        <td align="left"
                                                                            class="textoazul"><?= $auxiliar->traduce("Nº Paneles PB", $administrador->ID_IDIOMA) . ":"; ?>
                                                                        </td>
                                                                        <td class="textoazul"><?
                                                                            $TamanoText = "420px";
                                                                            $MaxLength  = "100";

                                                                            $ClassText = "copyright";
                                                                            $jscript   = "id='txPanelesPowerblock'  onchange='cambiarPotenciaCalculada();'";
                                                                            $html->TextBox("txPanelesPowerblock", $txPanelesPowerblock);
                                                                            unset($jscript);
                                                                            ?></td>
                                                                    </tr>
                                                                    <tr id="CampoPotenciaCalculada"
                                                                        style="<?= (($pantallaSolar != 1 && $pantallaConstruccion != 1) ? "display:none;" : "") ?>">
                                                                        <td align="center">
                                                                        </td>
                                                                        <td align="left"
                                                                            class="textoazul"><?= $auxiliar->traduce("Potencia PB", $administrador->ID_IDIOMA) . ":"; ?>
                                                                        </td>
                                                                        <td class="textoazul"><?
                                                                            $TamanoText = "420px";
                                                                            $MaxLength  = "100";

                                                                            $ClassText = "copyright";
                                                                            $readonly  = "readonly";
                                                                            $jscript   = "id='txPotenciaPowerblockCalculada'";
                                                                            $html->TextBox("txPotenciaPowerblockCalculada", $txPotenciaPowerblockCalculada);
                                                                            unset($jscript);
                                                                            unset($readonly);
                                                                            ?></td>
                                                                    </tr>
                                                                    <tr id="EstadoSector" style="display:none;">
                                                                        <td align="center" width="5%"></td>
                                                                        <td align="left"
                                                                            class="textoazul"><?= $auxiliar->traduce("Estado Sector", $administrador->ID_IDIOMA) . ":"; ?>
                                                                        </td>
                                                                        <td class="textoazul"><? echo $auxiliar->traduce($row->ESTADO_SECTOR, $administrador->ID_IDIOMA); ?></td>
                                                                    </tr>
                                                                    <? if (($pantallaSolar != 1) && ($pantallaConstruccion != 1))://CAMPOS QUE NO APLICAN EN LA PANTALLA DE INSTALACION DE CONSTRUCCION ?>
                                                                        <tr>
                                                                            <td align="center" width="5%">&nbsp;</td>
                                                                            <td align="left" class="textoazul"
                                                                                width="35%"><?= $auxiliar->traduce("Categoría Ubicación", $administrador->ID_IDIOMA) . ":"; ?>
                                                                            </td>
                                                                            <td class="textoazul" width="60%">
                                                                                <?
                                                                                $ClassText = "copyright";
                                                                                $MaxLength = "50";
                                                                                $idTextBox = 'txCategoriaUbicacion';
                                                                                if ($editableSoloEnCF):
                                                                                    $readonly   = "disabled";
                                                                                    $TamanoText = "420px";
                                                                                else:
                                                                                    $TamanoText = "400px";
                                                                                endif;

                                                                                $jscript = "onchange=\"document.FormSelect.idCategoriaUbicacion.value=''\"";
                                                                                $html->TextBox("txCategoriaUbicacion", $txCategoriaUbicacion);
                                                                                unset($jscript);
                                                                                unset($idTextBox);
                                                                                unset($readonly);

                                                                                if ($editableSoloEnCF == false):
                                                                                    ?>
                                                                                    <input type="hidden"
                                                                                           name="idCategoriaUbicacion"
                                                                                           id="idCategoriaUbicacion"
                                                                                           value="<? echo $idCategoriaUbicacion ?>">
                                                                                    <a href="<?= $pathRaiz ?>buscadores_maestros/busqueda_categoria_ubicacion.php?AlmacenarId=1"
                                                                                       class="fancyboxCategoriaUbicacion"
                                                                                       id="categoriaUbicacion">
                                                                                        <img border="0"
                                                                                             align="absmiddle"
                                                                                             alt="<?= $auxiliar->traduce("Buscar Categoría Ubicación", $administrador->ID_IDIOMA) ?>"
                                                                                             src="<?= $pathRaiz ?>imagenes/botones/listado.png"
                                                                                             name="Listado">
                                                                                    </a>

                                                                                    <span id="desplegable_categorias_ubicacion"
                                                                                          style="display: none;">
                                                                                <img
                                                                                        src="<?= $pathClases; ?>lib/ajax_script/img/esperando.gif"
                                                                                        width="15"
                                                                                        height="11"
                                                                                        alt="<?= $auxiliar->traduce("Buscando...", $administrador->ID_IDIOMA) ?>"/>
                                                                            </span>

                                                                                    <div class="entry" align="left"
                                                                                         id="actualizador_categorias_ubicacion"></div>
                                                                                    <script type="text/javascript"
                                                                                            language="javascript">
                                                                                        new Ajax.Autocompleter('txCategoriaUbicacion', 'actualizador_categorias_ubicacion', '<?=$pathRaiz?>buscadores_maestros/resp_ajax_categoria_ubicacion.php?AlmacenarId=1',
                                                                                            {
                                                                                                method: 'post',
                                                                                                indicator: 'desplegable_categorias_ubicacion',
                                                                                                minChars: '2',
                                                                                                afterUpdateElement: function (textbox, valor) {
                                                                                                    jQuery('#idCategoriaUbicacion').val(jQuery(valor).children('a').attr('alt'));
                                                                                                }
                                                                                            }
                                                                                        );
                                                                                    </script>
                                                                                <? endif; ?>
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td align="center" width="5%">&nbsp;</td>
                                                                            <td align="left" class="textoazul"
                                                                                width="35%"><?= $auxiliar->traduce("Clase APQ", $administrador->ID_IDIOMA) . ":"; ?>
                                                                            </td>
                                                                            <td class="textoazul" width="60%">
                                                                                <?
                                                                                $NombreSelect               = 'selAPQ';
                                                                                $i                          = 0;
                                                                                $Elementos_APQ[$i]['text']  = $auxiliar->traduce("No", $administrador->ID_IDIOMA);
                                                                                $Elementos_APQ[$i]['valor'] = '';
                                                                                $i                          = $i + 1;
                                                                                $Elementos_APQ[$i]['text']  = "1 - " . $auxiliar->traduce("Liquidos inflamables y combustible", $administrador->ID_IDIOMA);
                                                                                $Elementos_APQ[$i]['valor'] = '1';
                                                                                $i                          = $i + 1;
                                                                                $Elementos_APQ[$i]['text']  = "5 - " . $auxiliar->traduce("Botellas y botellones de gases", $administrador->ID_IDIOMA);
                                                                                $Elementos_APQ[$i]['valor'] = '5';
                                                                                $i                          = $i + 1;
                                                                                $Elementos_APQ[$i]['text']  = "6 - " . $auxiliar->traduce("Liquidos corrosivos", $administrador->ID_IDIOMA);
                                                                                $Elementos_APQ[$i]['valor'] = '6';
                                                                                $i                          = $i + 1;
                                                                                $Elementos_APQ[$i]['text']  = "7 - " . $auxiliar->traduce("Liquidos toxicos", $administrador->ID_IDIOMA);
                                                                                $Elementos_APQ[$i]['valor'] = '7';
                                                                                $i                          = $i + 1;
                                                                                $Elementos_APQ[$i]['text']  = "9 - " . $auxiliar->traduce("Peroxidos organicos", $administrador->ID_IDIOMA);
                                                                                $Elementos_APQ[$i]['valor'] = '9';
                                                                                $i                          = $i + 1;
                                                                                $Elementos_APQ[$i]['text']  = "RG";
                                                                                $Elementos_APQ[$i]['valor'] = 'RG';
                                                                                $Tamano                     = "425px";
                                                                                $Estilo                     = "copyright";
                                                                                if ($editableSoloEnCF):
                                                                                    $disabled = "disabled";
                                                                                endif;

                                                                                $html->SelectArr($NombreSelect, $Elementos_APQ, $selAPQ, $selAPQ);
                                                                                unset($disabled);
                                                                                ?>
                                                                            </td>
                                                                        </tr>
                                                                    <? endif; ?>
                                                                    <tr>
                                                                        <td align="center" width="5%">&nbsp;</td>
                                                                        <td align="left" class="textoazul"
                                                                            width="35%"><?= $auxiliar->traduce("Precio Fijo", $administrador->ID_IDIOMA) . ":"; ?>
                                                                        </td>
                                                                        <td class="textoazul" width="60%">
                                                                            <?
                                                                            $Estilo = 'check_estilo';
                                                                            if ($editableSoloEnCF):
                                                                                $disabled = "disabled";
                                                                            endif;
                                                                            $html->Option("chPrecioFijo", "Check", "1", $row->PRECIO_FIJO);
                                                                            unset($disabled);
                                                                            ?>
                                                                        </td>
                                                                    </tr>
                                                                    <tr id="AlmacenDestinoGaveta" style="display:none;">
                                                                        <td align="center" width="5%"><img
                                                                                    src="<? echo $pathRaiz ?>imagenes/diamante.gif"
                                                                                    width="7" height="7"></td>
                                                                        <td align="left" class="textoazul"
                                                                            width="35%"><?= $auxiliar->traduce("Almacén Destino Gaveta", $administrador->ID_IDIOMA) . ":"; ?>
                                                                        </td>
                                                                        <td class="textoazul" width="60%">
                                                                            <?
                                                                            $TamanoText = "400px";
                                                                            $ClassText  = "copyright ObligatorioRellenar";
                                                                            $MaxLength  = "50";
                                                                            $idTextBox  = 'txAlmacenDestinoGaveta';

                                                                            if ($accion != "Insertar"):
                                                                                $jscript = "onchange=\"document.FormSelect.idAlmacenDestinoGaveta.value=''\"";
                                                                                if (($txAlmacenDestinoGaveta != "") && ($txRefAlmacenDestinoGaveta != "")):
                                                                                    $html->TextBox("txAlmacenDestinoGaveta", $txRefAlmacenDestinoGaveta . " - " . $txAlmacenDestinoGaveta);
                                                                                else:
                                                                                    $html->TextBox("txAlmacenDestinoGaveta", "");
                                                                                endif;
                                                                            else:
                                                                                $administrador->precargarValorDefectoSiNecesario("ALMACEN", $idAlmacenDestinoGaveta, $txAlmacenDestinoGaveta, false);
                                                                                $html->TextBox("txAlmacenDestinoGaveta", $txAlmacenDestinoGaveta);
                                                                            endif;
                                                                            unset($jscript);
                                                                            unset($idTextBox);
                                                                            ?>
                                                                            <input type="hidden"
                                                                                   name="idAlmacenDestinoGaveta"
                                                                                   id="idAlmacenDestinoGaveta"
                                                                                   value="<? echo $idAlmacenDestinoGaveta ?>">
                                                                            <a href="<?= $pathRaiz ?>buscadores_maestros_restringidos/busqueda_almacen.php?AlmacenarId=1&NombreCampo=AlmacenDestinoGaveta"
                                                                               class="fancyboxAlmacenesDestinoGaveta"
                                                                               id="almacenesDestinoGaveta">
                                                                                <img border="0" align="absmiddle"
                                                                                     alt="<?= $auxiliar->traduce("Buscar Almacén", $administrador->ID_IDIOMA) ?>"
                                                                                     src="<?= $pathRaiz ?>imagenes/botones/listado.png"
                                                                                     name="Listado">
                                                                            </a>

                                                                            <span
                                                                                    id="desplegable_almacenes_destino_gaveta"
                                                                                    style="display: none;">
                                                                                <img
                                                                                        src="<?= $pathClases; ?>lib/ajax_script/img/esperando.gif"
                                                                                        width="15"
                                                                                        height="11"
                                                                                        alt="<?= $auxiliar->traduce("Buscando...", $administrador->ID_IDIOMA) ?>"/>
                                                                            </span>

                                                                            <div class="entry" align="left"
                                                                                 id="actualizador_almacenes_destino_gaveta"></div>
                                                                            <script type="text/javascript"
                                                                                    language="javascript">
                                                                                new Ajax.Autocompleter('txAlmacenDestinoGaveta', 'actualizador_almacenes_destino_gaveta', '<?=$pathRaiz?>buscadores_maestros_restringidos/resp_ajax_almacen.php?AlmacenarId=1&NombreCampo=AlmacenDestinoGaveta',
                                                                                    {
                                                                                        method: 'post',
                                                                                        indicator: 'desplegable_almacenes_destino_gaveta',
                                                                                        minChars: '2',
                                                                                        afterUpdateElement: function (textbox, valor) {
                                                                                            jQuery('#idAlmacenDestinoGaveta').val(jQuery(valor).children('a').attr('alt'));
                                                                                        }
                                                                                    }
                                                                                );
                                                                            </script>
                                                                        </td>
                                                                    </tr>

                                                                    <tr id="PasilloGaveta" style="display:none;">
                                                                        <td align="center" width="5%"><img
                                                                                    src="<? echo $pathRaiz ?>imagenes/diamante.gif"
                                                                                    width="7" height="7"></td>
                                                                        <td align="left"
                                                                            class="textoazul"><?= $auxiliar->traduce("Pasillo (2 dígitos)", $administrador->ID_IDIOMA) . ":"; ?>
                                                                        </td>
                                                                        <td class="textoazul"><? $TamanoText = "420px";
                                                                            $MaxLength                       = "2";

                                                                            $ClassText = "copyright ObligatorioRellenar";
                                                                            $html->TextBox("txPasilloGaveta", $txPasilloGaveta);
                                                                            ?>
                                                                        </td>
                                                                    </tr>

                                                                    <tr id="ProfundidadGaveta" style="display:none;">
                                                                        <td align="center" width="5%"><img
                                                                                    src="<? echo $pathRaiz ?>imagenes/diamante.gif"
                                                                                    width="7" height="7"></td>
                                                                        <td align="left"
                                                                            class="textoazul"><?= $auxiliar->traduce("Profundidad (2 dígitos)", $administrador->ID_IDIOMA) . ":"; ?>
                                                                        </td>
                                                                        <td class="textoazul"><? $TamanoText = "420px";
                                                                            $MaxLength                       = "2";

                                                                            $ClassText = "copyright ObligatorioRellenar";
                                                                            $html->TextBox("txProfundidadGaveta", $txProfundidadGaveta);
                                                                            ?>
                                                                        </td>
                                                                    </tr>
                                                                    <tr id="TipoSector"
                                                                        style="display:none;">
                                                                        <td align="center" width="5%"><img
                                                                                    src="<? echo $pathRaiz ?>imagenes/diamante.gif"
                                                                                    width="7" height="7"></td>
                                                                        <td align="left" class="textoazul"
                                                                            width="35%"><?= $auxiliar->traduce("Tipo sector", $administrador->ID_IDIOMA) . ":"; ?>
                                                                        </td>
                                                                        <td class="textoazul" width="60%">
                                                                            <?
                                                                            $TamanoText = "400px";
                                                                            $ClassText  = "copyright";
                                                                            $MaxLength  = "50";
                                                                            $idTextBox  = 'txTipoSector';
                                                                            $jscript    = "onchange=\"document.FormSelect.idTipoSector.value=''\"";
                                                                            $html->TextBox("txTipoSector", $txTipoSector);
                                                                            unset($jscript);
                                                                            unset($idTextBox);
                                                                            ?>
                                                                            <input type="hidden" name="idTipoSector"
                                                                                   id="idTipoSector"
                                                                                   value="<?= $idTipoSector ?>">
                                                                            <a href="<?= $pathRaiz ?>buscadores_maestros/busqueda_tipo_sector.php?AlmacenarId=1"
                                                                               class="fancyboxTipoSector"
                                                                               id="tipoSector">
                                                                                <img border="0" align="absmiddle"
                                                                                     alt="<?= $auxiliar->traduce("Buscar Tipo Sector", $administrador->ID_IDIOMA) ?>"
                                                                                     src="<?= $pathRaiz ?>imagenes/botones/listado.png"
                                                                                     name="Listado">
                                                                            </a>
                                                                            <span id="desplegable_tipo_sector"
                                                                                  style="display: none;">
                                                                                <img
                                                                                        src="<?= $pathClases; ?>lib/ajax_script/img/esperando.gif"
                                                                                        width="15"
                                                                                        height="11"
                                                                                        alt="<?= $auxiliar->traduce("Buscando...", $administrador->ID_IDIOMA) ?>"/>
                                                                            </span>

                                                                            <div class="entry" align="left"
                                                                                 id="actualizador_tipo_sector"></div>
                                                                            <script type="text/javascript"
                                                                                    language="javascript">
                                                                                new Ajax.Autocompleter('txTipoSector', 'actualizador_tipo_sector', '<?=$pathRaiz?>buscadores_maestros/resp_ajax_tipo_sector.php?AlmacenarId=1',
                                                                                    {
                                                                                        method: 'post',
                                                                                        indicator: 'desplegable_tipoSector',
                                                                                        minChars: '2',
                                                                                        afterUpdateElement: function (textbox, valor) {
                                                                                            jQuery('#idTipoSector').val(jQuery(valor).children('a').attr('alt'));
                                                                                        }
                                                                                    }
                                                                                );
                                                                            </script>
                                                                        </td>
                                                                    </tr>
                                                                    <tr id="CantidadPanelesSector"
                                                                        style="display:none;">
                                                                        <td align="center" width="5%"></td>
                                                                        <td align="left"
                                                                            class="textoazul"><?= $auxiliar->traduce("Cantidad paneles", $administrador->ID_IDIOMA) . ":"; ?>
                                                                        </td>
                                                                        <td class="textoazul"><?
                                                                            $idTextBox  = 'txCantidadPanelesSector';
                                                                            $TamanoText = "420px";
                                                                            $MaxLength  = "10";

                                                                            $ClassText = "copyright";
                                                                            $readonly  = "disabled";
                                                                            $html->TextBox("txCantidadPanelesSector", $txCantidadPanelesSector);
                                                                            unset($readonly);
                                                                            unset($idTextBox);
                                                                            ?>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td align="center"></td>
                                                                        <td align="left"
                                                                            class="textoazul"><?= $auxiliar->traduce("Descripción", $administrador->ID_IDIOMA) . ":"; ?>
                                                                        </td>
                                                                        <td class="textoazul"><textarea
                                                                                    name="txDescripcion" rows="2"
                                                                                    style="width:420px; resize:none;"
                                                                                    class="copyright"><?= $txDescripcion ?></textarea>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td align="center" width="5%">&nbsp;</td>
                                                                        <td align="left" class="textoazul"
                                                                            width="35%"><?= $auxiliar->traduce("Autostore", $administrador->ID_IDIOMA) . ":"; ?>
                                                                        </td>
                                                                        <td class="textoazul" width="60%">
                                                                            <?
                                                                            $Estilo = 'check_estilo';
                                                                            $html->Option("chAutostore", "Check", "1", $chAutostore);
                                                                            ?>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td align="center" width="5%">&nbsp;</td>
                                                                        <td align="left" class="textoazul"
                                                                            width="35%"><?= $auxiliar->traduce("Baja", $administrador->ID_IDIOMA) . ":"; ?>
                                                                        </td>
                                                                        <td class="textoazul" width="60%">
                                                                            <?
                                                                            $Estilo = 'check_estilo';
                                                                            $html->Option("chBaja", "Check", "1", $row->BAJA);
                                                                            ?>
                                                                        </td>
                                                                    </tr>
                                                                </table>

                                                            </td>
                                                            <td width="5" bgcolor="#D9E3EC" class="lineaderecha">
                                                                &nbsp;
                                                            </td>
                                                        </tr>
                                                        <tr bgcolor="#D9E3EC">
                                                            <td height="10" colspan="3" bgcolor="#D9E3EC"
                                                                class="lineabajodereizq"><img
                                                                        src="<? echo $pathRaiz ?>imagenes/transparente.gif"
                                                                        width="10" height="10"></td>
                                                        </tr>
                                                    </table>
                                                     
                                                </td>
                                                <td width="20" align="center" valign="middle" class="lineaizquierda">
                                                    &nbsp;
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="left" valign="top" bgcolor="#B3C7DA" class="lineabajo">
                                        <table width="100%" border="0" cellspacing="0" cellpadding="0"
                                               bgcolor="#D9E3EC">
                                            <tr height="25">
                                                <td class="lineabajo" width="50%" align="left"><span class="textoazul">
                                                        &nbsp;<a
                                                                href="index.php?recordar_busqueda=1<?= ($pantallaSolar == 1 ? "&pantallaSolar=1" : ($pantallaConstruccion == 1 ? "&pantallaConstruccion=1" : "")); ?>"
                                                                class="senaladoazul">
                                                            &nbsp;&nbsp;&nbsp;&nbsp;<?= $auxiliar->traduce("Volver", $administrador->ID_IDIOMA) ?>
                                                            &nbsp;&nbsp;&nbsp;&nbsp;</a></span></td>
                                                <td align="right" class="lineabajo"><span class="textoazul">

                                                        &nbsp;<a href="#" class="senalado6"
                                                                 onClick="this.disabled=true;jQuery('#txAlmacen').attr('autocomplete','on');jQuery('#idAlmacen').attr('autocomplete','on');	jQuery('#txAlmacenDestinoGaveta').attr('autocomplete','on');jQuery('#idAlmacenDestinoGaveta').attr('autocomplete','on');document.FormSelect.submit();return false">
                                                            &nbsp;&nbsp;&nbsp;&nbsp;<?= $auxiliar->traduce("Grabar", $administrador->ID_IDIOMA) ?>
                                                            &nbsp;&nbsp;&nbsp;&nbsp;</a>&nbsp;</span></td>
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
