<?php
# basedatos
# Clase basedatos contiene todas las funciones necesarias para
# la interaccion con las bases de datos
# No incluir nunca en las sesiones
# Octubre 2005 Ruben Alutiz Duarte

// DATOS DE ACCESO DE LA BASE DE DATOS
require_once "globales.php";
require_once "logger.php";
require_once "class.phpmailer.php";
require_once "auxiliar.php";

session_name(NOMBRE_SESSION); //ahora el nombre lo indicamos en globales

// INICIO REAL DE LA CLASE BASE DE DATOS
class basedatos
{
    var $aplicacion;

    var $email_remitente;
    var $nombre_remitente;
    var $email_error;
    var $email_recep_multifase;
    var $msje_error;

    var $nombre;
    var $titleProv;
    var $clave_aplicacion;
    var $empresa;

    var $lanzarExcepcion;
    var $conexion;

    function __construct()
    {
        global $nombrebd;

        $this->nombre     = $nombrebd;
        $this->aplicacion = "";

        $this->titleProv = TITULO_ENTORNO;

        $this->clave_aplicacion = CLAVE_ENTORNO; // PARA EL ASUNTO EMAILS ERROR
        $this->empresa          = EMPRESA_ENTORNO;

        $this->email_remitente  = OUTLOOK_USER;
        $this->nombre_remitente = REMITENTE_MAILS;

        $this->email_remitente_alertas = OUTLOOK_USER;
        $this->email_error             = EQUIPO_IR_CORREO;
        $this->msje_error              = "<br>Se detecto un error accediendo a datos del sistema.<br>Si el problema persiste contacte con nosotros en el número <b>948.316.082</b> de lunes a Viernes de 8:30 a 14:00 y de 15:00 a 17:00<br>";
        $this->msje_contacte           = "<br>Por favor contacte con nosotros en el número <b>948.316.082</b> de lunes a Viernes de 8:30 a 14:00 y de 15:00 a 17:00<br>";
    } // FIN basedatos


    function begin_transaction()
    {
        mysqli_query($this->conexion, "SET AUTOCOMMIT=0;");
        mysqli_query($this->conexion, "START TRANSACTION;");//"BEGIN;"
    }

    function commit_transaction()
    {
        mysqli_query($this->conexion, "COMMIT;");
        mysqli_query($this->conexion, "SET AUTOCOMMIT=1;");

    }

    function rollback_transaction()
    {
        mysqli_query($this->conexion, "ROLLBACK;");
        mysqli_query($this->conexion, "SET AUTOCOMMIT=1;");
    }

    //CUANDO EL SEMAFORO DE LAS INTERFACES MANTIENE BLOQUEADA VARIAS TRANSACCIONES Y SON SOBRE EL MISMO OBJETO, AL LIBERARSE EL SEMAFORO POR DEFECTO SE LEE LA BASE DE DATOS SEGUN ESTABA AL INICIAR LA TRANSACCION, POR LO QUE NO LEE LO QUE LAS LLAMADAS ANTERIORES HAN MODIFICADO, CON ESTE NIVEL DE LECTURA, PERMITE LEER LO COMMITEADO EN OTRAS TRANSACCIONES
    function set_isolation_level_committed()
    {
        mysqli_query($this->conexion, "SET TRANSACTION ISOLATION LEVEL READ COMMITTED;");// ESTABLECE A QUE DATOS PUEDE ACCEDER LA TRANSACCION CUANDO OTRAS TRANSACCIONES CORREN CONCURRENTEMENTE
    }

    function set_isolation_level_uncommitted()
    {
        mysqli_query($this->conexion, "SET TRANSACTION ISOLATION LEVEL READ UNCOMMITTED;");// ESTABLECE A QUE DATOS PUEDE ACCEDER LA TRANSACCION CUANDO OTRAS TRANSACCIONES CORREN CONCURRENTEMENTE
        mysqli_query($this->conexion, "SET transaction_read_only = ON;");// ESTABLECE EL MODO DE ACCESO DE LA TRANSACCION
    }

    function set_isolation_level_default()
    {
        mysqli_query($this->conexion, "SET TRANSACTION ISOLATION LEVEL REPEATABLE-READ;");// ESTABLECE LOS DATOS POR DEFECTO
        mysqli_query($this->conexion, "SET transaction_read_only = OFF;");// ESTABLECE EL MODO DE ACCESO DE LA TRANSACCION
    }

    function conectar()
    {
        global $host;
        global $usuario;
        global $password;

        mysqli_report(MYSQLI_REPORT_OFF);
        $this->conexion = mysqli_connect($host, $usuario, $password);

        //SI NO TENEMOS CONEXION
        if (mysqli_connect_error()):
            $cuerpoBTS = "Connect ErrNo: " . print_r(mysqli_connect_errno(), true) .
                "\nConnect Error: " . print_r(mysqli_connect_error(), true) .
                "\nErrNo: " . print_r(mysqli_errno($this->conexion), true) .
                "\nError: " . print_r(mysqli_error($this->conexion), true) .
                "\nHost: " . print_r($host, true) .
                "\nUsuario: " . print_r($usuario, true) .
                "\nPass: XXXXX";
            $this->registrarBTS("Error ExecSql\n" . $cuerpoBTS, 'Error SQL');

            return false;
        endif;

        $bd_seleccionada = mysqli_select_db($this->conexion, $this->nombre);

        return $bd_seleccionada;

    } // FIN Conectar

    function desconectar()
    {
        mysqli_close($this->conexion);
    } // FIN Desconectar

    // EJECUTA LA SQL, SI ERROR EMAIL Y ABRE UNA PAGINA DE ERROR, DEVUELVE EL result
    function ExecSQL($Clausula_Sql, $AbrirPagError = "Si")
    {
        global $Pagina_Error;
        global $host;
        global $usuario;
        global $password;

        if (!isset($Pagina_Error)) $Pagina_Error = "error.php";

        $resultado = mysqli_query($this->conexion, $Clausula_Sql);

        if ($resultado == false):
            //echo "--$Clausula_Sql--";
            if ($this->lanzarExcepcion == "Si"):
                throw new Exception("ErrorEjecutarSql::$Clausula_Sql" . mysqli_error($this->conexion));
            endif;


            if ($AbrirPagError == "Si"):
                // INFORMO DEL ERROR AL RESPONSABLE INFORMATICO
                $asunto = "Error ExecSql";
                $cuerpo = "SQL ERROR:\n$Clausula_Sql\n" . $_SERVER['SCRIPT_NAME'];
                $cuerpo .= "\n\n" . $this->obtenerBackTrace() . "\n\n" . mysqli_error($this->conexion);
                $cuerpo .= "\n\nConnect Error: " . print_r(mysqli_connect_error(), true) . "\nHost: " . print_r($host, true) . "\nUsuario: " . print_r($usuario, true) . "\nPass: XXXXX";
                $cuerpo .= "\n\nREQUEST:\n" . print_r($_REQUEST, true) . "\n\nFILES:\n" . print_r($_FILES, true) . "\n\nSERVER:\n" . print_r($_SERVER, true);

                //DOCKER NO TIENE EMAILS
                if (ENTORNO_WEB == 'DOCKER'):
                    echo $cuerpo . "<hr>";
                endif;

                $this->registrarBTS($asunto . "\n" . $cuerpo, 'Error SQL');

                // INFORMO DEL ERROR AL CLIENTE
                $TipoError = "ErrorEjecutarSql";
                include $Pagina_Error;
                exit;
            endif;
        endif;

        return $resultado;
    } // FIN ExecSQL

    // EJECUTA LA SQL, SI ERROR EMAIL SEGURO, Y ABRE UNA PAGINA DE ERROR EN FUNCION DEL BOOLEANO, DEVUELVE EL result
    function ExecSQLConNotificacionEmail($Clausula_Sql, $AbrirPagError = "Si")
    {
        global $Pagina_Error;
        if (!isset($Pagina_Error)) $Pagina_Error = "error.php";
        $resultado = mysqli_query($this->conexion, $Clausula_Sql);

        if ($resultado == false):
            //echo "--$Clausula_Sql--";
            if ($this->lanzarExcepcion == "Si"):
                throw new Exception("ErrorEjecutarSql::$Clausula_Sql");
            endif;
            // INFORMO DEL ERROR AL RESPONSABLE INFORMATICO
            $asunto = "Error ExecSql";
            $cuerpo = "SQL ERROR:\n$Clausula_Sql\n" . $_SERVER['SCRIPT_NAME'];
            $cuerpo .= "\n\n" . $this->obtenerBackTrace() . "\n\n" . mysqli_error($this->conexion);
            $cuerpo .= "\n\nREQUEST:\n" . print_r($_REQUEST, true) . "\n\nFILES:\n" . print_r($_FILES, true) . "\n\nSERVER:\n" . print_r($_SERVER, true);
            $this->registrarBTS($asunto . "\n" . $cuerpo, 'Error SQL');

            //DOCKER NO TIENE EMAILS
            if (ENTORNO_WEB == 'DOCKER'):
                echo $cuerpo . "<hr>";
            endif;

            if ($AbrirPagError == "Si"):
                // INFORMO DEL ERROR AL CLIENTE
                $TipoError = "ErrorEjecutarSql";
                include $Pagina_Error;
                exit;
            endif;
        endif;

        return $resultado;
    } // FIN ExecSQL


    function NumRegsTabla($Tabla, $clausulaWhere, $TipoBloqueoSelect = NULL)
    {

        $sql       = "SELECT count(*) as NUM FROM $Tabla WHERE $clausulaWhere";// . ($TipoBloqueoSelect == NULL?'':" " . $TipoBloqueoSelect);echo($sql . "<hr>");
        $resultado = $this->ExecSQL($sql);
        $row       = $this->SigReg($resultado);

        return $row->NUM;

    } // FIN NumRegsTabla


    // MUESTRA EL REGISTRO RESULTANTES DE UNA CONSULTA, DEVUELVE Pag Err, false O LA row
    function VerReg($Tabla, $Clave, $ValorClave, $AbrirPagError = "Si")
    {
        global $Pagina_Error;
        global $CamposExtra;
        if (!isset($Pagina_Error)) $Pagina_Error = "error.php";
        if (isset($CamposExtra)) $CamposExtra = ", $CamposExtra";

        //SQL
        $sql = "SELECT * $CamposExtra FROM $Tabla WHERE $Clave='$ValorClave'";//echo($sql);

        $resultado = $this->ExecSQL($sql, $AbrirPagError);
        // INFORMO DEL ERROR AL RESPONSABLE INFORMATICO
        if ($resultado == false || $this->NumRegs($resultado) == 0):
            //echo $sql;

            if ($AbrirPagError == "Si"):
                $asunto = "Error obteniendo el registro";
                $cuerpo = "Clave no existente\ntabla : $Tabla, campo : $Clave = $ValorClave\n$sql\n" . $_SERVER['SCRIPT_NAME'];
                $cuerpo .= "\n\n" . $this->obtenerBackTrace();
                $cuerpo .= "\n\nREQUEST:\n" . print_r($_REQUEST, true) . "\n\nFILES:\n" . print_r($_FILES, true) . "\n\nSERVER:\n" . print_r($_SERVER, true);
                $this->registrarBTS($asunto . "\n" . $cuerpo, 'Error SQL');

                //DOCKER NO TIENE EMAILS
                if (ENTORNO_WEB == 'DOCKER'):
                    $cuerpo = "Clave no existente\ntabla : $Tabla, campo : $Clave = $ValorClave\n$sql<br>" . $_SERVER['SCRIPT_NAME'];
                    $cuerpo .= "<br>" . $this->obtenerBackTrace();
                    exit($cuerpo);
                endif;
                // INFORMO DEL ERROR AL CLIENTE
                $TipoError = "NoEncontrado";
                include $Pagina_Error;
                exit;
            endif;

            return false;
        endif;

        return ($this->SigReg($resultado));
    } // FIN VerReg

    // MUESTRA EL REGISTRO RESULTANTES DE UNA CONSULTA, DEVUELVE Pag Err, false O LA row
    function VerRegRest($Tabla, $sqlRest, $AbrirPagError = "Si", $TipoBloqueoSelect = NULL)
    {
        global $Pagina_Error;
        global $CamposExtra;

        if (!isset($Pagina_Error)) $Pagina_Error = "error.php";
        if (isset($CamposExtra)) $CamposExtra = ", $CamposExtra";

        //SQL
        $sql       = "SELECT * $CamposExtra FROM $Tabla WHERE $sqlRest " . ($TipoBloqueoSelect == NULL ? '' : " " . $TipoBloqueoSelect);//echo($sql);
        $resultado = $this->ExecSQL($sql, $AbrirPagError);
        // INFORMO DEL ERROR AL RESPONSABLE INFORMATICO
        if (($resultado == false) || ($this->NumRegs($resultado) == 0))://exit($sql);


            if ($AbrirPagError == "Si"):
                $asunto = "Error obteniendo el registro";
                $cuerpo = "Clave no existente\ntabla : $Tabla, campo : $Clave = $ValorClave\n$sql\n" . $_SERVER['SCRIPT_NAME'];
                $cuerpo .= "\n\n" . $this->obtenerBackTrace();
                $cuerpo .= "\n\nREQUEST:\n" . print_r($_REQUEST, true) . "\n\nFILES:\n" . print_r($_FILES, true) . "\n\nSERVER:\n" . print_r($_SERVER, true);
                $this->registrarBTS($asunto . "\n" . $cuerpo, 'Error SQL');

                //DOCKER NO TIENE EMAILS
                if (ENTORNO_WEB == 'DOCKER'):
                    $cuerpo = "Clave no existente\ntabla : $Tabla, campo : $Clave = $ValorClave\n$sql<br>" . $_SERVER['SCRIPT_NAME'];
                    $cuerpo .= "<br>" . $this->obtenerBackTrace();
                    exit($cuerpo);
                endif;
                // INFORMO DEL ERROR AL CLIENTE
                $TipoError = "NoEncontrado";
                include $Pagina_Error;
                exit;
            endif;

            return false;
        endif;

        return ($this->SigReg($resultado));
    } // FIN VerReg


    function NumRegs($Id_Sql)
    {
        if ($Id_Sql != false):
            return mysqli_num_rows($Id_Sql);
        endif;

        return false;
    } // FIN NumRegs

    function Mover($Id_Sql, $Reg)
    {
        if ($Id_Sql != false):
            if (mysqli_num_rows($Id_Sql) > 0) mysqli_data_seek($Id_Sql, $Reg);
        endif;
    } // FIN mover

    function SigReg($Id_Sql)
    {
        if ($Id_Sql != false):
            return mysqli_fetch_object($Id_Sql);
        endif;

        return false;
    }// FIN SigReg

    function MaxIdTabla($tabla, $clave, $sqlRest = "")
    {

        // CALCULA EL MAXIMO IDENTIFICADOR DE LA TABLA INDICADA DEL CLIENTE ACTUAL
        $sql       = "SELECT max($clave) as maximo FROM $tabla $sqlRest";
        $resultado = $this->ExecSQL($sql);
        $row       = $this->SigReg($resultado);

        return $row->maximo;
    } // MaxIdTabla

    // FUNCION QUE SE ENCARGA DE ENVIAR UN EMAIL AL DESTINATARIO DE ERRORES
    function EnviarEmailErr($Asunto, $Cuerpo, $Tipo = "Plano")
    {
        global $auxiliar;
        if(!isset($auxiliar)) $auxiliar = new Auxiliar();
        $auxiliar->enviarCorreoSistema($Asunto, $Cuerpo, $this->email_remitente, $this->nombre_remitente, $this->email_error, "", 0, false);
    } // FIN EnviarEmail

    // FUNCION QUE SE ENCARGA DE ENVIAR UN EMAIL A UN CORREO ESPECIFICADO MEDIANTE PARAMETRO
    function EnviarEmailAlerta($Destino, $Asunto, $Cuerpo, $Tipo = "Plano")
    {
        global $auxiliar;
        if(!isset($auxiliar)) $auxiliar = new Auxiliar();
        $auxiliar->enviarCorreoSistema($Asunto, $Cuerpo, $this->email_remitente_alertas, $this->nombre_remitente, $Destino, "", 0, $Tipo == "Html");
    } // FIN EnviarEmail

    // FUNCION QUE SE ENCARGA DE ENVIAR UN EMAIL A UN CORREO ESPECIFICADO MEDIANTE PARAMETRO
    function EnviarEmail($Destino, $Asunto, $Cuerpo, $Tipo = "Plano")
    {
        global $auxiliar;
        if(!isset($auxiliar)) $auxiliar = new Auxiliar();
        if (ENTORNO == 'DESARROLLO'):
            $auxiliar->enviarCorreoSistema($Asunto, $Cuerpo, $this->email_remitente_alertas, $this->nombre_remitente, $this->email_error, "", 0, $Tipo == "Html");
        else:
            $auxiliar->enviarCorreoSistema($Asunto, $Cuerpo, $this->email_remitente_alertas, $this->nombre_remitente, $Destino, "", 0, $Tipo == "Html");
        endif;
    } // FIN EnviarEmail

    function MaximoIdTabla($tabla, $clave)
    {
        // CALCULA EL MAXIMO IDENTIFICADOR DE LA TABLA INDICADA
        global $bd;

        $sql       = "SELECT max($clave) as maximo FROM $tabla";
        $resultado = $bd->ExecSQL($sql);
        $row       = $bd->SigReg($resultado);

        return $row->maximo;
    } // MaximoIdTabla

    function MaximoIdTablaRest($tabla, $clave, $sqlRest)
    {
        // CALCULA EL MAXIMO IDENTIFICADOR DE LA TABLA INDICADA RESTRINGIENDO UN VALOR
        // POR EJEMPLO LA MAXIMA PARTE DEL CURSO 11
        global $bd;

        $sql       = "SELECT max($clave) as maximo FROM $tabla WHERE $sqlRest";
        $resultado = $bd->ExecSQL($sql);
        $row       = $bd->SigReg($resultado);

        return $row->maximo;
    } // MaximoIdTabla


    function escapeCondicional($str)
    {

        /* Replace the following line with whatever function you prefer to call to escape a string. */

        return addslashes( (string)$str);
    }


    function obtenerBackTrace()
    {
        //DECLARO LA VARIABLE GLOBAL ADMINISTRADOR
        global $administrador;

        $cuerpo = "BACKTRACE:\n";

        //AÑADO EL USUARIO QUE PROVOCA EL ERROR
        if (!empty($administrador->NOMBRE)):
            $cuerpo = $cuerpo . "USUARIO: " . $administrador->NOMBRE;

            if (!empty($administrador->ID_ADMINISTRADOR)):
                $cuerpo = $cuerpo . " - ID_ADMINISTRADOR: " . $administrador->ID_ADMINISTRADOR . "\n";
            else:
                $cuerpo = $cuerpo . " - ID_ADMINISTRADOR: NO REGISTRADO\n";
            endif;
        elseif (!empty($administrador->ID_ADMINISTRADOR)):
            $cuerpo = $cuerpo . "USUARIO: NO REGISTRADO - ID_ADMINISTRADOR: " . $administrador->ID_ADMINISTRADOR . "\n";
        else:
            $cuerpo = $cuerpo . "USUARIO: NO REGISTRADO\n";
        endif;

        //OBTENGO EL BACKTRACE
        $arrBacktrace = debug_backtrace();

        //ELIMINO LA LLAMADA A ESTA FUNCION ( obtenerBackTrace() )
        array_shift($arrBacktrace);

        //LE DOY LA VUELTA PARA IR EN ORDEN CRONOLOGICO DIRECTO
        $arrBacktrace = array_reverse($arrBacktrace);

        //FORMATEO EL BACKTRACE
        $i = 0;
        foreach ($arrBacktrace as $entry):
            $cuerpo .= "\n#$i";
            $cuerpo .= "\n  File: " . $entry['file'] . " (Line: " . $entry['line'] . ")";
            $cuerpo .= "\n  Llamada:" . $entry['class'] . $entry['type'] . $entry['function'];

            if (!empty($entry['args']) && is_array($entry['args'])):
                $cuerpo .= "(" . print_r($entry['args'], true) . ")";
            else:
                $cuerpo .= "()";
            endif;

            $i++;
        endforeach;

        return $cuerpo;
    }

    /**
     * FUNCION PARA LA BUSQUEDA EN DESPLEGABLES CON VARIAS SELECCIONES POR UN SOLO CAMPO
     * @param $txBusqueda TEXTO A BUSCAR
     * @param $campoBD CAMPO PARA LA BUSQUEDA
     * @param string $sqlOR CONDICION APARTE PARA CASOS ESPECIALES
     * @return string DEVUELVE DONDICION PARA LA CONSULTA
     */
    function busquedaTextoDesplegableSeparador($txBusqueda, $campoBD, $sqlOR = "")
    {

        $cad         = $txBusqueda;
        $arrBusqueda = explode(SEPARADOR_BUSQUEDA_MULTIPLE, (string)$cad);
        //VARIABLE PARA SABER SI YA SE HA AÑADIDO ALGO A LA SQL A DEVOLVER
        $filtroRelleno = false;
        $sqlTexto      = " AND (";
        //VARIABLE PARA SABER SI YA SE HA AÑADIDO ALGO A LA SQL A DEVOLVER
        $filtroRelleno = false;
        foreach ($arrBusqueda as $elemento):
            if ($filtroRelleno == true):
                $sqlTexto = $sqlTexto . " OR ";
            endif;
            //SI ELEMENTO ES NULL CAMBIAMOS CONSULTA
            if ($elemento == ELEMENTO_BUSQUEDA_VACIO_VALUE):
                $sqlTexto = "$sqlTexto  (" . $campoBD . " IS NULL  OR " . $campoBD . " = '')";
            else:
                $sqlTexto = "$sqlTexto  (" . $campoBD . " = '" . $this->escapeCondicional($elemento) . "')";
            endif;
            //ACTUALIZO LA VARIABLE DE FILTRO RELLENO
            $filtroRelleno = true;
        endforeach;
        $sqlTexto = "$sqlTexto $sqlOR)";

        return ($sqlTexto);
    }

    /**
     * FUNCION PARA LA BUSQUEDA EN DESPLEGABLES CON VARIAS SELECCIONES POR UN SOLO CAMPO PARA CAMPOS DE TIPO SET
     * @param $txBusqueda TEXTO A BUSCAR
     * @param $campoBD CAMPO PARA LA BUSQUEDA
     * @param string $sqlOR CONDICION APARTE PARA CASOS ESPECIALES
     * @return string DEVUELVE DONDICION PARA LA CONSULTA
     */
    function busquedaTextoTipoSET($txBusqueda, $campoBD, $sqlOR = "")
    {

        $cad         = $txBusqueda;
        $arrBusqueda = explode(SEPARADOR_BUSQUEDA_MULTIPLE, (string)$cad);

        //VARIABLE PARA SABER SI YA SE HA AÑADIDO ALGO A LA SQL A DEVOLVER
        $filtroRelleno = false;
        $sqlTexto      = " AND (";

        foreach ($arrBusqueda as $elemento):
            if ($filtroRelleno == true):
                $sqlTexto = $sqlTexto . " OR ";
            endif;
            //SI ELEMENTO ES NULL CAMBIAMOS CONSULTA
            if ($elemento == ELEMENTO_BUSQUEDA_VACIO_VALUE):
                $sqlTexto = "$sqlTexto  (" . $campoBD . " IS NULL  OR " . $campoBD . " = '')";
            else:
                $sqlTexto = "$sqlTexto  (FIND_IN_SET('" . $this->escapeCondicional($elemento) . "'," . $campoBD . "))";
            endif;
            //ACTUALIZO LA VARIABLE DE FILTRO RELLENO
            $filtroRelleno = true;
        endforeach;

        $sqlTexto = "$sqlTexto $sqlOR)";

        return ($sqlTexto);
    }

    /**
     * FUNCION PARA LA BUSQUEDA EN DESPLEGABLES CON VARIAS SELECCIONES POR UN SOLO CAMPO
     * @param $txBusqueda TEXTO A BUSCAR
     * @param $campoBD CAMPO PARA LA BUSQUEDA
     * @param string $sqlOR CONDICION APARTE PARA CASOS ESPECIALES
     * @return string DEVUELVE DONDICION PARA LA CONSULTA
     */
    function busquedaTextoDesplegable($txBusqueda, $campoBD, $sqlOR = "")
    {
        $sqlTexto = "";
        if (strpos( (string)$txBusqueda, SEPARADOR_BUSQUEDA_MULTIPLE) !== false):
            $sqlTexto = $this->busquedaTextoDesplegableSeparador($txBusqueda, $campoBD, $sqlOR);
        else:
            if ($txBusqueda == ELEMENTO_BUSQUEDA_VACIO_VALUE):
                $sqlTexto = "$sqlTexto AND (" . $campoBD . " IS NULL  OR " . $campoBD . " = '' ";
            elseif ($txBusqueda == ""):
                $sqlTexto = "$sqlTexto AND (FALSE";
            else:
                $sqlTexto = "$sqlTexto AND (" . $campoBD . " = '" . $this->escapeCondicional($txBusqueda) . "' ";
            endif;
            if ($sqlOR != ""):
                $sqlTexto .= " $sqlOR )";
            else:
                $sqlTexto .= " )";
            endif;
        endif;

        return ($sqlTexto);
    }

    /**
     * FUNCION PARA LA BUSQUEDA EN DESPLEGABLES CON VARIAS SELECCIONES POR VARIOS CAMPOS
     * @param $txBusqueda TEXTO A BUSCAR
     * @param $campoSBD  ARRAY DE CAMPOS PARA LA BUSQUEDA
     * @param string $sqlOR CONDICION APARTE PARA CASOS ESPECIALES
     * @return string DEVUELVE DONDICION PARA LA CONSULTA
     */
    function busquedaTextoDesplegableSeparadorArray($txBusqueda, $camposBD, $sqlOR = "")
    {

        $cad         = $txBusqueda;
        $arrBusqueda = explode(SEPARADOR_BUSQUEDA_MULTIPLE, (string)$cad);
        //VARIABLE PARA SABER SI YA SE HA AÑADIDO ALGO A LA SQL A DEVOLVER
        $filtroRelleno = false;
        $sqlTexto      = " AND (";
        //VARIABLE PARA SABER SI YA SE HA AÑADIDO ALGO A LA SQL A DEVOLVER
        $filtroRelleno = false;
        foreach ($arrBusqueda as $elemento):
            if ($filtroRelleno == true):
                $sqlTexto = $sqlTexto . " OR ";
            endif;
            //SI ELEMENTO ES NULL CAMBIAMOS CONSULTA
            if (strtolower((string)$elemento) == 'null' || strtolower((string)$txBusqueda) == 'Sin Categoria'):
                $sqlTexto = "$sqlTexto  (" . $camposBD[0] . " IS NULL)";
            else:
                $sqlTexto = "$sqlTexto  (" . $camposBD[0] . " = '" . $this->escapeCondicional($elemento) . "')";
            endif;
            //ACTUALIZO LA VARIABLE DE FILTRO RELLENO
            $filtroRelleno = true;
        endforeach;
        for ($i = 1; $i < count( (array)$camposBD); $i++):
            foreach ($arrBusqueda as $elemento):
                if ($filtroRelleno == true):
                    $sqlTexto = $sqlTexto . " OR ";
                endif;
                //SI ELEMENTO ES NULL CAMBIAMOS CONSULTA
                if (strtolower((string)$elemento) == 'null' || strtolower((string)$txBusqueda) == 'Sin Categoria'):
                    $sqlTexto = "$sqlTexto  (" . $camposBD[$i] . " IS NULL)";
                else:
                    $sqlTexto = "$sqlTexto  (" . $camposBD[$i] . " = '" . $this->escapeCondicional($elemento) . "')";
                endif;
                //ACTUALIZO LA VARIABLE DE FILTRO RELLENO
                $filtroRelleno = true;
            endforeach;
        endfor;
        $sqlTexto = "$sqlTexto $sqlOR)";

        return ($sqlTexto);
    }

    /**
     * FUNCION PARA LA BUSQUEDA EN DESPLEGABLES CON VARIAS SELECCIONES POR VARIOS CAMPOS
     * @param $txBusqueda TEXTO A BUSCAR
     * @param $campoSBD  ARRAY DE CAMPOS PARA LA BUSQUEDA
     * @param string $sqlOR CONDICION APARTE PARA CASOS ESPECIALES
     * @return string DEVUELVE DONDICION PARA LA CONSULTA
     */
    function busquedaTextoDesplegableArray($txBusqueda, $camposBD, $sqlOR = "")
    {
        $sqlTexto = "";
        if (strpos( (string)$txBusqueda, SEPARADOR_BUSQUEDA_MULTIPLE) !== false):
            $sqlTexto = $this->busquedaTextoDesplegableSeparadorArray($txBusqueda, $camposBD, $sqlOR);
        else:

            if (strtolower((string)$txBusqueda) == 'null' || strtolower((string)$txBusqueda) == 'Sin Categoria'):
                $sqlTexto = "$sqlTexto AND " . $camposBD[0] . " IS NULL";
            else:
                $sqlTexto = "$sqlTexto AND ( (" . $camposBD[0] . " = '" . $this->escapeCondicional($txBusqueda) . "') ";
            endif;
            for ($i = 1; $i < count( (array)$camposBD); $i++):
                if (strtolower((string)$txBusqueda) == 'null' || strtolower((string)$txBusqueda) == 'Sin Categoria'):
                    $sqlTexto = "$sqlTexto OR " . $camposBD[$i] . " IS NULL";
                else:
                    $sqlTexto = "$sqlTexto OR (" . $camposBD[$i] . " = '" . $this->escapeCondicional($txBusqueda) . "' )";
                endif;
            endfor;
            if ($sqlOR != ""):
                $sqlTexto .= " $sqlOR )";
            else:
                $sqlTexto .= " )";
            endif;
        endif;

        return ($sqlTexto);
    }


    //FUNCIÓN ENCAPSULADA DE LA BÚSQUEDA DE TEXTOS POR UN ÚNICO CAMPO
    function busquedaTexto($txBusqueda, $campoBD)
    {
        $sqlTexto = "";

        $cad = $txBusqueda;
        $tok = strtok( (string)$cad, " ");
        while ($tok !== false):
            $sqlTexto = "$sqlTexto AND " . $campoBD . " LIKE '%" . $this->escapeCondicional($tok) . "%' ";
            $tok      = strtok( " \n\t");
        endwhile;

        return ($sqlTexto);
    }

    //FUNCIÓN ENCAPSULADA DE LA BÚSQUEDA DE TEXTOS POR UN ÚNICO CAMPO
    function busquedaTextoExacta($txBusqueda, $campoBD)
    {

        global $html;
        $comprobacion_vacia = str_replace( '*', '',(string) $txBusqueda);
        $comprobacion_vacia = str_replace( '?', '',(string) $comprobacion_vacia);
        $html->PagErrorCondicionado(trim( (string)$comprobacion_vacia), "==", "", "BusquedaVaciaNoPermitida");

        $buscar_like = false;
        if ((strpos( (string)$txBusqueda, '*') !== false) || (strpos( (string)$txBusqueda, '?') !== false)) {
            $buscar_like = true;
            $txBusqueda  = str_replace( '*', '%',(string) $txBusqueda);
            $txBusqueda  = str_replace( '?', '_',(string) $txBusqueda);
        }

        if ($buscar_like):
            $sqlTexto = "$sqlTexto AND " . $campoBD . " LIKE '" . $this->escapeCondicional($txBusqueda) . "' ";
        else:
            $sqlTexto = "$sqlTexto AND " . $campoBD . " = '" . $this->escapeCondicional($txBusqueda) . "' ";
        endif;

        return ($sqlTexto);
    }

    //FUNCIÓN ENCAPSULADA DE LA BÚSQUEDA DE TEXTOS POR UN ÚNICO CAMPO
    function busquedaTextoExactaSeparador($txBusqueda, $campoBD)
    {
        global $html;
        $comprobacion_vacia = str_replace( '*', '',(string) $txBusqueda);
        $comprobacion_vacia = str_replace( '?', '',(string) $comprobacion_vacia);
        $comprobacion_vacia = str_replace( SEPARADOR_BUSQUEDA_MULTIPLE, '',(string) $comprobacion_vacia);

        // CON QUE UNO DE LOS TÉRMINOS INTRODUCIDOS SEA VÁLIDO CONTINUAMOS
        $html->PagErrorCondicionado(trim( (string)$comprobacion_vacia), "==", "", "BusquedaVaciaNoPermitida");

        $listaBusquedas = explode(SEPARADOR_BUSQUEDA_MULTIPLE, (string)$txBusqueda);

        $sqlTexto = " AND (";
        $txOR = '';
        foreach ($listaBusquedas as $i => $txBusqueda):
            $comprobacion_vacia = str_replace( '*', '',(string) $txBusqueda);
            $comprobacion_vacia = str_replace( '?', '',(string) $comprobacion_vacia);

            if(trim( (string)$comprobacion_vacia) != ''):
                $buscar_like = false;
                if ((strpos( (string)$txBusqueda, '*') !== false) || (strpos( (string)$txBusqueda, '?') !== false)) {
                    $buscar_like = true;
                    $txBusqueda  = str_replace( '*', '%',(string) $txBusqueda);
                    $txBusqueda  = str_replace( '?', '_',(string) $txBusqueda);
                }

                if ($buscar_like):
                    $sqlTexto .= " $txOR (" . $campoBD . " LIKE '" . $this->escapeCondicional($txBusqueda) . "') ";
                else:
                    $sqlTexto .= " $txOR (" . $campoBD . " = '" . $this->escapeCondicional($txBusqueda) . "') ";
                endif;

                $txOR = "OR";
            endif;
        endforeach;
        $sqlTexto .= ") ";

        return ($sqlTexto);
    }

    //FUNCIÓN ENCAPSULADA DE LA BÚSQUEDA DE TEXTOS POR VARIOS CAMPOS (UN ARRAY DE CAMPOS)
    function busquedaTextoArray($txBusqueda, $camposBD)
    {

        $cad = $txBusqueda;
        $tok = strtok( (string)$cad, " ");
        while ($tok !== false):
            $sqlTexto = "$sqlTexto AND (" . $camposBD[0] . " LIKE '%" . $this->escapeCondicional($tok) . "%'";
            for ($i = 1; $i < count( (array)$camposBD); $i++):
                $sqlTexto = "$sqlTexto OR " . $camposBD[$i] . " LIKE '%" . $this->escapeCondicional($tok) . "%'";
            endfor;
            $sqlTexto = "$sqlTexto)";
            $tok      = strtok(" \n\t");
        endwhile;

        return ($sqlTexto);
    }

    function busquedaTextoArraySeparador($txBusqueda, $camposBD)
    {

        $cad         = $txBusqueda;
        $arrBusqueda = explode(SEPARADOR_BUSQUEDA_MULTIPLE, (string)$cad);
        //VARIABLE PARA SABER SI YA SE HA AÑADIDO ALGO A LA SQL A DEVOLVER
        $filtroRelleno = false;
        $sqlTexto      = " AND (";
        //VARIABLE PARA SABER SI YA SE HA AÑADIDO ALGO A LA SQL A DEVOLVER
        $filtroRelleno = false;
        foreach ($arrBusqueda as $elemento):
            if ($filtroRelleno == true):
                $sqlTexto = $sqlTexto . " OR ";
            endif;
            $sqlTexto = "$sqlTexto  (" . $camposBD[0] . " LIKE '%" . $this->escapeCondicional($elemento) . "%')";
            for ($i = 1; $i < count( (array)$camposBD); $i++):
                $sqlTexto = "$sqlTexto OR (" . $camposBD[$i] . " LIKE '%" . $this->escapeCondicional($elemento) . "%')";
            endfor;

            //ACTUALIZO LA VARIABLE DE FILTRO RELLENO
            $filtroRelleno = true;
        endforeach;
        $sqlTexto = "$sqlTexto)";

        return ($sqlTexto);
    }

    //FUNCIÓN ENCAPSULADA DE LA BÚSQUEDA DE TEXTOS POR VARIOS CAMPOS (UN ARRAY DE CAMPOS)
    function busquedaTextoArrayExacta($txBusqueda, $camposBD)
    {

        global $html;
        $comprobacion_vacia = str_replace( '*', '',(string) $txBusqueda);
        $comprobacion_vacia = str_replace( '?', '',(string) $comprobacion_vacia);
        $html->PagErrorCondicionado(trim( (string)$comprobacion_vacia), "==", "", "BusquedaVaciaNoPermitida");

        $buscar_like = false;
        if ((strpos( (string)$txBusqueda, '*') !== false) || (strpos( (string)$txBusqueda, '?') !== false)) {
            $buscar_like = true;
            $txBusqueda  = str_replace( '*', '%',(string) $txBusqueda);
            $txBusqueda  = str_replace( '?', '_',(string) $txBusqueda);
        }

        if ($buscar_like):
            $sqlTexto = "$sqlTexto AND (" . $camposBD[0] . " LIKE '" . $this->escapeCondicional($txBusqueda) . "'";
        else:
            $sqlTexto = "$sqlTexto AND (" . $camposBD[0] . " = '" . $this->escapeCondicional($txBusqueda) . "'";
        endif;
        for ($i = 1; $i < count( (array)$camposBD); $i++):
            if ($buscar_like):
                $sqlTexto = "$sqlTexto OR " . $camposBD[$i] . " LIKE '" . $this->escapeCondicional($txBusqueda) . "'";
            else:
                $sqlTexto = "$sqlTexto OR " . $camposBD[$i] . " = '" . $this->escapeCondicional($txBusqueda) . "'";
            endif;
        endfor;
        $sqlTexto = "$sqlTexto)";

        return ($sqlTexto);
    }

    //FUNCIÓN ENCAPSULADA DE LA BÚSQUEDA DE TEXTOS POR VARIOS CAMPOS (UN ARRAY DE CAMPOS) CON LA OPCIÓN DE INTRODUCIR UNA CONDICIÓN AÑADIDA A LA CONSULTA (OR)
    function busquedaTextoArrayExactaEspecial($txBusqueda, $camposBD, $txBusquedaEspecial)
    {

        global $html;
        $comprobacion_vacia = str_replace( '*', '',(string) $txBusqueda);
        $comprobacion_vacia = str_replace( '?', '',(string) $comprobacion_vacia);
        $html->PagErrorCondicionado(trim( (string)$comprobacion_vacia), "==", "", "BusquedaVaciaNoPermitida");

        $buscar_like = false;
        if ((strpos( (string)$txBusqueda, '*') !== false) || (strpos( (string)$txBusqueda, '?') !== false)) {
            $buscar_like = true;
            $txBusqueda  = str_replace( '*', '%',(string) $txBusqueda);
            $txBusqueda  = str_replace( '?', '_',(string) $txBusqueda);
        }

        if ($buscar_like):
            $sqlTexto = "$sqlTexto AND (" . $camposBD[0] . " LIKE '" . $this->escapeCondicional($txBusqueda) . "'";
        else:
            $sqlTexto = "$sqlTexto AND (" . $camposBD[0] . " = '" . $this->escapeCondicional($txBusqueda) . "'";
        endif;
        for ($i = 1; $i < count( (array)$camposBD); $i++):
            if ($buscar_like):
                $sqlTexto = "$sqlTexto OR " . $camposBD[$i] . " LIKE '" . $this->escapeCondicional($txBusqueda) . "'";
            else:
                $sqlTexto = "$sqlTexto OR " . $camposBD[$i] . " = '" . $this->escapeCondicional($txBusqueda) . "'";
            endif;
        endfor;
        $sqlTexto = "$sqlTexto OR $txBusquedaEspecial)";

        return ($sqlTexto);
    }

    //FUNCIÓN ENCAPSULADA DE LA BÚSQUEDA DE VARIOS TEXTOS (UN ARRAY DE TEXTOS) POR VARIOS CAMPOS (UN ARRAY DE CAMPOS)
    function busquedaArrayTextoArrayExacta($arrayTextos, $camposBD)
    {
        global $html;

        //VARIABLE PARA DETERMINAR SI HAY ALGO RELLENO
        $textos_vacios = true;

        for ($i = 0; $i < count( (array)$arrayTextos); $i++):
            $textoComprobar = $arrayTextos[$i];
            $textoComprobar = str_replace( '*', '',(string) $textoComprobar);
            $textoComprobar = str_replace( '?', '',(string) $textoComprobar);
            if ($textoComprobar != ""):
                $textos_vacios = false;
            endif;
        endfor;
        $html->PagErrorCondicionado($textos_vacios, "==", true, "BusquedaVaciaNoPermitida");

        //VARIABLE SQL A DEVOLVER
        $sqlTexto = " AND (";

        //VARIABLE PARA SABER SI YA SE HA AÑADIDO ALGO A LA SQL A DEVOLVER
        $filtroRelleno = false;

        //RECORRO LOS TEXTOS PARA CONFORMAR LA SQL
        for ($i = 0; $i < count( (array)$arrayTextos); $i++):
            $textoBusqueda = $arrayTextos[$i];

            $buscar_like = false;
            if ((strpos( (string)$textoBusqueda, '*') !== false) || (strpos( (string)$textoBusqueda, '?') !== false)) {
                $buscar_like   = true;
                $textoBusqueda = str_replace( '*', '%',(string) $textoBusqueda);
                $textoBusqueda = str_replace( '?', '_',(string) $textoBusqueda);
            }

            //RECORRO LOS CAMPOS A COMPROBAR
            for ($j = 0; $j < count( (array)$camposBD); $j++):
                if ($filtroRelleno == true):
                    $sqlTexto = $sqlTexto . " OR ";
                endif;

                if ($buscar_like):
                    $sqlTexto = $sqlTexto . "(" . $camposBD[$j] . " LIKE '" . $this->escapeCondicional($textoBusqueda) . "')";
                else:
                    $sqlTexto = $sqlTexto . "(" . $camposBD[$j] . " = '" . $this->escapeCondicional($textoBusqueda) . "')";
                endif;

                //ACTUALIZO LA VARIABLE DE FILTRO RELLENO
                $filtroRelleno = true;
            endfor;
        endfor;

        //FINALIZO LA VARIABLE A DEVOLVER
        $sqlTexto = $sqlTexto . ")";

        //RETORNO LA VARIABLE CONSTRUIDA
        return $sqlTexto;
    }

    //FUNCIÓN ENCAPSULADA DE LA BÚSQUEDA DE TEXTOS EXACTOS
    function busquedaExacto($txBusqueda, $campoBD)
    {

        $sqlTexto = "$sqlTexto AND " . $campoBD . " = '" . $this->escapeCondicional($txBusqueda) . "'";

        return ($sqlTexto);
    }

    //FUNCIÓN ENCAPSULADA DE LA BÚSQUEDA DE NÚMEROS
    function busquedaNumero($txNumero, $campoBD)
    {
        //VARIABLE SQL A DEVOLVER
        $sqlNumero = "";

        // SI NO TIENE SEPARADOR, CREAMOS CONSULTA CON UN ÚNICO NÚMERO
        if (strpos( (string)$txNumero, SEPARADOR_BUSQUEDA_MULTIPLE) === false):
            $sqlNumero = "$sqlNumero AND " . $campoBD . " = '" . $this->escapeCondicional($txNumero) . "'";

            return ($sqlNumero);

        //SI TIENE SEPARADOR, RECORREMOS LOS NUMEROS Y CREAMOS LA CONSULTA MULTIPLE
        else:
            $sqlNumero     = " AND ( ";
            $arrNumero     = explode(SEPARADOR_BUSQUEDA_MULTIPLE, (string)$txNumero);
            $filtroRelleno = false;
            foreach ($arrNumero as $numero):
                if ($filtroRelleno == true):
                    $sqlNumero .= " OR ";
                endif;
                $sqlNumero     .= $campoBD . " = '" . $this->escapeCondicional($numero) . "'";
                $filtroRelleno = true;
            endforeach;
            $sqlNumero .= " ) ";

            return ($sqlNumero);
        endif;
    }

    //FUNCIÓN ENCAPSULADA DE LA BÚSQUEDA DE NÚMEROS CON LA OPCIÓN DE INTRODUCIR UNA CONDICIÓN AÑADIDA A LA CONSULTA (OR)
    function busquedaNumeroEspecial($txNumero, $campoBD, $txBusquedaEspecial)
    {
        //VARIABLE SQL A DEVOLVER
        $sqlNumero = "";

        // SI NO TIENE SEPARADOR, CREAMOS CONSULTA CON UN ÚNICO NÚMERO
        if (strpos( (string)$txNumero, SEPARADOR_BUSQUEDA_MULTIPLE) === false):
            $sqlNumero = "$sqlNumero AND (" . $campoBD . " = '" . $this->escapeCondicional($txNumero) . "' OR $txBusquedaEspecial)";

            return ($sqlNumero);

        //SI TIENE SEPARADOR, RECORREMOS LOS NUMEROS Y CREAMOS LA CONSULTA MULTIPLE
        else:
            $sqlNumero     = " AND ( ";
            $arrNumero     = explode(SEPARADOR_BUSQUEDA_MULTIPLE, (string)$txNumero);
            $filtroRelleno = false;
            foreach ($arrNumero as $numero):
                if ($filtroRelleno == true):
                    $sqlNumero .= " OR ";
                endif;
                $sqlNumero     .= $campoBD . " = '" . $this->escapeCondicional($numero) . "'";
                $filtroRelleno = true;
            endforeach;
            $sqlNumero .= " OR $txBusquedaEspecial) ";

            return ($sqlNumero);
        endif;
    }

    //FUNCIÓN ENCAPSULADA DE LA BÚSQUEDA DE ARRAY DENÚMEROS
    function busquedaNumero2($txNumero, $campoBD)
    {
        //VARIABLE SQL A DEVOLVER
        $sqlNumero = "";

        if (strpos( (string)$txNumero, SEPARADOR_BUSQUEDA_MULTIPLE) === false):
            $sqlNumero = "$sqlNumero AND " . $campoBD . " = '" . $this->escapeCondicional($txNumero) . "'";

            return ($sqlNumero);
        else:
            $sqlNumero     = " AND ( ";
            $arrNumero     = explode(SEPARADOR_BUSQUEDA_MULTIPLE, (string)$txNumero);
            $filtroRelleno = false;
            foreach ($arrNumero as $numero):
                if ($filtroRelleno == true):
                    $sqlNumero .= " OR ";
                endif;
                $sqlNumero     .= $campoBD . " = '" . $this->escapeCondicional($numero) . "'";
                $filtroRelleno = true;
            endforeach;
            $sqlNumero .= " ) ";

            return ($sqlNumero);
        endif;
    }

    //FUNCIÓN ENCAPSULADA DE LA BÚSQUEDA DE TEXTOS POR VARIOS CAMPOS (UN ARRAY DE CAMPOS)
    //ANTIGUA
    /*function busquedaNumeroArray($txBusqueda, $camposBD)
    {
	
		$cad=$txBusqueda;		
		$tok = strtok ($cad," ");		
		while ($tok !== false):
			$sqlTexto = "$sqlTexto AND (".$camposBD[0]." = '".$this->escapeCondicional($tok)."'";
			for($i=1;$i<count($camposBD);$i++):
				$sqlTexto = "$sqlTexto OR ".$camposBD[$i]." = '".$this->escapeCondicional($tok)."'";
			endfor;
			$sqlTexto = "$sqlTexto)";
			$tok = strtok(" \n\t");
		endwhile;	
		
        return ($sqlTexto);
    }*/

    //NUEVA
    //BUSQUEDA NUMERO `PR VARIOS CAMPOS (UN ARRAY DE CAMPOS)  CON BUSQUEDA MULTIPLE POR SEPARADOR
    function busquedaNumeroArray($txBusqueda, $camposBD)
    {

        $cad = $txBusqueda;
        $tok = strtok( (string)$cad, " ");

        //VARIABLE SQL A DEVOLVER
        $sqlTexto = "";

        // SI NO TIENE SEPARADOR, CREAMOS CONSULTA CON UN ÚNICO NÚMERO
        if (strpos( (string)$txBusqueda, SEPARADOR_BUSQUEDA_MULTIPLE) === false):
            while ($tok !== false):
                $sqlTexto = "$sqlTexto AND (" . $camposBD[0] . " = '" . $this->escapeCondicional($tok) . "'";
                for ($i = 1; $i < count( (array)$camposBD); $i++):
                    $sqlTexto = "$sqlTexto OR " . $camposBD[$i] . " = '" . $this->escapeCondicional($tok) . "'";
                endfor;
                $sqlTexto = "$sqlTexto)";
                $tok      = strtok(" \n\t");
            endwhile;

        //SI TIENE SEPARADOR, RECORREMOS LOS NUMEROS Y CREAMOS LA CONSULTA MULTIPLE
        else:
            $sqlTexto      = " AND ( ";
            $filtroRelleno = false;
            for ($i = 0; $i < count( (array)$camposBD); $i++):
                $arrNumero = explode(SEPARADOR_BUSQUEDA_MULTIPLE, (string)$txBusqueda);

                foreach ($arrNumero as $numero):
                    if ($filtroRelleno == true):
                        $sqlTexto .= " OR ";
                    endif;
                    $sqlTexto      .= $camposBD[$i] . " = '" . $this->escapeCondicional($numero) . "'";
                    $filtroRelleno = true;
                endforeach;
            endfor;
            $sqlTexto .= " ) ";

        endif;

        return ($sqlTexto);
    }

    //FUNCIÓN ENCAPSULADA DE LA BÚSQUEDA DE FECHAS TIPO DATE O DATETIME
    function busquedaFechas($txFechaInicio, $txFechaFin, $campoFechaBD, $tipo_fecha)
    {
        //$tipo_fecha ha de ser DATE o DATETIME

        global $comp;
        global $html;
        global $auxiliar;

        //COMPRUEBA FORMATO FECHA
        $txFechaInicio = trim( (string)$txFechaInicio);
        $txFechaFin    = trim( (string)$txFechaFin);

        if ($tipo_fecha == 'DATE') {
            $fechaInicioBBDD = $auxiliar->fechaFmtoSQL($txFechaInicio);
            $fechaFinBBDD    = $auxiliar->fechaFmtoSQL($txFechaFin);
        } elseif ($tipo_fecha == 'DATETIME') {

            //TRADUCIMOS ENTRE HUSOS HORARIOS
            $txFechaInicio_traducir = $txFechaInicio;
            $txFechaFin_traducir    = $txFechaFin;

            if (strlen( (string)$txFechaInicio_traducir) <= 10) {
                $txFechaInicio_traducir = $txFechaInicio_traducir . ' 00:00:00';
            }
            if (strlen( (string)$txFechaFin_traducir) <= 10) {
                $txFechaFin_traducir = $txFechaFin_traducir . ' 23:59:59';
            }
            $fechaInicioBBDD = $auxiliar->fechaFmtoSQLHora($txFechaInicio_traducir);
            $fechaFinBBDD    = $auxiliar->fechaFmtoSQLHora($txFechaFin_traducir);
            /*$fechaInicioBBDD = $auxiliar->fechaFmtoSQLHora($txFechaInicio.' '.'00:00:00');
            $fechaFinBBDD = $auxiliar->fechaFmtoSQLHora($txFechaFin.' '.'23:59:59');*/
        }
        
        if ($txFechaInicio == ''):
            $arr_tx[0]["err"]   = "Fecha Fin";
            $arr_tx[0]["valor"] = $txFechaFin;
            $comp->ComprobarFecha($arr_tx, "FechaIncorrecta");
            //$fechaInicioBBDD = substr($txFechaInicio,-4)."-".substr($txFechaInicio,3,2)."-".substr($txFechaInicio,0,2);
            //$fechaFinBBDD = substr($txFechaFin,-4)."-".substr($txFechaFin,3,2)."-".substr($txFechaFin,0,2);
            $sqlFecha = "$sqlFecha AND " . $campoFechaBD . " <= '" . $fechaFinBBDD . "'";
        elseif ($txFechaFin == ''):
            $arr_tx[0]["err"]   = "Fecha Inicio";
            $arr_tx[0]["valor"] = $txFechaInicio;
            $comp->ComprobarFecha($arr_tx, "FechaIncorrecta");
            //$fechaInicioBBDD = substr($txFechaInicio,-4)."-".substr($txFechaInicio,3,2)."-".substr($txFechaInicio,0,2);
            //$fechaFinBBDD = substr($txFechaFin,-4)."-".substr($txFechaFin,3,2)."-".substr($txFechaFin,0,2);
            $sqlFecha = "$sqlFecha AND " . $campoFechaBD . " >= '" . $fechaInicioBBDD . "'";
        elseif ($txFechaInicio <> '' && $txFechaFin <> ''):
            $arr_tx[0]["err"]   = "Fecha Inicio";
            $arr_tx[0]["valor"] = $txFechaInicio;
            $arr_tx[1]["err"]   = "Fecha Fin";
            $arr_tx[1]["valor"] = $txFechaFin;
            $comp->ComprobarFecha($arr_tx, "FechaIncorrecta");
            if ($fechaInicioBBDD > $fechaFinBBDD):
                $html->PagError('OrdenFechasIncorrecto');
            endif;
            //$fechaInicioBBDD = substr($txFechaInicio,-4)."-".substr($txFechaInicio,3,2)."-".substr($txFechaInicio,0,2);
            //$fechaFinBBDD = substr($txFechaFin,-4)."-".substr($txFechaFin,3,2)."-".substr($txFechaFin,0,2);
            $sqlFecha = "$sqlFecha AND " . $campoFechaBD . " >= '" . $fechaInicioBBDD . "'";
            $sqlFecha = "$sqlFecha AND " . $campoFechaBD . " <= '" . $fechaFinBBDD . "'";
        endif;

        return ($sqlFecha);
    }

    //FUNCIÓN ENCAPSULADA DE LA BÚSQUEDA DE FECHAS TIPO TIME
    function busquedaHoras($txHoraInicio, $GMT, $campoFechaBD, $tipo)
    {

        $min_ini   = substr( (string) $txHoraInicio, -2);
        $num_horas = 24 - intval(substr( (string) $txHoraInicio, 0, 2));
        $horaSQL   = intval(substr( (string) $txHoraInicio, 0, 2)) - intval($GMT) + 1;
        $horafin   = $horaSQL + $num_horas;

        if ($tipo == "inicio"):
            $comparador_principio = " >= ";
            $comparador_final     = " <= ";
        else:
            $comparador_principio = " <= ";
            $comparador_final     = " >= ";
        endif;

        $sqlHora = "";
        if ($horaSQL >= 24):
            $horaSQL = $horaSQL - 24;
            $horafin = $horaSQL + $num_horas;
        elseif ($horaSQL < 0):
            $horaSQL = $horaSQL + 24;
            $horafin = $horaSQL + $num_horas;
        endif;

        $sqlHora .= " AND (" . $campoFechaBD . $comparador_principio . "'" . $horaSQL . ":$min_ini'";

        if ($horafin >= 24):
            $horafin = $horafin - 24;
            $sqlHora .= ($tipo == "inicio" ? " OR " : " AND ");
            $sqlHora .= $campoFechaBD . $comparador_final . "'" . $horafin . ":00')";
        else:
            $sqlHora .= ($tipo == "inicio" ? " AND " : " OR ");
            $sqlHora .= $campoFechaBD . $comparador_final . "'" . $horafin . ":00')";
        endif;

        return ($sqlHora);
    }

    //FUNCIÓN ENCAPSULADA DE LA BÚSQUEDA DE FECHAS Y HORAS DATETIME
    function busquedaFechasHoras($txFechaInicio, $txFechaFin, $txHoraInicio, $txHoraFin, $campoFechaBD)
    {
        global $auxiliar;

        if ($txHoraInicio == '' && $txFechaInicio != ''):
            $txHoraInicioSQL = ($auxiliar->fechaFmtoSQL($txFechaInicio) . " 00:00:00");
        elseif ($txHoraInicio != '' && $txFechaInicio != ''):
            $txHoraInicioSQL = ($auxiliar->fechaFmtoSQL($txFechaInicio) . ' ' . $txHoraInicio . ":00");
        endif;

        if ($txHoraFin == '' && $txFechaFin != ''):
            $txHoraFinSQL = ($auxiliar->fechaFmtoSQL($txFechaFin) . " 23:59:59");
        elseif ($txHoraFin != '' && $txFechaFin != ''):
            $txHoraFinSQL = ($auxiliar->fechaFmtoSQL($txFechaFin) . ' ' . $txHoraFin . ":59");
        endif;

        if ($txHoraInicioSQL != ''):
            $sqlWSLog1 = " AND " . $campoFechaBD . " >= '" . $txHoraInicioSQL . "'";
        endif;
        if ($txHoraFinSQL != ''):
            $sqlWSLog1 .= " AND " . $campoFechaBD . " <= '" . $txHoraFinSQL . "'";
        endif;


        return ($sqlWSLog1);

    }

    //FUNCIÓN ENCAPSULADA DEL TEXTO DE LISTA DE LAS FECHAS Y HORAS
    function textoListaFechasHoras($txFechaInicio, $txFechaFin, $txHoraInicio, $txHoraFin, $campoFechaBD)
    {
        global $auxiliar;

        if ($txHoraInicio == '' && $txFechaInicio != ''):
            $txHoraInicioSQL = ($auxiliar->fechaFmtoSQL($txFechaInicio) . " 00:00:00");
        elseif ($txHoraInicio != '' && $txHoraInicio != ''):
            $txHoraInicioSQL = ($auxiliar->fechaFmtoSQL($txFechaInicio) . ' ' . $txHoraInicio . ":00");
        endif;

        if ($txHoraFin == '' && $txFechaFin != ''):
            $txHoraFinSQL = ($auxiliar->fechaFmtoSQL($txFechaFin) . " 23:59:59");
        elseif ($txHoraFin != '' && $txFechaFin != ''):
            $txHoraFinSQL = ($auxiliar->fechaFmtoSQL($txFechaFin) . ' ' . $txHoraFin . ":59");
        endif;

        $textoLista = '&Fecha';
        if ($txHoraInicioSQL != ''):
            if ($txHoraInicio != ''):
                $textoLista .= " Desde: $txFechaInicio $txHoraInicio";
            else:
                $textoLista .= " Desde: $txFechaInicio";
            endif;
        endif;
        if ($txHoraFinSQL != ''):
            if ($txHoraFin != ''):
                $textoLista .= " Hasta: $txFechaFin $txHoraFin";
            else:
                $textoLista .= " Hasta: $txFechaFin";
            endif;
        endif;

        return ($textoLista);
    }

    //FUNCIÓN ENCAPSULADA DEL TEXTO DE LISTA DE LAS FECHAS
    function textoListaFechas($txFechaInicio, $txFechaFin)
    {

        if ($txFechaInicio == $txFechaFin):
            $textoLista = $textoLista . "&Fecha: " . $txFechaInicio;
        else:
            if ($txFechaFin == ''):
                $textoLista = $textoLista . "&Fecha Desde: " . $txFechaInicio;
            elseif ($txFechaInicio == ''):
                $textoLista = $textoLista . "&Fecha Hasta: " . $txFechaFin;
            elseif ($txFechaInicio <> '' && $txFechaFin <> ''):
                $textoLista = $textoLista . "&Fecha Desde: $txFechaInicio Hasta: $txFechaFin";
            endif;
        endif;

        return ($textoLista);
    }

    function IdAsignado()
    {
        return mysqli_insert_id($this->conexion);
    }

    //AÑADE LOS CARACTERES DE ESCAPE SQL A UNA CADENA TENIENDO EN CUENTA EL ESTADO DE magic_quotes
    function addEscape($cadena)
    {
            return addslashes( (string)$cadena);

    }

    /**
     * FUNCION ENUMS CONTRA BBDD
     *
     * Devuelve valores Enum de una columna y tabla dadas.
     */

    function valoresEnums($nombreTabla, $nombreColumna)
    {

        $sqlEnum    = "SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '$nombreTabla' AND COLUMN_NAME =  '$nombreColumna'";
        $resultEnum = $this->ExecSQL($sqlEnum);
        $rowEnum    = $this->SigReg($resultEnum);
        $enumList   = explode(",", str_replace("'", "", (string) substr( (string) $rowEnum->COLUMN_TYPE, 5, (strlen( (string)$rowEnum->COLUMN_TYPE) - 6))));

        return $enumList;

    }

    //OBTIENE EL NAVEGADOR UTILIZADO POR EL USUARIO. VERSIÓN ACTUALIZADA 14/12/2018 (CHRISTIAN TÉLLEZ)
    function ObtenerNavegador($user_agent)
    {
        $navegador = 'Desconocido';

        $navegadores = array(
            '/firefox/i'   => 'Mozilla Firefox',
            '/safari/i'    => 'Safari',
            '/chrome/i'    => 'Chrome',
            '/edge/i'      => 'Edge',
            '/opr/i'       => 'Opera',
            '/netscape/i'  => 'Netscape',
            '/maxthon/i'   => 'Maxthon',
            '/konqueror/i' => 'Konqueror',
            '/mobile/i'    => 'Handheld Browser',
            '/trident/i'   => 'Internet Explorer 11',
            '/msie/i'      => 'Internet Explorer',
        );

        foreach ($navegadores as $regex => $valor)
            if (preg_match($regex, (string) $user_agent))
                $navegador = $valor;

        return $navegador;
    }

    static function ComprobarMaxEnviosEmail()
    {
        global $path_raiz;
        global $auxiliar;

        // NOMBRE DEL LOG
        $nombreLog = $path_raiz . "contador_max_emails/contador_email_errores.txt";

        // VER SI EXISTE EL LOG
        if (file_exists($nombreLog)):
            // LEER EL CONTENIDO
            $log = file($nombreLog);

            // SI NO HAY REGISTROS DEJAMOS ENVIAR EL EMAIL
            if ($log[0] == ""):
                $fp = fopen($nombreLog, "w");
                fwrite($fp, date("YmdH") . "|1");
                fclose($fp);

                return true;
            endif;

            // SI NO TIENE DOS CAMPOS DEJAMOS ENVIAR EL EMAIL
            $arrLog = explode("|", (string)$log[0]);

            if (sizeof($arrLog) != 2):
                $fp = fopen($nombreLog, "w");
                fwrite($fp, date("YmdH") . "|1");
                fclose($fp);

                return true;
            endif;

            // SI NO COINCIDE EL DIA Y HORA, DEJAMOS ENVIAR EL EMAIL Y SE INICIA EL CONTADOR
            $dia      = $arrLog[0];
            $contador = $arrLog[1];
            if (date("YmdH") != $arrLog[0]):
                $fp = fopen($nombreLog, "w");
                fwrite($fp, date("YmdH") . "|1");
                fclose($fp);

                return true;
            endif;

            // SI EL CONTADOR DE EMAILS ES MAYOR AL PERMITIDO DEVOLVER ERROR
            if ($contador >= 50):
                if ($contador == 50):
                    $auxiliar->enviarCorreoSistema("ACCIONA - MAX EMAILS", "MAXIMO EMAILS POR HORA ALCANZADO", OUTLOOK_USER, SENDER_EMAIL, $email_adminitracion, "");
                endif;
                $fp = fopen($nombreLog, "w");
                fwrite($fp, date("YmdH") . "|" . ($contador + 1));
                fclose($fp);

                return false;
            endif;

            // SI EL CONTADOR DE EMAILS ES MENOR AL PERMITIDO SE INCREMENTA Y SE DEVUELVE TRUE
            $fp = fopen($nombreLog, "w");

            fwrite($fp, date("YmdH") . "|" . ($contador + 1));
            fclose($fp);

            return true;
        else:
            // CREAR EL LOG
            $fp = fopen($nombreLog, "w");
            fwrite($fp, date("YmdH") . "|1");
            fclose($fp);

            return true;
        endif;
    }

    /**
     * FUNCION PARA LLAMAR DE FORMA ASINCRONA A LA API DE ERRORES EN TT
     * @param string $error MENSAJE DE ERROR
     * @param string $tipo TIPO DE ERROR
     */
    function registrarBTS($error, $tipo = '')
    {
        if (ENTORNO_WEB == 'DESARROLLO' || ENTORNO_WEB == 'INTEGRACION' || ENTORNO_WEB == 'PRODUCCION' || ENTORNO_WEB == 'PRODUCCION_EAGLE'):

            // CONSIGO EL SISTEMA
            $sistema = '';
            if (ENTORNO_WEB == 'PRODUCCION_EAGLE'):
                $sistema = '13';
            else:
                $sistema = '1';
            endif;

            // CONSIGO EL ENTORNO
            $entorno = '';
            if (ENTORNO_WEB == 'PRODUCCION_EAGLE'):
                $entorno = 'PRODUCCION';
            else:
                $entorno = ENTORNO_WEB;
            endif;

            $curl = curl_init();
            $data = array(
                'fechaCreacion' => date('Y-m-d H:i:s'),
                'sistema'       => $sistema,
                'entorno'       => $entorno,
                'urlServidor'   => URL_SERVIDOR,
                'ruta'          => $_SERVER['SCRIPT_FILENAME'],
                'tipo'          => $tipo,
                'migracion'     => 1,
                'mensaje'       => $error
            );

            //CONVERTIMOS A JSON
            $data_json = $this->convertir_json($data);

            curl_setopt_array($curl, array(
                CURLOPT_URL            => API_BTS,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING       => "",
                CURLOPT_MAXREDIRS      => 10,
                CURLOPT_TIMEOUT        => 10,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST  => "POST",
                CURLOPT_POSTFIELDS     => $data_json,
                CURLOPT_HTTPHEADER     => array(
                    "Content-Type: application/json",
                    "Content-Length: " . strlen( (string)$data_json),
                    "Authorization: Basic " . base64_encode((string)USUARIO_API_BTS . ':' . PASSWORD_API_BTS)
                ),
            ));
            curl_exec($curl);
            curl_close($curl);
        endif;
        if (GENERAR_LOGS):
            $this->log($error, $tipo);
        endif;
    }

    function log($error, $tipo = '') {
        // CONSIGO EL SISTEMA
        $sistema = '';
        if (ENTORNO_WEB == 'PRODUCCION_EAGLE'):
            $sistema = 'Eagle';
        else:
            $sistema = 'Acciona';
        endif;

        // CONSIGO EL ENTORNO
        $entorno = '';
        if (ENTORNO_WEB == 'PRODUCCION_EAGLE'):
            $entorno = 'PRODUCCION';
        else:
            $entorno = ENTORNO_WEB;
        endif;

        $logger = new logger();
        $logger->anadirLog($error, $tipo, $entorno, $sistema);
    }

    //FUNCION PARA CONVETIR JSON
    function convertir_json($data)
    {
        global $auxiliar;
        //PRIMERO, LE QUITAMOS LOS NULLS Y LOS CARACTERES ESPECIALES
        array_walk_recursive($data, "basedatos::encode_before_json");

        //GENERAMOS EL JSON SIN ESCAPAR UTF8 PARA LUEGO PODER HACER DECODE
        $data_json = json_encode($data, JSON_UNESCAPED_UNICODE);

        //SE HACE DECODE (CHAIN GO ADMITE CARACTERES ESPECIALES)
        $data_json = $auxiliar->to_iso88591($data_json);

        return $data_json;
    }

    public static function encode_before_json(&$item, $key)
    {
        global $auxiliar;
        $item = $auxiliar->to_utf8($item);
    }

    function FilasAfectadas()
    {
        return mysqli_affected_rows($this->conexion);
    } // FIN FilasAfectadas

} // FIN DE LA CLASE basedatos

?>