$(document).ready(function(){
    $('#Facturacion').click(function(){
        
        $.get('https://panoramex.mx/Factura',`data=${JSON.stringify({
           action:'facturas' 
        })}`,function(response){
            response = JSON.parse(response);
            if(response.error == 'false'){
                $('#content-dashboard').html(response.data);     
                
                
            }else{
                if(response.error == 'true'){
                    if(response.action == 'redireccionar'){
                        //window.location = response.url;
                        $.get('https://panoramex.mx/dashboard/users',`data=${JSON.stringify({
                            rand:Math.random(),
                            action:'rec_session',
                            
                        })}`,function(response){
                            console.log(response);
                            $('#modal-content-body').html(response);
                            $('.modalP').css('display','block');
                        });
                    }
                }
            }
            
        });
    });
});




var entrou = false;
var imageLoader = document.getElementById('inputFile');
var detallesFactura = [];
$("#image-holder").on("click", function() {
  $("#inputFile").click();
});

$("#inputFile").on("change", function() {
  if (!entrou) {

      if (typeof FileReader != "undefined") {
     

        var reader = new FileReader();
        var file = $('#inputFile')[0].files[0];
        reader.readAsText(file);
        reader.onload = function(e){
            //alert(e.target.result);
            var xml = e.target.result.replace(/"/g, "'");
            xml = xml.replace(/&amp/g," ");
            console.log(JSON.stringify({
                action:'archivoFactura',
                data:xml,
                detallesFactura,
            }));
        
            $.post('https://panoramex.mx/Factura',`data=${JSON.stringify({
                action:'archivoFactura',
                data:xml,
                detallesFactura
            })}`,function(response){
                console.log(response);
                response = JSON.parse(response);
                if(response.error == 'false'){
                    $('#mensaje_upload_xml').addClass('text-success');
                    $('#mensaje_upload_xml').html('Se subió el archivo con éxito');
                    $('#cont_area_upload').css('display','none');
                    
                }else{
                    $('#mensaje_upload_xml').addClass('text-danger');
                    $('#mensaje_upload_xml').html('Ocurrió un error, comunicarse con sistemas');
                    
                }
                $('#mensaje_upload_xml').css('display','block');
                setTimeout(function(){
                    $('#content_buttons_xml').css('display','block');
                    $('#mensaje_upload_xml').css('display','none');
                },3000);
            })
        }
      
      }
 
  }
  entrou = true;
  setTimeout(function() {
    entrou = false;
  }, 1000);
});

var dropbox;
dropbox = document.getElementById("image-holder");
dropbox.addEventListener("dragenter", dragenter, false);
dropbox.addEventListener("dragover", dragover, false);
dropbox.addEventListener("drop", drop, false);
dropbox.addEventListener("dragleave", dropleave, false);

function dragenter(e) {
  e.stopPropagation();
  e.preventDefault();
}

function dropleave(e){
    $(this).css('border','2px dashed rgba(0,0,0,0.3)');
    e.stopPropagation();
  e.preventDefault();
}

function dragover(e) {
    console.log($(this));
  $(this).css('border','2px dashed rgba(0, 0, 0, 0.78)');
  e.stopPropagation();
  e.preventDefault();
  
}

function drop(e) {
  e.stopPropagation();
  e.preventDefault();
  imageLoader.files = e.dataTransfer.files;
  
  

  $("#inputFile").change();
}

function showDetalleCompra(element){
    console.log(element.id.split("_"));
    var arrElements = element.id.split("_");
    var idCompraFactura = arrElements[2];
    var id = arrElements[4];
    var rfc = arrElements[3];
    detallesFactura = [];
    detallesFactura.push(id);
    detallesFactura.push(rfc);
    detallesFactura.push(idCompraFactura);
    $.get('https://panoramex.mx/Factura',`data=${JSON.stringify({
        action:'detallesFactura',
        id,
        idCompraFactura,
        rfc
    })}`,function(response){
        response = JSON.parse(response);
        if(response.error == 'false'){
            if(response.xml == 'true'){
                $('#content_buttons_xml').css('display','block');
                $('#cont_area_upload').css('display','none');
                
            }
            $('#contenedor_data').html(response.detallesFactura);
            $('#body_table_facturacion').html(response.rows);
        }else if(response.error == 'true'){
            
        }
    })
    $('#modal_detalles_compra').modal('show');
}

$('#renovar_xml').click(function(){
    console.log('renovar');
    $('#cont_area_upload').css('display','block');
});
$('#enviar_factura').click(function(){
    console.log('enviar Factura');
    //$('#cont_area_upload').css('display','block');
    $.get('https://panoramex.mx/Factura',`data=${JSON.stringify({
        action:'enviar_factura',
        detallesFactura
    })}`,function(response){
        console.log(response);
        response = JSON.parse(response);
        if(response.error == 'true'){
                        
                            $('.alerts').css('background','red');
                            $('.alerts').css('color','white');
                            $('.alerts').html('Ocurrio un error en el proceso, comunicarse con sistemas.');
                            $('.alerts').css('display','block');
                            setTimeout(function(){ 
                                
                               $('.alerts').css('display','none');
                               $('.alerts').html('');
                            }, 3000);
                        
                        
                    }else if(response.error == 'false'){
                        $('.alerts').css('background','green');
                        $('.alerts').css('color','white');
                        $('.alerts').html('Proceso realizado exitosamente');
                        $('.alerts').css('display','block');
                        setTimeout(function(){ 
                            
                           $('.alerts').css('display','none');
                           $('.alerts').html('');
                        }, 3000);
                    }
    })
});

$('#ver_factura').click(function(){
    console.log('enviar Factura');
    //$('#cont_area_upload').css('display','block');
    $.get('https://panoramex.mx/Factura',`data=${JSON.stringify({
        action:'ver_factura',
        detallesFactura
    })}`,function(response){
        console.log(response);
        response = JSON.parse(response);
        window.open(response.url);
    })
});