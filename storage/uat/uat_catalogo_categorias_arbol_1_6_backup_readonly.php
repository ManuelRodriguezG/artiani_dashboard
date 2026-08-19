<?php
/**
 * IA: Codex GPT-5
 * Fecha: 2026-08-14
 * Proposito: respaldar categorias ERP antes de preparar el arbol operativo 1-6.
 * Impacto: Catalogo ERP; conserva estado previo de categorias sin modificar BD.
 * Contrato: solo lectura sobre BD; escribe respaldo JSON fuera del proyecto.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";

class UatCatalogoCategoriasArbol16BackupReadonly extends CRUD {
  public function ejecutar() {
    $db = $this->getConexion();
    if (!$db) {
      return array("error" => true, "mensaje" => "Sin conexion a BD.");
    }

    $destino = "C:\\xampp\\panel_db_backups";
    if (!is_dir($destino)) {
      mkdir($destino, 0777, true);
    }

    $archivo = $destino . "\\catalogo_categorias_arbol_1_6_" . date("Ymd_His") . "_pre.json";
    $categorias = $db->query("SELECT *
      FROM erp_catalogo_categorias
      ORDER BY COALESCE(NULLIF(ruta, ''), nombre), id_categoria_erp")->fetchAll(PDO::FETCH_ASSOC);

    $payload = array(
      "generado_en" => date("c"),
      "proyecto" => "C:\\xampp\\htdocs\\panel_de_control",
      "tabla" => "erp_catalogo_categorias",
      "total_categorias" => count($categorias),
      "categorias" => $categorias
    );

    file_put_contents($archivo, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    return array(
      "error" => false,
      "archivo" => $archivo,
      "total_categorias" => count($categorias),
      "nota" => "No se modifico BD."
    );
  }
}

header("Content-Type: application/json; charset=utf-8");
echo json_encode((new UatCatalogoCategoriasArbol16BackupReadonly())->ejecutar(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
