<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-09-28.
 * Proposito: validar contratos read-only del modulo CMS Blog.
 * Impacto: confirma manifest, listado publico, relaciones, estado admin, plan DDL y sanitizacion sin escribir BD.
 * Contrato: read-only; no ejecuta DDL, no guarda publicaciones, no registra analytics y no toca catalogo/inventario.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/EcommerceBlogPublico.php";
require_once "../app/modelos/EcommercePublicoEsquema.php";

$blog = new EcommerceBlogPublico();
$esquema = new EcommercePublicoEsquema();

$manifest = $blog->manifestPublico();
$listado = $blog->blogPublico(array("pagina" => 1, "limite" => 2, "q" => "pecera"));
$listadoDemo = $blog->blogPublico(array("demo" => 1));
$detalleDemo = $blog->blogDetallePublico("guia-acuario-comunitario-artiani", array("demo" => 1));
$busqueda = $blog->buscarBlogPublico("pecera", 2);
$producto = $blog->contenidoProductoPublico("filtro-cascada-sunny-shf-600");
$categoria = $blog->contenidoCategoriaPublica("peces/filtracion/filtros");
$auditoria = $esquema->auditarCmsBlog();
$plan = $esquema->planActualizarCmsBlog(false);
$estadoAdmin = $blog->adminEstado($auditoria, $plan);

$sanitizador = new ReflectionMethod("EcommerceBlogPublico", "sanitizarContenidoHtmlBlog");
$sanitizador->setAccessible(true);
$htmlSanitizado = $sanitizador->invoke(
  $blog,
  "<h2 onclick=alert(1)>Titulo</h2><p>Texto <a href=\"javascript:alert(1)\">x</a><a href=\"/blog/ok\" target=\"_blank\">ok</a><script>alert(1)</script><img src=\"/img.webp\" onerror=\"x\" alt=\"Alt\"></p>"
);

$planDepurar = isset($plan["depurar"]) && is_array($plan["depurar"]) ? $plan["depurar"] : array();
$auditoriaDepurar = isset($auditoria["depurar"]) && is_array($auditoria["depurar"]) ? $auditoria["depurar"] : array();

$ok = empty($manifest["error"])
  && empty($listado["error"])
  && empty($listadoDemo["error"])
  && !empty($listadoDemo["depurar"]["demo"])
  && count($listadoDemo["depurar"]["items"] ?? array()) === 1
  && empty($detalleDemo["error"])
  && !empty($detalleDemo["depurar"]["demo"])
  && !empty($detalleDemo["depurar"]["publicacion"]["contenido_html"])
  && count($detalleDemo["depurar"]["productos_relacionados"] ?? array()) >= 3
  && count($detalleDemo["depurar"]["bloques_interactivos"] ?? array()) >= 1
  && is_array($busqueda)
  && empty($producto["error"])
  && empty($categoria["error"])
  && empty($auditoria["error"])
  && empty($estadoAdmin["error"])
  && !empty($planDepurar["read_only"])
  && intval($planDepurar["ddl_total"] ?? 0) >= 8
  && intval($auditoriaDepurar["tablas_total"] ?? 0) >= 8
  && strpos($htmlSanitizado, "<script") === false
  && stripos($htmlSanitizado, "javascript:") === false
  && stripos($htmlSanitizado, "onclick") === false
  && stripos($htmlSanitizado, "onerror") === false
  && strpos($htmlSanitizado, 'href="/blog/ok"') !== false
  && strpos($htmlSanitizado, 'rel="noopener noreferrer"') !== false;

echo json_encode(array(
  "ok" => $ok,
  "modo" => "read-only",
  "senal_cms_blog" => $ok ? "verde_contrato_blog_readonly" : "revisar_contrato_blog",
  "contratos" => array(
    "manifest_ok" => empty($manifest["error"]),
    "listado_error" => !empty($listado["error"]),
    "listado_estado" => $listado["depurar"]["estado"] ?? "schema_disponible_o_con_datos",
    "demo_items" => count($listadoDemo["depurar"]["items"] ?? array()),
    "demo_detalle_ok" => empty($detalleDemo["error"]) && !empty($detalleDemo["depurar"]["publicacion"]["contenido_html"]),
    "busqueda_items" => count($busqueda),
    "producto_items" => count($producto["depurar"]["items"] ?? array()),
    "categoria_items" => count($categoria["depurar"]["items"] ?? array()),
    "admin_estado_ok" => empty($estadoAdmin["error"])
  ),
  "schema" => array(
    "tablas_total" => intval($auditoriaDepurar["tablas_total"] ?? 0),
    "tablas_faltantes" => intval($auditoriaDepurar["tablas_faltantes"] ?? 0),
    "plan_readonly" => !empty($planDepurar["read_only"]),
    "ddl_total" => intval($planDepurar["ddl_total"] ?? 0),
    "ddl_pendientes" => intval($planDepurar["ddl_pendientes"] ?? 0)
  ),
  "sanitizacion" => array(
    "ok" => strpos($htmlSanitizado, "<script") === false
      && stripos($htmlSanitizado, "javascript:") === false
      && stripos($htmlSanitizado, "onclick") === false
      && stripos($htmlSanitizado, "onerror") === false,
    "html_sanitizado" => $htmlSanitizado
  ),
  "guardrails" => array(
    "no_escribe_bd" => true,
    "no_ejecuta_ddl" => true,
    "no_guarda_publicaciones" => true,
    "no_registra_analytics" => true,
    "no_catalogo" => true,
    "no_inventario" => true
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
