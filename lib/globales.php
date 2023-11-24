<?
//OBTENEMOS EL ARCHIVO DONDE ESTA DEFINIDO EL ENTORNO_WEB (Para usar en local)
require_once dirname(__FILE__) . '/../parametros.php';

// GENERAL
define('BLOQUEAR_TODOS_CLIENTES', '0');

// LOGGER
define('GENERAR_LOGS', true);
define('RUTA_ABS_LOG', '/var/www/html/' . $nombreCarpeta . '/logs/');
define('MAX_LOG_MB', 50);

//CORREOS EQUIPO IR
define('EQUIPO_IR_CORREO', 'alberto.rojo@irsoluciones.com');

//ENVIO ALERTAS DEFECTO
$envio_alerta_defecto = false;

define('DESTINATARIOS_TEST_DEFECTO', EQUIPO_IR_CORREO);

//LOGOS Y FONDOS
define('FONDO_CABECERA', 'fondo_cabecera.jpg');
define('FONDO_CABECERA_DENTRO', 'fondo_cabecera_dentro.jpg');

//NOMBRES
$nombre_empresa    = 'Acciona';
$nombre_aplicacion = 'ACCIONA SGA';
$titulo_aplicacion = 'ACCIONA Energía SCS';

define('REMITENTE_MAILS', SENDER_EMAIL);
define('EMPRESA_ENTORNO', $nombre_empresa);
define('CLAVE_ENTORNO', $nombre_aplicacion);
define('TITULO_ENTORNO', $titulo_aplicacion);

//NOMBRE DE LA SESION
define('NOMBRE_SESSION', NOMBRE_APLICACION . '20210126'); //MODIFICAR PARA DESLOGUEAR A LOS USUSARIOS

//URLs
$url_raiz     = $HostPuerto;
$path_raiz    = '/var/www/html/' . $nombreCarpeta . '/';
$url_web      = $nombreHost . 'administrador/';
$url_web_adm  = $HostPuerto . 'administrador/';
$path_web_adm = '/var/www/html/' . $nombreCarpeta . '/administrador/';

// MOSTRAR ERRORES
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
// FIN MOSTRAR ERRORES

//COLETILLAS PARA FACTURAS NO COMERCIALES
define('COLETILLA_ESP', '_ES');
define('COLETILLA_ENG', '_EN');

//SEPARADOR PARA BUSQUEDAS MULTIPLES
define('SEPARADOR_BUSQUEDA_MULTIPLE', '|');

//SEPARADOR ENTRE MATERIAL Y SERIELOTE EN PDA ANTIGUO
define('SEPARADOR_MATERIAL_PDA_ANTIGUO', '#');

//SEPARADOR ENTRE LOS CAMPOS DE LOS NUEVOS QR
define('SEPARADOR_MATERIAL_PDA', '@/$');

//VALOR PARA COMPARAR CANTIDADES
define('EPSILON_SISTEMA', '0.000000001');

//DEFINICION ELEMENTO PARA BUSQUEDAS VACIAS EN LOS DESPLEGABLES
define('ELEMENTO_BUSQUEDA_VACIO_VALUE', 'ElementoBusquedaVacio');

//VARIABLE PARA COMPROBAR SI SE DEBEN EJECUTAR LOS WEB SERVICES
//true -> Se Ejecutan
//false -> No se Ejecutan
define('EJECUTAR_WEB_SERVICES', false);

//VARIABLE PARA CONTROLAR SI LA MÁQUINA DE CÓDIGO FUENTE ES LA MISMA MÁQUINA DE LA BBDD
//true -> Están en la MISMA máquina
//false -> Están en DISTINTAS máquinas
define('MAQUINA_CODIGO_MISMA_MAQUINA_BD', false);