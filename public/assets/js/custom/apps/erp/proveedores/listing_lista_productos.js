"use strict";

// Class definition
var KTAppEcommerceSalesListing = function () {
    // Shared variables
    var table;
    var datatable;
    var flatpickr;
    var minDate, maxDate;

    // Private functions
    var initDatatable = function () {
        // Init datatable --- more info on datatables: https://datatables.net/manual/
        datatable = $(table).DataTable({
            "info": false,
            'order': [],
            'pageLength': 25,
            aLengthMenu: [[25, 50, 100, 200, -1], [25, 50, 100, 200, "All"]]
        });

        // Re-init functions on datatable re-draws
        datatable.on('draw', function () {
            handleDeleteRows();
        });
    }

    // Init flatpickr --- more info :https://flatpickr.js.org/getting-started/
    var initFlatpickr = () => {
        const element = document.querySelector('#kt_ecommerce_sales_flatpickr');
        flatpickr = $(element).flatpickr({
            altInput: true,
            altFormat: "d/m/Y",
            dateFormat: "Y-m-d",
            mode: "range",
            onChange: function (selectedDates, dateStr, instance) {
                handleFlatpickr(selectedDates, dateStr, instance);
            },
        });
    }

    // Search Datatable --- official docs reference: https://datatables.net/reference/api/search()
    var handleSearchDatatable = () => {
        const filterSearch = document.querySelector('[data-kt-ecommerce-order-filter="search"]');
        filterSearch.addEventListener('keyup', function (e) {
            console.log(e.target.value);
            datatable.search(e.target.value).draw();
            KTMenu.createInstances();
            focusOutTotal();
            calcularTotal();
        });
    }

    // Handle status filter dropdown
    var handleStatusFilter = () => {
        const filterStatus = document.querySelector('[data-kt-ecommerce-order-filter="status"]');
        $(filterStatus).on('change', e => {
            let value = e.target.value;
            if (value === 'all') {
                value = '';
            }
            datatable.column(3).search(value).draw();
            KTMenu.createInstances();
            focusOutTotal();
            calcularTotal();
        });
    }

    // Handle flatpickr --- more info: https://flatpickr.js.org/events/
    var handleFlatpickr = (selectedDates, dateStr, instance) => {
        minDate = selectedDates[0] ? new Date(selectedDates[0]) : null;
        maxDate = selectedDates[1] ? new Date(selectedDates[1]) : null;

        // Datatable date filter --- more info: https://datatables.net/extensions/datetime/examples/integration/datatables.html
        // Custom filtering function which will search data in column four between two values
        $.fn.dataTable.ext.search.push(
                function (settings, data, dataIndex) {
                    var min = minDate;
                    var max = maxDate;
                    var dateAdded = new Date(moment($(data[5]).text(), 'DD/MM/YYYY'));
                    var dateModified = new Date(moment($(data[6]).text(), 'DD/MM/YYYY'));

                    if (
                            (min === null && max === null) ||
                            (min === null && max >= dateModified) ||
                            (min <= dateAdded && max === null) ||
                            (min <= dateAdded && max >= dateModified)
                            ) {
                        return true;
                    }
                    return false;
                }
        );
        datatable.draw();
    }

    // Handle clear flatpickr
    var handleClearFlatpickr = () => {
        const clearButton = document.querySelector('#kt_ecommerce_sales_flatpickr_clear');
        clearButton.addEventListener('click', e => {
            flatpickr.clear();
        });
    }

    // Delete cateogry
    var handleDeleteRows = () => {
        // Select all delete buttons
        const deleteButtons = table.querySelectorAll('[data-kt-ecommerce-order-filter="delete_row"]');

        deleteButtons.forEach(d => {
            // Delete button on click
            d.addEventListener('click', function (e) {
                e.preventDefault();

                // Select parent row
                const parent = e.target.closest('tr');

                // Get category name
                const orderID = parent.querySelector('[data-kt-ecommerce-order-filter="order_id"]').innerText;

                // SweetAlert2 pop up --- official docs reference: https://sweetalert2.github.io/
                Swal.fire({
                    text: "Are you sure you want to delete order: " + orderID + "?",
                    icon: "warning",
                    showCancelButton: true,
                    buttonsStyling: false,
                    confirmButtonText: "Yes, delete!",
                    cancelButtonText: "No, cancel",
                    customClass: {
                        confirmButton: "btn fw-bold btn-danger",
                        cancelButton: "btn fw-bold btn-active-light-primary"
                    }
                }).then(function (result) {
                    if (result.value) {
                        Swal.fire({
                            text: "You have deleted " + orderID + "!.",
                            icon: "success",
                            buttonsStyling: false,
                            confirmButtonText: "Ok, got it!",
                            customClass: {
                                confirmButton: "btn fw-bold btn-primary",
                            }
                        }).then(function () {
                            // Remove current row
                            datatable.row($(parent)).remove().draw();
                        });
                    } else if (result.dismiss === 'cancel') {
                        Swal.fire({
                            text: orderID + " was not deleted.",
                            icon: "error",
                            buttonsStyling: false,
                            confirmButtonText: "Ok, got it!",
                            customClass: {
                                confirmButton: "btn fw-bold btn-primary",
                            }
                        });
                    }
                });
            })
        });
    }


    // Public methods
    return {
        init: function () {
            table = document.querySelector('#kt_ecommerce_sales_table');

            if (!table) {
                return;
            }

            initDatatable();
            handleSearchDatatable();
            handleStatusFilter();
            handleDeleteRows();
        }
    };
}();

// On document ready
KTUtil.onDOMContentLoaded(function () {

});

$(document).ready(function () {

    let id_lista = obtener_id_lista();
    let data = {
        id_lista: id_lista
    }
    obtener_lista_proveedor(data);
});

$('#kt_ecommerce_sales_table').on('draw.dt', function () {
    focusOutTotal();
});

function focusOutTotal() {
    $(".cantidad").each(function () {
        $(this).on("focusout", function () {
            console.log($(this)[0].id);
            console.log($("#checkbox-" + $(this)[0].id));
            if ($(this).val()) {
                $("#checkbox-" + $(this)[0].id)[0].checked = true;
                calcularTotal();
            }
        })
    });
}

function calcularTotal() {
    let productos_orden = [], info = {}, id_producto = 0, codigo = 0, nombre = null, cantidad = 0, precio = 0, total = 0, sku = "";
    $(".tr-item").each(function () {
        if ($("#" + $(this)[0].id + " .form-check-input")[0].checked == true) {
            cantidad = $("#" + $(this)[0].id + " .cantidad").val();
            precio = $("#" + $(this)[0].id + " .costo").text();
            total += cantidad * precio;
        }
    });
    $(".total_pedido").text('$' + total);
}

function generar_orden_de_compra() {
    console.log("generar orden");
    let productos_orden = [], info = {}, id_producto = 0, codigo = 0, nombre = null, cantidad = 0, precio = 0;
    $(".tr-item").each(function () {
        console.log($(this)[0].id);
        if ($("#" + $(this)[0].id + " .form-check-input")[0].checked == true) {
            id_producto = $(this)[0].id;
            codigo = $("#" + $(this)[0].id + " .sku").text();
            nombre = $("#" + $(this)[0].id + " .nombre").text();
            cantidad = $("#" + $(this)[0].id + " .cantidad").val();
            precio = $("#" + $(this)[0].id + " .costo").text();
            info = {
                id: id_producto,
                codigo: codigo,
                producto: nombre,
                cantidad: cantidad,
                precio: precio
            }
            productos_orden.push(info);
            console.log($("#" + $(this)[0].id + " .form-check-input")[0].checked);
        }
    });
    let data = {productos: productos_orden};
    console.log(productos_orden);
    let respuesta = orden_compra(data);

}

function orden_compra(data) {
    let respuesta = [];
    //se utiliza $.ajax(), a la cual se le pasa un objeto {}, con la información
    $.ajaxSetup({async: false});
    $.ajax({
        type: "POST", // la variable type guarda el tipo de la peticion GET,POST,..
//    contentType: 'multipart/form-data',
        url: "/proveedor/generar_orden_de_compra", //url guarda la ruta hacia donde se hace la peticion
        data: data, // data recive un objeto con la informacion que se enviara al servidor
        success: function (datos) { //success es una funcion que se utiliza si el servidor retorna informacion
            respuesta = JSON.parse(datos);
        }
    });
    $.ajaxSetup({async: true});
    return respuesta;
}

function item_venta(venta) {
    let code = `
        <tr id="tr-item-${venta.sku.trim().replace(" ","")}" class="tr-item">
                            <!--begin::Checkbox-->
                            <td>
                              <div class="form-check form-check-sm form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox" id="checkbox-${venta.sku.trim().replace(" ","")}"/>
                              </div>
                            </td>
                            <!--end::Checkbox-->
                            <!--begin::Customer=-->
                            <td>
                              <div class="d-flex align-items-center">
                            
                                <div class="ms-5 sku">${venta.sku.trim()}
                                </div>
                              </div>
                            </td>
                            <!--end::Customer=-->
                            <!--begin::Category=-->
                            <td>
                              <div class="d-flex align-items-center">
                                <!--begin::Thumbnail-->
                                <a href="#" class="symbol symbol-50px">
                                  <span class="symbol-label" style="background-image:url('${venta.url_imagen}');"></span>
                                </a>
                                <!--end::Thumbnail-->
                                <div class="ms-5">
                                  <!--begin::Title-->
                                  <a href="/producto/editar/${venta.id_producto}" class="text-gray-800 text-hover-primary fs-5 fw-bold nombre" data-kt-ecommerce-product-filter="product_name">${venta.nombre}</a>
                                  <!--end::Title-->
                                </div>
                              </div>
                            </td>
                            <!--end::Category=-->
                            
                            
                            <!--begin::Total=-->
                            <td class="text-end pe-0">
                              <span class="fw-bold">${venta.existencia}</span>
                            </td>
                            <!--end::Total=-->
                            <!--begin::Total=-->
                            <td class="text-end pe-0">
                              <span class="fw-bold costo">${venta.costo}</span>
                            </td>
                            <!--end::Total=-->
    <!--begin::Total=-->
                            <td class="text-end pe-0">
                              <span class="fw-bold piezas_por_caja">${venta.piezas_por_caja}</span>
                            </td>
                            <!--end::Total=-->
    <!--begin::Total=-->
                            <td class="text-end pe-0">
                              <span class="fw-bold">${venta.rotacion}</span>
                            </td>
                            <!--end::Total=-->
                            <td class="text-end pe-0">
                              <input type="text" class="form-control  form-control-solid cantidad" id="${venta.sku.trim().replace(" ","")}"/>
                            </td>
                            <!--begin::Action=-->
                            <td class="text-end">
                              <a href="#" class="btn btn-sm btn-light btn-active-light-primary" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">Actions
                                <!--begin::Svg Icon | path: icons/duotune/arrows/arr072.svg-->
                                <span class="svg-icon svg-icon-5 m-0">
                                  <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                  <path d="M11.4343 12.7344L7.25 8.55005C6.83579 8.13583 6.16421 8.13584 5.75 8.55005C5.33579 8.96426 5.33579 9.63583 5.75 10.05L11.2929 15.5929C11.6834 15.9835 12.3166 15.9835 12.7071 15.5929L18.25 10.05C18.6642 9.63584 18.6642 8.96426 18.25 8.55005C17.8358 8.13584 17.1642 8.13584 16.75 8.55005L12.5657 12.7344C12.2533 13.0468 11.7467 13.0468 11.4343 12.7344Z" fill="currentColor" />
                                  </svg>
                                </span>
                                <!--end::Svg Icon--></a>
                              <!--begin::Menu-->
                              <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-125px py-4" data-kt-menu="true">
                                <!--begin::Menu item-->
                                <div class="menu-item px-3">
                                  <a href="/proveedor/lista_producto_editar/${venta.id_producto}" class="menu-link px-3">Editar</a>
                                </div>
                              </div>
                              <!--end::Menu-->
                            </td>
                            <!--end::Action=-->
                          </tr>
    `;
    return code;
}

function obtener_lista_proveedor(data) {
    let respuesta = consultar_lista_proveedor(data);
    console.log(respuesta);
    if (respuesta.error == false) {
        ui_listas_proveedores(respuesta.depurar);
    }
}

function obtener_productos_pedido() {
    $(".tr-item").each(function () {

        console.log($("#" + $(this)[0].id + " .form-check-input")[0].checked);
    })
}

function obtener_id_lista() {
    let router = url_router();
    let id_producto = router.parametros[0];
    return id_producto;
}

function ui_listas_proveedores(ventas) {
    let codigo_ventas = "";
    Object.entries(ventas).forEach(function (row) {
        codigo_ventas += item_venta(row[1]);
    });
    $("#body-ventas").html(codigo_ventas);
    KTMenu.createInstances();
    KTAppEcommerceSalesListing.init();
    focusOutTotal();
}
