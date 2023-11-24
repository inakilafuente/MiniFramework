<?
error_reporting(E_ALL);
// PATHS DE LA WEB
$pathRaiz   = "./";
$pathClases = "../";

// DOWNLOAD
// Descarga un archivo mediante el servidor, comprobando que el usuario puede acceder a el
require_once $pathClases . "lib/basedatos.php";
require_once $pathClases . "lib/auxiliar.php";
require_once $pathClases . "lib/administrador.php";
require_once $pathClases . "lib/html.php";

session_start();
include $pathRaiz . 'seguridad_admin.php';

$logeado = $_SESSION["AUTH_ACCIONA_SGA_ADMINISTRADOR"] == "OK" && !empty(trim( (string)$administrador->ID_ADMINISTRADOR));

if ($logeado):
    $rutaDocumento = $_GET['ruta'];
    if (!empty($rutaDocumento)):
        $rutaAbsDoc = $path_raiz . 'documentos/';
        $rutaArchivoAbs = $rutaAbsDoc . $rutaDocumento;

        if (file_exists($rutaArchivoAbs)):
            header('Content-Description: File Transfer');
//            header('Content-Disposition: inline; filename="' . basename($rutaArchivoAbs) . '"');
//            header('Content-Disposition: attachment; filename="' . basename($rutaArchivoAbs) . '"');
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Type: ' .  mime_content_type($rutaArchivoAbs));
            header('Content-Length: ' . filesize($rutaArchivoAbs));
            ob_clean();
            flush(); // Flush system output buffer
            readfile($rutaArchivoAbs);
            exit();
        else:
            $html->PagError("NoEncuentraDocumento");
        endif;
    else:
        $html->PagError("NoEncuentraDocumento");
    endif;
endif;