<?php

class CatalogoErpMigracionEcommerce extends CRUD {

  private $db;
  private $unidadPieza = 0;
  private $marcas = array();
  private $categorias = array();
  private $atributos = array();
  private $mapaMarcas = array();
  private $mapaCategorias = array();
  private $mapaAtributos = array();
  private $mapaFiscales = array();
  private $codigosBarrasUnicos = array();

  public function __construct() {
    parent::__construct();
    $this->db = $this->getConexion();
  }

  public function ejecutar() {
    $resumen = array(
      "productos_ecom_total" => 0,
      "productos_maestros_creados" => 0,
      "skus_creados" => 0,
      "grupos_variantes_creados" => 0,
      "productos_independientes_creados" => 0,
      "productos_revision" => 0,
      "omitidos_ya_migrados" => 0
    );

    try {
      $this->db->beginTransaction();
      $this->prepararMapas();
      $productos = $this->cargarProductos();
      $resumen["productos_ecom_total"] = count($productos);
      $membresias = $this->cargarMembresias();
      $conteoSku = $this->conteoSku($productos);
      $grupos = array();
      $conteoMembresia = array();

      foreach ($membresias as $membresia) {
        $idProducto = intval($membresia["id_producto"]);
        $idVariante = intval($membresia["id_variante"]);
        $grupos[$idVariante][] = $idProducto;
        $conteoMembresia[$idProducto] = isset($conteoMembresia[$idProducto]) ? $conteoMembresia[$idProducto] + 1 : 1;
      }

      $procesados = array();
      foreach ($grupos as $idVariante => $miembros) {
        $motivos = array();
        foreach ($miembros as $idProducto) {
          $sku = $this->skuLimpio($productos[$idProducto]["sku"]);
          if ($sku === "") {
            $motivos[] = "sku_invalido";
          } elseif (isset($conteoSku[$this->skuClave($sku)]) && $conteoSku[$this->skuClave($sku)] > 1) {
            $motivos[] = "sku_duplicado";
          }
          if (isset($conteoMembresia[$idProducto]) && $conteoMembresia[$idProducto] > 1) {
            $motivos[] = "producto_en_multiples_grupos";
          }
        }
        $motivos = array_values(array_unique($motivos));
        if (!empty($motivos)) {
          foreach ($miembros as $idProducto) {
            $this->registrarIncidencia($productos[$idProducto], $idVariante, "grupo_variante_ambiguo", array(
              "motivos_grupo" => $motivos,
              "miembros" => $miembros
            ));
            $procesados[$idProducto] = true;
          }
          continue;
        }
        $resultado = $this->migrarGrupo($idVariante, $miembros, $productos);
        $resumen["productos_maestros_creados"] += $resultado["maestros"];
        $resumen["skus_creados"] += $resultado["skus"];
        $resumen["grupos_variantes_creados"] += $resultado["maestros"];
        $resumen["omitidos_ya_migrados"] += $resultado["omitidos"];
        foreach ($miembros as $idProducto) {
          $procesados[$idProducto] = true;
        }
      }

      foreach ($productos as $idProducto => $producto) {
        if (isset($procesados[$idProducto])) {
          continue;
        }
        $sku = $this->skuLimpio($producto["sku"]);
        if ($sku === "") {
          $this->registrarIncidencia($producto, null, "sku_invalido", array("sku_original" => $producto["sku"]));
          continue;
        }
        if (isset($conteoSku[$this->skuClave($sku)]) && $conteoSku[$this->skuClave($sku)] > 1) {
          $this->registrarIncidencia($producto, null, "sku_duplicado", array("sku" => $sku, "repeticiones" => $conteoSku[$this->skuClave($sku)]));
          continue;
        }
        $resultado = $this->migrarGrupo(null, array($idProducto), $productos);
        $resumen["productos_maestros_creados"] += $resultado["maestros"];
        $resumen["skus_creados"] += $resultado["skus"];
        $resumen["productos_independientes_creados"] += $resultado["maestros"];
        $resumen["omitidos_ya_migrados"] += $resultado["omitidos"];
      }

      $resumen["productos_revision"] = intval($this->db->query("SELECT COUNT(*) FROM erp_catalogo_migracion_ecom_incidencias WHERE estatus='pendiente'")->fetchColumn());
      $this->db->commit();
      return $this->respuesta(false, "success", "Migracion conservadora de ecommerce a catalogo ERP completada", $resumen);
    } catch (Exception $e) {
      if ($this->db->inTransaction()) {
        $this->db->rollBack();
      }
      return $this->respuesta(true, "danger", $e->getMessage(), $resumen);
    }
  }

  public function consultarIncidencia($idIncidencia) {
    try {
      $stmt = $this->db->prepare("SELECT * FROM erp_catalogo_migracion_ecom_incidencias WHERE id_incidencia=:id");
      $stmt->execute(array(":id" => intval($idIncidencia)));
      $incidencia = $stmt->fetch(PDO::FETCH_ASSOC);
      if (!$incidencia) {
        throw new Exception("La incidencia no existe");
      }

      if ($incidencia["id_variante_ecom"] !== null) {
        $stmt = $this->db->prepare("SELECT id_producto_ecom FROM erp_catalogo_migracion_ecom_incidencias WHERE id_variante_ecom=:variante AND estatus='pendiente' ORDER BY id_producto_ecom");
        $stmt->execute(array(":variante" => intval($incidencia["id_variante_ecom"])));
      } elseif (trim((string) $incidencia["sku"]) !== "") {
        $stmt = $this->db->prepare("SELECT id_producto_ecom FROM erp_catalogo_migracion_ecom_incidencias WHERE LOWER(TRIM(sku))=LOWER(TRIM(:sku)) AND estatus='pendiente' ORDER BY id_producto_ecom");
        $stmt->execute(array(":sku" => $incidencia["sku"]));
      } else {
        $stmt = $this->db->prepare("SELECT id_producto_ecom FROM erp_catalogo_migracion_ecom_incidencias WHERE id_incidencia=:id");
        $stmt->execute(array(":id" => intval($idIncidencia)));
      }
      $ids = array_map("intval", $stmt->fetchAll(PDO::FETCH_COLUMN));
      if (empty($ids)) {
        $ids = array(intval($incidencia["id_producto_ecom"]));
      }
      $lista = implode(",", $ids);
      $prefijo = $this->prefijoEcommerceParaProductos($ids);
      $fuenteEcommerce = $prefijo === "" ? "local" : "staging";
      $productos = $this->db->query("SELECT p.id_producto, p.sku, p.nombre, p.codigo_barras, p.precio_base, p.minima_existencia, p.maxima_existencia, p.estatus,
        GROUP_CONCAT(DISTINCT CONCAT(a.tipo_atributo, ': ', a.valor_atributo, IF(COALESCE(a.unidad_medida_atributo, '')='', '', CONCAT(' ', a.unidad_medida_atributo)), IF(COALESCE(a.descripcion_atributo, '')='', '', CONCAT(' ', a.descripcion_atributo))) ORDER BY a.tipo_atributo SEPARATOR ' | ') atributos,
        img.url_imagen
        FROM " . $prefijo . "ecom_productos p
        LEFT JOIN " . $prefijo . "ecom_productos_atributos pa ON pa.id_producto=p.id_producto
        LEFT JOIN " . $prefijo . "ecom_atributos a ON a.id_atributo=pa.id_atributo
        LEFT JOIN " . $prefijo . "ecom_productos_imagenes img ON img.id_producto=p.id_producto AND img.tipo_imagen='portada'
        WHERE p.id_producto IN (" . $lista . ")
        GROUP BY p.id_producto, p.sku, p.nombre, p.codigo_barras, p.precio_base, p.minima_existencia, p.maxima_existencia, p.estatus, img.url_imagen
        ORDER BY p.id_producto")->fetchAll(PDO::FETCH_ASSOC);
      foreach ($productos as &$producto) {
        $producto["fuente_ecommerce"] = $fuenteEcommerce;
        $base = trim((string) $producto["sku"]);
        $producto["sku_sugerido"] = $base === "" || strtoupper($base) === "NULL"
          ? "ECOM-" . intval($producto["id_producto"])
          : $base . "-" . intval($producto["id_producto"]);
        $producto["nombre_sugerido"] = $this->normalizarNombreLegible($producto["nombre"]);
        $producto["erp_vinculado"] = $this->buscarVinculoEcommerce($producto["id_producto"]);
      }
      unset($producto);
      return $this->respuesta(false, "success", "Detalle de incidencia consultado", array(
        "incidencia" => $incidencia,
        "productos" => $productos,
        "fuente_ecommerce" => $fuenteEcommerce,
        "resolucion_permitida" => $fuenteEcommerce === "local"
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "warning", $e->getMessage());
    }
  }

  public function resolverIncidencia($datos) {
    $idIncidencia = intval(isset($datos["id_incidencia"]) ? $datos["id_incidencia"] : 0);
    $nombreMaestro = trim(isset($datos["nombre_maestro"]) ? $datos["nombre_maestro"] : "");
    $codigoMaestro = trim(isset($datos["codigo_maestro"]) ? $datos["codigo_maestro"] : "");
    $skusCorregidos = isset($datos["skus"]) && is_array($datos["skus"]) ? $datos["skus"] : array();
    $productosIncluidos = isset($datos["productos_incluidos"]) && is_array($datos["productos_incluidos"]) ? $datos["productos_incluidos"] : array();
    $skusCorregidos = $this->filtrarPorProductosIncluidos($skusCorregidos, $productosIncluidos);
    if ($idIncidencia <= 0 || $nombreMaestro === "" || empty($skusCorregidos)) {
      return $this->respuesta(true, "warning", "Completa el producto maestro y sus SKU");
    }

    try {
      $this->db->beginTransaction();
      $detalle = $this->consultarIncidencia($idIncidencia);
      if ($detalle["error"]) {
        throw new Exception($detalle["mensaje"]);
      }
      if (isset($detalle["depurar"]["resolucion_permitida"]) && !$detalle["depurar"]["resolucion_permitida"]) {
        throw new Exception("Esta incidencia usa productos historicos de ecommerce; no se puede resolver creando producto ERP. Descarta, conserva como historial o reabre contra una fuente vigente.");
      }
      $productosDetalle = $detalle["depurar"]["productos"];
      $permitidos = array();
      foreach ($productosDetalle as $producto) {
        $permitidos[intval($producto["id_producto"])] = true;
      }
      $productos = $this->cargarProductos();
      $miembros = array();
      $skus = array();
      foreach ($skusCorregidos as $idProducto => $sku) {
        $idProducto = intval($idProducto);
        $sku = $this->skuLimpio($sku);
        if (!isset($permitidos[$idProducto])) {
          throw new Exception("El producto ecommerce " . $idProducto . " no pertenece al grupo de esta incidencia");
        }
        if (!isset($productos[$idProducto])) {
          throw new Exception("El producto ecommerce " . $idProducto . " ya no existe en la fuente vigente; descarta, conserva como historial o reabre la incidencia contra una fuente vigente.");
        }
        if ($sku === "") {
          throw new Exception("Todos los productos seleccionados necesitan un SKU ERP");
        }
        $clave = $this->skuClave($sku);
        if (isset($skus[$clave])) {
          throw new Exception("Los SKU corregidos deben ser únicos");
        }
        $stmt = $this->db->prepare("SELECT id_sku FROM erp_catalogo_skus WHERE LOWER(TRIM(sku))=LOWER(TRIM(:sku)) LIMIT 1");
        $stmt->execute(array(":sku" => $sku));
        if ($stmt->fetchColumn()) {
          throw new Exception("El SKU " . $sku . " ya existe en el catálogo ERP");
        }
        $skus[$clave] = $sku;
        $miembros[] = $idProducto;
      }
      if (empty($miembros)) {
        throw new Exception("Selecciona al menos un producto");
      }
      $this->prepararMapas();
      if (!empty($this->vinculosExistentes($miembros))) {
        throw new Exception("Uno de los productos ya está vinculado al catálogo ERP");
      }

      $principal = $miembros[0];
      $codigo = $codigoMaestro !== "" ? $codigoMaestro : "ECOM-REV-" . $idIncidencia;
      $stmt = $this->db->prepare("SELECT id_producto_erp FROM erp_catalogo_productos WHERE codigo_producto=:codigo LIMIT 1");
      $stmt->execute(array(":codigo" => $codigo));
      if ($stmt->fetchColumn()) {
        throw new Exception("El código del producto maestro ya existe");
      }
      $stmt = $this->db->prepare("INSERT INTO erp_catalogo_productos
        (codigo_producto, nombre, descripcion, tipo_producto, id_marca_erp, maneja_variantes, estatus)
        VALUES (:codigo, :nombre, :descripcion, :tipo, :marca, :variantes, :estatus)");
      $stmt->execute(array(
        ":codigo" => $codigo,
        ":nombre" => substr($nombreMaestro, 0, 255),
        ":descripcion" => $productos[$principal]["descripcion"],
        ":tipo" => strtolower(trim($productos[$principal]["tipo"])) === "servicio" ? "servicio" : "producto",
        ":marca" => $this->resolverMarca($principal) ?: null,
        ":variantes" => count($miembros) > 1 ? 1 : 0,
        ":estatus" => $this->estatusGrupo($miembros, $productos)
      ));
      $idProductoErp = intval($this->db->lastInsertId());
      $this->guardarCategorias($idProductoErp, $miembros);
      foreach ($miembros as $idProductoEcom) {
        $this->migrarSku($idProductoErp, $productos[$idProductoEcom], $skus[$this->skuClave($skusCorregidos[$idProductoEcom])]);
      }
      $this->db->commit();
      return $this->respuesta(false, "success", "Incidencia resuelta y producto ERP creado", array("id_producto_erp" => $idProductoErp, "skus_creados" => count($miembros)));
    } catch (Exception $e) {
      if ($this->db->inTransaction()) {
        $this->db->rollBack();
      }
      return $this->respuesta(true, "warning", $e->getMessage());
    }
  }

  public function vincularIncidenciaProductoExistente($datos) {
    $idIncidencia = intval(isset($datos["id_incidencia"]) ? $datos["id_incidencia"] : 0);
    $idProductoErp = intval(isset($datos["id_producto_erp_existente"]) ? $datos["id_producto_erp_existente"] : 0);
    $skusCorregidos = isset($datos["skus"]) && is_array($datos["skus"]) ? $datos["skus"] : array();
    $skusExistentes = isset($datos["skus_existentes"]) && is_array($datos["skus_existentes"]) ? $datos["skus_existentes"] : array();
    $productosIncluidos = isset($datos["productos_incluidos"]) && is_array($datos["productos_incluidos"]) ? $datos["productos_incluidos"] : array();
    $skusCorregidos = $this->filtrarPorProductosIncluidos($skusCorregidos, $productosIncluidos);
    $skusExistentes = $this->filtrarPorProductosIncluidos($skusExistentes, $productosIncluidos);
    if ($idIncidencia <= 0 || $idProductoErp <= 0) {
      return $this->respuesta(true, "warning", "Selecciona incidencia y producto ERP destino");
    }

    try {
      $this->db->beginTransaction();
      $stmt = $this->db->prepare("SELECT id_producto_erp FROM erp_catalogo_productos WHERE id_producto_erp=:producto AND estatus<>'fusionado' FOR UPDATE");
      $stmt->execute(array(":producto" => $idProductoErp));
      if (!$stmt->fetchColumn()) {
        throw new Exception("El producto ERP destino no existe o esta fusionado");
      }

      $detalle = $this->consultarIncidencia($idIncidencia);
      if ($detalle["error"]) {
        throw new Exception($detalle["mensaje"]);
      }
      if (isset($detalle["depurar"]["resolucion_permitida"]) && !$detalle["depurar"]["resolucion_permitida"]) {
        throw new Exception("Esta incidencia usa productos historicos de ecommerce; no se puede vincular como migracion normal. Descarta, conserva como historial o reabre contra una fuente vigente.");
      }
      $permitidos = array();
      foreach ($detalle["depurar"]["productos"] as $producto) {
        $permitidos[intval($producto["id_producto"])] = true;
      }
      $productos = $this->cargarProductos();
      $ids = array_unique(array_merge(array_keys($skusCorregidos), array_keys($skusExistentes)));
      $miembros = array();
      foreach ($ids as $idProducto) {
        $idProducto = intval($idProducto);
        if (!isset($permitidos[$idProducto])) {
          throw new Exception("El producto ecommerce " . $idProducto . " no pertenece al grupo de esta incidencia");
        }
        if (!isset($productos[$idProducto])) {
          throw new Exception("El producto ecommerce " . $idProducto . " ya no existe en la fuente vigente; descarta, conserva como historial o reabre la incidencia contra una fuente vigente.");
        }
        $miembros[] = $idProducto;
      }
      if (empty($miembros)) {
        throw new Exception("Selecciona al menos un producto ecommerce");
      }
      if (!empty($this->vinculosExistentes($miembros))) {
        throw new Exception("Uno de los productos ya esta vinculado al catalogo ERP");
      }

      $this->prepararMapas();
      $skusNuevos = array();
      $vinculos = 0;
      foreach ($miembros as $idProductoEcom) {
        $idSkuExistente = intval(isset($skusExistentes[$idProductoEcom]) ? $skusExistentes[$idProductoEcom] : 0);
        if ($idSkuExistente > 0) {
          $stmt = $this->db->prepare("SELECT id_sku FROM erp_catalogo_skus WHERE id_sku=:sku AND id_producto_erp=:producto LIMIT 1");
          $stmt->execute(array(":sku" => $idSkuExistente, ":producto" => $idProductoErp));
          if (!$stmt->fetchColumn()) {
            throw new Exception("El SKU ERP seleccionado no pertenece al producto destino");
          }
          $this->vincularEcommerceConSku($idProductoErp, $idSkuExistente, $productos[$idProductoEcom]);
          $vinculos++;
          continue;
        }

        $sku = $this->skuLimpio(isset($skusCorregidos[$idProductoEcom]) ? $skusCorregidos[$idProductoEcom] : "");
        if ($sku === "") {
          throw new Exception("Los productos sin SKU ERP existente necesitan un SKU nuevo");
        }
        $clave = $this->skuClave($sku);
        if (isset($skusNuevos[$clave])) {
          throw new Exception("Los SKU nuevos deben ser unicos");
        }
        $stmt = $this->db->prepare("SELECT id_sku FROM erp_catalogo_skus WHERE LOWER(TRIM(sku))=LOWER(TRIM(:sku)) LIMIT 1");
        $stmt->execute(array(":sku" => $sku));
        if ($stmt->fetchColumn()) {
          throw new Exception("El SKU " . $sku . " ya existe en el catalogo ERP; selecciona ese SKU como existente o usa otro");
        }
        $skusNuevos[$clave] = true;
        $this->migrarSku($idProductoErp, $productos[$idProductoEcom], $sku);
      }

      $stmt = $this->db->prepare("SELECT COUNT(*) FROM erp_catalogo_skus WHERE id_producto_erp=:producto");
      $stmt->execute(array(":producto" => $idProductoErp));
      $this->db->prepare("UPDATE erp_catalogo_productos SET maneja_variantes=:variantes, fecha_actualizacion=CURRENT_TIMESTAMP WHERE id_producto_erp=:producto")
        ->execute(array(":variantes" => intval($stmt->fetchColumn()) > 1 ? 1 : 0, ":producto" => $idProductoErp));

      $this->db->commit();
      return $this->respuesta(false, "success", "Incidencia vinculada al producto ERP existente", array(
        "id_producto_erp" => $idProductoErp,
        "skus_creados" => count($skusNuevos),
        "vinculos_existentes" => $vinculos
      ));
    } catch (Exception $e) {
      if ($this->db->inTransaction()) {
        $this->db->rollBack();
      }
      return $this->respuesta(true, "warning", $e->getMessage());
    }
  }

  public function resolverNombreCodificacion($datos) {
    $idIncidencia = intval(isset($datos["id_incidencia"]) ? $datos["id_incidencia"] : 0);
    $idProductoEcom = intval(isset($datos["id_producto_ecom"]) ? $datos["id_producto_ecom"] : 0);
    $nombre = trim(isset($datos["nombre_corregido"]) ? $datos["nombre_corregido"] : "");
    if ($idIncidencia <= 0 || $idProductoEcom <= 0 || $nombre === "") {
      return $this->respuesta(true, "warning", "Completa el nombre corregido");
    }
    try {
      $this->db->beginTransaction();
      $stmt = $this->db->prepare("SELECT id_producto_erp, id_sku FROM erp_catalogo_canales_vinculos WHERE canal='ecommerce' AND id_externo=:externo LIMIT 1");
      $stmt->execute(array(":externo" => (string) $idProductoEcom));
      $vinculo = $stmt->fetch(PDO::FETCH_ASSOC);
      if (!$vinculo) {
        throw new Exception("El producto aún no está vinculado al catálogo ERP");
      }
      $stmt = $this->db->prepare("UPDATE erp_catalogo_productos SET nombre=:nombre, estatus='activo', fecha_actualizacion=CURRENT_TIMESTAMP WHERE id_producto_erp=:producto AND estatus='borrador'");
      $stmt->execute(array(":nombre" => substr($nombre, 0, 255), ":producto" => intval($vinculo["id_producto_erp"])));
      $stmt = $this->db->prepare("UPDATE erp_catalogo_skus SET nombre=:nombre, estatus='activo', fecha_actualizacion=CURRENT_TIMESTAMP WHERE id_sku=:sku AND estatus='borrador'");
      $stmt->execute(array(":nombre" => substr($nombre, 0, 255), ":sku" => intval($vinculo["id_sku"])));
      $stmt = $this->db->prepare("UPDATE erp_catalogo_migracion_ecom_incidencias SET estatus='resuelta', fecha_actualizacion=CURRENT_TIMESTAMP WHERE id_incidencia=:incidencia AND id_producto_ecom=:producto");
      $stmt->execute(array(":incidencia" => $idIncidencia, ":producto" => $idProductoEcom));
      $this->db->commit();
      return $this->respuesta(false, "success", "Nombre corregido y producto activado", array("id_producto_erp" => intval($vinculo["id_producto_erp"]), "id_sku" => intval($vinculo["id_sku"])));
    } catch (Exception $e) {
      if ($this->db->inTransaction()) {
        $this->db->rollBack();
      }
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  public function descartarIncidencia($datos) {
    $idIncidencia = intval(isset($datos["id_incidencia"]) ? $datos["id_incidencia"] : 0);
    $motivo = trim(isset($datos["motivo_descarte"]) ? $datos["motivo_descarte"] : "");
    if ($idIncidencia <= 0) {
      return $this->respuesta(true, "warning", "Selecciona la incidencia a descartar");
    }
    try {
      $this->db->beginTransaction();
      $stmt = $this->db->prepare("SELECT detalle_json FROM erp_catalogo_migracion_ecom_incidencias WHERE id_incidencia=:id FOR UPDATE");
      $stmt->execute(array(":id" => $idIncidencia));
      $detalleJson = $stmt->fetchColumn();
      if ($detalleJson === false) {
        throw new Exception("La incidencia no existe");
      }
      $detalle = json_decode($detalleJson ?: "{}", true);
      if (!is_array($detalle)) {
        $detalle = array("detalle_original" => $detalleJson);
      }
      $detalle["resolucion"] = array(
        "accion" => "descartada",
        "motivo" => $motivo,
        "fecha" => date("Y-m-d H:i:s")
      );
      $stmt = $this->db->prepare("UPDATE erp_catalogo_migracion_ecom_incidencias
        SET estatus='descartada', detalle_json=:detalle, fecha_actualizacion=CURRENT_TIMESTAMP
        WHERE id_incidencia=:id");
      $stmt->execute(array(":detalle" => json_encode($detalle, JSON_UNESCAPED_UNICODE), ":id" => $idIncidencia));
      $this->db->commit();
      return $this->respuesta(false, "success", "Incidencia descartada", array("id_incidencia" => $idIncidencia));
    } catch (Exception $e) {
      if ($this->db->inTransaction()) {
        $this->db->rollBack();
      }
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  private function prepararMapas() {
    $this->unidadPieza = intval($this->db->query("SELECT id_unidad FROM erp_catalogo_unidades WHERE codigo='PZA' LIMIT 1")->fetchColumn());
    if ($this->unidadPieza <= 0) {
      throw new Exception("No existe la unidad base PZA");
    }
    $this->mapaMarcas = $this->mapaSimple("SELECT pm.id_producto, MIN(m.id_marca) id_marca, MIN(m.marca) nombre FROM ecom_productos_marcas pm INNER JOIN ecom_marcas m ON m.id_marca=pm.id_marca GROUP BY pm.id_producto");
    $this->mapaCategorias = $this->mapaMultiple("SELECT pc.id_producto, c.id_categoria, c.categoria nombre FROM ecom_productos_categorias pc INNER JOIN ecom_categorias c ON c.id_categoria=pc.id_categoria ORDER BY pc.id_producto, pc.id_producto_categoria");
    $this->mapaAtributos = $this->mapaMultiple("SELECT pa.id_producto, a.id_atributo, a.tipo_atributo tipo, a.valor_atributo valor, a.unidad_medida_atributo unidad FROM ecom_productos_atributos pa INNER JOIN ecom_atributos a ON a.id_atributo=pa.id_atributo ORDER BY pa.id_producto, a.tipo_atributo, a.id_atributo");
    $this->mapaFiscales = $this->mapaSimple("SELECT * FROM ecom_productos_fiscales");
    $stmt = $this->db->query("SELECT codigo_barras FROM ecom_productos WHERE codigo_barras IS NOT NULL AND TRIM(codigo_barras)<>'' AND TRIM(codigo_barras)<>'NULL' GROUP BY codigo_barras HAVING COUNT(*)=1");
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $codigo) {
      $this->codigosBarrasUnicos[$codigo] = true;
    }
  }

  private function cargarProductos() {
    $productos = array();
    $stmt = $this->db->query("SELECT * FROM ecom_productos ORDER BY id_producto");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $producto) {
      $productos[intval($producto["id_producto"])] = $producto;
    }
    return $productos;
  }

  private function filtrarPorProductosIncluidos($valores, $incluidos) {
    if (empty($incluidos)) {
      return $valores;
    }
    $permitidos = array();
    foreach ($incluidos as $idProducto) {
      $permitidos[intval($idProducto)] = true;
    }
    $filtrados = array();
    foreach ($valores as $idProducto => $valor) {
      if (isset($permitidos[intval($idProducto)])) {
        $filtrados[$idProducto] = $valor;
      }
    }
    return $filtrados;
  }

  private function cargarMembresias() {
    return $this->db->query("SELECT id_variante, id_producto, principal FROM ecom_productos_variantes ORDER BY id_variante, principal DESC, id_producto")->fetchAll(PDO::FETCH_ASSOC);
  }

  private function conteoSku($productos) {
    $conteo = array();
    foreach ($productos as $producto) {
      $sku = $this->skuLimpio($producto["sku"]);
      if ($sku !== "") {
        $clave = $this->skuClave($sku);
        $conteo[$clave] = isset($conteo[$clave]) ? $conteo[$clave] + 1 : 1;
      }
    }
    return $conteo;
  }

  private function migrarGrupo($idVariante, $miembros, $productos) {
    $vinculos = $this->vinculosExistentes($miembros);
    if (count($vinculos) === count($miembros)) {
      return array("maestros" => 0, "skus" => 0, "omitidos" => count($miembros));
    }
    if (!empty($vinculos)) {
      foreach ($miembros as $idProducto) {
        $this->registrarIncidencia($productos[$idProducto], $idVariante, "migracion_parcial_previa", array("vinculos_existentes" => $vinculos));
      }
      return array("maestros" => 0, "skus" => 0, "omitidos" => count($miembros));
    }

    $principal = $this->obtenerPrincipal($idVariante, $miembros);
    $productoPrincipal = $productos[$principal];
    $idMarca = $this->resolverMarca($principal);
    $codigo = $idVariante === null ? "ECOM-" . $principal : "ECOM-VAR-" . intval($idVariante);
    $stmt = $this->db->prepare("INSERT INTO erp_catalogo_productos
      (codigo_producto, nombre, descripcion, tipo_producto, id_marca_erp, maneja_variantes, estatus)
      VALUES (:codigo, :nombre, :descripcion, :tipo, :marca, :variantes, :estatus)");
    $stmt->execute(array(
      ":codigo" => $codigo,
      ":nombre" => trim($productoPrincipal["nombre"]),
      ":descripcion" => $productoPrincipal["descripcion"],
      ":tipo" => strtolower(trim($productoPrincipal["tipo"])) === "servicio" ? "servicio" : "producto",
      ":marca" => $idMarca ?: null,
      ":variantes" => count($miembros) > 1 ? 1 : 0,
      ":estatus" => $this->estatusGrupo($miembros, $productos)
    ));
    $idProductoErp = intval($this->db->lastInsertId());
    $this->guardarCategorias($idProductoErp, $miembros);

    foreach ($miembros as $idProductoEcom) {
      $this->migrarSku($idProductoErp, $productos[$idProductoEcom]);
    }
    return array("maestros" => 1, "skus" => count($miembros), "omitidos" => 0);
  }

  private function migrarSku($idProductoErp, $producto, $skuForzado = null) {
    $idProductoEcom = intval($producto["id_producto"]);
    $sku = $skuForzado === null ? $this->skuLimpio($producto["sku"]) : $this->skuLimpio($skuForzado);
    $tipoInventario = strtolower(trim($producto["tipo"])) === "servicio" ? "servicio" : "inventariable";
    $stmt = $this->db->prepare("INSERT INTO erp_catalogo_skus
      (id_producto_erp, sku, nombre, id_unidad_base, tipo_inventario, costo_referencia, estatus)
      VALUES (:producto, :sku, :nombre, :unidad, :tipo, 0, :estatus)");
    $stmt->execute(array(
      ":producto" => $idProductoErp,
      ":sku" => $sku,
      ":nombre" => trim($producto["nombre"]),
      ":unidad" => $this->unidadPieza,
      ":tipo" => $tipoInventario,
      ":estatus" => intval($producto["estatus"]) === 1 ? "activo" : "inactivo"
    ));
    $idSku = intval($this->db->lastInsertId());

    if (isset($this->codigosBarrasUnicos[$producto["codigo_barras"]])) {
      $stmt = $this->db->prepare("INSERT INTO erp_catalogo_sku_codigos (id_sku, tipo_codigo, codigo, es_principal) VALUES (:sku, 'barras', :codigo, 1)");
      $stmt->execute(array(":sku" => $idSku, ":codigo" => $producto["codigo_barras"]));
    }
    $stmt = $this->db->prepare("INSERT INTO erp_catalogo_sku_precios (id_sku, lista_precio, moneda, precio, estatus) VALUES (:sku, 'general', 'MXN', :precio, 'activo')");
    $stmt->execute(array(":sku" => $idSku, ":precio" => max(0, floatval($producto["precio_base"]))));
    $stmt = $this->db->prepare("INSERT INTO erp_catalogo_sku_reglas_inventario
      (id_sku, controla_inventario, permite_existencia_negativa, requiere_lote, requiere_caducidad, requiere_serie, requiere_serie_fabricante, generar_etiqueta_interna, requiere_escaneo_venta, estrategia_salida, stock_minimo, stock_maximo, punto_reorden)
      VALUES (:sku, :controla, 0, 0, 0, 0, 0, 0, 0, 'FIFO', :minimo, :maximo, :reorden)");
    $stmt->execute(array(
      ":sku" => $idSku,
      ":controla" => $tipoInventario === "servicio" ? 0 : 1,
      ":minimo" => max(0, floatval($producto["minima_existencia"])),
      ":maximo" => $producto["maxima_existencia"] === null ? null : max(0, floatval($producto["maxima_existencia"])),
      ":reorden" => max(0, floatval($producto["minima_existencia"]))
    ));
    $this->guardarAtributos($idSku, $idProductoEcom);
    $this->guardarFiscal($idSku, $idProductoEcom);
    $stmt = $this->db->prepare("INSERT INTO erp_catalogo_canales_vinculos
      (id_producto_erp, id_sku, canal, id_externo, sku_externo, sincronizar_catalogo, sincronizar_precio, sincronizar_existencia, estatus)
      VALUES (:producto, :sku, 'ecommerce', :externo, :sku_externo, 0, 0, 0, 'migrado')");
    $stmt->execute(array(":producto" => $idProductoErp, ":sku" => $idSku, ":externo" => (string) $idProductoEcom, ":sku_externo" => $producto["sku"]));
    $stmt = $this->db->prepare("UPDATE erp_catalogo_migracion_ecom_incidencias SET estatus='resuelta', fecha_actualizacion=CURRENT_TIMESTAMP WHERE id_producto_ecom=:producto");
    $stmt->execute(array(":producto" => $idProductoEcom));
  }

  private function vincularEcommerceConSku($idProductoErp, $idSku, $producto) {
    $idProductoEcom = intval($producto["id_producto"]);
    $stmt = $this->db->prepare("INSERT INTO erp_catalogo_canales_vinculos
      (id_producto_erp, id_sku, canal, id_externo, sku_externo, sincronizar_catalogo, sincronizar_precio, sincronizar_existencia, estatus)
      VALUES (:producto, :sku, 'ecommerce', :externo, :sku_externo, 0, 0, 0, 'migrado')
      ON DUPLICATE KEY UPDATE id_producto_erp=VALUES(id_producto_erp), id_sku=VALUES(id_sku), sku_externo=VALUES(sku_externo), estatus='migrado', fecha_actualizacion=CURRENT_TIMESTAMP");
    $stmt->execute(array(":producto" => $idProductoErp, ":sku" => $idSku, ":externo" => (string) $idProductoEcom, ":sku_externo" => $producto["sku"]));
    $stmt = $this->db->prepare("UPDATE erp_catalogo_migracion_ecom_incidencias SET estatus='resuelta', fecha_actualizacion=CURRENT_TIMESTAMP WHERE id_producto_ecom=:producto");
    $stmt->execute(array(":producto" => $idProductoEcom));
  }

  private function guardarAtributos($idSku, $idProductoEcom) {
    if (empty($this->mapaAtributos[$idProductoEcom])) {
      return;
    }
    $valores = array();
    foreach ($this->mapaAtributos[$idProductoEcom] as $atributo) {
      $tipo = trim($atributo["tipo"]) ?: "Atributo ecommerce";
      $valor = trim($atributo["valor"] . " " . $atributo["unidad"]);
      $valores[$tipo][] = $valor;
    }
    foreach ($valores as $tipo => $items) {
      $idAtributo = $this->resolverAtributo($tipo);
      $stmt = $this->db->prepare("INSERT INTO erp_catalogo_sku_atributos (id_sku, id_atributo_erp, valor) VALUES (:sku, :atributo, :valor)");
      $stmt->execute(array(":sku" => $idSku, ":atributo" => $idAtributo, ":valor" => substr(implode(" | ", array_values(array_unique($items))), 0, 500)));
    }
  }

  private function guardarFiscal($idSku, $idProductoEcom) {
    if (empty($this->mapaFiscales[$idProductoEcom])) {
      return;
    }
    $f = $this->mapaFiscales[$idProductoEcom];
    $stmt = $this->db->prepare("INSERT INTO erp_catalogo_sku_impuestos
      (id_sku, clave_producto_sat, clave_unidad_sat, objeto_impuesto, iva_porcentaje, ieps_porcentaje, incluye_impuestos)
      VALUES (:sku, :producto_sat, :unidad_sat, :objeto, :iva, :ieps, :incluye)");
    $stmt->execute(array(
      ":sku" => $idSku, ":producto_sat" => $f["clave_sat"], ":unidad_sat" => $f["clave_unidad_sat"],
      ":objeto" => $f["objeto_impuesto"], ":iva" => $f["porcentaje_iva"], ":ieps" => $f["porcentaje_ieps"], ":incluye" => intval($f["incluye_iva"])
    ));
  }

  private function guardarCategorias($idProductoErp, $miembros) {
    $ids = array();
    foreach ($miembros as $idProductoEcom) {
      foreach (isset($this->mapaCategorias[$idProductoEcom]) ? $this->mapaCategorias[$idProductoEcom] : array() as $categoria) {
        $ids[$this->resolverCategoria($categoria)] = true;
      }
    }
    $principal = 1;
    foreach (array_keys($ids) as $idCategoria) {
      $stmt = $this->db->prepare("INSERT INTO erp_catalogo_producto_categorias (id_producto_erp, id_categoria_erp, es_principal) VALUES (:producto, :categoria, :principal)");
      $stmt->execute(array(":producto" => $idProductoErp, ":categoria" => $idCategoria, ":principal" => $principal));
      $principal = 0;
    }
  }

  private function resolverMarca($idProductoEcom) {
    if (empty($this->mapaMarcas[$idProductoEcom])) {
      return 0;
    }
    $marca = $this->mapaMarcas[$idProductoEcom];
    $idExterno = intval($marca["id_marca"]);
    if (isset($this->marcas[$idExterno])) {
      return $this->marcas[$idExterno];
    }
    $codigo = "ECOM-MAR-" . $idExterno;
    $stmt = $this->db->prepare("INSERT INTO erp_catalogo_marcas (codigo, nombre, estatus) VALUES (:codigo, :nombre, 'activa') ON DUPLICATE KEY UPDATE id_marca_erp=LAST_INSERT_ID(id_marca_erp), nombre=VALUES(nombre)");
    $stmt->execute(array(":codigo" => $codigo, ":nombre" => $marca["nombre"]));
    return $this->marcas[$idExterno] = intval($this->db->lastInsertId());
  }

  private function resolverCategoria($categoria) {
    $idExterno = intval($categoria["id_categoria"]);
    if (isset($this->categorias[$idExterno])) {
      return $this->categorias[$idExterno];
    }
    $codigo = "ECOM-CAT-" . $idExterno;
    $stmt = $this->db->prepare("INSERT INTO erp_catalogo_categorias
      (codigo, nombre, ruta, nivel, tipo_categoria, origen, permite_productos, estatus)
      VALUES (:codigo, :nombre, :ruta, 0, 'legado_canal', 'ecommerce', 1, 'activa')
      ON DUPLICATE KEY UPDATE id_categoria_erp=LAST_INSERT_ID(id_categoria_erp), nombre=VALUES(nombre),
        tipo_categoria='legado_canal', origen='ecommerce', permite_productos=1");
    $stmt->execute(array(":codigo" => $codigo, ":nombre" => $categoria["nombre"], ":ruta" => $categoria["nombre"]));
    return $this->categorias[$idExterno] = intval($this->db->lastInsertId());
  }

  private function resolverAtributo($tipo) {
    $codigo = "ECOM-ATR-" . substr(md5(strtolower(trim($tipo))), 0, 16);
    if (isset($this->atributos[$codigo])) {
      return $this->atributos[$codigo];
    }
    $stmt = $this->db->prepare("INSERT INTO erp_catalogo_atributos (codigo, nombre, tipo_dato, es_variante, estatus) VALUES (:codigo, :nombre, 'texto', 0, 'activo') ON DUPLICATE KEY UPDATE id_atributo_erp=LAST_INSERT_ID(id_atributo_erp), nombre=VALUES(nombre)");
    $stmt->execute(array(":codigo" => $codigo, ":nombre" => substr($tipo, 0, 100)));
    return $this->atributos[$codigo] = intval($this->db->lastInsertId());
  }

  private function registrarIncidencia($producto, $idVariante, $motivo, $detalle) {
    $stmt = $this->db->prepare("INSERT INTO erp_catalogo_migracion_ecom_incidencias
      (id_producto_ecom, id_variante_ecom, sku, nombre_producto, motivo, detalle_json, estatus)
      VALUES (:producto, :variante, :sku, :nombre, :motivo, :detalle, 'pendiente')
      ON DUPLICATE KEY UPDATE id_variante_ecom=VALUES(id_variante_ecom), sku=VALUES(sku), nombre_producto=VALUES(nombre_producto),
      motivo=VALUES(motivo), detalle_json=VALUES(detalle_json), estatus='pendiente', fecha_actualizacion=CURRENT_TIMESTAMP");
    $stmt->execute(array(
      ":producto" => intval($producto["id_producto"]), ":variante" => $idVariante, ":sku" => $producto["sku"],
      ":nombre" => $producto["nombre"], ":motivo" => $motivo, ":detalle" => json_encode($detalle, JSON_UNESCAPED_UNICODE)
    ));
  }

  private function vinculosExistentes($miembros) {
    if (empty($miembros)) {
      return array();
    }
    $sql = "SELECT id_externo FROM erp_catalogo_canales_vinculos WHERE canal='ecommerce' AND id_externo IN (" . implode(",", array_map("intval", $miembros)) . ")";
    return $this->db->query($sql)->fetchAll(PDO::FETCH_COLUMN);
  }

  private function obtenerPrincipal($idVariante, $miembros) {
    if ($idVariante === null) {
      return intval($miembros[0]);
    }
    $stmt = $this->db->prepare("SELECT id_producto FROM ecom_productos_variantes WHERE id_variante=:variante AND principal=1 LIMIT 1");
    $stmt->execute(array(":variante" => intval($idVariante)));
    return intval($stmt->fetchColumn()) ?: intval($miembros[0]);
  }

  private function estatusGrupo($miembros, $productos) {
    foreach ($miembros as $idProducto) {
      if (intval($productos[$idProducto]["estatus"]) === 1) {
        return "activo";
      }
    }
    return "inactivo";
  }

  private function skuLimpio($sku) {
    $sku = trim((string) $sku);
    return $sku === "" || strtoupper($sku) === "NULL" ? "" : $sku;
  }

  private function skuClave($sku) {
    return strtolower($this->skuLimpio($sku));
  }

  private function buscarVinculoEcommerce($idProductoEcom) {
    $stmt = $this->db->prepare("SELECT v.id_producto_erp, v.id_sku, p.codigo_producto, p.nombre producto_erp, p.estatus producto_estatus,
      s.sku sku_erp, s.nombre sku_nombre, s.estatus sku_estatus
      FROM erp_catalogo_canales_vinculos v
      INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=v.id_producto_erp
      INNER JOIN erp_catalogo_skus s ON s.id_sku=v.id_sku
      WHERE v.canal='ecommerce' AND v.id_externo=:externo LIMIT 1");
    $stmt->execute(array(":externo" => (string) intval($idProductoEcom)));
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
  }

  private function normalizarNombreLegible($nombre) {
    $reemplazos = array("¢" => "ó", "£" => "ú", " " => "á", "�" => "");
    $nombre = strtr((string) $nombre, $reemplazos);
    $nombre = preg_replace('/\s+/', ' ', trim($nombre));
    $nombre = preg_replace_callback('/(\d+(?:\.\d+)?)\s*x\s*(\d+(?:\.\d+)?)(?:\s*x\s*(\d+(?:\.\d+)?))?/i', function ($m) {
      return $m[1] . " × " . $m[2] . (isset($m[3]) && $m[3] !== "" ? " × " . $m[3] : "");
    }, $nombre);
    return $nombre;
  }

  private function prefijoEcommerceParaProductos($ids) {
    if (empty($ids) || !$this->tablaExisteEnBase("artianilocal_productivo_staging", "ecom_productos")) {
      return "";
    }
    $lista = implode(",", array_map("intval", $ids));
    $local = intval($this->db->query("SELECT COUNT(*) FROM ecom_productos WHERE id_producto IN (" . $lista . ")")->fetchColumn());
    return $local === count($ids) ? "" : "artianilocal_productivo_staging.";
  }

  private function tablaExisteEnBase($base, $tabla) {
    $stmt = $this->db->prepare("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=:base AND TABLE_NAME=:tabla LIMIT 1");
    $stmt->execute(array(":base" => $base, ":tabla" => $tabla));
    return (bool) $stmt->fetchColumn();
  }

  private function mapaSimple($sql) {
    $mapa = array();
    foreach ($this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $fila) {
      $mapa[intval($fila["id_producto"])] = $fila;
    }
    return $mapa;
  }

  private function mapaMultiple($sql) {
    $mapa = array();
    foreach ($this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $fila) {
      $mapa[intval($fila["id_producto"])][] = $fila;
    }
    return $mapa;
  }

  private function respuesta($error, $tipo, $mensaje, $depurar = null) {
    return array("error" => $error, "tipo" => $tipo, "mensaje" => $mensaje, "depurar" => $depurar);
  }
}
