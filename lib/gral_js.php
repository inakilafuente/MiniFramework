<title>
    <? if (isset($tituloNav)):

        //OBTENGO LA ULTIMA SECCION
        $partes  = explode(">>", (string)$tituloNav);
        $seccion = $partes[count( (array)$partes) - 1];
        echo "SCS > " . $seccion;

    elseif (empty($bd->titleProv)):
        echo "ACCIONA Energía SCS";
    else:
        echo $bd->titleProv;
    endif; ?>
</title>
<meta http-equiv="cache-control" CONTENT="no-cache">
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link rel="icon" type="image/png" href="<?= $pathRaiz ?>/imagenes/icono_acciona.png"/>
<? if ($ficha_invitado == 1): ?>
    <link href="<? echo $pathRaiz ?>ficha_invitado.css?key=<?= date('i_s') ?>" rel="stylesheet" type="text/css">
<? else: ?>
    <link href="<? echo $pathRaiz ?>estilos1.css?key=<?= date('i_s') ?>" rel="stylesheet" type="text/css">
<? endif; ?>
<!--<script type="text/javascript" language="javascript" src="<?= $pathRaiz ?>../recursos/js/jquery-1.6.2.min.js"></script>-->
<script type="text/javascript" language="javascript" src="<?= $pathRaiz ?>../recursos/js/jquery-1.11.0.min.js"></script>
<script type="text/javascript" language="javascript"
        src="<?= $pathRaiz ?>../recursos/js/gral_js.js?v=1<?= date('i_s') ?>"></script>
<script type="text/javascript" language="javascript">
    var idIdioma = '<?=$administrador->ID_IDIOMA?>';
    var fmtoFecha = '<?=$administrador->FMTO_FECHA?>';
    var fmtoFechaPrimerDiaSemana = '<?=$administrador->FMTO_FECHA_PRIMER_DIA_SEMANA?>';
</script>
<!-- Multiple select -->
<link rel="stylesheet" href="<?= $pathClases; ?>recursos/js/multiselect_desplegable/multiple-select.css"
      type="text/css"/>
<script type="text/javascript" src="<?= $pathClases ?>recursos/js/multiselect_desplegable/multiple-select.js"></script>
<!-- FIN Multiple select -->

<!-- BUSQUEDA FANCYBOX -->
<script type="text/javascript"
        src="<?= $pathClases ?>recursos/js/fancybox_v2/jquery.fancybox.pack-2.1.5.js?v=2"></script>
<link rel="stylesheet" type="text/css" href="<?= $pathClases ?>recursos/js/fancybox_v2/jquery.fancybox-2.1.5.css"
      media="all"/>

<!--<script type="text/javascript" src="<?= $pathClases ?>recursos/js/fancybox/jquery.fancybox-1.3.4.js"></script>
<link rel="stylesheet" type="text/css" href="<?= $pathClases ?>recursos/js/fancybox/jquery.fancybox-1.3.4.css"
	  media="all"/>-->
