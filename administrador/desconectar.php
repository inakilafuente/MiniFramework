<?
// PATHS WEB
$pathClases = "../";

require_once $pathClases . "lib/basedatos.php";
require_once $pathClases . "lib/administrador.php";
$url_pagina_web = $url_web_adm;

session_start();
session_destroy();

Header("location: index.php");
exit;
?>
