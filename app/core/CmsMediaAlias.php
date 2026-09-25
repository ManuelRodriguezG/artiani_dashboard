<?php

/**
 * IA: Codex GPT-6 | Fecha: 2026-09-24
 * Proposito: conservar referencias publicas cuando una imagen CMS cambia de nombre o formato.
 * Impacto: rutas anteriores siguen llevando a la imagen actual sin reescribir contenidos guardados.
 * Contrato: solo lectura; metadata_json.rutas_anteriores contiene rutas locales exactas, nunca destinos externos.
 */
class CmsMediaAlias {
  /**
   * IA: Codex GPT-6 | Fecha: 2026-09-24
   * Proposito: reunir la ruta actual y sus nombres historicos para resolver y proteger sus usos.
   * Impacto: comparte criterios entre el acceso publico y el bloqueo de eliminacion.
   * Contrato: retorna rutas locales validas sin duplicados; descarta entradas malformadas sin normalizarlas.
   */
  public static function rutas(array $item): array {
    $candidatas = array($item['url'] ?? $item['ruta_publica'] ?? null);
    foreach (array('urls_anteriores', 'rutas_anteriores') as $campo) {
      if (isset($item[$campo]) && is_array($item[$campo])) {
        $candidatas = array_merge($candidatas, $item[$campo]);
      }
    }
    $metadata = $item['metadata_json'] ?? null;
    if (is_string($metadata)) { $metadata = json_decode($metadata, true); }
    if (is_array($metadata) && isset($metadata['rutas_anteriores']) && is_array($metadata['rutas_anteriores'])) {
      $candidatas = array_merge($candidatas, $metadata['rutas_anteriores']);
    }
    $salida = array();
    foreach ($candidatas as $ruta) {
      if (self::rutaValida($ruta)) { $salida[$ruta] = $ruta; }
    }
    return array_values($salida);
  }

  /**
   * IA: Codex GPT-6 | Fecha: 2026-09-24
   * Proposito: resolver una ruta conocida hacia su URL actual sin exponer metadatos administrativos.
   * Impacto: el controlador puede redirigir una imagen renombrada o convertida a WebP; archivos archivados conservan sus usos.
   * Contrato: retorna solamente {url,mime} o null; error de BD lanza excepcion, alias ambiguos no se resuelven.
   */
  public static function resolver(PDO $db, string $ruta): ?array {
    if (!self::rutaValida($ruta)) { return null; }
    $stmt = $db->prepare('SELECT ruta_publica, mime, metadata_json FROM erp_ecommerce_media_archivos WHERE ruta_publica=:ruta OR metadata_json IS NOT NULL');
    if (!$stmt || !$stmt->execute(array(':ruta' => $ruta))) {
      throw new RuntimeException('No se pudo resolver la ruta publica de Media CMS.');
    }
    $encontrado = null;
    while ($item = $stmt->fetch(PDO::FETCH_ASSOC)) {
      if (!in_array($ruta, self::rutas($item), true)) { continue; }
      $destino = $item['ruta_publica'] ?? null;
      if (!self::rutaValida($destino) || !self::mimeValido($destino, $item['mime'] ?? null)) { continue; }
      $actual = array('url' => $destino, 'mime' => $item['mime']);
      if ($encontrado !== null && $encontrado !== $actual) { return null; }
      $encontrado = $actual;
    }
    $codigo = $stmt->errorCode();
    if ($codigo !== null && $codigo !== '00000' && $codigo !== '02000') {
      throw new RuntimeException('No se pudo completar la resolucion de Media CMS.');
    }
    return $encontrado;
  }

  /**
   * IA: Codex GPT-6 | Fecha: 2026-09-24
   * Proposito: limitar aliases a archivos de imagen directos dentro de la biblioteca CMS.
   * Impacto: impide traversal, URLs externas, escapes, parametros y cabeceras inyectadas al redirigir.
   * Contrato: no decodifica ni corrige rutas; solo acepta el prefijo canonico y nombres ASCII de hasta 255 bytes.
   */
  private static function rutaValida($ruta): bool {
    return is_string($ruta)
      && strlen(basename($ruta)) <= 255
      && strpos($ruta, '..') === false
      && preg_match('~\A/assets/media/cms/ecommerce/[A-Za-z0-9_-][A-Za-z0-9._-]*\.(?:jpg|jpeg|png|webp|gif|avif|ico)\z~D', $ruta) === 1;
  }

  /**
   * IA: Codex GPT-6 | Fecha: 2026-09-24
   * Proposito: verificar el MIME del destino contra su extension antes de entregarlo al controlador publico.
   * Impacto: evita que metadata corrupta produzca cabeceras arbitrarias o formatos incongruentes.
   */
  private static function mimeValido(string $ruta, $mime): bool {
    $mimes = array('jpg' => array('image/jpeg'), 'jpeg' => array('image/jpeg'),
      'png' => array('image/png'), 'webp' => array('image/webp'), 'gif' => array('image/gif'),
      'avif' => array('image/avif'), 'ico' => array('image/x-icon', 'image/vnd.microsoft.icon'));
    return in_array($mime, $mimes[pathinfo($ruta, PATHINFO_EXTENSION)] ?? array(), true);
  }
}
