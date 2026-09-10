"use strict";

(function () {
    var solicitudes = [];
    var clientes = [];
    var cotizaciones = [];
    var productos = [];
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
            pedido_solicitado: "badge-light-primary",
            surtido_revision: "badge-light-warning",
            surtido_parcial: "badge-light-info",
            surtido_confirmado: "badge-light-success",
            no_surtible: "badge-light-danger",
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

    function labelTipoNegocio(tipo) {
        var mapa = {
            venta_internet: "Venta por internet",
            veterinaria: "Veterinaria",
            petshop: "Petshop",
            acuario: "Acuario",
            acuario_petshop: "Acuario + petshop",
            estetica_canina: "Estetica canina",
            criador: "Criador",
            vendedor_mercado: "Vendedor de mercado",
            vendedor_ambulante: "Vendedor ambulante",
            otro: "Otro"
        };
        return mapa[tipo] || tipo || "Por clasificar";
    }

    function labelPermiso(permiso) {
        var mapa = {
            "distribucion.catalogo.ver": "Ver catalogo",
            "distribucion.catalogo.ver_detalle": "Ver detalle de producto",
            "distribucion.precio.ver_publico": "Ver precio publico",
            "distribucion.precio.ver_mayoreo": "Ver precio mayoreo",
            "distribucion.precio.ver_lista_asignada": "Ver lista asignada",
            "distribucion.inventario.ver_disponibilidad": "Ver disponibilidad confirmada",
            "distribucion.cotizacion.solicitar": "Solicitar cotizacion",
            "distribucion.pedido.preliminar": "Enviar solicitud de pedido",
            "distribucion.catalogo.descargar": "Descargar catalogo",
            "distribucion.cuenta.editar": "Editar cuenta"
        };
        return mapa[permiso] || permiso;
    }

    function showError(error) {
        Swal.fire({text: error.message || String(error), icon: "error", confirmButtonText: "Aceptar"});
    }

    function showOk(message) {
        Swal.fire({text: message || "Operacion completada", icon: "success", confirmButtonText: "Aceptar"});
    }

    function telefonoWhatsApp(value) {
        var numero = String(value || "").replace(/\D+/g, "");
        if (numero.length === 10) {
            numero = "52" + numero;
        }
        return numero;
    }

    function mensajeWhatsApp(item) {
        var nombre = item && item.nombre ? item.nombre : "buen dia";
        var correo = item && item.correo ? item.correo : "";
        var portal = "https://distribucion.artiani.com.mx";
        var partes = [
            "Hola " + nombre + ", tu solicitud de acceso mayorista Artiani ya fue aprobada.",
            correo ? "Puedes intentar ingresar al portal de Distribucion con este correo: " + correo + "." : "Ya puedes intentar ingresar al portal de Distribucion.",
            "Portal: " + portal,
            "Si necesitas apoyo para activar o definir tu contrasena, te ayudamos por este medio."
        ];
        return partes.join("\n");
    }

    function abrirWhatsApp(item) {
        var numero = telefonoWhatsApp((item && (item.whatsapp || item.telefono)) || "");
        if (!numero) {
            Swal.fire({text: "Este registro no tiene telefono o WhatsApp capturado.", icon: "warning", confirmButtonText: "Aceptar"});
            return;
        }
        window.open("https://wa.me/" + encodeURIComponent(numero) + "?text=" + encodeURIComponent(mensajeWhatsApp(item)), "_blank", "noopener");
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
            return [item.folio, item.nombre, item.nombre_negocio, item.empresa, item.correo, item.telefono, item.whatsapp, item.ciudad, item.estado, item.tipo_negocio].join(" ").toLowerCase().indexOf(q) !== -1;
        });
        document.getElementById("dist_solicitudes_lista").innerHTML = visibles.map(function (item) {
            var acciones = "<button class=\"btn btn-sm btn-icon btn-light-info\" title=\"Detalle\" data-solicitud-detalle=\"" + escapeHtml(item.id_solicitud_distribucion) + "\"><i class=\"bi bi-card-text\"></i></button> ";
            if (item.telefono || item.whatsapp) {
                acciones += "<button class=\"btn btn-sm btn-icon btn-light-success\" title=\"WhatsApp\" data-solicitud-whatsapp=\"" + escapeHtml(item.id_solicitud_distribucion) + "\"><i class=\"bi bi-whatsapp\"></i></button> ";
            }
            if (permisosUi.aprobar && item.estatus === "pendiente") {
                acciones += "<button class=\"btn btn-sm btn-icon btn-light-success\" title=\"Aprobar\" data-solicitud-aprobar=\"" + escapeHtml(item.id_solicitud_distribucion) + "\"><i class=\"bi bi-check-lg\"></i></button> " +
                    "<button class=\"btn btn-sm btn-icon btn-light-danger\" title=\"Rechazar\" data-solicitud-rechazar=\"" + escapeHtml(item.id_solicitud_distribucion) + "\"><i class=\"bi bi-x-lg\"></i></button>";
            }
            var negocio = item.nombre_negocio || item.empresa || "";
            var contactoExtra = [item.correo, item.telefono ? "Tel. " + item.telefono : "", item.whatsapp ? "WA " + item.whatsapp : ""].filter(Boolean).join(" / ");
            var ubicacion = [item.ciudad, item.estado].filter(Boolean).join(", ");
            return "<tr><td><div class=\"fw-bold\">" + escapeHtml(item.folio) + "</div><div class=\"text-muted fs-7\">" + escapeHtml(item.fecha_registro || "") + "</div></td>" +
                "<td><div class=\"fw-bold\">" + escapeHtml(item.nombre) + "</div><div class=\"text-muted fs-7\">" + escapeHtml(contactoExtra) + "</div></td>" +
                "<td><div class=\"fw-bold\">" + escapeHtml(negocio) + "</div><div class=\"text-muted fs-7\">" + escapeHtml(labelTipoNegocio(item.tipo_negocio)) + "</div></td>" +
                "<td>" + escapeHtml(ubicacion || "Por capturar") + "</td><td>" + badge(item.estatus) + "</td><td class=\"text-end\">" + acciones + "</td></tr>";
        }).join("") || "<tr><td colspan=\"6\" class=\"text-center text-muted py-10\">Sin solicitudes</td></tr>";
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
            if (item.telefono) {
                acciones += "<button class=\"btn btn-sm btn-icon btn-light-success\" title=\"WhatsApp\" data-cliente-whatsapp=\"" + escapeHtml(item.id_cliente_distribucion) + "\"><i class=\"bi bi-whatsapp\"></i></button> ";
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
            var total = item.estatus === "pedido_solicitado" ? "<span class=\"text-muted\">Por confirmar</span>" : money(item.total_estimado);
            return "<tr><td class=\"fw-bold\">" + escapeHtml(item.folio) + "</td><td>ID " + escapeHtml(item.id_cliente_distribucion) + "</td><td>" + total + "</td>" +
                "<td>" + badge(item.estatus) + "</td><td class=\"text-muted fs-7\">" + escapeHtml(item.fecha_registro || "") + "</td><td class=\"text-end\">" + acciones + "</td></tr>";
        }).join("") || "<tr><td colspan=\"6\" class=\"text-center text-muted py-10\">Sin cotizaciones</td></tr>";
    }

    function renderProductos() {
        var lista = document.getElementById("dist_productos_lista");
        if (!lista) { return; }
        lista.innerHTML = productos.map(function (item) {
            var activo = ["activo", "publicado", "aprobado"].indexOf(item.canal_estatus) !== -1 && Number(item.sincronizar_catalogo || 0) === 1;
            var acciones = permisosUi.editar
                ? (activo
                    ? "<button class=\"btn btn-sm btn-icon btn-light-danger\" title=\"Desactivar\" data-sku-desactivar=\"" + escapeHtml(item.id_sku) + "\" data-vinculo=\"" + escapeHtml(item.id_canal_vinculo || "") + "\"><i class=\"bi bi-eye-slash\"></i></button>"
                    : "<button class=\"btn btn-sm btn-icon btn-light-success\" title=\"Publicar\" data-sku-publicar=\"" + escapeHtml(item.id_sku) + "\"><i class=\"bi bi-cloud-upload\"></i></button>")
                : "";
            return "<tr><td><div class=\"fw-bold\">" + escapeHtml(item.sku) + "</div><div class=\"text-muted fs-7\">ID " + escapeHtml(item.id_sku) + "</div></td>" +
                "<td><div class=\"fw-bold\">" + escapeHtml(item.sku_nombre || item.producto) + "</div><div class=\"text-muted fs-7\">" + escapeHtml(item.producto || "") + "</div></td>" +
                "<td class=\"text-muted fs-7\">" + escapeHtml(item.id_externo || "Sin publicar") + "</td><td>" + badge(item.canal_estatus || "sin_vinculo") + "</td>" +
                "<td class=\"text-end\">" + acciones + "</td></tr>";
        }).join("") || "<tr><td colspan=\"5\" class=\"text-center text-muted py-10\">Sin SKUs publicables</td></tr>";
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

    function cargarProductos() {
        var q = "";
        var input = document.getElementById("dist_productos_buscar");
        if (input) { q = input.value || ""; }
        return request("/DistribucionAdmin/skus_publicables?limite=80&q=" + encodeURIComponent(q)).then(function (response) {
            productos = response.depurar && response.depurar.items ? response.depurar.items : [];
            renderProductos();
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
            return Promise.all([cargarSolicitudes(), cargarClientes(), cargarCotizaciones(), cargarProductos()]);
        }).catch(showError);
    }

    function aprobarSolicitud(id) {
        var solicitud = solicitudes.find(function (item) { return String(item.id_solicitud_distribucion) === String(id); });
        var listaHtml = permisosUi.asignar_precios
            ? "<label class=\"form-label fw-semibold\">Lista de precios</label><select id=\"dist_aprobar_lista\" class=\"form-select form-select-solid mb-5\">" + getListaOptions("") + "</select>"
            : "<div class=\"text-muted mb-5\">Sin permiso para asignar lista de precios en este paso.</div>";
        var permisosHtml = permisosUi.editar
            ? permisosComerciales.map(function (permiso) {
                return "<label class=\"form-check form-check-custom form-check-solid mb-3\"><input class=\"form-check-input\" type=\"checkbox\" value=\"" + escapeHtml(permiso) + "\"><span class=\"form-check-label\"><span class=\"fw-semibold\">" + escapeHtml(labelPermiso(permiso)) + "</span><span class=\"text-muted d-block fs-8\">" + escapeHtml(permiso) + "</span></span></label>";
            }).join("")
            : "<div class=\"text-muted\">Sin permiso para asignar permisos comerciales en este paso.</div>";
        Swal.fire({
            title: "Aprobar cliente",
            html: "<div class=\"text-start\">" +
                "<div class=\"mb-5\"><div class=\"fw-bold\">" + escapeHtml(solicitud ? solicitud.nombre : "Solicitud") + "</div><div class=\"text-muted\">" + escapeHtml(solicitud ? (solicitud.nombre_negocio || solicitud.empresa || "") : "") + "</div></div>" +
                "<label class=\"form-label fw-semibold\">Contrasenia temporal</label><input id=\"dist_aprobar_contrasenia\" type=\"password\" class=\"form-control form-control-solid mb-5\" placeholder=\"Dejar vacio para definir despues\">" +
                "<label class=\"form-label fw-semibold\">Tipo de cliente</label><input class=\"form-control form-control-solid mb-5\" value=\"mayorista\" disabled>" +
                listaHtml +
                "<div class=\"separator my-5\"></div><div class=\"fw-semibold mb-3\">Permisos comerciales iniciales</div>" +
                permisosHtml +
                "</div>",
            width: 760,
            showCancelButton: true,
            confirmButtonText: "Aprobar",
            preConfirm: function () {
                var permisos = [];
                document.querySelectorAll(".swal2-container input[type='checkbox']:checked").forEach(function (input) {
                    permisos.push(input.value);
                });
                return {
                    contrasenia: (document.getElementById("dist_aprobar_contrasenia") || {}).value || "",
                    id_lista_precio: (document.getElementById("dist_aprobar_lista") || {}).value || "",
                    permisos: permisos
                };
            }
        }).then(function (result) {
            if (!result.isConfirmed) { return; }
            request("/DistribucionAdmin/cliente_aprobar", {
                id_solicitud_distribucion: id,
                contrasenia: result.value.contrasenia || "",
                id_lista_precio: result.value.id_lista_precio || "",
                permisos: JSON.stringify(result.value.permisos || [])
            })
                .then(function (response) {
                    if (response.error) { throw new Error(response.mensaje); }
                    showOk(response.mensaje);
                    return cargarTodo();
                }).catch(showError);
        });
    }

    function verSolicitud(id) {
        request("/DistribucionAdmin/solicitud_detalle?id_solicitud_distribucion=" + encodeURIComponent(id)).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            var item = response.depurar && response.depurar.solicitud ? response.depurar.solicitud : null;
            if (!item) { throw new Error("Solicitud no encontrada"); }
            var html = "<div class=\"text-start\">" +
                "<div class=\"mb-5\"><div class=\"fw-bold fs-6\">" + escapeHtml(item.nombre || "") + "</div><div class=\"text-muted\">" + escapeHtml(item.nombre_negocio || item.empresa || "") + "</div></div>" +
                "<div class=\"row g-4\">" +
                detalleCampo("Correo", item.correo) +
                detalleCampo("Telefono", item.telefono) +
                detalleCampo("WhatsApp", item.whatsapp) +
                detalleCampo("RFC", item.rfc) +
                detalleCampo("Ciudad", item.ciudad) +
                detalleCampo("Estado", item.estado) +
                detalleCampo("Tipo de negocio", labelTipoNegocio(item.tipo_negocio)) +
                detalleCampo("Interes", item.tipo_interes) +
                detalleCampo("Calle", item.calle) +
                detalleCampo("Numero exterior", item.numero_exterior) +
                detalleCampo("Numero interior", item.numero_interior) +
                detalleCampo("Colonia", item.colonia) +
                detalleCampo("Codigo postal", item.codigo_postal) +
                detalleCampo("Referencias", item.referencias, true) +
                detalleCampo("Intereses comerciales", item.intereses_comerciales, true) +
                detalleCampo("Mensaje", item.mensaje, true) +
                "</div></div>";
            Swal.fire({
                title: item.folio || "Solicitud Distribucion",
                html: html,
                width: 850,
                confirmButtonText: "Cerrar"
            });
        }).catch(showError);
    }

    function detalleCampo(label, value, wide) {
        return "<div class=\"" + (wide ? "col-12" : "col-md-6") + "\"><div class=\"text-muted fs-8 text-uppercase\">" + escapeHtml(label) + "</div><div class=\"fw-semibold\">" + escapeHtml(value || "Por capturar") + "</div></div>";
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
            return "<label class=\"form-check form-check-custom form-check-solid mb-3\"><input class=\"form-check-input\" type=\"checkbox\" value=\"" + escapeHtml(permiso) + "\"><span class=\"form-check-label\"><span class=\"fw-semibold\">" + escapeHtml(labelPermiso(permiso)) + "</span><span class=\"text-muted d-block fs-8\">" + escapeHtml(permiso) + "</span></span></label>";
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
        } else if (button.id === "dist_productos_buscar_btn") {
            cargarProductos().catch(showError);
        } else if (button.hasAttribute("data-solicitud-aprobar")) {
            aprobarSolicitud(button.getAttribute("data-solicitud-aprobar"));
        } else if (button.hasAttribute("data-solicitud-detalle")) {
            verSolicitud(button.getAttribute("data-solicitud-detalle"));
        } else if (button.hasAttribute("data-solicitud-whatsapp")) {
            var solicitud = solicitudes.find(function (item) { return String(item.id_solicitud_distribucion) === String(button.getAttribute("data-solicitud-whatsapp")); });
            abrirWhatsApp(solicitud);
        } else if (button.hasAttribute("data-solicitud-rechazar")) {
            accionSimple("/DistribucionAdmin/cliente_rechazar", {id_solicitud_distribucion: button.getAttribute("data-solicitud-rechazar")});
        } else if (button.hasAttribute("data-cliente-suspender")) {
            accionSimple("/DistribucionAdmin/cliente_suspendir", {id_cliente_distribucion: button.getAttribute("data-cliente-suspender")});
        } else if (button.hasAttribute("data-cliente-permisos")) {
            editarPermisos(button.getAttribute("data-cliente-permisos"));
        } else if (button.hasAttribute("data-cliente-whatsapp")) {
            var cliente = clientes.find(function (item) { return String(item.id_cliente_distribucion) === String(button.getAttribute("data-cliente-whatsapp")); });
            abrirWhatsApp(cliente);
        } else if (button.hasAttribute("data-cotizacion")) {
            accionSimple("/DistribucionAdmin/cotizacion_accion_plan", {
                id_cotizacion_distribucion: button.getAttribute("data-cotizacion"),
                accion: button.getAttribute("data-cotizacion-accion")
            });
        } else if (button.hasAttribute("data-sku-publicar")) {
            accionSimple("/DistribucionAdmin/publicar_sku", {id_sku: button.getAttribute("data-sku-publicar")});
        } else if (button.hasAttribute("data-sku-desactivar")) {
            accionSimple("/DistribucionAdmin/desactivar_sku", {
                id_sku: button.getAttribute("data-sku-desactivar"),
                id_canal_vinculo: button.getAttribute("data-vinculo")
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
        } else if (event.target.id === "dist_productos_buscar" && event.target.value.length === 0) {
            cargarProductos().catch(showError);
        }
    });

    cargarTodo();
})();
