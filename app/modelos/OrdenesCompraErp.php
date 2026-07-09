<?php

class OrdenesCompraErp extends CRUD {

    private $detalleIncluyeIvaColumnaExiste = null;
    private $detalleDatosFiscalesColumnaExiste = null;
    private $detalleEvidenciaCostoColumnaExiste = null;

    public function generarDesdeSolicitud($idSolicitud, $idUsuario) {
        $db = $this->getConexion();
        try {
            $db->beginTransaction();
            $idOrden = $this->crearDesdeSolicitud($db, intval($idSolicitud), $idUsuario);
            $stmt = $db->prepare("SELECT d.id_detalle id_solicitud_detalle,
                d.id_sku_erp, d.sku, d.nombre_producto nombre,
                d.cantidad, d.costo_estimado costo_unitario,
                CASE WHEN COALESCE(i.iva_porcentaje,0) > 0 AND i.iva_porcentaje <= 1
                    THEN i.iva_porcentaje * 100 ELSE COALESCE(i.iva_porcentaje,0) END porcentaje_impuesto,
                0 descuento
                FROM erp_compras_solicitudes_detalle d
                LEFT JOIN erp_catalogo_sku_impuestos i ON i.id_sku=d.id_sku_erp
                WHERE d.id_solicitud=:id ORDER BY d.id_detalle");
            $stmt->execute(array(":id" => intval($idSolicitud)));
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (empty($items)) {
                throw new Exception("La solicitud aprobada no contiene partidas");
            }
            $stmt = $db->prepare("SELECT id_proveedor FROM erp_compras_ordenes WHERE id_orden_compra=:id");
            $stmt->execute(array(":id" => $idOrden));
            $idProveedor = intval($stmt->fetchColumn());
            $detalle = $this->validarDetalle($db, $items, $idProveedor, intval($idSolicitud));
            $this->insertarDetalle($db, $idOrden, $detalle);
            $subtotal = 0;
            $impuestos = 0;
            $total = 0;
            foreach ($detalle as $item) {
                $subtotal += $item["subtotal"];
                $impuestos += $item["impuesto"];
                $total += $item["total"];
            }
            if (round($total, 2) <= 0) {
                throw new Exception("El total de la orden debe ser mayor a cero");
            }
            $db->prepare("UPDATE erp_compras_ordenes SET subtotal=:subtotal,
                impuestos=:impuestos, total=:total, saldo_pendiente=:total,
                fecha_actualizacion=NOW() WHERE id_orden_compra=:id")->execute(array(
                    ":subtotal" => round($subtotal, 6),
                    ":impuestos" => round($impuestos, 6),
                    ":total" => round($total, 6),
                    ":id" => $idOrden
                ));
            $db->commit();
            return $this->respuesta(false, "success", "Orden generada desde la solicitud", array(
                "id_orden_compra" => $idOrden
            ));
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    public function compararConSolicitud($idOrden, $idSolicitud = 0) {
        try {
            $db = $this->getConexion();
            $idOrden = intval($idOrden);
            $idSolicitud = intval($idSolicitud);
            if ($idOrden <= 0 && $idSolicitud <= 0) {
                return $this->respuesta(true, "warning", "Selecciona una orden valida");
            }

            if ($idOrden <= 0 && $idSolicitud > 0) {
                $stmtUltimaOrden = $db->prepare("SELECT id_orden_compra
                    FROM erp_compras_ordenes
                    WHERE id_solicitud=:id_solicitud
                        AND COALESCE(estatus,'borrador') != 'cancelada'
                    ORDER BY id_orden_compra DESC
                    LIMIT 1");
                $stmtUltimaOrden->execute(array(":id_solicitud" => $idSolicitud));
                $idOrden = intval($stmtUltimaOrden->fetchColumn());
                if ($idOrden <= 0) {
                    return $this->respuesta(true, "warning", "No se encontro una orden activa para esta solicitud");
                }
            }

            $stmt = $db->prepare("SELECT id_orden_compra, id_solicitud, folio, estatus
                FROM erp_compras_ordenes
                WHERE id_orden_compra=:id");
            $stmt->execute(array(":id" => $idOrden));
            $orden = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$orden) {
                return $this->respuesta(true, "warning", "Orden de compra no encontrada");
            }
            if (intval($orden["id_solicitud"]) <= 0) {
                return $this->respuesta(true, "warning", "La orden no tiene solicitud de origen", array(
                    "id_orden_compra" => $idOrden
                ));
            }

            $stmt = $db->prepare("SELECT id_detalle, id_solicitud_detalle, id_sku_erp, sku, nombre_producto,
                    cantidad, costo_unitario
                FROM erp_compras_ordenes_detalle
                WHERE id_orden_compra=:id ORDER BY id_detalle");
            $stmt->execute(array(":id" => $idOrden));
            $detalleOrden = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $idSolicitud = intval($orden["id_solicitud"]);
            $stmt = $db->prepare("SELECT id_detalle, id_sku_erp, sku, nombre_producto, costo_estimado, cantidad
                FROM erp_compras_solicitudes_detalle
                WHERE id_solicitud=:id ORDER BY id_detalle");
            $stmt->execute(array(":id" => $idSolicitud));
            $detalleSolicitud = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $solicitudAgrupada = array();
            $ordenAgrupada = array();

            foreach ($detalleSolicitud as $fila) {
                $clave = $this->claveComparacionItemSolicitud($fila);
                if (!isset($solicitudAgrupada[$clave])) {
                    $solicitudAgrupada[$clave] = array(
                        "clave" => $clave,
                        "id_sku_erp" => intval(isset($fila["id_sku_erp"]) ? $fila["id_sku_erp"] : 0),
                        "sku" => trim(isset($fila["sku"]) ? $fila["sku"] : ""),
                        "nombre_producto" => trim(isset($fila["nombre_producto"]) ? $fila["nombre_producto"] : ""),
                        "cantidad" => 0,
                        "costo_unitario" => floatval(isset($fila["costo_estimado"]) ? $fila["costo_estimado"] : 0),
                        "id_solicitud_detalles" => array()
                    );
                }
                $solicitudAgrupada[$clave]["cantidad"] += round(floatval($fila["cantidad"]), 6);
                $solicitudAgrupada[$clave]["id_solicitud_detalles"][] = intval($fila["id_detalle"]);
            }

            foreach ($detalleOrden as $fila) {
                $clave = $this->claveComparacionItemOrden($fila);
                if (!isset($ordenAgrupada[$clave])) {
                    $ordenAgrupada[$clave] = array(
                        "clave" => $clave,
                        "id_sku_erp" => intval(isset($fila["id_sku_erp"]) ? $fila["id_sku_erp"] : 0),
                        "sku" => trim(isset($fila["sku"]) ? $fila["sku"] : ""),
                        "nombre_producto" => trim(isset($fila["nombre_producto"]) ? $fila["nombre_producto"] : ""),
                        "cantidad" => 0,
                        "costo_unitario" => floatval(isset($fila["costo_unitario"]) ? $fila["costo_unitario"] : 0),
                        "id_orden_detalles" => array()
                    );
                }
                $ordenAgrupada[$clave]["cantidad"] += round(floatval($fila["cantidad"]), 6);
                $ordenAgrupada[$clave]["id_orden_detalles"][] = intval($fila["id_detalle"]);
            }

            $faltantes = array();
            $adicionales = array();
            $cambios = array();

            foreach ($solicitudAgrupada as $clave => $solicitudItem) {
                if (!isset($ordenAgrupada[$clave])) {
                    $faltantes[] = array(
                        "tipo" => "faltante",
                        "origen" => "solicitud",
                        "clave" => $clave,
                        "id_sku_erp" => $solicitudItem["id_sku_erp"],
                        "sku" => $solicitudItem["sku"],
                        "nombre" => $solicitudItem["nombre_producto"],
                        "cantidad" => $solicitudItem["cantidad"],
                        "costo_unitario" => $solicitudItem["costo_unitario"],
                        "id_solicitud_detalles" => $solicitudItem["id_solicitud_detalles"]
                    );
                    continue;
                }

                $ordenItem = $ordenAgrupada[$clave];
                $diffCantidad = round($ordenItem["cantidad"] - $solicitudItem["cantidad"], 6);
                $diffCosto = round($ordenItem["costo_unitario"] - $solicitudItem["costo_unitario"], 6);
                if (abs($diffCantidad) > 0.000001 || abs($diffCosto) > 0.000001) {
                    $cambios[] = array(
                        "tipo" => "diferencia",
                        "origen" => "mixto",
                        "clave" => $clave,
                        "id_sku_erp" => $solicitudItem["id_sku_erp"],
                        "sku" => $solicitudItem["sku"],
                        "nombre" => $solicitudItem["nombre_producto"],
                        "solicitud" => array(
                            "cantidad" => $solicitudItem["cantidad"],
                            "costo_unitario" => $solicitudItem["costo_unitario"],
                            "id_detalles" => $solicitudItem["id_solicitud_detalles"]
                        ),
                        "orden" => array(
                            "cantidad" => $ordenItem["cantidad"],
                            "costo_unitario" => $ordenItem["costo_unitario"],
                            "id_detalles" => $ordenItem["id_orden_detalles"]
                        ),
                        "delta" => array(
                            "cantidad" => $diffCantidad,
                            "costo_unitario" => $diffCosto
                        )
                    );
                }
                unset($ordenAgrupada[$clave]);
            }

            foreach ($ordenAgrupada as $clave => $ordenItem) {
                $adicionales[] = array(
                    "tipo" => "adicional",
                    "origen" => "orden",
                    "clave" => $clave,
                    "id_sku_erp" => $ordenItem["id_sku_erp"],
                    "sku" => $ordenItem["sku"],
                    "nombre" => $ordenItem["nombre_producto"],
                    "cantidad" => $ordenItem["cantidad"],
                    "costo_unitario" => $ordenItem["costo_unitario"],
                    "id_orden_detalles" => $ordenItem["id_orden_detalles"]
                );
            }

            $resumen = array(
                "faltantes" => count($faltantes),
                "adicionales" => count($adicionales),
                "cambios" => count($cambios),
                "total_solicitud" => count($detalleSolicitud),
                "total_orden" => count($detalleOrden)
            );

            return $this->respuesta(false, "success", "Comparacion solicitada-orden generada", array(
                "orden" => $orden,
                "resumen" => $resumen,
                "faltantes" => $faltantes,
                "adicionales" => $adicionales,
                "cambios" => $cambios
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    public function catalogos() {
        try {
            $db = $this->getConexion();
            $almacenes = $db->query("SELECT id_almacen, almacen, contacto_recepcion,
                telefono_recepcion, calle, numero_exterior, numero_interior, colonia,
                ciudad, estado, codigo_postal
                FROM erp_almacenes
                WHERE COALESCE(estatus, 'activo')='activo'
                AND COALESCE(permite_recepcion,0)=1
                ORDER BY orden ASC, almacen ASC")->fetchAll(PDO::FETCH_ASSOC);
            $proveedores = $db->query("SELECT id_proveedor, proveedor
                FROM erp_proveedores ORDER BY proveedor")->fetchAll(PDO::FETCH_ASSOC);
            return $this->respuesta(false, "success", "Catalogos consultados", array(
                "almacenes" => $almacenes,
                "proveedores" => $proveedores
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    public function listar($filtros = array()) {
        try {
            $db = $this->getConexion();
            $where = array("1=1");
            $params = array();
            if (!empty($filtros["estatus"])) {
                $where[] = "o.estatus=:estatus";
                $params[":estatus"] = $filtros["estatus"];
            }
            if (!empty($filtros["q"])) {
                $where[] = "(o.folio LIKE :q OR o.folio_proveedor LIKE :q OR p.proveedor LIKE :q OR s.folio LIKE :q)";
                $params[":q"] = "%" . trim($filtros["q"]) . "%";
            }
            $stmt = $db->prepare("SELECT o.id_orden_compra, o.folio, o.folio_proveedor,
                o.id_solicitud, o.origen, s.folio folio_solicitud, p.proveedor, a.almacen,
                o.estatus, o.fecha_orden, o.fecha_entrega_estimada, o.total,
                COUNT(d.id_detalle) total_partidas,
                COALESCE(SUM(d.cantidad),0) total_unidades
                FROM erp_compras_ordenes o
                INNER JOIN erp_proveedores p ON p.id_proveedor=o.id_proveedor
                LEFT JOIN erp_compras_solicitudes s ON s.id_solicitud=o.id_solicitud
                LEFT JOIN erp_almacenes a ON a.id_almacen=o.id_almacen_destino
                LEFT JOIN erp_compras_ordenes_detalle d ON d.id_orden_compra=o.id_orden_compra
                WHERE " . implode(" AND ", $where) . "
                GROUP BY o.id_orden_compra
                ORDER BY o.id_orden_compra DESC LIMIT 500");
            $stmt->execute($params);
            return $this->respuesta(false, "success", "Ordenes consultadas", $stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    public function consultar($idOrden) {
        try {
            $db = $this->getConexion();
            $stmt = $db->prepare("SELECT o.*, p.proveedor, s.folio folio_solicitud,
                a.almacen
                FROM erp_compras_ordenes o
                INNER JOIN erp_proveedores p ON p.id_proveedor=o.id_proveedor
                LEFT JOIN erp_compras_solicitudes s ON s.id_solicitud=o.id_solicitud
                LEFT JOIN erp_almacenes a ON a.id_almacen=o.id_almacen_destino
                WHERE o.id_orden_compra=:id");
            $stmt->execute(array(":id" => intval($idOrden)));
            $orden = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$orden) {
                return $this->respuesta(true, "warning", "Orden de compra no encontrada");
            }
            /**
             * IA: Codex GPT-5
             * Fecha: 2026-06-25
             * Proposito: enriquecer el detalle de orden con la miniatura activa de Catalogo sin persistir imagenes en Compras.
             * Impacto: Compras solo consume la URL visual; Catalogo conserva la fuente de verdad de imagenes.
             */
            $stmt = $db->prepare("SELECT d.*, 
                COALESCE(NULLIF(TRIM(s.nombre), ''), d.nombre_producto) nombre_sku,
                COALESCE(NULLIF(TRIM(sp.sku_proveedor), ''), d.sku) sku_proveedor,
                (
                    SELECT img.url_imagen
                    FROM erp_catalogo_imagenes img
                    WHERE img.estatus = 'activo'
                      AND (img.id_sku = d.id_sku_erp OR (img.id_sku IS NULL AND img.id_producto_erp = s.id_producto_erp))
                    ORDER BY
                      CASE WHEN img.id_sku = d.id_sku_erp THEN 0 ELSE 1 END,
                      img.orden ASC,
                      img.id_imagen_erp ASC
                    LIMIT 1
                ) AS url_imagen
                FROM erp_compras_ordenes_detalle d
                LEFT JOIN erp_catalogo_skus s ON s.id_sku=d.id_sku_erp
                LEFT JOIN erp_catalogo_sku_proveedores sp ON sp.id_sku_proveedor=d.id_sku_proveedor
                WHERE d.id_orden_compra=:id ORDER BY d.id_detalle");
            $stmt->execute(array(":id" => intval($idOrden)));
            $detalle = $this->enriquecerDetalleConsultadoConProveedor($db, $stmt->fetchAll(PDO::FETCH_ASSOC), intval($orden["id_proveedor"]));
            return $this->respuesta(false, "success", "Orden consultada", array(
                "orden" => $orden,
                "detalle" => $detalle
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    private function enriquecerDetalleConsultadoConProveedor($db, $detalle, $idProveedor) {
        // [Codex: GPT-5 2026-06-16] Reconciliacion al cargar: si Catalogo/Proveedores ya resolvio el SKU, Compras lo ve sin recapturar.
        if (!is_array($detalle) || intval($idProveedor) <= 0) {
            return $detalle;
        }

        foreach ($detalle as $idx => $item) {
            $tipoItem = isset($item["tipo_item"]) ? strtolower(trim((string) $item["tipo_item"])) : "";
            if ($this->esTipoItemNoInventariable($tipoItem)) {
                continue;
            }

            $idSku = intval(isset($item["id_sku_erp"]) ? $item["id_sku_erp"] : 0);
            $idSkuProveedor = intval(isset($item["id_sku_proveedor"]) ? $item["id_sku_proveedor"] : 0);
            if ($idSku > 0 && $idSkuProveedor > 0) {
                continue;
            }

            $match = $this->buscarRelacionProveedorParaDetalleConsultado($db, intval($idProveedor), $item);
            if (!$match) {
                continue;
            }

            $detalle[$idx]["id_producto"] = intval($match["id_producto_erp"]);
            $detalle[$idx]["id_sku_erp"] = intval($match["id_sku"]);
            $detalle[$idx]["id_sku_proveedor"] = intval($match["id_sku_proveedor"]);
            $detalle[$idx]["nombre_sku"] = $match["nombre"];
            $detalle[$idx]["sku"] = $match["sku"];
            $detalle[$idx]["sku_proveedor"] = $match["sku_proveedor"] !== "" ? $match["sku_proveedor"] : $match["sku"];
            $detalle[$idx]["unidad"] = $match["unidad"] !== "" ? $match["unidad"] : (isset($item["unidad"]) ? $item["unidad"] : "Pza");
            $detalle[$idx]["tipo_item"] = "producto";
            $detalle[$idx]["producto_registrado"] = 1;
            $detalle[$idx]["requiere_revision"] = 0;
            $detalle[$idx]["deteccion_catalogo"] = "relacion_proveedor_detectada_al_consultar";
        }

        return $detalle;
    }

    private function buscarRelacionProveedorParaDetalleConsultado($db, $idProveedor, $item) {
        $idSku = intval(isset($item["id_sku_erp"]) ? $item["id_sku_erp"] : 0);
        $sku = trim((string) (isset($item["sku"]) ? $item["sku"] : ""));
        $skuProveedor = trim((string) (isset($item["sku_proveedor"]) ? $item["sku_proveedor"] : ""));
        $nombre = trim((string) (isset($item["nombre_producto"]) ? $item["nombre_producto"] : ""));
        if ($nombre === "") {
            $nombre = trim((string) (isset($item["nombre_sku"]) ? $item["nombre_sku"] : ""));
        }

        if ($idSku > 0) {
            $stmt = $db->prepare("SELECT s.id_sku, s.id_producto_erp, s.sku, s.nombre,
                    COALESCE(u.abreviatura, '') unidad, sp.id_sku_proveedor,
                    COALESCE(sp.sku_proveedor, '') sku_proveedor
                FROM erp_catalogo_sku_proveedores sp
                INNER JOIN erp_catalogo_skus s ON s.id_sku=sp.id_sku AND s.estatus='activo'
                LEFT JOIN erp_catalogo_unidades u ON u.id_unidad=s.id_unidad_base
                WHERE sp.id_proveedor=:proveedor
                  AND sp.id_sku=:sku
                  AND sp.estatus='activo'
                ORDER BY sp.es_preferido DESC, sp.id_sku_proveedor DESC
                LIMIT 2");
            $stmt->execute(array(":proveedor" => intval($idProveedor), ":sku" => $idSku));
            $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return count($matches) === 1 ? $matches[0] : null;
        }

        if ($sku === "" && $skuProveedor === "" && $nombre === "") {
            return null;
        }

        $stmt = $db->prepare("SELECT s.id_sku, s.id_producto_erp, s.sku, s.nombre,
                COALESCE(u.abreviatura, '') unidad, sp.id_sku_proveedor,
                COALESCE(sp.sku_proveedor, '') sku_proveedor,
                CASE
                    WHEN :sku_proveedor_case <> '' AND LOWER(TRIM(sp.sku_proveedor)) = LOWER(TRIM(:sku_proveedor_val_case)) THEN 1
                    WHEN :sku_case <> '' AND LOWER(TRIM(sp.sku_proveedor)) = LOWER(TRIM(:sku_sp_val_case)) THEN 2
                    WHEN :sku_catalogo_case <> '' AND LOWER(TRIM(s.sku)) = LOWER(TRIM(:sku_catalogo_val_case)) THEN 3
                    WHEN :nombre_case <> '' AND LOWER(TRIM(s.nombre)) = LOWER(TRIM(:nombre_val_case)) THEN 4
                    ELSE 9
                END prioridad
            FROM erp_catalogo_sku_proveedores sp
            INNER JOIN erp_catalogo_skus s ON s.id_sku=sp.id_sku AND s.estatus='activo'
            LEFT JOIN erp_catalogo_unidades u ON u.id_unidad=s.id_unidad_base
                WHERE sp.id_proveedor=:proveedor
                  AND sp.estatus='activo'
                  AND (
                (:sku_proveedor_cmp <> '' AND LOWER(TRIM(sp.sku_proveedor)) = LOWER(TRIM(:sku_proveedor_val)))
                OR (:sku_cmp <> '' AND LOWER(TRIM(sp.sku_proveedor)) = LOWER(TRIM(:sku_sp_val)))
                OR (:sku_catalogo_cmp <> '' AND LOWER(TRIM(s.sku)) = LOWER(TRIM(:sku_catalogo_val)))
                OR (:nombre_cmp <> '' AND LOWER(TRIM(s.nombre)) = LOWER(TRIM(:nombre_val)))
                OR (:sku_norm_cmp <> '' AND LOWER(REPLACE(REPLACE(TRIM(sp.sku_proveedor), '-', ''), ' ', '')) = LOWER(:sku_norm_val))
                OR (:sku_norm_catalogo_cmp <> '' AND LOWER(REPLACE(REPLACE(TRIM(s.sku), '-', ''), ' ', '')) = LOWER(:sku_norm_catalogo_val))
              )
            ORDER BY prioridad ASC, sp.es_preferido DESC, sp.id_sku_proveedor DESC
            LIMIT 2");
        $skuNormalizado = preg_replace("/[\s-]+/", "", $sku);
        $skuProveedorNormalizado = preg_replace("/[\s-]+/", "", $skuProveedor);
        $skuNormalizadoBusqueda = $skuProveedorNormalizado !== "" ? $skuProveedorNormalizado : $skuNormalizado;
        $stmt->execute(array(
            ":proveedor" => intval($idProveedor),
            ":sku_proveedor_case" => $skuProveedor,
            ":sku_proveedor_val_case" => $skuProveedor,
            ":sku_case" => $sku,
            ":sku_sp_val_case" => $sku,
            ":sku_catalogo_case" => $sku,
            ":sku_catalogo_val_case" => $sku,
            ":nombre_case" => $nombre,
            ":nombre_val_case" => $nombre,
            ":sku_proveedor_cmp" => $skuProveedor,
            ":sku_proveedor_val" => $skuProveedor,
            ":sku_cmp" => $sku,
            ":sku_sp_val" => $sku,
            ":sku_catalogo_cmp" => $sku,
            ":sku_catalogo_val" => $sku,
            ":nombre_cmp" => $nombre,
            ":nombre_val" => $nombre,
            ":sku_norm_cmp" => $skuNormalizadoBusqueda,
            ":sku_norm_val" => $skuNormalizadoBusqueda,
            ":sku_norm_catalogo_cmp" => $skuNormalizado,
            ":sku_norm_catalogo_val" => $skuNormalizado
        ));
        $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return count($matches) === 1 ? $matches[0] : null;
    }

    public function guardar($datos, $idUsuario) {
        $idOrden = intval(isset($datos["id_orden_compra"]) ? $datos["id_orden_compra"] : 0);
        $idSolicitud = intval(isset($datos["id_solicitud"]) ? $datos["id_solicitud"] : 0);
        $idProveedor = intval(isset($datos["id_proveedor"]) ? $datos["id_proveedor"] : 0);
        $estatusDestino = isset($datos["estatus"]) && $datos["estatus"] === "enviada" ? "enviada" : "borrador";
        $items = isset($datos["items"]) ? $datos["items"] : array();
        if (is_string($items)) {
            $items = json_decode($items, true);
        }
        $db = $this->getConexion();
        try {
            $db->beginTransaction();
            if ($idOrden <= 0) {
                $idOrden = $this->crearDirecta($db, $idProveedor, $idUsuario);
            }

            $stmt = $db->prepare("SELECT * FROM erp_compras_ordenes WHERE id_orden_compra=:id FOR UPDATE");
            $stmt->execute(array(":id" => $idOrden));
            $orden = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$orden) {
                throw new Exception("Orden de compra no encontrada");
            }
            if ($orden["estatus"] !== "borrador") {
                throw new Exception("Solo las ordenes en borrador permiten edicion");
            }
            if (!is_array($items) || empty($items)) {
                throw new Exception("La orden debe contener al menos una partida");
            }

            $detalle = $this->validarDetalle(
                $db,
                $items,
                intval($orden["id_proveedor"]),
                intval($orden["id_solicitud"]),
                $idOrden
            );
            $subtotal = 0;
            $impuestos = 0;
            $total = 0;
            foreach ($detalle as $item) {
                $subtotal += $item["subtotal"];
                $impuestos += $item["impuesto"];
                $total += $item["total"];
            }
            $monedaOrden = strtoupper(trim((string) (isset($datos["moneda"]) ? $datos["moneda"] : "MXN")));
            $monedaOrden = in_array($monedaOrden, array("MXN", "USD", "EUR"), true) ? $monedaOrden : "";
            $tipoCambioOrden = floatval(isset($datos["tipo_cambio"]) ? $datos["tipo_cambio"] : 1);
            if ($estatusDestino === "enviada") {
                if ($monedaOrden === "") {
                    throw new Exception("Selecciona una moneda valida antes de enviar la orden");
                }
                if ($monedaOrden !== "MXN" && $tipoCambioOrden <= 0) {
                    throw new Exception("Captura un tipo de cambio valido antes de enviar una orden en moneda extranjera");
                }
                if (round($total, 2) <= 0) {
                    throw new Exception("El total de la orden debe ser mayor a cero para poder enviarla");
                }
                foreach ($detalle as $item) {
                    if (round(floatval(isset($item["costo"]) ? $item["costo"] : 0), 6) <= 0) {
                        throw new Exception("No puedes enviar una orden con partidas sin costo unitario");
                    }
                }
                $this->validarPoliticaEnvioOrdenErp($detalle);
            }
            $advertenciasOperativas = $estatusDestino === "enviada"
                ? $this->advertenciasOperativasEnvioOrden($db, $detalle, intval($orden["id_proveedor"]))
                : array();
            $stmt = $db->prepare("SELECT
                COALESCE((SELECT SUM(monto) FROM erp_compras_ordenes_pagos
                    WHERE id_orden_compra=:orden_pago
                    AND estado_pago IN ('aplicado','conciliado')),0) +
                COALESCE((SELECT SUM(monto) FROM erp_compras_ordenes_notas_credito
                    WHERE id_orden_compra=:orden_nota
                    AND estatus='aplicada'),0)");
            $stmt->execute(array(":orden_pago" => $idOrden, ":orden_nota" => $idOrden));
            $totalAplicado = round(floatval($stmt->fetchColumn()), 2);
            if (round($total, 2) + 0.009 < $totalAplicado) {
                throw new Exception("El total de la orden no puede ser menor al total financiero aplicado");
            }
            $idAlmacen = intval(isset($datos["id_almacen_destino"]) ? $datos["id_almacen_destino"] : 0);
            if ($estatusDestino === "enviada" && $idAlmacen <= 0) {
                throw new Exception("Selecciona el almacen de destino antes de enviar");
            }
            if ($estatusDestino === "enviada") {
                $this->validarProveedorPuedeEnviarOrden($db, intval($orden["id_proveedor"]));
            }
            if ($idAlmacen > 0) {
                $stmt = $db->prepare("SELECT * FROM erp_almacenes
                    WHERE id_almacen=:id AND COALESCE(estatus,'activo')='activo'");
                $stmt->execute(array(":id" => $idAlmacen));
                $almacen = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$almacen) {
                    throw new Exception("Almacen de destino no valido o inactivo");
                }
                if (intval(isset($almacen["permite_recepcion"]) ? $almacen["permite_recepcion"] : 0) !== 1) {
                    throw new Exception("El almacen de destino no permite recepcion de compras");
                }
            }

            $stmt = $db->prepare("UPDATE erp_compras_ordenes SET folio_proveedor=:folio_proveedor,
                id_almacen_destino=:almacen, fecha_entrega_estimada=:entrega,
                observaciones=:observaciones, contacto_recepcion=:contacto,
                telefono_recepcion=:telefono, direccion_entrega=:direccion,
                moneda=:moneda, tipo_cambio=:tipo_cambio, subtotal=:subtotal,
                impuestos=:impuestos, total=:total,
                estatus=:estatus, enviado_por=CASE WHEN :enviada='enviada' THEN :usuario ELSE enviado_por END,
                fecha_envio=CASE WHEN :enviada2='enviada' THEN NOW() ELSE fecha_envio END,
                fecha_actualizacion=NOW()
                WHERE id_orden_compra=:id");
            $stmt->execute(array(
                ":folio_proveedor" => trim(isset($datos["folio_proveedor"]) ? $datos["folio_proveedor"] : ""),
                ":almacen" => $idAlmacen ?: null,
                ":entrega" => !empty($datos["fecha_entrega_estimada"]) ? $datos["fecha_entrega_estimada"] : null,
                ":observaciones" => trim(isset($datos["observaciones"]) ? $datos["observaciones"] : ""),
                ":contacto" => trim(isset($datos["contacto_recepcion"]) ? $datos["contacto_recepcion"] : ""),
                ":telefono" => trim(isset($datos["telefono_recepcion"]) ? $datos["telefono_recepcion"] : ""),
                ":direccion" => trim(isset($datos["direccion_entrega"]) ? $datos["direccion_entrega"] : ""),
                ":moneda" => $monedaOrden !== "" ? $monedaOrden : "MXN",
                ":tipo_cambio" => max(0.000001, $tipoCambioOrden),
                ":subtotal" => round($subtotal, 6), ":impuestos" => round($impuestos, 6),
                ":total" => round($total, 6),
                ":estatus" => $estatusDestino, ":enviada" => $estatusDestino,
                ":enviada2" => $estatusDestino, ":usuario" => intval($idUsuario) ?: null,
                ":id" => $idOrden
            ));
            $this->sincronizarDetalle($db, $idOrden, $detalle);
            $this->registrarIncidenciasCatalogoDesdeOrden($db, $idOrden, intval($orden["id_proveedor"]), $detalle, $idUsuario);
            $this->registrarNotificacionesOperativasDesdeOrden($db, $orden, $idOrden, intval($orden["id_proveedor"]), $detalle, $idUsuario, $estatusDestino);
            $db->commit();
            return $this->respuesta(false, "success", $estatusDestino === "enviada"
                ? "Orden enviada y lista para recepcion" : "Borrador de orden guardado", array(
                    "id_orden_compra" => $idOrden,
                    "estatus" => $estatusDestino,
                    "total" => round($total, 6),
                    "advertencias_operativas" => $advertenciasOperativas
                ));
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    public function cancelar($idOrden, $idUsuario) {
        $db = $this->getConexion();
        try {
            $db->beginTransaction();
            $stmt = $db->prepare("SELECT * FROM erp_compras_ordenes WHERE id_orden_compra=:id FOR UPDATE");
            $stmt->execute(array(":id" => intval($idOrden)));
            $orden = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$orden || !in_array($orden["estatus"], array("borrador", "enviada"), true)) {
                throw new Exception("La orden ya no puede cancelarse");
            }
            $stmt = $db->prepare("SELECT COALESCE(SUM(cantidad_recibida),0) recibido
                FROM erp_compras_ordenes_detalle WHERE id_orden_compra=:id");
            $stmt->execute(array(":id" => intval($idOrden)));
            if (floatval($stmt->fetchColumn()) > 0) {
                throw new Exception("No se puede cancelar una orden con productos recibidos");
            }
            $stmt = $db->prepare("SELECT COUNT(*) FROM erp_almacen_recepciones
                WHERE id_orden_compra=:id AND estatus NOT IN ('pendiente','cancelada')");
            $stmt->execute(array(":id" => intval($idOrden)));
            if (intval($stmt->fetchColumn()) > 0) {
                throw new Exception("No se puede cancelar una orden con recepcion de almacen iniciada");
            }
            $stmt = $db->prepare("SELECT
                COALESCE((SELECT SUM(monto) FROM erp_compras_ordenes_pagos
                    WHERE id_orden_compra=:orden_pago
                    AND estado_pago IN ('aplicado','conciliado')),0) +
                COALESCE((SELECT SUM(monto) FROM erp_compras_ordenes_notas_credito
                    WHERE id_orden_compra=:orden_nota
                    AND estatus='aplicada'),0)");
            $stmt->execute(array(":orden_pago" => intval($idOrden), ":orden_nota" => intval($idOrden)));
            if (round(floatval($stmt->fetchColumn()), 2) > 0) {
                throw new Exception("No se puede cancelar una orden con pagos o notas aplicadas");
            }
            $db->prepare("UPDATE erp_compras_ordenes SET estatus='cancelada',
                fecha_actualizacion=NOW() WHERE id_orden_compra=:id")
                ->execute(array(":id" => intval($idOrden)));
            $db->prepare("UPDATE erp_compras_solicitudes SET estatus='aprobada',
                fecha_actualizacion=NOW() WHERE id_solicitud=:solicitud AND estatus='orden_generada'")
                ->execute(array(":solicitud" => intval($orden["id_solicitud"])));
            $db->prepare("UPDATE erp_almacen_recepciones SET estatus='cancelada',
                fecha_actualizacion=NOW() WHERE id_orden_compra=:id AND estatus='pendiente'")
                ->execute(array(":id" => intval($idOrden)));
            $db->commit();
            return $this->respuesta(false, "success", "Orden cancelada", array(
                "id_orden_compra" => intval($idOrden),
                "id_usuario" => intval($idUsuario)
            ));
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    public function cerrarCostos($idOrden, $idUsuario) {
        $db = $this->getConexion();
        try {
            $db->beginTransaction();
            $stmt = $db->prepare("SELECT * FROM erp_compras_ordenes WHERE id_orden_compra=:id FOR UPDATE");
            $stmt->execute(array(":id" => intval($idOrden)));
            $orden = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$orden) {
                throw new Exception("Orden de compra no encontrada");
            }
            if (in_array($orden["estatus"], array("borrador", "cancelada"), true)) {
                throw new Exception("Solo se pueden consolidar costos de ordenes enviadas o recibidas");
            }

            $incluyeIvaSelect = $this->detalleIncluyeIvaColumnaExiste($db) ? "d.costo_unitario_incluye_impuesto" : "0";
            $stmt = $db->prepare("SELECT d.id_detalle, d.id_sku_erp, d.id_sku_proveedor, d.sku, d.nombre_producto,
                    d.cantidad, d.cantidad_recibida, d.costo_unitario, {$incluyeIvaSelect} costo_unitario_incluye_impuesto,
                    d.porcentaje_impuesto, o.moneda, o.tipo_cambio
                FROM erp_compras_ordenes_detalle d
                INNER JOIN erp_compras_ordenes o ON o.id_orden_compra = d.id_orden_compra
                WHERE d.id_orden_compra=:id
                ORDER BY d.id_detalle");
            $stmt->execute(array(":id" => intval($idOrden)));
            $detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (empty($detalles)) {
                throw new Exception("La orden no tiene partidas");
            }

            $pendientes = array();
            $porSku = array();
            foreach ($detalles as $detalle) {
                $idSku = intval(isset($detalle["id_sku_erp"]) ? $detalle["id_sku_erp"] : 0);
                $cantidad = floatval(isset($detalle["cantidad"]) ? $detalle["cantidad"] : 0);
                $recibida = floatval(isset($detalle["cantidad_recibida"]) ? $detalle["cantidad_recibida"] : 0);
                $costo = floatval(isset($detalle["costo_unitario"]) ? $detalle["costo_unitario"] : 0);
                if ($idSku <= 0) {
                    $pendientes[] = "Partida sin SKU ERP: " . (isset($detalle["nombre_producto"]) ? $detalle["nombre_producto"] : "");
                    continue;
                }
                if ($cantidad <= 0) {
                    $pendientes[] = "SKU " . $idSku . " sin cantidad comprometida";
                    continue;
                }
                if ($costo <= 0) {
                    $pendientes[] = "SKU " . $idSku . " sin costo unitario valido";
                    continue;
                }
                $costoBaseMxn = $this->costoBaseMxnDetalleOrden($detalle);
                if ($costoBaseMxn <= 0) {
                    $pendientes[] = "SKU " . $idSku . " sin costo base MXN calculable";
                    continue;
                }
                if (!isset($porSku[$idSku])) {
                    $porSku[$idSku] = array(
                        "id_sku" => $idSku,
                        "cantidad_comprometida" => 0,
                        "importe_base_mxn" => 0,
                        "partidas" => 0
                    );
                }
                $porSku[$idSku]["cantidad_comprometida"] += $cantidad;
                $porSku[$idSku]["importe_base_mxn"] += $costoBaseMxn * $cantidad;
                $porSku[$idSku]["partidas"]++;
            }

            if (!empty($pendientes)) {
                throw new Exception("La orden aun no puede cerrar costos: " . implode("; ", array_slice($pendientes, 0, 5)));
            }
            if (empty($porSku)) {
                throw new Exception("No hay SKUs recibidos con costo para cerrar");
            }

            $stmtSku = $db->prepare("SELECT id_sku, sku, nombre, costo_referencia FROM erp_catalogo_skus WHERE id_sku=:id_sku AND estatus <> 'fusionado' FOR UPDATE");
            $stmtUpdate = $db->prepare("UPDATE erp_catalogo_skus SET costo_referencia=:costo, fecha_actualizacion=CURRENT_TIMESTAMP WHERE id_sku=:id_sku");
            $aplicados = array();
            foreach ($porSku as $idSku => $grupo) {
                $stmtSku->execute(array(":id_sku" => $idSku));
                $sku = $stmtSku->fetch(PDO::FETCH_ASSOC);
                if (!$sku) {
                    throw new Exception("SKU ERP " . $idSku . " no encontrado");
                }
                $costoOrden = $grupo["cantidad_comprometida"] > 0 ? $grupo["importe_base_mxn"] / $grupo["cantidad_comprometida"] : 0;
                if ($costoOrden <= 0) {
                    throw new Exception("Costo calculado invalido para SKU " . $idSku);
                }
                $promedioHistorico = $this->calcularCostoPromedioHistoricoSku($db, $idSku);
                $stmtUpdate->execute(array(
                    ":costo" => round($costoOrden, 6),
                    ":id_sku" => $idSku
                ));
                $aplicados[] = array(
                    "id_sku" => $idSku,
                    "sku" => isset($sku["sku"]) ? $sku["sku"] : "",
                    "nombre" => isset($sku["nombre"]) ? $sku["nombre"] : "",
                    "costo_anterior" => floatval(isset($sku["costo_referencia"]) ? $sku["costo_referencia"] : 0),
                    "costo_referencia_nuevo" => round($costoOrden, 6),
                    "costo_promedio_historico" => $promedioHistorico,
                    "cantidad_comprometida_orden" => round($grupo["cantidad_comprometida"], 6),
                    "partidas" => intval($grupo["partidas"])
                );
            }

            $db->commit();
            return $this->respuesta(false, "success", "Costos de compra consolidados", array(
                "id_orden_compra" => intval($idOrden),
                "id_usuario" => intval($idUsuario),
                "estatus_orden" => isset($orden["estatus"]) ? $orden["estatus"] : "",
                "skus_actualizados" => count($aplicados),
                "reglas" => array(
                    "La orden debe estar enviada o recibida.",
                    "El costo se toma del snapshot comprometido de la orden enviada.",
                    "El costo usa el snapshot neto guardado en la orden.",
                    "Si la orden no esta en MXN se usa el tipo de cambio guardado en la orden.",
                    "El costo promedio historico se calcula como indicador, no se guarda en esquema nuevo."
                ),
                "costos" => $aplicados
            ));
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    private function crearDesdeSolicitud($db, $idSolicitud, $idUsuario) {
        if ($idSolicitud <= 0) {
            throw new Exception("Selecciona una solicitud aprobada");
        }
        $stmt = $db->prepare("SELECT s.* FROM erp_compras_solicitudes s
            WHERE s.id_solicitud=:id FOR UPDATE");
        $stmt->execute(array(":id" => $idSolicitud));
        $solicitud = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$solicitud || $solicitud["estatus"] !== "aprobada") {
            throw new Exception("La solicitud ya no esta disponible para generar orden");
        }
        $stmt = $db->prepare("SELECT id_orden_compra FROM erp_compras_ordenes
            WHERE id_solicitud=:id AND estatus<>'cancelada' LIMIT 1");
        $stmt->execute(array(":id" => $idSolicitud));
        if ($stmt->fetchColumn()) {
            throw new Exception("La solicitud ya tiene una orden activa");
        }
        $stmt = $db->prepare("INSERT INTO erp_compras_ordenes
            (folio, id_proveedor, id_solicitud, subtotal, impuestos, total, estatus,
            fecha_orden, fecha_entrega_estimada, id_almacen_destino, observaciones, moneda, tipo_cambio,
            creado_por, fecha_actualizacion, origen)
            VALUES (NULL, :proveedor, :solicitud, 0, 0, 0, 'borrador', NOW(),
            :entrega, :almacen, :observaciones, 'MXN', 1, :usuario, NOW(), 'solicitud')");
        $stmt->execute(array(
            ":proveedor" => intval($solicitud["id_proveedor"]),
            ":solicitud" => $idSolicitud,
            ":entrega" => $solicitud["fecha_requerida"],
            ":almacen" => intval(isset($solicitud["id_almacen_destino"]) ? $solicitud["id_almacen_destino"] : 0) ?: null,
            ":observaciones" => $solicitud["observaciones"],
            ":usuario" => intval($idUsuario) ?: null
        ));
        $idOrden = intval($db->lastInsertId());
        $folio = "OC-" . date("Y") . "-" . str_pad($idOrden, 6, "0", STR_PAD_LEFT);
        $db->prepare("UPDATE erp_compras_ordenes SET folio=:folio WHERE id_orden_compra=:id")
            ->execute(array(":folio" => $folio, ":id" => $idOrden));
        $db->prepare("UPDATE erp_compras_solicitudes SET estatus='orden_generada',
            fecha_actualizacion=NOW() WHERE id_solicitud=:id")
            ->execute(array(":id" => $idSolicitud));
        return $idOrden;
    }

    private function normalizarTextoLower($valor) {
        return strtolower(trim((string) ($valor === null ? "" : $valor)));
    }

    private function claveComparacionItemSolicitud($item) {
        $idSku = intval(isset($item["id_sku_erp"]) ? $item["id_sku_erp"] : 0);
        if ($idSku > 0) {
            return "sku:" . $idSku;
        }
        $sku = $this->normalizarTextoLower(isset($item["sku"]) ? $item["sku"] : "");
        if ($sku !== "") {
            return "sku_txt:" . $sku;
        }
        return "nom:" . $this->normalizarTextoLower(isset($item["nombre_producto"]) ? $item["nombre_producto"] : "");
    }

    private function claveComparacionItemOrden($item) {
        $idSku = intval(isset($item["id_sku_erp"]) ? $item["id_sku_erp"] : 0);
        if ($idSku > 0) {
            return "sku:" . $idSku;
        }
        $sku = $this->normalizarTextoLower(isset($item["sku"]) ? $item["sku"] : "");
        if ($sku !== "") {
            return "sku_txt:" . $sku;
        }
        return "nom:" . $this->normalizarTextoLower(isset($item["nombre_producto"]) ? $item["nombre_producto"] : "");
    }

    private function crearDirecta($db, $idProveedor, $idUsuario) {
        if ($idProveedor <= 0) {
            throw new Exception("Selecciona un proveedor para la compra directa");
        }
        $stmt = $db->prepare("SELECT id_proveedor FROM erp_proveedores WHERE id_proveedor=:id");
        $stmt->execute(array(":id" => $idProveedor));
        if (!$stmt->fetchColumn()) {
            throw new Exception("Proveedor no valido");
        }
        $stmt = $db->prepare("INSERT INTO erp_compras_ordenes
            (folio, id_proveedor, id_solicitud, subtotal, impuestos, total, estatus,
            fecha_orden, moneda, tipo_cambio, creado_por, fecha_actualizacion, origen)
            VALUES (NULL, :proveedor, NULL, 0, 0, 0, 'borrador', NOW(), 'MXN', 1,
            :usuario, NOW(), 'directa')");
        $stmt->execute(array(
            ":proveedor" => $idProveedor,
            ":usuario" => intval($idUsuario) ?: null
        ));
        $idOrden = intval($db->lastInsertId());
        $folio = "OC-" . date("Y") . "-" . str_pad($idOrden, 6, "0", STR_PAD_LEFT);
        $db->prepare("UPDATE erp_compras_ordenes SET folio=:folio WHERE id_orden_compra=:id")
            ->execute(array(":folio" => $folio, ":id" => $idOrden));
        return $idOrden;
    }

    private function validarProveedorPuedeEnviarOrden($db, $idProveedor) {
        $stmt = $db->prepare("SELECT id_proveedor, proveedor, estatus_erp
            FROM erp_proveedores
            WHERE id_proveedor=:id_proveedor
            LIMIT 1");
        $stmt->execute(array(":id_proveedor" => intval($idProveedor)));
        $proveedor = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$proveedor) {
            throw new Exception("Proveedor no valido");
        }
        $estatus = trim((string) (isset($proveedor["estatus_erp"]) ? $proveedor["estatus_erp"] : ""));
        if (in_array($estatus, array("suspendido", "bloqueado", "inactivo"), true)) {
            throw new Exception("No puedes enviar la orden porque el proveedor esta " . $estatus);
        }
    }

    private function validarDetalle($db, $items, $idProveedor, $idSolicitud, $idOrden = 0) {
        $detalle = array();
            $ids = array();
            foreach ($items as $item) {
                $idSku = intval(isset($item["id_sku_erp"]) ? $item["id_sku_erp"] : 0);
                $skuTexto = trim(isset($item["sku"]) ? (string) $item["sku"] : "");
            if ($skuTexto === "") {
                $skuTexto = trim(isset($item["sku_proveedor"]) ? (string) $item["sku_proveedor"] : "");
            }
            $cantidad = round(floatval(isset($item["cantidad"]) ? $item["cantidad"] : 0), 6);
            $costo = round(floatval(isset($item["costo_unitario"]) ? $item["costo_unitario"] : 0), 6);
                $impuestoPct = round(floatval(isset($item["porcentaje_impuesto"]) ? $item["porcentaje_impuesto"] : 0), 6);
                $incluyeIva = $this->normalizarFlag(isset($item["costo_unitario_incluye_impuesto"])
                    ? $item["costo_unitario_incluye_impuesto"] : false);
                $descuento = round(floatval(isset($item["descuento"]) ? $item["descuento"] : 0), 6);
                $datosFiscalesBruto = isset($item["datos_fiscales"]) ? $item["datos_fiscales"] : "{}";
                if (is_string($datosFiscalesBruto)) {
                    $jsonTemp = json_decode($datosFiscalesBruto, true);
                    if (is_array($jsonTemp)) {
                        $datosFiscalesBruto = $jsonTemp;
                    }
                } elseif (isset($item["datos_fiscales_json"])) {
                    $jsonTemp = json_decode($item["datos_fiscales_json"], true);
                    if (is_array($jsonTemp)) {
                        $datosFiscalesBruto = $jsonTemp;
                    }
                }
                $datosFiscales = array(
                    "clave_sat" => isset($datosFiscalesBruto["clave_sat"]) ? trim((string) $datosFiscalesBruto["clave_sat"]) : "",
                    "clave_unidad_sat" => isset($datosFiscalesBruto["clave_unidad_sat"]) ? trim((string) $datosFiscalesBruto["clave_unidad_sat"]) : "",
                    "unidad" => isset($datosFiscalesBruto["unidad"]) ? trim((string) $datosFiscalesBruto["unidad"]) : "",
                    "objeto_impuesto" => isset($datosFiscalesBruto["objeto_impuesto"]) ? trim((string) $datosFiscalesBruto["objeto_impuesto"]) : "",
                    "tipo_impuesto" => isset($datosFiscalesBruto["tipo_impuesto"]) ? trim((string) $datosFiscalesBruto["tipo_impuesto"]) : "",
                    "porcentaje_iva" => round(floatval(isset($datosFiscalesBruto["porcentaje_iva"]) ? $datosFiscalesBruto["porcentaje_iva"] : 0), 6),
                    "porcentaje_ieps" => round(floatval(isset($datosFiscalesBruto["porcentaje_ieps"]) ? $datosFiscalesBruto["porcentaje_ieps"] : 0), 6),
                    "incluye_iva" => $this->normalizarFlag(isset($datosFiscalesBruto["incluye_iva"]) ? $datosFiscalesBruto["incluye_iva"] : 1),
                    "requiere_factura" => $this->normalizarFlag(isset($datosFiscalesBruto["requiere_factura"]) ? $datosFiscalesBruto["requiere_factura"] : 1)
                );
                $evidenciaCosto = $this->normalizarEvidenciaCostoDetalle(isset($item["evidencia_costo"]) ? $item["evidencia_costo"] : (isset($item["evidencia_costo_json"]) ? $item["evidencia_costo_json"] : $item));
                $idSolicitudDetalle = intval(isset($item["id_solicitud_detalle"]) ? $item["id_solicitud_detalle"] : 0);
                $idDetalle = intval(isset($item["id_detalle"]) ? $item["id_detalle"] : 0);
            $tipoSolicitado = isset($item["tipo_item"]) ? strtolower(trim((string) $item["tipo_item"])) : "";
            $nombreSolicitado = isset($item["nombre"]) ? trim((string) $item["nombre"]) : "";
            $esNoInventariable = $this->esTipoItemNoInventariable($tipoSolicitado);
            $claveItem = $idSku > 0
                ? ("id_sku:" . $idSku)
                : ($esNoInventariable
                    ? ("noinv:" . $tipoSolicitado . ":" . strtolower($nombreSolicitado))
                    : ("sku:" . strtolower($skuTexto)));
            if ($idSku <= 0 && !$esNoInventariable && $skuTexto === "") {
                throw new Exception("Hay partidas invalidas o SKU repetidos");
            }
            if ($idSku <= 0 && $esNoInventariable && $nombreSolicitado === "") {
                throw new Exception("Hay partidas invalidas o SKU repetidos");
            }
            if ($cantidad <= 0 || $costo < 0 ||
                $impuestoPct < 0 || $impuestoPct > 100 || isset($ids[$claveItem])) {
                throw new Exception("Hay partidas invalidas o SKU repetidos");
            }
            if ($idSolicitud > 0 && $idSku > 0 && $idSolicitudDetalle > 0) {
                $stmt = $db->prepare("SELECT id_detalle FROM erp_compras_solicitudes_detalle
                    WHERE id_detalle=:detalle AND id_solicitud=:solicitud AND id_sku_erp=:sku");
                $stmt->execute(array(
                    ":detalle" => $idSolicitudDetalle,
                    ":solicitud" => $idSolicitud,
                    ":sku" => $idSku
                ));
                if (!$stmt->fetchColumn()) {
                    throw new Exception("Una partida no pertenece a la solicitud de origen");
                }
            }
            if ($idDetalle > 0) {
                $stmt = $db->prepare("SELECT id_detalle FROM erp_compras_ordenes_detalle
                    WHERE id_detalle=:detalle AND id_orden_compra=:orden");
                $stmt->execute(array(
                    ":detalle" => $idDetalle,
                    ":orden" => intval($idOrden)
                ));
                if (!$stmt->fetchColumn()) {
                    throw new Exception("Una partida existente ya no pertenece a la orden");
                }
            }
            if ($idSku > 0) {
                $stmt = $db->prepare("SELECT s.id_sku, s.id_producto_erp, s.sku, s.nombre,
                    u.abreviatura unidad, sp.id_sku_proveedor
                    FROM erp_catalogo_skus s
                    INNER JOIN erp_catalogo_unidades u ON u.id_unidad=s.id_unidad_base
                    INNER JOIN erp_catalogo_sku_proveedores sp ON sp.id_sku=s.id_sku
                        AND sp.id_proveedor=:proveedor AND sp.estatus='activo'
                    WHERE s.id_sku=:sku AND s.estatus='activo'");
                $stmt->execute(array(":proveedor" => $idProveedor, ":sku" => $idSku));
                $sku = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$sku) {
                    throw new Exception("Un SKU no pertenece al proveedor de la orden");
                }
            }
            $tipoItem = "producto_nuevo";
            $productoRegistrado = 0;
            $requiereRevision = 1;
            $idProductoErp = 0;
            $skuProveedor = 0;
            $nombreFinal = isset($item["nombre"]) ? trim((string) $item["nombre"]) : "";
            $unidadFinal = isset($item["unidad"]) ? trim((string) $item["unidad"]) : "Pza";

            if ($idSku > 0) {
                $tipoItem = "producto";
                $productoRegistrado = 1;
                $requiereRevision = 0;
                $idProductoErp = intval($sku["id_producto_erp"]);
                $skuProveedor = intval($sku["id_sku_proveedor"]);
                $nombreFinal = $sku["nombre"];
                $unidadFinal = $sku["unidad"];
                $skuTexto = $sku["sku"];
            } else {
                if ($esNoInventariable) {
                    $tipoItem = $tipoSolicitado;
                    $requiereRevision = 0;
                }
            }
            $skuTexto = $skuTexto === "" ? (isset($item["sku"]) ? trim((string) $item["sku"]) : "") : $skuTexto;
            $costoUnitarioNeto = $incluyeIva && $impuestoPct > 0
                ? round($costo / (1 + ($impuestoPct / 100)), 6)
                : $costo;
            $bruto = round($cantidad * $costoUnitarioNeto, 6);
            $descuento = min(max(0, $descuento), $bruto);
            $subtotal = round($bruto - $descuento, 6);
            $impuesto = round($subtotal * ($impuestoPct / 100), 6);
            $detalle[] = array(
                "id_detalle" => $idDetalle,
                "id_solicitud_detalle" => $idSolicitudDetalle,
                "id_producto" => $idProductoErp,
                "id_sku" => $idSku,
                "costo_unitario_incluye_impuesto" => $incluyeIva,
                "id_sku_proveedor" => $skuProveedor,
                "sku" => $skuTexto, "nombre" => $nombreFinal, "unidad" => $unidadFinal,
                "tipo_item" => $tipoItem,
                "producto_registrado" => $productoRegistrado,
                "requiere_revision" => $requiereRevision,
                "cantidad" => $cantidad, "costo" => $costoUnitarioNeto, "impuesto_pct" => $impuestoPct,
                "subtotal" => $subtotal, "descuento" => $descuento,
                "impuesto" => $impuesto, "total" => round($subtotal + $impuesto, 6),
                "datos_fiscales_json" => json_encode($datosFiscales, JSON_UNESCAPED_UNICODE),
                "evidencia_costo_json" => json_encode($evidenciaCosto, JSON_UNESCAPED_UNICODE)
            );
            $ids[$claveItem] = true;
        }
        return $detalle;
    }

    private function validarPoliticaEnvioOrdenErp($detalle) {
        $bloqueos = array();
        foreach ($detalle as $item) {
            $idSku = intval(isset($item["id_sku"]) ? $item["id_sku"] : 0);
            $tipoItem = isset($item["tipo_item"]) ? strtolower(trim((string) $item["tipo_item"])) : "";
            if ($idSku <= 0 && !$this->esTipoItemNoInventariable($tipoItem)) {
                $bloqueos[] = trim((isset($item["sku"]) ? $item["sku"] : "") . " " . (isset($item["nombre"]) ? $item["nombre"] : ""));
            }
        }
        if (!empty($bloqueos)) {
            throw new Exception("No puedes enviar una orden con productos fisicos sin SKU ERP. Resuelve en Catalogo/Proveedores o marca la partida como cargo/servicio no inventariable: " . implode("; ", array_slice($bloqueos, 0, 5)));
        }
    }

    private function esTipoItemNoInventariable($tipoItem) {
        return in_array($tipoItem, array("servicio", "cargo", "no_inventariable", "adicional"), true);
    }

    private function sincronizarDetalle($db, $idOrden, $detalle) {
        $idsConservar = array();
        $actualizarCampos = array(
            "id_solicitud_detalle=:solicitud_detalle",
            "id_producto=:producto",
            "id_sku_erp=:sku_erp",
            "id_sku_proveedor=:sku_proveedor",
            "sku=:sku",
            "nombre_producto=:nombre",
            "unidad=:unidad",
            "cantidad=:cantidad",
            "costo_unitario=:costo",
            "porcentaje_impuesto=:impuesto",
            "tipo_item=:tipo_item",
            "producto_registrado=:producto_registrado",
            "requiere_revision=:requiere_revision",
            "subtotal=:subtotal",
            "descuento=:descuento",
            "total=:total"
        );
        if ($this->detalleIncluyeIvaColumnaExiste($db)) {
            $actualizarCampos[] = "costo_unitario_incluye_impuesto=:incluye_iva";
        }
        if ($this->detalleDatosFiscalesColumnaExiste($db)) {
            $actualizarCampos[] = "datos_fiscales_json=:datos_fiscales_json";
        }
        if ($this->detalleEvidenciaCostoColumnaExiste($db)) {
            $actualizarCampos[] = "evidencia_costo_json=:evidencia_costo_json";
        }
        $actualizar = $db->prepare("UPDATE erp_compras_ordenes_detalle SET\n            " . implode(", ", $actualizarCampos) . "\n            WHERE id_detalle=:detalle AND id_orden_compra=:orden");

        foreach ($detalle as $item) {
            if (intval($item["id_detalle"]) <= 0) {
                $this->insertarDetalle($db, $idOrden, array($item));
                $idsConservar[] = intval($db->lastInsertId());
                continue;
            }
            $params = array(
                ":solicitud_detalle" => $item["id_solicitud_detalle"] ?: null,
                ":producto" => $item["id_producto"],
                ":sku_erp" => $item["id_sku"],
                ":sku_proveedor" => $item["id_sku_proveedor"] ?: null,
                ":sku" => $item["sku"],
                ":nombre" => $item["nombre"],
                ":unidad" => $item["unidad"],
                ":cantidad" => $item["cantidad"],
                ":costo" => $item["costo"],
                ":impuesto" => $item["impuesto_pct"],
                ":tipo_item" => isset($item["tipo_item"]) ? $item["tipo_item"] : "producto",
                ":producto_registrado" => !empty($item["producto_registrado"]) ? 1 : 0,
                ":requiere_revision" => !empty($item["requiere_revision"]) ? 1 : 0,
                ":subtotal" => $item["subtotal"],
                ":descuento" => $item["descuento"],
                ":total" => $item["total"],
                ":detalle" => $item["id_detalle"],
                ":orden" => $idOrden
            );
            if ($this->detalleIncluyeIvaColumnaExiste($db)) {
                $params[":incluye_iva"] = $item["costo_unitario_incluye_impuesto"] ? 1 : 0;
            }
            if ($this->detalleDatosFiscalesColumnaExiste($db)) {
                $params[":datos_fiscales_json"] = isset($item["datos_fiscales_json"]) ? $item["datos_fiscales_json"] : null;
            }
            if ($this->detalleEvidenciaCostoColumnaExiste($db)) {
                $params[":evidencia_costo_json"] = isset($item["evidencia_costo_json"]) ? $item["evidencia_costo_json"] : null;
            }
            $actualizar->execute($params);
            $idsConservar[] = intval($item["id_detalle"]);
        }

        $stmt = $db->prepare("SELECT id_detalle FROM erp_compras_ordenes_detalle
            WHERE id_orden_compra=:orden");
        $stmt->execute(array(":orden" => $idOrden));
        $existentes = array_map("intval", $stmt->fetchAll(PDO::FETCH_COLUMN));
        $eliminar = array_values(array_diff($existentes, $idsConservar));
        if (empty($eliminar)) {
            return;
        }

        $marcas = implode(",", array_fill(0, count($eliminar), "?"));
        $stmt = $db->prepare("SELECT COUNT(*) FROM
            erp_compras_documentos_fiscales_conceptos
            WHERE id_orden_detalle IN (" . $marcas . ")");
        $stmt->execute($eliminar);
        if (intval($stmt->fetchColumn()) > 0) {
            throw new Exception("No puedes quitar una partida que ya esta relacionada con un concepto XML");
        }
        $stmt = $db->prepare("DELETE FROM erp_compras_ordenes_detalle
            WHERE id_orden_compra=? AND id_detalle IN (" . $marcas . ")");
        $stmt->execute(array_merge(array($idOrden), $eliminar));
    }

    private function insertarDetalle($db, $idOrden, $detalle) {
        $columnas = array(
            "id_orden_compra",
            "id_solicitud_detalle",
            "id_producto",
            "id_sku_erp",
            "id_sku_proveedor",
            "sku",
            "nombre_producto",
            "unidad",
            "cantidad",
            "costo_unitario",
            "porcentaje_impuesto",
            "subtotal",
            "descuento",
            "total",
            "cantidad_recibida",
            "tipo_item",
            "producto_registrado",
            "requiere_revision"
        );
        if ($this->detalleIncluyeIvaColumnaExiste($db)) {
            array_splice($columnas, 10, 0, array("costo_unitario_incluye_impuesto"));
        }
        if ($this->detalleDatosFiscalesColumnaExiste($db)) {
            $columnas[] = "datos_fiscales_json";
        }
        if ($this->detalleEvidenciaCostoColumnaExiste($db)) {
            $columnas[] = "evidencia_costo_json";
        }
        $valores = array_map(function($columna) {
            return ":" . $columna;
        }, $columnas);
        $stmt = $db->prepare("INSERT INTO erp_compras_ordenes_detalle\n            (" . implode(", ", $columnas) . ")\n            VALUES (" . implode(", ", $valores) . ")");
        foreach ($detalle as $item) {
            $params = array(
                ":id_orden_compra" => $idOrden,
                ":id_solicitud_detalle" => $item["id_solicitud_detalle"] ?: null,
                ":id_producto" => $item["id_producto"],
                ":id_sku_erp" => $item["id_sku"],
                ":id_sku_proveedor" => $item["id_sku_proveedor"] ?: null,
                ":sku" => $item["sku"],
                ":nombre_producto" => $item["nombre"],
                ":unidad" => $item["unidad"],
                ":cantidad" => $item["cantidad"],
                ":costo_unitario" => $item["costo"],
                ":porcentaje_impuesto" => $item["impuesto_pct"],
                ":subtotal" => $item["subtotal"],
                ":descuento" => $item["descuento"],
                ":total" => $item["total"],
                ":cantidad_recibida" => 0,
                ":tipo_item" => isset($item["tipo_item"]) ? $item["tipo_item"] : "producto",
                ":producto_registrado" => !empty($item["producto_registrado"]) ? 1 : 0,
                ":requiere_revision" => !empty($item["requiere_revision"]) ? 1 : 0
            );

            if ($this->detalleIncluyeIvaColumnaExiste($db)) {
                $params[":costo_unitario_incluye_impuesto"] = $item["costo_unitario_incluye_impuesto"] ? 1 : 0;
            }
            if ($this->detalleDatosFiscalesColumnaExiste($db)) {
                $params[":datos_fiscales_json"] = isset($item["datos_fiscales_json"]) ? $item["datos_fiscales_json"] : null;
            }
            if ($this->detalleEvidenciaCostoColumnaExiste($db)) {
                $params[":evidencia_costo_json"] = isset($item["evidencia_costo_json"]) ? $item["evidencia_costo_json"] : null;
            }
            $stmt->execute($params);
        }
    }

    private function detalleIncluyeIvaColumnaExiste($db) {
        if ($this->detalleIncluyeIvaColumnaExiste === null) {
            try {
                $stmt = $db->prepare("SHOW COLUMNS FROM erp_compras_ordenes_detalle LIKE 'costo_unitario_incluye_impuesto'");
                $stmt->execute();
                $fila = $stmt->fetch(PDO::FETCH_ASSOC);
                $this->detalleIncluyeIvaColumnaExiste = ($fila !== false);
            } catch (Exception $e) {
                $this->detalleIncluyeIvaColumnaExiste = false;
            }
        }
        return $this->detalleIncluyeIvaColumnaExiste;
    }

    private function costoBaseMxnDetalleOrden($detalle) {
        $costo = floatval(isset($detalle["costo_unitario"]) ? $detalle["costo_unitario"] : 0);
        $moneda = strtoupper(trim((string) (isset($detalle["moneda"]) ? $detalle["moneda"] : "MXN")));
        $tipoCambio = floatval(isset($detalle["tipo_cambio"]) ? $detalle["tipo_cambio"] : 1);
        if ($moneda !== "MXN") {
            if ($tipoCambio <= 0) {
                return 0;
            }
            $costo = $costo * $tipoCambio;
        }
        return round($costo, 6);
    }

    private function advertenciasOperativasEnvioOrden($db, $detalle, $idProveedor) {
        $advertencias = array();
        $stmtRelacion = $db->prepare("SELECT sp.id_sku_proveedor, sp.id_unidad_compra, sp.factor_conversion,
                sp.costo_ultimo,
                EXISTS (
                    SELECT 1
                    FROM erp_proveedores_sku_costos c
                    WHERE c.id_proveedor = sp.id_proveedor
                      AND c.id_sku = sp.id_sku
                      AND c.id_sku_proveedor = sp.id_sku_proveedor
                      AND c.estatus = 'vigente'
                      AND COALESCE(c.costo, 0) > 0
                    LIMIT 1
                ) AS tiene_costo_vigente
            FROM erp_catalogo_sku_proveedores sp
            WHERE sp.id_sku_proveedor = :id_sku_proveedor
              AND sp.id_proveedor = :id_proveedor
              AND sp.id_sku = :id_sku
              AND sp.estatus = 'activo'
            LIMIT 1");

        foreach ($detalle as $item) {
            $idSku = intval(isset($item["id_sku"]) ? $item["id_sku"] : 0);
            $idSkuProveedor = intval(isset($item["id_sku_proveedor"]) ? $item["id_sku_proveedor"] : 0);
            if ($idSku <= 0) {
                $advertencias[] = $this->advertenciaOperativaOrden($item, "sku_sin_relacion", "warning", "Partida sin SKU ERP relacionado");
                continue;
            }
            if ($idSkuProveedor <= 0) {
                $advertencias[] = $this->advertenciaOperativaOrden($item, "relacion_proveedor_sku_incompleta", "warning", "Relacion proveedor-SKU incompleta");
                continue;
            }
            $stmtRelacion->execute(array(
                ":id_sku_proveedor" => $idSkuProveedor,
                ":id_proveedor" => intval($idProveedor),
                ":id_sku" => $idSku
            ));
            $relacion = $stmtRelacion->fetch(PDO::FETCH_ASSOC);
            if (!$relacion) {
                $advertencias[] = $this->advertenciaOperativaOrden($item, "relacion_proveedor_sku_inactiva", "warning", "Relacion proveedor-SKU no activa");
                continue;
            }
            if (intval(isset($relacion["tiene_costo_vigente"]) ? $relacion["tiene_costo_vigente"] : 0) <= 0) {
                $advertencias[] = $this->advertenciaOperativaOrden($item, "sin_costo_vigente", "warning", "Sin costo vigente autorizado; se usara el costo capturado en la orden");
            }
            if (intval(isset($relacion["id_unidad_compra"]) ? $relacion["id_unidad_compra"] : 0) <= 0 ||
                floatval(isset($relacion["factor_conversion"]) ? $relacion["factor_conversion"] : 0) <= 0) {
                $advertencias[] = $this->advertenciaOperativaOrden($item, "unidad_factor_incompleto", "warning", "Unidad de compra o factor pendiente en la relacion proveedor-SKU");
            }
            $fiscal = json_decode(isset($item["datos_fiscales_json"]) ? $item["datos_fiscales_json"] : "{}", true);
            if (!is_array($fiscal)) {
                $fiscal = array();
            }
            if (trim((string) (isset($fiscal["clave_sat"]) ? $fiscal["clave_sat"] : "")) === "" ||
                trim((string) (isset($fiscal["clave_unidad_sat"]) ? $fiscal["clave_unidad_sat"] : "")) === "") {
                $advertencias[] = $this->advertenciaOperativaOrden($item, "fiscal_incompleto", "info", "Datos fiscales del SKU incompletos");
            }
        }

        return $advertencias;
    }

    private function advertenciaOperativaOrden($item, $codigo, $nivel, $mensaje) {
        return array(
            "codigo" => $codigo,
            "nivel" => $nivel,
            "mensaje" => $mensaje,
            "id_sku" => intval(isset($item["id_sku"]) ? $item["id_sku"] : 0),
            "id_sku_proveedor" => intval(isset($item["id_sku_proveedor"]) ? $item["id_sku_proveedor"] : 0),
            "sku" => isset($item["sku"]) ? $item["sku"] : "",
            "nombre" => isset($item["nombre"]) ? $item["nombre"] : ""
        );
    }

    private function registrarIncidenciasCatalogoDesdeOrden($db, $idOrden, $idProveedor, $detalle, $idUsuario) {
        // [Codex: GPT-5 2026-06-16] Pendientes interdepartamentales: Compras detecta, Catalogo/Proveedores atienden.
        try {
            $huellasActivas = array();
            $stmt = $db->prepare("INSERT INTO erp_catalogo_incidencias_calidad
                (huella, tipo_incidencia, entidad_tipo, id_producto_erp, id_sku, id_referencia,
                referencia_tipo, origen, severidad, titulo, descripcion, detalle_json,
                evidencia_json, propuesta_json, estatus, creado_por)
                VALUES (:huella, :tipo, 'sku', :producto, :sku_erp, :referencia,
                'erp_compras_ordenes', 'compra', :severidad, :titulo, :descripcion,
                :detalle, :evidencia, :propuesta, 'pendiente', :usuario)
                ON DUPLICATE KEY UPDATE
                    detalle_json=VALUES(detalle_json),
                    evidencia_json=VALUES(evidencia_json),
                    propuesta_json=VALUES(propuesta_json),
                    estatus=IF(estatus IN ('resuelta','descartada'), estatus, 'pendiente'),
                    fecha_actualizacion=CURRENT_TIMESTAMP");

            foreach ($detalle as $item) {
                $idSku = intval(isset($item["id_sku"]) ? $item["id_sku"] : (isset($item["id_sku_erp"]) ? $item["id_sku_erp"] : 0));
                $idSkuProveedor = intval(isset($item["id_sku_proveedor"]) ? $item["id_sku_proveedor"] : 0);
                $tipoItem = isset($item["tipo_item"]) ? strtolower(trim((string) $item["tipo_item"])) : "";
                $sku = trim((string) (isset($item["sku"]) ? $item["sku"] : ""));
                $nombre = trim((string) (isset($item["nombre"]) ? $item["nombre"] : ""));
                $tipoIncidencia = "";
                $titulo = "";
                $descripcion = "";
                $severidad = "advertencia";
                $moduloResponsable = "catalogo";

                if ($idSku <= 0 && !$this->esTipoItemNoInventariable($tipoItem)) {
                    $tipoIncidencia = "compra_producto_pendiente_alta";
                    $titulo = "Producto pendiente de alta desde orden de compra";
                    $descripcion = "Compras capturo un producto fisico sin SKU ERP. Catalogo debe revisar si se crea, se vincula o se descarta antes de envio/recepcion.";
                } elseif ($idSku > 0 && $idSkuProveedor <= 0) {
                    $tipoIncidencia = "compra_sku_sin_relacion_proveedor";
                    $titulo = "SKU ERP sin relacion activa con proveedor";
                    $descripcion = "Compras capturo un SKU ERP que requiere relacion proveedor-SKU para operar listas, costos y compras futuras.";
                    $moduloResponsable = "proveedores";
                }

                if ($tipoIncidencia === "") {
                    continue;
                }

                $detalleJson = array(
                    "id_orden_compra" => intval($idOrden),
                    "id_proveedor" => intval($idProveedor),
                    "id_sku_erp" => $idSku,
                    "id_sku_proveedor" => $idSkuProveedor,
                    "sku" => $sku,
                    "nombre_producto" => $nombre,
                    "tipo_item" => $tipoItem,
                    "cantidad" => isset($item["cantidad"]) ? $item["cantidad"] : 0,
                    "costo_unitario" => isset($item["costo"]) ? $item["costo"] : 0
                );
                $evidenciaJson = array(
                    "modulo" => "compras",
                    "tipo_origen" => "orden_compra",
                    "id_origen" => intval($idOrden),
                    "id_proveedor" => intval($idProveedor),
                    "sku" => $sku,
                    "nombre_producto" => $nombre,
                    "datos_fiscales_json" => isset($item["datos_fiscales_json"]) ? $item["datos_fiscales_json"] : "{}"
                );
                $propuestaJson = array(
                    "accion" => $idSku <= 0 ? "crear_o_vincular_sku_erp" : "crear_o_activar_relacion_proveedor_sku",
                    "modulo_responsable" => $moduloResponsable,
                    "no_resolver_desde_compras" => true
                );
                $huellaBase = "compras|orden|" . $tipoIncidencia .
                    "|orden:" . intval($idOrden) .
                    "|proveedor:" . intval($idProveedor) .
                    "|sku:" . strtolower(preg_replace("/\s+/", " ", $sku)) .
                    "|nombre:" . strtolower(preg_replace("/\s+/", " ", $nombre));
                $huella = hash("sha256", $huellaBase);
                $huellasActivas[$huella] = true;

                $stmt->execute(array(
                    ":huella" => $huella,
                    ":tipo" => $tipoIncidencia,
                    ":producto" => intval(isset($item["id_producto"]) ? $item["id_producto"] : 0) ?: null,
                    ":sku_erp" => $idSku ?: null,
                    ":referencia" => intval($idOrden),
                    ":severidad" => $severidad,
                    ":titulo" => $titulo,
                    ":descripcion" => $descripcion,
                    ":detalle" => json_encode($detalleJson, JSON_UNESCAPED_UNICODE),
                    ":evidencia" => json_encode($evidenciaJson, JSON_UNESCAPED_UNICODE),
                    ":propuesta" => json_encode($propuestaJson, JSON_UNESCAPED_UNICODE),
                    ":usuario" => intval($idUsuario) ?: null
                ));
            }
            $this->cerrarIncidenciasCatalogoOrdenResueltas($db, $idOrden, array_keys($huellasActivas));
        } catch (Exception $e) {
            return;
        }
    }

    private function cerrarIncidenciasCatalogoOrdenResueltas($db, $idOrden, $huellasActivas) {
        // [Codex: GPT-5 2026-06-16] Si Catalogo/Proveedores ya resolvieron una partida, la incidencia de Compras se cierra al guardar.
        $activas = array_fill_keys($huellasActivas, true);
        $stmt = $db->prepare("SELECT id_incidencia_calidad, huella
            FROM erp_catalogo_incidencias_calidad
            WHERE origen='compra'
              AND referencia_tipo='erp_compras_ordenes'
              AND id_referencia=:id
              AND tipo_incidencia IN ('compra_producto_pendiente_alta','compra_sku_sin_relacion_proveedor')
              AND estatus IN ('pendiente','en_revision','bloqueada')");
        $stmt->execute(array(":id" => intval($idOrden)));

        $stmtCerrar = $db->prepare("UPDATE erp_catalogo_incidencias_calidad SET
            estatus='resuelta',
            resolucion_json=:resolucion,
            fecha_resolucion=NOW(),
            fecha_actualizacion=NOW()
            WHERE id_incidencia_calidad=:id");

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
            $huella = trim((string) (isset($fila["huella"]) ? $fila["huella"] : ""));
            if ($huella !== "" && !isset($activas[$huella])) {
                $stmtCerrar->execute(array(
                    ":resolucion" => json_encode(array(
                        "resuelto_por" => "compras_guardar_orden",
                        "motivo" => "La partida ya no requiere alta o relacion proveedor-SKU en la orden."
                    ), JSON_UNESCAPED_UNICODE),
                    ":id" => intval($fila["id_incidencia_calidad"])
                ));
            }
        }
    }

    private function registrarNotificacionesOperativasDesdeOrden($db, $orden, $idOrden, $idProveedor, $detalle, $idUsuario, $estatusDestino) {
        // [Codex: GPT-5 2026-06-16] Notificaciones transversales: Compras crea alertas accionables para areas responsables.
        try {
            $folio = trim((string) (isset($orden["folio"]) ? $orden["folio"] : ("OC-" . intval($idOrden))));
            $huellasActivas = array();

            foreach ($detalle as $item) {
                $idSku = intval(isset($item["id_sku"]) ? $item["id_sku"] : (isset($item["id_sku_erp"]) ? $item["id_sku_erp"] : 0));
                $idSkuProveedor = intval(isset($item["id_sku_proveedor"]) ? $item["id_sku_proveedor"] : 0);
                $tipoItem = isset($item["tipo_item"]) ? strtolower(trim((string) $item["tipo_item"])) : "";
                $sku = trim((string) (isset($item["sku"]) ? $item["sku"] : ""));
                $nombre = trim((string) (isset($item["nombre"]) ? $item["nombre"] : ""));
                $tipoNotificacion = "";
                $area = "";
                $permiso = "";
                $titulo = "";
                $descripcion = "";
                $url = "";
                $prioridad = "normal";

                if ($idSku <= 0 && !$this->esTipoItemNoInventariable($tipoItem)) {
                    $tipoNotificacion = "compra_producto_pendiente_alta";
                    $area = "catalogo";
                    $permiso = "catalogo.editar";
                    $titulo = "Producto pendiente de alta desde " . $folio;
                    $descripcion = "Compras capturo un producto fisico sin SKU ERP: " . ($sku !== "" ? $sku . " - " : "") . $nombre;
                    $url = "/catalogoerp/configuracion";
                    $prioridad = "alta";
                } elseif ($idSku > 0 && $idSkuProveedor <= 0) {
                    $tipoNotificacion = "compra_sku_sin_relacion_proveedor";
                    $area = "proveedores";
                    $permiso = "proveedores.matching";
                    $titulo = "SKU sin relacion proveedor desde " . $folio;
                    $descripcion = "El SKU " . ($sku !== "" ? $sku : ("ERP " . $idSku)) . " requiere relacion activa con el proveedor para compras futuras.";
                    $url = "/proveedor/mostrar_proveedores_erp";
                    $prioridad = "normal";
                }

                if ($tipoNotificacion === "") {
                    continue;
                }

                $huella = $this->huellaNotificacionOrden($tipoNotificacion, $idOrden, $idProveedor, $sku, $nombre);
                $huellasActivas[$huella] = true;
                $payload = array(
                    "huella" => $huella,
                    "id_orden_compra" => intval($idOrden),
                    "folio" => $folio,
                    "id_proveedor" => intval($idProveedor),
                    "id_sku_erp" => $idSku,
                    "id_sku_proveedor" => $idSkuProveedor,
                    "sku" => $sku,
                    "nombre_producto" => $nombre,
                    "tipo_item" => $tipoItem,
                    "cantidad" => isset($item["cantidad"]) ? $item["cantidad"] : 0,
                    "costo_unitario" => isset($item["costo"]) ? $item["costo"] : 0
                );

                $this->guardarNotificacionOperativa($db, array(
                    "tipo" => $tipoNotificacion,
                    "modulo_origen" => "compras",
                    "entidad_origen" => "erp_compras_ordenes",
                    "id_entidad_origen" => intval($idOrden),
                    "area_responsable" => $area,
                    "permiso_requerido" => $permiso,
                    "titulo" => $titulo,
                    "descripcion" => $descripcion,
                    "prioridad" => $prioridad,
                    "url_accion" => $url,
                    "payload_json" => $payload,
                    "creado_por" => intval($idUsuario) ?: null
                ));
            }

            $this->cerrarNotificacionesOrdenResueltas($db, $idOrden, array_keys($huellasActivas));

            if ($estatusDestino === "enviada") {
                $this->guardarNotificacionOperativa($db, array(
                    "tipo" => "compra_orden_enviada_recepcion_pendiente",
                    "modulo_origen" => "compras",
                    "entidad_origen" => "erp_compras_ordenes",
                    "id_entidad_origen" => intval($idOrden),
                    "area_responsable" => "almacen",
                    "permiso_requerido" => "almacen.recibir",
                    "titulo" => "Orden enviada para recepcion " . $folio,
                    "descripcion" => "Compras envio la orden " . $folio . ". Almacen debe confirmar recepcion cuando llegue la mercancia.",
                    "prioridad" => "normal",
                    "url_accion" => "/almacen/mostrar_recepciones",
                    "payload_json" => array(
                        "huella" => $this->huellaNotificacionOrden("compra_orden_enviada_recepcion_pendiente", $idOrden, $idProveedor, "", ""),
                        "id_orden_compra" => intval($idOrden),
                        "folio" => $folio,
                        "id_proveedor" => intval($idProveedor)
                    ),
                    "creado_por" => intval($idUsuario) ?: null
                ));
            }
        } catch (Exception $e) {
            return;
        }
    }

    private function guardarNotificacionOperativa($db, $datos) {
        $payload = isset($datos["payload_json"]) && is_array($datos["payload_json"]) ? $datos["payload_json"] : array();
        $huella = isset($payload["huella"]) ? trim((string) $payload["huella"]) : "";
        $payloadJson = json_encode($payload, JSON_UNESCAPED_UNICODE);

        $stmt = $db->prepare("SELECT id_notificacion
            FROM erp_notificaciones
            WHERE tipo=:tipo
              AND modulo_origen=:modulo
              AND entidad_origen=:entidad
              AND id_entidad_origen=:id_entidad
              AND estatus IN ('pendiente','en_revision','bloqueada')
              AND payload_json LIKE :huella
            ORDER BY id_notificacion DESC
            LIMIT 1");
        $stmt->execute(array(
            ":tipo" => $datos["tipo"],
            ":modulo" => $datos["modulo_origen"],
            ":entidad" => $datos["entidad_origen"],
            ":id_entidad" => intval($datos["id_entidad_origen"]),
            ":huella" => '%"huella":"' . $huella . '"%'
        ));
        $idNotificacion = intval($stmt->fetchColumn());

        if ($idNotificacion > 0) {
            $stmt = $db->prepare("UPDATE erp_notificaciones SET
                area_responsable=:area, permiso_requerido=:permiso, titulo=:titulo,
                descripcion=:descripcion, prioridad=:prioridad, url_accion=:url,
                payload_json=:payload, fecha_actualizacion=NOW()
                WHERE id_notificacion=:id");
            $stmt->execute(array(
                ":area" => $datos["area_responsable"],
                ":permiso" => $datos["permiso_requerido"],
                ":titulo" => $datos["titulo"],
                ":descripcion" => $datos["descripcion"],
                ":prioridad" => $datos["prioridad"],
                ":url" => $datos["url_accion"],
                ":payload" => $payloadJson,
                ":id" => $idNotificacion
            ));
            return $idNotificacion;
        }

        $stmt = $db->prepare("INSERT INTO erp_notificaciones
            (tipo, modulo_origen, entidad_origen, id_entidad_origen,
            area_responsable, permiso_requerido, titulo, descripcion,
            prioridad, estatus, url_accion, payload_json, creado_por)
            VALUES (:tipo, :modulo, :entidad, :id_entidad,
            :area, :permiso, :titulo, :descripcion,
            :prioridad, 'pendiente', :url, :payload, :usuario)");
        $stmt->execute(array(
            ":tipo" => $datos["tipo"],
            ":modulo" => $datos["modulo_origen"],
            ":entidad" => $datos["entidad_origen"],
            ":id_entidad" => intval($datos["id_entidad_origen"]),
            ":area" => $datos["area_responsable"],
            ":permiso" => $datos["permiso_requerido"],
            ":titulo" => $datos["titulo"],
            ":descripcion" => $datos["descripcion"],
            ":prioridad" => $datos["prioridad"],
            ":url" => $datos["url_accion"],
            ":payload" => $payloadJson,
            ":usuario" => isset($datos["creado_por"]) ? $datos["creado_por"] : null
        ));
        return intval($db->lastInsertId());
    }

    private function cerrarNotificacionesOrdenResueltas($db, $idOrden, $huellasActivas) {
        $activas = array_fill_keys($huellasActivas, true);
        $stmt = $db->prepare("SELECT id_notificacion, payload_json
            FROM erp_notificaciones
            WHERE modulo_origen='compras'
              AND entidad_origen='erp_compras_ordenes'
              AND id_entidad_origen=:id
              AND tipo IN ('compra_producto_pendiente_alta','compra_sku_sin_relacion_proveedor')
              AND estatus IN ('pendiente','en_revision','bloqueada')");
        $stmt->execute(array(":id" => intval($idOrden)));

        $stmtCerrar = $db->prepare("UPDATE erp_notificaciones SET
            estatus='resuelta', fecha_resolucion=NOW(), fecha_actualizacion=NOW()
            WHERE id_notificacion=:id");

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
            $payload = json_decode(isset($fila["payload_json"]) ? $fila["payload_json"] : "{}", true);
            $huella = is_array($payload) && isset($payload["huella"]) ? trim((string) $payload["huella"]) : "";
            if ($huella !== "" && !isset($activas[$huella])) {
                $stmtCerrar->execute(array(":id" => intval($fila["id_notificacion"])));
            }
        }
    }

    private function huellaNotificacionOrden($tipo, $idOrden, $idProveedor, $sku, $nombre) {
        $base = "notificacion|compras|orden|" . $tipo .
            "|orden:" . intval($idOrden) .
            "|proveedor:" . intval($idProveedor) .
            "|sku:" . strtolower(preg_replace("/\s+/", " ", trim((string) $sku))) .
            "|nombre:" . strtolower(preg_replace("/\s+/", " ", trim((string) $nombre)));
        return hash("sha256", $base);
    }

    private function calcularCostoPromedioHistoricoSku($db, $idSku) {
        $incluyeIvaSelect = $this->detalleIncluyeIvaColumnaExiste($db) ? "d.costo_unitario_incluye_impuesto" : "0";
        $stmt = $db->prepare("SELECT d.cantidad_recibida, d.costo_unitario, {$incluyeIvaSelect} costo_unitario_incluye_impuesto,
                d.porcentaje_impuesto, o.moneda, o.tipo_cambio
            FROM erp_compras_ordenes_detalle d
            INNER JOIN erp_compras_ordenes o ON o.id_orden_compra = d.id_orden_compra
            WHERE d.id_sku_erp=:sku
              AND COALESCE(d.cantidad_recibida, 0) > 0
              AND COALESCE(d.costo_unitario, 0) > 0
              AND COALESCE(o.estatus, '') <> 'cancelada'");
        $stmt->execute(array(":sku" => intval($idSku)));
        $cantidad = 0;
        $importe = 0;
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
            $recibida = floatval(isset($fila["cantidad_recibida"]) ? $fila["cantidad_recibida"] : 0);
            $costo = $this->costoBaseMxnDetalleOrden($fila);
            if ($recibida <= 0 || $costo <= 0) {
                continue;
            }
            $cantidad += $recibida;
            $importe += $recibida * $costo;
        }
        return $cantidad > 0 ? round($importe / $cantidad, 6) : null;
    }

    private function detalleDatosFiscalesColumnaExiste($db) {
        if ($this->detalleDatosFiscalesColumnaExiste === null) {
            try {
                $stmt = $db->prepare("SHOW COLUMNS FROM erp_compras_ordenes_detalle LIKE 'datos_fiscales_json'");
                $stmt->execute();
                $fila = $stmt->fetch(PDO::FETCH_ASSOC);
                $this->detalleDatosFiscalesColumnaExiste = ($fila !== false);
            } catch (Exception $e) {
                $this->detalleDatosFiscalesColumnaExiste = false;
            }
        }
        return $this->detalleDatosFiscalesColumnaExiste;
    }

    private function detalleEvidenciaCostoColumnaExiste($db) {
        if ($this->detalleEvidenciaCostoColumnaExiste === null) {
            try {
                $stmt = $db->prepare("SHOW COLUMNS FROM erp_compras_ordenes_detalle LIKE 'evidencia_costo_json'");
                $stmt->execute();
                $fila = $stmt->fetch(PDO::FETCH_ASSOC);
                $this->detalleEvidenciaCostoColumnaExiste = ($fila !== false);
            } catch (Exception $e) {
                $this->detalleEvidenciaCostoColumnaExiste = false;
            }
        }
        return $this->detalleEvidenciaCostoColumnaExiste;
    }

    private function normalizarEvidenciaCostoDetalle($valor) {
        if (is_string($valor)) {
            $json = json_decode($valor, true);
            $valor = is_array($json) ? $json : array();
        }
        if (!is_array($valor)) {
            $valor = array();
        }
        return array(
            "id_costo_proveedor_sku" => intval(isset($valor["id_costo_proveedor_sku"]) ? $valor["id_costo_proveedor_sku"] : 0),
            "id_lista_proveedor_erp" => intval(isset($valor["id_lista_proveedor_erp"]) ? $valor["id_lista_proveedor_erp"] : 0),
            "fuente_costo" => isset($valor["fuente_costo"]) ? trim((string) $valor["fuente_costo"]) : "",
            "origen_costo" => isset($valor["origen_costo"]) ? trim((string) $valor["origen_costo"]) : "",
            "moneda_costo" => isset($valor["moneda_costo"]) ? trim((string) $valor["moneda_costo"]) : "",
            "vigencia_desde" => isset($valor["vigencia_desde"]) ? trim((string) $valor["vigencia_desde"]) : "",
            "vigencia_hasta" => isset($valor["vigencia_hasta"]) ? trim((string) $valor["vigencia_hasta"]) : ""
        );
    }

    public function buscarSkus($idProveedor, $termino) {
        try {
            require_once __DIR__ . "/Proveedores.php";
            $proveedores = new Proveedores();
            return $proveedores->skusComprablesParaComprasErp($idProveedor, $termino, "ordenes");
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    private function respuesta($error, $tipo, $mensaje, $depurar = array()) {
        return array("error" => $error, "tipo" => $tipo, "mensaje" => $mensaje, "depurar" => $depurar);
    }

    private function normalizarFlag($valor) {
        return $valor === true || $valor === 1 || $valor === "1" || $valor === "true" || $valor === "TRUE";
    }
}
