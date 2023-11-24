<?php

# aviso
# Clase aviso
# Se incluira en las sesiones
# Agosto 2011 David Del Rio

class aviso
{

    function __construct()
    {
    } // Fin pedido

    function LineaMovimientoEntradaAnulada($Destinatario, $Cuerpo)
    {
        global $bd;
        global $auxiliar;
        global $administrador;

        $bd->EnviarEmail($Destinatario, $auxiliar->traduce("Devolución de material", $administrador->ID_IDIOMA), $Cuerpo);
    }

    //GUARDA UN AVISO EN CASO DE TENER DESTINATARIOS, DEVUELVE EL ID DEL AVISO GENERADO
    function GuardarAviso($idObjeto, $tipoObjeto, $asuntoAviso, $cuerpoAviso, $arrAdmin = array(), $rutaAdjunto = '', $nombreAdjunto = '', $nombreAdjunto2 = '', $idCorreo = '')
    {
        global $bd;
        global $auxiliar;
        global $administrador;
        global $NotificaErrorPorEmail;

        $idAviso = "";

        //SI NO HAY ADMIISTRADOR (SI SE LLAMA DESDE ALGUN PROCESO)
        //ASOCIAMOS ADMINISTRADOR PROCESO AUTOMATICO
        if ($administrador == false || $administrador == null || $administrador->ID_ADMINISTRADOR == null):
            $rowAdministrador = $bd->VerReg("ADMINISTRADOR", "LOGIN", "PROCESOAUTOMATICO", "No");
            $idAdministrador  = $rowAdministrador->ID_ADMINISTRADOR;
        else:
            $idAdministrador = $administrador->ID_ADMINISTRADOR;
        endif;

        //SI HAY DESTINATARIOS
        if (count( (array)$arrAdmin) > 0):
            //CREO EL AVISO
            $sqlInsertAviso = " INSERT INTO AVISO SET
                                 TIPO_OBJETO = '$tipoObjeto'
                                 ,ID_OBJETO = $idObjeto
                                 ,ID_ADMINISTRADOR_REMITENTE = $idAdministrador
                                 ,ASUNTO = '" . $bd->escapeCondicional("$asuntoAviso") . "'
                                 ,CUERPO = '" . $bd->escapeCondicional("$cuerpoAviso") . "'
                                 ,RUTA_ADJUNTO =  '" . $bd->escapeCondicional("$rutaAdjunto") . "'
                                 ,NOMBRE_ADJUNTO =  '" . $bd->escapeCondicional("$nombreAdjunto") . "'
                                 ,NOMBRE_ADJUNTO_2 =  '" . $bd->escapeCondicional("$nombreAdjunto2") . "'
                                 ,FECHA_CREACION = '" . date("Y-m-d H:i:s") . "'
                                 ,ID_CORREO = " . ($idCorreo != "" ? $idCorreo : "NULL") . "
                                 ,ENVIADO = 0";
            $bd->ExecSQL($sqlInsertAviso);
            $idAviso = $bd->IdAsignado();

            //ASOCIO EL AVISO A SUS DESTINATARIOS
            foreach ($arrAdmin as $tipoDestinatario => $arrIDs):

                foreach ($arrIDs as $idAdminCorreo):
                    switch ($tipoDestinatario):
                        case "ADMINISTRADOR":
                            $sqlInsertAvisoAdmin = " INSERT INTO AVISO_ADMINISTRADOR SET
                                                          ID_AVISO=$idAviso
                                                         ,ID_ADMINISTRADOR=$idAdminCorreo";
                            $bd->ExecSQL($sqlInsertAvisoAdmin);
                            break;
                        case "PROVEEDOR":
                            $sqlInsertAvisoAdmin = " INSERT INTO AVISO_ADMINISTRADOR SET
                                                          ID_AVISO=$idAviso
                                                         ,ID_PROVEEDOR=$idAdminCorreo";
                            $bd->ExecSQL($sqlInsertAvisoAdmin);
                            break;
                        case "ORDEN_CONTRATACION_COMUNICACION":
                            $sqlInsertAvisoAdmin = " INSERT INTO AVISO_ADMINISTRADOR SET
                                                          ID_AVISO=$idAviso
                                                         ,ID_ORDEN_CONTRATACION_COMUNICACION=$idAdminCorreo";
                            $bd->ExecSQL($sqlInsertAvisoAdmin);
                            break;
                        case "SOLICITUD_TRANSPORTE":
                            $sqlInsertAvisoAdmin = " INSERT INTO AVISO_ADMINISTRADOR SET
                                                          ID_AVISO=$idAviso
                                                         ,ID_SOLICITUD_TRANSPORTE=$idAdminCorreo";
                            $bd->ExecSQL($sqlInsertAvisoAdmin);
                            break;
                        case "SOLICITUD_TERCEROS":
                            $sqlInsertAvisoAdmin = " INSERT INTO AVISO_ADMINISTRADOR SET
                                                          ID_AVISO=$idAviso
                                                         ,ID_SOLICITUD_TERCEROS=$idAdminCorreo";
                            $bd->ExecSQL($sqlInsertAvisoAdmin);
                            break;
                        case "PLANIFICADOR":
                            $sqlInsertAvisoAdmin = " INSERT INTO AVISO_ADMINISTRADOR SET
                                                          ID_AVISO=$idAviso
                                                         ,ID_PLANIFICADOR=$idAdminCorreo";
                            $bd->ExecSQL($sqlInsertAvisoAdmin);
                            break;
                        case "CENTRO_FISICO_INFORME_SGA_COMUNICACION":
                            $sqlInsertAvisoAdmin = " INSERT INTO AVISO_ADMINISTRADOR SET
                                                          ID_AVISO=$idAviso
                                                         ,ID_CENTRO_FISICO_INFORME_SGA_COMUNICACION=$idAdminCorreo";
                            $bd->ExecSQL($sqlInsertAvisoAdmin);
                            break;
                        case "INFORME_SGA_COMUNICACION":
                            $sqlInsertAvisoAdmin = " INSERT INTO AVISO_ADMINISTRADOR SET
                                                          ID_AVISO=$idAviso
                                                         ,ID_INFORME_SGA_COMUNICACION=$idAdminCorreo";
                            $bd->ExecSQL($sqlInsertAvisoAdmin);
                            break;
                    endswitch;
                endforeach;
            endforeach;
        endif;

        return $idAviso;
    }

    //ENVIA UN AVISO A LOS DESTINATARIOS DEL AVISO
    function enviarAviso($idAviso, $prioridadAlta = 0)
    {
        global $bd;
        global $path_raiz;
        global $auxiliar;
        global $administrador;
        global $NotificaErrorPorEmail;

        //BUSCAMOS EL AVISO
        $rowAviso = $bd->VerReg("AVISO", "ID_AVISO", $idAviso);

        //OBTENEMOS LAS VARIABLES GLOBALES DEPENDIENDO DEL TIPO
        if ($rowAviso->TIPO_OBJETO == "NECESIDAD"):
            $fromEmail           = NECESIDADES_REMITENTE_EMAIL;
            $fromNombre          = NECESIDADES_REMITENTE_NOMBRE;
            $enviarUsuarioReales = NECESIDADES_ENVIAR_EMAILS_DESTINATARIOS_REALES;
            $usuariostest        = NECESIDADES_EMAIL_DESTINATARIO_TEST;
            $usuariostest        .= ($usuariostest != "" && NECESIDADES_EMAIL_COPIA_OCULTA != "" ? "," : "") . NECESIDADES_EMAIL_COPIA_OCULTA;
        elseif ($rowAviso->TIPO_OBJETO == "ORDEN_CONTRATACION"):
            $fromEmail           = CONTRATACIONES_REMITENTE_EMAIL;
            $fromNombre          = CONTRATACIONES_REMITENTE_NOMBRE;
            $enviarUsuarioReales = CONTRATACIONES_ENVIAR_EMAILS_DESTINATARIOS_REALES;
            $usuariostest        = CONTRATACIONES_EMAIL_DESTINATARIO_TEST;
        elseif ($rowAviso->TIPO_OBJETO == "ORDEN_MONTAJE"):
            $fromEmail           = ORDEN_MONTAJE_REMITENTE_EMAIL;
            $fromNombre          = ORDEN_MONTAJE_REMITENTE_NOMBRE;
            $enviarUsuarioReales = ORDEN_MONTAJE_ENVIAR_EMAILS_DESTINATARIOS_REALES;
            $usuariostest        = ORDEN_MONTAJE_EMAIL_DESTINATARIO_TEST;
        elseif ($rowAviso->TIPO_OBJETO == "AUTOFACTURA"):
            $fromEmail           = AUTOFACTURAS_REMITENTE_EMAIL;
            $fromNombre          = AUTOFACTURAS_REMITENTE_NOMBRE;
            $enviarUsuarioReales = AUTOFACTURAS_ENVIAR_EMAILS_DESTINATARIOS_REALES;
            $usuariostest        = AUTOFACTURAS_EMAIL_DESTINATARIO_TEST;
        elseif ($rowAviso->TIPO_OBJETO == "ORDEN_PREPARACION"):
            $fromEmail           = PREPARACIONES_REMITENTE_EMAIL;
            $fromNombre          = PREPARACIONES_REMITENTE_NOMBRE;
            $enviarUsuarioReales = PREPARACIONES_ENVIAR_EMAILS_DESTINATARIOS_REALES;
            $usuariostest        = PREPARACIONES_EMAIL_DESTINATARIO_TEST;
        elseif ($rowAviso->TIPO_OBJETO == "FICHA_SEGURIDAD_MATERIAL"):
            $fromEmail           = FICHAS_SEGURIDAD_MATERIAL_REMITENTE_EMAIL;
            $fromNombre          = FICHAS_SEGURIDAD_MATERIAL_REMITENTE_NOMBRE;
            $enviarUsuarioReales = FICHAS_SEGURIDAD_MATERIAL_ENVIAR_EMAILS_DESTINATARIOS_REALES;
            $usuariostest        = FICHAS_SEGURIDAD_MATERIAL_EMAIL_DESTINATARIO_TEST;
        elseif ($rowAviso->TIPO_OBJETO == "PROVEEDOR"):
            $fromEmail           = PROVEEDOR_REMITENTE_EMAIL;
            $fromNombre          = PROVEEDOR_REMITENTE_NOMBRE;
            $enviarUsuarioReales = PROVEEDOR_ENVIAR_EMAILS_DESTINATARIOS_REALES;
            $usuariostest        = PROVEEDOR_EMAIL_DESTINATARIO_TEST;
        elseif ($rowAviso->TIPO_OBJETO == "SOLICITUD_TRANSPORTE"):
            $fromEmail           = SOLICITUDES_TRANSPORTE_REMITENTE_EMAIL;
            $fromNombre          = SOLICITUDES_TRANSPORTE_REMITENTE_NOMBRE;
            $enviarUsuarioReales = SOLICITUDES_TRANSPORTE_ENVIAR_EMAILS_DESTINATARIOS_REALES;
            $usuariostest        = SOLICITUDES_TRANSPORTE_EMAIL_DESTINATARIO_TEST;
        elseif ($rowAviso->TIPO_OBJETO == "SOLICITUD_TERCEROS"): //MISMOS SOLICITUDES
            $fromEmail           = SOLICITUDES_TRANSPORTE_REMITENTE_EMAIL;
            $fromNombre          = SOLICITUDES_TRANSPORTE_REMITENTE_NOMBRE;
            $enviarUsuarioReales = SOLICITUDES_TRANSPORTE_ENVIAR_EMAILS_DESTINATARIOS_REALES;
            $usuariostest        = SOLICITUDES_TRANSPORTE_EMAIL_DESTINATARIO_TEST;
        elseif ($rowAviso->TIPO_OBJETO == "ORDEN_TRANSPORTE_ACCION_AVISO"):
            $fromEmail           = ORDEN_TRANSPORTE_CONSTRUCCION_AVISO_ACCION_REMITENTE_EMAIL;
            $fromNombre          = ORDEN_TRANSPORTE_CONSTRUCCION_AVISO_ACCION_REMITENTE_NOMBRE;
            $enviarUsuarioReales = ORDEN_TRANSPORTE_CONSTRUCCION_AVISO_ACCION_ENVIAR_EMAILS_DESTINATARIOS_REALES;
            $usuariostest        = ORDEN_TRANSPORTE_CONSTRUCCION_AVISO_ACCION_EMAIL_DESTINATARIO_TEST;
        elseif ($rowAviso->TIPO_OBJETO == "RECEPCION_ALMACEN_SOLPED"):
            $fromEmail           = RECEPCION_ALMACEN_SOLPED_REMITENTE_EMAIL;
            $fromNombre          = RECEPCION_ALMACEN_SOLPED_REMITENTE_NOMBRE;
            $enviarUsuarioReales = RECEPCION_ALMACEN_SOLPED_ENVIAR_EMAILS_DESTINATARIOS_REALES;
            $usuariostest        = RECEPCION_ALMACEN_SOLPED_EMAIL_DESTINATARIO_TEST;
        elseif ($rowAviso->TIPO_OBJETO == "CENTRO_FISICO_INFORME_SGA"):
            $fromEmail           = INFORME_CONSTRUCCION_REMITENTE_EMAIL;
            $fromNombre          = INFORME_CONSTRUCCION_REMITENTE_NOMBRE;
            $enviarUsuarioReales = INFORME_CONSTRUCCION_ENVIAR_EMAILS_DESTINATARIOS_REALES;
            $usuariostest        = INFORME_CONSTRUCCION_EMAIL_DESTINATARIO_TEST;
        elseif ($rowAviso->TIPO_OBJETO == "INFORME_SGA"):
            $fromEmail           = INFORME_SGA_REMITENTE_EMAIL;
            $fromNombre          = INFORME_SGA_REMITENTE_NOMBRE;
            $enviarUsuarioReales = INFORME_SGA_ENVIAR_EMAILS_DESTINATARIOS_REALES;
            $usuariostest        = INFORME_SGA_EMAIL_DESTINATARIO_TEST;
        elseif ($rowAviso->TIPO_OBJETO == "INFORME_REPARACION"):
            $fromEmail           = INFORME_REPARACION_REMITENTE_EMAIL;
            $fromNombre          = INFORME_REPARACION_REMITENTE_NOMBRE;
            $enviarUsuarioReales = INFORME_REPARACION_ENVIAR_EMAILS_DESTINATARIOS_REALES;
            $usuariostest        = INFORME_REPARACION_EMAIL_DESTINATARIO_TEST;
        elseif ($rowAviso->TIPO_OBJETO == "SOLICITUD_MATERIAL_ACCION_AVISO"):
            $fromEmail           = SOLICITUDES_MATERIAL_REMITENTE_EMAIL;
            $fromNombre          = SOLICITUDES_MATERIAL_REMITENTE_NOMBRE;
            $enviarUsuarioReales = SOLICITUDES_MATERIAL_ENVIAR_EMAILS_DESTINATARIOS_REALES;
            $usuariostest        = SOLICITUDES_MATERIAL_EMAIL_DESTINATARIO_TEST;
        elseif ($rowAviso->TIPO_OBJETO == "SOLICITUD_MATERIAL_IC"): //MISMOS DATOS QUE SOLICITUDES MATERIAL AVISOS
            $fromEmail           = SOLICITUDES_MATERIAL_REMITENTE_EMAIL;
            $fromNombre          = SOLICITUDES_MATERIAL_REMITENTE_NOMBRE;
            $enviarUsuarioReales = SOLICITUDES_MATERIAL_ENVIAR_EMAILS_DESTINATARIOS_REALES;
            $usuariostest        = SOLICITUDES_MATERIAL_EMAIL_DESTINATARIO_TEST;
        elseif ($rowAviso->TIPO_OBJETO == "SOLICITUD_MATERIAL"): //MISMOS DATOS QUE SOLICITUDES MATERIAL AVISOS
            $fromEmail           = SOLICITUDES_MATERIAL_REMITENTE_EMAIL;
            $fromNombre          = SOLICITUDES_MATERIAL_REMITENTE_NOMBRE;
            $enviarUsuarioReales = SOLICITUDES_MATERIAL_ENVIAR_EMAILS_DESTINATARIOS_REALES;
            $usuariostest        = SOLICITUDES_MATERIAL_EMAIL_DESTINATARIO_TEST;
        elseif ($rowAviso->TIPO_OBJETO == "SOLICITUD_MATERIAL_SERVICIO"): //MISMOS DATOS QUE SOLICITUDES MATERIAL AVISOS
            $fromEmail           = SOLICITUDES_MATERIAL_REMITENTE_EMAIL;
            $fromNombre          = SOLICITUDES_MATERIAL_REMITENTE_NOMBRE;
            $enviarUsuarioReales = SOLICITUDES_MATERIAL_ENVIAR_EMAILS_DESTINATARIOS_REALES;
            $usuariostest        = SOLICITUDES_MATERIAL_EMAIL_DESTINATARIO_TEST;
        else:
            return false;
        endif;

        //ARMAMOS EL MAIL
        //EVITAR SALTOS DE LINEA QUE GENERA EL LECTOR DE CORREO AL EXCEDER DE 990 CARACTERES, PONIENDO ESPACIOS EN BLANCO
        $contents = wordwrap((string)$rowAviso->CUERPO);
        $contents = str_replace( "\n", "\r\n",(string) $contents);
        $contents = str_replace( "\n", "\r\n",(string) $contents);

        $Cuerpo = "<html><head></head><body><p style='font-family: Calibri, sans-serif; font-weight: bold; font-size: 14px;text-align: left;'>" . $contents . "</p><br></body></html>";
        $Asunto = $rowAviso->ASUNTO;

        //      * @param array $arrayAdjuntos[] =array('ruta_adjunto' => 'ruta/', 'nombre_adjunto.ext')
        //     * @param array $arrayFotos[0] = array('ruta_adjunto' => $path_raiz . "administrador/imagenes/" , 'nombre_adjunto' => 'acciona_impresos.jpg', 'nombre_imagen' => 'logo_acciona', 'encoding_imagen' => 'base64', 'tipo_imagen' => 'image/jpeg');

        $arrayAdjuntos = array();
        $arrayFotos    = array();

        //SI TIENE ADJUNTOS
        if ($rowAviso->NOMBRE_ADJUNTO != ""):
            $arrayAdjuntos[] = array('ruta_adjunto' => $path_raiz . "documentos/" . $rowAviso->RUTA_ADJUNTO . $rowAviso->NOMBRE_ADJUNTO, 'nombre_adjunto' => $rowAviso->NOMBRE_ADJUNTO);
        endif;
        if ($rowAviso->NOMBRE_ADJUNTO_2 != ""):
            $arrayAdjuntos[] = array('ruta_adjunto' => $path_raiz . "documentos/" . $rowAviso->RUTA_ADJUNTO . $rowAviso->NOMBRE_ADJUNTO_2, 'nombre_adjunto' => $rowAviso->NOMBRE_ADJUNTO_2);
        endif;

        if ($rowAviso->TIPO_OBJETO == "ORDEN_CONTRATACION")://SI ES ORDEN DE CONTRATACION METEMOS LA IMAGEN POR SI SE MUESTRA EN EL CORREO (EN EL CORREO METEREMOS <img src='cid:logo_acciona'>
            $arrayFotos[] = array('ruta_adjunto' => $path_raiz . "administrador/imagenes/acciona_impresos.jpg", 'nombre_imagen' => 'logo_acciona', 'nombre_adjunto' => "acciona_impresos.jpg",
                'encoding_imagen' => 'base64', 'tipo_imagen' => 'image/jpeg');
        endif;

        $arrCorreos = array();

        //BUSCO LOS DESTINATARIOS DEL AVISO
        //SI ES AUTOFACTURA SE COGE EL EMAIL DE CERTIFICACION DEL PROVEEDOR
        if ($rowAviso->TIPO_OBJETO == "AUTOFACTURA"):
            $sqlDestinatariosProveedores    = " SELECT PE.EMAIL
                                                 FROM AVISO_ADMINISTRADOR AA
                                                 INNER JOIN PROVEEDOR P ON P.ID_PROVEEDOR = AA.ID_PROVEEDOR
                                                 INNER JOIN PROVEEDOR_EMAIL PE ON PE.ID_PROVEEDOR = P.ID_PROVEEDOR
                                                 WHERE AA.ID_AVISO = $rowAviso->ID_AVISO AND AA.BAJA = 0
                                                 AND PE.BAJA = 0 AND TIPO = 'Certificacion'";
            $resultDestinatariosProveedores = $bd->ExecSQL($sqlDestinatariosProveedores);

            //GUARDO LOS EMAILS DE LOS DESTINATARIOS EN UN ARRAY
            while ($rowDestinatarioProveedores = $bd->SigReg($resultDestinatariosProveedores)):
                $arrCorreos[] = $rowDestinatarioProveedores->EMAIL;
            endwhile;
        elseif ($rowAviso->TIPO_OBJETO == "PROVEEDOR"):
            $sqlDestinatariosProveedores    = " SELECT P.EMAIL
                                                 FROM AVISO_ADMINISTRADOR AA
                                                 INNER JOIN PROVEEDOR P ON P.ID_PROVEEDOR = AA.ID_PROVEEDOR
                                                 WHERE AA.ID_AVISO = $rowAviso->ID_AVISO AND AA.BAJA = 0 ";
            $resultDestinatariosProveedores = $bd->ExecSQL($sqlDestinatariosProveedores);

            //GUARDO LOS EMAILS DE LOS DESTINATARIOS EN UN ARRAY
            while ($rowDestinatarioProveedores = $bd->SigReg($resultDestinatariosProveedores)):
                $arrCorreos[] = $rowDestinatarioProveedores->EMAIL;
            endwhile;
        elseif ($rowAviso->TIPO_OBJETO == "ORDEN_CONTRATACION")://SI ES ORDEN DE CONTRATACION SE BUSCAN EL EMAIL DE CONTRATACION DE PROVEEDORES

            $tarifaContratacion = "";
            //DEPENDIENDO DE SI LA CONTRATACION TIENE TARIFA SE COGEN UNOS CORREOS U OTROS
            $rowOrdenContratacion = $bd->VerReg("ORDEN_CONTRATACION", "ID_ORDEN_CONTRATACION", $rowAviso->ID_OBJETO, "No");
            if ($rowOrdenContratacion->ID_TARIFA != ""):
                $tarifaContratacion = $rowOrdenContratacion->ID_TARIFA;
            endif;
            if (!isset($tarifaContratacion) || $tarifaContratacion == ""):
                $sqlDestinatariosProveedores = " SELECT PE.EMAIL
                                                 FROM AVISO_ADMINISTRADOR AA
                                                 INNER JOIN PROVEEDOR P ON P.ID_PROVEEDOR = AA.ID_PROVEEDOR
                                                 INNER JOIN PROVEEDOR_EMAIL PE ON PE.ID_PROVEEDOR = P.ID_PROVEEDOR
                                                 WHERE AA.ID_AVISO = $rowAviso->ID_AVISO  AND AA.BAJA = 0
                                                 AND PE.BAJA = 0 AND TIPO = 'Contratacion Tarifa'
                                                 AND ID_TARIFA IS NULL ";
            else:
                $sqlDestinatariosProveedores = " SELECT PE.EMAIL
                                                 FROM AVISO_ADMINISTRADOR AA
                                                 INNER JOIN PROVEEDOR P ON P.ID_PROVEEDOR = AA.ID_PROVEEDOR
                                                 INNER JOIN PROVEEDOR_EMAIL PE ON PE.ID_PROVEEDOR = P.ID_PROVEEDOR
                                                 WHERE AA.ID_AVISO = $rowAviso->ID_AVISO  AND AA.BAJA = 0
                                                  AND PE.BAJA = 0 AND TIPO = 'Contratacion Tarifa'
                                                 AND (ID_TARIFA = $tarifaContratacion OR ID_TARIFA IS NULL) AND CONTRATACIONES_SIN_TARIFA = 0 ";
            endif;
//            $sqlDestinatariosProveedores = " SELECT P.EMAIL_CONTRATACION
//                                                 FROM AVISO_ADMINISTRADOR AA
//                                                 INNER JOIN PROVEEDOR P ON P.ID_PROVEEDOR = AA.ID_PROVEEDOR
//                                                 WHERE AA.ID_AVISO = $rowAviso->ID_AVISO AND P.EMAIL_CONTRATACION<>'' AND AA.BAJA = 0";
            $resultDestinatariosProveedores = $bd->ExecSQL($sqlDestinatariosProveedores);

            //GUARDO LOS EMAILS DE LOS DESTINATARIOS EN UN ARRAY
            while ($rowDestinatarioProveedores = $bd->SigReg($resultDestinatariosProveedores)):
                $arrCorreos[] = $rowDestinatarioProveedores->EMAIL;
            endwhile;


            //BUSCAMOS LOS EMAILS DE COMUNICACIONES
            $sqlDestinatariosComunicaciones    = " SELECT OCC.EMAIL
                                                     FROM AVISO_ADMINISTRADOR AA INNER JOIN ORDEN_CONTRATACION_COMUNICACION OCC ON OCC.ID_ORDEN_CONTRATACION_COMUNICACION = AA.ID_ORDEN_CONTRATACION_COMUNICACION
                                                     WHERE AA.ID_AVISO = $rowAviso->ID_AVISO AND OCC.EMAIL<>'' AND AA.BAJA = 0";
            $resultDestinatariosComunicaciones = $bd->ExecSQL($sqlDestinatariosComunicaciones);

            //GUARDO LOS EMAILS DE LOS DESTINATARIOS EN UN ARRAY
            while ($rowDestinatarioComunicaciones = $bd->SigReg($resultDestinatariosComunicaciones)):
                $arrEmailsComunicacion = explode(",", (string)$rowDestinatarioComunicaciones->EMAIL);
                foreach ($arrEmailsComunicacion as $email):
                    $arrCorreos[] = $email;
                endforeach;
            endwhile;

        elseif ($rowAviso->TIPO_OBJETO == "FICHA_SEGURIDAD_MATERIAL")://SI ES ORDEN DE CONTRATACION SE BUSCAN EL EMAIL DE CONTRATACION DE PROVEEDORES

            $sqlDestinatariosProveedores    = " SELECT PE.EMAIL
                                                 FROM AVISO_ADMINISTRADOR AA INNER JOIN PROVEEDOR P ON P.ID_PROVEEDOR = AA.ID_PROVEEDOR
                                                 INNER JOIN PROVEEDOR_EMAIL PE ON P.ID_PROVEEDOR = PE.ID_PROVEEDOR
                                                 WHERE AA.ID_AVISO = $rowAviso->ID_AVISO AND AA.BAJA = 0
                                                 AND PE.BAJA = 0 AND TIPO = 'FdS'";
            $resultDestinatariosProveedores = $bd->ExecSQL($sqlDestinatariosProveedores);

            //GUARDO LOS EMAILS DE LOS DESTINATARIOS EN UN ARRAY
            while ($rowDestinatarioProveedores = $bd->SigReg($resultDestinatariosProveedores)):
                $arrCorreos[] = $rowDestinatarioProveedores->EMAIL;
            endwhile;


            //CORREO PARA COMUNICAR A PLANIFICADOR
            $sqlDestinatariosProveedores    = " SELECT P.EMAIL
                                                 FROM AVISO_ADMINISTRADOR AA
                                                 INNER JOIN PLANIFICADOR P ON P.ID_PLANIFICADOR = AA.ID_PLANIFICADOR
                                                 WHERE AA.ID_AVISO = $rowAviso->ID_AVISO AND P.EMAIL<>'' AND AA.BAJA = 0";
            $resultDestinatariosProveedores = $bd->ExecSQL($sqlDestinatariosProveedores);

            //GUARDO LOS EMAILS DE LOS DESTINATARIOS EN UN ARRAY
            while ($rowDestinatarioProveedores = $bd->SigReg($resultDestinatariosProveedores)):
                $arrCorreos[] = $rowDestinatarioProveedores->EMAIL;
            endwhile;

        elseif ($rowAviso->TIPO_OBJETO == "INFORME_REPARACION")://SI ES ORDEN DE CONTRATACION SE BUSCAN EL EMAIL DE CONTRATACION DE PROVEEDORES

            $sqlDestinatariosProveedores    = " SELECT PE.EMAIL
                                                 FROM AVISO_ADMINISTRADOR AA INNER JOIN PROVEEDOR P ON P.ID_PROVEEDOR = AA.ID_PROVEEDOR
                                                 INNER JOIN PROVEEDOR_EMAIL PE ON P.ID_PROVEEDOR = PE.ID_PROVEEDOR
                                                 WHERE AA.ID_AVISO = $rowAviso->ID_AVISO AND AA.BAJA = 0
                                                 AND PE.BAJA = 0 AND TIPO = 'Informe reparacion'";
            $resultDestinatariosProveedores = $bd->ExecSQL($sqlDestinatariosProveedores);

            //GUARDO LOS EMAILS DE LOS DESTINATARIOS EN UN ARRAY
            while ($rowDestinatarioProveedores = $bd->SigReg($resultDestinatariosProveedores)):
                $arrCorreos[] = $rowDestinatarioProveedores->EMAIL;
            endwhile;


            //CORREO PARA COMUNICAR A PLANIFICADOR
            $sqlDestinatariosProveedores    = " SELECT P.EMAIL
                                                 FROM AVISO_ADMINISTRADOR AA
                                                 INNER JOIN PLANIFICADOR P ON P.ID_PLANIFICADOR = AA.ID_PLANIFICADOR
                                                 WHERE AA.ID_AVISO = $rowAviso->ID_AVISO AND P.EMAIL<>'' AND AA.BAJA = 0";
            $resultDestinatariosProveedores = $bd->ExecSQL($sqlDestinatariosProveedores);

            //GUARDO LOS EMAILS DE LOS DESTINATARIOS EN UN ARRAY
            while ($rowDestinatarioProveedores = $bd->SigReg($resultDestinatariosProveedores)):
                $arrCorreos[] = $rowDestinatarioProveedores->EMAIL;
            endwhile;

        elseif ($rowAviso->TIPO_OBJETO == "SOLICITUD_TRANSPORTE")://SI ES ORDEN DE CONTRATACION SE BUSCAN EL EMAIL DE CONTRATACION DE PROVEEDORES

            $sqlDestinatariosProveedores    = " SELECT PE.EMAIL
                                                 FROM AVISO_ADMINISTRADOR AA
                                                 INNER JOIN PROVEEDOR P ON P.ID_PROVEEDOR = AA.ID_PROVEEDOR
                                                 INNER JOIN PROVEEDOR_EMAIL PE ON PE.ID_PROVEEDOR = P.ID_PROVEEDOR
                                                 WHERE AA.ID_AVISO = $rowAviso->ID_AVISO AND AA.BAJA = 0
                                                 AND PE.BAJA = 0 AND TIPO = 'Solicitud Transporte Recogida Proveedor'";
            $resultDestinatariosProveedores = $bd->ExecSQL($sqlDestinatariosProveedores);
//            $sqlDestinatariosProveedores = " SELECT P.EMAIL
//                                                 FROM AVISO_ADMINISTRADOR AA
//                                                 INNER JOIN PROVEEDOR P ON P.ID_PROVEEDOR = AA.ID_PROVEEDOR
//                                                 WHERE AA.ID_AVISO = $rowAviso->ID_AVISO AND P.EMAIL<>'' AND AA.BAJA = 0";
//            $resultDestinatariosProveedores = $bd->ExecSQL($sqlDestinatariosProveedores);

            //GUARDO LOS EMAILS DE LOS DESTINATARIOS EN UN ARRAY
            while ($rowDestinatarioProveedores = $bd->SigReg($resultDestinatariosProveedores)):
                $arrCorreos[] = $rowDestinatarioProveedores->EMAIL;
            endwhile;

            //BUSCAMOS LOS EMAILS DE LOS CONTACTOS EN NOMBRE DE
            $sqlDestinatariosSolicitantes    = " SELECT ST.EMAIL_SOLICITANTE AS EMAIL
                                                     FROM AVISO_ADMINISTRADOR AA INNER JOIN SOLICITUD_TRANSPORTE ST ON ST.ID_SOLICITUD_TRANSPORTE = AA.ID_SOLICITUD_TRANSPORTE
                                                     WHERE AA.ID_AVISO = $rowAviso->ID_AVISO AND ST.EMAIL_SOLICITANTE<>'' AND AA.BAJA = 0";
            $resultDestinatariosSolicitantes = $bd->ExecSQL($sqlDestinatariosSolicitantes);

            //GUARDO LOS EMAILS DE LOS DESTINATARIOS EN UN ARRAY
            while ($rowDestinatarioSolicitantes = $bd->SigReg($resultDestinatariosSolicitantes)):
                $arrCorreos[] = $rowDestinatarioSolicitantes->EMAIL;
            endwhile;

        elseif ($rowAviso->TIPO_OBJETO == "CENTRO_FISICO_INFORME_SGA"):

            //BUSCAMOS LOS EMAILS DE COMUNICACIONES
            $sqlDestinatariosComunicaciones    = " SELECT CFC.EMAIL
                                                     FROM AVISO_ADMINISTRADOR AA
                                                     INNER JOIN CENTRO_FISICO_INFORME_SGA_COMUNICACION CFC ON CFC.ID_CENTRO_FISICO_INFORME_SGA_COMUNICACION = AA.ID_CENTRO_FISICO_INFORME_SGA_COMUNICACION
                                                     WHERE AA.ID_AVISO = $rowAviso->ID_AVISO AND CFC.EMAIL<>'' AND AA.BAJA = 0";
            $resultDestinatariosComunicaciones = $bd->ExecSQL($sqlDestinatariosComunicaciones);

            //GUARDO LOS EMAILS DE LOS DESTINATARIOS EN UN ARRAY
            while ($rowDestinatarioComunicaciones = $bd->SigReg($resultDestinatariosComunicaciones)):
                $arrEmailsComunicacion = explode(",", (string)$rowDestinatarioComunicaciones->EMAIL);
                foreach ($arrEmailsComunicacion as $email):
                    $arrCorreos[] = $email;
                endforeach;
            endwhile;

        elseif ($rowAviso->TIPO_OBJETO == "INFORME_SGA"):

            //BUSCAMOS LOS EMAILS DE COMUNICACIONES
            $sqlDestinatariosComunicaciones    = " SELECT CFC.EMAIL
                                                     FROM AVISO_ADMINISTRADOR AA
                                                     INNER JOIN INFORME_SGA_COMUNICACION CFC ON CFC.ID_INFORME_SGA_COMUNICACION = AA.ID_INFORME_SGA_COMUNICACION
                                                     WHERE AA.ID_AVISO = $rowAviso->ID_AVISO AND CFC.EMAIL<>'' AND AA.BAJA = 0";
            $resultDestinatariosComunicaciones = $bd->ExecSQL($sqlDestinatariosComunicaciones);

            //GUARDO LOS EMAILS DE LOS DESTINATARIOS EN UN ARRAY
            while ($rowDestinatarioComunicaciones = $bd->SigReg($resultDestinatariosComunicaciones)):
                $arrEmailsComunicacion = explode(",", (string)$rowDestinatarioComunicaciones->EMAIL);
                foreach ($arrEmailsComunicacion as $email):
                    $arrCorreos[] = $email;
                endforeach;
            endwhile;
        endif;

        //BUSCAMOS LOS DESTINATARIOS ADMINISTRADORES
        $sqlDestinatariosAdmin    = " SELECT A.EMAIL
                                     FROM AVISO_ADMINISTRADOR AA INNER JOIN ADMINISTRADOR A ON A.ID_ADMINISTRADOR=AA.ID_ADMINISTRADOR
                                     WHERE AA.ID_AVISO = $rowAviso->ID_AVISO AND A.EMAIL<>'' AND AA.BAJA = 0";
        $resultDestinatariosAdmin = $bd->ExecSQL($sqlDestinatariosAdmin);

        //GUARDO LOS EMAILS DE LOS DESTINATARIOS EN UN ARRAY
        while ($rowDestinatarioAdmin = $bd->SigReg($resultDestinatariosAdmin)):
            $arrCorreos[] = $rowDestinatarioAdmin->EMAIL;
        endwhile;

        //UNIFICAMOS
        $arrCorreos          = array_unique((array) $arrCorreos);
        $emailUsuariosReales = implode(',', (array) $arrCorreos);


        //SI EL CORREO PERTENECE A LA MATRIZ DE CORREOS, BUSCAMOS SI TIENE DESTINATARIOS FIJOS
        if ($rowAviso->ID_CORREO != ""):
            //AÑADIMOS LOS EMAILS
            $emailUsuariosReales .= $auxiliar->correosMatrizPoridCorreo($rowAviso->ID_CORREO);
        endif;

        $correos = '';
        if ($enviarUsuarioReales) {
            $correos = $emailUsuariosReales;
        }
        $auxiliar->enviarCorreoSistema($Asunto, $Cuerpo, $fromEmail, $fromNombre, $correos, $usuariostest, ($prioridadAlta == 1) ? 1 : 0, true, $arrayAdjuntos, $arrayFotos);

        //MARCAMOS EL AVISO COMO ENVIADO
        $sqlUpdate = " UPDATE AVISO SET
                           ENVIADO = 1
                          , EMAILS_DESTINATARIOS = '$emailUsuariosReales'
                          ,FECHA_ULTIMO_ENVIO = '" . date("Y-m-d H:i:s") . "'
                          WHERE ID_AVISO = $rowAviso->ID_AVISO";
        $bd->ExecSQL($sqlUpdate);

    }

    //ENVIAR CORREO DE PETICION DE FICHAS DE SEGURIDAD

    /**
     * ENVIO AVISO A PROVEEDOR PARA PETICION DE FICHA DE SEGURIDAD
     *
     */
    function envioAvisoFichaSeguridadMaterial($idProveedor, $idMaterial, $idFichaSeguridad, $idIdiomaPais, $rechazada = "No", $txObservacionesQSE = "")
    {
        global $auxiliar, $administrador, $bd;
        global $url_web;

        //PROVEEDOR
        $rowProveedor = $bd->VerReg("PROVEEDOR", "ID_PROVEEDOR", $idProveedor, "No");
        //MATERIAL
        $rowMaterial = $bd->VerReg("MATERIAL", "ID_MATERIAL", $idMaterial, "No");

        //IDIOMA PAIS
        $rowIdiomaPais = $bd->VerReg("IDIOMA", "ID_IDIOMA", $idIdiomaPais, "No");
        //PAIS PROVEEDOR
        $rowPaisProveedor = $bd->VerReg("PAIS", "ID_PAIS", $rowProveedor->ID_PAIS, "No");
        //FICHA SEGURIDAD
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $NotificaErrorPorEmail            = "No";
        $rowFichaSeguridadIdioma          = $bd->VerRegRest("INCIDENCIA_FICHA_SEGURIDAD_MATERIAL_IDIOMA", "ID_PROVEEDOR = $rowProveedor->ID_PROVEEDOR AND ID_MATERIAL = $rowMaterial->ID_MATERIAL AND ID_IDIOMA = $rowIdiomaPais->ID_IDIOMA ", "No");

        $this->envioAvisoFichaSeguridadMaterialPorId($rowFichaSeguridadIdioma->ID_INCIDENCIA_FICHA_SEGURIDAD_MATERIAL_IDIOMA, $rechazada, $txObservacionesQSE);

    }

    /**
     * CUERPO EMAIL HTML DE MATERIAL PARA QUE SE PIDE FICHA DE SEGURIDAD
     */
    function obtenerCuerpoHTMLMaterial($rowMaterial, $rowMaterialProveedor, $rowIdiomaFicha, $siglasIdioma, $numPedido, $posicionPedido)
    {
        global $auxiliar;
        $cuerpo = "";
        //CREAMOS LOS DATOS DEL CUERPO
        $cuerpo .= "<span style='font-family: Calibri, Arial; font-weight: bold; font-size: 14px;'><u>" . $auxiliar->traduce("Ref. Acciona Material", $siglasIdioma) . "</u> :" . $rowMaterial->REFERENCIA_SGA . " - " . ($siglasIdioma == "ESP" ? $rowMaterial->DESCRIPCION : $rowMaterial->DESCRIPCION_EN) . "</span><br>"
            . "<span style='font-family: Calibri, Arial; font-weight: bold; font-size: 14px;'><u>" . $auxiliar->traduce("Ref. Proveedor Material", $siglasIdioma) . " </u> :" . $rowMaterialProveedor->REF_MATERIAL_PROVEEDOR . "</span><br>"
            . "<span style='font-family: Calibri, Arial; font-weight: bold; font-size: 14px;'><u>" . $auxiliar->traduce("Idioma Ficha", $siglasIdioma) . " </u> :" . $rowIdiomaFicha->{"IDIOMA_" . $siglasIdioma} . "</span><br>"
            . "<span style='font-family: Calibri, Arial; font-weight: bold; font-size: 14px;'><u>" . $auxiliar->traduce("Ultimo pedido", $siglasIdioma) . "</u> : " . $numPedido . "</span><br>"
            . "<span style='font-family: Calibri, Arial; font-weight: bold; font-size: 14px;'><u>" . $auxiliar->traduce("Nº Linea", $siglasIdioma) . "</u> : " . $posicionPedido . "</span><br>";

        return $cuerpo;
    }

    /**
     * ENVIO DE AVISO A PARTIR DEL ID DE LA INCIDENCIA DE FICHA DE SEGURIDAD
     */
    function envioAvisoFichaSeguridadMaterialPorId($idIncidenciaFichaSeguridadMaterial, $rechazada = "No", $txObservacionesQSE = "")
    {
        global $auxiliar, $administrador, $bd;
        global $url_web;

        //PROVEEDOR
        $rowIFS = $bd->VerReg("INCIDENCIA_FICHA_SEGURIDAD_MATERIAL_IDIOMA", "ID_INCIDENCIA_FICHA_SEGURIDAD_MATERIAL_IDIOMA", $idIncidenciaFichaSeguridadMaterial, "No");
        //PROVEEDOR
        $rowProveedor = $bd->VerReg("PROVEEDOR", "ID_PROVEEDOR", $rowIFS->ID_PROVEEDOR, "No");
        //MATERIAL
        $rowMaterial = $bd->VerReg("MATERIAL", "ID_MATERIAL", $rowIFS->ID_MATERIAL, "No");
        //MATERIAL PROVEEDOR
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $NotificaErrorPorEmail            = "No";
        $rowMaterialProveedor             = $bd->VerRegRest("MATERIAL_PROVEEDOR", "ID_MATERIAL = $rowMaterial->ID_MATERIAL AND ID_PROVEEDOR = $rowProveedor->ID_PROVEEDOR", "No");
        //IDIOMA PAIS
        $rowIdiomaPais = $bd->VerReg("IDIOMA", "ID_IDIOMA", $rowIFS->ID_IDIOMA, "No");
        //PAIS PROVEEDOR
        $rowPaisProveedor = $bd->VerReg("PAIS", "ID_PAIS", $rowProveedor->ID_PAIS, "No");
        //IDIOMA PROVEEDOR
        $rowIdiomaProveedor = $bd->VerReg("IDIOMA", "ID_IDIOMA", $rowPaisProveedor->ID_IDIOMA_PRINCIPAL, "No");


        $asunto = $auxiliar->traduce("SE REQUIERE FICHA DE SEGURIDAD", $rowIdiomaProveedor->SIGLAS);

        if ($rechazada == "Si"):
            $asunto = $auxiliar->traduce("FICHA SEGURIDAD RECHAZADA", $rowIdiomaProveedor->SIGLAS) . ". " . $auxiliar->traduce("MATERIAL", $rowIdiomaProveedor->SIGLAS) . ":  $rowMaterial->REFERENCIA";
            $cuerpo = "<p style='font-family: Calibri, Arial; font-weight: bold; font-size: 14px;'>" . $auxiliar->traduce("SE REQUIERE NUEVA FICHA DE SEGURIDAD PARA EL MATERIAL. MOTIVO: FICHA RECHAZADA", $rowIdiomaProveedor->SIGLAS) . ": </p>";
            $cuerpo .= "<p style='font-family: Calibri, Arial; font-weight: bold; font-size: 14px;'>" . $auxiliar->traduce("Ficha rechazada por el motivo:", $rowIdiomaProveedor->SIGLAS) . " $txObservacionesQSE</p>";
        else:
            $cuerpo = "<p style='font-family: Calibri, Arial; font-weight: bold; font-size: 14px;'>" . $auxiliar->traduce("Estimado proveedor", $rowIdiomaProveedor->SIGLAS) . " </p>";
            $cuerpo = "<p style='font-family: Calibri, Arial; font-weight: bold; font-size: 14px;'>" . $auxiliar->traduce("Adjuntamos relación de fichas de seguridad pendientes de ser entregadas. Procede a adjuntarlas a través de los links siguientes", $rowIdiomaProveedor->SIGLAS) . ": </p>";
        endif;
        //OBTENER PEDIDO Y POSICION DE PEDIDO
        $sqlPedido                        = "SELECT PE.PEDIDO_SAP,PEL.LINEA_PEDIDO_SAP FROM PEDIDO_ENTRADA PE
                                        INNER JOIN PEDIDO_ENTRADA_LINEA PEL ON PEL.ID_PEDIDO_ENTRADA = PE.ID_PEDIDO_ENTRADA
                                        WHERE (TIPO_PEDIDO = 'Compra' OR TIPO_PEDIDO = 'Compra SGA Manual') AND FECHA_CREACION =
                                                ( SELECT MAX(FECHA_CREACION) FROM PEDIDO_ENTRADA PE2
                                                    INNER JOIN PEDIDO_ENTRADA_LINEA PEL2   ON PEL2.ID_PEDIDO_ENTRADA = PE2.ID_PEDIDO_ENTRADA
                                                    WHERE PEL2.ID_MATERIAL =" . $rowMaterial->ID_MATERIAL . "
                                                )
                                  AND PE.ID_PROVEEDOR =" . $rowProveedor->ID_PROVEEDOR . "
                                        AND PEL.ID_MATERIAL = " . $rowMaterial->ID_MATERIAL;
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $resultPedido                     = $bd->ExecSQL($sqlPedido, "No");
        $numPedido                        = "";
        $posicionPedido                   = "";

        if ($bd->NumRegs($resultPedido) > 0):
            $rowPedido      = $bd->SigReg($resultPedido);
            $numPedido      = $rowPedido->PEDIDO_SAP;
            $posicionPedido = $rowPedido->LINEA_PEDIDO_SAP;
        endif;
        //CREAMOS LOS DATOS DEL CUERPO
        $cuerpo .= $this->obtenerCuerpoHTMLMaterial($rowMaterial, $rowMaterialProveedor, $rowIdiomaPais, $rowIdiomaProveedor->SIGLAS, $numPedido, $posicionPedido);
        //        $cuerpo .= "<span style='font-family: Calibri, Arial; font-weight: bold; font-size: 14px;'><u>" . $auxiliar->traduce("Ref. Acciona Material", $rowIdiomaProveedor->SIGLAS) . "</u> :" . $rowMaterial->REFERENCIA_SGA . " - " . ($rowIdiomaProveedor->SIGLAS == "ESP" ? $rowMaterial->DESCRIPCION : $rowMaterial->DESCRIPCION_EN) . "</span><br>"
        //            . "<span style='font-family: Calibri, Arial; font-weight: bold; font-size: 14px;'><u>" . $auxiliar->traduce("Ref. Proveedor Material", $rowIdiomaProveedor->SIGLAS) . " </u> :" . $rowMaterialProveedor->REF_MATERIAL_PROVEEDOR . "</span><br>"
        //            . "<span style='font-family: Calibri, Arial; font-weight: bold; font-size: 14px;'><u>" . $auxiliar->traduce("Idioma Ficha", $rowIdiomaProveedor->SIGLAS) . " </u> :" . $rowIdiomaPais->{"IDIOMA_" . $rowIdiomaProveedor->SIGLAS} . "</span><br>"
        //            . "<span style='font-family: Calibri, Arial; font-weight: bold; font-size: 14px;'><u>" . $auxiliar->traduce("Ultimo pedido", $rowIdiomaProveedor->SIGLAS) . "</u> : " . $numPedido . "</span><br>"
        //            . "<span style='font-family: Calibri, Arial; font-weight: bold; font-size: 14px;'><u>" . $auxiliar->traduce("Nº Linea", $rowIdiomaProveedor->SIGLAS) . "</u> : " . $posicionPedido . "</span><br>";


        $cuerpo .= "<br><br><a href='"
            . trim( (string)$url_web . "maestros/fichas_seguridad_idioma/ficha_proveedor.php?idFichaSeguridad=" . $rowIFS->ID_INCIDENCIA_FICHA_SEGURIDAD_MATERIAL_IDIOMA . "&key=" . $rowIFS->KEY_CORREO) . "'>" . $auxiliar->traduce("Pulse aqui para adjuntar ficha", $rowIdiomaProveedor->SIGLAS) . "</a>";

        //DECLARAMOS EL ARRAY DE DESTINATARIO
        $arrDest                = array();
        $arrDest['PROVEEDOR'][] = $rowProveedor->ID_PROVEEDOR;

        //CREAMOS EL AVISO
        $idAviso = $this->GuardarAviso($rowIFS->ID_INCIDENCIA_FICHA_SEGURIDAD_MATERIAL_IDIOMA, "FICHA_SEGURIDAD_MATERIAL", $asunto, $cuerpo, $arrDest);

        //ENVIAMOS EL AVISOS
        if ($idAviso != ""):
            $this->enviarAviso($idAviso);
        endif;
    }


    /**
     * ENVIO DE AVISO A PARTIR DEL ID DE LA INCIDENCIA DE FICHA DE SEGURIDAD
     */
    function envioAvisoInformeReparacionPorId($idMovimientoSalidaLinea, $txObservacionesQSE = "")
    {
        global $auxiliar, $administrador, $bd;
        global $url_web;

        //MOVIMIENTO SALIDA LINEA
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowMovimientoSalidaLinea         = $bd->VerReg("MOVIMIENTO_SALIDA_LINEA", "ID_MOVIMIENTO_SALIDA_LINEA", $idMovimientoSalidaLinea, "No");

        //MOVIMIENTO SALIDA
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowMovimientoSalida              = $bd->VerReg("MOVIMIENTO_SALIDA", "ID_MOVIMIENTO_SALIDA", $rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA, "No");

        //PROVEEDOR
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowProveedor                     = $bd->VerReg("PROVEEDOR", "ID_PROVEEDOR", $rowMovimientoSalida->ID_PROVEEDOR, "No");

        //PAIS PROVEEDOR
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowPaisProveedor                 = $bd->VerReg("PAIS", "ID_PAIS", $rowProveedor->ID_PAIS, "No");

        //IDIOMA PROVEEDOR
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $rowIdiomaProveedor               = $bd->VerReg("IDIOMA", "ID_IDIOMA", $rowPaisProveedor->ID_IDIOMA_PRINCIPAL, "No");


        //ALBARÁN
        $albaranMostrar = (($rowMovimientoSalidaLinea->ID_ALBARAN == "" || $rowMovimientoSalidaLinea->ID_ALBARAN == NULL) ? "I" . $rowMovimientoSalida->ID_MOVIMIENTO_SALIDA : $rowMovimientoSalidaLinea->ID_ALBARAN);

        //POSICIÓN ALBARÁN
        $posicionAlbaran = "";
        if ($rowMovimientoSalidaLinea->ID_ALBARAN_LINEA != ""):
            $GLOBALS["NotificaErrorPorEmail"] = "No";
            $rowAlbaranLinea                  = $bd->VerReg("ALBARAN_LINEA", "ID_ALBARAN_LINEA", $rowMovimientoSalidaLinea->ID_ALBARAN_LINEA, "No");

            $posicionAlbaran = (int)$rowAlbaranLinea->NUMERO_LINEA;
        endif;
        //$posicionAlbaran = (int)$rowMovimientoSalidaLinea->LINEA_MOVIMIENTO_SAP;


        $asunto = $auxiliar->traduce("SE REQUIERE INFORME DE REPARACION", $rowIdiomaProveedor->SIGLAS);

        $cuerpo = "<p style='font-family: Calibri, Arial; font-weight: bold; font-size: 14px;'>" . $auxiliar->traduce("Estimado proveedor", $rowIdiomaProveedor->SIGLAS) . " </p>";
        $cuerpo .= "<p style='font-family: Calibri, Arial; font-weight: bold; font-size: 14px;'>" . $auxiliar->traduce("Se solicita proceso del informe de reparacion con albaran", $rowIdiomaProveedor->SIGLAS) . " <strong>" . $albaranMostrar . "</strong>" . " " . $auxiliar->traduce("y", $rowIdiomaProveedor->SIGLAS) . " " . $auxiliar->traduce("Posicion Albaran", $rowIdiomaProveedor->SIGLAS) . " " . "<strong>" . $posicionAlbaran . "</strong>" . ".</p>";


        $cuerpo .= "<br><br><a href='"
            . trim( (string)$url_web . "informes/informe_reparacion/index.php?tipoPantalla=Proveedor") . "'>" . $auxiliar->traduce("Pulse aqui para rellenar los datos", $rowIdiomaProveedor->SIGLAS) . "</a>";

        //DECLARAMOS EL ARRAY DE DESTINATARIO
        $arrDest                = array();
        $arrDest['PROVEEDOR'][] = $rowProveedor->ID_PROVEEDOR;

        //CREAMOS EL AVISO
        $idAviso = $this->GuardarAviso($rowMovimientoSalidaLinea->ID_MOVIMIENTO_SALIDA_LINEA, "INFORME_REPARACION", $asunto, $cuerpo, $arrDest);

        //ENVIAMOS EL AVISOS
        if ($idAviso != ""):
            $this->enviarAviso($idAviso);
        endif;
    }


    /**
     * ENVIO DE AVISO A FICHAS DE SEGURIDAD A PARTIR DE PROVEEDOR
     */
    function envioAvisoFichasSeguridadMaterialProveedor($idProveedor)
    {
        global $auxiliar, $administrador, $bd;
        global $url_web;
        global $observaciones_sistema;

        //PROVEEDOR
        $rowProveedor = $bd->VerReg("PROVEEDOR", "ID_PROVEEDOR", $idProveedor, "No");
        //PAIS PROVEEDOR
        $rowPaisProveedor = $bd->VerReg("PAIS", "ID_PAIS", $rowProveedor->ID_PAIS, "No");
        //IDIOMA PROVEEDOR
        $rowIdiomaProveedor = $bd->VerReg("IDIOMA", "ID_IDIOMA", $rowPaisProveedor->ID_IDIOMA_PRINCIPAL, "No");


        $asunto = $auxiliar->traduce("SE REQUIERE FICHA DE SEGURIDAD", $rowIdiomaProveedor->SIGLAS);
        $cuerpo = "<p style='font-family: Calibri, Arial; font-weight: bold; font-size: 14px;'>" . $auxiliar->traduce("Estimado proveedor", $rowIdiomaProveedor->SIGLAS) . " </p>";
        $cuerpo = "<p style='font-family: Calibri, Arial; font-weight: bold; font-size: 14px;'>" . $auxiliar->traduce("Adjuntamos relación de fichas de seguridad pendientes de ser entregadas. Procede a adjuntarlas a través de los links siguientes", $rowIdiomaProveedor->SIGLAS) . ": </p>";

        //ARRAY DE INCIDENCIAS REVISADAS
        $arrIncidenciasFdSAReclamar = array();

        //ENVIO TODAS LAS FICHAS DE SEGURIDAD PENDIENTES ;
        $sqlIFSComunicarProveedor    = "SELECT IFS.*
                                  FROM INCIDENCIA_FICHA_SEGURIDAD_MATERIAL_IDIOMA IFS
                                WHERE IFS.ID_PROVEEDOR = $rowProveedor->ID_PROVEEDOR AND IFS.BAJA = 0 AND IFS.ESTADO_INCIDENCIA = 'No Resuelta'
                                  AND IFS.ANULADO_MANUALMENTE = 0 AND  ESTADO_COMUNICACION_PROVEEDOR = 'Pdte. Comunicar a Proveedor' ";
        $resultIFSComunicarProveedor = $bd->ExecSQL($sqlIFSComunicarProveedor);
        while ($rowIFSComunicarProveedor = $bd->SigReg($resultIFSComunicarProveedor)):
            //INICIO LA TRANSACCION
            $bd->begin_transaction();
            //FICHA SEGURIDAD
            $rowFichaSeguridad = $bd->VerReg("INCIDENCIA_FICHA_SEGURIDAD_MATERIAL_IDIOMA", "ID_INCIDENCIA_FICHA_SEGURIDAD_MATERIAL_IDIOMA", $rowIFSComunicarProveedor->ID_INCIDENCIA_FICHA_SEGURIDAD_MATERIAL_IDIOMA);

            //MATERIAL
            $rowMaterial = $bd->VerReg("MATERIAL", "ID_MATERIAL", $rowFichaSeguridad->ID_MATERIAL, "No");
            //IDIOMA
            $rowIdiomaPais = $bd->VerReg("IDIOMA", "ID_IDIOMA", $rowFichaSeguridad->ID_IDIOMA, "No");

            //GENERAR KEY
            $keyCorreo = $auxiliar->generarKey();

            $sql       = "UPDATE INCIDENCIA_FICHA_SEGURIDAD_MATERIAL_IDIOMA SET
                ESTADO_COMUNICACION_PROVEEDOR ='Comunicada a Proveedor-Pdte. Respuesta'
                , FECHA_COMUNICACION_PROVEEDOR = '" . date("Y-m-d H:i:s") . "'
                , FECHA_MODIFICACION = '" . date("Y-m-d H:i:s") . "'
                , KEY_CORREO = '$keyCorreo'
                , ID_ADMINISTRADOR_MODIFICACION = $administrador->ID_ADMINISTRADOR
                WHERE ID_INCIDENCIA_FICHA_SEGURIDAD_MATERIAL_IDIOMA = $rowFichaSeguridad->ID_INCIDENCIA_FICHA_SEGURIDAD_MATERIAL_IDIOMA";
            $TipoError = "ErrorEjecutarSql";
            $bd->ExecSQL($sql);
            //FICHA SEGURIDAD ACTULIZADA
            $rowFichaSeguridadActualizada = $bd->VerReg("INCIDENCIA_FICHA_SEGURIDAD_MATERIAL_IDIOMA", "ID_INCIDENCIA_FICHA_SEGURIDAD_MATERIAL_IDIOMA", $rowFichaSeguridad->ID_INCIDENCIA_FICHA_SEGURIDAD_MATERIAL_IDIOMA, "No");

            //OBTENER PEDIDO Y POSICION DE PEDIDO
            $sqlPedido                        = "SELECT PE.PEDIDO_SAP,PEL.LINEA_PEDIDO_SAP FROM PEDIDO_ENTRADA PE
                                        INNER JOIN PEDIDO_ENTRADA_LINEA PEL ON PEL.ID_PEDIDO_ENTRADA = PE.ID_PEDIDO_ENTRADA
                                        WHERE (TIPO_PEDIDO = 'Compra' OR TIPO_PEDIDO = 'Compra SGA Manual') AND FECHA_CREACION =
                                                ( SELECT MAX(FECHA_CREACION) FROM PEDIDO_ENTRADA PE2
                                                    INNER JOIN PEDIDO_ENTRADA_LINEA PEL2   ON PEL2.ID_PEDIDO_ENTRADA = PE2.ID_PEDIDO_ENTRADA
                                                    WHERE PEL2.ID_MATERIAL =" . $rowMaterial->ID_MATERIAL . "
                                                )
                                  AND PE.ID_PROVEEDOR =" . $rowProveedor->ID_PROVEEDOR . "
                                        AND PEL.ID_MATERIAL = " . $rowMaterial->ID_MATERIAL;
            $GLOBALS["NotificaErrorPorEmail"] = "No";
            $resultPedido                     = $bd->ExecSQL($sqlPedido, "No");
            $numPedido                        = "";
            $posicionPedido                   = "";

            if ($bd->NumRegs($resultPedido) > 0):
                $rowPedido      = $bd->SigReg($resultPedido);
                $numPedido      = $rowPedido->PEDIDO_SAP;
                $posicionPedido = $rowPedido->LINEA_PEDIDO_SAP;
            endif;
            //MATERIAL PROVEEDOR
            $GLOBALS["NotificaErrorPorEmail"] = "No";
            $NotificaErrorPorEmail            = "No";
            $rowMaterialProveedor             = $bd->VerRegRest("MATERIAL_PROVEEDOR", "ID_MATERIAL = $rowMaterial->ID_MATERIAL AND ID_PROVEEDOR = $rowProveedor->ID_PROVEEDOR", "No");

            $cuerpo .= $this->obtenerCuerpoHTMLMaterial($rowMaterial, $rowMaterialProveedor, $rowIdiomaPais, $rowIdiomaProveedor->SIGLAS, $numPedido, $posicionPedido);
            //            $cuerpo .="<span style='font-family: Calibri, Arial; font-weight: bold; font-size: 14px;'><u>" . $auxiliar->traduce("Ref. Acciona Material", $rowIdiomaProveedor->SIGLAS) . "</u> :" . $rowMaterial->REFERENCIA_SGA . " - " . ($rowIdiomaProveedor->SIGLAS == "ESP" ? $rowMaterial->DESCRIPCION : $rowMaterial->DESCRIPCION_EN) . "</span><br>"
            //                . "<span style='font-family: Calibri, Arial; font-weight: bold; font-size: 14px;'><u>" . $auxiliar->traduce("Ref. Proveedor Material", $rowIdiomaProveedor->SIGLAS) . " </u> :" . $rowMaterialProveedor->REF_MATERIAL_PROVEEDOR . "</span><br>"
            //                . "<span style='font-family: Calibri, Arial; font-weight: bold; font-size: 14px;'><u>" . $auxiliar->traduce("Idioma Ficha", $rowIdiomaProveedor->SIGLAS) . " </u> :" . $rowIdiomaPais->{"IDIOMA_" . $rowIdiomaProveedor->SIGLAS} . "</span><br>"
            //                . "<span style='font-family: Calibri, Arial; font-weight: bold; font-size: 14px;'><u>" . $auxiliar->traduce("Ultimo pedido", $rowIdiomaProveedor->SIGLAS) . "</u> : " . $numPedido . "</span><br>"
            //                . "<span style='font-family: Calibri, Arial; font-weight: bold; font-size: 14px;'><u>" . $auxiliar->traduce("Nº Linea", $rowIdiomaProveedor->SIGLAS) . "</u> : " . $posicionPedido . "</span><br>";


            $cuerpo .= "<a href='"
                . trim( (string)$url_web . "maestros/fichas_seguridad_idioma/ficha_proveedor.php?idFichaSeguridad=" . $rowFichaSeguridad->ID_INCIDENCIA_FICHA_SEGURIDAD_MATERIAL_IDIOMA . "&key=" . $rowFichaSeguridadActualizada->KEY_CORREO) . "'>" . $auxiliar->traduce("Pulse aqui para adjuntar ficha", $rowIdiomaProveedor->SIGLAS) . "</a><br><br>";

            //GUARDAMOS TODAS LAS INCIDENCIAS QUE SE VAN A RECLAMAR POR PRIMERA VEZ
            $arrIncidenciasFdSAReclamar[] = $rowIFSComunicarProveedor->ID_INCIDENCIA_FICHA_SEGURIDAD_MATERIAL_IDIOMA;

            //GRABO LAS OBSERVACIONES EN OBSERVACIONES SISTEMA
            $observaciones_sistema->Grabar("INCIDENCIA_FICHA_SEGURIDAD_MATERIAL", $rowIFSComunicarProveedor->ID_INCIDENCIA_FICHA_SEGURIDAD_MATERIAL_IDIOMA, "Reclamar FdS a Proveedor");
            //LOG MOVIMIENTOS
            $administrador->Insertar_Log_Movimientos($administrador->ID_ADMINISTRADOR, "Modificación", "Incidencia Ficha Seguridad Material", $rowIFSComunicarProveedor->ID_INCIDENCIA_FICHA_SEGURIDAD_MATERIAL_IDIOMA, "Reclamar a Proveedor Primera vez (Proceso Automatico)", "INCIDENCIA_FICHA_SEGURIDAD_MATERIAL_IDIOMA", $rowIFSComunicarProveedor, $rowFichaSeguridadActualizada);
        endwhile;
        //DECLARAMOS EL ARRAY DE DESTINATARIO
        $arrDest                = array();
        $arrDest['PROVEEDOR'][] = $rowProveedor->ID_PROVEEDOR;

        //CREAMOS EL AVISO PARA CADA FICHA DE SEGURIDAD QUE HA SIDO RECLAMADA
        //PARA QUE QUEDE VISIBLE EN LA TRAZA DE LA FICHA DE LA INCIDENCIA (FICHA DE CORREOS ENVIADOS)
        foreach ($arrIncidenciasFdSAReclamar as $idFichaSeguridad):
            $idAviso = $this->GuardarAviso($idFichaSeguridad, "FICHA_SEGURIDAD_MATERIAL", $asunto, $cuerpo, $arrDest);
            //MARCAMOS EL AVISO COMO ENVIADO
            $sqlUpdate = " UPDATE AVISO SET
                                   ENVIADO = 1
                                  ,FECHA_ULTIMO_ENVIO = '" . date("Y-m-d H:i:s") . "'
                                  WHERE ID_AVISO = $idAviso";
            $bd->ExecSQL($sqlUpdate);
        endforeach;

        //SOLO ENVIAMOS UN AVISO (SON TODOS IGUALES)
        //ENVIAMOS EL AVISOS
        if ($idAviso != ""):
            $this->enviarAviso($idAviso);
        endif;
    }


    //ENVIAR RECLAMACION FICHA DE SEGURIDAD (CUANDO SE HA ENVIADO LA PETICION Y EL PROVEEDOR NO HA CONTESTADO)

    /**
     * ENVIO DE RECALMACION FICHA DE SEGURIDAD
     */
    function envioReclamacionFichaSeguridadMaterial($idProveedor, $idMaterial, $idFichaSeguridad, $idIdiomaPais, $rechazada = "No", $txObservacionesQSE = "")
    {
        global $auxiliar, $administrador, $bd;
        global $url_web;

        //PROVEEDOR
        $rowProveedor = $bd->VerReg("PROVEEDOR", "ID_PROVEEDOR", $idProveedor, "No");
        //MATERIAL
        $rowMaterial = $bd->VerReg("MATERIAL", "ID_MATERIAL", $idMaterial, "No");
        //MATERIAL PROVEEDOR
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $NotificaErrorPorEmail            = "No";
        $rowMaterialProveedor             = $bd->VerRegRest("MATERIAL_PROVEEDOR", "ID_MATERIAL = $idMaterial AND ID_PROVEEDOR = $idProveedor", "No");
        //IDIOMA PAIS
        $rowIdiomaPais = $bd->VerReg("IDIOMA", "ID_IDIOMA", $idIdiomaPais, "No");
        //PAIS PROVEEDOR
        $rowPaisProveedor = $bd->VerReg("PAIS", "ID_PAIS", $rowProveedor->ID_PAIS, "No");
        //IDIOMA PROVEEDOR
        $rowIdiomaProveedor = $bd->VerReg("IDIOMA", "ID_IDIOMA", $rowPaisProveedor->ID_IDIOMA_PRINCIPAL, "No");
        //FICHA SEGURIDAD
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $NotificaErrorPorEmail            = "No";
        $rowFichaSeguridadIdioma          = $bd->VerRegRest("INCIDENCIA_FICHA_SEGURIDAD_MATERIAL_IDIOMA", "ID_PROVEEDOR = $idProveedor AND ID_MATERIAL = $idMaterial AND ID_IDIOMA = $rowIdiomaPais->ID_IDIOMA ", "No");


        $asunto = $auxiliar->traduce("SE RECLAMA FICHA DE SEGURIDAD", $rowIdiomaProveedor->SIGLAS) . ". " . $auxiliar->traduce("MATERIAL", $rowIdiomaProveedor->SIGLAS) . ":  $rowMaterial->REFERENCIA_SGA";


        $cuerpo = "<p style='font-family: Calibri, Arial; font-weight: bold; font-size: 14px;'>" . $auxiliar->traduce("Estimado proveedor", $rowIdiomaProveedor->SIGLAS) . " </p>";
        $cuerpo = "<p style='font-family: Calibri, Arial; font-weight: bold; font-size: 14px;'>" . $auxiliar->traduce("Adjuntamos relación de fichas de seguridad pendientes de ser entregadas y que ya han sido solicitadas. Procede a subirlas a través de los links siguientes", $rowIdiomaProveedor->SIGLAS) . ": </p>";

        //OBTENER PEDIDO Y POSICION DE PEDIDO
        $sqlPedido                        = "SELECT PE.PEDIDO_SAP,PEL.LINEA_PEDIDO_SAP FROM PEDIDO_ENTRADA PE
                                        INNER JOIN PEDIDO_ENTRADA_LINEA PEL ON PEL.ID_PEDIDO_ENTRADA = PE.ID_PEDIDO_ENTRADA
                                        WHERE (TIPO_PEDIDO = 'Compra' OR TIPO_PEDIDO = 'Compra SGA Manual') AND FECHA_CREACION =
                                                ( SELECT MAX(FECHA_CREACION) FROM PEDIDO_ENTRADA PE2
                                                    INNER JOIN PEDIDO_ENTRADA_LINEA PEL2   ON PEL2.ID_PEDIDO_ENTRADA = PE2.ID_PEDIDO_ENTRADA
                                                    WHERE PEL2.ID_MATERIAL =" . $rowMaterial->ID_MATERIAL . "
                                                )
                                  AND PE.ID_PROVEEDOR =" . $rowProveedor->ID_PROVEEDOR . "
                                        AND PEL.ID_MATERIAL = " . $rowMaterial->ID_MATERIAL;
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $resultPedido                     = $bd->ExecSQL($sqlPedido, "No");
        $numPedido                        = "";
        $posicionPedido                   = "";

        if ($bd->NumRegs($resultPedido) > 0):
            $rowPedido      = $bd->SigReg($resultPedido);
            $numPedido      = $rowPedido->PEDIDO_SAP;
            $posicionPedido = $rowPedido->LINEA_PEDIDO_SAP;
        endif;
        $cuerpo .= $this->obtenerCuerpoHTMLMaterial($rowMaterial, $rowMaterialProveedor, $rowIdiomaPais, $rowIdiomaProveedor->SIGLAS, $numPedido, $posicionPedido);

        $cuerpo .= "<br><br><a href='"
            . $url_web . "maestros/fichas_seguridad_idioma/ficha_proveedor.php?idFichaSeguridad=$idFichaSeguridad&key=$rowFichaSeguridadIdioma->KEY_CORREO'>" . $auxiliar->traduce("Pulse aqui para adjuntar ficha", $rowIdiomaProveedor->SIGLAS) . "</a>";

        //DECLARAMOS EL ARRAY DE DESTINATARIO
        $arrDest                = array();
        $arrDest['PROVEEDOR'][] = $rowProveedor->ID_PROVEEDOR;

        //CREAMOS EL AVISO
        $idAviso = $this->GuardarAviso($idFichaSeguridad, "FICHA_SEGURIDAD_MATERIAL", $asunto, $cuerpo, $arrDest);

        //ENVIAMOS EL AVISOS
        if ($idAviso != ""):
            $this->enviarAviso($idAviso);
        endif;
    }


    /**
     * ENVIA RECLAMACIÓN A PROVEEDOR CON TODAS LAS FICHAS PENDIENTES A RECLAMAR Y CON UN RESUMEN DE LO QUE TIENE YA RECLAMADO Y PENDIENTE
     * @idProveedor int
     * @tiempoReclamarFdSProveedor int
     */
    function envioReclamacionFichaSeguridadMaterialPorProveedor($idProveedor, $tiempoReclamarFdSProveedor = 2)
    {
        global $auxiliar, $administrador, $bd, $rowAdministradorProcesos;
        global $url_web;
        global $observaciones_sistema;

        //ARARY DE INCIDENCIAS RECLAMADAS
        $arrIncidenciasFdSAReclamar = array();

        //PROVEEDOR
        $rowProveedor = $bd->VerReg("PROVEEDOR", "ID_PROVEEDOR", $idProveedor, "No");

        //PAIS PROVEEDOR
        $rowPaisProveedor = $bd->VerReg("PAIS", "ID_PAIS", $rowProveedor->ID_PAIS, "No");

        //IDIOMA PROVEEDOR
        $rowIdiomaProveedor = $bd->VerReg("IDIOMA", "ID_IDIOMA", $rowPaisProveedor->ID_IDIOMA_PRINCIPAL, "No");

        //ASUNTO CORREO
        $asunto = $auxiliar->traduce("SE RECLAMA FICHA DE SEGURIDAD", $rowIdiomaProveedor->SIGLAS);

        // PRIMERO (RECLAMADO PERO PENDIENTE)
        // RECORREMOS LAS FICHAS QUE YA HAN SIDO RECLAMADAS PERO NO HA SIDO ADJUNTADA LA FICHA (PENDIENTE)
        // POR EL PROVEEDOR PARA VOLVERLAS A RECLAMAR
        $sqlFichasSeguridadReclamadasAProveedor    = "SELECT * FROM INCIDENCIA_FICHA_SEGURIDAD_MATERIAL_IDIOMA
                                          WHERE ID_PROVEEDOR = $idProveedor
                                          AND ESTADO_INCIDENCIA = 'No Resuelta'
                                          AND  ESTADO_COMUNICACION_PROVEEDOR = 'Comunicada a Proveedor-Pdte. Respuesta'
                                          AND RECLAMADA = 'Reclamada a Proveedor'
                                          AND ANULADO_MANUALMENTE = 0
                                          AND BAJA = 0 ";
        $resultFichasSeguridadReclamadasAProveedor = $bd->ExecSQL($sqlFichasSeguridadReclamadasAProveedor, "No");

        //EMPIECE DEL CUERPO PENDIENTES
        $cuerpoFichasReclamadas = "";
        while ($rowReclamadaAProveedor = $bd->SigReg($resultFichasSeguridadReclamadasAProveedor)):
            if ($cuerpoFichasReclamadas == ""):
                //EMPIECE DEL CUERPO
                $cuerpoFichasReclamadas .= "<p style='font-family: Calibri, Arial; font-weight: bold; font-size: 14px;'>" . $auxiliar->traduce("Adjuntamos relación de fichas de seguridad pendientes de ser entregadas y que ya han sido solicitadas. Procede a subirlas a través de los links siguientes", $rowIdiomaProveedor->SIGLAS) . ": </p>";

            endif;

            //MATERIAL
            $rowMaterial = $bd->VerReg("MATERIAL", "ID_MATERIAL", $rowReclamadaAProveedor->ID_MATERIAL, "No");

            //MATERIAL PROVEEDOR
            $GLOBALS["NotificaErrorPorEmail"] = "No";
            $rowMaterialProveedor             = $bd->VerRegRest("MATERIAL_PROVEEDOR", "ID_MATERIAL = $rowMaterial->ID_MATERIAL AND ID_PROVEEDOR = $rowProveedor->ID_PROVEEDOR", "No");

            //IDIOMA PAIS
            $rowIdiomaPais = $bd->VerReg("IDIOMA", "ID_IDIOMA", $rowReclamadaAProveedor->ID_IDIOMA, "No");

            //FICHA SEGURIDAD
            $GLOBALS["NotificaErrorPorEmail"] = "No";
            $rowFichaSeguridadIdioma          = $bd->VerRegRest("INCIDENCIA_FICHA_SEGURIDAD_MATERIAL_IDIOMA", "ID_PROVEEDOR = $rowProveedor->ID_PROVEEDOR AND ID_MATERIAL = $rowMaterial->ID_MATERIAL AND ID_IDIOMA = $rowReclamadaAProveedor->ID_IDIOMA ", "No");

            //OBTENER PEDIDO Y POSICION DE PEDIDO
            $sqlPedido                        = "SELECT PE.PEDIDO_SAP,PEL.LINEA_PEDIDO_SAP FROM PEDIDO_ENTRADA PE
                                    INNER JOIN PEDIDO_ENTRADA_LINEA PEL ON PEL.ID_PEDIDO_ENTRADA = PE.ID_PEDIDO_ENTRADA
                                    WHERE (TIPO_PEDIDO = 'Compra' OR TIPO_PEDIDO = 'Compra SGA Manual') AND FECHA_CREACION =
                                            ( SELECT MAX(FECHA_CREACION) FROM PEDIDO_ENTRADA PE2
                                                INNER JOIN PEDIDO_ENTRADA_LINEA PEL2   ON PEL2.ID_PEDIDO_ENTRADA = PE2.ID_PEDIDO_ENTRADA
                                                WHERE PEL2.ID_MATERIAL =" . $rowMaterial->ID_MATERIAL . "
                                            )
				              AND PE.ID_PROVEEDOR =" . $rowProveedor->ID_PROVEEDOR . "
                                    AND PEL.ID_MATERIAL = " . $rowMaterial->ID_MATERIAL;
            $GLOBALS["NotificaErrorPorEmail"] = "No";
            $resultPedido                     = $bd->ExecSQL($sqlPedido, "No");
            $numPedido                        = "";
            $posicionPedido                   = "";

            if ($bd->NumRegs($resultPedido) > 0):
                $rowPedido      = $bd->SigReg($resultPedido);
                $numPedido      = $rowPedido->PEDIDO_SAP;
                $posicionPedido = $rowPedido->LINEA_PEDIDO_SAP;
            endif;

            //CUERPO PARA FICHAS RECLAMADAS
            $cuerpoFichasReclamadas .= $this->obtenerCuerpoHTMLMaterial($rowMaterial, $rowMaterialProveedor, $rowIdiomaPais, $rowIdiomaProveedor->SIGLAS, $numPedido, $posicionPedido);
            //            $cuerpoFichasReclamadas .= "<span style='font-family: Calibri, Arial; font-weight: bold; font-size: 14px;'><u>" . $auxiliar->traduce("Ref. Acciona Material", $rowIdiomaProveedor->SIGLAS) . "</u> :" . $rowMaterial->REFERENCIA_SGA . " - " . ($rowIdiomaProveedor->SIGLAS == "ESP" ? $rowMaterial->DESCRIPCION : $rowMaterial->DESCRIPCION_EN) . "</span><br>"
            //                . "<span style='font-family: Calibri, Arial; font-weight: bold; font-size: 14px;'><u>" . $auxiliar->traduce("Ref. Proveedor Material", $rowIdiomaProveedor->SIGLAS) . " </u> :" . $rowMaterialProveedor->REF_MATERIAL_PROVEEDOR . "</span><br>"
            //                . "<span style='font-family: Calibri, Arial; font-weight: bold; font-size: 14px;'><u>" . $auxiliar->traduce("Idioma Ficha", $rowIdiomaProveedor->SIGLAS) . " </u> :" . $rowIdiomaPais->{"IDIOMA_" . $rowIdiomaProveedor->SIGLAS} . "</span><br>"
            //                . "<span style='font-family: Calibri, Arial; font-weight: bold; font-size: 14px;'><u>" . $auxiliar->traduce("Ultimo pedido", $rowIdiomaProveedor->SIGLAS) . "</u> : " . $numPedido . "</span><br>"
            //                . "<span style='font-family: Calibri, Arial; font-weight: bold; font-size: 14px;'><u>" . $auxiliar->traduce("Nº Linea", $rowIdiomaProveedor->SIGLAS) . "</u> : " . $posicionPedido . "</span><br>";
            $cuerpoFichasReclamadas .= "<a href='"
                . $url_web . "maestros/fichas_seguridad_idioma/ficha_proveedor.php?idFichaSeguridad=$rowReclamadaAProveedor->ID_INCIDENCIA_FICHA_SEGURIDAD_MATERIAL_IDIOMA&key=$rowFichaSeguridadIdioma->KEY_CORREO'>" . $auxiliar->traduce("Pulse aqui para adjuntar ficha", $rowIdiomaProveedor->SIGLAS) . "</a><br><br>";

            //GUARDAMOS TODAS LAS INCIDENCIAS QUE SE VAN A RECLAMAR POR PRIMERA VEZ
            $arrIncidenciasFdSAReclamar[] = $rowReclamadaAProveedor->ID_INCIDENCIA_FICHA_SEGURIDAD_MATERIAL_IDIOMA;

            //GRABO LAS OBSERVACIONES EN OBSERVACIONES SISTEMA
            $observaciones_sistema->Grabar("INCIDENCIA_FICHA_SEGURIDAD_MATERIAL", $rowReclamadaAProveedor->ID_INCIDENCIA_FICHA_SEGURIDAD_MATERIAL_IDIOMA, "Reclamar FdS a Proveedor");
            //LOG MOVIMIENTOS

        endwhile;

        //SEGUNDO OBTENEMOS LAS FICHAS A RECLAMAR
        //EMPIECE DEL CUERPO
        $cuerpo = "<p style='font-family: Calibri, Arial; font-weight: bold; font-size: 14px;'>" . $auxiliar->traduce("Estimado proveedor", $rowIdiomaProveedor->SIGLAS) . " </p>";
        $cuerpo = "<p style='font-family: Calibri, Arial; font-weight: bold; font-size: 14px;'>" . $auxiliar->traduce("Adjuntamos relación de fichas de seguridad pendientes de ser entregadas. Procede a adjuntarlas a través de los links siguientes", $rowIdiomaProveedor->SIGLAS) . ": </p>";

        //RECOGEMOS LAS FICHAS A RECLAMAR, EL PROVEEDOR PUEDE TENER MAS DE UNA
        $sqlFichasSeguridadReclamarAProveedor    = "SELECT * FROM INCIDENCIA_FICHA_SEGURIDAD_MATERIAL_IDIOMA
                                          WHERE ID_PROVEEDOR = $idProveedor
                                          AND ESTADO_INCIDENCIA = 'No Resuelta'
                                          AND  ESTADO_COMUNICACION_PROVEEDOR = 'Comunicada a Proveedor-Pdte. Respuesta'
                                          AND RECLAMADA = 'No'
                                          AND ANULADO_MANUALMENTE = 0
                                          AND DATEDIFF(CURDATE(),FECHA_COMUNICACION_PROVEEDOR) >= $tiempoReclamarFdSProveedor
                                          AND BAJA = 0 ";
        $resultFichasSeguridadReclamarAProveedor = $bd->ExecSQL($sqlFichasSeguridadReclamarAProveedor, "No");
        while ($rowReclamarAProveedor = $bd->SigReg($resultFichasSeguridadReclamarAProveedor)):
            //ACTUALIZO LA INCIDENCIA
            $sql       = "UPDATE INCIDENCIA_FICHA_SEGURIDAD_MATERIAL_IDIOMA SET
                        ESTADO_COMUNICACION_PROVEEDOR ='Comunicada a Proveedor-Pdte. Respuesta'
                        , RECLAMADA = 'Reclamada a Proveedor'
                        , FECHA_RECLAMACION_PROVEEDOR = '" . date("Y-m-d H:i:s") . "'
                        , FECHA_MODIFICACION = '" . date("Y-m-d H:i:s") . "'
                        , ID_ADMINISTRADOR_MODIFICACION = $rowAdministradorProcesos->ID_ADMINISTRADOR
                        WHERE ID_INCIDENCIA_FICHA_SEGURIDAD_MATERIAL_IDIOMA = $rowReclamarAProveedor->ID_INCIDENCIA_FICHA_SEGURIDAD_MATERIAL_IDIOMA";
            $TipoError = "ErrorEjecutarSql";
            $bd->ExecSQL($sql);

            //FICHA SEGURIDAD ACTULIZADA
            $rowInciFichaSeguridadActualizada = $bd->VerReg("INCIDENCIA_FICHA_SEGURIDAD_MATERIAL_IDIOMA", "ID_INCIDENCIA_FICHA_SEGURIDAD_MATERIAL_IDIOMA", $rowReclamarAProveedor->ID_INCIDENCIA_FICHA_SEGURIDAD_MATERIAL_IDIOMA, "No");

            //MATERIAL
            $rowMaterial = $bd->VerReg("MATERIAL", "ID_MATERIAL", $rowReclamarAProveedor->ID_MATERIAL, "No");

            //MATERIAL PROVEEDOR
            $GLOBALS["NotificaErrorPorEmail"] = "No";
            $rowMaterialProveedor             = $bd->VerRegRest("MATERIAL_PROVEEDOR", "ID_MATERIAL = $rowMaterial->ID_MATERIAL AND ID_PROVEEDOR = $rowProveedor->ID_PROVEEDOR", "No");
            //IDIOMA PAIS
            $rowIdiomaPais = $bd->VerReg("IDIOMA", "ID_IDIOMA", $rowReclamarAProveedor->ID_IDIOMA, "No");

            //OBTENER PEDIDO Y POSICION DE PEDIDO
            $sqlPedido                        = "SELECT PE.PEDIDO_SAP,PEL.LINEA_PEDIDO_SAP FROM PEDIDO_ENTRADA PE
                                    INNER JOIN PEDIDO_ENTRADA_LINEA PEL ON PEL.ID_PEDIDO_ENTRADA = PE.ID_PEDIDO_ENTRADA
                                    WHERE (TIPO_PEDIDO = 'Compra' OR TIPO_PEDIDO = 'Compra SGA Manual') AND FECHA_CREACION =
                                            ( SELECT MAX(FECHA_CREACION) FROM PEDIDO_ENTRADA PE2
                                                INNER JOIN PEDIDO_ENTRADA_LINEA PEL2   ON PEL2.ID_PEDIDO_ENTRADA = PE2.ID_PEDIDO_ENTRADA
                                                WHERE PEL2.ID_MATERIAL =" . $rowMaterial->ID_MATERIAL . "
                                            )
				              AND PE.ID_PROVEEDOR =" . $rowProveedor->ID_PROVEEDOR . "
                                    AND PEL.ID_MATERIAL = " . $rowMaterial->ID_MATERIAL;
            $GLOBALS["NotificaErrorPorEmail"] = "No";
            $resultPedido                     = $bd->ExecSQL($sqlPedido, "No");
            $numPedido                        = "";
            $posicionPedido                   = "";

            if ($bd->NumRegs($resultPedido) > 0):
                $rowPedido      = $bd->SigReg($resultPedido);
                $numPedido      = $rowPedido->PEDIDO_SAP;
                $posicionPedido = $rowPedido->LINEA_PEDIDO_SAP;
            endif;

            //FICHA SEGURIDAD
            $GLOBALS["NotificaErrorPorEmail"] = "No";
            $NotificaErrorPorEmail            = "No";
            $rowFichaSeguridadIdioma          = $bd->VerRegRest("INCIDENCIA_FICHA_SEGURIDAD_MATERIAL_IDIOMA", "ID_PROVEEDOR = $rowProveedor->ID_PROVEEDOR AND ID_MATERIAL = $rowMaterial->ID_MATERIAL AND ID_IDIOMA = $rowIdiomaPais->ID_IDIOMA ", "No");

            //CUERPO
            $cuerpo .= $this->obtenerCuerpoHTMLMaterial($rowMaterial, $rowMaterialProveedor, $rowIdiomaPais, $rowIdiomaProveedor->SIGLAS, $numPedido, $posicionPedido);
            //            $cuerpo .= "<span style='font-family: Calibri, Arial; font-weight: bold; font-size: 14px;'><u>" . $auxiliar->traduce("Ref. Acciona Material", $rowIdiomaProveedor->SIGLAS) . "</u> : " . $rowMaterial->REFERENCIA_SGA . " - " . ($rowIdiomaProveedor->SIGLAS == "ESP" ? $rowMaterial->DESCRIPCION : $rowMaterial->DESCRIPCION_EN) . "</span><br>"
            //                . "<span style='font-family: Calibri, Arial; font-weight: bold; font-size: 14px;'><u>" . $auxiliar->traduce("Ref. Proveedor Material", $rowIdiomaProveedor->SIGLAS) . "</u> : " . $rowMaterialProveedor->REF_MATERIAL_PROVEEDOR . "</span><br>"
            //                . "<span style='font-family: Calibri, Arial; font-weight: bold; font-size: 14px;'><u>" . $auxiliar->traduce("Idioma Ficha", $rowIdiomaProveedor->SIGLAS) . "</u> : " . $rowIdiomaPais->{"IDIOMA_" . $rowIdiomaProveedor->SIGLAS} . "</span><br>"
            //                . "<span style='font-family: Calibri, Arial; font-weight: bold; font-size: 14px;'><u>" . $auxiliar->traduce("Ultimo pedido", $rowIdiomaProveedor->SIGLAS) . "</u> : " . $numPedido . "</span><br>"
            //                . "<span style='font-family: Calibri, Arial; font-weight: bold; font-size: 14px;'><u>" . $auxiliar->traduce("Nº Linea", $rowIdiomaProveedor->SIGLAS) . "</u> : " . $posicionPedido . "</span><br>";
            $cuerpo .= "<a href='"
                . $url_web . "maestros/fichas_seguridad_idioma/ficha_proveedor.php?idFichaSeguridad=$rowReclamarAProveedor->ID_INCIDENCIA_FICHA_SEGURIDAD_MATERIAL_IDIOMA&key=$rowFichaSeguridadIdioma->KEY_CORREO'>" . $auxiliar->traduce("Pulse aqui para adjuntar ficha", $rowIdiomaProveedor->SIGLAS) . "</a><br><br>";

            //GUARDAMOS TODAS LAS INCIDENCIAS QUE SE VAN A RECLAMAR POR PRIMERA VEZ
            $arrIncidenciasFdSAReclamar[] = $rowReclamarAProveedor->ID_INCIDENCIA_FICHA_SEGURIDAD_MATERIAL_IDIOMA;

            //GRABO LAS OBSERVACIONES EN OBSERVACIONES SISTEMA
            $observaciones_sistema->Grabar("INCIDENCIA_FICHA_SEGURIDAD_MATERIAL", $rowReclamarAProveedor->ID_INCIDENCIA_FICHA_SEGURIDAD_MATERIAL_IDIOMA, "Reclamar FdS a Proveedor");
            //LOG MOVIMIENTOS
            $administrador->Insertar_Log_Movimientos($rowAdministradorProcesos->ID_ADMINISTRADOR, "Modificación", "Incidencia Ficha Seguridad Material", $rowReclamarAProveedor->ID_INCIDENCIA_FICHA_SEGURIDAD_MATERIAL_IDIOMA, "Reclamar a Proveedor Primera vez (Proceso Automatico)", "INCIDENCIA_FICHA_SEGURIDAD_MATERIAL_IDIOMA", $rowReclamarAProveedor, $rowInciFichaSeguridadActualizada);
        endwhile;
        //ESTABLECEMOS EL CUERPO CON LAS FICHAS A RECLAMAR Y LO PENDIENTE
        $cuerpo = $cuerpo . $cuerpoFichasReclamadas;

        //DECLARAMOS EL ARRAY DE DESTINATARIO
        $arrDest                = array();
        $arrDest['PROVEEDOR'][] = $rowProveedor->ID_PROVEEDOR;

        //CREAMOS EL AVISO PARA CADA FICHA DE SEGURIDAD QUE HA SIDO RECLAMADA
        //PARA QUE QUEDE VISIBLE EN LA TRAZA DE LA FICHA DE LA INCIDENCIA (FICHA DE CORREOS ENVIADOS)
        foreach ($arrIncidenciasFdSAReclamar as $idFichaSeguridad):
            $idAviso = $this->GuardarAviso($idFichaSeguridad, "FICHA_SEGURIDAD_MATERIAL", $asunto, $cuerpo, $arrDest);
            //MARCAMOS EL AVISO COMO ENVIADO
            $sqlUpdate = " UPDATE AVISO SET
                               ENVIADO = 1
                              ,FECHA_ULTIMO_ENVIO = '" . date("Y-m-d H:i:s") . "'
                              WHERE ID_AVISO = $idAviso";
            $bd->ExecSQL($sqlUpdate);
        endforeach;

        //SOLO ENVIAMOS UN AVISO (SON TODOS IGUALES)
        //ENVIAMOS EL AVISOS
        if ($idAviso != ""):
            $this->enviarAviso($idAviso);
        endif;
    }


    /**
     * ENVIA CORREO A PROVEEDOR CON LAS FICHAS DE SEGURIDAD PENDIENTES DE ADJUNTAR
     * @param $idProveedor int
     */
    function envioPendientesFichaSeguridadMaterialProveedor($idProveedor)
    {
        global $auxiliar, $administrador, $bd, $rowAdministradorProcesos;
        global $url_web;
        global $observaciones_sistema;

        //ARRAY DE INCIDENCIAS REVISADAS
        $arrIncidenciasFdSAReclamar = array();

        //PROVEEDOR
        $rowProveedor = $bd->VerReg("PROVEEDOR", "ID_PROVEEDOR", $idProveedor, "No");
        //PAIS PROVEEDOR
        $rowPaisProveedor = $bd->VerReg("PAIS", "ID_PAIS", $rowProveedor->ID_PAIS, "No");
        //IDIOMA PROVEEDOR
        $rowIdiomaProveedor = $bd->VerReg("IDIOMA", "ID_IDIOMA", $rowPaisProveedor->ID_IDIOMA_PRINCIPAL, "No");

        //ASUNTO CORREO
        $asunto = $auxiliar->traduce("SE RECLAMAN FICHAS DE SEGURIDAD PENDIENTES", $rowIdiomaProveedor->SIGLAS);

        // RECORREMOS LAS FICHAS QUE YA HAN SIDO RECLAMADAS PERO NO HA SIDO ADJUNTADA LA FICHA (PENDIENTE)
        // POR EL PROVEEDOR PARA VOLVERLAS A RECLAMAR
        $sqlFichasSeguridadReclamadasAProveedor    = "SELECT * FROM INCIDENCIA_FICHA_SEGURIDAD_MATERIAL_IDIOMA
                                          WHERE ID_PROVEEDOR = $idProveedor
                                          AND ESTADO_INCIDENCIA = 'No Resuelta'
                                          AND  ESTADO_COMUNICACION_PROVEEDOR = 'Comunicada a Proveedor-Pdte. Respuesta'
                                              AND ( RECLAMADA = 'Reclamada a Proveedor' OR RECLAMADA = 'Reclamada a Comprador' )
                                          AND ANULADO_MANUALMENTE = 0
                                          AND BAJA = 0 ";
        $resultFichasSeguridadReclamadasAProveedor = $bd->ExecSQL($sqlFichasSeguridadReclamadasAProveedor, "No");

        //EMPIECE DEL CUERPO PENDIENTES
        $cuerpo = "<p style='font-family: Calibri, Arial; font-weight: bold; font-size: 14px;'>" . $auxiliar->traduce("FICHAS DE SEGURIDAD ANTERIORMENTE REQUERIDAS Y TODAVÍA PENDIENTES DE SER SUBIDAS", $rowIdiomaProveedor->SIGLAS) . ": </p>";

        while ($rowReclamadaAProveedor = $bd->SigReg($resultFichasSeguridadReclamadasAProveedor)):

            //MATERIAL
            $rowMaterial = $bd->VerReg("MATERIAL", "ID_MATERIAL", $rowReclamadaAProveedor->ID_MATERIAL, "No");

            //MATERIAL PROVEEDOR
            $GLOBALS["NotificaErrorPorEmail"] = "No";
            $rowMaterialProveedor             = $bd->VerRegRest("MATERIAL_PROVEEDOR", "ID_MATERIAL = $rowMaterial->ID_MATERIAL AND ID_PROVEEDOR = $rowProveedor->ID_PROVEEDOR", "No");

            //IDIOMA PAIS
            $rowIdiomaPais = $bd->VerReg("IDIOMA", "ID_IDIOMA", $rowReclamadaAProveedor->ID_IDIOMA, "No");

            //FICHA SEGURIDAD
            $GLOBALS["NotificaErrorPorEmail"] = "No";
            $rowFichaSeguridadIdioma          = $bd->VerRegRest("INCIDENCIA_FICHA_SEGURIDAD_MATERIAL_IDIOMA", "ID_PROVEEDOR = $rowProveedor->ID_PROVEEDOR AND ID_MATERIAL = $rowMaterial->ID_MATERIAL AND ID_IDIOMA = $rowReclamadaAProveedor->ID_IDIOMA ", "No");

            //OBTENER PEDIDO Y POSICION DE PEDIDO
            $sqlPedido                        = "SELECT PE.PEDIDO_SAP,PEL.LINEA_PEDIDO_SAP FROM PEDIDO_ENTRADA PE
                                    INNER JOIN PEDIDO_ENTRADA_LINEA PEL ON PEL.ID_PEDIDO_ENTRADA = PE.ID_PEDIDO_ENTRADA
                                    WHERE (TIPO_PEDIDO = 'Compra' OR TIPO_PEDIDO = 'Compra SGA Manual') AND FECHA_CREACION =
                                            ( SELECT MAX(FECHA_CREACION) FROM PEDIDO_ENTRADA PE2
                                                INNER JOIN PEDIDO_ENTRADA_LINEA PEL2   ON PEL2.ID_PEDIDO_ENTRADA = PE2.ID_PEDIDO_ENTRADA
                                                WHERE PEL2.ID_MATERIAL =" . $rowMaterial->ID_MATERIAL . "
                                            )
				              AND PE.ID_PROVEEDOR =" . $rowProveedor->ID_PROVEEDOR . "
                                    AND PEL.ID_MATERIAL = " . $rowMaterial->ID_MATERIAL;
            $GLOBALS["NotificaErrorPorEmail"] = "No";
            $NotificaErrorPorEmail            = "No";
            $resultPedido                     = $bd->ExecSQL($sqlPedido, "No");

            $numPedido      = "";
            $posicionPedido = "";

            if ($bd->NumRegs($resultPedido) > 0):
                $rowPedido      = $bd->SigReg($resultPedido);
                $numPedido      = $rowPedido->PEDIDO_SAP;
                $posicionPedido = $rowPedido->LINEA_PEDIDO_SAP;
            endif;

            //CREAMOS LOS DATOS DEL CUERPO
            $cuerpo .= $this->obtenerCuerpoHTMLMaterial($rowMaterial, $rowMaterialProveedor, $rowIdiomaPais, $rowIdiomaProveedor->SIGLAS, $numPedido, $posicionPedido);
            //            $cuerpo .= "<span style='font-family: Calibri, Arial; font-weight: bold; font-size: 14px;'><u>" . $auxiliar->traduce("Ref. Acciona Material", $rowIdiomaProveedor->SIGLAS) . "</u> :" . $rowMaterial->REFERENCIA_SGA . " - " . ($rowIdiomaProveedor->SIGLAS == "ESP" ? $rowMaterial->DESCRIPCION : $rowMaterial->DESCRIPCION_EN) . "</span><br>"
            //                . "<span style='font-family: Calibri, Arial; font-weight: bold; font-size: 14px;'><u>" . $auxiliar->traduce("Ref. Proveedor Material", $rowIdiomaProveedor->SIGLAS) . " </u> :" . $rowMaterialProveedor->REF_MATERIAL_PROVEEDOR . "</span><br>"
            //                . "<span style='font-family: Calibri, Arial; font-weight: bold; font-size: 14px;'><u>" . $auxiliar->traduce("Idioma Ficha", $rowIdiomaProveedor->SIGLAS) . " </u> :" . $rowIdiomaPais->{"IDIOMA_" . $rowIdiomaProveedor->SIGLAS} . "</span><br>"
            //                . "<span style='font-family: Calibri, Arial; font-weight: bold; font-size: 14px;'><u>" . $auxiliar->traduce("Ultimo pedido", $rowIdiomaProveedor->SIGLAS) . "</u> : " . $numPedido . "</span><br>"
            //                . "<span style='font-family: Calibri, Arial; font-weight: bold; font-size: 14px;'><u>" . $auxiliar->traduce("Nº Linea", $rowIdiomaProveedor->SIGLAS) . "</u> : " . $posicionPedido . "</span><br>";


            $cuerpo .= "<a href='"
                . $url_web . "maestros/fichas_seguridad_idioma/ficha_proveedor.php?idFichaSeguridad=$rowReclamadaAProveedor->ID_INCIDENCIA_FICHA_SEGURIDAD_MATERIAL_IDIOMA&key=$rowFichaSeguridadIdioma->KEY_CORREO'>" . $auxiliar->traduce("Pulse aqui para adjuntar ficha", $rowIdiomaProveedor->SIGLAS) . "</a><br><br>";

            //GUARDAMOS TODAS LAS INCIDENCIAS QUE SE VAN A RECLAMAR POR PRIMERA VEZ
            $arrIncidenciasFdSAReclamar[] = $rowReclamadaAProveedor->ID_INCIDENCIA_FICHA_SEGURIDAD_MATERIAL_IDIOMA;
            //GRABO LAS OBSERVACIONES EN OBSERVACIONES SISTEMA
            $observaciones_sistema->Grabar("INCIDENCIA_FICHA_SEGURIDAD_MATERIAL", $rowReclamadaAProveedor->ID_INCIDENCIA_FICHA_SEGURIDAD_MATERIAL_IDIOMA, "Informe Semanal FdS pendientes a Proveedor");

        endwhile;

        //DECLARAMOS EL ARRAY DE DESTINATARIO
        $arrDest                = array();
        $arrDest['PROVEEDOR'][] = $rowProveedor->ID_PROVEEDOR;

        //CREAMOS EL AVISO PARA CADA FICHA DE SEGURIDAD QUE HA SIDO RECLAMADA
        //PARA QUE QUEDE VISIBLE EN LA TRAZA DE LA FICHA DE LA INCIDENCIA (FICHA DE CORREOS ENVIADOS)
        foreach ($arrIncidenciasFdSAReclamar as $idFichaSeguridad):
            $idAviso = $this->GuardarAviso($idFichaSeguridad, "FICHA_SEGURIDAD_MATERIAL", $asunto, $cuerpo, $arrDest);
            //MARCAMOS EL AVISO COMO ENVIADO
            $sqlUpdate = " UPDATE AVISO SET
                               ENVIADO = 1
                              ,FECHA_ULTIMO_ENVIO = '" . date("Y-m-d H:i:s") . "'
                              WHERE ID_AVISO = $idAviso";
            $bd->ExecSQL($sqlUpdate);
        endforeach;

        //SOLO ENVIAMOS UN AVISO (SON TODOS IGUALES)
        //ENVIAMOS EL AVISOS
        if ($idAviso != ""):
            $this->enviarAviso($idAviso);
        endif;
    }


    /**
     * NO SE USA
     */
    function envioComunicacionCompradorFichaSeguridadMaterial($idPlanificador, $idProveedor, $idMaterial, $idFichaSeguridad, $idIdiomaPais)
    {
        global $auxiliar, $administrador, $bd;
        global $url_web;

        //PROVEEDOR
        $rowPlanificador = $bd->VerReg("PLANIFICADOR", "ID_PLANIFICADOR", $idPlanificador, "No");
        //MATERIAL
        $rowMaterial = $bd->VerReg("MATERIAL", "ID_MATERIAL", $idMaterial, "No");
        //MATERIAL PROVEEDOR
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $NotificaErrorPorEmail            = "No";
        //IDIOMA PAIS
        $rowIdiomaPais = $bd->VerReg("IDIOMA", "ID_IDIOMA", $idIdiomaPais, "No");
        //FICHA SEGURIDAD
        $GLOBALS["NotificaErrorPorEmail"] = "No";
        $NotificaErrorPorEmail            = "No";
        $rowFichaSeguridadIdioma          = $bd->VerRegRest("INCIDENCIA_FICHA_SEGURIDAD_MATERIAL_IDIOMA", "ID_PROVEEDOR = $idProveedor AND ID_MATERIAL = $idMaterial AND ID_IDIOMA = $rowIdiomaPais->ID_IDIOMA ", "No");


        $asunto = $auxiliar->traduce("SE RECLAMA FICHA DE SEGURIDAD", "ESP") . ". " . $auxiliar->traduce("MATERIAL", "ESP") . ":  $rowMaterial->REFERENCIA_SGA";


        $cuerpo = "<p style='font-family: Calibri, Arial; font-weight: bold; font-size: 14px;'>" . $auxiliar->traduce("SE RECLAMA FICHA DE SEGURIDAD PARA EL MATERIAL", "ESP") . ": </p>";
        $cuerpo .= "<p style='font-family: Calibri, Arial; font-weight: bold; font-size: 14px;'>" . $auxiliar->traduce("Ref. Acciona Material", "ESP") . ": $rowMaterial->REFERENCIA_SGA - $rowMaterial->DESCRIPCION / $rowMaterial->DESCRIPCION_EN. </p>"
            . "<p style='font-family: Calibri, Arial; font-weight: bold; font-size: 14px;'>" . " </p>"
            . "<p style='font-family: Calibri, Arial; font-weight: bold; font-size: 14px;'>" . $auxiliar->traduce("Idioma Ficha", "ESP") . ": $rowIdiomaPais->IDIOMA_ESP / $rowIdiomaPais->IDIOMA_ENG.</p>";


        $cuerpo .= "<br><br><a href='"
            . $url_web . "maestros/fichas_seguridad_idioma/ficha_proveedor.php?idFichaSeguridad=$idFichaSeguridad&key=$rowFichaSeguridadIdioma->KEY_CORREO'>" . $auxiliar->traduce("Pulse aqui para adjuntar ficha", "ESP") . "</a>";

        //DECLARAMOS EL ARRAY DE DESTINATARIO
        $arrDest                   = array();
        $arrDest['PLANIFICADOR'][] = $rowPlanificador->ID_PLANIFICADOR;

        //CREAMOS EL AVISO
        $idAviso = $this->GuardarAviso($idFichaSeguridad, "FICHA_SEGURIDAD_MATERIAL", $asunto, $cuerpo, $arrDest);

        //ENVIAMOS EL AVISOS
        if ($idAviso != ""):
            $this->enviarAviso($idAviso);
        endif;
    }


    //ENVIAR CORREO DE PETICION DE FICHAS DE SEGURIDAD

    /**
     * array de lienas de pedido
     * tipo pedido [entrada, salida]
     */
    function envioAvisoAlmacenSolpedDistinto($arrIdPedidoLinea, $idMovimientoEntrada)
    {
        global $auxiliar, $administrador, $bd;
        global $url_web;

        //DECLARAMOS EL ARRAY DE DESTINATARIO
        $arrDestESP         = array();
        $arrResponsablesESP = array();
        $arrDestENG         = array();
        $arrResponsablesENG = array();

        $asuntoESP = $auxiliar->traduce("RECECION PEDIDO CON ALMACEN SOLPED DISTINTO", "ESP");
        $asuntoENG = $auxiliar->traduce("RECECION PEDIDO CON ALMACEN SOLPED DISTINTO", "ENG");
        $cuerpoESP = "<p style='font-family: Calibri, Arial; font-weight: bold; font-size: 14px;'>" . $auxiliar->traduce("LINEAS DE PEDIDO CON ALMACEN SOLPED DISTINTO A ALMACEN DE DESTINO", "ESP") . " " . $auxiliar->traduce("Movimiento Entrada", "ESP") . ": $idMovimientoEntrada</p>";
        $cuerpoENG = "<p style='font-family: Calibri, Arial; font-weight: bold; font-size: 14px;'>" . $auxiliar->traduce("LINEAS DE PEDIDO CON ALMACEN SOLPED DISTINTO A ALMACEN DE DESTINO", "ENG") . " " . $auxiliar->traduce("Movimiento Entrada", "ENG") . ": $idMovimientoEntrada</p>";


        foreach ($arrIdPedidoLinea as $idPedidoLinea):
            if ($idPedidoLinea != ""):

                $rowLineaPedido = $bd->VerReg("PEDIDO_ENTRADA_LINEA", "ID_PEDIDO_ENTRADA_LINEA", $idPedidoLinea, "No");
                //ALMACEN DESTINO
                $GLOBALS["NotificaErrorPorEmail"] = "No";
                $NotificaErrorPorEmail            = "No";
                $rowAlmacen                       = $bd->VerReg("ALMACEN", "ID_ALMACEN", $rowLineaPedido->ID_ALMACEN, "No");

                //MATERIAL
                $rowMaterial = $bd->VerReg("MATERIAL", "ID_MATERIAL", $rowLineaPedido->ID_MATERIAL, "No");
                //ALMACEN SOLPED
                $GLOBALS["NotificaErrorPorEmail"] = "No";
                $NotificaErrorPorEmail            = "No";
                $rowAlmacenSolped                 = $bd->VerReg("ALMACEN", "ID_ALMACEN", $rowLineaPedido->ID_ALMACEN_SOLPED, "No");
                //MOVIMIENTO ENTRADA LINEA
                $rowMovimientoEntradaLinea = $bd->VerReg("MOVIMIENTO_ENTRADA_LINEA", "ID_PEDIDO_LINEA", $rowLineaPedido->ID_PEDIDO_ENTRADA_LINEA, "No");
                $rowUbicacion              = $bd->VerReg("UBICACION", "ID_UBICACION", $rowMovimientoEntradaLinea->ID_UBICACION, "No");

                //RESPONSABLES SUBZONAS LOGISTICAS
                $rowSubzonaLogistica                = $bd->VerReg("SUBZONA", "ID_SUBZONA", $rowAlmacen->ID_SUBZONA_LOGISTICA, "No");
                $sqlResponsablesSubzonaLogistica    = "SELECT * FROM SUBZONA_RESPONSABLE WHERE ID_SUBZONA = $rowSubzonaLogistica->ID_SUBZONA AND BAJA = 0";
                $resultResponsablesSubzonaLogistica = $bd->ExecSQL($sqlResponsablesSubzonaLogistica, "No");
                while ($rowResponsableSubzonaLogistica = $bd->SigReg($resultResponsablesSubzonaLogistica)):
                    $rowAdminResponsable = $bd->VerReg("ADMINISTRADOR", "ID_ADMINISTRADOR", $rowResponsableSubzonaLogistica->ID_ADMINISTRADOR_RESPONSABLE);
                    if ($rowAdminResponsable->IDIOMA_NOTIFICACIONES == "ESP"):
                        $arrResponsablesESP[] = $rowAdminResponsable->ID_ADMINISTRADOR;
                    elseif ($rowAdminResponsable->IDIOMA_NOTIFICACIONES == "ENG"):
                        $arrResponsablesENG[] = $rowAdminResponsable->ID_ADMINISTRADOR;
                    endif;
                endwhile;


                $cuerpoESP .= "<p style='font-family: Calibri, Arial; font-weight: bold; font-size: 14px;'>" . $auxiliar->traduce("Ubicacion", "ESP") . ": $rowUbicacion->UBICACION. </p>"
                    . "<p style='font-family: Calibri, Arial; font-weight: bold; font-size: 14px;'>" . $auxiliar->traduce("Ref. Acciona Material", "ESP") . ": $rowMaterial->REFERENCIA_SGA - $rowMaterial->DESCRIPCION / $rowMaterial->DESCRIPCION_EN. </p>"
                    . "<p style='font-family: Calibri, Arial; font-weight: bold; font-size: 14px;'>" . $auxiliar->traduce("Almacen Destino", "ESP") . ": $rowAlmacen->REFERENCIA -  $rowAlmacen->NOMBRE.</p>"
                    . "<p style='font-family: Calibri, Arial; font-weight: bold; font-size: 14px;'>" . $auxiliar->traduce("Almacen Solped", "ESP") . ": $rowAlmacenSolped->REFERENCIA -  $rowAlmacenSolped->NOMBRE.</p>";
                $cuerpoENG .= "<p style='font-family: Calibri, Arial; font-weight: bold; font-size: 14px;'>" . $auxiliar->traduce("Ubicacion", "ENG") . ": $rowUbicacion->UBICACION. </p>"
                    . "<p style='font-family: Calibri, Arial; font-weight: bold; font-size: 14px;'>" . $auxiliar->traduce("Ref. Acciona Material", "ENG") . ": $rowMaterial->REFERENCIA_SGA - $rowMaterial->DESCRIPCION / $rowMaterial->DESCRIPCION_EN. </p>"
                    . "<p style='font-family: Calibri, Arial; font-weight: bold; font-size: 14px;'>" . $auxiliar->traduce("Almacen Destino", "ENG") . ": $rowAlmacen->REFERENCIA -  $rowAlmacen->NOMBRE.</p>"
                    . "<p style='font-family: Calibri, Arial; font-weight: bold; font-size: 14px;'>" . $auxiliar->traduce("Almacen Solped", "ENG") . ": $rowAlmacenSolped->REFERENCIA -  $rowAlmacenSolped->NOMBRE.</p>";
            endif;

        endforeach;
        $arrDestESP['ADMINISTRADOR'] = array_unique( (array)$arrResponsablesESP);
        $arrDestENG['ADMINISTRADOR'] = array_unique( (array)$arrResponsablesENG);


        //CREAMOS EL AVISO
        $idAvisoESP = $this->GuardarAviso($idMovimientoEntrada, "RECEPCION_ALMACEN_SOLPED", $asuntoESP, $cuerpoESP, $arrDestESP);
        $idAvisoENG = $this->GuardarAviso($idMovimientoEntrada, "RECEPCION_ALMACEN_SOLPED", $asuntoENG, $cuerpoENG, $arrDestENG);


        //ENVIAMOS EL AVISOS
        if ($idAvisoESP != ""):
            $this->enviarAviso($idAvisoESP);
        endif;
        if ($idAvisoENG != ""):
            $this->enviarAviso($idAvisoENG);
        endif;
    }

    /*****
     * SE OBTIENEN LAS DIRECCIONES DE CORREO ESPECIFICAS A LAS QUE SE ENVIO EL AVISO
     * NO LAS ACTUALES DEL ADMINISTRADOR/PROVEEDOR/SOLICTUD...
     * $idAviso
     */
    function obtenerDestinatariosEnvio($idAviso)
    {

        global $bd;

        //BUSCAMOS EL AVISO
        $rowAviso = $bd->VerReg("AVISO", "ID_AVISO", $idAviso);

        return $rowAviso->EMAILS_DESTINATARIOS;
    }

    /*****
     * ENVIO SOLICITUD EMAIL PARA FICHAS DE SEGURIDAD A PROVEEDOR
     * $idProveedor int
     * $idIncidenciaFichaSeguridad int
     * $reclamar string
     */
    function envioSolicitudEmailProveedorFdS($idProveedor, $idIncidenciaFichaSeguridad, $reclamar = "No")
    {
        global $auxiliar, $administrador, $bd;
        global $url_web;

        //PROVEEDOR
        $rowProveedor = $bd->VerReg("PROVEEDOR", "ID_PROVEEDOR", $idProveedor, "No");
        //PAIS PROVEEDOR
        $rowPaisProveedor = $bd->VerReg("PAIS", "ID_PAIS", $rowProveedor->ID_PAIS, "No");
        //IDIOMA PROVEEDOR
        $rowIdiomaProveedor = $bd->VerReg("IDIOMA", "ID_IDIOMA", $rowPaisProveedor->ID_IDIOMA_PRINCIPAL, "No");

        //FICHA SEGURIDAD ACTULIZADA
        $rowIncidenciaFichaSeguridad = $bd->VerReg("INCIDENCIA_FICHA_SEGURIDAD_MATERIAL_IDIOMA", "ID_INCIDENCIA_FICHA_SEGURIDAD_MATERIAL_IDIOMA", $idIncidenciaFichaSeguridad, "No");

        if ($reclamar == "Si"):
            $asunto = $auxiliar->traduce("Reclamación Correo electrónico para solicitud de fichas de seguridad (FDSs)", $rowIdiomaProveedor->SIGLAS);
        else:
            $asunto = $auxiliar->traduce("Correo electrónico para solicitud de fichas de seguridad (FDSs)", $rowIdiomaProveedor->SIGLAS);
        endif;


        $cuerpo = "<p style='font-family: Calibri, Arial; font-weight: bold; font-size: 14px;'>" . $auxiliar->traduce("Estimado proveedor", $rowIdiomaProveedor->SIGLAS) . " </p>";
        $cuerpo .= "<p style='font-family: Calibri, Arial; font-weight: bold; font-size: 14px;'>"
            . $auxiliar->traduce("Solicitamos nos indique una dirección de correo electrónico, donde solicitaremos la actualización de las fichas de seguridad de los productos que usted nos suministra.", $rowIdiomaProveedor->SIGLAS) . "</p>";
        $cuerpo .= "<p style='font-family: Calibri, Arial; font-weight: bold; font-size: 14px;'>"
            . $auxiliar->traduce("En el link adjunto podrá incluir esto datos en el registro que tenemos de su sociedad en nuestra aplicación.", $rowIdiomaProveedor->SIGLAS) . "</p>";
        $cuerpo .= "<p style='font-family: Calibri, Arial; font-weight: bold; font-size: 14px;'>"
            . $auxiliar->traduce("Muchas Gracias. Saludos.", $rowIdiomaProveedor->SIGLAS) . "</p>";

        $cuerpo .= "\r\n<br><br><a href='"
            . str_replace( " ", "",(string) $url_web . "problemas/control_fichas_seguridad/ficha_email_proveedor_fds_externo.php?idFichaSeguridad=$idIncidenciaFichaSeguridad&idProveedor=" . $idProveedor . "&key=" . $rowProveedor->KEY_CORREO_FDS) . "'>" . $auxiliar->traduce("Pulse aqui para adjuntar correo", $rowIdiomaProveedor->SIGLAS) . "</a>";


        //DECLARAMOS EL ARRAY DE DESTINATARIO
        $arrDest                = array();
        $arrDest['PROVEEDOR'][] = $rowProveedor->ID_PROVEEDOR;

        //CREAMOS EL AVISO
//        $idAviso = $this->GuardarAviso($idProveedor, "PROVEEDOR", $asunto, $cuerpo, $arrDest);
        $idAviso = $this->GuardarAviso($idIncidenciaFichaSeguridad, "FICHA_SEGURIDAD_MATERIAL", $asunto, $cuerpo, $arrDest);

//        ENVIAMOS EL AVISOS
        if ($idAviso != ""):
            $this->enviarAviso($idAviso);
        endif;
    }

    /**
     * COMUNICACIÓN A COMPRADOR DE LAS SOLICITUDES PENDIENTES A LOS PROVEEDORES
     * @param $idPlanificador int
     */
    function envioComunicacionComprador($idPlanificador)
    {

        global $bd, $auxiliar, $administrador;
        global $url_web;
        global $observaciones_sistema;
        global $arrProveedorEmailCorreo;
        $cuerpo                     = "";
        $cuerpoPeticionCorreoInicio = "";
        $cuerpoPeticionCorreo       = "";
        $cuerpoPeticionFDS          = "";

        $spanConStilo = "<span style='font-family: Calibri, Arial; font-weight: bold; font-size: 14px;'>";

        //ARRAY DE INCIDENCIAS REVISADAS
        $arrIncidenciasFdSAReclamar = array();

        //IDIOMA COMPRADOR
        $rowPlanificador              = $bd->VerReg("PLANIFICADOR", "ID_PLANIFICADOR", $idPlanificador);
        $rowAdministradorPlanificador = $bd->VerReg("ADMINISTRADOR", "USUARIO_SAP", $rowPlanificador->USUARIO_SAP);
        $siglasIdiona                 = $rowAdministradorPlanificador->IDIOMA_NOTIFICACIONES;

        //ASUNTO DEL CORREO
        $asunto = $auxiliar->traduce("Correo electrónico reclamación actualización de información fichas de seguridad (FDSs)", $siglasIdiona);


        //1º RECOGEMOS AQUELLOS PROVEEDORES QUE NO TIENEN ASIGNADO CORREO Y SE LES HA INFORMADO
        $sqlProveedoresSinCorreo    = "SELECT MA.ID_PLANIFICADOR, P.ID_PROVEEDOR, IFS.ID_INCIDENCIA_FICHA_SEGURIDAD_MATERIAL_IDIOMA FROM INCIDENCIA_FICHA_SEGURIDAD_MATERIAL_IDIOMA IFS
                                      INNER JOIN MATERIAL_ALMACEN MA ON MA.ID_ALMACEN = IFS.ID_ALMACEN AND MA.ID_MATERIAL = IFS.ID_MATERIAL
                                      INNER JOIN PROVEEDOR P ON P.ID_PROVEEDOR = IFS.ID_PROVEEDOR
                                    WHERE IFS.ID_PROVEEDOR IS NOT NULL AND IFS.ANULADO_MANUALMENTE = 0 AND IFS.BAJA = 0 AND IFS.ESTADO_COMUNICACION_PROVEEDOR = 'Pdte. Comunicar a Proveedor' AND IFS.ESTADO_INCIDENCIA = 'No Resuelta'
                                      AND (P.ESTADO_SOLICITUD_EMAIL_FDS = 'Reclamado a Proveedor' OR P.ESTADO_SOLICITUD_EMAIL_FDS = 'Reclamado a Comprador')
                                      AND P.ID_PROVEEDOR NOT IN (SELECT PE.ID_PROVEEDOR FROM PROVEEDOR_EMAIL PE WHERE PE.TIPO = 'FdS' AND PE.BAJA = 0)
                                    AND MA.ID_PLANIFICADOR = $rowPlanificador->ID_PLANIFICADOR
                                    GROUP BY P.ID_PROVEEDOR";
        $resultProveedoresSinCorreo = $bd->ExecSQL($sqlProveedoresSinCorreo, "No");
        $numProveedoresSinCorreo    = $bd->NumRegs($resultProveedoresSinCorreo);


        //MOSTRAMOS LOS PROVEEDORES QUE TIENEN PENDIENTE EMAIL PARA FDS
        while ($rowProveedorSinCorreo = $bd->SigReg($resultProveedoresSinCorreo)):
            $rowProveedor = $bd->VerReg("PROVEEDOR", "ID_PROVEEDOR", $rowProveedorSinCorreo->ID_PROVEEDOR, "No");
            if (!in_array($rowProveedor->ID_PROVEEDOR, (array) $arrProveedorEmailCorreo)):
                $sqlUpdateProveedor = "UPDATE PROVEEDOR SET
                                          FECHA_EMAIL_FDS_RECLAMACION_COMPRADOR= '" . date("Y-m-d H:i:s") . "'
                                          ,ESTADO_SOLICITUD_EMAIL_FDS = 'Reclamado a Comprador'
                                          WHERE ID_PROVEEDOR = $rowProveedor->ID_PROVEEDOR";
                $bd->ExecSQL($sqlUpdateProveedor);
                $cuerpoPeticionCorreo .= "<li>" . $spanConStilo . $rowProveedor->REFERENCIA . " - " . $rowProveedor->NOMBRE . " " . $rowProveedor->EMAIL . "</span></li>";

                //GUARDAMOS TODAS LAS INCIDENCIAS QUE SE VAN A RECLAMAR POR PRIMERA VEZ
                $arrIncidenciasFdSAReclamar[] = $rowProveedorSinCorreo->ID_INCIDENCIA_FICHA_SEGURIDAD_MATERIAL_IDIOMA;
                //GRABO LAS OBSERVACIONES EN OBSERVACIONES SISTEMA
                $observaciones_sistema->Grabar("INCIDENCIA_FICHA_SEGURIDAD_MATERIAL", $rowProveedorSinCorreo->ID_INCIDENCIA_FICHA_SEGURIDAD_MATERIAL_IDIOMA, "Reclamacion Email FdS a Comprador");
                $arrProveedorEmailCorreo[] = $rowProveedor->ID_PROVEEDOR;

                //GUARDAMOS EN TODAS LAS FICHAS LAS OBSERVACIONES Y EL CORREO
                $sqlIFSComunicarProveedor    = "SELECT IFS.*
                                      FROM INCIDENCIA_FICHA_SEGURIDAD_MATERIAL_IDIOMA IFS
                                    WHERE IFS.ID_PROVEEDOR = $rowProveedor->ID_PROVEEDOR AND IFS.BAJA = 0 AND IFS.ESTADO_INCIDENCIA = 'No Resuelta'
                                      AND IFS.ANULADO_MANUALMENTE = 0 AND  ESTADO_COMUNICACION_PROVEEDOR = 'Pdte. Comunicar a Proveedor'
                                      AND ID_INCIDENCIA_FICHA_SEGURIDAD_MATERIAL_IDIOMA <> $rowProveedorSinCorreo->ID_INCIDENCIA_FICHA_SEGURIDAD_MATERIAL_IDIOMA";
                $resultIFSComunicarProveedor = $bd->ExecSQL($sqlIFSComunicarProveedor);

                while ($rowIFSComunicarProveedor = $bd->SigReg($resultIFSComunicarProveedor)):
                    //GUARDAMOS TODAS LAS INCIDENCIAS QUE SE VAN A RECLAMAR POR PRIMERA VEZ
                    $arrIncidenciasFdSAReclamar[] = $rowIFSComunicarProveedor->ID_INCIDENCIA_FICHA_SEGURIDAD_MATERIAL_IDIOMA;
                    $observaciones_sistema->Grabar("INCIDENCIA_FICHA_SEGURIDAD_MATERIAL", $rowIFSComunicarProveedor->ID_INCIDENCIA_FICHA_SEGURIDAD_MATERIAL_IDIOMA, "Reclamacion Email FdS a Comprador");

                endwhile;
            endif;
        endwhile;

        //CERAMOS LA LISTA
        //ESTABLECEMOS INICIO DEL CUERPO PARA EMAIL FDS PENDIENTES, SI HAY
        if ($cuerpoPeticionCorreo != ""):
            $cuerpoPeticionCorreoInicio .= $spanConStilo . $auxiliar->traduce("MSJE_PROVEEDORES_SIN_CORREO_ELECTRONICO", $siglasIdiona) . ": " . "</span><br><br>";
            $cuerpoPeticionCorreoInicio .= $spanConStilo . " <u> " . $auxiliar->traduce("Num. Proveedores", $siglasIdiona) . ": " . $numProveedoresSinCorreo . "</u>, " . "</span><br><ul>";
            $cuerpoPeticionCorreo       = $cuerpoPeticionCorreoInicio . $cuerpoPeticionCorreo . "</ul>";
        endif;

        //2º RECOGEMOS AQUELLOS PROVEEDORES QUE TIENE FICHAS DE SEGURIDAD PENDIENTES
        $sqlProveedoresFichas    = "SELECT IFS.ID_PROVEEDOR, IFS.ID_INCIDENCIA_FICHA_SEGURIDAD_MATERIAL_IDIOMA,MA.ID_PLANIFICADOR
                                    FROM INCIDENCIA_FICHA_SEGURIDAD_MATERIAL_IDIOMA IFS
                                      INNER JOIN MATERIAL_ALMACEN MA ON MA.ID_ALMACEN = IFS.ID_ALMACEN AND MA.ID_MATERIAL = IFS.ID_MATERIAL
                                      INNER JOIN PROVEEDOR P ON P.ID_PROVEEDOR = IFS.ID_PROVEEDOR
                                    WHERE IFS.ID_PROVEEDOR IS NOT NULL
                                    AND IFS.BAJA = 0
                                    AND IFS.ANULADO_MANUALMENTE = 0
                                      AND IFS.ESTADO_COMUNICACION_PROVEEDOR = 'Comunicada a Proveedor-Pdte. Respuesta'
                                      AND (IFS.RECLAMADA = 'Reclamada a Proveedor' OR IFS.RECLAMADA = 'Reclamada a Comprador')
                                      AND IFS.ESTADO_INCIDENCIA = 'No Resuelta'
                                      AND ID_PLANIFICADOR = $rowPlanificador->ID_PLANIFICADOR
                                      GROUP BY IFS.ID_PROVEEDOR";
        $resultProveedoresFichas = $bd->ExecSQL($sqlProveedoresFichas, "No");
        $numProveedoresFicha     = $bd->NumRegs($resultProveedoresFichas);

        //ESTABLECEMOS INICIO DEL CUERPO PARA EMAIL FDS PENDIENTES, SI HAY
        if ($numProveedoresFicha > 0):
            $cuerpoPeticionFDS .= $spanConStilo . $auxiliar->traduce("Los siguientes proveedores tienen fichas de seguridad pendientes de subir a la aplicación pese a haber sido requeridas y reclamadas", $siglasIdiona) . ": " . "</span><br><br>";
            $cuerpoPeticionFDS .= $spanConStilo . " <u> " . $auxiliar->traduce("Num. Proveedores", $siglasIdiona) . ": " . $numProveedoresFicha . "</u>, " . "</span><br><ul>";
        endif;

        //MOSTRAMOS PROVEEDORES  Y FDS DE CADA PROVEEDOR
        while ($rowProveedorFicha = $bd->SigReg($resultProveedoresFichas)):
            //OBTENEMOS EL PROVEEDOR
            $rowProveedor = $bd->VerReg("PROVEEDOR", "ID_PROVEEDOR", $rowProveedorFicha->ID_PROVEEDOR, "No");

            //PINTAMOS EL PROVEEDOR
            $cuerpoPeticionFDS .= "<li>" . $spanConStilo . ucfirst( (string)$auxiliar->traduce("Proveedor", $siglasIdiona)) . " " . $rowProveedor->REFERENCIA . " - " . $rowProveedor->NOMBRE . " " . $rowProveedor->EMAIL . "</span><br> ";

            //CONSULTAMOS LAS FDS DEL PROVEEDOR
            $sqlFichaProveedor    = "SELECT IFS.ID_MATERIAL, IFS.ID_INCIDENCIA_FICHA_SEGURIDAD_MATERIAL_IDIOMA, IFS.ID_IDIOMA
                                    FROM INCIDENCIA_FICHA_SEGURIDAD_MATERIAL_IDIOMA IFS
                                      INNER JOIN MATERIAL_ALMACEN MA ON MA.ID_ALMACEN = IFS.ID_ALMACEN AND MA.ID_MATERIAL = IFS.ID_MATERIAL
                                      INNER JOIN PROVEEDOR P ON P.ID_PROVEEDOR = IFS.ID_PROVEEDOR
                                    WHERE IFS.ID_PROVEEDOR IS NOT NULL
                                    AND IFS.BAJA = 0
                                    AND IFS.ANULADO_MANUALMENTE = 0
                                      AND IFS.ESTADO_COMUNICACION_PROVEEDOR = 'Comunicada a Proveedor-Pdte. Respuesta'
                                      AND (IFS.RECLAMADA = 'Reclamada a Proveedor' OR IFS.RECLAMADA = 'Reclamada a Comprador')
                                      AND IFS.ESTADO_INCIDENCIA = 'No Resuelta'
                                      AND ID_PLANIFICADOR = $rowPlanificador->ID_PLANIFICADOR
                                      AND IFS.ID_PROVEEDOR = $rowProveedor->ID_PROVEEDOR ";
            $resultFichaProveedor = $bd->ExecSQL($sqlFichaProveedor);

            //CREAMOS CUERPO PARA CADA PROVEEDOR
            $cuerpoPeticionFDS .= "<ul>";
            while ($rowProveedorFicha = $bd->SigReg($resultFichaProveedor)):
                //OBTENEMOS MATERIAL
                $rowMaterial = $bd->VerReg("MATERIAL", "ID_MATERIAL", $rowProveedorFicha->ID_MATERIAL, "No");

                //MATERIAL PROVEEDOR PARA REFERENCIA
                $GLOBALS["NotificaErrorPorEmail"] = "No";
                $rowMaterialProveedor             = $bd->VerRegRest("MATERIAL_PROVEEDOR", "ID_MATERIAL = $rowMaterial->ID_MATERIAL AND ID_PROVEEDOR = $rowProveedor->ID_PROVEEDOR", "No");

                //IDIOMA PAIS
                $rowIdiomaPais = $bd->VerReg("IDIOMA", "ID_IDIOMA", $rowProveedorFicha->ID_IDIOMA, "No");
                //OBTENER PEDIDO Y POSICION DE PEDIDO
                $sqlPedido                        = "SELECT PE.PEDIDO_SAP,PEL.LINEA_PEDIDO_SAP FROM PEDIDO_ENTRADA PE
                                    INNER JOIN PEDIDO_ENTRADA_LINEA PEL ON PEL.ID_PEDIDO_ENTRADA = PE.ID_PEDIDO_ENTRADA
                                    WHERE (TIPO_PEDIDO = 'Compra' OR TIPO_PEDIDO = 'Compra SGA Manual') AND FECHA_CREACION =
                                            ( SELECT MAX(FECHA_CREACION) FROM PEDIDO_ENTRADA PE2
                                                INNER JOIN PEDIDO_ENTRADA_LINEA PEL2   ON PEL2.ID_PEDIDO_ENTRADA = PE2.ID_PEDIDO_ENTRADA
                                                WHERE PEL2.ID_MATERIAL =" . $rowMaterial->ID_MATERIAL . "
                                            )
				              AND PE.ID_PROVEEDOR =" . $rowProveedor->ID_PROVEEDOR . "
                                    AND PEL.ID_MATERIAL = " . $rowMaterial->ID_MATERIAL;
                $GLOBALS["NotificaErrorPorEmail"] = "No";
                $resultPedido                     = $bd->ExecSQL($sqlPedido, "No");

                $numPedido      = "";
                $posicionPedido = "";
                if ($bd->NumRegs($resultPedido) > 0):
                    $rowPedido      = $bd->SigReg($resultPedido);
                    $numPedido      = $rowPedido->PEDIDO_SAP;
                    $posicionPedido = $rowPedido->LINEA_PEDIDO_SAP;
                endif;


                //CUERPO PARA CADA METERIAL
//                    $cuerpoPeticionFDS .= $this->obtenerCuerpoHTMLMaterial($rowMaterial, $rowMaterialProveedor, $rowIdiomaPais, $siglasIdiona, $numPedido, $posicionPedido) . "<br><br>";
                $cuerpoPeticionFDS .= "<li>" . $spanConStilo . "<u>" . $auxiliar->traduce("Ref. Acciona Material", $siglasIdiona) . " </u>: " . $rowMaterial->REFERENCIA . " - " . ($siglasIdiona == "ESP" ? $rowMaterial->DESCRIPCION : $rowMaterial->DESCRIPCION) . "</span><br>"
                    . $spanConStilo . "<u>" . $auxiliar->traduce("Ref. Proveedor Material", $siglasIdiona) . " </u> : " . $rowMaterialProveedor->REF_MATERIAL_PROVEEDOR . "</span><br>"
                    . $spanConStilo . "<u>" . $auxiliar->traduce("Ultimo pedido", $siglasIdiona) . "</u> : " . $numPedido . "</span><br>"
                    . $spanConStilo . "<u>" . $auxiliar->traduce("Nº Linea", $siglasIdiona) . "</u> : " . $posicionPedido . "</span><br></li>";

                //ESTABLECEMOS LA FICHA A COMUNICADA A COMPRADOR
                $sqlUpdateFdS = "UPDATE INCIDENCIA_FICHA_SEGURIDAD_MATERIAL_IDIOMA SET
                          RECLAMADA = 'Reclamada a Comprador'
                          WHERE ID_INCIDENCIA_FICHA_SEGURIDAD_MATERIAL_IDIOMA = $rowProveedorFicha->ID_INCIDENCIA_FICHA_SEGURIDAD_MATERIAL_IDIOMA";
                $bd->ExecSQL($sqlUpdateFdS);

                //GUARDAMOS TODAS LAS INCIDENCIAS QUE SE VAN A RECLAMAR POR PRIMERA VEZ
                $arrIncidenciasFdSAReclamar[] = $rowProveedorFicha->ID_INCIDENCIA_FICHA_SEGURIDAD_MATERIAL_IDIOMA;
                //GRABO LAS OBSERVACIONES EN OBSERVACIONES SISTEMA
                $observaciones_sistema->Grabar("INCIDENCIA_FICHA_SEGURIDAD_MATERIAL", $rowProveedorFicha->ID_INCIDENCIA_FICHA_SEGURIDAD_MATERIAL_IDIOMA, "Reclamacion FdS a Comprador");
            endwhile;
            $cuerpoPeticionFDS .= "</ul>";
        endwhile;

        //CERRAMOS LISTA
        if ($cuerpoPeticionFDS != ""):
            $cuerpoPeticionFDS .= "</ul>";
        endif;

        //ESTABLECEMOS INICIO DEL CORREO
        //SI HAY VALORES ENVIAMOS
        if ($cuerpoPeticionCorreo != "" || $cuerpoPeticionFDS != ""):
            $cuerpo .= $spanConStilo . $auxiliar->traduce("Buenos dias,", $siglasIdiona) . " </span><br>";

            //CREAMOS LOS DATOS DEL CUERPO
            $cuerpo .= $cuerpoPeticionCorreo . $cuerpoPeticionFDS;

            //GENERAMOS EL ENLACE CON FILTRO PARA PLANIFICADOR
            $cuerpo .= "<a target='_blank' href='"
                . $url_web . "problemas/control_fichas_seguridad/index.php?idPlanificador=" . $rowPlanificador->ID_PLANIFICADOR . "&txPlanificador=" . $rowPlanificador->USUARIO_SAP . "&selEstado=" . urlencode("Comunicada a Proveedor-Pdte. Respuesta") . "&selReclamadaProveedor=" . urlencode("Reclamada a Comprador") . "&selEstadoIncidencia=" . urlencode("No Resuelta") . "'>" . $auxiliar->traduce("Pulse aqui para ver relacion fichas pendientes", $siglasIdiona) . "</a><br><br>";
            echo $cuerpo;
            //DECLARAMOS EL ARRAY DE DESTINATARIO
            $arrDest                   = array();
            $arrDest['PLANIFICADOR'][] = $rowPlanificador->ID_PLANIFICADOR;

//        CREAMOS EL AVISO PARA CADA FICHA DE SEGURIDAD QUE HA SIDO RECLAMADA
//        PARA QUE QUEDE VISIBLE EN LA TRAZA DE LA FICHA DE LA INCIDENCIA (FICHA DE CORREOS ENVIADOS)
            foreach ($arrIncidenciasFdSAReclamar as $idFichaSeguridad):
                $idAviso = $this->GuardarAviso($idFichaSeguridad, "FICHA_SEGURIDAD_MATERIAL", $asunto, $cuerpo, $arrDest);
                //MARCAMOS EL AVISO COMO ENVIADO
                $sqlUpdate = " UPDATE AVISO SET
                               ENVIADO = 1
                              ,FECHA_ULTIMO_ENVIO = '" . date("Y-m-d H:i:s") . "'
                              WHERE ID_AVISO = $idAviso";
                $bd->ExecSQL($sqlUpdate);
            endforeach;
//
//        //SOLO ENVIAMOS UN AVISO (SON TODOS IGUALES)
////        ENVIAMOS EL AVISOS
            if ($idAviso != ""):
                $this->enviarAviso($idAviso);
            endif;
        endif;


    }


} // FIN CLASE AVISO