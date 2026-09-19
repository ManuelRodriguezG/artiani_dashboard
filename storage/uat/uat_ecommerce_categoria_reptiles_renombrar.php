<?php
/**
 * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-18
 * Proposito: auditar y renombrar la rama ecommerce "Reptiles y tortugas" sin crear redirecciones.
 * Impacto: Catalogo publico/SEO; actualiza nombre/ruta de categoria ERP y opcionalmente desactiva snapshot SEO viejo.
 * Contrato: dry-run por defecto; escritura solo con token operativo ECOMMERCE_REPTILES_RENOMBRAR.
 */

require __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";

class UatEcommerceCategoriaReptilesRenombrar extends CRUD {
  public function conexion() {
    return $this->getConexion();
  }
}

$token = isset($argv[1]) ? trim((string) $argv[1]) : "";
$aplicar = $token === "ECOMMERCE_REPTILES_RENOMBRAR";
$old = "Reptiles y tortugas";
$new = "Reptiles, anfibios e invertebrados";
$oldPath = "/categoria/reptiles-y-tortugas";
$newPath = "/categoria/reptiles-anfibios-e-invertebrados";

$modelo = new UatEcommerceCategoriaReptilesRenombrar();
$db = $modelo->conexion();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$salida = array(
  "aplicar" => $aplicar,
  "categoria_padre_id" => 485,
  "nombre_anterior" => $old,
  "nombre_nuevo" => $new,
  "path_anterior" => $oldPath,
  "path_nuevo" => $newPath,
  "antes" => array(),
  "plan" => array(),
  "resultado" => array()
);

$stmt = $db->prepare("SELECT id_categoria_erp, id_categoria_padre, codigo, nombre, ruta, nivel, permite_productos, estatus
  FROM erp_catalogo_categorias
  WHERE id_categoria_erp=:id OR ruta LIKE :prefijo
  ORDER BY ruta ASC, id_categoria_erp ASC");
$stmt->execute(array(":id" => 485, ":prefijo" => $old . " /%"));
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
$salida["antes"]["categorias"] = $categorias;

$salida["plan"]["categorias_a_actualizar"] = count($categorias);
$salida["plan"]["cambios_categoria"] = array();
foreach ($categorias as $fila) {
  $rutaActual = (string) $fila["ruta"];
  $nombreNuevo = intval($fila["id_categoria_erp"]) === 485 ? $new : (string) $fila["nombre"];
  $rutaNueva = $rutaActual === $old ? $new : preg_replace('/^' . preg_quote($old, '/') . '\s*\/\s*/', $new . " / ", $rutaActual);
  $salida["plan"]["cambios_categoria"][] = array(
    "id_categoria_erp" => intval($fila["id_categoria_erp"]),
    "nombre_actual" => (string) $fila["nombre"],
    "nombre_nuevo" => $nombreNuevo,
    "ruta_actual" => $rutaActual,
    "ruta_nueva" => $rutaNueva
  );
}

$tablas = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
$tieneSeoUrls = in_array("erp_ecommerce_seo_urls", $tablas, true);
$tieneRedirecciones = in_array("erp_ecommerce_seo_redirecciones", $tablas, true);

if ($tieneSeoUrls) {
  $stmt = $db->prepare("SELECT id_url, tipo, entidad_id, path, url, canonical, title, indexable, activo
    FROM erp_ecommerce_seo_urls
    WHERE entidad_id=485 OR path IN (:old_path, :new_path)
    ORDER BY id_url ASC");
  $stmt->execute(array(":old_path" => $oldPath, ":new_path" => $newPath));
  $salida["antes"]["seo_urls_categoria_padre"] = $stmt->fetchAll(PDO::FETCH_ASSOC);

  $stmt = $db->prepare("SELECT COUNT(*) FROM erp_ecommerce_seo_urls WHERE path LIKE :prefijo AND activo=1");
  $stmt->execute(array(":prefijo" => $oldPath . "/%"));
  $salida["antes"]["seo_urls_hijas_viejas_activas"] = intval($stmt->fetchColumn());
}

if ($tieneRedirecciones) {
  $stmt = $db->prepare("SELECT id_redireccion, url_origen, url_destino, status_code, tipo, activo, revisado
    FROM erp_ecommerce_seo_redirecciones
    WHERE url_origen LIKE :old OR url_destino LIKE :old OR url_origen LIKE :new OR url_destino LIKE :new
    ORDER BY id_redireccion ASC");
  $stmt->execute(array(":old" => "%reptiles-y-tortugas%", ":new" => "%reptiles-anfibios%"));
  $salida["antes"]["redirecciones_relacionadas"] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if (!$aplicar) {
  $salida["resultado"]["dry_run"] = true;
  echo json_encode($salida, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
  exit(0);
}

$db->beginTransaction();
try {
  $stmtPadre = $db->prepare("UPDATE erp_catalogo_categorias
    SET nombre=:nombre, ruta=:ruta, fecha_actualizacion=NOW()
    WHERE id_categoria_erp=485");
  $stmtPadre->execute(array(":nombre" => $new, ":ruta" => $new));

  $stmtHijas = $db->prepare("UPDATE erp_catalogo_categorias
    SET ruta=CONCAT(:nuevo_prefijo, SUBSTRING(ruta, :desde)), fecha_actualizacion=NOW()
    WHERE ruta LIKE :prefijo");
  $stmtHijas->execute(array(
    ":nuevo_prefijo" => $new,
    ":desde" => strlen($old) + 1,
    ":prefijo" => $old . " /%"
  ));

  $seoUrlsDesactivadas = 0;
  $redireccionesActualizadas = 0;
  if ($tieneSeoUrls) {
    $stmtSeo = $db->prepare("UPDATE erp_ecommerce_seo_urls
      SET activo=0, fecha_actualizacion=NOW()
      WHERE activo=1
        AND tipo='categoria'
        AND (path=:old_path OR path LIKE :old_prefix)");
    $stmtSeo->execute(array(":old_path" => $oldPath, ":old_prefix" => $oldPath . "/%"));
    $seoUrlsDesactivadas = $stmtSeo->rowCount();
  }

  if ($tieneRedirecciones) {
    $stmtRedirecciones = $db->prepare("UPDATE erp_ecommerce_seo_redirecciones
      SET url_destino=REPLACE(url_destino, :old_path, :new_path),
          fecha_actualizacion=NOW()
      WHERE activo=1
        AND (url_destino=:old_path_exact OR url_destino LIKE :old_path_prefix)");
    $stmtRedirecciones->execute(array(
      ":old_path" => $oldPath,
      ":new_path" => $newPath,
      ":old_path_exact" => $oldPath,
      ":old_path_prefix" => $oldPath . "/%"
    ));
    $redireccionesActualizadas = $stmtRedirecciones->rowCount();
  }

  $db->commit();
  $salida["resultado"] = array(
    "dry_run" => false,
    "padre_actualizado" => $stmtPadre->rowCount(),
    "hijas_actualizadas" => $stmtHijas->rowCount(),
    "seo_urls_viejas_desactivadas" => $seoUrlsDesactivadas,
    "redirecciones_destino_actualizadas" => $redireccionesActualizadas
  );
} catch (Exception $e) {
  $db->rollBack();
  throw $e;
}

echo json_encode($salida, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
