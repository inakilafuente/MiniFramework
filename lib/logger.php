<?php

# logger
# Clase logger contiene todas las funciones necesarias para la interaccion con los logs

require_once "globales.php";

class logger
{
    private $rutaLog;
    private $nombreLog = 'desarrollo_eventos.log';

    function __construct()
    {
        $this->rutaLog = RUTA_ABS_LOG;
    } // Fin logger

    // CREA LA CARPTA 'logs' SI NO ESTA CREADA
    function crearCarpetaLogs()
    {
        if (!is_dir($this->rutaLog)):
            mkdir($this->rutaLog, 0700, true);
            chown($this->rutaLog, 'apache');
        endif;
    }

    function anadirLog($error, $tipo, $entorno, $sistema)
    {

        try {
            //SE COMENTA MIENTRAS TENGAMOS QUE CREAR LA CARPATA '/logs' MANUALMENTE
            $this->crearCarpetaLogs();

            $existe = file_exists($this->rutaLog . $this->nombreLog);

            // ABRE O CREA EL ARCHIVO
            $logFile = fopen($this->rutaLog . $this->nombreLog, 'a');

            // SI FALLA, LANZO UNA EXCEPCIÓN PARA ENVIAR A BTS
            if ($logFile == false):
                throw new Exception();
            endif;

            // SI NO EXISTE, DAMOS PERMISOS, AGREGAMOS LOS DATOS DE ENTORNO, Y SISTEMA AL PRINCIPIO DEL ARCHIVO
            if (!$existe):
                fwrite($logFile, "Sistema: $sistema, Entorno: $entorno, URL Servidor: " . URL_SERVIDOR);
            else:
                //Llamamos a Limpiar Log para controlar el tamaño
                $this->limpiar_log($logFile);
            endif;

            $mensaje = "\n" . date('Y-m-d H:i:s') . " --> " . (!empty($tipo) ? $tipo : 'Error') . ' --> ' . $_SERVER['SCRIPT_FILENAME'] . ' --> ' . $error;

            // AGREGAMOS EL MENSAJE AL ARCHIVO
            $escrito = fwrite($logFile, $mensaje);
            if ($escrito == false):
                error_log("Fallo escribiendo log");
            endif;

            // CERRAMOS EL ARCHIVO
            fclose($logFile);
        } catch (Exception $e) {
            if (ENTORNO != 'PRODUCCION'):
                error_log('Ruta de log mal configurada');
            endif;
        }
    }


    /**
     * @return float NUMERO DE MEGAS QUE OCUPA EL LOG
     */
    function get_log_size()
    {
        try {

            $existe = file_exists($this->rutaLog . $this->nombreLog);

            if ($existe):
                $filesize = filesize($this->rutaLog . $this->nombreLog);

                return round( (float)$filesize / 1024 / 1024, 4);
            endif;


        } catch (Exception $e) {
            if (ENTORNO != 'PRODUCCION'):
                error_log('Ruta de log mal configurada');
            endif;
        }
    }

    /*
     * SI EL TAMAÑO EN MB ES MAYOR QUE EL PERMITIDO, BORRAMOS LA MITAD DEL CONTENIDO
     */
    function limpiar_log(&$logFile)
    {
        //SI EL TAMAÑO EN MB ES MAYOR QUE EL PERMITIDO, BORRAMOS LA MITAD DEL CONTENIDO
        if ($this->get_log_size() > MAX_LOG_MB):

            $offset = filesize($this->rutaLog . $this->nombreLog) / 2;

            //NOS QUEDAMOS CON LA SEGUNDA MITAD DEL ARCHIVO
            $logsToKeep = file_get_contents($this->rutaLog . $this->nombreLog, NULL, NULL, $offset, MAX_LOG_MB*1024*1024);
            file_put_contents($this->rutaLog . $this->nombreLog, $logsToKeep);
            fwrite($logFile, "\n" . date('Y-m-d H:i:s') . " --> Tamaño de Log Excedido. Se trunca a la mitad");
        endif;
    }

}