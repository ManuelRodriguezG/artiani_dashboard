/*
 * Documentacion IA: Codex GPT-5, 2026-08-31.
 * Proposito: UX read-only para Business Intelligence / Publicidad y temporadas.
 * Impacto: consulta historico legacy BI para decisiones comerciales sin escribir BD.
 * Contrato: solo GET interno protegido.
 */
(function () {
  "use strict";

  var chartTendencia = null;

  document.addEventListener("DOMContentLoaded", function () {
    iniciarFechas();
    bindEvents();
    cargarDashboard();
    cargarDiagnostico();
  });

  function iniciarFechas() {
    var hasta = new Date();
    var desde = new Date();
    desde.setFullYear(hasta.getFullYear() - 1);
    setValue("bi_pub_desde", isoDate(desde));
    setValue("bi_pub_hasta", isoDate(hasta));
  }

  function bindEvents() {
    on("bi_pub_recargar", "click", cargarDashboard);
    on("bi_diag_recargar", "click", cargarDiagnostico);
    on("bi_pub_desde", "change", cargarDashboard);
    on("bi_pub_hasta", "change", cargarDashboard);
    on("bi_pub_limite", "change", cargarDashboard);
  }

  function cargarDashboard() {
    setEstado("Cargando", "badge-light-warning");
    var params = new URLSearchParams();
    params.set("desde", valor("bi_pub_desde"));
    params.set("hasta", valor("bi_pub_hasta"));
    params.set("limite", valor("bi_pub_limite") || "20");
    fetch("/businessintelligence/publicidad_dashboard_erp?" + params.toString(), { headers: { "Accept": "application/json" } })
      .then(jsonResponse)
      .then(renderDashboard)
      .catch(function (error) {
        setEstado("Error", "badge-light-danger");
        renderList("bi_productos_top", [{ nombre: error.message || "No se pudo consultar BI", total: "" }]);
      });
  }

  function cargarDiagnostico() {
    fetch("/businessintelligence/diagnostico_erp", { headers: { "Accept": "application/json" } })
      .then(jsonResponse)
      .then(function (response) {
        renderDiagnostico(get(response, ["depurar"], {}));
      })
      .catch(function (error) {
        renderList("bi_diagnostico", [{ nombre: error.message || "No se pudo consultar diagnostico", total: "" }]);
      });
  }

  function renderDashboard(response) {
    var depurar = get(response, ["depurar"], {});
    var resumen = get(depurar, ["resumen"], {});
    setFuente(depurar);
    setText("bi_kpi_eventos", resumen.eventos_consumibles || 0);
    setText("bi_kpi_productos", resumen.visitas_productos || 0);
    setText("bi_kpi_busquedas", resumen.busquedas_total || 0);
    setText("bi_kpi_sin_resultado", resumen.busquedas_sin_resultado || 0);
    renderProductos("bi_productos_top", get(depurar, ["productos_top"], []));
    renderTerminos("bi_busquedas_top", get(depurar, ["busquedas_top"], []));
    renderList("bi_categorias_top", get(depurar, ["categorias_top"], []));
    renderList("bi_clasificaciones_top", get(depurar, ["clasificaciones_top"], []));
    renderList("bi_marcas_top", get(depurar, ["marcas_top"], []));
    renderTerminos("bi_busquedas_sin_resultado", get(depurar, ["busquedas_sin_resultado"], []));
    renderMesTipo(get(depurar, ["visitas_por_mes_tipo"], []));
    renderList("bi_acciones", get(depurar, ["acciones_consumibles"], []));
    renderRecomendaciones(get(depurar, ["recomendaciones_publicidad"], []));
    renderTendencia(get(depurar, ["tendencia_mensual"], []));
    renderCalendario(get(depurar, ["calendario_comercial"], []));
    toggleEmpty(Number(resumen.eventos_consumibles || 0) + Number(resumen.busquedas_total || 0) === 0);
    setEstado(response && response.error ? "Revisar" : "Read-only", response && response.error ? "badge-light-danger" : "badge-light-success");
  }

  function renderProductos(id, items) {
    var node = document.getElementById(id);
    if (!node) return;
    if (!Array.isArray(items) || items.length === 0) {
      node.innerHTML = '<div class="text-muted py-3">Sin datos en el rango.</div>';
      return;
    }
    node.innerHTML = '<div class="table-responsive"><table class="table table-row-dashed fs-7 gy-3 mb-0"><tbody>' + items.map(function (item) {
      return '<tr><td><span class="fw-semibold">' + escapeHtml(item.nombre || "") + '</span><div class="text-muted fs-8">' + escapeHtml(item.sku || ("Producto " + (item.id_producto || ""))) + '</div></td><td class="text-end fw-bold">' + Number(item.total || 0) + '</td></tr>';
    }).join("") + '</tbody></table></div>';
  }

  function renderTerminos(id, items) {
    var node = document.getElementById(id);
    if (!node) return;
    if (!Array.isArray(items) || items.length === 0) {
      node.innerHTML = '<div class="text-muted py-3">Sin datos en el rango.</div>';
      return;
    }
    node.innerHTML = '<div class="table-responsive"><table class="table table-row-dashed fs-7 gy-3 mb-0"><tbody>' + items.map(function (item) {
      return '<tr><td class="fw-semibold">' + escapeHtml(item.termino || item.nombre || "") + '</td><td class="text-end fw-bold">' + Number(item.total || 0) + '</td></tr>';
    }).join("") + '</tbody></table></div>';
  }

  function renderList(id, items) {
    var node = document.getElementById(id);
    if (!node) return;
    if (!Array.isArray(items) || items.length === 0) {
      node.innerHTML = '<div class="text-muted py-3">Sin datos en el rango.</div>';
      return;
    }
    node.innerHTML = '<div class="table-responsive"><table class="table table-row-dashed fs-7 gy-3 mb-0"><tbody>' + items.map(function (item) {
      var etiqueta = item.nombre || item.tabla || item.termino || item.tipo || "";
      if (item.tipo && item.accion) etiqueta = item.tipo + " / " + item.accion;
      return '<tr><td class="fw-semibold">' + escapeHtml(etiqueta) + '</td><td class="text-end fw-bold">' + escapeHtml(item.total == null ? "" : item.total) + '</td></tr>';
    }).join("") + '</tbody></table></div>';
  }

  function renderMesTipo(items) {
    var node = document.getElementById("bi_visitas_mes");
    if (!node) return;
    if (!Array.isArray(items) || items.length === 0) {
      node.innerHTML = '<div class="text-muted py-3">Sin datos en el rango.</div>';
      return;
    }
    node.innerHTML = '<div class="table-responsive"><table class="table table-row-dashed fs-7 gy-3 mb-0"><tbody>' + items.map(function (item) {
      return '<tr><td><span class="fw-semibold">' + escapeHtml(item.mes || "") + '</span><div class="text-muted fs-8">' + escapeHtml(item.tipo || "") + '</div></td><td class="text-end fw-bold">' + Number(item.total || 0) + '</td></tr>';
    }).join("") + '</tbody></table></div>';
  }

  function renderTendencia(items) {
    var chartNode = document.getElementById("bi_tendencia_chart");
    var fallback = document.getElementById("bi_tendencia_fallback");
    if (!chartNode || !fallback) return;
    if (!Array.isArray(items) || items.length === 0) {
      chartNode.innerHTML = "";
      fallback.innerHTML = '<div class="text-muted py-3">Sin datos en el rango.</div>';
      return;
    }
    var labels = items.map(function (item) { return item.mes || ""; });
    var productos = items.map(function (item) { return Number(item.productos || 0); });
    var categorias = items.map(function (item) { return Number(item.categorias || 0); });
    var busquedas = items.map(function (item) { return Number(item.busquedas || 0); });
    if (typeof ApexCharts === "undefined") {
      chartNode.innerHTML = "";
      fallback.innerHTML = '<div class="table-responsive"><table class="table table-row-dashed fs-7 gy-3 mb-0"><tbody>' + items.map(function (item) {
        return '<tr><td class="fw-semibold">' + escapeHtml(item.mes || "") + '</td><td class="text-end">' + Number(item.productos || 0) + '</td><td class="text-end">' + Number(item.categorias || 0) + '</td><td class="text-end">' + Number(item.busquedas || 0) + '</td></tr>';
      }).join("") + '</tbody></table></div>';
      return;
    }
    fallback.innerHTML = "";
    if (chartTendencia) {
      chartTendencia.destroy();
      chartTendencia = null;
    }
    chartTendencia = new ApexCharts(chartNode, {
      chart: { type: "area", height: 320, toolbar: { show: false }, fontFamily: "inherit" },
      dataLabels: { enabled: false },
      stroke: { curve: "smooth", width: 3 },
      series: [
        { name: "Productos", data: productos },
        { name: "Categorias", data: categorias },
        { name: "Busquedas", data: busquedas }
      ],
      xaxis: { categories: labels, labels: { style: { colors: "#7e8299" } } },
      yaxis: { labels: { style: { colors: "#7e8299" } } },
      colors: ["#3e97ff", "#50cd89", "#f1416c"],
      fill: { type: "gradient", gradient: { opacityFrom: 0.25, opacityTo: 0.02 } },
      legend: { position: "top", horizontalAlign: "right" },
      tooltip: { shared: true, intersect: false }
    });
    chartTendencia.render();
  }

  function renderRecomendaciones(items) {
    var node = document.getElementById("bi_recomendaciones");
    if (!node) return;
    if (!Array.isArray(items) || items.length === 0) {
      node.innerHTML = '<div class="col-12 text-muted py-3">Sin recomendaciones en el rango.</div>';
      return;
    }
    node.innerHTML = items.map(function (item) {
      return '<div class="col-md-6 col-xl-4"><div class="bi-rec h-100">' +
        '<div class="d-flex justify-content-between gap-2 mb-2"><span class="badge ' + prioridadClase(item.prioridad) + '">' + escapeHtml(item.prioridad || "") + '</span><strong>' + Number(item.total || 0) + '</strong></div>' +
        '<div class="bi-rec__title mb-2">' + escapeHtml(item.titulo || "") + '</div>' +
        '<div class="text-muted fs-8">' + escapeHtml(item.detalle || "") + '</div>' +
        '</div></div>';
    }).join("");
  }

  function prioridadClase(prioridad) {
    if (prioridad === "alta") return "badge-light-danger";
    if (prioridad === "media") return "badge-light-warning";
    return "badge-light-primary";
  }

  function renderCalendario(items) {
    var node = document.getElementById("bi_calendario");
    if (!node) return;
    if (!Array.isArray(items) || items.length === 0) {
      node.innerHTML = '<div class="text-muted py-3">Sin datos en el rango.</div>';
      return;
    }
    node.innerHTML = items.map(function (mes) {
      var productos = Array.isArray(mes.productos) ? mes.productos : [];
      var busquedas = Array.isArray(mes.busquedas) ? mes.busquedas : [];
      return '<div class="bi-month"><div class="d-flex justify-content-between mb-3"><span class="bi-month__title">' + escapeHtml(mes.mes || "") + '</span><span class="badge badge-light-primary">' + Number(mes.total_interes || 0) + '</span></div>' +
        '<div class="fw-bold fs-8 text-muted text-uppercase mb-1">Productos</div>' + renderMini(productos, "nombre") +
        '<div class="fw-bold fs-8 text-muted text-uppercase mt-3 mb-1">Busquedas</div>' + renderMini(busquedas, "termino") +
        '</div>';
    }).join("");
  }

  function renderMini(items, key) {
    if (!items.length) return '<div class="text-muted fs-8">Sin datos</div>';
    return items.map(function (item) {
      return '<div class="d-flex justify-content-between gap-2 fs-8"><span class="text-truncate">' + escapeHtml(item[key] || "") + '</span><strong>' + Number(item.total || 0) + '</strong></div>';
    }).join("");
  }

  function renderDiagnostico(depurar) {
    setFuente(depurar);
    var tablas = get(depurar, ["tablas"], {});
    var rangos = get(depurar, ["rangos"], {});
    var filas = Object.keys(tablas).map(function (tabla) {
      var rango = rangos[tabla] || {};
      var estado = tablas[tabla] ? "Disponible" : "No disponible";
      var total = rango.total == null ? "" : rango.total;
      return { nombre: tabla + " - " + estado, total: total };
    });
    renderList("bi_diagnostico", filas);
  }

  function setFuente(depurar) {
    var node = document.getElementById("bi_fuente");
    if (!node) return;
    var fuente = depurar.fuente || "conexion_activa";
    var base = depurar.base || "";
    node.className = "badge " + (fuente === "bi_legacy_productivo" ? "badge-light-success" : "badge-light-info");
    node.textContent = fuente + (base ? " / " + base : "");
  }

  function jsonResponse(response) {
    if (!response.ok) {
      throw new Error("HTTP " + response.status);
    }
    return response.text().then(function (text) {
      try {
        return JSON.parse(text);
      } catch (error) {
        throw new Error("Respuesta no JSON del servidor");
      }
    });
  }

  function on(id, eventName, callback) {
    var el = document.getElementById(id);
    if (el) el.addEventListener(eventName, callback);
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
    var node = document.getElementById("bi_pub_estado");
    if (!node) return;
    node.className = "badge " + clase;
    node.textContent = texto;
  }

  function toggleEmpty(show) {
    var node = document.getElementById("bi_empty");
    if (node) node.classList.toggle("d-none", !show);
  }

  function get(obj, path, fallback) {
    var current = obj;
    for (var i = 0; i < path.length; i++) {
      if (!current || typeof current !== "object" || !(path[i] in current)) return fallback == null ? {} : fallback;
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
