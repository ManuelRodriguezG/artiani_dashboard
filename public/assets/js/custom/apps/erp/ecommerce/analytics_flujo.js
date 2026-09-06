/*
 * Documentacion IA: Codex GPT-5, 2026-09-05.
 * Proposito: UX read-only para flujo de navegacion por sesion anonima Ecommerce Analytics.
 * Impacto: permite analizar recorridos sin mostrar PII, stock exacto, ventas ni inventario.
 * Contrato: consume GET protegido /ecommercePublico/analytics_flujo_erp.
 */
(function () {
  "use strict";

  var estado = { sessionKey: "" };

  document.addEventListener("DOMContentLoaded", function () {
    iniciarFechas();
    bindEvents();
    cargarFlujo();
  });

  function iniciarFechas() {
    var hasta = new Date();
    var desde = new Date();
    desde.setDate(hasta.getDate() - 7);
    setValue("ecom_flow_desde", isoDate(desde));
    setValue("ecom_flow_hasta", isoDate(hasta));
  }

  function bindEvents() {
    on("ecom_flow_recargar", "click", function () { cargarFlujo(estado.sessionKey); });
    on("ecom_flow_desde", "change", function () { estado.sessionKey = ""; cargarFlujo(); });
    on("ecom_flow_hasta", "change", function () { estado.sessionKey = ""; cargarFlujo(); });
    on("ecom_flow_limite", "change", function () { estado.sessionKey = ""; cargarFlujo(); });
    var sesiones = document.getElementById("ecom_flow_sesiones");
    if (sesiones) {
      sesiones.addEventListener("click", function (event) {
        var button = event.target.closest("[data-session-key]");
        if (!button) return;
        estado.sessionKey = button.getAttribute("data-session-key") || "";
        cargarFlujo(estado.sessionKey);
      });
    }
  }

  function cargarFlujo(sessionKey) {
    setEstado("Cargando", "badge-light-warning");
    var params = new URLSearchParams();
    params.set("desde", valor("ecom_flow_desde"));
    params.set("hasta", valor("ecom_flow_hasta"));
    params.set("limite", valor("ecom_flow_limite") || "25");
    if (sessionKey) params.set("session_key", sessionKey);
    fetch("/ecommercePublico/analytics_flujo_erp?" + params.toString(), { headers: { "Accept": "application/json" } })
      .then(function (response) { return response.json(); })
      .then(renderFlujo)
      .catch(function (error) {
        setEstado("Error", "badge-light-danger");
        renderTimeline([{ etiqueta: "Error", ruta: error.message || "No se pudo consultar flujo", fecha: "", tipo: "error" }]);
      });
  }

  function renderFlujo(response) {
    var depurar = get(response, ["depurar"], {});
    var sesiones = get(depurar, ["sesiones"], []);
    estado.sessionKey = get(depurar, ["session_key"], estado.sessionKey);
    renderSesiones(sesiones, estado.sessionKey);
    renderSesionSeleccionada(get(depurar, ["sesion_seleccionada"], {}), get(depurar, ["resumen"], {}), get(depurar, ["timeline"], []));
    renderTimeline(get(depurar, ["timeline"], []));
    setText("ecom_flow_actualizado", get(depurar, ["fecha_consulta"], "-"));
    setText("ecom_flow_sesiones_total", sesiones.length);
    setEstado(get(depurar, ["configurado"], false) ? "Eventos crudos" : "Sin esquema", get(depurar, ["configurado"], false) ? "badge-light-success" : "badge-light-warning");
  }

  function renderSesiones(items, selectedKey) {
    var node = document.getElementById("ecom_flow_sesiones");
    if (!node) return;
    if (!Array.isArray(items) || items.length === 0) {
      node.innerHTML = '<div class="text-muted py-3">Sin sesiones en el rango.</div>';
      return;
    }
    node.innerHTML = items.map(function (item) {
      var utm = [item.utm_source, item.utm_medium, item.utm_campaign].filter(Boolean).join(" / ");
      var active = item.session_key === selectedKey ? " is-active" : "";
      return '<button class="ecom-flow-session' + active + '" type="button" data-session-key="' + escapeHtml(item.session_key || "") + '">' +
        '<div class="d-flex justify-content-between gap-3"><div><div class="fw-bold">' + escapeHtml(item.session_key || "-") + '</div><div class="text-muted fs-8">' + escapeHtml(item.dispositivo_aproximado || item.canal || "-") + '</div></div><span class="badge badge-light-primary">' + escapeHtml(Number(item.eventos_rango || 0) + Number(item.busquedas_rango || 0)) + '</span></div>' +
        '<div class="ecom-flow-route fw-semibold mt-2">' + escapeHtml(item.ultimo_ruta || item.primer_ruta || "-") + '</div>' +
        '<div class="text-muted fs-8 ecom-flow-route">' + escapeHtml(utm || item.referrer || "-") + '</div>' +
        '<div class="text-muted fs-8 mt-2">' + escapeHtml(item.fecha_ultima_actividad || item.fecha_inicio || "-") + '</div>' +
      '</button>';
    }).join("");
  }

  function renderSesionSeleccionada(sesion, resumen, timeline) {
    setText("ecom_flow_session_titulo", sesion.session_key ? "Sesion " + sesion.session_key : "Sesion");
    var utm = [sesion.utm_source, sesion.utm_medium, sesion.utm_campaign].filter(Boolean).join(" / ");
    setText("ecom_flow_session_subtitulo", [sesion.canal || "", sesion.dispositivo_aproximado || "", utm || sesion.referrer || ""].filter(Boolean).join(" | ") || "-");
    setText("ecom_flow_kpi_eventos", resumen.eventos_total || 0);
    setText("ecom_flow_kpi_busquedas", resumen.busquedas_total || 0);
    setText("ecom_flow_kpi_conversiones", resumen.conversiones_total || 0);
    setText("ecom_flow_kpi_timeline", Array.isArray(timeline) ? timeline.length : 0);
  }

  function renderTimeline(items) {
    var node = document.getElementById("ecom_flow_timeline");
    if (!node) return;
    if (!Array.isArray(items) || items.length === 0) {
      node.innerHTML = '<div class="text-muted py-3">Selecciona una sesion con eventos en el rango.</div>';
      return;
    }
    node.innerHTML = items.map(function (item) {
      var dotClass = item.origen === "busqueda" ? " is-search" : (item.es_conversion ? " is-conversion" : "");
      var detalle = detalleTimeline(item);
      return '<div class="ecom-flow-item">' +
        '<div class="ecom-flow-dot' + dotClass + '"><i class="bi ' + iconoEvento(item) + '"></i></div>' +
        '<div class="ecom-flow-card">' +
          '<div class="d-flex justify-content-between align-items-start gap-3"><div><div class="fw-bold">' + escapeHtml(item.etiqueta || item.tipo || "-") + '</div><div class="text-muted fs-8">' + escapeHtml(item.fecha || "-") + '</div></div><span class="badge badge-light">' + escapeHtml(item.tipo || "-") + '</span></div>' +
          '<div class="ecom-flow-route mt-2">' + escapeHtml(item.ruta || item.slug || "-") + '</div>' +
          (detalle ? '<div class="text-muted fs-8 mt-1">' + escapeHtml(detalle) + '</div>' : '') +
        '</div>' +
      '</div>';
    }).join("");
  }

  function detalleTimeline(item) {
    if (item.origen === "busqueda") {
      return "Busqueda: " + (item.query || "-") + " | resultados: " + Number(item.resultados_total || 0) + (item.sin_resultados ? " | sin resultados" : "");
    }
    var datos = [];
    if (item.slug) datos.push("slug: " + item.slug);
    if (item.id_publicacion) datos.push("pub: " + item.id_publicacion);
    if (item.id_sku) datos.push("sku: " + item.id_sku);
    if (item.mascota) datos.push("mascota: " + item.mascota);
    if (item.necesidad) datos.push("necesidad: " + item.necesidad);
    return datos.join(" | ");
  }

  function iconoEvento(item) {
    return {
      page_view: "bi-window",
      view_product: "bi-box",
      search: "bi-search",
      add_to_quote: "bi-cart-plus",
      remove_from_quote: "bi-cart-dash",
      quote_dryrun: "bi-ui-checks",
      quote_preflight: "bi-shield-check",
      open_whatsapp: "bi-whatsapp",
      facturacion_view: "bi-receipt",
      facturacion_submit: "bi-send"
    }[item.tipo] || "bi-dot";
  }

  function on(id, eventName, callback) {
    var node = document.getElementById(id);
    if (node) node.addEventListener(eventName, callback);
  }

  function valor(id) {
    var el = document.getElementById(id);
    return el ? String(el.value || "").trim() : "";
  }

  function setValue(id, value) {
    var el = document.getElementById(id);
    if (el) el.value = value;
  }

  function setText(id, value) {
    var el = document.getElementById(id);
    if (el) el.textContent = String(value == null ? "" : value);
  }

  function setEstado(texto, clase) {
    var node = document.getElementById("ecom_flow_estado");
    if (!node) return;
    node.className = "badge " + clase;
    node.textContent = texto;
  }

  function get(obj, path, fallback) {
    var current = obj;
    for (var i = 0; i < path.length; i++) {
      if (!current || typeof current !== "object" || !(path[i] in current)) return fallback;
      current = current[path[i]];
    }
    return current;
  }

  function isoDate(date) {
    return date.toISOString().slice(0, 10);
  }

  function escapeHtml(value) {
    return String(value == null ? "" : value).replace(/[&<>"']/g, function (char) {
      return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#039;" }[char];
    });
  }
})();
