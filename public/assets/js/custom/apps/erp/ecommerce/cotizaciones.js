/*
 * Documentacion IA: Codex GPT-5, 2026-07-29.
 * Proposito: UX read-only para bandeja interna de cotizaciones ecommerce.
 * Impacto: consulta seguimiento futuro sin registrar cotizaciones, pedidos, ventas ni inventario.
 * Contrato: solo GET internos protegidos; no ejecuta acciones de escritura.
 */
(function () {
  "use strict";

  var state = {
    items: [],
    loading: false
  };

  document.addEventListener("DOMContentLoaded", function () {
    bindEvents();
    cargarBandeja();
  });

  function bindEvents() {
    var recargar = document.getElementById("ecom_cotizaciones_recargar");
    var q = document.getElementById("ecom_cot_q");
    var estatus = document.getElementById("ecom_cot_estatus");
    if (recargar) recargar.addEventListener("click", cargarBandeja);
    if (q) q.addEventListener("input", debounce(cargarBandeja, 350));
    if (estatus) estatus.addEventListener("change", cargarBandeja);
  }

  function cargarBandeja() {
    if (state.loading) return;
    state.loading = true;
    setEstado("Cargando", "badge-light-warning");
    var params = new URLSearchParams();
    params.set("limite", "25");
    var q = valor("ecom_cot_q");
    var estatus = valor("ecom_cot_estatus");
    if (q) params.set("q", q);
    if (estatus) params.set("estatus", estatus);

    fetch("/ecommercePublico/cotizaciones_bandeja_erp?" + params.toString(), { headers: { "Accept": "application/json" } })
      .then(jsonResponse)
      .then(function (response) {
        state.items = get(response, ["depurar", "items"], []);
        renderResumen(get(response, ["depurar", "resumen"], {}));
        renderTabla(state.items);
        setEstado("Read-only", "badge-light-success");
      })
      .catch(function (error) {
        setEstado("Error", "badge-light-danger");
        renderError(error.message || "No se pudo consultar bandeja.");
      })
      .finally(function () {
        state.loading = false;
      });
  }

  function cargarDetalle(folio) {
    if (!folio) return;
    var contenedor = document.getElementById("ecom_cot_detalle");
    if (contenedor) contenedor.innerHTML = '<div class="text-muted py-4">Cargando detalle...</div>';
    fetch("/ecommercePublico/cotizacion_detalle_erp/" + encodeURIComponent(folio), { headers: { "Accept": "application/json" } })
      .then(jsonResponse)
      .then(function (response) {
        renderDetalle(response);
      })
      .catch(function (error) {
        renderDetalleError(error.message || "No se pudo consultar detalle.");
      });
  }

  function renderResumen(resumen) {
    setText("ecom_cot_kpi_total", get(resumen, ["total_en_pagina"], 0));
    setText("ecom_cot_kpi_nuevas", get(resumen, ["nuevas"], 0));
    setText("ecom_cot_kpi_seguimiento", get(resumen, ["en_seguimiento"], 0));
    setText("ecom_cot_kpi_convertidas", get(resumen, ["convertidas"], 0));
  }

  function renderTabla(items) {
    var tbody = document.getElementById("ecom_cot_body");
    var empty = document.getElementById("ecom_cot_empty");
    if (!tbody) return;
    if (!Array.isArray(items) || items.length === 0) {
      tbody.innerHTML = "";
      if (empty) empty.classList.remove("d-none");
      return;
    }
    if (empty) empty.classList.add("d-none");
    tbody.innerHTML = items.map(function (item) {
      return [
        "<tr>",
        "<td><span class=\"fw-bold\">" + escapeHtml(item.folio) + "</span><div class=\"text-muted fs-8\">" + escapeHtml(item.origen) + "</div></td>",
        "<td>" + escapeHtml(item.nombre_contacto || "Sin nombre") + "<div class=\"text-muted fs-8\">" + escapeHtml(item.telefono_contacto || item.correo_contacto || "Sin contacto") + "</div></td>",
        "<td>" + badge(item.estatus) + "</td>",
        "<td class=\"text-end fw-bold\">$" + money(item.total_estimado) + " " + escapeHtml(item.moneda || "MXN") + "</td>",
        "<td class=\"text-end\">" + Number(item.partidas || 0) + "</td>",
        "<td>" + escapeHtml(item.fecha_registro || "") + "</td>",
        "<td class=\"text-end\"><div class=\"d-flex justify-content-end gap-2\"><button class=\"btn btn-sm btn-light-primary\" data-folio=\"" + escapeHtml(item.folio) + "\">Ver</button><button class=\"btn btn-sm btn-light\" data-plan=\"marcar_seguimiento\" data-folio=\"" + escapeHtml(item.folio) + "\">Plan</button></div></td>",
        "</tr>"
      ].join("");
    }).join("");
    Array.prototype.forEach.call(tbody.querySelectorAll("button[data-folio]"), function (btn) {
      btn.addEventListener("click", function () {
        var accion = btn.getAttribute("data-plan");
        if (accion) {
          cargarPlanAccion(btn.getAttribute("data-folio"), accion);
        } else {
          cargarDetalle(btn.getAttribute("data-folio"));
        }
      });
    });
  }

  function cargarPlanAccion(folio, accion) {
    var contenedor = document.getElementById("ecom_cot_detalle");
    if (contenedor) contenedor.innerHTML = '<div class="text-muted py-4">Preparando plan read-only...</div>';
    fetch("/ecommercePublico/cotizacion_accion_plan_erp", {
      method: "POST",
      headers: { "Accept": "application/json", "Content-Type": "application/json" },
      body: JSON.stringify({ folio: folio, accion: accion, motivo: "Revision interna read-only" })
    })
      .then(jsonResponse)
      .then(renderPlanAccion)
      .catch(function (error) {
        renderDetalleError(error.message || "No se pudo preparar plan.");
      });
  }

  function renderDetalle(response) {
    var contenedor = document.getElementById("ecom_cot_detalle");
    if (!contenedor) return;
    var item = get(response, ["depurar", "item"], null);
    var detalle = get(response, ["depurar", "detalle"], []);
    var eventos = get(response, ["depurar", "eventos"], []);
    if (!item) {
      contenedor.innerHTML = '<div class="text-muted py-4">Cotizacion no encontrada o aun no existen registros reales.</div>';
      return;
    }
    contenedor.innerHTML = [
      '<div class="row g-4">',
      '<div class="col-lg-4"><div class="fw-bold">Folio</div><div>' + escapeHtml(item.folio) + '</div></div>',
      '<div class="col-lg-4"><div class="fw-bold">Contacto</div><div>' + escapeHtml(item.nombre_contacto || "") + '</div><div class="text-muted fs-8">' + escapeHtml(item.telefono_contacto || "") + '</div></div>',
      '<div class="col-lg-4"><div class="fw-bold">Total</div><div>$' + money(item.total_estimado) + ' ' + escapeHtml(item.moneda || "MXN") + '</div></div>',
      '</div>',
      '<div class="separator my-4"></div>',
      '<div class="fw-bold mb-2">Detalle snapshot</div>',
      '<pre class="bg-light p-4 rounded ecom-code">' + escapeHtml(JSON.stringify(detalle, null, 2)) + '</pre>',
      '<div class="fw-bold mb-2">Eventos</div>',
      '<pre class="bg-light p-4 rounded ecom-code">' + escapeHtml(JSON.stringify(eventos, null, 2)) + '</pre>'
    ].join("");
  }

  function renderPlanAccion(response) {
    var contenedor = document.getElementById("ecom_cot_detalle");
    if (!contenedor) return;
    contenedor.innerHTML = [
      '<div class="fw-bold mb-2">Plan de accion read-only</div>',
      '<div class="alert alert-warning py-3">Este plan no cambia estatus, no crea pedido, no crea venta y no descuenta inventario.</div>',
      '<pre class="bg-light p-4 rounded ecom-code">' + escapeHtml(JSON.stringify(get(response, ["depurar"], {}), null, 2)) + '</pre>'
    ].join("");
  }

  function renderError(message) {
    var tbody = document.getElementById("ecom_cot_body");
    if (tbody) tbody.innerHTML = '<tr><td colspan="7" class="text-danger py-5">' + escapeHtml(message) + '</td></tr>';
  }

  function renderDetalleError(message) {
    var contenedor = document.getElementById("ecom_cot_detalle");
    if (contenedor) contenedor.innerHTML = '<div class="text-danger py-4">' + escapeHtml(message) + '</div>';
  }

  function setEstado(texto, clase) {
    var node = document.getElementById("ecom_cot_estado");
    if (!node) return;
    node.className = "badge " + clase;
    node.textContent = texto;
  }

  function badge(estatus) {
    var texto = estatus || "sin_estatus";
    var clase = texto === "recibida_whatsapp" ? "badge-light-primary" : texto.indexOf("convertida") === 0 ? "badge-light-success" : texto === "descartada" ? "badge-light-danger" : "badge-light-warning";
    return '<span class="badge ' + clase + '">' + escapeHtml(texto) + '</span>';
  }

  function jsonResponse(response) {
    return response.json();
  }

  function valor(id) {
    var el = document.getElementById(id);
    return el ? String(el.value || "").trim() : "";
  }

  function setText(id, value) {
    var el = document.getElementById(id);
    if (el) el.textContent = String(value);
  }

  function get(obj, path, fallback) {
    var current = obj;
    for (var i = 0; i < path.length; i++) {
      if (!current || typeof current !== "object" || !(path[i] in current)) return fallback;
      current = current[path[i]];
    }
    return current;
  }

  function money(value) {
    return Number(value || 0).toLocaleString("es-MX", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  function debounce(fn, wait) {
    var timer = null;
    return function () {
      clearTimeout(timer);
      timer = setTimeout(fn, wait);
    };
  }

  function escapeHtml(value) {
    return String(value == null ? "" : value).replace(/[&<>"']/g, function (char) {
      return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#039;" }[char];
    });
  }
})();
