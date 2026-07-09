/**
 * Helpers
*/

/**
 * Alerts
*/
//state: warning, info, success
function toogleAlert(state,text){
    $(".container-alert").removeClass('state-warning-alert');
    $(".container-alert").removeClass('state-success-alert');
    $(".container-alert").addClass('state-'+state+'-alert');
    $(".container-alert").html(text);
    $('.hidden-alerts').slideToggle("slow");
    setTimeout(function(){
        
        $('.hidden-alerts').slideToggle("slow");
        setTimeout(function(){
            
          
            $(".container-alert").removeClass('state-warning-alert');
            $(".container-alert").removeClass('state-success-alert');
        },2000)
        
    },3000)
}

/**
 * Modal
*/

function closeModalCompany(){
    $('.modal-company').css('display','none');
}

function openModalCompany(){
    $('.modal-company').css('display','block');
}

/**
 * convierte object Json de PHP a array object javascript
*/

function parseArrayObject(array){
    var arreglo = [];
    Object.keys(array).forEach(function (key) {
            
      
        arreglo.push([String(key),array[key]]);
       
    });
    return arreglo;
                
}