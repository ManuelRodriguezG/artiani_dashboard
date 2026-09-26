<?php

/**
 * IA: Codex GPT-6 | Fecha: 2026-09-25
 * Proposito: presentar catalogo agrupado y contenido editorial publico.
 * Impacto: EcommerceCatalogoPublico; no cambia identidades, precios ni URLs.
 */
trait EcommerceCatalogoPresentacion {
  private $cacheAtributosSelectorPublicos = array();
  private $tablasAtributosSelectorDisponibles = null;

  /** IA: Codex GPT-6 | 2026-09-25. Modo explicito y compatible; rechaza errores de integracion. */
  private function normalizarAgrupacionCatalogoPublico($valor) {
    $valor = trim((string) $valor);
    if ($valor === '') { return 'sku'; }
    if (!in_array($valor, array('sku', 'producto'), true)) {
      throw new InvalidArgumentException('agrupacion debe ser sku o producto');
    }
    return $valor;
  }

  /**
   * IA: Codex GPT-6 | 2026-09-25. Ranking SQL sobre candidatos ya filtrados, antes de LIMIT.
   * Contrato: requiere funciones ventana; cada representante cumple filtros. No modifica datos.
   */
  private function sqlCatalogoAgrupadoPublico($sqlCandidatos, $orden, $conRelevancia) {
    $orden = $this->ordenCatalogoPublicoNormalizado($orden);
    $ordenSql = 'destacado DESC, orden_publicacion ASC, titulo_publico ASC';
    if ($orden === 'nombre') { $ordenSql = 'titulo_publico ASC, orden_publicacion ASC'; }
    if ($orden === 'recientes') { $ordenSql = 'fecha_orden_publicacion DESC, titulo_publico ASC'; }
    if ($orden === 'precio_asc' || $orden === 'precio_desc') {
      $ordenSql = 'CASE WHEN mostrar_precio=1 AND precio IS NOT NULL THEN 0 ELSE 1 END ASC, '
        . 'CASE WHEN mostrar_precio=1 THEN precio ELSE NULL END ' . ($orden === 'precio_asc' ? 'ASC' : 'DESC') . ', titulo_publico ASC';
    }
    if ($conRelevancia && $orden === 'relevancia') { $ordenSql = 'relevancia_busqueda DESC, ' . $ordenSql; }
    $ordenSql .= ', id_publicacion ASC';
    return 'SELECT * FROM (SELECT candidatos.*, ROW_NUMBER() OVER (
      PARTITION BY id_producto_erp ORDER BY ' . $ordenSql . ') posicion_grupo
      FROM (' . $sqlCandidatos . ') candidatos) grupos
      WHERE posicion_grupo=1 ORDER BY ' . $ordenSql;
  }

  /** IA: Codex GPT-6 | 2026-09-25. Contrato compartido consultable por API, sin archivos internos. */
  private function contratoPresentacionCatalogoPublico() {
    return array(
      'version' => 'presentacion_catalogo_v1',
      'agrupacion_default' => 'sku',
      'agrupacion_producto' => array(
        'parametro' => 'agrupacion=producto',
        'endpoints' => array('/ecommercePublico/catalogo', '/ecommercePublico/busqueda', '/ecommercePublico/busqueda_sugerencias'),
        'identidad_grupo' => 'id_producto_erp',
        'regla' => 'Mismo producto ERP con varias publicaciones visibles; productos simples conservan tarjeta individual.',
        'representante' => 'Primera publicacion que cumple filtros segun orden solicitado y desempate id_publicacion.',
        'total' => 'Tarjetas resultantes, no SKUs; paginacion.total_skus es un conteo separado.',
        'precio' => 'Precio del SKU representativo, no rango ni precio desde.',
        'variantes_preview' => 'Hasta 6 publicadas del producto, incluye seleccion actual; no restringidas por la busqueda de la tarjeta.',
        'variantes_completas' => 'Seguir grupo_producto.variantes_url, paginado en modo sku.',
        'no_deduplicar_en_frontend' => true,
        'urls_sin_cambios' => true
      ),
      'descripcion' => array(
        'version' => 'descripcion_editorial_v1',
        'endpoint' => '/ecommercePublico/producto/{slug}',
        'campo' => 'depurar.item.descripcion_publica',
        'vacio_intencional' => true,
        'fallback_erp' => false,
        'formato' => 'depurar.item.descripcion_publica_formato: texto o html',
        'tags' => array('p', 'br', 'ul', 'ol', 'li', 'strong', 'b', 'em', 'i', 'h2', 'h3', 'h4'),
        'atributos_html_permitidos' => array(),
        'seo' => 'Texto plano corto, sin notas internas ni HTML.'
      ),
      'bloques' => array('secciones' => 'sku', 'producto.relacionados' => 'sku', 'producto.variantes' => 'sku'),
      'facetas_y_taxonomia' => 'Los conteos de categorias, marcas y facetas siguen contando SKUs; para paginas usar paginacion.total.',
      'carrito' => 'Usar id_publicacion/id_sku de la variante seleccionada y sus permisos; validar mediante preflight/dryrun.'
    );
  }

  /**
   * IA: Codex GPT-6 | 2026-09-25. Sanea al guardar y leer sin importar texto operativo.
   * Contrato: conserva texto plano/saltos o HTML editorial sin atributos; elimina subarboles activos.
   */
  private function sanearDescripcionPublica($contenido) {
    $contenido = trim((string) $contenido);
    if ($contenido === '' || strpos($contenido, '<') === false) { return $contenido; }
    if (!class_exists('DOMDocument')) { return ''; }
    $documento = new DOMDocument('1.0', 'UTF-8');
    $previo = libxml_use_internal_errors(true);
    try {
      if (!$documento->loadHTML('<?xml encoding="UTF-8"><html><body>' . $contenido . '</body></html>', LIBXML_NONET)) { return ''; }
      $body = $documento->getElementsByTagName('body')->item(0);
      return $body ? trim($this->serializarDescripcionPublica($body)) : '';
    } finally {
      libxml_clear_errors();
      libxml_use_internal_errors($previo);
    }
  }

  /** IA: Codex GPT-6 | 2026-09-25. Reconstruye nodos permitidos; nunca copia atributos, URLs ni eventos HTML. */
  private function serializarDescripcionPublica($nodo) {
    $permitidos = array('p', 'br', 'ul', 'ol', 'li', 'strong', 'b', 'em', 'i', 'h2', 'h3', 'h4');
    $descartar = array('script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button', 'select', 'textarea', 'svg', 'math', 'template', 'noscript', 'head');
    $salida = '';
    foreach ($nodo->childNodes as $hijo) {
      if ($hijo->nodeType === XML_TEXT_NODE || $hijo->nodeType === XML_CDATA_SECTION_NODE) {
        $salida .= htmlspecialchars($hijo->nodeValue, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
      } elseif ($hijo->nodeType === XML_ELEMENT_NODE) {
        $tag = strtolower($hijo->nodeName);
        if (in_array($tag, $descartar, true)) { continue; }
        $interior = $this->serializarDescripcionPublica($hijo);
        $salida .= in_array($tag, $permitidos, true) ? ($tag === 'br' ? '<br>' : '<' . $tag . '>' . $interior . '</' . $tag . '>') : $interior;
      }
    }
    return $salida;
  }

  /** IA: Codex GPT-6 | 2026-09-25. Solo atributos activos marcados es_variante; cache por solicitud, sin atributos administrativos. */
  private function atributosSelectorSkuPublicos($idSku) {
    $idSku = intval($idSku);
    if (isset($this->cacheAtributosSelectorPublicos[$idSku])) { return $this->cacheAtributosSelectorPublicos[$idSku]; }
    $db = $this->getConexion();
    if (!$db || $idSku <= 0) { return array(); }
    if (!$this->atributosSelectorDisponibles()) { return array(); }
    $stmt = $db->prepare("SELECT a.codigo, a.nombre, sa.valor, a.unidad
      FROM erp_catalogo_sku_atributos sa INNER JOIN erp_catalogo_atributos a ON a.id_atributo_erp=sa.id_atributo_erp
      WHERE sa.id_sku=:sku AND a.es_variante=1 AND a.estatus='activo' ORDER BY a.codigo, a.id_atributo_erp");
    $stmt->execute(array(':sku' => $idSku));
    return $this->cacheAtributosSelectorPublicos[$idSku] = $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  /** IA: Codex GPT-6 | 2026-09-25. Descubrimiento por solicitud; instalaciones sin atributos siguen operando. */
  private function atributosSelectorDisponibles() {
    if ($this->tablasAtributosSelectorDisponibles === null) {
      $db = $this->getConexion();
      $this->tablasAtributosSelectorDisponibles = $db && $this->tablaExiste($db, 'erp_catalogo_sku_atributos') && $this->tablaExiste($db, 'erp_catalogo_atributos');
    }
    return $this->tablasAtributosSelectorDisponibles;
  }

  /** IA: Codex GPT-6 | 2026-09-25. Color/medida filtran el SKU antes de agrupar; no busca atributos administrativos. */
  private function sqlCoincidenciaAtributoSelector($parametro, $regex = true) {
    $campo = $regex ? $this->textoBusquedaPublicaSql(array('av.valor', 'atv.unidad')) : "CONCAT_WS(' ', av.valor, atv.unidad)";
    return "EXISTS (SELECT 1 FROM erp_catalogo_sku_atributos av
      INNER JOIN erp_catalogo_atributos atv ON atv.id_atributo_erp=av.id_atributo_erp
      WHERE av.id_sku=s.id_sku AND atv.es_variante=1 AND atv.estatus='activo' AND " . $campo . ($regex ? ' REGEXP ' : ' LIKE ') . $parametro . ')';
  }
}
