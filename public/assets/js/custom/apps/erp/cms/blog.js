/**
 * IA: Codex GPT-5 | Fecha: 2026-09-11
 * Proposito: operar el editor inicial del submodulo CMS Blog.
 * Impacto: UI CMS Blog; lista publicaciones, guarda borradores y cambia estatus con CSRF.
 * Contrato: usa endpoints /cms/blog_*; no ejecuta DDL ni toca catalogo, precios o inventario.
 */
(function () {
  "use strict";

  var els = {};
  var ultimaLista = [];

  function $(id) {
    return document.getElementById(id);
  }

  function escapeHtml(valor) {
    return String(valor === null || valor === undefined ? "" : valor)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }

  function pretty(datos) {
    try {
      return JSON.stringify(datos, null, 2);
    } catch (e) {
      return String(datos || "");
    }
  }

  function setSalida(datos) {
    if (els.salida) {
      els.salida.textContent = typeof datos === "string" ? datos : pretty(datos);
    }
  }

  function slugify(valor) {
    return String(valor || "")
      .toLowerCase()
      .normalize("NFD")
      .replace(/[\u0300-\u036f]/g, "")
      .replace(/[^a-z0-9]+/g, "-")
      .replace(/^-+|-+$/g, "");
  }

  function fechaParaInput(fecha) {
    if (!fecha) return "";
    var valor = String(fecha).replace(" ", "T");
    return valor.slice(0, 16);
  }

  function renderEstado(respuesta) {
    var depurar = respuesta && respuesta.depurar ? respuesta.depurar : {};
    var auditoria = depurar.esquema && depurar.esquema.auditoria ? depurar.esquema.auditoria : {};
    var faltantes = auditoria.tablas_faltantes || 0;
    var total = auditoria.tablas_total || 0;
    if (els.resumen) {
      els.resumen.innerHTML = '<span class="badge badge-light-' + (faltantes > 0 ? "warning" : "success") + '">' +
        (faltantes > 0 ? "Pendiente DDL" : "Listo") + "</span>" +
        '<div class="mt-3">Tablas: ' + (total - faltantes) + " disponibles de " + total + ".</div>";
    }
    setSalida(respuesta);
  }

  function cargarEstado() {
    if (els.resumen) els.resumen.textContent = "Consultando...";
    fetch("/cms/blog_admin_estado_erp", { credentials: "same-origin" })
      .then(function (resp) { return resp.json(); })
      .then(renderEstado)
      .catch(function (error) {
        renderEstado({ error: true, tipo: "danger", mensaje: error.message || "No fue posible consultar el estado" });
      });
  }

  function renderLista(respuesta) {
    var depurar = respuesta && respuesta.depurar ? respuesta.depurar : {};
    ultimaLista = depurar.items || [];
    if (!els.lista) return;
    if (!ultimaLista.length) {
      els.lista.innerHTML = '<div class="text-muted">No hay publicaciones para estos filtros.</div>';
      setSalida(respuesta);
      return;
    }
    els.lista.innerHTML = ultimaLista.map(function (item) {
      return '<div class="cms-blog-item mb-2" data-id="' + Number(item.id || 0) + '">' +
        '<div class="d-flex justify-content-between gap-3">' +
        '<div class="fw-bold text-gray-900">' + escapeHtml(item.titulo || "Sin titulo") + "</div>" +
        '<span class="badge badge-light-' + (item.estado === "publicado" ? "success" : (item.estado === "pausado" ? "warning" : "primary")) + '">' + escapeHtml(item.estado || "") + "</span>" +
        "</div>" +
        '<div class="text-muted fs-8 mt-1">' + escapeHtml(item.tipo || "") + " · " + escapeHtml(item.url || "") + "</div>" +
        '<div class="text-muted fs-8 mt-1">' + escapeHtml(item.extracto || "") + "</div>" +
      "</div>";
    }).join("");
    setSalida(respuesta);
  }

  function listar() {
    if (els.lista) els.lista.textContent = "Cargando...";
    var params = new URLSearchParams();
    params.set("limite", "30");
    if (els.buscar && els.buscar.value.trim()) params.set("q", els.buscar.value.trim());
    if (els.filtroEstado && els.filtroEstado.value) params.set("estado", els.filtroEstado.value);
    fetch("/cms/blog_admin_listar_erp?" + params.toString(), { credentials: "same-origin" })
      .then(function (resp) { return resp.json(); })
      .then(renderLista)
      .catch(function (error) {
        if (els.lista) els.lista.textContent = "No fue posible listar.";
        setSalida(error.message || error);
      });
  }

  function limpiarForm() {
    els.id.value = "";
    els.tipo.value = "articulo";
    els.estado.value = "borrador";
    els.titulo.value = "";
    els.slug.value = "";
    els.autor.value = "Artiani";
    els.fecha.value = "";
    els.portadaUrl.value = "";
    els.portadaAlt.value = "";
    els.extracto.value = "";
    els.contenido.value = "";
    els.seoTitle.value = "";
    els.seoDescription.value = "";
    els.videosJson.value = "";
    els.productosJson.value = "";
    els.categoriasJson.value = "";
    els.imagenesJson.value = "";
    els.bloquesJson.value = "";
  }

  function cargarItem(id) {
    fetch("/cms/blog_admin_consultar_erp?id_blog_publicacion=" + encodeURIComponent(id), { credentials: "same-origin" })
      .then(function (resp) { return resp.json(); })
      .then(function (json) {
        var pub = json && json.depurar ? json.depurar.publicacion : null;
        if (!pub) {
          setSalida(json);
          return;
        }
        var relaciones = json && json.depurar && json.depurar.relaciones ? json.depurar.relaciones : {};
        els.id.value = pub.id || "";
        els.tipo.value = pub.tipo || "articulo";
        els.estado.value = pub.estado === "pausado" ? "pausado" : "borrador";
        els.titulo.value = pub.titulo || "";
        els.slug.value = pub.slug || "";
        els.autor.value = pub.autor || "Artiani";
        els.fecha.value = fechaParaInput(pub.fecha_publicacion || "");
        els.portadaUrl.value = pub.imagen_portada && pub.imagen_portada.url ? pub.imagen_portada.url : "";
        els.portadaAlt.value = pub.imagen_portada && (pub.imagen_portada.alt || pub.imagen_portada.alt_text) ? (pub.imagen_portada.alt || pub.imagen_portada.alt_text) : "";
        els.extracto.value = pub.extracto || "";
        els.contenido.value = pub.contenido_html || "";
        els.seoTitle.value = pub.seo && pub.seo.title ? pub.seo.title : "";
        els.seoDescription.value = pub.seo && pub.seo.description ? pub.seo.description : "";
        els.videosJson.value = pretty(relaciones.videos || []);
        els.productosJson.value = pretty(relaciones.productos || []);
        els.categoriasJson.value = pretty(relaciones.categorias || []);
        els.imagenesJson.value = pretty(relaciones.imagenes || []);
        els.bloquesJson.value = pretty(relaciones.bloques_interactivos || []);
        setSalida(json);
      })
      .catch(function (error) { setSalida(error.message || error); });
  }

  function formDataPublicacion() {
    var errorJson = validarJsonAvanzado();
    if (errorJson) {
      setSalida(errorJson);
      return null;
    }
    var form = new FormData();
    form.append("_csrf", window.ERP_CSRF_TOKEN || "");
    if (els.id.value) form.append("id_blog_publicacion", els.id.value);
    form.append("tipo", els.tipo.value);
    form.append("estado", els.estado.value);
    form.append("titulo", els.titulo.value.trim());
    form.append("slug", (els.slug.value.trim() || slugify(els.titulo.value)));
    form.append("autor", els.autor.value.trim() || "Artiani");
    form.append("fecha_publicacion", els.fecha.value);
    form.append("extracto", els.extracto.value.trim());
    form.append("contenido_html", els.contenido.value);
    form.append("imagen_portada", JSON.stringify({
      url: els.portadaUrl.value.trim(),
      alt: els.portadaAlt.value.trim(),
      width: 1600,
      height: 900
    }));
    form.append("seo", JSON.stringify({
      title: els.seoTitle.value.trim() || (els.titulo.value.trim() + " | Artiani"),
      description: els.seoDescription.value.trim() || els.extracto.value.trim(),
      canonical: "/blog/" + (els.slug.value.trim() || slugify(els.titulo.value)),
      robots: "index,follow",
      og_image: els.portadaUrl.value.trim()
    }));
    form.append("videos_json", els.videosJson.value.trim());
    form.append("productos_json", els.productosJson.value.trim());
    form.append("categorias_json", els.categoriasJson.value.trim());
    form.append("imagenes_json", els.imagenesJson.value.trim());
    form.append("bloques_interactivos_json", els.bloquesJson.value.trim());
    return form;
  }

  function validarJsonAvanzado() {
    var campos = [
      ["Videos JSON", els.videosJson],
      ["Productos relacionados JSON", els.productosJson],
      ["Categorias relacionadas JSON", els.categoriasJson],
      ["Imagenes internas JSON", els.imagenesJson],
      ["Bloques interactivos JSON", els.bloquesJson]
    ];
    for (var i = 0; i < campos.length; i++) {
      var valor = campos[i][1] && campos[i][1].value ? campos[i][1].value.trim() : "";
      if (!valor) continue;
      try {
        JSON.parse(valor);
      } catch (err) {
        return campos[i][0] + " invalido: " + err.message;
      }
    }
    return "";
  }

  function guardar(callback) {
    setSalida("Guardando...");
    var data = formDataPublicacion();
    if (!data) return;
    fetch("/cms/blog_publicacion_guardar_erp", {
      method: "POST",
      credentials: "same-origin",
      headers: { "X-CSRF-Token": window.ERP_CSRF_TOKEN || "" },
      body: data
    })
      .then(function (resp) { return resp.json(); })
      .then(function (json) {
        var depurar = json && json.depurar ? json.depurar : {};
        if (depurar.id_blog_publicacion) {
          els.id.value = depurar.id_blog_publicacion;
        }
        setSalida(json);
        listar();
        if (json && json.error === false && typeof callback === "function") {
          callback();
        }
      })
      .catch(function (error) { setSalida(error.message || error); });
  }

  function cambiarEstatus(estado) {
    var id = els.id.value;
    if (!id) {
      guardar(function () { cambiarEstatus(estado); });
      return;
    }
    var form = new FormData();
    form.append("_csrf", window.ERP_CSRF_TOKEN || "");
    form.append("id_blog_publicacion", id);
    form.append("estado", estado);
    setSalida("Actualizando estatus...");
    fetch("/cms/blog_publicacion_estatus_erp", {
      method: "POST",
      credentials: "same-origin",
      headers: { "X-CSRF-Token": window.ERP_CSRF_TOKEN || "" },
      body: form
    })
      .then(function (resp) { return resp.json(); })
      .then(function (json) {
        setSalida(json);
        listar();
      })
      .catch(function (error) { setSalida(error.message || error); });
  }

  function bind() {
    els.resumen = $("cms_blog_estado_resumen");
    els.salida = $("cms_blog_estado_json");
    els.lista = $("cms_blog_lista");
    els.buscar = $("cms_blog_buscar");
    els.filtroEstado = $("cms_blog_filtro_estado");
    els.id = $("cms_blog_id");
    els.tipo = $("cms_blog_tipo");
    els.estado = $("cms_blog_estado");
    els.titulo = $("cms_blog_titulo");
    els.slug = $("cms_blog_slug");
    els.autor = $("cms_blog_autor");
    els.fecha = $("cms_blog_fecha");
    els.portadaUrl = $("cms_blog_portada_url");
    els.portadaAlt = $("cms_blog_portada_alt");
    els.extracto = $("cms_blog_extracto");
    els.contenido = $("cms_blog_contenido");
    els.seoTitle = $("cms_blog_seo_title");
    els.seoDescription = $("cms_blog_seo_description");
    els.videosJson = $("cms_blog_videos_json");
    els.productosJson = $("cms_blog_productos_json");
    els.categoriasJson = $("cms_blog_categorias_json");
    els.imagenesJson = $("cms_blog_imagenes_json");
    els.bloquesJson = $("cms_blog_bloques_json");

    $("cms_blog_estado_btn").addEventListener("click", cargarEstado);
    $("cms_blog_listar_btn").addEventListener("click", listar);
    $("cms_blog_nuevo_btn").addEventListener("click", limpiarForm);
    $("cms_blog_guardar_btn").addEventListener("click", function () { guardar(); });
    $("cms_blog_publicar_btn").addEventListener("click", function () { guardar(function () { cambiarEstatus("publicado"); }); });
    $("cms_blog_pausar_btn").addEventListener("click", function () { cambiarEstatus("pausado"); });
    els.titulo.addEventListener("blur", function () {
      if (!els.slug.value.trim()) els.slug.value = slugify(els.titulo.value);
    });
    els.lista.addEventListener("click", function (ev) {
      var item = ev.target.closest(".cms-blog-item");
      if (item && item.dataset.id) cargarItem(item.dataset.id);
    });
    if (els.buscar) {
      els.buscar.addEventListener("keydown", function (ev) {
        if (ev.key === "Enter") listar();
      });
    }
    if (els.filtroEstado) {
      els.filtroEstado.addEventListener("change", listar);
    }
  }

  document.addEventListener("DOMContentLoaded", function () {
    if (!$("cms_blog_estado_btn")) return;
    bind();
    cargarEstado();
    listar();
  });
})();
