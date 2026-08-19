<?php
/**
 * IA: Codex GPT-5
 * Fecha: 2026-08-14
 * Proposito: limpiar relaciones producto-categoria para reclasificar Catalogo ERP desde el arbol 1-6.
 * Impacto: Catalogo ERP; deja productos sin categoria hasta que se reclasifiquen.
 * Contrato: preview por defecto; aplica solo con --execute, token y respaldo externo existente.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";

class UatCatalogoCategoriasRelacionesLimpiarApply extends CRUD {
  private $tokenEsperado = "CATALOGO_CATEGORIAS_RELACIONES_LIMPIAR";

  public function ejecutar($argv) {
    $args = $this->parseArgs($argv);
    $execute = isset($args["execute"]);
    $token = isset($args["token"]) ? (string)$args["token"] : "";
    $respaldo = isset($args["respaldo"]) ? (string)$args["respaldo"] : "";

    $db = $this->getConexion();
    $conteos = $this->conteos($db);
    $bloqueo = $this->bloqueo($execute, $token, $respaldo);

    if (!$execute || $bloqueo !== "") {
      return array(
        "error" => false,
        "modo" => "preview",
        "requiere" => array(
          "execute" => true,
          "token" => $this->tokenEsperado,
          "respaldo_externo_existente" => "ruta real fuera del proyecto"
        ),
        "motivo_bloqueo" => $bloqueo,
        "conteos_actuales" => $conteos,
        "impacto" => "Se eliminarian todas las filas de erp_catalogo_producto_categorias."
      );
    }

    $db->beginTransaction();
    try {
      $afectadas = $db->exec("DELETE FROM erp_catalogo_producto_categorias");
      $db->commit();
      return array(
        "error" => false,
        "modo" => "execute",
        "mensaje" => "Relaciones producto-categoria limpiadas. Catalogo queda listo para reclasificacion desde el arbol 1-6.",
        "respaldo_usado" => $respaldo,
        "filas_eliminadas" => intval($afectadas),
        "conteos_antes" => $conteos,
        "conteos_despues" => $this->conteos($db)
      );
    } catch (Exception $e) {
      if ($db->inTransaction()) {
        $db->rollBack();
      }
      return array(
        "error" => true,
        "modo" => "execute",
        "mensaje" => "No se pudo limpiar relaciones producto-categoria.",
        "depurar" => $e->getMessage()
      );
    }
  }

  private function conteos($db) {
    $productosTotal = intval($db->query("SELECT COUNT(*) FROM erp_catalogo_productos")->fetchColumn());
    $relacionesTotal = intval($db->query("SELECT COUNT(*) FROM erp_catalogo_producto_categorias")->fetchColumn());
    $productosConCategoria = intval($db->query("SELECT COUNT(DISTINCT id_producto_erp) FROM erp_catalogo_producto_categorias")->fetchColumn());
    return array(
      "productos_total" => $productosTotal,
      "relaciones_total" => $relacionesTotal,
      "productos_con_categoria" => $productosConCategoria,
      "productos_sin_categoria" => max(0, $productosTotal - $productosConCategoria)
    );
  }

  private function bloqueo($execute, $token, $respaldo) {
    if (!$execute) {
      return "";
    }
    if ($token !== $this->tokenEsperado) {
      return "Token invalido.";
    }
    if ($respaldo === "" || !file_exists($respaldo)) {
      return "Respaldo externo inexistente o no indicado.";
    }
    $proyecto = realpath(__DIR__ . "/../..");
    $respaldoReal = realpath($respaldo);
    if ($proyecto && $respaldoReal && stripos($respaldoReal, $proyecto) === 0) {
      return "El respaldo no puede estar dentro del proyecto.";
    }
    return "";
  }

  private function parseArgs($argv) {
    $args = array();
    foreach ($argv as $arg) {
      if ($arg === "--execute") {
        $args["execute"] = true;
        continue;
      }
      if (strpos($arg, "--") === 0 && strpos($arg, "=") !== false) {
        list($key, $value) = explode("=", substr($arg, 2), 2);
        $args[$key] = $value;
      }
    }
    return $args;
  }
}

header("Content-Type: application/json; charset=utf-8");
echo json_encode((new UatCatalogoCategoriasRelacionesLimpiarApply())->ejecutar($argv), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
