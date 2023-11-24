<?

require_once $pathClases . "lib/auxiliar.php";
require_once $pathClases . "lib/orden_transporte.php";

$orden_transporte = new orden_transporte();

//SI EL MENU SE DEBE MOSTRAR REDUCIDO
$sql_where_menu = "";
if (defined('MENU_REDUCIDO')):
    if ((MENU_REDUCIDO == 1) && (isset($ZonaTablaPadre))):
        //ARRAY VACIO
        $sql_where_menu = " AND ZONA_TABLA = '" . $ZonaTablaPadre . "' ";
    endif;
endif;

//MENU DEL PADRE
$sql_menu       = "SELECT ID_MENU,NOMBRE,COLUMNA_PERFIL,ZONA_TABLA,ORDEN_ALFABETICO FROM MENU ";
$sql_menu       .= "WHERE NIVEL_MENU='0' AND MOSTRAR_EN_MENU='1' $sql_where_menu ";
$sql_menu       .= "ORDER BY ID_PADRE,ORDEN";
$resultado_menu = $bd->ExecSQL($sql_menu);    //echo $sql_menu;

//CARPETA IMAGENES
$carpeta_imagenes = "imagenes";

//SOLO RECORREMOS TABLA SI NO ESTÁ VACÍA (EVITAMOS BUCLE INFINITO)
$arr_menu = array();
if ($bd->NumRegs($resultado_menu) > 0):
    //RECORREMOS EL MENU Y VAMOS GUARDANDO
    while ($fila_menu = $bd->SigReg($resultado_menu)):

        //COMPROBAMOS SI TIENE PERMISO
        if ($administrador->Hayar_Permiso_Perfil($fila_menu->COLUMNA_PERFIL) >= 1):

            //GUARDAMOS EL REGISTRO
            $arr_menu[$fila_menu->ID_MENU]['Menu'] = $fila_menu;

            //ARRAY PARA GUARDAR SUBMENU
            $arr_subMenu = array();

            //BUSCAMOS SUBMENU
            if ($fila_menu->ORDEN_ALFABETICO == "1"):
                $sql_submenu = "SELECT ID_MENU,NOMBRE,LINK,COLUMNA_PERFIL,ZONA_TABLA,MOSTRAR_EN_MENU, ENTORNO_CODEIGNITER
												FROM MENU M INNER JOIN DICCIONARIO D ON D.ID_DICCIONARIO =M.ID_DICCIONARIO
												WHERE NIVEL_MENU='1' AND ID_PADRE='" . $fila_menu->ID_MENU . "'
												ORDER BY " . ($administrador->ID_IDIOMA == "ESP" ? 'D.ESP' : 'D.ENG');
            else:
                $sql_submenu = "SELECT ID_MENU,NOMBRE,LINK,COLUMNA_PERFIL,ZONA_TABLA,MOSTRAR_EN_MENU, ENTORNO_CODEIGNITER
                                                FROM MENU
												WHERE ID_PADRE='" . $fila_menu->ID_MENU . "' AND NIVEL_MENU='1'
												ORDER BY ORDEN";
            endif;
            $resultado_submenu = $bd->ExecSQL($sql_submenu);

            //RECORREMOS EL SUBMENU Y VAMOS GUARDANDO
            while ($fila_submenu = $bd->SigReg($resultado_submenu)):

                //GUARDAMOS LA ROW
                $arr_subMenu[$fila_submenu->ID_MENU]['SubMenu'] = $fila_submenu;

                //COMPROBAMOS PERMISOS Y VISIBILIDAD
                if (($fila_submenu->MOSTRAR_EN_MENU == 1) && ($administrador->Hayar_Permiso_Perfil($fila_submenu->COLUMNA_PERFIL) >= 1)):

                    //SI ES UN SUBMENU
                    if ($fila_submenu->LINK == "SUBMENU"):

                        //ARRAY PARA GUARDAR SUBMENU NIVEL 2
                        $arr_subMenu2 = array();

                        //BUSCAMOS HIJOS DEL SUBMENU
                        $sql_submenu2       = "SELECT NOMBRE,LINK,COLUMNA_PERFIL,ZONA_TABLA,MOSTRAR_EN_MENU,ENTORNO_CODEIGNITER,ID_MENU
                                            FROM MENU
                                            WHERE ID_PADRE='" . $fila_submenu->ID_MENU . "' AND NIVEL_MENU='3'
                                            ORDER BY ORDEN";
                        $resultado_submenu2 = $bd->ExecSQL($sql_submenu2);


                        //RECORREMOS EL SUBMENU Y VAMOS GUARDANDO
                        while ($fila_sub_submenu = $bd->SigReg($resultado_submenu2)):

                            //GUARDAMOS DATOS
                            $arr_subMenu2[$fila_sub_submenu->ID_MENU]['SubMenu2'] = $fila_sub_submenu;

                            //COMPROBAMOS PERMISOS Y VISIBILIDAD
                            if (($fila_sub_submenu->MOSTRAR_EN_MENU == 1) && ($administrador->Hayar_Permiso_Perfil($fila_sub_submenu->COLUMNA_PERFIL) >= 1)):

                                //SI ES UN SUBSUBMENU
                                if ($fila_sub_submenu->LINK == "SUBSUBMENU"):
                                    //ARRAY PARA GUARDAR SUBMENU NIVEL 3
                                    $arr_subMenu3 = array();

                                    //BUSCAMOS HIJOS DEL SUBMENU
                                    $sql_submenu3       = "SELECT NOMBRE,LINK,COLUMNA_PERFIL,ZONA_TABLA,MOSTRAR_EN_MENU,ENTORNO_CODEIGNITER,ID_MENU
                                            FROM MENU
                                            WHERE ID_PADRE='" . $fila_sub_submenu->ID_MENU . "' AND NIVEL_MENU='3' AND LINK <> 'SUBSUBMENU'
                                            ORDER BY ORDEN";
                                    $resultado_submenu3 = $bd->ExecSQL($sql_submenu3);

                                    //RECORREMOS EL SUBMENU Y VAMOS GUARDANDO
                                    while ($fila_sub_sub_submenu = $bd->SigReg($resultado_submenu3)):

                                        //GUARDAMOS DATOS
                                        $arr_subMenu3[$fila_sub_sub_submenu->ID_MENU]['SubMenu3'] = $fila_sub_sub_submenu;

                                        //COMPROBAMOS PERMISOS Y VISIBILIDAD
                                        if (($fila_sub_sub_submenu->MOSTRAR_EN_MENU == 1) && ($administrador->Hayar_Permiso_Perfil($fila_sub_sub_submenu->COLUMNA_PERFIL) >= 1)):
                                            // GUARDAMOS QUE SE MUESTRE
                                            $arr_subMenu3[$fila_sub_sub_submenu->ID_MENU]['Mostrar'] = 1;
                                        else:
                                            // GUARDAMOS QUE NO SE MUESTRE
                                            $arr_subMenu3[$fila_sub_sub_submenu->ID_MENU]['Mostrar'] = 0;
                                        endif;
                                    endwhile;

                                    //GUARDAMOS SUBMENU 2
                                    $arr_subMenu2[$fila_sub_submenu->ID_MENU]['SubMenus3'] = $arr_subMenu3;
                                endif;

                                //GUARDAMOS QUE SE MUESTRE
                                $arr_subMenu2[$fila_sub_submenu->ID_MENU]['Mostrar'] = 1;
                            else:
                                //GUARDAMOS QUE NO SE MUESTRE
                                $arr_subMenu2[$fila_sub_submenu->ID_MENU]['Mostrar'] = 0;
                            endif;
                        endwhile;

                        //GUARDAMOS SUBMENU 2
                        $arr_subMenu[$fila_submenu->ID_MENU]['SubMenus2'] = $arr_subMenu2;

                        //COMPRUEBO SI TIENE SUBMENUS VISIBLES
                        $sql_nietos       = "SELECT ID_MENU FROM MENU WHERE NIVEL_MENU='3' AND ID_PADRE='" . $fila_submenu->ID_MENU . "' AND LINK='SUBSUBMENU' AND MOSTRAR_EN_MENU='1'";
                        $resultado_nietos = $bd->ExecSQL($sql_nietos);
                        $subsubmenus      = $bd->NumRegs($resultado_nietos);

                        //GUARDAMOS LOS DATOS
                        $arr_subMenu[$fila_submenu->ID_MENU]['num_submenus_nivel4'] = $subsubmenus;
                    endif;//FIN SI ES SUBMENU 2

                    //GUARDAMOS QUE SE MUESTRE
                    $arr_subMenu[$fila_submenu->ID_MENU]['Mostrar'] = 1;
                else:
                    //GUARDAMOS QUE NO SE MUESTRE
                    $arr_subMenu[$fila_submenu->ID_MENU]['Mostrar'] = 0;

                endif;//FIN SUBMENU PERMISOS


            endwhile;//FIN RECORRER SUBMENU

            //ASIGNAMOS SUBMENUS
            $arr_menu[$fila_menu->ID_MENU]['SubMenus'] = $arr_subMenu;

            //COMPRUEBO SI TIENE SUBMENUS VISIBLES
            $sql_nietos       = "SELECT ID_MENU FROM MENU WHERE NIVEL_MENU='1' AND ID_PADRE='" . $fila_menu->ID_MENU . "' AND LINK='SUBMENU' AND MOSTRAR_EN_MENU='1'";
            $resultado_nietos = $bd->ExecSQL($sql_nietos);
            $submenus         = $bd->NumRegs($resultado_nietos);

            //GUARDAMOS LOS DATOS
            $arr_menu[$fila_menu->ID_MENU]['num_submenus_nivel3'] = $submenus;


        endif;//FIN SI TIENE PERMISO
    endwhile;
endif;

?>
<!-- BUSQUEDA AJAX -->
<script src="<?= $pathClases; ?>lib/ajax_script/lib/prototype.js" type="text/javascript"></script>
<script src="<?= $pathClases; ?>lib/ajax_script/src/scriptaculous.js" type="text/javascript"></script>
<link rel="stylesheet" href="<?= $pathClases; ?>lib/ajax_script/style_ajax.css" type="text/css"/>
<!-- FIN BUSQUEDA AJAX -->
<td id="tdMenuOculto" valign="top" width="5" bgcolor="#982a29" rowspan="2" align="center"
    class="linearribaderecha" <?= $_SESSION['estado_menu'] == 1 ? 'style="display:none"' : '' ?> >
    <div class="blanco">&nbsp;<br/>&nbsp;<br/>&nbsp;<br/>&nbsp;<br/>&nbsp;<br/>&nbsp;<br/>&nbsp;<br/>M&nbsp;<br/>E&nbsp;<br/>N&nbsp;<br/>U&nbsp;<br/>
    </div>
</td>
<td id="tdMenuVisible" width="184" rowspan="2" align="center"
    valign="top" <?= $_SESSION['estado_menu'] == 0 ? 'style="display:none"' : '' ?>>
    <table width="184" height="74" border="0" cellpadding="0" cellspacing="0" bgcolor="#B3C7DA">
        <tr>
            <td height="21" align="center" valign="top" class="linearribaderecha">
                <table width="184" height="108" border="0" cellpadding="0" cellspacing="0"
                       style="background-size: cover;"
                       background="<? echo $pathRaiz ?><? echo $carpeta_imagenes ?>/fondo_administrador.gif">
                    <tr>
                        <td height="27" colspan="3" align="center" valign="center"
                            class="nombre_perfil"><?= $administrador->NombrePerfilAdministrador($administrador->ID_ADMINISTRADOR_PERFIL); ?></td>
                    </tr>
                    <tr>
                        <td width="13" align="center" class="proveedor">
                            <a href="<? echo $pathRaiz ?>administracion/datos_usuario/index.php"
                               title="<?= $auxiliar->traduce("Modificar mis datos", $administrador->ID_IDIOMA); ?>"
                               style="border: none;"><img src="<? echo $pathRaiz ?>imagenes/user.png"
                                                          style="padding: 15px 10px 0px 5px;border: none;"></a>
                        </td>
                        <td width="143" align="center" valign="top" class="proveedor"
                            style="text-align: center;"><? echo '<br>' . $administrador->NombreAdministrador($administrador->ID_ADMINISTRADOR) ?> </td>
                        <td width="13" align="center" valign="top" class="proveedor">&nbsp;</td>
                    </tr>
                    <tr>
                        <td height="5" colspan="3"><img src="<? echo $pathRaiz ?>imagenes/transparente.gif" width="10"
                                                        height="5"></td>
                    </tr>
                    <tr>
                        <td colspan="3" align="center" valign="top" height="35"><a
                                href="<? echo $pathRaiz ?>desconectar.php" class="senaladoazul">
                                &nbsp;&nbsp;&nbsp;&nbsp;<?= $auxiliar->traduce("Desconectar", $administrador->ID_IDIOMA) ?>
                                &nbsp;&nbsp;&nbsp;&nbsp;</a></td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td height="25" align="center" valign="top" class="lineaderecha">
                <script type="text/javascript" language="javascript">
                    function ModificarCheck() {
                        marcado = (jQuery('#chMenuSiempreVisible').is(':checked') ? '1' : '0');
                        jQuery.ajax({
                            cache: false,
                            url: '<?=$pathRaiz?>guardar_estado_menu.php?chMenuSiempreVisible=' + marcado
                        });
                    }
                </script>
                <? //var_dump($_SESSION['estado_menu']);?>
                <div class="textoazul"><?= $auxiliar->traduce("Menú siempre visible", $administrador->ID_IDIOMA) ?>:
                    <input type="checkbox" id="chMenuSiempreVisible"
                           name="chMenuSiempreVisible" <?= $_SESSION['estado_menu'] == '1' ? "checked='checked'" : ''; ?>
                           class="chech_estilo" onclick="ModificarCheck();"/>
                </div>

            </td>
        </tr>
        <tr>
            <td align="right" valign="top" class="lineaderecha">
                <table width="100%" border="0" cellpadding="0" cellspacing="0">
                    <tr>
                        <td height="23" colspan="2" align="right">
                            <table width="100%" height="23" border="0" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td width="7%"></td>
                                    <td height="25" width="93%" align="left" valign="top">
                                        <input name="txMenuIzq" id="txMenuIzq" maxlength="50" style="width: 130px"
                                               class="copyright" value=""
                                               onchange="document.FormSelect.txMenuIzq.value=''" autocomplete="off"
                                               type="text">
                                        <img
                                            src="<?= $pathRaiz ?>imagenes/lupa.png"
                                            align="bottom"
                                            width="16px"
                                            height="16px"
                                            name="lupaTablaIzq"
                                            border="0"
                                            id="lupaTablaIzq"/>
                                        <?
                                        //                $TamanoText = '140px';
                                        //                $ClassText = "copyright";
                                        //                $MaxLength = "50";
                                        //                $idTextBox = 'txMenuIzq';
                                        //                $jscript = "onchange=\"document.FormSelect.txMenuIzq.value=''\"";
                                        //                $html->TextBox("txMenuIzq", $txMenuIzq);
                                        //                unset($readonly);
                                        //                unset($jscript);
                                        //                unset($idTextBox);
                                        ?>

                                        <span id="desplegable_menu"
                                              style="display: none;"> <img
                                                src="<?= $pathClases; ?>lib/ajax_script/img/esperando.gif"
                                                width="10" height="11"
                                                alt="<?= $auxiliar->traduce("Buscando...", $administrador->ID_IDIOMA) ?>"/> </span>
                                        <span id="enlace_seleccion_menu"
                                              style="display: none;"> <a id="enlace_seleccionado" href=""></a> </span>

                                        <div class="entry" align="left"
                                             id="actualizador_menu"></div>
                                        <script type="text/javascript"
                                                language="JavaScript">
                                            new Ajax.Autocompleter('txMenuIzq', 'actualizador_menu', '<?=$pathRaiz?>buscadores_maestros/resp_ajax_menu_izq.php',
                                                {
                                                    method: 'post',
                                                    indicator: 'desplegable_menu',
                                                    minChars: '2',
                                                    afterUpdateElement: function (textbox, valor) {
                                                        jQuery('#enlace_seleccionado').attr('href', jQuery(valor).children('a').attr('alt'));
                                                        document.getElementById('enlace_seleccionado').click();
//                                jQuery('#enlace_seleccionado').trigger('click');
                                                    }
                                                }
                                            );
                                        </script>

                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td align="right" valign="top" class="lineaderecha">
                <table width="100%" border="0" cellpadding="0" cellspacing="0">
                    <?
                    //SOLO RECORREMOS TABLA SI NO ESTÁ VACÍA (EVITAMOS BUCLE INFINITO)
                    if (count( (array)$arr_menu) > 0):
                        //MOSTRAR PADRES DEL MENÚ
                        foreach ($arr_menu as $id_menu => $arr_datos_menu):

                            //DATOS DEL MENU
                            $fila_menu = $arr_datos_menu['Menu'];

                            //VARIABLE PARA VISUALIZAR EL MENÚ
                            $mostrarPantalla = true;

                            if ($administrador->esProveedor()):
                                if (($fila_menu->ZONA_TABLA == "Construccion") || ($fila_menu->ZONA_TABLA == "TransporteConstruccion")):

                                    //COMPROBAMOS SI EL PERFIL DEL USUARIO ES DE PROVEEDOR DE CONSTRUCCIÓN
                                    $NotificaErrorPorEmail = "No";

                                    //SQL
                                    $sqlPerfil = "SELECT ES_PROVEEDOR_CONSTRUCCION FROM ADMINISTRADOR_PERFIL WHERE ID_ADMINISTRADOR_PERFIL = $administrador->ID_ADMINISTRADOR_PERFIL";

                                    $resultadoPerfil = $bd->ExecSQL($sqlPerfil, "No");

                                    // INFORMO DEL ERROR AL RESPONSABLE INFORMATICO
                                    if ($resultadoPerfil == false || $bd->NumRegs($resultadoPerfil) == 0):
                                        $rowPerfil = false;
                                    else:
                                        $rowPerfil = $bd->SigReg($resultadoPerfil);
                                    endif;

                                    if ($rowPerfil->ES_PROVEEDOR_CONSTRUCCION == 1):
                                        if ($fila_menu->ZONA_TABLA == "Construccion"):
                                            //SI ES PROVEEDOR DE CONSTRUCCION, OBTENEMOS SU INFORMACIÓN
                                            $NotificaErrorPorEmail = "No";
                                            $rowProveedor          = $bd->VerReg("PROVEEDOR", "ID_PROVEEDOR", $administrador->ID_PROVEEDOR, "No");

                                            if ($rowProveedor->REFERENCIA != "V-EW01"):

                                                //SI EL PROVEEDOR NO ES NORDEX, ENTONCES NO LE PERMITIMOS VER LAS PANTALLAS
                                                $mostrarPantalla = false;

                                            endif;
                                        else:
                                            $mostrarPantalla = false;
                                        endif;
                                    endif;
                                endif;
                            endif;

                            //NUMERO DE SUBMENUS CON SUBSUBMENUS
                            $submenus = $arr_datos_menu['num_submenus_nivel3'];

                            if ($mostrarPantalla):
                                ?>
                                <tr>
                                    <td height="23" colspan="2" align="right">
                                        <table width="100%" height="23" border="0" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td width="1%"></td>
                                                <td width="99%" align="left" valign="top">
                                                    <!--DESPLEGABLE AUTOMÁTICO-->
                                                    <a href="#" class='switch'
                                                       onclick="desplegarHijos('<?= $fila_menu->ID_MENU ?>'); return false;">
                                                        <SPAN
                                                            class=titulo2><?= strtr( (string)strtoupper( (string)$auxiliar->traduce($fila_menu->NOMBRE, $administrador->ID_IDIOMA)), "àèìòùáéíóúçñäëïöü", "ÀÈÌÒÙÁÉÍÓÚÇÑÄËÏÖÜ") ?></SPAN>
                                                        <? if ($administrador->Hayar_Permiso_Perfil('ADM_CODIFICACION_OPERACIONES_ACCIONES') > 0 && ($fila_menu->ZONA_TABLA == "Codificacion") && ($bd->NumRegs($resultAccionesCodificacion) > 0)): ?>
                                                            <a style="float: right;">
                                                                <img id="img_warning_acciones"
                                                                     src="<? echo $pathRaiz ?>imagenes/warning.png"
                                                                     style="margin-right: 5px;margin-top: 2px;width:16px;float: right;"
                                                                     class="parpadeante"
                                                                     title="<?= $auxiliar->traduce("Existen acciones pendientes de resolver", $administrador->ID_IDIOMA) ?>">
                                                            </a>
                                                        <?endif;?>
                                                    </a>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <?
                                //DESPLEGABLE AUTOMÁTICO
                                if (count( (array)$arr_datos_menu['SubMenus']) > 0):
                                    ?>
                                    <tr class="<?= $fila_menu->ID_MENU ?>">
                                        <td colspan="2"
                                            class="hijos" <?= ($fila_menu->ZONA_TABLA != $ZonaTablaPadre && !$administrador->esProveedor()) ? 'style="display:none;"' : '' ?>>
                                            <table cellpadding="0" cellspacing="0" width="100%">
                                                <?

                                                //MOSTRAR HIJOS DEL MENÚ DADO EL ID_PADRE
                                                foreach ($arr_datos_menu['SubMenus'] as $idSubMenu => $arr_datos_submenu):

                                                    //SI LO TIENE QUE VER
                                                    if ($arr_datos_submenu['Mostrar'] == 1):

                                                        //DATOS DEL SUBMENU
                                                        $fila_submenu = $arr_datos_submenu['SubMenu'];

                                                        if ($fila_submenu->LINK == "SUBMENU"):
                                                            ?>
                                                            <tr>
                                                                <td height="23" align="right">
                                                                    <table width="100%" height="23" border="0"
                                                                           cellpadding="0"
                                                                           cellspacing="0">
                                                                        <tr>
                                                                            <td width="6%"></td>
                                                                            <td width="94%" align="left" valign="top">
                                                                                <!--DESPLEGABLE AUTOMÁTICO-->
                                                                                <a href="#" class='switch'
                                                                                   onclick="desplegarSubHijos('<?= $fila_submenu->ID_MENU ?>'); return false;">
                                                                        <SPAN
                                                                            class=subtitulo2><?= strtr( (string)strtoupper( (string)$auxiliar->traduce($fila_submenu->NOMBRE, $administrador->ID_IDIOMA)), "àèìòùáéíóúçñäëïöü", "ÀÈÌÒÙÁÉÍÓÚÇÑÄËÏÖÜ") ?></SPAN>
                                                                                    <? if ($administrador->Hayar_Permiso_Perfil('ADM_CODIFICACION_OPERACIONES_ACCIONES') > 0 && ($fila_submenu->ZONA_TABLA == "CodificacionOperaciones") && ($bd->NumRegs($resultAccionesCodificacion) > 0)): ?>
                                                                                        <a style="float: right;">
                                                                                            <img id="img_warning_acciones"
                                                                                                 src="<? echo $pathRaiz ?>imagenes/warning.png"
                                                                                                 style="margin-right: 5px;margin-top: 2px;width:16px;float: right;"
                                                                                                 class="parpadeante"
                                                                                                 title="<?= $auxiliar->traduce("Existen acciones pendientes de resolver", $administrador->ID_IDIOMA) ?>">
                                                                                        </a>
                                                                                    <?endif;?>
                                                                                </a>
                                                                            </td>
                                                                        </tr>
                                                                    </table>
                                                                </td>
                                                            </tr>
                                                            <?

                                                            //DESPLEGABLE AUTOMÁTICO
                                                            if (count( (array)$arr_datos_submenu['SubMenus2']) > 0):
                                                                ?>
                                                                <tr class="<?= $fila_submenu->ID_MENU ?>">
                                                                <td  class="subhijos" <?= ($fila_submenu->ZONA_TABLA != $ZonaSubTablaPadre && !$administrador->esProveedor()) ? 'style="display:none;"' : '' ?>>

                                                                <table cellpadding="0" cellspacing="0" width="100%">
                                                            <?
                                                            endif;//MOSTRAR HIJOS DEL MENÚ DADO EL ID_PADRE
                                                            foreach ($arr_datos_submenu['SubMenus2'] as $idSubMenu2 => $arr_datos_sub_submenu):

                                                                if ($arr_datos_sub_submenu['Mostrar'] == 1):

                                                                    //DATOS DEL SUBMENU
                                                                    $fila_sub_submenu = $arr_datos_sub_submenu['SubMenu2'];

                                                                    if ($fila_sub_submenu->LINK == 'SUBSUBMENU'):

                                                                        //COMPROBAMOS SI PODEMOS MOSTRAR EL MENÚ 'TRANSPORTES GC'
                                                                        if ($fila_sub_submenu->ZONA_TABLA == "ProveedoresSubSubmenuTransportesGC"):
                                                                            if ($administrador->esProveedor()):

                                                                                //OBTENEMOS EL PERFIL

                                                                                //SQL
                                                                                $sqlPerfil = "SELECT ES_PROVEEDOR_CONSTRUCCION, ES_FORWARDER_CONSTRUCCION, ES_AGENTE_ADUANAL_CONSTRUCCION FROM ADMINISTRADOR_PERFIL WHERE ID_ADMINISTRADOR_PERFIL = $administrador->ID_ADMINISTRADOR_PERFIL";

                                                                                $resultadoPerfil = $bd->ExecSQL($sqlPerfil, "No");

                                                                                // INFORMO DEL ERROR AL RESPONSABLE INFORMATICO
                                                                                if ($resultadoPerfil == false || $bd->NumRegs($resultadoPerfil) == 0):
                                                                                    $rowPerfil = false;
                                                                                else:
                                                                                    $rowPerfil = $bd->SigReg($resultadoPerfil);
                                                                                endif;

                                                                                //SI ES PROVEEDOR OBTENEMOS LA INFORMACIÓN DE ÉSTE
                                                                                $NotificaErrorPorEmail = "No";
                                                                                $rowProveedor          = $bd->VerReg("PROVEEDOR", "ID_PROVEEDOR", $administrador->ID_PROVEEDOR, "No");

                                                                                //SOLAMENTE PODRÁ VISUALIZAR ESTE MENÚ EL PROVEEDOR NORDEX
                                                                                if ((($rowPerfil->ES_PROVEEDOR_CONSTRUCCION == 1) && ($rowProveedor->REFERENCIA == "V-EW01")) || ($rowPerfil->ES_FORWARDER_CONSTRUCCION == 1) || ($rowPerfil->ES_AGENTE_ADUANAL_CONSTRUCCION == 1)):
                                                                                    ?>
                                                                                    <tr>
                                                                                        <td height="23" align="right">
                                                                                            <table width="100%"
                                                                                                   height="23"
                                                                                                   border="0"
                                                                                                   cellpadding="0"
                                                                                                   cellspacing="0">
                                                                                                <tr>
                                                                                                    <td width="12%"></td>
                                                                                                    <td width="88%"
                                                                                                        align="left"
                                                                                                        valign="top">
                                                                                                        <!--DESPLEGABLE AUTOMÁTICO-->
                                                                                                        <a href="#"
                                                                                                           class='switch'
                                                                                                           onclick="desplegarSubSubHijos('<?= $fila_sub_submenu->ID_MENU ?>'); return false;">
                                                                                            <SPAN
                                                                                                class=subtitulo3><?= strtr( (string)strtoupper( (string)$auxiliar->traduce($fila_sub_submenu->NOMBRE, $administrador->ID_IDIOMA)), "àèìòùáéíóúçñäëïöü", "ÀÈÌÒÙÁÉÍÓÚÇÑÄËÏÖÜ") ?></SPAN>
                                                                                                        </a>
                                                                                                    </td>
                                                                                                </tr>
                                                                                            </table>
                                                                                        </td>
                                                                                    </tr>
                                                                                <?
                                                                                endif;
                                                                            else:
                                                                                ?>
                                                                                <tr>
                                                                                    <td height="23" align="right">
                                                                                        <table width="100%" height="23"
                                                                                               border="0"
                                                                                               cellpadding="0"
                                                                                               cellspacing="0">
                                                                                            <tr>
                                                                                                <td width="12%"></td>
                                                                                                <td width="88%"
                                                                                                    align="left"
                                                                                                    valign="top">
                                                                                                    <!--DESPLEGABLE AUTOMÁTICO-->
                                                                                                    <a href="#"
                                                                                                       class='switch'
                                                                                                       onclick="desplegarSubSubHijos('<?= $fila_sub_submenu->ID_MENU ?>'); return false;">
                                                                                            <SPAN
                                                                                                class=subtitulo3><?= strtr( (string)strtoupper( (string)$auxiliar->traduce($fila_sub_submenu->NOMBRE, $administrador->ID_IDIOMA)), "àèìòùáéíóúçñäëïöü", "ÀÈÌÒÙÁÉÍÓÚÇÑÄËÏÖÜ") ?></SPAN>
                                                                                                    </a>
                                                                                                </td>
                                                                                            </tr>
                                                                                        </table>
                                                                                    </td>
                                                                                </tr>
                                                                            <?
                                                                            endif;
                                                                        else:
                                                                            //SI NO ESTÁ EN TRANSPORTES GC, MOSTRAMOS EL MENÚ
                                                                            ?>
                                                                            <tr>
                                                                                <td height="23" align="right">
                                                                                    <table width="100%" height="23"
                                                                                           border="0"
                                                                                           cellpadding="0"
                                                                                           cellspacing="0">
                                                                                        <tr>
                                                                                            <td width="12%"></td>
                                                                                            <td width="88%" align="left"
                                                                                                valign="top">
                                                                                                <!--DESPLEGABLE AUTOMÁTICO-->
                                                                                                <a href="#"
                                                                                                   class='switch'
                                                                                                   onclick="desplegarSubSubHijos('<?= $fila_sub_submenu->ID_MENU ?>'); return false;">
                                                                                            <SPAN
                                                                                                class=subtitulo3><?= strtr( (string)strtoupper( (string)$auxiliar->traduce($fila_sub_submenu->NOMBRE, $administrador->ID_IDIOMA)), "àèìòùáéíóúçñäëïöü", "ÀÈÌÒÙÁÉÍÓÚÇÑÄËÏÖÜ") ?></SPAN>
                                                                                                </a>
                                                                                            </td>
                                                                                        </tr>
                                                                                    </table>
                                                                                </td>
                                                                            </tr>
                                                                        <?
                                                                        endif;

                                                                        //DESPLEGABLE AUTOMÁTICO
                                                                        if (count( (array)$arr_datos_sub_submenu['SubMenus3']) > 0):

                                                                            //COMPROBAMOS SI PODEMOS MOSTRAR EL MENÚ 'TRANSPORTES GC'
                                                                            if ($fila_sub_submenu->ZONA_TABLA == "ProveedoresSubSubmenuTransportesGC"):
                                                                                if ($administrador->esProveedor()):

                                                                                    //OBTENEMOS EL PERFIL
                                                                                    //SQL
                                                                                    $sqlPerfil = "SELECT ES_PROVEEDOR_CONSTRUCCION, ES_FORWARDER_CONSTRUCCION, ES_AGENTE_ADUANAL_CONSTRUCCION FROM ADMINISTRADOR_PERFIL WHERE ID_ADMINISTRADOR_PERFIL = $administrador->ID_ADMINISTRADOR_PERFIL";

                                                                                    $resultadoPerfil = $bd->ExecSQL($sqlPerfil, "No");

                                                                                    // INFORMO DEL ERROR AL RESPONSABLE INFORMATICO
                                                                                    if ($resultadoPerfil == false || $bd->NumRegs($resultadoPerfil) == 0):
                                                                                        $rowPerfil = false;
                                                                                    else:
                                                                                        $rowPerfil = $bd->SigReg($resultadoPerfil);
                                                                                    endif;

                                                                                    //SI ES PROVEEDOR OBTENEMOS LA INFORMACIÓN DE ÉSTE
                                                                                    $NotificaErrorPorEmail = "No";
                                                                                    $rowProveedor          = $bd->VerReg("PROVEEDOR", "ID_PROVEEDOR", $administrador->ID_PROVEEDOR, "No");

                                                                                    //SOLAMENTE PODRÁ VISUALIZAR ESTE MENÚ EL PROVEEDOR NORDEX
                                                                                    if ((($rowPerfil->ES_PROVEEDOR_CONSTRUCCION == 1) && ($rowProveedor->REFERENCIA == "V-EW01")) || ($rowPerfil->ES_FORWARDER_CONSTRUCCION == 1) || ($rowPerfil->ES_AGENTE_ADUANAL_CONSTRUCCION == 1)):

                                                                                        ?>
                                                                                        <tr class="<?= $fila_sub_submenu->ID_MENU ?>">
                                                                                            <td class="subsubhijos" <?= ($fila_sub_submenu->ZONA_TABLA != $ZonaSubSubTablaPadre && !$administrador->esProveedor()) ? 'style="display:none;"' : '' ?>>

                                                                                                <table cellpadding="0"
                                                                                                       cellspacing="0"
                                                                                                       width="100%">
                                                                                                    <?
                                                                                                    foreach ($arr_datos_sub_submenu['SubMenus3'] as $idSubMenu3 => $arr_datos_sub_sub_submenu):
                                                                                                        if ($arr_datos_sub_sub_submenu['Mostrar'] == 1):

                                                                                                            //DATOS DEL SUBMENU
                                                                                                            $fila_sub_sub_submenu = $arr_datos_sub_sub_submenu['SubMenu3'];

                                                                                                            if ($fila_sub_sub_submenu->ZONA_TABLA == "ProveedoresAvisosConstruccionProveedores"):

                                                                                                                $sqlAcciones = "SELECT OT.ID_ORDEN_TRANSPORTE, OTC.ID_PROVEEDOR, OTC.ID_PROVEEDOR_FORWARDER, OTC.ID_PROVEEDOR_AGENTE_ADUANAL, OT.ID_AGENCIA, OTA.TIPO_DESTINATARIO, OTAA.ID_ORDEN_TRANSPORTE_ACCION_AVISO
                                                                                                FROM ORDEN_TRANSPORTE_ACCION OTA
                                                                                                    INNER JOIN ORDEN_TRANSPORTE_ACCION_AVISO OTAA ON OTAA.ID_ORDEN_TRANSPORTE_ACCION = OTA.ID_ORDEN_TRANSPORTE_ACCION
                                                                                                    INNER JOIN ORDEN_TRANSPORTE OT ON OT.ID_ORDEN_TRANSPORTE = OTAA.ID_ORDEN_TRANSPORTE
                                                                                                    INNER JOIN ORDEN_TRANSPORTE_CONSTRUCCION OTC ON OT.ID_ORDEN_TRANSPORTE = OTC.ID_ORDEN_TRANSPORTE
                                                                                                WHERE 1 = 1 AND OTAA.ESTADO_ACCION = 'Pendiente' AND OT.BAJA = 0 AND OTAA.BAJA = 0 AND ( (TIMESTAMPDIFF(DAY, OTAA.FECHA_RELACIONADA, NOW()) >= OTA.DIAS_BANDERA_VERDE) OR (OTA.DIAS_BANDERA_VERDE = 0) ) AND OTA.TIPO_ACCION <> 'Aceptar Documentacion' AND ( IF ( (OTA.ESTADO_TRANSPORTE = 'Puerto Origen' OR OTA.ESTADO_TRANSPORTE = 'Transito Internacional') AND (OTA.NO_AFECTA_ESTADOS = 0), OT.CON_EMBARQUE_GC <> 1, 1)) AND (OTA.TIPO_ACCION = 'Modificar Documentacion' OR OTA.TIPO_ACCION = 'Revisar Borrador BL' OR OTA.TIPO_ACCION = 'Revisar BL Definitivo' OR OTA.TIPO_ACCION = 'Revisar CMR' OR OTA.TIPO_ACCION = 'Añadir Borrador BL' OR OTA.TIPO_ACCION = 'Añadir BL Definitivo' OR OTA.TIPO_ACCION = 'Añadir CMR' OR OTA.TIPO_ACCION = 'Actualizar ETA' OR OTA.TIPO_ACCION = 'Confirmar Salida Buque' OR OTA.TIPO_ACCION = 'Confirmar Llegada Buque' OR OTA.TIPO_ACCION = 'Generar Transbordo' OR OTA.TIPO_ACCION = 'Aceptar/Rechazar Borrador BL' OR OTA.TIPO_ACCION = 'Aceptar/Rechazar BL Definitivo' OR OTA.TIPO_ACCION = 'Aceptar/Rechazar Documentacion Proveedor')" . ($administrador->esProveedor() ? " AND (OT.ID_AGENCIA = $administrador->ID_PROVEEDOR OR OTC.ID_PROVEEDOR = $administrador->ID_PROVEEDOR OR OTC.ID_PROVEEDOR_FORWARDER = $administrador->ID_PROVEEDOR OR OTC.ID_PROVEEDOR_AGENTE_ADUANAL = $administrador->ID_PROVEEDOR)" : "");

                                                                                                                if ($administrador->esProveedor()):
                                                                                                                    if ($administrador->ID_PROVEEDOR != ""):

                                                                                                                        $resultAcciones   = $bd->ExecSQL($sqlAcciones);
                                                                                                                        $sqlRolesAsumidos = "";
                                                                                                                        $coma             = "";

                                                                                                                        while ($row = $bd->SigReg($resultAcciones)):

                                                                                                                            $rowRolesAsumidos = false;

                                                                                                                            if ($row->ID_PROVEEDOR == $administrador->ID_PROVEEDOR):

                                                                                                                                //OBTENEMOS LOS ROLES ASUMIDOS
                                                                                                                                $rowRolesAsumidos = $bd->VerRegRest("ORDEN_TRANSPORTE_ROLES_ASUMIDOS", "ROL = 'Proveedor' AND ID_ORDEN_TRANSPORTE = $row->ID_ORDEN_TRANSPORTE", "No");

                                                                                                                            elseif ($row->ID_PROVEEDOR_FORWARDER == $administrador->ID_PROVEEDOR):

                                                                                                                                //OBTENEMOS LOS ROLES ASUMIDOS
                                                                                                                                $rowRolesAsumidos = $bd->VerRegRest("ORDEN_TRANSPORTE_ROLES_ASUMIDOS", "ROL = 'Forwarder' AND ID_ORDEN_TRANSPORTE = $row->ID_ORDEN_TRANSPORTE", "No");

                                                                                                                            elseif ($row->ID_PROVEEDOR_AGENTE_ADUANAL == $administrador->ID_PROVEEDOR):

                                                                                                                                //OBTENEMOS LOS ROLES ASUMIDOS
                                                                                                                                $rowRolesAsumidos = $bd->VerRegRest("ORDEN_TRANSPORTE_ROLES_ASUMIDOS", "ROL = 'Agente Aduanal' AND ID_ORDEN_TRANSPORTE = $row->ID_ORDEN_TRANSPORTE", "No");

                                                                                                                            endif;

                                                                                                                            if (($row->TIPO_DESTINATARIO == "Proveedor del Material") && ($rowRolesAsumidos->PROVEEDOR_ASUMIDO == 1)):

                                                                                                                                $sqlRolesAsumidos .= $coma . $row->ID_ORDEN_TRANSPORTE_ACCION_AVISO;
                                                                                                                                $coma             = ",";

                                                                                                                            elseif (($row->TIPO_DESTINATARIO == "Forwarder") && ($rowRolesAsumidos->FORWARDER_ASUMIDO == 1)):

                                                                                                                                $sqlRolesAsumidos .= $coma . $row->ID_ORDEN_TRANSPORTE_ACCION_AVISO;
                                                                                                                                $coma             = ",";

                                                                                                                            elseif (($row->TIPO_DESTINATARIO == "Agente Aduanal") && ($rowRolesAsumidos->AGENTE_ADUANAL_ASUMIDO == 1)):

                                                                                                                                $sqlRolesAsumidos .= $coma . $row->ID_ORDEN_TRANSPORTE_ACCION_AVISO;
                                                                                                                                $coma             = ",";

                                                                                                                            elseif (($row->TIPO_DESTINATARIO == "Transportista Inland") && ($rowRolesAsumidos->TRANSPORTISTA_INLAND_ASUMIDO == 1)):

                                                                                                                                $sqlRolesAsumidos .= $coma . $row->ID_ORDEN_TRANSPORTE_ACCION_AVISO;
                                                                                                                                $coma             = ",";

                                                                                                                            endif;

                                                                                                                        endwhile;

                                                                                                                        $sqlAcciones .= ($sqlRolesAsumidos != "" ? " AND OTAA.ID_ORDEN_TRANSPORTE_ACCION_AVISO IN ($sqlRolesAsumidos)" : " AND FALSE ");

                                                                                                                    endif;
                                                                                                                endif;

                                                                                                                //EJECUTAMOS LA CONSULTA
                                                                                                                $resultAcciones = $bd->ExecSQL($sqlAcciones);

                                                                                                            endif;

                                                                                                            if (($fila_sub_sub_submenu->ZONA_TABLA == "ProveedoresConstruccionCronogramaProveedores") || ($fila_sub_sub_submenu->ZONA_TABLA == "ProveedoresAvisosConstruccionProveedores")):

                                                                                                                //SI ES EL CRONOGRAMA DE PROVEEDORES, ENTONCES COMPRUEBO SI TIENE ACCIONES PENDIENTES
                                                                                                                if ($administrador->esProveedor()):

                                                                                                                    //SI ES PROVEEDOR, COMPRUEBO SI PUEDE DELEGAR ALGUNA ACCIÓN
                                                                                                                    if ($fila_sub_sub_submenu->ZONA_TABLA == "ProveedoresConstruccionCronogramaProveedores"):
                                                                                                                        $sqlOTsAccionesPendientes = "SELECT OTAA.ID_ORDEN_TRANSPORTE, OTA.TIPO_ACCION, OTA.TIPO_DESTINATARIO, OTC.ID_PROVEEDOR, OTC.ID_PROVEEDOR_FORWARDER, OTC.ID_PROVEEDOR_AGENTE_ADUANAL, OT.ID_AGENCIA
                                                                                                                                FROM ORDEN_TRANSPORTE_ACCION OTA
                                                                                                                                    INNER JOIN ORDEN_TRANSPORTE_ACCION_AVISO OTAA ON OTAA.ID_ORDEN_TRANSPORTE_ACCION = OTA.ID_ORDEN_TRANSPORTE_ACCION
                                                                                                                                    INNER JOIN ORDEN_TRANSPORTE OT ON OT.ID_ORDEN_TRANSPORTE = OTAA.ID_ORDEN_TRANSPORTE
                                                                                                                                    INNER JOIN ORDEN_TRANSPORTE_CONSTRUCCION OTC ON OTC.ID_ORDEN_TRANSPORTE = OT.ID_ORDEN_TRANSPORTE
                                                                                                                                WHERE OTAA.ESTADO_ACCION = 'Pendiente' AND OTA.TIENE_ACCION_MASIVA = 1 AND OTA.TIPO_ACCION <> 'Aceptar Documentacion' AND (OTC.ID_PROVEEDOR = $administrador->ID_PROVEEDOR OR OTC.ID_PROVEEDOR_FORWARDER = $administrador->ID_PROVEEDOR OR OTC.ID_PROVEEDOR_AGENTE_ADUANAL = $administrador->ID_PROVEEDOR OR OT.ID_AGENCIA = $administrador->ID_PROVEEDOR) AND OTAA.BAJA = 0 AND OTA.BAJA = 0 AND OT.BAJA = 0";
                                                                                                                    else:
                                                                                                                        $sqlOTsAccionesPendientes = "SELECT OTAA.ID_ORDEN_TRANSPORTE, OTA.TIPO_ACCION, OTA.TIPO_DESTINATARIO, OTC.ID_PROVEEDOR, OTC.ID_PROVEEDOR_FORWARDER, OTC.ID_PROVEEDOR_AGENTE_ADUANAL, OT.ID_AGENCIA
                                                                                                                                FROM ORDEN_TRANSPORTE_ACCION OTA
                                                                                                                                    INNER JOIN ORDEN_TRANSPORTE_ACCION_AVISO OTAA ON OTAA.ID_ORDEN_TRANSPORTE_ACCION = OTA.ID_ORDEN_TRANSPORTE_ACCION
                                                                                                                                    INNER JOIN ORDEN_TRANSPORTE OT ON OT.ID_ORDEN_TRANSPORTE = OTAA.ID_ORDEN_TRANSPORTE
                                                                                                                                    INNER JOIN ORDEN_TRANSPORTE_CONSTRUCCION OTC ON OTC.ID_ORDEN_TRANSPORTE = OT.ID_ORDEN_TRANSPORTE
                                                                                                                                WHERE OTAA.ESTADO_ACCION = 'Pendiente' AND OTA.TIPO_ACCION <> 'Aceptar Documentacion' AND (OTC.ID_PROVEEDOR = $administrador->ID_PROVEEDOR OR OTC.ID_PROVEEDOR_FORWARDER = $administrador->ID_PROVEEDOR OR OTC.ID_PROVEEDOR_AGENTE_ADUANAL = $administrador->ID_PROVEEDOR OR OT.ID_AGENCIA = $administrador->ID_PROVEEDOR) AND OTAA.BAJA = 0 AND OTA.BAJA = 0 AND OT.BAJA = 0";
                                                                                                                    endif;

                                                                                                                    $resultOTsAccionesPendientes = $bd->ExecSQL($sqlOTsAccionesPendientes);
                                                                                                                    $arrOTsAccionesPendientes    = array();

                                                                                                                    while ($rowOTsAccionesPendientes = $bd->SigReg($resultOTsAccionesPendientes)):

                                                                                                                        if (($rowOTsAccionesPendientes->TIPO_DESTINATARIO == "Proveedor del Material") && !in_array($rowOTsAccionesPendientes->ID_ORDEN_TRANSPORTE, (array) $arrOTsAccionesPendientes)):

                                                                                                                            if ($rowOTsAccionesPendientes->ID_PROVEEDOR == $administrador->ID_PROVEEDOR):

                                                                                                                                //SI EL PROVEEDOR SE CORRESPONDE CON EL PROVEEDOR DEL MATERIAL
                                                                                                                                $arrOTsAccionesPendientes[] = $rowOTsAccionesPendientes->ID_ORDEN_TRANSPORTE;

                                                                                                                            endif;

                                                                                                                        elseif (($rowOTsAccionesPendientes->TIPO_DESTINATARIO == "Forwarder") && !in_array($rowOTsAccionesPendientes->ID_ORDEN_TRANSPORTE, (array) $arrOTsAccionesPendientes)):

                                                                                                                            if ($rowOTsAccionesPendientes->ID_PROVEEDOR_FORWARDER == $administrador->ID_PROVEEDOR):

                                                                                                                                //SI EL PROVEEDOR SE CORRESPONDE CON EL FORWARDER
                                                                                                                                $arrOTsAccionesPendientes[] = $rowOTsAccionesPendientes->ID_ORDEN_TRANSPORTE;

                                                                                                                            elseif ($rowOTsAccionesPendientes->ID_PROVEEDOR == $administrador->ID_PROVEEDOR):

                                                                                                                                //SI EL PROVEEDOR SE CORRESPONDE CON EL PROVEEDOR DEL MATERIAL, OBTENEMOS LOS ROLES QUE PUEDE ASUMIR
                                                                                                                                $rowRolesAsumidos = $bd->VerRegRest("ORDEN_TRANSPORTE_ROLES_ASUMIDOS", "ROL = 'Proveedor' AND ID_ORDEN_TRANSPORTE = $rowOTsAccionesPendientes->ID_ORDEN_TRANSPORTE", "No");

                                                                                                                                if ($rowRolesAsumidos->FORWARDER_ASUMIDO == 1):

                                                                                                                                    //SI SE ASUME EL ROL DEL FORWARDER
                                                                                                                                    $arrOTsAccionesPendientes[] = $rowOTsAccionesPendientes->ID_ORDEN_TRANSPORTE;

                                                                                                                                endif;

                                                                                                                            endif;

                                                                                                                        elseif (($rowOTsAccionesPendientes->TIPO_DESTINATARIO == "Agente Aduanal") && !in_array($rowOTsAccionesPendientes->ID_ORDEN_TRANSPORTE, (array) $arrOTsAccionesPendientes)):

                                                                                                                            if ($rowOTsAccionesPendientes->ID_PROVEEDOR_AGENTE_ADUANAL == $administrador->ID_PROVEEDOR):

                                                                                                                                //SI EL PROVEEDOR SE CORRESPONDE CON EL AGENTE ADUANAL
                                                                                                                                $arrOTsAccionesPendientes[] = $rowOTsAccionesPendientes->ID_ORDEN_TRANSPORTE;

                                                                                                                            elseif ($rowOTsAccionesPendientes->ID_PROVEEDOR_FORWARDER == $administrador->ID_PROVEEDOR):

                                                                                                                                //SI EL PROVEEDOR SE CORRESPONDE CON EL FORWARDER, OBTENEMOS LOS ROLES QUE PUEDE ASUMIR
                                                                                                                                $rowRolesAsumidos = $bd->VerRegRest("ORDEN_TRANSPORTE_ROLES_ASUMIDOS", "ROL = 'Forwarder' AND ID_ORDEN_TRANSPORTE = $rowOTsAccionesPendientes->ID_ORDEN_TRANSPORTE", "No");

                                                                                                                            endif;

                                                                                                                        elseif (($rowOTsAccionesPendientes->TIPO_DESTINATARIO == "Transportista Inland") && !in_array($rowOTsAccionesPendientes->ID_ORDEN_TRANSPORTE, (array) $arrOTsAccionesPendientes)):

                                                                                                                            if ($rowOTsAccionesPendientes->ID_AGENCIA == $administrador->ID_PROVEEDOR):

                                                                                                                                //SI EL PROVEEDOR SE CORRESPONDE CON EL TRANSPORTISTA INLAND
                                                                                                                                $arrOTsAccionesPendientes[] = $rowOTsAccionesPendientes->ID_ORDEN_TRANSPORTE;

                                                                                                                            elseif ($rowOTsAccionesPendientes->ID_PROVEEDOR == $administrador->ID_PROVEEDOR):

                                                                                                                                //SI EL PROVEEDOR SE CORRESPONDE CON EL PROVEEDOR DEL MATERIAL, OBTENEMOS LOS ROLES QUE PUEDE ASUMIR
                                                                                                                                $rowRolesAsumidos = $bd->VerRegRest("ORDEN_TRANSPORTE_ROLES_ASUMIDOS", "ROL = 'Proveedor' AND ID_ORDEN_TRANSPORTE = $rowOTsAccionesPendientes->ID_ORDEN_TRANSPORTE", "No");

                                                                                                                                if ($rowRolesAsumidos->TRANSPORTISTA_INLAND_ASUMIDO == 1):

                                                                                                                                    //SI SE ASUME EL ROL DEL AGENTE ADUANAL
                                                                                                                                    $arrOTsAccionesPendientes[] = $rowOTsAccionesPendientes->ID_ORDEN_TRANSPORTE;

                                                                                                                                endif;

                                                                                                                            elseif ($rowOTsAccionesPendientes->ID_PROVEEDOR_FORWARDER == $administrador->ID_PROVEEDOR):

                                                                                                                                //SI EL PROVEEDOR SE CORRESPONDE CON EL PROVEEDOR DEL MATERIAL, OBTENEMOS LOS ROLES QUE PUEDE ASUMIR
                                                                                                                                $rowRolesAsumidos = $bd->VerRegRest("ORDEN_TRANSPORTE_ROLES_ASUMIDOS", "ROL = 'Forwarder' AND ID_ORDEN_TRANSPORTE = $rowOTsAccionesPendientes->ID_ORDEN_TRANSPORTE", "No");

                                                                                                                                if ($rowRolesAsumidos->TRANSPORTISTA_INLAND_ASUMIDO == 1):

                                                                                                                                    //SI SE ASUME EL ROL DEL AGENTE ADUANAL
                                                                                                                                    $arrOTsAccionesPendientes[] = $rowOTsAccionesPendientes->ID_ORDEN_TRANSPORTE;

                                                                                                                                endif;

                                                                                                                            endif;

                                                                                                                        endif;

                                                                                                                    endwhile;

                                                                                                                else:

                                                                                                                    //SI EL USUARIO NO ES PROVEEDOR, ENTONCES COMPROBAMOS SI EXISTEN OTS CON ACCIONES PENDIENTES
                                                                                                                    if ($fila_sub_sub_submenu->ZONA_TABLA == "ProveedoresConstruccionCronogramaProveedores"):
                                                                                                                        $sqlOTsAccionesPendientes = "SELECT DISTINCT OTAA.ID_ORDEN_TRANSPORTE, OTA.TIPO_ACCION
                                                                                                                FROM ORDEN_TRANSPORTE_ACCION OTA
                                                                                                                    INNER JOIN ORDEN_TRANSPORTE_ACCION_AVISO OTAA ON OTAA.ID_ORDEN_TRANSPORTE_ACCION = OTA.ID_ORDEN_TRANSPORTE_ACCION
                                                                                                                WHERE OTAA.ESTADO_ACCION = 'Pendiente' AND OTA.TIENE_ACCION_MASIVA = 1 AND OTA.TIPO_ACCION <> 'Aceptar Documentacion' AND OTAA.BAJA = 0 AND OTA.BAJA = 0";
                                                                                                                    else:
                                                                                                                        $sqlOTsAccionesPendientes = "SELECT DISTINCT OTAA.ID_ORDEN_TRANSPORTE, OTA.TIPO_ACCION
                                                                                                                FROM ORDEN_TRANSPORTE_ACCION OTA
                                                                                                                    INNER JOIN ORDEN_TRANSPORTE_ACCION_AVISO OTAA ON OTAA.ID_ORDEN_TRANSPORTE_ACCION = OTA.ID_ORDEN_TRANSPORTE_ACCION
                                                                                                                WHERE OTAA.ESTADO_ACCION = 'Pendiente' AND OTA.TIPO_ACCION <> 'Aceptar Documentacion' AND OTAA.BAJA = 0 AND OTA.BAJA = 0";
                                                                                                                    endif;

                                                                                                                    $resultOTsAccionesPendientes = $bd->ExecSQL($sqlOTsAccionesPendientes);
                                                                                                                    $arrOTsAccionesPendientes    = array();

                                                                                                                    while ($rowOTsAccionesPendientes = $bd->SigReg($resultOTsAccionesPendientes)):
                                                                                                                        $arrOTsAccionesPendientes[] = $rowOTsAccionesPendientes->ID_ORDEN_TRANSPORTE;

                                                                                                                        if ($rowOTsAccionesPendientes->TIPO_ACCION == "Modificar Documentacion" || $rowOTsAccionesPendientes->TIPO_ACCION == "Revisar Borrador BL" || $rowOTsAccionesPendientes->TIPO_ACCION == "Revisar BL Definitivo" || $rowOTsAccionesPendientes->TIPO_ACCION == "Revisar CMR" || $rowOTsAccionesPendientes->TIPO_ACCION == "Añadir Borrador BL" || $rowOTsAccionesPendientes->TIPO_ACCION == "Añadir BL Definitivo" || $rowOTsAccionesPendientes->TIPO_ACCION == "Añadir CMR" || $rowOTsAccionesPendientes->TIPO_ACCION == "Actualizar ETA" || $rowOTsAccionesPendientes->TIPO_ACCION == "Confirmar Salida Buque" || $rowOTsAccionesPendientes->TIPO_ACCION == "Confirmar Llegada Buque" || $rowOTsAccionesPendientes->TIPO_ACCION == "Aceptar/Rechazar Borrador BL" || $rowOTsAccionesPendientes->TIPO_ACCION == "Aceptar/Rechazar BL Definitivo" || $rowOTsAccionesPendientes->TIPO_ACCION == "Aceptar/Rechazar Documentacion Proveedor"):

                                                                                                                            $arrOTsAccionesDocumentalesPendientes[] = $rowOTsAccionesPendientes->ID_ORDEN_TRANSPORTE;

                                                                                                                        endif;

                                                                                                                    endwhile;

                                                                                                                endif;

                                                                                                            endif;

                                                                                                            //SI ES PROVEEDOR NORDEX, TIENE UN COMPORTAMIENTO DIFERENTE
                                                                                                            $nombrePantalla       = "";
                                                                                                            $hitosPendientes      = false;
                                                                                                            $mostrarTransportesGC = true;
                                                                                                            $arrHitosPendientes   = array();

                                                                                                            if ($administrador->esProveedor()):

                                                                                                                //OBTENGO EL PROVEEDOR
                                                                                                                $NotificaErrorPorEmail = "No";
                                                                                                                $rowProveedor          = $bd->VerReg("PROVEEDOR", "ID_PROVEEDOR", $administrador->ID_PROVEEDOR, "No");

                                                                                                                if ((($fila_sub_sub_submenu->ZONA_TABLA == "TransporteConstruccionTransporteGCProveedor") && ($rowProveedor->REFERENCIA == "V-EW01")) || ($fila_sub_sub_submenu->ZONA_TABLA == "TransporteConstruccionTransporteGCForwarder") || ($fila_sub_sub_submenu->ZONA_TABLA == "TransporteConstruccionTransporteGCAgenteAduanal")):
                                                                                                                    $nombrePantalla = "Embarques GC";

                                                                                                                    //OBTENEMOS LOS EMBARQUES ACTIVOS DEL SISTEMA
                                                                                                                    $sqlEmbarques    = "SELECT *
                                                                                                        FROM EMBARQUE
                                                                                                        WHERE BAJA = 0 AND (ID_PROVEEDOR = $administrador->ID_PROVEEDOR OR ID_FORWARDER = $administrador->ID_PROVEEDOR OR ID_EMPRESA_ADUANAL = $administrador->ID_PROVEEDOR)";
                                                                                                                    $resultEmbarques = $bd->ExecSQL($sqlEmbarques);

                                                                                                                    while ($rowEmbarques = $bd->SigReg($resultEmbarques)):

                                                                                                                        if (!$hitosPendientes):
                                                                                                                            //OBTENEMOS LOS HITOS PENDIENTES DE REALIZAR
                                                                                                                            $arrHitosPendientes = $orden_transporte->getHitosPendientes($rowEmbarques);

                                                                                                                            $hitosPendientes = (count( (array)$arrHitosPendientes) > 0 ? true : false);
                                                                                                                        endif;

                                                                                                                    endwhile;

                                                                                                                    $mostrarTransportesGC = true;

                                                                                                                else:

                                                                                                                    $nombrePantalla       = $fila_sub_sub_submenu->NOMBRE;
                                                                                                                    $mostrarTransportesGC = false;

                                                                                                                endif;

                                                                                                            else:

                                                                                                                $nombrePantalla = $fila_sub_sub_submenu->NOMBRE;

                                                                                                                if ($fila_sub_sub_submenu->ZONA_TABLA == "TransporteConstruccionTransporteGCProveedor"):

                                                                                                                    //OBTENEMOS LOS EMBARQUES ACTIVOS DEL SISTEMA
                                                                                                                    $sqlEmbarques    = "SELECT *
                                                                                                                FROM EMBARQUE
                                                                                                                WHERE BAJA = 0";
                                                                                                                    $resultEmbarques = $bd->ExecSQL($sqlEmbarques);

                                                                                                                    while ($rowEmbarques = $bd->SigReg($resultEmbarques)):

                                                                                                                        if (!$hitosPendientes):
                                                                                                                            //OBTENEMOS LOS HITOS PENDIENTES DE REALIZAR
                                                                                                                            $arrHitosPendientes = $orden_transporte->getHitosPendientes($rowEmbarques);

                                                                                                                            $hitosPendientes = (count( (array)$arrHitosPendientes) > 0 ? true : false);
                                                                                                                        endif;

                                                                                                                    endwhile;

                                                                                                                    $mostrarTransportesGC = true;

                                                                                                                endif;

                                                                                                            endif;

                                                                                                            ?>
                                                                                                            <tr>
                                                                                                                <td width="18%"
                                                                                                                    height="23"></td>

                                                                                                                <? if ($ZonaTabla == $fila_sub_sub_submenu->ZONA_TABLA): ?>
                                                                                                                    <? if ($fila_sub_sub_submenu->ZONA_TABLA == "TransporteConstruccionTransporteGCProveedor"): ?>
                                                                                                                        <? if ($mostrarTransportesGC): ?>
                                                                                                                            <td valign='top'
                                                                                                                                width='82%'
                                                                                                                                class='blanco'>
                                                                                                                                <div align='left'>
                                                                                                                                    <a href='<?php
                                                                                                                                    if ($fila_sub_sub_submenu->ENTORNO_CODEIGNITER == '1'):
                                                                                                                                        echo $pathRaiz . "tunel_codeigniter.php?idMenu=" . $fila_sub_sub_submenu->ID_MENU;
                                                                                                                                    else:
                                                                                                                                        echo $pathRaiz . $fila_sub_sub_submenu->LINK;
                                                                                                                                    endif; ?>'
                                                                                                                                       class='switch'><span
                                                                                                                                            class=senalado32>&nbsp;
                                                                                                    <?= $auxiliar->traduce($nombrePantalla, $administrador->ID_IDIOMA) ?>
                                                                                                </span>
                                                                                                                                        <? if (($fila_sub_sub_submenu->ZONA_TABLA == "TransporteConstruccionTransporteGCProveedor") && ($hitosPendientes)): ?>
                                                                                                                                            <a style="float: right;">
                                                                                                                                                <img id="img_warning"
                                                                                                                                                     src="<? echo $pathRaiz ?>imagenes/warning.png"
                                                                                                                                                     style="margin-right: 5px;margin-top: 2px;width:16px;float: right;"
                                                                                                                                                     class="parpadeante"
                                                                                                                                                     title="<?= $auxiliar->traduce("Existen hitos pendientes de resolver", $administrador->ID_IDIOMA) ?>">
                                                                                                                                            </a>
                                                                                                                                        <? endif; ?>
                                                                                                                                    </a>
                                                                                                                                </div>
                                                                                                                            </td>
                                                                                                                        <? endif; ?>
                                                                                                                    <? else: ?>
                                                                                                                        <td valign='top'
                                                                                                                            width='82%'
                                                                                                                            class='blanco'>
                                                                                                                            <div align='left'>
                                                                                                                                <a href='<?php
                                                                                                                                if ($fila_sub_sub_submenu->ENTORNO_CODEIGNITER == '1'):
                                                                                                                                    echo $pathRaiz . "tunel_codeigniter.php?idMenu=" . $fila_sub_sub_submenu->ID_MENU;
                                                                                                                                else:
                                                                                                                                    echo $pathRaiz . $fila_sub_sub_submenu->LINK;
                                                                                                                                endif; ?>'
                                                                                                                                   class='switch'><span
                                                                                                                                        class=senalado32>&nbsp;
                                                                                                    <?= $auxiliar->traduce($nombrePantalla, $administrador->ID_IDIOMA) ?>
                                                                                                </span>
                                                                                                                                    <? if (($fila_sub_sub_submenu->ZONA_TABLA == "ProveedoresConstruccionCronogramaProveedores") && (count( (array)$arrOTsAccionesPendientes) > 0)): ?>
                                                                                                                                        <a style="float: right;">
                                                                                                                                            <img id="img_warning"
                                                                                                                                                 src="<? echo $pathRaiz ?>imagenes/warning.png"
                                                                                                                                                 style="margin-right: 5px;margin-top: 2px;width:16px;float: right;"
                                                                                                                                                 class="parpadeante"
                                                                                                                                                 title="<?= $auxiliar->traduce("Existen OTs con acciones pendientes de resolver", $administrador->ID_IDIOMA) ?>">
                                                                                                                                        </a>
                                                                                                                                    <? elseif (($fila_sub_sub_submenu->ZONA_TABLA == "ProveedoresAvisosConstruccionProveedores") && ($bd->NumRegs($resultAcciones) > 0)): ?>
                                                                                                                                        <a style="float: right;">
                                                                                                                                            <img id="img_warning"
                                                                                                                                                 src="<? echo $pathRaiz ?>imagenes/warning.png"
                                                                                                                                                 style="margin-right: 5px;margin-top: 2px;width:16px;float: right;"
                                                                                                                                                 class="parpadeante"
                                                                                                                                                 title="<?= $auxiliar->traduce("Existen acciones pendientes de resolver", $administrador->ID_IDIOMA) ?>">
                                                                                                                                        </a>
                                                                                                                                    <? endif; ?>
                                                                                                                                </a>
                                                                                                                            </div>
                                                                                                                        </td>
                                                                                                                    <? endif; ?>
                                                                                                                <? else: ?>
                                                                                                                    <? if ($fila_sub_sub_submenu->ZONA_TABLA == "TransporteConstruccionTransporteGCProveedor"): ?>
                                                                                                                        <? if ($mostrarTransportesGC): ?>
                                                                                                                            <td width='82%'
                                                                                                                                height='23'
                                                                                                                                align=left
                                                                                                                                valign='top'>
                                                                                                                                <a href='<?php
                                                                                                                                if ($fila_sub_sub_submenu->ENTORNO_CODEIGNITER == '1'):
                                                                                                                                    echo $pathRaiz . "tunel_codeigniter.php?idMenu=" . $fila_sub_sub_submenu->ID_MENU;
                                                                                                                                else:
                                                                                                                                    echo $pathRaiz . $fila_sub_sub_submenu->LINK;
                                                                                                                                endif; ?>'
                                                                                                                                   class='switch'><SPAN
                                                                                                                                        class=white32><SPAN
                                                                                                                                            class=blackc32>&nbsp;
                                                                                                    <?= $auxiliar->traduce($nombrePantalla, $administrador->ID_IDIOMA) ?>
                                                                                                </SPAN></SPAN>
                                                                                                                                    <? if (($fila_sub_sub_submenu->ZONA_TABLA == "TransporteConstruccionTransporteGCProveedor") && ($hitosPendientes)): ?>
                                                                                                                                        <a style="float: right;">
                                                                                                                                            <img id="img_warning_gc"
                                                                                                                                                 src="<? echo $pathRaiz ?>imagenes/warning.png"
                                                                                                                                                 style="margin-right: 5px;margin-top: 2px;width:16px;float: right;"
                                                                                                                                                 class="parpadeante"
                                                                                                                                                 title="<?= $auxiliar->traduce("Existen hitos pendientes de resolver", $administrador->ID_IDIOMA) ?>">
                                                                                                                                        </a>
                                                                                                                                    <? endif; ?>
                                                                                                                                </a>
                                                                                                                            </td>
                                                                                                                        <? endif; ?>
                                                                                                                    <? else: ?>
                                                                                                                        <td width='82%'
                                                                                                                            height='23'
                                                                                                                            align=left
                                                                                                                            valign='top'>
                                                                                                                            <a href='<?php
                                                                                                                            if ($fila_sub_sub_submenu->ENTORNO_CODEIGNITER == '1'):
                                                                                                                                echo $pathRaiz . "tunel_codeigniter.php?idMenu=" . $fila_sub_sub_submenu->ID_MENU;
                                                                                                                            else:
                                                                                                                                echo $pathRaiz . $fila_sub_sub_submenu->LINK;
                                                                                                                            endif; ?>'
                                                                                                                               class='switch'><SPAN
                                                                                                                                    class=white32><SPAN
                                                                                                                                        class=blackc32>&nbsp;
                                                                                                    <?= $auxiliar->traduce($nombrePantalla, $administrador->ID_IDIOMA) ?>
                                                                                                </SPAN></SPAN>
                                                                                                                                <? if (($fila_sub_sub_submenu->ZONA_TABLA == "ProveedoresConstruccionCronogramaProveedores") && (count( (array)$arrOTsAccionesPendientes) > 0)): ?>
                                                                                                                                    <a style="float: right;">
                                                                                                                                        <img id="img_warning_cronograma"
                                                                                                                                             src="<? echo $pathRaiz ?>imagenes/warning.png"
                                                                                                                                             style="margin-right: 5px;margin-top: 2px;width:16px;float: right;"
                                                                                                                                             class="parpadeante"
                                                                                                                                             title="<?= $auxiliar->traduce("Existen OTs con acciones pendientes de resolver", $administrador->ID_IDIOMA) ?>">
                                                                                                                                    </a>
                                                                                                                                <? elseif (($fila_sub_sub_submenu->ZONA_TABLA == "ProveedoresAvisosConstruccionProveedores") && ($bd->NumRegs($resultAcciones) > 0)): ?>
                                                                                                                                    <a style="float: right;">
                                                                                                                                        <img id="img_warning_acciones"
                                                                                                                                             src="<? echo $pathRaiz ?>imagenes/warning.png"
                                                                                                                                             style="margin-right: 5px;margin-top: 2px;width:16px;float: right;"
                                                                                                                                             class="parpadeante"
                                                                                                                                             title="<?= $auxiliar->traduce("Existen acciones pendientes de resolver", $administrador->ID_IDIOMA) ?>">
                                                                                                                                    </a>
                                                                                                                                <? endif; ?>
                                                                                                                            </a>
                                                                                                                        </td>
                                                                                                                    <? endif; ?>
                                                                                                                <? endif; ?>
                                                                                                            </tr>
                                                                                                        <? endif; ?>
                                                                                                    <? endforeach; ?>
                                                                                                </table>
                                                                                            </td>
                                                                                        </tr>
                                                                                    <?
                                                                                    endif;
                                                                                else: ?>
                                                                                    <tr class="<?= $fila_sub_submenu->ID_MENU ?>">
                                                                                        <td class="subsubhijos" <?= ($fila_sub_submenu->ZONA_TABLA != $ZonaSubSubTablaPadre && !$administrador->esProveedor()) ? 'style="display:none;"' : '' ?>>

                                                                                            <table cellpadding="0"
                                                                                                   cellspacing="0"
                                                                                                   width="100%">
                                                                                                <?
                                                                                                foreach ($arr_datos_sub_submenu['SubMenus3'] as $idSubMenu3 => $arr_datos_sub_sub_submenu):
                                                                                                    if ($arr_datos_sub_sub_submenu['Mostrar'] == 1):

                                                                                                        //DATOS DEL SUBMENU
                                                                                                        $fila_sub_sub_submenu = $arr_datos_sub_sub_submenu['SubMenu3'];

                                                                                                        if ($fila_sub_sub_submenu->ZONA_TABLA == "ProveedoresAvisosConstruccionProveedores"):

                                                                                                            $sqlAcciones = "SELECT OT.ID_ORDEN_TRANSPORTE, OTC.ID_PROVEEDOR, OTC.ID_PROVEEDOR_FORWARDER, OTC.ID_PROVEEDOR_AGENTE_ADUANAL, OT.ID_AGENCIA, OTA.TIPO_DESTINATARIO, OTAA.ID_ORDEN_TRANSPORTE_ACCION_AVISO
                                                                                                FROM ORDEN_TRANSPORTE_ACCION OTA
                                                                                                    INNER JOIN ORDEN_TRANSPORTE_ACCION_AVISO OTAA ON OTAA.ID_ORDEN_TRANSPORTE_ACCION = OTA.ID_ORDEN_TRANSPORTE_ACCION
                                                                                                    INNER JOIN ORDEN_TRANSPORTE OT ON OT.ID_ORDEN_TRANSPORTE = OTAA.ID_ORDEN_TRANSPORTE
                                                                                                    INNER JOIN ORDEN_TRANSPORTE_CONSTRUCCION OTC ON OT.ID_ORDEN_TRANSPORTE = OTC.ID_ORDEN_TRANSPORTE
                                                                                                WHERE 1 = 1 AND OTAA.ESTADO_ACCION = 'Pendiente' AND OT.BAJA = 0 AND OTAA.BAJA = 0 AND ( (TIMESTAMPDIFF(DAY, OTAA.FECHA_RELACIONADA, NOW()) >= OTA.DIAS_BANDERA_VERDE) OR (OTA.DIAS_BANDERA_VERDE = 0) ) AND OTA.TIPO_ACCION <> 'Aceptar Documentacion' AND ( IF ( (OTA.ESTADO_TRANSPORTE = 'Puerto Origen' OR OTA.ESTADO_TRANSPORTE = 'Transito Internacional') AND (OTA.NO_AFECTA_ESTADOS = 0), OT.CON_EMBARQUE_GC <> 1, 1)) AND (OTA.TIPO_ACCION = 'Modificar Documentacion' OR OTA.TIPO_ACCION = 'Revisar Borrador BL' OR OTA.TIPO_ACCION = 'Revisar BL Definitivo' OR OTA.TIPO_ACCION = 'Revisar CMR' OR OTA.TIPO_ACCION = 'Añadir Borrador BL' OR OTA.TIPO_ACCION = 'Añadir BL Definitivo' OR OTA.TIPO_ACCION = 'Añadir CMR' OR OTA.TIPO_ACCION = 'Actualizar ETA' OR OTA.TIPO_ACCION = 'Confirmar Salida Buque' OR OTA.TIPO_ACCION = 'Confirmar Llegada Buque' OR OTA.TIPO_ACCION = 'Generar Transbordo' OR OTA.TIPO_ACCION = 'Aceptar/Rechazar Borrador BL' OR OTA.TIPO_ACCION = 'Aceptar/Rechazar BL Definitivo' OR OTA.TIPO_ACCION = 'Aceptar/Rechazar Documentacion Proveedor')" . ($administrador->esProveedor() ? " AND (OT.ID_AGENCIA = $administrador->ID_PROVEEDOR OR OTC.ID_PROVEEDOR = $administrador->ID_PROVEEDOR OR OTC.ID_PROVEEDOR_FORWARDER = $administrador->ID_PROVEEDOR OR OTC.ID_PROVEEDOR_AGENTE_ADUANAL = $administrador->ID_PROVEEDOR)" : "");

                                                                                                            if ($administrador->esProveedor()):
                                                                                                                if ($administrador->ID_PROVEEDOR != ""):

                                                                                                                    $resultAcciones   = $bd->ExecSQL($sqlAcciones);
                                                                                                                    $sqlRolesAsumidos = "";
                                                                                                                    $coma             = "";

                                                                                                                    while ($row = $bd->SigReg($resultAcciones)):

                                                                                                                        $rowRolesAsumidos = false;

                                                                                                                        if ($row->ID_PROVEEDOR == $administrador->ID_PROVEEDOR):

                                                                                                                            //OBTENEMOS LOS ROLES ASUMIDOS
                                                                                                                            $rowRolesAsumidos = $bd->VerRegRest("ORDEN_TRANSPORTE_ROLES_ASUMIDOS", "ROL = 'Proveedor' AND ID_ORDEN_TRANSPORTE = $row->ID_ORDEN_TRANSPORTE", "No");

                                                                                                                        elseif ($row->ID_PROVEEDOR_FORWARDER == $administrador->ID_PROVEEDOR):

                                                                                                                            //OBTENEMOS LOS ROLES ASUMIDOS
                                                                                                                            $rowRolesAsumidos = $bd->VerRegRest("ORDEN_TRANSPORTE_ROLES_ASUMIDOS", "ROL = 'Forwarder' AND ID_ORDEN_TRANSPORTE = $row->ID_ORDEN_TRANSPORTE", "No");

                                                                                                                        elseif ($row->ID_PROVEEDOR_AGENTE_ADUANAL == $administrador->ID_PROVEEDOR):

                                                                                                                            //OBTENEMOS LOS ROLES ASUMIDOS
                                                                                                                            $rowRolesAsumidos = $bd->VerRegRest("ORDEN_TRANSPORTE_ROLES_ASUMIDOS", "ROL = 'Agente Aduanal' AND ID_ORDEN_TRANSPORTE = $row->ID_ORDEN_TRANSPORTE", "No");

                                                                                                                        endif;

                                                                                                                        if (($row->TIPO_DESTINATARIO == "Proveedor del Material") && ($rowRolesAsumidos->PROVEEDOR_ASUMIDO == 1)):

                                                                                                                            $sqlRolesAsumidos .= $coma . $row->ID_ORDEN_TRANSPORTE_ACCION_AVISO;
                                                                                                                            $coma             = ",";

                                                                                                                        elseif (($row->TIPO_DESTINATARIO == "Forwarder") && ($rowRolesAsumidos->FORWARDER_ASUMIDO == 1)):

                                                                                                                            $sqlRolesAsumidos .= $coma . $row->ID_ORDEN_TRANSPORTE_ACCION_AVISO;
                                                                                                                            $coma             = ",";

                                                                                                                        elseif (($row->TIPO_DESTINATARIO == "Agente Aduanal") && ($rowRolesAsumidos->AGENTE_ADUANAL_ASUMIDO == 1)):

                                                                                                                            $sqlRolesAsumidos .= $coma . $row->ID_ORDEN_TRANSPORTE_ACCION_AVISO;
                                                                                                                            $coma             = ",";

                                                                                                                        elseif (($row->TIPO_DESTINATARIO == "Transportista Inland") && ($rowRolesAsumidos->TRANSPORTISTA_INLAND_ASUMIDO == 1)):

                                                                                                                            $sqlRolesAsumidos .= $coma . $row->ID_ORDEN_TRANSPORTE_ACCION_AVISO;
                                                                                                                            $coma             = ",";

                                                                                                                        endif;

                                                                                                                    endwhile;

                                                                                                                    $sqlAcciones .= ($sqlRolesAsumidos != "" ? " AND OTAA.ID_ORDEN_TRANSPORTE_ACCION_AVISO IN ($sqlRolesAsumidos)" : " AND FALSE ");

                                                                                                                endif;
                                                                                                            endif;

                                                                                                            //EJECUTAMOS LA CONSULTA
                                                                                                            $resultAcciones = $bd->ExecSQL($sqlAcciones);

                                                                                                        endif;

                                                                                                        if (($fila_sub_sub_submenu->ZONA_TABLA == "ProveedoresConstruccionCronogramaProveedores") || ($fila_sub_sub_submenu->ZONA_TABLA == "ProveedoresAvisosConstruccionProveedores")):

                                                                                                            //SI ES EL CRONOGRAMA DE PROVEEDORES, ENTONCES COMPRUEBO SI TIENE ACCIONES PENDIENTES
                                                                                                            if ($administrador->esProveedor()):

                                                                                                                //SI ES PROVEEDOR, COMPRUEBO SI PUEDE DELEGAR ALGUNA ACCIÓN
                                                                                                                if ($fila_sub_sub_submenu->ZONA_TABLA == "ProveedoresConstruccionCronogramaProveedores"):
                                                                                                                    $sqlOTsAccionesPendientes = "SELECT OTAA.ID_ORDEN_TRANSPORTE, OTA.TIPO_ACCION, OTA.TIPO_DESTINATARIO, OTC.ID_PROVEEDOR, OTC.ID_PROVEEDOR_FORWARDER, OTC.ID_PROVEEDOR_AGENTE_ADUANAL, OT.ID_AGENCIA
                                                                                                                                FROM ORDEN_TRANSPORTE_ACCION OTA
                                                                                                                                    INNER JOIN ORDEN_TRANSPORTE_ACCION_AVISO OTAA ON OTAA.ID_ORDEN_TRANSPORTE_ACCION = OTA.ID_ORDEN_TRANSPORTE_ACCION
                                                                                                                                    INNER JOIN ORDEN_TRANSPORTE OT ON OT.ID_ORDEN_TRANSPORTE = OTAA.ID_ORDEN_TRANSPORTE
                                                                                                                                    INNER JOIN ORDEN_TRANSPORTE_CONSTRUCCION OTC ON OTC.ID_ORDEN_TRANSPORTE = OT.ID_ORDEN_TRANSPORTE
                                                                                                                                WHERE OTAA.ESTADO_ACCION = 'Pendiente' AND OTA.TIENE_ACCION_MASIVA = 1 AND OTA.TIPO_ACCION <> 'Aceptar Documentacion' AND (OTC.ID_PROVEEDOR = $administrador->ID_PROVEEDOR OR OTC.ID_PROVEEDOR_FORWARDER = $administrador->ID_PROVEEDOR OR OTC.ID_PROVEEDOR_AGENTE_ADUANAL = $administrador->ID_PROVEEDOR OR OT.ID_AGENCIA = $administrador->ID_PROVEEDOR) AND OTAA.BAJA = 0 AND OTA.BAJA = 0 AND OT.BAJA = 0";
                                                                                                                else:
                                                                                                                    $sqlOTsAccionesPendientes = "SELECT OTAA.ID_ORDEN_TRANSPORTE, OTA.TIPO_ACCION, OTA.TIPO_DESTINATARIO, OTC.ID_PROVEEDOR, OTC.ID_PROVEEDOR_FORWARDER, OTC.ID_PROVEEDOR_AGENTE_ADUANAL, OT.ID_AGENCIA
                                                                                                                                FROM ORDEN_TRANSPORTE_ACCION OTA
                                                                                                                                    INNER JOIN ORDEN_TRANSPORTE_ACCION_AVISO OTAA ON OTAA.ID_ORDEN_TRANSPORTE_ACCION = OTA.ID_ORDEN_TRANSPORTE_ACCION
                                                                                                                                    INNER JOIN ORDEN_TRANSPORTE OT ON OT.ID_ORDEN_TRANSPORTE = OTAA.ID_ORDEN_TRANSPORTE
                                                                                                                                    INNER JOIN ORDEN_TRANSPORTE_CONSTRUCCION OTC ON OTC.ID_ORDEN_TRANSPORTE = OT.ID_ORDEN_TRANSPORTE
                                                                                                                                WHERE OTAA.ESTADO_ACCION = 'Pendiente' AND OTA.TIPO_ACCION <> 'Aceptar Documentacion' AND (OTC.ID_PROVEEDOR = $administrador->ID_PROVEEDOR OR OTC.ID_PROVEEDOR_FORWARDER = $administrador->ID_PROVEEDOR OR OTC.ID_PROVEEDOR_AGENTE_ADUANAL = $administrador->ID_PROVEEDOR OR OT.ID_AGENCIA = $administrador->ID_PROVEEDOR) AND OTAA.BAJA = 0 AND OTA.BAJA = 0 AND OT.BAJA = 0";
                                                                                                                endif;

                                                                                                                $resultOTsAccionesPendientes = $bd->ExecSQL($sqlOTsAccionesPendientes);
                                                                                                                $arrOTsAccionesPendientes    = array();

                                                                                                                while ($rowOTsAccionesPendientes = $bd->SigReg($resultOTsAccionesPendientes)):

                                                                                                                    if (($rowOTsAccionesPendientes->TIPO_DESTINATARIO == "Proveedor del Material") && !in_array($rowOTsAccionesPendientes->ID_ORDEN_TRANSPORTE, (array) $arrOTsAccionesPendientes)):

                                                                                                                        if ($rowOTsAccionesPendientes->ID_PROVEEDOR == $administrador->ID_PROVEEDOR):

                                                                                                                            //SI EL PROVEEDOR SE CORRESPONDE CON EL PROVEEDOR DEL MATERIAL
                                                                                                                            $arrOTsAccionesPendientes[] = $rowOTsAccionesPendientes->ID_ORDEN_TRANSPORTE;

                                                                                                                        endif;

                                                                                                                    elseif (($rowOTsAccionesPendientes->TIPO_DESTINATARIO == "Forwarder") && !in_array($rowOTsAccionesPendientes->ID_ORDEN_TRANSPORTE, (array) $arrOTsAccionesPendientes)):

                                                                                                                        if ($rowOTsAccionesPendientes->ID_PROVEEDOR_FORWARDER == $administrador->ID_PROVEEDOR):

                                                                                                                            //SI EL PROVEEDOR SE CORRESPONDE CON EL FORWARDER
                                                                                                                            $arrOTsAccionesPendientes[] = $rowOTsAccionesPendientes->ID_ORDEN_TRANSPORTE;

                                                                                                                        elseif ($rowOTsAccionesPendientes->ID_PROVEEDOR == $administrador->ID_PROVEEDOR):

                                                                                                                            //SI EL PROVEEDOR SE CORRESPONDE CON EL PROVEEDOR DEL MATERIAL, OBTENEMOS LOS ROLES QUE PUEDE ASUMIR
                                                                                                                            $rowRolesAsumidos = $bd->VerRegRest("ORDEN_TRANSPORTE_ROLES_ASUMIDOS", "ROL = 'Proveedor' AND ID_ORDEN_TRANSPORTE = $rowOTsAccionesPendientes->ID_ORDEN_TRANSPORTE", "No");

                                                                                                                            if ($rowRolesAsumidos->FORWARDER_ASUMIDO == 1):

                                                                                                                                //SI SE ASUME EL ROL DEL FORWARDER
                                                                                                                                $arrOTsAccionesPendientes[] = $rowOTsAccionesPendientes->ID_ORDEN_TRANSPORTE;

                                                                                                                            endif;

                                                                                                                        endif;

                                                                                                                    elseif (($rowOTsAccionesPendientes->TIPO_DESTINATARIO == "Agente Aduanal") && !in_array($rowOTsAccionesPendientes->ID_ORDEN_TRANSPORTE, (array) $arrOTsAccionesPendientes)):

                                                                                                                        if ($rowOTsAccionesPendientes->ID_PROVEEDOR_AGENTE_ADUANAL == $administrador->ID_PROVEEDOR):

                                                                                                                            //SI EL PROVEEDOR SE CORRESPONDE CON EL AGENTE ADUANAL
                                                                                                                            $arrOTsAccionesPendientes[] = $rowOTsAccionesPendientes->ID_ORDEN_TRANSPORTE;

                                                                                                                        elseif ($rowOTsAccionesPendientes->ID_PROVEEDOR_FORWARDER == $administrador->ID_PROVEEDOR):

                                                                                                                            //SI EL PROVEEDOR SE CORRESPONDE CON EL FORWARDER, OBTENEMOS LOS ROLES QUE PUEDE ASUMIR
                                                                                                                            $rowRolesAsumidos = $bd->VerRegRest("ORDEN_TRANSPORTE_ROLES_ASUMIDOS", "ROL = 'Forwarder' AND ID_ORDEN_TRANSPORTE = $rowOTsAccionesPendientes->ID_ORDEN_TRANSPORTE", "No");

                                                                                                                        endif;

                                                                                                                    elseif (($rowOTsAccionesPendientes->TIPO_DESTINATARIO == "Transportista Inland") && !in_array($rowOTsAccionesPendientes->ID_ORDEN_TRANSPORTE, (array) $arrOTsAccionesPendientes)):

                                                                                                                        if ($rowOTsAccionesPendientes->ID_AGENCIA == $administrador->ID_PROVEEDOR):

                                                                                                                            //SI EL PROVEEDOR SE CORRESPONDE CON EL TRANSPORTISTA INLAND
                                                                                                                            $arrOTsAccionesPendientes[] = $rowOTsAccionesPendientes->ID_ORDEN_TRANSPORTE;

                                                                                                                        elseif ($rowOTsAccionesPendientes->ID_PROVEEDOR == $administrador->ID_PROVEEDOR):

                                                                                                                            //SI EL PROVEEDOR SE CORRESPONDE CON EL PROVEEDOR DEL MATERIAL, OBTENEMOS LOS ROLES QUE PUEDE ASUMIR
                                                                                                                            $rowRolesAsumidos = $bd->VerRegRest("ORDEN_TRANSPORTE_ROLES_ASUMIDOS", "ROL = 'Proveedor' AND ID_ORDEN_TRANSPORTE = $rowOTsAccionesPendientes->ID_ORDEN_TRANSPORTE", "No");

                                                                                                                            if ($rowRolesAsumidos->TRANSPORTISTA_INLAND_ASUMIDO == 1):

                                                                                                                                //SI SE ASUME EL ROL DEL AGENTE ADUANAL
                                                                                                                                $arrOTsAccionesPendientes[] = $rowOTsAccionesPendientes->ID_ORDEN_TRANSPORTE;

                                                                                                                            endif;

                                                                                                                        elseif ($rowOTsAccionesPendientes->ID_PROVEEDOR_FORWARDER == $administrador->ID_PROVEEDOR):

                                                                                                                            //SI EL PROVEEDOR SE CORRESPONDE CON EL PROVEEDOR DEL MATERIAL, OBTENEMOS LOS ROLES QUE PUEDE ASUMIR
                                                                                                                            $rowRolesAsumidos = $bd->VerRegRest("ORDEN_TRANSPORTE_ROLES_ASUMIDOS", "ROL = 'Forwarder' AND ID_ORDEN_TRANSPORTE = $rowOTsAccionesPendientes->ID_ORDEN_TRANSPORTE", "No");

                                                                                                                            if ($rowRolesAsumidos->TRANSPORTISTA_INLAND_ASUMIDO == 1):

                                                                                                                                //SI SE ASUME EL ROL DEL AGENTE ADUANAL
                                                                                                                                $arrOTsAccionesPendientes[] = $rowOTsAccionesPendientes->ID_ORDEN_TRANSPORTE;

                                                                                                                            endif;

                                                                                                                        endif;

                                                                                                                    endif;

                                                                                                                endwhile;

                                                                                                            else:

                                                                                                                //SI EL USUARIO NO ES PROVEEDOR, ENTONCES COMPROBAMOS SI EXISTEN OTS CON ACCIONES PENDIENTES
                                                                                                                if ($fila_sub_sub_submenu->ZONA_TABLA == "ProveedoresConstruccionCronogramaProveedores"):
                                                                                                                    $sqlOTsAccionesPendientes = "SELECT DISTINCT OTAA.ID_ORDEN_TRANSPORTE, OTA.TIPO_ACCION
                                                                                                                FROM ORDEN_TRANSPORTE_ACCION OTA
                                                                                                                    INNER JOIN ORDEN_TRANSPORTE_ACCION_AVISO OTAA ON OTAA.ID_ORDEN_TRANSPORTE_ACCION = OTA.ID_ORDEN_TRANSPORTE_ACCION
                                                                                                                WHERE OTAA.ESTADO_ACCION = 'Pendiente' AND OTA.TIENE_ACCION_MASIVA = 1 AND OTA.TIPO_ACCION <> 'Aceptar Documentacion' AND OTAA.BAJA = 0 AND OTA.BAJA = 0";
                                                                                                                else:
                                                                                                                    $sqlOTsAccionesPendientes = "SELECT DISTINCT OTAA.ID_ORDEN_TRANSPORTE, OTA.TIPO_ACCION
                                                                                                                FROM ORDEN_TRANSPORTE_ACCION OTA
                                                                                                                    INNER JOIN ORDEN_TRANSPORTE_ACCION_AVISO OTAA ON OTAA.ID_ORDEN_TRANSPORTE_ACCION = OTA.ID_ORDEN_TRANSPORTE_ACCION
                                                                                                                WHERE OTAA.ESTADO_ACCION = 'Pendiente' AND OTA.TIPO_ACCION <> 'Aceptar Documentacion' AND OTAA.BAJA = 0 AND OTA.BAJA = 0";
                                                                                                                endif;

                                                                                                                $resultOTsAccionesPendientes = $bd->ExecSQL($sqlOTsAccionesPendientes);
                                                                                                                $arrOTsAccionesPendientes    = array();

                                                                                                                while ($rowOTsAccionesPendientes = $bd->SigReg($resultOTsAccionesPendientes)):
                                                                                                                    $arrOTsAccionesPendientes[] = $rowOTsAccionesPendientes->ID_ORDEN_TRANSPORTE;

                                                                                                                    if ($rowOTsAccionesPendientes->TIPO_ACCION == "Modificar Documentacion" || $rowOTsAccionesPendientes->TIPO_ACCION == "Revisar Borrador BL" || $rowOTsAccionesPendientes->TIPO_ACCION == "Revisar BL Definitivo" || $rowOTsAccionesPendientes->TIPO_ACCION == "Revisar CMR" || $rowOTsAccionesPendientes->TIPO_ACCION == "Añadir Borrador BL" || $rowOTsAccionesPendientes->TIPO_ACCION == "Añadir BL Definitivo" || $rowOTsAccionesPendientes->TIPO_ACCION == "Añadir CMR" || $rowOTsAccionesPendientes->TIPO_ACCION == "Actualizar ETA" || $rowOTsAccionesPendientes->TIPO_ACCION == "Confirmar Salida Buque" || $rowOTsAccionesPendientes->TIPO_ACCION == "Confirmar Llegada Buque" || $rowOTsAccionesPendientes->TIPO_ACCION == "Aceptar/Rechazar Borrador BL" || $rowOTsAccionesPendientes->TIPO_ACCION == "Aceptar/Rechazar BL Definitivo" || $rowOTsAccionesPendientes->TIPO_ACCION == "Aceptar/Rechazar Documentacion Proveedor"):

                                                                                                                        $arrOTsAccionesDocumentalesPendientes[] = $rowOTsAccionesPendientes->ID_ORDEN_TRANSPORTE;

                                                                                                                    endif;

                                                                                                                endwhile;

                                                                                                            endif;

                                                                                                        endif;

                                                                                                        //SI ES PROVEEDOR NORDEX, TIENE UN COMPORTAMIENTO DIFERENTE
                                                                                                        $nombrePantalla       = "";
                                                                                                        $hitosPendientes      = false;
                                                                                                        $mostrarTransportesGC = true;
                                                                                                        $arrHitosPendientes   = array();

                                                                                                        if ($administrador->esProveedor()):

                                                                                                            //OBTENGO EL PROVEEDOR
                                                                                                            $NotificaErrorPorEmail = "No";
                                                                                                            $rowProveedor          = $bd->VerReg("PROVEEDOR", "ID_PROVEEDOR", $administrador->ID_PROVEEDOR, "No");

                                                                                                            if ((($fila_sub_sub_submenu->ZONA_TABLA == "TransporteConstruccionTransporteGCProveedor") && ($rowProveedor->REFERENCIA == "V-EW01")) || ($fila_sub_sub_submenu->ZONA_TABLA == "TransporteConstruccionTransporteGCForwarder") || ($fila_sub_sub_submenu->ZONA_TABLA == "TransporteConstruccionTransporteGCAgenteAduanal")):
                                                                                                                $nombrePantalla = "Embarques GC";

                                                                                                                //OBTENEMOS LOS EMBARQUES ACTIVOS DEL SISTEMA
                                                                                                                $sqlEmbarques    = "SELECT *
                                                                                                        FROM EMBARQUE
                                                                                                        WHERE BAJA = 0 AND (ID_PROVEEDOR = $administrador->ID_PROVEEDOR OR ID_FORWARDER = $administrador->ID_PROVEEDOR OR ID_EMPRESA_ADUANAL = $administrador->ID_PROVEEDOR)";
                                                                                                                $resultEmbarques = $bd->ExecSQL($sqlEmbarques);

                                                                                                                while ($rowEmbarques = $bd->SigReg($resultEmbarques)):

                                                                                                                    if (!$hitosPendientes):
                                                                                                                        //OBTENEMOS LOS HITOS PENDIENTES DE REALIZAR
                                                                                                                        $arrHitosPendientes = $orden_transporte->getHitosPendientes($rowEmbarques);

                                                                                                                        $hitosPendientes = (count( (array)$arrHitosPendientes) > 0 ? true : false);
                                                                                                                    endif;

                                                                                                                endwhile;

                                                                                                                $mostrarTransportesGC = true;

                                                                                                            else:

                                                                                                                $nombrePantalla       = $fila_sub_sub_submenu->NOMBRE;
                                                                                                                $mostrarTransportesGC = false;

                                                                                                            endif;

                                                                                                        else:

                                                                                                            $nombrePantalla = $fila_sub_sub_submenu->NOMBRE;

                                                                                                            if ($fila_sub_sub_submenu->ZONA_TABLA == "TransporteConstruccionTransporteGCProveedor"):

                                                                                                                //OBTENEMOS LOS EMBARQUES ACTIVOS DEL SISTEMA
                                                                                                                $sqlEmbarques    = "SELECT *
                                                                                                                FROM EMBARQUE
                                                                                                                WHERE BAJA = 0";
                                                                                                                $resultEmbarques = $bd->ExecSQL($sqlEmbarques);

                                                                                                                while ($rowEmbarques = $bd->SigReg($resultEmbarques)):

                                                                                                                    if (!$hitosPendientes):
                                                                                                                        //OBTENEMOS LOS HITOS PENDIENTES DE REALIZAR
                                                                                                                        $arrHitosPendientes = $orden_transporte->getHitosPendientes($rowEmbarques);

                                                                                                                        $hitosPendientes = (count( (array)$arrHitosPendientes) > 0 ? true : false);
                                                                                                                    endif;

                                                                                                                endwhile;

                                                                                                                $mostrarTransportesGC = true;

                                                                                                            endif;

                                                                                                        endif;

                                                                                                        ?>
                                                                                                        <tr>
                                                                                                            <td width="18%"
                                                                                                                height="23"></td>

                                                                                                            <? if ($ZonaTabla == $fila_sub_sub_submenu->ZONA_TABLA): ?>
                                                                                                                <? if ($fila_sub_sub_submenu->ZONA_TABLA == "TransporteConstruccionTransporteGCProveedor"): ?>
                                                                                                                    <? if ($mostrarTransportesGC): ?>
                                                                                                                        <td valign='top'
                                                                                                                            width='82%'
                                                                                                                            class='blanco'>
                                                                                                                            <div align='left'>
                                                                                                                                <a href='<?php
                                                                                                                                if ($fila_sub_sub_submenu->ENTORNO_CODEIGNITER == '1'):
                                                                                                                                    echo $pathRaiz . "tunel_codeigniter.php?idMenu=" . $fila_sub_sub_submenu->ID_MENU;
                                                                                                                                else:
                                                                                                                                    echo $pathRaiz . $fila_sub_sub_submenu->LINK;
                                                                                                                                endif; ?>'
                                                                                                                                   class='switch'><span
                                                                                                                                        class=senalado32>&nbsp;
                                                                                                    <?= $auxiliar->traduce($nombrePantalla, $administrador->ID_IDIOMA) ?>
                                                                                                </span>
                                                                                                                                    <? if (($fila_sub_sub_submenu->ZONA_TABLA == "TransporteConstruccionTransporteGCProveedor") && ($hitosPendientes)): ?>
                                                                                                                                        <a style="float: right;">
                                                                                                                                            <img id="img_warning"
                                                                                                                                                 src="<? echo $pathRaiz ?>imagenes/warning.png"
                                                                                                                                                 style="margin-right: 5px;margin-top: 2px;width:16px;float: right;"
                                                                                                                                                 class="parpadeante"
                                                                                                                                                 title="<?= $auxiliar->traduce("Existen hitos pendientes de resolver", $administrador->ID_IDIOMA) ?>">
                                                                                                                                        </a>
                                                                                                                                    <? endif; ?>
                                                                                                                                </a>
                                                                                                                            </div>
                                                                                                                        </td>
                                                                                                                    <? endif; ?>
                                                                                                                <? else: ?>
                                                                                                                    <td valign='top'
                                                                                                                        width='82%'
                                                                                                                        class='blanco'>
                                                                                                                        <div align='left'>
                                                                                                                            <a href='<?php
                                                                                                                            if ($fila_sub_sub_submenu->ENTORNO_CODEIGNITER == '1'):
                                                                                                                                echo $pathRaiz . "tunel_codeigniter.php?idMenu=" . $fila_sub_sub_submenu->ID_MENU;
                                                                                                                            else:
                                                                                                                                echo $pathRaiz . $fila_sub_sub_submenu->LINK;
                                                                                                                            endif; ?>'
                                                                                                                               class='switch'><span
                                                                                                                                    class=senalado32>&nbsp;
                                                                                                    <?= $auxiliar->traduce($nombrePantalla, $administrador->ID_IDIOMA) ?>
                                                                                                </span>
                                                                                                                                <? if (($fila_sub_sub_submenu->ZONA_TABLA == "ProveedoresConstruccionCronogramaProveedores") && (count( (array)$arrOTsAccionesPendientes) > 0)): ?>
                                                                                                                                    <a style="float: right;">
                                                                                                                                        <img id="img_warning"
                                                                                                                                             src="<? echo $pathRaiz ?>imagenes/warning.png"
                                                                                                                                             style="margin-right: 5px;margin-top: 2px;width:16px;float: right;"
                                                                                                                                             class="parpadeante"
                                                                                                                                             title="<?= $auxiliar->traduce("Existen OTs con acciones pendientes de resolver", $administrador->ID_IDIOMA) ?>">
                                                                                                                                    </a>
                                                                                                                                <? elseif (($fila_sub_sub_submenu->ZONA_TABLA == "ProveedoresAvisosConstruccionProveedores") && ($bd->NumRegs($resultAcciones) > 0)): ?>
                                                                                                                                    <a style="float: right;">
                                                                                                                                        <img id="img_warning"
                                                                                                                                             src="<? echo $pathRaiz ?>imagenes/warning.png"
                                                                                                                                             style="margin-right: 5px;margin-top: 2px;width:16px;float: right;"
                                                                                                                                             class="parpadeante"
                                                                                                                                             title="<?= $auxiliar->traduce("Existen acciones pendientes de resolver", $administrador->ID_IDIOMA) ?>">
                                                                                                                                    </a>
                                                                                                                                <? endif; ?>
                                                                                                                            </a>
                                                                                                                        </div>
                                                                                                                    </td>
                                                                                                                <? endif; ?>
                                                                                                            <? else: ?>
                                                                                                                <? if ($fila_sub_sub_submenu->ZONA_TABLA == "TransporteConstruccionTransporteGCProveedor"): ?>
                                                                                                                    <? if ($mostrarTransportesGC): ?>
                                                                                                                        <td width='82%'
                                                                                                                            height='23'
                                                                                                                            align=left
                                                                                                                            valign='top'>
                                                                                                                            <a href='<?php
                                                                                                                            if ($fila_sub_sub_submenu->ENTORNO_CODEIGNITER == '1'):
                                                                                                                                echo $pathRaiz . "tunel_codeigniter.php?idMenu=" . $fila_sub_sub_submenu->ID_MENU;
                                                                                                                            else:
                                                                                                                                echo $pathRaiz . $fila_sub_sub_submenu->LINK;
                                                                                                                            endif; ?>'
                                                                                                                               class='switch'><SPAN
                                                                                                                                    class=white32><SPAN
                                                                                                                                        class=blackc32>&nbsp;
                                                                                                    <?= $auxiliar->traduce($nombrePantalla, $administrador->ID_IDIOMA) ?>
                                                                                                </SPAN></SPAN>
                                                                                                                                <? if (($fila_sub_sub_submenu->ZONA_TABLA == "TransporteConstruccionTransporteGCProveedor") && ($hitosPendientes)): ?>
                                                                                                                                    <a style="float: right;">
                                                                                                                                        <img id="img_warning_gc"
                                                                                                                                             src="<? echo $pathRaiz ?>imagenes/warning.png"
                                                                                                                                             style="margin-right: 5px;margin-top: 2px;width:16px;float: right;"
                                                                                                                                             class="parpadeante"
                                                                                                                                             title="<?= $auxiliar->traduce("Existen hitos pendientes de resolver", $administrador->ID_IDIOMA) ?>">
                                                                                                                                    </a>
                                                                                                                                <? endif; ?>
                                                                                                                            </a>
                                                                                                                        </td>
                                                                                                                    <? endif; ?>
                                                                                                                <? else: ?>
                                                                                                                    <td width='82%'
                                                                                                                        height='23'
                                                                                                                        align=left
                                                                                                                        valign='top'>
                                                                                                                        <a href='<?php
                                                                                                                        if ($fila_sub_sub_submenu->ENTORNO_CODEIGNITER == '1'):
                                                                                                                            echo $pathRaiz . "tunel_codeigniter.php?idMenu=" . $fila_sub_sub_submenu->ID_MENU;
                                                                                                                        else:
                                                                                                                            echo $pathRaiz . $fila_sub_sub_submenu->LINK;
                                                                                                                        endif; ?>'
                                                                                                                           class='switch'><SPAN
                                                                                                                                class=white32><SPAN
                                                                                                                                    class=blackc32>&nbsp;
                                                                                                    <?= $auxiliar->traduce($nombrePantalla, $administrador->ID_IDIOMA) ?>
                                                                                                </SPAN></SPAN>
                                                                                                                            <? if (($fila_sub_sub_submenu->ZONA_TABLA == "ProveedoresConstruccionCronogramaProveedores") && (count( (array)$arrOTsAccionesPendientes) > 0)): ?>
                                                                                                                                <a style="float: right;">
                                                                                                                                    <img id="img_warning_cronograma"
                                                                                                                                         src="<? echo $pathRaiz ?>imagenes/warning.png"
                                                                                                                                         style="margin-right: 5px;margin-top: 2px;width:16px;float: right;"
                                                                                                                                         class="parpadeante"
                                                                                                                                         title="<?= $auxiliar->traduce("Existen OTs con acciones pendientes de resolver", $administrador->ID_IDIOMA) ?>">
                                                                                                                                </a>
                                                                                                                            <? elseif (($fila_sub_sub_submenu->ZONA_TABLA == "ProveedoresAvisosConstruccionProveedores") && ($bd->NumRegs($resultAcciones) > 0)): ?>
                                                                                                                                <a style="float: right;">
                                                                                                                                    <img id="img_warning_acciones"
                                                                                                                                         src="<? echo $pathRaiz ?>imagenes/warning.png"
                                                                                                                                         style="margin-right: 5px;margin-top: 2px;width:16px;float: right;"
                                                                                                                                         class="parpadeante"
                                                                                                                                         title="<?= $auxiliar->traduce("Existen acciones pendientes de resolver", $administrador->ID_IDIOMA) ?>">
                                                                                                                                </a>
                                                                                                                            <? endif; ?>
                                                                                                                        </a>
                                                                                                                    </td>
                                                                                                                <? endif; ?>
                                                                                                            <? endif; ?>
                                                                                                        </tr>
                                                                                                    <? endif; ?>
                                                                                                <? endforeach; ?>
                                                                                            </table>
                                                                                        </td>
                                                                                    </tr>
                                                                                <?
                                                                                endif;
                                                                            else: ?>
                                                                                <tr class="<?= $fila_sub_submenu->ID_MENU ?>">
                                                                                    <td class="subsubhijos" <?= ($fila_sub_submenu->ZONA_TABLA != $ZonaSubSubTablaPadre && !$administrador->esProveedor()) ? 'style="display:none;"' : '' ?>>

                                                                                        <table cellpadding="0"
                                                                                               cellspacing="0"
                                                                                               width="100%">
                                                                                            <?
                                                                                            foreach ($arr_datos_sub_submenu['SubMenus3'] as $idSubMenu3 => $arr_datos_sub_sub_submenu):
                                                                                                if ($arr_datos_sub_sub_submenu['Mostrar'] == 1):

                                                                                                    //DATOS DEL SUBMENU
                                                                                                    $fila_sub_sub_submenu = $arr_datos_sub_sub_submenu['SubMenu3'];

                                                                                                    if ($fila_sub_sub_submenu->ZONA_TABLA == "ProveedoresAvisosConstruccionProveedores"):

                                                                                                        $sqlAcciones = "SELECT OT.ID_ORDEN_TRANSPORTE, OTC.ID_PROVEEDOR, OTC.ID_PROVEEDOR_FORWARDER, OTC.ID_PROVEEDOR_AGENTE_ADUANAL, OT.ID_AGENCIA, OTA.TIPO_DESTINATARIO, OTAA.ID_ORDEN_TRANSPORTE_ACCION_AVISO
                                                                                                FROM ORDEN_TRANSPORTE_ACCION OTA
                                                                                                    INNER JOIN ORDEN_TRANSPORTE_ACCION_AVISO OTAA ON OTAA.ID_ORDEN_TRANSPORTE_ACCION = OTA.ID_ORDEN_TRANSPORTE_ACCION
                                                                                                    INNER JOIN ORDEN_TRANSPORTE OT ON OT.ID_ORDEN_TRANSPORTE = OTAA.ID_ORDEN_TRANSPORTE
                                                                                                    INNER JOIN ORDEN_TRANSPORTE_CONSTRUCCION OTC ON OT.ID_ORDEN_TRANSPORTE = OTC.ID_ORDEN_TRANSPORTE
                                                                                                WHERE 1 = 1 AND OTAA.ESTADO_ACCION = 'Pendiente' AND OTA.TIENE_ACCION_MASIVA = 1 AND OT.BAJA = 0 AND OTAA.BAJA = 0 AND ( (TIMESTAMPDIFF(DAY, OTAA.FECHA_RELACIONADA, NOW()) >= OTA.DIAS_BANDERA_VERDE) OR (OTA.DIAS_BANDERA_VERDE = 0) ) AND OTA.TIPO_ACCION <> 'Aceptar Documentacion' AND ( IF ( (OTA.ESTADO_TRANSPORTE = 'Puerto Origen' OR OTA.ESTADO_TRANSPORTE = 'Transito Internacional') AND (OTA.NO_AFECTA_ESTADOS = 0), OT.CON_EMBARQUE_GC <> 1, 1)) AND (OTA.TIPO_ACCION = 'Modificar Documentacion' OR OTA.TIPO_ACCION = 'Revisar Borrador BL' OR OTA.TIPO_ACCION = 'Revisar BL Definitivo' OR OTA.TIPO_ACCION = 'Revisar CMR' OR OTA.TIPO_ACCION = 'Añadir Borrador BL' OR OTA.TIPO_ACCION = 'Añadir BL Definitivo' OR OTA.TIPO_ACCION = 'Añadir CMR' OR OTA.TIPO_ACCION = 'Actualizar ETA' OR OTA.TIPO_ACCION = 'Confirmar Salida Buque' OR OTA.TIPO_ACCION = 'Confirmar Llegada Buque' OR OTA.TIPO_ACCION = 'Generar Transbordo' OR OTA.TIPO_ACCION = 'Aceptar/Rechazar Borrador BL' OR OTA.TIPO_ACCION = 'Aceptar/Rechazar BL Definitivo' OR OTA.TIPO_ACCION = 'Aceptar/Rechazar Documentacion Proveedor')" . ($administrador->esProveedor() ? " AND (OT.ID_AGENCIA = $administrador->ID_PROVEEDOR OR OTC.ID_PROVEEDOR = $administrador->ID_PROVEEDOR OR OTC.ID_PROVEEDOR_FORWARDER = $administrador->ID_PROVEEDOR OR OTC.ID_PROVEEDOR_AGENTE_ADUANAL = $administrador->ID_PROVEEDOR)" : "");

                                                                                                        if ($administrador->esProveedor()):
                                                                                                            if ($administrador->ID_PROVEEDOR != ""):

                                                                                                                $resultAcciones   = $bd->ExecSQL($sqlAcciones);
                                                                                                                $sqlRolesAsumidos = "";
                                                                                                                $coma             = "";

                                                                                                                while ($row = $bd->SigReg($resultAcciones)):

                                                                                                                    $rowRolesAsumidos = false;

                                                                                                                    if ($row->ID_PROVEEDOR == $administrador->ID_PROVEEDOR):

                                                                                                                        //OBTENEMOS LOS ROLES ASUMIDOS
                                                                                                                        $rowRolesAsumidos = $bd->VerRegRest("ORDEN_TRANSPORTE_ROLES_ASUMIDOS", "ROL = 'Proveedor' AND ID_ORDEN_TRANSPORTE = $row->ID_ORDEN_TRANSPORTE", "No");

                                                                                                                    elseif ($row->ID_PROVEEDOR_FORWARDER == $administrador->ID_PROVEEDOR):

                                                                                                                        //OBTENEMOS LOS ROLES ASUMIDOS
                                                                                                                        $rowRolesAsumidos = $bd->VerRegRest("ORDEN_TRANSPORTE_ROLES_ASUMIDOS", "ROL = 'Forwarder' AND ID_ORDEN_TRANSPORTE = $row->ID_ORDEN_TRANSPORTE", "No");

                                                                                                                    elseif ($row->ID_PROVEEDOR_AGENTE_ADUANAL == $administrador->ID_PROVEEDOR):

                                                                                                                        //OBTENEMOS LOS ROLES ASUMIDOS
                                                                                                                        $rowRolesAsumidos = $bd->VerRegRest("ORDEN_TRANSPORTE_ROLES_ASUMIDOS", "ROL = 'Agente Aduanal' AND ID_ORDEN_TRANSPORTE = $row->ID_ORDEN_TRANSPORTE", "No");

                                                                                                                    endif;

                                                                                                                    if (($row->TIPO_DESTINATARIO == "Proveedor del Material") && ($rowRolesAsumidos->PROVEEDOR_ASUMIDO == 1)):

                                                                                                                        $sqlRolesAsumidos .= $coma . $row->ID_ORDEN_TRANSPORTE_ACCION_AVISO;
                                                                                                                        $coma             = ",";

                                                                                                                    elseif (($row->TIPO_DESTINATARIO == "Forwarder") && ($rowRolesAsumidos->FORWARDER_ASUMIDO == 1)):

                                                                                                                        $sqlRolesAsumidos .= $coma . $row->ID_ORDEN_TRANSPORTE_ACCION_AVISO;
                                                                                                                        $coma             = ",";

                                                                                                                    elseif (($row->TIPO_DESTINATARIO == "Agente Aduanal") && ($rowRolesAsumidos->AGENTE_ADUANAL_ASUMIDO == 1)):

                                                                                                                        $sqlRolesAsumidos .= $coma . $row->ID_ORDEN_TRANSPORTE_ACCION_AVISO;
                                                                                                                        $coma             = ",";

                                                                                                                    elseif (($row->TIPO_DESTINATARIO == "Transportista Inland") && ($rowRolesAsumidos->TRANSPORTISTA_INLAND_ASUMIDO == 1)):

                                                                                                                        $sqlRolesAsumidos .= $coma . $row->ID_ORDEN_TRANSPORTE_ACCION_AVISO;
                                                                                                                        $coma             = ",";

                                                                                                                    endif;

                                                                                                                endwhile;

                                                                                                                $sqlAcciones .= ($sqlRolesAsumidos != "" ? " AND OTAA.ID_ORDEN_TRANSPORTE_ACCION_AVISO IN ($sqlRolesAsumidos)" : " AND FALSE ");

                                                                                                            endif;
                                                                                                        endif;

                                                                                                        //EJECUTAMOS LA CONSULTA
                                                                                                        $resultAcciones = $bd->ExecSQL($sqlAcciones);

                                                                                                    endif;

                                                                                                    if (($fila_sub_sub_submenu->ZONA_TABLA == "ProveedoresConstruccionCronogramaProveedores") || ($fila_sub_sub_submenu->ZONA_TABLA == "ProveedoresAvisosConstruccionProveedores")):

                                                                                                        //SI ES EL CRONOGRAMA DE PROVEEDORES, ENTONCES COMPRUEBO SI TIENE ACCIONES PENDIENTES
                                                                                                        if ($administrador->esProveedor()):

                                                                                                            //SI ES PROVEEDOR, COMPRUEBO SI PUEDE DELEGAR ALGUNA ACCIÓN
                                                                                                            if ($fila_sub_sub_submenu->ZONA_TABLA == "ProveedoresConstruccionCronogramaProveedores"):
                                                                                                                $sqlOTsAccionesPendientes = "SELECT OTAA.ID_ORDEN_TRANSPORTE, OTA.TIPO_ACCION, OTA.TIPO_DESTINATARIO, OTC.ID_PROVEEDOR, OTC.ID_PROVEEDOR_FORWARDER, OTC.ID_PROVEEDOR_AGENTE_ADUANAL, OT.ID_AGENCIA
                                                                                                                                FROM ORDEN_TRANSPORTE_ACCION OTA
                                                                                                                                    INNER JOIN ORDEN_TRANSPORTE_ACCION_AVISO OTAA ON OTAA.ID_ORDEN_TRANSPORTE_ACCION = OTA.ID_ORDEN_TRANSPORTE_ACCION
                                                                                                                                    INNER JOIN ORDEN_TRANSPORTE OT ON OT.ID_ORDEN_TRANSPORTE = OTAA.ID_ORDEN_TRANSPORTE
                                                                                                                                    INNER JOIN ORDEN_TRANSPORTE_CONSTRUCCION OTC ON OTC.ID_ORDEN_TRANSPORTE = OT.ID_ORDEN_TRANSPORTE
                                                                                                                                WHERE OTAA.ESTADO_ACCION = 'Pendiente' AND OTA.TIENE_ACCION_MASIVA = 1 AND OTA.TIPO_ACCION <> 'Aceptar Documentacion' AND (OTC.ID_PROVEEDOR = $administrador->ID_PROVEEDOR OR OTC.ID_PROVEEDOR_FORWARDER = $administrador->ID_PROVEEDOR OR OTC.ID_PROVEEDOR_AGENTE_ADUANAL = $administrador->ID_PROVEEDOR OR OT.ID_AGENCIA = $administrador->ID_PROVEEDOR) AND OTAA.BAJA = 0 AND OTA.BAJA = 0 AND OT.BAJA = 0";
                                                                                                            else:
                                                                                                                $sqlOTsAccionesPendientes = "SELECT OTAA.ID_ORDEN_TRANSPORTE, OTA.TIPO_ACCION, OTA.TIPO_DESTINATARIO, OTC.ID_PROVEEDOR, OTC.ID_PROVEEDOR_FORWARDER, OTC.ID_PROVEEDOR_AGENTE_ADUANAL, OT.ID_AGENCIA
                                                                                                                                FROM ORDEN_TRANSPORTE_ACCION OTA
                                                                                                                                    INNER JOIN ORDEN_TRANSPORTE_ACCION_AVISO OTAA ON OTAA.ID_ORDEN_TRANSPORTE_ACCION = OTA.ID_ORDEN_TRANSPORTE_ACCION
                                                                                                                                    INNER JOIN ORDEN_TRANSPORTE OT ON OT.ID_ORDEN_TRANSPORTE = OTAA.ID_ORDEN_TRANSPORTE
                                                                                                                                    INNER JOIN ORDEN_TRANSPORTE_CONSTRUCCION OTC ON OTC.ID_ORDEN_TRANSPORTE = OT.ID_ORDEN_TRANSPORTE
                                                                                                                                WHERE OTAA.ESTADO_ACCION = 'Pendiente' AND OTA.TIPO_ACCION <> 'Aceptar Documentacion' AND (OTC.ID_PROVEEDOR = $administrador->ID_PROVEEDOR OR OTC.ID_PROVEEDOR_FORWARDER = $administrador->ID_PROVEEDOR OR OTC.ID_PROVEEDOR_AGENTE_ADUANAL = $administrador->ID_PROVEEDOR OR OT.ID_AGENCIA = $administrador->ID_PROVEEDOR) AND OTAA.BAJA = 0 AND OTA.BAJA = 0 AND OT.BAJA = 0";
                                                                                                            endif;

                                                                                                            $resultOTsAccionesPendientes = $bd->ExecSQL($sqlOTsAccionesPendientes);
                                                                                                            $arrOTsAccionesPendientes    = array();

                                                                                                            while ($rowOTsAccionesPendientes = $bd->SigReg($resultOTsAccionesPendientes)):

                                                                                                                if (($rowOTsAccionesPendientes->TIPO_DESTINATARIO == "Proveedor del Material") && !in_array($rowOTsAccionesPendientes->ID_ORDEN_TRANSPORTE, (array) $arrOTsAccionesPendientes)):

                                                                                                                    if ($rowOTsAccionesPendientes->ID_PROVEEDOR == $administrador->ID_PROVEEDOR):

                                                                                                                        //SI EL PROVEEDOR SE CORRESPONDE CON EL PROVEEDOR DEL MATERIAL
                                                                                                                        $arrOTsAccionesPendientes[] = $rowOTsAccionesPendientes->ID_ORDEN_TRANSPORTE;

                                                                                                                    endif;

                                                                                                                elseif (($rowOTsAccionesPendientes->TIPO_DESTINATARIO == "Forwarder") && !in_array($rowOTsAccionesPendientes->ID_ORDEN_TRANSPORTE, (array) $arrOTsAccionesPendientes)):

                                                                                                                    if ($rowOTsAccionesPendientes->ID_PROVEEDOR_FORWARDER == $administrador->ID_PROVEEDOR):

                                                                                                                        //SI EL PROVEEDOR SE CORRESPONDE CON EL FORWARDER
                                                                                                                        $arrOTsAccionesPendientes[] = $rowOTsAccionesPendientes->ID_ORDEN_TRANSPORTE;

                                                                                                                    elseif ($rowOTsAccionesPendientes->ID_PROVEEDOR == $administrador->ID_PROVEEDOR):

                                                                                                                        //SI EL PROVEEDOR SE CORRESPONDE CON EL PROVEEDOR DEL MATERIAL, OBTENEMOS LOS ROLES QUE PUEDE ASUMIR
                                                                                                                        $rowRolesAsumidos = $bd->VerRegRest("ORDEN_TRANSPORTE_ROLES_ASUMIDOS", "ROL = 'Proveedor' AND ID_ORDEN_TRANSPORTE = $rowOTsAccionesPendientes->ID_ORDEN_TRANSPORTE", "No");

                                                                                                                        if ($rowRolesAsumidos->FORWARDER_ASUMIDO == 1):

                                                                                                                            //SI SE ASUME EL ROL DEL FORWARDER
                                                                                                                            $arrOTsAccionesPendientes[] = $rowOTsAccionesPendientes->ID_ORDEN_TRANSPORTE;

                                                                                                                        endif;

                                                                                                                    endif;

                                                                                                                elseif (($rowOTsAccionesPendientes->TIPO_DESTINATARIO == "Agente Aduanal") && !in_array($rowOTsAccionesPendientes->ID_ORDEN_TRANSPORTE, (array) $arrOTsAccionesPendientes)):

                                                                                                                    if ($rowOTsAccionesPendientes->ID_PROVEEDOR_AGENTE_ADUANAL == $administrador->ID_PROVEEDOR):

                                                                                                                        //SI EL PROVEEDOR SE CORRESPONDE CON EL AGENTE ADUANAL
                                                                                                                        $arrOTsAccionesPendientes[] = $rowOTsAccionesPendientes->ID_ORDEN_TRANSPORTE;

                                                                                                                    elseif ($rowOTsAccionesPendientes->ID_PROVEEDOR_FORWARDER == $administrador->ID_PROVEEDOR):

                                                                                                                        //SI EL PROVEEDOR SE CORRESPONDE CON EL FORWARDER, OBTENEMOS LOS ROLES QUE PUEDE ASUMIR
                                                                                                                        $rowRolesAsumidos = $bd->VerRegRest("ORDEN_TRANSPORTE_ROLES_ASUMIDOS", "ROL = 'Forwarder' AND ID_ORDEN_TRANSPORTE = $rowOTsAccionesPendientes->ID_ORDEN_TRANSPORTE", "No");

                                                                                                                    endif;

                                                                                                                elseif (($rowOTsAccionesPendientes->TIPO_DESTINATARIO == "Transportista Inland") && !in_array($rowOTsAccionesPendientes->ID_ORDEN_TRANSPORTE, (array) $arrOTsAccionesPendientes)):

                                                                                                                    if ($rowOTsAccionesPendientes->ID_AGENCIA == $administrador->ID_PROVEEDOR):

                                                                                                                        //SI EL PROVEEDOR SE CORRESPONDE CON EL TRANSPORTISTA INLAND
                                                                                                                        $arrOTsAccionesPendientes[] = $rowOTsAccionesPendientes->ID_ORDEN_TRANSPORTE;

                                                                                                                    elseif ($rowOTsAccionesPendientes->ID_PROVEEDOR == $administrador->ID_PROVEEDOR):

                                                                                                                        //SI EL PROVEEDOR SE CORRESPONDE CON EL PROVEEDOR DEL MATERIAL, OBTENEMOS LOS ROLES QUE PUEDE ASUMIR
                                                                                                                        $rowRolesAsumidos = $bd->VerRegRest("ORDEN_TRANSPORTE_ROLES_ASUMIDOS", "ROL = 'Proveedor' AND ID_ORDEN_TRANSPORTE = $rowOTsAccionesPendientes->ID_ORDEN_TRANSPORTE", "No");

                                                                                                                        if ($rowRolesAsumidos->TRANSPORTISTA_INLAND_ASUMIDO == 1):

                                                                                                                            //SI SE ASUME EL ROL DEL AGENTE ADUANAL
                                                                                                                            $arrOTsAccionesPendientes[] = $rowOTsAccionesPendientes->ID_ORDEN_TRANSPORTE;

                                                                                                                        endif;

                                                                                                                    elseif ($rowOTsAccionesPendientes->ID_PROVEEDOR_FORWARDER == $administrador->ID_PROVEEDOR):

                                                                                                                        //SI EL PROVEEDOR SE CORRESPONDE CON EL PROVEEDOR DEL MATERIAL, OBTENEMOS LOS ROLES QUE PUEDE ASUMIR
                                                                                                                        $rowRolesAsumidos = $bd->VerRegRest("ORDEN_TRANSPORTE_ROLES_ASUMIDOS", "ROL = 'Forwarder' AND ID_ORDEN_TRANSPORTE = $rowOTsAccionesPendientes->ID_ORDEN_TRANSPORTE", "No");

                                                                                                                        if ($rowRolesAsumidos->TRANSPORTISTA_INLAND_ASUMIDO == 1):

                                                                                                                            //SI SE ASUME EL ROL DEL AGENTE ADUANAL
                                                                                                                            $arrOTsAccionesPendientes[] = $rowOTsAccionesPendientes->ID_ORDEN_TRANSPORTE;

                                                                                                                        endif;

                                                                                                                    endif;

                                                                                                                endif;

                                                                                                            endwhile;

                                                                                                        else:

                                                                                                            //SI EL USUARIO NO ES PROVEEDOR, ENTONCES COMPROBAMOS SI EXISTEN OTS CON ACCIONES PENDIENTES
                                                                                                            if ($fila_sub_sub_submenu->ZONA_TABLA == "ProveedoresConstruccionCronogramaProveedores"):
                                                                                                                $sqlOTsAccionesPendientes = "SELECT DISTINCT OTAA.ID_ORDEN_TRANSPORTE, OTA.TIPO_ACCION
                                                                                                                FROM ORDEN_TRANSPORTE_ACCION OTA
                                                                                                                    INNER JOIN ORDEN_TRANSPORTE_ACCION_AVISO OTAA ON OTAA.ID_ORDEN_TRANSPORTE_ACCION = OTA.ID_ORDEN_TRANSPORTE_ACCION
                                                                                                                WHERE OTAA.ESTADO_ACCION = 'Pendiente' AND OTA.TIENE_ACCION_MASIVA = 1 AND OTA.TIPO_ACCION <> 'Aceptar Documentacion' AND OTAA.BAJA = 0 AND OTA.BAJA = 0";
                                                                                                            else:
                                                                                                                $sqlOTsAccionesPendientes = "SELECT DISTINCT OTAA.ID_ORDEN_TRANSPORTE, OTA.TIPO_ACCION
                                                                                                                FROM ORDEN_TRANSPORTE_ACCION OTA
                                                                                                                    INNER JOIN ORDEN_TRANSPORTE_ACCION_AVISO OTAA ON OTAA.ID_ORDEN_TRANSPORTE_ACCION = OTA.ID_ORDEN_TRANSPORTE_ACCION
                                                                                                                WHERE OTAA.ESTADO_ACCION = 'Pendiente' AND OTA.TIPO_ACCION <> 'Aceptar Documentacion' AND OTAA.BAJA = 0 AND OTA.BAJA = 0";
                                                                                                            endif;

                                                                                                            $resultOTsAccionesPendientes = $bd->ExecSQL($sqlOTsAccionesPendientes);
                                                                                                            $arrOTsAccionesPendientes    = array();

                                                                                                            while ($rowOTsAccionesPendientes = $bd->SigReg($resultOTsAccionesPendientes)):
                                                                                                                $arrOTsAccionesPendientes[] = $rowOTsAccionesPendientes->ID_ORDEN_TRANSPORTE;

                                                                                                                if ($rowOTsAccionesPendientes->TIPO_ACCION == "Modificar Documentacion" || $rowOTsAccionesPendientes->TIPO_ACCION == "Revisar Borrador BL" || $rowOTsAccionesPendientes->TIPO_ACCION == "Revisar BL Definitivo" || $rowOTsAccionesPendientes->TIPO_ACCION == "Revisar CMR" || $rowOTsAccionesPendientes->TIPO_ACCION == "Añadir Borrador BL" || $rowOTsAccionesPendientes->TIPO_ACCION == "Añadir BL Definitivo" || $rowOTsAccionesPendientes->TIPO_ACCION == "Añadir CMR" || $rowOTsAccionesPendientes->TIPO_ACCION == "Actualizar ETA" || $rowOTsAccionesPendientes->TIPO_ACCION == "Confirmar Salida Buque" || $rowOTsAccionesPendientes->TIPO_ACCION == "Confirmar Llegada Buque" || $rowOTsAccionesPendientes->TIPO_ACCION == "Aceptar/Rechazar Borrador BL" || $rowOTsAccionesPendientes->TIPO_ACCION == "Aceptar/Rechazar BL Definitivo" || $rowOTsAccionesPendientes->TIPO_ACCION == "Aceptar/Rechazar Documentacion Proveedor"):

                                                                                                                    $arrOTsAccionesDocumentalesPendientes[] = $rowOTsAccionesPendientes->ID_ORDEN_TRANSPORTE;

                                                                                                                endif;

                                                                                                            endwhile;

                                                                                                        endif;

                                                                                                    endif;

                                                                                                    //SI ES PROVEEDOR NORDEX, TIENE UN COMPORTAMIENTO DIFERENTE
                                                                                                    $nombrePantalla       = "";
                                                                                                    $hitosPendientes      = false;
                                                                                                    $mostrarTransportesGC = true;
                                                                                                    $arrHitosPendientes   = array();

                                                                                                    if ($administrador->esProveedor()):

                                                                                                        //OBTENGO EL PROVEEDOR
                                                                                                        $NotificaErrorPorEmail = "No";
                                                                                                        $rowProveedor          = $bd->VerReg("PROVEEDOR", "ID_PROVEEDOR", $administrador->ID_PROVEEDOR, "No");

                                                                                                        if ((($fila_sub_sub_submenu->ZONA_TABLA == "TransporteConstruccionTransporteGCProveedor") && ($rowProveedor->REFERENCIA == "V-EW01")) || ($fila_sub_sub_submenu->ZONA_TABLA == "TransporteConstruccionTransporteGCForwarder") || ($fila_sub_sub_submenu->ZONA_TABLA == "TransporteConstruccionTransporteGCAgenteAduanal")):
                                                                                                            $nombrePantalla = "Embarques GC";

                                                                                                            //OBTENEMOS LOS EMBARQUES ACTIVOS DEL SISTEMA
                                                                                                            $sqlEmbarques    = "SELECT *
                                                                                                        FROM EMBARQUE
                                                                                                        WHERE BAJA = 0 AND (ID_PROVEEDOR = $administrador->ID_PROVEEDOR OR ID_FORWARDER = $administrador->ID_PROVEEDOR OR ID_EMPRESA_ADUANAL = $administrador->ID_PROVEEDOR)";
                                                                                                            $resultEmbarques = $bd->ExecSQL($sqlEmbarques);

                                                                                                            while ($rowEmbarques = $bd->SigReg($resultEmbarques)):

                                                                                                                if (!$hitosPendientes):
                                                                                                                    //OBTENEMOS LOS HITOS PENDIENTES DE REALIZAR
                                                                                                                    $arrHitosPendientes = $orden_transporte->getHitosPendientes($rowEmbarques);

                                                                                                                    $hitosPendientes = (count( (array)$arrHitosPendientes) > 0 ? true : false);
                                                                                                                endif;

                                                                                                            endwhile;

                                                                                                            $mostrarTransportesGC = true;

                                                                                                        else:

                                                                                                            $nombrePantalla       = $fila_sub_sub_submenu->NOMBRE;
                                                                                                            $mostrarTransportesGC = false;

                                                                                                        endif;

                                                                                                    else:

                                                                                                        $nombrePantalla = $fila_sub_sub_submenu->NOMBRE;

                                                                                                        if ($fila_sub_sub_submenu->ZONA_TABLA == "TransporteConstruccionTransporteGCProveedor"):

                                                                                                            //OBTENEMOS LOS EMBARQUES ACTIVOS DEL SISTEMA
                                                                                                            $sqlEmbarques    = "SELECT *
                                                                                                                FROM EMBARQUE
                                                                                                                WHERE BAJA = 0";
                                                                                                            $resultEmbarques = $bd->ExecSQL($sqlEmbarques);

                                                                                                            while ($rowEmbarques = $bd->SigReg($resultEmbarques)):

                                                                                                                if (!$hitosPendientes):
                                                                                                                    //OBTENEMOS LOS HITOS PENDIENTES DE REALIZAR
                                                                                                                    $arrHitosPendientes = $orden_transporte->getHitosPendientes($rowEmbarques);

                                                                                                                    $hitosPendientes = (count( (array)$arrHitosPendientes) > 0 ? true : false);
                                                                                                                endif;

                                                                                                            endwhile;

                                                                                                            $mostrarTransportesGC = true;

                                                                                                        endif;

                                                                                                    endif;

                                                                                                    ?>
                                                                                                    <tr>
                                                                                                        <td width="18%"
                                                                                                            height="23"></td>

                                                                                                        <? if ($ZonaTabla == $fila_sub_sub_submenu->ZONA_TABLA): ?>
                                                                                                            <? if ($fila_sub_sub_submenu->ZONA_TABLA == "TransporteConstruccionTransporteGCProveedor"): ?>
                                                                                                                <? if ($mostrarTransportesGC): ?>
                                                                                                                    <td valign='top'
                                                                                                                        width='82%'
                                                                                                                        class='blanco'>
                                                                                                                        <div align='left'>
                                                                                                                            <a href='<?php
                                                                                                                            if ($fila_sub_sub_submenu->ENTORNO_CODEIGNITER == '1'):
                                                                                                                                echo $pathRaiz . "tunel_codeigniter.php?idMenu=" . $fila_sub_sub_submenu->ID_MENU;
                                                                                                                            else:
                                                                                                                                echo $pathRaiz . $fila_sub_sub_submenu->LINK;
                                                                                                                            endif; ?>'
                                                                                                                               class='switch'><span
                                                                                                                                    class=senalado32>&nbsp;
                                                                                                    <?= $auxiliar->traduce($nombrePantalla, $administrador->ID_IDIOMA) ?>
                                                                                                </span>
                                                                                                                                <? if (($fila_sub_sub_submenu->ZONA_TABLA == "TransporteConstruccionTransporteGCProveedor") && ($hitosPendientes)): ?>
                                                                                                                                    <a style="float: right;">
                                                                                                                                        <img id="img_warning"
                                                                                                                                             src="<? echo $pathRaiz ?>imagenes/warning.png"
                                                                                                                                             style="margin-right: 5px;margin-top: 2px;width:16px;float: right;"
                                                                                                                                             class="parpadeante"
                                                                                                                                             title="<?= $auxiliar->traduce("Existen hitos pendientes de resolver", $administrador->ID_IDIOMA) ?>">
                                                                                                                                    </a>
                                                                                                                                <? endif; ?>
                                                                                                                            </a>
                                                                                                                        </div>
                                                                                                                    </td>
                                                                                                                <? endif; ?>
                                                                                                            <? else: ?>
                                                                                                                <td valign='top'
                                                                                                                    width='82%'
                                                                                                                    class='blanco'>
                                                                                                                    <div align='left'>
                                                                                                                        <a href='<?php
                                                                                                                        if ($fila_sub_sub_submenu->ENTORNO_CODEIGNITER == '1'):
                                                                                                                            echo $pathRaiz . "tunel_codeigniter.php?idMenu=" . $fila_sub_sub_submenu->ID_MENU;
                                                                                                                        else:
                                                                                                                            echo $pathRaiz . $fila_sub_sub_submenu->LINK;
                                                                                                                        endif; ?>'
                                                                                                                           class='switch'><span
                                                                                                                                class=senalado32>&nbsp;
                                                                                                    <?= $auxiliar->traduce($nombrePantalla, $administrador->ID_IDIOMA) ?>
                                                                                                </span>
                                                                                                                            <? if (($fila_sub_sub_submenu->ZONA_TABLA == "ProveedoresConstruccionCronogramaProveedores") && (count( (array)$arrOTsAccionesPendientes) > 0)): ?>
                                                                                                                                <a style="float: right;">
                                                                                                                                    <img id="img_warning"
                                                                                                                                         src="<? echo $pathRaiz ?>imagenes/warning.png"
                                                                                                                                         style="margin-right: 5px;margin-top: 2px;width:16px;float: right;"
                                                                                                                                         class="parpadeante"
                                                                                                                                         title="<?= $auxiliar->traduce("Existen OTs con acciones pendientes de resolver", $administrador->ID_IDIOMA) ?>">
                                                                                                                                </a>
                                                                                                                            <? elseif (($fila_sub_sub_submenu->ZONA_TABLA == "ProveedoresAvisosConstruccionProveedores") && ($bd->NumRegs($resultAcciones) > 0)): ?>
                                                                                                                                <a style="float: right;">
                                                                                                                                    <img id="img_warning"
                                                                                                                                         src="<? echo $pathRaiz ?>imagenes/warning.png"
                                                                                                                                         style="margin-right: 5px;margin-top: 2px;width:16px;float: right;"
                                                                                                                                         class="parpadeante"
                                                                                                                                         title="<?= $auxiliar->traduce("Existen acciones pendientes de resolver", $administrador->ID_IDIOMA) ?>">
                                                                                                                                </a>
                                                                                                                            <? endif; ?>
                                                                                                                        </a>
                                                                                                                    </div>
                                                                                                                </td>
                                                                                                            <? endif; ?>
                                                                                                        <? else: ?>
                                                                                                            <? if ($fila_sub_sub_submenu->ZONA_TABLA == "TransporteConstruccionTransporteGCProveedor"): ?>
                                                                                                                <? if ($mostrarTransportesGC): ?>
                                                                                                                    <td width='82%'
                                                                                                                        height='23'
                                                                                                                        align=left
                                                                                                                        valign='top'>
                                                                                                                        <a href='<?php
                                                                                                                        if ($fila_sub_sub_submenu->ENTORNO_CODEIGNITER == '1'):
                                                                                                                            echo $pathRaiz . "tunel_codeigniter.php?idMenu=" . $fila_sub_sub_submenu->ID_MENU;
                                                                                                                        else:
                                                                                                                            echo $pathRaiz . $fila_sub_sub_submenu->LINK;
                                                                                                                        endif; ?>'
                                                                                                                           class='switch'><SPAN
                                                                                                                                class=white32><SPAN
                                                                                                                                    class=blackc32>&nbsp;
                                                                                                    <?= $auxiliar->traduce($nombrePantalla, $administrador->ID_IDIOMA) ?>
                                                                                                </SPAN></SPAN>
                                                                                                                            <? if (($fila_sub_sub_submenu->ZONA_TABLA == "TransporteConstruccionTransporteGCProveedor") && ($hitosPendientes)): ?>
                                                                                                                                <a style="float: right;">
                                                                                                                                    <img id="img_warning_gc"
                                                                                                                                         src="<? echo $pathRaiz ?>imagenes/warning.png"
                                                                                                                                         style="margin-right: 5px;margin-top: 2px;width:16px;float: right;"
                                                                                                                                         class="parpadeante"
                                                                                                                                         title="<?= $auxiliar->traduce("Existen hitos pendientes de resolver", $administrador->ID_IDIOMA) ?>">
                                                                                                                                </a>
                                                                                                                            <? endif; ?>
                                                                                                                        </a>
                                                                                                                    </td>
                                                                                                                <? endif; ?>
                                                                                                            <? else: ?>
                                                                                                                <td width='82%'
                                                                                                                    height='23'
                                                                                                                    align=left
                                                                                                                    valign='top'>
                                                                                                                    <a href='<?php
                                                                                                                    if ($fila_sub_sub_submenu->ENTORNO_CODEIGNITER == '1'):
                                                                                                                        echo $pathRaiz . "tunel_codeigniter.php?idMenu=" . $fila_sub_sub_submenu->ID_MENU;
                                                                                                                    else:
                                                                                                                        echo $pathRaiz . $fila_sub_sub_submenu->LINK;
                                                                                                                    endif; ?>'
                                                                                                                       class='switch'><SPAN
                                                                                                                            class=white32><SPAN
                                                                                                                                class=blackc32>&nbsp;
                                                                                                    <?= $auxiliar->traduce($nombrePantalla, $administrador->ID_IDIOMA) ?>
                                                                                                </SPAN></SPAN>
                                                                                                                        <? if (($fila_sub_sub_submenu->ZONA_TABLA == "ProveedoresConstruccionCronogramaProveedores") && (count( (array)$arrOTsAccionesPendientes) > 0)): ?>
                                                                                                                            <a style="float: right;">
                                                                                                                                <img id="img_warning_cronograma"
                                                                                                                                     src="<? echo $pathRaiz ?>imagenes/warning.png"
                                                                                                                                     style="margin-right: 5px;margin-top: 2px;width:16px;float: right;"
                                                                                                                                     class="parpadeante"
                                                                                                                                     title="<?= $auxiliar->traduce("Existen OTs con acciones pendientes de resolver", $administrador->ID_IDIOMA) ?>">
                                                                                                                            </a>
                                                                                                                        <? elseif (($fila_sub_sub_submenu->ZONA_TABLA == "ProveedoresAvisosConstruccionProveedores") && ($bd->NumRegs($resultAcciones) > 0)): ?>
                                                                                                                            <a style="float: right;">
                                                                                                                                <img id="img_warning_acciones"
                                                                                                                                     src="<? echo $pathRaiz ?>imagenes/warning.png"
                                                                                                                                     style="margin-right: 5px;margin-top: 2px;width:16px;float: right;"
                                                                                                                                     class="parpadeante"
                                                                                                                                     title="<?= $auxiliar->traduce("Existen acciones pendientes de resolver", $administrador->ID_IDIOMA) ?>">
                                                                                                                            </a>
                                                                                                                        <? elseif ($administrador->Hayar_Permiso_Perfil('ADM_CODIFICACION_OPERACIONES_ACCIONES') > 0 && ($fila_sub_sub_submenu->ZONA_TABLA == "CodificacionOperacionesAcciones") && ($bd->NumRegs($resultAccionesCodificacion) > 0)): ?>
                                                                                                                            <a style="float: right;">
                                                                                                                                <img id="img_warning_acciones"
                                                                                                                                     src="<? echo $pathRaiz ?>imagenes/warning.png"
                                                                                                                                     style="margin-right: 5px;margin-top: 2px;width:16px;float: right;"
                                                                                                                                     class="parpadeante"
                                                                                                                                     title="<?= $auxiliar->traduce("Existen acciones pendientes de resolver", $administrador->ID_IDIOMA) ?>">
                                                                                                                            </a>
                                                                                                                        <? endif; ?>
                                                                                                                    </a>
                                                                                                                </td>
                                                                                                            <? endif; ?>
                                                                                                        <? endif; ?>
                                                                                                    </tr>
                                                                                                <? endif; ?>
                                                                                            <? endforeach; ?>
                                                                                        </table>
                                                                                    </td>
                                                                                </tr>
                                                                            <?
                                                                            endif;
                                                                        endif;//MOSTRAR HIJOS DEL MENÚ DADO EL ID_PADRE
                                                                        ?>
                                                                    <? else: ?>
                                                                        <tr>
                                                                            <td width="12%" height="23"></td>

                                                                            <? if ($ZonaTabla == $fila_sub_submenu->ZONA_TABLA): ?>
                                                                                <td valign='top' width='88%'
                                                                                    class='blanco'>
                                                                                    <div align='left'>
                                                                                        <a href='<?php
                                                                                        if ($fila_sub_submenu->ENTORNO_CODEIGNITER == '1'):
                                                                                            echo $pathRaiz . "tunel_codeigniter.php?idMenu=" . $fila_sub_submenu->ID_MENU;
                                                                                        else:
                                                                                            echo $pathRaiz . $fila_sub_submenu->LINK;
                                                                                        endif; ?>'
                                                                                           class='switch'><span
                                                                                                class=senalado22>&nbsp;<?= $auxiliar->traduce($fila_sub_submenu->NOMBRE, $administrador->ID_IDIOMA) ?></span>
                                                                                            <? if ($administrador->Hayar_Permiso_Perfil('ADM_CODIFICACION_OPERACIONES_ACCIONES') > 0 && ($fila_sub_submenu->ZONA_TABLA == "CodificacionOperacionesAcciones") && ($bd->NumRegs($resultAccionesCodificacion) > 0)): ?>
                                                                                                <a style="float: right;">
                                                                                                    <img id="img_warning_acciones"
                                                                                                         src="<? echo $pathRaiz ?>imagenes/warning.png"
                                                                                                         style="margin-right: 5px;margin-top: 2px;width:16px;float: right;"
                                                                                                         class="parpadeante"
                                                                                                         title="<?= $auxiliar->traduce("Existen acciones pendientes de resolver", $administrador->ID_IDIOMA) ?>">
                                                                                                </a>
                                                                                            <?endif;?>
                                                                                        </a>
                                                                                    </div>
                                                                                </td>
                                                                            <? else: ?>
                                                                                <td width='88%' height='23' align=left
                                                                                    valign='top'>
                                                                                    <a href='<?php
                                                                                    if ($fila_sub_submenu->ENTORNO_CODEIGNITER == '1'):
                                                                                        echo $pathRaiz . "tunel_codeigniter.php?idMenu=" . $fila_sub_submenu->ID_MENU;
                                                                                    else:
                                                                                        echo $pathRaiz . $fila_sub_submenu->LINK;
                                                                                    endif; ?>'
                                                                                       class='switch'><SPAN
                                                                                            class=white22><SPAN
                                                                                                class=blackc22>&nbsp;<?= $auxiliar->traduce($fila_sub_submenu->NOMBRE, $administrador->ID_IDIOMA) ?></SPAN></SPAN>
                                                                                        <? if ($administrador->Hayar_Permiso_Perfil('ADM_CODIFICACION_OPERACIONES_ACCIONES') > 0 && ($fila_sub_submenu->ZONA_TABLA == "CodificacionOperacionesAcciones") && ($bd->NumRegs($resultAccionesCodificacion) > 0)): ?>
                                                                                            <a style="float: right;">
                                                                                                <img id="img_warning_acciones"
                                                                                                     src="<? echo $pathRaiz ?>imagenes/warning.png"
                                                                                                     style="margin-right: 5px;margin-top: 2px;width:16px;float: right;"
                                                                                                     class="parpadeante"
                                                                                                     title="<?= $auxiliar->traduce("Existen acciones pendientes de resolver", $administrador->ID_IDIOMA) ?>">
                                                                                            </a>
                                                                                        <?endif;?>
                                                                                    </a>
                                                                                </td>
                                                                            <? endif; ?>
                                                                        </tr>

                                                                    <?
                                                                    endif;
                                                                endif;
                                                            endforeach;
                                                            //DESPLEGABLE AUTOMÁTICO
                                                            if (count( (array)$arr_datos_menu['SubMenus']) > 0):
                                                                ?>
                                                                </table>
                                                                </td>
                                                                </tr>
                                                            <? endif;

                                                        else: //TIENE/NO TIENE SUBMENU
                                                            ?>
                                                            <tr>
                                                                <td width="<?= ($submenus > 0) ? "12%" : "6%" ?>"
                                                                    height="23"
                                                                    align="left"></td>

                                                                <? if ($ZonaTabla == $fila_submenu->ZONA_TABLA): ?>
                                                                    <td valign='top'
                                                                        width='<?= ($submenus > 0) ? "88%" : "94%" ?>'
                                                                        class='blanco'>
                                                                        <div align='left'>
                                                                            <a href='<?php
                                                                            if ($fila_submenu->ENTORNO_CODEIGNITER == '1'):
                                                                                echo $pathRaiz . "tunel_codeigniter.php?idMenu=" . $fila_submenu->ID_MENU;
                                                                            else:
                                                                                echo $pathRaiz . $fila_submenu->LINK;
                                                                            endif; ?>'
                                                                               class='switch'><span
                                                                                    class=<?= ($submenus > 0) ? "senalado22" : "senaladov22" ?>>&nbsp;<?= $auxiliar->traduce($fila_submenu->NOMBRE, $administrador->ID_IDIOMA) ?></span>
                                                                                <? if ($administrador->Hayar_Permiso_Perfil('ADM_CODIFICACION_OPERACIONES_ACCIONES') > 0 && ($fila_submenu->ZONA_TABLA == "CodificacionOperaciones") && ($bd->NumRegs($resultAccionesCodificacion) > 0)): ?>
                                                                                    <a style="float: right;">
                                                                                        <img id="img_warning_acciones"
                                                                                             src="<? echo $pathRaiz ?>imagenes/warning.png"
                                                                                             style="margin-right: 5px;margin-top: 2px;width:16px;float: right;"
                                                                                             class="parpadeante"
                                                                                             title="<?= $auxiliar->traduce("Existen acciones pendientes de resolver", $administrador->ID_IDIOMA) ?>">
                                                                                    </a>
                                                                                <?endif;?>
                                                                            </a>
                                                                        </div>
                                                                    </td>
                                                                <? else: ?>
                                                                    <td width='<?= ($submenus > 0) ? "88%" : "94%" ?>'
                                                                        height='23'
                                                                        align=left valign='top'>
                                                                        <a href='<?php
                                                                        if ($fila_submenu->ENTORNO_CODEIGNITER == '1'):
                                                                            echo $pathRaiz . "tunel_codeigniter.php?idMenu=" . $fila_submenu->ID_MENU;
                                                                        else:
                                                                            echo $pathRaiz . $fila_submenu->LINK;
                                                                        endif; ?>'
                                                                           class='switch'><SPAN
                                                                                class=<?= ($submenus > 0) ? "white22" : "whitev22" ?>><SPAN
                                                                                    class=<?= ($submenus > 0) ? "blackc22" : "blackcv22" ?>>&nbsp;<?= $auxiliar->traduce($fila_submenu->NOMBRE, $administrador->ID_IDIOMA) ?></SPAN></SPAN>
                                                                            <? if ($administrador->Hayar_Permiso_Perfil('ADM_CODIFICACION_OPERACIONES_ACCIONES') > 0 && ($fila_submenu->ZONA_TABLA == "CodificacionOperaciones") && ($bd->NumRegs($resultAccionesCodificacion) > 0)): ?>
                                                                                <a style="float: right;">
                                                                                    <img id="img_warning_acciones"
                                                                                         src="<? echo $pathRaiz ?>imagenes/warning.png"
                                                                                         style="margin-right: 5px;margin-top: 2px;width:16px;float: right;"
                                                                                         class="parpadeante"
                                                                                         title="<?= $auxiliar->traduce("Existen acciones pendientes de resolver", $administrador->ID_IDIOMA) ?>">
                                                                                </a>
                                                                            <?endif;?>
                                                                        </a>
                                                                    </td>
                                                                <? endif; ?>

                                                            </tr>
                                                        <?
                                                        endif;//TIENE/NO TIENE SUBMENU
                                                    endif;//FIN VISIBILIDAD
                                                endforeach;

                                                //DESPLEGABLE AUTOMÁTICO
                                                ?>
                                            </table>
                                        </td>
                                    </tr>
                                <? endif;
                            endif;
                        endforeach;
                    endif;
                    ?>

                    <tr align="left">
                        <td height="10" colspan="2"><img src="<? echo $pathRaiz ?>imagenes/transparente.gif" width="10"
                                                         height="9"></td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td height="40" align="right" valign="top" class="lineabajoderecha">&nbsp;</td>
        </tr>
    </table>
</td>
