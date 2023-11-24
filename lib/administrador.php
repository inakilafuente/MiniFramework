<?php

# proveedor
# Clase proveedor contiene todas las funciones necesarias para
# la interaccion con la clase administrador
# Se incluira en las sesiones
# Marzo 2006 Carlos Arnáez

class administrador
{
    var $ID_ADMINISTRADOR;
    var $SUPERADMINISTRADOR;
    var $NOMBRE;
    var $USUARIO_SAP;
    var $ID_ADMINISTRADOR_PERFIL;
    var $LOGIN;
    var $ULTIMA_IP;
    var $ULTIMA_FECHA;
    var $ULTIMA_HORA;
    var $ULTIMO_IDIOMA;
    var $MAX_INTENTOS;
    var $ultimaBusqueda;
    var $PUEDE_VER_IMPORTES; //0 - No; 1 - Si;
    var $COMPRADOR_BPO; //0 - No; 1 - Si;
    var $ID_IDIOMA;
    var $ID_ALMACEN_POR_DEFECTO;
    var $ID_CENTRO_FISICO_POR_DEFECTO;
    var $FMTO_FECHA; //dd-mm-yyyy / mm-dd-yyyy
    var $FMTO_FECHA_PRIMER_DIA_SEMANA; //DOMINGO / LUNES
    var $ID_HUSO_HORARIO;
    var $FMTO_CSV; //USA/UK / Europeo
    var $IDIOMA_NOTIFICACIONES; // ESP/ENG
    var $ID_PROVEEDOR; // PROVEEDOR ASIGNADO A ASUARIOS PROVEEDORES

    //VARIABLES QUE DEBERA TENER CONFIGURADAS Y SE PEDIRAN AL USUARIO SI NO LOS TIENE INDICADOS
    var $ID_PAIS;
    var $ID_EMPRESA;
    var $FECHA_FIN_ACCESO_SIN_DATOS_CONFIGURADOS; //INDICARA LA FECHA TRAS LA CUAL YA NO PODRA ACCEDER A LA APLICACION SI NO CONFIGURA CIERTOS DATOS
    var $MAX_DIAS_ACCESO_SIN_DATOS_CONFIGURADOS = 3;
    var $ENTRA_SIN_CONFIGURAR_DATOS = false; //LO UTILIZAREMOS PARA SABER SI HAY QUE PEDIR DATOS CUANDO TODAVIA NO SE HA PASADO LA FECHA LIMITE
    var $REDIRIGIR_A_PANTALLA_CONFIGURACION;
    var $ESTADO_HUSO_HORARIO;  //VARIABLE PARA CONTROLAR LA VISUALIZACIÓN DE HUSOS HORARIOS AL ENTRAR EN LA APLICACION

    function __construct()
    {
        $this->MAX_INTENTOS = 8;
        //$this->ID_IDIOMA = "ESP";
        //$this->ID_IDIOMA = "ENG";

    } // Fin proveedor


    //IDIOMA ELEGIDO AL ENTRAR EN LA APLICACIÓN
    function setIdioma($idIdioma)
    {
        $this->ID_IDIOMA = $idIdioma;
    }


    function Grabar_Entrada_OK($row, $idioma, $actualizarUsuario = true)
    {
        // ALMACENO LOS DATOS DE ENTRADA DEL USUARIO
        global $bd;
        global $auxiliar;

        // GRABO LOS DATOS DEL USUARIO EN LA SESION
        $this->ID_ADMINISTRADOR   = $row->ID_ADMINISTRADOR;
        $this->SUPERADMINISTRADOR = $row->SUPERADMINISTRADOR;
        $this->NOMBRE             = $row->NOMBRE;
        $this->LOGIN              = $row->LOGIN;
        $this->USUARIO_SAP        = $row->USUARIO_SAP;

        $this->ULTIMA_IP                    = $row->ULTIMA_IP;
        $this->ULTIMA_FECHA                 = $row->ULTIMA_FECHA;
        $this->ULTIMO_IDIOMA                = $idioma;
        $this->ID_IDIOMA                    = $idioma;
        $this->ID_ADMINISTRADOR_PERFIL      = $row->ID_ADMINISTRADOR_PERFIL;
        $this->ID_ADMINISTRADOR_PERFIL_CODIFICACION     = $row->ID_ADMINISTRADOR_PERFIL_CODIFICACION;
        $this->PUEDE_VER_IMPORTES           = $row->VISUALIZACION_IMPORTES;
        $this->COMPRADOR_BPO                = $row->ES_COMPRADOR_BPO;
        $this->ID_ALMACEN_POR_DEFECTO       = $row->ID_ALMACEN_POR_DEFECTO;
        $this->ID_CENTRO_FISICO_POR_DEFECTO = $row->ID_CENTRO_FISICO_POR_DEFECTO;
        $this->FMTO_FECHA                   = $row->FMTO_FECHA;
        $this->FMTO_FECHA_PRIMER_DIA_SEMANA = $row->FMTO_FECHA_PRIMER_DIA_SEMANA;
        $this->ID_HUSO_HORARIO              = $row->ID_HUSO_HORARIO;
        $rowHusoHorario                     = $bd->VerReg("HUSO_HORARIO_", "ID_HUSO_HORARIO", $row->ID_HUSO_HORARIO);
        $this->ID_HUSO_HORARIO_PHP          = $rowHusoHorario->ID_HUSO_HORARIO_PHP;
        $this->FMTO_CSV                     = $row->FMTO_CSV;
        $this->IDIOMA_NOTIFICACIONES        = $row->IDIOMA_NOTIFICACIONES;
        $this->ID_PROVEEDOR                 = $row->ID_PROVEEDOR;

        $this->ID_PAIS                                 = $row->ID_PAIS;
        $this->ID_EMPRESA                              = $row->ID_EMPRESA;
        $this->FECHA_FIN_ACCESO_SIN_DATOS_CONFIGURADOS = $row->FECHA_FIN_ACCESO_SIN_DATOS_CONFIGURADOS;

        //SI NO TIENE FECHA DE FIN, INDICAMOS EN EL OBJETO CUAL SERÍA)
        if ($this->FECHA_FIN_ACCESO_SIN_DATOS_CONFIGURADOS == '0000-00-00' || $this->FECHA_FIN_ACCESO_SIN_DATOS_CONFIGURADOS == NULL):

            $fecha_fin = date('Y-m-d');
            $fecha_fin = strtotime((string) $fecha_fin);
            $fecha_fin = strtotime((string) "+" . ($this->MAX_DIAS_ACCESO_SIN_DATOS_CONFIGURADOS) . " day", $fecha_fin);
            $fecha_fin = date('Y-m-d', $fecha_fin);

            $this->FECHA_FIN_ACCESO_SIN_DATOS_CONFIGURADOS = $fecha_fin;
        endif;

        //A VECES ES NECESARIO QUE PASE POR LA PANTALLA DE CONFIGURACION
        $this->REDIRIGIR_A_PANTALLA_CONFIGURACION = ($row->REDIRIGIR_A_PANTALLA_CONFIGURACION == '1') ? true : false;

        $this->ESTADO_HUSO_HORARIO = $row->ESTADO_HUSO_HORARIO;

        if ($actualizarUsuario):
            // ALMACENO EN BD LOS DATOS DE LA ULTIMA ENTRADA DEL USUARIO
            $FechaEntrada = date("Y-m-d H:i:s");
            $IpEntrada    = $auxiliar->Hayar_IP();

            $sql = "UPDATE ADMINISTRADOR SET
                        INTENTOS_USER = 0
                        , ULTIMA_FECHA = '$FechaEntrada'
                        , ULTIMA_IP = '$IpEntrada'
                        , ULTIMO_IDIOMA = " . (isset($idioma) ? "'" . $idioma . "'" : 'NULL') . "
                        WHERE ID_ADMINISTRADOR = '$this->ID_ADMINISTRADOR'";
            $bd->ExecSQL($sql);
        endif;

    } // Fin Grabar_Entrada_OK


    function Insertar_Log_Movimientos_vieja($idAdmin, $tipoMovimiento, $objeto, $idObjeto, $descripcion, $nombreTabla = "")
    {

        global $bd;

        // INSERTO EL MOVIMIENTO
        $fechaAct = date("Y-m-d H:i:s");
        $sql      = "INSERT INTO LOG_MOVIMIENTOS";
        $sql      = "$sql SET ID_ADMINISTRADOR=$idAdmin,FECHA='$fechaAct',TIPO_MOVIMIENTO='$tipoMovimiento',OBJETO='$objeto',DESCRIPCION='$descripcion', NOMBRE_TABLA = '$nombreTabla'";
        if ($idObjeto <> ""):
            $sql = "$sql,ID_OBJETO=$idObjeto";
        endif;
        $bd->ExecSQL($sql);
    }

    /**
     * @param $idAdmin
     * @param $tipoMovimiento
     * @param $objeto
     * @param $idObjeto
     * @param $descripcion
     * @param string $nombreTabla TABLA QUE SE HACE LA MODIFICACION
     * @param string $rowAntigua ROW CON DATOS SIN ACTUALIZAR
     * @param string $rowActualizada ROW DATOS ACTUALIZADOS
     */
    function Insertar_Log_Movimientos($idAdmin, $tipoMovimiento, $objeto, $idObjeto, $descripcion, $nombreTabla = "", $rowAntigua = "", $rowActualizada = "", $actualizaciones = "")
    {
        global $bd;
        global $auxiliar;

        //CAMBIOS EN BBDD A REGISTRAR
        $cambios = '';
        //INSERT
        if ($rowActualizada != ""):
            $cambios = $auxiliar->obtenerCambiosRegistro($rowAntigua, $rowActualizada, $objeto);
        endif;

        if ($cambios != '' && $actualizaciones != ''):
            $cambios .= ', \n' . $actualizaciones;
        elseif ($cambios == '' && $actualizaciones != ''):
            $cambios = $actualizaciones;
        endif;

        if (($rowActualizada == "") || (($rowActualizada != "") && ($cambios != ""))):
            // INSERTO EL MOVIMIENTO
            $fechaAct = date("Y-m-d H:i:s");
            $sql      = "INSERT INTO LOG_MOVIMIENTOS SET
                            ID_ADMINISTRADOR = $idAdmin,
                            FECHA = '$fechaAct',
                            TIPO_MOVIMIENTO = '$tipoMovimiento',
                            OBJETO = '$objeto',
                            DESCRIPCION = '" . $bd->escapeCondicional($descripcion) . "',
                            NOMBRE_TABLA = '" . $bd->escapeCondicional($nombreTabla) . "',
                            DATOS = '" . $bd->escapeCondicional($cambios) . "'";

            if ($idObjeto <> ""):
                $sql = "$sql,ID_OBJETO=$idObjeto";
            endif;
            $bd->ExecSQL($sql);
            $idLogMov = $bd->IdAsignado();

            return $idLogMov;
        endif;
    }

    function Insertar_Log_Movimientos_Campo($idLogMov, $rowAntigua, $rowActualizada)
    {
        global $bd;

        if($rowActualizada != "" && $rowActualizada != null):
            //RECORREMOS LOS CAMPOS, PARA ENCONTRAR DIFERENCIAS
            foreach ($rowActualizada as $nombreCampo => $valorCampo):
                //VARIABLE PARA MOSTRAR EL CAMPO EN EL LOG DE MODIFICACION (RECOGEMOS VALOR BBDD)
                $motrarCampo = true;
                if ($rowAntigua->$nombreCampo != $valorCampo):


                    $valorCampoAntiguo     = $rowAntigua->$nombreCampo;
                    $valorCampoActualizado = $valorCampo;
                    $sqlInsertarLogCampos  = "INSERT INTO LOG_MOVIMIENTOS_CAMPO SET
                                            ID_LOG_MOVIMIENTOS = $idLogMov
                                            , NOMBRE_CAMPO = '" . $bd->escapeCondicional($nombreCampo) . "'
                                            , VALOR_ANTIGUO = '" . $bd->escapeCondicional($valorCampoAntiguo) . "'
                                            , VALOR_NUEVO = '" . $bd->escapeCondicional($valorCampoActualizado) . "'";

                    $bd->ExecSQL($sqlInsertarLogCampos);
                endif;
            endforeach;
        endif;

    }

    function Insertar_Alerta_Bloqueo($idTipoAlert, $descAlert, $idAdmin)
    {
        global $bd;

        // INSERTO LA LINEA DEL MOVIMIENTO
        $fechaAlerta = date("Y-m-d H:i:s");
        $sql         = "INSERT INTO ALERTA_ADM (ID_ALERTA_TIPO,ID_ADMINISTRADOR,FECHA_GENERACION,DESCRIPCION) VALUES($idTipoAlert,$idAdmin,'$fechaAlerta','$descAlert')";
        $bd->ExecSQL($sql);

    } // Fin Insertar_Alerta_Bloqueo

    /**
     * @param $tipo_alerta (codigo del tipo de alerta)
     * @param $id_admin
     * @param $idAdminCreacion
     * @param $idLineaControl
     * @param $idLineaControlFaseFundamental
     * @param $seccion
     * @param $idDocumento
     * @param $datos
     */
    function Insertar_Alerta_Expediting($tipo_alerta, $id_admin, $idAdminCreacion, $idLineaControl = NULL, $idLineaControlFaseFundamental = NULL, $seccion = NULL, $idDocumento = NULL, $datos = NULL)
    {
        global $bd;
        global $administrador;

        if($idAdminCreacion == $id_admin) return; //NO GENERAMOS ALERTAS PARA EL PROPIO CREADOR

        //COMRPOBAMOS QUE TENGA PERMISOS PARA ESA LINEA, SINO NO LA GENERAMOS
        if(!empty($idLineaControl) && !empty($id_admin))
        {
            $esAdmin     =  false;
            $esProveedor =  false;

            $sqlEsUsuarioAdministrador =
            "
            SELECT PA.ES_ADMINISTRADOR_EXPEDITING, PA.ES_SUPERUSUARIO
            FROM ADMINISTRADOR A INNER JOIN 
            ADMINISTRADOR_PERFIL PA ON PA.ID_ADMINISTRADOR_PERFIL = A.ID_ADMINISTRADOR_PERFIL_EXPEDITING
            WHERE A.ID_ADMINISTRADOR = '$id_admin' AND PA.ES_PERFIL_API = 0
            ";

            $resultEsUsuarioAdministrador = $bd->ExecSQL($sqlEsUsuarioAdministrador);
            $numRegistrosEsUsuario = $bd->numRegs($resultEsUsuarioAdministrador);

            if(!empty($numRegistrosEsUsuario))
            {
                $rowEsUsuarioAdministrador = $bd->SigReg($resultEsUsuarioAdministrador);
                $esAdmin = $rowEsUsuarioAdministrador->ES_ADMINISTRADOR_EXPEDITING == 1 || $rowEsUsuarioAdministrador->ES_SUPERUSUARIO == 1;
            }


            $sqlEsProveedorExpediting =
                "
            SELECT PA.ES_PROVEEDOR_EXPEDITING
            FROM ADMINISTRADOR A INNER JOIN 
            ADMINISTRADOR_PERFIL PA ON PA.ID_ADMINISTRADOR_PERFIL = A.ID_ADMINISTRADOR_PERFIL_EXPEDITING
            WHERE A.ID_ADMINISTRADOR = '$id_admin' AND PA.ES_PERFIL_API = 0
            ";

            $resultEsProveedorExpediting = $bd->ExecSQL($sqlEsProveedorExpediting);
            $numRegistrosEsProveedorExpediting = $bd->numRegs($resultEsProveedorExpediting);

            if(!empty($numRegistrosEsProveedorExpediting))
            {
                $rowEsProveedorExpediting = $bd->SigReg($resultEsProveedorExpediting);
                $esProveedor = $rowEsProveedorExpediting->ES_PROVEEDOR_EXPEDITING == 1;
            }

            $rowLineaControl = $bd->VerReg("LINEA_CONTROL", "ID_LINEA_CONTROL", $idLineaControl, 'No');
            if(empty($rowLineaControl))
            {
                //NO DEBERIA ENTRAR NUNCA
                return;
            }
            $id_almacen_comprobar_permiso = $rowLineaControl->ID_ALMACEN;

            //PERMISOS PROYECTO INSTALACION
            if (!$esAdmin && !$esProveedor):

                $sqlProyectosPermiso =
                "SELECT SQL_CALC_FOUND_ROWS AI.* 
                FROM ALMACEN A 
                INNER JOIN DIRECCION D ON D.ID_ALMACEN = A.ID_ALMACEN
                INNER JOIN ADMINISTRADOR_INSTALACION AI ON AI.ID_ALMACEN = A.ID_ALMACEN
                WHERE A.CATEGORIA_ALMACEN = 'Construccion: Instalacion' AND A.BAJA = '0' AND D.TIPO_DIRECCION = 'Almacen' 
                  AND D.BAJA = '0' AND AI.ID_ADMINISTRADOR = '$id_admin'
                ";

                $resultProyectosPermisos = $bd->ExecSQL($sqlProyectosPermiso);
                $numRegistrosEsProveedorExpediting = $bd->numRegs($resultProyectosPermisos);

                if(empty($numRegistrosEsProveedorExpediting))
                {
                    //NO TIENE PERMISO NO GENERAMOS ALERTA
                    return;
                }
                else
                {
                    $tiene_permiso = false;
                    while ($usuario_instalacion = $bd->SigReg($resultProyectosPermisos))
                    {
                        if($usuario_instalacion->ID_ALMACEN == $id_almacen_comprobar_permiso)
                        {
                            $tiene_permiso = true;
                            break;
                        }
                    }

                    if(!$tiene_permiso)
                    {
                        //NO TIENE PERMISO NO GENERAMOS ALERTA
                        return;
                    }
                }
            elseif ($esProveedor):
                $tiene_permiso = false;

                $sqlLineasAccesoProveedor = "
                SELECT A.ID_ADMINISTRADOR, LC.ID_LINEA_CONTROL, A.ID_EMPRESA, PE.ID_PROVEEDOR, LC.ID_ALMACEN, A2.ID_CENTRO_FISICO
                FROM LINEA_CONTROL LC
                JOIN PEDIDO_EXPEDITING PE ON PE.PEDIDO_SAP = LC.COD_PEDIDO
                JOIN ADMINISTRADOR A ON A.ID_EMPRESA = PE.ID_PROVEEDOR
                JOIN ALMACEN A2 on LC.ID_ALMACEN = A2.ID_ALMACEN
                WHERE LC.BAJA = 0 AND PE.BAJA = 0
                AND A.ID_ADMINISTRADOR = '$id_admin'
                ";

                $resultLineasAccesoProveedor = $bd->ExecSQL($sqlLineasAccesoProveedor);
                $numRegistrosLineasAccesoProveedor = $bd->numRegs($resultLineasAccesoProveedor);

                if(empty($numRegistrosLineasAccesoProveedor))
                {
                    //NO TIENE PERMISO NO GENERAMOS ALERTA
                    return;
                }

                while ($linea = $bd->SigReg($resultLineasAccesoProveedor))
                {
                    if($linea->ID_LINEA_CONTROL == $rowLineaControl->ID_LINEA_CONTROL)
                    {
                        $tiene_permiso = true;
                        break;
                    }
                }

                if(!$tiene_permiso)
                {
                    //NO TIENE PERMISO NO GENERAMOS ALERTA
                    return;
                }
            endif;
        }

        // INSERTO LA LINEA DEL MOVIMIENTO
        $fechaCreacionAlerta = date("Y-m-d H:i:s");

        //BUSCO EL TIPO DE ALERTA EXPEDITING
        $rowTipoAlertaExpediting = $bd->VerReg("TIPO_ALERTA_EXPEDITING", "CODIGO", $tipo_alerta, "No");

        //INSERTO LA LINEA
        $sqlInsert = "INSERT INTO ALERTA_EXPEDITING SET
									ID_TIPO_ALERTA_EXPEDITING = ". $rowTipoAlertaExpediting->ID_TIPO_ALERTA_EXPEDITING ."
									, FECHA_CREACION = '$fechaCreacionAlerta'
									, ID_ADMINISTRADOR = '$id_admin'
									, ID_LINEA_CONTROL = " . ($idLineaControl == NULL ? 'NULL' : $idLineaControl) . "
									, ID_LINEA_CONTROL_FASE_FUNDAMENTAL = " . ($idLineaControlFaseFundamental == NULL ? 'NULL' : $idLineaControlFaseFundamental) . "
									, SECCION = " . ($seccion == NULL ? 'NULL' : "'$seccion'") . "
									, ID_DOCUMENTO_EXPEDITING = " . ($idDocumento == NULL ? 'NULL' : "$idDocumento") . "
									, DATOS = " . ($datos == NULL ? 'NULL' : "'$datos'") . "
									, BAJA = 0";
        $bd->ExecSQL($sqlInsert);
        $idAlertaExpediting = $bd->IdAsignado();

        $administrador->Insertar_Log_Movimientos($idAdminCreacion, "Creación", "Alerta Expediting", $idAlertaExpediting, 'Creación de alerta expediting', "ALERTA_EXPEDITING");

    }

    /**
     * @param $tipo_alerta (codigo del tipo de alerta)
     * @param $id_admin
     * @param $idAdminCreacion
     * @param $idLineaControl
     * @param $idLineaControlFaseFundamental
     * @param $seccion
     * @param $idDocumento
     * @param $datos
     */
    function Validar_Existencia_Alerta_Expediting($tipo_alerta, $id_admin, $idAdminCreacion, $idLineaControl = NULL, $idLineaControlFaseFundamental = NULL, $seccion = NULL, $idDocumento = NULL, $datos = NULL)
    {
        global $bd;


        //BUSCO EL TIPO DE ALERTA EXPEDITING
        $rowTipoAlertaExpediting = $bd->VerReg("TIPO_ALERTA_EXPEDITING", "CODIGO", $tipo_alerta, "No");


        $sql    = "SELECT ID_ALERTA_EXPEDITING FROM ALERTA_EXPEDITING WHERE
									ID_TIPO_ALERTA_EXPEDITING = ". $rowTipoAlertaExpediting->ID_TIPO_ALERTA_EXPEDITING ."
									AND ID_ADMINISTRADOR = '$id_admin'
									AND ID_LINEA_CONTROL " . ($idLineaControl == NULL ? 'IS NULL' : "= " . $idLineaControl) . "
									AND ID_LINEA_CONTROL_FASE_FUNDAMENTAL " . ($idLineaControlFaseFundamental == NULL ? 'IS NULL' : "= " . $idLineaControlFaseFundamental) . "
									AND SECCION " . ($seccion == NULL ? 'IS NULL' : "= '$seccion'") . "
									AND ID_DOCUMENTO_EXPEDITING " . ($idDocumento == NULL ? 'IS NULL' : "= '$idDocumento'") . "
									AND " . ($datos == NULL ? 'DATOS IS NULL' : "DATOS = '$datos'") . "
									AND BAJA = 0";
        $result = $bd->ExecSQL($sql);

        if ($bd->NumRegs($result) > 0):
            return true;
        else:
            return false;
        endif;

    }


    function Hayar_Id($idAdm)
    {
        // HAYA EL ID DEL ADMINISTRADOR
        global $bd;

        $sql    = "SELECT ID_ADMINISTRADOR FROM ADMINISTRADOR WHERE ID_ADMINISTRADOR='$idProv'";
        $result = $bd->ExecSQL($sql);
        if ($bd->NumRegs($result) > 0):
            $row = $bd->SigReg($result);

            return $row->ID_ADMINISTRADOR;
        else:
            return -1; // VALOR INEXISTENTE
        endif;

    } // Fin Hayar_Id


    function Hayar_Nombre($idAdm)
    {
        global $bd;
        $AdmDest = $bd->VerReg("ADMINISTRADOR", "ID_ADMINISTRADOR", $idAdm, "No");
        if ($ProvDest == false) return "Inexistente";
        else                      return $AdmDest->NOMBRE;
    }

    function Hayar_Login($idAdm)
    {
        global $bd;
        $AdmDest = $bd->VerReg("ADMINISTRADOR", "ID_ADMINISTRADOR", $idAdm, "No");
        if ($AdmDest == false) return "Inexistente";
        else                      return $AdmDest->LOGIN;
    }

    function Incrementar_Intentos($idAdm)
    {
        // INCREMENTA EL Nº DE INTENTOS ERRONEOS DE ACCESO DE EL CLIENTE Y DEVUELVE
        // SI ESTE Nº ES MAYOR O MENOR A 10
        global $bd;

        $sql = "UPDATE ADMINISTRADOR SET INTENTOS_USER=INTENTOS_USER+1 WHERE ID_ADMINISTRADOR=$idAdm";
        $bd->ExecSQL($sql);

        $sql            = "SELECT INTENTOS_USER FROM ADMINISTRADOR WHERE ID_ADMINISTRADOR=$idAdm";
        $resultIntentos = $bd->ExecSQL($sql);
        if ($bd->NumRegs($resultIntentos) > 0):
            $rowIntentos = $bd->SigReg($resultIntentos);
            if ($rowIntentos->INTENTOS_USER == $this->MAX_INTENTOS):
                return "MaximoIntentosAlcanzado";
            endif;
        endif;

        return "MenorMaximoIntentos";

    } // Fin Incrementar_Intentos

    function Generar_Email_Bloqueo($rowAdmin)
    {
        // EMAIL AL CLIENTE INDICANDOLE QUE SE LE HA BLOQUEADO LA CUENTA
        global $bd;
        global $auxiliar;

        $Asunto = $auxiliar->traduce("Alerta Herramienta Web Aprovisionamiento", $this->ID_IDIOMA);
        $Cuerpo = $auxiliar->traduce("El numero de intentos erroneos permitidos con su codigo ha sido superado", $this->ID_IDIOMA) . ".<br>" . $auxiliar->traduce("Su cuenta ha sido bloqueada temporalmente por razones de seguridad", $this->ID_IDIOMA) . ".<br><br>$bd->msje_contacte";
        $bd->EnviarEmail($rowAdmin->EMAIL, $Asunto, $Cuerpo, "Html");

    } // Fin Generar_Alerta_Bloqueo

    function ExisteOtroMismoEmail($Email)
    {
        // DEVUELVE SI EXISTE OTRO USUARIO CON EL MISMO EMAIL
        global $bd;
        global $administrador;

        $sql            = "SELECT COUNT(ID_USUARIO) as NUMERO FROM USUARIO WHERE EMAIL='$Email'";
        $resultAsociado = $bd->ejecutarSQL($sql);
        $rowNumero      = $bd->siguiente_reg($resultAsociado);
        if ($rowNumero->NUMERO > 0):
            return "SiExiste";
        else:
            return "NoExiste";
        endif;
    } // Fin ExisteOtroMismoEmail

    function ExisteOtroMismoEmailModif($Email, $txCodigo)
    {
        // DEVUELVE SI EXISTE OTRO USUARIO CON EL MISMO EMAIL
        global $bd;
        global $administrador;

        $sql            = "SELECT COUNT(ID_USUARIO) as NUMERO FROM USUARIO WHERE EMAIL='$Email' AND ID_USUARIO <> $txCodigo";
        $resultAsociado = $bd->ejecutarSQL($sql);
        $rowNumero      = $bd->siguiente_reg($resultAsociado);
        if ($rowNumero->NUMERO > 0):
            return "SiExiste";
        else:
            return "NoExiste";
        endif;
    } // Fin ExisteOtroMismoEmail

    // COMPRUEBA SI EL ADMINISTRADOR YA EXISTE (MISMO LOGIN)
    function Comprobar_Existente($txLogin)
    {

        global $bd;

        $ValorDevolver = "No existente";
        $sql           = "SELECT COUNT(ID_ADMINISTRADOR) as NUM_REGS FROM ADMINISTRADOR WHERE LOGIN='$txLogin'";
        $resultNumero  = $bd->ExecSQL($sql);
        $rowNumero     = $bd->SigReg($resultNumero);
        if ($rowNumero->NUM_REGS > 0) $ValorDevolver = "Existente";

        return $ValorDevolver;
    }

    // COMPRUEBA SI EL CLIENTE YA EXISTE EN LA MODIFICACION
    function Comprobar_Existente_Modif($txLogin, $IdAdmin)
    {

        global $bd;

        $ValorDevolver = "No existente";
        $sql           = "SELECT COUNT(ID_ADMINISTRADOR) as NUM_REGS FROM ADMINISTRADOR WHERE LOGIN='$txLogin' AND ID_ADMINISTRADOR<>$IdAdmin";
        $resultNumero  = $bd->ExecSQL($sql);
        $rowNumero     = $bd->SigReg($resultNumero);
        if ($rowNumero->NUM_REGS > 0) $ValorDevolver = "Existente";

        return $ValorDevolver;
    }

    function ObtenerPerfilAdministrador($idAdmin)
    {
        // DEVUELVE EL REGISTRO COMPPLETO DEL PERFIL DEL ADMINISTRADOR PASADO COMO PARAMETRO

        global $bd;

        $rowAdmin = $bd->VerReg("ADMINISTRADOR", "ID_ADMINISTRADOR", $idAdmin);

        $rowPerfil = $bd->VerReg("ADMINISTRADOR_PERFIL", "ID_ADMINISTRADOR_PERFIL", $rowAdmin->ID_ADMINISTRADOR_PERFIL);

        return $rowPerfil;

    }

    function getSupervisor($rowAdministrador)
    {
        global $bd;

        if (($rowAdministrador->ID_ADMINISTRADOR_SUPERVISOR == NULL) || ($rowAdministrador->ID_ADMINISTRADOR_SUPERVISOR == '')):
            return NULL;
        else:
            $rowAdministradorSupervisor = $bd->VerReg("ADMINISTRADOR", "ID_ADMINISTRADOR", $rowAdministrador->ID_ADMINISTRADOR_SUPERVISOR);

            if ($rowAdministradorSupervisor->BAJA == 0):
                return $rowAdministradorSupervisor;
            else:
                return NULL;
            endif;
        endif;
    }

    function esSuperUsuario()
    {
        $rowPerfil = $this->ObtenerPerfilAdministrador($this->ID_ADMINISTRADOR);

        return $rowPerfil->ES_SUPERUSUARIO == "1";
    }

    function esDeMayorJerarquia($idPerfil, $idAdmin = 0)
    {
        global $bd;
        $rowAdministradorPerfil          = $this->ObtenerPerfilAdministrador($this->ID_ADMINISTRADOR);
        $rowAdministradorPerfilModificar = $bd->VerReg("ADMINISTRADOR_PERFIL", "ID_ADMINISTRADOR_PERFIL", $idPerfil, "NO");
        if ($this->esSuperUsuario()) {
            return true;
        } elseif ($this->ID_ADMINISTRADOR == $idAdmin) {
            if ($rowAdministradorPerfil->JERARQUIA_PERFIL <= $rowAdministradorPerfilModificar->JERARQUIA_PERFIL) {
                return true;
            } else {
                return false;
            }
        } else {
            if ($rowAdministradorPerfil->JERARQUIA_PERFIL < $rowAdministradorPerfilModificar->JERARQUIA_PERFIL) {
                return true;
            } else {
                return false;
            }
        }
    }

    function esAdministrador()
    {
        // DEVUELVE TRUE O FALSE EN FUNCION DE SI EL USUARIO ES ADMINISTRADOR

        $rowPerfil = $this->ObtenerPerfilAdministrador($this->ID_ADMINISTRADOR);

        return $rowPerfil->ES_ADMINISTRADOR == "1";

    }

    function esComprador()
    {
        // DEVUELVE TRUE O FALSE EN FUNCION DE SI EL USUARIO ES COMPRADOR

        $rowPerfil = $this->ObtenerPerfilAdministrador($this->ID_ADMINISTRADOR);

        return $rowPerfil->ES_COMPRADOR == "1";
    }

    function esProveedor()
    {
        // DEVUELVE TRUE O FALSE EN FUNCION DE SI EL USUARIO ES PROVEEDOR

        $rowPerfil = $this->ObtenerPerfilAdministrador($this->ID_ADMINISTRADOR);

        return $rowPerfil->ES_PROVEEDOR == "1";
    }

    function esProveedorForwarder()
    {
        // DEVUELVE TRUE O FALSE EN FUNCION DE SI EL USUARIO ES PROVEEDOR FORWARDER

        $rowPerfil = $this->ObtenerPerfilAdministrador($this->ID_ADMINISTRADOR);

        return $rowPerfil->ES_FORWARDER_CONSTRUCCION == "1";
    }

    function esProveedorAgenteAduanal()
    {
        // DEVUELVE TRUE O FALSE EN FUNCION DE SI EL USUARIO ES PROVEEDOR AGENTE ADUANAL

        $rowPerfil = $this->ObtenerPerfilAdministrador($this->ID_ADMINISTRADOR);

        return $rowPerfil->ES_AGENTE_ADUANAL_CONSTRUCCION == "1";
    }

    function esProveedorTransportistaInland()
    {
        // DEVUELVE TRUE O FALSE EN FUNCION DE SI EL USUARIO ES PROVEEDOR TRANSPORTISTA INLAND

        $rowPerfil = $this->ObtenerPerfilAdministrador($this->ID_ADMINISTRADOR);

        return $rowPerfil->ES_TRANSPORTISTA_INLAND_CONSTRUCCION == "1";
    }

    function esProveedorSurveyor()
    {
        // DEVUELVE TRUE O FALSE EN FUNCION DE SI EL USUARIO ES PROVEEDOR SURVEYOR

        $rowPerfil = $this->ObtenerPerfilAdministrador($this->ID_ADMINISTRADOR);

        return $rowPerfil->ES_SURVEYOR == "1";
    }

    function esInvitadoSolicitudes()
    {
        // DEVUELVE TRUE O FALSE EN FUNCION DE SI EL USUARIO ES PROVEEDOR

        $rowPerfil = $this->ObtenerPerfilAdministrador($this->ID_ADMINISTRADOR);

        return $rowPerfil->ES_USUARIO_SOLICITUD_INVITADO == "1";
    }

    function esGestorTransporte()
    {
        // DEVUELVE TRUE O FALSE EN FUNCION DE SI EL USUARIO ES GESTOR DE TRANSPORTE

        $rowPerfil = $this->ObtenerPerfilAdministrador($this->ID_ADMINISTRADOR);

        return $rowPerfil->ES_GESTOR_TRANSPORTE == "1";
    }

    function esTecnico()
    {
        // DEVUELVE TRUE O FALSE EN FUNCION DE SI EL USUARIO ES TECNICO

        $rowPerfil = $this->ObtenerPerfilAdministrador($this->ID_ADMINISTRADOR);

        return $rowPerfil->TIPO == "tec";
    }

    function esLogistico()
    {
        // DEVUELVE TRUE O FALSE EN FUNCION DE SI EL USUARIO ES LOGISTICO

        $rowPerfil = $this->ObtenerPerfilAdministrador($this->ID_ADMINISTRADOR);

        return $rowPerfil->TIPO == "log";
    }


    function esResponsable()
    {
        // DEVUELVE TRUE O FALSE EN FUNCION DE SI EL USUARIO ES RESPONSABLE DE ALGUNA ZONA O SUBZONA

        return ($this->esResponsableZona() || $this->esResponsableSubzona());
    }

    function esResponsableZona($idZona = NULL)
    {
        // DEVUELVE TRUE O FALSE EN FUNCION DE SI EL USUARIO ES RESPONSABLE DE ALGUNA ZONA
        global $bd;
        $sql = "SELECT ID_ZONA FROM ZONA_RESPONSABLE WHERE ID_ADMINISTRADOR_RESPONSABLE=" . $this->ID_ADMINISTRADOR . " AND BAJA = 0";

        if ($idZona != NULL) {
            $sql = " AND ID_ZONA = '" . $idZona . "'";
        }

        $result = $bd->ExecSQL($sql);

        return $bd->NumRegs($result) > 0;
    }

    function esResponsableSubzona($idSubzona = NULL)
    {
        // DEVUELVE TRUE O FALSE EN FUNCION DE SI EL USUARIO ES RESPONSABLE DE ALGUNA SUBZONA
        global $bd;
        $sql = "SELECT ID_SUBZONA FROM SUBZONA_RESPONSABLE WHERE ID_ADMINISTRADOR_RESPONSABLE=" . $this->ID_ADMINISTRADOR . " AND BAJA = 0";

        if ($idSubzona != NULL) {
            $sql = " AND ID_SUBZONA = '" . $idSubzona . "'";
        }

        $result = $bd->ExecSQL($sql, "No");

        return $bd->NumRegs($result) > 0;
    }


    function Hayar_Permiso_Perfil($Nombre)
    {
        global $bd;

        $referer = $_SERVER["HTTP_REFERER"];
        if ((strpos( (string)$referer, 'pda') != false) && ($this->ID_ADMINISTRADOR == '' || $this->ID_ADMINISTRADOR == NULL)):
            header("location: ../salir.php");
            exit;
        endif;

        $rowAdm = $bd->VerReg("ADMINISTRADOR", "ID_ADMINISTRADOR", $this->ID_ADMINISTRADOR, "No");

        //SQL
        $sql = "SELECT $Nombre
                    FROM ADMINISTRADOR_PERFIL
                    WHERE ID_ADMINISTRADOR_PERFIL = '$rowAdm->ID_ADMINISTRADOR_PERFIL'";
        $resultado = $bd->ExecSQL($sql, "No");

        // INFORMO DEL ERROR AL RESPONSABLE INFORMATICO
        if ($resultado == false || $bd->NumRegs($resultado) == 0):
            return false;
        endif;

        $rowPerfil = $bd->SigReg($resultado);

        $this->ID_ADMINISTRADOR_PERFIL = $rowAdm->ID_ADMINISTRADOR_PERFIL;

        $res = $rowPerfil->$Nombre;

        //BUSCO LOS PERMISOS DEL PERFIL DE CODIFICACION
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowPerfilCodificacion = $bd->VerReg("ADMINISTRADOR_PERFIL", "ID_ADMINISTRADOR_PERFIL", $rowAdm->ID_ADMINISTRADOR_PERFIL_CODIFICACION, "No");
        if ($rowPerfilCodificacion):
            $this->ID_ADMINISTRADOR_PERFIL_CODIFICACION = $rowPerfilCodificacion->ID_ADMINISTRADOR_PERFIL;
            //DEVULVO EL MAYOR VALOR DE LOS PERFILES
            $res = ($rowPerfil->$Nombre >= $rowPerfilCodificacion->$Nombre ? $rowPerfil->$Nombre : $rowPerfilCodificacion->$Nombre);
        endif;

        return ($res);
    }

    function obtenerAlmacenConectadoUsuario($idCentroOT = "")
    {
        global $bd;
        $res = "";

        if ($_SESSION["idAlmacenConectadoUsuario"] != ''):
            if (($this->PermiteGestionStockTerceros() == true) && ($idCentroOT != "")):

                //SI PERMITE STOCK A TERCEROS DEVOLVEMOS EL ALMACEN DEL CENTRO DE LA OT Y DEL CF AL QUE SE CONECTÓ SI EXISTE
                $res                  = $_SESSION["idAlmacenConectadoUsuario"];
                $rowAlmacenCompartido = $bd->VerReg("ALMACEN", "ID_ALMACEN", $res);
                $sqlAlmacen           = "SELECT ID_ALMACEN
                                            FROM ALMACEN A
                                            WHERE A.STOCK_COMPARTIDO = 1 AND A.ID_CENTRO = $idCentroOT AND A.ID_CENTRO_FISICO = $rowAlmacenCompartido->ID_CENTRO_FISICO";
                $resultAlmacen = $bd->ExecSQL($sqlAlmacen);

                //SI LO ENCONTRAMOS CAMBIAMOS EL ALMACEN
                if ($bd->NumRegs($resultAlmacen) > 0):
                    $rowAlmacen = $bd->SigReg($resultAlmacen);
                    $res        = $rowAlmacen->ID_ALMACEN;
                endif;
            else:
                $res = $_SESSION["idAlmacenConectadoUsuario"];
            endif;
        endif;

        return ($res);
    }

    function obtenerCentroFisicoConectadoUsuario()
    {
        $res = "";

        if ($_SESSION["idCentroFisicoConectadoUsuario"] != ''):
            $res = $_SESSION["idCentroFisicoConectadoUsuario"];
        endif;

        return ($res);
    }

    //OBTIENE LOS ALMACENES RELACIONES CON EL CF INTRODUCIDO POR EL USUARIO TÉCNICO
    function listadoAlmacenesConectadoUsuario($formatoRespuesta = "ARRAY")
    {
        global $bd;

        $respuesta = NULL;

        $arrayAlmacenes = array();

        if ($this->obtenerCentroFisicoConectadoUsuario() != ''):
            //CONSULTA FINAL
            $sqlAlmacenes = "SELECT DISTINCT ID_ALMACEN
                            FROM ALMACEN
                            WHERE (ID_CENTRO_FISICO=" . $this->obtenerCentroFisicoConectadoUsuario() . ")
                            AND BAJA = '0'";

            $resultAlmacenes = $bd->ExecSQL($sqlAlmacenes);
            while ($rowAlmacen = $bd->SigReg($resultAlmacenes)):
                $arrayAlmacenes[] = $rowAlmacen->ID_ALMACEN;
            endwhile;


            //CREO LA RESPUESTA CON EL LISTADO DE ALMACENES OBTENIDOS
            if ($formatoRespuesta == "ARRAY"):
                $respuesta = $arrayAlmacenes;

            elseif ($formatoRespuesta == "STRING"):

                if (count( (array)$arrayAlmacenes) > 0):
                    $listadoAlmacenes = "";
                    $coma             = "";
                    foreach ($arrayAlmacenes as $idAlmacen):
                        $listadoAlmacenes .= $coma . $idAlmacen;
                        $coma             = ",";
                    endforeach;
                else:
                    $listadoAlmacenes = "NULL";
                endif;

                $respuesta = "(" . $listadoAlmacenes . ")";
            endif;
        endif;

        //DEVUELVO EL RESULTADO
        return $respuesta;
    }

    //OBTIENE LOS ALMACENES DONDE ES RESPONSABLE
    function listadoAlmacenesResponsable($formatoRespuesta = "ARRAY")
    {
        global $bd;

        $respuesta = NULL;

        $arrayAlmacenes = array();

        //CONSULTA FINAL
        $sqlAlmacenes = "SELECT DISTINCT A.ID_ALMACEN, A.ID_ZONA_LOGISTICA, Z.ID_RESPONSABLE
                            FROM ALMACEN A
                            INNER JOIN ZONA Z ON Z.ID_ZONA = A.ID_ZONA_LOGISTICA
                            WHERE Z.ID_RESPONSABLE = " . $this->ID_ADMINISTRADOR . "
                            AND A.BAJA = '0'";

        $resultAlmacenes = $bd->ExecSQL($sqlAlmacenes);
        if ($bd->NumRegs($resultAlmacenes) > 0):

            while ($rowAlmacen = $bd->SigReg($resultAlmacenes)):
                $arrayAlmacenes[] = $rowAlmacen->ID_ALMACEN;
            endwhile;


            //CREO LA RESPUESTA CON EL LISTADO DE ALMACENES OBTENIDOS
            if ($formatoRespuesta == "ARRAY"):
                $respuesta = $arrayAlmacenes;

            elseif ($formatoRespuesta == "STRING"):

                if (count( (array)$arrayAlmacenes) > 0):
                    $listadoAlmacenes = "";
                    $coma             = "";
                    foreach ($arrayAlmacenes as $idAlmacen):
                        $listadoAlmacenes .= $coma . $idAlmacen;
                        $coma             = ",";
                    endforeach;
                else:
                    $listadoAlmacenes = "NULL";
                endif;

                $respuesta = "(" . $listadoAlmacenes . ")";

            elseif ($formatoRespuesta == "STRING_SEPARADOR_MULTIPLE"):

                if (count( (array)$arrayAlmacenes) > 0):
                    $listadoAlmacenes = "";
                    $separador        = "";
                    foreach ($arrayAlmacenes as $idAlmacen):
                        $listadoAlmacenes .= $separador . $idAlmacen;
                        $separador        = "|";
                    endforeach;
                else:
                    $listadoAlmacenes = "NULL";
                endif;

                $respuesta = $listadoAlmacenes;
            endif;
        endif;

        //DEVUELVO EL RESULTADO
        return $respuesta;
    }

    function PermiteGestionStockTerceros()
    {
        $res = false;

        if ($_SESSION["tieneStockCompartido"] == 1):
            $res = true;
        endif;

        return ($res);
    }

//DEVUELVE SI UN TÉCNICO TIENE PERMISO O NO PARA OPERAR SOBRE UN ALMACEN.
//SI EL ALMACEN PERTENECE A UN CF CON GESTION STOCK TERCEROS, PUEDE OPERAR EN CUALQUIER ALMACEN DE ESE CF
    function TienePermisoAlmacen($idAlmacen)
    {
        $res = false;

        if ($this->PermiteGestionStockTerceros() == false):
            if (($this->obtenerAlmacenConectadoUsuario() == $idAlmacen) || ($this->obtenerAlmacenConectadoUsuario() == '')):
                $res = true;
            endif;
        else:
            if (in_array($idAlmacen, (array) $this->listadoAlmacenesConectadoUsuario())):
                $res = true;
            endif;
        endif;

        return ($res);
    }

    function NombrePerfilAdministrador($idAdministradorPerfil)
    {
        global $bd;
        global $auxiliar;

        //SQL
        $sql = "SELECT NOMBRE, NOMBRE_ENG FROM ADMINISTRADOR_PERFIL WHERE ID_ADMINISTRADOR_PERFIL = '$idAdministradorPerfil'";

        $resultado = $bd->ExecSQL($sql, "No");

        // INFORMO DEL ERROR AL RESPONSABLE INFORMATICO
        if ($resultado == false || $bd->NumRegs($resultado) == 0):
            return false;
        endif;

        $row = $bd->SigReg($resultado);
        $nombre = $auxiliar->devolverCampoIdiomaAdmin($row, "NOMBRE", "NOMBRE_ENG");

        return ($nombre);
    }

    function NombreAdministrador($idAdministrador)
    {
        global $bd;

        $row    = $bd->VerReg("ADMINISTRADOR", "ID_ADMINISTRADOR", $idAdministrador);
        $nombre = $row->NOMBRE;

        return ($nombre);
    }

    function TelefonoFijoAdministrador($idAdministrador)
    {
        global $bd;

        $row    = $bd->VerReg("ADMINISTRADOR", "ID_ADMINISTRADOR", $idAdministrador);
        $nombre = $row->TELEFONO_FIJO;

        return ($nombre);
    }

    function TelefonoMovilAdministrador($idAdministrador)
    {
        global $bd;

        $row    = $bd->VerReg("ADMINISTRADOR", "ID_ADMINISTRADOR", $idAdministrador);
        $nombre = $row->TELEFONO_MOVIL;

        return ($nombre);
    }

    function getEmailAdministrador($idAdministrador)
    {
        global $bd;

        $row   = $bd->VerReg("ADMINISTRADOR", "ID_ADMINISTRADOR", $idAdministrador);
        $email = $row->EMAIL;

        return ($email);
    }

    function LoginAdministrador($idAdministrador)
    {
        global $bd;

        $row   = $bd->VerReg("ADMINISTRADOR", "ID_ADMINISTRADOR", $idAdministrador);
        $login = $row->LOGIN;

        return ($login);
    }


    /**
     * DEVUELVE true/false EN FUNCION DE SI EL ADMINISTRADOR TIENE ACCESO RESTRINGIDO POR ZONAS
     **/
    function esRestringidoPorZonas()
    {

        global $bd;

        //SI EL ADMINISTRADOR NO ESTA RESTRINGIDO POR ZONAS, SIEMPRE SERA TRUE
        $rowAdmin = $bd->VerReg("ADMINISTRADOR", "ID_ADMINISTRADOR", $this->ID_ADMINISTRADOR, "No");

        return $rowAdmin->RESTRINGIDO_POR_ZONAS == "1";

    }

    /**
     * DEVUELVE true/false EN FUNCION DE SI EL ADMINISTRADOR TIENE ACCESO RESTRINGIDO POR ZONAS (EXPEDICIONES)
     **/
    function comprobarZonasExpedicionPermiso($idExpedicion)
    {

        global $bd, $html;

        $rowExpedicion = $bd->VerReg("EXPEDICION", "ID_EXPEDICION", $idExpedicion, "No");

        if (($rowExpedicion->ID_CENTRO_FISICO == "")):
            if ($rowExpedicion->ID_ORDEN_TRANSPORTE != ""):
                $rowOrdenTransporte = $bd->VerReg("ORDEN_TRANSPORTE", "ID_ORDEN_TRANSPORTE", $rowExpedicion->ID_ORDEN_TRANSPORTE, "No");

                $html->PagErrorCondicionado($this->comprobarCentroPermiso($rowOrdenTransporte->ID_CENTRO_CONTRATANTE), "==", false, "SinPermisosSubzona");
            else:
                $html->PagErrorCondicionado($this->comprobarCentroPermiso($rowExpedicion->ID_CENTRO_CONTRATANTE), "==", false, "SinPermisosSubzona");
            endif;
        endif;

        //SI EL ADMINISTRADOR NO ESTA RESTRINGIDO POR ZONAS, SIEMPRE SERA TRUE
        $rowAdmin = $bd->VerReg("ADMINISTRADOR", "ID_ADMINISTRADOR", $this->ID_ADMINISTRADOR, "No");

        return $rowAdmin->RESTRINGIDO_POR_ZONAS == "1";

    }

    /**
     * DEVUELVE true/false EN FUNCION DE SI EL ADMINISTRADOR TIENE EL PERMISO INDICADO EN LA UBICACION INDICADA
     * PARA DETERMINAR SI TIENE PERMISO O NO, NOS BASAREMOS EN SI TIENE PERMISO EN EL ALMACEN DE LA UBICACION
     * $idUbicacion
     * $tipoAcceso = 'Lectura' / 'Escritura'
     **/
    function comprobarUbicacionPermiso($idUbicacion, $tipoAcceso = "Escritura")
    {
        global $bd;

        //SI EL ADMINISTRADOR NO ESTA RESTRINGIDO POR ZONAS, SIEMPRE SERA TRUE
        $rowAdmin = $bd->VerReg("ADMINISTRADOR", "ID_ADMINISTRADOR", $this->ID_ADMINISTRADOR, "No");
        if ($rowAdmin->RESTRINGIDO_POR_ZONAS == "0"):
            return true;
        endif;

        //OBTENGO LA UBICACION
        $NotificaErrorPorEmail = "No";
        $rowUbicacion          = $bd->VerReg("UBICACION", "ID_UBICACION", $idUbicacion, "No");
        if (!$rowUbicacion):
            return false;
        endif;
        unset($NotificaErrorPorEmail);

        //COMPRUEBO SI EL ALMACEN DE LA UBICACION TIENE PERMISO
        return $this->comprobarAlmacenPermiso($rowUbicacion->ID_ALMACEN, $tipoAcceso);
    }

    /**
     * DEVUELVE true/false EN FUNCION DE SI EL ADMINISTRADOR TIENE EL PERMISO INDICADO EN EL MATERIAL INDICADO
     * PARA DETERMINAR SI TIENE PERMISO O NO, NOS BASAREMOS EN SI TIENE PERMISO EN AL MENOS UNO DE LOS ALMACENES EN LOS QUE EL MATERIAL ESTA DEFINIDO
     * $idUbicacion
     * $tipoAcceso = 'Lectura' / 'Escritura'
     **/
    function comprobarMaterialPermiso($idMaterial, $tipoAcceso = "Escritura")
    {
        global $bd;

        //RESPUESTA POR DEFECTO ES FALSE
        $respuesta = false;

        //SI EL ADMINISTRADOR NO ESTA RESTRINGIDO POR ZONAS, SIEMPRE SERA TRUE
        $rowAdmin = $bd->VerReg("ADMINISTRADOR", "ID_ADMINISTRADOR", $this->ID_ADMINISTRADOR, "No");
        if ($rowAdmin->RESTRINGIDO_POR_ZONAS == "0"):
            return true;
        endif;

        //OBTENGO EL MATERIAL
        $NotificaErrorPorEmail = "No";
        $rowMaterial           = $bd->VerReg("MATERIAL", "ID_MATERIAL", $idMaterial, "No");
        if (!$rowMaterial):
            return false;
        endif;
        unset($NotificaErrorPorEmail);

        //OBTENGO LOS ALMACENES PARA LOS QUE ESTA DEFINIDO EL MATERIAL Y COMPRUEBO EL TIPO DE PERMISO QUE TIENE EL ADMINISTRADOR EN CADA UNO DE ELLOS
        $sqlMaterialAlmacen    = "SELECT DISTINCT ID_ALMACEN FROM MATERIAL_ALMACEN WHERE ID_MATERIAL = '" . ($rowMaterial->ID_MATERIAL) . "' AND BAJA = '0' ";
        $resultMaterialAlmacen = $bd->ExecSQL($sqlMaterialAlmacen);
        while ($rowMaterialAlmacen = $bd->SigReg($resultMaterialAlmacen)):

            //SI PARA ALGUNO TIENE PERMISO, DEVUELVO TRUE
            if ($this->comprobarAlmacenPermiso($rowMaterialAlmacen->ID_ALMACEN, $tipoAcceso)):
                return true;
            endif;

        endwhile;

        return $respuesta;
    }

    /**
     * DEVUELVE true/false EN FUNCION DE SI EL ADMINISTRADOR TIENE EL PERMISO INDICADO EN EL ALMACEN INDICADO
     * $idAlmacen
     * $tipoAcceso = 'Lectura' / 'Escritura'
     **/
    function comprobarAlmacenPermiso($idAlmacen, $tipoAcceso = "Escritura")
    {
        global $bd;

        //SI EL ADMINISTRADOR NO ESTA RESTRINGIDO POR ZONAS, SIEMPRE SERA TRUE
        $rowAdmin = $bd->VerReg("ADMINISTRADOR", "ID_ADMINISTRADOR", $this->ID_ADMINISTRADOR, "No");
        if ($rowAdmin->RESTRINGIDO_POR_ZONAS == "0"):
            return true;
        endif;

        //OBTENGO EL ALMACEN
        $NotificaErrorPorEmail            = "No";
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowAlmacen                       = $bd->VerReg("ALMACEN", "ID_ALMACEN", $idAlmacen, "No");
        unset($GLOBALS["NotificaErrorPorEmail"]);
        if (!$rowAlmacen):
            return false;
        endif;
        unset($NotificaErrorPorEmail);

        //SI EL ALMACEN NO ES DE TIPO 'acciona', DEVUELVO TRUE
        if ($rowAlmacen->TIPO_ALMACEN != 'acciona'):
            return true;
        endif;

        //OBTENGO EL ARRAY DE ALMACENES DEL ADMINISTRADOR CON EL PERMISO INDICADO
        $arrAlamacenesPermiso = $this->listadoAlmacenesPermiso($tipoAcceso, "ARRAY");

        //COMPRUEBO SI EL ALMACEN INDICADO ESTA EN EL ARRAY DE ALMACENES CON PERMISO
        return in_array($idAlmacen, (array) $arrAlamacenesPermiso);
    }

    /**
     * DEVUELVE true/false EN FUNCION DE SI EL ADMINISTRADOR TIENE EL PERMISO INDICADO SOBRE ALGUN ALMACEN DEL CENTRO INDICADO
     * $idCentro
     * $tipoAcceso = 'Lectura' / 'Escritura'
     * $tipoAlmacen = 'Todos' / 'acciona' / 'proveedor' / 'vehículo'
     **/
    function comprobarCentroPermiso($idCentro, $tipoAcceso = "Escritura", $tipoAlmacen = 'Todos')
    {
        global $bd;

        //SI EL ADMINISTRADOR NO ESTA RESTRINGIDO POR ZONAS, SIEMPRE SERA TRUE
        $rowAdmin = $bd->VerReg("ADMINISTRADOR", "ID_ADMINISTRADOR", $this->ID_ADMINISTRADOR, "No");
        if ($rowAdmin->RESTRINGIDO_POR_ZONAS == "0"):
            return true;
        endif;

        //OBTENGO EL CENTRO
        $NotificaErrorPorEmail = "No";
        $rowCentro             = $bd->VerReg("CENTRO", "ID_CENTRO", $idCentro, "No");
        if (!$rowCentro):
            return false;
        endif;
        unset($NotificaErrorPorEmail);

        //CALCULO EL NUMERO DE ALMACENES QUE CUMPLEN CON LOS CRITERIOS
        $sqlNumAlmacenes    = "SELECT COUNT(*) AS NUM
													FROM ALMACEN A 
													WHERE A.ID_CENTRO = $rowCentro->ID_CENTRO AND A.BAJA = 0 " . ($tipoAlmacen == 'Todos' ? '' : "AND A.TIPO_ALMACEN = '" . $tipoAlmacen . "' ") . "AND A.ID_ALMACEN IN  " . ($this->listadoAlmacenesPermiso($tipoAcceso, "STRING"));
        $resultNumAlmacenes = $bd->ExecSQL($sqlNumAlmacenes);
        $rowNumAlmacenes    = $bd->SigReg($resultNumAlmacenes);

        if ($rowNumAlmacenes->NUM > 0):
            return true;
        else:
            return false;
        endif;
    }

    /**
     * DEVUELVE true/false EN FUNCION DE SI EL ADMINISTRADOR TIENE EL PERMISO INDICADO SOBRE ALGUN ALMACEN DEL CENTRO FISICO INDICADO
     * $idCentroFisico
     * $tipoAcceso = 'Lectura' / 'Escritura'
     * $tipoAlmacen = 'Todos' / 'acciona' / 'proveedor' / 'vehículo'
     **/
    function comprobarCentroFisicoPermiso($idCentroFisico, $tipoAcceso = "Escritura", $tipoAlmacen = 'Todos')
    {
        global $bd;

        //SI EL ADMINISTRADOR NO ESTA RESTRINGIDO POR ZONAS, SIEMPRE SERA TRUE
        $rowAdmin = $bd->VerReg("ADMINISTRADOR", "ID_ADMINISTRADOR", $this->ID_ADMINISTRADOR, "No");
        if ($rowAdmin->RESTRINGIDO_POR_ZONAS == "0"):
            return true;
        endif;

        //OBTENGO EL CENTRO FISICO
        $NotificaErrorPorEmail = "No";
        $rowCentroFisico       = $bd->VerReg("CENTRO_FISICO", "ID_CENTRO_FISICO", $idCentroFisico, "No");
        if (!$rowCentroFisico):
            return false;
        endif;
        unset($NotificaErrorPorEmail);

        //CALCULO EL NUMERO DE ALMACENES QUE CUMPLEN CON LOS CRITERIOS
        $sqlNumAlmacenes    = "SELECT COUNT(*) AS NUM
													FROM ALMACEN A
													WHERE A.ID_CENTRO_FISICO = $rowCentroFisico->ID_CENTRO_FISICO AND A.BAJA = 0 " . ($tipoAlmacen == 'Todos' ? '' : "AND A.TIPO_ALMACEN = '" . $tipoAlmacen . "' ") . "AND A.ID_ALMACEN IN  " . ($this->listadoAlmacenesPermiso($tipoAcceso, "STRING"));
        $resultNumAlmacenes = $bd->ExecSQL($sqlNumAlmacenes);
        $rowNumAlmacenes    = $bd->SigReg($resultNumAlmacenes);

        if ($rowNumAlmacenes->NUM > 0):
            return true;
        else:
            return false;
        endif;
    }

    /**
     * DEVUELVE LOS ALMACENES DEL ADMINISTRADOR PARA LOS CUALES TIENE EL PERMISO INDICADO
     * Y LOS DEVUELVE EN EL FORMATO DE RESPUESTA INDICADO
     * $tipoAcceso = 'Lectura' / 'Escritura'    (SI ES 'Lectura' SACAREMOS LOS QUE TENGAN 'Lectura' o 'Escritura'; SI ES 'Escritura' SACAREMOS  SOLO LOS QUE TENGAN 'Escritura')
     * $formatoRespuesta = 'ARRAY' / 'STRING'   (STRING SE UTILIZARA PARA SQL, DEVOLVERA POR EJEMPLO (342,3443,452) O (NULL) SI NO ENCUENTRA NADA
     * NOTA: SOLO LIMITAREMOS ACCESO A ALMACEN POR SUBZONAS SI EL ALMACEN ES DE TIPO 'acciona',
     * SI ES 'vehiculo' o 'proveedor' MOSTRAREMOS AQUELLOS QUE PERTENCZCAN A UN CENTRO QUE TENGA AL MENOS UN ALMACEN CON EL PERMISO BUSCADO
     **/
    function listadoAlmacenesPermiso($tipoAcceso = "Lectura", $formatoRespuesta = "ARRAY")
    {
        global $bd;

        $respuesta = NULL;

        $arrayAlmacenes = array();


        $rowAdmin = $bd->VerReg("ADMINISTRADOR", "ID_ADMINISTRADOR", $this->ID_ADMINISTRADOR, "No");

        //SI EL ADMINISTRADOR NO ESTA RESTRINGIDO POR ZONAS, SERAN TODOS LOS ALMACENES
        if ($rowAdmin->RESTRINGIDO_POR_ZONAS == "0"):

            //BUSCO TODOS LOS ALMACENES
            $sqlAlmacenes    = "SELECT DISTINCT ID_ALMACEN FROM ALMACEN WHERE BAJA = '0' ";
            $resultAlmacenes = $bd->ExecSQL($sqlAlmacenes);
            while ($rowAlmacen = $bd->SigReg($resultAlmacenes)):
                $arrayAlmacenes[] = $rowAlmacen->ID_ALMACEN;
            endwhile;


        //SI ESTA RESTRINGIDO POR ZONAS
        else:
            //SI ES 'Lectura' SACAREMOS LOS QUE TENGAN 'Lectura' o 'Escritura'; SI ES 'Escritura' SACAREMOS  SOLO LOS QUE TENGAN 'Escritura'
            $sqlTipoAcceso = " AND TIPO_ACCESO = '" . $tipoAcceso . "' ";
            if ($tipoAcceso == "Escritura"):
                $sqlTipoAcceso = " AND TIPO_ACCESO = 'Escritura' ";
            elseif ($tipoAcceso == "Lectura"):
                $sqlTipoAcceso = " AND (TIPO_ACCESO = 'Lectura' OR TIPO_ACCESO = 'Escritura')  ";
            endif;

            //PRIMERO BUSCO LAS SUBZONAS EN LAS QUE EL USUARIO TENGA ESE TIPO DE ACCESO
            $sqlAdminSubzonas    = "SELECT DISTINCT ADMINISTRADOR_SUBZONA.ID_SUBZONA
							FROM ADMINISTRADOR_SUBZONA 
							INNER JOIN SUBZONA ON ADMINISTRADOR_SUBZONA.ID_SUBZONA = SUBZONA.ID_SUBZONA
							WHERE ID_ADMINISTRADOR = '" . ($this->ID_ADMINISTRADOR) . "'
							AND SUBZONA.BAJA = '0'  
							 $sqlTipoAcceso ";
            $resultAdminSubzonas = $bd->ExecSQL($sqlAdminSubzonas);
            $listadoSubzonas     = "";
            $coma                = "";
            while ($rowAdminSubzona = $bd->SigReg($resultAdminSubzonas)):
                $listadoSubzonas .= $coma . ($rowAdminSubzona->ID_SUBZONA);
                $coma            = ",";
            endwhile;

            //BUSCO EL TIPO DE PERFIL PARA SABER QUE CAMPO DE LA TABLA ALMACEN BUSCAR
            $nombreColumna = "";
            $rowAdm        = $bd->VerReg("ADMINISTRADOR", "ID_ADMINISTRADOR", $this->ID_ADMINISTRADOR, "No");
            $rowPerfil     = $this->ObtenerPerfilAdministrador($this->ID_ADMINISTRADOR);
//				$rowPerfil = $bd->VerReg("ADMINISTRADOR_PERFIL","ID_ADMINISTRADOR_PERFIL",$this->ID_ADMINISTRADOR_PERFIL,"No");
            if ($rowPerfil->TIPO == "tec"):
                $nombreColumna = "ID_SUBZONA_TECNICA";
            elseif ($rowPerfil->TIPO == "log"):
                $nombreColumna = "ID_SUBZONA_LOGISTICA";
            endif;

            //BUSCO LOS ALMACENES
            //BUSCO LOS ALMACENES DE ESAS SUBZONAS (SI HAY SUBZONAS)
            if ($listadoSubzonas != ""):
                //CREO EL WHERE DE LOS ALMACENES DE LAS SUBZONAS
                $whereAlmacenesSubZonas = " OR (TIPO_ALMACEN = 'acciona' AND " . $nombreColumna . " IN (" . $listadoSubzonas . "))";

                //CREO EL WHERE DE LOS VEHICULOS Y PROVEEDORES
                //PRIMERO OBTENGO LOS CENTROS CON AL MENOS UN ALMACEN (DE TIPO acciona) CON PERMISO
                $sqlCentrosPermiso    = "SELECT DISTINCT ID_CENTRO
													FROM ALMACEN 
													WHERE TIPO_ALMACEN = 'acciona' AND " . $nombreColumna . " IN (" . $listadoSubzonas . ")
													AND BAJA = '0'";
                $resultCentrosPermiso = $bd->ExecSQL($sqlCentrosPermiso);

                //CREO EL LISTADO DE CENTROS
                if ($bd->NumRegs($resultCentrosPermiso) > 0):
                    $listadoCentros = "";
                    $coma           = "";
                    while ($rowCentro = $bd->SigReg($resultCentrosPermiso)):
                        $listadoCentros .= $coma . ($rowCentro->ID_CENTRO);
                        $coma           = ",";
                    endwhile;
                else:
                    $listadoCentros = "NULL";
                endif;

                if ($listadoCentros != "NULL"):
                    $listadoCentros = "(" . $listadoCentros . ")";

                    $whereVehiculos          = " OR (TIPO_ALMACEN = 'vehiculo' AND ID_CENTRO IN $listadoCentros)";
                    $whereProveedores        = " OR (TIPO_ALMACEN = 'proveedor' AND ID_CENTRO IN $listadoCentros)";
                    $whereStockExternalizado = " OR (TIPO_ALMACEN = 'externalizado' AND ID_CENTRO IN $listadoCentros)";
                else:
                    $whereVehiculos          = " OR (TIPO_ALMACEN = 'vehiculo' AND ID_CENTRO IS NULL)";
                    $whereProveedores        = " OR (TIPO_ALMACEN = 'proveedor' AND ID_CENTRO IS NULL)";
                    $whereStockExternalizado = " OR (TIPO_ALMACEN = 'externalizado' AND ID_CENTRO IS NULL)";
                endif;
            endif;

            //CONSULTA FINAL
            $sqlAlmacenes = "SELECT DISTINCT ID_ALMACEN
								FROM ALMACEN 
								WHERE (1=0 $whereAlmacenesSubZonas $whereVehiculos $whereProveedores $whereStockExternalizado)
								AND BAJA = '0'";

            $resultAlmacenes = $bd->ExecSQL($sqlAlmacenes);
            while ($rowAlmacen = $bd->SigReg($resultAlmacenes)):
                $arrayAlmacenes[] = $rowAlmacen->ID_ALMACEN;
            endwhile;
        endif;


        //CREO LA RESPUESTA CON EL LISTADO DE ALMACENES OBTENIDOS
        if ($formatoRespuesta == "ARRAY"):
            $respuesta = $arrayAlmacenes;

        elseif ($formatoRespuesta == "STRING"):

            if (count( (array)$arrayAlmacenes) > 0):
                $listadoAlmacenes = "";
                $coma             = "";
                foreach ($arrayAlmacenes as $idAlmacen):
                    $listadoAlmacenes .= $coma . $idAlmacen;
                    $coma             = ",";
                endforeach;
            else:
                $listadoAlmacenes = "NULL";
            endif;

            $respuesta = "(" . $listadoAlmacenes . ")";

        endif;

        //DEVULEVO EL RESULTADO
        return $respuesta;
    }

    /**
     * DEVUELVE LOS CENTROS DEL ADMINISTRADOR PARA LOS CUALES TIENE EL PERMISO INDICADO
     * Y LOS DEVUELVE EN EL FORMATO DE RESPUESTA INDICADO
     * $tipoAcceso = 'Lectura' / 'Escritura'    (SI ES 'Lectura' SACAREMOS LOS QUE TENGAN 'Lectura' o 'Escritura'; SI ES 'Escritura' SACAREMOS  SOLO LOS QUE TENGAN 'Escritura')
     * $formatoRespuesta = 'ARRAY' / 'STRING'   (STRING SE UTILIZARA PARA SQL, DEVOLVERA POR EJEMPLO (342,3443,452) O (NULL) SI NO ENCUENTRA NADA
     * NOTA: SOLO LIMITAREMOS ACCESO A CENTRO POR SUBZONAS SI EL ALMACEN ES DE TIPO 'acciona',
     * SI ES 'vehiculo' MOSTRAREMOS AQUELLOS VEHICULOS QUE PERTENCZCAN A UN CENTRO QUE TENGA AL MENOS UN ALMACEN CON EL PERMISO BUSCADO
     * SI ES 'proveedor' NO LIMITAMOS
     **/
    function listadoCentrosPermiso($tipoAcceso = "Lectura", $formatoRespuesta = "ARRAY")
    {
        global $bd;

        $respuesta = NULL;

        $arrayCentros = array();


        $rowAdmin = $bd->VerReg("ADMINISTRADOR", "ID_ADMINISTRADOR", $this->ID_ADMINISTRADOR, "No");

        //SI EL ADMINISTRADOR NO ESTA RESTRINGIDO POR ZONAS, SERAN TODOS LOS CENTROS
        if ($rowAdmin->RESTRINGIDO_POR_ZONAS == "0"):

            //BUSCO TODOS LOS CENTROS
            $sqlCentros    = "SELECT DISTINCT ID_CENTRO FROM CENTRO WHERE BAJA = '0' ";
            $resultCentros = $bd->ExecSQL($sqlCentros);
            while ($rowCentro = $bd->SigReg($resultCentros)):
                $arrayCentros[] = $rowCentro->ID_CENTRO;
            endwhile;

        //SI ESTA RESTRINGIDO POR ZONAS
        else:
            //SI ES 'Lectura' SACAREMOS LOS QUE TENGAN 'Lectura' o 'Escritura'; SI ES 'Escritura' SACAREMOS  SOLO LOS QUE TENGAN 'Escritura'
            $sqlTipoAcceso = " AND TIPO_ACCESO = '" . $tipoAcceso . "' ";
            if ($tipoAcceso == "Escritura"):
                $sqlTipoAcceso = " AND TIPO_ACCESO = 'Escritura' ";
            elseif ($tipoAcceso == "Lectura"):
                $sqlTipoAcceso = " AND (TIPO_ACCESO = 'Lectura' OR TIPO_ACCESO = 'Escritura')  ";
            endif;

            //PRIMERO BUSCO LAS SUBZONAS EN LAS QUE EL USUARIO TENGA ESE TIPO DE ACCESO
            $sqlAdminSubzonas    = "SELECT DISTINCT ADMINISTRADOR_SUBZONA.ID_SUBZONA
														FROM ADMINISTRADOR_SUBZONA 
														INNER JOIN SUBZONA ON ADMINISTRADOR_SUBZONA.ID_SUBZONA = SUBZONA.ID_SUBZONA WHERE ID_ADMINISTRADOR = '" . ($this->ID_ADMINISTRADOR) . "' AND SUBZONA.BAJA = '0'
														 $sqlTipoAcceso ";
            $resultAdminSubzonas = $bd->ExecSQL($sqlAdminSubzonas);
            $listadoSubzonas     = "";
            $coma                = "";
            while ($rowAdminSubzona = $bd->SigReg($resultAdminSubzonas)):
                $listadoSubzonas .= $coma . ($rowAdminSubzona->ID_SUBZONA);
                $coma            = ",";
            endwhile;

            //BUSCO EL TIPO DE PERFIL PARA SABER QUE CAMPO DE LA TABLA ALMACEN BUSCAR
            $nombreColumna = "";
            $rowPerfil     = $bd->VerReg("ADMINISTRADOR_PERFIL", "ID_ADMINISTRADOR_PERFIL", $this->ID_ADMINISTRADOR_PERFIL, "No");
            if ($rowPerfil->TIPO == "tec"):
                $nombreColumna = "ID_SUBZONA_TECNICA";
            elseif ($rowPerfil->TIPO == "log"):
                $nombreColumna = "ID_SUBZONA_LOGISTICA";
            endif;

            //BUSCO LOS CENTROS
            //BUSCO LOS CENTROS DE LOS ALMACENES DE ESAS SUBZONAS (SI HAY SUBZONAS)
            if ($listadoSubzonas != ""):
                //CREO EL WHERE DE LOS ALMACENES DE LAS SUBZONAS
                $whereAlmacenesSubZonas = " OR (TIPO_ALMACEN = 'acciona' AND " . $nombreColumna . " IN (" . $listadoSubzonas . "))";

                //CREO EL WHERE DE LOS VEHICULOS
                //PRIMERO OBTENGO LOS CENTROS CON AL MENOS UN ALMACEN (DE TIPO acciona) CON PERMISO
                $sqlCentrosPermiso    = "SELECT DISTINCT ID_CENTRO
																	FROM ALMACEN 
																	WHERE TIPO_ALMACEN = 'acciona' AND " . $nombreColumna . " IN (" . $listadoSubzonas . ") AND BAJA = '0'";
                $resultCentrosPermiso = $bd->ExecSQL($sqlCentrosPermiso);

                //CREO EL LISTADO DE CENTROS
                if ($bd->NumRegs($resultCentrosPermiso) > 0):
                    $listadoCentros = "";
                    $coma           = "";
                    while ($rowCentro = $bd->SigReg($resultCentrosPermiso)):
                        $listadoCentros .= $coma . ($rowCentro->ID_CENTRO);
                        $coma           = ",";
                    endwhile;
                else:
                    $listadoCentros = "NULL";
                endif;

                if ($listadoCentros != "NULL"):
                    $listadoCentros = "(" . $listadoCentros . ")";

                    $whereVehiculos          = " OR (TIPO_ALMACEN = 'vehiculo' AND ID_CENTRO IN $listadoCentros)";
                    $whereProveedores        = " OR (TIPO_ALMACEN = 'proveedor' AND ID_CENTRO IN $listadoCentros)";
                    $whereStockExternalizado = " OR (TIPO_ALMACEN = 'externalizado' AND ID_CENTRO IN $listadoCentros)";
                else:
                    $whereVehiculos          = " OR (TIPO_ALMACEN = 'vehiculo' AND ID_CENTRO IS NULL)";
                    $whereProveedores        = " OR (TIPO_ALMACEN = 'proveedor' AND ID_CENTRO IS NULL)";
                    $whereStockExternalizado = " OR (TIPO_ALMACEN = 'externalizado' AND ID_CENTRO IS NULL)";
                endif;
            endif;

            //CONSULTA FINAL
            $sqlCentros = "SELECT DISTINCT ID_CENTRO
											FROM ALMACEN 
											WHERE (1=0 $whereAlmacenesSubZonas $whereVehiculos $whereProveedores $whereStockExternalizado)
											AND BAJA = '0'";

            $resultCentros = $bd->ExecSQL($sqlCentros);
            while ($rowCentro = $bd->SigReg($resultCentros)):
                $arrayCentros[] = $rowCentro->ID_CENTRO;
            endwhile;
        endif;


        //CREO LA RESPUESTA CON EL LISTADO DE ALMACENES OBTENIDOS
        if ($formatoRespuesta == "ARRAY"):
            $respuesta = $arrayCentros;

        elseif ($formatoRespuesta == "STRING"):

            if (count( (array)$arrayCentros) > 0):
                $listadoCentros = "";
                $coma           = "";
                foreach ($arrayCentros as $idCentro):
                    $listadoCentros .= $coma . $idCentro;
                    $coma           = ",";
                endforeach;
            else:
                $listadoCentros = "NULL";
            endif;

            $respuesta = "(" . $listadoCentros . ")";

        endif;

        //DEVULEVO EL RESULTADO
        return $respuesta;
    }

    /**
     * DEVUELVE LOS CENTROS FISICOS DEL ADMINISTRADOR PARA LOS CUALES TIENE EL PERMISO INDICADO
     * Y LOS DEVUELVE EN EL FORMATO DE RESPUESTA INDICADO
     * $tipoAcceso = 'Lectura' / 'Escritura'    (SI ES 'Lectura' SACAREMOS LOS QUE TENGAN 'Lectura' o 'Escritura'; SI ES 'Escritura' SACAREMOS  SOLO LOS QUE TENGAN 'Escritura')
     * $formatoRespuesta = 'ARRAY' / 'STRING'   (STRING SE UTILIZARA PARA SQL, DEVOLVERA POR EJEMPLO (342,3443,452) O (NULL) SI NO ENCUENTRA NADA
     * NOTA: SOLO LIMITAREMOS ACCESO A CENTRO POR SUBZONAS SI EL ALMACEN ES DE TIPO 'acciona',
     * SI ES 'vehiculo' MOSTRAREMOS AQUELLOS VEHICULOS QUE PERTENCZCAN A UN CENTRO QUE TENGA AL MENOS UN ALMACEN CON EL PERMISO BUSCADO
     * SI ES 'proveedor' NO LIMITAMOS
     **/
    function listadoCentrosFisicosPermiso($tipoAcceso = "Lectura", $formatoRespuesta = "ARRAY")
    {
        global $bd;

        $respuesta = NULL;

        $arrayCentros = array();


        $rowAdmin = $bd->VerReg("ADMINISTRADOR", "ID_ADMINISTRADOR", $this->ID_ADMINISTRADOR, "No");

        //SI EL ADMINISTRADOR NO ESTA RESTRINGIDO POR ZONAS, SERAN TODOS LOS CENTROS
        if ($rowAdmin->RESTRINGIDO_POR_ZONAS == "0"):

            //BUSCO TODOS LOS CENTROS
            $sqlCentrosFisicos    = "SELECT DISTINCT ID_CENTRO_FISICO FROM CENTRO_FISICO WHERE BAJA = '0' ";
            $resultCentrosFisicos = $bd->ExecSQL($sqlCentrosFisicos);
            while ($rowCentroFisico = $bd->SigReg($resultCentrosFisicos)):
                $arrayCentros[] = $rowCentroFisico->ID_CENTRO_FISICO;
            endwhile;

        //SI ESTA RESTRINGIDO POR ZONAS
        else:
            //SI ES 'Lectura' SACAREMOS LOS QUE TENGAN 'Lectura' o 'Escritura'; SI ES 'Escritura' SACAREMOS  SOLO LOS QUE TENGAN 'Escritura'
            $sqlTipoAcceso = " AND TIPO_ACCESO = '" . $tipoAcceso . "' ";
            if ($tipoAcceso == "Escritura"):
                $sqlTipoAcceso = " AND TIPO_ACCESO = 'Escritura' ";
            elseif ($tipoAcceso == "Lectura"):
                $sqlTipoAcceso = " AND (TIPO_ACCESO = 'Lectura' OR TIPO_ACCESO = 'Escritura')  ";
            endif;

            //PRIMERO BUSCO LAS SUBZONAS EN LAS QUE EL USUARIO TENGA ESE TIPO DE ACCESO
            $sqlAdminSubzonas    = "SELECT DISTINCT ADMINISTRADOR_SUBZONA.ID_SUBZONA
														FROM ADMINISTRADOR_SUBZONA 
														INNER JOIN SUBZONA ON ADMINISTRADOR_SUBZONA.ID_SUBZONA = SUBZONA.ID_SUBZONA WHERE ID_ADMINISTRADOR = '" . ($this->ID_ADMINISTRADOR) . "' AND SUBZONA.BAJA = '0'
														 $sqlTipoAcceso ";
            $resultAdminSubzonas = $bd->ExecSQL($sqlAdminSubzonas);
            $listadoSubzonas     = "";
            $coma                = "";
            while ($rowAdminSubzona = $bd->SigReg($resultAdminSubzonas)):
                $listadoSubzonas .= $coma . ($rowAdminSubzona->ID_SUBZONA);
                $coma            = ",";
            endwhile;

            //BUSCO EL TIPO DE PERFIL PARA SABER QUE CAMPO DE LA TABLA ALMACEN BUSCAR
            $nombreColumna = "";
            $rowPerfil     = $bd->VerReg("ADMINISTRADOR_PERFIL", "ID_ADMINISTRADOR_PERFIL", $this->ID_ADMINISTRADOR_PERFIL, "No");
            if ($rowPerfil->TIPO == "tec"):
                $nombreColumna = "ID_SUBZONA_TECNICA";
            elseif ($rowPerfil->TIPO == "log"):
                $nombreColumna = "ID_SUBZONA_LOGISTICA";
            endif;

            //BUSCO LOS CENTROS
            //BUSCO LOS CENTROS DE LOS ALMACENES DE ESAS SUBZONAS (SI HAY SUBZONAS)
            if ($listadoSubzonas != ""):
                //CREO EL WHERE DE LOS ALMACENES DE LAS SUBZONAS
                $whereAlmacenesSubZonas = " OR (TIPO_ALMACEN = 'acciona' AND " . $nombreColumna . " IN (" . $listadoSubzonas . "))";

                //CREO EL WHERE DE LOS VEHICULOS
                //PRIMERO OBTENGO LOS CENTROS CON AL MENOS UN ALMACEN (DE TIPO acciona) CON PERMISO
                $sqlCentrosFisicosPermiso    = "SELECT DISTINCT ID_CENTRO_FISICO
																	FROM ALMACEN 
																	WHERE TIPO_ALMACEN = 'acciona' AND " . $nombreColumna . " IN (" . $listadoSubzonas . ") AND BAJA = '0'";
                $resultCentrosFisicosPermiso = $bd->ExecSQL($sqlCentrosFisicosPermiso);

                //CREO EL LISTADO DE CENTROS
                if ($bd->NumRegs($resultCentrosFisicosPermiso) > 0):
                    $listadoCentrosFisicos = "";
                    $coma                  = "";
                    while ($rowCentroFisico = $bd->SigReg($resultCentrosFisicosPermiso)):
                        $listadoCentrosFisicos .= $coma . ($rowCentroFisico->ID_CENTRO_FISICO);
                        $coma                  = ",";
                    endwhile;
                else:
                    $listadoCentrosFisicos = "NULL";
                endif;

                if ($listadoCentrosFisicos != "NULL"):
                    $listadoCentrosFisicos = "(" . $listadoCentrosFisicos . ")";

                    $whereVehiculos          = " OR (TIPO_ALMACEN = 'vehiculo' AND ID_CENTRO_FISICO IN $listadoCentrosFisicos)";
                    $whereProveedores        = " OR (TIPO_ALMACEN = 'proveedor' AND ID_CENTRO_FISICO IN $listadoCentrosFisicos)";
                    $whereStockExternalizado = " OR (TIPO_ALMACEN = 'externalizado' AND ID_CENTRO_FISICO IN $listadoCentrosFisicos)";
                else:
                    $whereVehiculos          = " OR (TIPO_ALMACEN = 'vehiculo' AND ID_CENTRO_FISICO IS NULL)";
                    $whereProveedores        = " OR (TIPO_ALMACEN = 'proveedor' AND ID_CENTRO_FISICO IS NULL)";
                    $whereStockExternalizado = " OR (TIPO_ALMACEN = 'externalizado' AND ID_CENTRO_FISICO IS NULL)";
                endif;
            endif;

            //CONSULTA FINAL
            $sqlCentrosFisicos    = "SELECT DISTINCT ID_CENTRO_FISICO
											FROM ALMACEN 
											WHERE (1=0 $whereAlmacenesSubZonas $whereVehiculos $whereProveedores $whereStockExternalizado)
											AND BAJA = '0'";
            $resultCentrosFisicos = $bd->ExecSQL($sqlCentrosFisicos);
            while ($rowCentroFisico = $bd->SigReg($resultCentrosFisicos)):
                $arrayCentrosFisicos[] = $rowCentroFisico->ID_CENTRO_FISICO;
            endwhile;
        endif;

        //CREO LA RESPUESTA CON EL LISTADO DE ALMACENES OBTENIDOS
        if ($formatoRespuesta == "ARRAY"):
            $respuesta = $arrayCentrosFisicos;

        elseif ($formatoRespuesta == "STRING"):

            if (count( (array)$arrayCentrosFisicos) > 0):
                $listadoCentrosFisicos = "";
                $coma                  = "";
                foreach ($arrayCentrosFisicos as $idCentroFisico):
                    $listadoCentrosFisicos .= $coma . $idCentroFisico;
                    $coma                  = ",";
                endforeach;
            else:
                $listadoCentrosFisicos = "NULL";
            endif;

            $respuesta = "(" . $listadoCentrosFisicos . ")";

        endif;

        //DEVULEVO EL RESULTADO
        return $respuesta;
    }

    /**
     * DEVUELVE (EN LAS VARIABLES INDICADAS) LOS VALORES DE ID Y TEXTO QUE TENGA POR DEFECTO EL ADMINISTRADOR.
     * SI LAS VARIABLES HAN SIDO INDICADAS POR EL USUARIO, NO SE APLICA EL VALOR POR DEFECTO.
     * $campoAdministrador = "ALMACEN"/"CENTRO_FISICO"   (nombre del campo del que queremos el valor por defecto)
     * $variable_id (varible a la que queremos asignar el id por defecto)
     * $variable_tx (varible a la que queremos asignar el valor en texto por defecto)
     **/
    function precargarValorDefectoSiNecesario($campoAdministrador, &$variable_id, &$variable_tx, $soloReferencia = true)
    {
        global $bd;

        //PRIMERO PRECARGO LOS DATOS POR SI HAN CAMBIADO PUEDEN CAMBIAR POR PDA O POR WEB)
        $rowAdmin                           = $bd->VerReg("ADMINISTRADOR", "ID_ADMINISTRADOR", $this->ID_ADMINISTRADOR, "No");
        $this->ID_ALMACEN_POR_DEFECTO       = $rowAdmin->ID_ALMACEN_POR_DEFECTO;
        $this->ID_CENTRO_FISICO_POR_DEFECTO = $rowAdmin->ID_CENTRO_FISICO_POR_DEFECTO;

        //ALMACÉN
        if ($campoAdministrador == "ALMACEN"):

            //SI EL ADMINISTRADOR NO TIENE POR DEFECTO, DIRECTAMENTE SALIMOS SIN HACER NADA
            if ($this->ID_ALMACEN_POR_DEFECTO == NULL || $this->ID_ALMACEN_POR_DEFECTO == ""):
                return;
            endif;

            //SI LA VARIABLE TODAVIA NO TIENE VALOR (ES DECIR, SI EL USUARIO NO HA METIDO ALGO 'A MANO' EN EL FILTRO)
            if (!isset($variable_id) && !isset($variable_tx)):

                //OBTENGO EL DATO Y LO APLICO
                $NotificaErrorPorEmail = "No";
                $rowAlmacen            = $bd->VerReg("ALMACEN", "ID_ALMACEN", $this->ID_ALMACEN_POR_DEFECTO, "No");
                if ($rowAlmacen):
                    $variable_id = $rowAlmacen->ID_ALMACEN;
                    if ($soloReferencia == true):
                        $variable_tx = $rowAlmacen->REFERENCIA;
                    else:
                        $variable_tx = $rowAlmacen->REFERENCIA . ' - ' . $rowAlmacen->NOMBRE;
                    endif;
                endif;
                unset($NotificaErrorPorEmail);

            endif;
        endif;

        //CENTRO
        if ($campoAdministrador == "CENTRO"):

            //SI EL ADMINISTRADOR NO TIENE POR DEFECTO, DIRECTAMENTE SALIMOS SIN HACER NADA
            if ($this->ID_ALMACEN_POR_DEFECTO == NULL || $this->ID_ALMACEN_POR_DEFECTO == ""):
                return;
            endif;

            //SI LA VARIABLE TODAVIA NO TIENE VALOR (ES DECIR, SI EL USUARIO NO HA METIDO ALGO 'A MANO' EN EL FILTRO)
            if (!isset($variable_id) && !isset($variable_tx)):

                //OBTENGO EL DATO Y LO APLICO
                $NotificaErrorPorEmail = "No";
                $rowAlmacen            = $bd->VerReg("ALMACEN", "ID_ALMACEN", $this->ID_ALMACEN_POR_DEFECTO, "No");
                if ($rowAlmacen):
                    //OBTENGO EL DATO DEL CENTRO Y LO APLICO
                    $NotificaErrorPorEmail = "No";
                    $rowCentro             = $bd->VerReg("CENTRO", "ID_CENTRO", $rowAlmacen->ID_CENTRO, "No");
                    if ($rowCentro):
                        $variable_id = $rowCentro->ID_CENTRO;
                        if ($soloReferencia == true):
                            $variable_tx = $rowCentro->REFERENCIA;
                        else:
                            $variable_tx = $rowCentro->REFERENCIA . ' - ' . $rowCentro->CENTRO;
                        endif;
                    endif;
                endif;
                unset($NotificaErrorPorEmail);

            endif;
        endif;

        //CENTRO_FISICO
        if ($campoAdministrador == "CENTRO_FISICO"):

            //SI EL ADMINISTRADOR NO TIENE POR DEFECTO, DIRECTAMENTE SALIMOS SIN HACER NADA
            if ($this->ID_CENTRO_FISICO_POR_DEFECTO == NULL || $this->ID_CENTRO_FISICO_POR_DEFECTO == ""):
                return;
            endif;

            //SI LA VARIABLE TODAVIA NO TIENE VALOR (ES DECIR, SI EL USUARIO NO HA METIDO ALGO 'A MANO' EN EL FILTRO)
            if (!isset($variable_id) && !isset($variable_tx)):

                //OBTENGO EL DATO Y LO APLICO
                $NotificaErrorPorEmail = "No";
                $rowCentroFisico       = $bd->VerReg("CENTRO_FISICO", "ID_CENTRO_FISICO", $this->ID_CENTRO_FISICO_POR_DEFECTO, "No");
                if ($rowCentroFisico):
                    $variable_id = $rowCentroFisico->ID_CENTRO_FISICO;
                    if ($soloReferencia == true):
                        $variable_tx = $rowCentroFisico->REFERENCIA;
                    else:
                        $variable_tx = $rowCentroFisico->REFERENCIA . ' - ' . $rowCentroFisico->DENOMINACION_CENTRO_FISICO;
                    endif;
                endif;
                unset($NotificaErrorPorEmail);

            endif;
        endif;
    }


    /**
     * FUNCCION PARA DEVOLVER LOS CORREOS DEL PROVEEDOR
     * ESPECIFICANDO EL TIPO
     * Y EN EL CASO DE LAS CONTRATACIONES AQUELLOS SIN TARIFA
     * DEVUELVE ARRAY DE EMAILS
     */
    function obtenerEmailsProveedor($idProveedor, $tipo = "", $idTarifa = "", $contratacionSinTarifa = 0)
    {
        global $bd;
        $rowProveedor = $bd->VerReg("PROVEEDOR", "ID_PROVEEDOR", $idProveedor, "No");
        $sqlEmails    = "";
        $arrEmails    = array();

        //SI ES DEL TIPO CONTRATACION
        //COMPROBAMOS SI SON CONTRATACIONES ESPECIFICAS DE UNA TARIFA
        // O LAS QUE NO TIEEN TARIFA
        // O TODAS (SIN TARIFA ASOCIADA)
        if ($tipo == "Contratacion Tarifa"):
            if ($idTarifa != ""):
                $sqlEmails = "SELECT * FROM PROVEEDOR_EMAIL PE
                                  WHERE PE.ID_PROVEEDOR = $rowProveedor->ID_PROVEEDOR
                                  AND PE.BAJA = 0 AND TIPO = '$tipo' AND (ID_TARIFA = $idTarifa OR ID_TARIFA IS NULL)
                                  AND PE.CONTRATACIONES_SIN_TARIFA = 0";

            elseif ($idTarifa == "" && $contratacionSinTarifa == 1):
                $sqlEmails = "SELECT * FROM PROVEEDOR_EMAIL PE
                                  WHERE PE.ID_PROVEEDOR = $rowProveedor->ID_PROVEEDOR
                                  AND PE.BAJA = 0 AND TIPO = '$tipo' AND ID_TARIFA IS NULL
                                  AND PE.CONTRATACIONES_SIN_TARIFA = $contratacionSinTarifa";

            else:
                $sqlEmails = "SELECT * FROM PROVEEDOR_EMAIL PE
                                  WHERE PE.ID_PROVEEDOR = $rowProveedor->ID_PROVEEDOR
                                  AND PE.BAJA = 0 AND TIPO = '$tipo'
                                  AND PE.CONTRATACIONES_SIN_TARIFA = 0";
            endif;

        //RESTO DE TIPOS
        elseif ($tipo != "Contratacion Tarifa" && $tipo != ""):
            $sqlEmails = "SELECT * FROM PROVEEDOR_EMAIL PE
                             WHERE PE.ID_PROVEEDOR = $rowProveedor->ID_PROVEEDOR AND PE.BAJA = 0 AND TIPO = '$tipo' ";

        //SI NO TIENE TIPO, ES EL CORREO NORMAL DEL PROVEEDOR
        elseif ($tipo == ""):
            $arrEmails[] = $rowProveedor->EMAIL;
        endif;

        if ($sqlEmails != ""):
            $resultEmails = $bd->ExecSQL($sqlEmails, "No");
            if ($resultEmails != false && $bd->NumRegs($resultEmails) > 0):
                while ($rowEmails = $bd->SigReg($resultEmails)):
                    $arrEmails[] = $rowEmails->EMAIL;
                endwhile;
            endif;
        endif;

        return array_unique( (array)$arrEmails);
    }

    /***********FUNCIONES ANDROID ************************/
    function Insertar_Log_Android($codigoLlamada, $tipoServicio, $nombreFuncion)
    {
        global $bd;
        global $auxiliar;

        //SI VIENEN ADJUNTOS
        $textoAdjuntos = "";
        if (isset($_FILES)):
            $coma = "";
            foreach ($_FILES as $nombreVariable => $arrayFile):
                $textoAdjuntos .= $coma . $nombreVariable . " : " . $arrayFile['name'];
                $coma          = "; ";
            endforeach;
        endif;

        //GUARDAMOS LA LLAMADA
        $sqlInsert = "INSERT INTO LOG_EJECUCION_ANDROID SET
                          FECHA = '" . date("Y-m-d H:i:s") . "'
                        , LLAMADA = '" . $bd->escapeCondicional($codigoLlamada) . "'
                        , SERVICIO = '" . $bd->escapeCondicional($tipoServicio) . "'
                        , FUNCION = '" . $bd->escapeCondicional($nombreFuncion) . "'
                        , ADJUNTOS = " . ($textoAdjuntos == "" ? "NULL" : "'" . $bd->escapeCondicional($textoAdjuntos) . "'");
        $bd->ExecSQL($sqlInsert);

        return $bd->IdAsignado();
    }

    function Modificar_Log_Android($idLogAndroid, $statusRespuesta, $respuestaJSON)
    {
        global $bd;
        global $auxiliar;

        //GUARDAMOS LA LLAMADA
        $sqlUpdate = "UPDATE LOG_EJECUCION_ANDROID SET
                          ID_ADMINISTRADOR = " . ($this->ID_ADMINISTRADOR != "" ? $this->ID_ADMINISTRADOR : "NULL") . "
                        , RESULTADO = '" . $bd->escapeCondicional($statusRespuesta) . "'
                        , RESPUESTA = '" . $bd->escapeCondicional($respuestaJSON) . "'
                        WHERE ID_LOG_EJECUCION_ANDROID = $idLogAndroid";
        $bd->ExecSQL($sqlUpdate);

    }

    /**
     * @param $token_usuario
     * @param string $continuarTrasError SI VIENE CON VALOR "Si" entonces devolvemos codigo de error en vez de salirnos de la ejecucion. RELLENAMOS LOS DATOS de $administrador
     */
    function Comprobar_Token_Android($token_usuario, $continuarTrasError = "")
    {

        global $bd;
        global $auxiliar;
        global $idLogAndroid;


        //BUSCAMOS EL TOKEN DEL USUARIO
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowAdmin                         = $bd->VerReg("ADMINISTRADOR", "ID_ADMINISTRADOR", $this->ID_ADMINISTRADOR, "No");

        //COMPROBAMOS QUE LOS TOKEN  EXISTEN Y COINCIDEN
        if (($rowAdmin == false) || ($rowAdmin->TOKEN_ANDROID != $token_usuario) || ($rowAdmin->TOKEN_ANDROID == '')):

            $this->ID_ADMINISTRADOR = $rowAdmin->ID_ADMINISTRADOR;

            //SI NOS INDICAN QUE QUIEREN CONTINUAR LA EJECUCION, DEVOLVEMOS FALSE
            if ($continuarTrasError == "Si"):
                return false;

            else://SI NO, GUARDAMOS EL LOG Y ENVIAMOS EL ERROR A LA APP

                //HEADER JSON
                header('Content-type: application/json');


                //GENERAMOS EL ARRAY FINAL Y LO DEVOLVEMOS
                $arr = array('status' => '4', 'status_descripcion' => 'KO', 'data' => '', 'arr_errores' => "Sesion Expirada");


                //INICIO LA TRANSACCION
                $bd->begin_transaction();


                //GUARDAMOS LA RESPUESTA
                $this->Modificar_Log_Android($idLogAndroid, "KO", json_encode($arr));


                //FINALIZO LA TRANSACCION
                $bd->commit_transaction();


                echo json_encode($arr);

                exit;

            endif;
        else: //CARGAMOS DATOS DEL ADMINISTRADOR

            $this->ID_PROVEEDOR = $rowAdmin->ID_PROVEEDOR;

            // GRABO LOS DATOS DEL USUARIO EN LA SESION
            $this->ID_ADMINISTRADOR   = $rowAdmin->ID_ADMINISTRADOR;
            $this->SUPERADMINISTRADOR = $rowAdmin->SUPERADMINISTRADOR;
            $this->USUARIO_SAP        = $rowAdmin->USUARIO_SAP;
            //$this->NOMBRE = $row->NOMBRE;
            //$this->LOGIN = $row->LOGIN;
            //$this->ULTIMA_IP = $row->ULTIMA_IP;
            //$this->ULTIMA_FECHA = $row->ftoULTIMA_FECHA;
            //$this->ULTIMA_HORA = $row->ftoULTIMA_HORA;
            //$this->ULTIMO_IDIOMA = $idioma;
            //$this->ID_IDIOMA = $idioma;
            $this->ID_ADMINISTRADOR_PERFIL      = $rowAdmin->ID_ADMINISTRADOR_PERFIL;
            $this->PUEDE_VER_IMPORTES           = $rowAdmin->VISUALIZACION_IMPORTES;
            $this->COMPRADOR_BPO                = $rowAdmin->ES_COMPRADOR_BPO;
            $this->ID_ALMACEN_POR_DEFECTO       = $rowAdmin->ID_ALMACEN_POR_DEFECTO;
            $this->ID_CENTRO_FISICO_POR_DEFECTO = $rowAdmin->ID_CENTRO_FISICO_POR_DEFECTO;
            $this->FMTO_FECHA                   = $rowAdmin->FMTO_FECHA;
            $this->FMTO_FECHA_PRIMER_DIA_SEMANA = $rowAdmin->FMTO_FECHA_PRIMER_DIA_SEMANA;
            $this->ID_HUSO_HORARIO              = $rowAdmin->ID_HUSO_HORARIO;
            $rowHusoHorario                     = $bd->VerReg("HUSO_HORARIO_", "ID_HUSO_HORARIO", $rowAdmin->ID_HUSO_HORARIO);
            $this->ID_HUSO_HORARIO_PHP          = $rowHusoHorario->ID_HUSO_HORARIO_PHP;
            $this->FMTO_CSV                     = $rowAdmin->FMTO_CSV;
            $this->IDIOMA_NOTIFICACIONES        = $rowAdmin->IDIOMA_NOTIFICACIONES;
            $this->ID_PROVEEDOR                 = $rowAdmin->ID_PROVEEDOR;

            $this->ID_PAIS                                 = $rowAdmin->ID_PAIS;
            $this->ID_EMPRESA                              = $rowAdmin->ID_EMPRESA;
            $this->FECHA_FIN_ACCESO_SIN_DATOS_CONFIGURADOS = $rowAdmin->FECHA_FIN_ACCESO_SIN_DATOS_CONFIGURADOS;

        endif;

        return true;

    }


    /**
     * @param $nombreError con el error que provoca KO
     *  Guardar el Log y devuelve el json
     */
    function Devolver_KO_Android($nombreError)
    {

        global $bd;
        global $auxiliar;
        global $idLogAndroid;


        //HEADER JSON
        header('Content-type: application/json');


        //GENERAMOS EL ARRAY FINAL Y LO DEVOLVEMOS
        $arr = array('status' => '4', 'status_descripcion' => 'KO', 'data' => '', 'arr_errores' => $nombreError);


        //INICIO LA TRANSACCION
        $bd->begin_transaction();


        //GUARDAMOS LA RESPUESTA
        $this->Modificar_Log_Android($idLogAndroid, "KO", json_encode($arr));


        //FINALIZO LA TRANSACCION
        $bd->commit_transaction();


        echo json_encode($arr);

        exit;

    }

} // FIN CLASE
