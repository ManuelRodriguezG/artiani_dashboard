/*
 * Documentacion IA: Codex GPT-5, 2026-09-09.
 * Proposito: UI operativa para publicar configuracion de busqueda inteligente ecommerce.
 * Impacto: CMS/API publica; valida JSON, prueba endpoints publicos y guarda en configuracion ecommerce.
 * Contrato: no edita catalogo, precios ni inventario; POST protegido por CSRF.
 */
(function () {
  "use strict";

  var editor = document.getElementById("cms_busqueda_json");
  var fuente = document.getElementById("cms_busqueda_fuente");
  var stats = document.getElementById("cms_busqueda_stats");
  var salida = document.getElementById("cms_busqueda_resultado");
  var defaultsActuales = null;

  function setFuente(texto, tipo) {
    if (!fuente) return;
    fuente.textContent = texto || "Sin datos";
    fuente.className = "badge badge-light-" + (tipo || "primary");
  }

  function pretty(obj) {
    return JSON.stringify(obj || {}, null, 2);
  }

  function leerJsonEditor() {
    return JSON.parse(editor.value || "{}");
  }

  function renderStats(config, manifest) {
    if (!stats) return;
    config = config || {};
    var cards = [
      ["Sinonimos", Object.keys(config.sinonimos || {}).length],
      ["Stopwords", (config.stopwords || []).length],
      ["Prioridad", (config.prioridad_terminos || []).length],
      ["Reglas", (config.categorias_probables || []).length]
    ];
    stats.innerHTML = cards.map(function (card) {
      return '<div class="col-6"><div class="cms-search-stat"><div class="text-muted fs-8">' +
        escapeHtml(card[0]) + '</div><div class="fs-2 fw-bold">' + Number(card[1] || 0) + '</div></div></div>';
    }).join("") + '<div class="col-12"><div class="text-muted fs-8">Endpoint</div><code>' +
      escapeHtml((manifest && manifest.cms && manifest.cms.clave_configuracion) || "busqueda_inteligente_config") +
      '</code></div>';
  }

  function cargarManifest() {
    setFuente("Cargando", "primary");
    fetch("/cms/frontend_busqueda_manifest_erp", { credentials: "same-origin" })
      .then(function (r) { return r.json(); })
      .then(function (json) {
        var depurar = json && json.depurar ? json.depurar : {};
        var config = depurar.configuracion || {};
        defaultsActuales = config;
        editor.value = pretty(config);
        setFuente(depurar.fuente || "defaults_codigo", depurar.fuente === "bd_configuracion" ? "success" : "warning");
        renderStats(config, depurar);
      })
      .catch(function (err) {
        setFuente("Error", "danger");
        salida.textContent = String(err && err.message ? err.message : err);
      });
  }

  function publicar() {
    var config;
    try {
      config = leerJsonEditor();
      editor.value = pretty(config);
    } catch (err) {
      salida.textContent = "JSON invalido: " + err.message;
      return;
    }
    var form = new FormData();
    form.append("_csrf", window.ERP_CSRF_TOKEN || "");
    form.append("config_json", JSON.stringify(config));
    setFuente("Publicando", "primary");
    fetch("/cms/frontend_busqueda_publicar_erp", {
      method: "POST",
      credentials: "same-origin",
      headers: { "X-CSRF-Token": window.ERP_CSRF_TOKEN || "" },
      body: form
    })
      .then(function (r) { return r.json(); })
      .then(function (json) {
        salida.textContent = pretty(json);
        if (json && json.error === false) {
          setFuente("bd_configuracion", "success");
          renderStats(config, { cms: { clave_configuracion: "busqueda_inteligente_config" } });
        } else {
          setFuente("No publicado", "warning");
        }
      })
      .catch(function (err) {
        setFuente("Error", "danger");
        salida.textContent = String(err && err.message ? err.message : err);
      });
  }

  function probar() {
    var q = document.getElementById("cms_busqueda_q");
    var termino = q ? q.value : "";
    salida.textContent = "Consultando...";
    fetch("/ecommercePublico/busqueda?q=" + encodeURIComponent(termino) + "&limite=5", { credentials: "same-origin" })
      .then(function (r) { return r.json(); })
      .then(function (json) { salida.textContent = pretty(json); })
      .catch(function (err) { salida.textContent = String(err && err.message ? err.message : err); });
  }

  function escapeHtml(valor) {
    return String(valor === null || valor === undefined ? "" : valor)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }

  document.addEventListener("DOMContentLoaded", function () {
    if (!editor) return;
    cargarManifest();
    document.getElementById("cms_busqueda_publicar").addEventListener("click", publicar);
    document.getElementById("cms_busqueda_recargar").addEventListener("click", cargarManifest);
    document.getElementById("cms_busqueda_probar").addEventListener("click", probar);
    document.getElementById("cms_busqueda_formatear").addEventListener("click", function () {
      try {
        editor.value = pretty(leerJsonEditor());
      } catch (err) {
        salida.textContent = "JSON invalido: " + err.message;
      }
    });
    document.getElementById("cms_busqueda_restaurar").addEventListener("click", function () {
      editor.value = pretty(defaultsActuales || {});
      renderStats(defaultsActuales || {}, { cms: { clave_configuracion: "busqueda_inteligente_config" } });
    });
  });
})();
