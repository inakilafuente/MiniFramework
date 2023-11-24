// unique id of the added file
var totalFilesI = 0;
// current uploading status
var uploading = false;
// The current index of the uploaded file
var currentFileI = 0;
// The file queue
var queue = new Array;
//The ID
var idObject = 0;
//EL PREFIJO DEL NOMBRE DEL ENLACE A MOSTRAR
var prefijoNombreEnlace = '';
//LA SECCION
var seccionAdjunto = '';

var path ='';

function multiupload_init(pathParticular) {
	idObject = document.getElementById('idObject').value;
    seccionAdjunto = document.getElementById('seccionAdjunto').value;
	totalFilesI = document.getElementById('indice_fichero').value;
	currentFileI = totalFilesI;
    path = pathParticular;
	document.getElementById('inputFilename').onchange = function(event) {
		files = this.files;
		addFilesToQueue(files);
		this.value = ''; // Clear the files from the input
	};
}

function multiupload_init_avanzado(pathParticular) {
    idObject = document.getElementById('idObject').value;
    seccionAdjunto = document.getElementById('seccionAdjunto').value;
    prefijoNombreEnlace = document.getElementById('prefijoNombreEnlace').value;
    totalFilesI = document.getElementById('indice_fichero').value;
    currentFileI = totalFilesI;
    path = pathParticular;
    document.getElementById('inputFilename').onchange = function(event) {
        files = this.files;
        addFilesToQueue_avanzado(files);
        this.value = ''; // Clear the files from the input
    };
}

/**
  * Add files to the current upload queue and
  * create an html element for this file
  * @param object list of files
  */
function addFilesToQueue(files) {
	for (var i = 0; i < files.length; i++) {
		queue[totalFilesI] = files[i];
		addHtml = '<tr id="file_'+ totalFilesI +'" class="box">';
		addHtml += '<td class="info" id="info'+ totalFilesI +'" title="'+ idObject + '-'+ (files[i].name).toLowerCase() +'">'
		addHtml += idObject + '-'+ (files[i].name).toLowerCase()+'' +
                    '<span id="icono'+ totalFilesI +'" style=""><img src="../../../lib/ajax_script/img/esperando.gif" width="15" height="11" ' +
                     'alt="Subiendo..."/> </span></td>';
		addHtml += '</tr>';
		document.getElementById('queue').innerHTML += addHtml;
		totalFilesI++;
	}
	// Trigger the upload handler
	upload();
}

/**
 * Add files to the current upload queue and
 * create an html element for this file
 * @param object list of files
 */
function addFilesToQueue_avanzado(files) {
    for (var i = 0; i < files.length; i++) {
        queue[totalFilesI] = files[i];
        addHtml = '<tr id="file_'+ totalFilesI +'" class="box">';
        addHtml += '<td class="info" id="info'+ totalFilesI +'" title="'+ prefijoNombreEnlace + files[i].name +'">'
        addHtml += files[i].name+'' +
            '<span id="icono'+ totalFilesI +'" style=""><img src="../../../lib/ajax_script/img/esperando.gif" width="15" height="11" ' +
            'alt="Subiendo..."/> </span></td>';
        addHtml += '</tr>';
        document.getElementById('queue').innerHTML += addHtml;
        totalFilesI++;
    }
    // Trigger the upload handler
    upload();
}

/**
  * Upload the next file in the queue
  * @return void
  */
function upload() {
	if(uploading == true) {
		return;
	}
	
	if(queue[currentFileI] != undefined) {
		uploading = true;
		
		xhr = new XMLHttpRequest();
		
		// Update progess during upload
		xhr.upload.addEventListener("progress", function (evt) {
			progressHandling(evt, currentFileI);
		}, false);

		xhr.addEventListener("load", function (evt) {

			if(this.responseText == '0' || /ERROR/.test(this.responseText)) {
				document.getElementById('info' + currentFileI).style.background = '';
				document.getElementById('info' + currentFileI).className += ' error';
				document.getElementById('file_' + currentFileI).innerHTML += this.responseText;
                document.getElementById('icono' + currentFileI).style.display="none";
	        }
	        else if(this.responseText == '1') {
				document.getElementById('info' + currentFileI).style.background = '';
	        	document.getElementById('info' + currentFileI).className += ' complete';
	        }
	        else {
				document.getElementById('info' + currentFileI).style.background = '';
	        	document.getElementById('info' + currentFileI).className += ' complete';
                document.getElementById('info' + currentFileI).innerHTML = '<a class="copyright" href="'+ path + document.getElementById('info' + currentFileI).title +'" title="Ver adjunto" target="_blank">'+ document.getElementById('info' + currentFileI).title + '</a>';
		        document.getElementById('file_' + currentFileI).innerHTML += this.responseText;
	        }
			uploading = false;
			currentFileI++;
			upload(); // Continue to the next file
		}, false);
		
		// Post the file to the server
		xhr.open('POST', 'accion_ficheros.php');
		var formData = new FormData();
		formData.append("_method", 'POST');
		formData.append("numFile",currentFileI);
		formData.append("filename", queue[currentFileI]);
		formData.append("idObject", idObject);
        formData.append("seccionAdjunto", seccionAdjunto);
		xhr.send(formData);
	}
	return;
}

function delete_file(deleteID){

    xhr = new XMLHttpRequest();

    xhr.addEventListener("load", function (evt) {
        document.getElementById('file_' + deleteID).style.display = 'none';
        //document.getElementById('file_' + deleteID).innerHTML += this.responseText;
    }, false);

    // Post the file to the server
    xhr.open('POST', 'accion_ficheros.php');
    var formData = new FormData();
    formData.append("_method", 'POST');
    formData.append("accion", "Borrar");
    formData.append("filename", document.getElementById('info' + deleteID).title);
    formData.append("idObject", idObject);
    formData.append("seccionAdjunto",seccionAdjunto);
    xhr.send(formData);

}

/**
  * Status handeling for uploading a file
  * @param event current event of the upload
  * @param int the unique index of the html elements
  * @return void
  */
function progressHandling(evt, elementIndex) {
    if(evt.lengthComputable){
    	completion = Math.round(100 / evt.total * evt.loaded); //  + ' / ' + e.total;
        document.getElementById('info' + elementIndex).style.background = 'linear-gradient(90deg, rgba(80, 222, 81, 0.12) '+completion+'%, #eee '+completion+'%)';
    }
}