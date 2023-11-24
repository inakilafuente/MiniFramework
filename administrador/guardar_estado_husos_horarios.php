<?

include "../lib/globales.php";

session_name(NOMBRE_SESSION); //ahora el nombre lo indicamos en globales
session_start();
?>
<?
$_SESSION['estado_husos_horarios'] = $chHusosHorariosVisible;

?>