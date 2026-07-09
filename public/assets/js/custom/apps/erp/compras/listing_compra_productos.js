"use strict";

var KTAppEcommerceReportSales = function () {
    var t, e;
    return {
        init: function () {
            (t = document.querySelector("#kt_ecommerce_sales_table")) && (t.querySelectorAll("tbody tr").forEach((t => {
                const e = t.querySelectorAll("td")
                        , r = moment(e[0].innerHTML, "MMM DD, YYYY").format();
                e[0].setAttribute("data-order", r)
            }
            )),
                    e = $(t).DataTable({
                info: !1,
                order: [],
                pageLength: 10
            }),
                    (() => {
                        var t = moment().subtract(29, "days")
                                , e = moment()
                                , r = $("#kt_ecommerce_report_sales_daterangepicker");
                        function o(t, e) {
                            r.html(t.format("MMMM D, YYYY") + " - " + e.format("MMMM D, YYYY"))
                        }
                        r.daterangepicker({
                            startDate: t,
                            endDate: e,
                            ranges: {
                                Today: [moment(), moment()],
                                Yesterday: [moment().subtract(1, "days"), moment().subtract(1, "days")],
                                "Last 7 Days": [moment().subtract(6, "days"), moment()],
                                "Last 30 Days": [moment().subtract(29, "days"), moment()],
                                "This Month": [moment().startOf("month"), moment().endOf("month")],
                                "Last Month": [moment().subtract(1, "month").startOf("month"), moment().subtract(1, "month").endOf("month")]
                            }
                        }, o),
                                o(t, e)
                    }
                    )(),
                    (() => {
                        const e = "Sales Report";
                        new $.fn.dataTable.Buttons(t, {
                            buttons: [{
                                    extend: "copyHtml5",
                                    title: e
                                }, {
                                    extend: "excelHtml5",
                                    title: e
                                }, {
                                    extend: "csvHtml5",
                                    title: e
                                }, {
                                    extend: "pdfHtml5",
                                    title: e
                                }]
                        }).container().appendTo($("#kt_ecommerce_report_sales_export")),
                                document.querySelectorAll("#kt_ecommerce_report_sales_export_menu [data-kt-ecommerce-export]").forEach((t => {
                                    console.log(t);
                            t.addEventListener("click", (t => {
                                console.log(t);
                                t.preventDefault();
                                console.log(t);
                                const e = t.target.getAttribute("data-kt-ecommerce-export");
                                console.log(e);
                                document.querySelector(".dt-buttons .buttons-" + e).click();
                            }
                            ))
                        }
                        ))
                    }
                    )(),
                    document.querySelector('[data-kt-ecommerce-order-filter="search"]').addEventListener("keyup", (function (t) {
                e.search(t.target.value).draw()
            }
            )))
        }
    }
}();

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
            'pageLength': 10
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

function generar_orden_de_compra() {
    console.log("generar orden");
    let productos_orden = [], info = {}, id_producto = 0, codigo = 0, nombre = null, cantidad = 0, precio = 0;
    $(".tr-item").each(function () {
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
        <tr id="${venta.id_producto}" class="tr-item">
                           
                            <!--begin::Customer=-->
                            <td>
                              <div class="d-flex align-items-center">
                            
                                <div class="ms-5 sku">${venta.codigo}
                                </div>
                              </div>
                            </td>
                            <!--end::Customer=-->
                            <!--begin::Category=-->
                            <td>
                              <div class="d-flex align-items-center">
                               
                                <div class="ms-5">
                                  <!--begin::Title-->
                                  <a href="/producto/editar/${venta.producto}" class="text-gray-800 text-hover-primary fs-5 fw-bold nombre" data-kt-ecommerce-product-filter="product_name">${venta.producto}</a>
                                  <!--end::Title-->
                                </div>
                              </div>
                            </td>
                            <!--end::Category=-->
                            
                            
                            <!--begin::Total=-->
                            <td class="text-end pe-0">
                              <span class="fw-bold">${venta.cantidad}</span>
                            </td>
                            <!--end::Total=-->
                            <!--begin::Total=-->
                            <td class="text-end pe-0">
                              <span class="fw-bold costo">${venta.precio}</span>
                            </td>
                            <!--end::Total=-->
   
                            
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
//    KTAppEcommerceSalesListing.init();
    KTAppEcommerceReportSales.init();
}
