"use strict";

(function () {
    var solicitudes = [];
    var clientes = [];
    var cotizaciones = [];
    var productos = [];
    var miCatalogoClientes = [];
    var inventarios = [];
    var sugeridos = [];
    var resumen = {};
    var demanda = {};
    var catalogosFiltros = {marcas: [], categorias: [], proveedores: []};
    var productosPaginacion = {pagina: 1, limite: 120, total: 0, total_paginas: 1};
    var listas = [];
    var permisosComerciales = [];
    var permisosUi = window.DISTRIBUCION_ADMIN_PERMISOS || {};
    var seccionActiva = window.DISTRIBUCION_ADMIN_SECCION || "resumen";

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

    function moneyText(value) {
        if (value === null || value === undefined || value === "") {
            return "Por revisar";
        }
        return "$" + Number(value).toLocaleString("es-MX", {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    function numero(value) {
        return Number(value || 0).toLocaleString("es-MX", {minimumFractionDigits: 0, maximumFractionDigits: 2});
    }

    function valorNumerico(value) {
        if (value === null || value === undefined || value === "") { return null; }
        var numeroValor = Number(value);
        return Number.isFinite(numeroValor) ? numeroValor : null;
    }

    function jsonSeguro(value) {
        if (!value) { return {}; }
        if (typeof value === "object") { return value; }
        try {
            var parsed = JSON.parse(value);
            return parsed && typeof parsed === "object" ? parsed : {};
        } catch (e) {
            return {};
        }
    }

    function arraySeguro(value) {
        if (!value) { return []; }
        if (Array.isArray(value)) { return value; }
        if (typeof value === "object") { return Object.keys(value).map(function (key) { return value[key]; }); }
        try {
            var parsed = JSON.parse(value);
            if (Array.isArray(parsed)) { return parsed; }
            if (parsed && typeof parsed === "object") {
                return Object.keys(parsed).map(function (key) { return parsed[key]; });
            }
        } catch (e) {
            return String(value).split(",").map(function (item) { return item.trim(); }).filter(Boolean);
        }
        return [];
    }

    function selectValue(id) {
        var el = document.getElementById(id);
        return el ? (el.value || "") : "";
    }

    function query(params) {
        return Object.keys(params).filter(function (key) {
            return params[key] !== null && params[key] !== undefined && params[key] !== "";
        }).map(function (key) {
            return encodeURIComponent(key) + "=" + encodeURIComponent(params[key]);
        }).join("&");
    }

    function renderListaCompacta(id, items, tituloFn, metaFn, vacio) {
        var contenedor = document.getElementById(id);
        if (!contenedor) { return; }
        contenedor.innerHTML = (items || []).map(function (item) {
            return "<div class=\"d-flex justify-content-between align-items-start border-bottom py-3\">" +
                "<div class=\"pe-3\"><div class=\"fw-semibold\">" + escapeHtml(tituloFn(item)) + "</div><div class=\"text-muted fs-8\">" + escapeHtml(metaFn(item)) + "</div></div>" +
                "<span class=\"badge badge-light-primary\">" + escapeHtml(item.total || item.clientes || item.solicitudes || item.cantidad_solicitada || item.productos || 0) + "</span>" +
            "</div>";
        }).join("") || "<div class=\"text-muted text-center py-6\">" + escapeHtml(vacio || "Sin datos") + "</div>";
    }

    function llenarSelect(selector, items, label) {
        document.querySelectorAll(selector).forEach(function (select) {
            var actual = select.value || "";
            select.innerHTML = "<option value=\"\">" + escapeHtml(label) + "</option>" + (items || []).map(function (item) {
                return "<option value=\"" + escapeHtml(item.id) + "\">" + escapeHtml(item.nombre) + "</option>";
            }).join("");
            select.value = actual;
        });
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
            "distribucion.mi_catalogo.gestionar": "Gestionar Mi catalogo",
            "distribucion.surtido.gestionar": "Gestionar surtido habitual",
            "distribucion.inventario_cliente.gestionar": "Gestionar inventario propio",
            "distribucion.resurtido.sugerido": "Ver sugerido de resurtido",
            "distribucion.pedido.ver": "Ver pedidos propios",
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
        if (item && item.mensaje_whatsapp) {
            return item.mensaje_whatsapp;
        }
        var nombre = item && item.nombre ? item.nombre : "buen dia";
        var correo = item && item.correo ? item.correo : "";
        var portal = portalDistribucion();
        var partes = [
            "Hola " + nombre + ", tu solicitud de acceso mayorista Artiani ya fue aprobada.",
            correo ? "Puedes intentar ingresar al portal de Distribucion con este correo: " + correo + "." : "Ya puedes intentar ingresar al portal de Distribucion.",
            "Portal: " + portal,
            "Si necesitas apoyo para activar o definir tu contrasena, te ayudamos por este medio."
        ];
        return partes.join("\n");
    }

    function portalDistribucion() {
        var host = String(window.location.hostname || "").toLowerCase();
        if (host === "localhost" || host.indexOf(".com.local") !== -1) {
            return "http://distribucion.artiani.com.local";
        }
        return "https://distribucion.artiani.com.mx";
    }

    function abrirWhatsApp(item) {
        var numero = telefonoWhatsApp((item && (item.whatsapp || item.telefono)) || "");
        if (!numero) {
            Swal.fire({text: "Este registro no tiene telefono o WhatsApp capturado.", icon: "warning", confirmButtonText: "Aceptar"});
            return;
        }
        window.open("https://wa.me/" + encodeURIComponent(numero) + "?text=" + encodeURIComponent(mensajeWhatsApp(item)), "_blank", "noopener");
    }

    function abrirWhatsAppActivacion(data) {
        request("/DistribucionAdmin/cliente_activacion_link", data).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            var depurar = response.depurar || {};
            var cliente = depurar.cliente || {};
            abrirWhatsApp({
                nombre: cliente.nombre,
                correo: cliente.correo,
                telefono: cliente.telefono || data.telefono,
                whatsapp: data.whatsapp || cliente.telefono,
                mensaje_whatsapp: depurar.mensaje_whatsapp
            });
        }).catch(showError);
    }

    function generarLinkAccesoCliente(data) {
        request("/DistribucionAdmin/cliente_activacion_link", data).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            var depurar = response.depurar || {};
            var cliente = depurar.cliente || {};
            var mensaje = depurar.mensaje_whatsapp || "";
            var url = depurar.url || "";
            Swal.fire({
                title: "Nuevo link de acceso",
                html: "<div class=\"text-start\">" +
                    "<div class=\"mb-3\"><div class=\"fw-bold\">" + escapeHtml(cliente.nombre || "Cliente Distribucion") + "</div><div class=\"text-muted fs-7\">" + escapeHtml(cliente.correo || "") + "</div></div>" +
                    "<label class=\"form-label fw-semibold\">Link de activacion</label>" +
                    "<textarea class=\"form-control form-control-solid mb-3\" rows=\"3\" readonly>" + escapeHtml(url) + "</textarea>" +
                    "<div class=\"text-muted fs-8\">Este nuevo link revoca links de activacion anteriores y vence en 72 horas.</div>" +
                    "</div>",
                icon: "success",
                width: 720,
                showCancelButton: true,
                confirmButtonText: "Abrir WhatsApp",
                cancelButtonText: "Cerrar"
            }).then(function (result) {
                if (!result.isConfirmed) { return; }
                abrirWhatsApp({
                    nombre: cliente.nombre,
                    correo: cliente.correo,
                    telefono: cliente.telefono || data.telefono,
                    whatsapp: data.whatsapp || cliente.telefono,
                    mensaje_whatsapp: mensaje
                });
            });
        }).catch(showError);
    }

    function verAuditoriaCliente(idCliente) {
        var cliente = clientes.find(function (item) { return String(item.id_cliente_distribucion) === String(idCliente); });
        request("/DistribucionAdmin/cliente_auditoria?id_cliente_distribucion=" + encodeURIComponent(idCliente)).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            var items = response.depurar && response.depurar.items ? response.depurar.items : [];
            var filas = items.map(function (item) {
                return "<tr><td class=\"text-muted fs-8\">" + escapeHtml(item.fecha_registro || "") + "</td>" +
                    "<td><div class=\"fw-semibold\">" + escapeHtml(item.accion || "") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.mensaje || "") + "</div></td>" +
                    "<td>" + badge(item.resultado || "ok") + "</td></tr>";
            }).join("") || "<tr><td colspan=\"3\" class=\"text-center text-muted py-6\">Sin eventos registrados</td></tr>";
            Swal.fire({
                title: cliente ? cliente.nombre : "Historial de acceso",
                html: "<div class=\"table-responsive text-start\"><table class=\"table table-row-dashed fs-7 gy-3 mb-0\">" +
                    "<thead><tr class=\"text-muted fw-bold\"><th>Fecha</th><th>Evento</th><th>Resultado</th></tr></thead><tbody>" + filas + "</tbody></table></div>",
                width: 850,
                confirmButtonText: "Cerrar"
            });
        }).catch(showError);
    }

    function confirmarWhatsAppActivacion(solicitud, activacion) {
        if (!solicitud || !activacion || !activacion.mensaje_whatsapp) {
            showOk("Cliente Distribucion aprobado");
            return;
        }
        Swal.fire({
            title: "Cliente aprobado",
            text: "Se genero link para crear contrasenia. Puedes abrir WhatsApp y enviar el mensaje manualmente.",
            icon: "success",
            showCancelButton: true,
            confirmButtonText: "Abrir WhatsApp",
            cancelButtonText: "Cerrar"
        }).then(function (result) {
            if (!result.isConfirmed) { return; }
            abrirWhatsApp({
                nombre: solicitud.nombre,
                correo: solicitud.correo,
                telefono: solicitud.telefono,
                whatsapp: solicitud.whatsapp,
                mensaje_whatsapp: activacion.mensaje_whatsapp
            });
        });
    }

    function getListaOptions(selected) {
        var html = "<option value=\"\">Sin lista</option>";
        listas.forEach(function (lista) {
            html += "<option value=\"" + escapeHtml(lista.id_lista_precio) + "\"" + (String(selected || "") === String(lista.id_lista_precio) ? " selected" : "") + ">" +
                escapeHtml((lista.codigo || "Lista") + " - " + lista.nombre) + "</option>";
        });
        return html;
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-09-29
     * Proposito: renderizar resumen operativo interno de Distribucion.
     * Impacto: UI ERP Distribucion; muestra alertas de operacion sin consultar el frontend externo.
     */
    function renderResumen() {
        var metricas = resumen.metricas || {};
        var kpis = [
            ["Solicitudes pendientes", "solicitudes_pendientes", "badge-light-warning"],
            ["Clientes activos", "clientes_aprobados_activos", "badge-light-success"],
            ["Sin lista", "clientes_sin_lista", "badge-light-danger"],
            ["Sin permisos", "clientes_sin_permisos_completos", "badge-light-danger"],
            ["Productos publicados", "productos_publicados", "badge-light-primary"],
            ["Candidatos", "productos_candidatos", "badge-light-info"],
            ["En Mi catalogo", "productos_mi_catalogo", "badge-light-primary"],
            ["Sugeridos pendientes", "sugeridos_pendientes", "badge-light-warning"],
            ["Pedidos por revisar", "pedidos_pendientes", "badge-light-warning"],
            ["Demanda sin precio", "productos_demandados_sin_precio", "badge-light-danger"]
        ];
        var contenedor = document.getElementById("dist_resumen_kpis");
        if (contenedor) {
            contenedor.innerHTML = kpis.map(function (kpi) {
                return "<div class=\"dist-kpi\"><div class=\"text-muted fs-8 text-uppercase mb-3\">" + escapeHtml(kpi[0]) + "</div>" +
                    "<div class=\"dist-kpi-value\">" + escapeHtml(numero(metricas[kpi[1]] || 0)) + "</div>" +
                    "<span class=\"badge " + kpi[2] + " mt-3\">" + escapeHtml(kpi[1]) + "</span></div>";
            }).join("");
        }
        renderListaCompacta("dist_resumen_recientes", resumen.recientes_mi_catalogo || [], function (item) {
            return (item.producto || item.sku || "Producto") + " - " + (item.cliente || "Cliente");
        }, function (item) {
            return [item.empresa, item.alias_cliente, item.fecha_actualizacion].filter(Boolean).join(" / ");
        }, "Sin actividad reciente");
        renderListaCompacta("dist_resumen_top_catalogo", resumen.top_mi_catalogo || [], function (item) {
            return item.producto || item.sku || "Producto";
        }, function (item) {
            return (item.clientes || 0) + " clientes";
        }, "Sin productos agregados");
        renderListaCompacta("dist_resumen_top_pedidos", resumen.top_pedidos || [], function (item) {
            return item.producto || item.sku || "Producto";
        }, function (item) {
            return numero(item.cantidad_solicitada) + " solicitados";
        }, "Sin pedidos");
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-09-29
     * Proposito: renderizar actividad comercial agregada.
     * Impacto: UI ERP Distribucion; compara interes, pedidos e inventario cliente.
     */
    function renderDemanda() {
        renderListaCompacta("dist_demanda_catalogo", demanda.productos_mi_catalogo || [], function (item) { return item.producto || item.sku || "Producto"; }, function (item) { return (item.clientes || 0) + " clientes"; }, "Sin datos");
        renderListaCompacta("dist_demanda_pedidos", demanda.productos_pedidos || [], function (item) { return item.producto || item.sku || "Producto"; }, function (item) { return numero(item.cantidad_solicitada) + " piezas solicitadas"; }, "Sin datos");
        renderListaCompacta("dist_demanda_clientes", demanda.clientes_activos || [], function (item) { return item.cliente || item.empresa || "Cliente"; }, function (item) { return (item.productos_mi_catalogo || 0) + " en Mi catalogo / " + (item.solicitudes || 0) + " solicitudes"; }, "Sin datos");
        renderListaCompacta("dist_demanda_marcas", demanda.marcas || [], function (item) { return item.nombre || "Sin marca"; }, function (item) { return (item.clientes || 0) + " clientes"; }, "Sin datos");
        renderListaCompacta("dist_demanda_categorias", demanda.categorias || [], function (item) { return item.nombre || "Sin categoria"; }, function (item) { return (item.clientes || 0) + " clientes"; }, "Sin datos");
        renderListaCompacta("dist_demanda_proveedores", demanda.proveedores || [], function (item) { return item.nombre || item.proveedor || "Sin proveedor"; }, function (item) { return (item.clientes || item.productos || 0) + " referencias"; }, "Sin datos");
    }

    function renderSolicitudes() {
        if (!document.getElementById("dist_solicitudes_lista")) { return; }
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
        if (!document.getElementById("dist_clientes_lista")) { return; }
        var q = (document.getElementById("dist_clientes_buscar").value || "").toLowerCase();
        var incompleto = selectValue("dist_clientes_incompletos");
        var visibles = clientes.filter(function (item) {
            if ([item.nombre, item.empresa, item.correo, item.telefono].join(" ").toLowerCase().indexOf(q) === -1) { return false; }
            if (incompleto === "sin_lista") { return !Number(item.id_lista_precio || 0); }
            if (incompleto === "sin_permisos") { return !Number(item.permisos_activos || 0); }
            return true;
        });
        document.getElementById("dist_clientes_lista").innerHTML = visibles.map(function (item) {
            var totalCatalogo = miCatalogoClientes.filter(function (fila) { return String(fila.id_cliente_distribucion) === String(item.id_cliente_distribucion); }).length;
            var totalInventario = inventarios.filter(function (fila) { return String(fila.id_cliente_distribucion) === String(item.id_cliente_distribucion); }).length;
            var totalSugeridos = sugeridos.filter(function (fila) { return String(fila.id_cliente_distribucion) === String(item.id_cliente_distribucion); }).length;
            var totalPedidos = cotizaciones.filter(function (fila) { return String(fila.id_cliente_distribucion) === String(item.id_cliente_distribucion); }).length;
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
                acciones += "<button class=\"btn btn-sm btn-icon btn-light-secondary\" title=\"Entrega\" data-cliente-entrega=\"" + escapeHtml(item.id_cliente_distribucion) + "\"><i class=\"bi bi-truck\"></i></button> ";
                acciones += "<button class=\"btn btn-sm btn-icon btn-light-success\" title=\"Catalogo\" data-cliente-catalogo=\"" + escapeHtml(item.id_cliente_distribucion) + "\"><i class=\"bi bi-journal-check\"></i></button> ";
            }
            acciones += "<button class=\"btn btn-sm btn-icon btn-light-info\" title=\"Historial de acceso\" data-cliente-auditoria=\"" + escapeHtml(item.id_cliente_distribucion) + "\"><i class=\"bi bi-clock-history\"></i></button> ";
            if (item.telefono) {
                acciones += "<button class=\"btn btn-sm btn-icon btn-light-success\" title=\"WhatsApp\" data-cliente-whatsapp=\"" + escapeHtml(item.id_cliente_distribucion) + "\"><i class=\"bi bi-whatsapp\"></i></button> ";
            }
            if (permisosUi.aprobar && item.estatus === "aprobado") {
                acciones += "<button class=\"btn btn-sm btn-icon btn-light-warning\" title=\"Nuevo link de acceso\" data-cliente-acceso=\"" + escapeHtml(item.id_cliente_distribucion) + "\"><i class=\"bi bi-key\"></i></button> ";
            }
            if (permisosUi.aprobar && item.estatus !== "suspendido") {
                acciones += "<button class=\"btn btn-sm btn-icon btn-light-danger\" title=\"Suspender\" data-cliente-suspender=\"" + escapeHtml(item.id_cliente_distribucion) + "\"><i class=\"bi bi-pause-circle\"></i></button>";
            }
            var indicadores = "<span class=\"badge badge-light-primary me-1\">Mi catalogo " + escapeHtml(totalCatalogo) + "</span>" +
                "<span class=\"badge badge-light-info me-1\">Pedidos " + escapeHtml(totalPedidos) + "</span>" +
                "<span class=\"badge badge-light me-1\">Inv. " + escapeHtml(totalInventario) + "</span>" +
                "<span class=\"badge " + (item.catalogo_modo === "personalizado" ? "badge-light-success" : "badge-light") + " me-1\">Catalogo " + escapeHtml(item.catalogo_modo || "general") + "</span>" +
                (totalSugeridos > 0 ? "<span class=\"badge badge-light-warning\">Sugerido " + escapeHtml(totalSugeridos) + "</span>" : "") +
                "<div class=\"text-muted fs-8 mt-1\">Permisos " + escapeHtml(item.permisos_activos || 0) + " / Entrega " + escapeHtml(item.metodo_entrega_default || "por_definir") + " / Ultimo acceso " + escapeHtml(item.fecha_ultimo_login || "sin acceso") + "</div>";
            return "<tr><td><div class=\"fw-bold\">" + escapeHtml(item.nombre) + "</div><div class=\"text-muted fs-7\">" + escapeHtml(item.correo) + "</div></td>" +
                "<td>" + tipo + "</td><td>" + lista + "</td><td>" + indicadores + "</td>" +
                "<td>" + badge(item.estatus) + "</td><td class=\"text-end\">" + acciones + "</td></tr>";
        }).join("") || "<tr><td colspan=\"6\" class=\"text-center text-muted py-10\">Sin clientes</td></tr>";
    }

    function renderCotizaciones() {
        if (!document.getElementById("dist_cotizaciones_lista")) { return; }
        var q = (selectValue("dist_cotizaciones_buscar") || "").toLowerCase();
        var visibles = cotizaciones.filter(function (item) {
            return [item.folio, item.cliente, item.empresa, item.correo, item.estatus].join(" ").toLowerCase().indexOf(q) !== -1;
        });
        document.getElementById("dist_cotizaciones_total").textContent = visibles.length;
        document.getElementById("dist_cotizaciones_lista").innerHTML = visibles.map(function (item) {
            var acciones = permisosUi.cotizaciones_gestionar
                ? "<button class=\"btn btn-sm btn-icon btn-light-info\" title=\"Tomar\" data-cotizacion-accion=\"tomar\" data-cotizacion=\"" + escapeHtml(item.id_cotizacion_distribucion) + "\"><i class=\"bi bi-person-check\"></i></button> " +
                  "<button class=\"btn btn-sm btn-icon btn-light-success\" title=\"Responder\" data-cotizacion-accion=\"responder\" data-cotizacion=\"" + escapeHtml(item.id_cotizacion_distribucion) + "\"><i class=\"bi bi-send-check\"></i></button> " +
                  "<button class=\"btn btn-sm btn-icon btn-light-dark\" title=\"Cerrar\" data-cotizacion-accion=\"cerrar\" data-cotizacion=\"" + escapeHtml(item.id_cotizacion_distribucion) + "\"><i class=\"bi bi-check2-circle\"></i></button>"
                : "";
            acciones = "<button class=\"btn btn-sm btn-icon btn-light-primary\" title=\"Detalle\" data-cotizacion-detalle=\"" + escapeHtml(item.id_cotizacion_distribucion) + "\"><i class=\"bi bi-card-list\"></i></button> " + acciones;
            var totalSolicitado = valorNumerico(item.total_solicitado_items);
            if (totalSolicitado === null) { totalSolicitado = valorNumerico(item.subtotal || item.total_estimado); }
            var totalConfirmado = valorNumerico(item.total_confirmado);
            if (totalConfirmado === null) { totalConfirmado = valorNumerico(item.total_confirmado_items); }
            var valorHtml = "<div class=\"fw-semibold\">" + money(totalConfirmado !== null ? totalConfirmado : totalSolicitado) + "</div>" +
                "<div class=\"text-muted fs-8\">Sol. " + moneyText(totalSolicitado) + " / Conf. " + moneyText(totalConfirmado) + "</div>";
            var estadoCliente = item.respuesta_cliente_estatus ? "<div class=\"text-muted fs-8\">Cliente: " + escapeHtml(item.respuesta_cliente_estatus) + "</div>" : "";
            var pendientes = Number(item.partidas_pendientes || 0);
            var revision = "<div><span class=\"badge " + (pendientes > 0 ? "badge-light-warning" : "badge-light-success") + "\">" + escapeHtml(item.partidas_revisadas || 0) + "/" + escapeHtml(item.partidas || 0) + "</span></div>" +
                "<div class=\"text-muted fs-8\">" + (pendientes > 0 ? escapeHtml(pendientes) + " pendientes" : "Completo") + "</div>";
            return "<tr><td><div class=\"fw-bold\">" + escapeHtml(item.folio) + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.tipo_entrega || "entrega por definir") + "</div></td>" +
                "<td><div class=\"fw-semibold\">" + escapeHtml(item.cliente || ("ID " + item.id_cliente_distribucion)) + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.empresa || item.correo || "") + "</div></td>" +
                "<td>" + revision + "</td>" +
                "<td class=\"text-end\"><div class=\"fw-semibold\">" + numero(item.cantidad_solicitada) + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.partidas || 0) + " partidas</div></td>" +
                "<td class=\"text-end\"><div class=\"fw-semibold\">" + numero(item.cantidad_confirmada) + "</div><div class=\"text-muted fs-8\">existencia revisada</div></td>" +
                "<td>" + valorHtml + "</td>" +
                "<td>" + badge(item.estatus) + estadoCliente + "</td><td class=\"text-muted fs-7\">" + escapeHtml(item.fecha_registro || "") + "</td><td class=\"text-end\">" + acciones + "</td></tr>";
        }).join("") || "<tr><td colspan=\"9\" class=\"text-center text-muted py-10\">Sin pedidos</td></tr>";
    }

    function filtroTexto(item) {
        return [
            item.cliente,
            item.empresa,
            item.correo,
            item.sku,
            item.nombre_sku,
            item.producto,
            item.alias_cliente,
            item.ubicacion_cliente
        ].join(" ").toLowerCase();
    }

    function renderSurtidos() {
        var lista = document.getElementById("dist_mi_catalogo_lista");
        if (!lista) { return; }
        var q = (document.getElementById("dist_mi_catalogo_buscar").value || "").toLowerCase();
        var visibles = miCatalogoClientes.filter(function (item) { return filtroTexto(item).indexOf(q) !== -1; });
        document.getElementById("dist_mi_catalogo_total").textContent = visibles.length;
        lista.innerHTML = visibles.map(function (item) {
            var alias = [item.alias_cliente, item.ubicacion_cliente].filter(Boolean).join(" / ");
            return "<tr><td><div class=\"fw-bold\">" + escapeHtml(item.cliente || ("Cliente " + item.id_cliente_distribucion)) + "</div><div class=\"text-muted fs-7\">" + escapeHtml(item.empresa || item.correo || "") + "</div></td>" +
                "<td><div class=\"fw-bold\">" + escapeHtml(item.nombre_sku || item.producto || "") + "</div><div class=\"text-muted fs-7\">" + escapeHtml(item.sku || ("SKU " + item.id_sku)) + "</div></td>" +
                "<td>" + escapeHtml(item.marca || "Sin marca") + "</td><td>" + escapeHtml(item.categoria || "Sin categoria") + "</td><td>" + escapeHtml(item.proveedor_principal || "Sin proveedor") + "</td>" +
                "<td>" + escapeHtml(alias || "Sin alias") + "</td><td>" + escapeHtml(item.prioridad || 0) + "</td><td class=\"text-muted fs-8\">" + escapeHtml(item.fecha_actualizacion || item.fecha_registro || "") + "</td></tr>";
        }).join("") || "<tr><td colspan=\"8\" class=\"text-center text-muted py-10\">Sin productos en Mi catalogo</td></tr>";
    }

    function renderInventarioTabla(items, listaId, totalId, vacio) {
        var lista = document.getElementById(listaId);
        if (!lista) { return; }
        document.getElementById(totalId).textContent = items.length;
        lista.innerHTML = items.map(function (item) {
            return "<tr><td><div class=\"fw-bold\">" + escapeHtml(item.cliente || ("Cliente " + item.id_cliente_distribucion)) + "</div><div class=\"text-muted fs-7\">" + escapeHtml(item.empresa || item.correo || "") + "</div></td>" +
                "<td><div class=\"fw-bold\">" + escapeHtml(item.nombre_sku || item.producto || "") + "</div><div class=\"text-muted fs-7\">" + escapeHtml(item.sku || ("SKU " + item.id_sku)) + "</div></td>" +
                "<td><div class=\"fw-semibold\">" + escapeHtml(item.marca || "Sin marca") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.categoria || "Sin categoria") + "</div></td>" +
                "<td>" + escapeHtml(item.proveedor_principal || "Sin proveedor") + "</td>" +
                "<td class=\"text-end\">" + numero(item.existencia_cliente) + "</td>" +
                "<td class=\"text-end\">" + numero(item.minimo) + " / " + numero(item.maximo) + "</td>" +
                "<td class=\"text-end fw-bold\">" + numero(item.cantidad_sugerida) + "</td>" +
                "<td class=\"text-muted fs-7\">" + escapeHtml(item.fecha_conteo || item.fecha_actualizacion || "") + "</td></tr>";
        }).join("") || "<tr><td colspan=\"8\" class=\"text-center text-muted py-10\">" + escapeHtml(vacio) + "</td></tr>";
    }

    function renderInventarios() {
        var q = (document.getElementById("dist_inventarios_buscar").value || "").toLowerCase();
        var estado = selectValue("dist_inventarios_estado");
        var visibles = inventarios.filter(function (item) {
            if (filtroTexto(item).indexOf(q) === -1) { return false; }
            if (estado === "debajo_minimo") { return Number(item.cantidad_sugerida || 0) > 0; }
            if (estado === "sin_min_max") { return Number(item.minimo || 0) <= 0 || Number(item.maximo || 0) <= 0; }
            return true;
        });
        renderInventarioTabla(visibles, "dist_inventarios_lista", "dist_inventarios_total", "Sin inventarios de clientes");
    }

    function renderSugeridos() {
        var q = (document.getElementById("dist_sugeridos_buscar").value || "").toLowerCase();
        var visibles = sugeridos.filter(function (item) { return filtroTexto(item).indexOf(q) !== -1; });
        renderInventarioTabla(visibles, "dist_sugeridos_lista", "dist_sugeridos_total", "Sin sugeridos de resurtido");
    }

    function renderProductos() {
        var lista = document.getElementById("dist_productos_lista");
        if (!lista) { return; }
        var selectAll = document.getElementById("dist_productos_select_all");
        if (selectAll) { selectAll.checked = false; }
        renderProductosPaginacion();
        lista.innerHTML = productos.map(function (item) {
            var activo = ["activo", "publicado", "aprobado"].indexOf(item.canal_estatus) !== -1 && Number(item.sincronizar_catalogo || 0) === 1;
            var acciones = permisosUi.editar
                ? (activo
                    ? "<button class=\"btn btn-sm btn-icon btn-light-danger\" title=\"Desactivar\" data-sku-desactivar=\"" + escapeHtml(item.id_sku) + "\" data-vinculo=\"" + escapeHtml(item.id_canal_vinculo || "") + "\"><i class=\"bi bi-eye-slash\"></i></button>"
                    : "<button class=\"btn btn-sm btn-icon btn-light-success\" title=\"Publicar\" data-sku-publicar=\"" + escapeHtml(item.id_sku) + "\"><i class=\"bi bi-cloud-upload\"></i></button>")
                : "";
            if (permisosUi.editar) {
                acciones += " <button class=\"btn btn-sm btn-icon btn-light-primary\" title=\"Asignar a cliente\" data-producto-asignar-cliente=\"" + escapeHtml(item.id_sku) + "\"><i class=\"bi bi-person-plus\"></i></button>";
            }
            return "<tr><td><input class=\"form-check-input\" type=\"checkbox\" data-producto-seleccion=\"" + escapeHtml(item.id_sku) + "\"></td><td><div class=\"fw-bold\">" + escapeHtml(item.sku) + "</div><div class=\"text-muted fs-7\">ID " + escapeHtml(item.id_sku) + "</div></td>" +
                "<td><div class=\"fw-bold\">" + escapeHtml(item.sku_nombre || item.producto) + "</div><div class=\"text-muted fs-7\">" + escapeHtml(item.producto || "") + "</div></td>" +
                "<td><div class=\"fw-semibold\">" + escapeHtml(item.marca || "Sin marca") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.categoria || "Sin categoria") + "</div></td>" +
                "<td>" + escapeHtml(item.proveedor_principal || "Sin proveedor") + "</td>" +
                "<td>" + (Number(item.tiene_precio || 0) === 1 ? "<span class=\"badge badge-light-success\">Precio</span> " : "<span class=\"badge badge-light-danger\">Sin precio</span> ") +
                    (Number(item.tiene_imagen || 0) === 1 ? "<span class=\"badge badge-light-success\">Imagen</span> " : "<span class=\"badge badge-light-warning\">Sin imagen</span> ") +
                    (Number(item.tiene_descripcion || 0) === 1 ? "<span class=\"badge badge-light-success\">Ficha</span>" : "<span class=\"badge badge-light-warning\">Sin ficha</span>") + "</td>" +
                "<td class=\"text-muted fs-7\">" + escapeHtml(item.id_externo || "Sin publicar") + "</td><td>" + badge(item.canal_estatus || "sin_vinculo") + "</td>" +
                "<td class=\"text-end\">" + acciones + "</td></tr>";
        }).join("") || "<tr><td colspan=\"9\" class=\"text-center text-muted py-10\">Sin SKUs publicables</td></tr>";
    }

    function renderProductosPaginacion() {
        var total = Number(productosPaginacion.total || 0);
        var pagina = Number(productosPaginacion.pagina || 1);
        var limite = Number(productosPaginacion.limite || 120);
        var totalPaginas = Math.max(1, Number(productosPaginacion.total_paginas || 1));
        var inicio = total === 0 ? 0 : ((pagina - 1) * limite) + 1;
        var fin = Math.min(total, (pagina - 1) * limite + productos.length);
        var info = document.getElementById("dist_productos_paginacion_info");
        var actual = document.getElementById("dist_productos_pagina_actual");
        var anterior = document.getElementById("dist_productos_pagina_anterior");
        var siguiente = document.getElementById("dist_productos_pagina_siguiente");
        if (info) {
            info.textContent = "Mostrando " + inicio + "-" + fin + " de " + total + " productos";
        }
        if (actual) {
            actual.textContent = pagina + " / " + totalPaginas;
        }
        if (anterior) {
            anterior.disabled = pagina <= 1;
        }
        if (siguiente) {
            siguiente.disabled = pagina >= totalPaginas;
        }
    }

    function cargarSolicitudes() {
        var estatus = document.getElementById("dist_solicitudes_estatus").value;
        return request("/DistribucionAdmin/solicitudes?limite=100&estatus=" + encodeURIComponent(estatus)).then(function (response) {
            solicitudes = response.depurar && response.depurar.items ? response.depurar.items : [];
            renderSolicitudes();
        });
    }

    function cargarClientes() {
        var estatus = selectValue("dist_clientes_estatus");
        return request("/DistribucionAdmin/clientes?limite=100&estatus=" + encodeURIComponent(estatus)).then(function (response) {
            clientes = response.depurar && response.depurar.items ? response.depurar.items : [];
            renderClientes();
        });
    }

    function cargarCotizaciones() {
        return request("/DistribucionAdmin/cotizaciones?" + query({
            limite: 100,
            estatus: selectValue("dist_cotizaciones_estatus"),
            q: selectValue("dist_cotizaciones_buscar"),
            fecha_desde: selectValue("dist_cotizaciones_fecha_desde"),
            fecha_hasta: selectValue("dist_cotizaciones_fecha_hasta")
        })).then(function (response) {
            cotizaciones = response.depurar && response.depurar.items ? response.depurar.items : [];
            renderCotizaciones();
        });
    }

    function cargarSurtidos() {
        return request("/DistribucionAdmin/cliente_mi_catalogo?" + query({
            limite: 200,
            q: selectValue("dist_mi_catalogo_buscar"),
            id_marca_erp: selectValue("dist_mi_catalogo_marca"),
            id_categoria_erp: selectValue("dist_mi_catalogo_categoria"),
            id_proveedor: selectValue("dist_mi_catalogo_proveedor")
        })).then(function (response) {
            miCatalogoClientes = response.depurar && response.depurar.items ? response.depurar.items : [];
            renderSurtidos();
        });
    }

    function cargarInventarios() {
        return request("/DistribucionAdmin/cliente_inventarios?" + query({
            limite: 200,
            q: selectValue("dist_inventarios_buscar"),
            id_marca_erp: selectValue("dist_inventarios_marca"),
            id_categoria_erp: selectValue("dist_inventarios_categoria"),
            id_proveedor: selectValue("dist_inventarios_proveedor")
        })).then(function (response) {
            inventarios = response.depurar && response.depurar.items ? response.depurar.items : [];
            renderInventarios();
        });
    }

    function cargarSugeridos() {
        return request("/DistribucionAdmin/cliente_sugeridos?" + query({
            limite: 200,
            q: selectValue("dist_sugeridos_buscar"),
            id_marca_erp: selectValue("dist_sugeridos_marca"),
            id_categoria_erp: selectValue("dist_sugeridos_categoria"),
            id_proveedor: selectValue("dist_sugeridos_proveedor")
        })).then(function (response) {
            sugeridos = response.depurar && response.depurar.items ? response.depurar.items : [];
            renderSugeridos();
        });
    }

    function cargarProductos() {
        var q = "";
        var input = document.getElementById("dist_productos_buscar");
        if (input) { q = input.value || ""; }
        productosPaginacion.limite = Number(selectValue("dist_productos_limite") || productosPaginacion.limite || 120);
        return request("/DistribucionAdmin/skus_publicables?" + query(productosQueryFiltros(productosPaginacion.limite, productosPaginacion.pagina))).then(function (response) {
            productos = response.depurar && response.depurar.items ? response.depurar.items : [];
            productosPaginacion = response.depurar && response.depurar.paginacion ? response.depurar.paginacion : productosPaginacion;
            renderProductos();
        });
    }

    function productosQueryFiltros(limite, pagina) {
        var input = document.getElementById("dist_productos_buscar");
        return {
            limite: limite,
            pagina: pagina,
            q: input ? (input.value || "") : "",
            id_marca_erp: selectValue("dist_productos_marca"),
            id_categoria_erp: selectValue("dist_productos_categoria"),
            id_proveedor: selectValue("dist_productos_proveedor"),
            canal_estatus: selectValue("dist_productos_canal"),
            precio: selectValue("dist_productos_precio"),
            imagen: selectValue("dist_productos_imagen"),
            ficha: selectValue("dist_productos_ficha")
        };
    }

    function cargarProductosDesdePagina(pagina) {
        productosPaginacion.pagina = Math.max(1, Number(pagina || 1));
        return cargarProductos();
    }

    function reiniciarProductosYCargar() {
        productosPaginacion.pagina = 1;
        return cargarProductos();
    }

    function cargarResumen() {
        return request("/DistribucionAdmin/resumen").then(function (response) {
            resumen = response.depurar || {};
            renderResumen();
        });
    }

    function cargarDemanda() {
        return request("/DistribucionAdmin/demanda").then(function (response) {
            demanda = response.depurar || {};
            renderDemanda();
        });
    }

    function cargarCatalogosFiltros() {
        return request("/DistribucionAdmin/catalogos_filtros").then(function (response) {
            catalogosFiltros = response.depurar || {marcas: [], categorias: [], proveedores: []};
            llenarSelect(".dist-filtro-marca", catalogosFiltros.marcas || [], "Marca");
            llenarSelect(".dist-filtro-categoria", catalogosFiltros.categorias || [], "Categoria");
            llenarSelect(".dist-filtro-proveedor", catalogosFiltros.proveedores || [], "Proveedor");
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
        promesas.push(cargarCatalogosFiltros());
        return Promise.all(promesas);
    }

    function cargarTodo() {
        document.querySelectorAll("#dist_productos_publicar_lote, #dist_productos_publicar_filtrados, #dist_productos_asignar_cliente, #dist_productos_desactivar_lote").forEach(function (button) {
            button.classList.toggle("d-none", !permisosUi.editar);
        });
        return cargarAuxiliares().then(function () {
            if (seccionActiva === "solicitudes") {
                return cargarSolicitudes();
            }
            if (seccionActiva === "clientes") {
                return Promise.all([cargarClientes(), cargarCotizaciones(), cargarSurtidos(), cargarInventarios(), cargarSugeridos()]).then(function () { renderClientes(); });
            }
            if (seccionActiva === "pedidos") {
                return cargarCotizaciones();
            }
            if (seccionActiva === "mi_catalogo") {
                return cargarSurtidos();
            }
            if (seccionActiva === "inventarios") {
                return cargarInventarios();
            }
            if (seccionActiva === "sugeridos") {
                return cargarSugeridos();
            }
            if (seccionActiva === "productos") {
                return Promise.all([cargarClientes(), cargarProductos()]);
            }
            if (seccionActiva === "demanda") {
                return cargarDemanda();
            }
            return cargarResumen();
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
                "<label class=\"form-label fw-semibold\">Contrasenia temporal</label><input id=\"dist_aprobar_contrasenia\" type=\"password\" class=\"form-control form-control-solid mb-2\" placeholder=\"Dejar vacio para definir despues\"><div class=\"text-muted fs-8 mb-5\">Minimo 8 caracteres si se captura aqui.</div>" +
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
                var contrasenia = (document.getElementById("dist_aprobar_contrasenia") || {}).value || "";
                if (contrasenia && contrasenia.length < 8) {
                    Swal.showValidationMessage("La contrasenia temporal debe tener al menos 8 caracteres.");
                    return false;
                }
                document.querySelectorAll(".swal2-container input[type='checkbox']:checked").forEach(function (input) {
                    permisos.push(input.value);
                });
                return {
                    contrasenia: contrasenia,
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
                    confirmarWhatsAppActivacion(solicitud, response.depurar ? response.depurar.activacion : null);
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

    function categoriasInteresTexto(cliente) {
        var categorias = arraySeguro(cliente ? cliente.categorias_interes : []).map(function (item) {
            if (item && typeof item === "object") {
                return item.ruta || item.nombre || item.categoria || item.id_categoria_erp || item.id || "";
            }
            return item;
        }).filter(Boolean);
        return categorias.length ? categorias.join(", ") : "Sin categorias capturadas";
    }

    function opcionesCatalogoReglaHtml(tipo) {
        var items = [];
        if (tipo === "sku") {
            items = productos.slice(0, 250).map(function (item) {
                return {
                    id: item.id_sku,
                    nombre: (item.sku || ("SKU " + item.id_sku)) + (item.sku_nombre || item.producto ? " - " + (item.sku_nombre || item.producto) : "")
                };
            });
        } else if (tipo === "categoria") {
            items = (catalogosFiltros.categorias || []).map(function (item) {
                return {id: item.id, nombre: item.nombre};
            });
        } else if (tipo === "marca") {
            items = (catalogosFiltros.marcas || []).map(function (item) {
                return {id: item.id, nombre: item.nombre};
            });
        }
        return items.filter(function (item) { return item.id; }).map(function (item) {
            return "<option value=\"" + escapeHtml(item.id) + "\">" + escapeHtml(item.nombre || item.id) + "</option>";
        }).join("");
    }

    function actualizarAyudaReglaCatalogo() {
        var tipo = selectValue("dist_catalogo_regla_tipo") || "sku";
        var input = document.getElementById("dist_catalogo_regla_objeto");
        var lista = document.getElementById("dist_catalogo_regla_opciones");
        var ayuda = document.getElementById("dist_catalogo_regla_ayuda");
        if (lista) { lista.innerHTML = opcionesCatalogoReglaHtml(tipo); }
        if (input) {
            input.placeholder = tipo === "sku" ? "ID SKU" : (tipo === "categoria" ? "ID categoria" : "ID marca");
        }
        if (ayuda) {
            ayuda.textContent = tipo === "sku"
                ? "Puedes elegir un SKU cargado en Productos o escribir el ID SKU."
                : (tipo === "categoria" ? "Puedes elegir una categoria del catalogo o escribir su ID." : "Puedes elegir una marca del catalogo o escribir su ID.");
        }
    }

    function reglaCatalogoNombre(regla) {
        if (regla.tipo_regla === "sku") {
            return (regla.sku || ("SKU " + regla.id_sku)) + (regla.sku_nombre ? " - " + regla.sku_nombre : "");
        }
        if (regla.tipo_regla === "categoria") {
            return (regla.categoria_nombre || ("Categoria " + regla.id_categoria_erp)) + (regla.categoria_ruta ? " / " + regla.categoria_ruta : "");
        }
        if (regla.tipo_regla === "marca") {
            return regla.marca_nombre || ("Marca " + regla.id_marca_erp);
        }
        return regla.objeto_clave || "Regla";
    }

    function renderReglasCatalogoCliente(reglas, accion) {
        var filtradas = (reglas || []).filter(function (regla) { return regla.accion === accion; });
        if (!filtradas.length) {
            return "<div class=\"text-muted border rounded p-4\">Sin reglas " + escapeHtml(accion) + ".</div>";
        }
        return "<div class=\"table-responsive\"><table class=\"table table-sm align-middle mb-0\"><thead><tr class=\"text-muted fw-bold fs-8 text-uppercase\"><th>Objeto</th><th>Estado</th><th>Prioridad</th><th>Notas</th></tr></thead><tbody>" +
            filtradas.map(function (regla) {
                return "<tr><td><div class=\"fw-semibold\">" + escapeHtml(reglaCatalogoNombre(regla)) + "</div><div class=\"text-muted fs-8\">" + escapeHtml(regla.tipo_regla + " / " + (regla.objeto_clave || "")) + "</div></td>" +
                    "<td>" + badge(regla.estatus || "activo") + "</td>" +
                    "<td>" + escapeHtml(regla.prioridad || 0) + "</td>" +
                    "<td class=\"text-muted fs-8\">" + escapeHtml(regla.notas || "") + "</td></tr>";
            }).join("") + "</tbody></table></div>";
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-10-07
     * Proposito: administrar reglas de catalogo personalizado desde la ficha del cliente.
     * Impacto: UI ERP Distribucion; separa permitidos/ocultos por cliente sin modificar catalogo global.
     */
    function verCatalogoCliente(idCliente) {
        var cliente = clientes.find(function (item) { return String(item.id_cliente_distribucion) === String(idCliente); });
        if (!cliente) {
            Swal.fire({text: "Cliente no encontrado en la lista actual.", icon: "warning", confirmButtonText: "Aceptar"});
            return;
        }
        request("/DistribucionAdmin/cliente_catalogo_reglas?id_cliente_distribucion=" + encodeURIComponent(idCliente)).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            var reglas = response.depurar && response.depurar.items ? response.depurar.items : [];
            var permitidosActivos = reglas.filter(function (regla) { return regla.accion === "permitir" && regla.estatus === "activo"; }).length;
            var aviso = cliente.catalogo_modo === "personalizado" && permitidosActivos === 0
                ? "<div class=\"alert alert-warning py-3 mb-5\">Este cliente esta en modo personalizado y aun no tiene reglas activas para permitir productos.</div>"
                : "";
            var html = "<div class=\"text-start\">" +
                "<div class=\"mb-5\"><div class=\"fw-bold fs-5\">" + escapeHtml(cliente.nombre || "Cliente") + "</div><div class=\"text-muted\">" + escapeHtml([cliente.empresa, cliente.correo].filter(Boolean).join(" / ")) + "</div></div>" +
                aviso +
                "<div class=\"row g-4 mb-5\">" +
                "<div class=\"col-md-6\"><label class=\"form-label fw-semibold\">Modo de catalogo</label><select id=\"dist_catalogo_cliente_modo\" class=\"form-select form-select-solid\"><option value=\"general\"" + (cliente.catalogo_modo !== "personalizado" ? " selected" : "") + ">General</option><option value=\"personalizado\"" + (cliente.catalogo_modo === "personalizado" ? " selected" : "") + ">Personalizado</option></select></div>" +
                "<div class=\"col-md-6\"><label class=\"form-label fw-semibold\">Categorias de interes</label><input class=\"form-control form-control-solid\" value=\"" + escapeHtml(categoriasInteresTexto(cliente)) + "\" readonly></div>" +
                "</div>" +
                "<div class=\"d-flex justify-content-end mb-6\"><button type=\"button\" class=\"btn btn-sm btn-primary\" data-cliente-catalogo-preferencias=\"" + escapeHtml(idCliente) + "\"><i class=\"bi bi-save\"></i> Guardar modo</button></div>" +
                "<div class=\"row g-5 mb-6\"><div class=\"col-lg-6\"><div class=\"fw-semibold mb-3\">Permitidos</div>" + renderReglasCatalogoCliente(reglas, "permitir") + "</div><div class=\"col-lg-6\"><div class=\"fw-semibold mb-3\">Ocultos</div>" + renderReglasCatalogoCliente(reglas, "ocultar") + "</div></div>" +
                "<div class=\"separator my-5\"></div><div class=\"fw-semibold mb-3\">Agregar o actualizar regla</div>" +
                "<div class=\"row g-3\">" +
                "<div class=\"col-md-3\"><label class=\"form-label\">Tipo</label><select id=\"dist_catalogo_regla_tipo\" class=\"form-select form-select-solid\"><option value=\"sku\">SKU</option><option value=\"categoria\">Categoria</option><option value=\"marca\">Marca</option></select></div>" +
                "<div class=\"col-md-3\"><label class=\"form-label\">Accion</label><select id=\"dist_catalogo_regla_accion\" class=\"form-select form-select-solid\"><option value=\"permitir\">Permitir</option><option value=\"ocultar\">Ocultar</option></select></div>" +
                "<div class=\"col-md-3\"><label class=\"form-label\">Objeto</label><input id=\"dist_catalogo_regla_objeto\" class=\"form-control form-control-solid\" inputmode=\"numeric\" list=\"dist_catalogo_regla_opciones\" placeholder=\"ID SKU\"><datalist id=\"dist_catalogo_regla_opciones\"></datalist><div id=\"dist_catalogo_regla_ayuda\" class=\"text-muted fs-8 mt-1\"></div></div>" +
                "<div class=\"col-md-3\"><label class=\"form-label\">Prioridad</label><input id=\"dist_catalogo_regla_prioridad\" class=\"form-control form-control-solid\" inputmode=\"numeric\" value=\"10\"></div>" +
                "<div class=\"col-md-3\"><label class=\"form-label\">Estatus</label><select id=\"dist_catalogo_regla_estatus\" class=\"form-select form-select-solid\"><option value=\"activo\">Activo</option><option value=\"inactivo\">Inactivo</option></select></div>" +
                "<div class=\"col-md-9\"><label class=\"form-label\">Notas</label><input id=\"dist_catalogo_regla_notas\" class=\"form-control form-control-solid\" maxlength=\"250\" placeholder=\"Motivo o referencia interna\"></div>" +
                "</div><div class=\"d-flex justify-content-end mt-5\"><button type=\"button\" class=\"btn btn-success\" data-cliente-catalogo-regla=\"" + escapeHtml(idCliente) + "\"><i class=\"bi bi-plus-circle\"></i> Guardar regla</button></div>" +
                "</div>";
            Swal.fire({
                title: "Catalogo del cliente",
                html: html,
                width: 980,
                showConfirmButton: false,
                showCloseButton: true,
                didOpen: function () {
                    var btnPreferencias = document.querySelector("[data-cliente-catalogo-preferencias]");
                    var btnRegla = document.querySelector("[data-cliente-catalogo-regla]");
                    if (btnPreferencias) {
                        btnPreferencias.addEventListener("click", function () { guardarCatalogoPreferenciasCliente(idCliente); });
                    }
                    if (btnRegla) {
                        btnRegla.addEventListener("click", function () { guardarCatalogoReglaCliente(idCliente); });
                    }
                    var tipoRegla = document.getElementById("dist_catalogo_regla_tipo");
                    if (tipoRegla) {
                        tipoRegla.addEventListener("change", actualizarAyudaReglaCatalogo);
                    }
                    actualizarAyudaReglaCatalogo();
                }
            });
        }).catch(showError);
    }

    function guardarCatalogoPreferenciasCliente(idCliente) {
        var modo = selectValue("dist_catalogo_cliente_modo") || "general";
        request("/DistribucionAdmin/cliente_catalogo_preferencias", {
            id_cliente_distribucion: idCliente,
            catalogo_modo: modo,
            categorias_interes: JSON.stringify(arraySeguro((clientes.find(function (item) { return String(item.id_cliente_distribucion) === String(idCliente); }) || {}).categorias_interes))
        }).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            showOk(response.mensaje);
            return cargarClientes().then(function () { verCatalogoCliente(idCliente); });
        }).catch(showError);
    }

    function guardarCatalogoReglaCliente(idCliente) {
        var tipo = selectValue("dist_catalogo_regla_tipo");
        var accion = selectValue("dist_catalogo_regla_accion");
        var objeto = Number((document.getElementById("dist_catalogo_regla_objeto") || {}).value || 0);
        if (["sku", "categoria", "marca"].indexOf(tipo) === -1 || ["permitir", "ocultar"].indexOf(accion) === -1 || objeto <= 0) {
            Swal.fire({text: "Selecciona tipo, accion y un ID valido para la regla.", icon: "warning", confirmButtonText: "Aceptar"});
            return;
        }
        var data = {
            id_cliente_distribucion: idCliente,
            tipo_regla: tipo,
            accion: accion,
            prioridad: (document.getElementById("dist_catalogo_regla_prioridad") || {}).value || 0,
            estatus: selectValue("dist_catalogo_regla_estatus") || "activo",
            notas: (document.getElementById("dist_catalogo_regla_notas") || {}).value || "",
            origen: "admin"
        };
        data[tipo === "sku" ? "id_sku" : (tipo === "categoria" ? "id_categoria_erp" : "id_marca_erp")] = objeto;
        request("/DistribucionAdmin/cliente_catalogo_regla_guardar", data).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            showOk(response.mensaje);
            verCatalogoCliente(idCliente);
        }).catch(showError);
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

    function editarEntregaCliente(idCliente) {
        var cliente = clientes.find(function (item) { return String(item.id_cliente_distribucion) === String(idCliente); }) || {};
        Swal.fire({
            title: cliente.nombre || "Entrega cliente",
            html: "<div class=\"text-start\">" +
                "<label class=\"form-label fw-semibold\">Metodo default</label><select id=\"dist_cliente_metodo_entrega\" class=\"form-select form-select-solid mb-4\">" +
                ["por_definir", "envio", "recoger_tienda"].map(function (metodo) {
                    return "<option value=\"" + metodo + "\"" + ((cliente.metodo_entrega_default || "por_definir") === metodo ? " selected" : "") + ">" + metodo + "</option>";
                }).join("") + "</select>" +
                "<label class=\"form-label fw-semibold\">Costo de envio default</label><input id=\"dist_cliente_costo_envio\" class=\"form-control form-control-solid mb-4\" inputmode=\"decimal\" value=\"" + escapeHtml(cliente.costo_envio_default || 0) + "\">" +
                "<label class=\"form-check form-check-custom form-check-solid mb-3\"><input id=\"dist_cliente_habilitar_envio\" class=\"form-check-input\" type=\"checkbox\"" + (Number(cliente.entrega_habilitar_envio || 1) === 1 ? " checked" : "") + "><span class=\"form-check-label\">Puede usar envio</span></label>" +
                "<label class=\"form-check form-check-custom form-check-solid\"><input id=\"dist_cliente_habilitar_recoger\" class=\"form-check-input\" type=\"checkbox\"" + (Number(cliente.entrega_habilitar_recoger_tienda || 1) === 1 ? " checked" : "") + "><span class=\"form-check-label\">Puede recoger en tienda</span></label>" +
                "</div>",
            width: 620,
            showCancelButton: true,
            confirmButtonText: "Guardar entrega",
            preConfirm: function () {
                return {
                    metodo_entrega_default: (document.getElementById("dist_cliente_metodo_entrega") || {}).value || "por_definir",
                    costo_envio_default: (document.getElementById("dist_cliente_costo_envio") || {}).value || "0",
                    entrega_habilitar_envio: (document.getElementById("dist_cliente_habilitar_envio") || {}).checked ? 1 : 0,
                    entrega_habilitar_recoger_tienda: (document.getElementById("dist_cliente_habilitar_recoger") || {}).checked ? 1 : 0
                };
            }
        }).then(function (result) {
            if (!result.isConfirmed) { return; }
            accionSimple("/DistribucionAdmin/cliente_entrega_configurar", Object.assign({id_cliente_distribucion: idCliente}, result.value || {}));
        });
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-09-29
     * Proposito: mostrar partidas de pedido/cotizacion y permitir revision interna controlada.
     * Impacto: UI ERP Distribucion; no aparta inventario ni crea venta/pedido ERP.
     */
    function resumenPedido(cotizacion, items) {
        var cantidadSolicitada = 0;
        var cantidadConfirmada = 0;
        var totalSolicitado = 0;
        var tieneTotalSolicitado = false;
        var totalConfirmado = 0;
        var tieneTotalConfirmado = false;
        var pendientes = 0;
        (items || []).forEach(function (item) {
            var cantidad = Number(item.cantidad || 0);
            var confirmada = Number(item.cantidad_confirmada || 0);
            var precio = valorNumerico(item.precio_unitario_snapshot);
            cantidadSolicitada += cantidad;
            cantidadConfirmada += confirmada;
            if (valorNumerico(item.subtotal_snapshot) !== null) {
                totalSolicitado += Number(item.subtotal_snapshot);
                tieneTotalSolicitado = true;
            } else if (precio !== null) {
                totalSolicitado += cantidad * precio;
                tieneTotalSolicitado = true;
            }
            if (precio !== null && confirmada > 0) {
                totalConfirmado += confirmada * precio;
                tieneTotalConfirmado = true;
            }
            if (!item.estatus_revision || ["por_confirmar", "requiere_revision", "pendiente_proveedor"].indexOf(item.estatus_revision) !== -1) {
                pendientes++;
            }
        });
        var costoEnvio = valorNumerico(cotizacion.costo_envio);
        if (costoEnvio !== null && costoEnvio > 0 && tieneTotalConfirmado) {
            totalConfirmado += costoEnvio;
        }
        var totalConfirmadoGuardado = valorNumerico(cotizacion.total_confirmado);
        if (totalConfirmadoGuardado !== null) {
            totalConfirmado = totalConfirmadoGuardado;
            tieneTotalConfirmado = true;
        }
        return {
            cantidad_solicitada: cantidadSolicitada,
            cantidad_confirmada: cantidadConfirmada,
            total_solicitado: tieneTotalSolicitado ? totalSolicitado : valorNumerico(cotizacion.subtotal || cotizacion.total_estimado),
            total_confirmado: tieneTotalConfirmado ? totalConfirmado : null,
            pendientes: pendientes
        };
    }

    function abrirImpresionPedido(cotizacion, items) {
        var resumen = resumenPedido(cotizacion, items);
        var filas = (items || []).map(function (item) {
            var cantidad = Number(item.cantidad || 0);
            var confirmada = Number(item.cantidad_confirmada || 0);
            var precio = valorNumerico(item.precio_unitario_snapshot);
            var solicitado = valorNumerico(item.subtotal_snapshot);
            if (solicitado === null && precio !== null) { solicitado = cantidad * precio; }
            var confirmado = precio !== null && confirmada > 0 ? confirmada * precio : null;
            return "<tr><td><strong>" + escapeHtml(item.producto_actual || item.nombre_snapshot || "") + "</strong><br><span>" + escapeHtml(item.sku_actual || item.sku_snapshot || ("SKU " + item.id_sku)) + "</span></td>" +
                "<td class=\"num\">" + escapeHtml(numero(cantidad)) + "</td><td class=\"num\">" + escapeHtml(numero(confirmada)) + "</td><td>" + escapeHtml(item.estatus_revision || "por_confirmar") + "</td>" +
                "<td class=\"num\">" + escapeHtml(moneyText(precio)) + "</td><td class=\"num\">" + escapeHtml(moneyText(solicitado)) + "</td><td class=\"num\">" + escapeHtml(moneyText(confirmado)) + "</td>" +
                "<td>" + escapeHtml(item.comentario_revision || "") + "</td></tr>";
        }).join("");
        var ventana = window.open("", "_blank", "width=1100,height=800");
        if (!ventana) {
            Swal.fire({text: "No se pudo abrir la ventana de impresion. Revisa si el navegador bloqueo ventanas emergentes.", icon: "warning", confirmButtonText: "Aceptar"});
            return;
        }
        ventana.document.write("<!doctype html><html><head><meta charset=\"utf-8\"><title>" + escapeHtml(cotizacion.folio || "Pedido Distribucion") + "</title>" +
            "<style>body{font-family:Arial,sans-serif;color:#222;margin:24px}h1{font-size:22px;margin:0 0 4px}.muted{color:#666;font-size:12px}.grid{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin:18px 0}.box{border:1px solid #ddd;padding:10px;border-radius:6px}.label{font-size:11px;color:#666;text-transform:uppercase}.value{font-weight:700;margin-top:4px}table{width:100%;border-collapse:collapse;margin-top:18px;font-size:12px}th,td{border-bottom:1px solid #ddd;padding:8px;text-align:left;vertical-align:top}th{background:#f5f5f5}.num{text-align:right}@media print{button{display:none}}</style></head><body>" +
            "<button onclick=\"window.print()\">Imprimir</button><h1>" + escapeHtml(cotizacion.folio || "Pedido Distribucion") + "</h1>" +
            "<div class=\"muted\">" + escapeHtml([cotizacion.cliente, cotizacion.empresa, cotizacion.correo, cotizacion.estatus].filter(Boolean).join(" / ")) + "</div>" +
            "<div class=\"grid\"><div class=\"box\"><div class=\"label\">Solicitado</div><div class=\"value\">" + escapeHtml(numero(resumen.cantidad_solicitada)) + "</div></div>" +
            "<div class=\"box\"><div class=\"label\">Confirmado</div><div class=\"value\">" + escapeHtml(numero(resumen.cantidad_confirmada)) + "</div></div>" +
            "<div class=\"box\"><div class=\"label\">Valor solicitado</div><div class=\"value\">" + escapeHtml(moneyText(resumen.total_solicitado)) + "</div></div>" +
            "<div class=\"box\"><div class=\"label\">Valor confirmado</div><div class=\"value\">" + escapeHtml(moneyText(resumen.total_confirmado)) + "</div></div></div>" +
            "<table><thead><tr><th>Producto</th><th class=\"num\">Solicitado</th><th class=\"num\">Confirmado</th><th>Revision</th><th class=\"num\">Precio</th><th class=\"num\">Importe sol.</th><th class=\"num\">Importe conf.</th><th>Comentario</th></tr></thead><tbody>" + filas + "</tbody></table>" +
            "</body></html>");
        ventana.document.close();
        ventana.focus();
    }

    function verCotizacionDetalle(idCotizacion) {
        request("/DistribucionAdmin/cotizacion_detalle?id_cotizacion_distribucion=" + encodeURIComponent(idCotizacion)).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            var cotizacion = response.depurar && response.depurar.cotizacion ? response.depurar.cotizacion : {};
            var items = response.depurar && response.depurar.items ? response.depurar.items : [];
            var facturacion = jsonSeguro(cotizacion.facturacion_json);
            var requiereFactura = Number(cotizacion.requiere_factura || facturacion.requiere_factura || 0) === 1;
            var resumen = resumenPedido(cotizacion, items);
            var filas = items.map(function (item) {
                var revision = item.estatus_revision ? badge(item.estatus_revision) : "<span class=\"text-muted\">Por confirmar</span>";
                var boton = permisosUi.cotizaciones_gestionar
                    ? "<button class=\"btn btn-sm btn-icon btn-light-primary\" title=\"Revisar partida\" data-cotizacion-item-revisar=\"" + escapeHtml(item.id_cotizacion_item) + "\"><i class=\"bi bi-pencil-square\"></i></button>"
                    : "";
                var cantidad = Number(item.cantidad || 0);
                var confirmada = Number(item.cantidad_confirmada || 0);
                var precio = valorNumerico(item.precio_unitario_snapshot);
                var solicitado = valorNumerico(item.subtotal_snapshot);
                if (solicitado === null && precio !== null) { solicitado = cantidad * precio; }
                var confirmado = precio !== null && confirmada > 0 ? confirmada * precio : null;
                return "<tr><td><div class=\"fw-semibold\">" + escapeHtml(item.producto_actual || item.nombre_snapshot || "") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.sku_actual || item.sku_snapshot || ("SKU " + item.id_sku)) + "</div></td>" +
                    "<td class=\"text-end fw-semibold\">" + numero(cantidad) + "</td><td class=\"text-end\">" + numero(confirmada) + "</td>" +
                    "<td class=\"text-end\">" + money(precio) + "</td><td class=\"text-end\">" + money(solicitado) + "</td><td class=\"text-end\">" + money(confirmado) + "</td>" +
                    "<td>" + revision + "<div class=\"text-muted fs-8 mt-1\">" + escapeHtml(item.comentario_revision || "") + "</div></td><td class=\"text-end\">" + boton + "</td></tr>";
            }).join("") || "<tr><td colspan=\"8\" class=\"text-center text-muted py-6\">Sin partidas</td></tr>";
            var resumenHtml = "<div class=\"dist-order-kpis text-start mb-5\">" +
                "<div class=\"dist-order-kpi\"><div class=\"text-muted fs-8 text-uppercase\">Solicitado</div><div class=\"value\">" + escapeHtml(numero(resumen.cantidad_solicitada)) + "</div></div>" +
                "<div class=\"dist-order-kpi\"><div class=\"text-muted fs-8 text-uppercase\">Confirmado</div><div class=\"value\">" + escapeHtml(numero(resumen.cantidad_confirmada)) + "</div></div>" +
                "<div class=\"dist-order-kpi\"><div class=\"text-muted fs-8 text-uppercase\">Valor solicitado</div><div class=\"value\">" + money(resumen.total_solicitado) + "</div></div>" +
                "<div class=\"dist-order-kpi\"><div class=\"text-muted fs-8 text-uppercase\">Valor confirmado</div><div class=\"value\">" + money(resumen.total_confirmado) + "</div></div>" +
                "<div class=\"dist-order-kpi\"><div class=\"text-muted fs-8 text-uppercase\">Partidas pendientes</div><div class=\"value\">" + escapeHtml(resumen.pendientes) + "</div></div>" +
                "</div>";
            var entregaHtml = "<div class=\"row g-3 text-start mb-5\">" +
                "<div class=\"col-md-3\"><label class=\"form-label fw-semibold\">Entrega</label><select id=\"dist_pedido_tipo_entrega\" class=\"form-select form-select-solid\">" +
                ["por_definir", "envio", "recoger_tienda"].map(function (tipo) {
                    return "<option value=\"" + tipo + "\"" + ((cotizacion.tipo_entrega || "por_definir") === tipo ? " selected" : "") + ">" + tipo + "</option>";
                }).join("") + "</select></div>" +
                "<div class=\"col-md-3\"><label class=\"form-label fw-semibold\">Costo envio</label><input id=\"dist_pedido_costo_envio\" class=\"form-control form-control-solid\" inputmode=\"decimal\" value=\"" + escapeHtml(cotizacion.costo_envio || 0) + "\"></div>" +
                "<div class=\"col-md-3 d-flex align-items-end\"><label class=\"form-check form-check-custom form-check-solid mb-3\"><input id=\"dist_pedido_habilitar_envio\" class=\"form-check-input\" type=\"checkbox\"" + (Number(cotizacion.entrega_habilitar_envio || 1) === 1 ? " checked" : "") + "><span class=\"form-check-label\">Envio</span></label></div>" +
                "<div class=\"col-md-3 d-flex align-items-end\"><label class=\"form-check form-check-custom form-check-solid mb-3\"><input id=\"dist_pedido_habilitar_recoger\" class=\"form-check-input\" type=\"checkbox\"" + (Number(cotizacion.entrega_habilitar_recoger_tienda || 1) === 1 ? " checked" : "") + "><span class=\"form-check-label\">Recoger</span></label></div>" +
                "<div class=\"col-12 d-flex justify-content-between align-items-center\"><div class=\"text-muted fs-8\">Respuesta cliente: " + escapeHtml(cotizacion.respuesta_cliente_estatus || "pendiente") + "</div><button type=\"button\" class=\"btn btn-sm btn-light-primary\" data-cotizacion-imprimir=\"" + escapeHtml(idCotizacion) + "\"><i class=\"bi bi-printer\"></i> Imprimir revision</button>" +
                (permisosUi.cotizaciones_gestionar ? "<button type=\"button\" class=\"btn btn-sm btn-light-success\" data-cotizacion-entrega-guardar=\"" + escapeHtml(idCotizacion) + "\"><i class=\"bi bi-send-check\"></i> Guardar respuesta para cliente</button>" : "") + "</div>" +
                "</div>";
            var facturaHtml = "<div class=\"text-start mb-5 border rounded p-4\">" +
                "<label class=\"form-check form-check-custom form-check-solid mb-4\"><input id=\"dist_pedido_requiere_factura\" class=\"form-check-input\" type=\"checkbox\"" + (requiereFactura ? " checked" : "") + "><span class=\"form-check-label fw-semibold\">Cliente solicita factura</span></label>" +
                "<div class=\"row g-3\">" +
                "<div class=\"col-md-4\"><label class=\"form-label fw-semibold\">RFC</label><input id=\"dist_pedido_factura_rfc\" class=\"form-control form-control-solid\" value=\"" + escapeHtml(facturacion.rfc || "") + "\"></div>" +
                "<div class=\"col-md-8\"><label class=\"form-label fw-semibold\">Razon social</label><input id=\"dist_pedido_factura_razon\" class=\"form-control form-control-solid\" value=\"" + escapeHtml(facturacion.razon_social || "") + "\"></div>" +
                "<div class=\"col-md-4\"><label class=\"form-label fw-semibold\">Regimen fiscal</label><input id=\"dist_pedido_factura_regimen\" class=\"form-control form-control-solid\" value=\"" + escapeHtml(facturacion.regimen_fiscal || "") + "\"></div>" +
                "<div class=\"col-md-4\"><label class=\"form-label fw-semibold\">Uso CFDI</label><input id=\"dist_pedido_factura_uso\" class=\"form-control form-control-solid\" value=\"" + escapeHtml(facturacion.uso_cfdi || "") + "\"></div>" +
                "<div class=\"col-md-4\"><label class=\"form-label fw-semibold\">CP fiscal</label><input id=\"dist_pedido_factura_cp\" class=\"form-control form-control-solid\" value=\"" + escapeHtml(facturacion.codigo_postal_fiscal || "") + "\"></div>" +
                "<div class=\"col-md-6\"><label class=\"form-label fw-semibold\">Correo factura</label><input id=\"dist_pedido_factura_correo\" class=\"form-control form-control-solid\" value=\"" + escapeHtml(facturacion.correo_facturacion || "") + "\"></div>" +
                "<div class=\"col-md-6\"><label class=\"form-label fw-semibold\">Comentarios factura</label><input id=\"dist_pedido_factura_comentarios\" class=\"form-control form-control-solid\" value=\"" + escapeHtml(facturacion.comentarios_facturacion || "") + "\"></div>" +
                "</div></div>";
            Swal.fire({
                title: cotizacion.folio || "Solicitud Distribucion",
                html: "<div class=\"text-start mb-4\"><div class=\"fw-bold\">" + escapeHtml(cotizacion.cliente || ("Cliente " + (cotizacion.id_cliente_distribucion || ""))) + "</div><div class=\"text-muted fs-8\">" + escapeHtml([cotizacion.empresa, cotizacion.correo, cotizacion.estatus].filter(Boolean).join(" / ")) + "</div></div>" +
                    resumenHtml +
                    entregaHtml +
                    facturaHtml +
                    "<div class=\"table-responsive text-start\"><table class=\"table table-row-dashed fs-7 gy-3 mb-0\"><thead><tr class=\"text-muted fw-bold\"><th>Producto</th><th class=\"text-end\">Solicitado</th><th class=\"text-end\">Confirmado</th><th class=\"text-end\">Precio</th><th class=\"text-end\">Imp. solicitado</th><th class=\"text-end\">Imp. confirmado</th><th>Revision</th><th></th></tr></thead><tbody>" + filas + "</tbody></table></div>",
                width: 1180,
                confirmButtonText: "Cerrar",
                didOpen: function () {
                    var imprimir = document.querySelector(".swal2-container [data-cotizacion-imprimir]");
                    if (imprimir) {
                        imprimir.addEventListener("click", function () {
                            abrirImpresionPedido(cotizacion, items);
                        });
                    }
                    var guardarEntrega = document.querySelector(".swal2-container [data-cotizacion-entrega-guardar]");
                    if (guardarEntrega) {
                        guardarEntrega.addEventListener("click", function () {
                            guardarEntregaCotizacion(idCotizacion);
                        });
                    }
                    document.querySelectorAll(".swal2-container [data-cotizacion-item-revisar]").forEach(function (button) {
                        button.addEventListener("click", function () {
                            var idItem = button.getAttribute("data-cotizacion-item-revisar");
                            var item = items.find(function (fila) { return String(fila.id_cotizacion_item) === String(idItem); });
                            revisarPartidaCotizacion(item, idCotizacion);
                        });
                    });
                }
            });
        }).catch(showError);
    }

    function guardarEntregaCotizacion(idCotizacion) {
        request("/DistribucionAdmin/cotizacion_entrega_guardar", {
            id_cotizacion_distribucion: idCotizacion,
            tipo_entrega: (document.getElementById("dist_pedido_tipo_entrega") || {}).value || "por_definir",
            costo_envio: (document.getElementById("dist_pedido_costo_envio") || {}).value || "0",
            entrega_habilitar_envio: (document.getElementById("dist_pedido_habilitar_envio") || {}).checked ? 1 : 0,
            entrega_habilitar_recoger_tienda: (document.getElementById("dist_pedido_habilitar_recoger") || {}).checked ? 1 : 0,
            requiere_factura: (document.getElementById("dist_pedido_requiere_factura") || {}).checked ? 1 : 0,
            facturacion: JSON.stringify({
                requiere_factura: (document.getElementById("dist_pedido_requiere_factura") || {}).checked ? 1 : 0,
                rfc: (document.getElementById("dist_pedido_factura_rfc") || {}).value || "",
                razon_social: (document.getElementById("dist_pedido_factura_razon") || {}).value || "",
                regimen_fiscal: (document.getElementById("dist_pedido_factura_regimen") || {}).value || "",
                uso_cfdi: (document.getElementById("dist_pedido_factura_uso") || {}).value || "",
                codigo_postal_fiscal: (document.getElementById("dist_pedido_factura_cp") || {}).value || "",
                correo_facturacion: (document.getElementById("dist_pedido_factura_correo") || {}).value || "",
                comentarios_facturacion: (document.getElementById("dist_pedido_factura_comentarios") || {}).value || ""
            })
        }).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            showOk(response.mensaje);
            cargarCotizaciones().then(function () { verCotizacionDetalle(idCotizacion); });
        }).catch(showError);
    }

    function revisarPartidaCotizacion(item, idCotizacion) {
        if (!item) { return; }
        var solicitado = Number(item.cantidad || 0);
        Swal.fire({
            title: "Revisar partida",
            html: "<div class=\"text-start\">" +
                "<div class=\"mb-4\"><div class=\"fw-semibold\">" + escapeHtml(item.producto_actual || item.nombre_snapshot || "") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.sku_actual || item.sku_snapshot || "") + "</div></div>" +
                "<div class=\"row g-3 mb-4\"><div class=\"col-md-6\"><label class=\"form-label fw-semibold\">Cantidad solicitada</label><input class=\"form-control form-control-solid\" value=\"" + escapeHtml(numero(solicitado)) + "\" disabled></div>" +
                "<div class=\"col-md-6\"><label class=\"form-label fw-semibold\">Existencia / cantidad confirmada</label><input id=\"dist_revision_cantidad\" class=\"form-control form-control-solid\" inputmode=\"decimal\" value=\"" + escapeHtml(item.cantidad_confirmada || item.cantidad || 0) + "\"></div></div>" +
                "<label class=\"form-label fw-semibold\">Estatus</label><select id=\"dist_revision_estatus\" class=\"form-select form-select-solid mb-4\">" +
                ["por_confirmar", "confirmado", "parcial", "no_disponible", "pendiente_proveedor", "requiere_revision"].map(function (estatus) {
                    return "<option value=\"" + estatus + "\"" + (item.estatus_revision === estatus ? " selected" : "") + ">" + estatus + "</option>";
                }).join("") + "</select>" +
                "<label class=\"form-label fw-semibold\">Comentario</label><textarea id=\"dist_revision_comentario\" class=\"form-control form-control-solid\" rows=\"3\">" + escapeHtml(item.comentario_revision || "") + "</textarea>" +
                "</div>",
            width: 620,
            showCancelButton: true,
            confirmButtonText: "Guardar revision",
            preConfirm: function () {
                var confirmada = Number((document.getElementById("dist_revision_cantidad") || {}).value || "0");
                if (!Number.isFinite(confirmada) || confirmada < 0) {
                    Swal.showValidationMessage("Captura una cantidad confirmada valida.");
                    return false;
                }
                if (confirmada > solicitado) {
                    Swal.showValidationMessage("La cantidad confirmada no puede ser mayor a la solicitada por el cliente.");
                    return false;
                }
                return {
                    cantidad_confirmada: confirmada,
                    estatus_revision: (document.getElementById("dist_revision_estatus") || {}).value || "por_confirmar",
                    comentario_revision: (document.getElementById("dist_revision_comentario") || {}).value || ""
                };
            }
        }).then(function (result) {
            if (!result.isConfirmed) { return; }
            request("/DistribucionAdmin/cotizacion_item_revision", {
                id_cotizacion_item: item.id_cotizacion_item,
                cantidad_confirmada: result.value.cantidad_confirmada,
                estatus_revision: result.value.estatus_revision,
                comentario_revision: result.value.comentario_revision
            }).then(function (response) {
                if (response.error) { throw new Error(response.mensaje); }
                showOk(response.mensaje);
                cargarCotizaciones().then(function () { verCotizacionDetalle(idCotizacion); });
            }).catch(showError);
        });
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-09-29
     * Proposito: ejecutar acciones por lote sobre productos Distribucion con confirmacion previa.
     * Impacto: UI ERP Distribucion; reutiliza endpoints existentes y respeta bloqueo backend de precio activo.
     */
    function productosSeleccionados() {
        var seleccionados = [];
        document.querySelectorAll("[data-producto-seleccion]:checked").forEach(function (input) {
            var id = input.getAttribute("data-producto-seleccion");
            var item = productos.find(function (producto) { return String(producto.id_sku) === String(id); });
            if (item) { seleccionados.push(item); }
        });
        return seleccionados;
    }

    function productosObjetivoLote() {
        var seleccionados = productosSeleccionados();
        return seleccionados.length > 0 ? seleccionados : productos.slice();
    }

    function clientesOptionsCatalogo() {
        return clientes.map(function (cliente) {
            return "<option value=\"" + escapeHtml(cliente.id_cliente_distribucion) + "\">" + escapeHtml((cliente.nombre || ("Cliente " + cliente.id_cliente_distribucion)) + (cliente.empresa ? " - " + cliente.empresa : "")) + "</option>";
        }).join("");
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-10-07
     * Proposito: crear reglas SKU por lote desde Productos hacia un cliente Distribucion.
     * Impacto: UI ERP Distribucion; agiliza catalogo personalizado sin editar cada cliente manualmente.
     */
    function confirmarAsignarProductosCliente(itemsIniciales) {
        if (!permisosUi.editar) { return; }
        var items = itemsIniciales && itemsIniciales.length ? itemsIniciales : productosSeleccionados();
        if (!items.length) {
            Swal.fire({text: "Selecciona al menos un producto para asignarlo a un cliente.", icon: "warning", confirmButtonText: "Aceptar"});
            return;
        }
        var abrir = function () {
            if (!clientes.length) {
                Swal.fire({text: "No hay clientes cargados para asignar catalogo.", icon: "warning", confirmButtonText: "Aceptar"});
                return;
            }
            Swal.fire({
                title: "Asignar productos a cliente",
                html: "<div class=\"text-start\">" +
                    "<div class=\"mb-4\">Se crearan reglas por SKU para <strong>" + escapeHtml(items.length) + "</strong> producto(s).</div>" +
                    "<label class=\"form-label fw-semibold\">Cliente</label><select id=\"dist_asignar_cliente\" class=\"form-select form-select-solid mb-4\">" + clientesOptionsCatalogo() + "</select>" +
                    "<label class=\"form-label fw-semibold\">Accion</label><select id=\"dist_asignar_accion\" class=\"form-select form-select-solid mb-4\"><option value=\"permitir\">Permitir en catalogo</option><option value=\"ocultar\">Ocultar en catalogo</option></select>" +
                    "<label class=\"form-label fw-semibold\">Notas</label><input id=\"dist_asignar_notas\" class=\"form-control form-control-solid\" maxlength=\"250\" value=\"Regla creada desde Productos Distribucion\">" +
                    "</div>",
                width: 620,
                showCancelButton: true,
                confirmButtonText: "Guardar reglas",
                cancelButtonText: "Cancelar",
                preConfirm: function () {
                    var idCliente = selectValue("dist_asignar_cliente");
                    if (!idCliente) {
                        Swal.showValidationMessage("Selecciona un cliente.");
                        return false;
                    }
                    return {
                        id_cliente_distribucion: idCliente,
                        accion: selectValue("dist_asignar_accion") || "permitir",
                        notas: (document.getElementById("dist_asignar_notas") || {}).value || ""
                    };
                }
            }).then(function (result) {
                if (!result.isConfirmed) { return; }
                ejecutarAsignarProductosCliente(items, result.value);
            });
        };
        if (!clientes.length) {
            cargarClientes().then(abrir).catch(showError);
        } else {
            abrir();
        }
    }

    function resumenCalidadProductos(items) {
        return {
            total: items.length,
            sin_precio: items.filter(function (item) { return Number(item.tiene_precio || 0) !== 1; }).length,
            sin_imagen: items.filter(function (item) { return Number(item.tiene_imagen || 0) !== 1; }).length,
            sin_ficha: items.filter(function (item) { return Number(item.tiene_descripcion || 0) !== 1; }).length,
            publicados: items.filter(function (item) { return ["activo", "publicado", "aprobado"].indexOf(item.canal_estatus) !== -1 && Number(item.sincronizar_catalogo || 0) === 1; }).length
        };
    }

    function confirmarProductosLote(accion) {
        if (!permisosUi.editar) { return; }
        var items = productosObjetivoLote();
        if (!items.length) {
            Swal.fire({text: "No hay productos en la lista actual.", icon: "warning", confirmButtonText: "Aceptar"});
            return;
        }
        var resumenLote = resumenCalidadProductos(items);
        var verbo = accion === "publicar" ? "publicar" : "desactivar";
        Swal.fire({
            title: accion === "publicar" ? "Publicar lote" : "Desactivar lote",
            html: "<div class=\"text-start\">" +
                "<div class=\"mb-3\">Se van a " + escapeHtml(verbo) + " <strong>" + escapeHtml(resumenLote.total) + "</strong> productos de la lista actual o seleccionados.</div>" +
                "<div class=\"d-flex flex-wrap gap-2\">" +
                "<span class=\"badge badge-light-danger\">Sin precio " + escapeHtml(resumenLote.sin_precio) + "</span>" +
                "<span class=\"badge badge-light-warning\">Sin imagen " + escapeHtml(resumenLote.sin_imagen) + "</span>" +
                "<span class=\"badge badge-light-warning\">Sin ficha " + escapeHtml(resumenLote.sin_ficha) + "</span>" +
                "<span class=\"badge badge-light-primary\">Ya publicados " + escapeHtml(resumenLote.publicados) + "</span>" +
                "</div></div>",
            icon: "warning",
            width: 680,
            showCancelButton: true,
            confirmButtonText: accion === "publicar" ? "Publicar" : "Desactivar",
            cancelButtonText: "Cancelar"
        }).then(function (result) {
            if (!result.isConfirmed) { return; }
            ejecutarProductosLote(accion, items);
        });
    }

    function cargarTodosProductosFiltrados() {
        var todos = [];
        var pagina = 1;
        var totalPaginas = 1;
        function cargarPagina() {
            return request("/DistribucionAdmin/skus_publicables?" + query(productosQueryFiltros(300, pagina))).then(function (response) {
                if (response.error) { throw new Error(response.mensaje); }
                var depurar = response.depurar || {};
                var items = depurar.items || [];
                var paginacion = depurar.paginacion || {};
                totalPaginas = Number(paginacion.total_paginas || 1);
                todos = todos.concat(items);
                pagina++;
                if (pagina <= totalPaginas) {
                    return cargarPagina();
                }
                return todos;
            });
        }
        return cargarPagina();
    }

    function confirmarPublicarProductosFiltrados() {
        if (!permisosUi.editar) { return; }
        Swal.fire({
            title: "Revisando productos",
            text: "Estoy contando todos los productos que coinciden con tus filtros.",
            allowOutsideClick: false,
            didOpen: function () { Swal.showLoading(); }
        });
        cargarTodosProductosFiltrados().then(function (items) {
            var pendientes = items.filter(function (item) {
                var publicado = ["activo", "publicado", "aprobado"].indexOf(item.canal_estatus) !== -1 && Number(item.sincronizar_catalogo || 0) === 1;
                return !publicado;
            });
            if (!pendientes.length) {
                Swal.fire({text: "No hay productos pendientes de publicar con los filtros actuales.", icon: "info", confirmButtonText: "Aceptar"});
                return;
            }
            var resumenLote = resumenCalidadProductos(pendientes);
            Swal.fire({
                title: "Publicar todos filtrados",
                html: "<div class=\"text-start\">" +
                    "<div class=\"mb-3\">Se van a intentar publicar <strong>" + escapeHtml(resumenLote.total) + "</strong> productos no publicados que coinciden con los filtros actuales.</div>" +
                    "<div class=\"mb-3 text-muted fs-8\">La publicacion respeta el bloqueo del ERP: los productos sin precio activo no se publicaran.</div>" +
                    "<div class=\"d-flex flex-wrap gap-2\">" +
                    "<span class=\"badge badge-light-danger\">Sin precio " + escapeHtml(resumenLote.sin_precio) + "</span>" +
                    "<span class=\"badge badge-light-warning\">Sin imagen " + escapeHtml(resumenLote.sin_imagen) + "</span>" +
                    "<span class=\"badge badge-light-warning\">Sin ficha " + escapeHtml(resumenLote.sin_ficha) + "</span>" +
                    "</div></div>",
                icon: "warning",
                width: 720,
                showCancelButton: true,
                confirmButtonText: "Publicar todos filtrados",
                cancelButtonText: "Cancelar"
            }).then(function (result) {
                if (!result.isConfirmed) { return; }
                ejecutarProductosLote("publicar", pendientes);
            });
        }).catch(showError);
    }

    function ejecutarProductosLote(accion, items) {
        var url = accion === "publicar" ? "/DistribucionAdmin/publicar_sku" : "/DistribucionAdmin/desactivar_sku";
        var total = items.length;
        var ok = 0;
        var errores = [];
        var secuencia = Promise.resolve();
        items.forEach(function (item) {
            secuencia = secuencia.then(function () {
                return request(url, {
                    id_sku: item.id_sku,
                    id_canal_vinculo: item.id_canal_vinculo || ""
                }).then(function (response) {
                    if (response.error) {
                        errores.push((item.sku || item.id_sku) + ": " + response.mensaje);
                    } else {
                        ok++;
                    }
                }).catch(function (error) {
                    errores.push((item.sku || item.id_sku) + ": " + (error.message || String(error)));
                });
            });
        });
        secuencia.then(function () {
            Swal.fire({
                title: "Lote procesado",
                html: "<div class=\"text-start\"><div class=\"mb-2\">Correctos: <strong>" + escapeHtml(ok) + "</strong> de " + escapeHtml(total) + "</div>" +
                    (errores.length ? "<div class=\"text-muted fs-8\">" + escapeHtml(errores.slice(0, 8).join("\n")) + "</div>" : "") + "</div>",
                icon: errores.length ? "warning" : "success",
                confirmButtonText: "Aceptar"
            });
            return cargarTodo();
        });
    }

    function ejecutarAsignarProductosCliente(items, config) {
        var ok = 0;
        var errores = [];
        var secuencia = Promise.resolve();
        items.forEach(function (item) {
            secuencia = secuencia.then(function () {
                return request("/DistribucionAdmin/cliente_catalogo_regla_guardar", {
                    id_cliente_distribucion: config.id_cliente_distribucion,
                    tipo_regla: "sku",
                    id_sku: item.id_sku,
                    accion: config.accion,
                    prioridad: 10,
                    estatus: "activo",
                    origen: "productos",
                    notas: config.notas || ""
                }).then(function (response) {
                    if (response.error) {
                        errores.push((item.sku || item.id_sku) + ": " + response.mensaje);
                    } else {
                        ok++;
                    }
                }).catch(function (error) {
                    errores.push((item.sku || item.id_sku) + ": " + (error.message || String(error)));
                });
            });
        });
        secuencia.then(function () {
            Swal.fire({
                title: "Reglas guardadas",
                html: "<div class=\"text-start\"><div class=\"mb-2\">Correctas: <strong>" + escapeHtml(ok) + "</strong> de " + escapeHtml(items.length) + "</div>" +
                    (errores.length ? "<div class=\"text-muted fs-8\">" + escapeHtml(errores.slice(0, 8).join("\n")) + "</div>" : "") + "</div>",
                icon: errores.length ? "warning" : "success",
                confirmButtonText: "Aceptar"
            });
        });
    }

    document.addEventListener("click", function (event) {
        var button = event.target.closest("button");
        if (!button) { return; }
        if (button.id === "distribucion_refrescar") {
            cargarTodo();
        } else if (button.id === "dist_productos_buscar_btn") {
            reiniciarProductosYCargar().catch(showError);
        } else if (button.id === "dist_productos_pagina_anterior") {
            cargarProductosDesdePagina(Number(productosPaginacion.pagina || 1) - 1).catch(showError);
        } else if (button.id === "dist_productos_pagina_siguiente") {
            cargarProductosDesdePagina(Number(productosPaginacion.pagina || 1) + 1).catch(showError);
        } else if (button.id === "dist_productos_publicar_lote") {
            confirmarProductosLote("publicar");
        } else if (button.id === "dist_productos_publicar_filtrados") {
            confirmarPublicarProductosFiltrados();
        } else if (button.id === "dist_productos_desactivar_lote") {
            confirmarProductosLote("desactivar");
        } else if (button.id === "dist_productos_asignar_cliente") {
            confirmarAsignarProductosCliente();
        } else if (button.hasAttribute("data-solicitud-aprobar")) {
            aprobarSolicitud(button.getAttribute("data-solicitud-aprobar"));
        } else if (button.hasAttribute("data-solicitud-detalle")) {
            verSolicitud(button.getAttribute("data-solicitud-detalle"));
        } else if (button.hasAttribute("data-solicitud-whatsapp")) {
            var solicitud = solicitudes.find(function (item) { return String(item.id_solicitud_distribucion) === String(button.getAttribute("data-solicitud-whatsapp")); });
            if (solicitud && solicitud.estatus === "aprobado" && solicitud.id_cliente_distribucion) {
                abrirWhatsAppActivacion({
                    id_solicitud_distribucion: solicitud.id_solicitud_distribucion,
                    id_cliente_distribucion: solicitud.id_cliente_distribucion,
                    telefono: solicitud.telefono,
                    whatsapp: solicitud.whatsapp
                });
            } else {
                abrirWhatsApp(solicitud);
            }
        } else if (button.hasAttribute("data-solicitud-rechazar")) {
            accionSimple("/DistribucionAdmin/cliente_rechazar", {id_solicitud_distribucion: button.getAttribute("data-solicitud-rechazar")});
        } else if (button.hasAttribute("data-cliente-suspender")) {
            accionSimple("/DistribucionAdmin/cliente_suspendir", {id_cliente_distribucion: button.getAttribute("data-cliente-suspender")});
        } else if (button.hasAttribute("data-cliente-permisos")) {
            editarPermisos(button.getAttribute("data-cliente-permisos"));
        } else if (button.hasAttribute("data-cliente-entrega")) {
            editarEntregaCliente(button.getAttribute("data-cliente-entrega"));
        } else if (button.hasAttribute("data-cliente-catalogo")) {
            verCatalogoCliente(button.getAttribute("data-cliente-catalogo"));
        } else if (button.hasAttribute("data-cliente-auditoria")) {
            verAuditoriaCliente(button.getAttribute("data-cliente-auditoria"));
        } else if (button.hasAttribute("data-cliente-whatsapp")) {
            var cliente = clientes.find(function (item) { return String(item.id_cliente_distribucion) === String(button.getAttribute("data-cliente-whatsapp")); });
            abrirWhatsAppActivacion({
                id_cliente_distribucion: cliente ? cliente.id_cliente_distribucion : button.getAttribute("data-cliente-whatsapp"),
                telefono: cliente ? cliente.telefono : ""
            });
        } else if (button.hasAttribute("data-cliente-acceso")) {
            var clienteAcceso = clientes.find(function (item) { return String(item.id_cliente_distribucion) === String(button.getAttribute("data-cliente-acceso")); });
            generarLinkAccesoCliente({
                id_cliente_distribucion: clienteAcceso ? clienteAcceso.id_cliente_distribucion : button.getAttribute("data-cliente-acceso"),
                telefono: clienteAcceso ? clienteAcceso.telefono : ""
            });
        } else if (button.hasAttribute("data-cotizacion-detalle")) {
            verCotizacionDetalle(button.getAttribute("data-cotizacion-detalle"));
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
        } else if (button.hasAttribute("data-producto-asignar-cliente")) {
            var productoAsignar = productos.find(function (item) { return String(item.id_sku) === String(button.getAttribute("data-producto-asignar-cliente")); });
            confirmarAsignarProductosCliente(productoAsignar ? [productoAsignar] : []);
        }
    });

    document.addEventListener("change", function (event) {
        if (event.target.id === "dist_solicitudes_estatus") {
            cargarSolicitudes().catch(showError);
        } else if (event.target.id === "dist_clientes_estatus") {
            cargarClientes().catch(showError);
        } else if (event.target.id === "dist_clientes_incompletos") {
            renderClientes();
        } else if (event.target.id === "dist_cotizaciones_estatus" || event.target.id === "dist_cotizaciones_fecha_desde" || event.target.id === "dist_cotizaciones_fecha_hasta") {
            cargarCotizaciones().catch(showError);
        } else if (event.target.id === "dist_productos_select_all") {
            document.querySelectorAll("[data-producto-seleccion]").forEach(function (input) {
                input.checked = event.target.checked;
            });
        } else if (event.target.id === "dist_mi_catalogo_marca" || event.target.id === "dist_mi_catalogo_categoria" || event.target.id === "dist_mi_catalogo_proveedor") {
            cargarSurtidos().catch(showError);
        } else if (event.target.id === "dist_inventarios_estado" || event.target.id === "dist_inventarios_marca" || event.target.id === "dist_inventarios_categoria" || event.target.id === "dist_inventarios_proveedor") {
            if (event.target.id === "dist_inventarios_estado") {
                renderInventarios();
            } else {
                cargarInventarios().catch(showError);
            }
        } else if (event.target.id === "dist_sugeridos_marca" || event.target.id === "dist_sugeridos_categoria" || event.target.id === "dist_sugeridos_proveedor") {
            cargarSugeridos().catch(showError);
        } else if (event.target.id === "dist_productos_limite") {
            reiniciarProductosYCargar().catch(showError);
        } else if (event.target.id === "dist_productos_marca" || event.target.id === "dist_productos_categoria" || event.target.id === "dist_productos_proveedor" || event.target.id === "dist_productos_canal" || event.target.id === "dist_productos_precio" || event.target.id === "dist_productos_imagen" || event.target.id === "dist_productos_ficha") {
            reiniciarProductosYCargar().catch(showError);
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
        } else if (event.target.id === "dist_mi_catalogo_buscar") {
            renderSurtidos();
        } else if (event.target.id === "dist_inventarios_buscar") {
            renderInventarios();
        } else if (event.target.id === "dist_sugeridos_buscar") {
            renderSugeridos();
        } else if (event.target.id === "dist_cotizaciones_buscar") {
            renderCotizaciones();
        } else if (event.target.id === "dist_productos_buscar" && event.target.value.length === 0) {
            reiniciarProductosYCargar().catch(showError);
        }
    });

    cargarTodo();
})();
