<?php
/**
 * IA: Codex GPT-5
 * Fecha: 2026-08-13
 * Proposito: generar un resumen legible de categorias actuales para disenar el arbol maestro.
 * Impacto: Catalogo ERP; apoya decisiones de taxonomia sin modificar datos.
 * Contrato: solo lectura; emite Markdown por stdout.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";

class UatCatalogoCategoriasResumenMarkdownReadonly extends CRUD {
  public function ejecutar() {
    $db = $this->getConexion();
    $categorias = $db->query("SELECT c.id_categoria_erp, c.id_categoria_padre, c.codigo, c.nombre, c.ruta,
        c.tipo_categoria, c.origen, c.permite_productos, c.estatus,
        (SELECT COUNT(*) FROM erp_catalogo_producto_categorias pc WHERE pc.id_categoria_erp=c.id_categoria_erp) AS total_productos,
        (SELECT COUNT(*) FROM erp_catalogo_categorias h WHERE h.id_categoria_padre=c.id_categoria_erp) AS total_hijas
      FROM erp_catalogo_categorias c
      ORDER BY c.tipo_categoria DESC, COALESCE(NULLIF(c.ruta, ''), c.nombre), c.id_categoria_erp")->fetchAll(PDO::FETCH_ASSOC);

    $maestras = array();
    $legadas = array();
    foreach ($categorias as $categoria) {
      if ((string)$categoria["tipo_categoria"] === "maestra") {
        $maestras[] = $categoria;
      } else {
        $legadas[] = $categoria;
      }
    }

    echo "# Resumen categorias Catalogo ERP\n\n";
    echo "- Total categorias: " . count($categorias) . "\n";
    echo "- Maestras: " . count($maestras) . "\n";
    echo "- Legadas/ecommerce: " . count($legadas) . "\n\n";
    $this->imprimirGrupo("Maestras actuales", $maestras);
    $this->imprimirGrupo("Legadas ecommerce actuales", $legadas);
  }

  private function imprimirGrupo($titulo, $categorias) {
    echo "## " . $titulo . "\n\n";
    foreach ($categorias as $categoria) {
      $indent = str_repeat("  ", max(0, intval($categoria["nivel"])));
      $permite = intval($categoria["permite_productos"]) === 1 ? "productos" : "estructural";
      echo $indent . "- [" . intval($categoria["id_categoria_erp"]) . "] " . trim((string)$categoria["ruta"]);
      echo " | " . $permite . " | productos=" . intval($categoria["total_productos"]);
      echo " | hijas=" . intval($categoria["total_hijas"]);
      echo " | codigo=" . (string)$categoria["codigo"] . "\n";
    }
    echo "\n";
  }
}

header("Content-Type: text/plain; charset=utf-8");
(new UatCatalogoCategoriasResumenMarkdownReadonly())->ejecutar();
