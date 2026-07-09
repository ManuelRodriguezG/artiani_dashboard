var delets=0;
var language='';
var typeTable='';
var typeList='';

function saveCode(element){
     var codeTable = '';
    var action='';
    if(element.id=='saveTable'){
        console.log($('#previewTable')[0].innerHTML);
        codeTable=$('#previewTable')[0].innerHTML;
    }else if(element.id=='updateTable'){
        console.log($('#previewEditable')[0].innerHTML);
        codeTable=$('#previewEditable')[0].innerHTML;
    }
    console.log(codeTable)
    $('#itinerario-tour').text(codeTable);
    $('.modalTable').modal('hide');
}

function actionShow(element){
    $('#contTable').html('');
    $('#previewEditable').html('');
    $('#editTable').css('display','none');
    $('.titleContsCreate').css('display','none');
    //$('.buildTable').css('display','none');
    $('.controlsCreateList').css('display','none');
    $('.edit').css('display','none');
    $('.contPersonalize').css('display','none');
    var idTour = $('select[id=select-tour-edit]').val();
    console.log(idTour);
    var lang=$('select[id=select-language-content]').val();;
    var type='';
    /*if(element.id=='ESpagina'){
        console.log(element.id);
        lang='ES';
        type='pagina';
        
        
    }else if(element.id=='EScorreo'){
        lang='ES';
        type='correo';
        console.log(element.id);
    }else if(element.id=='ENpagina'){
        lang='EN';
        type='pagina';
        console.log(element.id);
    }else if(element.id=='ENcorreo'){
        lang='EN';
        type='correo';
        console.log(element.id);
    }else if(element.id=='listES'){
        lang='ES';
        type='list';
        typeList=true;
    }else if(element.id=='listEN'){
        lang='EN';
        type='list';
        typeList=true;
    }*/
    language=lang;
    typeTable=type;
    console.log(element.id);
    console.log($('#'+element.id).hasClass('itinerario'));
    if($('#'+element.id).hasClass('itinerario')){
        searchTable(lang,type,idTour);
    }else if($('#'+element.id).hasClass('list')){
        searchList(lang,type,idTour);
    }
    
}
function copyCode(element){
    var codigoACopiar = '';
    if(element.id=='copyCodeTableU'){
        codigoACopiar = document.getElementById('previewEditable');
    }else if(element.id=='copyCodeTableC'){
        codigoACopiar = document.getElementById('previewTable');
    }else if(element.id=='copyCodeList'){
        codigoACopiar = document.getElementById('previewList');
    }
    console.log(document.getElementById('previewEditable').innerHTML);
    
        
    $('#contCode').text(codigoACopiar.childNodes[0].outerHTML);
  
    var node = document.getElementById('contCode');
    console.log(codigoACopiar);
    console.log(codigoACopiar.childNodes[0].outerHTML);
    var seleccion = document.createRange();
   
    console.log(seleccion);
    console.log(node);
    var f=seleccion.selectNodeContents(node);
    console.log(f);
    window.getSelection().removeAllRanges();
    window.getSelection().addRange(seleccion);
    var res = document.execCommand('copy');
    console.log(res);
    window.getSelection().removeRange(seleccion);
}
function actionShowList(element){
    if(element.id=='listES'){
        lang='ES';
        typeList=''
    }else if(element.id=='listEN'){
        
    }
}
function showMenu1(element){
    console.log($('#lista1')[0]);
    if($('#lista1')[0].style.display=='block'){
        $('#lista1').css('display','none');
    }else{
        if($('#lista11')[0].style.display=='block'){
            $('#lista11').css('display','none');
        }
        $('#lista1').css('display','block');
    }
    
}
function showMenu2(element){
    console.log($('#lista1')[0]);
    if($('#lista11')[0].style.display=='block'){
        $('#lista11').css('display','none');
    }else{
        if($('#lista1')[0].style.display=='block'){
            $('#lista1').css('display','none');
        }
        $('#lista11').css('display','block');
    }
    
}
//personalize();
//**********************************Create and Edit Table****************************************//
function addRow(element){
	//longitud de caracteres del id de la tabla
	var longid = element.id.length;
	//id de la tabla que se esta modificando
	var idTable = element.id.substr(6,longid);
	console.log(idTable);
	//format id row = table+'row'+i
	var rowsTable = document.getElementsByClassName('rowTable');
	console.log(rowsTable[0].cells.length);
	//cantidad de filas que se encuentran en la tabla+1 para agregar una nueva
	var longRows = rowsTable.length+1;
	if (delets>0) {
		longRows= longRows + delets;
	}
	console.log(delets);

	//cantidad de columnas en esa tabla
	var longCols =rowsTable[0].cells.length;
	console.log(longCols);
	//codigo para agregar row
	$('#'+idTable).append('<tr id="'+idTable+'row'+longRows+'" class="rowTable"></tr>');
	//for para agregar cols
	for (var i = 1; i <= longCols; i++) {
		
		//agregar columna eliminar
		if(i==longCols){
			$('#'+idTable+'row'+longRows).append('<td id="delete'+idTable+'row'+longRows+'col'+i+'" class="colTable"></td>');
			//$('#delete'+idTable+'row'+longRows+'col'+i).append('<i class="fas fa-times " id="'+idTable+'row'+longRows+'" style="font-size: 33px;color: red;padding: 4px;" onclick="deleteRow(this);"></i> ');
			$('#delete'+idTable+'row'+longRows+'col'+i).append('<button type="button" id="'+idTable+'row'+longRows+'" style="margin:5px;margin-left: 10px;border-radius:100%;padding: 3px 9px;background:red;" class="btn " onclick="deleteRow(this);"><i class="fas fa-times "  style="font-size: 18px;color: white;" ></i></button> ');
		}else{
			$('#'+idTable+'row'+longRows).append('<td id="'+idTable+'row'+longRows+'col'+i+'" class="colTable"></td>');
			$('#'+idTable+'row'+longRows+'col'+i).append('<input onfocusout="preview();" class="input form-control" id="'+idTable+'row'+longRows+'col'+i+'input'+i+'"">');
		}
	}
	personalize('previewEditable');
	//Object.keys(rowsTable).forEach(function(codigo) {
    //    console.log(rowsTable[codigo].id);
    //   
    //});
}
function addCol(element){
	var idRow='';
	//longitud de caracteres del id de la tabla
	var longid = element.id.length;
	//id de la tabla que se esta modificando
	var idTable = element.id.substr(6,longid);
	console.log(idTable);
	//Obtener las filas de la tabla
	var rowsTable = document.getElementsByClassName('rowTable');
	//cantidad de filas que se encuentran en la tabla+1 para agregar una nueva
	var longRows = rowsTable;
	console.log(longRows);
	//cantidad de columnas en esa tabla
	var longCols =rowsTable[0].cells.length+1;
	//recorre el id de cada fila para insertar una columna extra
	Object.keys(rowsTable).forEach(function(indice) {
		console.log(rowsTable[indice].id);
		idRow =rowsTable[indice].id;
		$('#'+idRow).append('<td id="'+idRow+'col'+longCols+'" class="colTable"></td>');
		$('#'+idRow+'col'+longCols).append('<input onfocusout="preview();" id="'+idRow+'col'+longCols+'input'+longCols+'"">');

	});
}

function deleteRow(element){
	delets++;
	$('#'+element.id).remove();

	preview();
	console.log(element);
}

function createTable(rows=null,cols=null){
    if(rows == null ){
        $('#contTable').css('display','block');
	    
	    //$('.titleContsUpdate').css('display','block');
    }

	//poder crear las tablas que sean necesarias (pendiente)
	var table = "table1";
	if(rows !== null && cols !== null){
		var row=rows;
		var col=cols;
		$('#tableEditable').html('<table id="'+table+'"></table>');
		//construir div de acciones
		//$('#tableEditable').append('<div id="control'+table+'"></div>');
		//$('#contTable').append('<button id="addCol'+table+'" onclick="addCol(this);" class="btn btn-primary">addCol</button>');
		$('#controlTable').html('<span>Añadir Fila</span><button type="button" id="addRow'+table+'" style="margin:5px;border-radius:100%; padding:3px 9px;" onclick="addRow(this);" class="btn btn-primary"><i class="fas fa-plus"></i></button>');
		$('#controlTable').append('<span>Modificar Alto<input class="form-control"   type="number" style="margin:10px; width:100px; display:inline-block;" id="height" onchange="preview();" ></span>');
		$('#height').val(0);
		$('#controlTable').append('<div id="preview'+table+'"></div>');
	}else{
		row=$('#row').val();
		col=$('#col').val();
		$('#contTable').html('<table id="'+table+'"></table>');
		//construir div de acciones
		$('#contTable').append('<div id="control'+table+'"></div>');
		//$('#contTable').append('<button id="addCol'+table+'" onclick="addCol(this);" class="btn btn-primary">addCol</button>');
		$('#contTable').append('<span>Añadir Fila</span><button type="button" id="addRow'+table+'" style="margin:5px;border-radius:100%; padding:3px 9px;" onclick="addRow(this);" class="btn btn-primary"><i class="fas fa-plus"></i></button>');
		$('#contTable').append('<span>Modificar Alto<input class="form-control"   type="number" style="margin:10px; width:100px; display:inline-block;" id="height" onchange="preview();" ></span>');
		$('#height').val(0);
		$('#contTable').append('<div id="preview'+table+'"></div>');
		$('.titleContsCreate').css('display','block')
	}
	
	

	
	//construye table head
	//$('#table1').append('<thead id="'+table+'thead'+'"></thead>')
	//$('#'+table+'thead').append('<input id="'+table+'thead'+'input'+j+'"">'); 
	//contruye footer table
	$('#table1').append('<tfoot id="'+table+'tfoot'+'"></tfoot>')
	for (var i = 1; i <= row; i++) {
		//construye filas
		$('#'+table).append('<tr id="'+table+'row'+i+'" class="rowTable"></tr>');
		if (i==row) {
			$('#'+table+'tfoot').append('<tr id="'+table+'row'+'tfoot'+i+'" class="rowDelete"></tr>'); 
		}
		//For construye columnas
		for (var j = 1; j <= col; j++) {
			//construye celdas del footer
			console.log(i+'i');
			console.log(row+'row');
			console.log(j+'j');
			console.log(col+'col');
			if (i==row & j==col) {
				$('#'+table+'row'+'tfoot'+i).append('<td id="'+table+'row'+'tfoot'+i+'col'+j+'" class="colTable" colspan="'+col+'"></td>');
				$('#'+table+'row'+'tfoot'+i+'col'+j).append('<textarea onfocusout="preview();" class="input form-control" style="width:100%" id="'+table+'row'+'tfoot'+i+'col'+j+'input'+j+'""></textarea>'); 
			}
			//construye columnas
			$('#'+table+'row'+i).append('<td id="'+table+'row'+i+'col'+j+'" class="colTable"></td>');
			$('#'+table+'row'+i+'col'+j).append('<input onfocusout="preview();" class="input form-control" id="'+table+'row'+i+'col'+j+'input'+j+'"">');
			
			//agregar columna eliminar
			if(j==col){
				$('#'+table+'row'+i).append('<td id="delete'+table+'row'+i+'col'+j+'" class="colTable"></td>');
				$('#delete'+table+'row'+i+'col'+j).append('<button type="button" id="'+table+'row'+i+'" style="margin:5px;margin-left: 10px;border-radius:100%;padding: 3px 9px;background:red;" class="btn " onclick="deleteRow(this);"><i class="fas fa-times "  style="font-size: 18px;color: white;" ></i></button> ');
			}
		}
	}
}

function preview(){
	var tfoot = '';
	var longNodsTable='';
	var longNodsTfoot='';
	var cont =0;
	var item='';
	//jalar la tabla con cada onfocus de los inputs y agregarla a un contenedor
	//cambiar id de la tabla para no crear conflictos al momento de guardarla
	//escanear todos los inputs y cambiarlos por texto

	console.log(document.getElementById('table1').id);
	var table = $('#table1').clone();
	//$('#previewtable1 ').html(tables[0].outerHTML);
	//var contTable =  $('#previewtable1');
	//console.log(contTable[0].childNodes[0]);
	//contTable[0].childNodes[0].setAttribute('name','hola');
	//$('#'+contTable[0].childNodes[0].id).attr('id','hola');
	//var table = $('#hola');

	
	//$('#previewTable').html(table);
	console.log(table[0]);
	table[0].classList.add("table");
	table[0].classList.add("table-bordered");
	console.log(table);
	longNodsTable = table[0].childNodes.length;
	console.log(table[0].childNodes[0].localName);
	for (var i = 0; i < longNodsTable; i++) {
		console.log(i);
		console.log(table[0].childNodes[i].localName);
		if(table[0].childNodes[i].localName == 'tfoot'){
			//tfoot->tr
			
			//$('#'+table[0].id).removeAttr('id');
			//$('#'+table[0].childNodes[i].id).removeAttr('id');
			//$('#'+table[0].childNodes[i].childNodes[0].id).removeAttr('id');
			//$('#'+table[0].childNodes[i].childNodes[0].childNodes[0].id).removeAttr('id');
			table[0].removeAttribute('id');
			table[0].childNodes[i].removeAttribute('id');
			table[0].childNodes[i].childNodes[0].removeAttribute('id');
			if($('#height').val()>0){
				table[0].childNodes[i].childNodes[0].style.padding=$('#height').val()+'px';
				
			}else{
				table[0].childNodes[i].childNodes[0].style.padding='4px';
			}
			table[0].childNodes[i].childNodes[0].style.paddingLeft='10px';
			table[0].childNodes[i].childNodes[0].childNodes[0].removeAttribute('id');
			//id input
			//$('#'+table[0].childNodes[i].childNodes[0].childNodes[0].childNodes[0].id).removeAttr('id');
			table[0].childNodes[i].childNodes[0].childNodes[0].childNodes[0].removeAttribute('id');
			console.log(table[0].childNodes[i].childNodes[0].childNodes[0].childNodes[0].value);
			table[0].childNodes[i].childNodes[0].childNodes[0].innerHTML=table[0].childNodes[i].childNodes[0].childNodes[0].childNodes[0].value.replace(/\n/g, "<br />");
			longNodsTfoot=table[0].childNodes[i].childNodes.length;

			
			
		}else if (table[0].childNodes[i].localName == 'tbody') {
			//$('#'+table[0].childNodes[i].id).removeAttr('id');
			//id tr
			
			//tr->td
			//	->td
			for (var k = 0; k < table[0].childNodes[i].childNodes.length; k++) {
			    table[0].childNodes[i].childNodes[k].removeAttribute('id');
			    table[0].childNodes[i].childNodes[k].removeAttribute('class');
    			console.log(table[0].childNodes[i].childNodes[0].childNodes);
    			for (var j = 0; j < table[0].childNodes[i].childNodes[k].childNodes.length; j++) {
    				//tamaño real iniciando de 1
    				var tm = table[0].childNodes[i].childNodes[k].childNodes.length-1;
    				console.log(table[0].childNodes[i].childNodes[k].childNodes);
    				console.log(tm);
    				console.log(j);
    				if(j==tm){
    				    console.log(table[0].childNodes[i].childNodes[k]);
    				    console.log(table[0].childNodes[i].childNodes[k].childNodes[j]);
    					table[0].childNodes[i].childNodes[k].removeChild(table[0].childNodes[i].childNodes[k].childNodes[j]);
    				}else{
    					//$('#'+table[0].childNodes[i].childNodes[j].id).removeAttr('id');
    					//id td
    					table[0].childNodes[i].childNodes[k].childNodes[j].removeAttribute('id');
    					table[0].childNodes[i].childNodes[k].childNodes[j].removeAttribute('class');
    					if($('#height').val()>0){
    						table[0].childNodes[i].childNodes[k].childNodes[j].style.padding=$('#height').val()+'px';
    						
    					}else{
    						table[0].childNodes[i].childNodes[k].childNodes[j].style.padding='4px';
    					}
    					table[0].childNodes[i].childNodes[k].childNodes[j].style.paddingLeft='10px';
    					//id input
    					//$('#'+table[0].childNodes[i].childNodes[j].childNodes[0].id).removeAttr('id');
    					table[0].childNodes[i].childNodes[k].childNodes[j].childNodes[0].removeAttribute('id');
    					//Elegir solo las columnas que contienen texto
    					if (table[0].childNodes[i].childNodes[k].childNodes[j].childNodes[0].localName == "input") {
    						//console.log(table[0].childNodes[i].childNodes[j].id);
    						//table[0].childNodes[i].childNodes[j].setAttribute('name','hola');
    						
    						//var name = document.getElementsByName('hola');
    						//console.log(name);
    						table[0].childNodes[i].childNodes[k].childNodes[j].innerHTML='<p style="margin:0">'+table[0].childNodes[i].childNodes[k].childNodes[j].childNodes[0].value+'</p>';	
    						//table[0].childNodes[i].childNodes[j].removeAttribute('name');
    						
    					}
    				}
    
    
    			}
			}
		}
	}




	if ($('#previewEditable').hasClass('active')) {
	    $('#previewEditable').css('display','block');
	    $('.contSaveChange').css('display','block');
	    $('.editTable').css('display','block');
		$('#previewEditable').html(table[0].outerHTML);
		//Llamar a personalizar
		personalize('previewEditable');
	}else{
	    $('#inputColorWord').css('display','inline-block');
	    $('#inputBack').css('display','inline-block');
	    $('.contSaveTable').css('display','block');
	    $('.titleContsCreate').css('display','block');
	    $('.containerPersonalize').css('display','block');
		$('#previewTable').html(table[0].outerHTML);
		//Llamar a personalizar
		personalize('previewTable');
	}
}
function saveTable(element){
    var codeTable = '';
    var action='';
    var idTour=$('select[id=miselect]').val();
    //element contiene el btn que activara esta funcion, para guardar o actualizar
    console.log(element.id);
    if(element.id=='saveTable'){
        console.log($('#previewTable')[0].innerHTML);
        codeTable=$('#previewTable')[0].innerHTML;
        action='saveTable';
    }else if(element.id=='updateTable'){
        console.log($('#previewEditable')[0].innerHTML);
        codeTable=$('#previewEditable')[0].innerHTML;
        action='updateTable';
    }
    
    //get generico para guardar o actualizar
    $.get(`https://panoramex.mx/dashboard/tableAndList`, 'data=' + JSON.stringify({
	    action: action,
		language,
		typeTable,
		idTour,
		codeTable
	    
	}), (response) => {
  
        respuesta = JSON.parse(response);
		if(respuesta['save']==1){
		    
		    $('#contAlert').html('<div class="alert alert-info" role="alert">Guardado con éxito</div>');
		    $('.modalTable').modal('hide');
		    setTimeout(deleteAlert, 3000);
		}else{
		    $('#contAlert').html('<div class="alert alert-danger" role="alert">Error al guardar</div>');
		    $('.modalTable').modal('hide');
		    setTimeout(deleteAlert, 3000);
		}
		
    }, 'script');
}

function saveCodeList(){
    var code = $('#previewList')[0].innerHTML;
    $('#incluye-tour').text(code);
    $('.modalList').modal('hide');
}


function saveList(element){
    var codeList = '';
    var action='';
    var idTour=$('select[id=miselect]').val();
    //element contiene el btn que activara esta funcion, para guardar o actualizar
    console.log(element.id);
    
        console.log($('#previewList')[0].innerHTML);
        codeList=$('#previewList')[0].innerHTML;
        action='saveList';
    
    //get generico para guardar o actualizar
    $.get(`https://panoramex.mx/dashboard/tableAndList`, 'data=' + JSON.stringify({
	    action: action,
		language,
		typeList,
		idTour,
		codeList
	    
	}), (response) => {
	    
        //var ES={
        //    'Guardado con éxito',
        //    'Error al guardar'
        //};
        //var EN = {
        //    'Save Complete',
        //    'Failed to save'
        //    };
        //
        respuesta = JSON.parse(response);
        //var languages={
        //    ES,
        //    EN
        //}
        //console.log(languages['ES']);
        console.log(respuesta['save']);
		if(respuesta['save']==1){
		    
		    $('#contAlert').html('<div class="alert alert-info" role="alert">Guardado con éxito</div>');
		    $('.modalList').modal('hide');
		    setTimeout(deleteAlert, 3000);
		}else{
		    $('#contAlert').html('<div class="alert alert-danger" role="alert">Error al guardar</div>');
		    $('.modalList').modal('hide');
		    setTimeout(deleteAlert, 3000);
		}
		
    }, 'script');
}
function deleteAlert(){
    $('#contAlert').html('');
}
function personalize(table){
	var colorWord='';
	var colorTable='';
	console.log(colorWord);
	console.log(colorTable);
	console.log(table);
	if (table=='previewEditable' || $('#'+table.id).hasClass('previewEditable')) {
	    
	    colorWord=$('input[id=inputColorWords]').val();
	colorTable=$('input[id=inputBacks]').val();
	console.log(colorWord);
	console.log(colorTable);
	console.log(table);
		$('#previewEditable')[0].childNodes[0].style.color=colorWord;
		$('#previewEditable')[0].childNodes[0].style.backgroundColor=colorTable;

	}else{
		colorWord=$('input[id=inputColorWord]').val();
	colorTable=$('input[id=inputBack]').val();
	console.log(colorWord);
	console.log(colorTable);
	console.log(table);
		$('#previewTable')[0].childNodes[0].style.color=colorWord;
		$('#previewTable')[0].childNodes[0].style.backgroundColor=colorTable;
	}
}
var rgbToHex = function (rgb) { 
	var hex = Number(rgb).toString(16);
	if (hex.length < 2) {
		hex = "0" + hex;
	}
	return hex;
};
var fullColorHex = function(r,g,b) {   
	var red = rgbToHex(r);
	var green = rgbToHex(g);
	var blue = rgbToHex(b);
	return red+green+blue;
};

function searchTable(lang,type,idTour){
	var respuesta ='';
	console.log('searchTable');
	//$.get('https://panoramex.mx/dashboard/tableAndList',`data=${JSON.stringify({
	//	action: 'searchTable',
	//	lang,
	//	type
	//})}`,function(response){
	//	respuesta = JSON.parse(response);
	//	if(respuesta['hasTable']==1){
	//		$('.editTable').css('display','block');
	//		scanTable(respuesta['table']);
	//	}else if (respuesta['hasTable']==0) {
	//		//activar funcion de crear tabla
	//		$('.buildTable').css('display','block');
	//	}
	//	console.log(respuesta['table']);
	//	
	//});
	console.log($('#itinerario-tour').val() != 'null');
	if($('#itinerario-tour').val() != 'null'){
	    var nameTour=$('select[id=select-tour-edit]')[0].selectedOptions[0].innerHTML;
	    $('.modalTable').modal('show');
		    //mostrar titulo del modal
		    $('.titleModal').html('<h3 class="titleModal">Creación/Edición Itinerario "'+nameTour+'"</h3>');
		    //$('.editTable').html('');
		    $('#previewTable').html('');
		    $('#previewEditable').html('');
		    $('#controlTable').html('');
		    $('#tableEditable').html('');
		    $('.contValuesEdit').css('display','none');
		    $('.titleContsCreate').css('display','none');
		    $('#contTable').css('display','none');
		    $('.personalize').css('display','none');
		    $('.personalizeUpdate').css('display','block');
		    $('.previewTable').css('display','none');
		    $('.contSaveTable').css('display','none');
			$('.editTable').css('display','block');
			$('#previewEditable').css('display','block');
			$('.titleContsUpdate').css('display','block');
			scanTable($('#itinerario-tour').val());
	}else{
	    $('.modalTable').modal('show');
			//mostrar titulo del modal
		    $('.titleModal').html('<h3 class="titleModal">Creación/Edición Itinerario "'+nameTour+'"</h3>');
			//$('.editTable').html('');
			$('#previewTable').html('');
		    $('#previewEditable').html('');
		    $('#controlTable').html('');
		    $('#tableEditable').html('');
		    $('.editTable').css('display','none');
		    $('.containerPersonalize').css('display','none');
		    //$('.contEdicion').css('display','none');
			$('.buildTable').css('display','block');
			$('.contValuesEdit').css('display','block');
			$('.contSaveChange').css('display','none');
			$('.titleConstCreateUpdate').css('display','none');
			$('.titleContsUpdate').css('display','none');
			$('#previewEditable').css('display','none');
			if($('#previewEditable').hasClass('active')){
			    $('#previewEditable').removeClass('active');
			}
	}
	
}

function scanRGB(color){
	var cont1=0;
	var cont2=0;
	var r='';
	var g='';
	var b='';
	for (var i =0; i < color.length; i++) {
		if (color[i]=='(' || color[i]==',' ){
			cont1++;
		}
		

		if (cont1==1 && color[i]!='(') {
			r=r+color[i];
		}if (cont1==2 && color[i]!=',') {
			g=g+color[i];
		}if (cont1==3 && color[i]!=',' && color[i]!=')') {
			b=b+color[i];
		}

		
	}
	var rgb={
		r,
		g,
		b
	}
	return rgb;
}

function scanTable(table){
    console.log(table);
	var previewEditable ='';
	$('#previewEditable').html(table);
	$('#previewEditable').addClass('active');
	$('.edit').css('display','block');
	//Detecta si la tabla contiene algun estilo
	var color=$('#previewEditable')[0].childNodes[0].style.color;
	var padding = $('#previewEditable')[0].childNodes[0].childNodes[1].childNodes[0].childNodes[0].style.paddingTop;
	padding = padding.substr(0,2);
	console.log(padding+'padding');
	$('#height').val(padding);
	var wordColor=scanRGB(color);
	
	//color de letra hexadecimal
	var colorH=fullColorHex(wordColor['r'],wordColor['g'],wordColor['b']);
    

	console.log(colorH);
	var background=$('#previewEditable')[0].childNodes[0].style.backgroundColor;
	var backgroundColor=scanRGB(background);
	var colorHT=fullColorHex(backgroundColor['r'],backgroundColor['g'],backgroundColor['b']);
	console.log(color);
	console.log(background);
	//obtener la tabla dentro del elemento donde la insertamos para poder identificar
	previewEditable = $('#previewEditable');
	//Obtener filas y columnas de la tabla
	var filas = previewEditable[0].childNodes[0].tBodies[0].childNodes.length;
	console.log(filas+'filas');
	var columnas = previewEditable[0].childNodes[0].tBodies[0].childNodes[0].cells.length;
	console.log(previewEditable[0].childNodes);
	console.log(columnas+'columnas');
	console.log(previewEditable[0].childNodes);
	createTable(filas,columnas);
	//asignar colores
	console.log(color);
	if(color==''){
	    console.log('color no tiene nada');
	    colorH='000000';
	}
if(wordColor.b==''&&wordColor.r==''&&wordColor.g==''){
	    console.log('background no tiene nada');
	    colorHT='ffffff';
	}
	console.log(wordColor);
	$('input[name=colorWord]').val('#'+colorH);
	$('input[name=colorTable]').val('#'+colorHT);
	$('#height').val(padding);
	//recorrer filas y columas
	//table+'row'+i+'"
	//table+'row'+i+'col'+j+'
	for (var i =0; i <filas; i++) {
		for (var j = 0; j <columnas; j++) {
			console.log('#table1row'+i+'col'+j+'input'+j);
			valRow=i+1;
			valCol=j+1;
			console.log(previewEditable[0].childNodes[0].tBodies[0].childNodes[i].childNodes[j].childNodes[0].textContent);
			$('#table1row'+valRow+'col'+valCol+'input'+valCol).val(previewEditable[0].childNodes[0].tBodies[0].childNodes[i].childNodes[j].childNodes[0].textContent);		
			if (valRow==filas) {
				console.log(valRow);
				console.log(filas);
				//recorrer tfoot
				console.log(previewEditable[0].childNodes[0].tFoot.childNodes[0].childNodes);
				//var text = ;
				//text.replace(/<br>/g, "\n");
				$('#table1rowtfoot'+valRow+'col'+valCol+'input'+valCol).html(previewEditable[0].childNodes[0].tFoot.childNodes[0].childNodes[0].innerHTML.replace(/<br>/g,'\n'));
				
			}
		}
	}	
}
//function searchTable(lang,type){
//	var respuesta ='';
//	$.get('https://panoramex.mx/dashboard/tableAndList',`data=${JSON.stringify({
//		action: 'searchTable'
//	})}`,function(response){
//		respuesta = JSON.parse(response);
//		if(respuesta['hasTable']==1){
//			$('.editTable').css('display','block');
//			scanTable(respuesta['table']);
//		}else if (respuesta['hasTable']==0) {
//			//activar funcion de crear tabla
//			$('.buildTable').css('display','block');
//		}
//		console.log(respuesta['table']);
//		
//	});
//}
//**********************************Create and Edit List****************************************//
function searchList(lang,type,idTour){
    console.log('searchList');
	var respuesta ='';
	console.log(lang);
	console.log(type);
	console.log(idTour);
		var nameTour=$('select[id=select-tour-edit]')[0].selectedOptions[0].innerHTML;
		$('.titleModalList').html('<h3 class="titleModal">Creación/Edición Lista "'+nameTour+'"</h3>');
	if($('#incluye-tour').val() == 'null'){
	    
			$('.editableList').html('');
			$('#previewList').html('');
			$('.modalList').modal('show');
			$('.controlsCreateList').css('display','block');
			$('.contSave').css('display','none');
	}else{
	    	$('.controlsCreateList').css('display','none');
			$('.contSave').css('display','none');
			$('.modalList').modal('show');
			scanList($('#incluye-tour').val());
	}
	/*$.get(`https://panoramex.mx/dashboard/tableAndList`, 'data=' + JSON.stringify({
	    action: 'searchList',
		lang,
		type,
		idTour
	    
	}), (response) => {
    
    	respuesta = JSON.parse(response);
    	//Titulo del modal de lista
    	var nameTour=$('select[id=miselect]')[0].selectedOptions[0].innerHTML;
    	$('.titleModalList').html('<h3 class="titleModal">Creación/Edición Lista "'+nameTour+'"</h3>');
		if(respuesta['hasList']==1){
		
		}else if (respuesta['hasList']==0) {
			//activar funcion de crear lista
			
		}
		console.log(respuesta['list']);
    }, 'script');*/
	//$.get('conexion.php',`data=${JSON.stringify({
	//	action: 'searchList',
	//	lang,
	//	type,
	//	idTour
	//})}`,function(response){
	//	respuesta = JSON.parse(response);
	//	if(respuesta['hasList']==1){
	//		$('.controlsCreateList').css('display','none');
	//		scanList(respuesta['list']);
	//	}else if (respuesta['hasList']==0) {
	//		//activar funcion de crear lista
	//		$('.controlsCreateList').css('display','block');
	//	}
	//	console.log(respuesta['list']);
	//	
	//});
}
function createList(items=null){
	
	//Obtener cantidad de items a construir
	if (items!=null) {
		var noItems=items;
	}else{
		var noItems=$('#NoItems').val();
	}
	
	console.log(noItems);
	//insertar esqueleto de la lista
	$('.editableList').html('<ul id="list"></ul>');
	$('#controlList').html('<span>Añadir Fila</span><button type="button" id="addItemList" style="margin:5px;border-radius:100%; padding:3px 9px;" onclick="addItem(this);" class="btn btn-primary"><i class="fas fa-plus"></i></button>');
	//$('#controlList').append('<span>Modificar Alto<input class="form-control"   type="number" style="margin:10px; width:100px; display:inline-block;" id="height" onchange="previewList();" ></span>');
	for (var i = 1; i <=noItems; i++) {
		$('#list').append('<li id="item'+i+'" class="btn-group"></li>');
		$('#item'+i).append('<input class="form-control"  id="item'+i+'input'+i+'" onfocusout="previewList();">');
		$('#item'+i).append('<button type="button" id="'+'item'+i+'" style="margin:5px;margin-left: 10px;border-radius:100%;padding: 3px 9px;background:red;" class="btn " onclick="deleteItem(this);"><i class="fas fa-times "  style="font-size: 18px;color: white;" ></i></button> ');
	}

}
function addItem(){
	//cantidad de items creados
	var list = $('#list');
	console.log(list[0].childNodes.length);
	//Tomar en cuenta si hay se elimino algun elemento
	var cantItems =list[0].childNodes.length+delets+1;
	//Agregar item
	$('#list').append('<li id="item'+cantItems+'" class="btn-group"></li>');
	$('#item'+cantItems).append('<input class="form-control"  id="item'+cantItems+'input'+cantItems+'" onfocusout="previewList();">');
	$('#item'+cantItems).append('<button type="button" id="item'+cantItems+'" style="margin:5px;margin-left: 10px;border-radius:100%;padding: 3px 9px;background:red;" class="btn " onclick="deleteItem(this);"><i class="fas fa-times "  style="font-size: 18px;color: white;" ></i></button> ');
	previewList();
}
function deleteItem(element){
	console.log(element.id);
	delets++;
	$('#'+element.id).remove();
	previewList();
}
function previewList(){
	//mostrar panel para personalizar 
	$('.contPersonalize').css('display','block');
	//Obtener lista
	var list = $('#list').clone();
	console.log(list[0].childNodes.length);
	//longitud de items
	var longItems=list[0].childNodes.length;
	//quitar id de lista
	list[0].removeAttribute('id');
	for (var i = 0; i <longItems; i++) {
		console.log(list[0].childNodes[i]);
		//remover id de cada item
		list[0].childNodes[i].removeAttribute('id');
		list[0].childNodes[i].removeAttribute('class');
		var longNodes=list[0].childNodes[i].childNodes.length;
		//console.log(list[0].childNodes[i].childNodes[0]);
		//console.log(list[0].childNodes[i].childNodes[1]);
		//console.log(list[0].childNodes[i].childNodes[2]);
		console.log(list[0].childNodes[i].childNodes);
		longNodes = longNodes-2;
		console.log(longNodes);
		for (var j = 0; j <longNodes; j++) {

			console.log(list[0].childNodes[i].childNodes);
			if (list[0].childNodes[i].childNodes[j].localName=='input') {
				//reemplazar el input por texto
				console.log(list[0].childNodes[i].childNodes[j].value);
				list[0].childNodes[i].innerHTML='<p style="margin:0">'+list[0].childNodes[i].childNodes[j].value+'</p>';
				//remover input
				//list[0].childNodes[i].removeChild(list[0].childNodes[i].childNodes[j]);
			}
			else if (list[0].childNodes[i].childNodes[j].localName=='button') {
				//remover btn delete
				list[0].childNodes[i].removeChild(list[0].childNodes[i].childNodes[j]);
			}else{
				//remover
				list[0].childNodes[i].removeChild(list[0].childNodes[i].childNodes[j]);
			}
		}



		//console.log(list[0].childNodes[i].childNodes);

	}
	console.log(list);
    $('.contSave').css('display','block');
	$('#personalizeList').css('display','block');
	$('#previewList').html(list);
	personalizeList();
}
function personalizeList(){
	var vineta=$('select[id=selectList]').val();
	console.log(vineta);
	//acceder al elemento que contiene la lista
	var finalList = $('#previewList');
	//añade estilo a la lista
	finalList[0].childNodes[0].style.listStyleType=vineta;
	
}
function scanList(list){

	console.log(list);
	$('#previewList').html(list);
	//obtener la lista que acabamos de insertar como elemento
	var lista = $('#previewList')[0].childNodes[0];
	//detectar si contiene estilo de viñeta
	console.log(lista.style.listStyleType);
	var styleVineta = lista.style.listStyleType;
	$('select[id=selectList]').val(styleVineta);
	//obtener cantidad de items
	var cantItems=lista.childNodes.length;
	console.log(cantItems);
	//crea inpus editables
	createList(cantItems);
	//mostrar zona para personalizar
	$('.contPersonalize').css('display','block');
	//rellenar lista creada
	for (var i = 0; i <cantItems; i++) {
		console.log(lista.childNodes[i].childNodes[0].textContent);
		noItem=i+1;
		$('#item'+noItem+'input'+noItem).val(lista.childNodes[i].childNodes[0].textContent);
	}
	$('.contSave').css('display','block');
}