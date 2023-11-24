<?
header('Content-Type: text/html;charset=windows-1252');
//EXTRAMOS LAS VARIABLES
if (!empty($_SESSION)) extract($_SESSION);
if (!empty($_REQUEST)) extract($_REQUEST);
if (!empty($_FILES)):
    //Si usamos extract no funciona como esperaba el sistema, el sistema en la variable espera el tmp_name y el extract nos crea un array con todos los datos del file.
    foreach($_FILES as $nombre_varible_file => $datos_variable_file):
        $$nombre_varible_file = $datos_variable_file['tmp_name'];
    endforeach;
endif;

//COMPRUEBA QUE EL USUARIO ESTE AUTENTIFICADO
// PAGINA SOLO ACCESIBLE POR EL ADMINISTRADOR
if ($_SESSION["AUTH_ACCIONA_SGA_ADMINISTRADOR"] != "OK" || !isset($bd) || trim( (string)$administrador->ID_ADMINISTRADOR) == ""):
    //GUARDO LA PAGINA A REDIRIGIR AL USUARIO TRAS LOGUEARSE
    $_SESSION["PAGINA_A_REDIRIGIR_TRAS_LOGUEARSE"] = $_SERVER["REQUEST_URI"];

    // SI ES AL DESCARGAR UN DOCUMENTO, LE MANDAMOS A LA PAGINA ANTERIOR A CLICAR SOBRE EL DOCUMENTO
    if (isset($esDescargaDocumento) && $esDescargaDocumento === true):
        $_SESSION["PAGINA_A_REDIRIGIR_TRAS_LOGUEARSE"] = $_SERVER["HTTP_REFERER"];
    endif;

    //si no existe, envio a la pána de autentificacion
    header("Location: $url_web_adm");
    //ademas salgo de este script
    exit();
endif;

//COMPRUEBO QUE SE ESTA UTILIZANDO UN NAVEGADOR COMPATIBLE
$navegador = $bd->ObtenerNavegador($_SERVER['HTTP_USER_AGENT']);
if (($navegador == "Internet Explorer")):
    $urlError = $url_web_adm . "error_out.php?TipoError=NavegadorNoCompatible";
    header("Location: $urlError");
    exit;
endif;

//CONECTAMOS A BASE DE DATOS
$bd->conectar();

//CAMBIO DE IDIOMA
if (($administrador->ID_IDIOMA == '') || ($administrador->ID_IDIOMA == NULL)):
    $idIdioma = 'ESP';
endif;

if ($idIdioma == 'ESP'):
    $administrador->setIdioma("ESP");
elseif ($idIdioma == 'ENG'):
    $administrador->setIdioma("ENG");
endif;

//REVISO SI EL USUARIO TIENE PERMISOS PARA ACCEDER DURANTE EL BLOQUEO
if ($administrador->SUPERADMINISTRADOR == 0):
    //HAYO LA FECHA Y HORA ACTUAL
    $fechaActualBloqueoSistema = date("Y-m-d H:i:s");

    $sqlBloqueoSistema    = "SELECT * FROM AVISO_SISTEMA_BLOQUEADO";
    $resultBloqueoSistema = $bd->ExecSQL($sqlBloqueoSistema);
    $rowBloqueoSistema    = $bd->SigReg($resultBloqueoSistema);

    $fechaHoraInicialBloqueoSistema = $rowBloqueoSistema->FECHA_INICIO_BLOQUEO . " " . $rowBloqueoSistema->HORA_INICIO_BLOQUEO;
    $fechaHoraFinalBloqueoSistema   = $rowBloqueoSistema->FECHA_FIN_BLOQUEO . " " . $rowBloqueoSistema->HORA_FIN_BLOQUEO;

    if (($rowBloqueoSistema->ACTIVO == 1) && ($fechaActualBloqueoSistema > $fechaHoraInicialBloqueoSistema) && ($fechaActualBloqueoSistema < $fechaHoraFinalBloqueoSistema)):    //ESTAMOS EN EL RANGO DEFINIDO POR EL BLOQUEO Y ACTIVADO
        $urlError = $url_web_adm . "error_bloqueo.php?TipoError=SistemaBloqueado";
        header("Location: $urlError");
        exit;
    endif;
endif;


if ($administrador->esResponsableSubzona() && $administrador->TelefonoMovilAdministrador($administrador->ID_ADMINISTRADOR) == ""):
    $administrador->REDIRIGIR_A_PANTALLA_CONFIGURACION = true;
endif;
//COMPRUEBO QUE EL ADMINISTRADOR TENGA CONFIGURADOS TODOS LOS DATOS NECESARIOS, EN OTRO CASO REDIRIGO PARA QUE LOS INTRODUZCA
if ((($administrador->ID_PAIS == '') || ($administrador->ID_PAIS === NULL)
        || ($administrador->ID_EMPRESA == '') || ($administrador->ID_EMPRESA === NULL) || ($administrador->REDIRIGIR_A_PANTALLA_CONFIGURACION == true))
    && $administrador->ENTRA_SIN_CONFIGURAR_DATOS == false
):

    //COMPRUEBO QUE LA QUE LA PAGINA EN LA QUE ESTA NO ESTE ENTRE LAS HABILITADAS
    //HABILITAMOS ALGUNAS PARA QUE PUEDA RELLENAR SU DATOS, DE OTRO MODO ENTRARIA EN BUCLE INFINITO
    global $auxiliar;
    $pagina_actual = $auxiliar->currrentPageURL(); //SI ESTOY EN LA PAGINA OBTENDRE 'https://aplicaciones.vicarli.com/acciona_sga/administrador/administracion/datos_usuario/index.php'

    //REDIRIGIRÉ SIEMPRE Y CUANDO LA PAGINAS HABILITADAS NO ES CONTENIDAS COMPLETAMENTE EN LA URL ACTUAL

    if (strpos((string)$pagina_actual,(string) $url_datos_usuario) === false
        && strpos((string)$pagina_actual,(string) $url_busqueda_maestros) === false
    ):

        header("Location: $url_peticion_datos_usuario");
        //ademas salgo de este script
        exit();
    endif;

endif;