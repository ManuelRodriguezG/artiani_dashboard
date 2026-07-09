$(document).ready( function () {
    var languages = '';
    //Construye menu dashboard (otra opcion mas tardado)
    //$.get('https://panoramex.mx/dashboard/accessControl',`data=${JSON.stringify({
    //    action:'menuDashboard'
    //})}`,function(response){
    //    console.log(response);
    //    //$('.items-navbar-menu-panoramex').html(response);
    //});
    $.get('https://panoramex.mx/dashboard/users',`data=${JSON.stringify({
        rand:Math.random(),
        action:'activeSession',
        
    })}`,function(response){
        console.log(response);
        if(response == '0'){
            window.location = 'https://panoramex.mx/dashboard/login';
        }
        
    });
    
    
    $('#Tours').click(function(){
        
        $.get('https://panoramex.mx/dashboard/createUpdateTour',`data=${JSON.stringify({
           action:'codeTour' 
        })}`,function(response){
            response = JSON.parse(response);
            if(response.error == 'false'){
                $('#content-dashboard').html(response.code);     
                
                
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


function removerVehiculo(element){
    //console.log(element.id.substring(7));
    $('#'+element.id.substring(7)).remove();
}
//terminar guardar privados, asi como mandar error si hace falta informacion
function privado(){
    var idErrores = [];
    var errores = [];
    var values = [];
    var keys = [];
    $('.vehiculos-privado').each(function(){
        console.log($(this)[0].id);
        if($('#cantidad-tour-'+$(this)[0].id).val() == 0){
            errores.push('#cantidad-tour-'+$(this)[0].id+' vacio');
            $('#cantidad-tour-'+$(this)[0].id).addClass('input-warning');
        }else{
            $('#cantidad-tour-'+$(this)[0].id).removeClass('input-warning');
        }
        if($('#minimo-privado-tour-'+$(this)[0].id).val() == 0){
            errores.push('#minimo-privado-tour-'+$(this)[0].id+' vacio');
            $('#minimo-privado-tour-'+$(this)[0].id).addClass('input-warning');
        }else{
            $('#minimo-privado-tour-'+$(this)[0].id).removeClass('input-warning');
        }
        if($('#capacidad-tour-'+$(this)[0].id).val() == 0){
            errores.push('#capacidad-tour-'+$(this)[0].id+' vacio');
            $('#capacidad-tour-'+$(this)[0].id).addClass('input-warning');
        }else{
            $('#capacidad-tour-'+$(this)[0].id).removeClass('input-warning');
        }
        if(errores.length > 0){
            
            idErrores.push($(this)[0].id);
            errores = [];
        }else{
            keys.push($(this)[0].id.substring(9,10));
            values.push({
                cantidad:$('#cantidad-tour-'+$(this)[0].id).val(),
                minimo:$('#minimo-privado-tour-'+$(this)[0].id).val(),
                capacidad:$('#capacidad-tour-'+$(this)[0].id).val()
            });
        }
    });
    console.log(idErrores);
    console.log(errores);
    console.log(values);
    
    if(idErrores.length > 0){
        $('.alerts').css('background','red');
        $('.alerts').css('color','white');
        $('.alerts').html('Datos incompletos');
        $('.alerts').css('display','block');
        setTimeout(function(){ 
            
           $('.alerts').css('display','none');
           $('.alerts').html('');
        }, 3000);   
        
    }else{
        var privado = $('#switch-privado')[0].checked;
        var idTour = $('#select-tour-edit').val();
        
            $.get('https://panoramex.mx/dashboard/createUpdateTour',`data=${JSON.stringify({
               action:'guardaPrivado' ,
               privado,
               values,
               keys,
               idTour
               
            })}`,function(response){
                response = JSON.parse(response);
                if(response.error == 'true'){
                    if(response.accion == 'redireccionar'){
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
                }else if(response.error == 'false'){
                    if(response.accion == 'mensaje'){
                        $('.alerts').css('background','green');
                        $('.alerts').css('color','white');
                        $('.alerts').html(response.msg);
                        $('.alerts').css('display','block');
                        setTimeout(function(){ 
                            
                           $('.alerts').css('display','none');
                           $('.alerts').html('');
                        }, 3000);   
                    }
                }
            });
        
    }
    
}

var contVehiculos = 0;
function agregarVehiculo(){
    console.log($('select[id=select-vehiculos]').val());
    var idVehiculo = $('select[id=select-vehiculos]').val();
    var urlVehiculo = $('#select-vehiculos > option[value="'+idVehiculo+'"]')[0].attributes['url-imagen'].value;
    console.log($('#select-vehiculos > option[value="'+idVehiculo+'"]')[0].attributes['url-imagen']);
    
    var code = '<div class="row row-dashboard vehiculos-privado" style="width:100%;padding:10px;background:white;margin:10px !important;box-shadow:0px 0px 7px -1px rgba(0,0,0,0.3);" id="vehiculo-'+idVehiculo+'-'+contVehiculos+'">'+
                                                '<div class="row row-dashboard" style="width:100%;justify-content:center;">'+
                                                    '<div class="form-group " style="padding:5px;position:relative;margin:0 !important;">'+
                                                        '<img src="'+urlVehiculo+'" width="120">'+
                                                    '</div>'+
                                                    '<div class="form-group " style="padding:5px;position:relative;margin:0 !important;">'+
                                                    '<!-- cantidad de vehiculos -->'+
                                                        '<label style="font-weight:bold;color:gray;" for="minimo-privado-tour" id="label-cantidad-tour-vehiculo-'+idVehiculo+'-'+contVehiculos+'">'+
                                                            'Cantidad de Vehiculos'+
                                                        '</label>'+
                                                        '<input type="number" class="form-control form-control-sm" id="cantidad-tour-vehiculo-'+idVehiculo+'-'+contVehiculos+'"  value="0">'+
                                                    '</div>'+
                                                    '<div class="form-group " style="padding:5px;position:relative;margin:0 !important;">'+
                                                    '<!-- Minimo privado -->'+
                                                        '<label style="font-weight:bold;color:gray;" for="minimo-privado-tour-" id="label-minimo-privado-tour-vehiculo-'+idVehiculo+'-'+contVehiculos+'">'+
                                                            'Minimo Privado'+
                                                            '<div class="help-tip" style="z-index:1000; ">'+
                            	                           	    '<p style="z-index:1000;">Cantidad minima de personas para poder realizar el tour de manera privada.</p>'+
                            	                       	    '</div>'+
                                                        '</label>'+
                                                        '<input type="number" class="form-control form-control-sm" id="minimo-privado-tour-vehiculo-'+idVehiculo+'-'+contVehiculos+'"  value="0">'+
                                                    '</div>'+
                                                    '<div class="form-group " style="padding:5px;position:relative;margin:0 !important;">'+
                                                    '<!-- Capacidad -->'+
                                                        '<label style="font-weight:bold;color:gray;" for="minimo-privado-tour" id="label-capacidad-tour-vehiculo-'+idVehiculo+'-'+contVehiculos+'">'+
                                                            'Capacidad'+
                                                            '<div class="help-tip" style="z-index:1000; ">'+
                            	                           	    '<p style="z-index:1000;">Cupo de personas para este vehiculo</p>'+
                            	                       	    '</div>'+
                                                        '</label>'+
                                                        '<input type="number" class="form-control form-control-sm" id="capacidad-tour-vehiculo-'+idVehiculo+'-'+contVehiculos+'" placeholder="" value="0">'+
                                                    '</div>'+
                                                '</div>'+
                                                '<div class="" style="text-align: right;width:100%;">'+
                                                    '<a class="" style="padding: 0;cursor:pointer;" id="remove-vehiculo-'+idVehiculo+'-'+contVehiculos+'" onclick="removerVehiculo(this);">'+
                                                        '<label style="text-decoration: underline;color: #0073aa;margin: 0;cursor:pointer;" class="text-danger">'+
                                                        'Remove'+
                                                        '</label>'+
                                                    '</a>'+
                                                '</div>'+
                                            '</div>';
                                            contVehiculos++;
    $('#cont-vehiculos').append(code);
}

function busquedaListaTours(){
    console.log($(".tours-list").data("tours"));
    var toursString = $(".tours-list").data("tours");
    var toursList = toursString.split(",");
    console.log(toursList);
    var textoBusqueda = $("#search-tours-dd").val();
    var cont = 0;
    console.log($("#search-tours-dd").val()+"input");
    var groupTours = [];
    $(".group-list .drag-item").each(function(){ 
    console.log($(this).text()) 
    groupTours.push($(this).text());
        
    });
    for(var i = 0; i<toursList.length;i++){
        if($("#search-tours-dd").val()){
            if(toursList[i].toLowerCase().indexOf(textoBusqueda.toLowerCase()) != -1){
                
                console.log(cont);
                var elementsTour = toursList[i].split("-");
                console.log(elementsTour);
                console.log(groupTours.includes(elementsTour[0]) == false);
                if(cont == 0){
                
                    if(groupTours.includes(elementsTour[0]) == false){
                    cont++;
                    console.log("si entra1")
                        $(".tours-list").html('<li class="drag-item" id="group-'+elementsTour[1]+'">'+elementsTour[0]+"</li>");    
                    }
                    
                }else{
                    if(groupTours.includes(elementsTour[0]) == false){
                    console.log("si entra2")
                        $(".tours-list").append('<li class="drag-item" id="group-'+elementsTour[1]+'">'+elementsTour[0]+"</li>");    
                    }
                    
                }
            }
        }else{
            
                console.log(cont);
                var elementsTour = toursList[i].split("-");
                console.log(elementsTour);
                console.log(groupTours.includes(elementsTour[0]) == false);
                if(cont == 0){
                    if(groupTours.includes(elementsTour[0]) == false){
                    cont++;
                    console.log("si entra3")
                        $(".tours-list").html('<li class="drag-item" id="'+elementsTour[1]+'">'+elementsTour[0]+"</li>");    
                    }
                }else{
                    if(groupTours.includes(elementsTour[0]) == false){
                    console.log("si entra4")
                        $(".tours-list").append('<li class="drag-item" id="'+elementsTour[1]+'">'+elementsTour[0]+"</li>");
                    }
                }
        }
    }
    console.log(String.fromCharCode(event.keyCode));
    console.log($("#search-tours-dd").val()+String.fromCharCode(event.keyCode));
}
                            
function colLines(element){
    
        
            if(element.id == "single-page"){
                //$('#section-multiple').css('display','none');
                $('#section-multiple').addClass('hide');
                $('#section-multiple').removeClass('show');
                $("#"+element.id).addClass("select");
                $("#"+element.id).addClass("page-select");
                $("#multiple-page").removeClass("page-select");
                if($("#multiple-page").hasClass("select")){
                    $("#multiple-page").removeClass("select");
                }
            }else if(element.id == "multiple-page"){
                
                $('#section-multiple').addClass('show');
                $('#section-multiple').removeClass('hide');
                $("#"+element.id).addClass("select");
                $("#"+element.id).addClass("page-select");
                $("#single-page").removeClass("page-select");
                if($("#single-page").hasClass("select")){
                    $("#single-page").removeClass("select");
                }
            }
        
}

function showLanguage(){
    console.log($('.contenedor-select-language-content').hasClass('hide-content'));
    if($('.contenedor-select-language-content').hasClass('hide-content')){
         $('.contenedor-select-language-content').removeClass('hide-content');
    }else{
        sectionsEditTour();
    }
}
    
function sectionsEditTour(element){
    var tour = $('select[id=select-tour-edit]').val();
    var lang = $('select[id=select-language-content]').val();
    console.log(languages);
    if(tour != 0){
        if(languages.includes(lang)){
            console.log('si se encuentra');
        
            console.log('codeAddTour');
            $.get('https://panoramex.mx/dashboard/createUpdateTour',`data=${JSON.stringify({
                   action:'sectionsEditTour',
                   lang,
                   tour
            })}`,function(response){
                $('.cont-sections-edit-tours').html(response);
                if($('#cont-vehiculos')[0].attributes['cont'].value > 0){
                   contVehiculos = parseInt($('#cont-vehiculos')[0].attributes['cont'].value) +1;
                }
                
            });
        }else{
            $('.alerts').css('background','red');
            $('.alerts').css('color','white');
            $('.alerts').html('El lenguage seleccionado no es válido');
            $('.alerts').css('display','block');
            setTimeout(function(){ 
                
               $('.alerts').css('display','none');
               $('.alerts').html('');
            }, 3000);    
        }
    }else{
        $('.alerts').css('background','red');
        $('.alerts').css('color','white');
        $('.alerts').html('Selecciona un tour');
        $('.alerts').css('display','block');
        setTimeout(function(){ 
            
           $('.alerts').css('display','none');
           $('.alerts').html('');
        }, 3000);
    }
}
    
function codeAction(element){
    
    var action = '';
    if(element.id == 'edit-tour'){
        action = 'codeEditTour';
    }else if(element.id == 'add-tour'){
        action = 'codeAddTour';
    }
    console.log('codeAddTour');
    $.get('https://panoramex.mx/dashboard/createUpdateTour',`data=${JSON.stringify({
           action
        })}`,function(response){
            response = JSON.parse(response);
            if(action == 'codeEditTour'){
                
            
                if(response.error == 'false'){
                    $('.row-content-tour').html(response.code);  
                    languages = response.lang;
                    
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
            }else if(action == 'codeAddTour'){
                if(response.error == 'false'){
                    $('.row-content-tour').html(response.code);  
                    
                    
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
            }
            $("#select-tour-edit").select2();
            $("#select-language-content").select2();
        });
        
}

function showMin(element){
    console.log(element.checked);
    if(element.checked == true){
        $('#minimo-privado-tour').css('display','block');
        $('#label-minimo-privado-tour').css('display','block');
    }else{
        $('#minimo-privado-tour').css('display','none');
        $('#label-minimo-privado-tour').css('display','none');
    }
}

function changeRel(element){
    console.log(element.value);
    $('#value-relevancia-tour').text(element.value);
}

function createTour(element){
    console.log($('#add-form-tour'));
    console.log($('#add-form-tour').find('input[id="name-tour"]').val());
    console.log($('#add-form-tour').find('input[id="antelacion-tour"]').val());
    console.log($('#add-form-tour').find('input[id="localidad-tour"]').val());
    console.log($('#add-form-tour').find('input[id="private-tour"]')[0].checked);
    console.log($('#add-form-tour').find('input[id="estado-tour"]').val());
    console.log($('#add-form-tour').find('input[id="minimo-privado-tour"]').val());
    console.log($('#add-form-tour').find('input[id="pais-tour"]').val());
    console.log($('#add-form-tour').find('select[id="zona-tour"]').val());
    console.log($('#add-form-tour').find('input[id="relevancia-tour"]').val());
    //var variable = $('#add-form-tour');
    var nombreTour = $('#add-form-tour').find('input[id="name-tour"]').val();
    var antelacionTour = $('#add-form-tour').find('input[id="antelacion-tour"]').val();
    var localidadTour = $('#add-form-tour').find('input[id="localidad-tour"]').val();
    var privateTour = $('#add-form-tour').find('input[id="private-tour"]')[0].checked;
    var estadoTour = $('#add-form-tour').find('input[id="estado-tour"]').val();
    var minPrivadoTour = $('#add-form-tour').find('input[id="minimo-privado-tour"]').val();
    var minPeople = $('#add-form-tour').find('input[id="minimo-people-tour"]').val();
    var maxPeople = $('#add-form-tour').find('input[id="maximo-people-tour"]').val();
    var paisTour = $('#add-form-tour').find('input[id="pais-tour"]').val();
    var zonaTour = $('#add-form-tour').find('input[id="zona-tour"]').val();
    var relevanciaTour = $('#add-form-tour').find('input[id="relevancia-tour"]').val();
    var send = 'false';
    if(nombreTour && antelacionTour && localidadTour && estadoTour && paisTour && zonaTour && minPeople){
        if(privateTour){
            if(minPrivadoTour){
                //get
                console.log('GET');
                send = 'true';
                
            }else{
                minPrivadoTour ? '1' : animate('#minimo-privado-tour');    
            }
        }else{
            //get
            console.log('GET');
            send = 'true';
        }
        
        if(send == 'true'){
            $.get('https://panoramex.mx/dashboard/createUpdateTour',`data=${JSON.stringify({
                action:'create',
                nombreTour,
                antelacionTour,
                localidadTour,
                privateTour,
                estadoTour,
                minPrivadoTour,
                paisTour,
                zonaTour,
                minPeople,
                maxPeople,
                relevanciaTour
            })}`,function(response){
                response = JSON.parse(response);
                //$respuesta = array('estado'=>$resp['estado'],'action'=>'alert','msg'=>$resp['descripcion']);
                //$respuesta = array('estado'=>$resp['estado'],'action'=>'alert','msg'=>'Ya se encuentra un tour con el mismo nombre');
                //$respuesta = array('estado'=>'danger','action'=>'redireccion','url'=>'https://panoramex.mx/dashboard/login');
                if(response.estado == 'danger'){
                    if(response.action == 'redireccion'){
                        //window.location = response.url;
                        $.get('https://panoramex.mx/dashboard/users',`data=${JSON.stringify({
                            rand:Math.random(),
                            action:'rec_session',
                            
                        })}`,function(response){
                            console.log(response);
                            $('#modal-content-body').html(response);
                            $('.modalP').css('display','block');
                        });
                    }else{
                        $('.alerts').css('background','red');
                        $('.alerts').css('color','white');
                        $('.alerts').html(response.msg);
                        $('.alerts').css('display','block');
                        setTimeout(function(){ 
                            
                           $('.alerts').css('display','none');
                           $('.alerts').html('');
                        }, 3000);
                    }
                    
                }else if(response.estado == 'success'){
                    $('.alerts').css('background','green');
                    $('.alerts').css('color','white');
                    $('.alerts').html(response.msg);
                    $('.alerts').css('display','block');
                    setTimeout(function(){ 
                        
                       $('.alerts').css('display','none');
                       $('.alerts').html('');
                    }, 3000);
                }
                
            });
        }
        
    }else{
        if(nombreTour){
            
        }else{
            animate('#name-tour');
        }
        nombreTour ? '1' : 
        antelacionTour ? '1' : animate('#antelacion-tour');
        localidadTour ? '1' : animate('#localidad-tour');
        estadoTour ? '1' : animate('#estado-tour');
        paisTour ? '1' : animate('#pais-tour');
        zonaTour ? '1' : animate('#zona-tour');
        
        
    }
    
}

function animate(id){
    $(id).addClass('animated shake').one('animationend oAnimationEnd mozAnimationEnd webkitAnimationEnd', function() {
        $(this).removeClass('animated shake');
    });
}
//------------------------ funciones de los Tags -------------------------------------
function insertTags(){
    var itemTags = $('#cont-space-tags .item-tag');
    var tags = 'null';
    var length = itemTags.length -1;
    var cont = 0;
    $('#cont-space-tags .item-tag').each(function(){
        console.log($(this).text());
        if(tags == 'null'){
            
            if(length == cont){
                tags = $(this).text();    
            }else{
                tags = $(this).text()+',';    
            }
        }else{
            if(length == cont){
                tags += $(this).text();    
            }else{
                tags += $(this).text()+',';
            }
            
        }
        cont++;
        //console.log($(this).val);
    })
    console.log(tags);
    
   
    console.log(tags);
    var inputValues = document.getElementById('input-tag-value');
    console.log(inputValues);
    inputValues.value  = '';
    
    var inputKeywords = document.getElementById('keywords-tour');
    inputKeywords.value = tags;
}

function existTag(tag){
    var itemTags = document.getElementsByClassName('item-tag');
    var length = itemTags.length -1;
    var retorno = true;
    for(var i = 0; i < itemTags.length; i++){
        console.log(itemTags[i].textContent);
        if(itemTags[i].textContent == tag){
            retorno = false;
        }
        
    }
    return retorno;
}

function createTag(event){
     var x = event.which || event.keyCode;
        if(x == 13){
            var val = $('#input-tag-value').val();
            console.log(existTag(val));
            if(existTag(val)){
                
            
                if(val){
                    $('<span class="item-tag">'+val+'<i class="fas fa-times delete-tag-item" onclick="deleteTag(this);" style="padding-left: 5px;cursor: pointer;"></i></span>').insertBefore($('.input-create-tags'));
                    insertTags();    
                    $('#input-tag-value').css('background','none');
                }else{
                    $('#input-tag-value').css('background','#ffdcdc');
                }
            }else{
                $('#input-tag-value').css('background','#ffdcdc');
            }
            
            
        }
}

function deleteTag(element){
    //console.log(element[0]);
    //console.log(element.parentElement.remove());
    element.parentElement.remove();
    insertTags();
    
}

function focusInput(){
    var space = document.getElementById('input-tag-value');
    console.log(space);
    space.focus();
}
//-----------------------------------------------------------------------
function insertTagsImages(){
    var itemTags = document.getElementsByClassName('item-tag-imagenes');
    var tags = 'null';
    var length = itemTags.length -1;
    for(var i = 0; i < itemTags.length; i++){
        console.log(itemTags[i].textContent);
        if(tags == 'null'){
            
            if(length == i){
                tags = itemTags[i].textContent;    
            }else{
                tags = itemTags[i].textContent+',';    
            }
        }else{
            if(length == i){
                tags += itemTags[i].textContent;    
            }else{
                tags += itemTags[i].textContent+',';
            }
            
        }
        
    }
    console.log(tags);
    var inputValues = document.getElementById('input-tag-value-imagenes');
    console.log(inputValues);
    inputValues.value  = '';
    
    var inputKeywords = document.getElementById('keywords-tour-imagenes');
    inputKeywords.value = tags;
}
//-----------------------------------------------------------------------
function createTagImages(event){
     var x = event.which || event.keyCode;
        if(x == 13){
            var val = $('#input-tag-value-imagenes').val();
            console.log(existTag(val));
            if(existTag(val)){
                
            
                if(val){
                    $('<span class="item-tag-imagenes">'+val+'<i class="fas fa-times delete-tag-item" onclick="deleteTagImagenes(this);" style="padding-left: 5px;cursor: pointer;"></i></span>').insertBefore($('.input-create-tags-imagenes'));
                    insertTagsImages();    
                    $('#input-tag-value-imagenes').css('background','none');
                }else{
                    $('#input-tag-value-imagenes').css('background','#ffdcdc');
                }
            }else{
                $('#input-tag-value-imagenes').css('background','#ffdcdc');
            }
            
            
        }
}

function deleteTagImagenes(element){
    //console.log(element[0]);
    //console.log(element.parentElement.remove());
    element.parentElement.remove();
    insertTagsImages();
    
}

function focusInputImagenes(){
    var space = document.getElementById('input-tag-value-imagenes');
    console.log(space);
    space.focus();
}


function insertTagsImagesUp(){
    var itemTags = document.getElementsByClassName('item-tag-imagenes-up');
    var tags = 'null';
    var length = itemTags.length -1;
    for(var i = 0; i < itemTags.length; i++){
        console.log(itemTags[i].textContent);
        if(tags == 'null'){
            
            if(length == i){
                tags = itemTags[i].textContent;    
            }else{
                tags = itemTags[i].textContent+',';    
            }
        }else{
            if(length == i){
                tags += itemTags[i].textContent;    
            }else{
                tags += itemTags[i].textContent+',';
            }
            
        }
        
    }
    console.log(tags);
    var inputValues = document.getElementById('input-tag-value-imagenes-up');
    console.log(inputValues);
    inputValues.value  = '';
    
    var inputKeywords = document.getElementById('keywords-tour-imagenes-up');
    inputKeywords.value = tags;
}

function createTagImagesUp(event){
     var x = event.which || event.keyCode;
        if(x == 13){
            var val = $('#input-tag-value-imagenes-up').val();
            console.log(existTag(val));
            if(existTag(val)){
                
            
                if(val){
                    $('<span class="item-tag-imagenes-up">'+val+'<i class="fas fa-times delete-tag-item" onclick="deleteTagImagenesUp(this);" style="padding-left: 5px;cursor: pointer;"></i></span>').insertBefore($('.input-create-tags-imagenes-up'));
                    insertTagsImagesUp();    
                    $('#input-tag-value-imagenes-up').css('background','none');
                }else{
                    $('#input-tag-value-imagenes-up').css('background','#ffdcdc');
                }
            }else{
                $('#input-tag-value-imagenes-up').css('background','#ffdcdc');
            }
            
            
        }
}

function deleteTagImagenesUp(element){
    //console.log(element[0]);
    //console.log(element.parentElement.remove());
    element.parentElement.remove();
    insertTagsImagesUp();
    
}

function focusInputImagenesUp(){
    var space = document.getElementById('input-tag-value-imagenes-up');
    console.log(space);
    space.focus();
}


// functions idiomas
function insertTagIdiomas(){
    var itemTags = document.getElementsByClassName('item-tag-idiomas');
    var tags = 'null';
    var length = itemTags.length -1;
    for(var i = 0; i < itemTags.length; i++){
        console.log(itemTags[i].textContent);
        if(tags == 'null'){
            
            if(length == i){
                tags = itemTags[i].textContent;    
            }else{
                tags = itemTags[i].textContent+',';    
            }
        }else{
            if(length == i){
                tags += itemTags[i].textContent;    
            }else{
                tags += itemTags[i].textContent+',';
            }
            
        }
        
    }
    console.log(tags);
    var inputValues = document.getElementById('input-tag-value-idiomas');
    console.log(inputValues);
    inputValues.value  = '';
    
    var inputKeywords = document.getElementById('idiomas-tour');
    inputKeywords.value = tags;
}

function createTagIdiomas(event){
     var x = event.which || event.keyCode;
        if(x == 13){
            var val = $('#input-tag-value-idiomas').val();
            console.log(existTag(val));
            if(existTag(val)){
                
            
                if(val){
                    $('<span class="item-tag-idiomas">'+val+'<i class="fas fa-times delete-tag-item" onclick="deleteTagIdiomas(this);" style="padding-left: 5px;cursor: pointer;"></i></span>').insertBefore($('.input-create-tags-idiomas'));
                    insertTagIdiomas();    
                    $('#input-tag-value-idiomas').css('background','none');
                }else{
                    $('#input-tag-value-idiomas').css('background','#ffdcdc');
                }
            }else{
                $('#input-tag-value-idiomas').css('background','#ffdcdc');
            }
            
            
        }
}

function deleteTagIdiomas(element){
    //console.log(element[0]);
    //console.log(element.parentElement.remove());
    element.parentElement.remove();
    insertTagIdiomas();
    
}

function focusInputIdiomas(){
    var space = document.getElementById('input-tag-value-idiomas');
    console.log(space);
    space.focus();
}
//------------------------ funciones de los Tags -------------------------------------

//------------------------ funciones de las fechas -------------------------------------
function openAndClose(element){
     let dates = [];
     let datesArr = [];
     let listAnt = [];
     var identificador = '';
     if(element.id == 'addDate'){
         identificador = 'openDates';
     }else{
         identificador = 'closeDates';
     }
     $('#'+identificador+' .list-group-item').each(function(index) {
       
       datesArr.push($(this).text());
 
    });
    console.log(listAnt);
    $('.input-daterange input').each(function(index) {
        console.log(index);
       
        const date = new Date($(this).val());
        console.log(date);
        console.log($(this).val());
        if ($(this).val()) {
            dates.push(date);
        }
            
        
        
    });
    console.log(dates);
    console.log(datesArr);
    var cantDates = dates.length;
    console.log(cantDates);
    for (let i = 0; i < dates.length; i++) {
        if (i < 1) {
            //editTour.addDate(dates[i]);
            console.log(dates[i]);
            var months = ['01','02','03','04','05','06','07','08','09','10','11','12'];
            
            const date1 = new Date(dates[i]);
            if(cantDates == 1){
                date1.setDate(date1.getDate() + 1); 
                 var length1 = String(date1.getDate());
                if(length1.length == 1){
                    var fecha1 = date1.getFullYear()+'-'+months[date1.getMonth()]+'-0'+date1.getDate();    
                }else{
                    var fecha1 = date1.getFullYear()+'-'+months[date1.getMonth()]+'-'+date1.getDate();
                }
                console.log(jQuery.inArray( fecha1, datesArr));
                 if(jQuery.inArray( fecha1, datesArr ) == -1){
                    datesArr.push(fecha1);  
                   }
            
            }
            
            
            const date2 = new Date(dates[i + 1]);
            
            const difference = ((date2 - date1) / 1000 / 60 / 60 / 24)+1;
            console.log(difference);
            for (let j = 1; j <= difference; j++) {
                let date =  new Date(dates[i]);
                date.setDate(date.getDate() + j);
                //editTour.addDate(date);
                //console.log(date.getDate());
                //console.log(date.getMonth());
                //console.log(date.getFullYear());
                console.log(date);
                console.log(date.getFullYear()+'-'+months[date.getMonth()]+'-'+date.getDate());
                
                var length = String(date.getDate());
                console.log(length.length);
                if(length.length == 1){
                    var fecha = date.getFullYear()+'-'+months[date.getMonth()]+'-0'+date.getDate();    
                }else{
                    var fecha = date.getFullYear()+'-'+months[date.getMonth()]+'-'+date.getDate();
                }
                console.log(jQuery.inArray( fecha, datesArr));
                if(jQuery.inArray( fecha, datesArr ) == -1){
                    datesArr.push(fecha);
                }
                
                
            }
        }
    }
    if(element.id == 'addDate'){
        printDates(datesArr,'#openDates');    
    }else{
        printDates(datesArr,'#closeDates');
    }
    
   $('#date').val('');
        $('#date2').val('');
    
   
    
    
}

function cleanList(element){
    console.log(element);
    $(element).remove();
}

function printDates(dates,id){
    const ul = $(id);
    $(id).empty();
    console.log(dates);
    for(var i = 0; i < dates.length;i++){
        var fecha = dates[i];
        console.log(dates[i]);
        console.log(fecha);
        $("#list-"+fecha).remove();
        ul.append('<li class="list-group-item list-group-item-action item-dates" id="list-'+fecha+'"  onclick="cleanList(this)">'+fecha+'</li>');
                        //$('<li>')
                        //.addClass('list-group-item list-group-item-action')
                        //.attr('id','item-list-'.fecha)
                        //.css({
                        //    'padding': '1px 1px',
                        //    'font-size': '13px',
                        //    'cursor': 'pointer'})
                        //.on('click', () => {
                        //    console.log(fecha);
                        //    cleanList('item-list-'.fecha);
                        //    //printDates(dates,id);
                        //    //this.cleanDate(id, date, list);
                        //    //this.printDate(id, list);
                        //})
                        //.html(dates[i])
                        //.appendTo(ul);
    }
}
//------------------------ funciones de las fechas -------------------------------------
//------------------------ funciones de las categorias -------------------------------------
function createCategory(){
    var categories = [];
    $(".item-tag-category").each(function(index) {
       
       console.log($(this).text());
       categories.push($(this).text());
    
    });
    console.log(categories);
    console.log(jQuery.inArray("Cultura",categories));
    console.log($("#help-input").val());
    if(!$("#help-input").val()){
        if($("select[id=select-categorias]").val() != 0){
            if(jQuery.inArray($("select[id=select-categorias]").val(),categories) == -1){
                $('#content-categories').append('<span class="item-tag item-tag-category">'+$("select[id=select-categorias]").val()+'<i class="fas fa-times delete-tag-item" onclick="deleteCategory(this);" style="padding-left: 5px;cursor: pointer;"></i></span>');
                insertCategorias();
            }
        }
    }else{
        console.log(jQuery.inArray($("#help-input").val(),categories) );
        if(jQuery.inArray($("#help-input").val(),categories) == -1){
            $("select[id=select-categorias]").append('<option selected="selected">'+ $("#help-input").val()+'</option>');
            $('#content-categories').append('<span class="item-tag item-tag-category">'+$("#help-input").val()+'<i class="fas fa-times delete-tag-item" onclick="deleteCategory(this);" style="padding-left: 5px;cursor: pointer;"></i></span>');
            insertCategorias();
        }
    }
   
   

}

function insertCategorias(){
    var itemTags = document.getElementsByClassName('item-tag-category');
    var tags = 'null';
    var length = itemTags.length -1;
    for(var i = 0; i < itemTags.length; i++){
        console.log(itemTags[i].textContent);
        if(tags == 'null'){
            
            if(length == i){
                tags = itemTags[i].textContent;    
            }else{
                tags = itemTags[i].textContent+',';    
            }
        }else{
            if(length == i){
                tags += itemTags[i].textContent;    
            }else{
                tags += itemTags[i].textContent+',';
            }
            
        }
        
    }
    console.log(tags);
    var inputValues = document.getElementById('help-input');
    console.log(inputValues);
    inputValues.value  = '';
    
    var inputKeywords = document.getElementById('categorias');
    inputKeywords.value = tags;
}

function deleteCategory(element){
    //console.log(element[0]);
    //console.log(element.parentElement.remove());
    element.parentElement.remove();
    insertCategorias();
    
}
//------------------------ funciones de las categorias -------------------------------------
//------------------------ funciones de los precios -------------------------------------
function priceArea(){
    console.log($('select[id=select-tipo-prices]').val() != '0');
    if($('select[id=select-tipo-prices]').val() != '0'){
        
    
        $('#body-table-prices').append('<tr>'+
                                                    '<td colspan="2" class="bg-light text-center types-prices" id="title-tipo-'+$('select[id=select-tipo-prices]').val()+'">'+
                                                        'Precio '+$('select[id=select-tipo-prices]').val()+
                                                    '</td>'+
                                                '</tr>'+
                                                '<tr>'+
                                                    '<td >'+
                                                        'Precio Adulto'+
                                                    '</td>'+
                                                    '<td>'+
                                                        '<div class="input-group input-group-sm">'+
                                                            '<div class="input-group-prepend">'+
                                                                '<span class="input-group-text">$</span>'+
                                                            '</div>'+
                                                            '<input type="text" class="form-control form-control-sm" id="price-'+$('select[id=select-tipo-prices]').val()+'-adulto" >'+
                                                            '<div class="input-group-append">'+
                                                                '<span class="input-group-text">.00</span>'+
                                                            '</div>'+
                                                        '</div>'+
                                                    '</td>'+
                                                '</tr>'+
                                                '<tr>'+
                                                    '<td>'+
                                                        'Precio Menor'+
                                                    '</td>'+
                                                    '<td>'+
                                                        '<div class="input-group input-group-sm">'+
                                                            '<div class="input-group-prepend">'+
                                                                '<span class="input-group-text">$</span>'+
                                                            '</div>'+
                                                            '<input type="text" class="form-control form-control-sm" id="price-'+$('select[id=select-tipo-prices]').val()+'-menor" >'+
                                                            '<div class="input-group-append">'+
                                                                '<span class="input-group-text">.00</span>'+
                                                            '</div>'+
                                                        '</div>'+
                                                    '</td>'+
'                                                    '+
                                                '</tr>');
        $(".options-prices").each(function() {
           
           if($(this).text() == $('select[id=select-tipo-prices]').val()){
               $(this).remove();
           }
           
        
        });
    }
}
//------------------------ funciones de los precios -------------------------------------

function generalInfo(){
    $.get('https://panoramex.mx/dashboard/AccessControl',`data=${JSON.stringify({
        action:'recuperarSession'
    })}`,function(response){
        response = JSON.parse(response);
        console.log(response);
        if(response.error == 'true'){
            $.get('https://panoramex.mx/dashboard/users',`data=${JSON.stringify({
                rand:Math.random(),
                action:'rec_session',
                
            })}`,function(response){
                console.log(response);
                $('#modal-content-body').html(response);
                $('.modalP').css('display','block');
            });
        }else if(response.error == 'false'){
        
            //Verificar si existe elemento
            if($('#switch')[0]){
                var active = $('#switch')[0].checked == true ? '1' : '0';    
            }else{
                var active = 'null';
            }
            var id = document.getElementById('select-tour-edit').value;
            var lan = document.getElementById('select-language-content').value;
            var minPerson = $('#minimo-people-tour').val() ? $('#minimo-people-tour').val() : 'null';
            var maxPerson = $('#maximo-people-tour').val() ? $('#maximo-people-tour').val() : 'null';
            var diasAntelacion = $('#antelacion-tour').val() ? $('#antelacion-tour').val() : 'null';
            var relevancia = $('#relevancia-tour').val() ? $('#relevancia-tour').val() : 'null';
            //var privateY = $('#isPrivateY')[0].checked ? $('#isPrivateY')[0].checked : 'null';
            //var privateN = $('#isPrivateN')[0].checked ? $('#isPrivateN')[0].checked : 'null';
            //var minPrivate = $('#minimo-privado-tour').val() ? $('#minimo-privado-tour').val() : 'null';
            var localidad = $('#localidad-tour').val() ? $('#localidad-tour').val() : 'null';
            var estado = $('#estado-tour').val() ? $('#estado-tour').val() : 'null';
            var pais = $('#pais-tour').val() ? $('#pais-tour').val() : 'null';
            var zonaT = $('#zona-tour').val() ? $('#zona-tour').val() : 'null';
            var inicioTour = $('#inicio-tour').val() ? $('#inicio-tour').val() : 'null';
            var finTour = $('#fin-tour').val() ? $('#fin-tour').val() : 'null';
            //var singlePage = $('#single-page').hasClass('select') ? $('#single-page').hasClass('select') : 'null'; 
            //var multiplePage = $('#multiple-page').hasClass('select') ? $('#multiple-page').hasClass('select') : 'null';
            var page = 'single';
            //if(multiplePage == true){
            //    page = 'multiple';
            //}
            //console.log(singlePage);
            console.log(active);
            console.log(minPerson);
            console.log(diasAntelacion);
            console.log(relevancia);
            //console.log(privateY);
            //console.log(privateN);
            //console.log(minPrivate);
            console.log(localidad);
            console.log(estado);
            console.log(pais);
            console.log(zonaT);
            var daysTours = (daysTour());
            $.get('https://panoramex.mx/dashboard/createUpdateTour',`data=${JSON.stringify({
                rand:Math.random(),
                action:'guardaInfoGeneral',
                id,
                lan,
                active,
                page,
                minPerson,
                maxPerson,
                diasAntelacion,
                relevancia,
                //privateY,
                //privateN,
                //minPrivate,
                localidad,
                estado,
                pais,
                zonaT,
                inicioTour,
                finTour,
                daysTours
                
            })}`,function(response){
               
                console.log(response);
                response = JSON.parse(response);
                console.log(response.error);
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
                    }else{
                        $('.alerts').css('background','red');
                        $('.alerts').css('color','white');
                        $('.alerts').html(response.mensaje);
                        $('.alerts').css('display','block');
                        setTimeout(function(){ 
                        
                       $('.alerts').css('display','none');
                       $('.alerts').html('');
                        }, 3000);   
                    }
                }else if(response.error == 'false'){
                     $('.alerts').css('background','green');
                        $('.alerts').css('color','white');
                        $('.alerts').html(response.mensaje);
                        $('.alerts').css('display','block');
                        setTimeout(function(){ 
                        
                       $('.alerts').css('display','none');
                       $('.alerts').html('');
                        }, 3000);   
                }
                 
            });
        }
    });
}

function daysTour(){
    var monday = $('#tourDays1')[0].checked ? $('#tourDays1')[0].checked : 'null';
    var tuesday = $('#tourDays2')[0].checked ? $('#tourDays2')[0].checked : 'null';
    var wednesday = $('#tourDays3')[0].checked ? $('#tourDays3')[0].checked : 'null';
    var thursday = $('#tourDays4')[0].checked ? $('#tourDays4')[0].checked : 'null';
    var friday = $('#tourDays5')[0].checked ? $('#tourDays5')[0].checked : 'null';
    var saturday = $('#tourDays6')[0].checked ? $('#tourDays6')[0].checked : 'null';
    var sunday = $('#tourDays7')[0].checked ? $('#tourDays7')[0].checked : 'null';
    console.log(monday);
    console.log(tuesday);
    console.log(wednesday);
    console.log(thursday);
    console.log(friday);
    console.log(saturday);
    console.log(sunday);
    var vals = {
        monday,
        tuesday,
        wednesday,
        thursday,
        friday,
        saturday,
        sunday
    };
    var days = [];
    days['days']= vals;
    return vals;
}

function prices(){
    $.get('https://panoramex.mx/dashboard/AccessControl',`data=${JSON.stringify({
        action:'recuperarSession'
    })}`,function(response){
        response = JSON.parse(response);
        console.log(response);
        if(response.error == 'true'){
            $.get('https://panoramex.mx/dashboard/users',`data=${JSON.stringify({
                rand:Math.random(),
                action:'rec_session',
                
            })}`,function(response){
                console.log(response);
                $('#modal-content-body').html(response);
                $('.modalP').css('display','block');
            });
        }else if(response.error == 'false'){
       
            var id = document.getElementById('select-tour-edit').value;
            var lan = document.getElementById('select-language-content').value;
            var prices = [];
            $('.types-prices').each(function(index){
               var vals = {
                   tipo: $(this)[0].id.substring(11),
                   adulto:$('#price-'+$(this)[0].id.substring(11)+'-adulto').val(),
                   menor:$('#price-'+$(this)[0].id.substring(11)+'-menor').val()
                   
               };
               //prices[] = vals;
               prices.push(vals);
            });
            console.log(prices);
        
            $.get('https://panoramex.mx/dashboard/createUpdateTour',`data=${JSON.stringify({
                rand:Math.random(),
                action:'guardaPrecios',
                id,
                lan,
                pricesTour:prices
                
            })}`,function(response){
                console.log(response);
                response = JSON.parse(response);
                console.log(response.error);
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
                    }else{
                        $('.alerts').css('background','red');
                        $('.alerts').css('color','white');
                        $('.alerts').html(response.mensaje);
                        $('.alerts').css('display','block');
                        setTimeout(function(){ 
                        
                       $('.alerts').css('display','none');
                       $('.alerts').html('');
                        }, 3000);   
                    }
                }else if(response.error == 'false'){
                     $('.alerts').css('background','green');
                        $('.alerts').css('color','white');
                        $('.alerts').html(response.mensaje);
                        $('.alerts').css('display','block');
                        setTimeout(function(){ 
                        
                       $('.alerts').css('display','none');
                       $('.alerts').html('');
                        }, 3000);   
                }
                 
            });
        }
    });
      
}

function dates(element){
    $.get('https://panoramex.mx/dashboard/AccessControl',`data=${JSON.stringify({
        action:'recuperarSession'
    })}`,function(response){
        response = JSON.parse(response);
        console.log(response);
        if(response.error == 'true'){
            $.get('https://panoramex.mx/dashboard/users',`data=${JSON.stringify({
                rand:Math.random(),
                action:'rec_session',
                
            })}`,function(response){
                console.log(response);
                $('#modal-content-body').html(response);
                $('.modalP').css('display','block');
            });
        }else if(response.error == 'false'){
       
            var tipo = element.id;
            var id = document.getElementById('select-tour-edit').value;
            var lan = document.getElementById('select-language-content').value;
            var openDates=[];
            var closeDates=[];
            $('#openDates .item-dates').each(function(index){
                console.log($(this).text());
                openDates.push($(this).text());
                
            });
            $('#closeDates .item-dates').each(function(index){
                console.log($(this).text());
                closeDates.push($(this).text());
            });
            console.log(closeDates);
            console.log(openDates);
            var dates = [];
            dates['closeDates']= closeDates;
            dates['openDates']= openDates;
            dates = {
                closeDates,
                openDates
            }
            $.get('https://panoramex.mx/dashboard/createUpdateTour',`data=${JSON.stringify({
                rand:Math.random(),
                action:'guardaFechas',
                id,
                lan,
                tipo,
                datesTour:dates
                
            })}`,function(response){
                console.log(response);
                response = JSON.parse(response);
                console.log(response.error);
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
                    }else{
                        $('.alerts').css('background','red');
                        $('.alerts').css('color','white');
                        $('.alerts').html(response.mensaje);
                        $('.alerts').css('display','block');
                        setTimeout(function(){ 
                        
                       $('.alerts').css('display','none');
                       $('.alerts').html('');
                        }, 3000);   
                    }
                }else if(response.error == 'false'){
                     $('.alerts').css('background','green');
                        $('.alerts').css('color','white');
                        $('.alerts').html(response.mensaje);
                        $('.alerts').css('display','block');
                        setTimeout(function(){ 
                        
                       $('.alerts').css('display','none');
                       $('.alerts').html('');
                        }, 3000);   
                }
                 
            });
        }
    });
}



function content(){
    $.get('https://panoramex.mx/dashboard/AccessControl',`data=${JSON.stringify({
        action:'recuperarSession'
    })}`,function(response){
        response = JSON.parse(response);
        console.log(response);
        if(response.error == 'true'){
            $.get('https://panoramex.mx/dashboard/users',`data=${JSON.stringify({
                rand:Math.random(),
                action:'rec_session',
                
            })}`,function(response){
                console.log(response);
                $('#modal-content-body').html(response);
                $('.modalP').css('display','block');
            });
        }else if(response.error == 'false'){
       
            var id = document.getElementById('select-tour-edit').value;
            var lan = document.getElementById('select-language-content').value;
            var titleTour = $('#title-tour').val() ? $('#title-tour').val() : 'null';
            var titlePage = $('#title-page').val() ? $('#title-page').val() : 'null';
            var uriTour = $('#uri-tour').val() ? $('#uri-tour').val() : 'null';
            var descripcionTour = $('#descripcion-tour').val() ? $('#descripcion-tour').val() : 'null';
            var resenaTour = $('#resena-tour').val() ? $('#resena-tour').val() : 'null';
            var keywordsTour = $('#keywords-tour').val() ? $('#keywords-tour').val() : 'null';
            var categorias = $('#categorias').val() ? $('#categorias').val() : 'null';
            var itinerario = $('#itinerario-tour').val() ? $('#itinerario-tour').val() : 'null';
            var incluye = $('#incluye-tour').val() ? $('#incluye-tour').val() : 'null';
            var idiomasDelTour = $('#idiomas-tour').val() ? $('#idiomas-tour').val() : 'null';

            console.log(titleTour);
            console.log(titlePage);
            console.log(uriTour);
            console.log(descripcionTour);
            console.log(resenaTour);
            console.log(keywordsTour);
            var vals = {
                titleTour,
                titlePage,
                uriTour,
                descripcionTour,
                resenaTour,
                keywordsTour,
                categorias,
                itinerario,
                incluye,
                idiomasDelTour
              };
            $.get('https://panoramex.mx/dashboard/createUpdateTour',`data=${JSON.stringify({
                rand:Math.random(),
                action:'guardarContenido',
                id,
                lan,
                titleTour,
                titlePage,
                uriTour,
                descripcionTour,
                resenaTour,
                keywordsTour,
                categorias,
                itinerario,
                incluye,
                idiomasDelTour
                
            })}`,function(response){
                generalInfo();
                console.log(response);
                response = JSON.parse(response);
                console.log(response.error);
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
                    }else{
                        $('.alerts').css('background','red');
                        $('.alerts').css('color','white');
                        $('.alerts').html(response.mensaje);
                        $('.alerts').css('display','block');
                        setTimeout(function(){ 
                        
                       $('.alerts').css('display','none');
                       $('.alerts').html('');
                        }, 3000);   
                    }
                }else if(response.error == 'false'){
                     $('.alerts').css('background','green');
                        $('.alerts').css('color','white');
                        $('.alerts').html(response.mensaje);
                        $('.alerts').css('display','block');
                        setTimeout(function(){ 
                        
                       $('.alerts').css('display','none');
                       $('.alerts').html('');
                        }, 3000);   
                }
                 
            });
        }
    });
}

function imagesTour(){
    var id = document.getElementById('select-tour-edit').value;
    var lan = document.getElementById('select-language-content').value;
    var images = [];
    $('.image-tour').each(function(index){
        console.log($(this)[0].name);
        images.push($(this)[0].name);
        
    });
    console.log(images);
    var imagesTour = [];
    imagesTour['imagesTour']= images;
    $.get('https://panoramex.mx/dashboard/createUpdateTour',`data=${JSON.stringify({
        rand:Math.random(),
        action:'guardaImagenes',
        id,
        lan,
        imagesTours:images
        
    })}`,function(response){
        console.log(response);
        response = JSON.parse(response);
        console.log(response.error);
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
            }else{
                $('.alerts').css('background','red');
                $('.alerts').css('color','white');
                $('.alerts').html(response.mensaje);
                $('.alerts').css('display','block');
                setTimeout(function(){ 
                
               $('.alerts').css('display','none');
               $('.alerts').html('');
                }, 3000);   
            }
        }else if(response.error == 'false'){
             $('.alerts').css('background','green');
                $('.alerts').css('color','white');
                $('.alerts').html(response.mensaje);
                $('.alerts').css('display','block');
                setTimeout(function(){ 
                
               $('.alerts').css('display','none');
               $('.alerts').html('');
                }, 3000);   
        }
         
    });
    
}

function construyeToursList(){
    console.log($(".tours-list").data("tours"));
    var toursString = $(".tours-list").data("tours");
    var toursList = toursString.split(",");

    var cont = 0;
    
    
    for(var i = 0; i<toursList.length;i++){
        
            
             
                var elementsTour = toursList[i].split("-");
          
                if(cont == 0){
                   
                    cont++;
               
                        $(".tours-list").html('<li class="drag-item" id="group-'+elementsTour[1]+'">'+elementsTour[0]+"</li>");    
                    
                }else{
                  
                 
                        $(".tours-list").append('<li class="drag-item" id="group-'+elementsTour[1]+'">'+elementsTour[0]+"</li>");
                    
                }
        
    }
}

function validaListaTours(element){
    console.log(element.id);
    var id = element.id.substring(6);
    var lan = document.getElementById('select-language-content').value;
    var container = $('#'+element.id)[0].parentNode.classList.contains('group-list');
    console.log(container);
    if(container == true){
        
    
        $.get('https://panoramex.mx/dashboard/createUpdateTour',`data=${JSON.stringify({
            rand:Math.random(),
            action:'validaListaTours',
            id,
            lan
            
        })}`,function(response){
           console.log(response);
            response = JSON.parse(response);
            console.log(response.error);
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
                }else{
                     construyeToursList();
                     $('.group-list #'+element.id).remove()
                    $('.alerts').css('background','red');
                    $('.alerts').css('color','white');
                    $('.alerts').html(response.mensaje);
                    $('.alerts').css('display','block');
                    setTimeout(function(){ 
                    
                   $('.alerts').css('display','none');
                   $('.alerts').html('');
                    }, 3000);   
                }
            }else if(response.error == 'false'){
               
                 $('.alerts').css('background','green');
                    $('.alerts').css('color','white');
                    $('.alerts').html(response.mensaje);
                    $('.alerts').css('display','block');
                    setTimeout(function(){ 
                    
                   $('.alerts').css('display','none');
                   $('.alerts').html('');
                    }, 3000);   
            }
        });
    }
}

function multiplesTours(){
    $.get('https://panoramex.mx/dashboard/AccessControl',`data=${JSON.stringify({
        action:'recuperarSession'
    })}`,function(response){
        response = JSON.parse(response);
        console.log(response);
        if(response.error == 'true'){
            $.get('https://panoramex.mx/dashboard/users',`data=${JSON.stringify({
                rand:Math.random(),
                action:'rec_session',
                
            })}`,function(response){
                console.log(response);
                $('#modal-content-body').html(response);
                $('.modalP').css('display','block');
            });
        }else if(response.error == 'false'){
       
            var id = document.getElementById('select-tour-edit').value;
            var lan = document.getElementById('select-language-content').value;
            var tituloPagina = $('#title-page-general-tour').val() ? $('#title-page-general-tour').val() : 'null';
            var uriPagina = $('#uri-page-general-tour').val() ? $('#uri-page-general-tour').val() : 'null';
            var contenidoPagina = $('#contenido-page-general-tour').val() ? $('#contenido-page-general-tour').val() : 'null';
            var multiplePage = $('#multiple-page').hasClass('select') ? $('#multiple-page').hasClass('select') : 'null';
            var page = 'single';
            if(multiplePage == true){
                page = 'multiple';
            }
            var items = [];
            var itemsTour = '';
            var cont = 0;
            $('.group-list .drag-item').each(function(){
                cont++;
                console.log($(this).text());
                console.log($(this)[0].id.substring(6));
                items.push($(this)[0].id.substring(6));
                if(cont == 1){
                    itemsTour += ','+$(this)[0].id.substring(6)+',';
                }else{
                    itemsTour += $(this)[0].id.substring(6)+',';    
                }
                
            })
            var multiples = {
            itemsTour,
            tituloPagina,
            uriPagina,
            contenidoPagina,
            items,
            page
            };
            console.log(JSON.stringify(multiples));
            console.log(tituloPagina);
            //return (multiples);
            $.get('https://panoramex.mx/dashboard/createUpdateTour',`data=${JSON.stringify({
                rand:Math.random(),
                action:'saveMultiples',
                id,
                lan,
                page,
                itemsTour,
                tituloPagina,
                uriPagina,
                contenidoPagina,
                items
                
            })}`,function(response){
                console.log(response);
                response = JSON.parse(response);
                console.log(response.error);
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
                    }else{
                        $('.alerts').css('background','red');
                        $('.alerts').css('color','white');
                        $('.alerts').html(response.mensaje);
                        $('.alerts').css('display','block');
                        setTimeout(function(){ 
                        
                       $('.alerts').css('display','none');
                       $('.alerts').html('');
                        }, 3000);   
                    }
                }else if(response.error == 'false'){
                     $('.alerts').css('background','green');
                        $('.alerts').css('color','white');
                        $('.alerts').html(response.mensaje);
                        $('.alerts').css('display','block');
                        setTimeout(function(){ 
                        
                       $('.alerts').css('display','none');
                       $('.alerts').html('');
                        }, 3000);   
                }
                 
            });
        }
    });
        
}

function saveInformation(){
    var generalInfoTur = (generalInfo());
    var pricesTour = (prices());
    var contentTour = (content());
    var imagesTours = (imagesTour());
    var daysTours = (daysTour());
    var datesTour = (dates());
    var multiplesT = multiplesTours();
    var id = document.getElementById('select-tour-edit').value;
    var lan = document.getElementById('select-language-content').value;
    var vals = {
        generalInfoTur,
        pricesTour,
        
        imagesTours,
        daysTours,
        datesTour,
        multiplesT,
        id,
        lan
    }
    var info = window.btoa(JSON.stringify(vals)); 
    $.get('https://panoramex.mx/dashboard/createUpdateTour',`data=${JSON.stringify({
        rand:Math.random(),
        action:'saveChanges',
        info,
        contentTour
        
    })}`,function(response){
        console.log(response);
        response = JSON.parse(response);
        console.log(response.error);
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
            }else{
                $('.alerts').css('background','red');
                $('.alerts').css('color','white');
                $('.alerts').html(response.mensaje);
                $('.alerts').css('display','block');
                setTimeout(function(){ 
                
               $('.alerts').css('display','none');
               $('.alerts').html('');
                }, 3000);   
            }
        }else if(response.error == 'false'){
             $('.alerts').css('background','green');
                $('.alerts').css('color','white');
                $('.alerts').html(response.mensaje);
                $('.alerts').css('display','block');
                setTimeout(function(){ 
                
               $('.alerts').css('display','none');
               $('.alerts').html('');
                }, 3000);   
        }
         
    });
}

function sendValidInfoo(){
    var generalInfoTur = (generalInfo());
    var pricesTour = (prices());
    var contentTour = (content());
    var imagesTours = (imagesTour());
    var daysTours = (daysTour());
    var datesTour = (dates());
    var id = document.getElementById('select-tour-edit').value;
    var mensaje = document.getElementById('mensaje-user').value ? document.getElementById('mensaje-user').value : 'null';
    var validate = document.getElementById('validate-info').checked;
    var rechazo = document.getElementById('rechazo-info').checked;
    var lan = document.getElementById('select-language-content').value;
    $.get('https://panoramex.mx/dashboard/createUpdateTour',`data=${JSON.stringify({
        rand:Math.random(),
        action:'accionesValidacion',
        generalInfoTur,
        pricesTour,
        contentTour,
        imagesTours,
        daysTours,
        datesTour,
        id,
        lan,
        validate,
        rechazo,
        mensaje
    })}`,function(response){
        console.log(response);
        response = JSON.parse(response);
        console.log(response.error);
        if(response.error == 'true'){
            if(response.action == 'session'){
               // window.location = response.url;
                $.get('https://panoramex.mx/dashboard/users',`data=${JSON.stringify({
                    rand:Math.random(),
                    action:'rec_session',
                    
                })}`,function(response){
                    console.log(response);
                    $('#modal-content-body').html(response);
                    $('.modalP').css('display','block');
                });
            }else{
                $('.alerts').css('background','red');
                $('.alerts').css('color','white');
                $('.alerts').html(response.mensaje);
                $('.alerts').css('display','block');
                setTimeout(function(){ 
                
               $('.alerts').css('display','none');
               $('.alerts').html('');
                }, 3000);   
            }
        }else if(response.error == 'false'){
             $('.alerts').css('background','green');
                $('.alerts').css('color','white');
                $('.alerts').html(response.mensaje);
                $('.alerts').css('display','block');
                setTimeout(function(){ 
                
               $('.alerts').css('display','none');
               $('.alerts').html('');
                }, 3000);   
        }
         
    });
}


function validacionUri(element) {
    
        console.log("key pressed ",  String.fromCharCode(event.keyCode));
        console.log(event.keyCode);
        var val = document.getElementById(element.id).value;
        console.log(val);
        val = normalize(val)
        console.log(val);
        document.getElementById(element.id).value = val;
    
  
}

var normalize = (function() {
  var from = "ÃÀÁÄÂÈÉËÊÌÍÏÎÒÓÖÔÙÚÜÛãàáäâèéëêìíïîòóöôùúüûÑñÇç'", 
      to   = "AAAAAEEEEIIIIOOOOUUUUaaaaaeeeeiiiioooouuuunncc",
      mapping = {};
 
  for(var i = 0, j = from.length; i < j; i++ )
      mapping[ from.charAt( i ) ] = to.charAt( i );
 
  return function( str ) {
      var ret = [];
      for( var i = 0, j = str.length; i < j; i++ ) {
          var c = str.charAt( i );
          if( mapping.hasOwnProperty( str.charAt( i ) ) )
              ret.push( mapping[ c ] );
          else
              ret.push( c );
      }      
      return ret.join( '' ).replace( /[^-A-Za-z0-9]+/g, '-' ).toLowerCase();
  }
 
})();

function guardazonaTuristica(){
    
}



function muestraModalZonaTuristica(){
    
    $('.modal-titleP').css('display','inline-block');
    $('.btnP').css('display','inline-block');
    $('.modalP').css('display','block');
    $('.modal-bodyP').html(
        '<div class="form-group " style="padding:5px;position:relative;">'+
        '<!-- Localidad -->'+
            '<label style="font-weight:bold;color:gray;" for="localidad-tour">Zona Turistica'+
                '<div class="help-tip" style="z-index:1000;top:8px;right:32px;position:sticky;display:inline-block;">'+
                    '<p style="z-index:1000;">Ingresa el lugar donde se realiza el tour.</p>'+
                '</div>'+
            '</label>'+
            '<input type="text" class="form-control form-control-sm" id="input-zona-turistica-tour" placeholder="zona turistica" value="'+$("#zona-tour")[0].dataset['newzona']+'">'+
        '<!-- Minimo personas -->'+
            '<label style="font-weight:bold;color:gray;" for="minimo-people-tour" id="label-minimo-people-tour">Frase Turistica'+
                '<div class="help-tip" style="z-index:1000;top:8px;right:32px;position:sticky;display:inline-block;">'+
                    '<p style="z-index:1000;">Ingresa el minimo de personas que se requieren para realizar el tour.</p>'+
                '</div>'+
            '</label>'+
            '<input type="text" class="form-control form-control-sm" id="input-frase-tour" placeholder="Minimo Personas">'+
        '</div>'+
        '<div class="row" style="text-align:center;margin: 0;text-align: center;justify-content: center;">'+
        '    <button type="button" class="btn btn-primary" style="justify-content: center;" onclick="agregaZonaTuristica();">Agregar</button>'+
        '</div>'+
        '<div class="row" style="text-align:center;margin: 0;text-align: center;justify-content: center;display:none;" id="alerts-zona-tour"> '+
        '</div>'
        );
    
}
function recuperarSession(){
    console.log('recuperarSession');
    const user = $('#username').val();
    const pass = $('#password').val();

    if (user !== '' && pass !== '') {
        $.post('https://panoramex.mx/dashboard/users', `data=${JSON.stringify({
            email: user,
            password: pass,
            action: 'authenticate'
        })}`, (response) => {
            if (response.authenticate == 1) {
                //window.location.replace(response.url);
                $('.modalP').css('display','none');
            } else {
                $('#errorMsg').html(response.error).show();
                //if(response.error == 'Por favor verifica tu contraseña'){
                    //$('#generateLinkContra').html('<a href="javascript:sendLink();">Reestrablecer contraseña</a>');    
                //}
                animate('#user-container');
                animate('#pass-container');
            }
        }, 'json');
    }else {
        if ($('#username').val() === '') {
            animate('#user-container');
        }
        if ($('#password').val() === '') {
            animate('#pass-container');
        }
    }
}


function printCodeDates(element){
    var tipo = '';
    var idTour = $('#select-tour-edit').val();
    if(element.id == 'codigo-privado'){
        tipo = 'privado';
    }else if(element.id == 'codigo-grupal'){
        tipo = 'grupal';
    }
    $.get('https://panoramex.mx/dashboard/createUpdateTour',`data=${JSON.stringify({
            action:'codigoDates',
            idTour,
            tipo
        })}`,function(response){
            response = JSON.parse(response);
            if(response.tipo == 'privado'){
                $('#fechas-grupal').html('');
                $('#fechas-privado').html(response.code);
                $('#codigo-grupal').removeClass('collapsed');
                $('#dates-tour').removeClass('show');
            }else if(response.tipo == 'grupal'){
                $('#fechas-privado').html('');
                $('#fechas-grupal').html(response.code);
                $('#codigo-privado').removeClass('collapsed');
                $('#dates-privado-tour').removeClass('show');
            }
        });
    
}