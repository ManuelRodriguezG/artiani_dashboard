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
    obtener_inventarios();
    KTAppEcommerceSalesListing.init();
});

function porcentaje_nuevo(elemento) {
    console.log(elemento.value);
    console.log(elemento.id);
    console.log($(elemento).attr("costo_nuevo"));
    let precio_sugerido = elemento.value;
    let costo_nuevo = $(elemento).attr("costo_nuevo");
    let nuevo_porcentaje = 0;
    let diferencia = precio_sugerido - costo_nuevo;

    if (precio_sugerido != 0) {
        nuevo_porcentaje = diferencia / precio_sugerido;
        $("#nuevo_porcentaje_" + elemento.id).text(nuevo_porcentaje);
        $("#nueva_ganancia_" + elemento.id).text(diferencia);
    }
}

function item_venta(venta) {
    let diferencia_nueva = venta.precio_sugerido - venta.costo_nuevo;
    console.log(diferencia_nueva);
    let diferencia_positiva = diferencia_nueva < 0 ? diferencia_nueva * -1 : diferencia_nueva;
    console.log(diferencia_positiva);
    let nuevo_porcentaje = (diferencia_positiva / venta.precio_sugerido) * 100;
    console.log(nuevo_porcentaje);
    let diferencia = venta.diferencia < 0 ? '<span class="fw-bold text-primary ms-3">' + venta.diferencia + '</span>' : '<span class="fw-bold text-danger ms-3">' + venta.diferencia + '</span>';
    let porcentaje_actual = (venta.precio_actual - venta.costo_anterior) / venta.precio_actual;
    let code = `
        <tr>
                            <!--begin::Checkbox-->
                            <td>
                              <div class="form-check form-check-sm form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox" value="1" />
                              </div>
                            </td>
                            <!--end::Checkbox-->
                            <!--begin::Status=-->
                            <td class="pe-0 text-center" data-order="Completed">
                              <!--begin::Badges-->
                              ${venta.fch_m}
                              <!--end::Badges-->
                            </td>
                            <!--end::Status=-->
                            <td>
                                <div class="d-flex align-items-center">
                                    <!--begin::Thumbnail-->

                                      <span class="symbol-label" style="background-image:url('${venta.url_imagen}');width: 50px;height: 50px;background-size: 100%;"></span>

                                    <!--end::Thumbnail-->
                                    <div class="ms-5">
                                      <!--begin::Title-->

                                        ${venta.nombre}

                                      <!--end::Title-->
                                    </div>
                                  </div>
                            </td>  
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
                            <td class="text-center pe-0">
                              <span class="fw-bold">${venta.sku}</span>
                            </td>
                            <!--end::Total=-->
                            <!--begin::Status=-->
                            <td class="text-end pe-0" data-order="Completed">
                              <!--begin::Badges-->
                              ${venta.costo_anterior}
                              <!--end::Badges-->
                            </td>
                            <!--end::Status=-->
                            <!--begin::Status=-->
                            <td class="text-end pe-0" data-order="Completed">
                              <!--begin::Badges-->
                              ${venta.costo_nuevo}
                              <!--end::Badges-->
                            </td>
                            <!--end::Status=-->
                            <!--begin::Status=-->
                            <td class="text-end pe-0" data-order="Completed">
                              <!--begin::Badges-->
                              ${diferencia}
                              <!--end::Badges-->
                            </td>
                            <!--end::Status=-->
                            <!--begin::Status=-->
                            <td class="text-end pe-0" data-order="Completed">
                              <!--begin::Badges-->
                              ${venta.precio_sugerido}
                              <!--end::Badges-->
                            </td>
                            <!--end::Status=-->
                            
                            <!--begin::Status=-->
                            <td class="text-end pe-0" data-order="Completed">
                              <!--begin::Badges-->
                              ${venta.porcentaje_cambio}
                              <!--end::Badges-->
                            </td>
                            <!--end::Status=-->
                            <!--begin::Status=-->
                            <td class="text-end pe-0" data-order="Completed">
                              <!--begin::Badges-->
                              ${venta.precio_actual - venta.costo_anterior}
                              <!--end::Badges-->
                            </td>
                            <!--end::Status=-->
                            <!--begin::Status=-->
                            <td class="text-end pe-0" data-order="Completed">
                              <!--begin::Badges-->
                              ${venta.precio_actual}
                              <!--end::Badges-->
                            </td>
                            <!--end::Status=-->
                            <!--begin::Status=-->
                            <td class="text-end pe-0" data-order="Completed">
                              <!--begin::Badges-->
                              ${porcentaje_actual}
                              <!--end::Badges-->
                            </td>
                            <!--end::Status=-->
                            
                            <!--begin::Status=-->
                            <td class="text-end pe-0" data-order="Completed">
                              <!--begin::Input cambio precio-->
                              <input value="${venta.precio_sugerido}" type="text" class="form-control form-control-solid" costo_nuevo="${venta.costo_nuevo}" id="${venta.sku + "-" + venta.id_proveedor}" name="${venta.sku + "-" + venta.id_proveedor}" onkeyup="porcentaje_nuevo(this)">
                              <!--end::Badges-->
                            </td>
                            <!--end::Status=-->
                            <!--begin::Status=-->
                            <td class="text-end pe-0" data-order="Completed">
                              <!--begin::Badges-->
                              <p id="nuevo_porcentaje_${venta.sku + "-" + venta.id_proveedor}">${nuevo_porcentaje}</p>
                              <!--end::Badges-->
                            </td>
                            <!--end::Status=-->
                            <!--begin::Nueva ganancia=-->
                            <td class="text-end pe-0" data-order="Completed">
                              <!--begin::Badges-->
                              
                              <p id="nueva_ganancia_${venta.sku + "-" + venta.id_proveedor}">${venta.precio_sugerido - venta.costo_nuevo}</p>
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
                                <!--begin::Menu item-->
                                <div class="menu-item px-3">
                                  <a href="javascript:void(0);" onclick="accion_historial_costo(this);" class="menu-link px-3" accion="afectar" sku="${venta.sku}" id_historial="${venta.id_historial}" id_proveedor="${venta.id_proveedor}" name="${venta.sku + "-" + venta.id_proveedor}">Afectar</a>
                                </div>
                                <!--end::Menu item-->
                                <!--begin::Menu item-->
                                <div class="menu-item px-3">
                                  <a href="javascript:void(0);" onclick="accion_historial_costo(this);" accion="quitar_revision" class="menu-link px-3" id_historial="${venta.id_historial}">Quitar revision</a>
                                </div>
                                <!--end::Menu item-->
                              </div>
                              <!--end::Menu-->
                            </td>
                            <!--end::Action=-->
                          </tr>
    `;
    return code;
}

function obtener_inventarios() {
    let respuesta = consultar_proveedor_pedidos();
    console.log(respuesta);
    if (respuesta.error == false) {
        ui_inventarios(respuesta.depurar);
    }
}

function accion_historial_costo(elemento) {
    console.log($(elemento));
    let accion = $(elemento).attr("accion");
    let data = {};
    if (accion == "afectar") {
        let id_proveedor = $(elemento).attr("id_proveedor");
        let id_historial = $(elemento).attr("id_historial");
        let sku = $(elemento).attr("sku");
        let name = $(elemento).attr("name");
        let nuevo_precio = $("#" + name).val();
        data = {
            "accion": accion,
            "nuevo_precio": nuevo_precio,
            "sku": sku,
            "id_proveedor": id_proveedor,
            "id_historial": id_historial
        };
    } else if (accion == "quitar_revision") {
        let id_historial = $(elemento).attr("id_historial");
        data = {
            "accion": accion,
            "id_historial": id_historial

        };
    }
    console.log(data);
    let respuesta = generar_accion_historial_costos(data);
    if (respuesta.error == false) {
        location.reload();
//        ui_inventarios(respuesta.depurar);
    }
}

function ui_inventarios(ventas) {
    let codigo_ventas = "";
    Object.entries(ventas).forEach(function (row) {
        codigo_ventas += item_venta(row[1]);
    });
    $("#body-ventas").html(codigo_ventas);
    KTMenu.createInstances();
}
