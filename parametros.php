<?php
//defino los parámetros de configuración

//-----------------------------------------------------------------------------
//ENTORNO
//-----------------------------------------------------------------------------

define("ENTORNO", "DESARROLLO");

//ENTORNO WEB
define("ENTORNO_WEB", "DOCKER");

//PARA EVITAR CARGAR EL MENU ENTERO
define("MENU_REDUCIDO", "1");

// BASE DE DATOS LOCAL
$host     = "172.17.0.1";
$nombrebd = "VICARLI_ACCIONA_SGA_REAL";
$usuario  = "root";
$password = "Xacuzedu-77";


// URLs
$nombreHost    = "http://localhost/";
$HostPuerto    = "http://localhost:80/";
$nombreCarpeta = "acciona_sga";
define("URL_SERVIDOR", 'localhost');
define("HOST", $nombreHost);

//NOMBRE APLICACION
define('NOMBRE_APLICACION', 'ACCIONA_SGA_DOCKER');

//CUENTAS CORREO
define("CORREO_HOST", "");
define("CORREO_USER", "");
define("CORREO_PASS", "");

define("OUTLOOK_USER", "");
define("OUTLOOK_PWD", "");
define("SENDER_EMAIL", "");
