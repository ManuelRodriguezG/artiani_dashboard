<?php
/**
 * IA: Codex GPT-6 | 2026-09-25. Regresion de contenido y agrupacion con datos reales y fixtures SQL.
 * Impacto: contrato frontend; solo SELECT dentro de transaccion READ ONLY, sin migraciones ni eventos.
 * Uso: php storage/uat/uat_ecommerce_presentacion_readonly.php
 */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$_SERVER['SERVER_NAME'] = 'panel.com.local';
chdir(__DIR__ . '/../../public');
require_once '../app/iniciador.php';
require_once '../app/modelos/EcommerceCatalogoPublico.php';
$checks = 0;
$db = null;
$fallo = null;
$resultados = array();
try {
  $modelo = new EcommerceCatalogoPublico();
  $db = privadoPresentacion($modelo, 'getConexion');
  if (!$db) { throw new RuntimeException('Conexion configurada no disponible'); }
  $db->exec('START TRANSACTION READ ONLY');
  $resultados['motor_bd'] = $db->query('SELECT VERSION()')->fetchColumn();

  foreach (array(
    "Texto plano\nSegundo parrafo" => "Texto plano\nSegundo parrafo",
    '<p class="x" onclick="alert(1)">Hola <strong>mundo</strong></p>' => '<p>Hola <strong>mundo</strong></p>',
    '<h3 style="color:red">Uso</h3><ul><li>Uno</li></ul>' => '<h3>Uso</h3><ul><li>Uno</li></ul>',
    '<script>SECRETO_SCRIPT</script><p>Seguro</p><iframe>SECRETO_IFRAME</iframe>' => '<p>Seguro</p>',
    '<form><p>NOTA_ADMIN</p></form><svg onload="alert(1)"><text>SVG</text></svg><p>Bien</p>' => '<p>Bien</p>',
    '<p><img src=x onerror=alert(1)>Hola<br>Bien</p>' => '<p>Hola<br>Bien</p>',
    '' => ''
  ) as $entrada => $esperado) {
    $real = privadoPresentacion($modelo, 'sanearDescripcionPublica', array($entrada));
    comprobarPresentacion($real === $esperado, 'Sanitizado allowlist: ' . substr($entrada, 0, 40));
    comprobarPresentacion(privadoPresentacion($modelo, 'sanearDescripcionPublica', array($real)) === $real, 'Saneado idempotente');
  }
  $plano = privadoPresentacion($modelo, 'textoPlanoSeoPublico', array('<p>Uno</p><p>Dos<script>privado</script></p>'));
  comprobarPresentacion($plano === 'Uno Dos', 'SEO conserva espacios y elimina contenido activo');
  $contrato = privadoPresentacion($modelo, 'contratoPresentacionCatalogoPublico');
  comprobarPresentacion($contrato['agrupacion_default'] === 'sku' && !$contrato['descripcion']['fallback_erp'], 'Contrato opt-in, sin fallback editorial');
  $informativa = privadoPresentacion($modelo, 'variantePreviewPublica', array(array('id_sku' => -1, 'id_publicacion' => 999,
    'precio' => null, 'moneda' => null, 'permite_cotizacion' => false, 'permite_whatsapp' => true,
    'imagen' => null, 'imagen_fuente' => ''), -1));
  comprobarPresentacion($informativa['precio'] === null && !$informativa['mostrar_precio'] && !$informativa['permite_cotizacion'] && $informativa['permite_whatsapp'], 'Variante informativa conserva permisos propios sin $0');
  comprobarPresentacion($informativa['imagen'] === null && $informativa['imagen_fuente'] === '', 'No prestar imagen de otra variante');
  $atributoSql = privadoPresentacion($modelo, 'sqlCoincidenciaAtributoSelector', array(':valor'));
  $atributoSql = str_replace(array('erp_catalogo_sku_atributos', 'erp_catalogo_atributos'), array(
    "(SELECT 11 id_sku, 1 id_atributo_erp, 'rojo' valor UNION ALL SELECT 12, 1, 'azul' UNION ALL SELECT 11, 2, 'azul')",
    "(SELECT 1 id_atributo_erp, 1 es_variante, 'activo' estatus, NULL unidad UNION ALL SELECT 2, 0, 'activo', NULL)"
  ), $atributoSql);
  $stmtAtributo = $db->prepare('SELECT s.id_sku FROM (SELECT 11 id_sku UNION ALL SELECT 12) s WHERE ' . $atributoSql);
  $stmtAtributo->execute(array(':valor' => '(^|[^[:alnum:]])azul([^[:alnum:]]|$)'));
  comprobarPresentacion(array_map('intval', $stmtAtributo->fetchAll(PDO::FETCH_COLUMN)) === array(12), 'Buscar atributo exclusivo sin considerar atributo administrativo');
  probarGruposFixture($modelo, $db);

  $sku = catalogoPresentacion($modelo, array('q' => 'churro', 'limite' => 12));
  $grupo = catalogoPresentacion($modelo, array('q' => 'churro', 'limite' => 12, 'agrupacion' => 'producto'));
  comprobarPresentacion($sku['agrupacion'] === 'sku' && $sku['paginacion']['total'] === 6, 'Compatibilidad SKU: seis churros del diagnostico');
  comprobarPresentacion($grupo['paginacion']['total'] === 2 && $grupo['paginacion']['total_skus'] === 6, 'Dos tarjetas y seis SKUs');
  $ids = array_column($grupo['items'], 'id_producto_erp');
  sort($ids);
  comprobarPresentacion($ids === array(50, 1016), 'Grupos reales, sin fusionar productos distintos');
  comprobarPresentacion($grupo['paginacion']['unidad'] === 'grupos', 'Total declara unidad visible');
  foreach ($grupo['items'] as $item) {
    comprobarPresentacion(!array_key_exists('descripcion_publica', $item), 'Card sin descripciones');
    comprobarPresentacion($item['id_sku'] === $item['grupo_producto']['seleccion_actual']['id_sku'], 'Identidad seleccionada real');
    comprobarPresentacion(strpos($item['url'], '/producto/') === 0, 'Sin URL nueva de grupo');
  }
  $grupo50 = array_values(array_filter($grupo['items'], function($item) { return $item['id_producto_erp'] === 50; }))[0];
  $preview = $grupo50['grupo_producto']['variantes_preview'];
  comprobarPresentacion(count($preview) === 5, 'Preview contiene cinco presentaciones');
  comprobarPresentacion(count(array_filter($preview, function($v) { return $v['actual']; })) === 1, 'Una seleccion actual en preview');
  parse_str(parse_url($grupo50['grupo_producto']['variantes_url'], PHP_URL_QUERY), $params);
  $todas = catalogoPresentacion($modelo, $params);
  comprobarPresentacion($todas['paginacion']['total'] === 5, 'Enlace de variantes contiene solo el producto seleccionado');
  $porSku = array_column($todas['items'], null, 'id_sku');
  foreach ($preview as $v) {
    $original = $porSku[$v['id_sku']];
    foreach (array('id_publicacion', 'slug', 'imagen', 'imagen_fuente', 'precio', 'moneda', 'permite_cotizacion', 'permite_whatsapp') as $campo) {
      comprobarPresentacion($v[$campo] === $original[$campo], 'Variante conserva ' . $campo);
    }
    comprobarPresentacion(is_array($v['atributos_selector']), 'Atributos de selector explicitos');
  }
  $paginas = array();
  foreach (array(1, 2) as $pagina) {
    $parte = catalogoPresentacion($modelo, array('q' => 'churro', 'agrupacion' => 'producto', 'limite' => 1, 'pagina' => $pagina));
    $paginas = array_merge($paginas, array_column($parte['items'], 'id_publicacion'));
    parse_str(parse_url($parte['paginacion']['primera'], PHP_URL_QUERY), $enlace);
    comprobarPresentacion($enlace['agrupacion'] === 'producto' && $enlace['q'] === 'churro', 'Enlaces conservan modo y filtros');
  }
  comprobarPresentacion($paginas === array_column($grupo['items'], 'id_publicacion'), 'Mismo representante/orden al cambiar limite');
  $especifico = catalogoPresentacion($modelo, array('q' => 'churro blanco para peces 100', 'agrupacion' => 'producto'));
  comprobarPresentacion(count($especifico['items']) === 1 && $especifico['items'][0]['id_sku'] === 1759, 'Representante coincide con presentacion buscada');
  $erizo = respuestaPresentacion($modelo->busquedaInteligentePublica(array('q' => 'Alimento para erizo', 'agrupacion' => 'producto', 'limite' => 12)));
  comprobarPresentacion($erizo['total'] === 8 && count(array_unique(array_column($erizo['items'], 'id_producto_erp'))) === 8, 'Los ocho alimentos siguen separados');
  comprobarPresentacion(strpos($erizo['paginacion']['primera'], '/ecommercePublico/busqueda?') === 0, 'Busqueda conserva endpoint');
  $detalle = respuestaPresentacion($modelo->productoPublico('alimento-churro-blanco-para-peces-100g'));
  comprobarPresentacion($detalle['item']['id_sku'] === 1759 && $detalle['item']['descripcion_publica'] === '', 'No importar texto de 4 kg en ficha 100 g');
  comprobarPresentacion($detalle['item']['descripcion'] === '' && $detalle['item']['descripcion_publica_fuente'] === 'publicacion_ecommerce', 'Compatibilidad no recupera notas internas');
  comprobarPresentacion(strpos($detalle['seo']['description'], '4 kgrs') === false && strpos($detalle['seo']['og_description'], 'Venta por kilos') === false, 'SEO sin nota de granel heredada');
  $seoItems = privadoPresentacion($modelo, 'seoProductosPublicosItems', array(5000));
  $seoChurro = array_values(array_filter($seoItems, function($v) { return $v['slug'] === 'alimento-churro-blanco-para-peces-100g'; }));
  comprobarPresentacion(count($seoChurro) === 1 && $seoChurro[0]['description'] === '', 'Universo SEO conserva URL sin importar descripcion ERP');
  $prep = respuestaPresentacion($modelo->prepararPublicacion(array('id_sku' => 1759)));
  comprobarPresentacion($prep['publicacion_sugerida']['descripcion_publica'] === '', 'Preparar conserva vacio intencional');
  $plan = respuestaPresentacion($modelo->planGuardarPublicacion(array('id_sku' => 1759, 'descripcion_publica' => '')));
  comprobarPresentacion($plan['publicacion_normalizada']['descripcion_publica'] === '', 'Plan de guardado mantiene vacio, sin ejecutar SQL');
  $planHtml = respuestaPresentacion($modelo->planGuardarPublicacion(array('id_sku' => 1759, 'descripcion_publica' => '<p onclick="alert(1)">Editorial</p><script>no</script>')));
  comprobarPresentacion($planHtml['publicacion_normalizada']['descripcion_publica'] === '<p>Editorial</p>', 'Plan de guardado sanea HTML antes de persistir');
  $categorias = $db->query('SELECT pc.id_categoria_erp, pc.es_principal, c.id_categoria_padre FROM erp_catalogo_producto_categorias pc
    INNER JOIN erp_catalogo_categorias c ON c.id_categoria_erp=pc.id_categoria_erp WHERE pc.id_producto_erp=50')->fetchAll(PDO::FETCH_ASSOC);
  $filtros = array(array('marca_id' => $grupo50['marca_obj']['id']));
  foreach ($categorias as $categoria) {
    $filtros[] = array('categoria_id' => $categoria['id_categoria_erp']);
    if ($categoria['id_categoria_padre']) {
      $filtros[] = array('categoria_id' => $categoria['id_categoria_padre'], 'incluir_hijos' => 1, 'marca_id' => $grupo50['marca_obj']['id']);
    }
  }
  foreach ($filtros as $filtro) {
    $base = array_merge(array('q' => 'churro', 'limite' => 60, 'orden' => 'precio_desc'), $filtro);
    $individuales = catalogoPresentacion($modelo, $base);
    $agrupados = catalogoPresentacion($modelo, array_merge($base, array('agrupacion' => 'producto')));
    comprobarPresentacion($agrupados['paginacion']['total'] === count(array_unique(array_column($individuales['items'], 'id_producto_erp'))), 'Marca/categoria/rama: filtrar antes de agrupar');
    foreach ($agrupados['items'] as $item) {
      comprobarPresentacion(in_array($item['id_publicacion'], array_column($individuales['items'], 'id_publicacion'), true), 'Representante pertenece al conjunto filtrado');
    }
    parse_str(parse_url($agrupados['paginacion']['primera'], PHP_URL_QUERY), $urlFiltros);
    comprobarPresentacion($urlFiltros['agrupacion'] === 'producto' && $urlFiltros['orden'] === 'precio_desc', 'Paginacion mantiene modo y orden combinados');
  }
  $sg = respuestaPresentacion($modelo->busquedaSugerenciasPublicas(array('q' => 'churro', 'agrupacion' => 'producto', 'limite' => 6)));
  $bg = respuestaPresentacion($modelo->busquedaInteligentePublica(array('q' => 'churro', 'agrupacion' => 'producto', 'limite' => 12)));
  comprobarPresentacion(array_column($sg['grupos']['productos'], 'valor') === array_column($bg['items'], 'slug'), 'Sugerencias agrupadas usan mismo representante y ranking');
  $incorrecto = $modelo->catalogoPublico(array('agrupacion' => 'inexistente'));
  comprobarPresentacion(!empty($incorrecto['error']), 'Modo invalido no se simula como exito');
  $resultados['churro'] = array('skus' => 6, 'tarjetas' => 2, 'variantes_grupo_50' => count($preview), 'descripcion_100g' => $detalle['item']['descripcion_publica']);
  $resultados['erizo_tarjetas'] = $erizo['total'];
} catch (Throwable $e) {
  $fallo = $e->getMessage();
} finally {
  if ($db && $db->inTransaction()) { $db->rollBack(); }
}
echo json_encode(array('ok' => $fallo === null, 'checks' => $checks, 'fallo' => $fallo, 'resultados' => $resultados), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
exit($fallo === null ? 0 : 1);

/** IA: Codex GPT-6 | 2026-09-25. Acceso controlado a helpers para pruebas read-only. */
function privadoPresentacion($objeto, $metodo, $args = array()) {
  $ref = new ReflectionMethod($objeto, $metodo);
  $ref->setAccessible(true);
  return $ref->invokeArgs($objeto, $args);
}
/** IA: Codex GPT-6 | 2026-09-25. No convierte fallos en resultados vacios. */
function respuestaPresentacion($r) {
  if (!empty($r['error'])) { throw new RuntimeException($r['mensaje']); }
  return $r['depurar'];
}
/** IA: Codex GPT-6 | 2026-09-25. Invoca listado real sin operaciones de escritura. */
function catalogoPresentacion($modelo, $params) { return respuestaPresentacion($modelo->catalogoPublico($params)); }
/** IA: Codex GPT-6 | 2026-09-25. Asercion de contrato que falla el proceso. */
function comprobarPresentacion($ok, $mensaje) {
  global $checks;
  $checks++;
  if (!$ok) { throw new RuntimeException($mensaje); }
}
/** IA: Codex GPT-6 | 2026-09-25. Fixtures con SELECT, no tablas: paginas llenas y permisos/precio independientes. */
function probarGruposFixture($modelo, $db) {
  $filas = array();
  for ($grupo = 1; $grupo <= 16; $grupo++) {
    foreach (array(1, 2) as $variante) {
      $id = $grupo * 10 + $variante;
      $filas[] = "SELECT $id id_publicacion, $grupo id_producto_erp, 'Producto $id' titulo_publico, 0 destacado,
        $variante orden_publicacion, '2026-09-25' fecha_orden_publicacion, " . ($variante === 1 ? 'NULL' : $grupo) . " precio,
        " . ($variante === 1 ? 0 : 1) . " mostrar_precio, " . ($variante === 1 ? 0 : 1) . " permite_cotizacion,
        $variante relevancia_busqueda, '" . ($variante === 1 ? 'rojo' : 'azul') . "' color";
    }
  }
  $base = implode(' UNION ALL ', $filas);
  foreach (array('relevancia', 'nombre', 'precio_asc', 'precio_desc', 'recientes') as $orden) {
    $sql = privadoPresentacion($modelo, 'sqlCatalogoAgrupadoPublico', array($base, $orden, true));
    $todo = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    comprobarPresentacion(count($todo) === 16, 'Dieciseis grupos: ' . $orden);
    foreach (array(6, 12, 24) as $limite) {
      $recorrido = array();
      for ($offset = 0; $offset < 16; $offset += $limite) {
        $pagina = $db->query($sql . ' LIMIT ' . $limite . ' OFFSET ' . $offset)->fetchAll(PDO::FETCH_ASSOC);
        comprobarPresentacion(count($pagina) === min($limite, 16 - $offset), 'Pagina llena antes de ultima');
        $recorrido = array_merge($recorrido, $pagina);
      }
      comprobarPresentacion($recorrido === $todo, 'Prefijo/orden estable con limite ' . $limite);
    }
    if (strpos($orden, 'precio_') === 0) {
      foreach ($todo as $item) { comprobarPresentacion((int) $item['mostrar_precio'] === 1, 'No elegir precio oculto como precio desde'); }
    }
  }
  $azules = 'SELECT * FROM (' . $base . ") publicados WHERE color='azul'";
  $sql = privadoPresentacion($modelo, 'sqlCatalogoAgrupadoPublico', array($azules, 'nombre', false));
  foreach ($db->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $item) {
    comprobarPresentacion($item['color'] === 'azul', 'Representante respeta atributo exclusivo filtrado');
  }
}
