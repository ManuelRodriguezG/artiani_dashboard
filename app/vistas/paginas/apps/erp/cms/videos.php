<?php
/**
 * IA: Codex GPT-5
 * Fecha: 2026-09-28
 * Proposito: mostrar consola CMS para videos TikTok ecommerce.
 * Impacto: CMS Videos; captura enlaces externos, miniaturas, copy y relaciones sin alojar video.
 * Contrato: vista protegida por controlador; POST usa CSRF global y endpoints CMS.
 */
?>
<style>
  .cms-videos-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
  .cms-videos-list { max-height: 560px; overflow: auto; }
  .cms-videos-item { border: 1px solid #eef1f5; border-radius: 8px; padding: 12px; cursor: pointer; }
  .cms-videos-item:hover { background: #f9fafb; }
  .cms-videos-thumb { width: 72px; aspect-ratio: 9 / 16; object-fit: cover; border-radius: 8px; background: #f5f8fa; }
  .cms-videos-preview { aspect-ratio: 9 / 16; max-height: 520px; width: min(100%, 300px); object-fit: cover; border-radius: 8px; background: #f5f8fa; }
  .cms-videos-output { min-height: 180px; max-height: 360px; overflow: auto; }
  .cms-videos-textarea { min-height: 96px; }
  @media (max-width: 991.98px) { .cms-videos-grid { grid-template-columns: 1fr; } }
</style>

<div class="post d-flex flex-column-fluid" id="kt_post">
  <div id="kt_content_container" class="container-xxl">
    <div class="d-flex flex-wrap flex-stack mb-6">
      <div>
        <h1 class="fw-bold mb-2">CMS Videos TikTok</h1>
        <div class="text-muted">Enlaces TikTok, miniaturas, copy y relaciones para ecommerce publico.</div>
      </div>
      <div class="d-flex gap-2">
        <a class="btn btn-light" href="/cms/frontend/blog"><i class="bi bi-journal-text"></i> Blog CMS</a>
        <a class="btn btn-light-primary" href="/ecommercePublico/videos_manifest" target="_blank"><i class="bi bi-box-arrow-up-right"></i> Manifest publico</a>
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
              <button class="btn btn-primary" type="button" id="cms_videos_estado_btn"><i class="bi bi-arrow-clockwise"></i> Revisar estado</button>
              <a class="btn btn-light" href="/ecommercePublico/esquema_plan_videos" target="_blank"><i class="bi bi-database"></i> Ver plan DDL</a>
              <a class="btn btn-light" href="/ecommercePublico/esquema_auditar_videos" target="_blank"><i class="bi bi-clipboard-check"></i> Auditar tablas</a>
            </div>
            <div class="separator my-5"></div>
            <div id="cms_videos_estado_resumen" class="text-muted fs-7">Sin consultar.</div>
          </div>
        </div>

        <div class="card mb-5">
          <div class="card-header">
            <h3 class="card-title">Videos</h3>
          </div>
          <div class="card-body">
            <div class="position-relative mb-3">
              <i class="bi bi-search position-absolute ms-4 mt-3"></i>
              <input class="form-control form-control-solid ps-11" id="cms_videos_buscar" placeholder="Buscar titulo, copy, hashtag o tema">
            </div>
            <div class="d-flex gap-2 mb-4">
              <select class="form-select form-select-sm form-select-solid" id="cms_videos_filtro_estado">
                <option value="">Todos</option>
                <option value="borrador">Borrador</option>
                <option value="publicado">Publicado</option>
                <option value="pausado">Pausado</option>
              </select>
              <button class="btn btn-sm btn-light-primary" type="button" id="cms_videos_listar_btn"><i class="bi bi-arrow-clockwise"></i></button>
            </div>
            <div id="cms_videos_lista" class="cms-videos-list text-muted fs-7">Sin cargar.</div>
          </div>
        </div>

        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Salida</h3>
          </div>
          <div class="card-body">
            <pre class="cms-videos-output bg-light rounded p-3 mb-0 fs-8" id="cms_videos_salida">{}</pre>
          </div>
        </div>
      </div>

      <div class="col-xl-8">
        <div class="card mb-5">
          <div class="card-header">
            <h3 class="card-title">Editor TikTok</h3>
            <div class="card-toolbar">
              <button class="btn btn-sm btn-light" type="button" id="cms_videos_nuevo_btn"><i class="bi bi-file-earmark-plus"></i> Nuevo</button>
            </div>
          </div>
          <div class="card-body">
            <form id="cms_videos_form">
              <input type="hidden" id="cms_videos_id">
              <div class="row g-5">
                <div class="col-lg-8">
                  <div class="cms-videos-grid">
                    <div>
                      <label class="form-label">Titulo</label>
                      <input class="form-control form-control-solid" id="cms_videos_titulo" maxlength="180">
                    </div>
                    <div>
                      <label class="form-label">Slug</label>
                      <input class="form-control form-control-solid" id="cms_videos_slug" maxlength="220">
                    </div>
                    <div>
                      <label class="form-label">Tipo</label>
                      <select class="form-select form-select-solid" id="cms_videos_tipo">
                        <option value="demo_producto">Demo producto</option>
                        <option value="instalacion">Instalacion</option>
                        <option value="comparativo">Comparativo</option>
                        <option value="consejo_rapido">Consejo rapido</option>
                        <option value="unboxing">Unboxing</option>
                        <option value="uso_producto">Uso producto</option>
                        <option value="inspiracion">Inspiracion</option>
                        <option value="faq">FAQ</option>
                      </select>
                    </div>
                    <div>
                      <label class="form-label">Estado de trabajo</label>
                      <select class="form-select form-select-solid" id="cms_videos_estado">
                        <option value="borrador">Borrador</option>
                        <option value="pausado">Pausado</option>
                      </select>
                    </div>
                    <div>
                      <label class="form-label">URL TikTok</label>
                      <input class="form-control form-control-solid" id="cms_videos_video_url" placeholder="https://www.tiktok.com/@artiani/video/0000000000000000000">
                    </div>
                    <div>
                      <label class="form-label">Embed generado</label>
                      <input class="form-control form-control-solid" id="cms_videos_embed_url" placeholder="https://www.tiktok.com/player/v1/...">
                    </div>
                    <div>
                      <label class="form-label">Miniatura URL</label>
                      <input class="form-control form-control-solid" id="cms_videos_thumbnail_url" placeholder="/assets/media/cms/ecommerce/videos/thumbnail.webp">
                    </div>
                    <div>
                      <label class="form-label">ALT miniatura</label>
                      <input class="form-control form-control-solid" id="cms_videos_thumbnail_alt" maxlength="220">
                    </div>
                    <div>
                      <label class="form-label">Autor TikTok</label>
                      <input class="form-control form-control-solid" id="cms_videos_tiktok_author" maxlength="120" placeholder="artiani">
                    </div>
                    <div>
                      <label class="form-label">ID post TikTok</label>
                      <input class="form-control form-control-solid" id="cms_videos_tiktok_post_id" maxlength="80">
                    </div>
                    <div>
                      <label class="form-label">Duracion segundos</label>
                      <input class="form-control form-control-solid" id="cms_videos_duracion" type="number" min="0" step="1">
                    </div>
                    <div>
                      <label class="form-label">Orden</label>
                      <input class="form-control form-control-solid" id="cms_videos_orden" type="number" step="1" value="0">
                    </div>
                  </div>

                  <div class="form-check form-switch form-check-custom form-check-solid mt-5">
                    <input class="form-check-input" type="checkbox" id="cms_videos_destacado">
                    <label class="form-check-label" for="cms_videos_destacado">Destacado en listados</label>
                  </div>

                  <div class="mt-5">
                    <label class="form-label">Descripcion corta</label>
                    <textarea class="form-control form-control-solid cms-videos-textarea" id="cms_videos_descripcion_corta" maxlength="300"></textarea>
                  </div>
                  <div class="mt-5">
                    <label class="form-label">Descripcion larga</label>
                    <textarea class="form-control form-control-solid cms-videos-textarea" id="cms_videos_descripcion_larga"></textarea>
                  </div>
                  <div class="mt-5">
                    <label class="form-label">Copy TikTok</label>
                    <textarea class="form-control form-control-solid cms-videos-textarea" id="cms_videos_copy_tiktok"></textarea>
                  </div>
                  <div class="mt-5">
                    <label class="form-label">Hashtags</label>
                    <input class="form-control form-control-solid" id="cms_videos_hashtags" placeholder="#acuario #artiani #mascotas">
                  </div>
                  <div class="mt-5">
                    <label class="form-label">Texto de busqueda</label>
                    <textarea class="form-control form-control-solid cms-videos-textarea" id="cms_videos_texto_busqueda" placeholder="Palabras comerciales, sinonimos y temas del video"></textarea>
                  </div>
                </div>

                <div class="col-lg-4">
                  <div class="bg-light rounded p-4 mb-5 text-center">
                    <img class="cms-videos-preview" id="cms_videos_preview" alt="Preview miniatura">
                    <div class="text-muted fs-8 mt-3">Los listados publicos usan esta miniatura; TikTok se carga hasta click.</div>
                  </div>
                  <div class="mb-5">
                    <label class="form-label">Producto principal JSON</label>
                    <textarea class="form-control form-control-solid cms-videos-textarea" id="cms_videos_producto_principal_json" spellcheck="false">{
  "slug_producto": "",
  "id_publicacion": 0,
  "id_sku": 0
}</textarea>
                  </div>
                  <div class="mb-5">
                    <label class="form-label">Productos relacionados JSON</label>
                    <textarea class="form-control form-control-solid cms-videos-textarea" id="cms_videos_productos_json" spellcheck="false">[]</textarea>
                  </div>
                  <div class="mb-5">
                    <label class="form-label">Categorias JSON</label>
                    <textarea class="form-control form-control-solid cms-videos-textarea" id="cms_videos_categorias_json" spellcheck="false">[]</textarea>
                  </div>
                </div>
              </div>

              <div class="separator my-6"></div>
              <div class="cms-videos-grid">
                <div>
                  <label class="form-label">SEO title</label>
                  <input class="form-control form-control-solid" id="cms_videos_seo_title" maxlength="220">
                </div>
                <div>
                  <label class="form-label">SEO canonical</label>
                  <input class="form-control form-control-solid" id="cms_videos_seo_canonical" maxlength="260" placeholder="/videos/slug">
                </div>
                <div>
                  <label class="form-label">SEO description</label>
                  <input class="form-control form-control-solid" id="cms_videos_seo_description" maxlength="320">
                </div>
                <div>
                  <label class="form-label">OG image</label>
                  <input class="form-control form-control-solid" id="cms_videos_og_image" placeholder="Usa la miniatura si queda vacio">
                </div>
              </div>

              <div class="d-flex flex-wrap gap-2 mt-6">
                <button class="btn btn-primary" type="submit" id="cms_videos_guardar_btn"><i class="bi bi-save"></i> Guardar borrador</button>
                <button class="btn btn-light-success" type="button" id="cms_videos_publicar_btn"><i class="bi bi-check2-circle"></i> Publicar</button>
                <button class="btn btn-light-warning" type="button" id="cms_videos_pausar_btn"><i class="bi bi-pause-circle"></i> Pausar</button>
                <button class="btn btn-light" type="button" id="cms_videos_borrador_btn"><i class="bi bi-pencil"></i> Volver a borrador</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
  window.ERP_CSRF_TOKEN = "<?= htmlspecialchars(Sesionseguridad::csrfToken(), ENT_QUOTES, 'UTF-8') ?>";
</script>
<script src="/assets/js/custom/apps/erp/cms/videos.js?v=20260928-videos1"></script>
