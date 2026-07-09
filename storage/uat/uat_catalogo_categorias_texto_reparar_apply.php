<?php
/**
 * IA: Codex GPT-5
 * Fecha: 2026-06-29
 * Proposito: corrige texto danado de categorias heredadas con mapa deterministico.
 * Impacto: Catalogo ERP; mejora legibilidad del CRUD de categorias sin tocar productos ni relaciones.
 * Contrato: actualiza solo nombre, descripcion y ruta cuando contienen secuencias mojibake conocidas.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";

class UatCatalogoCategoriasTextoRepararApply extends CRUD {
  public function ejecutar() {
    $db = $this->getConexion();
    $categorias = $db->query("SELECT id_categoria_erp, nombre, descripcion, ruta
      FROM erp_catalogo_categorias
      WHERE nombre REGEXP 'Ã|Â|�|├|┬|â'
         OR ruta REGEXP 'Ã|Â|�|├|┬|â'
         OR descripcion REGEXP 'Ã|Â|�|├|┬|â'
      ORDER BY id_categoria_erp")->fetchAll(PDO::FETCH_ASSOC);

    $actualizadas = array();
    $stmt = $db->prepare("UPDATE erp_catalogo_categorias
      SET nombre=:nombre, descripcion=:descripcion, ruta=:ruta, fecha_actualizacion=CURRENT_TIMESTAMP
      WHERE id_categoria_erp=:id");

    $db->beginTransaction();
    try {
      foreach ($categorias as $categoria) {
        $nombre = $this->repararTexto($categoria["nombre"]);
        $descripcion = $this->repararTexto($categoria["descripcion"]);
        $ruta = $this->repararTexto($categoria["ruta"]);
        if ($nombre === $categoria["nombre"] && $descripcion === $categoria["descripcion"] && $ruta === $categoria["ruta"]) {
          continue;
        }
        $stmt->execute(array(
          ":nombre" => $nombre,
          ":descripcion" => $descripcion,
          ":ruta" => $ruta,
          ":id" => intval($categoria["id_categoria_erp"])
        ));
        $actualizadas[] = array(
          "id_categoria_erp" => intval($categoria["id_categoria_erp"]),
          "nombre_antes" => $categoria["nombre"],
          "nombre_despues" => $nombre,
          "ruta_antes" => $categoria["ruta"],
          "ruta_despues" => $ruta
        );
      }
      $db->commit();
    } catch (Exception $e) {
      if ($db->inTransaction()) {
        $db->rollBack();
      }
      throw $e;
    }

    $pendientes = intval($db->query("SELECT COUNT(*) FROM erp_catalogo_categorias
      WHERE nombre REGEXP 'Ã|Â|�|├|┬|â'
         OR ruta REGEXP 'Ã|Â|�|├|┬|â'
         OR descripcion REGEXP 'Ã|Â|�|├|┬|â'")->fetchColumn());

    return array(
      "actualizadas" => count($actualizadas),
      "pendientes_texto_danado" => $pendientes,
      "detalle" => $actualizadas,
      "nota" => "No se modificaron productos, SKUs ni relaciones de categorias."
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
echo json_encode((new UatCatalogoCategoriasTextoRepararApply())->ejecutar(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
