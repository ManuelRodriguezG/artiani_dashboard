<?php
/**
 * IA: Codex GPT-5
 * Fecha: 2026-06-29
 * Proposito: corrige nombres danados de atributos maestros con mapa deterministico.
 * Impacto: Catalogo ERP; mejora legibilidad de atributos sin mover valores de SKUs.
 * Contrato: actualiza solo nombre/unidad de atributos; no fusiona duplicados ni toca erp_catalogo_sku_atributos.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";

class UatCatalogoAtributosTextoRepararApply extends CRUD {
  public function ejecutar() {
    $db = $this->getConexion();
    $items = $db->query("SELECT id_atributo_erp, codigo, nombre, unidad
      FROM erp_catalogo_atributos
      WHERE nombre REGEXP 'Ã|Â|�|├|┬|â'
         OR unidad REGEXP 'Ã|Â|�|├|┬|â'
         OR codigo='ATR-DIAMETRO'
      ORDER BY id_atributo_erp")->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $db->prepare("UPDATE erp_catalogo_atributos
      SET nombre=:nombre, unidad=:unidad
      WHERE id_atributo_erp=:id");
    $actualizados = array();

    $db->beginTransaction();
    try {
      foreach ($items as $item) {
        $nombre = $this->repararTexto($item["nombre"]);
        $unidad = $this->repararTexto($item["unidad"]);
        if ($item["codigo"] === "ATR-DIAMETRO" && ($nombre === "Di?metro" || $nombre === "Diametro")) {
          $nombre = "Diámetro";
        }
        if ($nombre === $item["nombre"] && $unidad === $item["unidad"]) {
          continue;
        }
        $stmt->execute(array(
          ":nombre" => $nombre,
          ":unidad" => $unidad === "" ? null : $unidad,
          ":id" => intval($item["id_atributo_erp"])
        ));
        $actualizados[] = array(
          "id_atributo_erp" => intval($item["id_atributo_erp"]),
          "codigo" => $item["codigo"],
          "nombre_antes" => $item["nombre"],
          "nombre_despues" => $nombre
        );
      }
      $db->commit();
    } catch (Exception $e) {
      if ($db->inTransaction()) {
        $db->rollBack();
      }
      throw $e;
    }

    return array(
      "actualizados" => count($actualizados),
      "detalle" => $actualizados,
      "nota" => "No se tocaron productos, SKUs ni valores de atributos."
    );
  }

  private function repararTexto($texto) {
    return strtr((string)$texto, array(
      "├â┬í" => "á",
      "├â┬¡" => "í",
      "├â┬│" => "ó",
      "├â┬®" => "é",
      "├â┬║" => "ú",
      "├â┬▒" => "ñ",
      "├â┬ü" => "Á",
      "├â┬ì" => "Í",
      "├â┬ô" => "Ó",
      "├â┬ë" => "É",
      "├â┬Ü" => "Ú",
      "├â┬æ" => "Ñ"
    ));
  }
}

header("Content-Type: application/json; charset=utf-8");
echo json_encode((new UatCatalogoAtributosTextoRepararApply())->ejecutar(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
