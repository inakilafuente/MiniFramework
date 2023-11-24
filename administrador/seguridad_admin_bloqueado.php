<?
//COMPRUEBA QUE EL USUARIO ESTE AUTENTIFICADO
// PAGINA SOLO ACCESIBLE POR EL ADMINISTRADOR
if ($_SESSION["AUTH_ACCIONA_SGA_ADMINISTRADOR"] != "OK" || !isset($bd) || trim( (string)$administrador->ID_ADMINISTRADOR) == ""):
    //si no existe, envio a la página de autentificacion
    header("Location: $url_web_adm");
    //ademas salgo de este script
    exit();
endif;

//COMPRUEBO QUE SE ESTA UTILIZANDO UN NAVEGADOR COMPATIBLE
$navegador = $bd->ObtenerNavegador($_SERVER['HTTP_USER_AGENT']);
if ($navegador == "Internet Explorer"):
    $urlError = $url_web_adm . "error_out.php?TipoError=NavegadorNoCompatible";
    header("Location: $urlError");
    exit;
endif;

//BUSCO EL PERFIL PERMITIDO (ID:17 -> (A) Gestor Avanzado)
if (($administrador->ID_ADMINISTRADOR_PERFIL != 17) && ($administrador->ID_ADMINISTRADOR_PERFIL != 1)):
    $urlError = $url_web_adm . "error_out.php?TipoError=PerfilNoAdmitido";
    header("Location: $urlError");
    exit;
endif;

$bd->conectar();