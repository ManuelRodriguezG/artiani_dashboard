<?php
/**
 * IA: Codex GPT-5
 * Fecha: 2026-06-29
 * Proposito: validar que la bandeja de Clasificacion pendiente liste productos sin categoria principal.
 * Impacto: Catalogo ERP; confirma que Configuracion ataca el pendiente operativo correcto.
 * Contrato: read-only; no aplica asignaciones, no crea categorias, no modifica marcas.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/modelos/CatalogoErpDatos.php";

class UatCatalogoClasificacionPendienteReadonly extends CatalogoErpDatos {
  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-29
   * Proposito: medir si los productos sin categoria principal ya tienen categorias secundarias aprovechables.
   * Impacto: Catalogo ERP; ayuda a decidir si se puede prellenar sugerencia sin guardar datos.
   * Contrato: solo SELECT.
   */
  public function relacionesCategoriaPendientes() {
    $sql = "SELECT COUNT(*) total,
        SUM(t.relaciones>0) con_relaciones,
        SUM(t.relaciones=1) con_una,
        SUM(t.relaciones>1) con_varias
      FROM (
        SELECT p.id_producto_erp, COUNT(pc.id_producto_categoria) relaciones
        FROM erp_catalogo_productos p
        LEFT JOIN erp_catalogo_producto_categorias pc ON pc.id_producto_erp=p.id_producto_erp
        WHERE p.estatus<>'fusionado'
          AND NOT EXISTS (
            SELECT 1 FROM erp_catalogo_producto_categorias pc2
            WHERE pc2.id_producto_erp=p.id_producto_erp AND pc2.es_principal=1
          )
        GROUP BY p.id_producto_erp
      ) t";
    return $this->getConexion()->query($sql)->fetch(PDO::FETCH_ASSOC);
  }
}

$opciones = array();
foreach ($argv as $arg) {
  if ($arg === "--summary") {
    $opciones["summary"] = true;
    continue;
  }
  if (strpos($arg, "--") === 0 && strpos($arg, "=") !== false) {
    list($key, $value) = explode("=", substr($arg, 2), 2);
    $opciones[$key] = $value;
  }
}
$limite = isset($opciones["limit"]) ? intval($opciones["limit"]) : 10;
$limite = max(1, min(100, $limite));

$modelo = new UatCatalogoClasificacionPendienteReadonly();
$respuesta = $modelo->listarRevisionMetadatosCatalogo();
$depurar = isset($respuesta["depurar"]) && is_array($respuesta["depurar"]) ? $respuesta["depurar"] : array();
$pendientes = isset($depurar["pendientes"]) && is_array($depurar["pendientes"]) ? $depurar["pendientes"] : array();
$categorias = isset($depurar["categorias"]) && is_array($depurar["categorias"]) ? $depurar["categorias"] : array();
$marcas = isset($depurar["marcas"]) && is_array($depurar["marcas"]) ? $depurar["marcas"] : array();

$muestraCategoria = array_slice(array_values(array_filter($pendientes, function ($item) {
  return isset($item["tipo_revision"]) && $item["tipo_revision"] === "categoria";
})), 0, $limite);
$muestraMarca = array_slice(array_values(array_filter($pendientes, function ($item) {
  return isset($item["tipo_revision"]) && $item["tipo_revision"] === "marca";
})), 0, $limite);

if (!empty($opciones["summary"])) {
  $respuesta["depurar"] = array(
    "sin_categoria" => isset($depurar["sin_categoria"]) ? intval($depurar["sin_categoria"]) : 0,
    "marcas_ambiguas" => isset($depurar["marcas_ambiguas"]) ? intval($depurar["marcas_ambiguas"]) : 0,
    "categorias_disponibles" => count($categorias),
    "marcas_disponibles" => count($marcas)
  );
} else {
  $respuesta["depurar"]["pendientes"] = array_slice($pendientes, 0, $limite);
  $respuesta["depurar"]["categorias"] = array_slice($categorias, 0, $limite);
  $respuesta["depurar"]["marcas"] = array_slice($marcas, 0, $limite);
}

$respuesta["depurar"]["contrato_uat"] = array(
  "read_only" => true,
  "no_aplica_asignaciones" => true,
  "criterio_categoria" => "Producto sin relacion principal en erp_catalogo_producto_categorias.es_principal=1"
);
$respuesta["depurar"]["relaciones_categoria_pendientes"] = $modelo->relacionesCategoriaPendientes();
$respuesta["depurar"]["muestra_categoria"] = $muestraCategoria;
$respuesta["depurar"]["muestra_marca"] = $muestraMarca;

echo json_encode($respuesta, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
