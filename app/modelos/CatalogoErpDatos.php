<?php

class CatalogoErpDatos extends CRUD {

  public function listarProductos() {
    try {
      $db = $this->getConexion();
      $sql = "SELECT p.id_producto_erp,
                     p.codigo_producto,
                     p.nombre,
                     p.tipo_producto,
                     p.estatus,
                     m.nombre AS marca,
                     pc.id_categoria_erp,
                     COALESCE(c.ruta, c.nombre) AS categoria,
                     (
                       SELECT i.url_imagen
                       FROM erp_catalogo_imagenes i
                       WHERE i.id_producto_erp = p.id_producto_erp
                         AND i.estatus = 'activo'
                       ORDER BY
                         FIELD(i.tipo_imagen, 'portada', 'empaque', 'detalle', 'galeria', 'referencia'),
                         CASE WHEN i.id_sku IS NULL THEN 0 ELSE 1 END,
                         i.orden ASC,
                         i.id_imagen_erp ASC
                       LIMIT 1
                     ) AS url_imagen,
                     COUNT(s.id_sku) AS total_skus,
                     SUM(CASE WHEN s.id_sku IS NOT NULL AND s.estatus NOT IN ('inactivo','descontinuado','fusionado') THEN 1 ELSE 0 END) AS total_skus_vigentes,
                     SUM(CASE WHEN s.id_sku IS NOT NULL AND s.estatus IN ('inactivo','descontinuado','fusionado') THEN 1 ELSE 0 END) AS total_skus_archivados,
                     SUM(CASE WHEN s.id_sku IS NOT NULL AND s.estatus NOT IN ('inactivo','descontinuado','fusionado')
                       AND NOT EXISTS (SELECT 1 FROM erp_catalogo_sku_proveedores sp WHERE sp.id_sku=s.id_sku AND sp.estatus='activo')
                       THEN 1 ELSE 0 END) AS skus_sin_proveedor_activo,
                     SUM(CASE WHEN s.id_sku IS NOT NULL AND s.estatus NOT IN ('inactivo','descontinuado','fusionado')
                       AND (SELECT COUNT(*) FROM erp_catalogo_sku_proveedores sp WHERE sp.id_sku=s.id_sku AND sp.estatus='activo') > 1
                       THEN 1 ELSE 0 END) AS skus_multiples_proveedores,
                     SUM(CASE WHEN s.id_sku IS NOT NULL AND s.estatus NOT IN ('inactivo','descontinuado','fusionado')
                       AND EXISTS (SELECT 1 FROM erp_catalogo_sku_proveedores sp WHERE sp.id_sku=s.id_sku AND sp.estatus='activo')
                       AND NOT EXISTS (SELECT 1 FROM erp_catalogo_sku_proveedores sp WHERE sp.id_sku=s.id_sku AND sp.estatus='activo' AND sp.es_preferido=1)
                       THEN 1 ELSE 0 END) AS skus_proveedor_sin_preferido,
                     GROUP_CONCAT(s.sku ORDER BY s.id_sku SEPARATOR ', ') AS skus,
                     GROUP_CONCAT(CASE WHEN s.estatus IN ('inactivo','descontinuado','fusionado') THEN NULL ELSE s.sku END ORDER BY s.id_sku SEPARATOR ', ') AS skus_vigentes,
                     GROUP_CONCAT(CASE WHEN s.estatus IN ('inactivo','descontinuado','fusionado') THEN s.sku ELSE NULL END ORDER BY s.id_sku SEPARATOR ', ') AS skus_archivados
              FROM erp_catalogo_productos p
              LEFT JOIN erp_catalogo_marcas m ON m.id_marca_erp = p.id_marca_erp
              LEFT JOIN erp_catalogo_producto_categorias pc ON pc.id_producto_erp = p.id_producto_erp AND pc.es_principal = 1
              LEFT JOIN erp_catalogo_categorias c ON c.id_categoria_erp = pc.id_categoria_erp
              LEFT JOIN erp_catalogo_skus s ON s.id_producto_erp = p.id_producto_erp
              GROUP BY p.id_producto_erp, p.codigo_producto, p.nombre, p.tipo_producto, p.estatus, m.nombre, pc.id_categoria_erp, c.ruta, c.nombre
              ORDER BY p.id_producto_erp DESC";
      $stmt = $db->prepare($sql);
      $stmt->execute();
      return $this->respuesta(false, "success", "Productos ERP consultados", $stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  public function listarIncidenciasMigracionEcommerce() {
    try {
      $db = $this->getConexion();
      $incidencias = $db->query("SELECT id_incidencia, id_producto_ecom, id_variante_ecom, sku, nombre_producto, motivo, detalle_json, estatus, fecha_registro, fecha_actualizacion
        FROM erp_catalogo_migracion_ecom_incidencias
        ORDER BY FIELD(estatus, 'pendiente', 'resuelta', 'descartada'), motivo, id_variante_ecom, nombre_producto")->fetchAll(PDO::FETCH_ASSOC);
      $resumen = $db->query("SELECT
        COUNT(*) total,
        SUM(estatus='pendiente') pendientes,
        SUM(estatus='resuelta') resueltas,
        SUM(estatus='descartada') descartadas,
        SUM(motivo='sku_invalido' AND estatus='pendiente') sku_invalidos,
        SUM(motivo IN ('sku_duplicado', 'sku_duplicado_productivo') AND estatus='pendiente') sku_duplicados,
        SUM(motivo='sku_duplicado_productivo' AND estatus='pendiente') sku_duplicados_productivos,
        SUM(motivo='grupo_variante_ambiguo' AND estatus='pendiente') grupos_ambiguos
        FROM erp_catalogo_migracion_ecom_incidencias")->fetch(PDO::FETCH_ASSOC);
      return $this->respuesta(false, "success", "Incidencias de migracion consultadas", array("resumen" => $resumen, "incidencias" => $incidencias));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage(), array());
    }
  }

  public function catalogosFormulario() {
    try {
      $db = $this->getConexion();
      return $this->respuesta(false, "success", "Catalogos consultados", array(
        "unidades" => $db->query("SELECT id_unidad, codigo, nombre, abreviatura, decimales_permitidos FROM erp_catalogo_unidades WHERE estatus = 'activa' ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC),
        "marcas" => $db->query("SELECT id_marca_erp, codigo, nombre FROM erp_catalogo_marcas WHERE estatus = 'activa' ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC),
        "categorias" => $db->query("SELECT id_categoria_erp, codigo, nombre, ruta FROM erp_catalogo_categorias
          WHERE estatus='activa' AND tipo_categoria='maestra' AND permite_productos=1
          ORDER BY COALESCE(ruta, nombre), nombre")->fetchAll(PDO::FETCH_ASSOC),
        "proveedores" => $db->query("SELECT id_proveedor, proveedor FROM erp_proveedores ORDER BY proveedor")->fetchAll(PDO::FETCH_ASSOC),
        "atributos" => $db->query("SELECT id_atributo_erp, codigo, nombre, tipo_dato, unidad, configuracion_json, es_variante FROM erp_catalogo_atributos WHERE estatus='activo' ORDER BY es_variante DESC, nombre")->fetchAll(PDO::FETCH_ASSOC)
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  public function listarCatalogosAdministrativos() {
    try {
      $db = $this->getConexion();
      $imagenesDisponibles = $this->esquemaImagenesCatalogoMaestroDisponible($db);
      $marcas = $db->query("SELECT id_marca_erp, codigo, nombre, descripcion, estatus FROM erp_catalogo_marcas ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
      $categorias = $db->query("SELECT c.id_categoria_erp, c.id_categoria_padre, c.codigo, c.nombre, c.descripcion, c.ruta, c.nivel,
          c.tipo_categoria, c.origen, c.permite_productos, c.estatus,
          p.nombre AS categoria_padre,
          (SELECT COUNT(*) FROM erp_catalogo_categorias h WHERE h.id_categoria_padre=c.id_categoria_erp) total_hijas,
          (SELECT COUNT(*) FROM erp_catalogo_producto_categorias pc WHERE pc.id_categoria_erp=c.id_categoria_erp) total_productos
          FROM erp_catalogo_categorias c
          LEFT JOIN erp_catalogo_categorias p ON p.id_categoria_erp = c.id_categoria_padre
          ORDER BY COALESCE(c.ruta, c.nombre), c.nombre")->fetchAll(PDO::FETCH_ASSOC);
      if ($imagenesDisponibles) {
        $this->agregarResumenImagenesMarcasCategorias($db, $marcas, $categorias);
      }
      return $this->respuesta(false, "success", "Catálogos auxiliares consultados", array(
        "schema" => array("imagenes_marcas_categorias" => $imagenesDisponibles),
        "marcas" => $marcas,
        "categorias" => $categorias,
        "unidades" => $db->query("SELECT id_unidad, codigo, nombre, abreviatura, tipo_magnitud, decimales_permitidos, clave_sat, estatus FROM erp_catalogo_unidades ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC),
        "atributos" => $db->query("SELECT id_atributo_erp, codigo, nombre, tipo_dato, unidad, configuracion_json, es_variante, estatus FROM erp_catalogo_atributos ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC)
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  public function prepararArbolCategoriasMaestro() {
    $db = $this->getConexion();
    try {
      $db->beginTransaction();
      $db->exec("UPDATE erp_catalogo_categorias
        SET tipo_categoria='legado_canal', origen='ecommerce', permite_productos=1,
          fecha_actualizacion=CURRENT_TIMESTAMP
        WHERE codigo LIKE 'ECOM-CAT-%'");

      $arbol = array(
        array("CAT-ALIM", "Alimentacion", array(
          array("CAT-ALIM-ALIMENTOS", "Alimentos"),
          array("CAT-ALIM-PREMIOS", "Premios y snacks"),
          array("CAT-ALIM-COMEDEROS", "Comederos y bebederos")
        )),
        array("CAT-HAB", "Habitat y descanso", array(
          array("CAT-HAB-JAULAS", "Jaulas, corrales y vallas"),
          array("CAT-HAB-REFUGIOS", "Camas, casas y refugios"),
          array("CAT-HAB-ACUARIOS", "Acuarios y peceras"),
          array("CAT-HAB-TERRARIOS", "Terrarios y tortugueros")
        )),
        array("CAT-SALUD", "Salud e higiene", array(
          array("CAT-SALUD-HIGIENE", "Higiene y limpieza"),
          array("CAT-SALUD-PREVENCION", "Prevencion y cuidado")
        )),
        array("CAT-JUEGO", "Juego y enriquecimiento", array(
          array("CAT-JUEGO-JUGUETES", "Juguetes"),
          array("CAT-JUEGO-RASCADORES", "Rascadores"),
          array("CAT-JUEGO-AMBIENTACION", "Decoracion y enriquecimiento")
        )),
        array("CAT-TRANS", "Transporte, paseo y entrenamiento", array(
          array("CAT-TRANS-TRANSPORTADORAS", "Transportadoras y mochilas"),
          array("CAT-TRANS-SUJECION", "Paseo y sujecion"),
          array("CAT-TRANS-ENTRENAMIENTO", "Entrenamiento")
        )),
        array("CAT-EQUIP", "Equipamiento tecnico", array(
          array("CAT-EQUIP-FILTRACION", "Filtracion y oxigenacion"),
          array("CAT-EQUIP-CALEFACCION", "Calefaccion"),
          array("CAT-EQUIP-ILUMINACION", "Iluminacion"),
          array("CAT-EQUIP-BOMBAS", "Bombas y circulacion")
        )),
        array("CAT-REP", "Repuestos y accesorios", array(
          array("CAT-REP-REPUESTOS", "Repuestos"),
          array("CAT-REP-ACCESORIOS", "Accesorios generales")
        )),
        array("CAT-VIVOS", "Animales y plantas vivas", array(
          array("CAT-VIVOS-PECES", "Peces"),
          array("CAT-VIVOS-PLANTAS", "Plantas acuaticas")
        ))
      );
      $stmt = $db->prepare("INSERT INTO erp_catalogo_categorias
        (id_categoria_padre, codigo, nombre, descripcion, ruta, nivel, tipo_categoria, origen, permite_productos, estatus)
        VALUES (:padre, :codigo, :nombre, :descripcion, :ruta, :nivel, 'maestra', 'erp', :permite, 'activa')
        ON DUPLICATE KEY UPDATE id_categoria_erp=LAST_INSERT_ID(id_categoria_erp),
          id_categoria_padre=VALUES(id_categoria_padre), nombre=VALUES(nombre), descripcion=VALUES(descripcion),
          ruta=VALUES(ruta), nivel=VALUES(nivel), tipo_categoria='maestra', origen='erp',
          permite_productos=VALUES(permite_productos), estatus='activa', fecha_actualizacion=CURRENT_TIMESTAMP");
      $raices = 0;
      $hojas = 0;
      foreach ($arbol as $grupo) {
        $stmt->execute(array(
          ":padre" => null, ":codigo" => $grupo[0], ":nombre" => $grupo[1],
          ":descripcion" => "Familia estructural del catalogo maestro ERP",
          ":ruta" => $grupo[1], ":nivel" => 0, ":permite" => 0
        ));
        $idPadre = intval($db->lastInsertId());
        $raices++;
        foreach ($grupo[2] as $hija) {
          $stmt->execute(array(
            ":padre" => $idPadre, ":codigo" => $hija[0], ":nombre" => $hija[1],
            ":descripcion" => "Categoria operativa del catalogo maestro ERP",
            ":ruta" => $grupo[1] . " / " . $hija[1], ":nivel" => 1, ":permite" => 1
          ));
          $hojas++;
        }
      }
      $db->commit();
      return $this->respuesta(false, "success", "Arbol maestro de categorias preparado", array(
        "familias" => $raices,
        "categorias_operativas" => $hojas,
        "categorias_legado" => intval($db->query("SELECT COUNT(*) FROM erp_catalogo_categorias WHERE tipo_categoria='legado_canal'")->fetchColumn())
      ));
    } catch (Exception $e) {
      if ($db->inTransaction()) {
        $db->rollBack();
      }
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  public function sincronizarRelacionesCategoriasMaestras($idUsuario = 0) {
    $db = $this->getConexion();
    $mapa = array(
      "CAT-HAB-ACUARIOS" => array(4, 14, 108, 110),
      "CAT-EQUIP-FILTRACION" => array(5),
      "CAT-EQUIP-CALEFACCION" => array(6, 16),
      "CAT-EQUIP-ILUMINACION" => array(7),
      "CAT-JUEGO-AMBIENTACION" => array(8, 18),
      "CAT-ALIM-ALIMENTOS" => array(9, 15, 20, 22, 34, 35, 75, 79, 80, 81, 82, 83, 86, 88, 89, 93, 99, 104, 105),
      "CAT-REP-ACCESORIOS" => array(10, 18, 32, 96, 111, 113),
      "CAT-REP-REPUESTOS" => array(11),
      "CAT-HAB-TERRARIOS" => array(12, 17),
      "CAT-VIVOS-PLANTAS" => array(13),
      "CAT-HAB-JAULAS" => array(19, 21, 24, 31, 33, 65, 87, 96, 106, 107),
      "CAT-ALIM-COMEDEROS" => array(20, 22, 25, 35, 90, 93, 104, 105),
      "CAT-TRANS-TRANSPORTADORAS" => array(23, 28, 91, 116),
      "CAT-SALUD-HIGIENE" => array(26, 29, 36, 72, 92, 101, 102, 103),
      "CAT-HAB-REFUGIOS" => array(27, 37, 64, 71, 87),
      "CAT-JUEGO-JUGUETES" => array(30, 73, 76),
      "CAT-ALIM-PREMIOS" => array(99, 112),
      "CAT-EQUIP-BOMBAS" => array(94),
      "CAT-SALUD-PREVENCION" => array(95),
      "CAT-VIVOS-PECES" => array(100),
      "CAT-JUEGO-RASCADORES" => array(109),
      "CAT-TRANS-SUJECION" => array(114, 115),
      "CAT-TRANS-ENTRENAMIENTO" => array(114, 115)
    );
    try {
      $db->beginTransaction();
      $destinos = array();
      $stmt = $db->prepare("SELECT id_categoria_erp FROM erp_catalogo_categorias
        WHERE codigo=:codigo AND tipo_categoria='maestra' AND permite_productos=1 AND estatus='activa'");
      foreach (array_keys($mapa) as $codigo) {
        $stmt->execute(array(":codigo" => $codigo));
        $destinos[$codigo] = intval($stmt->fetchColumn());
        if ($destinos[$codigo] <= 0) {
          throw new Exception("Falta la categoria maestra " . $codigo);
        }
      }

      $stmtOrigen = $db->prepare("SELECT id_categoria_erp FROM erp_catalogo_categorias
        WHERE codigo=:codigo AND tipo_categoria='legado_canal' LIMIT 1");
      $stmtEquivalencia = $db->prepare("INSERT INTO erp_catalogo_categoria_equivalencias
        (id_categoria_origen, id_categoria_destino, tipo, confianza, estatus, observaciones, creado_por)
        VALUES (:origen, :destino, 'migracion', 100.00, 'aplicada',
          'Relacion automatica basada en ecom_categorias, clasificaciones y relaciones historicas de productos', :usuario)
        ON DUPLICATE KEY UPDATE confianza=VALUES(confianza), estatus='aplicada',
          observaciones=VALUES(observaciones), creado_por=VALUES(creado_por), fecha_actualizacion=CURRENT_TIMESTAMP");
      $stmtProductos = $db->prepare("INSERT IGNORE INTO erp_catalogo_producto_categorias
        (id_producto_erp, id_categoria_erp, es_principal)
        SELECT pc.id_producto_erp, :destino, 0
        FROM erp_catalogo_producto_categorias pc
        WHERE pc.id_categoria_erp=:origen");
      $equivalencias = 0;
      $relaciones = 0;
      $origenesMapeados = array();
      foreach ($mapa as $codigoDestino => $idsEcom) {
        foreach ($idsEcom as $idEcom) {
          $stmtOrigen->execute(array(":codigo" => "ECOM-CAT-" . intval($idEcom)));
          $idOrigen = intval($stmtOrigen->fetchColumn());
          if ($idOrigen <= 0) {
            continue;
          }
          $stmtEquivalencia->execute(array(
            ":origen" => $idOrigen,
            ":destino" => $destinos[$codigoDestino],
            ":usuario" => intval($idUsuario) ?: null
          ));
          $equivalencias++;
          $origenesMapeados[$idOrigen] = true;
          $stmtProductos->execute(array(":destino" => $destinos[$codigoDestino], ":origen" => $idOrigen));
          $relaciones += $stmtProductos->rowCount();
        }
      }

      $prioridad = array(
        "CAT-ALIM-ALIMENTOS", "CAT-HAB-ACUARIOS", "CAT-HAB-TERRARIOS", "CAT-HAB-JAULAS",
        "CAT-HAB-REFUGIOS", "CAT-SALUD-HIGIENE", "CAT-SALUD-PREVENCION", "CAT-JUEGO-JUGUETES",
        "CAT-JUEGO-RASCADORES", "CAT-TRANS-TRANSPORTADORAS", "CAT-EQUIP-FILTRACION",
        "CAT-EQUIP-CALEFACCION", "CAT-EQUIP-ILUMINACION", "CAT-EQUIP-BOMBAS",
        "CAT-VIVOS-PECES", "CAT-VIVOS-PLANTAS", "CAT-ALIM-PREMIOS", "CAT-ALIM-COMEDEROS",
        "CAT-JUEGO-AMBIENTACION", "CAT-TRANS-SUJECION", "CAT-TRANS-ENTRENAMIENTO",
        "CAT-REP-REPUESTOS", "CAT-REP-ACCESORIOS"
      );
      $case = array();
      foreach ($prioridad as $orden => $codigo) {
        $case[] = "WHEN c.codigo=" . $db->quote($codigo) . " THEN " . ($orden + 1);
      }
      $productos = $db->query("SELECT DISTINCT pc.id_producto_erp
        FROM erp_catalogo_producto_categorias pc
        INNER JOIN erp_catalogo_categorias c ON c.id_categoria_erp=pc.id_categoria_erp
        WHERE c.tipo_categoria='maestra' AND c.permite_productos=1")->fetchAll(PDO::FETCH_COLUMN);
      $stmtPrincipal = $db->prepare("SELECT pc.id_categoria_erp
        FROM erp_catalogo_producto_categorias pc
        INNER JOIN erp_catalogo_categorias c ON c.id_categoria_erp=pc.id_categoria_erp
        WHERE pc.id_producto_erp=:producto AND c.tipo_categoria='maestra' AND c.permite_productos=1
        ORDER BY CASE " . implode(" ", $case) . " ELSE 999 END, pc.id_producto_categoria
        LIMIT 1");
      $stmtLimpiar = $db->prepare("UPDATE erp_catalogo_producto_categorias SET es_principal=0 WHERE id_producto_erp=:producto");
      $stmtMarcar = $db->prepare("UPDATE erp_catalogo_producto_categorias SET es_principal=1
        WHERE id_producto_erp=:producto AND id_categoria_erp=:categoria");
      foreach ($productos as $idProducto) {
        $stmtPrincipal->execute(array(":producto" => intval($idProducto)));
        $idPrincipal = intval($stmtPrincipal->fetchColumn());
        if ($idPrincipal > 0) {
          $stmtLimpiar->execute(array(":producto" => intval($idProducto)));
          $stmtMarcar->execute(array(":producto" => intval($idProducto), ":categoria" => $idPrincipal));
        }
      }
      $db->commit();
      return $this->respuesta(false, "success", "Relaciones maestras aplicadas desde la clasificacion historica", array(
        "categorias_legado_mapeadas" => count($origenesMapeados),
        "equivalencias" => $equivalencias,
        "relaciones_producto_creadas" => $relaciones,
        "productos_con_categoria_maestra" => count($productos)
      ));
    } catch (Exception $e) {
      if ($db->inTransaction()) {
        $db->rollBack();
      }
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  public function listarTaxonomiasCatalogo() {
    try {
      $db = $this->getConexion();
      if (!$this->tablaExisteCatalogo($db, "erp_catalogo_taxonomias")) {
        return $this->respuesta(false, "warning", "El esquema de taxonomias esta pendiente", array("taxonomias" => array(), "nodos" => array()));
      }
      $taxonomias = $db->query("SELECT t.id_taxonomia, t.codigo, t.nombre, t.tipo, t.canal, t.descripcion, t.estatus,
        COUNT(DISTINCT n.id_nodo_taxonomia) total_nodos,
        COUNT(DISTINCT CASE WHEN n.tipo_nodo='clasificacion' THEN n.id_nodo_taxonomia END) total_clasificaciones,
        COUNT(DISTINCT CASE WHEN n.tipo_nodo='categoria' THEN n.id_nodo_taxonomia END) total_categorias,
        COUNT(DISTINCT pn.id_producto_erp) total_productos
        FROM erp_catalogo_taxonomias t
        LEFT JOIN erp_catalogo_taxonomia_nodos n ON n.id_taxonomia=t.id_taxonomia AND n.estatus='activo'
        LEFT JOIN erp_catalogo_producto_taxonomia_nodos pn ON pn.id_nodo_taxonomia=n.id_nodo_taxonomia
        GROUP BY t.id_taxonomia, t.codigo, t.nombre, t.tipo, t.canal, t.descripcion, t.estatus
        ORDER BY t.nombre")->fetchAll(PDO::FETCH_ASSOC);
      $nodos = $db->query("SELECT n.id_nodo_taxonomia, n.id_taxonomia, n.id_nodo_padre, n.id_categoria_erp,
        n.tipo_nodo, n.codigo, n.nombre, n.ruta, n.nivel, n.orden, n.id_externo, n.estatus,
        CASE WHEN n.tipo_nodo='clasificacion' THEN (
          SELECT COUNT(DISTINCT pnc.id_producto_erp)
          FROM erp_catalogo_taxonomia_nodos nc
          INNER JOIN erp_catalogo_producto_taxonomia_nodos pnc ON pnc.id_nodo_taxonomia=nc.id_nodo_taxonomia
          WHERE nc.id_nodo_padre=n.id_nodo_taxonomia AND nc.estatus='activo'
        ) ELSE COUNT(DISTINCT pn.id_producto_erp) END total_productos
        FROM erp_catalogo_taxonomia_nodos n
        LEFT JOIN erp_catalogo_producto_taxonomia_nodos pn ON pn.id_nodo_taxonomia=n.id_nodo_taxonomia
        WHERE n.estatus='activo'
        GROUP BY n.id_nodo_taxonomia, n.id_taxonomia, n.id_nodo_padre, n.id_categoria_erp,
          n.tipo_nodo, n.codigo, n.nombre, n.ruta, n.nivel, n.orden, n.id_externo, n.estatus
        ORDER BY n.id_taxonomia, n.ruta, n.orden, n.nombre")->fetchAll(PDO::FETCH_ASSOC);
      return $this->respuesta(false, "success", "Taxonomias consultadas", array("taxonomias" => $taxonomias, "nodos" => $nodos));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-29
   * Proposito: convierte clasificaciones heredadas en categorias maestras jerarquicas y las usa como categoria principal cuando el producto aun no tiene una.
   * Impacto: Catalogo ERP; limpia la dependencia visual de ecommerce sin borrar evidencia historica ni tablas origen.
   */
  public function sincronizarTaxonomiaEcommerce() {
    $db = $this->getConexion();
    foreach (array("ecom_clasificaciones", "ecom_clasificaciones_categorias", "ecom_categorias", "ecom_productos_categorias") as $tabla) {
      if (!$this->tablaExisteCatalogo($db, $tabla)) {
        return $this->respuesta(true, "warning", "Falta la tabla de origen " . $tabla);
      }
    }
    foreach (array("erp_catalogo_taxonomias", "erp_catalogo_taxonomia_nodos", "erp_catalogo_producto_taxonomia_nodos") as $tabla) {
      if (!$this->tablaExisteCatalogo($db, $tabla)) {
        return $this->respuesta(true, "warning", "Primero ejecuta la actualizacion del esquema del catalogo ERP");
      }
    }

    $resumen = array(
      "clasificaciones" => 0,
      "ramas_categoria" => 0,
      "categorias_estructurales_creadas" => 0,
      "categorias_operativas_creadas" => 0,
      "categorias_maestras_creadas" => 0,
      "productos_vinculados" => 0,
      "categorias_principales_asignadas" => 0,
      "productos_ecommerce_sin_vinculo_erp" => 0
    );
    try {
      $db->beginTransaction();
      $db->exec("INSERT INTO erp_catalogo_taxonomias
        (codigo, nombre, tipo, canal, descripcion, estatus)
        VALUES ('ECOM-HISTORICA', 'Clasificacion historica importada', 'clasificacion', 'historico',
          'Clasificacion heredada usada como insumo para construir categorias maestras ERP.', 'activa')
        ON DUPLICATE KEY UPDATE id_taxonomia=LAST_INSERT_ID(id_taxonomia), nombre=VALUES(nombre),
          tipo=VALUES(tipo), canal=VALUES(canal), descripcion=VALUES(descripcion), estatus='activa',
          fecha_actualizacion=CURRENT_TIMESTAMP");
      $idTaxonomia = intval($db->lastInsertId());

      $stmtCategoriaMaestra = $db->prepare("INSERT INTO erp_catalogo_categorias
        (id_categoria_padre, codigo, nombre, descripcion, ruta, nivel, tipo_categoria, origen, permite_productos, estatus)
        VALUES (:padre, :codigo, :nombre, :descripcion, :ruta, :nivel, 'maestra', 'erp', :permite, 'activa')
        ON DUPLICATE KEY UPDATE id_categoria_erp=LAST_INSERT_ID(id_categoria_erp),
          id_categoria_padre=VALUES(id_categoria_padre), nombre=VALUES(nombre), descripcion=VALUES(descripcion),
          ruta=VALUES(ruta), nivel=VALUES(nivel), tipo_categoria='maestra', origen='erp',
          permite_productos=VALUES(permite_productos), estatus='activa', fecha_actualizacion=CURRENT_TIMESTAMP");

      $stmtNodo = $db->prepare("INSERT INTO erp_catalogo_taxonomia_nodos
        (id_taxonomia, id_nodo_padre, id_categoria_erp, tipo_nodo, codigo, nombre, ruta, nivel, orden, id_externo, estatus)
        VALUES (:taxonomia, :padre, :categoria, :tipo, :codigo, :nombre, :ruta, :nivel, :orden, :externo, 'activo')
        ON DUPLICATE KEY UPDATE id_nodo_taxonomia=LAST_INSERT_ID(id_nodo_taxonomia),
          id_nodo_padre=VALUES(id_nodo_padre), id_categoria_erp=VALUES(id_categoria_erp),
          tipo_nodo=VALUES(tipo_nodo), nombre=VALUES(nombre), ruta=VALUES(ruta), nivel=VALUES(nivel),
          orden=VALUES(orden), id_externo=VALUES(id_externo), estatus='activo',
          fecha_actualizacion=CURRENT_TIMESTAMP");
      $stmtNodo->execute(array(
        ":taxonomia" => $idTaxonomia, ":padre" => null, ":categoria" => null, ":tipo" => "raiz",
        ":codigo" => "ROOT", ":nombre" => "Catalogo", ":ruta" => "Catalogo",
        ":nivel" => 0, ":orden" => 0, ":externo" => null
      ));
      $idRaiz = intval($db->lastInsertId());
      $db->prepare("UPDATE erp_catalogo_taxonomia_nodos SET estatus='inactivo', fecha_actualizacion=CURRENT_TIMESTAMP
        WHERE id_taxonomia=:taxonomia AND id_nodo_taxonomia<>:raiz")
        ->execute(array(":taxonomia" => $idTaxonomia, ":raiz" => $idRaiz));

      $clasificaciones = $db->query("SELECT id_clasificacion, clasificacion
        FROM ecom_clasificaciones WHERE estatus=1 ORDER BY id_clasificacion")->fetchAll(PDO::FETCH_ASSOC);
      $nodosClasificacion = array();
      $categoriasClasificacion = array();
      $nombresClasificacion = array();
      foreach ($clasificaciones as $orden => $clasificacion) {
        $idClasificacion = intval($clasificacion["id_clasificacion"]);
        $nombre = $this->normalizarTextoHistoricoCatalogo($clasificacion["clasificacion"]);
        $stmtCategoriaMaestra->execute(array(
          ":padre" => null,
          ":codigo" => "CLAS-HIST-" . $idClasificacion,
          ":nombre" => $nombre,
          ":descripcion" => "Clasificacion heredada convertida en categoria estructural ERP",
          ":ruta" => $nombre,
          ":nivel" => 0,
          ":permite" => 0
        ));
        $categoriasClasificacion[$idClasificacion] = intval($db->lastInsertId());
        $resumen["categorias_estructurales_creadas"]++;
        $stmtNodo->execute(array(
          ":taxonomia" => $idTaxonomia, ":padre" => $idRaiz, ":categoria" => $categoriasClasificacion[$idClasificacion],
          ":tipo" => "clasificacion", ":codigo" => "CLAS-" . $idClasificacion, ":nombre" => $nombre,
          ":ruta" => "Catalogo / " . $nombre, ":nivel" => 1, ":orden" => $orden + 1,
          ":externo" => (string) $idClasificacion
        ));
        $nodosClasificacion[$idClasificacion] = intval($db->lastInsertId());
        $nombresClasificacion[$idClasificacion] = $nombre;
        $resumen["clasificaciones"]++;
      }

      $ramas = $db->query("SELECT cc.id_clasificacion, cc.id_categoria, c.categoria
        FROM ecom_clasificaciones_categorias cc
        INNER JOIN ecom_clasificaciones cl ON cl.id_clasificacion=cc.id_clasificacion AND cl.estatus=1
        INNER JOIN ecom_categorias c ON c.id_categoria=cc.id_categoria AND c.estatus=1
        ORDER BY cc.id_clasificacion, cc.id_clasificacion_categoria")->fetchAll(PDO::FETCH_ASSOC);
      $stmtProductosCategoria = $db->prepare("INSERT INTO erp_catalogo_producto_categorias
        (id_producto_erp, id_categoria_erp, es_principal)
        SELECT DISTINCT v.id_producto_erp, :categoria,
          CASE WHEN NOT EXISTS (
            SELECT 1 FROM erp_catalogo_producto_categorias pc
            WHERE pc.id_producto_erp=v.id_producto_erp AND pc.es_principal=1
          ) THEN 1 ELSE 0 END
        FROM erp_catalogo_canales_vinculos v
        INNER JOIN ecom_productos_categorias pc ON pc.id_producto=CAST(v.id_externo AS UNSIGNED)
        INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=v.id_producto_erp AND p.estatus<>'fusionado'
        WHERE v.canal='ecommerce' AND v.id_producto_erp IS NOT NULL AND pc.id_categoria=:categoria_origen
        ON DUPLICATE KEY UPDATE es_principal=GREATEST(es_principal, VALUES(es_principal))");
      foreach ($ramas as $orden => $rama) {
        $idClasificacion = intval($rama["id_clasificacion"]);
        $idCategoriaEcom = intval($rama["id_categoria"]);
        if (!isset($nodosClasificacion[$idClasificacion], $categoriasClasificacion[$idClasificacion])) {
          continue;
        }
        $nombreCategoria = $this->normalizarTextoHistoricoCatalogo($rama["categoria"]);
        $rutaCategoria = $nombresClasificacion[$idClasificacion] . " / " . $nombreCategoria;
        $stmtCategoriaMaestra->execute(array(
          ":padre" => $categoriasClasificacion[$idClasificacion],
          ":codigo" => "CLAS-HIST-" . $idClasificacion . "-CAT-" . $idCategoriaEcom,
          ":nombre" => $nombreCategoria,
          ":descripcion" => "Categoria heredada convertida en categoria operativa ERP",
          ":ruta" => $rutaCategoria,
          ":nivel" => 1,
          ":permite" => 1
        ));
        $idCategoriaErp = intval($db->lastInsertId());
        $resumen["categorias_operativas_creadas"]++;
        $resumen["categorias_maestras_creadas"]++;
        $stmtNodo->execute(array(
          ":taxonomia" => $idTaxonomia, ":padre" => $nodosClasificacion[$idClasificacion],
          ":categoria" => $idCategoriaErp, ":tipo" => "categoria",
          ":codigo" => "CLAS-" . $idClasificacion . "-CAT-" . $idCategoriaEcom,
          ":nombre" => $nombreCategoria,
          ":ruta" => "Catalogo / " . $rutaCategoria,
          ":nivel" => 2, ":orden" => $orden + 1, ":externo" => (string) $idCategoriaEcom
        ));
        $stmtProductosCategoria->execute(array(":categoria" => $idCategoriaErp, ":categoria_origen" => $idCategoriaEcom));
        $resumen["categorias_principales_asignadas"] += $stmtProductosCategoria->rowCount();
        $resumen["ramas_categoria"]++;
      }
      $resumen["ramas_categoria"] = intval($db->query("SELECT COUNT(*) FROM erp_catalogo_taxonomia_nodos
        WHERE id_taxonomia=" . $idTaxonomia . " AND tipo_nodo='categoria' AND estatus='activo'")->fetchColumn());

      $db->prepare("DELETE pn FROM erp_catalogo_producto_taxonomia_nodos pn
        INNER JOIN erp_catalogo_taxonomia_nodos n ON n.id_nodo_taxonomia=pn.id_nodo_taxonomia
        WHERE n.id_taxonomia=:taxonomia")->execute(array(":taxonomia" => $idTaxonomia));
      $stmt = $db->prepare("INSERT IGNORE INTO erp_catalogo_producto_taxonomia_nodos
        (id_producto_erp, id_nodo_taxonomia, es_principal)
        SELECT DISTINCT v.id_producto_erp, n.id_nodo_taxonomia, 0
        FROM erp_catalogo_canales_vinculos v
        INNER JOIN ecom_productos_categorias pc ON pc.id_producto=CAST(v.id_externo AS UNSIGNED)
        INNER JOIN erp_catalogo_taxonomia_nodos n
          ON n.id_taxonomia=:taxonomia AND n.tipo_nodo='categoria'
          AND n.id_externo=CAST(pc.id_categoria AS CHAR) AND n.estatus='activo'
        INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=v.id_producto_erp AND p.estatus<>'fusionado'
        WHERE v.canal='ecommerce' AND v.id_producto_erp IS NOT NULL");
      $stmt->execute(array(":taxonomia" => $idTaxonomia));
      $resumen["productos_vinculados"] = intval($db->query("SELECT COUNT(DISTINCT pn.id_producto_erp)
        FROM erp_catalogo_producto_taxonomia_nodos pn
        INNER JOIN erp_catalogo_taxonomia_nodos n ON n.id_nodo_taxonomia=pn.id_nodo_taxonomia
        WHERE n.id_taxonomia=" . $idTaxonomia)->fetchColumn());
      $resumen["productos_ecommerce_sin_vinculo_erp"] = intval($db->query("SELECT COUNT(DISTINCT pc.id_producto)
        FROM ecom_productos_categorias pc
        WHERE NOT EXISTS (
          SELECT 1 FROM erp_catalogo_canales_vinculos v
          WHERE v.canal='ecommerce' AND CAST(v.id_externo AS UNSIGNED)=pc.id_producto AND v.id_producto_erp IS NOT NULL
        )")->fetchColumn());
      $db->commit();
      return $this->respuesta(false, "success", "Clasificacion heredada sincronizada como categorias maestras", $resumen);
    } catch (Exception $e) {
      if ($db->inTransaction()) {
        $db->rollBack();
      }
      return $this->respuesta(true, "danger", $e->getMessage(), $resumen);
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-24
   * Proposito: audita completitud de Catalogo separando precio provisional de decisiones formales de listas.
   * Impacto: Catalogo ERP; Precios/Listas sigue siendo responsable del precio final por canal.
   */
  public function auditarCalidadCatalogo() {
    try {
      $db = $this->getConexion();
      $resumen = array(
        "productos" => intval($db->query("SELECT COUNT(*) FROM erp_catalogo_productos WHERE estatus<>'fusionado'")->fetchColumn()),
        "skus" => intval($db->query("SELECT COUNT(*) FROM erp_catalogo_skus WHERE estatus<>'fusionado'")->fetchColumn()),
        "productos_sin_sku" => intval($db->query("SELECT COUNT(*) FROM erp_catalogo_productos p WHERE p.estatus<>'fusionado' AND NOT EXISTS (SELECT 1 FROM erp_catalogo_skus s WHERE s.id_producto_erp=p.id_producto_erp AND s.estatus<>'fusionado')")->fetchColumn()),
        "productos_sin_marca" => intval($db->query("SELECT COUNT(*) FROM erp_catalogo_productos WHERE estatus<>'fusionado' AND id_marca_erp IS NULL")->fetchColumn()),
        "productos_sin_categoria" => intval($db->query("SELECT COUNT(*) FROM erp_catalogo_productos p WHERE p.estatus<>'fusionado' AND NOT EXISTS (SELECT 1 FROM erp_catalogo_producto_categorias pc WHERE pc.id_producto_erp=p.id_producto_erp)")->fetchColumn()),
        "productos_sin_imagen" => intval($db->query("SELECT COUNT(*) FROM erp_catalogo_productos p WHERE p.estatus<>'fusionado' AND NOT EXISTS (SELECT 1 FROM erp_catalogo_imagenes i WHERE i.id_producto_erp=p.id_producto_erp AND i.estatus='activo')")->fetchColumn()),
        "skus_sin_precio" => intval($db->query("SELECT COUNT(*) FROM erp_catalogo_skus s WHERE s.estatus<>'fusionado' AND NOT EXISTS (SELECT 1 FROM erp_catalogo_sku_precios pr WHERE pr.id_sku=s.id_sku AND pr.estatus='activo')")->fetchColumn()),
        "skus_fiscal_incompleto" => intval($db->query("SELECT COUNT(*) FROM erp_catalogo_skus s LEFT JOIN erp_catalogo_sku_impuestos imp ON imp.id_sku=s.id_sku WHERE s.estatus<>'fusionado' AND (imp.id_sku IS NULL OR TRIM(COALESCE(imp.clave_producto_sat,''))='' OR TRIM(COALESCE(imp.clave_unidad_sat,''))='' OR TRIM(COALESCE(imp.objeto_impuesto,''))='' OR imp.iva_porcentaje IS NULL OR imp.ieps_porcentaje IS NULL OR imp.incluye_impuestos IS NULL)")->fetchColumn()),
        "skus_activos_fiscal_incompleto" => intval($db->query("SELECT COUNT(*) FROM erp_catalogo_skus s LEFT JOIN erp_catalogo_sku_impuestos imp ON imp.id_sku=s.id_sku WHERE s.estatus='activo' AND (imp.id_sku IS NULL OR TRIM(COALESCE(imp.clave_producto_sat,''))='' OR TRIM(COALESCE(imp.clave_unidad_sat,''))='' OR TRIM(COALESCE(imp.objeto_impuesto,''))='' OR imp.iva_porcentaje IS NULL OR imp.ieps_porcentaje IS NULL OR imp.incluye_impuestos IS NULL)")->fetchColumn()),
        "skus_comprables_fiscal_incompleto" => intval($db->query("SELECT COUNT(*) FROM erp_catalogo_skus s LEFT JOIN erp_catalogo_sku_impuestos imp ON imp.id_sku=s.id_sku WHERE s.estatus='activo' AND EXISTS (SELECT 1 FROM erp_catalogo_sku_proveedores sp WHERE sp.id_sku=s.id_sku AND sp.estatus='activo') AND (imp.id_sku IS NULL OR TRIM(COALESCE(imp.clave_producto_sat,''))='' OR TRIM(COALESCE(imp.clave_unidad_sat,''))='' OR TRIM(COALESCE(imp.objeto_impuesto,''))='' OR imp.iva_porcentaje IS NULL OR imp.ieps_porcentaje IS NULL OR imp.incluye_impuestos IS NULL)")->fetchColumn()),
        "skus_activos_sin_proveedor_activo" => intval($db->query("SELECT COUNT(*) FROM erp_catalogo_skus s WHERE s.estatus='activo' AND NOT EXISTS (SELECT 1 FROM erp_catalogo_sku_proveedores sp WHERE sp.id_sku=s.id_sku AND sp.estatus='activo')")->fetchColumn()),
        "skus_activos_sin_codigo_principal" => intval($db->query("SELECT COUNT(*) FROM erp_catalogo_skus s WHERE s.estatus='activo' AND NOT EXISTS (SELECT 1 FROM erp_catalogo_sku_codigos c WHERE c.id_sku=s.id_sku AND c.es_principal=1 AND c.estatus='activo')")->fetchColumn()),
        "skus_sin_reglas" => intval($db->query("SELECT COUNT(*) FROM erp_catalogo_skus s WHERE s.estatus<>'fusionado' AND s.tipo_inventario NOT IN ('servicio','cargo') AND NOT EXISTS (SELECT 1 FROM erp_catalogo_sku_reglas_inventario r WHERE r.id_sku=s.id_sku)")->fetchColumn()),
        "skus_sin_reorden" => intval($db->query("SELECT COUNT(*) FROM erp_catalogo_skus s INNER JOIN erp_catalogo_sku_reglas_inventario r ON r.id_sku=s.id_sku WHERE s.estatus<>'fusionado' AND s.tipo_inventario NOT IN ('servicio','cargo') AND r.controla_inventario=1 AND r.punto_reorden<=0")->fetchColumn()),
        "incidencias_reglas_inventario_abiertas" => intval($db->query("SELECT COUNT(*) FROM erp_catalogo_incidencias_calidad WHERE tipo_incidencia LIKE 'inventario_%' AND estatus IN ('pendiente','en_revision','bloqueada')")->fetchColumn()),
        "incidencias_bloqueos_criticos_abiertas" => intval($db->query("SELECT COUNT(*) FROM erp_catalogo_incidencias_calidad WHERE tipo_incidencia IN ('fiscal_incompleto','sku_sin_proveedor_activo','sku_sin_codigo_principal') AND estatus IN ('pendiente','en_revision','bloqueada')")->fetchColumn()),
        "variantes_sin_atributos" => intval($db->query("SELECT COUNT(*) FROM erp_catalogo_productos p WHERE p.estatus<>'fusionado' AND p.maneja_variantes=1 AND NOT EXISTS (SELECT 1 FROM erp_catalogo_skus s INNER JOIN erp_catalogo_sku_atributos sa ON sa.id_sku=s.id_sku INNER JOIN erp_catalogo_atributos a ON a.id_atributo_erp=sa.id_atributo_erp AND a.es_variante=1 WHERE s.id_producto_erp=p.id_producto_erp)")->fetchColumn()),
        "incidencias_migracion_pendientes" => intval($db->query("SELECT COUNT(*) FROM erp_catalogo_migracion_ecom_incidencias WHERE estatus='pendiente'")->fetchColumn())
      );

      $problemas = array_merge(
        $this->problemasCatalogo($db, "Producto sin SKU", "alta", "SELECT p.id_producto_erp, NULL id_sku, p.codigo_producto, p.nombre producto, NULL sku FROM erp_catalogo_productos p WHERE p.estatus<>'fusionado' AND NOT EXISTS (SELECT 1 FROM erp_catalogo_skus s WHERE s.id_producto_erp=p.id_producto_erp AND s.estatus<>'fusionado') ORDER BY p.id_producto_erp DESC LIMIT 25"),
        $this->problemasCatalogo($db, "SKU sin precio provisional", "media", "SELECT p.id_producto_erp, s.id_sku, p.codigo_producto, p.nombre producto, s.sku FROM erp_catalogo_skus s INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp WHERE s.estatus<>'fusionado' AND NOT EXISTS (SELECT 1 FROM erp_catalogo_sku_precios pr WHERE pr.id_sku=s.id_sku AND pr.estatus='activo') ORDER BY s.id_sku DESC LIMIT 25"),
        $this->problemasCatalogo($db, "SKU comprable sin fiscal completo", "bloqueante", "SELECT p.id_producto_erp, s.id_sku, p.codigo_producto, p.nombre producto, s.sku FROM erp_catalogo_skus s INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp LEFT JOIN erp_catalogo_sku_impuestos imp ON imp.id_sku=s.id_sku WHERE s.estatus='activo' AND EXISTS (SELECT 1 FROM erp_catalogo_sku_proveedores sp WHERE sp.id_sku=s.id_sku AND sp.estatus='activo') AND (imp.id_sku IS NULL OR TRIM(COALESCE(imp.clave_producto_sat,''))='' OR TRIM(COALESCE(imp.clave_unidad_sat,''))='' OR TRIM(COALESCE(imp.objeto_impuesto,''))='' OR imp.iva_porcentaje IS NULL OR imp.ieps_porcentaje IS NULL OR imp.incluye_impuestos IS NULL) ORDER BY s.id_sku DESC LIMIT 25"),
        $this->problemasCatalogo($db, "SKU activo sin proveedor activo", "alta", "SELECT p.id_producto_erp, s.id_sku, p.codigo_producto, p.nombre producto, s.sku FROM erp_catalogo_skus s INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp WHERE s.estatus='activo' AND NOT EXISTS (SELECT 1 FROM erp_catalogo_sku_proveedores sp WHERE sp.id_sku=s.id_sku AND sp.estatus='activo') ORDER BY s.id_sku DESC LIMIT 25"),
        $this->problemasCatalogo($db, "SKU activo sin codigo principal", "media", "SELECT p.id_producto_erp, s.id_sku, p.codigo_producto, p.nombre producto, s.sku FROM erp_catalogo_skus s INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp WHERE s.estatus='activo' AND NOT EXISTS (SELECT 1 FROM erp_catalogo_sku_codigos c WHERE c.id_sku=s.id_sku AND c.es_principal=1 AND c.estatus='activo') ORDER BY s.id_sku DESC LIMIT 25"),
        $this->problemasCatalogo($db, "Producto sin categoria", "media", "SELECT p.id_producto_erp, NULL id_sku, p.codigo_producto, p.nombre producto, NULL sku FROM erp_catalogo_productos p WHERE p.estatus<>'fusionado' AND NOT EXISTS (SELECT 1 FROM erp_catalogo_producto_categorias pc WHERE pc.id_producto_erp=p.id_producto_erp) ORDER BY p.id_producto_erp DESC LIMIT 25"),
        $this->problemasCatalogo($db, "Producto sin imagen", "media", "SELECT p.id_producto_erp, NULL id_sku, p.codigo_producto, p.nombre producto, NULL sku FROM erp_catalogo_productos p WHERE p.estatus<>'fusionado' AND NOT EXISTS (SELECT 1 FROM erp_catalogo_imagenes i WHERE i.id_producto_erp=p.id_producto_erp AND i.estatus='activo') ORDER BY p.id_producto_erp DESC LIMIT 25"),
        $this->problemasCatalogo($db, "Producto con variantes sin atributos", "media", "SELECT p.id_producto_erp, NULL id_sku, p.codigo_producto, p.nombre producto, NULL sku FROM erp_catalogo_productos p WHERE p.estatus<>'fusionado' AND p.maneja_variantes=1 AND NOT EXISTS (SELECT 1 FROM erp_catalogo_skus s INNER JOIN erp_catalogo_sku_atributos sa ON sa.id_sku=s.id_sku INNER JOIN erp_catalogo_atributos a ON a.id_atributo_erp=sa.id_atributo_erp AND a.es_variante=1 WHERE s.id_producto_erp=p.id_producto_erp) ORDER BY p.id_producto_erp DESC LIMIT 25")
      );

      return $this->respuesta(false, "success", "Auditoria de calidad consultada", array(
        "resumen" => $resumen,
        "problemas" => array_slice($problemas, 0, 100)
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  public function sincronizarIncidenciasReglasInventario($idUsuario = 0) {
    $db = $this->getConexion();
    $resumen = array(
      "default_generico" => 0,
      "reorden_cero" => 0,
      "posible_cargo_servicio" => 0,
      "posible_lote_caducidad" => 0
    );

    try {
      $db->beginTransaction();

      foreach ($this->consultarSkusReglaDefaultGenerica($db) as $fila) {
        $this->guardarIncidenciaCalidad($db, array(
          "tipo" => "inventario_default_generico",
          "severidad" => "advertencia",
          "titulo" => "Regla de inventario generica",
          "descripcion" => "SKU inventariable con reglas fisicas y reorden en valores default; requiere validacion operativa.",
          "fila" => $fila,
          "detalle" => array(
            "motivo" => "Regla generica detectada",
            "criterios" => array("inventariable", "sin lote", "sin caducidad", "sin serie", "FIFO", "minimo cero", "reorden cero")
          ),
          "propuesta" => array("accion" => "validar_tipo_inventario_y_controles_fisicos")
        ), $idUsuario);
        $resumen["default_generico"]++;
      }

      foreach ($this->consultarSkusReordenCero($db) as $fila) {
        $this->guardarIncidenciaCalidad($db, array(
          "tipo" => "inventario_reorden_cero",
          "severidad" => intval($fila["proveedores_activos"]) > 0 ? "advertencia" : "informativo",
          "titulo" => "SKU sin punto de reorden",
          "descripcion" => "SKU inventariable con punto de reorden en cero; no generara alerta util de reposicion.",
          "fila" => $fila,
          "detalle" => array("motivo" => "Punto de reorden en cero", "proveedores_activos" => intval($fila["proveedores_activos"])),
          "propuesta" => array("accion" => "revisar_o_aplicar_propuesta_reorden")
        ), $idUsuario);
        $resumen["reorden_cero"]++;
      }

      foreach ($this->consultarSkusPosibleCargoServicio($db) as $fila) {
        $this->guardarIncidenciaCalidad($db, array(
          "tipo" => "inventario_posible_cargo_servicio",
          "severidad" => "alta",
          "titulo" => "SKU podria ser cargo o servicio",
          "descripcion" => "El nombre sugiere cargo/servicio, pero el SKU controla inventario.",
          "fila" => $fila,
          "detalle" => array("motivo" => "Texto de nombre/SKU sugiere cargo o servicio"),
          "propuesta" => array("accion" => "clasificar_como_cargo_servicio_o_confirmar_inventariable")
        ), $idUsuario);
        $resumen["posible_cargo_servicio"]++;
      }

      foreach ($this->consultarSkusPosibleLoteCaducidad($db) as $fila) {
        $this->guardarIncidenciaCalidad($db, array(
          "tipo" => "inventario_posible_lote_caducidad",
          "severidad" => "media",
          "titulo" => "SKU podria requerir lote/caducidad",
          "descripcion" => "El nombre sugiere producto con trazabilidad o vencimiento, pero no requiere lote/caducidad.",
          "fila" => $fila,
          "detalle" => array("motivo" => "Texto de nombre/SKU sugiere lote, caducidad o control sanitario"),
          "propuesta" => array("accion" => "validar_lote_caducidad_y_fefo")
        ), $idUsuario);
        $resumen["posible_lote_caducidad"]++;
      }

      $db->commit();
      return $this->respuesta(false, "success", "Incidencias de reglas de inventario sincronizadas", $resumen);
    } catch (Exception $e) {
      if ($db->inTransaction()) {
        $db->rollBack();
      }
      return $this->respuesta(true, "danger", $e->getMessage(), $resumen);
    }
  }

  public function sincronizarIncidenciasVariantes($idUsuario = 0) {
    $db = $this->getConexion();
    $resumen = array(
      "sin_atributos" => 0,
      "valores_incompletos" => 0,
      "firma_duplicada" => 0
    );

    try {
      $db->beginTransaction();

      foreach ($this->consultarProductosVariantesSinAtributos($db) as $fila) {
        $this->guardarIncidenciaCalidad($db, array(
          "tipo" => "variantes_sin_atributos",
          "entidad_tipo" => "producto",
          "severidad" => "media",
          "titulo" => "Producto con variantes sin atributos",
          "descripcion" => "Producto marcado como variante con multiples SKUs, pero sin atributos de variante configurados.",
          "fila" => $fila,
          "detalle" => array(
            "motivo" => "Producto con multiples SKUs sin firma de variante",
            "skus_activos" => intval(isset($fila["skus_activos"]) ? $fila["skus_activos"] : 0)
          ),
          "propuesta" => array("accion" => "definir_atributo_variante_color_medida_presentacion_sabor_formula_modelo")
        ), $idUsuario);
        $resumen["sin_atributos"]++;
      }

      foreach ($this->consultarSkusVariantesValoresIncompletos($db) as $fila) {
        $this->guardarIncidenciaCalidad($db, array(
          "tipo" => "variantes_valores_incompletos",
          "severidad" => "media",
          "titulo" => "SKU con valores de variante incompletos",
          "descripcion" => "El producto tiene atributos de variante, pero este SKU no tiene todos los valores requeridos para una firma legible.",
          "fila" => $fila,
          "detalle" => array(
            "motivo" => "Faltan valores de atributos de variante",
            "atributos_faltantes" => isset($fila["atributos_faltantes"]) ? $fila["atributos_faltantes"] : ""
          ),
          "propuesta" => array("accion" => "capturar_valores_de_variante_para_el_sku")
        ), $idUsuario);
        $resumen["valores_incompletos"]++;
      }

      foreach ($this->consultarProductosVariantesFirmasDuplicadas($db) as $fila) {
        $this->guardarIncidenciaCalidad($db, array(
          "tipo" => "variantes_firma_duplicada",
          "entidad_tipo" => "producto",
          "severidad" => "alta",
          "titulo" => "Producto con firma de variante duplicada",
          "descripcion" => "Dos o mas SKUs del producto comparten la misma combinacion de atributos de variante.",
          "fila" => $fila,
          "detalle" => array(
            "motivo" => "Firma de variante repetida",
            "firma" => isset($fila["firma"]) ? $fila["firma"] : "",
            "skus_duplicados" => isset($fila["skus_duplicados"]) ? $fila["skus_duplicados"] : ""
          ),
          "propuesta" => array("accion" => "agregar_o_corregir_atributo_diferenciador")
        ), $idUsuario);
        $resumen["firma_duplicada"]++;
      }

      $db->commit();
      return $this->respuesta(false, "success", "Incidencias de variantes sincronizadas", $resumen);
    } catch (Exception $e) {
      if ($db->inTransaction()) {
        $db->rollBack();
      }
      return $this->respuesta(true, "danger", $e->getMessage(), $resumen);
    }
  }

  public function sincronizarIncidenciasBloqueosCriticos($idUsuario = 0) {
    $db = $this->getConexion();
    $resumen = array(
      "fiscal_incompleto" => 0,
      "sin_proveedor_activo" => 0,
      "sin_codigo_principal" => 0
    );

    try {
      $db->beginTransaction();

      foreach ($this->consultarSkusFiscalIncompleto($db) as $fila) {
        $proveedores = intval(isset($fila["proveedores_activos"]) ? $fila["proveedores_activos"] : 0);
        $estatusSku = isset($fila["estatus_sku"]) ? $fila["estatus_sku"] : "";
        $this->guardarIncidenciaCalidad($db, array(
          "tipo" => "fiscal_incompleto",
          "severidad" => ($estatusSku === "activo" && $proveedores > 0) ? "bloqueante" : ($estatusSku === "activo" ? "advertencia" : "informativo"),
          "titulo" => "SKU sin fiscal completo",
          "descripcion" => "Faltan datos fiscales minimos para que el SKU sea confiable en Compras/XML.",
          "fila" => $fila,
          "detalle" => array(
            "motivo" => "Fiscal incompleto",
            "proveedores_activos" => $proveedores,
            "estatus_sku" => $estatusSku
          ),
          "propuesta" => array("accion" => "capturar_clave_sat_unidad_sat_objeto_iva_ieps_incluye_impuestos")
        ), $idUsuario);
        $resumen["fiscal_incompleto"]++;
      }

      foreach ($this->consultarSkusSinProveedorActivo($db) as $fila) {
        $this->guardarIncidenciaCalidad($db, array(
          "tipo" => "sku_sin_proveedor_activo",
          "severidad" => "advertencia",
          "titulo" => "SKU activo sin proveedor activo",
          "descripcion" => "SKU activo no tiene relacion proveedor activa; Compras no debe seleccionarlo como comprable.",
          "fila" => $fila,
          "detalle" => array("motivo" => "Sin proveedor activo"),
          "propuesta" => array("accion" => "relacionar_proveedor_activo_o_inactivar_sku")
        ), $idUsuario);
        $resumen["sin_proveedor_activo"]++;
      }

      foreach ($this->consultarSkusSinCodigoPrincipal($db) as $fila) {
        $this->guardarIncidenciaCalidad($db, array(
          "tipo" => "sku_sin_codigo_principal",
          "severidad" => intval($fila["proveedores_activos"]) > 0 ? "advertencia" : "informativo",
          "titulo" => "SKU activo sin codigo principal",
          "descripcion" => "SKU activo sin codigo principal; no bloquea Compras por id_sku, pero reduce busqueda, escaneo y conciliacion.",
          "fila" => $fila,
          "detalle" => array("motivo" => "Sin codigo principal activo", "proveedores_activos" => intval($fila["proveedores_activos"])),
          "propuesta" => array("accion" => "capturar_codigo_principal_o_documentar_no_aplica")
        ), $idUsuario);
        $resumen["sin_codigo_principal"]++;
      }

      $db->commit();
      return $this->respuesta(false, "success", "Incidencias de bloqueos criticos sincronizadas", $resumen);
    } catch (Exception $e) {
      if ($db->inTransaction()) {
        $db->rollBack();
      }
      return $this->respuesta(true, "danger", $e->getMessage(), $resumen);
    }
  }

  public function sincronizarIncidenciasComprasXml($idUsuario = 0) {
    $db = $this->getConexion();
    $resumen = array(
      "producto_nuevo" => 0,
      "concepto_sin_coincidencia" => 0,
      "producto_atencion" => 0,
      "fiscal_xml_sugerido" => 0,
      "fiscal_xml_diferente" => 0
    );

    try {
      $db->beginTransaction();

      foreach ($this->consultarComprasProductoNuevo($db) as $fila) {
        $this->guardarIncidenciaCalidad($db, array(
          "tipo" => "compra_producto_nuevo",
          "origen" => "compra",
          "entidad_tipo" => "compra_detalle",
          "id_referencia" => intval($fila["id_detalle"]),
          "referencia_tipo" => "erp_compras_ordenes_detalle",
          "severidad" => "alta",
          "titulo" => "Producto nuevo pendiente de Catalogo",
          "descripcion" => "Compra contiene partida sin SKU ERP confiable; Catalogo debe alta, vincular o descartar.",
          "fila" => $fila,
          "detalle" => array(
            "motivo" => "Partida producto_nuevo o requiere revision",
            "id_orden_compra" => intval($fila["id_orden_compra"]),
            "id_proveedor" => intval($fila["id_proveedor"])
          ),
          "propuesta" => array("accion" => "crear_o_vincular_sku_erp_y_relacion_proveedor")
        ), $idUsuario);
        $resumen["producto_nuevo"]++;
      }

      foreach ($this->consultarXmlConceptosSinCoincidencia($db) as $fila) {
        $coincidenciasSugeridas = $this->sugerirCoincidenciasCatalogoXml($db, $fila);
        $this->guardarIncidenciaCalidad($db, array(
          "tipo" => "xml_concepto_sin_coincidencia",
          "origen" => "xml",
          "entidad_tipo" => "xml_concepto",
          "id_referencia" => intval($fila["id_documento_concepto"]),
          "referencia_tipo" => "erp_compras_documentos_fiscales_conceptos",
          "severidad" => "alta",
          "titulo" => "Concepto XML sin coincidencia en Catalogo",
          "descripcion" => "Concepto XML no pudo conciliarse contra partida/SKU; requiere alta, vinculacion o descarte documentado.",
          "fila" => $fila,
          "detalle" => array(
            "motivo" => "Concepto XML sin coincidencia",
            "id_documento_fiscal" => intval($fila["id_documento_fiscal"]),
            "id_orden_compra" => intval($fila["id_orden_compra"])
          ),
          "propuesta" => array(
            "accion" => "buscar_sku_existente_crear_producto_o_descartar_concepto",
            "coincidencias_sugeridas" => $coincidenciasSugeridas
          )
        ), $idUsuario);
        $resumen["concepto_sin_coincidencia"]++;
      }

      foreach ($this->consultarComprasProductosAtencionPendientes($db) as $fila) {
        $this->guardarIncidenciaCalidad($db, array(
          "tipo" => "compra_producto_atencion",
          "origen" => $fila["motivo"] === "no_incluido_xml" ? "xml" : "compra",
          "entidad_tipo" => "compra_atencion",
          "id_referencia" => intval($fila["id_producto_atencion"]),
          "referencia_tipo" => "erp_compras_ordenes_productos_atencion",
          "severidad" => "media",
          "titulo" => "Pendiente de atencion de Compras para Catalogo",
          "descripcion" => "Compras/XML genero un pendiente operativo que debe revisarse desde Catalogo cuando afecte maestros.",
          "fila" => $fila,
          "detalle" => array("motivo" => $fila["motivo"], "id_orden_compra" => intval($fila["id_orden_compra"])),
          "propuesta" => array("accion" => "resolver_pendiente_o_relacionar_con_sku_erp")
        ), $idUsuario);
        $resumen["producto_atencion"]++;
      }

      foreach ($this->consultarXmlFiscalSugerido($db) as $fila) {
        $this->guardarIncidenciaCalidad($db, array(
          "tipo" => "fiscal_xml_sugerido",
          "origen" => "xml",
          "entidad_tipo" => "xml_concepto",
          "id_referencia" => intval($fila["id_documento_concepto"]),
          "referencia_tipo" => "erp_compras_documentos_fiscales_conceptos",
          "severidad" => "advertencia",
          "titulo" => "XML sugiere fiscal para SKU incompleto",
          "descripcion" => "El XML trae datos fiscales para un SKU cuyo maestro esta vacio o incompleto; Catalogo debe validar antes de actualizar.",
          "fila" => $fila,
          "detalle" => array("motivo" => "Fiscal sugerido desde XML", "id_sku" => intval($fila["id_sku"])),
          "propuesta" => array("accion" => "validar_evidencia_xml_y_actualizar_fiscal_si_aplica")
        ), $idUsuario);
        $resumen["fiscal_xml_sugerido"]++;
      }

      foreach ($this->consultarXmlFiscalDiferente($db) as $fila) {
        $this->guardarIncidenciaCalidad($db, array(
          "tipo" => "fiscal_xml_diferente",
          "origen" => "xml",
          "entidad_tipo" => "xml_concepto",
          "id_referencia" => intval($fila["id_documento_concepto"]),
          "referencia_tipo" => "erp_compras_documentos_fiscales_conceptos",
          "severidad" => "alta",
          "titulo" => "XML difiere del fiscal maestro",
          "descripcion" => "Los datos fiscales del XML difieren del maestro del SKU; requiere decision documentada.",
          "fila" => $fila,
          "detalle" => array("motivo" => "Fiscal XML distinto al maestro", "id_sku" => intval($fila["id_sku"])),
          "propuesta" => array("accion" => "comparar_xml_maestro_y_documentar_decision")
        ), $idUsuario);
        $resumen["fiscal_xml_diferente"]++;
      }

      $db->commit();
      return $this->respuesta(false, "success", "Incidencias de Compras/XML sincronizadas", $resumen);
    } catch (Exception $e) {
      if ($db->inTransaction()) {
        $db->rollBack();
      }
      return $this->respuesta(true, "danger", $e->getMessage(), $resumen);
    }
  }

  public function listarIncidenciasCalidad($filtros = array()) {
    try {
      $db = $this->getConexion();
      $where = array();
      $params = array();

      $estatus = $this->texto($filtros, "estatus", "abiertas");
      $estatusPermitidos = array("pendiente", "en_revision", "resuelta", "descartada", "bloqueada");
      if ($estatus === "abiertas" || $estatus === "") {
        $where[] = "i.estatus IN ('pendiente','en_revision','bloqueada')";
      } elseif ($estatus !== "todas" && in_array($estatus, $estatusPermitidos, true)) {
        $where[] = "i.estatus=:estatus";
        $params[":estatus"] = $estatus;
      }

      $tipo = $this->texto($filtros, "tipo_incidencia");
      if ($tipo !== "") {
        $where[] = "i.tipo_incidencia=:tipo";
        $params[":tipo"] = $tipo;
      }
      $origen = $this->texto($filtros, "origen");
      if ($origen !== "") {
        $where[] = "i.origen=:origen";
        $params[":origen"] = $origen;
      }
      $severidad = $this->texto($filtros, "severidad");
      if ($severidad !== "") {
        $where[] = "i.severidad=:severidad";
        $params[":severidad"] = $severidad;
      }
      $idProducto = intval(isset($filtros["id_producto_erp"]) ? $filtros["id_producto_erp"] : 0);
      if ($idProducto > 0) {
        $where[] = "i.id_producto_erp=:producto";
        $params[":producto"] = $idProducto;
      }
      $idSku = intval(isset($filtros["id_sku"]) ? $filtros["id_sku"] : 0);
      if ($idSku > 0) {
        $where[] = "i.id_sku=:sku";
        $params[":sku"] = $idSku;
      }

      $condicion = empty($where) ? "1=1" : implode(" AND ", $where);
      $limite = max(1, min(200, intval(isset($filtros["limite"]) ? $filtros["limite"] : 100)));
      $offset = max(0, intval(isset($filtros["offset"]) ? $filtros["offset"] : 0));

      $stmt = $db->prepare("SELECT i.id_incidencia_calidad, i.huella, i.tipo_incidencia, i.entidad_tipo,
        i.id_producto_erp, i.id_sku, i.id_referencia, i.referencia_tipo, i.origen, i.severidad,
        i.titulo, i.descripcion, i.detalle_json, i.evidencia_json, i.propuesta_json, i.resolucion_json,
        i.estatus, i.responsable_id, i.creado_por, i.resuelto_por, i.fecha_vencimiento, i.fecha_resolucion,
        i.fecha_registro, i.fecha_actualizacion,
        p.codigo_producto, p.nombre producto, s.sku, s.nombre nombre_sku
        FROM erp_catalogo_incidencias_calidad i
        LEFT JOIN erp_catalogo_productos p ON p.id_producto_erp=i.id_producto_erp
        LEFT JOIN erp_catalogo_skus s ON s.id_sku=i.id_sku
        WHERE " . $condicion . "
        ORDER BY FIELD(i.severidad, 'bloqueante', 'alta', 'media', 'advertencia', 'informativo'),
          FIELD(i.estatus, 'pendiente', 'en_revision', 'bloqueada', 'resuelta', 'descartada'),
          COALESCE(i.fecha_actualizacion, i.fecha_registro) DESC
        LIMIT " . $limite . " OFFSET " . $offset);
      $stmt->execute($params);
      $incidencias = array_map(array($this, "normalizarIncidenciaCalidad"), $stmt->fetchAll(PDO::FETCH_ASSOC));

      $stmt = $db->prepare("SELECT COUNT(*) FROM erp_catalogo_incidencias_calidad i WHERE " . $condicion);
      $stmt->execute($params);
      $total = intval($stmt->fetchColumn());

      return $this->respuesta(false, "success", "Incidencias de calidad consultadas", array(
        "incidencias" => $incidencias,
        "total" => $total,
        "limite" => $limite,
        "offset" => $offset,
        "resumen" => $this->resumenIncidenciasCalidad($db)
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  public function cambiarEstatusIncidenciaCalidad($datos, $idUsuario = 0) {
    $id = intval(isset($datos["id_incidencia_calidad"]) ? $datos["id_incidencia_calidad"] : 0);
    $estatus = $this->opcion($datos, "estatus", array("pendiente", "en_revision", "resuelta", "descartada", "bloqueada"), "");
    $resolucion = $this->texto($datos, "resolucion");
    $responsable = intval(isset($datos["responsable_id"]) ? $datos["responsable_id"] : 0);

    if ($id <= 0 || $estatus === "") {
      return $this->respuesta(true, "warning", "Selecciona incidencia y estatus valido");
    }
    if (in_array($estatus, array("resuelta", "descartada", "bloqueada"), true) && $resolucion === "") {
      return $this->respuesta(true, "warning", "Captura la resolucion o motivo de la decision");
    }

    $db = $this->getConexion();
    try {
      $db->beginTransaction();
      $stmt = $db->prepare("SELECT * FROM erp_catalogo_incidencias_calidad WHERE id_incidencia_calidad=:id FOR UPDATE");
      $stmt->execute(array(":id" => $id));
      $actual = $stmt->fetch(PDO::FETCH_ASSOC);
      if (!$actual) {
        throw new Exception("Incidencia de calidad no encontrada");
      }

      $resolucionJson = array(
        "estatus_anterior" => $actual["estatus"],
        "estatus_nuevo" => $estatus,
        "resolucion" => $resolucion,
        "usuario_id" => intval($idUsuario) ?: null,
        "fecha" => date("c"),
        "detalle" => array(
          "responsable_id" => $responsable ?: null,
          "id_referencia" => isset($datos["id_referencia"]) ? intval($datos["id_referencia"]) : null,
          "referencia_tipo" => $this->texto($datos, "referencia_tipo")
        )
      );

      $stmt = $db->prepare("UPDATE erp_catalogo_incidencias_calidad SET
        estatus=:estatus,
        responsable_id=:responsable,
        resolucion_json=:resolucion_json,
        resuelto_por=:resuelto_por,
        fecha_resolucion=:fecha_resolucion,
        fecha_actualizacion=CURRENT_TIMESTAMP
        WHERE id_incidencia_calidad=:id");
      $final = in_array($estatus, array("resuelta", "descartada"), true);
      $stmt->execute(array(
        ":estatus" => $estatus,
        ":responsable" => $responsable ?: null,
        ":resolucion_json" => json_encode($resolucionJson, JSON_UNESCAPED_UNICODE),
        ":resuelto_por" => $final ? (intval($idUsuario) ?: null) : null,
        ":fecha_resolucion" => $final ? date("Y-m-d H:i:s") : null,
        ":id" => $id
      ));

      $db->commit();
      return $this->respuesta(false, "success", "Incidencia de calidad actualizada", array(
        "id_incidencia_calidad" => $id,
        "estatus" => $estatus,
        "antes" => array(
          "estatus" => $actual["estatus"],
          "responsable_id" => $actual["responsable_id"]
        ),
        "despues" => array(
          "estatus" => $estatus,
          "responsable_id" => $responsable ?: null
        )
      ));
    } catch (Exception $e) {
      if ($db->inTransaction()) {
        $db->rollBack();
      }
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  public function listarPropuestasCostosProveedor() {
    try {
      $db = $this->getConexion();
      $sql = "SELECT s.id_sku, s.sku, s.nombre sku_nombre, s.costo_referencia, s.id_unidad_base,
        p.id_producto_erp, p.codigo_producto, p.nombre producto,
        lp.id_producto id_producto_proveedor, lp.sku sku_proveedor, lp.costo costo_propuesto,
        l.id_proveedor, l.lista, l.fch_r, prov.proveedor,
        COALESCE(pr.precio, 0) precio_venta
        FROM erp_catalogo_skus s
        INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
        INNER JOIN erp_proveedores_listas_productos lp ON LOWER(TRIM(lp.sku))=LOWER(TRIM(s.sku))
        INNER JOIN erp_proveedores_listas l ON l.id_lista_proveedor=lp.id_lista_proveedor
        LEFT JOIN erp_proveedores prov ON prov.id_proveedor=l.id_proveedor
        LEFT JOIN erp_catalogo_sku_precios pr ON pr.id_sku=s.id_sku AND pr.lista_precio='general' AND pr.moneda='MXN' AND pr.estatus='activo'
        WHERE s.estatus<>'fusionado' AND TRIM(COALESCE(lp.sku, ''))<>'' AND lp.costo>0 AND l.id_proveedor IS NOT NULL
        ORDER BY s.id_sku, COALESCE(l.fch_r, '1000-01-01') DESC, lp.id_producto DESC";
      $agrupadas = array();
      foreach ($db->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $fila) {
        $idSku = intval($fila["id_sku"]);
        if (!isset($agrupadas[$idSku])) {
          $fila["costos_detectados"] = array();
          $fila["total_coincidencias"] = 0;
          $agrupadas[$idSku] = $fila;
        }
        $claveCosto = number_format(floatval($fila["costo_propuesto"]), 6, ".", "");
        $agrupadas[$idSku]["costos_detectados"][$claveCosto] = floatval($fila["costo_propuesto"]);
        $agrupadas[$idSku]["total_coincidencias"]++;
      }
      foreach ($agrupadas as &$propuesta) {
        $propuesta["costos_detectados"] = array_values($propuesta["costos_detectados"]);
        sort($propuesta["costos_detectados"], SORT_NUMERIC);
        $propuesta["requiere_revision"] = count($propuesta["costos_detectados"]) > 1 ? 1 : 0;
        $precio = floatval($propuesta["precio_venta"]);
        $costo = floatval($propuesta["costo_propuesto"]);
        $propuesta["margen_estimado"] = $precio > 0 ? (($precio - $costo) / $precio) * 100 : null;
      }
      unset($propuesta);
      return $this->respuesta(false, "success", "Propuestas de costos consultadas", array(
        "total" => count($agrupadas),
        "confiables" => count(array_filter($agrupadas, function ($item) { return intval($item["requiere_revision"]) === 0; })),
        "requieren_revision" => count(array_filter($agrupadas, function ($item) { return intval($item["requiere_revision"]) === 1; })),
        "propuestas" => array_values($agrupadas)
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  public function listarRelacionesProveedorHistoricas() {
    try {
      $db = $this->getConexion();
      $resumen = array(
        "productos_historicos" => intval($db->query("SELECT COUNT(*) FROM erp_proveedores_listas_productos")->fetchColumn()),
        "relaciones_erp" => intval($db->query("SELECT COUNT(*) FROM erp_catalogo_sku_proveedores WHERE estatus='activo'")->fetchColumn()),
        "coincidencias_exactas" => 0,
        "relaciones_exactas_pendientes" => 0,
        "posibles_coincidencias_nombre" => 0,
        "productos_sin_coincidencia" => 0
      );

      $sqlExactas = "SELECT COUNT(*) FROM (
        SELECT s.id_sku, l.id_proveedor
        FROM erp_proveedores_listas_productos lp
        INNER JOIN erp_proveedores_listas l ON l.id_lista_proveedor=lp.id_lista_proveedor AND l.id_proveedor IS NOT NULL
        INNER JOIN erp_catalogo_skus s ON UPPER(TRIM(s.sku))=UPPER(TRIM(lp.sku)) AND s.estatus<>'fusionado'
        WHERE TRIM(COALESCE(lp.sku, ''))<>''
        GROUP BY s.id_sku, l.id_proveedor
      ) x";
      $resumen["coincidencias_exactas"] = intval($db->query($sqlExactas)->fetchColumn());

      $sqlPendientes = "SELECT COUNT(*) FROM (
        SELECT s.id_sku, l.id_proveedor
        FROM erp_proveedores_listas_productos lp
        INNER JOIN erp_proveedores_listas l ON l.id_lista_proveedor=lp.id_lista_proveedor AND l.id_proveedor IS NOT NULL
        INNER JOIN erp_catalogo_skus s ON UPPER(TRIM(s.sku))=UPPER(TRIM(lp.sku)) AND s.estatus<>'fusionado'
        LEFT JOIN erp_catalogo_sku_proveedores sp ON sp.id_sku=s.id_sku AND sp.id_proveedor=l.id_proveedor
        WHERE TRIM(COALESCE(lp.sku, ''))<>'' AND sp.id_sku_proveedor IS NULL
        GROUP BY s.id_sku, l.id_proveedor
      ) x";
      $resumen["relaciones_exactas_pendientes"] = intval($db->query($sqlPendientes)->fetchColumn());

      $sqlPosiblesNombre = "SELECT COUNT(*) FROM (
        SELECT lp.id_producto
        FROM erp_proveedores_listas_productos lp
        INNER JOIN erp_proveedores_listas l ON l.id_lista_proveedor=lp.id_lista_proveedor AND l.id_proveedor IS NOT NULL
        INNER JOIN erp_catalogo_skus s ON UPPER(TRIM(s.nombre))=UPPER(TRIM(lp.nombre)) AND s.estatus<>'fusionado'
        WHERE TRIM(COALESCE(lp.nombre, ''))<>''
          AND NOT EXISTS (
            SELECT 1 FROM erp_catalogo_skus sx
            WHERE UPPER(TRIM(sx.sku))=UPPER(TRIM(lp.sku)) AND sx.estatus<>'fusionado'
          )
        GROUP BY lp.id_producto
      ) x";
      $resumen["posibles_coincidencias_nombre"] = intval($db->query($sqlPosiblesNombre)->fetchColumn());

      $sqlSinCoincidencia = "SELECT COUNT(*) FROM erp_proveedores_listas_productos lp
        WHERE TRIM(COALESCE(lp.sku, ''))<>''
        AND NOT EXISTS (
          SELECT 1 FROM erp_catalogo_skus s
          WHERE UPPER(TRIM(s.sku))=UPPER(TRIM(lp.sku)) AND s.estatus<>'fusionado'
        )";
      $resumen["productos_sin_coincidencia"] = intval($db->query($sqlSinCoincidencia)->fetchColumn());

      $sql = "SELECT lp.id_producto id_producto_proveedor, l.id_proveedor, prov.proveedor, l.lista,
        lp.sku sku_proveedor, lp.nombre nombre_proveedor, lp.costo, s.id_sku, s.sku, s.nombre sku_nombre,
        CASE WHEN sp.id_sku_proveedor IS NULL THEN 'pendiente' ELSE 'vinculado' END estatus_relacion
        FROM erp_proveedores_listas_productos lp
        INNER JOIN erp_proveedores_listas l ON l.id_lista_proveedor=lp.id_lista_proveedor AND l.id_proveedor IS NOT NULL
        LEFT JOIN erp_proveedores prov ON prov.id_proveedor=l.id_proveedor
        INNER JOIN erp_catalogo_skus s ON UPPER(TRIM(s.sku))=UPPER(TRIM(lp.sku)) AND s.estatus<>'fusionado'
        LEFT JOIN erp_catalogo_sku_proveedores sp ON sp.id_sku=s.id_sku AND sp.id_proveedor=l.id_proveedor
        WHERE TRIM(COALESCE(lp.sku, ''))<>'' AND sp.id_sku_proveedor IS NULL
        ORDER BY prov.proveedor, s.sku, COALESCE(l.fch_r, '1000-01-01') DESC, lp.id_producto DESC
        LIMIT 500";
      $pendientes = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

      $sqlPosibles = "SELECT lp.id_producto id_producto_proveedor, l.id_proveedor, prov.proveedor, l.lista,
        lp.sku sku_proveedor, lp.nombre nombre_proveedor, lp.costo,
        s.id_sku, s.sku, s.nombre sku_nombre,
        'posible_nombre' criterio_match
        FROM erp_proveedores_listas_productos lp
        INNER JOIN erp_proveedores_listas l ON l.id_lista_proveedor=lp.id_lista_proveedor AND l.id_proveedor IS NOT NULL
        LEFT JOIN erp_proveedores prov ON prov.id_proveedor=l.id_proveedor
        INNER JOIN erp_catalogo_skus s ON UPPER(TRIM(s.nombre))=UPPER(TRIM(lp.nombre)) AND s.estatus<>'fusionado'
        WHERE TRIM(COALESCE(lp.nombre, ''))<>''
          AND NOT EXISTS (
            SELECT 1 FROM erp_catalogo_skus sx
            WHERE UPPER(TRIM(sx.sku))=UPPER(TRIM(lp.sku)) AND sx.estatus<>'fusionado'
          )
        ORDER BY prov.proveedor, lp.nombre, COALESCE(l.fch_r, '1000-01-01') DESC, lp.id_producto DESC
        LIMIT 250";
      $posibles = $db->query($sqlPosibles)->fetchAll(PDO::FETCH_ASSOC);

      return $this->respuesta(false, "success", "Relaciones historicas de proveedor consultadas", array(
        "resumen" => $resumen,
        "pendientes" => $pendientes,
        "posibles" => $posibles,
        "estrategia_matching" => array(
          "match_exacto" => "SKU de lista proveedor igual a SKU ERP; puede sincronizarse solo si el proveedor y unidad son confiables.",
          "posible_match" => "Nombre de lista proveedor coincide con nombre de SKU ERP, pero el SKU no coincide; requiere revision manual antes de crear relacion.",
          "sin_match" => "Sin SKU exacto ni evidencia suficiente; no crear producto ni relacion sin revisar Proveedores/listas."
        )
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  public function sincronizarRelacionesProveedorHistoricas() {
    $db = $this->getConexion();
    try {
      $db->beginTransaction();
      $sql = "SELECT s.id_sku, s.id_unidad_base, l.id_proveedor, lp.sku sku_proveedor, lp.costo,
        COALESCE(l.fch_r, '1000-01-01') fecha_lista, lp.id_producto
        FROM erp_proveedores_listas_productos lp
        INNER JOIN erp_proveedores_listas l ON l.id_lista_proveedor=lp.id_lista_proveedor AND l.id_proveedor IS NOT NULL
        INNER JOIN erp_catalogo_skus s ON UPPER(TRIM(s.sku))=UPPER(TRIM(lp.sku)) AND s.estatus<>'fusionado'
        WHERE TRIM(COALESCE(lp.sku, ''))<>''
        ORDER BY s.id_sku, l.id_proveedor, COALESCE(l.fch_r, '1000-01-01') DESC, lp.id_producto DESC";
      $origenes = array();
      foreach ($db->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $fila) {
        $clave = intval($fila["id_sku"]) . ":" . intval($fila["id_proveedor"]);
        if (!isset($origenes[$clave])) {
          $origenes[$clave] = $fila;
        }
      }

      $stmtExiste = $db->prepare("SELECT id_sku_proveedor, es_preferido FROM erp_catalogo_sku_proveedores
        WHERE id_sku=:sku AND id_proveedor=:proveedor FOR UPDATE");
      $stmtGuardar = $db->prepare("INSERT INTO erp_catalogo_sku_proveedores
        (id_sku, id_proveedor, sku_proveedor, id_unidad_compra, factor_conversion, costo_ultimo, cantidad_minima, dias_entrega, es_preferido, estatus)
        VALUES (:sku, :proveedor, :sku_proveedor, :unidad, 1, :costo, 1, 0, 0, 'activo')
        ON DUPLICATE KEY UPDATE sku_proveedor=VALUES(sku_proveedor), id_unidad_compra=VALUES(id_unidad_compra),
        costo_ultimo=VALUES(costo_ultimo), estatus='activo', fecha_actualizacion=CURRENT_TIMESTAMP");
      $insertadas = 0;
      $actualizadas = 0;
      $skus = array();
      foreach ($origenes as $origen) {
        $stmtExiste->execute(array(":sku" => intval($origen["id_sku"]), ":proveedor" => intval($origen["id_proveedor"])));
        $existente = $stmtExiste->fetch(PDO::FETCH_ASSOC);
        $stmtGuardar->execute(array(
          ":sku" => intval($origen["id_sku"]),
          ":proveedor" => intval($origen["id_proveedor"]),
          ":sku_proveedor" => $origen["sku_proveedor"],
          ":unidad" => intval($origen["id_unidad_base"]),
          ":costo" => max(0, floatval($origen["costo"]))
        ));
        $existente ? $actualizadas++ : $insertadas++;
        $skus[intval($origen["id_sku"])] = true;
      }

      $preferidosAsignados = 0;
      $stmtPreferido = $db->prepare("SELECT COUNT(*) FROM erp_catalogo_sku_proveedores
        WHERE id_sku=:sku AND estatus='activo' AND es_preferido=1");
      $stmtElegir = $db->prepare("UPDATE erp_catalogo_sku_proveedores SET es_preferido=1, fecha_actualizacion=CURRENT_TIMESTAMP
        WHERE id_sku_proveedor=(
          SELECT id FROM (
            SELECT id_sku_proveedor id FROM erp_catalogo_sku_proveedores
            WHERE id_sku=:sku AND estatus='activo'
            ORDER BY costo_ultimo>0 DESC, fecha_actualizacion DESC, id_sku_proveedor ASC LIMIT 1
          ) candidato
        )");
      foreach (array_keys($skus) as $idSku) {
        $stmtPreferido->execute(array(":sku" => $idSku));
        if (intval($stmtPreferido->fetchColumn()) === 0) {
          $stmtElegir->execute(array(":sku" => $idSku));
          $preferidosAsignados += $stmtElegir->rowCount();
        }
      }

      $db->exec("UPDATE erp_catalogo_skus s
        INNER JOIN erp_catalogo_sku_proveedores sp ON sp.id_sku=s.id_sku AND sp.es_preferido=1 AND sp.estatus='activo'
        SET s.costo_referencia=sp.costo_ultimo, s.fecha_actualizacion=CURRENT_TIMESTAMP
        WHERE sp.costo_ultimo>0");

      $db->commit();
      return $this->respuesta(false, "success", "Relaciones proveedor-SKU sincronizadas", array(
        "coincidencias_procesadas" => count($origenes),
        "relaciones_insertadas" => $insertadas,
        "relaciones_actualizadas" => $actualizadas,
        "preferidos_asignados" => $preferidosAsignados
      ));
    } catch (Exception $e) {
      if ($db->inTransaction()) {
        $db->rollBack();
      }
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  /**
   * IA: Codex GPT-5 | Fecha: 2026-06-25
   * Proposito: vincula relaciones proveedor-SKU solo para renglones seleccionados por el usuario desde matches exactos.
   * Impacto: Catalogo ERP; evita sincronizacion masiva ciega y conserva unidad/factor por defecto para revision posterior.
   * Contrato: `ids_productos_proveedor` es JSON con ids de `erp_proveedores_listas_productos`; solo acepta match exacto SKU lista = SKU ERP.
   */
  public function aplicarRelacionesProveedorSeleccionadas($datos) {
    $ids = json_decode(isset($datos["ids_productos_proveedor"]) ? $datos["ids_productos_proveedor"] : "[]", true);
    if (!is_array($ids) || empty($ids)) {
      return $this->respuesta(true, "warning", "Selecciona al menos una relacion proveedor-SKU");
    }
    $ids = array_values(array_unique(array_filter(array_map("intval", $ids), function ($id) {
      return $id > 0;
    })));
    if (empty($ids)) {
      return $this->respuesta(true, "warning", "Selecciona relaciones validas");
    }
    if (count($ids) > 200) {
      return $this->respuesta(true, "warning", "Aplica como maximo 200 relaciones por operacion");
    }

    $db = $this->getConexion();
    try {
      $db->beginTransaction();
      $placeholders = implode(",", array_fill(0, count($ids), "?"));
      $sql = "SELECT lp.id_producto, s.id_sku, s.id_unidad_base, l.id_proveedor, lp.sku sku_proveedor, lp.costo
        FROM erp_proveedores_listas_productos lp
        INNER JOIN erp_proveedores_listas l ON l.id_lista_proveedor=lp.id_lista_proveedor AND l.id_proveedor IS NOT NULL
        INNER JOIN erp_catalogo_skus s ON UPPER(TRIM(s.sku))=UPPER(TRIM(lp.sku)) AND s.estatus<>'fusionado'
        LEFT JOIN erp_catalogo_sku_proveedores sp ON sp.id_sku=s.id_sku AND sp.id_proveedor=l.id_proveedor
        WHERE lp.id_producto IN ($placeholders)
          AND TRIM(COALESCE(lp.sku, ''))<>''
          AND sp.id_sku_proveedor IS NULL
        ORDER BY lp.id_producto DESC";
      $stmt = $db->prepare($sql);
      foreach ($ids as $i => $id) {
        $stmt->bindValue($i + 1, $id, PDO::PARAM_INT);
      }
      $stmt->execute();
      $candidatos = $stmt->fetchAll(PDO::FETCH_ASSOC);
      if (empty($candidatos)) {
        $db->rollBack();
        return $this->respuesta(true, "info", "No hay relaciones seleccionadas pendientes con match exacto");
      }

      $guardar = $db->prepare("INSERT INTO erp_catalogo_sku_proveedores
        (id_sku, id_proveedor, sku_proveedor, id_unidad_compra, factor_conversion, costo_ultimo, cantidad_minima, dias_entrega, es_preferido, estatus)
        VALUES (:sku, :proveedor, :sku_proveedor, :unidad, 1, :costo, 1, 0, 0, 'activo')
        ON DUPLICATE KEY UPDATE sku_proveedor=VALUES(sku_proveedor), costo_ultimo=VALUES(costo_ultimo),
          estatus='activo', fecha_actualizacion=CURRENT_TIMESTAMP");
      $insertadas = 0;
      $skus = array();
      foreach ($candidatos as $fila) {
        $guardar->execute(array(
          ":sku" => intval($fila["id_sku"]),
          ":proveedor" => intval($fila["id_proveedor"]),
          ":sku_proveedor" => $fila["sku_proveedor"],
          ":unidad" => intval($fila["id_unidad_base"]),
          ":costo" => max(0, floatval($fila["costo"]))
        ));
        $insertadas += $guardar->rowCount() > 0 ? 1 : 0;
        $skus[intval($fila["id_sku"])] = true;
      }

      $preferidos = 0;
      $conteo = $db->prepare("SELECT COUNT(*) FROM erp_catalogo_sku_proveedores WHERE id_sku=:sku AND estatus='activo' AND es_preferido=1");
      $unico = $db->prepare("SELECT id_sku_proveedor FROM erp_catalogo_sku_proveedores WHERE id_sku=:sku AND estatus='activo'");
      $marcar = $db->prepare("UPDATE erp_catalogo_sku_proveedores SET es_preferido=1, fecha_actualizacion=CURRENT_TIMESTAMP WHERE id_sku_proveedor=:relacion");
      foreach (array_keys($skus) as $idSku) {
        $conteo->execute(array(":sku" => $idSku));
        if (intval($conteo->fetchColumn()) > 0) {
          continue;
        }
        $unico->execute(array(":sku" => $idSku));
        $relaciones = $unico->fetchAll(PDO::FETCH_COLUMN);
        if (count($relaciones) === 1) {
          $marcar->execute(array(":relacion" => intval($relaciones[0])));
          $preferidos += $marcar->rowCount();
        }
      }

      $db->commit();
      return $this->respuesta(false, "success", "Relaciones proveedor-SKU seleccionadas aplicadas", array(
        "seleccionadas" => count($ids),
        "matches_aplicados" => count($candidatos),
        "relaciones_insertadas" => $insertadas,
        "preferidos_asignados" => $preferidos,
        "nota" => "Unidad compra=factor base actual; revisa granel/cajas antes de usar en compras."
      ));
    } catch (Exception $e) {
      if ($db->inTransaction()) {
        $db->rollBack();
      }
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  public function aplicarPropuestasCostosProveedor($datos) {
    $items = json_decode(isset($datos["items"]) ? $datos["items"] : "[]", true);
    if (!is_array($items) || empty($items)) {
      return $this->respuesta(true, "warning", "Selecciona al menos una propuesta de costo");
    }
    if (count($items) > 1000) {
      return $this->respuesta(true, "warning", "Aplica como maximo 1000 costos por operacion");
    }

    $db = $this->getConexion();
    try {
      $db->beginTransaction();
      $actualizados = 0;
      foreach ($items as $item) {
        $idSku = intval(isset($item["id_sku"]) ? $item["id_sku"] : 0);
        $idProductoProveedor = intval(isset($item["id_producto_proveedor"]) ? $item["id_producto_proveedor"] : 0);
        $costo = isset($item["costo"]) && is_numeric($item["costo"]) ? floatval($item["costo"]) : 0;
        if ($idSku <= 0 || $idProductoProveedor <= 0 || $costo <= 0) {
          throw new Exception("Una propuesta seleccionada contiene datos invalidos");
        }

        $stmt = $db->prepare("SELECT s.id_unidad_base, l.id_proveedor, lp.sku sku_proveedor, lp.costo
          FROM erp_catalogo_skus s
          INNER JOIN erp_proveedores_listas_productos lp ON lp.id_producto=:producto_proveedor
            AND LOWER(TRIM(lp.sku))=LOWER(TRIM(s.sku))
          INNER JOIN erp_proveedores_listas l ON l.id_lista_proveedor=lp.id_lista_proveedor
          WHERE s.id_sku=:sku AND s.estatus<>'fusionado' AND l.id_proveedor IS NOT NULL
          LIMIT 1 FOR UPDATE");
        $stmt->execute(array(":producto_proveedor" => $idProductoProveedor, ":sku" => $idSku));
        $origen = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$origen) {
          throw new Exception("La propuesta ya no coincide con el SKU ERP");
        }
        if (abs(floatval($origen["costo"]) - $costo) > 0.0001) {
          throw new Exception("El costo propuesto cambio; actualiza la bandeja antes de aplicar");
        }

        $db->prepare("UPDATE erp_catalogo_skus SET costo_referencia=:costo, fecha_actualizacion=CURRENT_TIMESTAMP WHERE id_sku=:sku")
          ->execute(array(":costo" => $costo, ":sku" => $idSku));
        $db->prepare("UPDATE erp_catalogo_sku_proveedores SET es_preferido=0 WHERE id_sku=:sku")
          ->execute(array(":sku" => $idSku));
        $stmt = $db->prepare("INSERT INTO erp_catalogo_sku_proveedores
          (id_sku, id_proveedor, sku_proveedor, id_unidad_compra, factor_conversion, costo_ultimo, cantidad_minima, dias_entrega, es_preferido, estatus)
          VALUES (:sku, :proveedor, :sku_proveedor, :unidad, 1, :costo, 1, 0, 1, 'activo')
          ON DUPLICATE KEY UPDATE sku_proveedor=VALUES(sku_proveedor), id_unidad_compra=VALUES(id_unidad_compra),
          costo_ultimo=VALUES(costo_ultimo), es_preferido=1, estatus='activo', fecha_actualizacion=CURRENT_TIMESTAMP");
        $stmt->execute(array(
          ":sku" => $idSku,
          ":proveedor" => intval($origen["id_proveedor"]),
          ":sku_proveedor" => $origen["sku_proveedor"],
          ":unidad" => intval($origen["id_unidad_base"]),
          ":costo" => $costo
        ));
        $actualizados++;
      }
      $db->commit();
      return $this->respuesta(false, "success", "Costos de proveedor aplicados", array("actualizados" => $actualizados));
    } catch (Exception $e) {
      if ($db->inTransaction()) {
        $db->rollBack();
      }
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  public function sincronizarCostosProveedor() {
    $propuestas = $this->listarPropuestasCostosProveedor();
    if ($propuestas["error"]) {
      return $propuestas;
    }
    $items = array();
    foreach ($propuestas["depurar"]["propuestas"] as $propuesta) {
      $items[] = array(
        "id_sku" => $propuesta["id_sku"],
        "id_producto_proveedor" => $propuesta["id_producto_proveedor"],
        "costo" => $propuesta["costo_propuesto"]
      );
    }
    if (empty($items)) {
      return $this->respuesta(false, "success", "No hay costos nuevos para sincronizar", array("actualizados" => 0));
    }
    return $this->aplicarPropuestasCostosProveedor(array("items" => json_encode($items)));
  }

  public function sincronizarMetadatosCatalogo() {
    $db = $this->getConexion();
    $resumen = array(
      "marcas_asignadas" => 0,
      "marcas_creadas" => 0,
      "marcas_ambiguas" => 0,
      "categorias_asignadas" => 0,
      "categorias_creadas" => 0,
      "origen_categorias" => "sin_origen"
    );
    try {
      $db->beginTransaction();
      $sql = "SELECT p.id_producto_erp, TRIM(lp.marca) marca
        FROM erp_catalogo_productos p
        INNER JOIN erp_catalogo_skus s ON s.id_producto_erp=p.id_producto_erp
        INNER JOIN erp_proveedores_listas_productos lp ON LOWER(TRIM(lp.sku))=LOWER(TRIM(s.sku))
        WHERE p.estatus<>'fusionado' AND p.id_marca_erp IS NULL AND TRIM(COALESCE(lp.marca, ''))<>''
        GROUP BY p.id_producto_erp, TRIM(lp.marca)
        ORDER BY p.id_producto_erp";
      $marcasProducto = array();
      foreach ($db->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $fila) {
        $marcasProducto[intval($fila["id_producto_erp"])][strtolower($fila["marca"])] = $fila["marca"];
      }
      foreach ($marcasProducto as $idProducto => $marcas) {
        if (count($marcas) !== 1) {
          $resumen["marcas_ambiguas"]++;
          continue;
        }
        $nombreMarca = reset($marcas);
        $stmt = $db->prepare("SELECT id_marca_erp FROM erp_catalogo_marcas WHERE LOWER(TRIM(nombre))=LOWER(TRIM(:nombre)) LIMIT 1");
        $stmt->execute(array(":nombre" => $nombreMarca));
        $idMarca = intval($stmt->fetchColumn());
        if ($idMarca <= 0) {
          $codigo = "PROV-MAR-" . strtoupper(substr(md5(strtolower($nombreMarca)), 0, 12));
          $stmt = $db->prepare("INSERT INTO erp_catalogo_marcas (codigo, nombre, descripcion, estatus)
            VALUES (:codigo, :nombre, 'Sincronizada desde lista de proveedor', 'activa')
            ON DUPLICATE KEY UPDATE id_marca_erp=LAST_INSERT_ID(id_marca_erp), estatus='activa'");
          $stmt->execute(array(":codigo" => $codigo, ":nombre" => $nombreMarca));
          $idMarca = intval($db->lastInsertId());
          $resumen["marcas_creadas"]++;
        }
        $stmt = $db->prepare("UPDATE erp_catalogo_productos SET id_marca_erp=:marca, fecha_actualizacion=CURRENT_TIMESTAMP
          WHERE id_producto_erp=:producto AND id_marca_erp IS NULL");
        $stmt->execute(array(":marca" => $idMarca, ":producto" => $idProducto));
        $resumen["marcas_asignadas"] += $stmt->rowCount();
      }

      $origenes = array();
      if ($this->tablaExisteCatalogo($db, "ecom_productos_categorias") && $this->tablaExisteCatalogo($db, "ecom_categorias")) {
        $origenes["ecommerce_local"] = "";
      }
      if ($this->tablaExisteCatalogo($db, "ecom_productos_categorias", "artianilocal_productivo_staging")
        && $this->tablaExisteCatalogo($db, "ecom_categorias", "artianilocal_productivo_staging")) {
        $origenes["staging_productivo"] = "artianilocal_productivo_staging.";
      }
      $origenesUsados = array();
      foreach ($origenes as $origen => $prefijo) {
        $sql = "SELECT DISTINCT p.id_producto_erp, c.id_categoria id_categoria_ecom, c.categoria
          FROM erp_catalogo_productos p
          INNER JOIN erp_catalogo_canales_vinculos v ON v.id_producto_erp=p.id_producto_erp AND v.canal='ecommerce'
          INNER JOIN " . $prefijo . "ecom_productos_categorias ec ON ec.id_producto=CAST(v.id_externo AS UNSIGNED)
          INNER JOIN " . $prefijo . "ecom_categorias c ON c.id_categoria=ec.id_categoria
          WHERE p.estatus<>'fusionado'
            AND NOT EXISTS (SELECT 1 FROM erp_catalogo_producto_categorias pc WHERE pc.id_producto_erp=p.id_producto_erp)
          ORDER BY p.id_producto_erp, ec.id_producto_categoria";
        $principal = array();
        foreach ($db->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $fila) {
          $codigo = "ECOM-CAT-" . intval($fila["id_categoria_ecom"]);
          $stmt = $db->prepare("SELECT id_categoria_erp FROM erp_catalogo_categorias WHERE codigo=:codigo LIMIT 1");
          $stmt->execute(array(":codigo" => $codigo));
          $idCategoria = intval($stmt->fetchColumn());
          if ($idCategoria <= 0) {
            $stmt = $db->prepare("INSERT INTO erp_catalogo_categorias
              (codigo, nombre, ruta, nivel, tipo_categoria, origen, permite_productos, estatus)
              VALUES (:codigo, :nombre, :ruta, 0, 'legado_canal', 'ecommerce', 1, 'activa')
              ON DUPLICATE KEY UPDATE id_categoria_erp=LAST_INSERT_ID(id_categoria_erp), nombre=VALUES(nombre),
                tipo_categoria='legado_canal', origen='ecommerce', permite_productos=1, estatus='activa'");
            $stmt->execute(array(":codigo" => $codigo, ":nombre" => $fila["categoria"], ":ruta" => $fila["categoria"]));
            $idCategoria = intval($db->lastInsertId());
            $resumen["categorias_creadas"]++;
          }
          $idProducto = intval($fila["id_producto_erp"]);
          $stmt = $db->prepare("INSERT IGNORE INTO erp_catalogo_producto_categorias
            (id_producto_erp, id_categoria_erp, es_principal) VALUES (:producto, :categoria, :principal)");
          $stmt->execute(array(
            ":producto" => $idProducto,
            ":categoria" => $idCategoria,
            ":principal" => isset($principal[$idProducto]) ? 0 : 1
          ));
          if ($stmt->rowCount() > 0) {
            $resumen["categorias_asignadas"]++;
            $principal[$idProducto] = true;
            $origenesUsados[$origen] = true;
          }
        }
      }
      if (!empty($origenesUsados)) {
        $resumen["origen_categorias"] = implode("+", array_keys($origenesUsados));
      } elseif (!empty($origenes)) {
        $resumen["origen_categorias"] = implode("+", array_keys($origenes));
      }
      $db->commit();
      return $this->respuesta(false, "success", "Metadatos del catalogo sincronizados", $resumen);
    } catch (Exception $e) {
      if ($db->inTransaction()) {
        $db->rollBack();
      }
      return $this->respuesta(true, "danger", $e->getMessage(), $resumen);
    }
  }

  /**
   * IA: Codex GPT-5 | Fecha: 2026-06-29
   * Proposito: listar pendientes de categoria principal y marca para saneamiento operativo desde Configuracion.
   * Impacto: Catalogo ERP; alimenta la bandeja de clasificacion pendiente sin escribir datos.
   * Contrato: read-only; considera pendiente a un producto sin categoria principal, aunque tenga categorias secundarias.
   */
  public function listarRevisionMetadatosCatalogo() {
    try {
      $db = $this->getConexion();
      $sinCategoria = $db->query("SELECT p.id_producto_erp, p.codigo_producto, p.nombre,
        GROUP_CONCAT(DISTINCT s.sku ORDER BY s.sku SEPARATOR ' | ') skus,
        'categoria' tipo_revision
        FROM erp_catalogo_productos p
        LEFT JOIN erp_catalogo_skus s ON s.id_producto_erp=p.id_producto_erp AND s.estatus<>'fusionado'
        WHERE p.estatus<>'fusionado'
          AND NOT EXISTS (SELECT 1 FROM erp_catalogo_producto_categorias pc WHERE pc.id_producto_erp=p.id_producto_erp AND pc.es_principal=1)
        GROUP BY p.id_producto_erp, p.codigo_producto, p.nombre
        ORDER BY p.nombre")->fetchAll(PDO::FETCH_ASSOC);

      $sql = "SELECT p.id_producto_erp, p.codigo_producto, p.nombre,
        GROUP_CONCAT(DISTINCT s.sku ORDER BY s.sku SEPARATOR ' | ') skus,
        GROUP_CONCAT(DISTINCT TRIM(lp.marca) ORDER BY TRIM(lp.marca) SEPARATOR ' | ') marcas_candidatas,
        'marca' tipo_revision
        FROM erp_catalogo_productos p
        INNER JOIN erp_catalogo_skus s ON s.id_producto_erp=p.id_producto_erp
        INNER JOIN erp_proveedores_listas_productos lp ON LOWER(TRIM(lp.sku))=LOWER(TRIM(s.sku))
        WHERE p.estatus<>'fusionado' AND p.id_marca_erp IS NULL AND TRIM(COALESCE(lp.marca, ''))<>''
        GROUP BY p.id_producto_erp, p.codigo_producto, p.nombre
        HAVING COUNT(DISTINCT LOWER(TRIM(lp.marca)))>1
        ORDER BY p.nombre";
      $marcasAmbiguas = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

      return $this->respuesta(false, "success", "Pendientes de clasificacion consultados", array(
        "sin_categoria" => count($sinCategoria),
        "marcas_ambiguas" => count($marcasAmbiguas),
        "pendientes" => array_merge($sinCategoria, $marcasAmbiguas),
        "categorias" => $db->query("SELECT id_categoria_erp, nombre, ruta FROM erp_catalogo_categorias
          WHERE estatus='activa' AND tipo_categoria='maestra' AND permite_productos=1
          ORDER BY COALESCE(ruta, nombre), nombre")->fetchAll(PDO::FETCH_ASSOC),
        "marcas" => $db->query("SELECT id_marca_erp, nombre FROM erp_catalogo_marcas WHERE estatus='activa' ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC)
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  /**
   * IA: Codex GPT-5 | Fecha: 2026-06-25
   * Proposito: aplica marca/categoria por lote; permite forzar categoria principal cuando la UI masiva lo solicita.
   * Impacto: Catalogo ERP; acelera saneamiento post-migracion sin alterar productos fusionados.
   */
  public function aplicarRevisionMetadatosCatalogo($datos) {
    $asignaciones = json_decode(isset($datos["asignaciones"]) ? $datos["asignaciones"] : "[]", true);
    if (!is_array($asignaciones) || empty($asignaciones)) {
      return $this->respuesta(true, "warning", "Selecciona al menos una categoria o marca");
    }
    if (count($asignaciones) > 500) {
      return $this->respuesta(true, "warning", "Aplica como maximo 500 asignaciones por operacion");
    }

    $db = $this->getConexion();
    try {
      $db->beginTransaction();
      $categorias = 0;
      $marcas = 0;
      foreach ($asignaciones as $asignacion) {
        $idProducto = intval(isset($asignacion["id_producto_erp"]) ? $asignacion["id_producto_erp"] : 0);
        $idCategoria = intval(isset($asignacion["id_categoria_erp"]) ? $asignacion["id_categoria_erp"] : 0);
        $idMarca = intval(isset($asignacion["id_marca_erp"]) ? $asignacion["id_marca_erp"] : 0);
        $forzarPrincipal = intval(isset($asignacion["forzar_categoria_principal"]) ? $asignacion["forzar_categoria_principal"] : 0) === 1;
        if ($idProducto <= 0 || ($idCategoria <= 0 && $idMarca <= 0)) {
          throw new Exception("Una asignacion contiene datos invalidos");
        }
        $stmt = $db->prepare("SELECT id_producto_erp FROM erp_catalogo_productos WHERE id_producto_erp=:producto AND estatus<>'fusionado' FOR UPDATE");
        $stmt->execute(array(":producto" => $idProducto));
        if (!$stmt->fetchColumn()) {
          throw new Exception("Uno de los productos ya no esta disponible");
        }

        if ($idCategoria > 0) {
          $stmt = $db->prepare("SELECT id_categoria_erp FROM erp_catalogo_categorias WHERE id_categoria_erp=:categoria AND estatus='activa' AND tipo_categoria='maestra' AND permite_productos=1");
          $stmt->execute(array(":categoria" => $idCategoria));
          if (!$stmt->fetchColumn()) {
            throw new Exception("Una categoria seleccionada no esta activa o no permite productos");
          }
          if ($forzarPrincipal) {
            $stmt = $db->prepare("UPDATE erp_catalogo_producto_categorias SET es_principal=0 WHERE id_producto_erp=:producto");
            $stmt->execute(array(":producto" => $idProducto));
          }
          $stmt = $db->prepare("SELECT COUNT(*) FROM erp_catalogo_producto_categorias WHERE id_producto_erp=:producto");
          $stmt->execute(array(":producto" => $idProducto));
          $esPrincipal = ($forzarPrincipal || intval($stmt->fetchColumn()) === 0) ? 1 : 0;
          $stmt = $db->prepare("INSERT INTO erp_catalogo_producto_categorias (id_producto_erp, id_categoria_erp, es_principal)
            VALUES (:producto, :categoria, :principal)
            ON DUPLICATE KEY UPDATE es_principal=GREATEST(es_principal, VALUES(es_principal))");
          $stmt->execute(array(":producto" => $idProducto, ":categoria" => $idCategoria, ":principal" => $esPrincipal));
          $categorias++;
        }
        if ($idMarca > 0) {
          $stmt = $db->prepare("SELECT id_marca_erp FROM erp_catalogo_marcas WHERE id_marca_erp=:marca AND estatus='activa'");
          $stmt->execute(array(":marca" => $idMarca));
          if (!$stmt->fetchColumn()) {
            throw new Exception("Una marca seleccionada no esta activa");
          }
          $stmt = $db->prepare("UPDATE erp_catalogo_productos SET id_marca_erp=:marca, fecha_actualizacion=CURRENT_TIMESTAMP WHERE id_producto_erp=:producto");
          $stmt->execute(array(":marca" => $idMarca, ":producto" => $idProducto));
          $marcas++;
        }
      }
      $db->commit();
      return $this->respuesta(false, "success", "Clasificacion del catalogo actualizada", array(
        "categorias_asignadas" => $categorias,
        "marcas_asignadas" => $marcas
      ));
    } catch (Exception $e) {
      if ($db->inTransaction()) {
        $db->rollBack();
      }
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  /**
   * IA: Codex GPT-5 | Fecha: 2026-07-17
   * Proposito: actualizar por lote el estado de vida del producto maestro sin abrir cada ficha.
   * Impacto: Catalogo ERP; acelera saneamiento operativo y conserva `fusionado` reservado al flujo de fusion.
   * Contrato: `ids_productos` JSON, maximo 250 productos, `estatus` en borrador/en_revision/activo/inactivo/descontinuado.
   */
  public function actualizarEstatusProductosMasivo($datos, $idUsuario) {
    $ids = json_decode(isset($datos["ids_productos"]) ? $datos["ids_productos"] : "[]", true);
    if (!is_array($ids) || empty($ids)) {
      return $this->respuesta(true, "warning", "Selecciona al menos un producto");
    }
    $ids = array_values(array_unique(array_filter(array_map("intval", $ids), function ($id) {
      return $id > 0;
    })));
    if (empty($ids)) {
      return $this->respuesta(true, "warning", "Selecciona productos validos");
    }
    if (count($ids) > 250) {
      return $this->respuesta(true, "warning", "Aplica como maximo 250 productos por operacion");
    }
    $estatusSolicitado = $this->texto($datos, "estatus");
    if (!in_array($estatusSolicitado, array("borrador", "en_revision", "activo", "inactivo", "descontinuado"), true)) {
      return $this->respuesta(true, "warning", "Selecciona un estado maestro valido");
    }

    $db = $this->getConexion();
    try {
      $db->beginTransaction();
      $placeholders = implode(",", array_fill(0, count($ids), "?"));
      $stmt = $db->prepare("SELECT id_producto_erp, estatus FROM erp_catalogo_productos
        WHERE id_producto_erp IN ($placeholders)
        FOR UPDATE");
      foreach ($ids as $idx => $idProducto) {
        $stmt->bindValue($idx + 1, $idProducto, PDO::PARAM_INT);
      }
      $stmt->execute();
      $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
      if (empty($productos)) {
        throw new Exception("No se encontraron productos seleccionados");
      }
      $actualizables = array();
      $fusionados = 0;
      foreach ($productos as $producto) {
        if ((string) $producto["estatus"] === "fusionado") {
          $fusionados++;
          continue;
        }
        $actualizables[] = intval($producto["id_producto_erp"]);
      }
      if (empty($actualizables)) {
        throw new Exception("Los productos seleccionados no pueden cambiarse manualmente");
      }
      $placeholdersUpdate = implode(",", array_fill(0, count($actualizables), "?"));
      $actualizar = $db->prepare("UPDATE erp_catalogo_productos
        SET estatus=?, actualizado_por=?, fecha_actualizacion=CURRENT_TIMESTAMP
        WHERE id_producto_erp IN ($placeholdersUpdate)
          AND estatus<>'fusionado'");
      $actualizar->bindValue(1, $estatusSolicitado);
      if (intval($idUsuario) > 0) {
        $actualizar->bindValue(2, intval($idUsuario), PDO::PARAM_INT);
      } else {
        $actualizar->bindValue(2, null, PDO::PARAM_NULL);
      }
      foreach ($actualizables as $idx => $idProducto) {
        $actualizar->bindValue($idx + 3, $idProducto, PDO::PARAM_INT);
      }
      $actualizar->execute();
      $db->commit();
      return $this->respuesta(false, "success", "Estado maestro actualizado", array(
        "productos_seleccionados" => count($ids),
        "productos_encontrados" => count($productos),
        "productos_actualizados" => $actualizar->rowCount(),
        "fusionados_omitidos" => $fusionados,
        "estatus" => $estatusSolicitado
      ));
    } catch (Exception $e) {
      if ($db->inTransaction()) {
        $db->rollBack();
      }
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  public function listarPropuestasReorden($politica = array()) {
    $niveles = array(
      "AAA" => array("minimo" => 6, "reorden" => 6, "maximo" => 12),
      "AA" => array("minimo" => 3, "reorden" => 3, "maximo" => 6),
      "A" => array("minimo" => 1, "reorden" => 1, "maximo" => 3),
      "PRODUCTO DE IMPULSO" => array("minimo" => 2, "reorden" => 2, "maximo" => 4)
    );
    foreach ($niveles as $clave => $valores) {
      $prefijo = strtolower(str_replace(" ", "_", $clave));
      foreach (array("minimo", "reorden", "maximo") as $campo) {
        if (isset($politica[$prefijo . "_" . $campo]) && is_numeric($politica[$prefijo . "_" . $campo])) {
          $niveles[$clave][$campo] = max(0, floatval($politica[$prefijo . "_" . $campo]));
        }
      }
    }

    try {
      $db = $this->getConexion();
      $sql = "SELECT s.id_sku, s.sku, s.nombre sku_nombre, p.id_producto_erp, p.codigo_producto, p.nombre producto,
        r.stock_minimo actual_minimo, r.punto_reorden actual_reorden, r.stock_maximo actual_maximo,
        UPPER(TRIM(lp.rotacion)) rotacion, lp.existencia existencia_proveedor, lp.id_producto id_producto_proveedor,
        l.lista, prov.proveedor
        FROM erp_catalogo_skus s
        INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
        INNER JOIN erp_catalogo_sku_reglas_inventario r ON r.id_sku=s.id_sku AND r.controla_inventario=1
        INNER JOIN erp_proveedores_listas_productos lp ON LOWER(TRIM(lp.sku))=LOWER(TRIM(s.sku))
        INNER JOIN erp_proveedores_listas l ON l.id_lista_proveedor=lp.id_lista_proveedor
        LEFT JOIN erp_proveedores prov ON prov.id_proveedor=l.id_proveedor
        WHERE s.estatus<>'fusionado' AND UPPER(TRIM(lp.rotacion)) IN ('AAA', 'AA', 'A', 'PRODUCTO DE IMPULSO')
        ORDER BY s.id_sku, COALESCE(l.fch_r, '1000-01-01') DESC, lp.id_producto DESC";
      $propuestas = array();
      foreach ($db->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $fila) {
        $idSku = intval($fila["id_sku"]);
        if (isset($propuestas[$idSku])) {
          continue;
        }
        $nivel = $niveles[$fila["rotacion"]];
        $fila["propuesto_minimo"] = $nivel["minimo"];
        $fila["propuesto_reorden"] = $nivel["reorden"];
        $fila["propuesto_maximo"] = max($nivel["maximo"], $nivel["reorden"]);
        $propuestas[$idSku] = $fila;
      }
      return $this->respuesta(false, "success", "Propuestas de reorden consultadas", array(
        "politica" => $niveles,
        "total" => count($propuestas),
        "propuestas" => array_values($propuestas)
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  public function aplicarPropuestasReorden($datos) {
    $ids = json_decode(isset($datos["ids_sku"]) ? $datos["ids_sku"] : "[]", true);
    if (!is_array($ids) || empty($ids)) {
      return $this->respuesta(true, "warning", "Selecciona al menos un SKU para actualizar");
    }
    $propuestas = $this->listarPropuestasReorden($datos);
    if ($propuestas["error"]) {
      return $propuestas;
    }
    $permitidas = array();
    foreach ($propuestas["depurar"]["propuestas"] as $propuesta) {
      $permitidas[intval($propuesta["id_sku"])] = $propuesta;
    }

    $db = $this->getConexion();
    try {
      $db->beginTransaction();
      $stmt = $db->prepare("UPDATE erp_catalogo_sku_reglas_inventario
        SET stock_minimo=:minimo, punto_reorden=:reorden, stock_maximo=:maximo, fecha_actualizacion=CURRENT_TIMESTAMP
        WHERE id_sku=:sku AND controla_inventario=1");
      $actualizados = 0;
      foreach (array_unique(array_map("intval", $ids)) as $idSku) {
        if (!isset($permitidas[$idSku])) {
          throw new Exception("Una propuesta de reorden ya no es valida");
        }
        $p = $permitidas[$idSku];
        $stmt->execute(array(
          ":minimo" => $p["propuesto_minimo"],
          ":reorden" => $p["propuesto_reorden"],
          ":maximo" => $p["propuesto_maximo"],
          ":sku" => $idSku
        ));
        $actualizados += $stmt->rowCount() > 0 ? 1 : 0;
      }
      $db->commit();
      return $this->respuesta(false, "success", "Reglas de reorden actualizadas", array("actualizados" => $actualizados));
    } catch (Exception $e) {
      if ($db->inTransaction()) {
        $db->rollBack();
      }
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-11
   * Proposito: guarda catalogos maestros y autogenera codigo para marca/categoria cuando el usuario captura solo nombre.
   * Impacto: Configuracion de Catalogo ERP; facilita CRUD operativo sin relajar codigos de unidades/atributos.
   */
  public function guardarCatalogoAuxiliar($tipo, $datos) {
    $permitidos = array("marca", "categoria", "unidad", "atributo");
    if (!in_array($tipo, $permitidos, true)) {
      return $this->respuesta(true, "warning", "Tipo de catálogo no permitido");
    }
    if ($this->texto($datos, "codigo") === "" && in_array($tipo, array("marca", "categoria"), true) && $this->texto($datos, "nombre") !== "") {
      $datos["codigo"] = $this->codigoDesdeTexto($this->texto($datos, "nombre"), $tipo === "marca" ? "MAR" : "CAT");
    }
    if ($this->texto($datos, "codigo") === "" || $this->texto($datos, "nombre") === "") {
      return $this->respuesta(true, "warning", "Código y nombre son obligatorios");
    }

    try {
      if ($tipo === "marca") {
        return $this->guardarMarca($datos);
      }
      if ($tipo === "categoria") {
        return $this->guardarCategoria($datos);
      }
      if ($tipo === "unidad") {
        return $this->guardarUnidad($datos);
      }
      return $this->guardarAtributo($datos);
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getCode() === "23000" ? "El código o nombre ya está registrado" : $e->getMessage());
    }
  }

  private function problemasCatalogo($db, $problema, $severidad, $sql) {
    $filas = array();
    foreach ($db->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $fila) {
      $fila["problema"] = $problema;
      $fila["severidad"] = $severidad;
      $filas[] = $fila;
    }
    return $filas;
  }

  private function consultarSkusReglaDefaultGenerica($db) {
    return $db->query("SELECT p.id_producto_erp, s.id_sku, p.codigo_producto, p.nombre producto, s.sku, s.nombre nombre_sku,
      s.tipo_inventario, r.stock_minimo, r.stock_maximo, r.punto_reorden, r.estrategia_salida,
      r.requiere_lote, r.requiere_caducidad, r.requiere_serie, r.requiere_serie_fabricante,
      r.generar_etiqueta_interna, r.requiere_escaneo_venta
      FROM erp_catalogo_skus s
      INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
      INNER JOIN erp_catalogo_sku_reglas_inventario r ON r.id_sku=s.id_sku
      WHERE s.estatus<>'fusionado' AND s.tipo_inventario='inventariable' AND r.controla_inventario=1
        AND r.requiere_lote=0 AND r.requiere_caducidad=0 AND r.requiere_serie=0
        AND r.estrategia_salida='FIFO' AND r.stock_minimo<=0 AND r.punto_reorden<=0
      ORDER BY s.id_sku")->fetchAll(PDO::FETCH_ASSOC);
  }

  private function consultarSkusReordenCero($db) {
    return $db->query("SELECT p.id_producto_erp, s.id_sku, p.codigo_producto, p.nombre producto, s.sku, s.nombre nombre_sku,
      s.tipo_inventario, r.stock_minimo, r.stock_maximo, r.punto_reorden,
      (SELECT COUNT(*) FROM erp_catalogo_sku_proveedores sp WHERE sp.id_sku=s.id_sku AND sp.estatus='activo') proveedores_activos
      FROM erp_catalogo_skus s
      INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
      INNER JOIN erp_catalogo_sku_reglas_inventario r ON r.id_sku=s.id_sku
      WHERE s.estatus<>'fusionado' AND s.tipo_inventario NOT IN ('servicio','cargo')
        AND r.controla_inventario=1 AND r.punto_reorden<=0
      ORDER BY proveedores_activos DESC, s.id_sku")->fetchAll(PDO::FETCH_ASSOC);
  }

  private function consultarSkusPosibleCargoServicio($db) {
    return $db->query("SELECT p.id_producto_erp, s.id_sku, p.codigo_producto, p.nombre producto, s.sku, s.nombre nombre_sku,
      s.tipo_inventario, r.controla_inventario
      FROM erp_catalogo_skus s
      INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
      INNER JOIN erp_catalogo_sku_reglas_inventario r ON r.id_sku=s.id_sku
      WHERE s.estatus<>'fusionado' AND r.controla_inventario=1
        AND CONCAT(' ', p.nombre, ' ', s.nombre, ' ', s.sku, ' ') REGEXP 'envio|flete|paqueteria|maniobra|seguro|empaque|servicio|cargo|gasto'
      ORDER BY s.id_sku")->fetchAll(PDO::FETCH_ASSOC);
  }

  private function consultarSkusPosibleLoteCaducidad($db) {
    return $db->query("SELECT p.id_producto_erp, s.id_sku, p.codigo_producto, p.nombre producto, s.sku, s.nombre nombre_sku,
      s.tipo_inventario, r.requiere_lote, r.requiere_caducidad, r.estrategia_salida
      FROM erp_catalogo_skus s
      INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
      INNER JOIN erp_catalogo_sku_reglas_inventario r ON r.id_sku=s.id_sku
      WHERE s.estatus<>'fusionado' AND r.controla_inventario=1
        AND r.requiere_lote=0 AND r.requiere_caducidad=0
        AND CONCAT(' ', p.nombre, ' ', s.nombre, ' ', s.sku, ' ') REGEXP 'alimento|medic|vacuna|suplement|vitamina|desparasit|antibiot|caduc|premio'
      ORDER BY s.id_sku")->fetchAll(PDO::FETCH_ASSOC);
  }

  private function consultarProductosVariantesSinAtributos($db) {
    return $db->query("SELECT p.id_producto_erp, NULL id_sku, p.codigo_producto, p.nombre producto, NULL sku,
      COUNT(s.id_sku) skus_activos
      FROM erp_catalogo_productos p
      INNER JOIN erp_catalogo_skus s ON s.id_producto_erp=p.id_producto_erp AND s.estatus<>'fusionado'
      WHERE p.estatus<>'fusionado' AND p.maneja_variantes=1
      GROUP BY p.id_producto_erp, p.codigo_producto, p.nombre
      HAVING COUNT(s.id_sku)>1
        AND NOT EXISTS (
          SELECT 1
          FROM erp_catalogo_skus sx
          INNER JOIN erp_catalogo_sku_atributos sa ON sa.id_sku=sx.id_sku
          INNER JOIN erp_catalogo_atributos a ON a.id_atributo_erp=sa.id_atributo_erp AND a.es_variante=1 AND a.estatus='activo'
          WHERE sx.id_producto_erp=p.id_producto_erp AND sx.estatus<>'fusionado'
        )
      ORDER BY p.id_producto_erp")->fetchAll(PDO::FETCH_ASSOC);
  }

  private function consultarSkusVariantesValoresIncompletos($db) {
    return $db->query("SELECT p.id_producto_erp, s.id_sku, p.codigo_producto, p.nombre producto, s.sku, s.nombre nombre_sku,
      GROUP_CONCAT(av.nombre ORDER BY av.nombre SEPARATOR ', ') atributos_faltantes
      FROM erp_catalogo_productos p
      INNER JOIN erp_catalogo_skus s ON s.id_producto_erp=p.id_producto_erp AND s.estatus<>'fusionado'
      INNER JOIN (
        SELECT DISTINCT sx.id_producto_erp, a.id_atributo_erp, a.nombre
        FROM erp_catalogo_skus sx
        INNER JOIN erp_catalogo_sku_atributos sa ON sa.id_sku=sx.id_sku AND TRIM(sa.valor)<>''
        INNER JOIN erp_catalogo_atributos a ON a.id_atributo_erp=sa.id_atributo_erp AND a.es_variante=1 AND a.estatus='activo'
        WHERE sx.estatus<>'fusionado'
      ) av ON av.id_producto_erp=p.id_producto_erp
      LEFT JOIN erp_catalogo_sku_atributos sav ON sav.id_sku=s.id_sku AND sav.id_atributo_erp=av.id_atributo_erp AND TRIM(sav.valor)<>''
      WHERE p.estatus<>'fusionado' AND p.maneja_variantes=1 AND sav.id_sku IS NULL
      GROUP BY p.id_producto_erp, s.id_sku, p.codigo_producto, p.nombre, s.sku, s.nombre
      ORDER BY p.id_producto_erp, s.id_sku")->fetchAll(PDO::FETCH_ASSOC);
  }

  private function consultarProductosVariantesFirmasDuplicadas($db) {
    return $db->query("SELECT p.id_producto_erp, NULL id_sku, p.codigo_producto, p.nombre producto, NULL sku,
      firmas.firma, GROUP_CONCAT(firmas.sku ORDER BY firmas.sku SEPARATOR ', ') skus_duplicados
      FROM (
        SELECT s.id_producto_erp, s.id_sku, s.sku,
          GROUP_CONCAT(CONCAT(sa.id_atributo_erp, '=', LOWER(TRIM(sa.valor))) ORDER BY sa.id_atributo_erp SEPARATOR '|') firma
        FROM erp_catalogo_skus s
        LEFT JOIN erp_catalogo_sku_atributos sa ON sa.id_sku=s.id_sku
          AND TRIM(sa.valor)<>''
          AND sa.id_atributo_erp IN (
            SELECT id_atributo_erp FROM erp_catalogo_atributos WHERE es_variante=1 AND estatus='activo'
          )
        WHERE s.estatus<>'fusionado'
        GROUP BY s.id_producto_erp, s.id_sku, s.sku
      ) firmas
      INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=firmas.id_producto_erp
      WHERE p.estatus<>'fusionado' AND p.maneja_variantes=1 AND firmas.firma IS NOT NULL AND firmas.firma<>''
      GROUP BY p.id_producto_erp, p.codigo_producto, p.nombre, firmas.firma
      HAVING COUNT(*)>1
      ORDER BY p.id_producto_erp")->fetchAll(PDO::FETCH_ASSOC);
  }

  private function consultarSkusFiscalIncompleto($db) {
    return $db->query("SELECT p.id_producto_erp, s.id_sku, p.codigo_producto, p.nombre producto, s.sku, s.nombre nombre_sku,
      s.estatus estatus_sku, s.costo_referencia,
      imp.clave_producto_sat, imp.clave_unidad_sat, imp.objeto_impuesto, imp.iva_porcentaje, imp.ieps_porcentaje, imp.incluye_impuestos,
      (SELECT COUNT(*) FROM erp_catalogo_sku_proveedores sp WHERE sp.id_sku=s.id_sku AND sp.estatus='activo') proveedores_activos
      FROM erp_catalogo_skus s
      INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
      LEFT JOIN erp_catalogo_sku_impuestos imp ON imp.id_sku=s.id_sku
      WHERE s.estatus<>'fusionado'
        AND (imp.id_sku IS NULL
          OR TRIM(COALESCE(imp.clave_producto_sat,''))=''
          OR TRIM(COALESCE(imp.clave_unidad_sat,''))=''
          OR TRIM(COALESCE(imp.objeto_impuesto,''))=''
          OR imp.iva_porcentaje IS NULL
          OR imp.ieps_porcentaje IS NULL
          OR imp.incluye_impuestos IS NULL)
      ORDER BY FIELD(s.estatus, 'activo') DESC, proveedores_activos DESC, s.id_sku")->fetchAll(PDO::FETCH_ASSOC);
  }

  private function consultarSkusSinProveedorActivo($db) {
    return $db->query("SELECT p.id_producto_erp, s.id_sku, p.codigo_producto, p.nombre producto, s.sku, s.nombre nombre_sku,
      s.estatus estatus_sku, s.costo_referencia
      FROM erp_catalogo_skus s
      INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
      WHERE s.estatus='activo'
        AND NOT EXISTS (SELECT 1 FROM erp_catalogo_sku_proveedores sp WHERE sp.id_sku=s.id_sku AND sp.estatus='activo')
      ORDER BY s.id_sku")->fetchAll(PDO::FETCH_ASSOC);
  }

  private function consultarSkusSinCodigoPrincipal($db) {
    return $db->query("SELECT p.id_producto_erp, s.id_sku, p.codigo_producto, p.nombre producto, s.sku, s.nombre nombre_sku,
      s.estatus estatus_sku,
      (SELECT COUNT(*) FROM erp_catalogo_sku_proveedores sp WHERE sp.id_sku=s.id_sku AND sp.estatus='activo') proveedores_activos
      FROM erp_catalogo_skus s
      INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
      WHERE s.estatus='activo'
        AND NOT EXISTS (SELECT 1 FROM erp_catalogo_sku_codigos c WHERE c.id_sku=s.id_sku AND c.es_principal=1 AND c.estatus='activo')
      ORDER BY proveedores_activos DESC, s.id_sku")->fetchAll(PDO::FETCH_ASSOC);
  }

  private function consultarSkusComprablesSinCostoReferencia($db) {
    return $db->query("SELECT p.id_producto_erp, s.id_sku, p.codigo_producto, p.nombre producto, s.sku, s.nombre nombre_sku,
      s.estatus estatus_sku, s.costo_referencia,
      (SELECT COUNT(*) FROM erp_catalogo_sku_proveedores sp WHERE sp.id_sku=s.id_sku AND sp.estatus='activo') proveedores_activos
      FROM erp_catalogo_skus s
      INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
      WHERE s.estatus='activo' AND s.costo_referencia<=0
        AND EXISTS (SELECT 1 FROM erp_catalogo_sku_proveedores sp WHERE sp.id_sku=s.id_sku AND sp.estatus='activo')
      ORDER BY s.id_sku")->fetchAll(PDO::FETCH_ASSOC);
  }

  private function consultarComprasProductoNuevo($db) {
    return $db->query("SELECT d.id_detalle, d.id_orden_compra, o.id_proveedor, o.id_solicitud,
      d.id_producto, d.id_sku_erp id_sku, d.id_sku_proveedor, d.sku, d.nombre_producto producto,
      d.nombre_producto nombre_sku, d.unidad, d.cantidad, d.costo_unitario, d.tipo_item,
      d.producto_registrado, d.requiere_revision, d.datos_fiscales_json
      FROM erp_compras_ordenes_detalle d
      INNER JOIN erp_compras_ordenes o ON o.id_orden_compra=d.id_orden_compra
      WHERE d.tipo_item='producto_nuevo'
        OR d.requiere_revision=1
        OR COALESCE(d.id_sku_erp,0)=0
      ORDER BY d.id_orden_compra DESC, d.id_detalle")->fetchAll(PDO::FETCH_ASSOC);
  }

  private function consultarXmlConceptosSinCoincidencia($db) {
    return $db->query("SELECT c.id_documento_concepto, c.id_documento_fiscal, f.id_orden_compra,
      o.id_proveedor,
      f.uuid, f.serie, f.folio, c.id_orden_detalle, c.id_sku_erp id_sku, c.id_sku_proveedor,
      c.no_identificacion sku, c.descripcion producto, c.descripcion nombre_sku,
      c.clave_producto_sat, c.clave_unidad_sat, c.unidad, c.objeto_impuesto,
      c.cantidad, c.valor_unitario, c.importe, c.descuento, c.iva_porcentaje, c.ieps_porcentaje,
      c.resultado_conciliacion, c.observaciones_conciliacion
      FROM erp_compras_documentos_fiscales_conceptos c
      INNER JOIN erp_compras_documentos_fiscales f ON f.id_documento_fiscal=c.id_documento_fiscal
      LEFT JOIN erp_compras_ordenes o ON o.id_orden_compra=f.id_orden_compra
      WHERE c.resultado_conciliacion='sin_coincidencia'
      ORDER BY c.id_documento_concepto")->fetchAll(PDO::FETCH_ASSOC);
  }

  private function sugerirCoincidenciasCatalogoXml($db, $fila) {
    $idProveedor = intval(isset($fila["id_proveedor"]) ? $fila["id_proveedor"] : 0);
    $noIdentificacion = trim((string) (isset($fila["sku"]) ? $fila["sku"] : ""));
    $descripcion = trim((string) (isset($fila["producto"]) ? $fila["producto"] : ""));
    $candidatos = array();

    if ($idProveedor > 0 && $noIdentificacion !== "") {
      $stmt = $db->prepare("SELECT s.id_sku, s.sku, s.nombre, sp.id_sku_proveedor, sp.sku_proveedor,
          p.nombre producto, 100 puntaje, 'sku_proveedor_exacto' criterio
        FROM erp_catalogo_sku_proveedores sp
        INNER JOIN erp_catalogo_skus s ON s.id_sku=sp.id_sku AND s.estatus='activo'
        INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
        WHERE sp.id_proveedor=:proveedor AND sp.estatus='activo'
          AND LOWER(TRIM(sp.sku_proveedor))=LOWER(TRIM(:sku))
        LIMIT 5");
      $stmt->execute(array(":proveedor" => $idProveedor, ":sku" => $noIdentificacion));
      $this->agregarCandidatosXml($candidatos, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    if ($noIdentificacion !== "") {
      $stmt = $db->prepare("SELECT s.id_sku, s.sku, s.nombre, sp.id_sku_proveedor, sp.sku_proveedor,
          p.nombre producto, 90 puntaje,
          CASE
            WHEN LOWER(TRIM(s.sku))=LOWER(TRIM(:sku_a)) THEN 'sku_erp_exacto'
            ELSE 'codigo_activo_exacto'
          END criterio
        FROM erp_catalogo_skus s
        INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
        LEFT JOIN erp_catalogo_sku_proveedores sp ON sp.id_sku=s.id_sku
          AND (:proveedor=0 OR sp.id_proveedor=:proveedor) AND sp.estatus='activo'
        WHERE s.estatus='activo'
          AND (
            LOWER(TRIM(s.sku))=LOWER(TRIM(:sku_b))
            OR EXISTS (
              SELECT 1 FROM erp_catalogo_sku_codigos cod
              WHERE cod.id_sku=s.id_sku AND cod.estatus='activo'
                AND LOWER(TRIM(cod.codigo))=LOWER(TRIM(:sku_c))
            )
          )
        ORDER BY CASE WHEN sp.id_sku_proveedor IS NULL THEN 1 ELSE 0 END, s.id_sku
        LIMIT 5");
      $stmt->execute(array(
        ":proveedor" => $idProveedor,
        ":sku_a" => $noIdentificacion,
        ":sku_b" => $noIdentificacion,
        ":sku_c" => $noIdentificacion
      ));
      $this->agregarCandidatosXml($candidatos, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    $terminos = $this->terminosBusquedaXml($descripcion);
    if (!empty($terminos)) {
      $where = array();
      $params = array(":proveedor" => $idProveedor);
      foreach ($terminos as $idx => $termino) {
        $clave = ":t" . $idx;
        $where[] = "(s.nombre LIKE " . $clave . " OR p.nombre LIKE " . $clave . ")";
        $params[$clave] = "%" . $termino . "%";
      }
      $stmt = $db->prepare("SELECT s.id_sku, s.sku, s.nombre, sp.id_sku_proveedor, sp.sku_proveedor,
          p.nombre producto, 60 puntaje, 'nombre_aproximado' criterio
        FROM erp_catalogo_skus s
        INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
        LEFT JOIN erp_catalogo_sku_proveedores sp ON sp.id_sku=s.id_sku
          AND (:proveedor=0 OR sp.id_proveedor=:proveedor) AND sp.estatus='activo'
        WHERE s.estatus='activo' AND " . implode(" AND ", $where) . "
        ORDER BY CASE WHEN sp.id_sku_proveedor IS NULL THEN 1 ELSE 0 END, s.nombre
        LIMIT 5");
      $stmt->execute($params);
      $this->agregarCandidatosXml($candidatos, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    usort($candidatos, function ($a, $b) {
      return intval($b["puntaje"]) - intval($a["puntaje"]);
    });
    return array_slice(array_values($candidatos), 0, 5);
  }

  private function agregarCandidatosXml(&$candidatos, $filas) {
    foreach ($filas as $fila) {
      $idSku = intval(isset($fila["id_sku"]) ? $fila["id_sku"] : 0);
      if ($idSku <= 0 || isset($candidatos[$idSku])) {
        continue;
      }
      $candidatos[$idSku] = array(
        "id_sku" => $idSku,
        "sku" => isset($fila["sku"]) ? $fila["sku"] : "",
        "nombre" => isset($fila["nombre"]) ? $fila["nombre"] : "",
        "producto" => isset($fila["producto"]) ? $fila["producto"] : "",
        "id_sku_proveedor" => intval(isset($fila["id_sku_proveedor"]) ? $fila["id_sku_proveedor"] : 0),
        "sku_proveedor" => isset($fila["sku_proveedor"]) ? $fila["sku_proveedor"] : "",
        "puntaje" => intval(isset($fila["puntaje"]) ? $fila["puntaje"] : 0),
        "criterio" => isset($fila["criterio"]) ? $fila["criterio"] : ""
      );
    }
  }

  private function terminosBusquedaXml($descripcion) {
    $descripcion = strtolower(preg_replace('/[^a-zA-Z0-9]+/', ' ', (string) $descripcion));
    $palabras = array_values(array_unique(array_filter(explode(" ", $descripcion), function ($palabra) {
      return strlen($palabra) >= 4 && !in_array($palabra, array("para", "con", "pieza", "piezas", "producto", "unidad"), true);
    })));
    return array_slice($palabras, 0, 3);
  }

  private function consultarComprasProductosAtencionPendientes($db) {
    return $db->query("SELECT a.id_producto_atencion, a.id_orden_compra, a.id_solicitud, a.id_proveedor,
      a.id_producto, a.id_sku_erp id_sku, a.id_sku_proveedor, a.id_orden_detalle, a.id_documento_fiscal,
      a.sku, a.nombre_producto producto, a.nombre_producto nombre_sku,
      a.cantidad_solicitada, a.cantidad_comprada, a.motivo, a.estatus, a.observaciones
      FROM erp_compras_ordenes_productos_atencion a
      WHERE a.estatus='pendiente'
      ORDER BY a.id_producto_atencion")->fetchAll(PDO::FETCH_ASSOC);
  }

  private function consultarXmlFiscalSugerido($db) {
    return $db->query("SELECT c.id_documento_concepto, c.id_documento_fiscal, f.id_orden_compra,
      f.uuid, f.serie, f.folio, c.id_sku_erp id_sku, c.no_identificacion sku,
      c.descripcion producto, c.descripcion nombre_sku,
      c.clave_producto_sat, c.clave_unidad_sat, c.unidad, c.objeto_impuesto,
      c.iva_porcentaje, c.ieps_porcentaje,
      imp.clave_producto_sat maestro_clave_producto_sat,
      imp.clave_unidad_sat maestro_clave_unidad_sat,
      imp.objeto_impuesto maestro_objeto_impuesto,
      imp.iva_porcentaje maestro_iva_porcentaje,
      imp.ieps_porcentaje maestro_ieps_porcentaje
      FROM erp_compras_documentos_fiscales_conceptos c
      INNER JOIN erp_compras_documentos_fiscales f ON f.id_documento_fiscal=c.id_documento_fiscal
      LEFT JOIN erp_catalogo_sku_impuestos imp ON imp.id_sku=c.id_sku_erp
      WHERE COALESCE(c.id_sku_erp,0)>0
        AND (TRIM(COALESCE(c.clave_producto_sat,''))<>'' OR TRIM(COALESCE(c.clave_unidad_sat,''))<>'' OR TRIM(COALESCE(c.objeto_impuesto,''))<>'' OR c.iva_porcentaje>0 OR c.ieps_porcentaje>0)
        AND (imp.id_sku IS NULL
          OR TRIM(COALESCE(imp.clave_producto_sat,''))=''
          OR TRIM(COALESCE(imp.clave_unidad_sat,''))=''
          OR TRIM(COALESCE(imp.objeto_impuesto,''))=''
          OR imp.iva_porcentaje IS NULL
          OR imp.ieps_porcentaje IS NULL
          OR imp.incluye_impuestos IS NULL)
      ORDER BY c.id_documento_concepto")->fetchAll(PDO::FETCH_ASSOC);
  }

  private function consultarXmlFiscalDiferente($db) {
    return $db->query("SELECT c.id_documento_concepto, c.id_documento_fiscal, f.id_orden_compra,
      f.uuid, f.serie, f.folio, c.id_sku_erp id_sku, c.no_identificacion sku,
      c.descripcion producto, c.descripcion nombre_sku,
      c.clave_producto_sat, c.clave_unidad_sat, c.unidad, c.objeto_impuesto,
      c.iva_porcentaje, c.ieps_porcentaje,
      imp.clave_producto_sat maestro_clave_producto_sat,
      imp.clave_unidad_sat maestro_clave_unidad_sat,
      imp.objeto_impuesto maestro_objeto_impuesto,
      imp.iva_porcentaje maestro_iva_porcentaje,
      imp.ieps_porcentaje maestro_ieps_porcentaje
      FROM erp_compras_documentos_fiscales_conceptos c
      INNER JOIN erp_compras_documentos_fiscales f ON f.id_documento_fiscal=c.id_documento_fiscal
      INNER JOIN erp_catalogo_sku_impuestos imp ON imp.id_sku=c.id_sku_erp
      WHERE COALESCE(c.id_sku_erp,0)>0
        AND ((TRIM(COALESCE(c.clave_producto_sat,''))<>'' AND TRIM(COALESCE(imp.clave_producto_sat,''))<>'' AND TRIM(c.clave_producto_sat)<>TRIM(imp.clave_producto_sat))
          OR (TRIM(COALESCE(c.clave_unidad_sat,''))<>'' AND TRIM(COALESCE(imp.clave_unidad_sat,''))<>'' AND TRIM(c.clave_unidad_sat)<>TRIM(imp.clave_unidad_sat))
          OR (TRIM(COALESCE(c.objeto_impuesto,''))<>'' AND TRIM(COALESCE(imp.objeto_impuesto,''))<>'' AND TRIM(c.objeto_impuesto)<>TRIM(imp.objeto_impuesto))
          OR (c.iva_porcentaje IS NOT NULL AND imp.iva_porcentaje IS NOT NULL AND ABS(c.iva_porcentaje-imp.iva_porcentaje)>0.0001)
          OR (c.ieps_porcentaje IS NOT NULL AND imp.ieps_porcentaje IS NOT NULL AND ABS(c.ieps_porcentaje-imp.ieps_porcentaje)>0.0001))
      ORDER BY c.id_documento_concepto")->fetchAll(PDO::FETCH_ASSOC);
  }

  public function crearSkuTemporalDesdeIncidenciaProveedor($datos, $idUsuario = 0) {
    $idIncidencia = intval(isset($datos["id_incidencia_calidad"]) ? $datos["id_incidencia_calidad"] : 0);
    $idUnidad = intval(isset($datos["id_unidad_base"]) ? $datos["id_unidad_base"] : 0);
    if ($idIncidencia <= 0 || $idUnidad <= 0) {
      return $this->respuesta(true, "warning", "Selecciona incidencia y unidad base para crear el SKU temporal");
    }
    if ($this->factorUnidadBaseSku($datos) <= 0) {
      return $this->respuesta(true, "warning", "El factor de conversion de la unidad base debe ser mayor a cero");
    }

    $db = $this->getConexion();
    try {
      $stmt = $db->prepare("SELECT *
        FROM erp_catalogo_incidencias_calidad
        WHERE id_incidencia_calidad = :id
        LIMIT 1");
      $stmt->execute(array(":id" => $idIncidencia));
      $incidencia = $stmt->fetch(PDO::FETCH_ASSOC);
      if (!$incidencia) {
        throw new Exception("Incidencia de Catalogo no encontrada");
      }
      if ($incidencia["origen"] !== "proveedores" || $incidencia["tipo_incidencia"] !== "proveedor_sku_sin_match") {
        throw new Exception("Solo se permite crear SKU temporal desde pendientes de proveedor sin SKU ERP");
      }
      if (in_array($incidencia["estatus"], array("resuelta", "descartada"), true)) {
        throw new Exception("La incidencia ya esta cerrada");
      }
      if (intval($incidencia["id_sku"]) > 0) {
        throw new Exception("La incidencia ya tiene SKU ERP relacionado");
      }
      if (!$this->unidadBaseExiste($db, $idUnidad)) {
        throw new Exception("Selecciona una unidad base activa");
      }

      $evidencia = $this->jsonArrayCatalogo(isset($incidencia["evidencia_json"]) ? $incidencia["evidencia_json"] : "");
      $detalle = $this->jsonArrayCatalogo(isset($incidencia["detalle_json"]) ? $incidencia["detalle_json"] : "");
      $renglon = isset($evidencia["renglon"]) && is_array($evidencia["renglon"]) ? $evidencia["renglon"] : array();
      $idReferencia = intval(isset($incidencia["id_referencia"]) ? $incidencia["id_referencia"] : 0);
      $codigoProducto = $this->codigoTemporalCatalogo($this->texto($datos, "codigo_producto", "TMP-PROV-" . $idReferencia), "TMP-PROV-" . $idReferencia, 80);
      $nombreProducto = $this->texto($datos, "nombre_producto", "");
      if ($nombreProducto === "") {
        $nombreProducto = $this->textoArrayCatalogo($renglon, "descripcion_proveedor", "Producto proveedor pendiente");
      }
      $sku = $this->codigoTemporalCatalogo($this->texto($datos, "sku", ""), "", 150);
      if ($sku === "") {
        $sku = $this->codigoTemporalCatalogo($this->textoArrayCatalogo($renglon, "sku_proveedor", ""), "", 150);
      }
      if ($sku === "") {
        $sku = $this->codigoTemporalCatalogo($this->textoArrayCatalogo($renglon, "codigo_barras", ""), "", 150);
      }
      if ($sku === "") {
        $sku = $this->codigoTemporalCatalogo($this->textoArrayCatalogo($renglon, "codigo_interno", ""), "", 150);
      }
      if ($sku === "") {
        $sku = "TMP-PROV-" . $idReferencia;
      }
      $nombreSku = $this->texto($datos, "nombre_sku", $nombreProducto);
      $codigoPrincipal = $this->codigoPrincipalDesdeRenglonProveedor($renglon);

      $existente = $this->buscarSkuExistentePorIdentidad($db, $sku, $codigoPrincipal);
      if ($existente) {
        throw new Exception("Ya existe un SKU ERP con esa identidad: " . $existente["sku"] . " (ID " . intval($existente["id_sku"]) . "). Usa matching en Proveedores.");
      }

      $idMarca = $this->resolverMarca($db, $datos);
      $idCategoria = $this->resolverCategoria($db, $datos);

      $stmt = $db->prepare("INSERT INTO erp_catalogo_productos
        (codigo_producto, nombre, descripcion, tipo_producto, id_marca_erp, maneja_variantes, estatus, creado_por)
        VALUES (:codigo, :nombre, :descripcion, 'producto', :marca, 0, 'borrador', :usuario)");
      $stmt->execute(array(
        ":codigo" => $codigoProducto,
        ":nombre" => $nombreProducto,
        ":descripcion" => $this->descripcionTemporalCatalogo($incidencia, $detalle, $renglon),
        ":marca" => $idMarca ?: null,
        ":usuario" => intval($idUsuario) ?: null
      ));
      $idProducto = intval($db->lastInsertId());

      if ($idCategoria > 0) {
        $stmt = $db->prepare("INSERT INTO erp_catalogo_producto_categorias (id_producto_erp, id_categoria_erp, es_principal)
          VALUES (:producto, :categoria, 1)");
        $stmt->execute(array(":producto" => $idProducto, ":categoria" => $idCategoria));
      }

      $stmt = $db->prepare("INSERT INTO erp_catalogo_skus
        (id_producto_erp, sku, nombre, tipo_inventario, id_unidad_base, factor_unidad_base, costo_referencia, permite_venta_sin_existencia, estatus, creado_por)
        VALUES (:producto, :sku, :nombre, 'inventariable', :unidad, :factor_unidad, 0, 0, 'borrador', :usuario)");
      $stmt->execute(array(
        ":producto" => $idProducto,
        ":sku" => $sku,
        ":nombre" => $nombreSku,
        ":unidad" => $idUnidad,
        ":factor_unidad" => $this->factorUnidadBaseSku($datos),
        ":usuario" => intval($idUsuario) ?: null
      ));
      $idSku = intval($db->lastInsertId());

      if ($codigoPrincipal !== "") {
        $this->actualizarCodigoBarrasPrincipal($db, $idSku, $codigoPrincipal);
      }

      $stmt = $db->prepare("UPDATE erp_catalogo_incidencias_calidad SET
          entidad_tipo = 'sku',
          id_producto_erp = :producto,
          id_sku = :sku,
          estatus = 'en_revision',
          resolucion_json = :resolucion,
          responsable_id = COALESCE(responsable_id, :usuario),
          fecha_actualizacion = CURRENT_TIMESTAMP
        WHERE id_incidencia_calidad = :id");
      $resolucion = array(
        "accion" => "sku_temporal_creado",
        "id_producto_erp" => $idProducto,
        "id_sku" => $idSku,
        "estatus_sku" => "borrador",
        "usuario_id" => intval($idUsuario) ?: null,
        "fecha" => date("c"),
        "nota" => "Creado desde incidencia de Proveedores; requiere completar Catalogo y hacer matching desde Proveedores."
      );
      $stmt->execute(array(
        ":producto" => $idProducto,
        ":sku" => $idSku,
        ":resolucion" => json_encode($resolucion, JSON_UNESCAPED_UNICODE),
        ":usuario" => intval($idUsuario) ?: null,
        ":id" => $idIncidencia
      ));

      $this->confirmarSkuTemporalProveedor($db, $idIncidencia, $idProducto, $idSku);
      $idNotificacionSeguimiento = $this->registrarNotificacionesSkuTemporalProveedor($db, $incidencia, $detalle, $renglon, $idProducto, $idSku, $sku, $idUsuario);

      return $this->respuesta(false, "success", "SKU temporal creado en Catalogo", array(
        "id_incidencia_calidad" => $idIncidencia,
        "id_producto_erp" => $idProducto,
        "id_sku" => $idSku,
        "id_notificacion_seguimiento" => $idNotificacionSeguimiento,
        "sku" => $sku,
        "codigo_producto" => $codigoProducto,
        "estatus" => "borrador",
        "siguiente_paso" => "Completar Catalogo y hacer matching desde Proveedores"
      ));
    } catch (Exception $e) {
      $mensaje = $e->getCode() === "23000" ? "El codigo de producto, SKU o codigo ya existe; revisa matching antes de crear temporal" : $e->getMessage();
      return $this->respuesta(true, "danger", $mensaje);
    }
  }

  private function confirmarSkuTemporalProveedor($db, $idIncidencia, $idProducto, $idSku) {
    if (intval($idProducto) <= 0 || intval($idSku) <= 0) {
      throw new Exception("No se pudo obtener el ID del producto o SKU temporal");
    }

    $stmt = $db->prepare("SELECT COUNT(*) FROM erp_catalogo_productos WHERE id_producto_erp = :producto");
    $stmt->execute(array(":producto" => intval($idProducto)));
    if (intval($stmt->fetchColumn()) <= 0) {
      throw new Exception("No se pudo confirmar el producto temporal en Catalogo");
    }

    $stmt = $db->prepare("SELECT COUNT(*) FROM erp_catalogo_skus WHERE id_sku = :sku AND id_producto_erp = :producto");
    $stmt->execute(array(":sku" => intval($idSku), ":producto" => intval($idProducto)));
    if (intval($stmt->fetchColumn()) <= 0) {
      throw new Exception("No se pudo confirmar el SKU temporal en Catalogo");
    }

    $stmt = $db->prepare("SELECT id_producto_erp, id_sku, estatus
      FROM erp_catalogo_incidencias_calidad
      WHERE id_incidencia_calidad = :incidencia
      LIMIT 1");
    $stmt->execute(array(":incidencia" => intval($idIncidencia)));
    $incidencia = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$incidencia) {
      throw new Exception("No se pudo confirmar la incidencia de Catalogo");
    }
    if (intval($incidencia["id_producto_erp"]) !== intval($idProducto) || intval($incidencia["id_sku"]) !== intval($idSku)) {
      throw new Exception("El SKU temporal se creo, pero la incidencia no quedo vinculada");
    }
  }

  private function registrarNotificacionesSkuTemporalProveedor($db, $incidencia, $detalle, $renglon, $idProducto, $idSku, $sku, $idUsuario) {
    try {
      require_once __DIR__ . "/NotificacionesErp.php";
      $notificaciones = new NotificacionesErp();
      $huellaIncidencia = trim((string) (isset($incidencia["huella"]) ? $incidencia["huella"] : ""));
      if ($huellaIncidencia !== "") {
        $notificaciones->resolverOperativaPorHuellaEnConexion($db, "proveedor_producto_pendiente_alta_catalogo", $huellaIncidencia);
      }

      $idIncidencia = intval(isset($incidencia["id_incidencia_calidad"]) ? $incidencia["id_incidencia_calidad"] : 0);
      $idProveedor = intval(isset($detalle["id_proveedor"]) ? $detalle["id_proveedor"] : 0);
      $idLista = intval(isset($detalle["id_lista_proveedor_erp"]) ? $detalle["id_lista_proveedor_erp"] : 0);
      $skuProveedor = $this->textoArrayCatalogo($renglon, "sku_proveedor", "");
      $descripcion = $this->textoArrayCatalogo($renglon, "descripcion_proveedor", "");
      $huellaSeguimiento = hash("sha256", "notificacion|catalogo|sku_temporal_proveedor|incidencia:" . $idIncidencia . "|sku:" . intval($idSku));

      return $notificaciones->guardarOperativaEnConexion($db, array(
        "tipo" => "catalogo_sku_temporal_creado_proveedor_matching",
        "modulo_origen" => "catalogo",
        "entidad_origen" => "erp_catalogo_incidencias_calidad",
        "id_entidad_origen" => $idIncidencia,
        "area_responsable" => "proveedores",
        "permiso_requerido" => "proveedores.matching",
        "titulo" => "SKU temporal creado; vincular proveedor",
        "descripcion" => "Catalogo creo el SKU temporal " . $sku . ". Proveedores debe relacionarlo con el producto del proveedor" . ($skuProveedor !== "" ? " " . $skuProveedor : "") . ".",
        "prioridad" => "normal",
        "url_accion" => "/proveedor/mostrar_proveedores_erp",
        "payload_json" => array(
          "huella" => $huellaSeguimiento,
          "huella_incidencia" => $huellaIncidencia,
          "id_incidencia_calidad" => $idIncidencia,
          "id_producto_erp" => intval($idProducto),
          "id_sku" => intval($idSku),
          "sku" => $sku,
          "id_proveedor" => $idProveedor,
          "id_lista_proveedor_erp" => $idLista,
          "id_lista_detalle_erp" => intval(isset($incidencia["id_referencia"]) ? $incidencia["id_referencia"] : 0),
          "sku_proveedor" => $skuProveedor,
          "descripcion_proveedor" => $descripcion,
          "siguiente_paso" => "hacer_matching_desde_proveedores"
        ),
        "creado_por" => intval($idUsuario) ?: null
      ));
    } catch (Exception $e) {
      return 0;
    }
  }

  private function unidadBaseExiste($db, $idUnidad) {
    $stmt = $db->prepare("SELECT id_unidad FROM erp_catalogo_unidades WHERE id_unidad = :unidad AND estatus = 'activa' LIMIT 1");
    $stmt->execute(array(":unidad" => intval($idUnidad)));
    return intval($stmt->fetchColumn()) > 0;
  }

  private function jsonArrayCatalogo($json) {
    $datos = trim((string) $json) !== "" ? json_decode($json, true) : array();
    return is_array($datos) ? $datos : array();
  }

  private function textoArrayCatalogo($datos, $campo, $default = "") {
    return isset($datos[$campo]) ? trim((string) $datos[$campo]) : $default;
  }

  private function codigoTemporalCatalogo($valor, $default, $limite) {
    $valor = trim((string) $valor);
    if ($valor === "") {
      $valor = $default;
    }
    $valor = preg_replace('/\s+/', '-', strtoupper($valor));
    $valor = preg_replace('/[^A-Z0-9._-]/', '', $valor);
    if ($valor === "") {
      $valor = $default;
    }
    return substr($valor, 0, $limite);
  }

  private function codigoPrincipalDesdeRenglonProveedor($renglon) {
    foreach (array("codigo_barras", "codigo_interno", "sku_proveedor") as $campo) {
      $valor = $this->textoArrayCatalogo($renglon, $campo, "");
      if ($valor !== "") {
        return $valor;
      }
    }
    return "";
  }

  private function buscarSkuExistentePorIdentidad($db, $sku, $codigo) {
    $stmt = $db->prepare("SELECT s.id_sku, s.sku, s.nombre
      FROM erp_catalogo_skus s
      LEFT JOIN erp_catalogo_sku_codigos c ON c.id_sku = s.id_sku AND c.estatus = 'activo'
      WHERE s.estatus <> 'fusionado'
        AND (
          LOWER(TRIM(s.sku)) = LOWER(TRIM(:sku))
          OR (:codigo <> '' AND LOWER(TRIM(c.codigo)) = LOWER(TRIM(:codigo)))
        )
      ORDER BY s.estatus = 'activo' DESC, s.id_sku DESC
      LIMIT 1");
    $stmt->execute(array(":sku" => $sku, ":codigo" => trim((string) $codigo)));
    $fila = $stmt->fetch(PDO::FETCH_ASSOC);
    return $fila ? $fila : null;
  }

  private function descripcionTemporalCatalogo($incidencia, $detalle, $renglon) {
    $partes = array(
      "SKU temporal creado desde Proveedores.",
      "Incidencia: " . intval(isset($incidencia["id_incidencia_calidad"]) ? $incidencia["id_incidencia_calidad"] : 0),
      "Proveedor: " . intval(isset($detalle["id_proveedor"]) ? $detalle["id_proveedor"] : 0),
      "Lista: " . intval(isset($detalle["id_lista_proveedor_erp"]) ? $detalle["id_lista_proveedor_erp"] : 0)
    );
    $descripcion = $this->textoArrayCatalogo($renglon, "descripcion_proveedor", "");
    if ($descripcion !== "") {
      $partes[] = "Descripcion proveedor: " . $descripcion;
    }
    return implode("\n", $partes);
  }

  private function resumenIncidenciasCalidad($db) {
    $resumen = array(
      "por_estatus" => array(),
      "por_severidad" => array(),
      "por_tipo_abiertas" => array()
    );

    foreach ($db->query("SELECT estatus, COUNT(*) total FROM erp_catalogo_incidencias_calidad GROUP BY estatus ORDER BY estatus")->fetchAll(PDO::FETCH_ASSOC) as $fila) {
      $resumen["por_estatus"][$fila["estatus"]] = intval($fila["total"]);
    }
    foreach ($db->query("SELECT severidad, COUNT(*) total FROM erp_catalogo_incidencias_calidad WHERE estatus IN ('pendiente','en_revision','bloqueada') GROUP BY severidad ORDER BY severidad")->fetchAll(PDO::FETCH_ASSOC) as $fila) {
      $resumen["por_severidad"][$fila["severidad"]] = intval($fila["total"]);
    }
    foreach ($db->query("SELECT tipo_incidencia, COUNT(*) total FROM erp_catalogo_incidencias_calidad WHERE estatus IN ('pendiente','en_revision','bloqueada') GROUP BY tipo_incidencia ORDER BY total DESC, tipo_incidencia LIMIT 30")->fetchAll(PDO::FETCH_ASSOC) as $fila) {
      $resumen["por_tipo_abiertas"][$fila["tipo_incidencia"]] = intval($fila["total"]);
    }

    return $resumen;
  }

  private function normalizarIncidenciaCalidad($fila) {
    foreach (array("detalle_json", "evidencia_json", "propuesta_json", "resolucion_json") as $campo) {
      $valor = isset($fila[$campo]) ? $fila[$campo] : "";
      $fila[$campo] = $valor !== "" && $valor !== null ? json_decode($valor, true) : null;
    }
    foreach (array("id_incidencia_calidad", "id_producto_erp", "id_sku", "id_referencia", "responsable_id", "creado_por", "resuelto_por") as $campo) {
      if (isset($fila[$campo]) && $fila[$campo] !== null) {
        $fila[$campo] = intval($fila[$campo]);
      }
    }
    return $fila;
  }

  private function guardarIncidenciaCalidad($db, $incidencia, $idUsuario) {
    $fila = isset($incidencia["fila"]) ? $incidencia["fila"] : array();
    $tipo = $incidencia["tipo"];
    $idSku = intval(isset($fila["id_sku"]) ? $fila["id_sku"] : 0);
    $idProducto = intval(isset($fila["id_producto_erp"]) ? $fila["id_producto_erp"] : 0);
    $idReferencia = intval(isset($incidencia["id_referencia"]) ? $incidencia["id_referencia"] : (isset($fila["id_referencia"]) ? $fila["id_referencia"] : 0));
    $referenciaTipo = isset($incidencia["referencia_tipo"]) ? $incidencia["referencia_tipo"] : "";
    $origen = isset($incidencia["origen"]) ? $incidencia["origen"] : "catalogo";
    $entidadTipo = isset($incidencia["entidad_tipo"]) ? $incidencia["entidad_tipo"] : ($idSku > 0 ? "sku" : "producto");
    if (!preg_match('/^[a-z0-9_]{2,40}$/', $entidadTipo)) {
      $entidadTipo = $idSku > 0 ? "sku" : "producto";
    }
    if (!in_array($origen, array("catalogo", "compra", "xml", "migracion", "captura_manual", "proveedores"), true)) {
      $origen = "catalogo";
    }
    if (!preg_match('/^[a-zA-Z0-9_]{0,60}$/', $referenciaTipo)) {
      $referenciaTipo = "";
    }
    $idEntidad = $idSku > 0 ? $idSku : ($idProducto > 0 ? $idProducto : $idReferencia);
    $huellaBase = isset($incidencia["huella_base"])
      ? $incidencia["huella_base"]
      : $tipo . "|" . $entidadTipo . "|" . $idEntidad;
    $detalle = isset($incidencia["detalle"]) ? $incidencia["detalle"] : array();
    $detalle["huella_base"] = $huellaBase;
    $detalle["sku"] = isset($fila["sku"]) ? $fila["sku"] : "";
    $detalle["nombre_sku"] = isset($fila["nombre_sku"]) ? $fila["nombre_sku"] : "";

    $stmt = $db->prepare("INSERT INTO erp_catalogo_incidencias_calidad
      (huella, tipo_incidencia, entidad_tipo, id_producto_erp, id_sku, id_referencia, referencia_tipo,
       origen, severidad, titulo, descripcion,
       detalle_json, evidencia_json, propuesta_json, estatus, creado_por)
      VALUES (:huella, :tipo, :entidad_tipo, :producto, :sku, :id_referencia, :referencia_tipo,
       :origen, :severidad, :titulo, :descripcion,
       :detalle, :evidencia, :propuesta, 'pendiente', :usuario)
      ON DUPLICATE KEY UPDATE
        severidad=VALUES(severidad), titulo=VALUES(titulo), descripcion=VALUES(descripcion),
        detalle_json=VALUES(detalle_json), evidencia_json=VALUES(evidencia_json), propuesta_json=VALUES(propuesta_json),
        estatus=IF(estatus IN ('resuelta','descartada'), estatus, 'pendiente'),
        fecha_actualizacion=CURRENT_TIMESTAMP");
    $stmt->execute(array(
      ":huella" => hash("sha256", $huellaBase),
      ":tipo" => $tipo,
      ":entidad_tipo" => $entidadTipo,
      ":producto" => $idProducto ?: null,
      ":sku" => $idSku ?: null,
      ":id_referencia" => $idReferencia ?: null,
      ":referencia_tipo" => $referenciaTipo ?: null,
      ":origen" => $origen,
      ":severidad" => $incidencia["severidad"],
      ":titulo" => $incidencia["titulo"],
      ":descripcion" => $incidencia["descripcion"],
      ":detalle" => json_encode($detalle, JSON_UNESCAPED_UNICODE),
      ":evidencia" => json_encode($fila, JSON_UNESCAPED_UNICODE),
      ":propuesta" => json_encode(isset($incidencia["propuesta"]) ? $incidencia["propuesta"] : array(), JSON_UNESCAPED_UNICODE),
      ":usuario" => intval($idUsuario) ?: null
    ));
  }

  private function tablaExisteCatalogo($db, $tabla, $base = null) {
    $base = $base ?: MYSQLBASE;
    $stmt = $db->prepare("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES
      WHERE TABLE_SCHEMA=:base AND TABLE_NAME=:tabla LIMIT 1");
    $stmt->execute(array(":base" => $base, ":tabla" => $tabla));
    return (bool) $stmt->fetchColumn();
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-26
   * Proposito: detecta columnas opcionales de Catalogo antes de leer/escribir campos aun no migrados.
   * Impacto: Catalogo ERP; permite preparar UI y backend sin romper instalaciones previas al DDL.
   */
  private function columnaExisteCatalogo($db, $tabla, $columna, $base = null) {
    $base = $base ?: MYSQLBASE;
    $stmt = $db->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
      WHERE TABLE_SCHEMA=:base AND TABLE_NAME=:tabla AND COLUMN_NAME=:columna LIMIT 1");
    $stmt->execute(array(":base" => $base, ":tabla" => $tabla, ":columna" => $columna));
    return (bool) $stmt->fetchColumn();
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-26
   * Proposito: valida si las columnas de recepcion variable ya existen para lectura/escritura segura.
   * Impacto: Catalogo ERP; separa configuracion de Catalogo de la aplicacion pendiente de esquema.
   */
  private function esquemaRecepcionVariableDisponible($db) {
    foreach (array(
      "requiere_cantidad_variable_recepcion",
      "requiere_unidades_fisicas_recepcion",
      "tolerancia_recepcion_porcentaje",
      "nota_recepcion_variable"
    ) as $columna) {
      if (!$this->columnaExisteCatalogo($db, "erp_catalogo_sku_reglas_inventario", $columna)) {
        return false;
      }
    }
    return true;
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-26
   * Proposito: valida si el esquema de paquetes configurables esta disponible antes de consultar o escribir recetas.
   * Impacto: Catalogo ERP; evita errores SQL mientras la migracion esta pendiente de autorizacion.
   */
  private function esquemaPaquetesDisponible($db) {
    foreach (array(
      "erp_catalogo_sku_paquetes",
      "erp_catalogo_sku_paquete_componentes",
      "erp_catalogo_sku_paquete_grupos",
      "erp_catalogo_sku_paquete_grupo_opciones"
    ) as $tabla) {
      if (!$this->tablaExisteCatalogo($db, $tabla)) {
        return false;
      }
    }
    return true;
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-29
   * Proposito: valida disponibilidad de tablas de imagenes para marcas y categorias antes de leer o escribir.
   * Impacto: Catalogo ERP; permite preparar UI sin aplicar DDL ni romper Configuracion.
   */
  private function esquemaImagenesCatalogoMaestroDisponible($db) {
    return $this->tablaExisteCatalogo($db, "erp_catalogo_marca_imagenes")
      && $this->tablaExisteCatalogo($db, "erp_catalogo_categoria_imagenes");
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-29
   * Proposito: agrega conteo y miniatura principal a marcas/categorias si el esquema visual existe.
   * Impacto: Catalogo ERP; mejora UX de catalogos maestros sin cambiar el contrato cuando el DDL esta pendiente.
   */
  private function agregarResumenImagenesMarcasCategorias($db, &$marcas, &$categorias) {
    $imagenesMarcas = array();
    foreach ($db->query("SELECT id_marca_erp, COUNT(*) total_imagenes,
        MIN(CASE WHEN tipo_imagen='logo' AND estatus='activo' THEN url_imagen ELSE NULL END) logo_url
      FROM erp_catalogo_marca_imagenes
      WHERE estatus='activo'
      GROUP BY id_marca_erp")->fetchAll(PDO::FETCH_ASSOC) as $fila) {
      $imagenesMarcas[intval($fila["id_marca_erp"])] = $fila;
    }
    foreach ($marcas as &$marca) {
      $id = intval($marca["id_marca_erp"]);
      $marca["total_imagenes"] = isset($imagenesMarcas[$id]) ? intval($imagenesMarcas[$id]["total_imagenes"]) : 0;
      $marca["imagen_principal"] = isset($imagenesMarcas[$id]) ? $imagenesMarcas[$id]["logo_url"] : null;
    }
    unset($marca);

    $imagenesCategorias = array();
    foreach ($db->query("SELECT id_categoria_erp, COUNT(*) total_imagenes,
        MIN(CASE WHEN tipo_imagen IN ('icono','portada') AND estatus='activo' THEN url_imagen ELSE NULL END) imagen_principal
      FROM erp_catalogo_categoria_imagenes
      WHERE estatus='activo'
      GROUP BY id_categoria_erp")->fetchAll(PDO::FETCH_ASSOC) as $fila) {
      $imagenesCategorias[intval($fila["id_categoria_erp"])] = $fila;
    }
    foreach ($categorias as &$categoria) {
      $id = intval($categoria["id_categoria_erp"]);
      $categoria["total_imagenes"] = isset($imagenesCategorias[$id]) ? intval($imagenesCategorias[$id]["total_imagenes"]) : 0;
      $categoria["imagen_principal"] = isset($imagenesCategorias[$id]) ? $imagenesCategorias[$id]["imagen_principal"] : null;
    }
    unset($categoria);
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-29
   * Proposito: lista imagenes de una marca o categoria con candado de esquema pendiente.
   * Impacto: Catalogo ERP; habilita UI segura antes de aplicar el DDL real.
   */
  public function listarImagenesCatalogoMaestro($tipoEntidad, $idEntidad) {
    $tipoEntidad = $this->normalizarEntidadImagenCatalogo($tipoEntidad);
    $idEntidad = intval($idEntidad);
    if ($tipoEntidad === "" || $idEntidad <= 0) {
      return $this->respuesta(true, "warning", "Selecciona marca o categoria");
    }
    try {
      $db = $this->getConexion();
      if (!$this->esquemaImagenesCatalogoMaestroDisponible($db)) {
        return $this->respuesta(false, "warning", "Esquema de imagenes de marcas/categorias pendiente", array(
          "schema_disponible" => false,
          "imagenes" => array(),
          "tipo_entidad" => $tipoEntidad,
          "id_entidad" => $idEntidad
        ));
      }
      $meta = $this->metaImagenCatalogoMaestro($tipoEntidad);
      $this->validarEntidadImagenCatalogo($db, $tipoEntidad, $idEntidad);
      $stmt = $db->prepare("SELECT " . $meta["id_imagen"] . " AS id_imagen, " . $meta["id_entidad"] . " AS id_entidad,
          tipo_imagen, url_imagen, texto_alternativo, orden, estatus, fecha_registro, fecha_actualizacion
        FROM " . $meta["tabla"] . "
        WHERE " . $meta["id_entidad"] . "=:entidad
        ORDER BY FIELD(estatus, 'activo', 'inactivo'), FIELD(tipo_imagen, " . $meta["orden_tipos"] . "), orden, " . $meta["id_imagen"]);
      $stmt->execute(array(":entidad" => $idEntidad));
      return $this->respuesta(false, "success", "Imagenes consultadas", array(
        "schema_disponible" => true,
        "tipo_entidad" => $tipoEntidad,
        "id_entidad" => $idEntidad,
        "tipos_permitidos" => $meta["tipos"],
        "imagenes" => $stmt->fetchAll(PDO::FETCH_ASSOC)
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-29
   * Proposito: guarda imagen de marca/categoria cuando el esquema ya fue autorizado.
   * Impacto: Catalogo ERP; no toca productos, ventas ni canales; conserva baja logica por estatus.
   */
  public function guardarImagenCatalogoMaestro($datos) {
    $tipoEntidad = $this->normalizarEntidadImagenCatalogo($this->texto($datos, "tipo_entidad"));
    $idEntidad = intval(isset($datos["id_entidad"]) ? $datos["id_entidad"] : 0);
    $idImagen = intval(isset($datos["id_imagen"]) ? $datos["id_imagen"] : 0);
    $url = $this->texto($datos, "url_imagen");
    if ($tipoEntidad === "" || $idEntidad <= 0 || $url === "") {
      return $this->respuesta(true, "warning", "Selecciona entidad y captura la ruta de la imagen");
    }
    if (!preg_match('/^(https?:\/\/|\/|media\/|assets\/|uploads\/)/i', $url)) {
      return $this->respuesta(true, "warning", "Usa una URL http(s) o una ruta local valida");
    }
    try {
      $db = $this->getConexion();
      if (!$this->esquemaImagenesCatalogoMaestroDisponible($db)) {
        return $this->respuesta(true, "warning", "Aplica primero el esquema de imagenes de marcas/categorias");
      }
      $meta = $this->metaImagenCatalogoMaestro($tipoEntidad);
      $this->validarEntidadImagenCatalogo($db, $tipoEntidad, $idEntidad);
      $tipoImagen = $this->opcion($datos, "tipo_imagen", $meta["tipos"], $meta["tipo_default"]);
      $estatus = $this->opcion($datos, "estatus", array("activo", "inactivo"), "activo");
      $db->beginTransaction();
      if ($estatus === "activo" && in_array($tipoImagen, $meta["tipos_unicos"], true)) {
        $db->prepare("UPDATE " . $meta["tabla"] . "
          SET estatus='inactivo', fecha_actualizacion=CURRENT_TIMESTAMP
          WHERE " . $meta["id_entidad"] . "=:entidad AND tipo_imagen=:tipo AND estatus='activo' AND " . $meta["id_imagen"] . "<>:imagen")
          ->execute(array(":entidad" => $idEntidad, ":tipo" => $tipoImagen, ":imagen" => $idImagen));
      }
      if ($idImagen > 0) {
        $stmt = $db->prepare("UPDATE " . $meta["tabla"] . " SET
            tipo_imagen=:tipo, url_imagen=:url, texto_alternativo=:alt, orden=:orden,
            estatus=:estatus, fecha_actualizacion=CURRENT_TIMESTAMP
          WHERE " . $meta["id_imagen"] . "=:imagen AND " . $meta["id_entidad"] . "=:entidad");
        $stmt->execute(array(
          ":tipo" => $tipoImagen,
          ":url" => substr($url, 0, 700),
          ":alt" => $this->texto($datos, "texto_alternativo") ?: null,
          ":orden" => intval(isset($datos["orden"]) ? $datos["orden"] : 0),
          ":estatus" => $estatus,
          ":imagen" => $idImagen,
          ":entidad" => $idEntidad
        ));
        if ($stmt->rowCount() === 0) {
          throw new Exception("La imagen no pertenece a esta entidad o ya no existe");
        }
      } else {
        $stmt = $db->prepare("INSERT INTO " . $meta["tabla"] . "
          (" . $meta["id_entidad"] . ", tipo_imagen, url_imagen, texto_alternativo, orden, estatus)
          VALUES (:entidad, :tipo, :url, :alt, :orden, :estatus)");
        $stmt->execute(array(
          ":entidad" => $idEntidad,
          ":tipo" => $tipoImagen,
          ":url" => substr($url, 0, 700),
          ":alt" => $this->texto($datos, "texto_alternativo") ?: null,
          ":orden" => intval(isset($datos["orden"]) ? $datos["orden"] : 0),
          ":estatus" => $estatus
        ));
        $idImagen = intval($db->lastInsertId());
      }
      $db->commit();
      return $this->respuesta(false, "success", "Imagen guardada", array(
        "tipo_entidad" => $tipoEntidad,
        "id_entidad" => $idEntidad,
        "id_imagen" => $idImagen
      ));
    } catch (Exception $e) {
      if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
      }
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-29
   * Proposito: desactiva imagen de marca/categoria sin borrado fisico.
   * Impacto: Catalogo ERP; mantiene trazabilidad visual de catalogos maestros.
   */
  public function desactivarImagenCatalogoMaestro($datos) {
    $tipoEntidad = $this->normalizarEntidadImagenCatalogo($this->texto($datos, "tipo_entidad"));
    $idEntidad = intval(isset($datos["id_entidad"]) ? $datos["id_entidad"] : 0);
    $idImagen = intval(isset($datos["id_imagen"]) ? $datos["id_imagen"] : 0);
    if ($tipoEntidad === "" || $idEntidad <= 0 || $idImagen <= 0) {
      return $this->respuesta(true, "warning", "Selecciona la imagen a desactivar");
    }
    try {
      $db = $this->getConexion();
      if (!$this->esquemaImagenesCatalogoMaestroDisponible($db)) {
        return $this->respuesta(true, "warning", "Aplica primero el esquema de imagenes de marcas/categorias");
      }
      $meta = $this->metaImagenCatalogoMaestro($tipoEntidad);
      $stmt = $db->prepare("UPDATE " . $meta["tabla"] . "
        SET estatus='inactivo', fecha_actualizacion=CURRENT_TIMESTAMP
        WHERE " . $meta["id_imagen"] . "=:imagen AND " . $meta["id_entidad"] . "=:entidad");
      $stmt->execute(array(":imagen" => $idImagen, ":entidad" => $idEntidad));
      if ($stmt->rowCount() === 0) {
        return $this->respuesta(true, "warning", "La imagen no pertenece a esta entidad o ya no existe");
      }
      return $this->respuesta(false, "success", "Imagen desactivada", array(
        "tipo_entidad" => $tipoEntidad,
        "id_entidad" => $idEntidad,
        "id_imagen" => $idImagen
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  private function normalizarEntidadImagenCatalogo($tipoEntidad) {
    $tipoEntidad = trim((string) $tipoEntidad);
    return in_array($tipoEntidad, array("marca", "categoria"), true) ? $tipoEntidad : "";
  }

  private function metaImagenCatalogoMaestro($tipoEntidad) {
    if ($tipoEntidad === "marca") {
      return array(
        "tabla" => "erp_catalogo_marca_imagenes",
        "id_imagen" => "id_marca_imagen",
        "id_entidad" => "id_marca_erp",
        "tabla_entidad" => "erp_catalogo_marcas",
        "tipos" => array("logo", "banner", "referencia"),
        "tipos_unicos" => array("logo"),
        "tipo_default" => "logo",
        "orden_tipos" => "'logo','banner','referencia'"
      );
    }
    return array(
      "tabla" => "erp_catalogo_categoria_imagenes",
      "id_imagen" => "id_categoria_imagen",
      "id_entidad" => "id_categoria_erp",
      "tabla_entidad" => "erp_catalogo_categorias",
      "tipos" => array("icono", "portada", "referencia"),
      "tipos_unicos" => array("icono", "portada"),
      "tipo_default" => "icono",
      "orden_tipos" => "'icono','portada','referencia'"
    );
  }

  private function validarEntidadImagenCatalogo($db, $tipoEntidad, $idEntidad) {
    $meta = $this->metaImagenCatalogoMaestro($tipoEntidad);
    $stmt = $db->prepare("SELECT " . $meta["id_entidad"] . " FROM " . $meta["tabla_entidad"] . " WHERE " . $meta["id_entidad"] . "=:id LIMIT 1");
    $stmt->execute(array(":id" => intval($idEntidad)));
    if (!$stmt->fetchColumn()) {
      throw new Exception($tipoEntidad === "marca" ? "Marca no encontrada" : "Categoria no encontrada");
    }
  }

  private function guardarMarca($datos) {
    $db = $this->getConexion();
    $id = intval(isset($datos["id"]) ? $datos["id"] : 0);
    $estatus = $this->opcion($datos, "estatus", array("activa", "inactiva"), "activa");
    if ($id > 0 && $estatus === "inactiva" && $this->contarUsoMarcaCatalogo($db, $id) > 0) {
      return $this->respuesta(true, "warning", "No puedes inactivar una marca con productos relacionados. Reasigna los productos antes de inactivarla.");
    }
    if ($id > 0) {
      $stmt = $db->prepare("UPDATE erp_catalogo_marcas SET codigo=:codigo, nombre=:nombre, descripcion=:descripcion, estatus=:estatus, fecha_actualizacion=CURRENT_TIMESTAMP WHERE id_marca_erp=:id");
    } else {
      $stmt = $db->prepare("INSERT INTO erp_catalogo_marcas (codigo, nombre, descripcion, estatus) VALUES (:codigo, :nombre, :descripcion, :estatus)");
    }
    $params = array(":codigo" => $this->texto($datos, "codigo"), ":nombre" => $this->texto($datos, "nombre"), ":descripcion" => $this->texto($datos, "descripcion"), ":estatus" => $estatus);
    if ($id > 0) { $params[":id"] = $id; }
    $stmt->execute($params);
    return $this->respuesta(false, "success", "Marca guardada", array("id" => $id ?: intval($db->lastInsertId())));
  }

  private function guardarCategoria($datos) {
    $db = $this->getConexion();
    $id = intval(isset($datos["id"]) ? $datos["id"] : 0);
    $padre = intval(isset($datos["id_categoria_padre"]) ? $datos["id_categoria_padre"] : 0);
    $tipoCategoria = $this->opcion($datos, "tipo_categoria", array("maestra", "legado_canal"), "maestra");
    $origen = $this->opcion($datos, "origen", array("erp", "ecommerce", "manual"), "erp");
    $permiteProductos = $this->booleano($datos, "permite_productos");
    $estatus = $this->opcion($datos, "estatus", array("activa", "inactiva"), "activa");
    if ($id > 0 && $padre === $id) {
      return $this->respuesta(true, "warning", "Una categoría no puede ser su propia categoría padre");
    }
    if ($id > 0 && $padre > 0 && $this->categoriaEsDescendiente($db, $id, $padre)) {
      return $this->respuesta(true, "warning", "La categoría padre seleccionada produciría un ciclo");
    }
    if ($padre > 0) {
      $stmt = $db->prepare("SELECT tipo_categoria FROM erp_catalogo_categorias WHERE id_categoria_erp=:padre AND estatus='activa'");
      $stmt->execute(array(":padre" => $padre));
      if ($stmt->fetchColumn() !== "maestra") {
        return $this->respuesta(true, "warning", "La categoria padre debe pertenecer al arbol maestro");
      }
    }
    if ($id > 0 && !$permiteProductos) {
      $stmt = $db->prepare("SELECT COUNT(*) FROM erp_catalogo_producto_categorias WHERE id_categoria_erp=:categoria");
      $stmt->execute(array(":categoria" => $id));
      if (intval($stmt->fetchColumn()) > 0) {
        return $this->respuesta(true, "warning", "No puedes convertir en estructural una categoria que ya tiene productos");
      }
    }
    if ($id > 0 && $estatus === "inactiva" && $this->contarUsoCategoriaCatalogo($db, $id) > 0) {
      return $this->respuesta(true, "warning", "No puedes inactivar una categoria con productos, subcategorias activas o navegacion relacionada.");
    }
    if ($tipoCategoria === "legado_canal") {
      $padre = 0;
      $permiteProductos = 1;
    }
    $rutaNivel = $this->rutaCategoria($db, $padre, $this->texto($datos, "nombre"));
    $db->beginTransaction();
    try {
      if ($id > 0) {
        $stmt = $db->prepare("UPDATE erp_catalogo_categorias SET id_categoria_padre=:padre, codigo=:codigo, nombre=:nombre,
          descripcion=:descripcion, ruta=:ruta, nivel=:nivel, tipo_categoria=:tipo_categoria, origen=:origen,
          permite_productos=:permite_productos, estatus=:estatus, fecha_actualizacion=CURRENT_TIMESTAMP
          WHERE id_categoria_erp=:id");
      } else {
        $stmt = $db->prepare("INSERT INTO erp_catalogo_categorias
          (id_categoria_padre, codigo, nombre, descripcion, ruta, nivel, tipo_categoria, origen, permite_productos, estatus)
          VALUES (:padre, :codigo, :nombre, :descripcion, :ruta, :nivel, :tipo_categoria, :origen, :permite_productos, :estatus)");
      }
      $params = array(
        ":padre" => $padre ?: null,
        ":codigo" => $this->texto($datos, "codigo"),
        ":nombre" => $this->texto($datos, "nombre"),
        ":descripcion" => $this->texto($datos, "descripcion"),
        ":ruta" => $rutaNivel["ruta"],
        ":nivel" => $rutaNivel["nivel"],
        ":tipo_categoria" => $tipoCategoria,
        ":origen" => $origen,
        ":permite_productos" => $permiteProductos,
        ":estatus" => $estatus
      );
      if ($id > 0) { $params[":id"] = $id; }
      $stmt->execute($params);
      $id = $id ?: intval($db->lastInsertId());
      $this->recalcularDescendientes($db, $id);
      $db->commit();
      return $this->respuesta(false, "success", "Categoría guardada", array("id" => $id));
    } catch (Exception $e) {
      if ($db->inTransaction()) { $db->rollBack(); }
      throw $e;
    }
  }

  private function guardarUnidad($datos) {
    $db = $this->getConexion();
    $id = intval(isset($datos["id"]) ? $datos["id"] : 0);
    if ($this->texto($datos, "abreviatura") === "") {
      return $this->respuesta(true, "warning", "La abreviatura es obligatoria");
    }
    $estatus = $this->opcion($datos, "estatus", array("activa", "inactiva"), "activa");
    if ($id > 0 && $estatus === "inactiva" && $this->contarUsoUnidadCatalogo($db, $id) > 0) {
      return $this->respuesta(true, "warning", "No puedes inactivar una unidad usada por SKUs, proveedores, paquetes o configuraciones operativas.");
    }
    if ($id > 0) {
      $stmt = $db->prepare("UPDATE erp_catalogo_unidades SET codigo=:codigo, nombre=:nombre, abreviatura=:abreviatura, tipo_magnitud=:magnitud, decimales_permitidos=:decimales, clave_sat=:sat, estatus=:estatus, fecha_actualizacion=CURRENT_TIMESTAMP WHERE id_unidad=:id");
    } else {
      $stmt = $db->prepare("INSERT INTO erp_catalogo_unidades (codigo, nombre, abreviatura, tipo_magnitud, decimales_permitidos, clave_sat, estatus) VALUES (:codigo, :nombre, :abreviatura, :magnitud, :decimales, :sat, :estatus)");
    }
    $params = array(":codigo" => $this->texto($datos, "codigo"), ":nombre" => $this->texto($datos, "nombre"), ":abreviatura" => $this->texto($datos, "abreviatura"), ":magnitud" => $this->texto($datos, "tipo_magnitud", "unidad"), ":decimales" => $this->booleano($datos, "decimales_permitidos"), ":sat" => $this->texto($datos, "clave_sat") ?: null, ":estatus" => $estatus);
    if ($id > 0) { $params[":id"] = $id; }
    $stmt->execute($params);
    return $this->respuesta(false, "success", "Unidad guardada", array("id" => $id ?: intval($db->lastInsertId())));
  }

  private function guardarAtributo($datos) {
    $db = $this->getConexion();
    $id = intval(isset($datos["id"]) ? $datos["id"] : 0);
    $tipo = $this->opcion($datos, "tipo_dato", array("texto", "numero", "booleano", "fecha", "lista", "color"), "texto");
    $estatus = $this->opcion($datos, "estatus", array("activo", "inactivo"), "activo");
    if ($id > 0 && $estatus === "inactivo" && $this->contarUsoAtributoCatalogo($db, $id) > 0) {
      return $this->respuesta(true, "warning", "No puedes inactivar un atributo usado por SKUs o variantes. Reasigna primero esos SKUs.");
    }
    $configuracion = null;
    if ($tipo === "lista") {
      $opciones = preg_split('/[\r\n,]+/', $this->texto($datos, "opciones_lista"));
      $opciones = array_values(array_unique(array_filter(array_map("trim", $opciones))));
      if (empty($opciones)) {
        throw new Exception("Agrega al menos una opción para el atributo tipo lista");
      }
      $configuracion = json_encode(array("opciones" => $opciones), JSON_UNESCAPED_UNICODE);
    }
    if ($id > 0) {
      $stmt = $db->prepare("UPDATE erp_catalogo_atributos SET codigo=:codigo, nombre=:nombre, tipo_dato=:tipo, unidad=:unidad, configuracion_json=:configuracion, es_variante=:variante, estatus=:estatus WHERE id_atributo_erp=:id");
    } else {
      $stmt = $db->prepare("INSERT INTO erp_catalogo_atributos (codigo, nombre, tipo_dato, unidad, configuracion_json, es_variante, estatus) VALUES (:codigo, :nombre, :tipo, :unidad, :configuracion, :variante, :estatus)");
    }
    $params = array(":codigo" => $this->texto($datos, "codigo"), ":nombre" => $this->texto($datos, "nombre"), ":tipo" => $tipo, ":unidad" => $this->texto($datos, "unidad") ?: null, ":configuracion" => $configuracion, ":variante" => $this->booleano($datos, "es_variante"), ":estatus" => $estatus);
    if ($id > 0) { $params[":id"] = $id; }
    $stmt->execute($params);
    return $this->respuesta(false, "success", "Atributo guardado", array("id" => $id ?: intval($db->lastInsertId())));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-29
   * Proposito: cuenta relaciones activas antes de inactivar una marca maestra.
   * Impacto: Catalogo ERP; evita que productos existentes queden apuntando a una marca inactiva.
   */
  private function contarUsoMarcaCatalogo($db, $idMarca) {
    return $this->contarCatalogo($db, "SELECT COUNT(*) FROM erp_catalogo_productos WHERE id_marca_erp=:id AND estatus<>'fusionado'", array(":id" => intval($idMarca)));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-29
   * Proposito: cuenta productos, subcategorias y navegacion antes de inactivar una categoria.
   * Impacto: Catalogo ERP; protege categoria principal, arbol maestro y taxonomias relacionadas.
   */
  private function contarUsoCategoriaCatalogo($db, $idCategoria) {
    $idCategoria = intval($idCategoria);
    $total = $this->contarCatalogo($db, "SELECT COUNT(*) FROM erp_catalogo_producto_categorias WHERE id_categoria_erp=:id", array(":id" => $idCategoria));
    $total += $this->contarCatalogo($db, "SELECT COUNT(*) FROM erp_catalogo_categorias WHERE id_categoria_padre=:id AND estatus='activa'", array(":id" => $idCategoria));
    if ($this->tablaExisteCatalogo($db, "erp_catalogo_taxonomia_nodos")) {
      $total += $this->contarCatalogo($db, "SELECT COUNT(*) FROM erp_catalogo_taxonomia_nodos WHERE id_categoria_erp=:id AND estatus='activo'", array(":id" => $idCategoria));
    }
    return $total;
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-29
   * Proposito: cuenta usos de una unidad antes de inactivarla.
   * Impacto: Catalogo ERP; protege SKUs, proveedor, paquetes y preparaciones que dependen de la unidad.
   */
  private function contarUsoUnidadCatalogo($db, $idUnidad) {
    $idUnidad = intval($idUnidad);
    $total = $this->contarCatalogo($db, "SELECT COUNT(*) FROM erp_catalogo_skus WHERE id_unidad_base=:id AND estatus<>'fusionado'", array(":id" => $idUnidad));
    $total += $this->contarCatalogo($db, "SELECT COUNT(*) FROM erp_catalogo_sku_proveedores WHERE id_unidad_compra=:id AND estatus='activo'", array(":id" => $idUnidad));
    if ($this->tablaExisteCatalogo($db, "erp_catalogo_sku_paquete_componentes")) {
      $total += $this->contarCatalogo($db, "SELECT COUNT(*) FROM erp_catalogo_sku_paquete_componentes WHERE id_unidad=:id AND estatus='activo'", array(":id" => $idUnidad));
    }
    if ($this->tablaExisteCatalogo($db, "erp_catalogo_sku_paquete_grupo_opciones")) {
      $total += $this->contarCatalogo($db, "SELECT COUNT(*) FROM erp_catalogo_sku_paquete_grupo_opciones WHERE id_unidad=:id AND estatus='activo'", array(":id" => $idUnidad));
    }
    return $total;
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-29
   * Proposito: cuenta usos de atributo antes de inactivarlo.
   * Impacto: Catalogo ERP; evita romper variantes o fichas tecnicas ya capturadas en SKUs.
   */
  private function contarUsoAtributoCatalogo($db, $idAtributo) {
    return $this->contarCatalogo($db, "SELECT COUNT(*) FROM erp_catalogo_sku_atributos WHERE id_atributo_erp=:id", array(":id" => intval($idAtributo)));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-29
   * Proposito: ejecuta conteos de uso con parametros consistentes para protecciones de catalogos maestros.
   * Impacto: Catalogo ERP; concentra consultas de validacion previas a baja logica.
   */
  private function contarCatalogo($db, $sql, $params) {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return intval($stmt->fetchColumn());
  }

  private function categoriaEsDescendiente($db, $idCategoria, $posiblePadre) {
    $actual = $posiblePadre;
    while ($actual > 0) {
      if ($actual === $idCategoria) { return true; }
      $stmt = $db->prepare("SELECT id_categoria_padre FROM erp_catalogo_categorias WHERE id_categoria_erp=:id");
      $stmt->execute(array(":id" => $actual));
      $actual = intval($stmt->fetchColumn());
    }
    return false;
  }

  private function rutaCategoria($db, $idPadre, $nombre) {
    if ($idPadre <= 0) { return array("ruta" => $nombre, "nivel" => 0); }
    $stmt = $db->prepare("SELECT ruta, nivel FROM erp_catalogo_categorias WHERE id_categoria_erp=:id");
    $stmt->execute(array(":id" => $idPadre));
    $padre = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$padre) { throw new Exception("Categoría padre no encontrada"); }
    return array("ruta" => $padre["ruta"] . " / " . $nombre, "nivel" => intval($padre["nivel"]) + 1);
  }

  private function recalcularDescendientes($db, $idPadre) {
    $stmt = $db->prepare("SELECT id_categoria_erp, nombre FROM erp_catalogo_categorias WHERE id_categoria_padre=:padre");
    $stmt->execute(array(":padre" => $idPadre));
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $hija) {
      $rutaNivel = $this->rutaCategoria($db, $idPadre, $hija["nombre"]);
      $update = $db->prepare("UPDATE erp_catalogo_categorias SET ruta=:ruta, nivel=:nivel, fecha_actualizacion=CURRENT_TIMESTAMP WHERE id_categoria_erp=:id");
      $update->execute(array(":ruta" => $rutaNivel["ruta"], ":nivel" => $rutaNivel["nivel"], ":id" => $hija["id_categoria_erp"]));
      $this->recalcularDescendientes($db, intval($hija["id_categoria_erp"]));
    }
  }

  public function consultarProducto($idProducto) {
    try {
      $db = $this->getConexion();
      $stmt = $db->prepare("SELECT p.*, m.nombre AS marca,
        pc.id_categoria_erp, c.nombre AS categoria
        FROM erp_catalogo_productos p
        LEFT JOIN erp_catalogo_marcas m ON m.id_marca_erp = p.id_marca_erp
        LEFT JOIN erp_catalogo_producto_categorias pc ON pc.id_producto_erp = p.id_producto_erp AND pc.es_principal = 1
        LEFT JOIN erp_catalogo_categorias c ON c.id_categoria_erp = pc.id_categoria_erp
        WHERE p.id_producto_erp = :producto LIMIT 1");
      $stmt->execute(array(":producto" => intval($idProducto)));
      $producto = $stmt->fetch(PDO::FETCH_ASSOC);
      if (!$producto) {
        return $this->respuesta(true, "warning", "Producto ERP no encontrado");
      }
      $categoriasProducto = $this->consultarCategoriasProducto($db, intval($idProducto));

      $recepcionVariableSelect = $this->esquemaRecepcionVariableDisponible($db)
        ? "r.requiere_cantidad_variable_recepcion, r.requiere_unidades_fisicas_recepcion, r.tolerancia_recepcion_porcentaje, r.nota_recepcion_variable,"
        : "0 AS requiere_cantidad_variable_recepcion, 0 AS requiere_unidades_fisicas_recepcion, NULL AS tolerancia_recepcion_porcentaje, NULL AS nota_recepcion_variable,";
      $stmt = $db->prepare("SELECT s.id_sku, s.sku, s.nombre, s.tipo_inventario, s.id_unidad_base, s.factor_unidad_base, s.costo_referencia,
        s.permite_venta_sin_existencia, s.estatus, u.nombre AS unidad, u.abreviatura,
        u.tipo_magnitud, u.decimales_permitidos,
        cod.codigo AS codigo_barras, pr.precio, pr.moneda,
        r.controla_inventario, r.requiere_lote, r.requiere_caducidad, r.requiere_serie, r.requiere_serie_fabricante,
        r.generar_etiqueta_interna, r.requiere_escaneo_venta,
        r.permite_venta_fraccionaria, r.precision_decimal, r.incremento_minimo_venta,
        r.unidad_venta_label, r.permite_etiqueta_fraccionada, r.prefijo_etiqueta_interna,
        r.plantilla_etiqueta, r.tipo_etiqueta_seguridad, r.instrucciones_etiquetado,
        r.estrategia_salida, r.stock_minimo, r.stock_maximo, r.punto_reorden,
        CASE WHEN r.id_sku IS NULL THEN 0 ELSE 1 END AS tiene_regla_inventario,
        r.permite_existencia_negativa, r.dias_alerta_caducidad, r.dias_minimos_recepcion,
        " . $recepcionVariableSelect . "
        imp.clave_producto_sat, imp.clave_unidad_sat, imp.objeto_impuesto,
        imp.iva_porcentaje, imp.ieps_porcentaje, imp.incluye_impuestos,
        (SELECT COUNT(*) FROM erp_catalogo_sku_proveedores sp WHERE sp.id_sku=s.id_sku AND sp.estatus='activo') AS proveedores_activos,
        (SELECT COUNT(*) FROM erp_catalogo_canales_vinculos v WHERE v.id_sku=s.id_sku AND v.estatus='activo') AS vinculos_ecommerce,
        (SELECT COUNT(*) FROM erp_compras_solicitudes_detalle sd WHERE sd.id_sku_erp=s.id_sku) AS usos_solicitudes,
        (SELECT COUNT(*) FROM erp_compras_ordenes_detalle od WHERE od.id_sku_erp=s.id_sku) AS usos_ordenes,
        (SELECT COUNT(*) FROM erp_almacen_recepciones_detalle rd WHERE rd.id_sku_erp=s.id_sku) AS usos_recepciones,
        (SELECT COUNT(*) FROM erp_inventario_movimientos im WHERE im.id_sku_erp=s.id_sku) AS usos_movimientos,
        (SELECT COUNT(*) FROM erp_inventario_existencias ie WHERE ie.id_sku_erp=s.id_sku) AS usos_existencias,
        (SELECT COUNT(*) FROM erp_catalogo_incidencias_calidad ic
          WHERE ic.id_sku=s.id_sku AND ic.estatus IN ('pendiente','en_revision','bloqueada')) AS incidencias_calidad_abiertas
        FROM erp_catalogo_skus s
        INNER JOIN erp_catalogo_unidades u ON u.id_unidad = s.id_unidad_base
        LEFT JOIN erp_catalogo_sku_codigos cod ON cod.id_sku = s.id_sku AND cod.es_principal = 1 AND cod.estatus='activo'
          AND cod.id_sku_codigo = (
            SELECT c2.id_sku_codigo FROM erp_catalogo_sku_codigos c2
            WHERE c2.id_sku=s.id_sku AND c2.es_principal=1 AND c2.estatus='activo'
            ORDER BY c2.tipo_codigo='codigo_barras' DESC, c2.id_sku_codigo DESC LIMIT 1
          )
        LEFT JOIN erp_catalogo_sku_precios pr ON pr.id_sku = s.id_sku AND pr.lista_precio = 'general'
        LEFT JOIN erp_catalogo_sku_reglas_inventario r ON r.id_sku = s.id_sku
        LEFT JOIN erp_catalogo_sku_impuestos imp ON imp.id_sku = s.id_sku
        WHERE s.id_producto_erp = :producto ORDER BY s.id_sku");
      $stmt->execute(array(":producto" => intval($idProducto)));
      $skus = $stmt->fetchAll(PDO::FETCH_ASSOC);

      return $this->respuesta(false, "success", "Producto ERP consultado", array(
        "producto" => $producto,
        "categorias_producto" => $categoriasProducto,
        "skus" => $skus,
        "imagenes" => $this->consultarImagenesProducto($db, intval($idProducto)),
        "proveedores" => $this->consultarSkuProveedores($db, intval($idProducto)),
        "presentaciones" => $this->consultarSkuPresentaciones($db, intval($idProducto)),
        "paquetes" => $this->consultarPaquetesProducto($db, intval($idProducto)),
        "variantes" => $this->consultarVariantesProducto($db, intval($idProducto), $skus)
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-11
   * Proposito: lista categoria principal y secundarias de un producto para edicion controlada.
   * Impacto: Catalogo ERP; habilita clasificacion multiple sin cambiar esquema ni afectar otros modulos.
   */
  private function consultarCategoriasProducto($db, $idProducto) {
    $stmt = $db->prepare("SELECT pc.id_categoria_erp, pc.es_principal, c.nombre, c.ruta
      FROM erp_catalogo_producto_categorias pc
      INNER JOIN erp_catalogo_categorias c ON c.id_categoria_erp=pc.id_categoria_erp
      WHERE pc.id_producto_erp=:producto
      ORDER BY pc.es_principal DESC, c.ruta, c.nombre");
    $stmt->execute(array(":producto" => intval($idProducto)));
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public function guardarVariantesProducto($datos) {
    $idProducto = intval(isset($datos["id_producto_erp"]) ? $datos["id_producto_erp"] : 0);
    $idAtributo = intval(isset($datos["id_atributo_erp"]) ? $datos["id_atributo_erp"] : 0);
    $nombreNuevo = $this->texto($datos, "nuevo_atributo");
    if ($nombreNuevo !== "") {
      $idAtributo = 0;
    }
    $valores = isset($datos["valores"]) && is_array($datos["valores"]) ? $datos["valores"] : array();
    if ($idProducto <= 0 || ($idAtributo <= 0 && $nombreNuevo === "")) {
      return $this->respuesta(true, "warning", "Selecciona o crea un atributo de variante");
    }

    $db = $this->getConexion();
    try {
      $db->beginTransaction();
      $stmt = $db->prepare("SELECT id_sku FROM erp_catalogo_skus WHERE id_producto_erp=:producto ORDER BY id_sku");
      $stmt->execute(array(":producto" => $idProducto));
      $idsSku = array_map("intval", $stmt->fetchAll(PDO::FETCH_COLUMN));
      if (empty($idsSku)) {
        throw new Exception("El producto no tiene SKU para configurar");
      }

      if ($idAtributo <= 0) {
        $stmt = $db->prepare("SELECT id_atributo_erp FROM erp_catalogo_atributos WHERE LOWER(TRIM(nombre))=LOWER(TRIM(:nombre)) AND estatus='activo' ORDER BY id_atributo_erp LIMIT 1");
        $stmt->execute(array(":nombre" => $nombreNuevo));
        $idAtributo = intval($stmt->fetchColumn());
        if ($idAtributo > 0) {
          $db->prepare("UPDATE erp_catalogo_atributos SET es_variante=1 WHERE id_atributo_erp=:atributo")
            ->execute(array(":atributo" => $idAtributo));
        } else {
          $codigo = "VAR-" . strtoupper(substr(md5($nombreNuevo), 0, 12));
          $stmt = $db->prepare("INSERT INTO erp_catalogo_atributos (codigo, nombre, tipo_dato, es_variante, estatus)
            VALUES (:codigo, :nombre, 'texto', 1, 'activo')
            ON DUPLICATE KEY UPDATE id_atributo_erp=LAST_INSERT_ID(id_atributo_erp), es_variante=1, estatus='activo'");
          $stmt->execute(array(":codigo" => $codigo, ":nombre" => $nombreNuevo));
          $idAtributo = intval($db->lastInsertId());
        }
      } else {
        $stmt = $db->prepare("UPDATE erp_catalogo_atributos SET es_variante=1 WHERE id_atributo_erp=:atributo AND estatus='activo'");
        $stmt->execute(array(":atributo" => $idAtributo));
        if ($stmt->rowCount() === 0) {
          $stmt = $db->prepare("SELECT id_atributo_erp FROM erp_catalogo_atributos WHERE id_atributo_erp=:atributo AND estatus='activo'");
          $stmt->execute(array(":atributo" => $idAtributo));
          if (!$stmt->fetchColumn()) {
            throw new Exception("El atributo seleccionado no existe o está inactivo");
          }
        }
      }

      $stmt = $db->prepare("SELECT tipo_dato, configuracion_json FROM erp_catalogo_atributos WHERE id_atributo_erp=:atributo");
      $stmt->execute(array(":atributo" => $idAtributo));
      $definicionAtributo = $stmt->fetch(PDO::FETCH_ASSOC);

      $upsert = $db->prepare("INSERT INTO erp_catalogo_sku_atributos (id_sku, id_atributo_erp, valor)
        VALUES (:sku, :atributo, :valor)
        ON DUPLICATE KEY UPDATE valor=VALUES(valor), fecha_actualizacion=CURRENT_TIMESTAMP");
      $eliminar = $db->prepare("DELETE FROM erp_catalogo_sku_atributos WHERE id_sku=:sku AND id_atributo_erp=:atributo");
      foreach ($idsSku as $idSku) {
        $valor = isset($valores[$idSku]) ? $this->normalizarValorAtributo($valores[$idSku], $definicionAtributo) : "";
        if ($valor === "") {
          $eliminar->execute(array(":sku" => $idSku, ":atributo" => $idAtributo));
        } else {
          $upsert->execute(array(":sku" => $idSku, ":atributo" => $idAtributo, ":valor" => substr($valor, 0, 500)));
        }
      }

      $duplicadas = $this->combinacionesVariantesDuplicadas($db, $idProducto);
      $db->prepare("UPDATE erp_catalogo_productos SET maneja_variantes=1, fecha_actualizacion=CURRENT_TIMESTAMP WHERE id_producto_erp=:producto")
        ->execute(array(":producto" => $idProducto));
      $db->commit();
      return $this->respuesta(
        false,
        "success",
        empty($duplicadas)
          ? "Valores de variante guardados"
          : "Valores guardados; aún hay combinaciones de variantes repetidas",
        array(
          "id_atributo_erp" => $idAtributo,
          "duplicadas" => $duplicadas
        )
      );
    } catch (Exception $e) {
      if ($db->inTransaction()) {
        $db->rollBack();
      }
      return $this->respuesta(true, "warning", $e->getMessage());
    }
  }

  public function guardarImagenProducto($datos) {
    $idImagen = intval(isset($datos["id_imagen_erp"]) ? $datos["id_imagen_erp"] : 0);
    $idProducto = intval(isset($datos["id_producto_erp"]) ? $datos["id_producto_erp"] : 0);
    $idSku = intval(isset($datos["id_sku"]) ? $datos["id_sku"] : 0);
    $url = $this->texto($datos, "url_imagen");
    if ($idProducto <= 0 || $url === "") {
      return $this->respuesta(true, "warning", "Selecciona producto y captura la ruta de la imagen");
    }
    if (!preg_match('/^(https?:\/\/|\/|media\/|assets\/|uploads\/)/i', $url)) {
      return $this->respuesta(true, "warning", "Usa una URL http(s) o una ruta local valida");
    }

    $db = $this->getConexion();
    try {
      $db->beginTransaction();
      $stmt = $db->prepare("SELECT id_producto_erp FROM erp_catalogo_productos WHERE id_producto_erp=:producto LIMIT 1");
      $stmt->execute(array(":producto" => $idProducto));
      if (!$stmt->fetchColumn()) {
        throw new Exception("Producto ERP no encontrado");
      }
      if ($idSku > 0) {
        $stmt = $db->prepare("SELECT id_sku FROM erp_catalogo_skus WHERE id_sku=:sku AND id_producto_erp=:producto LIMIT 1");
        $stmt->execute(array(":sku" => $idSku, ":producto" => $idProducto));
        if (!$stmt->fetchColumn()) {
          throw new Exception("El SKU seleccionado no pertenece al producto");
        }
      }

      $tipo = $this->opcion($datos, "tipo_imagen", array("portada", "galeria", "detalle", "empaque", "referencia"), "galeria");
      $estatus = $this->opcion($datos, "estatus", array("activo", "inactivo"), "activo");
      if ($tipo === "portada" && $estatus === "activo") {
        $db->prepare("UPDATE erp_catalogo_imagenes SET tipo_imagen='galeria', fecha_actualizacion=CURRENT_TIMESTAMP WHERE id_producto_erp=:producto AND tipo_imagen='portada' AND estatus='activo' AND id_imagen_erp<>:imagen")
          ->execute(array(":producto" => $idProducto, ":imagen" => $idImagen));
      }

      if ($idImagen > 0) {
        $stmt = $db->prepare("UPDATE erp_catalogo_imagenes SET
          id_sku=:sku, tipo_imagen=:tipo, url_imagen=:url, texto_alternativo=:alt,
          orden=:orden, estatus=:estatus, fecha_actualizacion=CURRENT_TIMESTAMP
          WHERE id_imagen_erp=:imagen AND id_producto_erp=:producto");
        $stmt->execute(array(
          ":sku" => $idSku > 0 ? $idSku : null,
          ":tipo" => $tipo,
          ":url" => substr($url, 0, 700),
          ":alt" => $this->texto($datos, "texto_alternativo") ?: null,
          ":orden" => intval(isset($datos["orden"]) ? $datos["orden"] : 0),
          ":estatus" => $estatus,
          ":imagen" => $idImagen,
          ":producto" => $idProducto
        ));
        if ($stmt->rowCount() === 0) {
          $stmt = $db->prepare("SELECT id_imagen_erp FROM erp_catalogo_imagenes WHERE id_imagen_erp=:imagen AND id_producto_erp=:producto");
          $stmt->execute(array(":imagen" => $idImagen, ":producto" => $idProducto));
          if (!$stmt->fetchColumn()) {
            throw new Exception("La imagen no pertenece al producto seleccionado");
          }
        }
      } else {
        $stmt = $db->prepare("INSERT INTO erp_catalogo_imagenes
          (id_producto_erp, id_sku, tipo_imagen, url_imagen, texto_alternativo, orden, fuente, estatus)
          VALUES (:producto, :sku, :tipo, :url, :alt, :orden, 'erp', :estatus)");
        $stmt->execute(array(
          ":producto" => $idProducto,
          ":sku" => $idSku > 0 ? $idSku : null,
          ":tipo" => $tipo,
          ":url" => substr($url, 0, 700),
          ":alt" => $this->texto($datos, "texto_alternativo") ?: null,
          ":orden" => intval(isset($datos["orden"]) ? $datos["orden"] : 0),
          ":estatus" => $estatus
        ));
        $idImagen = intval($db->lastInsertId());
      }

      $db->commit();
      return $this->respuesta(false, "success", "Imagen guardada", array("id_producto_erp" => $idProducto, "id_imagen_erp" => $idImagen));
    } catch (Exception $e) {
      if ($db->inTransaction()) {
        $db->rollBack();
      }
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  public function desactivarImagenProducto($datos) {
    $idImagen = intval(isset($datos["id_imagen_erp"]) ? $datos["id_imagen_erp"] : 0);
    $idProducto = intval(isset($datos["id_producto_erp"]) ? $datos["id_producto_erp"] : 0);
    if ($idImagen <= 0 || $idProducto <= 0) {
      return $this->respuesta(true, "warning", "Selecciona la imagen a desactivar");
    }
    try {
      $db = $this->getConexion();
      $stmt = $db->prepare("UPDATE erp_catalogo_imagenes SET estatus='inactivo', fecha_actualizacion=CURRENT_TIMESTAMP WHERE id_imagen_erp=:imagen AND id_producto_erp=:producto");
      $stmt->execute(array(":imagen" => $idImagen, ":producto" => $idProducto));
      return $this->respuesta(false, "success", "Imagen desactivada", array("id_imagen_erp" => $idImagen));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  public function auditarRecuperacionImagenesEcommerce($idProducto = 0) {
    $idProducto = intval($idProducto);
    try {
      $db = $this->getConexion();
      if (!$this->tablaExisteCatalogo($db, "ecom_productos_imagenes")) {
        return $this->respuesta(true, "warning", "No existe ecom_productos_imagenes en esta base");
      }

      $resumen = array();
      $resumen["ecom_imagenes"] = intval($db->query("SELECT COUNT(*) FROM ecom_productos_imagenes")->fetchColumn());
      $resumen["ecom_productos_con_imagen"] = intval($db->query("SELECT COUNT(DISTINCT id_producto) FROM ecom_productos_imagenes WHERE TRIM(COALESCE(url_imagen,''))<>''")->fetchColumn());
      $resumen["ecom_sin_url"] = intval($db->query("SELECT COUNT(*) FROM ecom_productos_imagenes WHERE TRIM(COALESCE(url_imagen,''))=''")->fetchColumn());
      $resumen["erp_imagenes"] = intval($db->query("SELECT COUNT(*) FROM erp_catalogo_imagenes")->fetchColumn());
      $resumen["erp_imagenes_ecommerce"] = intval($db->query("SELECT COUNT(*) FROM erp_catalogo_imagenes WHERE fuente='ecommerce'")->fetchColumn());
      $resumen["erp_productos_sin_imagen_activa"] = intval($db->query("SELECT COUNT(*) FROM erp_catalogo_productos p WHERE p.estatus<>'fusionado' AND NOT EXISTS (SELECT 1 FROM erp_catalogo_imagenes i WHERE i.id_producto_erp=p.id_producto_erp AND i.estatus='activo')")->fetchColumn());

      $stmt = $db->query("SELECT COUNT(*) imagenes_vinculables,
        COUNT(DISTINCT v.id_producto_erp) productos_vinculados_con_imagen_ecom,
        SUM(i.id_imagen_erp IS NOT NULL) ya_en_erp,
        SUM(i.id_imagen_erp IS NULL) faltantes_en_erp
        FROM ecom_productos_imagenes e
        INNER JOIN erp_catalogo_canales_vinculos v
          ON v.canal='ecommerce' AND CAST(v.id_externo AS UNSIGNED)=e.id_producto AND v.id_producto_erp IS NOT NULL
        LEFT JOIN erp_catalogo_imagenes i
          ON i.fuente='ecommerce' AND i.id_externo=CAST(e.id_producto_imagen AS CHAR)
        WHERE TRIM(COALESCE(e.url_imagen,''))<>''");
      $resumen = array_merge($resumen, $stmt->fetch(PDO::FETCH_ASSOC));

      $resumen["productos_sin_imagen_con_ecom_disponible"] = intval($db->query("SELECT COUNT(DISTINCT v.id_producto_erp)
        FROM erp_catalogo_canales_vinculos v
        WHERE v.canal='ecommerce' AND v.id_producto_erp IS NOT NULL
          AND NOT EXISTS (SELECT 1 FROM erp_catalogo_imagenes i WHERE i.id_producto_erp=v.id_producto_erp AND i.estatus='activo')
          AND EXISTS (SELECT 1 FROM ecom_productos_imagenes e WHERE e.id_producto=CAST(v.id_externo AS UNSIGNED) AND TRIM(COALESCE(e.url_imagen,''))<>'')")->fetchColumn());
      $resumen["productos_sin_imagen_sin_ecom_disponible"] = intval($db->query("SELECT COUNT(DISTINCT p.id_producto_erp)
        FROM erp_catalogo_productos p
        WHERE p.estatus<>'fusionado'
          AND NOT EXISTS (SELECT 1 FROM erp_catalogo_imagenes i WHERE i.id_producto_erp=p.id_producto_erp AND i.estatus='activo')
          AND NOT EXISTS (
            SELECT 1 FROM erp_catalogo_canales_vinculos v
            INNER JOIN ecom_productos_imagenes e
              ON e.id_producto=CAST(v.id_externo AS UNSIGNED) AND TRIM(COALESCE(e.url_imagen,''))<>''
            WHERE v.canal='ecommerce' AND v.id_producto_erp=p.id_producto_erp
          )")->fetchColumn());

      return $this->respuesta(false, "success", "Auditoria de imagenes ecommerce consultada", array(
        "resumen" => $resumen,
        "candidatas" => $this->candidatasImagenesEcommerce($db, $idProducto > 0 ? 0 : 25, $idProducto),
        "id_producto_erp" => $idProducto
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  /**
   * IA: Codex GPT-5 | Fecha: 2026-06-26
   * Proposito: recuperar una sola imagen ecommerce seleccionada por el usuario para el producto ERP abierto.
   * Impacto: Catalogo ERP; evita relacionar imagenes de forma masiva o ambigua.
   * Contrato: requiere id_producto_erp, id_producto_imagen y tipo_imagen permitido; valida vinculo ecommerce->ERP.
   */
  public function recuperarImagenEcommerceSeleccionada($datos) {
    $idProducto = intval(isset($datos["id_producto_erp"]) ? $datos["id_producto_erp"] : 0);
    $idImagenEcom = intval(isset($datos["id_producto_imagen"]) ? $datos["id_producto_imagen"] : 0);
    $tipo = $this->opcion($datos, "tipo_imagen", array("portada", "galeria", "detalle", "empaque", "referencia"), "galeria");
    if ($idProducto <= 0 || $idImagenEcom <= 0) {
      return $this->respuesta(true, "warning", "Selecciona producto e imagen ecommerce");
    }

    $db = $this->getConexion();
    try {
      if (!$this->tablaExisteCatalogo($db, "ecom_productos_imagenes")) {
        return $this->respuesta(true, "warning", "No existe ecom_productos_imagenes en esta base");
      }
      $stmt = $db->prepare("SELECT e.id_producto_imagen, e.id_producto, v.id_producto_erp, p.nombre nombre_producto,
          e.url_imagen,
          ROW_NUMBER() OVER (PARTITION BY v.id_producto_erp ORDER BY e.tipo_imagen='portada' DESC, e.id_producto_imagen) - 1 AS orden_sugerido
        FROM ecom_productos_imagenes e
        INNER JOIN erp_catalogo_canales_vinculos v
          ON v.canal='ecommerce' AND CAST(v.id_externo AS UNSIGNED)=e.id_producto AND v.id_producto_erp=:producto
        INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=v.id_producto_erp AND p.estatus<>'fusionado'
        LEFT JOIN erp_catalogo_imagenes i
          ON i.fuente='ecommerce' AND i.id_externo=CAST(e.id_producto_imagen AS CHAR)
        WHERE e.id_producto_imagen=:imagen
          AND TRIM(COALESCE(e.url_imagen,''))<>''
          AND e.url_imagen LIKE 'media/%'
          AND i.id_imagen_erp IS NULL
        LIMIT 1");
      $stmt->execute(array(":producto" => $idProducto, ":imagen" => $idImagenEcom));
      $fila = $stmt->fetch(PDO::FETCH_ASSOC);
      if (!$fila) {
        return $this->respuesta(true, "warning", "La imagen no esta disponible o no pertenece al producto abierto");
      }

      $db->beginTransaction();
      if ($tipo === "portada") {
        $db->prepare("UPDATE erp_catalogo_imagenes
          SET tipo_imagen='galeria', fecha_actualizacion=CURRENT_TIMESTAMP
          WHERE id_producto_erp=:producto AND tipo_imagen='portada' AND estatus='activo'")
          ->execute(array(":producto" => $idProducto));
      }
      $stmt = $db->prepare("INSERT INTO erp_catalogo_imagenes
        (id_producto_erp, id_sku, tipo_imagen, url_imagen, texto_alternativo, orden, fuente, id_externo, estatus)
        VALUES (:producto, NULL, :tipo, :url, :alt, :orden, 'ecommerce', :externo, 'activo')");
      $stmt->execute(array(
        ":producto" => $idProducto,
        ":tipo" => $tipo,
        ":url" => substr($fila["url_imagen"], 0, 700),
        ":alt" => $fila["nombre_producto"] ?: null,
        ":orden" => intval($fila["orden_sugerido"]),
        ":externo" => (string) $fila["id_producto_imagen"]
      ));
      $idImagen = intval($db->lastInsertId());
      $db->commit();

      return $this->respuesta(false, "success", "Imagen ecommerce relacionada con el producto", array(
        "id_producto_erp" => $idProducto,
        "id_imagen_erp" => $idImagen,
        "id_producto_imagen" => $idImagenEcom,
        "tipo_imagen" => $tipo
      ));
    } catch (Exception $e) {
      if ($db->inTransaction()) {
        $db->rollBack();
      }
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  public function recuperarImagenesEcommerce($ejecutar = false) {
    $db = $this->getConexion();
    $resumen = array("candidatas" => 0, "insertadas" => 0, "modo" => $ejecutar ? "ejecucion" : "simulacion");
    try {
      if (!$this->tablaExisteCatalogo($db, "ecom_productos_imagenes")) {
        return $this->respuesta(true, "warning", "No existe ecom_productos_imagenes en esta base", $resumen);
      }
      $candidatas = $this->candidatasImagenesEcommerce($db, 0);
      $resumen["candidatas"] = count($candidatas);
      if (!$ejecutar) {
        return $this->respuesta(false, "info", "Simulacion de recuperacion de imagenes ecommerce", $resumen);
      }

      $db->beginTransaction();
      $stmtPortada = $db->prepare("UPDATE erp_catalogo_imagenes
        SET tipo_imagen='galeria', fecha_actualizacion=CURRENT_TIMESTAMP
        WHERE id_producto_erp=:producto AND tipo_imagen='portada' AND estatus='activo'");
      $stmtInsert = $db->prepare("INSERT INTO erp_catalogo_imagenes
        (id_producto_erp, id_sku, tipo_imagen, url_imagen, texto_alternativo, orden, fuente, id_externo, estatus)
        VALUES (:producto, NULL, :tipo, :url, :alt, :orden, 'ecommerce', :externo, 'activo')");
      foreach ($candidatas as $fila) {
        if ($fila["tipo_imagen_erp"] === "portada") {
          $stmtPortada->execute(array(":producto" => intval($fila["id_producto_erp"])));
        }
        $stmtInsert->execute(array(
          ":producto" => intval($fila["id_producto_erp"]),
          ":tipo" => $fila["tipo_imagen_erp"],
          ":url" => substr($fila["url_imagen"], 0, 700),
          ":alt" => $fila["nombre_producto"] ?: null,
          ":orden" => intval($fila["orden_sugerido"]),
          ":externo" => (string) $fila["id_producto_imagen"]
        ));
        $resumen["insertadas"]++;
      }
      $db->commit();
      return $this->respuesta(false, "success", "Imagenes ecommerce recuperadas", $resumen);
    } catch (Exception $e) {
      if ($db->inTransaction()) {
        $db->rollBack();
      }
      return $this->respuesta(true, "danger", $e->getMessage(), $resumen);
    }
  }

  private function candidatasImagenesEcommerce($db, $limite = 0, $idProducto = 0) {
    $idProducto = intval($idProducto);
    $sql = "SELECT e.id_producto_imagen, e.id_producto, v.id_producto_erp, p.nombre nombre_producto,
        e.tipo_imagen tipo_imagen_ecom,
        CASE WHEN e.tipo_imagen='portada' THEN 'portada' ELSE 'galeria' END tipo_imagen_erp,
        e.url_imagen,
        ROW_NUMBER() OVER (PARTITION BY v.id_producto_erp ORDER BY e.tipo_imagen='portada' DESC, e.id_producto_imagen) - 1 AS orden_sugerido
      FROM ecom_productos_imagenes e
      INNER JOIN erp_catalogo_canales_vinculos v
        ON v.canal='ecommerce' AND CAST(v.id_externo AS UNSIGNED)=e.id_producto AND v.id_producto_erp IS NOT NULL
      INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=v.id_producto_erp AND p.estatus<>'fusionado'
      LEFT JOIN erp_catalogo_imagenes i
        ON i.fuente='ecommerce' AND i.id_externo=CAST(e.id_producto_imagen AS CHAR)
      WHERE TRIM(COALESCE(e.url_imagen,''))<>''
        AND e.url_imagen LIKE 'media/%'
        AND i.id_imagen_erp IS NULL";
    $params = array();
    if ($idProducto > 0) {
      $sql .= " AND v.id_producto_erp = :producto";
      $params[":producto"] = $idProducto;
    }
    $sql .= " ORDER BY e.tipo_imagen='portada' DESC, e.id_producto_imagen";
    if (intval($limite) > 0) {
      $sql .= " LIMIT " . intval($limite);
    }
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  private function consultarVariantesProducto($db, $idProducto, $skus) {
    $stmt = $db->prepare("SELECT DISTINCT a.id_atributo_erp, a.codigo, a.nombre, a.tipo_dato, a.unidad, a.configuracion_json, a.es_variante
      FROM erp_catalogo_atributos a
      INNER JOIN erp_catalogo_sku_atributos sa ON sa.id_atributo_erp=a.id_atributo_erp
      INNER JOIN erp_catalogo_skus s ON s.id_sku=sa.id_sku
      WHERE s.id_producto_erp=:producto AND a.es_variante=1 AND a.estatus='activo'
      ORDER BY a.nombre");
    $stmt->execute(array(":producto" => $idProducto));
    $atributos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $valores = array();
    if (!empty($skus)) {
      $ids = implode(",", array_map(function ($sku) { return intval($sku["id_sku"]); }, $skus));
      $stmt = $db->query("SELECT sa.id_sku, sa.id_atributo_erp, sa.valor FROM erp_catalogo_sku_atributos sa
        INNER JOIN erp_catalogo_atributos a ON a.id_atributo_erp=sa.id_atributo_erp AND a.es_variante=1
        WHERE sa.id_sku IN (" . $ids . ")");
      foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
        $valores[$fila["id_sku"]][$fila["id_atributo_erp"]] = $fila["valor"];
      }
    }
    return array(
      "atributos" => $atributos,
      "valores" => $valores,
      "duplicadas" => $this->combinacionesVariantesDuplicadas($db, $idProducto)
    );
  }

  private function consultarImagenesProducto($db, $idProducto) {
    $stmt = $db->prepare("SELECT i.id_imagen_erp, i.id_producto_erp, i.id_sku, i.tipo_imagen, i.url_imagen,
      i.texto_alternativo, i.orden, i.fuente, i.id_externo, i.estatus, s.sku
      FROM erp_catalogo_imagenes i
      LEFT JOIN erp_catalogo_skus s ON s.id_sku=i.id_sku
      WHERE i.id_producto_erp=:producto
      ORDER BY FIELD(i.estatus, 'activo', 'inactivo'), FIELD(i.tipo_imagen, 'portada', 'galeria', 'detalle', 'empaque', 'referencia'), i.orden, i.id_imagen_erp");
    $stmt->execute(array(":producto" => intval($idProducto)));
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  private function combinacionesVariantesDuplicadas($db, $idProducto) {
    $stmt = $db->prepare("SELECT s.id_sku, s.sku, GROUP_CONCAT(CONCAT(sa.id_atributo_erp, '=', LOWER(TRIM(sa.valor))) ORDER BY sa.id_atributo_erp SEPARATOR '|') firma
      FROM erp_catalogo_skus s
      LEFT JOIN erp_catalogo_sku_atributos sa ON sa.id_sku=s.id_sku
        AND sa.id_atributo_erp IN (SELECT id_atributo_erp FROM erp_catalogo_atributos WHERE es_variante=1 AND estatus='activo')
      WHERE s.id_producto_erp=:producto
      GROUP BY s.id_sku, s.sku");
    $stmt->execute(array(":producto" => $idProducto));
    $firmas = array();
    $duplicadas = array();
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
      $firma = trim((string) $fila["firma"]);
      if ($firma === "") {
        continue;
      }
      if (isset($firmas[$firma])) {
        $duplicadas[$firmas[$firma]] = true;
        $duplicadas[$fila["sku"]] = true;
      } else {
        $firmas[$firma] = $fila["sku"];
      }
    }
    return array_keys($duplicadas);
  }

  private function normalizarValorAtributo($valor, $atributo) {
    $valor = trim((string) $valor);
    if ($valor === "" || !$atributo) {
      return $valor;
    }
    $tipo = isset($atributo["tipo_dato"]) ? $atributo["tipo_dato"] : "texto";
    if ($tipo === "color") {
      if (preg_match('/^#?([0-9a-f]{3})$/i', $valor, $m)) {
        return "#" . strtoupper($m[1][0] . $m[1][0] . $m[1][1] . $m[1][1] . $m[1][2] . $m[1][2]);
      }
      if (preg_match('/^#?([0-9a-f]{6})$/i', $valor, $m)) {
        return "#" . strtoupper($m[1]);
      }
      if (preg_match('/^rgb\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*\)$/i', $valor, $m)
        && intval($m[1]) <= 255 && intval($m[2]) <= 255 && intval($m[3]) <= 255) {
        return sprintf("#%02X%02X%02X", intval($m[1]), intval($m[2]), intval($m[3]));
      }
      throw new Exception("El color debe estar en formato hexadecimal o RGB");
    }
    if ($tipo === "numero" && !is_numeric($valor)) {
      throw new Exception("Los valores del atributo numérico deben ser números");
    }
    if ($tipo === "booleano") {
      return in_array(strtolower($valor), array("1", "si", "sí", "true"), true) ? "1" : "0";
    }
    if ($tipo === "fecha") {
      $fecha = DateTime::createFromFormat("Y-m-d", $valor);
      if (!$fecha || $fecha->format("Y-m-d") !== $valor) {
        throw new Exception("Los valores del atributo fecha deben usar el formato AAAA-MM-DD");
      }
    }
    if ($tipo === "lista") {
      $configuracion = json_decode(isset($atributo["configuracion_json"]) ? $atributo["configuracion_json"] : "", true);
      $opciones = isset($configuracion["opciones"]) && is_array($configuracion["opciones"]) ? $configuracion["opciones"] : array();
      if (!in_array($valor, $opciones, true)) {
        throw new Exception("Selecciona una opción válida para el atributo");
      }
    }
    return substr($valor, 0, 500);
  }

  /**
   * IA: Codex GPT-5 | Fecha: 2026-06-25
   * Proposito: guarda relacion SKU-proveedor permitiendo varios proveedores y un solo preferido por SKU.
   * Impacto: Catalogo ERP; Compras/Recepcion pueden identificar proveedor principal sin perder alternativas.
   */
  public function guardarSkuProveedor($datos) {
    $idRelacion = intval(isset($datos["id_sku_proveedor"]) ? $datos["id_sku_proveedor"] : 0);
    $idSku = intval(isset($datos["id_sku"]) ? $datos["id_sku"] : 0);
    $idProveedor = intval(isset($datos["id_proveedor"]) ? $datos["id_proveedor"] : 0);
    $idUnidad = intval(isset($datos["id_unidad_compra"]) ? $datos["id_unidad_compra"] : 0);
    if ($idSku <= 0 || $idProveedor <= 0 || $idUnidad <= 0) {
      return $this->respuesta(true, "warning", "Selecciona SKU, proveedor y unidad de compra");
    }
    if ($this->decimal($datos, "factor_conversion") <= 0 || $this->decimal($datos, "cantidad_minima") <= 0) {
      return $this->respuesta(true, "warning", "Conversión y cantidad mínima deben ser mayores a cero");
    }
    $preferido = $this->booleano($datos, "es_preferido");
    $estatus = $this->opcion($datos, "estatus", array("activo", "inactivo"), "activo");
    if ($estatus !== "activo") {
      $preferido = 0;
    }
    try {
      $db = $this->getConexion();
      $db->beginTransaction();
      $stmt = $db->prepare("SELECT id_sku FROM erp_catalogo_skus WHERE id_sku=:sku AND estatus<>'fusionado' LIMIT 1");
      $stmt->execute(array(":sku" => $idSku));
      if (!$stmt->fetchColumn()) {
        throw new Exception("Selecciona un SKU ERP valido");
      }
      $stmt = $db->prepare("SELECT id_unidad FROM erp_catalogo_unidades WHERE id_unidad=:unidad AND estatus='activa' LIMIT 1");
      $stmt->execute(array(":unidad" => $idUnidad));
      if (!$stmt->fetchColumn()) {
        throw new Exception("Selecciona una unidad de compra activa");
      }
      if ($preferido === 1) {
        $db->prepare("UPDATE erp_catalogo_sku_proveedores SET es_preferido=0, fecha_actualizacion=CURRENT_TIMESTAMP WHERE id_sku=:sku AND id_sku_proveedor<>:relacion")
          ->execute(array(":sku" => $idSku, ":relacion" => $idRelacion));
      }
      if ($idRelacion > 0) {
        $stmt = $db->prepare("SELECT id_sku_proveedor FROM erp_catalogo_sku_proveedores WHERE id_sku_proveedor=:relacion LIMIT 1");
        $stmt->execute(array(":relacion" => $idRelacion));
        if (!$stmt->fetchColumn()) {
          throw new Exception("La relacion SKU-proveedor ya no existe");
        }
        $stmt = $db->prepare("SELECT id_sku_proveedor FROM erp_catalogo_sku_proveedores
          WHERE id_sku=:sku AND id_proveedor=:proveedor AND id_sku_proveedor<>:relacion LIMIT 1");
        $stmt->execute(array(":sku" => $idSku, ":proveedor" => $idProveedor, ":relacion" => $idRelacion));
        if ($stmt->fetchColumn()) {
          throw new Exception("Este SKU ya tiene una relacion con ese proveedor");
        }
        $stmt = $db->prepare("UPDATE erp_catalogo_sku_proveedores
          SET id_sku=:sku, id_proveedor=:proveedor, sku_proveedor=:sku_proveedor, id_unidad_compra=:unidad,
          factor_conversion=:factor, costo_ultimo=:costo, cantidad_minima=:minima, dias_entrega=:dias,
          es_preferido=:preferido, estatus=:estatus, fecha_actualizacion=CURRENT_TIMESTAMP
          WHERE id_sku_proveedor=:relacion");
        $stmt->execute(array(
          ":sku" => $idSku, ":proveedor" => $idProveedor, ":sku_proveedor" => $this->texto($datos, "sku_proveedor") ?: null,
          ":unidad" => $idUnidad, ":factor" => $this->decimal($datos, "factor_conversion"), ":costo" => $this->decimal($datos, "costo_ultimo"),
          ":minima" => $this->decimal($datos, "cantidad_minima"), ":dias" => max(0, intval(isset($datos["dias_entrega"]) ? $datos["dias_entrega"] : 0)),
          ":preferido" => $preferido, ":estatus" => $estatus, ":relacion" => $idRelacion
        ));
        $db->commit();
        return $this->respuesta(false, "success", "Relacion SKU-proveedor actualizada", array("id_sku_proveedor" => $idRelacion, "id_sku" => $idSku, "id_proveedor" => $idProveedor));
      }
      $stmt = $db->prepare("INSERT INTO erp_catalogo_sku_proveedores
        (id_sku, id_proveedor, sku_proveedor, id_unidad_compra, factor_conversion, costo_ultimo, cantidad_minima, dias_entrega, es_preferido, estatus)
        VALUES (:sku, :proveedor, :sku_proveedor, :unidad, :factor, :costo, :minima, :dias, :preferido, :estatus)
        ON DUPLICATE KEY UPDATE sku_proveedor=VALUES(sku_proveedor), id_unidad_compra=VALUES(id_unidad_compra),
        factor_conversion=VALUES(factor_conversion), costo_ultimo=VALUES(costo_ultimo), cantidad_minima=VALUES(cantidad_minima),
        dias_entrega=VALUES(dias_entrega), es_preferido=VALUES(es_preferido), estatus=VALUES(estatus), fecha_actualizacion=CURRENT_TIMESTAMP");
      $stmt->execute(array(
        ":sku" => $idSku, ":proveedor" => $idProveedor, ":sku_proveedor" => $this->texto($datos, "sku_proveedor") ?: null,
        ":unidad" => $idUnidad, ":factor" => $this->decimal($datos, "factor_conversion"), ":costo" => $this->decimal($datos, "costo_ultimo"),
        ":minima" => $this->decimal($datos, "cantidad_minima"), ":dias" => max(0, intval(isset($datos["dias_entrega"]) ? $datos["dias_entrega"] : 0)),
        ":preferido" => $preferido, ":estatus" => $estatus
      ));
      $db->commit();
      return $this->respuesta(false, "success", "Relación SKU-proveedor guardada", array("id_sku" => $idSku, "id_proveedor" => $idProveedor));
    } catch (Exception $e) {
      if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
      }
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  /**
   * IA: Codex GPT-5 | Fecha: 2026-06-26
   * Proposito: crear relaciones proveedor-SKU por lote para productos seleccionados, solo en SKUs sin proveedor activo.
   * Impacto: Catalogo ERP; reduce captura repetitiva sin sobrescribir proveedores existentes ni decidir costos reales.
   * Contrato: `ids_productos` JSON, proveedor/unidad/factor/minima obligatorios; `simular=1` no escribe.
   */
  public function asignarProveedorMasivoSkusSinProveedor($datos) {
    $ids = json_decode(isset($datos["ids_productos"]) ? $datos["ids_productos"] : "[]", true);
    if (!is_array($ids) || empty($ids)) {
      return $this->respuesta(true, "warning", "Selecciona al menos un producto");
    }
    $ids = array_values(array_unique(array_filter(array_map("intval", $ids), function ($id) {
      return $id > 0;
    })));
    if (empty($ids)) {
      return $this->respuesta(true, "warning", "Selecciona productos validos");
    }
    if (count($ids) > 250) {
      return $this->respuesta(true, "warning", "Aplica como maximo 250 productos por operacion");
    }

    $idProveedor = intval(isset($datos["id_proveedor"]) ? $datos["id_proveedor"] : 0);
    $idUnidad = intval(isset($datos["id_unidad_compra"]) ? $datos["id_unidad_compra"] : 0);
    $factor = $this->decimal($datos, "factor_conversion");
    $minima = $this->decimal($datos, "cantidad_minima");
    $dias = max(0, intval(isset($datos["dias_entrega"]) ? $datos["dias_entrega"] : 0));
    $simular = $this->booleano($datos, "simular");
    if ($idProveedor <= 0 || $idUnidad <= 0) {
      return $this->respuesta(true, "warning", "Selecciona proveedor y unidad de compra");
    }
    if ($factor <= 0 || $minima <= 0) {
      return $this->respuesta(true, "warning", "Factor de conversion y compra minima deben ser mayores a cero");
    }

    $db = $this->getConexion();
    try {
      $db->beginTransaction();
      $stmt = $db->prepare("SELECT id_proveedor FROM erp_proveedores WHERE id_proveedor=:proveedor LIMIT 1");
      $stmt->execute(array(":proveedor" => $idProveedor));
      if (!$stmt->fetchColumn()) {
        throw new Exception("Selecciona un proveedor valido");
      }
      $stmt = $db->prepare("SELECT id_unidad FROM erp_catalogo_unidades WHERE id_unidad=:unidad AND estatus='activa' LIMIT 1");
      $stmt->execute(array(":unidad" => $idUnidad));
      if (!$stmt->fetchColumn()) {
        throw new Exception("Selecciona una unidad de compra activa");
      }

      $placeholders = implode(",", array_fill(0, count($ids), "?"));
      $stmt = $db->prepare("SELECT s.id_sku, s.sku, s.id_producto_erp
        FROM erp_catalogo_skus s
        INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp AND p.estatus<>'fusionado'
        WHERE s.id_producto_erp IN ($placeholders)
          AND s.estatus NOT IN ('inactivo','descontinuado','fusionado')
          AND NOT EXISTS (
            SELECT 1 FROM erp_catalogo_sku_proveedores sp
            WHERE sp.id_sku=s.id_sku AND sp.estatus='activo'
          )
        ORDER BY s.id_producto_erp, s.id_sku");
      foreach ($ids as $idx => $idProducto) {
        $stmt->bindValue($idx + 1, $idProducto, PDO::PARAM_INT);
      }
      $stmt->execute();
      $skus = $stmt->fetchAll(PDO::FETCH_ASSOC);
      if (empty($skus)) {
        $db->rollBack();
        return $this->respuesta(true, "info", "Los productos seleccionados no tienen SKUs pendientes de proveedor");
      }
      $productosAfectados = array();
      foreach ($skus as $sku) {
        $productosAfectados[intval($sku["id_producto_erp"])] = true;
      }
      if ($simular === 1) {
        $db->rollBack();
        return $this->respuesta(false, "info", "Simulacion de proveedor masivo consultada", array(
          "productos_seleccionados" => count($ids),
          "productos_afectados" => count($productosAfectados),
          "skus_relacionables" => count($skus),
          "id_proveedor" => $idProveedor,
          "id_unidad_compra" => $idUnidad,
          "factor_conversion" => $factor,
          "cantidad_minima" => $minima
        ));
      }

      $insertar = $db->prepare("INSERT INTO erp_catalogo_sku_proveedores
        (id_sku, id_proveedor, sku_proveedor, id_unidad_compra, factor_conversion, costo_ultimo, cantidad_minima, dias_entrega, es_preferido, estatus)
        VALUES (:sku, :proveedor, :sku_proveedor, :unidad, :factor, 0, :minima, :dias, 1, 'activo')
        ON DUPLICATE KEY UPDATE sku_proveedor=VALUES(sku_proveedor), id_unidad_compra=VALUES(id_unidad_compra),
          factor_conversion=VALUES(factor_conversion), cantidad_minima=VALUES(cantidad_minima),
          dias_entrega=VALUES(dias_entrega), es_preferido=1, estatus='activo', fecha_actualizacion=CURRENT_TIMESTAMP");
      $relaciones = 0;
      foreach ($skus as $sku) {
        $insertar->execute(array(
          ":sku" => intval($sku["id_sku"]),
          ":proveedor" => $idProveedor,
          ":sku_proveedor" => $sku["sku"],
          ":unidad" => $idUnidad,
          ":factor" => $factor,
          ":minima" => $minima,
          ":dias" => $dias
        ));
        $relaciones++;
        $productosAfectados[intval($sku["id_producto_erp"])] = true;
      }
      $db->commit();
      return $this->respuesta(false, "success", "Proveedor asignado a SKUs sin proveedor", array(
        "productos_seleccionados" => count($ids),
        "productos_afectados" => count($productosAfectados),
        "skus_relacionados" => $relaciones,
        "id_proveedor" => $idProveedor,
        "id_unidad_compra" => $idUnidad,
        "factor_conversion" => $factor,
        "cantidad_minima" => $minima
      ));
    } catch (Exception $e) {
      if ($db->inTransaction()) {
        $db->rollBack();
      }
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  private function consultarSkuProveedores($db, $idProducto) {
    $stmt = $db->prepare("SELECT sp.id_sku_proveedor, sp.id_sku, sp.id_proveedor, sp.sku_proveedor,
      sp.id_unidad_compra, sp.factor_conversion, sp.costo_ultimo, sp.cantidad_minima, sp.dias_entrega,
      sp.es_preferido, sp.estatus, s.sku, s.nombre AS nombre_sku,
      s.id_unidad_base, ub.codigo AS unidad_base_codigo, ub.nombre AS unidad_base_nombre,
      ub.abreviatura AS unidad_base_abreviatura, ub.tipo_magnitud AS unidad_base_magnitud,
      ub.decimales_permitidos AS unidad_base_decimales,
      p.proveedor, u.nombre AS unidad_compra, u.abreviatura, u.codigo AS unidad_compra_codigo
      FROM erp_catalogo_sku_proveedores sp
      INNER JOIN erp_catalogo_skus s ON s.id_sku=sp.id_sku
      INNER JOIN erp_catalogo_unidades ub ON ub.id_unidad=s.id_unidad_base
      INNER JOIN erp_proveedores p ON p.id_proveedor=sp.id_proveedor
      INNER JOIN erp_catalogo_unidades u ON u.id_unidad=sp.id_unidad_compra
      WHERE s.id_producto_erp=:producto ORDER BY s.sku, sp.es_preferido DESC, p.proveedor");
    $stmt->execute(array(":producto" => $idProducto));
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-26
   * Proposito: consulta paquetes configurables del producto sin romper el modal si el esquema aun no fue autorizado.
   * Impacto: Catalogo ERP; prepara la lectura de recetas de paquetes y mantiene el flujo en modo seguro sin migracion aplicada.
   * Contrato: devuelve esquema_disponible=false cuando faltan tablas o ocurre una consulta parcial.
   */
  private function consultarPaquetesProducto($db, $idProducto) {
    $tablas = array(
      "erp_catalogo_sku_paquetes",
      "erp_catalogo_sku_paquete_componentes",
      "erp_catalogo_sku_paquete_grupos",
      "erp_catalogo_sku_paquete_grupo_opciones"
    );
    $faltantes = array();
    foreach ($tablas as $tabla) {
      if (!$this->tablaExisteCatalogo($db, $tabla)) {
        $faltantes[] = $tabla;
      }
    }
    if (!empty($faltantes)) {
      return array(
        "esquema_disponible" => false,
        "mensaje" => "El esquema de paquetes configurables esta pendiente de aplicar.",
        "tablas_faltantes" => $faltantes,
        "paquetes" => array()
      );
    }

    try {
      $stmt = $db->prepare("SELECT p.id_paquete, p.id_sku_paquete, p.tipo_paquete, p.modo_disponibilidad,
        p.permite_configuracion_cliente, p.permite_desarmar, p.requiere_armado_almacen,
        p.observaciones, p.estatus, s.sku, s.nombre AS nombre_sku
        FROM erp_catalogo_sku_paquetes p
        INNER JOIN erp_catalogo_skus s ON s.id_sku=p.id_sku_paquete
        WHERE s.id_producto_erp=:producto
          AND p.estatus IN ('activo','borrador')
        ORDER BY p.id_paquete");
      $stmt->execute(array(":producto" => intval($idProducto)));
      $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);

      $paquetes = array();
      $ids = array();
      foreach ($filas as $fila) {
        $id = intval($fila["id_paquete"]);
        $fila["componentes"] = array();
        $fila["grupos"] = array();
        $paquetes[$id] = $fila;
        $ids[] = $id;
      }

      if (empty($ids)) {
        return array(
          "esquema_disponible" => true,
          "paquetes" => array(),
          "totales" => array("paquetes" => 0, "componentes" => 0, "grupos" => 0, "opciones" => 0)
        );
      }

      $listaIds = implode(",", array_map("intval", $ids));
      $componentes = $db->query("SELECT c.id_componente, c.id_paquete, c.id_sku_componente, c.cantidad,
        c.id_unidad, c.factor_conversion, c.orden, c.estatus, s.sku, s.nombre AS nombre_sku,
        u.abreviatura AS unidad
        FROM erp_catalogo_sku_paquete_componentes c
        INNER JOIN erp_catalogo_skus s ON s.id_sku=c.id_sku_componente
        LEFT JOIN erp_catalogo_unidades u ON u.id_unidad=c.id_unidad
        WHERE c.id_paquete IN (" . $listaIds . ") AND c.estatus='activo'
        ORDER BY c.id_paquete, c.orden, c.id_componente")->fetchAll(PDO::FETCH_ASSOC);
      foreach ($componentes as $componente) {
        $idPaquete = intval($componente["id_paquete"]);
        if (isset($paquetes[$idPaquete])) {
          $paquetes[$idPaquete]["componentes"][] = $componente;
        }
      }

      $grupos = $db->query("SELECT id_grupo, id_paquete, codigo, nombre, descripcion,
        min_selecciones, max_selecciones, modo_cantidad, cantidad_total_grupo,
        obligatorio, orden, estatus
        FROM erp_catalogo_sku_paquete_grupos
        WHERE id_paquete IN (" . $listaIds . ") AND estatus='activo'
        ORDER BY id_paquete, orden, id_grupo")->fetchAll(PDO::FETCH_ASSOC);
      $gruposPorId = array();
      foreach ($grupos as $grupo) {
        $idGrupo = intval($grupo["id_grupo"]);
        $idPaquete = intval($grupo["id_paquete"]);
        $grupo["opciones"] = array();
        $gruposPorId[$idGrupo] = array("id_paquete" => $idPaquete, "grupo" => $grupo);
      }

      if (!empty($gruposPorId)) {
        $listaGrupos = implode(",", array_map("intval", array_keys($gruposPorId)));
        $opciones = $db->query("SELECT o.id_opcion, o.id_grupo, o.id_sku_opcion, o.cantidad_default,
          o.cantidad_minima, o.cantidad_maxima, o.id_unidad, o.factor_conversion,
          o.permite_cantidad_editable, o.orden, o.estatus, s.sku, s.nombre AS nombre_sku,
          u.abreviatura AS unidad
          FROM erp_catalogo_sku_paquete_grupo_opciones o
          INNER JOIN erp_catalogo_skus s ON s.id_sku=o.id_sku_opcion
          LEFT JOIN erp_catalogo_unidades u ON u.id_unidad=o.id_unidad
          WHERE o.id_grupo IN (" . $listaGrupos . ") AND o.estatus='activo'
          ORDER BY o.id_grupo, o.orden, o.id_opcion")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($opciones as $opcion) {
          $idGrupo = intval($opcion["id_grupo"]);
          if (isset($gruposPorId[$idGrupo])) {
            $gruposPorId[$idGrupo]["grupo"]["opciones"][] = $opcion;
          }
        }
      }

      foreach ($gruposPorId as $grupoInfo) {
        $idPaquete = intval($grupoInfo["id_paquete"]);
        if (isset($paquetes[$idPaquete])) {
          $paquetes[$idPaquete]["grupos"][] = $grupoInfo["grupo"];
        }
      }

      return array(
        "esquema_disponible" => true,
        "paquetes" => array_values($paquetes),
        "totales" => array(
          "paquetes" => count($paquetes),
          "componentes" => count($componentes),
          "grupos" => count($grupos),
          "opciones" => isset($opciones) ? count($opciones) : 0
        )
      );
    } catch (Exception $e) {
      return array(
        "esquema_disponible" => false,
        "mensaje" => "No se pudo consultar paquetes configurables.",
        "detalle" => $e->getMessage(),
        "paquetes" => array()
      );
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-26
   * Proposito: guarda el encabezado de un paquete y sus componentes fijos cuando el esquema ya existe.
   * Impacto: Catalogo ERP; habilita paquete simple sin tocar Ventas, Almacen ni Inventario.
   * Contrato: si faltan tablas responde warning y no intenta escribir; crear y editar recetas son acciones separadas para no sobrescribir paquetes por accidente.
   */
  public function guardarPaqueteSimple($datos, $idUsuario = 0) {
    $db = $this->getConexion();
    if (!$this->esquemaPaquetesDisponible($db)) {
      return $this->respuesta(true, "warning", "El esquema de paquetes configurables aun no esta aplicado");
    }

    $idPaquete = intval(isset($datos["id_paquete"]) ? $datos["id_paquete"] : 0);
    $idSkuPaquete = intval(isset($datos["id_sku_paquete"]) ? $datos["id_sku_paquete"] : 0);
    $tipo = $this->opcion($datos, "tipo_paquete", array("simple", "configurable", "virtual", "prearmado", "combo", "comprado_cerrado"), "simple");
    $modo = $this->opcion($datos, "modo_disponibilidad", array("por_componentes", "por_existencia_armada", "mixto"), "por_componentes");
    $estatus = $this->opcion($datos, "estatus", array("activo", "inactivo", "borrador"), "activo");
    try {
      $componentes = $this->normalizarComponentesPaquete($datos);
    } catch (Exception $e) {
      return $this->respuesta(true, "warning", $e->getMessage());
    }

    if ($idSkuPaquete <= 0) {
      return $this->respuesta(true, "warning", "Selecciona el SKU que representa el paquete");
    }
    if ($tipo === "simple" && empty($componentes)) {
      return $this->respuesta(true, "warning", "Un paquete simple necesita al menos un componente fijo");
    }

    try {
      $stmt = $db->prepare("SELECT id_sku, sku, estatus FROM erp_catalogo_skus WHERE id_sku=:sku LIMIT 1");
      $stmt->execute(array(":sku" => $idSkuPaquete));
      $skuPaquete = $stmt->fetch(PDO::FETCH_ASSOC);
      if (!$skuPaquete) {
        return $this->respuesta(true, "warning", "El SKU paquete no existe");
      }
      if (in_array($skuPaquete["estatus"], array("fusionado", "descontinuado", "inactivo"), true)) {
        return $this->respuesta(true, "warning", "El SKU paquete debe estar operativo para configurar receta");
      }
      $stmtReceta = $db->prepare("SELECT id_paquete, estatus FROM erp_catalogo_sku_paquetes WHERE id_sku_paquete=:sku LIMIT 1");
      $stmtReceta->execute(array(":sku" => $idSkuPaquete));
      $recetaExistente = $stmtReceta->fetch(PDO::FETCH_ASSOC);
      if (!$idPaquete && $recetaExistente) {
        return $this->respuesta(true, "warning", "Ese SKU ya tiene receta de paquete. Usa Editar paquete o crea otro SKU paquete para una receta distinta.");
      }
      if ($idPaquete && $recetaExistente && intval($recetaExistente["id_paquete"]) !== $idPaquete) {
        return $this->respuesta(true, "warning", "Ese SKU ya pertenece a otra receta de paquete.");
      }
      foreach ($componentes as $componente) {
        if ($componente["id_sku_componente"] === $idSkuPaquete) {
          return $this->respuesta(true, "warning", "El paquete no puede contenerse a si mismo como componente");
        }
      }
      if (!empty($componentes)) {
        $idsComponentes = array_map("intval", array_column($componentes, "id_sku_componente"));
        $stmtComponentes = $db->query("SELECT id_sku, sku, estatus
          FROM erp_catalogo_skus
          WHERE id_sku IN (" . implode(",", $idsComponentes) . ")");
        $componentesEncontrados = array();
        foreach ($stmtComponentes->fetchAll(PDO::FETCH_ASSOC) as $filaComponente) {
          $componentesEncontrados[intval($filaComponente["id_sku"])] = $filaComponente;
        }
        foreach ($idsComponentes as $idComponente) {
          if (!isset($componentesEncontrados[$idComponente])) {
            return $this->respuesta(true, "warning", "Uno de los componentes seleccionados ya no existe");
          }
          if (in_array($componentesEncontrados[$idComponente]["estatus"], array("fusionado", "descontinuado", "inactivo"), true)) {
            return $this->respuesta(true, "warning", "El componente " . $componentesEncontrados[$idComponente]["sku"] . " no esta operativo");
          }
        }
      }

      $db->beginTransaction();
      if ($idPaquete > 0) {
        $stmt = $db->prepare("UPDATE erp_catalogo_sku_paquetes
          SET id_sku_paquete=:sku, tipo_paquete=:tipo, modo_disponibilidad=:modo,
            permite_configuracion_cliente=:configurable, permite_desarmar=:desarmar,
            requiere_armado_almacen=:armado, observaciones=:observaciones, estatus=:estatus,
            actualizado_por=:usuario, fecha_actualizacion=CURRENT_TIMESTAMP
          WHERE id_paquete=:id");
        $stmt->execute(array(
          ":sku" => $idSkuPaquete,
          ":tipo" => $tipo,
          ":modo" => $modo,
          ":configurable" => $this->booleano($datos, "permite_configuracion_cliente"),
          ":desarmar" => $this->booleano($datos, "permite_desarmar"),
          ":armado" => $this->booleano($datos, "requiere_armado_almacen"),
          ":observaciones" => $this->texto($datos, "observaciones"),
          ":estatus" => $estatus,
          ":usuario" => intval($idUsuario) ?: null,
          ":id" => $idPaquete
        ));
        if ($stmt->rowCount() === 0) {
          $existe = $db->prepare("SELECT id_paquete FROM erp_catalogo_sku_paquetes WHERE id_paquete=:id");
          $existe->execute(array(":id" => $idPaquete));
          if (!$existe->fetchColumn()) {
            throw new Exception("El paquete que intentas editar ya no existe");
          }
        }
      } else {
        $stmt = $db->prepare("INSERT INTO erp_catalogo_sku_paquetes
          (id_sku_paquete, tipo_paquete, modo_disponibilidad, permite_configuracion_cliente,
           permite_desarmar, requiere_armado_almacen, observaciones, estatus, creado_por, actualizado_por)
          VALUES (:sku, :tipo, :modo, :configurable, :desarmar, :armado, :observaciones, :estatus, :usuario, :usuario)");
        $stmt->execute(array(
          ":sku" => $idSkuPaquete,
          ":tipo" => $tipo,
          ":modo" => $modo,
          ":configurable" => $this->booleano($datos, "permite_configuracion_cliente"),
          ":desarmar" => $this->booleano($datos, "permite_desarmar"),
          ":armado" => $this->booleano($datos, "requiere_armado_almacen"),
          ":observaciones" => $this->texto($datos, "observaciones"),
          ":estatus" => $estatus,
          ":usuario" => intval($idUsuario) ?: null
        ));
        $idPaquete = intval($db->lastInsertId());
      }

      $this->guardarComponentesPaquete($db, $idPaquete, $componentes);
      $db->commit();
      return $this->respuesta(false, "success", "Paquete guardado", array(
        "id_paquete" => $idPaquete,
        "id_sku_paquete" => $idSkuPaquete,
        "componentes" => count($componentes)
      ));
    } catch (Exception $e) {
      if ($db->inTransaction()) {
        $db->rollBack();
      }
      return $this->respuesta(true, "danger", $e->getCode() === "23000" ? "Ese SKU ya tiene una receta de paquete" : $e->getMessage());
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-26
   * Proposito: desactiva una receta de paquete sin borrar su configuracion.
   * Impacto: Catalogo ERP; conserva historial y evita que se use una receta obsoleta.
   */
  public function desactivarPaquete($datos) {
    $db = $this->getConexion();
    if (!$this->esquemaPaquetesDisponible($db)) {
      return $this->respuesta(true, "warning", "El esquema de paquetes configurables aun no esta aplicado");
    }
    $idPaquete = intval(isset($datos["id_paquete"]) ? $datos["id_paquete"] : 0);
    if ($idPaquete <= 0) {
      return $this->respuesta(true, "warning", "Selecciona el paquete que vas a desactivar");
    }
    try {
      $stmt = $db->prepare("UPDATE erp_catalogo_sku_paquetes
        SET estatus='inactivo', fecha_actualizacion=CURRENT_TIMESTAMP
        WHERE id_paquete=:id");
      $stmt->execute(array(":id" => $idPaquete));
      if ($stmt->rowCount() === 0) {
        return $this->respuesta(true, "warning", "No se encontro el paquete indicado");
      }
      return $this->respuesta(false, "success", "Paquete desactivado", array("id_paquete" => $idPaquete));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-26
   * Proposito: guarda un grupo de seleccion para paquetes configurables.
   * Impacto: Catalogo ERP; define reglas de eleccion sin afectar ventas ni inventario.
   * Contrato: requiere esquema aplicado y un paquete existente.
   */
  public function guardarPaqueteGrupo($datos, $idUsuario = 0) {
    $db = $this->getConexion();
    if (!$this->esquemaPaquetesDisponible($db)) {
      return $this->respuesta(true, "warning", "El esquema de paquetes configurables aun no esta aplicado");
    }

    $idGrupo = intval(isset($datos["id_grupo"]) ? $datos["id_grupo"] : 0);
    $idPaquete = intval(isset($datos["id_paquete"]) ? $datos["id_paquete"] : 0);
    $codigo = strtoupper(preg_replace('/[^A-Za-z0-9_-]+/', '-', $this->texto($datos, "codigo")));
    $codigo = trim($codigo, "-_");
    $nombre = $this->texto($datos, "nombre");
    $minimo = intval(isset($datos["min_selecciones"]) ? $datos["min_selecciones"] : 1);
    $maximo = intval(isset($datos["max_selecciones"]) ? $datos["max_selecciones"] : 1);
    $modoCantidad = $this->opcion($datos, "modo_cantidad", array("cantidad_fija", "cantidad_editable", "distribuir_total"), "cantidad_fija");
    $cantidadTotal = $this->texto($datos, "cantidad_total_grupo") === "" ? null : $this->decimal($datos, "cantidad_total_grupo");
    $estatus = $this->opcion($datos, "estatus", array("activo", "inactivo", "borrador"), "activo");

    if ($idPaquete <= 0 || $codigo === "" || $nombre === "") {
      return $this->respuesta(true, "warning", "Completa paquete, codigo y nombre del grupo");
    }
    if ($minimo < 0 || $maximo < 1 || $maximo < $minimo) {
      return $this->respuesta(true, "warning", "Minimo y maximo de seleccion no son validos");
    }
    if ($modoCantidad === "distribuir_total" && ($cantidadTotal === null || $cantidadTotal <= 0)) {
      return $this->respuesta(true, "warning", "Un grupo que distribuye total necesita cantidad total mayor a cero");
    }

    try {
      $stmt = $db->prepare("SELECT id_paquete FROM erp_catalogo_sku_paquetes WHERE id_paquete=:paquete LIMIT 1");
      $stmt->execute(array(":paquete" => $idPaquete));
      if (!$stmt->fetchColumn()) {
        return $this->respuesta(true, "warning", "El paquete indicado no existe");
      }

      $params = array(
        ":paquete" => $idPaquete,
        ":codigo" => $codigo,
        ":nombre" => $nombre,
        ":descripcion" => $this->texto($datos, "descripcion"),
        ":minimo" => $minimo,
        ":maximo" => $maximo,
        ":modo" => $modoCantidad,
        ":cantidad" => $cantidadTotal,
        ":obligatorio" => $this->booleano($datos, "obligatorio"),
        ":orden" => intval(isset($datos["orden"]) ? $datos["orden"] : 0),
        ":estatus" => $estatus
      );

      if ($idGrupo > 0) {
        $params[":id"] = $idGrupo;
        $stmt = $db->prepare("UPDATE erp_catalogo_sku_paquete_grupos
          SET id_paquete=:paquete, codigo=:codigo, nombre=:nombre, descripcion=:descripcion,
            min_selecciones=:minimo, max_selecciones=:maximo, modo_cantidad=:modo,
            cantidad_total_grupo=:cantidad, obligatorio=:obligatorio, orden=:orden,
            estatus=:estatus, fecha_actualizacion=CURRENT_TIMESTAMP
          WHERE id_grupo=:id");
        $stmt->execute($params);
        if ($stmt->rowCount() === 0) {
          $existe = $db->prepare("SELECT id_grupo FROM erp_catalogo_sku_paquete_grupos WHERE id_grupo=:id");
          $existe->execute(array(":id" => $idGrupo));
          if (!$existe->fetchColumn()) {
            return $this->respuesta(true, "warning", "El grupo que intentas editar ya no existe");
          }
        }
      } else {
        $stmt = $db->prepare("INSERT INTO erp_catalogo_sku_paquete_grupos
          (id_paquete, codigo, nombre, descripcion, min_selecciones, max_selecciones,
           modo_cantidad, cantidad_total_grupo, obligatorio, orden, estatus)
          VALUES (:paquete, :codigo, :nombre, :descripcion, :minimo, :maximo,
           :modo, :cantidad, :obligatorio, :orden, :estatus)
          ON DUPLICATE KEY UPDATE id_grupo=LAST_INSERT_ID(id_grupo),
            nombre=VALUES(nombre), descripcion=VALUES(descripcion), min_selecciones=VALUES(min_selecciones),
            max_selecciones=VALUES(max_selecciones), modo_cantidad=VALUES(modo_cantidad),
            cantidad_total_grupo=VALUES(cantidad_total_grupo), obligatorio=VALUES(obligatorio),
            orden=VALUES(orden), estatus=VALUES(estatus), fecha_actualizacion=CURRENT_TIMESTAMP");
        $stmt->execute($params);
        $idGrupo = intval($db->lastInsertId());
      }

      $db->prepare("UPDATE erp_catalogo_sku_paquetes
        SET tipo_paquete=IF(tipo_paquete='simple', 'configurable', tipo_paquete),
            permite_configuracion_cliente=1,
            actualizado_por=:usuario,
            fecha_actualizacion=CURRENT_TIMESTAMP
        WHERE id_paquete=:paquete")
        ->execute(array(
          ":usuario" => intval($idUsuario) ?: null,
          ":paquete" => $idPaquete
        ));

      return $this->respuesta(false, "success", "Grupo de paquete guardado", array(
        "id_grupo" => $idGrupo,
        "id_paquete" => $idPaquete,
        "actualizado_por" => intval($idUsuario) ?: null
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getCode() === "23000" ? "Ya existe un grupo con ese codigo en el paquete" : $e->getMessage());
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-26
   * Proposito: desactiva un grupo configurable y sus opciones sin borrar historial.
   * Impacto: Catalogo ERP; evita opciones huerfanas activas en grupos obsoletos.
   */
  public function desactivarPaqueteGrupo($datos) {
    $db = $this->getConexion();
    if (!$this->esquemaPaquetesDisponible($db)) {
      return $this->respuesta(true, "warning", "El esquema de paquetes configurables aun no esta aplicado");
    }
    $idGrupo = intval(isset($datos["id_grupo"]) ? $datos["id_grupo"] : 0);
    if ($idGrupo <= 0) {
      return $this->respuesta(true, "warning", "Selecciona el grupo que vas a desactivar");
    }
    try {
      $db->beginTransaction();
      $stmt = $db->prepare("UPDATE erp_catalogo_sku_paquete_grupos
        SET estatus='inactivo', fecha_actualizacion=CURRENT_TIMESTAMP
        WHERE id_grupo=:id");
      $stmt->execute(array(":id" => $idGrupo));
      if ($stmt->rowCount() === 0) {
        $db->rollBack();
        return $this->respuesta(true, "warning", "No se encontro el grupo indicado");
      }
      $db->prepare("UPDATE erp_catalogo_sku_paquete_grupo_opciones
        SET estatus='inactivo', fecha_actualizacion=CURRENT_TIMESTAMP
        WHERE id_grupo=:id AND estatus='activo'")
        ->execute(array(":id" => $idGrupo));
      $db->commit();
      return $this->respuesta(false, "success", "Grupo desactivado", array("id_grupo" => $idGrupo));
    } catch (Exception $e) {
      if ($db->inTransaction()) {
        $db->rollBack();
      }
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-26
   * Proposito: guarda una opcion SKU dentro de un grupo de paquete configurable.
   * Impacto: Catalogo ERP; define alternativas elegibles sin decidir precio ni consumo de inventario.
   */
  public function guardarPaqueteGrupoOpcion($datos) {
    $db = $this->getConexion();
    if (!$this->esquemaPaquetesDisponible($db)) {
      return $this->respuesta(true, "warning", "El esquema de paquetes configurables aun no esta aplicado");
    }

    $idOpcion = intval(isset($datos["id_opcion"]) ? $datos["id_opcion"] : 0);
    $idGrupo = intval(isset($datos["id_grupo"]) ? $datos["id_grupo"] : 0);
    $idSkuOpcion = intval(isset($datos["id_sku_opcion"]) ? $datos["id_sku_opcion"] : 0);
    $cantidadDefault = $this->decimal($datos, "cantidad_default");
    $cantidadMinima = $this->texto($datos, "cantidad_minima") === "" ? null : $this->decimal($datos, "cantidad_minima");
    $cantidadMaxima = $this->texto($datos, "cantidad_maxima") === "" ? null : $this->decimal($datos, "cantidad_maxima");
    $factor = $this->texto($datos, "factor_conversion") === "" ? 1 : $this->decimal($datos, "factor_conversion");
    $estatus = $this->opcion($datos, "estatus", array("activo", "inactivo", "borrador"), "activo");

    if ($idGrupo <= 0 || $idSkuOpcion <= 0) {
      return $this->respuesta(true, "warning", "Selecciona grupo y SKU opcion");
    }
    if ($cantidadDefault <= 0 || $factor <= 0) {
      return $this->respuesta(true, "warning", "Cantidad default y factor deben ser mayores a cero");
    }
    if (($cantidadMinima !== null && $cantidadMinima <= 0) || ($cantidadMaxima !== null && $cantidadMaxima <= 0)) {
      return $this->respuesta(true, "warning", "Cantidades minima y maxima deben ser mayores a cero");
    }
    if ($cantidadMinima !== null && $cantidadMaxima !== null && $cantidadMaxima < $cantidadMinima) {
      return $this->respuesta(true, "warning", "Cantidad maxima no puede ser menor que minima");
    }
    if ($cantidadMinima !== null && $cantidadDefault < $cantidadMinima) {
      return $this->respuesta(true, "warning", "Cantidad default no puede ser menor que minima");
    }
    if ($cantidadMaxima !== null && $cantidadDefault > $cantidadMaxima) {
      return $this->respuesta(true, "warning", "Cantidad default no puede ser mayor que maxima");
    }

    try {
      $stmt = $db->prepare("SELECT g.id_grupo, p.id_sku_paquete
        FROM erp_catalogo_sku_paquete_grupos g
        INNER JOIN erp_catalogo_sku_paquetes p ON p.id_paquete=g.id_paquete
        WHERE g.id_grupo=:grupo AND g.estatus<>'inactivo'
        LIMIT 1");
      $stmt->execute(array(":grupo" => $idGrupo));
      $grupo = $stmt->fetch(PDO::FETCH_ASSOC);
      if (!$grupo) {
        return $this->respuesta(true, "warning", "El grupo indicado no existe o esta inactivo");
      }
      if (intval($grupo["id_sku_paquete"]) === $idSkuOpcion) {
        return $this->respuesta(true, "warning", "La opcion no puede ser el mismo SKU paquete");
      }

      $stmt = $db->prepare("SELECT id_sku, sku, estatus FROM erp_catalogo_skus WHERE id_sku=:sku LIMIT 1");
      $stmt->execute(array(":sku" => $idSkuOpcion));
      $sku = $stmt->fetch(PDO::FETCH_ASSOC);
      if (!$sku) {
        return $this->respuesta(true, "warning", "El SKU opcion no existe");
      }
      if (in_array($sku["estatus"], array("fusionado", "descontinuado", "inactivo"), true)) {
        return $this->respuesta(true, "warning", "El SKU opcion " . $sku["sku"] . " no esta operativo");
      }
      $stmt = $db->prepare("SELECT id_opcion FROM erp_catalogo_sku_paquete_grupo_opciones
        WHERE id_grupo=:grupo AND id_sku_opcion=:sku AND estatus='activo'
          AND (:id_actual=0 OR id_opcion<>:id_comparar)
        LIMIT 1");
      $stmt->execute(array(":grupo" => $idGrupo, ":sku" => $idSkuOpcion, ":id_actual" => $idOpcion, ":id_comparar" => $idOpcion));
      if ($stmt->fetchColumn()) {
        return $this->respuesta(true, "warning", "Ese SKU ya esta como opcion activa del grupo");
      }

      $params = array(
        ":grupo" => $idGrupo,
        ":sku" => $idSkuOpcion,
        ":default" => $cantidadDefault,
        ":minima" => $cantidadMinima,
        ":maxima" => $cantidadMaxima,
        ":unidad" => intval(isset($datos["id_unidad"]) ? $datos["id_unidad"] : 0) ?: null,
        ":factor" => $factor,
        ":editable" => $this->booleano($datos, "permite_cantidad_editable"),
        ":orden" => intval(isset($datos["orden"]) ? $datos["orden"] : 0),
        ":estatus" => $estatus
      );

      if ($idOpcion > 0) {
        $params[":id"] = $idOpcion;
        $stmt = $db->prepare("UPDATE erp_catalogo_sku_paquete_grupo_opciones
          SET id_grupo=:grupo, id_sku_opcion=:sku, cantidad_default=:default,
            cantidad_minima=:minima, cantidad_maxima=:maxima, id_unidad=:unidad,
            factor_conversion=:factor, permite_cantidad_editable=:editable,
            orden=:orden, estatus=:estatus, fecha_actualizacion=CURRENT_TIMESTAMP
          WHERE id_opcion=:id");
        $stmt->execute($params);
      } else {
        $stmt = $db->prepare("INSERT INTO erp_catalogo_sku_paquete_grupo_opciones
          (id_grupo, id_sku_opcion, cantidad_default, cantidad_minima, cantidad_maxima,
           id_unidad, factor_conversion, permite_cantidad_editable, orden, estatus)
          VALUES (:grupo, :sku, :default, :minima, :maxima, :unidad, :factor, :editable, :orden, :estatus)");
        $stmt->execute($params);
        $idOpcion = intval($db->lastInsertId());
      }

      return $this->respuesta(false, "success", "Opcion de paquete guardada", array(
        "id_opcion" => $idOpcion,
        "id_grupo" => $idGrupo
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-26
   * Proposito: desactiva una opcion de grupo configurable sin borrar configuracion.
   * Impacto: Catalogo ERP; conserva trazabilidad y evita opciones no vigentes.
   */
  public function desactivarPaqueteGrupoOpcion($datos) {
    $db = $this->getConexion();
    if (!$this->esquemaPaquetesDisponible($db)) {
      return $this->respuesta(true, "warning", "El esquema de paquetes configurables aun no esta aplicado");
    }
    $idOpcion = intval(isset($datos["id_opcion"]) ? $datos["id_opcion"] : 0);
    if ($idOpcion <= 0) {
      return $this->respuesta(true, "warning", "Selecciona la opcion que vas a desactivar");
    }
    try {
      $stmt = $db->prepare("UPDATE erp_catalogo_sku_paquete_grupo_opciones
        SET estatus='inactivo', fecha_actualizacion=CURRENT_TIMESTAMP
        WHERE id_opcion=:id");
      $stmt->execute(array(":id" => $idOpcion));
      if ($stmt->rowCount() === 0) {
        return $this->respuesta(true, "warning", "No se encontro la opcion indicada");
      }
      return $this->respuesta(false, "success", "Opcion desactivada", array("id_opcion" => $idOpcion));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-26
   * Proposito: normaliza renglones de componentes enviados por UI para receta de paquete.
   * Impacto: Catalogo ERP; evita cantidades/factores invalidos antes de persistir.
   */
  private function normalizarComponentesPaquete($datos) {
    $skus = isset($datos["componente_sku"]) && is_array($datos["componente_sku"]) ? $datos["componente_sku"] : array();
    $cantidades = isset($datos["componente_cantidad"]) && is_array($datos["componente_cantidad"]) ? $datos["componente_cantidad"] : array();
    $unidades = isset($datos["componente_unidad"]) && is_array($datos["componente_unidad"]) ? $datos["componente_unidad"] : array();
    $factores = isset($datos["componente_factor"]) && is_array($datos["componente_factor"]) ? $datos["componente_factor"] : array();
    $componentes = array();
    $vistos = array();
    foreach ($skus as $indice => $idSku) {
      $idSku = intval($idSku);
      if ($idSku <= 0) {
        continue;
      }
      if (isset($vistos[$idSku])) {
        throw new Exception("No repitas el mismo SKU como componente fijo; ajusta su cantidad");
      }
      $cantidad = isset($cantidades[$indice]) && is_numeric($cantidades[$indice]) ? floatval($cantidades[$indice]) : 0;
      $factor = isset($factores[$indice]) && is_numeric($factores[$indice]) ? floatval($factores[$indice]) : 1;
      if ($cantidad <= 0) {
        throw new Exception("Cada componente del paquete necesita cantidad mayor a cero");
      }
      if ($factor <= 0) {
        throw new Exception("Cada componente del paquete necesita factor mayor a cero");
      }
      $componentes[] = array(
        "id_sku_componente" => $idSku,
        "cantidad" => $cantidad,
        "id_unidad" => isset($unidades[$indice]) ? intval($unidades[$indice]) : null,
        "factor_conversion" => $factor,
        "orden" => count($componentes) + 1
      );
      $vistos[$idSku] = true;
    }
    return $componentes;
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-26
   * Proposito: reemplaza componentes activos de una receta conservando los anteriores como inactivos.
   * Impacto: Catalogo ERP; permite editar receta simple sin borrar rastros de configuraciones previas.
   */
  private function guardarComponentesPaquete($db, $idPaquete, $componentes) {
    $db->prepare("UPDATE erp_catalogo_sku_paquete_componentes
      SET estatus='inactivo', fecha_actualizacion=CURRENT_TIMESTAMP
      WHERE id_paquete=:paquete AND estatus='activo'")
      ->execute(array(":paquete" => intval($idPaquete)));
    if (empty($componentes)) {
      return;
    }
    $stmt = $db->prepare("INSERT INTO erp_catalogo_sku_paquete_componentes
      (id_paquete, id_sku_componente, cantidad, id_unidad, factor_conversion, orden, estatus)
      VALUES (:paquete, :sku, :cantidad, :unidad, :factor, :orden, 'activo')");
    foreach ($componentes as $componente) {
      $stmt->execute(array(
        ":paquete" => intval($idPaquete),
        ":sku" => intval($componente["id_sku_componente"]),
        ":cantidad" => $componente["cantidad"],
        ":unidad" => $componente["id_unidad"] ?: null,
        ":factor" => $componente["factor_conversion"],
        ":orden" => intval($componente["orden"])
      ));
    }
  }

  /**
   * IA: Codex GPT-5 | Fecha: 2026-06-25
   * Proposito: marca proveedor preferido automaticamente solo cuando el SKU tiene un unico proveedor activo.
   * Impacto: Catalogo ERP; sanea casos no ambiguos sin tomar decisiones comerciales por SKUs con varios proveedores.
   */
  public function marcarProveedorUnicoPreferido($idProducto) {
    $idProducto = intval($idProducto);
    if ($idProducto <= 0) {
      return $this->respuesta(true, "warning", "Falta el producto ERP");
    }

    $db = $this->getConexion();
    try {
      $db->beginTransaction();
      $stmt = $db->prepare("SELECT s.id_sku, s.sku,
        COUNT(sp.id_sku_proveedor) proveedores_activos,
        SUM(CASE WHEN sp.es_preferido=1 THEN 1 ELSE 0 END) preferidos,
        MIN(sp.id_sku_proveedor) id_sku_proveedor
        FROM erp_catalogo_skus s
        INNER JOIN erp_catalogo_sku_proveedores sp ON sp.id_sku=s.id_sku AND sp.estatus='activo'
        WHERE s.id_producto_erp=:producto AND s.estatus NOT IN ('inactivo','descontinuado','fusionado')
        GROUP BY s.id_sku, s.sku
        HAVING proveedores_activos=1 AND preferidos=0");
      $stmt->execute(array(":producto" => $idProducto));
      $candidatos = $stmt->fetchAll(PDO::FETCH_ASSOC);
      if (empty($candidatos)) {
        $db->rollBack();
        return $this->respuesta(true, "info", "No hay SKUs con un unico proveedor activo pendiente de principal");
      }

      $update = $db->prepare("UPDATE erp_catalogo_sku_proveedores
        SET es_preferido=1, fecha_actualizacion=CURRENT_TIMESTAMP
        WHERE id_sku_proveedor=:relacion");
      $actualizados = 0;
      foreach ($candidatos as $candidato) {
        $update->execute(array(":relacion" => intval($candidato["id_sku_proveedor"])));
        $actualizados += $update->rowCount();
      }
      $db->commit();
      return $this->respuesta(false, "success", "Proveedor principal asignado en SKUs no ambiguos", array(
        "skus_actualizados" => $actualizados,
        "candidatos" => count($candidatos)
      ));
    } catch (Exception $e) {
      if ($db->inTransaction()) {
        $db->rollBack();
      }
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  public function guardarSkuPresentacion($datos) {
    $idRegla = intval(isset($datos["id_sku_presentacion_regla"]) ? $datos["id_sku_presentacion_regla"] : 0);
    $idBase = intval(isset($datos["id_sku_base"]) ? $datos["id_sku_base"] : 0);
    $idPresentacion = intval(isset($datos["id_sku_presentacion"]) ? $datos["id_sku_presentacion"] : 0);
    $factor = $this->decimal($datos, "factor_salida_base");
    $modo = $this->opcion($datos, "modo_disponibilidad", array("preparada", "bajo_demanda", "mixta"), "preparada");
    $consume = $this->opcion($datos, "consume_stock_base_en", array("preparacion", "venta"), "preparacion");
    $capacidadTexto = $this->texto($datos, "capacidad_diaria");
    $capacidad = $capacidadTexto === "" ? null : floatval($capacidadTexto);
    $merma = $this->decimal($datos, "merma_porcentaje");

    if ($idBase <= 0 || $idPresentacion <= 0) {
      return $this->respuesta(true, "warning", "Selecciona SKU base y SKU presentacion");
    }
    if ($idBase === $idPresentacion) {
      return $this->respuesta(true, "warning", "El SKU base y la presentacion no pueden ser el mismo");
    }
    if ($factor <= 0) {
      return $this->respuesta(true, "warning", "El factor de salida base debe ser mayor a cero");
    }
    if ($capacidad !== null && $capacidad <= 0) {
      return $this->respuesta(true, "warning", "La capacidad diaria debe ser mayor a cero o quedar vacia");
    }
    if ($merma < 0 || $merma >= 100) {
      return $this->respuesta(true, "warning", "La merma debe estar entre 0 y menor a 100");
    }
    if ($modo === "preparada" && $consume !== "preparacion") {
      return $this->respuesta(true, "warning", "Una presentacion preparada debe consumir stock base al prepararse");
    }
    if ($modo === "bajo_demanda" && $consume !== "venta") {
      return $this->respuesta(true, "warning", "Una presentacion bajo demanda debe consumir stock base al venderse");
    }

    $db = $this->getConexion();
    try {
      $stmt = $db->prepare("SELECT s.id_sku, s.id_producto_erp, s.sku, s.nombre, s.tipo_inventario, s.estatus
        FROM erp_catalogo_skus s
        WHERE s.id_sku IN (:base, :presentacion)");
      $stmt->execute(array(":base" => $idBase, ":presentacion" => $idPresentacion));
      $skus = array();
      foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $sku) {
        $skus[intval($sku["id_sku"])] = $sku;
      }
      if (!isset($skus[$idBase]) || !isset($skus[$idPresentacion])) {
        return $this->respuesta(true, "warning", "Selecciona SKU existentes para base y presentacion");
      }
      if ($skus[$idBase]["estatus"] === "fusionado" || $skus[$idPresentacion]["estatus"] === "fusionado") {
        return $this->respuesta(true, "warning", "No puedes configurar presentaciones con SKU fusionados");
      }
      if (intval($skus[$idBase]["id_producto_erp"]) !== intval($skus[$idPresentacion]["id_producto_erp"])) {
        return $this->respuesta(true, "warning", "Por ahora la presentacion debe pertenecer al mismo producto maestro que el SKU base");
      }
      if (!$this->controlaInventario($skus[$idBase]["tipo_inventario"])) {
        return $this->respuesta(true, "warning", "El SKU base debe controlar inventario para alimentar presentaciones");
      }

      $params = array(
        ":base" => $idBase,
        ":presentacion" => $idPresentacion,
        ":factor" => $factor,
        ":modo" => $modo,
        ":consume" => $consume,
        ":empaque" => $this->booleano($datos, "requiere_empaque"),
        ":capacidad" => $capacidad,
        ":merma" => $merma,
        ":estatus" => $this->opcion($datos, "estatus", array("activa", "inactiva"), "activa")
      );

      $db->beginTransaction();
      if ($idRegla > 0) {
        $stmt = $db->prepare("UPDATE erp_catalogo_sku_presentaciones
          SET id_sku_base=:base, id_sku_presentacion=:presentacion, factor_salida_base=:factor,
            modo_disponibilidad=:modo, consume_stock_base_en=:consume, requiere_empaque=:empaque,
            capacidad_diaria=:capacidad, merma_porcentaje=:merma, estatus=:estatus,
            fecha_actualizacion=CURRENT_TIMESTAMP
          WHERE id_sku_presentacion_regla=:id");
        $params[":id"] = $idRegla;
        $stmt->execute($params);
        if ($stmt->rowCount() === 0) {
          $stmtExiste = $db->prepare("SELECT id_sku_presentacion_regla FROM erp_catalogo_sku_presentaciones WHERE id_sku_presentacion_regla=:id");
          $stmtExiste->execute(array(":id" => $idRegla));
          if (!$stmtExiste->fetchColumn()) {
            throw new Exception("La presentacion que intentas editar ya no existe");
          }
        }
      } else {
        $stmt = $db->prepare("INSERT INTO erp_catalogo_sku_presentaciones
          (id_sku_base, id_sku_presentacion, factor_salida_base, modo_disponibilidad, consume_stock_base_en, requiere_empaque, capacidad_diaria, merma_porcentaje, estatus)
          VALUES (:base, :presentacion, :factor, :modo, :consume, :empaque, :capacidad, :merma, :estatus)
          ON DUPLICATE KEY UPDATE id_sku_base=VALUES(id_sku_base), factor_salida_base=VALUES(factor_salida_base),
          modo_disponibilidad=VALUES(modo_disponibilidad), consume_stock_base_en=VALUES(consume_stock_base_en),
          requiere_empaque=VALUES(requiere_empaque), capacidad_diaria=VALUES(capacidad_diaria),
          merma_porcentaje=VALUES(merma_porcentaje), estatus=VALUES(estatus), fecha_actualizacion=CURRENT_TIMESTAMP");
        $stmt->execute($params);
      }
      $db->commit();
      return $this->respuesta(false, "success", "Presentacion de venta guardada", array(
        "id_sku_presentacion_regla" => $idRegla,
        "id_sku_base" => $idBase,
        "id_sku_presentacion" => $idPresentacion
      ));
    } catch (Exception $e) {
      if ($db->inTransaction()) {
        $db->rollBack();
      }
      return $this->respuesta(true, "danger", $e->getCode() === "23000" ? "Ese SKU presentacion ya tiene una regla configurada" : $e->getMessage());
    }
  }

  public function desactivarSkuPresentacion($datos) {
    $id = intval(isset($datos["id_sku_presentacion_regla"]) ? $datos["id_sku_presentacion_regla"] : 0);
    if ($id <= 0) {
      return $this->respuesta(true, "warning", "Selecciona la presentacion que vas a desactivar");
    }
    try {
      $db = $this->getConexion();
      $stmt = $db->prepare("UPDATE erp_catalogo_sku_presentaciones
        SET estatus='inactiva', fecha_actualizacion=CURRENT_TIMESTAMP
        WHERE id_sku_presentacion_regla=:id");
      $stmt->execute(array(":id" => $id));
      if ($stmt->rowCount() === 0) {
        return $this->respuesta(true, "warning", "No se encontro la presentacion indicada");
      }
      return $this->respuesta(false, "success", "Presentacion desactivada", array("id_sku_presentacion_regla" => $id));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  private function consultarSkuPresentaciones($db, $idProducto) {
    $stmt = $db->prepare("SELECT pr.id_sku_presentacion_regla, pr.id_sku_base, pr.id_sku_presentacion,
      pr.factor_salida_base, pr.modo_disponibilidad, pr.consume_stock_base_en, pr.requiere_empaque,
      pr.capacidad_diaria, pr.merma_porcentaje, pr.estatus,
      base.sku AS sku_base, base.nombre AS nombre_base, ub.abreviatura AS unidad_base,
      ub.tipo_magnitud AS unidad_base_magnitud, ub.decimales_permitidos AS unidad_base_decimales,
      pres.sku AS sku_presentacion, pres.nombre AS nombre_presentacion, up.abreviatura AS unidad_presentacion,
      up.tipo_magnitud AS unidad_presentacion_magnitud, up.decimales_permitidos AS unidad_presentacion_decimales
      FROM erp_catalogo_sku_presentaciones pr
      INNER JOIN erp_catalogo_skus base ON base.id_sku=pr.id_sku_base
      INNER JOIN erp_catalogo_skus pres ON pres.id_sku=pr.id_sku_presentacion
      INNER JOIN erp_catalogo_unidades ub ON ub.id_unidad=base.id_unidad_base
      INNER JOIN erp_catalogo_unidades up ON up.id_unidad=pres.id_unidad_base
      WHERE base.id_producto_erp=:producto OR pres.id_producto_erp=:producto
      ORDER BY FIELD(pr.estatus, 'activa', 'inactiva'), base.sku, pres.sku");
    $stmt->execute(array(":producto" => intval($idProducto)));
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public function actualizarProducto($datos, $idUsuario) {
    $idProducto = intval(isset($datos["id_producto_erp"]) ? $datos["id_producto_erp"] : 0);
    if ($idProducto <= 0 || $this->texto($datos, "codigo_producto") === "" || $this->texto($datos, "nombre_producto") === "") {
      return $this->respuesta(true, "warning", "Completa los datos obligatorios del producto");
    }

    $db = $this->getConexion();
    try {
      $db->beginTransaction();
      $stmt = $db->prepare("SELECT estatus FROM erp_catalogo_productos WHERE id_producto_erp = :producto LIMIT 1");
      $stmt->execute(array(":producto" => $idProducto));
      $estatusActual = $stmt->fetchColumn();
      if ($estatusActual === false) {
        throw new Exception("Producto ERP no encontrado");
      }
      $estatusActual = (string) $estatusActual;
      if ($estatusActual === "fusionado") {
        throw new Exception("No se puede editar manualmente un producto fusionado");
      }
      $idMarca = $this->resolverMarca($db, $datos);
      $idCategoria = $this->resolverCategoria($db, $datos);
      $categoriasSecundarias = $this->resolverCategoriasSecundariasProducto($db, $datos, $idCategoria);
      $stmt = $db->prepare("UPDATE erp_catalogo_productos SET
        codigo_producto = :codigo, nombre = :nombre, descripcion = :descripcion,
        tipo_producto = :tipo, id_marca_erp = :marca, maneja_variantes = :variantes,
        estatus = :estatus, actualizado_por = :usuario, fecha_actualizacion = CURRENT_TIMESTAMP
        WHERE id_producto_erp = :producto");
      $stmt->execute(array(
        ":codigo" => $this->texto($datos, "codigo_producto"),
        ":nombre" => $this->texto($datos, "nombre_producto"),
        ":descripcion" => $this->texto($datos, "descripcion"),
        ":tipo" => $this->opcion($datos, "tipo_producto", array("producto", "servicio", "kit", "insumo"), "producto"),
        ":marca" => $idMarca ?: null,
        ":variantes" => $this->booleano($datos, "maneja_variantes"),
        ":estatus" => $this->estatusProductoMaestro($datos),
        ":usuario" => intval($idUsuario) ?: null,
        ":producto" => $idProducto
      ));
      $stmt = $db->prepare("DELETE FROM erp_catalogo_producto_categorias WHERE id_producto_erp=:producto");
      $stmt->execute(array(":producto" => $idProducto));
      if ($idCategoria > 0) {
        $stmt = $db->prepare("INSERT INTO erp_catalogo_producto_categorias
          (id_producto_erp, id_categoria_erp, es_principal)
          VALUES (:producto, :categoria, 1)");
        $stmt->execute(array(":producto" => $idProducto, ":categoria" => $idCategoria));
      }
      foreach ($categoriasSecundarias as $idCategoriaSecundaria) {
        $stmt = $db->prepare("INSERT INTO erp_catalogo_producto_categorias
          (id_producto_erp, id_categoria_erp, es_principal)
          VALUES (:producto, :categoria, 0)");
        $stmt->execute(array(":producto" => $idProducto, ":categoria" => $idCategoriaSecundaria));
      }
      $db->commit();
      return $this->respuesta(false, "success", "Producto maestro actualizado", array(
        "id_producto_erp" => $idProducto,
        "categorias_secundarias" => count($categoriasSecundarias)
      ));
    } catch (Exception $e) {
      if ($db->inTransaction()) {
        $db->rollBack();
      }
      return $this->respuesta(true, "danger", $e->getCode() === "23000" ? "El código de producto ya existe" : $e->getMessage());
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-11
   * Proposito: normaliza y valida categorias secundarias de producto manteniendo una unica principal.
   * Impacto: Catalogo ERP; permite multiclase operativa sin aceptar categorias estructurales ni legado ecommerce.
   */
  private function resolverCategoriasSecundariasProducto($db, $datos, $idCategoriaPrincipal) {
    $entrada = isset($datos["categorias_secundarias"]) ? $datos["categorias_secundarias"] : array();
    if (!is_array($entrada)) {
      $entrada = $entrada === "" ? array() : explode(",", (string) $entrada);
    }
    $ids = array();
    foreach ($entrada as $valor) {
      $id = intval($valor);
      if ($id > 0 && $id !== intval($idCategoriaPrincipal)) {
        $ids[$id] = $id;
      }
    }
    if (!$ids) {
      return array();
    }
    $stmt = $db->prepare("SELECT id_categoria_erp FROM erp_catalogo_categorias
      WHERE id_categoria_erp=:categoria AND estatus='activa' AND tipo_categoria='maestra' AND permite_productos=1");
    foreach ($ids as $id) {
      $stmt->execute(array(":categoria" => $id));
      if (!$stmt->fetchColumn()) {
        throw new Exception("Una categoria secundaria no esta activa o no permite productos");
      }
    }
    return array_values($ids);
  }

  public function agregarSku($datos, $idUsuario) {
    $idProducto = intval(isset($datos["id_producto_erp"]) ? $datos["id_producto_erp"] : 0);
    if ($idProducto > 0) {
      $datos = $this->aplicarBaseSkuProducto($idProducto, $datos);
    }
    $validacion = $this->validarSku($datos);
    if ($idProducto <= 0 || $validacion !== true) {
      return $this->respuesta(true, "warning", $idProducto <= 0 ? "Falta el producto maestro" : $validacion);
    }

    $db = $this->getConexion();
    try {
      $db->beginTransaction();
      $stmt = $db->prepare("SELECT id_producto_erp FROM erp_catalogo_productos WHERE id_producto_erp = :producto LIMIT 1");
      $stmt->execute(array(":producto" => $idProducto));
      if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
        throw new Exception("Producto ERP no encontrado");
      }
      $idSku = $this->insertarSkuCompleto($db, $idProducto, $datos, $idUsuario);
      $stmt = $db->prepare("UPDATE erp_catalogo_productos SET maneja_variantes = 1, actualizado_por = :usuario, fecha_actualizacion = CURRENT_TIMESTAMP WHERE id_producto_erp = :producto");
      $stmt->execute(array(":usuario" => intval($idUsuario) ?: null, ":producto" => $idProducto));
      $db->commit();
      return $this->respuesta(false, "success", "SKU agregado correctamente", array("id_producto_erp" => $idProducto, "id_sku" => $idSku));
    } catch (Exception $e) {
      if ($db->inTransaction()) {
        $db->rollBack();
      }
      return $this->respuesta(true, "danger", $e->getCode() === "23000" ? "El SKU o código de barras ya existe" : $e->getMessage());
    }
  }

  public function baseSkuProducto($idProducto) {
    try {
      $db = $this->getConexion();
      $base = $this->consultarBaseSkuProducto($db, intval($idProducto));
      if (!$base) {
        return $this->respuesta(false, "info", "El producto aún no tiene SKU base", array());
      }
      return $this->respuesta(false, "success", "Base de SKU consultada", $base);
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-26
   * Proposito: busca SKUs activos para armar recetas de paquetes con componentes de cualquier producto.
   * Impacto: Catalogo ERP; evita limitar paquetes a SKUs del producto maestro abierto.
   */
  public function buscarSkusParaPaquete($termino, $limite = 20) {
    $termino = trim((string) $termino);
    $limite = max(1, min(50, intval($limite)));
    if (strlen($termino) < 2) {
      return $this->respuesta(false, "success", "Indica al menos dos caracteres", array());
    }
    try {
      $db = $this->getConexion();
      $stmt = $db->prepare("SELECT s.id_sku, s.sku, s.nombre, s.estatus,
        p.codigo_producto, p.nombre AS producto, u.id_unidad, u.nombre AS unidad, u.abreviatura
        FROM erp_catalogo_skus s
        INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
        INNER JOIN erp_catalogo_unidades u ON u.id_unidad=s.id_unidad_base
        WHERE s.estatus IN ('activo','borrador','en_revision')
          AND (s.sku LIKE :q_sku OR s.nombre LIKE :q_nombre OR p.nombre LIKE :q_producto OR p.codigo_producto LIKE :q_codigo)
        ORDER BY CASE WHEN s.sku=:exacto THEN 0 WHEN s.sku LIKE :prefijo THEN 1 ELSE 2 END, s.sku
        LIMIT " . intval($limite));
      $stmt->execute(array(
        ":q_sku" => "%" . $termino . "%",
        ":q_nombre" => "%" . $termino . "%",
        ":q_producto" => "%" . $termino . "%",
        ":q_codigo" => "%" . $termino . "%",
        ":exacto" => $termino,
        ":prefijo" => $termino . "%"
      ));
      return $this->respuesta(false, "success", "SKUs encontrados", $stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  public function actualizarSku($datos, $idUsuario) {
    $idSku = intval(isset($datos["id_sku"]) ? $datos["id_sku"] : 0);
    $idProducto = intval(isset($datos["id_producto_erp"]) ? $datos["id_producto_erp"] : 0);
    $validacion = $this->validarSku($datos);
    if ($idSku <= 0 || $idProducto <= 0 || $validacion !== true) {
      return $this->respuesta(true, "warning", $idSku <= 0 ? "Selecciona el SKU que vas a editar" : ($idProducto <= 0 ? "Falta el producto maestro" : $validacion));
    }

    $db = $this->getConexion();
    try {
      $db->beginTransaction();
      $stmt = $db->prepare("SELECT s.id_sku, s.sku, s.estatus, s.costo_referencia, cod.codigo codigo_barras
        FROM erp_catalogo_skus s
        LEFT JOIN erp_catalogo_sku_codigos cod ON cod.id_sku=s.id_sku
          AND cod.es_principal=1 AND cod.estatus='activo'
          AND cod.id_sku_codigo = (
            SELECT c2.id_sku_codigo FROM erp_catalogo_sku_codigos c2
            WHERE c2.id_sku=s.id_sku AND c2.es_principal=1 AND c2.estatus='activo'
            ORDER BY c2.tipo_codigo='codigo_barras' DESC, c2.id_sku_codigo DESC LIMIT 1
          )
        WHERE s.id_sku=:sku AND s.id_producto_erp=:producto LIMIT 1");
      $stmt->execute(array(":sku" => $idSku, ":producto" => $idProducto));
      $skuActual = $stmt->fetch(PDO::FETCH_ASSOC);
      if (!$skuActual) {
        throw new Exception("El SKU no pertenece al producto seleccionado");
      }
      if ((string) $skuActual["estatus"] === "fusionado") {
        throw new Exception("No se puede editar manualmente un SKU fusionado");
      }
      $codigoSkuAnterior = isset($skuActual["sku"]) ? (string) $skuActual["sku"] : "";
      $codigoBarrasAnterior = isset($skuActual["codigo_barras"]) ? (string) $skuActual["codigo_barras"] : "";
      $codigoSkuNuevo = $this->texto($datos, "sku");
      $codigoBarras = $this->texto($datos, "codigo_barras");
      $cambiaIdentidad = $codigoSkuAnterior !== $codigoSkuNuevo || $codigoBarrasAnterior !== $codigoBarras;
      $motivoIdentidad = $this->texto($datos, "motivo_cambio_identidad");
      if ($cambiaIdentidad && $motivoIdentidad === "") {
        throw new Exception("Indica el motivo del cambio de SKU o código de barras");
      }

      $tipoInventario = $this->tipoInventario($datos);
      $controlaInventario = $this->controlaInventario($tipoInventario);
      $costoReferencia = array_key_exists("costo_referencia", $datos)
        ? $this->decimal($datos, "costo_referencia")
        : floatval(isset($skuActual["costo_referencia"]) ? $skuActual["costo_referencia"] : 0);
      $stmt = $db->prepare("UPDATE erp_catalogo_skus SET
        sku=:codigo, nombre=:nombre, tipo_inventario=:tipo, id_unidad_base=:unidad, factor_unidad_base=:factor_unidad,
        costo_referencia=:costo, permite_venta_sin_existencia=:venta_sin_stock,
        estatus=:estatus, actualizado_por=:usuario, fecha_actualizacion=CURRENT_TIMESTAMP
        WHERE id_sku=:sku AND id_producto_erp=:producto");
      $stmt->execute(array(
        ":codigo" => $this->texto($datos, "sku"),
        ":nombre" => $this->texto($datos, "nombre_sku"),
        ":tipo" => $tipoInventario,
        ":unidad" => intval($datos["id_unidad_base"]),
        ":factor_unidad" => $this->factorUnidadBaseSku($datos),
        ":costo" => $costoReferencia,
        ":venta_sin_stock" => $this->booleano($datos, "permite_venta_sin_existencia"),
        ":estatus" => $this->estatusSkuMaestro($datos),
        ":usuario" => intval($idUsuario) ?: null,
        ":sku" => $idSku,
        ":producto" => $idProducto
      ));

      $this->actualizarCodigoBarrasPrincipal($db, $idSku, $codigoBarras);

      $db->prepare("DELETE FROM erp_catalogo_sku_precios WHERE id_sku=:sku AND lista_precio='general'")
        ->execute(array(":sku" => $idSku));
      $stmt = $db->prepare("INSERT INTO erp_catalogo_sku_precios (id_sku, lista_precio, moneda, precio, estatus) VALUES (:sku, 'general', :moneda, :precio, 'activo')");
      $stmt->execute(array(":sku" => $idSku, ":moneda" => $this->opcion($datos, "moneda", array("MXN", "USD"), "MXN"), ":precio" => $this->decimal($datos, "precio")));

      $this->guardarFiscalSku($db, $idSku, $datos);

      $stockMaximo = $this->texto($datos, "stock_maximo");
      $reglasGranel = $this->reglasGranelSku($db, $datos, $controlaInventario);
      $stmt = $db->prepare("INSERT INTO erp_catalogo_sku_reglas_inventario
        (id_sku, controla_inventario, permite_existencia_negativa, requiere_lote, requiere_caducidad, requiere_serie, requiere_serie_fabricante, generar_etiqueta_interna, requiere_escaneo_venta, permite_venta_fraccionaria, precision_decimal, incremento_minimo_venta, unidad_venta_label, permite_etiqueta_fraccionada, prefijo_etiqueta_interna, plantilla_etiqueta, tipo_etiqueta_seguridad, instrucciones_etiquetado, estrategia_salida, stock_minimo, stock_maximo, punto_reorden, dias_alerta_caducidad, dias_minimos_recepcion)
        VALUES (:sku, :controla, :negativa, :lote, :caducidad, :serie, :serie_fabricante, :etiqueta_interna, :escaneo_venta, :venta_fraccionaria, :precision_decimal, :incremento_minimo_venta, :unidad_venta_label, :etiqueta_fraccionada, :prefijo_etiqueta, :plantilla_etiqueta, :tipo_etiqueta, :instrucciones_etiqueta, :estrategia, :minimo, :maximo, :reorden, :alerta, :min_recepcion)
        ON DUPLICATE KEY UPDATE controla_inventario=VALUES(controla_inventario), permite_existencia_negativa=VALUES(permite_existencia_negativa),
        requiere_lote=VALUES(requiere_lote), requiere_caducidad=VALUES(requiere_caducidad), requiere_serie=VALUES(requiere_serie),
        requiere_serie_fabricante=VALUES(requiere_serie_fabricante), generar_etiqueta_interna=VALUES(generar_etiqueta_interna),
        requiere_escaneo_venta=VALUES(requiere_escaneo_venta), permite_venta_fraccionaria=VALUES(permite_venta_fraccionaria),
        precision_decimal=VALUES(precision_decimal), incremento_minimo_venta=VALUES(incremento_minimo_venta),
        unidad_venta_label=VALUES(unidad_venta_label), permite_etiqueta_fraccionada=VALUES(permite_etiqueta_fraccionada),
        prefijo_etiqueta_interna=VALUES(prefijo_etiqueta_interna),
        plantilla_etiqueta=VALUES(plantilla_etiqueta), tipo_etiqueta_seguridad=VALUES(tipo_etiqueta_seguridad),
        instrucciones_etiquetado=VALUES(instrucciones_etiquetado),
        estrategia_salida=VALUES(estrategia_salida), stock_minimo=VALUES(stock_minimo), stock_maximo=VALUES(stock_maximo),
        punto_reorden=VALUES(punto_reorden), dias_alerta_caducidad=VALUES(dias_alerta_caducidad),
        dias_minimos_recepcion=VALUES(dias_minimos_recepcion), fecha_actualizacion=CURRENT_TIMESTAMP");
      $stmt->execute(array(
        ":sku" => $idSku,
        ":controla" => $controlaInventario ? 1 : 0,
        ":negativa" => $controlaInventario ? $this->booleano($datos, "permite_existencia_negativa") : 0,
        ":lote" => $controlaInventario ? $this->booleano($datos, "requiere_lote") : 0,
        ":caducidad" => $controlaInventario ? $this->booleano($datos, "requiere_caducidad") : 0,
        ":serie" => $controlaInventario ? $this->booleano($datos, "requiere_serie") : 0,
        ":serie_fabricante" => $controlaInventario ? $this->booleano($datos, "requiere_serie_fabricante") : 0,
        ":etiqueta_interna" => $controlaInventario ? $this->booleano($datos, "generar_etiqueta_interna") : 0,
        ":escaneo_venta" => $controlaInventario ? $this->booleano($datos, "requiere_escaneo_venta") : 0,
        ":venta_fraccionaria" => $reglasGranel["permite_venta_fraccionaria"],
        ":precision_decimal" => $reglasGranel["precision_decimal"],
        ":incremento_minimo_venta" => $reglasGranel["incremento_minimo_venta"],
        ":unidad_venta_label" => $reglasGranel["unidad_venta_label"],
        ":etiqueta_fraccionada" => $reglasGranel["permite_etiqueta_fraccionada"],
        ":prefijo_etiqueta" => $controlaInventario ? $this->texto($datos, "prefijo_etiqueta_interna") : "",
        ":plantilla_etiqueta" => $controlaInventario ? $this->texto($datos, "plantilla_etiqueta") : "",
        ":tipo_etiqueta" => $controlaInventario ? $this->texto($datos, "tipo_etiqueta_seguridad") : "",
        ":instrucciones_etiqueta" => $controlaInventario ? $this->texto($datos, "instrucciones_etiquetado") : "",
        ":estrategia" => $this->estrategiaSalida($datos, $controlaInventario),
        ":minimo" => $controlaInventario ? $this->decimal($datos, "stock_minimo") : 0,
        ":maximo" => $controlaInventario && $stockMaximo !== "" ? floatval($stockMaximo) : null,
        ":reorden" => $controlaInventario ? $this->decimal($datos, "punto_reorden") : 0,
        ":alerta" => max(0, intval(isset($datos["dias_alerta_caducidad"]) ? $datos["dias_alerta_caducidad"] : 90)),
        ":min_recepcion" => max(0, intval(isset($datos["dias_minimos_recepcion"]) ? $datos["dias_minimos_recepcion"] : 0))
      ));
      $this->guardarRecepcionVariableSku($db, $idSku, $datos, $controlaInventario);

      $cambiosIdentidad = array();
      if ($codigoSkuAnterior !== $codigoSkuNuevo) {
        $cambiosIdentidad["sku"] = array("antes" => $codigoSkuAnterior, "despues" => $codigoSkuNuevo);
      }
      if ($codigoBarrasAnterior !== $codigoBarras) {
        $cambiosIdentidad["codigo_barras"] = array("antes" => $codigoBarrasAnterior, "despues" => $codigoBarras);
      }
      if (!empty($cambiosIdentidad)) {
        $cambiosIdentidad["motivo"] = $motivoIdentidad;
      }

      $db->commit();
      return $this->respuesta(false, "success", "SKU actualizado correctamente", array(
        "id_producto_erp" => $idProducto,
        "id_sku" => $idSku,
        "cambios_identidad" => $cambiosIdentidad
      ));
    } catch (Exception $e) {
      if ($db->inTransaction()) {
        $db->rollBack();
      }
      return $this->respuesta(true, "danger", $e->getCode() === "23000" ? "El SKU o código de barras ya existe" : $e->getMessage());
    }
  }

  public function crearProductoConSku($datos, $idUsuario) {
    $validacion = $this->validarAlta($datos);
    if ($validacion !== true) {
      return $this->respuesta(true, "warning", $validacion);
    }

    $db = $this->getConexion();
    try {
      $db->beginTransaction();

      $idMarca = $this->resolverMarca($db, $datos);
      $idCategoria = $this->resolverCategoria($db, $datos);

      $stmt = $db->prepare("INSERT INTO erp_catalogo_productos
        (codigo_producto, nombre, descripcion, tipo_producto, id_marca_erp, maneja_variantes, estatus, creado_por)
        VALUES (:codigo, :nombre, :descripcion, :tipo, :marca, :variantes, :estatus, :usuario)");
      $stmt->execute(array(
        ":codigo" => $this->texto($datos, "codigo_producto"),
        ":nombre" => $this->texto($datos, "nombre_producto"),
        ":descripcion" => $this->texto($datos, "descripcion"),
        ":tipo" => $this->opcion($datos, "tipo_producto", array("producto", "servicio", "kit", "insumo"), "producto"),
        ":marca" => $idMarca ?: null,
        ":variantes" => $this->booleano($datos, "maneja_variantes"),
        ":estatus" => $this->estatusProductoMaestro($datos),
        ":usuario" => intval($idUsuario) ?: null
      ));
      $idProducto = intval($db->lastInsertId());

      if ($idCategoria > 0) {
        $stmt = $db->prepare("INSERT INTO erp_catalogo_producto_categorias (id_producto_erp, id_categoria_erp, es_principal) VALUES (:producto, :categoria, 1)");
        $stmt->execute(array(":producto" => $idProducto, ":categoria" => $idCategoria));
      }

      $tipoInventario = $this->tipoInventario($datos);
      $stmt = $db->prepare("INSERT INTO erp_catalogo_skus
        (id_producto_erp, sku, nombre, tipo_inventario, id_unidad_base, factor_unidad_base, costo_referencia, permite_venta_sin_existencia, estatus, creado_por)
        VALUES (:producto, :sku, :nombre, :tipo, :unidad, :factor_unidad, :costo, :venta_sin_stock, :estatus, :usuario)");
      $stmt->execute(array(
        ":producto" => $idProducto,
        ":sku" => $this->texto($datos, "sku"),
        ":nombre" => $this->texto($datos, "nombre_sku", $this->texto($datos, "nombre_producto")),
        ":tipo" => $tipoInventario,
        ":unidad" => intval($datos["id_unidad_base"]),
        ":factor_unidad" => $this->factorUnidadBaseSku($datos),
        ":costo" => $this->decimal($datos, "costo_referencia"),
        ":venta_sin_stock" => $this->booleano($datos, "permite_venta_sin_existencia"),
        ":estatus" => $this->estatusSkuMaestro($datos),
        ":usuario" => intval($idUsuario) ?: null
      ));
      $idSku = intval($db->lastInsertId());

      $codigoBarras = $this->texto($datos, "codigo_barras");
      $this->actualizarCodigoBarrasPrincipal($db, $idSku, $codigoBarras);

      $stmt = $db->prepare("INSERT INTO erp_catalogo_sku_precios (id_sku, lista_precio, moneda, precio, estatus) VALUES (:sku, 'general', :moneda, :precio, 'activo')");
      $stmt->execute(array(
        ":sku" => $idSku,
        ":moneda" => $this->opcion($datos, "moneda", array("MXN", "USD"), "MXN"),
        ":precio" => $this->decimal($datos, "precio")
      ));

      $this->guardarFiscalSku($db, $idSku, $datos);

      $controlaInventario = $this->controlaInventario($tipoInventario);
      $stmt = $db->prepare("INSERT INTO erp_catalogo_sku_reglas_inventario
        (id_sku, controla_inventario, permite_existencia_negativa, requiere_lote, requiere_caducidad, requiere_serie, requiere_serie_fabricante, generar_etiqueta_interna, requiere_escaneo_venta, permite_venta_fraccionaria, precision_decimal, incremento_minimo_venta, unidad_venta_label, permite_etiqueta_fraccionada, prefijo_etiqueta_interna, plantilla_etiqueta, tipo_etiqueta_seguridad, instrucciones_etiquetado, estrategia_salida, stock_minimo, stock_maximo, punto_reorden, dias_alerta_caducidad, dias_minimos_recepcion)
        VALUES (:sku, :controla, :negativa, :lote, :caducidad, :serie, :serie_fabricante, :etiqueta_interna, :escaneo_venta, :venta_fraccionaria, :precision_decimal, :incremento_minimo_venta, :unidad_venta_label, :etiqueta_fraccionada, :prefijo_etiqueta, :plantilla_etiqueta, :tipo_etiqueta, :instrucciones_etiqueta, :estrategia, :minimo, :maximo, :reorden, :alerta, :min_recepcion)");
      $stockMaximo = $this->texto($datos, "stock_maximo");
      $reglasGranel = $this->reglasGranelSku($db, $datos, $controlaInventario);
      $stmt->execute(array(
        ":sku" => $idSku,
        ":controla" => $controlaInventario ? 1 : 0,
        ":negativa" => $controlaInventario ? $this->booleano($datos, "permite_existencia_negativa") : 0,
        ":lote" => $controlaInventario ? $this->booleano($datos, "requiere_lote") : 0,
        ":caducidad" => $controlaInventario ? $this->booleano($datos, "requiere_caducidad") : 0,
        ":serie" => $controlaInventario ? $this->booleano($datos, "requiere_serie") : 0,
        ":serie_fabricante" => $controlaInventario ? $this->booleano($datos, "requiere_serie_fabricante") : 0,
        ":etiqueta_interna" => $controlaInventario ? $this->booleano($datos, "generar_etiqueta_interna") : 0,
        ":escaneo_venta" => $controlaInventario ? $this->booleano($datos, "requiere_escaneo_venta") : 0,
        ":venta_fraccionaria" => $reglasGranel["permite_venta_fraccionaria"],
        ":precision_decimal" => $reglasGranel["precision_decimal"],
        ":incremento_minimo_venta" => $reglasGranel["incremento_minimo_venta"],
        ":unidad_venta_label" => $reglasGranel["unidad_venta_label"],
        ":etiqueta_fraccionada" => $reglasGranel["permite_etiqueta_fraccionada"],
        ":prefijo_etiqueta" => $controlaInventario ? $this->texto($datos, "prefijo_etiqueta_interna") : "",
        ":plantilla_etiqueta" => $controlaInventario ? $this->texto($datos, "plantilla_etiqueta") : "",
        ":tipo_etiqueta" => $controlaInventario ? $this->texto($datos, "tipo_etiqueta_seguridad") : "",
        ":instrucciones_etiqueta" => $controlaInventario ? $this->texto($datos, "instrucciones_etiquetado") : "",
        ":estrategia" => $this->estrategiaSalida($datos, $controlaInventario),
        ":minimo" => $controlaInventario ? $this->decimal($datos, "stock_minimo") : 0,
        ":maximo" => $controlaInventario && $stockMaximo !== "" ? floatval($stockMaximo) : null,
        ":reorden" => $controlaInventario ? $this->decimal($datos, "punto_reorden") : 0,
        ":alerta" => max(0, intval(isset($datos["dias_alerta_caducidad"]) ? $datos["dias_alerta_caducidad"] : 90)),
        ":min_recepcion" => max(0, intval(isset($datos["dias_minimos_recepcion"]) ? $datos["dias_minimos_recepcion"] : 0))
      ));
      $this->guardarRecepcionVariableSku($db, $idSku, $datos, $controlaInventario);

      $db->commit();
      return $this->respuesta(false, "success", "Producto y SKU registrados correctamente", array(
        "id_producto_erp" => $idProducto,
        "id_sku" => $idSku
      ));
    } catch (Exception $e) {
      if ($db->inTransaction()) {
        $db->rollBack();
      }
      $mensaje = $e->getCode() === "23000" ? "El codigo de producto, SKU o codigo de barras ya existe" : $e->getMessage();
      return $this->respuesta(true, "danger", $mensaje);
    }
  }

  private function resolverMarca($db, $datos) {
    $id = intval(isset($datos["id_marca_erp"]) ? $datos["id_marca_erp"] : 0);
    $nombre = $this->texto($datos, "marca_nueva");
    if ($id > 0 || $nombre === "") {
      return $id;
    }
    $stmt = $db->prepare("SELECT id_marca_erp FROM erp_catalogo_marcas WHERE nombre = :nombre LIMIT 1");
    $stmt->execute(array(":nombre" => $nombre));
    $existente = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($existente) {
      return intval($existente["id_marca_erp"]);
    }
    $codigo = $this->codigoDesdeTexto($nombre, "MAR");
    $stmt = $db->prepare("INSERT INTO erp_catalogo_marcas (codigo, nombre) VALUES (:codigo, :nombre)");
    $stmt->execute(array(":codigo" => $codigo, ":nombre" => $nombre));
    return intval($db->lastInsertId());
  }

  private function insertarSkuCompleto($db, $idProducto, $datos, $idUsuario) {
    $tipoInventario = $this->tipoInventario($datos);
    $controlaInventario = $this->controlaInventario($tipoInventario);
    $stmt = $db->prepare("INSERT INTO erp_catalogo_skus
      (id_producto_erp, sku, nombre, tipo_inventario, id_unidad_base, factor_unidad_base, costo_referencia, permite_venta_sin_existencia, estatus, creado_por)
      VALUES (:producto, :sku, :nombre, :tipo, :unidad, :factor_unidad, :costo, :venta_sin_stock, :estatus, :usuario)");
    $stmt->execute(array(
      ":producto" => intval($idProducto),
      ":sku" => $this->texto($datos, "sku"),
      ":nombre" => $this->texto($datos, "nombre_sku", $this->texto($datos, "nombre_producto")),
      ":tipo" => $tipoInventario,
      ":unidad" => intval($datos["id_unidad_base"]),
      ":factor_unidad" => $this->factorUnidadBaseSku($datos),
      ":costo" => $this->decimal($datos, "costo_referencia"),
      ":venta_sin_stock" => $this->booleano($datos, "permite_venta_sin_existencia"),
      ":estatus" => $this->estatusSkuMaestro($datos),
      ":usuario" => intval($idUsuario) ?: null
    ));
    $idSku = intval($db->lastInsertId());

    $this->actualizarCodigoBarrasPrincipal($db, $idSku, $this->texto($datos, "codigo_barras"));
    $stmt = $db->prepare("INSERT INTO erp_catalogo_sku_precios (id_sku, lista_precio, moneda, precio, estatus) VALUES (:sku, 'general', :moneda, :precio, 'activo')");
    $stmt->execute(array(":sku" => $idSku, ":moneda" => $this->opcion($datos, "moneda", array("MXN", "USD"), "MXN"), ":precio" => $this->decimal($datos, "precio")));
    $this->guardarFiscalSku($db, $idSku, $datos);
    $stockMaximo = $this->texto($datos, "stock_maximo");
    $stmt = $db->prepare("INSERT INTO erp_catalogo_sku_reglas_inventario
      (id_sku, controla_inventario, permite_existencia_negativa, requiere_lote, requiere_caducidad, requiere_serie, requiere_serie_fabricante, generar_etiqueta_interna, requiere_escaneo_venta, permite_venta_fraccionaria, precision_decimal, incremento_minimo_venta, unidad_venta_label, permite_etiqueta_fraccionada, prefijo_etiqueta_interna, plantilla_etiqueta, tipo_etiqueta_seguridad, instrucciones_etiquetado, estrategia_salida, stock_minimo, stock_maximo, punto_reorden, dias_alerta_caducidad, dias_minimos_recepcion)
      VALUES (:sku, :controla, :negativa, :lote, :caducidad, :serie, :serie_fabricante, :etiqueta_interna, :escaneo_venta, :venta_fraccionaria, :precision_decimal, :incremento_minimo_venta, :unidad_venta_label, :etiqueta_fraccionada, :prefijo_etiqueta, :plantilla_etiqueta, :tipo_etiqueta, :instrucciones_etiqueta, :estrategia, :minimo, :maximo, :reorden, :alerta, :min_recepcion)");
    $reglasGranel = $this->reglasGranelSku($db, $datos, $controlaInventario);
    $stmt->execute(array(
      ":sku" => $idSku, ":controla" => $controlaInventario ? 1 : 0,
      ":negativa" => $controlaInventario ? $this->booleano($datos, "permite_existencia_negativa") : 0, ":lote" => $controlaInventario ? $this->booleano($datos, "requiere_lote") : 0,
      ":caducidad" => $controlaInventario ? $this->booleano($datos, "requiere_caducidad") : 0, ":serie" => $controlaInventario ? $this->booleano($datos, "requiere_serie") : 0,
      ":serie_fabricante" => $controlaInventario ? $this->booleano($datos, "requiere_serie_fabricante") : 0, ":etiqueta_interna" => $controlaInventario ? $this->booleano($datos, "generar_etiqueta_interna") : 0,
      ":escaneo_venta" => $controlaInventario ? $this->booleano($datos, "requiere_escaneo_venta") : 0, ":prefijo_etiqueta" => $controlaInventario ? $this->texto($datos, "prefijo_etiqueta_interna") : "",
      ":venta_fraccionaria" => $reglasGranel["permite_venta_fraccionaria"], ":precision_decimal" => $reglasGranel["precision_decimal"],
      ":incremento_minimo_venta" => $reglasGranel["incremento_minimo_venta"], ":unidad_venta_label" => $reglasGranel["unidad_venta_label"],
      ":etiqueta_fraccionada" => $reglasGranel["permite_etiqueta_fraccionada"],
      ":plantilla_etiqueta" => $controlaInventario ? $this->texto($datos, "plantilla_etiqueta") : "", ":tipo_etiqueta" => $controlaInventario ? $this->texto($datos, "tipo_etiqueta_seguridad") : "",
      ":instrucciones_etiqueta" => $controlaInventario ? $this->texto($datos, "instrucciones_etiquetado") : "",
      ":estrategia" => $this->estrategiaSalida($datos, $controlaInventario),
      ":minimo" => $controlaInventario ? $this->decimal($datos, "stock_minimo") : 0, ":maximo" => $controlaInventario && $stockMaximo !== "" ? floatval($stockMaximo) : null,
      ":reorden" => $controlaInventario ? $this->decimal($datos, "punto_reorden") : 0, ":alerta" => max(0, intval(isset($datos["dias_alerta_caducidad"]) ? $datos["dias_alerta_caducidad"] : 90)),
      ":min_recepcion" => max(0, intval(isset($datos["dias_minimos_recepcion"]) ? $datos["dias_minimos_recepcion"] : 0))
    ));
    $this->guardarRecepcionVariableSku($db, $idSku, $datos, $controlaInventario);
    return $idSku;
  }

  private function aplicarBaseSkuProducto($idProducto, $datos) {
    $db = $this->getConexion();
    $base = $this->consultarBaseSkuProducto($db, $idProducto);
    if (!$base) {
      return $datos;
    }
    $campos = array(
      "id_unidad_base", "factor_unidad_base", "tipo_inventario", "costo_referencia", "precio", "moneda",
      "stock_minimo", "stock_maximo", "punto_reorden", "estrategia_salida",
      "iva_porcentaje", "ieps_porcentaje", "incluye_impuestos", "requiere_lote",
      "requiere_caducidad", "requiere_serie", "permite_venta_sin_existencia",
      "permite_existencia_negativa", "dias_alerta_caducidad", "dias_minimos_recepcion",
      "permite_venta_fraccionaria", "precision_decimal", "incremento_minimo_venta",
      "unidad_venta_label", "permite_etiqueta_fraccionada",
      "requiere_cantidad_variable_recepcion", "requiere_unidades_fisicas_recepcion",
      "tolerancia_recepcion_porcentaje", "nota_recepcion_variable"
    );
    foreach ($campos as $campo) {
      if (!isset($datos[$campo]) || trim((string) $datos[$campo]) === "") {
        $datos[$campo] = isset($base[$campo]) ? $base[$campo] : "";
      }
    }
    return $datos;
  }

  private function consultarBaseSkuProducto($db, $idProducto) {
    $recepcionVariableSelect = $this->esquemaRecepcionVariableDisponible($db)
      ? "r.requiere_cantidad_variable_recepcion, r.requiere_unidades_fisicas_recepcion, r.tolerancia_recepcion_porcentaje, r.nota_recepcion_variable,"
      : "0 AS requiere_cantidad_variable_recepcion, 0 AS requiere_unidades_fisicas_recepcion, NULL AS tolerancia_recepcion_porcentaje, NULL AS nota_recepcion_variable,";
    $stmt = $db->prepare("SELECT s.id_sku, s.id_unidad_base, s.factor_unidad_base, s.tipo_inventario, s.costo_referencia,
      s.permite_venta_sin_existencia, pr.precio, COALESCE(pr.moneda, 'MXN') moneda,
      r.stock_minimo, r.stock_maximo, r.punto_reorden, r.estrategia_salida,
      r.requiere_lote, r.requiere_caducidad, r.requiere_serie, r.requiere_serie_fabricante,
      r.generar_etiqueta_interna, r.requiere_escaneo_venta, r.prefijo_etiqueta_interna,
      r.permite_venta_fraccionaria, r.precision_decimal, r.incremento_minimo_venta,
      r.unidad_venta_label, r.permite_etiqueta_fraccionada,
      r.plantilla_etiqueta, r.tipo_etiqueta_seguridad, r.instrucciones_etiquetado,
      r.permite_existencia_negativa, r.dias_alerta_caducidad, r.dias_minimos_recepcion,
      " . $recepcionVariableSelect . "
      imp.iva_porcentaje, imp.ieps_porcentaje, imp.incluye_impuestos
      FROM erp_catalogo_skus s
      LEFT JOIN erp_catalogo_sku_precios pr ON pr.id_sku=s.id_sku AND pr.lista_precio='general' AND pr.estatus='activo'
      LEFT JOIN erp_catalogo_sku_reglas_inventario r ON r.id_sku=s.id_sku
      LEFT JOIN erp_catalogo_sku_impuestos imp ON imp.id_sku=s.id_sku
      WHERE s.id_producto_erp=:producto AND s.estatus<>'fusionado'
      ORDER BY s.id_sku DESC
      LIMIT 1");
    $stmt->execute(array(":producto" => intval($idProducto)));
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-11
   * Proposito: resuelve solo categorias operativas existentes para evitar raices sueltas desde producto.
   * Impacto: Catalogo ERP; obliga a crear categorias en Configuracion con padre, uso e imagen.
   */
  private function resolverCategoria($db, $datos) {
    $id = intval(isset($datos["id_categoria_erp"]) ? $datos["id_categoria_erp"] : 0);
    $nombre = $this->texto($datos, "categoria_nueva");
    if ($id > 0) {
      $stmt = $db->prepare("SELECT id_categoria_erp FROM erp_catalogo_categorias
        WHERE id_categoria_erp=:categoria AND estatus='activa' AND tipo_categoria='maestra' AND permite_productos=1");
      $stmt->execute(array(":categoria" => $id));
      if (!$stmt->fetchColumn()) {
        throw new Exception("Selecciona una categoria operativa que permita productos");
      }
      return $id;
    }
    if ($nombre === "") {
      return 0;
    }
    throw new Exception("Crea la categoria desde Configuracion para definir padre, uso e imagen antes de asignarla al producto");
  }

  private function validarAlta($datos) {
    foreach (array("codigo_producto", "nombre_producto", "sku", "id_unidad_base") as $campo) {
      if ($this->texto($datos, $campo) === "" || ($campo === "id_unidad_base" && intval($datos[$campo]) <= 0)) {
        return "Completa los campos obligatorios del producto y SKU";
      }
    }
    if ($this->decimal($datos, "precio") < 0 || $this->decimal($datos, "costo_referencia") < 0) {
      return "Precio y costo no pueden ser negativos";
    }
    if ($this->factorUnidadBaseSku($datos) <= 0) {
      return "El factor de conversion de la unidad base debe ser mayor a cero";
    }
    $controlaInventario = $this->controlaInventario($this->tipoInventario($datos));
    if ($controlaInventario && $this->booleano($datos, "requiere_caducidad") && !$this->booleano($datos, "requiere_lote")) {
      return "Un SKU con caducidad debe controlar lote";
    }
    $validacionReorden = $this->validarReordenSku($datos);
    return $validacionReorden === true ? $this->validarFiscalSku($datos) : $validacionReorden;
  }

  private function reglasGranelSku($db, $datos, $controlaInventario) {
    if (!$controlaInventario) {
      return $this->reglasGranelDefault();
    }

    $ventaFraccionaria = $this->booleano($datos, "permite_venta_fraccionaria");
    if (!$ventaFraccionaria) {
      return $this->reglasGranelDefault();
    }

    $idUnidad = intval(isset($datos["id_unidad_base"]) ? $datos["id_unidad_base"] : 0);
    if ($idUnidad <= 0) {
      throw new Exception("Selecciona unidad base antes de activar venta fraccionaria");
    }
    $stmt = $db->prepare("SELECT id_unidad, abreviatura, decimales_permitidos FROM erp_catalogo_unidades WHERE id_unidad=:unidad AND estatus='activa' LIMIT 1");
    $stmt->execute(array(":unidad" => $idUnidad));
    $unidad = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$unidad) {
      throw new Exception("Selecciona una unidad base activa para venta fraccionaria");
    }
    if (intval($unidad["decimales_permitidos"]) !== 1) {
      throw new Exception("La unidad base debe permitir decimales para vender a granel");
    }

    $precision = intval(isset($datos["precision_decimal"]) ? $datos["precision_decimal"] : 0);
    $incremento = $this->decimal($datos, "incremento_minimo_venta");
    if ($precision < 1 || $precision > 6) {
      throw new Exception("La precision decimal para granel debe estar entre 1 y 6");
    }
    if ($incremento <= 0) {
      throw new Exception("El incremento minimo de venta a granel debe ser mayor a 0");
    }
    if ($incremento < pow(10, -1 * $precision)) {
      throw new Exception("El incremento minimo no puede ser menor a la precision decimal configurada");
    }
    if ($this->cantidadDecimalesCatalogo($this->texto($datos, "incremento_minimo_venta")) > $precision) {
      throw new Exception("El incremento minimo no puede tener mas decimales que la precision configurada");
    }

    $permiteEtiquetaFraccionada = $this->booleano($datos, "permite_etiqueta_fraccionada");
    if ($this->booleano($datos, "generar_etiqueta_interna") && !$permiteEtiquetaFraccionada) {
      throw new Exception("No se permite etiqueta individual en venta fraccionaria salvo que actives etiqueta fraccionada");
    }
    if (($this->booleano($datos, "requiere_serie") || $this->booleano($datos, "requiere_serie_fabricante")) && !$permiteEtiquetaFraccionada) {
      throw new Exception("No se permite serie individual en venta fraccionaria salvo que actives etiqueta fraccionada");
    }

    $unidadLabel = $this->texto($datos, "unidad_venta_label");
    if ($unidadLabel === "") {
      $unidadLabel = isset($unidad["abreviatura"]) ? $unidad["abreviatura"] : "";
    }

    return array(
      "permite_venta_fraccionaria" => 1,
      "precision_decimal" => $precision,
      "incremento_minimo_venta" => $incremento,
      "unidad_venta_label" => $unidadLabel,
      "permite_etiqueta_fraccionada" => $permiteEtiquetaFraccionada
    );
  }

  private function reglasGranelDefault() {
    return array(
      "permite_venta_fraccionaria" => 0,
      "precision_decimal" => 0,
      "incremento_minimo_venta" => 1,
      "unidad_venta_label" => "",
      "permite_etiqueta_fraccionada" => 0
    );
  }

  private function cantidadDecimalesCatalogo($valor) {
    $valor = trim((string) $valor);
    if ($valor === "") {
      return 0;
    }
    $valor = strtolower(str_replace(",", ".", $valor));
    if (strpos($valor, "e") !== false) {
      $valor = rtrim(rtrim(sprintf("%.10F", floatval($valor)), "0"), ".");
    }
    $partes = explode(".", $valor, 2);
    if (count($partes) < 2) {
      return 0;
    }
    return strlen(rtrim($partes[1], "0"));
  }

  private function tipoInventario($datos) {
    return $this->opcion($datos, "tipo_inventario", array("inventariable", "servicio", "cargo", "consumible", "kit"), "inventariable");
  }

  private function controlaInventario($tipoInventario) {
    return !in_array($tipoInventario, array("servicio", "cargo"), true);
  }

  private function estrategiaSalida($datos, $controlaInventario) {
    if (!$controlaInventario) {
      return "FIFO";
    }
    if ($this->booleano($datos, "requiere_caducidad")) {
      return "FEFO";
    }
    return $this->opcion($datos, "estrategia_salida", array("FIFO", "FEFO", "LIFO"), "FIFO");
  }

  private function validarReordenSku($datos) {
    if (!$this->controlaInventario($this->tipoInventario($datos))) {
      return true;
    }

    $minimo = $this->decimal($datos, "stock_minimo");
    $reorden = $this->decimal($datos, "punto_reorden");
    $maximoTexto = $this->texto($datos, "stock_maximo");
    $maximo = $maximoTexto === "" ? null : floatval($maximoTexto);
    if ($minimo < 0 || $reorden < 0 || ($maximo !== null && $maximo < 0)) {
      return "Stock mínimo, reorden y máximo no pueden ser negativos";
    }
    if ($maximo !== null && ($maximo < $minimo || $maximo < $reorden)) {
      return "Stock máximo debe ser mayor o igual que mínimo y reorden";
    }
    if ($reorden > 0 && $minimo > 0 && $reorden < $minimo) {
      return "Punto de reorden debe ser mayor o igual que stock mínimo";
    }
    return true;
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-26
   * Proposito: valida configuracion de cantidad variable en recepcion antes de guardarla.
   * Impacto: Catalogo ERP; prepara contrato para Almacen/Recepciones sin crear unidades operativas falsas.
   */
  private function validarRecepcionVariableSku($datos) {
    $controlaInventario = $this->controlaInventario($this->tipoInventario($datos));
    if (!$controlaInventario && ($this->booleano($datos, "requiere_cantidad_variable_recepcion") || $this->booleano($datos, "requiere_unidades_fisicas_recepcion"))) {
      return "Solo un SKU que controla inventario puede requerir cantidad variable en recepcion";
    }
    if ($this->texto($datos, "tolerancia_recepcion_porcentaje") !== "") {
      $tolerancia = $this->decimal($datos, "tolerancia_recepcion_porcentaje");
      if ($tolerancia < 0 || $tolerancia > 100) {
        return "La tolerancia de recepcion debe estar entre 0 y 100";
      }
    }
    if (!$this->booleano($datos, "requiere_cantidad_variable_recepcion") && $this->texto($datos, "tolerancia_recepcion_porcentaje") !== "") {
      return "La tolerancia solo aplica si se requiere cantidad variable en recepcion";
    }
    return true;
  }

  private function validarSku($datos) {
    foreach (array("sku", "nombre_sku", "id_unidad_base") as $campo) {
      if ($this->texto($datos, $campo) === "" || ($campo === "id_unidad_base" && intval($datos[$campo]) <= 0)) {
        return "Completa los campos obligatorios del SKU";
      }
    }
    if ($this->factorUnidadBaseSku($datos) <= 0) {
      return "El factor de conversion de la unidad base debe ser mayor a cero";
    }
    if ($this->decimal($datos, "precio") < 0 || $this->decimal($datos, "costo_referencia") < 0) {
      return "Precio y costo no pueden ser negativos";
    }
    $controlaInventario = $this->controlaInventario($this->tipoInventario($datos));
    if ($controlaInventario && $this->booleano($datos, "requiere_caducidad") && !$this->booleano($datos, "requiere_lote")) {
      return "Un SKU con caducidad debe controlar lote";
    }
    $validacionReorden = $this->validarReordenSku($datos);
    if ($validacionReorden !== true) {
      return $validacionReorden;
    }
    $validacionRecepcion = $this->validarRecepcionVariableSku($datos);
    return $validacionRecepcion === true ? $this->validarFiscalSku($datos) : $validacionRecepcion;
  }

  private function factorUnidadBaseSku($datos) {
    return $this->texto($datos, "factor_unidad_base") === "" ? 1 : $this->decimal($datos, "factor_unidad_base");
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-24
   * Proposito: persiste datos fiscales capturados aunque esten parciales.
   * Impacto: Catalogo ERP; las auditorias siguen marcando fiscal incompleto hasta completar el contrato fiscal.
   */
  private function guardarFiscalSku($db, $idSku, $datos) {
    if (!$this->fiscalCapturado($datos)) {
      return;
    }

    $iva = $this->porcentajeFiscal($datos, "iva_porcentaje");
    $ieps = $this->porcentajeFiscal($datos, "ieps_porcentaje");
    $stmt = $db->prepare("INSERT INTO erp_catalogo_sku_impuestos
      (id_sku, clave_producto_sat, clave_unidad_sat, objeto_impuesto, iva_porcentaje, ieps_porcentaje, incluye_impuestos)
      VALUES (:sku, :producto_sat, :unidad_sat, :objeto, :iva, :ieps, :incluye)
      ON DUPLICATE KEY UPDATE clave_producto_sat=VALUES(clave_producto_sat), clave_unidad_sat=VALUES(clave_unidad_sat),
        objeto_impuesto=VALUES(objeto_impuesto), iva_porcentaje=VALUES(iva_porcentaje), ieps_porcentaje=VALUES(ieps_porcentaje),
        incluye_impuestos=VALUES(incluye_impuestos), fecha_actualizacion=CURRENT_TIMESTAMP");
    $stmt->execute(array(
      ":sku" => intval($idSku),
      ":producto_sat" => $this->texto($datos, "clave_producto_sat"),
      ":unidad_sat" => $this->texto($datos, "clave_unidad_sat"),
      ":objeto" => $this->texto($datos, "objeto_impuesto"),
      ":iva" => $iva === null ? 0 : $iva,
      ":ieps" => $ieps === null ? 0 : $ieps,
      ":incluye" => $this->booleano($datos, "incluye_impuestos")
    ));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-26
   * Proposito: guarda reglas de recepcion variable solo cuando el esquema opcional ya fue aplicado.
   * Impacto: Catalogo ERP; deja configuracion lista para Almacen/Recepciones sin romper BD previa al DDL.
   */
  private function guardarRecepcionVariableSku($db, $idSku, $datos, $controlaInventario) {
    if (!$this->esquemaRecepcionVariableDisponible($db)) {
      return;
    }
    $requiereCantidad = $controlaInventario ? $this->booleano($datos, "requiere_cantidad_variable_recepcion") : 0;
    $requiereFisicas = $controlaInventario ? $this->booleano($datos, "requiere_unidades_fisicas_recepcion") : 0;
    $tolerancia = ($controlaInventario && $requiereCantidad && $this->texto($datos, "tolerancia_recepcion_porcentaje") !== "")
      ? $this->decimal($datos, "tolerancia_recepcion_porcentaje")
      : null;
    $nota = $controlaInventario ? $this->texto($datos, "nota_recepcion_variable") : "";
    $stmt = $db->prepare("UPDATE erp_catalogo_sku_reglas_inventario
      SET requiere_cantidad_variable_recepcion=:cantidad,
        requiere_unidades_fisicas_recepcion=:fisicas,
        tolerancia_recepcion_porcentaje=:tolerancia,
        nota_recepcion_variable=:nota,
        fecha_actualizacion=CURRENT_TIMESTAMP
      WHERE id_sku=:sku");
    $stmt->execute(array(
      ":cantidad" => $requiereCantidad,
      ":fisicas" => $requiereFisicas,
      ":tolerancia" => $tolerancia,
      ":nota" => $nota,
      ":sku" => intval($idSku)
    ));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-24
   * Proposito: permite captura fiscal progresiva y valida solo valores presentes.
   * Impacto: Catalogo ERP; fiscal incompleto queda como alerta de calidad, no como bloqueo de guardado.
   */
  private function validarFiscalSku($datos) {
    if (!$this->fiscalCapturado($datos)) {
      return true;
    }

    $objeto = $this->texto($datos, "objeto_impuesto");
    if ($objeto !== "" && !in_array($objeto, array("01", "02", "03"), true)) {
      return "Selecciona un objeto de impuesto SAT valido";
    }

    $iva = $this->porcentajeFiscal($datos, "iva_porcentaje");
    $ieps = $this->porcentajeFiscal($datos, "ieps_porcentaje");
    if (($this->texto($datos, "iva_porcentaje") !== "" && $iva === null) || ($this->texto($datos, "ieps_porcentaje") !== "" && $ieps === null)) {
      return "IVA e IEPS deben ser porcentajes validos";
    }
    if (($iva !== null && $iva < 0) || ($ieps !== null && $ieps < 0)) {
      return "IVA e IEPS deben ser porcentajes mayores o iguales a cero";
    }

    if ($objeto === "01" && (($iva !== null && $iva > 0) || ($ieps !== null && $ieps > 0))) {
      return "Un SKU no objeto de impuesto debe tener IVA e IEPS en cero";
    }

    return true;
  }

  private function fiscalCapturado($datos) {
    foreach (array("clave_producto_sat", "clave_unidad_sat", "objeto_impuesto", "iva_porcentaje", "ieps_porcentaje") as $campo) {
      if ($this->texto($datos, $campo) !== "") {
        return true;
      }
    }
    return $this->booleano($datos, "incluye_impuestos") === 1;
  }

  private function actualizarCodigoBarrasPrincipal($db, $idSku, $codigoBarras) {
    $codigoBarras = trim((string) $codigoBarras);
    $stmt = $db->prepare("SELECT id_sku_codigo, id_sku, tipo_codigo, codigo
      FROM erp_catalogo_sku_codigos
      WHERE estatus='activo' AND codigo=:codigo
      LIMIT 1");
    if ($codigoBarras !== "") {
      $stmt->execute(array(":codigo" => $codigoBarras));
      $existente = $stmt->fetch(PDO::FETCH_ASSOC);
      if ($existente && intval($existente["id_sku"]) !== intval($idSku)) {
        throw new Exception("El código de barras ya pertenece a otro SKU");
      }
    }

    $db->prepare("UPDATE erp_catalogo_sku_codigos
      SET es_principal=0, estatus='historico', fecha_actualizacion=CURRENT_TIMESTAMP
      WHERE id_sku=:sku AND tipo_codigo IN ('codigo_barras','barras') AND es_principal=1
        AND (:codigo='' OR NOT (tipo_codigo='codigo_barras' AND codigo=:codigo))")
      ->execute(array(":sku" => intval($idSku), ":codigo" => $codigoBarras));

    if ($codigoBarras === "") {
      return;
    }

    $stmt = $db->prepare("INSERT INTO erp_catalogo_sku_codigos
      (id_sku, tipo_codigo, codigo, es_principal, estatus)
      VALUES (:sku, 'codigo_barras', :codigo, 1, 'activo')
      ON DUPLICATE KEY UPDATE id_sku=VALUES(id_sku), es_principal=1,
        estatus='activo', fecha_actualizacion=CURRENT_TIMESTAMP");
    $stmt->execute(array(":sku" => intval($idSku), ":codigo" => $codigoBarras));
  }

  private function texto($datos, $campo, $default = "") {
    return isset($datos[$campo]) ? trim((string) $datos[$campo]) : $default;
  }

  /**
   * IA: GPT-5 Codex
   * Fecha: 2026-07-12
   * Proposito: normaliza texto historico con mojibake conocido antes de reconstruir taxonomias ERP desde tablas ecom_*.
   * Impacto: Catalogo ERP; previene que sincronizaciones heredadas vuelvan a escribir categorias maestras con acentos dañados.
   * Contrato: recibe texto plano, devuelve texto recortado; no consulta ni modifica BD.
   */
  private function normalizarTextoHistoricoCatalogo($texto) {
    $texto = trim((string) $texto);
    return strtr($texto, array(
      "\xE2\x94\x9C\xC2\xA1" => "\xC3\xAD",
      "\xE2\x94\x9C\xC3\xAD" => "\xC3\xA1",
      "\xE2\x94\x9C\xE2\x94\x82" => "\xC3\xB3",
      "\xE2\x94\x9C\xC2\xAE" => "\xC3\xA9",
      "\xE2\x94\x9C\xE2\x95\x91" => "\xC3\xBA",
      "\xE2\x94\x9C\xE2\x96\x92" => "\xC3\xB1",
      "\xE2\x94\x9C\xC3\xBC" => "\xC3\x81",
      "\xE2\x94\x9C\xC3\xAC" => "\xC3\x8D",
      "\xE2\x94\x9C\xC3\xB4" => "\xC3\x93",
      "\xE2\x94\x9C\xC3\xAB" => "\xC3\x89",
      "\xE2\x94\x9C\xC3\x9C" => "\xC3\x9A",
      "\xE2\x94\x9C\xC3\xA6" => "\xC3\x91",
      "\xE2\x94\x9C\xC3\xA2\xE2\x94\xAC\xC3\xAD" => "\xC3\xA1",
      "\xE2\x94\x9C\xC3\xA2\xE2\x94\xAC\xC2\xA1" => "\xC3\xAD",
      "\xE2\x94\x9C\xC3\xA2\xE2\x94\xAC\xE2\x94\x82" => "\xC3\xB3",
      "\xE2\x94\x9C\xC3\xA2\xE2\x94\xAC\xC2\xAE" => "\xC3\xA9",
      "\xE2\x94\x9C\xC3\xA2\xE2\x94\xAC\xE2\x95\x91" => "\xC3\xBA",
      "\xE2\x94\x9C\xC3\xA2\xE2\x94\xAC\xE2\x96\x92" => "\xC3\xB1",
      "\xE2\x94\x9C\xC3\xA2\xE2\x94\xAC\xC3\xBC" => "\xC3\x81",
      "\xE2\x94\x9C\xC3\xA2\xE2\x94\xAC\xC3\xAC" => "\xC3\x8D",
      "\xE2\x94\x9C\xC3\xA2\xE2\x94\xAC\xC3\xB4" => "\xC3\x93",
      "\xE2\x94\x9C\xC3\xA2\xE2\x94\xAC\xC3\xAB" => "\xC3\x89",
      "\xE2\x94\x9C\xC3\xA2\xE2\x94\xAC\xC3\x9C" => "\xC3\x9A",
      "\xE2\x94\x9C\xC3\xA2\xE2\x94\xAC\xC3\xA6" => "\xC3\x91"
    ));
  }

  private function decimal($datos, $campo) {
    return isset($datos[$campo]) && is_numeric($datos[$campo]) ? floatval($datos[$campo]) : 0;
  }

  private function porcentajeFiscal($datos, $campo) {
    if (!isset($datos[$campo]) || trim((string) $datos[$campo]) === "" || !is_numeric($datos[$campo])) {
      return null;
    }
    $valor = floatval($datos[$campo]);
    if ($valor > 0 && $valor <= 1) {
      $valor = $valor * 100;
    }
    return $valor;
  }

  private function booleano($datos, $campo) {
    return isset($datos[$campo]) && in_array((string) $datos[$campo], array("1", "true", "on"), true) ? 1 : 0;
  }

  /**
   * IA: Codex GPT-5 | Fecha: 2026-06-24
   * Proposito: normaliza el estatus de vida del producto maestro sin mezclarlo con preparacion operativa.
   * Impacto: Catalogo ERP; fusionado queda reservado al flujo de fusion y no se captura manualmente.
   */
  private function estatusProductoMaestro($datos) {
    return $this->opcion($datos, "estatus", array("borrador", "en_revision", "activo", "inactivo", "descontinuado"), "activo");
  }

  /**
   * IA: Codex GPT-5 | Fecha: 2026-06-24
   * Proposito: normaliza el estatus de vida del SKU sin convertirlo en listo para comprar/vender.
   * Impacto: Catalogo ERP; permite capturar SKUs en revision o descontinuados y reserva fusionado para fusiones.
   */
  private function estatusSkuMaestro($datos) {
    return $this->opcion($datos, "estatus", array("borrador", "en_revision", "activo", "inactivo", "descontinuado"), "activo");
  }

  private function opcion($datos, $campo, $permitidas, $default) {
    $valor = $this->texto($datos, $campo, $default);
    return in_array($valor, $permitidas, true) ? $valor : $default;
  }

  private function codigoDesdeTexto($texto, $prefijo) {
    $normalizado = strtoupper(preg_replace('/[^A-Za-z0-9]+/', '-', $texto));
    return $prefijo . "-" . trim($normalizado, "-") . "-" . substr(strtoupper(bin2hex(random_bytes(3))), 0, 6);
  }

  private function respuesta($error, $tipo, $mensaje, $depurar = array()) {
    return array("error" => $error, "tipo" => $tipo, "mensaje" => $mensaje, "depurar" => $depurar);
  }
}
