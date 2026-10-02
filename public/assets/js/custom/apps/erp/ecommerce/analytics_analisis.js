/*
 * Documentacion IA: Codex GPT-5, 2026-09-29.
 * Proposito: UX read-only para explorador por secciones de Ecommerce Analytics.
 * Impacto: permite revisar tablas detalladas sin exponer PII ni ejecutar escrituras.
 * Contrato: consume GET protegido /ecommercePublico/analytics_analisis_erp.
 */
(function () {
  "use strict";

  var estado = { seccion: "sesiones", timer: null };
  var titulos = {
    sesiones: ["Sesiones", "Session hash anonimo, rutas y actividad."],
    page_views: ["Page views", "URLs visitadas agrupadas por ruta."],
    productos: ["Productos vistos", "Productos con vistas por slug, publicacion y SKU."],
    busquedas: ["Busquedas", "Terminos buscados y demanda sin resultados."],
    whatsapp: ["WhatsApp", "Aperturas de WhatsApp registradas desde el ecommerce."],
    eventos: ["Eventos", "Eventos crudos recientes para auditoria de tracking."]
  };

  document.addEventListener("DOMContentLoaded", function () {
    iniciarFechas();
    iniciarSeccion();
    bindEvents();
    cargar();
  });

  function iniciarFechas() {
    var hasta = new Date();
    var desde = new Date();
    desde.setDate(hasta.getDate() - 7);
    setValue("ecom_aa_desde", isoDate(desde));
    setValue("ecom_aa_hasta", isoDate(hasta));
  }

  function iniciarSeccion() {
    var params = new URLSearchParams(window.location.search || "");
    var seccion = params.get("seccion") || "";
    if (titulos[seccion]) estado.seccion = seccion;
    activarTab();
  }

  function bindEvents() {
    on("ecom_aa_recargar", "click", cargar);
    on("ecom_aa_desde", "change", cargar);
    on("ecom_aa_hasta", "change", cargar);
    on("ecom_aa_limite", "change", cargar);
    on("ecom_aa_q", "input", function () {
      clearTimeout(estado.timer);
      estado.timer = setTimeout(cargar, 350);
    });
    var tabs = document.getElementById("ecom_aa_tabs");
    if (tabs) {
      tabs.addEventListener("click", function (event) {
        var button = event.target.closest("[data-section]");
        if (!button) return;
        estado.seccion = button.getAttribute("data-section") || "sesiones";
        activarTab();
        actualizarUrl();
        cargar();
      });
    }
  }

  function cargar() {
    setEstado("Cargando", "badge-light-warning");
    var params = new URLSearchParams();
    params.set("seccion", estado.seccion);
    params.set("desde", valor("ecom_aa_desde"));
    params.set("hasta", valor("ecom_aa_hasta"));
    params.set("limite", valor("ecom_aa_limite") || "100");
    params.set("q", valor("ecom_aa_q"));
    fetch("/ecommercePublico/analytics_analisis_erp?" + params.toString(), { headers: { "Accept": "application/json" } })
      .then(function (response) { return response.json(); })
      .then(render)
      .catch(function (error) {
        setEstado("Error", "badge-light-danger");
        renderTabla(["error"], [{ error: error.message || "No se pudo consultar analytics" }]);
      });
  }

  function render(response) {
    var depurar = get(response, ["depurar"], {});
    var seccion = get(depurar, ["seccion"], estado.seccion);
    var titulo = titulos[seccion] || titulos.sesiones;
    setText("ecom_aa_titulo", titulo[0]);
    setText("ecom_aa_subtitulo", titulo[1]);
    renderResumen(get(depurar, ["resumen"], {}));
    renderTabla(get(depurar, ["columnas"], []), get(depurar, ["items"], []));
    setText("ecom_aa_actualizado", get(depurar, ["fecha_consulta"], "-"));
    setEstado(get(depurar, ["configurado"], false) ? "Read-only" : "Sin esquema", get(depurar, ["configurado"], false) ? "badge-light-success" : "badge-light-warning");
  }

  function renderResumen(resumen) {
    var node = document.getElementById("ecom_aa_resumen");
    if (!node) return;
    var claves = Object.keys(resumen || {});
    if (claves.length === 0) {
      node.innerHTML = "";
      return;
    }
    node.innerHTML = claves.map(function (clave) {
      return '<div class="col-md-3"><div class="ecom-aa-kpi"><span class="text-muted fs-8 text-uppercase fw-bold">' + escapeHtml(label(clave)) + '</span><strong>' + escapeHtml(resumen[clave]) + '</strong></div></div>';
    }).join("");
  }

  function renderTabla(columnas, items) {
    var node = document.getElementById("ecom_aa_tabla");
    if (!node) return;
    setText("ecom_aa_total_items", (Array.isArray(items) ? items.length : 0) + " registros");
    if (!Array.isArray(columnas) || columnas.length === 0 || !Array.isArray(items) || items.length === 0) {
      node.innerHTML = '<div class="text-muted py-4">Sin datos en el rango.</div>';
      return;
    }
    node.innerHTML = '<div class="table-responsive"><table class="table table-row-dashed fs-7 gy-3 mb-0 ecom-aa-table"><thead><tr class="text-muted fw-bold">' +
      columnas.map(function (columna) { return '<th>' + escapeHtml(label(columna)) + '</th>'; }).join("") +
      '</tr></thead><tbody>' +
      items.map(function (item) {
        return '<tr>' + columnas.map(function (columna) {
          return '<td>' + celda(columna, item[columna]) + '</td>';
        }).join("") + '</tr>';
      }).join("") +
      '</tbody></table></div>';
  }

  function celda(columna, value) {
    var texto = value == null || value === "" ? "-" : String(value);
    if (columna.indexOf("ruta") >= 0 || columna === "slug") {
      return '<div class="ecom-aa-route" title="' + escapeHtml(texto) + '">' + escapeHtml(texto) + '</div>';
    }
    if (columna === "session_key") {
      return '<a class="fw-bold text-primary" href="/ecommercePublico/analytics_flujo?session_key=' + encodeURIComponent(texto) + '">' + escapeHtml(texto) + '</a>';
    }
    return escapeHtml(texto);
  }

  function activarTab() {
    document.querySelectorAll("[data-section]").forEach(function (button) {
      button.classList.toggle("is-active", button.getAttribute("data-section") === estado.seccion);
    });
  }

  function actualizarUrl() {
    var url = new URL(window.location.href);
    url.searchParams.set("seccion", estado.seccion);
    window.history.replaceState({}, "", url.toString());
  }

  function label(clave) {
    return {
      session_key: "Sesion",
      eventos_total: "Eventos",
      primer_ruta: "Primera ruta",
      ultimo_ruta: "Ultima ruta",
      dispositivo_aproximado: "Dispositivo",
      fecha_inicio: "Inicio",
      fecha_ultima_actividad: "Ultima actividad",
      total: "Total",
      sesiones: "Sesiones",
      sesiones_un_evento: "Sesiones 1 evento",
      eventos_promedio: "Eventos promedio",
      page_views: "Page views",
      urls: "URLs",
      ruta: "Ruta",
      primera_fecha: "Primera fecha",
      ultima_fecha: "Ultima fecha",
      slug: "Slug",
      id_publicacion: "Publicacion",
      id_sku: "SKU",
      vistas: "Vistas",
      productos: "Productos",
      query_normalizada: "Busqueda",
      sin_resultados: "Sin resultados",
      sin_resultados_total: "Sin resultados",
      resultados_promedio: "Resultados prom.",
      aperturas_whatsapp: "WhatsApp",
      fecha: "Fecha",
      tipo_evento: "Evento",
      tipos: "Tipos",
      eventos: "Eventos",
      canal: "Canal"
    }[clave] || clave;
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
    var node = document.getElementById("ecom_aa_estado");
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
