function crear_producto(data) {
    let respuesta = [];
    //se utiliza $.ajax(), a la cual se le pasa un objeto {}, con la información
    $.ajaxSetup({async: false});
    $.ajax({
        type: "POST", // la variable type guarda el tipo de la peticion GET,POST,..
        url: "/producto/registrar", //url guarda la ruta hacia donde se hace la peticion
        data: data, // data recive un objeto con la informacion que se enviara al servidor
        success: function (datos) { //success es una funcion que se utiliza si el servidor retorna informacion
            respuesta = JSON.parse(datos);
        }
    })
    $.ajaxSetup({async: true});
    return respuesta;
}

function consulta_busqueda(data) {
    let respuesta = [];
    //se utiliza $.ajax(), a la cual se le pasa un objeto {}, con la información
    $.ajaxSetup({async: false});
    $.ajax({
        type: "POST", // la variable type guarda el tipo de la peticion GET,POST,..
        url: "/producto/consultar_busqueda", //url guarda la ruta hacia donde se hace la peticion
        data: data, // data recive un objeto con la informacion que se enviara al servidor
        success: function (datos) { //success es una funcion que se utiliza si el servidor retorna informacion
            respuesta = JSON.parse(datos);
        }
    });
    $.ajaxSetup({async: true});
    return respuesta;
}

function consultar_producto_editar(data) {
    let respuesta = [];
    //se utiliza $.ajax(), a la cual se le pasa un objeto {}, con la información
    $.ajaxSetup({async: false});
    $.ajax({
        type: "POST", // la variable type guarda el tipo de la peticion GET,POST,..
        url: "/proveedor/consultar_producto_editar", //url guarda la ruta hacia donde se hace la peticion
        data: data, // data recive un objeto con la informacion que se enviara al servidor
        success: function (datos) { //success es una funcion que se utiliza si el servidor retorna informacion
            respuesta = datos;
        }
    });
    $.ajaxSetup({async: true});
    return respuesta;
}

function consultar(data) {
    let respuesta = [];
    //se utiliza $.ajax(), a la cual se le pasa un objeto {}, con la información
    $.ajaxSetup({async: false});
    $.ajax({
        type: "POST", // la variable type guarda el tipo de la peticion GET,POST,..
        url: "/producto/consultar", //url guarda la ruta hacia donde se hace la peticion
        data: data, // data recive un objeto con la informacion que se enviara al servidor
        success: function (datos) { //success es una funcion que se utiliza si el servidor retorna informacion
            respuesta = datos;
        }
    });
    $.ajaxSetup({async: true});
    return respuesta;
}

function listar(data) {
    let respuesta = [];
    //se utiliza $.ajax(), a la cual se le pasa un objeto {}, con la información
    $.ajaxSetup({async: false});
    $.ajax({
        type: "POST", // la variable type guarda el tipo de la peticion GET,POST,..
        url: "/producto/listar", //url guarda la ruta hacia donde se hace la peticion
        data: data, // data recive un objeto con la informacion que se enviara al servidor
        success: function (datos) { //success es una funcion que se utiliza si el servidor retorna informacion
            respuesta = datos;
        }
    });
    $.ajaxSetup({async: true});
    return respuesta;
}

function actualizar(data) {
    let respuesta = [];
    //se utiliza $.ajax(), a la cual se le pasa un objeto {}, con la información
    $.ajaxSetup({async: false});
    $.ajax({
        type: "POST", // la variable type guarda el tipo de la peticion GET,POST,..
//    contentType: 'multipart/form-data',
        url: "/proveedor/actualizar_lista_producto", //url guarda la ruta hacia donde se hace la peticion
        data: data, // data recive un objeto con la informacion que se enviara al servidor
        success: function (datos) { //success es una funcion que se utiliza si el servidor retorna informacion
            respuesta = JSON.parse(datos);
        }
    });
    $.ajaxSetup({async: true});
    return respuesta;
}

async function actualizar_producto_portada(data) {
    let respuesta = [];
    //se utiliza $.ajax(), a la cual se le pasa un objeto {}, con la información
//  $.ajaxSetup({async: false});
    respuesta = await fetch("/proveedor/actualizar_portada", {method: 'POST', body: data})
            .then((response) => response.json())
            .then((data) => {
                return data;
            });

//  $.ajaxSetup({async: true});
    return respuesta;
}