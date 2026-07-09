<?php

class GarantiasErp extends CRUD {

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-06-27
     * Proposito: consultar si la estructura minima de Garantias ERP esta disponible antes de operar.
     * Impacto: Garantias ERP; evita que Catalogo, Ventas o Postventa usen tablas inexistentes.
     * Contrato: no modifica BD; devuelve booleano y lista de tablas faltantes.
     */
    public function disponibilidadEsquema() {
        $db = $this->getConexion();
        $tablas = array(
            "erp_garantias_politicas",
            "erp_garantias_politicas_reglas",
            "erp_ventas_detalle_garantias",
            "erp_garantias_reclamos",
            "erp_garantias_reclamos_eventos",
            "erp_garantias_adjuntos",
            "erp_garantias_proveedor_seguimiento"
        );
        $faltantes = array();

        foreach ($tablas as $tabla) {
            if (!$this->tablaExiste($db, $tabla)) {
                $faltantes[] = $tabla;
            }
        }

        return array(
            "disponible" => empty($faltantes),
            "faltantes" => $faltantes
        );
    }

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-06-27
     * Proposito: resolver la politica vigente de garantia para un SKU usando reglas configuradas.
     * Impacto: Catalogo, Ventas/POS y Postventa consumen este contrato sin duplicar logica.
     * Contrato: read-only; si falta esquema devuelve `sin_garantia` con alerta bloqueante.
     */
    public function resolverGarantiaSku($datos = array()) {
        try {
            $db = $this->getConexion();
            $idSku = intval($this->valor($datos, "id_sku_erp", $this->valor($datos, "id_sku", 0)));
            $fecha = trim((string) $this->valor($datos, "fecha", date("Y-m-d")));
            $canal = trim((string) $this->valor($datos, "canal", ""));
            $idAlmacen = intval($this->valor($datos, "id_almacen", 0));
            $alertas = array();

            if ($idSku <= 0) {
                return $this->respuesta(false, "warning", "Indica un SKU ERP para resolver garantia", array(
                    "politica" => $this->politicaSinGarantia(),
                    "alertas" => array("sku_no_indicado")
                ));
            }

            $disponibilidad = $this->disponibilidadEsquema();
            if (!$disponibilidad["disponible"]) {
                return $this->respuesta(false, "warning", "Esquema de Garantias ERP pendiente", array(
                    "id_sku_erp" => $idSku,
                    "politica" => $this->politicaSinGarantia(),
                    "regla" => null,
                    "snapshot_sugerido" => $this->snapshotSugerido($this->politicaSinGarantia(), $fecha),
                    "alertas" => array("esquema_pendiente"),
                    "faltantes" => $disponibilidad["faltantes"]
                ));
            }

            $sku = $this->consultarContextoSku($db, $idSku);
            if (!$sku) {
                return $this->respuesta(false, "warning", "SKU ERP no encontrado", array(
                    "id_sku_erp" => $idSku,
                    "politica" => $this->politicaSinGarantia(),
                    "alertas" => array("sku_no_encontrado")
                ));
            }

            $reglas = $this->buscarReglasAplicables($db, $sku, $fecha, $canal, $idAlmacen);
            if (empty($reglas)) {
                return $this->respuesta(false, "info", "El SKU no tiene garantia configurada", array(
                    "id_sku_erp" => $idSku,
                    "sku" => $sku,
                    "politica" => $this->politicaSinGarantia(),
                    "regla" => null,
                    "snapshot_sugerido" => $this->snapshotSugerido($this->politicaSinGarantia(), $fecha),
                    "alertas" => array("politica_no_configurada")
                ));
            }

            $primera = $reglas[0];
            $empatadas = $this->reglasEmpatadas($reglas, $primera);
            if (count($empatadas) > 1) {
                $alertas[] = "reglas_duplicadas_misma_prioridad";
            }

            $politica = $this->normalizarPolitica($primera);
            return $this->respuesta(false, empty($alertas) ? "success" : "warning", "Garantia resuelta", array(
                "id_sku_erp" => $idSku,
                "sku" => $sku,
                "politica" => $politica,
                "regla" => $this->normalizarRegla($primera),
                "snapshot_sugerido" => $this->snapshotSugerido($politica, $fecha),
                "alertas" => $alertas
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-06-27
     * Proposito: simular creacion de reclamo para validar datos minimos antes de habilitar flujo real.
     * Impacto: Postventa/Garantias; prepara validaciones sin crear folios ni mover inventario.
     * Contrato: dry-run; no escribe BD.
     */
    public function reclamoDryRun($datos = array()) {
        $bloqueos = array();
        $idVenta = intval($this->valor($datos, "id_venta", 0));
        $idVentaDetalle = intval($this->valor($datos, "id_venta_detalle", 0));
        $idSku = intval($this->valor($datos, "id_sku_erp", $this->valor($datos, "id_sku", 0)));
        $motivo = trim((string) $this->valor($datos, "motivo", ""));

        if ($idVenta <= 0 && $idVentaDetalle <= 0) {
            $bloqueos[] = "Indica venta o detalle de venta para ligar el reclamo";
        }
        if ($idSku <= 0) {
            $bloqueos[] = "Indica SKU ERP";
        }
        if ($motivo === "") {
            $bloqueos[] = "Captura motivo del reclamo";
        }

        $disponibilidad = $this->disponibilidadEsquema();
        if (!$disponibilidad["disponible"]) {
            $bloqueos[] = "Esquema de Garantias ERP pendiente de autorizacion y respaldo externo";
        }

        return $this->respuesta(false, empty($bloqueos) ? "success" : "warning", empty($bloqueos) ? "Dry-run de reclamo valido" : "Dry-run de reclamo bloqueado", array(
            "dry_run" => true,
            "bloqueos" => $bloqueos,
            "faltantes" => $disponibilidad["faltantes"],
            "contrato" => array(
                "no_crea_reclamo" => true,
                "no_mueve_inventario" => true,
                "no_crea_devolucion" => true,
                "requiere_snapshot_venta" => true,
                "requiere_eventos_auditables" => true
            )
        ));
    }

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-06-27
     * Proposito: simular el snapshot de garantia que Ventas debe guardar por partida al confirmar venta.
     * Impacto: Ventas/POS y Garantias ERP; evita recalcular politicas vivas para ventas historicas.
     * Contrato: dry-run; no crea venta, no guarda snapshot y no modifica inventario.
     */
    public function ventaSnapshotDryRun($datos = array()) {
        $items = $this->decodificarItems($this->valor($datos, "items", array()));
        $fecha = trim((string) $this->valor($datos, "fecha", date("Y-m-d")));
        $canal = trim((string) $this->valor($datos, "canal", "pos"));
        $idAlmacen = intval($this->valor($datos, "id_almacen", 0));
        $bloqueos = array();
        $snapshots = array();

        if (empty($items)) {
            $bloqueos[] = "Agrega partidas para simular snapshot de garantia";
        }

        foreach ($items as $indice => $item) {
            $idSku = intval($this->valor($item, "id_sku_erp", $this->valor($item, "id_sku", 0)));
            if ($idSku <= 0) {
                $bloqueos[] = "Partida " . ($indice + 1) . ": falta SKU ERP";
                continue;
            }
            $resuelto = $this->resolverGarantiaSku(array(
                "id_sku_erp" => $idSku,
                "fecha" => $fecha,
                "canal" => $canal,
                "id_almacen" => $idAlmacen
            ));
            $depurar = isset($resuelto["depurar"]) && is_array($resuelto["depurar"]) ? $resuelto["depurar"] : array();
            $alertas = isset($depurar["alertas"]) && is_array($depurar["alertas"]) ? $depurar["alertas"] : array();
            if (in_array("esquema_pendiente", $alertas, true)) {
                $bloqueos[] = "Esquema de Garantias ERP pendiente de autorizacion y respaldo externo";
            }
            $snapshots[] = array(
                "indice" => $indice,
                "id_sku_erp" => $idSku,
                "resultado" => $resuelto
            );
        }

        return $this->respuesta(false, empty($bloqueos) ? "success" : "warning", empty($bloqueos) ? "Dry-run de snapshot valido" : "Dry-run de snapshot bloqueado", array(
            "dry_run" => true,
            "fecha" => $fecha,
            "canal" => $canal,
            "id_almacen" => $idAlmacen,
            "bloqueos" => array_values(array_unique($bloqueos)),
            "snapshots" => $snapshots,
            "contrato" => array(
                "no_crea_venta" => true,
                "no_guarda_snapshot" => true,
                "no_mueve_inventario" => true,
                "snapshot_debe_guardarse_al_confirmar_venta" => true
            )
        ));
    }

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-06-27
     * Proposito: guardar el snapshot de garantia por partida al confirmar una venta real.
     * Impacto: Ventas/POS y Garantias ERP; evita recalcular politicas vivas en tickets historicos.
     * Contrato: requiere transaccion externa; no crea venta ni mueve inventario.
     */
    public function guardarSnapshotsVenta($db, $datos = array()) {
        try {
            if (!$db) {
                return $this->respuesta(true, "danger", "Conexion no disponible para guardar snapshot de garantia");
            }

            $disponibilidad = $this->disponibilidadEsquema();
            if (!$disponibilidad["disponible"]) {
                return $this->respuesta(false, "warning", "Esquema de Garantias ERP pendiente", array(
                    "guardados" => array(),
                    "bloqueos" => array("esquema_pendiente"),
                    "faltantes" => $disponibilidad["faltantes"]
                ));
            }

            $idVenta = intval($this->valor($datos, "id_venta", 0));
            $idAlmacen = intval($this->valor($datos, "id_almacen", 0));
            $fecha = trim((string) $this->valor($datos, "fecha", date("Y-m-d")));
            $canal = trim((string) $this->valor($datos, "canal", "pos"));
            $detalles = $this->decodificarItems($this->valor($datos, "detalles", array()));
            $guardados = array();
            $bloqueos = array();

            if ($idVenta <= 0) {
                $bloqueos[] = "Falta id_venta para snapshot de garantia";
            }
            if (empty($detalles)) {
                $bloqueos[] = "Faltan detalles de venta para snapshot de garantia";
            }
            if (!empty($bloqueos)) {
                return $this->respuesta(false, "warning", "Snapshot de garantia bloqueado", array(
                    "guardados" => array(),
                    "bloqueos" => $bloqueos
                ));
            }

            foreach ($detalles as $indice => $detalle) {
                $idDetalle = intval($this->valor($detalle, "id_venta_detalle", 0));
                $idSku = intval($this->valor($detalle, "id_sku_erp", $this->valor($detalle, "id_sku", 0)));
                $idProducto = intval($this->valor($detalle, "id_producto_erp", 0));
                if ($idDetalle <= 0 || $idSku <= 0) {
                    $bloqueos[] = "Partida " . ($indice + 1) . ": falta detalle o SKU para snapshot";
                    continue;
                }

                $resuelto = $this->resolverGarantiaSku(array(
                    "id_sku_erp" => $idSku,
                    "fecha" => $fecha,
                    "canal" => $canal,
                    "id_almacen" => $idAlmacen
                ));
                $depurar = isset($resuelto["depurar"]) && is_array($resuelto["depurar"]) ? $resuelto["depurar"] : array();
                $politica = isset($depurar["politica"]) && is_array($depurar["politica"]) ? $depurar["politica"] : $this->politicaSinGarantia();
                $regla = isset($depurar["regla"]) && is_array($depurar["regla"]) ? $depurar["regla"] : array();
                $snapshot = isset($depurar["snapshot_sugerido"]) && is_array($depurar["snapshot_sugerido"])
                    ? $depurar["snapshot_sugerido"]
                    : $this->snapshotSugerido($politica, $fecha);

                $stmt = $db->prepare("INSERT INTO erp_ventas_detalle_garantias
                    (id_venta, id_venta_detalle, id_producto_erp, id_sku_erp,
                     id_garantia_politica, id_regla_garantia, tipo_garantia_snapshot,
                     nombre_politica_snapshot, duracion_valor_snapshot, unidad_duracion_snapshot,
                     coberturas_snapshot, requisitos_snapshot, exclusiones_snapshot,
                     resumen_ticket, fecha_inicio, fecha_vencimiento, estatus, fecha_registro)
                    VALUES (:venta, :detalle, :producto, :sku, :politica, :regla,
                     :tipo, :nombre, :duracion, :unidad, :coberturas, :requisitos,
                     :exclusiones, :resumen, :inicio, :vencimiento, 'vigente', NOW())");
                $stmt->execute(array(
                    ":venta" => $idVenta,
                    ":detalle" => $idDetalle,
                    ":producto" => $idProducto > 0 ? $idProducto : null,
                    ":sku" => $idSku,
                    ":politica" => intval($this->valor($politica, "id_garantia_politica", 0)) > 0 ? intval($this->valor($politica, "id_garantia_politica", 0)) : null,
                    ":regla" => intval($this->valor($regla, "id_regla_garantia", 0)) > 0 ? intval($this->valor($regla, "id_regla_garantia", 0)) : null,
                    ":tipo" => $this->valor($politica, "tipo_garantia", "sin_garantia"),
                    ":nombre" => $this->valor($politica, "nombre", "Sin garantia"),
                    ":duracion" => intval($this->valor($politica, "duracion_valor", 0)),
                    ":unidad" => $this->valor($politica, "unidad_duracion", "dias"),
                    ":coberturas" => $this->normalizarJsonTexto($this->valor($politica, "coberturas", array())),
                    ":requisitos" => $this->normalizarJsonTexto($this->valor($politica, "requisitos", array())),
                    ":exclusiones" => $this->normalizarJsonTexto($this->valor($politica, "exclusiones", array())),
                    ":resumen" => $this->valor($snapshot, "resumen_ticket", "Sin garantia"),
                    ":inicio" => $this->valor($snapshot, "fecha_inicio", $fecha),
                    ":vencimiento" => $this->valor($snapshot, "fecha_vencimiento", null)
                ));

                $guardados[] = array(
                    "id_venta_detalle_garantia" => intval($db->lastInsertId()),
                    "id_venta_detalle" => $idDetalle,
                    "id_sku_erp" => $idSku,
                    "resumen_ticket" => $this->valor($snapshot, "resumen_ticket", "Sin garantia"),
                    "fecha_vencimiento" => $this->valor($snapshot, "fecha_vencimiento", null)
                );
            }

            return $this->respuesta(false, empty($bloqueos) ? "success" : "warning", "Snapshots de garantia procesados", array(
                "guardados" => $guardados,
                "bloqueos" => $bloqueos,
                "contrato" => array(
                    "venta_ya_confirmada" => true,
                    "no_recalcula_ticket_historico" => true,
                    "sin_movimiento_inventario" => true
                )
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-06-27
     * Proposito: consultar elegibilidad preliminar de garantia sin crear reclamo.
     * Impacto: Postventa, Ventas historicas y Garantias ERP.
     * Contrato: read-only; si falta esquema devuelve bloqueo accionable.
     */
    public function elegibilidadConsultar($datos = array()) {
        $disponibilidad = $this->disponibilidadEsquema();
        if (!$disponibilidad["disponible"]) {
            return $this->respuesta(false, "warning", "Esquema de Garantias ERP pendiente", array(
                "elegible" => false,
                "bloqueos" => array("esquema_pendiente"),
                "faltantes" => $disponibilidad["faltantes"],
                "regla" => "No se puede consultar elegibilidad real sin snapshot de venta."
            ));
        }

        $idVentaDetalleGarantia = intval($this->valor($datos, "id_venta_detalle_garantia", 0));
        $idVentaDetalle = intval($this->valor($datos, "id_venta_detalle", 0));
        $folio = trim((string) $this->valor($datos, "folio", ""));

        if ($idVentaDetalleGarantia <= 0 && $idVentaDetalle <= 0 && $folio === "") {
            return $this->respuesta(false, "warning", "Indica snapshot, detalle de venta o folio", array(
                "elegible" => false,
                "bloqueos" => array("referencia_no_indicada")
            ));
        }

        return $this->respuesta(false, "info", "Consulta de elegibilidad pendiente de implementar contra ventas reales", array(
            "elegible" => false,
            "bloqueos" => array("consulta_real_pendiente"),
            "contrato" => array(
                "debe_consultar_snapshot" => true,
                "debe_validar_vigencia" => true,
                "debe_validar_unidad_vendida_si_aplica" => true,
                "no_crea_reclamo" => true
            )
        ));
    }

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-06-27
     * Proposito: listar politicas de garantia configuradas sin modificar datos.
     * Impacto: Garantias/Catalogo; alimenta futuras pantallas de administracion.
     * Contrato: read-only; si falta esquema devuelve bloqueo accionable.
     */
    public function listarPoliticas($filtros = array()) {
        try {
            $disponibilidad = $this->disponibilidadEsquema();
            if (!$disponibilidad["disponible"]) {
                return $this->respuesta(false, "warning", "Esquema de Garantias ERP pendiente", array(
                    "politicas" => array(),
                    "faltantes" => $disponibilidad["faltantes"]
                ));
            }

            $db = $this->getConexion();
            $estatus = trim((string) $this->valor($filtros, "estatus", ""));
            $tipo = trim((string) $this->valor($filtros, "tipo_garantia", ""));
            $params = array();
            $where = array("1=1");

            if ($estatus !== "") {
                $where[] = "p.estatus = :estatus";
                $params[":estatus"] = $estatus;
            }
            if ($tipo !== "") {
                $where[] = "p.tipo_garantia = :tipo";
                $params[":tipo"] = $tipo;
            }

            $sql = "SELECT p.*, COUNT(r.id_regla_garantia) reglas_activas
                    FROM erp_garantias_politicas p
                    LEFT JOIN erp_garantias_politicas_reglas r
                      ON r.id_garantia_politica = p.id_garantia_politica AND r.estatus = 'activa'
                    WHERE " . implode(" AND ", $where) . "
                    GROUP BY p.id_garantia_politica
                    ORDER BY p.estatus = 'activa' DESC, p.nombre";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);

            return $this->respuesta(false, "success", "Politicas de garantia consultadas", array(
                "politicas" => $stmt->fetchAll(PDO::FETCH_ASSOC)
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-06-27
     * Proposito: listar reglas de asignacion de politicas sin modificar configuracion.
     * Impacto: Garantias/Catalogo; permite auditar que SKUs, productos o categorias tienen garantia.
     * Contrato: read-only; no crea, actualiza ni desactiva reglas.
     */
    public function listarReglasPoliticas($filtros = array()) {
        try {
            $disponibilidad = $this->disponibilidadEsquema();
            if (!$disponibilidad["disponible"]) {
                return $this->respuesta(false, "warning", "Esquema de Garantias ERP pendiente", array(
                    "reglas" => array(),
                    "faltantes" => $disponibilidad["faltantes"]
                ));
            }

            $db = $this->getConexion();
            $estatus = trim((string) $this->valor($filtros, "estatus", "activa"));
            $ambito = trim((string) $this->valor($filtros, "ambito", ""));
            $idPolitica = intval($this->valor($filtros, "id_garantia_politica", 0));
            $params = array();
            $where = array("1=1");

            if ($estatus !== "") {
                $where[] = "r.estatus = :estatus";
                $params[":estatus"] = $estatus;
            }
            if ($ambito !== "") {
                $where[] = "r.ambito = :ambito";
                $params[":ambito"] = $ambito;
            }
            if ($idPolitica > 0) {
                $where[] = "r.id_garantia_politica = :politica";
                $params[":politica"] = $idPolitica;
            }

            $sql = "SELECT r.*, p.codigo, p.nombre AS politica_nombre, p.tipo_garantia,
                           CASE
                             WHEN r.ambito='sku' THEN s.sku
                             WHEN r.ambito='producto' THEN pr.nombre
                             WHEN r.ambito='categoria' THEN c.nombre
                             WHEN r.ambito='marca' THEN m.nombre
                             WHEN r.ambito='proveedor' THEN pv.proveedor
                             ELSE NULL
                           END AS referencia_nombre
                    FROM erp_garantias_politicas_reglas r
                    INNER JOIN erp_garantias_politicas p ON p.id_garantia_politica = r.id_garantia_politica
                    LEFT JOIN erp_catalogo_skus s ON r.ambito='sku' AND s.id_sku = r.id_referencia
                    LEFT JOIN erp_catalogo_productos pr ON r.ambito='producto' AND pr.id_producto_erp = r.id_referencia
                    LEFT JOIN erp_catalogo_categorias c ON r.ambito='categoria' AND c.id_categoria_erp = r.id_referencia
                    LEFT JOIN erp_catalogo_marcas m ON r.ambito='marca' AND m.id_marca_erp = r.id_referencia
                    LEFT JOIN erp_proveedores pv ON r.ambito='proveedor' AND pv.id_proveedor = r.id_referencia
                    WHERE " . implode(" AND ", $where) . "
                    ORDER BY r.estatus='activa' DESC, r.prioridad ASC, r.ambito ASC, r.id_referencia ASC
                    LIMIT 300";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);

            return $this->respuesta(false, "success", "Reglas de garantia consultadas", array(
                "reglas" => $stmt->fetchAll(PDO::FETCH_ASSOC)
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-06-27
     * Proposito: buscar SKUs desde Garantias para resolver politicas sin exigir ID interno al usuario.
     * Impacto: Garantias/Catalogo; reutiliza catalogo como fuente sin modificar productos.
     * Contrato: read-only; devuelve maximo 20 coincidencias activas o en revision.
     */
    public function buscarSkus($filtros = array()) {
        try {
            $termino = trim((string) $this->valor($filtros, "q", $this->valor($filtros, "termino", "")));
            if (strlen($termino) < 2) {
                return $this->respuesta(false, "success", "Indica al menos dos caracteres", array(
                    "skus" => array()
                ));
            }

            $db = $this->getConexion();
            $stmt = $db->prepare("SELECT s.id_sku, s.sku, s.nombre AS sku_nombre, s.estatus,
                       p.id_producto_erp, p.codigo_producto, p.nombre AS producto,
                       u.nombre AS unidad, u.abreviatura
                FROM erp_catalogo_skus s
                INNER JOIN erp_catalogo_productos p ON p.id_producto_erp = s.id_producto_erp
                LEFT JOIN erp_catalogo_unidades u ON u.id_unidad = s.id_unidad_base
                WHERE s.estatus IN ('activo','borrador','en_revision')
                  AND (
                    s.sku LIKE :q_sku
                    OR s.nombre LIKE :q_nombre
                    OR p.nombre LIKE :q_producto
                    OR p.codigo_producto LIKE :q_codigo
                  )
                ORDER BY CASE WHEN s.sku=:exacto THEN 0 WHEN s.sku LIKE :prefijo THEN 1 ELSE 2 END,
                         s.estatus='activo' DESC,
                         s.sku ASC
                LIMIT 20");
            $stmt->execute(array(
                ":q_sku" => "%" . $termino . "%",
                ":q_nombre" => "%" . $termino . "%",
                ":q_producto" => "%" . $termino . "%",
                ":q_codigo" => "%" . $termino . "%",
                ":exacto" => $termino,
                ":prefijo" => $termino . "%"
            ));

            return $this->respuesta(false, "success", "SKUs encontrados", array(
                "skus" => $stmt->fetchAll(PDO::FETCH_ASSOC)
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-06-27
     * Proposito: buscar referencias por ambito para asignar politicas sin capturar IDs manualmente.
     * Impacto: Garantias/Catalogo; facilita reglas por SKU, producto, categoria, marca o proveedor.
     * Contrato: read-only; no crea reglas ni modifica catalogos.
     */
    public function buscarReferenciasRegla($filtros = array()) {
        try {
            $ambito = trim((string) $this->valor($filtros, "ambito", "sku"));
            if (!in_array($ambito, array("sku", "producto", "categoria", "marca", "proveedor"), true)) {
                $ambito = "sku";
            }
            $termino = trim((string) $this->valor($filtros, "q", $this->valor($filtros, "termino", "")));
            if (strlen($termino) < 2) {
                return $this->respuesta(false, "success", "Indica al menos dos caracteres", array(
                    "ambito" => $ambito,
                    "referencias" => array()
                ));
            }

            $db = $this->getConexion();
            $like = "%" . $termino . "%";
            $prefijo = $termino . "%";
            switch ($ambito) {
                case "producto":
                    $stmt = $db->prepare("SELECT p.id_producto_erp AS id_referencia,
                               p.codigo_producto AS codigo,
                               p.nombre AS nombre,
                               p.estatus,
                               CONCAT('Producto ERP #', p.id_producto_erp) AS detalle
                        FROM erp_catalogo_productos p
                        WHERE p.estatus<>'fusionado'
                          AND (p.codigo_producto LIKE :q_codigo OR p.nombre LIKE :q_nombre)
                        ORDER BY CASE WHEN p.codigo_producto=:exacto THEN 0 WHEN p.codigo_producto LIKE :prefijo THEN 1 ELSE 2 END,
                                 p.nombre ASC
                        LIMIT 20");
                    $stmt->execute(array(":q_codigo" => $like, ":q_nombre" => $like, ":exacto" => $termino, ":prefijo" => $prefijo));
                    break;
                case "categoria":
                    $stmt = $db->prepare("SELECT c.id_categoria_erp AS id_referencia,
                               c.codigo AS codigo,
                               COALESCE(c.ruta, c.nombre) AS nombre,
                               c.estatus,
                               CONCAT('Categoria ERP #', c.id_categoria_erp) AS detalle
                        FROM erp_catalogo_categorias c
                        WHERE c.estatus='activa'
                          AND c.permite_productos=1
                          AND (c.codigo LIKE :q_codigo OR c.nombre LIKE :q_nombre OR c.ruta LIKE :q_ruta)
                        ORDER BY CASE WHEN c.codigo=:exacto THEN 0 WHEN c.codigo LIKE :prefijo THEN 1 ELSE 2 END,
                                 COALESCE(c.ruta, c.nombre) ASC
                        LIMIT 20");
                    $stmt->execute(array(":q_codigo" => $like, ":q_nombre" => $like, ":q_ruta" => $like, ":exacto" => $termino, ":prefijo" => $prefijo));
                    break;
                case "marca":
                    $stmt = $db->prepare("SELECT m.id_marca_erp AS id_referencia,
                               m.codigo AS codigo,
                               m.nombre AS nombre,
                               m.estatus,
                               CONCAT('Marca ERP #', m.id_marca_erp) AS detalle
                        FROM erp_catalogo_marcas m
                        WHERE m.estatus='activa'
                          AND (m.codigo LIKE :q_codigo OR m.nombre LIKE :q_nombre)
                        ORDER BY CASE WHEN m.codigo=:exacto THEN 0 WHEN m.codigo LIKE :prefijo THEN 1 ELSE 2 END,
                                 m.nombre ASC
                        LIMIT 20");
                    $stmt->execute(array(":q_codigo" => $like, ":q_nombre" => $like, ":exacto" => $termino, ":prefijo" => $prefijo));
                    break;
                case "proveedor":
                    $stmt = $db->prepare("SELECT p.id_proveedor AS id_referencia,
                               CAST(p.id_proveedor AS CHAR) AS codigo,
                               p.proveedor AS nombre,
                               'activo' AS estatus,
                               CONCAT('Proveedor #', p.id_proveedor) AS detalle
                        FROM erp_proveedores p
                        WHERE p.proveedor LIKE :q_nombre OR CAST(p.id_proveedor AS CHAR)=:exacto
                        ORDER BY CASE WHEN CAST(p.id_proveedor AS CHAR)=:exacto THEN 0 WHEN p.proveedor LIKE :prefijo THEN 1 ELSE 2 END,
                                 p.proveedor ASC
                        LIMIT 20");
                    $stmt->execute(array(":q_nombre" => $like, ":exacto" => $termino, ":prefijo" => $prefijo));
                    break;
                case "sku":
                default:
                    $stmt = $db->prepare("SELECT s.id_sku AS id_referencia,
                               s.sku AS codigo,
                               COALESCE(s.nombre, p.nombre) AS nombre,
                               s.estatus,
                               CONCAT('Producto: ', p.nombre) AS detalle
                        FROM erp_catalogo_skus s
                        INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
                        WHERE s.estatus IN ('activo','borrador','en_revision')
                          AND (s.sku LIKE :q_sku OR s.nombre LIKE :q_nombre OR p.nombre LIKE :q_producto OR p.codigo_producto LIKE :q_codigo)
                        ORDER BY CASE WHEN s.sku=:exacto THEN 0 WHEN s.sku LIKE :prefijo THEN 1 ELSE 2 END,
                                 s.estatus='activo' DESC,
                                 s.sku ASC
                        LIMIT 20");
                    $stmt->execute(array(":q_sku" => $like, ":q_nombre" => $like, ":q_producto" => $like, ":q_codigo" => $like, ":exacto" => $termino, ":prefijo" => $prefijo));
                    break;
            }

            return $this->respuesta(false, "success", "Referencias encontradas", array(
                "ambito" => $ambito,
                "referencias" => $stmt->fetchAll(PDO::FETCH_ASSOC),
                "contrato" => array(
                    "no_crea_regla" => true,
                    "no_modifica_catalogo" => true
                )
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-06-29
     * Proposito: auditar cobertura de politicas de garantia sobre SKUs activos.
     * Impacto: Garantias/Catalogo; cuantifica pendientes antes de asignar reglas masivas, incluyendo reglas por proveedor activo.
     * Contrato: read-only; no resuelve reclamos ni modifica reglas.
     */
    public function auditarCoberturaSkus($filtros = array()) {
        try {
            $disponibilidad = $this->disponibilidadEsquema();
            if (!$disponibilidad["disponible"]) {
                return $this->respuesta(false, "warning", "Esquema de Garantias ERP pendiente", array(
                    "faltantes" => $disponibilidad["faltantes"]
                ));
            }

            $db = $this->getConexion();
            $subqueryCobertura = "EXISTS (
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
                        SELECT 1 FROM erp_catalogo_sku_proveedores sp
                        WHERE sp.id_sku=s.id_sku AND sp.id_proveedor=r.id_referencia AND sp.estatus='activo'
                    ))
                  )
            )";

            $sqlResumen = "SELECT
                    COUNT(*) total_skus_activos,
                    SUM(CASE WHEN {$subqueryCobertura} THEN 1 ELSE 0 END) skus_con_regla,
                    SUM(CASE WHEN {$subqueryCobertura} THEN 0 ELSE 1 END) skus_sin_regla
                FROM erp_catalogo_skus s
                INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
                LEFT JOIN erp_catalogo_producto_categorias pc ON pc.id_producto_erp=p.id_producto_erp AND pc.es_principal=1
                WHERE s.estatus='activo'";
            $resumen = $db->query($sqlResumen)->fetch(PDO::FETCH_ASSOC);

            $sqlEjemplos = "SELECT s.id_sku, s.sku, s.nombre AS sku_nombre,
                       p.id_producto_erp, p.nombre AS producto,
                       pc.id_categoria_erp, c.nombre AS categoria,
                       p.id_marca_erp, m.nombre AS marca
                FROM erp_catalogo_skus s
                INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
                LEFT JOIN erp_catalogo_producto_categorias pc ON pc.id_producto_erp=p.id_producto_erp AND pc.es_principal=1
                LEFT JOIN erp_catalogo_categorias c ON c.id_categoria_erp=pc.id_categoria_erp
                LEFT JOIN erp_catalogo_marcas m ON m.id_marca_erp=p.id_marca_erp
                WHERE s.estatus='activo'
                  AND NOT {$subqueryCobertura}
                ORDER BY s.id_sku DESC
                LIMIT 25";
            $ejemplos = $db->query($sqlEjemplos)->fetchAll(PDO::FETCH_ASSOC);

            return $this->respuesta(false, "success", "Cobertura de garantias auditada", array(
                "resumen" => array(
                    "total_skus_activos" => intval($resumen["total_skus_activos"]),
                    "skus_con_regla" => intval($resumen["skus_con_regla"]),
                    "skus_sin_regla" => intval($resumen["skus_sin_regla"])
                ),
                "ejemplos_sin_regla" => $ejemplos,
                "contrato" => array(
                    "no_modifica_reglas" => true,
                    "no_asigna_politicas" => true,
                    "no_crea_notificaciones" => true
                )
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-06-27
     * Proposito: previsualizar cuantos SKUs afectaria una regla de garantia antes de guardarla.
     * Impacto: Garantias/Catalogo; evita asignaciones masivas sin evidencia.
     * Contrato: read-only/dry-run; no crea reglas, no modifica politicas y no mueve inventario.
     */
    public function previsualizarImpactoRegla($datos = array()) {
        try {
            $validacion = $this->politicaReglaDryRun($datos);
            $bloqueos = isset($validacion["depurar"]["bloqueos"]) ? $validacion["depurar"]["bloqueos"] : array();
            if (!empty($bloqueos)) {
                return $this->respuesta(false, "warning", "Vista previa bloqueada", array(
                    "dry_run" => true,
                    "bloqueos" => $bloqueos,
                    "impacto" => null
                ));
            }

            $disponibilidad = $this->disponibilidadEsquema();
            if (!$disponibilidad["disponible"]) {
                return $this->respuesta(false, "warning", "Esquema de Garantias ERP pendiente", array(
                    "faltantes" => $disponibilidad["faltantes"]
                ));
            }

            $db = $this->getConexion();
            $normalizado = $validacion["depurar"]["normalizado"];
            $ambito = $normalizado["ambito"];
            $idReferencia = intval($normalizado["id_referencia"]);
            $condicion = $this->condicionSkusPorAmbito($ambito);
            if ($condicion === null) {
                return $this->respuesta(false, "warning", "Ambito no soportado para vista previa", array(
                    "ambito" => $ambito
                ));
            }

            $params = array(":referencia" => $idReferencia);
            $sqlBase = "FROM erp_catalogo_skus s
                INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
                LEFT JOIN erp_catalogo_producto_categorias pc ON pc.id_producto_erp=p.id_producto_erp
                LEFT JOIN erp_catalogo_categorias c ON c.id_categoria_erp=pc.id_categoria_erp
                LEFT JOIN erp_catalogo_marcas m ON m.id_marca_erp=p.id_marca_erp
                WHERE s.estatus='activo' AND " . $condicion;

            $stmtTotal = $db->prepare("SELECT COUNT(DISTINCT s.id_sku) " . $sqlBase);
            $stmtTotal->execute($params);
            $total = intval($stmtTotal->fetchColumn());

            $stmtConRegla = $db->prepare("SELECT COUNT(DISTINCT s.id_sku) " . $sqlBase . "
                AND EXISTS (
                    SELECT 1
                    FROM erp_garantias_politicas_reglas r
                    INNER JOIN erp_garantias_politicas gp ON gp.id_garantia_politica=r.id_garantia_politica AND gp.estatus='activa'
                    WHERE r.estatus='activa'
                      AND (
                        (r.ambito='sku' AND r.id_referencia=s.id_sku)
                        OR (r.ambito='producto' AND r.id_referencia=s.id_producto_erp)
                        OR (r.ambito='categoria' AND r.id_referencia=pc.id_categoria_erp)
                        OR (r.ambito='marca' AND r.id_referencia=p.id_marca_erp)
                        OR (r.ambito='proveedor' AND EXISTS (
                            SELECT 1 FROM erp_catalogo_sku_proveedores sp
                            WHERE sp.id_sku=s.id_sku AND sp.id_proveedor=r.id_referencia AND sp.estatus='activo'
                        ))
                      )
                )");
            $stmtConRegla->execute($params);
            $conRegla = intval($stmtConRegla->fetchColumn());

            $stmtDuplicada = $db->prepare("SELECT r.id_regla_garantia, p.codigo, r.prioridad, r.canal, r.estatus
                FROM erp_garantias_politicas_reglas r
                INNER JOIN erp_garantias_politicas p ON p.id_garantia_politica=r.id_garantia_politica
                WHERE r.ambito=:ambito AND r.id_referencia=:referencia AND r.estatus='activa'
                ORDER BY r.prioridad ASC, r.id_regla_garantia ASC");
            $stmtDuplicada->execute(array(":ambito" => $ambito, ":referencia" => $idReferencia));

            $stmtEjemplos = $db->prepare("SELECT DISTINCT s.id_sku, s.sku, s.nombre AS sku_nombre,
                       p.id_producto_erp, p.nombre AS producto,
                       pc.id_categoria_erp, c.nombre AS categoria,
                       p.id_marca_erp, m.nombre AS marca
                " . $sqlBase . "
                ORDER BY s.id_sku DESC
                LIMIT 25");
            $stmtEjemplos->execute($params);

            return $this->respuesta(false, "success", "Vista previa de impacto generada", array(
                "dry_run" => true,
                "normalizado" => $normalizado,
                "impacto" => array(
                    "skus_afectados" => $total,
                    "skus_con_alguna_regla_actual" => $conRegla,
                    "skus_sin_regla_actual" => max(0, $total - $conRegla),
                    "reglas_existentes_mismo_ambito" => $stmtDuplicada->fetchAll(PDO::FETCH_ASSOC),
                    "ejemplos" => $stmtEjemplos->fetchAll(PDO::FETCH_ASSOC),
                    "advertencias" => $ambito === "proveedor" ? array("Las reglas por proveedor requieren que el flujo que resuelve garantia conozca proveedor de la partida o proveedor principal vigente.") : array()
                ),
                "contrato" => array(
                    "no_crea_regla" => true,
                    "no_actualiza_regla" => true,
                    "no_asigna_politica" => true
                )
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-06-27
     * Proposito: validar captura de politica de garantia antes de permitir guardado real.
     * Impacto: Garantias/Catalogo; evita crear politicas incompletas o ambiguas.
     * Contrato: dry-run; no inserta ni actualiza BD.
     */
    public function politicaDryRun($datos = array()) {
        $bloqueos = array();
        $codigo = strtoupper(trim((string) $this->valor($datos, "codigo", "")));
        $nombre = trim((string) $this->valor($datos, "nombre", ""));
        $tipo = trim((string) $this->valor($datos, "tipo_garantia", ""));
        $duracion = intval($this->valor($datos, "duracion_valor", 0));
        $unidad = trim((string) $this->valor($datos, "unidad_duracion", "dias"));

        if ($codigo === "") {
            $bloqueos[] = "Captura codigo de politica";
        }
        if ($nombre === "") {
            $bloqueos[] = "Captura nombre de politica";
        }
        if (!in_array($tipo, array("sin_garantia", "garantia_tienda", "garantia_proveedor", "garantia_fabricante", "cambio_inmediato", "reparacion", "satisfaccion_limitada", "caducidad_calidad"), true)) {
            $bloqueos[] = "Tipo de garantia invalido";
        }
        if ($duracion < 0) {
            $bloqueos[] = "La duracion no puede ser negativa";
        }
        if (!in_array($unidad, array("dias", "meses"), true)) {
            $bloqueos[] = "Unidad de duracion invalida";
        }
        if ($tipo !== "sin_garantia" && $duracion <= 0) {
            $bloqueos[] = "Una politica con garantia debe tener duracion mayor a cero";
        }

        return $this->respuesta(false, empty($bloqueos) ? "success" : "warning", empty($bloqueos) ? "Dry-run de politica valido" : "Dry-run de politica bloqueado", array(
            "dry_run" => true,
            "bloqueos" => $bloqueos,
            "normalizado" => array(
                "codigo" => $codigo,
                "nombre" => $nombre,
                "descripcion" => trim((string) $this->valor($datos, "descripcion", "")),
                "tipo_garantia" => $tipo,
                "duracion_valor" => $duracion,
                "unidad_duracion" => $unidad,
                "requisitos" => array(
                    "requiere_ticket" => intval($this->valor($datos, "requiere_ticket", 1)),
                    "requiere_cliente" => intval($this->valor($datos, "requiere_cliente", 0)),
                    "requiere_serie" => intval($this->valor($datos, "requiere_serie", 0)),
                    "requiere_lote" => intval($this->valor($datos, "requiere_lote", 0)),
                    "requiere_empaque" => intval($this->valor($datos, "requiere_empaque", 0)),
                    "requiere_diagnostico" => intval($this->valor($datos, "requiere_diagnostico", 0)),
                    "requiere_fotos" => intval($this->valor($datos, "requiere_fotos", 0)),
                    "requiere_autorizacion_supervisor" => intval($this->valor($datos, "requiere_autorizacion_supervisor", 0)),
                    "requiere_validacion_proveedor" => intval($this->valor($datos, "requiere_validacion_proveedor", 0))
                ),
                "resultados" => array(
                    "permite_cambio" => intval($this->valor($datos, "permite_cambio", 0)),
                    "permite_reparacion" => intval($this->valor($datos, "permite_reparacion", 0)),
                    "permite_devolucion_dinero" => intval($this->valor($datos, "permite_devolucion_dinero", 0)),
                    "permite_nota_credito" => intval($this->valor($datos, "permite_nota_credito", 0)),
                    "permite_envio_proveedor" => intval($this->valor($datos, "permite_envio_proveedor", 0))
                )
            ),
            "contrato" => array(
                "no_crea_politica" => true,
                "no_actualiza_politica" => true,
                "requiere_autorizacion_para_guardado_real" => true
            )
        ));
    }

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-06-29
     * Proposito: validar regla de asignacion de garantia antes de permitir guardado real.
     * Impacto: Garantias/Catalogo; protege precedencia por SKU/producto/categoria/marca/proveedor y bloquea referencias inexistentes.
     * Contrato: dry-run; no inserta ni actualiza BD.
     */
    public function politicaReglaDryRun($datos = array()) {
        $bloqueos = array();
        $idPolitica = intval($this->valor($datos, "id_garantia_politica", 0));
        $ambito = trim((string) $this->valor($datos, "ambito", ""));
        $idReferencia = intval($this->valor($datos, "id_referencia", 0));
        $prioridad = intval($this->valor($datos, "prioridad", 100));
        $canal = trim((string) $this->valor($datos, "canal", ""));
        $idAlmacen = intval($this->valor($datos, "id_almacen", 0));
        $idRegla = intval($this->valor($datos, "id_regla_garantia", 0));
        $vigenciaDesde = trim((string) $this->valor($datos, "vigencia_desde", ""));
        $vigenciaHasta = trim((string) $this->valor($datos, "vigencia_hasta", ""));

        if ($idPolitica <= 0) {
            $bloqueos[] = "Indica politica de garantia";
        }
        if (!in_array($ambito, array("sku", "producto", "categoria", "marca", "proveedor"), true)) {
            $bloqueos[] = "Ambito de regla invalido";
        }
        if ($idReferencia <= 0) {
            $bloqueos[] = "Indica referencia del ambito";
        }
        if ($prioridad <= 0) {
            $bloqueos[] = "La prioridad debe ser mayor a cero";
        }
        if ($vigenciaDesde !== "" && $vigenciaHasta !== "" && $vigenciaHasta < $vigenciaDesde) {
            $bloqueos[] = "La vigencia hasta no puede ser anterior a vigencia desde";
        }
        if (empty($bloqueos)) {
            $disponibilidad = $this->disponibilidadEsquema();
            if (!$disponibilidad["disponible"]) {
                $bloqueos[] = "Esquema de Garantias ERP pendiente";
            } else {
                $db = $this->getConexion();
                if (!$this->existePoliticaGarantia($db, $idPolitica)) {
                    $bloqueos[] = "La politica seleccionada no existe";
                }
                if (!$this->existeReferenciaRegla($db, $ambito, $idReferencia)) {
                    $bloqueos[] = "La referencia seleccionada no existe o no esta disponible para el ambito";
                }
                $solapada = $this->buscarReglaSolapada($db, array(
                    "id_regla_garantia" => $idRegla,
                    "ambito" => $ambito,
                    "id_referencia" => $idReferencia,
                    "prioridad" => $prioridad,
                    "canal" => $canal,
                    "id_almacen" => $idAlmacen,
                    "vigencia_desde" => $vigenciaDesde,
                    "vigencia_hasta" => $vigenciaHasta
                ));
                if ($solapada) {
                    $bloqueos[] = "Ya existe una regla activa solapada para el mismo ambito, referencia, prioridad, canal o vigencia";
                }
            }
        }

        return $this->respuesta(false, empty($bloqueos) ? "success" : "warning", empty($bloqueos) ? "Dry-run de regla valido" : "Dry-run de regla bloqueado", array(
            "dry_run" => true,
            "bloqueos" => $bloqueos,
            "normalizado" => array(
                "id_garantia_politica" => $idPolitica,
                "ambito" => $ambito,
                "id_referencia" => $idReferencia,
                "prioridad" => $prioridad,
                "canal" => $canal,
                "id_almacen" => $idAlmacen,
                "vigencia_desde" => $vigenciaDesde,
                "vigencia_hasta" => $vigenciaHasta
            ),
            "contrato" => array(
                "no_crea_regla" => true,
                "no_actualiza_regla" => true,
                "requiere_revision_para_guardado_real" => true
            )
        ));
    }

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-06-27
     * Proposito: guardar politica de garantia con validaciones de negocio ya probadas en dry-run.
     * Impacto: Garantias/Catalogo; crea o actualiza politicas que Ventas usara como snapshot.
     * Contrato: escribe BD; el controlador debe exigir permiso, respaldo externo y token antes de llamar.
     */
    public function guardarPolitica($datos = array()) {
        try {
            $validacion = $this->politicaDryRun($datos);
            $bloqueos = isset($validacion["depurar"]["bloqueos"]) ? $validacion["depurar"]["bloqueos"] : array();
            if (!empty($bloqueos)) {
                return $validacion;
            }

            $disponibilidad = $this->disponibilidadEsquema();
            if (!$disponibilidad["disponible"]) {
                return $this->respuesta(false, "warning", "Esquema de Garantias ERP pendiente", array(
                    "faltantes" => $disponibilidad["faltantes"]
                ));
            }

            $db = $this->getConexion();
            $idPolitica = intval($this->valor($datos, "id_garantia_politica", 0));
            $normalizado = $validacion["depurar"]["normalizado"];
            $usuario = intval($this->valor($datos, "usuario_id", 0));

            if ($idPolitica <= 0) {
                $stmtExistente = $db->prepare("SELECT id_garantia_politica FROM erp_garantias_politicas WHERE codigo=:codigo LIMIT 1");
                $stmtExistente->execute(array(":codigo" => $normalizado["codigo"]));
                $existente = $stmtExistente->fetch(PDO::FETCH_ASSOC);
                if ($existente) {
                    $idPolitica = intval($existente["id_garantia_politica"]);
                }
            }

            $payload = array(
                ":codigo" => $normalizado["codigo"],
                ":nombre" => $normalizado["nombre"],
                ":descripcion" => trim((string) $this->valor($datos, "descripcion", "")),
                ":tipo" => $normalizado["tipo_garantia"],
                ":duracion" => $normalizado["duracion_valor"],
                ":unidad" => $normalizado["unidad_duracion"],
                ":coberturas" => $this->normalizarJsonTexto($this->valor($datos, "coberturas_json", $this->valor($datos, "coberturas", array()))),
                ":requisitos" => $this->normalizarJsonTexto($this->valor($datos, "requisitos_json", $this->valor($datos, "requisitos", array()))),
                ":exclusiones" => $this->normalizarJsonTexto($this->valor($datos, "exclusiones_json", $this->valor($datos, "exclusiones", array()))),
                ":requiere_ticket" => intval($this->valor($datos, "requiere_ticket", 1)),
                ":requiere_cliente" => intval($this->valor($datos, "requiere_cliente", 0)),
                ":requiere_serie" => intval($this->valor($datos, "requiere_serie", 0)),
                ":requiere_lote" => intval($this->valor($datos, "requiere_lote", 0)),
                ":requiere_empaque" => intval($this->valor($datos, "requiere_empaque", 0)),
                ":requiere_diagnostico" => intval($this->valor($datos, "requiere_diagnostico", 0)),
                ":requiere_fotos" => intval($this->valor($datos, "requiere_fotos", 0)),
                ":requiere_autorizacion" => intval($this->valor($datos, "requiere_autorizacion_supervisor", 0)),
                ":requiere_proveedor" => intval($this->valor($datos, "requiere_validacion_proveedor", 0)),
                ":permite_cambio" => intval($this->valor($datos, "permite_cambio", 0)),
                ":permite_reparacion" => intval($this->valor($datos, "permite_reparacion", 0)),
                ":permite_devolucion" => intval($this->valor($datos, "permite_devolucion_dinero", 0)),
                ":permite_nota" => intval($this->valor($datos, "permite_nota_credito", 0)),
                ":permite_proveedor" => intval($this->valor($datos, "permite_envio_proveedor", 0)),
                ":estatus" => trim((string) $this->valor($datos, "estatus", "activa")),
                ":usuario" => $usuario > 0 ? $usuario : null
            );

            if ($idPolitica > 0) {
                $payload[":id"] = $idPolitica;
                $stmt = $db->prepare("UPDATE erp_garantias_politicas SET
                    codigo=:codigo, nombre=:nombre, descripcion=:descripcion, tipo_garantia=:tipo,
                    duracion_valor=:duracion, unidad_duracion=:unidad, coberturas_json=:coberturas,
                    requisitos_json=:requisitos, exclusiones_json=:exclusiones, requiere_ticket=:requiere_ticket,
                    requiere_cliente=:requiere_cliente, requiere_serie=:requiere_serie, requiere_lote=:requiere_lote,
                    requiere_empaque=:requiere_empaque, requiere_diagnostico=:requiere_diagnostico,
                    requiere_fotos=:requiere_fotos, requiere_autorizacion_supervisor=:requiere_autorizacion,
                    requiere_validacion_proveedor=:requiere_proveedor, permite_cambio=:permite_cambio,
                    permite_reparacion=:permite_reparacion, permite_devolucion_dinero=:permite_devolucion,
                    permite_nota_credito=:permite_nota, permite_envio_proveedor=:permite_proveedor,
                    estatus=:estatus, actualizado_por=:usuario, fecha_actualizacion=NOW()
                    WHERE id_garantia_politica=:id");
                $stmt->execute($payload);
            } else {
                $stmt = $db->prepare("INSERT INTO erp_garantias_politicas
                    (codigo, nombre, descripcion, tipo_garantia, duracion_valor, unidad_duracion,
                     coberturas_json, requisitos_json, exclusiones_json, requiere_ticket, requiere_cliente,
                     requiere_serie, requiere_lote, requiere_empaque, requiere_diagnostico, requiere_fotos,
                     requiere_autorizacion_supervisor, requiere_validacion_proveedor, permite_cambio,
                     permite_reparacion, permite_devolucion_dinero, permite_nota_credito, permite_envio_proveedor,
                     estatus, creado_por)
                    VALUES
                    (:codigo, :nombre, :descripcion, :tipo, :duracion, :unidad, :coberturas, :requisitos,
                     :exclusiones, :requiere_ticket, :requiere_cliente, :requiere_serie, :requiere_lote,
                     :requiere_empaque, :requiere_diagnostico, :requiere_fotos, :requiere_autorizacion,
                     :requiere_proveedor, :permite_cambio, :permite_reparacion, :permite_devolucion,
                     :permite_nota, :permite_proveedor, :estatus, :usuario)");
                $stmt->execute($payload);
                $idPolitica = intval($db->lastInsertId());
            }

            return $this->respuesta(false, "success", "Politica de garantia guardada", array(
                "id_garantia_politica" => $idPolitica,
                "codigo" => $normalizado["codigo"]
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-06-27
     * Proposito: guardar regla de asignacion de garantia a SKU/producto/categoria/marca/proveedor.
     * Impacto: Garantias/Catalogo; determina que politica aplica en POS y postventa.
     * Contrato: escribe BD; el controlador debe exigir permiso, respaldo externo y token antes de llamar.
     */
    public function guardarPoliticaRegla($datos = array()) {
        try {
            $validacion = $this->politicaReglaDryRun($datos);
            $bloqueos = isset($validacion["depurar"]["bloqueos"]) ? $validacion["depurar"]["bloqueos"] : array();
            if (!empty($bloqueos)) {
                return $validacion;
            }

            $disponibilidad = $this->disponibilidadEsquema();
            if (!$disponibilidad["disponible"]) {
                return $this->respuesta(false, "warning", "Esquema de Garantias ERP pendiente", array(
                    "faltantes" => $disponibilidad["faltantes"]
                ));
            }

            $db = $this->getConexion();
            $normalizado = $validacion["depurar"]["normalizado"];
            $idRegla = intval($this->valor($datos, "id_regla_garantia", 0));
            $usuario = intval($this->valor($datos, "usuario_id", 0));
            $payload = array(
                ":politica" => $normalizado["id_garantia_politica"],
                ":ambito" => $normalizado["ambito"],
                ":referencia" => $normalizado["id_referencia"],
                ":prioridad" => $normalizado["prioridad"],
                ":canal" => trim((string) $this->valor($datos, "canal", "")) ?: null,
                ":almacen" => intval($this->valor($datos, "id_almacen", 0)) ?: null,
                ":desde" => $normalizado["vigencia_desde"] !== "" ? $normalizado["vigencia_desde"] : null,
                ":hasta" => $normalizado["vigencia_hasta"] !== "" ? $normalizado["vigencia_hasta"] : null,
                ":estatus" => trim((string) $this->valor($datos, "estatus", "activa")),
                ":observaciones" => trim((string) $this->valor($datos, "observaciones", "")),
                ":usuario" => $usuario > 0 ? $usuario : null
            );

            if ($idRegla <= 0) {
                $stmtPolitica = $db->prepare("SELECT id_garantia_politica FROM erp_garantias_politicas WHERE id_garantia_politica=:id LIMIT 1");
                $stmtPolitica->execute(array(":id" => $normalizado["id_garantia_politica"]));
                if (!$stmtPolitica->fetch(PDO::FETCH_ASSOC)) {
                    return $this->respuesta(false, "warning", "La politica de garantia no existe", array(
                        "id_garantia_politica" => $normalizado["id_garantia_politica"]
                    ));
                }

                $stmtDuplicada = $db->prepare("SELECT id_regla_garantia
                    FROM erp_garantias_politicas_reglas
                    WHERE id_garantia_politica=:politica
                      AND ambito=:ambito
                      AND id_referencia=:referencia
                      AND prioridad=:prioridad
                      AND canal <=> :canal
                      AND id_almacen <=> :almacen
                      AND vigencia_desde <=> :desde
                      AND vigencia_hasta <=> :hasta
                      AND estatus=:estatus
                    LIMIT 1");
                $stmtDuplicada->execute(array(
                    ":politica" => $payload[":politica"],
                    ":ambito" => $payload[":ambito"],
                    ":referencia" => $payload[":referencia"],
                    ":prioridad" => $payload[":prioridad"],
                    ":canal" => $payload[":canal"],
                    ":almacen" => $payload[":almacen"],
                    ":desde" => $payload[":desde"],
                    ":hasta" => $payload[":hasta"],
                    ":estatus" => $payload[":estatus"]
                ));
                $duplicada = $stmtDuplicada->fetch(PDO::FETCH_ASSOC);
                if ($duplicada) {
                    return $this->respuesta(false, "info", "Regla de garantia ya existente", array(
                        "id_regla_garantia" => intval($duplicada["id_regla_garantia"]),
                        "id_garantia_politica" => $normalizado["id_garantia_politica"],
                        "sin_duplicar" => true
                    ));
                }
            }

            if ($idRegla > 0) {
                $payload[":id"] = $idRegla;
                $stmt = $db->prepare("UPDATE erp_garantias_politicas_reglas SET
                    id_garantia_politica=:politica, ambito=:ambito, id_referencia=:referencia,
                    prioridad=:prioridad, canal=:canal, id_almacen=:almacen, vigencia_desde=:desde,
                    vigencia_hasta=:hasta, estatus=:estatus, observaciones=:observaciones,
                    actualizado_por=:usuario, fecha_actualizacion=NOW()
                    WHERE id_regla_garantia=:id");
                $stmt->execute($payload);
            } else {
                $stmt = $db->prepare("INSERT INTO erp_garantias_politicas_reglas
                    (id_garantia_politica, ambito, id_referencia, prioridad, canal, id_almacen,
                     vigencia_desde, vigencia_hasta, estatus, observaciones, creado_por)
                    VALUES
                    (:politica, :ambito, :referencia, :prioridad, :canal, :almacen, :desde, :hasta,
                     :estatus, :observaciones, :usuario)");
                $stmt->execute($payload);
                $idRegla = intval($db->lastInsertId());
            }

            return $this->respuesta(false, "success", "Regla de garantia guardada", array(
                "id_regla_garantia" => $idRegla,
                "id_garantia_politica" => $normalizado["id_garantia_politica"]
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-06-27
     * Proposito: activar o desactivar una politica de garantia sin borrar historial.
     * Impacto: Garantias/Catalogo; una politica inactiva deja de resolver garantias futuras.
     * Contrato: escribe BD; baja logica, no elimina politicas ni snapshots historicos.
     */
    public function cambiarEstatusPolitica($datos = array()) {
        try {
            $idPolitica = intval($this->valor($datos, "id_garantia_politica", 0));
            $estatus = trim((string) $this->valor($datos, "estatus", ""));
            if ($idPolitica <= 0 || !in_array($estatus, array("activa", "inactiva"), true)) {
                return $this->respuesta(false, "warning", "Indica politica y estatus valido", array(
                    "permitidos" => array("activa", "inactiva")
                ));
            }

            $disponibilidad = $this->disponibilidadEsquema();
            if (!$disponibilidad["disponible"]) {
                return $this->respuesta(false, "warning", "Esquema de Garantias ERP pendiente", array(
                    "faltantes" => $disponibilidad["faltantes"]
                ));
            }

            $db = $this->getConexion();
            $stmt = $db->prepare("UPDATE erp_garantias_politicas
                SET estatus=:estatus, actualizado_por=:usuario, fecha_actualizacion=NOW()
                WHERE id_garantia_politica=:id");
            $stmt->execute(array(
                ":estatus" => $estatus,
                ":usuario" => intval($this->valor($datos, "usuario_id", 0)) ?: null,
                ":id" => $idPolitica
            ));

            if ($stmt->rowCount() === 0) {
                return $this->respuesta(false, "info", "Politica sin cambios o no encontrada", array(
                    "id_garantia_politica" => $idPolitica,
                    "estatus" => $estatus
                ));
            }

            return $this->respuesta(false, "success", "Estatus de politica actualizado", array(
                "id_garantia_politica" => $idPolitica,
                "estatus" => $estatus
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-06-27
     * Proposito: activar o desactivar una regla de garantia sin borrar configuracion.
     * Impacto: Garantias/Catalogo; una regla inactiva deja de participar en el resolver.
     * Contrato: escribe BD; baja logica, no elimina reglas ni snapshots historicos.
     */
    public function cambiarEstatusRegla($datos = array()) {
        try {
            $idRegla = intval($this->valor($datos, "id_regla_garantia", 0));
            $estatus = trim((string) $this->valor($datos, "estatus", ""));
            if ($idRegla <= 0 || !in_array($estatus, array("activa", "inactiva"), true)) {
                return $this->respuesta(false, "warning", "Indica regla y estatus valido", array(
                    "permitidos" => array("activa", "inactiva")
                ));
            }

            $disponibilidad = $this->disponibilidadEsquema();
            if (!$disponibilidad["disponible"]) {
                return $this->respuesta(false, "warning", "Esquema de Garantias ERP pendiente", array(
                    "faltantes" => $disponibilidad["faltantes"]
                ));
            }

            $db = $this->getConexion();
            $stmt = $db->prepare("UPDATE erp_garantias_politicas_reglas
                SET estatus=:estatus, actualizado_por=:usuario, fecha_actualizacion=NOW()
                WHERE id_regla_garantia=:id");
            $stmt->execute(array(
                ":estatus" => $estatus,
                ":usuario" => intval($this->valor($datos, "usuario_id", 0)) ?: null,
                ":id" => $idRegla
            ));

            if ($stmt->rowCount() === 0) {
                return $this->respuesta(false, "info", "Regla sin cambios o no encontrada", array(
                    "id_regla_garantia" => $idRegla,
                    "estatus" => $estatus
                ));
            }

            return $this->respuesta(false, "success", "Estatus de regla actualizado", array(
                "id_regla_garantia" => $idRegla,
                "estatus" => $estatus
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    private function consultarContextoSku($db, $idSku) {
        $sql = "SELECT s.id_sku, s.id_producto_erp, s.sku, s.nombre, s.estatus,
                       pc.id_categoria_erp, p.id_marca_erp, p.nombre AS producto
                FROM erp_catalogo_skus s
                LEFT JOIN erp_catalogo_productos p ON p.id_producto_erp = s.id_producto_erp
                LEFT JOIN erp_catalogo_producto_categorias pc ON pc.id_producto_erp = p.id_producto_erp AND pc.es_principal = 1
                WHERE s.id_sku = :id
                LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->execute(array(":id" => $idSku));
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        return $fila ?: null;
    }

    private function condicionSkusPorAmbito($ambito) {
        switch ($ambito) {
            case "sku":
                return "s.id_sku=:referencia";
            case "producto":
                return "s.id_producto_erp=:referencia";
            case "categoria":
                return "pc.id_categoria_erp=:referencia";
            case "marca":
                return "p.id_marca_erp=:referencia";
            case "proveedor":
                return "EXISTS (
                    SELECT 1 FROM erp_catalogo_sku_proveedores sp
                    WHERE sp.id_sku=s.id_sku AND sp.id_proveedor=:referencia AND sp.estatus='activo'
                )";
            default:
                return null;
        }
    }

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-06-29
     * Proposito: confirma que la politica usada por una regla exista antes de validar o guardar.
     * Impacto: Garantias ERP; evita reglas huerfanas por llamadas directas al endpoint.
     */
    private function existePoliticaGarantia($db, $idPolitica) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM erp_garantias_politicas WHERE id_garantia_politica=:id");
        $stmt->execute(array(":id" => intval($idPolitica)));
        return intval($stmt->fetchColumn()) > 0;
    }

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-06-29
     * Proposito: valida que la referencia de una regla exista en Catalogo/Proveedores segun su ambito.
     * Impacto: Garantias ERP; protege asignaciones por SKU/producto/categoria/marca/proveedor.
     */
    private function existeReferenciaRegla($db, $ambito, $idReferencia) {
        $idReferencia = intval($idReferencia);
        if ($idReferencia <= 0) {
            return false;
        }
        $consultas = array(
            "sku" => "SELECT COUNT(*) FROM erp_catalogo_skus WHERE id_sku=:id AND estatus IN ('activo','borrador','en_revision')",
            "producto" => "SELECT COUNT(*) FROM erp_catalogo_productos WHERE id_producto_erp=:id AND estatus<>'fusionado'",
            "categoria" => "SELECT COUNT(*) FROM erp_catalogo_categorias WHERE id_categoria_erp=:id AND estatus='activa' AND permite_productos=1",
            "marca" => "SELECT COUNT(*) FROM erp_catalogo_marcas WHERE id_marca_erp=:id AND estatus='activa'",
            "proveedor" => "SELECT COUNT(*) FROM erp_proveedores WHERE id_proveedor=:id"
        );
        if (!isset($consultas[$ambito])) {
            return false;
        }
        $stmt = $db->prepare($consultas[$ambito]);
        $stmt->execute(array(":id" => $idReferencia));
        return intval($stmt->fetchColumn()) > 0;
    }

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-06-29
     * Proposito: detecta reglas activas que se solapan en el mismo ambito, referencia y prioridad.
     * Impacto: Garantias ERP; evita ambiguedad operativa antes de persistir reglas.
     */
    private function buscarReglaSolapada($db, $datos) {
        $canal = trim((string) $this->valor($datos, "canal", ""));
        $idAlmacen = intval($this->valor($datos, "id_almacen", 0));
        $desde = trim((string) $this->valor($datos, "vigencia_desde", ""));
        $hasta = trim((string) $this->valor($datos, "vigencia_hasta", ""));
        $stmt = $db->prepare("SELECT id_regla_garantia
            FROM erp_garantias_politicas_reglas
            WHERE estatus='activa'
              AND id_regla_garantia<>:id_regla
              AND ambito=:ambito
              AND id_referencia=:referencia
              AND prioridad=:prioridad
              AND (COALESCE(canal, '')='' OR :canal_todo='' OR canal=:canal_valor)
              AND (COALESCE(id_almacen, 0)=0 OR :almacen_todo=0 OR id_almacen=:almacen_valor)
              AND (vigencia_desde IS NULL OR :hasta_nula IS NULL OR vigencia_desde<=:hasta_valor)
              AND (vigencia_hasta IS NULL OR :desde_nula IS NULL OR vigencia_hasta>=:desde_valor)
            ORDER BY prioridad ASC, id_regla_garantia ASC
            LIMIT 1");
        $stmt->execute(array(
            ":id_regla" => intval($this->valor($datos, "id_regla_garantia", 0)),
            ":ambito" => trim((string) $this->valor($datos, "ambito", "")),
            ":referencia" => intval($this->valor($datos, "id_referencia", 0)),
            ":prioridad" => intval($this->valor($datos, "prioridad", 100)),
            ":canal_todo" => $canal,
            ":canal_valor" => $canal,
            ":almacen_todo" => $idAlmacen,
            ":almacen_valor" => $idAlmacen,
            ":desde_nula" => $desde !== "" ? $desde : null,
            ":desde_valor" => $desde !== "" ? $desde : null,
            ":hasta_nula" => $hasta !== "" ? $hasta : null,
            ":hasta_valor" => $hasta !== "" ? $hasta : null
        ));
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        return $fila ?: null;
    }

    private function buscarReglasAplicables($db, $sku, $fecha, $canal, $idAlmacen) {
        $ambitos = array(
            array("ambito" => "sku", "id" => intval($sku["id_sku"])),
            array("ambito" => "producto", "id" => intval($sku["id_producto_erp"])),
            array("ambito" => "categoria", "id" => intval($sku["id_categoria_erp"])),
            array("ambito" => "marca", "id" => intval($sku["id_marca_erp"]))
        );
        $condiciones = array();
        $params = array(":fecha" => $fecha);

        foreach ($ambitos as $indice => $ambito) {
            if ($ambito["id"] <= 0) {
                continue;
            }
            $condiciones[] = "(r.ambito = :ambito{$indice} AND r.id_referencia = :referencia{$indice})";
            $params[":ambito{$indice}"] = $ambito["ambito"];
            $params[":referencia{$indice}"] = $ambito["id"];
        }

        if (empty($condiciones)) {
            return array();
        }

        $params[":canal"] = $canal;
        $params[":id_almacen"] = $idAlmacen;
        $sql = "SELECT r.*, p.codigo, p.nombre, p.descripcion, p.tipo_garantia, p.duracion_valor,
                       p.unidad_duracion, p.coberturas_json, p.requisitos_json, p.exclusiones_json,
                       p.requiere_ticket, p.requiere_cliente, p.requiere_serie, p.requiere_lote,
                       p.requiere_empaque, p.requiere_diagnostico, p.requiere_fotos,
                       p.requiere_autorizacion_supervisor, p.requiere_validacion_proveedor,
                       p.permite_cambio, p.permite_reparacion, p.permite_devolucion_dinero,
                       p.permite_nota_credito, p.permite_envio_proveedor,
                       CASE r.ambito
                         WHEN 'sku' THEN 1
                         WHEN 'producto' THEN 2
                         WHEN 'categoria' THEN 3
                         WHEN 'marca' THEN 4
                         WHEN 'proveedor' THEN 5
                         ELSE 99
                       END AS peso_ambito
                FROM erp_garantias_politicas_reglas r
                INNER JOIN erp_garantias_politicas p ON p.id_garantia_politica = r.id_garantia_politica
                WHERE r.estatus = 'activa'
                  AND p.estatus = 'activa'
                  AND (" . implode(" OR ", $condiciones) . ")
                  AND (r.vigencia_desde IS NULL OR r.vigencia_desde <= :fecha)
                  AND (r.vigencia_hasta IS NULL OR r.vigencia_hasta >= :fecha)
                  AND (r.canal IS NULL OR r.canal = '' OR r.canal = :canal)
                  AND (r.id_almacen IS NULL OR r.id_almacen = 0 OR r.id_almacen = :id_almacen)
                ORDER BY r.prioridad ASC, peso_ambito ASC, r.id_regla_garantia ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function reglasEmpatadas($reglas, $primera) {
        $empatadas = array();
        foreach ($reglas as $regla) {
            if (intval($regla["prioridad"]) === intval($primera["prioridad"]) && $regla["ambito"] === $primera["ambito"]) {
                $empatadas[] = $regla;
            }
        }
        return $empatadas;
    }

    private function politicaSinGarantia() {
        return array(
            "id_garantia_politica" => 0,
            "codigo" => "SIN_GARANTIA",
            "nombre" => "Sin garantia",
            "tipo_garantia" => "sin_garantia",
            "duracion_valor" => 0,
            "unidad_duracion" => "dias",
            "coberturas" => array(),
            "requisitos" => array(),
            "exclusiones" => array()
        );
    }

    private function normalizarPolitica($fila) {
        return array(
            "id_garantia_politica" => intval($fila["id_garantia_politica"]),
            "codigo" => $fila["codigo"],
            "nombre" => $fila["nombre"],
            "tipo_garantia" => $fila["tipo_garantia"],
            "duracion_valor" => intval($fila["duracion_valor"]),
            "unidad_duracion" => $fila["unidad_duracion"],
            "coberturas" => $this->jsonLista($fila["coberturas_json"]),
            "requisitos" => $this->jsonLista($fila["requisitos_json"]),
            "exclusiones" => $this->jsonLista($fila["exclusiones_json"]),
            "flags" => array(
                "requiere_ticket" => intval($fila["requiere_ticket"]),
                "requiere_cliente" => intval($fila["requiere_cliente"]),
                "requiere_serie" => intval($fila["requiere_serie"]),
                "requiere_lote" => intval($fila["requiere_lote"]),
                "requiere_empaque" => intval($fila["requiere_empaque"]),
                "requiere_diagnostico" => intval($fila["requiere_diagnostico"]),
                "requiere_fotos" => intval($fila["requiere_fotos"]),
                "requiere_autorizacion_supervisor" => intval($fila["requiere_autorizacion_supervisor"]),
                "requiere_validacion_proveedor" => intval($fila["requiere_validacion_proveedor"])
            )
        );
    }

    private function normalizarRegla($fila) {
        return array(
            "id_regla_garantia" => intval($fila["id_regla_garantia"]),
            "ambito" => $fila["ambito"],
            "id_referencia" => intval($fila["id_referencia"]),
            "prioridad" => intval($fila["prioridad"]),
            "canal" => $fila["canal"],
            "id_almacen" => intval($fila["id_almacen"]),
            "origen" => $fila["ambito"]
        );
    }

    private function snapshotSugerido($politica, $fecha) {
        $fechaInicio = $fecha ?: date("Y-m-d");
        $fechaVencimiento = null;
        $duracion = intval($politica["duracion_valor"]);
        $unidad = $politica["unidad_duracion"];

        if ($duracion > 0) {
            $intervalo = $unidad === "meses" ? "P" . $duracion . "M" : "P" . $duracion . "D";
            $dt = new DateTime($fechaInicio);
            $dt->add(new DateInterval($intervalo));
            $fechaVencimiento = $dt->format("Y-m-d");
        }

        return array(
            "fecha_inicio" => $fechaInicio,
            "fecha_vencimiento" => $fechaVencimiento,
            "resumen_ticket" => $politica["tipo_garantia"] === "sin_garantia"
                ? "Sin garantia"
                : trim($politica["nombre"] . " - " . $duracion . " " . $unidad)
        );
    }

    private function jsonLista($valor) {
        if ($valor === null || trim((string) $valor) === "") {
            return array();
        }
        $datos = json_decode($valor, true);
        return is_array($datos) ? $datos : array();
    }

    private function normalizarJsonTexto($valor) {
        if (is_array($valor)) {
            return json_encode(array_values($valor), JSON_UNESCAPED_UNICODE);
        }
        $texto = trim((string) $valor);
        if ($texto === "") {
            return null;
        }
        $json = json_decode($texto, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
            return json_encode($json, JSON_UNESCAPED_UNICODE);
        }
        return json_encode(array($texto), JSON_UNESCAPED_UNICODE);
    }

    private function decodificarItems($items) {
        if (is_string($items)) {
            $decodificado = json_decode($items, true);
            return is_array($decodificado) ? $decodificado : array();
        }
        return is_array($items) ? $items : array();
    }

    private function tablaExiste($db, $tabla) {
        $stmt = $db->prepare("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = :base AND TABLE_NAME = :tabla LIMIT 1");
        $stmt->execute(array(":base" => MYSQLBASE, ":tabla" => $tabla));
        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function valor($datos, $clave, $default = null) {
        return is_array($datos) && array_key_exists($clave, $datos) ? $datos[$clave] : $default;
    }

    private function respuesta($error, $tipo, $mensaje, $depurar = null) {
        return array("error" => $error, "tipo" => $tipo, "mensaje" => $mensaje, "depurar" => $depurar);
    }
}
