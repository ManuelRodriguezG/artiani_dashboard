

function estatus_pedidos_listar() {
    let estatus = [];
    $.ajaxSetup({async: false});
    $.ajax({
        type: "POST", // la variable type guarda el tipo de la peticion GET,POST,..
        url: "/ventas/estatus_pedido", //url guarda la ruta hacia donde se hace la peticion
//    data: data, // data recive un objeto con la informacion que se enviara al servidor
        success: function (datos) { //success es una funcion que se utiliza si el servidor retorna informacion
            let data = JSON.parse(datos);
            estatus = data;
//            
        }
    });
    $.ajaxSetup({async: true});
    return estatus;
}