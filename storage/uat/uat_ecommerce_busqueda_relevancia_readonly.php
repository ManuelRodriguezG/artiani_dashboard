<?php
/**
 * IA: Codex GPT-6 | Fecha: 2026-09-25
 * Proposito: regresion real de relevancia, sugerencias y paginacion antes del lanzamiento.
 * Impacto: Ecommerce publico; fallo devuelve exit 1, nunca registra eventos ni altera productos.
 * Contrato: --base=https://sys.artiani.com.mx (GET) o --model (codigo local, conexion configurada, READ ONLY).
 */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$opciones = getopt('', array('base::', 'model'));
$modelo = null;
$conexion = null;
$base = rtrim(isset($opciones['base']) ? $opciones['base'] : 'http://panel.com.local', '/');
$checks = 0;
$resumen = array();

try {
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
    probarMotorSqlFixtures($modelo, $conexion);
  }

  $q = 'Alimento para erizo';
  $completa = consultarRelevancia('busqueda', array('q' => $q, 'limite' => 24));
  $slugs = slugsRelevancia($completa['items']);
  $esperados = array(
    'alimento-completo-premium-para-erizos-hedgehog-1kg',
    'alimento-para-erizo-1-2kg-gusano-cera', 'alimento-para-erizo-1-2kg-tenebrios',
    'alimento-para-erizo-250g-gusano-cera', 'alimento-para-erizo-250g-tenebrios',
    'alimento-para-erizo-700g-gusano-cera', 'alimento-para-erizo-700g-tenebrios',
    'alimento-para-erizos-select-800g'
  );
  comprobarRelevancia(count(array_diff($esperados, $slugs)) === 0, 'Los ocho alimentos del diagnostico deben ser recuperables');
  comprobarRelevancia($completa['total'] === 8 && count($slugs) === 8, 'Fixture publicado del 2026-09-25: ocho alimentos, sin llavero/shampoo; actual=' . $completa['total'] . ' slugs=' . implode(', ', $slugs));
  comprobarRelevancia($completa['query_usada_catalogo'] !== 'alimento', 'No perder erizo');
  $recorridos = array();
  for ($pagina = 1; $pagina <= 3; $pagina++) {
    $parte = consultarRelevancia('busqueda', array('q' => $q, 'limite' => 3, 'pagina' => $pagina));
    comprobarRelevancia($parte['total'] === $completa['total'], 'Total constante al paginar');
    $recorridos = array_merge($recorridos, slugsRelevancia($parte['items']));
    comprobarRelevancia(strpos($parte['paginacion']['primera'], '/ecommercePublico/busqueda?') === 0, 'Paginacion conserva endpoint');
    parse_str(parse_url($parte['paginacion']['primera'], PHP_URL_QUERY), $query);
    comprobarRelevancia($query['q'] === $q, 'Paginacion conserva frase original');
  }
  comprobarRelevancia($recorridos === $slugs, 'Paginas sin repeticiones, omisiones ni cambios de ranking');
  foreach (array('ALIMENTOS PARA ERIZOS', 'alimento erizo', 'comida para erizos', "Alim\u{00e9}nto para erizo", 'alimento eriz') as $consulta) {
    $variante = consultarRelevancia('busqueda', array('q' => $consulta, 'limite' => 24));
    $actual = slugsRelevancia($variante['items']);
    sort($actual);
    $ordenados = $slugs;
    sort($ordenados);
    comprobarRelevancia($actual === $ordenados, 'Plural, caja, acentos o sinonimo: ' . $consulta);
  }
  foreach (array($q, 'areneros', 'Filtro para pecera de 40 litros') as $consulta) {
    $busqueda = consultarRelevancia('busqueda', array('q' => $consulta, 'limite' => 12));
    $s6 = consultarRelevancia('busqueda_sugerencias', array('q' => $consulta, 'limite' => 6));
    $s12 = consultarRelevancia('busqueda_sugerencias', array('q' => $consulta, 'limite' => 12));
    $a = array_column($s6['grupos']['productos'], 'valor');
    $b = array_column($s12['grupos']['productos'], 'valor');
    comprobarRelevancia($a === array_slice($b, 0, 6), 'Prefijo estable entre limites: ' . $consulta);
    comprobarRelevancia($b === slugsRelevancia($busqueda['items']), 'Sugerencias y resultados comparten ranking: ' . $consulta);
    comprobarRelevancia($s6['total_productos'] === $busqueda['total'], 'Totales de productos coinciden');
    if ($consulta === 'areneros') {
      comprobarRelevancia($busqueda['total'] > 0, 'Plural areneros recupera productos');
    }
    if (strpos($consulta, '40 litros') !== false) {
      comprobarRelevancia($busqueda['interpretacion']['atributos_detectados']['capacidad_litros'] === 40, 'Conserva capacidad solicitada');
      foreach ($busqueda['items'] as $item) {
        comprobarRelevancia(preg_match('/\b40\s*(l|lt|lts|litros?)\b/i', $item['nombre'] . ' ' . $item['presentacion']) === 1, 'No confundir 40 cm o 400 l/h con 40 litros');
      }
    }
    $resumen[$consulta] = array('total' => $busqueda['total'], 'sugerencias' => count($a), 'query' => $busqueda['query_usada_catalogo']);
  }
  $b1 = consultarRelevancia('busqueda', array('q' => 'alimento', 'limite' => 12));
  $b2 = consultarRelevancia('busqueda', array('q' => 'alimento', 'limite' => 12, 'pagina' => 2));
  $b24 = consultarRelevancia('busqueda', array('q' => 'alimento', 'limite' => 24));
  comprobarRelevancia(array_merge(slugsRelevancia($b1['items']), slugsRelevancia($b2['items'])) === slugsRelevancia($b24['items']), 'Busqueda amplia: paginas 1/2 equivalen a primeros 24');
  comprobarRelevancia(count(array_intersect(slugsRelevancia($b1['items']), slugsRelevancia($b2['items']))) === 0, 'Busqueda amplia sin duplicados');
  foreach (array('nombre', 'precio_asc', 'precio_desc', 'recientes') as $orden) {
    $primera = consultarRelevancia('busqueda', array('q' => $q, 'limite' => 4, 'orden' => $orden));
    $segunda = consultarRelevancia('busqueda', array('q' => $q, 'limite' => 4, 'pagina' => 2, 'orden' => $orden));
    $ocho = consultarRelevancia('busqueda', array('q' => $q, 'limite' => 8, 'orden' => $orden));
    comprobarRelevancia(array_merge(slugsRelevancia($primera['items']), slugsRelevancia($segunda['items'])) === slugsRelevancia($ocho['items']), 'Orden global estable: ' . $orden);
  }
  foreach (array('alimento erizo qzxsincoincidencias', 'para de con', '!!!', '') as $consulta) {
    $vacio = consultarRelevancia('busqueda', array('q' => $consulta, 'limite' => 12));
    comprobarRelevancia($vacio['total'] === 0 && $vacio['items'] === array(), 'Sin coincidencias no ampliar a alimento ni a todo el catalogo');
  }
  $filtrado = consultarRelevancia('busqueda', array('q' => $q, 'orden' => 'nombre', 'categoria_slug' => 'no-existe-qatest', 'incluir_hijos' => 1, 'limite' => 3));
  comprobarRelevancia($filtrado['total'] === 0, 'Respetar categoria inexistente');
  parse_str(parse_url($filtrado['paginacion']['primera'], PHP_URL_QUERY), $query);
  comprobarRelevancia($query['categoria_slug'] === 'no-existe-qatest' && $query['orden'] === 'nombre' && $query['incluir_hijos'] === '1', 'Conservar filtros y orden en enlaces');
  $marcaInvalida = consultarRelevancia('busqueda', array('q' => $q, 'marca_id' => 2147483647));
  parse_str(parse_url($marcaInvalida['paginacion']['primera'], PHP_URL_QUERY), $query);
  comprobarRelevancia($marcaInvalida['total'] === 0 && $query['marca'] === '2147483647', 'No perder una marca inexistente al paginar');
  echo json_encode(array('ok' => true, 'checks' => $checks, 'modo' => $modelo ? 'modelo_local_readonly' : 'http_get', 'resultados' => $resumen), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
} catch (Throwable $e) {
  echo json_encode(array('ok' => false, 'checks' => $checks, 'fallo' => $e->getMessage()), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
  exit(1);
} finally {
  if ($conexion && $conexion->inTransaction()) { $conexion->rollBack(); }
}

/** IA: Codex GPT-6 | 2026-09-25. GET o modelo local read-only; aborta ante error para evitar falsos ceros. */
function consultarRelevancia($endpoint, $params) {
  global $modelo, $base;
  if ($modelo) {
    $metodo = $endpoint === 'busqueda' ? 'busquedaInteligentePublica' : 'busquedaSugerenciasPublicas';
    $respuesta = $modelo->$metodo($params);
  } else {
    $ctx = stream_context_create(array('http' => array('method' => 'GET', 'timeout' => 45, 'header' => "Accept: application/json\r\n")));
    $raw = file_get_contents($base . '/ecommercePublico/' . $endpoint . '?' . http_build_query($params), false, $ctx);
    $respuesta = json_decode((string) $raw, true);
  }
  if (!is_array($respuesta) || !empty($respuesta['error']) || !isset($respuesta['depurar']['items']) && $endpoint === 'busqueda') {
    throw new RuntimeException('Fallo de consulta ' . $endpoint . ': ' . (isset($respuesta['mensaje']) ? $respuesta['mensaje'] : 'Sin JSON valido'));
  }
  return $respuesta['depurar'];
}

/** IA: Codex GPT-6 | 2026-09-25. Compara identidades publicas sin modificar productos. */
function slugsRelevancia($items) { return array_column($items, 'slug'); }

/** IA: Codex GPT-6 | 2026-09-25. Asercion fail-fast de contrato y resultados; exit no cero al fallar. */
function comprobarRelevancia($ok, $mensaje) {
  global $checks;
  $checks++;
  if (!$ok) { throw new RuntimeException($mensaje); }
}

/**
 * IA: Codex GPT-6 | 2026-09-25. Fixtures como SELECT de constantes, sin tablas temporales ni escrituras.
 * Verifica positivos/negativos de capacidad y acentos aunque el catalogo real no tenga un filtro de 40 l.
 */
function probarMotorSqlFixtures($modelo, $db) {
  $interpretar = new ReflectionMethod('EcommerceCatalogoPublico', 'interpretarBusquedaPublica');
  $interpretar->setAccessible(true);
  $criterio = new ReflectionMethod('EcommerceCatalogoPublico', 'criterioBusquedaPublicaSql');
  $criterio->setAccessible(true);
  $nombres = array(
    1 => 'Filtro para pecera 40 litros',
    2 => 'Filtro acuario 40 l',
    3 => 'Filtro placa 40cm',
    4 => 'Filtro pecera 400 litros por hora',
    5 => 'Alimento erizo 40 litros',
    6 => 'Filtro pecera 25 litros',
    7 => 'Filtro para pecera sin capacidad',
    8 => "Alimento completo premium para ERIZOS",
    9 => 'Alimento perro',
    10 => 'Shampoo erizo',
    11 => 'Arenero cubierto',
    12 => 'Areneros grandes',
    13 => "Juguete p\u{00e1}jaro"
  );
  $selects = array();
  foreach ($nombres as $id => $nombre) {
    $selects[] = 'SELECT ' . $id . ' id_publicacion, ' . $db->quote($nombre) . ' titulo_publico, NULL presentacion_publica, 0 mostrar_precio';
  }
  $from = ' FROM (' . implode(' UNION ALL ', $selects) . ') pub
    CROSS JOIN (SELECT NULL nombre, NULL sku, -1 id_sku) s
    CROSS JOIN (SELECT NULL nombre) p CROSS JOIN (SELECT NULL nombre) m
    CROSS JOIN (SELECT NULL nombre, NULL ruta) c
    CROSS JOIN (SELECT NULL url_imagen) img_sku CROSS JOIN (SELECT NULL url_imagen) img_prod
    CROSS JOIN (SELECT 0 precio) pr';
  foreach (array(
    'Filtro para pecera de 40 litros' => array(1, 2),
    'Alimento para erizo' => array(5, 8),
    'areneros' => array(11, 12),
    'aren' => array(11, 12),
    'juguete pajaro' => array(13),
    'alimento qzxinexistente' => array()
  ) as $q => $esperado) {
    $sql = $criterio->invoke($modelo, $interpretar->invoke($modelo, $q));
    $consulta = 'SELECT pub.id_publicacion, ' . $sql['score'] . ' score' . $from . ' WHERE ' . implode(' AND ', $sql['where']) . ' ORDER BY score DESC, pub.id_publicacion';
    $stmt = $db->prepare($consulta);
    $stmt->execute(array_merge($sql['params'], $sql['params_score']));
    $ids = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    comprobarRelevancia($ids === $esperado, 'Fixture SQL: ' . $q);
  }
}
