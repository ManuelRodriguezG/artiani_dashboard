/**
 * IA: Codex GPT-5 | Fecha: 2026-09-28
 * Proposito: operar la consola CMS Videos para enlaces TikTok.
 * Impacto: UI CMS Videos; lista, consulta, guarda y cambia estatus con CSRF.
 * Contrato: usa endpoints /cms/videos_*; no sube ni descarga videos.
 */
(function () {
  "use strict";

  var els = {};

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
    return String(fecha).replace(" ", "T").slice(0, 16);
  }

  function extraerTikTok(url) {
    var salida = { postId: "", author: "" };
    var texto = String(url || "");
    var match = texto.match(/tiktok\.com\/@([^/]+)\/video\/([0-9]+)/i);
    if (match) {
      salida.author = slugify(match[1]);
      salida.postId = match[2];
      return salida;
    }
    match = texto.match(/\/video\/([0-9]+)/i) || texto.match(/\/v\/([0-9]+)/i);
    if (match) salida.postId = match[1];
    return salida;
  }

  function sincronizarTikTok() {
    var datos = extraerTikTok(els.videoUrl.value);
    if (datos.postId && !els.postId.value) els.postId.value = datos.postId;
    if (datos.author && !els.author.value) els.author.value = datos.author;
    if (els.postId.value && !els.embedUrl.value) {
      els.embedUrl.value = "https://www.tiktok.com/player/v1/" + els.postId.value + "?autoplay=0&description=0";
    }
  }

  function normalizarHashtags(valor) {
    var vistos = {};
    return String(valor || "")
      .split(/[\s,]+/)
      .map(function (tag) { return tag.replace(/^#/, "").replace(/[^A-Za-z0-9_]/g, ""); })
      .filter(function (tag) {
        if (!tag || vistos[tag]) return false;
        vistos[tag] = true;
        return true;
      })
      .map(function (tag) { return "#" + tag; })
      .join(" ");
  }

  function jsonValido(texto, nombre) {
    try {
      var valor = JSON.parse(texto || "[]");
      return { ok: true, valor: valor };
    } catch (e) {
      return { ok: false, mensaje: nombre + ": JSON invalido. " + e.message };
    }
  }

  function validarJson() {
    var campos = [
      { el: els.productoPrincipalJson, nombre: "Producto principal", defaultValue: "{}" },
      { el: els.productosJson, nombre: "Productos relacionados", defaultValue: "[]" },
      { el: els.categoriasJson, nombre: "Categorias", defaultValue: "[]" }
    ];
    for (var i = 0; i < campos.length; i++) {
      var texto = campos[i].el.value.trim() || campos[i].defaultValue;
      var resultado = jsonValido(texto, campos[i].nombre);
      if (!resultado.ok) return resultado.mensaje;
      campos[i].el.value = pretty(resultado.valor);
    }
    return "";
  }

  function renderPreview() {
    if (!els.preview) return;
    var url = els.thumbnailUrl.value.trim();
    els.preview.src = url || "";
    els.preview.alt = els.thumbnailAlt.value.trim() || "Preview miniatura";
  }

  function renderEstado(respuesta) {
    var depurar = respuesta && respuesta.depurar ? respuesta.depurar : {};
    var auditoria = depurar.esquema && depurar.esquema.auditoria ? depurar.esquema.auditoria : {};
    var faltantes = Number(auditoria.tablas_faltantes || 0);
    var total = Number(Object.keys(auditoria.tablas || {}).length || auditoria.tablas_total || 3);
    if (els.resumen) {
      els.resumen.innerHTML = '<span class="badge badge-light-' + (faltantes > 0 ? "warning" : "success") + '">' +
        (faltantes > 0 ? "Pendiente DDL" : "Listo") + "</span>" +
        '<div class="mt-3">Tablas disponibles: ' + Math.max(0, total - faltantes) + " de " + total + ".</div>" +
        '<div class="mt-2">Provider: TikTok, con miniatura obligatoria y carga diferida.</div>';
    }
    setSalida(respuesta);
  }

  function cargarEstado() {
    if (els.resumen) els.resumen.textContent = "Consultando...";
    fetch("/cms/videos_admin_estado_erp", { credentials: "same-origin" })
      .then(function (resp) { return resp.json(); })
      .then(renderEstado)
      .catch(function (error) {
        renderEstado({ error: true, tipo: "danger", mensaje: error.message || "No fue posible consultar el estado" });
      });
  }

  function renderLista(respuesta) {
    var depurar = respuesta && respuesta.depurar ? respuesta.depurar : {};
    var items = depurar.items || [];
    if (!els.lista) return;
    if (!items.length) {
      els.lista.innerHTML = '<div class="text-muted">No hay videos para estos filtros.</div>';
      setSalida(respuesta);
      return;
    }
    els.lista.innerHTML = items.map(function (item) {
      var thumb = item.thumbnail && item.thumbnail.url ? item.thumbnail.url : "";
      return '<div class="cms-videos-item mb-2" data-id="' + Number(item.id || 0) + '">' +
        '<div class="d-flex gap-3">' +
          '<img class="cms-videos-thumb" src="' + escapeHtml(thumb) + '" alt="' + escapeHtml(item.thumbnail && item.thumbnail.alt ? item.thumbnail.alt : "") + '">' +
          '<div class="flex-grow-1">' +
            '<div class="d-flex justify-content-between gap-3">' +
              '<div class="fw-bold text-gray-900">' + escapeHtml(item.titulo || "Sin titulo") + "</div>" +
              '<span class="badge badge-light-' + (item.estado === "publicado" ? "success" : (item.estado === "pausado" ? "warning" : "primary")) + '">' + escapeHtml(item.estado || "") + "</span>" +
            "</div>" +
            '<div class="text-muted fs-8 mt-1">' + escapeHtml(item.tipo_video || "") + " · " + escapeHtml(item.url || "") + "</div>" +
            '<div class="text-muted fs-8 mt-1">' + escapeHtml(item.descripcion_corta || item.copy_tiktok || "") + "</div>" +
          "</div>" +
        "</div>" +
      "</div>";
    }).join("");
    setSalida(respuesta);
  }

  function listar() {
    if (els.lista) els.lista.textContent = "Cargando...";
    var params = new URLSearchParams();
    params.set("limite", "30");
    if (els.buscar.value.trim()) params.set("q", els.buscar.value.trim());
    if (els.filtroEstado.value) params.set("estado", els.filtroEstado.value);
    fetch("/cms/videos_admin_listar_erp?" + params.toString(), { credentials: "same-origin" })
      .then(function (resp) { return resp.json(); })
      .then(renderLista)
      .catch(function (error) {
        if (els.lista) els.lista.textContent = "No fue posible listar.";
        setSalida(error.message || error);
      });
  }

  function limpiarForm() {
    els.id.value = "";
    els.titulo.value = "";
    els.slug.value = "";
    els.tipo.value = "demo_producto";
    els.estado.value = "borrador";
    els.videoUrl.value = "";
    els.embedUrl.value = "";
    els.thumbnailUrl.value = "";
    els.thumbnailAlt.value = "";
    els.author.value = "";
    els.postId.value = "";
    els.duracion.value = "";
    els.orden.value = "0";
    els.destacado.checked = false;
    els.descripcionCorta.value = "";
    els.descripcionLarga.value = "";
    els.copyTiktok.value = "";
    els.hashtags.value = "";
    els.textoBusqueda.value = "";
    els.productoPrincipalJson.value = pretty({ slug_producto: "", id_publicacion: 0, id_sku: 0 });
    els.productosJson.value = "[]";
    els.categoriasJson.value = "[]";
    els.seoTitle.value = "";
    els.seoCanonical.value = "";
    els.seoDescription.value = "";
    els.ogImage.value = "";
    renderPreview();
  }

  function cargarItem(id) {
    fetch("/cms/videos_admin_consultar_erp?id_video=" + encodeURIComponent(id), { credentials: "same-origin" })
      .then(function (resp) { return resp.json(); })
      .then(function (json) {
        var video = json && json.depurar ? json.depurar.video : null;
        if (!video) {
          setSalida(json);
          return;
        }
        var relaciones = json.depurar.relaciones || {};
        els.id.value = video.id || "";
        els.titulo.value = video.titulo || "";
        els.slug.value = video.slug || "";
        els.tipo.value = video.tipo_video || "demo_producto";
        els.estado.value = video.estado === "pausado" ? "pausado" : "borrador";
        els.videoUrl.value = video.video && video.video.url ? video.video.url : "";
        els.embedUrl.value = video.video && video.video.embed_url ? video.video.embed_url : "";
        els.thumbnailUrl.value = video.thumbnail && video.thumbnail.url ? video.thumbnail.url : "";
        els.thumbnailAlt.value = video.thumbnail && video.thumbnail.alt ? video.thumbnail.alt : "";
        els.author.value = video.video && video.video.tiktok_author ? video.video.tiktok_author : "";
        els.postId.value = video.video && video.video.tiktok_post_id ? video.video.tiktok_post_id : "";
        els.duracion.value = video.duracion_segundos || "";
        els.orden.value = video.orden || 0;
        els.destacado.checked = !!video.destacado;
        els.descripcionCorta.value = video.descripcion_corta || "";
        els.descripcionLarga.value = video.descripcion_larga || "";
        els.copyTiktok.value = video.copy_tiktok || "";
        els.hashtags.value = video.hashtags || "";
        els.textoBusqueda.value = video.texto_busqueda || "";
        var productos = relaciones.productos || [];
        var principal = {};
        var secundarios = [];
        productos.forEach(function (producto) {
          if (Number(producto.producto_principal || 0) === 1 && !principal.slug_producto) {
            principal = producto;
          } else {
            secundarios.push(producto);
          }
        });
        els.productoPrincipalJson.value = pretty(principal);
        els.productosJson.value = pretty(secundarios);
        els.categoriasJson.value = pretty(relaciones.categorias || []);
        els.seoTitle.value = video.seo && video.seo.title ? video.seo.title : "";
        els.seoCanonical.value = video.seo && video.seo.canonical ? video.seo.canonical : "";
        els.seoDescription.value = video.seo && video.seo.description ? video.seo.description : "";
        els.ogImage.value = video.seo && video.seo.og_image ? video.seo.og_image : "";
        renderPreview();
        setSalida(json);
      })
      .catch(function (error) { setSalida(error.message || error); });
  }

  function formDataVideo() {
    sincronizarTikTok();
    els.hashtags.value = normalizarHashtags(els.hashtags.value);
    var errorJson = validarJson();
    if (errorJson) {
      setSalida(errorJson);
      return null;
    }
    var form = new FormData();
    form.append("_csrf", window.ERP_CSRF_TOKEN || "");
    if (els.id.value) form.append("id_video", els.id.value);
    form.append("titulo", els.titulo.value.trim());
    form.append("slug", els.slug.value.trim() || slugify(els.titulo.value));
    form.append("tipo_video", els.tipo.value);
    form.append("estado", els.estado.value);
    form.append("video_url", els.videoUrl.value.trim());
    form.append("embed_url", els.embedUrl.value.trim());
    form.append("thumbnail_url", els.thumbnailUrl.value.trim());
    form.append("thumbnail_alt", els.thumbnailAlt.value.trim());
    form.append("tiktok_author", els.author.value.trim());
    form.append("tiktok_post_id", els.postId.value.trim());
    form.append("duracion_segundos", els.duracion.value || "0");
    form.append("orden", els.orden.value || "0");
    form.append("destacado", els.destacado.checked ? "1" : "0");
    form.append("descripcion_corta", els.descripcionCorta.value.trim());
    form.append("descripcion_larga", els.descripcionLarga.value.trim());
    form.append("copy_tiktok", els.copyTiktok.value.trim());
    form.append("hashtags", els.hashtags.value.trim());
    form.append("texto_busqueda", els.textoBusqueda.value.trim());
    form.append("producto_principal_json", els.productoPrincipalJson.value.trim());
    form.append("productos_json", els.productosJson.value.trim());
    form.append("categorias_json", els.categoriasJson.value.trim());
    form.append("seo_title", els.seoTitle.value.trim());
    form.append("seo_canonical", els.seoCanonical.value.trim());
    form.append("seo_description", els.seoDescription.value.trim());
    form.append("og_image", els.ogImage.value.trim());
    form.append("metadata_json", "{}");
    return form;
  }

  function guardar(evento) {
    if (evento) evento.preventDefault();
    var form = formDataVideo();
    if (!form) return;
    fetch("/cms/videos_guardar_erp", { method: "POST", body: form, credentials: "same-origin" })
      .then(function (resp) { return resp.json(); })
      .then(function (json) {
        if (json && json.depurar && json.depurar.id_video) els.id.value = json.depurar.id_video;
        setSalida(json);
        listar();
      })
      .catch(function (error) { setSalida(error.message || error); });
  }

  function cambiarEstatus(estado) {
    if (!els.id.value) {
      setSalida("Guarda o selecciona un video antes de cambiar estatus.");
      return;
    }
    var form = new FormData();
    form.append("_csrf", window.ERP_CSRF_TOKEN || "");
    form.append("id_video", els.id.value);
    form.append("estado", estado);
    fetch("/cms/videos_estatus_erp", { method: "POST", body: form, credentials: "same-origin" })
      .then(function (resp) { return resp.json(); })
      .then(function (json) {
        setSalida(json);
        listar();
      })
      .catch(function (error) { setSalida(error.message || error); });
  }

  function bind() {
    els.estadoBtn.addEventListener("click", cargarEstado);
    els.listarBtn.addEventListener("click", listar);
    els.nuevoBtn.addEventListener("click", limpiarForm);
    els.form.addEventListener("submit", guardar);
    els.publicarBtn.addEventListener("click", function () { cambiarEstatus("publicado"); });
    els.pausarBtn.addEventListener("click", function () { cambiarEstatus("pausado"); });
    els.borradorBtn.addEventListener("click", function () { cambiarEstatus("borrador"); });
    els.videoUrl.addEventListener("blur", sincronizarTikTok);
    els.postId.addEventListener("blur", sincronizarTikTok);
    els.thumbnailUrl.addEventListener("input", renderPreview);
    els.thumbnailAlt.addEventListener("input", renderPreview);
    els.titulo.addEventListener("blur", function () {
      if (!els.slug.value.trim()) els.slug.value = slugify(els.titulo.value);
      if (!els.seoTitle.value.trim()) els.seoTitle.value = els.titulo.value + " | Artiani";
      if (!els.seoCanonical.value.trim() && els.slug.value.trim()) els.seoCanonical.value = "/videos/" + els.slug.value.trim();
    });
    els.hashtags.addEventListener("blur", function () { els.hashtags.value = normalizarHashtags(els.hashtags.value); });
    els.lista.addEventListener("click", function (event) {
      var item = event.target.closest(".cms-videos-item");
      if (item && item.getAttribute("data-id")) cargarItem(item.getAttribute("data-id"));
    });
  }

  function cacheEls() {
    els.estadoBtn = $("cms_videos_estado_btn");
    els.resumen = $("cms_videos_estado_resumen");
    els.buscar = $("cms_videos_buscar");
    els.filtroEstado = $("cms_videos_filtro_estado");
    els.listarBtn = $("cms_videos_listar_btn");
    els.lista = $("cms_videos_lista");
    els.salida = $("cms_videos_salida");
    els.form = $("cms_videos_form");
    els.nuevoBtn = $("cms_videos_nuevo_btn");
    els.id = $("cms_videos_id");
    els.titulo = $("cms_videos_titulo");
    els.slug = $("cms_videos_slug");
    els.tipo = $("cms_videos_tipo");
    els.estado = $("cms_videos_estado");
    els.videoUrl = $("cms_videos_video_url");
    els.embedUrl = $("cms_videos_embed_url");
    els.thumbnailUrl = $("cms_videos_thumbnail_url");
    els.thumbnailAlt = $("cms_videos_thumbnail_alt");
    els.author = $("cms_videos_tiktok_author");
    els.postId = $("cms_videos_tiktok_post_id");
    els.duracion = $("cms_videos_duracion");
    els.orden = $("cms_videos_orden");
    els.destacado = $("cms_videos_destacado");
    els.descripcionCorta = $("cms_videos_descripcion_corta");
    els.descripcionLarga = $("cms_videos_descripcion_larga");
    els.copyTiktok = $("cms_videos_copy_tiktok");
    els.hashtags = $("cms_videos_hashtags");
    els.textoBusqueda = $("cms_videos_texto_busqueda");
    els.productoPrincipalJson = $("cms_videos_producto_principal_json");
    els.productosJson = $("cms_videos_productos_json");
    els.categoriasJson = $("cms_videos_categorias_json");
    els.preview = $("cms_videos_preview");
    els.seoTitle = $("cms_videos_seo_title");
    els.seoCanonical = $("cms_videos_seo_canonical");
    els.seoDescription = $("cms_videos_seo_description");
    els.ogImage = $("cms_videos_og_image");
    els.publicarBtn = $("cms_videos_publicar_btn");
    els.pausarBtn = $("cms_videos_pausar_btn");
    els.borradorBtn = $("cms_videos_borrador_btn");
  }

  document.addEventListener("DOMContentLoaded", function () {
    cacheEls();
    if (!els.form) return;
    bind();
    limpiarForm();
    cargarEstado();
    listar();
  });
})();
