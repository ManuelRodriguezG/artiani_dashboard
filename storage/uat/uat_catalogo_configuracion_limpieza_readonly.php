<?php
/**
 * IA: Codex GPT-5
 * Fecha: 2026-06-29
 * Proposito: auditar pendientes de limpieza en Configuracion de Catalogo ERP antes de tocar registros.
 * Impacto: Catalogo ERP; prioriza categorias, marcas, proveedores, rastros heredados y textos danados.
 * Contrato: read-only; no ejecuta DDL, no crea catalogos, no actualiza productos y no borra migracion.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";

class UatCatalogoConfiguracionLimpiezaReadonly extends CRUD {
    /**
     * IA: Codex GPT-5
     * Fecha: 2026-06-29
     * Proposito: ejecutar auditoria read-only de saneamiento operativo de Catalogo.
     * Impacto: Catalogo ERP; entrega conteos y ejemplos accionables.
     * Contrato: solo SELECT e information_schema.
     */
    public function ejecutar() {
        $db = $this->getConexion();
        $tablas = array(
            "erp_catalogo_productos",
            "erp_catalogo_skus",
            "erp_catalogo_producto_categorias",
            "erp_catalogo_categorias",
            "erp_catalogo_marcas",
            "erp_catalogo_sku_proveedores",
            "erp_catalogo_canales_vinculos",
            "erp_catalogo_migracion_ecom_incidencias",
            "erp_catalogo_marca_imagenes",
            "erp_catalogo_categoria_imagenes"
        );
        $estadoTablas = array();
        foreach ($tablas as $tabla) {
            $estadoTablas[$tabla] = $this->tablaExiste($db, $tabla);
        }

        $conteos = array(
            "productos_no_fusionados" => $this->contar($db, "SELECT COUNT(*) FROM erp_catalogo_productos WHERE estatus<>'fusionado'"),
            "skus_activos" => $this->contar($db, "SELECT COUNT(*) FROM erp_catalogo_skus WHERE estatus='activo'"),
            "productos_sin_categoria_principal" => $this->contar($db, "SELECT COUNT(*) FROM erp_catalogo_productos p WHERE p.estatus<>'fusionado' AND NOT EXISTS (SELECT 1 FROM erp_catalogo_producto_categorias pc WHERE pc.id_producto_erp=p.id_producto_erp AND pc.es_principal=1)"),
            "productos_con_mas_de_una_categoria_principal" => $this->contar($db, "SELECT COUNT(*) FROM (SELECT pc.id_producto_erp FROM erp_catalogo_producto_categorias pc WHERE pc.es_principal=1 GROUP BY pc.id_producto_erp HAVING COUNT(*)>1) x"),
            "productos_sin_marca" => $this->contar($db, "SELECT COUNT(*) FROM erp_catalogo_productos p WHERE p.estatus<>'fusionado' AND (p.id_marca_erp IS NULL OR p.id_marca_erp=0)"),
            "skus_activos_sin_proveedor_activo" => $this->contar($db, "SELECT COUNT(*) FROM erp_catalogo_skus s WHERE s.estatus='activo' AND NOT EXISTS (SELECT 1 FROM erp_catalogo_sku_proveedores sp WHERE sp.id_sku=s.id_sku AND sp.estatus='activo')"),
            "skus_con_varios_proveedores_sin_preferido" => $this->contar($db, "SELECT COUNT(*) FROM erp_catalogo_skus s WHERE s.estatus='activo' AND (SELECT COUNT(*) FROM erp_catalogo_sku_proveedores sp WHERE sp.id_sku=s.id_sku AND sp.estatus='activo')>1 AND NOT EXISTS (SELECT 1 FROM erp_catalogo_sku_proveedores sp WHERE sp.id_sku=s.id_sku AND sp.estatus='activo' AND sp.es_preferido=1)"),
            "categorias_heredadas" => $this->contar($db, "SELECT COUNT(*) FROM erp_catalogo_categorias WHERE tipo_categoria='legado_canal' OR origen='ecommerce'"),
            "vinculos_canal_ecommerce" => $estadoTablas["erp_catalogo_canales_vinculos"] ? $this->contar($db, "SELECT COUNT(*) FROM erp_catalogo_canales_vinculos WHERE canal='ecommerce'") : null,
            "incidencias_migracion_pendientes" => $estadoTablas["erp_catalogo_migracion_ecom_incidencias"] ? $this->contar($db, "SELECT COUNT(*) FROM erp_catalogo_migracion_ecom_incidencias WHERE estatus='pendiente'") : null,
            "categorias_texto_danado" => $this->contar($db, "SELECT COUNT(*) FROM erp_catalogo_categorias c WHERE " . $this->condicionTextoDanado("c.nombre") . " OR " . $this->condicionTextoDanado("c.ruta")),
            "marcas_texto_danado" => $this->contar($db, "SELECT COUNT(*) FROM erp_catalogo_marcas m WHERE " . $this->condicionTextoDanado("m.nombre")),
            "productos_texto_danado" => $this->contar($db, "SELECT COUNT(*) FROM erp_catalogo_productos p WHERE " . $this->condicionTextoDanado("p.nombre"))
        );

        $ejemplos = array(
            "sin_categoria_principal" => $this->consultar($db, "SELECT p.id_producto_erp, p.codigo_producto, p.nombre producto, p.estatus
                FROM erp_catalogo_productos p
                WHERE p.estatus<>'fusionado'
                  AND NOT EXISTS (SELECT 1 FROM erp_catalogo_producto_categorias pc WHERE pc.id_producto_erp=p.id_producto_erp AND pc.es_principal=1)
                ORDER BY p.id_producto_erp DESC LIMIT 20"),
            "sin_marca" => $this->consultar($db, "SELECT p.id_producto_erp, p.codigo_producto, p.nombre producto, p.estatus
                FROM erp_catalogo_productos p
                WHERE p.estatus<>'fusionado' AND (p.id_marca_erp IS NULL OR p.id_marca_erp=0)
                ORDER BY p.id_producto_erp DESC LIMIT 20"),
            "skus_sin_proveedor" => $this->consultar($db, "SELECT s.id_sku, s.sku, s.nombre sku_nombre, p.nombre producto
                FROM erp_catalogo_skus s
                INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
                WHERE s.estatus='activo'
                  AND NOT EXISTS (SELECT 1 FROM erp_catalogo_sku_proveedores sp WHERE sp.id_sku=s.id_sku AND sp.estatus='activo')
                ORDER BY s.id_sku DESC LIMIT 20"),
            "varios_proveedores_sin_preferido" => $this->consultar($db, "SELECT s.id_sku, s.sku, s.nombre sku_nombre,
                       COUNT(sp.id_sku_proveedor) proveedores_activos
                FROM erp_catalogo_skus s
                INNER JOIN erp_catalogo_sku_proveedores sp ON sp.id_sku=s.id_sku AND sp.estatus='activo'
                WHERE s.estatus='activo'
                GROUP BY s.id_sku, s.sku, s.nombre
                HAVING COUNT(sp.id_sku_proveedor)>1
                   AND SUM(CASE WHEN sp.es_preferido=1 THEN 1 ELSE 0 END)=0
                ORDER BY proveedores_activos DESC, s.id_sku DESC LIMIT 20"),
            "categorias_texto_danado" => $this->consultar($db, "SELECT c.id_categoria_erp, c.codigo, c.nombre, c.ruta, c.tipo_categoria, c.origen
                FROM erp_catalogo_categorias c
                WHERE " . $this->condicionTextoDanado("c.nombre") . "
                   OR " . $this->condicionTextoDanado("c.ruta") . "
                ORDER BY c.id_categoria_erp DESC LIMIT 20"),
            "categorias_heredadas" => $this->consultar($db, "SELECT c.id_categoria_erp, c.codigo, c.nombre, c.ruta, c.tipo_categoria, c.origen,
                       (SELECT COUNT(*) FROM erp_catalogo_producto_categorias pc WHERE pc.id_categoria_erp=c.id_categoria_erp) productos_relacionados
                FROM erp_catalogo_categorias c
                WHERE c.tipo_categoria='legado_canal' OR c.origen='ecommerce'
                ORDER BY productos_relacionados DESC, c.id_categoria_erp DESC LIMIT 20")
        );

        $incidencias = $estadoTablas["erp_catalogo_migracion_ecom_incidencias"]
            ? $this->consultar($db, "SELECT estatus, motivo, COUNT(*) total
                FROM erp_catalogo_migracion_ecom_incidencias
                GROUP BY estatus, motivo
                ORDER BY total DESC")
            : array();

        return array(
            "error" => false,
            "tipo" => "success",
            "mensaje" => "Auditoria read-only de limpieza de Configuracion de Catalogo ERP",
            "depurar" => array(
                "tablas" => $estadoTablas,
                "conteos" => $conteos,
                "ejemplos" => $ejemplos,
                "incidencias_migracion" => $incidencias,
                "prioridad_sugerida" => array(
                    "1" => "Resolver productos sin categoria principal, porque afecta Garantias, Ventas, reportes y navegacion.",
                    "2" => "Resolver SKUs sin proveedor activo o sin proveedor preferido cuando haya multiples.",
                    "3" => "Corregir textos danados en categorias antes de usarlas como reglas amplias.",
                    "4" => "Decidir si los vinculos de ecommerce se conservan como trazabilidad historica o se archivan visualmente.",
                    "5" => "Completar marcas despues de categorias/proveedores para no frenar operacion."
                ),
                "contrato" => array(
                    "read_only" => true,
                    "no_ejecuta_ddl" => true,
                    "no_actualiza_productos" => true,
                    "no_borra_migracion" => true
                )
            )
        );
    }

    private function tablaExiste($db, $tabla) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=:base AND table_name=:tabla");
        $stmt->execute(array(":base" => MYSQLBASE, ":tabla" => $tabla));
        return intval($stmt->fetchColumn()) > 0;
    }

    private function contar($db, $sql) {
        try {
            return intval($db->query($sql)->fetchColumn());
        } catch (Exception $e) {
            return null;
        }
    }

    private function consultar($db, $sql) {
        try {
            return $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return array(array("error_consulta" => $e->getMessage()));
        }
    }

    private function condicionTextoDanado($columna) {
        return "(HEX(COALESCE(" . $columna . ",'')) LIKE '%C383%'"
            . " OR HEX(COALESCE(" . $columna . ",'')) LIKE '%C382%'"
            . " OR HEX(COALESCE(" . $columna . ",'')) LIKE '%E2949C%'"
            . " OR HEX(COALESCE(" . $columna . ",'')) LIKE '%E294AC%'"
            . " OR HEX(COALESCE(" . $columna . ",'')) LIKE '%EFBFBD%')";
    }
}

$uat = new UatCatalogoConfiguracionLimpiezaReadonly();
echo json_encode($uat->ejecutar(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
