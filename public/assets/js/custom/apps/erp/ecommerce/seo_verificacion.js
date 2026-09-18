"use strict";

/*
 * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-18
 * Proposito: UI para verificar reglas SEO y sitemap contra frontend local con marcas persistidas en BD.
 * Impacto: ayuda a validar Artiani v2 antes de produccion y conserva seguimiento compartido.
 * Contrato: consulta /seo_verificacion_erp y guarda marcas en /seo_verificacion_guardar_erp; no modifica reglas SEO.
 */
(function () {
  var ultimoEstado = { reglas: [], sitemap: [] };
  var marcas = {};
  var itemsPorClave = {};
  var persistenciaDisponible = false;
  var mensajePersistencia = "";

  document.addEventListener("DOMContentLoaded", function () {
    var ejecutar = document.getElementById("seo_check_ejecutar");
    var filtro = document.getElementById("seo_check_filtro_revision");
    var limpiar = document.getElementById("seo_check_limpiar_marcas");
    var marcarVisibles = document.getElementById("seo_check_marcar_visibles");
    if (ejecutar) ejecutar.addEventListener("click", cargarVerificacion);
    if (filtro) filtro.addEventListener("change", renderUltimoEstado);
    if (limpiar) limpiar.addEventListener("click", limpiarMarcas);
    if (marcarVisibles) marcarVisibles.addEventListener("click", marcarVisiblesComoProbadas);
    document.addEventListener("change", function (event) {
      var check = event.target.closest("[data-seo-check-marca]");
      if (!check) return;
      guardarMarca(check.getAttribute("data-seo-check-marca"), check.checked, check);
    });
    cargarVerificacion();
  });

  function cargarVerificacion() {
    setEstado("Verificando", "badge-light-warning");
    setHtml("seo_check_mensaje", '<div class="alert alert-info py-3">Consultando ERP, marcas guardadas y frontend local...</div>');
    var params = new URLSearchParams();
    params.set("frontend", valor("seo_check_frontend") || "http://artiani.com.local");
    params.set("limite_reglas", valor("seo_check_limite_reglas") || "120");
    params.set("limite_sitemap", valor("seo_check_limite_sitemap") || "120");
    params.set("probar_http", valor("seo_check_probar_http") || "1");
    Promise.all([
      fetch("/ecommercePublico/seo_verificacion_erp?" + params.toString(), { headers: { Accept: "application/json" } }).then(jsonResponse),
      fetch("/ecommercePublico/seo_verificaciones_persistidas_erp", { headers: { Accept: "application/json" } }).then(jsonResponse)
    ])
      .then(function (responses) {
        aplicarPersistencia(responses[1]);
        renderVerificacion(responses[0]);
        setEstado(responses[0].error ? "Error" : "Listo", responses[0].error ? "badge-light-danger" : "badge-light-success");
      })
      .catch(function (error) {
        setEstado("Error", "badge-light-danger");
        setHtml("seo_check_mensaje", '<div class="alert alert-danger py-3">' + escapeHtml(error.message || "No se pudo verificar.") + "</div>");
      });
  }

  function aplicarPersistencia(response) {
    var depurar = get(response, ["depurar"], {});
    persistenciaDisponible = get(depurar, ["tabla_disponible"], false) === true;
    marcas = get(depurar, ["items"], {}) || {};
    mensajePersistencia = response && response.mensaje ? response.mensaje : "";
  }

  function renderVerificacion(response) {
    var depurar = get(response, ["depurar"], {});
    var resumen = get(depurar, ["resumen"], {});
    var reglas = get(depurar, ["reglas"], []);
    var sitemap = get(depurar, ["sitemap"], []);
    ultimoEstado = { reglas: Array.isArray(reglas) ? reglas : [], sitemap: Array.isArray(sitemap) ? sitemap : [] };
    reconstruirItemsPorClave();
    var revisar = Number(resumen.reglas_revisar || 0) + Number(resumen.sitemap_revisar || 0);
    setText("seo_check_kpi_reglas", resumen.reglas_total || 0);
    setText("seo_check_kpi_reglas_ok", resumen.reglas_ok || 0);
    setText("seo_check_kpi_sitemap", resumen.sitemap_total || 0);
    setText("seo_check_kpi_revisar", revisar);
    setHtml("seo_check_mensaje", mensajeResumen(depurar, revisar));
    renderReglas(reglas);
    renderSitemap(sitemap);
    renderExplicacion(get(depurar, ["explicacion_sitemap"], {}));
    actualizarKpiProbadas();
    aplicarFiltroRevision();
  }

  function renderUltimoEstado() {
    reconstruirItemsPorClave();
    renderReglas(ultimoEstado.reglas);
    renderSitemap(ultimoEstado.sitemap);
    actualizarKpiProbadas();
    aplicarFiltroRevision();
  }

  function mensajeResumen(depurar, revisar) {
    var frontend = depurar.frontend_base || "http://artiani.com.local";
    var partes = [];
    if (!persistenciaDisponible) {
      partes.push('<div class="alert alert-warning py-3 mb-3">Las pruebas se pueden consultar, pero aun falta crear la tabla <code>erp_ecommerce_seo_verificaciones</code> para guardar las marcas en BD.</div>');
    } else if (mensajePersistencia) {
      partes.push('<div class="alert alert-light-success py-3 mb-3">Las marcas de probada se guardan en base de datos.</div>');
    }
    if (revisar > 0) {
      partes.push('<div class="alert alert-warning py-3">Hay ' + escapeHtml(revisar) + ' resultado(s) para revisar en ' + escapeHtml(frontend) + '.</div>');
    } else {
      partes.push('<div class="alert alert-success py-3">La muestra verificada contra ' + escapeHtml(frontend) + ' no tiene observaciones.</div>');
    }
    return partes.join("");
  }

  function renderReglas(items) {
    var tbody = document.getElementById("seo_check_reglas_body");
    if (!tbody) return;
    if (!Array.isArray(items) || !items.length) {
      tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-6">No hay reglas 301/410 activas en la muestra.</td></tr>';
      return;
    }
    tbody.innerHTML = items.map(function (item) {
      var marcaId = claveRegla(item);
      var probada = marcaProbada(marcaId);
      var esperado = Number(item.status_esperado) === 410
        ? "410 Gone sin destino"
        : String(item.status_esperado || "301") + " -> " + linkLocal(item.destino_local || item.to || "");
      var destinoStatus = item.destino_status_http ? '<div class="text-muted fs-8 mt-1">Destino responde: ' + escapeHtml(item.destino_status_http) + "</div>" : "";
      var respuesta = String(item.status_http || "-");
      if (item.location) respuesta += " | Location: " + item.location;
      return [
        '<tr data-seo-check-row="' + escapeAttr(marcaId) + '" data-seo-check-probada="' + (probada ? "1" : "0") + '">',
        '<td>' + checkboxMarca(marcaId, probada) + "</td>",
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
      tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-6">No hay URLs de sitemap en la muestra.</td></tr>';
      return;
    }
    tbody.innerHTML = items.map(function (item) {
      var marcaId = claveSitemap(item);
      var probada = marcaProbada(marcaId);
      return [
        '<tr data-seo-check-row="' + escapeAttr(marcaId) + '" data-seo-check-probada="' + (probada ? "1" : "0") + '">',
        '<td>' + checkboxMarca(marcaId, probada) + "</td>",
        '<td class="seo-check-path">' + escapeHtml(item.loc || "-") + '<div class="text-muted fs-8">' + escapeHtml(item.path || "") + "</div></td>",
        '<td class="seo-check-path">' + linkLocal(item.url_local) + "</td>",
        '<td>' + escapeHtml(item.changefreq || "-") + '<div class="text-muted fs-8">Prioridad ' + escapeHtml(item.priority || "-") + "</div></td>",
        '<td>' + escapeHtml(item.status_http || "-") + "</td>",
        '<td>' + badgeResultado(item.resultado) + "</td>",
        "</tr>"
      ].join("");
    }).join("");
  }

  function aplicarFiltroRevision() {
    var filtro = valor("seo_check_filtro_revision") || "todas";
    var rows = document.querySelectorAll("[data-seo-check-row]");
    rows.forEach(function (row) {
      var probada = row.getAttribute("data-seo-check-probada") === "1";
      var visible = filtro === "todas" || (filtro === "probadas" && probada) || (filtro === "pendientes" && !probada);
      row.style.display = visible ? "" : "none";
    });
  }

  function actualizarKpiProbadas() {
    var total = 0;
    ultimoEstado.reglas.forEach(function (item) { if (marcaProbada(claveRegla(item))) total++; });
    ultimoEstado.sitemap.forEach(function (item) { if (marcaProbada(claveSitemap(item))) total++; });
    setText("seo_check_kpi_probadas", total);
  }

  function marcarVisiblesComoProbadas() {
    if (!persistenciaDisponible) {
      alert("Primero hay que crear la tabla erp_ecommerce_seo_verificaciones para guardar en base de datos.");
      return;
    }
    var claves = [];
    document.querySelectorAll("[data-seo-check-row]").forEach(function (row) {
      if (row.style.display === "none") return;
      var id = row.getAttribute("data-seo-check-row");
      if (id) claves.push(id);
    });
    guardarVarias(claves, true);
  }

  function limpiarMarcas() {
    if (!persistenciaDisponible) {
      alert("No hay tabla de base de datos disponible para limpiar marcas.");
      return;
    }
    if (!window.confirm("Quitar la marca de probada de las URLs cargadas en esta pantalla?")) return;
    guardarVarias(Object.keys(itemsPorClave), false);
  }

  function guardarVarias(claves, probada) {
    if (!claves.length) return;
    setEstado("Guardando", "badge-light-warning");
    var cadena = Promise.resolve();
    claves.forEach(function (clave) {
      cadena = cadena.then(function () { return guardarMarcaRemota(clave, probada); });
    });
    cadena
      .then(function () {
        claves.forEach(function (clave) {
          marcas[clave] = Object.assign({}, marcas[clave] || {}, { probada: probada });
        });
        renderUltimoEstado();
        setEstado("Listo", "badge-light-success");
      })
      .catch(function (error) {
        setEstado("Error", "badge-light-danger");
        setHtml("seo_check_mensaje", '<div class="alert alert-danger py-3">' + escapeHtml(error.message || "No se pudieron guardar las marcas.") + "</div>");
      });
  }

  function checkboxMarca(id, probada) {
    var disabled = persistenciaDisponible ? "" : " disabled";
    var title = persistenciaDisponible ? "" : ' title="Falta crear la tabla de verificacion SEO"';
    return '<label class="form-check form-check-custom form-check-solid"' + title + '><input class="form-check-input" type="checkbox" data-seo-check-marca="' + escapeAttr(id) + '"' + (probada ? " checked" : "") + disabled + '><span class="form-check-label fs-8">' + (probada ? "Probada" : "Pendiente") + "</span></label>";
  }

  function guardarMarca(id, probada, check) {
    if (!id) return;
    if (!persistenciaDisponible) {
      if (check) check.checked = !probada;
      alert("Primero hay que crear la tabla erp_ecommerce_seo_verificaciones para guardar en base de datos.");
      return;
    }
    setCheckGuardando(check, true);
    guardarMarcaRemota(id, probada)
      .then(function () {
        marcas[id] = Object.assign({}, marcas[id] || {}, { probada: probada });
        actualizarFilaMarca(id, probada);
        actualizarKpiProbadas();
        aplicarFiltroRevision();
      })
      .catch(function (error) {
        if (check) check.checked = !probada;
        setHtml("seo_check_mensaje", '<div class="alert alert-danger py-3">' + escapeHtml(error.message || "No se pudo guardar la marca.") + "</div>");
      })
      .finally(function () {
        setCheckGuardando(check, false);
      });
  }

  function guardarMarcaRemota(id, probada) {
    var item = itemsPorClave[id];
    if (!item) return Promise.reject(new Error("No se encontro la URL en la muestra cargada."));
    var payload = Object.assign({}, item, { probada: probada ? 1 : 0 });
    return postJson("/ecommercePublico/seo_verificacion_guardar_erp", payload).then(function (response) {
      if (response.error) throw new Error(response.mensaje || "No se pudo guardar.");
      return response;
    });
  }

  function actualizarFilaMarca(id, probada) {
    var row = filaPorClave(id);
    if (row) row.setAttribute("data-seo-check-probada", probada ? "1" : "0");
    var check = row ? row.querySelector("[data-seo-check-marca]") : null;
    if (check) check.checked = probada;
    var label = check && check.parentElement ? check.parentElement.querySelector(".form-check-label") : null;
    if (label) label.textContent = probada ? "Probada" : "Pendiente";
  }

  function filaPorClave(id) {
    var rows = document.querySelectorAll("[data-seo-check-row]");
    for (var i = 0; i < rows.length; i++) {
      if (rows[i].getAttribute("data-seo-check-row") === id) return rows[i];
    }
    return null;
  }

  function setCheckGuardando(check, guardando) {
    if (!check) return;
    check.disabled = guardando;
  }

  function reconstruirItemsPorClave() {
    itemsPorClave = {};
    ultimoEstado.reglas.forEach(function (item) {
      var clave = claveRegla(item);
      itemsPorClave[clave] = {
        clave: clave,
        tipo: "regla",
        path: item.from || "",
        url_origen: item.url_local || "",
        url_destino: item.destino_local || item.to || "",
        status_esperado: item.status_esperado || null,
        resultado_http: item.resultado || "",
        status_http: item.status_http || null,
        destino_status_http: item.destino_status_http || null
      };
    });
    ultimoEstado.sitemap.forEach(function (item) {
      var clave = claveSitemap(item);
      itemsPorClave[clave] = {
        clave: clave,
        tipo: "sitemap",
        path: item.path || item.loc || "",
        url_origen: item.url_local || item.loc || "",
        url_destino: "",
        status_esperado: 200,
        resultado_http: item.resultado || "",
        status_http: item.status_http || null,
        destino_status_http: null
      };
    });
  }

  function marcaProbada(clave) {
    return !!(marcas && marcas[clave] && marcas[clave].probada);
  }

  function claveRegla(item) {
    return "regla|" + String(item.from || "") + "|" + String(item.status_esperado || "") + "|" + String(item.to || "");
  }

  function claveSitemap(item) {
    return "sitemap|" + String(item.path || item.loc || "");
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

  function postJson(url, data) {
    data = data || {};
    data._csrf = window.ERP_CSRF_TOKEN || "";
    return fetch(url, {
      method: "POST",
      headers: { Accept: "application/json", "Content-Type": "application/json", "X-CSRF-Token": window.ERP_CSRF_TOKEN || "" },
      body: JSON.stringify(data)
    }).then(jsonResponse);
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
