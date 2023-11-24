;(function($) {	
	$.ContextMenu = function(){};
	$.ContextMenu.Current = null;
	$.fn.ContextMenu = function(opciones){
		if (!$(this).length) {
			return this;
		}
		
		
		
		//LE ESTABLEZCO LAS OPCIONES POR DEFECTO
		opciones = $.extend({}, $.fn.ContextMenu.defaults, opciones);
		
		//OCULTO LOS LISTADOS
		$(this).children('.ContextMenuContent').hide();
		
		$(this).each(function(index, elem){
			//OBTENGO LAS OPCIONES
			opcionesElem = $.extend({}, $.fn.ContextMenu.defaults, opciones);
			
			//SACO EL BOTON, MENU Y CONTENEDOR Y SE LO ASIGNO
			opcionesElem.boton = $(elem).children('.ContextMenuTrigger');
			opcionesElem.boton.data('ContextMenuElement',$(elem));
			opcionesElem.menu = $('<div class="ContextMenuElementWrapper"></div>').append($('<div class="ContextMenuDiv"></div>').append($(elem).children('.ContextMenuContent').show())).appendTo($(elem));
			opcionesElem.menu.data('ContextMenuElement',$(elem));
			opcionesElem.contenedor = $(elem);
			if(navigator.appName == 'Microsoft Internet Explorer'){
				$('<span class="IEShadow"></span>').appendTo(opcionesElem.menu);
			}
			
			//ESTABLEZCO LA ANCHURA
			$(elem).find('.ContextMenuContent').css('width', opcionesElem.anchoMenu+'px');
			
			//ESTABLEZCO LA POSICION DEL MENU
			pos = opcionesElem.boton.offset();
			if(opcionesElem.posicion == 'bottom-left'){
				opcionesElem.menu.css('margin-left', parseInt(opcionesElem.boton.width() - opcionesElem.menu.width()) + 'px');
			}else if(opcionesElem.posicion == 'bottom-right'){
				
			}else if(opcionesElem.posicion == 'top-left'){
				opcionesElem.menu.css('margin-left', parseInt(opcionesElem.boton.width() - opcionesElem.menu.width()) + 'px');
				opcionesElem.menu.css('margin-top', parseInt(-opcionesElem.boton.height() - opcionesElem.menu.height()) + 'px');
			}else if(opcionesElem.posicion == 'top-right'){
				opcionesElem.menu.css('margin-top', parseInt(-opcionesElem.boton.height() - opcionesElem.menu.height()) + 'px');
			}
			
			//LE AGREGO LAS OPCIONES AL ELEMENTO
			$(elem).data('ContextMenu',opcionesElem);
			
			
			//AGREGO LA FUNCION DEL CLICK AL BOTON
			opcionesElem.boton.unbind('click.cm').bind('click.cm', _cm_click);
		});
		
		//OCULTO LOS LISTADOS
		$(this).children('.ContextMenuElementWrapper').hide();
		
		
	}
	
	_cm_click = function(e) {
		e.preventDefault();
		
		//SI HAY ALGUNO MOSTRADO LO OCULTO
		_ocultarMenu(e, $.ContextMenu.Current);
		
		opt = $(this).data('ContextMenuElement').data('ContextMenu');
		
		//SI HAY ALGUN MOVIMIENTO ACTIVO NO HAGO NADA
		if(opt.estado == 'running'){ return; }
			
		if(opt.estado == 'hide'){ //LO MUESTRO
			_mostrarMenu(e, this);
		}else{	//LO OCULTO
			_ocultarMenu(e, this);
		}
		
		return;
	}
	
	_mostrarMenu = function(e, elem){
		//alert('mostrar');
		//SI NO HAY ELEMENTO COJO EL ACTUAL
		if(!elem || elem == 'undefined'){
			elem = $.ContextMenu.Current;
		}
		//SI AUN ASÍ NO HAY ELEMENTO NO HAGO NADA
		if(!elem || elem == 'undefined'){ return; }
		
		//CARGO LAS OPCIONES
		opt = $(elem).data('ContextMenuElement').data('ContextMenu');
		
		//SI YA ESTÄ EN MARCHA NO HAGO NADA
		if(opt.estado == 'running'){ return;}
		
		//CAMBIO EL ESTADO Y MUESTRO EL MENU
		$(elem).data('ContextMenuElement').data('ContextMenu').onBeforeShow();
		$(elem).data('ContextMenuElement').data('ContextMenu').estado = 'running';
		$(elem).data('ContextMenuElement').data('ContextMenu').menu.slideDown(opt.duracionSlide, function(){
			//alert('mostrado');
			//AGREGO EL EVENTO PARA OCULTAR LOS MENUS
			$(document).one('click.cmclose', _ocultarMenu);
			$(this).data('ContextMenuElement').data('ContextMenu').estado = 'show';
			$(this).data('ContextMenuElement').data('ContextMenu').onShow();
		});
		
		//ESTABLEZCO EL MENU ACTUAL COMO EL MOSTRADO
		$.ContextMenu.Current = elem;
	}
	
	_ocultarMenu = function(e, elem){
		//alert('ocultar');
		//SI NO HAY ELEMENTO COJO EL ACTUAL
		if(!elem || elem == 'undefined'){
			elem = $.ContextMenu.Current;
		}
		//SI AUN ASÍ NO HAY ELEMENTO NO HAGO NADA
		if(!elem || elem == 'undefined'){ return; }
		
		//CARGO LAS OPCIONES
		opt = $(elem).data('ContextMenuElement').data('ContextMenu');
		
		//SI YA ESTÄ EN MARCHA NO HAGO NADA
		if(opt.estado == 'running'){ return;}
		
		//CAMBIO EL ESTADO Y OCULTO EL MENU
		$(document).unbind('click.cmclose');
		$(elem).data('ContextMenuElement').data('ContextMenu').onBeforeHide();
		$(elem).data('ContextMenuElement').data('ContextMenu').estado = 'running';
		$(elem).data('ContextMenuElement').data('ContextMenu').menu.slideUp(opt.duracionSlide,function(){
			$(this).data('ContextMenuElement').data('ContextMenu').estado = 'hide';
			$(elem).data('ContextMenuElement').data('ContextMenu').onHide();
		});
		
		//ESTABLEZCO EL MENU ACTUAL A NULO
		$.ContextMenu.Current = null;
	}
	
	//OPCIONES POR DEFECTO
	$.fn.ContextMenu.defaults = {
		boton 				: false,
		menu 					: false,
		contenedor		: false,
		
		posicion			: 'bottom-left',
		duracionSlide	: 1,
		anchoMenu			: 150,
				
		estado				: 'hide',
		
		onShow				: function(){},
		onBeforeShow	: function(){},
		onHide				: function(){},
		onBeforeHide	: function(){}
	};

})(jQuery);