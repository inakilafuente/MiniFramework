<? header('Content-Type: text/html;charset=windows-1252');

// PATHS DE LA WEB
$pathRaiz     = "./";
$pathManuales = "../../";//var/www/html/manuales/docs
$pathClases   = "../";

// DOWNLOAD
// Descarga un archivo mediante el servidor, comprobando que el usuario puede acceder a el
require_once $pathClases . "lib/basedatos.php";
require_once $pathClases . "lib/auxiliar.php";
require_once $pathClases . "lib/administrador.php";
require_once $pathClases . "lib/html.php";

session_start();
$esDescargaDocumento = true;
include $pathRaiz . 'seguridad_admin.php';

$logeado = $_SESSION["AUTH_ACCIONA_SGA_ADMINISTRADOR"] == "OK" && !empty(trim( (string)$administrador->ID_ADMINISTRADOR));

// TODO, esto ya lo hace seguridad_admin, quitar?
if ($logeado):
    $key           = isset($_GET['key']) ? $_GET['key'] : '';
    $rutaDocumento = $_GET['ruta'];
    if (!empty($rutaDocumento)):
        switch ($key):
            case 'ejemplos':
                $rutaAbs = $path_raiz . 'documentos_app/';
                break;
            case 'manual':
                $rutaAbs = $pathManuales . 'manuales/docs/';
                break;
            default:
                $rutaAbs = $path_raiz . 'documentos/';
        endswitch;

        $rutaArchivoAbs = $rutaAbs . $rutaDocumento;

        if (file_exists($rutaArchivoAbs)):
            $mime = mime_content_type($rutaArchivoAbs);
            header('Content-Description: File Transfer');
            // SI ES TEXTO PLANO O CSV, FORZAMOS LA DESCARGA
            if ($mime == 'text/plain' || $mime == 'text/csv'):
                header('Content-Disposition: attachment; filename="' . basename($rutaArchivoAbs) . '"');
            else:
                header('Content-Disposition: filename="' . basename($rutaArchivoAbs) . '"');
            endif;
//            header('Content-Disposition: inline; filename="' . basename($rutaArchivoAbs) . '"');
//            header('Content-Disposition: attachment; filename="' . basename($rutaArchivoAbs) . '"');
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Type: ' . mime_content_type($rutaArchivoAbs));
            header('Content-Length: ' . filesize($rutaArchivoAbs));
            ob_clean();
            flush(); // Flush system output buffer
            readfile($rutaArchivoAbs);
            exit();
        else:
            $basename  = basename($rutaArchivoAbs);
            $msjeError = " '$basename'";
            $html->PagError("NoEncuentraDocumento");
        endif;
    else:
        $html->PagError("NoEncuentraDocumento");
    endif;
else:
    //GUARDO LA PAGINA A REDIRIGIR AL USUARIO TRAS LOGUEARSE
    $_SESSION["PAGINA_A_REDIRIGIR_TRAS_LOGUEARSE"] = $_SERVER["HTTP_REFERER"];

    //si no existe, envio a la página de autentificacion
    header("Refresh:0; url=: $url_web_adm");
    //ademas salgo de este script
    exit();
endif;