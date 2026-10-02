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

    function numero(value) {
        return Number(value || 0).toLocaleString("es-MX", {minimumFractionDigits: 0, maximumFractionDigits: 2});
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
                (totalSugeridos > 0 ? "<span class=\"badge badge-light-warning\">Sugerido " + escapeHtml(totalSugeridos) + "</span>" : "") +
                "<div class=\"text-muted fs-8 mt-1\">Permisos " + escapeHtml(item.permisos_activos || 0) + " / Entrega " + escapeHtml(item.metodo_entrega_default || "por_definir") + " / Ultimo acceso " + escapeHtml(item.fecha_ultimo_login || "sin acceso") + "</div>";
            return "<tr><td><div class=\"fw-bold\">" + escapeHtml(item.nombre) + "</div><div class=\"text-muted fs-7\">" + escapeHtml(item.correo) + "</div></td>" +
                "<td>" + tipo + "</td><td>" + lista + "</td><td>" + indicadores + "</td>" +
                "<td>" + badge(item.estatus) + "</td><td class=\"text-end\">" + acciones + "</td></tr>";
        }).join("") || "<tr><td colspan=\"6\" class=\"text-center text-muted py-10\">Sin clientes</td></tr>";
    }

    function renderCotizaciones() {
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
            var total = item.estatus === "pedido_solicitado" ? "<span class=\"text-muted\">Por confirmar</span>" : money(item.total_confirmado || item.total_estimado);
            var estadoCliente = item.respuesta_cliente_estatus ? "<div class=\"text-muted fs-8\">Cliente: " + escapeHtml(item.respuesta_cliente_estatus) + "</div>" : "";
            return "<tr><td class=\"fw-bold\">" + escapeHtml(item.folio) + "</td><td><div class=\"fw-semibold\">" + escapeHtml(item.cliente || ("ID " + item.id_cliente_distribucion)) + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.empresa || item.correo || "") + "</div></td><td><span class=\"badge badge-light\">" + escapeHtml(item.partidas || 0) + "</span></td><td>" + total + "</td>" +
                "<td>" + badge(item.estatus) + estadoCliente + "</td><td class=\"text-muted fs-7\">" + escapeHtml(item.fecha_registro || "") + "</td><td class=\"text-end\">" + acciones + "</td></tr>";
        }).join("") || "<tr><td colspan=\"7\" class=\"text-center text-muted py-10\">Sin cotizaciones</td></tr>";
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
        var estatus = document.getElementById("dist_clientes_estatus").value;
        return request("/DistribucionAdmin/clientes?limite=100&estatus=" + encodeURIComponent(estatus)).then(function (response) {
            clientes = response.depurar && response.depurar.items ? response.depurar.items : [];
            renderClientes();
        });
    }

    function cargarCotizaciones() {
        return request("/DistribucionAdmin/cotizaciones?" + query({limite: 100, estatus: selectValue("dist_cotizaciones_estatus"), q: selectValue("dist_cotizaciones_buscar")})).then(function (response) {
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
        document.querySelectorAll("#dist_productos_publicar_lote, #dist_productos_publicar_filtrados, #dist_productos_desactivar_lote").forEach(function (button) {
            button.classList.toggle("d-none", !permisosUi.editar);
        });
        return cargarAuxiliares().then(function () {
            return Promise.all([cargarResumen(), cargarDemanda(), cargarSolicitudes(), cargarClientes(), cargarCotizaciones(), cargarSurtidos(), cargarInventarios(), cargarSugeridos(), cargarProductos()]).then(function () {
                renderClientes();
            });
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
    function verCotizacionDetalle(idCotizacion) {
        request("/DistribucionAdmin/cotizacion_detalle?id_cotizacion_distribucion=" + encodeURIComponent(idCotizacion)).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            var cotizacion = response.depurar && response.depurar.cotizacion ? response.depurar.cotizacion : {};
            var items = response.depurar && response.depurar.items ? response.depurar.items : [];
            var facturacion = jsonSeguro(cotizacion.facturacion_json);
            var requiereFactura = Number(cotizacion.requiere_factura || facturacion.requiere_factura || 0) === 1;
            var filas = items.map(function (item) {
                var revision = item.estatus_revision ? badge(item.estatus_revision) : "<span class=\"text-muted\">Por confirmar</span>";
                var boton = permisosUi.cotizaciones_gestionar
                    ? "<button class=\"btn btn-sm btn-icon btn-light-primary\" title=\"Revisar partida\" data-cotizacion-item-revisar=\"" + escapeHtml(item.id_cotizacion_item) + "\"><i class=\"bi bi-pencil-square\"></i></button>"
                    : "";
                return "<tr><td><div class=\"fw-semibold\">" + escapeHtml(item.producto_actual || item.nombre_snapshot || "") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.sku_actual || item.sku_snapshot || ("SKU " + item.id_sku)) + "</div></td>" +
                    "<td class=\"text-end\">" + numero(item.cantidad) + "</td><td class=\"text-end\">" + numero(item.cantidad_confirmada) + "</td><td>" + revision + "</td><td>" + escapeHtml(item.comentario_revision || "") + "</td><td class=\"text-end\">" + boton + "</td></tr>";
            }).join("") || "<tr><td colspan=\"6\" class=\"text-center text-muted py-6\">Sin partidas</td></tr>";
            var entregaHtml = "<div class=\"row g-3 text-start mb-5\">" +
                "<div class=\"col-md-3\"><label class=\"form-label fw-semibold\">Entrega</label><select id=\"dist_pedido_tipo_entrega\" class=\"form-select form-select-solid\">" +
                ["por_definir", "envio", "recoger_tienda"].map(function (tipo) {
                    return "<option value=\"" + tipo + "\"" + ((cotizacion.tipo_entrega || "por_definir") === tipo ? " selected" : "") + ">" + tipo + "</option>";
                }).join("") + "</select></div>" +
                "<div class=\"col-md-3\"><label class=\"form-label fw-semibold\">Costo envio</label><input id=\"dist_pedido_costo_envio\" class=\"form-control form-control-solid\" inputmode=\"decimal\" value=\"" + escapeHtml(cotizacion.costo_envio || 0) + "\"></div>" +
                "<div class=\"col-md-3 d-flex align-items-end\"><label class=\"form-check form-check-custom form-check-solid mb-3\"><input id=\"dist_pedido_habilitar_envio\" class=\"form-check-input\" type=\"checkbox\"" + (Number(cotizacion.entrega_habilitar_envio || 1) === 1 ? " checked" : "") + "><span class=\"form-check-label\">Envio</span></label></div>" +
                "<div class=\"col-md-3 d-flex align-items-end\"><label class=\"form-check form-check-custom form-check-solid mb-3\"><input id=\"dist_pedido_habilitar_recoger\" class=\"form-check-input\" type=\"checkbox\"" + (Number(cotizacion.entrega_habilitar_recoger_tienda || 1) === 1 ? " checked" : "") + "><span class=\"form-check-label\">Recoger</span></label></div>" +
                "<div class=\"col-12 d-flex justify-content-between align-items-center\"><div class=\"text-muted fs-8\">Respuesta cliente: " + escapeHtml(cotizacion.respuesta_cliente_estatus || "pendiente") + "</div>" +
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
                    entregaHtml +
                    facturaHtml +
                    "<div class=\"table-responsive text-start\"><table class=\"table table-row-dashed fs-7 gy-3 mb-0\"><thead><tr class=\"text-muted fw-bold\"><th>Producto</th><th class=\"text-end\">Solicitado</th><th class=\"text-end\">Confirmado</th><th>Revision</th><th>Comentario</th><th></th></tr></thead><tbody>" + filas + "</tbody></table></div>",
                width: 980,
                confirmButtonText: "Cerrar",
                didOpen: function () {
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
        Swal.fire({
            title: "Revisar partida",
            html: "<div class=\"text-start\">" +
                "<div class=\"mb-4\"><div class=\"fw-semibold\">" + escapeHtml(item.producto_actual || item.nombre_snapshot || "") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.sku_actual || item.sku_snapshot || "") + "</div></div>" +
                "<label class=\"form-label fw-semibold\">Cantidad confirmada</label><input id=\"dist_revision_cantidad\" class=\"form-control form-control-solid mb-4\" inputmode=\"decimal\" value=\"" + escapeHtml(item.cantidad_confirmada || item.cantidad || 0) + "\">" +
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
                return {
                    cantidad_confirmada: (document.getElementById("dist_revision_cantidad") || {}).value || "0",
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
        }
    });

    document.addEventListener("change", function (event) {
        if (event.target.id === "dist_solicitudes_estatus") {
            cargarSolicitudes().catch(showError);
        } else if (event.target.id === "dist_clientes_estatus") {
            cargarClientes().catch(showError);
        } else if (event.target.id === "dist_clientes_incompletos") {
            renderClientes();
        } else if (event.target.id === "dist_cotizaciones_estatus") {
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
