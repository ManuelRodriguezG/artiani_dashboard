<?php
/**
 * IA: Codex GPT-5
 * Fecha: 2026-08-14
 * Proposito: ocultar categorias historicas para dejar visible solo el arbol operativo CAT16 durante reclasificacion.
 * Impacto: Catalogo ERP; cambia estatus de categorias no CAT16 sin eliminar registros ni relaciones.
 * Contrato: preview por defecto; aplica solo con --execute, token y respaldo externo existente.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";

class UatCatalogoCategoriasHistoricasOcultarApply extends CRUD {
  private $tokenEsperado = "CATALOGO_CATEGORIAS_HISTORICAS_OCULTAR";

  public function ejecutar($argv) {
    $args = $this->parseArgs($argv);
    $execute = isset($args["execute"]);
    $token = isset($args["token"]) ? (string)$args["token"] : "";
    $respaldo = isset($args["respaldo"]) ? (string)$args["respaldo"] : "";

    $db = $this->getConexion();
    if (!$db) {
      return array("error" => true, "mensaje" => "Sin conexion a BD.");
    }

    $preview = $this->previsualizar($db);
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
        "resumen" => $preview["resumen"],
        "muestra_ocultar" => array_slice($preview["categorias"], 0, 80),
        "nota" => "No se modifico BD."
      );
    }

    $db->beginTransaction();
    try {
      $stmt = $db->prepare("UPDATE erp_catalogo_categorias
        SET estatus='inactiva', fecha_actualizacion=CURRENT_TIMESTAMP
        WHERE estatus='activa' AND codigo NOT LIKE 'CAT16-%'");
      $stmt->execute();
      $afectadas = $stmt->rowCount();
      $db->commit();

      return array(
        "error" => false,
        "modo" => "execute",
        "mensaje" => "Categorias historicas ocultadas. Queda visible el arbol operativo CAT16 para reclasificacion.",
        "respaldo_usado" => $respaldo,
        "filas_actualizadas" => intval($afectadas),
        "conteos_despues" => $this->conteos($db)
      );
    } catch (Exception $e) {
      if ($db->inTransaction()) {
        $db->rollBack();
      }
      return array(
        "error" => true,
        "modo" => "execute",
        "mensaje" => "No se pudieron ocultar categorias historicas.",
        "depurar" => $e->getMessage()
      );
    }
  }

  private function previsualizar($db) {
    $categorias = $db->query("SELECT id_categoria_erp, codigo, nombre, ruta, tipo_categoria, origen, permite_productos, estatus
      FROM erp_catalogo_categorias
      WHERE estatus='activa' AND codigo NOT LIKE 'CAT16-%'
      ORDER BY tipo_categoria, ruta, id_categoria_erp")->fetchAll(PDO::FETCH_ASSOC);
    return array(
      "resumen" => $this->conteos($db) + array("categorias_a_ocultar" => count($categorias)),
      "categorias" => $categorias
    );
  }

  private function conteos($db) {
    return array(
      "categorias_total" => intval($db->query("SELECT COUNT(*) FROM erp_catalogo_categorias")->fetchColumn()),
      "categorias_activas" => intval($db->query("SELECT COUNT(*) FROM erp_catalogo_categorias WHERE estatus='activa'")->fetchColumn()),
      "categorias_cat16_activas" => intval($db->query("SELECT COUNT(*) FROM erp_catalogo_categorias WHERE estatus='activa' AND codigo LIKE 'CAT16-%'")->fetchColumn()),
      "categorias_historicas_activas" => intval($db->query("SELECT COUNT(*) FROM erp_catalogo_categorias WHERE estatus='activa' AND codigo NOT LIKE 'CAT16-%'")->fetchColumn())
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
echo json_encode((new UatCatalogoCategoriasHistoricasOcultarApply())->ejecutar($argv), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
