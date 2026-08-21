<?php

class ComprasSugeridosCompraErp extends CRUD {

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-08-20
     * Proposito: consultar y operar sugeridos de compra por proveedor sin afectar inventario.
     * Impacto: Compras/Solicitudes; prepara revisiones editables que pueden convertirse en solicitud normal.
     * Contrato: no crea kardex ni modifica existencias; las escrituras dependen de tablas autorizadas por ComprasEsquema.
     */
    public function catalogos() {
        try {
            require_once __DIR__ . "/SolicitudesCompraErp.php";
            $solicitudes = new SolicitudesCompraErp();
            return $solicitudes->catalogos();
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-08-20
     * Proposito: listar revisiones de Sugerido de compra para seguimiento operativo.
     * Impacto: Compras/Sugerido; lectura de revisiones guardadas, sin inventario ni kardex.
     */
    public function listar($filtros = array()) {
        try {
            $db = $this->getConexion();
            if (!$this->schemaDisponible($db)) {
                return $this->respuesta(false, "info", "Esquema de sugerido pendiente", array("schema_pendiente" => 1, "items" => array()));
            }
            $where = array("1=1");
            $params = array();
            $q = trim((string) $this->valor($filtros, "q", ""));
            $estatus = strtolower(trim((string) $this->valor($filtros, "estatus", "")));
            $idProveedor = intval($this->valor($filtros, "id_proveedor", 0));
            if ($q !== "") {
                $where[] = "(s.folio LIKE :q OR p.proveedor LIKE :q OR s.observaciones LIKE :q)";
                $params[":q"] = "%" . $q . "%";
            }
            if ($estatus !== "") {
                $where[] = "s.estatus=:estatus";
                $params[":estatus"] = $estatus;
            }
            if ($idProveedor > 0) {
                $where[] = "s.id_proveedor=:proveedor";
                $params[":proveedor"] = $idProveedor;
            }
            $sql = "SELECT s.id_sugerido_compra, s.folio, s.id_proveedor, p.proveedor, s.estatus,
                    s.observaciones, s.id_solicitud_generada, sol.folio AS folio_solicitud,
                    s.fecha_registro, s.fecha_actualizacion,
                    COUNT(d.id_detalle) AS total_partidas,
                    SUM(CASE WHEN COALESCE(d.cantidad_solicitar,0)>0 THEN 1 ELSE 0 END) AS partidas_solicitar,
                    SUM(COALESCE(d.cantidad_solicitar,0)) AS total_unidades,
                    SUM(COALESCE(d.cantidad_solicitar,0) * COALESCE(d.costo_estimado,0)) AS total_estimado
                FROM erp_compras_sugeridos_compra s
                INNER JOIN erp_proveedores p ON p.id_proveedor=s.id_proveedor
                LEFT JOIN erp_compras_sugeridos_compra_detalle d ON d.id_sugerido_compra=s.id_sugerido_compra
                LEFT JOIN erp_compras_solicitudes sol ON sol.id_solicitud=s.id_solicitud_generada
                WHERE " . implode(" AND ", $where) . "
                GROUP BY s.id_sugerido_compra, s.folio, s.id_proveedor, p.proveedor, s.estatus,
                    s.observaciones, s.id_solicitud_generada, sol.folio, s.fecha_registro, s.fecha_actualizacion
                ORDER BY s.id_sugerido_compra DESC
                LIMIT 300";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return $this->respuesta(false, "success", "Sugeridos consultados", array(
                "schema_pendiente" => 0,
                "items" => $stmt->fetchAll(PDO::FETCH_ASSOC)
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-08-20
     * Proposito: duplicar una revision como nueva consultando nuevamente reglas actuales de catalogo/proveedor.
     * Impacto: Compras/Sugerido; crea nuevo borrador y recalcula sugeridos, sin inventario ni kardex.
     */
    public function duplicarComoNuevo($idSugerido, $idUsuario) {
        $db = $this->getConexion();
        try {
            if (!$this->schemaDisponible($db)) {
                return $this->respuesta(true, "warning", "El esquema de Sugerido de compra aun no esta preparado", array("schema_pendiente" => 1));
            }
            $consulta = $this->consultar($idSugerido);
            if ($consulta["error"]) {
                return $consulta;
            }
            $sugerido = $consulta["depurar"]["sugerido"];
            $detalleOriginal = $consulta["depurar"]["detalle"];
            if (!$sugerido) {
                return $this->respuesta(true, "warning", "Sugerido no encontrado");
            }
            $actuales = $this->productosProveedor(array(
                "id_proveedor" => intval($sugerido["id_proveedor"]),
                "limite" => 800
            ));
            if ($actuales["error"]) {
                return $actuales;
            }
            $porRelacion = array();
            foreach ($actuales["depurar"]["items"] as $itemActual) {
                $porRelacion[intval($itemActual["id_sku_proveedor"])] = $itemActual;
            }
            $items = array();
            $omitidos = 0;
            foreach ($detalleOriginal as $itemAnterior) {
                $relacion = intval($itemAnterior["id_sku_proveedor"]);
                if (!isset($porRelacion[$relacion])) {
                    $omitidos++;
                    continue;
                }
                $nuevo = $porRelacion[$relacion];
                $nuevo["existencia_revisada"] = floatval($itemAnterior["existencia_revisada"]);
                $nuevo["cantidad_sugerida"] = $this->calcularCantidadSugerida($nuevo, $nuevo["existencia_revisada"]);
                $nuevo["cantidad_solicitar"] = $nuevo["cantidad_sugerida"];
                $nuevo["observaciones"] = trim((string) $itemAnterior["observaciones"]);
                $items[] = $nuevo;
            }
            if (empty($items)) {
                return $this->respuesta(true, "warning", "No hay partidas activas para duplicar");
            }
            $respuesta = $this->guardar(array(
                "id_sugerido_compra" => 0,
                "id_proveedor" => intval($sugerido["id_proveedor"]),
                "estatus" => "borrador",
                "observaciones" => "Duplicado desde " . $sugerido["folio"],
                "items" => $items
            ), $idUsuario);
            if (!$respuesta["error"]) {
                $respuesta["depurar"]["omitidos_por_relacion_inactiva"] = $omitidos;
            }
            return $respuesta;
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }
    /**
     * IA: Codex GPT-5
     * Fecha: 2026-08-20
     * Proposito: listar SKUs comprables de un proveedor con reglas de minimo/maximo para calcular sugeridos.
     * Impacto: Compras/Sugerido; lectura ERP nueva, sin fallback legacy ni inventario.
     * Actualizacion IA: Codex GPT-5 | Fecha: 2026-08-21
     * Regla: Sugerido parte de codigos exactos de la lista del proveedor; no expande variantes internas ERP.
     */
    public function productosProveedor($filtros = array()) {
        try {
            $db = $this->getConexion();
            $idProveedor = intval($this->valor($filtros, "id_proveedor", 0));
            $q = trim((string) $this->valor($filtros, "q", ""));
            $limite = max(1, min(800, intval($this->valor($filtros, "limite", 300))));
            if ($idProveedor <= 0) {
                return $this->respuesta(true, "warning", "Selecciona un proveedor");
            }

            $where = array(
                "sp.id_proveedor=:proveedor",
                "sp.estatus='activo'",
                "s.estatus='activo'",
                "p.estatus='activo'",
                "EXISTS (
                    SELECT 1
                    FROM erp_proveedores_listas_detalle_erp ld_base
                    INNER JOIN erp_proveedores_listas_erp l_base
                        ON l_base.id_lista_proveedor_erp=ld_base.id_lista_proveedor_erp
                    WHERE l_base.id_proveedor=sp.id_proveedor
                      AND ld_base.id_sku_proveedor=sp.id_sku_proveedor
                      AND ld_base.id_sku=sp.id_sku
                      AND (
                        LOWER(TRIM(ld_base.sku_proveedor))=LOWER(TRIM(sp.sku_proveedor))
                        OR LOWER(TRIM(ld_base.codigo_interno))=LOWER(TRIM(sp.sku_proveedor))
                        OR LOWER(TRIM(ld_base.codigo_barras))=LOWER(TRIM(sp.sku_proveedor))
                      )
                )"
            );
            $params = array(":proveedor" => $idProveedor);
            if ($q !== "") {
                $where[] = "(sp.sku_proveedor LIKE :q OR EXISTS (
                    SELECT 1 FROM erp_proveedores_listas_detalle_erp ldq
                    INNER JOIN erp_proveedores_listas_erp lq ON lq.id_lista_proveedor_erp=ldq.id_lista_proveedor_erp
                    WHERE lq.id_proveedor=sp.id_proveedor
                      AND ldq.id_sku_proveedor=sp.id_sku_proveedor
                      AND ldq.id_sku=sp.id_sku
                      AND (
                        LOWER(TRIM(ldq.sku_proveedor))=LOWER(TRIM(sp.sku_proveedor))
                        OR LOWER(TRIM(ldq.codigo_interno))=LOWER(TRIM(sp.sku_proveedor))
                        OR LOWER(TRIM(ldq.codigo_barras))=LOWER(TRIM(sp.sku_proveedor))
                      )
                      AND (ldq.descripcion_proveedor LIKE :q OR ldq.sku_proveedor LIKE :q OR ldq.codigo_barras LIKE :q OR ldq.codigo_interno LIKE :q OR ldq.marca_proveedor LIKE :q)
                ))";
                $params[":q"] = "%" . $q . "%";
            }

            $sql = "SELECT sp.id_sku_proveedor, sp.id_proveedor, sp.id_sku AS id_sku_erp,
                    sp.sku_proveedor, sp.factor_conversion, sp.cantidad_minima, sp.costo_ultimo,
                    sp.es_preferido, uc.abreviatura AS unidad_compra,
                    s.sku AS sku_erp, s.nombre AS nombre_erp, s.id_producto_erp,
                    p.nombre AS producto_erp,
                    COALESCE(r.stock_minimo,0) AS stock_minimo,
                    r.stock_maximo,
                    COALESCE(r.punto_reorden,0) AS punto_reorden,
                    COALESCE(cv.costo, sp.costo_ultimo, 0) AS costo_estimado,
                    cv.moneda AS moneda_costo,
                    cv.origen AS origen_costo,
                    (
                        SELECT ld.descripcion_proveedor
                        FROM erp_proveedores_listas_detalle_erp ld
                        INNER JOIN erp_proveedores_listas_erp l ON l.id_lista_proveedor_erp=ld.id_lista_proveedor_erp
                        WHERE l.id_proveedor=sp.id_proveedor
                          AND ld.id_sku_proveedor=sp.id_sku_proveedor
                          AND ld.id_sku=sp.id_sku
                        ORDER BY CASE WHEN ld.estado_match='relacion_aplicada' THEN 0 ELSE 1 END, ld.id_lista_detalle_erp DESC
                        LIMIT 1
                    ) AS nombre_proveedor,
                    (
                        SELECT ld.costo
                        FROM erp_proveedores_listas_detalle_erp ld
                        INNER JOIN erp_proveedores_listas_erp l ON l.id_lista_proveedor_erp=ld.id_lista_proveedor_erp
                        WHERE l.id_proveedor=sp.id_proveedor
                          AND ld.id_sku_proveedor=sp.id_sku_proveedor
                          AND ld.id_sku=sp.id_sku
                          AND COALESCE(ld.costo,0)>0
                        ORDER BY CASE WHEN ld.estado_match='relacion_aplicada' THEN 0 ELSE 1 END, ld.id_lista_detalle_erp DESC
                        LIMIT 1
                    ) AS costo_lista_proveedor
                FROM erp_catalogo_sku_proveedores sp
                INNER JOIN erp_catalogo_skus s ON s.id_sku=sp.id_sku
                INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
                LEFT JOIN erp_catalogo_unidades uc ON uc.id_unidad=sp.id_unidad_compra
                LEFT JOIN erp_catalogo_sku_reglas_inventario r ON r.id_sku=s.id_sku
                LEFT JOIN erp_proveedores_sku_costos cv ON cv.id_costo_proveedor_sku=(
                    SELECT MAX(c2.id_costo_proveedor_sku)
                    FROM erp_proveedores_sku_costos c2
                    WHERE c2.id_proveedor=sp.id_proveedor
                      AND c2.id_sku=sp.id_sku
                      AND c2.id_sku_proveedor=sp.id_sku_proveedor
                      AND c2.estatus='vigente'
                )
                WHERE " . implode(" AND ", $where) . "
                ORDER BY sp.es_preferido DESC, nombre_proveedor ASC, s.nombre ASC, sp.sku_proveedor ASC
                LIMIT :limite";
            $stmt = $db->prepare($sql);
            foreach ($params as $clave => $valor) {
                $stmt->bindValue($clave, $valor);
            }
            $stmt->bindValue(":limite", $limite, PDO::PARAM_INT);
            $stmt->execute();

            $items = array();
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
                $fila["nombre_proveedor"] = trim((string) $fila["nombre_proveedor"]);
                if ($fila["nombre_proveedor"] === "") {
                    $fila["nombre_proveedor"] = $fila["nombre_erp"];
                }
                $fila["costo_estimado"] = floatval($fila["costo_estimado"] ?: $fila["costo_lista_proveedor"] ?: $fila["costo_ultimo"] ?: 0);
                $fila["factor_conversion"] = floatval($fila["factor_conversion"] ?: 1);
                $fila["cantidad_minima"] = floatval($fila["cantidad_minima"] ?: 1);
                $fila["stock_minimo"] = floatval($fila["stock_minimo"] ?: 0);
                $fila["stock_maximo"] = $fila["stock_maximo"] === null ? null : floatval($fila["stock_maximo"]);
                $fila["punto_reorden"] = floatval($fila["punto_reorden"] ?: 0);
                $fila["existencia_revisada"] = 0;
                $fila["cantidad_sugerida"] = $this->calcularCantidadSugerida($fila, 0);
                $fila["cantidad_solicitar"] = $fila["cantidad_sugerida"];
                $items[] = $fila;
            }

            return $this->respuesta(false, "success", "Productos del proveedor consultados", array(
                "sin_escrituras" => true,
                "id_proveedor" => $idProveedor,
                "items" => $items,
                "total" => count($items)
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    public function consultar($idSugerido) {
        try {
            $db = $this->getConexion();
            if (!$this->schemaDisponible($db)) {
                return $this->respuesta(false, "info", "Esquema de sugerido pendiente", array("schema_pendiente" => 1, "sugerido" => null, "detalle" => array()));
            }
            $idSugerido = intval($idSugerido);
            if ($idSugerido <= 0) {
                return $this->respuesta(false, "success", "Nuevo sugerido", array("schema_pendiente" => 0, "sugerido" => null, "detalle" => array()));
            }
            $stmt = $db->prepare("SELECT s.*, p.proveedor FROM erp_compras_sugeridos_compra s INNER JOIN erp_proveedores p ON p.id_proveedor=s.id_proveedor WHERE s.id_sugerido_compra=:id");
            $stmt->execute(array(":id" => $idSugerido));
            $sugerido = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$sugerido) {
                return $this->respuesta(true, "warning", "Sugerido de compra no encontrado");
            }
            $stmt = $db->prepare("SELECT * FROM erp_compras_sugeridos_compra_detalle WHERE id_sugerido_compra=:id ORDER BY id_detalle");
            $stmt->execute(array(":id" => $idSugerido));
            return $this->respuesta(false, "success", "Sugerido consultado", array(
                "schema_pendiente" => 0,
                "sugerido" => $sugerido,
                "detalle" => $stmt->fetchAll(PDO::FETCH_ASSOC)
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-08-20
     * Proposito: guardar revision de sugerido sin afectar inventario y con snapshot de minimo/maximo usado.
     * Impacto: Compras/Sugerido; permite retomar o generar solicitud posteriormente.
     */
    public function guardar($datos, $idUsuario) {
        $db = $this->getConexion();
        try {
            if (!$this->schemaDisponible($db)) {
                return $this->respuesta(true, "warning", "El esquema de Sugerido de compra aun no esta preparado", array("schema_pendiente" => 1));
            }
            $idSugerido = intval($this->valor($datos, "id_sugerido_compra", 0));
            $idProveedor = intval($this->valor($datos, "id_proveedor", 0));
            $estatus = $this->normalizarEstatus($this->valor($datos, "estatus", "borrador"));
            $items = $this->valor($datos, "items", array());
            if (is_string($items)) {
                $items = json_decode($items, true);
            }
            if ($idProveedor <= 0) {
                return $this->respuesta(true, "warning", "Selecciona un proveedor");
            }
            if (!is_array($items) || empty($items)) {
                return $this->respuesta(true, "warning", "Agrega al menos una partida al sugerido");
            }

            $detalle = $this->normalizarDetalle($items);
            $db->beginTransaction();
            if ($idSugerido > 0) {
                $stmt = $db->prepare("SELECT estatus FROM erp_compras_sugeridos_compra WHERE id_sugerido_compra=:id FOR UPDATE");
                $stmt->execute(array(":id" => $idSugerido));
                $actual = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$actual) {
                    throw new Exception("Sugerido no encontrado");
                }
                if (in_array($actual["estatus"], array("solicitud_generada", "cancelada"), true)) {
                    throw new Exception("Este sugerido ya no permite edicion");
                }
                $stmt = $db->prepare("UPDATE erp_compras_sugeridos_compra SET id_proveedor=:proveedor, estatus=:estatus, observaciones=:observaciones, fecha_actualizacion=NOW() WHERE id_sugerido_compra=:id");
                $stmt->execute(array(
                    ":proveedor" => $idProveedor,
                    ":estatus" => $estatus,
                    ":observaciones" => trim((string) $this->valor($datos, "observaciones", "")),
                    ":id" => $idSugerido
                ));
                $db->prepare("DELETE FROM erp_compras_sugeridos_compra_detalle WHERE id_sugerido_compra=:id")->execute(array(":id" => $idSugerido));
            } else {
                $stmt = $db->prepare("INSERT INTO erp_compras_sugeridos_compra (folio, id_proveedor, estatus, observaciones, creado_por) VALUES (NULL, :proveedor, :estatus, :observaciones, :usuario)");
                $stmt->execute(array(
                    ":proveedor" => $idProveedor,
                    ":estatus" => $estatus,
                    ":observaciones" => trim((string) $this->valor($datos, "observaciones", "")),
                    ":usuario" => intval($idUsuario) ?: null
                ));
                $idSugerido = intval($db->lastInsertId());
                $folio = "SUG-" . date("Y") . "-" . str_pad($idSugerido, 6, "0", STR_PAD_LEFT);
                $db->prepare("UPDATE erp_compras_sugeridos_compra SET folio=:folio WHERE id_sugerido_compra=:id")
                    ->execute(array(":folio" => $folio, ":id" => $idSugerido));
            }
            $this->insertarDetalle($db, $idSugerido, $detalle);
            $db->commit();
            return $this->respuesta(false, "success", "Sugerido de compra guardado", array("id_sugerido_compra" => $idSugerido, "estatus" => $estatus));
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    public function generarSolicitud($idSugerido, $idUsuario) {
        $db = $this->getConexion();
        try {
            if (!$this->schemaDisponible($db)) {
                return $this->respuesta(true, "warning", "El esquema de Sugerido de compra aun no esta preparado", array("schema_pendiente" => 1));
            }
            $consulta = $this->consultar($idSugerido);
            if ($consulta["error"]) {
                return $consulta;
            }
            $sugerido = $consulta["depurar"]["sugerido"];
            $detalle = $consulta["depurar"]["detalle"];
            if (!$sugerido || in_array($sugerido["estatus"], array("solicitud_generada", "cancelada"), true)) {
                return $this->respuesta(true, "warning", "Este sugerido no puede generar solicitud");
            }
            $items = array();
            foreach ($detalle as $item) {
                $cantidad = round(floatval($item["cantidad_solicitar"]), 6);
                if ($cantidad <= 0) {
                    continue;
                }
                $items[] = array(
                    "id_sku_erp" => intval($item["id_sku_erp"]),
                    "id_sku_proveedor" => intval($item["id_sku_proveedor"]),
                    "sku" => $item["sku_proveedor"],
                    "nombre" => $item["nombre_proveedor"],
                    "cantidad" => $cantidad,
                    "costo_estimado" => floatval($item["costo_estimado"]),
                    "observaciones" => trim((string) $item["observaciones"]),
                    "factor_conversion" => floatval($item["factor_conversion"]),
                    "cantidad_minima" => floatval($item["cantidad_minima"]),
                    "fuente_costo" => "sugerido_compra",
                    "decision_abastecimiento" => array("origen" => "sugerido_compra", "id_sugerido_compra" => intval($idSugerido))
                );
            }
            if (empty($items)) {
                return $this->respuesta(true, "warning", "No hay cantidades a solicitar");
            }
            require_once __DIR__ . "/SolicitudesCompraErp.php";
            $solicitudes = new SolicitudesCompraErp();
            $respuesta = $solicitudes->guardar(array(
                "id_proveedor" => intval($sugerido["id_proveedor"]),
                "estatus" => "borrador",
                "observaciones" => "Generada desde sugerido de compra " . $sugerido["folio"],
                "prioridad" => "normal",
                "items" => $items
            ), $idUsuario);
            if ($respuesta["error"]) {
                return $respuesta;
            }
            $db->prepare("UPDATE erp_compras_sugeridos_compra SET estatus='solicitud_generada', id_solicitud_generada=:solicitud, fecha_actualizacion=NOW() WHERE id_sugerido_compra=:id")
                ->execute(array(":solicitud" => intval($respuesta["depurar"]["id_solicitud"]), ":id" => intval($idSugerido)));
            $respuesta["depurar"]["id_sugerido_compra"] = intval($idSugerido);
            return $respuesta;
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    private function normalizarDetalle($items) {
        $detalle = array();
        $vistos = array();
        foreach ($items as $item) {
            $idSku = intval($this->valor($item, "id_sku_erp", $this->valor($item, "id_sku", 0)));
            $idRelacion = intval($this->valor($item, "id_sku_proveedor", 0));
            if ($idSku <= 0 || $idRelacion <= 0) {
                throw new Exception("Todas las partidas deben tener relacion proveedor-SKU ERP");
            }
            $clave = "relacion:" . $idRelacion;
            if (isset($vistos[$clave])) {
                throw new Exception("Hay productos repetidos en el sugerido");
            }
            $vistos[$clave] = true;
            $existencia = round(floatval($this->valor($item, "existencia_revisada", 0)), 6);
            $fila = array(
                "id_sku_erp" => $idSku,
                "id_sku_proveedor" => $idRelacion,
                "sku_erp" => trim((string) $this->valor($item, "sku_erp", "")),
                "sku_proveedor" => trim((string) $this->valor($item, "sku_proveedor", $this->valor($item, "sku", ""))),
                "nombre_erp" => trim((string) $this->valor($item, "nombre_erp", "")),
                "nombre_proveedor" => trim((string) $this->valor($item, "nombre_proveedor", $this->valor($item, "nombre", ""))),
                "unidad_compra" => trim((string) $this->valor($item, "unidad_compra", $this->valor($item, "unidad", ""))),
                "factor_conversion" => max(0.000001, floatval($this->valor($item, "factor_conversion", 1))),
                "cantidad_minima" => max(0, floatval($this->valor($item, "cantidad_minima", 1))),
                "stock_minimo" => max(0, floatval($this->valor($item, "stock_minimo", 0))),
                "stock_maximo" => $this->valor($item, "stock_maximo", null) === null || $this->valor($item, "stock_maximo", "") === "" ? null : max(0, floatval($this->valor($item, "stock_maximo", 0))),
                "punto_reorden" => max(0, floatval($this->valor($item, "punto_reorden", 0))),
                "existencia_revisada" => $existencia,
                "cantidad_sugerida" => 0,
                "cantidad_solicitar" => max(0, floatval($this->valor($item, "cantidad_solicitar", 0))),
                "costo_estimado" => max(0, floatval($this->valor($item, "costo_estimado", $this->valor($item, "costo_ultimo", 0)))),
                "observaciones" => trim((string) $this->valor($item, "observaciones", ""))
            );
            $fila["cantidad_sugerida"] = $this->calcularCantidadSugerida($fila, $existencia);
            if ($fila["cantidad_solicitar"] <= 0) {
                $fila["cantidad_solicitar"] = $fila["cantidad_sugerida"];
            }
            $detalle[] = $fila;
        }
        return $detalle;
    }

    private function insertarDetalle(PDO $db, $idSugerido, $detalle) {
        $stmt = $db->prepare("INSERT INTO erp_compras_sugeridos_compra_detalle
            (id_sugerido_compra, id_sku_erp, id_sku_proveedor, sku_erp, sku_proveedor, nombre_erp, nombre_proveedor,
             unidad_compra, factor_conversion, cantidad_minima, stock_minimo, stock_maximo, punto_reorden,
             existencia_revisada, cantidad_sugerida, cantidad_solicitar, costo_estimado, observaciones)
            VALUES (:id, :sku, :relacion, :sku_erp, :sku_proveedor, :nombre_erp, :nombre_proveedor,
             :unidad, :factor, :minima, :stock_minimo, :stock_maximo, :reorden,
             :existencia, :sugerida, :solicitar, :costo, :observaciones)");
        foreach ($detalle as $item) {
            $stmt->execute(array(
                ":id" => intval($idSugerido),
                ":sku" => $item["id_sku_erp"],
                ":relacion" => $item["id_sku_proveedor"],
                ":sku_erp" => $item["sku_erp"],
                ":sku_proveedor" => $item["sku_proveedor"],
                ":nombre_erp" => $item["nombre_erp"],
                ":nombre_proveedor" => $item["nombre_proveedor"],
                ":unidad" => $item["unidad_compra"],
                ":factor" => $item["factor_conversion"],
                ":minima" => $item["cantidad_minima"],
                ":stock_minimo" => $item["stock_minimo"],
                ":stock_maximo" => $item["stock_maximo"],
                ":reorden" => $item["punto_reorden"],
                ":existencia" => $item["existencia_revisada"],
                ":sugerida" => $item["cantidad_sugerida"],
                ":solicitar" => $item["cantidad_solicitar"],
                ":costo" => $item["costo_estimado"],
                ":observaciones" => $item["observaciones"]
            ));
        }
    }

    private function calcularCantidadSugerida($fila, $existencia) {
        $existencia = max(0, floatval($existencia));
        $minimo = max(0, floatval($this->valor($fila, "stock_minimo", 0)));
        $maximoValor = $this->valor($fila, "stock_maximo", null);
        $maximo = $maximoValor === null || $maximoValor === "" ? null : max(0, floatval($maximoValor));
        $reorden = max(0, floatval($this->valor($fila, "punto_reorden", 0)));
        $factor = max(0.000001, floatval($this->valor($fila, "factor_conversion", 1)));
        $minima = max(0, floatval($this->valor($fila, "cantidad_minima", 1)));
        $necesidadBase = 0;
        if ($maximo !== null && $maximo > 0) {
            $necesidadBase = max(0, $maximo - $existencia);
        } elseif ($reorden > 0) {
            $necesidadBase = max(0, $reorden - $existencia);
        } elseif ($minimo > 0) {
            $necesidadBase = max(0, $minimo - $existencia);
        }
        if ($necesidadBase <= 0) {
            return 0;
        }
        $cantidadCompra = ceil(($necesidadBase / $factor) * 1000000) / 1000000;
        if ($minima > 0 && $cantidadCompra > 0 && $cantidadCompra < $minima) {
            $cantidadCompra = $minima;
        }
        return round($cantidadCompra, 6);
    }

    private function schemaDisponible(PDO $db) {
        return $this->tablaExiste($db, "erp_compras_sugeridos_compra") && $this->tablaExiste($db, "erp_compras_sugeridos_compra_detalle");
    }

    private function tablaExiste(PDO $db, $tabla) {
        $stmt = $db->prepare("SHOW TABLES LIKE :tabla");
        $stmt->execute(array(":tabla" => $tabla));
        return $stmt->fetch(PDO::FETCH_NUM) !== false;
    }

    private function normalizarEstatus($estatus) {
        $estatus = strtolower(trim((string) $estatus));
        return in_array($estatus, array("borrador", "lista"), true) ? $estatus : "borrador";
    }

    private function valor($datos, $campo, $default = null) {
        return is_array($datos) && array_key_exists($campo, $datos) ? $datos[$campo] : $default;
    }

    private function respuesta($error, $tipo, $mensaje, $depurar = array()) {
        return array("error" => $error, "tipo" => $tipo, "mensaje" => $mensaje, "depurar" => $depurar);
    }
}

