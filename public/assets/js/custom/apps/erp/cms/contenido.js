/*
 * Documentacion IA: Codex GPT-5, 2026-08-10.
 * Proposito: UX funcional en memoria para el modulo CMS.
 * Impacto: permite armar bloques CMS, ordenarlos y previsualizar JSON sin escribir BD.
 * Contrato: consume endpoints GET internos protegidos; toda edicion es local hasta activar persistencia autorizada.
 */
(function () {
  "use strict";

  var STORAGE_KEY = "erp_ecommerce_cms_preview_local_v1";

  var estado = {
    manifest: null,
    pagina: null,
    slots: [],
    tipos: [],
    slotActivo: "",
    bloqueActivo: "",
    contadorLocal: 1
  };

  document.addEventListener("DOMContentLoaded", function () {
    bindEvents();
    cargarTodo();
  });

  function bindEvents() {
    on("ecom_cms_recargar", "click", cargarTodo);
    on("ecom_cms_preview", "click", cargarPagina);
    on("ecom_cms_pagina", "change", cargarPagina);
    on("ecom_cms_nuevo", "click", nuevoBloque);
    on("ecom_cms_cargar_defaults", "click", cargarPagina);
    on("ecom_cms_limpiar_form", "click", limpiarForm);
    on("ecom_cms_copiar_json", "click", copiarJson);
    on("ecom_cms_exportar_json", "click", exportarJson);
    on("ecom_cms_importar_json", "click", importarJson);
    on("ecom_cms_filtro_estatus", "change", renderBloques);
    on("ecom_cms_validar", "click", validarYRender);
    on("ecom_cms_guardar_local", "click", guardarBorradorLocal);
    on("ecom_cms_cargar_local", "click", cargarBorradorLocal);
    on("ecom_cms_descartar_local", "click", descartarBorradorLocal);

    var form = $("ecom_cms_form");
    if (form) {
      form.addEventListener("submit", function (event) {
        event.preventDefault();
        aplicarForm();
      });
    }

    var slots = $("ecom_cms_slots");
    if (slots) {
      slots.addEventListener("click", function (event) {
        var button = event.target.closest("[data-cms-slot]");
        if (!button) return;
        estado.slotActivo = button.getAttribute("data-cms-slot") || "";
        estado.bloqueActivo = primerBloqueId(estado.slotActivo);
        renderTodoLocal();
      });
    }

    var bloques = $("ecom_cms_bloques");
    if (bloques) {
      bloques.addEventListener("click", function (event) {
        var action = event.target.closest("[data-cms-action]");
        if (!action) return;
        ejecutarAccionBloque(action.getAttribute("data-cms-action"), action.getAttribute("data-cms-id"));
      });
    }
  }

  function cargarTodo() {
    setEstado("Cargando", "badge-light-info");
    return Promise.all([
      getJson("/cms/contenido_admin_estado_erp"),
      getJson("/cms/contenido_admin_manifest_erp")
    ]).then(function (respuestas) {
      renderEstado(respuestas[0]);
      renderContratos(respuestas[0]);
      estado.manifest = respuestas[1].depurar || {};
      estado.tipos = estado.manifest.tipos_bloque || [];
      renderTipos(estado.tipos);
      llenarSelectTipos();
      return cargarPagina();
    }).then(function () {
      setEstado("Listo", "badge-light-success");
    }).catch(function (error) {
      setEstado("Error", "badge-light-danger");
      setText("ecom_cms_json", JSON.stringify({ error: true, mensaje: error.message || String(error) }, null, 2));
    });
  }

  function cargarPagina() {
    var params = new URLSearchParams();
    params.set("pagina", valor("ecom_cms_pagina") || "home");
    params.set("plantilla", valor("ecom_cms_template") || "artiani_default");
    if (valor("ecom_cms_pagina") === "categoria") {
      params.set("categoria", valor("ecom_cms_categoria") || "peces");
    }
    setEstado("Cargando pagina", "badge-light-info");
    return getJson("/cms/contenido_admin_pagina_erp?" + params.toString()).then(function (response) {
      estado.pagina = response.depurar || {};
      estado.slots = normalizarSlots(estado.pagina.slots || []);
      estado.slotActivo = estado.slots[0] ? estado.slots[0].slot : "";
      estado.bloqueActivo = primerBloqueId(estado.slotActivo);
      limpiarForm();
      renderTodoLocal();
      setEstado("Listo", "badge-light-success");
    });
  }

  function renderTodoLocal() {
    if (!estado.bloqueActivo) {
      estado.bloqueActivo = primerBloqueId(estado.slotActivo);
    }
    llenarSelectTipos();
    renderSlots(slotsManifest());
    renderSlotDetalle();
    renderBloques();
    renderResumenEditorial();
    renderPublicabilidadSlots();
    renderValidacion(validarContenido());
    renderJson();
    renderVisual();
    if (estado.bloqueActivo) {
      cargarBloqueEnForm(estado.bloqueActivo);
    }
  }

  function renderEstado(response) {
    var depurar = response.depurar || {};
    var resumen = depurar.resumen || {};
    setText("ecom_cms_plantilla", resumen.plantilla_activa || "artiani_default");
    setText("ecom_cms_slots_total", resumen.slots_total || 0);
    setText("ecom_cms_tipos_total", resumen.tipos_bloque_total || 0);
    setText("ecom_cms_persistencia", depurar.persistencia_real ? "Activa" : "Read-only");
    renderEsquema(depurar.esquema || {});
  }

  function renderContratos(response) {
    var node = $("ecom_cms_contratos");
    if (!node) return;
    var depurar = response.depurar || {};
    var admin = depurar.endpoints_admin || {};
    var publicos = depurar.endpoints_publicos || {};
    var resumen = depurar.resumen || {};
    renderArranque(publicos);
    node.innerHTML = '<div class="row g-4">' +
      '<div class="col-lg-4"><div class="border rounded p-4 h-100"><div class="fw-bold mb-3">Estado del CMS</div>' +
        '<div class="d-flex flex-column gap-2 fs-7">' +
          contratoFila("Modo", depurar.modo || "admin_readonly") +
          contratoFila("Fase", depurar.fase || "cms_readonly") +
          contratoFila("Persistencia", depurar.persistencia_real ? "Activa" : "Read-only") +
          contratoFila("Bloques home", resumen.bloques_default_home || 0) +
        '</div></div></div>' +
      '<div class="col-lg-4"><div class="border rounded p-4 h-100"><div class="fw-bold mb-3">Endpoints internos</div>' +
        contratoLista(admin) +
      '</div></div>' +
      '<div class="col-lg-4"><div class="border rounded p-4 h-100"><div class="fw-bold mb-3">Endpoints publicos futuros</div>' +
        contratoLista(publicos) +
      '</div></div>' +
    '</div>';
  }

  function renderArranque(publicos) {
    var node = $("ecom_cms_arranque");
    if (!node) return;
    var recomendado = publicos && publicos.configuracion_inicial ? publicos.configuracion_inicial : "/ecommercePublico/configuracion_inicial";
    var legacy = publicos && publicos.bootstrap_alias_legacy ? publicos.bootstrap_alias_legacy : "/ecommercePublico/bootstrap";
    node.innerHTML = '<div class="d-flex flex-column gap-4">' +
      '<div><div class="text-muted fs-8 text-uppercase fw-bold mb-1">Endpoint recomendado</div><a class="fw-bold" href="' + escapeAttr(recomendado) + '" target="_blank" rel="noopener">' + escapeHtml(recomendado) + '</a><div class="text-muted fs-8 mt-1">El frontend ecommerce debe iniciar desde esta ruta.</div></div>' +
      '<div><div class="text-muted fs-8 text-uppercase fw-bold mb-1">Alias legacy</div><code>' + escapeHtml(legacy) + '</code><div class="text-muted fs-8 mt-1">Solo compatibilidad temporal; no usar como nombre nuevo.</div></div>' +
    '</div>';
  }

  function contratoFila(label, valorTexto) {
    return '<div class="d-flex justify-content-between gap-3"><span class="text-muted">' + escapeHtml(label) + '</span><span class="fw-semibold text-end">' + escapeHtml(valorTexto) + '</span></div>';
  }

  function contratoLista(endpoints) {
    var keys = Object.keys(endpoints || {});
    if (!keys.length) return '<div class="text-muted fs-7">Sin endpoints declarados.</div>';
    return '<div class="d-flex flex-column gap-2">' + keys.map(function (key) {
      return '<div class="fs-8"><div class="text-muted">' + escapeHtml(key) + '</div><code>' + escapeHtml(endpoints[key]) + '</code></div>';
    }).join("") + '</div>';
  }

  function slotsManifest() {
    var plantillas = estado.manifest && estado.manifest.plantillas ? estado.manifest.plantillas : [];
    return plantillas[0] && Array.isArray(plantillas[0].slots) ? plantillas[0].slots : [];
  }

  function renderSlots(slots) {
    var node = $("ecom_cms_slots");
    if (!node) return;
    if (!slots.length) {
      node.innerHTML = '<div class="text-muted">Sin slots declarados.</div>';
      return;
    }
    node.innerHTML = slots.map(function (slot) {
      var total = bloquesDeSlot(slot.codigo).length;
      return '<button type="button" class="ecom-cms-slot text-start w-100 ' + (estado.slotActivo === slot.codigo ? "is-active" : "") + '" data-cms-slot="' + escapeAttr(slot.codigo) + '">' +
        '<div class="d-flex justify-content-between gap-3"><div><div class="fw-bold">' + escapeHtml(slot.codigo) + '</div><div class="text-muted fs-8">' + escapeHtml(slot.nombre || "") + '</div></div><span class="badge badge-light-primary">' + escapeHtml(slot.pagina || "") + '</span></div>' +
        '<div class="ecom-cms-chip-list mt-3">' + (slot.tipos || []).map(function (tipo) { return '<span class="badge badge-light-info">' + escapeHtml(tipo) + '</span>'; }).join("") + '</div>' +
        '<div class="text-muted fs-8 mt-2">Bloques: ' + escapeHtml(total) + ' / ' + escapeHtml(slot.max_bloques || 0) + (slot.requerido ? " / requerido" : "") + '</div>' +
      '</button>';
    }).join("");
  }

  function renderBloques() {
    var node = $("ecom_cms_bloques");
    if (!node) return;
    setText("ecom_cms_slot_activo_label", estado.slotActivo || "Selecciona un slot");
    var bloques = bloquesDeSlot(estado.slotActivo);
    var filtro = valor("ecom_cms_filtro_estatus");
    var bloquesVisibles = filtro ? bloques.filter(function (bloque) { return normalizarEstatusForm(bloque.estatus) === filtro; }) : bloques;
    if (!bloques.length) {
      node.innerHTML = '<div class="text-muted py-4">Sin bloques en este slot.</div>';
      return;
    }
    if (!bloquesVisibles.length) {
      node.innerHTML = '<div class="text-muted py-4">Sin bloques para el filtro seleccionado.</div>';
      return;
    }
    node.innerHTML = bloquesVisibles.map(function (bloque) {
      var index = bloques.findIndex(function (item) { return item.id === bloque.id; });
      var acciones = accionesBloqueHtml(bloque);
      return '<div class="ecom-cms-block ' + (estado.bloqueActivo === bloque.id ? "border-primary" : "") + '">' +
        '<div class="d-flex justify-content-between gap-3 flex-wrap">' +
          '<div><div class="fw-bold">' + escapeHtml(bloque.titulo || bloque.texto || bloque.id) + '</div><div class="text-muted fs-8">' + escapeHtml(bloque.tipo || "") + ' / orden ' + escapeHtml(index + 1) + '</div></div>' +
          '<div class="ecom-cms-actions">' + acciones + '</div>' +
        '</div>' +
        '<div class="ecom-cms-chip-list mt-3"><span class="badge ' + claseEstatus(bloque.estatus) + '">' + escapeHtml(bloque.estatus || "borrador") + '</span>' + vigenciaBadge(bloque) + '</div>' +
      '</div>';
    }).join("");
  }

  function renderSlotDetalle() {
    var node = $("ecom_cms_slot_detalle");
    if (!node) return;
    var slotDef = getSlotDef(estado.slotActivo) || {};
    var slotData = slotActual() || {};
    var bloques = bloquesDeSlot(estado.slotActivo);
    if (!estado.slotActivo) {
      node.innerHTML = '<div class="text-muted">Selecciona un slot para ver su detalle.</div>';
      return;
    }
    node.innerHTML = '<div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-4">' +
        '<div><div class="text-muted fs-8 text-uppercase fw-bold">Slot seleccionado</div><h3 class="fw-bold mb-1">' + escapeHtml(estado.slotActivo) + '</h3><div class="text-muted fs-7">' + escapeHtml(slotDef.nombre || slotData.nombre || "") + '</div></div>' +
        '<span class="badge ' + (slotDef.requerido ? "badge-light-danger" : "badge-light-secondary") + '">' + (slotDef.requerido ? "Requerido" : "Opcional") + '</span>' +
      '</div>' +
      '<div class="row g-3 mb-4">' +
        detalleMetrica("Pagina", slotDef.pagina || slotData.pagina || "-") +
        detalleMetrica("Maximo", slotDef.max_bloques || 0) +
        detalleMetrica("Bloques preview", bloques.length) +
        detalleMetrica("Contexto", slotData.contexto || "global") +
      '</div>' +
      '<div class="mb-2 fw-semibold">Tipos permitidos</div>' +
      '<div class="ecom-cms-chip-list">' + ((slotDef.tipos || []).map(function (tipo) {
        return '<span class="badge badge-light-info">' + escapeHtml(tipo) + '</span>';
      }).join("") || '<span class="text-muted fs-7">Sin tipos definidos.</span>') + '</div>';
  }

  function detalleMetrica(label, valorTexto) {
    return '<div class="col-sm-6 col-lg-3"><div class="border rounded p-3 h-100"><div class="text-muted fs-8 text-uppercase fw-bold">' + escapeHtml(label) + '</div><div class="fw-bold fs-6">' + escapeHtml(valorTexto) + '</div></div></div>';
  }

  function renderResumenEditorial() {
    var node = $("ecom_cms_resumen_editorial");
    if (!node) return;
    var conteo = resumenEditorial();
    node.innerHTML = '<div class="row g-3">' +
      resumenCaja("Bloques", conteo.total, "Total en preview local") +
      resumenCaja("Publicados", conteo.publicado, "Listos para publicar cuando exista BD") +
      resumenCaja("Borrador", conteo.borrador, "Pendientes de revision") +
      resumenCaja("Pausados", conteo.pausado, "No deberian mostrarse al publicar") +
      resumenCaja("Vigentes", conteo.vigente, "Dentro de rango actual") +
      resumenCaja("Futuros", conteo.futuro, "Programados") +
      resumenCaja("Vencidos", conteo.vencido, "Revisar antes de publicar") +
      resumenCaja("Sin vigencia", conteo.sin_vigencia, "Siempre visibles si se publican") +
    '</div>';
  }

  function resumenEditorial() {
    var conteo = {
      total: 0,
      publicado: 0,
      borrador: 0,
      pausado: 0,
      vigente: 0,
      futuro: 0,
      vencido: 0,
      sin_vigencia: 0
    };
    estado.slots.forEach(function (slot) {
      (slot.bloques || []).forEach(function (bloque) {
        var estatus = normalizarEstatusForm(bloque.estatus);
        var vigencia = estadoVigencia(bloque);
        conteo.total++;
        conteo[estatus] = (conteo[estatus] || 0) + 1;
        conteo[vigencia] = (conteo[vigencia] || 0) + 1;
      });
    });
    return conteo;
  }

  function resumenCaja(label, valorTexto, ayuda) {
    return '<div class="col-sm-6 col-xl-3"><div class="border rounded p-3 h-100"><div class="d-flex justify-content-between gap-3 align-items-start"><div><div class="text-muted fs-8 text-uppercase fw-bold">' + escapeHtml(label) + '</div><div class="fw-bold fs-3">' + escapeHtml(valorTexto) + '</div></div></div><div class="text-muted fs-8 mt-2">' + escapeHtml(ayuda) + '</div></div></div>';
  }

  function estadoVigencia(bloque) {
    var vigencia = bloque && bloque.vigencia ? bloque.vigencia : {};
    if (!vigencia.desde && !vigencia.hasta) return "sin_vigencia";
    var ahora = new Date();
    var desde = vigencia.desde ? new Date(vigencia.desde) : null;
    var hasta = vigencia.hasta ? new Date(vigencia.hasta) : null;
    if (desde && !isNaN(desde.getTime()) && desde > ahora) return "futuro";
    if (hasta && !isNaN(hasta.getTime()) && hasta < ahora) return "vencido";
    return "vigente";
  }

  function renderPublicabilidadSlots() {
    var node = $("ecom_cms_publicabilidad_slots");
    if (!node) return;
    var defs = slotsManifest();
    if (!defs.length) {
      node.innerHTML = '<div class="text-muted">Sin slots para evaluar.</div>';
      return;
    }
    node.innerHTML = defs.map(function (slotDef) {
      var revision = revisarSlot(slotDef);
      return '<div class="ecom-cms-slot-status">' +
        '<div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">' +
          '<div><div class="fw-bold">' + escapeHtml(slotDef.codigo) + '</div><div class="text-muted fs-8">' + escapeHtml(slotDef.nombre || "") + '</div></div>' +
          '<span class="badge ' + revision.clase + '">' + escapeHtml(revision.estado) + '</span>' +
        '</div>' +
        '<div class="d-flex flex-wrap gap-2 mt-3 fs-8">' +
          '<span class="badge badge-light-secondary">Bloques: ' + escapeHtml(revision.bloques) + '</span>' +
          '<span class="badge badge-light-secondary">Max: ' + escapeHtml(slotDef.max_bloques || 0) + '</span>' +
          (slotDef.requerido ? '<span class="badge badge-light-danger">Requerido</span>' : '<span class="badge badge-light">Opcional</span>') +
        '</div>' +
        (revision.mensajes.length ? '<div class="mt-3 text-muted fs-8">' + revision.mensajes.map(escapeHtml).join("<br>") + '</div>' : '') +
      '</div>';
    }).join("");
  }

  function revisarSlot(slotDef) {
    var bloques = bloquesDeSlot(slotDef.codigo);
    var mensajes = [];
    var errores = [];
    var alertas = [];
    var max = Number(slotDef.max_bloques || 0);
    if (slotDef.requerido && bloques.length === 0) {
      errores.push("Requiere al menos un bloque.");
    }
    if (max > 0 && bloques.length > max) {
      errores.push("Excede el maximo permitido.");
    }
    bloques.forEach(function (bloque, index) {
      validarBloque(slotDef, bloque, index + 1, errores, alertas);
    });
    if (errores.length) {
      mensajes = errores.slice(0, 3);
      return { estado: "No publicable", clase: "badge-light-danger", bloques: bloques.length, mensajes: mensajes };
    }
    if (alertas.length) {
      mensajes = alertas.slice(0, 3);
      return { estado: "Con alertas", clase: "badge-light-warning", bloques: bloques.length, mensajes: mensajes };
    }
    if (!bloques.length) {
      return { estado: slotDef.requerido ? "Incompleto" : "Vacio opcional", clase: slotDef.requerido ? "badge-light-danger" : "badge-light-secondary", bloques: 0, mensajes: [] };
    }
    return { estado: "Publicable", clase: "badge-light-success", bloques: bloques.length, mensajes: [] };
  }

  function accionesBloqueHtml(bloque) {
    var id = escapeAttr(bloque.id);
    var modo = document.body ? document.body.getAttribute("data-cms-bloques-mode") : "";
    if (modo === "seleccion") {
      return '<button class="btn btn-sm btn-light-primary" type="button" data-cms-action="editar" data-cms-id="' + id + '"><i class="bi bi-eye"></i> Ver</button>';
    }
    return '<button class="btn btn-sm btn-light-primary" type="button" data-cms-action="editar" data-cms-id="' + id + '"><i class="bi bi-pencil"></i></button>' +
      '<button class="btn btn-sm btn-light-info" type="button" data-cms-action="duplicar" data-cms-id="' + id + '"><i class="bi bi-copy"></i></button>' +
      '<button class="btn btn-sm btn-light" type="button" data-cms-action="subir" data-cms-id="' + id + '"><i class="bi bi-arrow-up"></i></button>' +
      '<button class="btn btn-sm btn-light" type="button" data-cms-action="bajar" data-cms-id="' + id + '"><i class="bi bi-arrow-down"></i></button>' +
      '<button class="btn btn-sm btn-light-warning" type="button" data-cms-action="pausar" data-cms-id="' + id + '"><i class="bi bi-pause"></i></button>' +
      '<button class="btn btn-sm btn-light-danger" type="button" data-cms-action="quitar" data-cms-id="' + id + '"><i class="bi bi-trash"></i></button>';
  }

  function renderTipos(tipos) {
    var node = $("ecom_cms_tipos");
    if (!node) return;
    if (!tipos.length) {
      node.innerHTML = '<div class="text-muted">Sin tipos declarados.</div>';
      return;
    }
    node.innerHTML = '<div class="table-responsive"><table class="table table-row-dashed fs-7 gy-3 mb-0"><tbody>' + tipos.map(function (tipo) {
      return '<tr><td><span class="fw-semibold">' + escapeHtml(tipo.tipo || "") + '</span><div class="text-muted fs-8">' + escapeHtml(tipo.uso || "") + '</div></td><td class="text-end">' + escapeHtml((tipo.campos || []).length) + ' campos</td></tr>';
    }).join("") + '</tbody></table></div>';
  }

  function renderEsquema(esquema) {
    var node = $("ecom_cms_esquema");
    if (!node) return;
    var auditoria = esquema.auditoria || {};
    var plan = esquema.plan || {};
    var tablas = auditoria.auditoria || {};
    var rows = Object.keys(tablas).map(function (tabla) {
      var existe = tablas[tabla] && tablas[tabla].existe;
      return '<tr><td class="fw-semibold">' + escapeHtml(tabla) + '</td><td class="text-end"><span class="badge ' + (existe ? "badge-light-success" : "badge-light-warning") + '">' + (existe ? "Existe" : "Pendiente") + '</span></td></tr>';
    }).join("");
    node.innerHTML = '<div class="mb-3"><span class="badge badge-light-info">DDL propuesto: ' + escapeHtml(plan.ddl_total || 0) + '</span> <span class="badge badge-light-warning">No ejecutado</span></div>' +
      '<div class="table-responsive"><table class="table table-row-dashed fs-8 gy-2 mb-0"><tbody>' + rows + '</tbody></table></div>';
  }

  function renderJson() {
    var modo = document.body ? document.body.getAttribute("data-cms-json-mode") : "";
    var salida = modo === "manifest" ? construirManifestPreview() : construirPreview();
    setText("ecom_cms_json", JSON.stringify(salida, null, 2));
  }

  function renderValidacion(resultado) {
    var node = $("ecom_cms_validacion");
    var resumen = $("ecom_cms_validacion_resumen");
    if (!node) return;
    resultado = resultado || validarContenido();
    if (resumen) {
      var clase = resultado.errores.length ? "badge-light-danger" : (resultado.alertas.length ? "badge-light-warning" : "badge-light-success");
      resumen.className = "badge " + clase;
      resumen.textContent = resultado.errores.length + " errores / " + resultado.alertas.length + " alertas";
    }
    var items = resultado.errores.map(function (item) {
      return { tipo: "danger", texto: item };
    }).concat(resultado.alertas.map(function (item) {
      return { tipo: "warning", texto: item };
    }));
    if (!items.length) {
      node.innerHTML = '<div class="ecom-cms-validation__item text-success fw-semibold"><i class="bi bi-check2-circle"></i> Preview local listo para revision.</div>';
      return;
    }
    node.innerHTML = items.map(function (item) {
      return '<div class="ecom-cms-validation__item text-' + item.tipo + '"><i class="bi bi-exclamation-triangle"></i> ' + escapeHtml(item.texto) + '</div>';
    }).join("");
  }

  function validarYRender() {
    var resultado = validarContenido();
    renderValidacion(resultado);
    setEstado(resultado.errores.length ? "Con errores" : (resultado.alertas.length ? "Con alertas" : "Valido"), resultado.errores.length ? "badge-light-danger" : (resultado.alertas.length ? "badge-light-warning" : "badge-light-success"));
  }

  function validarContenido() {
    var errores = [];
    var alertas = [];
    var defs = slotsManifest();
    defs.forEach(function (slotDef) {
      var bloques = bloquesDeSlot(slotDef.codigo);
      var max = Number(slotDef.max_bloques || 0);
      if (slotDef.requerido && bloques.length === 0) {
        errores.push(slotDef.codigo + ": requiere al menos un bloque.");
      }
      if (max > 0 && bloques.length > max) {
        errores.push(slotDef.codigo + ": excede maximo de " + max + " bloques.");
      }
      bloques.forEach(function (bloque, index) {
        validarBloque(slotDef, bloque, index + 1, errores, alertas);
      });
    });
    return { errores: errores, alertas: alertas };
  }

  function validarBloque(slotDef, bloque, orden, errores, alertas) {
    var prefijo = slotDef.codigo + " #" + orden + " (" + (bloque.tipo || "sin_tipo") + ")";
    if ((slotDef.tipos || []).indexOf(bloque.tipo) < 0) {
      errores.push(prefijo + ": tipo no permitido en este slot.");
    }
    if (!String(bloque.titulo || bloque.texto || "").trim()) {
      errores.push(prefijo + ": falta titulo o texto principal.");
    }
    if (bloque.tipo === "hero_banner" || bloque.tipo === "category_banner") {
      if (!valorBloqueMedia(bloque, "alt")) {
        errores.push(prefijo + ": falta alt text de imagen.");
      }
      if (!valorBloqueMedia(bloque, "imagen_desktop")) {
        alertas.push(prefijo + ": falta imagen desktop real.");
      }
      if (!valorBloqueMedia(bloque, "imagen_mobile")) {
        alertas.push(prefijo + ": falta imagen mobile.");
      }
    }
    if (bloque.tipo === "product_collection" && (!bloque.source || !bloque.source.endpoint)) {
      errores.push(prefijo + ": falta endpoint source.");
    }
    if (bloque.tipo === "content_html_safe" && /<script[\s>]/i.test(String(bloque.contenido_html || ""))) {
      errores.push(prefijo + ": contiene script y debe sanitizarse.");
    }
    if (bloque.vigencia && bloque.vigencia.desde && bloque.vigencia.hasta && bloque.vigencia.desde > bloque.vigencia.hasta) {
      errores.push(prefijo + ": vigencia hasta es menor que vigencia desde.");
    }
    if (normalizarEstatusForm(bloque.estatus) !== "publicado") {
      alertas.push(prefijo + ": no esta publicado.");
    }
  }

  function renderVisual() {
    var node = $("ecom_cms_visual");
    if (!node) return;
    var bloque = buscarBloque(estado.bloqueActivo) || bloquesDeSlot(estado.slotActivo)[0];
    if (!bloque) {
      node.innerHTML = '<div class="text-muted py-4">Sin bloque seleccionado.</div>';
      return;
    }
    var img = valorBloqueMedia(bloque, "imagen_desktop");
    var media = img ? '<img class="ecom-cms-preview-img mb-4" src="' + escapeAttr(img) + '" alt="' + escapeAttr(valorBloqueMedia(bloque, "alt")) + '">' : '<div class="ecom-cms-preview-img d-flex align-items-center justify-content-center text-muted mb-4">Sin imagen</div>';
    var cta = bloque.cta && bloque.cta.label ? '<a class="btn btn-sm btn-primary" href="javascript:void(0)">' + escapeHtml(bloque.cta.label) + '</a>' : "";
    var extra = "";
    if (bloque.tipo === "product_collection" && bloque.source) {
      extra = '<div class="text-muted fs-8 mt-2">' + escapeHtml(bloque.source.endpoint || "") + '</div>';
    }
    if (bloque.tipo === "image_card_grid" && Array.isArray(bloque.items)) {
      extra = '<div class="row g-2 mt-2">' + bloque.items.map(function (item) {
        return '<div class="col-6"><div class="border rounded p-2 fs-8">' + escapeHtml(item.titulo || "") + '</div></div>';
      }).join("") + '</div>';
    }
    node.innerHTML = media + '<h4 class="fw-bold mb-2">' + escapeHtml(bloque.titulo || bloque.texto || bloque.id) + '</h4>' +
      '<div class="text-muted mb-3">' + escapeHtml(bloque.subtitulo || bloque.contenido_html || bloque.texto || "") + '</div>' + cta + extra;
  }

  function construirManifestPreview() {
    return {
      fuente: "manifest_preview_panel",
      persistencia_real: false,
      plantilla_activa: estado.manifest && estado.manifest.plantilla_activa ? estado.manifest.plantilla_activa : "artiani_default",
      plantillas: estado.manifest && estado.manifest.plantillas ? estado.manifest.plantillas : [],
      tipos_bloque: estado.tipos || [],
      guardrails: {
        read_only: true,
        no_escribe_bd: true,
        no_ejecuta_ddl: true,
        frontend_consume_api_publica: true
      }
    };
  }

  function construirPreview() {
    var pagina = clone(estado.pagina || {});
    pagina.fuente = "preview_local_panel";
    pagina.editable_desde_panel = true;
    pagina.persistencia_real = false;
    pagina.slots = estado.slots.map(function (slot) {
      var copia = clone(slot);
      copia.bloques = (copia.bloques || []).map(function (bloque, index) {
        bloque.orden = index + 1;
        return bloque;
      });
      return copia;
    });
    pagina.resumen = pagina.resumen || {};
    pagina.resumen.slots_total = pagina.slots.length;
    pagina.resumen.bloques_total = pagina.slots.reduce(function (total, slot) { return total + (slot.bloques || []).length; }, 0);
    pagina.guardrails = Object.assign({}, pagina.guardrails || {}, {
      preview_local: true,
      no_escribe_bd: true,
      no_ejecuta_ddl: true,
      requiere_persistencia_para_api_publica: true
    });
    return pagina;
  }

  function nuevoBloque() {
    if (!estado.slotActivo) {
      setEstado("Elige slot", "badge-light-warning");
      return;
    }
    var slotDef = slotsManifest().filter(function (slot) { return slot.codigo === estado.slotActivo; })[0] || {};
    var bloques = bloquesDeSlot(estado.slotActivo);
    var max = Number(slotDef.max_bloques || 0);
    if (max > 0 && bloques.length >= max) {
      setEstado("Slot lleno", "badge-light-warning");
      return;
    }
    var tipo = (slotDef.tipos || [])[0] || "promo_strip";
    var bloque = crearBloqueBase(tipo);
    bloques.push(bloque);
    estado.bloqueActivo = bloque.id;
    renderTodoLocal();
    setEstado("Bloque local", "badge-light-success");
  }

  function crearBloqueBase(tipo) {
    return {
      id: "local-" + Date.now() + "-" + estado.contadorLocal++,
      tipo: tipo,
      estatus: "borrador",
      titulo: etiquetaTipo(tipo),
      subtitulo: "",
      media: { imagen_desktop: "", imagen_mobile: "", alt: "" },
      cta: { label: "", url: "" },
      source: tipo === "product_collection" ? { tipo: "catalogo_dinamico", endpoint: "/ecommercePublico/catalogo?limite=8", metodo: "GET" } : undefined,
      limite: tipo === "product_collection" ? 8 : undefined,
      vigencia: { desde: "", hasta: "" },
      guardrails: { local_preview: true }
    };
  }

  function aplicarForm() {
    var id = valor("ecom_cms_block_id");
    var bloque = id ? buscarBloque(id) : null;
    if (!bloque) {
      bloque = crearBloqueBase(valor("ecom_cms_block_tipo") || "promo_strip");
      bloquesDeSlot(estado.slotActivo).push(bloque);
    }
    bloque.tipo = valor("ecom_cms_block_tipo") || bloque.tipo;
    bloque.estatus = valor("ecom_cms_block_estatus") || "borrador";
    bloque.titulo = valor("ecom_cms_block_titulo");
    bloque.subtitulo = valor("ecom_cms_block_subtitulo");
    bloque.texto = bloque.tipo === "promo_strip" ? valor("ecom_cms_block_subtitulo") : bloque.texto;
    bloque.media = {
      imagen_desktop: valor("ecom_cms_block_img_desktop"),
      imagen_mobile: valor("ecom_cms_block_img_mobile"),
      alt: valor("ecom_cms_block_alt")
    };
    bloque.cta = {
      label: valor("ecom_cms_block_cta_label"),
      url: valor("ecom_cms_block_cta_url")
    };
    bloque.vigencia = {
      desde: valor("ecom_cms_block_desde"),
      hasta: valor("ecom_cms_block_hasta")
    };
    aplicarPayloadPorTipo(bloque);
    estado.bloqueActivo = bloque.id;
    renderTodoLocal();
    setEstado("Preview actualizado", "badge-light-success");
  }

  function aplicarPayloadPorTipo(bloque) {
    var payload = valor("ecom_cms_block_payload");
    if (bloque.tipo === "content_html_safe") {
      bloque.contenido_html = payload;
      return;
    }
    if (bloque.tipo === "image_card_grid") {
      bloque.items = parseJsonSeguro(payload, []);
      return;
    }
    if (bloque.tipo === "product_collection") {
      bloque.source = { tipo: "catalogo_dinamico", endpoint: valor("ecom_cms_block_source") || "/ecommercePublico/catalogo?limite=8", metodo: "GET" };
      return;
    }
    bloque.payload_local = payload;
  }

  function cargarBloqueEnForm(id) {
    var bloque = buscarBloque(id);
    if (!bloque) return;
    llenarSelectTipos(bloque.tipo || "");
    setValue("ecom_cms_block_id", bloque.id);
    setValue("ecom_cms_block_tipo", bloque.tipo || "");
    setValue("ecom_cms_block_estatus", normalizarEstatusForm(bloque.estatus));
    setValue("ecom_cms_block_titulo", bloque.titulo || "");
    setValue("ecom_cms_block_subtitulo", bloque.subtitulo || bloque.texto || "");
    setValue("ecom_cms_block_cta_label", bloque.cta && bloque.cta.label ? bloque.cta.label : "");
    setValue("ecom_cms_block_cta_url", bloque.cta && bloque.cta.url ? bloque.cta.url : "");
    setValue("ecom_cms_block_img_desktop", valorBloqueMedia(bloque, "imagen_desktop"));
    setValue("ecom_cms_block_img_mobile", valorBloqueMedia(bloque, "imagen_mobile"));
    setValue("ecom_cms_block_alt", valorBloqueMedia(bloque, "alt"));
    setValue("ecom_cms_block_source", bloque.source && bloque.source.endpoint ? bloque.source.endpoint : "");
    setValue("ecom_cms_block_desde", bloque.vigencia && bloque.vigencia.desde ? bloque.vigencia.desde : "");
    setValue("ecom_cms_block_hasta", bloque.vigencia && bloque.vigencia.hasta ? bloque.vigencia.hasta : "");
    setValue("ecom_cms_block_payload", payloadParaForm(bloque));
    setText("ecom_cms_editor_tipo", bloque.tipo || "-");
    setText("ecom_cms_editor_modo", bloque.id.indexOf("local-") === 0 ? "Bloque local nuevo" : "Bloque default editable en preview");
  }

  function limpiarForm() {
    ["ecom_cms_block_id", "ecom_cms_block_titulo", "ecom_cms_block_subtitulo", "ecom_cms_block_cta_label", "ecom_cms_block_cta_url", "ecom_cms_block_img_desktop", "ecom_cms_block_img_mobile", "ecom_cms_block_alt", "ecom_cms_block_source", "ecom_cms_block_desde", "ecom_cms_block_hasta", "ecom_cms_block_payload"].forEach(function (id) {
      setValue(id, "");
    });
    setValue("ecom_cms_block_estatus", "borrador");
    var slotDef = slotsManifest().filter(function (slot) { return slot.codigo === estado.slotActivo; })[0] || {};
    setValue("ecom_cms_block_tipo", (slotDef.tipos || [])[0] || "promo_strip");
    setText("ecom_cms_editor_tipo", "-");
  }

  function ejecutarAccionBloque(accion, id) {
    var slot = slotActual();
    if (!slot) return;
    var bloques = slot.bloques || [];
    var index = bloques.findIndex(function (bloque) { return bloque.id === id; });
    if (index < 0) return;
    if (accion === "editar") {
      estado.bloqueActivo = id;
    }
    if (accion === "subir" && index > 0) {
      bloques.splice(index - 1, 0, bloques.splice(index, 1)[0]);
    }
    if (accion === "bajar" && index < bloques.length - 1) {
      bloques.splice(index + 1, 0, bloques.splice(index, 1)[0]);
    }
    if (accion === "duplicar") {
      var duplicado = clone(bloques[index]);
      duplicado.id = "local-" + Date.now() + "-" + estado.contadorLocal++;
      duplicado.estatus = "borrador";
      duplicado.titulo = (duplicado.titulo || duplicado.id) + " copia";
      bloques.splice(index + 1, 0, duplicado);
      estado.bloqueActivo = duplicado.id;
    }
    if (accion === "pausar") {
      bloques[index].estatus = normalizarEstatusForm(bloques[index].estatus) === "pausado" ? "borrador" : "pausado";
    }
    if (accion === "quitar") {
      bloques.splice(index, 1);
      estado.bloqueActivo = bloques[0] ? bloques[0].id : "";
    }
    renderTodoLocal();
  }

  function normalizarSlots(slots) {
    return clone(slots).map(function (slot) {
      slot.bloques = (slot.bloques || []).map(function (bloque) {
        bloque.id = String(bloque.id || ("default-" + Math.random()));
        bloque.estatus = normalizarEstatusForm(bloque.estatus || "borrador");
        return bloque;
      });
      return slot;
    });
  }

  function bloquesDeSlot(codigo) {
    var slot = estado.slots.filter(function (item) { return item.slot === codigo; })[0];
    if (!slot) return [];
    if (!Array.isArray(slot.bloques)) slot.bloques = [];
    return slot.bloques;
  }

  function slotActual() {
    return estado.slots.filter(function (item) { return item.slot === estado.slotActivo; })[0] || null;
  }

  function buscarBloque(id) {
    var encontrado = null;
    estado.slots.forEach(function (slot) {
      (slot.bloques || []).forEach(function (bloque) {
        if (bloque.id === id) encontrado = bloque;
      });
    });
    return encontrado;
  }

  function primerBloqueId(slot) {
    var bloques = bloquesDeSlot(slot);
    return bloques[0] ? bloques[0].id : "";
  }

  function getSlotDef(codigo) {
    return slotsManifest().filter(function (slot) { return slot.codigo === codigo; })[0] || null;
  }

  function llenarSelectTipos(tipoActual) {
    var select = $("ecom_cms_block_tipo");
    if (!select) return;
    var slotDef = getSlotDef(estado.slotActivo);
    var permitidos = slotDef && Array.isArray(slotDef.tipos) && slotDef.tipos.length ? slotDef.tipos : estado.tipos.map(function (tipo) { return tipo.tipo; });
    if (tipoActual && permitidos.indexOf(tipoActual) < 0) {
      permitidos = permitidos.concat([tipoActual]);
    }
    select.innerHTML = permitidos.map(function (codigo) {
      var tipo = estado.tipos.filter(function (item) { return item.tipo === codigo; })[0] || { tipo: codigo };
      return '<option value="' + escapeAttr(tipo.tipo) + '">' + escapeHtml(tipo.tipo) + '</option>';
    }).join("");
  }

  function copiarJson() {
    var texto = $("ecom_cms_json") ? $("ecom_cms_json").textContent : "";
    if (!texto) return;
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(texto).then(function () {
        setEstado("JSON copiado", "badge-light-success");
      });
      return;
    }
    setEstado("Copia manual", "badge-light-warning");
  }

  function exportarJson() {
    var json = JSON.stringify(construirPreview(), null, 2);
    var blob = new Blob([json], { type: "application/json;charset=utf-8" });
    var url = URL.createObjectURL(blob);
    var link = document.createElement("a");
    var pagina = valor("ecom_cms_pagina") || "home";
    link.href = url;
    link.download = "cms-ecommerce-" + pagina + "-preview.json";
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
    setEstado("JSON exportado", "badge-light-success");
  }

  function importarJson() {
    var raw = valor("ecom_cms_import_json");
    if (!raw) {
      setEstado("Pega JSON", "badge-light-warning");
      return;
    }
    try {
      var data = JSON.parse(raw);
      var payload = data.depurar && data.depurar.slots ? data.depurar : data;
      if (!Array.isArray(payload.slots)) {
        setEstado("JSON sin slots", "badge-light-danger");
        return;
      }
      estado.pagina = payload;
      estado.slots = normalizarSlots(payload.slots);
      estado.slotActivo = estado.slots[0] ? estado.slots[0].slot : "";
      estado.bloqueActivo = primerBloqueId(estado.slotActivo);
      renderTodoLocal();
      setEstado("JSON importado", "badge-light-success");
    } catch (error) {
      setEstado("JSON invalido", "badge-light-danger");
    }
  }

  function guardarBorradorLocal() {
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify({
        pagina: estado.pagina,
        slots: estado.slots,
        slotActivo: estado.slotActivo,
        bloqueActivo: estado.bloqueActivo,
        contadorLocal: estado.contadorLocal,
        guardado_en: new Date().toISOString()
      }));
      setEstado("Borrador local guardado", "badge-light-success");
    } catch (error) {
      setEstado("No se pudo guardar local", "badge-light-danger");
    }
  }

  function cargarBorradorLocal() {
    try {
      var raw = localStorage.getItem(STORAGE_KEY);
      if (!raw) {
        setEstado("Sin borrador local", "badge-light-warning");
        return;
      }
      var borrador = JSON.parse(raw);
      estado.pagina = borrador.pagina || estado.pagina || {};
      estado.slots = Array.isArray(borrador.slots) ? borrador.slots : estado.slots;
      estado.slotActivo = borrador.slotActivo || (estado.slots[0] ? estado.slots[0].slot : "");
      estado.bloqueActivo = borrador.bloqueActivo || primerBloqueId(estado.slotActivo);
      estado.contadorLocal = Number(borrador.contadorLocal || estado.contadorLocal || 1);
      renderTodoLocal();
      setEstado("Borrador local cargado", "badge-light-success");
    } catch (error) {
      setEstado("Borrador local invalido", "badge-light-danger");
    }
  }

  function descartarBorradorLocal() {
    localStorage.removeItem(STORAGE_KEY);
    setEstado("Borrador local descartado", "badge-light-warning");
  }

  function payloadParaForm(bloque) {
    if (bloque.tipo === "content_html_safe") return bloque.contenido_html || "";
    if (bloque.tipo === "image_card_grid") return JSON.stringify(bloque.items || [], null, 2);
    return bloque.payload_local || "";
  }

  function valorBloqueMedia(bloque, key) {
    if (!bloque || !bloque.media) return "";
    return bloque.media[key] || "";
  }

  function parseJsonSeguro(texto, fallback) {
    if (!texto) return fallback;
    try {
      var parsed = JSON.parse(texto);
      return Array.isArray(parsed) ? parsed : fallback;
    } catch (e) {
      setEstado("JSON invalido", "badge-light-warning");
      return fallback;
    }
  }

  function normalizarEstatusForm(estatus) {
    estatus = String(estatus || "borrador").replace("_default", "");
    return ["borrador", "publicado", "pausado"].indexOf(estatus) >= 0 ? estatus : "borrador";
  }

  function claseEstatus(estatus) {
    estatus = normalizarEstatusForm(estatus);
    if (estatus === "publicado") return "badge-light-success";
    if (estatus === "pausado") return "badge-light-warning";
    return "badge-light-secondary";
  }

  function vigenciaBadge(bloque) {
    var vigencia = bloque.vigencia || {};
    if (!vigencia.desde && !vigencia.hasta) return "";
    return '<span class="badge badge-light-info">' + escapeHtml((vigencia.desde || "sin inicio") + " / " + (vigencia.hasta || "sin fin")) + '</span>';
  }

  function etiquetaTipo(tipo) {
    var match = estado.tipos.filter(function (item) { return item.tipo === tipo; })[0];
    return match ? match.nombre : tipo;
  }

  function getJson(url) {
    return fetch(url, { credentials: "same-origin", headers: { "Accept": "application/json" } }).then(function (response) {
      return response.json();
    });
  }

  function on(id, eventName, callback) {
    var node = $(id);
    if (node) node.addEventListener(eventName, callback);
  }

  function $(id) { return document.getElementById(id); }

  function valor(id) {
    var node = $(id);
    return node ? String(node.value || "").trim() : "";
  }

  function setValue(id, value) {
    var node = $(id);
    if (node) node.value = value == null ? "" : String(value);
  }

  function setText(id, value) {
    var node = $(id);
    if (node) node.textContent = String(value == null ? "" : value);
  }

  function setEstado(texto, clase) {
    var node = $("ecom_cms_estado");
    if (!node) return;
    node.className = "badge " + (clase || "badge-light-primary");
    node.textContent = texto;
  }

  function clone(value) {
    return JSON.parse(JSON.stringify(value || {}));
  }

  function escapeHtml(value) {
    var div = document.createElement("div");
    div.textContent = value == null ? "" : String(value);
    return div.innerHTML;
  }

  function escapeAttr(value) {
    return escapeHtml(value).replace(/"/g, "&quot;");
  }
})();
