<?php
/**
 * IA: Codex GPT-5
 * Fecha: 2026-09-11
 * Proposito: mostrar la consola inicial del submodulo Blog/CMS comercial.
 * Impacto: CMS Blog; permite revisar esquema, crear borradores y validar contratos antes del frontend.
 * Contrato: vista protegida por controlador; POST usa CSRF global y endpoints CMS.
 */
?>
<style>
  .cms-blog-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
  .cms-blog-list { max-height: 520px; overflow: auto; }
  .cms-blog-item { border: 1px solid #eef1f5; border-radius: 8px; padding: 12px; cursor: pointer; }
  .cms-blog-item:hover { background: #f9fafb; }
  .cms-blog-json { min-height: 180px; max-height: 360px; overflow: auto; }
  @media (max-width: 991.98px) { .cms-blog-grid { grid-template-columns: 1fr; } }
</style>

<div class="post d-flex flex-column-fluid" id="kt_post">
  <div id="kt_content_container" class="container-xxl">
    <div class="d-flex flex-wrap flex-stack mb-6">
      <div>
        <h1 class="fw-bold mb-2">CMS Blog / Guias</h1>
        <div class="text-muted">Articulos, guias, videos y contenido comercial para ecommerce publico.</div>
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
                    <option value="video">Video</option>
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
                  <label class="form-label">Imagen portada URL</label>
                  <input class="form-control form-control-solid" id="cms_blog_portada_url" placeholder="/assets/media/cms/ecommerce/imagen.webp">
                </div>
                <div>
                  <label class="form-label">ALT portada</label>
                  <input class="form-control form-control-solid" id="cms_blog_portada_alt" maxlength="255">
                </div>
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
                        <label class="form-label">Videos JSON</label>
                        <textarea class="form-control form-control-solid" id="cms_blog_videos_json" rows="4" placeholder='[{"tipo":"tiktok","titulo":"Video","thumbnail":"https://...","embed_url":"https://www.tiktok.com/embed/...","url_original":"https://..."}]'></textarea>
                      </div>
                      <div class="mb-4">
                        <label class="form-label">Productos relacionados JSON</label>
                        <textarea class="form-control form-control-solid" id="cms_blog_productos_json" rows="3" placeholder='[{"id_publicacion":123,"orden":1}]'></textarea>
                      </div>
                      <div class="mb-4">
                        <label class="form-label">Categorias relacionadas JSON</label>
                        <textarea class="form-control form-control-solid" id="cms_blog_categorias_json" rows="3" placeholder='[{"nombre":"Peceras","path_slug":"acuario-y-peces/peceras","url":"/categoria/acuario-y-peces/peceras"}]'></textarea>
                      </div>
                      <div class="mb-4">
                        <label class="form-label">Imagenes internas JSON</label>
                        <textarea class="form-control form-control-solid" id="cms_blog_imagenes_json" rows="3" placeholder='[{"url":"https://...","alt":"Filtro interno","caption":"Ejemplo","width":1200,"height":800}]'></textarea>
                      </div>
                      <div>
                        <label class="form-label">Bloques interactivos JSON</label>
                        <textarea class="form-control form-control-solid" id="cms_blog_bloques_json" rows="4" placeholder='[{"tipo":"imagen_productos","titulo":"Compra lo que ves","imagen":{"url":"https://...","alt":"Pecera equipada"},"puntos":[{"x":34,"y":48,"producto":{"id_publicacion":123}}]}]'></textarea>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="d-flex flex-wrap gap-2 mt-5">
                <button class="btn btn-primary" type="button" id="cms_blog_guardar_btn"><i class="bi bi-save"></i> Guardar borrador</button>
                <button class="btn btn-success" type="button" id="cms_blog_publicar_btn"><i class="bi bi-check2-circle"></i> Publicar</button>
                <button class="btn btn-light-warning" type="button" id="cms_blog_pausar_btn"><i class="bi bi-pause-circle"></i> Pausar</button>
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
      </div>
    </div>
  </div>
</div>

<script>
  window.ERP_CSRF_TOKEN = "<?= htmlspecialchars(Sesionseguridad::csrfToken(), ENT_QUOTES, 'UTF-8') ?>";
</script>
<script src="/assets/js/custom/apps/erp/cms/blog.js?v=20260911-blog2"></script>
