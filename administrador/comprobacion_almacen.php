<?
// PATHS DE LA WEB
$pathRaiz   = "./";
$pathClases = "../";

// INCLUDES DE LIBRERIAS PROPIAS
require_once $pathClases . "lib/basedatos.php";
require_once $pathClases . "lib/auxiliar.php";
require_once $pathClases . "lib/comprobar.php";
require_once $pathClases . "lib/material.php";
require_once $pathClases . "lib/ubicacion.php";
require_once $pathClases . "lib/stock_compartido.php";
require_once $pathClases . "lib/stock_externalizado.php";
require_once $pathClases . "lib/pedido.php";
require_once $pathClases . "lib/devolucion_entrada.php";
require_once $pathClases . "lib/html.php";
require_once $pathClases . "lib/navegar.php";
require_once $pathClases . "lib/gestor.php";
require_once $pathClases . "lib/cliente.php";
require_once $pathClases . "lib/administrador.php";
require_once $pathClases . "lib/lineas_etiqueta.php";
require_once $pathClases . "lib/linea_bulto.php";
require_once $pathClases . "lib/aviso.php";
require_once $pathClases . "lib/sap.php";
require_once $pathClases . "lib/albaran.php";
require_once $pathClases . "lib/reserva.php";
require_once $pathClases . "lib/expedicion.php";
require_once $pathClases . "lib/movimiento.php";
require_once $pathClases . "lib/informe_gestion.php";
require_once $pathClases . "lib/ordenes_transferencia.php";
require_once $pathClases . "lib/necesidad.php";
require_once $pathClases . "lib/observaciones_sistema.php";
require_once $pathClases . "lib/orden_trabajo.php";
require_once $pathClases . "lib/orden_preparacion.php";
require_once $pathClases . "lib/orden_transporte.php";
require_once $pathClases . "lib/orden_montaje.php";
require_once $pathClases . "lib/expedicion_SAP.php";
require_once $pathClases . "lib/importe.php";
require_once $pathClases . "lib/solar.php";
require_once $pathClases . "lib/incidencia_sistema.php";
require_once $pathClases . "lib/enablon.php";
require_once $pathClases . "lib/solicitud_material_ic.php";
require_once $pathClases . "lib/solicitud_material.php";
require_once $pathClases . "lib/solicitud_material_servicio.php";
require_once $pathClases . "lib/documentum.php";
require_once $pathClases . "lib/solicitud_material_sustitutivo.php";
require_once $pathClases . "lib/autostore.php";

if (!empty($_SESSION)) extract($_SESSION);
if (!empty($_REQUEST)) extract($_REQUEST);
if (!empty($_FILES)) extract($_FILES);

// TODAVIA NO ESTA EN LA SESION
$bd                 = new basedatos();
$bd->pagina_inicial = "Si"; // PARA QUE NO DE ERROR DE NO CADUCADA, SOLO EN LAS PAG INCIAL
$bd->conectar();
$html = new html();

$administrador = new administrador();
$Pagina_Error  = "error_out.php";

// COMPROBACION CODIGO ALMACÉN NO VACÍO
if ($txCodigoAlmacen == ''):
    $html->PagError("CampoCodigoObligatorio");
endif;

$sqlCodigo = "SELECT A.ID_ALMACEN,A.STOCK_COMPARTIDO,A.TIPO_STOCK,A.ID_CENTRO_FISICO FROM ALMACEN A WHERE A.CODIGO_ALMACEN_ETIQUETA = '" . $bd->escapeCondicional($txCodigoAlmacen) . "'";
$resCodigo = $bd->ExecSQL($sqlCodigo);

if ($bd->NumRegs($resCodigo) == 0):
    $html->PagError("CodigoErroneo");
else:
    $rowCodigo = $bd->SigReg($resCodigo);

    $sql          = "SELECT *,date_format(ULTIMA_FECHA,'%d-%m-%Y') as ftoULTIMA_FECHA,date_format(ULTIMA_FECHA,'%H:%i') as ftoULTIMA_HORA FROM ADMINISTRADOR WHERE BAJA=0 AND ID_ADMINISTRADOR=$idUsuario";
    $Pagina_Error = "error_out.php";
    $result       = $bd->ExecSQL($sql);

    if ($bd->NumRegs($result) > 0): // DATOS CORRECTOS

        $row = $bd->SigReg($result);
        $html->PagErrorCond($row->BLOQUEO_USER, 1, "UsuarioBloqueado");

        session_destroy();
        session_start();

        $_SESSION["idAlmacenConectadoUsuario"] = $rowCodigo->ID_ALMACEN;

        // SI SE HA CONECTADO A UN ALMACEN CON STOCK COMPARTIDO, ASIGNAMOS EL CF A LOS FILTROS
        if ($rowCodigo->STOCK_COMPARTIDO == 1):

            // MODIFICO EL REGISTRO DE LA BD
            $sql       = "UPDATE ADMINISTRADOR SET
                        ID_CENTRO_FISICO_POR_DEFECTO=" . $rowCodigo->ID_CENTRO_FISICO . "
                        WHERE ID_ADMINISTRADOR=$idUsuario";
            $TipoError = "ErrorEjecutarSql";
            $bd->ExecSQL($sql);

            //GUARDAMOS EL CF COMO FILTRO DEFECTO
            $row->ID_CENTRO_FISICO_DEFECTO = $rowCodigo->ID_CENTRO_FISICO;


            $_SESSION["idCentroFisicoConectadoUsuario"] = $rowCodigo->ID_CENTRO_FISICO;
            $_SESSION["tieneStockCompartido"]           = $rowCodigo->STOCK_COMPARTIDO;
        endif;

        // ACCESO CORRECTO A LA BASE DE DATOS => LO MARCO PARA PREGUNTARLO EN TODAS LAS PAGS
        $_SESSION["AUTH_ACCIONA_SGA_ADMINISTRADOR"] = "OK";

        // CREO LOS OBJETOS
        $auxiliar              = new auxiliar();
        $comp                  = new comprobar();
        $navegar               = new navegar();
        $administrador         = new administrador();
        $gestor                = new gestor();
        $cli                   = new cliente();
        $mat                   = new material();
        $lin_eti               = new lineas_etiqueta();
        $lin_bul               = new linea_bulto();
        $pedido                = new pedido();
        $devEnt                = new devolucionEntrada();
        $ubicacion             = new ubicacion();
        $stock_compartido      = new stock_compartido();
        $stock_externalizado   = new stock_externalizado();
        $aviso                 = new aviso();
        $sap                   = new sap();
        $albaran               = new albaran();
        $reserva               = new reserva();
        $expedicion            = new expedicion();
        $movimiento            = new movimiento();
        $inf_ges               = new informe_gestion();
        $ord_transf            = new ordenes_transferencia();
        $necesidad             = new necesidad();
        $observaciones_sistema = new observaciones_sistema();
        $orden_trabajo         = new orden_trabajo();
        $orden_preparacion     = new orden_preparacion();
        $orden_transporte      = new orden_transporte();
        $orden_montaje         = new orden_montaje();
        $exp_SAP               = new expedicion_SAP();
        $importe               = new importe();
        $solar                 = new solar();
        $incidencia_sistema    = new incidencia_sistema();
        $enablon               = new enablon();
        $solicitud_material_ic = new solicitud_material_ic();
        $solicitud_material    = new solicitud_material();
        $solicitud_material_servicio    = new solicitud_material_servicio();
        $documentum            = new documentum();
        $solicitud_material_sustitutivo = new solicitud_material_sustitutivo();
        $autostore             = new autostore();

        // REGISTRO SESIONES
        $_SESSION['bd']                    = $bd;
        $_SESSION['auxiliar']              = $auxiliar;
        $_SESSION['comp']                  = $comp;
        $_SESSION['html']                  = $html;
        $_SESSION['navegar']               = $navegar;
        $_SESSION['administrador']         = $administrador;
        $_SESSION['pedido']                = $pedido;
        $_SESSION['devEnt']                = $devEnt;
        $_SESSION['ubicacion']             = $ubicacion;
        $_SESSION['stock_compartido']      = $stock_compartido;
        $_SESSION['stock_externalizado']   = $stock_externalizado;
        $_SESSION['gestor']                = $gestor;
        $_SESSION['cli']                   = $cli;
        $_SESSION['mat']                   = $mat;
        $_SESSION['lin_eti']               = $lin_eti;
        $_SESSION['lin_bul']               = $lin_bul;
        $_SESSION['aviso']                 = $aviso;
        $_SESSION['sap']                   = $sap;
        $_SESSION['albaran']               = $albaran;
        $_SESSION['reserva']               = $reserva;
        $_SESSION['expedicion']            = $expedicion;
        $_SESSION['movimiento']            = $movimiento;
        $_SESSION['inf_ges']               = $inf_ges;
        $_SESSION['ord_transf']            = $ord_transf;
        $_SESSION['necesidad']             = $necesidad;
        $_SESSION['observaciones_sistema'] = $observaciones_sistema;
        $_SESSION['orden_trabajo']         = $orden_trabajo;
        $_SESSION['orden_transporte']      = $orden_transporte;
        $_SESSION['orden_preparacion']     = $orden_preparacion;
        $_SESSION['orden_montaje']         = $orden_montaje;
        $_SESSION['exp_SAP']               = $exp_SAP;
        $_SESSION['importe']               = $importe;
        $_SESSION['solar']                 = $solar;
        $_SESSION['incidencia_sistema']    = $incidencia_sistema;
        $_SESSION['enablon']               = $enablon;
        $_SESSION['solicitud_material_ic'] = $solicitud_material_ic;
        $_SESSION['solicitud_material']    = $solicitud_material;
        $_SESSION['solicitud_material_servicio']    = $solicitud_material_servicio;
        $_SESSION['documentum']            = $documentum;
        $_SESSION['solicitud_material_sustitutivo']  = $solicitud_material_sustitutivo;
        $_SESSION['autostore']             = $autostore;

        // LOG MOVIMIENTOS
        $administrador->Insertar_Log_Movimientos($row->ID_ADMINISTRADOR, 'Acceso', "", "", "");

        // GRABO LA ULTIMA ENTRADA DEL CLIENTE
        unset($Pagina_Error);
        $administrador->Grabar_Entrada_OK($row, $RbtnIdioma);
        $administrador->setIdioma($RbtnIdioma);
        //header ("Location: bienvenida.php");

        //REDIRIJO LA PAGINA EN FUNCION DE SI TIENE PAGINA A LA QUE IR O NO
        if ((isset($_SESSION["PAGINA_A_REDIRIGIR_TRAS_LOGUEARSE"])) && ($_SESSION["PAGINA_A_REDIRIGIR_TRAS_LOGUEARSE"] != "")):
            if (!(isset($_SESSION['estado_menu']))):
                $_SESSION['estado_menu'] = 1;
            endif;
            header("Location: " . $_SESSION["PAGINA_A_REDIRIGIR_TRAS_LOGUEARSE"]);
            unset($_SESSION["PAGINA_A_REDIRIGIR_TRAS_LOGUEARSE"]);

        elseif ($administrador->esProveedor() == 1)://PROVEEDORES LES LLEVAMOS DIRECTAMENTE A SU MENU

            //REDIRIGIMOS SEGUN EL PERMISO DEL PERFIL
            if ($administrador->Hayar_Permiso_Perfil('ADM_PROVEEDORES_CONTRATACIONES') > 1):
                header("Location: proveedores/contrataciones/index.php");

            elseif ($administrador->Hayar_Permiso_Perfil('ADM_PROVEEDORES_CONSTRUCCION_PROVEEDOR') > 1):
                header("Location: transporte_construccion/unidad_transporte/index.php?tipoPantalla=Proveedor");

            elseif ($administrador->Hayar_Permiso_Perfil('ADM_PROVEEDORES_CONSTRUCCION_FORWARDER') > 1):
                header("Location: transporte_construccion/unidad_transporte/index.php?tipoPantalla=Forwarder");

            elseif ($administrador->Hayar_Permiso_Perfil('ADM_PROVEEDORES_CONSTRUCCION_AGENTE_ADUANAL') > 1):
                header("Location: transporte_construccion/unidad_transporte/index.php?tipoPantalla=Aduana");

            elseif ($administrador->Hayar_Permiso_Perfil('ADM_PROVEEDORES_CONSTRUCCION_INLAND') > 1):
                header("Location: transporte_construccion/unidad_transporte/index.php?tipoPantalla=Inland");

            else:
                header("Location: bienvenida.php");

            endif;

        else:
            header("Location: bienvenida.php");
        endif;

    else: // DATOS ERRONEOS

        // PUEDE QUE EXISTA EL CLIENTE O NO CON ESE LOGIN
        $NotificaErrorPorEmail           = "No";
        $rowAdmin                        = $bd->VerReg("ADMINISTRADOR", "LOGIN", $txLogin, "No");
        $NotificaErrorPorEmail           = "";
        $administrador->ID_ADMINISTRADOR = $rowAdmin->ID_ADMINISTRADOR;

        if ($rowAdmin != false): // EL ADMIN SI EXISTE Y ENTONCES PUEDO REGISTRAR COSAS
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
endif;
?>