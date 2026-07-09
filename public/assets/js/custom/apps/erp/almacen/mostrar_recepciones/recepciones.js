function consultar_recepciones_almacen() {
    let respuesta = [];
    $.ajaxSetup({async: false});
    $.ajax({
        type: "POST",
        url: "/almacen/obtener_recepciones",
        success: function (datos) {
            respuesta = JSON.parse(datos);
        }
    });
    $.ajaxSetup({async: true});
    return respuesta;
}
