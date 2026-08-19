<?php
/**
 * IA: Codex GPT-5
 * Fecha: 2026-08-13
 * Proposito: exportar el arbol actual de categorias ERP para diseno operativo.
 * Impacto: Catalogo ERP; facilita reorganizar categorias sin modificar datos.
 * Contrato: solo lectura; no actualiza categorias, productos ni relaciones.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";

class UatCatalogoCategoriasArbolReadonly extends CRUD {
  public function ejecutar() {
    $db = $this->getConexion();
    $categorias = $db->query("SELECT c.id_categoria_erp, c.id_categoria_padre, c.codigo, c.nombre, c.descripcion,
        c.ruta, c.nivel, c.tipo_categoria, c.origen, c.permite_productos, c.estatus,
        (SELECT COUNT(*) FROM erp_catalogo_producto_categorias pc WHERE pc.id_categoria_erp=c.id_categoria_erp) AS total_productos,
        (SELECT COUNT(*) FROM erp_catalogo_categorias h WHERE h.id_categoria_padre=c.id_categoria_erp) AS total_hijas
      FROM erp_catalogo_categorias c
      ORDER BY COALESCE(NULLIF(c.ruta, ''), c.nombre), c.id_categoria_erp")->fetchAll(PDO::FETCH_ASSOC);

    $raices = array();
    $porPadre = array();
    foreach ($categorias as $categoria) {
      $padre = intval($categoria["id_categoria_padre"]);
      if ($padre === 0) {
        $raices[] = $this->normalizar($categoria);
      }
      if (!isset($porPadre[$padre])) {
        $porPadre[$padre] = array();
      }
      $porPadre[$padre][] = $this->normalizar($categoria);
    }

    return array(
      "resumen" => array(
        "total_categorias" => count($categorias),
        "total_raices" => count($raices),
        "nota" => "Solo lectura; usar para documento de rediseno del arbol."
      ),
      "raices" => $raices,
      "categorias" => array_map(array($this, "normalizar"), $categorias),
      "arbol" => $this->construirArbol(0, $porPadre, 0)
    );
  }

  private function construirArbol($padre, $porPadre, $profundidad) {
    if ($profundidad > 12 || empty($porPadre[$padre])) {
      return array();
    }
    $items = array();
    foreach ($porPadre[$padre] as $categoria) {
      $categoria["hijas"] = $this->construirArbol(intval($categoria["id_categoria_erp"]), $porPadre, $profundidad + 1);
      $items[] = $categoria;
    }
    return $items;
  }

  private function normalizar($categoria) {
    return array(
      "id_categoria_erp" => intval($categoria["id_categoria_erp"]),
      "id_categoria_padre" => intval($categoria["id_categoria_padre"]),
      "codigo" => (string)$categoria["codigo"],
      "nombre" => (string)$categoria["nombre"],
      "ruta" => (string)$categoria["ruta"],
      "nivel" => intval($categoria["nivel"]),
      "tipo_categoria" => (string)$categoria["tipo_categoria"],
      "origen" => (string)$categoria["origen"],
      "permite_productos" => intval($categoria["permite_productos"]),
      "estatus" => (string)$categoria["estatus"],
      "total_productos" => intval($categoria["total_productos"]),
      "total_hijas" => intval($categoria["total_hijas"])
    );
  }
}

header("Content-Type: application/json; charset=utf-8");
echo json_encode((new UatCatalogoCategoriasArbolReadonly())->ejecutar(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
