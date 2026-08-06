/*
 * Documentacion IA: Codex GPT-5, 2026-08-04.
 * Proposito: UX read-only para dashboard Ecommerce / Analytics.
 * Impacto: consulta metricas anonimas sin registrar eventos, ventas, checkout ni inventario.
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
    setText("ecom_an_kpi_busquedas", resumen.busquedas_total || 0);
    setText("ecom_an_kpi_whatsapp", resumen.whatsapp_total || 0);
    renderEmbudo(get(depurar, ["embudo"], {}));
    renderList("ecom_an_urls", get(depurar, ["urls_mas_vistas"], []));
    renderProductos("ecom_an_productos_vistos", get(depurar, ["productos_mas_vistos"], []));
    renderProductos("ecom_an_productos_cotizacion", get(depurar, ["productos_agregados_cotizacion"], []));
    renderList("ecom_an_busquedas", get(depurar, ["busquedas_frecuentes"], []));
    renderList("ecom_an_sin_resultados", get(depurar, ["busquedas_sin_resultados"], []));
    renderProductos("ecom_an_interes_sin_conversion", get(depurar, ["productos_interes_sin_conversion"], []), "vistas");
    renderList("ecom_an_mascotas", get(depurar, ["mascotas_consultadas"], []));
    renderList("ecom_an_necesidades", get(depurar, ["necesidades_consultadas"], []));
    toggleEmpty(!get(depurar, ["configurado"], false) || Number(resumen.eventos_total || 0) + Number(resumen.busquedas_total || 0) === 0);
    setEstado(get(depurar, ["configurado"], false) ? etiquetaFuente(get(depurar, ["fuente_metricas"], "eventos_crudos")) : "Sin esquema", get(depurar, ["configurado"], false) ? "badge-light-success" : "badge-light-warning");
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

  function renderList(id, items) {
    var node = document.getElementById(id);
    if (!node) return;
    if (!Array.isArray(items) || items.length === 0) {
      node.innerHTML = '<div class="text-muted py-3">Sin datos en el rango.</div>';
      return;
    }
    node.innerHTML = '<div class="table-responsive"><table class="table table-row-dashed fs-7 gy-3 mb-0"><tbody>' + items.map(function (item) {
      return '<tr><td class="fw-semibold">' + escapeHtml(item.valor || item.query_normalizada || item.termino || "") + '</td><td class="text-end fw-bold">' + escapeHtml(item.total == null ? "" : item.total) + '</td></tr>';
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

  function etiquetaFuente(fuente) {
    return fuente === "resumen_diario" ? "Resumen diario" : "Read-only";
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
