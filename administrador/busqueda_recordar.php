<?
//BUSQUEDA_RECORDAR
//Modulo que inicializa o recupera variables post por zona, para que los campos de búsqueda le aparezcan
//al usuario inicializados con la última selección al ir navegando por las diferentes pantallas de la aplicación.
if ($PaginaRecordar <> ""):
    if ($recordar_busqueda == "1" && $BuscadorMultiple != 1):
        //CARGA ULTIMA BUSQUEDA
        if (is_array($administrador->ultimaBusquedaNombre[$PaginaRecordar])):
            $numeroPost = count( (array)$administrador->ultimaBusquedaNombre[$PaginaRecordar]);
        endif;
        for ($countPost = 0; $countPost < $numeroPost; $countPost++):
            $nombreVariablePost = $administrador->ultimaBusquedaNombre[$PaginaRecordar][$countPost];
            if (($nombreVariablePost == "exportar_excel") || ($nombreVariablePost == "exportar_excel_gestion_ubicaciones") || ($nombreVariablePost == "exportar_csv") || ($nombreVariablePost == "exportar_formulario_seguro")):
                continue;
            endif;
            $$nombreVariablePost = $administrador->ultimaBusquedaValor[$PaginaRecordar][$countPost];
        endfor;
    else:
        //ALMACENA VARIABLES ACTUALEES
        //ELIMINO ACTUALES VALORES DEL ARRAY
        unset($administrador->ultimaBusquedaNombre[$PaginaRecordar]);
        unset($administrador->ultimaBusquedaValor[$PaginaRecordar]);
        //CARGO VARIABLES POST
        $numeroPost  = count( (array)$_POST);
        $tagsPost    = array_keys( (array)$_POST); // obtiene los nombres de las varibles
        $valoresPost = array_values($_POST);// obtiene los valores de las varibles
        for ($countPost = 0; $countPost < $numeroPost; $countPost++):
            //ALMACENA BUSQUEDA ACTUAL
            $administrador->ultimaBusquedaNombre[$PaginaRecordar][$countPost] = $tagsPost[$countPost];
            $administrador->ultimaBusquedaValor[$PaginaRecordar][$countPost]  = $valoresPost[$countPost];
        endfor;
    endif;


    //CASO DE BUSCADORES CON OPCION DE BUSQUEDA MULTIPLE
    if ($BuscadorMultiple == "1"):

        //CARGO VARIABLES POST
        if ($recordar_busqueda_multiple == "1"):
            $numeroPost  = count( (array)$_POST);
            $tagsPost    = array_keys( (array)$_POST); // obtiene los nombres de las varibles
            $valoresPost = array_values($_POST);// obtiene los valores de las varibles
            for ($countPost = 0; $countPost < $numeroPost; $countPost++):
                //ALMACENA BUSQUEDA ACTUAL
                $administrador->ultimaBusquedaNombreMultiple[$paginaReferer][$PaginaRecordar][$countPost] = $tagsPost[$countPost];
                $administrador->ultimaBusquedaValorMultiple[$paginaReferer][$PaginaRecordar][$countPost]  = $valoresPost[$countPost];
            endfor;
        else:
            //CARGA ULTIMA BUSQUEDA
            $numeroPost = is_array($administrador->ultimaBusquedaNombreMultiple[$paginaReferer][$PaginaRecordar]) ? count( (array)$administrador->ultimaBusquedaNombreMultiple[$paginaReferer][$PaginaRecordar]) : 0;
            for ($countPost = 0; $countPost < $numeroPost; $countPost++):
                $nombreVariablePost = $administrador->ultimaBusquedaNombreMultiple[$paginaReferer][$PaginaRecordar][$countPost];
                if (($nombreVariablePost == "exportar_excel") || ($nombreVariablePost == "exportar_excel_gestion_ubicaciones") || ($nombreVariablePost == "exportar_csv") || ($nombreVariablePost == "exportar_formulario_seguro")):
                    continue;
                endif;
                $$nombreVariablePost = $administrador->ultimaBusquedaValorMultiple[$paginaReferer][$PaginaRecordar][$countPost];
            endfor;
        endif;

    endif;
    if ((!isset($Buscar)) || ($Buscar == "No")):
        //ALMACENA VARIABLES ACTUALEES
        //ELIMINO ACTUALES VALORES DEL ARRAY
        $url = "https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        unset($administrador->ultimaBusquedaNombreMultiple[$url]);
        unset($administrador->ultimaBusquedaValorMultiple[$url]);

    endif;
endif;