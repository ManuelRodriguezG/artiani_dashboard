/*
 * Documentacion IA: Codex GPT-5, 2026-09-03.
 * Proposito: UX interna para revisar y ejecutar acciones autorizadas de SEO/migracion URLs ecommerce.
 * Impacto: consulta estado, URLs, redirecciones, sitemap y permite guardar solo con token operativo.
 * Contrato: POST de escritura protegido con CSRF y token; DDL sigue fuera de esta pantalla.
 */
(function () {
  "use strict";

  var mostrarOcultas = false;
  var descartadasKey = "ecom_seo_urls_viejas_descartadas";

  document.addEventListener("DOMContentLoaded", function () {
    bindEvents();
    cargarSeo();
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
    if (recargar) recargar.addEventListener("click", cargarSeo);
    if (limite) limite.addEventListener("change", cargarSeo);
    if (importarPlan) importarPlan.addEventListener("click", prepararImportacion);
    if (importarGuardar) importarGuardar.addEventListener("click", guardarImportacion);
    if (redireccionPlan) redireccionPlan.addEventListener("click", prepararRedireccion);
    if (redireccionGuardar) redireccionGuardar.addEventListener("click", guardarRedireccion);
    if (urlsSyncPlan) urlsSyncPlan.addEventListener("click", prepararSyncUrls);
    if (urlsSyncGuardar) urlsSyncGuardar.addEventListener("click", guardarSyncUrls);
    if (revisionRecargar) revisionRecargar.addEventListener("click", cargarRevisionUrls);
    if (revisionOcultas) revisionOcultas.addEventListener("click", function () {
      mostrarOcultas = !mostrarOcultas;
      revisionOcultas.className = mostrarOcultas ? "btn btn-sm btn-warning" : "btn btn-sm btn-light-warning";
      revisionOcultas.innerHTML = '<i class="bi bi-eye"></i> ' + (mostrarOcultas ? "Mostrando ocultas" : "Ocultas");
      cargarRevisionUrls();
    });
    [revisionQ, revisionAccion, revisionPrioridad, revisionLimite].forEach(function (node) {
      if (!node) return;
      node.addEventListener(node === revisionQ ? "input" : "change", debounce(cargarRevisionUrls, 250));
    });
    document.addEventListener("click", function (event) {
      var usar = event.target.closest("[data-seo-usar-redireccion]");
      var ocultar = event.target.closest("[data-seo-ocultar-url]");
      if (usar) {
        usarRevisionComoRedireccion(usar);
      }
      if (ocultar) {
        ocultarRevisionUrl(ocultar.getAttribute("data-seo-ocultar-url") || "");
      }
    });
  }

  function cargarSeo() {
    setEstado("Cargando", "badge-light-warning");
    var params = new URLSearchParams();
    params.set("limite", valor("ecom_seo_limite") || "25");
    Promise.all([
      fetch("/ecommercePublico/seo_dashboard_erp?" + params.toString(), { headers: { Accept: "application/json" } }).then(jsonResponse),
      fetch("/ecommercePublico/esquema_plan_seo_migracion", { headers: { Accept: "application/json" } }).then(jsonResponse),
      fetch("/ecommercePublico/seo_urls_viejas_revision_erp?limite=120", { headers: { Accept: "application/json" } }).then(jsonResponse)
    ]).then(function (responses) {
      renderDashboard(responses[0]);
      renderDdl(responses[1]);
      renderRevisionUrls(responses[2]);
      setEstado("Read-only", "badge-light-success");
    }).catch(function (error) {
      setEstado("Error", "badge-light-danger");
      renderError(error.message || "No se pudo consultar SEO.");
    });
  }

  function cargarRevisionUrls() {
    var params = new URLSearchParams();
    params.set("limite", valor("ecom_seo_revision_limite") || "120");
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
    var descartadas = cargarDescartadas();
    var items = Array.isArray(depurar.items) ? depurar.items : [];
    var visibles = items.filter(function (item) {
      return mostrarOcultas || !descartadas[item.path_original || ""];
    });
    setHtml("ecom_seo_revision_info", [
      '<div class="d-flex flex-wrap gap-2 align-items-center">',
      '<span class="badge badge-light-primary">Reporte: ' + escapeHtml(depurar.total_reporte || 0) + '</span>',
      depurar.total_nuevas ? '<span class="badge badge-light-success">Nuevas: ' + escapeHtml(depurar.total_nuevas) + '</span>' : "",
      '<span class="badge badge-light-info">Filtrado: ' + escapeHtml(depurar.total_filtrado || 0) + '</span>',
      '<span class="badge badge-light-warning">Ocultas UI: ' + escapeHtml(Object.keys(descartadas).length) + '</span>',
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
      var ocultada = descartadas[item.path_original || ""];
      var sugerencias = Array.isArray(item.sugerencias) ? item.sugerencias : [];
      return [
        '<tr class="' + (ocultada ? "ecom-seo-row-muted" : "") + '">',
        '<td><div class="fw-semibold ecom-seo-path">' + escapeHtml(item.path_original || "-") + '</div><div class="text-muted fs-8 ecom-seo-path">' + escapeHtml(item.titulo_detectado || item.url_original || "") + "</div></td>",
        '<td>' + badgeHttp(item.status_http) + "</td>",
        '<td>' + badgeTipo(item.tipo_plan || item.tipo_crawl || "url") + '<div class="text-muted fs-8">' + escapeHtml(item.tipo_crawl || "") + "</div></td>",
        '<td>' + renderSugerenciasRevision(item, sugerencias, destino, local) + "</td>",
        '<td>' + badgeAccion(item.accion_sugerida) + '<div class="mt-1">' + badgeConfianza(item.confianza) + '</div><div class="text-muted fs-8">' + escapeHtml(item.nota || item.motivo || "") + "</div></td>",
        '<td class="text-end"><div class="d-flex justify-content-end gap-2">' +
          '<button class="btn btn-sm btn-light-primary" type="button" data-seo-usar-redireccion="1" data-from="' + escapeAttr(item.path_original || "") + '" data-to="' + escapeAttr(destino) + '" data-tipo="' + escapeAttr(item.tipo_plan || "manual") + '"' + (!destino ? " disabled" : "") + '><i class="bi bi-arrow-return-right"></i></button>' +
          '<button class="btn btn-sm btn-light-warning" type="button" data-seo-ocultar-url="' + escapeAttr(item.path_original || "") + '"><i class="bi bi-eye-slash"></i></button>' +
        "</div></td>",
        "</tr>"
      ].join("");
    }).join("");
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
        '<span class="fw-semibold ecom-seo-path">' + escapeHtml(path || "-") + "</span>",
        '<div class="text-muted fs-8 ecom-seo-path">' + escapeHtml(sug.title || sug.motivo || "") + "</div>",
        '<div class="d-flex flex-wrap gap-2 mt-1">' + badgeConfianza(sug.confianza) + '<span class="badge badge-light">score ' + escapeHtml(sug.score || 0) + '</span><span class="badge badge-light">' + escapeHtml(sug.motivo || "") + "</span></div>",
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
      resumenCaja("Aprobar", acciones.aprobar_301_candidato || 0),
      resumenCaja("Validar", acciones.validar_301_candidato || 0),
      resumenCaja("Manual", acciones.revisar_manual || 0),
      resumenCaja("Excluir/410", acciones.excluir_o_410 || 0)
    ].join("");
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
    var token = valor("ecom_seo_token_redireccion");
    var data = {
      from: valor("ecom_seo_redir_from"),
      to: valor("ecom_seo_redir_to"),
      status: valor("ecom_seo_redir_status") || "301",
      tipo: valor("ecom_seo_redir_tipo") || "manual",
      motivo: "revision_manual_seo",
      autorizar: token
    };
    if (!data.from || !data.to) {
      window.alert("Captura origen y destino.");
      return;
    }
    if (!token) {
      window.alert("Captura el token de redireccion.");
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
      validar_301_candidato: "badge-light-primary",
      revisar_manual: "badge-light-warning",
      excluir_o_410: "badge-light-danger"
    };
    var textos = {
      aprobar_301_candidato: "Aprobar",
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
})();
