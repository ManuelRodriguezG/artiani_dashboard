<?php

class Almacenes extends CRUD {
    
    private $tabla_erp_almacenes = "erp_almacenes";
    private $tabla_erp_compras_ordenes = "erp_compras_ordenes";
    private $tabla_erp_compras_ordenes_detalle = "erp_compras_ordenes_detalle";
    private $tabla_erp_almacen_recepciones = "erp_almacen_recepciones";
    private $tabla_erp_almacen_recepciones_detalle = "erp_almacen_recepciones_detalle";
    private $tabla_erp_almacen_recepciones_lotes = "erp_almacen_recepciones_lotes";
    private $tabla_erp_almacen_recepciones_incidencias = "erp_almacen_recepciones_incidencias";
    private $tabla_erp_inventario_movimientos = "erp_inventario_movimientos";
    private $tabla_erp_inventario_existencias = "erp_inventario_existencias";
    private $tabla_erp_inventario_unidades = "erp_inventario_unidades";
    private $tabla_erp_almacen_ubicaciones = "erp_almacen_ubicaciones";
    private $tabla_erp_almacen_preparaciones = "erp_almacen_preparaciones";
    private $tabla_erp_almacen_preparacion_consumos = "erp_almacen_preparacion_consumos";
    private $tabla_erp_almacen_preparacion_resultados = "erp_almacen_preparacion_resultados";
    private $tabla_erp_catalogo_sku_transformaciones = "erp_catalogo_sku_transformaciones";

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-06-27
     * Proposito: detecta columnas opcionales antes de leer reglas de recepcion variable.
     * Impacto: Almacen/Recepciones; permite preparar el flujo sin romper instalaciones sin DDL aplicado.
     */
    private function columnaExisteAlmacen($db, $tabla, $columna) {
        $stmt = $db->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :tabla AND COLUMN_NAME = :columna LIMIT 1");
        $stmt->execute(array(":tabla" => $tabla, ":columna" => $columna));
        return (bool) $stmt->fetchColumn();
    }

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-07-11
     * Proposito: permite que Resurtido opere en modo lectura aunque el DDL autorizado aun no exista.
     * Impacto: Almacen/Resurtido; evita errores fatales y reporta schema pendiente sin escribir en BD.
     */
    private function tablaExisteAlmacen($db, $tabla) {
        $stmt = $db->prepare("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :tabla LIMIT 1");
        $stmt->execute(array(":tabla" => $tabla));
        return (bool) $stmt->fetchColumn();
    }

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-06-27
     * Proposito: confirma si Catalogo ya expone las reglas de cantidad real variable para Recepcion.
     * Impacto: Almacen/Recepciones; las reglas se leen solo cuando el esquema autorizado existe.
     */
    private function esquemaRecepcionVariableDisponibleAlmacen($db) {
        foreach (array(
            "requiere_cantidad_variable_recepcion",
            "requiere_unidades_fisicas_recepcion",
            "tolerancia_recepcion_porcentaje",
            "nota_recepcion_variable"
        ) as $columna) {
            if (!$this->columnaExisteAlmacen($db, "erp_catalogo_sku_reglas_inventario", $columna)) {
                return false;
            }
        }
        return true;
    }

    public function obtener_almacenes($filtros = array()) {
        $incluir_inactivos = isset($filtros["incluir_inactivos"]) && intval($filtros["incluir_inactivos"]) === 1;
        $where = array();
        $campos = array(
            "id_almacen",
            "codigo_almacen",
            "almacen",
            "nombre_comercial",
            "pais",
            "ciudad",
            "colonia",
            "codigo_postal",
            "calle",
            "numero_exterior",
            "numero_interior",
            "estatus",
            "tipo_almacen",
            "permite_recepcion",
            "permite_venta",
            "permite_preparacion",
            "permite_ajustes",
            "es_tecnico",
            "'almacen' as tipo_establecimiento"
        );
        $this->setColumnas($campos);
        $this->setTabla($this->tabla_erp_almacenes);
        if (!$incluir_inactivos) {
            $where[] = "COALESCE(estatus,'activo')='activo'";
        }
        foreach (array("permite_recepcion", "permite_venta", "permite_preparacion", "permite_ajustes") as $flag) {
            if (isset($filtros[$flag]) && $filtros[$flag] !== "") {
                $where[] = $flag . "=" . intval($filtros[$flag]);
            }
        }
        if (!empty($where)) {
            $this->setWhere(implode(" AND ", $where));
        }
        $this->setOrderBy("orden ASC, almacen ASC");
        return $this->listar();
    }

    public function consultar_almacenes_configuracion($filtros = array()) {
        try {
            $db = $this->getConexion();
            $where = array("1=1");
            $params = array();
            $estatus = trim((string) $this->valor($filtros, "estatus", ""));
            $tipo = trim((string) $this->valor($filtros, "tipo_almacen", ""));
            $q = trim((string) $this->valor($filtros, "q", ""));
            if ($estatus !== "") {
                $where[] = "COALESCE(estatus,'activo')=:estatus";
                $params[":estatus"] = $estatus;
            }
            if ($tipo !== "") {
                $where[] = "tipo_almacen=:tipo";
                $params[":tipo"] = $tipo;
            }
            if ($q !== "") {
                $where[] = "(almacen LIKE :q OR codigo_almacen LIKE :q OR nombre_comercial LIKE :q OR calle LIKE :q)";
                $params[":q"] = "%" . $q . "%";
            }
            $sql = "SELECT id_almacen, codigo_almacen, almacen, nombre_comercial, pais, estado, municipio, ciudad,
                    colonia, codigo_postal, calle, numero_exterior, numero_interior, contacto_recepcion,
                    telefono_recepcion, email_recepcion, referencias_direccion, estatus, tipo_almacen,
                    permite_recepcion, permite_venta, permite_preparacion, permite_ajustes, es_tecnico,
                    orden, observaciones, fecha_actualizacion
                FROM {$this->tabla_erp_almacenes}
                WHERE " . implode(" AND ", $where) . "
                ORDER BY orden ASC, almacen ASC";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return $this->crudResponse(false, "success", "Almacenes consultados", $stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (Exception $e) {
            return $this->crudResponse(true, "danger", $e->getMessage());
        }
    }

    public function guardar_almacen_configuracion($datos, $id_usuario = 0) {
        try {
            $db = $this->getConexion();
            $id = intval($this->valor($datos, "id_almacen", 0));
            $codigo = strtoupper(trim((string) $this->valor($datos, "codigo_almacen", "")));
            $nombre = trim((string) $this->valor($datos, "almacen", ""));
            $tipo = trim((string) $this->valor($datos, "tipo_almacen", ""));
            $estatus = trim((string) $this->valor($datos, "estatus", "activo"));
            $tipos = array("punto_venta", "sucursal", "bodega", "principal", "transito", "devoluciones", "merma", "cuarentena");
            $estatus_validos = array("activo", "inactivo");
            if ($codigo === "" || $nombre === "" || !in_array($tipo, $tipos, true) || !in_array($estatus, $estatus_validos, true)) {
                return $this->crudResponse(true, "warning", "Codigo, nombre, tipo y estatus son obligatorios");
            }
            $stmt = $db->prepare("SELECT id_almacen FROM {$this->tabla_erp_almacenes} WHERE codigo_almacen=:codigo AND id_almacen<>:id LIMIT 1");
            $stmt->execute(array(":codigo" => $codigo, ":id" => $id));
            if ($stmt->fetchColumn()) {
                return $this->crudResponse(true, "warning", "Ya existe un almacen con ese codigo");
            }
            $params = array(
                ":codigo" => $codigo,
                ":almacen" => $nombre,
                ":nombre_comercial" => $this->valor($datos, "nombre_comercial", null),
                ":pais" => $this->valor($datos, "pais", null),
                ":estado" => $this->valor($datos, "estado", null),
                ":municipio" => $this->valor($datos, "municipio", null),
                ":ciudad" => $this->valor($datos, "ciudad", null),
                ":colonia" => $this->valor($datos, "colonia", null),
                ":codigo_postal" => $this->valor($datos, "codigo_postal", null),
                ":calle" => $this->valor($datos, "calle", null),
                ":numero_exterior" => $this->valor($datos, "numero_exterior", null),
                ":numero_interior" => $this->valor($datos, "numero_interior", null),
                ":contacto" => $this->valor($datos, "contacto_recepcion", null),
                ":telefono" => $this->valor($datos, "telefono_recepcion", null),
                ":email" => $this->valor($datos, "email_recepcion", null),
                ":referencias" => $this->valor($datos, "referencias_direccion", null),
                ":estatus" => $estatus,
                ":tipo" => $tipo,
                ":recepcion" => intval($this->valor($datos, "permite_recepcion", 0)),
                ":venta" => intval($this->valor($datos, "permite_venta", 0)),
                ":preparacion" => intval($this->valor($datos, "permite_preparacion", 0)),
                ":ajustes" => intval($this->valor($datos, "permite_ajustes", 0)),
                ":tecnico" => intval($this->valor($datos, "es_tecnico", 0)),
                ":orden" => intval($this->valor($datos, "orden", 100)),
                ":observaciones" => $this->valor($datos, "observaciones", null)
            );
            if ($id > 0) {
                $params[":id"] = $id;
                $sql = "UPDATE {$this->tabla_erp_almacenes}
                    SET codigo_almacen=:codigo, almacen=:almacen, nombre_comercial=:nombre_comercial, pais=:pais,
                        estado=:estado, municipio=:municipio, ciudad=:ciudad, colonia=:colonia,
                        codigo_postal=:codigo_postal, calle=:calle, numero_exterior=:numero_exterior,
                        numero_interior=:numero_interior, contacto_recepcion=:contacto,
                        telefono_recepcion=:telefono, email_recepcion=:email, referencias_direccion=:referencias,
                        estatus=:estatus, tipo_almacen=:tipo, permite_recepcion=:recepcion, permite_venta=:venta,
                        permite_preparacion=:preparacion, permite_ajustes=:ajustes, es_tecnico=:tecnico,
                        orden=:orden, observaciones=:observaciones, fecha_actualizacion=NOW()
                    WHERE id_almacen=:id";
            } else {
                $sql = "INSERT INTO {$this->tabla_erp_almacenes}
                    (codigo_almacen, almacen, nombre_comercial, pais, estado, municipio, ciudad, colonia,
                     codigo_postal, calle, numero_exterior, numero_interior, contacto_recepcion,
                     telefono_recepcion, email_recepcion, referencias_direccion, estatus, tipo_almacen,
                     permite_recepcion, permite_venta, permite_preparacion, permite_ajustes, es_tecnico,
                     orden, observaciones, fecha_actualizacion)
                    VALUES (:codigo, :almacen, :nombre_comercial, :pais, :estado, :municipio, :ciudad, :colonia,
                     :codigo_postal, :calle, :numero_exterior, :numero_interior, :contacto,
                     :telefono, :email, :referencias, :estatus, :tipo, :recepcion, :venta,
                     :preparacion, :ajustes, :tecnico, :orden, :observaciones, NOW())";
            }
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $id_guardado = $id > 0 ? $id : intval($db->lastInsertId());
            return $this->crudResponse(false, "success", "Almacen guardado", array("id_almacen" => $id_guardado));
        } catch (Exception $e) {
            return $this->crudResponse(true, "danger", $e->getMessage());
        }
    }

    public function consultar_ubicaciones_configuracion($filtros = array()) {
        try {
            $db = $this->getConexion();
            $where = array("1=1");
            $params = array();
            $id_almacen = intval($this->valor($filtros, "id_almacen", 0));
            $estatus = trim((string) $this->valor($filtros, "estatus", ""));
            if ($id_almacen > 0) {
                $where[] = "u.id_almacen_clave=:almacen";
                $params[":almacen"] = $id_almacen;
            }
            if ($estatus !== "") {
                $where[] = "u.estatus=:estatus";
                $params[":estatus"] = $estatus;
            }
            $sql = "SELECT u.id_ubicacion, u.id_almacen_clave AS id_almacen, a.almacen, u.codigo_ubicacion,
                    u.nombre, u.zona, u.pasillo, u.rack, u.nivel, u.contenedor, u.descripcion,
                    u.estatus, u.fecha_actualizacion
                FROM {$this->tabla_erp_almacen_ubicaciones} u
                INNER JOIN {$this->tabla_erp_almacenes} a ON a.id_almacen=u.id_almacen_clave
                WHERE " . implode(" AND ", $where) . "
                ORDER BY a.orden ASC, u.codigo_ubicacion ASC";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return $this->crudResponse(false, "success", "Ubicaciones consultadas", $stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (Exception $e) {
            return $this->crudResponse(true, "danger", $e->getMessage());
        }
    }

    public function guardar_ubicacion_configuracion($datos, $id_usuario = 0) {
        try {
            $db = $this->getConexion();
            $id = intval($this->valor($datos, "id_ubicacion", 0));
            $id_almacen = intval($this->valor($datos, "id_almacen", 0));
            $codigo = strtoupper(trim((string) $this->valor($datos, "codigo_ubicacion", "")));
            $estatus = trim((string) $this->valor($datos, "estatus", "activa"));
            if ($id_almacen <= 0 || $codigo === "" || !in_array($estatus, array("activa", "inactiva"), true)) {
                return $this->crudResponse(true, "warning", "Almacen, codigo y estatus son obligatorios");
            }
            $stmt = $db->prepare("SELECT id_almacen FROM {$this->tabla_erp_almacenes}
                WHERE id_almacen=:almacen AND COALESCE(estatus,'activo')='activo' LIMIT 1");
            $stmt->execute(array(":almacen" => $id_almacen));
            if (!$stmt->fetchColumn()) {
                return $this->crudResponse(true, "warning", "Almacen no valido o inactivo");
            }
            $stmt = $db->prepare("SELECT id_ubicacion FROM {$this->tabla_erp_almacen_ubicaciones}
                WHERE id_almacen_clave=:almacen AND codigo_ubicacion=:codigo AND id_ubicacion<>:id LIMIT 1");
            $stmt->execute(array(":almacen" => $id_almacen, ":codigo" => $codigo, ":id" => $id));
            if ($stmt->fetchColumn()) {
                return $this->crudResponse(true, "warning", "Ya existe una ubicacion con ese codigo en el almacen");
            }
            $params = array(
                ":almacen" => $id_almacen,
                ":codigo" => $codigo,
                ":nombre" => $this->valor($datos, "nombre", $codigo),
                ":zona" => $this->valor($datos, "zona", null),
                ":pasillo" => $this->valor($datos, "pasillo", null),
                ":rack" => $this->valor($datos, "rack", null),
                ":nivel" => $this->valor($datos, "nivel", null),
                ":contenedor" => $this->valor($datos, "contenedor", null),
                ":descripcion" => $this->valor($datos, "descripcion", null),
                ":estatus" => $estatus
            );
            if ($id > 0) {
                $params[":id"] = $id;
                $sql = "UPDATE {$this->tabla_erp_almacen_ubicaciones}
                    SET id_almacen=:almacen, id_almacen_clave=:almacen, codigo_ubicacion=:codigo,
                        nombre=:nombre, zona=:zona, pasillo=:pasillo, rack=:rack, nivel=:nivel,
                        contenedor=:contenedor, descripcion=:descripcion, estatus=:estatus,
                        fecha_actualizacion=NOW()
                    WHERE id_ubicacion=:id";
            } else {
                $sql = "INSERT INTO {$this->tabla_erp_almacen_ubicaciones}
                    (id_almacen, id_almacen_clave, codigo_ubicacion, nombre, zona, pasillo,
                     rack, nivel, contenedor, descripcion, estatus, fecha_actualizacion)
                    VALUES (:almacen, :almacen, :codigo, :nombre, :zona, :pasillo,
                     :rack, :nivel, :contenedor, :descripcion, :estatus, NOW())";
            }
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $id_guardado = $id > 0 ? $id : intval($db->lastInsertId());
            return $this->crudResponse(false, "success", "Ubicacion guardada", array("id_ubicacion" => $id_guardado));
        } catch (Exception $e) {
            return $this->crudResponse(true, "danger", $e->getMessage());
        }
    }

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-07-11
     * Proposito: previsualiza necesidades de resurtido por almacen/SKU usando reglas globales de Catalogo.
     * Impacto: Almacen/Resurtido; no crea solicitudes, alertas ni movimientos de inventario.
     * Contrato: consulta read-only; hasta que exista politica local por tienda/SKU, usa stock_minimo, stock_maximo y punto_reorden globales del SKU.
     */
    public function preflight_stock_bajo_resurtido($filtros = array()) {
        try {
            $db = $this->getConexion();
            $id_almacen = intval($this->valor($filtros, "id_almacen", 0));
            $id_sku = intval($this->valor($filtros, "id_sku", 0));
            $q = trim((string) $this->valor($filtros, "q", ""));
            $solo_bajos = intval($this->valor($filtros, "solo_bajos", 1)) === 1;

            if ($id_almacen <= 0) {
                return $this->crudResponse(true, "warning", "Selecciona almacen para calcular resurtido");
            }

            $stmt = $db->prepare("SELECT id_almacen, codigo_almacen, almacen, tipo_almacen, estatus
                FROM {$this->tabla_erp_almacenes}
                WHERE id_almacen=:almacen AND COALESCE(estatus,'activo')='activo'
                LIMIT 1");
            $stmt->execute(array(":almacen" => $id_almacen));
            $almacen = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$almacen) {
                return $this->crudResponse(true, "warning", "Almacen no encontrado o inactivo");
            }

            $where = array("s.estatus='activo'", "p.estatus='activo'", "COALESCE(r.controla_inventario, 1)=1");
            $params = array(":almacen" => $id_almacen);
            if ($id_sku > 0) {
                $where[] = "s.id_sku=:sku";
                $params[":sku"] = $id_sku;
            }
            if ($q !== "") {
                $where[] = "(s.sku LIKE :q OR s.nombre LIKE :q OR p.nombre LIKE :q OR p.codigo_producto LIKE :q)";
                $params[":q"] = "%" . $q . "%";
            }

            $sql = "SELECT s.id_sku, s.sku, s.nombre AS nombre_sku, p.id_producto_erp, p.nombre AS producto,
                    COALESCE(NULLIF(r.unidad_venta_label,''), ub.abreviatura, ub.codigo, '') AS unidad_base,
                    COALESCE(r.stock_minimo, 0.000000) AS stock_minimo,
                    r.stock_maximo,
                    COALESCE(r.punto_reorden, 0.000000) AS punto_reorden,
                    COALESCE(SUM(CASE WHEN e.id_almacen_clave=:almacen THEN e.cantidad ELSE 0 END), 0) AS cantidad,
                    COALESCE(SUM(CASE WHEN e.id_almacen_clave=:almacen THEN e.cantidad_apartada ELSE 0 END), 0) AS cantidad_apartada,
                    COALESCE(SUM(CASE WHEN e.id_almacen_clave=:almacen THEN e.cantidad_disponible ELSE 0 END), 0) AS cantidad_disponible
                FROM erp_catalogo_skus s
                INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
                LEFT JOIN erp_catalogo_sku_reglas_inventario r ON r.id_sku=s.id_sku
                LEFT JOIN erp_catalogo_unidades ub ON ub.id_unidad=s.id_unidad_base
                LEFT JOIN {$this->tabla_erp_inventario_existencias} e ON e.id_sku_erp=s.id_sku
                WHERE " . implode(" AND ", $where) . "
                GROUP BY s.id_sku, s.sku, s.nombre, p.id_producto_erp, p.nombre, r.unidad_venta_label,
                    ub.abreviatura, ub.codigo, r.stock_minimo, r.stock_maximo, r.punto_reorden
                ORDER BY p.nombre ASC, s.sku ASC
                LIMIT 500";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $items = array();
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
                $disponible = round(floatval($fila["cantidad_disponible"]), 6);
                $punto = round(floatval($fila["punto_reorden"]), 6);
                $minimo = round(floatval($fila["stock_minimo"]), 6);
                $maximo = $fila["stock_maximo"] === null ? null : round(floatval($fila["stock_maximo"]), 6);
                $umbral = $punto > 0 ? $punto : $minimo;
                $requiere = $umbral > 0 && $disponible <= $umbral + 0.000001;
                $sugerida = 0.000000;
                if ($requiere) {
                    if ($maximo !== null && $maximo > $disponible) {
                        $sugerida = round($maximo - $disponible, 6);
                    } elseif ($punto > $disponible) {
                        $sugerida = round($punto - $disponible, 6);
                    } elseif ($minimo > $disponible) {
                        $sugerida = round($minimo - $disponible, 6);
                    }
                }
                if ($solo_bajos && !$requiere) {
                    continue;
                }
                $fila["umbral_usado"] = $umbral;
                $fila["requiere_resurtido"] = $requiere ? 1 : 0;
                $fila["cantidad_sugerida"] = $sugerida;
                $fila["politica_fuente"] = "catalogo_global";
                $items[] = $fila;
            }

            return $this->crudResponse(false, "success", "Preflight de stock bajo consultado", array(
                "almacen" => $almacen,
                "total" => count($items),
                "items" => $items,
                "solo_bajos" => $solo_bajos ? 1 : 0,
                "politica_local_disponible" => 0
            ));
        } catch (Exception $e) {
            return $this->crudResponse(true, "danger", $e->getMessage());
        }
    }

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-07-11
     * Proposito: lista solicitudes de resurtido en modo read-only y detecta si el esquema esta pendiente.
     * Impacto: Almacen/Resurtido; habilita pantalla inicial sin permitir escrituras ni movimientos de inventario.
     * Contrato: no crea ni modifica datos; si faltan tablas devuelve schema_pendiente=1 e items vacios.
     */
    public function consultar_resurtidos_readonly($filtros = array()) {
        try {
            $db = $this->getConexion();
            $tablas = array(
                "erp_almacen_resurtidos",
                "erp_almacen_resurtido_detalle",
                "erp_almacen_resurtido_diferencias"
            );
            $faltantes = array();
            foreach ($tablas as $tabla) {
                if (!$this->tablaExisteAlmacen($db, $tabla)) {
                    $faltantes[] = $tabla;
                }
            }
            if (!empty($faltantes)) {
                return $this->crudResponse(false, "info", "Esquema de resurtido pendiente", array(
                    "schema_pendiente" => 1,
                    "tablas_faltantes" => $faltantes,
                    "items" => array(),
                    "total" => 0
                ));
            }

            $where = array("1=1");
            $params = array();
            $estatus = trim((string) $this->valor($filtros, "estatus", ""));
            $id_origen = intval($this->valor($filtros, "id_almacen_origen", 0));
            $id_destino = intval($this->valor($filtros, "id_almacen_destino", 0));
            $q = trim((string) $this->valor($filtros, "q", ""));
            $estatus_validos = array("borrador", "solicitado", "autorizado", "rechazado", "preparando", "preparado", "enviado", "recibido_parcial", "recibido", "cerrado", "cancelado");

            if ($estatus !== "" && in_array($estatus, $estatus_validos, true)) {
                $where[] = "r.estatus=:estatus";
                $params[":estatus"] = $estatus;
            }
            if ($id_origen > 0) {
                $where[] = "r.id_almacen_origen=:origen";
                $params[":origen"] = $id_origen;
            }
            if ($id_destino > 0) {
                $where[] = "r.id_almacen_solicitante=:destino";
                $params[":destino"] = $id_destino;
            }
            if ($q !== "") {
                $where[] = "(r.folio LIKE :q OR ao.almacen LIKE :q OR ad.almacen LIKE :q OR r.observaciones LIKE :q)";
                $params[":q"] = "%" . $q . "%";
            }

            $sql = "SELECT r.id_resurtido_almacen, r.id_resurtido_almacen AS id_resurtido,
                    r.folio, r.estatus, r.prioridad, r.fecha_solicitud,
                    r.fecha_autorizacion, r.fecha_envio, r.fecha_recepcion,
                    r.id_almacen_origen, r.id_almacen_solicitante AS id_almacen_destino, ao.almacen AS almacen_origen,
                    ad.almacen AS almacen_destino, r.observaciones,
                    COALESCE(det.total_partidas, 0) AS total_partidas,
                    COALESCE(det.cantidad_solicitada, 0) AS cantidad_solicitada,
                    COALESCE(det.cantidad_autorizada, 0) AS cantidad_autorizada,
                    COALESCE(det.cantidad_preparada, 0) AS cantidad_preparada,
                    COALESCE(det.cantidad_enviada, 0) AS cantidad_enviada,
                    COALESCE(det.cantidad_recibida, 0) AS cantidad_recibida,
                    COALESCE(dif.total_diferencias, 0) AS total_diferencias
                FROM erp_almacen_resurtidos r
                LEFT JOIN {$this->tabla_erp_almacenes} ao ON ao.id_almacen=r.id_almacen_origen
                LEFT JOIN {$this->tabla_erp_almacenes} ad ON ad.id_almacen=r.id_almacen_solicitante
                LEFT JOIN (
                    SELECT id_resurtido_almacen, COUNT(*) AS total_partidas,
                        SUM(cantidad_solicitada) AS cantidad_solicitada,
                        SUM(cantidad_autorizada) AS cantidad_autorizada,
                        SUM(cantidad_preparada) AS cantidad_preparada,
                        SUM(cantidad_enviada) AS cantidad_enviada,
                        SUM(cantidad_recibida) AS cantidad_recibida
                    FROM erp_almacen_resurtido_detalle
                    GROUP BY id_resurtido_almacen
                ) det ON det.id_resurtido_almacen=r.id_resurtido_almacen
                LEFT JOIN (
                    SELECT id_resurtido_almacen, COUNT(*) AS total_diferencias
                    FROM erp_almacen_resurtido_diferencias
                    GROUP BY id_resurtido_almacen
                ) dif ON dif.id_resurtido_almacen=r.id_resurtido_almacen
                WHERE " . implode(" AND ", $where) . "
                ORDER BY r.id_resurtido_almacen DESC
                LIMIT 200";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $this->crudResponse(false, "success", "Resurtidos consultados", array(
                "schema_pendiente" => 0,
                "items" => $items,
                "total" => count($items)
            ));
        } catch (Exception $e) {
            return $this->crudResponse(true, "danger", $e->getMessage());
        }
    }

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-07-12
     * Proposito: consulta un folio de resurtido completo en modo read-only para auditoria/UAT.
     * Impacto: Almacen/Resurtido; no modifica solicitudes ni inventario y conserva trazabilidad por tablas documentales.
     * Contrato: acepta id_resurtido_almacen/id_resurtido o folio; si falta DDL devuelve schema_pendiente=1.
     */
    public function consultar_resurtido_readonly($filtros = array()) {
        try {
            $db = $this->getConexion();
            $tablas = array(
                "erp_almacen_resurtidos",
                "erp_almacen_resurtido_detalle",
                "erp_almacen_resurtido_preparacion",
                "erp_almacen_resurtido_envios",
                "erp_almacen_resurtido_recepciones",
                "erp_almacen_resurtido_diferencias"
            );
            $faltantes = array();
            foreach ($tablas as $tabla) {
                if (!$this->tablaExisteAlmacen($db, $tabla)) {
                    $faltantes[] = $tabla;
                }
            }
            if (!empty($faltantes)) {
                return $this->crudResponse(false, "info", "Esquema de resurtido pendiente", array(
                    "schema_pendiente" => 1,
                    "tablas_faltantes" => $faltantes,
                    "encabezado" => null,
                    "detalle" => array(),
                    "preparacion" => array(),
                    "envios" => array(),
                    "recepciones" => array(),
                    "diferencias" => array()
                ));
            }

            $id = intval($this->valor($filtros, "id_resurtido_almacen", $this->valor($filtros, "id_resurtido", 0)));
            $folio = trim((string) $this->valor($filtros, "folio", ""));
            if ($id <= 0 && $folio === "") {
                return $this->crudResponse(true, "warning", "Selecciona folio de resurtido");
            }

            $where = $id > 0 ? "r.id_resurtido_almacen=:id" : "r.folio=:folio";
            $params = $id > 0 ? array(":id" => $id) : array(":folio" => $folio);
            $sql = "SELECT r.id_resurtido_almacen, r.id_resurtido_almacen AS id_resurtido,
                    r.folio, r.tipo_documento, r.estatus, r.prioridad, r.origen_solicitud,
                    r.id_almacen_solicitante, r.id_almacen_solicitante AS id_almacen_destino,
                    r.id_almacen_origen, r.id_almacen_transito,
                    sol.almacen AS almacen_solicitante, sol.almacen AS almacen_destino,
                    ori.almacen AS almacen_origen, tra.almacen AS almacen_transito,
                    r.fecha_solicitud, r.fecha_autorizacion, r.fecha_preparacion,
                    r.fecha_envio, r.fecha_recepcion, r.fecha_cierre,
                    r.solicitado_por, r.autorizado_por, r.preparado_por, r.enviado_por,
                    r.recibido_por, r.cerrado_por, r.observaciones, r.motivo_cancelacion,
                    r.fecha_registro, r.fecha_actualizacion
                FROM erp_almacen_resurtidos r
                LEFT JOIN {$this->tabla_erp_almacenes} sol ON sol.id_almacen=r.id_almacen_solicitante
                LEFT JOIN {$this->tabla_erp_almacenes} ori ON ori.id_almacen=r.id_almacen_origen
                LEFT JOIN {$this->tabla_erp_almacenes} tra ON tra.id_almacen=r.id_almacen_transito
                WHERE {$where}
                LIMIT 1";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $encabezado = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$encabezado) {
                return $this->crudResponse(true, "warning", "Folio de resurtido no encontrado");
            }

            $id_resurtido = intval($encabezado["id_resurtido_almacen"]);
            $params_id = array(":id" => $id_resurtido);

            $stmt = $db->prepare("SELECT id_resurtido_detalle, id_resurtido_almacen, id_sku_erp, id_producto,
                    sku, nombre_producto, unidad_base, cantidad_solicitada, cantidad_autorizada,
                    cantidad_preparada, cantidad_enviada, cantidad_recibida, cantidad_diferencia,
                    estatus, motivo_rechazo, observaciones, fecha_registro, fecha_actualizacion
                FROM erp_almacen_resurtido_detalle
                WHERE id_resurtido_almacen=:id
                ORDER BY id_resurtido_detalle ASC");
            $stmt->execute($params_id);
            $detalle = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = $db->prepare("SELECT p.id_resurtido_preparacion, p.id_resurtido_almacen,
                    p.id_resurtido_detalle, p.id_existencia_origen, p.id_inventario_unidad,
                    p.id_almacen_origen, a.almacen AS almacen_origen, p.ubicacion_origen_id,
                    p.id_sku_erp, p.lote, p.fecha_caducidad, p.cantidad_preparada,
                    p.cantidad_unidad_antes, p.cantidad_unidad_despues, p.estado_fisico_unidad,
                    p.estatus, p.preparado_por, p.fecha_preparacion, p.observaciones,
                    e.codigo_existencia, u.codigo_unico, u.codigo_etiqueta_interna
                FROM erp_almacen_resurtido_preparacion p
                LEFT JOIN {$this->tabla_erp_almacenes} a ON a.id_almacen=p.id_almacen_origen
                LEFT JOIN {$this->tabla_erp_inventario_existencias} e ON e.id_existencia_inventario=p.id_existencia_origen
                LEFT JOIN {$this->tabla_erp_inventario_unidades} u ON u.id_inventario_unidad=p.id_inventario_unidad
                WHERE p.id_resurtido_almacen=:id
                ORDER BY p.id_resurtido_preparacion ASC");
            $stmt->execute($params_id);
            $preparacion = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = $db->prepare("SELECT id_resurtido_envio, id_resurtido_almacen, id_resurtido_preparacion,
                    id_movimiento_salida, id_movimiento_transito_entrada, id_existencia_transito,
                    id_inventario_unidad, cantidad_enviada, estatus, enviado_por, fecha_envio, observaciones
                FROM erp_almacen_resurtido_envios
                WHERE id_resurtido_almacen=:id
                ORDER BY id_resurtido_envio ASC");
            $stmt->execute($params_id);
            $envios = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = $db->prepare("SELECT r.id_resurtido_recepcion, r.id_resurtido_almacen, r.id_resurtido_envio,
                    r.id_almacen_destino, a.almacen AS almacen_destino, r.ubicacion_destino_id,
                    r.id_movimiento_transito_salida, r.id_movimiento_entrada_destino,
                    r.id_existencia_destino, r.id_inventario_unidad, r.lote_recibido,
                    r.fecha_caducidad_recibida, r.cantidad_recibida, r.estatus,
                    r.recibido_por, r.fecha_recepcion, r.observaciones
                FROM erp_almacen_resurtido_recepciones r
                LEFT JOIN {$this->tabla_erp_almacenes} a ON a.id_almacen=r.id_almacen_destino
                WHERE r.id_resurtido_almacen=:id
                ORDER BY r.id_resurtido_recepcion ASC");
            $stmt->execute($params_id);
            $recepciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = $db->prepare("SELECT id_resurtido_diferencia, id_resurtido_almacen, id_resurtido_detalle,
                    id_resurtido_envio, id_resurtido_recepcion, tipo_diferencia, severidad,
                    id_sku_erp, id_inventario_unidad, cantidad_esperada, cantidad_recibida,
                    cantidad_diferencia, lote_esperado, lote_recibido, fecha_caducidad_esperada,
                    fecha_caducidad_recibida, estatus, accion_sugerida, accion_tomada,
                    registrado_por, resuelto_por, fecha_registro, fecha_resolucion, observaciones
                FROM erp_almacen_resurtido_diferencias
                WHERE id_resurtido_almacen=:id
                ORDER BY id_resurtido_diferencia ASC");
            $stmt->execute($params_id);
            $diferencias = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $this->crudResponse(false, "success", "Resurtido consultado", array(
                "schema_pendiente" => 0,
                "encabezado" => $encabezado,
                "detalle" => $detalle,
                "preparacion" => $preparacion,
                "envios" => $envios,
                "recepciones" => $recepciones,
                "diferencias" => $diferencias
            ));
        } catch (Exception $e) {
            return $this->crudResponse(true, "danger", $e->getMessage());
        }
    }

    public function consultar_recepciones_almacen() {
        $this->setColumnas(array(
            "erpar.id_recepcion_almacen",
            "erpar.id_orden_compra",
            "erpar.id_proveedor",
            "erpar.id_almacen",
            "erpar.folio",
            "erpar.folio_orden_compra",
            "erpar.estatus",
            "erpar.origen",
            "erpar.fecha_alerta",
            "erpar.fecha_inicio_recepcion",
            "erpar.fecha_cierre_recepcion",
            "erpar.observaciones",
            "erpar.fecha_registro",
            "erpp.proveedor",
            "erpa.almacen",
            "erpco.total as total_orden",
            "erpco.fecha_orden",
            "erpco.fecha_entrega_estimada",
            "(SELECT COUNT(*) FROM erp_almacen_recepciones_detalle erpard WHERE erpard.id_recepcion_almacen = erpar.id_recepcion_almacen) as total_partidas",
            "(SELECT COALESCE(SUM(erpard.cantidad_ordenada), 0) FROM erp_almacen_recepciones_detalle erpard WHERE erpard.id_recepcion_almacen = erpar.id_recepcion_almacen) as cantidad_ordenada",
            "(SELECT COALESCE(SUM(erpard.cantidad_recibida), 0) FROM erp_almacen_recepciones_detalle erpard WHERE erpard.id_recepcion_almacen = erpar.id_recepcion_almacen) as cantidad_recibida",
            "(SELECT COALESCE(SUM(erpard.cantidad_pendiente), 0) FROM erp_almacen_recepciones_detalle erpard WHERE erpard.id_recepcion_almacen = erpar.id_recepcion_almacen) as cantidad_pendiente"
        ));
        $this->setTabla($this->tabla_erp_almacen_recepciones . " erpar");
        $this->setLeftJoin("erp_proveedores erpp ON erpp.id_proveedor = erpar.id_proveedor");
        $this->setLeftJoin($this->tabla_erp_almacenes . " erpa ON erpa.id_almacen = erpar.id_almacen");
        $this->setLeftJoin($this->tabla_erp_compras_ordenes . " erpco ON erpco.id_orden_compra = erpar.id_orden_compra");
        $this->setOrderBy("erpar.id_recepcion_almacen DESC");
        return $this->listar();
    }

    public function consultar_presentaciones_preparables($filtros = array()) {
        try {
            $db = $this->getConexion();
            $id_almacen = intval($this->valor($filtros, "id_almacen", 0));
            if ($id_almacen > 0) {
                $this->validar_almacen_preparacion($db, $id_almacen);
            }
            $termino = trim((string) $this->valor($filtros, "termino", ""));
            $params = array(
                ":almacen" => $id_almacen,
                ":almacen_todos" => $id_almacen
            );
            $whereTermino = "";
            if ($termino !== "") {
                $whereTermino = " AND (
                    base.sku LIKE :termino OR base.nombre LIKE :termino
                    OR pres.sku LIKE :termino OR pres.nombre LIKE :termino
                    OR prod.nombre LIKE :termino OR prod.codigo_producto LIKE :termino
                )";
                $params[":termino"] = "%" . $termino . "%";
            }

            $sql = "SELECT
                NULL AS id_sku_presentacion_regla,
                tr.id_sku_transformacion,
                tr.id_sku_origen AS id_sku_base,
                tr.id_sku_resultado AS id_sku_presentacion,
                ROUND(tr.cantidad_origen / NULLIF(tr.unidades_resultado,0), 6) AS factor_salida_base,
                tr.cantidad_origen,
                tr.unidades_resultado,
                tr.tipo_transformacion,
                tr.modo_disponibilidad,
                'preparacion' AS consume_stock_base_en,
                tr.requiere_empaque,
                tr.capacidad_diaria,
                tr.merma_porcentaje,
                tr.estatus,
                prod.id_producto_erp,
                prod.codigo_producto,
                prod.nombre AS producto,
                base.sku AS sku_base,
                base.nombre AS nombre_base,
                ub.abreviatura AS unidad_base,
                pres.sku AS sku_presentacion,
                pres.nombre AS nombre_presentacion,
                up.abreviatura AS unidad_presentacion,
                COALESCE(regla_pres.generar_etiqueta_interna, 0) AS genera_etiqueta_presentacion,
                COALESCE(regla_pres.prefijo_etiqueta_interna, '') AS prefijo_etiqueta_presentacion,
                COALESCE(SUM(CASE WHEN :almacen_todos=0 OR exi.id_almacen_clave=:almacen THEN exi.cantidad_disponible ELSE 0 END), 0) AS existencia_base_disponible,
                CASE
                    WHEN tr.cantidad_origen > 0 THEN FLOOR(COALESCE(SUM(CASE WHEN :almacen_todos=0 OR exi.id_almacen_clave=:almacen THEN exi.cantidad_disponible ELSE 0 END), 0) / (tr.cantidad_origen / NULLIF(tr.unidades_resultado,0)))
                    ELSE 0
                END AS unidades_posibles
                FROM {$this->tabla_erp_catalogo_sku_transformaciones} tr
                INNER JOIN erp_catalogo_skus base ON base.id_sku=tr.id_sku_origen
                INNER JOIN erp_catalogo_skus pres ON pres.id_sku=tr.id_sku_resultado
                INNER JOIN erp_catalogo_productos prod ON prod.id_producto_erp=base.id_producto_erp
                LEFT JOIN erp_catalogo_unidades ub ON ub.id_unidad=base.id_unidad_base
                LEFT JOIN erp_catalogo_unidades up ON up.id_unidad=pres.id_unidad_base
                LEFT JOIN erp_catalogo_sku_reglas_inventario regla_pres ON regla_pres.id_sku=pres.id_sku
                LEFT JOIN {$this->tabla_erp_inventario_existencias} exi ON exi.id_sku_erp=base.id_sku AND exi.estatus_existencia='disponible'
                WHERE tr.estatus='activa'
                  AND tr.modo_disponibilidad IN ('preparada', 'mixta')
                  AND base.estatus='activo'
                  AND pres.estatus='activo'
                  AND prod.estatus='activo'
                  {$whereTermino}
                GROUP BY tr.id_sku_transformacion, tr.id_sku_origen, tr.id_sku_resultado,
                  tr.cantidad_origen, tr.unidades_resultado, tr.tipo_transformacion, tr.modo_disponibilidad,
                  tr.requiere_empaque, tr.capacidad_diaria, tr.merma_porcentaje, tr.estatus,
                  prod.id_producto_erp, prod.codigo_producto, prod.nombre,
                  base.sku, base.nombre, ub.abreviatura,
                  pres.sku, pres.nombre, up.abreviatura,
                  regla_pres.generar_etiqueta_interna, regla_pres.prefijo_etiqueta_interna
                ORDER BY prod.nombre, base.sku, tr.tipo_transformacion, tr.cantidad_origen, pres.sku";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return $this->crudResponse(false, "success", "Presentaciones preparables consultadas", $stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (Exception $e) {
            return $this->crudResponse(true, "danger", $e->getMessage());
        }
    }

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-06-25
     * Proposito: consulta existencias origen para preparacion y adjunta unidades fisicas trazables cuando existan.
     * Impacto: Almacen/Preparacion; permite elegir etiqueta/unidad especifica sin cambiar el saldo agregado de Inventario.
     * Contrato: devuelve existencias disponibles del SKU base y, por existencia, unidades con contenido fisico disponible.
     */
    public function consultar_existencias_base_preparacion($id_sku_base, $id_almacen = 0) {
        $id_sku_base = intval($id_sku_base);
        $id_almacen = intval($id_almacen);
        if ($id_sku_base <= 0) {
            return $this->crudResponse(true, "warning", "SKU base no valido");
        }

        try {
            $db = $this->getConexion();
            $params = array(":sku" => $id_sku_base);
            $whereAlmacen = "";
            if ($id_almacen > 0) {
                $whereAlmacen = " AND exi.id_almacen_clave=:almacen";
                $params[":almacen"] = $id_almacen;
            }

            $sql = "SELECT
                exi.id_existencia_inventario,
                exi.id_producto,
                exi.id_sku_erp,
                exi.id_almacen,
                exi.id_almacen_clave,
                alm.almacen,
                exi.codigo_existencia,
                exi.lote,
                exi.fecha_caducidad,
                exi.ubicacion_id,
                exi.ubicacion,
                exi.cantidad,
                exi.cantidad_apartada,
                exi.cantidad_disponible,
                exi.costo_promedio,
                exi.estatus_existencia,
                sku.sku,
                sku.nombre AS nombre_sku
                FROM {$this->tabla_erp_inventario_existencias} exi
                INNER JOIN erp_catalogo_skus sku ON sku.id_sku=exi.id_sku_erp
                LEFT JOIN {$this->tabla_erp_almacenes} alm ON alm.id_almacen=exi.id_almacen_clave
                WHERE exi.id_sku_erp=:sku
                  AND exi.estatus_existencia='disponible'
                  AND exi.cantidad_disponible>0
                  {$whereAlmacen}
                ORDER BY CASE WHEN exi.fecha_caducidad IS NULL THEN 1 ELSE 0 END,
                  exi.fecha_caducidad, exi.fecha_registro, exi.id_existencia_inventario";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $existencias = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $existencias = $this->adjuntar_unidades_fisicas_preparacion($db, $existencias);
            return $this->crudResponse(false, "success", "Existencias base consultadas", $existencias);
        } catch (Exception $e) {
            return $this->crudResponse(true, "danger", $e->getMessage());
        }
    }

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-06-25
     * Proposito: lista preparaciones con sus referencias de existencia y unidad fisica origen.
     * Impacto: Almacen/Preparacion; permite reabrir borradores sin perder la etiqueta/unidad seleccionada.
     */
    public function consultar_preparaciones($filtros = array()) {
        try {
            $db = $this->getConexion();
            $estado = trim((string) $this->valor($filtros, "estatus", ""));
            $termino = trim((string) $this->valor($filtros, "q", ""));
            $solo_operativos = intval($this->valor($filtros, "solo_operativos", 0)) === 1;
            $params = array();
            $where = "";
            if ($estado !== "") {
                $where .= " AND prep.estatus=:estatus";
                $params[":estatus"] = $estado;
            }
            if ($termino !== "") {
                $where .= " AND (prep.folio LIKE :q OR base.sku LIKE :q OR pres.sku LIKE :q OR base.nombre LIKE :q OR pres.nombre LIKE :q)";
                $params[":q"] = "%" . $termino . "%";
            }
            if ($solo_operativos) {
                $where .= " AND COALESCE(alm.estatus,'activo')='activo' AND COALESCE(alm.permite_preparacion,0)=1";
            }

            $sql = "SELECT prep.id_preparacion_almacen, prep.folio, prep.estatus, prep.fecha_preparacion,
                prep.id_almacen, alm.almacen, prep.id_sku_base, base.sku AS sku_base, base.nombre AS nombre_base,
                prep.id_sku_presentacion, pres.sku AS sku_presentacion, pres.nombre AS nombre_presentacion,
                prep.id_sku_presentacion_regla, prep.id_sku_transformacion, prep.id_existencia_origen,
                prep.id_unidad_origen,
                prep.unidades_preparadas, prep.cantidad_base_consumida, prep.cantidad_origen_consumida,
                prep.merma_porcentaje, prep.observaciones, prep.fecha_registro, prep.fecha_actualizacion
                FROM {$this->tabla_erp_almacen_preparaciones} prep
                INNER JOIN erp_catalogo_skus base ON base.id_sku=prep.id_sku_base
                INNER JOIN erp_catalogo_skus pres ON pres.id_sku=prep.id_sku_presentacion
                LEFT JOIN {$this->tabla_erp_almacenes} alm ON alm.id_almacen=prep.id_almacen
                WHERE 1=1 {$where}
                ORDER BY prep.id_preparacion_almacen DESC
                LIMIT 100";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return $this->crudResponse(false, "success", "Preparaciones consultadas", $stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (Exception $e) {
            return $this->crudResponse(true, "danger", $e->getMessage());
        }
    }

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-06-25
     * Proposito: guarda borradores de preparacion enlazando existencia y, cuando aplique, unidad fisica origen.
     * Impacto: Almacen/Preparacion; evita preparar desde saldo agregado cuando hay etiquetas/unidades trazables.
     * Contrato: si la existencia origen tiene unidades disponibles, `id_unidad_origen` es obligatorio y debe cubrir el consumo.
     */
    public function guardar_borrador_preparacion($datos = array(), $id_usuario = 0) {
        $id_preparacion = intval($this->valor($datos, "id_preparacion_almacen", 0));
        $id_almacen = intval($this->valor($datos, "id_almacen", 0));
        $id_regla = intval($this->valor($datos, "id_sku_presentacion_regla", 0));
        $id_transformacion = intval($this->valor($datos, "id_sku_transformacion", 0));
        $id_existencia_origen = intval($this->valor($datos, "id_existencia_origen", 0));
        $id_unidad_origen = intval($this->valor($datos, "id_unidad_origen", 0));
        $unidades = intval($this->valor($datos, "unidades_preparadas", 0));
        $observaciones = trim((string) $this->valor($datos, "observaciones", ""));

        if ($id_almacen <= 0 || ($id_regla <= 0 && $id_transformacion <= 0) || $unidades <= 0) {
            return $this->crudResponse(true, "warning", "Almacen, transformacion y unidades son obligatorios");
        }
        if ($id_transformacion > 0 && $id_existencia_origen <= 0) {
            return $this->crudResponse(true, "warning", "Selecciona la existencia fisica origen");
        }

        $db = $this->getConexion();
        try {
            $db->beginTransaction();
            $this->validar_almacen_preparacion($db, $id_almacen);
            $regla = $id_transformacion > 0
                ? $this->consultar_transformacion_preparacion($db, $id_transformacion)
                : $this->consultar_regla_preparacion($db, $id_regla);
            $cantidad_base = $this->calcular_cantidad_base_preparacion($unidades, $regla);
            if ($id_existencia_origen > 0) {
                $this->seleccionar_existencia_origen_preparacion($db, $id_existencia_origen, intval($regla["id_sku_base"]), $id_almacen, $cantidad_base, false);
                if ($id_unidad_origen > 0) {
                    $this->seleccionar_unidad_origen_preparacion($db, $id_unidad_origen, $id_existencia_origen, intval($regla["id_sku_base"]), $id_almacen, $cantidad_base, false);
                } else if ($this->existencia_tiene_unidades_preparacion($db, $id_existencia_origen)) {
                    throw new Exception("Selecciona la unidad fisica origen para preparar");
                }
            }

            if ($id_preparacion > 0) {
                $stmt = $db->prepare("SELECT id_preparacion_almacen, folio, estatus
                    FROM {$this->tabla_erp_almacen_preparaciones}
                    WHERE id_preparacion_almacen=:preparacion FOR UPDATE");
                $stmt->execute(array(":preparacion" => $id_preparacion));
                $actual = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$actual) {
                    throw new Exception("Borrador de preparacion no encontrado");
                }
                if (trim($actual["estatus"]) !== "borrador") {
                    throw new Exception("Solo se puede editar una preparacion en borrador");
                }

                $stmt = $db->prepare("UPDATE {$this->tabla_erp_almacen_preparaciones}
                    SET id_almacen=:almacen, id_sku_base=:sku_base, id_sku_presentacion=:sku_presentacion,
                        id_sku_presentacion_regla=:regla, id_sku_transformacion=:transformacion,
                        id_existencia_origen=:existencia_origen, id_unidad_origen=:unidad_origen, unidades_preparadas=:unidades,
                        cantidad_base_consumida=:cantidad_base, cantidad_origen_consumida=:cantidad_origen, merma_porcentaje=:merma,
                        observaciones=:observaciones, fecha_actualizacion=NOW()
                    WHERE id_preparacion_almacen=:preparacion");
                $stmt->execute(array(
                    ":almacen" => $id_almacen,
                    ":sku_base" => intval($regla["id_sku_base"]),
                    ":sku_presentacion" => intval($regla["id_sku_presentacion"]),
                    ":regla" => $id_regla > 0 ? $id_regla : null,
                    ":transformacion" => $id_transformacion > 0 ? $id_transformacion : null,
                    ":existencia_origen" => $id_existencia_origen > 0 ? $id_existencia_origen : null,
                    ":unidad_origen" => $id_unidad_origen > 0 ? $id_unidad_origen : null,
                    ":unidades" => $unidades,
                    ":cantidad_base" => $cantidad_base,
                    ":cantidad_origen" => $cantidad_base,
                    ":merma" => floatval($regla["merma_porcentaje"]),
                    ":observaciones" => $observaciones,
                    ":preparacion" => $id_preparacion
                ));
                $folio = $actual["folio"];
            } else {
                $folio = $this->generar_folio_preparacion($db);
                $stmt = $db->prepare("INSERT INTO {$this->tabla_erp_almacen_preparaciones}
                    (folio, id_almacen, id_sku_base, id_sku_presentacion, id_sku_presentacion_regla,
                     id_sku_transformacion, id_existencia_origen, estatus, unidades_preparadas,
                     id_unidad_origen,
                     cantidad_base_consumida, cantidad_origen_consumida, merma_porcentaje,
                     observaciones, creado_por, fecha_registro)
                    VALUES (:folio, :almacen, :sku_base, :sku_presentacion, :regla,
                     :transformacion, :existencia_origen, 'borrador', :unidades,
                     :unidad_origen,
                     :cantidad_base, :cantidad_origen, :merma, :observaciones, :usuario, NOW())");
                $stmt->execute(array(
                    ":folio" => $folio,
                    ":almacen" => $id_almacen,
                    ":sku_base" => intval($regla["id_sku_base"]),
                    ":sku_presentacion" => intval($regla["id_sku_presentacion"]),
                    ":regla" => $id_regla > 0 ? $id_regla : null,
                    ":transformacion" => $id_transformacion > 0 ? $id_transformacion : null,
                    ":existencia_origen" => $id_existencia_origen > 0 ? $id_existencia_origen : null,
                    ":unidad_origen" => $id_unidad_origen > 0 ? $id_unidad_origen : null,
                    ":unidades" => $unidades,
                    ":cantidad_base" => $cantidad_base,
                    ":cantidad_origen" => $cantidad_base,
                    ":merma" => floatval($regla["merma_porcentaje"]),
                    ":observaciones" => $observaciones,
                    ":usuario" => intval($id_usuario) ?: null
                ));
                $id_preparacion = intval($db->lastInsertId());
            }

            $db->commit();
            return $this->crudResponse(false, "success", "Borrador de preparacion guardado", array(
                "id_preparacion_almacen" => $id_preparacion,
                "folio" => $folio,
                "estatus" => "borrador",
                "id_almacen" => $id_almacen,
                "id_sku_base" => intval($regla["id_sku_base"]),
                "id_sku_presentacion" => intval($regla["id_sku_presentacion"]),
                "id_sku_presentacion_regla" => $id_regla,
                "id_sku_transformacion" => $id_transformacion,
                "id_existencia_origen" => $id_existencia_origen,
                "id_unidad_origen" => $id_unidad_origen,
                "unidades_preparadas" => $unidades,
                "cantidad_base_consumida" => $cantidad_base,
                "cantidad_origen_consumida" => $cantidad_base,
                "merma_porcentaje" => floatval($regla["merma_porcentaje"])
            ));
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return $this->crudResponse(true, "danger", $e->getMessage());
        }
    }

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-06-25
     * Proposito: confirma preparacion registrando kardex y consumo de unidad fisica cuando fue seleccionada.
     * Impacto: Almacen/Preparacion e Inventario; mantiene salida del SKU origen, entrada del SKU resultado y trazabilidad por etiqueta.
     * Contrato: la preparacion debe estar en borrador; si hay unidad origen, esta debe pertenecer a la existencia y tener saldo suficiente.
     */
    public function confirmar_preparacion($id_preparacion, $id_usuario = 0) {
        $id_preparacion = intval($id_preparacion);
        if ($id_preparacion <= 0) {
            return $this->crudResponse(true, "warning", "Preparacion no valida");
        }

        $db = $this->getConexion();
        try {
            $db->beginTransaction();
            $stmt = $db->prepare("SELECT * FROM {$this->tabla_erp_almacen_preparaciones}
                WHERE id_preparacion_almacen=:preparacion FOR UPDATE");
            $stmt->execute(array(":preparacion" => $id_preparacion));
            $preparacion = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$preparacion) {
                throw new Exception("Preparacion no encontrada");
            }
            if (trim($preparacion["estatus"]) !== "borrador") {
                throw new Exception("Solo se puede confirmar una preparacion en borrador");
            }
            $this->validar_almacen_preparacion($db, intval($preparacion["id_almacen"]));

            $regla = intval($preparacion["id_sku_transformacion"]) > 0
                ? $this->consultar_transformacion_preparacion($db, intval($preparacion["id_sku_transformacion"]))
                : $this->consultar_regla_preparacion($db, intval($preparacion["id_sku_presentacion_regla"]));
            $cantidad_base = round(floatval($this->valor($preparacion, "cantidad_origen_consumida", $preparacion["cantidad_base_consumida"])), 6);
            $unidades = intval($preparacion["unidades_preparadas"]);
            if ($cantidad_base <= 0 || $unidades <= 0) {
                throw new Exception("La preparacion no tiene cantidades validas");
            }
            if (intval($preparacion["id_existencia_origen"]) <= 0) {
                throw new Exception("La preparacion no tiene existencia origen seleccionada");
            }

            $existencia_base = $this->seleccionar_existencia_origen_preparacion(
                $db,
                intval($preparacion["id_existencia_origen"]),
                intval($preparacion["id_sku_base"]),
                intval($preparacion["id_almacen"]),
                $cantidad_base,
                true
            );
            $unidad_origen = null;
            if (intval($this->valor($preparacion, "id_unidad_origen", 0)) > 0) {
                $unidad_origen = $this->seleccionar_unidad_origen_preparacion(
                    $db,
                    intval($preparacion["id_unidad_origen"]),
                    intval($preparacion["id_existencia_origen"]),
                    intval($preparacion["id_sku_base"]),
                    intval($preparacion["id_almacen"]),
                    $cantidad_base,
                    true
                );
            } else if ($this->existencia_tiene_unidades_preparacion($db, intval($preparacion["id_existencia_origen"]))) {
                throw new Exception("La preparacion requiere unidad fisica origen");
            }
            $consumo = $this->registrar_consumo_preparacion($db, $preparacion, $existencia_base, $cantidad_base, $unidad_origen);
            $movimiento_salida = $this->aplicar_salida_preparacion($db, $preparacion, $existencia_base, $consumo, $cantidad_base);
            $this->actualizar_ultimo_movimiento_existencia($db, intval($existencia_base["id_existencia_inventario"]), $movimiento_salida);
            if ($unidad_origen) {
                $this->actualizar_unidad_origen_preparacion($db, $unidad_origen, $consumo, $movimiento_salida);
            }

            $existencia_presentacion = $this->obtener_o_crear_existencia_presentacion_preparacion(
                $db,
                $preparacion,
                $existencia_base,
                $unidades,
                floatval($consumo["costo_total"])
            );
            $resultado = $this->registrar_resultado_preparacion($db, $preparacion, $consumo, $existencia_presentacion, $regla, $unidades);
            $movimiento_entrada = $this->aplicar_entrada_preparacion($db, $preparacion, $existencia_presentacion, $resultado, $unidades);
            $this->actualizar_ultimo_movimiento_existencia($db, intval($existencia_presentacion["id_existencia_inventario"]), $movimiento_entrada);

            $etiquetas = 0;
            $control = $this->consultar_control_producto_transaccion($db, intval($existencia_presentacion["id_producto"]), intval($preparacion["id_sku_presentacion"]));
            if (intval($control["requiere_codigo_unico"]) === 1 || intval($control["generar_etiqueta_individual"]) === 1) {
                $etiquetas = $this->generar_unidades_preparacion($db, $preparacion, $resultado, $existencia_presentacion, $control, $unidades);
            }

            $stmt = $db->prepare("UPDATE {$this->tabla_erp_almacen_preparacion_consumos}
                SET id_movimiento_salida=:movimiento WHERE id_preparacion_consumo=:consumo");
            $stmt->execute(array(":movimiento" => $movimiento_salida, ":consumo" => intval($consumo["id_preparacion_consumo"])));

            $stmt = $db->prepare("UPDATE {$this->tabla_erp_almacen_preparacion_resultados}
                SET id_movimiento_entrada=:movimiento, etiquetas_generadas=:etiquetas
                WHERE id_preparacion_resultado=:resultado");
            $stmt->execute(array(":movimiento" => $movimiento_entrada, ":etiquetas" => $etiquetas, ":resultado" => intval($resultado["id_preparacion_resultado"])));

            $stmt = $db->prepare("UPDATE {$this->tabla_erp_almacen_preparaciones}
                SET estatus='confirmada', fecha_preparacion=NOW(), confirmado_por=:usuario, fecha_actualizacion=NOW()
                WHERE id_preparacion_almacen=:preparacion");
            $stmt->execute(array(":usuario" => intval($id_usuario) ?: null, ":preparacion" => $id_preparacion));

            $db->commit();
            return $this->crudResponse(false, "success", "Preparacion confirmada", array(
                "id_preparacion_almacen" => $id_preparacion,
                "folio" => $preparacion["folio"],
                "movimiento_salida" => $movimiento_salida,
                "movimiento_entrada" => $movimiento_entrada,
                "etiquetas_generadas" => $etiquetas
            ));
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return $this->crudResponse(true, "danger", $e->getMessage());
        }
    }

    public function consultar_recepcion_almacen_completa($id_recepcion_almacen) {
        $id_recepcion_almacen = intval($id_recepcion_almacen);
        if ($id_recepcion_almacen <= 0) {
            return $this->crudResponse(true, "warning", "Recepcion de almacen no valida");
        }

        $recepcion = $this->consultar_recepcion_almacen_por_id($id_recepcion_almacen);
        if ($recepcion["error"] == true) {
            return $recepcion;
        }

        $detalle = $this->consultar_detalle_recepcion_almacen($id_recepcion_almacen);

        return $this->crudResponse(false, "success", "Recepcion de almacen consultada correctamente", array(
            "recepcion" => $recepcion["depurar"],
            "detalle" => $detalle["error"] == false ? $detalle["depurar"] : array()
        ));
    }

    private function consultar_recepcion_almacen_por_id($id_recepcion_almacen) {
        $this->setColumnas(array(
            "erpar.id_recepcion_almacen",
            "erpar.id_orden_compra",
            "erpar.id_proveedor",
            "erpar.id_almacen",
            "erpar.folio",
            "erpar.folio_orden_compra",
            "erpar.estatus",
            "erpar.origen",
            "erpar.fecha_alerta",
            "erpar.fecha_inicio_recepcion",
            "erpar.fecha_cierre_recepcion",
            "erpar.observaciones",
            "erpar.fecha_registro",
            "erpp.proveedor",
            "erpa.almacen",
            "erpco.total as total_orden",
            "erpco.fecha_orden",
            "erpco.fecha_entrega_estimada"
        ));
        $this->setTabla($this->tabla_erp_almacen_recepciones . " erpar");
        $this->setLeftJoin("erp_proveedores erpp ON erpp.id_proveedor = erpar.id_proveedor");
        $this->setLeftJoin($this->tabla_erp_almacenes . " erpa ON erpa.id_almacen = erpar.id_almacen");
        $this->setLeftJoin($this->tabla_erp_compras_ordenes . " erpco ON erpco.id_orden_compra = erpar.id_orden_compra");
        $this->setWhere("erpar.id_recepcion_almacen = " . intval($id_recepcion_almacen));
        return $this->buscarRegistro();
    }

    private function consultar_detalle_recepcion_almacen($id_recepcion_almacen) {
        $db = $this->getConexion();
        $recepcionVariableSelect = $this->esquemaRecepcionVariableDisponibleAlmacen($db)
            ? array(
                "COALESCE(erpsri.requiere_cantidad_variable_recepcion, 0) as requiere_cantidad_variable_recepcion",
                "COALESCE(erpsri.requiere_unidades_fisicas_recepcion, 0) as requiere_unidades_fisicas_recepcion",
                "erpsri.tolerancia_recepcion_porcentaje",
                "erpsri.nota_recepcion_variable"
            )
            : array(
                "0 as requiere_cantidad_variable_recepcion",
                "0 as requiere_unidades_fisicas_recepcion",
                "NULL as tolerancia_recepcion_porcentaje",
                "NULL as nota_recepcion_variable"
            );
        $this->setColumnas(array_merge(array(
            "erpard.id_recepcion_detalle",
            "erpard.id_recepcion_almacen",
            "erpard.id_orden_compra",
            "erpard.id_orden_compra_detalle",
            "erpard.id_producto",
            "erpard.id_sku_erp",
            "erpard.id_producto_proveedor",
            "erpard.sku",
            "erpard.nombre_producto",
            "erpard.unidad",
            "erpard.cantidad_ordenada",
            "erpard.cantidad_recibida",
            "erpard.cantidad_pendiente",
            "erpard.costo_unitario",
            "erpard.estatus",
            "erpard.observaciones",
            "COALESCE(erpard.id_sku_proveedor, erpocd.id_sku_proveedor, 0) as id_sku_proveedor",
            "COALESCE(erpard.id_unidad_compra, erpsp.id_unidad_compra, 0) as id_unidad_compra",
            "COALESCE(NULLIF(erpard.unidad_compra, ''), uc.abreviatura, uc.codigo, erpard.unidad, '') as unidad_compra",
            "COALESCE(NULLIF(erpard.factor_conversion, 0), erpsp.factor_conversion, 0) as factor_conversion",
            "COALESCE(erpard.id_unidad_base, erps.id_unidad_base, 0) as id_unidad_base",
            "COALESCE(NULLIF(erpard.unidad_base, ''), ub.abreviatura, ub.codigo, erpard.unidad, '') as unidad_base",
            "COALESCE(NULLIF(erpard.estatus_sku_recepcion, ''), erps.estatus, '') as estatus_sku_erp",
            "COALESCE(erpsp.estatus, '') as estatus_sku_proveedor",
            "CASE WHEN COALESCE(erpard.cantidad_ordenada_base, 0) > 0 THEN erpard.cantidad_ordenada_base WHEN COALESCE(NULLIF(erpard.factor_conversion, 0), erpsp.factor_conversion, 0) > 0 THEN erpard.cantidad_ordenada * COALESCE(NULLIF(erpard.factor_conversion, 0), erpsp.factor_conversion, 0) ELSE NULL END as cantidad_ordenada_base",
            "CASE WHEN COALESCE(erpard.cantidad_recibida_base, 0) > 0 THEN erpard.cantidad_recibida_base WHEN COALESCE(NULLIF(erpard.factor_conversion, 0), erpsp.factor_conversion, 0) > 0 THEN erpard.cantidad_recibida * COALESCE(NULLIF(erpard.factor_conversion, 0), erpsp.factor_conversion, 0) ELSE NULL END as cantidad_recibida_base",
            "CASE WHEN COALESCE(NULLIF(erpard.factor_conversion, 0), erpsp.factor_conversion, 0) > 0 THEN erpard.cantidad_pendiente * COALESCE(NULLIF(erpard.factor_conversion, 0), erpsp.factor_conversion, 0) ELSE NULL END as cantidad_pendiente_base",
            "COALESCE(erpsri.requiere_lote, erpci.requiere_lote, 0) as requiere_lote",
            "COALESCE(erpsri.requiere_caducidad, erpci.requiere_caducidad, 0) as requiere_caducidad",
            "COALESCE(erpsri.requiere_serie_fabricante, erpsri.requiere_serie, erpci.requiere_codigo_unico, 0) as requiere_codigo_unico",
            "COALESCE(erpsri.generar_etiqueta_interna, erpci.generar_etiqueta_individual, 0) as generar_etiqueta_individual",
            "COALESCE(erpsri.prefijo_etiqueta_interna, erpci.prefijo_codigo_unico) as prefijo_codigo_unico",
            "COALESCE(erpsri.dias_alerta_caducidad, erpci.dias_alerta_caducidad, 90) as dias_alerta_caducidad",
            "COALESCE(erpsri.dias_minimos_recepcion, erpci.dias_minimos_recepcion, 0) as dias_minimos_recepcion",
            "COALESCE(erpsri.estrategia_salida, erpci.estrategia_salida, 'FIFO') as estrategia_salida",
            "CASE WHEN erpsri.id_sku IS NOT NULL THEN IF(erpsri.requiere_lote = 1, 0, 1) ELSE COALESCE(erpci.permitir_recepcion_sin_lote, 1) END as permitir_recepcion_sin_lote",
            "COALESCE(erpci.permitir_recepcion_caducada, 0) as permitir_recepcion_caducada"
        ), $recepcionVariableSelect));
        $this->setTabla($this->tabla_erp_almacen_recepciones_detalle . " erpard");
        $this->setLeftJoin($this->tabla_erp_compras_ordenes_detalle . " erpocd ON erpocd.id_detalle = erpard.id_orden_compra_detalle");
        $this->setLeftJoin("erp_catalogo_skus erps ON erps.id_sku = erpard.id_sku_erp");
        $this->setLeftJoin("erp_catalogo_sku_proveedores erpsp ON erpsp.id_sku_proveedor = COALESCE(erpard.id_sku_proveedor, erpocd.id_sku_proveedor)");
        $this->setLeftJoin("erp_catalogo_unidades uc ON uc.id_unidad = erpsp.id_unidad_compra");
        $this->setLeftJoin("erp_catalogo_unidades ub ON ub.id_unidad = erps.id_unidad_base");
        $this->setLeftJoin("erp_catalogo_sku_reglas_inventario erpsri ON erpsri.id_sku = erpard.id_sku_erp");
        $this->setLeftJoin("erp_productos_control_inventario erpci ON erpci.id_producto = erpard.id_producto");
        $this->setWhere("erpard.id_recepcion_almacen = " . intval($id_recepcion_almacen));
        $respuesta = $this->listar();
        if ($respuesta["error"] == false && is_array($respuesta["depurar"])) {
            foreach ($respuesta["depurar"] as $idx => $producto) {
                $respuesta["depurar"][$idx] = $this->anotar_alertas_recepcion_catalogo($producto);
            }
        }
        return $respuesta;
    }

    private function anotar_alertas_recepcion_catalogo($producto) {
        $alertas = array();
        $bloqueante = 0;
        $id_sku_erp = intval($this->valor($producto, "id_sku_erp", 0));
        $id_sku_proveedor = intval($this->valor($producto, "id_sku_proveedor", 0));
        $factor = floatval($this->valor($producto, "factor_conversion", 0));
        $unidad_compra = trim($this->valor($producto, "unidad_compra", ""));
        $unidad_base = trim($this->valor($producto, "unidad_base", ""));
        $estatus_sku = strtolower(trim($this->valor($producto, "estatus_sku_erp", "")));
        $estatus_relacion = strtolower(trim($this->valor($producto, "estatus_sku_proveedor", "")));
        $recepcion_variable = intval($this->valor($producto, "requiere_cantidad_variable_recepcion", 0)) === 1;

        if ($id_sku_erp <= 0) {
            $alertas[] = array("tipo" => "sku_pendiente", "severidad" => "alta", "mensaje" => "SKU ERP pendiente; no se puede generar existencia vendible.");
            $bloqueante = 1;
        }
        if ($recepcion_variable && $unidad_base === "") {
            $alertas[] = array("tipo" => "unidad_base_pendiente", "severidad" => "alta", "mensaje" => "Unidad base pendiente; la cantidad real no puede registrarse sin unidad de inventario.");
            $bloqueante = 1;
        }
        if ($estatus_sku === "fusionado") {
            $alertas[] = array("tipo" => "sku_fusionado", "severidad" => "alta", "mensaje" => "SKU fusionado; se debe recibir contra el SKU vigente.");
            $bloqueante = 1;
        } else if (in_array($estatus_sku, array("inactivo", "descontinuado"), true)) {
            $alertas[] = array("tipo" => "sku_no_activo", "severidad" => "media", "mensaje" => "SKU " . $estatus_sku . "; requiere decision operativa antes de recibir normalmente.");
        }
        if ($id_sku_proveedor > 0) {
            if (!$recepcion_variable && ($unidad_compra === "" || $factor <= 0)) {
                $alertas[] = array("tipo" => "unidad_factor_pendiente", "severidad" => "alta", "mensaje" => "Unidad/factor de compra pendiente; no se deben asumir equivalencias.");
                $bloqueante = 1;
            }
            if ($estatus_relacion !== "" && $estatus_relacion !== "activo") {
                $alertas[] = array("tipo" => "relacion_proveedor_no_activa", "severidad" => "media", "mensaje" => "Relacion proveedor-SKU " . $estatus_relacion . "; validar antes de recibir.");
            }
        }
        if ($id_sku_proveedor <= 0 && $id_sku_erp > 0) {
            $alertas[] = array("tipo" => "relacion_proveedor_pendiente", "severidad" => "media", "mensaje" => "No hay relacion SKU proveedor en la OC; se recibira solo contra SKU ERP.");
        }
        if (!$recepcion_variable && $factor === 1.0 && $unidad_compra !== "" && $unidad_base !== "" && strtolower($unidad_compra) !== strtolower($unidad_base)) {
            $alertas[] = array("tipo" => "factor_sospechoso", "severidad" => "media", "mensaje" => "Unidad compra distinta a unidad base con factor 1; validar que sea correcto.");
        }

        $producto["alertas_recepcion"] = $alertas;
        $producto["alerta_bloqueante"] = $bloqueante;
        return $producto;
    }

    public function preparar_recepcion_desde_orden_compra($id_orden_compra) {
        $id_orden_compra = intval($id_orden_compra);
        if ($id_orden_compra <= 0) {
            return $this->crudResponse(true, "warning", "Orden de compra no valida para preparar recepcion de almacen");
        }

        $orden = $this->consultar_orden_compra_para_recepcion($id_orden_compra);
        if ($orden['error'] == true) {
            return $orden;
        }

        $orden = $orden['depurar'];
        $estatus_orden = strtolower(trim($this->valor($orden, "estatus", "")));
        if ($estatus_orden !== "enviada") {
            return $this->crudResponse(false, "info", "La orden no esta enviada; no se prepara recepcion de almacen", array(
                "id_orden_compra" => $id_orden_compra,
                "estatus" => $this->valor($orden, "estatus", "")
            ));
        }

        $detalle = $this->consultar_detalle_orden_compra_para_recepcion($id_orden_compra);
        if ($detalle['error'] == true) {
            return $this->crudResponse(true, "warning", "La orden esta enviada, pero no tiene detalle para preparar recepcion de almacen", $detalle);
        }

        $recepcion_existente = $this->consultar_recepcion_orden_compra($id_orden_compra);
        if ($recepcion_existente['error'] == false) {
            $id_recepcion_almacen = $this->valor($recepcion_existente['depurar'], "id_recepcion_almacen", 0);
            $detalles_registrados = $this->registrar_detalles_faltantes_recepcion_orden_compra($id_recepcion_almacen, $orden, $detalle['depurar']);
            return $this->crudResponse(false, "success", "Recepcion de almacen existente; detalle verificado", array(
                "id_recepcion_almacen" => $id_recepcion_almacen,
                "id_orden_compra" => $id_orden_compra,
                "detalles_registrados" => $detalles_registrados,
                "recepcion_existente" => true
            ));
        }

        $recepcion = $this->registrar_recepcion_orden_compra($orden);
        if ($recepcion['error'] == true) {
            return $recepcion;
        }

        $id_recepcion_almacen = $recepcion['depurar'];
        $detalles_registrados = $this->registrar_detalles_faltantes_recepcion_orden_compra($id_recepcion_almacen, $orden, $detalle['depurar']);

        return $this->crudResponse(false, "success", "Recepcion de almacen preparada correctamente", array(
            "id_recepcion_almacen" => $id_recepcion_almacen,
            "id_orden_compra" => $id_orden_compra,
            "detalles_registrados" => $detalles_registrados
        ));
    }

    private function consultar_orden_compra_para_recepcion($id_orden_compra) {
        $this->setColumnas(array(
            "id_orden_compra",
            "folio",
            "id_proveedor",
            "id_almacen_destino",
            "folio_proveedor",
            "estatus",
            "fecha_entrega_estimada",
            "observaciones"
        ));
        $this->setTabla($this->tabla_erp_compras_ordenes);
        $this->setWhere("id_orden_compra = " . intval($id_orden_compra));
        return $this->buscarRegistro();
    }

    private function consultar_recepcion_orden_compra($id_orden_compra) {
        $this->setColumnas(array("*"));
        $this->setTabla($this->tabla_erp_almacen_recepciones);
        $this->setWhere("id_orden_compra = " . intval($id_orden_compra));
        return $this->buscarRegistro();
    }

    private function consultar_detalle_orden_compra_para_recepcion($id_orden_compra) {
        $this->setColumnas(array(
            "d.id_detalle as id_orden_compra_detalle",
            "d.id_orden_compra",
            "d.id_producto",
            "d.id_sku_erp",
            "d.id_sku_proveedor",
            "d.id_producto_proveedor",
            "d.sku",
            "d.nombre_producto",
            "d.unidad",
            "d.tipo_item",
            "d.cantidad",
            "d.costo_unitario",
            "d.costo_antes_impuesto",
            "COALESCE(sp.id_unidad_compra, 0) as id_unidad_compra",
            "COALESCE(uc.abreviatura, uc.codigo, d.unidad, '') as unidad_compra",
            "COALESCE(s.id_unidad_base, 0) as id_unidad_base",
            "COALESCE(ub.abreviatura, ub.codigo, d.unidad, '') as unidad_base",
            "COALESCE(sp.factor_conversion, 1) as factor_conversion",
            "COALESCE(s.estatus, '') as estatus_sku_recepcion",
            "COALESCE(sp.estatus, '') as estatus_sku_proveedor"
        ));
        $this->setTabla($this->tabla_erp_compras_ordenes_detalle . " d");
        $this->setLeftJoin("erp_catalogo_sku_reglas_inventario r ON r.id_sku = d.id_sku_erp");
        $this->setLeftJoin("erp_catalogo_skus s ON s.id_sku = d.id_sku_erp");
        $this->setLeftJoin("erp_catalogo_sku_proveedores sp ON sp.id_sku_proveedor = d.id_sku_proveedor");
        $this->setLeftJoin("erp_catalogo_unidades uc ON uc.id_unidad = sp.id_unidad_compra");
        $this->setLeftJoin("erp_catalogo_unidades ub ON ub.id_unidad = s.id_unidad_base");
        $this->setWhere("d.id_orden_compra = " . intval($id_orden_compra) . "
            AND LOWER(COALESCE(d.tipo_item, 'producto')) NOT IN ('servicio','cargo','adicional','no_inventariable')
            AND COALESCE(r.controla_inventario, CASE WHEN s.tipo_inventario IN ('servicio','cargo') THEN 0 ELSE 1 END, 1) = 1");
        return $this->listar();
    }

    private function consultar_recepcion_detalle_orden_compra($id_recepcion_almacen, $producto) {
        $id_orden_compra_detalle = intval($this->valor($producto, "id_orden_compra_detalle", 0));
        $id_producto = intval($this->valor($producto, "id_producto", 0));
        $id_producto_proveedor = intval($this->valor($producto, "id_producto_proveedor", 0));
        $sku = trim($this->valor($producto, "sku", ""));
        $condiciones = array();

        if ($id_orden_compra_detalle > 0) {
            $condiciones[] = "id_orden_compra_detalle = " . $id_orden_compra_detalle;
        }
        if ($sku !== "") {
            $condiciones[] = "sku = '" . addslashes($sku) . "'";
        }
        if ($id_producto > 0) {
            $condiciones[] = "id_producto = " . $id_producto;
        }
        if ($id_producto_proveedor > 0) {
            $condiciones[] = "id_producto_proveedor = " . $id_producto_proveedor;
        }
        if (empty($condiciones)) {
            $condiciones[] = "1 = 0";
        }

        $this->setColumnas(array("id_recepcion_detalle", "cantidad_recibida"));
        $this->setTabla($this->tabla_erp_almacen_recepciones_detalle);
        $this->setWhere("id_recepcion_almacen = " . intval($id_recepcion_almacen) . " AND (" . implode(" OR ", $condiciones) . ")");
        return $this->buscarRegistro();
    }

    private function registrar_detalles_faltantes_recepcion_orden_compra($id_recepcion_almacen, $orden, $detalle) {
        $detalles_registrados = 0;
        foreach ($detalle as $producto) {
            $detalle_existente = $this->consultar_recepcion_detalle_orden_compra($id_recepcion_almacen, $producto);
            if ($detalle_existente['error'] == false) {
                $this->sincronizar_recepcion_detalle_orden_compra($detalle_existente['depurar'], $producto);
                continue;
            }

            $registro_detalle = $this->registrar_recepcion_detalle_orden_compra($id_recepcion_almacen, $orden, $producto);
            if ($registro_detalle['error'] == false) {
                $detalles_registrados++;
            }
        }

        return $detalles_registrados;
    }

    private function sincronizar_recepcion_detalle_orden_compra($detalle_existente, $producto) {
        $id_recepcion_detalle = intval($this->valor($detalle_existente, "id_recepcion_detalle", 0));
        if ($id_recepcion_detalle <= 0) {
            return $this->crudResponse(true, "warning", "Detalle de recepcion no valido para sincronizar");
        }

        $cantidad_ordenada = $this->valor($producto, "cantidad", 0);
        $cantidad_recibida = $this->valor($detalle_existente, "cantidad_recibida", 0);
        $cantidad_pendiente = max(0, floatval($cantidad_ordenada) - floatval($cantidad_recibida));
        $estatus = "pendiente";
        if (floatval($cantidad_recibida) > 0 && $cantidad_pendiente > 0) {
            $estatus = "parcial";
        } else if (floatval($cantidad_recibida) > 0 && $cantidad_pendiente <= 0) {
            $estatus = "recibida";
        }

        $identidad_erp = $this->resolver_identidad_sku_erp($producto);
        $snapshot = $this->snapshot_recepcion_catalogo($producto, $identidad_erp);
        $this->setColumnas(array(
            "id_orden_compra_detalle",
            "id_producto",
            "id_sku_erp",
            "id_sku_proveedor",
            "id_unidad_compra",
            "unidad_compra",
            "id_unidad_base",
            "unidad_base",
            "factor_conversion",
            "id_producto_proveedor",
            "sku",
            "nombre_producto",
            "unidad",
            "cantidad_ordenada",
            "cantidad_ordenada_base",
            "cantidad_pendiente",
            "cantidad_pendiente_base",
            "costo_unitario",
            "costo_unitario_base",
            "estatus",
            "estatus_sku_recepcion",
            "requiere_clasificacion",
            "fecha_actualizacion"
        ));
        $this->setColumnasValores(array(
            intval($this->valor($producto, "id_orden_compra_detalle", 0)),
            $identidad_erp["id_producto_erp"] ?: intval($this->valor($producto, "id_producto", 0)),
            $identidad_erp["id_sku_erp"] ?: null,
            $snapshot["id_sku_proveedor"] ?: null,
            $snapshot["id_unidad_compra"] ?: null,
            $snapshot["unidad_compra"],
            $snapshot["id_unidad_base"] ?: null,
            $snapshot["unidad_base"],
            $snapshot["factor_conversion"],
            intval($this->valor($producto, "id_producto_proveedor", 0)),
            $this->valor($producto, "sku", ""),
            $this->valor($producto, "nombre_producto", ""),
            $snapshot["unidad_compra"] ?: $this->valor($producto, "unidad", ""),
            $cantidad_ordenada,
            $snapshot["cantidad_ordenada_base"],
            $cantidad_pendiente,
            round(floatval($cantidad_pendiente) * floatval($snapshot["factor_conversion"]), 6),
            $this->valor($producto, "costo_unitario", $this->valor($producto, "costo_antes_impuesto", 0)),
            $snapshot["costo_unitario_base"],
            $estatus,
            $snapshot["estatus_sku_recepcion"],
            $snapshot["requiere_clasificacion"],
            date("Y-m-d H:i:s")
        ));
        $this->setTabla($this->tabla_erp_almacen_recepciones_detalle);
        $this->setWhere("id_recepcion_detalle = " . $id_recepcion_detalle);
        return $this->update();
    }

    private function registrar_recepcion_orden_compra($orden) {
        $id_orden_compra = intval($this->valor($orden, "id_orden_compra", 0));
        $this->setColumnas(array(
            "id_orden_compra",
            "id_proveedor",
            "id_almacen",
            "folio",
            "folio_orden_compra",
            "estatus",
            "origen",
            "fecha_alerta",
            "observaciones"
        ));
        $this->setColumnasValores(array(
            $id_orden_compra,
            intval($this->valor($orden, "id_proveedor", 0)),
            intval($this->valor($orden, "id_almacen_destino", 0)),
            "REC-OC-" . $id_orden_compra,
            $this->valor($orden, "folio", "") ?: $id_orden_compra,
            "pendiente",
            "orden_compra",
            $this->valor($orden, "fecha_entrega_estimada", "") ?: date("Y-m-d H:i:s"),
            $this->valor($orden, "observaciones", "")
        ));
        $this->setTabla($this->tabla_erp_almacen_recepciones);
        return $this->insertar();
    }

    private function registrar_recepcion_detalle_orden_compra($id_recepcion_almacen, $orden, $producto) {
        $cantidad_ordenada = $this->valor($producto, "cantidad", 0);
        $identidad_erp = $this->resolver_identidad_sku_erp($producto);
        $snapshot = $this->snapshot_recepcion_catalogo($producto, $identidad_erp);
        $this->setColumnas(array(
            "id_recepcion_almacen",
            "id_orden_compra",
            "id_orden_compra_detalle",
            "id_producto",
            "id_sku_erp",
            "id_sku_proveedor",
            "id_unidad_compra",
            "unidad_compra",
            "id_unidad_base",
            "unidad_base",
            "factor_conversion",
            "id_producto_proveedor",
            "sku",
            "nombre_producto",
            "unidad",
            "cantidad_ordenada",
            "cantidad_ordenada_base",
            "cantidad_recibida",
            "cantidad_recibida_base",
            "cantidad_pendiente",
            "cantidad_pendiente_base",
            "costo_unitario",
            "costo_unitario_base",
            "estatus",
            "estatus_sku_recepcion",
            "requiere_clasificacion"
        ));
        $this->setColumnasValores(array(
            intval($id_recepcion_almacen),
            intval($this->valor($orden, "id_orden_compra", 0)),
            intval($this->valor($producto, "id_orden_compra_detalle", 0)),
            $identidad_erp["id_producto_erp"] ?: intval($this->valor($producto, "id_producto", 0)),
            $identidad_erp["id_sku_erp"] ?: null,
            $snapshot["id_sku_proveedor"] ?: null,
            $snapshot["id_unidad_compra"] ?: null,
            $snapshot["unidad_compra"],
            $snapshot["id_unidad_base"] ?: null,
            $snapshot["unidad_base"],
            $snapshot["factor_conversion"],
            intval($this->valor($producto, "id_producto_proveedor", 0)),
            $this->valor($producto, "sku", ""),
            $this->valor($producto, "nombre_producto", ""),
            $snapshot["unidad_compra"] ?: $this->valor($producto, "unidad", ""),
            $cantidad_ordenada,
            $snapshot["cantidad_ordenada_base"],
            0,
            0,
            $cantidad_ordenada,
            $snapshot["cantidad_ordenada_base"],
            $this->valor($producto, "costo_unitario", $this->valor($producto, "costo_antes_impuesto", 0)),
            $snapshot["costo_unitario_base"],
            "pendiente",
            $snapshot["estatus_sku_recepcion"],
            $snapshot["requiere_clasificacion"]
        ));
        $this->setTabla($this->tabla_erp_almacen_recepciones_detalle);
        return $this->insertar();
    }

    private function snapshot_recepcion_catalogo($producto, $identidad_erp) {
        $factor = floatval($this->valor($producto, "factor_conversion", 1));
        if ($factor <= 0) {
            $factor = 0;
        }
        $cantidad = floatval($this->valor($producto, "cantidad", 0));
        $costo = floatval($this->valor($producto, "costo_unitario", $this->valor($producto, "costo_antes_impuesto", 0)));
        $costo_base = $factor > 0 ? ($costo / $factor) : 0;
        $estatus_sku = trim($this->valor($producto, "estatus_sku_recepcion", ""));
        $requiere_clasificacion = 0;
        if (intval($this->valor($producto, "id_sku_erp", 0)) <= 0 && intval($identidad_erp["id_sku_erp"]) <= 0) {
            $requiere_clasificacion = 1;
        }

        return array(
            "id_sku_proveedor" => intval($this->valor($producto, "id_sku_proveedor", 0)),
            "id_unidad_compra" => intval($this->valor($producto, "id_unidad_compra", 0)),
            "unidad_compra" => trim($this->valor($producto, "unidad_compra", $this->valor($producto, "unidad", ""))),
            "id_unidad_base" => intval($this->valor($producto, "id_unidad_base", 0)),
            "unidad_base" => trim($this->valor($producto, "unidad_base", $this->valor($producto, "unidad", ""))),
            "factor_conversion" => round($factor, 6),
            "cantidad_ordenada_base" => round($cantidad * max($factor, 0), 6),
            "costo_unitario_base" => round($costo_base, 6),
            "estatus_sku_recepcion" => $estatus_sku,
            "requiere_clasificacion" => $requiere_clasificacion
        );
    }

    private function resolver_identidad_sku_erp($producto) {
        $id_sku_erp = intval($this->valor($producto, "id_sku_erp", 0));
        $sku = trim($this->valor($producto, "sku", ""));
        $condiciones = array();
        if ($id_sku_erp > 0) {
            $condiciones[] = "id_sku = " . $id_sku_erp;
        }
        if ($sku !== "") {
            $condiciones[] = "UPPER(TRIM(sku)) = UPPER('" . addslashes($sku) . "')";
        }
        if (empty($condiciones)) {
            return array("id_sku_erp" => 0, "id_producto_erp" => 0);
        }

        $this->setColumnas(array("id_sku", "id_producto_erp"));
        $this->setTabla("erp_catalogo_skus");
        $this->setWhere("(" . implode(" OR ", $condiciones) . ") AND estatus <> 'fusionado'");
        $respuesta = $this->buscarRegistro();
        if ($respuesta["error"] == true || empty($respuesta["depurar"])) {
            return array("id_sku_erp" => 0, "id_producto_erp" => 0);
        }

        return array(
            "id_sku_erp" => intval($respuesta["depurar"]["id_sku"]),
            "id_producto_erp" => intval($respuesta["depurar"]["id_producto_erp"])
        );
    }

    public function guardar_recepcion_almacen($id_recepcion_almacen, $partidas, $id_usuario = 0) {
        $id_recepcion_almacen = intval($id_recepcion_almacen);
        if ($id_recepcion_almacen <= 0) {
            return $this->crudResponse(true, "warning", "Recepcion de almacen no valida");
        }

        if (!is_array($partidas) || empty($partidas)) {
            return $this->crudResponse(true, "warning", "No hay partidas para recibir");
        }

        $db = $this->getConexion();

        try {
            $db->beginTransaction();

            $recepcion = $this->consultar_recepcion_transaccion($db, $id_recepcion_almacen);
            if (!$recepcion) {
                throw new Exception("No se encontro la recepcion de almacen");
            }

            if (in_array(strtolower($recepcion["estatus"]), array("recibida", "cancelada"))) {
                throw new Exception("La recepcion ya no permite movimientos");
            }
            $this->validar_almacen_recepcion($db, intval($recepcion["id_almacen"]));

            $resumen = array(
                "lotes" => 0,
                "movimientos" => 0,
                "unidades" => 0,
                "incidencias" => 0
            );

            $detalle_afectado = array();
            $cantidades_capturadas = array();
            foreach ($partidas as $partida) {
                $cantidad = round(floatval($this->valor($partida, "cantidad", 0)), 4);
                if ($cantidad <= 0) {
                    continue;
                }

                $id_detalle = intval($this->valor($partida, "id_recepcion_detalle", 0));
                $detalle = $this->consultar_detalle_transaccion($db, $id_recepcion_almacen, $id_detalle);
                if (!$detalle) {
                    throw new Exception("Una partida no pertenece a esta recepcion");
                }
                if (intval($detalle["id_sku_erp"]) <= 0) {
                    throw new Exception("El producto " . $detalle["nombre_producto"] . " no esta vinculado a un SKU maestro ERP");
                }
                if (intval($this->valor($detalle, "requiere_clasificacion", 0)) === 1) {
                    throw new Exception("El producto " . $detalle["nombre_producto"] . " requiere clasificacion antes de generar existencia");
                }
                $control = $this->consultar_control_producto_transaccion($db, intval($detalle["id_producto"]), intval($detalle["id_sku_erp"]));
                if (intval($this->valor($detalle, "id_sku_proveedor", 0)) > 0 && floatval($this->valor($detalle, "factor_conversion", 0)) <= 0 && intval($this->valor($control, "requiere_cantidad_variable_recepcion", 0)) !== 1) {
                    throw new Exception("El producto " . $detalle["nombre_producto"] . " no tiene factor de compra valido");
                }
                $partida = $this->partida_recepcion_con_conversion($detalle, $partida, $control);

                if (!isset($cantidades_capturadas[$id_detalle])) {
                    $cantidades_capturadas[$id_detalle] = 0;
                }
                $cantidades_capturadas[$id_detalle] = round($cantidades_capturadas[$id_detalle] + $cantidad, 4);
                if ($cantidades_capturadas[$id_detalle] > round(floatval($detalle["cantidad_pendiente"]), 4)) {
                    throw new Exception("La cantidad capturada de " . $detalle["nombre_producto"] . " supera lo pendiente por recibir");
                }

                $lote = trim($this->valor($partida, "lote", ""));
                $fecha_caducidad = trim($this->valor($partida, "fecha_caducidad", ""));
                $ubicacion = trim($this->valor($partida, "ubicacion", ""));

                if (intval($control["requiere_lote"]) === 1 && $lote === "" && intval($control["permitir_recepcion_sin_lote"]) !== 1) {
                    throw new Exception("El producto " . $detalle["nombre_producto"] . " requiere lote");
                }

                if (intval($control["requiere_caducidad"]) === 1 && $fecha_caducidad === "") {
                    throw new Exception("El producto " . $detalle["nombre_producto"] . " requiere caducidad");
                }

                $generar_codigo_partida = intval($this->valor($partida, "generar_codigo_unico", 0)) === 1;
                if ((intval($control["requiere_codigo_unico"]) === 1 || intval($control["generar_etiqueta_individual"]) === 1 || $generar_codigo_partida) && floor($cantidad) != $cantidad) {
                    throw new Exception("El producto " . $detalle["nombre_producto"] . " requiere codigos unicos y debe recibirse en piezas enteras");
                }

                $revision = $this->evaluar_revision_caducidad($fecha_caducidad, $control);
                if ($revision["caducado"] && intval($control["permitir_recepcion_caducada"]) !== 1) {
                    throw new Exception("El producto " . $detalle["nombre_producto"] . " esta caducado y no permite recepcion");
                }

                $ubicacion_id = $this->obtener_o_crear_ubicacion($db, intval($recepcion["id_almacen"]), $ubicacion);
                $id_recepcion_lote = $this->insertar_recepcion_lote($db, $recepcion, $detalle, $control, $partida, $ubicacion_id, $revision);
                $resumen["lotes"]++;

                $existencia_movimiento = $this->actualizar_existencia($db, $recepcion, $detalle, $partida, $ubicacion_id, $id_recepcion_lote);
                $id_existencia = intval($existencia_movimiento["id_existencia"]);
                $id_movimiento = $this->insertar_movimiento_inventario($db, $recepcion, $detalle, $partida, $id_recepcion_lote, $existencia_movimiento, $ubicacion_id);
                $this->actualizar_ultimo_movimiento_existencia($db, $id_existencia, $id_movimiento);
                $resumen["movimientos"]++;

                if (intval($control["requiere_codigo_unico"]) === 1 || intval($control["generar_etiqueta_individual"]) === 1 || $generar_codigo_partida) {
                    $resumen["unidades"] += $this->generar_unidades_inventario($db, $recepcion, $detalle, $control, $partida, $id_recepcion_lote, $id_existencia, $ubicacion_id);
                }

                if ($revision["requiere_revision"]) {
                    $this->insertar_incidencia_recepcion($db, $recepcion, $detalle, $partida, $revision["motivo_revision"], $revision["severidad"], $revision["accion_sugerida"]);
                    $resumen["incidencias"]++;
                }

                $detalle_afectado[$id_detalle] = true;
            }

            if ($resumen["lotes"] <= 0) {
                throw new Exception("No capturaste cantidades mayores a cero para recibir");
            }

            foreach (array_keys($detalle_afectado) as $id_detalle) {
                $resumen["incidencias"] += $this->actualizar_detalle_recepcion($db, $id_recepcion_almacen, $id_detalle);
                $this->sincronizar_detalle_orden_desde_recepcion($db, $id_recepcion_almacen, $id_detalle);
            }

            $estatus_recepcion = $this->actualizar_estatus_recepcion($db, $id_recepcion_almacen, $id_usuario);
            $estatus_orden = $this->sincronizar_estatus_orden_desde_recepcion($db, $recepcion, $estatus_recepcion);
            $this->cerrar_notificacion_recepcion_pendiente_orden($db, intval($this->valor($recepcion, "id_orden_compra", 0)), $estatus_recepcion);

            $db->commit();
            $resumen["estatus_recepcion"] = $estatus_recepcion;
            $resumen["estatus_orden"] = $estatus_orden;

            return $this->crudResponse(false, "success", "Recepcion guardada correctamente", $resumen);
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return $this->crudResponse(true, "danger", $e->getMessage());
        }
    }

    private function consultar_recepcion_transaccion($db, $id_recepcion_almacen) {
        $stmt = $db->prepare("SELECT * FROM {$this->tabla_erp_almacen_recepciones} WHERE id_recepcion_almacen = :id FOR UPDATE");
        $stmt->execute(array(":id" => intval($id_recepcion_almacen)));
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function consultar_detalle_transaccion($db, $id_recepcion_almacen, $id_detalle) {
        $stmt = $db->prepare("SELECT * FROM {$this->tabla_erp_almacen_recepciones_detalle} WHERE id_recepcion_almacen = :recepcion AND id_recepcion_detalle = :detalle FOR UPDATE");
        $stmt->execute(array(":recepcion" => intval($id_recepcion_almacen), ":detalle" => intval($id_detalle)));
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-06-27
     * Proposito: normaliza cantidad de compra y cantidad base recibida, usando captura real cuando el SKU lo exige.
     * Impacto: Almacen/Recepciones e Inventario; evita generar existencia base desde un factor fijo en SKUs variables.
     * Contrato: `cantidad` conserva unidades fisicas/compra; `cantidad_base_real` alimenta inventario solo si Catalogo marca recepcion variable.
     */
    private function partida_recepcion_con_conversion($detalle, $partida, $control = array()) {
        $cantidad_compra = round(floatval($this->valor($partida, "cantidad", 0)), 6);
        $recepcion_variable = intval($this->valor($control, "requiere_cantidad_variable_recepcion", 0)) === 1;
        $requiere_unidades_fisicas = intval($this->valor($control, "requiere_unidades_fisicas_recepcion", 0)) === 1;
        $factor = floatval($this->valor($detalle, "factor_conversion", 1));
        if ($factor <= 0) {
            $factor = 1;
        }
        if ($requiere_unidades_fisicas && floor($cantidad_compra) != $cantidad_compra) {
            throw new Exception("El producto " . $detalle["nombre_producto"] . " requiere capturar unidades fisicas enteras");
        }

        if ($recepcion_variable) {
            $cantidad_base = round(floatval($this->valor($partida, "cantidad_base_real", $this->valor($partida, "cantidad_base", 0))), 6);
            if ($cantidad_base <= 0) {
                throw new Exception("Captura la cantidad real recibida de " . $detalle["nombre_producto"]);
            }
            if (trim($this->valor($detalle, "unidad_base", "")) === "") {
                throw new Exception("El producto " . $detalle["nombre_producto"] . " no tiene unidad base para registrar cantidad real");
            }
        } else {
            $cantidad_base = round($cantidad_compra * $factor, 6);
        }
        $costo_compra = floatval($this->valor($detalle, "costo_unitario", 0));
        $costo_base = $cantidad_base > 0 ? round(($costo_compra * $cantidad_compra) / $cantidad_base, 6) : round($costo_compra, 6);

        $partida["_cantidad_compra"] = $cantidad_compra;
        $partida["_cantidad_base"] = $cantidad_base;
        $partida["_factor_conversion"] = round($factor, 6);
        $partida["_unidad_compra"] = $this->valor($detalle, "unidad_compra", $this->valor($detalle, "unidad", ""));
        $partida["_unidad_base"] = $this->valor($detalle, "unidad_base", $this->valor($detalle, "unidad", ""));
        $partida["_costo_unitario_base"] = $costo_base;
        return $partida;
    }

    private function consultar_control_producto_transaccion($db, $id_producto, $id_sku_erp = 0) {
        $control = array();
        if (intval($id_sku_erp) > 0) {
            $recepcionVariableSelect = $this->esquemaRecepcionVariableDisponibleAlmacen($db)
                ? ", requiere_cantidad_variable_recepcion, requiere_unidades_fisicas_recepcion, tolerancia_recepcion_porcentaje, nota_recepcion_variable"
                : ", 0 AS requiere_cantidad_variable_recepcion, 0 AS requiere_unidades_fisicas_recepcion, NULL AS tolerancia_recepcion_porcentaje, NULL AS nota_recepcion_variable";
            $stmt = $db->prepare("SELECT requiere_lote, requiere_caducidad, requiere_serie,
                requiere_serie_fabricante, generar_etiqueta_interna, prefijo_etiqueta_interna,
                dias_alerta_caducidad, dias_minimos_recepcion
                {$recepcionVariableSelect}
                FROM erp_catalogo_sku_reglas_inventario WHERE id_sku = :id_sku");
            $stmt->execute(array(":id_sku" => intval($id_sku_erp)));
            $control = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($control) {
                $control["requiere_codigo_unico"] = intval($this->valor($control, "requiere_serie_fabricante", $this->valor($control, "requiere_serie", 0)));
                $control["generar_etiqueta_individual"] = intval($this->valor($control, "generar_etiqueta_interna", 0));
                $control["prefijo_codigo_unico"] = $this->valor($control, "prefijo_etiqueta_interna", "");
                $control["permitir_recepcion_sin_lote"] = 0;
                $control["permitir_recepcion_caducada"] = 0;
            }
        }
        if ($control) {
            return array(
                "requiere_lote" => intval($this->valor($control, "requiere_lote", 0)),
                "requiere_caducidad" => intval($this->valor($control, "requiere_caducidad", 0)),
                "requiere_codigo_unico" => intval($this->valor($control, "requiere_codigo_unico", 0)),
                "generar_etiqueta_individual" => intval($this->valor($control, "generar_etiqueta_individual", 0)),
                "prefijo_codigo_unico" => $this->valor($control, "prefijo_codigo_unico", ""),
                "dias_alerta_caducidad" => intval($this->valor($control, "dias_alerta_caducidad", 90)),
                "dias_minimos_recepcion" => intval($this->valor($control, "dias_minimos_recepcion", 0)),
                "requiere_cantidad_variable_recepcion" => intval($this->valor($control, "requiere_cantidad_variable_recepcion", 0)),
                "requiere_unidades_fisicas_recepcion" => intval($this->valor($control, "requiere_unidades_fisicas_recepcion", 0)),
                "tolerancia_recepcion_porcentaje" => $this->valor($control, "tolerancia_recepcion_porcentaje", null),
                "nota_recepcion_variable" => $this->valor($control, "nota_recepcion_variable", ""),
                "permitir_recepcion_sin_lote" => intval($this->valor($control, "permitir_recepcion_sin_lote", 0)),
                "permitir_recepcion_caducada" => intval($this->valor($control, "permitir_recepcion_caducada", 0))
            );
        }

        $stmt = $db->prepare("SELECT * FROM erp_productos_control_inventario WHERE id_producto = :id_producto");
        $stmt->execute(array(":id_producto" => intval($id_producto)));
        $control = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$control) {
            $control = array();
        }

        return array(
            "requiere_lote" => intval($this->valor($control, "requiere_lote", 0)),
            "requiere_caducidad" => intval($this->valor($control, "requiere_caducidad", 0)),
            "requiere_codigo_unico" => intval($this->valor($control, "requiere_codigo_unico", 0)),
            "generar_etiqueta_individual" => intval($this->valor($control, "generar_etiqueta_individual", 0)),
            "prefijo_codigo_unico" => $this->valor($control, "prefijo_codigo_unico", ""),
            "dias_alerta_caducidad" => intval($this->valor($control, "dias_alerta_caducidad", 90)),
            "dias_minimos_recepcion" => intval($this->valor($control, "dias_minimos_recepcion", 30)),
            "requiere_cantidad_variable_recepcion" => 0,
            "requiere_unidades_fisicas_recepcion" => 0,
            "tolerancia_recepcion_porcentaje" => null,
            "nota_recepcion_variable" => "",
            "permitir_recepcion_sin_lote" => intval($this->valor($control, "permitir_recepcion_sin_lote", 1)),
            "permitir_recepcion_caducada" => intval($this->valor($control, "permitir_recepcion_caducada", 0))
        );
    }

    private function evaluar_revision_caducidad($fecha_caducidad, $control) {
        $respuesta = array(
            "requiere_revision" => 0,
            "motivo_revision" => null,
            "severidad" => "media",
            "accion_sugerida" => null,
            "caducado" => false
        );

        if ($fecha_caducidad === "") {
            return $respuesta;
        }

        $hoy = new DateTime(date("Y-m-d"));
        $fecha = new DateTime($fecha_caducidad);
        $dias = intval($hoy->diff($fecha)->format("%r%a"));

        if ($dias < 0) {
            $respuesta["requiere_revision"] = 1;
            $respuesta["motivo_revision"] = "caducado";
            $respuesta["severidad"] = "alta";
            $respuesta["accion_sugerida"] = "rechazar_o_devolver";
            $respuesta["caducado"] = true;
            return $respuesta;
        }

        if ($dias <= intval($control["dias_minimos_recepcion"])) {
            $respuesta["requiere_revision"] = 1;
            $respuesta["motivo_revision"] = "muy_proximo_a_vencer";
            $respuesta["severidad"] = "alta";
            $respuesta["accion_sugerida"] = "validar_con_compras";
            return $respuesta;
        }

        if ($dias <= intval($control["dias_alerta_caducidad"])) {
            $respuesta["requiere_revision"] = 1;
            $respuesta["motivo_revision"] = "proximo_a_vencer";
            $respuesta["severidad"] = "media";
            $respuesta["accion_sugerida"] = "priorizar_salida";
        }

        return $respuesta;
    }

    private function obtener_o_crear_ubicacion($db, $id_almacen, $ubicacion) {
        if ($ubicacion === "") {
            return null;
        }

        $codigo = $this->normalizar_clave($ubicacion);
        $id_almacen_clave = intval($id_almacen);
        $stmt = $db->prepare("SELECT id_ubicacion FROM {$this->tabla_erp_almacen_ubicaciones} WHERE id_almacen_clave = :almacen AND codigo_ubicacion = :codigo LIMIT 1");
        $stmt->execute(array(":almacen" => $id_almacen_clave, ":codigo" => $codigo));
        $ubicacion_existente = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($ubicacion_existente) {
            return intval($ubicacion_existente["id_ubicacion"]);
        }

        $stmt = $db->prepare("INSERT INTO {$this->tabla_erp_almacen_ubicaciones} (id_almacen, id_almacen_clave, codigo_ubicacion, nombre, estatus) VALUES (:id_almacen, :id_almacen_clave, :codigo, :nombre, 'activa')");
        $stmt->execute(array(
            ":id_almacen" => $id_almacen ?: null,
            ":id_almacen_clave" => $id_almacen_clave,
            ":codigo" => $codigo,
            ":nombre" => $ubicacion
        ));
        return intval($db->lastInsertId());
    }

    private function insertar_recepcion_lote($db, $recepcion, $detalle, $control, $partida, $ubicacion_id, $revision) {
        $lote = trim($this->valor($partida, "lote", ""));
        $fecha_caducidad = trim($this->valor($partida, "fecha_caducidad", ""));
        $ubicacion = trim($this->valor($partida, "ubicacion", ""));
        $cantidad_compra = round(floatval($this->valor($partida, "_cantidad_compra", $this->valor($partida, "cantidad", 0))), 6);
        $cantidad_base = round(floatval($this->valor($partida, "_cantidad_base", $this->valor($partida, "cantidad", 0))), 6);
        $factor = round(floatval($this->valor($partida, "_factor_conversion", $this->valor($detalle, "factor_conversion", 1))), 6);
        $unidad_compra = $this->valor($partida, "_unidad_compra", $this->valor($detalle, "unidad_compra", $this->valor($detalle, "unidad", "")));
        $unidad_base = $this->valor($partida, "_unidad_base", $this->valor($detalle, "unidad_base", $this->valor($detalle, "unidad", "")));
        $costo_base = round(floatval($this->valor($partida, "_costo_unitario_base", $this->valor($detalle, "costo_unitario_base", 0))), 6);
        $stmt = $db->prepare("INSERT INTO {$this->tabla_erp_almacen_recepciones_lotes}
            (id_recepcion_almacen, id_recepcion_detalle, id_producto, id_sku_erp, id_almacen, lote, lote_clave, fecha_caducidad, fecha_caducidad_clave, ubicacion_id, ubicacion_clave, ubicacion, cantidad_compra, unidad_compra, cantidad_base, unidad_base, factor_conversion, estatus_lote, dias_alerta_caducidad, dias_minimos_recepcion, requiere_revision, motivo_revision, cantidad, costo_unitario, costo_unitario_base, observaciones)
            VALUES (:recepcion, :detalle, :producto, :sku_erp, :almacen, :lote, :lote_clave, :fecha_caducidad, :fecha_caducidad_clave, :ubicacion_id, :ubicacion_clave, :ubicacion, :cantidad_compra, :unidad_compra, :cantidad_base, :unidad_base, :factor_conversion, :estatus_lote, :dias_alerta, :dias_minimos, :requiere_revision, :motivo_revision, :cantidad, :costo_unitario, :costo_unitario_base, :observaciones)");
        $stmt->execute(array(
            ":recepcion" => intval($recepcion["id_recepcion_almacen"]),
            ":detalle" => intval($detalle["id_recepcion_detalle"]),
            ":producto" => intval($detalle["id_producto"]),
            ":sku_erp" => intval($detalle["id_sku_erp"]) ?: null,
            ":almacen" => intval($recepcion["id_almacen"]) ?: null,
            ":lote" => $lote !== "" ? $lote : null,
            ":lote_clave" => $this->normalizar_clave($lote),
            ":fecha_caducidad" => $fecha_caducidad !== "" ? $fecha_caducidad : null,
            ":fecha_caducidad_clave" => $fecha_caducidad !== "" ? $fecha_caducidad : "1000-01-01",
            ":ubicacion_id" => $ubicacion_id,
            ":ubicacion_clave" => $ubicacion_id ? intval($ubicacion_id) : 0,
            ":ubicacion" => $ubicacion !== "" ? $ubicacion : null,
            ":cantidad_compra" => $cantidad_compra,
            ":unidad_compra" => $unidad_compra !== "" ? $unidad_compra : null,
            ":cantidad_base" => $cantidad_base,
            ":unidad_base" => $unidad_base !== "" ? $unidad_base : null,
            ":factor_conversion" => $factor,
            ":estatus_lote" => $revision["requiere_revision"] ? "pendiente_revision" : "disponible",
            ":dias_alerta" => intval($control["dias_alerta_caducidad"]),
            ":dias_minimos" => intval($control["dias_minimos_recepcion"]),
            ":requiere_revision" => intval($revision["requiere_revision"]),
            ":motivo_revision" => $revision["motivo_revision"],
            ":cantidad" => $cantidad_base,
            ":costo_unitario" => $costo_base,
            ":costo_unitario_base" => $costo_base,
            ":observaciones" => trim($this->valor($partida, "observaciones", ""))
        ));

        $id_lote = intval($db->lastInsertId());
        $codigo = "LOT-" . intval($recepcion["id_recepcion_almacen"]) . "-" . $id_lote;
        $stmt = $db->prepare("UPDATE {$this->tabla_erp_almacen_recepciones_lotes} SET codigo_recepcion_lote = :codigo WHERE id_recepcion_lote = :id");
        $stmt->execute(array(":codigo" => $codigo, ":id" => $id_lote));
        return $id_lote;
    }

    private function actualizar_existencia($db, $recepcion, $detalle, $partida, $ubicacion_id, $id_recepcion_lote) {
        $id_producto = intval($detalle["id_producto"]);
        $id_sku_erp = intval($detalle["id_sku_erp"]);
        $id_almacen = intval($recepcion["id_almacen"]);
        $id_almacen_clave = $id_almacen;
        $lote = trim($this->valor($partida, "lote", ""));
        $fecha_caducidad = trim($this->valor($partida, "fecha_caducidad", ""));
        $ubicacion = trim($this->valor($partida, "ubicacion", ""));
        $cantidad = round(floatval($this->valor($partida, "_cantidad_base", $this->valor($partida, "cantidad", 0))), 6);
        $costo = round(floatval($this->valor($partida, "_costo_unitario_base", $this->valor($detalle, "costo_unitario_base", 0))), 6);

        $stmt = $db->prepare("SELECT * FROM {$this->tabla_erp_inventario_existencias}
            WHERE id_producto = :producto AND COALESCE(id_sku_erp, 0) = :sku_erp AND id_almacen_clave = :almacen AND lote_clave = :lote_clave AND fecha_caducidad_clave = :caducidad_clave AND ubicacion_clave = :ubicacion_clave FOR UPDATE");
        $stmt->execute(array(
            ":producto" => $id_producto,
            ":sku_erp" => $id_sku_erp,
            ":almacen" => $id_almacen_clave,
            ":lote_clave" => $this->normalizar_clave($lote),
            ":caducidad_clave" => $fecha_caducidad !== "" ? $fecha_caducidad : "1000-01-01",
            ":ubicacion_clave" => $ubicacion_id ? intval($ubicacion_id) : 0
        ));
        $existencia = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existencia) {
            $cantidad_anterior = floatval($existencia["cantidad"]);
            $cantidad_nueva = $cantidad_anterior + $cantidad;
            $costo_promedio = $cantidad_nueva > 0 ? (($cantidad_anterior * floatval($existencia["costo_promedio"])) + ($cantidad * $costo)) / $cantidad_nueva : $costo;
            $stmt = $db->prepare("UPDATE {$this->tabla_erp_inventario_existencias}
                SET cantidad = :cantidad, cantidad_disponible = cantidad_disponible + :recibida, costo_promedio = :costo_promedio, estatus_existencia = 'disponible', fecha_actualizacion = NOW()
                WHERE id_existencia_inventario = :id");
            $stmt->execute(array(
                ":cantidad" => $cantidad_nueva,
                ":recibida" => $cantidad,
                ":costo_promedio" => round($costo_promedio, 4),
                ":id" => intval($existencia["id_existencia_inventario"])
            ));
            return array(
                "id_existencia" => intval($existencia["id_existencia_inventario"]),
                "existencia_anterior" => round($cantidad_anterior, 6),
                "existencia_nueva" => round($cantidad_nueva, 6)
            );
        }

        $stmt = $db->prepare("INSERT INTO {$this->tabla_erp_inventario_existencias}
            (id_producto, id_sku_erp, id_almacen, id_almacen_clave, codigo_existencia, lote, lote_clave, fecha_caducidad, fecha_caducidad_clave, ubicacion_id, ubicacion_clave, ubicacion, cantidad, cantidad_apartada, cantidad_disponible, costo_promedio, estatus_existencia)
            VALUES (:producto, :sku_erp, :almacen, :almacen_clave, NULL, :lote, :lote_clave, :caducidad, :caducidad_clave, :ubicacion_id, :ubicacion_clave, :ubicacion, :cantidad, 0, :disponible, :costo, 'disponible')");
        $stmt->execute(array(
            ":producto" => $id_producto,
            ":sku_erp" => $id_sku_erp ?: null,
            ":almacen" => $id_almacen ?: null,
            ":almacen_clave" => $id_almacen_clave,
            ":lote" => $lote !== "" ? $lote : null,
            ":lote_clave" => $this->normalizar_clave($lote),
            ":caducidad" => $fecha_caducidad !== "" ? $fecha_caducidad : null,
            ":caducidad_clave" => $fecha_caducidad !== "" ? $fecha_caducidad : "1000-01-01",
            ":ubicacion_id" => $ubicacion_id,
            ":ubicacion_clave" => $ubicacion_id ? intval($ubicacion_id) : 0,
            ":ubicacion" => $ubicacion !== "" ? $ubicacion : null,
            ":cantidad" => $cantidad,
            ":disponible" => $cantidad,
            ":costo" => $costo
        ));

        $id_existencia = intval($db->lastInsertId());
        $codigo = "EXI-" . $id_producto . "-" . $id_existencia;
        $stmt = $db->prepare("UPDATE {$this->tabla_erp_inventario_existencias} SET codigo_existencia = :codigo WHERE id_existencia_inventario = :id");
        $stmt->execute(array(":codigo" => $codigo, ":id" => $id_existencia));
        return array(
            "id_existencia" => $id_existencia,
            "existencia_anterior" => 0,
            "existencia_nueva" => $cantidad
        );
    }

    private function insertar_movimiento_inventario($db, $recepcion, $detalle, $partida, $id_recepcion_lote, $existencia_movimiento, $ubicacion_id) {
        $cantidad = round(floatval($this->valor($partida, "_cantidad_base", $this->valor($partida, "cantidad", 0))), 6);
        $costo = round(floatval($this->valor($partida, "_costo_unitario_base", $this->valor($detalle, "costo_unitario_base", 0))), 6);
        $id_existencia = intval($this->valor($existencia_movimiento, "id_existencia", 0));
        $stmt = $db->prepare("INSERT INTO {$this->tabla_erp_inventario_movimientos}
            (id_producto, id_sku_erp, id_almacen, tipo_movimiento, origen_tipo, origen_id, origen_detalle_id, id_recepcion_lote, id_existencia_inventario, codigo_existencia, lote, fecha_caducidad, ubicacion_id, ubicacion, cantidad, costo_unitario, costo_total, existencia_anterior, existencia_nueva, referencia, observaciones)
            SELECT :producto, :sku_erp, :almacen, 'entrada', 'recepcion_compra', :origen, :detalle, :lote_id, :existencia_id, codigo_existencia, :lote, :caducidad, :ubicacion_id, :ubicacion, :cantidad, :costo, :total, :existencia_anterior, :existencia_nueva, :referencia, :observaciones
            FROM {$this->tabla_erp_inventario_existencias} WHERE id_existencia_inventario = :existencia_id_select");
        $stmt->execute(array(
            ":producto" => intval($detalle["id_producto"]),
            ":sku_erp" => intval($detalle["id_sku_erp"]) ?: null,
            ":almacen" => intval($recepcion["id_almacen"]) ?: null,
            ":origen" => intval($recepcion["id_recepcion_almacen"]),
            ":detalle" => intval($detalle["id_recepcion_detalle"]),
            ":lote_id" => intval($id_recepcion_lote),
            ":existencia_id" => intval($id_existencia),
            ":lote" => trim($this->valor($partida, "lote", "")) ?: null,
            ":caducidad" => trim($this->valor($partida, "fecha_caducidad", "")) ?: null,
            ":ubicacion_id" => $ubicacion_id,
            ":ubicacion" => trim($this->valor($partida, "ubicacion", "")) ?: null,
            ":cantidad" => $cantidad,
            ":costo" => $costo,
            ":total" => round($cantidad * $costo, 4),
            ":existencia_anterior" => round(floatval($this->valor($existencia_movimiento, "existencia_anterior", 0)), 6),
            ":existencia_nueva" => round(floatval($this->valor($existencia_movimiento, "existencia_nueva", 0)), 6),
            ":referencia" => $this->valor($recepcion, "folio", ""),
            ":observaciones" => trim($this->valor($partida, "observaciones", "")),
            ":existencia_id_select" => intval($id_existencia)
        ));
        return intval($db->lastInsertId());
    }

    private function actualizar_ultimo_movimiento_existencia($db, $id_existencia, $id_movimiento) {
        if (intval($id_existencia) <= 0 || intval($id_movimiento) <= 0) {
            return;
        }
        $stmt = $db->prepare("UPDATE {$this->tabla_erp_inventario_existencias}
            SET ultimo_movimiento_id = :movimiento, fecha_actualizacion = NOW()
            WHERE id_existencia_inventario = :existencia");
        $stmt->execute(array(
            ":movimiento" => intval($id_movimiento),
            ":existencia" => intval($id_existencia)
        ));
    }

    private function costo_unitario_inventario($db, $id_sku, $costo_compra) {
        $costo = round(floatval($costo_compra), 6);
        $id_sku = intval($id_sku);
        if ($id_sku <= 0 || $costo <= 0) {
            return round($costo, 4);
        }

        $stmt = $db->prepare("SELECT factor_unidad_base FROM erp_catalogo_skus WHERE id_sku=:sku LIMIT 1");
        $stmt->execute(array(":sku" => $id_sku));
        $sku = $stmt->fetch(PDO::FETCH_ASSOC);
        $factor = $sku ? floatval($sku["factor_unidad_base"]) : 1;
        if ($factor > 1) {
            $costo = $costo / $factor;
        }
        return round($costo, 4);
    }

    private function generar_unidades_inventario($db, $recepcion, $detalle, $control, $partida, $id_recepcion_lote, $id_existencia, $ubicacion_id) {
        $cantidad = intval(floatval($this->valor($partida, "cantidad", 0)));
        $cantidad_base_total = round(floatval($this->valor($partida, "_cantidad_base", $this->valor($partida, "cantidad", 0))), 6);
        $contenido_base = $cantidad > 0 ? round($cantidad_base_total / $cantidad, 6) : 0;
        if ($contenido_base <= 0) {
            $contenido_base = 1;
        }
        $unidad_base = $this->valor($partida, "_unidad_base", $this->valor($detalle, "unidad_base", $this->valor($detalle, "unidad", "")));
        $prefijo = $this->normalizar_clave($this->valor($control, "prefijo_codigo_unico", ""));
        if ($prefijo === "") {
            $prefijo = "INV";
        }

        $stmt = $db->prepare("INSERT INTO {$this->tabla_erp_inventario_unidades}
            (codigo_unico, tipo_identidad, codigo_etiqueta_interna, id_producto, id_sku_erp, id_recepcion_almacen, id_recepcion_lote, id_existencia_inventario, id_almacen, ubicacion_id, lote, fecha_caducidad, cantidad_base_original, cantidad_base_disponible, unidad_base, estatus, estado_etiqueta, estado_fisico, origen_tipo, origen_id, origen_detalle_id, observaciones)
            VALUES (:codigo, 'etiqueta_interna', :codigo_etiqueta, :producto, :sku_erp, :recepcion, :lote_id, :existencia, :almacen, :ubicacion_id, :lote, :caducidad, :cantidad_base_original, :cantidad_base_disponible, :unidad_base, 'disponible', 'pendiente_impresion', 'cerrada', 'recepcion_compra', :origen_id, :origen_detalle_id, :observaciones)");

        for ($i = 1; $i <= $cantidad; $i++) {
            $codigo = $prefijo . "-" . str_pad(intval($recepcion["id_recepcion_almacen"]), 5, "0", STR_PAD_LEFT) . "-" . intval($id_recepcion_lote) . "-" . str_pad($i, 4, "0", STR_PAD_LEFT);
            $stmt->execute(array(
                ":codigo" => $codigo,
                ":codigo_etiqueta" => $codigo,
                ":producto" => intval($detalle["id_producto"]),
                ":sku_erp" => intval($detalle["id_sku_erp"]) ?: null,
                ":recepcion" => intval($recepcion["id_recepcion_almacen"]),
                ":lote_id" => intval($id_recepcion_lote),
                ":existencia" => intval($id_existencia),
                ":almacen" => intval($recepcion["id_almacen"]) ?: null,
                ":ubicacion_id" => $ubicacion_id,
                ":lote" => trim($this->valor($partida, "lote", "")) ?: null,
                ":caducidad" => trim($this->valor($partida, "fecha_caducidad", "")) ?: null,
                ":cantidad_base_original" => $contenido_base,
                ":cantidad_base_disponible" => $contenido_base,
                ":unidad_base" => $unidad_base !== "" ? $unidad_base : null,
                ":origen_id" => intval($recepcion["id_recepcion_almacen"]),
                ":origen_detalle_id" => intval($detalle["id_recepcion_detalle"]),
                ":observaciones" => trim($this->valor($partida, "observaciones", ""))
            ));
        }

        return $cantidad;
    }

    private function insertar_incidencia_recepcion($db, $recepcion, $detalle, $partida, $tipo, $severidad, $accion_sugerida) {
        $stmt = $db->prepare("INSERT INTO {$this->tabla_erp_almacen_recepciones_incidencias}
            (id_recepcion_almacen, id_recepcion_detalle, id_producto, tipo_incidencia, severidad, cantidad, lote, fecha_caducidad, accion_sugerida, estatus, observaciones)
            VALUES (:recepcion, :detalle, :producto, :tipo, :severidad, :cantidad, :lote, :caducidad, :accion, 'pendiente', :observaciones)");
        $stmt->execute(array(
            ":recepcion" => intval($recepcion["id_recepcion_almacen"]),
            ":detalle" => intval($detalle["id_recepcion_detalle"]),
            ":producto" => intval($detalle["id_producto"]),
            ":tipo" => $tipo,
            ":severidad" => $severidad,
            ":cantidad" => round(floatval($this->valor($partida, "cantidad", 0)), 4),
            ":lote" => trim($this->valor($partida, "lote", "")) ?: null,
            ":caducidad" => trim($this->valor($partida, "fecha_caducidad", "")) ?: null,
            ":accion" => $accion_sugerida,
            ":observaciones" => trim($this->valor($partida, "observaciones", ""))
        ));
    }

    private function actualizar_detalle_recepcion($db, $id_recepcion_almacen, $id_detalle) {
        $stmt = $db->prepare("SELECT * FROM {$this->tabla_erp_almacen_recepciones_detalle}
            WHERE id_recepcion_almacen = :recepcion AND id_recepcion_detalle = :detalle FOR UPDATE");
        $stmt->execute(array(":recepcion" => intval($id_recepcion_almacen), ":detalle" => intval($id_detalle)));
        $detalle = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$detalle) {
            return 0;
        }

        $stmt = $db->prepare("SELECT
                COALESCE(SUM(CASE WHEN COALESCE(cantidad_compra, 0) > 0 THEN cantidad_compra ELSE cantidad END), 0) AS cantidad_lotes,
                COALESCE(SUM(CASE WHEN COALESCE(cantidad_base, 0) > 0 THEN cantidad_base ELSE cantidad END), 0) AS cantidad_base_lotes
            FROM {$this->tabla_erp_almacen_recepciones_lotes}
            WHERE id_recepcion_detalle = :detalle");
        $stmt->execute(array(":detalle" => intval($id_detalle)));
        $lotes = $stmt->fetch(PDO::FETCH_ASSOC);
        $recibida = round(floatval($lotes["cantidad_lotes"]), 4);
        $recibida_base = round(floatval($lotes["cantidad_base_lotes"]), 6);
        $ordenada = round(floatval($detalle["cantidad_ordenada"]), 4);
        $pendiente = max(0, $ordenada - $recibida);
        $ordenada_base = round(floatval($this->valor($detalle, "cantidad_ordenada_base", 0)), 6);
        if ($ordenada_base <= 0) {
            $ordenada_base = round($ordenada * floatval($this->valor($detalle, "factor_conversion", 1)), 6);
        }
        $pendiente_base = max(0, round($ordenada_base - $recibida_base, 6));
        $estatus = "pendiente";
        if ($recibida > $ordenada) {
            $estatus = "excedente";
        } else if ($pendiente == 0 && $recibida > 0) {
            $estatus = "recibida";
        } else if ($recibida > 0) {
            $estatus = "parcial";
        }

        $stmt = $db->prepare("UPDATE {$this->tabla_erp_almacen_recepciones_detalle}
            SET cantidad_recibida = :recibida, cantidad_recibida_base = :recibida_base, cantidad_pendiente = :pendiente, cantidad_pendiente_base = :pendiente_base, estatus = :estatus, fecha_actualizacion = NOW()
            WHERE id_recepcion_detalle = :detalle");
        $stmt->execute(array(
            ":recibida" => $recibida,
            ":recibida_base" => $recibida_base,
            ":pendiente" => $pendiente,
            ":pendiente_base" => $pendiente_base,
            ":estatus" => $estatus,
            ":detalle" => intval($id_detalle)
        ));

        if ($estatus === "excedente") {
            $partida = array("cantidad" => $recibida - $ordenada, "lote" => "", "fecha_caducidad" => "", "observaciones" => "Recepcion mayor a la cantidad ordenada");
            $recepcion = array("id_recepcion_almacen" => $id_recepcion_almacen);
            $this->insertar_incidencia_recepcion($db, $recepcion, $detalle, $partida, "excedente", "media", "validar_con_compras");
            return 1;
        }

        return 0;
    }

    private function sincronizar_detalle_orden_desde_recepcion($db, $id_recepcion_almacen, $id_detalle) {
        $stmt = $db->prepare("SELECT d.id_orden_compra_detalle, d.id_sku_erp, d.cantidad_recibida, d.costo_unitario
            FROM {$this->tabla_erp_almacen_recepciones_detalle} d
            WHERE d.id_recepcion_almacen = :recepcion AND d.id_recepcion_detalle = :detalle");
        $stmt->execute(array(
            ":recepcion" => intval($id_recepcion_almacen),
            ":detalle" => intval($id_detalle)
        ));
        $detalle = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$detalle) {
            return;
        }

        if (intval($detalle["id_orden_compra_detalle"]) > 0) {
            $stmt = $db->prepare("UPDATE {$this->tabla_erp_compras_ordenes_detalle}
                SET cantidad_recibida = :cantidad
                WHERE id_detalle = :detalle");
            $stmt->execute(array(
                ":cantidad" => round(floatval($detalle["cantidad_recibida"]), 2),
                ":detalle" => intval($detalle["id_orden_compra_detalle"])
            ));
        }
    }

    private function actualizar_estatus_recepcion($db, $id_recepcion_almacen, $id_usuario) {
        $stmt = $db->prepare("SELECT
            COUNT(*) AS total,
            SUM(CASE WHEN cantidad_recibida > 0 THEN 1 ELSE 0 END) AS con_recibido,
            SUM(CASE WHEN cantidad_pendiente > 0 THEN 1 ELSE 0 END) AS con_pendiente
            FROM {$this->tabla_erp_almacen_recepciones_detalle}
            WHERE id_recepcion_almacen = :recepcion");
        $stmt->execute(array(":recepcion" => intval($id_recepcion_almacen)));
        $resumen = $stmt->fetch(PDO::FETCH_ASSOC);

        $estatus = "pendiente";
        if (intval($resumen["con_recibido"]) > 0 && intval($resumen["con_pendiente"]) > 0) {
            $estatus = "parcial";
        } else if (intval($resumen["total"]) > 0 && intval($resumen["con_pendiente"]) === 0 && intval($resumen["con_recibido"]) > 0) {
            $estatus = "recibida";
        }

        $fecha_inicio = $estatus !== "pendiente" ? "COALESCE(fecha_inicio_recepcion, NOW())" : "fecha_inicio_recepcion";
        $fecha_cierre = $estatus === "recibida" ? "NOW()" : "fecha_cierre_recepcion";
        $stmt = $db->prepare("UPDATE {$this->tabla_erp_almacen_recepciones}
            SET estatus = :estatus, fecha_inicio_recepcion = {$fecha_inicio}, fecha_cierre_recepcion = {$fecha_cierre}, recibido_por = :usuario, fecha_actualizacion = NOW()
            WHERE id_recepcion_almacen = :recepcion");
        $stmt->execute(array(":estatus" => $estatus, ":usuario" => intval($id_usuario) ?: null, ":recepcion" => intval($id_recepcion_almacen)));
        return $estatus;
    }

    private function sincronizar_estatus_orden_desde_recepcion($db, $recepcion, $estatus_recepcion) {
        $id_orden_compra = intval($this->valor($recepcion, "id_orden_compra", 0));
        if ($id_orden_compra <= 0) {
            return "";
        }
        $estatus_orden = "";
        if ($estatus_recepcion === "parcial") {
            $estatus_orden = "parcial";
        } elseif ($estatus_recepcion === "recibida") {
            $estatus_orden = "recibida";
        }
        if ($estatus_orden === "") {
            return "";
        }
        $stmt = $db->prepare("UPDATE {$this->tabla_erp_compras_ordenes}
            SET estatus = :estatus, fecha_actualizacion = NOW()
            WHERE id_orden_compra = :orden AND estatus <> 'cancelada'");
        $stmt->execute(array(
            ":estatus" => $estatus_orden,
            ":orden" => $id_orden_compra
        ));
        return $estatus_orden;
    }

    private function cerrar_notificacion_recepcion_pendiente_orden($db, $id_orden_compra, $estatus_recepcion) {
        // [Codex: GPT-5 2026-06-16] Cierra alerta operativa cuando Almacen ya inicio o completo recepcion.
        if (intval($id_orden_compra) <= 0 || !in_array($estatus_recepcion, array("parcial", "recibida"), true)) {
            return;
        }
        try {
            $stmt = $db->prepare("UPDATE erp_notificaciones SET
                estatus='resuelta', fecha_resolucion=NOW(), fecha_actualizacion=NOW()
                WHERE tipo='compra_orden_enviada_recepcion_pendiente'
                  AND modulo_origen='compras'
                  AND entidad_origen='erp_compras_ordenes'
                  AND id_entidad_origen=:orden
                  AND estatus IN ('pendiente','en_revision','bloqueada')");
            $stmt->execute(array(":orden" => intval($id_orden_compra)));
        } catch (Exception $e) {
            return;
        }
    }

    private function normalizar_clave($valor) {
        $valor = strtoupper(trim((string) $valor));
        $valor = preg_replace("/[^A-Z0-9]+/", "-", $valor);
        $valor = trim($valor, "-");
        return $valor;
    }

    private function validar_almacen_preparacion($db, $id_almacen) {
        $stmt = $db->prepare("SELECT id_almacen, permite_preparacion FROM {$this->tabla_erp_almacenes}
            WHERE id_almacen=:almacen AND COALESCE(estatus,'activo')='activo' LIMIT 1");
        $stmt->execute(array(":almacen" => intval($id_almacen)));
        $almacen = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$almacen) {
            throw new Exception("Almacen no encontrado o inactivo");
        }
        if (intval(isset($almacen["permite_preparacion"]) ? $almacen["permite_preparacion"] : 0) !== 1) {
            throw new Exception("El almacen seleccionado no permite preparacion/empaque");
        }
    }

    private function validar_almacen_recepcion($db, $id_almacen) {
        $stmt = $db->prepare("SELECT id_almacen, permite_recepcion FROM {$this->tabla_erp_almacenes}
            WHERE id_almacen=:almacen AND COALESCE(estatus,'activo')='activo' LIMIT 1");
        $stmt->execute(array(":almacen" => intval($id_almacen)));
        $almacen = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$almacen) {
            throw new Exception("Almacen de recepcion no encontrado o inactivo");
        }
        if (intval(isset($almacen["permite_recepcion"]) ? $almacen["permite_recepcion"] : 0) !== 1) {
            throw new Exception("El almacen seleccionado no permite recepcion de compras");
        }
    }

    private function consultar_regla_preparacion($db, $id_regla) {
        $stmt = $db->prepare("SELECT pr.id_sku_presentacion_regla, pr.id_sku_base, pr.id_sku_presentacion,
                pr.factor_salida_base, pr.modo_disponibilidad, pr.consume_stock_base_en,
                pr.merma_porcentaje, pr.estatus, base.estatus AS estatus_base,
                pres.estatus AS estatus_presentacion, prod.estatus AS estatus_producto
            FROM erp_catalogo_sku_presentaciones pr
            INNER JOIN erp_catalogo_skus base ON base.id_sku=pr.id_sku_base
            INNER JOIN erp_catalogo_skus pres ON pres.id_sku=pr.id_sku_presentacion
            INNER JOIN erp_catalogo_productos prod ON prod.id_producto_erp=base.id_producto_erp
            WHERE pr.id_sku_presentacion_regla=:regla LIMIT 1");
        $stmt->execute(array(":regla" => intval($id_regla)));
        $regla = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$regla) {
            throw new Exception("Presentacion no encontrada");
        }
        if (trim($regla["estatus"]) !== "activa") {
            throw new Exception("La presentacion no esta activa");
        }
        if (trim($regla["consume_stock_base_en"]) !== "preparacion") {
            throw new Exception("La presentacion no esta configurada para preparacion");
        }
        if (!in_array(trim($regla["modo_disponibilidad"]), array("preparada", "mixta"), true)) {
            throw new Exception("La presentacion no permite stock preparado");
        }
        if (trim($regla["estatus_base"]) !== "activo" || trim($regla["estatus_presentacion"]) !== "activo" || trim($regla["estatus_producto"]) !== "activo") {
            throw new Exception("El producto o alguno de sus SKU no esta activo");
        }
        if (floatval($regla["factor_salida_base"]) <= 0) {
            throw new Exception("El factor de salida base debe ser mayor a cero");
        }
        return $regla;
    }

    private function consultar_transformacion_preparacion($db, $id_transformacion) {
        $stmt = $db->prepare("SELECT tr.id_sku_transformacion, tr.id_sku_origen AS id_sku_base,
                tr.id_sku_resultado AS id_sku_presentacion, tr.cantidad_origen, tr.unidades_resultado,
                ROUND(tr.cantidad_origen / NULLIF(tr.unidades_resultado,0), 6) AS factor_salida_base,
                tr.tipo_transformacion, tr.modo_disponibilidad, tr.merma_porcentaje, tr.estatus,
                base.estatus AS estatus_base, pres.estatus AS estatus_presentacion,
                prod.estatus AS estatus_producto
            FROM {$this->tabla_erp_catalogo_sku_transformaciones} tr
            INNER JOIN erp_catalogo_skus base ON base.id_sku=tr.id_sku_origen
            INNER JOIN erp_catalogo_skus pres ON pres.id_sku=tr.id_sku_resultado
            INNER JOIN erp_catalogo_productos prod ON prod.id_producto_erp=base.id_producto_erp
            WHERE tr.id_sku_transformacion=:transformacion LIMIT 1");
        $stmt->execute(array(":transformacion" => intval($id_transformacion)));
        $regla = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$regla) {
            throw new Exception("Transformacion no encontrada");
        }
        if (trim($regla["estatus"]) !== "activa") {
            throw new Exception("La transformacion no esta activa");
        }
        if (!in_array(trim($regla["modo_disponibilidad"]), array("preparada", "mixta"), true)) {
            throw new Exception("La transformacion no permite stock preparado");
        }
        if (trim($regla["estatus_base"]) !== "activo" || trim($regla["estatus_presentacion"]) !== "activo" || trim($regla["estatus_producto"]) !== "activo") {
            throw new Exception("El SKU origen, resultado o producto no esta activo");
        }
        if (floatval($regla["cantidad_origen"]) <= 0 || intval($regla["unidades_resultado"]) <= 0) {
            throw new Exception("La transformacion debe tener cantidad origen y unidades resultado mayores a cero");
        }
        return $regla;
    }

    private function calcular_cantidad_base_preparacion($unidades, $regla) {
        $factor = floatval($this->valor($regla, "factor_salida_base", 0));
        $merma = floatval($this->valor($regla, "merma_porcentaje", 0));
        return round(floatval($unidades) * $factor * (1 + ($merma / 100)), 6);
    }

    private function generar_folio_preparacion($db) {
        $prefijo = "PREP-" . date("Ymd") . "-";
        $stmt = $db->prepare("SELECT folio FROM {$this->tabla_erp_almacen_preparaciones}
            WHERE folio LIKE :prefijo ORDER BY folio DESC LIMIT 1");
        $stmt->execute(array(":prefijo" => $prefijo . "%"));
        $ultimo = $stmt->fetch(PDO::FETCH_ASSOC);
        $consecutivo = 1;
        if ($ultimo && preg_match('/-(\d{4})$/', $ultimo["folio"], $m)) {
            $consecutivo = intval($m[1]) + 1;
        }
        return $prefijo . str_pad((string) $consecutivo, 4, "0", STR_PAD_LEFT);
    }

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-06-25
     * Proposito: adjunta etiquetas/unidades fisicas disponibles a cada existencia origen de preparacion.
     * Impacto: Almacen/Preparacion; habilita seleccion operativa por unidad real sin crear stock teorico.
     * Contrato: solo devuelve unidades disponibles, con contenido base mayor a cero y estado fisico utilizable.
     */
    private function adjuntar_unidades_fisicas_preparacion($db, $existencias) {
        if (!is_array($existencias) || count($existencias) === 0) {
            return array();
        }
        $ids = array();
        foreach ($existencias as $existencia) {
            $id = intval($this->valor($existencia, "id_existencia_inventario", 0));
            if ($id > 0) {
                $ids[] = $id;
            }
        }
        if (count($ids) === 0) {
            return $existencias;
        }

        $placeholders = array();
        $params = array();
        foreach ($ids as $idx => $id) {
            $key = ":existencia_" . $idx;
            $placeholders[] = $key;
            $params[$key] = $id;
        }
        $sql = "SELECT id_inventario_unidad, codigo_unico, codigo_etiqueta_interna, id_existencia_inventario,
                id_sku_erp, id_almacen, lote, fecha_caducidad, cantidad_base_original,
                cantidad_base_disponible, unidad_base, estatus, estado_etiqueta, estado_fisico
            FROM {$this->tabla_erp_inventario_unidades}
            WHERE id_existencia_inventario IN (" . implode(",", $placeholders) . ")
              AND estatus='disponible'
              AND cantidad_base_disponible > 0
              AND estado_fisico IN ('cerrada','abierta')
            ORDER BY CASE WHEN fecha_caducidad IS NULL THEN 1 ELSE 0 END,
              fecha_caducidad, fecha_registro, id_inventario_unidad";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $por_existencia = array();
        while ($unidad = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $id_existencia = intval($unidad["id_existencia_inventario"]);
            if (!isset($por_existencia[$id_existencia])) {
                $por_existencia[$id_existencia] = array();
            }
            $por_existencia[$id_existencia][] = $unidad;
        }

        foreach ($existencias as $idx => $existencia) {
            $id_existencia = intval($this->valor($existencia, "id_existencia_inventario", 0));
            $existencias[$idx]["unidades_fisicas"] = isset($por_existencia[$id_existencia]) ? $por_existencia[$id_existencia] : array();
            $existencias[$idx]["requiere_unidad_fisica"] = count($existencias[$idx]["unidades_fisicas"]) > 0 ? 1 : 0;
        }
        return $existencias;
    }

    private function seleccionar_existencia_base_preparacion($db, $id_sku_base, $id_almacen, $cantidad_base) {
        $stmt = $db->prepare("SELECT * FROM {$this->tabla_erp_inventario_existencias}
            WHERE id_sku_erp=:sku AND id_almacen_clave=:almacen AND estatus_existencia='disponible'
              AND cantidad_disponible >= :cantidad
            ORDER BY CASE WHEN fecha_caducidad IS NULL THEN 1 ELSE 0 END, fecha_caducidad, fecha_registro, id_existencia_inventario
            LIMIT 1 FOR UPDATE");
        $stmt->execute(array(":sku" => intval($id_sku_base), ":almacen" => intval($id_almacen), ":cantidad" => $cantidad_base));
        $existencia = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$existencia) {
            throw new Exception("Existencia insuficiente del SKU base en un solo lote/ubicacion para confirmar la preparacion");
        }
        return $existencia;
    }

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-06-25
     * Proposito: valida si una existencia tiene unidades fisicas trazables disponibles.
     * Impacto: Almacen/Preparacion; decide si el operador debe elegir etiqueta/unidad antes de preparar.
     */
    private function existencia_tiene_unidades_preparacion($db, $id_existencia) {
        $stmt = $db->prepare("SELECT COUNT(*) AS total
            FROM {$this->tabla_erp_inventario_unidades}
            WHERE id_existencia_inventario=:existencia
              AND estatus='disponible'
              AND cantidad_base_disponible > 0
              AND estado_fisico IN ('cerrada','abierta')");
        $stmt->execute(array(":existencia" => intval($id_existencia)));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return intval($this->valor($row, "total", 0)) > 0;
    }

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-06-25
     * Proposito: bloquea o valida la unidad fisica exacta que se usara como origen de preparacion.
     * Impacto: Almacen/Preparacion; impide consumir una etiqueta que no pertenece a la existencia/SKU/almacen o no tiene saldo.
     * Contrato: devuelve la unidad si cubre `cantidad_base`; lanza excepcion si ya no esta disponible.
     */
    private function seleccionar_unidad_origen_preparacion($db, $id_unidad, $id_existencia, $id_sku_base, $id_almacen, $cantidad_base, $bloquear = true) {
        $sql = "SELECT *
            FROM {$this->tabla_erp_inventario_unidades}
            WHERE id_inventario_unidad=:unidad
              AND id_existencia_inventario=:existencia
              AND id_sku_erp=:sku
              AND id_almacen=:almacen
              AND estatus='disponible'
              AND estado_fisico IN ('cerrada','abierta')
              AND cantidad_base_disponible >= :cantidad";
        if ($bloquear) {
            $sql .= " FOR UPDATE";
        }
        $stmt = $db->prepare($sql);
        $stmt->execute(array(
            ":unidad" => intval($id_unidad),
            ":existencia" => intval($id_existencia),
            ":sku" => intval($id_sku_base),
            ":almacen" => intval($id_almacen),
            ":cantidad" => round(floatval($cantidad_base), 6)
        ));
        $unidad = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$unidad) {
            throw new Exception("La unidad fisica origen seleccionada no tiene saldo suficiente o ya no esta disponible");
        }
        return $unidad;
    }

    private function seleccionar_existencia_origen_preparacion($db, $id_existencia, $id_sku_base, $id_almacen, $cantidad_base, $bloquear = true) {
        $sql = "SELECT * FROM {$this->tabla_erp_inventario_existencias}
            WHERE id_existencia_inventario=:existencia
              AND id_sku_erp=:sku
              AND id_almacen_clave=:almacen
              AND estatus_existencia='disponible'
              AND cantidad_disponible >= :cantidad";
        if ($bloquear) {
            $sql .= " FOR UPDATE";
        }
        $stmt = $db->prepare($sql);
        $stmt->execute(array(
            ":existencia" => intval($id_existencia),
            ":sku" => intval($id_sku_base),
            ":almacen" => intval($id_almacen),
            ":cantidad" => $cantidad_base
        ));
        $existencia = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$existencia) {
            throw new Exception("La existencia origen seleccionada no tiene stock suficiente o ya no esta disponible");
        }
        return $existencia;
    }

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-06-25
     * Proposito: registra consumo de preparacion con snapshot de unidad fisica cuando existe.
     * Impacto: Almacen/Preparacion e Inventario; conserva evidencia antes/despues de la etiqueta usada.
     * Contrato: no descuenta la unidad; solo registra el consumo. El descuento fisico ocurre despues del movimiento de salida.
     */
    private function registrar_consumo_preparacion($db, $preparacion, $existencia_base, $cantidad_base, $unidad_origen = null) {
        $costo = round(floatval($existencia_base["costo_promedio"]), 4);
        $total = round($cantidad_base * $costo, 4);
        $cantidad_unidad_antes = $unidad_origen ? round(floatval($unidad_origen["cantidad_base_disponible"]), 6) : 0;
        $cantidad_unidad_despues = $unidad_origen ? max(0, round($cantidad_unidad_antes - floatval($cantidad_base), 6)) : 0;
        $estado_unidad_despues = null;
        if ($unidad_origen) {
            $estado_unidad_despues = $cantidad_unidad_despues <= 0 ? "consumida" : "abierta";
        }
        $stmt = $db->prepare("INSERT INTO {$this->tabla_erp_almacen_preparacion_consumos}
            (id_preparacion_almacen, id_existencia_inventario, id_inventario_unidad, cantidad_unidad_antes, cantidad_unidad_despues, estado_unidad_despues, id_sku_base, id_almacen, ubicacion_id, lote, fecha_caducidad, cantidad_consumida, costo_unitario, costo_total, fecha_registro)
            VALUES (:preparacion, :existencia, :unidad, :unidad_antes, :unidad_despues, :estado_unidad, :sku, :almacen, :ubicacion, :lote, :caducidad, :cantidad, :costo, :total, NOW())");
        $stmt->execute(array(
            ":preparacion" => intval($preparacion["id_preparacion_almacen"]),
            ":existencia" => intval($existencia_base["id_existencia_inventario"]),
            ":unidad" => $unidad_origen ? intval($unidad_origen["id_inventario_unidad"]) : null,
            ":unidad_antes" => $cantidad_unidad_antes,
            ":unidad_despues" => $cantidad_unidad_despues,
            ":estado_unidad" => $estado_unidad_despues,
            ":sku" => intval($preparacion["id_sku_base"]),
            ":almacen" => intval($preparacion["id_almacen"]),
            ":ubicacion" => intval($existencia_base["ubicacion_id"]) ?: null,
            ":lote" => $existencia_base["lote"],
            ":caducidad" => $existencia_base["fecha_caducidad"],
            ":cantidad" => $cantidad_base,
            ":costo" => $costo,
            ":total" => $total
        ));
        return array(
            "id_preparacion_consumo" => intval($db->lastInsertId()),
            "costo_unitario" => $costo,
            "costo_total" => $total,
            "id_inventario_unidad" => $unidad_origen ? intval($unidad_origen["id_inventario_unidad"]) : 0,
            "cantidad_unidad_antes" => $cantidad_unidad_antes,
            "cantidad_unidad_despues" => $cantidad_unidad_despues,
            "estado_unidad_despues" => $estado_unidad_despues
        );
    }

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-06-25
     * Proposito: descuenta contenido de la unidad fisica origen y la enlaza al consumo/kardex.
     * Impacto: Inventario; permite rastrear que etiqueta/unidad fue abierta o consumida por una preparacion.
     * Contrato: se ejecuta dentro de la misma transaccion despues de crear movimiento de salida.
     */
    private function actualizar_unidad_origen_preparacion($db, $unidad_origen, $consumo, $id_movimiento_salida) {
        $id_unidad = intval($this->valor($unidad_origen, "id_inventario_unidad", 0));
        if ($id_unidad <= 0) {
            return;
        }
        $cantidad_despues = round(floatval($this->valor($consumo, "cantidad_unidad_despues", 0)), 6);
        $estado = trim((string) $this->valor($consumo, "estado_unidad_despues", ""));
        if ($estado === "") {
            $estado = $cantidad_despues <= 0 ? "consumida" : "abierta";
        }
        $estatus = $cantidad_despues <= 0 ? "consumida" : "disponible";
        $stmt = $db->prepare("UPDATE {$this->tabla_erp_inventario_unidades}
            SET cantidad_base_disponible=:cantidad_disponible,
                estado_fisico=:estado_fisico,
                estatus=:estatus,
                id_preparacion_consumo=:consumo,
                id_movimiento_consumo=:movimiento,
                fecha_consumo=NOW(),
                fecha_actualizacion=NOW()
            WHERE id_inventario_unidad=:unidad");
        $stmt->execute(array(
            ":cantidad_disponible" => $cantidad_despues,
            ":estado_fisico" => $estado,
            ":estatus" => $estatus,
            ":consumo" => intval($consumo["id_preparacion_consumo"]),
            ":movimiento" => intval($id_movimiento_salida),
            ":unidad" => $id_unidad
        ));
    }

    private function aplicar_salida_preparacion($db, $preparacion, $existencia_base, $consumo, $cantidad_base) {
        $anterior = round(floatval($existencia_base["cantidad"]), 6);
        $nueva = round($anterior - $cantidad_base, 6);
        $nueva_disponible = round(floatval($existencia_base["cantidad_disponible"]) - $cantidad_base, 6);
        $estatus = $nueva_disponible <= 0 ? "agotada" : "disponible";
        $stmt = $db->prepare("UPDATE {$this->tabla_erp_inventario_existencias}
            SET cantidad=:cantidad, cantidad_disponible=:disponible, estatus_existencia=:estatus, fecha_actualizacion=NOW()
            WHERE id_existencia_inventario=:existencia");
        $stmt->execute(array(
            ":cantidad" => $nueva,
            ":disponible" => $nueva_disponible,
            ":estatus" => $estatus,
            ":existencia" => intval($existencia_base["id_existencia_inventario"])
        ));

        $stmt = $db->prepare("INSERT INTO {$this->tabla_erp_inventario_movimientos}
            (id_producto, id_sku_erp, id_almacen, tipo_movimiento, origen_tipo, origen_id, origen_detalle_id, id_recepcion_lote, id_existencia_inventario, codigo_existencia, lote, fecha_caducidad, ubicacion_id, ubicacion, cantidad, costo_unitario, costo_total, existencia_anterior, existencia_nueva, referencia, observaciones)
            VALUES (:producto, :sku, :almacen, 'salida', 'preparacion_presentacion', :origen, :detalle, NULL, :existencia, :codigo, :lote, :caducidad, :ubicacion_id, :ubicacion, :cantidad, :costo, :total, :anterior, :nueva, :referencia, :observaciones)");
        $stmt->execute(array(
            ":producto" => intval($existencia_base["id_producto"]),
            ":sku" => intval($preparacion["id_sku_base"]),
            ":almacen" => intval($preparacion["id_almacen"]),
            ":origen" => intval($preparacion["id_preparacion_almacen"]),
            ":detalle" => intval($consumo["id_preparacion_consumo"]),
            ":existencia" => intval($existencia_base["id_existencia_inventario"]),
            ":codigo" => $existencia_base["codigo_existencia"],
            ":lote" => $existencia_base["lote"],
            ":caducidad" => $existencia_base["fecha_caducidad"],
            ":ubicacion_id" => intval($existencia_base["ubicacion_id"]) ?: null,
            ":ubicacion" => $existencia_base["ubicacion"],
            ":cantidad" => round($cantidad_base, 4),
            ":costo" => floatval($consumo["costo_unitario"]),
            ":total" => floatval($consumo["costo_total"]),
            ":anterior" => round($anterior, 4),
            ":nueva" => round($nueva, 4),
            ":referencia" => $preparacion["folio"],
            ":observaciones" => "Consumo base por preparacion de presentacion"
        ));
        return intval($db->lastInsertId());
    }

    private function obtener_o_crear_existencia_presentacion_preparacion($db, $preparacion, $existencia_base, $unidades, $costo_total) {
        $stmt = $db->prepare("SELECT s.id_producto_erp FROM erp_catalogo_skus s WHERE s.id_sku=:sku LIMIT 1");
        $stmt->execute(array(":sku" => intval($preparacion["id_sku_presentacion"])));
        $sku = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$sku) {
            throw new Exception("SKU presentacion no encontrado");
        }

        $id_producto = intval($sku["id_producto_erp"]);
        $lote_clave = $this->normalizar_clave($existencia_base["lote"]);
        $caducidad_clave = trim((string) $existencia_base["fecha_caducidad"]) !== "" ? $existencia_base["fecha_caducidad"] : "1000-01-01";
        $ubicacion_clave = intval($existencia_base["ubicacion_id"]) ?: 0;
        $costo_unitario = $unidades > 0 ? round($costo_total / $unidades, 4) : 0;

        $stmt = $db->prepare("SELECT * FROM {$this->tabla_erp_inventario_existencias}
            WHERE id_producto=:producto AND COALESCE(id_sku_erp,0)=:sku AND id_almacen_clave=:almacen
              AND lote_clave=:lote_clave AND fecha_caducidad_clave=:caducidad_clave AND ubicacion_clave=:ubicacion_clave
            FOR UPDATE");
        $stmt->execute(array(
            ":producto" => $id_producto,
            ":sku" => intval($preparacion["id_sku_presentacion"]),
            ":almacen" => intval($preparacion["id_almacen"]),
            ":lote_clave" => $lote_clave,
            ":caducidad_clave" => $caducidad_clave,
            ":ubicacion_clave" => $ubicacion_clave
        ));
        $existencia = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($existencia) {
            $anterior = floatval($existencia["cantidad"]);
            $nueva = $anterior + $unidades;
            $costo_promedio = $nueva > 0 ? (($anterior * floatval($existencia["costo_promedio"])) + $costo_total) / $nueva : $costo_unitario;
            $stmt = $db->prepare("UPDATE {$this->tabla_erp_inventario_existencias}
                SET cantidad=:cantidad, cantidad_disponible=cantidad_disponible + :entrada, costo_promedio=:costo, estatus_existencia='disponible', fecha_actualizacion=NOW()
                WHERE id_existencia_inventario=:existencia");
            $stmt->execute(array(":cantidad" => $nueva, ":entrada" => $unidades, ":costo" => round($costo_promedio, 4), ":existencia" => intval($existencia["id_existencia_inventario"])));
            $existencia["existencia_anterior"] = round($anterior, 4);
            $existencia["existencia_nueva"] = round($nueva, 4);
            $existencia["costo_unitario_preparacion"] = $costo_unitario;
            return $existencia;
        }

        $stmt = $db->prepare("INSERT INTO {$this->tabla_erp_inventario_existencias}
            (id_producto, id_sku_erp, id_almacen, id_almacen_clave, codigo_existencia, lote, lote_clave, fecha_caducidad, fecha_caducidad_clave, ubicacion_id, ubicacion_clave, ubicacion, cantidad, cantidad_apartada, cantidad_disponible, costo_promedio, estatus_existencia)
            VALUES (:producto, :sku, :almacen, :almacen_clave, NULL, :lote, :lote_clave, :caducidad, :caducidad_clave, :ubicacion_id, :ubicacion_clave, :ubicacion, :cantidad, 0, :disponible, :costo, 'disponible')");
        $stmt->execute(array(
            ":producto" => $id_producto,
            ":sku" => intval($preparacion["id_sku_presentacion"]),
            ":almacen" => intval($preparacion["id_almacen"]),
            ":almacen_clave" => intval($preparacion["id_almacen"]),
            ":lote" => $existencia_base["lote"],
            ":lote_clave" => $lote_clave,
            ":caducidad" => $existencia_base["fecha_caducidad"],
            ":caducidad_clave" => $caducidad_clave,
            ":ubicacion_id" => intval($existencia_base["ubicacion_id"]) ?: null,
            ":ubicacion_clave" => $ubicacion_clave,
            ":ubicacion" => $existencia_base["ubicacion"],
            ":cantidad" => $unidades,
            ":disponible" => $unidades,
            ":costo" => $costo_unitario
        ));
        $id_existencia = intval($db->lastInsertId());
        $codigo = "EXI-" . $id_producto . "-" . $id_existencia;
        $stmt = $db->prepare("UPDATE {$this->tabla_erp_inventario_existencias} SET codigo_existencia=:codigo WHERE id_existencia_inventario=:existencia");
        $stmt->execute(array(":codigo" => $codigo, ":existencia" => $id_existencia));

        $stmt = $db->prepare("SELECT * FROM {$this->tabla_erp_inventario_existencias} WHERE id_existencia_inventario=:existencia");
        $stmt->execute(array(":existencia" => $id_existencia));
        $existencia = $stmt->fetch(PDO::FETCH_ASSOC);
        $existencia["existencia_anterior"] = 0;
        $existencia["existencia_nueva"] = $unidades;
        $existencia["costo_unitario_preparacion"] = $costo_unitario;
        return $existencia;
    }

    private function registrar_resultado_preparacion($db, $preparacion, $consumo, $existencia_presentacion, $regla, $unidades) {
        $stmt = $db->prepare("INSERT INTO {$this->tabla_erp_almacen_preparacion_resultados}
            (id_preparacion_almacen, id_preparacion_consumo, id_existencia_inventario, id_sku_presentacion, id_almacen, ubicacion_id, lote, fecha_caducidad, unidades_preparadas, factor_salida_base, cantidad_base_equivalente, costo_unitario, costo_total, genera_etiquetas, etiquetas_generadas, fecha_registro)
            VALUES (:preparacion, :consumo, :existencia, :sku, :almacen, :ubicacion, :lote, :caducidad, :unidades, :factor, :base, :costo, :total, 0, 0, NOW())");
        $stmt->execute(array(
            ":preparacion" => intval($preparacion["id_preparacion_almacen"]),
            ":consumo" => intval($consumo["id_preparacion_consumo"]),
            ":existencia" => intval($existencia_presentacion["id_existencia_inventario"]),
            ":sku" => intval($preparacion["id_sku_presentacion"]),
            ":almacen" => intval($preparacion["id_almacen"]),
            ":ubicacion" => intval($existencia_presentacion["ubicacion_id"]) ?: null,
            ":lote" => $existencia_presentacion["lote"],
            ":caducidad" => $existencia_presentacion["fecha_caducidad"],
            ":unidades" => $unidades,
            ":factor" => floatval($regla["factor_salida_base"]),
            ":base" => floatval($preparacion["cantidad_base_consumida"]),
            ":costo" => floatval($existencia_presentacion["costo_unitario_preparacion"]),
            ":total" => round(floatval($existencia_presentacion["costo_unitario_preparacion"]) * $unidades, 4)
        ));
        return array("id_preparacion_resultado" => intval($db->lastInsertId()));
    }

    private function aplicar_entrada_preparacion($db, $preparacion, $existencia_presentacion, $resultado, $unidades) {
        $costo = floatval($existencia_presentacion["costo_unitario_preparacion"]);
        $stmt = $db->prepare("INSERT INTO {$this->tabla_erp_inventario_movimientos}
            (id_producto, id_sku_erp, id_almacen, tipo_movimiento, origen_tipo, origen_id, origen_detalle_id, id_recepcion_lote, id_existencia_inventario, codigo_existencia, lote, fecha_caducidad, ubicacion_id, ubicacion, cantidad, costo_unitario, costo_total, existencia_anterior, existencia_nueva, referencia, observaciones)
            VALUES (:producto, :sku, :almacen, 'entrada', 'preparacion_presentacion', :origen, :detalle, NULL, :existencia, :codigo, :lote, :caducidad, :ubicacion_id, :ubicacion, :cantidad, :costo, :total, :anterior, :nueva, :referencia, :observaciones)");
        $stmt->execute(array(
            ":producto" => intval($existencia_presentacion["id_producto"]),
            ":sku" => intval($preparacion["id_sku_presentacion"]),
            ":almacen" => intval($preparacion["id_almacen"]),
            ":origen" => intval($preparacion["id_preparacion_almacen"]),
            ":detalle" => intval($resultado["id_preparacion_resultado"]),
            ":existencia" => intval($existencia_presentacion["id_existencia_inventario"]),
            ":codigo" => $existencia_presentacion["codigo_existencia"],
            ":lote" => $existencia_presentacion["lote"],
            ":caducidad" => $existencia_presentacion["fecha_caducidad"],
            ":ubicacion_id" => intval($existencia_presentacion["ubicacion_id"]) ?: null,
            ":ubicacion" => $existencia_presentacion["ubicacion"],
            ":cantidad" => $unidades,
            ":costo" => $costo,
            ":total" => round($costo * $unidades, 4),
            ":anterior" => floatval($existencia_presentacion["existencia_anterior"]),
            ":nueva" => floatval($existencia_presentacion["existencia_nueva"]),
            ":referencia" => $preparacion["folio"],
            ":observaciones" => "Entrada de presentacion preparada"
        ));
        return intval($db->lastInsertId());
    }

    private function generar_unidades_preparacion($db, $preparacion, $resultado, $existencia_presentacion, $control, $unidades) {
        $prefijo = $this->normalizar_clave($this->valor($control, "prefijo_codigo_unico", ""));
        if ($prefijo === "") {
            $prefijo = "PREP";
        }
        $contenido_base = 1;
        $unidad_base = "pza";
        $stmt = $db->prepare("INSERT INTO {$this->tabla_erp_inventario_unidades}
            (codigo_unico, tipo_identidad, codigo_etiqueta_interna, id_producto, id_sku_erp, id_recepcion_almacen, id_recepcion_lote, id_existencia_inventario, id_almacen, ubicacion_id, lote, fecha_caducidad, cantidad_base_original, cantidad_base_disponible, unidad_base, estatus, estado_etiqueta, estado_fisico, origen_tipo, origen_id, origen_detalle_id, observaciones)
            VALUES (:codigo, 'etiqueta_interna', :codigo_etiqueta, :producto, :sku, NULL, NULL, :existencia, :almacen, :ubicacion, :lote, :caducidad, :cantidad_base_original, :cantidad_base_disponible, :unidad_base, 'disponible', 'pendiente_impresion', 'cerrada', 'preparacion_presentacion', :origen, :detalle, :observaciones)");
        $generadas = 0;
        for ($i = 1; $i <= intval($unidades); $i++) {
            $codigo = $prefijo . "-P" . str_pad((string) intval($preparacion["id_preparacion_almacen"]), 6, "0", STR_PAD_LEFT) . "-" . str_pad((string) $i, 4, "0", STR_PAD_LEFT);
            $stmt->execute(array(
                ":codigo" => $codigo,
                ":codigo_etiqueta" => $codigo,
                ":producto" => intval($existencia_presentacion["id_producto"]),
                ":sku" => intval($preparacion["id_sku_presentacion"]),
                ":existencia" => intval($existencia_presentacion["id_existencia_inventario"]),
                ":almacen" => intval($preparacion["id_almacen"]),
                ":ubicacion" => intval($existencia_presentacion["ubicacion_id"]) ?: null,
                ":lote" => $existencia_presentacion["lote"],
                ":caducidad" => $existencia_presentacion["fecha_caducidad"],
                ":cantidad_base_original" => $contenido_base,
                ":cantidad_base_disponible" => $contenido_base,
                ":unidad_base" => $unidad_base,
                ":origen" => intval($preparacion["id_preparacion_almacen"]),
                ":detalle" => intval($resultado["id_preparacion_resultado"]),
                ":observaciones" => "Etiqueta generada por preparacion " . $preparacion["folio"]
            ));
            $generadas++;
        }
        return $generadas;
    }

    private function valor($array, $key, $default = "") {
        return isset($array[$key]) ? $array[$key] : $default;
    }
}
