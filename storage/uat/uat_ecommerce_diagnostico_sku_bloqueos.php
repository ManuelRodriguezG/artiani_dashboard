<?php
/**
 * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-22
 * Proposito: diagnosticar bloqueos ecommerce de un SKU usando las mismas reglas de publicabilidad.
 * Impacto: Ecommerce gobierno/publicaciones; ayuda a distinguir bloqueo real de cruce de informacion o bug.
 * Contrato: solo lectura; no publica, no cambia slugs, precios, inventario ni imagenes.
 */

require __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/modelos/EcommerceCatalogoPublico.php";

class UatEcommerceDiagnosticoSkuBloqueos extends EcommerceCatalogoPublico {
  public function conexionPublica() {
    return $this->getConexion();
  }

  public function candidatoPublico($db, $idSku) {
    $ref = new ReflectionMethod("EcommerceCatalogoPublico", "consultarCandidatoPorSku");
    $ref->setAccessible(true);
    return $ref->invoke($this, $db, $idSku);
  }

  public function bloqueosPublicos($fila) {
    $ref = new ReflectionMethod("EcommerceCatalogoPublico", "bloqueosPublicacion");
    $ref->setAccessible(true);
    return $ref->invoke($this, $fila);
  }

  public function auditoriaEditorialPublica($fila, $publicacion = array()) {
    $ref = new ReflectionMethod("EcommerceCatalogoPublico", "auditoriaEditorialPublicacion");
    $ref->setAccessible(true);
    return $ref->invoke($this, $fila, $publicacion);
  }

  public function prepararPublicacionPublica($idSku) {
    return $this->prepararPublicacion(array("id_sku" => $idSku));
  }

  public function bloqueosConConfirmacionesPublico($bloqueos, $datos = array()) {
    $ref = new ReflectionMethod("EcommerceCatalogoPublico", "bloqueosConConfirmacionesOperativas");
    $ref->setAccessible(true);
    return $ref->invoke($this, $bloqueos, $datos);
  }

  public function conflictoSlugPublico($db, $slug, $idSku) {
    $ref = new ReflectionMethod("EcommerceCatalogoPublico", "conflictoSlugPublicacion");
    $ref->setAccessible(true);
    return $ref->invoke($this, $db, $slug, $idSku);
  }

  public function slugificarPublico($texto) {
    $ref = new ReflectionMethod("EcommerceCatalogoPublico", "slugificar");
    $ref->setAccessible(true);
    return $ref->invoke($this, $texto);
  }
}

$skuBuscado = isset($argv[1]) ? trim((string) $argv[1]) : "GRAVA-28";
$modelo = new UatEcommerceDiagnosticoSkuBloqueos();
$db = $modelo->conexionPublica();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$salida = array(
  "sku_buscado" => $skuBuscado,
  "matches_sku" => array(),
  "seleccion" => null,
  "candidato" => null,
  "bloqueos_publicacion" => array(),
  "auditoria_editorial" => array(),
  "preparar_publicacion" => array(),
  "intento_informativo_sin_confirmaciones" => array(),
  "intento_informativo_confirmando_no_granel" => array(),
  "diagnostico" => array()
);

$stmt = $db->prepare("SELECT s.id_sku, s.sku, s.nombre nombre_sku, s.estatus estatus_sku, s.tipo_inventario,
    p.id_producto_erp, p.nombre nombre_producto, p.descripcion descripcion_producto, p.estatus estatus_producto,
    m.nombre marca
  FROM erp_catalogo_skus s
  INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
  LEFT JOIN erp_catalogo_marcas m ON m.id_marca_erp=p.id_marca_erp
  WHERE s.sku=:sku OR s.sku LIKE :like OR s.nombre LIKE :texto OR p.nombre LIKE :texto
  ORDER BY s.sku=:sku DESC, s.id_sku ASC
  LIMIT 25");
$stmt->execute(array(
  ":sku" => $skuBuscado,
  ":like" => "%" . $skuBuscado . "%",
  ":texto" => "%" . str_replace("-", " ", $skuBuscado) . "%"
));
$salida["matches_sku"] = $stmt->fetchAll(PDO::FETCH_ASSOC);

$seleccion = null;
foreach ($salida["matches_sku"] as $row) {
  if (strcasecmp((string) $row["sku"], $skuBuscado) === 0) {
    $seleccion = $row;
    break;
  }
}
if (!$seleccion && !empty($salida["matches_sku"])) {
  $seleccion = $salida["matches_sku"][0];
}
$salida["seleccion"] = $seleccion;

if (!$seleccion) {
  $salida["diagnostico"][] = "No encontre SKU por codigo/nombre.";
  echo json_encode($salida, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
  exit(0);
}

$idSku = (int) $seleccion["id_sku"];
$fila = $modelo->candidatoPublico($db, $idSku);
$salida["candidato"] = $fila ?: null;
if (!$fila) {
  $salida["diagnostico"][] = "El SKU no aparece como candidato activo para ecommerce: producto/SKU inactivo o cruce base no cumple.";
  echo json_encode($salida, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
  exit(0);
}

$salida["bloqueos_publicacion"] = $modelo->bloqueosPublicos($fila);
$salida["auditoria_editorial"] = $modelo->auditoriaEditorialPublica($fila);

$prep = $modelo->prepararPublicacionPublica($idSku);
$salida["preparar_publicacion"] = array(
  "error" => isset($prep["error"]) ? $prep["error"] : null,
  "tipo" => isset($prep["tipo"]) ? $prep["tipo"] : null,
  "mensaje" => isset($prep["mensaje"]) ? $prep["mensaje"] : null,
  "bloqueos_publicacion" => isset($prep["depurar"]["bloqueos_publicacion"]) ? $prep["depurar"]["bloqueos_publicacion"] : array(),
  "publicacion_actual" => isset($prep["depurar"]["publicacion_actual"]) ? $prep["depurar"]["publicacion_actual"] : null,
  "publicacion_sugerida" => isset($prep["depurar"]["publicacion_sugerida"]) ? $prep["depurar"]["publicacion_sugerida"] : null
);

$simularInformativo = function ($datos) use ($modelo, $db, $fila, $idSku, $prep) {
  $depPrep = isset($prep["depurar"]) ? $prep["depurar"] : array();
  $sugerida = isset($depPrep["publicacion_sugerida"]) ? $depPrep["publicacion_sugerida"] : array();
  $actual = isset($depPrep["publicacion_actual"]) ? $depPrep["publicacion_actual"] : array();
  $slug = $modelo->slugificarPublico(isset($datos["slug"]) ? $datos["slug"] : (isset($actual["slug"]) ? $actual["slug"] : (isset($sugerida["slug"]) ? $sugerida["slug"] : "")));
  $titulo = trim((string) (isset($datos["titulo_publico"]) ? $datos["titulo_publico"] : (isset($actual["titulo_publico"]) ? $actual["titulo_publico"] : (isset($sugerida["titulo_publico"]) ? $sugerida["titulo_publico"] : (isset($fila["nombre_publico"]) ? $fila["nombre_publico"] : "")))));
  $descripcion = trim((string) (isset($datos["descripcion_publica"]) ? $datos["descripcion_publica"] : (isset($actual["descripcion_publica"]) ? $actual["descripcion_publica"] : (isset($sugerida["descripcion_publica"]) ? $sugerida["descripcion_publica"] : ""))));
  $presentacion = trim((string) (isset($datos["presentacion_publica"]) ? $datos["presentacion_publica"] : (isset($actual["presentacion_publica"]) ? $actual["presentacion_publica"] : (isset($sugerida["presentacion_publica"]) ? $sugerida["presentacion_publica"] : ""))));
  $exigirImagen = intval(isset($datos["exigir_imagen"]) ? $datos["exigir_imagen"] : 0) === 1;
  $bloqueos = array();
  $advertencias = array();
  foreach ($modelo->bloqueosPublicos($fila) as $bloqueo) {
    if (in_array($bloqueo, array("venta_fraccionaria_bloqueada_fase_1", "posible_granel_textual", "html_no_permitido"), true)) {
      $bloqueos[] = $bloqueo;
    } elseif ($bloqueo === "imagen_faltante" && $exigirImagen) {
      $bloqueos[] = $bloqueo;
    } elseif ($bloqueo !== "publicacion_existente") {
      $advertencias[] = $bloqueo;
    }
  }
  $bloqueos = $modelo->bloqueosConConfirmacionesPublico($bloqueos, $datos);
  $auditoria = $modelo->auditoriaEditorialPublica($fila, array(
    "titulo_publico" => $titulo,
    "descripcion_publica" => $descripcion,
    "presentacion_publica" => $presentacion
  ));
  foreach ((array) (isset($auditoria["bloqueos_criticos"]) ? $auditoria["bloqueos_criticos"] : array()) as $bloqueoEditorial) {
    if (in_array($bloqueoEditorial, array("posible_granel_textual", "html_no_permitido"), true)) {
      $bloqueos[] = $bloqueoEditorial;
    } else {
      $advertencias[] = $bloqueoEditorial;
    }
  }
  $bloqueos = $modelo->bloqueosConConfirmacionesPublico($bloqueos, $datos);
  foreach ((array) (isset($auditoria["alertas"]) ? $auditoria["alertas"] : array()) as $alerta) {
    $advertencias[] = $alerta;
  }
  if ($slug === "") { $bloqueos[] = "slug_requerido"; }
  if ($titulo === "") { $bloqueos[] = "titulo_publico_requerido"; }
  if ($slug !== "" && $modelo->conflictoSlugPublico($db, $slug, $idSku)) { $bloqueos[] = "slug_ya_usado_por_otro_sku"; }
  return array(
    "read_only" => true,
    "escribe_bd" => false,
    "slug" => $slug,
    "titulo_publico" => $titulo,
    "bloqueos_publicacion" => array_values(array_unique($bloqueos)),
    "advertencias_publicacion" => array_values(array_unique($advertencias)),
    "auditoria_editorial" => $auditoria
  );
};

$salida["intento_informativo_sin_confirmaciones"] = $simularInformativo(array("id_sku" => $idSku, "exigir_imagen" => 0));
$salida["intento_informativo_confirmando_no_granel"] = $simularInformativo(array("id_sku" => $idSku, "exigir_imagen" => 0, "confirmar_no_granel_textual" => 1));

if (in_array("venta_fraccionaria_bloqueada_fase_1", $salida["bloqueos_publicacion"], true)) {
  $salida["diagnostico"][] = "Bloqueo real: el SKU esta marcado como venta fraccionaria; el ecommerce publico lo excluye por diseño.";
}
if (in_array("posible_granel_textual", $salida["bloqueos_publicacion"], true)) {
  $salida["diagnostico"][] = "Bloqueo editorial: el texto contiene señales de granel/por kilo. Se puede quitar con confirmacion operativa si no es granel.";
}
if (!empty($salida["intento_informativo_confirmando_no_granel"]["bloqueos_publicacion"])) {
  $salida["diagnostico"][] = "Aun confirmando no granel textual, quedan bloqueos criticos para informativo.";
} elseif (!empty($salida["intento_informativo_sin_confirmaciones"]["bloqueos_publicacion"])) {
  $salida["diagnostico"][] = "El bloqueo parece depender de confirmacion operativa; sin confirmacion no publica, con confirmacion quedaria publicable.";
}

echo json_encode($salida, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
