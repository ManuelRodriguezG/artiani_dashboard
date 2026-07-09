function consultar_listas_proveedores(data) {
    let respuesta = [];
    //se utiliza $.ajax(), a la cual se le pasa un objeto {}, con la información
    $.ajaxSetup({async: false});
    $.ajax({
        type: "POST", // la variable type guarda el tipo de la peticion GET,POST,..
        url: "/proveedor/listas_usuario_mayoreo_consultar", //url guarda la ruta hacia donde se hace la peticion
        data: data, // data recive un objeto con la informacion que se enviara al servidor
        success: function (datos) { //success es una funcion que se utiliza si el servidor retorna informacion
            console.log(datos);
            respuesta = JSON.parse(datos);
        }
    });
    $.ajaxSetup({async: true});
    return respuesta;
}

function actualizar_estatus_lista_mayoreo_usuario_mayoreo(data) {
    console.log(data);
    let respuesta = [];
    //se utiliza $.ajax(), a la cual se le pasa un objeto {}, con la información
    $.ajaxSetup({async: false});
    $.ajax({
        type: "POST", // la variable type guarda el tipo de la peticion GET,POST,..
        url: "/proveedor/actualizar_estatus_lista_mayoreo_usuario_mayoreo", //url guarda la ruta hacia donde se hace la peticion
        data: data, // data recive un objeto con la informacion que se enviara al servidor
        success: function (datos) { //success es una funcion que se utiliza si el servidor retorna informacion
            console.log(datos);
            respuesta = JSON.parse(datos);
        }
    });
    $.ajaxSetup({async: true});
    return respuesta;
}

function consultar_venta_editar(data) {
    let respuesta = [];
    //se utiliza $.ajax(), a la cual se le pasa un objeto {}, con la información
    $.ajaxSetup({async: false});
    $.ajax({
        type: "POST", // la variable type guarda el tipo de la peticion GET,POST,..
        url: "/ventas/consulta_completa", //url guarda la ruta hacia donde se hace la peticion
        data: data, // data recive un objeto con la informacion que se enviara al servidor
        success: function (datos) { //success es una funcion que se utiliza si el servidor retorna informacion
            console.log(datos);
            respuesta = JSON.parse(datos);
        }
    });
    $.ajaxSetup({async: true});
    return respuesta;
}

function eliminar_venta() {

}

function crear_Venta() {

}

function actualizar_venta() {

}

