<?php
/**
 * IA: Codex GPT-5
 * Fecha: 2026-06-29
 * Proposito: auditar SKUs activos sin proveedor y detectar cuales tienen match exacto en listas de proveedor.
 * Impacto: Catalogo ERP/Proveedores; prepara saneamiento sin crear relaciones ni tocar costos.
 * Contrato: read-only; no inserta en erp_catalogo_sku_proveedores y no actualiza costo_referencia.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";

class UatCatalogoSkusSinProveedorReadonly extends CRUD {
  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-29
   * Proposito: agrupar pendientes de proveedor por categoria, marca y match exacto.
   * Impacto: Catalogo ERP; ayuda a decidir si se usa match historico o asignacion masiva manual.
   * Contrato: solo SELECT.
   */
  public function ejecutar() {
    $db = $this->getConexion();
    $sinProveedor = "NOT EXISTS (
      SELECT 1 FROM erp_catalogo_sku_proveedores sp
      WHERE sp.id_sku=s.id_sku AND sp.estatus='activo'
    )";
    $matchExacto = "EXISTS (
      SELECT 1
      FROM erp_proveedores_listas_productos lp
      INNER JOIN erp_proveedores_listas l ON l.id_lista_proveedor=lp.id_lista_proveedor AND l.id_proveedor IS NOT NULL
      WHERE UPPER(TRIM(lp.sku))=UPPER(TRIM(s.sku))
        AND TRIM(COALESCE(lp.sku,''))<>''
    )";

    $resumen = $db->query("SELECT
        COUNT(*) skus_sin_proveedor,
        SUM(CASE WHEN {$matchExacto} THEN 1 ELSE 0 END) con_match_exacto_lista,
        SUM(CASE WHEN {$matchExacto} THEN 0 ELSE 1 END) sin_match_exacto_lista
      FROM erp_catalogo_skus s
      WHERE s.estatus='activo' AND {$sinProveedor}")->fetch(PDO::FETCH_ASSOC);

    $porCategoria = $db->query("SELECT COALESCE(c.ruta, c.nombre, '(sin categoria principal)') categoria,
          pc.id_categoria_erp,
          COUNT(DISTINCT s.id_sku) skus_sin_proveedor,
          SUM(CASE WHEN {$matchExacto} THEN 1 ELSE 0 END) con_match_exacto_lista
        FROM erp_catalogo_skus s
        INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
        LEFT JOIN erp_catalogo_producto_categorias pc ON pc.id_producto_erp=p.id_producto_erp AND pc.es_principal=1
        LEFT JOIN erp_catalogo_categorias c ON c.id_categoria_erp=pc.id_categoria_erp
        WHERE s.estatus='activo' AND {$sinProveedor}
        GROUP BY pc.id_categoria_erp, COALESCE(c.ruta, c.nombre, '(sin categoria principal)')
        ORDER BY skus_sin_proveedor DESC, categoria ASC
        LIMIT 25")->fetchAll(PDO::FETCH_ASSOC);

    $porMarca = $db->query("SELECT COALESCE(m.nombre, '(sin marca)') marca,
          p.id_marca_erp,
          COUNT(DISTINCT s.id_sku) skus_sin_proveedor,
          SUM(CASE WHEN {$matchExacto} THEN 1 ELSE 0 END) con_match_exacto_lista
        FROM erp_catalogo_skus s
        INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
        LEFT JOIN erp_catalogo_marcas m ON m.id_marca_erp=p.id_marca_erp
        WHERE s.estatus='activo' AND {$sinProveedor}
        GROUP BY p.id_marca_erp, COALESCE(m.nombre, '(sin marca)')
        ORDER BY skus_sin_proveedor DESC, marca ASC
        LIMIT 25")->fetchAll(PDO::FETCH_ASSOC);

    $matches = $db->query("SELECT s.id_sku, s.sku, s.nombre sku_nombre,
          p.id_producto_erp, p.nombre producto,
          COUNT(DISTINCT l.id_proveedor) proveedores_match,
          GROUP_CONCAT(DISTINCT prov.proveedor ORDER BY prov.proveedor SEPARATOR ' | ') proveedores
        FROM erp_catalogo_skus s
        INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
        INNER JOIN erp_proveedores_listas_productos lp ON UPPER(TRIM(lp.sku))=UPPER(TRIM(s.sku)) AND TRIM(COALESCE(lp.sku,''))<>''
        INNER JOIN erp_proveedores_listas l ON l.id_lista_proveedor=lp.id_lista_proveedor AND l.id_proveedor IS NOT NULL
        LEFT JOIN erp_proveedores prov ON prov.id_proveedor=l.id_proveedor
        WHERE s.estatus='activo' AND {$sinProveedor}
        GROUP BY s.id_sku, s.sku, s.nombre, p.id_producto_erp, p.nombre
        ORDER BY proveedores_match ASC, s.sku ASC
        LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);

    return array(
      "error" => false,
      "tipo" => "success",
      "mensaje" => "SKUs activos sin proveedor auditados",
      "depurar" => array(
        "resumen" => array(
          "skus_sin_proveedor" => intval($resumen["skus_sin_proveedor"]),
          "con_match_exacto_lista" => intval($resumen["con_match_exacto_lista"]),
          "sin_match_exacto_lista" => intval($resumen["sin_match_exacto_lista"])
        ),
        "por_categoria" => $porCategoria,
        "por_marca" => $porMarca,
        "ejemplos_match_exacto" => $matches,
        "decision" => array(
          "match_exacto" => "Revisar en Configuracion > Proveedor y costos > coincidencias exactas antes de vincular.",
          "sin_match" => "Usar Productos con filtro SKU sin proveedor y proveedor masivo solo cuando el operador sabe que todos los seleccionados comparten proveedor/unidad/factor.",
          "costo" => "No usar costo como dato obligatorio de Catalogo; solo relacion proveedor, unidad y factor."
        ),
        "contrato" => array(
          "read_only" => true,
          "no_crea_relaciones" => true,
          "no_actualiza_costos" => true
        )
      )
    );
  }
}

$uat = new UatCatalogoSkusSinProveedorReadonly();
echo json_encode($uat->ejecutar(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
