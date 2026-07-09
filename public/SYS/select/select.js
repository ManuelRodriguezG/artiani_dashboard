function crear_opcion(opciones) {
    let opcion = $('<option>', opciones)[0];
    return opcion;
}

function rellenar_select(identificador, data) {
    let opciones_metodos_pago = "";
    $(identificador).html();
    data.map(function (fila) {
        opciones_metodos_pago = crear_opcion(fila);
        console.log(opciones_metodos_pago);
        $(identificador).append(opciones_metodos_pago);
    });
}