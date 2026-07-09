<?php

class CatalogoErpEsquema extends DBSchema {

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-26
   * Proposito: declara las tablas maestras que Catalogo ERP debe auditar, incluyendo paquetes planeados.
   * Impacto: Catalogo ERP; solo amplia auditoria/plan de esquema, no ejecuta migraciones por si mismo.
   */
  public function tablasCatalogoErp() {
    return array(
      "erp_catalogo_unidades",
      "erp_catalogo_marcas",
      "erp_catalogo_categorias",
      "erp_catalogo_categoria_equivalencias",
      "erp_catalogo_productos",
      "erp_catalogo_producto_categorias",
      "erp_catalogo_skus",
      "erp_catalogo_sku_codigos",
      "erp_catalogo_atributos",
      "erp_catalogo_sku_atributos",
      "erp_catalogo_sku_proveedores",
      "erp_catalogo_sku_impuestos",
      "erp_catalogo_sku_precios",
      "erp_catalogo_sku_reglas_inventario",
      "erp_catalogo_sku_presentaciones",
      "erp_catalogo_sku_paquetes",
      "erp_catalogo_sku_paquete_componentes",
      "erp_catalogo_sku_paquete_grupos",
      "erp_catalogo_sku_paquete_grupo_opciones",
      "erp_catalogo_canales_vinculos",
      "erp_catalogo_migracion_ecom_incidencias",
      "erp_catalogo_incidencias_calidad",
      "erp_catalogo_imagenes",
      "erp_catalogo_marca_imagenes",
      "erp_catalogo_categoria_imagenes",
      "erp_catalogo_revision_nombres",
      "erp_catalogo_productos_fusiones",
      "erp_catalogo_taxonomias",
      "erp_catalogo_taxonomia_nodos",
      "erp_catalogo_producto_taxonomia_nodos"
    );
  }

  public function auditarCatalogoErp() {
    $auditoria = array();
    $tienePendientes = false;
    $resumen = array(
      "tablas_faltantes" => 0,
      "columnas_faltantes" => 0,
      "indices_faltantes" => 0,
      "indices_con_columnas_distintas" => 0
    );
    $contratos = $this->contratosAuditoriaCatalogoErp();

    foreach ($this->tablasCatalogoErp() as $tabla) {
      $existe = $this->tablaExiste($tabla);
      $reglas = isset($contratos[$tabla]) ? $contratos[$tabla] : array("columnas" => array(), "indices" => array());
      $auditoria[$tabla] = $this->auditarTablaContrato($tabla, $existe, $reglas);

      if (!$existe) {
        $tienePendientes = true;
        $resumen["tablas_faltantes"]++;
        continue;
      }

      $resumen["columnas_faltantes"] += count($auditoria[$tabla]["faltantes"]["columnas"]);
      $resumen["indices_faltantes"] += count($auditoria[$tabla]["faltantes"]["indices"]);
      $resumen["indices_con_columnas_distintas"] += count($auditoria[$tabla]["faltantes"]["indices_columnas"]);
      if (!empty($auditoria[$tabla]["faltantes"]["columnas"]) || !empty($auditoria[$tabla]["faltantes"]["indices"]) || !empty($auditoria[$tabla]["faltantes"]["indices_columnas"])) {
        $tienePendientes = true;
      }
    }

    foreach ($this->contratosOperativosSkuErp() as $tabla => $reglas) {
      $existe = $this->tablaExiste($tabla);
      $auditoria[$tabla] = $this->auditarTablaContrato($tabla, $existe, $reglas);

      if (!$existe) {
        $tienePendientes = true;
        $resumen["tablas_faltantes"]++;
        continue;
      }

      $resumen["columnas_faltantes"] += count($auditoria[$tabla]["faltantes"]["columnas"]);
      $resumen["indices_faltantes"] += count($auditoria[$tabla]["faltantes"]["indices"]);
      $resumen["indices_con_columnas_distintas"] += count($auditoria[$tabla]["faltantes"]["indices_columnas"]);
      if (!empty($auditoria[$tabla]["faltantes"]["columnas"]) || !empty($auditoria[$tabla]["faltantes"]["indices"]) || !empty($auditoria[$tabla]["faltantes"]["indices_columnas"])) {
        $tienePendientes = true;
      }
    }

    return array(
      "error" => false,
      "tipo" => $tienePendientes ? "warning" : "success",
      "mensaje" => $tienePendientes ? "Hay pendientes criticos en el esquema del catalogo maestro ERP" : "El esquema critico del catalogo maestro ERP esta completo",
      "depurar" => array(
        "tiene_pendientes" => $tienePendientes,
        "resumen" => $resumen,
        "auditoria" => $auditoria,
        "aislamiento_ecommerce" => true
      )
    );
  }

  private function auditarTablaContrato($tabla, $existe, $reglas) {
    $resultado = array(
      "existe" => $existe,
      "severidad" => $existe ? "ok" : "critica",
      "impacto" => $existe ? "Tabla disponible para el contrato de Catalogo ERP." : "Tabla faltante; bloquea el contrato de Catalogo ERP para esta entidad.",
      "columnas" => array(),
      "indices" => array(),
      "faltantes" => array(
        "columnas" => array(),
        "indices" => array(),
        "indices_columnas" => array()
      )
    );

    if (!$existe) {
      return $resultado;
    }

    $columnas = isset($reglas["columnas"]) ? $reglas["columnas"] : array();
    foreach ($columnas as $columna => $meta) {
      if (is_int($columna)) {
        $columna = $meta;
        $meta = array();
      }
      $existeColumna = $this->columnaExiste($tabla, $columna);
      $resultado["columnas"][$columna] = array(
        "existe" => $existeColumna,
        "severidad" => isset($meta["severidad"]) ? $meta["severidad"] : "media",
        "impacto" => isset($meta["impacto"]) ? $meta["impacto"] : "Columna requerida por el contrato de Catalogo ERP."
      );
      if (!$existeColumna) {
        $resultado["faltantes"]["columnas"][$columna] = $resultado["columnas"][$columna];
        $resultado["severidad"] = $this->severidadMayor($resultado["severidad"], $resultado["columnas"][$columna]["severidad"]);
      }
    }

    $indices = isset($reglas["indices"]) ? $reglas["indices"] : array();
    foreach ($indices as $indice => $meta) {
      if (is_int($indice)) {
        $indice = $meta;
        $meta = array();
      }
      $existeIndice = $this->indiceExiste($tabla, $indice);
      $columnasEsperadas = isset($meta["columnas"]) ? $meta["columnas"] : array();
      $columnasActuales = $existeIndice ? $this->columnasIndice($tabla, $indice) : array();
      $columnasCorrectas = empty($columnasEsperadas) || $columnasActuales === $columnasEsperadas;
      $resultado["indices"][$indice] = array(
        "existe" => $existeIndice,
        "columnas_esperadas" => $columnasEsperadas,
        "columnas_actuales" => $columnasActuales,
        "columnas_correctas" => $columnasCorrectas,
        "severidad" => isset($meta["severidad"]) ? $meta["severidad"] : "media",
        "impacto" => isset($meta["impacto"]) ? $meta["impacto"] : "Indice requerido por el contrato de Catalogo ERP."
      );
      if (!$existeIndice) {
        $resultado["faltantes"]["indices"][$indice] = $resultado["indices"][$indice];
        $resultado["severidad"] = $this->severidadMayor($resultado["severidad"], $resultado["indices"][$indice]["severidad"]);
      } elseif (!$columnasCorrectas) {
        $resultado["faltantes"]["indices_columnas"][$indice] = $resultado["indices"][$indice];
        $resultado["severidad"] = $this->severidadMayor($resultado["severidad"], $resultado["indices"][$indice]["severidad"]);
      }
    }

    if (!empty($resultado["faltantes"]["columnas"]) || !empty($resultado["faltantes"]["indices"]) || !empty($resultado["faltantes"]["indices_columnas"])) {
      $resultado["impacto"] = "Tabla existe, pero tiene faltantes que pueden romper busquedas, integraciones o validaciones del Catalogo ERP.";
    }

    return $resultado;
  }

  private function severidadMayor($actual, $nueva) {
    $orden = array("ok" => 0, "baja" => 1, "media" => 2, "alta" => 3, "critica" => 4);
    $actualValor = isset($orden[$actual]) ? $orden[$actual] : 0;
    $nuevaValor = isset($orden[$nueva]) ? $orden[$nueva] : 0;
    return $nuevaValor > $actualValor ? $nueva : $actual;
  }

  private function contratosAuditoriaCatalogoErp() {
    return array(
      "erp_catalogo_unidades" => array(
        "columnas" => array("id_unidad", "codigo", "nombre", "abreviatura", "tipo_magnitud", "decimales_permitidos", "clave_sat", "estatus"),
        "indices" => array(
          "idx_catalogo_unidad_codigo" => array("columnas" => array("codigo"), "severidad" => "alta", "impacto" => "Evita unidades duplicadas y permite resolver unidad base/compra.")
        )
      ),
      "erp_catalogo_marcas" => array(
        "columnas" => array("id_marca_erp", "codigo", "nombre", "estatus"),
        "indices" => array(
          "idx_catalogo_marca_codigo" => array("columnas" => array("codigo"), "severidad" => "media", "impacto" => "Evita codigos de marca duplicados."),
          "idx_catalogo_marca_nombre" => array("columnas" => array("nombre"), "severidad" => "media", "impacto" => "Evita nombres de marca duplicados.")
        )
      ),
      "erp_catalogo_categorias" => array(
        "columnas" => array("id_categoria_erp", "id_categoria_padre", "codigo", "nombre", "ruta", "nivel", "tipo_categoria", "origen", "permite_productos", "estatus"),
        "indices" => array(
          "idx_catalogo_categoria_codigo" => array("columnas" => array("codigo"), "severidad" => "media", "impacto" => "Evita categorias duplicadas por codigo."),
          "idx_catalogo_categoria_padre" => array("columnas" => array("id_categoria_padre"), "severidad" => "media", "impacto" => "Permite construir arboles de categorias.")
        )
      ),
      "erp_catalogo_categoria_equivalencias" => array(
        "columnas" => array("id_equivalencia", "id_categoria_origen", "id_categoria_destino", "tipo", "confianza", "estatus"),
        "indices" => array(
          "idx_categoria_equivalencia" => array("columnas" => array("id_categoria_origen", "id_categoria_destino", "tipo"), "severidad" => "media", "impacto" => "Evita equivalencias duplicadas entre categorias."),
          "idx_categoria_equivalencia_destino" => array("columnas" => array("id_categoria_destino"), "severidad" => "baja", "impacto" => "Acelera consultas por categoria destino.")
        )
      ),
      "erp_catalogo_productos" => array(
        "columnas" => array("id_producto_erp", "codigo_producto", "nombre", "tipo_producto", "id_marca_erp", "maneja_variantes", "estatus"),
        "indices" => array(
          "idx_catalogo_producto_codigo" => array("columnas" => array("codigo_producto"), "severidad" => "critica", "impacto" => "Evita duplicar productos maestros."),
          "idx_catalogo_producto_nombre" => array("columnas" => array("nombre"), "severidad" => "media", "impacto" => "Soporta busquedas por nombre en Catalogo."),
          "idx_catalogo_producto_marca" => array("columnas" => array("id_marca_erp"), "severidad" => "media", "impacto" => "Soporta filtros por marca."),
          "idx_catalogo_producto_estatus" => array("columnas" => array("estatus"), "severidad" => "media", "impacto" => "Soporta filtros por estatus.")
        )
      ),
      "erp_catalogo_producto_categorias" => array(
        "columnas" => array("id_producto_categoria", "id_producto_erp", "id_categoria_erp", "es_principal"),
        "indices" => array(
          "idx_catalogo_producto_categoria" => array("columnas" => array("id_producto_erp", "id_categoria_erp"), "severidad" => "media", "impacto" => "Evita relaciones duplicadas producto-categoria."),
          "idx_catalogo_producto_categoria_categoria" => array("columnas" => array("id_categoria_erp"), "severidad" => "media", "impacto" => "Permite listar productos por categoria.")
        )
      ),
      "erp_catalogo_skus" => array(
        "columnas" => array("id_sku", "id_producto_erp", "sku", "nombre", "tipo_inventario", "id_unidad_base", "factor_unidad_base", "costo_referencia", "permite_venta_sin_existencia", "estatus"),
        "indices" => array(
          "idx_catalogo_sku" => array("columnas" => array("sku"), "severidad" => "critica", "impacto" => "SKU unico para Compras, XML, Almacen e Inventario."),
          "idx_catalogo_sku_producto" => array("columnas" => array("id_producto_erp"), "severidad" => "alta", "impacto" => "Relaciona SKUs con producto maestro."),
          "idx_catalogo_sku_estatus" => array("columnas" => array("estatus"), "severidad" => "media", "impacto" => "Soporta busquedas de SKUs activos.")
        )
      ),
      "erp_catalogo_sku_codigos" => array(
        "columnas" => array("id_sku_codigo", "id_sku", "tipo_codigo", "codigo", "es_principal", "estatus", "fecha_actualizacion"),
        "indices" => array(
          "idx_catalogo_codigo_tipo_codigo" => array("columnas" => array("tipo_codigo", "codigo"), "severidad" => "alta", "impacto" => "Evita duplicar codigos alternos y habilita busqueda por codigo."),
          "idx_catalogo_codigo_sku" => array("columnas" => array("id_sku"), "severidad" => "alta", "impacto" => "Relaciona codigos con SKU ERP.")
        )
      ),
      "erp_catalogo_atributos" => array(
        "columnas" => array("id_atributo_erp", "codigo", "nombre", "tipo_dato", "unidad", "configuracion_json", "es_variante", "estatus"),
        "indices" => array(
          "idx_catalogo_atributo_codigo" => array("columnas" => array("codigo"), "severidad" => "media", "impacto" => "Evita atributos duplicados.")
        )
      ),
      "erp_catalogo_sku_atributos" => array(
        "columnas" => array("id_sku_atributo", "id_sku", "id_atributo_erp", "valor"),
        "indices" => array(
          "idx_catalogo_sku_atributo" => array("columnas" => array("id_sku", "id_atributo_erp"), "severidad" => "media", "impacto" => "Evita repetir el mismo atributo en un SKU."),
          "idx_catalogo_sku_atributo_atributo" => array("columnas" => array("id_atributo_erp"), "severidad" => "baja", "impacto" => "Permite revisar variantes por atributo.")
        )
      ),
      "erp_catalogo_sku_proveedores" => array(
        "columnas" => array("id_sku_proveedor", "id_sku", "id_proveedor", "sku_proveedor", "id_unidad_compra", "factor_conversion", "costo_ultimo", "cantidad_minima", "dias_entrega", "es_preferido", "estatus"),
        "indices" => array(
          "idx_catalogo_sku_proveedor" => array("columnas" => array("id_sku", "id_proveedor"), "severidad" => "critica", "impacto" => "Contrato de Compras: un SKU comprable debe estar relacionado con proveedor."),
          "idx_catalogo_sku_proveedor_proveedor" => array("columnas" => array("id_proveedor"), "severidad" => "alta", "impacto" => "Permite buscar SKUs por proveedor en Solicitudes y Ordenes.")
        )
      ),
      "erp_catalogo_sku_impuestos" => array(
        "columnas" => array("id_sku_impuesto", "id_sku", "clave_producto_sat", "clave_unidad_sat", "objeto_impuesto", "iva_porcentaje", "ieps_porcentaje", "incluye_impuestos"),
        "indices" => array(
          "idx_catalogo_sku_impuesto_sku" => array("columnas" => array("id_sku"), "severidad" => "critica", "impacto" => "Relacion fiscal 1:1 por SKU para Compras/XML/SAT.")
        )
      ),
      "erp_catalogo_sku_precios" => array(
        "columnas" => array("id_sku_precio", "id_sku", "lista_precio", "moneda", "precio", "vigencia_desde", "vigencia_hasta", "estatus"),
        "indices" => array(
          "idx_catalogo_sku_precio_lista" => array("columnas" => array("id_sku", "lista_precio", "moneda"), "severidad" => "alta", "impacto" => "Evita duplicar precio por lista/moneda."),
          "idx_catalogo_sku_precio_vigencia" => array("columnas" => array("vigencia_desde", "vigencia_hasta"), "severidad" => "media", "impacto" => "Soporta consulta por vigencia.")
        )
      ),
      "erp_catalogo_sku_reglas_inventario" => array(
        "columnas" => array(
          "id_sku_regla_inventario", "id_sku", "controla_inventario", "permite_existencia_negativa", "requiere_lote",
          "requiere_caducidad", "requiere_serie", "requiere_serie_fabricante", "generar_etiqueta_interna",
          "requiere_escaneo_venta", "permite_venta_fraccionaria", "precision_decimal", "incremento_minimo_venta",
          "unidad_venta_label", "permite_etiqueta_fraccionada", "prefijo_etiqueta_interna", "plantilla_etiqueta",
          "tipo_etiqueta_seguridad", "instrucciones_etiquetado", "estrategia_salida", "stock_minimo", "stock_maximo",
          "punto_reorden", "dias_alerta_caducidad", "dias_minimos_recepcion",
          "requiere_cantidad_variable_recepcion", "requiere_unidades_fisicas_recepcion",
          "tolerancia_recepcion_porcentaje", "nota_recepcion_variable"
        ),
        "indices" => array(
          "idx_catalogo_sku_regla_sku" => array("columnas" => array("id_sku"), "severidad" => "critica", "impacto" => "Relacion 1:1 de reglas fisicas para Almacen e Inventario."),
          "idx_catalogo_regla_recepcion_variable" => array("columnas" => array("requiere_cantidad_variable_recepcion"), "severidad" => "alta", "impacto" => "Permite a Almacen/Recepcion detectar SKUs que requieren cantidad real capturada.")
        )
      ),
      "erp_catalogo_sku_presentaciones" => array(
        "columnas" => array("id_sku_presentacion_regla", "id_sku_base", "id_sku_presentacion", "factor_salida_base", "modo_disponibilidad", "consume_stock_base_en", "requiere_empaque", "capacidad_diaria", "merma_porcentaje", "estatus", "fecha_registro", "fecha_actualizacion"),
        "indices" => array(
          "idx_catalogo_presentacion_sku" => array("columnas" => array("id_sku_presentacion"), "severidad" => "critica", "impacto" => "Evita duplicar la regla de una presentacion vendible y permite resolver que SKU base consume."),
          "idx_catalogo_presentacion_base" => array("columnas" => array("id_sku_base"), "severidad" => "alta", "impacto" => "Permite listar presentaciones derivadas de un SKU base.")
        )
      ),
      "erp_catalogo_sku_paquetes" => array(
        "columnas" => array("id_paquete", "id_sku_paquete", "tipo_paquete", "modo_disponibilidad", "permite_configuracion_cliente", "permite_desarmar", "requiere_armado_almacen", "observaciones", "estatus", "fecha_registro", "fecha_actualizacion"),
        "indices" => array(
          "idx_catalogo_paquete_sku" => array("columnas" => array("id_sku_paquete"), "severidad" => "critica", "impacto" => "Evita duplicar receta maestra para un SKU paquete."),
          "idx_catalogo_paquete_tipo" => array("columnas" => array("tipo_paquete"), "severidad" => "media", "impacto" => "Permite filtrar paquetes simples, configurables, virtuales, prearmados, combos o comprados cerrados."),
          "idx_catalogo_paquete_estatus" => array("columnas" => array("estatus"), "severidad" => "media", "impacto" => "Permite listar paquetes activos/inactivos.")
        )
      ),
      "erp_catalogo_sku_paquete_componentes" => array(
        "columnas" => array("id_componente", "id_paquete", "id_sku_componente", "cantidad", "id_unidad", "factor_conversion", "orden", "estatus", "fecha_registro", "fecha_actualizacion"),
        "indices" => array(
          "idx_catalogo_paquete_componente_paquete" => array("columnas" => array("id_paquete"), "severidad" => "critica", "impacto" => "Permite listar los componentes fijos de una receta de paquete."),
          "idx_catalogo_paquete_componente_sku" => array("columnas" => array("id_sku_componente"), "severidad" => "alta", "impacto" => "Permite detectar en que paquetes participa un SKU como componente fijo.")
        )
      ),
      "erp_catalogo_sku_paquete_grupos" => array(
        "columnas" => array("id_grupo", "id_paquete", "codigo", "nombre", "descripcion", "min_selecciones", "max_selecciones", "modo_cantidad", "cantidad_total_grupo", "obligatorio", "orden", "estatus", "fecha_registro", "fecha_actualizacion"),
        "indices" => array(
          "idx_catalogo_paquete_grupo_codigo" => array("columnas" => array("id_paquete", "codigo"), "severidad" => "alta", "impacto" => "Evita duplicar grupos de seleccion dentro del mismo paquete."),
          "idx_catalogo_paquete_grupo_paquete" => array("columnas" => array("id_paquete"), "severidad" => "critica", "impacto" => "Permite listar grupos configurables de un paquete.")
        )
      ),
      "erp_catalogo_sku_paquete_grupo_opciones" => array(
        "columnas" => array("id_opcion", "id_grupo", "id_sku_opcion", "cantidad_default", "cantidad_minima", "cantidad_maxima", "id_unidad", "factor_conversion", "permite_cantidad_editable", "orden", "estatus", "fecha_registro", "fecha_actualizacion"),
        "indices" => array(
          "idx_catalogo_paquete_opcion_grupo" => array("columnas" => array("id_grupo"), "severidad" => "critica", "impacto" => "Permite listar las opciones seleccionables de un grupo."),
          "idx_catalogo_paquete_opcion_sku" => array("columnas" => array("id_sku_opcion"), "severidad" => "alta", "impacto" => "Permite detectar en que grupos seleccionables aparece un SKU.")
        )
      ),
      "erp_catalogo_canales_vinculos" => array(
        "columnas" => array("id_canal_vinculo", "id_producto_erp", "id_sku", "canal", "id_externo", "sku_externo", "sincronizar_catalogo", "sincronizar_precio", "sincronizar_existencia", "estatus"),
        "indices" => array(
          "idx_catalogo_canal_externo" => array("columnas" => array("canal", "id_externo"), "severidad" => "alta", "impacto" => "Evita vinculos externos duplicados por canal."),
          "idx_catalogo_canal_producto" => array("columnas" => array("id_producto_erp"), "severidad" => "media", "impacto" => "Permite rastrear producto ERP desde ecommerce."),
          "idx_catalogo_canal_sku" => array("columnas" => array("id_sku"), "severidad" => "media", "impacto" => "Permite rastrear SKU ERP desde ecommerce.")
        )
      ),
      "erp_catalogo_migracion_ecom_incidencias" => array(
        "columnas" => array("id_incidencia", "id_producto_ecom", "id_variante_ecom", "sku", "nombre_producto", "motivo", "detalle_json", "estatus"),
        "indices" => array(
          "idx_migracion_ecom_producto" => array("columnas" => array("id_producto_ecom"), "severidad" => "alta", "impacto" => "Evita duplicar incidencia por producto ecommerce."),
          "idx_migracion_ecom_motivo" => array("columnas" => array("motivo"), "severidad" => "media", "impacto" => "Permite clasificar incidencias por motivo."),
          "idx_migracion_ecom_estatus" => array("columnas" => array("estatus"), "severidad" => "media", "impacto" => "Permite filtrar pendientes/resueltas.")
        )
      ),
      "erp_catalogo_incidencias_calidad" => array(
        "columnas" => array("id_incidencia_calidad", "huella", "tipo_incidencia", "entidad_tipo", "id_producto_erp", "id_sku", "origen", "severidad", "estatus", "detalle_json", "evidencia_json", "propuesta_json", "resolucion_json"),
        "indices" => array(
          "idx_catalogo_incidencia_huella" => array("columnas" => array("huella"), "severidad" => "critica", "impacto" => "Evita duplicar incidencias activas por el mismo problema operativo."),
          "idx_catalogo_incidencia_sku" => array("columnas" => array("id_sku"), "severidad" => "alta", "impacto" => "Permite consultar pendientes fiscales y de calidad por SKU."),
          "idx_catalogo_incidencia_tipo_estatus" => array("columnas" => array("tipo_incidencia", "estatus"), "severidad" => "alta", "impacto" => "Permite bandejas de trabajo por tipo de incidencia y estatus."),
          "idx_catalogo_incidencia_severidad" => array("columnas" => array("severidad", "estatus"), "severidad" => "media", "impacto" => "Permite priorizar bloqueantes, advertencias e informativas."),
          "idx_catalogo_incidencia_origen" => array("columnas" => array("origen"), "severidad" => "media", "impacto" => "Rastrea si la incidencia nacio en Catalogo, Compras, XML, migracion o captura manual.")
        )
      ),
      "erp_catalogo_imagenes" => array(
        "columnas" => array("id_imagen_erp", "id_producto_erp", "id_sku", "tipo_imagen", "url_imagen", "texto_alternativo", "orden", "fuente", "id_externo", "estatus"),
        "indices" => array(
          "idx_catalogo_imagen_producto" => array("columnas" => array("id_producto_erp"), "severidad" => "media", "impacto" => "Permite listar imagenes por producto."),
          "idx_catalogo_imagen_sku" => array("columnas" => array("id_sku"), "severidad" => "media", "impacto" => "Permite listar imagenes especificas de SKU."),
          "idx_catalogo_imagen_tipo" => array("columnas" => array("tipo_imagen", "estatus"), "severidad" => "baja", "impacto" => "Permite ubicar portada/galeria activa.")
        )
      ),
      "erp_catalogo_marca_imagenes" => array(
        "columnas" => array("id_marca_imagen", "id_marca_erp", "tipo_imagen", "url_imagen", "texto_alternativo", "orden", "estatus", "fecha_registro", "fecha_actualizacion"),
        "indices" => array(
          "idx_marca_imagen_marca" => array("columnas" => array("id_marca_erp", "estatus"), "severidad" => "media", "impacto" => "Permite listar logos, banners y referencias por marca."),
          "idx_marca_imagen_tipo" => array("columnas" => array("tipo_imagen", "estatus"), "severidad" => "baja", "impacto" => "Permite ubicar logos activos por tipo.")
        )
      ),
      "erp_catalogo_categoria_imagenes" => array(
        "columnas" => array("id_categoria_imagen", "id_categoria_erp", "tipo_imagen", "url_imagen", "texto_alternativo", "orden", "estatus", "fecha_registro", "fecha_actualizacion"),
        "indices" => array(
          "idx_categoria_imagen_categoria" => array("columnas" => array("id_categoria_erp", "estatus"), "severidad" => "media", "impacto" => "Permite listar iconos, portadas y referencias por categoria."),
          "idx_categoria_imagen_tipo" => array("columnas" => array("tipo_imagen", "estatus"), "severidad" => "baja", "impacto" => "Permite ubicar iconos o portadas activas por tipo.")
        )
      ),
      "erp_catalogo_revision_nombres" => array(
        "columnas" => array("id_revision_nombre", "id_producto_erp", "id_sku", "nombre_actual", "nombre_proveedor", "nombre_propuesto", "evidencia_json", "estatus"),
        "indices" => array(
          "idx_catalogo_revision_nombre_sku" => array("columnas" => array("id_sku"), "severidad" => "media", "impacto" => "Evita revisiones duplicadas por SKU."),
          "idx_catalogo_revision_nombre_producto" => array("columnas" => array("id_producto_erp"), "severidad" => "baja", "impacto" => "Permite listar revisiones por producto."),
          "idx_catalogo_revision_nombre_estatus" => array("columnas" => array("estatus"), "severidad" => "baja", "impacto" => "Permite filtrar revisiones pendientes.")
        )
      ),
      "erp_catalogo_productos_fusiones" => array(
        "columnas" => array("id_fusion", "id_producto_origen", "id_producto_destino", "motivo", "skus_movidos", "usuario_id"),
        "indices" => array(
          "idx_catalogo_fusion_origen" => array("columnas" => array("id_producto_origen"), "severidad" => "media", "impacto" => "Mantiene trazabilidad del producto fusionado."),
          "idx_catalogo_fusion_destino" => array("columnas" => array("id_producto_destino"), "severidad" => "media", "impacto" => "Mantiene trazabilidad del producto destino.")
        )
      ),
      "erp_catalogo_taxonomias" => array(
        "columnas" => array("id_taxonomia", "codigo", "nombre", "tipo", "canal", "estatus"),
        "indices" => array(
          "idx_catalogo_taxonomia_codigo" => array("columnas" => array("codigo"), "severidad" => "media", "impacto" => "Evita taxonomias duplicadas.")
        )
      ),
      "erp_catalogo_taxonomia_nodos" => array(
        "columnas" => array("id_nodo_taxonomia", "id_taxonomia", "id_nodo_padre", "id_categoria_erp", "tipo_nodo", "codigo", "nombre", "ruta", "nivel", "orden", "id_externo", "estatus"),
        "indices" => array(
          "idx_taxonomia_nodo_codigo" => array("columnas" => array("id_taxonomia", "codigo"), "severidad" => "media", "impacto" => "Evita nodos duplicados por taxonomia."),
          "idx_taxonomia_nodo_padre" => array("columnas" => array("id_nodo_padre"), "severidad" => "media", "impacto" => "Permite navegar el arbol de nodos."),
          "idx_taxonomia_nodo_categoria" => array("columnas" => array("id_categoria_erp"), "severidad" => "baja", "impacto" => "Relaciona nodos con categorias ERP.")
        )
      ),
      "erp_catalogo_producto_taxonomia_nodos" => array(
        "columnas" => array("id_producto_nodo", "id_producto_erp", "id_nodo_taxonomia", "es_principal"),
        "indices" => array(
          "idx_producto_taxonomia_nodo" => array("columnas" => array("id_producto_erp", "id_nodo_taxonomia"), "severidad" => "media", "impacto" => "Evita duplicar producto en un nodo."),
          "idx_producto_taxonomia_nodo_nodo" => array("columnas" => array("id_nodo_taxonomia"), "severidad" => "baja", "impacto" => "Permite listar productos por nodo.")
        )
      )
    );
  }

  private function contratosOperativosSkuErp() {
    return array(
      "erp_compras_ordenes_detalle" => array(
        "columnas" => array(
          "id_sku_erp" => array("severidad" => "critica", "impacto" => "Permite que Compras apunte al SKU maestro ERP.")
        ),
        "indices" => array(
          "idx_compra_detalle_sku_erp" => array("columnas" => array("id_sku_erp"), "severidad" => "alta", "impacto" => "Acelera consultas de ordenes por SKU ERP.")
        )
      ),
      "erp_almacen_recepciones_detalle" => array(
        "columnas" => array(
          "id_sku_erp" => array("severidad" => "critica", "impacto" => "Permite recibir contra SKU maestro ERP.")
        ),
        "indices" => array(
          "idx_recepcion_detalle_sku_erp" => array("columnas" => array("id_sku_erp"), "severidad" => "alta", "impacto" => "Acelera consultas de recepcion por SKU ERP.")
        )
      ),
      "erp_almacen_recepciones_lotes" => array(
        "columnas" => array(
          "id_sku_erp" => array("severidad" => "critica", "impacto" => "Permite rastrear lote/caducidad por SKU maestro ERP.")
        ),
        "indices" => array(
          "idx_recepcion_lote_sku_erp" => array("columnas" => array("id_sku_erp"), "severidad" => "alta", "impacto" => "Acelera consultas de lotes por SKU ERP.")
        )
      ),
      "erp_inventario_movimientos" => array(
        "columnas" => array(
          "id_sku_erp" => array("severidad" => "critica", "impacto" => "Permite movimientos de inventario contra SKU maestro ERP.")
        ),
        "indices" => array(
          "idx_inventario_mov_sku_erp" => array("columnas" => array("id_sku_erp"), "severidad" => "alta", "impacto" => "Acelera Kardex/movimientos por SKU ERP.")
        )
      ),
      "erp_inventario_existencias" => array(
        "columnas" => array(
          "id_sku_erp" => array("severidad" => "critica", "impacto" => "Permite existencia por SKU maestro ERP.")
        ),
        "indices" => array(
          "idx_inventario_existencia_sku_erp" => array("columnas" => array("id_sku_erp"), "severidad" => "alta", "impacto" => "Acelera consultas de existencia por SKU ERP."),
          "idx_existencia_producto_lote_ubicacion" => array("columnas" => array("id_producto", "id_sku_erp", "id_almacen_clave", "lote_clave", "fecha_caducidad_clave", "ubicacion_clave"), "severidad" => "critica", "impacto" => "Evita duplicar existencias por producto/SKU/almacen/lote/caducidad/ubicacion.")
        )
      )
    );
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-26
   * Proposito: prepara el DDL de Catalogo ERP, incluyendo recepcion variable y paquetes/kits.
   * Impacto: Catalogo ERP; con $ejecutar=false solo devuelve plan, con $ejecutar=true requiere respaldo/autorizacion externa.
   */
  public function planActualizarCatalogoErp($ejecutar = false) {
    $plan = array();
    $opciones = "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

    $plan[] = $this->crearTablaSiNoExiste("erp_catalogo_unidades", array(
      "`id_unidad` INT NOT NULL AUTO_INCREMENT",
      "`codigo` VARCHAR(30) NOT NULL",
      "`nombre` VARCHAR(100) NOT NULL",
      "`abreviatura` VARCHAR(30) NOT NULL",
      "`tipo_magnitud` VARCHAR(40) NOT NULL DEFAULT 'unidad'",
      "`decimales_permitidos` TINYINT(1) NOT NULL DEFAULT 0",
      "`clave_sat` VARCHAR(20) NULL",
      "`estatus` VARCHAR(20) NOT NULL DEFAULT 'activa'",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`fecha_actualizacion` DATETIME NULL",
      "PRIMARY KEY (`id_unidad`)",
      "UNIQUE KEY `idx_catalogo_unidad_codigo` (`codigo`)"
    ), $opciones, $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("erp_catalogo_marcas", array(
      "`id_marca_erp` INT NOT NULL AUTO_INCREMENT",
      "`codigo` VARCHAR(40) NOT NULL",
      "`nombre` VARCHAR(150) NOT NULL",
      "`descripcion` TEXT NULL",
      "`estatus` VARCHAR(20) NOT NULL DEFAULT 'activa'",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`fecha_actualizacion` DATETIME NULL",
      "PRIMARY KEY (`id_marca_erp`)",
      "UNIQUE KEY `idx_catalogo_marca_codigo` (`codigo`)",
      "UNIQUE KEY `idx_catalogo_marca_nombre` (`nombre`)"
    ), $opciones, $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("erp_catalogo_categorias", array(
      "`id_categoria_erp` INT NOT NULL AUTO_INCREMENT",
      "`id_categoria_padre` INT NULL",
      "`codigo` VARCHAR(50) NOT NULL",
      "`nombre` VARCHAR(150) NOT NULL",
      "`descripcion` TEXT NULL",
      "`ruta` VARCHAR(600) NULL",
      "`nivel` INT NOT NULL DEFAULT 0",
      "`tipo_categoria` VARCHAR(30) NOT NULL DEFAULT 'maestra'",
      "`origen` VARCHAR(40) NOT NULL DEFAULT 'erp'",
      "`permite_productos` TINYINT(1) NOT NULL DEFAULT 1",
      "`estatus` VARCHAR(20) NOT NULL DEFAULT 'activa'",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`fecha_actualizacion` DATETIME NULL",
      "PRIMARY KEY (`id_categoria_erp`)",
      "UNIQUE KEY `idx_catalogo_categoria_codigo` (`codigo`)",
      "KEY `idx_catalogo_categoria_padre` (`id_categoria_padre`)",
      "CONSTRAINT `fk_catalogo_categoria_padre` FOREIGN KEY (`id_categoria_padre`) REFERENCES `erp_catalogo_categorias` (`id_categoria_erp`)"
    ), $opciones, $ejecutar);
    $plan[] = $this->agregarColumnaSiNoExiste("erp_catalogo_categorias", "tipo_categoria", "VARCHAR(30) NOT NULL DEFAULT 'maestra' AFTER `nivel`", $ejecutar);
    $plan[] = $this->agregarColumnaSiNoExiste("erp_catalogo_categorias", "origen", "VARCHAR(40) NOT NULL DEFAULT 'erp' AFTER `tipo_categoria`", $ejecutar);
    $plan[] = $this->agregarColumnaSiNoExiste("erp_catalogo_categorias", "permite_productos", "TINYINT(1) NOT NULL DEFAULT 1 AFTER `origen`", $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("erp_catalogo_categoria_equivalencias", array(
      "`id_equivalencia` BIGINT NOT NULL AUTO_INCREMENT",
      "`id_categoria_origen` INT NOT NULL",
      "`id_categoria_destino` INT NOT NULL",
      "`tipo` VARCHAR(30) NOT NULL DEFAULT 'migracion'",
      "`confianza` DECIMAL(5,2) NULL",
      "`estatus` VARCHAR(20) NOT NULL DEFAULT 'propuesta'",
      "`observaciones` VARCHAR(500) NULL",
      "`creado_por` INT NULL",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`fecha_actualizacion` DATETIME NULL",
      "PRIMARY KEY (`id_equivalencia`)",
      "UNIQUE KEY `idx_categoria_equivalencia` (`id_categoria_origen`, `id_categoria_destino`, `tipo`)",
      "KEY `idx_categoria_equivalencia_destino` (`id_categoria_destino`)",
      "CONSTRAINT `fk_categoria_equivalencia_origen` FOREIGN KEY (`id_categoria_origen`) REFERENCES `erp_catalogo_categorias` (`id_categoria_erp`)",
      "CONSTRAINT `fk_categoria_equivalencia_destino` FOREIGN KEY (`id_categoria_destino`) REFERENCES `erp_catalogo_categorias` (`id_categoria_erp`)"
    ), $opciones, $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("erp_catalogo_productos", array(
      "`id_producto_erp` BIGINT NOT NULL AUTO_INCREMENT",
      "`codigo_producto` VARCHAR(80) NOT NULL",
      "`nombre` VARCHAR(255) NOT NULL",
      "`descripcion` TEXT NULL",
      "`tipo_producto` VARCHAR(40) NOT NULL DEFAULT 'producto'",
      "`id_marca_erp` INT NULL",
      "`maneja_variantes` TINYINT(1) NOT NULL DEFAULT 0",
      "`estatus` VARCHAR(30) NOT NULL DEFAULT 'borrador'",
      "`creado_por` INT NULL",
      "`actualizado_por` INT NULL",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`fecha_actualizacion` DATETIME NULL",
      "PRIMARY KEY (`id_producto_erp`)",
      "UNIQUE KEY `idx_catalogo_producto_codigo` (`codigo_producto`)",
      "KEY `idx_catalogo_producto_nombre` (`nombre`)",
      "KEY `idx_catalogo_producto_marca` (`id_marca_erp`)",
      "KEY `idx_catalogo_producto_estatus` (`estatus`)",
      "CONSTRAINT `fk_catalogo_producto_marca` FOREIGN KEY (`id_marca_erp`) REFERENCES `erp_catalogo_marcas` (`id_marca_erp`)"
    ), $opciones, $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("erp_catalogo_producto_categorias", array(
      "`id_producto_categoria` BIGINT NOT NULL AUTO_INCREMENT",
      "`id_producto_erp` BIGINT NOT NULL",
      "`id_categoria_erp` INT NOT NULL",
      "`es_principal` TINYINT(1) NOT NULL DEFAULT 0",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "PRIMARY KEY (`id_producto_categoria`)",
      "UNIQUE KEY `idx_catalogo_producto_categoria` (`id_producto_erp`, `id_categoria_erp`)",
      "KEY `idx_catalogo_producto_categoria_categoria` (`id_categoria_erp`)",
      "CONSTRAINT `fk_catalogo_producto_categoria_producto` FOREIGN KEY (`id_producto_erp`) REFERENCES `erp_catalogo_productos` (`id_producto_erp`)",
      "CONSTRAINT `fk_catalogo_producto_categoria_categoria` FOREIGN KEY (`id_categoria_erp`) REFERENCES `erp_catalogo_categorias` (`id_categoria_erp`)"
    ), $opciones, $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("erp_catalogo_skus", array(
      "`id_sku` BIGINT NOT NULL AUTO_INCREMENT",
      "`id_producto_erp` BIGINT NOT NULL",
      "`sku` VARCHAR(150) NOT NULL",
      "`nombre` VARCHAR(255) NOT NULL",
      "`tipo_inventario` VARCHAR(40) NOT NULL DEFAULT 'inventariable'",
      "`id_unidad_base` INT NOT NULL",
      "`factor_unidad_base` DECIMAL(18,6) NOT NULL DEFAULT 1.000000",
      "`costo_referencia` DECIMAL(18,6) NOT NULL DEFAULT 0.000000",
      "`permite_venta_sin_existencia` TINYINT(1) NOT NULL DEFAULT 0",
      "`estatus` VARCHAR(30) NOT NULL DEFAULT 'borrador'",
      "`creado_por` INT NULL",
      "`actualizado_por` INT NULL",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`fecha_actualizacion` DATETIME NULL",
      "PRIMARY KEY (`id_sku`)",
      "UNIQUE KEY `idx_catalogo_sku` (`sku`)",
      "KEY `idx_catalogo_sku_producto` (`id_producto_erp`)",
      "KEY `idx_catalogo_sku_estatus` (`estatus`)",
      "CONSTRAINT `fk_catalogo_sku_producto` FOREIGN KEY (`id_producto_erp`) REFERENCES `erp_catalogo_productos` (`id_producto_erp`)",
      "CONSTRAINT `fk_catalogo_sku_unidad` FOREIGN KEY (`id_unidad_base`) REFERENCES `erp_catalogo_unidades` (`id_unidad`)"
    ), $opciones, $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("erp_catalogo_sku_codigos", array(
      "`id_sku_codigo` BIGINT NOT NULL AUTO_INCREMENT",
      "`id_sku` BIGINT NOT NULL",
      "`tipo_codigo` VARCHAR(30) NOT NULL DEFAULT 'codigo_barras'",
      "`codigo` VARCHAR(180) NOT NULL",
      "`es_principal` TINYINT(1) NOT NULL DEFAULT 0",
      "`estatus` VARCHAR(20) NOT NULL DEFAULT 'activo'",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`fecha_actualizacion` DATETIME NULL",
      "PRIMARY KEY (`id_sku_codigo`)",
      "UNIQUE KEY `idx_catalogo_codigo_tipo_codigo` (`tipo_codigo`, `codigo`)",
      "KEY `idx_catalogo_codigo_sku` (`id_sku`)",
      "CONSTRAINT `fk_catalogo_codigo_sku` FOREIGN KEY (`id_sku`) REFERENCES `erp_catalogo_skus` (`id_sku`)"
    ), $opciones, $ejecutar);
    $plan[] = $this->agregarColumnaSiNoExiste("erp_catalogo_sku_codigos", "fecha_actualizacion", "DATETIME NULL AFTER `fecha_registro`", $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("erp_catalogo_atributos", array(
      "`id_atributo_erp` INT NOT NULL AUTO_INCREMENT",
      "`codigo` VARCHAR(50) NOT NULL",
      "`nombre` VARCHAR(100) NOT NULL",
      "`tipo_dato` VARCHAR(30) NOT NULL DEFAULT 'texto'",
      "`unidad` VARCHAR(30) NULL",
      "`configuracion_json` TEXT NULL",
      "`es_variante` TINYINT(1) NOT NULL DEFAULT 0",
      "`estatus` VARCHAR(20) NOT NULL DEFAULT 'activo'",
      "PRIMARY KEY (`id_atributo_erp`)",
      "UNIQUE KEY `idx_catalogo_atributo_codigo` (`codigo`)"
    ), $opciones, $ejecutar);
    $plan[] = $this->agregarColumnaSiNoExiste("erp_catalogo_atributos", "configuracion_json", "TEXT NULL AFTER `unidad`", $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("erp_catalogo_sku_atributos", array(
      "`id_sku_atributo` BIGINT NOT NULL AUTO_INCREMENT",
      "`id_sku` BIGINT NOT NULL",
      "`id_atributo_erp` INT NOT NULL",
      "`valor` VARCHAR(500) NOT NULL",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`fecha_actualizacion` DATETIME NULL",
      "PRIMARY KEY (`id_sku_atributo`)",
      "UNIQUE KEY `idx_catalogo_sku_atributo` (`id_sku`, `id_atributo_erp`)",
      "KEY `idx_catalogo_sku_atributo_atributo` (`id_atributo_erp`)",
      "CONSTRAINT `fk_catalogo_sku_atributo_sku` FOREIGN KEY (`id_sku`) REFERENCES `erp_catalogo_skus` (`id_sku`)",
      "CONSTRAINT `fk_catalogo_sku_atributo_atributo` FOREIGN KEY (`id_atributo_erp`) REFERENCES `erp_catalogo_atributos` (`id_atributo_erp`)"
    ), $opciones, $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("erp_catalogo_sku_proveedores", array(
      "`id_sku_proveedor` BIGINT NOT NULL AUTO_INCREMENT",
      "`id_sku` BIGINT NOT NULL",
      "`id_proveedor` INT NOT NULL",
      "`sku_proveedor` VARCHAR(150) NULL",
      "`id_unidad_compra` INT NOT NULL",
      "`factor_conversion` DECIMAL(18,6) NOT NULL DEFAULT 1.000000",
      "`costo_ultimo` DECIMAL(18,6) NOT NULL DEFAULT 0.000000",
      "`cantidad_minima` DECIMAL(18,6) NOT NULL DEFAULT 1.000000",
      "`dias_entrega` INT NOT NULL DEFAULT 0",
      "`es_preferido` TINYINT(1) NOT NULL DEFAULT 0",
      "`estatus` VARCHAR(20) NOT NULL DEFAULT 'activo'",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`fecha_actualizacion` DATETIME NULL",
      "PRIMARY KEY (`id_sku_proveedor`)",
      "UNIQUE KEY `idx_catalogo_sku_proveedor` (`id_sku`, `id_proveedor`)",
      "KEY `idx_catalogo_sku_proveedor_proveedor` (`id_proveedor`)",
      "CONSTRAINT `fk_catalogo_sku_proveedor_sku` FOREIGN KEY (`id_sku`) REFERENCES `erp_catalogo_skus` (`id_sku`)",
      "CONSTRAINT `fk_catalogo_sku_proveedor_unidad` FOREIGN KEY (`id_unidad_compra`) REFERENCES `erp_catalogo_unidades` (`id_unidad`)"
    ), $opciones, $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("erp_catalogo_sku_impuestos", array(
      "`id_sku_impuesto` BIGINT NOT NULL AUTO_INCREMENT",
      "`id_sku` BIGINT NOT NULL",
      "`clave_producto_sat` VARCHAR(20) NULL",
      "`clave_unidad_sat` VARCHAR(20) NULL",
      "`objeto_impuesto` VARCHAR(10) NULL",
      "`iva_porcentaje` DECIMAL(8,4) NOT NULL DEFAULT 0.0000",
      "`ieps_porcentaje` DECIMAL(8,4) NOT NULL DEFAULT 0.0000",
      "`incluye_impuestos` TINYINT(1) NOT NULL DEFAULT 0",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`fecha_actualizacion` DATETIME NULL",
      "PRIMARY KEY (`id_sku_impuesto`)",
      "UNIQUE KEY `idx_catalogo_sku_impuesto_sku` (`id_sku`)",
      "CONSTRAINT `fk_catalogo_sku_impuesto_sku` FOREIGN KEY (`id_sku`) REFERENCES `erp_catalogo_skus` (`id_sku`)"
    ), $opciones, $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("erp_catalogo_sku_precios", array(
      "`id_sku_precio` BIGINT NOT NULL AUTO_INCREMENT",
      "`id_sku` BIGINT NOT NULL",
      "`lista_precio` VARCHAR(80) NOT NULL DEFAULT 'general'",
      "`moneda` CHAR(3) NOT NULL DEFAULT 'MXN'",
      "`precio` DECIMAL(18,6) NOT NULL DEFAULT 0.000000",
      "`vigencia_desde` DATETIME NULL",
      "`vigencia_hasta` DATETIME NULL",
      "`estatus` VARCHAR(20) NOT NULL DEFAULT 'activo'",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`fecha_actualizacion` DATETIME NULL",
      "PRIMARY KEY (`id_sku_precio`)",
      "UNIQUE KEY `idx_catalogo_sku_precio_lista` (`id_sku`, `lista_precio`, `moneda`)",
      "KEY `idx_catalogo_sku_precio_vigencia` (`vigencia_desde`, `vigencia_hasta`)",
      "CONSTRAINT `fk_catalogo_sku_precio_sku` FOREIGN KEY (`id_sku`) REFERENCES `erp_catalogo_skus` (`id_sku`)"
    ), $opciones, $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("erp_catalogo_sku_reglas_inventario", array(
      "`id_sku_regla_inventario` BIGINT NOT NULL AUTO_INCREMENT",
      "`id_sku` BIGINT NOT NULL",
      "`controla_inventario` TINYINT(1) NOT NULL DEFAULT 1",
      "`permite_existencia_negativa` TINYINT(1) NOT NULL DEFAULT 0",
      "`requiere_lote` TINYINT(1) NOT NULL DEFAULT 0",
      "`requiere_caducidad` TINYINT(1) NOT NULL DEFAULT 0",
      "`requiere_serie` TINYINT(1) NOT NULL DEFAULT 0",
      "`requiere_serie_fabricante` TINYINT(1) NOT NULL DEFAULT 0",
      "`generar_etiqueta_interna` TINYINT(1) NOT NULL DEFAULT 0",
      "`requiere_escaneo_venta` TINYINT(1) NOT NULL DEFAULT 0",
      "`permite_venta_fraccionaria` TINYINT(1) NOT NULL DEFAULT 0",
      "`precision_decimal` TINYINT UNSIGNED NOT NULL DEFAULT 0",
      "`incremento_minimo_venta` DECIMAL(18,6) NOT NULL DEFAULT 1.000000",
      "`unidad_venta_label` VARCHAR(30) NULL",
      "`permite_etiqueta_fraccionada` TINYINT(1) NOT NULL DEFAULT 0",
      "`prefijo_etiqueta_interna` VARCHAR(30) NULL",
      "`plantilla_etiqueta` VARCHAR(80) NULL",
      "`tipo_etiqueta_seguridad` VARCHAR(40) NULL",
      "`instrucciones_etiquetado` TEXT NULL",
      "`estrategia_salida` VARCHAR(20) NOT NULL DEFAULT 'FIFO'",
      "`stock_minimo` DECIMAL(18,6) NOT NULL DEFAULT 0.000000",
      "`stock_maximo` DECIMAL(18,6) NULL",
      "`punto_reorden` DECIMAL(18,6) NOT NULL DEFAULT 0.000000",
      "`dias_alerta_caducidad` INT NOT NULL DEFAULT 90",
      "`dias_minimos_recepcion` INT NOT NULL DEFAULT 0",
      "`requiere_cantidad_variable_recepcion` TINYINT(1) NOT NULL DEFAULT 0",
      "`requiere_unidades_fisicas_recepcion` TINYINT(1) NOT NULL DEFAULT 0",
      "`tolerancia_recepcion_porcentaje` DECIMAL(9,4) NULL",
      "`nota_recepcion_variable` VARCHAR(255) NULL",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`fecha_actualizacion` DATETIME NULL",
      "PRIMARY KEY (`id_sku_regla_inventario`)",
      "UNIQUE KEY `idx_catalogo_sku_regla_sku` (`id_sku`)",
      "KEY `idx_catalogo_regla_recepcion_variable` (`requiere_cantidad_variable_recepcion`)",
      "CONSTRAINT `fk_catalogo_sku_regla_sku` FOREIGN KEY (`id_sku`) REFERENCES `erp_catalogo_skus` (`id_sku`)"
    ), $opciones, $ejecutar);
    $plan[] = $this->agregarColumnaSiNoExiste("erp_catalogo_sku_reglas_inventario", "requiere_serie_fabricante", "TINYINT(1) NOT NULL DEFAULT 0 AFTER `requiere_serie`", $ejecutar);
    $plan[] = $this->agregarColumnaSiNoExiste("erp_catalogo_sku_reglas_inventario", "generar_etiqueta_interna", "TINYINT(1) NOT NULL DEFAULT 0 AFTER `requiere_serie_fabricante`", $ejecutar);
    $plan[] = $this->agregarColumnaSiNoExiste("erp_catalogo_sku_reglas_inventario", "requiere_escaneo_venta", "TINYINT(1) NOT NULL DEFAULT 0 AFTER `generar_etiqueta_interna`", $ejecutar);
    $plan[] = $this->agregarColumnaSiNoExiste("erp_catalogo_sku_reglas_inventario", "permite_venta_fraccionaria", "TINYINT(1) NOT NULL DEFAULT 0 AFTER `requiere_escaneo_venta`", $ejecutar);
    $plan[] = $this->agregarColumnaSiNoExiste("erp_catalogo_sku_reglas_inventario", "precision_decimal", "TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER `permite_venta_fraccionaria`", $ejecutar);
    $plan[] = $this->agregarColumnaSiNoExiste("erp_catalogo_sku_reglas_inventario", "incremento_minimo_venta", "DECIMAL(18,6) NOT NULL DEFAULT 1.000000 AFTER `precision_decimal`", $ejecutar);
    $plan[] = $this->agregarColumnaSiNoExiste("erp_catalogo_sku_reglas_inventario", "unidad_venta_label", "VARCHAR(30) NULL AFTER `incremento_minimo_venta`", $ejecutar);
    $plan[] = $this->agregarColumnaSiNoExiste("erp_catalogo_sku_reglas_inventario", "permite_etiqueta_fraccionada", "TINYINT(1) NOT NULL DEFAULT 0 AFTER `unidad_venta_label`", $ejecutar);
    $plan[] = $this->agregarColumnaSiNoExiste("erp_catalogo_sku_reglas_inventario", "prefijo_etiqueta_interna", "VARCHAR(30) NULL AFTER `permite_etiqueta_fraccionada`", $ejecutar);
    $plan[] = $this->agregarColumnaSiNoExiste("erp_catalogo_sku_reglas_inventario", "plantilla_etiqueta", "VARCHAR(80) NULL AFTER `prefijo_etiqueta_interna`", $ejecutar);
    $plan[] = $this->agregarColumnaSiNoExiste("erp_catalogo_sku_reglas_inventario", "tipo_etiqueta_seguridad", "VARCHAR(40) NULL AFTER `plantilla_etiqueta`", $ejecutar);
    $plan[] = $this->agregarColumnaSiNoExiste("erp_catalogo_sku_reglas_inventario", "instrucciones_etiquetado", "TEXT NULL AFTER `tipo_etiqueta_seguridad`", $ejecutar);
    $plan[] = $this->agregarColumnaSiNoExiste("erp_catalogo_sku_reglas_inventario", "requiere_cantidad_variable_recepcion", "TINYINT(1) NOT NULL DEFAULT 0 AFTER `dias_minimos_recepcion`", $ejecutar);
    $plan[] = $this->agregarColumnaSiNoExiste("erp_catalogo_sku_reglas_inventario", "requiere_unidades_fisicas_recepcion", "TINYINT(1) NOT NULL DEFAULT 0 AFTER `requiere_cantidad_variable_recepcion`", $ejecutar);
    $plan[] = $this->agregarColumnaSiNoExiste("erp_catalogo_sku_reglas_inventario", "tolerancia_recepcion_porcentaje", "DECIMAL(9,4) NULL AFTER `requiere_unidades_fisicas_recepcion`", $ejecutar);
    $plan[] = $this->agregarColumnaSiNoExiste("erp_catalogo_sku_reglas_inventario", "nota_recepcion_variable", "VARCHAR(255) NULL AFTER `tolerancia_recepcion_porcentaje`", $ejecutar);
    $plan[] = $this->agregarIndiceSiNoExiste("erp_catalogo_sku_reglas_inventario", "idx_catalogo_regla_recepcion_variable", "KEY `idx_catalogo_regla_recepcion_variable` (`requiere_cantidad_variable_recepcion`)", $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("erp_catalogo_sku_presentaciones", array(
      "`id_sku_presentacion_regla` BIGINT NOT NULL AUTO_INCREMENT",
      "`id_sku_base` BIGINT NOT NULL",
      "`id_sku_presentacion` BIGINT NOT NULL",
      "`factor_salida_base` DECIMAL(18,6) NOT NULL",
      "`modo_disponibilidad` VARCHAR(30) NOT NULL DEFAULT 'preparada'",
      "`consume_stock_base_en` VARCHAR(30) NOT NULL DEFAULT 'preparacion'",
      "`requiere_empaque` TINYINT(1) NOT NULL DEFAULT 1",
      "`capacidad_diaria` DECIMAL(18,6) NULL",
      "`merma_porcentaje` DECIMAL(9,4) NOT NULL DEFAULT 0.0000",
      "`estatus` VARCHAR(30) NOT NULL DEFAULT 'activa'",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`fecha_actualizacion` DATETIME NULL",
      "PRIMARY KEY (`id_sku_presentacion_regla`)",
      "UNIQUE KEY `idx_catalogo_presentacion_sku` (`id_sku_presentacion`)",
      "KEY `idx_catalogo_presentacion_base` (`id_sku_base`)",
      "CONSTRAINT `fk_catalogo_presentacion_base` FOREIGN KEY (`id_sku_base`) REFERENCES `erp_catalogo_skus` (`id_sku`)",
      "CONSTRAINT `fk_catalogo_presentacion_sku` FOREIGN KEY (`id_sku_presentacion`) REFERENCES `erp_catalogo_skus` (`id_sku`)"
    ), $opciones, $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("erp_catalogo_sku_paquetes", array(
      "`id_paquete` BIGINT NOT NULL AUTO_INCREMENT",
      "`id_sku_paquete` BIGINT NOT NULL",
      "`tipo_paquete` VARCHAR(30) NOT NULL DEFAULT 'simple'",
      "`modo_disponibilidad` VARCHAR(30) NOT NULL DEFAULT 'por_componentes'",
      "`permite_configuracion_cliente` TINYINT(1) NOT NULL DEFAULT 0",
      "`permite_desarmar` TINYINT(1) NOT NULL DEFAULT 0",
      "`requiere_armado_almacen` TINYINT(1) NOT NULL DEFAULT 0",
      "`observaciones` TEXT NULL",
      "`estatus` VARCHAR(30) NOT NULL DEFAULT 'activo'",
      "`creado_por` INT NULL",
      "`actualizado_por` INT NULL",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`fecha_actualizacion` DATETIME NULL",
      "PRIMARY KEY (`id_paquete`)",
      "UNIQUE KEY `idx_catalogo_paquete_sku` (`id_sku_paquete`)",
      "KEY `idx_catalogo_paquete_tipo` (`tipo_paquete`)",
      "KEY `idx_catalogo_paquete_estatus` (`estatus`)",
      "CONSTRAINT `fk_catalogo_paquete_sku` FOREIGN KEY (`id_sku_paquete`) REFERENCES `erp_catalogo_skus` (`id_sku`)"
    ), $opciones, $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("erp_catalogo_sku_paquete_componentes", array(
      "`id_componente` BIGINT NOT NULL AUTO_INCREMENT",
      "`id_paquete` BIGINT NOT NULL",
      "`id_sku_componente` BIGINT NOT NULL",
      "`cantidad` DECIMAL(18,6) NOT NULL",
      "`id_unidad` INT NULL",
      "`factor_conversion` DECIMAL(18,6) NOT NULL DEFAULT 1.000000",
      "`orden` INT NOT NULL DEFAULT 0",
      "`estatus` VARCHAR(30) NOT NULL DEFAULT 'activo'",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`fecha_actualizacion` DATETIME NULL",
      "PRIMARY KEY (`id_componente`)",
      "KEY `idx_catalogo_paquete_componente_paquete` (`id_paquete`)",
      "KEY `idx_catalogo_paquete_componente_sku` (`id_sku_componente`)",
      "CONSTRAINT `fk_catalogo_paquete_componente_paquete` FOREIGN KEY (`id_paquete`) REFERENCES `erp_catalogo_sku_paquetes` (`id_paquete`)",
      "CONSTRAINT `fk_catalogo_paquete_componente_sku` FOREIGN KEY (`id_sku_componente`) REFERENCES `erp_catalogo_skus` (`id_sku`)",
      "CONSTRAINT `fk_catalogo_paquete_componente_unidad` FOREIGN KEY (`id_unidad`) REFERENCES `erp_catalogo_unidades` (`id_unidad`)"
    ), $opciones, $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("erp_catalogo_sku_paquete_grupos", array(
      "`id_grupo` BIGINT NOT NULL AUTO_INCREMENT",
      "`id_paquete` BIGINT NOT NULL",
      "`codigo` VARCHAR(80) NOT NULL",
      "`nombre` VARCHAR(150) NOT NULL",
      "`descripcion` VARCHAR(255) NULL",
      "`min_selecciones` INT NOT NULL DEFAULT 1",
      "`max_selecciones` INT NOT NULL DEFAULT 1",
      "`modo_cantidad` VARCHAR(30) NOT NULL DEFAULT 'cantidad_fija'",
      "`cantidad_total_grupo` DECIMAL(18,6) NULL",
      "`obligatorio` TINYINT(1) NOT NULL DEFAULT 1",
      "`orden` INT NOT NULL DEFAULT 0",
      "`estatus` VARCHAR(30) NOT NULL DEFAULT 'activo'",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`fecha_actualizacion` DATETIME NULL",
      "PRIMARY KEY (`id_grupo`)",
      "UNIQUE KEY `idx_catalogo_paquete_grupo_codigo` (`id_paquete`, `codigo`)",
      "KEY `idx_catalogo_paquete_grupo_paquete` (`id_paquete`)",
      "CONSTRAINT `fk_catalogo_paquete_grupo_paquete` FOREIGN KEY (`id_paquete`) REFERENCES `erp_catalogo_sku_paquetes` (`id_paquete`)"
    ), $opciones, $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("erp_catalogo_sku_paquete_grupo_opciones", array(
      "`id_opcion` BIGINT NOT NULL AUTO_INCREMENT",
      "`id_grupo` BIGINT NOT NULL",
      "`id_sku_opcion` BIGINT NOT NULL",
      "`cantidad_default` DECIMAL(18,6) NOT NULL DEFAULT 1.000000",
      "`cantidad_minima` DECIMAL(18,6) NULL",
      "`cantidad_maxima` DECIMAL(18,6) NULL",
      "`id_unidad` INT NULL",
      "`factor_conversion` DECIMAL(18,6) NOT NULL DEFAULT 1.000000",
      "`permite_cantidad_editable` TINYINT(1) NOT NULL DEFAULT 0",
      "`orden` INT NOT NULL DEFAULT 0",
      "`estatus` VARCHAR(30) NOT NULL DEFAULT 'activo'",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`fecha_actualizacion` DATETIME NULL",
      "PRIMARY KEY (`id_opcion`)",
      "KEY `idx_catalogo_paquete_opcion_grupo` (`id_grupo`)",
      "KEY `idx_catalogo_paquete_opcion_sku` (`id_sku_opcion`)",
      "CONSTRAINT `fk_catalogo_paquete_opcion_grupo` FOREIGN KEY (`id_grupo`) REFERENCES `erp_catalogo_sku_paquete_grupos` (`id_grupo`)",
      "CONSTRAINT `fk_catalogo_paquete_opcion_sku` FOREIGN KEY (`id_sku_opcion`) REFERENCES `erp_catalogo_skus` (`id_sku`)",
      "CONSTRAINT `fk_catalogo_paquete_opcion_unidad` FOREIGN KEY (`id_unidad`) REFERENCES `erp_catalogo_unidades` (`id_unidad`)"
    ), $opciones, $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("erp_catalogo_canales_vinculos", array(
      "`id_canal_vinculo` BIGINT NOT NULL AUTO_INCREMENT",
      "`id_producto_erp` BIGINT NULL",
      "`id_sku` BIGINT NULL",
      "`canal` VARCHAR(50) NOT NULL",
      "`id_externo` VARCHAR(180) NOT NULL",
      "`sku_externo` VARCHAR(180) NULL",
      "`sincronizar_catalogo` TINYINT(1) NOT NULL DEFAULT 1",
      "`sincronizar_precio` TINYINT(1) NOT NULL DEFAULT 1",
      "`sincronizar_existencia` TINYINT(1) NOT NULL DEFAULT 1",
      "`estatus` VARCHAR(30) NOT NULL DEFAULT 'pendiente'",
      "`ultima_sincronizacion` DATETIME NULL",
      "`ultimo_error` TEXT NULL",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`fecha_actualizacion` DATETIME NULL",
      "PRIMARY KEY (`id_canal_vinculo`)",
      "UNIQUE KEY `idx_catalogo_canal_externo` (`canal`, `id_externo`)",
      "KEY `idx_catalogo_canal_producto` (`id_producto_erp`)",
      "KEY `idx_catalogo_canal_sku` (`id_sku`)",
      "CONSTRAINT `fk_catalogo_canal_producto` FOREIGN KEY (`id_producto_erp`) REFERENCES `erp_catalogo_productos` (`id_producto_erp`)",
      "CONSTRAINT `fk_catalogo_canal_sku` FOREIGN KEY (`id_sku`) REFERENCES `erp_catalogo_skus` (`id_sku`)"
    ), $opciones, $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("erp_catalogo_migracion_ecom_incidencias", array(
      "`id_incidencia` BIGINT NOT NULL AUTO_INCREMENT",
      "`id_producto_ecom` INT NOT NULL",
      "`id_variante_ecom` INT NULL",
      "`sku` VARCHAR(150) NULL",
      "`nombre_producto` VARCHAR(255) NULL",
      "`motivo` VARCHAR(80) NOT NULL",
      "`detalle_json` LONGTEXT NULL",
      "`estatus` VARCHAR(30) NOT NULL DEFAULT 'pendiente'",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`fecha_actualizacion` DATETIME NULL",
      "PRIMARY KEY (`id_incidencia`)",
      "UNIQUE KEY `idx_migracion_ecom_producto` (`id_producto_ecom`)",
      "KEY `idx_migracion_ecom_motivo` (`motivo`)",
      "KEY `idx_migracion_ecom_estatus` (`estatus`)"
    ), $opciones, $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("erp_catalogo_incidencias_calidad", array(
      "`id_incidencia_calidad` BIGINT NOT NULL AUTO_INCREMENT",
      "`huella` VARCHAR(64) NOT NULL",
      "`tipo_incidencia` VARCHAR(80) NOT NULL",
      "`entidad_tipo` VARCHAR(40) NOT NULL DEFAULT 'sku'",
      "`id_producto_erp` BIGINT NULL",
      "`id_sku` BIGINT NULL",
      "`id_referencia` BIGINT NULL",
      "`referencia_tipo` VARCHAR(60) NULL",
      "`origen` VARCHAR(40) NOT NULL DEFAULT 'catalogo'",
      "`severidad` VARCHAR(20) NOT NULL DEFAULT 'advertencia'",
      "`titulo` VARCHAR(180) NOT NULL",
      "`descripcion` VARCHAR(700) NULL",
      "`detalle_json` LONGTEXT NULL",
      "`evidencia_json` LONGTEXT NULL",
      "`propuesta_json` LONGTEXT NULL",
      "`resolucion_json` LONGTEXT NULL",
      "`estatus` VARCHAR(30) NOT NULL DEFAULT 'pendiente'",
      "`responsable_id` INT NULL",
      "`creado_por` INT NULL",
      "`resuelto_por` INT NULL",
      "`fecha_vencimiento` DATETIME NULL",
      "`fecha_resolucion` DATETIME NULL",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`fecha_actualizacion` DATETIME NULL",
      "PRIMARY KEY (`id_incidencia_calidad`)",
      "UNIQUE KEY `idx_catalogo_incidencia_huella` (`huella`)",
      "KEY `idx_catalogo_incidencia_sku` (`id_sku`)",
      "KEY `idx_catalogo_incidencia_producto` (`id_producto_erp`)",
      "KEY `idx_catalogo_incidencia_tipo_estatus` (`tipo_incidencia`, `estatus`)",
      "KEY `idx_catalogo_incidencia_severidad` (`severidad`, `estatus`)",
      "KEY `idx_catalogo_incidencia_origen` (`origen`)",
      "CONSTRAINT `fk_catalogo_incidencia_producto` FOREIGN KEY (`id_producto_erp`) REFERENCES `erp_catalogo_productos` (`id_producto_erp`)",
      "CONSTRAINT `fk_catalogo_incidencia_sku` FOREIGN KEY (`id_sku`) REFERENCES `erp_catalogo_skus` (`id_sku`)"
    ), $opciones, $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("erp_catalogo_imagenes", array(
      "`id_imagen_erp` BIGINT NOT NULL AUTO_INCREMENT",
      "`id_producto_erp` BIGINT NOT NULL",
      "`id_sku` BIGINT NULL",
      "`tipo_imagen` VARCHAR(30) NOT NULL DEFAULT 'galeria'",
      "`url_imagen` VARCHAR(700) NOT NULL",
      "`texto_alternativo` VARCHAR(255) NULL",
      "`orden` INT NOT NULL DEFAULT 0",
      "`fuente` VARCHAR(40) NOT NULL DEFAULT 'erp'",
      "`id_externo` VARCHAR(180) NULL",
      "`estatus` VARCHAR(20) NOT NULL DEFAULT 'activo'",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`fecha_actualizacion` DATETIME NULL",
      "PRIMARY KEY (`id_imagen_erp`)",
      "KEY `idx_catalogo_imagen_producto` (`id_producto_erp`)",
      "KEY `idx_catalogo_imagen_sku` (`id_sku`)",
      "KEY `idx_catalogo_imagen_tipo` (`tipo_imagen`, `estatus`)",
      "CONSTRAINT `fk_catalogo_imagen_producto` FOREIGN KEY (`id_producto_erp`) REFERENCES `erp_catalogo_productos` (`id_producto_erp`)",
      "CONSTRAINT `fk_catalogo_imagen_sku` FOREIGN KEY (`id_sku`) REFERENCES `erp_catalogo_skus` (`id_sku`)"
    ), $opciones, $ejecutar);

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-06-29
     * Proposito: prepara el contrato de imagenes propias para marcas y categorias maestras.
     * Impacto: Catalogo ERP; solo entra al plan/auditoria y no escribe datos si no se ejecuta el esquema con autorizacion.
     */
    $plan[] = $this->crearTablaSiNoExiste("erp_catalogo_marca_imagenes", array(
      "`id_marca_imagen` BIGINT NOT NULL AUTO_INCREMENT",
      "`id_marca_erp` INT NOT NULL",
      "`tipo_imagen` VARCHAR(30) NOT NULL DEFAULT 'logo'",
      "`url_imagen` VARCHAR(700) NOT NULL",
      "`texto_alternativo` VARCHAR(255) NULL",
      "`orden` INT NOT NULL DEFAULT 0",
      "`estatus` VARCHAR(20) NOT NULL DEFAULT 'activo'",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`fecha_actualizacion` DATETIME NULL",
      "PRIMARY KEY (`id_marca_imagen`)",
      "KEY `idx_marca_imagen_marca` (`id_marca_erp`, `estatus`)",
      "KEY `idx_marca_imagen_tipo` (`tipo_imagen`, `estatus`)",
      "CONSTRAINT `fk_marca_imagen_marca` FOREIGN KEY (`id_marca_erp`) REFERENCES `erp_catalogo_marcas` (`id_marca_erp`)"
    ), $opciones, $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("erp_catalogo_categoria_imagenes", array(
      "`id_categoria_imagen` BIGINT NOT NULL AUTO_INCREMENT",
      "`id_categoria_erp` INT NOT NULL",
      "`tipo_imagen` VARCHAR(30) NOT NULL DEFAULT 'icono'",
      "`url_imagen` VARCHAR(700) NOT NULL",
      "`texto_alternativo` VARCHAR(255) NULL",
      "`orden` INT NOT NULL DEFAULT 0",
      "`estatus` VARCHAR(20) NOT NULL DEFAULT 'activo'",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`fecha_actualizacion` DATETIME NULL",
      "PRIMARY KEY (`id_categoria_imagen`)",
      "KEY `idx_categoria_imagen_categoria` (`id_categoria_erp`, `estatus`)",
      "KEY `idx_categoria_imagen_tipo` (`tipo_imagen`, `estatus`)",
      "CONSTRAINT `fk_categoria_imagen_categoria` FOREIGN KEY (`id_categoria_erp`) REFERENCES `erp_catalogo_categorias` (`id_categoria_erp`)"
    ), $opciones, $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("erp_catalogo_revision_nombres", array(
      "`id_revision_nombre` BIGINT NOT NULL AUTO_INCREMENT",
      "`id_producto_erp` BIGINT NOT NULL",
      "`id_sku` BIGINT NOT NULL",
      "`nombre_actual` VARCHAR(255) NOT NULL",
      "`nombre_proveedor` VARCHAR(255) NULL",
      "`nombre_propuesto` VARCHAR(255) NOT NULL",
      "`evidencia_json` LONGTEXT NULL",
      "`estatus` VARCHAR(30) NOT NULL DEFAULT 'pendiente'",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`fecha_actualizacion` DATETIME NULL",
      "PRIMARY KEY (`id_revision_nombre`)",
      "UNIQUE KEY `idx_catalogo_revision_nombre_sku` (`id_sku`)",
      "KEY `idx_catalogo_revision_nombre_producto` (`id_producto_erp`)",
      "KEY `idx_catalogo_revision_nombre_estatus` (`estatus`)",
      "CONSTRAINT `fk_catalogo_revision_nombre_producto` FOREIGN KEY (`id_producto_erp`) REFERENCES `erp_catalogo_productos` (`id_producto_erp`)",
      "CONSTRAINT `fk_catalogo_revision_nombre_sku` FOREIGN KEY (`id_sku`) REFERENCES `erp_catalogo_skus` (`id_sku`)"
    ), $opciones, $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("erp_catalogo_productos_fusiones", array(
      "`id_fusion` BIGINT NOT NULL AUTO_INCREMENT",
      "`id_producto_origen` BIGINT NOT NULL",
      "`id_producto_destino` BIGINT NOT NULL",
      "`motivo` VARCHAR(255) NULL",
      "`skus_movidos` INT NOT NULL DEFAULT 0",
      "`usuario_id` INT NULL",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "PRIMARY KEY (`id_fusion`)",
      "KEY `idx_catalogo_fusion_origen` (`id_producto_origen`)",
      "KEY `idx_catalogo_fusion_destino` (`id_producto_destino`)",
      "CONSTRAINT `fk_catalogo_fusion_origen` FOREIGN KEY (`id_producto_origen`) REFERENCES `erp_catalogo_productos` (`id_producto_erp`)",
      "CONSTRAINT `fk_catalogo_fusion_destino` FOREIGN KEY (`id_producto_destino`) REFERENCES `erp_catalogo_productos` (`id_producto_erp`)"
    ), $opciones, $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("erp_catalogo_taxonomias", array(
      "`id_taxonomia` INT NOT NULL AUTO_INCREMENT",
      "`codigo` VARCHAR(60) NOT NULL",
      "`nombre` VARCHAR(150) NOT NULL",
      "`tipo` VARCHAR(30) NOT NULL DEFAULT 'navegacion'",
      "`canal` VARCHAR(50) NULL",
      "`descripcion` TEXT NULL",
      "`estatus` VARCHAR(20) NOT NULL DEFAULT 'activa'",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`fecha_actualizacion` DATETIME NULL",
      "PRIMARY KEY (`id_taxonomia`)",
      "UNIQUE KEY `idx_catalogo_taxonomia_codigo` (`codigo`)"
    ), $opciones, $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("erp_catalogo_taxonomia_nodos", array(
      "`id_nodo_taxonomia` BIGINT NOT NULL AUTO_INCREMENT",
      "`id_taxonomia` INT NOT NULL",
      "`id_nodo_padre` BIGINT NULL",
      "`id_categoria_erp` INT NULL",
      "`tipo_nodo` VARCHAR(30) NOT NULL DEFAULT 'categoria'",
      "`codigo` VARCHAR(100) NOT NULL",
      "`nombre` VARCHAR(200) NOT NULL",
      "`ruta` VARCHAR(1000) NOT NULL",
      "`nivel` INT NOT NULL DEFAULT 0",
      "`orden` INT NOT NULL DEFAULT 0",
      "`id_externo` VARCHAR(180) NULL",
      "`estatus` VARCHAR(20) NOT NULL DEFAULT 'activo'",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`fecha_actualizacion` DATETIME NULL",
      "PRIMARY KEY (`id_nodo_taxonomia`)",
      "UNIQUE KEY `idx_taxonomia_nodo_codigo` (`id_taxonomia`, `codigo`)",
      "KEY `idx_taxonomia_nodo_padre` (`id_nodo_padre`)",
      "KEY `idx_taxonomia_nodo_categoria` (`id_categoria_erp`)",
      "CONSTRAINT `fk_taxonomia_nodo_taxonomia` FOREIGN KEY (`id_taxonomia`) REFERENCES `erp_catalogo_taxonomias` (`id_taxonomia`)",
      "CONSTRAINT `fk_taxonomia_nodo_padre` FOREIGN KEY (`id_nodo_padre`) REFERENCES `erp_catalogo_taxonomia_nodos` (`id_nodo_taxonomia`)",
      "CONSTRAINT `fk_taxonomia_nodo_categoria` FOREIGN KEY (`id_categoria_erp`) REFERENCES `erp_catalogo_categorias` (`id_categoria_erp`)"
    ), $opciones, $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("erp_catalogo_producto_taxonomia_nodos", array(
      "`id_producto_nodo` BIGINT NOT NULL AUTO_INCREMENT",
      "`id_producto_erp` BIGINT NOT NULL",
      "`id_nodo_taxonomia` BIGINT NOT NULL",
      "`es_principal` TINYINT(1) NOT NULL DEFAULT 0",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "PRIMARY KEY (`id_producto_nodo`)",
      "UNIQUE KEY `idx_producto_taxonomia_nodo` (`id_producto_erp`, `id_nodo_taxonomia`)",
      "KEY `idx_producto_taxonomia_nodo_nodo` (`id_nodo_taxonomia`)",
      "CONSTRAINT `fk_producto_taxonomia_producto` FOREIGN KEY (`id_producto_erp`) REFERENCES `erp_catalogo_productos` (`id_producto_erp`)",
      "CONSTRAINT `fk_producto_taxonomia_nodo` FOREIGN KEY (`id_nodo_taxonomia`) REFERENCES `erp_catalogo_taxonomia_nodos` (`id_nodo_taxonomia`)"
    ), $opciones, $ejecutar);

    $referenciasOperativas = array(
      "erp_compras_ordenes_detalle" => "idx_compra_detalle_sku_erp",
      "erp_almacen_recepciones_detalle" => "idx_recepcion_detalle_sku_erp",
      "erp_almacen_recepciones_lotes" => "idx_recepcion_lote_sku_erp",
      "erp_inventario_movimientos" => "idx_inventario_mov_sku_erp",
      "erp_inventario_existencias" => "idx_inventario_existencia_sku_erp"
    );
    foreach ($referenciasOperativas as $tablaOperativa => $indiceOperativo) {
      $plan[] = $this->agregarColumnaSiNoExiste($tablaOperativa, "id_sku_erp", "BIGINT NULL", $ejecutar);
      $plan[] = $this->agregarIndiceSiNoExiste($tablaOperativa, $indiceOperativo, "KEY `" . $indiceOperativo . "` (`id_sku_erp`)", $ejecutar);
    }

    if ($this->columnaExiste("erp_inventario_existencias", "id_sku_erp")) {
      $descripcionExistencias = $this->describirTabla("erp_inventario_existencias");
      foreach ($descripcionExistencias["depurar"] as $columnaExistencia) {
        if ($columnaExistencia["columna"] === "id_sku_erp" && ($columnaExistencia["permite_null"] !== "NO" || (string) $columnaExistencia["valor_default"] !== "0")) {
          $plan[] = $this->modificarColumna("erp_inventario_existencias", "id_sku_erp", "BIGINT NOT NULL DEFAULT 0", $ejecutar);
          break;
        }
      }
      $columnasIndiceExistencia = array("id_producto", "id_sku_erp", "id_almacen_clave", "lote_clave", "fecha_caducidad_clave", "ubicacion_clave");
      if ($this->columnasIndice("erp_inventario_existencias", "idx_existencia_producto_lote_ubicacion") !== $columnasIndiceExistencia) {
        $plan[] = $this->reemplazarIndice(
          "erp_inventario_existencias",
          "idx_existencia_producto_lote_ubicacion",
          "UNIQUE KEY `idx_existencia_producto_lote_ubicacion` (`id_producto`, `id_sku_erp`, `id_almacen_clave`, `lote_clave`, `fecha_caducidad_clave`, `ubicacion_clave`)",
          $ejecutar
        );
      }
    }

    $plan = array_merge($plan, $this->planSemillaUnidades($ejecutar));

    return array(
      "error" => $this->planTieneErrores($plan),
      "tipo" => $this->planTieneErrores($plan) ? "danger" : "success",
      "mensaje" => $ejecutar ? "Esquema del catalogo maestro ERP ejecutado" : "Plan del catalogo maestro ERP generado en dry-run",
      "depurar" => $plan
    );
  }

  private function planTieneErrores($plan) {
    foreach ($plan as $paso) {
      if (!empty($paso["error"])) {
        return true;
      }
    }
    return false;
  }

  private function planSemillaUnidades($ejecutar) {
    $unidades = array(
      array("PZA", "Pieza", "pza", "unidad", 0, "H87"),
      array("KG", "Kilogramo", "kg", "masa", 1, "KGM"),
      array("G", "Gramo", "g", "masa", 1, "GRM"),
      array("L", "Litro", "L", "volumen", 1, "LTR"),
      array("ML", "Mililitro", "ml", "volumen", 1, "MLT"),
      array("M", "Metro", "m", "longitud", 1, "MTR"),
      array("CM", "Centimetro", "cm", "longitud", 1, "CMT"),
      array("CAJA", "Caja", "caja", "empaque", 0, "XBX"),
      array("PAQ", "Paquete", "paq", "empaque", 0, "XPK"),
      array("SERV", "Servicio", "serv", "servicio", 1, "E48")
    );
    $plan = array();

    foreach ($unidades as $unidad) {
      $sql = "INSERT INTO erp_catalogo_unidades
                (codigo, nombre, abreviatura, tipo_magnitud, decimales_permitidos, clave_sat, estatus)
              VALUES
                (" . $this->sqlTexto($unidad[0]) . ", " . $this->sqlTexto($unidad[1]) . ", " . $this->sqlTexto($unidad[2]) . ", " .
                $this->sqlTexto($unidad[3]) . ", " . intval($unidad[4]) . ", " . $this->sqlTexto($unidad[5]) . ", 'activa')
              ON DUPLICATE KEY UPDATE
                nombre = VALUES(nombre),
                abreviatura = VALUES(abreviatura),
                tipo_magnitud = VALUES(tipo_magnitud),
                decimales_permitidos = VALUES(decimales_permitidos),
                clave_sat = VALUES(clave_sat),
                estatus = VALUES(estatus),
                fecha_actualizacion = CURRENT_TIMESTAMP;";
      $plan[] = $this->ejecutarSemilla($sql, $ejecutar);
    }
    return $plan;
  }

  private function ejecutarSemilla($sql, $ejecutar) {
    if (!$ejecutar) {
      return array("error" => false, "tipo" => "info", "mensaje" => "SQL de semilla generado sin ejecutar", "depurar" => array("sql" => $sql, "ejecutado" => false));
    }

    try {
      $db = $this->conectar();
      $stmt = $db->prepare($sql);
      $stmt->execute();
      return array("error" => false, "tipo" => "success", "mensaje" => "Unidad base registrada", "depurar" => array("ejecutado" => true));
    } catch (Exception $e) {
      return array("error" => true, "tipo" => "danger", "mensaje" => $e->getMessage(), "depurar" => $sql);
    }
  }

  private function sqlTexto($valor) {
    return "'" . str_replace("'", "''", $valor) . "'";
  }
}
