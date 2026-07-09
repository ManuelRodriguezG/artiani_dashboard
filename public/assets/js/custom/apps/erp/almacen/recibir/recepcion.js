function consultar_recepcion_almacen(id_recepcion_almacen) {
    let respuesta = [];
    $.ajaxSetup({async: false});
    $.ajax({
        type: "POST",
        url: "/almacen/consultar_recepcion",
        data: {
            id_recepcion_almacen: id_recepcion_almacen
        },
        success: function (datos) {
            respuesta = JSON.parse(datos);
        }
    });
    $.ajaxSetup({async: true});
    return respuesta;
}

function guardar_recepcion_almacen(id_recepcion_almacen, partidas) {
    let respuesta = [];
    $.ajaxSetup({async: false});
    $.ajax({
        type: "POST",
        url: "/almacen/guardar_recepcion",
        data: {
            id_recepcion_almacen: id_recepcion_almacen,
            partidas: JSON.stringify(partidas)
        },
        success: function (datos) {
            respuesta = JSON.parse(datos);
        }
    });
    $.ajaxSetup({async: true});
    return respuesta;
}
