<?php
/**
 * IA: Codex GPT-5
 * Fecha: 2026-06-29
 * Proposito: valida disponibilidad read-only del esquema de imagenes de marcas/categorias.
 * Impacto: Catalogo ERP; confirma que la UI puede habilitar CRUD por URL sin crear registros de prueba.
 * Contrato: solo lectura; no inserta imagenes ni modifica marcas/categorias.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";

class UatCatalogoImagenesMaestrosReadinessReadonly extends CRUD {
  public function ejecutar() {
    $db = $this->getConexion();
    $tablas = array(
      "erp_catalogo_marca_imagenes",
      "erp_catalogo_categoria_imagenes"
    );
    $estado = array();
    foreach ($tablas as $tabla) {
      $estado[$tabla] = array(
        "existe" => $this->tablaExiste($db, $tabla),
        "registros" => $this->tablaExiste($db, $tabla) ? $this->contar($db, $tabla) : null,
        "indices" => $this->tablaExiste($db, $tabla) ? $this->indices($db, $tabla) : array()
      );
    }
    return array(
      "schema_disponible" => $estado["erp_catalogo_marca_imagenes"]["existe"] && $estado["erp_catalogo_categoria_imagenes"]["existe"],
      "tablas" => $estado,
      "conteo_maestros" => array(
        "marcas" => $this->contar($db, "erp_catalogo_marcas"),
        "categorias" => $this->contar($db, "erp_catalogo_categorias")
      ),
      "nota" => "Solo lectura; si schema_disponible=true la UI deja de mostrar candado de esquema pendiente."
    );
  }

  private function tablaExiste($db, $tabla) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=:tabla");
    $stmt->execute(array(":tabla" => $tabla));
    return intval($stmt->fetchColumn()) > 0;
  }

  private function contar($db, $tabla) {
    return intval($db->query("SELECT COUNT(*) FROM `" . str_replace("`", "", $tabla) . "`")->fetchColumn());
  }

  private function indices($db, $tabla) {
    $stmt = $db->prepare("SELECT DISTINCT INDEX_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=:tabla ORDER BY INDEX_NAME");
    $stmt->execute(array(":tabla" => $tabla));
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
  }
}

header("Content-Type: application/json; charset=utf-8");
echo json_encode((new UatCatalogoImagenesMaestrosReadinessReadonly())->ejecutar(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
