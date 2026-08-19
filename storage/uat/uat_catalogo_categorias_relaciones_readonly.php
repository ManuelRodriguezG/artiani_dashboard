<?php
/**
 * IA: Codex GPT-5
 * Fecha: 2026-08-14
 * Proposito: auditar relaciones producto-categoria antes de reorganizar el arbol maestro.
 * Impacto: Catalogo ERP; mide riesgo de limpiar o reemplazar categorias sin modificar datos.
 * Contrato: solo lectura; no actualiza categorias, productos ni relaciones.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";

class UatCatalogoCategoriasRelacionesReadonly extends CRUD {
  public function ejecutar() {
    $db = $this->getConexion();

    $resumen = array(
      "productos_total" => intval($db->query("SELECT COUNT(*) FROM erp_catalogo_productos")->fetchColumn()),
      "productos_activos" => intval($db->query("SELECT COUNT(*) FROM erp_catalogo_productos WHERE estatus='activo'")->fetchColumn()),
      "relaciones_total" => intval($db->query("SELECT COUNT(*) FROM erp_catalogo_producto_categorias")->fetchColumn()),
      "relaciones_principales" => intval($db->query("SELECT COUNT(*) FROM erp_catalogo_producto_categorias WHERE es_principal=1")->fetchColumn()),
      "productos_con_categoria" => intval($db->query("SELECT COUNT(DISTINCT id_producto_erp) FROM erp_catalogo_producto_categorias")->fetchColumn()),
      "productos_con_principal" => intval($db->query("SELECT COUNT(DISTINCT id_producto_erp) FROM erp_catalogo_producto_categorias WHERE es_principal=1")->fetchColumn())
    );
    $resumen["productos_sin_categoria"] = max(0, $resumen["productos_total"] - $resumen["productos_con_categoria"]);
    $resumen["productos_sin_principal"] = max(0, $resumen["productos_total"] - $resumen["productos_con_principal"]);

    $porTipo = $db->query("SELECT c.tipo_categoria, c.origen, COUNT(*) AS relaciones,
        COUNT(DISTINCT pc.id_producto_erp) AS productos
      FROM erp_catalogo_producto_categorias pc
      INNER JOIN erp_catalogo_categorias c ON c.id_categoria_erp=pc.id_categoria_erp
      GROUP BY c.tipo_categoria, c.origen
      ORDER BY relaciones DESC")->fetchAll(PDO::FETCH_ASSOC);

    $raicesConRelaciones = $db->query("SELECT c.id_categoria_erp, c.codigo, c.nombre, c.ruta, c.tipo_categoria, c.origen,
        COUNT(pc.id_producto_categoria) AS relaciones,
        COUNT(DISTINCT pc.id_producto_erp) AS productos,
        SUM(CASE WHEN pc.es_principal=1 THEN 1 ELSE 0 END) AS principales
      FROM erp_catalogo_producto_categorias pc
      INNER JOIN erp_catalogo_categorias c ON c.id_categoria_erp=pc.id_categoria_erp
      WHERE c.id_categoria_padre IS NULL OR c.id_categoria_padre=0
      GROUP BY c.id_categoria_erp, c.codigo, c.nombre, c.ruta, c.tipo_categoria, c.origen
      ORDER BY productos DESC, c.nombre ASC
      LIMIT 80")->fetchAll(PDO::FETCH_ASSOC);

    $categoriasMasUsadas = $db->query("SELECT c.id_categoria_erp, c.id_categoria_padre, c.codigo, c.nombre, c.ruta, c.tipo_categoria, c.origen,
        COUNT(pc.id_producto_categoria) AS relaciones,
        COUNT(DISTINCT pc.id_producto_erp) AS productos,
        SUM(CASE WHEN pc.es_principal=1 THEN 1 ELSE 0 END) AS principales
      FROM erp_catalogo_producto_categorias pc
      INNER JOIN erp_catalogo_categorias c ON c.id_categoria_erp=pc.id_categoria_erp
      GROUP BY c.id_categoria_erp, c.id_categoria_padre, c.codigo, c.nombre, c.ruta, c.tipo_categoria, c.origen
      ORDER BY productos DESC, c.ruta ASC
      LIMIT 120")->fetchAll(PDO::FETCH_ASSOC);

    $productosConMultiplesPrincipales = $db->query("SELECT p.id_producto_erp, p.codigo_producto, p.nombre,
        COUNT(*) AS principales
      FROM erp_catalogo_producto_categorias pc
      INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=pc.id_producto_erp
      WHERE pc.es_principal=1
      GROUP BY p.id_producto_erp, p.codigo_producto, p.nombre
      HAVING COUNT(*) > 1
      ORDER BY principales DESC, p.nombre ASC
      LIMIT 60")->fetchAll(PDO::FETCH_ASSOC);

    $productosSinPrincipalMuestra = $db->query("SELECT p.id_producto_erp, p.codigo_producto, p.nombre, p.estatus,
        COUNT(pc.id_producto_categoria) AS categorias
      FROM erp_catalogo_productos p
      LEFT JOIN erp_catalogo_producto_categorias pc ON pc.id_producto_erp=p.id_producto_erp
      GROUP BY p.id_producto_erp, p.codigo_producto, p.nombre, p.estatus
      HAVING SUM(CASE WHEN pc.es_principal=1 THEN 1 ELSE 0 END)=0
      ORDER BY p.estatus ASC, p.nombre ASC
      LIMIT 80")->fetchAll(PDO::FETCH_ASSOC);

    return array(
      "resumen" => $resumen,
      "relaciones_por_tipo_categoria" => $this->normalizarLista($porTipo),
      "raices_con_relaciones_muestra" => $this->normalizarLista($raicesConRelaciones),
      "categorias_mas_usadas_muestra" => $this->normalizarLista($categoriasMasUsadas),
      "productos_con_multiples_principales_muestra" => $this->normalizarLista($productosConMultiplesPrincipales),
      "productos_sin_principal_muestra" => $this->normalizarLista($productosSinPrincipalMuestra),
      "recomendacion" => "Auditoria de estado actual. Si relaciones_total=0, Catalogo esta listo para reclasificacion desde el arbol operativo vigente."
    );
  }

  private function normalizarLista($items) {
    $normalizados = array();
    foreach ($items as $item) {
      foreach ($item as $clave => $valor) {
        if (is_numeric($valor) && strpos((string)$valor, ".") === false) {
          $item[$clave] = intval($valor);
        }
      }
      $normalizados[] = $item;
    }
    return $normalizados;
  }
}

header("Content-Type: application/json; charset=utf-8");
echo json_encode((new UatCatalogoCategoriasRelacionesReadonly())->ejecutar(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
