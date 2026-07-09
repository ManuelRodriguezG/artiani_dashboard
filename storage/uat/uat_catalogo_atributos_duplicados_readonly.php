<?php
/**
 * IA: Codex GPT-5
 * Fecha: 2026-06-29
 * Proposito: audita duplicados de atributos antes de cualquier fusion.
 * Impacto: Catalogo ERP; identifica conflictos de valores por SKU sin modificar datos.
 * Contrato: solo lectura; no actualiza erp_catalogo_sku_atributos ni inactiva atributos.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";

class UatCatalogoAtributosDuplicadosReadonly extends CRUD {
  public function ejecutar() {
    $db = $this->getConexion();
    $grupos = array(
      "Alto" => array("canonico" => "ATR-ALTO", "heredados" => array("ECOM-ATR-329d0259b8f9da84")),
      "Ancho" => array("canonico" => "ATR-ANCHO", "heredados" => array("ECOM-ATR-f80492b5ad62275e")),
      "Diametro" => array("canonico" => "ATR-DIAMETRO", "heredados" => array("ECOM-ATR-3b069e54516fc717"))
    );
    $resultado = array();
    foreach ($grupos as $nombre => $grupo) {
      $resultado[$nombre] = $this->auditarGrupo($db, $grupo["canonico"], $grupo["heredados"]);
    }
    return array(
      "duplicados" => $resultado,
      "nota" => "Solo lectura. Si hay conflictos, la fusion requiere decision operativa por SKU."
    );
  }

  private function auditarGrupo($db, $codigoCanonico, $codigosHeredados) {
    $idCanonico = $this->idAtributo($db, $codigoCanonico);
    $idsHeredados = array();
    foreach ($codigosHeredados as $codigo) {
      $idsHeredados[] = $this->idAtributo($db, $codigo);
    }
    $idsHeredados = array_values(array_filter($idsHeredados));
    if ($idCanonico <= 0 || empty($idsHeredados)) {
      return array("error" => "Falta atributo canonico o heredado", "canonico" => $codigoCanonico, "heredados" => $codigosHeredados);
    }

    $idsSql = implode(",", array_map("intval", $idsHeredados));
    $sql = "SELECT
        COUNT(DISTINCT CASE WHEN sa.id_atributo_erp=:canonico THEN sa.id_sku END) AS skus_canonico,
        COUNT(DISTINCT CASE WHEN sa.id_atributo_erp IN ($idsSql) THEN sa.id_sku END) AS skus_heredado,
        COUNT(DISTINCT CASE WHEN c.id_sku IS NOT NULL AND h.id_sku IS NOT NULL THEN c.id_sku END) AS skus_con_ambos
      FROM erp_catalogo_sku_atributos sa
      LEFT JOIN erp_catalogo_sku_atributos c ON c.id_sku=sa.id_sku AND c.id_atributo_erp=:canonico_join
      LEFT JOIN erp_catalogo_sku_atributos h ON h.id_sku=sa.id_sku AND h.id_atributo_erp IN ($idsSql)
      WHERE sa.id_atributo_erp=:canonico_where OR sa.id_atributo_erp IN ($idsSql)";
    $stmt = $db->prepare($sql);
    $stmt->execute(array(
      ":canonico" => $idCanonico,
      ":canonico_join" => $idCanonico,
      ":canonico_where" => $idCanonico
    ));
    $resumen = $stmt->fetch(PDO::FETCH_ASSOC);

    $conflictos = $db->prepare("SELECT s.id_sku, s.sku, pc.valor AS valor_canonico, ph.valor AS valor_heredado
      FROM erp_catalogo_sku_atributos pc
      INNER JOIN erp_catalogo_sku_atributos ph ON ph.id_sku=pc.id_sku AND ph.id_atributo_erp IN ($idsSql)
      INNER JOIN erp_catalogo_skus s ON s.id_sku=pc.id_sku
      WHERE pc.id_atributo_erp=:canonico
      ORDER BY s.sku
      LIMIT 50");
    $conflictos->execute(array(":canonico" => $idCanonico));

    return array(
      "id_canonico" => $idCanonico,
      "ids_heredados" => $idsHeredados,
      "skus_canonico" => intval($resumen["skus_canonico"]),
      "skus_heredado" => intval($resumen["skus_heredado"]),
      "skus_con_ambos" => intval($resumen["skus_con_ambos"]),
      "muestra_conflictos" => $conflictos->fetchAll(PDO::FETCH_ASSOC)
    );
  }

  private function idAtributo($db, $codigo) {
    $stmt = $db->prepare("SELECT id_atributo_erp FROM erp_catalogo_atributos WHERE codigo=:codigo LIMIT 1");
    $stmt->execute(array(":codigo" => $codigo));
    return intval($stmt->fetchColumn());
  }
}

header("Content-Type: application/json; charset=utf-8");
echo json_encode((new UatCatalogoAtributosDuplicadosReadonly())->ejecutar(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
