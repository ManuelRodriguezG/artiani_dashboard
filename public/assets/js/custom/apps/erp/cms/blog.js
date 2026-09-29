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
  var bloqueProductoResultados = [];

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
    els.orden.value = "0";
    els.destacado.checked = false;
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
    limpiarBloqueInteractivo(false);
    renderPreview();
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
        els.orden.value = String(pub.orden || 0);
        els.destacado.checked = Number(pub.destacado || 0) === 1;
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
        cargarBloqueInteractivoDesdeJson();
        setSalida(json);
        renderPreview();
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
    form.append("orden", els.orden.value || "0");
    form.append("destacado", els.destacado.checked ? "1" : "0");
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

  function htmlPreviewSeguro(html) {
    var valor = String(html || "");
    valor = valor.replace(/<script[\s\S]*?>[\s\S]*?<\/script>/gi, "");
    valor = valor.replace(/\son[a-z]+\s*=\s*("[^"]*"|'[^']*'|[^\s>]+)/gi, "");
    valor = valor.replace(/javascript:/gi, "");
    return valor;
  }

  /**
   * IA: Codex GPT-5 | Fecha: 2026-09-28
   * Proposito: generar preview administrativo local del articulo sin persistir ni abrir frontend publico.
   * Impacto: CMS Blog; ayuda a revisar jerarquia, portada, extracto y contenido antes de guardar/publicar.
   * Contrato: solo usa datos del formulario actual; iframe aislado sin scripts.
   */
  function renderPreview() {
    if (!els.previewFrame) return;
    var titulo = els.titulo.value.trim() || "Titulo de publicacion";
    var tipo = els.tipo.value || "articulo";
    var autor = els.autor.value.trim() || "Artiani";
    var fecha = els.fecha.value ? els.fecha.value.replace("T", " ") : "";
    var portada = els.portadaUrl.value.trim();
    var portadaAlt = els.portadaAlt.value.trim() || titulo;
    var extracto = els.extracto.value.trim();
    var contenido = htmlPreviewSeguro(els.contenido.value);
    var bloqueInteractivo = bloqueInteractivoActual();
    var bloqueHtml = "";
    if (bloqueInteractivo.imagen && bloqueInteractivo.imagen.url) {
      bloqueHtml = '<section class="hotspot-section">' +
        (bloqueInteractivo.titulo ? '<h2>' + escapeHtml(bloqueInteractivo.titulo) + '</h2>' : '') +
        '<div class="hotspot-wrap"><img src="' + escapeHtml(bloqueInteractivo.imagen.url) + '" alt="' + escapeHtml(bloqueInteractivo.imagen.alt || "Imagen interactiva") + '">' +
        (bloqueInteractivo.puntos || []).map(function (punto, index) {
          var producto = punto.producto || {};
          return '<span class="hotspot-pin" style="left:' + porcentaje(punto.x, 50) + '%;top:' + porcentaje(punto.y, 50) + '%;" title="' + escapeHtml(producto.nombre || ("Producto " + (index + 1))) + '">' + (index + 1) + '</span>';
        }).join("") +
        '</div></section>';
    }
    var doc = '<!doctype html><html><head><meta charset="utf-8">' +
      '<meta name="viewport" content="width=device-width, initial-scale=1">' +
      '<style>' +
        'body{margin:0;font-family:Arial,sans-serif;color:#1f2937;background:#fff;line-height:1.6;}' +
        '.wrap{max-width:860px;margin:0 auto;padding:34px 22px 56px;}' +
        '.meta{display:flex;flex-wrap:wrap;gap:10px;color:#6b7280;font-size:13px;margin-bottom:14px;}' +
        '.pill{background:#eef2ff;color:#3730a3;border-radius:999px;padding:3px 10px;text-transform:uppercase;font-size:11px;font-weight:700;}' +
        'h1{font-size:34px;line-height:1.15;margin:0 0 12px;color:#111827;letter-spacing:0;}' +
        '.extracto{font-size:18px;color:#4b5563;margin:0 0 24px;}' +
        '.portada{width:100%;border-radius:8px;margin:8px 0 28px;display:block;max-height:460px;object-fit:cover;background:#f3f4f6;}' +
        '.content{font-size:16px;}' +
        '.content img{max-width:100%;height:auto;border-radius:8px;}' +
        '.content h2{font-size:25px;line-height:1.25;margin-top:32px;color:#111827;}' +
        '.content h3{font-size:20px;margin-top:24px;color:#111827;}' +
        '.content a{color:#1d4ed8;}' +
        '.hotspot-section{margin-top:34px;}' +
        '.hotspot-wrap{position:relative;border-radius:8px;overflow:hidden;background:#f3f4f6;}' +
        '.hotspot-wrap img{width:100%;display:block;}' +
        '.hotspot-pin{position:absolute;width:26px;height:26px;border-radius:50%;border:2px solid #fff;background:#0d6efd;color:#fff;font-size:12px;font-weight:700;display:inline-flex;align-items:center;justify-content:center;transform:translate(-50%,-50%);box-shadow:0 4px 14px rgba(13,110,253,.35);}' +
        '.empty{border:1px dashed #d1d5db;border-radius:8px;padding:24px;color:#6b7280;background:#f9fafb;}' +
      '</style></head><body><main class="wrap">' +
      '<div class="meta"><span class="pill">' + escapeHtml(tipo) + '</span><span>' + escapeHtml(autor) + '</span>' + (fecha ? '<span>' + escapeHtml(fecha) + '</span>' : '') + '</div>' +
      '<h1>' + escapeHtml(titulo) + '</h1>' +
      (extracto ? '<p class="extracto">' + escapeHtml(extracto) + '</p>' : '') +
      (portada ? '<img class="portada" src="' + escapeHtml(portada) + '" alt="' + escapeHtml(portadaAlt) + '">' : '') +
      '<article class="content">' + (contenido.trim() ? contenido : '<div class="empty">Agrega contenido HTML para previsualizar el articulo.</div>') + '</article>' +
      bloqueHtml +
      '</main></body></html>';
    els.previewFrame.setAttribute("srcdoc", doc);
  }

  function leerJsonArray(campo) {
    if (!campo || !campo.value.trim()) return [];
    try {
      var parsed = JSON.parse(campo.value.trim());
      return Array.isArray(parsed) ? parsed : [];
    } catch (err) {
      setSalida("JSON invalido: " + err.message);
      return null;
    }
  }

  function escribirJsonArray(campo, items) {
    if (!campo) return;
    campo.value = pretty(items || []);
  }

  function agregarProductoRelacionado(producto) {
    var actuales = leerJsonArray(els.productosJson);
    if (actuales === null) return;
    var idPublicacion = Number(producto.id_publicacion || producto.id || 0);
    if (!idPublicacion) {
      setSalida("El producto seleccionado no tiene id_publicacion publico.");
      return;
    }
    var existe = actuales.some(function (item) {
      return Number(item.id_publicacion || 0) === idPublicacion;
    });
    if (!existe) {
      actuales.push({ id_publicacion: idPublicacion, orden: actuales.length + 1 });
      escribirJsonArray(els.productosJson, actuales);
    }
    setSalida({ error: false, tipo: "success", mensaje: "Producto relacionado agregado.", depurar: { id_publicacion: idPublicacion } });
  }

  function agregarCategoriaRelacionada(categoria) {
    var actuales = leerJsonArray(els.categoriasJson);
    if (actuales === null) return;
    var pathSlug = categoria.path_slug || categoria.slug || "";
    if (!pathSlug) {
      setSalida("La categoria seleccionada no tiene path_slug publico.");
      return;
    }
    var existe = actuales.some(function (item) {
      return String(item.path_slug || "") === String(pathSlug);
    });
    if (!existe) {
      actuales.push({
        nombre: categoria.nombre_publico || categoria.nombre || "Categoria",
        path_slug: pathSlug,
        url: categoria.url || ("/categoria/" + pathSlug),
        id_categoria_erp: categoria.id_categoria_erp || categoria.id || null
      });
      escribirJsonArray(els.categoriasJson, actuales);
    }
    setSalida({ error: false, tipo: "success", mensaje: "Categoria relacionada agregada.", depurar: { path_slug: pathSlug } });
  }

  function mediaUrl(item) {
    return item.url || item.ruta_publica || item.preview_url || "";
  }

  function mediaAlt(item) {
    return item.alt || item.alt_text || item.nombre_seo || item.nombre_original || item.nombre || "";
  }

  function mediaTitulo(item) {
    return item.nombre_seo || item.nombre_original || item.nombre || "Imagen CMS";
  }

  function agregarImagenInterna(item) {
    var actuales = leerJsonArray(els.imagenesJson);
    if (actuales === null) return;
    var url = mediaUrl(item);
    if (!url) {
      setSalida("La imagen seleccionada no tiene URL publica.");
      return;
    }
    var existe = actuales.some(function (imagen) {
      return String(imagen.url || "") === String(url);
    });
    if (!existe) {
      actuales.push({
        url: url,
        alt: mediaAlt(item),
        caption: item.caption || "",
        width: Number(item.width || item.ancho || 0) || null,
        height: Number(item.height || item.alto || 0) || null,
        orden: actuales.length + 1
      });
      escribirJsonArray(els.imagenesJson, actuales);
    }
    setSalida({ error: false, tipo: "success", mensaje: "Imagen interna agregada.", depurar: { url: url } });
  }

  function porcentaje(valor, defecto) {
    var num = Number(valor);
    if (!isFinite(num)) num = defecto;
    return Math.max(0, Math.min(100, Math.round(num * 10) / 10));
  }

  function bloqueInteractivoActual() {
    var bloques = leerJsonArray(els.bloquesJson);
    if (bloques === null || !bloques.length || typeof bloques[0] !== "object") {
      return { tipo: "imagen_productos", titulo: "", imagen: {}, puntos: [] };
    }
    var bloque = bloques[0] || {};
    return {
      tipo: bloque.tipo || "imagen_productos",
      titulo: bloque.titulo || "",
      imagen: bloque.imagen && typeof bloque.imagen === "object" ? bloque.imagen : {},
      puntos: Array.isArray(bloque.puntos) ? bloque.puntos : []
    };
  }

  function escribirBloqueInteractivo(puntos) {
    var titulo = els.bloqueTitulo ? els.bloqueTitulo.value.trim() : "";
    var url = els.bloqueImagenUrl ? els.bloqueImagenUrl.value.trim() : "";
    var alt = els.bloqueImagenAlt ? els.bloqueImagenAlt.value.trim() : "";
    var listaPuntos = Array.isArray(puntos) ? puntos : bloqueInteractivoActual().puntos;
    if (!titulo && !url && !alt && !listaPuntos.length) {
      escribirJsonArray(els.bloquesJson, []);
      renderBloqueInteractivo();
      return;
    }
    escribirJsonArray(els.bloquesJson, [{
      tipo: "imagen_productos",
      titulo: titulo,
      imagen: { url: url, alt: alt },
      puntos: listaPuntos.map(function (punto, index) {
        var producto = punto.producto && typeof punto.producto === "object" ? punto.producto : {};
        return {
          x: porcentaje(punto.x, 50),
          y: porcentaje(punto.y, 50),
          producto: {
            id_publicacion: Number(producto.id_publicacion || 0) || null,
            nombre: producto.nombre || "",
            url: producto.url || ""
          },
          orden: index + 1
        };
      }),
      orden: 1
    }]);
    renderBloqueInteractivo();
  }

  function cargarBloqueInteractivoDesdeJson() {
    if (!els.bloquesJson || !els.bloqueTitulo) return;
    var bloque = bloqueInteractivoActual();
    els.bloqueTitulo.value = bloque.titulo || "";
    els.bloqueImagenUrl.value = bloque.imagen && bloque.imagen.url ? bloque.imagen.url : "";
    els.bloqueImagenAlt.value = bloque.imagen && (bloque.imagen.alt || bloque.imagen.alt_text) ? (bloque.imagen.alt || bloque.imagen.alt_text) : "";
    renderBloqueInteractivo();
  }

  function limpiarBloqueInteractivo(actualizarJson) {
    if (!els.bloqueTitulo) return;
    els.bloqueTitulo.value = "";
    els.bloqueImagenUrl.value = "";
    els.bloqueImagenAlt.value = "";
    els.bloqueProductoId.value = "";
    els.bloqueX.value = "50";
    els.bloqueY.value = "50";
    if (actualizarJson !== false) escribirJsonArray(els.bloquesJson, []);
    renderBloqueInteractivo();
  }

  /**
   * IA: Codex GPT-5 | Fecha: 2026-09-28
   * Proposito: construir puntos interactivos de Blog desde controles visuales en vez de JSON manual.
   * Impacto: CMS Blog; mantiene contrato bloques_interactivos_json para frontend sin cambiar persistencia.
   * Contrato: genera un bloque imagen_productos con coordenadas porcentuales y productos publicos relacionados.
   */
  function renderBloqueInteractivo() {
    if (!els.bloqueCanvas || !els.bloquePuntosLista) return;
    var bloque = bloqueInteractivoActual();
    var imagenUrl = els.bloqueImagenUrl && els.bloqueImagenUrl.value.trim() ? els.bloqueImagenUrl.value.trim() : (bloque.imagen.url || "");
    var imagenAlt = els.bloqueImagenAlt && els.bloqueImagenAlt.value.trim() ? els.bloqueImagenAlt.value.trim() : (bloque.imagen.alt || "Imagen interactiva");
    var puntos = Array.isArray(bloque.puntos) ? bloque.puntos : [];
    if (!imagenUrl) {
      els.bloqueCanvas.innerHTML = '<div class="text-muted fs-7 p-5">Selecciona una imagen para ver los puntos interactivos.</div>';
    } else {
      els.bloqueCanvas.innerHTML = '<img src="' + escapeHtml(imagenUrl) + '" alt="' + escapeHtml(imagenAlt) + '">' +
        puntos.map(function (punto, index) {
          return '<span class="cms-blog-hotspot-pin" style="left:' + porcentaje(punto.x, 50) + '%;top:' + porcentaje(punto.y, 50) + '%;">' + (index + 1) + '</span>';
        }).join("");
    }
    if (!puntos.length) {
      els.bloquePuntosLista.innerHTML = '<div class="text-muted">Sin puntos.</div>';
      return;
    }
    els.bloquePuntosLista.innerHTML = puntos.map(function (punto, index) {
      var producto = punto.producto || {};
      return '<div class="border rounded p-2 mb-2">' +
        '<div class="d-flex justify-content-between gap-2">' +
          '<div class="fw-semibold">Punto ' + (index + 1) + '</div>' +
          '<button class="btn btn-sm btn-light-danger cms-blog-bloque-punto-eliminar" type="button" data-index="' + index + '"><i class="bi bi-x-lg"></i></button>' +
        '</div>' +
        '<div class="text-muted">X ' + porcentaje(punto.x, 50) + '% · Y ' + porcentaje(punto.y, 50) + '%</div>' +
        '<div class="text-muted text-truncate">Producto: ' + escapeHtml(producto.nombre || ("ID " + (producto.id_publicacion || ""))) + '</div>' +
      '</div>';
    }).join("");
    els.bloquePuntosLista.querySelectorAll(".cms-blog-bloque-punto-eliminar").forEach(function (btn) {
      btn.addEventListener("click", function () {
        var actuales = bloqueInteractivoActual().puntos;
        actuales.splice(Number(btn.dataset.index) || 0, 1);
        escribirBloqueInteractivo(actuales);
      });
    });
  }

  function agregarPuntoInteractivo() {
    var productoId = Number(els.bloqueProductoId && els.bloqueProductoId.value ? els.bloqueProductoId.value : 0);
    if (!els.bloqueImagenUrl.value.trim()) {
      setSalida("Selecciona una imagen para el bloque interactivo.");
      return;
    }
    if (!productoId) {
      setSalida("Selecciona o captura el id_publicacion del producto para el punto.");
      return;
    }
    var productoEncontrado = bloqueProductoResultados.find(function (producto) {
      return Number(producto.id_publicacion || producto.id || 0) === productoId;
    }) || {};
    var bloque = bloqueInteractivoActual();
    var puntos = bloque.puntos.slice();
    puntos.push({
      x: porcentaje(els.bloqueX.value, 50),
      y: porcentaje(els.bloqueY.value, 50),
      producto: {
        id_publicacion: productoId,
        nombre: productoEncontrado.nombre || "",
        url: productoEncontrado.url || productoEncontrado.url_publica || ""
      }
    });
    escribirBloqueInteractivo(puntos);
    setSalida({ error: false, tipo: "success", mensaje: "Punto interactivo agregado.", depurar: { id_publicacion: productoId } });
  }

  function usarPortadaEnBloque() {
    if (!els.bloqueImagenUrl) return;
    els.bloqueImagenUrl.value = els.portadaUrl.value.trim();
    els.bloqueImagenAlt.value = els.portadaAlt.value.trim();
    escribirBloqueInteractivo();
  }

  function capturarCoordenadaBloque(ev) {
    if (!els.bloqueCanvas || !els.bloqueImagenUrl.value.trim()) return;
    var rect = els.bloqueCanvas.getBoundingClientRect();
    if (!rect.width || !rect.height) return;
    els.bloqueX.value = String(porcentaje(((ev.clientX - rect.left) / rect.width) * 100, 50));
    els.bloqueY.value = String(porcentaje(((ev.clientY - rect.top) / rect.height) * 100, 50));
  }

  function buscarProductosBloqueInteractivo() {
    if (!els.bloqueProductosPanel || !els.bloqueProductosResultados) return;
    els.bloqueProductosPanel.classList.remove("d-none");
    els.bloqueProductosResultados.innerHTML = '<div class="col-12 text-muted fs-7">Buscando productos publicados...</div>';
    var params = new URLSearchParams();
    params.set("limite", "8");
    if (els.bloqueProductosBuscar && els.bloqueProductosBuscar.value.trim()) params.set("q", els.bloqueProductosBuscar.value.trim());
    fetch("/ecommercePublico/catalogo?" + params.toString(), { credentials: "same-origin" })
      .then(function (resp) { return resp.json(); })
      .then(function (json) {
        var depurar = json && json.depurar ? json.depurar : {};
        bloqueProductoResultados = Array.isArray(depurar.items) ? depurar.items : [];
        if (!bloqueProductoResultados.length) {
          els.bloqueProductosResultados.innerHTML = '<div class="col-12"><div class="alert alert-light-warning fs-7 mb-0">No encontre productos publicados con ese filtro.</div></div>';
          setSalida(json);
          return;
        }
        els.bloqueProductosResultados.innerHTML = bloqueProductoResultados.map(function (item, index) {
          var img = item.imagen || "";
          return '<div class="col-md-6">' +
            '<button class="btn btn-light w-100 text-start p-2 cms-blog-bloque-producto-item" type="button" data-index="' + index + '">' +
              '<div class="d-flex gap-3 align-items-center">' +
                '<div class="symbol symbol-50px bg-light flex-shrink-0">' +
                  (img ? '<img src="' + escapeHtml(img) + '" alt="' + escapeHtml(item.imagen_alt || item.nombre || "Producto") + '">' : '<span class="symbol-label"><i class="bi bi-box-seam"></i></span>') +
                '</div>' +
                '<div class="min-w-0">' +
                  '<div class="fw-semibold fs-8 text-truncate">' + escapeHtml(item.nombre || "Producto") + '</div>' +
                  '<div class="text-muted fs-9 text-truncate">' + escapeHtml(item.url || item.url_publica || "") + '</div>' +
                '</div>' +
              '</div>' +
            '</button>' +
          '</div>';
        }).join("");
        els.bloqueProductosResultados.querySelectorAll(".cms-blog-bloque-producto-item").forEach(function (btn) {
          btn.addEventListener("click", function () {
            var item = bloqueProductoResultados[Number(btn.dataset.index) || 0] || {};
            var idPublicacion = Number(item.id_publicacion || item.id || 0);
            if (idPublicacion) {
              els.bloqueProductoId.value = String(idPublicacion);
              setSalida({ error: false, tipo: "success", mensaje: "Producto seleccionado para punto interactivo.", depurar: { id_publicacion: idPublicacion } });
            }
          });
        });
        setSalida(json);
      })
      .catch(function (error) {
        els.bloqueProductosResultados.innerHTML = '<div class="col-12"><div class="alert alert-light-danger fs-7 mb-0">No fue posible consultar productos.</div></div>';
        setSalida(error.message || error);
      });
  }

  /**
   * IA: Codex GPT-5 | Fecha: 2026-09-28
   * Proposito: agregar productos publicados como relaciones de blog sin capturar JSON a mano.
   * Impacto: CMS Blog; lectura de catalogo publico y escritura local del formulario.
   * Contrato: usa /ecommercePublico/catalogo read-only y conserva el JSON esperado por backend.
   */
  function buscarProductosRelacionados() {
    if (!els.productosPanel || !els.productosResultados) return;
    els.productosPanel.classList.remove("d-none");
    els.productosResultados.innerHTML = '<div class="col-12 text-muted fs-7">Buscando productos publicados...</div>';
    var params = new URLSearchParams();
    params.set("limite", "8");
    if (els.productosBuscar && els.productosBuscar.value.trim()) params.set("q", els.productosBuscar.value.trim());
    fetch("/ecommercePublico/catalogo?" + params.toString(), { credentials: "same-origin" })
      .then(function (resp) { return resp.json(); })
      .then(function (json) {
        var depurar = json && json.depurar ? json.depurar : {};
        var items = Array.isArray(depurar.items) ? depurar.items : [];
        if (!items.length) {
          els.productosResultados.innerHTML = '<div class="col-12"><div class="alert alert-light-warning fs-7 mb-0">No encontre productos publicados con ese filtro.</div></div>';
          setSalida(json);
          return;
        }
        els.productosResultados.innerHTML = items.map(function (item, index) {
          var img = item.imagen || "";
          return '<div class="col-md-6">' +
            '<button class="btn btn-light w-100 text-start p-2 cms-blog-producto-item" type="button" data-index="' + index + '">' +
              '<div class="d-flex gap-3 align-items-center">' +
                '<div class="symbol symbol-60px bg-light flex-shrink-0">' +
                  (img ? '<img src="' + escapeHtml(img) + '" alt="' + escapeHtml(item.imagen_alt || item.nombre || "Producto") + '">' : '<span class="symbol-label"><i class="bi bi-box-seam"></i></span>') +
                '</div>' +
                '<div class="min-w-0">' +
                  '<div class="fw-semibold fs-8 text-truncate">' + escapeHtml(item.nombre || "Producto") + '</div>' +
                  '<div class="text-muted fs-9 text-truncate">' + escapeHtml(item.marca || "") + '</div>' +
                  '<div class="text-muted fs-9 text-truncate">' + escapeHtml(item.url || item.url_publica || "") + '</div>' +
                '</div>' +
              '</div>' +
            '</button>' +
          '</div>';
        }).join("");
        els.productosResultados.querySelectorAll(".cms-blog-producto-item").forEach(function (btn) {
          btn.addEventListener("click", function () {
            agregarProductoRelacionado(items[Number(btn.dataset.index) || 0] || {});
          });
        });
        setSalida(json);
      })
      .catch(function (error) {
        els.productosResultados.innerHTML = '<div class="col-12"><div class="alert alert-light-danger fs-7 mb-0">No fue posible consultar productos.</div></div>';
        setSalida(error.message || error);
      });
  }

  /**
   * IA: Codex GPT-5 | Fecha: 2026-09-28
   * Proposito: elegir categorias publicas para relacionarlas con una publicacion.
   * Impacto: CMS Blog; lectura de categorias publicas y escritura local del formulario.
   * Contrato: usa /ecommercePublico/categorias read-only y conserva path_slug para frontend.
   */
  function buscarCategoriasRelacionadas() {
    if (!els.categoriasPanel || !els.categoriasResultados) return;
    els.categoriasPanel.classList.remove("d-none");
    els.categoriasResultados.innerHTML = '<div class="col-12 text-muted fs-7">Cargando categorias publicas...</div>';
    fetch("/ecommercePublico/categorias?limite=160", { credentials: "same-origin" })
      .then(function (resp) { return resp.json(); })
      .then(function (json) {
        var depurar = json && json.depurar ? json.depurar : {};
        var items = Array.isArray(depurar.items) ? depurar.items : [];
        var filtro = els.categoriasBuscar && els.categoriasBuscar.value.trim().toLowerCase() ? els.categoriasBuscar.value.trim().toLowerCase() : "";
        if (filtro) {
          items = items.filter(function (item) {
            return String((item.nombre_publico || item.nombre || "") + " " + (item.path_slug || "")).toLowerCase().indexOf(filtro) !== -1;
          });
        }
        if (!items.length) {
          els.categoriasResultados.innerHTML = '<div class="col-12"><div class="alert alert-light-warning fs-7 mb-0">No encontre categorias con ese filtro.</div></div>';
          setSalida(json);
          return;
        }
        els.categoriasResultados.innerHTML = items.slice(0, 24).map(function (item, index) {
          return '<div class="col-md-6">' +
            '<button class="btn btn-light w-100 text-start p-3 cms-blog-categoria-item" type="button" data-index="' + index + '">' +
              '<div class="fw-semibold fs-8 text-truncate">' + escapeHtml(item.nombre_publico || item.nombre || "Categoria") + '</div>' +
              '<div class="text-muted fs-9 text-truncate">' + escapeHtml(item.path_slug || "") + '</div>' +
            '</button>' +
          '</div>';
        }).join("");
        els.categoriasResultados.querySelectorAll(".cms-blog-categoria-item").forEach(function (btn) {
          btn.addEventListener("click", function () {
            agregarCategoriaRelacionada(items[Number(btn.dataset.index) || 0] || {});
          });
        });
        setSalida(json);
      })
      .catch(function (error) {
        els.categoriasResultados.innerHTML = '<div class="col-12"><div class="alert alert-light-danger fs-7 mb-0">No fue posible consultar categorias.</div></div>';
        setSalida(error.message || error);
      });
  }

  /**
   * IA: Codex GPT-5 | Fecha: 2026-09-28
   * Proposito: agregar imagenes internas desde Media CMS sin pegar URLs manuales.
   * Impacto: CMS Blog; lectura de biblioteca y escritura local del JSON de imagenes.
   * Contrato: no sube archivos ni modifica Media CMS; solo agrega referencias al formulario.
   */
  function abrirMediaImagenesInternas() {
    if (!els.imagenesPanel || !els.imagenesResultados) return;
    els.imagenesPanel.classList.remove("d-none");
    els.imagenesResultados.innerHTML = '<div class="col-12 text-muted fs-7">Cargando Media CMS...</div>';
    fetch("/cms/media_admin_listar_erp?limite=24&uso=blog", { credentials: "same-origin" })
      .then(function (resp) { return resp.json(); })
      .then(function (json) {
        var depurar = json && json.depurar ? json.depurar : {};
        var items = Array.isArray(depurar.items) ? depurar.items : [];
        if (!items.length) {
          els.imagenesResultados.innerHTML = '<div class="col-12"><div class="alert alert-light-warning fs-7 mb-0">No hay imagenes disponibles para Blog. Revisa /cms/media o carga una imagen nueva.</div></div>';
          setSalida(json);
          return;
        }
        els.imagenesResultados.innerHTML = items.map(function (item, index) {
          var url = mediaUrl(item);
          var alt = mediaAlt(item);
          return '<div class="col-md-4">' +
            '<button class="btn btn-light w-100 text-start p-2 cms-blog-imagen-item" type="button" data-index="' + index + '">' +
              '<div class="ratio ratio-16x9 bg-light mb-2">' +
                (url ? '<img src="' + escapeHtml(url) + '" alt="' + escapeHtml(alt || "Imagen CMS") + '" class="w-100 h-100 object-fit-cover rounded">' : '') +
              '</div>' +
              '<div class="fw-semibold fs-8 text-truncate">' + escapeHtml(mediaTitulo(item)) + '</div>' +
              '<div class="text-muted fs-9 text-truncate">' + escapeHtml(alt || "Sin ALT") + '</div>' +
            '</button>' +
          '</div>';
        }).join("");
        els.imagenesResultados.querySelectorAll(".cms-blog-imagen-item").forEach(function (btn) {
          btn.addEventListener("click", function () {
            agregarImagenInterna(items[Number(btn.dataset.index) || 0] || {});
          });
        });
        setSalida(json);
      })
      .catch(function (error) {
        els.imagenesResultados.innerHTML = '<div class="col-12"><div class="alert alert-light-danger fs-7 mb-0">No fue posible consultar Media CMS.</div></div>';
        setSalida(error.message || error);
      });
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

  /**
   * IA: Codex GPT-5 | Fecha: 2026-09-28
   * Proposito: seleccionar portada desde Media CMS sin pegar URLs manuales.
   * Impacto: CMS Blog; reutiliza biblioteca existente y conserva ALT capturado.
   * Contrato: lectura GET a /cms/media_admin_listar_erp; no sube ni modifica archivos.
   */
  function abrirMediaPortada() {
    if (!els.mediaPanel || !els.mediaLista) return;
    els.mediaPanel.classList.remove("d-none");
    els.mediaLista.innerHTML = '<div class="col-12 text-muted fs-7">Cargando Media CMS...</div>';
    fetch("/cms/media_admin_listar_erp?limite=24&uso=blog", { credentials: "same-origin" })
      .then(function (resp) { return resp.json(); })
      .then(function (json) {
        var depurar = json && json.depurar ? json.depurar : {};
        var items = Array.isArray(depurar.items) ? depurar.items : [];
        if (!items.length) {
          els.mediaLista.innerHTML = '<div class="col-12"><div class="alert alert-light-warning fs-7 mb-0">No hay imagenes disponibles para Blog. Revisa /cms/media o carga una imagen nueva.</div></div>';
          setSalida(json);
          return;
        }
        els.mediaLista.innerHTML = items.map(function (item, index) {
          var url = mediaUrl(item);
          var alt = mediaAlt(item);
          return '<div class="col-md-4">' +
            '<button class="btn btn-light w-100 text-start p-2 cms-blog-media-item" type="button" data-index="' + index + '">' +
              '<div class="ratio ratio-16x9 bg-light mb-2">' +
                (url ? '<img src="' + escapeHtml(url) + '" alt="' + escapeHtml(alt || "Imagen CMS") + '" class="w-100 h-100 object-fit-cover rounded">' : '') +
              '</div>' +
              '<div class="fw-semibold fs-8 text-truncate">' + escapeHtml(mediaTitulo(item)) + '</div>' +
              '<div class="text-muted fs-9 text-truncate">' + escapeHtml(alt || "Sin ALT") + '</div>' +
            '</button>' +
          '</div>';
        }).join("");
        els.mediaLista.querySelectorAll(".cms-blog-media-item").forEach(function (btn) {
          btn.addEventListener("click", function () {
            var item = items[Number(btn.dataset.index) || 0] || {};
            var url = mediaUrl(item);
            var alt = mediaAlt(item);
            els.portadaUrl.value = url;
            if (!els.portadaAlt.value.trim() && alt) els.portadaAlt.value = alt;
            els.mediaPanel.classList.add("d-none");
          });
        });
        setSalida(json);
      })
      .catch(function (error) {
        els.mediaLista.innerHTML = '<div class="col-12"><div class="alert alert-light-danger fs-7 mb-0">No fue posible consultar Media CMS.</div></div>';
        setSalida(error.message || error);
      });
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
    els.orden = $("cms_blog_orden");
    els.destacado = $("cms_blog_destacado");
    els.portadaUrl = $("cms_blog_portada_url");
    els.portadaAlt = $("cms_blog_portada_alt");
    els.extracto = $("cms_blog_extracto");
    els.contenido = $("cms_blog_contenido");
    els.seoTitle = $("cms_blog_seo_title");
    els.seoDescription = $("cms_blog_seo_description");
    els.mediaPanel = $("cms_blog_media_panel");
    els.mediaLista = $("cms_blog_media_lista");
    els.previewFrame = $("cms_blog_preview_frame");
    els.productosPanel = $("cms_blog_productos_panel");
    els.productosBuscar = $("cms_blog_productos_buscar");
    els.productosResultados = $("cms_blog_productos_resultados");
    els.categoriasPanel = $("cms_blog_categorias_panel");
    els.categoriasBuscar = $("cms_blog_categorias_buscar");
    els.categoriasResultados = $("cms_blog_categorias_resultados");
    els.imagenesPanel = $("cms_blog_imagenes_panel");
    els.imagenesResultados = $("cms_blog_imagenes_resultados");
    els.videosJson = $("cms_blog_videos_json");
    els.productosJson = $("cms_blog_productos_json");
    els.categoriasJson = $("cms_blog_categorias_json");
    els.imagenesJson = $("cms_blog_imagenes_json");
    els.bloquesJson = $("cms_blog_bloques_json");
    els.bloqueTitulo = $("cms_blog_bloque_titulo");
    els.bloqueImagenUrl = $("cms_blog_bloque_imagen_url");
    els.bloqueImagenAlt = $("cms_blog_bloque_imagen_alt");
    els.bloqueProductoId = $("cms_blog_bloque_producto_id");
    els.bloqueX = $("cms_blog_bloque_x");
    els.bloqueY = $("cms_blog_bloque_y");
    els.bloqueCanvas = $("cms_blog_bloque_canvas");
    els.bloquePuntosLista = $("cms_blog_bloque_puntos_lista");
    els.bloqueProductosPanel = $("cms_blog_bloque_productos_panel");
    els.bloqueProductosBuscar = $("cms_blog_bloque_productos_buscar");
    els.bloqueProductosResultados = $("cms_blog_bloque_productos_resultados");

    $("cms_blog_estado_btn").addEventListener("click", cargarEstado);
    $("cms_blog_listar_btn").addEventListener("click", listar);
    $("cms_blog_nuevo_btn").addEventListener("click", limpiarForm);
    $("cms_blog_guardar_btn").addEventListener("click", function () { guardar(); });
    $("cms_blog_publicar_btn").addEventListener("click", function () { guardar(function () { cambiarEstatus("publicado"); }); });
    $("cms_blog_pausar_btn").addEventListener("click", function () { cambiarEstatus("pausado"); });
    $("cms_blog_preview_btn").addEventListener("click", renderPreview);
    $("cms_blog_media_btn").addEventListener("click", abrirMediaPortada);
    $("cms_blog_media_cerrar").addEventListener("click", function () { els.mediaPanel.classList.add("d-none"); });
    $("cms_blog_productos_btn").addEventListener("click", buscarProductosRelacionados);
    $("cms_blog_productos_buscar_btn").addEventListener("click", buscarProductosRelacionados);
    $("cms_blog_productos_cerrar").addEventListener("click", function () { els.productosPanel.classList.add("d-none"); });
    $("cms_blog_categorias_btn").addEventListener("click", buscarCategoriasRelacionadas);
    $("cms_blog_categorias_buscar_btn").addEventListener("click", buscarCategoriasRelacionadas);
    $("cms_blog_categorias_cerrar").addEventListener("click", function () { els.categoriasPanel.classList.add("d-none"); });
    $("cms_blog_imagenes_btn").addEventListener("click", abrirMediaImagenesInternas);
    $("cms_blog_imagenes_cerrar").addEventListener("click", function () { els.imagenesPanel.classList.add("d-none"); });
    $("cms_blog_bloque_usar_portada").addEventListener("click", usarPortadaEnBloque);
    $("cms_blog_bloque_punto_agregar").addEventListener("click", agregarPuntoInteractivo);
    $("cms_blog_bloque_limpiar").addEventListener("click", function () { limpiarBloqueInteractivo(true); });
    $("cms_blog_bloque_productos_btn").addEventListener("click", buscarProductosBloqueInteractivo);
    $("cms_blog_bloque_productos_buscar_btn").addEventListener("click", buscarProductosBloqueInteractivo);
    $("cms_blog_bloque_productos_cerrar").addEventListener("click", function () { els.bloqueProductosPanel.classList.add("d-none"); });
    els.bloqueCanvas.addEventListener("click", capturarCoordenadaBloque);
    [els.bloqueTitulo, els.bloqueImagenUrl, els.bloqueImagenAlt].forEach(function (campo) {
      campo.addEventListener("change", function () { escribirBloqueInteractivo(); });
      campo.addEventListener("blur", function () { escribirBloqueInteractivo(); });
    });
    els.bloquesJson.addEventListener("blur", cargarBloqueInteractivoDesdeJson);
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
    if (els.productosBuscar) {
      els.productosBuscar.addEventListener("keydown", function (ev) {
        if (ev.key === "Enter") {
          ev.preventDefault();
          buscarProductosRelacionados();
        }
      });
    }
    if (els.categoriasBuscar) {
      els.categoriasBuscar.addEventListener("keydown", function (ev) {
        if (ev.key === "Enter") {
          ev.preventDefault();
          buscarCategoriasRelacionadas();
        }
      });
    }
    if (els.bloqueProductosBuscar) {
      els.bloqueProductosBuscar.addEventListener("keydown", function (ev) {
        if (ev.key === "Enter") {
          ev.preventDefault();
          buscarProductosBloqueInteractivo();
        }
      });
    }
    renderPreview();
  }

  document.addEventListener("DOMContentLoaded", function () {
    if (!$("cms_blog_estado_btn")) return;
    bind();
    cargarEstado();
    listar();
  });
})();
