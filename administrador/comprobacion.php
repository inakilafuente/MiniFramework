<?
header('Content-Type: text/html;charset=windows-1252');

// PATHS DE LA WEB
$pathRaiz   = "./";
$pathClases = "../";

// INCLUDES DE LIBRERIAS PROPIAS
require_once $pathClases . "lib/basedatos.php";
require_once $pathClases . "lib/auxiliar.php";
require_once $pathClases . "lib/comprobar.php";
require_once $pathClases . "lib/material.php";
require_once $pathClases . "lib/ubicacion.php";
require_once $pathClases . "lib/pedido.php";
require_once $pathClases . "lib/html.php";
require_once $pathClases . "lib/navegar.php";
require_once $pathClases . "lib/gestor.php";
require_once $pathClases . "lib/cliente.php";
require_once $pathClases . "lib/administrador.php";
require_once $pathClases . "lib/lineas_etiqueta.php";
require_once $pathClases . "lib/linea_bulto.php";
require_once $pathClases . "lib/aviso.php";
require_once $pathClases . "lib/reserva.php";
require_once $pathClases . "lib/expedicion.php";
require_once $pathClases . "lib/movimiento.php";
require_once $pathClases . "lib/observaciones_sistema.php";

//EXTRAEMOS VARIABLES
if (!empty($_SESSION)) extract($_SESSION);
if (!empty($_REQUEST)) extract($_REQUEST);
if (!empty($_FILES)) extract($_FILES);

// TODAVIA NO ESTA EN LA SESION
$bd                 = new basedatos();
$bd->pagina_inicial = "Si"; // PARA QUE NO DE ERROR DE NO CADUCADA, SOLO EN LAS PAG INCIAL
$bd->conectar();
$html = new html();

//SACAMOS LA CONTRASEÑA ENCRIPTADA DEL USUARIO
$rowAdmin              = $bd->VerReg("ADMINISTRADOR", "LOGIN",$bd->escapeCondicional($txLogin), "No");
$passwordEncriptado = $rowAdmin -> PASSWD_PLANO;

//COMPARAMOS LAS CONTRASEÑAS
if( $passwordEncriptado != NULL ):
    $coincidePassword = password_verify( $bd->escapeCondicional($txPassword),$passwordEncriptado);
    if ($coincidePassword == false):
        $passwordComprobada = 0;
    else:
        $passwordComprobada = 1;
    endif;
else:
    $passwordComprobada = 0;
endif;

$sql          = "SELECT *,date_format(ULTIMA_FECHA,'%d-%m-%Y') as ftoULTIMA_FECHA,date_format(ULTIMA_FECHA,'%H:%i') as ftoULTIMA_HORA FROM ADMINISTRADOR WHERE BAJA=0 AND STRCMP(LOGIN,'" . $bd->escapeCondicional($txLogin) . "')=0 AND (". $passwordComprobada . " = 1)";

$Pagina_Error = "error_out.php";
$result       = $bd->ExecSQL($sql);

if ($bd->NumRegs($result) > 0): // DATOS CORRECTOS

    $row = $bd->SigReg($result);
    $html->PagErrorCond($row->BLOQUEO_USER, 1, "UsuarioBloqueado");

    session_destroy();
    session_start();

    //COMPROBAMOS SI EL PERFIL DEL USUARIO DEBE PEDIR_ALMACEN
    $sqlPedirAlmacen    = "SELECT PEDIR_ALMACEN FROM ADMINISTRADOR_PERFIL WHERE ID_ADMINISTRADOR_PERFIL = " . $row->ID_ADMINISTRADOR_PERFIL;
    $resultPedirAlmacen = $bd->ExecSQL($sqlPedirAlmacen);
    $rowPedirAlmacen    = $bd->SigReg($resultPedirAlmacen);
    if ($rowPedirAlmacen->PEDIR_ALMACEN == 1):
        //HAYO LA FECHA Y HORA ACTUAL
        $fecha = date("Y-m-d H:i:s");

        $sqlBloqueoSistema    = "SELECT * FROM AVISO_SISTEMA_BLOQUEADO";
        $resultBloqueoSistema = $bd->ExecSQL($sqlBloqueoSistema);
        $rowBloqueoSistema    = $bd->SigReg($resultBloqueoSistema);

        $fechaHoraInicial = $rowBloqueoSistema->FECHA_INICIO_BLOQUEO . " " . $rowBloqueoSistema->HORA_INICIO_BLOQUEO;
        $fechaHoraFinal   = $rowBloqueoSistema->FECHA_FIN_BLOQUEO . " " . $rowBloqueoSistema->HORA_FIN_BLOQUEO;

        if (($rowBloqueoSistema->ACTIVO == 1) && ($fecha > $fechaHoraInicial) && ($fecha < $fechaHoraFinal)): //ESTAMOS EN EL RANGO DEFINIDO POR EL BLOQUEO Y ACTIVADO
            $urlError = $url_web_adm . "error_bloqueo.php?TipoError=SistemaBloqueado";
            header("Location: $urlError");
            exit;
        endif;

        $_SESSION['LOGIN_PEDIR_ALMACEN']      = true;
        $_SESSION['ID_USUARIO_PEDIR_ALMACEN'] = $row->ID_ADMINISTRADOR;
        $_SESSION["IDIOMA_ADMINISTRADOR"]     = $RbtnIdioma;
        header("Location: index.php");

        die;
    endif;

    // ACCESO CORRECTO A LA BASE DE DATOS => LO MARCO PARA PREGUNTARLO EN TODAS LAS PAGS
    $_SESSION["AUTH_ACCIONA_SGA_ADMINISTRADOR"] = "OK";

    // CREO LOS OBJETOS
    $auxiliar = new auxiliar();
    $comp     = new comprobar();
    $navegar  = new navegar();

    if ($administrador == NULL):
        $administrador = new administrador();
    endif;

    $gestor                         = new gestor();
    $cli                            = new cliente();
    $mat                            = new material();
    $lin_eti                        = new lineas_etiqueta();
    $lin_bul                        = new linea_bulto();
    $pedido                         = new pedido();
    $ubicacion                      = new ubicacion();
    $aviso                          = new aviso();
    $reserva                        = new reserva();
    $expedicion                     = new expedicion();
    $movimiento                     = new movimiento();
    $observaciones_sistema          = new observaciones_sistema();

    // REGISTRO SESIONES
    $_SESSION['bd']                             = $bd;
    $_SESSION['auxiliar']                       = $auxiliar;
    $_SESSION['comp']                           = $comp;
    $_SESSION['html']                           = $html;
    $_SESSION['navegar']                        = $navegar;
    $_SESSION['administrador']                  = $administrador;
    $_SESSION['pedido']                         = $pedido;
    $_SESSION['ubicacion']                      = $ubicacion;
    $_SESSION['gestor']                         = $gestor;
    $_SESSION['cli']                            = $cli;
    $_SESSION['mat']                            = $mat;
    $_SESSION['lin_eti']                        = $lin_eti;
    $_SESSION['lin_bul']                        = $lin_bul;
    $_SESSION['aviso']                          = $aviso;
    $_SESSION['reserva']                        = $reserva;
    $_SESSION['expedicion']                     = $expedicion;
    $_SESSION['movimiento']                     = $movimiento;
    $_SESSION['observaciones_sistema']          = $observaciones_sistema;

    // GRABO LA ULTIMA ENTRADA DEL CLIENTE
    unset($Pagina_Error);

    // GUARDAR COOKIE IDIOMA
    setcookie("IDIOMA_USER", $RbtnIdioma, (time() + (60 * 60 * 24 * 365)));

    //REVISO SI EL SISTEMA ESTA BLOQUEADO
    $sqlBloqueoSistema    = "SELECT * FROM AVISO_SISTEMA_BLOQUEADO";
    $resultBloqueoSistema = $bd->ExecSQL($sqlBloqueoSistema);
    $rowBloqueoSistema    = $bd->SigReg($resultBloqueoSistema);

    //HAYO LA FECHA Y HORA DE INICIO Y DE FIN DEL BLOQUEO
    $fechaHoraInicial = $rowBloqueoSistema->FECHA_INICIO_BLOQUEO . " " . $rowBloqueoSistema->HORA_INICIO_BLOQUEO;
    $fechaHoraFinal   = $rowBloqueoSistema->FECHA_FIN_BLOQUEO . " " . $rowBloqueoSistema->HORA_FIN_BLOQUEO;

    //HAYO LA FECHA Y HORA ACTUAL
    $fecha = date("Y-m-d H:i:s");

    //REVISO SI EL BLOQUEO ESTA ACTIVO Y SI APLICA
    if (($rowBloqueoSistema->ACTIVO == 1) && ($fecha > $fechaHoraInicial) && ($fecha < $fechaHoraFinal)): //ESTAMOS EN EL RANGO DEFINIDO POR EL BLOQUEO Y ACTIVADO
        //REVISO SI EL USUARIO TIENE PERMISOS PARA ACCEDER DURANTE EL BLOQUEO
        if ($row->SUPERADMINISTRADOR == 0):
            $urlError = $url_web_adm . "error_bloqueo.php?TipoError=SistemaBloqueado";
            header("Location: $urlError");
            exit;
        endif;

        //NO REGISTRAMOS LA ENTRADA DE LOS SUPERADMINISTRADORES SI EL SISTEMA ESTA BLOQUEADO
        $administrador->Grabar_Entrada_OK($row, $RbtnIdioma, false);

        //NO REGISTRAMOS LA ENTRADA DE LOS SUPERADMINISTRADORES SI EL SISTEMA ESTÁ BLOQUEADO
    else:
        //REGISTRAMOS LA ENTRADA DE TODOS LOS USUARIOS
        $administrador->Grabar_Entrada_OK($row, $RbtnIdioma);

        // LOG MOVIMIENTOS
        $administrador->Insertar_Log_Movimientos($row->ID_ADMINISTRADOR, 'Acceso', "", "", "");
    endif;

    $_SESSION['estado_husos_horarios'] = $row->ESTADO_HUSO_HORARIO;

    //REDIRIJO LA PAGINA EN FUNCION DE SI TIENE PAGINA A LA QUE IR O NO
    if ((isset($_SESSION["PAGINA_A_REDIRIGIR_TRAS_LOGUEARSE"])) && ($_SESSION["PAGINA_A_REDIRIGIR_TRAS_LOGUEARSE"] != "")):
        if (!(isset($_SESSION['estado_menu']))):
            $_SESSION['estado_menu'] = 1;
        endif;
        header("Location: " . $_SESSION["PAGINA_A_REDIRIGIR_TRAS_LOGUEARSE"]);
        unset($_SESSION["PAGINA_A_REDIRIGIR_TRAS_LOGUEARSE"]);

    elseif ($administrador->esProveedor() == 1): //PROVEEDORES LES LLEVAMOS DIRECTAMENTE A SU MENU
        $_SESSION['estado_menu'] = 1;

        //REDIRIGIMOS SEGUN EL PERMISO DEL PERFIL
        if ($administrador->Hayar_Permiso_Perfil('ADM_PROVEEDORES_CONTRATACIONES') > 1):
            header("Location: proveedores/contrataciones/index.php");

        elseif ($administrador->Hayar_Permiso_Perfil('ADM_PROVEEDORES_CONSTRUCCION_PROVEEDOR') > 1):
            header("Location: transporte_construccion/avisos_construccion_proveedores/index.php");

        elseif ($administrador->Hayar_Permiso_Perfil('ADM_PROVEEDORES_CONSTRUCCION_FORWARDER') > 1):
            header("Location: transporte_construccion/avisos_construccion_proveedores/index.php");

        elseif ($administrador->Hayar_Permiso_Perfil('ADM_PROVEEDORES_CONSTRUCCION_AGENTE_ADUANAL') > 1):
            header("Location: transporte_construccion/avisos_construccion_proveedores/index.php");

        elseif ($administrador->Hayar_Permiso_Perfil('ADM_PROVEEDORES_CONSTRUCCION_INLAND') > 1):
            header("Location: transporte_construccion/avisos_construccion_proveedores/index.php");

        else:
            header("Location: bienvenida.php");

        endif;

    else:
        header("Location: bienvenida.php");
    endif;

else: // DATOS ERRONEOS

    // PUEDE QUE EXISTA EL CLIENTE O NO CON ESE LOGIN
    $NotificaErrorPorEmail = "No";
    $rowAdmin              = $bd->VerReg("ADMINISTRADOR", "LOGIN", $txLogin, "No");
    $NotificaErrorPorEmail = "";

    if ($rowAdmin != false): // EL ADMIN SI EXISTE Y ENTONCES PUEDO REGISTRAR COSAS
        $administrador                   = new administrador();
        $administrador->ID_ADMINISTRADOR = $rowAdmin->ID_ADMINISTRADOR;

        $result = $administrador->Incrementar_Intentos($rowAdmin->ID_ADMINISTRADOR);
        if ($result == "MaximoIntentosAlcanzado"): // Nª EXACTO DE BLOQUEO DEL USUARIO
            // BLOQUEO EL USUARIO
            $sql = "UPDATE ADMINISTRADOR SET BLOQUEO_USER=1 WHERE ID_ADMINISTRADOR=$rowAdmin->ID_ADMINISTRADOR";
            $bd->ExecSQL($sql);
            $html->PagError("UsuarioBloqueadoAlerta");
        endif;
        // SI BLOQUEADO LO DIGO
        $html->PagErrorCond($rowAdmin->BLOQUEO_USER, 1, "UsuarioBloqueado");
    endif;
    $html->PagError("DatosErroneos");

endif;