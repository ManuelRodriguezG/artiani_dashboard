<?php
/**
 * IA: Codex GPT-5
 * Fecha: 2026-06-29
 * Proposito: fusiona atributos duplicados sin conflictos hacia atributos canonicos.
 * Impacto: Catalogo ERP; limpia atributos heredados usados por pocos SKUs sin cambiar valores capturados.
 * Contrato: mueve erp_catalogo_sku_atributos solo cuando el SKU no tiene ya el atributo canonico; luego inactiva heredados sin uso.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";

class UatCatalogoAtributosDuplicadosFusionApply extends CRUD {
  public function ejecutar() {
    $db = $this->getConexion();
    $grupos = array(
      "Alto" => array("canonico" => "ATR-ALTO", "heredados" => array("ECOM-ATR-329d0259b8f9da84")),
      "Ancho" => array("canonico" => "ATR-ANCHO", "heredados" => array("ECOM-ATR-f80492b5ad62275e")),
      "Diametro" => array("canonico" => "ATR-DIAMETRO", "heredados" => array("ECOM-ATR-3b069e54516fc717"))
    );
    $resultado = array();
    $db->beginTransaction();
    try {
      foreach ($grupos as $nombre => $grupo) {
        $resultado[$nombre] = $this->fusionarGrupo($db, $grupo["canonico"], $grupo["heredados"]);
      }
      $db->commit();
    } catch (Exception $e) {
      if ($db->inTransaction()) {
        $db->rollBack();
      }
      throw $e;
    }
    return array(
      "fusion" => $resultado,
      "nota" => "Se movieron solo atributos sin conflicto; no se editaron productos ni valores."
    );
  }

  private function fusionarGrupo($db, $codigoCanonico, $codigosHeredados) {
    $idCanonico = $this->idAtributo($db, $codigoCanonico);
    $idsHeredados = array();
    foreach ($codigosHeredados as $codigo) {
      $id = $this->idAtributo($db, $codigo);
      if ($id > 0) {
        $idsHeredados[] = $id;
      }
    }
    if ($idCanonico <= 0 || empty($idsHeredados)) {
      throw new Exception("Falta atributo canonico o heredado para " . $codigoCanonico);
    }

    $idsSql = implode(",", array_map("intval", $idsHeredados));
    $conflictos = $db->prepare("SELECT COUNT(*)
      FROM erp_catalogo_sku_atributos canonico
      INNER JOIN erp_catalogo_sku_atributos heredado ON heredado.id_sku=canonico.id_sku AND heredado.id_atributo_erp IN ($idsSql)
      WHERE canonico.id_atributo_erp=:canonico");
    $conflictos->execute(array(":canonico" => $idCanonico));
    if (intval($conflictos->fetchColumn()) > 0) {
      throw new Exception("Existen SKUs con atributo canonico y heredado para " . $codigoCanonico);
    }

    $mover = $db->prepare("UPDATE erp_catalogo_sku_atributos
      SET id_atributo_erp=:canonico
      WHERE id_atributo_erp IN ($idsSql)
        AND NOT EXISTS (
          SELECT 1 FROM (
            SELECT id_sku FROM erp_catalogo_sku_atributos
            WHERE id_atributo_erp=:canonico_check
          ) existentes
          WHERE existentes.id_sku=erp_catalogo_sku_atributos.id_sku
        )");
    $mover->execute(array(":canonico" => $idCanonico, ":canonico_check" => $idCanonico));
    $movidos = $mover->rowCount();

    $usoPendiente = $db->query("SELECT COUNT(*) FROM erp_catalogo_sku_atributos WHERE id_atributo_erp IN ($idsSql)")->fetchColumn();
    $inactivados = 0;
    if (intval($usoPendiente) === 0) {
      $db->exec("UPDATE erp_catalogo_atributos SET estatus='inactivo' WHERE id_atributo_erp IN ($idsSql)");
      $inactivados = count($idsHeredados);
    }

    return array(
      "id_canonico" => $idCanonico,
      "ids_heredados" => $idsHeredados,
      "valores_movidos" => $movidos,
      "uso_heredado_pendiente" => intval($usoPendiente),
      "atributos_heredados_inactivados" => $inactivados
    );
  }

  private function idAtributo($db, $codigo) {
    $stmt = $db->prepare("SELECT id_atributo_erp FROM erp_catalogo_atributos WHERE codigo=:codigo LIMIT 1");
    $stmt->execute(array(":codigo" => $codigo));
    return intval($stmt->fetchColumn());
  }
}

header("Content-Type: application/json; charset=utf-8");
echo json_encode((new UatCatalogoAtributosDuplicadosFusionApply())->ejecutar(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
