$(document).ready(function(){
    console.log('facturacion cargada js');
    
});
var dataFacturacion = [];
function setDataFacturacion(key,value){
    dataFacturacion[key]=value;
    console.log(dataFacturacion);
}
/**
 * Validar la referencia y el importe
*/
$('#valida-factura').click(function(){
    console.log('valida-factura');
    var referencia = $('#facturacion-referencia').val();
    var rfc = $('#facturacion-rfc').val();
    var importe = $('#facturacion-importe').val();
    var politicas = $('#politicas-privacity')[0].checked;
    
    if(referencia && rfc && importe && politicas == true){
        $('#facturacion-referencia').removeClass('border-danger');
        $('#facturacion-rfc').removeClass('border-danger');
        $('#facturacion-importe').removeClass('border-danger');
        $('#text-politicas').removeClass('text-danger');
        console.log('validado');
        $.get('https://panoramex.mx/Factura',`data=${JSON.stringify({
            action:'validacionFactura',
            referencia,
            rfc,
            importe,
            politicas
        })}`,function(response){
            console.log(response);
            response = JSON.parse(response);
            console.log(response);
            setDataFacturacion('encontrado',response.encontrado);
            setDataFacturacion('data',response.data);
            if(response.encontrado.compra == 'true'){
                if(response.proceso == 'true'){
                    $('#alert-compra').html('La factura se encuentra en proceso');
                    $('#alert-compra').css('display','block');
                    setTimeout(function(){
                        $('#alert-compra').css('display','none');    
                    },3000);
                }else{
                    
                
            
                    if(response.encontrado.rfc == 'false'){
                        
                        $('#facturacion_edit_data').remove();
                        $('#facturacion_cancelar').remove();
                        $('#facturacion_aceptar').remove();
                        $('#facturacion_email').val(dataFacturacion['data']['compra']['email']);
                        $('#facturacion_rfc').val(dataFacturacion['data']['rfc']['rfc']);
                        $('#facturacion_razon_social').val(dataFacturacion['data']['compra']['name']);
                    }else if(response.encontrado.rfc == 'true'){
                        $('.facturacion-data').each(function(){
                           console.log($(this)[0].id.substring(12))
                           var key = $(this)[0].id.substring(12);
                           $('#facturacion_'+key).addClass('facturacion-data-disabled');
                           $('#facturacion_'+key).attr('disabled','disabled');
                           $('#facturacion_'+key).val(dataFacturacion['data']['rfc'][key]);
                        });    
                    }
                    $('#factura-modal').modal('show');
                }
            }else if(response.encontrado.compra == 'false'){
                if(response.proceso == 'true'){
                    $('#alert-compra').html('La factura se encuentra en proceso');
                    $('#alert-compra').css('display','block');
                    setTimeout(function(){
                        $('#alert-compra').css('display','none');    
                    },3000);
                }else if(response.proceso == 'false'){
                    $('#alert-compra').html('No se encuentra la compra, verificar e intentar de nuevo');
                    $('#alert-compra').css('display','block');
                    setTimeout(function(){
                        $('#alert-compra').css('display','none');    
                    },3000);
                }
                
            }
            
        })
    }else{
        if(!referencia){
            $('#facturacion-referencia').addClass('border-danger');
        }else{
            $('#facturacion-referencia').removeClass('border-danger');
        }
        if(!rfc){
            $('#facturacion-rfc').addClass('border-danger');
        }else{
            $('#facturacion-rfc').removeClass('border-danger');
        }
        if(!importe){
            $('#facturacion-importe').addClass('border-danger');
        }else{
            $('#facturacion-importe').removeClass('border-danger');
        }
        if(politicas == false){
            $('#text-politicas').addClass('text-danger');
        }else{
            $('#text-politicas').removeClass('text-danger');
        }
    }
});

/**
 * Control de navegaci√≥n
*/
$('.control_nav').click(function(){
    
    var navegacion = {
        'nav-facturacion':{
            nextPanel:'nav-CFDI',
            prevPanel:'none'
        },
        'nav-CFDI':{
            nextPanel:'nav-review',
            prevPanel:'nav-facturacion'
        }
        
    };
    
    console.log($(this));
    console.log($(this).hasClass('next-view'));
    var idPanelActivo = $('.active-panel')[0].id;
    
    if($(this).hasClass('next-view') == true){
        
        var response = facturacionData(navegacion[idPanelActivo]['nextPanel']);
        if(response == 'true'){
            $('#'+idPanelActivo).removeClass('active show active-panel');
            console.log(navegacion[idPanelActivo]['nextPanel']);
            var idPanelSiguiente = navegacion[idPanelActivo]['nextPanel'];
            
            $('#'+idPanelSiguiente).addClass('active show active-panel');    
        }
        
    }else if($(this).hasClass('prev-view') == true){
        
        $('#'+idPanelActivo).removeClass('active show active-panel');
        console.log(navegacion[idPanelActivo]['prevPanel']);
        var idPanelSiguiente = navegacion[idPanelActivo]['prevPanel'];
        
        $('#'+idPanelSiguiente).addClass('active show active-panel');
    }
    console.log($(this)[0].id);
    console.log($('.active-panel')[0].id);
    
});

/**
 * Recibe un identificador y seg®≤n el identificador es la informaci®Æn que solicitara
*/
function facturacionData(id){
    console.log(id+'id');
    var lenData = $('.facturacion-data').length;
    var arrData = [];
    var counter = 0;
    $('.facturacion-data').each(function(){
        
        if($(this).val()){
            counter++;
            arrData[$(this)[0].id.substring(12)] = $(this).val();
            $(this).removeClass('border-danger');
        }else{
            $(this).addClass('border-danger');
        }
        
    })
    console.log(arrData);
    if(counter == lenData){
        console.log('si son iguales');
        if(id == 'nav-CFDI'){
            setDataFacturacion('dataFacturacion',arrData);
            $('.facturacion-data').each(function(){
                
                $('#span_'+$(this)[0].id.substring(12)).text($(this).val());
                
            })
           $('#body_table_facturacion').html(dataFacturacion['data']['compra']['tableBody']);
           return 'true';
       }else{
           return 'false';
       }
    }else{
        return 'false';
    }
   
}

/**
 * hace una solicitud para una factura
*/
$('#solicitar_factura').click(function(){
    
    console.log(JSON.stringify(dataFacturacion));
    $.get('https://panoramex.mx/Factura',`data=${JSON.stringify({
        action:'solicitarFactura',
        razon_social:dataFacturacion['dataFacturacion']['razon_social'],
        rfc:dataFacturacion['dataFacturacion']['rfc'],
        codigo_postal:dataFacturacion['dataFacturacion']['codigo_postal'],
        interior:dataFacturacion['dataFacturacion']['interior'],
        email:dataFacturacion['dataFacturacion']['email'],
        country:dataFacturacion['dataFacturacion']['country'],
        city:dataFacturacion['dataFacturacion']['city'],
        direccion:dataFacturacion['dataFacturacion']['direccion'],
        referencia: dataFacturacion['data']['compra']['referencia'],
        encontrado_rfc:dataFacturacion['encontrado']['rfc'],
    })}`,function(response){
        console.log(response);
        var navegacion = {
            'nav-facturacion':{
                nextPanel:'nav-CFDI',
                prevPanel:'none'
            },
            'nav-CFDI':{
                nextPanel:'nav-Success',
                prevPanel:'nav-facturacion'
            }
        
        };
        var idPanelActivo = $('.active-panel')[0].id;
        var idPanelSiguiente = navegacion[idPanelActivo]['nextPanel'];
        $('#'+idPanelActivo).removeClass('active show active-panel');
        $('#'+idPanelSiguiente).addClass('active show active-panel'); 
    });
})

/**
 * Habilita la seccion de los datos de facturacion 
*/
$('#facturacion_edit_data').click(function(){
    
    $('#facturacion_cancelar').css('display','inline-block');
    $('#facturacion_aceptar').css('display','inline-block');
    $('#facturacion_edit_data').css('display','none');
    $('#continue-arrow').css('display','none');
    $('.facturacion-data').each(function(){
       console.log($(this)[0].id.substring(12))
       var key = $(this)[0].id.substring(12);
       
       $('#facturacion_'+key).removeAttr('disabled');
       
    });  
});

/**
 * Modifica los datos de facturacion de un rfc ya existente
*/
$('#facturacion_aceptar').click(function(){
    var lenData = $('.facturacion-data').length;
    var counter = 0;
    var datosNuevos = [];
    var rfc = '';
    $('.facturacion-data').each(function(){
        
        if($(this).val()){
            counter++;
            if($(this)[0].id == 'facturacion_rfc'){
                rfc = $(this).val();
            }
            $(this).removeClass('border-danger');
            datosNuevos.push($(this).val());
        }else{
            $(this).addClass('border-danger');
        }
        
    })
    datosNuevos.push(rfc);
    console.log(counter);
    console.log(lenData);
    if(counter == lenData){
        $('#facturacion_cancelar').css('display','none');
        $('#facturacion_aceptar').css('display','none');
        $('#facturacion_edit_data').css('display','inline-block');
        $('#continue-arrow').css('display','inline-block');
        console.log(datosNuevos)
        $.get('https://panoramex.mx/Factura',`data=${JSON.stringify({
            action:'datosFacturacion',
            datosNuevos
        })}`,function(response){
            console.log(response);
        })
        $('.facturacion-data').each(function(){
           console.log($(this)[0].id.substring(12))
           var key = $(this)[0].id.substring(12);
           $('#facturacion_'+key).addClass('facturacion-data-disabled');
           $('#facturacion_'+key).attr('disabled','disabled');
           
        });
    }
});

/**
 * Reordena el front de los datos de facturacion bloqueandolos para no ser
 * modificados
*/
$('#facturacion_cancelar').click(function(){
    $('#facturacion_cancelar').css('display','none');
    $('#facturacion_aceptar').css('display','none');
    $('#facturacion_edit_data').css('display','inline-block');
    $('#continue-arrow').css('display','inline-block');
    $('.facturacion-data').each(function(){
       console.log($(this)[0].id.substring(12))
       var key = $(this)[0].id.substring(12);
       $('#facturacion_'+key).addClass('facturacion-data-disabled');
       $('#facturacion_'+key).attr('disabled','disabled');
       
    });   
});