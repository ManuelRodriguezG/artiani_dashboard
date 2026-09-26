<?php
/**
 * IA: Codex GPT-5 | Fecha: 2026-09-25.
 * Proposito: comprobar activacion del contrato publico antes de integrar frontend.
 * Impacto: solo GET, o modelo local dentro de READ ONLY; no publica ni cambia datos.
 * Contrato: --base=https://sys.artiani.com.mx/ecommercePublico o --model; exit 1 ante fallo.
 * Los conteos de churro/erizo son fixtures publicados del diagnostico del 2026-09-25.
 */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$opciones = getopt('', array('base:', 'model'));
$modelo = null;
$conexion = null;
$checks = 0;
$peticiones = 0;
$salida = array();
$codigo = 0;
$base = rtrim((string) ($opciones['base'] ?? ''), '/');

/** IA: Codex GPT-5 | 2026-09-25. Falla explicitamente; nunca confunde HTTP 200 con contrato actualizado. */
function verificarRelease($condicion, $mensaje) {
  global $checks;
  $checks++;
  if (!$condicion) { throw new RuntimeException($mensaje); }
}

/** IA: Codex GPT-5 | 2026-09-25. Transporte read-only; exige HTTP 200 y JSON de exito sin seguir redirecciones. */
function consultarRelease($endpoint, $params = array()) {
  global $modelo, $base, $peticiones;
  $peticiones++;
  if ($modelo) {
    $metodos = array('catalogo_manifest' => 'catalogoManifestPublico', 'catalogo' => 'catalogoPublico',
      'busqueda' => 'busquedaInteligentePublica', 'busqueda_sugerencias' => 'busquedaSugerenciasPublicas');
    $respuesta = strpos($endpoint, 'producto/') === 0
      ? $modelo->productoPublico(substr($endpoint, strlen('producto/')))
      : $modelo->{$metodos[$endpoint]}($params);
  } else {
    $url = $base . '/' . $endpoint . ($params ? '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986) : '');
    $ch = curl_init($url);
    curl_setopt_array($ch, array(CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 15,
      CURLOPT_TIMEOUT => 90, CURLOPT_FOLLOWLOCATION => false,
      CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2,
      CURLOPT_HTTPHEADER => array('Accept: application/json', 'Cache-Control: no-cache')));
    $contenido = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $falloRed = curl_errno($ch);
    curl_close($ch);
    if ($contenido === false || $status !== 200) {
      throw new RuntimeException($endpoint . ': HTTP ' . $status . ', error de transporte ' . $falloRed);
    }
    $respuesta = json_decode($contenido, true);
  }
  if (!is_array($respuesta) || ($respuesta['error'] ?? null) !== false || !is_array($respuesta['depurar'] ?? null)) {
    throw new RuntimeException($endpoint . ': falta respuesta JSON valida de exito');
  }
  return $respuesta['depurar'];
}

try {
  if (isset($opciones['model']) === isset($opciones['base'])) {
    throw new InvalidArgumentException('Elegir exactamente --model o --base=URL_API');
  }
  if (isset($opciones['model'])) {
    $_SERVER['SERVER_NAME'] = 'panel.com.local';
    chdir(__DIR__ . '/../../public');
    require_once '../app/iniciador.php';
    require_once '../app/modelos/EcommerceCatalogoPublico.php';
    $modelo = new EcommerceCatalogoPublico();
    $metodo = new ReflectionMethod('CRUD', 'getConexion');
    $metodo->setAccessible(true);
    $conexion = $metodo->invoke($modelo);
    if (!$conexion) { throw new RuntimeException('Conexion configurada no disponible'); }
    $conexion->exec('START TRANSACTION READ ONLY');
  } else {
    $partes = parse_url($base);
    if (!$partes || !in_array($partes['scheme'] ?? '', array('http', 'https'), true)
      || empty($partes['host']) || isset($partes['user']) || isset($partes['pass'])
      || isset($partes['query']) || isset($partes['fragment'])
      || ($partes['path'] ?? '') !== '/ecommercePublico') {
      throw new InvalidArgumentException('base debe ser URL http/https terminada en /ecommercePublico, sin credenciales ni query');
    }
  }
  $manifest = consultarRelease('catalogo_manifest');
  $contrato = $manifest['presentacion_catalogo'] ?? array();
  verificarRelease(($contrato['version'] ?? '') === 'presentacion_catalogo_v1',
    'API sin presentacion_catalogo_v1. No activar agrupacion en frontend: revisar despliegue/cache.');
  verificarRelease(($contrato['agrupacion_default'] ?? '') === 'sku', 'Default compatible SKU');
  verificarRelease(($contrato['descripcion']['fallback_erp'] ?? null) === false, 'Contrato sin fallback editorial ERP');
  $params = array('q' => 'churro', 'vista' => 'card', 'limite' => 12, 'agrupacion' => 'producto');
  $grupo = consultarRelease('catalogo', $params);
  verificarRelease(($grupo['presentacion_version'] ?? '') === 'presentacion_catalogo_v1', 'Listado con version activa');
  verificarRelease(($grupo['agrupacion'] ?? '') === 'producto' && ($grupo['paginacion']['unidad'] ?? '') === 'grupos', 'Listado realmente agrupado');
  verificarRelease(($grupo['paginacion']['total'] ?? 0) === 2 && ($grupo['paginacion']['total_skus'] ?? 0) === 6,
    'Fixture churro: dos grupos y seis SKUs; si cambio el catalogo, revisar fixture sin omitir el control de agrupacion');
  verificarRelease(count($grupo['items']) === 2, 'Dos tarjetas en respuesta');
  $recorrido = array();
  foreach (array(1, 2) as $pagina) {
    $parte = consultarRelease('catalogo', array_merge($params, array('limite' => 1, 'pagina' => $pagina)));
    $recorrido = array_merge($recorrido, array_column($parte['items'], 'id_publicacion'));
    parse_str(parse_url($parte['paginacion']['primera'], PHP_URL_QUERY) ?? '', $query);
    verificarRelease(($query['agrupacion'] ?? '') === 'producto' && ($query['q'] ?? '') === 'churro', 'Paginacion conserva contexto');
  }
  verificarRelease($recorrido === array_column($grupo['items'], 'id_publicacion'), 'Representantes y orden estables antes de paginar');
  foreach ($grupo['items'] as $item) {
    verificarRelease(!array_key_exists('descripcion_publica', $item), 'Card ligera sin descripcion');
    verificarRelease($item['id_sku'] === $item['grupo_producto']['seleccion_actual']['id_sku'], 'Representante con identidad real');
    $preview = $item['grupo_producto']['variantes_preview'];
    if ($item['grupo_producto']['agrupable']) {
      verificarRelease(count(array_filter($preview, function ($v) use ($item) { return $v['id_sku'] === $item['id_sku']; })) === 1,
        'Preview agrupable incluye seleccion actual una sola vez');
    } else {
      verificarRelease($preview === array(), 'Producto simple usa tarjeta actual sin selector');
    }
    foreach ($preview as $v) {
      verificarRelease(array_key_exists('precio', $v) && array_key_exists('permite_cotizacion', $v)
        && array_key_exists('imagen_fuente', $v) && isset($v['atributos_selector']), 'Variante con precio, permisos, imagen y selector propios');
    }
  }
  $detalle = consultarRelease('producto/alimento-churro-blanco-para-peces-100g');
  verificarRelease(($detalle['descripcion_version'] ?? '') === 'descripcion_editorial_v1', 'Detalle editorial activo');
  verificarRelease(($detalle['item']['descripcion_publica_fuente'] ?? '') === 'publicacion_ecommerce', 'Sin herencia ERP en ficha');
  verificarRelease($detalle['item']['descripcion_publica'] === $detalle['item']['descripcion'], 'Alias respeta campo publico incluso vacio');
  verificarRelease(stripos($detalle['item']['descripcion_publica'], '4 kgrs') === false, 'Ficha 100 g no anuncia empaque de 4 kg');
  verificarRelease($detalle['item']['id_sku'] === 1759 && $detalle['item']['slug'] === 'alimento-churro-blanco-para-peces-100g', 'Identidad y slug conservados');
  $erizo = consultarRelease('busqueda', array('q' => 'Alimento para erizo', 'agrupacion' => 'producto', 'limite' => 12, 'vista' => 'card'));
  verificarRelease(($erizo['motor_version'] ?? '') === 'terminos_and_sql_v2', 'Motor de busqueda actualizado');
  verificarRelease($erizo['total'] === 8 && count(array_unique(array_column($erizo['items'], 'id_producto_erp'))) === 8, 'Ocho alimentos distintos, sin fusionar');
  $sugerencias = consultarRelease('busqueda_sugerencias', array('q' => 'Alimento para erizo', 'agrupacion' => 'producto', 'limite' => 6));
  verificarRelease(($sugerencias['agrupacion'] ?? '') === 'producto' && $sugerencias['total_productos'] === 8, 'Sugerencias con mismo modo y total');
  verificarRelease(array_column($sugerencias['grupos']['productos'], 'valor') === array_slice(array_column($erizo['items'], 'slug'), 0, 6), 'Sugerencias consistentes con resultados');
  $salida = array('ok' => true, 'contrato' => 'presentacion_catalogo_v1', 'churro_grupos' => 2, 'erizo_grupos' => 8);
} catch (Throwable $e) {
  $codigo = 1;
  $salida = array('ok' => false, 'fallo' => $e->getMessage());
} finally {
  if ($conexion && $conexion->inTransaction()) { $conexion->rollBack(); }
}
$salida['modo'] = $modelo ? 'modelo_local_readonly' : 'http_get';
$salida['checks'] = $checks;
$salida['consultas'] = $peticiones;
echo json_encode($salida, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
exit($codigo);
