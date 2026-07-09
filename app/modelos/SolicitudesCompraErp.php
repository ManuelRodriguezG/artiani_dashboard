<?php

class SolicitudesCompraErp extends CRUD {

    private $columnasCache = array();

    private function columnaExiste($tabla, $columna) {
        $clave = $tabla . "." . $columna;
        if (isset($this->columnasCache[$clave])) {
            return $this->columnasCache[$clave];
        }
        try {
            $stmt = $this->getConexion()->prepare("SHOW COLUMNS FROM {$tabla} LIKE :columna");
            $stmt->execute(array(":columna" => $columna));
            $this->columnasCache[$clave] = (bool) $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $this->columnasCache[$clave] = false;
        }
        return $this->columnasCache[$clave];
    }

    private function prefijoAlias($alias) {
        $alias = trim((string) $alias);
        return $alias !== "" ? $alias . "." : "";
    }

    private function expresionNombreUsuario($alias = "") {
        $prefijo = $this->prefijoAlias($alias);
        $opciones = array();
        if ($this->columnaExiste("sys_usuarios", "nombre_mostrar")) {
            $opciones[] = "NULLIF(TRIM(" . $prefijo . "nombre_mostrar), '')";
        }
        if ($this->columnaExiste("sys_usuarios", "nombres")) {
            $partes = array("COALESCE(" . $prefijo . "nombres,'')");
            if ($this->columnaExiste("sys_usuarios", "apellido_paterno")) {
                $partes[] = "COALESCE(" . $prefijo . "apellido_paterno,'')";
            }
            if ($this->columnaExiste("sys_usuarios", "apellido_materno")) {
                $partes[] = "COALESCE(" . $prefijo . "apellido_materno,'')";
            }
            $opciones[] = "NULLIF(TRIM(CONCAT(" . implode(", ' ', ", $partes) . ")), '')";
        }
        if ($this->columnaExiste("sys_usuarios", "alias")) {
            $opciones[] = "NULLIF(TRIM(" . $prefijo . "alias), '')";
        }
        if ($this->columnaExiste("sys_usuarios", "correo")) {
            $opciones[] = "NULLIF(TRIM(" . $prefijo . "correo), '')";
        }
        $opciones[] = "CONCAT('Usuario ', " . $prefijo . "id_usuario)";
        return "COALESCE(" . implode(", ", $opciones) . ")";
    }

    private function expresionAreaUsuario($alias = "") {
        if ($this->columnaExiste("sys_usuarios", "area_departamento")) {
            return $this->prefijoAlias($alias) . "area_departamento";
        }
        return "NULL";
    }

    public function catalogos() {
        try {
            $db = $this->getConexion();
            $usuarioNombreExpr = $this->expresionNombreUsuario();
            $usuarioAreaExpr = $this->expresionAreaUsuario();
            $proveedores = $db->query("SELECT id_proveedor, proveedor
                FROM erp_proveedores
                ORDER BY proveedor")->fetchAll(PDO::FETCH_ASSOC);
            $almacenes = $db->query("SELECT id_almacen, almacen
                FROM erp_almacenes
                WHERE COALESCE(estatus, 'activo')='activo'
                AND COALESCE(permite_recepcion,0)=1
                ORDER BY orden ASC, almacen ASC")->fetchAll(PDO::FETCH_ASSOC);
            $usuarios = $db->query("SELECT id_usuario,
                    " . $usuarioNombreExpr . " nombre,
                    " . $usuarioAreaExpr . " area_departamento
                FROM sys_usuarios
                WHERE COALESCE(estatus,1)=1
                ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
            return $this->respuesta(false, "success", "Catalogos consultados", array(
                "proveedores" => $proveedores,
                "almacenes" => $almacenes,
                "usuarios" => $usuarios
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    public function buscarSkus($idProveedor, $termino) {
        try {
            require_once __DIR__ . "/Proveedores.php";
            $proveedores = new Proveedores();
            return $proveedores->skusComprablesParaComprasErp($idProveedor, $termino, "solicitudes");
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    public function listar($filtros = array()) {
        try {
            $db = $this->getConexion();
            $tieneAlmacenDestino = $this->columnaExiste("erp_compras_solicitudes", "id_almacen_destino");
            $usuarioNombreExpr = $this->expresionNombreUsuario("us");
            $usuarioAreaExpr = $this->expresionAreaUsuario("us");
            $where = array("1=1");
            $params = array();
            if (!empty($filtros["estatus"])) {
                $where[] = "s.estatus=:estatus";
                $params[":estatus"] = $filtros["estatus"];
            }
            if (!empty($filtros["prioridad"])) {
                $where[] = "s.prioridad=:prioridad";
                $params[":prioridad"] = $filtros["prioridad"];
            }
            if (!empty($filtros["id_proveedor"])) {
                $where[] = "s.id_proveedor=:id_proveedor";
                $params[":id_proveedor"] = intval($filtros["id_proveedor"]);
            }
            if ($tieneAlmacenDestino && !empty($filtros["id_almacen_destino"])) {
                $where[] = "s.id_almacen_destino=:id_almacen_destino";
                $params[":id_almacen_destino"] = intval($filtros["id_almacen_destino"]);
            }
            if (!empty($filtros["fecha_desde"])) {
                $where[] = "s.fecha_requerida>=:fecha_desde";
                $params[":fecha_desde"] = $filtros["fecha_desde"];
            }
            if (!empty($filtros["fecha_hasta"])) {
                $where[] = "s.fecha_requerida<=:fecha_hasta";
                $params[":fecha_hasta"] = $filtros["fecha_hasta"];
            }
            if (isset($filtros["con_orden"]) && $filtros["con_orden"] !== "") {
                if (intval($filtros["con_orden"]) === 1) {
                    $where[] = "ou.id_orden_compra IS NOT NULL";
                } elseif (intval($filtros["con_orden"]) === 0) {
                    $where[] = "ou.id_orden_compra IS NULL";
                }
            }
            if (isset($filtros["productos_nuevos"]) && $filtros["productos_nuevos"] !== "" &&
                intval($filtros["productos_nuevos"]) === 1) {
                $where[] = "EXISTS (
                    SELECT 1 FROM erp_compras_solicitudes_detalle dn
                    WHERE dn.id_solicitud=s.id_solicitud AND COALESCE(dn.id_sku_erp,0)=0
                )";
            }
            if (!empty($filtros["solicitado_por"]) && intval($filtros["solicitado_por"]) > 0) {
                $where[] = "s.solicitado_por=:solicitado_por";
                $params[":solicitado_por"] = intval($filtros["solicitado_por"]);
            }
            if (!empty($filtros["q"])) {
                $where[] = "(s.folio LIKE :q OR p.proveedor LIKE :q OR s.observaciones LIKE :q OR o.folio LIKE :q
                    OR " . $usuarioNombreExpr . " LIKE :q)";
                $params[":q"] = "%" . trim($filtros["q"]) . "%";
            }
            $sql = "SELECT s.id_solicitud, s.folio, s.id_proveedor, p.proveedor, s.estatus,
                s.prioridad, s.fecha_solicitud, s.fecha_requerida, s.subtotal_estimado, s.solicitado_por,
                " . ($tieneAlmacenDestino ? "s.id_almacen_destino" : "NULL") . " id_almacen_destino,
                a.almacen,
                " . $usuarioNombreExpr . " solicitante_nombre,
                " . $usuarioAreaExpr . " solicitante_area,
                o.id_orden_compra, o.folio folio_orden, o.estatus estatus_orden,
                COUNT(d.id_detalle) total_partidas, COALESCE(SUM(d.cantidad),0) total_unidades,
                COALESCE(SUM(CASE WHEN COALESCE(d.id_sku_erp,0)=0 THEN 1 ELSE 0 END),0) productos_nuevos
                FROM erp_compras_solicitudes s
                INNER JOIN erp_proveedores p ON p.id_proveedor=s.id_proveedor
                LEFT JOIN sys_usuarios us ON us.id_usuario=s.solicitado_por
                " . ($tieneAlmacenDestino ? "LEFT JOIN erp_almacenes a ON a.id_almacen=s.id_almacen_destino" : "LEFT JOIN erp_almacenes a ON 1=0") . "
                LEFT JOIN erp_compras_solicitudes_detalle d ON d.id_solicitud=s.id_solicitud
                LEFT JOIN (
                    SELECT id_solicitud, MAX(id_orden_compra) id_orden_compra
                    FROM erp_compras_ordenes
                    WHERE COALESCE(estatus,'borrador') != 'cancelada'
                    GROUP BY id_solicitud
                ) ou ON ou.id_solicitud=s.id_solicitud
                LEFT JOIN erp_compras_ordenes o ON o.id_orden_compra=ou.id_orden_compra
                WHERE " . implode(" AND ", $where) . "
                GROUP BY s.id_solicitud
                ORDER BY s.id_solicitud DESC LIMIT 500";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return $this->respuesta(false, "success", "Solicitudes consultadas", $stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    public function consultar($idSolicitud) {
        try {
            $db = $this->getConexion();
            $tieneAlmacenDestino = $this->columnaExiste("erp_compras_solicitudes", "id_almacen_destino");
            $usuarioNombreExpr = $this->expresionNombreUsuario("us");
            $usuarioAreaExpr = $this->expresionAreaUsuario("us");
            $stmt = $db->prepare("SELECT s.*, p.proveedor,
                    " . ($tieneAlmacenDestino ? "s.id_almacen_destino" : "NULL") . " id_almacen_destino,
                    a.almacen,
                    " . $usuarioNombreExpr . " solicitante_nombre,
                    " . $usuarioAreaExpr . " solicitante_area
                FROM erp_compras_solicitudes s
                INNER JOIN erp_proveedores p ON p.id_proveedor=s.id_proveedor
                LEFT JOIN sys_usuarios us ON us.id_usuario=s.solicitado_por
                " . ($tieneAlmacenDestino ? "LEFT JOIN erp_almacenes a ON a.id_almacen=s.id_almacen_destino" : "LEFT JOIN erp_almacenes a ON 1=0") . "
                WHERE s.id_solicitud=:id");
            $stmt->execute(array(":id" => intval($idSolicitud)));
            $solicitud = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$solicitud) {
                return $this->respuesta(true, "warning", "Solicitud no encontrada");
            }
            $stmt = $db->prepare("SELECT d.*, 
                COALESCE(s.sku, d.sku) AS sku, 
                COALESCE(s.nombre, d.nombre_producto) AS nombre, 
                u.abreviatura unidad,
                sp.sku_proveedor
                FROM erp_compras_solicitudes_detalle d
                LEFT JOIN erp_catalogo_skus s ON s.id_sku=d.id_sku_erp
                LEFT JOIN erp_catalogo_unidades u ON u.id_unidad=s.id_unidad_base
                LEFT JOIN erp_catalogo_sku_proveedores sp ON sp.id_sku=d.id_sku_erp
                    AND sp.id_proveedor=:proveedor
                WHERE d.id_solicitud=:id ORDER BY d.id_detalle");
            $stmt->execute(array(":id" => intval($idSolicitud), ":proveedor" => intval($solicitud["id_proveedor"])));
            $detalle = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = $db->prepare("SELECT o.id_orden_compra, o.folio, o.folio_proveedor,
                    o.estatus, o.fecha_orden, o.total, p.proveedor,
                    COUNT(d.id_detalle) total_partidas
                FROM erp_compras_ordenes o
                INNER JOIN erp_proveedores p ON p.id_proveedor=o.id_proveedor
                LEFT JOIN erp_compras_ordenes_detalle d ON d.id_orden_compra=o.id_orden_compra
                WHERE o.id_solicitud=:id
                    AND COALESCE(o.estatus,'borrador') != 'cancelada'
                GROUP BY o.id_orden_compra
                ORDER BY o.id_orden_compra DESC
                LIMIT 1");
            $stmt->execute(array(":id" => intval($idSolicitud)));
            $ordenRelacionada = $stmt->fetch(PDO::FETCH_ASSOC);

            return $this->respuesta(false, "success", "Solicitud consultada", array(
                "solicitud" => $solicitud,
                "detalle" => $detalle,
                "orden_relacionada" => $ordenRelacionada ?: null
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    public function guardar($datos, $idUsuario) {
        $idSolicitud = intval(isset($datos["id_solicitud"]) ? $datos["id_solicitud"] : 0);
        $idProveedor = intval(isset($datos["id_proveedor"]) ? $datos["id_proveedor"] : 0);
        $estatusDestino = isset($datos["estatus"]) && $datos["estatus"] === "pendiente" ? "pendiente" : "borrador";
        $items = isset($datos["items"]) ? $datos["items"] : array();
        if (is_string($items)) {
            $items = json_decode($items, true);
        }
        if ($idProveedor <= 0) {
            return $this->respuesta(true, "warning", "Selecciona un proveedor");
        }
        if (!is_array($items)) {
            return $this->respuesta(true, "warning", "Formato de productos invalido");
        }
        if ($estatusDestino === "pendiente" && empty($items)) {
            return $this->respuesta(true, "warning", "Agrega al menos un producto para enviar a aprobacion");
        }

        $db = $this->getConexion();
        try {
            $db->beginTransaction();
            $actual = null;
            if ($idSolicitud > 0) {
                $stmt = $db->prepare("SELECT * FROM erp_compras_solicitudes WHERE id_solicitud=:id FOR UPDATE");
                $stmt->execute(array(":id" => $idSolicitud));
                $actual = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$actual) {
                    throw new Exception("Solicitud no encontrada");
                }
                if ($actual["estatus"] !== "borrador") {
                    throw new Exception("Solo las solicitudes en borrador permiten edicion");
                }
            }

            $detalle = array();
            $subtotal = 0;
            $ids = array();
            foreach ($items as $item) {
                $idSku = intval(isset($item["id_sku_erp"]) ? $item["id_sku_erp"] : 0);
                $cantidad = round(floatval(isset($item["cantidad"]) ? $item["cantidad"] : 0), 6);
                $costo = round(floatval(isset($item["costo_estimado"]) ? $item["costo_estimado"] : 0), 6);
                $skuText = trim(isset($item["sku"]) ? $item["sku"] : (isset($item["sku_producto"]) ? $item["sku_producto"] : ""));
                $nombreText = trim(isset($item["nombre"]) ? $item["nombre"] : (isset($item["nombre_producto"]) ? $item["nombre_producto"] : ""));
                $idSkuProveedor = 0;
                if ($cantidad <= 0) {
                    throw new Exception("Hay partidas invalidas o vacias");
                }
                if ($estatusDestino === "pendiente" && $costo <= 0) {
                    throw new Exception("Costo estimado invalido en una partida");
                }
                if ($idSku > 0) {
                    $clave = "sku:" . $idSku;
                    $stmt = $db->prepare("SELECT s.id_sku, s.sku, s.nombre, sp.id_sku_proveedor
                        FROM erp_catalogo_skus s
                        INNER JOIN erp_catalogo_sku_proveedores sp ON sp.id_sku=s.id_sku
                            AND sp.id_proveedor=:proveedor AND sp.estatus='activo'
                        WHERE s.id_sku=:sku AND s.estatus='activo'");
                    $stmt->execute(array(":proveedor" => $idProveedor, ":sku" => $idSku));
                    $sku = $stmt->fetch(PDO::FETCH_ASSOC);
                    if (!$sku) {
                        throw new Exception("Un SKU no pertenece al proveedor seleccionado");
                    }
                    $skuText = $sku["sku"];
                    $nombreText = $sku["nombre"];
                    $idSkuProveedor = intval($sku["id_sku_proveedor"]);
                } else {
                    $clave = "nuevo:" . strtolower(preg_replace("/\s+/", " ", $skuText)) . "|" . strtolower(preg_replace("/\s+/", " ", $nombreText));
                    if ($skuText === "" || $nombreText === "") {
                        throw new Exception("Para producto propuesto captura SKU sugerido y nombre");
                    }
                }
                if (isset($ids[$clave])) {
                    throw new Exception("Hay partidas invalidas o producto repetido");
                }
                $ids[$clave] = true;

                $sub = round($cantidad * $costo, 6);
                $subtotal += $sub;
                $detalle[] = array(
                    "id_sku" => $idSku, "id_sku_proveedor" => $idSkuProveedor,
                    "sku" => $skuText, "nombre" => $nombreText, "cantidad" => $cantidad,
                    "costo" => $costo, "subtotal" => $sub,
                    "observaciones" => trim(isset($item["observaciones"]) ? $item["observaciones"] : "")
                );
            }

            $fechaRequerida = !empty($datos["fecha_requerida"]) ? $datos["fecha_requerida"] : null;
            $prioridad = in_array(isset($datos["prioridad"]) ? $datos["prioridad"] : "", array("baja", "normal", "alta", "urgente"), true)
                ? $datos["prioridad"] : "normal";
            $tieneAlmacenDestino = $this->columnaExiste("erp_compras_solicitudes", "id_almacen_destino");
            $idAlmacenDestino = intval(isset($datos["id_almacen_destino"]) ? $datos["id_almacen_destino"] : 0);
            if ($tieneAlmacenDestino && $idAlmacenDestino > 0) {
                $stmt = $db->prepare("SELECT permite_recepcion FROM erp_almacenes
                    WHERE id_almacen=:id AND COALESCE(estatus,'activo')='activo' LIMIT 1");
                $stmt->execute(array(":id" => $idAlmacenDestino));
                $almacenDestino = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$almacenDestino) {
                    throw new Exception("Almacen destino no valido o inactivo");
                }
                if (intval(isset($almacenDestino["permite_recepcion"]) ? $almacenDestino["permite_recepcion"] : 0) !== 1) {
                    throw new Exception("El almacen destino no permite recepcion de compras");
                }
            }
            if ($tieneAlmacenDestino && $estatusDestino === "pendiente" && $idAlmacenDestino <= 0) {
                throw new Exception("Selecciona el almacen destino antes de enviar a aprobacion");
            }
            if ($idSolicitud > 0) {
                $stmt = $db->prepare("UPDATE erp_compras_solicitudes SET id_proveedor=:proveedor,
                    " . ($tieneAlmacenDestino ? "id_almacen_destino=:almacen," : "") . "
                    estatus=:estatus, observaciones=:observaciones, fecha_requerida=:requerida,
                    prioridad=:prioridad, subtotal_estimado=:subtotal, fecha_actualizacion=NOW()
                    WHERE id_solicitud=:id");
                $paramsUpdate = array(":proveedor" => $idProveedor, ":estatus" => $estatusDestino,
                    ":observaciones" => trim(isset($datos["observaciones"]) ? $datos["observaciones"] : ""),
                    ":requerida" => $fechaRequerida, ":prioridad" => $prioridad,
                    ":subtotal" => $subtotal, ":id" => $idSolicitud);
                if ($tieneAlmacenDestino) {
                    $paramsUpdate[":almacen"] = $idAlmacenDestino ?: null;
                }
                $stmt->execute($paramsUpdate);
                $db->prepare("DELETE FROM erp_compras_solicitudes_detalle WHERE id_solicitud=:id")
                    ->execute(array(":id" => $idSolicitud));
            } else {
                $columnasInsert = "id_proveedor, folio, estatus, observaciones, fecha_solicitud, fecha_requerida, ";
                $valoresInsert = ":proveedor, NULL, :estatus, :observaciones, NOW(), :requerida, ";
                if ($tieneAlmacenDestino) {
                    $columnasInsert .= "id_almacen_destino, ";
                    $valoresInsert .= ":almacen, ";
                }
                $stmt = $db->prepare("INSERT INTO erp_compras_solicitudes
                    (" . $columnasInsert . "prioridad, solicitado_por, subtotal_estimado)
                    VALUES (" . $valoresInsert . ":prioridad, :usuario, :subtotal)");
                $paramsInsert = array(":proveedor" => $idProveedor, ":estatus" => $estatusDestino,
                    ":observaciones" => trim(isset($datos["observaciones"]) ? $datos["observaciones"] : ""),
                    ":requerida" => $fechaRequerida, ":prioridad" => $prioridad,
                    ":usuario" => intval($idUsuario) ?: null, ":subtotal" => $subtotal);
                if ($tieneAlmacenDestino) {
                    $paramsInsert[":almacen"] = $idAlmacenDestino ?: null;
                }
                $stmt->execute($paramsInsert);
                $idSolicitud = intval($db->lastInsertId());
                $folio = "SC-" . date("Y") . "-" . str_pad($idSolicitud, 6, "0", STR_PAD_LEFT);
                $db->prepare("UPDATE erp_compras_solicitudes SET folio=:folio WHERE id_solicitud=:id")
                    ->execute(array(":folio" => $folio, ":id" => $idSolicitud));
            }

            $stmt = $db->prepare("INSERT INTO erp_compras_solicitudes_detalle
                (id_solicitud, id_sku_erp, id_sku_proveedor, sku, nombre_producto, cantidad,
                costo_estimado, subtotal, observaciones)
                VALUES (:solicitud, :sku_id, :relacion, :sku, :nombre, :cantidad, :costo, :subtotal, :observaciones)");
            foreach ($detalle as $item) {
                $stmt->execute(array(":solicitud" => $idSolicitud, ":sku_id" => $item["id_sku"],
                    ":relacion" => $item["id_sku_proveedor"], ":sku" => $item["sku"],
                    ":nombre" => $item["nombre"], ":cantidad" => $item["cantidad"],
                    ":costo" => $item["costo"], ":subtotal" => $item["subtotal"],
                    ":observaciones" => $item["observaciones"]));
            }

            $this->sincronizarPendientesCatalogo(
                $db,
                $idSolicitud,
                $idProveedor,
                $detalle,
                $idUsuario
            );

            $db->commit();
            return $this->respuesta(false, "success", $estatusDestino === "pendiente"
                ? "Solicitud enviada a aprobacion" : "Borrador guardado", array(
                    "id_solicitud" => $idSolicitud, "estatus" => $estatusDestino, "subtotal" => $subtotal
                ));
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    private function sincronizarPendientesCatalogo(PDO $db, $idSolicitud, $idProveedor, $detalle, $idUsuario = 0) {
        $idSolicitud = intval($idSolicitud);
        $idProveedor = intval($idProveedor);
        if ($idSolicitud <= 0 || $idProveedor <= 0) {
            return;
        }

        foreach ($detalle as $item) {
            $idSkuErp = intval($item["id_sku"]);
            if ($idSkuErp > 0) {
                continue;
            }

            $sku = trim((string) $item["sku"]);
            $nombre = trim((string) $item["nombre"]);
            if ($sku === "" && $nombre === "") {
                continue;
            }

            $detalleJson = array(
                "motivo" => "producto_no_registrado_en_catalogo_erp",
                "origen" => "solicitud",
                "id_solicitud" => $idSolicitud,
                "id_proveedor" => $idProveedor,
                "sku_sugerido" => $sku,
                "nombre_sugerido" => $nombre,
                "cantidad_solicitada" => $item["cantidad"],
                "costo_estimado" => $item["costo"],
                "observaciones" => $item["observaciones"]
            );
            $propuestaJson = array(
                "accion_sugerida" => "revisar_crear_o_vincular_sku_erp",
                "no_crear_maestro_automaticamente" => true,
                "modulo_responsable" => "catalogo"
            );
            $evidenciaJson = array(
                "modulo" => "compras",
                "tipo_origen" => "solicitud",
                "id_origen" => $idSolicitud,
                "id_proveedor" => $idProveedor,
                "sku" => $sku,
                "nombre_producto" => $nombre,
                "cantidad" => $item["cantidad"],
                "costo_estimado" => $item["costo"]
            );
            $huellaBase = "compras|solicitud_producto_propuesto|solicitud:" . $idSolicitud .
                "|proveedor:" . $idProveedor .
                "|sku:" . strtolower(preg_replace("/\s+/", " ", $sku)) .
                "|nombre:" . strtolower(preg_replace("/\s+/", " ", $nombre));

            $stmtInsert = $db->prepare("INSERT INTO erp_catalogo_incidencias_calidad
                (huella, tipo_incidencia, entidad_tipo, id_producto_erp, id_sku, id_referencia,
                referencia_tipo, origen, severidad, titulo, descripcion, detalle_json,
                evidencia_json, propuesta_json, estatus, creado_por)
                VALUES (:huella, 'compra_producto_propuesto', 'sku', NULL, NULL, :referencia,
                'erp_compras_solicitudes', 'compra', 'advertencia',
                'Producto propuesto desde solicitud de compra',
                'Compras capturo un producto que no existe como SKU ERP. Catalogo debe revisar si se crea, se vincula o se descarta.',
                :detalle, :evidencia, :propuesta, 'pendiente', :usuario)
                ON DUPLICATE KEY UPDATE
                    detalle_json=VALUES(detalle_json),
                    evidencia_json=VALUES(evidencia_json),
                    propuesta_json=VALUES(propuesta_json),
                    estatus=IF(estatus IN ('resuelta','descartada'), estatus, 'pendiente'),
                    fecha_actualizacion=CURRENT_TIMESTAMP");
            $stmtInsert->execute(array(
                ":huella" => hash("sha256", $huellaBase),
                ":referencia" => $idSolicitud,
                ":detalle" => json_encode($detalleJson, JSON_UNESCAPED_UNICODE),
                ":evidencia" => json_encode($evidenciaJson, JSON_UNESCAPED_UNICODE),
                ":propuesta" => json_encode($propuestaJson, JSON_UNESCAPED_UNICODE),
                ":usuario" => intval($idUsuario) ?: null
            ));
        }
    }

    public function cambiarEstatus($idSolicitud, $estatus, $idUsuario, $motivo = "") {
        $estatus = strtolower(trim((string)$estatus));
        $motivo = trim((string)$motivo);

        $transiciones = array(
            "borrador" => array("cancelada"),
            "pendiente" => array("aprobada", "rechazada", "cancelada"),
            "aprobada" => array("cancelada", "orden_generada"),
            "rechazada" => array(),
            "orden_generada" => array(),
            "cancelada" => array()
        );

        if (!in_array($estatus, array("aprobada", "rechazada", "cancelada", "orden_generada"), true)) {
            return $this->respuesta(true, "warning", "Estatus no permitido");
        }
        try {
            $db = $this->getConexion();
            $db->beginTransaction();

            $idSolicitud = intval($idSolicitud);
            if ($idSolicitud <= 0) {
                throw new Exception("Solicitud invalida");
            }
            $usuario = intval($idUsuario);
            if ($usuario <= 0) {
                throw new Exception("Usuario invalido");
            }

            $stmt = $db->prepare("SELECT id_solicitud, estatus, observaciones
                FROM erp_compras_solicitudes WHERE id_solicitud=:id FOR UPDATE");
            $stmt->execute(array(":id" => $idSolicitud));
            $actual = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$actual) {
                throw new Exception("Solicitud no encontrada");
            }

            $estatusActual = $actual["estatus"];
            if (!isset($transiciones[$estatusActual])) {
                throw new Exception("Estado actual de solicitud no valido");
            }
            if (!in_array($estatus, $transiciones[$estatusActual], true)) {
                return $this->respuesta(true, "warning", "No es posible cambiar de '{$estatusActual}' a '{$estatus}'");
            }

            if (($estatus === "rechazada" || $estatus === "cancelada") && $motivo === "") {
                return $this->respuesta(true, "warning", "El motivo es obligatorio para esta transicion");
            }

            $set = array(
                "estatus=:estatus",
                "fecha_actualizacion=NOW()"
            );
            $params = array(
                ":estatus" => $estatus,
                ":id" => $idSolicitud
            );
            if ($estatus === "aprobada") {
                $set[] = "fecha_aprobacion=NOW()";
                $set[] = "aprobado_por=:usuario";
                $params[":usuario"] = $usuario;
            }
            if ($estatus === "cancelada") {
                $set[] = "fecha_cancelacion=NOW()";
                if ($motivo !== "") {
                    $set[] = "observaciones=CONCAT_WS('\n', observaciones, :motivo)";
                    $params[":motivo"] = " [cancelada] " . $motivo;
                }
            }
            if ($estatus === "rechazada") {
                if ($motivo !== "") {
                    $set[] = "observaciones=CONCAT_WS('\n', observaciones, :motivo)";
                    $params[":motivo"] = " [rechazada] " . $motivo;
                }
            }

            $sql = "UPDATE erp_compras_solicitudes SET " . implode(", ", $set) . " WHERE id_solicitud=:id";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            if ($stmt->rowCount() !== 1) {
                throw new Exception("No se pudo actualizar la solicitud");
            }

            if ($estatus === "orden_generada") {
                $stmt = $db->prepare("UPDATE erp_compras_solicitudes
                    SET fecha_actualizacion=NOW()
                    WHERE id_solicitud=:id");
                $stmt->execute(array(":id" => $idSolicitud));
            }

            $db->commit();
            return $this->respuesta(false, "success", "Estatus actualizado", array(
                "id_solicitud" => $idSolicitud,
                "estatus_anterior" => $estatusActual,
                "estatus" => $estatus,
                "motivo" => $motivo
            ));
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    private function respuesta($error, $tipo, $mensaje, $depurar = array()) {
        return array("error" => $error, "tipo" => $tipo, "mensaje" => $mensaje, "depurar" => $depurar);
    }
}
