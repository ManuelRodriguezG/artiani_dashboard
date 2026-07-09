function crear_paquete(data) {
    let respuesta;

    //se utiliza $.ajax(), a la cual se le pasa un objeto {}, con la información
    $.ajaxSetup({async: false});
    $.ajax({
        type: "POST", // la variable type guarda el tipo de la peticion GET,POST,..
        url: "/paquetes/registrar", //url guarda la ruta hacia donde se hace la peticion
        data: data, // data recive un objeto con la informacion que se enviara al servidor
        success: function (datos) { //success es una funcion que se utiliza si el servidor retorna informacion
            console.log(datos);
            respuesta = JSON.parse(datos);
        }
    });
    $.ajaxSetup({async: true});
    return respuesta;
}

async function actualizar_paquete_producto_portada(data) {
    let respuesta = [];
    //se utiliza $.ajax(), a la cual se le pasa un objeto {}, con la información
//  $.ajaxSetup({async: false});
    respuesta = await fetch("/paquetes/actualizar_portada", {method: 'POST', body: data})
            .then((response) => response.json())
            .then((data) => {
                return data;
            });

    console.log(respuesta);
//  $.ajaxSetup({async: true});
    return respuesta;
}

function listar_paquetes(data) {
    let respuesta = [];
    //se utiliza $.ajax(), a la cual se le pasa un objeto {}, con la información
    $.ajaxSetup({async: false});
    $.ajax({
        type: "POST", // la variable type guarda el tipo de la peticion GET,POST,..
        url: "/paquetes/listar", //url guarda la ruta hacia donde se hace la peticion
        data: data, // data recive un objeto con la informacion que se enviara al servidor
        success: function (datos) { //success es una funcion que se utiliza si el servidor retorna informacion
            console.log(datos);
            respuesta = JSON.parse(datos);
        }
    });
    $.ajaxSetup({async: true});
    return respuesta;
}

function consultar_paquete_editar(data) {
    let respuesta = [];
    //se utiliza $.ajax(), a la cual se le pasa un objeto {}, con la información
    $.ajaxSetup({async: false});
    $.ajax({
        type: "POST", // la variable type guarda el tipo de la peticion GET,POST,..
        url: "/paquetes/consultar_paquete_editar", //url guarda la ruta hacia donde se hace la peticion
        data: data, // data recive un objeto con la informacion que se enviara al servidor
        success: function (datos) { //success es una funcion que se utiliza si el servidor retorna informacion
            console.log(datos);
            respuesta = datos;
        }
    });
    $.ajaxSetup({async: true});
    return respuesta;
}

function actualizar_paquete(data) {
    let respuesta = [];
    //se utiliza $.ajax(), a la cual se le pasa un objeto {}, con la información
    $.ajaxSetup({async: false});
    $.ajax({
        type: "POST", // la variable type guarda el tipo de la peticion GET,POST,..
//    contentType: 'multipart/form-data',
        url: "/paquetes/actualizar", //url guarda la ruta hacia donde se hace la peticion
        data: data, // data recive un objeto con la informacion que se enviara al servidor
        success: function (datos) { //success es una funcion que se utiliza si el servidor retorna informacion
            console.log(datos);
            respuesta = JSON.parse(datos);
        }
    });
    $.ajaxSetup({async: true});
    return respuesta;
}