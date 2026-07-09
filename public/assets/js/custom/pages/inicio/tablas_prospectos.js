// On document ready
KTUtil.onDOMContentLoaded(function () {
//    KTAppEcommerceProducts.init();
    consultar_prospectos();
});

var KTAppEcommerceProspectos = function () {
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
                {orderable: false, targets: 2} // Disable ordering on column 7 (actions)
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
            table = document.querySelector('#tabla_prospectos');

            if (!table) {
                return;
            }

            initDatatable();
        }
    };
}();

function consultar_prospectos() {
    let data = "";
    let codigo_producto = "";
    let productos = listar_prospectos(data);
    console.log(productos);
    productos = JSON.parse(productos);
    if (productos.error == false) {
        productos.depurar.map(function (producto) {
            
            codigo_producto += `
                        <tr>
                            <td class="text-end pe-0">
                                <span class="text-gray-600 fw-bold fs-6">${producto.id_prospecto}</span>
                            </td>
                            <td class="text-end pe-0">
                                <span class="text-gray-600 fw-bold fs-6">${producto.nombres}</span>
                            </td>
                            <td class="text-end pe-0">
                                <span class="text-gray-600 fw-bold fs-6">
                                    <a href="./prospectos/editar_carrito/${producto.id_prospecto}">${producto.fch_r}</a>
                                </span>
                            </td>
                        </tr>
            `;
        });
        $("#body_prospectos").html(codigo_producto);
        KTMenu.createInstances();
        KTAppEcommerceProspectos.init();
    }

}


