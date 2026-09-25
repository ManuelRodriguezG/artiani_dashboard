<?php

/**
 * IA: Codex GPT-6 | Fecha: 2026-09-24
 * Proposito: validar originales Media sin conversiones implicitas.
 * Impacto: altas y reemplazos CMS; no depende de GD ni modifica archivos.
 */
class CmsMediaArchivo {
  const MAX_BYTES = 2097152;

  /** IA: Codex GPT-6 | Fecha: 2026-09-25
   * Proposito: evitar publicar imagenes con permisos privados heredados de temporales Linux.
   * Impacto: altas, reemplazos y recuperaciones Media; solo el propietario puede escribir.
   * Contrato: ruta local ya validada por Media, archivo regular sin enlaces; false impide confirmar.
   * Los temporales preparados siguen privados por el directorio de resguardo 0700.
   */
  public static function asegurarLecturaPublica($ruta) {
    if (!is_file($ruta) || is_link($ruta) || !@chmod($ruta, 0644)) return false;
    clearstatcache(true, $ruta);
    if (!is_readable($ruta)) return false;
    // Windows solo implementa el atributo de escritura; la mascara POSIX se verifica en Linux.
    if (PHP_OS_FAMILY !== 'Windows') {
      $permisos = @fileperms($ruta);
      if ($permisos === false || ($permisos & 0777) !== 0644) return false;
    }
    return true;
  }


  /** IA: Codex GPT-6 | Fecha: 2026-09-25
   * Proposito: comprobar que el archivo guardado existe, es legible y conserva los bytes recibidos.
   * Impacto: alta/reemplazo Media; separa verificacion fisica de la posterior prueba HTTP del navegador.
   * Contrato: ruta local ya autorizada; solo lectura, sin rutas internas ni excepciones en el informe.
   */
  public static function verificarGuardado($ruta, $bytesEsperados, $hashEsperado) {
    $informe = array('ok' => false, 'estado' => 'no_verificable', 'existe' => false, 'legible' => false,
      'permisos_publicos' => null, 'permisos' => null, 'bytes_coinciden' => null, 'hash_coincide' => null,
      'mensaje' => 'No se pudo comprobar el archivo en el servidor. Revisa su ficha antes de volver a subirlo.');
    try {
      clearstatcache(true, $ruta);
      if (is_link($ruta)) return $informe;
      $informe['existe'] = is_file($ruta);
      if (!$informe['existe']) {
        $informe['estado'] = 'ausente';
        $informe['mensaje'] = 'El archivo no existe en la carpeta de Media de este servidor. Revisa el almacenamiento o reemplazalo desde su ficha.';
        return $informe;
      }
      $informe['legible'] = is_readable($ruta);
      $modo = @fileperms($ruta);
      if (PHP_OS_FAMILY !== 'Windows' && $modo !== false) {
        $informe['permisos'] = sprintf('%04o', $modo & 0777);
        $informe['permisos_publicos'] = ($modo & 0044) === 0044;
      }
      if (!$informe['legible']) {
        $informe['estado'] = 'no_legible';
        $informe['mensaje'] = 'El archivo existe, pero PHP no puede leerlo. Revisa sus permisos y propietario en el servidor.';
        return $informe;
      }
      if ($informe['permisos_publicos'] === false) {
        $informe['estado'] = 'permisos_restringidos';
        $informe['mensaje'] = 'El archivo existe, pero tiene permisos restringidos (' . $informe['permisos'] . '). Revisa su lectura publica en el servidor.';
        return $informe;
      }
      if ((PHP_OS_FAMILY !== 'Windows' && $modo === false) || (int) $bytesEsperados < 1 || !preg_match('/\A[a-f0-9]{64}\z/D', (string) $hashEsperado)) return $informe;
      $bytes = @filesize($ruta);
      $hash = @hash_file('sha256', $ruta);
      if ($bytes === false || $hash === false) return $informe;
      $informe['bytes_coinciden'] = $bytes === (int) $bytesEsperados;
      $informe['hash_coincide'] = hash_equals((string) $hashEsperado, $hash);
      if (!$informe['bytes_coinciden'] || !$informe['hash_coincide']) {
        $informe['estado'] = 'contenido_distinto';
        $informe['mensaje'] = 'El archivo existe, pero su contenido no coincide con el registrado. Revisa la version del archivo en el servidor.';
        return $informe;
      }
      $informe['ok'] = true;
      $informe['estado'] = 'verificado';
      $informe['mensaje'] = 'Archivo comprobado en el servidor: existe, es legible y su contenido coincide. Falta comprobar su acceso publico.';
    } catch (Throwable $error) { /* No filtrar rutas fisicas ni errores internos en la respuesta administrativa. */ }
    return $informe;
  }

  /** IA: Codex GPT-6 | Fecha: 2026-09-24
   * Proposito: comprobar formato real, extension, dimensiones y peso.
   * Contrato: recibe ruta local ya autorizada; retorna metadata o lanza excepcion.
   */
  public static function inspeccionar($ruta, $nombre) {
    $extension = strtolower(pathinfo($nombre, PATHINFO_EXTENSION));
    $mimes = array('jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
      'webp' => 'image/webp', 'gif' => 'image/gif', 'avif' => 'image/avif', 'ico' => 'image/x-icon');
    if (!isset($mimes[$extension])) throw new Exception('Usa JPG, JPEG, PNG, WebP, GIF, AVIF o ICO.');
    $bytes = filesize($ruta);
    if (!$bytes || $bytes > self::MAX_BYTES) throw new Exception('La imagen final debe pesar como maximo 2 MB. Puedes optimizarla antes de subir.');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($ruta);
    $dim = @getimagesize($ruta);
    if ($extension === 'ico') {
      $contenido = file_get_contents($ruta);
      $dim = self::dimensionesIco($contenido);
      if (!in_array($mime, array('image/x-icon', 'image/vnd.microsoft.icon', 'application/octet-stream'), true)) throw new Exception('El contenido no corresponde a un archivo ICO.');
    } else {
      if ($mime !== $mimes[$extension]) throw new Exception('La extension no coincide con el contenido. Usa el archivo original con su extension correcta.');
      // PHP 8.0 no reconoce dimensiones AVIF: leer ispe de su contenedor ISO BMFF.
      if ($extension === 'avif' && !$dim) {
        $contenido = file_get_contents($ruta);
        $dim = self::dimensionesAvif($contenido);
      }
    }
    if (!$dim || empty($dim[0]) || empty($dim[1])) throw new Exception('No se pudo validar la imagen y sus dimensiones.');
    return array('mime' => $mimes[$extension], 'extension' => $extension, 'bytes' => $bytes,
      'ancho' => (int) $dim[0], 'alto' => (int) $dim[1], 'hash' => hash_file('sha256', $ruta));
  }

  /** IA: Codex GPT-6 | Fecha: 2026-09-24
   * Proposito: impedir que cualquier archivo renombrado .ico pase como favicon.
   * Contrato: valida todas las entradas PNG/DIB y sus limites; conserva bytes y resoluciones.
   */
  public static function dimensionesIco($contenido) {
    $largo = strlen($contenido);
    if ($largo < 22 || substr($contenido, 0, 4) !== "\x00\x00\x01\x00") throw new Exception('El archivo ICO no tiene una cabecera valida.');
    $header = unpack('vtotal', substr($contenido, 4, 2));
    $total = $header['total'];
    if (!$total || $total > 256 || 6 + $total * 16 > $largo) throw new Exception('El directorio ICO no es valido.');
    $mayor = array(0, 0);
    for ($i = 0; $i < $total; $i++) {
      $entry = unpack('Cancho/Calto/Ccolores/Creservado/vplanos/vbits/Vbytes/Voffset', substr($contenido, 6 + $i * 16, 16));
      if ($entry['reservado'] !== 0 || $entry['offset'] < 6 + $total * 16 || $entry['bytes'] < 8 || $entry['offset'] + $entry['bytes'] > $largo) throw new Exception('El ICO contiene una imagen incompleta.');
      $imagen = substr($contenido, $entry['offset'], $entry['bytes']);
      $ancho = $entry['ancho'] ?: 256;
      $alto = $entry['alto'] ?: 256;
      if (substr($imagen, 0, 8) === "\x89PNG\r\n\x1a\n") {
        $dim = @getimagesizefromstring($imagen);
        if (!$dim || $dim[0] !== $ancho || $dim[1] !== $alto) throw new Exception('Las dimensiones PNG del ICO no coinciden.');
      } else {
        if (strlen($imagen) < 40) throw new Exception('La imagen interna del ICO no es valida.');
        $dib = unpack('Vcabecera/Vancho/Valto/vplanos/vbits/Vcompresion/Vtamano/Vxppm/Vyppm/Vcolores/Vimportantes', substr($imagen, 0, 40));
        if (!in_array($dib['cabecera'], array(40, 52, 56, 108, 124), true) || $dib['cabecera'] > strlen($imagen) || $dib['ancho'] !== $ancho || $dib['alto'] !== $alto * 2) throw new Exception('La imagen BMP del ICO no es valida.');
        if ($dib['planos'] !== 1 || !in_array($dib['bits'], array(1, 4, 8, 16, 24, 32), true) || !in_array($dib['compresion'], array(0, 3, 6), true)) throw new Exception('El formato BMP del ICO no es compatible.');
        if (($entry['planos'] && $entry['planos'] !== $dib['planos']) || ($entry['bits'] && $entry['bits'] !== $dib['bits']) || ($dib['compresion'] !== 0 && !in_array($dib['bits'], array(16, 32), true))) throw new Exception('Los datos BMP del ICO no coinciden con su directorio.');
        $paleta = $dib['colores'] ?: ($dib['bits'] <= 8 ? 1 << $dib['bits'] : 0);
        if ($dib['bits'] <= 8 && $paleta > (1 << $dib['bits'])) throw new Exception('La paleta del ICO no es valida.');
        $mascarasExtra = $dib['cabecera'] === 40 && $dib['compresion'] !== 0 ? ($dib['compresion'] === 6 ? 16 : 12) : 0;
        $inicioPixeles = $dib['cabecera'] + $mascarasExtra + $paleta * 4;
        $bytesPixeles = intdiv($ancho * $dib['bits'] + 31, 32) * 4 * $alto;
        $bytesMascara = intdiv($ancho + 31, 32) * 4 * $alto;
        $disponible = strlen($imagen) - $inicioPixeles;
        if ($disponible < $bytesPixeles || ($dib['bits'] !== 32 && $disponible < $bytesPixeles + $bytesMascara) || ($disponible > $bytesPixeles && $disponible < $bytesPixeles + $bytesMascara) || $dib['tamano'] > $disponible) throw new Exception('El ICO contiene pixeles o mascara incompletos.');
      }
      if ($ancho * $alto > $mayor[0] * $mayor[1]) $mayor = array($ancho, $alto);
    }
    return $mayor;
  }

  /** IA: Codex GPT-6 | Fecha: 2026-09-24
   * Proposito: validar dimensiones AVIF en versiones PHP sin lector nativo del formato.
   * Impacto: evita aceptar la cadena ispe encontrada dentro de datos ajenos a sus propiedades.
   * Contrato: exige marca AVIF, contenedor meta/iprp/ipco y datos de imagen; no decodifica ni convierte.
   */
  public static function dimensionesAvif($contenido) {
    $dimensiones = array(); $marca = false; $datosImagen = false;
    foreach (self::cajasAvif($contenido, 0, strlen($contenido)) as $caja) {
      if ($caja['tipo'] === 'ftyp') {
        $brands = substr($contenido, $caja['inicio'], 4) . substr($contenido, $caja['inicio'] + 8, max(0, $caja['fin'] - $caja['inicio'] - 8));
        foreach (str_split($brands, 4) as $brand) { if ($brand === 'avif' || $brand === 'avis') $marca = true; }
      }
      if ($caja['tipo'] === 'mdat' && $caja['fin'] > $caja['inicio']) $datosImagen = true;
      if ($caja['tipo'] !== 'meta') continue;
      if ($caja['fin'] - $caja['inicio'] < 4) throw new Exception('El contenedor AVIF esta incompleto.');
      foreach (self::cajasAvif($contenido, $caja['inicio'] + 4, $caja['fin']) as $meta) {
        if ($meta['tipo'] === 'idat' && $meta['fin'] > $meta['inicio']) $datosImagen = true;
        if ($meta['tipo'] !== 'iprp') continue;
        foreach (self::cajasAvif($contenido, $meta['inicio'], $meta['fin']) as $propiedad) {
          if ($propiedad['tipo'] !== 'ipco') continue;
          foreach (self::cajasAvif($contenido, $propiedad['inicio'], $propiedad['fin']) as $info) {
            if ($info['tipo'] !== 'ispe') continue;
            if ($info['fin'] - $info['inicio'] !== 12) throw new Exception('Las dimensiones AVIF estan incompletas.');
            $valor = unpack('Nancho/Nalto', substr($contenido, $info['inicio'] + 4, 8));
            if (!$valor['ancho'] || !$valor['alto']) throw new Exception('Las dimensiones AVIF no son validas.');
            $dimensiones[] = array($valor['ancho'], $valor['alto']);
          }
        }
      }
    }
    if (!$marca || !$datosImagen || !$dimensiones) throw new Exception('No se pudo validar la estructura y las dimensiones AVIF.');
    return $dimensiones[0];
  }

  /** IA: Codex GPT-6 | Fecha: 2026-09-24
   * Proposito: recorrer cajas ISO BMFF comprobando sus limites antes de inspeccionar propiedades.
   * Impacto: validacion AVIF; rechaza contenedores truncados y tamanos que excedan el archivo.
   */
  private static function cajasAvif($contenido, $inicio, $fin) {
    $cajas = array();
    while ($inicio < $fin) {
      if ($fin - $inicio < 8) throw new Exception('El contenedor AVIF esta truncado.');
      $header = unpack('Ntamano/a4tipo', substr($contenido, $inicio, 8));
      $largoHeader = 8; $tamano = $header['tamano'];
      if ($tamano === 1) {
        if ($fin - $inicio < 16) throw new Exception('El contenedor AVIF esta truncado.');
        $extendido = unpack('Nalto/Nbajo', substr($contenido, $inicio + 8, 8));
        if ($extendido['alto'] !== 0) throw new Exception('El contenedor AVIF excede el limite permitido.');
        $tamano = $extendido['bajo']; $largoHeader = 16;
      } elseif ($tamano === 0) { $tamano = $fin - $inicio; }
      if ($tamano < $largoHeader || $tamano > $fin - $inicio) throw new Exception('El contenedor AVIF tiene un tamano invalido.');
      $cajas[] = array('tipo' => $header['tipo'], 'inicio' => $inicio + $largoHeader, 'fin' => $inicio + $tamano);
      $inicio += $tamano;
    }
    return $cajas;
  }

  /** IA: Codex GPT-6 | Fecha: 2026-09-24
   * Proposito: confinar operaciones a archivos simples de la carpeta publica CMS.
   * Contrato: nunca acepta rutas relativas, enlaces simbolicos ni nombres ejecutables.
   */
  public static function ruta($url) {
    if (!preg_match('~^/assets/media/cms/ecommerce/([a-zA-Z0-9_.-]+\.(?:jpg|jpeg|png|webp|gif|avif|ico))$~D', $url, $match)) throw new Exception('La ruta no pertenece a Media CMS.');
    $directorio = realpath(dirname(__DIR__, 2) . '/public/assets/media/cms/ecommerce');
    if (!$directorio) throw new Exception('La carpeta Media CMS no esta disponible.');
    $ruta = $directorio . DIRECTORY_SEPARATOR . $match[1];
    if (is_link($ruta) || (file_exists($ruta) && realpath(dirname(realpath($ruta))) !== $directorio)) throw new Exception('La ruta fisica de la imagen no es valida.');
    return $ruta;
  }
}
