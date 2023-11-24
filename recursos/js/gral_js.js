function MM_preloadImages() { //v3.0
    var d = document;
    if (d.images) {
        if (!d.MM_p) d.MM_p = new Array();
        var i, j = d.MM_p.length, a = MM_preloadImages.arguments;
        for (i = 0; i < a.length; i++)
            if (a[i].indexOf("#") != 0) {
                d.MM_p[j] = new Image;
                d.MM_p[j++].src = a[i];
            }
    }
}


function MM_swapImgRestore() { //v3.0
    var i, x, a = document.MM_sr;
    for (i = 0; a && i < a.length && (x = a[i]) && x.oSrc; i++) x.src = x.oSrc;
}

function MM_findObj(n, d) { //v4.01
    var p, i, x;
    if (!d) d = document;
    if ((p = n.indexOf("?")) > 0 && parent.frames.length) {
        d = parent.frames[n.substring(p + 1)].document;
        n = n.substring(0, p);
    }
    if (!(x = d[n]) && d.all) x = d.all[n];
    for (i = 0; !x && i < d.forms.length; i++) x = d.forms[i][n];
    for (i = 0; !x && d.layers && i < d.layers.length; i++) x = MM_findObj(n, d.layers[i].document);
    if (!x && d.getElementById) x = d.getElementById(n);
    return x;
}

function MM_swapImage() { //v3.0
    var i, j = 0, x, a = MM_swapImage.arguments;
    document.MM_sr = new Array;
    for (i = 0; i < (a.length - 2); i += 3)
        if ((x = MM_findObj(a[i])) != null) {
            document.MM_sr[j++] = x;
            if (!x.oSrc) x.oSrc = x.src;
            x.src = a[i + 2];
        }
}

function AbreVentana(URL_Ventana, OpcionesVentana, Ancho, Alto,
                     Centrada, PosX, PosY) {

    if (Centrada) {
        PosX = (screen.availWidth - Ancho) / 2;
        PosY = (screen.availHeight - Alto) / 2;
    }

    if (OpcionesVentana == '')
        OpcionesVentana = 'width=' + Ancho;
    else
        OpcionesVentana += ',width=' + Ancho;

    OpcionesVentana += ',height=' + Alto + ',left=' + PosX
        + ',top=' + PosY;

    window.open(URL_Ventana, "", OpcionesVentana);
    return false;
}
//-->


function blinkIt() {
    if (!document.all) return;
    else {
        for (i = 0; i < document.all.tags('blink').length; i++) {
            s = document.all.tags('blink')[i];
            s.style.visibility = (s.style.visibility == 'visible') ? 'hidden' : 'visible';
        }
    }
}
setInterval('blinkIt()', 500);


//DESPLIEGUE DE MENUS
function desplegarHijos(idPadre) {
    jQuery('td.lineaderecha tr.' + idPadre + ' td.hijos').toggle();
}
function desplegarSubHijos(idPadre) {
    jQuery('td.hijos tr.' + idPadre + ' td.subhijos').toggle();
}
function desplegarSubSubHijos(idPadre) {
    jQuery('td.hijos tr.' + idPadre + ' td.subsubhijos').toggle();
}


//PARA PONER EL FOCO EN EL SIGUIENTE CONTROL
function siguiente_control(control) {
    marcar = false;
    var siguiente = control.parents('.tablaFiltros').find('input[type=text],select').filter(function (index, controlFind) {
            if (marcar) {
                marcar = false;
                return true;
            }
            if (controlFind == control[0]) {
                marcar = true;
            }
            return false;
        }
    )[0];

    // SI EXISTE EL SIGUIENTE, LE HACEMOS FOCUS
    if (siguiente) {
        siguiente.focus();
    }
}

//FUNCION PARA APLICAR FORMATOS AL CONVERTIR ENTRE UNIDAD DE MEDIDA Y COMPRA
function number_format(a, b, c, d) {
    a = Math.round(a * Math.pow(10, b)) / Math.pow(10, b);
    e = a + '';
    f = e.split('.');
    if (!f[0]) {
        f[0] = '0';
    }
    if (!f[1]) {
        f[1] = '';
    }
    if (f[1].length < b) {
        g = f[1];
        for (i = f[1].length + 1; i <= b; i++) {
            g += '0';
        }
        f[1] = g;
    }
    if (d != '' && f[0].length > 3) {
        h = f[0];
        f[0] = '';
        for (j = 3; j < h.length; j += 3) {
            i = h.slice(h.length - j, h.length - j + 3);
            f[0] = d + i + f[0] + '';
        }
        j = h.substr(0, (h.length % 3 == 0) ? 3 : (h.length % 3));
        f[0] = j + f[0];
    }
    c = (b <= 0) ? '' : c;
    return f[0] + c + f[1];
}

function ActualizarClaseComboValorTodosDiferenciado(combo, valorTodos, claseValorTodos, claseRestoValores) {
    jQuery(combo).removeClass(claseValorTodos);
    jQuery(combo).removeClass(claseRestoValores);

    if (combo.value == valorTodos) {
        jQuery(combo).addClass(claseValorTodos);
    }
    else {
        jQuery(combo).addClass(claseRestoValores);
    }
}

//FUNCION PARA MOSTRAR/NO MOSTRAR LA VENTANA DE OPCIONES
function ventana_opciones(contenedor, e) {
    var lista = contenedor.parentNode.getElementsByTagName("ul")[0];

    //OBTENEMOS DONDE SE MOSTRARÁ LA VENTANA
    var windowHeight = window.innerHeight;
    var windowWidth  = window.innerWidth;
    if (lista.style.display == "" || lista.style.display == "none") {
        lista.style.display = "block";
        var lista_height = lista.offsetHeight;
        var lista_width = lista.offsetWidth;
        lista.style.display = "none";

        //PARA QUE NO HAGA SCROLL EN EL FONDO DE LA PAGINA
        if ((e.clientY + lista_height) > windowHeight) {
            lista.style.top = (15 - lista_height) + "px";
        }
        else {
            lista.style.top = (1) + "px";
        }

        //PARA QUE NO HAGA SCROLL A LA DERECHA DE LA PAGINA DE LA PAGINA
        if (lista_width > e.clientX && (lista_width + 20 < (windowWidth - e.clientX ))) {
            lista.style.right = (1 - lista_width) + "px";
        }
    }

    //MOSTRAMOS/NO MOSTRAMOS
    if (lista.style.display == "block") {
        lista.style.display = "none";
    }
    else {
        lista.style.display = "block";
    }

    contenedor.parentNode.addEventListener("mouseleave", function ocultar_ventana(event) {
        lista.style.display = "none";
    }, false);
}

//FUNCION PARA HACER EFECTO LUPA A UNA IMAGEN
function efecto_lupa(contenedor, e, pathImagenGrande, altura, anchura) {
    var divLupa = contenedor.parentNode.getElementsByClassName("lupa")[0];
    var imageLupa = divLupa.getElementsByTagName("img")[0];

    imageLupa.setAttribute('src', pathImagenGrande);

    //PARA QUE NO HAGA SCROLL EN EL FONDO DE LA PAGINA
    var windowHeight = window.innerHeight;
    if ((e.clientY + altura) > windowHeight) {
        imageLupa.style.top = (15 - altura) + "px";
    }
    else {
        imageLupa.style.top = (5) + "px";
    }

    imageLupa.style.left = (50) + "px";

    //MOSTRAMOS/NO MOSTRAMOS
    if (divLupa.style.display == "block") {
        divLupa.style.display = "none";
    }
    else {
        divLupa.style.display = "block";
    }

    contenedor.addEventListener("mouseleave", function ocultar_ventana(event) {
        divLupa.style.display = "none";
    }, false);
}

//FUNCION PARA MOSTRAR/NO MOSTRAR LA VENTANA CON INFORMACION (TEXTO)
function ventana_informacion(contenedor, e) {
    var lista = contenedor.parentNode.getElementsByTagName("ul")[0];

    //VARIABLE PARA CONTROLAR SI CENTRAMOS LA VENTANA, SI NO SE CENTRA VALDRA 15, SI NO 1 (PARA LA ALTURA)
    var sumaCoordY = 15;
    var signo = -1;
    //VARAIBLE PARA NO CENTRAR EN EJE X E Y
    var estaCentrado = false;
    //OBTENEMOS DONDE SE MOSTRARÁ LA VENTANA HORIZONTALMENTE
    //var windowWidth = window.innerWidth;
    if (lista.style.display == "" || lista.style.display == "none") {
        lista.style.display = "block";
        var lista_width = lista.offsetWidth;
        lista.style.display = "none";
        //PARA QUE NO HAGA SCROLL A LA IZQUIERDA DE LA PANTALLA
        var windowWidth = window.innerWidth;
        //NO USAR SCREEN X, DISTINTO ENTRE NAVEGADORES

        if (lista_width > e.clientX && (lista_width + 20 < (windowWidth - e.clientX ))) {
            lista.style.right = (1 - lista_width) + "px";
        }
        else if (lista_width < e.clientX) {
            lista.style.right = (contenedor.offsetWidth ) + "px";
        }
        else {
            sumaCoordY = 1;
            widthMedia = lista_width - e.clientX;
            lista.style.right = -widthMedia + "px";
            signo = 1;
            estaCentrado = true;
        }
    }

    //OBTENEMOS DONDE SE MOSTRARÁ LA VENTANA EJE Y
    var windowHeight = window.innerHeight;

    lista.style.display = "block";
    var lista_height = lista.offsetHeight;
    lista.style.display = "none";

    if (lista.style.display == "" || lista.style.display == "none") {
        //PARA QUE NO HAGA SCROLL EN EL FONDO DE LA PAGINA
        if (((e.clientY + lista_height) >= windowHeight) && (lista_height < e.clientY)) {
            //if (lista_height > e.clientY && (lista_height + 20 < (windowHeight - e.clientY ))) {
            //if ( (lista_height + 20 < (windowHeight - e.clientY ))) {
            lista.style.top = (sumaCoordY - lista_height) + "px";
            //    lista.style.top = (15-lista_height) + "px";
        }
        else if (lista_height < (windowHeight - e.clientY) || estaCentrado) {
            //else {
            lista.style.top = (signo * 15) + "px";
            //lista.style.top = (15) + "px";

        } else {
            lista.style.top = (signo - (Math.abs(lista_height - e.clientY))) + "px";
        }
    }

    //MOSTRAMOS/NO MOSTRAMOS
    if (lista.style.display == "block") {
        lista.style.display = "none";
    }
    else {
        lista.style.display = "block";
    }

    contenedor.parentNode.addEventListener("mouseleave", function ocultar_ventana(event) {
        lista.style.display = "none";
    }, false);
}


function addObligatorioRellenar() {
    jQuery('.ObligatorioRellenar').keyup(function () {
        jQuery('.ObligatorioRellenar').each(function (index) {
            if (jQuery(this).is(':disabled')) {
                jQuery(this).css("background-color", "#EBEBE4");
            }
            else {
                if (jQuery(this).val() == '') {
                    jQuery(this).css("background-color", "pink");
                }
                else {
                    jQuery(this).css("background-color", "white");
                }
            }
        })
    });

    jQuery('.ObligatorioRellenar').change(function () {
        jQuery('.ObligatorioRellenar').each(function (index) {
            if (jQuery(this).is(':disabled')) {
                jQuery(this).css("background-color", "#EBEBE4");
            }
            else {
                if (jQuery(this).val() == '') {
                    jQuery(this).css("background-color", "pink");
                }
                else {
                    jQuery(this).css("background-color", "white");
                }
            }
        })
    });

    jQuery('.ObligatorioRellenar').each(function (index) {
        if (jQuery(this).is(':disabled')) {
            jQuery(this).css("background-color", "#EBEBE4");
        }
        else {
            if (jQuery(this).val() == '') {
                jQuery(this).css("background-color", "pink");
            }
            else {
                jQuery(this).css("background-color", "white");
            }
        }
    })

}


jQuery(document).ready(function () {
    jQuery('#tdMenuVisible').mouseleave(function (event) {
        if (!jQuery('#chMenuSiempreVisible').is(':checked')) {
            jQuery('#tdMenuVisible').hide();
            jQuery('#tdMenuOculto').show();
        }
    });
    jQuery('#tdMenuOculto').mouseover(function (event) {
        jQuery('#tdMenuVisible').show();
        jQuery('#tdMenuOculto').hide();
    });
    if (jQuery('#chMenuSiempreVisible').is(':checked')) {
        jQuery('#tdMenuVisible').show();
        jQuery('#tdMenuOculto').hide();
    }

    addObligatorioRellenar();

});

function ComprobarInputObligatorio(input) {

    if (input.is(':disabled')) {
        input.css("background-color", "#EBEBE4");
    }
    else {
        if (input.val() == '') {
            input.css("background-color", "pink");
        }
        else {
            input.css("background-color", "white");
        }
    }
}

//AL SELECCIONAR UN ALMACEN ACTUALIZA LAS BUSQUEDAS DE UBICACION POR ESE ALMACEN
/**
 *
 * @param tipo
 * @param pathRaiz
 */
function actualizadorAlmacenes(tipo, pathRaiz) {

    var actualizador = "actualizador_ubicaciones";
    var desplegable = "desplegable_ubicaciones";
    var contenedor = "#contenedor_actualizador_ubicaciones";
    var ubicaciones = "#ubicaciones";
    var NombreCampo = "NombreCampo=Ubicacion";
    if (tipo == "Destino") {
        actualizador = "actualizador_ubicaciones_destino";
        desplegable = "desplegable_ubicaciones_destino";
        contenedor = "#contenedor_actualizador_ubicaciones_destino";
        ubicaciones = "#ubicaciones_destino";
        NombreCampo = "NombreCampo=UbicacionDestino";
    }


    //ACTUALIZO URL FANCYBOX PARA UBICACIONES
    jQuery(ubicaciones).attr("href", pathRaiz + "buscadores_maestros_restringidos/busqueda_ubicacion.php?AlmacenarId=0&tipoAcceso=Lectura&almacenarCentro=1&almacenarAlmacen=1&" + NombreCampo + "&idAlmacen=" + jQuery("#idAlmacen" + tipo).val());

    //BORRO DIV QUE CONTIENE ASOCIADO LA PRIMERA CARGA DE BUSCADOR AJAX, PARA CREAR OTRO CON EL BUSCADOR AJAX FILTRADO POR ALMACEN
    jQuery('#' + actualizador).remove();
    jQuery(contenedor).append("<div class='entry' align='left' id='" + actualizador + "'></div>");
    new Ajax.Autocompleter('txUbicacion' + tipo, actualizador, pathRaiz + 'buscadores_maestros_restringidos/resp_ajax_ubicacion.php?AlmacenarId=0&tipoAcceso=Lectura&' + NombreCampo + '&idAlmacen=' + jQuery('#idAlmacen' + tipo).val(), {
        method: 'post',
        indicator: desplegable,
        minChars: '2',
        afterUpdateElement: function (textbox, valor) {
            jQuery('#botonBuscar').focus();
            jQuery(textbox).val(jQuery(valor).children('a').attr('alt'));
            jQuery('#idUbicacion' + tipo).val(jQuery(valor).children('a').attr('rev'));
            jQuery('#idCentro' + tipo).val(jQuery(valor).children('a').attr('idCentro'));
            jQuery('#txCentro' + tipo).val(jQuery(valor).children('a').attr('refCentro'));
            jQuery('#idAlmacen' + tipo).val(jQuery(valor).children('a').attr('idAlmacen'));
            jQuery('#txAlmacen' + tipo).val(jQuery(valor).children('a').attr('refAlmacen'));
        }
    });
}

function actualizadorMaquinas(tipo, pathRaiz) {

    var actualizador = "actualizador_ubicaciones";
    var desplegable = "desplegable_ubicaciones";
    var contenedor = "#contenedor_actualizador_ubicaciones";
    var ubicaciones = "#ubicaciones";
    var NombreCampo = "NombreCampo=Ubicacion";
    //ACTUALIZO URL FANCYBOX PARA UBICACIONES
    jQuery(ubicaciones).attr("href", pathRaiz + "buscadores_maestros/busqueda_ubicacion.php?AlmacenarId=0&soloMaquina=1&" + NombreCampo + "&idAlmacen=" + jQuery("#idAlmacen" + tipo).val());

    //BORRO DIV QUE CONTIENE ASOCIADO LA PRIMERA CARGA DE BUSCADOR AJAX, PARA CREAR OTRO CON EL BUSCADOR AJAX FILTRADO POR ALMACEN
    jQuery('#' + actualizador).remove();
    jQuery(contenedor).append("<div class='entry' align='left' id='" + actualizador + "'></div>");
    new Ajax.Autocompleter('txUbicacion', actualizador, pathRaiz + 'buscadores_maestros/resp_ajax_ubicacion.php?AlmacenarId=0&soloMaquina=1&' + NombreCampo + '&idAlmacen=' + jQuery('#idAlmacen' + tipo).val(), {
        method: 'post',
        indicator: desplegable,
        minChars: '2',
        afterUpdateElement: function (textbox, valor) {
            jQuery('#botonBuscar').focus();
            jQuery(textbox).val(jQuery(valor).children('a').attr('alt'));
            jQuery('#idUbicacion').val(jQuery(valor).children('a').attr('rev'));
            jQuery('#idAlmacen' + tipo).val(jQuery(valor).children('a').attr('idAlmacen'));
            jQuery('#txAlmacen' + tipo).val(jQuery(valor).children('a').attr('refAlmacen'));
        }
    });
}

//AL SELECCIONAR UNA INSTALACION (ALMACEN) SE LE PASA AL AJAX DE ACTIVIDAD
/**
 *
 * @param tipo
 * @param pathRaiz
 */
function actualizadorActividadesInstalacion(pathRaiz) {

    var actualizador = "actualizador_actividades";
    var desplegable = "desplegable_actividades";
    var contenedor = "#contenedor_actualizador_actividades";
    var actividades = "#actividades";
    var NombreCampo = "NombreCampo=Actividad";


    //ACTUALIZO URL FANCYBOX PARA UBICACIONES
    jQuery(actividades).attr("href", pathRaiz + "buscadores_maestros/busqueda_actividad_montaje.php?AlmacenarId=1&tipoAcceso=Lectura&" + NombreCampo + "&idAlmacen=" + jQuery("#idAlmacen").val());

    //BORRO DIV QUE CONTIENE ASOCIADO LA PRIMERA CARGA DE BUSCADOR AJAX, PARA CREAR OTRO CON EL BUSCADOR AJAX FILTRADO POR ALMACEN
    jQuery('#' + actualizador).remove();
    jQuery(contenedor).append("<div class='entry' align='left' id='" + actualizador + "'></div>");
    new Ajax.Autocompleter('txActividad', actualizador, pathRaiz + 'buscadores_maestros/resp_ajax_actividad_montaje.php?AlmacenarId=1&tipoAcceso=Lectura&' + NombreCampo + '&idAlmacen=' + jQuery('#idAlmacen').val(), {
        method: 'post',
        indicator: desplegable,
        minChars: '2',
        afterUpdateElement: function (textbox, valor) {
            jQuery('#idActividad').val(jQuery(valor).children('a').attr('alt'));
            jQuery('#idFase').val(jQuery(valor).children('a').attr('idFase'));
            jQuery('#txFase').val(jQuery(valor).children('a').attr('txFase'));
            jQuery('#idSubFase').val(jQuery(valor).children('a').attr('idSubFase'));
            jQuery('#txSubFase').val(jQuery(valor).children('a').attr('txSubFase'));
            jQuery('#idEtapa').val(jQuery(valor).children('a').attr('idEtapa'));
            jQuery('#txEtapa').val(jQuery(valor).children('a').attr('txEtapa'));
            jQuery('#idSubEtapa').val(jQuery(valor).children('a').attr('idSubEtapa'));
            jQuery('#txSubEtapa').val(jQuery(valor).children('a').attr('txSubEtapa'));
        }
    });
}


/**
 * @param idFormulario Formulario al que hacen referencia los elementos a limpiar
 * @param arrElementos marcas para añadir excepcion de limpiar filtro. Ej [".no_limpiable", "[data-name]"], no limpiaria los elementos con la clase
 *  "no_limpiable", ni los elementos con el atributo data-name
 */
function limpiarFiltros(idFormulario, arrElementos) {
    //LIMPIO LOS FORMULARIOS (EXCEPTO AQUELLOS QUE ESTAN MARCADOS CON LAS EXCEPCIONES PASADAS POR PARAMETRO)
    //RECORRO ARRAY PARA RECOGER EXCEPCIONES QUE EVITAN BORRADO
    var excepciones = "";
    for (var i = 0; i < arrElementos.length; i++) {
        if (excepciones == "") {
            excepciones = arrElementos[i];
        } else {
            excepciones += ", " + arrElementos[i];
        }
    }
    jQuery("#" + idFormulario + " input[type='text']").not(excepciones).val("");
    jQuery("#" + idFormulario + " input[type='hidden']").not(excepciones).val("");
    jQuery("#" + idFormulario + " input[type='checkbox']").not(excepciones).prop("checked", false);

    //LIMPIO LOS SELECT (EXCEPTO AQUELLOS QUE ESTAN MARCADOS COMO NO LIMPIABLES)
    jQuery("#" + idFormulario + " select").not(excepciones).val ("");

    //VACIO SELECTS
    jQuery("#" + idFormulario + " select").not(excepciones).each(function () {
        var elemento = jQuery(this).attr('id')
        var elementoSinSelect = elemento.replace('select', '');
        //FORMA DE ASIGNAR VALORES A LOS NUEVOS SELECTS
        jQuery("#" + elemento).multipleSelect("setSelects", []);
        jQuery("#" + elementoSinSelect).val('')
    });
}
function limpiarFiltrosSinIDForm(arrElementos) {
    //LIMPIO LOS FORMULARIOS (EXCEPTO AQUELLOS QUE ESTAN MARCADOS CON LAS EXCEPCIONES PASADAS POR PARAMETRO)
    //RECORRO ARRAY PARA RECOGER EXCEPCIONES QUE EVITAN BORRADO
    var excepciones = "";
    for (var i = 0; i < arrElementos.length; i++) {
        if (excepciones == "") {
            excepciones = arrElementos[i];
        } else {
            excepciones += ", " + arrElementos[i];
        }
    }
    jQuery(":input").not(excepciones).val ("");

    //LIMPIO LOS SELECT (EXCEPTO AQUELLOS QUE ESTAN MARCADOS COMO NO LIMPIABLES)
    jQuery("select").not(excepciones).val ("");

    //VACIO SELECTS
    jQuery("select").not(excepciones).each(function () {
        var elemento = jQuery(this).attr('id')
        var elementoSinSelect = elemento.replace('select', '');
        //FORMA DE ASIGNAR VALORES A LOS NUEVOS SELECTS
        jQuery("#" + elemento).multipleSelect("setSelects", []);
        jQuery("#" + elementoSinSelect).val('')
    });
}

/**
 * mostrar elementos html buscados por clase
 * @param clase
 */
function mostrarElementosPorClase(clase) {
    jQuery("." + clase).each(function () {
        jQuery(this).fadeIn(1000);
    });
}

/**
 * esconder elementos html buscados por clase
 * @param clase
 */
function ocultarElementosPorClase(clase) {
    jQuery("." + clase).each(function () {
        jQuery(this).fadeOut();
    });
}

/**
 *
 * @param tipo hacer referncia a la pastilla de datos filtros que queremos que se oculte muestre
 */

function mostrarOcultarPastillasFiltros(tipo) {
    if (tipo == 'todasPastillas' || tipo == "todosFiltros") {
        var nombre = "Filtros";
        if (tipo == "todasPastillas") {
            nombre = "Pastillas";

        }
        jQuery(".ver" + nombre).each(function () {
            jQuery(this).show();
        });
        jQuery(".ch" + nombre).each(function () {
            jQuery(this).val('1');

        });
        jQuery(".btn" + nombre).each(function () {
            jQuery(this).removeClass('botonGris');
            jQuery(this).addClass('botonAzul');

        });

    } else if (tipo == 'ocultarPastillas' || tipo == "ocultarFiltros") {

        var nombre = "Filtros";
        if (tipo == "ocultarPastillas") {
            nombre = "Pastillas";

        }
        jQuery(".ver" + nombre).each(function () {

            jQuery(this).hide();
        });
        jQuery(".ch" + nombre).each(function () {
            jQuery(this).val('0');

        });
        jQuery(".btn" + nombre).each(function () {
            jQuery(this).removeClass('botonAzul');
            jQuery(this).addClass('botonGris');

        });

    } else {
        if (!jQuery('.ver' + tipo).is(':visible')) {
            jQuery('.ver' + tipo).show();
            jQuery('#chVer' + tipo).val('1');
            jQuery('#btn' + tipo).removeClass('botonGris');
            jQuery('#btn' + tipo).addClass('botonAzul');
        } else {
            jQuery('.ver' + tipo).hide();
            jQuery('#chVer' + tipo).val('0');
            jQuery('#btn' + tipo).removeClass('botonAzul');
            jQuery('#btn' + tipo).addClass('botonGris');
        }
    }

}

function mostrarEsconderElementoFechasClase(elemento, clase) {
    if (jQuery('#Contraer' + elemento).is(':visible')) {

        ocultarElementosPorClase(clase);
        jQuery("#Contraer" + elemento).hide();
        jQuery("#Expandir" + elemento).show();
    } else {
        mostrarElementosPorClase(clase);
        jQuery("#Contraer" + elemento).show();
        jQuery("#Expandir" + elemento).hide();
    }

    return false;
}

function mostrarEsconderElementoCheckClase(clase, check) {
    if (jQuery('input[name=' + check + ']').prop('checked')) {
        mostrarElementosPorClase(clase);
    } else {
        ocultarElementosPorClase(clase);
    }

    return false;
}

function mostrarEsconderElementoCheckClases(clases, check) {
    if (jQuery('input[name=' + check + ']').prop('checked')) {
        for (i = 0; i < clases.length; i++) {
            mostrarElementosPorClase(clases[i]);
        }
    } else {
        for (i = 0; i < clases.length; i++) {
            ocultarElementosPorClase(clases[i]);
        }
    }

    return false;
}

//MARCA TODOS LOS ELEMENTOS INPUT DE TIPO CHECKBOX QUE TIENEN LA CLASE PASADA POR ARGUMENT
function marcarElementosCheckClase(clase) {
    jQuery("." + clase + ":input:checkbox").each(function () {
        jQuery(this).prop('checked', true);

    });
    return false;
}
//DESMARCA TODOS LOS ELEMENTOS INPUT DE TIPO CHECKBOX QUE TIENEN LA CLASE PASADA POR ARGUMENT
function desmarcarElementosCheckClase(clase) {
    jQuery("." + clase + ":input:checkbox").each(function () {
        jQuery(this).prop('checked', false);

    });
    return false;
}

//SEGUN SI ESTA MARCADO EL CHECKBOX QUE EJECUTA LA ACCION SE
// MARCA O DESMARCA TODOS LOS ELEMENTOS INPUT DE TIPO CHECKBOX QUE TIENEN LA CLASE PASADA POR ARGUMENT
function marcarDesmarcarElementoCheckClase(elemento, clase) {
    if (jQuery(elemento).prop('checked')) {
        marcarElementosCheckClase(clase);
    } else {
        desmarcarElementosCheckClase(clase);
    }
    return false;
}

//HABILITA TODOS LOS ELEMENTOS INPUT DE TIPO CHECKBOX QUE TIENEN LA CLASE PASADA POR ARGUMENT
function habilitarElementosCheckClase(clase) {
    jQuery("." + clase + ":input:checkbox").each(function () {
        jQuery(this).prop('disabled', true);

    });
    return false;
}
//DESHABILITA TODOS LOS ELEMENTOS INPUT DE TIPO CHECKBOX QUE TIENEN LA CLASE PASADA POR ARGUMENT
function deshabilitarElementosCheckClase(clase) {
    jQuery("." + clase + ":input:checkbox").each(function () {
        jQuery(this).prop('disabled', false);

    });
    return false;
}

//SEGUN SI ESTA HABILITADO EL CHECKBOX QUE EJECUTA LA ACCION SE
// HABILITA O DESHABILITA TODOS LOS ELEMENTOS INPUT DE TIPO CHECKBOX QUE TIENEN LA CLASE PASADA POR ARGUMENT
function habilitarDeshabilitarElementoCheckClase(elemento, clase) {
    if (jQuery(elemento).prop('checked')) {
        habilitarElementosCheckClase(clase);
    } else {
        deshabilitarElementosCheckClase(clase);
    }
    return false;
}


//GENERAR ENLACES PARA LAS ESTRUCTURAS DE CONSTRUCCION
function cambiarEnlaceDirectoBuscadorMaestro(url, nombreID, pantalla) {
    window.open(url + jQuery("#" + nombreID).val() + pantalla, "_black");
}

/**
 * Funcion para seleccionar todos los elementos del listado (checkbox)
 * @param chSel
 * @param nombreArraySelec nombre del array de checkbox a marcar ej. chSelec
 */
function seleccionarTodasListados(chSel, nombreArraySelec) {

    //RECORRO TODOS LOS INPUT DE TIPO CHECKBOS DE LA PANTALLA
    jQuery(":input:checkbox").each(function () {
        //MARCO O DESMARCO EL ARRAY DE CHECKS QUE YO SELECCIONO
        if ((jQuery(chSel).prop('checked')) && ( (this.name.substring(0, (this.name.indexOf("[") + 1)) == nombreArraySelec + "[")  )) {
            jQuery(this).prop('checked', true);
        }
        else if ((!jQuery(chSel).prop('checked')) && ( (this.name.substring(0, (this.name.indexOf("[") + 1)) == nombreArraySelec + "[")  )) {
            jQuery(this).prop('checked', false);
        }
    });
}

/**
 * Funcion para comprobar si se han seleccionado elementos del listado
 * @param nombreArraySelec nombre del array de checkbox a marcar ej. chSele
 * @returns {string}
 */
function comprobarSiSeleccionada(nombreArraySelec) {

    var lista_elementos = '';
    var coma = '';

    //RECORRO TODOS LOS INPUT DE TIPO CHECKBOS DE LA PANTALLA
    jQuery(":input:checkbox").each(function () {
        //GUARDO AQUELLOS ELEMENTOS QUE ME INTERESAN
        if ((this.name.substr(0, (this.name.indexOf("[") + 1)) == nombreArraySelec + "[") && jQuery(this).prop('checked')) {
            elemento = this.name.substring(this.name.indexOf("[") + 1, this.name.indexOf("]"));
            lista_elementos = lista_elementos + coma + elemento;
            coma = ',';
        }
    });
    return lista_elementos;

}

//FUNCION PARA MOSTRAR EL NOMBRE DEL ARCHIVO DE UN INPUT FILE
function conseguirNombreInput(nombre) {
    //QUITAMOS C:\FAKEPATH\ Y DEJAMOS SOLO EL NOMBRE DEL ARCHIVO
    var nombreArchivo = jQuery("#" + nombre).val().split('\\').pop();

    //RETIRAMOS EL TEXTO DEL ARCHIVO SI SELECCIONAMOS OTRO
    if (nombreArchivo != "") {
        jQuery("span[id='" + nombre + "']").remove();
    }

    //CREAMOS LA ETIQUETA Y MOSTRAMOS EL TEXTO CON EL ARCHIVO ELEGIDO CON UN ESTILO DEFINIDO
    if (nombreArchivo != "" && nombre != "") {
        jQuery("#" + nombre).after("<span class='fuenteExaminar' id=" + nombre + "> " + nombreArchivo + "</span>");
    }
}

//FUNCION PARA QUE LOS INPUTS DE TIPO FILE CAMBIEN DE ESTILO
function InputFileBotonExaminarVerde(idioma) {
    jQuery(document).ready(function () {
        //DEFINIMOS EL ARRAY
        var listaInputs = new Array();

        //CREAMOS EL ESTILO PARA EL BOTON DE BORRAR
        jQuery('input[name=btBorrar]').attr('class', 'botonExaminar');

        //ESCONDEMOS EL BOTON DE EXAMINAR
        jQuery('input[type="file"]').css("display", "none");

        //BUSCAMOS TODOS LOS INPUT FILE
        jQuery('input:file').each(function () {
            //RECOGEMOS CADA NOMBRE DE CADA INPUT
            var elementoNombre = jQuery(this).attr('name');

            //METEMOS CADA INPUT DENTRO DEL ARRAY
            listaInputs.push(elementoNombre);
        });

        //LOS RECORREMOS Y AÑADIMOS EL ATRIBUTO A CADA INPUT CON ONCHANGE PARA LLAMAR A LA FUNCION
        for (var i = 0, len = listaInputs.length; i < len; i++) {
            //VERIFICAMOS SI EL BOTON ESTA DESHABILITADO
            var deshabilitado = jQuery("input:file[name='" + listaInputs[i] + "']").prop('disabled');
            var texto = '';
            if (idioma == 'ESP') {
                texto = 'Examinar...';
            } else {
                texto = 'Scan...';
            }

            //CAMBIAMOS EL BOTON DE CADA UNO DE LOS INPUT
            if (deshabilitado == false) {
                jQuery("input:file[name='" + listaInputs[i] + "']").before("<label for='" + listaInputs[i] + "' class='botonExaminar'>" + texto + "</label>");
            } else {
                jQuery("input:file[name='" + listaInputs[i] + "']").before("<label for='" + listaInputs[i] + "' class='botonExaminarDeshabilitado'>" + texto + "</label>");
            }

            //ASIGNAMOS EL NOMBRE COMO IDENTIFICADOR PARA CADA INPUT
            jQuery("input:file[name='" + listaInputs[i] + "']").attr('id', "" + listaInputs[i] + "");
            //LE DAMOS EL ATRIBUTO ONCHANGE PARA LLAMAR A LA FUNCION
            jQuery("#" + listaInputs[i]).attr('onchange', 'conseguirNombreInput("' + listaInputs[i] + '")');
        }
    });
}


//PARA QUE LOS BOTONES SE MUESTREN CUANDO LA PANTALLA SEA PEQUENA
jQuery(window).load(function () {

    //var $elem = document.getElementById("contenedorbotones");
    var $array_elem = document.getElementsByClassName("contenedorbotones");
    for (auxnum = 0; auxnum < $array_elem.length; auxnum++) {
        var $elem = $array_elem[auxnum];

        jQuery(window).resize(function () {
            //SI LA PANTALLA ES MAS GRANDE QUE LA VENTANA
            if (jQuery(window).width() < jQuery(document).width()) {
                $elem.style.right = (jQuery(document).width() - jQuery(window).width() - jQuery(window).scrollLeft()) + "px";
                //SI SE NOS HA SALIDO DE LA CELDA, LO COLOCAMOS EN SU ESTADO INICIAL
                if ($elem.offsetLeft < -50) {
                    $elem.style.right = (1) + "px";
                }
            }
            else {
                $elem.style.right = (1) + "px";
            }

        }).trigger('resize');

        jQuery(window).scroll(function () {
            //SI LA PANTALLA ES MAS GRANDE QUE LA VENTANA
            if (jQuery(window).width() < jQuery(document).width()) {
                $elem.style.right = (jQuery(document).width() - jQuery(window).width() - jQuery(window).scrollLeft()) + "px";
                //SI SE NOS HA SALIDO DE LA CELDA, LO COLOCAMOS EN SU ESTADO INICIAL
                if ($elem.offsetLeft < -50) {
                    $elem.style.right = (1) + "px";
                }
            }
            else {
                $elem.style.right = (1) + "px";
            }

        }).trigger('scroll');
    }


    jQuery(window).resize(function () {
        var $array_elem2 = document.getElementsByClassName("contenedorTablaFiltros");
        for (auxnum = 0; auxnum < $array_elem2.length; auxnum++) {
            var $elem2 = $array_elem2[auxnum];
            //SI LA PANTALLA ES MAS GRANDE QUE LA VENTANA
            if (jQuery(window).width() < jQuery(document).width()) {
                //SI EL ELEMENTO PADRE YA NO SE MUESTRA POR LA IZQUIERDA, SEGUIMOS AL SCROLL QUITANDO LA POSICION DE LA TABLA PADRE
                if ($elem2.parentNode.getBoundingClientRect().left < 0) {
                    $elem2.style.left = (jQuery(window).scrollLeft() - $elem2.parentNode.offsetLeft + 5) + "px";
                }
                else {
                    //SI NO DEJAMOS EL LEFT a 1
                    $elem2.style.left = (1) + "px";
                }
            }
            else {
                $elem2.style.left = (1) + "px";
            }
        }

    }).trigger('resize');

    jQuery(window).scroll(function () {
        var $array_elem2 = document.getElementsByClassName("contenedorTablaFiltros");
        for (auxnum = 0; auxnum < $array_elem2.length; auxnum++) {
            var $elem2 = $array_elem2[auxnum];
            //SI LA PANTALLA ES MAS GRANDE QUE LA VENTANA
            if (jQuery(window).width() < jQuery(document).width()) {
                if ($elem2.offsetWidth + 100 < jQuery(window).width()) {
                    //SI EL ELEMENTO PADRE YA NO SE MUESTRA POR LA IZQUIERDA, SEGUIMOS AL SCROLL QUITANDO LA POSICION DE LA TABLA PADRE
                    if ($elem2.parentNode.getBoundingClientRect().left < 0) {
                        $elem2.style.left = (jQuery(window).scrollLeft() - $elem2.parentNode.offsetLeft + 5) + "px";
                    }
                    else {
                        //SI NO DEJAMOS EL LEFT a 1
                        $elem2.style.left = (1) + "px";
                    }
                }
            }
            else {
                $elem2.style.left = (1) + "px";
            }
        }

    }).trigger('scroll');


    //CLASE contenedorCentrado PARA QUE UN ELEMENTO SE MANTANGA CENTRADO AUNQUE LA PAGINA SEA GRANDE
    var $array_elem3 = document.getElementsByClassName("contenedorCentrado");
    if ($array_elem3.length > 0) {

        //DEFINIMOS EL RESIZE PARA LOS ELEMENTOS contenedorCentrado DE LA PAGINA
        jQuery(window).resize(function () {

            //RECORREMOS LOS ELEMENTOS
            for (auxnum = 0; auxnum < $array_elem3.length; auxnum++) {
                var $elem3 = $array_elem3[auxnum];

                //SI LA PANTALLA ES MAS GRANDE QUE LA VENTANA
                if (jQuery(window).width() < jQuery(document).width()) {
                    //GUARDAMOS TAMAÑO PANTALLA Y POSICION POR SI NOS SALIMOS
                    Pantallaprevia = jQuery(document).width();
                    leftPrevia = $elem3.style.left;

                    //SI LA ANCHULA DEL OBJETO ES MAS GRANDE QUE EL DE LA VENTANA
                    if (jQuery(window).width() < $elem3.offsetWidth) {

                        //PONEMOS EL OBJETO EN LA POSICION DEL SCROLL
                        $elem3.parentNode.align = "left";
                        $elem3.style.left = 1 + jQuery(window).scrollLeft() + "px";
                    }
                    else {
                        //LE PONEMOS ALINEACION IZQUIERDA Y CALCULAMOS LA MITAD DE LA PANTALLA PARA UBICAR EL OBJETO
                        $elem3.parentNode.align = "left";
                        $elem3.style.left = ((jQuery(window).width() - $elem3.offsetWidth) / 2 + jQuery(window).scrollLeft()) + "px";
                    }

                    //SI NOS HEMOS SALIDO DE LA PANTALLA, LO DEJAMOS COMO ESTABA
                    if (Pantallaprevia < jQuery(document).width()) {
                        //LO PONEMOS A LA DERECHA
                        $elem3.style.left = leftPrevia;
                    }
                }
                else {
                    $elem3.parentNode.align = "center";
                    $elem3.style.left = (1) + "px";
                }
            }

        }).trigger('resize');


        //DEFINIMOS EL SCROLL PARA LOS ELEMENTOS contenedorCentrado DE LA PAGINA
        jQuery(window).scroll(function () {

            //RECORREMOS LOS ELEMENTOS
            for (auxnum = 0; auxnum < $array_elem3.length; auxnum++) {
                var $elem3 = $array_elem3[auxnum];

                //SI LA PANTALLA ES MAS GRANDE QUE LA VENTANA
                if (jQuery(window).width() < jQuery(document).width()) {

                    //GUARDAMOS TAMAÑO PANTALLA Y POSICION POR SI NOS SALIMOS
                    Pantallaprevia = jQuery(document).width();
                    leftPrevia = $elem3.style.left;

                    //SI LA ANCHULA DEL OBJETO ES MAS GRANDE QUE EL DE LA VENTANA
                    if (jQuery(window).width() < $elem3.offsetWidth) {

                        //PONEMOS EL OBJETO EN LA POSICION DEL SCROLL
                        $elem3.parentNode.align = "left";
                        $elem3.style.left = 1 + jQuery(window).scrollLeft() + "px";
                    }
                    else {
                        //LE PONEMOS ALINEACION IZQUIERDA Y CALCULAMOS LA MITAD DE LA PANTALLA PARA UBICAR EL OBJETO
                        $elem3.parentNode.align = "left";
                        $elem3.style.left = ((jQuery(window).width() - $elem3.offsetWidth) / 2 + jQuery(window).scrollLeft()) + "px";
                    }

                    //SI NOS HEMOS SALIDO DE LA PANTALLA, LO DEJAMOS COMO ESTABA
                    if (Pantallaprevia < jQuery(document).width()) {
                        //LO PONEMOS A LA DERECHA
                        $elem3.style.left = leftPrevia;
                    }
                }
                else {
                    $elem3.parentNode.align = "center";
                    $elem3.style.left = (1) + "px";
                }
            }

        }).trigger('scroll');

    }//FIN CLASE contenedorCentrado PARA QUE UN ELEMENTO SE MANTANGA CENTRADO AUNQUE LA PAGINA SEA GRANDE


});// FIN PARA QUE LOS BOTONES SE MUESTREN CUANDO LA PANTALLA SEA PEQUENA


//COMPORTAMIENTO DE LOS NUEVOS DESPLEGABLES AL PULSAR LAS FLECHAS DE ARRIBA Y ABAJO (NO HAGA SCROLL LA WEB)
jQuery(window).ready(function () {
    var keys = {};
    jQuery(".ms-choice").bind("keydown", function (e) {
        keys[e.keyCode] = true;
        switch (e.keyCode) {
            case 38:
            case 13:
            case 40:
                e.preventDefault();
                break; // Arrow keys
            //  case 32:e.preventDefault(); break;
            default:
                break; // do not block other keys

        }
    });
    jQuery(".ms-drop").bind("keydown", function (e) {
        keys[e.keyCode] = true;
        switch (e.keyCode) {
            case 38:
            case 13:
            case 40:
            case 9:// Arrow keys
            //case 32:e.preventDefault(); break;
            default:
                break; // do not block other keys

        }
    });
    //jQuery("textarea.copypaste").on('paste', function () {
    //    var element = this;
    //
    //    setTimeout(function () {
    //        str = jQuery(element).val();
    //        jQuery(element).val(str.replace(/\n/g,"|"));
    //    }, 200);
    //});
});