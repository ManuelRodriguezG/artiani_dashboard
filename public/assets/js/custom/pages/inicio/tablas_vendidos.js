// On document ready
KTUtil.onDOMContentLoaded(function () {
    consultar_productos_visitados();
});

var KTAppEcommerceVisitados = function () {
    // Shared variables
    var table;
    var datatable;

    // Private functions
    var initDatatable = function () { 
        // Init datatable --- more info on datatables: https://datatables.net/manual/
        datatable = $(table).DataTable({
            "info": false,
            'order': [],
            'pageLength': 10,
            'columnDefs': [
                {orderable: false, targets: 0}, // Disable ordering on column 0 (checkbox)
                {orderable: false, targets: 3} // Disable ordering on column 7 (actions)
            ]
        });

        // Re-init functions on datatable re-draws
        datatable.on('draw', function () {
            handleDeleteRows();
        });
    }



    // Public methods
    return {
        init: function () {
            table = document.querySelector('#tabla_productos_mas_visitados');

            if (!table) {
                return;
            }

            initDatatable();
        }
    };
}();

function consultar_productos_visitados() {
    let data = "";
    let codigo_producto = "";
    let productos = listar_productos_visitados(data);
    console.log(productos);
    productos = JSON.parse(productos);
    if (productos.error == false) {
        productos.depurar.map(function (producto) {
            
            codigo_producto += `
                        <tr>
                            <td class="text-end pe-0">
                                <span class="text-gray-600 fw-bold fs-6">${producto.id_producto}</span>
                            </td>
                            <td class="text-end pe-0">
                                <span class="text-gray-600 fw-bold fs-6">${producto.cantidad}</span>
                            </td>
                            <td class="text-end pe-0">
                                <span class="text-gray-600 fw-bold fs-6">${producto.tipo}</span>
                            </td>
                            <td class="text-end pe-0">
                                <span class="text-gray-800 fw-bold  mb-1 fs-6">
                                    <a href="./producto/editar/${producto.id_producto}">${producto.nombre}</a>
                                </span>
                            </td>
                        </tr>
                        
            `;
        });
        console.log(codigo_producto);
        $("#body_productos_visitados").html(codigo_producto);
        KTMenu.createInstances();
        KTAppEcommerceVisitados.init();
    }

}


