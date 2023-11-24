<?php

# cliente
# Clase cliente contiene todas las funciones necesarias para
# la interaccion con la clase cliente
# Se incluira en las sesiones
# Julio 2007 Carlos Arnáez

class cliente
{
    var $ID_CLIENTE;
    var $ID_EMPLEADO;
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

    // COMPRUEBA SI EL CLIENTE YA EXISTE (MISMA REFERENCIA)
    function Comprobar_Existente($txReferencia)
    {

        global $bd;

        $ValorDevolver = "No existente";
        $sql           = "SELECT COUNT(ID_CLIENTE) as NUM_REGS FROM CLIENTE WHERE REFERENCIA='$txReferencia'";
        $resultNumero  = $bd->ExecSQL($sql);
        $rowNumero     = $bd->SigReg($resultNumero);
        if ($rowNumero->NUM_REGS > 0) $ValorDevolver = "Existente";

        return $ValorDevolver;
    }

    // COMPRUEBA SI EL CLIENTE YA EXISTE (MISMA REFERENCIA)
    function Comprobar_Existente_Nombre($txNombre)
    {

        global $bd;

        $ValorDevolver = "No existente";
        $sql           = "SELECT COUNT(ID_CLIENTE) as NUM_REGS FROM CLIENTE WHERE NOMBRE='$txNombre'";
        $resultNumero  = $bd->ExecSQL($sql);
        $rowNumero     = $bd->SigReg($resultNumero);
        if ($rowNumero->NUM_REGS > 0) $ValorDevolver = "Existente";

        return $ValorDevolver;
    }

    // COMPRUEBA SI EL CLIENTE YA EXISTE EN LA MODIFICACION
    function Comprobar_Existente_Modif($txReferencia, $IdCliente)
    {

        global $bd;

        $ValorDevolver = "No existente";
        $sql           = "SELECT COUNT(ID_CLIENTE) as NUM_REGS FROM CLIENTE WHERE REFERENCIA='$txReferencia' AND ID_CLIENTE<>$IdCliente";
        $resultNumero  = $bd->ExecSQL($sql);
        $rowNumero     = $bd->SigReg($resultNumero);
        if ($rowNumero->NUM_REGS > 0) $ValorDevolver = "Existente";

        return $ValorDevolver;
    }

    // COMPRUEBA SI EL CLIENTE YA EXISTE (MISMA REFERENCIA) Y NO ESTA DADO DE BAJA
    function Comprobar_Existente_Activo($txReferencia)
    {

        global $bd;

        $ValorDevolver = "No existente";
        $sql           = "SELECT COUNT(ID_CLIENTE) as NUM_REGS FROM CLIENTE WHERE REFERENCIA='$txReferencia' AND BAJA=0";
        $resultNumero  = $bd->ExecSQL($sql);
        $rowNumero     = $bd->SigReg($resultNumero);
        if ($rowNumero->NUM_REGS > 0) $ValorDevolver = "Existente";

        return $ValorDevolver;
    }

    function Grabar_Entrada_OK($row)
    {
        // ALMACENO LOS DATOS DE ENTRADA DEL USUARIO
        global $bd;
        global $auxiliar;
        global $esAdministrador;

        // GRABO LOS DATOS DEL USUARIO EN LA SESION
        $this->ID_CLIENTE = $row->ID_CLIENTE;
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
        $sql          = "UPDATE CLIENTE SET INTENTOS_USER=0,ULTIMA_FECHA='$FechaEntrada',ULTIMA_IP='$IpEntrada' WHERE ID_CLIENTE='$this->ID_CLIENTE'";
        $bd->ExecSQL($sql);
    } // Fin Grabar_Entrada_OK


    function Insertar_Mov_Gral($idTipoMov, $descMov)
    {
        global $bd;

        // INSERTO LA LINEA DEL MOVIMIENTO
        $fechaAct = date("Y-m-d H:i:s");
        $sql      = "INSERT INTO MOVIMIENTO_CLIENTE (FECHA,ID_TIPO_MOVIMIENTO,DESCRIPCION,ID_CLIENTE) VALUES ('$fechaAct',$idTipoMov,'$descMov','$this->ID_CLIENTE')";
        $bd->ExecSQL($sql);
    } // Fin Insertar_Mov_Gral

    function Insertar_Alerta_Gral($idTipoAlert, $descAlert)
    {
        global $bd;

        // INSERTO LA ALERTA
        $fechaAlerta = date("Y-m-d H:i:s");
        $sql         = "INSERT INTO ALERTA_CLIENTE (ID_ALERTA_TIPO,ID_CLIENTE,FECHA_GENERACION,DESCRIPCION) VALUES($idTipoAlert,'$idCli','$fechaAlerta','$descAlert')";
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


    function Hayar_Id($idCli)
    {
        // HAYA EL LOGIN DEL CLIENTE QUE ES IDENTIFICABLE UNIVOCAMENTE
        global $bd;

        $sql    = "SELECT ID_CLIENTE FROM CLIENTE WHERE ID_CLIENTE='$idCli'";
        $result = $bd->ExecSQL($sql);
        if ($bd->NumRegs($result) > 0):
            $row = $bd->SigReg($result);

            return $row->ID_CLIENTE;
        else:
            return -1; // VALOR INEXISTENTE
        endif;

    } // Fin Hayar_Id

    function Hayar_Id_Ref($refCli)
    {
        // HAYA EL LOGIN DEL CLIENTE QUE ES IDENTIFICABLE UNIVOCAMENTE
        global $bd;

        $sql    = "SELECT ID_CLIENTE FROM CLIENTE WHERE REFERENCIA='$refCli'";
        $result = $bd->ExecSQL($sql);
        if ($bd->NumRegs($result) > 0):
            $row = $bd->SigReg($result);

            return $row->ID_CLIENTE;
        else:
            return -1; // VALOR INEXISTENTE
        endif;

    } // Fin Hayar_Id

    function Hayar_Cliente($idCli)
    {
        // HAYA EL LOGIN DEL CLIENTE QUE ES IDENTIFICABLE UNIVOCAMENTE
        global $bd;

        $sql    = "SELECT * FROM CLIENTE WHERE ID_CLIENTE='$idCli'";
        $result = $bd->ExecSQL($sql);
        if ($bd->NumRegs($result) > 0):
            $row = $bd->SigReg($result);

            return $row;
        else:
            return -1; // VALOR INEXISTENTE
        endif;

    } // Fin Hayar_Id

    function Hayar_Cliente_Ref($refCli)
    {
        // HAYA EL LOGIN DEL CLIENTE A PARTIR DE SU REFERENCIA QUE ES IDENTIFICABLE UNIVOCAMENTE
        global $bd;

        $sql    = "SELECT * FROM CLIENTE WHERE REFERENCIA='$refCli'";
        $result = $bd->ExecSQL($sql);
        if ($bd->NumRegs($result) > 0):
            $row = $bd->SigReg($result);

            return $row;
        else:
            return -1; // VALOR INEXISTENTE
        endif;

    }


    function Hayar_Nombre($idCliente)
    {
        global $bd;
        global $NotificaErrorPorEmail;

        // HAYO EL CLIENTE
        $Cli = $bd->VerReg("CLIENTE", "ID_CLIENTE", $idCliente, "No");
        if ($Cli == false) return "Inexistente";
        else return $Cli->NOMBRE;
    }

    function Incrementar_Intentos($IdCli)
    {
        // INCREMENTA EL Nº DE INTENTOS ERRONEOS DE ACCESO DE EL CLIENTE Y DEVUELVE
        // SI ESTE Nº ES MAYOR O MENOR A 10
        global $bd;

        $sql = "UPDATE CLIENTE SET INTENTOS_USER=INTENTOS_USER+1 WHERE ID_CLIENTE='$IdCli'";
        $bd->ExecSQL($sql);

        $sql            = "SELECT INTENTOS_USER FROM CLIENTE WHERE ID_CLIENTE='$IdCli'";
        $resultIntentos = $bd->ExecSQL($sql);
        if ($bd->NumRegs($resultIntentos) > 0):
            $rowIntentos = $bd->SigReg($resultIntentos);
            if ($rowIntentos->INTENTOS_USER >= $this->MAX_INTENTOS):
                return "MaximoIntentosAlcanzado";
            endif;
        endif;

        return "MenorMaximoIntentos";

    } // Fin Incrementar_Intentos

    function Generar_Email_Bloqueo($rowCli)
    {
        // EMAIL AL CLIENTE INDICANDOLE QUE SE LE HA BLOQUEADO LA CUENTA
        global $bd;

        $Asunto = "Alerta Herramienta Web Aprovisionamiento Caja Navarra";
        $Cuerpo = "El número de intentos erroneos permitidos con su código ha sido superado.<br>Su cuenta ha sido bloqueada temporalmente por razones de seguridad.<br><br>$bd->msje_contacte";
        $bd->EnviarEmail($rowCli->EMAIL, $Asunto, $Cuerpo, "Html");

    } // Fin Generar_Alerta_Bloqueo

} // FIN CLASE
