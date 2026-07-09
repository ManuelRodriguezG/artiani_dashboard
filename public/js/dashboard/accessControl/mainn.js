
/************************************Funciones de Control de Session************************************************************/
/********************************************Events**********************************************************/
var idleTime = 0;

$(document).ready(function () {
    $('#closeSession').click(function(){
       $.get('https://panoramex.mx/dashboard/users',`data=${JSON.stringify({
           action:'destroySession'
       })}`,function(response){
           console.log(response);
           if(response == '0'){
               window.location = 'https://panoramex.mx/dashboard/login';
           }
       }) 
    });
    //Timer inactividad
    var idleInterval = setInterval(timerIncrementInactividad, 60000); // 60 segundos

          
    
    
    //timer actividad
    //Zero the idle timer on mouse movement.
    $(this).click(function (e) {
        idleTime = 0;
        //console.log(idleTime);
    });
    
    $(function() {
     $(this).scroll(function() {
    
      idleTime = 0;
        //console.log(idleTime);
    
     });
    });
    //$(this).keypress(function (e) {
    //   
    //});
});
/********************************************Functions**********************************************************/
//Funcion que incrementa contador de inactividad de los usuarios Rigo sistem
function timerIncrementInactividad() {
    //console.log('//----------Inactividad--------------------//');
    idleTime = idleTime + 1;
    //console.log(idleTime);
    //if (idleTime > 59) { // 60 minutes
    if (idleTime == 'nunca terminar sesion') { // 60 minutes
    $.get('../controllers/users.php', `data=${JSON.stringify({
        action: 'sessionActive',
       estado: 'inactivo'
      }
    
    
    )}`,  function(response) {  
        response = JSON.parse(response);
        //console.log(response);
        //console.log('//----------Inactividad--------------------//');
        if(response.active == '0'){
            alert('La sesion ha terminado');
            window.location.reload();
        }
        
    });
        //window.location.reload();
    }
}


/************************************Funciones de Manifesto************************************************************/
/********************************************Functions**********************************************************/
//funcion de petición del manifesto
//parámetro: fecha solicitada en adminventas
 function showVentas(){
    var date = $('#dateManifesto').val();
    console.log(date);
     $.get('https://panoramex.mx/dashboard/accessControl', `data=${JSON.stringify({
            action: 'manifesto',
            date
          }
        
        
        )}`,  function(response) {  
            response = JSON.parse(response);
            if(response.respuesta=='true'){
                $('.rowTableVentas').html(response.results);
            }else{
                $('.rowTableVentas').html('No se encontraron resultados');
            }
        });
}

/********************************************Events**********************************************************/
// On click show modal access control
$("#manifesto").click(function(){
    $('#modalManifesto').modal('show');
    $('#modalManifesto').modal({backdrop: 'static', keyboard: false});

});
var optionsMan = {
            format: 'yyyy-mm-dd',
            orientation: 'top',
            //container,
            autoclose: true,
            startDate: new Date(),
        };
$('#dateManifesto').datepicker(optionsMan);

 
/************************************Funciones de control de accesos*****************************************************/
//controlAcceso();
//Esta funcion elimina las zonas a las que el usuario no tiene acceso
function controlAcceso(){
    var accesos ='';
    $.ajaxSetup({async:false});
    $.get('https://panoramex.mx/dashboard/accessControl', `data=${JSON.stringify({
            action: 'controlVistas',
          }
        
        
        )}`,  function(response) {
            //console.log(response);
            
            var response = JSON.parse(response);
            if(response['accesos'] == 'true'){
                console.log('si tiene accesos');
                //console.log(response['values'].length);
                if(response['values'] != 'totalAccess'){
                    var arrayValues = response['values'];
                    var longValues = response['values'].length;
                    for(var i=0; i<longValues; i++){
                        console.log(arrayValues[i]);
                        $('.acceso'+arrayValues[i]).remove();
                    }
                }
                
                //console.log(response['allAccess']);
                arrayAccesos = response['allAccess'];
                //addEvent 
                var arrayValuesAccess = response['YAccess'];
                var longValuesAccess = response['YAccess'].length;
                console.log(arrayValuesAccess);
                for(var j=0; j<longValuesAccess; j++){
                    //console.log(arrayValuesAccess[j]);
                    //$('.acceso'+arrayValuesAccess[j]).attr('onclick','registroAcceso("'+arrayValuesAccess[j]+'");');
                    $('.acceso'+arrayValuesAccess[j]).attr('onclick','registroAcceso(this);');
                    $('.acceso'+arrayValuesAccess[j]).attr('access',arrayValuesAccess[j]);
                    
                }
            }else{
                //arrayAccesos = ['none'];
                var arrayAllValues = response['allAccess'];
                var longAllValues = response['allAccess'].length;
                for(var k=0; k<longAllValues; k++){
                    console.log(arrayAllValues[k]);
                    $('.acceso'+arrayAllValues[k]).remove();
                }
                
                
            }
            
        });
        //console.log(accesos);
        
}
//Esta pagina registra la actividad de los usuarios, registrando la zona donde entro o intento entrar-----
function registroAcceso(element){
    var name = element.attributes.access.value;
    var value = element.text;
    console.log(name);
    console.log(element);
    $.get('https://panoramex.mx/dashboard/accessControl', `data=${JSON.stringify({
            action: 'registroAccess',
            id:name,
            value
          }
        
        
        )}`,  function(response) {
            
            
        });
    
    
}
//--------------------------------------------------------------------------------------------------------
//Esta funcion controla los selects de la seccion de configuracion ------
function selectControl(element){
    console.log('success');
    var office = $("select[id=office]").val();
    var department = $("select[id=department]").val();
    var integrantes = $("select[id=integrantes]").val();
    var action = '';
    var value = '';
    var value1 = '';
    var value2 = '';
    var value3 = '';
    var id='';
    //------------------------------------
    if(element.id=='office'){
        $("#btnSave").hide();
        $('.rowContForms').html('');
        if(office != 0){
            $("#cont-department").show();
            action='departments';
            
            id='Configuracion';
        }else{
            $("#cont-department").hide();
        }
    //------------------------------------        
    }else if(element.id=='department'){
        $("#btnSave").hide();
        $('.rowContForms').html('');
        if(department != 0){
            $("#cont-integrantes").show();
            action='integrantes';
            value1 = office;
             value2 = department;
            id='Configuracion';
        }else{
            $("#cont-integrantes").hide();
        }
    //------------------------------------
    }else if(element.id=='integrantes'){
        if(office != 0){
            $("#cont-department").show();
            action='actions';
            value1=office;
            value2=department;
            value3=integrantes;
            id='Configuracion';
        }else{
            $("#cont-department").hide();
        }
    //------------------------------------
    }else if(element.id=='configuracion'){
        $("#btnSave").hide();
        //validar si tiene permisos para mostrar este contenido
        action='configuracion';
        value1=0;
        value2=0;
        id='Configuracion';
        
    }
    //----------------------------------------------------------------------------------------------
    
    if(element.id== 'configuracion' || (element.id=='department' && value2 != 0) || (element.id=='integrantes' && value3 != 0) || (element.id=='office' && office != 0)){
        $.get('https://panoramex.mx/dashboard/accessControl', `data=${JSON.stringify({
            action: action,
            value:value1,
            value2,
            value3,
            id
          }
        
        
        )}`,  function(response) {
            
            response = JSON.parse(response);

            if(response.error){
                window.location = response.url;
            }else{
                if(element.id=='department'){
             
             
                    $('#integrantes').html(response.respuesta);
                    
                }
                if(element.id=='office'){
                 
                 
                    $('#department').html(response.respuesta);
                }
                if(element.id=='integrantes'){
                    console.log('integrantes');
                 
                 
                    $('.rowContForms').html(response.respuesta);
                    $("#btnSave").show();
                }
            }
            
        
            
        });
    }else{
        //Validaciones para ocultar contenido no deseado, oculta boton de guardar cambios 
        if(element.id == 'office' && office == 0){
            $("#cont-department").hide();
            
        }
        if(element.id == 'department' && value2 == 0){
            $("#cont-integrantes").hide();
        }
        if(element.id == 'office' && office == 0){
            $("#cont-integrantes").hide();
        }
        if(element.id == 'integrantes' && value3 == 0){
            $("#btnSave").hide();
        }
        $('.rowContForms').html('');
        
    }
}
//-----------------------------------------------------------------------
//Esta funcion crea un arreglo de los checks de accesos y hace una petici贸n para registrar los cambios
function save(){
    var checks = $('.checkAccess');
    var idUser = $('#integrantes').val();
    console.log(checks);
    var longChecks = checks.length;
    var arrayChecks = [];
    console.log(longChecks);
    for(var i=0;i<longChecks;i++){
        console.log(checks[i].value);
        console.log(checks[i].checked);
        if(checks[i].checked == true){
            arrayChecks.push(checks[i].value);
        }
    }
    //arrayChecks =  $.parseJSON(arrayChecks);
    checks = arrayChecks;
    console.log(arrayChecks);
    $.get('https://panoramex.mx/dashboard/accessControl', `data=${JSON.stringify({
            action: 'saveChanges',
            checks,
            idUser,
            id:'Configuracion'
          }
        
        
        )}`,  function(response) {
             //var response = JSON.parse(response);
            console.log(response);
            $('.contAlerts').html(response);
            setTimeout(deleteAlert, 3000);
            
        });
    console.log(idUser);
    console.log(arrayChecks);
}
//----------------------------------------------------------------------------------------------------
function deleteAlert(){
    $('#alert').remove();
}
//Esta funcion crea un nuevo acceso para una nueva secci贸n
function addAcceso(){
    
    var pagina = $('#newAccess').val();
    if(pagina !== ''){
        $.get('https://panoramex.mx/dashboard/accessControl', `data=${JSON.stringify({
            action: 'addAccess',
            pagina,
            id:'Configuracion'
          }
        
        
        )}`,  function(response) {
             //var response = JSON.parse(response);
            console.log(response);
            $('.contAlerts').html(response);
            
        });    
    }
     
}
//--------------------------------------------------------
//Esta funcion hace una peticion para mandar a traer el identificador de la pagina de acceso----------
function identificador(element){
    var idPagina = element.id;
    $.get('https://panoramex.mx/dashboard/accessControl', `data=${JSON.stringify({
            action: 'identificador',
            idPagina,
            id:'Configuracion'
          }
        
        
        )}`,  function(response) {
             //var response = JSON.parse(response);
            console.log(response);
            $('.contAlerts').html(response);
            
        });
}
//----------------------------------------------------------------------------------------------------
/************************************Funciones de Disponibilidad*********************************************************/
function showDisp(){
    var date = $('#dateDisp').val();
    console.log(date);
     $.get('https://panoramex.mx/dashboard/accessControl', `data=${JSON.stringify({
            action: 'disponibilidad',
            date
          }
        
        
        )}`,  function(response) {  
            response = JSON.parse(response);
            if(response['availability']=='true'){
                $('#listTours').html(response['list']);
            }else{
                $('#listTours').html('No se encuentra Disponibilidad');
            }
        });
}
function createSelect(){
    $.get('https://panoramex.mx/dashboard/accessControl',`data=${JSON.stringify({
        action:'createSelect'
    })}`,function(response){
        response = JSON.parse(response);
            $('#toursSelect').html(response['respuesta']);    
    });
}
function showDates(){
    var idTour = $('select[id=toursSelect]').val();
    $.get('https://panoramex.mx/dashboard/accessControl',`data=${JSON.stringify({
        action:'showDates',
        idTour
    })}`,function(response){
        response = JSON.parse(response);
        if(response['success'] == '1'){
            if(response['datesOpen'] == 'true'){
                $('.rowDatesMsg').html('');
                $('.rowDatesOpen').html('<ul class="dateOpen"></ul>');
                console.log(response['open'].length);
                var lengthOpen = response['open'].length;
                var fechasOpen = response['open'];
                for(var i=0; i<lengthOpen; i++){
                    $('.dateOpen').append('<li>'+fechasOpen[i]+'</li>');
                }
            }
            if(response['datesClose'] == 'true'){
                console.log(response['close'].length);
                $('.rowDatesMsg').html('');
                 $('.rowDatesClose').html('<ul class="dateClose"></ul>');
                var lengthClose = response['close'].length;
                var fechasClose = response['close'];
                for(var j=0; j<lengthClose; j++){
                    $('.dateClose').append('<li>'+fechasClose[j]+'</li>');
                }
            }
            if(response['datesOpen'] == 'true' || response['dateClose'] == 'true'){
                //Reemplazar Mensaje si estuviera visible
                $('.rowDatesMsg').html('');
            }
            if(response['datesClose'] == 'false' &&  response['datesOpen']){
                $('.rowDatesClose').html('');
                $('.rowDatesOpen').html('');
                $('.rowDatesMsg').html('No se encontraron fechas');
            }
        }
    })
}
// On click show modal access control
$("#disponibilidad").click(function(){
    $('#modalDisponibilidad').modal('show');
    $('#modalDisponibilidad').modal({backdrop: 'static', keyboard: false});

});
//Library Select2
    //$('#toursSelect').select2({
    //    //width: 'resolve',
    //    dropdownParent: $('#modalDisponibilidad')
//
    //});
   var options = {
            format: 'yyyy-mm-dd',
            orientation: 'top',
            //container,
            autoclose: true,
            startDate: new Date(),
        };
   $('#dateDisp').datepicker(options);
/********************************************Events**********************************************************/
// On click show modal access control
function controlAccesos(){
    
}
$("#ControlAccesos").click(function(){
    $.get('https://panoramex.mx/dashboard/accessControl',`data=${JSON.stringify({
        action:'controlAccesos',
    })}`,function(response){
        response = JSON.parse(response);
        if(response.error){
            window.location = response.url;
        }else{
            $('#content-dashboard').html(response.code);        
        }
        
    });
    //$('#modalControlAccess').modal('show');
    
    //$('#modalControlAccess').modal({backdrop: 'static', keyboard: false});

});


    //Library Select2
    //$('#office').select2({
    //    //width: 'resolve',
    //    dropdownParent: $('#modalControlAccess')
    //});
    //$('#department').select2({
    //    //width: 'resolve',
    //    dropdownParent: $('#modalControlAccess')
    //});
    //$('#integrantes').select2({
    //    //width: 'resolve',
    //    dropdownParent: $('#modalControlAccess')
    //});
   
/************************************Funciones de Keywords************************************************************/

function addKeyword(){
    var equals = 'false';
     var keyword=$('#inputKeyword').val();
    //obtener keywords existentes
    $.ajaxSetup({async:false});
     $.get('accessControl/controller/componentsAdminVentas.php', `data=${JSON.stringify({
        action: 'keywordsExist'
      }
    
    
    )}`,  function(response) { 
        
        
        if(response.success == 'true'){
           response.keywords.forEach(function (keywordDB) {
            console.log(keywordDB);
            if(keyword.toLowerCase() == keywordDB.toLowerCase()){
                console.log('son iguales');
                equals='true';
            }
            });
        }else{
           
        }
        
    },'json');
    $.ajaxSetup({async:true});
    
   
    var clasificacion='';
    var general = document.getElementById('general');
    console.log(general);
    var especifica = document.getElementById('especifica');
    console.log(general.checked);
    console.log(especifica.checked);
    var destino = $('#destination option:selected').text();
    var volumen = $('#volumen').val();
    var posicion = $('#posicion').val();
    var dificultad = $('#dificultad').val();
    var trafico = $('#trafico').val();
    if(general.checked == true){
        clasificacion = 'general';
    }
    if(especifica.checked == true){
        clasificacion = 'especifica';
    }
    console.log(clasificacion);
    console.log(equals+'equalsssssssssssssssssssssss');
    if(equals == 'false' ){
        if(keyword != '' ){
            $.get('accessControl/controller/componentsAdminVentas.php', `data=${JSON.stringify({
                action: 'addKeyword',
                keyword,
                destino,
                volumen,
                posicion,
                dificultad,
                trafico,
                clasificacion
                
              }
            
            
            )}`,  function(response) { 
                
                
                if(response.success == 'true'){
                     equals = 'false';
                    $('#msgStatusAdd').css('display','block');
                     $('.alertAddSuccess').css('display','block');
                     $('.alertAddSuccess').html('Registro agregado con exito');
                    setTimeout(function(){ $('.alertAddSuccess').css('display','none'); $('#msgStatusAdd').css('display','none'); }, 5000);
                    buildDestiny();
                    $('#inputKeyword').val('');
                    $('#volumen').val('');
                    $('#posicion').val('');
                    $('#dificultad').val('');
                    $('#trafico').val('');
                }else{
                    $('#inputKeyword').val('');
                    $('#volumen').val('');
                    $('#posicion').val('');
                    $('#dificultad').val('');
                    $('#trafico').val('');
                     equals = 'false';
                    $('#msgStatusAdd').css('display','block');
                     $('.alertAddDanger').css('display','block');
                     $('.alertAddDanger').html('Ha ocurrido un error al insertar el registro');
                    setTimeout(function(){ $('.alertAddDanger').css('display','none'); $('#msgStatusAdd').css('display','none'); }, 5000);
                }
                
            },'json');
        }else{
             equals = 'false';
        $('#msgStatusAdd').css('display','block');
        $('.alertAddDanger').css('display','block');
        $('.alertAddDanger').html('Campo vacio');
        setTimeout(function(){ $('.alertAddDanger').css('display','none'); $('#msgStatusAdd').css('display','none'); }, 3000);
        }
         
    }else{
        equals = 'false';
        $('#msgStatusAdd').css('display','block');
        $('.alertAddDanger').css('display','block');
        $('.alertAddDanger').html('La keyword ya existe');
        setTimeout(function(){ $('.alertAddDanger').css('display','none'); $('#msgStatusAdd').css('display','none'); }, 3000);
    }
   
}

function deleteKeyword(element){
    console.log(element.id);
    var identificador = element.id;
    $.get('accessControl/controller/componentsAdminVentas.php', `data=${JSON.stringify({
        action: 'deleteKeyword',
       identificador
        
      }
    
    
    )}`,  function(response) { 
        
        
        if(response.success == 'true'){
             $('#msgStatusAdd').css('display','block');
                 $('.alertAddSuccess').css('display','block');
                 $('.alertAddSuccess').html('Registro eliminado con exito');
                setTimeout(function(){ $('.alertAddSuccess').css('display','none'); $('#msgStatusAdd').css('display','none'); }, 5000);
            //Cambio de menu
            if($('#edithKeyword-tab').hasClass('active')){
                //Links menu
                $('#edithKeyword-tab').removeClass('active');
                $('#addKeyword-tab').addClass('active');
                //tabs-content
                if($('#edithKeyword').hasClass('active')){
                    $('#edithKeyword').removeClass('active');
                    $('#edithKeyword').removeClass('show');
                    $('#addKeyword').addClass('active');
                    $('#addKeyword').addClass('show');
                }    
            }
            buildDestiny();
        }else{
        $('#msgStatusAdd').css('display','block');
        $('.alertAddDanger').css('display','block');
        $('.alertAddDanger').html('Error al eliminar registro');
        setTimeout(function(){ $('.alertAddDanger').css('display','none'); $('#msgStatusAdd').css('display','none'); }, 3000);
        }
        
    },'json');
}

function buildDestiny(){
//$('#inputKeyword').val('Guadalajara');
//
//$('#volumen').val('142000');
//$('#posicion').val('8');
//$('#dificultad').val('28');
//$('#trafico').val('1725');
    $.get('accessControl/controller/componentsAdminVentas.php', `data=${JSON.stringify({
        action: 'destinationTours'
      }
    )}`,  function(response) { 
        console.log(response.value);
        
        if(response.success == 'true'){
            $('#destination').html(response.value);  
            $('#contTableKeywords').html(response.table);  
        }else{
            $('#destination').html('error componentsAdminVentas.php');    
        }
        
    },'json');
}
function edithKeyword(elemento){
    //obtención de valores a editar
    var identificador = elemento.id.substr(5);
    var keywordE=$('#keyword'+identificador).text();
    
    var clasificacionE = $('#clasificacion'+identificador).text();
    
    var destinoE = $('#destino'+identificador).text();
    var volumenE = $('#volumen'+identificador).text();
    var posicionE = $('#posicion'+identificador).text();
    var dificultadE = $('#dificultad'+identificador).text();
    var traficoE = $('#trafico'+identificador).text();
    if(clasificacionE == 'general'){
       var generalE = 'true';
    }else{
        generalE = 'false';
    }
    if(clasificacionE == 'especifica'){
        var especificaE = 'true';
    }else{
        especificaE = 'false';
    }
    //obtención de destinos
    $.get('accessControl/controller/componentsAdminVentas.php', `data=${JSON.stringify({
        action: 'destinationTours'
      }
    )}`,  function(response) { 
        console.log(response.value);
        
        if(response.success == 'true'){
            $('#destinationEdit').html(response.value);  
            
        }else{
            $('#destinationEdit').html('error componentsAdminVentas.php');    
        }
        
    },'json');
    console.log(identificador);
    console.log(keywordE);
    console.log(clasificacionE);
    console.log(destinoE);
    console.log(volumenE);
    console.log(posicionE);
    console.log(dificultadE);
    console.log(traficoE);
    console.log(generalE);
    console.log(especificaE);
    //asignación de valores a editar
    $('#inputKeywordEdit').val(keywordE);
    $('.btnUpdate').attr('id',identificador);
    if(generalE == 'true'){
        $('#generalEdit').attr('checked','true');
    }else{
        $('#especificaEdit').attr('checked','true');
    }
    $('#destinationEdit').val(destinoE);
    $('#volumenEdit').val(volumenE);
    $('#posicionEdit').val(posicionE);
    $('#dificultadEdit').val(dificultadE);
    $('#traficoEdit').val(traficoE);
    
    //Cambio de menu
    if($('#addKeyword-tab').hasClass('active')){
        //Links menu
        $('#addKeyword-tab').removeClass('active');
        $('#edithKeyword-tab').addClass('active');
        //tabs-content
        if($('#addKeyword').hasClass('active')){
            $('#addKeyword').removeClass('active');
            $('#addKeyword').removeClass('show');
            $('#edithKeyword').addClass('active');
            $('#edithKeyword').addClass('show');
        }    
    }
    
    console.log(elemento);
    console.log(elemento.id.substr(5));
}
function updateKeyword(){
    var keywordE=$('#inputKeywordEdit').val();
    var clasificacionE='';
    var generalE = document.getElementById('generalEdit');
    console.log(general);
    var especificaE = document.getElementById('especificaEdit');
    console.log(generalE.checked);
    console.log(especificaE.checked);
    var destinoE = $('#destinationEdit option:selected').text();
    var volumenE = $('#volumenEdit').val();
    var posicionE = $('#posicionEdit').val();
    var dificultadE = $('#dificultadEdit').val();
    var traficoE = $('#traficoEdit').val();
    if(generalE.checked == true){
        clasificacionE = 'general';
    }
    if(especificaE.checked == true){
        clasificacionE = 'especifica';
    }
    var elementoIdentificador = $('.btnUpdate');
    var identificador = elementoIdentificador[0].id;
    console.log(identificador);
    console.log(elementoIdentificador);
    $.get('accessControl/controller/componentsAdminVentas.php', `data=${JSON.stringify({
        action: 'updateKeyword',
        keywordE,
        destinoE,
        volumenE,
        posicionE,
        dificultadE,
        traficoE,
        clasificacionE,
        identificador
        
      }
    
    
    )}`,  function(response) { 
        
        console.log(response);
        if(response.success == 'true'){
            
            buildDestiny();
            //Cambio de menu
            if($('#edithKeyword-tab').hasClass('active')){
                //Links menu
                $('#edithKeyword-tab').removeClass('active');
                $('#addKeyword-tab').addClass('active');
                //tabs-content
                if($('#edithKeyword').hasClass('active')){
                    $('#edithKeyword').removeClass('active');
                    $('#edithKeyword').removeClass('show');
                    $('#addKeyword').addClass('active');
                    $('#addKeyword').addClass('show');
                }    
            }
            $('#msgStatusAdd').css('display','block');
            $('.alertAddSuccess').css('display','block');
            $('.alertAddSuccess').html('Actualizado con exito');
            
            setTimeout(function(){ $('.alertAddSuccess').css('display','none');  $('.msgStatusAdd').css('display','none');}, 5000);
        }else{
             $('#msgStatusAdd').css('display','block');
            $('.alertAddDanger').css('display','block');
            $('.alertAddDanger').html('Ha ocurrido un error al actualizar');
            
            setTimeout(function(){ $('.alertAddDanger').css('display','none');  $('.msgStatusAdd').css('display','none');}, 5000);
        }
        
    },'json');
}

/********************************************Events**********************************************************/
// On click show modal access control
$("#keywords").click(function(){
    buildDestiny();
    $('#modalKeywords').modal('show');

});

 //Library Select2
    //$('#destination').select2({
    //    //width: 'resolve',
    //    dropdownParent: $('#modalKeywords')
    //});
    //$('#destinationEdit').select2({
    //    //width: 'resolve',
    //    dropdownParent: $('#modalKeywords')
    //});