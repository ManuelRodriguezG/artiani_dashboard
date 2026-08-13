<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-08-12.
 * Proposito: sembrar configuracion inicial CMS ecommerce despues de DDL y respaldo validado.
 * Impacto: inserta estructura base de contenido y frontend; no crea contenido comercial ni activa endpoints POST.
 * Contrato: requiere --respaldo=RUTA existente; idempotente; no modifica catalogo, precios, inventario ni publicaciones de producto.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/EcommercePublicoEsquema.php";

class CmsSeedDb extends CRUD {
  public function db() {
    return $this->getConexion();
  }
}

$opciones = getopt("", array("respaldo:"));
$respaldo = isset($opciones["respaldo"]) ? (string) $opciones["respaldo"] : "";
$bloqueos = validarRespaldoCmsSeed($respaldo);

if (!empty($bloqueos)) {
  echo json_encode(array(
    "ok" => false,
    "modo" => "seed_authorized",
    "bloqueos" => $bloqueos,
    "seed_ejecutado" => false
  ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
  exit(2);
}

$esquema = new EcommercePublicoEsquema();
$auditoriaContenido = $esquema->auditarCmsContenido();
$auditoriaFrontend = $esquema->auditarCmsFrontend();
if ((int) valorCmsSeed($auditoriaContenido, array("depurar", "tablas_faltantes"), 99) !== 0) {
  $bloqueos[] = "tablas_contenido_pendientes";
}
if ((int) valorCmsSeed($auditoriaFrontend, array("depurar", "tablas_faltantes"), 99) !== 0) {
  $bloqueos[] = "tablas_frontend_pendientes";
}
if (!empty($bloqueos)) {
  echo json_encode(array(
    "ok" => false,
    "modo" => "seed_authorized",
    "bloqueos" => $bloqueos,
    "seed_ejecutado" => false
  ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
  exit(2);
}

$db = (new CmsSeedDb())->db();
$resumen = array();

try {
  $db->beginTransaction();

  $idPlantilla = seedPlantillaContenido($db);
  $resumen["plantilla_contenido"] = "artiani_default";
  $resumen["slots"] = seedSlotsContenido($db, $idPlantilla);

  $idTema = seedTemaFrontend($db);
  $resumen["tema"] = "wokiee_artiani";
  $layouts = seedLayoutsFrontend($db, $idTema);
  $componentes = seedComponentesFrontend($db, $idTema);
  $plantillas = seedPlantillasFrontend($db, $idTema, $layouts);
  $resumen["layouts"] = count($layouts);
  $resumen["componentes"] = count($componentes);
  $resumen["plantillas_vista"] = count($plantillas);
  $resumen["secciones"] = seedSeccionesFrontend($db, $plantillas, $componentes);
  $resumen["activaciones"] = seedActivacionesFrontend($db, $plantillas);

  $db->commit();
} catch (Exception $e) {
  if ($db->inTransaction()) {
    $db->rollBack();
  }
  echo json_encode(array(
    "ok" => false,
    "modo" => "seed_authorized",
    "seed_ejecutado" => false,
    "mensaje" => $e->getMessage()
  ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
  exit(1);
}

echo json_encode(array(
  "ok" => true,
  "modo" => "seed_authorized",
  "senal_persistencia" => "cms_seed_base_aplicado",
  "respaldo" => array(
    "ruta" => $respaldo,
    "tamano_bytes" => filesize($respaldo)
  ),
  "resumen" => $resumen,
  "guardrails" => array(
    "no_crea_contenido_comercial" => true,
    "no_activa_endpoints_post" => true,
    "no_cambia_api_publica" => true,
    "no_modifica_catalogo" => true,
    "no_modifica_inventario" => true
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function seedPlantillaContenido($db) {
  $config = json_encode(array(
    "canal" => "catalogo_publico",
    "slots_iniciales" => array("home.hero", "home.promo", "home.categorias", "home.destacados", "categoria.banner", "categoria.productos", "catalogo.encabezado"),
    "guardrails" => array("headless" => true, "frontend_renderiza" => true)
  ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  return upsertId($db,
    "INSERT INTO erp_ecommerce_plantillas (codigo, nombre, descripcion, version_plantilla, estatus, activa, config_json)
     VALUES (:codigo, :nombre, :descripcion, :version_plantilla, :estatus, :activa, :config_json)
     ON DUPLICATE KEY UPDATE id_plantilla = LAST_INSERT_ID(id_plantilla), nombre = VALUES(nombre), descripcion = VALUES(descripcion), version_plantilla = VALUES(version_plantilla), estatus = VALUES(estatus), activa = VALUES(activa), config_json = VALUES(config_json), fecha_actualizacion = NOW()",
    array(
      ":codigo" => "artiani_default",
      ":nombre" => "Artiani default",
      ":descripcion" => "Plantilla editorial base para ecommerce Artiani.",
      ":version_plantilla" => "1.0.0",
      ":estatus" => "publicado",
      ":activa" => 1,
      ":config_json" => $config
    )
  );
}

function seedSlotsContenido($db, $idPlantilla) {
  $slots = array(
    array("home.hero", "Hero home", "home", array("hero_banner"), 1, 1, 1),
    array("home.promo", "Promociones home", "home", array("promo_strip", "content_html_safe"), 3, 0, 2),
    array("home.categorias", "Categorias home", "home", array("image_card_grid"), 2, 0, 3),
    array("home.destacados", "Destacados home", "home", array("product_collection"), 4, 0, 4),
    array("categoria.banner", "Banner categoria", "categoria", array("category_banner", "hero_banner"), 1, 0, 1),
    array("categoria.productos", "Productos categoria", "categoria", array("product_collection"), 3, 0, 2),
    array("catalogo.encabezado", "Encabezado catalogo", "catalogo", array("promo_strip", "content_html_safe"), 2, 0, 1)
  );
  foreach ($slots as $slot) {
    $stmt = $db->prepare(
      "INSERT INTO erp_ecommerce_plantilla_slots (id_plantilla, codigo, nombre, pagina, tipos_bloque_json, max_bloques, requerido, orden, estatus)
       VALUES (:id_plantilla, :codigo, :nombre, :pagina, :tipos_bloque_json, :max_bloques, :requerido, :orden, 'activo')
       ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), pagina = VALUES(pagina), tipos_bloque_json = VALUES(tipos_bloque_json), max_bloques = VALUES(max_bloques), requerido = VALUES(requerido), orden = VALUES(orden), estatus = 'activo', fecha_actualizacion = NOW()"
    );
    $stmt->execute(array(
      ":id_plantilla" => $idPlantilla,
      ":codigo" => $slot[0],
      ":nombre" => $slot[1],
      ":pagina" => $slot[2],
      ":tipos_bloque_json" => json_encode($slot[3], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
      ":max_bloques" => $slot[4],
      ":requerido" => $slot[5],
      ":orden" => $slot[6]
    ));
  }
  return count($slots);
}

function seedTemaFrontend($db) {
  $config = json_encode(array("primer_tema" => true, "fuente_visual" => "wokiee", "otros_temas_futuros" => true), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  return upsertId($db,
    "INSERT INTO erp_ecommerce_frontend_temas (codigo, nombre, proveedor, descripcion, version_tema, estatus, activo, config_json)
     VALUES (:codigo, :nombre, :proveedor, :descripcion, '1.0.0', 'publicado', 1, :config_json)
     ON DUPLICATE KEY UPDATE id_tema = LAST_INSERT_ID(id_tema), nombre = VALUES(nombre), proveedor = VALUES(proveedor), descripcion = VALUES(descripcion), estatus = 'publicado', activo = 1, config_json = VALUES(config_json), fecha_actualizacion = NOW()",
    array(
      ":codigo" => "wokiee_artiani",
      ":nombre" => "Wokiee Artiani",
      ":proveedor" => "ThemeForest/Wokiee",
      ":descripcion" => "Primer tema visual conectado al CMS.",
      ":config_json" => $config
    )
  );
}

function seedLayoutsFrontend($db, $idTema) {
  $layouts = array(
    "storefront_wokiee_v1" => array("Storefront Wokiee v1", "Layout base para home."),
    "category_wokiee_v1" => array("Category Wokiee v1", "Layout base para categoria."),
    "catalog_wokiee_v1" => array("Catalog Wokiee v1", "Layout base para catalogo.")
  );
  $ids = array();
  foreach ($layouts as $codigo => $datos) {
    $ids[$codigo] = upsertId($db,
      "INSERT INTO erp_ecommerce_frontend_layouts (id_tema, codigo, nombre, descripcion, version_layout, estatus, config_json)
       VALUES (:id_tema, :codigo, :nombre, :descripcion, '1.0.0', 'publicado', '{}')
       ON DUPLICATE KEY UPDATE id_layout = LAST_INSERT_ID(id_layout), id_tema = VALUES(id_tema), nombre = VALUES(nombre), descripcion = VALUES(descripcion), estatus = 'publicado', fecha_actualizacion = NOW()",
      array(":id_tema" => $idTema, ":codigo" => $codigo, ":nombre" => $datos[0], ":descripcion" => $datos[1])
    );
  }
  return $ids;
}

function seedComponentesFrontend($db, $idTema) {
  $componentes = componentesSeed();
  $ids = array();
  foreach ($componentes as $codigo => $datos) {
    $ids[$codigo] = upsertId($db,
      "INSERT INTO erp_ecommerce_frontend_componentes (id_tema, codigo, nombre, descripcion, bloques_permitidos_json, variantes_json, slots_compatibles_json, estatus, config_json)
       VALUES (:id_tema, :codigo, :nombre, :descripcion, :bloques, :variantes, :slots, 'activo', '{}')
       ON DUPLICATE KEY UPDATE id_componente = LAST_INSERT_ID(id_componente), id_tema = VALUES(id_tema), nombre = VALUES(nombre), descripcion = VALUES(descripcion), bloques_permitidos_json = VALUES(bloques_permitidos_json), variantes_json = VALUES(variantes_json), slots_compatibles_json = VALUES(slots_compatibles_json), estatus = 'activo', fecha_actualizacion = NOW()",
      array(
        ":id_tema" => $idTema,
        ":codigo" => $codigo,
        ":nombre" => $datos["nombre"],
        ":descripcion" => $datos["descripcion"],
        ":bloques" => json_encode($datos["bloques"], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ":variantes" => json_encode($datos["variantes"], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ":slots" => json_encode($datos["slots"], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
      )
    );
  }
  return $ids;
}

function seedPlantillasFrontend($db, $idTema, $layouts) {
  $plantillas = array(
    "wokiee_home_default" => array("Wokiee home default", "home", "storefront_wokiee_v1"),
    "wokiee_categoria_default" => array("Wokiee categoria default", "categoria", "category_wokiee_v1"),
    "wokiee_catalogo_default" => array("Wokiee catalogo default", "catalogo", "catalog_wokiee_v1")
  );
  $ids = array();
  foreach ($plantillas as $codigo => $datos) {
    $ids[$codigo] = upsertId($db,
      "INSERT INTO erp_ecommerce_frontend_plantillas (id_tema, id_layout, codigo, nombre, pagina, version_plantilla, estatus, config_json)
       VALUES (:id_tema, :id_layout, :codigo, :nombre, :pagina, '1.0.0', 'publicado', '{}')
       ON DUPLICATE KEY UPDATE id_plantilla_vista = LAST_INSERT_ID(id_plantilla_vista), id_tema = VALUES(id_tema), id_layout = VALUES(id_layout), nombre = VALUES(nombre), pagina = VALUES(pagina), estatus = 'publicado', fecha_actualizacion = NOW()",
      array(":id_tema" => $idTema, ":id_layout" => $layouts[$datos[2]], ":codigo" => $codigo, ":nombre" => $datos[0], ":pagina" => $datos[1])
    );
  }
  return $ids;
}

function seedSeccionesFrontend($db, $plantillas, $componentes) {
  $secciones = array(
    array("wokiee_home_default", "home.hero", "HeroSlider", "full_width", 1),
    array("wokiee_home_default", "home.promo", "PromoStrip", "compact", 2),
    array("wokiee_home_default", "home.categorias", "CategoryGrid", "cards_4", 3),
    array("wokiee_home_default", "home.destacados", "ProductCarousel", "compact_cards", 4),
    array("wokiee_categoria_default", "categoria.banner", "HeroSlider", "boxed", 1),
    array("wokiee_categoria_default", "categoria.productos", "ProductCarousel", "wide_cards", 2),
    array("wokiee_catalogo_default", "catalogo.encabezado", "SafeHtmlBlock", "wide", 1)
  );
  foreach ($secciones as $seccion) {
    $idPlantilla = $plantillas[$seccion[0]];
    $idComponente = $componentes[$seccion[2]];
    $idExistente = buscarId($db, "SELECT id_seccion FROM erp_ecommerce_frontend_plantilla_secciones WHERE id_plantilla_vista = :id_plantilla_vista AND slot_codigo = :slot_codigo LIMIT 1", array(":id_plantilla_vista" => $idPlantilla, ":slot_codigo" => $seccion[1]));
    if ($idExistente > 0) {
      $stmt = $db->prepare("UPDATE erp_ecommerce_frontend_plantilla_secciones SET id_componente = :id_componente, variante = :variante, orden = :orden, estatus = 'activo', fecha_actualizacion = NOW() WHERE id_seccion = :id_seccion");
      $stmt->execute(array(":id_componente" => $idComponente, ":variante" => $seccion[3], ":orden" => $seccion[4], ":id_seccion" => $idExistente));
    } else {
      $stmt = $db->prepare("INSERT INTO erp_ecommerce_frontend_plantilla_secciones (id_plantilla_vista, id_componente, slot_codigo, variante, orden, estatus, config_json) VALUES (:id_plantilla_vista, :id_componente, :slot_codigo, :variante, :orden, 'activo', '{}')");
      $stmt->execute(array(":id_plantilla_vista" => $idPlantilla, ":id_componente" => $idComponente, ":slot_codigo" => $seccion[1], ":variante" => $seccion[3], ":orden" => $seccion[4]));
    }
  }
  return count($secciones);
}

function seedActivacionesFrontend($db, $plantillas) {
  $activaciones = array(
    array("wokiee_home_default", "home", "*"),
    array("wokiee_categoria_default", "categoria", "{slug_categoria}"),
    array("wokiee_catalogo_default", "catalogo", "*")
  );
  foreach ($activaciones as $activacion) {
    $idPlantilla = $plantillas[$activacion[0]];
    $idExistente = buscarId($db, "SELECT id_plantilla_activa FROM erp_ecommerce_frontend_plantilla_activas WHERE pagina = :pagina AND canal = 'catalogo_publico' AND contexto_clave = :contexto_clave LIMIT 1", array(":pagina" => $activacion[1], ":contexto_clave" => $activacion[2]));
    if ($idExistente > 0) {
      $stmt = $db->prepare("UPDATE erp_ecommerce_frontend_plantilla_activas SET id_plantilla_vista = :id_plantilla_vista, estatus = 'activa', fecha_actualizacion = NOW() WHERE id_plantilla_activa = :id_plantilla_activa");
      $stmt->execute(array(":id_plantilla_vista" => $idPlantilla, ":id_plantilla_activa" => $idExistente));
    } else {
      $stmt = $db->prepare("INSERT INTO erp_ecommerce_frontend_plantilla_activas (id_plantilla_vista, pagina, canal, contexto_clave, estatus) VALUES (:id_plantilla_vista, :pagina, 'catalogo_publico', :contexto_clave, 'activa')");
      $stmt->execute(array(":id_plantilla_vista" => $idPlantilla, ":pagina" => $activacion[1], ":contexto_clave" => $activacion[2]));
    }
  }
  return count($activaciones);
}

function componentesSeed() {
  return array(
    "HeroSlider" => array("nombre" => "Hero slider", "descripcion" => "Renderiza banners principales o de categoria.", "bloques" => array("hero_banner", "category_banner"), "variantes" => array("full_width", "boxed", "split"), "slots" => array("home.hero", "categoria.banner")),
    "PromoStrip" => array("nombre" => "Tira promocional", "descripcion" => "Renderiza textos promocionales.", "bloques" => array("promo_strip"), "variantes" => array("single", "stacked", "compact"), "slots" => array("home.promo", "catalogo.encabezado")),
    "CategoryGrid" => array("nombre" => "Grid de categorias", "descripcion" => "Renderiza cards de categorias.", "bloques" => array("image_card_grid"), "variantes" => array("cards_3", "cards_4", "mosaic"), "slots" => array("home.categorias")),
    "ProductCarousel" => array("nombre" => "Carrusel de productos", "descripcion" => "Renderiza colecciones dinamicas de productos.", "bloques" => array("product_collection"), "variantes" => array("compact_cards", "wide_cards", "simple_row"), "slots" => array("home.destacados", "categoria.productos")),
    "ImageCardGrid" => array("nombre" => "Cards con imagen", "descripcion" => "Renderiza grids editoriales con imagen.", "bloques" => array("image_card_grid"), "variantes" => array("two_columns", "three_columns", "editorial"), "slots" => array("home.categorias", "home.promo")),
    "SafeHtmlBlock" => array("nombre" => "Contenido HTML seguro", "descripcion" => "Renderiza contenido editorial sanitizado.", "bloques" => array("content_html_safe"), "variantes" => array("narrow", "wide", "accordion"), "slots" => array("catalogo.encabezado", "home.promo"))
  );
}

function upsertId($db, $sql, $params) {
  $stmt = $db->prepare($sql);
  $stmt->execute($params);
  return (int) $db->lastInsertId();
}

function buscarId($db, $sql, $params) {
  $stmt = $db->prepare($sql);
  $stmt->execute($params);
  $id = $stmt->fetchColumn();
  return $id === false ? 0 : (int) $id;
}

function validarRespaldoCmsSeed($respaldo) {
  if ($respaldo === "") {
    return array("respaldo_requerido");
  }
  if (!file_exists($respaldo)) {
    return array("respaldo_no_existe");
  }
  if ((int) filesize($respaldo) <= 0) {
    return array("respaldo_vacio");
  }
  return array();
}

function valorCmsSeed($datos, $ruta, $default = null) {
  $actual = $datos;
  foreach ($ruta as $segmento) {
    if (!is_array($actual) || !array_key_exists($segmento, $actual)) {
      return $default;
    }
    $actual = $actual[$segmento];
  }
  return $actual;
}
