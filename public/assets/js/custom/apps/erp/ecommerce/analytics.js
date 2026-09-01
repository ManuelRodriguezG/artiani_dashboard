/*
 * Documentacion IA: Codex GPT-5, 2026-08-31.
 * Proposito: UX interna para dashboard Ecommerce / Analytics.
 * Impacto: consulta metricas anonimas de persistencia real sin registrar ventas, checkout ni inventario.
 * Contrato: solo GET interno protegido.
 */
(function () {
  "use strict";

  document.addEventListener("DOMContentLoaded", function () {
    iniciarFechas();
    bindEvents();
    cargarDashboard();
  });

  function iniciarFechas() {
    var hasta = new Date();
    var desde = new Date();
    desde.setDate(hasta.getDate() - 30);
    setValue("ecom_an_desde", isoDate(desde));
    setValue("ecom_an_hasta", isoDate(hasta));
  }

  function bindEvents() {
    var recargar = document.getElementById("ecom_an_recargar");
    var desde = document.getElementById("ecom_an_desde");
    var hasta = document.getElementById("ecom_an_hasta");
    var limite = document.getElementById("ecom_an_limite");
    if (recargar) recargar.addEventListener("click", cargarDashboard);
    if (desde) desde.addEventListener("change", cargarDashboard);
    if (hasta) hasta.addEventListener("change", cargarDashboard);
    if (limite) limite.addEventListener("change", cargarDashboard);
  }

  function cargarDashboard() {
    setEstado("Cargando", "badge-light-warning");
    var params = new URLSearchParams();
    params.set("desde", valor("ecom_an_desde"));
    params.set("hasta", valor("ecom_an_hasta"));
    params.set("limite", valor("ecom_an_limite") || "10");
    fetch("/ecommercePublico/analytics_dashboard_erp?" + params.toString(), { headers: { "Accept": "application/json" } })
      .then(jsonResponse)
      .then(renderDashboard)
      .catch(function (error) {
        setEstado("Error", "badge-light-danger");
        renderList("ecom_an_urls", [{ valor: error.message || "No se pudo consultar analytics", total: "" }]);
      });
  }

  function renderDashboard(response) {
    var depurar = get(response, ["depurar"], {});
    var resumen = get(depurar, ["resumen"], {});
    setText("ecom_an_kpi_sesiones", resumen.sesiones_total || 0);
    setText("ecom_an_kpi_eventos", resumen.eventos_total || 0);
    setText("ecom_an_kpi_page_views", resumen.page_views || 0);
    setText("ecom_an_kpi_productos_vistos", resumen.productos_vistos || 0);
    setText("ecom_an_kpi_busquedas", resumen.busquedas_total || 0);
    setText("ecom_an_kpi_whatsapp", resumen.whatsapp_total || 0);
    setText("ecom_an_kpi_facturacion", resumen.facturacion_submit_total || 0);
    renderEmbudo(get(depurar, ["embudo"], {}));
    renderAbandono(get(depurar, ["abandono_por_etapa"], []));
    renderSesiones(get(depurar, ["sesiones_recientes"], []));
    renderList("ecom_an_canales", get(depurar, ["canales"], []));
    renderList("ecom_an_urls", get(depurar, ["urls_mas_vistas"], []));
    renderProductos("ecom_an_productos_vistos", get(depurar, ["productos_mas_vistos"], []));
    renderProductos("ecom_an_productos_cotizacion", get(depurar, ["productos_agregados_cotizacion"], []));
    renderList("ecom_an_conversiones", get(depurar, ["conversiones_por_tipo"], []), etiquetaConversion);
    renderList("ecom_an_busquedas", get(depurar, ["busquedas_frecuentes"], []));
    renderList("ecom_an_sin_resultados", get(depurar, ["busquedas_sin_resultados"], []));
    renderFacturacion(get(depurar, ["facturacion_eventos"], []));
    renderProductos("ecom_an_interes_sin_conversion", get(depurar, ["productos_interes_sin_conversion"], []), "vistas");
    renderList("ecom_an_mascotas", get(depurar, ["mascotas_consultadas"], []));
    renderList("ecom_an_necesidades", get(depurar, ["necesidades_consultadas"], []));
    toggleEmpty(!get(depurar, ["configurado"], false) || Number(resumen.eventos_total || 0) + Number(resumen.busquedas_total || 0) === 0);
    setEstado(get(depurar, ["configurado"], false) ? etiquetaFuente(get(depurar, ["fuente_metricas"], "eventos_crudos")) : "Sin esquema", get(depurar, ["configurado"], false) ? "badge-light-success" : "badge-light-warning");
    setPersistencia(get(depurar, ["persistencia", "modo_actual"], ""));
    setText("ecom_an_ultimo_evento", resumenUltimoEvento(get(depurar, ["ultimo_evento"], {})));
    setText("ecom_an_actualizado", get(depurar, ["fecha_consulta"], "-"));
  }

  function renderEmbudo(embudo) {
    var node = document.getElementById("ecom_an_embudo");
    if (!node) return;
    var pasos = [
      ["page_view", "Visita"],
      ["view_product", "Producto"],
      ["add_to_quote", "Cotizacion"],
      ["quote_dryrun", "Dry-run"],
      ["quote_preflight", "Preflight"],
      ["open_whatsapp", "WhatsApp"]
    ];
    node.innerHTML = pasos.map(function (paso) {
      return '<div class="ecom-an-step"><span class="text-muted fs-8">' + escapeHtml(paso[1]) + '</span><strong>' + Number(embudo[paso[0]] || 0) + '</strong><span class="text-muted fs-8">' + escapeHtml(paso[0]) + '</span></div>';
    }).join("");
  }

  function renderList(id, items, formatter) {
    var node = document.getElementById(id);
    if (!node) return;
    if (!Array.isArray(items) || items.length === 0) {
      node.innerHTML = '<div class="text-muted py-3">Sin datos en el rango.</div>';
      return;
    }
    node.innerHTML = '<div class="table-responsive"><table class="table table-row-dashed fs-7 gy-3 mb-0"><tbody>' + items.map(function (item) {
      var label = item.valor || item.query_normalizada || item.termino || "";
      if (typeof formatter === "function") label = formatter(label);
      return '<tr><td class="fw-semibold">' + escapeHtml(label) + '</td><td class="text-end fw-bold">' + escapeHtml(item.total == null ? "" : item.total) + '</td></tr>';
    }).join("") + '</tbody></table></div>';
  }

  function renderSesiones(items) {
    var node = document.getElementById("ecom_an_sesiones");
    if (!node) return;
    if (!Array.isArray(items) || items.length === 0) {
      node.innerHTML = '<div class="text-muted py-3">Sin datos en el rango.</div>';
      return;
    }
    node.innerHTML = '<div class="table-responsive ecom-an-scroll"><table class="table table-row-dashed fs-7 gy-3 mb-0"><thead><tr class="text-muted fw-bold"><th>Sesion</th><th>Ruta</th><th>Canal</th><th class="text-end">Eventos</th><th>Actividad</th></tr></thead><tbody>' + items.map(function (item) {
      var utm = [item.utm_source, item.utm_medium, item.utm_campaign].filter(Boolean).join(" / ");
      return '<tr><td><span class="fw-semibold">' + escapeHtml(item.session_id_hash_corto || "-") + '</span><div class="text-muted fs-8">' + escapeHtml(item.dispositivo_aproximado || "-") + '</div></td><td><span class="fw-semibold">' + escapeHtml(item.ultimo_ruta || item.primer_ruta || "-") + '</span><div class="text-muted fs-8">' + escapeHtml(utm || item.referrer || "-") + '</div></td><td>' + escapeHtml(item.canal || "-") + '</td><td class="text-end fw-bold">' + escapeHtml(item.eventos_total || 0) + '</td><td><span class="text-muted fs-8">' + escapeHtml(item.fecha_ultima_actividad || item.fecha_inicio || "-") + '</span></td></tr>';
    }).join("") + '</tbody></table></div>';
  }

  function renderFacturacion(items) {
    var node = document.getElementById("ecom_an_facturacion");
    if (!node) return;
    if (!Array.isArray(items) || items.length === 0) {
      node.innerHTML = '<div class="text-muted py-3">Sin datos en el rango.</div>';
      return;
    }
    node.innerHTML = '<div class="table-responsive"><table class="table table-row-dashed fs-7 gy-3 mb-0"><tbody>' + items.map(function (item) {
      return '<tr><td><span class="fw-semibold">' + escapeHtml(etiquetaConversion(item.tipo_conversion)) + '</span><div class="text-muted fs-8">' + escapeHtml(item.ruta_origen || "-") + '</div></td><td><span class="text-muted fs-8">' + escapeHtml(item.fecha_registro || "-") + '</span></td></tr>';
    }).join("") + '</tbody></table></div>';
  }

  function renderAbandono(items) {
    var node = document.getElementById("ecom_an_abandono");
    if (!node) return;
    if (!Array.isArray(items) || items.length === 0) {
      node.innerHTML = '<div class="text-muted py-3">Sin datos en el rango.</div>';
      return;
    }
    node.innerHTML = '<div class="table-responsive"><table class="table table-row-dashed fs-7 gy-3 mb-0"><tbody>' + items.map(function (item) {
      var ratio = item.ratio_paso == null ? "-" : Math.round(Number(item.ratio_paso) * 100) + "%";
      return '<tr><td><span class="fw-semibold">' + escapeHtml(etiquetaPaso(item.de)) + '</span><div class="text-muted fs-8">a ' + escapeHtml(etiquetaPaso(item.a)) + '</div></td><td class="text-end"><div class="fw-bold">' + escapeHtml(item.abandono_estimado || 0) + '</div><div class="text-muted fs-8">' + escapeHtml(ratio) + '</div></td></tr>';
    }).join("") + '</tbody></table></div>';
  }

  function renderProductos(id, items, totalKey) {
    var node = document.getElementById(id);
    if (!node) return;
    totalKey = totalKey || "total";
    if (!Array.isArray(items) || items.length === 0) {
      node.innerHTML = '<div class="text-muted py-3">Sin datos en el rango.</div>';
      return;
    }
    node.innerHTML = '<div class="table-responsive"><table class="table table-row-dashed fs-7 gy-3 mb-0"><tbody>' + items.map(function (item) {
      var etiqueta = item.slug || ("Publicacion " + (item.id_publicacion || "-") + " / SKU " + (item.id_sku || "-"));
      return '<tr><td><span class="fw-semibold">' + escapeHtml(etiqueta) + '</span><div class="text-muted fs-8">pub ' + escapeHtml(item.id_publicacion || "-") + ' / sku ' + escapeHtml(item.id_sku || "-") + '</div></td><td class="text-end fw-bold">' + escapeHtml(item[totalKey] == null ? item.total : item[totalKey]) + '</td></tr>';
    }).join("") + '</tbody></table></div>';
  }

  function toggleEmpty(show) {
    var node = document.getElementById("ecom_an_empty");
    if (!node) return;
    node.classList.toggle("d-none", !show);
  }

  function jsonResponse(response) {
    return response.json();
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
    if (el) el.textContent = String(value);
  }

  function setEstado(texto, clase) {
    var node = document.getElementById("ecom_an_estado");
    if (!node) return;
    node.className = "badge " + clase;
    node.textContent = texto;
  }

  function setPersistencia(modo) {
    var node = document.getElementById("ecom_an_persistencia");
    if (!node) return;
    var activo = modo === "registra_bd";
    node.className = "badge " + (activo ? "badge-light-success" : "badge-light-warning") + " ms-2";
    node.textContent = activo ? "Persistencia activa" : "Preflight";
  }

  function etiquetaFuente(fuente) {
    return fuente === "resumen_diario" ? "Resumen diario" : "Eventos crudos";
  }

  function resumenUltimoEvento(evento) {
    if (!evento || !evento.fecha_registro) return "-";
    var partes = [evento.fecha_registro, evento.tipo_evento || "", evento.ruta || evento.slug || ""].filter(Boolean);
    return partes.join(" | ");
  }

  function etiquetaPaso(paso) {
    return {
      page_view: "Visita",
      view_product: "Producto",
      add_to_quote: "Cotizacion",
      quote_dryrun: "Dry-run",
      quote_preflight: "Preflight",
      open_whatsapp: "WhatsApp"
    }[paso] || paso || "";
  }

  function etiquetaConversion(tipo) {
    return {
      add_to_quote: "Agregado a cotizacion",
      remove_from_quote: "Quitado de cotizacion",
      quote_dryrun: "Validacion carrito",
      quote_preflight: "Preflight cotizacion",
      open_whatsapp: "Apertura WhatsApp",
      facturacion_view: "Vista facturacion",
      facturacion_submit: "Envio facturacion"
    }[tipo] || tipo || "";
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
