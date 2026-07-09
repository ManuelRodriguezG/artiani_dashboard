var modelosVehiculos = "";
var imagenesMarcas = "";
var idsImagen = "";
$(document).ready(function(){
    $('#catalagoAutos').click(function(){
        codeVehiculos();
    });
    
    
    
    
});

function codeVehiculos(){
    $.get("https://melorautopartes.com/dashboard/vehiculos",`data=${JSON.stringify({
            action:"codeVehiculos"
        })}`,function(response){
            console.log(response);
            response = JSON.parse(response);
            if(response.error == "false"){
                $("#views-zone").html(response.code);
                //function parseArrayObject, location: https://melorautopartes.com/js/helpers/main.js
                var arregloMarcas = parseArrayObject(response.data);
                modelosVehiculos = response.modelos;
                imagenesMarcas = response.imagenesMarcas;
                idsImagen = response.idsImagen;
                //constructor (containerName, object, datos, leyenda, agregar=true, funcion='',icon='')
                //let funct = new Function(employeeSelector);
                window["select-marcas"] = new SelectU("containerSelectMarcas","select-marcas",arregloMarcas,"Seleccionar Marca",true,construirModelosVehiculos);
                
                
                
                const fileImage = document.querySelector('.input-preview__src1');
                const filePreview = document.querySelector('.input-preview1');
                
                fileImage.onchange = function () {
                    const reader = new FileReader();
                
                    reader.onload = function (e) {
                        // get loaded data and render thumbnail.
                        filePreview.style.backgroundImage  = "url("+e.target.result+")";
                        filePreview.classList.add("has-image");
                        filePreview.classList.add("edit-image");
                    };
                
                    // read the image file as a data URL.
                    reader.readAsDataURL(this.files[0]);
                };
                
                //sube imagen 
                $(".upload-image").click(function(){
                    if($(".input-preview2").hasClass("has-image")){
                        var resp = enviarImagenMarcas("insert-image-file");
                        console.log(resp);
                        resp = JSON.parse(resp);
                        if(resp.bandera == "true"){
                            //toogleAlert("success",resp.mensaje);
                            console.log(resp);
                            if($("#agregar-modelo").val()){
                                var infoVehiculo = {
                                    marca:$("#containerSelectMarcas-inner-input").val(),
                                    modelo:$("#agregar-modelo").val(),
                                    infoImage:{
                                        url: resp.url,
                                        nombre:resp.nombre,
                                        peso:resp.peso,
                                        descripcion:"Imagen de la marca "+$("#containerSelectMarcas-inner-input").val(),
                                        keywords:$("#containerSelectMarcas-inner-input").val()+","+$("#agregar-modelo").val(),
                                        
                                    }                                };
                                $.get('https://melorautopartes.com/dashboard/vehiculos',`data=${JSON.stringify({
                                    action:"nuevaMarca",
                                    infoVehiculo
                                })}`,function(response){
                                    console.log(response);
                                    response = JSON.parse(response);
                                    if(response.sessionActive){
                                        console.log(response.codeRecuperation);
                                        
                                        $("#modal-content-body").html(response.codeRecuperation);
                                        openModalCompany();
                                    }else{
                                        if(response.error == "false"){
                                            toogleAlert("success",response.msg);
                                            //$("#agregar-modelo-marca").slideToggle("slow");
                                            
                                            //Agregar los elementos agregados a la lista para posible edicion de los mismos
                                            //Marcas
                                               // window["select-marcas"].lista.push([String(response.data.idMarca),response.data.marca]);
                                               // window["select-marcas"].added = [];
                                               // //Modelos
                                               // var newModelo = {
                                               //     id:String(response.data.idModelo),
                                               //     modelo:response.data.modelo,
                                               //     idMarca:String(response.data.idMarca)
                                               //     
                                               // }; 
                                               // modelosVehiculos.push(newModelo);
                                            //console.log(window["select-marcas"])
                                            codeVehiculos();
                                        }else if(response.error == "true"){
                                            toogleAlert("warning",response.msg);
                                        }
                                    }
                                });
                                
                            }else{
                                toogleAlert("warning","No se ha insertado el modelo");    
                            }
                            
                            
                        }else{
                            toogleAlert("warning",resp.mensaje);
                        }
                    }else{
                        toogleAlert("warning","No se ha insertado imagen de la Marca");
                    }
                    
                    
                })
                
                
           }else{
                if(response.sessionActive){
                    console.log(response.codeRecuperation);
                    
                    $("#modal-content-body").html(response.codeRecuperation);
                    openModalCompany();
                }else{
                    toogleAlert("warning",response.msg);
                }
               
           }
        })
}



function actualizarMarcaVehiculo(data){
    if($(".input-preview1").hasClass("has-image")){
        actualizarImagen();
    }else{
        if($("#texto-marca-editable").val()){
                var infoVehiculo = {
                    marca:$("#texto-marca-editable").val(),
                    idMarca:document.getElementById("containerSelectMarcas-inner-input").getAttribute("data-id-option"),
                                              };
                $.get('https://melorautopartes.com/dashboard/vehiculos',`data=${JSON.stringify({
                    action:"actualizarMarca",
                    infoVehiculo
                })}`,function(response){
                    console.log(response);
                    response = JSON.parse(response);
                    if(response.sessionActive){
                        console.log(response.codeRecuperation);
                        
                        $("#modal-content-body").html(response.codeRecuperation);
                        openModalCompany();
                    }else{
                        if(response.error == "false"){
                            toogleAlert("success",response.msg);
                           
                            codeVehiculos();
                        }else if(response.error == "true"){
                            toogleAlert("warning",response.msg);
                        }
                    }
                });
                
            }else{
                toogleAlert("warning","No se ha insertado el modelo");    
            }
    }
                           
                            
                            
                            
                            
                        
}

function actualizarImagen(){
    var resp = enviarImagenMarcas("update-image-file");
        console.log(resp);
        resp = JSON.parse(resp);
        if(resp.bandera == "true"){
            //
                        if($("#texto-marca-editable").val()){
                                var idMarca = document.getElementById("containerSelectMarcas-inner-input").getAttribute("data-id-option");
                                var infoVehiculo = {
                                    marca:$("#texto-marca-editable").val(),
                                    idMarca,
                                    idImagen:idsImagen[idMarca]
                                    
                                };
                                $.get('https://melorautopartes.com/dashboard/vehiculos',`data=${JSON.stringify({
                                    action:"actualizarMarca",
                                    infoVehiculo,
                                    infoImage:{
                                        url: resp.url,
                                        nombre:resp.nombre,
                                        peso:resp.peso,
                                        descripcion:"Imagen de la marca "+$("#texto-marca-editable").val(),
                                        id:idsImagen[idMarca]
                                        
                                    }   
                                })}`,function(response){
                                    console.log(response);
                                    response = JSON.parse(response);
                                    if(response.sessionActive){
                                        console.log(response.codeRecuperation);
                                        
                                        $("#modal-content-body").html(response.codeRecuperation);
                                        openModalCompany();
                                    }else{
                                        if(response.error == "false"){
                                            toogleAlert("success",response.msg);
                                           
                                            codeVehiculos();
                                        }else if(response.error == "true"){
                                            toogleAlert("warning",response.msg);
                                        }
                                    }
                                });
                                
                            }else{
                                toogleAlert("warning","No se ha insertado el modelo");    
                            }
        }else{
            toogleAlert("warning",resp.mensaje);
        }
}



function enviarImagenMarcas(idInputFile){
  
                    
                    var obj=JSON.stringify({
                        width:200,
                        height:200,
                        relacionAspecto:true,
                        nombreImagen:$("#containerSelectMarcas-inner-input").val(),
                        weight:100,
                        action:'cargarParametros',
                        action2:'keywords',
                        keywords:"fsdfsd",
                        descripcion:"hola",
                        nombreReemplazo:$("#containerSelectMarcas-inner-input").val(),
                        identificadorFile:idInputFile,
                        relacionAspecto:true //conservar relacion de aspecto
                        
                    });
                    var resp = enviarParametrosImages(obj);
                    //console.log(resp+" esta es la respuesta");
                    //resp = JSON.parse(resp);
                    //if(resp.bandera == "true"){
                    //    toogleAlert("success",resp.mensaje);
                    //}else{
                    //    toogleAlert("warning",resp.mensaje);
                    //}
                    return resp;
           
}

function getModels(idMarca){
    var arreglo = [];
    modelosVehiculos.forEach(function(valor,clave,modelosVehiculos){
        console.log(valor.idMarca)
        if(valor.idMarca == idMarca){
            arreglo.push([String(valor.id),valor.modelo]);
        }
    });
    return arreglo;
}

function construirModelosVehiculos(){
    
    
    
    var idMarca = document.getElementById("containerSelectMarcas-inner-input").getAttribute("data-id-option");
    if(idMarca != 'null'){
        //Ocultar insert marca si esta visible
        if($("#agregar-modelo-marca").hasClass("show-marca-editable") == true){
            $('#agregar-modelo-marca').slideToggle("slow");
            $("#agregar-modelo-marca").removeClass("show-marca-editable");
        }
        
        //Mostrar imagen de la marca
        if(imagenesMarcas[idMarca] != "null"){
            var filePreview = document.querySelector('.input-preview1');
            filePreview.style.backgroundImage  = "url("+imagenesMarcas[idMarca]+")";
            //filePreview.classList.add("has-image");
            filePreview.classList.add("edit-image");    
        }else{
            var filePreview = document.querySelector('.input-preview1');
            filePreview.style.backgroundImage  = "";
            //filePreview.classList.add("has-image");
            filePreview.classList.remove("edit-image");  
        }
        
        //asigna valor al input editable
        $("#texto-marca-editable").val($("#containerSelectMarcas-inner-input").val());
        
        //Limpiar contenedor Select
        $("#containerSelectModelos").html("");
        //controla contenedor editable de modelo
        if($('#editable-modelo').hasClass("show-editable-modelo") == true){
            $('#editable-modelo').slideToggle("slow");
            $('#editable-modelo').removeClass("show-editable-modelo");
        }
        //controla contenedor editable de marca y select de modelo
        if($('#select-modelo-vehiculo').hasClass("show-modelo") == false){
            $('#select-modelo-vehiculo').slideToggle("slow");
            if($('#editable-marca').hasClass("show-editable-marca") == false){
                $('#editable-marca').slideToggle("slow");
                $('#editable-marca').addClass("show-editable-marca");    
            }
            
            
            $('#select-modelo-vehiculo').addClass("show-modelo");
        }
        var arregloModelos = getModels(idMarca);
        window["select-modelos"] = new SelectU("containerSelectModelos","select-modelos",arregloModelos,"Seleccionar Modelo",true,mostrarEditableModelo);    
    }else{
        if($("#editable-marca").hasClass("show-editable-marca") == true){
            $('#editable-marca').slideToggle("slow");
            $("#editable-marca").removeClass("show-editable-marca");
        }
        if($("#select-modelo-vehiculo").hasClass("show-modelo") == true){
            $('#select-modelo-vehiculo').slideToggle("slow");
            $("#select-modelo-vehiculo").removeClass("show-modelo");
        }
        if($("#editable-modelo").hasClass("show-editable-modelo") == true){
            $('#editable-modelo').slideToggle("slow");
            $("#editable-modelo").removeClass("show-editable-modelo");
        }
       agregarMarcaModelo();
    }
    
    console.log("modelos Vehiculos");
}

function agregarMarcaModelo(){
    
    $('#agregar-modelo-marca').slideToggle("slow");
    $('#agregar-modelo-marca').addClass("show-marca-editable");
    const fileImage = document.querySelector('.input-preview__src2');
    const filePreview = document.querySelector('.input-preview2');
                
    fileImage.onchange = function () {
        const reader = new FileReader();
    
        reader.onload = function (e) {
            // get loaded data and render thumbnail.
            filePreview.style.backgroundImage  = "url("+e.target.result+")";
            filePreview.classList.add("has-image");
            filePreview.classList.add("edit-image");
        };
    
        // read the image file as a data URL.
        reader.readAsDataURL(this.files[0]);
    };
    
}

function buscarModelo(){
    console.log($("#containerSelectModelos-inner-input").val());
    var modeloABuscar = $("#containerSelectModelos-inner-input").val();
    var find = false;
    modelosVehiculos.forEach(function(elemento,index,modelosVehiculos){
        console.log(elemento.modelo)
        if(elemento.modelo == modeloABuscar){
            find = true;
        }
        
    })
    return find;
}


function mostrarEditableModelo(){
    var resp = buscarModelo();
    if(resp == true){
        $("#button-modelo").html('<i class="fas fa-sync"></i>');
        $("#button-modelo").addClass('update-modelo');
        $("#button-modelo").removeClass('insert-modelo');
        
    }else{
        $("#button-modelo").html('Guardar');
        $("#button-modelo").removeClass('update-modelo');
        $("#button-modelo").addClass('insert-modelo');
    }
    if($('#editable-modelo').hasClass("show-editable-modelo") == false){
            
            $('#editable-modelo').slideToggle("slow");
            
            $('#editable-modelo').addClass("show-editable-modelo");
        }else{
            
        }
    $("#texto-modelo-editable").val($("#containerSelectModelos-inner-input").val());
}

function controlAccionesDeModelos(element){
    var action = "";
    var idModelo = 0;
    var idMarca = 0;
    var modelo = $("#texto-modelo-editable").val();
    console.log(element.classList.contains("update-modelo"));
    if(element.classList.contains("update-modelo") == true){
        action = "update-modelo"; 
        idModelo = document.getElementById("containerSelectModelos-inner-input").getAttribute("data-id-option");
    }else if(element.classList.contains("insert-modelo") == true){
        action = "insert-modelo"; 
        idMarca = document.getElementById("containerSelectMarcas-inner-input").getAttribute("data-id-option");
    }
    
    $.get("https://melorautopartes.com/dashboard/vehiculos",`data=${JSON.stringify({
        action,
        idModelo,
        modelo,
        idMarca
    })}`,function(response){
        response = JSON.parse(response);
        if(response.sessionActive){
            console.log(response.codeRecuperation);
            
            $("#modal-content-body").html(response.codeRecuperation);
            openModalCompany();
        }else{
            
            if(response.error == "false"){
                //refrescar select modelo
                if(action == "update-modelo"){
                    actualizarArreglo(response);    
                }else if(action == "insert-modelo"){
                    insertarArregloModelos(response);
                }
                
                //actualizar arreglo de modelos
            }else if(response.error == "true"){
                toogleAlert("warning",response.msg);
            }
        }
        console.log(response);
    });
}

function insertarArregloModelos(data){
    modelosVehiculos.push({id:data.idModelo,modelo:data.modelo,idMarca:data.idMarca});
    construirModelosVehiculos();
}

function actualizarArreglo(data){
    //console.log(data);
    
    var idModelo = data.idModelo;
    var modelo = data.modelo;
    modelosVehiculos.forEach(function(elemento,index,modelosVehiculos){
        console.log(elemento.id == idModelo)
        if(elemento.id == idModelo){
            elemento.modelo = modelo;
            modelosVehiculos[index].modelo = modelo;
        }
        
    })
    construirModelosVehiculos();
}