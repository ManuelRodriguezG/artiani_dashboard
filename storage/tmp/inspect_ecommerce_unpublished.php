<?php
require __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/modelos/EcommerceCatalogoPublico.php";
class InspectEcommerceUnpublished extends EcommerceCatalogoPublico {
  public function dbPublica() { return $this->getConexion(); }
  public function auditarPublico($filtros) { return $this->auditarPublicabilidad($filtros); }
}
$m = new InspectEcommerceUnpublished();
$db = $m->dbPublica();
$out = array();
foreach (array("erp_catalogo_productos", "erp_catalogo_skus", "erp_ecommerce_publicaciones", "erp_catalogo_imagenes", "erp_listas_precios_detalle", "erp_inventario_existencias") as $tabla) {
  try {
    $out[$tabla] = $db->query("SHOW COLUMNS FROM `" . $tabla . "`")->fetchAll(PDO::FETCH_ASSOC);
  } catch (Exception $e) { $out[$tabla] = array("error" => $e->getMessage()); }
}
echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
