<?php

/** IA: Codex GPT-6 | Fecha: 2026-09-24
 * Proposito: gestionar reemplazos y bajas conservando referencias de Media CMS.
 * Impacto: extension del modelo EcommerceCatalogoPublico; no aplica DDL.
 */
trait EcommerceMediaGestion {
  /** IA: Codex GPT-6 | Fecha: 2026-09-25
   * Proposito: adjuntar comprobacion fisica al resultado de una carga, incluso si ya estaba registrada.
   * Impacto: mensajes Media distinguen almacenamiento de acceso HTTP sin exponer rutas privadas.
   * Contrato: no escribe ni repara; nunca convierte un guardado confirmado en un error de subida.
   */
  private function mediaAdjuntarValidacionArchivo($item) {
    $item['validacion_archivo'] = array('ok' => false, 'estado' => 'no_verificable', 'existe' => false,
      'legible' => false, 'permisos_publicos' => null, 'permisos' => null,
      'bytes_coinciden' => null, 'hash_coincide' => null,
      'mensaje' => 'No se pudo comprobar el archivo en el servidor. Revisa su ficha antes de volver a subirlo.');
    try {
      $item['validacion_archivo'] = CmsMediaArchivo::verificarGuardado(
        CmsMediaArchivo::ruta($item['url']), $item['bytes'], $item['hash_sha256'] ?? '');
    } catch (Throwable $error) { /* Carpeta ausente o ruta invalida: no exponer detalles del filesystem. */ }
    return $item;
  }

  /** IA: Codex GPT-6 | Fecha: 2026-09-24
   * Proposito: mostrar dependencias persistidas antes de borrar.
   * Contrato: GET solo lectura; errores nunca se interpretan como cero usos.
   */
  public function mediaAdminUsosInterno($datos = array()) {
    try {
      $db = $this->getConexion();
      $item = $this->mediaBuscarPorId($db, intval($this->valor($datos, 'id_media_archivo', 0)));
      if (!$item) throw new Exception('La imagen de Media CMS no existe.');
      $usos = CmsMediaReferencias::usos($db, $item);
      return $this->respuesta(false, 'success', 'Usos guardados de la imagen consultados.', array(
        'id_media_archivo' => $item['id_media_archivo'], 'usos' => $usos, 'total' => count($usos), 'puede_eliminar' => count($usos) === 0,
        'alcance' => 'CMS y Blog guardados en este servidor. Los borradores locales y sitios externos no se pueden detectar.'));
    } catch (Throwable $e) {
      return $this->respuesta(true, 'danger', 'No se pudieron verificar los usos de esta imagen.', array('puede_eliminar' => false));
    }
  }

  /** IA: Codex GPT-6 | Fecha: 2026-09-24
   * Proposito: cambiar archivo/formato/nombre manteniendo ID, codigo y aliases publicos.
   * Contrato: nombre_seo y alt opcionales; archivo opcional para renombrar, resguardo y rollback.
   */
  public function mediaAdminReemplazarInterno($archivo, $datos = array(), $idUsuario = 0) {
    $db = $this->getConexion();
    $temporal = ''; $respaldo = ''; $destino = ''; $rutaAnterior = '';
    $movido = false; $retirado = false; $confirmado = false; $conservarRespaldo = false; $existiaAnterior = false;
    try {
      $hayArchivo = is_array($archivo) && $archivo && intval($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
      if ($hayArchivo) {
        $this->mediaValidarArchivoUpload($archivo);
        $nuevo = CmsMediaArchivo::inspeccionar($archivo['tmp_name'], $archivo['name']);
      }
      if (!$db || !$db->beginTransaction()) throw new Exception('No fue posible iniciar el cambio de Media CMS.');
      $id = intval($this->valor($datos, 'id_media_archivo', 0));
      $lock = $db->prepare('SELECT metadata_json FROM erp_ecommerce_media_archivos WHERE id_media_archivo=:id FOR UPDATE');
      if (!$lock || !$lock->execute(array(':id' => $id))) throw new Exception('No fue posible bloquear la imagen para actualizarla.');
      $metadataTexto = $lock->fetchColumn();
      $item = $this->mediaBuscarPorId($db, $id);
      if (!$item) throw new Exception('La imagen de Media CMS no existe.');
      $metadata = $metadataTexto ? json_decode($metadataTexto, true) : array();
      if (!is_array($metadata)) throw new Exception('Los metadatos existentes de la imagen requieren revision antes de cambiarla.');
      $rutaAnterior = CmsMediaArchivo::ruta($item['url']);
      $existiaAnterior = is_file($rutaAnterior);
      $nombrePrevio = (string) ($metadata['nombre_seo'] ?? '');
      $nombre = array_key_exists('nombre_seo', $datos) ? CmsMediaNombre::normalizar($datos['nombre_seo']) : ($nombrePrevio ?: CmsMediaNombre::sugerir($item));
      if ($nombre === '') throw new Exception('Escribe un nombre descriptivo para la imagen.');
      $alt = array_key_exists('alt', $datos) ? mb_substr(trim((string) $datos['alt']), 0, 255) : $item['alt'];
      if ($alt === '') throw new Exception('La descripcion alternativa de la imagen no puede quedar vacia.');
      if (!$hayArchivo) {
        if (!is_file($rutaAnterior)) throw new Exception('Falta el archivo actual. Selecciona un archivo para reemplazarlo.');
        $nuevo = CmsMediaArchivo::inspeccionar($rutaAnterior, $item['nombre_archivo']);
      }
      $actual = strtolower($item['extension']);
      $familiaActual = $actual === 'jpeg' ? 'jpg' : $actual;
      $familiaNueva = $nuevo['extension'] === 'jpeg' ? 'jpg' : $nuevo['extension'];
      $cambiaNombre = array_key_exists('nombre_seo', $datos) && $nombre !== $nombrePrevio;
      $cambiaRuta = $cambiaNombre || $familiaActual !== $familiaNueva;
      $extension = $cambiaRuta ? $nuevo['extension'] : $actual;
      $nombreArchivo = $cambiaRuta ? CmsMediaNombre::archivo($nombre, $extension, $id) : $item['nombre_archivo'];
      $url = $cambiaRuta ? '/assets/media/cms/ecommerce/' . $nombreArchivo : $item['url'];
      $destino = CmsMediaArchivo::ruta($url);
      if ($cambiaRuta && file_exists($destino)) throw new Exception('El nombre generado ya existe. Vuelve a intentar el cambio.');
      $duplicado = $this->mediaBuscarPorHash($db, $nuevo['hash']);
      if ($duplicado && (int) $duplicado['id_media_archivo'] !== $id) throw new Exception('Este archivo ya pertenece a otra imagen de la biblioteca. Selecciona esa imagen o utiliza un archivo diferente.');
      $mismosBytes = is_file($rutaAnterior) && hash_file('sha256', $rutaAnterior) === $nuevo['hash'];
      $cambiaArchivo = $cambiaRuta || !$mismosBytes;
      if (!$cambiaArchivo && $alt === $item['alt'] && !$cambiaNombre) {
        $db->rollBack();
        $item = $this->mediaAdjuntarValidacionArchivo($item);
        return $this->respuesta(false, $item['validacion_archivo']['ok'] ? 'info' : 'warning',
          $item['validacion_archivo']['ok'] ? 'La imagen ya contiene ese archivo y esos datos.' : 'La imagen ya esta registrada. ' . $item['validacion_archivo']['mensaje'], $item);
      }
      $aliases = CmsMediaAlias::rutas(array_merge($item, array('metadata_json' => $metadataTexto)));
      if ($cambiaRuta) {
        $metadata['rutas_anteriores'] = array_values(array_filter($aliases, function ($ruta) use ($url) { return $ruta !== $url; }));
        $metadata['nombre_seo'] = $nombre;
      } elseif ($nombrePrevio !== '') { $metadata['nombre_seo'] = $nombrePrevio; }
      if ($cambiaArchivo) {
        $temporal = $this->mediaTemporalPrivado();
        if ($hayArchivo) {
          if (!@move_uploaded_file($archivo['tmp_name'], $temporal)) throw new Exception('No fue posible preparar el archivo nuevo.');
        } elseif (!@copy($rutaAnterior, $temporal)) { throw new Exception('No fue posible preparar el cambio de nombre.'); }
        // IA: Codex GPT-6 | 2026-09-25 | tempnam/copy deja 0600; preparar lectura antes de publicar o retirar el original.
        if (!CmsMediaArchivo::asegurarLecturaPublica($temporal)) throw new Exception('No fue posible habilitar la lectura publica. Se conserva la imagen anterior.');
        if (is_file($rutaAnterior)) {
          $respaldo = $this->mediaTemporalPrivado();
          if (!@copy($rutaAnterior, $respaldo)) throw new Exception('No fue posible resguardar la imagen anterior.');
        }
        if (!@rename($temporal, $destino)) throw new Exception('No fue posible guardar la imagen actualizada.');
        $temporal = ''; $movido = true;
        // Retirar el anterior dentro de la transaccion activa permite rollback y activa su redireccion.
        if ($cambiaRuta && is_file($rutaAnterior)) {
          if (!@unlink($rutaAnterior)) throw new Exception('No fue posible retirar el archivo anterior; se conserva la imagen original.');
          $retirado = true;
        }
      }
      // IA: Codex GPT-6 | 2026-09-25 | Detectar copia incompleta antes de guardar metadata o confirmar el reemplazo.
      $validacion = CmsMediaArchivo::verificarGuardado($destino, $nuevo['bytes'], $nuevo['hash']);
      if (!$validacion['ok']) throw new Exception($validacion['mensaje'] . ' No se confirmo el cambio.');
      $stmt = $db->prepare('UPDATE erp_ecommerce_media_archivos SET nombre_original=:nombre, nombre_archivo=:archivo, ruta_publica=:ruta, extension=:extension, mime=:mime, bytes=:bytes, ancho=:ancho, alto=:alto, hash_sha256=:hash, alt_text=:alt, metadata_json=:metadata, actualizado_por=:usuario, fecha_actualizacion=NOW() WHERE id_media_archivo=:id');
      if (!$stmt || !$stmt->execute(array(
        ':nombre' => $hayArchivo ? mb_substr(basename($archivo['name']), 0, 255) : $item['nombre_original'],
        ':archivo' => $nombreArchivo, ':ruta' => $url, ':extension' => $extension, ':mime' => $nuevo['mime'],
        ':bytes' => $nuevo['bytes'], ':ancho' => $nuevo['ancho'], ':alto' => $nuevo['alto'], ':hash' => $nuevo['hash'],
        ':alt' => $alt, ':metadata' => json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ':usuario' => intval($idUsuario) ?: null, ':id' => $id
      ))) throw new Exception('No fue posible registrar el cambio de Media CMS.');
      $resultado = $this->mediaBuscarPorId($db, $id);
      $resultado['validacion_archivo'] = $validacion;
      if (!$db->commit()) throw new Exception('No fue posible confirmar el cambio de Media CMS.');
      $confirmado = true;
      $resultado['bytes_antes'] = $item['bytes'];
      $resultado['bytes_ahorrados'] = max(0, $item['bytes'] - $nuevo['bytes']);
      $resultado['url_anterior'] = $item['url'];
      $resultado['anterior'] = array('nombre_original' => $item['nombre_original'], 'url' => $item['url'], 'extension' => $actual, 'alt' => $item['alt'], 'bytes' => $item['bytes'], 'ancho' => $item['ancho'], 'alto' => $item['alto']);
      $limpio = !$respaldo || @unlink($respaldo);
      if ($limpio) $respaldo = '';
      else { $conservarRespaldo = true; error_log('Media CMS: limpieza pendiente del resguardo ' . $respaldo); }
      return $this->respuesta(false, $limpio ? 'success' : 'warning',
        $limpio ? 'Imagen actualizada. Las direcciones anteriores siguen funcionando; el alt guardado en cada pagina se conserva.' : 'Imagen actualizada; queda un resguardo privado pendiente de limpieza.',
        $resultado);
    } catch (Throwable $e) {
      $falloRestauracion = false;
      if ($movido && !$confirmado) {
        $mismaRuta = $destino === $rutaAnterior;
        if ($respaldo && is_file($respaldo) && ($mismaRuta || $retirado)) {
          // IA: Codex GPT-6 | 2026-09-25 | Recuperar bytes y lectura publica; conservar respaldo si cualquiera falla.
          if (!CmsMediaArchivo::asegurarLecturaPublica($respaldo) || !@rename($respaldo, $rutaAnterior)) { $conservarRespaldo = true; $falloRestauracion = true; }
          else $respaldo = '';
        }
        if (!$mismaRuta && is_file($destino) && !@unlink($destino)) $falloRestauracion = true;
        // Cuando faltaba el original y no habia resguardo, retirar solo el nuevo archivo.
        if ($mismaRuta && !$existiaAnterior && is_file($destino) && !@unlink($destino)) $falloRestauracion = true;
      }
      $rollbackCompleto = $this->mediaRevertirTransaccion($db);
      if ($falloRestauracion) {
        if ($conservarRespaldo) error_log('Media CMS: restauracion pendiente del resguardo ' . $respaldo);
        return $this->respuesta(true, 'danger', 'No se pudo completar la recuperacion. Se conserva el resguardo disponible para revision administrativa.', array());
      }
      if (!$rollbackCompleto) return $this->respuesta(true, 'danger', 'No fue posible cerrar la transaccion de Media CMS. Revisa su estado antes de reintentar.', array());
      return $this->respuesta(true, 'danger', $e instanceof PDOException ? 'No se pudo actualizar Media CMS. Se restauro la imagen anterior.' : $e->getMessage(), array());
    } finally {
      if ($temporal && is_file($temporal)) @unlink($temporal);
      if (!$conservarRespaldo && $respaldo && is_file($respaldo)) @unlink($respaldo);
    }
  }

  /** IA: Codex GPT-6 | Fecha: 2026-09-24
   * Proposito: resolver direcciones historicas publicas sin exponer la ficha administrativa.
   * Impacto: enlaces CMS antiguos; no redirige a si mismo ni a archivos inexistentes.
   */
  public function mediaResolverAliasInterno($ruta) {
    try {
      $destino = CmsMediaAlias::resolver($this->getConexion(), $ruta);
      if (!$destino || $destino['url'] === $ruta || !is_file(CmsMediaArchivo::ruta($destino['url']))) return null;
      return $destino;
    } catch (Throwable $e) { return null; }
  }

  /** IA: Codex GPT-6 | Fecha: 2026-09-24
   * Proposito: eliminar solamente medios sin referencias guardadas o fallback.
   * Contrato: bloquea filas durante revision y conserva archivo si falla la baja BD.
   */
  private function mediaGestionEliminar($datos, $idUsuario) {
    $db = $this->getConexion(); $respaldo = ''; $ruta = ''; $confirmado = false; $retirado = false;
    try {
      if (!$db->beginTransaction()) throw new Exception('No fue posible iniciar la eliminacion de Media CMS.');
      $id = intval($this->valor($datos, 'id_media_archivo', $this->valor($datos, 'media_id', 0)));
      $lock = $db->prepare('SELECT id_media_archivo FROM erp_ecommerce_media_archivos WHERE id_media_archivo=:id FOR UPDATE');
      if (!$lock || !$lock->execute(array(':id' => $id))) throw new Exception('No fue posible bloquear la imagen para eliminarla.');
      $item = $this->mediaBuscarPorId($db, $id);
      if (!$item) throw new Exception('La imagen de Media CMS no existe.');
      $usos = CmsMediaReferencias::usos($db, $item, true);
      if ($usos) {
        $db->rollBack();
        return $this->respuesta(true, 'warning', 'La imagen tiene usos guardados. Reemplazala conservando su direccion o retira esas referencias antes de eliminar.', array('usos' => $usos, 'total' => count($usos), 'puede_eliminar' => false));
      }
      $ruta = CmsMediaArchivo::ruta($item['url']);
      if (is_file($ruta)) {
        $respaldo = $this->mediaTemporalPrivado();
        if (!@rename($ruta, $respaldo)) throw new Exception('No se pudo retirar el archivo. La imagen permanece en la biblioteca.');
        $retirado = true;
      }
      $stmt = $db->prepare('DELETE FROM erp_ecommerce_media_archivos WHERE id_media_archivo=:id');
      if (!$stmt || !$stmt->execute(array(':id' => $id))) throw new Exception('No fue posible registrar la eliminacion de Media CMS.');
      if (!$db->commit()) throw new Exception('No fue posible confirmar la eliminacion de Media CMS.');
      $confirmado = true;
      $limpio = !$respaldo || @unlink($respaldo);
      return $this->respuesta(false, $limpio ? 'success' : 'warning', $limpio ? 'Imagen eliminada de Media CMS.' : 'Imagen retirada; queda un resguardo privado pendiente de limpieza.', array('id_media_archivo' => $id, 'url' => $item['url'], 'archivo_eliminado' => $limpio, 'eliminado_por' => intval($idUsuario)));
    } catch (Throwable $e) {
      // tempnam crea un archivo vacio: solamente contiene el original despues de un rename exitoso.
      $falloRestauracion = false;
      if (!$confirmado && $retirado && $respaldo && is_file($respaldo)) {
        // IA: Codex GPT-6 | 2026-09-25 | Una baja revertida debe recuperar una imagen publicamente legible.
        $falloRestauracion = !CmsMediaArchivo::asegurarLecturaPublica($respaldo) || !@rename($respaldo, $ruta);
        if ($falloRestauracion) error_log('Media CMS: restauracion pendiente del resguardo ' . $respaldo);
      } elseif (!$retirado && $respaldo && is_file($respaldo)) { @unlink($respaldo); }
      $rollbackCompleto = $this->mediaRevertirTransaccion($db);
      if ($falloRestauracion) return $this->respuesta(true, 'danger', 'Fallo la restauracion. El archivo permanece en resguardo privado para recuperacion administrativa.', array());
      if (!$rollbackCompleto) return $this->respuesta(true, 'danger', 'No fue posible cerrar la transaccion de Media CMS. Revisa su estado antes de volver a modificar la imagen.', array('puede_eliminar' => false));
      return $this->respuesta(true, 'danger', $e instanceof PDOException ? 'No se pudo verificar o eliminar la imagen. No se autorizo la baja.' : $e->getMessage(), array('puede_eliminar' => false));
    }
  }

  /** IA: Codex GPT-6 | Fecha: 2026-09-24
   * Proposito: cerrar el rollback despues de restaurar archivos sin interrumpir su recuperacion.
   * Impacto: reemplazos y bajas CMS; no libera el bloqueo antes de recuperar el original.
   * Contrato: devuelve false ante un fallo de rollback; nunca elimina resguardos por ese fallo.
   */
  private function mediaRevertirTransaccion($db) {
    try {
      return !$db || !$db->inTransaction() || $db->rollBack();
    } catch (Throwable $error) {
      error_log('Media CMS: fallo al revertir la transaccion.');
      return false;
    }
  }

  /** IA: Codex GPT-6 | Fecha: 2026-09-24
   * Proposito: staging fuera de la carpeta publica para rollback de cambios individuales.
   * Contrato: archivo temporal unico; se elimina tras exito o restauracion.
   */
  private function mediaTemporalPrivado() {
    $dir = dirname(__DIR__, 2) . '/storage/cms_media_resguardo';
    if (!is_dir($dir) && !@mkdir($dir, 0700, true) && !is_dir($dir)) throw new Exception('No fue posible preparar el resguardo de Media.');
    $ruta = @tempnam($dir, 'media_');
    if (!$ruta) throw new Exception('No fue posible preparar el archivo temporal.');
    return $ruta;
  }
}
