// On document ready
KTUtil.onDOMContentLoaded(function () {
//    KTAppEcommerceProducts.init();
    consultar_pedidos_nuevos();
});

var KTAppEcommercePedidos = function () {
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
            'rowspan':0,
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
            table = document.querySelector('#pedidos_nuevos');

            if (!table) {
                return;
            }

            initDatatable();
        }
    };
}();

function consultar_pedidos_nuevos() {
    let data = "";
    let codigo_producto = "";
    let productos = listar_pedidos_nuevos(data);
    console.log(productos);
    productos = JSON.parse(productos);
    if (productos.error == false) {
        productos.depurar.map(function (producto) {
            
            codigo_producto += `
                        <tr>
                            <td class="text-end pe-0">
                                <span class="text-gray-600 fw-bold fs-6">${producto.id_pedido}</span>
                            </td>
                            <td class="text-end pe-0">
                                <span class="text-gray-600 fw-bold fs-6">${producto.nombres}</span>
                            </td>
                            <td class="text-end pe-0">
                                <span class="text-gray-600 fw-bold fs-6">${producto.total}</span>
                            </td>
                            <td class="text-end pe-0">
                                <span class="text-gray-800 fw-bold text-hover-primary mb-1 fs-6">
                                    <a href="./ventas/editar/${producto.id_pedido}">${producto.fch_r}</a>
                                </span>
                            </td>
                        </tr>
                        
            `;
        });
        $("#body_pedidos_nuevos").html(codigo_producto);
        KTMenu.createInstances();
        KTAppEcommercePedidos.init();
    }

}


