<?php

/**
 * IA: Codex GPT-6 | Fecha: 2026-09-24
 * Proposito: localizar referencias persistidas antes de administrar archivos Media CMS.
 * Impacto: protege contenido publicado, borradores, Blog y las imagenes predeterminadas.
 * Contrato: no modifica datos ni esquema; las consultas fallidas impiden afirmar que no hay usos.
 */
class CmsMediaReferencias {
  /**
   * IA: Codex GPT-6 | Fecha: 2026-09-24
   * Proposito: describir los lugares que conservan una imagen, incluidos contenidos pausados.
   * Impacto: permite bloquear eliminaciones sin depender de un indice de usos incompleto, incluidos nombres anteriores.
   * Contrato: item conserva urls_anteriores o metadata_json; retorna [{origen,referencia,estado}]; bloquear exige transaccion y usa FOR UPDATE.
   */
  public static function usos(PDO $db, array $item, bool $bloquear = false) {
    if ($bloquear && !$db->inTransaction()) {
      throw new RuntimeException("La verificacion bloqueante de Media CMS requiere una transaccion activa.");
    }
    $fuentes = self::fuentes();
    $tablas = array_keys($fuentes);
    $tablas[] = "erp_ecommerce_media_usos";
    $tablas[] = "erp_ecommerce_media_archivos";
    $columnas = self::columnasDisponibles($db, $tablas);
    $usos = array();

    foreach ($fuentes as $tabla => $fuente) {
      if (!isset($columnas[$tabla])) { continue; }
      $disponibles = $columnas[$tabla];
      if (!isset($disponibles[$fuente["id"]])) {
        throw new RuntimeException("No se pudo verificar la estructura de referencias de Media CMS: " . $tabla . ".");
      }
      $campos = array_values(array_filter($fuente["campos"], function ($campo) use ($disponibles) {
        return isset($disponibles[$campo]);
      }));
      if (!$campos) { continue; }
      $seleccion = array_unique(array_merge(array($fuente["id"]), $campos, array_values(array_filter(
        array($fuente["estado"], $fuente["etiqueta"]),
        function ($campo) use ($disponibles) { return isset($disponibles[$campo]); }
      ))));
      $sql = "SELECT " . self::identificadores($seleccion) . " FROM `" . $tabla . "`" . ($bloquear ? " FOR UPDATE" : "");
      $stmt = self::consultar($db, $sql);
      while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $coincidencias = array();
        foreach ($campos as $campo) {
          if (self::contieneReferencia($fila[$campo], $item)) { $coincidencias[] = $campo; }
        }
        if (!$coincidencias) { continue; }
        $etiqueta = isset($fila[$fuente["etiqueta"]]) ? trim((string) $fila[$fuente["etiqueta"]]) : "";
        $usos[] = array(
          "origen" => $fuente["origen"],
          "referencia" => ($etiqueta !== "" ? $etiqueta . " · " : "") . "#" . $fila[$fuente["id"]] . " (" . implode(", ", $coincidencias) . ")",
          "estado" => isset($fila[$fuente["estado"]]) ? (string) $fila[$fuente["estado"]] : "registrado"
        );
      }
      self::verificarLectura($stmt);
    }

    $id = (int) ($item["id_media_archivo"] ?? $item["media_id"] ?? 0);
    if ($id > 0 && isset($columnas["erp_ecommerce_media_usos"])) {
      $requeridas = array("id_media_archivo", "entidad_tipo", "entidad_clave", "campo", "estatus");
      self::exigirColumnas($columnas["erp_ecommerce_media_usos"], $requeridas);
      $stmt = self::consultar($db, "SELECT " . self::identificadores($requeridas) . " FROM erp_ecommerce_media_usos WHERE id_media_archivo=:id" . ($bloquear ? " FOR UPDATE" : ""), array(":id" => $id));
      while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $usos[] = array(
          "origen" => "Uso registrado: " . $fila["entidad_tipo"],
          "referencia" => $fila["entidad_clave"] . " · " . $fila["campo"],
          "estado" => (string) $fila["estatus"]
        );
      }
      self::verificarLectura($stmt);
    }

    if ($id > 0 && isset($columnas["erp_ecommerce_media_archivos"])) {
      self::exigirColumnas($columnas["erp_ecommerce_media_archivos"], array("id_media_archivo", "estatus", "uso_sugerido", "tipo_sugerido"));
      foreach (array("home" => "Home", "categoria" => "Categorias") as $uso => $etiqueta) {
        // Mismo criterio de mediaCmsPrincipalPublica: una imagen puede servir sin un payload que la cite.
        $stmt = self::consultar($db,
          "SELECT id_media_archivo FROM erp_ecommerce_media_archivos WHERE estatus='activo' AND uso_sugerido=:uso AND tipo_sugerido IN ('hero','banner','principal') ORDER BY id_media_archivo DESC LIMIT 1" . ($bloquear ? " FOR UPDATE" : ""),
          array(":uso" => $uso)
        );
        $predeterminada = $stmt->fetchColumn();
        self::verificarLectura($stmt);
        if ((int) $predeterminada === $id) {
          $usos[] = array(
            "origen" => "Imagen predeterminada de " . $etiqueta,
            "referencia" => "Seleccion automatica cuando falta una imagen publicada. Reemplaza esta imagen o configura otra imagen predeterminada antes de eliminarla.",
            "estado" => "seleccion_automatica"
          );
        }
      }
    }
    return $usos;
  }

  /**
   * IA: Codex GPT-6 | Fecha: 2026-09-24
   * Proposito: reconocer URLs CMS y referencias por ID/codigo en JSON, HTML y campos simples.
   * Impacto: evita omitir URLs absolutas, escapadas o codificadas y aliases al proteger una imagen renombrada.
   * Contrato: funcion pura; no confunde un nombre de archivo con otro que solo comparte prefijo.
   */
  public static function contieneReferencia($contenido, array $item) {
    return self::buscarReferencia($contenido, $item, CmsMediaAlias::rutas($item), "", 0);
  }

  /**
   * IA: Codex GPT-6 | Fecha: 2026-09-24
   * Proposito: recorrer JSON conservando el significado de las claves que contienen IDs de media.
   * Impacto: protege referencias estructuradas y tambien HTML incrustado; sin persistencia.
   */
  private static function buscarReferencia($contenido, array $item, array $rutas, $clave, $profundidad) {
    if ($profundidad > 64) {
      throw new RuntimeException("El contenido CMS es demasiado profundo para verificar sus referencias de forma segura.");
    }
    if (is_array($contenido)) {
      foreach ($contenido as $campo => $valor) {
        if (self::buscarReferencia($valor, $item, $rutas, (string) $campo, $profundidad + 1)) { return true; }
      }
      return false;
    }
    if (!is_scalar($contenido)) { return false; }
    $id = (int) ($item["id_media_archivo"] ?? $item["media_id"] ?? 0);
    if ($id > 0 && in_array($clave, array("media_id", "id_media_archivo"), true) && ctype_digit((string) $contenido) && (int) $contenido === $id) { return true; }
    $codigo = (string) ($item["codigo"] ?? "");
    if ($codigo !== "" && in_array($clave, array("codigo", "codigo_media", "media_codigo"), true) && (string) $contenido === $codigo) { return true; }
    $texto = (string) $contenido;
    $json = json_decode($texto, true);
    if (json_last_error() === JSON_ERROR_NONE && (is_array($json) || (is_string($json) && $json !== $texto))) {
      if (self::buscarReferencia($json, $item, $rutas, $clave, $profundidad + 1)) { return true; }
    }
    $texto = self::normalizarTexto($texto);
    foreach ($rutas as $ruta) {
      $patron = "~" . preg_quote($ruta, "~") . "(?=$|[?\\#\\s\"'<>),;\\]\\}])~u";
      $coincide = preg_match($patron, $texto);
      if ($coincide === false) {
        // Un byte no UTF-8 en HTML legado no debe desactivar la proteccion de borrado.
        $coincide = preg_match(substr($patron, 0, -1), $texto);
      }
      if ($coincide === 1) { return true; }
    }
    return false;
  }

  /**
   * IA: Codex GPT-6 | Fecha: 2026-09-24
   * Proposito: unificar escapes JSON, entidades HTML y codificacion URL antes de comparar rutas.
   * Impacto: deteccion de referencias; no altera payloads ni URLs guardadas.
   */
  private static function normalizarTexto($texto) {
    for ($i = 0; $i < 3; $i++) {
      $anterior = $texto;
      $texto = html_entity_decode($texto, ENT_QUOTES | ENT_HTML5, "UTF-8");
      $texto = rawurldecode($texto);
      $texto = preg_replace_callback('/(?:\\\\u[0-9a-fA-F]{4})+/', function ($coincidencia) {
        $valor = json_decode('"' . $coincidencia[0] . '"');
        return is_string($valor) ? $valor : $coincidencia[0];
      }, $texto);
      $texto = str_replace("\\/", "/", $texto);
      if ($anterior === $texto) { break; }
    }
    return $texto;
  }

  /**
   * IA: Codex GPT-6 | Fecha: 2026-09-24
   * Proposito: declarar las fuentes CMS/Blog de imagenes auditadas en los modelos del proyecto.
   * Impacto: alcance del bloqueo de eliminacion; los estados pausados y borradores conservan sus archivos.
   */
  private static function fuentes() {
    return array(
      "erp_ecommerce_contenido_bloques" => array("origen" => "Contenido CMS", "id" => "id_bloque", "estado" => "estatus", "etiqueta" => "nombre_interno", "campos" => array("payload_json")),
      "erp_ecommerce_contenido_media" => array("origen" => "Imagen de bloque CMS", "id" => "id_media", "estado" => "estatus", "etiqueta" => "id_bloque", "campos" => array("url_desktop", "url_mobile", "metadata_json")),
      "erp_ecommerce_blog_publicaciones" => array("origen" => "Publicacion de Blog", "id" => "id_blog_publicacion", "estado" => "estado", "etiqueta" => "titulo", "campos" => array("imagen_portada_json", "seo_json", "contenido_html")),
      "erp_ecommerce_blog_media" => array("origen" => "Imagen de Blog", "id" => "id_blog_media", "estado" => "estatus", "etiqueta" => "id_blog_publicacion", "campos" => array("url", "url_desktop", "url_tablet", "url_mobile", "url_thumbnail", "metadata_json")),
      "erp_ecommerce_blog_bloques_interactivos" => array("origen" => "Bloque interactivo de Blog", "id" => "id_blog_bloque_interactivo", "estado" => "estatus", "etiqueta" => "titulo", "campos" => array("imagen_json", "puntos_json")),
      "erp_ecommerce_blog_videos" => array("origen" => "Miniatura de video de Blog", "id" => "id_blog_video", "estado" => "estatus", "etiqueta" => "titulo", "campos" => array("thumbnail"))
    );
  }

  /**
   * IA: Codex GPT-6 | Fecha: 2026-09-24
   * Proposito: reconocer tablas/columnas instaladas sin crear ni cambiar esquema.
   * Impacto: permite instalaciones sin Blog; una consulta fallida bloquea la operacion solicitada.
   */
  private static function columnasDisponibles(PDO $db, array $tablas) {
    $stmt = self::consultar($db, "SELECT TABLE_NAME, COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN (" . implode(",", array_fill(0, count($tablas), "?")) . ")", $tablas);
    $salida = array();
    while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $salida[$fila["TABLE_NAME"]][$fila["COLUMN_NAME"]] = true;
    }
    self::verificarLectura($stmt);
    return $salida;
  }

  /**
   * IA: Codex GPT-6 | Fecha: 2026-09-24
   * Proposito: rechazar estructuras parciales que impidan revisar usos por ID o seleccion automatica.
   * Impacto: elimina falsos resultados sin referencias por columnas obligatorias ausentes.
   */
  private static function exigirColumnas(array $disponibles, array $requeridas) {
    foreach ($requeridas as $campo) {
      if (!isset($disponibles[$campo])) { throw new RuntimeException("La estructura de Media CMS no permite verificar todas sus referencias."); }
    }
  }

  /**
   * IA: Codex GPT-6 | Fecha: 2026-09-24
   * Proposito: construir proyecciones exclusivamente con identificadores declarados por este helper.
   * Impacto: consultas de referencias, sin interpolar entrada del usuario.
   */
  private static function identificadores(array $campos) {
    return implode(", ", array_map(function ($campo) { return "`" . $campo . "`"; }, $campos));
  }

  /**
   * IA: Codex GPT-6 | Fecha: 2026-09-24
   * Proposito: exigir ejecucion correcta incluso cuando PDO no esta configurado para lanzar excepciones.
   * Impacto: un error de lectura nunca se interpreta como una imagen sin usos.
   */
  private static function consultar(PDO $db, $sql, array $parametros = array()) {
    $stmt = $db->prepare($sql);
    if (!$stmt || !$stmt->execute($parametros)) { throw new RuntimeException("No se pudieron verificar las referencias de Media CMS."); }
    return $stmt;
  }

  /**
   * IA: Codex GPT-6 | Fecha: 2026-09-24
   * Proposito: distinguir fin de resultados de errores durante la lectura PDO.
   * Impacto: conserva el bloqueo seguro ante fallos al consultar contenido.
   */
  private static function verificarLectura(PDOStatement $stmt) {
    $codigo = $stmt->errorCode();
    if ($codigo !== null && $codigo !== "00000" && $codigo !== "02000") { throw new RuntimeException("No se pudo completar la verificacion de referencias de Media CMS."); }
  }
}
