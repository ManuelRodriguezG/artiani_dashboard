"use strict";

var KTAlmacenRecepcionesListing = function () {
    var table;
    var datatable;
    var flatpickr;
    var minDate, maxDate;

    var initDatatable = function () {
        datatable = $(table).DataTable({
            "info": false,
            "order": [],
            "pageLength": 10,
            "columnDefs": [
                {orderable: false, targets: 0},
                {orderable: false, targets: 10}
            ]
        });
    };

    var initFlatpickr = function () {
        const element = document.querySelector("#kt_almacen_recepciones_flatpickr");
        flatpickr = $(element).flatpickr({
            altInput: true,
            altFormat: "d/m/Y",
            dateFormat: "Y-m-d",
            mode: "range",
            onChange: function (selectedDates, dateStr, instance) {
                handleFlatpickr(selectedDates, dateStr, instance);
            }
        });
    };

    var handleSearchDatatable = function () {
        const filterSearch = document.querySelector('[data-kt-almacen-recepcion-filter="search"]');
        filterSearch.addEventListener("keyup", function (e) {
            datatable.search(e.target.value).draw();
            KTMenu.createInstances();
        });
    };

    var handleStatusFilter = function () {
        const filterStatus = document.querySelector('[data-kt-almacen-recepcion-filter="status"]');
        $(filterStatus).on("change", function (e) {
            let value = e.target.value;
            if (value === "all") {
                value = "";
            }
            datatable.column(6).search(value).draw();
            KTMenu.createInstances();
        });
    };

    var handleFlatpickr = function (selectedDates, dateStr, instance) {
        minDate = selectedDates[0] ? new Date(selectedDates[0]) : null;
        maxDate = selectedDates[1] ? new Date(selectedDates[1]) : null;

        $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
            if (settings.nTable.id !== "kt_almacen_recepciones_table") {
                return true;
            }

            const fecha = $(data[9]).text().trim();
            const fechaAlerta = fecha ? new Date(fecha.replace(" ", "T")) : null;

            if (!fechaAlerta) {
                return true;
            }

            return (minDate === null && maxDate === null) ||
                    (minDate === null && fechaAlerta <= maxDate) ||
                    (minDate <= fechaAlerta && maxDate === null) ||
                    (minDate <= fechaAlerta && fechaAlerta <= maxDate);
        });
        datatable.draw();
    };

    var handleClearFlatpickr = function () {
        const clearButton = document.querySelector("#kt_almacen_recepciones_flatpickr_clear");
        clearButton.addEventListener("click", function () {
            flatpickr.clear();
            minDate = null;
            maxDate = null;
            datatable.draw();
        });
    };

    return {
        init: function () {
            table = document.querySelector("#kt_almacen_recepciones_table");
            if (!table) {
                return;
            }

            ui_obtener_recepciones_almacen();
            initDatatable();
            initFlatpickr();
            handleSearchDatatable();
            handleStatusFilter();
            handleClearFlatpickr();
        }
    };
}();

KTUtil.onDOMContentLoaded(function () {
    KTAlmacenRecepcionesListing.init();
});

function ui_obtener_recepciones_almacen() {
    let respuesta = consultar_recepciones_almacen();
    if (respuesta.error == false) {
        ui_recepciones_almacen(respuesta.depurar);
        return;
    }

    $("#body-recepciones").html("");
}

function ui_recepciones_almacen(recepciones) {
    let codigo = "";
    Object.entries(recepciones).forEach(function (row) {
        codigo += item_recepcion_almacen(row[1]);
    });
    $("#body-recepciones").html(codigo);
    KTMenu.createInstances();
}

function item_recepcion_almacen(recepcion) {
    const estatus = (recepcion.estatus || "pendiente").toLowerCase();
    const badge = badge_estatus_recepcion(estatus);
    const cantidadPendiente = parseFloat(recepcion.cantidad_pendiente || 0);
    const textoAccionRecepcion = estatus === "recibida" || estatus === "cancelada" ? "Ver recepcion" : "Recibir";

    return `
        <tr>
            <td>
                <div class="form-check form-check-sm form-check-custom form-check-solid">
                    <input class="form-check-input" type="checkbox" value="${recepcion.id_recepcion_almacen}" />
                </div>
            </td>
            <td data-kt-almacen-recepcion-filter="recepcion_id">
                <span class="text-gray-800 fw-bold">${recepcion.id_recepcion_almacen}</span>
            </td>
            <td>
                <span class="text-gray-800 fw-bold">${recepcion.folio || ""}</span>
            </td>
            <td>
                <a href="/compra/ver_orden_compra/${recepcion.id_orden_compra}" class="text-gray-800 text-hover-primary fw-bold">
                    ${recepcion.folio_orden_compra || recepcion.id_orden_compra}
                </a>
            </td>
            <td class="text-end pe-0">
                <span class="fw-bold">${recepcion.proveedor || ""}</span>
            </td>
            <td class="text-end pe-0">
                <span class="fw-bold">${recepcion.almacen || ""}</span>
            </td>
            <td class="text-end pe-0">
                ${badge}
            </td>
            <td class="text-end pe-0">
                <span class="fw-bold">${recepcion.total_partidas || 0}</span>
            </td>
            <td class="text-end pe-0">
                <span class="fw-bold">${cantidadPendiente.toFixed(4)}</span>
            </td>
            <td class="text-end pe-0">
                <span class="fw-bold">${recepcion.fecha_alerta || ""}</span>
            </td>
            <td class="text-end">
                <a href="#" class="btn btn-sm btn-light btn-active-light-primary" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
                    Acciones
                    <span class="svg-icon svg-icon-5 m-0">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M11.4343 12.7344L7.25 8.55005C6.83579 8.13583 6.16421 8.13584 5.75 8.55005C5.33579 8.96426 5.33579 9.63583 5.75 10.05L11.2929 15.5929C11.6834 15.9835 12.3166 15.9835 12.7071 15.5929L18.25 10.05C18.6642 9.63584 18.6642 8.96426 18.25 8.55005C17.8358 8.13584 17.1642 8.13584 16.75 8.55005L12.5657 12.7344C12.2533 13.0468 11.7467 13.0468 11.4343 12.7344Z" fill="currentColor" />
                        </svg>
                    </span>
                </a>
                <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-150px py-4" data-kt-menu="true">
                    <div class="menu-item px-3">
                        <a href="/almacen/recibir/${recepcion.id_recepcion_almacen}" class="menu-link px-3">${textoAccionRecepcion}</a>
                    </div>
                    <div class="menu-item px-3">
                        <a href="/compra/ver_orden_compra/${recepcion.id_orden_compra}" class="menu-link px-3">Ver OC</a>
                    </div>
                </div>
            </td>
        </tr>
    `;
}

function badge_estatus_recepcion(estatus) {
    if (estatus === "recibida") {
        return `<span class="badge badge-light-success">${estatus}</span>`;
    }
    if (estatus === "parcial") {
        return `<span class="badge badge-light-warning">${estatus}</span>`;
    }
    if (estatus === "cancelada") {
        return `<span class="badge badge-light-danger">${estatus}</span>`;
    }
    return `<span class="badge badge-light-primary">${estatus}</span>`;
}
