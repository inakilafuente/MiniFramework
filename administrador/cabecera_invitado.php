<? if (ENTORNO_WEB == 'PRODUCCION'):
    $colorCabecera = "#FFFFFF";
elseif (ENTORNO_WEB == 'DESARROLLO'):
    $colorCabecera = "#5d00ff";
elseif (ENTORNO_WEB == 'INTEGRACION'):
    $colorCabecera = "#d4ff00";
endif;
?>
<table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
    <?
    //COMPRUEBO SI EL SISTEMA ESTA BLOQUEADO
    $sistemaBloqueado = false;

    //HAYO LA FECHA Y HORA ACTUAL
    $fechaActualBloqueoSistema = date("Y-m-d H:i:s");

    $sqlBloqueoSistema    = "SELECT * FROM AVISO_SISTEMA_BLOQUEADO";
    $resultBloqueoSistema = $bd->ExecSQL($sqlBloqueoSistema);
    $rowBloqueoSistema    = $bd->SigReg($resultBloqueoSistema);

    $fechaHoraInicialBloqueoSistema = $rowBloqueoSistema->FECHA_INICIO_BLOQUEO . " " . $rowBloqueoSistema->HORA_INICIO_BLOQUEO;
    $fechaHoraFinalBloqueoSistema   = $rowBloqueoSistema->FECHA_FIN_BLOQUEO . " " . $rowBloqueoSistema->HORA_FIN_BLOQUEO;

    if (($rowBloqueoSistema->ACTIVO == 1) && ($fechaActualBloqueoSistema > $fechaHoraInicialBloqueoSistema) && ($fechaActualBloqueoSistema < $fechaHoraFinalBloqueoSistema)): //ESTAMOS EN EL RANGO DEFINIDO POR EL BLOQUEO Y ACTIVADO
        $sistemaBloqueado = true;
    endif;
    ?>

    <? if ($sistemaBloqueado == true): ?>
        <tr>
            <td width="33%" height="1" align="center"
                bgcolor="<?= $colorCabecera; ?>">
                &nbsp;</td>
            <td align="center" bgcolor="<?= $colorCabecera; ?>">
                <table width="936" height="32" border="0" cellpadding="0" cellspacing="0">
                    <tr>
                        <td align="center" valign="middle"><font
                                id="texto_parpadeo"><font id="texto_parpadeo"
                                                          style="font-weight:bold;color:red"><? echo "¡ " . $auxiliar->traduce("Sistema Bloqueado", $administrador->ID_IDIOMA) . " !"; ?></font>
                        </td>
                    </tr>
                </table>
            </td>
            <td width="33%" align="center" bgcolor="<?= $colorCabecera; ?>">
                &nbsp;</td>
        </tr>
    <? endif; ?>
    <tr>
        <td width="33%" height="1" align="center"
            bgcolor="<?= $colorCabecera; ?>">
            &nbsp;</td>
        <td align="center" bgcolor="<?= $colorCabecera; ?>">
            <table width="936" height="32" border="0" cellpadding="0" cellspacing="0">
                <tr>
                    <td align="right" valign="middle"><span class="textoencimaimagen"><?= $tituloPag; ?></span><img
                            src="<? echo $pathRaiz ?>imagenes/fondo_cabecera_solicitud.jpg"/>
                    </td>
                </tr>
            </table>
        </td>
        <td width="33%" align="center" bgcolor="<?= $colorCabecera; ?>">
            &nbsp;</td>
    </tr>
    <tr>
        <td height="2" colspan="4" width="100%" align="center" valign="top" class="lineanegra"><img
                src="<? echo $pathRaiz ?>imagenes/transparente.gif" width="10" height="3"></td>
    </tr>
    <tr>
        <td height="20px"></td>
    </tr>
</table>