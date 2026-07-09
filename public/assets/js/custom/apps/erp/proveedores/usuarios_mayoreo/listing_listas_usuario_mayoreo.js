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
            'pageLength': 10,
            'columnDefs': [
                {orderable: false, targets: 0}, // Disable ordering on column 0 (checkbox)
                {orderable: false, targets: 5}, // Disable ordering on column 7 (actions)
            ]
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
            datatable.search(e.target.value).draw();
            KTMenu.createInstances();
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
            initFlatpickr();
            handleSearchDatatable();
            handleStatusFilter();
            handleDeleteRows();
            handleClearFlatpickr();
        }
    };
}();

// On document ready
KTUtil.onDOMContentLoaded(function () {
    KTAppEcommerceSalesListing.init();
});

$(document).ready(function () {
    let id_paquete = obtener_id_lista();
    console.log(id_paquete);
    let data = {
        "id_usuario": id_paquete
    }
    obtener_listas_proveedores(data);
})

function item_venta(venta, listas_usuario) {
    let asignado = false;
    if (listas_usuario.includes(venta.id_lista_mayoreo)) {
        console.log('contiene');
        asignado = true;
    }
    let code = `
        <tr>
                            <!--begin::Checkbox-->
                            <td>
                              <div class="form-check form-check-sm form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox" value="1" />
                              </div>
                            </td>
                            <!--end::Checkbox-->
                            <!--begin::Order ID=-->
                            <td data-kt-ecommerce-order-filter="order_id">
                              <a href="../../demo1/dist/apps/ecommerce/sales/details.html" class="text-gray-800 text-hover-primary fw-bold">${venta.id_lista_mayoreo}</a>
                            </td>
                            <!--end::Order ID=-->
                            <!--begin::Customer=-->
                            <td>
                              <div class="d-flex align-items-center">
                            
                                <div class="ms-5">
                                  <!--begin::Title-->
                                  ${venta.proveedor}
                                  <!--end::Title-->
                                </div>
                              </div>
                            </td>
                            <!--end::Customer=-->
                            <!--begin::Total=-->
                            <td class="text-end pe-0">
                              <span class="fw-bold">${venta.tipo_lista_mayoreo}</span>
                            </td>
                            <!--end::Total=-->
                            <!--begin::Status=-->
                            <td class="text-end pe-0" data-order="Completed">
                              <!--begin::Badges-->
                              ${asignado == false ? '<div class="badge badge-light-primary">No asignada</div>' : '<div class="badge badge-light-success">Asignada</div>'}
                              <!--end::Badges-->
                            </td>
                            <!--end::Status=-->
                            
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
                                ${asignado == false ? '<!--begin::Menu item--><div class="menu-item px-3"><a href="javascript:void(0);" onclick="cambiar_estatus_lista(this);" class="menu-link px-3" data-id-lista-mayoreo="' + venta.id_lista_mayoreo + '" data-accion="asignar">Asignar</a></div><!--end::Menu item-->' : '<!--begin::Menu item--><div class="menu-item px-3"><a  href="javascript:void(0);" onclick="cambiar_estatus_lista(this);" class="menu-link px-3" data-id-lista-mayoreo="' + venta.id_lista_mayoreo + '" data-accion="quitar_asignacion">No asignar</a></div><!--end::Menu item-->'}
                              </div>
                              <!--end::Menu-->
                            </td>
                            <!--end::Action=-->
                          </tr>
    `;
    return code;
}

function obtener_id_lista() {
    let router = url_router();
    console.log(router);
    let id_paquete = router.parametros[0];
    return id_paquete;
}

async function obtener_listas_proveedores() {
    let id_paquete = obtener_id_lista();
    console.log(id_paquete);
    let data = {
        "id_lista_mayoreo": id_paquete
    }
    let respuesta = await consultar_listas_proveedores(data);
    console.log(respuesta);
    if (respuesta.listas_mayoreo.error == false) {
        ui_listas_proveedores(respuesta.listas_mayoreo.depurar, respuesta.listas_usuario);
    }
}

async function cambiar_estatus_lista(elemento) {
    console.log(elemento);
    console.log($(elemento));
    let id_usuario = obtener_id_lista();
    let estatus = $(elemento).attr("data-accion");
    let id_lista_mayoreo = $(elemento).attr("data-id-lista-mayoreo");

    let data = {
        "accion": estatus,
        "id_lista_mayoreo": id_lista_mayoreo,
        "id_usuario": id_usuario
    };
    let respuesta = await actualizar_estatus_lista_mayoreo_usuario_mayoreo(data);
    console.log(respuesta);
    if (respuesta.error == false) {
        location.reload();
    }
}

function ui_listas_proveedores(ventas, listas_usuario) {
    let codigo_ventas = "";
    Object.entries(ventas).forEach(function (row) {
        codigo_ventas += item_venta(row[1], listas_usuario);
    });
    $("#body-ventas").html(codigo_ventas);
    KTMenu.createInstances();
}
