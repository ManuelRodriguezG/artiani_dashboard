"use strict";

(function () {
    var solicitudes = [];
    var clientes = [];
    var cotizaciones = [];
    var listas = [];
    var permisosComerciales = [];
    var permisosUi = window.DISTRIBUCION_ADMIN_PERMISOS || {};

    function escapeHtml(value) {
        var div = document.createElement("div");
        div.textContent = value == null ? "" : String(value);
        return div.innerHTML;
    }

    function request(url, data) {
        var options = {credentials: "same-origin"};
        if (data) {
            options.method = "POST";
            options.headers = {
                "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
                "X-CSRF-Token": window.ERP_CSRF_TOKEN || ""
            };
            data._csrf = window.ERP_CSRF_TOKEN || "";
            options.body = new URLSearchParams(data).toString();
        }
        return fetch(url, options).then(function (response) {
            if (!response.ok) {
                throw new Error("Respuesta no valida del servidor (" + response.status + ")");
            }
            return response.json();
        });
    }

    function badge(estatus) {
        var mapa = {
            pendiente: "badge-light-warning",
            aprobado: "badge-light-success",
            aprobada: "badge-light-success",
            rechazado: "badge-light-danger",
            suspendido: "badge-light-danger",
            recibida: "badge-light-primary",
            recibida_revision: "badge-light-warning",
            en_revision: "badge-light-info",
            requiere_info: "badge-light-warning",
            respondida: "badge-light-success",
            cerrada: "badge-light-dark",
            cancelada: "badge-light-danger"
        };
        return "<span class=\"badge " + (mapa[estatus] || "badge-light") + "\">" + escapeHtml(estatus || "sin estado") + "</span>";
    }

    function money(value) {
        if (value === null || value === undefined || value === "") {
            return "<span class=\"text-muted\">Por revisar</span>";
        }
        return "$" + Number(value).toLocaleString("es-MX", {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    function showError(error) {
        Swal.fire({text: error.message || String(error), icon: "error", confirmButtonText: "Aceptar"});
    }

    function showOk(message) {
        Swal.fire({text: message || "Operacion completada", icon: "success", confirmButtonText: "Aceptar"});
    }

    function getListaOptions(selected) {
        var html = "<option value=\"\">Sin lista</option>";
        listas.forEach(function (lista) {
            html += "<option value=\"" + escapeHtml(lista.id_lista_precio) + "\"" + (String(selected || "") === String(lista.id_lista_precio) ? " selected" : "") + ">" +
                escapeHtml((lista.codigo || "Lista") + " - " + lista.nombre) + "</option>";
        });
        return html;
    }

    function renderSolicitudes() {
        var q = (document.getElementById("dist_solicitudes_buscar").value || "").toLowerCase();
        var visibles = solicitudes.filter(function (item) {
            return [item.folio, item.nombre, item.empresa, item.correo, item.telefono].join(" ").toLowerCase().indexOf(q) !== -1;
        });
        document.getElementById("dist_solicitudes_lista").innerHTML = visibles.map(function (item) {
            var acciones = "";
            if (permisosUi.aprobar && item.estatus === "pendiente") {
                acciones = "<button class=\"btn btn-sm btn-icon btn-light-success\" title=\"Aprobar\" data-solicitud-aprobar=\"" + escapeHtml(item.id_solicitud_distribucion) + "\"><i class=\"bi bi-check-lg\"></i></button> " +
                    "<button class=\"btn btn-sm btn-icon btn-light-danger\" title=\"Rechazar\" data-solicitud-rechazar=\"" + escapeHtml(item.id_solicitud_distribucion) + "\"><i class=\"bi bi-x-lg\"></i></button>";
            }
            return "<tr><td><div class=\"fw-bold\">" + escapeHtml(item.folio) + "</div><div class=\"text-muted fs-7\">" + escapeHtml(item.fecha_registro || "") + "</div></td>" +
                "<td><div class=\"fw-bold\">" + escapeHtml(item.nombre) + "</div><div class=\"text-muted fs-7\">" + escapeHtml(item.empresa || item.correo) + "</div></td>" +
                "<td>" + escapeHtml(item.tipo_interes) + "</td><td>" + badge(item.estatus) + "</td><td class=\"text-end\">" + acciones + "</td></tr>";
        }).join("") || "<tr><td colspan=\"5\" class=\"text-center text-muted py-10\">Sin solicitudes</td></tr>";
    }

    function renderClientes() {
        var q = (document.getElementById("dist_clientes_buscar").value || "").toLowerCase();
        var visibles = clientes.filter(function (item) {
            return [item.nombre, item.empresa, item.correo, item.telefono].join(" ").toLowerCase().indexOf(q) !== -1;
        });
        document.getElementById("dist_clientes_lista").innerHTML = visibles.map(function (item) {
            var tipo = permisosUi.editar
                ? "<select class=\"form-select form-select-sm\" data-cliente-tipo=\"" + escapeHtml(item.id_cliente_distribucion) + "\">" +
                  ["registrado", "revendedor", "mayorista", "distribuidor_autorizado"].map(function (tipoCliente) {
                      return "<option value=\"" + tipoCliente + "\"" + (item.tipo_cliente === tipoCliente ? " selected" : "") + ">" + tipoCliente + "</option>";
                  }).join("") + "</select>"
                : escapeHtml(item.tipo_cliente);
            var lista = permisosUi.asignar_precios
                ? "<select class=\"form-select form-select-sm\" data-cliente-lista=\"" + escapeHtml(item.id_cliente_distribucion) + "\">" + getListaOptions(item.id_lista_precio) + "</select>"
                : escapeHtml(item.lista_precio || "Sin lista");
            var acciones = "";
            if (permisosUi.editar) {
                acciones += "<button class=\"btn btn-sm btn-icon btn-light-primary\" title=\"Permisos\" data-cliente-permisos=\"" + escapeHtml(item.id_cliente_distribucion) + "\"><i class=\"bi bi-sliders\"></i></button> ";
            }
            if (permisosUi.aprobar && item.estatus !== "suspendido") {
                acciones += "<button class=\"btn btn-sm btn-icon btn-light-danger\" title=\"Suspender\" data-cliente-suspender=\"" + escapeHtml(item.id_cliente_distribucion) + "\"><i class=\"bi bi-pause-circle\"></i></button>";
            }
            return "<tr><td><div class=\"fw-bold\">" + escapeHtml(item.nombre) + "</div><div class=\"text-muted fs-7\">" + escapeHtml(item.correo) + "</div></td>" +
                "<td>" + tipo + "</td><td>" + lista + "</td><td><span class=\"badge badge-light-primary\">" + escapeHtml(item.permisos_activos || 0) + "</span></td>" +
                "<td>" + badge(item.estatus) + "</td><td class=\"text-end\">" + acciones + "</td></tr>";
        }).join("") || "<tr><td colspan=\"6\" class=\"text-center text-muted py-10\">Sin clientes</td></tr>";
    }

    function renderCotizaciones() {
        document.getElementById("dist_cotizaciones_total").textContent = cotizaciones.length;
        document.getElementById("dist_cotizaciones_lista").innerHTML = cotizaciones.map(function (item) {
            var acciones = permisosUi.cotizaciones_gestionar
                ? "<button class=\"btn btn-sm btn-icon btn-light-info\" title=\"Tomar\" data-cotizacion-accion=\"tomar\" data-cotizacion=\"" + escapeHtml(item.id_cotizacion_distribucion) + "\"><i class=\"bi bi-person-check\"></i></button> " +
                  "<button class=\"btn btn-sm btn-icon btn-light-success\" title=\"Responder\" data-cotizacion-accion=\"responder\" data-cotizacion=\"" + escapeHtml(item.id_cotizacion_distribucion) + "\"><i class=\"bi bi-send-check\"></i></button> " +
                  "<button class=\"btn btn-sm btn-icon btn-light-dark\" title=\"Cerrar\" data-cotizacion-accion=\"cerrar\" data-cotizacion=\"" + escapeHtml(item.id_cotizacion_distribucion) + "\"><i class=\"bi bi-check2-circle\"></i></button>"
                : "";
            return "<tr><td class=\"fw-bold\">" + escapeHtml(item.folio) + "</td><td>ID " + escapeHtml(item.id_cliente_distribucion) + "</td><td>" + money(item.total_estimado) + "</td>" +
                "<td>" + badge(item.estatus) + "</td><td class=\"text-muted fs-7\">" + escapeHtml(item.fecha_registro || "") + "</td><td class=\"text-end\">" + acciones + "</td></tr>";
        }).join("") || "<tr><td colspan=\"6\" class=\"text-center text-muted py-10\">Sin cotizaciones</td></tr>";
    }

    function cargarSolicitudes() {
        var estatus = document.getElementById("dist_solicitudes_estatus").value;
        return request("/DistribucionAdmin/solicitudes?limite=100&estatus=" + encodeURIComponent(estatus)).then(function (response) {
            solicitudes = response.depurar && response.depurar.items ? response.depurar.items : [];
            renderSolicitudes();
        });
    }

    function cargarClientes() {
        var estatus = document.getElementById("dist_clientes_estatus").value;
        return request("/DistribucionAdmin/clientes?limite=100&estatus=" + encodeURIComponent(estatus)).then(function (response) {
            clientes = response.depurar && response.depurar.items ? response.depurar.items : [];
            renderClientes();
        });
    }

    function cargarCotizaciones() {
        return request("/DistribucionAdmin/cotizaciones?limite=100").then(function (response) {
            cotizaciones = response.depurar && response.depurar.items ? response.depurar.items : [];
            renderCotizaciones();
        });
    }

    function cargarAuxiliares() {
        var promesas = [];
        if (permisosUi.asignar_precios) {
            promesas.push(request("/DistribucionAdmin/listas_precios").then(function (response) {
                listas = response.depurar && response.depurar.items ? response.depurar.items : [];
            }));
        }
        if (permisosUi.editar) {
            promesas.push(request("/DistribucionAdmin/permisos_comerciales").then(function (response) {
                permisosComerciales = response.depurar && response.depurar.items ? response.depurar.items : [];
            }));
        }
        return Promise.all(promesas);
    }

    function cargarTodo() {
        return cargarAuxiliares().then(function () {
            return Promise.all([cargarSolicitudes(), cargarClientes(), cargarCotizaciones()]);
        }).catch(showError);
    }

    function aprobarSolicitud(id) {
        Swal.fire({
            title: "Aprobar cliente",
            input: "password",
            inputLabel: "Contrasenia temporal opcional",
            inputPlaceholder: "Dejar vacio para definir despues",
            showCancelButton: true,
            confirmButtonText: "Aprobar"
        }).then(function (result) {
            if (!result.isConfirmed) { return; }
            request("/DistribucionAdmin/cliente_aprobar", {id_solicitud_distribucion: id, contrasenia: result.value || ""})
                .then(function (response) {
                    if (response.error) { throw new Error(response.mensaje); }
                    showOk(response.mensaje);
                    return cargarTodo();
                }).catch(showError);
        });
    }

    function accionSimple(url, data) {
        return request(url, data).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            showOk(response.mensaje);
            return cargarTodo();
        }).catch(showError);
    }

    function editarPermisos(idCliente) {
        var cliente = clientes.find(function (item) { return String(item.id_cliente_distribucion) === String(idCliente); });
        var html = permisosComerciales.map(function (permiso) {
            return "<label class=\"form-check form-check-custom form-check-solid mb-3\"><input class=\"form-check-input\" type=\"checkbox\" value=\"" + escapeHtml(permiso) + "\"><span class=\"form-check-label\">" + escapeHtml(permiso) + "</span></label>";
        }).join("");
        Swal.fire({
            title: cliente ? cliente.nombre : "Permisos",
            html: "<div class=\"text-start\">" + html + "</div>",
            width: 650,
            showCancelButton: true,
            confirmButtonText: "Guardar permisos",
            didOpen: function () {
                var actual = cliente && cliente.permisos ? String(cliente.permisos).split(",") : [];
                document.querySelectorAll(".swal2-container input[type='checkbox']").forEach(function (input) {
                    input.checked = actual.indexOf(input.value) !== -1;
                });
            },
            preConfirm: function () {
                var seleccion = [];
                document.querySelectorAll(".swal2-container input[type='checkbox']:checked").forEach(function (input) {
                    seleccion.push(input.value);
                });
                return seleccion;
            }
        }).then(function (result) {
            if (!result.isConfirmed) { return; }
            accionSimple("/DistribucionAdmin/asignar_permisos", {id_cliente_distribucion: idCliente, permisos: JSON.stringify(result.value || [])});
        });
    }

    document.addEventListener("click", function (event) {
        var button = event.target.closest("button");
        if (!button) { return; }
        if (button.id === "distribucion_refrescar") {
            cargarTodo();
        } else if (button.hasAttribute("data-solicitud-aprobar")) {
            aprobarSolicitud(button.getAttribute("data-solicitud-aprobar"));
        } else if (button.hasAttribute("data-solicitud-rechazar")) {
            accionSimple("/DistribucionAdmin/cliente_rechazar", {id_solicitud_distribucion: button.getAttribute("data-solicitud-rechazar")});
        } else if (button.hasAttribute("data-cliente-suspender")) {
            accionSimple("/DistribucionAdmin/cliente_suspendir", {id_cliente_distribucion: button.getAttribute("data-cliente-suspender")});
        } else if (button.hasAttribute("data-cliente-permisos")) {
            editarPermisos(button.getAttribute("data-cliente-permisos"));
        } else if (button.hasAttribute("data-cotizacion")) {
            accionSimple("/DistribucionAdmin/cotizacion_accion_plan", {
                id_cotizacion_distribucion: button.getAttribute("data-cotizacion"),
                accion: button.getAttribute("data-cotizacion-accion")
            });
        }
    });

    document.addEventListener("change", function (event) {
        if (event.target.id === "dist_solicitudes_estatus") {
            cargarSolicitudes().catch(showError);
        } else if (event.target.id === "dist_clientes_estatus") {
            cargarClientes().catch(showError);
        } else if (event.target.hasAttribute("data-cliente-tipo")) {
            accionSimple("/DistribucionAdmin/asignar_tipo_cliente", {
                id_cliente_distribucion: event.target.getAttribute("data-cliente-tipo"),
                tipo_cliente: event.target.value
            });
        } else if (event.target.hasAttribute("data-cliente-lista") && event.target.value) {
            accionSimple("/DistribucionAdmin/asignar_lista_precio", {
                id_cliente_distribucion: event.target.getAttribute("data-cliente-lista"),
                id_lista_precio: event.target.value
            });
        }
    });

    document.addEventListener("input", function (event) {
        if (event.target.id === "dist_solicitudes_buscar") {
            renderSolicitudes();
        } else if (event.target.id === "dist_clientes_buscar") {
            renderClientes();
        }
    });

    cargarTodo();
})();
