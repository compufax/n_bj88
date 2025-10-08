var tablabusqueda = null;
function menu(modulo, cvemenu, archivo, nombre, registro){
	$('#modulo_'+modulo).addClass('collapsed');
	$('#collapse_'+modulo).removeClass('show');
	//$('#nombrepagina').html(nombre);
	//atcr(archivo, '', 0, 0);
	$.ajax({
	  url: 'guardarregistro.php',
	  type: "POST",
	  data: {
		menu: cvemenu,
		registro: registro,
		cveusuario: $('#cveusuario').val(),
		cveplaza: $('#cveplaza').val()
	  },
		success: function(data) {
			if (data == 'error'){
				sweetAlert('', 'Necesita seleccionar una plaza', 'error');
			}
			else{
				document.formaprincipal.cvemenu.value=cvemenu;
				$('#nombrepagina').html(nombre);
				atcr(archivo, '', 0, 0);
			}
		}
	});
}


function atcr(archivo, target, cmd, registro){
	waitingDialog.show();
	document.formaprincipal.action=archivo;
	document.formaprincipal.cmd.value=cmd;
	document.formaprincipal.reg.value=registro;
	document.formaprincipal.target=target;
	if(target == ''){

		var fd = new FormData(document.getElementById("formaprincipal"));
		$.ajax({
			url: archivo,
			type: "POST",
			data: fd,
			processData: false,  // tell jQuery not to process the data
	  		contentType: false,   // tell jQuery not to set contentType
			success: function(data) {
				waitingDialog.hide();
				if(IsJsonString(data)){
					data = $.parseJSON(data);
					if(data.error==1){
						alert(data.mensaje);
					}
				}
				else{
					$('#contenedorprincipal').html('');
					$('#contenedorprincipal').append(data);
				}
				document.formaprincipal.cmd.value='';
				document.formaprincipal.reg.value='';
			}
		});
	}
	else{
		document.formaprincipal.submit();
		document.formaprincipal.cmd.value='';
		document.formaprincipal.reg.value='';
		waitingDialog.hide();
	}
	
}

function cambiarPlaza(cveplaza, elemento){
	document.formaprincipal.cveplaza.value=cveplaza;
	if(cveplaza>0){
		$('#nombreplazatop').html(elemento.html());
		archivo = document.formaprincipal.action.split('/');
		if(archivo[archivo.length-1] != 'principal.php'){
			atcr(document.formaprincipal.action, '', 0, 0);
		}
		recargar_menu(cveplaza);
	}
	else{
		window.location='principal.php';
	}
}

function recargar_menu(cveplaza){
	$.ajax({
		url: 'menu.php',
		type: "POST",
		data: {
			cveplaza: cveplaza,
			cveusuario: $('#cveusuario').val()
		},
		success: function(data) {
			$('.menuprincipal').remove();
			$(data).insertAfter('#premenu');
		}
	});
}

function mostrar_busqueda(archivo, campo_id, cmd, callback, campo_callback){
	var callback = callback || '';
	var campo_callback = campo_callback || '';
	if($('#tablabusqueda').length > 0){
		tablabusqueda.destroy();
	}
	$.ajax({
		url: archivo,
		type: "POST",
		data: {
			cmd: cmd,
			campo_id: campo_id,
			cveempresa: $('#cveempresa').val(),
			callback: callback,
			campo_callback: campo_callback
		},
		success: function(data) {
			$('#bodybusquedas').html('');
			$('#bodybusquedas').append(data);
			$('#modalbusquedas').modal('show');	
		}
	});
}

function obtener_datos_registro(archivo, registro_id){
	var registro = null;
	$.ajax({
		url: archivo,
		type: "POST",
		dataType: "json",
		async: false,
		data: {
			cmd: 3000,
			registro_id: registro_id
		},
		success: function(data) {
			registro = data;
		}
	});
	return registro;
}


function mostrar_bloqueo(){
    $("#panelbloqueo").css("display","block");
}

function cerrar_bloqueo(){
	$("#panelbloqueo").css("display","none");
}

$(document).ajaxStart(function(){
    $("#panelbloqueo").removeClass('oculto');
}).ajaxStop(function(){
    $("#panelbloqueo").addClass('oculto');
});

function IsJsonString(str) {
    try {
       JSON.parse(str);
    } catch (e) {
       return false;
    }
    return true;
}

function cambiar_check(campo){
	if(document.getElementById(campo).checked){
		document.getElementById(campo+'_h').value=1;
	}
	else{
		document.getElementById(campo+'_h').value=0;
	}
}
	
function mueveReloj(){
	cadena=document.getElementById("relojprincipal").innerHTML;
	if(cadena.substr(11,1)=="0")
		var	horas = parseInt(cadena.substr(12,1));
	else
		var	horas = parseInt(cadena.substr(11,2));
	if(cadena.substr(14,1)=="0")
		var	minuto = parseInt(cadena.substr(15,1));
	else
		var	minuto = parseInt(cadena.substr(14,2));
	if(cadena.substr(17,1)=="0")
		var	segundo = parseInt(cadena.substr(18,1));
	else
		var	segundo = parseInt(cadena.substr(17,2));
	var	anio = parseInt(cadena.substr(0,4));
	if(cadena.substr(5,1)=="0")
		var	mes = parseInt(cadena.substr(6,1));
	else
		var	mes = parseInt(cadena.substr(5,2));
	if(cadena.substr(8,1)=="0")
		var	dia = parseInt(cadena.substr(9,1));
	else
		var	dia = parseInt(cadena.substr(8,2));
	segundo++;
	if (segundo==60) {
		segundo=0;
		minuto++;
		if (minuto==60) {
			minuto=0;
			horas++;
			if (horas==24) {
				horas=0;
				dia++;
				if((dia==31 && (mes==4 || mes==6 || mes==9 || mes==11)) || (dia==32 && (mes==1 || mes==3 || mes==5 || mes==7 || mes==8 || mes==10 || mes==12)) || (dia==29 && mes==2 && (anio%4)!=0) || (dia==30 && mes==2 && (anio%4)==0)){
					dia=1;
					mes++;
				}
				if(mes==13){
					mes=1;
					anio++;
				}
			}
		}
	}
	if(horas<10) horas="0"+parseInt(horas);
	if(minuto<10) minuto="0"+parseInt(minuto);
	if(segundo<10) segundo="0"+parseInt(segundo);
	if(dia<10) dia="0"+parseInt(dia);
	if(mes<10) mes="0"+parseInt(mes);
	horaImprimible = anio+"-"+mes+"-"+dia+" "+horas+":"+minuto+ ":"+segundo;

	document.getElementById("relojprincipal").innerHTML = horaImprimible;

	setTimeout("mueveReloj()",1000)
}


/*
waitingDialog.show(); Sin Mensaje
waitingDialog.show('Custom message'); Con Mensaje
waitingDialog.show('Custom message', {dialogSize: 'sm', progressType: 'warning'}); Con mensaje y opciones
waitingDialog.show('Dialog with callback on hidden',{onHide: function () {alert('Callback!');}}); Con Mensaje y callback al cerrar
*/

var waitingDialog = waitingDialog || (function ($) {
    'use strict';

	// Creating modal dialog's DOM
	var $dialog = $(
		'<div class="modal" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog" aria-hidden="true" style="padding-top:15%; overflow-y:visible;">' +
		'<div class="modal-dialog modal-m">' +
		'<div class="modal-content">' +
			'<div class="modal-header"><h3 style="margin:0;"></h3></div>' +
			'<div class="modal-body">' +
				'<div class="progress progress-striped active" style="margin-bottom:0;"><div class="progress-bar progress-bar-striped" style="width: 100%"></div></div>' +
			'</div>' +
		'</div></div></div>');

	return {
		/**
		 * Opens our dialog
		 * @param message Custom message
		 * @param options Custom options:
		 * 				  options.dialogSize - bootstrap postfix for dialog size, e.g. "sm", "m";
		 * 				  options.progressType - bootstrap postfix for progress bar type, e.g. "success", "warning".
		 */
		show: function (message, options) {
			// Assigning defaults
			if (typeof options === 'undefined') {
				options = {};
			}
			if (typeof message === 'undefined') {
				message = 'Cargando';
			}
			var settings = $.extend({
				dialogSize: 'm',
				progressType: 'animated',
				onHide: null, // This callback runs after the dialog was hidden
				onShow: null
			}, options);


			// Configuring dialog
			$dialog.find('.modal-dialog').attr('class', 'modal-dialog').addClass('modal-' + settings.dialogSize);
			$dialog.find('.progress-bar').attr('class', 'progress-bar progress-bar-striped');
			if (settings.progressType) {
				$dialog.find('.progress-bar').addClass('progress-bar-' + settings.progressType);
			}
			$dialog.find('h3').text(message);
			// Adding callbacks
			if (typeof settings.onHide === 'function') {
				$dialog.off('hidden.bs.modal').on('hidden.bs.modal', function (e) {
					settings.onHide.call($dialog);
				});
			}
			if (typeof settings.onHide === 'function') {
				$dialog.off('hidden.bs.modal').on('hidden.bs.modal', function (e) {
					settings.onHide.call($dialog);
				});
				$dialog.on('shown.bs.modal', function (e) {
					settings.onHide.call($dialog);
				});
			}
			
			// Opening dialog
			$dialog.modal('show');
		},
		/**
		 * Closes dialog
		 */
		hide: function () {
			$dialog.modal('hide');
		}
	};

})(jQuery);
//mueveReloj();