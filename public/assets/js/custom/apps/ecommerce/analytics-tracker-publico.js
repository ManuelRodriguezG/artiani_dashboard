/*
 * Documentacion IA: Codex GPT-5, 2026-08-31.
 * Proposito: SDK publico ligero para registrar Analytics Ecommerce v1 desde frontend externo.
 * Impacto: envia eventos anonimos a /ecommercePublico sin tocar ventas, inventario, pedidos ni cotizaciones reales.
 * Contrato: genera session_id en localStorage, filtra claves prohibidas y usa solo endpoints publicos de analytics.
 */
(function (window, document) {
  "use strict";

  var STORAGE_KEY = "artiani_ecommerce_session_id";
  var DEFAULT_ENDPOINT_BASE = "";
  var DEFAULT_CANAL = "web_publica";
  var blockedKeys = {
    nombre: true,
    telefono: true,
    celular: true,
    correo: true,
    email: true,
    rfc: true,
    razon_social: true,
    direccion: true,
    datos_fiscales: true,
    fiscal: true,
    stock_exacto: true,
    existencia_exacta: true,
    existencia: true,
    inventario: true
  };
  var state = {
    endpointBase: DEFAULT_ENDPOINT_BASE,
    canal: DEFAULT_CANAL,
    enabled: true,
    debug: false,
    initialized: false
  };

  function init(options) {
    options = options || {};
    state.endpointBase = normalizeBase(options.endpointBase || DEFAULT_ENDPOINT_BASE);
    state.canal = cleanToken(options.canal || DEFAULT_CANAL, 50) || DEFAULT_CANAL;
    state.enabled = options.enabled !== false;
    state.debug = options.debug === true;
    state.initialized = true;
    if (options.autoSession !== false) {
      trackSession(options.session || {});
    }
    if (options.autoPageView === true) {
      pageView(options.page || {});
    }
    return api;
  }

  function sessionId() {
    var current = "";
    try { current = window.localStorage.getItem(STORAGE_KEY) || ""; } catch (e) { current = ""; }
    if (current) return current;
    current = createUuid();
    try { window.localStorage.setItem(STORAGE_KEY, current); } catch (e) {}
    return current;
  }

  function trackSession(payload) {
    return post("/ecommercePublico/analytics_sesion", withDefaults(payload || {}));
  }

  function pageView(payload) {
    payload = payload || {};
    return post("/ecommercePublico/evento_navegacion", withDefaults(merge(payload, {
      tipo_evento: "page_view",
      ruta: payload.ruta || currentPath(),
      referrer: payload.referrer == null ? document.referrer : payload.referrer
    })));
  }

  function viewProduct(payload) {
    payload = payload || {};
    return post("/ecommercePublico/evento_navegacion", withDefaults(merge(payload, {
      tipo_evento: "view_product",
      ruta: payload.ruta || currentPath()
    })));
  }

  function search(payload) {
    payload = payload || {};
    return post("/ecommercePublico/busqueda_registrar", withDefaults(merge(payload, {
      ruta: payload.ruta || currentPath()
    })));
  }

  function conversion(tipo, payload, options) {
    payload = payload || {};
    options = options || {};
    return post("/ecommercePublico/analytics_conversion", withDefaults(merge(payload, {
      tipo_conversion: cleanToken(tipo || payload.tipo_conversion || "", 70),
      ruta: payload.ruta || currentPath()
    })), options);
  }

  function openWhatsapp(payload) {
    return conversion("open_whatsapp", payload || {}, { beacon: true });
  }

  function facturacionView(payload) {
    payload = payload || {};
    return post("/ecommercePublico/evento_navegacion", withDefaults(merge(payload, {
      tipo_evento: "facturacion_view",
      ruta: payload.ruta || currentPath()
    })));
  }

  function facturacionSubmit(payload) {
    return conversion("facturacion_submit", payload || {});
  }

  function withDefaults(payload) {
    return sanitize(merge(payload || {}, {
      session_id: sessionId(),
      canal: payload.canal || state.canal,
      dispositivo: payload.dispositivo || deviceType(),
      utm_source: payload.utm_source == null ? queryParam("utm_source") : payload.utm_source,
      utm_medium: payload.utm_medium == null ? queryParam("utm_medium") : payload.utm_medium,
      utm_campaign: payload.utm_campaign == null ? queryParam("utm_campaign") : payload.utm_campaign
    }));
  }

  function post(path, payload, options) {
    options = options || {};
    if (!state.enabled) return Promise.resolve({ skipped: true });
    var url = state.endpointBase + path;
    var body = JSON.stringify(sanitize(payload || {}));
    if (options.beacon === true && window.navigator && typeof window.navigator.sendBeacon === "function") {
      var sent = window.navigator.sendBeacon(url, new Blob([body], { type: "application/json" }));
      if (sent) return Promise.resolve({ beacon: true });
    }
    return window.fetch(url, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      credentials: "omit",
      body: body
    }).then(function (response) {
      return response.json().catch(function () {
        return { error: !response.ok, status: response.status };
      });
    }).catch(function (error) {
      if (state.debug && window.console) window.console.warn("Analytics ecommerce no enviado", error);
      return { error: true, skipped: true };
    });
  }

  function sanitize(value) {
    if (Array.isArray(value)) {
      return value.map(sanitize).filter(function (item) { return item !== undefined; });
    }
    if (value && typeof value === "object") {
      var output = {};
      Object.keys(value).forEach(function (key) {
        var normalized = normalizeKey(key);
        if (blockedKeys[normalized]) return;
        var cleaned = sanitize(value[key]);
        if (cleaned !== undefined) output[key] = cleaned;
      });
      return output;
    }
    if (typeof value === "string") return value.slice(0, 1000);
    if (typeof value === "number" && !isFinite(value)) return 0;
    if (typeof value === "function") return undefined;
    return value;
  }

  function cleanToken(value, max) {
    return String(value || "").replace(/[^a-zA-Z0-9_\-.]/g, "").slice(0, max || 80);
  }

  function normalizeKey(key) {
    return String(key || "").toLowerCase().replace(/[\s-]+/g, "_");
  }

  function normalizeBase(base) {
    base = String(base || "").replace(/\/+$/, "");
    return base;
  }

  function currentPath() {
    return window.location.pathname + window.location.search;
  }

  function queryParam(name) {
    try {
      return new URLSearchParams(window.location.search).get(name) || "";
    } catch (e) {
      return "";
    }
  }

  function deviceType() {
    var width = window.innerWidth || 1024;
    if (width < 768) return "mobile";
    if (width < 1100) return "tablet";
    return "desktop";
  }

  function createUuid() {
    if (window.crypto && typeof window.crypto.randomUUID === "function") {
      return window.crypto.randomUUID();
    }
    return "xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx".replace(/[xy]/g, function (char) {
      var random = Math.random() * 16 | 0;
      var value = char === "x" ? random : (random & 3 | 8);
      return value.toString(16);
    });
  }

  function merge(base, extra) {
    var output = {};
    Object.keys(base || {}).forEach(function (key) { output[key] = base[key]; });
    Object.keys(extra || {}).forEach(function (key) {
      if (extra[key] !== undefined) output[key] = extra[key];
    });
    return output;
  }

  var api = {
    init: init,
    sessionId: sessionId,
    trackSession: trackSession,
    pageView: pageView,
    viewProduct: viewProduct,
    search: search,
    conversion: conversion,
    openWhatsapp: openWhatsapp,
    facturacionView: facturacionView,
    facturacionSubmit: facturacionSubmit,
    sanitize: sanitize
  };

  window.ArtianiEcommerceAnalytics = api;
})(window, document);
