<?php
/**
 * IA: Codex GPT-5
 * Fecha: 2026-06-29
 * Proposito: detectar candidatos de asignacion masiva de garantias por categoria, marca y proveedor.
 * Impacto: Garantias/Catalogo; acelera cobertura sin crear reglas ni modificar productos.
 * Contrato: read-only; no crea politicas, no crea reglas y no actualiza Catalogo.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/modelos/GarantiasErp.php";

class UatGarantiasCoberturaCandidatosReadonly extends GarantiasErp {
    /**
     * IA: Codex GPT-5
     * Fecha: 2026-06-29
     * Proposito: agrupar SKUs activos sin garantia por dimensiones reutilizables.
     * Impacto: Garantias ERP; ayuda a priorizar reglas por categoria, marca o proveedor.
     * Contrato: solo ejecuta SELECT.
     */
    public function ejecutar() {
        $disponibilidad = $this->disponibilidadEsquema();
        if (!$disponibilidad["disponible"]) {
            return array(
                "error" => false,
                "tipo" => "warning",
                "mensaje" => "Esquema de Garantias ERP pendiente",
                "depurar" => array(
                    "faltantes" => $disponibilidad["faltantes"]
                )
            );
        }

        $db = $this->getConexion();
        $cobertura = "EXISTS (
            SELECT 1
            FROM erp_garantias_politicas_reglas r
            INNER JOIN erp_garantias_politicas gp ON gp.id_garantia_politica = r.id_garantia_politica AND gp.estatus='activa'
            WHERE r.estatus='activa'
              AND (
                (r.ambito='sku' AND r.id_referencia=s.id_sku)
                OR (r.ambito='producto' AND r.id_referencia=s.id_producto_erp)
                OR (r.ambito='categoria' AND r.id_referencia=pc.id_categoria_erp)
                OR (r.ambito='marca' AND r.id_referencia=p.id_marca_erp)
                OR (r.ambito='proveedor' AND EXISTS (
                    SELECT 1 FROM erp_catalogo_sku_proveedores sp2
                    WHERE sp2.id_sku=s.id_sku AND sp2.id_proveedor=r.id_referencia AND sp2.estatus='activo'
                ))
              )
        )";

        $categorias = $db->query("SELECT pc.id_categoria_erp, COALESCE(c.ruta, c.nombre, '(sin categoria)') categoria,
                       COUNT(DISTINCT s.id_sku) skus_sin_regla
                FROM erp_catalogo_skus s
                INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
                LEFT JOIN erp_catalogo_producto_categorias pc ON pc.id_producto_erp=p.id_producto_erp AND pc.es_principal=1
                LEFT JOIN erp_catalogo_categorias c ON c.id_categoria_erp=pc.id_categoria_erp
                WHERE s.estatus='activo' AND NOT {$cobertura}
                GROUP BY pc.id_categoria_erp, COALESCE(c.ruta, c.nombre, '(sin categoria)')
                ORDER BY skus_sin_regla DESC, categoria ASC
                LIMIT 25")->fetchAll(PDO::FETCH_ASSOC);

        $marcas = $db->query("SELECT p.id_marca_erp, COALESCE(m.nombre, '(sin marca)') marca,
                       COUNT(DISTINCT s.id_sku) skus_sin_regla
                FROM erp_catalogo_skus s
                INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
                LEFT JOIN erp_catalogo_producto_categorias pc ON pc.id_producto_erp=p.id_producto_erp AND pc.es_principal=1
                LEFT JOIN erp_catalogo_marcas m ON m.id_marca_erp=p.id_marca_erp
                WHERE s.estatus='activo' AND NOT {$cobertura}
                GROUP BY p.id_marca_erp, COALESCE(m.nombre, '(sin marca)')
                ORDER BY skus_sin_regla DESC, marca ASC
                LIMIT 25")->fetchAll(PDO::FETCH_ASSOC);

        $proveedores = $db->query("SELECT sp.id_proveedor, COALESCE(pv.proveedor, '(sin proveedor)') proveedor,
                       COUNT(DISTINCT s.id_sku) skus_sin_regla
                FROM erp_catalogo_skus s
                INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
                LEFT JOIN erp_catalogo_producto_categorias pc ON pc.id_producto_erp=p.id_producto_erp AND pc.es_principal=1
                LEFT JOIN erp_catalogo_sku_proveedores sp ON sp.id_sku=s.id_sku AND sp.estatus='activo'
                LEFT JOIN erp_proveedores pv ON pv.id_proveedor=sp.id_proveedor
                WHERE s.estatus='activo' AND NOT {$cobertura}
                GROUP BY sp.id_proveedor, COALESCE(pv.proveedor, '(sin proveedor)')
                ORDER BY skus_sin_regla DESC, proveedor ASC
                LIMIT 25")->fetchAll(PDO::FETCH_ASSOC);

        return array(
            "error" => false,
            "tipo" => "success",
            "mensaje" => "Candidatos de cobertura de garantias consultados",
            "depurar" => array(
                "categorias" => $categorias,
                "marcas" => $marcas,
                "proveedores" => $proveedores,
                "criterio_uso" => array(
                    "categoria" => "Usar cuando todos o casi todos los SKUs de una rama comparten la misma politica.",
                    "marca" => "Usar cuando la marca tiene garantia uniforme independientemente de categoria.",
                    "proveedor" => "Usar cuando el proveedor define una politica consistente para sus productos.",
                    "sku" => "Reservar para excepciones puntuales."
                ),
                "contrato" => array(
                    "read_only" => true,
                    "no_crea_reglas" => true,
                    "no_modifica_catalogo" => true
                )
            )
        );
    }
}

$uat = new UatGarantiasCoberturaCandidatosReadonly();
echo json_encode($uat->ejecutar(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
