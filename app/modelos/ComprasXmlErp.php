<?php

class ComprasXmlErp extends CRUD {

    public function importar($idOrden, $archivo, $idUsuario) {
        $ruta = "";
        $idOrden = intval($idOrden);
        if ($idOrden <= 0 || empty($archivo["tmp_name"]) || !is_uploaded_file($archivo["tmp_name"])) {
            return $this->respuesta(true, "warning", "Selecciona una orden guardada y un archivo XML");
        }
        if (!empty($archivo["error"]) || intval($archivo["size"]) > 5242880) {
            return $this->respuesta(true, "warning", "El XML no pudo cargarse o excede 5 MB");
        }
        $contenido = file_get_contents($archivo["tmp_name"]);
        if ($contenido === false || stripos($contenido, "<!DOCTYPE") !== false || stripos($contenido, "<!ENTITY") !== false) {
            return $this->respuesta(true, "danger", "El archivo XML no es seguro");
        }

        try {
            $cfdi = $this->leerCfdi($contenido);
            $db = $this->getConexion();
            $db->beginTransaction();
            $stmt = $db->prepare("SELECT * FROM erp_compras_ordenes
                WHERE id_orden_compra=:id AND estatus='borrador' FOR UPDATE");
            $stmt->execute(array(":id" => $idOrden));
            $orden = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$orden) {
                throw new Exception("Solo se puede importar XML en una orden en borrador");
            }

            $hash = hash("sha256", $contenido);
            $stmt = $db->prepare("SELECT id_documento_fiscal FROM erp_compras_documentos_fiscales
                WHERE archivo_hash=:hash OR (uuid IS NOT NULL AND uuid<>'' AND uuid=:uuid) LIMIT 1");
            $stmt->execute(array(":hash" => $hash, ":uuid" => $cfdi["uuid"]));
            if ($stmt->fetchColumn()) {
                throw new Exception("Este CFDI ya fue importado");
            }

            $directorioRelativo = "uploads/erp/compras/ordenes/" . $idOrden . "/fiscales/";
            $directorio = dirname(__DIR__, 2) . "/public/" . $directorioRelativo;
            if (!is_dir($directorio) && !mkdir($directorio, 0775, true)) {
                throw new Exception("No se pudo preparar el directorio fiscal");
            }
            $nombre = ($cfdi["uuid"] ?: date("YmdHis")) . ".xml";
            $nombre = preg_replace("/[^A-Za-z0-9._-]/", "_", $nombre);
            $ruta = $directorio . $nombre;
            if (file_put_contents($ruta, $contenido) === false) {
                throw new Exception("No se pudo conservar el XML");
            }

            $stmt = $db->prepare("INSERT INTO erp_compras_documentos_fiscales
                (id_orden_compra, uuid, version_cfdi, serie, folio, fecha_emision,
                rfc_emisor, nombre_emisor, rfc_receptor, nombre_receptor, moneda,
                tipo_cambio, subtotal, descuento, total, archivo_nombre, archivo_ruta,
                archivo_hash, estatus_conciliacion, creado_por)
                VALUES (:orden,:uuid,:version,:serie,:folio,:fecha,:rfc_emisor,:emisor,
                :rfc_receptor,:receptor,:moneda,:tipo_cambio,:subtotal,:descuento,:total,
                :archivo,:ruta,:hash,'pendiente',:usuario)");
            $stmt->execute(array(
                ":orden" => $idOrden, ":uuid" => $cfdi["uuid"] ?: null,
                ":version" => $cfdi["version"], ":serie" => $cfdi["serie"],
                ":folio" => $cfdi["folio"], ":fecha" => $cfdi["fecha"],
                ":rfc_emisor" => $cfdi["rfc_emisor"], ":emisor" => $cfdi["nombre_emisor"],
                ":rfc_receptor" => $cfdi["rfc_receptor"], ":receptor" => $cfdi["nombre_receptor"],
                ":moneda" => $cfdi["moneda"], ":tipo_cambio" => $cfdi["tipo_cambio"],
                ":subtotal" => $cfdi["subtotal"], ":descuento" => $cfdi["descuento"],
                ":total" => $cfdi["total"], ":archivo" => basename($archivo["name"]),
                ":ruta" => $directorioRelativo . $nombre, ":hash" => $hash,
                ":usuario" => intval($idUsuario) ?: null
            ));
            $idDocumento = intval($db->lastInsertId());
            $resumen = array(
                "coincidencias" => 0,
                "sin_coincidencia" => 0,
                "ambiguas" => 0,
                "fiscales_completados" => 0,
                "fiscales_sugeridos" => 0
            );
            foreach ($cfdi["conceptos"] as $concepto) {
                $match = $this->conciliarConcepto($db, $idOrden, $concepto);
                if ($match["id_sku"] > 0) {
                    $resumen["coincidencias"]++;
                    if ($this->evaluarSugerenciaFiscalSku($db, $match["id_sku"], $concepto)) {
                        $resumen["fiscales_sugeridos"]++;
                    }
                } elseif ($match["resultado"] === "ambigua") {
                    $resumen["ambiguas"]++;
                } else {
                    $resumen["sin_coincidencia"]++;
                }
                $this->insertarConcepto($db, $idDocumento, $concepto, $match);
            }
            $estatus = ($resumen["sin_coincidencia"] + $resumen["ambiguas"]) > 0
                ? "requiere_revision" : "conciliado";
            $this->actualizarEstatusDocumento($db, $idDocumento);
            $pendientes = $this->sincronizarPendientesOrden($db, $idOrden, $idDocumento);
            $this->sincronizarNotificacionXmlDocumento($db, $idOrden, $idDocumento, $idUsuario);
            $db->commit();
            return $this->respuesta(false, $estatus === "conciliado" ? "success" : "warning",
                $estatus === "conciliado" ? "XML conciliado con la orden" : "XML importado con conceptos por revisar",
                array_merge(array("id_orden_compra" => $idOrden,
                    "id_documento_fiscal" => $idDocumento, "estatus" => $estatus,
                    "pendientes_futuros" => $pendientes), $resumen));
        } catch (Exception $e) {
            if (isset($db) && $db->inTransaction()) {
                $db->rollBack();
            }
            if ($ruta !== "" && is_file($ruta)) {
                unlink($ruta);
            }
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    public function parsear($archivo, $idProveedor = 0) {
        if (empty($archivo["tmp_name"]) || !is_uploaded_file($archivo["tmp_name"])) {
            return $this->respuesta(true, "warning", "Selecciona un archivo XML");
        }
        if (!empty($archivo["error"]) || intval($archivo["size"]) > 5242880) {
            return $this->respuesta(true, "warning", "El XML no pudo cargarse o excede 5 MB");
        }
        $contenido = file_get_contents($archivo["tmp_name"]);
        if ($contenido === false || stripos($contenido, "<!DOCTYPE") !== false || stripos($contenido, "<!ENTITY") !== false) {
            return $this->respuesta(true, "danger", "El archivo XML no es seguro");
        }
        try {
            $cfdi = $this->leerCfdi($contenido);
            $idProveedor = intval($idProveedor);
            if ($idProveedor > 0) {
                $cfdi["conceptos"] = $this->enriquecerConceptosConProveedor($cfdi["conceptos"], $idProveedor);
            }
            return $this->respuesta(false, "success", "XML analizado correctamente", array(
                "uuid" => $cfdi["uuid"],
                "conceptos" => $cfdi["conceptos"],
                "subtotal" => $cfdi["subtotal"],
                "descuento" => $cfdi["descuento"],
                "total" => $cfdi["total"],
                "moneda" => $cfdi["moneda"],
                "nombre_emisor" => $cfdi["nombre_emisor"],
                "rfc_emisor" => $cfdi["rfc_emisor"]
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    /**
     * Modulo: ERP Compras
     * Funcion: enriquecerConceptosConProveedor
     * Documentacion IA: Codex GPT-5
     * Fecha: 2026-06-15
     * Descripcion: En parseo temporal de XML, reconoce conceptos contra relaciones activas
     * SKU-proveedor sin crear productos ni modificar contratos de proveedor.
     */
    private function enriquecerConceptosConProveedor($conceptos, $idProveedor) {
        if (!is_array($conceptos) || intval($idProveedor) <= 0) {
            return $conceptos;
        }
        $db = $this->getConexion();
        foreach ($conceptos as $idx => $concepto) {
            $match = $this->buscarSkuProveedorParaConcepto($db, intval($idProveedor), $concepto);
            if (!$match) {
                $conceptos[$idx]["resultado_conciliacion"] = isset($conceptos[$idx]["resultado_conciliacion"])
                    ? $conceptos[$idx]["resultado_conciliacion"] : "sin_coincidencia";
                continue;
            }
            if (!empty($match["ambigua"])) {
                $conceptos[$idx]["resultado_conciliacion"] = "ambigua";
                continue;
            }
            $conceptos[$idx]["id_sku_erp"] = intval($match["id_sku"]);
            $conceptos[$idx]["id_sku_proveedor"] = intval($match["id_sku_proveedor"]);
            $conceptos[$idx]["sku"] = $match["sku"];
            $conceptos[$idx]["sku_proveedor"] = $match["sku_proveedor"];
            $conceptos[$idx]["nombre"] = $match["nombre"];
            $conceptos[$idx]["unidad_erp"] = $match["unidad"];
            $conceptos[$idx]["costo_proveedor"] = $match["costo_ultimo"];
            $conceptos[$idx]["producto_registrado"] = 1;
            $conceptos[$idx]["requiere_revision"] = 0;
            $conceptos[$idx]["tipo_item"] = "producto";
            $conceptos[$idx]["resultado_conciliacion"] = "coincidencia_catalogo";
        }
        return $conceptos;
    }

    private function buscarSkuProveedorParaConcepto($db, $idProveedor, $concepto) {
        $clave = trim((string) (isset($concepto["no_identificacion"]) ? $concepto["no_identificacion"] : ""));
        $descripcion = trim((string) (isset($concepto["descripcion"]) ? $concepto["descripcion"] : ""));
        if ($clave === "" && $descripcion === "") {
            return null;
        }
        $stmt = $db->prepare("SELECT s.id_sku, s.sku, s.nombre, COALESCE(u.abreviatura, '') unidad,
                sp.id_sku_proveedor, sp.sku_proveedor, COALESCE(sp.costo_ultimo, 0) costo_ultimo,
                CASE
                    WHEN :clave_case <> '' AND LOWER(TRIM(sp.sku_proveedor)) = LOWER(TRIM(:clave_sp_case)) THEN 1
                    WHEN :clave_sku_case <> '' AND LOWER(TRIM(s.sku)) = LOWER(TRIM(:clave_sku_val_case)) THEN 2
                    WHEN :desc_case <> '' AND LOWER(TRIM(s.nombre)) = LOWER(TRIM(:desc_val_case)) THEN 3
                    ELSE 9
                END prioridad
            FROM erp_catalogo_sku_proveedores sp
            INNER JOIN erp_catalogo_skus s ON s.id_sku=sp.id_sku AND s.estatus='activo'
            LEFT JOIN erp_catalogo_unidades u ON u.id_unidad=s.id_unidad_base
            WHERE sp.id_proveedor=:proveedor AND sp.estatus='activo'
              AND (
                (:clave_cmp <> '' AND LOWER(TRIM(sp.sku_proveedor)) = LOWER(TRIM(:clave_sp)))
                OR (:clave_sku_cmp <> '' AND LOWER(TRIM(s.sku)) = LOWER(TRIM(:clave_sku_val)))
                OR (:desc_cmp <> '' AND LOWER(TRIM(s.nombre)) = LOWER(TRIM(:desc_val)))
              )
            ORDER BY prioridad ASC, sp.es_preferido DESC, sp.id_sku_proveedor DESC
            LIMIT 2");
        $stmt->execute(array(
            ":proveedor" => intval($idProveedor),
            ":clave_case" => $clave,
            ":clave_sp_case" => $clave,
            ":clave_sku_case" => $clave,
            ":clave_sku_val_case" => $clave,
            ":desc_case" => $descripcion,
            ":desc_val_case" => $descripcion,
            ":clave_cmp" => $clave,
            ":clave_sp" => $clave,
            ":clave_sku_cmp" => $clave,
            ":clave_sku_val" => $clave,
            ":desc_cmp" => $descripcion,
            ":desc_val" => $descripcion
        ));
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (count($rows) === 0) {
            return null;
        }
        if (count($rows) > 1 && intval($rows[0]["prioridad"]) === intval($rows[1]["prioridad"])) {
            return array("ambigua" => true);
        }
        return $rows[0];
    }

    public function listarDocumentos($idOrden) {
        try {
            $db = $this->getConexion();
            $stmt = $db->prepare("SELECT d.*,
                COUNT(c.id_documento_concepto) conceptos,
                SUM(c.resultado_conciliacion='coincidencia_exacta') coincidencias,
                SUM(c.resultado_conciliacion='sin_coincidencia') sin_coincidencia
                FROM erp_compras_documentos_fiscales d
                LEFT JOIN erp_compras_documentos_fiscales_conceptos c
                    ON c.id_documento_fiscal=d.id_documento_fiscal
                WHERE d.id_orden_compra=:orden GROUP BY d.id_documento_fiscal
                ORDER BY d.id_documento_fiscal DESC");
            $stmt->execute(array(":orden" => intval($idOrden)));
            return $this->respuesta(false, "success", "Documentos consultados", $stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    public function moverConceptos($idOrden, $conceptos, $asignaciones, $idUsuario) {
        $db = $this->getConexion();
        try {
            $idOrden = intval($idOrden);
            if ($idOrden <= 0) {
                throw new Exception("Selecciona una orden válida");
            }
            $idsConceptos = $this->normalizarIds($conceptos);
            $asignaciones = is_array($asignaciones) ? $asignaciones : array();
            if (empty($idsConceptos)) {
                return $this->respuesta(true, "warning", "Selecciona al menos un concepto para mover");
            }

            $db->beginTransaction();
            $stmt = $db->prepare("SELECT id_orden_compra, estatus
                FROM erp_compras_ordenes WHERE id_orden_compra=:orden FOR UPDATE");
            $stmt->execute(array(":orden" => $idOrden));
            $orden = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$orden || $orden["estatus"] !== "borrador") {
                throw new Exception("Solo se puede relacionar conceptos en ordenes en borrador");
            }

            $stmt = $db->prepare("SELECT c.id_documento_concepto, c.id_documento_fiscal,
                c.resultado_conciliacion, d.id_detalle, d.id_sku_erp, d.id_sku_proveedor
                FROM erp_compras_documentos_fiscales_conceptos c
                INNER JOIN erp_compras_documentos_fiscales f
                    ON f.id_documento_fiscal=c.id_documento_fiscal
                LEFT JOIN erp_compras_ordenes_detalle d
                    ON d.id_detalle=:detalle
                    AND d.id_orden_compra=f.id_orden_compra
                WHERE c.id_documento_concepto=:concepto
                    AND f.id_orden_compra=:orden");
            $update = $db->prepare("UPDATE erp_compras_documentos_fiscales_conceptos
                SET id_orden_detalle=:detalle, id_sku_erp=:sku, id_sku_proveedor=:sku_proveedor,
                    resultado_conciliacion='coincidencia_manual', resuelto_por=:usuario,
                    fecha_resolucion=NOW()
                WHERE id_documento_concepto=:concepto");
            $mover = 0;
            $omitidos = 0;
            $documentos = array();
            $detallesValidos = $this->obtenerDetallesOrden($db, $idOrden);
            $detallesMap = array();
            foreach ($detallesValidos as $detalle) {
                $detallesMap[intval($detalle["id_detalle"])] = $detalle;
            }

            foreach ($idsConceptos as $idConcepto) {
                $idDetalle = isset($asignaciones[$idConcepto]) ? intval($asignaciones[$idConcepto]) : 0;
                if ($idDetalle <= 0 || empty($detallesMap[$idDetalle])) {
                    $omitidos++;
                    continue;
                }
                $stmt->execute(array(
                    ":detalle" => $idDetalle,
                    ":concepto" => $idConcepto,
                    ":orden" => $idOrden
                ));
                $fila = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$fila) {
                    $omitidos++;
                    continue;
                }
                $detalle = $detallesMap[$idDetalle];
                $update->execute(array(
                    ":detalle" => $idDetalle,
                    ":sku" => !empty($detalle["id_sku_erp"]) ? intval($detalle["id_sku_erp"]) : null,
                    ":sku_proveedor" => !empty($detalle["id_sku_proveedor"])
                        ? intval($detalle["id_sku_proveedor"]) : null,
                    ":usuario" => intval($idUsuario) ?: null,
                    ":concepto" => $idConcepto
                ));
                $documentos[intval($fila["id_documento_fiscal"])] = true;
                $mover++;
            }

            if ($mover > 0) {
                foreach (array_keys($documentos) as $idDocumento) {
                    $this->actualizarEstatusDocumento($db, intval($idDocumento));
                    $this->sincronizarNotificacionXmlDocumento($db, $idOrden, intval($idDocumento), $idUsuario);
                }
                $this->sincronizarPendientesOrden($db, $idOrden, max(array_keys($documentos)));
            }

            $db->commit();
            return $this->respuesta(false, "success", $omitidos > 0 ? "Algunos conceptos quedaron sin asignar" : "Conceptos relacionados", array(
                "id_orden_compra" => $idOrden,
                "procesados" => $mover,
                "omitidos" => $omitidos
            ));
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    public function descartarConceptos($idOrden, $conceptos, $idUsuario) {
        $db = $this->getConexion();
        try {
            $idOrden = intval($idOrden);
            if ($idOrden <= 0) {
                throw new Exception("Selecciona una orden válida");
            }
            $idsConceptos = $this->normalizarIds($conceptos);
            if (empty($idsConceptos)) {
                return $this->respuesta(true, "warning", "Selecciona al menos un concepto para descartar");
            }

            $db->beginTransaction();
            $stmt = $db->prepare("SELECT id_orden_compra, estatus
                FROM erp_compras_ordenes WHERE id_orden_compra=:orden FOR UPDATE");
            $stmt->execute(array(":orden" => $idOrden));
            $orden = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$orden || $orden["estatus"] !== "borrador") {
                throw new Exception("Solo se puede descartar conceptos en ordenes en borrador");
            }

            $concepto = $db->prepare("SELECT c.id_documento_fiscal
                FROM erp_compras_documentos_fiscales_conceptos c
                INNER JOIN erp_compras_documentos_fiscales f
                    ON f.id_documento_fiscal=c.id_documento_fiscal
                WHERE c.id_documento_concepto=:concepto AND f.id_orden_compra=:orden");
            $update = $db->prepare("UPDATE erp_compras_documentos_fiscales_conceptos
                SET id_orden_detalle=NULL, id_sku_erp=NULL, id_sku_proveedor=NULL,
                    resultado_conciliacion='descartado', resuelto_por=:usuario,
                    observaciones_conciliacion='Descartado desde conciliación',
                    fecha_resolucion=NOW()
                WHERE id_documento_concepto=:concepto");

            $documentos = array();
            $descartados = 0;
            $omitidos = 0;
            foreach ($idsConceptos as $idConcepto) {
                $concepto->execute(array(":concepto" => $idConcepto, ":orden" => $idOrden));
                $fila = $concepto->fetch(PDO::FETCH_ASSOC);
                if (!$fila) {
                    $omitidos++;
                    continue;
                }
                $update->execute(array(
                    ":usuario" => intval($idUsuario) ?: null,
                    ":concepto" => $idConcepto
                ));
                $documentos[intval($fila["id_documento_fiscal"])] = true;
                $descartados++;
            }

            if ($descartados > 0) {
                foreach (array_keys($documentos) as $idDocumento) {
                    $this->actualizarEstatusDocumento($db, intval($idDocumento));
                    $this->sincronizarNotificacionXmlDocumento($db, $idOrden, intval($idDocumento), $idUsuario);
                }
                $this->sincronizarPendientesOrden($db, $idOrden, max(array_keys($documentos)));
            }
            $db->commit();
            return $this->respuesta(false, "success", $omitidos > 0 ? "Algunos conceptos no aplicaron para descartar" : "Conceptos descartados", array(
                "id_orden_compra" => $idOrden,
                "descartados" => $descartados,
                "omitidos" => $omitidos
            ));
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    public function consultarConciliacion($idOrden) {
        try {
            $db = $this->getConexion();
            $idOrden = intval($idOrden);
            $stmt = $db->prepare("SELECT id_orden_compra, estatus
                FROM erp_compras_ordenes WHERE id_orden_compra=:orden");
            $stmt->execute(array(":orden" => $idOrden));
            $orden = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$orden) {
                throw new Exception("Orden de compra no encontrada");
            }

            $stmt = $db->prepare("SELECT c.id_documento_concepto, c.id_documento_fiscal,
                c.id_orden_detalle, c.id_sku_erp, c.id_sku_proveedor,
                c.no_identificacion, c.descripcion, c.cantidad cantidad_xml,
                c.valor_unitario costo_xml, c.importe, c.descuento,
                c.iva_porcentaje, c.ieps_porcentaje, c.resultado_conciliacion,
                c.observaciones_conciliacion, f.uuid, f.serie, f.folio,
                d.sku sku_orden, d.nombre_producto nombre_orden,
                d.cantidad cantidad_orden, d.costo_unitario costo_orden,
                CASE WHEN d.id_detalle IS NULL THEN NULL
                    ELSE c.cantidad-d.cantidad END diferencia_cantidad,
                CASE WHEN d.id_detalle IS NULL THEN NULL
                    ELSE c.valor_unitario-d.costo_unitario END diferencia_costo
                FROM erp_compras_documentos_fiscales_conceptos c
                INNER JOIN erp_compras_documentos_fiscales f
                    ON f.id_documento_fiscal=c.id_documento_fiscal
                LEFT JOIN erp_compras_ordenes_detalle d
                    ON d.id_detalle=c.id_orden_detalle AND d.id_orden_compra=f.id_orden_compra
                WHERE f.id_orden_compra=:orden
                ORDER BY f.id_documento_fiscal DESC, c.id_documento_concepto");
            $stmt->execute(array(":orden" => $idOrden));
            $conceptos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = $db->prepare("SELECT d.id_detalle, d.id_sku_erp, d.id_sku_proveedor,
                d.id_producto, d.sku, d.nombre_producto, d.unidad, d.cantidad,
                d.costo_unitario, d.id_solicitud_detalle,
                COALESCE(SUM(CASE WHEN f.id_documento_fiscal IS NOT NULL
                    AND c.resultado_conciliacion IN
                    ('coincidencia_exacta','coincidencia_manual')
                    THEN c.cantidad ELSE 0 END),0) cantidad_xml,
                COUNT(CASE WHEN f.id_documento_fiscal IS NOT NULL
                    AND c.resultado_conciliacion IN
                    ('coincidencia_exacta','coincidencia_manual')
                    THEN 1 END) conceptos_relacionados
                FROM erp_compras_ordenes_detalle d
                LEFT JOIN erp_compras_documentos_fiscales_conceptos c
                    ON c.id_orden_detalle=d.id_detalle
                LEFT JOIN erp_compras_documentos_fiscales f
                    ON f.id_documento_fiscal=c.id_documento_fiscal
                    AND f.id_orden_compra=d.id_orden_compra
                WHERE d.id_orden_compra=:orden
                GROUP BY d.id_detalle ORDER BY d.id_detalle");
            $stmt->execute(array(":orden" => $idOrden));
            $detalleOrden = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = $db->prepare("SELECT * FROM erp_compras_ordenes_productos_atencion
                WHERE id_orden_compra=:orden ORDER BY estatus, id_producto_atencion");
            $stmt->execute(array(":orden" => $idOrden));
            $pendientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $resumen = array(
                "conceptos" => count($conceptos),
                "coincidencias" => 0,
                "requieren_revision" => 0,
                "esperados_no_incluidos" => 0
            );
            foreach ($conceptos as $concepto) {
                if (in_array($concepto["resultado_conciliacion"],
                    array("coincidencia_exacta", "coincidencia_manual"), true)) {
                    $resumen["coincidencias"]++;
                } elseif ($concepto["resultado_conciliacion"] !== "descartado") {
                    $resumen["requieren_revision"]++;
                }
            }
            foreach ($detalleOrden as $detalle) {
                if (intval($detalle["conceptos_relacionados"]) === 0) {
                    $resumen["esperados_no_incluidos"]++;
                }
            }

            return $this->respuesta(false, "success", "Conciliacion consultada", array(
                "orden" => $orden,
                "resumen" => $resumen,
                "conceptos" => $conceptos,
                "detalle_orden" => $detalleOrden,
                "pendientes" => $pendientes
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    public function resolverConcepto($idOrden, $idConcepto, $idOrdenDetalle, $idUsuario) {
        $db = $this->getConexion();
        try {
            $db->beginTransaction();
            $stmt = $db->prepare("SELECT id_orden_compra, estatus
                FROM erp_compras_ordenes WHERE id_orden_compra=:orden FOR UPDATE");
            $stmt->execute(array(":orden" => intval($idOrden)));
            $orden = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$orden || $orden["estatus"] !== "borrador") {
                throw new Exception("Solo una orden en borrador permite resolver conciliaciones");
            }

            $stmt = $db->prepare("SELECT c.*, f.id_orden_compra
                FROM erp_compras_documentos_fiscales_conceptos c
                INNER JOIN erp_compras_documentos_fiscales f
                    ON f.id_documento_fiscal=c.id_documento_fiscal
                WHERE c.id_documento_concepto=:concepto
                    AND f.id_orden_compra=:orden FOR UPDATE");
            $stmt->execute(array(
                ":concepto" => intval($idConcepto),
                ":orden" => intval($idOrden)
            ));
            $concepto = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$concepto) {
                throw new Exception("Concepto XML no encontrado en la orden");
            }

            $stmt = $db->prepare("SELECT id_detalle, id_sku_erp, id_sku_proveedor
                FROM erp_compras_ordenes_detalle
                WHERE id_detalle=:detalle AND id_orden_compra=:orden");
            $stmt->execute(array(
                ":detalle" => intval($idOrdenDetalle),
                ":orden" => intval($idOrden)
            ));
            $detalle = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$detalle) {
                throw new Exception("La partida seleccionada no pertenece a la orden");
            }

            $db->prepare("UPDATE erp_compras_documentos_fiscales_conceptos
                SET id_orden_detalle=:detalle, id_sku_erp=:sku,
                    id_sku_proveedor=:sku_proveedor,
                    resultado_conciliacion='coincidencia_manual',
                    resuelto_por=:usuario, fecha_resolucion=NOW()
                WHERE id_documento_concepto=:concepto")->execute(array(
                    ":detalle" => intval($detalle["id_detalle"]),
                    ":sku" => intval($detalle["id_sku_erp"]),
                    ":sku_proveedor" => !empty($detalle["id_sku_proveedor"])
                        ? intval($detalle["id_sku_proveedor"]) : null,
                    ":usuario" => intval($idUsuario) ?: null,
                    ":concepto" => intval($idConcepto)
                ));

            $conceptoFiscal = array(
                "clave_producto_sat" => $concepto["clave_producto_sat"],
                "clave_unidad_sat" => $concepto["clave_unidad_sat"],
                "objeto_impuesto" => $concepto["objeto_impuesto"],
                "iva_porcentaje" => $concepto["iva_porcentaje"],
                "ieps_porcentaje" => $concepto["ieps_porcentaje"]
            );
            $this->evaluarSugerenciaFiscalSku($db, intval($detalle["id_sku_erp"]), $conceptoFiscal);
            $this->actualizarEstatusDocumento($db, intval($concepto["id_documento_fiscal"]));
            $this->sincronizarNotificacionXmlDocumento($db, intval($idOrden), intval($concepto["id_documento_fiscal"]), $idUsuario);
            $this->sincronizarPendientesOrden(
                $db,
                intval($idOrden),
                intval($concepto["id_documento_fiscal"])
            );
            $db->commit();
            return $this->respuesta(false, "success", "Concepto relacionado con la partida", array(
                "id_orden_compra" => intval($idOrden),
                "id_documento_concepto" => intval($idConcepto),
                "id_orden_detalle" => intval($idOrdenDetalle)
            ));
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    public function sincronizarPendientes($idOrden) {
        $db = $this->getConexion();
        try {
            $db->beginTransaction();
            $stmt = $db->prepare("SELECT id_documento_fiscal
                FROM erp_compras_documentos_fiscales
                WHERE id_orden_compra=:orden
                ORDER BY id_documento_fiscal DESC LIMIT 1");
            $stmt->execute(array(":orden" => intval($idOrden)));
            $idDocumento = intval($stmt->fetchColumn());
            $pendientes = $idDocumento > 0
                ? $this->sincronizarPendientesOrden($db, intval($idOrden), $idDocumento)
                : 0;
            $db->commit();
            return $this->respuesta(false, "success", "Pendientes sincronizados", array(
                "id_orden_compra" => intval($idOrden),
                "pendientes" => $pendientes
            ));
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    private function leerCfdi($contenido) {
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        if (!$dom->loadXML($contenido, LIBXML_NONET | LIBXML_NOBLANKS | LIBXML_NOERROR | LIBXML_NOWARNING)) {
            throw new Exception("El archivo no contiene un XML valido");
        }
        $xp = new DOMXPath($dom);
        $comprobante = $xp->query("/*[local-name()='Comprobante']")->item(0);
        if (!$comprobante) {
            throw new Exception("El XML no es un CFDI");
        }
        $emisor = $xp->query("/*[local-name()='Comprobante']/*[local-name()='Emisor']")->item(0);
        $receptor = $xp->query("/*[local-name()='Comprobante']/*[local-name()='Receptor']")->item(0);
        $timbre = $xp->query("//*[local-name()='TimbreFiscalDigital']")->item(0);
        $conceptos = array();
        foreach ($xp->query("/*[local-name()='Comprobante']/*[local-name()='Conceptos']/*[local-name()='Concepto']") as $nodo) {
            $iva = 0;
            $ieps = 0;
            foreach ($xp->query(".//*[local-name()='Traslado']", $nodo) as $traslado) {
                $tasa = floatval($this->attr($traslado, "TasaOCuota")) * 100;
                if ($this->attr($traslado, "Impuesto") === "002") { $iva = $tasa; }
                if ($this->attr($traslado, "Impuesto") === "003") { $ieps = $tasa; }
            }
            $conceptos[] = array(
                "no_identificacion" => $this->attr($nodo, "NoIdentificacion"),
                "descripcion" => $this->attr($nodo, "Descripcion"),
                "clave_producto_sat" => $this->attr($nodo, "ClaveProdServ"),
                "clave_unidad_sat" => $this->attr($nodo, "ClaveUnidad"),
                "unidad" => $this->attr($nodo, "Unidad"),
                "objeto_impuesto" => $this->attr($nodo, "ObjetoImp"),
                "cantidad" => floatval($this->attr($nodo, "Cantidad")),
                "valor_unitario" => floatval($this->attr($nodo, "ValorUnitario")),
                "importe" => floatval($this->attr($nodo, "Importe")),
                "descuento" => floatval($this->attr($nodo, "Descuento")),
                "iva_porcentaje" => $iva, "ieps_porcentaje" => $ieps
            );
        }
        if (empty($conceptos)) {
            throw new Exception("El CFDI no contiene conceptos");
        }
        return array(
            "uuid" => $timbre ? strtoupper($this->attr($timbre, "UUID")) : "",
            "version" => $this->attr($comprobante, "Version"),
            "serie" => $this->attr($comprobante, "Serie"), "folio" => $this->attr($comprobante, "Folio"),
            "fecha" => str_replace("T", " ", $this->attr($comprobante, "Fecha")),
            "moneda" => $this->attr($comprobante, "Moneda"),
            "tipo_cambio" => floatval($this->attr($comprobante, "TipoCambio")) ?: 1,
            "subtotal" => floatval($this->attr($comprobante, "SubTotal")),
            "descuento" => floatval($this->attr($comprobante, "Descuento")),
            "total" => floatval($this->attr($comprobante, "Total")),
            "rfc_emisor" => $emisor ? $this->attr($emisor, "Rfc") : "",
            "nombre_emisor" => $emisor ? $this->attr($emisor, "Nombre") : "",
            "rfc_receptor" => $receptor ? $this->attr($receptor, "Rfc") : "",
            "nombre_receptor" => $receptor ? $this->attr($receptor, "Nombre") : "",
            "conceptos" => $conceptos
        );
    }

    private function conciliarConcepto($db, $idOrden, $concepto) {
        $clave = trim($concepto["no_identificacion"]);
        if ($clave !== "") {
            $stmt = $db->prepare("SELECT d.id_detalle, d.id_sku_erp, d.id_sku_proveedor
                FROM erp_compras_ordenes_detalle d
                LEFT JOIN erp_catalogo_sku_proveedores sp ON sp.id_sku_proveedor=d.id_sku_proveedor
                WHERE d.id_orden_compra=:orden
                AND (BINARY TRIM(d.sku)=BINARY :clave
                    OR BINARY TRIM(sp.sku_proveedor)=BINARY :clave2)");
            $stmt->execute(array(":orden" => $idOrden, ":clave" => $clave, ":clave2" => $clave));
            $coincidencias = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (count($coincidencias) === 1) {
                return array(
                    "id_detalle" => intval($coincidencias[0]["id_detalle"]),
                    "id_sku" => intval($coincidencias[0]["id_sku_erp"]),
                    "id_sku_proveedor" => intval($coincidencias[0]["id_sku_proveedor"]),
                    "resultado" => "coincidencia_exacta"
                );
            }
            if (count($coincidencias) > 1) {
                return array("id_detalle" => 0, "id_sku" => 0, "resultado" => "ambigua");
            }
        }

        $stmt = $db->prepare("SELECT id_proveedor FROM erp_compras_ordenes WHERE id_orden_compra=:orden LIMIT 1");
        $stmt->execute(array(":orden" => intval($idOrden)));
        $idProveedor = intval($stmt->fetchColumn());
        if ($idProveedor > 0) {
            $matchCatalogo = $this->buscarSkuProveedorParaConcepto($db, $idProveedor, $concepto);
            if ($matchCatalogo && empty($matchCatalogo["ambigua"])) {
                return array(
                    "id_detalle" => 0,
                    "id_sku" => intval($matchCatalogo["id_sku"]),
                    "id_sku_proveedor" => intval($matchCatalogo["id_sku_proveedor"]),
                    "resultado" => "coincidencia_catalogo"
                );
            }
            if ($matchCatalogo && !empty($matchCatalogo["ambigua"])) {
                return array("id_detalle" => 0, "id_sku" => 0, "resultado" => "ambigua");
            }
        }

        return array("id_detalle" => 0, "id_sku" => 0, "resultado" => "sin_coincidencia");
    }

    private function evaluarSugerenciaFiscalSku($db, $idSku, $concepto) {
        $stmt = $db->prepare("SELECT * FROM erp_catalogo_sku_impuestos WHERE id_sku=:sku");
        $stmt->execute(array(":sku" => $idSku));
        $actual = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$actual) {
            return $this->conceptoTieneFiscal($concepto);
        }
        foreach (array("clave_producto_sat", "clave_unidad_sat", "objeto_impuesto") as $campo) {
            $xml = trim((string) (isset($concepto[$campo]) ? $concepto[$campo] : ""));
            $maestro = trim((string) (isset($actual[$campo]) ? $actual[$campo] : ""));
            if ($xml !== "" && $maestro === "") {
                return true;
            }
            if ($xml !== "" && $maestro !== "" && strcasecmp($xml, $maestro) !== 0) {
                return true;
            }
        }
        foreach (array("iva_porcentaje", "ieps_porcentaje") as $campo) {
            if (!isset($concepto[$campo]) || $concepto[$campo] === null || $concepto[$campo] === "") {
                continue;
            }
            $xml = round(floatval($concepto[$campo]), 6);
            $maestro = isset($actual[$campo]) && $actual[$campo] !== null ? round(floatval($actual[$campo]), 6) : null;
            if ($maestro === null || $xml !== $maestro) {
                return true;
            }
        }
        return false;
    }

    private function conceptoTieneFiscal($concepto) {
        return trim((string) (isset($concepto["clave_producto_sat"]) ? $concepto["clave_producto_sat"] : "")) !== ""
            || trim((string) (isset($concepto["clave_unidad_sat"]) ? $concepto["clave_unidad_sat"] : "")) !== ""
            || trim((string) (isset($concepto["objeto_impuesto"]) ? $concepto["objeto_impuesto"] : "")) !== ""
            || (isset($concepto["iva_porcentaje"]) && floatval($concepto["iva_porcentaje"]) > 0)
            || (isset($concepto["ieps_porcentaje"]) && floatval($concepto["ieps_porcentaje"]) > 0);
    }

    private function insertarConcepto($db, $idDocumento, $c, $match) {
        $stmt = $db->prepare("INSERT INTO erp_compras_documentos_fiscales_conceptos
            (id_documento_fiscal,id_orden_detalle,id_sku_erp,id_sku_proveedor,
            no_identificacion,descripcion,
            clave_producto_sat,clave_unidad_sat,unidad,objeto_impuesto,cantidad,
            valor_unitario,importe,descuento,iva_porcentaje,ieps_porcentaje,resultado_conciliacion)
            VALUES (:documento,:detalle,:sku,:sku_proveedor,:identificacion,:descripcion,:producto_sat,
            :unidad_sat,:unidad,:objeto,:cantidad,:valor,:importe,:descuento,:iva,:ieps,:resultado)");
        $stmt->execute(array(
            ":documento" => $idDocumento, ":detalle" => $match["id_detalle"] ?: null,
            ":sku" => $match["id_sku"] ?: null,
            ":sku_proveedor" => !empty($match["id_sku_proveedor"])
                ? $match["id_sku_proveedor"] : null,
            ":identificacion" => $c["no_identificacion"],
            ":descripcion" => $c["descripcion"], ":producto_sat" => $c["clave_producto_sat"],
            ":unidad_sat" => $c["clave_unidad_sat"], ":unidad" => $c["unidad"],
            ":objeto" => $c["objeto_impuesto"], ":cantidad" => $c["cantidad"],
            ":valor" => $c["valor_unitario"], ":importe" => $c["importe"],
            ":descuento" => $c["descuento"], ":iva" => $c["iva_porcentaje"],
            ":ieps" => $c["ieps_porcentaje"], ":resultado" => $match["resultado"]
        ));
    }

    private function actualizarEstatusDocumento($db, $idDocumento) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM
            erp_compras_documentos_fiscales_conceptos
            WHERE id_documento_fiscal=:documento
            AND resultado_conciliacion NOT IN ('coincidencia_exacta','coincidencia_manual','descartado')");
        $stmt->execute(array(":documento" => intval($idDocumento)));
        $estatus = intval($stmt->fetchColumn()) > 0 ? "requiere_revision" : "conciliado";
        $db->prepare("UPDATE erp_compras_documentos_fiscales
            SET estatus_conciliacion=:estatus WHERE id_documento_fiscal=:documento")
            ->execute(array(":estatus" => $estatus, ":documento" => intval($idDocumento)));
        return $estatus;
    }

    private function obtenerDetallesOrden($db, $idOrden) {
        $stmt = $db->prepare("SELECT id_detalle, id_sku_erp, id_sku_proveedor
            FROM erp_compras_ordenes_detalle
            WHERE id_orden_compra=:orden");
        $stmt->execute(array(":orden" => intval($idOrden)));
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function normalizarIds($entrada) {
        if (is_string($entrada)) {
            $entrada = trim($entrada);
            if ($entrada === "") { return array(); }
            $tmp = json_decode($entrada, true);
            if (is_array($tmp)) {
                $entrada = $tmp;
            } else {
                $entrada = preg_split("/,|;|\n/", $entrada);
            }
        }
        if (!is_array($entrada)) { return array(); }
        $ids = array();
        foreach ($entrada as $valor) {
            if (is_array($valor)) {
                if (isset($valor["id_documento_concepto"])) {
                    $ids[] = intval($valor["id_documento_concepto"]);
                } elseif (isset($valor["concepto"])) {
                    $ids[] = intval($valor["concepto"]);
                }
            } else {
                $ids[] = intval($valor);
            }
        }
        return array_values(array_filter(array_map("intval", $ids), function($valor) { return $valor > 0; }));
    }

    private function sincronizarPendientesOrden($db, $idOrden, $idDocumento) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM erp_compras_documentos_fiscales
            WHERE id_orden_compra=:orden");
        $stmt->execute(array(":orden" => intval($idOrden)));
        if (intval($stmt->fetchColumn()) === 0) {
            return 0;
        }

        $stmt = $db->prepare("SELECT o.id_solicitud, o.id_proveedor,
            d.id_detalle, d.id_producto, d.id_sku_erp, d.id_sku_proveedor,
            d.sku, d.nombre_producto, d.cantidad,
            COUNT(CASE WHEN f.id_documento_fiscal IS NOT NULL
                AND c.resultado_conciliacion IN
                ('coincidencia_exacta','coincidencia_manual') THEN 1 END) coincidencias,
            COALESCE(SUM(CASE WHEN f.id_documento_fiscal IS NOT NULL
                AND c.resultado_conciliacion IN
                ('coincidencia_exacta','coincidencia_manual')
                THEN c.cantidad ELSE 0 END),0) cantidad_comprada
            FROM erp_compras_ordenes o
            INNER JOIN erp_compras_ordenes_detalle d
                ON d.id_orden_compra=o.id_orden_compra
            LEFT JOIN erp_compras_documentos_fiscales_conceptos c
                ON c.id_orden_detalle=d.id_detalle
            LEFT JOIN erp_compras_documentos_fiscales f
                ON f.id_documento_fiscal=c.id_documento_fiscal
                AND f.id_orden_compra=o.id_orden_compra
            WHERE o.id_orden_compra=:orden
            GROUP BY d.id_detalle");
        $stmt->execute(array(":orden" => intval($idOrden)));
        $partidas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $pendientes = 0;

        foreach ($partidas as $partida) {
            $stmt = $db->prepare("SELECT id_producto_atencion
                FROM erp_compras_ordenes_productos_atencion
                WHERE id_orden_compra=:orden AND id_orden_detalle=:detalle
                    AND motivo='no_incluido_xml' LIMIT 1");
            $stmt->execute(array(
                ":orden" => intval($idOrden),
                ":detalle" => intval($partida["id_detalle"])
            ));
            $idPendiente = intval($stmt->fetchColumn());
            $tieneCoincidencia = intval($partida["coincidencias"]) > 0;
            $estatus = $tieneCoincidencia ? "resuelto" : "pendiente";

            if ($idPendiente > 0) {
                $db->prepare("UPDATE erp_compras_ordenes_productos_atencion SET
                    id_documento_fiscal=:documento, cantidad_solicitada=:solicitada,
                    cantidad_comprada=:comprada, estatus=:estatus,
                    fecha_actualizacion=NOW()
                    WHERE id_producto_atencion=:id")->execute(array(
                        ":documento" => intval($idDocumento) ?: null,
                        ":solicitada" => $partida["cantidad"],
                        ":comprada" => $partida["cantidad_comprada"],
                        ":estatus" => $estatus,
                        ":id" => $idPendiente
                    ));
            } elseif (!$tieneCoincidencia) {
                $db->prepare("INSERT INTO erp_compras_ordenes_productos_atencion
                    (id_orden_compra,id_solicitud,id_proveedor,id_producto,
                    id_sku_erp,id_sku_proveedor,id_orden_detalle,id_documento_fiscal,
                    sku,nombre_producto,cantidad_solicitada,cantidad_comprada,
                    motivo,estatus,observaciones,fecha_actualizacion)
                    VALUES (:orden,:solicitud,:proveedor,:producto,:sku_erp,
                    :sku_proveedor,:detalle,:documento,:sku,:nombre,:solicitada,
                    :comprada,'no_incluido_xml','pendiente',
                    'Partida esperada no localizada en los XML de la orden',NOW())")
                    ->execute(array(
                        ":orden" => intval($idOrden),
                        ":solicitud" => !empty($partida["id_solicitud"])
                            ? intval($partida["id_solicitud"]) : null,
                        ":proveedor" => intval($partida["id_proveedor"]),
                        ":producto" => intval($partida["id_producto"]),
                        ":sku_erp" => intval($partida["id_sku_erp"]),
                        ":sku_proveedor" => !empty($partida["id_sku_proveedor"])
                            ? intval($partida["id_sku_proveedor"]) : null,
                        ":detalle" => intval($partida["id_detalle"]),
                        ":documento" => intval($idDocumento) ?: null,
                        ":sku" => $partida["sku"],
                        ":nombre" => $partida["nombre_producto"],
                        ":solicitada" => $partida["cantidad"],
                        ":comprada" => $partida["cantidad_comprada"]
                    ));
            }

            if (!$tieneCoincidencia) {
                $pendientes++;
            }
        }

        return $pendientes;
    }

    private function sincronizarNotificacionXmlDocumento($db, $idOrden, $idDocumento, $idUsuario) {
        // [Codex: GPT-5 2026-06-16] Alerta transversal por XML con conceptos sin conciliacion completa.
        try {
            $stmt = $db->prepare("SELECT f.id_documento_fiscal, f.uuid, f.serie, f.folio,
                    f.estatus_conciliacion, o.folio folio_orden,
                    COUNT(c.id_documento_concepto) conceptos,
                    SUM(CASE WHEN c.resultado_conciliacion NOT IN
                        ('coincidencia_exacta','coincidencia_manual','descartado') THEN 1 ELSE 0 END) pendientes
                FROM erp_compras_documentos_fiscales f
                INNER JOIN erp_compras_ordenes o ON o.id_orden_compra=f.id_orden_compra
                LEFT JOIN erp_compras_documentos_fiscales_conceptos c
                    ON c.id_documento_fiscal=f.id_documento_fiscal
                WHERE f.id_orden_compra=:orden AND f.id_documento_fiscal=:documento
                GROUP BY f.id_documento_fiscal");
            $stmt->execute(array(
                ":orden" => intval($idOrden),
                ":documento" => intval($idDocumento)
            ));
            $doc = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$doc) {
                return;
            }

            $huella = $this->huellaNotificacionXml($idOrden, $idDocumento);
            $pendientes = intval(isset($doc["pendientes"]) ? $doc["pendientes"] : 0);
            if ($pendientes <= 0) {
                $this->cerrarNotificacionXmlDocumento($db, intval($idOrden), $huella);
                return;
            }

            $folioOrden = trim((string) (isset($doc["folio_orden"]) ? $doc["folio_orden"] : ("OC-" . intval($idOrden))));
            $folioXml = trim((string) (isset($doc["serie"]) ? $doc["serie"] : "") . (isset($doc["folio"]) ? $doc["folio"] : ""));
            $referenciaXml = $folioXml !== "" ? $folioXml : (trim((string) $doc["uuid"]) !== "" ? trim((string) $doc["uuid"]) : "documento " . intval($idDocumento));

            $this->guardarNotificacionXml($db, array(
                "tipo" => "compra_xml_conceptos_revision",
                "modulo_origen" => "compras",
                "entidad_origen" => "erp_compras_documentos_fiscales",
                "id_entidad_origen" => intval($idOrden),
                "area_responsable" => "catalogo",
                "permiso_requerido" => "catalogo.editar",
                "titulo" => "XML con conceptos por revisar en " . $folioOrden,
                "descripcion" => "El XML " . $referenciaXml . " tiene " . $pendientes . " concepto(s) sin conciliacion completa.",
                "prioridad" => "normal",
                "url_accion" => "/catalogoerp/configuracion",
                "payload_json" => array(
                    "huella" => $huella,
                    "id_orden_compra" => intval($idOrden),
                    "folio_orden" => $folioOrden,
                    "id_documento_fiscal" => intval($idDocumento),
                    "uuid" => isset($doc["uuid"]) ? $doc["uuid"] : "",
                    "conceptos" => intval(isset($doc["conceptos"]) ? $doc["conceptos"] : 0),
                    "pendientes" => $pendientes
                ),
                "creado_por" => intval($idUsuario) ?: null
            ));
        } catch (Exception $e) {
            return;
        }
    }

    private function guardarNotificacionXml($db, $datos) {
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
                area_responsable=:area, permiso_requerido=:permiso,
                titulo=:titulo, descripcion=:descripcion, prioridad=:prioridad,
                url_accion=:url, payload_json=:payload, fecha_actualizacion=NOW()
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
            return;
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
    }

    private function cerrarNotificacionXmlDocumento($db, $idOrden, $huella) {
        $stmt = $db->prepare("UPDATE erp_notificaciones SET
            estatus='resuelta', fecha_resolucion=NOW(), fecha_actualizacion=NOW()
            WHERE tipo='compra_xml_conceptos_revision'
              AND modulo_origen='compras'
              AND entidad_origen='erp_compras_documentos_fiscales'
              AND id_entidad_origen=:orden
              AND estatus IN ('pendiente','en_revision','bloqueada')
              AND payload_json LIKE :huella");
        $stmt->execute(array(
            ":orden" => intval($idOrden),
            ":huella" => '%"huella":"' . trim((string) $huella) . '"%'
        ));
    }

    private function huellaNotificacionXml($idOrden, $idDocumento) {
        return hash("sha256", "notificacion|compras|xml|orden:" . intval($idOrden) . "|documento:" . intval($idDocumento));
    }

    private function attr($nodo, $nombre) {
        return $nodo && $nodo->attributes->getNamedItem($nombre)
            ? trim($nodo->attributes->getNamedItem($nombre)->nodeValue) : "";
    }

    private function respuesta($error, $tipo, $mensaje, $depurar = array()) {
        return array("error" => $error, "tipo" => $tipo, "mensaje" => $mensaje, "depurar" => $depurar);
    }
}
