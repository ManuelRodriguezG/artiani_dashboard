/**
 * Control de Accesos
 * 
 * 
*/
$(document).ready(function(){
   
   
   
   $("#accessControl").click(function(){
       $.get("https://melorautopartes.com/dashboard/accessControl",`data=${JSON.stringify({
           action:"codeAccessControl"
       })}`,function(response){
           console.log(response);
           response = JSON.parse(response);
           if(response.error == "false"){
               $("#views-zone").html(response.code);
               console.log(response.users);
               var arreglo = [];
               var users = response.users;
               Object.keys(users).forEach(function (key) {
               
                  
                    arreglo.push([String(key),users[key]]);
                   
                })
                //constructor (containerName, object, datos, leyenda, agregar=true, funcion='',icon='')
                //let funct = new Function(employeeSelector);
                window["select"] = new SelectU("containerPrueba","select",arreglo,"Seleccionar Usuario",false,employeeSelector);
                
                /**
                 * Bt Seccion Agregar Acceso
                */
                $(".btn-add-access").click(function(){
                    $('#add-access').slideToggle("slow"); 
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
           
       
       
           /**
            * Insertar nueva zona de control de accesos
           */
           $("#add-access-control").click(function(){
               var newAccess = $("#input-add-new-access").val();
               if(newAccess){
                    $.get("https://melorautopartes.com/dashboard/accessControl",`data=${JSON.stringify({
                       action:"agregarAccess",
                       newAccess
                   })}`,function(response){
                       
                       console.log(response);
                       response = JSON.parse(response);
                        if(response.error == "false"){
                            toogleAlert("success",response.msg);   
                        }else{
                            toogleAlert("warning",response.msg);
                        }
                   })
               }else{
                   toogleAlert("warning","Campo Obligatorio");
               }
               
           });
           
           /**
            * Guardar cambios de accesos
           */
           $("#save-access").click(function(){
               console.log('Event save access');
               saveAccessEmployee();
           })
       })
   })
    
});

function employeeSelector(){
    console.log("corrio la function");
    var idOption = document.getElementById('containerPrueba-inner-input').getAttribute("data-id-option");
    $.get("https://melorautopartes.com/dashboard/accessControl",`data=${JSON.stringify({
        action:"actions",
        idOption
    })}`,function(response){
        console.log(response);
        response = JSON.parse(response);
        if(response.sessionActive){
                    console.log(response.codeRecuperation);
                    
                    $("#modal-content-body").html(response.codeRecuperation);
                    openModalCompany();
                }else{
                    $("#accesos-employee").html(response.respuesta);
                }
    })
}

function saveAccessEmployee(){
    var checks = $('.checkAccess');
    var idUser = document.getElementById('containerPrueba-inner-input').getAttribute("data-id-option");
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
    $.get('https://melorautopartes.com/dashboard/accessControl', `data=${JSON.stringify({
            action: 'saveChanges',
            checks,
            idUser,
            id:'Configuracion'
          }
        
        
        )}`,  function(response) {
            response = JSON.parse(response);
            console.log(response);
            if(response.error == "false"){
                toogleAlert("success",response.msg);   
            }else{
                toogleAlert("warning",response.msg);
            }
            
        });
    console.log(idUser);
    console.log(arrayChecks);
}
    