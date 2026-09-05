/*
 * Documentacion IA: Codex GPT-5, 2026-08-31.
 * Proposito: UX interna para bandeja Ecommerce Leads / Carritos.
 * Impacto: consulta intencion comercial y prepara planes de seguimiento sin crear pedidos, ventas ni inventario.
 * Contrato: consume endpoints internos protegidos; acciones en modo plan read-only.
 */
(function () {
  "use strict";

  var placeholderImagen = "data:image/svg+xml;charset=UTF-8,%3Csvg%20xmlns='http://www.w3.org/2000/svg'%20viewBox='0%200%2056%2056'%3E%3Crect%20width='56'%20height='56'%20rx='8'%20fill='%23f1f3f6'/%3E%3Cpath%20d='M11%2042h34L34%2029l-8%209-5-7z'%20fill='%23c8ced8'/%3E%3Ccircle%20cx='20'%20cy='20'%20r='6'%20fill='%23d7dce5'/%3E%3C/svg%3E";
  var state = { items: [], productos: [], detalle: null, loading: false, loadingProductos: false, leadSeleccionado: null };

  document.addEventListener("DOMContentLoaded", function () {
    bindEvents();
    cargarBandeja();
    cargarProductos();
  });

  function bindEvents() {
    var recargar = document.getElementById("ecom_leads_recargar");
    var q = document.getElementById("ecom_leads_q");
    var estatus = document.getElementById("ecom_leads_estatus");
    var copiar = document.getElementById("ecom_leads_copiar");
    var productosQ = document.getElementById("ecom_leads_productos_q");
    var productosValidacion = document.getElementById("ecom_leads_productos_validacion");
    var limpiarProductos = document.getElementById("ecom_leads_productos_limpiar");
    if (recargar) recargar.addEventListener("click", cargarBandeja);
    if (q) q.addEventListener("input", debounce(cargarBandeja, 350));
    if (estatus) estatus.addEventListener("change", cargarBandeja);
    if (copiar) copiar.addEventListener("click", copiarResumen);
    if (recargar) recargar.addEventListener("click", cargarProductos);
    if (productosQ) productosQ.addEventListener("input", debounce(cargarProductos, 350));
    if (productosValidacion) productosValidacion.addEventListener("change", cargarProductos);
    if (limpiarProductos) limpiarProductos.addEventListener("click", limpiarSeleccionLead);
  }

  function cargarBandeja() {
    if (state.loading) return;
    state.loading = true;
    setEstado("Cargando", "badge-light-warning");
    var params = new URLSearchParams();
    params.set("limite", "25");
    var q = valor("ecom_leads_q");
    var estatus = valor("ecom_leads_estatus");
    if (q) params.set("q", q);
    if (estatus) params.set("estatus", estatus);
    fetch("/ecommercePublico/carritos_dashboard_erp?" + params.toString(), { headers: { Accept: "application/json" } })
      .then(jsonResponse)
      .then(function (response) {
        state.items = get(response, ["depurar", "items"], []);
        renderResumen(get(response, ["depurar", "resumen"], {}));
        renderTabla(state.items);
        setEstado(get(response, ["depurar", "configurado"], false) ? "Read-only" : "Sin esquema", get(response, ["depurar", "configurado"], false) ? "badge-light-success" : "badge-light-warning");
      })
      .catch(function (error) {
        setEstado("Error", "badge-light-danger");
        renderError(error.message || "No se pudo consultar Leads.");
      })
      .finally(function () {
        state.loading = false;
      });
  }

  function cargarProductos() {
    if (state.loadingProductos) return;
    state.loadingProductos = true;
    setEstadoProductos("Cargando", "badge-light-warning");
    var params = new URLSearchParams();
    params.set("limite", "80");
    var q = valor("ecom_leads_productos_q");
    var validacion = valor("ecom_leads_productos_validacion");
    var estatus = valor("ecom_leads_estatus");
    if (q) params.set("q", q);
    if (validacion) params.set("validacion", validacion);
    if (estatus) params.set("estatus", estatus);
    if (state.leadSeleccionado) params.set("id_carrito_lead", state.leadSeleccionado);
    renderFiltroLeadActivo();
    fetch("/ecommercePublico/productos_leads_erp?" + params.toString(), { headers: { Accept: "application/json" } })
      .then(jsonResponse)
      .then(function (response) {
        state.productos = get(response, ["depurar", "items"], []);
        renderResumenProductos(get(response, ["depurar", "resumen"], {}));
        renderGaleriaProductos(state.productos);
        renderTablaProductos(state.productos);
        setEstadoProductos(get(response, ["depurar", "configurado"], false) ? "Activo" : "Sin esquema", get(response, ["depurar", "configurado"], false) ? "badge-light-success" : "badge-light-warning");
      })
      .catch(function (error) {
        setEstadoProductos("Error", "badge-light-danger");
        renderErrorProductos(error.message || "No se pudieron consultar productos.");
      })
      .finally(function () {
        state.loadingProductos = false;
      });
  }

  function cargarDetalle(id, opciones) {
    if (!id) return;
    opciones = opciones || {};
    if (opciones.filtrarProductos !== false) {
      state.leadSeleccionado = String(id);
      renderFiltroLeadActivo();
      cargarProductos();
    }
    setDetalle('<div class="text-muted py-4">Cargando detalle...</div>');
    fetch("/ecommercePublico/carrito_detalle_erp/" + encodeURIComponent(id), { headers: { Accept: "application/json" } })
      .then(jsonResponse)
      .then(renderDetalle)
      .catch(function (error) {
        setDetalle('<div class="text-danger py-4">' + escapeHtml(error.message || "No se pudo consultar detalle.") + "</div>");
      });
  }

  function cargarPlan(id, accion) {
    setDetalle('<div class="text-muted py-4">Preparando plan read-only...</div>');
    fetch("/ecommercePublico/carrito_accion_plan_erp", {
      method: "POST",
      headers: { Accept: "application/json", "Content-Type": "application/json" },
      body: JSON.stringify({ id_carrito_lead: id, accion: accion, nota: "Revision interna read-only" })
    })
      .then(jsonResponse)
      .then(function (response) {
        setDetalle([
          '<div class="alert alert-warning py-3">Este plan no cambia estatus, no crea pedido, no crea venta y no descuenta inventario.</div>',
          '<pre class="bg-light p-4 rounded ecom-lead-code">' + escapeHtml(JSON.stringify(get(response, ["depurar"], {}), null, 2)) + "</pre>"
        ].join(""));
      })
      .catch(function (error) {
        setDetalle('<div class="text-danger py-4">' + escapeHtml(error.message || "No se pudo preparar plan.") + "</div>");
      });
  }

  function renderResumen(resumen) {
    setText("ecom_leads_kpi_total", resumen.total || 0);
    setText("ecom_leads_kpi_anonimos", resumen.anonimos || 0);
    setText("ecom_leads_kpi_contacto", resumen.con_contacto || 0);
    setText("ecom_leads_kpi_whatsapp", resumen.whatsapp_abierto || 0);
  }

  function renderResumenProductos(resumen) {
    setText("ecom_leads_productos_total", resumen.total || 0);
    setText("ecom_leads_productos_piezas", Number(resumen.piezas_total || 0).toLocaleString("es-MX"));
    setText("ecom_leads_productos_vigentes", resumen.vigentes || 0);
    setText("ecom_leads_productos_revision", resumen.requieren_revision || 0);
  }

  function renderTabla(items) {
    var tbody = document.getElementById("ecom_leads_body");
    var empty = document.getElementById("ecom_leads_empty");
    if (!tbody) return;
    if (!Array.isArray(items) || items.length === 0) {
      tbody.innerHTML = "";
      if (empty) empty.classList.remove("d-none");
      return;
    }
    if (empty) empty.classList.add("d-none");
    tbody.innerHTML = items.map(function (item) {
      var contacto = item.nombre_contacto || item.telefono_contacto || item.correo_contacto || "Anonimo";
      return [
        "<tr>",
        "<td><span class=\"fw-semibold\">" + escapeHtml(item.fecha_ultima_actividad || item.fecha_registro || "-") + "</span><div class=\"text-muted fs-8\">" + escapeHtml(item.canal || "web_publica") + "</div></td>",
        "<td><span class=\"fw-bold\">" + escapeHtml(contacto) + "</span><div class=\"text-muted fs-8\">" + escapeHtml(item.session_id_hash || "-") + "</div></td>",
        "<td><span class=\"fw-semibold\">" + Number(item.items_total || 0) + " item(s)</span><div class=\"text-muted fs-8\">" + Number(item.piezas_total || 0) + " pieza(s)</div></td>",
        "<td>" + badge(item.estatus) + '<div class="text-muted fs-8">' + escapeHtml(item.etapa_actual || "-") + "</div></td>",
        '<td class="text-end fw-bold">$' + money(item.subtotal_estimado) + " " + escapeHtml(item.moneda || "MXN") + "</td>",
        "<td>" + escapeHtml(item.ultima_ruta || "-") + '<div class="text-muted fs-8">' + flags(item) + "</div></td>",
        '<td class="text-end"><div class="d-flex justify-content-end gap-2"><button class="btn btn-sm btn-light-primary" data-id="' + escapeHtml(item.id_carrito_lead) + '">Ver</button><button class="btn btn-sm btn-light" data-plan="marcar_en_seguimiento" data-id="' + escapeHtml(item.id_carrito_lead) + '">Plan</button></div></td>',
        "</tr>"
      ].join("");
    }).join("");
    Array.prototype.forEach.call(tbody.querySelectorAll("button[data-id]"), function (btn) {
      btn.addEventListener("click", function () {
        var accion = btn.getAttribute("data-plan");
        if (accion) cargarPlan(btn.getAttribute("data-id"), accion);
        else cargarDetalle(btn.getAttribute("data-id"));
      });
    });
  }

  function renderTablaProductos(items) {
    var tbody = document.getElementById("ecom_leads_productos_body");
    var empty = document.getElementById("ecom_leads_productos_empty");
    if (!tbody) return;
    if (!Array.isArray(items) || items.length === 0) {
      tbody.innerHTML = "";
      if (empty) empty.classList.remove("d-none");
      return;
    }
    if (empty) empty.classList.add("d-none");
    tbody.innerHTML = items.map(function (item) {
      var contacto = item.nombre_contacto || item.telefono_contacto || item.correo_contacto || "Anonimo";
      return [
        "<tr>",
        "<td><span class=\"fw-semibold\">" + escapeHtml(item.fecha_ultima_actividad || item.fecha_item || "-") + "</span><div class=\"text-muted fs-8\">" + escapeHtml(item.canal || "web_publica") + "</div></td>",
        '<td class="ecom-lead-product"><div class="d-flex align-items-start gap-3"><img class="ecom-lead-thumb" src="' + escapeHtml(imagenUrl(item.imagen_url)) + '" alt=""><div><span class="fw-bold">' + escapeHtml(item.nombre || item.slug || "Producto") + '</span><div class="text-muted fs-8">SKU ' + escapeHtml(item.sku || item.id_sku || "-") + " / Pub " + escapeHtml(item.id_publicacion || "-") + '</div><div class="text-muted fs-8">' + escapeHtml(item.slug || "") + "</div></div></div></td>",
        "<td>" + badgeValidacion(item.validacion_publicacion) + detalleValidacion(item.validacion_detalle) + "</td>",
        "<td><span class=\"fw-semibold\">" + escapeHtml(contacto) + "</span><div class=\"text-muted fs-8\">" + escapeHtml(item.session_id_hash || "-") + "</div><div class=\"text-muted fs-8\">" + badge(item.estatus || "-") + "</div></td>",
        '<td class="text-end fw-bold">' + Number(item.cantidad || 0).toLocaleString("es-MX") + "</td>",
        '<td class="text-end fw-bold">$' + money(item.subtotal) + " " + escapeHtml(item.moneda || "MXN") + "</td>",
        '<td class="text-end"><button class="btn btn-sm btn-light-primary" data-producto-lead="' + escapeHtml(item.id_carrito_lead) + '">Ver lead</button></td>',
        "</tr>"
      ].join("");
    }).join("");
    Array.prototype.forEach.call(tbody.querySelectorAll("button[data-producto-lead]"), function (btn) {
      btn.addEventListener("click", function () {
        cargarDetalle(btn.getAttribute("data-producto-lead"));
        var detalleNode = document.getElementById("ecom_leads_detalle");
        if (detalleNode && detalleNode.scrollIntoView) {
          detalleNode.scrollIntoView({ behavior: "smooth", block: "start" });
        }
      });
    });
  }

  function renderGaleriaProductos(items) {
    var node = document.getElementById("ecom_leads_productos_galeria");
    if (!node) return;
    if (!Array.isArray(items) || items.length === 0) {
      node.innerHTML = "";
      return;
    }
    node.innerHTML = items.slice(0, 24).map(function (item) {
      return [
        '<div class="ecom-lead-gallery-card">',
        '<img class="ecom-lead-gallery-card__image" src="' + escapeHtml(imagenUrl(item.imagen_url)) + '" alt="">',
        '<div class="ecom-lead-gallery-card__body">',
        '<div class="ecom-lead-gallery-card__title">' + escapeHtml(item.nombre || item.slug || "Producto") + "</div>",
        '<div class="text-muted fs-8">SKU ' + escapeHtml(item.sku || item.id_sku || "-") + " / Pub " + escapeHtml(item.id_publicacion || "-") + "</div>",
        '<div>' + badgeValidacion(item.validacion_publicacion) + "</div>",
        '<div class="d-flex justify-content-between text-muted fs-8"><span>Cant. ' + Number(item.cantidad || 0).toLocaleString("es-MX") + '</span><span>$' + money(item.subtotal) + "</span></div>",
        '<button class="btn btn-sm btn-light-primary mt-auto" type="button" data-galeria-lead="' + escapeHtml(item.id_carrito_lead) + '">Ver lead</button>',
        "</div>",
        "</div>"
      ].join("");
    }).join("");
    Array.prototype.forEach.call(node.querySelectorAll("button[data-galeria-lead]"), function (btn) {
      btn.addEventListener("click", function () {
        cargarDetalle(btn.getAttribute("data-galeria-lead"));
        var detalleNode = document.getElementById("ecom_leads_detalle");
        if (detalleNode && detalleNode.scrollIntoView) {
          detalleNode.scrollIntoView({ behavior: "smooth", block: "start" });
        }
      });
    });
  }

  function renderDetalle(response) {
    var depurar = get(response, ["depurar"], {});
    var item = depurar.item || null;
    var detalle = depurar.detalle || [];
    var eventos = depurar.eventos || [];
    var notas = depurar.notas || [];
    state.detalle = { item: item, detalle: detalle };
    toggleCopiar(!!item);
    if (!item) {
      setDetalle('<div class="text-muted py-4">Lead no encontrado o el esquema aun no tiene registros.</div>');
      return;
    }
    setDetalle([
      '<div class="row g-4 mb-4">',
      resumenBox("Contacto", item.nombre_contacto || item.telefono_contacto || item.correo_contacto || "Anonimo", item.session_id_hash || ""),
      resumenBox("Estatus", item.estatus || "-", item.etapa_actual || ""),
      resumenBox("Total", "$" + money(item.subtotal_estimado) + " " + (item.moneda || "MXN"), Number(item.items_total || 0) + " item(s)"),
      "</div>",
      '<div class="fw-bold mb-2">Resumen WhatsApp</div>',
      '<pre class="bg-light p-4 rounded ecom-lead-code">' + escapeHtml(resumenWhatsapp(item, detalle)) + "</pre>",
      '<div class="fw-bold mb-2">Items snapshot</div>',
      '<pre class="bg-light p-4 rounded ecom-lead-code">' + escapeHtml(JSON.stringify(detalle, null, 2)) + "</pre>",
      '<div class="fw-bold mb-2">Eventos</div>',
      '<pre class="bg-light p-4 rounded ecom-lead-code">' + escapeHtml(JSON.stringify(eventos, null, 2)) + "</pre>",
      '<div class="fw-bold mb-2">Notas</div>',
      '<pre class="bg-light p-4 rounded ecom-lead-code">' + escapeHtml(JSON.stringify(notas, null, 2)) + "</pre>"
    ].join(""));
  }

  function resumenBox(label, value, meta) {
    return '<div class="col-md-4"><div class="ecom-lead-summary"><div class="text-muted fs-8 text-uppercase fw-bold">' + escapeHtml(label) + '</div><div class="fw-bold">' + escapeHtml(value) + '</div><div class="text-muted fs-8">' + escapeHtml(meta || "") + "</div></div></div>";
  }

  function copiarResumen() {
    if (!state.detalle || !state.detalle.item) return;
    var texto = resumenWhatsapp(state.detalle.item, state.detalle.detalle || []);
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(texto);
      setEstado("Resumen copiado", "badge-light-success");
    }
  }

  function resumenWhatsapp(item, detalle) {
    var lineas = ["Resumen de carrito ecommerce", "Estatus: " + (item.estatus || "-"), "Contacto: " + (item.nombre_contacto || item.telefono_contacto || item.correo_contacto || "Anonimo"), "Total estimado: $" + money(item.subtotal_estimado) + " " + (item.moneda || "MXN"), "", "Productos:"];
    if (Array.isArray(detalle) && detalle.length) {
      detalle.forEach(function (row) {
        lineas.push("- " + (row.nombre_snapshot || row.slug || row.sku_snapshot || "Producto") + " x " + Number(row.cantidad || 0) + " = $" + money(row.subtotal_snapshot));
      });
    } else {
      lineas.push("- Sin detalle disponible");
    }
    return lineas.join("\n");
  }

  function flags(item) {
    var salida = [];
    if (item.whatsapp_abierto) salida.push("WhatsApp");
    if (item.acepta_politicas) salida.push("Politicas");
    if (item.solicito_facturacion) salida.push("Facturacion");
    return salida.length ? salida.map(escapeHtml).join(" / ") : "-";
  }

  function renderError(message) {
    var tbody = document.getElementById("ecom_leads_body");
    if (tbody) tbody.innerHTML = '<tr><td colspan="7" class="text-danger py-5">' + escapeHtml(message) + "</td></tr>";
  }

  function renderErrorProductos(message) {
    var tbody = document.getElementById("ecom_leads_productos_body");
    if (tbody) tbody.innerHTML = '<tr><td colspan="7" class="text-danger py-5">' + escapeHtml(message) + "</td></tr>";
  }

  function setDetalle(html) {
    var node = document.getElementById("ecom_leads_detalle");
    if (node) node.innerHTML = html;
  }

  function toggleCopiar(show) {
    var btn = document.getElementById("ecom_leads_copiar");
    if (btn) btn.classList.toggle("d-none", !show);
  }

  function limpiarSeleccionLead() {
    state.leadSeleccionado = null;
    renderFiltroLeadActivo();
    cargarProductos();
  }

  function renderFiltroLeadActivo() {
    var badgeFiltro = document.getElementById("ecom_leads_productos_filtro_lead");
    var limpiar = document.getElementById("ecom_leads_productos_limpiar");
    var activo = !!state.leadSeleccionado;
    if (badgeFiltro) {
      badgeFiltro.classList.toggle("d-none", !activo);
      badgeFiltro.textContent = activo ? "Viendo solo lead #" + state.leadSeleccionado : "Todos los leads";
    }
    if (limpiar) limpiar.classList.toggle("d-none", !activo);
  }

  function setEstado(texto, clase) {
    var node = document.getElementById("ecom_leads_estado");
    if (!node) return;
    node.className = "badge " + clase;
    node.textContent = texto;
  }

  function setEstadoProductos(texto, clase) {
    var node = document.getElementById("ecom_leads_productos_estado");
    if (!node) return;
    node.className = "badge " + clase;
    node.textContent = texto;
  }

  function badge(estatus) {
    var texto = estatus || "sin_estatus";
    var clase = texto === "whatsapp_abierto" || texto === "convertido" ? "badge-light-success" : texto === "descartado" ? "badge-light-danger" : texto === "en_seguimiento" ? "badge-light-primary" : "badge-light-warning";
    return '<span class="badge ' + clase + '">' + escapeHtml(texto) + "</span>";
  }

  function badgeValidacion(valor) {
    var texto = valor || "sin_validacion";
    var clase = texto === "publicacion_vigente" ? "badge-light-success" : texto === "identificadores_inconsistentes" || texto === "no_encontrado" ? "badge-light-danger" : "badge-light-warning";
    return '<span class="badge ' + clase + '">' + escapeHtml(texto) + "</span>";
  }

  function detalleValidacion(detalle) {
    if (!detalle || typeof detalle !== "object") return "";
    var partes = [];
    if (detalle.sku_catalogo) partes.push("SKU catalogo: " + detalle.sku_catalogo);
    if (detalle.estatus_publicacion) partes.push("Pub: " + detalle.estatus_publicacion);
    if (detalle.motivo) partes.push(detalle.motivo);
    return partes.length ? '<div class="text-muted fs-8 mt-1">' + escapeHtml(partes.join(" / ")) + "</div>" : "";
  }

  function jsonResponse(response) { return response.json(); }
  function valor(id) { var el = document.getElementById(id); return el ? String(el.value || "").trim() : ""; }
  function setText(id, value) { var el = document.getElementById(id); if (el) el.textContent = String(value); }
  function money(value) { return Number(value || 0).toLocaleString("es-MX", { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
  function imagenUrl(url) {
    url = String(url || "").trim();
    if (!url) return placeholderImagen;
    if (/^(https?:)?\/\//i.test(url) || url.indexOf("data:") === 0 || url.charAt(0) === "/") return url;
    return "/" + url.replace(/^\/+/, "");
  }
  function debounce(fn, wait) { var timer = null; return function () { clearTimeout(timer); timer = setTimeout(fn, wait); }; }
  function get(obj, path, fallback) {
    var current = obj;
    for (var i = 0; i < path.length; i++) {
      if (!current || typeof current !== "object" || !(path[i] in current)) return fallback;
      current = current[path[i]];
    }
    return current;
  }
  function escapeHtml(value) {
    return String(value == null ? "" : value).replace(/[&<>"']/g, function (char) {
      return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#039;" }[char];
    });
  }
})();
