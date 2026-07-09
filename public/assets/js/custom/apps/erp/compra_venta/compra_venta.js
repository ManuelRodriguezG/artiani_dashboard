function consultar_unidades_compra_venta() {
    let unidades_compra_venta = [];
    $.ajaxSetup({async: false});
    $.ajax({
        type: "POST", // la variable type guarda el tipo de la peticion GET,POST,..
        url: "/Compra_venta/consultar_unidades_compra_venta", //url guarda la ruta hacia donde se hace la peticion
//    data: data, // data recive un objeto con la informacion que se enviara al servidor
        success: function (datos) { //success es una funcion que se utiliza si el servidor retorna informacion
            let data = JSON.parse(datos);
            unidades_compra_venta = data;
//            
        }
    });
    $.ajaxSetup({async: true});
    return unidades_compra_venta;
}