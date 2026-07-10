<?php

class InventarioErp extends CRUD {

    public function catalogos() {
        try {
            $db = $this->getConexion();
            return $this->respuesta(false, "success", "Catalogos de inventario consultados", array(
                "almacenes" => $db->query("SELECT id_almacen, codigo_almacen, almacen, ciudad, colonia, tipo_almacen
                    FROM erp_almacenes
                    WHERE COALESCE(estatus,'activo')='activo'
                    ORDER BY orden ASC, almacen ASC")->fetchAll(PDO::FETCH_ASSOC),
                "ubicaciones" => $db->query("SELECT id_ubicacion, id_almacen_clave id_almacen, codigo_ubicacion, nombre, zona, pasillo, rack, nivel, contenedor
                    FROM erp_almacen_ubicaciones WHERE estatus IN ('activo','activa') ORDER BY id_almacen_clave, codigo_ubicacion")->fetchAll(PDO::FETCH_ASSOC)
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-06-25
     * Proposito: lista saldos contables enriquecidos con resumen de unidades fisicas trazables.
     * Impacto: Inventario/Existencias; permite distinguir stock contable, unidad cerrada y unidad abierta disponible.
     * Contrato: consulta read-only; una unidad abierta suma disponibilidad fisica, pero no representa unidad cerrada vendible.
     */
    public function listarExistencias($filtros = array()) {
        try {
            $db = $this->getConexion();
            $idAlmacen = intval(isset($filtros["id_almacen"]) ? $filtros["id_almacen"] : 0);
            $termino = trim(isset($filtros["q"]) ? $filtros["q"] : "");
            $incluirAgotadas = intval(isset($filtros["incluir_agotadas"]) ? $filtros["incluir_agotadas"] : 0);
            $estadoFisico = trim(isset($filtros["estado_fisico"]) ? $filtros["estado_fisico"] : "");
            if ($estadoFisico !== "" && !in_array($estadoFisico, array("cerrada", "abierta", "consumida", "agotada", "vendida", "cancelada"), true)) {
                $estadoFisico = "";
            }
            $sql = "SELECT e.id_existencia_inventario, e.codigo_existencia, e.id_almacen_clave id_almacen,
                a.almacen, e.id_sku_erp id_sku, s.sku, s.nombre nombre_sku, p.nombre producto,
                e.lote, e.fecha_caducidad, e.ubicacion_id, e.ubicacion, e.cantidad, e.cantidad_apartada,
                e.cantidad_disponible, e.costo_promedio, e.estatus_existencia, e.fecha_actualizacion,
                COALESCE(uf.unidades_total, 0) unidades_total,
                COALESCE(uf.unidades_disponibles, 0) unidades_disponibles,
                COALESCE(uf.unidades_cerradas, 0) unidades_cerradas,
                COALESCE(uf.unidades_abiertas, 0) unidades_abiertas,
                COALESCE(uf.unidades_consumidas, 0) unidades_consumidas,
                COALESCE(uf.unidades_vendidas, 0) unidades_vendidas,
                COALESCE(uf.etiquetas_pendientes, 0) etiquetas_pendientes,
                COALESCE(uf.etiquetas_impresas, 0) etiquetas_impresas,
                COALESCE(uf.etiquetas_pegadas, 0) etiquetas_pegadas,
                COALESCE(uf.contenido_base_original, 0) contenido_base_original,
                COALESCE(uf.contenido_base_disponible, 0) contenido_base_disponible,
                COALESCE(uf.unidad_base, '') unidad_base_trazable,
                ROUND(e.cantidad-COALESCE(uf.contenido_base_disponible, 0), 6) diferencia_contenido_unidades
                FROM erp_inventario_existencias e
                INNER JOIN erp_catalogo_skus s ON s.id_sku=e.id_sku_erp
                INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
                LEFT JOIN erp_almacenes a ON a.id_almacen=e.id_almacen_clave
                LEFT JOIN (
                    SELECT id_existencia_inventario,
                        COUNT(*) unidades_total,
                        SUM(CASE WHEN estatus='disponible' THEN 1 ELSE 0 END) unidades_disponibles,
                        SUM(CASE WHEN estado_fisico='cerrada' AND estatus='disponible' THEN 1 ELSE 0 END) unidades_cerradas,
                        SUM(CASE WHEN estado_fisico='abierta' AND estatus='disponible' THEN 1 ELSE 0 END) unidades_abiertas,
                        SUM(CASE WHEN estado_fisico IN ('consumida','agotada') OR estatus IN ('consumida','agotada') THEN 1 ELSE 0 END) unidades_consumidas,
                        SUM(CASE WHEN estado_fisico='vendida' OR estatus='vendida' THEN 1 ELSE 0 END) unidades_vendidas,
                        SUM(CASE WHEN estatus <> 'cancelada' AND estado_etiqueta IN ('pendiente_impresion','reimpresa') THEN 1 ELSE 0 END) etiquetas_pendientes,
                        SUM(CASE WHEN estatus <> 'cancelada' AND estado_etiqueta='impresa' THEN 1 ELSE 0 END) etiquetas_impresas,
                        SUM(CASE WHEN estatus <> 'cancelada' AND estado_etiqueta='pegada' THEN 1 ELSE 0 END) etiquetas_pegadas,
                        SUM(cantidad_base_original) contenido_base_original,
                        SUM(CASE WHEN estatus='disponible' THEN cantidad_base_disponible ELSE 0 END) contenido_base_disponible,
                        MAX(unidad_base) unidad_base
                    FROM erp_inventario_unidades
                    WHERE id_existencia_inventario IS NOT NULL
                    GROUP BY id_existencia_inventario
                ) uf ON uf.id_existencia_inventario=e.id_existencia_inventario
                WHERE (:almacen=0 OR e.id_almacen_clave=:almacen_filtro)
                  AND (
                    :estado_fisico='' OR EXISTS (
                        SELECT 1 FROM erp_inventario_unidades ux
                        WHERE ux.id_existencia_inventario=e.id_existencia_inventario
                          AND ux.estado_fisico=:estado_fisico_filtro
                    )
                  )
                  AND (
                    :incluir_agotadas=1 OR e.cantidad<>0 OR e.cantidad_disponible<>0 OR e.cantidad_apartada<>0
                    OR (:termino<>'' AND (s.sku LIKE :buscar OR e.codigo_existencia LIKE :buscar))
                    OR (:termino<>'' AND EXISTS (
                        SELECT 1 FROM erp_inventario_movimientos mt
                        WHERE mt.id_existencia_inventario=e.id_existencia_inventario
                          AND (mt.referencia LIKE :buscar OR mt.codigo_existencia LIKE :buscar)
                    ))
                    OR (:termino<>'' AND EXISTS (
                        SELECT 1 FROM erp_inventario_unidades ut
                        LEFT JOIN erp_almacen_preparaciones pt ON ut.origen_tipo='preparacion_presentacion' AND pt.id_preparacion_almacen=ut.origen_id
                        LEFT JOIN erp_almacen_recepciones rt ON rt.id_recepcion_almacen=ut.id_recepcion_almacen
                        WHERE ut.id_existencia_inventario=e.id_existencia_inventario
                          AND (ut.codigo_unico LIKE :buscar OR ut.codigo_etiqueta_interna LIKE :buscar OR ut.serie_fabricante LIKE :buscar OR ut.estado_fisico LIKE :buscar OR pt.folio LIKE :buscar OR rt.folio LIKE :buscar)
                    ))
                  )
                  AND (
                    :termino='' OR s.sku LIKE :buscar OR s.nombre LIKE :buscar OR p.nombre LIKE :buscar OR e.codigo_existencia LIKE :buscar
                    OR EXISTS (
                        SELECT 1 FROM erp_inventario_movimientos mt
                        WHERE mt.id_existencia_inventario=e.id_existencia_inventario
                          AND (mt.referencia LIKE :buscar OR mt.codigo_existencia LIKE :buscar)
                    )
                    OR EXISTS (
                        SELECT 1 FROM erp_inventario_unidades ut
                        LEFT JOIN erp_almacen_preparaciones pt ON ut.origen_tipo='preparacion_presentacion' AND pt.id_preparacion_almacen=ut.origen_id
                        LEFT JOIN erp_almacen_recepciones rt ON rt.id_recepcion_almacen=ut.id_recepcion_almacen
                        WHERE ut.id_existencia_inventario=e.id_existencia_inventario
                          AND (ut.codigo_unico LIKE :buscar OR ut.codigo_etiqueta_interna LIKE :buscar OR ut.serie_fabricante LIKE :buscar OR ut.estado_fisico LIKE :buscar OR pt.folio LIKE :buscar OR rt.folio LIKE :buscar)
                    )
                  )
                ORDER BY p.nombre, s.sku, a.almacen, e.fecha_caducidad LIMIT 1000";
            $stmt = $db->prepare($sql);
            $stmt->execute(array(
                ":almacen" => $idAlmacen, ":almacen_filtro" => $idAlmacen,
                ":estado_fisico" => $estadoFisico, ":estado_fisico_filtro" => $estadoFisico,
                ":incluir_agotadas" => $incluirAgotadas,
                ":termino" => $termino, ":buscar" => "%" . $termino . "%"
            ));
            return $this->respuesta(false, "success", "Existencias ERP consultadas", $stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    public function listarMovimientos($filtros = array()) {
        try {
            $db = $this->getConexion();
            $idAlmacen = intval(isset($filtros["id_almacen"]) ? $filtros["id_almacen"] : 0);
            $termino = trim(isset($filtros["q"]) ? $filtros["q"] : "");
            $sql = "SELECT m.id_movimiento_inventario, m.fecha_registro, m.tipo_movimiento, m.origen_tipo,
                m.referencia, m.id_almacen, a.almacen, m.id_sku_erp id_sku, s.sku,
                COALESCE(s.nombre, p.nombre) producto, m.codigo_existencia, m.lote, m.fecha_caducidad,
                m.ubicacion, m.cantidad, m.costo_unitario, m.costo_total, m.existencia_anterior,
                m.existencia_nueva, m.observaciones
                FROM erp_inventario_movimientos m
                LEFT JOIN erp_catalogo_skus s ON s.id_sku=m.id_sku_erp
                LEFT JOIN erp_catalogo_productos p ON p.id_producto_erp=m.id_producto
                LEFT JOIN erp_almacenes a ON a.id_almacen=m.id_almacen
                WHERE (:almacen=0 OR m.id_almacen=:almacen_filtro)
                  AND (
                    :termino='' OR s.sku LIKE :buscar OR s.nombre LIKE :buscar OR p.nombre LIKE :buscar
                    OR m.referencia LIKE :buscar OR m.codigo_existencia LIKE :buscar OR m.lote LIKE :buscar OR m.origen_tipo LIKE :buscar
                    OR EXISTS (
                        SELECT 1 FROM erp_inventario_unidades ut
                        LEFT JOIN erp_almacen_preparaciones pt ON ut.origen_tipo='preparacion_presentacion' AND pt.id_preparacion_almacen=ut.origen_id
                        LEFT JOIN erp_almacen_recepciones rt ON rt.id_recepcion_almacen=ut.id_recepcion_almacen
                        WHERE ut.id_existencia_inventario=m.id_existencia_inventario
                          AND (ut.codigo_unico LIKE :buscar OR ut.codigo_etiqueta_interna LIKE :buscar OR ut.serie_fabricante LIKE :buscar OR pt.folio LIKE :buscar OR rt.folio LIKE :buscar)
                    )
                  )
                ORDER BY m.id_movimiento_inventario DESC LIMIT 500";
            $stmt = $db->prepare($sql);
            $stmt->execute(array(
                ":almacen" => $idAlmacen, ":almacen_filtro" => $idAlmacen,
                ":termino" => $termino, ":buscar" => "%" . $termino . "%"
            ));
            return $this->respuesta(false, "success", "Kardex ERP consultado", $stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-06-25
     * Proposito: lista etiquetas/unidades fisicas para el ciclo operativo de impresion, pegado y trazabilidad.
     * Impacto: Almacen/Etiquetado; muestra origen de recepcion, preparacion o inventario inicial sin mezclar flujos.
     * Contrato: no modifica inventario; expone estado de etiqueta, estado fisico y contenido disponible para la UI.
     */
    public function listarEtiquetas($filtros = array()) {
        try {
            $db = $this->getConexion();
            $idAlmacen = intval(isset($filtros["id_almacen"]) ? $filtros["id_almacen"] : 0);
            $termino = trim(isset($filtros["q"]) ? $filtros["q"] : "");
            $estado = trim(isset($filtros["estado_etiqueta"]) ? $filtros["estado_etiqueta"] : "");
            $estadoFisico = trim(isset($filtros["estado_fisico"]) ? $filtros["estado_fisico"] : "");
            $estadosPermitidos = array("pendiente_impresion", "impresa", "pegada", "reimpresa", "cancelada");
            if ($estado !== "" && !in_array($estado, $estadosPermitidos, true)) {
                $estado = "";
            }
            if ($estadoFisico !== "" && !in_array($estadoFisico, array("cerrada", "abierta", "consumida", "agotada", "vendida", "cancelada"), true)) {
                $estadoFisico = "";
            }

            $sql = "SELECT u.id_inventario_unidad, u.codigo_unico, u.tipo_identidad, u.serie_fabricante,
                u.codigo_etiqueta_interna, u.estado_etiqueta, u.estatus, u.fecha_impresion, u.fecha_etiquetado,
                u.cantidad_base_original, u.cantidad_base_disponible, u.unidad_base, u.estado_fisico,
                u.id_almacen, a.almacen, u.id_sku_erp id_sku, s.sku, COALESCE(s.nombre, p.nombre) producto,
                u.lote, u.fecha_caducidad, u.ubicacion_id, COALESCE(ub.nombre, u.ubicacion_id) ubicacion,
                u.origen_tipo, u.origen_id, u.origen_detalle_id,
                COALESCE(r.folio, prep.folio, mi.referencia) folio_recepcion,
                CASE
                    WHEN prep.folio IS NOT NULL THEN 'Preparacion/Empaque'
                    WHEN mi.referencia IS NOT NULL THEN 'Inventario inicial'
                    ELSE r.folio_orden_compra
                END folio_orden_compra
                FROM erp_inventario_unidades u
                LEFT JOIN erp_catalogo_skus s ON s.id_sku=u.id_sku_erp
                LEFT JOIN erp_catalogo_productos p ON p.id_producto_erp=COALESCE(s.id_producto_erp, u.id_producto)
                LEFT JOIN erp_almacenes a ON a.id_almacen=u.id_almacen
                LEFT JOIN erp_almacen_ubicaciones ub ON ub.id_ubicacion=u.ubicacion_id
                LEFT JOIN erp_almacen_recepciones r ON r.id_recepcion_almacen=u.id_recepcion_almacen
                LEFT JOIN erp_almacen_preparaciones prep ON u.origen_tipo='preparacion_presentacion' AND prep.id_preparacion_almacen=u.origen_id
                LEFT JOIN erp_inventario_movimientos mi ON u.origen_tipo='inventario_inicial' AND mi.id_movimiento_inventario=u.origen_id
                WHERE (:almacen=0 OR u.id_almacen=:almacen_filtro)
                  AND (:estado='' OR u.estado_etiqueta=:estado_filtro)
                  AND (:estado_fisico='' OR u.estado_fisico=:estado_fisico_filtro)
                  AND (:termino='' OR u.codigo_unico LIKE :buscar OR u.codigo_etiqueta_interna LIKE :buscar OR u.serie_fabricante LIKE :buscar OR u.estado_fisico LIKE :buscar OR s.sku LIKE :buscar OR s.nombre LIKE :buscar OR p.nombre LIKE :buscar OR r.folio LIKE :buscar OR prep.folio LIKE :buscar OR mi.referencia LIKE :buscar)
                ORDER BY FIELD(u.estado_etiqueta, 'pendiente_impresion', 'impresa', 'pegada', 'reimpresa', 'cancelada'),
                    u.id_inventario_unidad DESC
                LIMIT 500";
            $stmt = $db->prepare($sql);
            $stmt->execute(array(
                ":almacen" => $idAlmacen,
                ":almacen_filtro" => $idAlmacen,
                ":estado" => $estado,
                ":estado_filtro" => $estado,
                ":estado_fisico" => $estadoFisico,
                ":estado_fisico_filtro" => $estadoFisico,
                ":termino" => $termino,
                ":buscar" => "%" . $termino . "%"
            ));
            return $this->respuesta(false, "success", "Etiquetas de trazabilidad consultadas", $stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    public function consultarTrazabilidad($filtros = array()) {
        try {
            $db = $this->getConexion();
            $clave = trim(isset($filtros["q"]) ? $filtros["q"] : "");
            if ($clave === "") {
                return $this->respuesta(true, "warning", "Clave de trazabilidad obligatoria");
            }
            $buscar = "%" . $clave . "%";

            $sqlExistencias = "SELECT DISTINCT e.id_existencia_inventario, e.codigo_existencia, e.id_almacen_clave id_almacen,
                    a.almacen, e.id_sku_erp id_sku, s.sku, s.nombre nombre_sku, p.nombre producto,
                    e.lote, e.fecha_caducidad, e.ubicacion_id, e.ubicacion, e.cantidad, e.cantidad_apartada,
                    e.cantidad_disponible, e.costo_promedio, e.estatus_existencia, e.fecha_actualizacion
                FROM erp_inventario_existencias e
                INNER JOIN erp_catalogo_skus s ON s.id_sku=e.id_sku_erp
                INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
                LEFT JOIN erp_almacenes a ON a.id_almacen=e.id_almacen_clave
                LEFT JOIN erp_inventario_movimientos m ON m.id_existencia_inventario=e.id_existencia_inventario
                LEFT JOIN erp_inventario_unidades u ON u.id_existencia_inventario=e.id_existencia_inventario
                LEFT JOIN erp_almacen_preparaciones prep ON u.origen_tipo='preparacion_presentacion' AND prep.id_preparacion_almacen=u.origen_id
                LEFT JOIN erp_almacen_recepciones rec ON rec.id_recepcion_almacen=u.id_recepcion_almacen
                WHERE e.codigo_existencia=:clave
                   OR s.sku=:clave_sku
                   OR m.referencia LIKE :buscar
                   OR m.codigo_existencia=:clave_mov
                   OR u.codigo_unico=:clave_unidad
                   OR u.codigo_etiqueta_interna=:clave_etiqueta
                   OR u.serie_fabricante=:clave_serie
                   OR prep.folio LIKE :buscar_prep
                   OR rec.folio LIKE :buscar_rec
                ORDER BY e.id_existencia_inventario DESC
                LIMIT 25";
            $stmt = $db->prepare($sqlExistencias);
            $stmt->execute(array(
                ":clave" => $clave,
                ":clave_sku" => $clave,
                ":buscar" => $buscar,
                ":clave_mov" => $clave,
                ":clave_unidad" => $clave,
                ":clave_etiqueta" => $clave,
                ":clave_serie" => $clave,
                ":buscar_prep" => $buscar,
                ":buscar_rec" => $buscar
            ));
            $existencias = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $idsExistencia = array_values(array_unique(array_filter(array_map(function ($item) {
                return intval($item["id_existencia_inventario"]);
            }, $existencias))));

            $movimientos = $this->consultarMovimientosTrazabilidad($db, $clave, $buscar, $idsExistencia);
            $unidades = $this->consultarUnidadesTrazabilidad($db, $clave, $buscar, $idsExistencia);

            return $this->respuesta(false, "success", "Trazabilidad consultada", array(
                "clave" => $clave,
                "existencias" => $existencias,
                "movimientos" => $movimientos,
                "unidades" => $unidades
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    public function diagnosticoOperativo($filtros = array()) {
        try {
            $db = $this->getConexion();
            $idAlmacen = intval(isset($filtros["id_almacen"]) ? $filtros["id_almacen"] : 0);
            $hallazgos = array();

            $negativos = $this->diagnosticoExistencias($db, $idAlmacen,
                "(e.cantidad < 0 OR e.cantidad_disponible < 0 OR e.cantidad_apartada < 0)",
                "negativos");
            $this->agregarHallazgoDiagnostico($hallazgos, "INV-DIAG-NEG", "danger", "Existencias negativas", $negativos);

            $descuadres = $this->diagnosticoExistencias($db, $idAlmacen,
                "ABS(e.cantidad - e.cantidad_apartada - e.cantidad_disponible) > 0.0001",
                "descuadres");
            $this->agregarHallazgoDiagnostico($hallazgos, "INV-DIAG-SALDO", "danger", "Cantidad, apartado y disponible no cuadran", $descuadres);

            $estatus = $this->diagnosticoExistencias($db, $idAlmacen,
                "((e.cantidad = 0 AND e.cantidad_disponible = 0 AND e.cantidad_apartada = 0 AND e.estatus_existencia <> 'agotada') OR (e.cantidad > 0 AND e.estatus_existencia = 'agotada'))",
                "estatus_inconsistente");
            $this->agregarHallazgoDiagnostico($hallazgos, "INV-DIAG-EST", "warning", "Estado de existencia inconsistente", $estatus);

            $vencidas = $this->diagnosticoExistencias($db, $idAlmacen,
                "e.cantidad_disponible > 0 AND e.fecha_caducidad IS NOT NULL AND e.fecha_caducidad < CURDATE()",
                "caducidad_vencida");
            $this->agregarHallazgoDiagnostico($hallazgos, "INV-DIAG-CAD-VENC", "danger", "Existencias vencidas con disponible", $vencidas);

            $porVencer = $this->diagnosticoExistencias($db, $idAlmacen,
                "e.cantidad_disponible > 0 AND e.fecha_caducidad IS NOT NULL AND e.fecha_caducidad >= CURDATE() AND e.fecha_caducidad <= DATE_ADD(CURDATE(), INTERVAL COALESCE(NULLIF(r.dias_alerta_caducidad,0), 90) DAY)",
                "caducidad_proxima");
            $this->agregarHallazgoDiagnostico($hallazgos, "INV-DIAG-CAD-PROX", "warning", "Existencias proximas a caducar", $porVencer);

            $etiquetasPendientes = $this->diagnosticoEtiquetasPendientes($db, $idAlmacen);
            $this->agregarHallazgoDiagnostico($hallazgos, "INV-DIAG-ETQ", "info", "Etiquetas pendientes de ciclo fisico", $etiquetasPendientes);

            $unidadesDescuadradas = $this->diagnosticoUnidadesVsExistencia($db, $idAlmacen);
            $this->agregarHallazgoDiagnostico($hallazgos, "INV-DIAG-UNID", "warning", "Unidades disponibles no coinciden con existencia", $unidadesDescuadradas);

            $reservasSaldo = $this->diagnosticoReservasVsApartado($db, $idAlmacen);
            $this->agregarHallazgoDiagnostico($hallazgos, "INV-DIAG-RES-SALDO", "danger", "Apartado no cuadra con reservas activas", $reservasSaldo);

            $reservasVencidas = $this->diagnosticoReservasVencidas($db, $idAlmacen);
            $this->agregarHallazgoDiagnostico($hallazgos, "INV-DIAG-RES-VENC", "warning", "Reservas activas vencidas", $reservasVencidas);

            $reservasEstatus = $this->diagnosticoReservasEstatus($db, $idAlmacen);
            $this->agregarHallazgoDiagnostico($hallazgos, "INV-DIAG-RES-EST", "warning", "Reservas con estado inconsistente", $reservasEstatus);

            return $this->respuesta(false, "success", "Diagnostico operativo consultado", array(
                "id_almacen" => $idAlmacen,
                "fecha" => date("Y-m-d H:i:s"),
                "resumen" => array(
                    "total_hallazgos" => count($hallazgos),
                    "criticos" => count(array_filter($hallazgos, function ($item) { return $item["severidad"] === "danger"; })),
                    "advertencias" => count(array_filter($hallazgos, function ($item) { return $item["severidad"] === "warning"; })),
                    "informativos" => count(array_filter($hallazgos, function ($item) { return $item["severidad"] === "info"; }))
                ),
                "hallazgos" => $hallazgos
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    public function valuacionInventario($filtros = array()) {
        try {
            $db = $this->getConexion();
            $idAlmacen = intval(isset($filtros["id_almacen"]) ? $filtros["id_almacen"] : 0);
            $termino = trim(isset($filtros["q"]) ? $filtros["q"] : "");
            $sql = "SELECT e.id_almacen_clave id_almacen, a.almacen, e.id_sku_erp id_sku,
                    s.sku, COALESCE(s.nombre, p.nombre) producto,
                    COUNT(*) existencias,
                    SUM(e.cantidad) cantidad_total,
                    SUM(e.cantidad_disponible) disponible_total,
                    SUM(e.cantidad_apartada) apartada_total,
                    SUM(e.cantidad * e.costo_promedio) valor_total,
                    CASE WHEN SUM(e.cantidad) > 0 THEN SUM(e.cantidad * e.costo_promedio) / SUM(e.cantidad) ELSE 0 END costo_promedio_estimado
                FROM erp_inventario_existencias e
                INNER JOIN erp_catalogo_skus s ON s.id_sku=e.id_sku_erp
                INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
                LEFT JOIN erp_almacenes a ON a.id_almacen=e.id_almacen_clave
                WHERE (:almacen=0 OR e.id_almacen_clave=:almacen_filtro)
                  AND (e.cantidad<>0 OR e.cantidad_disponible<>0 OR e.cantidad_apartada<>0)
                  AND (:termino='' OR s.sku LIKE :buscar OR s.nombre LIKE :buscar OR p.nombre LIKE :buscar OR e.codigo_existencia LIKE :buscar)
                GROUP BY e.id_almacen_clave, a.almacen, e.id_sku_erp, s.sku, producto
                ORDER BY valor_total DESC, s.sku ASC
                LIMIT 500";
            $stmt = $db->prepare($sql);
            $stmt->execute(array(
                ":almacen" => $idAlmacen,
                ":almacen_filtro" => $idAlmacen,
                ":termino" => $termino,
                ":buscar" => "%" . $termino . "%"
            ));
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $resumen = array(
                "skus" => count($items),
                "cantidad_total" => 0,
                "disponible_total" => 0,
                "apartada_total" => 0,
                "valor_total" => 0
            );
            foreach ($items as $item) {
                $resumen["cantidad_total"] += floatval($item["cantidad_total"]);
                $resumen["disponible_total"] += floatval($item["disponible_total"]);
                $resumen["apartada_total"] += floatval($item["apartada_total"]);
                $resumen["valor_total"] += floatval($item["valor_total"]);
            }
            return $this->respuesta(false, "success", "Valuacion de inventario consultada", array(
                "resumen" => $resumen,
                "items" => $items
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    public function listarConteos($filtros = array()) {
        try {
            $db = $this->getConexion();
            $idAlmacen = intval(isset($filtros["id_almacen"]) ? $filtros["id_almacen"] : 0);
            $estatus = trim(isset($filtros["estatus"]) ? $filtros["estatus"] : "");
            $sql = "SELECT c.id_conteo_inventario, c.folio, c.id_almacen, a.almacen, c.ubicacion_id,
                    ub.codigo_ubicacion, ub.nombre ubicacion, c.tipo_conteo, c.estatus, c.fecha_programada,
                    c.fecha_inicio, c.fecha_cierre, c.referencia_ajuste, c.observaciones,
                    COUNT(d.id_conteo_detalle) partidas,
                    SUM(CASE WHEN d.cantidad_fisica IS NOT NULL THEN 1 ELSE 0 END) capturadas,
                    SUM(CASE WHEN ABS(d.diferencia) > 0.0001 THEN 1 ELSE 0 END) diferencias,
                    SUM(d.costo_diferencia) costo_diferencia
                FROM erp_inventario_conteos c
                LEFT JOIN erp_almacenes a ON a.id_almacen=c.id_almacen
                LEFT JOIN erp_almacen_ubicaciones ub ON ub.id_ubicacion=c.ubicacion_id
                LEFT JOIN erp_inventario_conteos_detalle d ON d.id_conteo_inventario=c.id_conteo_inventario
                WHERE (:almacen=0 OR c.id_almacen=:almacen_filtro)
                  AND (:estatus='' OR c.estatus=:estatus_filtro)
                GROUP BY c.id_conteo_inventario, c.folio, c.id_almacen, a.almacen, c.ubicacion_id,
                    ub.codigo_ubicacion, ub.nombre, c.tipo_conteo, c.estatus, c.fecha_programada,
                    c.fecha_inicio, c.fecha_cierre, c.referencia_ajuste, c.observaciones
                ORDER BY c.id_conteo_inventario DESC
                LIMIT 200";
            $stmt = $db->prepare($sql);
            $stmt->execute(array(
                ":almacen" => $idAlmacen,
                ":almacen_filtro" => $idAlmacen,
                ":estatus" => $estatus,
                ":estatus_filtro" => $estatus
            ));
            return $this->respuesta(false, "success", "Conteos consultados", $stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    public function crearConteo($datos, $idUsuario = 0) {
        $idAlmacen = intval(isset($datos["id_almacen"]) ? $datos["id_almacen"] : 0);
        $ubicacionId = intval(isset($datos["ubicacion_id"]) ? $datos["ubicacion_id"] : 0);
        $tipo = trim(isset($datos["tipo_conteo"]) ? $datos["tipo_conteo"] : "ciclico");
        if ($idAlmacen <= 0 || !in_array($tipo, array("ciclico", "general", "ubicacion"), true)) {
            return $this->respuesta(true, "warning", "Almacen y tipo de conteo son obligatorios");
        }

        $db = $this->getConexion();
        try {
            $db->beginTransaction();
            $this->validarAlmacen($db, $idAlmacen);
            if ($ubicacionId > 0) {
                $stmt = $db->prepare("SELECT id_ubicacion FROM erp_almacen_ubicaciones
                    WHERE id_ubicacion=:ubicacion AND id_almacen_clave=:almacen AND estatus IN ('activo','activa')");
                $stmt->execute(array(":ubicacion" => $ubicacionId, ":almacen" => $idAlmacen));
                if (!$stmt->fetchColumn()) {
                    throw new Exception("La ubicacion no pertenece al almacen");
                }
            }

            $folio = $this->folioConteo($db);
            $stmt = $db->prepare("INSERT INTO erp_inventario_conteos
                (folio, id_almacen, ubicacion_id, tipo_conteo, estatus, fecha_programada,
                 fecha_inicio, creado_por, observaciones)
                VALUES (:folio,:almacen,:ubicacion,:tipo,'en_conteo',:programada,NOW(),:usuario,:observaciones)");
            $stmt->execute(array(
                ":folio" => $folio,
                ":almacen" => $idAlmacen,
                ":ubicacion" => $ubicacionId ?: null,
                ":tipo" => $tipo,
                ":programada" => trim(isset($datos["fecha_programada"]) ? $datos["fecha_programada"] : "") ?: null,
                ":usuario" => intval($idUsuario) ?: null,
                ":observaciones" => trim(isset($datos["observaciones"]) ? $datos["observaciones"] : "")
            ));
            $idConteo = intval($db->lastInsertId());

            $sql = "INSERT INTO erp_inventario_conteos_detalle
                (id_conteo_inventario, id_existencia_inventario, codigo_existencia, id_producto, id_sku_erp,
                 id_almacen, ubicacion_id, ubicacion, lote, fecha_caducidad, cantidad_sistema,
                 costo_promedio, costo_diferencia, estatus)
                SELECT :conteo, e.id_existencia_inventario, e.codigo_existencia, e.id_producto, e.id_sku_erp,
                    e.id_almacen_clave, e.ubicacion_id, e.ubicacion, e.lote, e.fecha_caducidad, e.cantidad,
                    e.costo_promedio, 0, 'pendiente'
                FROM erp_inventario_existencias e
                WHERE e.id_almacen_clave=:almacen
                  AND (e.cantidad<>0 OR e.cantidad_disponible<>0 OR e.cantidad_apartada<>0)";
            $params = array(":conteo" => $idConteo, ":almacen" => $idAlmacen);
            if ($ubicacionId > 0) {
                $sql .= " AND e.ubicacion_clave=:ubicacion";
                $params[":ubicacion"] = $ubicacionId;
            }
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $partidas = $stmt->rowCount();
            if ($partidas <= 0) {
                throw new Exception("No hay existencias activas para generar conteo");
            }
            $db->commit();
            return $this->respuesta(false, "success", "Conteo fisico creado", array(
                "id_conteo_inventario" => $idConteo,
                "folio" => $folio,
                "partidas" => $partidas
            ));
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    public function consultarConteo($filtros = array()) {
        try {
            $db = $this->getConexion();
            $idConteo = intval(isset($filtros["id_conteo_inventario"]) ? $filtros["id_conteo_inventario"] : 0);
            if ($idConteo <= 0) {
                return $this->respuesta(true, "warning", "Conteo no valido");
            }
            $stmt = $db->prepare("SELECT c.*, a.almacen, ub.codigo_ubicacion, ub.nombre ubicacion
                FROM erp_inventario_conteos c
                LEFT JOIN erp_almacenes a ON a.id_almacen=c.id_almacen
                LEFT JOIN erp_almacen_ubicaciones ub ON ub.id_ubicacion=c.ubicacion_id
                WHERE c.id_conteo_inventario=:conteo");
            $stmt->execute(array(":conteo" => $idConteo));
            $conteo = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$conteo) {
                return $this->respuesta(true, "warning", "Conteo no encontrado");
            }
            $stmt = $db->prepare("SELECT d.*, s.sku, COALESCE(s.nombre, p.nombre) producto
                FROM erp_inventario_conteos_detalle d
                LEFT JOIN erp_catalogo_skus s ON s.id_sku=d.id_sku_erp
                LEFT JOIN erp_catalogo_productos p ON p.id_producto_erp=d.id_producto
                WHERE d.id_conteo_inventario=:conteo
                ORDER BY s.sku ASC, d.fecha_caducidad ASC, d.codigo_existencia ASC");
            $stmt->execute(array(":conteo" => $idConteo));
            return $this->respuesta(false, "success", "Conteo consultado", array(
                "conteo" => $conteo,
                "detalle" => $stmt->fetchAll(PDO::FETCH_ASSOC)
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    public function capturarConteo($datos, $idUsuario = 0) {
        $idConteo = intval(isset($datos["id_conteo_inventario"]) ? $datos["id_conteo_inventario"] : 0);
        $items = json_decode(isset($datos["items"]) ? $datos["items"] : "[]", true);
        if ($idConteo <= 0 || !is_array($items) || empty($items)) {
            return $this->respuesta(true, "warning", "Conteo e items son obligatorios");
        }
        $db = $this->getConexion();
        try {
            $db->beginTransaction();
            $stmt = $db->prepare("SELECT id_conteo_inventario, estatus FROM erp_inventario_conteos
                WHERE id_conteo_inventario=:conteo FOR UPDATE");
            $stmt->execute(array(":conteo" => $idConteo));
            $conteo = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$conteo || !in_array($conteo["estatus"], array("borrador", "en_conteo", "validado"), true)) {
                throw new Exception("El conteo no esta disponible para captura");
            }
            $actualizadas = 0;
            foreach ($items as $item) {
                $idDetalle = intval(isset($item["id_conteo_detalle"]) ? $item["id_conteo_detalle"] : 0);
                if ($idDetalle <= 0 || !array_key_exists("cantidad_fisica", $item)) {
                    continue;
                }
                $fisica = round(floatval($item["cantidad_fisica"]), 6);
                if ($fisica < 0) {
                    throw new Exception("La cantidad fisica no puede ser negativa");
                }
                $stmt = $db->prepare("UPDATE erp_inventario_conteos_detalle
                    SET cantidad_fisica=:fisica,
                        diferencia=ROUND(:fisica_calc-cantidad_sistema, 6),
                        costo_diferencia=ROUND((:fisica_costo-cantidad_sistema)*costo_promedio, 6),
                        motivo_diferencia=:motivo,
                        estatus='capturado',
                        contado_por=:usuario,
                        fecha_conteo=NOW(),
                        observaciones=:observaciones,
                        fecha_actualizacion=NOW()
                    WHERE id_conteo_detalle=:detalle AND id_conteo_inventario=:conteo");
                $stmt->execute(array(
                    ":fisica" => $fisica,
                    ":fisica_calc" => $fisica,
                    ":fisica_costo" => $fisica,
                    ":motivo" => trim(isset($item["motivo_diferencia"]) ? $item["motivo_diferencia"] : ""),
                    ":usuario" => intval($idUsuario) ?: null,
                    ":observaciones" => trim(isset($item["observaciones"]) ? $item["observaciones"] : ""),
                    ":detalle" => $idDetalle,
                    ":conteo" => $idConteo
                ));
                $actualizadas += $stmt->rowCount();
            }
            $db->prepare("UPDATE erp_inventario_conteos SET estatus='en_conteo', fecha_actualizacion=NOW()
                WHERE id_conteo_inventario=:conteo AND estatus='borrador'")
                ->execute(array(":conteo" => $idConteo));
            $db->commit();
            return $this->respuesta(false, "success", "Captura de conteo guardada", array(
                "id_conteo_inventario" => $idConteo,
                "partidas_actualizadas" => $actualizadas
            ));
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    public function previewCerrarConteo($filtros = array()) {
        try {
            $db = $this->getConexion();
            $idConteo = intval(isset($filtros["id_conteo_inventario"]) ? $filtros["id_conteo_inventario"] : 0);
            if ($idConteo <= 0) {
                return $this->respuesta(true, "warning", "Conteo no valido");
            }
            return $this->respuesta(false, "success", "Preview de cierre consultado", $this->resumenCierreConteo($db, $idConteo));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    public function cerrarConteo($datos, $idUsuario = 0) {
        $idConteo = intval(isset($datos["id_conteo_inventario"]) ? $datos["id_conteo_inventario"] : 0);
        if ($idConteo <= 0) {
            return $this->respuesta(true, "warning", "Conteo no valido");
        }

        $db = $this->getConexion();
        try {
            $db->beginTransaction();
            $stmt = $db->prepare("SELECT * FROM erp_inventario_conteos WHERE id_conteo_inventario=:conteo FOR UPDATE");
            $stmt->execute(array(":conteo" => $idConteo));
            $conteo = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$conteo) {
                throw new Exception("Conteo no encontrado");
            }
            if (!in_array($conteo["estatus"], array("en_conteo", "validado"), true)) {
                throw new Exception("El conteo no esta disponible para cierre");
            }

            $resumen = $this->resumenCierreConteo($db, $idConteo);
            if (intval($resumen["pendientes"]) > 0) {
                throw new Exception("No se puede cerrar: faltan partidas por capturar");
            }

            $stmt = $db->prepare("SELECT * FROM erp_inventario_conteos_detalle
                WHERE id_conteo_inventario=:conteo AND ABS(diferencia)>0.0001
                ORDER BY id_conteo_detalle FOR UPDATE");
            $stmt->execute(array(":conteo" => $idConteo));
            $diferencias = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $movimientos = 0;
            foreach ($diferencias as $detalle) {
                $stmtExistencia = $db->prepare("SELECT * FROM erp_inventario_existencias
                    WHERE id_existencia_inventario=:existencia FOR UPDATE");
                $stmtExistencia->execute(array(":existencia" => intval($detalle["id_existencia_inventario"])));
                $existencia = $stmtExistencia->fetch(PDO::FETCH_ASSOC);
                if (!$existencia) {
                    throw new Exception("Existencia no encontrada para " . $detalle["codigo_existencia"]);
                }
                if (floatval($existencia["cantidad_apartada"]) > 0.0001) {
                    throw new Exception("No se puede ajustar " . $existencia["codigo_existencia"] . " porque tiene cantidad apartada");
                }
                $diferencia = round(floatval($detalle["diferencia"]), 6);
                $tipo = $diferencia > 0 ? "entrada" : "salida";
                $cantidad = abs($diferencia);
                if ($tipo === "salida" && $cantidad > floatval($existencia["cantidad_disponible"]) + 0.0001) {
                    throw new Exception("La diferencia supera lo disponible en " . $existencia["codigo_existencia"]);
                }
                $motivo = trim($detalle["motivo_diferencia"] ?: ($tipo === "entrada" ? "sobrante_conteo" : "faltante_conteo"));
                $datosMovimiento = array(
                    "motivo_ajuste" => $motivo,
                    "observaciones" => trim("cierre_conteo:" . $conteo["folio"] . " | " . (isset($datos["observaciones"]) ? $datos["observaciones"] : ""))
                );
                $idMovimiento = $this->aplicarCambio($db, $existencia, $cantidad, $tipo, "conteo_fisico", $idConteo, $conteo["folio"], $datosMovimiento, $idUsuario);
                $db->prepare("UPDATE erp_inventario_conteos_detalle
                    SET estatus='ajustado', id_movimiento_inventario=:movimiento, fecha_actualizacion=NOW()
                    WHERE id_conteo_detalle=:detalle")
                    ->execute(array(":movimiento" => $idMovimiento, ":detalle" => intval($detalle["id_conteo_detalle"])));
                $movimientos++;
            }

            $db->prepare("UPDATE erp_inventario_conteos
                SET estatus='cerrado', fecha_cierre=NOW(), cerrado_por=:usuario, referencia_ajuste=:referencia,
                    observaciones=TRIM(CONCAT(COALESCE(observaciones,''), CASE WHEN COALESCE(observaciones,'')<>'' THEN ' | ' ELSE '' END, :observaciones)),
                    fecha_actualizacion=NOW()
                WHERE id_conteo_inventario=:conteo")
                ->execute(array(
                    ":usuario" => intval($idUsuario) ?: null,
                    ":referencia" => $conteo["folio"],
                    ":observaciones" => trim(isset($datos["observaciones"]) ? $datos["observaciones"] : "cierre sin observaciones"),
                    ":conteo" => $idConteo
                ));
            $db->commit();

            return $this->respuesta(false, "success", "Conteo cerrado", array(
                "id_conteo_inventario" => $idConteo,
                "folio" => $conteo["folio"],
                "movimientos" => $movimientos,
                "resumen" => $resumen
            ));
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    public function listarReservas($filtros = array()) {
        try {
            $db = $this->getConexion();
            $idAlmacen = intval(isset($filtros["id_almacen"]) ? $filtros["id_almacen"] : 0);
            $estatus = trim(isset($filtros["estatus"]) ? $filtros["estatus"] : "");
            $termino = trim(isset($filtros["q"]) ? $filtros["q"] : "");
            $sql = "SELECT r.id_reserva_inventario, r.folio, r.origen_tipo, r.origen_id, r.origen_detalle_id,
                    r.id_existencia_inventario, r.codigo_existencia, r.id_almacen, a.almacen,
                    r.id_sku_erp id_sku, s.sku, COALESCE(s.nombre, p.nombre) producto,
                    r.lote, r.fecha_caducidad, r.cantidad_reservada, r.cantidad_consumida,
                    r.cantidad_liberada,
                    (r.cantidad_reservada-r.cantidad_consumida-r.cantidad_liberada) cantidad_pendiente,
                    r.estatus, r.fecha_reserva, r.fecha_vencimiento, r.fecha_cierre, r.observaciones
                FROM erp_inventario_reservas r
                LEFT JOIN erp_catalogo_skus s ON s.id_sku=r.id_sku_erp
                LEFT JOIN erp_catalogo_productos p ON p.id_producto_erp=r.id_producto
                LEFT JOIN erp_almacenes a ON a.id_almacen=r.id_almacen
                WHERE (:almacen=0 OR r.id_almacen=:almacen_filtro)
                  AND (:estatus='' OR r.estatus=:estatus_filtro)
                  AND (:termino='' OR r.folio LIKE :buscar OR r.codigo_existencia LIKE :buscar OR s.sku LIKE :buscar OR s.nombre LIKE :buscar OR p.nombre LIKE :buscar OR r.origen_tipo LIKE :buscar)
                ORDER BY FIELD(r.estatus,'activa','vencida','liberada','consumida','cancelada'), r.id_reserva_inventario DESC
                LIMIT 500";
            $stmt = $db->prepare($sql);
            $stmt->execute(array(
                ":almacen" => $idAlmacen,
                ":almacen_filtro" => $idAlmacen,
                ":estatus" => $estatus,
                ":estatus_filtro" => $estatus,
                ":termino" => $termino,
                ":buscar" => "%" . $termino . "%"
            ));
            return $this->respuesta(false, "success", "Reservas consultadas", $stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    public function crearReserva($datos, $idUsuario = 0) {
        $idExistencia = intval(isset($datos["id_existencia_inventario"]) ? $datos["id_existencia_inventario"] : 0);
        $cantidad = round(floatval(isset($datos["cantidad"]) ? $datos["cantidad"] : 0), 6);
        $origenTipo = strtolower($this->clave(trim(isset($datos["origen_tipo"]) ? $datos["origen_tipo"] : "reserva_manual")));
        if ($origenTipo === "") {
            $origenTipo = "reserva_manual";
        }
        if ($idExistencia <= 0 || $cantidad <= 0) {
            return $this->respuesta(true, "warning", "Existencia y cantidad son obligatorias");
        }
        $db = $this->getConexion();
        try {
            $db->beginTransaction();
            $stmt = $db->prepare("SELECT * FROM erp_inventario_existencias WHERE id_existencia_inventario=:existencia FOR UPDATE");
            $stmt->execute(array(":existencia" => $idExistencia));
            $existencia = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$existencia) {
                throw new Exception("Existencia no encontrada");
            }
            if ($cantidad > floatval($existencia["cantidad_disponible"]) + 0.0001) {
                throw new Exception("La reserva supera lo disponible");
            }
            $folio = $this->folioReserva($db);
            $stmt = $db->prepare("INSERT INTO erp_inventario_reservas
                (folio, origen_tipo, origen_id, origen_detalle_id, id_existencia_inventario, codigo_existencia,
                 id_producto, id_sku_erp, id_almacen, ubicacion_id, lote, fecha_caducidad,
                 cantidad_reservada, estatus, fecha_vencimiento, creado_por, observaciones)
                VALUES (:folio,:origen_tipo,:origen_id,:origen_detalle_id,:existencia,:codigo,
                 :producto,:sku,:almacen,:ubicacion,:lote,:caducidad,
                 :cantidad,'activa',:vencimiento,:usuario,:observaciones)");
            $stmt->execute(array(
                ":folio" => $folio,
                ":origen_tipo" => $origenTipo,
                ":origen_id" => intval(isset($datos["origen_id"]) ? $datos["origen_id"] : 0) ?: null,
                ":origen_detalle_id" => intval(isset($datos["origen_detalle_id"]) ? $datos["origen_detalle_id"] : 0) ?: null,
                ":existencia" => $idExistencia,
                ":codigo" => $existencia["codigo_existencia"],
                ":producto" => intval($existencia["id_producto"]),
                ":sku" => intval($existencia["id_sku_erp"]),
                ":almacen" => intval($existencia["id_almacen_clave"]),
                ":ubicacion" => intval($existencia["ubicacion_id"]) ?: null,
                ":lote" => $existencia["lote"],
                ":caducidad" => $existencia["fecha_caducidad"],
                ":cantidad" => $cantidad,
                ":vencimiento" => trim(isset($datos["fecha_vencimiento"]) ? $datos["fecha_vencimiento"] : "") ?: null,
                ":usuario" => intval($idUsuario) ?: null,
                ":observaciones" => trim(isset($datos["observaciones"]) ? $datos["observaciones"] : "")
            ));
            $idReserva = intval($db->lastInsertId());
            $db->prepare("UPDATE erp_inventario_existencias
                SET cantidad_apartada=ROUND(cantidad_apartada+:cantidad,4),
                    cantidad_disponible=ROUND(cantidad_disponible-:cantidad_disponible,4),
                    fecha_actualizacion=NOW()
                WHERE id_existencia_inventario=:existencia")
                ->execute(array(
                    ":cantidad" => $cantidad,
                    ":cantidad_disponible" => $cantidad,
                    ":existencia" => $idExistencia
                ));
            $db->commit();
            return $this->respuesta(false, "success", "Reserva creada", array(
                "id_reserva_inventario" => $idReserva,
                "folio" => $folio,
                "codigo_existencia" => $existencia["codigo_existencia"],
                "cantidad_reservada" => $cantidad
            ));
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    public function liberarReserva($datos, $idUsuario = 0) {
        $idReserva = intval(isset($datos["id_reserva_inventario"]) ? $datos["id_reserva_inventario"] : 0);
        if ($idReserva <= 0) {
            return $this->respuesta(true, "warning", "Reserva no valida");
        }
        $db = $this->getConexion();
        try {
            $db->beginTransaction();
            $stmt = $db->prepare("SELECT * FROM erp_inventario_reservas WHERE id_reserva_inventario=:reserva FOR UPDATE");
            $stmt->execute(array(":reserva" => $idReserva));
            $reserva = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$reserva || $reserva["estatus"] !== "activa") {
                throw new Exception("La reserva no esta activa");
            }
            $pendiente = round(floatval($reserva["cantidad_reservada"]) - floatval($reserva["cantidad_consumida"]) - floatval($reserva["cantidad_liberada"]), 6);
            if ($pendiente <= 0) {
                throw new Exception("La reserva no tiene cantidad pendiente");
            }
            $stmt = $db->prepare("SELECT * FROM erp_inventario_existencias WHERE id_existencia_inventario=:existencia FOR UPDATE");
            $stmt->execute(array(":existencia" => intval($reserva["id_existencia_inventario"])));
            $existencia = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$existencia) {
                throw new Exception("Existencia no encontrada para liberar reserva");
            }
            if ($pendiente > floatval($existencia["cantidad_apartada"]) + 0.0001) {
                throw new Exception("La reserva supera la cantidad apartada de la existencia");
            }
            $db->prepare("UPDATE erp_inventario_existencias
                SET cantidad_apartada=ROUND(cantidad_apartada-:cantidad,4),
                    cantidad_disponible=ROUND(cantidad_disponible+:cantidad_disponible,4),
                    fecha_actualizacion=NOW()
                WHERE id_existencia_inventario=:existencia")
                ->execute(array(
                    ":cantidad" => $pendiente,
                    ":cantidad_disponible" => $pendiente,
                    ":existencia" => intval($reserva["id_existencia_inventario"])
                ));
            $db->prepare("UPDATE erp_inventario_reservas
                SET cantidad_liberada=ROUND(cantidad_liberada+:cantidad,6),
                    estatus='liberada', fecha_cierre=NOW(), cerrado_por=:usuario,
                    observaciones=TRIM(CONCAT(COALESCE(observaciones,''), CASE WHEN COALESCE(observaciones,'')<>'' THEN ' | ' ELSE '' END, :observaciones)),
                    fecha_actualizacion=NOW()
                WHERE id_reserva_inventario=:reserva")
                ->execute(array(
                    ":cantidad" => $pendiente,
                    ":usuario" => intval($idUsuario) ?: null,
                    ":observaciones" => trim(isset($datos["observaciones"]) ? $datos["observaciones"] : "liberacion de reserva"),
                    ":reserva" => $idReserva
                ));
            $db->commit();
            return $this->respuesta(false, "success", "Reserva liberada", array(
                "id_reserva_inventario" => $idReserva,
                "folio" => $reserva["folio"],
                "cantidad_liberada" => $pendiente
            ));
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-07-05.
     * Proposito: consumir una reserva activa cuando un documento comercial entrega mercancia.
     * Impacto: baja cantidad fisica y cantidad apartada, inserta kardex de salida y cierra la reserva como consumida.
     * Contrato: escritura real transaccional; no crea venta ni pagos, solo consume reserva existente.
     */
    public function consumirReserva($datos, $idUsuario = 0) {
        $idReserva = intval(isset($datos["id_reserva_inventario"]) ? $datos["id_reserva_inventario"] : 0);
        $origenTipo = trim(isset($datos["origen_tipo"]) ? $datos["origen_tipo"] : "pedido_entrega");
        $origenId = intval(isset($datos["origen_id"]) ? $datos["origen_id"] : 0);
        $referencia = trim(isset($datos["referencia"]) ? $datos["referencia"] : "");
        if ($idReserva <= 0) {
            return $this->respuesta(true, "warning", "Reserva no valida");
        }
        $db = $this->getConexion();
        try {
            $db->beginTransaction();
            $stmt = $db->prepare("SELECT * FROM erp_inventario_reservas WHERE id_reserva_inventario=:reserva FOR UPDATE");
            $stmt->execute(array(":reserva" => $idReserva));
            $reserva = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$reserva || $reserva["estatus"] !== "activa") {
                throw new Exception("La reserva no esta activa");
            }
            $pendiente = round(floatval($reserva["cantidad_reservada"]) - floatval($reserva["cantidad_consumida"]) - floatval($reserva["cantidad_liberada"]), 6);
            if ($pendiente <= 0) {
                throw new Exception("La reserva no tiene cantidad pendiente");
            }
            $stmt = $db->prepare("SELECT * FROM erp_inventario_existencias WHERE id_existencia_inventario=:existencia FOR UPDATE");
            $stmt->execute(array(":existencia" => intval($reserva["id_existencia_inventario"])));
            $existencia = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$existencia) {
                throw new Exception("Existencia no encontrada para consumir reserva");
            }
            if ($pendiente > floatval($existencia["cantidad_apartada"]) + 0.0001 || $pendiente > floatval($existencia["cantidad"]) + 0.0001) {
                throw new Exception("La reserva supera la existencia apartada disponible para consumo");
            }

            $anterior = round(floatval($existencia["cantidad"]), 6);
            $nueva = round($anterior - $pendiente, 6);
            $apartadaNueva = round(floatval($existencia["cantidad_apartada"]) - $pendiente, 6);
            $db->prepare("UPDATE erp_inventario_existencias
                SET cantidad=:cantidad, cantidad_apartada=:apartada,
                    estatus_existencia=:estatus, fecha_actualizacion=NOW()
                WHERE id_existencia_inventario=:existencia")
                ->execute(array(
                    ":cantidad" => $nueva,
                    ":apartada" => $apartadaNueva,
                    ":estatus" => $nueva > 0.0001 ? "disponible" : "agotada",
                    ":existencia" => intval($existencia["id_existencia_inventario"])
                ));

            $costo = round(floatval($existencia["costo_promedio"]), 6);
            $stmt = $db->prepare("INSERT INTO erp_inventario_movimientos
                (id_producto,id_sku_erp,id_almacen,tipo_movimiento,origen_tipo,origen_id,id_existencia_inventario,
                 codigo_existencia,lote,fecha_caducidad,ubicacion_id,ubicacion,cantidad,costo_unitario,costo_total,
                 existencia_anterior,existencia_nueva,referencia,observaciones)
                VALUES (:producto,:sku,:almacen,'salida',:origen_tipo,:origen_id,:existencia,
                 :codigo,:lote,:caducidad,:ubicacion_id,:ubicacion,:cantidad,:costo,:total,
                 :anterior,:nueva,:referencia,:observaciones)");
            $stmt->execute(array(
                ":producto" => intval($existencia["id_producto"]),
                ":sku" => intval($existencia["id_sku_erp"]),
                ":almacen" => intval($existencia["id_almacen_clave"]),
                ":origen_tipo" => $origenTipo,
                ":origen_id" => $origenId ?: intval($reserva["origen_id"]),
                ":existencia" => intval($existencia["id_existencia_inventario"]),
                ":codigo" => $existencia["codigo_existencia"],
                ":lote" => $existencia["lote"],
                ":caducidad" => $existencia["fecha_caducidad"],
                ":ubicacion_id" => $existencia["ubicacion_id"],
                ":ubicacion" => $existencia["ubicacion"],
                ":cantidad" => $pendiente,
                ":costo" => $costo,
                ":total" => round($pendiente * $costo, 6),
                ":anterior" => $anterior,
                ":nueva" => $nueva,
                ":referencia" => $referencia !== "" ? $referencia : $reserva["folio"],
                ":observaciones" => trim(isset($datos["observaciones"]) ? $datos["observaciones"] : "consumo de reserva") . " | usuario:" . intval($idUsuario)
            ));
            $idMovimiento = intval($db->lastInsertId());
            $db->prepare("UPDATE erp_inventario_existencias SET ultimo_movimiento_id=:movimiento WHERE id_existencia_inventario=:existencia")
                ->execute(array(":movimiento" => $idMovimiento, ":existencia" => intval($existencia["id_existencia_inventario"])));
            $db->prepare("UPDATE erp_inventario_reservas
                SET cantidad_consumida=ROUND(cantidad_consumida+:cantidad,6),
                    estatus='consumida', fecha_cierre=NOW(), cerrado_por=:usuario,
                    observaciones=TRIM(CONCAT(COALESCE(observaciones,''), CASE WHEN COALESCE(observaciones,'')<>'' THEN ' | ' ELSE '' END, :observaciones)),
                    fecha_actualizacion=NOW()
                WHERE id_reserva_inventario=:reserva")
                ->execute(array(
                    ":cantidad" => $pendiente,
                    ":usuario" => intval($idUsuario) ?: null,
                    ":observaciones" => trim(isset($datos["observaciones"]) ? $datos["observaciones"] : "consumida por entrega"),
                    ":reserva" => $idReserva
                ));
            $db->commit();
            return $this->respuesta(false, "success", "Reserva consumida", array(
                "id_reserva_inventario" => $idReserva,
                "folio" => $reserva["folio"],
                "id_movimiento_inventario" => $idMovimiento,
                "cantidad_consumida" => $pendiente
            ));
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    private function resumenCierreConteo($db, $idConteo) {
        $stmt = $db->prepare("SELECT c.id_conteo_inventario, c.folio, c.estatus,
                COUNT(d.id_conteo_detalle) partidas,
                SUM(CASE WHEN d.cantidad_fisica IS NULL THEN 1 ELSE 0 END) pendientes,
                SUM(CASE WHEN d.cantidad_fisica IS NOT NULL THEN 1 ELSE 0 END) capturadas,
                SUM(CASE WHEN ABS(d.diferencia)>0.0001 THEN 1 ELSE 0 END) diferencias,
                COALESCE(SUM(CASE WHEN d.diferencia>0.0001 THEN d.diferencia ELSE 0 END),0) sobrante,
                COALESCE(SUM(CASE WHEN d.diferencia<-0.0001 THEN ABS(d.diferencia) ELSE 0 END),0) faltante,
                COALESCE(SUM(d.costo_diferencia),0) costo_diferencia
            FROM erp_inventario_conteos c
            LEFT JOIN erp_inventario_conteos_detalle d ON d.id_conteo_inventario=c.id_conteo_inventario
            WHERE c.id_conteo_inventario=:conteo
            GROUP BY c.id_conteo_inventario, c.folio, c.estatus");
        $stmt->execute(array(":conteo" => $idConteo));
        $resumen = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$resumen) {
            throw new Exception("Conteo no encontrado");
        }
        return $resumen;
    }

    private function consultarMovimientosTrazabilidad($db, $clave, $buscar, $idsExistencia) {
        $params = array(
            ":clave" => $clave,
            ":clave_sku" => $clave,
            ":buscar" => $buscar,
            ":clave_unidad" => $clave,
            ":clave_etiqueta" => $clave,
            ":clave_serie" => $clave
        );
        $condicionExistencias = $this->condicionIdsTrazabilidad("m.id_existencia_inventario", $idsExistencia, "mov_exi", $params);

        $sql = "SELECT DISTINCT m.id_movimiento_inventario, m.fecha_registro, m.tipo_movimiento, m.origen_tipo,
                m.referencia, m.id_almacen, a.almacen, m.id_sku_erp id_sku, s.sku,
                COALESCE(s.nombre, p.nombre) producto, m.codigo_existencia, m.lote, m.fecha_caducidad,
                m.ubicacion, m.cantidad, m.costo_unitario, m.costo_total, m.existencia_anterior,
                m.existencia_nueva, m.observaciones
            FROM erp_inventario_movimientos m
            LEFT JOIN erp_catalogo_skus s ON s.id_sku=m.id_sku_erp
            LEFT JOIN erp_catalogo_productos p ON p.id_producto_erp=m.id_producto
            LEFT JOIN erp_almacenes a ON a.id_almacen=m.id_almacen
            LEFT JOIN erp_inventario_unidades u ON u.id_existencia_inventario=m.id_existencia_inventario
            LEFT JOIN erp_almacen_preparaciones prep ON u.origen_tipo='preparacion_presentacion' AND prep.id_preparacion_almacen=u.origen_id
            LEFT JOIN erp_almacen_recepciones rec ON rec.id_recepcion_almacen=u.id_recepcion_almacen
            WHERE m.codigo_existencia=:clave
               OR s.sku=:clave_sku
               OR m.referencia LIKE :buscar
               OR m.lote LIKE :buscar
               OR prep.folio LIKE :buscar
               OR rec.folio LIKE :buscar
               OR u.codigo_unico=:clave_unidad
               OR u.codigo_etiqueta_interna=:clave_etiqueta
               OR u.serie_fabricante=:clave_serie
               " . ($condicionExistencias !== "" ? "OR " . $condicionExistencias : "") . "
            ORDER BY m.id_movimiento_inventario DESC
            LIMIT 100";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function diagnosticoExistencias($db, $idAlmacen, $condicion, $tipo) {
        $sql = "SELECT e.id_existencia_inventario, e.codigo_existencia, e.id_almacen_clave id_almacen,
                a.almacen, s.sku, COALESCE(s.nombre, p.nombre) producto,
                e.lote, e.fecha_caducidad, e.ubicacion, e.cantidad, e.cantidad_apartada,
                e.cantidad_disponible, e.estatus_existencia
            FROM erp_inventario_existencias e
            INNER JOIN erp_catalogo_skus s ON s.id_sku=e.id_sku_erp
            INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
            LEFT JOIN erp_catalogo_sku_reglas_inventario r ON r.id_sku=e.id_sku_erp
            LEFT JOIN erp_almacenes a ON a.id_almacen=e.id_almacen_clave
            WHERE (:almacen=0 OR e.id_almacen_clave=:almacen_filtro)
              AND " . $condicion . "
            ORDER BY e.fecha_caducidad IS NULL, e.fecha_caducidad ASC, s.sku ASC
            LIMIT 25";
        $stmt = $db->prepare($sql);
        $stmt->execute(array(":almacen" => $idAlmacen, ":almacen_filtro" => $idAlmacen));
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($items as &$item) {
            $item["tipo_diagnostico"] = $tipo;
        }
        return $items;
    }

    private function diagnosticoEtiquetasPendientes($db, $idAlmacen) {
        $sql = "SELECT u.estado_etiqueta, COUNT(*) total,
                MIN(COALESCE(u.codigo_etiqueta_interna, u.codigo_unico)) primer_codigo,
                MAX(COALESCE(u.codigo_etiqueta_interna, u.codigo_unico)) ultimo_codigo
            FROM erp_inventario_unidades u
            WHERE (:almacen=0 OR u.id_almacen=:almacen_filtro)
              AND u.estatus <> 'cancelada'
              AND u.estado_etiqueta IN ('pendiente_impresion', 'impresa', 'reimpresa')
            GROUP BY u.estado_etiqueta
            ORDER BY FIELD(u.estado_etiqueta, 'pendiente_impresion', 'impresa', 'reimpresa')";
        $stmt = $db->prepare($sql);
        $stmt->execute(array(":almacen" => $idAlmacen, ":almacen_filtro" => $idAlmacen));
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function diagnosticoUnidadesVsExistencia($db, $idAlmacen) {
        $sql = "SELECT e.id_existencia_inventario, e.codigo_existencia, e.id_almacen_clave id_almacen,
                a.almacen, s.sku, COALESCE(s.nombre, p.nombre) producto,
                e.lote, e.fecha_caducidad, e.ubicacion, e.cantidad,
                COUNT(u.id_inventario_unidad) unidades_disponibles,
                COALESCE(SUM(CASE WHEN u.estatus='disponible' THEN u.cantidad_base_disponible ELSE 0 END), 0) contenido_unidades_disponible
            FROM erp_inventario_existencias e
            INNER JOIN erp_catalogo_skus s ON s.id_sku=e.id_sku_erp
            INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
            INNER JOIN erp_catalogo_sku_reglas_inventario r ON r.id_sku=e.id_sku_erp AND r.generar_etiqueta_interna=1
            LEFT JOIN erp_almacenes a ON a.id_almacen=e.id_almacen_clave
            LEFT JOIN erp_inventario_unidades u ON u.id_existencia_inventario=e.id_existencia_inventario AND u.estatus='disponible'
            WHERE (:almacen=0 OR e.id_almacen_clave=:almacen_filtro)
            GROUP BY e.id_existencia_inventario, e.codigo_existencia, e.id_almacen_clave,
                a.almacen, s.sku, producto, e.lote, e.fecha_caducidad, e.ubicacion, e.cantidad
            HAVING ABS(e.cantidad - contenido_unidades_disponible) > 0.0001
            ORDER BY s.sku ASC, e.fecha_caducidad ASC
            LIMIT 25";
        $stmt = $db->prepare($sql);
        $stmt->execute(array(":almacen" => $idAlmacen, ":almacen_filtro" => $idAlmacen));
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function diagnosticoReservasVsApartado($db, $idAlmacen) {
        $sql = "SELECT e.id_existencia_inventario, e.codigo_existencia, e.id_almacen_clave id_almacen,
                a.almacen, s.sku, COALESCE(s.nombre, p.nombre) producto,
                e.lote, e.fecha_caducidad, e.ubicacion, e.cantidad_apartada,
                COALESCE(res.reserva_pendiente, 0) reserva_pendiente,
                ROUND(e.cantidad_apartada-COALESCE(res.reserva_pendiente, 0), 6) diferencia_apartado
            FROM erp_inventario_existencias e
            INNER JOIN erp_catalogo_skus s ON s.id_sku=e.id_sku_erp
            INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
            LEFT JOIN erp_almacenes a ON a.id_almacen=e.id_almacen_clave
            LEFT JOIN (
                SELECT id_existencia_inventario,
                    SUM(cantidad_reservada-cantidad_consumida-cantidad_liberada) reserva_pendiente
                FROM erp_inventario_reservas
                WHERE estatus='activa'
                GROUP BY id_existencia_inventario
            ) res ON res.id_existencia_inventario=e.id_existencia_inventario
            WHERE (:almacen=0 OR e.id_almacen_clave=:almacen_filtro)
              AND ABS(e.cantidad_apartada-COALESCE(res.reserva_pendiente, 0)) > 0.0001
            ORDER BY ABS(e.cantidad_apartada-COALESCE(res.reserva_pendiente, 0)) DESC
            LIMIT 25";
        $stmt = $db->prepare($sql);
        $stmt->execute(array(":almacen" => $idAlmacen, ":almacen_filtro" => $idAlmacen));
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function diagnosticoReservasVencidas($db, $idAlmacen) {
        $sql = "SELECT r.id_reserva_inventario, r.folio, r.codigo_existencia, r.id_almacen,
                a.almacen, s.sku, COALESCE(s.nombre, p.nombre) producto,
                r.lote, r.fecha_caducidad, r.fecha_vencimiento,
                (r.cantidad_reservada-r.cantidad_consumida-r.cantidad_liberada) reserva_pendiente,
                r.estatus
            FROM erp_inventario_reservas r
            LEFT JOIN erp_catalogo_skus s ON s.id_sku=r.id_sku_erp
            LEFT JOIN erp_catalogo_productos p ON p.id_producto_erp=r.id_producto
            LEFT JOIN erp_almacenes a ON a.id_almacen=r.id_almacen
            WHERE (:almacen=0 OR r.id_almacen=:almacen_filtro)
              AND r.estatus='activa'
              AND r.fecha_vencimiento IS NOT NULL
              AND r.fecha_vencimiento < NOW()
              AND (r.cantidad_reservada-r.cantidad_consumida-r.cantidad_liberada) > 0.0001
            ORDER BY r.fecha_vencimiento ASC
            LIMIT 25";
        $stmt = $db->prepare($sql);
        $stmt->execute(array(":almacen" => $idAlmacen, ":almacen_filtro" => $idAlmacen));
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function diagnosticoReservasEstatus($db, $idAlmacen) {
        $sql = "SELECT r.id_reserva_inventario, r.folio, r.codigo_existencia, r.id_almacen,
                a.almacen, s.sku, COALESCE(s.nombre, p.nombre) producto,
                r.lote, r.fecha_caducidad, r.fecha_vencimiento,
                r.cantidad_reservada, r.cantidad_consumida, r.cantidad_liberada,
                (r.cantidad_reservada-r.cantidad_consumida-r.cantidad_liberada) reserva_pendiente,
                r.estatus
            FROM erp_inventario_reservas r
            LEFT JOIN erp_catalogo_skus s ON s.id_sku=r.id_sku_erp
            LEFT JOIN erp_catalogo_productos p ON p.id_producto_erp=r.id_producto
            LEFT JOIN erp_almacenes a ON a.id_almacen=r.id_almacen
            WHERE (:almacen=0 OR r.id_almacen=:almacen_filtro)
              AND (
                (r.estatus='activa' AND (r.cantidad_reservada-r.cantidad_consumida-r.cantidad_liberada) <= 0.0001)
                OR (r.estatus<>'activa' AND (r.cantidad_reservada-r.cantidad_consumida-r.cantidad_liberada) > 0.0001)
                OR (r.cantidad_reservada-r.cantidad_consumida-r.cantidad_liberada) < -0.0001
              )
            ORDER BY r.id_reserva_inventario DESC
            LIMIT 25";
        $stmt = $db->prepare($sql);
        $stmt->execute(array(":almacen" => $idAlmacen, ":almacen_filtro" => $idAlmacen));
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function agregarHallazgoDiagnostico(&$hallazgos, $id, $severidad, $titulo, $items) {
        if (empty($items)) {
            return;
        }
        $total = 0;
        foreach ($items as $item) {
            $total += isset($item["total"]) ? intval($item["total"]) : 1;
        }
        $hallazgos[] = array(
            "id" => $id,
            "severidad" => $severidad,
            "titulo" => $titulo,
            "total" => $total,
            "items" => $items
        );
    }

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-06-25
     * Proposito: consulta unidades fisicas relacionadas con una busqueda de trazabilidad.
     * Impacto: Inventario/Almacen; mantiene visible contenido y estado fisico al rastrear etiquetas.
     * Contrato: no modifica datos; devuelve unidades por clave directa, folio relacionado o existencia asociada.
     */
    private function consultarUnidadesTrazabilidad($db, $clave, $buscar, $idsExistencia) {
        $params = array(
            ":clave" => $clave,
            ":clave_sku" => $clave,
            ":buscar" => $buscar,
            ":clave_unidad" => $clave,
            ":clave_etiqueta" => $clave,
            ":clave_serie" => $clave
        );
        $condicionExistencias = $this->condicionIdsTrazabilidad("u.id_existencia_inventario", $idsExistencia, "uni_exi", $params);

        $sql = "SELECT DISTINCT u.id_inventario_unidad, u.codigo_unico, u.tipo_identidad, u.serie_fabricante,
                u.codigo_etiqueta_interna, u.estado_etiqueta, u.estatus, u.fecha_impresion, u.fecha_etiquetado,
                u.cantidad_base_original, u.cantidad_base_disponible, u.unidad_base, u.estado_fisico,
                u.id_almacen, a.almacen, u.id_sku_erp id_sku, s.sku, COALESCE(s.nombre, p.nombre) producto,
                u.lote, u.fecha_caducidad, u.ubicacion_id, COALESCE(ub.nombre, u.ubicacion_id) ubicacion,
                u.origen_tipo, u.origen_id, u.origen_detalle_id,
                COALESCE(r.folio, prep.folio, mi.referencia) folio_recepcion,
                CASE
                    WHEN prep.folio IS NOT NULL THEN 'Preparacion/Empaque'
                    WHEN mi.referencia IS NOT NULL THEN 'Inventario inicial'
                    ELSE r.folio_orden_compra
                END folio_orden_compra
            FROM erp_inventario_unidades u
            LEFT JOIN erp_catalogo_skus s ON s.id_sku=u.id_sku_erp
            LEFT JOIN erp_catalogo_productos p ON p.id_producto_erp=COALESCE(s.id_producto_erp, u.id_producto)
            LEFT JOIN erp_almacenes a ON a.id_almacen=u.id_almacen
            LEFT JOIN erp_almacen_ubicaciones ub ON ub.id_ubicacion=u.ubicacion_id
            LEFT JOIN erp_almacen_recepciones r ON r.id_recepcion_almacen=u.id_recepcion_almacen
            LEFT JOIN erp_almacen_preparaciones prep ON u.origen_tipo='preparacion_presentacion' AND prep.id_preparacion_almacen=u.origen_id
            LEFT JOIN erp_inventario_movimientos mi ON u.origen_tipo='inventario_inicial' AND mi.id_movimiento_inventario=u.origen_id
            WHERE u.codigo_unico=:clave_unidad
               OR u.codigo_etiqueta_interna=:clave_etiqueta
               OR u.serie_fabricante=:clave_serie
               OR s.sku=:clave_sku
               OR r.folio LIKE :buscar
               OR prep.folio LIKE :buscar
               OR mi.referencia LIKE :buscar
               OR u.lote LIKE :buscar
               " . ($condicionExistencias !== "" ? "OR " . $condicionExistencias : "") . "
            ORDER BY u.id_inventario_unidad DESC
            LIMIT 100";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function condicionIdsTrazabilidad($campo, $ids, $prefijo, &$params) {
        $ids = array_values(array_unique(array_filter(array_map("intval", $ids))));
        if (empty($ids)) {
            return "";
        }
        $placeholders = array();
        foreach ($ids as $indice => $id) {
            $nombre = ":" . $prefijo . "_" . $indice;
            $placeholders[] = $nombre;
            $params[$nombre] = $id;
        }
        return $campo . " IN (" . implode(",", $placeholders) . ")";
    }

    public function marcarEtiquetaImpresa($datos, $idUsuario = 0) {
        return $this->actualizarEstadoEtiqueta($datos, $idUsuario, "impresa");
    }

    public function marcarEtiquetasImpresas($datos, $idUsuario = 0) {
        $ids = isset($datos["ids"]) ? $datos["ids"] : array();
        if (is_string($ids)) {
            $ids = json_decode($ids, true);
        }
        if (!is_array($ids)) {
            return $this->respuesta(true, "warning", "Seleccion de etiquetas no valida");
        }
        $ids = array_values(array_unique(array_filter(array_map("intval", $ids))));
        if (empty($ids)) {
            return $this->respuesta(true, "warning", "Selecciona al menos una etiqueta");
        }

        $db = $this->getConexion();
        try {
            $db->beginTransaction();
            $placeholders = implode(",", array_fill(0, count($ids), "?"));
            $stmt = $db->prepare("SELECT id_inventario_unidad, codigo_unico, codigo_etiqueta_interna, estado_etiqueta
                FROM erp_inventario_unidades WHERE id_inventario_unidad IN ($placeholders) FOR UPDATE");
            $stmt->execute($ids);
            $unidades = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (count($unidades) !== count($ids)) {
                throw new Exception("Una o mas etiquetas no fueron encontradas");
            }

            foreach ($unidades as $unidad) {
                if (!in_array(trim($unidad["estado_etiqueta"]), array("pendiente_impresion", "reimpresa"), true)) {
                    $codigo = $unidad["codigo_etiqueta_interna"] ?: $unidad["codigo_unico"];
                    throw new Exception("La etiqueta " . $codigo . " no esta pendiente de impresion");
                }
            }

            $params = array(intval($idUsuario) ?: null);
            $params = array_merge($params, $ids);
            $stmt = $db->prepare("UPDATE erp_inventario_unidades
                SET estado_etiqueta='impresa', fecha_impresion=NOW(), impreso_por=?, fecha_actualizacion=NOW()
                WHERE id_inventario_unidad IN ($placeholders)");
            $stmt->execute($params);
            $db->commit();

            return $this->respuesta(false, "success", "Etiquetas marcadas como impresas", array(
                "ids" => $ids,
                "total" => count($ids)
            ));
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    public function marcarEtiquetaPegada($datos, $idUsuario = 0) {
        return $this->actualizarEstadoEtiqueta($datos, $idUsuario, "pegada");
    }

    public function marcarEtiquetasPegadas($datos, $idUsuario = 0) {
        return $this->actualizarEstadoEtiquetas($datos, $idUsuario, "pegada");
    }

    public function buscarSkus($termino, $idAlmacen = 0) {
        try {
            $db = $this->getConexion();
            $termino = trim((string) $termino);
            if (strlen($termino) < 2) {
                return $this->respuesta(false, "success", "Escribe al menos dos caracteres", array());
            }
            $sql = "SELECT s.id_sku, s.sku, s.nombre nombre_sku, s.costo_referencia,
                s.factor_unidad_base,
                p.id_producto_erp, p.codigo_producto, p.nombre producto,
                COALESCE(r.permite_venta_fraccionaria, 0) permite_venta_fraccionaria,
                COALESCE(r.precision_decimal, 0) precision_decimal,
                COALESCE(r.incremento_minimo_venta, 1.000000) incremento_minimo_venta,
                COALESCE(NULLIF(r.unidad_venta_label, ''), ub.abreviatura, ub.codigo, '') unidad_venta_label,
                COALESCE(ub.abreviatura, ub.codigo, '') unidad_base_label,
                COALESCE(sp.factor_conversion, s.factor_unidad_base, 1.000000) factor_conversion_compra,
                COALESCE(uc.abreviatura, uc.codigo, '') unidad_compra_label,
                COALESCE(r.generar_etiqueta_interna, 0) generar_etiqueta_interna,
                COALESCE(r.requiere_lote, 0) requiere_lote,
                COALESCE(r.requiere_caducidad, 0) requiere_caducidad,
                COALESCE(SUM(CASE WHEN e.id_almacen_clave=:almacen OR :almacen_todos=0 THEN e.cantidad_disponible ELSE 0 END), 0) existencia_disponible
                FROM erp_catalogo_skus s
                INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
                LEFT JOIN erp_catalogo_sku_reglas_inventario r ON r.id_sku=s.id_sku
                LEFT JOIN erp_catalogo_unidades ub ON ub.id_unidad=s.id_unidad_base
                LEFT JOIN erp_catalogo_sku_proveedores sp ON sp.id_sku=s.id_sku AND sp.estatus='activo' AND sp.es_preferido=1
                LEFT JOIN erp_catalogo_unidades uc ON uc.id_unidad=sp.id_unidad_compra
                LEFT JOIN erp_inventario_existencias e ON e.id_sku_erp=s.id_sku
                WHERE s.estatus='activo' AND p.estatus='activo'
                  AND (s.sku LIKE :termino OR s.nombre LIKE :termino OR p.nombre LIKE :termino OR p.codigo_producto LIKE :termino)
                GROUP BY s.id_sku, s.sku, s.nombre, s.costo_referencia, s.factor_unidad_base, p.id_producto_erp, p.codigo_producto, p.nombre,
                    r.permite_venta_fraccionaria, r.precision_decimal, r.incremento_minimo_venta, r.unidad_venta_label,
                    r.generar_etiqueta_interna, r.requiere_lote, r.requiere_caducidad, ub.abreviatura, ub.codigo,
                    sp.factor_conversion, uc.abreviatura, uc.codigo
                ORDER BY p.nombre, s.sku LIMIT 50";
            $stmt = $db->prepare($sql);
            $stmt->execute(array(":almacen" => intval($idAlmacen), ":almacen_todos" => intval($idAlmacen), ":termino" => "%" . $termino . "%"));
            return $this->respuesta(false, "success", "SKU ERP consultados", $stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    public function aplicarAjuste($datos, $idUsuario = 0) {
        $idAlmacen = intval(isset($datos["id_almacen"]) ? $datos["id_almacen"] : 0);
        $tipo = isset($datos["tipo_ajuste"]) ? trim($datos["tipo_ajuste"]) : "";
        $items = json_decode(isset($datos["items"]) ? $datos["items"] : "[]", true);
        if ($idAlmacen <= 0 || !in_array($tipo, array("entrada", "salida"), true) || !is_array($items) || empty($items)) {
            return $this->respuesta(true, "warning", "Almacen, tipo e items son obligatorios");
        }
        $db = $this->getConexion();
        try {
            $db->beginTransaction();
            $this->validarAlmacen($db, $idAlmacen);
            $documentoOperacion = $this->documentoOperacionAjuste($datos, $tipo);
            $motivoAjuste = $this->motivoAjuste($datos, $tipo, $documentoOperacion);
            $referencia = $this->referenciaMovimiento($datos, $documentoOperacion === "inventario_inicial" ? "INV-INICIAL" : "AJU", $documentoOperacion === "inventario_inicial");
            $movimientos = 0;
            $etiquetasGeneradas = 0;
            $indiceItem = 0;
            foreach ($items as $item) {
                $indiceItem++;
                $sku = $this->consultarSku($db, intval(isset($item["id_sku"]) ? $item["id_sku"] : 0));
                $cantidad = $this->cantidadBaseAjuste($sku, $item, $documentoOperacion);
                if ($cantidad <= 0) {
                    throw new Exception("Todas las cantidades deben ser mayores a cero");
                }
                $this->validarReglasItem($sku, $item, $cantidad, $documentoOperacion);
                if ($tipo === "entrada") {
                    $existencia = $this->obtenerOCrearExistencia($db, $sku, $idAlmacen, $item);
                    $idMovimiento = $this->aplicarCambio($db, $existencia, $cantidad, "entrada", $documentoOperacion, 0, $referencia, $datos, $idUsuario);
                    if ($documentoOperacion === "inventario_inicial") {
                        $etiquetasGeneradas += $this->generarUnidadesInventarioInicial($db, $sku, $existencia, $cantidad, $item, $referencia, $idMovimiento, $indiceItem, $datos);
                    }
                    $movimientos++;
                    continue;
                }
                $pendiente = $cantidad;
                foreach ($this->existenciasDisponibles($db, intval($sku["id_sku"]), $idAlmacen, $item) as $existencia) {
                    if ($pendiente <= 0) {
                        break;
                    }
                    $retirar = min($pendiente, floatval($existencia["cantidad_disponible"]));
                    $this->aplicarCambio($db, $existencia, $retirar, "salida", "ajuste", 0, $referencia, $datos, $idUsuario);
                    $pendiente = round($pendiente - $retirar, 4);
                    $movimientos++;
                }
                if ($pendiente > 0.0001) {
                    throw new Exception("Existencia insuficiente para el SKU " . $sku["sku"]);
                }
            }
            $db->commit();
            return $this->respuesta(false, "success", $documentoOperacion === "inventario_inicial" ? "Inventario inicial ERP aplicado" : "Ajuste ERP aplicado", array(
                "referencia" => $referencia,
                "movimientos" => $movimientos,
                "etiquetas_generadas" => $etiquetasGeneradas,
                "origen_tipo" => $documentoOperacion,
                "motivo_ajuste" => $motivoAjuste
            ));
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    public function aplicarTraspaso($datos, $idUsuario = 0) {
        $origen = intval(isset($datos["id_almacen_origen"]) ? $datos["id_almacen_origen"] : 0);
        $destino = intval(isset($datos["id_almacen_destino"]) ? $datos["id_almacen_destino"] : 0);
        $items = json_decode(isset($datos["items"]) ? $datos["items"] : "[]", true);
        if ($origen <= 0 || $destino <= 0 || $origen === $destino || !is_array($items) || empty($items)) {
            return $this->respuesta(true, "warning", "Selecciona almacenes diferentes y agrega items");
        }
        $db = $this->getConexion();
        try {
            $db->beginTransaction();
            $this->validarAlmacen($db, $origen);
            $this->validarAlmacen($db, $destino);
            $referencia = $this->referenciaMovimiento($datos, "TRA");
            $movimientos = 0;
            foreach ($items as $item) {
                $cantidad = round(floatval(isset($item["cantidad"]) ? $item["cantidad"] : 0), 4);
                $sku = $this->consultarSku($db, intval(isset($item["id_sku"]) ? $item["id_sku"] : 0));
                if ($cantidad <= 0) {
                    throw new Exception("Todas las cantidades deben ser mayores a cero");
                }
                $pendiente = $cantidad;
                foreach ($this->existenciasDisponibles($db, intval($sku["id_sku"]), $origen, $item) as $existenciaOrigen) {
                    if ($pendiente <= 0) {
                        break;
                    }
                    $mover = min($pendiente, floatval($existenciaOrigen["cantidad_disponible"]));
                    $this->aplicarCambio($db, $existenciaOrigen, $mover, "salida", "traspaso", 0, $referencia, $datos, $idUsuario);
                    $itemDestino = array(
                        "lote" => $existenciaOrigen["lote"],
                        "fecha_caducidad" => $existenciaOrigen["fecha_caducidad"],
                        "ubicacion_id" => isset($item["ubicacion_destino_id"]) ? intval($item["ubicacion_destino_id"]) : 0
                    );
                    $existenciaDestino = $this->obtenerOCrearExistencia($db, $sku, $destino, $itemDestino, floatval($existenciaOrigen["costo_promedio"]));
                    $this->aplicarCambio($db, $existenciaDestino, $mover, "entrada", "traspaso", 0, $referencia, $datos, $idUsuario);
                    $pendiente = round($pendiente - $mover, 4);
                    $movimientos += 2;
                }
                if ($pendiente > 0.0001) {
                    throw new Exception("Existencia insuficiente para traspasar el SKU " . $sku["sku"]);
                }
            }
            $db->commit();
            return $this->respuesta(false, "success", "Traspaso ERP aplicado", array("referencia" => $referencia, "movimientos" => $movimientos));
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    private function validarAlmacen($db, $idAlmacen) {
        $stmt = $db->prepare("SELECT id_almacen FROM erp_almacenes
            WHERE id_almacen=:almacen AND COALESCE(estatus,'activo')='activo'");
        $stmt->execute(array(":almacen" => $idAlmacen));
        if (!$stmt->fetchColumn()) {
            throw new Exception("Almacen no encontrado o inactivo");
        }
    }

    private function consultarSku($db, $idSku) {
        $stmt = $db->prepare("SELECT s.id_sku, s.sku, s.id_producto_erp, s.costo_referencia,
                s.factor_unidad_base,
                COALESCE(ub.abreviatura, ub.codigo, '') unidad_base_label,
                COALESCE(sp.factor_conversion, s.factor_unidad_base, 1.000000) factor_conversion_compra,
                COALESCE(uc.abreviatura, uc.codigo, '') unidad_compra_label,
                COALESCE(r.requiere_lote, 0) requiere_lote,
                COALESCE(r.requiere_caducidad, 0) requiere_caducidad,
                COALESCE(r.generar_etiqueta_interna, 0) generar_etiqueta_interna,
                COALESCE(r.permite_venta_fraccionaria, 0) permite_venta_fraccionaria,
                COALESCE(r.precision_decimal, 0) precision_decimal,
                COALESCE(r.incremento_minimo_venta, 1.000000) incremento_minimo_venta,
                COALESCE(r.prefijo_etiqueta_interna, '') prefijo_etiqueta_interna
            FROM erp_catalogo_skus s INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
            LEFT JOIN erp_catalogo_sku_reglas_inventario r ON r.id_sku=s.id_sku
            LEFT JOIN erp_catalogo_unidades ub ON ub.id_unidad=s.id_unidad_base
            LEFT JOIN erp_catalogo_sku_proveedores sp ON sp.id_sku=s.id_sku AND sp.estatus='activo' AND sp.es_preferido=1
            LEFT JOIN erp_catalogo_unidades uc ON uc.id_unidad=sp.id_unidad_compra
            WHERE s.id_sku=:sku AND s.estatus='activo' AND p.estatus='activo'");
        $stmt->execute(array(":sku" => $idSku));
        $sku = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$sku) {
            throw new Exception("SKU ERP no encontrado");
        }
        return $sku;
    }

    private function existenciasDisponibles($db, $idSku, $idAlmacen, $item) {
        $sql = "SELECT * FROM erp_inventario_existencias
            WHERE id_sku_erp=:sku AND id_almacen_clave=:almacen AND cantidad_disponible>0";
        $params = array(":sku" => $idSku, ":almacen" => $idAlmacen);
        if (!empty($item["id_existencia_inventario"])) {
            $sql .= " AND id_existencia_inventario=:existencia";
            $params[":existencia"] = intval($item["id_existencia_inventario"]);
        }
        $sql .= " ORDER BY CASE WHEN fecha_caducidad IS NULL THEN 1 ELSE 0 END, fecha_caducidad, fecha_registro FOR UPDATE";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function documentoOperacionAjuste($datos, $tipo) {
        $documento = trim(isset($datos["documento_operacion"]) ? $datos["documento_operacion"] : "ajuste");
        if ($documento === "inventario_inicial") {
            if ($tipo !== "entrada") {
                throw new Exception("El inventario inicial solo permite entradas");
            }
            return "inventario_inicial";
        }
        return "ajuste";
    }

    private function motivoAjuste($datos, $tipo, $documentoOperacion) {
        $motivo = trim(isset($datos["motivo_ajuste"]) ? $datos["motivo_ajuste"] : "");
        $permitidosEntrada = array("sobrante_conteo", "correccion_documentada", "devolucion_cliente", "recuperacion");
        $permitidosSalida = array("faltante_conteo", "merma", "caducado", "danado", "uso_interno", "robo_perdida", "correccion_documentada");
        if ($documentoOperacion === "inventario_inicial") {
            if ($motivo === "" || $motivo === "inventario_inicial") {
                return "inventario_inicial";
            }
            throw new Exception("El motivo de inventario inicial no es valido");
        }
        if ($tipo === "entrada" && in_array($motivo, $permitidosEntrada, true)) {
            return $motivo;
        }
        if ($tipo === "salida" && in_array($motivo, $permitidosSalida, true)) {
            return $motivo;
        }
        throw new Exception("Selecciona un motivo de ajuste valido");
    }

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-07-09
     * Proposito: normaliza captura de inventario inicial real a cantidad base antes del kardex.
     * Impacto: Inventario inicial; soporta stock historico por unidad base, unidad de compra y unidad fisica cerrada/abierta.
     * Contrato: no escribe por si misma; devuelve cantidad positiva en unidad base para existencia y movimiento.
     */
    private function cantidadBaseAjuste($sku, $item, $documentoOperacion) {
        if ($documentoOperacion !== "inventario_inicial") {
            return round(floatval(isset($item["cantidad"]) ? $item["cantidad"] : 0), 4);
        }

        $modo = trim(isset($item["modo_captura"]) ? $item["modo_captura"] : "base");
        if (!in_array($modo, array("base", "unidad_compra", "unidad_fisica_cerrada", "unidad_fisica_abierta"), true)) {
            throw new Exception("Modo de captura de inventario inicial no valido");
        }

        if ($modo === "unidad_compra") {
            $cantidadCompra = round(floatval(isset($item["cantidad_compra"]) ? $item["cantidad_compra"] : (isset($item["cantidad"]) ? $item["cantidad"] : 0)), 6);
            $factor = round(floatval(isset($item["factor_conversion"]) ? $item["factor_conversion"] : $sku["factor_conversion_compra"]), 6);
            if ($cantidadCompra <= 0 || $factor <= 0) {
                throw new Exception("Captura cantidad de compra y factor de conversion validos para " . $sku["sku"]);
            }
            return round($cantidadCompra * $factor, 4);
        }

        if ($modo === "unidad_fisica_cerrada") {
            $unidades = intval(isset($item["cantidad_unidades_fisicas"]) ? $item["cantidad_unidades_fisicas"] : (isset($item["cantidad"]) ? $item["cantidad"] : 0));
            $contenido = round(floatval(isset($item["contenido_base_original"]) ? $item["contenido_base_original"] : 0), 6);
            if ($unidades <= 0 || $contenido <= 0) {
                throw new Exception("Captura unidades fisicas cerradas y contenido base validos para " . $sku["sku"]);
            }
            return round($unidades * $contenido, 4);
        }

        if ($modo === "unidad_fisica_abierta") {
            $original = round(floatval(isset($item["contenido_base_original"]) ? $item["contenido_base_original"] : 0), 6);
            $disponible = round(floatval(isset($item["contenido_base_disponible"]) ? $item["contenido_base_disponible"] : (isset($item["cantidad"]) ? $item["cantidad"] : 0)), 6);
            if ($original <= 0 || $disponible <= 0 || $disponible > $original + 0.000001) {
                throw new Exception("Captura contenido original y disponible validos para la unidad abierta de " . $sku["sku"]);
            }
            return round($disponible, 4);
        }

        return round(floatval(isset($item["cantidad"]) ? $item["cantidad"] : 0), 4);
    }

    private function validarReglasItem($sku, $item, $cantidad, $documentoOperacion) {
        $modo = trim(isset($item["modo_captura"]) ? $item["modo_captura"] : "base");
        if (intval($sku["permite_venta_fraccionaria"]) !== 1 && abs($cantidad - round($cantidad)) > 0.0001) {
            throw new Exception("La cantidad de " . $sku["sku"] . " debe ser entera");
        }
        if (intval($sku["requiere_lote"]) === 1 && trim(isset($item["lote"]) ? $item["lote"] : "") === "") {
            throw new Exception("El SKU " . $sku["sku"] . " requiere lote");
        }
        if (intval($sku["requiere_caducidad"]) === 1 && trim(isset($item["fecha_caducidad"]) ? $item["fecha_caducidad"] : "") === "") {
            throw new Exception("El SKU " . $sku["sku"] . " requiere caducidad");
        }
        if ($documentoOperacion === "inventario_inicial" && intval($sku["generar_etiqueta_interna"]) === 1
            && !in_array($modo, array("unidad_fisica_cerrada", "unidad_fisica_abierta"), true)
            && abs($cantidad - floor($cantidad)) > 0.0001) {
            throw new Exception("El SKU " . $sku["sku"] . " genera etiquetas y debe cargarse en unidades enteras");
        }
    }

    private function actualizarEstadoEtiqueta($datos, $idUsuario, $estadoDestino) {
        $idUnidad = intval(isset($datos["id_inventario_unidad"]) ? $datos["id_inventario_unidad"] : 0);
        if ($idUnidad <= 0) {
            return $this->respuesta(true, "warning", "Unidad de inventario no valida");
        }

        $db = $this->getConexion();
        try {
            $db->beginTransaction();
            $stmt = $db->prepare("SELECT id_inventario_unidad, codigo_unico, codigo_etiqueta_interna, estado_etiqueta
                FROM erp_inventario_unidades WHERE id_inventario_unidad=:unidad FOR UPDATE");
            $stmt->execute(array(":unidad" => $idUnidad));
            $unidad = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$unidad) {
                throw new Exception("Unidad de inventario no encontrada");
            }

            $estadoActual = trim($unidad["estado_etiqueta"]);
            if ($estadoDestino === "impresa" && !in_array($estadoActual, array("pendiente_impresion", "reimpresa"), true)) {
                throw new Exception("Solo se puede marcar como impresa una etiqueta pendiente de impresion");
            }
            if ($estadoDestino === "pegada" && !in_array($estadoActual, array("impresa", "reimpresa"), true)) {
                throw new Exception("Primero marca la etiqueta como impresa");
            }

            if ($estadoDestino === "impresa") {
                $sql = "UPDATE erp_inventario_unidades
                    SET estado_etiqueta='impresa', fecha_impresion=NOW(), impreso_por=:usuario, fecha_actualizacion=NOW()
                    WHERE id_inventario_unidad=:unidad";
            } else {
                $sql = "UPDATE erp_inventario_unidades
                    SET estado_etiqueta='pegada', fecha_etiquetado=NOW(), etiquetado_por=:usuario, fecha_actualizacion=NOW()
                    WHERE id_inventario_unidad=:unidad";
            }
            $stmt = $db->prepare($sql);
            $stmt->execute(array(":usuario" => intval($idUsuario) ?: null, ":unidad" => $idUnidad));
            $db->commit();

            return $this->respuesta(false, "success", "Estado de etiqueta actualizado", array(
                "id_inventario_unidad" => $idUnidad,
                "codigo" => $unidad["codigo_etiqueta_interna"] ?: $unidad["codigo_unico"],
                "estado_anterior" => $estadoActual,
                "estado_nuevo" => $estadoDestino
            ));
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    private function actualizarEstadoEtiquetas($datos, $idUsuario, $estadoDestino) {
        $ids = isset($datos["ids"]) ? $datos["ids"] : array();
        if (is_string($ids)) {
            $ids = json_decode($ids, true);
        }
        if (!is_array($ids)) {
            return $this->respuesta(true, "warning", "Seleccion de etiquetas no valida");
        }
        $ids = array_values(array_unique(array_filter(array_map("intval", $ids))));
        if (empty($ids)) {
            return $this->respuesta(true, "warning", "Selecciona al menos una etiqueta");
        }

        $permitidos = array();
        $sqlSet = "";
        if ($estadoDestino === "pegada") {
            $permitidos = array("impresa", "reimpresa");
            $sqlSet = "estado_etiqueta='pegada', fecha_etiquetado=NOW(), etiquetado_por=?, fecha_actualizacion=NOW()";
        } else {
            return $this->respuesta(true, "warning", "Estado de etiqueta no soportado");
        }

        $db = $this->getConexion();
        try {
            $db->beginTransaction();
            $placeholders = implode(",", array_fill(0, count($ids), "?"));
            $stmt = $db->prepare("SELECT id_inventario_unidad, codigo_unico, codigo_etiqueta_interna, estado_etiqueta
                FROM erp_inventario_unidades WHERE id_inventario_unidad IN ($placeholders) FOR UPDATE");
            $stmt->execute($ids);
            $unidades = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (count($unidades) !== count($ids)) {
                throw new Exception("Una o mas etiquetas no fueron encontradas");
            }

            foreach ($unidades as $unidad) {
                if (!in_array(trim($unidad["estado_etiqueta"]), $permitidos, true)) {
                    $codigo = $unidad["codigo_etiqueta_interna"] ?: $unidad["codigo_unico"];
                    throw new Exception("La etiqueta " . $codigo . " no esta lista para marcarse como pegada");
                }
            }

            $params = array(intval($idUsuario) ?: null);
            $params = array_merge($params, $ids);
            $stmt = $db->prepare("UPDATE erp_inventario_unidades SET $sqlSet
                WHERE id_inventario_unidad IN ($placeholders)");
            $stmt->execute($params);
            $db->commit();

            return $this->respuesta(false, "success", "Etiquetas marcadas como pegadas", array(
                "ids" => $ids,
                "total" => count($ids),
                "estado_nuevo" => $estadoDestino
            ));
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    private function obtenerOCrearExistencia($db, $sku, $idAlmacen, $item, $costo = null) {
        $lote = trim(isset($item["lote"]) ? $item["lote"] : "");
        $caducidad = trim(isset($item["fecha_caducidad"]) ? $item["fecha_caducidad"] : "");
        $ubicacionId = intval(isset($item["ubicacion_id"]) ? $item["ubicacion_id"] : 0);
        if ($ubicacionId > 0) {
            $stmt = $db->prepare("SELECT nombre FROM erp_almacen_ubicaciones WHERE id_ubicacion=:ubicacion AND id_almacen_clave=:almacen AND estatus IN ('activo','activa')");
            $stmt->execute(array(":ubicacion" => $ubicacionId, ":almacen" => $idAlmacen));
            $ubicacion = $stmt->fetchColumn();
            if ($ubicacion === false) {
                throw new Exception("La ubicacion no pertenece al almacen");
            }
        } else {
            $ubicacion = null;
        }
        $stmt = $db->prepare("SELECT * FROM erp_inventario_existencias
            WHERE id_producto=:producto AND id_sku_erp=:sku AND id_almacen_clave=:almacen
              AND lote_clave=:lote AND fecha_caducidad_clave=:caducidad AND ubicacion_clave=:ubicacion FOR UPDATE");
        $stmt->execute(array(
            ":producto" => intval($sku["id_producto_erp"]), ":sku" => intval($sku["id_sku"]), ":almacen" => $idAlmacen,
            ":lote" => $this->clave($lote), ":caducidad" => $caducidad ?: "1000-01-01", ":ubicacion" => $ubicacionId
        ));
        $existencia = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($existencia) {
            return $existencia;
        }
        $stmt = $db->prepare("INSERT INTO erp_inventario_existencias
            (id_producto, id_sku_erp, id_almacen, id_almacen_clave, lote, lote_clave, fecha_caducidad,
             fecha_caducidad_clave, ubicacion_id, ubicacion_clave, ubicacion, cantidad, cantidad_apartada,
             cantidad_disponible, costo_promedio, estatus_existencia)
            VALUES (:producto,:sku,:almacen,:almacen,:lote,:lote_clave,:caducidad,:caducidad_clave,
             :ubicacion_id,:ubicacion_id,:ubicacion,0,0,0,:costo,'disponible')");
        $stmt->execute(array(
            ":producto" => intval($sku["id_producto_erp"]), ":sku" => intval($sku["id_sku"]), ":almacen" => $idAlmacen,
            ":lote" => $lote ?: null, ":lote_clave" => $this->clave($lote), ":caducidad" => $caducidad ?: null,
            ":caducidad_clave" => $caducidad ?: "1000-01-01", ":ubicacion_id" => $ubicacionId,
            ":ubicacion" => $ubicacion ?: null, ":costo" => $costo === null ? floatval($sku["costo_referencia"]) : $costo
        ));
        $id = intval($db->lastInsertId());
        $db->prepare("UPDATE erp_inventario_existencias SET codigo_existencia=:codigo WHERE id_existencia_inventario=:id")
            ->execute(array(":codigo" => "EXI-" . intval($sku["id_producto_erp"]) . "-" . $id, ":id" => $id));
        $stmt = $db->prepare("SELECT * FROM erp_inventario_existencias WHERE id_existencia_inventario=:id FOR UPDATE");
        $stmt->execute(array(":id" => $id));
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function aplicarCambio($db, $existencia, $cantidad, $tipo, $origenTipo, $origenId, $referencia, $datos, $idUsuario) {
        $signo = $tipo === "entrada" ? 1 : -1;
        $anterior = floatval($existencia["cantidad"]);
        $nueva = round($anterior + ($cantidad * $signo), 4);
        $disponible = round(floatval($existencia["cantidad_disponible"]) + ($cantidad * $signo), 4);
        if ($nueva < -0.0001 || $disponible < -0.0001) {
            throw new Exception("El movimiento produciria existencia negativa");
        }
        $stmt = $db->prepare("UPDATE erp_inventario_existencias SET cantidad=:cantidad,
            cantidad_disponible=:disponible, estatus_existencia=:estatus, fecha_actualizacion=NOW()
            WHERE id_existencia_inventario=:id");
        $stmt->execute(array(
            ":cantidad" => $nueva, ":disponible" => $disponible,
            ":estatus" => $nueva > 0 ? "disponible" : "agotada", ":id" => intval($existencia["id_existencia_inventario"])
        ));
        $costo = floatval($existencia["costo_promedio"]);
        $stmt = $db->prepare("INSERT INTO erp_inventario_movimientos
            (id_producto,id_sku_erp,id_almacen,tipo_movimiento,origen_tipo,origen_id,id_existencia_inventario,
             codigo_existencia,lote,fecha_caducidad,ubicacion_id,ubicacion,cantidad,costo_unitario,costo_total,
             existencia_anterior,existencia_nueva,referencia,observaciones)
            VALUES (:producto,:sku,:almacen,:tipo,:origen_tipo,:origen_id,:existencia,:codigo,:lote,:caducidad,
             :ubicacion_id,:ubicacion,:cantidad,:costo,:total,:anterior,:nueva,:referencia,:observaciones)");
        $stmt->execute(array(
            ":producto" => intval($existencia["id_producto"]), ":sku" => intval($existencia["id_sku_erp"]),
            ":almacen" => intval($existencia["id_almacen_clave"]), ":tipo" => $tipo, ":origen_tipo" => $origenTipo,
            ":origen_id" => intval($origenId), ":existencia" => intval($existencia["id_existencia_inventario"]),
            ":codigo" => $existencia["codigo_existencia"], ":lote" => $existencia["lote"],
            ":caducidad" => $existencia["fecha_caducidad"], ":ubicacion_id" => $existencia["ubicacion_id"],
            ":ubicacion" => $existencia["ubicacion"], ":cantidad" => $cantidad, ":costo" => $costo,
            ":total" => round($cantidad * $costo, 4), ":anterior" => $anterior, ":nueva" => $nueva,
            ":referencia" => $referencia,
            ":observaciones" => $this->observacionesMovimiento($datos, $idUsuario)
        ));
        $idMovimiento = intval($db->lastInsertId());
        $db->prepare("UPDATE erp_inventario_existencias SET ultimo_movimiento_id=:movimiento WHERE id_existencia_inventario=:existencia")
            ->execute(array(":movimiento" => $idMovimiento, ":existencia" => intval($existencia["id_existencia_inventario"])));
        return $idMovimiento;
    }

    private function observacionesMovimiento($datos, $idUsuario) {
        $partes = array();
        $motivo = trim(isset($datos["motivo_ajuste"]) ? $datos["motivo_ajuste"] : "");
        if ($motivo !== "") {
            $partes[] = "motivo:" . $motivo;
        }
        $observaciones = trim(isset($datos["observaciones"]) ? $datos["observaciones"] : "");
        if ($observaciones !== "") {
            $partes[] = $observaciones;
        }
        if ($idUsuario) {
            $partes[] = "usuario:" . intval($idUsuario);
        }
        return implode(" | ", $partes);
    }

    private function generarUnidadesInventarioInicial($db, $sku, $existencia, $cantidad, $item, $referencia, $idMovimiento, $indiceItem, $datos) {
        $modo = trim(isset($item["modo_captura"]) ? $item["modo_captura"] : "base");
        $requiereUnidadFisica = in_array($modo, array("unidad_fisica_cerrada", "unidad_fisica_abierta"), true);
        if (intval($sku["generar_etiqueta_interna"]) !== 1 && !$requiereUnidadFisica) {
            return 0;
        }
        $cantidadEntera = intval($cantidad);
        $contenidoOriginal = 1.000000;
        $contenidoDisponible = 1.000000;
        $estadoFisico = "cerrada";
        if ($modo === "unidad_fisica_cerrada") {
            $cantidadEntera = intval(isset($item["cantidad_unidades_fisicas"]) ? $item["cantidad_unidades_fisicas"] : 0);
            $contenidoOriginal = round(floatval(isset($item["contenido_base_original"]) ? $item["contenido_base_original"] : 0), 6);
            $contenidoDisponible = $contenidoOriginal;
        } elseif ($modo === "unidad_fisica_abierta") {
            $cantidadEntera = 1;
            $contenidoOriginal = round(floatval(isset($item["contenido_base_original"]) ? $item["contenido_base_original"] : 0), 6);
            $contenidoDisponible = round(floatval(isset($item["contenido_base_disponible"]) ? $item["contenido_base_disponible"] : $cantidad), 6);
            $estadoFisico = "abierta";
        }
        if ($cantidadEntera <= 0) {
            return 0;
        }
        $prefijo = $this->clave(isset($sku["prefijo_etiqueta_interna"]) ? $sku["prefijo_etiqueta_interna"] : "");
        if ($prefijo === "") {
            $prefijo = "INV";
        }
        $observaciones = trim(isset($datos["observaciones"]) ? $datos["observaciones"] : "");
        $stmt = $db->prepare("INSERT INTO erp_inventario_unidades
            (codigo_unico, tipo_identidad, codigo_etiqueta_interna, id_producto, id_sku_erp,
             id_recepcion_almacen, id_recepcion_lote, id_existencia_inventario, id_almacen, ubicacion_id,
             lote, fecha_caducidad, cantidad_base_original, cantidad_base_disponible, unidad_base,
             estatus, estado_etiqueta, estado_fisico, origen_tipo, origen_id, origen_detalle_id, observaciones)
            VALUES (:codigo, 'etiqueta_interna', :codigo_etiqueta, :producto, :sku,
             NULL, NULL, :existencia, :almacen, :ubicacion_id,
             :lote, :caducidad, :cantidad_base_original, :cantidad_base_disponible, :unidad_base,
             'disponible', 'pendiente_impresion', :estado_fisico, 'inventario_inicial', :origen, :detalle, :observaciones)");

        for ($i = 1; $i <= $cantidadEntera; $i++) {
            $codigo = $prefijo . "-II" . str_pad((string) intval($idMovimiento), 6, "0", STR_PAD_LEFT) . "-" . str_pad((string) $i, 4, "0", STR_PAD_LEFT);
            $contenidoItemOriginal = $requiereUnidadFisica ? $contenidoOriginal : 1.000000;
            $contenidoItemDisponible = $requiereUnidadFisica ? $contenidoDisponible : 1.000000;
            $stmt->execute(array(
                ":codigo" => $codigo,
                ":codigo_etiqueta" => $codigo,
                ":producto" => intval($sku["id_producto_erp"]),
                ":sku" => intval($sku["id_sku"]),
                ":existencia" => intval($existencia["id_existencia_inventario"]),
                ":almacen" => intval($existencia["id_almacen_clave"]),
                ":ubicacion_id" => intval($existencia["ubicacion_id"]) ?: null,
                ":lote" => $existencia["lote"],
                ":caducidad" => $existencia["fecha_caducidad"],
                ":cantidad_base_original" => $contenidoItemOriginal,
                ":cantidad_base_disponible" => $contenidoItemDisponible,
                ":unidad_base" => trim(isset($sku["unidad_base_label"]) ? $sku["unidad_base_label"] : "") ?: null,
                ":estado_fisico" => $estadoFisico,
                ":origen" => intval($idMovimiento),
                ":detalle" => intval($indiceItem),
                ":observaciones" => trim("Inventario inicial " . $referencia . ($observaciones !== "" ? " | " . $observaciones : ""))
            ));
        }
        return $cantidadEntera;
    }

    private function clave($valor) {
        $valor = strtoupper(trim((string) $valor));
        return preg_replace('/[^A-Z0-9_-]+/', '-', $valor);
    }

    private function referenciaMovimiento($datos, $prefijo, $requerida = false) {
        $referencia = trim(isset($datos["referencia"]) ? $datos["referencia"] : "");
        if ($referencia !== "") {
            $referencia = strtoupper(preg_replace('/[^A-Z0-9_-]+/', '-', $referencia));
            if ($prefijo === "INV-INICIAL" && strpos($referencia, "INV-INICIAL-") !== 0) {
                throw new Exception("La referencia de inventario inicial debe iniciar con INV-INICIAL-");
            }
            return substr($referencia, 0, 120);
        }
        if ($requerida) {
            throw new Exception("La referencia documental es obligatoria");
        }
        return $prefijo . "-" . date("YmdHis");
    }

    private function folioConteo($db) {
        $prefijo = "CON-" . date("Ymd");
        $stmt = $db->prepare("SELECT COUNT(*) FROM erp_inventario_conteos WHERE folio LIKE :prefijo");
        $stmt->execute(array(":prefijo" => $prefijo . "-%"));
        $siguiente = intval($stmt->fetchColumn()) + 1;
        return $prefijo . "-" . str_pad((string) $siguiente, 4, "0", STR_PAD_LEFT);
    }

    private function folioReserva($db) {
        $prefijo = "RES-" . date("Ymd");
        $stmt = $db->prepare("SELECT COUNT(*) FROM erp_inventario_reservas WHERE folio LIKE :prefijo");
        $stmt->execute(array(":prefijo" => $prefijo . "-%"));
        $siguiente = intval($stmt->fetchColumn()) + 1;
        return $prefijo . "-" . str_pad((string) $siguiente, 4, "0", STR_PAD_LEFT);
    }

    private function respuesta($error, $tipo, $mensaje, $depurar = array()) {
        return array("error" => $error, "tipo" => $tipo, "mensaje" => $mensaje, "depurar" => $depurar);
    }
}
