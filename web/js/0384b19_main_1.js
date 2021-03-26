function controlli(dominio,cplnumber,pixel){

var response = new Array({stato:true});
var response = {stato:true, "objs":[]};
//[{"stato":"true", "messaggio":''}];

	if(dominio.val()!=''){
		if(!is_valid_url(dominio.val())){
			response.stato = false;
			response.objs.push ( {"id":dominio.attr('id'),"messaggio":generaErrorBox('Inserire una URL valida')} );
		}
	}else{
		response.stato = false;
		response.objs.push ( {"id":dominio.attr('id'),"messaggio":generaErrorBox('Il campo url &egrave; obbligatorio')} );
	}
	if(cplnumber.val()==''){
		response.stato = false;
		response.objs.push ( {"id":cplnumber.attr('id'),"messaggio":generaErrorBox('Il campo CPL &egrave; obbligatorio')} );
	}
	if(pixel.val()!=''){
		var test = (/(<img|<iframe|<script)/i).test(pixel.val());
		if(!test){
			response.stato = false;
			response.objs.push ( {"id":pixel.attr('id'),"messaggio":generaErrorBox('Il codice pixel deve essere in uno dei seguenti formati: img, iframe o script')} );
		}
	}else{
		response.stato = false;
		response.objs.push ( {"id":pixel.attr('id'),"messaggio":generaErrorBox('Il campo Codice pixel &egrave; obbligatorio')} );
	}
	return response;
}
function generaErrorBox(errore){
	var html = '<small class="errorBox">';
	html += errore;
	html += '</small>';
	return html;
}

    function is_valid_url(url) 
{
   return /^(http(s)?:\/\/)?(www\.)?[a-z0-9]+([\-\.]{1}[a-z0-9]+)*\.[a-z]{2,5}(:[0-9]{1,5})?(\/.*)?$/.test(url);
}
 
function cleanError(){
	var campi = new Array('dominio','cplnumber','pixel');
	$.each(campi, function(index, id) {
		$('#'+id + '_errore').html('');
	});
}