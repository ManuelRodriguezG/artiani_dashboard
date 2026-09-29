<?php
/**
 * IA: Codex GPT-5
 * Fecha: 2026-09-11
 * Proposito: mostrar la consola inicial del submodulo Blog/CMS comercial.
 * Impacto: CMS Blog; permite revisar esquema, crear borradores y validar contratos antes del frontend.
 * Contrato: vista protegida por controlador; POST usa CSRF global y endpoints CMS.
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <base href="../../../../">
  <title>CMS - Blog / Guias</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
  <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css">
  <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css">
<style>
  .cms-blog-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
  .cms-blog-list { max-height: 520px; overflow: auto; }
  .cms-blog-item { border: 1px solid #eef1f5; border-radius: 8px; padding: 12px; cursor: pointer; }
  .cms-blog-item:hover { background: #f9fafb; }
  .cms-blog-json { min-height: 180px; max-height: 360px; overflow: auto; }
  .cms-blog-preview-frame { width: 100%; min-height: 720px; border: 1px solid #eef1f5; border-radius: 8px; background: #fff; }
  .cms-blog-hotspot-canvas { position: relative; overflow: hidden; border: 1px solid #eef1f5; border-radius: 8px; background: #f5f8fa; min-height: 220px; }
  .cms-blog-hotspot-canvas img { width: 100%; display: block; object-fit: cover; }
  .cms-blog-hotspot-pin { position: absolute; width: 22px; height: 22px; border-radius: 50%; border: 2px solid #fff; background: #0d6efd; color: #fff; font-size: 11px; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; transform: translate(-50%, -50%); box-shadow: 0 4px 14px rgba(13,110,253,.35); }
  @media (max-width: 991.98px) { .cms-blog-grid { grid-template-columns: 1fr; } }
</style>
</head>
<body id="kt_app_body" data-kt-app-layout="dark-sidebar" data-kt-app-header-fixed="true" data-kt-app-sidebar-enabled="true" data-kt-app-sidebar-fixed="true" class="app-default">
<div class="d-flex flex-column flex-root app-root" id="kt_app_root">
  <div class="app-page flex-column flex-column-fluid" id="kt_app_page">
    <?= include_once '../app/vistas/includes/header/header.php'; ?>
    <div class="app-wrapper flex-column flex-row-fluid" id="kt_app_wrapper">
      <?= include_once '../app/vistas/includes/header/sidebar.php'; ?>
      <div class="app-main flex-column flex-row-fluid" id="kt_app_main">
        <div class="d-flex flex-column flex-column-fluid">
          <div class="app-content flex-column-fluid">

<div class="post d-flex flex-column-fluid" id="kt_post">
  <div id="kt_content_container" class="container-xxl">
    <div class="d-flex flex-wrap flex-stack mb-6">
      <div>
        <h1 class="fw-bold mb-2">CMS Blog / Guias</h1>
        <div class="text-muted">Articulos, guias y contenido comercial para ecommerce publico.</div>
      </div>
      <div class="d-flex gap-2">
        <a class="btn btn-light" href="/cms/frontend/busqueda"><i class="bi bi-search"></i> Busqueda CMS</a>
        <a class="btn btn-light-primary" href="/ecommercePublico/blog_manifest" target="_blank"><i class="bi bi-box-arrow-up-right"></i> Manifest publico</a>
      </div>
    </div>

    <div class="row g-5">
      <div class="col-xl-4">
        <div class="card mb-5">
          <div class="card-header">
            <h3 class="card-title">Estado del modulo</h3>
          </div>
          <div class="card-body">
            <div class="d-grid gap-3">
              <button class="btn btn-primary" type="button" id="cms_blog_estado_btn"><i class="bi bi-arrow-clockwise"></i> Revisar estado</button>
              <a class="btn btn-light" href="/ecommercePublico/esquema_plan_cms_blog" target="_blank"><i class="bi bi-database"></i> Ver plan DDL</a>
              <a class="btn btn-light" href="/ecommercePublico/esquema_auditar_cms_blog" target="_blank"><i class="bi bi-clipboard-check"></i> Auditar tablas</a>
            </div>
            <div class="separator my-5"></div>
            <div id="cms_blog_estado_resumen" class="text-muted fs-7">Sin consultar.</div>
          </div>
        </div>

        <div class="card mb-5">
          <div class="card-header">
            <h3 class="card-title">Siguiente paso</h3>
          </div>
          <div class="card-body fs-7">
            <div class="mb-3"><span class="badge badge-light-warning me-2">Pendiente</span>Aplicar DDL solo con respaldo y autorizacion explicita.</div>
            <div class="mb-3"><span class="badge badge-light-primary me-2">UI</span>Usar selectores visuales para relaciones de Blog y puntos interactivos.</div>
            <div><span class="badge badge-light-info me-2">Frontend</span>Consumir `/ecommercePublico/blog`, `/blog/{slug}` y busqueda global.</div>
          </div>
        </div>

        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Publicaciones</h3>
          </div>
          <div class="card-body">
            <div class="position-relative mb-3">
              <i class="bi bi-search position-absolute ms-4 mt-3"></i>
              <input class="form-control form-control-solid ps-11" id="cms_blog_buscar" placeholder="Buscar titulo, guia o contenido">
            </div>
            <div class="d-flex gap-2 mb-4">
              <select class="form-select form-select-sm form-select-solid" id="cms_blog_filtro_estado">
                <option value="">Todos</option>
                <option value="borrador">Borrador</option>
                <option value="publicado">Publicado</option>
                <option value="pausado">Pausado</option>
              </select>
              <button class="btn btn-sm btn-light-primary" type="button" id="cms_blog_listar_btn"><i class="bi bi-arrow-clockwise"></i></button>
            </div>
            <div id="cms_blog_lista" class="cms-blog-list text-muted fs-7">Sin cargar.</div>
          </div>
        </div>
      </div>

      <div class="col-xl-8">
        <div class="card mb-5">
          <div class="card-header">
            <h3 class="card-title">Editor inicial</h3>
            <div class="card-toolbar">
              <button class="btn btn-sm btn-light" type="button" id="cms_blog_nuevo_btn"><i class="bi bi-file-earmark-plus"></i> Nuevo</button>
            </div>
          </div>
          <div class="card-body">
            <form id="cms_blog_form">
              <input type="hidden" id="cms_blog_id">
              <div class="cms-blog-grid">
                <div>
                  <label class="form-label">Tipo</label>
                  <select class="form-select form-select-solid" id="cms_blog_tipo">
                    <option value="articulo">Articulo</option>
                    <option value="guia">Guia</option>
                    <option value="noticia">Noticia</option>
                    <option value="inspiracion">Inspiracion</option>
                    <option value="caso_cliente">Caso cliente</option>
                    <option value="recomendacion_producto">Recomendacion producto</option>
                  </select>
                </div>
                <div>
                  <label class="form-label">Estado de trabajo</label>
                  <select class="form-select form-select-solid" id="cms_blog_estado">
                    <option value="borrador">Borrador</option>
                    <option value="pausado">Pausado</option>
                  </select>
                </div>
                <div>
                  <label class="form-label">Titulo</label>
                  <input class="form-control form-control-solid" id="cms_blog_titulo" maxlength="255">
                </div>
                <div>
                  <label class="form-label">Slug</label>
                  <input class="form-control form-control-solid" id="cms_blog_slug" maxlength="180">
                </div>
                <div>
                  <label class="form-label">Autor</label>
                  <input class="form-control form-control-solid" id="cms_blog_autor" maxlength="120" value="Artiani">
                </div>
                <div>
                  <label class="form-label">Fecha publicacion</label>
                  <input class="form-control form-control-solid" id="cms_blog_fecha" type="datetime-local">
                </div>
                <div>
                  <label class="form-label">Orden editorial</label>
                  <input class="form-control form-control-solid" id="cms_blog_orden" type="number" min="0" step="1" value="0">
                </div>
                <div>
                  <label class="form-label">Visibilidad editorial</label>
                  <label class="form-check form-switch form-check-custom form-check-solid mt-3">
                    <input class="form-check-input" id="cms_blog_destacado" type="checkbox" value="1">
                    <span class="form-check-label">Destacar en listados publicos</span>
                  </label>
                </div>
                <div>
                  <label class="form-label">Imagen portada URL</label>
                  <div class="input-group">
                    <input class="form-control form-control-solid" id="cms_blog_portada_url" placeholder="/assets/media/cms/ecommerce/imagen.webp">
                    <button class="btn btn-light-primary" type="button" id="cms_blog_media_btn"><i class="bi bi-images"></i> Media</button>
                  </div>
                </div>
                <div>
                  <label class="form-label">ALT portada</label>
                  <input class="form-control form-control-solid" id="cms_blog_portada_alt" maxlength="255">
                </div>
              </div>
              <div class="mt-3 d-none" id="cms_blog_media_panel">
                <div class="d-flex align-items-center justify-content-between mb-2">
                  <div class="fw-semibold fs-7">Seleccionar portada desde Media CMS</div>
                  <button class="btn btn-sm btn-light" type="button" id="cms_blog_media_cerrar"><i class="bi bi-x-lg"></i></button>
                </div>
                <div id="cms_blog_media_lista" class="row g-3"></div>
              </div>

              <div class="mt-4">
                <label class="form-label">Extracto</label>
                <textarea class="form-control form-control-solid" id="cms_blog_extracto" rows="3"></textarea>
              </div>

              <div class="mt-4">
                <label class="form-label">Contenido HTML seguro</label>
                <textarea class="form-control form-control-solid" id="cms_blog_contenido" rows="10" placeholder="<p>Contenido...</p>"></textarea>
              </div>

              <div class="cms-blog-grid mt-4">
                <div>
                  <label class="form-label">SEO title</label>
                  <input class="form-control form-control-solid" id="cms_blog_seo_title" maxlength="255">
                </div>
                <div>
                  <label class="form-label">SEO description</label>
                  <input class="form-control form-control-solid" id="cms_blog_seo_description" maxlength="255">
                </div>
              </div>

              <div class="accordion mt-5" id="cms_blog_relaciones">
                <div class="accordion-item">
                  <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#cms_blog_relaciones_body">
                      Relaciones y bloques avanzados
                    </button>
                  </h2>
                  <div id="cms_blog_relaciones_body" class="accordion-collapse collapse" data-bs-parent="#cms_blog_relaciones">
                    <div class="accordion-body">
                      <div class="mb-4">
                        <label class="form-label">Videos incorporados JSON</label>
                        <div class="text-muted fs-8 mb-2">Reservado para relacionar videos cuando el modulo Videos exista; Blog no administra videos.</div>
                        <textarea class="form-control form-control-solid" id="cms_blog_videos_json" rows="4" placeholder='[{"id_video":1,"posicion":"contenido","orden":1}]'></textarea>
                      </div>
                      <div class="mb-4">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                          <label class="form-label mb-0">Productos relacionados JSON</label>
                          <button class="btn btn-sm btn-light-primary" type="button" id="cms_blog_productos_btn"><i class="bi bi-search"></i> Buscar productos</button>
                        </div>
                        <div class="d-none border rounded p-3 mb-3" id="cms_blog_productos_panel">
                          <div class="input-group input-group-sm mb-3">
                            <input class="form-control form-control-solid" id="cms_blog_productos_buscar" placeholder="Buscar producto publicado">
                            <button class="btn btn-primary" type="button" id="cms_blog_productos_buscar_btn"><i class="bi bi-search"></i></button>
                            <button class="btn btn-light" type="button" id="cms_blog_productos_cerrar"><i class="bi bi-x-lg"></i></button>
                          </div>
                          <div id="cms_blog_productos_resultados" class="row g-3"></div>
                        </div>
                        <textarea class="form-control form-control-solid" id="cms_blog_productos_json" rows="3" placeholder='[{"id_publicacion":123,"orden":1}]'></textarea>
                      </div>
                      <div class="mb-4">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                          <label class="form-label mb-0">Categorias relacionadas JSON</label>
                          <button class="btn btn-sm btn-light-primary" type="button" id="cms_blog_categorias_btn"><i class="bi bi-diagram-3"></i> Elegir categorias</button>
                        </div>
                        <div class="d-none border rounded p-3 mb-3" id="cms_blog_categorias_panel">
                          <div class="input-group input-group-sm mb-3">
                            <input class="form-control form-control-solid" id="cms_blog_categorias_buscar" placeholder="Filtrar categoria publica">
                            <button class="btn btn-primary" type="button" id="cms_blog_categorias_buscar_btn"><i class="bi bi-search"></i></button>
                            <button class="btn btn-light" type="button" id="cms_blog_categorias_cerrar"><i class="bi bi-x-lg"></i></button>
                          </div>
                          <div id="cms_blog_categorias_resultados" class="row g-3"></div>
                        </div>
                        <textarea class="form-control form-control-solid" id="cms_blog_categorias_json" rows="3" placeholder='[{"nombre":"Peceras","path_slug":"acuario-y-peces/peceras","url":"/categoria/acuario-y-peces/peceras"}]'></textarea>
                      </div>
                      <div class="mb-4">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                          <label class="form-label mb-0">Imagenes internas JSON</label>
                          <button class="btn btn-sm btn-light-primary" type="button" id="cms_blog_imagenes_btn"><i class="bi bi-images"></i> Agregar imagenes</button>
                        </div>
                        <div class="d-none border rounded p-3 mb-3" id="cms_blog_imagenes_panel">
                          <div class="d-flex align-items-center justify-content-between mb-3">
                            <div class="fw-semibold fs-7">Imagenes internas desde Media CMS</div>
                            <button class="btn btn-sm btn-light" type="button" id="cms_blog_imagenes_cerrar"><i class="bi bi-x-lg"></i></button>
                          </div>
                          <div id="cms_blog_imagenes_resultados" class="row g-3"></div>
                        </div>
                        <textarea class="form-control form-control-solid" id="cms_blog_imagenes_json" rows="3" placeholder='[{"url":"https://...","alt":"Filtro interno","caption":"Ejemplo","width":1200,"height":800}]'></textarea>
                      </div>
                      <div>
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                          <label class="form-label mb-0">Bloque interactivo de imagen</label>
                          <button class="btn btn-sm btn-light-primary" type="button" id="cms_blog_bloque_usar_portada"><i class="bi bi-image"></i> Usar portada</button>
                        </div>
                        <div class="text-muted fs-8 mb-3">Arma puntos sobre una imagen y relaciona cada punto con un producto. Se guarda en el contrato de bloques interactivos del Blog.</div>
                        <div class="cms-blog-grid">
                          <div>
                            <label class="form-label">Titulo del bloque</label>
                            <input class="form-control form-control-solid" id="cms_blog_bloque_titulo" maxlength="180" placeholder="Compra lo que ves">
                          </div>
                          <div>
                            <label class="form-label">Imagen del bloque URL</label>
                            <input class="form-control form-control-solid" id="cms_blog_bloque_imagen_url" placeholder="/assets/media/cms/ecommerce/imagen.webp">
                          </div>
                          <div>
                            <label class="form-label">ALT de imagen</label>
                            <input class="form-control form-control-solid" id="cms_blog_bloque_imagen_alt" maxlength="255">
                          </div>
                          <div>
                            <label class="form-label">Producto del punto</label>
                            <div class="input-group">
                              <input class="form-control form-control-solid" id="cms_blog_bloque_producto_id" type="number" min="1" placeholder="id_publicacion">
                              <button class="btn btn-light-primary" type="button" id="cms_blog_bloque_productos_btn"><i class="bi bi-search"></i></button>
                            </div>
                          </div>
                          <div>
                            <label class="form-label">X %</label>
                            <input class="form-control form-control-solid" id="cms_blog_bloque_x" type="number" min="0" max="100" step="0.1" value="50">
                          </div>
                          <div>
                            <label class="form-label">Y %</label>
                            <input class="form-control form-control-solid" id="cms_blog_bloque_y" type="number" min="0" max="100" step="0.1" value="50">
                          </div>
                        </div>
                        <div class="d-none border rounded p-3 mt-3" id="cms_blog_bloque_productos_panel">
                          <div class="input-group input-group-sm mb-3">
                            <input class="form-control form-control-solid" id="cms_blog_bloque_productos_buscar" placeholder="Buscar producto publicado para el punto">
                            <button class="btn btn-primary" type="button" id="cms_blog_bloque_productos_buscar_btn"><i class="bi bi-search"></i></button>
                            <button class="btn btn-light" type="button" id="cms_blog_bloque_productos_cerrar"><i class="bi bi-x-lg"></i></button>
                          </div>
                          <div id="cms_blog_bloque_productos_resultados" class="row g-3"></div>
                        </div>
                        <div class="d-flex flex-wrap gap-2 mt-3">
                          <button class="btn btn-sm btn-primary" type="button" id="cms_blog_bloque_punto_agregar"><i class="bi bi-plus-circle"></i> Agregar punto</button>
                          <button class="btn btn-sm btn-light-warning" type="button" id="cms_blog_bloque_limpiar"><i class="bi bi-trash3"></i> Limpiar bloque</button>
                        </div>
                        <div class="row g-4 mt-1">
                          <div class="col-lg-7">
                            <div class="cms-blog-hotspot-canvas" id="cms_blog_bloque_canvas">
                              <div class="text-muted fs-7 p-5">Selecciona una imagen para ver los puntos interactivos.</div>
                            </div>
                          </div>
                          <div class="col-lg-5">
                            <div class="fw-semibold fs-7 mb-2">Puntos del bloque</div>
                            <div id="cms_blog_bloque_puntos_lista" class="text-muted fs-8">Sin puntos.</div>
                          </div>
                        </div>
                        <textarea class="form-control form-control-solid mt-3" id="cms_blog_bloques_json" rows="4" placeholder='[{"tipo":"imagen_productos","titulo":"Compra lo que ves","imagen":{"url":"https://...","alt":"Pecera equipada"},"puntos":[{"x":34,"y":48,"producto":{"id_publicacion":123}}]}]'></textarea>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="d-flex flex-wrap gap-2 mt-5">
                <button class="btn btn-primary" type="button" id="cms_blog_guardar_btn"><i class="bi bi-save"></i> Guardar borrador</button>
                <button class="btn btn-success" type="button" id="cms_blog_publicar_btn"><i class="bi bi-check2-circle"></i> Publicar</button>
                <button class="btn btn-light-warning" type="button" id="cms_blog_pausar_btn"><i class="bi bi-pause-circle"></i> Pausar</button>
                <button class="btn btn-light-primary" type="button" id="cms_blog_preview_btn"><i class="bi bi-eye"></i> Preview</button>
                <a class="btn btn-light" href="/ecommercePublico/blog" target="_blank"><i class="bi bi-box-arrow-up-right"></i> Ver API blog</a>
              </div>
            </form>
          </div>
        </div>

        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Respuesta / contrato</h3>
          </div>
          <div class="card-body">
            <pre class="bg-light p-4 rounded fs-8 cms-blog-json mb-0" id="cms_blog_estado_json">Sin datos.</pre>
          </div>
        </div>

        <div class="card mt-5">
          <div class="card-header">
            <h3 class="card-title">Preview administrativo</h3>
          </div>
          <div class="card-body">
            <iframe class="cms-blog-preview-frame" id="cms_blog_preview_frame" sandbox=""></iframe>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

          </div>
        </div>
        <?= include_once '../app/vistas/includes/footer/footer.php'; ?>
      </div>
    </div>
  </div>
</div>
<script>
  window.ERP_CSRF_TOKEN = "<?= htmlspecialchars(SesionSeguridad::csrfToken(), ENT_QUOTES, 'UTF-8') ?>";
</script>
<script src="assets/plugins/global/plugins.bundle.js"></script>
<script src="assets/js/scripts.bundle.js"></script>
<script src="assets/js/custom/apps/erp/cms/blog.js?v=20260928-blog9"></script>
</body>
</html>
