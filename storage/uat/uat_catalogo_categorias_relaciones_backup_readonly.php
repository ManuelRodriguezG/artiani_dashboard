<?php
/**
 * IA: Codex GPT-5
 * Fecha: 2026-08-14
 * Proposito: respaldar relaciones producto-categoria antes de limpiar la clasificacion del catalogo.
 * Impacto: Catalogo ERP; permite recuperar evidencia de categorias previas sin modificar BD.
 * Contrato: solo lectura sobre BD; escribe respaldo JSON fuera del proyecto.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";

class UatCatalogoCategoriasRelacionesBackupReadonly extends CRUD {
  public function ejecutar() {
    $db = $this->getConexion();
    $destino = "C:\\xampp\\panel_db_backups";
    if (!is_dir($destino)) {
      mkdir($destino, 0777, true);
    }

    $archivo = $destino . "\\catalogo_producto_categorias_" . date("Ymd_His") . "_pre_limpieza.json";
    $relaciones = $db->query("SELECT pc.id_producto_categoria, pc.id_producto_erp, p.codigo_producto, p.nombre AS producto,
        pc.id_categoria_erp, c.codigo AS categoria_codigo, c.nombre AS categoria_nombre, c.ruta AS categoria_ruta,
        c.tipo_categoria, c.origen, pc.es_principal
      FROM erp_catalogo_producto_categorias pc
      LEFT JOIN erp_catalogo_productos p ON p.id_producto_erp=pc.id_producto_erp
      LEFT JOIN erp_catalogo_categorias c ON c.id_categoria_erp=pc.id_categoria_erp
      ORDER BY pc.id_producto_erp, pc.es_principal DESC, c.ruta, pc.id_producto_categoria")->fetchAll(PDO::FETCH_ASSOC);

    $payload = array(
      "generado_en" => date("c"),
      "proyecto" => "C:\\xampp\\htdocs\\panel_de_control",
      "tabla" => "erp_catalogo_producto_categorias",
      "total_relaciones" => count($relaciones),
      "relaciones" => $relaciones
    );

    file_put_contents($archivo, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    return array(
      "error" => false,
      "archivo" => $archivo,
      "total_relaciones" => count($relaciones),
      "nota" => "No se modifico BD."
    );
  }
}

header("Content-Type: application/json; charset=utf-8");
echo json_encode((new UatCatalogoCategoriasRelacionesBackupReadonly())->ejecutar(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
