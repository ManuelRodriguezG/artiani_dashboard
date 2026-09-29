/*
 * Documentacion IA: Codex GPT-5, 2026-09-28.
 * Proposito: SDK publico para renderizar videos ecommerce desde /ecommercePublico/videos.
 * Impacto: frontend externo; muestra miniaturas y carga iframe TikTok solo con accion del usuario.
 * Contrato: no lee tablas, no descarga videos, no calcula precios y envia analytics anonimo opcional.
 */
(function (window, document) {
  "use strict";

  var DEFAULT_BASE = "";
  var state = {
    endpointBase: DEFAULT_BASE,
    initialized: false,
    debug: false,
    analytics: true
  };

  function init(options) {
    options = options || {};
    state.endpointBase = normalizeBase(options.endpointBase || DEFAULT_BASE);
    state.debug = options.debug === true;
    state.analytics = options.analytics !== false;
    state.initialized = true;
    if (options.autoMount !== false) mountAll();
    return api;
  }

  function mountAll(root) {
    root = root || document;
    findAll(root, "[data-artiani-videos-list]").forEach(function (el) {
      renderList(el, datasetOptions(el));
    });
    findAll(root, "[data-artiani-video-detail]").forEach(function (el) {
      renderDetail(el, merge(datasetOptions(el), { slug: el.getAttribute("data-artiani-video-detail") || datasetOptions(el).slug }));
    });
    findAll(root, "[data-artiani-product-videos]").forEach(function (el) {
      renderProductVideos(el, merge(datasetOptions(el), { slug: el.getAttribute("data-artiani-product-videos") || datasetOptions(el).slug }));
    });
    findAll(root, "[data-artiani-category-videos]").forEach(function (el) {
      renderCategoryVideos(el, merge(datasetOptions(el), { pathSlug: el.getAttribute("data-artiani-category-videos") || datasetOptions(el).pathSlug }));
    });
  }

  function renderList(container, options) {
    options = options || {};
    setLoading(container);
    var params = new URLSearchParams();
    params.set("pagina", String(options.pagina || 1));
    params.set("limite", String(options.limite || container.getAttribute("data-limite") || 12));
    if (options.tipo) params.set("tipo", options.tipo);
    if (options.categoria) params.set("categoria", options.categoria);
    if (options.destacado) params.set("destacado", options.destacado);
    return getJson("/ecommercePublico/videos?" + params.toString()).then(function (json) {
      var data = depurar(json);
      container.innerHTML = '<div class="artiani-videos-grid">' + (data.items || []).map(cardHtml).join("") + "</div>" + paginationHtml(data.paginacion);
      bindCards(container);
      return json;
    }).catch(function (error) {
      setError(container, error);
    });
  }

  function renderDetail(container, options) {
    options = options || {};
    var slug = cleanPath(options.slug || "");
    if (!slug) {
      setError(container, "Video no especificado.");
      return Promise.resolve();
    }
    setLoading(container);
    return getJson("/ecommercePublico/videos/" + encodeURIComponent(slug)).then(function (json) {
      var item = depurar(json).item || null;
      if (!item) {
        setError(container, "Video no encontrado.");
        return json;
      }
      container.innerHTML = detailHtml(item);
      bindDetail(container, item);
      track("video_view", item, { pagina: "/videos/" + item.slug });
      return json;
    }).catch(function (error) {
      setError(container, error);
    });
  }

  function renderProductVideos(container, options) {
    options = options || {};
    var slug = cleanPath(options.slug || "");
    if (!slug) {
      setError(container, "Producto no especificado.");
      return Promise.resolve();
    }
    setLoading(container);
    return getJson("/ecommercePublico/producto/" + encodeURIComponent(slug) + "/videos?limite=" + encodeURIComponent(options.limite || 6)).then(function (json) {
      renderRelated(container, depurar(json), "Videos de este producto");
      return json;
    }).catch(function (error) {
      setError(container, error);
    });
  }

  function renderCategoryVideos(container, options) {
    options = options || {};
    var pathSlug = cleanPath(options.pathSlug || "");
    if (!pathSlug) {
      setError(container, "Categoria no especificada.");
      return Promise.resolve();
    }
    setLoading(container);
    return getJson("/ecommercePublico/categoria/" + pathSlug.split("/").map(encodeURIComponent).join("/") + "/videos?limite=" + encodeURIComponent(options.limite || 6)).then(function (json) {
      renderRelated(container, depurar(json), "Videos de esta categoria");
      return json;
    }).catch(function (error) {
      setError(container, error);
    });
  }

  function renderRelated(container, data, fallbackTitle) {
    var items = data.items || [];
    if (!items.length) {
      container.innerHTML = "";
      return;
    }
    var title = data.frontend && data.frontend.seccion_titulo ? data.frontend.seccion_titulo : fallbackTitle;
    container.innerHTML = '<section class="artiani-videos-section">' +
      '<div class="artiani-videos-section-head"><h2>' + escapeHtml(title) + "</h2></div>" +
      '<div class="artiani-videos-strip">' + items.map(cardHtml).join("") + "</div>" +
      "</section>";
    bindCards(container);
  }

  function cardHtml(item) {
    var thumb = item.thumbnail || {};
    var video = item.video || {};
    return '<article class="artiani-video-card" data-video-slug="' + escapeHtml(item.slug || "") + '">' +
      '<a class="artiani-video-thumb" href="/videos/' + encodeURIComponent(item.slug || "") + '" aria-label="Ver video ' + escapeHtml(item.titulo || "") + '">' +
        '<img src="' + escapeHtml(thumb.url || "") + '" alt="' + escapeHtml(thumb.alt || item.titulo || "Video Artiani") + '" loading="lazy">' +
        '<span class="artiani-video-play" aria-hidden="true">Play</span>' +
      "</a>" +
      '<div class="artiani-video-body">' +
        '<div class="artiani-video-type">' + escapeHtml(labelTipo(item.tipo_video || "")) + "</div>" +
        '<h3><a href="/videos/' + encodeURIComponent(item.slug || "") + '">' + escapeHtml(item.titulo || "") + "</a></h3>" +
        '<p>' + escapeHtml(item.descripcion_corta || item.copy_tiktok || "") + "</p>" +
        productoLinkHtml(item.producto_principal) +
        (video.duracion_segundos ? '<div class="artiani-video-duration">' + escapeHtml(formatDuration(video.duracion_segundos)) + "</div>" : "") +
      "</div>" +
    "</article>";
  }

  function detailHtml(item) {
    var thumb = item.thumbnail || {};
    var video = item.video || {};
    return '<article class="artiani-video-detail" data-video-slug="' + escapeHtml(item.slug || "") + '">' +
      '<div class="artiani-video-player" data-embed-url="' + escapeHtml(video.embed_url || "") + '">' +
        '<button class="artiani-video-load" type="button" aria-label="Reproducir video">' +
          '<img src="' + escapeHtml(thumb.url || "") + '" alt="' + escapeHtml(thumb.alt || item.titulo || "Video Artiani") + '">' +
          '<span>Reproducir</span>' +
        "</button>" +
      "</div>" +
      '<div class="artiani-video-copy">' +
        '<div class="artiani-video-type">' + escapeHtml(labelTipo(item.tipo_video || "")) + "</div>" +
        "<h1>" + escapeHtml(item.titulo || "") + "</h1>" +
        '<p class="artiani-video-summary">' + escapeHtml(item.descripcion_corta || "") + "</p>" +
        (item.descripcion_larga ? '<div class="artiani-video-description">' + escapeHtml(item.descripcion_larga) + "</div>" : "") +
        productoLinkHtml(item.producto_principal) +
        categoriasHtml(item.categorias_relacionadas || []) +
      "</div>" +
    "</article>";
  }

  function productoLinkHtml(producto) {
    if (!producto || !producto.url) return "";
    return '<a class="artiani-video-product-link" href="' + escapeHtml(producto.url) + '" data-video-product-link="' + escapeHtml(producto.slug || "") + '">' +
      escapeHtml(producto.titulo || producto.slug || "Ver producto") +
    "</a>";
  }

  function categoriasHtml(categorias) {
    if (!categorias.length) return "";
    return '<div class="artiani-video-categories">' + categorias.map(function (categoria) {
      var url = categoria.url_categoria || (categoria.path_slug ? "/categoria/" + categoria.path_slug : "");
      return url ? '<a href="' + escapeHtml(url) + '" data-video-category-link="' + escapeHtml(categoria.path_slug || "") + '">' + escapeHtml(categoria.nombre || categoria.path_slug || "Categoria") + "</a>" : "";
    }).join("") + "</div>";
  }

  function bindCards(container) {
    findAll(container, ".artiani-video-card a").forEach(function (link) {
      link.addEventListener("click", function () {
        var card = link.closest(".artiani-video-card");
        track("video_view", { slug: card ? card.getAttribute("data-video-slug") : "" }, { pagina: link.getAttribute("href") || "" });
      });
    });
    findAll(container, "[data-video-product-link]").forEach(function (link) {
      link.addEventListener("click", function () {
        var card = link.closest("[data-video-slug]");
        track("video_producto_click", { slug: card ? card.getAttribute("data-video-slug") : "" }, { producto_slug: link.getAttribute("data-video-product-link") || "" });
      });
    });
  }

  function bindDetail(container, item) {
    var player = container.querySelector(".artiani-video-player");
    var button = container.querySelector(".artiani-video-load");
    if (button && player) {
      button.addEventListener("click", function () {
        var embedUrl = player.getAttribute("data-embed-url") || "";
        if (!embedUrl) return;
        player.innerHTML = '<iframe src="' + escapeHtml(embedUrl) + '" title="' + escapeHtml(item.titulo || "Video Artiani") + '" loading="lazy" allow="encrypted-media; picture-in-picture" allowfullscreen></iframe>';
        track("video_play", item, { pagina: "/videos/" + item.slug });
      });
    }
    findAll(container, "[data-video-product-link]").forEach(function (link) {
      link.addEventListener("click", function () {
        track("video_producto_click", item, { producto_slug: link.getAttribute("data-video-product-link") || "" });
      });
    });
    findAll(container, "[data-video-category-link]").forEach(function (link) {
      link.addEventListener("click", function () {
        track("video_categoria_click", item, { categoria_path_slug: link.getAttribute("data-video-category-link") || "" });
      });
    });
  }

  function track(evento, item, extra) {
    if (!state.analytics) return Promise.resolve({ skipped: true });
    item = item || {};
    extra = extra || {};
    var payload = merge({
      evento: evento,
      pagina: extra.pagina || currentPath(),
      referencia_tipo: "video",
      referencia_slug: item.slug || "",
      detalle: extra
    }, {});
    if (window.ArtianiEcommerceAnalytics && typeof window.ArtianiEcommerceAnalytics.rawPost === "function") {
      return window.ArtianiEcommerceAnalytics.rawPost("/ecommercePublico/analytics_evento", payload, { beacon: evento !== "video_play" });
    }
    return postJson("/ecommercePublico/analytics_evento", payload);
  }

  function getJson(path) {
    return window.fetch(state.endpointBase + path, {
      method: "GET",
      headers: { Accept: "application/json" },
      credentials: "omit"
    }).then(function (response) {
      return response.json().then(function (json) {
        if (!response.ok || (json && json.error)) {
          throw new Error((json && json.mensaje) || "No fue posible consultar videos.");
        }
        return json;
      });
    });
  }

  function postJson(path, payload) {
    return window.fetch(state.endpointBase + path, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      credentials: "omit",
      body: JSON.stringify(payload || {})
    }).then(function (response) {
      return response.json().catch(function () {
        return { error: !response.ok, status: response.status };
      });
    }).catch(function (error) {
      if (state.debug && window.console) window.console.warn("Evento de video no enviado", error);
      return { error: true, skipped: true };
    });
  }

  function depurar(json) {
    return json && json.depurar ? json.depurar : {};
  }

  function setLoading(container) {
    container.innerHTML = '<div class="artiani-videos-loading">Cargando videos...</div>';
  }

  function setError(container, error) {
    var message = typeof error === "string" ? error : (error && error.message ? error.message : "No fue posible cargar videos.");
    container.innerHTML = '<div class="artiani-videos-error">' + escapeHtml(message) + "</div>";
  }

  function paginationHtml(paginacion) {
    if (!paginacion || Number(paginacion.total_paginas || 0) <= 1) return "";
    return '<nav class="artiani-videos-pagination" aria-label="Paginacion videos">' +
      '<span>Pagina ' + Number(paginacion.pagina || 1) + " de " + Number(paginacion.total_paginas || 1) + "</span>" +
    "</nav>";
  }

  function labelTipo(tipo) {
    return String(tipo || "").replace(/_/g, " ").replace(/\b\w/g, function (letra) { return letra.toUpperCase(); });
  }

  function formatDuration(seconds) {
    seconds = Math.max(0, Number(seconds || 0));
    var min = Math.floor(seconds / 60);
    var sec = Math.floor(seconds % 60);
    return min + ":" + (sec < 10 ? "0" : "") + sec;
  }

  function datasetOptions(el) {
    return {
      pagina: el.getAttribute("data-pagina") || "",
      limite: el.getAttribute("data-limite") || "",
      tipo: el.getAttribute("data-tipo") || "",
      categoria: el.getAttribute("data-categoria") || "",
      destacado: el.getAttribute("data-destacado") || "",
      slug: el.getAttribute("data-slug") || "",
      pathSlug: el.getAttribute("data-path-slug") || ""
    };
  }

  function cleanPath(value) {
    return String(value || "").replace(/^\/+|\/+$/g, "");
  }

  function currentPath() {
    return window.location ? window.location.pathname : "";
  }

  function normalizeBase(base) {
    return String(base || "").replace(/\/+$/, "");
  }

  function merge(a, b) {
    var out = {};
    Object.keys(a || {}).forEach(function (key) { out[key] = a[key]; });
    Object.keys(b || {}).forEach(function (key) { out[key] = b[key]; });
    return out;
  }

  function findAll(root, selector) {
    return Array.prototype.slice.call(root.querySelectorAll(selector));
  }

  function escapeHtml(value) {
    return String(value === null || value === undefined ? "" : value)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }

  var api = {
    init: init,
    mountAll: mountAll,
    renderList: renderList,
    renderDetail: renderDetail,
    renderProductVideos: renderProductVideos,
    renderCategoryVideos: renderCategoryVideos,
    track: track
  };

  window.ArtianiEcommerceVideos = api;
})(window, document);
