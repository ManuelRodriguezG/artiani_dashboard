"use strict";

/*
 * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-17
 * Proposito: UI read-only para verificar reglas SEO y sitemap contra frontend local.
 * Impacto: ayuda a validar Artiani v2 antes de produccion sin modificar BD.
 * Contrato: consume /ecommercePublico/seo_verificacion_erp por GET; no guarda cambios.
 */
(function () {
  document.addEventListener("DOMContentLoaded", function () {
    var ejecutar = document.getElementById("seo_check_ejecutar");
    if (ejecutar) ejecutar.addEventListener("click", cargarVerificacion);
    cargarVerificacion();
  });

  function cargarVerificacion() {
    setEstado("Verificando", "badge-light-warning");
    setHtml("seo_check_mensaje", '<div class="alert alert-info py-3">Consultando ERP y probando frontend local...</div>');
    var params = new URLSearchParams();
    params.set("frontend", valor("seo_check_frontend") || "http://artiani.com.local");
    params.set("limite_reglas", valor("seo_check_limite_reglas") || "120");
    params.set("limite_sitemap", valor("seo_check_limite_sitemap") || "120");
    params.set("probar_http", valor("seo_check_probar_http") || "1");
    fetch("/ecommercePublico/seo_verificacion_erp?" + params.toString(), { headers: { Accept: "application/json" } })
      .then(jsonResponse)
      .then(function (response) {
        renderVerificacion(response);
        setEstado(response.error ? "Error" : "Listo", response.error ? "badge-light-danger" : "badge-light-success");
      })
      .catch(function (error) {
        setEstado("Error", "badge-light-danger");
        setHtml("seo_check_mensaje", '<div class="alert alert-danger py-3">' + escapeHtml(error.message || "No se pudo verificar.") + "</div>");
      });
  }

  function renderVerificacion(response) {
    var depurar = get(response, ["depurar"], {});
    var resumen = get(depurar, ["resumen"], {});
    var reglas = get(depurar, ["reglas"], []);
    var sitemap = get(depurar, ["sitemap"], []);
    var revisar = Number(resumen.reglas_revisar || 0) + Number(resumen.sitemap_revisar || 0);
    setText("seo_check_kpi_reglas", resumen.reglas_total || 0);
    setText("seo_check_kpi_reglas_ok", resumen.reglas_ok || 0);
    setText("seo_check_kpi_sitemap", resumen.sitemap_total || 0);
    setText("seo_check_kpi_revisar", revisar);
    setHtml("seo_check_mensaje", mensajeResumen(depurar, revisar));
    renderReglas(reglas);
    renderSitemap(sitemap);
    renderExplicacion(get(depurar, ["explicacion_sitemap"], {}));
  }

  function mensajeResumen(depurar, revisar) {
    var frontend = depurar.frontend_base || "http://artiani.com.local";
    if (revisar > 0) {
      return '<div class="alert alert-warning py-3">Hay ' + escapeHtml(revisar) + ' resultado(s) para revisar en ' + escapeHtml(frontend) + '.</div>';
    }
    return '<div class="alert alert-success py-3">La muestra verificada contra ' + escapeHtml(frontend) + ' no tiene observaciones.</div>';
  }

  function renderReglas(items) {
    var tbody = document.getElementById("seo_check_reglas_body");
    if (!tbody) return;
    if (!Array.isArray(items) || !items.length) {
      tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-6">No hay reglas 301/410 activas en la muestra.</td></tr>';
      return;
    }
    tbody.innerHTML = items.map(function (item) {
      var esperado = Number(item.status_esperado) === 410
        ? "410 Gone sin destino"
        : String(item.status_esperado || "301") + " -> " + linkLocal(item.destino_local || item.to || "");
      var destinoStatus = item.destino_status_http ? '<div class="text-muted fs-8 mt-1">Destino responde: ' + escapeHtml(item.destino_status_http) + "</div>" : "";
      var respuesta = String(item.status_http || "-");
      if (item.location) respuesta += " | Location: " + item.location;
      return [
        "<tr>",
        '<td><span class="badge ' + (item.tipo_regla === "gone" ? "badge-light-danger" : "badge-light-primary") + '">' + escapeHtml(item.tipo_regla || "regla") + '</span><div class="fw-semibold seo-check-path mt-1">' + escapeHtml(item.from || "-") + '</div><div class="text-muted fs-8">' + escapeHtml(item.motivo || "") + "</div></td>",
        '<td class="seo-check-path">' + linkLocal(item.url_local) + '<div class="text-muted fs-8">Origen viejo en frontend local</div></td>',
        '<td class="seo-check-path">' + esperado + destinoStatus + "</td>",
        '<td class="seo-check-path">' + escapeHtml(respuesta) + "</td>",
        '<td>' + badgeResultado(item.resultado) + "</td>",
        "</tr>"
      ].join("");
    }).join("");
  }

  function renderSitemap(items) {
    var tbody = document.getElementById("seo_check_sitemap_body");
    if (!tbody) return;
    if (!Array.isArray(items) || !items.length) {
      tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-6">No hay URLs de sitemap en la muestra.</td></tr>';
      return;
    }
    tbody.innerHTML = items.map(function (item) {
      return [
        "<tr>",
        '<td class="seo-check-path">' + escapeHtml(item.loc || "-") + '<div class="text-muted fs-8">' + escapeHtml(item.path || "") + "</div></td>",
        '<td class="seo-check-path">' + linkLocal(item.url_local) + "</td>",
        '<td>' + escapeHtml(item.changefreq || "-") + '<div class="text-muted fs-8">Prioridad ' + escapeHtml(item.priority || "-") + "</div></td>",
        '<td>' + escapeHtml(item.status_http || "-") + "</td>",
        '<td>' + badgeResultado(item.resultado) + "</td>",
        "</tr>"
      ].join("");
    }).join("");
  }

  function renderExplicacion(info) {
    var node = document.getElementById("seo_check_explicacion");
    if (!node) return;
    var incluye = Array.isArray(info.incluye) ? info.incluye : [];
    var excluye = Array.isArray(info.excluye) ? info.excluye : [];
    node.innerHTML = [
      '<div class="mb-4"><div class="text-muted fs-8">Fuente</div><div class="fw-semibold">' + escapeHtml(info.fuente || "/ecommercePublico/seo_sitemap") + "</div></div>",
      '<div class="fw-bold mb-2">Incluye</div>',
      listaSimple(incluye, "badge-light-success"),
      '<div class="fw-bold mt-5 mb-2">Excluye</div>',
      listaSimple(excluye, "badge-light-danger")
    ].join("");
  }

  function listaSimple(items, clase) {
    if (!items.length) return '<div class="text-muted">Sin datos.</div>';
    return items.map(function (item) {
      return '<div class="d-flex align-items-start gap-2 py-1"><span class="badge ' + clase + ' mt-1">&nbsp;</span><span>' + escapeHtml(item) + "</span></div>";
    }).join("");
  }

  function badgeResultado(resultado) {
    if (resultado === "ok") return '<span class="badge badge-light-success">OK</span>';
    if (resultado === "sin_prueba_http") return '<span class="badge badge-light-info">Solo listado</span>';
    return '<span class="badge badge-light-warning">Revisar</span>';
  }

  function linkLocal(url) {
    if (!url) return "-";
    return '<a href="' + escapeAttr(url) + '" target="_blank" rel="noopener" class="seo-check-path">' + escapeHtml(url) + "</a>";
  }

  function jsonResponse(response) {
    return response.text().then(function (text) {
      try {
        return JSON.parse(text);
      } catch (e) {
        throw new Error(text || "Respuesta no valida");
      }
    });
  }

  function setEstado(texto, clase) {
    var node = document.getElementById("seo_check_estado");
    if (!node) return;
    node.className = "badge " + (clase || "badge-light-primary");
    node.textContent = texto;
  }

  function setText(id, value) {
    var node = document.getElementById(id);
    if (node) node.textContent = value;
  }

  function setHtml(id, html) {
    var node = document.getElementById(id);
    if (node) node.innerHTML = html;
  }

  function valor(id) {
    var node = document.getElementById(id);
    return node ? String(node.value || "").trim() : "";
  }

  function get(obj, path, fallback) {
    var cur = obj;
    for (var i = 0; i < path.length; i++) {
      if (cur == null || typeof cur !== "object" || !(path[i] in cur)) return fallback;
      cur = cur[path[i]];
    }
    return cur == null ? fallback : cur;
  }

  function escapeHtml(value) {
    return String(value == null ? "" : value).replace(/[&<>"']/g, function (char) {
      return ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#039;" })[char];
    });
  }

  function escapeAttr(value) {
    return escapeHtml(value).replace(/`/g, "&#096;");
  }
})();
