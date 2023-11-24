<?
if ($ordenar_por == ""):    // POR DEFECTO ORDENA POR LA PRIMERA COLUMNA
    $ordenar_por = 1;
    $sent_ord    = 0;
endif;
if ($sent_ord == "" || !isset($sent_ord)) $sent_ord = 0;
if ($ult_ord <> $ordenar_por):
    $sent_ord = 0;
endif;
if (($ordenar_por <> "") && ($ult_ord == $ordenar_por) && $pagina_act == "" && $Buscar <> "Si") $sent_ord = !$sent_ord;
$ult_ord = $ordenar_por;
?>