<?php

class OperacionMiniInventarioErp extends CRUD {

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-09-28
     * Proposito: cargar catalogos para Mini inventario operativo sin escribir BD.
     * Impacto: Operacion/Mini inventario; ayuda a filtrar por tienda, proveedor y categoria.
     * Contrato: read-only; si una tabla opcional no existe devuelve listas vacias en vez de fallar.
     */
    public function catalogos() {
        try {
            $db = $this->getConexion();
            if (!$db) {
                return $this->respuesta(true, "danger", "Conexion de BD no disponible");
            }

            $whereAlmacenes = $this->whereEstatusOperativo($db, "erp_almacenes", array("activo"));
            $whereProveedores = $this->whereEstatusOperativo($db, "erp_proveedores", array("activo", "alta"));
            $whereCategorias = $this->whereEstatusOperativo($db, "erp_catalogo_categorias", array("activa", "activo"));

            $almacenes = $this->tablaExiste($db, "erp_almacenes")
                ? $db->query("SELECT id_almacen, codigo_almacen, almacen, tipo_almacen FROM erp_almacenes {$whereAlmacenes} ORDER BY almacen")->fetchAll(PDO::FETCH_ASSOC)
                : array();
            $proveedores = $this->tablaExiste($db, "erp_proveedores")
                ? $db->query("SELECT id_proveedor, proveedor FROM erp_proveedores {$whereProveedores} ORDER BY proveedor")->fetchAll(PDO::FETCH_ASSOC)
                : array();
            $categorias = $this->tablaExiste($db, "erp_catalogo_categorias")
                ? $db->query("SELECT id_categoria_erp, COALESCE(NULLIF(ruta,''), nombre) AS categoria FROM erp_catalogo_categorias {$whereCategorias} ORDER BY COALESCE(NULLIF(ruta,''), nombre) LIMIT 500")->fetchAll(PDO::FETCH_ASSOC)
                : array();

            return $this->respuesta(false, "success", "Catalogos consultados", array(
                "sin_escrituras" => true,
                "almacenes" => $almacenes,
                "proveedores" => $proveedores,
                "categorias" => $categorias
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-09-28
     * Proposito: listar SKUs de venta para captura operativa temporal de stock listo/tareas.
     * Impacto: Operacion/Mini inventario; no usa kardex como verdad obligatoria ni modifica inventario.
     * Contrato: read-only; expone reglas min/max, proveedor preferido y relaciones de preparacion/apertura cuando existen.
     */
    public function productosCatalogoVenta($filtros = array()) {
        try {
            $db = $this->getConexion();
            if (!$db) {
                return $this->respuesta(true, "danger", "Conexion de BD no disponible");
            }

            $q = trim((string) $this->valor($filtros, "q", ""));
            $idProveedor = intval($this->valor($filtros, "id_proveedor", 0));
            $idCategoria = intval($this->valor($filtros, "id_categoria_erp", 0));
            $idAlmacen = intval($this->valor($filtros, "id_almacen", 0));
            $tipo = trim((string) $this->valor($filtros, "tipo", ""));
            $limite = max(1, min(800, intval($this->valor($filtros, "limite", 300))));

            $tienePresentaciones = $this->tablaExiste($db, "erp_catalogo_sku_presentaciones");
            $tieneAperturas = $this->tablaExiste($db, "erp_catalogo_sku_aperturas_empaque");
            $tieneInventario = $this->tablaExiste($db, "erp_inventario_existencias");
            $tieneCodigos = $this->tablaExiste($db, "erp_catalogo_sku_codigos");

            $selectPresentacion = $tienePresentaciones
                ? "pres.id_sku_presentacion_regla, pres.id_sku_base, base.sku AS sku_origen_presentacion, base.nombre AS nombre_origen_presentacion, pres.factor_salida_base"
                : "NULL AS id_sku_presentacion_regla, NULL AS id_sku_base, NULL AS sku_origen_presentacion, NULL AS nombre_origen_presentacion, NULL AS factor_salida_base";
            $joinPresentacion = $tienePresentaciones
                ? "LEFT JOIN erp_catalogo_sku_presentaciones pres ON pres.id_sku_presentacion=s.id_sku AND pres.estatus IN ('activo','activa')
                   LEFT JOIN erp_catalogo_skus base ON base.id_sku=pres.id_sku_base"
                : "";

            $selectApertura = $tieneAperturas
                ? "ape.id_apertura_empaque, ape.id_sku_origen AS id_sku_origen_apertura, origen.sku AS sku_origen_apertura, origen.nombre AS nombre_origen_apertura, ape.factor_conversion AS factor_apertura"
                : "NULL AS id_apertura_empaque, NULL AS id_sku_origen_apertura, NULL AS sku_origen_apertura, NULL AS nombre_origen_apertura, NULL AS factor_apertura";
            $joinApertura = $tieneAperturas
                ? "LEFT JOIN erp_catalogo_sku_aperturas_empaque ape ON ape.id_sku_destino=s.id_sku AND ape.estatus IN ('activo','activa')
                   LEFT JOIN erp_catalogo_skus origen ON origen.id_sku=ape.id_sku_origen"
                : "";

            $selectExistencia = $tieneInventario && $idAlmacen > 0
                ? "(SELECT COALESCE(SUM(ex.cantidad_disponible),0) FROM erp_inventario_existencias ex WHERE ex.id_sku_erp=s.id_sku AND ex.id_almacen_clave=:almacen) AS existencia_sistema"
                : "0 AS existencia_sistema";

            $where = array("s.estatus='activo'", "p.estatus='activo'");
            $params = array();
            if ($idAlmacen > 0 && $tieneInventario) {
                $params[":almacen"] = $idAlmacen;
            }
            if ($idProveedor > 0) {
                $where[] = "EXISTS (SELECT 1 FROM erp_catalogo_sku_proveedores spf WHERE spf.id_sku=s.id_sku AND spf.id_proveedor=:proveedor AND spf.estatus='activo')";
                $params[":proveedor"] = $idProveedor;
            }
            if ($idCategoria > 0) {
                $where[] = "EXISTS (SELECT 1 FROM erp_catalogo_producto_categorias pcf WHERE pcf.id_producto_erp=p.id_producto_erp AND pcf.id_categoria_erp=:categoria)";
                $params[":categoria"] = $idCategoria;
            }
            if ($q !== "") {
                $busqueda = "(s.sku LIKE :q OR s.nombre LIKE :q OR p.nombre LIKE :q OR p.codigo_producto LIKE :q";
                if ($tieneCodigos) {
                    $busqueda .= " OR EXISTS (SELECT 1 FROM erp_catalogo_sku_codigos cod WHERE cod.id_sku=s.id_sku AND cod.estatus='activo' AND cod.codigo LIKE :q)";
                }
                $busqueda .= ")";
                $where[] = $busqueda;
                $params[":q"] = "%" . $q . "%";
            }

            if ($tipo === "reempacar" && $tienePresentaciones) {
                $where[] = "pres.id_sku_presentacion_regla IS NOT NULL";
            } elseif ($tipo === "abrir_empaque" && $tieneAperturas) {
                $where[] = "ape.id_apertura_empaque IS NOT NULL";
            } elseif ($tipo === "etiquetar") {
                $where[] = "COALESCE(r.generar_etiqueta_interna,0)=1";
            }

            $sql = "SELECT s.id_sku, s.sku, s.nombre AS nombre_sku, s.id_producto_erp,
                    p.nombre AS producto, p.codigo_producto, m.nombre AS marca,
                    cat.id_categoria_erp, COALESCE(cat.ruta, cat.nombre) AS categoria,
                    COALESCE(NULLIF(r.unidad_venta_label,''), ub.abreviatura, ub.codigo, '') AS unidad_venta,
                    COALESCE(r.stock_minimo,0) AS stock_minimo, r.stock_maximo,
                    COALESCE(r.punto_reorden,0) AS punto_reorden,
                    COALESCE(r.generar_etiqueta_interna,0) AS generar_etiqueta_interna,
                    COALESCE(r.permite_venta_fraccionaria,0) AS permite_venta_fraccionaria,
                    sp.id_proveedor, prov.proveedor, sp.sku_proveedor, sp.factor_conversion,
                    {$selectPresentacion}, {$selectApertura}, {$selectExistencia},
                    COALESCE(
                        (SELECT img_sku.url_imagen FROM erp_catalogo_imagenes img_sku
                         WHERE img_sku.id_sku=s.id_sku AND img_sku.estatus='activo' AND TRIM(COALESCE(img_sku.url_imagen,''))<>''
                         ORDER BY FIELD(img_sku.tipo_imagen, 'portada', 'empaque', 'detalle', 'galeria', 'referencia'), img_sku.orden ASC, img_sku.id_imagen_erp ASC LIMIT 1),
                        (SELECT img_prod.url_imagen FROM erp_catalogo_imagenes img_prod
                         WHERE img_prod.id_producto_erp=p.id_producto_erp AND (img_prod.id_sku IS NULL OR img_prod.id_sku=0) AND img_prod.estatus='activo' AND TRIM(COALESCE(img_prod.url_imagen,''))<>''
                         ORDER BY FIELD(img_prod.tipo_imagen, 'portada', 'empaque', 'detalle', 'galeria', 'referencia'), img_prod.orden ASC, img_prod.id_imagen_erp ASC LIMIT 1)
                    ) AS imagen_portada
                FROM erp_catalogo_skus s
                INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
                LEFT JOIN erp_catalogo_marcas m ON m.id_marca_erp=p.id_marca_erp
                LEFT JOIN erp_catalogo_producto_categorias pc ON pc.id_producto_erp=p.id_producto_erp AND pc.es_principal=1
                LEFT JOIN erp_catalogo_categorias cat ON cat.id_categoria_erp=pc.id_categoria_erp
                LEFT JOIN erp_catalogo_unidades ub ON ub.id_unidad=s.id_unidad_base
                LEFT JOIN erp_catalogo_sku_reglas_inventario r ON r.id_sku=s.id_sku
                LEFT JOIN erp_catalogo_sku_proveedores sp ON sp.id_sku=s.id_sku AND sp.estatus='activo' AND sp.es_preferido=1
                LEFT JOIN erp_proveedores prov ON prov.id_proveedor=sp.id_proveedor
                {$joinPresentacion}
                {$joinApertura}
                WHERE " . implode(" AND ", $where) . "
                ORDER BY p.nombre ASC, s.nombre ASC, s.sku ASC
                LIMIT :limite";

            $stmt = $db->prepare($sql);
            foreach ($params as $clave => $valor) {
                $stmt->bindValue($clave, $valor);
            }
            $stmt->bindValue(":limite", $limite, PDO::PARAM_INT);
            $stmt->execute();

            $items = array();
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
                $items[] = $this->normalizarProducto($fila);
            }

            return $this->respuesta(false, "success", "Productos de venta consultados", array(
                "sin_escrituras" => true,
                "items" => $items,
                "total" => count($items),
                "schema" => array(
                    "presentaciones" => $tienePresentaciones,
                    "aperturas_empaque" => $tieneAperturas,
                    "inventario_leido" => $tieneInventario && $idAlmacen > 0
                )
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    private function normalizarProducto($fila) {
        $accion = "revisar";
        $origen = "";
        $factor = null;
        if (!empty($fila["id_sku_presentacion_regla"])) {
            $accion = "reempacar";
            $origen = trim((string) $fila["sku_origen_presentacion"]);
            $factor = $fila["factor_salida_base"];
        } elseif (!empty($fila["id_apertura_empaque"])) {
            $accion = "abrir_empaque";
            $origen = trim((string) $fila["sku_origen_apertura"]);
            $factor = $fila["factor_apertura"];
        } elseif (intval($this->valor($fila, "generar_etiqueta_interna", 0)) === 1) {
            $accion = "etiquetar";
        }

        return array(
            "id_sku" => intval($fila["id_sku"]),
            "sku" => $fila["sku"],
            "nombre_sku" => $fila["nombre_sku"],
            "producto" => $fila["producto"],
            "marca" => $fila["marca"],
            "categoria" => $fila["categoria"],
            "id_categoria_erp" => intval($this->valor($fila, "id_categoria_erp", 0)),
            "unidad_venta" => $fila["unidad_venta"],
            "stock_minimo" => floatval($this->valor($fila, "stock_minimo", 0)),
            "stock_maximo" => $fila["stock_maximo"] === null ? null : floatval($fila["stock_maximo"]),
            "punto_reorden" => floatval($this->valor($fila, "punto_reorden", 0)),
            "existencia_sistema" => floatval($this->valor($fila, "existencia_sistema", 0)),
            "proveedor" => $fila["proveedor"],
            "id_proveedor" => intval($this->valor($fila, "id_proveedor", 0)),
            "sku_proveedor" => $fila["sku_proveedor"],
            "factor_conversion" => $fila["factor_conversion"] === null ? null : floatval($fila["factor_conversion"]),
            "imagen_portada" => $fila["imagen_portada"],
            "accion_sugerida" => $accion,
            "sku_origen" => $origen,
            "factor_operativo" => $factor === null ? null : floatval($factor),
            "generar_etiqueta_interna" => intval($this->valor($fila, "generar_etiqueta_interna", 0)),
            "permite_venta_fraccionaria" => intval($this->valor($fila, "permite_venta_fraccionaria", 0))
        );
    }

    private function tablaExiste($db, $tabla) {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', (string) $tabla)) {
            return false;
        }
        $stmt = $db->prepare("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=:tabla LIMIT 1");
        $stmt->execute(array(":tabla" => $tabla));
        return (bool) $stmt->fetchColumn();
    }

    private function columnaExiste($db, $tabla, $columna) {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', (string) $tabla) || !preg_match('/^[a-zA-Z0-9_]+$/', (string) $columna)) {
            return false;
        }
        $stmt = $db->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=:tabla AND COLUMN_NAME=:columna LIMIT 1");
        $stmt->execute(array(":tabla" => $tabla, ":columna" => $columna));
        return (bool) $stmt->fetchColumn();
    }

    private function whereEstatusOperativo($db, $tabla, $valoresTexto) {
        if (!$this->tablaExiste($db, $tabla)) {
            return "";
        }
        if ($this->columnaExiste($db, $tabla, "estatus")) {
            return "WHERE estatus IN (" . $this->listaSqlTexto($valoresTexto) . ")";
        }
        if ($this->columnaExiste($db, $tabla, "estado")) {
            return "WHERE estado IN (" . $this->listaSqlTexto($valoresTexto) . ")";
        }
        if ($this->columnaExiste($db, $tabla, "activo")) {
            return "WHERE activo IN (1,'1','activo','si','sí')";
        }
        return "";
    }

    private function listaSqlTexto($valores) {
        $seguros = array();
        foreach ((array) $valores as $valor) {
            $seguros[] = "'" . str_replace("'", "''", (string) $valor) . "'";
        }
        return implode(",", $seguros);
    }

    private function valor($datos, $campo, $default = null) {
        return is_array($datos) && array_key_exists($campo, $datos) ? $datos[$campo] : $default;
    }

    private function respuesta($error, $tipo, $mensaje, $depurar = array()) {
        return array("error" => $error, "tipo" => $tipo, "mensaje" => $mensaje, "depurar" => $depurar);
    }
}
