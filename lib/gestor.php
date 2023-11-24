<?php

# gestor
# Clase gestor contiene todas las funciones necesarias para
# la interaccion con la clase gestor
# Se incluira en las sesiones
# Julio 2007 Carlos Arnáez

class gestor
{
    var $ID_GESTOR;
    var $REFERENCIA;
    var $NOMBRE;
    var $EMAIL;
    var $LOGIN;
    var $ULTIMA_IP;
    var $ULTIMA_FECHA;
    var $ULTIMA_HORA;
    var $MAX_INTENTOS;
    var $ultimaBusqueda;

    function __construct($actualizar = true)
    {
        if ($actualizar) $this->Actualizar_Max_Intentos();
    } // Fin proveedor


    function Actualizar_Max_Intentos()
    {
        global $bd;
        $this->MAX_INTENTOS = 8;
    }

    function Grabar_Entrada_OK($row)
    {
        // ALMACENO LOS DATOS DE ENTRADA DEL USUARIO
        global $bd;
        global $auxiliar;
        global $esAdministrador;

        // GRABO LOS DATOS DEL USUARIO EN LA SESION
        $this->ID_GESTOR  = $row->ID_GESTOR;
        $this->REFERENCIA = $row->REFERENCIA;
        $this->NOMBRE     = $row->NOMBRE;
        $this->LOGIN      = $row->LOGIN;

        $this->ULTIMA_IP    = $row->ULTIMA_IP;
        $this->ULTIMA_FECHA = $row->ftoULTIMA_FECHA;
        $this->ULTIMA_HORA  = $row->ftoULTIMA_HORA;
        $this->EMAIL        = $row->EMAIL;

        // ALMACENO EN BD LOS DATOS DE LA ULTIMA ENTRADA DEL USUARIO
        $FechaEntrada = date("Y-m-d H:i:s");
        $IpEntrada    = $auxiliar->Hayar_IP();
        $sql          = "UPDATE GESTOR SET INTENTOS_USER=0,ULTIMA_FECHA='$FechaEntrada',ULTIMA_IP='$IpEntrada' WHERE ID_GESTOR='$this->ID_GESTOR'";
        $bd->ExecSQL($sql);
    } // Fin Grabar_Entrada_OK


    function Insertar_Mov_Gral($idTipoMov, $descMov)
    {
        global $bd;

        // INSERTO LA LINEA DEL MOVIMIENTO
        $fechaAct = date("Y-m-d H:i:s");
        $sql      = "INSERT INTO MOVIMIENTO_GESTOR (FECHA,ID_TIPO_MOVIMIENTO,DESCRIPCION,ID_GESTOR) VALUES ('$fechaAct',$idTipoMov,'$descMov','$this->ID_GESTOR')";
        $bd->ExecSQL($sql);
    } // Fin Insertar_Mov_Gral

    function Insertar_Alerta_Gral($idTipoAlert, $descAlert, $idGes)
    {
        global $bd;

        // INSERTO LA ALERTA
        $fechaAlerta = date("Y-m-d H:i:s");
        $sql         = "INSERT INTO ALERTA_GESTOR (ID_ALERTA_TIPO,ID_GESTOR,FECHA_GENERACION,DESCRIPCION) VALUES($idTipoAlert,'$idGes','$fechaAlerta','$descAlert')";
        $bd->ExecSQL($sql);

    } // Fin Insertar_Mov

    function Insertar_Alerta_Bloqueo($idTipoAlert, $descAlert, $idCli)
    {
        global $bd;

        // INSERTO LA LINEA DEL MOVIMIENTO
        $fechaAlerta = date("Y-m-d H:i:s");
        $sql         = "INSERT INTO ALERTA_ADM (ID_ALERTA_TIPO,ID_CLIENTE,FECHA_GENERACION,DESCRIPCION) VALUES($idTipoAlert,'$idCli','$fechaAlerta','$descAlert')";
        $bd->ExecSQL($sql);

    } // Fin Insertar_Alerta_Bloqueo


    function Hayar_Id($idGes)
    {
        // HAYA EL LOGIN DEL CLIENTE QUE ES IDENTIFICABLE UNIVOCAMENTE
        global $bd;

        $sql    = "SELECT ID_GESTOR FROM GESTOR WHERE ID_GESTOR='$idGes'";
        $result = $bd->ExecSQL($sql);
        if ($bd->NumRegs($result) > 0):
            $row = $bd->SigReg($result);

            return $row->ID_GESTOR;
        else:
            return -1; // VALOR INEXISTENTE
        endif;

    } // Fin Hayar_Id


    function Hayar_Nombre($idGestor)
    {
        global $bd;
        global $NotificaErrorPorEmail;

        // HAYO EL PROVEEDOR DESTINO
        $Ges = $bd->VerReg("GESTOR", "ID_GESTOR", $idGestor, "No");

        return $Ges->NOMBRE;
    }

    function Incrementar_Intentos($IdGes)
    {
        // INCREMENTA EL Nº DE INTENTOS ERRONEOS DE ACCESO DE EL CLIENTE Y DEVUELVE
        // SI ESTE Nº ES MAYOR O MENOR A 10
        global $bd;

        $sql = "UPDATE GESTOR SET INTENTOS_USER=INTENTOS_USER+1 WHERE ID_GESTOR='$IdGes'";
        $bd->ExecSQL($sql);

        $sql            = "SELECT INTENTOS_USER FROM GESTOR WHERE ID_GESTOR='$IdGes'";
        $resultIntentos = $bd->ExecSQL($sql);
        if ($bd->NumRegs($resultIntentos) > 0):
            $rowIntentos = $bd->SigReg($resultIntentos);
            if ($rowIntentos->INTENTOS_USER >= $this->MAX_INTENTOS):
                return "MaximoIntentosAlcanzado";
            endif;
        endif;

        return "MenorMaximoIntentos";

    } // Fin Incrementar_Intentos

    function Existe_Gestor($referenciaGestor, $idGestor = 0)
    {
        global $bd;

        if ($idGestor != 0):
            $sql          = "SELECT ID_GESTOR FROM GESTOR WHERE REFERENCIA='$referenciaGestor' AND ID_GESTOR<>$idGestor";
            $resultGestor = $bd->ExecSQL($sql);
            if ($bd->NumRegs($resultGestor) > 0):
                return true;
            else:
                return false;
            endif;
        else:
            $sql          = "SELECT ID_GESTOR FROM GESTOR WHERE REFERENCIA='$referenciaGestor' ";
            $resultGestor = $bd->ExecSQL($sql);
            if ($bd->NumRegs($resultGestor) > 0):
                return true;
            else:
                return false;
            endif;

        endif;


    } // Fin Incrementar_Intentos

    function Generar_Email_Bloqueo($rowProv)
    {
        // EMAIL AL CLIENTE INDICANDOLE QUE SE LE HA BLOQUEADO LA CUENTA
        global $bd;
        global $auxiliar;
        global $administrador;

        $Asunto = $auxiliar->traduce("Alerta Herramienta Web Aprovisionamiento", $administrador->ID_IDIOMA);
        $Cuerpo = $auxiliar->traduce("El número de intentos erroneos permitidos con su código ha sido superado", $administrador->ID_IDIOMA) . ".<br>" . $auxiliar->traduce("Su cuenta ha sido bloqueada temporalmente por razones de seguridad", $administrador->ID_IDIOMA) . ".<br><br>$bd->msje_contacte";
        $bd->EnviarEmail($rowProv->EMAIL, $Asunto, $Cuerpo, "Html");

    } // Fin Generar_Alerta_Bloqueo

} // FIN CLASE
