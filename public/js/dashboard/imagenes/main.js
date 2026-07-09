
$(document).ready(function(){
        
//-------------------------------------------------------------------------------
selectDestino();

//-------------------------------------------------------------------------------------------
    
//---------------------------------------------------------------------------------------------
selectCategoria();
//-----------------------------------------------------------------------------------------------------
//$('#selectDestino').select2();
//$('#selectCategorias').select2();


})//cierre ready


 

function clickBoton(id){
    console.log(id.id);
    $('#Mymodal').show().scrollTop(0);
    $('#prueba2').css('visibility','visible');

	$.get(`https://melorautopartes.com/dashboard/images`, 'data=' + JSON.stringify({
	    action: 'selectDestino',
	    rand:Math.random(),
	}), (data) => {
		data.forEach(tour => {

			$('#selectDestinox').append($('<option>', {
				value: tour,
				text: tour,

			}));
			
		});
	}, 'json');

	var y = id.src;

	$('#urlFotoNew').val(y);
	$('#urlFotoNew').removeAttr('name');
	$('#urlFotoNew').attr('name',id.id);


	//$('#selectDestinox').select2();
	$('#selectDestino').css('visibility','visible');
	$('#prueba2').css('visibility','visible');
	$("#urlFoto").removeAttr('value');
	var select=id.id;
	

    $.ajaxSetup({async:false});
	$.get('https://melorautopartes.com/dashboard/images', 'data=' + JSON.stringify({
		action: 'mostrarInfo',
		rand:Math.random(),
		select,

	}),(response) => {
		$('#prueba2').html(response);
		$('[data-toggle="tooltip"]').tooltip(); 
		
		var cont = $('#contImage').val();
         if(cont < 32 || cont == 'empty')
	    {   
	       
	        $('#fixed').removeClass("position-fixed");
	        $('#elCol').removeClass("col-5");
	        
	        $('#elCol').addClass('col-12');
	    }else{
	        
	     	$('#fixed').addClass("position-fixed");
	        $('#elCol').removeClass("col-12");
	        
	        $('#elCol').addClass('col-5');
	    }
	    
		$('#save').click(() => {
		    var ids = localStorage.getItem("idUrlFoto");
		    console.log(localStorage.getItem("idUrlFoto"));
		    
		    var source = id.src;
		    var idP = id.id;
		    console.log(ids);
		    console.log(source);
            console.log(id.name);
            console.log(idP);
			$('#urlFoto' + ids).removeAttr('src');
			$('#urlFoto' + ids).removeAttr('name');
			$('#urlFoto' + ids).attr("src",source);
			$('#urlFoto' + ids).attr('name',idP);
			$('#' + ids).removeAttr('name');
			$('#' + ids).attr('name',idP);


			var select= idP;
			var nombre = $('#nombre'+ select).val();
			var categoria = $('#selectCategoria' + select).val();
			var urlFoto = source;
			var destino = $('#selectDestinox'+ select).val();
			var descripcionImg = $('#descripcionImg'+ select).val();
			var peso = $('#peso'+ select).val();
			var alto = $('#alto' + select).val();
			var ancho = $('#ancho'+ select).val();

			

			if(categoria == 'youtube principal' || categoria == 'youtube carrusel'){
				
				urlFoto = id.className;
				$('#urlFotoNew').val(urlFoto);
				$('#urlFotov' + id.align).removeAttr('src');
				$('#urlFotov' + id.align).attr('src',id.className);
				$('#urlFotov' + id.align).attr('name',id.id);

			}
			console.log(urlFoto,destino,descripcionImg,peso,alto,ancho,categoria);
			$.get('https://melorautopartes.com/dashboard/images', 'data=' + JSON.stringify({
				action: 'updateInfo',
				rand:Math.random(),
				select,
				urlFoto,
				destino,
				descripcionImg,
				peso,
				alto,
				ancho,
				categoria,
				nombre,

			}))
		});



	},'text');
	$.ajaxSetup({async:true});


}


function clickBotons(id){

	$.get(`https://melorautopartes.com/dashboard/images`, 'data=' + JSON.stringify({
	    action: 'selectDestino',
	    rand:Math.random(),
	}), (data) => {
		data.forEach(tour => {

			$('#selectDestinox').append($('<option>', {
				value: tour,
				text: tour,

			}));
			
		});
	}, 'json');

	//$('#selectDestinox').select2();
	$('#selectDestino').css('visibility','visible');
	$('#prueba2').css('visibility','visible');
	var select=id.name;
	



	$.get('https://melorautopartes.com/dashboard/images', 'data=' + JSON.stringify({
		action: 'mostrarInfo',
		rand:Math.random(),
		select,

	}),(response) => {
	    console.log("clickbotons1");
		$('#prueba2').html(response);
        $('[data-toggle="tooltip"]').tooltip(); 
      var cont = $('#contImage').val();
         if(cont < 32 || cont == 'empty')
	    {   
	       
	        $('#fixed').removeClass("position-fixed");
	        $('#elCol').removeClass("col-5");
	        
	        $('#elCol').addClass('col-12');
	    }else{
	       
	     	$('#fixed').addClass("position-fixed");
	        $('#elCol').removeClass("col-12");
	        
	        $('#elCol').addClass('col-5');
	    }

		$('#save').click(() => {
			if(id.name != 'new'){
			
				$('#' + id.id).attr('src',id.src);
				$("#" + id.id).removeAttr('value');
				var select=id.name;
				$('#' + id.id).attr("src",id.src);
				$('#' + id.id).removeAttr('name');

				$('#' + id.id).attr('name',id.name);
			}



			var select = id.name;
			var nombre = $('#nombre'+ select).val();
			var urlFoto = id.src;
			var destino = $('#selectDestinox'+ select).val();
			var descripcionImg = $('#descripcionImg'+ select).val();
			var peso = $('#peso'+ select).val();
			var alto = $('#alto' + select).val();
			var ancho = $('#ancho'+ select).val();
			var categoria = $('#selectCategoria' + select).val();

			if(categoria == 'youtube principal' || categoria == 'youtube carrusel'){
			
				urlFoto = id.className;
				$('#urlFotoNew').val(urlFoto);
				$('#urlFotov' + id.align).removeAttr('src');
				$('#urlFotov' + id.align).attr('src',id.className);
				$('#urlFotov' + id.align).attr('name',id.id);

			}

			console.log(urlFoto,destino,descripcionImg,peso,alto,ancho,categoria);
			$.get('https://melorautopartes.com/dashboard/images', 'data=' + JSON.stringify({
				action: 'updateInfo',
				rand:Math.random(),
				select,
				urlFoto,
				destino,
				descripcionImg,
				peso,
				alto,
				ancho,
				categoria,
				nombre,

			}))
		});



	},'text');


}







function validar(){

		$('#save').attr('data-dismiss','modal');
	
}

function seleccionar(){
        //$('#images').imgAreaSelect({remove:true});
     //$('#images').imgAreaSelect({remove:false});
    
    
	$('#colCategoria').css('visibility','visible');
	$('#colDestino').css('visibility','visible');
	$('#subir').css('display','none');  
	$('#seleccionar').css('display','inline-flex');
	$('#prueba2').css('visibility','visible');

	$('#seleccionarBoton').css('background-color','#28a745');
	$('#seleccionarBoton').css('color','white');


	$('#subirBoton').css('background-color','white');
	$('#subirBoton').css('color',' #28a745');

	$('.modal-footer').html(
    "<div class='col-12' style='text-align:left;/*! margin-left: -69px; */'>"+
    "<div class='row' style='margin-left: 218px;'>"+
    "<button type='button' data-dismiss='modal' onclick='closee();' class='btn btn-danger' style='margin-right: 27px;' data-toggle='tooltip' data-placement='bottom' title='Cerrar la ventana sin guardar la selección de la foto'>Close</button>"+
    "<button type='button' class='btn btn-primary' onclick='validar();' id='save' data-toggle='tooltip' data-placement='bottom' title='Subir la Foto para el Tour previamente seleccionado'>Subir Foto</button>"+
    "</div>"+
    "</div>"+
    "</div>");
    
    /*$('.modal-footer').html("<div class='col-12' style='text-align:left;/*! margin-left: -69px; '>"+
        "<div class='row' style='margin-left: 218px;'>"+
        "<button type='button' data-dismiss='modal' onclick='closee();' class='btn btn-danger' style='margin-right: 27px;'>Close</button>"+
        "<button type='button' class='btn btn-primary' onclick='validar();' id='save'>Subir Foto</button>"+
        "</div>"+
        "</div>"+
        "</div>"); */
        
        $('[data-toggle="tooltip"]').tooltip(); 
}

/////////------------------- Drag & drop files here---------------------//////////////////////////////////////


function activar(){
    //$('#bloqueo').removeAttr('disabled');
}

function desactivar(){
       //$('#images').imgAreaSelect({remove:true});
        //$('#bloqueo').attr('disabled','');
 //$('#images').imgAreaSelect({remove:false});
      	//recortar(anchoP, altoP,'#images','#ancho','#alto');
      	
      //	$('#relacion').prop( 'checked',false);
}



function cerrarAngel(){
     $(".modal").hide();
      $('body').removeClass('modal-open');
       $('.modal-backdrop').remove();
     
}



function dragover(){
    $('.custom-file').css('color','blue');

}

function subir(){
    $('.modal-footer').html("");
	$('#colCategoria').css('visibility','hidden');
	$('#colDestino').css('visibility','hidden');
	$('#prueba2').css('visibility','hidden');
	$('#subir').css('display','inline-flex');
	document.getElementById("subir").style.marginBottom = "0px";
	document.getElementById("subir").style.marginTop= "40px";
	
	
	$('#seleccionar').css('display','none');

$('.help-tip:before').css('color','#cdccd4');

	$('#subirBoton').css('background-color','#28a745');
	$('#subirBoton').css('color','white');


	$('#seleccionarBoton').css('background-color','white');
	$('#seleccionarBoton').css('color','#28a745');

	$('#subir').html(
	    
	    
"<div class='container'>"+
  "<div class='row'>"+
    "<div class='col-lg-6 '>"+
                "<div class='row' style=' top:55px;'>"+
                    "<div class='col-lg-12 colA7'>"+
                            "<form id='formulario' enctype='multipart/form-data'   method='POST'style='position:relative;'>"+
                    		"<div class='input-group mb-3'>"+
                    		"<div class='custom-file'style='height:51px;   border-style: dashed; color: #ccc1c1;' >"+
                    		"<input type='file'  accept='image/gif/*' class='custom-file-input' name='archivo' id='inputGroupFile01' aria-describedby='inputGroupFileAddon01' ondragover='dragover(this)'>"+
                    		"<label style='border-style: none; left: 200px; position: absolute;'  id='nombrei'for='inputGroupFile01' >Drag & Drop</label>"+
                    		"<div id='inp' class='help-tip'style=' z-index:9999; top:9px !important;'>"+
                    		"<p style='z-index:9999;'> Para Seleccionar imagen: Soltar imagen o click en Drag&Drop</p>"+
                    		"</div>"+
                    		"</div>"+
                    		"</div>"+
                    			"<button type='button' class='close' onclick=cerrarAngel(); data-dismiss='alert' aria-label='Close'>"+
                    		"<span aria-hidden='true'>&times</span>"+
                    	
                    		"</form>"+
                    "</div>"+
                "</div>"+

                  "<div class='col-lg-6 col-md-5' style='left:-63px; margin:0px; padding:0px;'>"+
                               //"<label for='formGroupExampleInput2'>Categoria</label>"+
                        		"<select class='js-example-responsive' style='width: 100%' id='selectCategoria'>"+
                        		"<option selected='selected' value='seleccionar categoria'>Seleccionar Categoria</option>;"+
                        		"<option value='card'>card</option>;"+
                        		"<option value='carrusel'>carrusel</option>;"+
                        		"<option value='gif'>gif</option>;"+
                        		"<option value='portada'>portada</option>;"+
                        		"<option value='principal'>principal</option>;"+
                        		"<option value='recomendados'>recomendados</option>;"+
                        		"<option value='youtube carrusel'>youtube carrusel</option>;"+
                        		"<option value='Hotel'>Hotel</option>;"+
                        		"</select>"+
                        "</div>"+

                // otro row con 3 columlnas para alto ancho y no relacion
                "<div class=row>"+
                    "<div class='col-lg-2 col-md-1.5' style='left: 14px;'>"+
                                	"<fieldset id='bloqueo' disabled=''>"+
                            		"<input  class='form-check-input' type='checkbox' value='1' id='relacion' >"+
                            		"<label class='form-check-label' for='defaultCheck1'  class='form-control'>no Conservar relacion</label>"+
                            		"</fieldset>"+	
                     "</div>"+
                    
                    "<div class=' col-lg-5 col-md-5 colA'>"+
                                "<p  style='position:relative;top:20px;left:0px;background-color: steelblue;color: white;'  class='form-control' > Ancho de la Imagen </p>"+ 
    		                    "<input  id='ancho' type='number' value='0'   class='form-control' step='1'>"+
    	                        "</br>"+
                    "</div>"+
                    
                    "<div class=' col-lg-5 col-md-5 colA colA1'>"+
                            "<p  style='position: relative;top: 20px;left:0px;background-color: steelblue;color: white;'  class='form-control'  > Alto de la imagen </p>"+
        	            	"<input id='alto' type='number' value='0'  '  step='1'  class='form-control'  '>"+
        		            "</br>"+
                    "</div>"+
                "</div>"+
                
                // otro row para peso y nombre
                "<div class='row'>"+
                
                
                    "<div class=' col-lg-6 col-md-6 colA3 '>"+
                            "<p style='position:relative;top: 18px;left:0px;background-color: steelblue;color: white;'  class='form-control'  class='form-control'  > Peso  de la imagen (KB)</p>"+
		                    "<input id='peso' type='number' value='0'   ' step='1'  class='form-control' >"+
	                    	"</br>"+
	                    	
	                    	
                    "</div>"+
                   
                    "<div class='col-lg-6 col-md-5 colA2 '>"+
                    		"<p style='position:relative;top: 18px;left:0px;background-color: steelblue;color: white;'  class='form-control'> Nombre de la imagen</p>"+
	                       	"<input id='nombreDestino'type='text' size='100' maxlength='100'  placeholder='nombre Image' name='nombre'  class='form-control'>"+
		                    "</br>"+
                    "</div>"+
                        "<div class='col-12' style='top:-69px;display:none;'>"+
                    		"<p  style='position:relative;top:20px;left:0px;background-color: steelblue;color: white;'  class='form-control' > URLFOTO </p>"+ 
                    		"<input   type='text' class='form-control' id='urlFoto' disabled>"+
                    		"</br>"+
		                "</div>"+
                
                "</div>"+
                
                //otro row para descripcion
                "<div class='row'>"+
                   "<div class='col-lg-12 col-md-11 colA4'>"+
                        "<label for='formGroupExampleInput2'>Descripcion</label>"+
		                "<textarea  style='height:33px;' class='form-control'  rows='3' id='descripcionImg' required></textarea>"+
		            "</div>"+  
                "</div>"+
                
                //otro row para categoria y destino
                "<div class='row '>"+
                      
                        
                        
                        "<div class='col-lg-6  col-md-6'>"+
                            		"<label for='formGroupExampleInput2'>Destino</label>"+
                            		"<select class='js-example-responsive' style='width: 100%' id='selectDestinox'  onchange='selectKeywords();'>"+
                            		"<option></option>"+
		                            "</select>"+
                        "</div>"+
                        
                "</div>"+
                
                //otro row para seleccionar keyWords
                "<div class='row'>"+
                    "<div class=' col-lg-6 col-md-6'>"+
                            "<p style='left: 7px;position: relative;top: 21px;'> Seleccion de KeyWords</p>"+
                            "<select id='tagsPage' class='js-example-responsive' style='width: 80%'>"+
                            "</select>"+
                    "</div>"+ 
                    
                    "<div class='col-lg-6 col-md-6  '   style='top:40px;' >"+
                           "<button type='button'  style='top: -10px;   position: relative; left:16%;'class='btn btn-success' id='savePage' onclick='subirTags();' data-toggle='tooltip' data-placement='right' title='Incluir KeyWords' >Incluir</button>"+
                            	"<div  id='cet'  class='help-tip'style=' z-index:9999; top:-53px  !important; '>"+
	                            	"<p style='z-index:9999;'>Las dimenciones para las categorias son: <br> Portada: 1500 x 570(Estricto),peso:250KB <br>Card: 550 x 350(Estricto),peso:50KB <br>Carrousel:700 x 370(sugerido),peso:100KB <br>Principal:700 x 370(sugerido),peso:100KB <br>Recomendados:180x180(Estricto),peso:35KB</p>"+
	                        	"</div>"+
                    "</div>"+ 
                "</div>"+
                //otro row para keyWors
                "<div class='row'  >"+
                  "<div class ='col-lg-12 col-md-12 colA5' style='margin-top:12px;'>"+
                        "<div  id='rowKey' >"+
                    	    "<div class=' keywords align-self-center  '>"+
                            "<div class= style=''> "+
                            "<div class='card'>"+
                            "<h5 class='card-header'>KeyWords</h5>"+
                            "<div class='card-body'>"+
                            "<div class='rowLM' id='tagsDiv'>"+
                            "<!--<input name='tags' id='tagsx' class='tags' name='keywords' value='' required/>-->"+
                            
                            "<input name='tags' style='display:none' id='keywords-tour-imagenes-up' class=' form-control form-control-sm' name='keywords' value='' required/>"+
                            '<div id="cont-space-tags" onclick="focusInputImagenesUp();">'+
                                                    
                    
                    '<div class="input-create-tags-imagenes-up">'+
                    '<input id="input-tag-value-imagenes-up" type="text" style="border: none;padding: 5px;border-radius: 5px !important;max-width: 115px;" placeholder="Add Tag" onkeypress="createTagImagesUp(event);">'+
                '</div>'+
                '</div>'+
                            
                            "<div  id='kye'class='help-tip'style='  top:-46px !important; left:517px !important;'>"+
                    		"<p style='z-index:9999;'>Seleccion de KeyWords para la imagen a modificar</p>"+
                	    "</div>"+
                		"</div>"+
                        "</div>"+
                        "</div>"+
                        "</div>"+
                        "</div>"+
                        "</div>"+
                        "</div>"+
                		
                "</div>"+
                
            // otro row para   remplazar marga y subir
            
            "<div class='row divSubir'  style='height:100px;position:relative; left:21px;'>"+
                  "<div class='col-md-6'>"+
                        "<input  class='form-check-input' type='checkbox' value='1' id='logoCheck' >"+
	                    "<label class='form-check-label' for='defaultCheck1'  class='form-control'  >Incluir Marca Agua</label>"+
                   "</div>"+
                   
                   "<div class='col-md-6'>"+
                        		"<input  class='form-check-input' type='checkbox' value='1' id='RenombrarCheck' >"+
	                           	"<label class='form-check-label' for='defaultCheck1'  class='form-control'>Reemplazar archivo</label>"+
                   "</div>"+
                   
                   "<div class='col'>"+
                    	"<input style='margin-left: auto;margin-right: auto; position:relative;top:0px;'  type='button' class='btn btn-primary upload ' value='Subir' data-toggle='tooltip' data-placement='right' title='Subir Imagen al Banco de Imagenes'>"+
                   "</div>"+
            
            "</div>"+
                
                
    "</div>"+
    // es el col para la imagen 
    "<div class='col-lg-6 col-md-12 colA6'>"+
            "<div class='card'   style='top:10'>"+
        		"<h5 class='card-header'>Imagen Recortar</h5>"+
        		"<img id='images' style='width:100%;'  src='images/imagenes/tuFoto.png'>"+
        		"<div class='help-tip'style=' top:10px !important;'>"+
        		"<p style='z-index:9999;'>Para activar la seleccion,se tiene que hacer un arrastre en la imagen</p>"+
        		"</div>"+
            "</div>"+
                
  
  "</div>"+
         "<div   id='content' style='display:none'>"+
                         "<img  src='js/images/loader.gif' <br/>Un momento, por favor..."+
                    "</div>"+
            "<div id='myAlert' style='top:10px' class='alert alert-info collapse' role='alert'>"+
            "</div>"+
            
            
"</div>"
	    
	    
	    
	    
	    /*
	    
		"<div class='modal-body' id='scroll' style='top: 45px;'>"+
	
		"<div class='row Miguel' style='position:relative;'>"+
		"</br>"+
		"<div class='col-md-2' style='top: -70px;'>"+
	
		"</div>"+
		"<div class='col-md-5' style='top:-88px;'>"+

		"</div>"+
		"<div class='col-md-5' style='top:-90px;'>"+
		
		"<div class='help-tip'style='  left:-321px !important; top:74px !important; top:500!important;'>"+
		"<p style='z-index:9999;'>No conservar Relacion: Ajustar libremente el alto y ancho de seleccion'</p>"+
		"</div>"+
		"</div>"+
		"<div class='col-md-6' style='top:-115px;'>"+
		
		"</div>"+
		"<div class='col-md-6'style='top:-116px;'>"+

		"</div>"+
		
		"<div class='col-12' style='top:-69px;display:none;'>"+
		"<p  style='position:relative;top:20px;left:0px;background-color: steelblue;color: white;'  class='form-control' > URLFOTO </p>"+ 
		"<input   type='text' class='form-control' id='urlFoto' disabled>"+
		"</br>"+
		"</div>"+
		"<div class='col-12 mb-2' style='top:-138px;'>"+
		
		"</div>"+
		"<div class='col-6 mb-2'style='top:-142px;'>"+
		
		"</div>"+
		"<div class='col-6'  style='top:-142px;'>"+

		"</div>"+
	
		"<div class='col-12' id='Page' style='margin-top: -166px;'>"+
		
        
        "</div>"+
        "<div class='row' id='rowKey' style='position: relative;top: -73px;'>"+
	    "<div class='row keywords align-self-center  mb-5'>"+
        "<div class='col-12' style='margin-bottom: 70px;'> "+
      
        "</div>"+
        "</div>"+
        "</div>"+
        "</div>"+
        "</div>"+
        "</div>"+
		"<div class='col-md-6' style='top:-174px; left:-129px;'>"+
		
		"</div>"+
		"<div class='col-md-6'style='top:-200px;left:97px;'>"+

		"<br>"+
	
		"</div>"+
		"</div>"+

		"</div>"+
		"</div>"+
		"</div>"+
		"</div>"+
		"</div>"+
		"<div class='col-md-6' id='prueba2'>"+
		"<div class='col-md-6'>"+
		
		"</div>"+
		"<div class='card-body'  >"+
		"<div class='row rowLM'>"+
		"<div class='col-12 mb-2'>"+
		"</div>"+
		"</div>"+
		"</div>"+
		"</div>"+
		"</div>"*/);






//$('#tagsPage').select2();

//$('#tagsx').tagsInput({
//  'height':'40px',
//  'width':'400px', 
//});

var  x= window.matchMedia("(max-width: 1199px)")
if(x.matches){
    media5();
}
x.addListener(media5)








var  x= window.matchMedia("(max-width: 991px)")
if(x.matches){
    media6();
}
x.addListener(media6)






var  x= window.matchMedia("(max-width: 767px)")
if(x.matches){
    
        var activarplugin='true';

    console.log(activarplugin);
    
    media7();
}
x.addListener(media7)









var x = window.matchMedia("(max-width: 550px)")
if(x.matches){
    media1();
}
x.addListener(media1)





var x = window.matchMedia("(max-width: 526px)")
if(x.matches){
    media8();
}
x.addListener(media8)



















var x= window.matchMedia("(max-width: 477px)")
if(x.matches){
    Media2();
}
x.addListener(Media2)




////otro  media 

var x = window.matchMedia("(max-width: 398px)")
if(x.matches){
    Media3();
}
x.addListener(Media3)


// otro media  

var x = window.matchMedia("(max-width: 385px)")
if(x.matches){
    Media4();
}
x.addListener(Media4)









//selectKeywords();
var ancho=0,alto=0,textoNombre=0,peso=0,relacionO=0,logo=0,logoM=0,relacionAspecto=0,anchoP=0,altoP=0,pesoP=0,x1E=0,y1E=0,x2E=0,y2E=0,arrastre=0,relacion=0,validateModi=0,cambiarNombre='false',anchoDiv=0,altoDiv=0,cat=0;
$(document).ready(function() {
  $('.upload').tooltip();
});


$(document).ready(function() {
    $(window).resize(function(){
    
        console.log($(document).width());
        if($(document).width() <= 668){
            $('#prueba').addClass('col-md-12');
            $('#prueba').removeClass('col-md-8');
        }else if($(document).width() > 668){
            $('#prueba').removeClass('col-md-12');
            $('#prueba').addClass('col-md-8');
        }
    
    });
    
  $('#savePage').tooltip();
  
  	 anchoDiv=$('#images').width();
  	    console.log('en el ready ancho '+anchoDiv);
});



//---Subir Modal------------------






document.getElementById('MyModal')
.addEventListener('scroll', function(event) {
  //$('#images').imgAreaSelect({remove:true});
  //$('#images').imgAreaSelect({remove:false});
  	//recortar(anchoP, altoP,'#images','#ancho','#alto');
});







			   
			   	
			   	
        
 
    








           
$('#ancho').on('change', function() {
        var altoEscala1=$('#images').height();
     var anchoEscala=$('#images').width();
   
    if($('#relacion').prop( 'checked')==false){
	var x=obtenerDimenciones('ancho');  
	$('#alto').val(Math.round(x));
	validateModi=false;

    }else{
         $('#images').selectAreas('destroy');
         $('#images').css('width','inherit');
  validateModi=true;
        obtenerParametros();
         console.log('ancho div '+anchoEscala);
    //var anchoEscala=$('#images').width();
    var escalaSelecAncho=anchoEscala/anchoP;// formula para conservar la relacon de aspecto
    var anchoModi=ancho*escalaSelecAncho;

    
     console.log('alto div '+altoEscala1);
    //var altoEscala=$('#images').height();
       var escalaSelecALto=altoEscala1/altoP;
       var altoModi=alto*escalaSelecALto;
       
    	
    	anchoModi = Math.trunc(anchoModi);
    	  altoModi = Math.trunc(altoModi);
    	
    console.log('valor chinchon ancho '+anchoModi);
    console.log('valor chingon alto '+ altoModi);
console.log(' en el onchange del ancho');
$('#images').selectAreas({
					width: anchoDiv,
					areas: [
						{

							x: 0,
							y: 0,
							width: anchoModi,
							height: altoModi,
						}
					]


});




        
    }
});




$('#alto').on('change', function() {
    var altoEscala1=$('#images').height();
     var anchoEscala=$('#images').width();
    if($('#relacion').prop( 'checked')==false){
	var x=obtenerDimenciones('alto'); 
	$('#ancho').val(Math.round(x));
	validateModi=false;
	
    }else{
      $('#images').selectAreas('destroy');
         $('#images').css('width','inherit');
         //$('#images').css('height','inherit');
       obtenerParametros();
       validateModi=true;
    
    console.log('altoEscala1 '+altoEscala1);
    
       var escalaSelecALto=altoEscala1/altoP;
           var altoModi=alto*escalaSelecALto;
 

    	      //var anchoEscala=$('#images').width();
    	     console.log('ancho escala'+anchoEscala);
    	    
    var escalaSelecAncho=anchoEscala/anchoP;
    var anchoModi=ancho*escalaSelecAncho;
 //console.log('este es el ancho del div '+anchoDiv);
 
    	  anchoModi = Math.trunc(anchoModi);
    	  altoModi = Math.trunc(altoModi);
    	     console.log('valor chingon alto '+ altoModi);
    	     console.log('valor chinchon ancho '+anchoModi);
       
$('#images').selectAreas({
					width: anchoDiv,
					areas: [
						{

							x: 0,
							y: 0,
							width: anchoModi,
							height: altoModi,
						}
					]


});

    	

        
    }
});
            

    












//*************************************************************************************** proceso2**********************************************************************************************************************************************************************************
$('#selectCategoria').on('change',function(){
var proceso2=JSON.stringify({proceso2:'angel'});
        	$.ajax({
        		url:'https://melorautopartes.com/dashboard/indexImg',
        		type:'POST',
        		data:{proceso2},
        		success:function(data){
        	pb02();
        		}
        
        	});
});










//******************************************************************************************************************************proceso2*******************************************************************************************************************************************************

$('.upload').on('click', function() {
    $.get('https://melorautopartes.com/dashboard/AccessControl',`data=${JSON.stringify({
        action:'recuperarSession'
    })}`,function(response){
        response = JSON.parse(response);
        console.log(response);
        if(response.error == 'true'){
            $('#MyModal').modal('hide');
            $.get('https://melorautopartes.com/dashboard/users',`data=${JSON.stringify({
                rand:Math.random(),
                action:'rec_session',
                
            })}`,function(response){
                console.log(response);
                $('#modal-content-body').html(response);
                $('.modalP').css('display','block');
                $('#loginBtn').click(function(){
                    $('#MyModal').modal('show');
                });
            });
        }else if(response.error == 'false'){
            
       
            var numeroElementos=arrayInput();
            console.log('numero de elementos en el array'+numeroElementos);
            
            if(numeroElementos==0){
            
               	 console.log(cat);
               	 
               	 if(cat=='image/gif'){
               	     obtenerParametros();
               	 }
               	 
               	
               	 if(cat=='image/png' || cat=='image/jpeg'){
            	var areas = $('#images').selectAreas('areas');
            	
            	console.log(areas.length);
            	
            	if(areas.length==1)
            	{
            	   	var x1=areas[0]['x'];
        						var y1=areas[0]['y'];
        						var y2=areas[0]['height']+y1;
        						var x2=areas[0]['width']+x1;
        
        			    var altoEscala;
                		var anchoEscala=$('#images').width();
                		var escala=(anchoEscala/anchoP);
        			 console.log(anchoP+' anchop');
                    x1E=Math.round(x1/escala);
        			 y1E=Math.round(y1/escala);
        			  x2E=Math.round(x2/escala);
        			 y2E=Math.round(y2/escala);
        			 
        			    	console.log(areas[0]);  
        			        console.log('x1 '+x1E);
        					console.log('y1 '+y1E);
        					console.log('x2 '+x2E);
        					console.log('y2 '+y2E);
               	 }
        					
            
            
            $('#images').css({remove:true});
        	obtenerParametros();
        	$('#myAlert').show('fade');    
        	if(arrastre!=true){
        	    if(x1E!=0){
        	    x1E=0
        	    y1E=0
        	    x2E=0
        	    y2E=0
        	
        	    }
        	    }
            	   
            	   
            	   
            	    
            	}
           
        				
        
        	
        	var des=$('.tags').val();
        	
        	var destino1=$('#selectDestinox').val();
        		var descripcion=$('#descripcionImg').val();
        	var obj=JSON.stringify({ancho:ancho,alto:alto,textoNombre:textoNombre,peso:peso,logo:logo,reemplazar:logoM,x1E:x1E,y1E:y1E,x2:x2E,y2:y2E,action:'cargarParametros',action1:'keywords',des,destino1,descripcion:descripcion,cambiarNombre:cambiarNombre,tipo:cat});
        	$.ajax({
        		url:'https://melorautopartes.com/dashboard/indexImg',
        		type:'POST',
        		data:{obj},
        		success:function(data){
        			  var data=JSON.parse(data);
        			
        
        				if(data['bandera']=='true'){
        				   
        
        		    mandarAjax(); 
        		}else{
        		  
                 alertas(data);
                 var nombre=data['nombre'];
                 	$('#nombreDestino').val(nombre);
                 
        		}
        		}
        
        	});
        
        
        
        }
        }
    });
});




	
////////////////////////////----------------ondrop---------------------/////////////////////////




var direccion = window.URL || window.webkitURL;
$('#inputGroupFile01').change(function(e) {
     altoDiv=$('#images').height();
  		console.log('en el ready alto '+altoDiv);
  	
   
   console.log('en el onchange del input');
   	$('#images').selectAreas('destroy');
   		
   	
      	$('#bloqueo').attr('disabled','');
    $('#relacion').prop( 'checked',false);

 
$('.custom-file').css('color','#ccc1c1');
	var angel=this.files[0].name;
	$(this).siblings('.custom-file-label').addClass('selected').html(angel);



	var file, img=null;
	file = this.files[0];


	if (file ) {
		img = new Image();
		
		cat=file['type'];
console.log(file['type']);

		if(img !=null){
    
			img.setAttribute('id','images');
			

		}

		img.onload = function () {

		   
		   arrastre=false;
		     
		     	console.log(y2E);

            //console.log(relacionO);

    


			document.getElementsByClassName('images');
			//$('#images').append(img);
			
			//document.getElementById('images').appendChild(img);
		
			//document.body.appendChild(img);
			var dato=[img];
			console.log(dato);
	
	 var ruta=dato[0].src;


	



	 		$('#images').attr('src',ruta);
	 	
	$('#images').attr('src',ruta);
			anchoP=dato[dato.length-1].width;
			//console.log(anchoP);
			altoP=dato[dato.length-1].height;
			relacionO=altoP/anchoP;



if( /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) ) {
   
    	$('#images').selectAreas({
			onChanged:null,
			maxSize: [0, 0],
					onChanging:null,
					maxAreas: 1,
					areas: [
						{
							x: 0,
							y: 0,
							width: 150,
							height: 150,
						}
					]
});

}






			recortar(anchoP, altoP,'#images','#ancho','#alto');
	

        
			$('#ancho').on('change', function() {
			    if(relacion==false){
				if($('#ancho').val()>anchoP){
					$('#ancho').val(anchoP);
					$('#alto').val(altoP);
				}
			    }
			});
         

           
			$('#alto').on('change', function() { 
			    if(relacion==false){
				if($('#alto').val()>altoP){
					$('#alto').val(altoP);
					$('#ancho').val(anchoP);

				}
            }
			 });
                
            

			pesoP= Math.round(inputGroupFile01.files[0]['size']/1000);
			$('#ancho').val(anchoP);
			$('#alto').val(altoP);
			$('#peso').val(pesoP);
			   return anchoP;







			$('#peso').on('change', function() {
				console.log($('#peso').val());
				if($('#peso').val()>pesoP){
					$('.upload').attr('disabled','disabled');
					$('#peso').text(peso);
					$('#content').css('display','block');


					$('#content').text('El el tamaño es mayor a la original');
				}else{
					$('.upload').removeAttr('disabled'); 
					$('#content').css('display','none');
				}


			});





		};
		img.src = direccion.createObjectURL(file);

	}

});



 







//-----Cierre Subir Modal--------------


//$('#selectCategoria').select2();
//$('#selectDestinox').select2();

$.get(`https://melorautopartes.com/dashboard/images`, 'data=' + JSON.stringify({
    action: 'selectDestino',
    rand:Math.random(),
}), (data) => {
	console.log(data);
	data.forEach(tour => {

		$('#selectDestinox').append($('<option>', {
			value: tour,
			text: tour,

		}));

	});
}, 'json');


//$.get(`https://melorautopartes.com/dashboard/images`, 'data=' + JSON.stringify({action: 'selectCategoria'}), (data) => {
//	console.log(data);
//	data.forEach(tour => {
//
//		$('#selectCategoria').append($('<option>', {
//			value: tour,
//			text: tour,
//
//		}));
//
//	});
//}, 'json');



/*$('#modal-footer').html("<button type='button' class='btn btn-default' data-dismiss='modal' onclick='closee();'>Close</button>"+
"<button type='button' class='btn btn-primary' id='saveSubir' onclick='validarSubir();'>Save changes</button>");*/


$('#saveSubir').click(() => {
	var nombre = $('#nombreDestino').val();
	var urlFoto = $('#urlFotox').val();
	var destino = $('#selectDestinox').val();
	var descripcionImg = $('#descripcionImg').val();
	var peso = $('#peso').val();
	var alto = $('#alto').val();
	var ancho = $('#ancho').val();
	var categoria = $('#selectCategoria').val();

	console.log("este es el url foto" + urlFoto);
	console.log(urlFoto,destino,descripcionImg,peso,alto,ancho,categoria);
	$.get('https://melorautopartes.com/dashboard/images', 'data=' + JSON.stringify({
		action: 'insertInfo',
		rand:Math.random(),
		urlFoto,
		destino,
		descripcionImg,
		peso,
		alto,
		ancho,
		categoria,
		nombre,

	}),'script')

});

}

function media5(){
    
$('.tagsinput').css('width','337px');
$('#cet').css('top','-62px');
$('#kye').css('left','340px'); 

    
}
function media6(){
    
$('.colA6').css('top','-84px');
$('.divSubir').css('top','31rem');
$('.divSubir').css('left','21px');
$('#kye').css('left','444px');
    
    
    
}
function media7(){
$('#cet').css('top','-193px');
$('#kye').css('top','-72px'); 
$('#savePage').css('top','-33px');
$('#savePage').css('left','12%');  
$('.divSubir').css('top','29.5rem');
}
function media1(){
    
console.log(' si es menor 550');
$('.colA').css('width','13rem');

$('.colA1').css('width','12rem');
$('.colA1').css('left','-1rem');

$('.colA2').css('left','-1rem');
$('.colA2').css('width','13rem');

$('.colA3').css('width','12rem');

$('.colA4').css('width','24rem');


$('.colA5').css('width','26rem');


$('.tagsinput').css('width','355px');

}
function media8(){
    console.log('502px');
$('#kye').css('left','394px'); 
$('.divSubir').css('top','26.5rem'); 
}
function Media2(){
    
console.log(' si es menor 413');
$('.colA5').css('width','22rem');


$('.colA7').css('width','22rem');


$('.colA').css('width','8rem');

$('.colA1').css('width','7rem');
$('.colA1').css('left','-1rem');


$('.colA3').css('width','8rem');
$('.colA3').css('top','-7rem');
$('.colA3').css('right','-13rem');

$('.colA2').css('left','-8rem');
$('.colA2').css('width','16rem');
$('.colA2').css('top','-2rem');

$('.colA4').css('width','24rem');
$('.colA4').css('left','-15px');

$('.colA6').css('width','23rem');
$('.colA6').css('top','-5rem');

$('.tagsinput').css('width','293px');


$('#savePage').css('top','-31px');  

$('#cet').css('top','-180px');

$('.divSubir').css('top','22.5rem');

$('#kye').css('top','-73px'); 
$('#kye').css('left','305px'); 

$('#inp').css('top','9px'); 
$('#inp').css('left','322px'); 

$('#nombrei').css('left','115px');


$('.colA4').css('margin-top','-3rem');

$('#Imagcol').removeClass('col-3');
$('#Imagcol').addClass('col-lg-12');
}
function Media3(){
console.log(' si es menor 360');
$('.colA5').css('width','22rem');

$('.colA7').css('width','21rem');


$('.colA').css('width','8rem');

$('.colA1').css('width','7rem');
$('.colA1').css('left','-1rem');


$('.colA3').css('width','8rem');
$('.colA3').css('top','-7rem');
$('.colA3').css('right','-13rem');

$('.colA2').css('left','-8rem');
$('.colA2').css('width','15rem');
$('.colA2').css('top','-2rem');

$('.colA4').css('width','23rem');
$('.colA4').css('left','-15px');

$('.colA6').css('width','22rem');
$('.colA6').css('top','-5rem');

$('.tagsinput').css('width','293px');


$('#savePage').css('top','-32px');  

$('#cet').css('top','-180px');
$('#cet').css('left','322px');

$('#kye').css('top','-77px'); 
$('#kye').css('left','305px'); 

$('#inp').css('top','9px'); 
$('#inp').css('left','308px'); 

$('#nombrei').css('left','115px');


$('.colA4').css('margin-top','-3rem');


$('#Imagcol').removeClass('col-3');
$('#Imagcol').addClass('col-lg-12');
}
function Media4(){
console.log(' si es menor 376');
$('.colA5').css('width','20rem');

$('.colA7').css('width','21rem');


$('.colA').css('width','8rem');

$('.colA1').css('width','7rem');
$('.colA1').css('left','-1rem');


$('.colA3').css('width','8rem');
$('.colA3').css('top','-7rem');
$('.colA3').css('right','-13rem');

$('.colA2').css('left','-8rem');
$('.colA2').css('width','14rem');
$('.colA2').css('top','-2rem');

$('.colA4').css('width','22rem');
$('.colA4').css('left','-15px');

$('.colA6').css('width','20rem');
$('.colA6').css('top','-5rem');

$('.tagsinput').css('width','259px');


$('#savePage').css('top','-32px');  
$('.divSubir').css('top','20.5rem');

$('#cet').css('top','-192px');
$('#cet').css('left','300px');

$('#kye').css('top','-74px'); 
$('#kye').css('left','285px'); 

$('#inp').css('top','9px'); 
$('#inp').css('left','281px'); 

$('#nombrei').css('left','115px');


$('.colA4').css('margin-top','-3rem');


$('#Imagcol').removeClass('col-3');
$('#Imagcol').addClass('col-lg-12');
}
function recortar(anchoP,altoP,imageCont,an,al){
    	//	$("#inputGroupFile01").val('');





$('#images').css('width','inherit');
//$('#images').css('height','inherit');
$('#bloqueo').removeAttr('disabled');
//imageCont es el id del contenedor de la imagen que estamos seleccionando            
//an es un input donde se escribe el ancho de la imagen
//al es un input donde se escribe el alto de la imagen
$('#images').selectAreas({
			aspectRatio:0,
			onChanging:escribirDimenciones, //mmiestras cambia el area de seleccion 

			maxSize: [0, 0],
					maxAreas: 1,
					width: anchoDiv,
					allowSelect: true,
					allowMove: true,


});


	var papa=$("#images").parent();
  	papa[0]['id']='papa';
    console.log(papa[0]['id']);
   console.log(papa);
   $('#papa').height(altoDiv);
   var hijo=$('#papa').children();
console.log(hijo);

	var	altoImagen=$('#papa').height();
	console.log(altoImagen);
	
	
	
	//para calcular el ancho de la imagen con relaccion de asecto
	var anchoEscala1=altoImagen/altoP;
	console.log(anchoEscala1);
	
	var escalaAncho1=altoImagen*anchoEscala1;
	console.log(escalaAncho1);

		
		$('#images').css('width','100%');
	$('#images').css('height','');



}
function escribirDimenciones(alto,ancho,relacionSelec,imageCont,an,al){
			   //$('#images').selectAreas('reset');


    	var areas = $('#images').selectAreas('areas');
    	console.log(' si areas '+areas);
    
    	                var x1=areas[0]['x'];
						var y1=areas[0]['y'];
						var y2=areas[0]['height']+y1;
						var x2=areas[0]['width']+x1;
						var ancho=areas[0]['width'];
						var alto=areas[0]['height']
						
    	
					
                       
			    var altoEscala;
        		var anchoEscala=$('#images').width();
        		var escala=(anchoEscala/anchoP);
			 console.log(anchoP+' anchop');
            ancho=Math.round(ancho/escala);
			 alto=Math.round(alto/escala);
			  //x2E=Math.round(x2/escala);
			 //y2E=Math.round(y2/escala);
			 	 //y2E=Math.round(y2/escala);
			 
			    	console.log(areas[0]);  
			        console.log('ancho '+ancho);
					console.log('alto'+alto);
				
		
		    
			 
			   	var relacionAspecto=altoP/anchoP;
			   arrastre=true;
			   	 //console.log(anchoP);
			   	//console.log(altoP);
			   	//console.log(relacionAspecto);
			   	
        	
        		 
        		
        	
		
			 
			
			 

			 
			if(alto==0){
			    
			anchoEscala=(ancho)/escala;// se combiente la medida en tamaño fisico
			altoEscala=anchoEscala*relacionAspecto;
			

		    $(an).val(anchoEscala);
			$(al).val(Math.round(altoEscala));
		
			
			
		}


		if(ancho==0){
		
			altoEscala=(alto)/escala;
			anchoEscala=altoEscala/relacionAspecto;
			
			$('#ancho').val(Math.round(ancho));
			$('#alto').val(Math.round(alto));
			
			
			
		}

		if(ancho>0&&alto>0){
       
			altoEscala=(alto)/escala;
			anchoEscala=(ancho)/escala;
			$('#ancho').val(Math.round(ancho));
			$('#alto').val(Math.round(alto));

		}

			
			   	    
	 
    }
function arrayInput(){
   var array=vadidarInputs();
   console.log(array);
   var numeroElementos=array.length;
   
   
   
array.forEach(function(elemento) {
           
  //console.log(elemento);
            document.getElementById(elemento).style.border='solid red 2px';
            document.getElementById('select2-selectCategoria-container').style.lineHeight = "23px";
            document.getElementById('select2-selectDestinox-container').style.height = "28px";
});
https://www.google.com/aclk?sa=l&ai=DChcSEwi4tOSclIHoAhWShsAKHXz_AZEYABA2GgJpbQ&sig=AOD64_1r8-i4BsfuNo4x7FuTFN1Q45QAXA&adurl&ctype=5&ved=2ahUKEwjUsNWclIHoAhVFSawKHfhuDGUQvhd6BQgBEJYB
return numeroElementos;

}
function pb02(){
    
    	var formData = new FormData();
	var files = $('#inputGroupFile01')[0].files[0];
	formData.append('file',files);

$.ajax({
		url: 'https://melorautopartes.com/dashboard/indexImg',
		type: 'post',
		data:formData,
		contentType: false,
		processData: false,
		success:function(data) {
		    var data=JSON.parse(data);
             Imagen02(data);
		}
	});	

    
}
function Imagen02(data){
   // alert("en imagen 02");
        alert(data['mensaje']);
	    console.log("ancho "+data['ancho']);
	    console.log("alto "+data['alto']);

	    if(data['mensaje']='Es rectangular   la  imagen'){
	        console.log(" se tiene que poner el area select");
	            	$('#images').selectAreas({
			onChanged:null,
			maxSize: [0, 0],
					onChanging:null,
					maxAreas: 1,
					areas: [
						{
							x: 0,
							y: 0,
							width: 150,
							height: 150,
						}
					]
});
  
	    }

	    
	    
	    
}
function  alertas(data){
	$('#myAlert').css('display','block');
	if($('#myAlert').hasClass('alert-danger')){
		$('#myAlert').removeClass('alert-danger');
	}
	if($('#myAlert').hasClass('alert-info')){
		$('#myAlert').removeClass('alert-info');
	}


	$('#myAlert').addClass(data['tipo']); 
	$('#myAlert').text(data['mensaje']); 
console.log(data['bandera'])
if(data['bandera']=='true'){
$('#urlFoto').val(data['url']);
      
      

     stringPrueba = stringPrueba + $('.tags').val();
     console.log(stringPrueba)
	var nombre = $('#nombreDestino').val();
	var urlFoto = $('#urlFoto').val();
	var destino = $('#selectDestinox').val();
	var descripcionImg = $('#descripcionImg').val();
	var peso = data['peso'];
	var alto = $('#alto').val();
	var ancho = $('#ancho').val();
	var categoria = $('#selectCategoria').val();
	var keywords = $('#keywords-tour-imagenes-up').val();
	var typeProduct = $('#typeProduct').val()

	console.log("este es el url foto" + urlFoto);
	console.log(urlFoto,destino,descripcionImg,peso,alto,ancho,categoria,keywords);
	
if( $('#RenombrarCheck').is(':checked') ) {
	  	$.get('https://melorautopartes.com/dashboard/images', 'data=' + JSON.stringify({
		action: 'remplazar',
		rand:Math.random(),
		urlFoto,
		destino,
		descripcionImg,
		peso,
		alto,
		ancho,
		categoria,
		nombre,
		keywords,

	}),'script')  

    
}else{
	
		 $.get('https://melorautopartes.com/dashboard/images', 'data=' + JSON.stringify({
		action: 'insertInfo',
		rand:Math.random(),
		urlFoto,
		destino,
		descripcionImg,
		peso,
		alto,
		ancho,
		categoria,
		nombre,
		keywords,
		typeProduct

	}),'script')

	
}

	
	arrays = [];
	
}
	setTimeout(alertFunc,15000);
	data=0;
}  
function alertFunc() {
	$('#myAlert').css('display','none');
}
function obtenerDimenciones(x){

	obtenerParametros();
	var hD,wD;
	hD=alto;
	wD=ancho;
	var relacionD=hD/wD; 



	if(x=='alto'){
		wD=hD/relacionO;
		ancho=wD;
		return wD;
	}


	if(x=='ancho'){
		hD=ancho*relacionO;
		alto=hD;
		return hD;
	}

}

function mostrar(id){
    var destinoTour = sessionStorage.getItem("destinoTour");
    console.log(destinoTour);
   
    //console.log(destinoTour);
	console.log(id.id);

	var x = id.id;
	
	 localStorage.setItem("idUrlFoto", x);
	 
	 

	$('#prueba2').css('visibility','hidden');
	var input = $("#urlFoto" + x).val();
	if(id.className == 'btn btn-info'){
		$('#urlFotov' + x).val('');
	}else{
		$('#urlFoto' + x).val('');
	}
	 var page=$('#typeProduct').val();
	 if(page == 'hotel'){
	     destinoTour = 'Hotel';
	 }
	$.get('https://melorautopartes.com/dashboard/images', 'data=' + JSON.stringify({
		action: 'mostrarImagen',
		rand:Math.random(),
		input,
		x,
		destinoTour,

	}),(response) => {
	    
		$('#prueba').html(response);
		console.log(destinoTour);
		if(page != 'Fichas Hotel'){
	        $('#selectDestino').val(destinoTour).trigger('change');
		    $("#selectDestino option[value='"+ destinoTour +"']").attr("selected","selected");
	    }
		

		$('#urlFotoNew').val('');
		$('#urlFotoNew').removeAttr('name');
		if(id.name != 'new'){



			//clickBoton(id);
			if(id.className == 'btn btn-info'){
				var xd= document.getElementById('urlFotov' + x);
			}else{
				var xd= document.getElementById('urlFoto' + x);
			}
			clickBotons(xd);
			
		}


	},'text');
}

function closee(){
	var inputax = $('#inputAux').val();
	$('#urlFoto').val(inputax);
	$("#urlFoto").removeAttr('value');
	$('#urlFoto').attr("value",inputax);

	console.log(inputax);
}

function selectDestino(){
	$.get(`https://melorautopartes.com/dashboard/images`, 'data=' + JSON.stringify({
	    action: 'selectDestino',
	    rand:Math.random(),
	}), (data) => {
		data.forEach(tour => {

			$('#selectDestino').append($('<option>', {
				value: tour,
				text: tour,

			}));

		});
	}, 'json');
}

function selectCategoria(){
	$.get(`https://melorautopartes.com/dashboard/images`, 'data=' + JSON.stringify({
	    action: 'selectCategoria',
	    rand:Math.random(),
	}), (data) => {
		data.forEach(tour => {
          
			$('#selectCategorias').append($('<option>', {
				value: tour,
				text: tour,

			}));

		});
	}, 'json');
}

function validarSubir(){

	var nombre = $('#nombre').val();
	var urlFoto = $('#urlFotox').val();
	var destino = $('#selectDestinox').val();
	var descripcionImg = $('#descripcionImg').val();
	var peso = $('#peso').val();
	var alto = $('#alto').val();
	var ancho = $('#ancho').val();
	var categoria = $('#selectCategoria').val();


	if(nombre == "" || nombre.length == 0){
		alert('ERROR: El campo Titulo no debe ir vacío o lleno de solamente espacios en blanco');
		return false;
	}
	if(destino == "" || destino.length == 0){
		alert('ERROR: El campo Destino no debe ir vacío o lleno de solamente espacios en blanco');
		return false;

	}
	if(urlFoto == "" || urlFoto == 0){
		alert('ERROR: El campo UrlFoto no debe ir vacío o lleno de solamente espacios en blanco');
		return false;

	}
	if(descripcionImg == "" || descripcionImg.length == 0 ){
		alert('ERROR: El campo Descripcion no debe ir vacío o lleno de solamente espacios en blanco');
		return false;

	}
	if(peso == "" || peso.length == 0 ){
		alert('ERROR: El campo Peso no debe ir vacío o lleno de solamente espacios en blanco');
		return false;

	}
	if(alto == "" || alto.length == 0 ){
		alert('ERROR: El campo Alto no debe ir vacío o lleno de solamente espacios en blanco');
		return false;

	}
	if(ancho == "" || ancho.length == 0){
		alert('ERROR: El campo Ancho no debe ir vacío o lleno de solamente espacios en blanco');
		return false;

	}
	if(categoria == "" || categoria.length == 0 ){
		alert('ERROR: El campo de las categoria no debe ir vacío o lleno de solamente espacios en blanco');
		return false;
	}
	else{
		$('#saveSubir').attr('data-dismiss','modal');
	}

}

function agregarlist(){
	$('#new').css('display','block');
	$('#new').html("<div class='row row-dashboard'>"+
		"<div class='col-md-4' style='text-align:center;' id='colUrl'>"+
		"<div class='form-group'>"+
		"<label for='formGroupExampleInput2'>URL foto</label>"+
		"<input type='text' class='form-control'  placeholder='urlFoto' id='urlFotoNew' name='new'required>"+
		"</div>"+
		"</div>"+
		"<div class='col-md-4' style='text-align:center;' id='colTipo'>"+
		"<div class='form-group'>"+
		"<div class='row LM1'>"+
		"<div class='col-12'>"+
		"<label for='formGroupExampleInput2'>Tipo de Imagen</label>"+
		"</div>"+
		"<div class='col-12'>"+
		"<select id='selectTipoNew' class='js-example-responsive' style='width: 100% !important'>"+
		"<option selected='selected'>Tipo</option>"+
		"<option value='new'>Nuevo Tipo</option>"+
		"</select>"+
		"</div>"+
		"</div>"+
		"</div>"+
		"</div>"+
		"<div class='col-md-2' style='display:none;' id='campoNew'>"+
        "<div class='form-group'>"+
        "<label for='formGroupExampleInput2'>Nuevo Tipo</label>"+
        "<input type='text' class='form-control'  placeholder='agrega un nuevo tipo' id='newIcon'  required style='margin-top: -5px;'>"+
        "</div>"+
        "</div>"+
		"<div class='col-md-2' style='text-align:center;margin-top:-5px;'>"+
		"<div class='form-group'>"+
		"<button type='button' class='btn btn-primary' data-toggle='modal' data-target='.bd-example-modal-lg' id='' onclick='mostrar(this);mainModal();' style='margin-top: 34px;' name='new'>Selecciona Foto</button>"+
		"<div class='modal fade bd-example-modal-lg' role='dialog' aria-labelledby='myLargeModalLabel' aria-hidden='true' id='MyModal' data-keyboard='false' data-backdrop='static' >"+
		"<div class='modal-dialog modal-xl'>"+
		"<div class='modal-content'>"+
		"<div class='col-12 mt-4 margin-bottom:0px;' >"+
		"<div class='row'>"+
		"<div id='Imagcol'>"+
		"<form class='form-inline'>"+
		"<button class='btn btn-outline-success' type='button' id='seleccionarBoton' onclick='seleccionar();' style='background-color: #28a745;color:white;'>Seleccionar</button>"+
		"<button class='btn btn-outline-success' type='button' id='subirBoton' onclick='subir();'>Subir Fotografíaaa</button>"+
		"</form>"+
		"</div>"+
		"<div class='col-4' style='margin-left: -57px; margin-top: 6px; width: -96px;' id='colDestino'>"+
		"<select class='js-example-responsive' style='width: 50%' id='selectDestino'>"+
		"<option selected='selected' default='default' default='default'>Destino</option>"+
		"</select>"+
		"<div class='col-4' style='margin-left: 220px;margin-top: -27px;' id='colCategoria'>"+
		"<select class='js-example-responsive' style='width: 210%' id='selectCategorias'>"+
		"<option selected='selected' default='default' value='default'>Categoria</option>"+
		"</select>"+
		"</div>"+
		"</div>"+
		"</div>"+
		"<div class='container'>"+
		"<div class='row seleccionar' id='seleccionar'>"+
		"<div class='col-8 mt-5 mb-5' id='prueba'>"+
		"</div>"+
		"<div class='col-4 mt-5 mb-5' id='prueba2'>"+
		"</div>"+
		"</div>"+
		"<div class='row subir' id='subir' style='display: none;margin-bottom:0px;margin-top: 40px;'>"+
		"</div>"+
        "<div class='modal-footer' style='margin-bottom:70px;text-align: center;'>"+
        "<div class='col-12' style='text-align:left;/*! margin-left: -69px; */'>"+
        "<div class='row' style='margin-left: 218px;'>"+
        "<button type='button' data-dismiss='modal' onclick='closee();' class='btn btn-danger' style='margin-right: 27px;'>Close</button>"+
        "<button type='button' class='btn btn-primary' onclick='validar();' id='save'>Subir Foto</button>"+
        "</div>"+
        "</div>"+
        "</div>"+
		"</div>"+
		"</div>"+
		"</div>"+
		"</div> "+
		"</div>"+
		"</div>"+
		"</div>"+
		"<div class='col-md-2 text-center' style='margin-top: 30px;'>"+
		"<button type='button' class='btn btn-success' id='enviarImagenNew' onclick='imagenDatos();'>Enviar</button>"+
		"<div class='help-tip'style=' top:7px !important;'>"+
		"<p style='z-index:9999'><b>Youtube:</b> Para agregar videos de esta plataforma agregar el codigo embebido y seleccionar en tipo de Imagen 'Youtube carrusel' .<br><br><b>Ejemplo:</b> https://www.youtube.com/embed/GRfQ1xsesqI</p>"+
		"</div>"+
		"</div>"+
		"</div>"+
		"<div id='auxVideo' style='display:none'>"+
		"</div>");

	//$('#selectTipoNew').select2();
	
	$('#selectTipoNew').change(() => { 
	     var tipo = $('#selectTipoNew').val();
	     
	if(tipo == 'new'){
	$('#colUrl').removeClass('col-md-4');
	$('#colUrl').addClass('col-md-3');
	
	$('#colTipo').removeClass('col-md-4');
	$('#colTipo').addClass('col-md-3');
	    
    $('#campoNew').css('display','block');
    
    }else{
    $('#colUrl').removeClass('col-md-3');
    $('#colUrl').addClass('col-md-4');
    
    $('#colTipo').removeClass('col-md-3');
    $('#colTipo').addClass('col-md-4');
    
    $('#campoNew').css('display','none');
    }
	    
	})


	$.get(`https://melorautopartes.com/dashboard/images`, 'data=' + JSON.stringify({
	    action: 'selectTipo',
	    rand:Math.random(),
	}), (data) => {
		data.forEach(tour => {
			$('#selectTipoNew').append($('<option>', {
				value: tour,
				text: tour,

			}));
			
		});
	}, 'json');



}

function enviarImagenNew(){
    var page = $('#typeProduct').val();
    var id =''; 
    var idImagenes ='';
    var idProduct ='';
    var tipo ='null';
    var idProductSec = 'null';
    var idTypeProduct = 'null';
    var product = 'null';
    var urlImg = 'null';
    var nameTypeProduct = 'null';
    console.log(page);
    if(page == 'tour'){
        product = 'tour';
        idImagenes =  $('#urlFotoNew').attr('name');
    	idProduct = $('#select-tour-edit').val();
    	tipo = $('#selectTipoNew').val();
    }else{
        product = 'hotel';
        
    	idImagenes =  $('#urlFotoNew').attr('name');
    	idProduct = $('#idHotel').val();
    	idProductSec = $('#tipoHabitacion').val();
    	nameTypeProduct = $('#tipoHabitacion option:selected').text();
    	
    	 
    }
	
	
	if(tipo == 'youtube carrusel'){
	              var urlVideo = $('#urlFotoNew').val();
	    		$.get('https://melorautopartes.com/dashboard/images', 'data=' + JSON.stringify({
				action: 'insertVideo',
				tipo,
				rand:Math.random(),
				urlVideo,
				
			}),(response) =>{
			    
			console.log(response);
			$('#auxVideo').html(response);
			
			idImagenes = $('#auxVideos').val();
			console.log(idImagenes);
			
			$.get('https://melorautopartes.com/dashboard/images', 'data=' + JSON.stringify({
			action: 'insertFichasToursImagenes',
			rand:Math.random(),
			idImagenes,
			idProduct,
			tipo,
			product,
			

		}),'text');
		imagenDatos();
		$('#new').css('display','none');
		//alert('Se ha añadido correctamente la Imagen');
		
	},'text');
	}else{		

    
    	if(tipo == 'Tipo'){
    		alert('Falta de llenar el campo "Tipo"');
    	}else{
    
             if(tipo == 'new'){
        	 tipo = $('#newIcon').val();
        	}
        		console.log(idImagenes,idProduct,tipo);
        		$.get('https://melorautopartes.com/dashboard/images',`data=${JSON.stringify({
        		    action: 'insertFichasToursImagenes',
        			rand:Math.random(),
        			idImagenes,
        			tipo,
        			product,
        			idProduct,
        			idProductSec,
                    nameTypeProduct
        		})}`,function(response){
        		    showPreview();
            		$('#new').css('display','none');
            		alert('Se ha añadido correctamente la Imagen');
        		})
        
        		
       
    
        }
	}

}



function updateImagen(idx){
    var page = $('#typeProduct').val();
    var id =''; 
    var idImagenes ='';
    var idProduct ='';
    var tipo ='';
    var idProductSec = 'null';
    var idTypeProduct = 'null';
    var product = 'null';
    var urlImg = 'null';
    var nameTypeProduct = 'null';
    if(page != 'Fichas Hotel'){
        product = 'tour';
        id = idx.id;
	    idImagenes =  $('#urlFoto' + idx.id).attr('name');
	    idProduct = $('#select-tour-edit').val();
	    tipo = $('#selectTipo' + idx.id).val();    
    }else{
        product = 'hotel';
        id = idx.id;
	    idImagenes =  $('#urlFoto' + idx.id).attr('name');
	    idProduct = $('#idHotel').val();
	    idProductSec = $('#tipoHabitacion').val();
	    nameTypeProduct = $('#tipoHabitacion option:selected').text();
	    
	    tipo = $('#selectTipo' + idx.id).val();    
    }
	

	console.log(id,idImagenes,idProduct,tipo);

	if(tipo == 'youtube carrusel' || tipo == 'youtube principal'){
		idImagenes =  $('#urlFotov' + idx.id).attr('name');
	}

	$.get('https://melorautopartes.com/dashboard/images', 'data=' + JSON.stringify({
		action: 'updateImagen',
		rand:Math.random(),
		id,
		idImagenes,
		idProduct,
		tipo,
		idProductSec,
        nameTypeProduct,
        product
        

	}),'text');

	alert("Se ha actualizado correctamente");

}




function deleteImagen(idx){
    var page = $('#typeProduct').val();
	var id=idx.id;

	console.log(id);

	$.get('https://melorautopartes.com/dashboard/images', 'data=' + JSON.stringify({
		action: 'deleteImagen',
		rand:Math.random(),
		id,
        page
	}),'text');

	alert('Se ha eliminado correctamente la imagen');
}

function deleteBancoImagen(idx){
    
	var id=idx.id;

	console.log(id);
	
    $.get('https://melorautopartes.com/dashboard/images', 'data=' + JSON.stringify({
	   action: 'validarImagenes',
	   rand:Math.random(),
	   id,


		}),(response) =>{
		    console.log(response);
		    if(response == 1 || response == false){
		        if(confirm('¿Deseas Eliminarla')){
		            
	$.get('https://melorautopartes.com/dashboard/images', 'data=' + JSON.stringify({
		action: 'deleteBancoImagen',
		rand:Math.random(),
		id,

	}),'text');

	alert('Se ha eliminado correctamente la imagen');
 
closee();
$('#' + id).attr('data-dismiss','modal');
$('#prueba2').html('');
		        }else{
                   return false;
                } 
		        $(".modal").hide();
		        $('body').removeClass('modal-open');
		      $('.modal-backdrop').remove();
		    }else if(response != 1 || response != false){
		        alert("La imagen esta en los tours : " + response);
		        $(".modal").hide();
		        $('body').removeClass('modal-open');
		      $('.modal-backdrop').remove();
		    }
		    
		    
			$('#prueba').html(response);

		},'text');
	    
	
	


  

    
}

function updateinfos(id){
        
		
			
actualizarMeta(id);

			if(categoria == 'youtube principal' || categoria == 'youtube carrusel'){
				urlFoto = id.className;
		var ruta=$('#urlFotoNew').val(urlFoto);
				$('#urlFotov' + id.align).removeAttr('src');
				$('#urlFotov' + id.align).attr('src',id.className);
				$('#urlFotov' + id.align).attr('name',id.id);

			}

			
	var b=validar(nombre,destino,urlFoto,descripcionImg,peso,alto,ancho,categoria);
	
	 

if(b == true){
			//console.log(urlFoto,destino,descripcionImg,peso,alto,ancho,categoria,nombre);
			
		
}
    
}

function validar(nombre,destino,urlFoto,descripcionImg,peso,alto,ancho,categoria){

	     
	if(nombre == "" || nombre.length == 0){
		alert('ERROR: El campo Titulo no debe ir vacío o lleno de solamente espacios en blanco');
		return false;
	}
	if(destino == "" || destino.length == 0){
		alert('ERROR: El campo Destino no debe ir vacío o lleno de solamente espacios en blanco');
		return false;

	}
	if(urlFoto == "" || urlFoto == 0){
		alert('ERROR: El campo UrlFoto no debe ir vacío o lleno de solamente espacios en blanco');
		return false;

	}
	if(descripcionImg == "" || descripcionImg.length == 0 ){
		alert('ERROR: El campo Descripcion no debe ir vacío o lleno de solamente espacios en blanco');
		return false;

	}
	if(peso == "" || peso.length == 0 ){
		alert('ERROR: El campo Peso no debe ir vacío o lleno de solamente espacios en blanco');
		return false;

	}
	if(alto == "" || alto.length == 0 ){
		alert('ERROR: El campo Alto no debe ir vacío o lleno de solamente espacios en blanco');
		return false;

	}
	if(ancho == "" || ancho.length == 0){
		alert('ERROR: El campo Ancho no debe ir vacío o lleno de solamente espacios en blanco');
		return false;

	}
	if(categoria == "" || categoria.length == 0 ){
		alert('ERROR: El campo de las categoria no debe ir vacío o lleno de solamente espacios en blanco');
		return false;
	}
	return true;
	
}

function actualizarMeta(id){
            var select= id.id;
			var nombre = $('#nombre'+ select).val();
			var categoria = $('#selectCategoria' + select).val();
			var urlFoto = id.src;
			var destino = $('#selectDestinox'+ select).val();
			var descripcionImg = $('#descripcionImg'+ select).val();
			var peso = $('#peso'+ select).val();
			var alto = $('#alto' + select).val();
			var ancho = $('#ancho'+ select).val();
			var urlMeta=$('#ruta').val();
		    var keyw=$('#keywords-tour-imagenes').val();
			 
    

var objModificar=JSON.stringify({nombre:nombre,city:destino,descripcion:descripcionImg,ruta:urlMeta,keyword:keyw,action:'modificarMetadatos'});
console.log(objModificar);
$.ajaxSetup({async:false});
	$.ajax({
		url:'https://melorautopartes.com/dashboard/indexImg',
		type:'POST',
		data:{objModificar},
		success:function(data){
		     var data=JSON.parse(data);
			 
			 if(data['mensaje']=='true'){
			     	$.get('https://melorautopartes.com/dashboard/images', 'data=' + JSON.stringify({
				action: 'updateInfo',
				rand:Math.random(),
				select,
				urlFoto,
				destino,
				descripcionImg,
				peso,
				alto,
				ancho,
				categoria,
				nombre,
				keyw,

			}),(response) =>{
			
	$('#inputAuxiliar').html(response);
    var succes= $('#succesAux').val();
    
    if(succes == '1'){
      alert("Se ha actualizado correctamente");
      
    }
    else{
      alert("Hubo un error, comunicate con sistemas");
    }
			    
			},'text');

			 }else{
			     
			     alert('Hubo un error, intenta mas tarde ');
			 }
			 
			 
	
			
		
			

			
		}

	});
	$.ajaxSetup({async:true});

} 










function imagenDatos(val = null){
    
    $.get('https://melorautopartes.com/dashboard/AccessControl',`data=${JSON.stringify({
        action:'recuperarSession'
    })}`,function(response){
        response = JSON.parse(response);
        console.log(response);
        if(response.error == 'true'){
            $.get('https://melorautopartes.com/dashboard/users',`data=${JSON.stringify({
                rand:Math.random(),
                action:'rec_session',
                
            })}`,function(response){
                console.log(response);
                $('#modal-content-body').html(response);
                $('.modalP').css('display','block');
            });
        }else if(response.error == 'false'){
        
            var page = $('#typeProduct').val();
            var product = 'tour';
            var idHotel = 0;
            var idHab = 0;
            var tipoHab = 'null';
            if(page == 'Fichas Hotel'){
                product = 'hotel';
                var tour = $('select[id=select-tour-edit]').val();
                tipoHab = $('#tipoHabitacion option:selected').text();
                
            }else{
                idHotel = $('#miselect20').val();      
                var tour = $('select[id=select-tour-edit]').val();
            }
          
            $('#bodyCard').css('display','block');
            $('#agregarlist').css('display','block');
        
            $.get('https://melorautopartes.com/dashboard/images', 'data=' + JSON.stringify({
            action: 'selectImagenes',
            rand:Math.random(),
            idHotel,
            idHab,
            tipoHab,
            product,
            tour
        
          }), (response) => {
           $('#rowImagenes').html(response);
          var destinoTour =  $('#destinoTour').val();
          //var idTour = $('#idTour').text();
           sessionStorage.setItem("destinoTour", destinoTour);
          // sessionStorage.setItem("idTour", idTour);
        
         }, 'text');
            if(val != 1){
            enviarImagenNew();    
        }
        }
    });

}

function showPreview(val = null){
    console.log('showPreview');
//Detect page "Fichas Tours" or "Fichas Hotel"
    var page = $('#typeProduct').val();
    var product = 'tour';
    var idHotel = 0;
    var idHab = 0;
    var tipoHab = 'null';
    if(page == 'tour'){
        
        var tour = $('select[id=select-tour-edit]').val();
    }else{
//        asignar hotel
    }
  
  $('#bodyCard').css('display','block');
  $('#agregarlist').css('display','block');

 $.get('https://melorautopartes.com/dashboard/images',`data=${JSON.stringify({
    action: 'selectImagenes',
    tour,
    rand:Math.random(),
    product
 })}`,function(response){
     
    console.log(response)
   $('#rowImagenes').html(response);
  var destinoTour =  $('#destinoTour').val();
  //var idTour = $('#idTour').text();
   sessionStorage.setItem("destinoTour", destinoTour);
  // sessionStorage.setItem("idTour", idTour);

 })


}
function mainModal(){

  selectDestino();
  selectCategoria();
  //$('#selectDestino').select2();
  //$('#selectCategorias').select2();
var x = localStorage.getItem("idUrlFoto");


//-----------------------------------------------------------------------------------------------------
$('#selectDestino').change(() => {
 // $('#prueba2').css('visibility','hidden');
 var x = localStorage.getItem("idUrlFoto");

  var categoria = $('#selectCategorias').val();
  
  var destino=$('#selectDestino').val();



  if(categoria != 'default' &&  destino != 'default'){

    $.get('https://melorautopartes.com/dashboard/images', 'data=' + JSON.stringify({
      action: 'selectTwoDestino',
      rand:Math.random(),
      destino,
      categoria,
      x,


    }),(response) =>{
      $('#prueba').html(response);
      
      var cont = $('#contImage').val();
      
         if(cont < 32 || cont == 'empty')
	    {   
	        console.log("si entroooooooooooooooo");
	        $('#fixed').removeClass("position-fixed");
	        //$('#elCol').removeClass("col-5");
	        
	        $('#elCol').addClass('col-12');
	    }else{
	     	        $('#fixed').addClass("position-fixed");
	        $('#elCol').removeClass("col-12");
	        
	        $('#elCol').addClass('col-5');
	    }


    },'text');

  }

  if(categoria == 'default' && destino != 'default'){
    $.get('https://melorautopartes.com/dashboard/images', 'data=' + JSON.stringify({
      action: 'select',
      destino,
      rand:Math.random(),
      x,


    }),(response) =>{
        console.log(response);
      $('#prueba').html(response);
            var cont = $('#contImage').val();   
           
      
         if(cont < 32 || cont == 'empty')
	    {   
	        console.log("si entroooooooooooooooo");
	        $('#fixed').removeClass("position-fixed");
	        $('#elCol').removeClass("col-5");
	        
	        $('#elCol').addClass('col-12');
	    }else{
	        
	     	$('#fixed').addClass("position-fixed");
	        $('#elCol').removeClass("col-12");
	        
	        $('#elCol').addClass('col-5');
	    }

    },'text');
  }

  if(categoria == 'default' && destino == 'default'){
    $.get('https://melorautopartes.com/dashboard/images', 'data=' + JSON.stringify({
      action: 'selectTodas',
      rand:Math.random(),

    }),(response) =>{
      $('#prueba').html(response);
            var cont = $('#contImage').val();   
           
      
         if(cont < 32 || cont == 'empty')
	    {   
	        console.log("si entroooooooooooooooo");
	        $('#fixed').removeClass("position-fixed");
	        $('#elCol').removeClass("col-5");
	        
	        $('#elCol').addClass('col-12');
	    }else{
	        
	     	$('#fixed').addClass("position-fixed");
	        $('#elCol').removeClass("col-12");
	        
	        $('#elCol').addClass('col-5');
	    }

    },'text');
  }
  
  
    if(destino == 'default' && categoria != 'default'){
    $.get('https://melorautopartes.com/dashboard/images', 'data=' + JSON.stringify({
      action: 'selectCategorias',
      rand:Math.random(),
      categoria,
      x,


    }),(response) =>{
      $('#prueba').html(response);
      
            var cont = $('#contImage').val();
      
         if(cont < 32 || cont == 'empty')
	    {   
	       
	        $('#fixed').removeClass("position-fixed");
	        $('#elCol').removeClass("col-5");
	        $('#elCol').addClass('col-12');
	    }else{
	        $('#fixed').addClass("position-fixed");
	        $('#elCol').removeClass("col-12");
	        
	        $('#elCol').addClass('col-5');
	    }

    },'text');
  }



})
  //-------------------------------------------------------------------------------------------------------------------

//-------------------------------------------------------------------------------------------------------------
$('#selectCategorias').change(() => {
  //$('#prueba2').css('visibility','hidden');

  var destino = $('#selectDestino').val();
  var categoria=$('#selectCategorias').val();

  


  if(destino != 'default' && categoria != 'default'){

    $.get('https://melorautopartes.com/dashboard/images', 'data=' + JSON.stringify({
      action: 'selectTwoCategoria',
      rand:Math.random(),
      categoria,
      destino,
      x,


    }),(response) =>{
      $('#prueba').html(response);
      
            var cont = $('#contImage').val();
            
            //console.log(cont);
      
         if(cont < 32 || cont == 'empty' )
	    {   
	        
	        $('#fixed').removeClass("position-fixed");
	        $('#elCol').removeClass("col-5");
	        
	        $('#elCol').addClass('col-12');
	    }else{
	        
	    	$('#fixed').addClass("position-fixed");
	        $('#elCol').removeClass("col-12");
	        
	        $('#elCol').addClass('col-5');
	    }

    },'text');

  }


  if(destino == 'default' && categoria != 'default'){
    $.get('https://melorautopartes.com/dashboard/images', 'data=' + JSON.stringify({
      action: 'selectCategorias',
      categoria,
      rand:Math.random(),
      x,


    }),(response) =>{
      $('#prueba').html(response);
      
            var cont = $('#contImage').val();
      
         if(cont < 32 || cont == 'empty')
	    {   
	       
	        $('#fixed').removeClass("position-fixed");
	        $('#elCol').removeClass("col-5");
	        $('#elCol').addClass('col-12');
	    }else{
	        $('#fixed').addClass("position-fixed");
	        $('#elCol').removeClass("col-12");
	        
	        $('#elCol').addClass('col-5');
	    }

    },'text');
  }
  
  
  
    if(categoria == 'default' && destino == 'default'){
    $.get('https://melorautopartes.com/dashboard/images', 'data=' + JSON.stringify({
      action: 'selectTodas',
      rand:Math.random(),

    }),(response) =>{
      $('#prueba').html(response);
            var cont = $('#contImage').val();   
           
      
         if(cont < 32 || cont == 'empty')
	    {   
	        console.log("si entroooooooooooooooo");
	        $('#fixed').removeClass("position-fixed");
	        $('#elCol').removeClass("col-5");
	        
	        $('#elCol').addClass('col-12');
	    }else{
	        
	     	$('#fixed').addClass("position-fixed");
	        $('#elCol').removeClass("col-12");
	        
	        $('#elCol').addClass('col-5');
	    }

    },'text');
  }
  
  
  
    if(categoria == 'default' && destino != 'default'){
    $.get('https://melorautopartes.com/dashboard/images', 'data=' + JSON.stringify({
      action: 'select',
      rand:Math.random(),
      destino,
      x,


    }),(response) =>{
        console.log(response);
      $('#prueba').html(response);
            var cont = $('#contImage').val();   
           
      
         if(cont < 32 || cont == 'empty')
	    {   
	        console.log("si entroooooooooooooooo");
	        $('#fixed').removeClass("position-fixed");
	        $('#elCol').removeClass("col-5");
	        
	        $('#elCol').addClass('col-12');
	    }else{
	        
	     	$('#fixed').addClass("position-fixed");
	        $('#elCol').removeClass("col-12");
	        
	        $('#elCol').addClass('col-5');
	    }

    },'text');
  }
  

})


//-----------------------------------------------------------------------------------------------------
}

var arrays = new Array();
var stringPrueba = "";
function subirTags(){
   var destinox = $('#selectDestinox').val();
   var tags = $('#tagsPage').val();
   var valueTag = $('#keywords-tour-imagenes').val();
   		  
   
  
   if(tags == 'Todas las generales'){
      $.get('https://melorautopartes.com/dashboard/images', 'data=' + JSON.stringify({
			action: 'generales',
			rand:Math.random(),
			destinox,
		}),(response) =>{
		 var arrayAux = JSON.parse(response);
		 var cont  = arrayAux.length;
		 console.log(cont);
		 var auxiliar = 0;
		 var x = 0 ;
		 console.log(valueTag);
		 
		  

		  while(auxiliar < cont){
		      if(valueTag != ''){
		     valueTag =  valueTag + "," + arrayAux[auxiliar];
		      }else if(x == 0 && valueTag == ''){
		          valueTag = arrayAux[auxiliar] + ',';
		      }else if(auxiliar < cont-1){
		         x=1;
		      }else{
		           valueTag = arrayAux[auxiliar];
		      }
		       auxiliar ++;
		  }
		 
		  
		  console.log(valueTag);
		  arrayPush(valueTag);
		    
		},'text');

       
   }else{
        if(valueTag != ""){
            valueTag = valueTag + "," + tags;
            arrayPush(valueTag)
        }else{
            arrayPush(tags)
        }
    
   }
   
//arrayPush();


   $('#tagsPage option:selected').remove();
}

function selectKeywords(destiny){
    $('.tags').val("");
    var destino = $('#selectDestinox').val();
   $.get('https://melorautopartes.com/dashboard/images', 'data=' + JSON.stringify({
			action: 'selectKeywords',
			rand:Math.random(),
			destino,
		}),(response) =>{
			$('#tagsPage').html(response);
			
			
		},'text');
		
}

function arrayPush(valueTag){
       console.log(valueTag);
   
   
       		$.get('https://melorautopartes.com/dashboard/images', 'data=' + JSON.stringify({
			action: 'arrays',
			rand:Math.random(),
			valueTag,
			
		}),(response) =>{
			$('#rowKey').html(response);
			
//$('#tagsx').tagsInput({
//  'height':'100px',
//  'width':'400px',
//});
		//	selectKeywords();

		},'text');
}



function selectKeywordsEdit(destiny){
    var destino = destiny;
   $.get('https://melorautopartes.com/dashboard/images', 'data=' + JSON.stringify({
			action: 'selectKeywordsEdit',
			rand:Math.random(),
			destino,
		}),(response) =>{
			$('#tagsPagex').html(response);
			
			var  cadena  = $('#keywords-tour-imagenes').val();
			var nombres = cadena.split(",");
			console.log(nombres);
			var cont  = nombres.length;
		    console.log(cont);
		    var auxiliar = 0;
			$("#tagsPagex option[value='Todas las generales']").remove();
		  while(auxiliar < cont){
            console.log("hola");
            $("#tagsPagex option[value='"+nombres[auxiliar]+"']").remove();
            auxiliar++;
		  }
			
			
		},'text');
		
}












function subirTagsEdit(){
   var destinox = $('#auxDestino').val();
   var tags = $('select[id=tagsPagex]').val();
   var valueTag = $('#keywords-tour-imagenes').val();
   
   console.log(destinox + " ---- " + tags + " ---- " + valueTag);
   
   
   if(tags == 'Todas las generales'){
      $.get('https://melorautopartes.com/dashboard/images', 'data=' + JSON.stringify({
			action: 'generales',
			rand:Math.random(),
			destinox,
		}),(response) =>{
		 var arrayAux = JSON.parse(response);
		 var cont  = arrayAux.length;
		 console.log(cont);
		 var auxiliar = 0;
		 var x = 0 ;
		 console.log(valueTag);
		  

		  while(auxiliar < cont){
		      if(valueTag != ''){
		     valueTag =  valueTag + "," + arrayAux[auxiliar];
		      }else if(x == 0 && valueTag == 'undefined'){
		          valueTag = arrayAux[auxiliar] + ',';
		      }else if(auxiliar < cont-1){
		         x=1;
		      }else{
		           valueTag = arrayAux[auxiliar];
		      }
		       auxiliar ++;
		  }
		 
		  
		  console.log(valueTag);
		  arrayPushEdit(valueTag);
		    
		},'text');

       
   }else{
        if(valueTag != ""){
            valueTag = valueTag + "," + tags;
            arrayPushEdit(valueTag)
        }else{
            arrayPushEdit(tags)
        }
    
   }    
   $('#tagsPagex option:selected').remove();
}

function keyCollapses(ids){
    let id = ids.name;
    
     if($('#collapseExample').hasClass('collapse show'))
	    {
	     	 //$('#fixed').addClass("position-fixed");
	        //$('#elCol').removeClass("col-12");
	        
	        //$('#elCol').addClass('col-5');
	    }else{
	         $('#MyModal').animate({ scrollTop: 230 }, 'slow');
	        $('#fixed').removeClass("position-fixed");  
	        $('#elCol').removeClass("col-5");
	        
	        $('#elCol').addClass('col-12');
	        
	       
	        
	        
	    $.get('https://melorautopartes.com/dashboard/images', 'data=' + JSON.stringify({
		   action: 'keyWordsMostrar',
		   rand:Math.random(),
			id,
		}),(response) =>{
		    $('#bodyKey').html(response)
		    $('[data-toggle="tooltip"]').tooltip(); 
		 var  destiny =  $('#auxDestino').val();
		    console.log(destiny);
		    selectKeywordsEdit(destiny);
		    
		    
		    //$('#tagsxx').tagsInput({'height':'400px','width':'300px',});
             
             //$('#tagsPagex').select2();

		},'text');
	        
	        
	        
	    }

}

function text(){
    var idHotel = $('select[id=idHotel]').val();
    
    $.get('https://www.gdltours.com/adminVentas/fichasTours/controller/conexion.php', 'data=' + JSON.stringify({
        action: 'selectImgsHabs',
        rand:Math.random(),
        idHotel,
    
    }), (response) => {
       $('#tipoHabitacion').html(response);
       console.log(response);
    
    }, 'script');
}

function arrayPushEdit(valueTag){
       console.log(valueTag);
   
   
       		$.get('https://melorautopartes.com/dashboard/images', 'data=' + JSON.stringify({
			action: 'arraysEdit',
			rand:Math.random(),
			valueTag,
			
		}),(response) =>{
			$('#rowKey').html(response);
			
//$('#tagsxx').tagsInput({
//  'height':'500px',
//  'width':'280px',
//});
		//	selectKeywords();

		},'text');
}

function obtenerParametros(){
	ancho=$('#ancho').val();
	alto=$('#alto').val();
	peso=$('#peso').val();
	textoNombre=$('#nombreDestino').val();
	logo=$( '#logoCheck' ).prop( 'checked');
	logoM=$( '#RenombrarCheck' ).prop( 'checked');

	
	
	
}

function obtenerParametros(){
	ancho=$('#ancho').val();
	alto=$('#alto').val();
	peso=$('#peso').val();
	textoNombre=$('#nombreDestino').val();
	logo=$( '#logoCheck' ).prop( 'checked');
	logoM=$( '#RenombrarCheck' ).prop( 'checked');

	
	
	
}

function mandarAjax(){
     alertFunc();
	var formData = new FormData();
	var files = $('#inputGroupFile01')[0].files[0];
	formData.append('file',files);
	$('#content').css('display','block');

$.ajax({
		url: 'https://melorautopartes.com/dashboard/indexImg',
		type: 'post',
		data:formData,
		contentType: false,
		processData: false,
		success:function(data) {
		    
		$('#content').css('display','none');



			data=JSON.parse(data);


			alertas(data);


		}
	});	



}

function vadidarInputs(){

    var an=$('#ancho').val();
    var al=$('#alto').val();
    var pes=$('#peso').val();
    var nombre= $('#nombreDestino').val();
    var descrip = $('#descripcionImg').val();
    var categ=$('#selectCategoria').val();
    var desti=$('#selectDestinox').val();
    var array=[];
    var arrayValor=[];
    

    if(an==0){
        an='ancho';
        array.push(an);
     
    }else{
    document.getElementById('ancho').style.border='solid #968f8f5e 1px';
    } 
    if(al==0){
        al='alto';
        array.push(al);
    }else{
    document.getElementById('alto').style.border='solid #968f8f5e 1px';
    } 
    
    
    
    if(pes==0){
        pes='peso';
       array.push(pes);
    }else{
    document.getElementById('peso').style.border='solid #968f8f5e 1px';
    } 
    
    
    
    if(nombre==''){
        nombre='nombreDestino';
       array.push(nombre);
    }else{
    document.getElementById('nombreDestino').style.border='solid #968f8f5e 1px';
    } 
    
    if(descrip==''){
         descrip='descripcionImg';
         array.push(descrip);
    }else{
    document.getElementById('descripcionImg').style.border='solid #968f8f5e 1px';
    } 
    
    if(categ=='seleccionar categoria'){
         categ='select2-selectCategoria-container';
        array.push(categ);
    }else{
    document.getElementById('select2-selectCategoria-container').style.border='solid #968f8f5e 1px';
    } 
    
    if(desti==''){
         desti='select2-selectDestinox-container';
      array.push(desti);
    }else{
    document.getElementById('select2-selectDestinox-container').style.border='solid #968f8f5e 1px';
    } 
    


  return array;
    
}




/**
 * Funciones para subir imagenes de manera independiente en cualquier parte
*/

function enviarParametrosImages(parametros){
    console.log(parametros);
    var params = JSON.parse(parametros);
    console.log(params.width);
    var obj=JSON.stringify({
        ancho:params.width,
        alto:params.height,
        textoNombre:params.nombreImagen,
        peso:params.weight,
        logo:false, //true or false, marca de agua
        reemplazar:false,
        x1E:0,
        y1E:0,
        x2:0,
        y2:0,
        action:params.action,
        action1:params.action2,
        des:params.keywords,
        destino1:"gfdgf",
        descripcion:params.descripcion,
        cambiarNombre:params.nombreReemplazo,
        tipo:null,
        relacionAspecto:params.relacionAspecto //true or false, Conservar relacion de aspecto
        
        
    });
    var respuesta = "";
    $.ajaxSetup({async:false});
        	$.ajax({
        		url:'https://melorautopartes.com/dashboard/indexImg',
        		type:'POST',
        		data:{obj},
        		success:function(data){
        		    
        			  var data=JSON.parse(data);
        			
                
        				if(data['bandera']=='true'){
        				   
        
        		    respuesta =  enviarFileImage(parametros); 
        		}else{
        		  
                 alertas(data);
                 var nombre=data['nombre'];
                 	$('#nombreDestino').val(nombre);
                 
        		}
        		}
        
        	});
        	$.ajaxSetup({async:true});
        	return respuesta;
}

function enviarFileImage(parametros){
     var params = JSON.parse(parametros);
	var formData = new FormData();
	var files = $('#'+params.identificadorFile)[0].files[0];
	console.log(files);
	formData.append('file',files);
	$('#content').css('display','block');
	var respuesta = "";
$.ajaxSetup({async:false});
$.ajax({
		url: 'https://melorautopartes.com/dashboard/indexImg',
		type: 'post',
		data:formData,
		contentType: false,
		processData: false,
		success:function(data) {
		    respuesta = data;
		$('#content').css('display','none');



			data=JSON.parse(data);


			alertas(data);


		}
	});	
	$.ajaxSetup({async:true});
	return respuesta;
}