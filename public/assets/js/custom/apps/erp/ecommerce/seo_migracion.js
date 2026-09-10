/*
 * Documentacion IA: Codex GPT-5, 2026-09-03.
 * Proposito: UX interna para revisar y ejecutar acciones autorizadas de SEO/migracion URLs ecommerce.
 * Impacto: consulta estado, URLs, redirecciones, sitemap y permite guardar acciones SEO operativas.
 * Contrato: POST protegido con CSRF/permisos; DDL y acciones masivas conservan token.
 */
(function () {
  "use strict";

  var mostrarOcultas = false;
  var descartadasKey = "ecom_seo_urls_viejas_descartadas";
  var decisionesKey = "ecom_seo_urls_viejas_decisiones";
  var productoSeoSeleccionado = null;

  document.addEventListener("DOMContentLoaded", function () {
    bindEvents();
    cargarTokensSesion();
    inicializarMesasDiferidas();
    cargarSeo();
    cargarRevisionUrls();
  });

  function bindEvents() {
    var recargar = document.getElementById("ecom_seo_recargar");
    var limite = document.getElementById("ecom_seo_limite");
    var importarPlan = document.getElementById("ecom_seo_importar_plan");
    var importarGuardar = document.getElementById("ecom_seo_importar_guardar");
    var redireccionPlan = document.getElementById("ecom_seo_redireccion_plan");
    var redireccionGuardar = document.getElementById("ecom_seo_redireccion_guardar");
    var urlsSyncPlan = document.getElementById("ecom_seo_urls_sync_plan");
    var urlsSyncGuardar = document.getElementById("ecom_seo_urls_sync_guardar");
    var revisionRecargar = document.getElementById("ecom_seo_revision_recargar");
    var revisionOcultas = document.getElementById("ecom_seo_revision_mostrar_ocultas");
    var revisionQ = document.getElementById("ecom_seo_revision_q");
    var revisionAccion = document.getElementById("ecom_seo_revision_accion");
    var revisionPrioridad = document.getElementById("ecom_seo_revision_prioridad");
    var revisionLimite = document.getElementById("ecom_seo_revision_limite");
    var revisionFuente = document.getElementById("ecom_seo_revision_fuente");
    var productosRecargar = document.getElementById("ecom_seo_productos_recargar");
    var productosQ = document.getElementById("ecom_seo_productos_q");
    var productosEstatus = document.getElementById("ecom_seo_productos_estatus");
    var productosRelacion = document.getElementById("ecom_seo_productos_relacion");
    var productosLimite = document.getElementById("ecom_seo_productos_limite");
    var productoSlugSugerir = document.getElementById("ecom_seo_producto_slug_sugerir");
    var productoSlugPlan = document.getElementById("ecom_seo_producto_slug_plan");
    var productoSlugGuardar = document.getElementById("ecom_seo_producto_slug_guardar");
    var productoUrlViejaBuscar = document.getElementById("ecom_seo_producto_url_vieja_buscar");
    var productoUrlViejaQ = document.getElementById("ecom_seo_producto_url_vieja_q");
    var destinoBuscar = document.getElementById("ecom_seo_destino_buscar");
    var destinoQ = document.getElementById("ecom_seo_destino_q");
    if (recargar) recargar.addEventListener("click", recargarSeoCompleto);
    if (limite) limite.addEventListener("change", cargarSeo);
    if (importarPlan) importarPlan.addEventListener("click", prepararImportacion);
    if (importarGuardar) importarGuardar.addEventListener("click", guardarImportacion);
    if (redireccionPlan) redireccionPlan.addEventListener("click", prepararRedireccion);
    if (redireccionGuardar) redireccionGuardar.addEventListener("click", guardarRedireccion);
    if (urlsSyncPlan) urlsSyncPlan.addEventListener("click", prepararSyncUrls);
    if (urlsSyncGuardar) urlsSyncGuardar.addEventListener("click", guardarSyncUrls);
    if (revisionRecargar) revisionRecargar.addEventListener("click", cargarRevisionUrls);
    if (productosRecargar) productosRecargar.addEventListener("click", cargarProductosSlugs);
    if (productoSlugSugerir) productoSlugSugerir.addEventListener("click", sugerirSlugProductoSeleccionado);
    if (productoSlugPlan) productoSlugPlan.addEventListener("click", prepararProductoSlug);
    if (productoSlugGuardar) productoSlugGuardar.addEventListener("click", guardarProductoSlug);
    if (productoUrlViejaBuscar) productoUrlViejaBuscar.addEventListener("click", buscarUrlsViejasParaProducto);
    if (productoUrlViejaQ) productoUrlViejaQ.addEventListener("input", debounce(buscarUrlsViejasParaProducto, 350));
    if (productoToken) productoToken.addEventListener("input", guardarTokensSesion);
    if (redireccionToken) redireccionToken.addEventListener("input", guardarTokensSesion);
    if (destinoBuscar) destinoBuscar.addEventListener("click", buscarDestinosCanonicos);
    if (destinoQ) destinoQ.addEventListener("input", debounce(buscarDestinosCanonicos, 350));
    if (revisionOcultas) revisionOcultas.addEventListener("click", function () {
      mostrarOcultas = !mostrarOcultas;
      revisionOcultas.className = mostrarOcultas ? "btn btn-sm btn-warning" : "btn btn-sm btn-light-warning";
      revisionOcultas.innerHTML = '<i class="bi bi-eye"></i> ' + (mostrarOcultas ? "Mostrando ocultas" : "Ocultas");
      cargarRevisionUrls();
    });
    [revisionQ, revisionAccion, revisionPrioridad, revisionLimite, revisionFuente].forEach(function (node) {
      if (!node) return;
      node.addEventListener(node === revisionQ ? "input" : "change", debounce(cargarRevisionUrls, 250));
    });
    [productosQ, productosEstatus, productosRelacion, productosLimite].forEach(function (node) {
      if (!node) return;
      node.addEventListener(node === productosQ ? "input" : "change", debounce(cargarProductosSlugs, 250));
    });
    document.addEventListener("click", function (event) {
      var usar = event.target.closest("[data-seo-usar-redireccion]");
      var ocultar = event.target.closest("[data-seo-ocultar-url]");
      var editarProducto = event.target.closest("[data-seo-producto-editar]");
      var usarProductoRelacion = event.target.closest("[data-seo-producto-redireccion]");
      var usarProductoUrlVieja = event.target.closest("[data-seo-producto-url-vieja]");
      var revisarProductoSugerido = event.target.closest("[data-seo-revisar-producto-sugerido]");
      var buscarProductoDesdeUrl = event.target.closest("[data-seo-buscar-producto-url]");
      var usarDestinoCanonico = event.target.closest("[data-seo-usar-destino-canonico]");
      if (usar) {
        usarRevisionComoRedireccion(usar);
      }
      if (ocultar) {
        ocultarRevisionUrl(ocultar.getAttribute("data-seo-ocultar-url") || "");
      }
      if (editarProducto) {
        seleccionarProductoSeo(parseJsonSeguro(editarProducto.getAttribute("data-producto") || "{}"));
      }
      if (usarProductoRelacion) {
        usarProductoComoRedireccion(usarProductoRelacion);
      }
      if (usarProductoUrlVieja) {
        usarProductoComoRedireccion(usarProductoUrlVieja);
      }
      if (revisarProductoSugerido) {
        revisarProductoDesdeSugerencia(revisarProductoSugerido);
      }
      if (buscarProductoDesdeUrl) {
        buscarProductosCatalogoDesdeUrl(buscarProductoDesdeUrl);
      }
      if (usarDestinoCanonico) {
        setValue("ecom_seo_redir_to", usarDestinoCanonico.getAttribute("data-path") || "");
        setValue("ecom_seo_redir_tipo", usarDestinoCanonico.getAttribute("data-tipo") || "manual");
      }
    });
    document.addEventListener("change", function (event) {
      var decision = event.target.closest("[data-seo-decision-url]");
      if (decision) {
        guardarDecisionUrlVieja(decision.getAttribute("data-seo-decision-url") || "", decision.value || "");
      }
    });
  }

  function inicializarMesasDiferidas() {
    var productos = document.getElementById("ecom_seo_productos_body");
    if (productos) {
      productos.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-6">Busca un SKU, nombre o slug para revisar el producto nuevo.</td></tr>';
    }
    var revision = document.getElementById("ecom_seo_revision_body");
    if (revision) {
      revision.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-6">Cargando URLs indexadas de Google...</td></tr>';
    }
  }

  function cargarTokensSesion() {
    try {
    } catch (e) {}
  }

  function guardarTokensSesion() {
    try {
    } catch (e) {}
  }

  function recargarSeoCompleto() {
    cargarSeo();
    cargarRevisionUrls();
  }

  function cargarSeo() {
    setEstado("Cargando", "badge-light-warning");
    var params = new URLSearchParams();
    params.set("limite", valor("ecom_seo_limite") || "25");
    Promise.all([
      fetch("/ecommercePublico/seo_dashboard_erp?" + params.toString(), { headers: { Accept: "application/json" } }).then(jsonResponse),
      fetch("/ecommercePublico/esquema_plan_seo_migracion", { headers: { Accept: "application/json" } }).then(jsonResponse)
    ]).then(function (responses) {
      renderDashboard(responses[0]);
      renderDdl(responses[1]);
      setEstado("Read-only", "badge-light-success");
    }).catch(function (error) {
      setEstado("Error", "badge-light-danger");
      renderError(error.message || "No se pudo consultar SEO.");
    });
  }

  function cargarRevisionUrls() {
    var params = new URLSearchParams();
    params.set("limite", valor("ecom_seo_revision_limite") || "120");
    params.set("fuente", valor("ecom_seo_revision_fuente") || "indexadas");
    if (valor("ecom_seo_revision_q")) params.set("q", valor("ecom_seo_revision_q"));
    if (valor("ecom_seo_revision_accion")) params.set("accion", valor("ecom_seo_revision_accion"));
    if (valor("ecom_seo_revision_prioridad")) params.set("prioridad", valor("ecom_seo_revision_prioridad"));
    fetch("/ecommercePublico/seo_urls_viejas_revision_erp?" + params.toString(), { headers: { Accept: "application/json" } })
      .then(jsonResponse)
      .then(renderRevisionUrls)
      .catch(function (error) {
        setHtml("ecom_seo_revision_info", '<div class="alert alert-danger py-3">' + escapeHtml(error.message || "No se pudo consultar revision.") + "</div>");
      });
  }

  function cargarProductosSlugs() {
    var params = new URLSearchParams();
    params.set("limite", valor("ecom_seo_productos_limite") || "80");
    if (valor("ecom_seo_productos_q")) params.set("q", valor("ecom_seo_productos_q"));
    if (valor("ecom_seo_productos_estatus")) params.set("estatus", valor("ecom_seo_productos_estatus"));
    if (valor("ecom_seo_productos_relacion")) params.set("relacion", valor("ecom_seo_productos_relacion"));
    return fetch("/ecommercePublico/seo_productos_slugs_erp?" + params.toString(), { headers: { Accept: "application/json" } })
      .then(jsonResponse)
      .then(function (response) {
        renderProductosSlugs(response);
        return response;
      })
      .catch(function (error) {
        setHtml("ecom_seo_producto_plan", '<div class="alert alert-danger py-3">' + escapeHtml(error.message || "No se pudieron consultar productos.") + "</div>");
      });
  }

  function renderDashboard(response) {
    var depurar = get(response, ["depurar"], {});
    var estado = get(depurar, ["estado"], {});
    var resumen = get(depurar, ["resumen"], {});
    setText("ecom_seo_kpi_urls", resumen.urls_total_muestra || 0);
    setText("ecom_seo_kpi_redirecciones", estado.redirecciones_total || resumen.redirecciones_activas || 0);
    setText("ecom_seo_kpi_pendientes", estado.urls_pendientes_total || 0);
    setText("ecom_seo_kpi_sitemap", resumen.sitemap_items_muestra || 0);
    setValue("ecom_seo_dominio", estado.dominio_produccion || "");
    setBadge("ecom_seo_robots_badge", resumen.robots_disponible ? "Robots listo" : "Robots pendiente", resumen.robots_disponible ? "badge-light-success" : "badge-light-warning");
    renderPasos(get(depurar, ["siguiente_operativo"], []));
    renderUrls(get(depurar, ["urls"], []));
    renderRedirecciones(get(depurar, ["redirecciones"], []));
    renderSitemap(get(depurar, ["sitemap"], []));
    renderTablas(get(estado, ["tablas_seo"], {}));
    renderAutorizados(get(depurar, ["endpoints_autorizados"], {}));
    setText("ecom_seo_robots_txt", depurar.robots_txt || "");
  }

  function renderPasos(items) {
    var node = document.getElementById("ecom_seo_pasos");
    if (!node) return;
    if (!Array.isArray(items) || items.length === 0) {
      node.innerHTML = '<div class="text-muted">Sin pasos pendientes.</div>';
      return;
    }
    node.innerHTML = items.map(function (item, index) {
      return '<div class="ecom-seo-step"><span class="badge badge-light-primary mb-2">' + (index + 1) + '</span><div class="fw-semibold">' + escapeHtml(etiquetaPaso(item)) + '</div><div class="text-muted fs-8">' + escapeHtml(item) + '</div></div>';
    }).join("");
  }

  function renderUrls(items) {
    var tbody = document.getElementById("ecom_seo_urls_body");
    if (!tbody) return;
    if (!Array.isArray(items) || items.length === 0) {
      tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-6">Sin URLs para mostrar.</td></tr>';
      return;
    }
    tbody.innerHTML = items.map(function (item) {
      return [
        "<tr>",
        "<td>" + badgeTipo(item.tipo) + "</td>",
        '<td><div class="fw-semibold ecom-seo-path">' + escapeHtml(item.path || "-") + '</div><div class="text-muted fs-8 ecom-seo-path">' + escapeHtml(item.canonical || "") + "</div></td>",
        '<td><span class="fw-semibold">' + escapeHtml(item.title || "-") + '</span><div class="text-muted fs-8">' + escapeHtml(item.description || "") + "</div></td>",
        '<td class="text-end">' + (item.indexable ? '<span class="badge badge-light-success">Si</span>' : '<span class="badge badge-light-warning">No</span>') + "</td>",
        "</tr>"
      ].join("");
    }).join("");
  }

  function renderRedirecciones(items) {
    var tbody = document.getElementById("ecom_seo_redirecciones_body");
    if (!tbody) return;
    if (!Array.isArray(items) || items.length === 0) {
      tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted py-6">Sin redirecciones activas. Falta aplicar esquema, importar URLs viejas y aprobar equivalencias.</td></tr>';
      return;
    }
    tbody.innerHTML = items.map(function (item) {
      return [
        "<tr>",
        '<td class="fw-semibold ecom-seo-path">' + escapeHtml(item.from || "-") + "</td>",
        '<td class="ecom-seo-path">' + escapeHtml(item.to || "-") + "</td>",
        "<td>" + badgeStatus(item.status || 301) + '<div class="text-muted fs-8">' + escapeHtml(item.tipo || "") + "</div></td>",
        "</tr>"
      ].join("");
    }).join("");
  }

  function renderSitemap(items) {
    var tbody = document.getElementById("ecom_seo_sitemap_body");
    if (!tbody) return;
    if (!Array.isArray(items) || items.length === 0) {
      tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted py-6">Sin sitemap para mostrar.</td></tr>';
      return;
    }
    tbody.innerHTML = items.map(function (item) {
      return '<tr><td class="fw-semibold ecom-seo-path">' + escapeHtml(item.loc || "-") + '</td><td>' + escapeHtml(item.changefreq || "-") + '</td><td class="text-end fw-bold">' + escapeHtml(item.priority || "-") + "</td></tr>";
    }).join("");
  }

  function renderDdl(response) {
    var depurar = get(response, ["depurar"], {});
    var plan = get(depurar, ["plan"], []);
    var pendientes = Number(depurar.ddl_pendientes || 0);
    setBadge("ecom_seo_ddl_badge", pendientes > 0 ? "DDL pendiente" : "DDL revisado", pendientes > 0 ? "badge-light-warning" : "badge-light-success");
    var node = document.getElementById("ecom_seo_ddl");
    if (!node) return;
    if (!Array.isArray(plan) || plan.length === 0) {
      node.innerHTML = '<div class="text-muted">Sin DDL generado.</div>';
      return;
    }
    node.innerHTML = plan.map(function (item) {
      var sql = get(item, ["depurar", "sql"], "");
      var tabla = extraerTabla(sql);
      return '<div class="border rounded p-3 mb-3"><div class="d-flex justify-content-between gap-3"><span class="fw-semibold">' + escapeHtml(tabla || item.mensaje || "DDL") + '</span>' + badgeDdl(item) + '</div><pre class="bg-light p-3 rounded mt-3 mb-0 ecom-seo-code">' + escapeHtml(sql || JSON.stringify(item.depurar || {}, null, 2)) + "</pre></div>";
    }).join("");
  }

  function renderTablas(tablas) {
    var node = document.getElementById("ecom_seo_tablas");
    if (!node) return;
    var keys = Object.keys(tablas || {});
    if (keys.length === 0) {
      node.innerHTML = '<div class="text-muted">Sin estado de tablas.</div>';
      return;
    }
    node.innerHTML = keys.map(function (key) {
      var existe = !!tablas[key];
      return [
        '<div class="d-flex justify-content-between align-items-center border-bottom py-3">',
        '<span class="fw-semibold">' + escapeHtml(key) + "</span>",
        '<span class="badge ' + (existe ? "badge-light-success" : "badge-light-warning") + '">' + (existe ? "Existe" : "Pendiente") + "</span>",
        "</div>"
      ].join("");
    }).join("");
  }

  function renderAutorizados(endpoints) {
    var node = document.getElementById("ecom_seo_autorizados");
    if (!node) return;
    var keys = Object.keys(endpoints || {});
    if (keys.length === 0) {
      node.innerHTML = '<div class="text-muted">Sin acciones autorizadas configuradas.</div>';
      return;
    }
    node.innerHTML = keys.map(function (endpoint) {
      return [
        '<div class="border rounded p-3 mb-3">',
        '<div class="fw-semibold ecom-seo-path">' + escapeHtml(endpoint) + "</div>",
        '<div class="text-muted fs-8 mt-1">Token requerido</div>',
        '<code>' + escapeHtml(endpoints[endpoint]) + "</code>",
        "</div>"
      ].join("");
    }).join("");
  }

  function renderRevisionUrls(response) {
    var depurar = get(response, ["depurar"], {});
    var tbody = document.getElementById("ecom_seo_revision_body");
    if (!tbody) return;
    if (!depurar.disponible) {
      setHtml("ecom_seo_revision_info", '<div class="alert alert-info py-3">Aun no hay reporte enriquecido. Ejecuta el rastreo read-only y genera el reporte para alimentar esta mesa.</div>');
      tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-6">Sin reporte de URLs anteriores.</td></tr>';
      renderRevisionResumen({});
      return;
    }
    var decisiones = cargarDecisiones();
    var items = Array.isArray(depurar.items) ? depurar.items : [];
    var visibles = items;
    setHtml("ecom_seo_revision_info", [
      '<div class="d-flex flex-wrap gap-2 align-items-center">',
      '<span class="badge badge-light-primary">Reporte: ' + escapeHtml(depurar.total_reporte || 0) + '</span>',
      '<span class="badge badge-light-dark">Fuente: ' + escapeHtml(depurar.fuente || "reporte") + '</span>',
      depurar.total_nuevas ? '<span class="badge badge-light-success">Nuevas: ' + escapeHtml(depurar.total_nuevas) + '</span>' : "",
      '<span class="badge badge-light-info">Filtrado: ' + escapeHtml(depurar.total_filtrado || 0) + '</span>',
      '<span class="badge badge-light-warning">Decididas UI: ' + escapeHtml(Object.keys(decisiones).length) + '</span>',
      '<span class="text-muted fs-8 ecom-seo-path">Preview local: ' + escapeHtml(depurar.base_local_revision || "http://artiani.com.local") + '</span>',
      "</div>"
    ].join(""));
    renderRevisionResumen(depurar.resumen || {});
    if (visibles.length === 0) {
      tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-6">Sin URLs para los filtros actuales.</td></tr>';
      return;
    }
    tbody.innerHTML = visibles.map(function (item) {
      var destino = item.url_destino_sugerida || "";
      var local = item.url_destino_local || "";
      var sugerencias = Array.isArray(item.sugerencias) ? item.sugerencias : [];
      var mismaUri = normalizarPathUi(item.path_original || "") === normalizarPathUi(destino);
      var decisionActual = decisiones[item.path_original || ""] || "";
      return [
        "<tr>",
        '<td><div class="fw-semibold ecom-seo-path">' + escapeHtml(item.path_original || "-") + '</div>' + renderOrigenRevision(item) + '<div class="text-muted fs-8 ecom-seo-path">' + escapeHtml(item.titulo_detectado || "") + "</div></td>",
        '<td>' + badgeHttp(item.status_http) + "</td>",
        '<td>' + badgeTipo(item.tipo_plan || item.tipo_detectado || item.tipo_crawl || "url") + '<div class="text-muted fs-8">' + escapeHtml(item.tipo_crawl || item.origen || "") + "</div></td>",
        '<td>' + renderSugerenciasRevision(item, sugerencias, destino, local) + "</td>",
        '<td>' + badgeAccion(item.accion_sugerida) + '<div class="mt-1">' + badgeConfianza(item.confianza) + '</div><div class="text-muted fs-8">' + escapeHtml(item.nota || item.motivo || "") + "</div></td>",
        '<td class="text-end"><div class="d-flex justify-content-end gap-2 mb-2">' +
          '<button class="btn btn-sm btn-light-primary" type="button" data-seo-usar-redireccion="1" data-from="' + escapeAttr(item.path_original || "") + '" data-to="' + escapeAttr(destino) + '" data-tipo="' + escapeAttr(item.tipo_plan || item.tipo_detectado || "manual") + '"' + (!destino || mismaUri ? " disabled" : "") + '><i class="bi bi-arrow-return-right"></i></button>' +
          '<button class="btn btn-sm btn-light-info" type="button" data-seo-buscar-producto-url="1" data-q="' + escapeAttr(queryDesdePathViejo(item.path_original || "")) + '"><i class="bi bi-search"></i></button>' +
        '</div><select class="form-select form-select-sm form-select-solid" data-seo-decision-url="' + escapeAttr(item.path_original || "") + '">' +
          opcionDecision("", "Pendiente", decisionActual) +
          opcionDecision("redirigir_producto", "Redirigir a producto", decisionActual) +
          opcionDecision("mantener_agotado", "Mantener agotado", decisionActual) +
          opcionDecision("habilitar_basico_agotado", "Habilitar basico agotado", decisionActual) +
          opcionDecision("redirigir_categoria", "Redirigir categoria", decisionActual) +
          opcionDecision("410_descontinuado", "410 descontinuado", decisionActual) +
        "</select></td>",
        "</tr>"
      ].join("");
    }).join("");
  }

  function renderOrigenRevision(item) {
    var url = item.url_original || "";
    if (!url && item.path_original) {
      url = "https://artiani.com.mx" + item.path_original;
    }
    if (!url) {
      return "";
    }
    return '<a class="fs-8 ecom-seo-path d-inline-block" target="_blank" rel="noopener" href="' + escapeAttr(url) + '">' + escapeHtml(url) + "</a>";
  }

  function renderSugerenciasRevision(item, sugerencias, destino, local) {
    if (!sugerencias.length) {
      return '<div class="fw-semibold ecom-seo-path">' + escapeHtml(destino || "Sin sugerencia") + '</div>' + (local ? '<a class="fs-8" target="_blank" rel="noopener" href="' + escapeAttr(local) + '">' + escapeHtml(local) + "</a>" : '<div class="text-muted fs-8">' + escapeHtml(item.motivo || "Requiere revision") + "</div>");
    }
    return sugerencias.map(function (sug, index) {
      var path = sug.path || "";
      var preview = path ? "http://artiani.com.local" + path : "";
      return [
        '<div class="' + (index > 0 ? "border-top pt-2 mt-2" : "") + '">',
        '<button class="btn btn-sm btn-light-primary me-2" type="button" data-seo-usar-redireccion="1" data-from="' + escapeAttr(item.path_original || "") + '" data-to="' + escapeAttr(path) + '" data-tipo="' + escapeAttr(sug.tipo || item.tipo_plan || "manual") + '"><i class="bi bi-arrow-return-right"></i></button>',
        '<button class="btn btn-sm btn-light-info me-2" type="button" data-seo-revisar-producto-sugerido="1" data-q="' + escapeAttr(sug.sku || sug.slug || sug.title || path) + '"><i class="bi bi-pencil-square"></i></button>',
        '<span class="fw-semibold ecom-seo-path">' + escapeHtml(path || "-") + "</span>",
        '<div class="text-muted fs-8 ecom-seo-path">' + escapeHtml(sug.title || sug.motivo || "") + "</div>",
        '<div class="d-flex flex-wrap gap-2 mt-1">' + badgeConfianza(sug.confianza) + '<span class="badge badge-light">score ' + escapeHtml(sug.score || 0) + '</span><span class="badge badge-light">' + escapeHtml(sug.sku || "sin sku") + '</span><span class="badge badge-light">' + escapeHtml(sug.estatus_publicacion || sug.fuente_comparacion || "") + '</span><span class="badge badge-light">' + escapeHtml(sug.motivo || "") + "</span></div>",
        preview ? '<a class="fs-8" target="_blank" rel="noopener" href="' + escapeAttr(preview) + '">' + escapeHtml(preview) + "</a>" : "",
        "</div>"
      ].join("");
    }).join("");
  }

  function renderRevisionResumen(resumen) {
    var node = document.getElementById("ecom_seo_revision_resumen");
    if (!node) return;
    var acciones = resumen.accion || {};
    node.innerHTML = [
      resumenCaja("Sin 301", acciones.sin_redireccion_necesaria || 0),
      resumenCaja("Aprobar", acciones.aprobar_301_candidato || 0),
      resumenCaja("Validar", acciones.validar_301_candidato || 0),
      resumenCaja("Manual", acciones.revisar_manual || 0),
      resumenCaja("Excluir/410", acciones.excluir_o_410 || 0)
    ].join("");
  }

  function renderProductosSlugs(response) {
    var depurar = get(response, ["depurar"], {});
    var tbody = document.getElementById("ecom_seo_productos_body");
    if (!tbody) return;
    var items = Array.isArray(depurar.items) ? depurar.items : [];
    if (!items.length) {
      tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-6">Sin productos para los filtros actuales.</td></tr>';
      return;
    }
    tbody.innerHTML = items.map(function (item) {
      var sugerencias = Array.isArray(item.urls_viejas_sugeridas) ? item.urls_viejas_sugeridas : [];
      return [
        "<tr>",
        '<td><div class="fw-bold">' + escapeHtml(item.sku || "-") + '</div>' + badgeProductoEstatus(item.estatus_publicacion) + '<div class="text-muted fs-8">' + escapeHtml(item.marca || "") + "</div></td>",
        '<td><div class="fw-semibold">' + escapeHtml(item.titulo_publico || "-") + '</div><div class="text-muted fs-8">' + escapeHtml(item.nombre_sku || item.nombre_producto || "") + '</div><div class="text-muted fs-8">' + escapeHtml(item.categoria || "") + "</div></td>",
        '<td><div class="fw-semibold ecom-seo-path">' + escapeHtml(item.slug || "-") + '</div><a class="fs-8 ecom-seo-path d-block" target="_blank" rel="noopener" href="' + escapeAttr(item.url_local || "#") + '">' + escapeHtml(item.url_local || "") + '</a><a class="fs-8 ecom-seo-path d-block" target="_blank" rel="noopener" href="' + escapeAttr(item.canonical_url || "#") + '">' + escapeHtml(item.canonical_url || "") + "</a></td>",
        '<td>' + renderProductoRelaciones(item, sugerencias) + "</td>",
        '<td class="text-end"><button class="btn btn-sm btn-light-primary" type="button" data-seo-producto-editar="1" data-producto="' + escapeAttr(JSON.stringify(item)) + '"><i class="bi bi-pencil-square"></i></button></td>',
        "</tr>"
      ].join("");
    }).join("");
  }

  function renderProductoRelaciones(item, sugerencias) {
    if (!sugerencias.length) {
      return '<span class="text-muted fs-8">Sin candidato viejo claro.</span>';
    }
    return sugerencias.map(function (sug, index) {
      return [
        '<div class="' + (index > 0 ? "border-top pt-2 mt-2" : "") + '">',
        '<button class="btn btn-sm btn-light-primary me-2" type="button" data-seo-producto-redireccion="1" data-from="' + escapeAttr(sug.path_original || "") + '" data-to="' + escapeAttr(item.url_publica || sug.url_destino_sugerida || "") + '" data-tipo="producto"><i class="bi bi-arrow-return-right"></i></button>',
        '<span class="fw-semibold ecom-seo-path">' + escapeHtml(sug.path_original || "-") + "</span>",
        '<div><a class="fs-8 ecom-seo-path" target="_blank" rel="noopener" href="' + escapeAttr(sug.url_original || "#") + '">' + escapeHtml(sug.url_original || "") + "</a></div>",
        '<div class="d-flex flex-wrap gap-2 mt-1">' + badgeConfianza(sug.confianza) + '<span class="badge badge-light">score ' + escapeHtml(sug.score || 0) + '</span></div>',
        '<div class="text-muted fs-8">' + escapeHtml(sug.titulo_detectado || sug.motivo || "") + "</div>",
        "</div>"
      ].join("");
    }).join("");
  }

  function seleccionarProductoSeo(item) {
    productoSeoSeleccionado = item || null;
    setValue("ecom_seo_producto_id_publicacion", get(item, ["id_publicacion"], ""));
    setValue("ecom_seo_producto_id_sku", get(item, ["id_sku"], ""));
    setValue("ecom_seo_producto_sku", get(item, ["sku"], ""));
    setValue("ecom_seo_producto_titulo_publico", get(item, ["titulo_publico"], ""));
    setValue("ecom_seo_producto_slug", get(item, ["slug"], ""));
    setText("ecom_seo_producto_editor_titulo", get(item, ["titulo_publico"], "Selecciona un producto"));
    setBadge("ecom_seo_producto_editor_badge", get(item, ["estatus_publicacion"], "Sin seleccion"), "badge-light-primary");
    setLink("ecom_seo_producto_url_local", get(item, ["url_local"], ""));
    setLink("ecom_seo_producto_url_canonical", get(item, ["canonical_url"], ""));
    setHtml("ecom_seo_producto_plan", "");
    setHtml("ecom_seo_producto_url_vieja_resultados", "");
    setValue("ecom_seo_producto_url_vieja_q", get(item, ["sku"], "") || get(item, ["titulo_publico"], ""));
    var editor = document.getElementById("ecom_seo_producto_editor_titulo");
    if (editor && editor.scrollIntoView) editor.scrollIntoView({ behavior: "smooth", block: "center" });
  }

  function sugerirSlugProductoSeleccionado() {
    if (!productoSeoSeleccionado) {
      window.alert("Selecciona un producto primero.");
      return;
    }
    var titulo = valor("ecom_seo_producto_titulo_publico");
    if (!titulo) {
      window.alert("Captura un nombre publico.");
      return;
    }
    var tituloOriginal = get(productoSeoSeleccionado, ["titulo_publico"], "");
    var nombreCambio = normalizarTextoComparacion(titulo) !== normalizarTextoComparacion(tituloOriginal);
    var sugerido = !nombreCambio ? (get(productoSeoSeleccionado, ["slug_profesional_sugerido"], "") || get(productoSeoSeleccionado, ["slug_sugerido_desde_nombre"], "")) : "";
    setValue("ecom_seo_producto_slug", sugerido || slugificarProductoLocal(titulo));
  }

  function productoSlugPayload() {
    return {
      id_publicacion: valor("ecom_seo_producto_id_publicacion"),
      id_sku: valor("ecom_seo_producto_id_sku"),
      titulo_publico: valor("ecom_seo_producto_titulo_publico"),
      slug: valor("ecom_seo_producto_slug")
    };
  }

  function prepararProductoSlug() {
    if (!valor("ecom_seo_producto_id_publicacion")) {
      window.alert("Selecciona un producto.");
      return;
    }
    setEstado("Validando producto", "badge-light-warning");
    postJson("/ecommercePublico/seo_producto_slug_plan_erp", productoSlugPayload())
      .then(function (response) {
        renderProductoSlugPlan(response, get(response, ["depurar"], {}));
        setEstado(response.error ? "Revisar" : "Plan listo", response.error ? "badge-light-warning" : "badge-light-success");
      })
      .catch(function (error) {
        setEstado("Error", "badge-light-danger");
        setHtml("ecom_seo_producto_plan", '<div class="alert alert-danger py-3">' + escapeHtml(error.message || "No se pudo validar producto.") + "</div>");
      });
  }

  function guardarProductoSlug() {
    if (!valor("ecom_seo_producto_id_publicacion")) {
      window.alert("Selecciona un producto.");
      return;
    }
    var data = productoSlugPayload();
    if (!window.confirm("Guardar nombre publico y slug de este producto?")) {
      return;
    }
    setEstado("Guardando producto", "badge-light-warning");
    postJson("/ecommercePublico/seo_producto_slug_guardar_erp", data)
      .then(function (response) {
        renderProductoSlugGuardado(response, get(response, ["depurar"], {}));
        setEstado(response.error ? "Bloqueado" : "Guardado", response.error ? "badge-light-warning" : "badge-light-success");
        if (!response.error) cargarProductosSlugs();
      })
      .catch(function (error) {
        setEstado("Error", "badge-light-danger");
        setHtml("ecom_seo_producto_plan", '<div class="alert alert-danger py-3">' + escapeHtml(error.message || "No se pudo guardar producto.") + "</div>");
      });
  }

  function renderProductoSlugPlan(response, depurar) {
    var bloqueos = Array.isArray(depurar.bloqueos) ? depurar.bloqueos : [];
    var tipo = bloqueos.length ? "warning" : "success";
    var redir = depurar.redireccion_301_sugerida || null;
    setHtml("ecom_seo_producto_plan", [
      '<div class="alert alert-' + tipo + ' py-3">',
      escapeHtml(response.mensaje || "Plan generado"),
      bloqueos.length ? '<div class="mt-2">Bloqueos: ' + escapeHtml(bloqueos.join(", ")) + "</div>" : "",
      "</div>",
      redir ? '<div class="border rounded p-3 mb-3"><div class="fw-semibold">301 sugerido por cambio de slug</div><div class="ecom-seo-path fs-8">' + escapeHtml(redir.from) + " -> " + escapeHtml(redir.to) + "</div></div>" : '<div class="text-muted fs-8 mb-3">Sin cambio de slug; no requiere 301.</div>',
      depurar.sql_preview ? '<pre class="bg-light p-3 rounded ecom-seo-code">' + escapeHtml(JSON.stringify(depurar.sql_preview, null, 2)) + "</pre>" : ""
    ].join(""));
  }

  function renderProductoSlugGuardado(response, depurar) {
    var tipo = response.error ? "warning" : "success";
    setHtml("ecom_seo_producto_plan", [
      '<div class="alert alert-' + tipo + ' py-3">',
      escapeHtml(response.mensaje || "Resultado de guardado"),
      "</div>",
      '<pre class="bg-light p-3 rounded ecom-seo-code">',
      escapeHtml(JSON.stringify({
        slug_anterior: depurar.slug_anterior || "",
        slug_actual: depurar.slug_actual || "",
        slug_cambiado: !!depurar.slug_cambiado,
        redireccion_301: depurar.redireccion_301 || null,
        bloqueos: depurar.bloqueos_publicacion || depurar.bloqueos || []
      }, null, 2)),
      "</pre>"
    ].join(""));
  }

  function usarProductoComoRedireccion(node) {
    setValue("ecom_seo_redir_from", node.getAttribute("data-from") || "");
    setValue("ecom_seo_redir_to", node.getAttribute("data-to") || "");
    setValue("ecom_seo_redir_tipo", node.getAttribute("data-tipo") || "producto");
    var form = document.getElementById("ecom_seo_redir_from");
    if (form && form.scrollIntoView) form.scrollIntoView({ behavior: "smooth", block: "center" });
  }

  function buscarProductosCatalogoDesdeUrl(node) {
    var q = node.getAttribute("data-q") || "";
    if (!q) return;
    setValue("ecom_seo_productos_q", q);
    setHtml("ecom_seo_producto_plan", '<div class="alert alert-info py-3">Buscando productos ERP relacionados con: ' + escapeHtml(q) + "</div>");
    fetch("/ecommercePublico/publicaciones_auditar_erp?limite=15&q=" + encodeURIComponent(q), { headers: { Accept: "application/json" } })
      .then(jsonResponse)
      .then(function (response) {
        renderCandidatosCatalogoDesdeUrl(response, q);
      })
      .catch(function (error) {
        setHtml("ecom_seo_producto_plan", '<div class="alert alert-danger py-3">' + escapeHtml(error.message || "No se pudo buscar en catalogo ERP.") + "</div>");
      });
    cargarProductosSlugs();
    var productos = document.getElementById("ecom_seo_productos_body");
    if (productos && productos.scrollIntoView) productos.scrollIntoView({ behavior: "smooth", block: "start" });
  }

  function renderCandidatosCatalogoDesdeUrl(response, q) {
    var candidatos = get(response, ["depurar", "candidatos"], []);
    if (!Array.isArray(candidatos) || !candidatos.length) {
      setHtml("ecom_seo_producto_plan", '<div class="alert alert-warning py-3">No encontre productos ERP para: ' + escapeHtml(q) + "</div>");
      return;
    }
    setHtml("ecom_seo_producto_plan", [
      '<div class="alert alert-info py-3">Candidatos ERP encontrados. Si no esta publicado, revisa los requisitos para habilitarlo como ficha basica agotada.</div>',
      '<div class="table-responsive ecom-seo-scroll"><table class="table table-row-dashed fs-8 gy-2 mb-0"><tbody>',
      candidatos.map(function (item) {
        var bloqueos = get(item, ["bloqueos_publicacion"], get(item, ["bloqueos"], []));
        if (!Array.isArray(bloqueos)) bloqueos = [];
        var sku = item.sku || item.codigo_sku || "";
        var nombre = item.nombre_publico || item.nombre_sku || item.nombre || item.nombre_producto || "";
        var idSku = item.id_sku || "";
        return [
          "<tr>",
          '<td><div class="fw-bold">' + escapeHtml(sku || ("SKU " + idSku)) + '</div><div class="fw-semibold">' + escapeHtml(nombre || "-") + '</div><div class="text-muted fs-8">' + escapeHtml(item.estatus_publicacion || "sin_publicacion") + " · " + escapeHtml(item.disponibilidad_publica_sugerida || item.disponibilidad || "") + '</div><div class="text-muted fs-8">Requisitos: ' + escapeHtml(bloqueos.length ? bloqueos.join(", ") : "listo_o_revisar_publicacion") + "</div></td>",
          '<td class="text-end"><button class="btn btn-sm btn-light-primary" type="button" data-seo-revisar-producto-sugerido="1" data-q="' + escapeAttr(sku || nombre) + '"><i class="bi bi-pencil-square"></i></button></td>',
          "</tr>"
        ].join("");
      }).join(""),
      "</tbody></table></div>"
    ].join(""));
  }

  function revisarProductoDesdeSugerencia(node) {
    var q = node.getAttribute("data-q") || "";
    if (!q) return;
    setValue("ecom_seo_productos_q", q.replace(/\s+\|\s+Artiani$/i, ""));
    setValue("ecom_seo_productos_relacion", "");
    cargarProductosSlugs();
    var tabla = document.getElementById("ecom_seo_productos_body");
    if (tabla && tabla.scrollIntoView) tabla.scrollIntoView({ behavior: "smooth", block: "start" });
  }

  function buscarUrlsViejasParaProducto() {
    if (!productoSeoSeleccionado) {
      return;
    }
    var q = valor("ecom_seo_producto_url_vieja_q");
    var node = document.getElementById("ecom_seo_producto_url_vieja_resultados");
    if (!node) return;
    if (!q || q.length < 3) {
      node.innerHTML = '<div class="text-muted fs-8">Escribe al menos 3 caracteres para buscar URLs productivas anteriores.</div>';
      return;
    }
    var params = new URLSearchParams();
    params.set("limite", "20");
    params.set("q", q);
    params.set("fuente", "indexadas");
    fetch("/ecommercePublico/seo_urls_viejas_revision_erp?" + params.toString(), { headers: { Accept: "application/json" } })
      .then(jsonResponse)
      .then(function (response) {
        var items = get(response, ["depurar", "items"], []);
        if (!Array.isArray(items) || !items.length) {
          node.innerHTML = '<div class="alert alert-info py-3 mb-0">Sin URLs anteriores para esa busqueda.</div>';
          return;
        }
        node.innerHTML = '<div class="table-responsive ecom-seo-scroll"><table class="table table-row-dashed fs-8 gy-2 mb-0"><tbody>' +
          items.map(function (item) {
            var from = item.path_original || "";
            var to = get(productoSeoSeleccionado, ["url_publica"], "");
            var url = item.url_original || ("https://artiani.com.mx" + from);
            return [
              "<tr>",
              '<td><div class="fw-semibold ecom-seo-path">' + escapeHtml(from || "-") + '</div><a class="fs-8 ecom-seo-path" target="_blank" rel="noopener" href="' + escapeAttr(url) + '">' + escapeHtml(url) + '</a><div class="text-muted fs-8">' + escapeHtml(item.titulo_detectado || item.motivo || "") + "</div></td>",
              '<td class="text-end"><button class="btn btn-sm btn-light-primary" type="button" data-seo-producto-url-vieja="1" data-from="' + escapeAttr(from) + '" data-to="' + escapeAttr(to) + '" data-tipo="producto"><i class="bi bi-arrow-return-right"></i></button></td>',
              "</tr>"
            ].join("");
          }).join("") + "</tbody></table></div>";
      })
      .catch(function (error) {
        node.innerHTML = '<div class="alert alert-danger py-3 mb-0">' + escapeHtml(error.message || "No se pudieron buscar URLs anteriores.") + "</div>";
      });
  }

  function usarRevisionComoRedireccion(node) {
    setValue("ecom_seo_redir_from", node.getAttribute("data-from") || "");
    setValue("ecom_seo_redir_to", node.getAttribute("data-to") || "");
    setValue("ecom_seo_redir_tipo", node.getAttribute("data-tipo") || "manual");
    var form = document.getElementById("ecom_seo_redir_from");
    if (form && form.scrollIntoView) form.scrollIntoView({ behavior: "smooth", block: "center" });
  }

  function ocultarRevisionUrl(path) {
    if (!path) return;
    var descartadas = cargarDescartadas();
    if (descartadas[path]) {
      delete descartadas[path];
    } else {
      descartadas[path] = true;
    }
    guardarDescartadas(descartadas);
    cargarRevisionUrls();
  }

  function prepararImportacion() {
    var texto = valor("ecom_seo_urls_viejas");
    if (!texto) {
      window.alert("Pega al menos una URL vieja.");
      return;
    }
    setEstado("Preparando URLs", "badge-light-warning");
    postJson("/ecommercePublico/seo_urls_viejas_importar_plan_erp", { urls_texto: texto })
      .then(function (response) {
        renderImportacionPlan(get(response, ["depurar", "items"], []), response.mensaje || "");
        setEstado("Plan listo", "badge-light-success");
      })
      .catch(function (error) {
        setEstado("Error", "badge-light-danger");
        setHtml("ecom_seo_importacion_plan", '<div class="alert alert-danger">' + escapeHtml(error.message || "No se pudo preparar importacion.") + "</div>");
      });
  }

  function prepararSyncUrls() {
    setEstado("Preparando sync", "badge-light-warning");
    postJson("/ecommercePublico/seo_urls_sincronizar_plan_erp", { limite: valor("ecom_seo_limite") || "25" })
      .then(function (response) {
        renderSyncUrls(response, get(response, ["depurar"], {}));
        setEstado("Plan listo", "badge-light-success");
      })
      .catch(function (error) {
        setEstado("Error", "badge-light-danger");
        setHtml("ecom_seo_urls_sync_resultado", '<div class="alert alert-danger">' + escapeHtml(error.message || "No se pudo preparar sincronizacion.") + "</div>");
      });
  }

  function guardarSyncUrls() {
    var token = valor("ecom_seo_token_urls_sync");
    if (!token) {
      window.alert("Captura el token de sincronizacion.");
      return;
    }
    if (!window.confirm("Sincronizar snapshot de URLs canonicas SEO?")) {
      return;
    }
    setEstado("Sincronizando URLs", "badge-light-warning");
    postJson("/ecommercePublico/seo_urls_sincronizar_erp", { limite: "500", autorizar: token })
      .then(function (response) {
        renderSyncUrls(response, get(response, ["depurar"], {}));
        setEstado(response.error ? "Bloqueado" : "Sincronizado", response.error ? "badge-light-warning" : "badge-light-success");
        if (!response.error) cargarSeo();
      })
      .catch(function (error) {
        setEstado("Error", "badge-light-danger");
        setHtml("ecom_seo_urls_sync_resultado", '<div class="alert alert-danger">' + escapeHtml(error.message || "No se pudo sincronizar URLs.") + "</div>");
      });
  }

  function guardarImportacion() {
    var texto = valor("ecom_seo_urls_viejas");
    var token = valor("ecom_seo_token_importar");
    if (!texto) {
      window.alert("Pega al menos una URL vieja.");
      return;
    }
    if (!token) {
      window.alert("Captura el token de importacion.");
      return;
    }
    if (!window.confirm("Importar URLs viejas para revision SEO?")) {
      return;
    }
    setEstado("Importando URLs", "badge-light-warning");
    postJson("/ecommercePublico/seo_urls_viejas_importar_erp", { urls_texto: texto, autorizar: token })
      .then(function (response) {
        var depurar = get(response, ["depurar"], {});
        renderImportacionGuardada(response, depurar);
        setEstado(response.error ? "Bloqueado" : "Importado", response.error ? "badge-light-warning" : "badge-light-success");
        if (!response.error) cargarSeo();
      })
      .catch(function (error) {
        setEstado("Error", "badge-light-danger");
        setHtml("ecom_seo_importacion_plan", '<div class="alert alert-danger">' + escapeHtml(error.message || "No se pudo importar.") + "</div>");
      });
  }

  function prepararRedireccion() {
    var data = {
      from: valor("ecom_seo_redir_from"),
      to: valor("ecom_seo_redir_to"),
      status: valor("ecom_seo_redir_status") || "301",
      tipo: valor("ecom_seo_redir_tipo") || "manual",
      motivo: "revision_manual_seo"
    };
    if (!data.from || !data.to) {
      window.alert("Captura origen y destino.");
      return;
    }
    setEstado("Validando 301", "badge-light-warning");
    postJson("/ecommercePublico/seo_redireccion_plan_erp", data)
      .then(function (response) {
        renderRedireccionPlan(get(response, ["depurar"], {}));
        setEstado("Plan listo", "badge-light-success");
      })
      .catch(function (error) {
        setEstado("Error", "badge-light-danger");
        setHtml("ecom_seo_redireccion_resultado", '<div class="alert alert-danger">' + escapeHtml(error.message || "No se pudo validar redireccion.") + "</div>");
      });
  }

  function guardarRedireccion() {
    var data = {
      from: valor("ecom_seo_redir_from"),
      to: valor("ecom_seo_redir_to"),
      status: valor("ecom_seo_redir_status") || "301",
      tipo: valor("ecom_seo_redir_tipo") || "manual",
      motivo: "revision_manual_seo"
    };
    if (!data.from || !data.to) {
      window.alert("Captura origen y destino.");
      return;
    }
    if (!window.confirm("Guardar esta redireccion SEO aprobada?")) {
      return;
    }
    setEstado("Guardando 301", "badge-light-warning");
    postJson("/ecommercePublico/seo_redireccion_guardar_erp", data)
      .then(function (response) {
        renderRedireccionGuardada(response, get(response, ["depurar"], {}));
        setEstado(response.error ? "Bloqueado" : "Guardado", response.error ? "badge-light-warning" : "badge-light-success");
        if (!response.error) cargarSeo();
      })
      .catch(function (error) {
        setEstado("Error", "badge-light-danger");
        setHtml("ecom_seo_redireccion_resultado", '<div class="alert alert-danger">' + escapeHtml(error.message || "No se pudo guardar redireccion.") + "</div>");
      });
  }

  function renderImportacionPlan(items, mensaje) {
    var node = document.getElementById("ecom_seo_importacion_plan");
    if (!node) return;
    if (!Array.isArray(items) || items.length === 0) {
      node.innerHTML = '<div class="alert alert-warning mb-0">' + escapeHtml(mensaje || "Sin URLs validas para preparar.") + "</div>";
      return;
    }
    node.innerHTML = '<div class="alert alert-info py-3">Plan read-only: no guarda URLs ni crea redirecciones.</div>' +
      '<div class="table-responsive ecom-seo-scroll"><table class="table table-row-dashed fs-7 gy-3 mb-0"><thead><tr class="text-muted fw-bold"><th>Origen</th><th>Tipo</th><th>Sugerencia</th><th>Confianza</th></tr></thead><tbody>' +
      items.map(function (item) {
        return '<tr><td class="fw-semibold ecom-seo-path">' + escapeHtml(item.path_original || "-") + '</td><td>' + escapeHtml(item.tipo_detectado || "-") + '</td><td class="ecom-seo-path">' + escapeHtml(item.url_destino_sugerida || "Revision manual") + '<div class="text-muted fs-8">' + escapeHtml(item.motivo || "") + '</div></td><td>' + badgeConfianza(item.confianza) + "</td></tr>";
      }).join("") + "</tbody></table></div>";
  }

  function renderImportacionGuardada(response, depurar) {
    var tipo = response.error ? "warning" : "success";
    setHtml("ecom_seo_importacion_plan", [
      '<div class="alert alert-' + tipo + ' py-3">',
      escapeHtml(response.mensaje || "Resultado de importacion"),
      "</div>",
      '<div class="row g-3">',
      resumenCaja("Recibidas", depurar.total_recibidas || 0),
      resumenCaja("Insertadas", depurar.total_insertadas || 0),
      resumenCaja("Actualizadas", depurar.total_actualizadas || 0),
      resumenCaja("Omitidas", depurar.total_omitidas || 0),
      "</div>"
    ].join(""));
  }

  function renderSyncUrls(response, depurar) {
    var tipo = response.error ? "warning" : "info";
    var total = depurar.total_canonicas || 0;
    setHtml("ecom_seo_urls_sync_resultado", [
      '<div class="alert alert-' + tipo + ' py-3">',
      escapeHtml(response.mensaje || "Resultado de sincronizacion"),
      "</div>",
      '<div class="row g-3">',
      resumenCaja("Canonicas", total),
      resumenCaja("Nuevas", depurar.nuevas != null ? depurar.nuevas : depurar.total_insertadas || 0),
      resumenCaja("Actualizar", depurar.actualizar != null ? depurar.actualizar : depurar.total_actualizadas || 0),
      resumenCaja("Sin cambio", depurar.sin_cambio != null ? depurar.sin_cambio : depurar.total_sin_cambio || 0),
      "</div>",
      depurar.sql_preview ? '<pre class="bg-light p-4 rounded ecom-seo-code mt-4">' + escapeHtml(JSON.stringify(depurar.sql_preview, null, 2)) + "</pre>" : ""
    ].join(""));
  }

  function renderRedireccionPlan(depurar) {
    var node = document.getElementById("ecom_seo_redireccion_resultado");
    if (!node) return;
    var bloqueos = Array.isArray(depurar.bloqueos) ? depurar.bloqueos : [];
    var alerta = bloqueos.length
      ? '<div class="alert alert-warning py-3">Corrige: ' + escapeHtml(bloqueos.join(", ")) + "</div>"
      : '<div class="alert alert-success py-3">Redireccion valida para aprobacion futura.</div>';
    node.innerHTML = alerta +
      '<pre class="bg-light p-4 rounded ecom-seo-code">' + escapeHtml(depurar.sql_preview || JSON.stringify(depurar.redireccion || {}, null, 2)) + "</pre>";
  }

  function renderRedireccionGuardada(response, depurar) {
    var tipo = response.error ? "warning" : "success";
    var redireccion = depurar.redireccion || {};
    setHtml("ecom_seo_redireccion_resultado", [
      '<div class="alert alert-' + tipo + ' py-3">',
      escapeHtml(response.mensaje || "Resultado de redireccion"),
      "</div>",
      '<pre class="bg-light p-4 rounded ecom-seo-code">',
      escapeHtml(JSON.stringify({
        filas_afectadas: depurar.filas_afectadas || 0,
        redireccion: redireccion,
        bloqueos: depurar.bloqueos || []
      }, null, 2)),
      "</pre>"
    ].join(""));
  }

  function resumenCaja(label, value) {
    return '<div class="col-6 col-md-3"><div class="border rounded p-3"><div class="text-muted fs-8 text-uppercase fw-bold">' + escapeHtml(label) + '</div><div class="fs-4 fw-bold">' + escapeHtml(value) + "</div></div></div>";
  }

  function badgeProductoEstatus(estatus) {
    var clase = estatus === "publicado" ? "badge-light-success" : (estatus === "pausado" ? "badge-light-warning" : "badge-light-info");
    return '<span class="badge ' + clase + '">' + escapeHtml(estatus || "-") + "</span>";
  }

  function renderError(mensaje) {
    var tbody = document.getElementById("ecom_seo_urls_body");
    if (tbody) tbody.innerHTML = '<tr><td colspan="4"><div class="alert alert-danger mb-0">' + escapeHtml(mensaje) + "</div></td></tr>";
  }

  function badgeTipo(tipo) {
    return '<span class="badge badge-light-primary">' + escapeHtml(tipo || "url") + "</span>";
  }

  function badgeStatus(status) {
    return '<span class="badge badge-light-success">' + escapeHtml(status) + "</span>";
  }

  function badgeHttp(status) {
    status = Number(status || 0);
    var clase = status >= 500 ? "badge-light-danger" : (status >= 400 ? "badge-light-warning" : "badge-light-success");
    return '<span class="badge ' + clase + '">' + escapeHtml(status || "-") + "</span>";
  }

  function badgeAccion(accion) {
    var clases = {
      aprobar_301_candidato: "badge-light-success",
      sin_redireccion_necesaria: "badge-light-info",
      validar_301_candidato: "badge-light-primary",
      revisar_manual: "badge-light-warning",
      excluir_o_410: "badge-light-danger"
    };
    var textos = {
      aprobar_301_candidato: "Aprobar",
      sin_redireccion_necesaria: "Sin 301",
      validar_301_candidato: "Validar",
      revisar_manual: "Manual",
      excluir_o_410: "Excluir/410"
    };
    return '<span class="badge ' + (clases[accion] || "badge-light") + '">' + escapeHtml(textos[accion] || accion || "Revision") + "</span>";
  }

  function badgeDdl(item) {
    var depurar = item && item.depurar ? item.depurar : {};
    if (depurar.ejecutado) return '<span class="badge badge-light-success">Aplicado</span>';
    if (depurar.sql) return '<span class="badge badge-light-warning">Pendiente</span>';
    return '<span class="badge badge-light-info">Existe</span>';
  }

  function etiquetaPaso(valor) {
    return {
      revisar_plan_ddl: "Revisar DDL",
      autorizar_respaldo_y_apply: "Autorizar apply",
      importar_urls_viejas: "Importar URLs",
      revisar_equivalencias: "Revisar destinos",
      aprobar_redirecciones_301: "Aprobar 301",
      integrar_frontend: "Integrar frontend"
    }[valor] || valor;
  }

  function extraerTabla(sql) {
    var match = String(sql || "").match(/CREATE TABLE `([^`]+)`/);
    return match ? match[1] : "";
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

  function setValue(id, value) {
    var el = document.getElementById(id);
    if (el) el.value = String(value == null ? "" : value);
  }

  function setHtml(id, value) {
    var el = document.getElementById(id);
    if (el) el.innerHTML = value;
  }

  function setLink(id, url) {
    var el = document.getElementById(id);
    if (!el) return;
    el.textContent = url || "";
    el.setAttribute("href", url || "#");
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

  function badgeConfianza(valor) {
    var clase = valor === "exacta" || valor === "alta" ? "badge-light-success" : (valor === "media" ? "badge-light-info" : "badge-light-warning");
    return '<span class="badge ' + clase + '">' + escapeHtml(valor || "baja") + "</span>";
  }

  function setBadge(id, texto, clase) {
    var node = document.getElementById(id);
    if (!node) return;
    node.className = "badge " + clase;
    node.textContent = texto;
  }

  function setEstado(texto, clase) {
    setBadge("ecom_seo_estado", texto, clase);
  }

  function get(obj, path, fallback) {
    var cur = obj;
    for (var i = 0; i < path.length; i++) {
      if (cur == null || typeof cur !== "object" || !(path[i] in cur)) return fallback;
      cur = cur[path[i]];
    }
    return cur == null ? fallback : cur;
  }

  function cargarDescartadas() {
    try {
      return JSON.parse(window.localStorage.getItem(descartadasKey) || "{}") || {};
    } catch (e) {
      return {};
    }
  }

  function guardarDescartadas(descartadas) {
    try {
      window.localStorage.setItem(descartadasKey, JSON.stringify(descartadas || {}));
    } catch (e) {}
  }

  function cargarDecisiones() {
    try {
      return JSON.parse(window.localStorage.getItem(decisionesKey) || "{}") || {};
    } catch (e) {
      return {};
    }
  }

  function guardarDecisionUrlVieja(path, decision) {
    if (!path) return;
    var decisiones = cargarDecisiones();
    if (!decision) {
      delete decisiones[path];
    } else {
      decisiones[path] = decision;
    }
    try {
      window.localStorage.setItem(decisionesKey, JSON.stringify(decisiones));
    } catch (e) {}
    if (decision === "redirigir_categoria") {
      setValue("ecom_seo_redir_from", path);
      setValue("ecom_seo_redir_tipo", "categoria");
      setValue("ecom_seo_destino_tipo", "categoria");
      setValue("ecom_seo_destino_q", queryDesdePathViejo(path));
      buscarDestinosCanonicos();
    }
    setEstado(decision ? "Decision marcada" : "Decision pendiente", decision ? "badge-light-success" : "badge-light-warning");
  }

  function buscarDestinosCanonicos() {
    var q = valor("ecom_seo_destino_q");
    var tipo = valor("ecom_seo_destino_tipo") || "producto";
    var node = document.getElementById("ecom_seo_destino_resultados");
    if (!node) return;
    if (!q || q.length < 3) {
      node.innerHTML = '<div class="text-muted fs-8">Escribe al menos 3 caracteres.</div>';
      return;
    }
    fetch("/ecommercePublico/seo_destinos_canonicos_erp?limite=20&tipo=" + encodeURIComponent(tipo) + "&q=" + encodeURIComponent(q), { headers: { Accept: "application/json" } })
      .then(jsonResponse)
      .then(function (response) {
        var items = get(response, ["depurar", "items"], []);
        if (!Array.isArray(items) || !items.length) {
          node.innerHTML = '<div class="alert alert-info py-3 mb-0">Sin destinos ' + escapeHtml(tipo) + ' para esa busqueda.</div>';
          return;
        }
        node.innerHTML = '<div class="table-responsive ecom-seo-scroll"><table class="table table-row-dashed fs-8 gy-2 mb-0"><tbody>' +
          items.map(function (item) {
            return [
              "<tr>",
              '<td><div class="fw-semibold ecom-seo-path">' + escapeHtml(item.path || "-") + '</div><div class="text-muted fs-8">' + escapeHtml(item.title || "") + '</div><a class="fs-8 ecom-seo-path" target="_blank" rel="noopener" href="' + escapeAttr(item.url_local || "#") + '">' + escapeHtml(item.url_local || "") + "</a></td>",
              '<td class="text-end"><button class="btn btn-sm btn-light-primary" type="button" data-seo-usar-destino-canonico="1" data-path="' + escapeAttr(item.path || "") + '" data-tipo="' + escapeAttr(item.tipo || tipo) + '"><i class="bi bi-check2"></i></button></td>',
              "</tr>"
            ].join("");
          }).join("") + "</tbody></table></div>";
      })
      .catch(function (error) {
        node.innerHTML = '<div class="alert alert-danger py-3 mb-0">' + escapeHtml(error.message || "No se pudieron buscar destinos.") + "</div>";
      });
  }

  function opcionDecision(value, label, actual) {
    return '<option value="' + escapeAttr(value) + '"' + (String(value) === String(actual) ? " selected" : "") + ">" + escapeHtml(label) + "</option>";
  }

  function queryDesdePathViejo(path) {
    path = normalizarPathUi(path);
    var partes = path.split("/").filter(Boolean);
    var ultimo = partes.length ? partes[partes.length - 1] : "";
    var penultimo = partes.length > 1 ? partes[partes.length - 2] : "";
    var candidato = ultimo && ultimo.toLowerCase() !== "undefined" ? ultimo : penultimo;
    candidato = candidato.replace(/-/g, " ").replace(/\s+/g, " ").trim();
    return candidato || path.replace(/[\/-]+/g, " ").trim();
  }

  function debounce(fn, wait) {
    var timer = null;
    return function () {
      window.clearTimeout(timer);
      timer = window.setTimeout(fn, wait || 250);
    };
  }

  function escapeHtml(value) {
    return String(value == null ? "" : value)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }

  function escapeAttr(value) {
    return escapeHtml(value).replace(/`/g, "&#096;");
  }

  function parseJsonSeguro(value) {
    try {
      return JSON.parse(value || "{}") || {};
    } catch (e) {
      return {};
    }
  }

  function slugificarLocal(texto) {
    return String(texto || "")
      .toLowerCase()
      .normalize("NFD")
      .replace(/[\u0300-\u036f]/g, "")
      .replace(/&|\+/g, " y ")
      .replace(/['"`´]+/g, "")
      .replace(/[^a-z0-9]+/g, "-")
      .replace(/^-+|-+$/g, "")
      .slice(0, 170) || "producto";
  }

  function slugificarProductoLocal(texto) {
    texto = String(texto || "")
      .toLowerCase()
      .normalize("NFD")
      .replace(/[\u0300-\u036f]/g, "")
      .replace(/\b(\d+(?:[\.,]\d+)?)\s*(kilogramos?|kgs?|kg)\b/gi, "$1kg")
      .replace(/\b(\d+(?:[\.,]\d+)?)\s*(gramos?|grs?|gr|g)\b/gi, "$1g")
      .replace(/\b(\d+(?:[\.,]\d+)?)\s*(mililitros?|mls?|ml)\b/gi, "$1ml")
      .replace(/\b(\d+(?:[\.,]\d+)?)\s*(litros?|lts?|lt|l)\b/gi, "$1l")
      .replace(/\b(c\/u|cu|pzas?|pza|pz|pieza?s?|unidad(?:es)?|unid(?:ad)?\.?)\b/gi, " ")
      .replace(/\s+/g, " ")
      .trim();
    return slugificarLocal(texto);
  }

  function normalizarTextoComparacion(texto) {
    return String(texto || "")
      .toLowerCase()
      .normalize("NFD")
      .replace(/[\u0300-\u036f]/g, "")
      .replace(/\s+/g, " ")
      .trim();
  }

  function normalizarPathUi(path) {
    path = String(path == null ? "" : path).trim();
    if (!path) return "";
    try {
      if (/^https?:\/\//i.test(path)) path = new URL(path).pathname;
    } catch (e) {}
    path = "/" + path.replace(/^\/+/, "");
    return path.replace(/\/+/g, "/");
  }
})();
